<?php

/*
 *  Modul za dolocanje lokacije na podlagi IP-ja
 * 
 *	Po novem se uporablja GeoIP2 z GeoLiteCity knjiznico
 *
 */


use GeoIp2\Database\Reader;

class SurveyGeoIP{

	var $anketa;				# id ankete
	var $data = array();		# tabela z ip analizo lokacij
        
    var $countryDB = false;
    var $countriesLocationsData = array();

    
	function __construct($anketa){
		global $site_url;

		// Ce imamo anketo
		if ((int)$anketa > 0){
			$this->anketa = $anketa;
		}
                
        // Check if we have data for countries
        $sql = sisplet_query("SELECT COUNT(*) AS cnt FROM countries_locations", 'obj');
        if($sql->cnt > 0){
            $this->countryDB = true;
	    }
	}
	
	
	public function displayData(){
		global $lang;	
		
		// Zakesiramo podatke o lokacijah
		$this->calculateLocations();		
		//var_dump($this->data);
        

        // Opozorilo o nenatancnosti
        echo '<p class="bold">'.$lang['srv_geoip_warning'].'</p>';


		echo '<div>';
        echo '<div class="floatLeft">';
        
		// Izpis tabele frekvenc
		echo '<table class="geoip_table">';
		
		// Prva vrstica
		echo '<tr>';
		echo '<th>'.$lang['srv_geoip_country'].'</th>';
		echo '<th>'.$lang['srv_geoip_city'].'</th>';
		echo '<th>'.$lang['srv_geoip_freq'].'</th>';
		echo '<th>%</th>';
		echo '</tr>';

		// Vrstice s podatki
		if(isset($this->data['all'])){

            foreach($this->data['all'] as $country => $country_freq){
                
                if($country !== 'all'){
                
                    // Frekvenca po drzavi
                    $country_percent = ($this->data['freq'] > 0) ? round($country_freq['freq'] / $this->data['freq'], 3) * 100 : 0;

                    echo '<tr class="country">';
                    echo '<td>'.($country == '' ? '<i>'.$lang['srv_geoip_unknown'].'<i>' : mb_convert_encoding($country, "UTF-8", "ISO-8859-1")).'</td>';
                                $cities = mb_convert_encoding($this->data['all'][$country]['cities'], "UTF-8", "ISO-8859-1");
                    echo '<td>'.(isset($country_freq['showMap']) ? '<a class="fMap rawData" title="'.$lang['srv_view_data_on_map'].'" href="javascript:void(0);" onclick=\'passMapDataRaw('.json_encode($cities).');\'><img src="img_0/Google_Maps_Icon.png" height="24" width="24" /></a></td>' : '');
                    echo '<td>'.$country_freq['freq'].'</td>';
                    echo '<td>'.$country_percent.' %</td>';
                    echo '</tr>';
                    
                    // Se frekvence po mestih
                    foreach($this->data['all'][$country]['cities'] as $city => $city_data){

                        $city_percent = ($this->data['freq'] > 0) ? round($city_data['cnt'] / $this->data['freq'], 3) * 100 : 0;

                        echo '<tr class="city">';
                        echo '<td></td>';
                        echo '<td>'.($city == '' ? '<i>'.$lang['srv_geoip_unknown'].'<i>' : mb_convert_encoding($city, "UTF-8", "ISO-8859-1")).'</td>';
                        echo '<td>'.$city_data['cnt'].'</td>';
                        echo '<td>'.$city_percent.' %</td>';
                        echo '</tr>';
                    }
                }
            }
        }
        
        // Zadnja vrstica
		echo '<tr class="country">';	
		echo '<td>'.$lang['hour_total'].'</td>';
		echo '<td></td>';
		echo '<td>'.$this->data['freq'].'</td>';
		echo '<td>100%</td>';
		echo '</tr>';
        echo '</table>';
        
        echo '</div>';

        // Google maps on right of table
        if(count($this->countriesLocationsData)>0){

            echo '<div class="floatLeft" style="margin: 15px 0px 0px 50px;">';

            if(count($this->countriesLocationsData) > 1)
                $this->displayNavigationMaps();

            echo '<div id="map_ip" style="width: 800px; height:500px;border-style: solid;border-width: 1px;border-color: #b4b3b3;"></div>';
           
            echo '</div>';

            $cities = mb_convert_encoding($this->data['all']['all']['cities'], "UTF-8", "ISO-8859-1");
            
            echo '<script type="text/javascript">passMapDataRaw('.json_encode($cities).');googleMapsAPIProcedura(initializeMapGeneralForIPs);</script>';
        }
        echo '</div>';
	}
	
	
	// Loop cez response in zackesiramo ip-je in lokacije
	private function calculateLocations(){
        global $site_path;
        global $lang;

        // Inicializiramo reader s knjiznico ip lokacij     
        $reader = new Reader($site_path.'admin/survey/modules/mod_geoIP/db/GeoLite2-City.mmdb');
        //$reader = new Reader($site_path.'admin/survey/modules/mod_geoIP/db/GeoLite2-Country.mmdb');


		$sql = sisplet_query("SELECT ip, preview, testdata, last_status, lurker 
								FROM srv_user 
                                WHERE ank_id='".$this->anketa."' AND testdata='0' AND preview='0' AND deleted='0'
                            ");
		while($row = mysqli_fetch_array($sql)){
			
			// Ce locimo glede na status
			//$index = $row['last_status'].'_'.$row['lurker'];
			$index = 'all';

            // Poskusimo ustvariti objekt z lokacijo za IP
            $unknown_location = false;
            try {
                $location_object = $reader->city($row['ip']);
            } 
            catch (Exception $e) {
                //echo 'Error: ',  $e->getMessage();
                $unknown_location = true;
            }
            
            // Ce ni ip-ja v bazi gre za neznano lokacijo
            if($unknown_location){
                $location_data['country_name'] = $lang['srv_unknown'];
                $location_data['country_code'] = '';
                $location_data['city'] = $lang['srv_unknown'];
                $location_data['longitude'] = '';
                $location_data['latitude'] = '';
            }
            // Drugace preberemo iz knjiznice podatke o lokaciji
            else{
                $location_data['country_name'] = $location_object->country->name;
                $location_data['country_code'] = $location_object->country->isoCode;
                $location_data['city'] = $location_object->city->name;
                $location_data['longitude'] = $location_object->location->longitude;
                $location_data['latitude'] = $location_object->location->latitude;
            }

			// Frekvence po drzavah
			if(isset($this->data[$index][$location_data['country_name']]['freq'])){

                $this->data[$index][$location_data['country_name']]['freq']++;
                
                if($location_data['country_name']!='')
                    $this->countriesLocationsData[$location_data['country_name']]['cnt']++;
            }
			else{
                $this->data[$index][$location_data['country_name']]['freq'] = 1;
                
                //store coordinates for country
                if($this->countryDB && $location_data['country_name'] != '' && $location_data['country_name'] != $lang['srv_unknown']){

                    $sqlc = sisplet_query("SELECT latitude, longitude FROM countries_locations WHERE country_code='".$location_data['country_code']."'", 'obj');

                    $this->countriesLocationsData[$location_data['country_name']]['cnt'] = 1;
                    $this->countriesLocationsData[$location_data['country_name']]['lat'] = $sqlc->latitude;
                    $this->countriesLocationsData[$location_data['country_name']]['lng'] = $sqlc->longitude;
                }
            }
			
			// Frekvence po mestih
			if(isset($this->data[$index][$location_data['country_name']]['cities'][$location_data['city']])){
				$this->data[$index][$location_data['country_name']]['cities'][$location_data['city']]['cnt']++;
                $this->data[$index]['all']['cities'][$location_data['city']]['cnt']++;
            }
			else{
				$this->data[$index][$location_data['country_name']]['cities'][$location_data['city']]['cnt'] = 1;
                $this->data[$index]['all']['cities'][$location_data['city']]['cnt'] = 1;

                if($location_data['city'] != '' && $location_data['city'] != $lang['srv_unknown']){
                    $this->data[$index][$location_data['country_name']]['cities'][$location_data['city']]['lat'] = floatval($location_data['latitude']);
                    $this->data[$index][$location_data['country_name']]['cities'][$location_data['city']]['lng'] = floatval($location_data['longitude']);
                    $this->data[$index]['all']['cities'][$location_data['city']]['lat'] = floatval($location_data['latitude']);
                    $this->data[$index]['all']['cities'][$location_data['city']]['lng'] = floatval($location_data['longitude']);
                }
            }

            // Mesto ni znano, imamo pa koordinate
            if($location_data['city'] == '' && isset($location_data['longitude']) && $location_data['longitude'] != '' && isset($location_data['latitude']) && $location_data['latitude'] != ''){
                
                if(isset($this->data[$index][$location_data['country_name']]['cities'][$location_data['city']][''.floatval($location_data['latitude']).floatval($location_data['longitude'])])){
                    $this->data[$index][$location_data['country_name']]['cities'][$location_data['city']][''.floatval($location_data['latitude']).floatval($location_data['longitude'])]['cnt']++;
                    $this->data[$index]['all']['cities'][$location_data['city']][''.floatval($location_data['latitude']).floatval($location_data['longitude'])]['cnt']++;
                }
                else{
                    $this->data[$index][$location_data['country_name']]['cities'][$location_data['city']][''.floatval($location_data['latitude']).floatval($location_data['longitude'])]['cnt'] = 1;
                    $this->data[$index][$location_data['country_name']]['cities'][$location_data['city']][''.floatval($location_data['latitude']).floatval($location_data['longitude'])]['lat'] = floatval($location_data['latitude']);
                    $this->data[$index][$location_data['country_name']]['cities'][$location_data['city']][''.floatval($location_data['latitude']).floatval($location_data['longitude'])]['lng'] = floatval($location_data['longitude']);
                    $this->data[$index]['all']['cities'][$location_data['city']][''.floatval($location_data['latitude']).floatval($location_data['longitude'])]['cnt'] = 1;
                    $this->data[$index]['all']['cities'][$location_data['city']][''.floatval($location_data['latitude']).floatval($location_data['longitude'])]['lat'] = floatval($location_data['latitude']);
                    $this->data[$index]['all']['cities'][$location_data['city']][''.floatval($location_data['latitude']).floatval($location_data['longitude'])]['lng'] = floatval($location_data['longitude']);
                }   
            }

            if(isset($location_data['latitude']) && $location_data['latitude'] != ''){
                $this->data[$index][$location_data['country_name']]['showMap'] = 1;
                $this->data[$index]['all']['showMap'] = 1;
            }
                        
			// Frekvenca vseh
			if(isset($this->data['freq']))
				$this->data['freq']++;
			else
				$this->data['freq'] = 1;
		}
	}
        
    function displayNavigationMaps() {
        global $lang;
        
        $countries = mb_convert_encoding($this->countriesLocationsData, "UTF-8", "ISO-8859-1");
        $cities = mb_convert_encoding($this->data['all']['all']['cities'], "UTF-8", "ISO-8859-1");

        echo '<div class="secondNavigation">';
        echo '<ul class="secondNavigation">';

        echo'<li>';
        echo '<a class="no-img active" id="geoip_cities" onclick=\'geoip_map_navigation_toggle(this, '.json_encode($cities).');\'>';
        echo '<span class="label">' . $lang['srv_geoip_map_cities'] . '</span>';
        echo '</a>';
        echo'</li>';

        #space
        echo'<li class="space">';
        echo'</li>';

        echo'<li>';
        echo '<a class="no-img" id="geoip_countries" onclick=\'geoip_map_navigation_toggle(this, '.json_encode($countries).');\'>';
        echo '<span class="label">' . $lang['srv_geoip_map_countries'] . '</span>';
        echo '</a>';
        echo'</li>';

        echo'</ul>';
        echo '</div>';

        echo '<br class="clr" />';
        echo '<br class="clr" />';
    }
}