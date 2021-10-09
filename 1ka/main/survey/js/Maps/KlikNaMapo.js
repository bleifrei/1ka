// Author: Uroš Podkrižnik (17.12.2015)
// Tip vprasanja = 26

// KLIK NA MAPO

/**
 * izvrsi funkcionalnost za klik na zemljevid
 * @param {type} spremenljivka - int - id spremenljivke
 * @returns {undefined}
 */
function klikNaMapo(spremenljivka) {
    //pridobi mapo spremenljivke
    var map = document.getElementById("map_"+spremenljivka).gMap;
    
    // ko user klikne na mapo, funkcija vrne pozicijo (koordinate - position) ter kreira
    google.maps.event.addListener(map, 'click', function (event) {

        //koordinate
        var pos = {
            lat: event.latLng.lat(),
            lng: event.latLng.lng()
        };

        //za omejitev odgovorov
        if(ml_sprem.indexOf(spremenljivka) > -1 || max_mark[spremenljivka] != st_markerjev[spremenljivka]){
            // naslov se pridobi, da se klice geocoding
            GeocodingF(pos, function (data) {
                //ce ne vrne null - je nasel naslov
                if (data != null) {

                    //kreira marker na lokaciji, kjer je uporabnik kliknil
                    createMarker(spremenljivka, data.formatted_address, pos, false);

                } else {
                    //odpre se okno, ce je prislo do napake - null - (mozen je tudi prekratek delay med geocoding requesti)
                    alert(lang['srv_resevanje_alert_location_not_found_map']);
                }
            });
        }

        return pos;
    });
}