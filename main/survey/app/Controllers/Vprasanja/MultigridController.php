<?php
/***************************************
 * Description: Multigrid: dropdown, select box, checkbox, multiple
 *
 * Vprašanje je prisotno:
 *  tip 6 - enota 2, enota 6
 *  tip 16 - enota 3, enota 6, enota 0
 *  tip 19
 *  tip 20
 *  tip 24
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


class MultigridController extends Controller
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

        return new MultigridController();
    }

    protected $spremenljivka;

    /**
     * @desc prikaze vnosno polje za navadno tabelo in tabelo diferencial
     * Stara funkcija $this->displayMultigrid($spremenljivka);
     */
    public function display($spremenljivka)
    {
        global $lang;

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        // Pri vpogledu moramo skriti name atribut pri loopih, da se izpise prava vrednost
        $hideName = ((get('loop_id') != null) && ($_GET['m'] == 'quick_edit')) ? true : false;

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $spremenljivkaParams = new enkaParameters($row['params']);
		
        // prej:
        // $gridWidth = (($spremenljivkaParams->get('gridWidth') > 0) ? $spremenljivkaParams->get('gridWidth') : 30);
        // tole prej je klicalo funkcijo 2x. Ker imam še opcijo -2, bom naredil z enim klicem in več kode tu.
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


		// Za izris traku
		$diferencial_trak = ($spremenljivkaParams->get('diferencial_trak') ? $spremenljivkaParams->get('diferencial_trak') : 0); //za checkbox trak
		$trak_num_of_titles = ($spremenljivkaParams->get('trak_num_of_titles') ? $spremenljivkaParams->get('trak_num_of_titles') : 0); //belezi stevilo nadnaslovov
		if($diferencial_trak == 1 && ($row['enota'] == 1 || $row['enota'] == 0)){	//ce je trak vklopljen @ diferencial ali klasicna tabela
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
        }
        else{
			$trak_class = '';
			$trak_class_input = '';
			$question = 'question';
			$display_trak_num_of_titles = 'style="display:none;"';
		}
		
		for($i = 1; $i <= $trak_num_of_titles; $i++){
			$trak_nadnaslov[$i] = ($spremenljivkaParams->get('trak_nadnaslov_'.$i.'') ? $spremenljivkaParams->get('trak_nadnaslov_'.$i.'') : $lang['srv_new_text']);
		}
		// Za izris traku - konec
		
		
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
        if ($row['enota'] == 1) {
            $size += 2;
        }

        # če imamo nastavljno sirino prvega grida ostalih ne nastavljamo
        if ($gridWidth == 30) {
            $cellsize = round(80 / $size);
        } else {
            $cellsize = 'auto';
        }

        $spacesize = round(80 / $size / 4);

        $bg = 1;


        // IZRIS TABELE
        echo '<table class="grid_table multigrid">' . "\n";


        // Colgroup
        // ce je gridWidth enak nula, imamo skrito labelo.
        // hideLabels bom posredoval naprej v funkcije
        if ($hideLabels == false) {
            echo '<colgroup class="question">';
            echo '  <col class="width_' . $gridWidth . '">';
            echo '</colgroup>';

            echo '<colgroup>';
            echo '  <col class="space">';
            echo '</colgroup>';
        }



        echo '<colgroup class="category">';
        for ($i = 1; $i <= $row['grids']; $i++) {
            echo '<col>';
        }
        echo '</colgroup>';


        if ($mv_count > 0) {
            echo '<colgroup>';
            echo '  <col class="space">';
            echo '</colgroup>';

            echo '<colgroup class="missing">';
            for ($i = 1; $i <= $mv_count; $i++)
                echo '<col>';
            echo '</colgroup>';
        }
        if ($row['enota'] > 0 && !in_array($row['enota'], [11,12])) {
            // desnih label ne bom skrival, ker je neumno dati dvojne labele + skrite labele!
                echo '<colgroup>';
                echo '  <col class="space">';
                echo '</colgroup>';

                echo '<colgroup class="differential">';
                echo '  <col class="width_' . $gridWidth . '">';
                echo '</colgroup>';
        }
        // Colgroup - konec


        // Header vrstica
        echo '<thead>';
        $this->displayMultigridHeader($spremenljivka, $row, $srv_grids, $mv_count, $cssAlign, $diferencial_trak, $trak_nadnaslov, $display_trak_num_of_titles, $middle_row=false, $hideLabels);
        echo '</thead>';
    

        // Body tabele
        echo '<tbody>';

        $bg++;

        $orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

        # cache tabele srv_data_grid, da se ne dela vsakic posebej nov query (preberemo enkrat vse odgovore userja)
        $srv_data_grid = array();
        $sql_grid = sisplet_query("SELECT * FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        while ($row_grid = mysqli_fetch_array($sql_grid)) {
            $srv_data_grid[$row_grid['vre_id']] = $row_grid;
        }
		
		// Ali skrivamo radio gumbe in checkboxe
		$presetValue = ($spremenljivkaParams->get('presetValue') > 0 && empty($srv_data_grid)) ? $spremenljivkaParams->get('presetValue') : 0;

        # loop skozi srv_vrednost, da izpišemo vrstice z vrednostmi
        $sql1 = sisplet_query("SELECT id, naslov, naslov2, hidden, other FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
        while ($row1 = mysqli_fetch_array($sql1)) {
            
			# po potrebi prevedemo naslov
            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '') {
                $row1['naslov'] = $naslov;
            }
			
			# po potrebi prevedemo naslov2 za semanticni diferencial
			if($row['enota'] == 1){
				$naslov2 = Language::getInstance()->srv_language_vrednost($row1['id'], true);
				if ($naslov2 != '') {
					$row1['naslov2'] = $naslov2;
				}
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

            echo '	<tr id="vrednost_if_'.$row1['id'].'" '.(($row1['hidden'] == 1) ? ' style="display:none"' : '') . (($row1['hidden'] == 2) ? ' class="answer-disabled"' : '').'">' . "\n";

            if ($hideLabels == false) {

                echo '		<td class="'.$question.'">';

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
                echo '		<td></td>' . "\n";
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
                        echo '<td class="missing ' . $cssAlign . '">';

                        # imamo missing vrednost
                        echo '<label for="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '">';
                        echo '<input type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="grid_missing_value_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . ((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) ? ' checked' : '') . ' data-calculation="0" vre_id="' . $row1['id'] . '" onclick="checkChecked(this); checkTableMissing(this); checkBranching(); setCheckedClass(this, null, ' . $row1['id'] . '); trak_change_bg(this, '.$diferencial_trak.', '.$srv_grid['spr_id'].', 1);">';

                        // Font awesome
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                            '></span>';
                        echo '</label>';

                        echo '</td>' . "\n";
                    } 
                    else {
						if($diferencial_trak == 1 && ($row['enota'] == 1 || $row['enota'] == 0) ){
							echo '<td onClick="checkBranching(); trak_change_bg(this, '.$diferencial_trak.', '.$srv_grid['spr_id'].', 0);" id="trak_tbl_' . $row1['id'] . '_'.$srv_grid['vrstni_red'].'" class="category' . ((Helper::getCustomCheckbox() != 0) ? ' custom-radio custom-size-' . Helper::getCustomCheckbox() : '') . ' '.$trak_class.' '.(((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing) ? 'trak_container_bg' : '').'">';
                            
                            # ni missing vrednost
							echo '<input vre_id = '.$row1['id'].' class="'.$trak_class_input.'" type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '">';
							echo '<label class="radio-button-label">'.$srv_grid['variable'].'</label>';
                            
                            echo '</td>' . "\n";						
                        }
                        else{
                            // Pri VAS in slikovnem tiput mora biti 'checked' označen tudi pri TD elementu
                            $_checked = (((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing) ? ' checked' : '');

                            // V kolikor imamo vizalno skalo in smeške
						    if($row['enota'] == 11){
                                $tabelaSmeski = 'visual-radio-scale visual-radio-table';
                            }
                            elseif($row['enota'] == 12){
                                $tabelaSmeski = 'custom_radio_picture custom-radio-table';
                            }
                            else{
                                $tabelaSmeski ='';
                            }

							echo '<td class="category '.$tabelaSmeski. $_checked.' ' . $cssAlign . '">';
							# ni missing vrednost
                            echo '<label for="vrednost_' . $row1['id'] . '_grid_' . $value . '">';
							echo '<input type="radio" ' . (!$hideName ? ' name="vrednost_' . $row1['id'] . '"' : '') . ' id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . $_checked . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '" vre_id = '.$row1['id'].' onclick="checkChecked(this); checkBranching(); setCheckedClass(this, null, ' . $row1['id'] . '); customRadioTableSelect(' . $row1['id'] . ', ' . $value. ');">';

                            if($row['enota'] == 11){
                                echo '<span class="enka-vizualna-skala siv-'.$row['grids'].$value.'"></span>';
                            }elseif($row['enota'] == 12){
                                echo '<span class="enka-custom-radio '.$spremenljivkaParams->get('customRadio').'"></span>';
                            }else {
                                // Font awesome
                                echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? 'answer-disabled' : '') . '"' .
                                    ((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
                                    '></span>';
                            }
							echo '</label>';

                            //Pri smeških moramo pognati JS, da doda ustrezen razred 'obarvan'
                            if($row['enota'] == 12 && ((($grid_id == $value && $grid_id != '') || ($presetValue == $value && $presetValue != 0)) && !$is_missing)){
                                echo '<script>
                                         $(document).ready( function(){ customRadioTableSelect(\''.$row1['id'].'\', \''.$value.'\'); } );
                                 </script>';
                            }

							echo '</td>' . "\n";
						}
                    }
                }
            }
            # dodamo še enoto
            if ($row['enota'] == 1) {
                // Datapiping
                $row1['naslov2'] = Helper::dataPiping($row1['naslov2']);

                echo '		<td></td>' . "\n";
                echo '		<td class="differential question_trak">' . $row1['naslov2'] . '</td>' . "\n";
            }
            echo '	</tr>' . "\n";
            
            $bg++;
        }

        echo '</tbody>';

        echo '</table>' . "\n";
        
        
		// za ureditev prilagajanja label stolpcev - prilagajanje trem opisnim nadnaslovom
        $custom_column_label_option = ($spremenljivkaParams->get('custom_column_label_option') ? $spremenljivkaParams->get('custom_column_label_option') : 1);
        echo '<script>
                change_custom_column_label_respondent(\'' . $row['grids'] . '\', \'' . $row['id'] . '\', \'' . $custom_column_label_option . '\');
            </script>';				
            
        // prilagajanje stevilu izbranih nadnaslovov
        if($trak_num_of_titles != 0){
            ?>
                <script>
                    $(document).ready(function(){
                        trak_edit_num_titles_respondent(<?=$row['grids']?>, <?=$spremenljivka?>, <?=$trak_num_of_titles?>, <?=json_encode($trak_nadnaslov)?>);
                    });
                </script>
            <?
        }
        // za ureditev prilagajanja label stolpcev - konec
        

        // JS za ponvaljanje naslovne vrstice
        $grid_repeat_header = ($spremenljivkaParams->get('grid_repeat_header') ? $spremenljivkaParams->get('grid_repeat_header') : 0);        
        if($grid_repeat_header > 0){
            echo '<script> gridRepeatHeader(\''.$grid_repeat_header.'\', \''.$row['id'].'\'); </script>';	
        }
    }

    /************************************************
     * Stara funkcija $this->displayMultigridDropdown($spremenljivka);
     ************************************************/
    public function dropdown($spremenljivka)
    {
        global $lang;

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $_otherStatusFields = array(99 => 'undecided', 98 => 'rejected', 97 => 'inappropriate');

        $spremenljivkaParams = new enkaParameters($row['params']);
        // $gridWidth = (($spremenljivkaParams->get('gridWidth') > 0) ? $spremenljivkaParams->get('gridWidth') : 30);
        // tole prej je klicalo  funkcijo 2x. Ker imam še opcijo -2, bom naredil z enim klicem in več kode tu.
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
        if ($gridAlign == 0)
            $cssAlign = ' class="alignCenter"';
        elseif ($gridAlign == 1)
            $cssAlign = ' class="alignLeft"';
        elseif ($gridAlign == 2)
            $cssAlign = ' class="alignRight"';

        echo '      <table class="grid_table multigriddropdown">' . "\n";
        if ($hideLabels == false) {
            echo '<colgroup class="question">';
            echo '<col class="width_' . $gridWidth . '">';
            echo '</colgroup>';
            echo '<colgroup>';
            echo '<col class="space">';
            echo '</colgroup>';
        }
        echo '<colgroup class="category">';
        echo '<col>';
        echo '</colgroup>';

        echo '<tbody>';

        $bg++;

        $orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

        // cache tabele srv_grid, da se ne bere vsakic znova
        $srv_grids = array();
		
        $sql_grid = sisplet_query("SELECT * FROM srv_grid WHERE spr_id = '$row[id]'");
        while ($row_grid = mysqli_fetch_array($sql_grid)) {

            $naslov = Language::srv_language_grid($row['id'], $row_grid['id']);
            if ($naslov != '') $row_grid['naslov'] = $naslov;

            $srv_grids[$row_grid['vrstni_red']] = $row_grid;
        }

        // cache tabele srv_data_grid, da se ne dela vsakic posebej nov query (preberemo enkrat vse odgovore userja)
        $srv_data_grid = array();
        $sql_grid = sisplet_query("SELECT * FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        while ($row_grid = mysqli_fetch_array($sql_grid)) {
            $srv_data_grid[$row_grid['vre_id']] = $row_grid;
        }

        $sql1 = sisplet_query("SELECT id, naslov, hidden, other FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
        while ($row1 = mysqli_fetch_array($sql1)) {

            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '') $row1['naslov'] = $naslov;

            // Datapiping
            $row1['naslov'] = Helper::dataPiping($row1['naslov']);

            echo '<tr id="vrednost_if_' . $row1['id'] . '" ' . (($row1['hidden'] == 1) ? 'style="display:none"' : '') . (($row1['hidden'] == 2) ? 'class="answer-disabled"' : '') . '>' . "\n";
            
            if ($hideLabels == false) {
            echo '<td class="question">' . $row1['naslov'];
                if ($row1['other'] == 1) {
                    $sql3 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND vre_id='$row1[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
                    $row3 = mysqli_fetch_array($sql3);

                    $otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
                    $otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

                    if ($otherHeight > 1)
                        echo ' <textarea name="textfield_' . $row1['id'] . '" rows="' . $otherHeight . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '">' . $row3['text'] . '</textarea>';
                    else
                        echo ' <input type="text" name="textfield_' . $row1['id'] . '" value="' . $row3['text'] . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" />';

                    //echo '   <input type="text" name="textfield_'.$row1['id'].'" value="'.$row3['text'].'">';
                }
                echo '          </td>' . "\n";
                echo '          <td></td>' . "\n";
            }


            $grid_id = $srv_data_grid[$row1['id']]['grd_id'];

            echo '<td ' . $cssAlign . '>';


            if ($row['enota'] == 2) {
                $spremenljivkaParams = new enkaParameters($row['params']);
                $prvaVrstica_roleta = ($spremenljivkaParams->get('prvaVrstica_roleta') ? $spremenljivkaParams->get('prvaVrstica_roleta') : 1);

                //echo '<select multiple name="vrednost_'.$row1['id'].'" id="vrednost_'.$spremenljivka.'_'.$row1['id'].'" size="'.$sbSize.'" onchange="checkBranching(); omejiSelectBoxMulti('.$spremenljivka.','.$row1['id'].'); clickSelectBox('.$spremenljivka.');" multiple = " ">'."\n";
                echo '<select name="vrednost_' . $row1['id'] . '" id="vrednost_' . $spremenljivka . '_' . $row1['id'] . '" size="1" onclick="checkBranching(); omejiSelectBoxMulti(' . $spremenljivka . ',' . $row1['id'] . '); clickSelectBoxMulti(' . $spremenljivka . ',' . $row1['id'] . ');"	>' . "\n";
                switch ($prvaVrstica_roleta) {
                    case "1":
                        echo '        <option value=""></option>';
                        break;
                    case "2":

                        break;
                    case "3":
                        echo '        <option value="">' . $lang['srv_dropdown_select'] . '...</option>';
                        break;
                }
            }
	
			if (count($srv_grids) > 0) {
                foreach ($srv_grids AS $i => $srv_grid) {
					
					$row5 = $srv_grid;
					
                    $value = $srv_grid['id'];
                    $vsebina = '';
					
					echo '<option name="vrednost_' . $row1['id'] . '_' . $value . '" id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' selected="true"' : '') . ' data-calculation="' . $row5['variable'] . '" onclick="checkBranching();">' . $row5['naslov'] . '</option>';
				}
			}
			

            echo '</select>' . "\n";
            echo '</td>' . "\n";

            echo '</tr>' . "\n";

            $bg++;
        }

        echo '</tbody>';

        echo '</table>' . "\n";
    }

    /************************************************
     * Stara funkcija $this->displayMultigridSelectBox($spremenljivka);d
     ************************************************/
    public function selectBox($spremenljivka)
    {
        global $lang;

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $_otherStatusFields = array(99 => 'undecided', 98 => 'rejected', 97 => 'inappropriate');

        $spremenljivkaParams = new enkaParameters($row['params']);
        // $gridWidth = (($spremenljivkaParams->get('gridWidth') > 0) ? $spremenljivkaParams->get('gridWidth') : 30);

        // tole prej je klicalo  funkcijo 2x. Ker imam še opcijo -2, bom naredil z enim klicem in več kode tu.
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

        if ($gridAlign == 0)
            $cssAlign = ' class="alignCenter"';
        elseif ($gridAlign == 1)
            $cssAlign = ' class="alignLeft"';
        elseif ($gridAlign == 2)
            $cssAlign = ' class="alignRight"';

        echo '      <table class="grid_table multigriddropdown">' . "\n";

        if ($hideLabels ==false) {
            echo '<colgroup class="question">';
            echo '<col class="width_' . $gridWidth . '">';
            echo '</colgroup>';
            echo '<colgroup>';
            echo '<col class="space">';
            echo '</colgroup>';
        }
        echo '<colgroup class="category">';
        echo '<col>';
        echo '</colgroup>';

        echo '<tbody>';

        $bg++;

        $orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

		$missingi = array();
				
        // cache tabele srv_grid, da se ne bere vsakic znova
        $srv_grid = array();
        $sql_grid = sisplet_query("SELECT * FROM srv_grid WHERE spr_id = '$row[id]'");
        while ($row_grid = mysqli_fetch_array($sql_grid)) {

            $naslov = Language::srv_language_grid($row['id'], $row_grid['id']);
            if ($naslov != '') $row_grid['naslov'] = $naslov;

            $srv_grid[$row_grid['vrstni_red']] = $row_grid;
			
			//belezenje missingov
			if($row_grid['other']!=0){
				array_push($missingi, $row_grid['naslov']);			
			}			
        }
		
        // cache tabele srv_data_grid, da se ne dela vsakic posebej nov query (preberemo enkrat vse odgovore userja)
        $srv_data_grid = array();
        $sql_grid = sisplet_query("SELECT * FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        while ($row_grid = mysqli_fetch_array($sql_grid)) {
            $srv_data_grid[$row_grid['vre_id']] = $row_grid;
        }

        $sql1 = sisplet_query("SELECT id, naslov, hidden, other FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
        while ($row1 = mysqli_fetch_array($sql1)) {

            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '') $row1['naslov'] = $naslov;

            // Datapiping
            $row1['naslov'] = Helper::dataPiping($row1['naslov']);

            echo '<tr id="vrednost_if_' . $row1['id'] . '" ' . (($row1['hidden'] == 1) ? 'style="display:none"' : '') . (($row1['hidden'] == 2) ? 'class="answer-disabled"' : '') . '>' . "\n";

            if ($hideLabels == false) {
                echo '<td class="question">' . $row1['naslov'];
                if ($row1['other'] == 1) {
                    $sql3 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND vre_id='$row1[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
                    $row3 = mysqli_fetch_array($sql3);

                    $otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
                    $otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

                    if ($otherHeight > 1)
                        echo ' <textarea name="textfield_' . $row1['id'] . '" rows="' . $otherHeight . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '">' . $row3['text'] . '</textarea>';
                    else
                        echo ' <input type="text" name="textfield_' . $row1['id'] . '" value="' . $row3['text'] . '" style="' . ($otherWidth != -1 ? ' width:' . $otherWidth . '%;' : '') . '" />';

                    //echo '   <input type="text" name="textfield_'.$row1['id'].'" value="'.$row3['text'].'">';
                }
                echo '          </td>' . "\n";
                echo '          <td></td>' . "\n";
            }


            $grid_id = $srv_data_grid[$row1['id']]['grd_id'];

            echo '<td ' . $cssAlign . '>';

            // todo id se podvaja - popravljeno
            //echo '<select name="vrednost_'.$row1['id'].'" id="vrednost_'.$spremenljivka.'_'.$row1['id'].'" size="1" onchange="checkBranching();" >'."\n";

            if ($row['tip'] == 6) {
                $spremenljivkaParams = new enkaParameters($row['params']);
                $sbSize = ($spremenljivkaParams->get('sbSize') ? $spremenljivkaParams->get('sbSize') : 1);
                $prvaVrstica = ($spremenljivkaParams->get('prvaVrstica') ? $spremenljivkaParams->get('prvaVrstica') : 1);
                if ($prvaVrstica != 1) {
                    $sbSize = $sbSize + 1;
                }
                //echo '<select multiple name="vrednost_'.$row1['id'].'" id="vrednost_'.$spremenljivka.'_'.$row1['id'].'" size="'.$sbSize.'" onchange="checkBranching(); omejiSelectBoxMulti('.$spremenljivka.','.$row1['id'].'); clickSelectBox('.$spremenljivka.');" multiple = " ">'."\n";
                echo '<select multiple name="vrednost_' . $row1['id'] . '" id="vrednost_' . $spremenljivka . '_' . $row1['id'] . '" size="' . $sbSize . '" onclick="checkBranching(); omejiSelectBoxMulti(' . $spremenljivka . ',' . $row1['id'] . '); clickSelectBoxMulti(' . $spremenljivka . ',' . $row1['id'] . ');" multiple = " ">' . "\n";
                switch ($prvaVrstica) {
                    case "1":

                        break;
                    case "2":
                        echo '        <option value=""></option>';
                        break;
                    case "3":
                        echo '        <option value="">' . $lang['srv_dropdown_select'] . '...</option>';
                        break;
                }


            } elseif ($row['tip'] == 16) {
                $spremenljivkaParams = new enkaParameters($row['params']);
                $sbSize = ($spremenljivkaParams->get('sbSize') ? $spremenljivkaParams->get('sbSize') : 1);
                $prvaVrstica = ($spremenljivkaParams->get('prvaVrstica') ? $spremenljivkaParams->get('prvaVrstica') : 1);
                if ($prvaVrstica != 1) {
                    $sbSize = $sbSize + 1;
                }

                echo '<select name="vrednost_' . $row1['id'] . '[]" id="vrednost_' . $spremenljivka . '_' . $row1['id'] . '" size="' . $sbSize . '" onclick="checkBranching(); clickSelectBoxMulti(' . $spremenljivka . ',' . $row1['id'] . ');" multiple = " ">' . "\n";
                switch ($prvaVrstica) {
                    case "1":

                        break;
                    case "2":
                        echo '        <option value=""></option>';
                        break;
                    case "3":
                        echo '        <option value="">' . $lang['srv_dropdown_select'] . '...</option>';
                        break;
                }

            }

            //echo '<option value=""></option>'."\n";
			
            //for ($i = 1; $i <= $row['grids']; $i++) {
            for ($i = 1; $i <= count($srv_grid); $i++) {

                $row5 = $srv_grid[$i];
				$value1 = $value2 = $row5['id'];
				
				if($row5['other']!=0){	//ce so missingi
					$value2 = $row5['other'];										
				}               

                // todo name je obsolete - popravljeno
                if ($row['tip'] == 16) {
                    //echo '<option name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' selected="true"' : '') . ' data-calculation="' . $row5['variable'] . '" onclick="checkBranching();">' . $row5['naslov'] . '</option>';
                    echo '<option name="vrednost_' . $row1['id'] . '_grid_' . $value1 . '" id="vrednost_' . $row1['id'] . '_grid_' . $valu1e . '" value="' . $value2 . '"' . (($grid_id == $value1 && $grid_id != '') ? ' selected="true"' : '') . ' data-calculation="' . $row5['variable'] . '" onclick="checkBranching();">' . $row5['naslov'] . '</option>';
                    //echo '<option name="vrednost_' . $row1['id'] . '_grid_' . $value1 . '" id="vrednost_' . $row1['id'] . '_grid_' . $valu1e . '" value="' . $value2 . '"' . (($grid_id == $value1 && $grid_id != '') ? ' selected="true"' : '') . ' data-calculation="0" onclick="checkBranching();">' . $row5['naslov'] . '</option>';
                } else {
                    //echo '<option name="vrednost_' . $row1['id'] . '_' . $i . '" id="vrednost_' . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' selected="true"' : '') . ' data-calculation="' . $row5['variable'] . '" onclick="checkBranching();">' . $row5['naslov'] . '</option>';
                    echo '<option name="vrednost_' . $row1['id'] . '_' . $i . '" id="vrednost_' . $row1['id'] . '_grid_' . $value1 . '" value="' . $value2 . '"' . (($grid_id == $value1 && $grid_id != '') ? ' selected="true"' : '') . ' data-calculation="' . $row5['variable'] . '" onclick="checkBranching();">' . $row5['naslov'] . '</option>';
                }
            }

            echo '</select>' . "\n";
            echo '</td>' . "\n";

            echo '</tr>' . "\n";

            $bg++;
        }

        echo '</tbody>';

        echo '</table>' . "\n";
    }

    /************************************************
     * Stara funkcija  $this->displayMultigridSelectBox($spremenljivka);
     ************************************************/
    public function checkbox($spremenljivka)
    {
        global $lang;

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        // izracuni za sirino celic
        $size = $row['grids'];

        $spremenljivkaParams = new enkaParameters($row['params']);
        
        // za združljivost za nazaj dodam tudi v "staro funkcijo" opcijo prikaza brez labele :)
        // $gridWidth = (($spremenljivkaParams->get('gridWidth') > 0) ? $spremenljivkaParams->get('gridWidth') : 30);
        // tole prej je klicalo  funkcijo 2x. Ker imam še opcijo -2, bom naredil z enim klicem in več kode tu.
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

        // polovimo vrednosti gridov, prevedmo naslove in hkrati preverimo ali imamo missinge
        $srv_grids = array();
        $mv_count = 0; # koliko je stolpcev z manjkajočimi vrednostmi
       
        // če polje other != 0 je grid kot missing
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

        echo '      <table class="grid_table multigridcheckbox">' . "\n";

        if ($hideLabels == false) {
            echo '<colgroup class="question">';
            echo '  <col class="width_' . $gridWidth . '">';
            echo '</colgroup>';

            echo '<colgroup>';
            echo '  <col class="space">';
            echo '</colgroup>';
        }

        echo '<colgroup class="category">';
        for ($i = 1; $i <= $row['grids']; $i++)
            echo '<col>';
        echo '</colgroup>';

        if ($mv_count > 0) {
            echo '<colgroup>';
            echo '  <col class="space">';
            echo '</colgroup>';

            echo '<colgroup class="missing">';
            for ($i = 1; $i <= $mv_count; $i++)
                echo '<col>';
            echo '</colgroup>';
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


        // Header tabele
        echo '<thead>';
        $this->displayMulticheckboxHeader($spremenljivka, $row, $srv_grids, $mv_count, $cssAlign, $middle_row=false, $hideLabels);
        echo '</thead>';


        // Body tabele
        echo '<tbody>';

        $bg++;

        $orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

        $srv_data_cache = array();

        $sql2 = sisplet_query("SELECT * FROM srv_data_checkgrid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id ORDER BY grd_id");
        if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);
        while ($row2 = mysqli_fetch_assoc($sql2)) {
            $srv_data_cache[$row2['vre_id']][$row2['grd_id']] = $row2;
        }

        $sql1 = sisplet_query("SELECT id, naslov, hidden, other FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
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
                        $grid_id = $srv_data_cache[$row1['id']][$i]['grd_id'];
                        if ($srv_grids[$i]['id'] == $grid_id) {
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
                }
                echo '          </td>' . "\n";
                echo '          <td></td>' . "\n";
            }

            if (count($srv_grids) > 0) {
                $first_missing_value = true;
                foreach ($srv_grids AS $i => $srv_grid) {

                    $grid_id = $srv_data_cache[$row1['id']][$i]['grd_id'];

                    $value = $srv_grid['id'];
                    $vsebina = '';

                    if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                        # dodamo spejs pred manjkajočimi vrednostmi
                        echo '<td></td>';
                        $first_missing_value = false;
                    }

                    # izpišemo labelo grida
                    if ($srv_grid['other'] != 0) {
                        echo '<td class="missing ' . $cssAlign . '">';
                        # imamo missing polje
                        echo '<label for="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '">';
                        echo '<input type="checkbox" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation="0" onclick="checkChecked(this); checkTableMissing(this); checkBranching(); setCheckedClass(this, 16, ' . $row1['id'] . ');">';

                        // Font awesome checkbox
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? 'style="font-size:' . Helper::getCustomCheckbox() .'px;"' : '').
                            '></span>';

                        echo '</label>';
                        echo '</td>' . "\n";
                    } else {# ni missing vrednost
                        echo '<td class="category ' . $cssAlign . '">';
                        echo '<label for="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '">';
                        echo '<input type="checkbox" name="vrednost_' . $row1['id'] . '_grid_' . $value . '" id="' . (($srv_grid['other'] != 0) ? 'grid_missing_value_' : 'vrednost_') . $row1['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="1" onclick="checkChecked(this); checkBranching(); setCheckedClass(this, 16, ' . $row1['id'] . ');">';

                        // Font awesome checkbox
                        echo '<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') . '"' .
                            ((Helper::getCustomCheckbox() != 0) ? 'style="font-size:' . Helper::getCustomCheckbox() .'px;"' : '').
                            '></span>';

                        echo '</label>';
                        echo '</td>' . "\n";
                    }

                }
            }

            echo '        </tr>' . "\n";

            $bg++;
        }
        echo '</tbody>';

        echo '      </table>' . "\n";

        // JS za ponvaljanje naslovne vrstice
        $grid_repeat_header = ($spremenljivkaParams->get('grid_repeat_header') ? $spremenljivkaParams->get('grid_repeat_header') : 0);        
        if($grid_repeat_header > 0){
            echo '<script> gridRepeatHeader(\''.$grid_repeat_header.'\', \''.$row['id'].'\'); </script>';	
        }
    }


    /**
     * @desc prikaze vnosno polje za multiple (kombinirano) tabelo, ki je sestavljena iz večih tabel
     * Stara funkcija $this->displayMultigridMultiple($spremenljivka);
     */
    public function multiple($spremenljivka)
    {
        global $admin_type;
        global $lang;

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        // Pri vpogledu moramo skriti name atribut pri loopih, da se izpise prava vrednost
        $hideName = ((get('loop_id') != null) && ($_GET['m'] == 'quick_edit')) ? true : false;

        $row = Model::select_from_srv_spremenljivka($spremenljivka);


        $sql1 = sisplet_query("SELECT spr_id FROM srv_grid_multiple WHERE parent='$spremenljivka' ORDER BY vrstni_red");
        while ($row1 = mysqli_fetch_array($sql1)) {
            $multiple[] = $row1['spr_id'];
        }
        if (count($multiple) == 0) 
            return;

        $spremenljivkaParams = new enkaParameters($row['params']);
        // $gridWidth = (($spremenljivkaParams->get('gridWidth') > 0) ? $spremenljivkaParams->get('gridWidth') : 30);

        // tole prej je klicalo  funkcijo 2x. Ker imam še opcijo -2, bom naredil z enim klicem in več kode tu.
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

        // izracuni za sirino celic
        $size = $row['grids'];

        # polovimo vrednosti gridov, prevedemo naslove in hkrati preverimo ali imamo missinge
        $srv_grids = array();
        $mv_count = 0; # koliko je stolpcev z manjkajočimi vrednostmi
        
        # če polje other != 0 je grid kot missing
        $sql_grid = sisplet_query("SELECT g.*, s.tip, s.enota, s.dostop, s.params FROM srv_grid g, srv_grid_multiple m, srv_spremenljivka s WHERE s.id=g.spr_id AND g.spr_id=m.spr_id AND m.spr_id IN (" . implode(',', $multiple) . ") ORDER BY m.vrstni_red, g.vrstni_red");
        if (!$sql_grid) echo mysqli_error($GLOBALS['connect_db']);

        while ($row_grid = mysqli_fetch_assoc($sql_grid)) {

            # priredimo naslov če prevajamo anketo
            $naslov = Language::srv_language_grid($row_grid['spr_id'], $row_grid['id']);
            if ($naslov != '') {
                $row_grid['naslov'] = $naslov;
            }

            // Sirina posamezne podtabele v % (lahko jo posebej nastavimo)
            $subtabelaParams = new enkaParameters($row_grid['params']);
            $gridmultiple_width = (($subtabelaParams->get('gridmultiple_width') > 0) ? $subtabelaParams->get('gridmultiple_width') : 0);
            if($gridmultiple_width > 0){

                $sql_grid_size = sisplet_query("SELECT COUNT(*) as cnt_grids FROM srv_grid WHERE spr_id='".$row_grid['spr_id']."'");
                $row_grid_size = mysqli_fetch_assoc($sql_grid_size);

                if((int)$row_grid_size['cnt_grids'] > 1){
                    $row_grid['gridmultiple_width'] = floor($gridmultiple_width / $row_grid_size['cnt_grids']);
                }
                else{
                    $row_grid['gridmultiple_width'] = $gridmultiple_width;
                }
            }
            
            if ($row_grid['tip'] == 6 && ($row_grid['enota'] == 2 || $row_grid['enota'] == 6)) {
                $srv_grids[$row_grid['spr_id'] . '-0'] = $row_grid;
            } 
            elseif ($row_grid['tip'] == 16 && $row_grid['enota'] == 6) {
                $srv_grids[$row_grid['spr_id'] . '-0'] = $row_grid;
            } 
            else {
                $srv_grids[$row_grid['spr_id'] . '-' . $row_grid['id']] = $row_grid;

                # če je označena kot manjkajoča jo prištejemo k manjkajočim
                if ($row_grid['other'] != 0) {
                    $mv_count++;
                }
            }
        }

        $size = count($srv_grids);


        # če imamo nastavljno sirino prvega grida ostalih ne nastavljamo
        if ($gridWidth == 30) {
            $cellsize = round(80 / $size);
        } 
        else {
            $cellsize = 'auto';
        }

        $spacesize = round(80 / $size / 4);

        $bg = 1;

        echo '<table class="grid_table multigrid">' . "\n";
        
        if ($hideLabels == false) {
            echo '<colgroup class="question">';
            echo '<col class="width_' . $gridWidth . '">';
            echo '</colgroup>';
            echo '<colgroup>';
            echo '<col class="space">';
            echo '</colgroup>';
        }

        echo '<colgroup class="category">';
        /*for ($i = 1; $i <= $size; $i++)
            echo '<col>';*/
        foreach ($srv_grids as $grid){
            if($grid['gridmultiple_width'] > 0)
                echo '<col style="width:'.$grid['gridmultiple_width'].'%;">';
            else
                echo '<col>';
        }
        echo '</colgroup>';

        echo '<thead>';

        // podnaslovi gridov
        if ($row['grid_subtitle1'] == '1') {
            echo '        <tr>';
            if ($hideLabels == false) {
                echo '          <td></td>';
                //echo '          <td style="width:' . $spacesize . '%"></td>';
                echo '          <td></td>';
            }
            $sql2 = sisplet_query("SELECT s.id, s.naslov, s.tip, s.dostop, s.enota, s.grids FROM srv_spremenljivka s, srv_grid_multiple m WHERE s.id = m.spr_id AND parent = '" . $row['id'] . "' ORDER BY m.vrstni_red");
            while ($row2 = mysqli_fetch_array($sql2)) {

                // Datapiping
                $row2['naslov'] = Helper::dataPiping($row2['naslov']);

                if (($admin_type <= $row2['dostop'] && $admin_type >= 0) || ($admin_type == -1 && $row2['dostop'] == 4) || get('forceShowSpremenljivka')){
					if (get('lang_id') != null) {					
						$rowl = \App\Controllers\LanguageController::srv_language_spremenljivka($row2['id']);					
						if (strip_tags($rowl['naslov']) != '') $row2['naslov'] = $rowl['naslov'];
					}					
					
					echo '          <td colspan="' . ($row2['tip'] == 6 && ($row2['enota'] == 2 || $row2['enota'] == 6) ? '1' : $row2['grids']) . '" class="grid_header" grd="g_' . $row2['id'] . '">' . $row2['naslov'] . '</td>';
				}
			}

            echo '        </tr>';
        }

        echo '	<tr>' . "\n";
        if ($hideLabels == false) {
            echo '		<td></td>' . "\n";
            echo '		<td></td>' . "\n";
        }

        # Izpišemo TOP vrstico z labelami
        if (count($srv_grids) > 0) {
            $first_missing_value = true;
            $jj = 0;
            foreach ($srv_grids AS $i => $srv_grid) {
                $jj++;
                if (($admin_type <= $srv_grid['dostop'] && $admin_type >= 0) || ($admin_type == -1 && $srv_grid['dostop'] == 4) || get('forceShowSpremenljivka')) {

                    // Datapiping
                    $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);

                    if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                        # dodamo spejs pred manjkajočimi vrednostmi
                        echo '		<td></td>' . "\n";
                        $first_missing_value = false;
                    }

                    # izpišemo labelo grida
                    if ($srv_grid['tip'] == 6 && ($srv_grid['enota'] == 2 || $srv_grid['enota'] == 6)) {
                        echo '<td></td>';
                    } 
                    elseif ($srv_grid['tip'] == 16 && $srv_grid['enota'] == 6) {
                        echo '<td></td>';
                    } 
                    else {
                        echo '<td class="' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . ' ' . ($srv_grid['vrstni_red'] == 1 && $jj > 1 ? 'double' : '') . ' ' . $cssAlign . '">' . $srv_grid['naslov'] . '</td>' . "\n";
                    }
                }
            }
        }
        if ($row['enota'] > 0) echo '<td></td><td></td>';    // differencial
        echo '	</tr>' . "\n";
        echo '</thead>';

        echo '<tbody>';

        $bg++;

        $orderby = Model::generate_order_by_field($multiple[0], get('usr_id'));

        # cache tabele srv_data_grid, da se ne dela vsakic posebej nov query (preberemo enkrat vse odgovore userja)
        $srv_data_grid = array();
        $sql_grid = sisplet_query("SELECT * FROM srv_data_grid" . get('db_table') . " WHERE spr_id IN (" . implode(',', $multiple) . ") AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        while ($row_grid = mysqli_fetch_array($sql_grid)) {
            $srv_data_grid[$row_grid['spr_id']][$row_grid['vre_id']] = $row_grid;
        }

        $srv_data_checkgrid = array();
        $sql2 = sisplet_query("SELECT * FROM srv_data_checkgrid" . get('db_table') . " WHERE spr_id IN (" . implode(',', $multiple) . ") AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id ORDER BY grd_id");
        if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);
        while ($row2 = mysqli_fetch_assoc($sql2)) {
            $srv_data_checkgrid[$row2['spr_id']][$row2['vre_id']][$row2['grd_id']] = $row2;
        }

        $srv_data_cache = array();
        $sql2 = sisplet_query("SELECT * FROM srv_data_textgrid" . get('db_table') . " WHERE spr_id IN (" . implode(',', $multiple) . ") AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id ORDER BY grd_id");
        while ($row2 = mysqli_fetch_assoc($sql2)) {
            $srv_data_cache[$row2['spr_id']][$row2['vre_id']][$row2['grd_id']] = $row2;
        }

        # loop skozi srv_vrednost, da izpišemo vrstice z vrednostmi
        $sql1 = sisplet_query("SELECT id, naslov, naslov2, vrstni_red, hidden, other FROM srv_vrednost WHERE spr_id='" . $multiple[0] . "' ORDER BY FIELD(vrstni_red, $orderby)");
        $sql_if_variabla = sisplet_query("SELECT id, other FROM srv_vrednost WHERE spr_id='" . $row['id'] . "' ORDER BY FIELD(vrstni_red, $orderby)");
        while ($row1 = mysqli_fetch_array($sql1)) {

            $row_if_variabla = mysqli_fetch_array($sql_if_variabla);

            # po potrebi prevedemo naslov
            $naslov = Language::getInstance()->srv_language_vrednost($row_if_variabla['id']);
            if ($naslov != '') {
                $row1['naslov'] = $naslov;
            }

            // Datapiping
            $row1['naslov'] = Helper::dataPiping($row1['naslov']);

            $is_missing = false;

            echo '	<tr id="vrednost_if_' . $row_if_variabla['id'] . '" ' . (($row1['hidden'] == 1) ? 'style="display:none"' : '') . (($row1['hidden'] == 2) ? 'class="answer-disabled"' : '') . '>' . "\n";

            //echo '	<tr id="vrednost_if_'.$row1['id'].'">'."\n";
            
            if ($hideLabels == false) {
                echo '		<td class="question">';
                echo $row1['naslov'];
                if ($row_if_variabla['other'] == 1) {
                    $sql3 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='" . $multiple[0] . "' AND vre_id='$row1[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
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
                echo '		<td></td>' . "\n";
            }
            if (count($srv_grids) > 0) {
                $first_missing_value = true;

                $jj = 0;

                foreach ($srv_grids AS $i => $srv_grid) {
                    $jj++;

                    if (($admin_type <= $srv_grid['dostop'] && $admin_type >= 0) || ($admin_type == -1 && $srv_grid['dostop'] == 4) || get('forceShowSpremenljivka')) {

                        $sql2 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='" . $srv_grid['spr_id'] . "' AND vrstni_red = '" . $row1['vrstni_red'] . "'");
                        $row2 = mysqli_fetch_array($sql2);

                        $row_spr = Model::select_from_srv_spremenljivka($srv_grid['spr_id']);
                        $spr_Params = new enkaParameters($row_spr['params']);

                        $value = $srv_grids[$i]['id'];

                        // multigrid
                        if ($row_spr['tip'] == 6) {

                            // dropdown in selectbox
                            if (($row_spr['enota'] == 2) || ($row_spr['enota'] == 6)) {
                                $sbSize = ($spr_Params->get('sbSize') ? $spr_Params->get('sbSize') : 3);
                                $prvaVrstica = ($spr_Params->get('prvaVrstica') ? $spr_Params->get('prvaVrstica') : 1);
                                $prvaVrstica_roleta = ($spr_Params->get('prvaVrstica_roleta') ? $spr_Params->get('prvaVrstica_roleta') : 1);
                                $grid_id = $srv_data_grid[$row_spr['id']][$row2['id']]['grd_id'];

                                echo '<td class="category ' . ($srv_grid['vrstni_red'] == 1 && $jj > 1 ? 'double' : '') . ' ' . $cssAlign . '">';
                                if ($row_spr['enota'] == 2) {//roleta
                                    echo '<select id="multi_' . $row_spr['id'] . '_' . $row2['id'] . '_grid_' . $value . '" onchange="checkBranching();" size="1" name="multi_' . $row_spr['id'] . '_' . $row2['id'] . '">';
                                    //echo '<option value=""></option>';
                                    switch ($prvaVrstica_roleta) {
                                        case "1":
                                            echo '        <option value=""></option>' . "\n";
                                            break;
                                        case "2":

                                            break;
                                        case "3":
                                            echo '        <option value="">' . $lang['srv_dropdown_select'] . '...</option>' . "\n";
                                            break;
                                    }
                                } elseif ($row_spr['enota'] == 6) {//selectbox
                                    if ($prvaVrstica != 1) {
                                        $sbSize = $sbSize + 1;
                                    }
                                    echo '<select id="multi_' . $row_spr['id'] . '_' . $row2['id'] . '_grid_' . $value . '" onclick="checkBranching(); clickSelectBoxMultiCombo(' . $row_spr['id'] . ',' . $row2['id'] . ', ' . $value . ');" size="' . $sbSize . '" name="multi_' . $row_spr['id'] . '_' . $row2['id'] . '">';

                                    switch ($prvaVrstica) {
                                        case "1":

                                            break;
                                        case "2":
                                            echo '<option value=""></option>';
                                            break;
                                        case "3":
                                            echo '<option value="">' . $lang['srv_dropdown_select'] . '...</option>';
                                            break;
                                    }


                                    //echo '<option value=""></option>';
                                }


                                $sql_grid = sisplet_query("SELECT id, naslov, variable FROM srv_grid WHERE spr_id='$row_spr[id]' ORDER BY vrstni_red");
                                while ($row_grid = mysqli_fetch_array($sql_grid)) {
                                    echo '<option id="vrednost_' . $row_spr['id'] . '_grid_' . $row_grid['id'] . '" value="' . $row_grid['id'] . '" onclick="checkBranching();" data-calculation="' . $row_grid['variable'] . '" name="vrednost_' . $row_spr['id'] . '" ' . ($row_grid['id'] == $grid_id ? ' selected' : '') . '>' . $row_grid['naslov'] . '</option>';
                                }
                                echo '</select>';
                                echo '</td>';
                            } else {

                                $grid_id = $srv_data_grid[$row_spr['id']][$row2['id']]['grd_id'];

                                echo '<td class="category ' . ($srv_grid['vrstni_red'] == 1 && $jj > 1 ? 'double' : '') . ' ' . $cssAlign . '">';
                                # ni missing vrednost
                                echo '<label for="multi_' . $row_spr['id'] . '_' . $row2['id'] . '_grid_' . $value . '">';
                                echo '<input type="radio" ' . (!$hideName ? ' name="multi_' . $row_spr['id'] . '_' . $row2['id'] . '"' : '') . ' id="multi_' . $row_spr['id'] . '_' . $row2['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="' . $srv_grids[$i]['variable'] . '" onclick="checkChecked(this); checkBranching();">';
                                
								// Font awesome
								echo '<span class="enka-checkbox-radio "' .((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
									'></span>';								
								
								echo '</label>';
                                echo '</td>' . "\n";

                            }

                            // multi
                        } elseif ($row_spr['tip'] == 16) {

                            //multi selectbox
                            if ($row_spr['enota'] == 6) {
                                $spremenljivkaParams = new enkaParameters($row_spr['params']);
                                $sbSize = ($spremenljivkaParams->get('sbSize') ? $spremenljivkaParams->get('sbSize') : 3);
                                $prvaVrstica = ($spremenljivkaParams->get('prvaVrstica') ? $spremenljivkaParams->get('prvaVrstica') : 1);
                                if ($prvaVrstica != 1) {
                                    $sbSize = $sbSize + 1;
                                }
                                $grid_id = $srv_data_grid[$row_spr['id']][$row2['id']]['grd_id'];

                                echo '<td class="category ' . ($srv_grid['vrstni_red'] == 1 && $jj > 1 ? 'double' : '') . ' ' . $cssAlign . '">';

                                //echo '<select multiple="" id="multi_'.$row_spr['id'].'_'.$row2['id'].'_grid_'.$value.'" onchange="checkBranching();" size="'.$sbSize.'" name="multi_'.$row_spr['id'].'_'.$row2['id'].'">';
                                echo '<select multiple="" id="multi_' . $row_spr['id'] . '_' . $row2['id'] . '_grid_' . $value . '" onclick="checkBranching(); clickSelectBoxMultiCombo(' . $row_spr['id'] . ',' . $row2['id'] . ', ' . $value . ');" size="' . $sbSize . '" name="multi_' . $row_spr['id'] . '_' . $row2['id'] . '[]">';

                                switch ($prvaVrstica) {
                                    case "1":

                                        break;
                                    case "2":
                                        echo '<option value=""></option>';
                                        break;
                                    case "3":
                                        echo '<option value="">' . $lang['srv_dropdown_select'] . '...</option>';
                                        break;
                                }


                                //echo '<option value=""></option>';

                                $sql_grid = sisplet_query("SELECT id, naslov, variable FROM srv_grid WHERE spr_id='$row_spr[id]' ORDER BY vrstni_red");
                                while ($row_grid = mysqli_fetch_array($sql_grid)) {
                                    echo '<option id="vrednost_' . $row_spr['id'] . '_grid_' . $row_grid['id'] . '" value="' . $row_grid['id'] . '" onclick="checkBranching();" data-calculation="' . $row_grid['variable'] . '" name="vrednost_' . $row_spr['id'] . '" ' . ($row_grid['id'] == $grid_id ? ' selected' : '') . '>' . $row_grid['naslov'] . '</option>';
                                    //echo '<option id="vrednost_'.$row_spr['id'].'_grid_'.$row_grid['id'].'" value="'.$row_grid['id'].'" onclick="checkBranching();" data-calculation="'.$row_grid['variable'].'" name="vrednost_'.$row_spr['id'].'[]" '.($row_grid['id']==$grid_id?' selected':'').'>'.$row_grid['naslov'].'</option>';
                                }
                                echo '</select>';
                                echo '</td>';
                            }//multi checkbox
                            elseif ($row_spr['enota'] != 6) {
                                $grid_id = $srv_data_checkgrid[$row_spr['id']][$row2['id']][$value]['grd_id'];

                                echo '<td class="category ' . ($srv_grid['vrstni_red'] == 1 && $jj > 1 ? 'double' : '') . ' ' . $cssAlign . '">';
                                echo '<label for="multi_' . $row_spr['id'] . '_' . $row2['id'] . '_grid_' . $value . '">';
                                echo '<input type="checkbox" name="multi_' . $row_spr['id'] . '_' . $row2['id'] . '_grid_' . $value . '" id="multi_' . $row_spr['id'] . '_' . $row2['id'] . '_grid_' . $value . '" value="' . $value . '"' . (($grid_id == $value && $grid_id != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation="1" onclick="checkChecked(this); checkBranching();">';
                                
								// Font awesome
								echo '<span class="enka-checkbox-radio "' .((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox() . 'px;"') : '') .
									'></span>';	
								
								echo '</label>';
                                echo '</td>' . "\n";
                            }
                            // multitext
                        } elseif ($row_spr['tip'] == 19) {

                            $taWidth = ($spr_Params->get('taWidth') ? $spr_Params->get('taWidth') : -1);
                            $taHeight = ($spr_Params->get('taHeight') ? $spr_Params->get('taHeight') : 1);
                            
                            //default sirina
                            if ($taWidth == -1) {
                                $taWidth = 80;
                            } 
                            else {
                                $taWidth = $taWidth * 10; // da dobimo % (opcije se od 1 - 9)
                            }

                            $vsebina = $srv_data_cache[$row_spr['id']][$row2['id']][$srv_grid['id']]['text'];

                            // datum
                            if ($spr_Params->get('multigrid-datum') == '1') {

                                echo '<td class="category ' . ($srv_grid['vrstni_red'] == 1 && $jj > 1 ? 'double' : '') . ' datepicker ' . $cssAlign . '">';
                                echo '<input type="text" class="width_' . $taWidth . ' height_' . $taHeight . '" name="multi_' . $row_spr['id'] . '_' . $row2['id'] . '_grid_' . $value . '" id="multi_' . $row_spr['id'] . '_' . $row2['id'] . '_grid_' . $value . '" data-calculation="' . $srv_grid['variable'] . '" ' . ($is_missing ? ' disabled' : '') . ' onkeyup="checkBranching();" value="' . ($is_missing ? '' : $vsebina) . '" readonly="true">' . '';

                                $date_element = "#multi_" . $row_spr['id'] . "_" . $row2['id'] . "_grid_" . $value;
                                ?>
                                <script>
                                    datepicker("<?=$date_element?>", <?=($_GET['a'] != 'preview_spremenljivka' ? 'true' : 'false')?>);
                                    <?php
                                    # dodamo date range
                                    echo Helper::getDatepickerRange($row_spr['id'], $date_element);

                                    echo '$( "' . $date_element . '" ).datepicker( "option", "closeText", \'' . $lang['srv_clear'] . '\');';
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

                                    ?>
                                </script>
                                <?

                                echo '</td>' . "\n";     
                            } 
                            // navaden text
                            else {
                                echo '<td class="category ' . ($srv_grid['vrstni_red'] == 1 && $jj > 1 ? 'double' : '') . ' ' . $cssAlign . '">';
                                echo '<textarea class="width_' . $taWidth . ' height_' . $taHeight . '" rows="' . $taHeight . '" name="multi_' . $row_spr['id'] . '_' . $row2['id'] . '_grid_' . $value . '" id="multi_' . $row_spr['id'] . '_' . $row2['id'] . '_grid_' . $value . '" data-calculation="' . $srv_grid['variable'] . '" ' . ($is_missing ? ' disabled' : '') . ' onkeyup="checkBranching();">' . ($is_missing ? '' : $vsebina) . '</textarea>';
                                echo '</td>' . "\n";
                            }

                            // multinumber
                        } elseif ($row_spr['tip'] == 20) {

                            $taWidth = ($spr_Params->get('taWidth') ? $spr_Params->get('taWidth') : -1);
                            $taHeight = ($spr_Params->get('taHeight') ? $spr_Params->get('taHeight') : 1);
                            
                            //default sirina
                            if ($taWidth == -1) {
                                $taWidth = 80;
                            } 
                            else {
                                $taWidth = $taWidth * 10; // da dobimo % (opcije se od 1 - 9)
                            }

                            $vsebina = $srv_data_cache[$row_spr['id']][$row2['id']][$srv_grid['id']]['text'];


                            echo '<td class="category ' . ($srv_grid['vrstni_red'] == 1 && $jj > 1 ? 'double' : '') . ' ' . $cssAlign . '">';
                            //echo '<textarea class="width_'.$taWidth.' height_'.$taHeight.'" rows="'.$taHeight.'" name="multi_'.$row_spr['id'].'_'.$row2['id'].'_grid_'.$value.'" id="multi_'.$row_spr['id'].'_'.$row2['id'].'_grid_'.$value.'" data-calculation="'.$srv_grid['variable'].'" '.($is_missing ? ' disabled' : '').' onkeyup="checkBranching();">'.($is_missing ? '' : $vsebina).'</textarea>';
                            echo '<input type="text" class="width_' . $taWidth . '" name="multi_' . $row_spr['id'] . '_' . $row2['id'] . '_grid_' . $value . '" id="multi_' . $row_spr['id'] . '_' . $row2['id'] . '_grid_' . $value . '" value="' . ($is_missing ? '' : $vsebina) . '" data-calculation="' . $srv_grid['variable'] . '" ' . ($is_missing ? ' disabled' : '') . ' onkeypress="checkNumber(this, ' . $row_spr['cela'] . ', ' . $row_spr['decimalna'] . ');" onkeyup="checkNumber(this, ' . $row_spr['cela'] . ', ' . $row_spr['decimalna'] . '); checkBranching();">';

                            echo '</td>' . "\n";

                        }
                    }
                }
            }
            # dodamo še enoto
            if ($row['enota'] == 1) {
                echo '		<td></td>' . "\n";
                echo '		<td class="differential">' . $row1['naslov2'] . '</td>' . "\n";
            }
            echo '	</tr>' . "\n";

            $bg++;
        }

        echo '</tbody>';

        echo '</table>' . "\n";
    }


    // Izris naslovne vrstice tabele za radio tabelo
    private function displayMultigridHeader($spremenljivka, $row, $srv_grids, $mv_count, $cssAlign, $diferencial_trak, $trak_nadnaslov, $display_trak_num_of_titles, $middle_row=false, $hideLabels=false){
        global $lang;        

		// Vrstica z nadnaslovi
		echo '<tr '.$display_trak_num_of_titles.' class="display_trak_num_of_titles_respondent_'.$row['id'].'">';
                
		if ($hideLabels == false) {
                    echo '          <td></td>';
                    echo '          <td></td>';
                }
                
		for ($j = 1; $j <= $row['grids']; $j++) {

			if($j == 1){
				$nadnaslov_floating = 'left';
            }
            else if($j == $row['grids']){
				$nadnaslov_floating = 'right';
            }
            else{
				$nadnaslov_floating = 'none';
            }
            
			echo '<td class="trak_inline_nadnaslov" grd="gr_'.$j.'"><div id="trak_nadnaslov_'.$j.'_'.$spremenljivka.'" name="trak_nadnaslov_'.$j.'" class="trak_inline_nadnaslov" style="float:'.$nadnaslov_floating.'; display:inline" '.(strpos($trak_nadnaslov[$j], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $trak_nadnaslov[$j] . '</div></td>';
        }
        
        // Ce je diferencial
		if ($row['enota'] == 1) {	

            echo '<td></td><td></td>';
            
			if($mv_count > 0 && $diferencial_trak == 1){
				for($z=0; $z<=$mv_count; $z++){
					echo '<td></td>';
				}
			}
		}
		echo '</tr>';	
        // Vrstica z nadnaslovi - konec
        
        
        // Preverimo, ce ponavljamo glavo - potem ji dodamo class in jo z JS ponovimo
        $spremenljivkaParams = new enkaParameters($row['params']);
        $grid_repeat_header = ($spremenljivkaParams->get('grid_repeat_header') ? $spremenljivkaParams->get('grid_repeat_header') : 0);        

        // Zacetek TR
		if (($mv_count > 0 && $diferencial_trak == 1) || $diferencial_trak != 1) {
			echo '	<tr class="table-header '.($middle_row ? 'middle_row"' : ''). ' ' .($grid_repeat_header > 0 ? 'repeat_header' : '').'">' . "\n";
                        if ($hideLabels == false) {
                            echo '          <td></td>';
                            echo '          <td></td>';
                        }
        }

        if (count($srv_grids) > 0) {

            $first_missing_value = true;

            foreach ($srv_grids AS $i => $srv_grid) {

                # dodamo spejs pred manjkajočimi vrednostmi
                if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                    echo '		<td></td>' . "\n";
                    $first_missing_value = false;
                }

                // Datapiping
                $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
                
                // Ce ni traku izpišemo labelo grida
				if($diferencial_trak != 1){	
					echo '		<td class="' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . ' ' . $cssAlign . '">' . $srv_grid['naslov'] . '</td>' . "\n";
                }
                // Ce je trak
                elseif($diferencial_trak == 1 && $mv_count > 0){	
                    
                    // Izpišemo ustrezno labelo grida
					if($srv_grid['other'] == 0){	//ce je labela za kategorijo odgovora, naj bo prazno
						echo '		<td class="' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . ' ' . $cssAlign . '"></td>' . "\n";
                    }
                    // Drugace, ce je labela za missing, izpisi labelo
                    else {	
						echo '		<td class="' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . ' ' . $cssAlign . '">' . $srv_grid['naslov'] . '</td>' . "\n";
					}
				}
			}
        }
        
        // Differencial in ni traku
        if ($row['enota'] > 0 && $diferencial_trak != 1 && !in_array($row['enota'], [11,12]))
            echo '<td></td><td></td>';
        
            
        // Konec TR
		if (($mv_count > 0 && $diferencial_trak == 1) || $diferencial_trak != 1)
			echo '	</tr>' . "\n";
    }

    // Izris naslovne vrstice tabele za checkbox tabelo
    private function displayMulticheckboxHeader($spremenljivka, $row, $srv_grids, $mv_count, $cssAlign, $middle_row=false, $hideLabels){
        global $lang;

        // Preverimo, ce ponavljamo glavo - potem ji dodamo class in jo z JS ponovimo
        $spremenljivkaParams = new enkaParameters($row['params']);
        $grid_repeat_header = ($spremenljivkaParams->get('grid_repeat_header') ? $spremenljivkaParams->get('grid_repeat_header') : 0);        

        // Zacetek TR
        echo '	<tr class="table-header '.($middle_row ? 'middle_row"' : ''). ' ' .($grid_repeat_header > 0 ? 'repeat_header' : '').'">' . "\n";
        if ($hideLabels == false) {
            echo '		<td></td>' . "\n";
            echo '		<td></td>' . "\n";
        }


        if (count($srv_grids) > 0) {

            $first_missing_value = true;

            foreach ($srv_grids AS $g_id => $srv_grid) {

                # dodamo spejs pred manjkajočimi vrednostmi
                if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {                 
                    echo '<td></td>';
                    $first_missing_value = false;
                }

                // Datapiping
                $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);

                # izpišemo labelo grida
                echo '<td class="' . ($srv_grid['other'] == 0 ? 'category' : 'missing') . ' ' . $cssAlign . '">' . $srv_grid['naslov'] . '</td>' . "\n";
            }
        }


        // Konec TR
        echo '        </tr>' . "\n";
    }
}