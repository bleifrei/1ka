<?php
/** 
 *  June 2016
 * 
 * Pridobi podatke o markerjih, za za njihov prikaz v mapi v podatkih in analizah
 * 
 * @author Uros Podkriznik
 */
class SurveyMapData
{	
    function __construct() {
    }

    //podatki o markerjih za vsakega userja posebej
    function mapData() {
        if(isset($_POST['map_data'])) {
            $json = $_POST['map_data'];
            
            //get multi_input_type (marker, polyline, polygon)
            $row = Cache::srv_spremenljivka($json['spr_id']);

            $newParams = new enkaParameters($row['params']);
            $input = $newParams->get('multi_input_type');
            $marpod = $newParams->get('marker_podvprasanje'); //ali dodam podvprasanje v infowindow
            $naslovpod = $newParams->get('naslov_podvprasanja_map'); //dobi naslov podvprasanja mape

            /*
             * tukaj se pogleda, ali ima user nastavljeno na prikaz vseh enot (1) ali samo ustrezne (2)
            global $global_user_id;
            
            SurveyUserSetting :: getInstance()->Init($json['ank_id'], $global_user_id);
            $currentProfileId = SurveyUserSetting :: getInstance()->getSettings('default_status_profile_'.A_ANALYSIS);
            error_log(json_encode($currentProfileId));  */  
            
            if($row['enota'] == 3){
                $sql1 = sisplet_query("SELECT vm.vre_id, vm.lat, vm.lng, vm.address, v.naslov FROM srv_vrednost AS v 
                LEFT JOIN srv_vrednost_map AS vm ON v.id = vm.vre_id
                WHERE v.spr_id='".$json['spr_id']."'", 'array');

                //je vec vrednosti
                if(!isset($sql1['lat']))
                    $map_data['data'] = $sql1;
                //je ena vrednost
                else
                    $map_data['data'][] = $sql1;
                
                //get info shapes
                $sql2 = sisplet_query("SELECT lat, lng, address, overlay_id FROM srv_vrednost_map 
                    WHERE spr_id='".$json['spr_id']."' AND overlay_type='polyline' ORDER BY overlay_id, vrstni_red", 'array');

                $map_data_info_shapes = array();
                        
                //create json data for info shapes
                $st_linij=0;
                $last_id=0;
                foreach ($sql2 as $line_row) {
                    if($line_row['overlay_id'] != $last_id){
                        $st_linij++;
                        $last_id = $line_row['overlay_id'];
                        $map_data_info_shapes[$st_linij-1]['overlay_id']=$line_row['overlay_id'];
                        $map_data_info_shapes[$st_linij-1]['address']=$line_row['address'];
                        $map_data_info_shapes[$st_linij-1]['path']= array();
                    }

                    $path = array();
                    $path['lat']=floatval($line_row['lat']);
                    $path['lng']=floatval($line_row['lng']);

                    array_push($map_data_info_shapes[$st_linij-1]['path'], $path);
                }
                $map_data['info_shapes'] = $map_data_info_shapes;
            }
            else{
                if($input == 'marker')
                    $str_query = "SELECT REPLACE(REPLACE(REPLACE(sdm.address,'\n',' '),'\r',' '),'|',' ') as address, "
                        . "REPLACE(REPLACE(REPLACE(sdm.text,'\n',' '),'\r',' '),'|',' ') as text, sdm.lat, sdm.lng "
                        . "FROM srv_data_map AS sdm JOIN srv_user AS u ON sdm.usr_id = u.id WHERE u.deleted = '0' AND sdm.spr_id = ". $json['spr_id'];
                else
                    $str_query = "SELECT sdm.lat, sdm.lng, sdm.usr_id "
                        . "FROM srv_data_map AS sdm JOIN srv_user AS u ON sdm.usr_id = u.id WHERE u.deleted = '0' AND sdm.spr_id = ". $json['spr_id'];

                if($json['usr_id'] != '-1')
                    $str_query.=" AND usr_id = ". $json['usr_id'];

                if($json['loop_id'] != '0' && $json['loop_id'] != '-1')
                    $str_query.=" AND loop_id = ". $json['loop_id'];

                if($input != 'marker'){
                    $str_query.=" ORDER BY sdm.usr_id, sdm.vrstni_red";
                    $map_data1 = sisplet_query($str_query, 'array');

                    //iterate and convert all coordinates to float - needed for JS
                    /*for($i=0; $i<count($map_data['data']); $i++){
                        $map_data['data'][$i]['lat'] = floatval($map_data['data'][$i]['lat']);
                        $map_data['data'][$i]['lng'] = floatval($map_data['data'][$i]['lng']);
                    }*/

                    $i=0;
                    $user_id=null;
                    foreach($map_data1 as $item)
                    {
                       if($user_id!=null && $user_id!=$item['usr_id'])
                            $i=0;

                       $user_id = $item['usr_id'];
                       //error_log(json_encode($key.' '.json_encode($item)));
                       $map_data['data'][$user_id][$i]['lat'] = floatval($item['lat']);
                       $map_data['data'][$user_id][$i]['lng'] = floatval($item['lng']);
                       $i++;
                    }
                }
                else{
                    $map_data = array();
                    $data = sisplet_query($str_query);

                    while ($row1 = mysqli_fetch_array($data)) {
                        $map_data[] = $row1;
                    }
                }
            }
            $map_data['input_type'] = $input;
            $map_data['enota'] = $row['enota'];
            $map_data['podvprasanje'] = ($marpod > 0) ? true : false;
            if($marpod > 0 && $naslovpod != '')
                $map_data['podvprasanje_naslov'] = $naslovpod;

            echo json_encode($map_data);
        }
    exit();
    }
    
    //podatki o markerjih o vseh userjih za to spremenljivko glede na filterje
    function mapDataAll() {
        if(isset($_POST['map_data'])) {
            $json = $_POST['map_data'];
            
            //nastavimo podstran, da nam naredi pravilen profileId pri kreiranju filterjev
            $_POST['podstran'] = A_ANALYSIS;

            //zazenemo in pridobimo podatke o spremenljivki
            $a = new SurveyAnalysis();
            $a->Init($json['ank_id']);
            
            $spremenljivka = $a::$_HEADERS[$json['spr_id']."_".$json['loop_id']];
                
            $_answers = $a->getAnswers($spremenljivka, -1, true);
            $_valid_answers = $_answers['valid'];

            //pridobimo predelan json pripravljen za js
            $map_data = $this->prepareMapDataAll($json['spr_id'], $_valid_answers);

            echo json_encode($map_data);

        }
        exit();
    }
    
    /**
     * 
     * @global type $lang
     * @param int $spid - id spremenljivke
     * @param array $_valid_answers - array vseh valid answers pridobljen iz SurveyAnalysis->getAnswers()
     * @return array json predelanih podatkov, pripravljenih za filanje markerjev na mapo v js
     */
    private static function prepareMapDataAll($spid, $_valid_answers){
            global $lang;
                                                                                            
                $spremenljivka = Cache::srv_spremenljivka($spid);
                $newParams = new enkaParameters($spremenljivka['params']);

                $input = $newParams->get('multi_input_type');
                $marpod = $newParams->get('marker_podvprasanje'); //ali dodam podvprasanje v infowindow
                $naslovpod = $newParams->get('naslov_podvprasanja_map'); //dobi naslov podvprasanja mape
                $enota = $spremenljivka["enota"];

                //tukaj ni choose location, zato locujemo samo med markerji ali shape
                //ali je shape
                if($input != 'marker'){
                    $map_data = array('data' => array());

                    //gremo cez vsak odgovor
                    foreach ($_valid_answers as $user_id => $row1) {
                                                
                        $new_row = array();
                        
                        //v podatkih so podatki o koordinatah deljena s <br>, zato je treba to razbit
                        $values = array_values($row1);
                        //dobimo array vseh koordinat za ta odgovor
                        $latlngs = explode('<br>',$values[0]);

                        //kreiraj array za vsak marker posebej in ga dodaj v parent array
                        for($i = 0; $i < count($latlngs); $i++){

                            //v podatkih so lat in lng deljena z vejico, razbij
                            $latlng = explode(', ',$latlngs[$i]);
                            $new_row = array("lat" => floatval($latlng[0]), "lng" => floatval($latlng[1]));

                            //filaj koordinate v array
                            if($map_data['data'][$user_id])
                                array_push($map_data['data'][$user_id], $new_row);
                            else
                                $map_data['data'][$user_id] = array($new_row);
                        }
                    }
                }
                //markerji
                else{
                    $map_data = array();

                    //gremo cez vsak odgovor
                    foreach ($_valid_answers as $row1) {
                        $new_row = array();
                        $values = array_values($row1);

                        //addres je vedno prva, razbij jo
                        $addresses = explode('<br>',$values[0]);

                        //ce marker nima nastavljene podvprasanja, sta samo address in koordinate
                        if(count($values) == 2){
                            $latlngs = explode('<br>',$values[1]);
                            $texts = null;
                        }
                        //verjetno je nastavljeno podvprasanje, zato so 3 kolumne (address, text, koordinate)
                        else{
                            $latlngs = explode('<br>',$values[2]);
                            $texts = explode('<br>',$values[1]);
                        }

                        //kreiraj array za vsak marker posebej in ga dodaj v parent array
                        for($i = 0; $i < count($addresses); $i++){

                            //v podatkih so lat in lng deljena z vejico, razbij
                            $latlng = explode(', ',$latlngs[$i]);

                            //ce je nastavljeno podvprasanje, dodaj tudi to v array
                            if($texts)
                                $new_row = array("address" => $addresses[$i], "text" => $texts[$i], "lat" => floatval($latlng[0]), "lng" => floatval($latlng[1]));
                            else
                                $new_row = array("address" => $addresses[$i], "lat" => floatval($latlng[0]), "lng" => floatval($latlng[1]));

                            array_push($map_data, $new_row);
                        }
                    }
                }

                //dodaj se osnovne info oz. parametre o spremenljivki
                $map_data['input_type'] = $input;
                $map_data['enota'] = $enota;
                $map_data['podvprasanje'] = ($marpod > 0) ? true : false;
                if($marpod > 0 && $naslovpod != '')
                    $map_data['podvprasanje_naslov'] = $naslovpod;
            
                return ($map_data);
        }
}