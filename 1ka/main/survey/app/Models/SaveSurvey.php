<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 26.02.2016
 *****************************************/

namespace App\Models;


// Osnovni razredi
use App\Controllers\CheckController as Check;
use App\Controllers\Controller;
use SurveyInfo;
use SurveySetting;
use Common;
use enkaParameters;

// Iz admin/survey


// Vprašanja

class SaveSurvey extends Model
{

    public function __construct()
    {
        // Definiramo globalne spremenljivke, ki jih kasneje uporabljamo v funkcijah
        global $admin_type;
        global $lang;

        //self::$admin_type = $admin_type;
        //$this->lang = $lang;

    }

    /************************************************
     * Get instance
     ************************************************/
    protected static $_instance;

    public static function getInstance()
    {
        if (self::$_instance) {
            self::refreshVariables();
            return self::$_instance;
        }

        return new SaveSurvey();
    }

    private function refreshVariables()
    {
        return (new Controller())->getGlobalVariables();
    }

	
    /**
     * obdela poslano stran in jo shrani v bazo
     * za (vecino) podatkovnih querijev se podatki kesirajo v zgornje ^ spremenljivke, in se jih naenkrat shrani v bazo v posted_commit()
     *
     * @param mixed $preskocena_stran v primeru, da stran preskocimo, to zapisemo v bazo v srv_user_grupa
     * @param mixed $spr_id podamo v primeru, da shranjujemo samo 1 spremenljivko (in ne celo stran), uporablja se pri urejanju podatkov
     */
    public function posted($preskocena = 0, $spr_id = 0){
        global $lang;
        global $admin_type;


        // Modul panel - status - shranimo v cookie, ker drugace se vcasih ne ohrani do konca ce so strani preskocene
        if(isset($_POST['panel_status']) && $_POST['panel_status'] != ''){
            setcookie('panel_status', $_POST['panel_status'], 0, '/');
        }
            
        Check::getInstance()->check_captcha();

        # če smo samo v predogledu uporabnika ne shranjujemo
        if (get('quick_view') == true) {
            return false;
        }

        $loop_id = get('loop_id') == null ? "NULL" : "'" . get('loop_id') . "'";

        $rowa = SurveyInfo::getInstance()->getSurveyRow();
        
        // podatke shranimo v vsakem primeru, ker jih v neaktivni ali predogledu potem na koncu zbrisemo!!!

        // updatamo, ce ne gre za preskoceno stran. pri preskoceni strani, pa updatamo samo prvic
        if ($preskocena == 0 || get('preskocena_first') == 1) {

            SurveySetting::getInstance()->Init(get('anketa'));
            $date = SurveySetting::getInstance()->getSurveyMiscSetting('survey_date');

            if ($date == 0) $_time_insert = "NOW()"; else $_time_insert = "''";

            if (isset($_GET['language'])) save('language', (int)$_GET['language']); else save('language', $lang['id']);
            // popravimo cas updata
            $s = sisplet_query("UPDATE srv_user SET time_edit = " . $_time_insert . ", language='" . get('language') . "' WHERE id='" . get('usr_id') . "'");
            if (!$s) {
                echo 'err3433' . mysqli_error($GLOBALS['connect_db']);
                die();
            }
            // po straneh
            sisplet_query("REPLACE INTO srv_user_grupa" . get('db_table') . " (gru_id, usr_id, time_edit, preskocena) VALUES ('" . get('grupa') . "', '" . get('usr_id') . "', " . $_time_insert . ", '$preskocena')");
            if ($preskocena == 1) save('preskocena_first', 0);

            # potrebno bo osvežit seznam anket
            Model::setUpdateSurveyList();
        }

        SurveySetting::getInstance()->Init(get('anketa'));
        $question_resp_comment = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment');


        /**
         * Tele spremenljivke so za vse INSERTe, ki se vnasajo v tabele (z imenom spremenljivke)
         * Namen je ta, da se vnosi kesirajo in naenkrat vnesejo v bazo, kar pohitri celotno zadevo
         * V bazo ne shranjuje v tej funkciji, ampak se potem dodatno poklice se posted_commit()
         */
        $srv_data_grid = '';
        $srv_data_vrednost = '';
        $srv_data_text = '';
        $srv_data_checkgrid = '';
        $srv_data_textgrid = '';
        $srv_data_rating = '';
        $srv_data_vrednost_cond = '';
        $srv_data_map = '';
        $srv_data_heatmap = '';

        if ($preskocena == 1) {
            $srv_data_grid = get('cache_srv_data_grid');
            $srv_data_vrednost = get('cache_srv_data_vrednost');
            $srv_data_text = get('cache_srv_data_text');
            $srv_data_checkgrid = get('cache_srv_data_checkgrid');
            $srv_data_textgrid = get('cache_srv_data_textgrid');
            $srv_data_rating = get('cache_srv_data_rating');
            $srv_data_vrednost_cond = get('cache_srv_data_vrednost_cond');
            $srv_data_map = get('cache_srv_data_map');
            $srv_data_heatmap = get('cache_srv_data_heatmap');
        }

        // shranjevanje pri hitrem editiranju vnosov - preskocimo vprasanja z uploadom ker drugace zgubimo datoteke
        if (isset($_GET['m']) && $_GET['m'] == 'quick_edit'){     
            $sql = sisplet_query("SELECT s.* FROM srv_spremenljivka s, srv_grupa g 
                                    WHERE s.gru_id=g.id AND g.ank_id='" . get('anketa') . "' AND s.visible='1' AND '$admin_type' <= s.dostop 
                                        AND !(s.tip='21' AND (s.upload='1' OR s.upload='2' OR s.signature='1'))
                                    ORDER BY s.vrstni_red ASC");
        }
        // shranjevanje vseh spremenljivk na enkrat pri all pages preview-u
        elseif (get('displayAllPages')) {    
            $sql = sisplet_query("SELECT s.* FROM srv_spremenljivka s, srv_grupa g 
                                    WHERE s.gru_id=g.id AND g.ank_id='" . get('anketa') . "' AND s.visible='1' AND '$admin_type' <= s.dostop 
                                    ORDER BY s.vrstni_red ASC");
        } 
        // normalno shranjevanje
        elseif ($spr_id == 0){     
            $sql = sisplet_query("SELECT * FROM srv_spremenljivka 
                                    WHERE gru_id='" . get('grupa') . "' AND visible='1' AND '$admin_type' <= dostop 
                                    ORDER BY vrstni_red ASC");
        }
        // shranimo samo 1 spremenljivko (pri popravljanju podatkov v bazi)
        elseif ($spr_id > 0){ 
            $sql = sisplet_query("SELECT * FROM srv_spremenljivka WHERE id='$spr_id'");
        }

        // gremo cez vprasanja v trenutni grupi (strani)
        while ($row = mysqli_fetch_array($sql)) {

            // vnesemo komentar vprasanja
            if ($question_resp_comment == 1) {
                $text = $_POST['question_comment_' . $row['id']];
                sisplet_query("DELETE FROM srv_data_text" . get('db_table') . " WHERE spr_id='0' AND vre_id='$row[id]' AND usr_id='" . get('usr_id') . "'");
                if ($text != '') {
                    if (isset($_POST['inicialke']) && $_POST['inicialke'] != '')
                        $text = '<b>' . $_POST['inicialke'] . '</b> (__DATE__):' . "\n\r" . $text;
                    $s = sisplet_query("INSERT INTO srv_data_text" . get('db_table') . " (spr_id, vre_id, text, usr_id) VALUES ('0', '$row[id]', '$text', '" . get('usr_id') . "')");
                    if (!$s) echo mysqli_error($GLOBALS['connect_db']);
                }

            }

            if (($row['tip'] < 14) || ($row['tip'] > 15)) {

                // pri ratingu ne smemo brisat kadar se shranjuje preko ajaxa
                if ($row['tip'] != 17 
                    || ($row['tip'] == 17 && 
                            ($row['design'] == 1 || $row['design'] == 3 || get('mobile') > 0)
                        )
                    )
                    
                    add('cache_delete', $row['id'] . ',');                    
            } 
            else {

                save('loop_AW', $_POST['loop_AW']);
                save('ime_AW', $_POST['ime_AW']);

                if ($row['podpora'] == 1) {
                    $sql1 = sisplet_query("SELECT * FROM srv_data_imena WHERE usr_id='" . get('usr_id') . "' AND ((emotion=1 AND countE<6) OR (emotionINT=1)) ORDER BY countE ");
                } elseif ($row['podpora'] == 2) {
                    $sql1 = sisplet_query("SELECT * FROM srv_data_imena WHERE usr_id='" . get('usr_id') . "' AND ((social=1 AND countS<6) OR (socialINT=1)) AND ((emotion<1 OR countE>5) AND (emotionINT<1)) ORDER BY countS ");
                }

                if (get('ime_AW') - 1 != -1)
                    if (mysqli_num_rows($sql1) > 0)
                        mysqli_data_seek($sql1, get('ime_AW') - 1);
                    else
                        if (mysqli_num_rows($sql1) > 0)
                            mysqli_data_seek($sql1, mysqli_num_rows($sql1) - 1);

                $row1 = mysqli_fetch_array($sql1);

                sisplet_query("DELETE FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND vre_id='$row1[id]'");
                sisplet_query("DELETE FROM srv_data_vrednost" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "'");
                sisplet_query("DELETE FROM srv_data_text" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "'");
            }


            // če imamo polja 99,98,97 shranimo te vrednosti ( vrednost_$row[id]_other )
            if (isset($_POST['other_selected_vrednost_' . $row['id']]) && $_POST['other_selected_vrednost_' . $row['id']] != "")
                $saved_other = Model::setOtherValue($row['id'], $_POST['other_selected_vrednost_' . $row['id']]);
            else // pobrišemo morebitne zapise
                $deleted_other = Model::setOtherValue($row['id'], null);


            $hasOthers = $this->savePostedSpecialVars($row);
            if ($row['tip'] != 5) {    // ni nagovor


                // radio ali select - dodaten pogoj zaradi spola pri glasovanju, ki ga preverjamo na koncu
                if (($row['tip'] == 1 || $row['tip'] == 3) && (($rowa['survey_type'] != 0) || ($row['vrstni_red'] == 1))) {

                    $vrednost = (isset($_POST['vrednost_' . $row['id']]) ? $_POST['vrednost_' . $row['id']] : null);

                    if ($rowa['mass_insert'] == 1 && $_GET['m'] != 'quick_edit' && $_GET['a'] != 'edit_data_question_save') $vrednost = Model::mass_insert($row['id'], $row['tip'], $_POST['vrednost_' . $row['id']]);
                    if ($_POST['visible_' . $row['id']] == 1) {

                        // ce je drag-drop postavitev (orientation == 8)
                        if ($row['orientation'] == 8 && get('mobile') != 1) {
                            $sql_8 = sisplet_query("SELECT vre_id FROM srv_data_vrednost" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "'");
                            $row_8 = mysqli_fetch_array($sql_8);
                            $vrednost_8 = $row_8['vre_id'];
                            $srv_data_vrednost .= "('$row[id]', '$vrednost_8', '" . get('usr_id') . "', $loop_id),";
                        }

                        if ($vrednost > 0) {
                            $srv_data_vrednost .= "('$row[id]', '$vrednost', '" . get('usr_id') . "', $loop_id),";
                            
                            Model::user_not_lurker();
                            
                            if ($_POST['textfield_' . $vrednost] != '')
                                $srv_data_text .= "('$row[id]', '$vrednost', '" . $_POST['textfield_' . $vrednost] . "', '', '" . get('usr_id') . "', $loop_id),";
                        } 
                        else {

                        }
                    } 
                    else {
                        $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }


                    // checkbox
                } elseif ($row['tip'] == 2) {

                    if ($_POST['visible_' . $row['id']] == 1) {

                        //ce je drag-drop postavitev (orientation == 8)
                        if ($row['orientation'] == 8 && get('mobile') != 1) {
                            
                            $sql_8 = sisplet_query("SELECT vre_id FROM srv_data_vrednost" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "'");
                            
                            while ($row_8 = mysqli_fetch_array($sql_8)) {
                                $vrednost_8 = $row_8['vre_id'];
                                $srv_data_vrednost .= "('$row[id]', '$vrednost_8', '" . get('usr_id') . "', $loop_id),";
                            }
                        }

                        if (isset($_POST['vrednost_' . $row['id']])) {

                            $vrednost = $_POST['vrednost_' . $row['id']];
                            
                            if ($rowa['mass_insert'] == 1 && $_GET['m'] != 'quick_edit' && $_GET['a'] != 'edit_data_question_save') $vrednost = Model::mass_insert($row['id'], $row['tip'], $_POST['vrednost_' . $row['id']]);

                            //ce ni drag-drop postavitev (orientation != 8)
                            if ($row['orientation'] != 8 || get('mobile') == 1) {
                                foreach ($vrednost AS $key => $val) {
                                    if ($val > 0) {
                                        $srv_data_vrednost .= "('$row[id]', '$val', '" . get('usr_id') . "', $loop_id),";

                                        Model::user_not_lurker();

                                        if (isset($_POST['textfield_' . $val]) && $_POST['textfield_' . $val] != '')
                                            $srv_data_text .= "('$row[id]', '$val', '" . $_POST['textfield_' . $val] . "', '', '" . get('usr_id') . "', $loop_id),";
                                    }
                                }
                            }

                        }

                        // če imamo if na vprašanjij, imamo v arrayu vrednosti katere so -2
                        if (isset($_POST['cond_vrednost_' . $row['id']])) {
                            $vrednost = $_POST['cond_vrednost_' . $row['id']];
                            foreach ($vrednost AS $key => $val) {
                                if ($val > 0) {
                                    $srv_data_vrednost_cond .= "('$row[id]', '$val', '-2', '" . get('usr_id') . "', $loop_id),";
                                }
                            }
                        }
                    } else {
                        $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }


                    // multigrid
                } elseif ($row['tip'] == 6 && $row['enota'] != 3) {

                    if ($_POST['visible_' . $row['id']] == 1) {

                        $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]'");
                        while ($row1 = mysqli_fetch_array($sql1)) {

                            $grid_id = $_POST['vrednost_' . $row1['id']];

                            // $grid_id  > 0
                            if ((string)$grid_id != "") { 
                                # grid_id je lahko tudi negativen za missing vrednosti
                                $srv_data_grid .= "('$row[id]', '$row1[id]', '" . get('usr_id') . "', '$grid_id', $loop_id),";
                                Model::user_not_lurker();
                            } 
                            else {
                                // če imamo if na vprašanju, imamo v arrayu vrednosti katere so -2
                                if (isset($_POST['cond_vrednost_' . $row1['id']])) {
                                    $srv_data_grid .= "('$row[id]', '$row1[id]', '" . get('usr_id') . "', '-2', $loop_id),";
                                }
                            }

                            // vsebino text polja vnesemo v vsakem primeru
                            if ($_POST['textfield_' . $row1['id']] != '') {
                                $srv_data_text .= "('$row[id]', '$row1[id]', '" . $_POST['textfield_' . $row1['id']] . "', '', '" . get('usr_id') . "', $loop_id),";
                                Model::user_not_lurker();
                            }
                        }

                        //ureditev za drag and drop grid
                        if ($row['enota'] == 9) {    //ce je postavitev drag and drop
                            $sql_9 = sisplet_query("SELECT vre_id, grd_id FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "'");
                            while ($row_9 = mysqli_fetch_array($sql_9)) {
                                $vrednost_9 = $row_9['vre_id'];
                                $grd_id_9 = $row_9['grd_id'];
                                $srv_data_grid .= "('$row[id]', '$vrednost_9', '" . get('usr_id') . "', $grd_id_9,  $loop_id),";
                            }
                        }
                    } 
                    else {
                        $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }

                    //double multigrid
                } elseif ($row['tip'] == 6 && $row['enota'] == 3) {
                    if ($_POST['visible_' . $row['id']] == 1) {

                        $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]'");
                        while ($row1 = mysqli_fetch_array($sql1)) {

                            $grid_id = $_POST['vrednost_' . $row1['id']];
                            $grid_id2 = $_POST['vrednost_' . $row1['id'] . '_part_2'];

                            if ((string)$grid_id != "") // $grid_id  > 0
                            { # grid_id je lahko tudi negativen za missing vrednosti
                                $srv_data_checkgrid .= "('$row[id]', '$row1[id]', '" . get('usr_id') . "', '$grid_id', $loop_id),";
                                Model::user_not_lurker();
                            } 
                            else {
                            }

                            if ((string)$grid_id2 != "") // $grid_id2  > 0
                            { # grid_id2 je lahko tudi negativen za missing vrednosti
                                $srv_data_checkgrid .= "('$row[id]', '$row1[id]', '" . get('usr_id') . "', '$grid_id2', $loop_id),";
                                Model::user_not_lurker();
                            }

                            // vsebino text polja vnesemo v vsakem primeru
                            if (isset($_POST['textfield_' . $row1['id']]) && $_POST['textfield_' . $row1['id']] != '') {
                                $srv_data_text .= "('$row[id]', '$row1[id]', '" . $_POST['textfield_' . $row1['id']] . "', '', '" . get('usr_id') . "', $loop_id),";
                                Model::user_not_lurker();
                            }
                        }

                    } else {
                        $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }


                    // multicheckbox
                } elseif ($row['tip'] == 16) {

                    if ($_POST['visible_' . $row['id']] == 1) {
                        $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]'");

                        $sql2 = sisplet_query("SELECT id FROM srv_grid WHERE spr_id = '$row[id]' ORDER BY vrstni_red");

                        while ($row1 = mysqli_fetch_array($sql1)) {
                            if (mysqli_num_rows($sql2) > 0)
                                mysqli_data_seek($sql2, 0);

                            // Ce imamo select-boxe moramo drugace obdelat podatke
                            if ($row['enota'] == 6) {
                                $vrednost = $_POST['vrednost_' . $row1['id']];

                                foreach ($vrednost AS $key => $val) {
                                    $srv_data_checkgrid .= "('$row[id]', '$row1[id]', '" . get('usr_id') . "', '$val', $loop_id),";
                                    Model::user_not_lurker();
                                }
                            } else {
                                while ($row2 = mysqli_fetch_array($sql2)) {

                                    $grid_id = $_POST['vrednost_' . $row1['id'] . '_grid_' . $row2['id']];

                                    // $grid_id  > 0
                                    if ((string)$grid_id != ""){ 
                                        # grid_id je lahko tudi negativen za missing vrednosti
                                        $srv_data_checkgrid .= "('$row[id]', '$row1[id]', '" . get('usr_id') . "', '$grid_id', $loop_id),";
                                        Model::user_not_lurker();
                                    } 
                                    else {
                                    }
                                }
                            }

                            // vsebino text polja vnesemo v vsakem primeru
                            if ($_POST['textfield_' . $row1['id']] != '') {
                                $srv_data_text .= "('$row[id]', '$row1[id]', '" . $_POST['textfield_' . $row1['id']] . "', '', '" . get('usr_id') . "', $loop_id),";
                                Model::user_not_lurker();
                            }
                        }
                        //ureditev za drag and drop grid
                        if ($row['enota'] == 9) {    //ce je postavitev drag and drop
                            $sql_9 = sisplet_query("SELECT vre_id, grd_id FROM srv_data_checkgrid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "'");
                            while ($row_9 = mysqli_fetch_array($sql_9)) {
                                $vrednost_9 = $row_9['vre_id'];
                                $grd_id_9 = $row_9['grd_id'];
                                $srv_data_checkgrid .= "('$row[id]', '$vrednost_9', '" . get('usr_id') . "', $grd_id_9,  $loop_id),";
                            }
                        }

                    } else {
                        $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }

                    // multitext
                } elseif ($row['tip'] == 19) {
                    if ($_POST['visible_' . $row['id']] == 1) {

                        $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]'");

                        $sql2 = sisplet_query("SELECT id, other FROM srv_grid WHERE spr_id = '$row[id]' ORDER BY vrstni_red");
                        if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);

                        while ($row1 = mysqli_fetch_array($sql1)) {

                            //$sql2 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id = '$row[id]' ORDER BY vrstni_red");
                            if (mysqli_num_rows($sql2) > 0)
                                mysqli_data_seek($sql2, 0);

                            while ($row2 = mysqli_fetch_array($sql2)) {

                                $value = $_POST['vrednost_' . $row1['id'] . '_grid_' . $row2['id']];
                                $grid_id = $row2['id'];

                                # če mamo missing
                                if ($row2['other'] != 0
                                    && isset($_POST['vrednost_' . $row1['id'] . '_grid_' . $row2['id']])
                                    && $_POST['vrednost_' . $row1['id'] . '_grid_' . $row2['id']] != ''
                                ) {
                                    $srv_data_grid .= "('$row[id]', '$row1[id]', '" . get('usr_id') . "', '$grid_id', $loop_id),";
                                    Model::user_not_lurker();

                                } 
                                else if ($value != '') {
                                    $srv_data_textgrid .= "('$row[id]', '$row1[id]', '" . get('usr_id') . "', '$grid_id', '$value', $loop_id),";
                                    Model::user_not_lurker();
                                } 
                                else {
                                    //sisplet_query("INSERT INTO srv_data_textgrid (spr_id, vre_id, usr_id, grd_id) VALUES ('$row[id]', '$row1[id]', '".get('usr_id')."', '-1')");
                                    // ta je bil ze prej zakomentiran...
                                }

                            }
                            
                            // vsebino text polja vnesemo v vsakem primeru
                            if ($_POST['textfield_' . $row1['id']] != '') {
                                $srv_data_text .= "('$row[id]', '$row1[id]', '" . $_POST['textfield_' . $row1['id']] . "', '', '" . get('usr_id') . "', $loop_id),";
                                Model::user_not_lurker();
                            }
                        }

                    } else {
                        
                        // Poseben primer za modul sazu in komentarje na razvrscanje regij
                        if(SurveyInfo::getInstance()->checkSurveyModule('sazu')){

                            $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]'");

                            $sql2 = sisplet_query("SELECT id, other FROM srv_grid WHERE spr_id = '$row[id]' ORDER BY vrstni_red");
                            if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);

                            while ($row1 = mysqli_fetch_array($sql1)) {

                                if (mysqli_num_rows($sql2) > 0)
                                    mysqli_data_seek($sql2, 0);

                                while ($row2 = mysqli_fetch_array($sql2)) {

                                    $value = $_POST['vrednost_' . $row1['id'] . '_grid_' . $row2['id']];
                                    $grid_id = $row2['id'];

                                    if ($value != '') {
                                        $srv_data_textgrid .= "('$row[id]', '$row1[id]', '" . get('usr_id') . "', '$grid_id', '$value', $loop_id),";
                                        Model::user_not_lurker();
                                    }
                                }
                                // vsebino text polja vnesemo v vsakem primeru
                                if ($_POST['textfield_' . $row1['id']] != '') {
                                    $srv_data_text .= "('$row[id]', '$row1[id]', '" . $_POST['textfield_' . $row1['id']] . "', '', '" . get('usr_id') . "', $loop_id),";
                                    Model::user_not_lurker();
                                }
                            }
                        }
                        else
                            $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }


                    // multinumber
                } elseif ($row['tip'] == 20) {

                    if ($_POST['visible_' . $row['id']] == 1) {

                        $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]'");

                        $sql2 = sisplet_query("SELECT id, other FROM srv_grid WHERE spr_id = '$row[id]' ORDER BY vrstni_red");

                        while ($row1 = mysqli_fetch_array($sql1)) {

                            //$sql2 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id = '$row[id]' ORDER BY vrstni_red");
                            if (mysqli_num_rows($sql2) > 0)
                                mysqli_data_seek($sql2, 0);

                            while ($row2 = mysqli_fetch_array($sql2)) {
                                $value = $_POST['vrednost_' . $row1['id'] . '_grid_' . $row2['id']];
                                $grid_id = $row2['id'];

                                # če mamo missing
                                if ($row2['other'] != 0
                                    && isset($_POST['vrednost_' . $row1['id'] . '_grid_' . $row2['id']])
                                    && $_POST['vrednost_' . $row1['id'] . '_grid_' . $row2['id']] != ''
                                ) {
                                    $srv_data_grid .= "('$row[id]', '$row1[id]', '" . get('usr_id') . "', '$grid_id', $loop_id),";
                                    Model::user_not_lurker();

                                } else if ($value != '') {
                                    Model::user_not_lurker();
                                    //sisplet_query("INSERT INTO srv_data_textgrid (spr_id, vre_id, usr_id, grd_id, text) VALUES ('$row[id]', '$row1[id]', '".get('usr_id')."', '$grid_id', '$value')");
                                    $srv_data_textgrid .= "('$row[id]', '$row1[id]', '" . get('usr_id') . "', '$grid_id', '$value', $loop_id),";
                                } else {
                                    //sisplet_query("INSERT INTO srv_data_textgrid (spr_id, vre_id, usr_id, grd_id) VALUES ('$row[id]', '$row1[id]', '".get('usr_id')."', '-1')");
                                    // ze prej ...
                                }

                            }

                            // vsebino text polja vnesemo v vsakem primeru
                            if ($_POST['textfield_' . $row1['id']] != '') {
                                //sisplet_query("INSERT INTO srv_data_text (spr_id, vre_id, text, usr_id) VALUES ('$row[id]', '$row1[id]', '".$_POST['textfield_'.$row1['id']]."', '".get('usr_id')."')");
                                $srv_data_text .= "('$row[id]', '$row1[id]', '" . $_POST['textfield_' . $row1['id']] . "', '', '" . get('usr_id') . "', $loop_id),";
                                Model::user_not_lurker();
                            }

                        }

                    } else {
                        //$grid_id = $row2['id'];
                        //sisplet_query("INSERT INTO srv_data_textgrid (spr_id, vre_id, usr_id, grd_id, text) VALUES ('$row[id]', '$row1[id]', '".get('usr_id')."', '$grid_id', '-2')");
                        //$srv_data_textgrid .= "('$row[id]', '$row1[id]', '".get('usr_id')."', '$grid_id', '-2'),";
                        $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }

                    // multiple grid (kombinirana tabela)
                } elseif ($row['tip'] == 24) {
                    if ($_POST['visible_' . $row['id']] == 1) {

                        $spr_sql = sisplet_query("SELECT s.id, s.tip, s.enota FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='$row[id]' AND m.spr_id=s.id ORDER BY m.vrstni_red");
                        if (!$spr_sql) echo mysqli_error($GLOBALS['connect_db']);
                        while ($spr_row = mysqli_fetch_array($spr_sql)) {

                            add('cache_delete', $spr_row['id'] . ',');

                            if ($spr_row['tip'] == 6) {

                                $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '" . $spr_row['id'] . "'");
                                while ($row1 = mysqli_fetch_array($sql1)) {

                                    $grid_id = $_POST['multi_' . $spr_row['id'] . '_' . $row1['id']];

                                    if ((string)$grid_id != "") // $grid_id  > 0
                                    { # grid_id je lahko tudi negativen za missing vrednosti
                                        //sisplet_query("INSERT INTO srv_data_grid".get('db_table')." (spr_id, vre_id, usr_id, grd_id) VALUES ('$row[id]', '$row1[id]', '".get('usr_id')."', '$grid_id')");
                                        $srv_data_grid .= "('$spr_row[id]', '$row1[id]', '" . get('usr_id') . "', '$grid_id', $loop_id),";
                                        Model::user_not_lurker();
                                    } else {
                                        //sisplet_query("INSERT INTO srv_data_grid".get('db_table')." (spr_id, vre_id, usr_id, grd_id) VALUES ('$row[id]', '$row1[id]', '".get('usr_id')."', '-1')");
                                        //$srv_data_grid .= "('$row[id]', '$row1[id]', '".get('usr_id')."', '-1'),";
                                    }
                                    // vsebino text polja vnesemo v vsakem primeru
                                    if ($_POST['textfield_' . $row1['id']] != '') {
                                        //sisplet_query("INSERT INTO srv_data_text (spr_id, vre_id, text, usr_id) VALUES ('$row[id]', '$row1[id]', '".$_POST['textfield_'.$row1['id']]."', '".get('usr_id')."')");
                                        $srv_data_text .= "('$spr_row[id]', '$row1[id]', '" . $_POST['textfield_' . $row1['id']] . "', '', '" . get('usr_id') . "', $loop_id),";
                                        Model::user_not_lurker();
                                    }
                                }

                            } elseif ($spr_row['tip'] == 16) {

                                $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$spr_row[id]'");

                                $sql2 = sisplet_query("SELECT id FROM srv_grid WHERE spr_id = '$spr_row[id]' ORDER BY vrstni_red");

                                while ($row1 = mysqli_fetch_array($sql1)) {
                                    //$sql2 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id = '$row[id]' ORDER BY vrstni_red");
                                    if (mysqli_num_rows($sql2) > 0)
                                        mysqli_data_seek($sql2, 0);
                                    // Ce imamo select-boxe moramo drugace obdelat podatke
                                    if ($spr_row['enota'] == 6) {
                                        $vrednost = $_POST['multi_' . $spr_row['id'] . '_' . $row1['id']];    //name="multi_'.$row_spr['id'].'_'.$row2['id'].'[]"
                                        foreach ($vrednost AS $key => $val) {
                                            $srv_data_checkgrid .= "('$spr_row[id]', '$row1[id]', '" . get('usr_id') . "', '$val', $loop_id),";
                                            Model::user_not_lurker();
                                        }
                                    } else {
                                        while ($row2 = mysqli_fetch_array($sql2)) {

                                            $grid_id = $_POST['multi_' . $spr_row['id'] . '_' . $row1['id'] . '_grid_' . $row2['id']];
                                            if ((string)$grid_id != "") // $grid_id  > 0
                                            { # grid_id je lahko tudi negativen za missing vrednosti
                                                //sisplet_query("INSERT INTO srv_data_checkgrid (spr_id, vre_id, usr_id, grd_id) VALUES ('$row[id]', '$row1[id]', '".get('usr_id')."', '$grid_id')");
                                                $srv_data_checkgrid .= "('$spr_row[id]', '$row1[id]', '" . get('usr_id') . "', '$grid_id', $loop_id),";
                                                Model::user_not_lurker();
                                            } else {
                                                //sisplet_query("INSERT INTO srv_data_checkgrid (spr_id, vre_id, usr_id, grd_id) VALUES ('$row[id]', '$row1[id]', '".get('usr_id')."', '-1')");
                                                // ta je bil ze prej zakomentiran..
                                            }
                                        }
                                    }
                                    // vsebino text polja vnesemo v vsakem primeru
                                    if ($_POST['textfield_' . $row1['id']] != '') {
                                        //sisplet_query("INSERT INTO srv_data_text (spr_id, vre_id, text, usr_id) VALUES ('$row[id]', '$row1[id]', '".$_POST['textfield_'.$row1['id']]."', '".get('usr_id')."')");
                                        $srv_data_text .= "('$spr_row[id]', '$row1[id]', '" . $_POST['textfield_' . $row1['id']] . "', '', '" . get('usr_id') . "', $loop_id),";
                                        Model::user_not_lurker();
                                    }
                                }

                            } elseif ($spr_row['tip'] == 19) {

                                $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$spr_row[id]'");

                                $sql2 = sisplet_query("SELECT id, other FROM srv_grid WHERE spr_id = '$spr_row[id]' ORDER BY vrstni_red");
                                if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);

                                while ($row1 = mysqli_fetch_array($sql1)) {

                                    //$sql2 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id = '$row[id]' ORDER BY vrstni_red");
                                    if (mysqli_num_rows($sql2) > 0)
                                        mysqli_data_seek($sql2, 0);

                                    while ($row2 = mysqli_fetch_array($sql2)) {

                                        $value = $_POST['multi_' . $spr_row['id'] . '_' . $row1['id'] . '_grid_' . $row2['id']];
                                        $grid_id = $row2['id'];

                                        # če mamo missing
                                        if ($row2['other'] != 0
                                            && isset($_POST['multi_' . $spr_row['id'] . '_' . $row1['id'] . '_grid_' . $row2['id']])
                                            && $_POST['multi_' . $spr_row['id'] . '_' . $row1['id'] . '_grid_' . $row2['id']] != ''
                                        ) {
                                            $srv_data_grid .= "('$spr_row[id]', '$row1[id]', '" . get('usr_id') . "', '$grid_id', $loop_id),";
                                            Model::user_not_lurker();

                                        } else if ($value != '') {
                                            //sisplet_query("INSERT INTO srv_data_textgrid (spr_id, vre_id, usr_id, grd_id, text) VALUES ('$row[id]', '$row1[id]', '".get('usr_id')."', '$grid_id', '$value')");
                                            $srv_data_textgrid .= "('$spr_row[id]', '$row1[id]', '" . get('usr_id') . "', '$grid_id', '$value', $loop_id),";
                                            Model::user_not_lurker();
                                        } else {
                                            //sisplet_query("INSERT INTO srv_data_textgrid (spr_id, vre_id, usr_id, grd_id) VALUES ('$row[id]', '$row1[id]', '".get('usr_id')."', '-1')");
                                            // ta je bil ze prej zakomentiran...
                                        }

                                    }
                                    // vsebino text polja vnesemo v vsakem primeru
                                    if ($_POST['textfield_' . $row1['id']] != '') {
                                        //sisplet_query("INSERT INTO srv_data_text (spr_id, vre_id, text, usr_id) VALUES ('$row[id]', '$row1[id]', '".$_POST['textfield_'.$row1['id']]."', '".get('usr_id')."')");
                                        $srv_data_text .= "('$spr_row[id]', '$row1[id]', '" . $_POST['textfield_' . $row1['id']] . "', '', '" . get('usr_id') . "', $loop_id),";
                                        Model::user_not_lurker();
                                    }
                                }

                            } elseif ($spr_row['tip'] == 20) {

                                $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$spr_row[id]'");

                                $sql2 = sisplet_query("SELECT id, other FROM srv_grid WHERE spr_id = '$spr_row[id]' ORDER BY vrstni_red");

                                while ($row1 = mysqli_fetch_array($sql1)) {

                                    //$sql2 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id = '$row[id]' ORDER BY vrstni_red");
                                    if (mysqli_num_rows($sql2) > 0)
                                        mysqli_data_seek($sql2, 0);

                                    while ($row2 = mysqli_fetch_array($sql2)) {

                                        $value = $_POST['multi_' . $spr_row['id'] . '_' . $row1['id'] . '_grid_' . $row2['id']];
                                        $grid_id = $row2['id'];

                                        # če mamo missing
                                        if ($row2['other'] != 0
                                            && isset($_POST['multi_' . $spr_row['id'] . '_' . $row1['id'] . '_grid_' . $row2['id']])
                                            && $_POST['multi_' . $spr_row['id'] . '_' . $row1['id'] . '_grid_' . $row2['id']] != ''
                                        ) {
                                            $srv_data_grid .= "('$spr_row[id]', '$row1[id]', '" . get('usr_id') . "', '$grid_id', $loop_id),";
                                            Model::user_not_lurker();

                                        } else if ($value != '') {
                                            Model::user_not_lurker();
                                            //sisplet_query("INSERT INTO srv_data_textgrid (spr_id, vre_id, usr_id, grd_id, text) VALUES ('$row[id]', '$row1[id]', '".get('usr_id')."', '$grid_id', '$value')");
                                            $srv_data_textgrid .= "('$spr_row[id]', '$row1[id]', '" . get('usr_id') . "', '$grid_id', '$value', $loop_id),";
                                        } else {
                                            //sisplet_query("INSERT INTO srv_data_textgrid (spr_id, vre_id, usr_id, grd_id) VALUES ('$row[id]', '$row1[id]', '".get('usr_id')."', '-1')");
                                            // ze prej ...
                                        }

                                    }

                                    // vsebino text polja vnesemo v vsakem primeru
                                    if ($_POST['textfield_' . $row1['id']] != '') {
                                        //sisplet_query("INSERT INTO srv_data_text (spr_id, vre_id, text, usr_id) VALUES ('$row[id]', '$row1[id]', '".$_POST['textfield_'.$row1['id']]."', '".get('usr_id')."')");
                                        $srv_data_text .= "('$spr_row[id]', '$row1[id]', '" . $_POST['textfield_' . $row1['id']] . "', '', '" . get('usr_id') . "', $loop_id),";
                                        Model::user_not_lurker();
                                    }

                                }

                            }

                        }

                    } else {
                        $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }

                    // textbox
                } elseif ($row['tip'] == 4) {

                    if ($_POST['visible_' . $row['id']] == 1) {
                        $vrednost = $_POST['vrednost_' . $row['id']];
                        if ($vrednost != '') {
                            //sisplet_query("INSERT INTO srv_data_text (spr_id, text, usr_id) VALUES ('$row[id]', '$vrednost', '".get('usr_id')."')");
                            $srv_data_text .= "('$row[id]', '', '$vrednost', '', '" . get('usr_id') . "', $loop_id),";
                            Model::user_not_lurker();
                        } else {
                            //sisplet_query("INSERT INTO srv_data_vrednost".get('db_table')." (spr_id, vre_id, usr_id) VALUES ('$row[id]', '-1', '".get('usr_id')."')");
                            //$srv_data_vrednost .= "('$row[id]', '-1', '".get('usr_id')."'),";
                        }
                    } else {
                        //sisplet_query("INSERT INTO srv_data_vrednost".get('db_table')." (spr_id, vre_id, usr_id) VALUES ('$row[id]', '-2', '".get('usr_id')."')");
                        $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }


                    // textbox*
                } elseif ($row['tip'] == 21) {

                    if ($_POST['visible_' . $row['id']] == 1) {

                        $empty = true;

                        $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]'");
                        while ($row1 = mysqli_fetch_array($sql1)) {

                            // posebej pohendlamo upload datotek ali fotografijo, ce je respondent uporoabil navadni upload slike
                            if ($row['upload'] == 1 || ($row['upload'] == 2 && !$_POST['foto_vrednost_' . $row['id'] . '_kos_' . $row1['id']])) {

                                $path_parts = pathinfo($_FILES['vrednost_' . $row['id'] . '_kos_' . $row1['id']]["name"]);
                                $ext = $path_parts['extension'];
                                $name = substr($path_parts['filename'], 0, 25);
                                
                                $file_allowed = true;

                                // Preverimo ce je file type dovoljen (prava koncnica)
                                if( !in_array(strtolower($ext), array("jpeg", "jpg", "png", "gif", "pdf", "doc", "docx", "xls", "xlsx")) ){
                                    $file_allowed = false;
                                }

                                // Preverimo ce je file velikost ok
                                if( (int)(filesize($_FILES['vrednost_' . $row['id'] . '_kos_' . $row1['id']]["tmp_name"]) / 1024 / 1024) > 16 ){
                                    $file_allowed = false;
                                }

                                // Ce imamo izbran file in je ok koncnica in velikost
                                if (strlen($name) > 0 && $file_allowed) {
                                    $filename = get('usr_id') . '_' . $name . '.' . $ext;

                                    move_uploaded_file($_FILES['vrednost_' . $row['id'] . '_kos_' . $row1['id']]["tmp_name"], 'uploads/' . $filename);
                                    $vrednost = uniqid();
                                    
                                    //pri fotografiji naredi tukaj da zbrise iz mape uploads morebitno prejsnjo sliko narejeno z webcam (lahko, da sta razlicna exstensiona in se ne povozi)

                                    sisplet_query("INSERT INTO srv_data_upload (ank_id, usr_id, code, filename) VALUES ('" . get('anketa') . "', '" . get('usr_id') . "', '$vrednost', '$filename')");
                                } 
                                // Ni bil nalozen noben file
                                else {
                                    $vrednost = '';
                                }

                            } 
                            elseif($row['upload'] == 2){
                                $encoded_data = $_POST['foto_vrednost_' . $row['id'] . '_kos_' . $row1['id']];
                                
                                if($encoded_data != ''){
                                    $binary_data = base64_decode( $encoded_data );

                                    $filename = get('usr_id') . '_' . $row['id'] . '_' . get('anketa') . '.jpg';

                                    // save to server (beware of permissions)
                                    $result = file_put_contents( 'uploads/' . $filename, $binary_data );
                                    if (!$result) die("Could not save image!  Check file permissions.");
                                    else{
                                        $vrednost = uniqid();

                                        sisplet_query("INSERT INTO srv_data_upload (ank_id, usr_id, code, filename) VALUES ('" . get('anketa') . "', '" . get('usr_id') . "', '$vrednost', '$filename')");
                                    }
                                }
                                //ni bilo datoteke
                                else {
                                    $vrednost = '';
                                }
                            }
                            // posebej pohendlamo signature - shranjevanje slike
                            elseif ($row['signature'] == 1) {
                                
                                //if (isset($_POST['signature-data_spremenljivka_'.$row['id']])){	// če so prišli podatki, nadaljuj
                                if ($_POST['signature-data_spremenljivka_' . $row['id']] != "" || $_POST['vrednost_' . $row['id'] . '_kos_' . $row1['id']] != "") {    // če so prišli podatki o podpisu ali o imenu v polju

                                    $podatki_slika = $_POST['signature-data_spremenljivka_' . $row['id']];    //podatki za generiranje slike
                                    $vrednost = $_POST['vrednost_' . $row['id'] . '_kos_' . $row1['id']];    //vnešeno besedilo v input polje Podpis osebe
                                    $signature_name = $vrednost;

                                    //$vrednost_signature = uniqid();    //vrednost, ki se beleži v bazi kot unique koda
                                    $vrednost_signature = $row['id'];    //vrednost, ki se beleži v bazi kot spr_id

                                    //$sqlIsFilename = sisplet_query("SELECT filename FROM srv_data_upload WHERE usr_id = '" . get('usr_id') . "' AND ank_id = '" . get('anketa') . "' ");
                                    $sqlIsFilename = sisplet_query("SELECT filename FROM srv_data_upload WHERE usr_id = '" . get('usr_id') . "' AND ank_id = '" . get('anketa') . "' AND code = '" . $row['id'] . "' ");
                                    
                                    $rowSqlIsFilename = mysqli_fetch_array($sqlIsFilename);
                                    
                                    
                                    
                                    if (mysqli_num_rows($sqlIsFilename) == 0) {    //ce ni nicesar v bazi, ustvari datoteko s sliko in vpisi informacijo v bazo
                                            //odstranitev sumnikov in presledkov iz vnesenega imena podpisanega za shranjevanje datoteke
                                            $posebni_znaki = array("č", "ć", "ž", "š", "đ", "Č", "Ć", "Ž", "Š", "Đ");
                                            $navadni_znaki = array("c", "c", "z", "s", "d", "C", "C", "Z", "S", "D");
                                            $signature_name = str_replace($posebni_znaki, $navadni_znaki, $signature_name);    //replace posebnih znakov (tako, ime datoteke ne bo sporno)
                                            $signature_name = str_replace(" ", "_", $signature_name); //replace presledka

                                            $data_pieces = explode(",", $podatki_slika);
                                            $encoded_image = $data_pieces[1];
                                            $decoded_image = base64_decode($encoded_image);
                                            $data = $decoded_image;
                                            $im = imagecreatefromstring($data);
                                            if ($im !== false) {
                                                imagesavealpha($im, true);    //da reši črno sliko
                                                imagepng($im, './uploads/' . get('usr_id') . '_' . $row['id'] . '_' . get('anketa') . '.png');    //ustvarjeno sliko prenesi v mapo, kjer se nahajajo tudi upload datoteke
                                                imagedestroy($im);
                                            } else {
                                                echo 'An error occurred.';
                                            }

                                            //vpisi v bazo, v tabelo, kjer se vpisujejo datoteke, ki se uploadajo, podatke o generirani sliki podpisa
                                            $filename_signature = get('usr_id') . '_' . $row['id'] . '_' . get('anketa') . '.png';
                                            
                                            sisplet_query("INSERT INTO srv_data_upload (ank_id, usr_id, code, filename) VALUES ('" . get('anketa') . "', '" . get('usr_id') . "', '$vrednost_signature', '$filename_signature')");

                                    }
                                } else {
                                    $vrednost = '';
                                }
                            } else {
                                $vrednost = $_POST['vrednost_' . $row['id'] . '_kos_' . $row1['id']];
                            }

                            if ($vrednost != '') {
                                //sisplet_query("INSERT INTO srv_data_text (spr_id, vre_id, text, usr_id) VALUES ('$row[id]', '$row1[id]', '$vrednost', '".get('usr_id')."')");
                                $srv_data_text .= "('$row[id]', '$row1[id]', '$vrednost', '', '" . get('usr_id') . "', $loop_id),";
                                Model::user_not_lurker();
                                $empty = false;
                            }
                        }

                        if ($empty) {
                            if (isset($_POST['vrednost_mv_' . $row['id']])) {
                                //sisplet_query("INSERT INTO srv_data_vrednost".get('db_table')." (spr_id, vre_id, usr_id) VALUES ('$row[id]', '-2', '".get('usr_id')."')");
                                $srv_data_vrednost .= "('$row[id]', '" . $_POST['vrednost_mv_' . $row['id']][0] . "', '" . get('usr_id') . "', $loop_id),";
                            }

                            #preverimo ali je missing
                            //sisplet_query("INSERT INTO srv_data_vrednost".get('db_table')." (spr_id, vre_id, usr_id) VALUES ('$row[id]', '-1', '".get('usr_id')."')");
                            //$srv_data_vrednost .= "('$row[id]', '-1', '".get('usr_id')."'),";
                        }
                        
                        if ($row['variable'] == 'email' && $row['sistem'] == '1') {
                            if (trim($vrednost) != '' && (int)$rowa['email_to_list'] == 1) {
                                User::addUserEmailToList(trim($vrednost));
                            }
                            
                            # sinhroniziramo spremembo emaila z vabili.
                            User::sinhronizeInvitationEmail($vrednost);
                        }
                        
                    } else {
                        //sisplet_query("INSERT INTO srv_data_vrednost".get('db_table')." (spr_id, vre_id, usr_id) VALUES ('$row[id]', '-2', '".get('usr_id')."')");
                        $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }

                    // number
                } elseif ($row['tip'] == 7) {

                    if ($_POST['visible_' . $row['id']] == 1) {

                        $empty = true;

                        $i = 1;
                        if (isset($_POST['vrednost_' . $row['id']])) {
                            $vrednost = $_POST['vrednost_' . $row['id']];

                            $text = '';
                            $text2 = '';

                            foreach ($vrednost AS $key => $val) {
                                if ($i == 1) {
                                    if ($val != '' && $val != '-') {
                                        $text = $val;
                                        $empty = false;
                                        
                                        Model::user_not_lurker();
                                    } 
                                    else {
                                    }

                                } else {

                                    if ($val != '' && $val != '-') {
                                        $text2 = $val;
                                        $empty = false;

                                        Model::user_not_lurker();
                                    } 
                                    else {
                                    }
                                }

                                $i++;
                            }

                        }

                        if (!$empty) {
                            $srv_data_text .= "('$row[id]', '', '$text', '$text2', '" . get('usr_id') . "', $loop_id),";
                        } else {
                            if (isset($_POST['vrednost_mv_' . $row['id']])) {
                                //sisplet_query("INSERT INTO srv_data_vrednost".get('db_table')." (spr_id, vre_id, usr_id) VALUES ('$row[id]', '-2', '".get('usr_id')."')");
                                $srv_data_vrednost .= "('$row[id]', '" . $_POST['vrednost_mv_' . $row['id']][0] . "', '" . get('usr_id') . "', $loop_id),";
                            }
                        }

                    } else {
                        //sisplet_query("INSERT INTO srv_data_vrednost".get('db_table')." (spr_id, vre_id, usr_id) VALUES ('$row[id]', '-2', '".get('usr_id')."')");
                        $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }


                    // compute
                } elseif ($row['tip'] == 22) {

                    if (isset($_POST['vrednost_' . $row['id']])) {

                        $val = $_POST['vrednost_' . $row['id']];

                        //if ($val == 'NaN') $val = '-1';         // ce je kateri od odgovorov missing, je tudi kalkulacija missing
                        if ($val == 'NaN') $val = '-88';        // ce je kateri od odgovorov missing, je tudi kalkulacija missing

                        if ($val != '') {
                            $srv_data_text .= "('$row[id]', '', '$val', '', '" . get('usr_id') . "', $loop_id),";
                            Model::user_not_lurker();
                        } 
                        else {
                        }
                    }

                    // kvota
                } elseif ($row['tip'] == 25) {

                    if (isset($_POST['vrednost_' . $row['id']])) {

                        $val = $_POST['vrednost_' . $row['id']];

                        if ($val == 'NaN') $val = '-1';        // ce je kateri od odgovorov missing, je tudi kvota missing

                        if ($val != '') {
                            //sisplet_query("INSERT INTO srv_data_text (spr_id, text, usr_id) VALUES ('$row[id]', '$val', '".get('usr_id')."')");
                            $srv_data_text .= "('$row[id]', '', '$val', '', '" . get('usr_id') . "', $loop_id),";
                            
                            Model::user_not_lurker();
                        } 
                        else {
                        }
                    }

                    // 8_datum
                } elseif ($row['tip'] == 8) {

                    if ($_POST['visible_' . $row['id']] == 1) {
                        if (isset($_POST['vrednost_mv_' . $row['id']]) && count($_POST['vrednost_mv_' . $row['id']]) > 0) {
                            $srv_data_vrednost .= "('$row[id]', '" . $_POST['vrednost_mv_' . $row['id']][0] . "', '" . get('usr_id') . "', $loop_id),";
                            Model::user_not_lurker();
                        } else {

                            $vrednost = $_POST['vrednost_' . $row['id']];
                            if ($vrednost != '') {
                                //sisplet_query("INSERT INTO srv_data_text (spr_id, text, usr_id) VALUES ('$row[id]', '$vrednost', '".get('usr_id')."')");
                                $srv_data_text .= "('$row[id]', '', '$vrednost', '', '" . get('usr_id') . "', $loop_id),";
                                
                                Model::user_not_lurker();
                            }
                            else {
                            }
                        }
                    } else {
                        //sisplet_query("INSERT INTO srv_data_vrednost".get('db_table')." (spr_id, vre_id, usr_id) VALUES ('$row[id]', '-2', '".get('usr_id')."')");
                        $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }

                    // ranking
                } elseif ($row['tip'] == 17) {
                    if ($_POST['visible_' . $row['id']] == 1) {

                        // cifre - ostevilcevanje
                        if ($row['design'] == 1 || get('mobile') > 0) {

                            $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]' AND vrstni_red>0 ORDER BY vrstni_red");
                            while ($row1 = mysqli_fetch_array($sql1)) {

                                if (isset($_POST['spremenljivka_' . $row['id'] . '_vrednost_' . $row1['id']])) {
                                    $vrednost = $_POST['spremenljivka_' . $row['id'] . '_vrednost_' . $row1['id']];
                                    if ($vrednost != '') {
                                        //sisplet_query("REPLACE INTO srv_data_rating (spr_id, vre_id, usr_id, vrstni_red) VALUES ('$row[id]', '$row1[id]', '".get('usr_id')."', '$vrednost')");
                                        $srv_data_rating .= "('$row[id]', '$row1[id]', '" . get('usr_id') . "', '$vrednost', $loop_id),";
                                        Model::user_not_lurker();
                                    } else {
                                        //sisplet_query("DELETE FROM srv_data_rating WHERE vre_id='$row1[id]' AND usr_id='".get('usr_id')."'");
                                    }
                                }
                            }
                        } 
                        // n==k (sortable)
                        else if ($row['design'] == 2) {
                            // shranjuje preko ajaxa
                        } 
                        // n>k
                        else if ($row['design'] == 0) {
                            // shranjuje preko ajaxa
                        }
                        // ranking image hotspot
                        else if ($row['design'] == 3) {

                            $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]' AND vrstni_red>0 ORDER BY vrstni_red");
                            while ($row1 = mysqli_fetch_array($sql1)) {

                                if (isset($_POST['spremenljivka_' . $row['id'] . '_vrednost_' . $row1['id']])) {
                                    $vrednost = $_POST['spremenljivka_' . $row['id'] . '_vrednost_' . $row1['id']];
                                    if ($vrednost != '') {
                                        //sisplet_query("REPLACE INTO srv_data_rating (spr_id, vre_id, usr_id, vrstni_red) VALUES ('$row[id]', '$row1[id]', '".get('usr_id')."', '$vrednost')");
                                        $srv_data_rating .= "('$row[id]', '$row1[id]', '" . get('usr_id') . "', '$vrednost', $loop_id),";
                                        Model::user_not_lurker();
                                    } else {
                                        //sisplet_query("DELETE FROM srv_data_rating WHERE vre_id='$row1[id]' AND usr_id='".get('usr_id')."'");
                                    }
                                }			
                            }
                        } 
                    } 
                    else {
                        //sisplet_query("INSERT INTO srv_data_vrednost".get('db_table')." (spr_id, vre_id, usr_id) VALUES ('$row[id]', '-2', '".get('usr_id')."')");
                        $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }


                    // vsota
                } elseif ($row['tip'] == 18) {

                    if ($_POST['visible_' . $row['id']] == 1) {

                        $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]' AND vrstni_red>0 ORDER BY vrstni_red");
                        while ($row1 = mysqli_fetch_array($sql1)) {

                            if (isset($_POST['spremenljivka_' . $row['id'] . '_vrednost_' . $row1['id']])) {

                                $vrednost = $_POST['spremenljivka_' . $row['id'] . '_vrednost_' . $row1['id']];
                                if ($vrednost != '') {
                                    //$s = sisplet_query("REPLACE INTO srv_data_text (spr_id, vre_id, usr_id, text) VALUES ('$row[id]', '$row1[id]', '".get('usr_id')."', '$vrednost')");
                                    $srv_data_text .= "('$row[id]', '$row1[id]', '$vrednost', '', '" . get('usr_id') . "', $loop_id),";
                                    Model::user_not_lurker();
                                }
                            }
                        }

                    } else {
                        $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }
                } //imena
                elseif ($row['tip'] == 9) {

                    if ($_POST['visible_' . $row['id']] == 1) {

                        $empty = true;

                        $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[id]' ORDER BY vrstni_red ASC");

                        $vrednost = $_POST['spremenljivka_' . $row['id']];

                        // ce imamo 1 textarea dobimo 1 string, ki ga moremo razbit na posamezna imena (locena z entri)
                        if ($row['design'] == 2) {
                            $vrednost = preg_replace('!(\r?\n){2,}!', '\r\n', $vrednost);
                            $vrednost = explode('\r\n', $vrednost);
                        }

                        foreach ($vrednost AS $key => $val) {

                            $row1 = mysqli_fetch_array($sql1);
                            //sisplet_query("INSERT INTO srv_data_text (spr_id, vre_id, text, usr_id) VALUES ('$row[id2]', '$row1[id]', 'aaa', '".get('usr_id')."')");

                            if ($val != '') {
                                //sisplet_query("INSERT INTO srv_data_text (spr_id, vre_id, text, usr_id) VALUES ('$row[id]', '$row1[id]', '$val', '".get('usr_id')."')");
                                $srv_data_text .= "('$row[id]', '$row1[id]', '$val', '', '" . get('usr_id') . "', $loop_id),";
                                Model::user_not_lurker();
                                $empty = false;
                            }
                        }

                        if ($empty) {
                            //sisplet_query("INSERT INTO srv_data_vrednost".get('db_table')." (spr_id, vre_id, usr_id) VALUES ('$row[id]', '-1', '".get('usr_id')."')");
                            //$srv_data_vrednost .= "('$row[id]', '-1', '".get('usr_id')."'),";
                        }

                    } else {
                        //sisplet_query("INSERT INTO srv_data_vrednost".get('db_table')." (spr_id, vre_id, usr_id) VALUES ('$row[id]', '-2', '".get('usr_id')."')");
                        $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }
                    
                } //Map - Lokacija
                elseif ($row['tip'] == 26) {
                    //get type of map - 1=my location, 2=multilocation
                    $enota = $row['enota'];
                    
                    if ($_POST['visible_' . $row['id']] == 1) {

                        //get input type - marker, polyline, polygon
                        $spremenljivkaParams = new enkaParameters($row['params']);

                        $input = $spremenljivkaParams->get('multi_input_type'); 

                        foreach ($_POST['vrednost_' . $row['id']] AS $key => $val) {

                                $data = explode("|", $val);

                                //izberi lokacijo
                                if($enota == 3){
                                    $je_odgovoril = (isset($_POST[$data[0] . '_text']) && $_POST[$data[0] . '_text'] != '');
                                    $srv_data_map .= "(" . get('usr_id') . ", '$row[id]', $loop_id, $data[0], ". get('anketa') . ", '', '', '', '".
                                            ($je_odgovoril ? $_POST[$data[0] . '_text'] : '-1')."', ''),";
                                    if($je_odgovoril)
                                        Model::user_not_lurker();
                                }
                                else{
                                    //linija ali poligon
                                    if($enota == 2 && $input != 'marker'){
                                        $srv_data_map .= "(" . get('usr_id') . ", '$row[id]', $loop_id, NULL, ". get('anketa') . ", '$data[1]', '$data[2]', '', '', '$data[0]'),";
                                    }
                                    //marker
                                    else{
                                        //nastavi odgovor na podvprasanje
                                        if(isset($_POST[$data[0] . '_text']))
                                            $marker_text = $_POST[$data[0] . '_text'] != '' ? $_POST[$data[0] . '_text'] : '-1';
                                        else
                                            $marker_text = '-4';

                                        $srv_data_map .= "(" . get('usr_id') . ", '$row[id]', $loop_id, NULL, ". get('anketa') . ", '$data[1]', '$data[2]', '$data[3]', '".
                                                $marker_text."', ''),";                                            
                                    }
                                    Model::user_not_lurker();
                                }
                        }
                    }
                    else {
                        //sisplet_query("INSERT INTO srv_data_map (usr_id, spr_id, loop_id, ank_id, lat, lng, address, text) "
                        //    . "VALUES (" . get('usr_id') . ", '$row[id]', $loop_id, ". get('anketa') . ", '-2', '-2', '-2', '-2')");
                        //if($enota == 3)
                            $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                        //else
                            //$srv_data_map .= "(" . get('usr_id') . ", '$row[id]', $loop_id, NULL, ". get('anketa') . ", '-2', '-2', '-2', '-2', ''),";
                    }
                } //HeatMap
                elseif ($row['tip'] == 27) {
                                            
                    if ($_POST['visible_' . $row['id']] == 1) {
                        //za koordinate
                        foreach ($_POST['vrednost_' . $row['id']] AS $key => $val) {

                                $data = explode("|", $val);

                                $srv_data_heatmap .= "(" . get('usr_id') . ", '$row[id]', $loop_id, NULL, ". get('anketa') . ", '$data[1]', '$data[2]', '$data[3]', '".
                                        (isset($_POST[$data[0] . '_text']) ? $_POST[$data[0] . '_text'] : '-2')."', ''),";
                                Model::user_not_lurker();
                                $empty = false;
                        }//za koordinate - konec
                        
                        //za checkbox
                        foreach ($_POST['vrednostHeatmap_' . $row['id']] AS $key => $val) {
                                
                                if ($val > 0) {
                                    //sisplet_query("INSERT INTO srv_data_vrednost".get('db_table')." (spr_id, vre_id, usr_id) VALUES ('$row[id]', '$val', '".get('usr_id')."')");
                                    $srv_data_vrednost .= "('$row[id]', '$val', '" . get('usr_id') . "', $loop_id),";
                                    Model::user_not_lurker();
                                    if (isset($_POST['textfield_' . $val]) && $_POST['textfield_' . $val] != '')
                                        //sisplet_query("INSERT INTO srv_data_text (spr_id, vre_id, text, usr_id) VALUES ('$row[id]', '$val', '".$_POST['textfield_'.$val]."', '".get('usr_id')."')");
                                        $srv_data_text .= "('$row[id]', '$val', '" . $_POST['textfield_' . $val] . "', '', '" . get('usr_id') . "', $loop_id),";
                                }
                        }//za checkbox - konec
                        
                        // če imamo if na vprašanjij, imamo v arrayu vrednosti katere so -2
                        if (isset($_POST['cond_vrednost_' . $row['id']])) {
                            $vrednost = $_POST['cond_vrednost_' . $row['id']];
                            foreach ($vrednost AS $key => $val) {
                                if ($val > 0) {
                                    //sisplet_query("INSERT INTO srv_data_vrednost".get('db_table')." (spr_id, vre_id, usr_id) VALUES ('$row[id]', '$val', '".get('usr_id')."')");
                                    $srv_data_vrednost_cond .= "('$row[id]', '$val', '-2', '" . get('usr_id') . "', $loop_id),";
                                }
                            }
                        }
                    }
                    else {
                        //sisplet_query("INSERT INTO srv_data_map (usr_id, spr_id, loop_id, ank_id, lat, lng, address, text) "
                        //    . "VALUES (" . get('usr_id') . ", '$row[id]', $loop_id, ". get('anketa') . ", '-2', '-2', '-2', '-2')");
                        $srv_data_heatmap .= "(" . get('usr_id') . ", '$row[id]', $loop_id, NULL, ". get('anketa') . ", '-2', '-2', '-2', '-2', ''),";
                        $srv_data_vrednost .= "('$row[id]', '-2', '" . get('usr_id') . "', $loop_id),";
                    }						
                }
            }

        } // -- while
        
        //na koncu preverimo dodatna vprasanja pri glasovanju (spol)
        if ($rowa['survey_type'] == 0) {

            if (mysqli_num_rows($sql) > 0)
                mysqli_data_seek($sql, 0);
            $row = mysqli_fetch_array($sql);

            $sqlG = sisplet_query("SELECT spol FROM srv_glasovanje WHERE spr_id = '$row[id]'");
            $rowG = mysqli_fetch_array($sqlG);

            $sql4 = sisplet_query("SELECT id FROM srv_spremenljivka WHERE vrstni_red='2' AND gru_id='$row[gru_id]' ");
            $row4 = mysqli_fetch_array($sql4);

            //ce je nastavljena nastavitev za vprasanje o spolu
            if ($rowG['spol'] == 1) {
                $spol = $_POST['submit'];
                if ($spol == "Moški") {
                    $vrednost = 1;

                    $sql5 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$row4[id]' AND vrstni_red='1' ");
                    $row5 = mysqli_fetch_array($sql5);
                } elseif ($spol == "Ženska") {
                    $vrednost = 2;

                    $sql5 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$row4[id]' AND vrstni_red='2' ");
                    $row5 = mysqli_fetch_array($sql5);
                } else
                    $vrednost = 0;

                if ($vrednost > 0) {
                    sisplet_query("INSERT INTO srv_data_vrednost" . get('db_table') . " (spr_id, vre_id, usr_id) VALUES ('$row4[id]', '$row5[id]', '" . get('usr_id') . "')");
                    sisplet_query("INSERT INTO srv_data_glasovanje (spr_id, usr_id, spol) VALUES ('$row[id]', '" . get('usr_id') . "', '$vrednost')");

                    Model::user_not_lurker();
                } else {
                    //sisplet_query("INSERT INTO srv_data_glasovanje (spr_id, usr_id, spol) VALUES ('$row[id]', '".get('usr_id')."', '-1')");
                }
            }
        }
        
        // Za evoli teammeter moramo na prvi strani posebej shraniti department
        if( (SurveyInfo::getInstance()->checkSurveyModule('evoli_teammeter') 
                || SurveyInfo::getInstance()->checkSurveyModule('evoli_quality_climate') 
                || SurveyInfo::getInstance()->checkSurveyModule('evoli_teamship_meter') 
                || SurveyInfo::getInstance()->checkSurveyModule('evoli_organizational_employeeship_meter')
            ) 
            && isset($_POST['evoli_tm_department'])
        ){
            
            $sqlTMD = sisplet_query("SELECT * FROM srv_evoli_teammeter_data_department WHERE usr_id='".get('usr_id')."'");
            
            if(mysqli_num_rows($sqlTMD) == 0)
                sisplet_query("INSERT INTO srv_evoli_teammeter_data_department (department_id, usr_id) VALUES ('".$_POST['evoli_tm_department']."', '".get('usr_id')."')");
            else
                sisplet_query("UPDATE srv_evoli_teammeter_data_department SET department_id='".$_POST['evoli_tm_department']."' WHERE usr_id='".get('usr_id')."'");
        }


        save('cache_srv_data_grid', $srv_data_grid);
        save('cache_srv_data_vrednost', $srv_data_vrednost);
        save('cache_srv_data_text', $srv_data_text);
        save('cache_srv_data_checkgrid', $srv_data_checkgrid);
        save('cache_srv_data_textgrid', $srv_data_textgrid);
        save('cache_srv_data_rating', $srv_data_rating);
        save('cache_srv_data_vrednost_cond', $srv_data_vrednost_cond);
        save('cache_srv_data_map', $srv_data_map);
        save('cache_srv_data_heatmap', $srv_data_heatmap);

        // ce ni preskocena stran, dodamo v bazo (pri preskoceni bomo dodal posebej in kasnej)
        if ($preskocena == 0)
            $this->posted_commit();

        // pri preskoceni strani podatke shranimo z posted_commit(), ki se klice izven te funkcije, kjer loopamo cez strani
    }

    /**
     * v funkciji posted() nastavimo cache spremenljivke, ki jih sedaj vse naenkrat shranimo v bazo
     * pri preskoku strani kasneje posebej shranimo podatke v bazo (optimizacija, da se ne shranjujejo vsakic sproti v zanki)
     *
     */
    public function posted_commit()
    {
        //sisplet_query("COMMIT");
        # če smo samo v predogledu uporabnika ne shranjujemo
        if (get('quick_view') == true) {
            return false;
        }


        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        // odrezemo zadnjo vejico, ker smo jo dodajali kar povsod
        $delete = substr(get('cache_delete'), 0, -1);

        if ($delete != '') {
            $s = sisplet_query("DELETE FROM srv_data_grid" . get('db_table') . " WHERE spr_id IN ($delete) AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
            if (!$s) {
                echo 'err01: ' . mysqli_error($GLOBALS['connect_db']);
            }
            $s = sisplet_query("DELETE FROM srv_data_vrednost" . get('db_table') . " WHERE spr_id IN ($delete) AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
            if (!$s) {
                echo 'err02: ' . mysqli_error($GLOBALS['connect_db']);
            }
            $s = sisplet_query("DELETE FROM srv_data_text" . get('db_table') . " WHERE spr_id IN ($delete) AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
            if (!$s) {
                echo 'err03: ' . mysqli_error($GLOBALS['connect_db']);
            }
            $s = sisplet_query("DELETE FROM srv_data_checkgrid" . get('db_table') . " WHERE spr_id IN ($delete) AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
            if (!$s) {
                echo 'err04: ' . mysqli_error($GLOBALS['connect_db']);
            }
            $s = sisplet_query("DELETE FROM srv_data_textgrid" . get('db_table') . " WHERE spr_id IN ($delete) AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
            if (!$s) {
                echo 'err05: ' . mysqli_error($GLOBALS['connect_db']);
            }
            $s = sisplet_query("DELETE FROM srv_data_rating WHERE spr_id IN ($delete) AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
            if (!$s) {
                echo 'err06: ' . mysqli_error($GLOBALS['connect_db']);
            }
            $s = sisplet_query("DELETE FROM srv_data_map WHERE spr_id IN ($delete) AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
            if (!$s) {
                echo 'err07: ' . mysqli_error($GLOBALS['connect_db']);
            }
			$s = sisplet_query("DELETE FROM srv_data_heatmap WHERE spr_id IN ($delete) AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
            if (!$s) {
                echo 'err08: ' . mysqli_error($GLOBALS['connect_db']);
            }
            //$s = sisplet_query("DELETE FROM srv_data_glasovanje WHERE spr_id IN ($delete) AND usr_id='".get('usr_id')."'");					if (!$s) echo 'err07: '.mysqli_error($GLOBALS['connect_db']);
        }

        // odrezemo zadnjo vejico, ker smo jo dodajali kar povsod
        $srv_data_grid = substr(get('cache_srv_data_grid'), 0, -1);
        $srv_data_vrednost = substr(get('cache_srv_data_vrednost'), 0, -1);
        $srv_data_text = substr(get('cache_srv_data_text'), 0, -1);
        $srv_data_checkgrid = substr(get('cache_srv_data_checkgrid'), 0, -1);
        $srv_data_textgrid = substr(get('cache_srv_data_textgrid'), 0, -1);
        $srv_data_rating = substr(get('cache_srv_data_rating'), 0, -1);
        $srv_data_vrednost_cond = substr(get('cache_srv_data_vrednost_cond'), 0, -1);
        $srv_data_map = substr(get('cache_srv_data_map'), 0, -1);
        $srv_data_heatmap = substr(get('cache_srv_data_heatmap'), 0, -1);

        if ($srv_data_grid != '') {
            $s = sisplet_query("INSERT INTO srv_data_grid" . get('db_table') . " (spr_id, vre_id, usr_id, grd_id, loop_id) VALUES $srv_data_grid");
            if (!$s) {
                echo 'err11: ' . mysqli_error($GLOBALS['connect_db']);
                die();
            }
        }
        if ($srv_data_vrednost != '') {
            $s = sisplet_query("INSERT INTO srv_data_vrednost" . get('db_table') . " (spr_id, vre_id, usr_id, loop_id) VALUES $srv_data_vrednost");
            if (!$s) {
                echo 'err12: ' . mysqli_error($GLOBALS['connect_db']);
                die();
            }
        }
        if ($srv_data_text != '') {
            $s = sisplet_query("INSERT INTO srv_data_text" . get('db_table') . " (spr_id, vre_id, text, text2, usr_id, loop_id) VALUES $srv_data_text");
            if (!$s) {
                echo 'err13: ' . mysqli_error($GLOBALS['connect_db']);
                die();
            }
        }
        if ($srv_data_checkgrid != '') {
            $s = sisplet_query("INSERT INTO srv_data_checkgrid" . get('db_table') . " (spr_id, vre_id, usr_id, grd_id, loop_id) VALUES $srv_data_checkgrid");
            if (!$s) {
                echo 'err14: ' . mysqli_error($GLOBALS['connect_db']);
                die();
            }
        }
        if ($srv_data_textgrid != '') {
            $s = sisplet_query("INSERT INTO srv_data_textgrid" . get('db_table') . " (spr_id, vre_id, usr_id, grd_id, text, loop_id) VALUES $srv_data_textgrid");
            if (!$s) {
                echo 'err15: ' . mysqli_error($GLOBALS['connect_db']);
                die();
            }
        }
        if ($srv_data_rating != '') {
            $s = sisplet_query("INSERT INTO srv_data_rating (spr_id, vre_id, usr_id, vrstni_red, loop_id) VALUES $srv_data_rating");
            if (!$s) {
                echo 'err16: ' . mysqli_error($GLOBALS['connect_db']);
                die();
            }
        }
        if ($srv_data_vrednost_cond != '') {
            $s = sisplet_query("INSERT INTO srv_data_vrednost_cond (spr_id, vre_id, text, usr_id, loop_id) VALUES $srv_data_vrednost_cond");
            if (!$s) {
                echo 'err17: ' . mysqli_error($GLOBALS['connect_db']);
                die();
            }
        }
        if ($srv_data_map != '') {
            $s = sisplet_query("INSERT INTO srv_data_map (usr_id, spr_id, loop_id, vre_id, ank_id, lat, lng, address, text, vrstni_red) VALUES $srv_data_map");
            if (!$s) {
                echo 'err18: ' . mysqli_error($GLOBALS['connect_db']);
                die();
            }
        }
        if ($srv_data_heatmap != '') {
            $s = sisplet_query("INSERT INTO srv_data_heatmap (usr_id, spr_id, loop_id, vre_id, ank_id, lat, lng, address, text, vrstni_red) VALUES $srv_data_heatmap");
            if (!$s) {
                echo 'err19: ' . mysqli_error($GLOBALS['connect_db']);
                die();
            }
        }

        // Posebej shranimo password iz cookija, ker po novem se password za dostop do ankete shrani v spremenljivko
        // Ce imamo password ga shranimo kot odgovor
        if (isset($_COOKIE['password_' . get('anketa')])) {
            $password = $_COOKIE['password_' . get('anketa')];

            $sql1 = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='" . get('anketa') . "' AND s.gru_id=g.id AND s.skupine='2'");
            if (get('usr_id') != null && mysqli_num_rows($sql1) > 0) {

                $row1 = mysqli_fetch_array($sql1);

                $sql2 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row1[id]' AND naslov='$password'");
                $row2 = mysqli_fetch_array($sql2);
                $s = sisplet_query("DELETE FROM srv_data_vrednost" . get('db_table') . " WHERE spr_id='$row1[id]' AND usr_id='" . get('usr_id') . "'");
                if (!$s) echo mysqli_error($GLOBALS['connect_db']);
                $data = $row2['id'];
                $s = sisplet_query("INSERT INTO srv_data_vrednost" . get('db_table') . " (spr_id, vre_id, usr_id) VALUES ('$row1[id]', '$data', '" . get('usr_id') . "')");
                if (!$s) echo 'err6543' . mysqli_error($GLOBALS['connect_db']);
            }
        }

        //sisplet_query("BEGIN");
    }

	
    /**
     * shrani sistemske spremenljivke podane preko urlja v bazo
     *
     */
    public static function saveSistemske()
    {
        global $lang;

        $url_params = array();
        $usr_id = get('usr_id');

        /*
		 * UL evalvacija - sistemske so zakodirane z url_encode in base64_encode - naceloma se lahko uporabi povsod kjer se rabi kodiran url
		 * Dekodiranje tudi samoevalvacijske hierarhije
		 */
        if (isset( $_GET['enc'] )) {

            // Preberemo parametre in jih dekodiramo
            $request_encoded = $_GET['enc'];
            $request = base64_decode(urldecode($request_encoded));

            // Parametre shranimo v nov array
            $request_array = array();
            parse_str($request, $request_array);

            // Shranimo vse parametre
            $url_params = array_merge($_GET, $request_array);

        } else
            $url_params = $_GET;

		
		// Pri studentski evalvaciji posebej shranimo sifro studenta ce jo imamo (zaradi anonimnosti v posebno tabelo, ki jo potem pobrisemo)
		if(Common::checkModule('evalvacija') == '1' && isset($url_params['sifstud'])){
			
			$student = $url_params['sifstud'];
			
			$s = sisplet_query("INSERT INTO eval_data_user (student, ank_id, usr_id) VALUES ('".$student."', '".get('anketa')."', '".get('usr_id')."')");
            if (!$s) echo 'err9988' . mysqli_error($GLOBALS['connect_db']);
		}
		

        // preverimo ce so vrednosti kaksne sistemske spremenljivke podane v urlju in jih shranimo
        $sql1 = sisplet_query("SELECT s.id, s.variable, s.skupine, s.tip FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='" . get('anketa') . "' AND s.gru_id=g.id AND s.sistem='1' AND (s.tip='4' OR s.tip='21' OR s.tip='1' OR s.tip='3')");
        while ($row1 = mysqli_fetch_array($sql1)) {

            if ($row1['id'] > 0 && (isset($url_params[$row1['variable']]) || $row1['skupine'] == 3)) {

                if ($usr_id != null) {
                    if ($row1['tip'] == 4) {
						
                        $s = sisplet_query("DELETE FROM srv_data_text" . get('db_table') . " WHERE spr_id='$row1[id]' AND usr_id='" . get('usr_id') . "'");
                        if (!$s) echo mysqli_error($GLOBALS['connect_db']);
                        
						$data = $url_params[$row1['variable']];
                       
					   $s = sisplet_query("INSERT INTO srv_data_text" . get('db_table') . " (spr_id, text, usr_id) VALUES ('$row1[id]', '$data', '" . get('usr_id') . "')");
                        if (!$s) echo 'err3455' . mysqli_error($GLOBALS['connect_db']);
						
                    } else if ($row1['tip'] == 21) {
						
                        $sql2 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row1[id]' ORDER BY vrstni_red");
                        $row2 = mysqli_fetch_array($sql2);
                        
						$s = sisplet_query("DELETE FROM srv_data_text" . get('db_table') . " WHERE spr_id='$row1[id]' AND usr_id='" . get('usr_id') . "'");
                        if (!$s) echo mysqli_error($GLOBALS['connect_db']);
                        
						$data = $url_params[$row1['variable']];
                        
						$s = sisplet_query("INSERT INTO srv_data_text" . get('db_table') . " (spr_id, vre_id, text, usr_id) VALUES ('$row1[id]', '$row2[id]', '$data', '" . get('usr_id') . "')");
                        if (!$s) echo 'err6543' . mysqli_error($GLOBALS['connect_db']);
						
                    } else {
						
                        $s = sisplet_query("DELETE FROM srv_data_vrednost" . get('db_table') . " WHERE spr_id='$row1[id]' AND usr_id='" . get('usr_id') . "'");
                        if (!$s) echo mysqli_error($GLOBALS['connect_db']);

                        $data = $url_params[$row1['variable']];

                        // Pri jeziku nimamo nastavljen id vrednosti ampak lang_id
                        if ($row1['skupine'] == 3) {
                            $naslov = $lang['language'];

                            // Noce prjet zaradi čšž-jev tko da je to se najlazje:)
                            if (strcmp($naslov, 'Sloven&#353;&#269;ina') == 0)
                                $sql3 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row1[id]' AND naslov='Slovenščina'");
                            else
                                $sql3 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row1[id]' AND naslov='" . $naslov . "'");

                            $row3 = mysqli_fetch_array($sql3);
                            $data = $row3['id'];
                        }
						
						// Pri skupinah imamo lahko nastavljeno vrednost in ne id-ja
                        if ($row1['skupine'] == 1) {
                            $sql3 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row1[id]' AND naslov='" . $data . "'");

							if(mysqli_num_rows($sql3) > 0){
								$row3 = mysqli_fetch_array($sql3);
								$data = $row3['id'];
							}
                        }

                        $s = sisplet_query("INSERT INTO srv_data_vrednost" . get('db_table') . " (spr_id, vre_id, usr_id) VALUES ('$row1[id]', '$data', '" . get('usr_id') . "')");
                        if (!$s) echo 'err6543' . mysqli_error($GLOBALS['connect_db']);
                    }
                }
            }
        }
    }

    public function savePostedSpecialVars($row)
    {
        $res = false;
        $allowedTips = array(4, 7, 8); // text, number, datum
        if (in_array($row['tip'], $allowedTips)) {
            // text
            if ($_POST['visible_' . $row['id']] == 1) {
                if (isset($_POST['vrednost_' . $row['id'] . '_other'])) {
                    $vrednost = $_POST['vrednost_' . $row['id'] . '_other'];
                    foreach ($vrednost AS $key => $val) {
                        if ($val > 0) {
                            sisplet_query("INSERT INTO srv_data_vrednost" . get('db_table') . " (spr_id, vre_id, usr_id) VALUES ('$row[id]', '$val', '" . get('usr_id') . "')");
                            $res = true;
                        }
                    }
                }
            }// endif visible
        } //endif tip
        return $res;
    }
	
}