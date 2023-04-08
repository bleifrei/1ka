<?php

// tekstovna vsebina
// V SETTINGS.PHP DODAJ SYNC_SERVER_PATH!!!!!

include_once ('../function.php');
include_once ('../admin/survey/definition.php');
include_once ('../vendor/autoload.php');
include_once ('../settings.php');

$struktura = array();
$podatki = array(
    'srv_user' => array(), 
    'srv_data_checkgrid_archive1' => array(), 
    'srv_data_checkgrid_archive2' => array(), 
    'srv_data_checkgrid_active' => array(), 
    'srv_data_glasovanje' => array(), 
    'srv_data_grid_archive1' => array(), 
    'srv_data_grid_archive2' => array(), 
    'srv_data_grid_active' => array(), 
    'srv_data_rating' => array(), 
    'srv_data_text_archive1' => array(), 
    'srv_data_text_archive2' => array(), 
    'srv_data_text_active' => array(), 
    'srv_data_textgrid_archive1' => array(), 
    'srv_data_textgrid_archive2' => array(), 
    'srv_data_textgrid_active' => array(), 
    'srv_data_upload' => array(),
    'srv_data_vrednost_archive1' => array(), 
    'srv_data_vrednost_archive2' => array(), 
    'srv_data_vrednost_active' => array(), 
    'srv_user_grupa_archive1' => array(), 
    'srv_user_grupa_archive2' => array(), 
    'srv_user_grupa_active' => array());


// You can use the desired folder to check and comment the others.
// foreach (glob("../downloads/*") as $path) { // lists all files in sub-folder called "downloads"

$obdelani = array();

$uvozeno = false;

if ($handle = opendir($sync_server_path)) {

    foreach (glob($sync_server_path ."*.1ka") as $path) { // lists all files in folder called "test"
        $datoteke[$path] = filectime($path);
    } arsort($datoteke);

    foreach ($datoteke as $file => $timestamp) {
            $raw = file_get_contents($file);
            $vsebina = unserialize($raw);
            
            $num = 0;
            
            foreach ($vsebina as $machine_ID => $kljuc) {
                
                
                // imam ime in secret v machine_id.
                $terminal = explode ("||~||", $machine_ID);
                
                // preverim, če sem to anketo + ta računalnik že delal - če nisem, grem delati.
                if (!in_array($terminal[0] ."-" .$kljuc['srv_anketa'][0]['id'], $obdelani)) {
                    array_push($obdelani, $terminal[0] ."-" .$kljuc['srv_anketa'][0]['id']);
                    // Porihtaj naslov tako da bo "Fieldwork: anketa datum URA";

                    // preverim če je secret OK...
                    $result = sisplet_query ("SELECT sid_server, id FROM srv_fieldwork WHERE secret='" .$terminal[1] ."' AND terminal_id='" .$terminal[0] ."' AND sid_terminal='" .$kljuc['srv_anketa'][0]['id'] ."' AND sid_server='" .$_GET['srv_id'] ."'");
                    if ($r = mysqli_fetch_row ($result)) {

                        if (count($struktura) == 0) {
                            
                            $num = substr_count($raw, '"recnum"');

                            mysql_query ("UPDATE srv_fieldwork SET lastnum='$num' WHERE id='" .$r[1] ."'");
                            
        //                    $struktura['version'] = $kljuc['version'];
                            $struktura['version'] = time();
                            $struktura['srv_anketa'] = $kljuc['srv_anketa'];
                            // tole pa popravi (naslov)
                            $struktura['srv_anketa'][0]['naslov'] = "Fieldwork: " .$kljuc['srv_anketa'][0]['naslov'] .' ' .date('H:i:s');
                            $struktura['srv_alert'] = $kljuc['srv_alert'];
                            $struktura['srv_call_setting'] = $kljuc['srv_call_setting'];
                            $struktura['srv_dostop'] = $kljuc['srv_dostop'];
                            $struktura['srv_dostop_language'] = $kljuc['srv_dostop_language'];
                            $struktura['srv_language'] = $kljuc['srv_language'];
                            $struktura['srv_grupa'] = $kljuc['srv_grupa'];
                            $struktura['srv_spremenljivka'] = $kljuc['srv_spremenljivka'];
                            $struktura['srv_vrednost'] = $kljuc['srv_vrednost'];
                            $struktura['srv_grid'] = $kljuc['srv_grid'];
                            $struktura['srv_grid_multiple'] = $kljuc['srv_grid_multiple'];
                            $struktura['srv_language_spremenljivka'] = $kljuc['srv_language_spremenljivka'];
                            $struktura['srv_language_vrednost'] = $kljuc['srv_language_vrednost'];
                            $struktura['srv_language_grid'] = $kljuc['srv_language_grid'];
                            $struktura['srv_missing_values'] = $kljuc['srv_missing_values'];
                            $struktura['srv_calculation'] = $kljuc['srv_calculation'];
                            $struktura['srv_survey_misc'] = $kljuc['srv_survey_misc'];
                            $struktura['srv_glasovanje'] = $kljuc['srv_glasovanje'];
                            $struktura['srv_if'] = $kljuc['srv_if'];
                            $struktura['srv_condition'] = $kljuc['srv_condition'];
                            $struktura['srv_condition_grid'] = $kljuc['srv_condition_grid'];
                            $struktura['srv_condition_vre'] = $kljuc['srv_condition_vre'];
                            $struktura['srv_loop'] = $kljuc['srv_loop'];
                            $struktura['srv_loop_vre'] = $kljuc['srv_loop_vre'];
                            $struktura['srv_loop_data'] = $kljuc['srv_loop_data'];
                            $struktura['srv_branching'] = $kljuc['srv_branching'];
                            $struktura['data'] = true;
                        }

                        $podatki['srv_user'] = array_merge($podatki['srv_user'], $kljuc['srv_user']);
                        $podatki['srv_data_checkgrid_archive1'] = array_merge($podatki['srv_data_checkgrid_archive1'], $kljuc['srv_data_checkgrid_archive1']);
                        $podatki['srv_data_checkgrid_archive2'] = array_merge($podatki['srv_data_checkgrid_archive2'], $kljuc['srv_data_checkgrid_archive2']);
                        $podatki['srv_data_checkgrid_active'] = array_merge($podatki['srv_data_checkgrid_active'], $kljuc['srv_data_checkgrid_active']);
                        $podatki['srv_data_glasovanje'] = array_merge($podatki['srv_data_glasovanje'], $kljuc['srv_data_glasovanje']);
                        $podatki['srv_data_grid_archive1'] = array_merge($podatki['srv_data_grid_archive1'], $kljuc['srv_data_grid_archive1']);
                        $podatki['srv_data_grid_archive2'] = array_merge($podatki['srv_data_grid_archive2'], $kljuc['srv_data_grid_archive2']);
                        $podatki['srv_data_grid_active'] = array_merge($podatki['srv_data_grid_active'], $kljuc['srv_data_grid_active']);
                        $podatki['srv_data_rating'] = array_merge($podatki['srv_data_rating'], $kljuc['srv_data_rating']);
                        $podatki['srv_data_text_archive1'] = array_merge($podatki['srv_data_text_archive1'], $kljuc['srv_data_text_archive1']);
                        $podatki['srv_data_text_archive2'] = array_merge($podatki['srv_data_text_archive2'], $kljuc['srv_data_text_archive2']);
                        $podatki['srv_data_text_active'] = array_merge($podatki['srv_data_text_active'], $kljuc['srv_data_text_active']);
                        $podatki['srv_data_textgrid_archive1'] = array_merge($podatki['srv_data_textgrid_archive1'], $kljuc['srv_data_textgrid_archive1']);
                        $podatki['srv_data_textgrid_archive2'] = array_merge($podatki['srv_data_textgrid_archive2'], $kljuc['srv_data_textgrid_archive2']);
                        $podatki['srv_data_textgrid_active'] = array_merge($podatki['srv_data_textgrid_active'], $kljuc['srv_data_textgrid_active']);
                        $podatki['srv_data_upload'] = array_merge($podatki['srv_data_upload'], $kljuc['srv_data_upload']);
                        $podatki['srv_data_vrednost_archive1'] = array_merge($podatki['srv_data_vrednost_archive1'], $kljuc['srv_data_vrednost_archive1']);
                        $podatki['srv_data_vrednost_archive2'] = array_merge($podatki['srv_data_vrednost_archive2'], $kljuc['srv_data_vrednost_archive2']);
                        $podatki['srv_data_vrednost_active'] = array_merge($podatki['srv_data_vrednost_active'], $kljuc['srv_data_vrednost_active']);
                        $podatki['srv_user_grupa_archive1'] = array_merge($podatki['srv_user_grupa_archive1'], $kljuc['srv_user_grupa_archive1']);
                        $podatki['srv_user_grupa_archive2'] = array_merge($podatki['srv_user_grupa_archive2'], $kljuc['srv_user_grupa_archive2']);
                        $podatki['srv_user_grupa_active'] = array_merge($podatki['srv_user_grupa_active'], $kljuc['srv_user_grupa_active']);

                        $uvozeno = true;
                    }
                }
            }
    }
}

if ($uvozeno == true) {

        $skupno = array_merge ($struktura, $podatki);

        SurveyCopy::setSrcSurvey(-1);
        SurveyCopy::setSrcConectDb($connect_db);
        SurveyCopy::setDestSite(0);

        SurveyCopy::setSourceArray($skupno);

        $new_srv_id = SurveyCopy::doCopy();
        
        header ('location: ' .$site_url .'admin/survey/index.php?anketa=' .$_GET['srv_id'] .'&a=fieldwork&n=' .$new_srv_id);
}
else {
        header ('location: ' .$site_url .'admin/survey/index.php?anketa=' .$_GET['srv_id'] .'&a=fieldwork&n=0');
}

