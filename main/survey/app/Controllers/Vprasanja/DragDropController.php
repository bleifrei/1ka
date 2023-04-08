<?php
/***************************************
 * Description: Prikaže vprašanje Drag and drop (multigrid, multicheckbox, radio, checkbox, select
 *
 * Vprašanje je prisotno:
 * tip 16 -  enota 9 in enota 3
 * tip 6 - enota 9
 * tip 1, 2, 3 - orientation  8
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

// Iz admin/survey
use enkaParameters;
use SurveySetting;
use Common;


// Vprašanja

class DragDropController extends Controller
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

        return new DragDropController();
    }

    /**
     * @desc prikaze vnos za drag-drop
     */
    public function display($spremenljivka)
    {
		global $lang;
        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $spremenljivkaParams = new enkaParameters($row['params']);
        $selected = Model::getOtherValue($spremenljivka);
		
		$checkbox_limit = ($spremenljivkaParams->get('checkbox_limit') ? $spremenljivkaParams->get('checkbox_limit') : 0);

        // Pri WebSM anketi nimamo userja, zato ne izvajamo ajaxa
        $ajax = 'true';
        $usr_id = get('usr_id');
        if (get('anketa') == get('webSMSurvey') && Common::checkModule('websmsurvey') == '1') {
            $ajax = 'false';
            $usr_id = 0;
        }
		
		// Pri vpogledu moramo deaktivirati canvas in tipke (quick_edit & quick_view = 0)
		$quick_view = json_encode(get('quick_view'));

         //n>k	// prestavljanje
        //if ($row['design'] == 0 && get('mobile') == 0){

        $order = Model::generate_order_by_field($spremenljivka, get('usr_id'));
       //$sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id = '$spremenljivka' AND vrstni_red>0 ORDER BY FIELD(vrstni_red, $order)");
        $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$spremenljivka' AND id NOT IN(SELECT vre_id FROM srv_data_rating WHERE spr_id = '$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id) ORDER BY FIELD(vrstni_red, $order)");


        //izracun visine
        $num = mysqli_num_rows($sql1); //stevilo trenutnih kategorij odgovorov v levem bloku
        $size = $num * 37;


        $sqlc = sisplet_query("SELECT * FROM srv_data_rating WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        $count = mysqli_num_rows($sqlc);
 
        echo '<div id="prestavljanje_' . $spremenljivka . '">';
        //echo	'<div id="vrednost_if_'.$row1['id'].'">';
        echo '<table class="ranking_table">';
        echo '<tr>';
        //zaslon razdelimo na dva dela - izris leve strani
        echo '<td>';

        if (get('lang_id') != null) $_lang = '_' . get('lang_id'); else $_lang = '';
        $srv_ranking_avaliable_categories = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_ranking_avaliable_categories' . $_lang);
        if ($srv_ranking_avaliable_categories == '') $srv_ranking_avaliable_categories = self::$lang['srv_ranking_avaliable_categories'];

        echo '<b>' . $srv_ranking_avaliable_categories . ':</b>';

        echo '<div id="half_' . $spremenljivka . '" class="dropzone" style="height:' . $size . 'px">';
        $sql1 = sisplet_query("SELECT id, naslov, other FROM srv_vrednost WHERE spr_id='$spremenljivka' AND id NOT IN(SELECT vre_id FROM srv_data_rating WHERE spr_id = '$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id) ORDER BY FIELD(vrstni_red, $order)");

        echo '<ul>';

        if (get('mobile') == 0 || get('mobile') == 2) {// ce respondent uporablja PC ali tablico
            echo '<li>
							<div id="half_frame_dropping_' . $spremenljivka . '" class="frame_dropping" onHover=""></div>';
        } else if (get('mobile') == 1) {// ce respondent uporablja mobilnik
            echo '<li>
							<div id="half_frame_dropping_' . $spremenljivka . '" class="frame_dropping_mobile" onHover=""></div>';
        }


        while ($row1 = mysqli_fetch_array($sql1)) {

            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '') $row1['naslov'] = $naslov;

            //$this->display_DropBox($row1['naslov'], $row1['id']);
            $value = $row1['naslov'];

            // Datapiping
            $value = Helper::dataPiping($value);

            $vre_id = $row1['id'];
            $length = strlen($value);    //dolzina teksta kategorije odgovora
            $style = '';
            if (get('mobile') == 0 || get('mobile') == 2) {// ce respondent uporablja PC ali tablico
                $class = 'ranking';
            } else if (get('mobile') == 1) {
                $class = 'ranking_mobile';
            }


            $c = '';
            $other = $row1['other'];    //drugo, po navadi missing
			
			//********* potrebno za pravilno prikazovanje predogleda
			
			if(isset($_GET['a'])){				
				if($_GET['a'] == 'preview_spremenljivka'){
					$preview_spremenljivka = 1;
 					echo '
						<script>
							var usr_id = 0;
						</script>					
					';
				}else{
					$preview_spremenljivka = 0;
					echo '
						<script>
							var usr_id = '.$usr_id.';
						</script>					
					';	
				}
			}else{
				$preview_spremenljivka = 0;				
 				echo '
					<script>
						var usr_id = '.$usr_id.';
					</script>					
				';				
			}
			//********* potrebno za pravilno prikazovanje predogleda - konec

            ?>
            <script>
                draggableOnDroppable[<?=$vre_id?>] = false;	//inicializacija spremenljivke, ki belezi, ali je odgovor prisoten v ustreznem kontejnerju
                draggableOver[<?=$spremenljivka?>] = false;

                $(document).ready(function () {
					Draggable(<?=$row['tip']?>, <?=$spremenljivka?>, <?=$vre_id?>, <?=$ajax?>, srv_meta_anketa_id, '<?=self::$site_url?>', usr_id, <?=$other?>, <?=get('mobile')?>, <?=$quick_view?>, <?=$preview_spremenljivka?>); //poklici funkcijo za ureditev draggable in droppable
				});
            </script>
            <?
            //echo	'<div id="vrednost_if_'.$id.'" class="variabla">';
            //echo	'</div>';	//vrednost_if_ID
            //div ki vsebuje vrednost
            //if($length > 30)
            //	if($length > 90){
            //		$niz = substr($value, 0, 90);
            //echo	'<div title="'.strip_tags($value).'" id="'.$class.'_'.$id.'" class="'.$class.'_long '.$c.'">'.$niz.'...</div>'."\n";
            //		echo	'<div title="'.strip_tags($value).'" id="'.$class.'_'.$vre_id.'" class="'.$class.'_long '.$c.'">'.$niz.'...</div>'."\n";
            //	}
            //	else
            //echo	'<div title="'.strip_tags($value).'" id="'.$class.'_'.$id.'" class="'.$class.'_long '.$c.'">'.$value.'</div>'."\n";
            //		echo	'<div title="'.strip_tags($value).'" id="'.$class.'_'.$vre_id.'" class="'.$class.'_long '.$c.'">'.$value.'</div>'."\n";
            //else{
            //echo	'<div id="'.$class.'_'.$id.'" class="'.$class.' '.$c.'">'.$value.'</div>'."\n";
            echo '<div id="spremenljivka_' . $spremenljivka . '_vrednost_' . $vre_id . '" class="' . $class . ' ' . $c . '">' . $value . '</div>' . "\n"; //'#spremenljivka_'+spremenljivka+'_vrednost_'+id
            //}
        }

        echo '</li>' . "\n";
        echo '</ul>';
        echo '</div>';    //half_$spremenljivka
        echo '</td>';

        //srednja celica (stevilo prenesenih in spodaj puscica)
        if (get('mobile') == 0 || get('mobile') == 2) {// ce respondent uporablja PC ali tablico
            echo '<td class="middle">';
        } else if (get('mobile') == 1) {// ce respondent uporablja mobilnik
            echo '<td class="middle_mobile">';
        }
        echo '<b></b>';
        echo '<img src="' . self::$site_url . 'main/survey/skins/Modern/arrow.png" alt="arrow">';
        echo '</td>';

        //izris desne strani
        echo '<td>';

        echo '<b>' . self::$lang['srv_drag_drop_answers'] . ':</b>';

        echo '<div class="dropholder">'; // ker na td ne primer position relative za nastavit position absolute na dropzone


        echo '<div id="half2_' . $spremenljivka . '" class="dropzone">';
        $sql2 = sisplet_query("SELECT vre_id FROM srv_data_rating WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id ORDER BY vrstni_red ASC");

        while ($row2 = mysqli_fetch_array($sql2)) {
            $sql1 = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE id='$row2[vre_id]' ");
            $row1 = mysqli_fetch_array($sql1);

            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '') $row1['naslov'] = $naslov;

            // Datapiping
            $row1['naslov'] = Helper::dataPiping($row1['naslov']);

            $this->display_DropBox($row1['naslov'], $row1['id']);
        }

        //echo	'<div id="vrednost_if_'.$id.'" class="variabla">';
        //echo	'<div id="vrednost_if_" class="variabla">';
        //echo	'</div>';	//vrednost_if_ID

//			echo		'</div>'; //half2_$spremenljivka
        if (get('mobile') == 0 || get('mobile') == 2) {// ce respondent uporablja PC ali tablico
            echo '<ul>';
            echo '<li>
									<div id="half2_frame_dropping_' . $spremenljivka . '" class="frame_dropping" onHover=""></div>
								</li>' . "\n";
            echo '</ul>';
        } else if (get('mobile') == 1) {// ce respondent uporablja mobilnik
            echo '<ul>';
            echo '<li>
									<div id="half2_frame_dropping_' . $spremenljivka . '" class="frame_dropping_mobile" onHover=""></div>
								</li>' . "\n";
            echo '</ul>';
        }

        echo '</div>'; //half2_$spremenljivka
        echo '</div>'; //dropholder

        echo '</td>';


        echo '</tr>';
        echo '</table>';
		
			//Gumb za resetiranje
			echo '<div class="buttonsHeatmap">';			
				//echo '<input id="resetDragDrop_'.$row['id'].'" type="button"  value="Ponastavi">';
				echo '<input id="resetDragDrop_'.$row['id'].'" type="button"  value="'.$lang['srv_drag_and_drop_reset_button'].'">';	//srv_drag_and_drop_reset_button
			echo '</div>';
		
        echo '</div>';
        //}


        ?>
        <script>
            $(document).ready(function () {
                DragDropDelovanje(<?=$row['tip']?>, <?=$spremenljivka?>, '<?=self::$site_url?>', <?=$ajax?>, srv_meta_anketa_id, usr_id, <?=$num?>, <?=get('mobile')?>, <?=$checkbox_limit?>); //poklici funkcijo za ureditev draggable in droppable
            });
        </script>
        <?

        //preveri, ce je ze kaj v bazi. Pomembno, za prikazovanje ze odgovorjenih zadev, ko uporabnik gre na prejsnjo stran ali kaj podobnega
        $sql2_PP = sisplet_query("SELECT spr_id, vre_id FROM srv_data_vrednost" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");

        while ($row2_PP = mysqli_fetch_array($sql2_PP)) {

            $spr_id_b = $row2_PP["spr_id"];
            $vre_id_b = $row2_PP["vre_id"];

            if (!empty($row2_PP)) {    //ce je ze nekaj v bazi

                ?>
                <script>
                    $(document).ready(function () {
                        var other = $('#spremenljivka_<?=$spr_id_b?>_vrednost_<?=$vre_id_b?>').attr('missing');
						if(typeof checkBranching == 'function'){
							checkBranching();
						}
                        
                        //console.log(other);
                        //draggableOnDroppable[<?=$spr_id_b?>] = true;	//odgovor je prisoten
                        if (<?=$row['tip']?> == 1
                        )
                        {	//ce je samo en mozen odgovor
                            //if( (<?=$row['tip']?> == 1) || (<?=$row['tip']?> == 2 && <?=$other?> != 0) ){	//ce je samo en mozen odgovor
                            $('#half2_frame_dropping_<?=$spr_id_b?>')
                                .prepend($('#spremenljivka_<?=$spr_id_b?>_vrednost_<?=$vre_id_b?>')) //prenesi ustrezni odgovor
                            //.droppable( 'disable' );	//onemogoci prenos drugega odgovora
                        }
                        //else if (<?=$row['tip']?> == 2 && other != 0) {
                        else
                        if (other != 0) {
                            $('#half2_frame_dropping_<?=$spr_id_b?>')
                                .prepend($('#spremenljivka_<?=$spr_id_b?>_vrednost_<?=$vre_id_b?>')) //prenesi ustrezni odgovor
                            //.droppable( 'disable' );	//onemogoci prenos drugega odgovora
                            //console.log('Je missing');
                        }
                        else {
                            $('#half2_frame_dropping_<?=$spr_id_b?>')
                                .prepend($('#spremenljivka_<?=$spr_id_b?>_vrednost_<?=$vre_id_b?>')) //prenesi ustrezni odgovor
                            //console.log('Ni missing');
                        }
                    });
                </script>
                <?
            }
        }
    }

    /**
     * @desc prikaze vnos za drag-drop v grid
     */
    public function grid($spremenljivka)
    {
		global $lang;
        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $spremenljivkaParams = new enkaParameters($row['params']);
        $selected = Model::getOtherValue($spremenljivka);
		
		//***********za skatlasto obliko
		$display_drag_and_drop_new_look = ($spremenljivkaParams->get('display_drag_and_drop_new_look') ? $spremenljivkaParams->get('display_drag_and_drop_new_look') : 0); //za checkbox
		//***********za skatlasto obliko - konec

		$quick_view = json_encode(get('quick_view'));
		
        // Pri WebSM anketi nimamo userja, zato ne izvajamo ajaxa
        $ajax = 'true';
        $usr_id = get('usr_id');
        if (get('anketa') == get('webSMSurvey') && Common::checkModule('websmsurvey') == '1') {
            $ajax = 'false';
            $usr_id = 0;
        }

        //n>k	// prestavljanje
        //if ($row['design'] == 0 && get('mobile') == 0){

        $order = Model::generate_order_by_field($spremenljivka, get('usr_id'));
        //$sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id = '$spremenljivka' AND vrstni_red>0 ORDER BY FIELD(vrstni_red, $order)");
        $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$spremenljivka' AND id NOT IN(SELECT vre_id FROM srv_data_rating WHERE spr_id = '$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id) ORDER BY FIELD(vrstni_red, $order)");


        //izracun visine
        $num = mysqli_num_rows($sql1); //stevilo trenutnih kategorij odgovorov v levem bloku
        $size = $num * 37;


        $sqlc = sisplet_query("SELECT * FROM srv_data_rating WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        $count = mysqli_num_rows($sqlc);

        echo '<div id="prestavljanje_' . $spremenljivka . '">';
        //echo	'<div id="vrednost_if_'.$row1['id'].'">';
        echo '<table class="ranking_table">';
        echo '<tr>';
        //zaslon razdelimo na dva dela - izris leve strani
        echo '<td id="left_frame_'.$spremenljivka.'">';

        if (get('lang_id') != null) $_lang = '_' . get('lang_id'); else $_lang = '';
        $srv_ranking_avaliable_categories = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_ranking_avaliable_categories' . $_lang);
        if ($srv_ranking_avaliable_categories == '') $srv_ranking_avaliable_categories = self::$lang['srv_ranking_avaliable_categories'];

        echo '<b>' . $srv_ranking_avaliable_categories . ':</b>';

        echo '<div id="half_' . $spremenljivka . '" class="dropzone" style="height:' . $size . 'px">';
        $sql1 = sisplet_query("SELECT id, naslov, other FROM srv_vrednost WHERE spr_id='$spremenljivka' AND id NOT IN(SELECT vre_id FROM srv_data_rating WHERE spr_id = '$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id) ORDER BY FIELD(vrstni_red, $order)");

        if (get('mobile') == 0 || get('mobile') == 2) {// ce respondent uporablja PC ali tablico
            echo '<ul>';
            echo '<li>
							<div id="half_frame_dropping_' . $spremenljivka . '" class="frame_dropping" onHover=""></div>';
        } else if (get('mobile') == 1) {// ce respondent uporablja mobilnik
            echo '<ul>';
            echo '<li>
							<div id="half_frame_dropping_' . $spremenljivka . '" class="frame_dropping_mobile" onHover=""></div>';

        }


        while ($row1 = mysqli_fetch_array($sql1)) {

            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '') $row1['naslov'] = $naslov;

            //$this->display_DropBox($row1['naslov'], $row1['id']);
            $value = $row1['naslov'];

            // Datapiping
            $value = Helper::dataPiping($value);

            $vre_id = $row1['id'];
            $length = strlen($value);    //dolzina teksta kategorije odgovora
            $style = '';
            if (get('mobile') == 0 || get('mobile') == 2) {// ce respondent uporablja PC ali tablico
				$class = 'ranking';				
            } 
            else if (get('mobile') == 1) {
                $class = 'ranking_mobile';
            }
            $c = '';
            $other = $row1['other'];    //drugo, po navadi missing
			
			//********* potrebno za pravilno prikazovanje predogleda
			if(isset($_GET['a'])){
				if($_GET['a'] == 'preview_spremenljivka'){
					$preview_spremenljivka = 1;
					echo '
						<script>
							var usr_id = 0;
						</script>					
					';
				}else{
					$preview_spremenljivka = 0;
					echo '
						<script>
							var usr_id = '.$usr_id.';
						</script>					
					';	
				}
			}else{
				$preview_spremenljivka = 0;
				echo '
					<script>
						var usr_id = '.$usr_id.';
					</script>					
				';				
			}
			//********* potrebno za pravilno prikazovanje predogleda - konec


            ?>
            <script>
                //spremenljvke, ce se ne uporablja polj
                //draggableOnDroppable[<?=$vre_id?>] = false;	//inicializacija spremenljivke, ki belezi, ali je trenutna kategorija odgovora prisotna kontejnerju/okvirju

				skatlastOkvir[<?=$spremenljivka?>] = <?=$display_drag_and_drop_new_look?>; //belezi, ali je okvir skatlaste oblike
                //draggableOverDroppable[<?=$vre_id?>] = false;
                data_after_refresh[<?=$spremenljivka?>] = false;
                frame_total_height_right[<?=$spremenljivka?>] = 0;
                last_vre_id[<?=$spremenljivka?>] = 0;
                vre_id_global[<?=$spremenljivka?>] = 0;
                last_indeks[<?=$spremenljivka?>] = 0;
                last_drop[<?=$vre_id?>] = 0;
                //num_grids_global[<?=$spremenljivka?>] = 0;
                indeks_global[<?=$spremenljivka?>] = 0;
                num_grids_global[<?=$spremenljivka?>] = <?=$row['grids']?>;//stevilo okvirjev, pomembno za revert kategorije odgovora
                cat_pushed[<?=$spremenljivka?>] = false;
                draggable_global[<?=$vre_id?>] = 0;
				var from_left = [];
				from_left[<?=$vre_id?>] = true;
				//console.log("from_left[<?=$vre_id?>]: "+from_left[<?=$vre_id?>]);
                //spremenljvke, ce se ne uporablja polj - konec

                //spremenljivke kot polja polj
                draggableOnDroppable[<?=$vre_id?>] = new Array(2);	//inicializacija spremenljivke, ki belezi, ali je trenutna kategorija odgovora prisotna kontejnerju/okvirju
                draggableOverDroppable[<?=$vre_id?>] = new Array(2);
                for (i = 1; i <= num_grids_global[<?=$spremenljivka?>]; i++) {
                    draggableOnDroppable[<?=$vre_id?>][i] = false;
                    draggableOverDroppable[<?=$vre_id?>][i] = false;
                }
                //spremenljivke kot polja polj - konec


                $(document).ready(function () {
					GridDraggable(<?=$row['tip']?>, <?=$spremenljivka?>, <?=$vre_id?>, <?=$ajax?>, srv_meta_anketa_id, '<?=self::$site_url?>', usr_id, <?=$other?>, <?=get('mobile')?>, <?=$display_drag_and_drop_new_look?>, <?=$quick_view?>, <?=$preview_spremenljivka?>); //poklici funkcijo za ureditev draggable in droppable
                });
            </script>
            <?

            echo '<div id="spremenljivka_' . $spremenljivka . '_vrednost_' . $vre_id . '" class="' . $class . ' ' . $c . '">' . $value . '</div>' . "\n"; //'#spremenljivka_'+spremenljivka+'_vrednost_'+id
        }

        echo '</li>' . "\n";
        echo '</ul>';
        echo '</div>';    //half_$spremenljivka
        echo '</td>';

        //srednja celica (stevilo prenesenih in spodaj puscica)
        if (get('mobile') == 0 || get('mobile') == 2) {// ce respondent uporablja PC ali tablico
            echo '<td class="middle">';
        } else if (get('mobile') == 1) {// ce respondent uporablja mobilnik
            echo '<td class="middle_mobile">';
        }
        echo '<b></b>';
        echo '<img src="' . self::$site_url . 'main/survey/skins/Modern/arrow.png" alt="arrow">';
        echo '</td>';

        //izris desne strani***********************************************************************************
		
        echo '<td id="right_frame_'.$spremenljivka.'">';

        echo '<b>' . self::$lang['srv_drag_drop_answers'] . ':</b>';

        echo '<div class="dropholder">'; // ker na td ne primer position relative za nastavit position absolute na dropzone


        echo '<div id="half2_' . $spremenljivka . '" class="dropzone">';
        $sql2 = sisplet_query("SELECT vre_id FROM srv_data_rating WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id ORDER BY vrstni_red ASC");

        while ($row2 = mysqli_fetch_array($sql2)) {
            $sql1 = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE id='$row2[vre_id]' ");
            $row1 = mysqli_fetch_array($sql1);

            $naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
            if ($naslov != '') $row1['naslov'] = $naslov;

            // Datapiping
            $row1['naslov'] = Helper::dataPiping($row1['naslov']);

            $this->display_DropBox($row1['naslov'], $row1['id']);
        }


        # polovimo vrednosti gridov, prevedemo naslove in hkrati preverimo ali imamo missinge
        $srv_grids = array();
        $mv_count = 0; # koliko je stolpcev z manjkajočimi vrednostmi
        # če polje other != 0 je grid kot missing
        $sql_grid = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$spremenljivka' ORDER BY vrstni_red");

        while ($row_grid = mysqli_fetch_assoc($sql_grid)) {
            # priredimo naslov če prevajamo anketo
            $naslov = Language::srv_language_grid($spremenljivka, $row_grid['id']);
            if ($naslov != '') {
                $row_grid['naslov'] = $naslov;
            }
            $srv_grids[$row_grid['id']] = $row_grid;
            # če je označena kot manjkajoča jo prištejemo k manjkajočim
            if ($row_grid['other'] != 0) {
                $mv_count++;
            }

        }

        # Izpišemo TOP vrstico z labelami
        //if (count($srv_grids) > 0) {
        /* 					$first_missing_value = true;
                            foreach ($srv_grids AS $i => $srv_grid) {
                                if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                                    # dodamo spejs pred manjkajočimi vrednostmi
                                    echo '		<td></td>'."\n";
                                    $first_missing_value = false;
                                }
                                # izpišemo labelo grida
                                //echo '		<td class="'.($srv_grid['other']==0?'category':'missing').' '.$cssAlign.'">'.$srv_grid['naslov'].'</td>'."\n";
                                echo $srv_grid['naslov'];
                            } */
        //}


        //glede na število gridov, dodati ustrezno stevilo <li> oz. okvirjev
        echo '<ul>';
        $first_missing_value = true;
        foreach ($srv_grids AS $i => $srv_grid) {
            /* 						if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
                                        # dodamo spejs pred manjkajočimi vrednostmi
                                        echo '		<td></td>'."\n";
                                        $first_missing_value = false;
                                    } */

            // Datapiping
            $srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
            if (get('mobile') == 0 || get('mobile') == 2) {// ce respondent uporablja PC ali tablico
				if($display_drag_and_drop_new_look == 0){
					echo '<li>
												<div class="frame_dropping_titles">' . $srv_grid['naslov'] . '</div>
											</li>' . "\n";    //izpis "naslova" okvirja
					echo '<li>
												<div id="half2_frame_dropping_' . $i . '_' . $spremenljivka . '" class="frame_dropping" onHover=""></div>
											</li>' . "\n";    //izpis okvirja
				}else if($display_drag_and_drop_new_look == 1){
					echo '<li>
												<div id="half2_frame_dropping_' . $i . '_' . $spremenljivka . '" class="frame_dropping_box" onHover=""></div>
											</li>' . "\n";    //izpis okvirja
					echo '<li>
												<div class="frame_dropping_titles_box">' . $srv_grid['naslov'] . '</div>
											</li>' . "\n";    //izpis "naslova" okvirja
				}
			} else if (get('mobile') == 1) {// ce respondent uporablja mobilnik
               
				if($display_drag_and_drop_new_look == 0){
					echo '<li>
												<div class="frame_dropping_titles_mobile">' . $srv_grid['naslov'] . '</div>
											</li>' . "\n";    //izpis "naslova" okvirja
					echo '<li>
												<div id="half2_frame_dropping_' . $i . '_' . $spremenljivka . '" class="frame_dropping_mobile" onHover=""></div>
											</li>' . "\n";    //izpis okvirja
				}else if($display_drag_and_drop_new_look == 1){
					echo '<li>
												<div id="half2_frame_dropping_' . $i . '_' . $spremenljivka . '" class="frame_dropping_box_mobile" onHover=""></div>
											</li>' . "\n";    //izpis okvirja
					echo '<li>
												<div class="frame_dropping_titles_box_mobile">' . $srv_grid['naslov'] . '</div>
											</li>' . "\n";    //izpis "naslova" okvirja
				}
			}
            ?>
            <script>
                $(document).ready(function () {
                    //ce imamo missing, je potrebno povecati stevilo grid-ov oz. okvirjev
                    if (<?=$srv_grid['other']?> !=
                    '0'
                    )
                    {
                        //console.log("Imamo missing!");
                        num_grids_global[<?=$spremenljivka?>] = num_grids_global[<?=$spremenljivka?>] + 1;

                    }
					//if(<?=$display_drag_and_drop_new_look?> == 0){
						GridDragDropDelovanje(num_grids_global[<?=$spremenljivka?>],<?=$i?>, <?=$row['tip']?>, <?=$spremenljivka?>, '<?=self::$site_url?>', <?=$ajax?>, srv_meta_anketa_id, usr_id, <?=$num?>, <?=get('mobile')?>, <?=$display_drag_and_drop_new_look?>); //poklici funkcijo za ureditev draggable in droppable
/* 					}
					else if(<?=$display_drag_and_drop_new_look?> == 1){
						//GridDragDropDelovanjeBox(num_grids_global[<?=$spremenljivka?>],<?=$i?>, <?=$row['tip']?>, <?=$spremenljivka?>, '<?=self::$site_url?>', <?=$ajax?>, srv_meta_anketa_id, <?=$usr_id?>, <?=$num?>, <?=get('mobile')?>); //poklici funkcijo za ureditev draggable in droppable
						GridDragDropDelovanjeBox(num_grids_global[<?=$spremenljivka?>],<?=$i?>, <?=$row['tip']?>, <?=$spremenljivka?>, '<?=self::$site_url?>', <?=$ajax?>, srv_meta_anketa_id, usr_id, <?=$num?>, <?=get('mobile')?>); //poklici funkcijo za ureditev draggable in droppable
					} */
                });
            </script>
            <?
        }
        ?>
        <script>
            $(document).ready(function () {
                //console.log($('#spremenljivka_'+<?=$spremenljivka?>).css('height'));
				var spremenljivka = <?=$spremenljivka?>;
				var ajax = <?=$ajax?>;
				var site_url = '<?=self::$site_url?>';
				var num_grids = num_grids_global[<?=$spremenljivka?>];				
				
				//Ureditev povrnitve odgovorov (iz desne strani) v levo	
				$('#resetDragDrop_'+spremenljivka).click(function(){
					
					var rightFrameHasChildren = ($('#half2_'+spremenljivka).find('.ranking').length ? 'Da' : 'Ne');
					//console.log("Imamo kaj v desnem okvirju? "+rightFrameHasChildren);
					
					if (rightFrameHasChildren == 'Da'){
						
						//pobrisi vse iz baze
						if (ajax){
							$.post(site_url+'/main/survey/ajax.php?a=delete_dragdrop_grid_data_reset', {spremenljivka: spremenljivka, usr_id: usr_id, anketa: srv_meta_anketa_id, tip: <?=$row['tip']?>}); //post-aj potrebne podatke
						}
						
						$('#half2_'+spremenljivka).find('.ranking').each(function(indeks) {	//preleti vse prisotne kategorije odgovorov v desnem okvirju
							var id = $(this).attr('id');
							//console.log(index+1 + ": " + id);
							var indeks = indeks+1;
							var defaultHeight = 30;	//default, povrnjena visina posameznega okvirja
							var parent = $(this).parent().attr('id');
							//console.log("parent:"+parent);
							var index = parent.substring(21,22);	//indeks okvirja v katerem se nahaja trenutna kategorija odgovora
							//console.log("index:"+index);
							//console.log("id:"+id);
							
							$('#'+parent).outerHeight(defaultHeight);	//povrni visino posameznega okvirja
							//var parentHeight = $('#'+parent).outerHeight(true);
							//console.log("parent:"+parent+" with height:"+parentHeight);
							
							dynamic_question_height(spremenljivka, num_grids, <?=get('mobile')?>, <?=$display_drag_and_drop_new_look?>); //povrni visino celotnega vprasanja
							
							ResetButtonHeight(spremenljivka);//povrni gumb na zacetno visino
							if(<?=$display_drag_and_drop_new_look?>){
								$(this).removeClass('drag_and_drop_box_right');
								$(this).removeClass('drag_and_drop_box_right_after_refresh');
								$(this).addClass('drag_and_drop_right');
							}

							
							//var vre_id = $(this).val();
							var vre_id = $(this).attr('value');
							//console.log("vre_id:"+vre_id);
							
							if(<?=$row['tip']?> == 6){
								$('#half_frame_dropping_'+spremenljivka).prepend(this);	//pripopaj preneseno kategorijo na zacetek seznama kategorij na levi strani
								//uredi parametre za nadalnje delovanje odgovarjanja
								draggableOnDroppable[vre_id] = false;	//oznacimo, da smo trenutno kategorijo odgovora odstranili iz okvirja
								draggableOverDroppable[vre_id] = false;								
								last_indeks[spremenljivka] = 0;
								last_drop[vre_id] = 0;
								last_vre_id[spremenljivka] = 0;
								//uredi parametre za nadalnje delovanje odgovarjanja - konec
							}else if(<?=$row['tip']?> == 16){
								$(this).remove();	//odstrani kategorijo odgovora iz okvirja								
								
								//uredi parametre za nadalnje delovanje odgovarjanja								
								draggableOnDroppable[vre_id][index] = false;	//oznacimo, da smo trenutno kategorijo odgovora odstranili iz okvirja
								draggableOverDroppable[vre_id][index] = false;								
								//uredi parametre za nadalnje delovanje odgovarjanja - konec
							}
							from_left[vre_id] = true;
						});
					}
				});	
            });
        </script>
        <?


        echo '</ul>';


        echo '</div>'; //half2_$spremenljivka
        echo '</div>'; //dropholder

        echo '</td>';
        //************************************************ konec izrisa desne strani

        echo '</tr>';
        echo '</table>';
		
			//Gumb za resetiranje
			echo '<div class="buttonsHeatmap">';			
				//echo '<input id="resetDragDrop_'.$row['id'].'" type="button"  value="Ponastavi">';
				echo '<input id="resetDragDrop_'.$row['id'].'" type="button"  value="'.$lang['srv_drag_and_drop_reset_button'].'">';	//srv_drag_and_drop_reset_button
			echo '</div>';
		
		
        echo '</div>';
        //}

        //********* urejanje prikaza ob morebitnem refresh-u strani ali prehod na naslednjo oz. prejsnjo stran
        //preveri, ce je ze kaj v bazi. Pomembno, za prikazovanje ze odgovorjenih zadev, ko uporabnik gre na prejsnjo stran ali kaj podobnega
        if ($row['tip'] == 6) {
            $sql2_PP = sisplet_query("SELECT spr_id, vre_id, grd_id FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        } elseif ($row['tip'] == 16) {
            //$sql2_PP = sisplet_query("SELECT spr_id, vre_id, grd_id FROM srv_data_checkgrid WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
            $sql2_PP = sisplet_query("SELECT spr_id, vre_id, grd_id FROM srv_data_checkgrid_active WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        }


        ?>
        <script>

            var cat_margin_left = 10 + 5 * 2 + 1 * 2; //hrani rob za ureditev visine levega okvirja = margin_spodnji + padding(spredi pa zadi) + border(spredi pa zadi) + neznanka
            //var title_heigth = 26;	//visina okvricka z naslovom
			//var title_heigth = $('#spremenljivka_<?=$spremenljivka?> .naslov').height();
			//console.log("Title height from survey: "+title_heigth);
            var height_beside = 40; //visina od zacetka vprasanja do prvega okvirja (in malo po zadnjem okvirju)
            var final_height_right_block_PP = 0;	//hrani koncno visino desnega bloka, torej vseh prisotnih okvirjev
			var top_cat_right = 30;
			var left_cat = -6;
			
        </script>
        <?
		$i = 0;
        while ($row2_PP = mysqli_fetch_array($sql2_PP)) {

            $spr_id_b = $row2_PP["spr_id"];
            $vre_id_b = $row2_PP["vre_id"];
            $grd_id_b = $row2_PP["grd_id"];

            if (!empty($row2_PP)) {    //ce je ze nekaj v bazi
                ?>
                <script>
                    $(document).ready(function () {
						
                        data_after_refresh[<?=$spremenljivka?>] = true;	//oznacimo, da so prisotni podatki po refresh-u
                        draggableOnDroppable[<?=$vre_id_b?>][<?=$grd_id_b?>] = true;	//oznacimo, da je trenutna kategorija odgovora v okvirju z dolocenim indeksom
                        draggableOverDroppable[<?=$vre_id_b?>][<?=$grd_id_b?>] = true;
                        last_drop[<?=$vre_id_b?>] = <?=$grd_id_b?>;	//pomembno za revert kategorije odgovora in belezenje podatkov v bazo ob refreshu
                        vre_id_global[<?=$spr_id_b?>] = <?=$vre_id_b?>; //pomembno za revert kategorije odgovora ob refreshu
                        //uredi visino trenuntega okvirja glede na visino trenutne kategorije odgovora
                        frame_height(<?=$spr_id_b?>, <?=$vre_id_b?>, <?=$grd_id_b?>, false);
						
						if(typeof checkBranching == 'function'){
							checkBranching();
						}
						
						var desniOkvirRefresh = $('#half2_frame_dropping_<?=$grd_id_b?>_<?=$spr_id_b?>');
						var trenutnaKategorija = $('#spremenljivka_<?=$spr_id_b?>_vrednost_<?=$vre_id_b?>');
                        if (<?=$row['tip']?> == 6)
                        {	//ce je tabela - en odgovor
							if(<?=$display_drag_and_drop_new_look?> == 0){
								desniOkvirRefresh	//v ustrezen okvir z indeksom grd_id
									.prepend($('#spremenljivka_<?=$spr_id_b?>_vrednost_<?=$vre_id_b?>')) //prenesi ustrezni odgovor
							}else if(<?=$display_drag_and_drop_new_look?> == 1){	//ce so skatle
								desniOkvirRefresh	//v ustrezen okvir z indeksom grd_id
									.prepend($('#spremenljivka_<?=$spr_id_b?>_vrednost_<?=$vre_id_b?>').css({left: left_cat})); //prenesi ustrezni odgovor
								trenutnaKategorija.removeClass('drag_and_drop');
								trenutnaKategorija.addClass('drag_and_drop_box_right_after_refresh');
								//var pravaVisina = calcPravaVisina(desniOkvirRefresh, 0);	//visina/pozicija prenesene kategorije v desnem okvirju	
								/* desniOkvirRefresh	//v ustrezen okvir z indeksom grd_id
									.prepend($('#spremenljivka_<?=$spr_id_b?>_vrednost_<?=$vre_id_b?>').css({top: pravaVisina})); //prenesi ustrezni odgovor */
							}
						}
                        else
                        if (<?=$row['tip']?> == 16)
                        {
							if(<?=$display_drag_and_drop_new_look?> == 0){
								desniOkvirRefresh	//v ustrezen okvir z indeksom grd_id
									.prepend($('#spremenljivka_<?=$spr_id_b?>_vrednost_<?=$vre_id_b?>').clone()) //kloniraj ustrezni odgovor
							}else if(<?=$display_drag_and_drop_new_look?> == 1){
								desniOkvirRefresh	//v ustrezen okvir z indeksom grd_id
									.prepend($('#spremenljivka_<?=$spr_id_b?>_vrednost_<?=$vre_id_b?>').clone().css({left:left_cat})) //kloniraj ustrezni odgovor
								
								desniOkvirRefresh.children(trenutnaKategorija).removeClass('drag_and_drop');
								desniOkvirRefresh.children(trenutnaKategorija).addClass('drag_and_drop_box_right_after_refresh');//dodamo slog, ki dokoncno postavi draggable na pravo lokacijo
									
							}
/* 							if(<?=$display_drag_and_drop_new_look?> == 0){
								$('#half2_frame_dropping_<?=$grd_id_b?>_<?=$spr_id_b?>')	//v ustrezen okvir z indeksom grd_id
									.prepend($('#spremenljivka_<?=$spr_id_b?>_vrednost_<?=$vre_id_b?>').clone()) //kloniraj ustrezni odgovor
							}else if(<?=$display_drag_and_drop_new_look?> == 1){
								$('#half2_frame_dropping_<?=$grd_id_b?>_<?=$spr_id_b?>')	//v ustrezen okvir z indeksom grd_id
									.prepend($('#spremenljivka_<?=$spr_id_b?>_vrednost_<?=$vre_id_b?>').clone().css({top: (top_cat_right), left:-6})) //kloniraj ustrezni odgovor
							} */
                        }

/*                         //console.log('Nekaj je v bazi!');
                        data_after_refresh[<?=$spremenljivka?>] = true;	//oznacimo, da so prisotni podatki po refresh-u
                        draggableOnDroppable[<?=$vre_id_b?>][<?=$grd_id_b?>] = true;	//oznacimo, da je trenutna kategorija odgovora v okvirju z dolocenim indeksom
                        draggableOverDroppable[<?=$vre_id_b?>][<?=$grd_id_b?>] = true;
                        last_drop[<?=$vre_id_b?>] = <?=$grd_id_b?>;	//pomembno za revert kategorije odgovora in belezenje podatkov v bazo ob refreshu
                        vre_id_global[<?=$spr_id_b?>] = <?=$vre_id_b?>; //pomembno za revert kategorije odgovora ob refreshu
                        //uredi visino trenuntega okvirja glede na visino trenutne kategorije odgovora
                        frame_height(<?=$spr_id_b?>, <?=$vre_id_b?>, <?=$grd_id_b?>, false);

                        checkBranching(); */
                    });
                </script>
                <?
			}
        }
			
        //if(!empty($row2_PP)){
        if (mysqli_num_rows($sql2_PP) != 0) {
            ?>
            <script>
                $(document).ready(function () {
                    data_after_refresh[<?=mysqli_num_rows($sql2_PP)?>] = <?=mysqli_num_rows($sql2_PP)?>;
					refresh[<?=$spremenljivka?>] = 1;
                    //console.log(<?=$vre_id?>);
                    //console.log(last_drop[<?=$vre_id?>]);
                    //*******************dinamicna visina celotnega vprasanja glede na vsebino prenesenih desnih okvirjev
                    //question_height(<?=$spr_id_b?>, <?=$row['grids']?>);
                    //question_height(<?=$spr_id_b?>, num_grids_global[<?=$spremenljivka?>]);
					//dynamic_question_height(<?=$spr_id_b?>, num_grids_global[<?=$spremenljivka?>]);
                    num_grids_global[<?=$spremenljivka?>] = <?=$row['grids']?>;//stevilo okvirjev, pomembno za revert kategorije odgovora ob refreshu
				});
            </script>
            <?
            //}
        }
        //********* konec - urejanje prikaza ob morebitnem refresh-u strani ali prehod na naslednjo oz. prejsnjo stran
    }

    /**
     * @desc izrisemo drop okno
     */
    public function display_DropBox($value, $id)
    {

        $length = strlen($value);
        $style = '';
        $class = 'ranking';
        $c = '';

        //div ki vsebuje vrednost
        if ($length > 30)
            if ($length > 90) {
                $niz = substr($value, 0, 90);
                echo '<div title="' . strip_tags($value) . '" id="' . $class . '_' . $id . '" class="' . $class . '_long ' . $c . '">' . $niz . '...</div>' . "\n";
            } else
                echo '<div title="' . strip_tags($value) . '" id="' . $class . '_' . $id . '" class="' . $class . '_long ' . $c . '">' . $value . '</div>' . "\n";
        else
            echo '<div id="' . $class . '_' . $id . '" class="' . $class . ' ' . $c . '">' . $value . '</div>' . "\n";
    }

}