// Author: Uroš Podkrižnik (22.2.2017)
// Tip vprasanja = 26

//MARKERS - adding markers in branching

//storing active shape, to for instance disable edit mode when needed
var active_shape;

/**
 * Ce obstajajo podatki v bazi (rec. uporabnik klikne 'Prejsnja stran'). 
 * Kreirajo se markerji, shranjeni v bazi.
 * @param spremenljivka int id spremenljivke
 * @param map_data JSON Object
 * @param locked boolean true, if survey is locked in branching
 */
function map_data_fill_vnaprej_mrkerji(spremenljivka, map_data, locked) {
    for (var row in map_data) {
        var row_object = map_data[row];
        var pos = {lat: row_object.lat, lng: row_object.lng};

        createMarkerVnaprej(spremenljivka, row_object.address, row_object.naslov,
                ustvari_basic_marker(spremenljivka, pos, false), row_object.id, true, locked);
    }
}

/**
 * Ce obstajajo podatki v bazi izrisi info shapes
 * Kreirajo se markerji, shranjeni v bazi.
 * @param spremenljivka int id spremenljivke
 * @param map_data JSON Object
 * @param locked boolean true, if survey is locked in branching
 * @returns {int} ID of last shape
 */
function map_data_fill_vnaprej_shapes(spremenljivka, map_data, locked) {
    //pridobi mapo spremenljivke
    var map = document.getElementById("br_map_" + spremenljivka).gMap;

    var shapeOption = polylineOptions(map);
    var last_id = 0;
    for (var i = 0; i < map_data.length; i++)
    {
        
        last_id = map_data[i].overlay_id;
        var color = mapShapeColors[map_data[i].overlay_id-1 % mapShapeColors.length];

        //set and show the shape
        shapeOption.path = map_data[i].path;
        shapeOption.strokeColor = '#' + color;
        shapeOption.strokeColorOrg = '#' + color;
        shapeOption.editable = false;
        var shape = new google.maps.Polyline(shapeOption);

        shape.overlay_id = map_data[i].overlay_id;
        shape.address = map_data[i].address;
        shape.infowin = makeShapeInfoWindow(spremenljivka, shape, locked);
        setListenersShapeVM(spremenljivka, shape, map, locked);

        //google.maps.event.addListener(shape, 'mouseover', function() { this.setOptions({strokeWeight: 6, strokeColor: 'black', zIndex: 2}); });
        //google.maps.event.addListener(shape, 'mouseout', function() { this.setOptions({strokeWeight: 4, strokeColor: this.strokeColorOrg , zIndex: 1}); });
    }
    return last_id;
}

/**
 * Ustvari in vrne basic marker, na mapo spremenljivke z danimi koordinatami
 * @param {type} spremenljivka - int - id spremenljivke
 * @param {type} pos - koordinate - objekt {lat: ???, lng: ???}
 * @param {string} addressFromSearchBox - address if adding marker from searchBox, null otherwise
 *                  (used for ignoring duplicates)
 * @returns {google.maps.Marker}
 */
function ustvari_basic_marker(spremenljivka, pos, addressFromSearchBox) {
    var marker = addressFromSearchBox ? findMarkerFromAddress(addressFromSearchBox, spremenljivka) : null;

    if (!marker) {
        //pridobi mapo spremenljivke
        var map = document.getElementById("br_map_" + spremenljivka).gMap;

        //kreiraj marker
        var marker = new google.maps.Marker({
            position: new google.maps.LatLng(pos.lat, pos.lng),
            map: map,
            identifier: getMarkerUniqueId(pos.lat, pos.lng, spremenljivka),
            icon: {url: "img_0/marker_text_off.png"}
        });

        //hide warning text for 'no values yet'
        var no_value_warning = document.getElementById("variabla_no_value_" + spremenljivka);
        no_value_warning.style.display = "none";

        allMarkers[spremenljivka].push(marker);

        return marker;
    } else {
        //trigger click on marker, to focus it and open infowindow
        google.maps.event.trigger(marker, 'click');
        return null
    }
}

/**
 * funkcija, ki kreira osnovni marker (rdec)
 * @param {type} spremenljivka - int - id spremenljivke
 * @param {type} add - String - label o informacijah markerja
 * @param {type} text - string - text za windowinfo textarea
 * @param {type} marker - google maps new created marker
 * @returns {undefined}
 */
function shraniMarker(spremenljivka, add, text, marker) {
    if (marker != null) {
        //save new marker in DB
        $.post('ajax.php?t=branching&a=save_marker', {spr_id: spremenljivka, address: add,
            lat: marker.getPosition().lat(), lng: marker.getPosition().lng(), anketa: srv_meta_anketa_id},
                function (id) {
                    //id - id markerja v bazi
                    createMarkerVnaprej(spremenljivka, add, text, marker, id, false);
                });
    }
}

/**
 * funkcija, ki kreira osnovni marker (rdec)
 * @param {type} spremenljivka - int - id spremenljivke
 * @param {type} add - String - label o informacijah markerja
 * @param {type} naslov - string - text za windowinfo textarea
 * @param {type} marker - google maps new created marker
 * @param {type} id - id vrednosti markerja iz tabele srv_vrednost
 * @param {type} fromLoad - boolean true, if data for map already axists and 
 *      this function was used to load a map with existing markers
 * @param locked boolean true, if survey is locked in branching
 * @returns {undefined}
 */
function createMarkerVnaprej(spremenljivka, add, naslov, marker, id, fromLoad, locked) {
    if (marker != null) {
        //pridobi mapo spremenljivke
        var map = document.getElementById("br_map_" + spremenljivka).gMap;

        marker.address = add;
        marker.id = id;

        bounds[spremenljivka].extend(marker.position);

        //markers[markerId] = marker; // cache marker in markers object
        //dont set listeners if survey is locked
        if (!locked)
            bindMarkerEventsVM(spremenljivka, marker, map); // bind right click event to marker

        //Create a div element for container.
        var container = document.createElement("div");
        //margin, da se pri italic ne odreze zadja crka
        container.style.cssText = 'margin-right:1px';

        //Create a textarea for the input text
        var textBoxTitle = document.createElement("input");
        textBoxTitle.style.cssText = "font-weight:bold; font-size:1em";
        textBoxTitle.type = "text";
        textBoxTitle.value = naslov;
        textBoxTitle.id = "input_map_vre_" + id;
        textBoxTitle.className = "input_title";
        container.appendChild(textBoxTitle);
        container.appendChild(document.createElement("br"));
        //ce je anketa locked, disablaj urejanje naslova
        if (locked)
            textBoxTitle.disabled = "disabled";

        //ko se spremeni textarea v windowinfo, spremeni value inputa za text
        google.maps.event.addDomListener(textBoxTitle, "change", function () {
            //save title of marker in DB
            $.post('ajax.php?t=vprasanjeinline&a=inline_vrednost_naslov_save', {
                spremenljivka: spremenljivka, vrednost: id, naslov: textBoxTitle.value,
                anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id});
        });

        //Create a label element for address.
        var address = document.createElement("label");
        address.innerHTML = '<i>' + add + '</i>';
        address.style.cssText = 'font-size:0.85em;';
        container.appendChild(address);
        container.appendChild(document.createElement("br"));

        //create label for subquestion if exist or is not empty string
        if (podvprasanje_naslov[spremenljivka]) {
            container.appendChild(document.createElement("br"));
            //Create a label element for subquestion.
            var podvprasanje = document.createElement("label");
            podvprasanje.innerHTML = '<b>' + podvprasanje_naslov[spremenljivka] + '</b>';
            podvprasanje.style.cssText = 'font-size:0.95em';
            container.appendChild(podvprasanje);
            container.appendChild(document.createElement("br"));
        }

        var textBox = document.createElement("textarea");
        //textBox.setAttribute("id", markerId + "_textarea");
        textBox.style.width = "100%";
        textBox.className = "boxsizingBorder";
        textBox.disabled = "disabled";
        textBox.style.height = "50px";
        textBox.style.resize = "none";
        container.appendChild(textBox);

        //listener ob kliku na marker
        google.maps.event.addListener(marker, 'click', function () {
            //if infowindow in opened, blur focus, so saving in DB can be triggered
            blurTitleInfowindow(false);

            infowindow.setContent(container);
            infowindow.open(map, marker);
            $("#input_map_vre_" + id).focus();
        });

        if (!fromLoad) {
            //if infowindow in opened, blur focus, so saving in DB can be triggered
            blurTitleInfowindow(true);

            //nastavi label
            infowindow.setContent(container);
            //odpre marker - prikaze label (kot da bi ga kliknil)
            infowindow.open(map, marker);
            //fokus na input
            $("#input_map_vre_" + id).focus();
        } else {
            //zemljevid se prilagodi okviru
            map.fitBounds(bounds[spremenljivka]);
        }

        st_markerjev[spremenljivka]++;
    }
}

/**
 * handler za desni klik in dvojni klik na markerja
 * @param {type} spremenljivka - int - id spremenljivke
 * @param {type} marker
 * @param {type} map - google map
 */
function bindMarkerEventsVM(spremenljivka, marker, map) {
    google.maps.event.addListener(marker, "rightclick", function () {
        //removeMarker(spremenljivka, marker); // remove marker from array
        deleteMenu.open(map, null, null, spremenljivka, null, null, marker);
    });
    google.maps.event.addListener(marker, "dblclick", function () {
        //removeMarker(spremenljivka, marker); // remove marker from array
        deleteMenu.open(map, null, null, spremenljivka, null, null, marker);
    });
}

/**
 * All necessaries to remove marker - from map, DB, global variables
 * @param {type} spremenljivka - int - id spremenljivke
 * @param {type} marker
 */
function removeMarkerVM(spremenljivka, marker) {
    function callback() {
        marker.setMap(null); // set markers setMap to null to remove it from map
        //remove marker from marker array
        var index = allMarkers[spremenljivka].indexOf(marker);
        if (index > -1)
            allMarkers[spremenljivka].splice(index, 1);
        st_markerjev[spremenljivka]--;
        if (st_markerjev[spremenljivka] < 1) {
            //show warning text for 'no values yet'
            var no_value_warning = document.getElementById("variabla_no_value_" + spremenljivka);
            no_value_warning.style.display = "inline-block";
        }
    }
    inline_vrednost_delete(spremenljivka, marker.id, 0, callback);
}

/**
 * Function to set parameters for drawing a polygon on map
 * 
 * @param {type} spremenljivka - id spremenljivke
 * @returns {undefined}
 */
function drawMarkers(spremenljivka) {
    var map = document.getElementById("br_map_" + spremenljivka).gMap;

    var startDrawingMode = google.maps.drawing.OverlayType.MARKER;

    var icon = {
        url: "img_0/marker_text_off.png"
    };

    var drawingManager = new google.maps.drawing.DrawingManager({
        drawingMode: startDrawingMode,
        drawingControl: true,
        drawingControlOptions: {
            position: google.maps.ControlPosition.TOP_RIGHT,
            drawingModes: ['marker', 'polyline']
        },
        markerOptions: {icon: icon},
        polylineOptions: polylineOptions(map)
    });

    //listener when user creates a marker
    google.maps.event.addListener(drawingManager, 'markercomplete', function (marker) {

        //hide warning text for 'no values yet'
        var no_value_warning = document.getElementById("variabla_no_value_" + spremenljivka);
        no_value_warning.style.display = "none";

        //koordinate
        var pos = {
            lat: marker.getPosition().lat(),
            lng: marker.getPosition().lng()
        };

        marker.indentifier = getMarkerUniqueId(pos.lat, pos.lng, spremenljivka);
        allMarkers[spremenljivka].push(marker);

        // naslov se pridobi, da se klice geocoding
        GeocodingF(pos, function (data) {
            //ce ne vrne null - je nasel naslov
            if (data != null) {
                //createMarkerVnaprej(spremenljivka, data.formatted_address, '', marker);
                shraniMarker(spremenljivka, data.formatted_address, '', marker);
            } else {
                marker.setMap(null);
                //odpre se okno, ce je prislo do napake - null - (mozen je tudi prekratek delay med geocoding requesti)
                alert(lang['srv_resevanje_alert_location_not_found_map']);
            }
        });
    });

    //listener when user creates a line
    google.maps.event.addListener(drawingManager, 'polylinecomplete', function (line) {
        afterShapeCompleteSettingsVM(line, spremenljivka, map);
    });

    drawingManager.setMap(map);

    google.maps.event.addListener(map, 'click', function () {
        changeFocusLine(null);
    });
}

/**
 * Do some things when focus is changed when inculdin lines
 * @param {type} shape - shape that is clicked or wanted to be in focus
 * @returns {undefined}
 */
function changeFocusLine(shape) {
    if (active_shape != shape) {
        if (active_shape)
            active_shape.setOptions({editable: false});
        active_shape = shape;
    }
    blurTitleInfowindow(true);
}

/**
 * Trigger a blur() on titlein ifnowindow
 * @param {type} closeInfowindow - true to close infowindow afterwards
 * @returns {undefined}
 */
function blurTitleInfowindow(closeInfowindow) {
    if (infowindow.getMap()) {
        //if infowindow in opened, blur focus, so saving in DB can be triggered
        infowindow.content.getElementsByClassName("input_title")[0].blur();
        if (closeInfowindow)
            infowindow.close();
    }
}

/**
 * Polyline options holder
 * @param map google map, to put polyline in it
 * @returns {polylineOptions.PolylineAnonym$6}
 */
function polylineOptionsVM(map) {
    return {
        strokeColor: 'black',
        strokeColorOrg: 'black',
        strokeWeight: 4,
        clickable: true,
        editable: !viewMode,
        zIndex: 1,
        map: map
    };
}

/**
 * Sets all kind of settings after shape is completed
 * 
 * @param {google's shape} shape - polyline or polygon if already exists
 * @param {type} spremenljivka - id spremenljivke
 * @param {type} map - google map
 * @returns {undefined}
 */
function afterShapeCompleteSettingsVM(shape, spremenljivka, map) {
    st_shapes[spremenljivka]['last_id']++;
    shape.overlay_id = st_shapes[spremenljivka]['last_id'];
    shape.address = '';
    shape.infowin = makeShapeInfoWindow(spremenljivka, shape);

    saveNewLineInDB(spremenljivka, shape.getPath().getArray(), st_shapes[spremenljivka]['last_id']);

    var color = mapShapeColors[shape.overlay_id-1 % mapShapeColors.length];
    st_shapes[spremenljivka]['count']++;

    //set shape
    shape.setOptions({strokeColor: '#' + color});

    changeFocusLine(shape);

    //open infowindow
    infowindow.setContent(shape.infowin);
    var pathArr = shape.getPath().getArray();
    //set position to rounded middle point
    infowindow.setPosition(pathArr[Math.round((pathArr.length - 1) / 2)]);
    infowindow.open(map);

    setListenersShapeVM(spremenljivka, shape, map);

    //focus on input
    $("#map_input_overlay_id_" + spremenljivka + "_" + shape.overlay_id).focus();
}

/**
 * Creates and returns a html container for infowindow
 * @param {type} spremenljivka - id spremenljivke
 * @param {type} shape - googles shape - polyline or polygon
 * @param locked boolean true, if survey is locked in branching
 * @returns {makeShapeInfoWindow.container|Element}
 */
function makeShapeInfoWindow(spremenljivka, shape, locked) {
    //Create a div element for container.
    var container = document.createElement("div");
    //margin, da se pri italic ne odreze zadja crka
    container.style.cssText = 'margin-right:1px';

    //Create a textarea for the input text
    var textBoxTitle = document.createElement("input");
    textBoxTitle.style.cssText = "font-weight:bold; font-size:1em !important; display: block; float: left;";
    textBoxTitle.type = "text";
    textBoxTitle.value = shape.address;
    textBoxTitle.className = "input_title";
    textBoxTitle.id = "map_input_overlay_id_" + spremenljivka + "_" + shape.overlay_id;
    container.appendChild(textBoxTitle);
    //container.appendChild(document.createElement("br"));
    //disable changing title if locked
    if (locked)
        textBoxTitle.disabled = "disabled";

    //dont show delete image if survey is locked and no need for listeners
    if (!locked) {
        var deleteImg = document.createElement("span");
        deleteImg.className = "faicon delete icon-grey_dark_link";
        deleteImg.title = lang['srv_vprasanje_delete_line_map'];
        deleteImg.style.cssText = "height:1.65em; display: block; float: left; margin-left:7px; cursor: pointer;";
        container.appendChild(deleteImg);

        //ko se spremeni textarea v windowinfo, spremeni value inputa za text
        google.maps.event.addDomListener(deleteImg, "click", function () {
            if (confirm(lang['srv_vprasanje_delete_line_confirm_map']))
                deleteLineInDB(spremenljivka, shape);
        });

        //ko se spremeni textarea v windowinfo, spremeni value inputa za text
        google.maps.event.addDomListener(textBoxTitle, "change", function () {
            //save title of SHAPE in DB
            var lineData = {anketa: srv_meta_anketa_id, spr_id: spremenljivka,
                overlay_id: shape.overlay_id, address: textBoxTitle.value};
            $.post('ajax.php?t=branching&a=edit_naslov_polyline', lineData);
        });
    }

    return container;
}

/**
 * Set Liteners when editing a shape
 * @param {type} spremenljivka - id spremenljivke
 * @param {type} shape - googles shape to set listeners on
 * @param {type} map - google map
 * @param locked boolean true, if survey is locked in branching
 * @returns {undefined}
 */
function setListenersShapeVM(spremenljivka, shape, map, locked) {
    //listener ob kliku na marker
    google.maps.event.addListener(shape, 'click', function (ev) {
        //pusti editable false, ce je locked
        if (!locked)
            this.setOptions({editable: true});
        changeFocusLine(this);

        //open infowindow
        infowindow.setContent(this.infowin);
        infowindow.setPosition(ev.latLng);
        infowindow.open(map);

        $("#map_input_overlay_id_" + spremenljivka + "_" + shape.overlay_id).focus();
    });

    //no need for listeners if survey is locked
    if (!locked) {
        //when editing end or start point
        google.maps.event.addListener(shape.getPath(), 'set_at', function (index, obj) {
            saveEditedLineInDB(spremenljivka, shape.getPath().getArray(), shape.overlay_id);
        });
        //when inserting new point (break old one)
        google.maps.event.addListener(shape.getPath(), 'insert_at', function (index, obj) {
            saveEditedLineInDB(spremenljivka, shape.getPath().getArray(), shape.overlay_id);
        });
        //when deleting a point -> undo 
        google.maps.event.addListener(shape.getPath(), 'remove_at', function (index, obj) {
            saveEditedLineInDB(spremenljivka, shape.getPath().getArray(), shape.overlay_id);
        });

        setDeleteMenu(shape, map, spremenljivka)
    }
}

/**
 * Sets rightclick and dblclick listeners to open delete menu
 * @param {type} shape - shape to set listeners on
 * @param {type} map  - map on which to hover menu (shape's map)
 * @param {type} spremenljivka - id of variable
 * @returns {undefined}
 */
function setDeleteMenu(shape, map, spremenljivka) {
    google.maps.event.addListener(shape, 'rightclick', function (e) {
        // Check if click was on a vertex control point
        if (e.vertex == undefined) {
            return;
        }
        //removeVertexVM(spremenljivka, shape, e.vertex);
        deleteMenu.open(map, shape, e.vertex, spremenljivka, null, null);
    });
    google.maps.event.addListener(shape, 'dblclick', function (e) {
        // Check if click was on a vertex control point
        if (e.vertex == undefined) {
            return;
        }
        //removeVertexVM(spremenljivka, shape, e.vertex);
        deleteMenu.open(map, shape, e.vertex, spremenljivka, null, null);
    });
}

/**
 * Creates json data of shape to later insert or update DB
 * @param {type} spremenljivka - id spremenljivke
 * @param {type} pathArray - array of path of shape (shape.getPath().getArray())
 * @param {type} overlay_id - id of overlay
 * @returns {createLineData.lineData}
 */
function createLineData(spremenljivka, pathArray, overlay_id) {
    var lineData = {anketa: srv_meta_anketa_id, spr_id: spremenljivka, overlay_id: overlay_id, path: []};

    for (var i = 0, n = pathArray.length; i < n; i++) {
        var latLng = pathArray[i];
        var vrstni_red = i + 1;
        lineData.path[i] = {lat: latLng.lat(), lng: latLng.lng(), vrstni_red: i + 1};
    }

    return lineData;
}

/**
 * Saves newly created line in DB
 * @param {type} spremenljivka - id spremenljivke
 * @param {type} pathArray - array of path of shape (shape.getPath().getArray())
 * @param {type} overlay_id - id of overlay
 * @returns {undefined}
 */
function saveNewLineInDB(spremenljivka, pathArray, overlay_id) {
    var lineData = createLineData(spremenljivka, pathArray, overlay_id);
    $.post('ajax.php?t=branching&a=save_polyline', lineData);
}

/**
 * Saves edited line in DB
 * @param {type} spremenljivka - id spremenljivke
 * @param {type} pathArray - array of path of shape (shape.getPath().getArray())
 * @param {type} overlay_id - id of overlay
 * @returns {undefined}
 */
function saveEditedLineInDB(spremenljivka, pathArray, overlay_id) {
    var lineData = createLineData(spremenljivka, pathArray, overlay_id);
    $.post('ajax.php?t=branching&a=edit_polyline', lineData);
}

/**
 * Deletes line in DB
 * @param {type} spremenljivka - id spremenljivke
 * @param {type} shape - shape to be deleted
 * @returns {undefined}
 */
function deleteLineInDB(spremenljivka, shape) {
    shape.setMap(null);
    st_shapes[spremenljivka]['count']--;
    blurTitleInfowindow(true);
    var lineData = {anketa: srv_meta_anketa_id, spr_id: spremenljivka, overlay_id: shape.overlay_id};
    $.post('ajax.php?t=branching&a=delete_polyline', lineData);
}

/**
 * Removes a vertex/point of shape and if it after last it clears the map
 * and sets drawing mode on again
 * @param {type} spremenljivka - id spremenljivke
 * @param {google's shape} shape - polyline or polygon if already exists
 * @param {type} vertex - vertex or point which was selected
 * @returns {undefined}
 */
/*function removeVertexVM(spremenljivka, shape, vertex) {
    if (!shape || vertex == undefined){
        //closes menu
        this.close();
        return;
    }

    shape.getPath().removeAt(vertex);
    saveEditedLineInDB(spremenljivka, shape.getPath().getArray(), shape.overlay_id);

    if (shape.getPath().length < 2) {
        //remove shape
        deleteLineInDB(spremenljivka, shape);
    }
    //closes menu
    this.close();
}*/