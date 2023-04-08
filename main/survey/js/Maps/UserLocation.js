// Author: Uroš Podkrižnik (17.12.2015)
// Tip vprasanja = 26 

// USER LOCATION
// Skripta, ki najde lokacijo uporabnika

/**
 * funkcija za zacetek procedure ya ugotavljanje lokacije userja
 * @param {type} spremenljivka - int - id spremenljivke
 * @returns {undefined}
 */
function userLocation(spremenljivka) {
    // ali browser podpira pridobivanje lokacije uporabnika
    if (navigator.geolocation) {
        
        var options = {
            enableHighAccuracy: true,
            timeout: 0,
            maximumAge: Infinity
        };

        navigator.geolocation.getCurrentPosition(
            function (position) {
                //pozicija v latitude in longitude, ki jo najde
                var pos = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };
                
                //accuracy of position
                //console.log('More or less meters ' + position.coords.accuracy);

                //klice proceduro, kjer se naprej operira s koordinatami
                userLocationProceduraF(pos, spremenljivka);
            }, function (error) {
                var warning = $('#warning_geo_'+spremenljivka);

                if(error){
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            warning.html(lang['srv_resevanje_user_denied_geo_map']);
                            break;
                        case error.POSITION_UNAVAILABLE:
                            warning.html(lang['srv_resevanje_position_unavailable_geo_map']);
                            break;
                        case error.TIMEOUT:
                            warning.html(lang['srv_resevanje_timeout_geo_map']);
                            break;
                        case error.UNKNOWN_ERROR:
                            warning.html(lang['srv_resevanje_unknown_error_geo_map']);
                            break;
                    }
                }
                else
                    warning.html(lang['srv_resevanje_browser_not_support_geo_map']);

                warning.show();
            }
        );
    } else {
        // Browser doesn't support Geolocation
        handleLocationError(false);
    }
}

/**
 * procedura, ki se izvede, ko API najde lokacijo userja
 * @param {type} pos - koordinate - objekt {lat: ???, lng: ???}
 * @param {type} spremenljivka - int - id spremenljivke
 * @returns {undefined}
 */
function userLocationProceduraF(pos, spremenljivka) {
    //za omejitev max markerjev
    if(ml_sprem.indexOf(spremenljivka) > -1 || max_mark[spremenljivka]-st_markerjev[spremenljivka] != 0){
        //pretvori iz koordinat v naslov in nastavi label v infowindow
        GeocodingF(pos, function (data) {
            //ce ne vrne null - je nasel naslov
            if (data != null) {
                //kreira marker na lokaciji
                for (key in usrLoc_sprem) {
                    //Param fromSearchBox is true because of possible duplicate at multilocation with userloction ON,
                    //if user goes to previous page. 
                    //In very rare occasions this may couse bad UX in case: 
                    //user has clicked on location with address 'Undefined road', then userlocation returns
                    //address as 'Undefined road' - in this case, marker will not be created from userlocation.
                    createMarker(usrLoc_sprem[key], data.formatted_address, pos, true);
                }
            } else {
                //odpre se okno, ce je prislo do napake - null - (mozen je tudi prekratek delay med geocoding requesti)
                alert(lang['srv_resevanje_alert_location_not_found_map']);
            }
        });
    }
}