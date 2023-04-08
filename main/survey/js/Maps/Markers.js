// Author: Uroš Podkrižnik (17.12.2015)
// Tip vprasanja = 26

//MARKERS

/**
 * Ce obstajajo podatki v bazi (rec. uporabnik klikne 'Prejsnja stran'). 
 * Kreirajo se markerji, shranjeni v bazi.
 * @param spremenljivka int id spremenljivke
 * @param map_data JSON Object
 * @param podtip int - podtip/enota - 1-mojalokacija, 2-multilokacija, 3-chooselokacija
 */
function map_data_fill(spremenljivka, map_data, podtip) {
    for (var row in map_data) {
        var row_object = map_data[row];
        var pos = {lat: row_object.lat, lng: row_object.lng};
        //kreiraj marker
        createMarker(spremenljivka, row_object.address, pos, false, row_object.text, podtip, row_object.naslov, row_object.vre_id);
    }
}

/**
 * funkcija, ki kreira osnovni marker (rdec)
 * @param {type} spremenljivka - int - id spremenljivke
 * @param {type} add - String - label o informacijah markerja
 * @param {type} pos - koordinate - objekt {lat: ???, lng: ???}
 * @param {boolean} fromSearchBox - true if called from searchBox, false otherwise (used to avoid duplicates)
 * @param {type} text - string - text za windowinfo textarea
 * @param {type} podtip - int - podtip/enota - 1-mojalokacija, 2-multilokacija, 3-chooselokacija
 * @param {type} naslov - string - title of marker
 * @param {type} vre_id - int - id of vrednost
 * @returns {undefined}
 */
function createMarker(spremenljivka, add, pos, fromSearchBox, text, podtip, naslov, vre_id) {
    //in case, we are creating marker from searchbox, check if marker with this address already exist
    //if yes, do not create marker and focus on marker with this address, if not, create marker
    var marker = fromSearchBox ? findMarkerFromAddress(add, spremenljivka) : null;

    if (!marker) {
        //pridobi mapo spremenljivke
        var map;
        if (document.getElementById("map_" + spremenljivka))
            map = document.getElementById("map_" + spremenljivka).gMap;
        else
            map = document.getElementById("map_canvas_all").gMap;

        //pridobi ID markerja
        var markerId = ((podtip == 3) && vre_id) ? vre_id : getMarkerUniqueId(pos.lat, pos.lng, spremenljivka);

        //ce ze obstaja marker s tocno takim ID (iste koordinate), ignoriraj
        //do te situacije pride (da je spodaj false) lahko samo tako, da v 
        //searchbox uporabnik dvakrat vpise isto lokacijo in jo markira -podvajanje podatkov
        if ($('#' + markerId).length == 0) {

            var path_img_dir = srv_site_url + 'admin/survey/img_0/';

            var icon = {
                fillColor: '#FF5555',
                url: path_img_dir + (podvprasanje[spremenljivka] ? 'marker_text_off.png' :
                        'marker_default.svg'),
                fillOpacity: 1,
                strokeWeight: 1
            }
            
            var namapo = null;
            //var soda = spremenljivka % 2 == 0;

            //v view mode naredimo clusters, pri resevanju pa jhi sproti filamo v  mapo
            if (!viewMode)
                namapo = map;
            else if(podtip == 3){
                namapo = map;
            }

            //nastavitve markerja
            var marker = new google.maps.Marker({
                position: new google.maps.LatLng(pos.lat, pos.lng),
                id: 'marker_' + markerId,
                map: namapo,
                icon: icon,
                address: add
            });

            
            //store marker in markers array
            allMarkers[spremenljivka].push(marker);

            //ce je tip moja lokacija
            if (ml_sprem.indexOf(spremenljivka) > -1) {
                //ce obstaja marker, ga izbrisi
                if (mlmarker['' + spremenljivka]){
                    removeMarker(spremenljivka, mlmarker['' + spremenljivka], markerId);
                    deleteAllMarkerInputs(spremenljivka);
                }
                //shrani marker v array, za kasnejsi iibris markerja iz mape pri mojilokaciji
                mlmarker['' + spremenljivka] = marker;
            }

            if (!viewMode) {
                //ustvari input, ce ze ne obstaja ta id (tocno te koordinate)
                createMarkerInput(spremenljivka, marker, markerId, text);

                if (podtip != 3)
                    //markers[markerId] = marker; // cache marker in markers object
                    bindMarkerEvents(spremenljivka, marker, markerId, map); // bind right click event to marker
            }

            //Create a div element for container.
            var container = document.createElement("div");

            if (podtip == 3) {
                if (naslov) {
                    //Create a label element for title.
                    var title = document.createElement("label");
                    title.innerHTML = '<b>' + naslov + '</b><br>';
                    container.appendChild(title);
                }

                //Create a label element for address.
                var label = document.createElement("label");
                label.innerHTML = '<i>' + add + '</i><br>' + (podvprasanje_naslov[spremenljivka] ? '<br>' : '');
                label.style.cssText = 'font-size:0.85em';
                container.appendChild(label);
            } else {
                //Create a label element for address.
                var label = document.createElement("label");
                label.innerHTML = viewMode ? '<b>' + add + '</b><br>' : add + '<br>';
                container.appendChild(label);
            }

            //naredi textbox, ce je nastavljeno podvprasanje
            if (podvprasanje[spremenljivka]) {
                //Create a label element for subquestion title
                if (podvprasanje_naslov[spremenljivka]) {
                    var podvprasanje_title = document.createElement("label");
                    podvprasanje_title.innerHTML = viewMode ? podvprasanje_naslov[spremenljivka] + '<br>' :
                            '<b>' + podvprasanje_naslov[spremenljivka] + '</b><br>';
                    container.appendChild(podvprasanje_title);
                }

                if (!viewMode) {
                    //Create a textarea for the input text
                    var textBox = document.createElement("textarea");
                    textBox.setAttribute("id", markerId + "_textarea");
                    textBox.style.width = "100%";
                    textBox.id = markerId + "_textarea_id";
                    textBox.className = "boxsizingBorder";
                    textBox.style.height = "50px";
                } else {
                    //Create a label for respondent text response
                    var textBox = document.createElement("label");
                }
                //ce obstaja text, ga vstavi v textarea
                if (text && (text != '-2' && text != '-4' && text != '-1')) {
                    textBox.innerHTML = viewMode ? '<i>' + text + '</i>' : text;
                    marker.setIcon({url: path_img_dir + 'marker_text_on.png'});
                }

                container.appendChild(textBox);

                if (!viewMode) {
                    //ko se spremeni textarea v windowinfo, spremeni value inputa za text
                    google.maps.event.addDomListener(textBox, "input", function () {
                        document.getElementById(markerId + '_text').value = textBox.value;
                        if (textBox.value)
                            marker.setIcon({url: path_img_dir + 'marker_text_on.png'});
                        else
                            marker.setIcon({url: path_img_dir + 'marker_text_off.png'});
                    });
                }
            }

            //if "choose location" in view mode, declare own infowindow for each marker and open in
            //otherwise use global variable infowindow
            var infowin = (podtip == 3 && viewMode) ? new google.maps.InfoWindow() : infowindow;

            //listener ob kliku na marker - focus input mora biti po nastavljanju bounds ali zoom
            google.maps.event.addListener(marker, 'click', function () {
                infowin.setContent(container);
                infowin.open(map, marker);
                //focus on input
                $("#" + markerId + "_textarea_id").focus();
            });

            //nastavi label
            infowin.setContent(container);
            //odpre marker - prikaze label (kot da bi ga kliknil)
            infowin.open(map, marker);

            if (ml_sprem.indexOf(spremenljivka) == -1)
                //v okvir se doda nov marker
                bounds[spremenljivka].extend(marker.position);

            //ce je samo eden marker, ga malo odzoomaj
            if (ml_sprem.indexOf(spremenljivka) > -1 || (st_markerjev[spremenljivka] == 0) && viewMode) {
                map.setCenter(marker.position);
                map.setZoom(17);
            } else if (st_markerjev[spremenljivka] > 0) {
                //zemljevid se prilagodi okviru
                map.fitBounds(bounds[spremenljivka]);

                //ce ni v viewMode - je v resevanju ankete - se za vsak marker odzooma
                /*if(!viewMode && podtip != 3){
                 //zmanjsaj zoom za 1, ker google naredi prevec oddaljeno
                 google.maps.event.addListenerOnce(map, "bounds_changed", function() { 
                 map.setZoom(map.getZoom()-1);
                 });
                 }*/
                //ce je v viewMode - v podatkih ali analizah - se odzooma za 1 samo pri zadnjem markerju
                if (viewMode && max_mark[spremenljivka] == st_markerjev[spremenljivka] + 1) {
                    //zmanjsaj zoom za 1, ker google naredi prevec oddaljeno
                    google.maps.event.addListenerOnce(map, "bounds_changed", function () {
                        map.setZoom(map.getZoom() - 1);
                    });
                }
            }

            //stevilo markerjev se poveca
            st_markerjev[spremenljivka]++;

            //odstej od dovoljenih za opozorilo
            if (ml_sprem.indexOf(spremenljivka) == -1 && !viewMode && max_mark[spremenljivka] == st_markerjev[spremenljivka])
                $('#max_marker_' + spremenljivka).show();

            //focus on input - must be after setting bounds or zooming
            $("#" + markerId + "_textarea_id").focus();
        }
    } else {
        //trigger click on marker, to focus it and open infowindow
        google.maps.event.trigger(marker, 'click');
    }
}

/**
 * handler za desni klik in dvojni klik na markerja
 * @param {type} spremenljivka - int - id spremenljivke
 * @param {type} marker
 * @param {type} markerId
 * @param {type} map - google map
 */
function bindMarkerEvents(spremenljivka, marker, markerId, map) {
    google.maps.event.addListener(marker, "rightclick", function () {
        //removeMarker(spremenljivka, marker, markerId); // remove marker from array
        deleteMenu.open(map, null, null, spremenljivka, null, null, marker, markerId);
        $('#max_marker_' + spremenljivka).hide();
    });
    google.maps.event.addListener(marker, "dblclick", function () {
        //removeMarker(spremenljivka, marker, markerId); // remove marker from array
        deleteMenu.open(map, null, null, spremenljivka, null, null, marker, markerId);
        $('#max_marker_' + spremenljivka).hide();
    });
}

/**
 * brise marker iz zemljevida
 * @param {type} spremenljivka - int - id spremenljivke
 * @param {type} marker
 * @param {type} markerId
 */
function removeMarker(spremenljivka, marker, markerId) {
    marker.setMap(null); // set markers setMap to null to remove it from map
    //remove marker from marker array
    var index = allMarkers[spremenljivka].indexOf(marker);
    if (index > -1)
        allMarkers[spremenljivka].splice(index, 1);
    //stevilo markerjev se zmanjsa
    st_markerjev[spremenljivka]--;
    
    deleteMarkerInput(markerId); //remove input of marker fro form
}

/**
 * kreira hidden inpute s podatki markerja
 * @param {type} spremenljivka - int - id spremenljivke
 * @param {type} marker
 * @param {type} markerId - id markerja
 * @param {type} text - text za textarea v windowinfo
 * @returns {undefined}
 */
function createMarkerInput(spremenljivka, marker, markerId, text) {
    //najdi element za drzanje variabel
    var $variable_holder = $("#spremenljivka_" + spremenljivka + "_variabla");

    //kreiraj input s statičnimi podatki markerja
    var $markerInput = $("<input>", {id: markerId, name: "vrednost_" + spremenljivka + "[]",
        type: "hidden", class: "marker_input",
        value: markerId + "|" + marker.getPosition().lat() + "|" +
                marker.getPosition().lng() + "|" + marker.address});
    $variable_holder.append($markerInput);

    if (podvprasanje[spremenljivka]) {
        //kreiraj input s textom  - textarea v infowindow
        var $textareaInput = $("<input>", {id: markerId + '_text', name: markerId + '_text',
            type: "hidden", value: text, class: "marker_input"});
        $variable_holder.append($textareaInput);
    }
}

/**
 * brise inpute s podatki markerja
 * @param {type} markerId - id markerja
 */
function deleteMarkerInput(markerId) {
    //brisi input s staticnimi podatki markerja
    $('#' + markerId).remove();
    //brisi input s textom markerja v windowinfo
    $('#' + markerId + '_text').remove();
}

/**
 * brise vse inpute mape (vse markerje)
 * @param {type} spremenljivka - int - id spremenljivke
 */
function deleteAllMarkerInputs(spremenljivka) {
    //brisi vse inpute s staticnimi podatki markerja
    $('#spremenljivka_' + spremenljivka + '_variabla').find('input.marker_input').remove();
}