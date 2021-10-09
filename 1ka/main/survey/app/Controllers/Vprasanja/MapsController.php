<?php
/* * *************************************
 * Description: Maps (google maps lokacija)
 *
 * Vprašanje je prisotno:
 *  tip 26 - lokacija
 *  podtip 2 - multi lokacija
 *  podtip 1 - moja lokacija
 *
 * Autor: Uros Podkriznik
 * Created date: 22.04.2016
 * *************************************** */

namespace App\Controllers\Vprasanja;

// Osnovni razredi
use App\Controllers\Controller;
use App\Controllers\HelperController as Helper;
use App\Controllers\LanguageController as Language;
use App\Models\Model;
use enkaParameters;

/**
 * Description of MapsController
 *
 * @author uros p.
 */
class MapsController extends Controller {

    public function __construct() {
        parent::getGlobalVariables();
    }

    /*     * **********************************************
     * Get instance
     * ********************************************** */

    private static $_instance;

    public static function getInstance() {
        if (self::$_instance)
            return self::$_instance;

        return new MapsController();
    }

    public function display($spremenljivka, $oblika) {
        global $lang;
        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $spremenljivkaParams = new enkaParameters($row['params']);
        $podtip = $row['enota'];
        //$selected = Model::getOtherValue($spremenljivka);

        echo '<div id="spremenljivka_' . $spremenljivka . '_variabla" class="variabla ' . $oblika['cssFloat'] . '">' . "\n";

        //get input type - marker, polyline, polygon
        $multi_input_type = $spremenljivkaParams->get('multi_input_type');

        $map_data = array();
        $map_data_info_shapes = array();

        //ce je choose location
        if($podtip == 3){  
            
            $loop_id_3 = "";
            //force text to be null from sql
            $force_text_null = 'false';
            
            //do we have loop
            if(get('loop_id') != null){
                //does data for this loop already exist?
                $sql1 = sisplet_query("SELECT id FROM srv_data_map WHERE usr_id='" . get('usr_id') .
                        "' AND spr_id='$spremenljivka' AND loop_id $loop_id", 'array');
                    
                //data does not exist, force null on text
                if(count($sql1) == 0)
                    $force_text_null = 'true';
                else
                    $loop_id_3 = " AND dm.loop_id = '" . get('loop_id') . "'";
            }
            
            
            $sql1 = sisplet_query("SELECT vm.vre_id, vm.lat, vm.lng, vm.address, v.naslov, IF($force_text_null, NULL, dm.text) as text FROM srv_vrednost AS v 
                LEFT JOIN srv_vrednost_map AS vm ON v.id = vm.vre_id
                LEFT JOIN srv_data_map AS dm ON v.id = dm.vre_id AND dm.usr_id='" . get('usr_id') . "'
                WHERE v.spr_id='$spremenljivka' $loop_id_3", 'array');
            
            //je vec vrednosti
            if(!isset($sql1['lat']))
                $map_data = $sql1;
            //je ena vrednost
            else
                $map_data[] = $sql1;
            
            
            //get info shapes
            $sql2 = sisplet_query("SELECT lat, lng, address, overlay_id FROM srv_vrednost_map 
                WHERE spr_id='$spremenljivka' AND overlay_type='polyline' ORDER BY overlay_id, vrstni_red", 'array');

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
        }
        //ce je polygon ali polyline
        else if($podtip == 2 && $multi_input_type != '' && $multi_input_type != 'marker'){
            //ce so podatki ze v bazi (rec. uporabnik klikne 'Prejsnja stran')
            $sql1 = sisplet_query("SELECT lat, lng FROM srv_data_map WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id "
                    . "ORDER BY vrstni_red", 'array');

            //if not empty
            if(count($sql1)>0){
                $map_data = $sql1;
                
                //iterate and convert all coordinates to float - needed for JS
                for($i=0; $i<count($map_data); $i++){
                    $map_data[$i]['lat'] = floatval($map_data[$i]['lat']);
                    $map_data[$i]['lng'] = floatval($map_data[$i]['lng']);
                }
            }
        }
        //ce so markerji
        else{
            //ce so podatki ze v bazi (rec. uporabnik klikne 'Prejsnja stran')
            $sql1 = sisplet_query("SELECT lat, lng, address, text FROM srv_data_map WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id "
                    . "ORDER BY vrstni_red", 'array');
        
            //je vec vrednosti
            if(!isset($sql1['lat']))
                $map_data = $sql1;
            //je ena vrednost
            else
                $map_data[] = $sql1;
        }
        
        //dobi fokus mape
        $fokus = $spremenljivkaParams->get('fokus_mape'); 
        
        //pridobi parametre za centriranje mape in jo nastavi za kasnejso uporabo v js
        $fokus_koordinate = $spremenljivkaParams->get('fokus_koordinate'); //dobi fokus koordinat mape
        if(!isset(json_decode($fokus_koordinate)->center->lat))
            $fokus_koordinate = false;
        
        //dobi max odgovore
        $maxmark = $spremenljivkaParams->get('max_markers');
        //staro, kasneje sem dodal max_markers - to je zaradi vasjinega workshopa
        if($maxmark == '')
            $maxmark = $spremenljivkaParams->get('date_range_max');
        
        //dobi user location
        $spremenljivkaParams->get('user_location') == 1 ? $userLocation = json_encode(true) :
            $userLocation = json_encode(false);
        
        //dobi podvprasanje za marker
        $spremenljivkaParams->get('marker_podvprasanje') == 1 ? $podvprasanje = json_encode(true) :
            $podvprasanje = json_encode(false);
        
        //dobi naslov podvprasanje za marker
        $podvprasanje_naslov = $spremenljivkaParams->get('naslov_podvprasanja_map');
        if(!$spremenljivkaParams->get('naslov_podvprasanja_map'))
            $podvprasanje_naslov = '';
        
        //ali se prikaze searchbox
        $spremenljivkaParams->get('dodaj_searchbox') == 1 ? $dodaj_searchbox = json_encode(true) :
            $dodaj_searchbox = json_encode(false);
        
        //ce je tip moja lokacija
        if($podtip == 1){
            //vkljucijo se funkcije za klik na mapi
            $klikNaMapo = json_encode(true);
            //vkljuci se iskanje lokacije uporabnika
            //$userLocation = json_encode(True);
            $mojaLokacija = json_encode(True);
            if(!$fokus)
                $fokus = "Slovenia"; 
        }
        //ce je tip multi lokacija
        elseif($podtip == 2){
            //vkljuci se iskanje lokacije uporabnika
            //$userLocation = json_encode(false);
            $mojaLokacija = json_encode(false);
            if(!$fokus)
                $fokus = "Slovenia"; 
            
            if($multi_input_type != 'marker'){
                $klikNaMapo = json_encode(false);
                //izrisi opozorilo o max odgovorov
                echo '<div id="end_shape_info_'.$spremenljivka.'" style="display: inline-block; color:red; font-size:0.8em;">'.$lang['srv_resevanje_info_end_shpe_map'].'</div>';
            }
            else{
                //izrisi opozorilo o max odgovorov
                echo '<div id="max_marker_'.$spremenljivka.'" style="display: none; color:red; font-size:0.8em;">'.$lang['srv_resevanje_max_marker_opozorilo_map'].'</div>';
                    //vkljucijo se funkcije za klik na mapi
                    $klikNaMapo = json_encode(true);
            }
        }
        //ce je podtip izberi lokacijo
        elseif($podtip == 3){
            //izkljucijo se funkcije za klik na mapi
            $klikNaMapo = json_encode(false);
            $mojaLokacija = json_encode(false);
        }
        
        //ali smo v pregledu izpolnjene ankete? ce da, se v JS nastavi na viewMode=true
        $quick_view = json_encode(get('quick_view'));
        //disablaj klik na mapo, ce je v quick_view
        if(get('quick_view'))
            $klikNaMapo = json_encode(false);
        
        //warningi za geolokacijo
        echo '<div id="warning_geo_'.$spremenljivka.'" style="display: none; color:orange; font-size:0.8em;"></div>';
        
        //izrisi search box za v mapo
        echo '<input id="pac-input_'.$spremenljivka.'" class="pac-input" type="text" onkeypress="return event.keyCode != 13;" style="display: none;">';
        //izrisi mapo
        echo '<div id="map_'.$spremenljivka.'" style="width:100%;height:300px;border-style: solid;border-width: 1px;border-color: #b4b3b3;"></div>';
        
        ?>
        <script type="text/javascript">
            //zaradi resevanja preko mobilnikov, dodaj ta class, da se iznici top padding (mobile.css)
            document.getElementById("spremenljivka_<?php echo $spremenljivka; ?>")
                    .getElementsByClassName("variable_holder")[0].className += " map_spremenljivka";

            //preveri, ce je google API ze loadan (ce se je vedno na novo naloadal, je prislo do errorjev)
            if((typeof google === 'object' && typeof google.maps === 'object'))
                MapDeclaration();
            else{
                mapsAPIseNi(MapDeclaration);
            }

            function MapDeclaration(){
                //spremenljivke
                var spremenljivka = "<?php echo $spremenljivka; ?>";
                //podtip
                var podtip = "<?php echo $podtip; ?>";
                max_mark[spremenljivka] = "<?php echo $maxmark; ?>";
                //je ta spremenljivka tipa moja lokacija?
                var mojaLokacija = <?php echo $mojaLokacija; ?>;
                //ze ta spremenljivka vsebuje podatke? primer, ce gre uporabnik na prejsnjo stran
                var map_data = JSON.parse('<?php echo addslashes(json_encode($map_data)); ?>');
                //vsebuje ta spremenljivka F user location (trenutna lokacija)
                var doUserLocation = <?php echo $userLocation; ?>;
                //ali se izrise podvprasanje v infowindow
                podvprasanje[spremenljivka] = <?php echo $podvprasanje; ?>;
                //naslov podvprasanja v infowindow
                podvprasanje_naslov[spremenljivka] = '<?php echo $podvprasanje_naslov; ?>';
                //se naj izvede F klik na mapo (najbrz bo to vedno true)
                var doKlikNaMapo = <?php echo $klikNaMapo; ?>;
                //smo v pregledu izpolnjene ankete?
                viewMode = <?php echo $quick_view; ?>;
                //centerInMap = string naslova, kaj bo zajel zemljevid. Rec. Slovenija / ali Ljubljana
                var centerInMap = "<?php echo $fokus; ?>";
                //get input type - marker, polyline, polygon
                var multi_input_type = "<?php echo $multi_input_type; ?>";
                //steje markerje (int id spremenljivke: int st markerjev)
                st_markerjev[spremenljivka] = 0;     
                //array of all marksers
                allMarkers['<?php echo $spremenljivka; ?>'] = [];
                
                //pridobi parametre za centriranje mape in jo nastavi za kasnejso uporabo
                var centerInMapKoordinate = <?php echo json_encode($fokus_koordinate)?>;
                if(centerInMapKoordinate)
                    centerInMapKoordinate = JSON.parse(centerInMapKoordinate);
                
                //ce je spremenljivka tipa moj lokacija, jo shrani v array
                if(mojaLokacija)
                    ml_sprem.push(spremenljivka);

                //mapType = tip zemljevida, ki bo prikazan. Recimo za satelitsko sliko google.maps.MapTypeId.SATELLITE (možno še .ROADMAP)
                var mapType = google.maps.MapTypeId.ROADMAP;

                //Deklaracija potrebnih stvari za delovanje in upravljanje google maps JS API
                var mapOptions = {
                    streetViewControl: false,
                    navigationControl: false,
                    mapTypeId: mapType
                };
                
                //ce je v bazi naslov enak vpisanemu v nastavitvah, nastavi po parametrih
                if(centerInMapKoordinate){
                    mapOptions.center = {lat:  parseFloat(centerInMapKoordinate.center.lat), 
                        lng:  parseFloat(centerInMapKoordinate.center.lng)};
                    mapOptions.zoom = parseInt(centerInMapKoordinate.zoom);
                }   

                //deklaracija geocoderja (API)
                if(!geocoder)
                    geocoder = new google.maps.Geocoder();
                //infowindow iz API-ja, za prikaz markerja in informacije o markerju
                if(!infowindow)
                    infowindow = new google.maps.InfoWindow();
                //deklaracija zemljevida
                var mapdiv = document.getElementById("map_"+spremenljivka);
                var map = new google.maps.Map(mapdiv, mapOptions);
                //shrani podatke za center v mapo v primeru, ce je spremenljivka hidden  
                //zaradi loopa, da se lahko ob prikayu ponovno nastavi
                map.centerInMap = centerInMap;
                map.centerInMapKoordinate = centerInMapKoordinate;
                //to se kasneje uporabi za pridobitev mape z id-em spremenljivke
                mapdiv.gMap = map;
                //deklaracija mej/okvira prikaza na zemljevidu
                bounds[spremenljivka] = new google.maps.LatLngBounds();
                
                //ce ze obstajajo markerji ali podatki za mapo, jo nafilaj
                if (multi_input_type && multi_input_type != 'marker'){
                        if(map_data.length > 0)
                            map_data_fill_shape(spremenljivka, map_data, multi_input_type);
                        else
                            drawShape(spremenljivka, null, multi_input_type);
                }
                else if (map_data.length > 0)
                    map_data_fill(spremenljivka, map_data, podtip);
                //ce ne obstajajo podatki, centriraj mapo na nastavljeno obmocje
                else if(!centerInMapKoordinate)
                    centrirajMap(centerInMap, map);
                
                if(podtip == 3){
                    //fill info shapes in map if exists
                    var map_data_info_shapes = JSON.parse('<?php echo addslashes(json_encode($map_data_info_shapes)); ?>');
                    if (map_data_info_shapes.length > 0)
                        map_data_fill_info_shapes('<?php echo $spremenljivka; ?>', map_data_info_shapes);
                }

                //izvedi, ce je nastavljena trenutna lokacija respodenta
                if (doUserLocation && map_data.length === 0){
                    usrLoc_sprem.push(spremenljivka);
                    userLocation(spremenljivka);
                }

                //izvedi, ce je nastavljen klik na mapo
                if (doKlikNaMapo)
                    klikNaMapo(spremenljivka);
                
                //izrisi in nastavi search box na zemljevidu
                if(<?php echo $dodaj_searchbox; ?>)
                    searchBox(spremenljivka, function doAfterPlaceFromSearchBox(pos, address){
                        if(max_mark[spremenljivka]-st_markerjev[spremenljivka] > 0)
                            createMarker(spremenljivka, address, pos, true);
                    });
            }

        </script>
        <?php
        echo '</div>';
    }

}
