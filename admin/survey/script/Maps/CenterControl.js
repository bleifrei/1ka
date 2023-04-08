// Author: Uroš Podkrižnik (23.1.2017)
// Tip vprasanja = 26

//SET CONTROLS FOR SETTING FOCUS ON MAP

/**
 * Used in Branching.php for setting Map focus on Map
 * @param {type} controlDiv - div to put in custom settings - buttons
 * @param {type} map - google map
 * @param {type} spremenljivka - id spremenljivke
 * @returns {undefined}
 */
function centerControl(controlDiv, map, spremenljivka) {

    // Set CSS for the control border.
    var controlUI = document.createElement('div');
    controlUI.style.backgroundColor = '#fff';
    controlUI.style.border = '2px solid #fff';
    controlUI.style.borderRadius = '3px';
    controlUI.style.boxShadow = '0 2px 6px rgba(0,0,0,.3)';
    controlUI.style.cursor = 'pointer';
    controlUI.style.marginBottom = '22px';
    controlUI.style.textAlign = 'center';
    controlUI.style.display = 'inline-block';
    controlUI.title = lang['srv_vprasanje_fokus_button_map_set_title'];
    controlDiv.appendChild(controlUI);

    // Set CSS for the control interior.
    var controlText = document.createElement('div');
    controlText.style.color = 'rgb(25,25,25)';
    controlText.style.fontFamily = 'Roboto,Arial,sans-serif';
    controlText.style.fontSize = '16px';
    controlText.style.lineHeight = '28px';
    controlText.style.paddingLeft = '5px';
    controlText.style.paddingRight = '5px';
    controlText.innerHTML = lang['srv_vprasanje_fokus_button_map_set'];
    controlUI.appendChild(controlText);

    //cancel button
    var controlUI1 = document.createElement('div');
    controlUI1.style.backgroundColor = '#fff';
    controlUI1.style.border = '2px solid #fff';
    controlUI1.style.borderRadius = '3px';
    controlUI1.style.boxShadow = '0 2px 6px rgba(0,0,0,.3)';
    controlUI1.style.cursor = 'pointer';
    controlUI1.style.marginBottom = '22px';
    controlUI1.style.textAlign = 'center';
    controlUI1.style.display = 'none';
    controlUI1.style.marginLeft = '5px';
    controlUI1.title = lang['srv_vprasanje_fokus_button_map_cancel_title'];
    controlDiv.appendChild(controlUI1);

    //cancel button
    var controlText1 = document.createElement('div');
    controlText1.style.color = 'rgb(25,25,25)';
    controlText1.style.fontFamily = 'Roboto,Arial,sans-serif';
    controlText1.style.fontSize = '16px';
    controlText1.style.lineHeight = '28px';
    controlText1.style.paddingLeft = '5px';
    controlText1.style.paddingRight = '5px';
    controlText1.innerHTML = lang['srv_vprasanje_fokus_button_map_cancel'];
    controlUI1.appendChild(controlText1);

    // Setup the click event listeners
    controlUI.addEventListener('click', function() {
        if(controlText.innerHTML === lang['srv_vprasanje_fokus_button_map_set']){
            controlText.innerHTML = lang['srv_vprasanje_fokus_button_map_save'];
            controlUI.title = lang['srv_vprasanje_fokus_button_map_save_title'];

            setMapMovable(map);

            controlUI1.style.display = 'inline-block';
        }
        else{
            //shrani spremembe v bazo
            set_fokus_koordiante_map(spremenljivka, map.getCenter().lat(), 
                map.getCenter().lng(), map.getZoom(), '');
            set_fokus_string_map(spremenljivka, '')

            var fokus_mape_settings = document.getElementById("fokus_mape_"+spremenljivka);
            if(fokus_mape_settings)
                //v nastavitvah vprasanja pobrisi value textboxa za fokus
                document.getElementById("fokus_mape_"+spremenljivka).value = "";

            setMapNoFocus(map);
        }
    });

    // Setup the click event listeners for cancel
    controlUI1.addEventListener('click', function() {
        setMapNoFocus(map);
        vprasanje_fullscreen(spremenljivka);
    });

    //set map to not focusing mode
    function setMapNoFocus(map){
            setMapFixed(map);
            controlUI1.style.display = 'none';
            controlText.innerHTML = lang['srv_vprasanje_fokus_button_map_set'];
            controlUI.title = lang['srv_vprasanje_fokus_button_map_set_title'];
    }
}

function set_fokus_koordiante_map(spremenljivka, lat, lng, zoom, fokus){
    //kreiraj json za kasnejsi fokus mape - da se ne porabljajo kvote za geocoding
    var fokusJSON = {koordinate:{center:{lat:null, lng:null}, zoom:null, fokus:fokus}, 
        spr_id:spremenljivka, anketa: srv_meta_anketa_id};
    fokusJSON.koordinate.center.lat = lat;
    fokusJSON.koordinate.center.lng = lng;
    fokusJSON.koordinate.zoom = zoom;

    //shrani parametre v bazo - BranchingAjax.php -> ajax_fokus_koordiante_map()
    $.post('ajax.php?t=branching&a=fokus_koordiante_map', fokusJSON);
}

function set_fokus_string_map(spremenljivka, fokus){
    //spremeni fokus string v praznega, saj je custom narejen fokus
    var fokusJSON2 = {fokus:fokus, spr_id:spremenljivka, anketa: srv_meta_anketa_id};
    $.post('ajax.php?t=branching&a=fokus_string_map', fokusJSON2);
}

function setMapMovable(map){
    map.set('zoomControl', true);
    map.set('disableDoubleClickZoom', false);
    map.set('scrollwheel', true);
    map.set('navigationControl', true);
    map.set('draggable', true); 
    map.set('mapTypeControl', true);
}

function setMapFixed(map){
    map.set('zoomControl', false);
    map.set('disableDoubleClickZoom', true);
    map.set('scrollwheel', false);
    map.set('navigationControl', false);
    map.set('draggable', false); 
    map.set('mapTypeControl', false);
}