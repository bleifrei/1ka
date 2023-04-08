// Author: Uroš Podkrižnik (17.12.2015)
// Tip vprasanja = 26

// GEOCODING

/**
 * pretvorba iz latitude, longitude v berljiv naslov
 * funkcija vrne String - results[0] - prvi (najdaljsi naslov)
 * @param {type} pos - koordinate - objekt {lat: ???, lng: ???}
 * @param {type} callback - function - ker je geocode asinhrona funkcija, se uporabi, da se vrne rezultat
 * @returns {undefined}
 */
function GeocodingF(pos, callback) {
    geocoder.geocode({'location': pos}, function (results, status) {

        // ce je status OK - pridobil informacije o naslovu
        if (status === google.maps.GeocoderStatus.OK) {
            //console.log(results);//[0]= polni naslov, [1]= Ljubljana, Slovenija,....
            if (results[0]) {
                //console.log(results[0].formatted_address);
                //vrne rezultat (Objekt s polnim naslovom)
                callback(results[0]);
            } else {
                console.log('No results found');
                callback(null);
            }
        }
        // ce je prislo do napake
        else {
            console.log('Geocoder failed due to: ' + status);
            if(status == 'ZERO_RESULTS'){
                var obj = {formatted_address: ""}
                callback(obj);
            }
            else
                callback(null);
        }
    });
}

/**
 * centriranje na zemljevidu (kaj se bo prikazalo na zemljevidu / zajelo v okvir)
 * @param {type} centerInMap - String - naslov, ki ga naj zemljevid zajame v okvir
 * @param {type} map - mapa/zemljevid
 * @returns {undefined}
 */
function centrirajMap (centerInMap, map){
    geocoderFromAddress(centerInMap, function(place){
        if(place){
            map.setCenter(place.geometry.location);
            map.fitBounds(place.geometry.viewport);
            //povecaj zoom za 1, ker google naredi prevec oddaljeno
            //pri vecji povrsini na mapi (npr Slovenija), ne dela ok
            //map.setZoom(map.getZoom()+1);
        }
    });
}

/**
 * Geocoding from address to places, in callback only first place is returned
 * @param {type} address - address to geocode
 * @param {type} callback - callback function to call when place is found
 * @returns {undefined}
 */
function geocoderFromAddress(address, callback){
    var delay = 100;
    var stej_poizvedbe = 0;

    geocoder.geocode({'address': address}, function (results, status) {
        if (status === google.maps.GeocoderStatus.OK) {
            callback(results[0]);
        }
        //zelo redko pride do tega, recimo ce uporabnik na polno stanca lokacije
        else if(status === google.maps.GeocoderStatus.OVER_QUERY_LIMIT){
            if(stej_poizvedbe < 10){
                setTimeout(geocoderFromAddress(address, callback), delay);
                stej_poizvedbe++;
                delay += 100;
            }
            else
                console.log('Geocoder error: OVER_QUERY_LIMIT; repeated: '+stej_poizvedbe);
        }
        //ce ni najdenih rezultatov za vpisan naslov v nastavitvah (fokus)
        else if(status === google.maps.GeocoderStatus.ZERO_RESULTS){
            alert(lang['srv_branching_no_results_geo_map']+': '+address);
        }
        else
            console.log('Geocoder error: ' + status);
        
        return null;
    });
}