/**
 * modul MAZA - mobilna aplikacija za anketirance
 * Uroš Podkrižnik 17.10.2017
 */

//default number of meters of radius for geofences
var maza_default_radius = 50;
//days relative to today to start date of repeater
var maza_start_day_repeater = -1;
//days relative to today to end date of repeater
var maza_end_day_repeater = 1;
//is geofencing on
var geofencing_on = false;

function maza_on_off() {
    $.post('ajax.php?t=MAZA&a=maza_on_off', {on_off: $('#advanced_module_maza').is(':checked'), anketa: srv_meta_anketa_id});
}

function doMAZAInactiveUsersExportCSV() {
    $.post('ajax.php?t=MAZA&a=InactiveUsersExportCSV', {anketa: srv_meta_anketa_id});
}

function onAlarmsFormsLoad() {
    var maza_submit_repeater = $('#maza_submit_repeater');
    var maza_submit_alarms = $('#maza_submit_alarms');
    //array of range from - to for input everywhichday in form of alarms or repeater
    var maza_everywhichday_range = [2, 6];

    //ALARM
    $('input[name="maza_title"]').bind('input', function () {
        maza_toggle_submit_alarms();
    });
    $('input[name="maza_message"]').bind('input', function () {
        maza_toggle_submit_alarms();
    });
    $('input[name="maza_alarm_intervalby"]').bind('change', function () {
        $('#maza_alarm_div_weekly').toggle(($(this).val() === 'weekly'));
        $('#maza_alarm_div_daily').toggle(($(this).val() === 'daily'));
        maza_toggle_submit_alarms();
        $.post('ajax.php?t=MAZA&a=changeRepeatBy', {anketa: srv_meta_anketa_id, maza_repeatby: $(this).val(), maza_table: "alarms"});
    });
    $('input[name="maza_alarm_timeinday[]"]').bind('change', function () {
        var times = [];
        $('input:checkbox[name="maza_alarm_timeinday[]"]:checked').each(function () {
            times.push($(this).val());
        });
        maza_toggle_submit_alarms();
        $.post('ajax.php?t=MAZA&a=changeTimeInDay', {anketa: srv_meta_anketa_id, maza_time_in_day: times, maza_table: "alarms"});
    });
    $('input[name="maza_alarm_dayinweek[]"]').bind('change', function () {
        var times = [];
        $('input:checkbox[name="maza_alarm_dayinweek[]"]:checked').each(function () {
            times.push($(this).val());
        });
        maza_toggle_submit_alarms();
        $.post('ajax.php?t=MAZA&a=changeDayInWeek', {anketa: srv_meta_anketa_id, maza_day_in_week: times, maza_table: "alarms"});
    });
    $('input[name="maza_alarm_everywhichday"]').bind('input', function () {
        if ($(this).val() >= maza_everywhichday_range[0] && $(this).val() <= maza_everywhichday_range[1])
            $.post('ajax.php?t=MAZA&a=changeEveryWhichDay', {anketa: srv_meta_anketa_id, maza_every_which_day: $(this).val(), maza_table: "alarms"});
        maza_toggle_submit_alarms();
    });

    //REPEATER
    $('input[name="maza_repeater_intervalby"]').bind('change', function () {
        $('#maza_repeater_div_weekly').toggle(($(this).val() === 'weekly'));
        $('#maza_repeater_div_daily').toggle(($(this).val() === 'daily'));
        //$.post('ajax.php?t=MAZA&a=changeRepeatBy', {anketa: srv_meta_anketa_id, maza_repeatby: $(this).val(), maza_table: "repeaters"});
        maza_toggle_submit_repeater();
    });
    $('input[name="maza_repeater_timeinday[]"]').bind('change', function () {
        /*var times = [];
        $('input:checkbox[name="maza_repeater_timeinday[]"]:checked').each(function () {
            times.push($(this).val());
        });
        if(times)
            $('#maza_repeater_div_everyday').css("border", "none");*/
        maza_toggle_submit_repeater();
        //$.post('ajax.php?t=MAZA&a=changeTimeInDay', {anketa: srv_meta_anketa_id, maza_time_in_day: times, maza_table: "repeaters"});
    });
    $('input[name="maza_repeater_dayinweek[]"]').bind('change', function () {
        /*var times = [];
        $('input:checkbox[name="maza_repeater_dayinweek[]"]:checked').each(function () {
            times.push($(this).val());
        });*/
        /*if(times)
            $('#maza_repeater_div_weekly').css("border", "none");*/
        maza_toggle_submit_repeater();
        //$.post('ajax.php?t=MAZA&a=changeDayInWeek', {anketa: srv_meta_anketa_id, maza_day_in_week: times, maza_table: "repeaters"});
    });
    $('input[name="maza_repeater_everywhichday"]').bind('input', function () {
        /*if ($(this).val() > 1 && $(this).val() < 7){
            //$('#maza_repeater_div_everywhichday').css("border", "none");
            //$.post('ajax.php?t=MAZA&a=changeEveryWhichDay', {anketa: srv_meta_anketa_id, maza_every_which_day: $(this).val(), maza_table: "repeaters"});
        }*/
        maza_toggle_submit_repeater();
    });
    $('input[name="maza_repeater_date_start"]').bind('change', function () {
        var pickedDateSplit = this.value.split(".");
        var pickedDate = new Date(pickedDateSplit[2], pickedDateSplit[1]-1, pickedDateSplit[0]);
        var pickedDateFormated = $.datepicker.formatDate('dd.mm.yy', pickedDate);
        this.value = pickedDateFormated;

        var pickedDateTimestamp = pickedDate.getTime();
        var nowDate = new Date();
        var todayDateTimestamp = (new Date(nowDate.getFullYear(), nowDate.getMonth(), nowDate.getDate())).getTime();

        //if date is not valid, clear it
        if(!pickedDateTimestamp || pickedDateTimestamp < todayDateTimestamp+(maza_start_day_repeater*86400000)){
            this.value = "";
            $("#maza_repeater_start_date_warning").hide();
        }
        maza_toggle_submit_repeater();
    });
    $('input[name="maza_repeater_date_end"]').bind('change', function () {
        var pickedDateSplit = this.value.split(".");
        var pickedDate = new Date(pickedDateSplit[2], pickedDateSplit[1]-1, pickedDateSplit[0]);
        var pickedDateFormated = $.datepicker.formatDate('dd.mm.yy', pickedDate);
        this.value = pickedDateFormated;

        var pickedDateTimestamp = pickedDate.getTime();
        var nowDate = new Date();
        var todayDateTimestamp = (new Date(nowDate.getFullYear(), nowDate.getMonth(), nowDate.getDate())).getTime();

        //if date is not valid, clear it
        if(!pickedDateTimestamp || pickedDateTimestamp < todayDateTimestamp+(maza_end_day_repeater*86400000)){
            this.value = "";
        }
        maza_toggle_submit_repeater();
    });

    /**
     * Toggle submition button for alarms form
     */
    function maza_toggle_submit_alarms() {
        if (!$('input[name="maza_message"]').val().length > 0)
            maza_submit_alarms.hide();
        else {
            if ($('input:checkbox[name="maza_alarm_timeinday[]"]:checked').length === 0)
                maza_submit_alarms.hide();
            else if ($('input[name="maza_alarm_intervalby"]:checked').val() === "everyday")
                maza_submit_alarms.show();
            else if ($('input[name="maza_alarm_intervalby"]:checked').val() === "daily") {
                var everywhichday = $('input[name="maza_alarm_everywhichday"]').val();
                if (everywhichday >= maza_everywhichday_range[0] && everywhichday <= maza_everywhichday_range[1])
                    maza_submit_alarms.show();
                else
                    maza_submit_alarms.hide();
            } else if ($('input[name="maza_alarm_intervalby"]:checked').val() === "weekly") {
                if ($('input:checkbox[name="maza_alarm_dayinweek[]"]:checked').length === 0)
                    maza_submit_alarms.hide();
                else
                    maza_submit_alarms.show();
            }
        }
    }

    /**
     * Toggle submition button for repeater form
     */
    /*function maza_check_submit_repeater() {
        if($('#maza_repeater_date_start').val().length > 0){
            if ($('input:checkbox[name="maza_repeater_timeinday"]:checked').length === 0){
                $('#maza_repeater_div_everyday').css("border", "red solid 1px");
                return false;
            }
            else if ($('input[name="maza_repeater_intervalby"]:checked').val() === "everyday")
                return true;
            else if ($('input[name="maza_repeater_intervalby"]:checked').val() === "daily") {
                var everywhichday = $('input[name="maza_repeater_everywhichday"]').val();
                if (everywhichday >= maza_everywhichday_range[0] && everywhichday <= maza_everywhichday_range[1])
                    return true;
                else{
                    $('#maza_repeater_div_daily').css("border", "red solid 1px");
                    return false;
                }
            } else if ($('input[name="maza_repeater_intervalby"]:checked').val() === "weekly") {
                if ($('input:checkbox[name="maza_repeater_dayinweek"]:checked').length === 0){
                    $('#maza_repeater_div_weekly').css("border", "red solid 1px");
                    return false;
                }
                else
                    return true;
            }
        }
    }*/
    
     /**
     * Toggle submition button for repeater form
     */
    function maza_toggle_submit_repeater() {
        var startVal = $('#maza_repeater_date_start').val();
        var endVal = $('#maza_repeater_date_end').val();
        var datesValid = true;

        if(startVal && endVal){
            var startDateSplit = startVal.split(".");
            var startDate = new Date(startDateSplit[2], startDateSplit[1]-1, startDateSplit[0]);
            var endDateSplit = endVal.split(".");
            var endDate = new Date(endDateSplit[2], endDateSplit[1]-1, endDateSplit[0]);
            datesValid = startVal < endVal;
        }
        
        if(!startVal || (startVal && !datesValid))
            maza_submit_repeater.hide();
        else if ($('input:checkbox[name="maza_repeater_timeinday[]"]:checked').length === 0)
            maza_submit_repeater.hide();
        else if ($('input[name="maza_repeater_intervalby"]:checked').val() === "everyday")
            maza_submit_repeater.show();
        else if ($('input[name="maza_repeater_intervalby"]:checked').val() === "daily") {
            var everywhichday = $('input[name="maza_repeater_everywhichday"]').val();
            if (everywhichday >= maza_everywhichday_range[0] && everywhichday <= maza_everywhichday_range[1])
                maza_submit_repeater.show();
            else
                maza_submit_repeater.hide();
        } else if ($('input[name="maza_repeater_intervalby"]:checked').val() === "weekly") {
            if ($('input:checkbox[name="maza_repeater_dayinweek[]"]:checked').length === 0)
                maza_submit_repeater.hide();
            else
                maza_submit_repeater.show();
        }
    }

    maza_toggle_submit_alarms();
    maza_toggle_submit_repeater();
    mazaChooseDate(maza_start_day_repeater, $("#maza_repeater_date_start"));
    mazaChooseDate(maza_end_day_repeater, $("#maza_repeater_date_end"));
}

/**
 * Sets datepicke on start of repeater input
 * @param {type} maza_start_day_repeater - int of days to start calendar relative to today
 * @param {type} element - element to set calendar on
 * @returns {undefined}
 */
function mazaChooseDate(maza_start_day_repeater, element) {
    element.datepicker({
        //selectOtherMonths: true,
        //changeMonth: true,
        minDate: maza_start_day_repeater,
        dateFormat: "dd.mm.yy",
        showAnim: "slideDown",
        //showOn: "button",
        //buttonImage: srv_site_url + "admin/survey/script/calendar/calendar.gif",
        //buttonImageOnly: true,
        onSelect: function (dateText, inst) {
            element.trigger('change');
            return false;
        }
    });
}

/**
 * When geofencing form is loaded
 * @param {type} ank_id - ID of survey
 * @param {type} geo_on - is geofencing on
 * @returns {undefined}
 */
function onGeofencingFormsLoad(ank_id, geo_on){
    geofencing_on = geo_on;
    //preveri, ce je google API ze includan (ce se je vedno icludal, je prislo do errorjev)
    if((typeof google === 'object' && typeof google.maps === 'object')){
        mazaMaps(ank_id);
    }
    else{
        //main/app/contollers/js/Maps/Declaration.js
        mapsAPIseNi (function(){mazaMaps(ank_id);});
    }
    
    //disable/enable notification setting based on checkbox
    $('#maza_geofence_trigger_survey').bind('change', function () {
        if(this.checked){
            $("#maza_title").prop('disabled', true);
            $("#maza_message").prop('disabled', true);
        }
        else{
            $("#maza_title").prop('disabled', false);
            $("#maza_message").prop('disabled', false);
        }
    });
}

/**
 * Toggle AR interval on AR checkbox changed
 * @param {type} checkbox - checkbox element
 * @returns {undefined}
 */
function toggleARInterval(checkbox){
        if (checkbox.checked)
                $('#maza_ar_interval_div').show();
            else
                $('#maza_ar_interval_div').hide();
}

//nastavi mapo
function mazaMaps(ank_id){
    //mapType = tip zemljevida, ki bo prikazan. Recimo za satelitsko sliko google.maps.MapTypeId.SATELLITE (možno še .ROADMAP)
    var mapType = google.maps.MapTypeId.ROADMAP;
    //centerInMap = string naslova, kaj bo zajel zemljevid. Rec. Slovenija / ali Ljubljana
    //var centerInMap = '';

    //pridobi parametre za centriranje mape in jo nastavi za kasnejso uporabo
    /*var centerInMapKoordinate = <?php echo json_encode($fokus_koordinate)?>;
    if(centerInMapKoordinate)
        centerInMapKoordinate = JSON.parse(centerInMapKoordinate);*/

    //Deklaracija potrebnih stvari za delovanje in upravljanje google maps JS API
    var mapOptions = {
            disableDoubleClickZoom: true,
            mapTypeId: mapType
    };

    //ce je v bazi naslov enak vpisanemu v nastavitvah, nastavi po parametrih
    /*if(centerInMapKoordinate.fokus === centerInMap || centerInMap === ''){
        mapOptions.center = {lat:  parseFloat(centerInMapKoordinate.center.lat), 
            lng:  parseFloat(centerInMapKoordinate.center.lng)};
        mapOptions.zoom = parseInt(centerInMapKoordinate.zoom);
    }   */
    //ce ni parametrov v bazi ali pa je nanovo kreirana spremenljivka, nastavi na Slovenijo
    //else if(!centerInMapKoordinate && centerInMap === '<?php echo $default_centerInMap; ?>'){
        mapOptions.center = {lat: 46.151241, lng: 14.995463};
        mapOptions.zoom = 7;
    //}

    //deklaracija zemljevida
    var mapdiv = document.getElementById("maza_map_geofencing");
    var map = new google.maps.Map(mapdiv, mapOptions);
    //to se kasneje uporabi za pridobitev mape z id-em spremenljivke
    mapdiv.gMap = map;
    //deklaracija mej/okvira prikaza na zemljevidu
    bounds['maza_map_geofencing'] = new google.maps.LatLngBounds();

    //deklaracija geocoderja (API)
    if(!geocoder)
        geocoder = new google.maps.Geocoder();  
    if(!infowindow)
        infowindow = new google.maps.InfoWindow();
    
    allMarkers['maza_map_geofencing'] = [];
    
    if(!geofencing_on){
        searchBox('maza_map_geofencing', function doAfterPlaceFromSearchBox(pos, address){
            //reset bounds, so we can focus only on this geofence
            bounds['maza_map_geofencing'] = new google.maps.LatLngBounds();
            //save geofence
            maza_saveGeofence(address, maza_create_basic_circle(pos, address, maza_default_radius, true));
        });

        //set click event on map
        maza_klikNaMapo();
    }
        
    //ni ok, ce se klice globalna spremenljivka srv_meta_anketa_id, ker se ne vedno pravi cas nastavi
    $.post('ajax.php?t=MAZA&a=get_all_geofences', {anketa: ank_id},
                function (data) {
                    if(data.length > 0)
                        //id - id markerja v bazi
                        map_data_fill_vnaprej_geofences(JSON.parse(data));
                });
}

/**
 * Create and fill existing geofences in map
 * @param map_data JSON Object
 */
function map_data_fill_vnaprej_geofences(map_data) {
    for (var row in map_data) {
        var row_object = map_data[row];
        var pos = {lat: row_object.lat, lng: row_object.lng};

        maza_createCircleVnaprej(row_object.address, maza_create_basic_circle(pos, row_object.address, row_object.radius, false), row_object.id, row_object.name);
    }
}


/**
 * Ustvari in vrne basic circle, na mapo spremenljivke z danimi koordinatami
 * @param {type} pos - koordinate - objekt {lat: ???, lng: ???}
 * @param {string} addressFromSearchBox - address if adding marker from searchBox, null otherwise
 *                  (used for ignoring duplicates)
 * @returns {google.maps.Circle}
 */
function maza_create_basic_circle(pos, address, radius, fromSearchBox) {
    var circle = null;
	
	if(fromSearchBox === undefined) {
		fromSearchBox = false;
	}
	
    //pridobi mapo spremenljivke
    var map = document.getElementById("maza_map_geofencing").gMap;

    if(fromSearchBox) 
        circle = address ? findMarkerFromAddress(address, "maza_map_geofencing") : null;

    if (!circle) {
        //kreiraj marker
        /*var marker = new google.maps.Marker({
            position: new google.maps.LatLng(pos.lat, pos.lng),
            map: map,
            identifier: getMarkerUniqueId(pos.lat, pos.lng, "maza_map_geofencing")//,
            //icon: {url: "img_0/marker_text_off.png"}
        });*/

        // Add circle overlay and bind to marker
        circle = new google.maps.Circle({
          center: new google.maps.LatLng(pos.lat, pos.lng),
          map: map,
          radius: parseFloat(radius), //in meters
          editable: !geofencing_on,
          fillColor: 'red'
        });
        //circle.bindTo('center', marker, 'position');
        
        //add marker in array
        allMarkers["maza_map_geofencing"].push(circle);

        return circle;
    } else {
        //trigger click on marker, to focus it and open infowindow
        //google.maps.event.trigger(circle, 'click');

        //reset bounds
        bounds['maza_map_geofencing'] = new google.maps.LatLngBounds();
        //add to bounds 
        bounds["maza_map_geofencing"].union(circle.getBounds());
        //zemljevid se prilagodi okviru
        map.fitBounds(bounds["maza_map_geofencing"]);
        return null
    }
}

/**
 * funkcija, ki kreira osnovni geofence (rdec)
 * @param {type} address - String - label o informacijah markerja
 * @param {type} marker - google maps new created circle
 * @returns {undefined}
 */
function maza_saveGeofence(address, circle) {
    if (circle != null) {
        //save new marker in DB
        $.post('ajax.php?t=MAZA&a=insert_geofence', {address: address,
            lat: circle.getCenter().lat(), lng: circle.getCenter().lng(), radius: maza_default_radius, anketa: srv_meta_anketa_id},
                function (id) {
                    //id - id markerja v bazi
                    maza_createCircleVnaprej(address, circle, id, null);
                });
    }
}

/**
 * Set what to do when click event on map
 * @returns {undefined}
 */
function maza_klikNaMapo() {
    //pridobi mapo spremenljivke
    var map = document.getElementById("maza_map_geofencing").gMap;
    
    // ko user klikne na mapo, funkcija vrne pozicijo (koordinate - position) ter kreira
    google.maps.event.addListener(map, 'click', function (event) {

        //koordinate
        var pos = {
            lat: event.latLng.lat(),
            lng: event.latLng.lng()
        };

        // naslov se pridobi, da se klice geocoding
        GeocodingF(pos, function (data) {
            //ce ne vrne null - je nasel naslov
            if (data != null) {
                //reset bounds, to set focus only on this geofence
                bounds['maza_map_geofencing'] = new google.maps.LatLngBounds();
                
                //kreira marker na lokaciji, kjer je uporabnik kliknil
                maza_saveGeofence(data.formatted_address, maza_create_basic_circle(pos, data.formatted_address, maza_default_radius, false));
            } else {
                //odpre se okno, ce je prislo do napake - null - (mozen je tudi prekratek delay med geocoding requesti)
                alert(lang['srv_resevanje_alert_location_not_found_map']);
            }
        });
        

        return pos;
    });
}             

/**
 * funkcija, ki kreira osnovni circle (rdec)
 * @param {type} address - String - label o informacijah markerja
 * @param {type} circle - google maps new created circle
 * @param {type} id - id vrednosti markerja iz tabele geofence
 * @param {type} name - interno ime geofenca
 * @returns {undefined}
 */
function maza_createCircleVnaprej(address, circle, id, name) {
    if (circle != null) {
        //pridobi mapo spremenljivke
        var map = document.getElementById("maza_map_geofencing").gMap;

        circle.address = address;
        circle.id = id;
        if(name)
            circle.name = name;
        circle.infowin = makeGeofenceInfoWindow(circle);
        setListenersGeofence(circle, map);

        //markers[markerId] = marker; // cache marker in markers object
        //dont set listeners if survey is locked
        //bindMarkerEventsVM("maza_map_geofencing", marker, map); // bind right click event to marker
        //maza_setDeleteMenu(circle, map, "maza_map_geofencing");

        //Create a div element for container.
        /*var container = document.createElement("div");
        //margin, da se pri italic ne odreze zadja crka
        container.style.cssText = 'margin-right:1px';

        //Create a label element for address.
        var address = document.createElement("label");
        address.innerHTML = '<i>' + address + '</i>';
        address.style.cssText = 'font-size:0.85em;';
        container.appendChild(address);*/

        //listener ob kliku na marker
        /*google.maps.event.addListener(marker, 'click', function () {
            //if infowindow in opened, blur focus, so saving in DB can be triggered
            blurTitleInfowindow(false);
            infowindow.setContent(container);
            infowindow.open(map, marker);
        });*/

        google.maps.event.addListener(circle,'radius_changed',function(){
            //console.log('radius_changed '+circle.getRadius());
            $.post('ajax.php?t=MAZA&a=update_geofence', {id: circle.id, radius: circle.getRadius(), anketa: srv_meta_anketa_id});
            circle.radius = circle.getRadius();
            circle.infowin = makeGeofenceInfoWindow(circle);
            infowindow.close();
        });
        google.maps.event.addListener(circle,'center_changed',function(){
            //console.log('center_changed '+circle.getCenter());
            //koordinate
            var pos = {
                lat: circle.getCenter().lat(),
                lng: circle.getCenter().lng()
            };

            // naslov se pridobi, da se klice geocoding
            GeocodingF(pos, function (data) {
                //ce ne vrne null - je nasel naslov
                if (data != null) {
                    //save changes in DB
                    $.post('ajax.php?t=MAZA&a=update_geofence', {id: circle.id, lat: circle.getCenter().lat(), lng: circle.getCenter().lng(), address: data.formatted_address, anketa: srv_meta_anketa_id});
                    circle.address = data.formatted_address;
                } else {
                    //did not found address, save empty string
                    $.post('ajax.php?t=MAZA&a=update_geofence', {id: circle.id, lat: circle.getCenter().lat(), lng: circle.getCenter().lng(), address: '', anketa: srv_meta_anketa_id});
                    circle.address = 'Unknown address';
                }
                circle.infowin = makeGeofenceInfoWindow(circle);
                infowindow.close();
            });
            
        });

        //add to bounds 
        bounds["maza_map_geofencing"].union(circle.getBounds());
        
        //zemljevid se prilagodi okviru
        map.fitBounds(bounds["maza_map_geofencing"]);
    }
}

/**
 * Creates and returns a html container for infowindow
 * @param {type} shape - googles shape - polyline or polygon
 * @returns {makeShapeInfoWindow.container|Element}
 */
function makeGeofenceInfoWindow(shape) {
    //Create a div element for container.
    var container = document.createElement("div");
    //margin, da se pri italic ne odreze zadja crka
    container.style.cssText = 'margin-right:1px';

    //Create a textarea for the input text
    var textBoxTitle = document.createElement("input");
    textBoxTitle.style.cssText = "font-weight:bold; font-size:1em !important; display: block; float: left;";
    textBoxTitle.type = "text";
    textBoxTitle.placeholder = lang['srv_maza_geofence_infowin_name'];
    if(shape.name)
        textBoxTitle.value = shape.name;
    textBoxTitle.className = "input_title";
    textBoxTitle.maxLength = "20";
    textBoxTitle.id = "map_input_overlay_id_" + shape.id;
    if(geofencing_on)
        textBoxTitle.disabled=true;
    container.appendChild(textBoxTitle);
    //ko se spremeni textarea v windowinfo, spremeni value inputa za text
    google.maps.event.addDomListener(textBoxTitle, "change", function () {
        //save title of marker in DB
        $.post('ajax.php?t=MAZA&a=update_geofence_name', {id: shape.id, name: textBoxTitle.value,
            anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id});
    });

    if(!geofencing_on){
        var deleteImg = document.createElement("span");
        deleteImg.className = "faicon delete icon-grey_dark_link";
        deleteImg.title = lang['srv_vprasanje_delete_line_map'];
        deleteImg.style.cssText = "height:1.65em; display: block; float: right; margin-left:7px; cursor: pointer;";
        container.appendChild(deleteImg);
        
        //ko se spremeni textarea v windowinfo, spremeni value inputa za text
        google.maps.event.addDomListener(deleteImg, "click", function () {
            if (confirm(lang['srv_maza_geofence_delete_confirm_map']))
                maza_delete_geofence(shape);
        });
    }
    
    container.appendChild(document.createElement("br"));
    
    //Create a label element for address.
    var address = document.createElement("label");
    address.innerHTML = '<i>' + shape.address + '</i>';
    address.style.cssText = 'font-size:0.85em; cursor: default;';
    container.appendChild(address);
    container.appendChild(document.createElement("br"));
    
    //Create a label element for radius.
    var radius = document.createElement("label");
    radius.innerHTML = lang['srv_maza_geofence_infowin_radius'] + Math.round(shape.radius) + lang['srv_maza_geofence_infowin_radius_unit'];
    radius.style.cssText = 'font-size:0.85em;cursor: default;';
    container.appendChild(radius);
    container.appendChild(document.createElement("br"));

    //ko se spremeni textarea v windowinfo, spremeni value inputa za text
    /*google.maps.event.addDomListener(textBoxTitle, "change", function () {
        //save title of SHAPE in DB
        var lineData = {anketa: srv_meta_anketa_id, spr_id: spremenljivka,
            overlay_id: shape.overlay_id, address: textBoxTitle.value};
        $.post('ajax.php?t=branching&a=edit_naslov_polyline', lineData);
    });*/

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
function setListenersGeofence(shape, map) {
    //listener ob kliku na marker
    google.maps.event.addListener(shape, 'click', function (ev) {
        //changeFocusLine(this);

        //open infowindow
        infowindow.setContent(this.infowin);
        infowindow.setPosition(ev.latLng);
        infowindow.open(map);

        $("#map_input_overlay_id_" + shape.id).focus();
    });
}

/**
 * Sets rightclick and dblclick listeners to open delete menu
 * @param {type} shape - shape to set listeners on
 * @param {type} map  - map on which to hover menu (shape's map)
 * @param {type} spremenljivka - id of variable
 * @returns {undefined}
 */
function maza_delete_geofence(circle) {
    $.post('ajax.php?t=MAZA&a=delete_geofence', {id: circle.id, anketa: srv_meta_anketa_id},
        function (data){
            if(data = 'OK')
                circle.setMap(null);
                infowindow.close();
        });
}

/**
 * Sets rightclick and dblclick listeners to open delete menu
 * @param {type} shape - shape to set listeners on
 * @param {type} map  - map on which to hover menu (shape's map)
 * @param {type} spremenljivka - id of variable
 * @returns {undefined}
 */
function maza_setDeleteMenu(shape, map, spremenljivka) {
    google.maps.event.addListener(shape, 'rightclick', function (e) {
        deleteMenu.open_shape(map, shape, e.latLng, spremenljivka);
    });
    google.maps.event.addListener(shape, 'dblclick', function (e) {
        deleteMenu.open_shape(map, shape, e.latLng, spremenljivka);
    });
}

/**
 * Onclick function when saving repeater
 * @returns {undefined}
 */
function maza_repeater_submit_button_click(){
    var inputVal = $("#maza_repeater_date_start").val();
    var pickedDateSplit = inputVal.split(".");
    var pickedDate = new Date(pickedDateSplit[2], pickedDateSplit[1]-1, pickedDateSplit[0]);
        
    var pickedDateTimestamp = pickedDate.getTime();
    var nowDate = new Date();
    var todayDateTimestamp = (new Date(nowDate.getFullYear(), nowDate.getMonth(), nowDate.getDate())).getTime();

    //date is in starting range, show warning
    if(pickedDateTimestamp <= todayDateTimestamp){
        var result = confirm(lang['srv_maza_repeater_edit_warning_alert']);
        if(result)
            $('#maza_save_repeater_form').submit();
    }
    //date is in the future, all ok, save it
    else
        $('#maza_save_repeater_form').submit();
}

/**
 * Onclick function when finishing repeater
 * @returns {undefined}
 */
function maza_repeater_cancel_click(){
    var result = confirm(lang['srv_maza_repeater_finish_warning_alert']);
    if(result)
        $('#maza_cancel_repeater_form').submit();
}
