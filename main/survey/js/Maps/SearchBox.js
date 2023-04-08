// Author: Uroš Podkrižnik (25.6.2016)
// Tip vprasanja = 26 

// SEARCH BOX

/**
 * Skripta, ki ustvari in nastavi iskalno polje za zemljevid
 * @param {type} spremenljivka - int - id spremenljivke
 * @returns {undefined}
 */
function searchBox(spremenljivka, doOnPlacesChanged){
    //pridobi mapo spremenljivke
    var map;
    if(document.getElementById("map_"+spremenljivka))
        map = document.getElementById("map_"+spremenljivka).gMap;
    else if(document.getElementById("br_map_"+spremenljivka)){
        map = document.getElementById("br_map_"+spremenljivka).gMap;
    }
    else{
        map = document.getElementById("maza_map_geofencing").gMap;
    }
    
    // Create the search box and link it to the UI element.
    var input = document.getElementById('pac-input_'+spremenljivka);
    input.style.display='inline-block';
    var searchBox = new google.maps.places.SearchBox(input);
    map.controls[google.maps.ControlPosition.TOP_LEFT].push(input);

    // Bias the SearchBox results towards current map's viewport.
    map.addListener('bounds_changed', function() {
        searchBox.setBounds(map.getBounds());
    });

    // Listen for the event fired when the user selects a prediction and retrieve
    // more details for that place.
    searchBox.addListener('places_changed', function() {
        var places = searchBox.getPlaces();

        if (places.length == 0)
            return;
        
        //first place has data of geometry
        if(places[0].geometry){
            //pozicija v latitude in longitude, ki jo najde
            var pos = {
                lat: places[0].geometry.location.lat(),
                lng: places[0].geometry.location.lng()
            };

            doOnPlacesChanged(pos, places[0].formatted_address);
        }
        //first place does not have data of geometry, do a geocoding from adress
        else{
            findPlace(places[0].name, function(pos, formatted_address){
                doOnPlacesChanged(pos, formatted_address);
            });
        }
    });
}

/**
 * Find place from addres
 * @param {type} address - address to geocode
 * @param {type} doAfterPlaceFound - callback function to call when place is found
 * @returns {undefined}
 */
function findPlace (address, doAfterPlaceFound){
    geocoderFromAddress(address, function(place){
        if(place){
            var pos = {
                lat: place.geometry.location.lat(),
                lng: place.geometry.location.lng()
            };
            
            doAfterPlaceFound(pos, place.formatted_address);
        }
    });
}
