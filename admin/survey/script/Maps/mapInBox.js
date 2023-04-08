//map_data_holder postane holder id-jev potrebnih za prikaz markerjev
var map_data_holder = {};
//id spremenljivke, ki bo prikazana v pojavnem oknu
var spremenljivka_box = 0;
    
//Procedura, ki preveri ce je gogle maps API ze includan 
function googleMapsAPIProcedura(after){
    //preveri, ce je google API ze includan (ce se je vedno nanovo icludal, je prislo do errorjev)
    if((typeof google === 'object' && typeof google.maps === 'object')){
        //API je naloadan, inicializiraj mapo
        after();
    }
    else{
        mapsAPIseNi(after);
    }
}

//kreiraj colorbox (popup, kjer bo zemljevid)
$(document).ready(function () {
    $(".fMap").colorbox({
        scrolling:false,
        width:"80%",
        height:"80%",
        title: "",
        html:'<div id="map_canvas_all" style="width:100%;height:100%"></div>',
        onComplete:function(){ 
            if(!$(".fMap").hasClass("rawData")){
                $('#colorbox').addClass("divPopUp");
                getMapDataAjax(); 
            }
            else{
                googleMapsAPIProcedura(initializeMapGeneralForIPs);
            }
        }
    });
});

/**
 * ob kliku na link prenesi podatke o zeljeni spremenljivki
 * @param {type} sprid - int - id spremenljivke
 * @param {type} usrid - int - id userja
 * @param {type} loopid - int - id loopa
 * @param {type} ankid - int - id ankete
 * @param {type} ajaxCall - string - call to ajax function mapData or mapDataAll
 * @returns {undefined}
 */
function passMapData(sprid, usrid, loopid, ankid, ajaxCall){
    if(!ajaxCall)
        ajaxCall = "mapData";
    //map_data_holder postane holder id-jev potrebnih za prikaz markerjev kasneje
    map_data_holder = {spr_id: sprid, usr_id: usrid, loop_id: loopid, ank_id: ankid, ajaxCall: ajaxCall};
    spremenljivka_box = sprid;
}

/**
 * Passing raw json data for google maps (originaly used for mod IP location)
 * @param {type} rawData - json - [{"Modena":{"cnt":2,"lat":44.666698455810546875,"lng":10.9167003631591796875}}]
 * @returns {undefined}
 */
function passMapDataRaw(rawData){
    //map_data_holder postane holder id-jev potrebnih za prikaz markerjev kasneje
    map_data_holder = rawData;
    spremenljivka_box = 'IPloc';
}
 
 //inicializiraj mapo / nastavi mapo v colorboxu
function initializeMapGeneral() {    
    
    var mapOptions = {
        mapTypeId: google.maps.MapTypeId.ROADMAP
    };
    
    //pri vpogledu podatkov je viewMode vedno true
    viewMode = true;
    
    //infowindow iz API-ja, za prikaz markerja in informacije o markerju
    infowindow = new google.maps.InfoWindow();
    //deklaracija zemljevida
    var mapdiv = document.getElementById("map_canvas_all");
    var map = new google.maps.Map(mapdiv, mapOptions);
    //to se kasneje uporabi za pridobitev mape z id-em spremenljivke
    mapdiv.gMap = map;

    //deklaracija mej/okvira prikaza na zemljevidu
    bounds[spremenljivka_box] = new google.maps.LatLngBounds();
    //to store combined bounds of all polylines or polygons
    bounds['all'] = new google.maps.LatLngBounds();
    
    //get input type (marker, polyline, polygon)
    var input_type = map_data_holder.input_type;
    delete map_data_holder.input_type;
    
    //get subtype (mylocation-1, multilocation-2, chooselocation-3)
    var enota = map_data_holder.enota;
    delete map_data_holder.enota;
    
    //podvprasanja v infowindow
    podvprasanje[spremenljivka_box] = map_data_holder.podvprasanje;
    delete map_data_holder.podvprasanje;
    
    //naslov podvprasanja v infowindow
    podvprasanje_naslov[spremenljivka_box] = map_data_holder.podvprasanje_naslov;
    delete map_data_holder.podvprasanje_naslov;
    
    max_mark[spremenljivka_box] = map_data_holder.length;
    ml_sprem[spremenljivka_box] = false;
    st_markerjev[spremenljivka_box] = 0;
    allMarkers[spremenljivka_box] = [];

    if(enota == 3){
        map_data_fill(spremenljivka_box, map_data_holder.data, enota);
        map_data_fill_info_shapes(spremenljivka_box, map_data_holder.info_shapes);
    }
    else{
        if(input_type == 'marker'){
            //nafilaj mapo z markerji
            map_data_fill(spremenljivka_box, map_data_holder);

            //var soda = spremenljivka_box % 2 == 0;

            //if(soda){
            // Add a marker clusterer to manage the markers.
            var markerCluster = new MarkerClusterer(map, allMarkers[spremenljivka_box],
                {imagePath: srv_site_url + 'admin/survey/img_0/markerclusterer/m',
                maxZoom: 15});
            //}
        }
        else if(input_type == 'polyline')
            map_data_fill_shape(spremenljivka_box, map_data_holder.data, input_type);
        else if(input_type == 'polygon')
            map_data_fill_shape(spremenljivka_box, map_data_holder.data, input_type);
    }
    //hide loader
    $("#cboxLoadingOverlay").hide();
};

//inicializiraj mapo / nastavi mapo v colorboxu za mod IP location
function initializeMapGeneralForIPs() {    
    var mapOptions = {
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        streetViewControl: false
    };
    
    //infowindow iz API-ja, za prikaz markerja in informacije o markerju
    infowindow = new google.maps.InfoWindow();
    //deklaracija zemljevida
    
    var mapdiv = document.getElementById("map_canvas_all");
    if(!mapdiv)
        mapdiv = document.getElementById("map_ip");
    var map = new google.maps.Map(mapdiv, mapOptions);
    //to se kasneje uporabi za pridobitev mape z id-em spremenljivke
    mapdiv.gMap = map;

    //deklaracija mej/okvira prikaza na zemljevidu
    bounds[spremenljivka_box] = new google.maps.LatLngBounds();
    
    max_mark[spremenljivka_box] = map_data_holder.length;
    ml_sprem[spremenljivka_box] = false;
    st_markerjev[spremenljivka_box] = 0;
    allMarkers[spremenljivka_box] = [];
    
    //close infowindow on zoom because info can change due to clustering
    google.maps.event.addListener(map, 'zoom_changed', function() {
        infowindow.close();
    });
    
    Object.keys(map_data_holder).forEach(function(key) {
        //pomnozi markerje glede na frekvence
        for(var i=0; i<map_data_holder[key].cnt; i++)
            //nafilaj mapo z markerji
            if(key!=='')
                createMarkerForIps(key, map_data_holder[key].lat, map_data_holder[key].lng);
            //ce ni podatka o imenu mesta aka neznana lokacija
            else{
                
                delete map_data_holder['']['cnt'];
                Object.keys(map_data_holder['']).forEach(function(key) {
                    //pomnozi markerje glede na frekvence
                    for(var i=0; i<map_data_holder[''][key].cnt; i++)
                        //nafilaj mapo z markerji
                        createMarkerForIps('N/A', map_data_holder[''][key].lat, map_data_holder[''][key].lng);
                });
            }
    });
    
    // Add a marker clusterer to manage the markers.
    var markerCluster = new MarkerClusterer(map, allMarkers[spremenljivka_box],
        {imagePath: srv_site_url + 'admin/survey/img_0/markerclusterer/m',
        maxZoom: 30,
        zoomOnClick: false});
    
    google.maps.event.addListener(markerCluster, 'clusterclick', function(cluster) {
        var markers = cluster.getMarkers();

        var array = [];
        for (var i = 0; i < markers.length; i++) {
            if(array[markers[i].getTitle()])
                array[markers[i].getTitle()]++;
            else
                array[markers[i].getTitle()]=1;
        }
        //array["N/A"] = array[""];
        //delete array[""];

        var text = '';
        Object.keys(array).forEach(function(key) {
            text += '<b>'+array[key]+'</b> '+key+'<br>';
        });

        if (map.getZoom() <= markerCluster.getMaxZoom()) {
            infowindow.setContent(text);
            infowindow.setPosition(cluster.getCenter());
            infowindow.open(map);
        }
    });
    
    //hide loader
    $("#cboxLoadingOverlay").hide();
};

function createMarkerForIps(key, lat, lng) {
    //pridobi mapo spremenljivke
    var map  = document.getElementById("map_canvas_all");
    if(!map)
        map = document.getElementById("map_ip").gMap;
    else
        map  = document.getElementById("map_canvas_all").gMap;
    
    //var path_img_dir = srv_site_url + 'admin/survey/img_0/';
    var bigIcon = 1;
    /*if(map_data_holder[key].cnt > 9999)
        bigIcon = 5;
    else if(map_data_holder[key].cnt > 999)
        bigIcon = 4;
    else if(map_data_holder[key].cnt > 99)
        bigIcon = 3;
     else if(map_data_holder[key].cnt > 9)
        bigIcon = 2;*/

    var icon = {
        //fillColor: '#FF5555',
        //url: path_img_dir + 'marker_default.svg',
        url: srv_site_url + 'admin/survey/img_0/markerclusterer/m'+bigIcon+'.png',
        fillOpacity: 1,
        strokeWeight: 1
    };

    //nastavitve markerja
    var marker = new google.maps.Marker({
        position: new google.maps.LatLng(lat, lng),
        title: key,
        map: map,
        icon: icon,
        label: {
            text: /*map_data_holder[key].cnt+''*/'1'
            // Add in the custom label here
            //fontFamily: 'Roboto, Arial, sans-serif, custom-label-' + map_data_holder[key].cnt
        }
    });

    allMarkers[spremenljivka_box].push(marker);

    //Create a div element for container.
    var container = document.createElement("div");

    //Create a label element for address.
    var label = document.createElement("label");
    label.innerHTML = '<b>' + 1 + '</b> '+key;
    container.appendChild(label);

    //listener ob kliku na marker - focus input mora biti po nastavljanju bounds ali zoom
    google.maps.event.addListener(marker, 'click', function () {
        infowindow.setContent(container);
        infowindow.open(map, marker);
    });

    //nastavi label
    infowindow.setContent(container);
    //odpre marker - prikaze label (kot da bi ga kliknil)
    //infowin.open(map, marker);

    //v okvir se doda nov marker
    bounds[spremenljivka_box].extend(marker.position);

    //ce je samo eden marker, ga malo odzoomaj
    if (st_markerjev[spremenljivka_box] == 0) {
        map.setCenter(marker.position);
        map.setZoom(17);
    } else if (st_markerjev[spremenljivka_box] > 0) {
        //zemljevid se prilagodi okviru
        map.fitBounds(bounds[spremenljivka_box]);
        //ce je v viewMode - v podatkih ali analizah - se odzooma za 1 samo pri zadnjem markerju
        if (max_mark[spremenljivka_box] == st_markerjev[spremenljivka_box] + 1) {
            //zmanjsaj zoom za 1, ker google naredi prevec oddaljeno
            google.maps.event.addListenerOnce(map, "bounds_changed", function () {
                map.setZoom(map.getZoom() - 1);
            });
        }
    }

    //stevilo markerjev se poveca
    st_markerjev[spremenljivka_box]++;
}

//pridobi markerje iz baze
function getMapDataAjax() {
    //show loader
    $("#cboxLoadingOverlay").show();
    
    $.ajax({
            cache: false,
            crossDomain: true,
            type: 'post',
            dataType: "json",
            url: 'ajax.php?t='+map_data_holder.ajaxCall,
            data: { map_data: map_data_holder },
            error: function(response) {
                console.log("Error in Ajax connection! Please try later!");
            },
            success: function(response) {
                //holder markerjev
                map_data_holder = response;
                googleMapsAPIProcedura(initializeMapGeneral);
            }
    });
}

//for IP location navigation
function geoip_map_navigation_toggle(el, data){
    var elid = $(el).attr('id');
    if(!$(el).hasClass("active")){
        if(elid === 'geoip_countries')
            document.getElementById("geoip_cities").classList.remove("active");
        else
            document.getElementById("geoip_countries").classList.remove("active");

        $(el).addClass('active');
        
        passMapDataRaw(data);
        googleMapsAPIProcedura(initializeMapGeneralForIPs);
    }
}