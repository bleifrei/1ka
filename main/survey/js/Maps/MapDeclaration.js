// Author: Uroš Podkrižnik (1.7.2016)
// Tip vprasanja = 26

//DECLARATION

//GLOBALNE SPREMENLJIVKE ZA VSE SPREMENLJIVKE TIP 26 NA ENI STRANI
//mej/okvira prikaza na zemljevidu objekt za vsako spremenljivko
var bounds = {},
        //objekt stevila vseh markerjev za vsako spremenljivk posebej
        st_markerjev = {},
        //object of number of shapes and ID of last shape for every variable ({spr_id: {'count': 5, 'last_id': 9}, ...})
        //'last_id' is not fully reliable as it is used for shapes colours 
        //      -> to make it reliable, method must be creaed to store all id's and removing last id at shame removal
        st_shapes = {},
        //infowindow iz API-ja, za prikaz markerja in informacije o markerju
        infowindow,
        //array id-jev spremenljivk, ki so tipa moja lokacija (max 1 marker)
        ml_sprem = [],
        //array id-jev spremenljivk, ki imajo F user location
        usrLoc_sprem = [],
        geocoder,
        //ce je true, se do skript dostopa iz podatkov, analiz
        //v primeru, ko se gre za resevanje mora vedno biti false
        viewMode = false,
        //objekt id spremenljivka : boolean, kjer je true, ce spremenljivka
        //uporablja F z podvprasanjem v infowindow markerja
        podvprasanje = {},
        //objekt id spremenljivka : string, kjer je naslov podvprasanja, ce
        //uporablja F z podvprasanjem v infowindow markerja
        podvprasanje_naslov = {},
        //objekt id spremenljivka : int, kjer je int stevilo max 
        //dovoljenih markerjev na mapi
        max_mark = {},
        //objekt vseh markerjev na strani (respondetn ali branching) {spr_id:[markers array]}
        allMarkers = {},
        //objekt id spremenljivka : marker
        //potrebujejo ga samo spremenljivke tipa moja lokacija (max 1 marker)
        //uporablja se za brisanje prejsnjega markerja na mapi
        mlmarker = {},
        //colors for shapes - polylines and polgons
        mapShapeColors = ['c0504d', '4f81bd', '9bbb59', '8064a2', '4bacc6', 'f79646', '92a9cf', '8c0000', 'f00800', 'ff8a82', 'f2c4c8', '0b0387', '0400fc', '9794f2'],
        deleteMenu;

function mapsAPIseNi(MapDeclaration) {
    //prveri, ce je element skripte APIja ze kreiran
    var google_api = document.getElementById("google_api");
    //ce je ze kreiran, dodaj funkcijo v onload (da se mapa kreira, ko se api dokoncno naloada)
    if (google_api) {
        //element z apijem ze obstaja, se pravi da se se loada
        //deklariraj funkcijo za onload
        var addFunctionOnLoad = function (callback) {
            if (google_api.addEventListener) {
                google_api.addEventListener('load', callback, false);
            } else {
                google_api.attachEvent('onload', callback);
            }
        };
        //dodaj funkcijo v onload
        addFunctionOnLoad(MapDeclaration);
    }
    //ce element skripte za API se ne obstaja, jo nalozi
    else
        loadGoogleMapsScript(function () {
            MapDeclaration();
            initializeDeleteMenu();
        });
}

//includaj oz. nalozi skripno google APIja ter nastavi mapo
function loadGoogleMapsScript(callback)
{
    // Adding the script tag to the head as suggested before
    var head = document.getElementsByTagName('head')[0];
    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.id = 'google_api';
    script.src = "https://maps.googleapis.com/maps/api/js?key="+google_maps_API_key+"&libraries=places,drawing";

    // Then bind the event to the callback function.
    // There are several events for cross browser compatibility.
    script.onreadystatechange = callback;
    script.onload = callback;

    // Fire the loading
    head.appendChild(script);
}

/**
 * Used for clearing a map of polyline or polygon
 * @param {type} controlDiv - div to put in custom settings - buttons
 * @returns {div element} element of a control
 */
function drawingControl(controlDiv) {

    controlDiv.style.padding = '10px';

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
    controlUI.title = lang['srv_vprasanje_button_map_clear'];
    controlDiv.appendChild(controlUI);

    // Set CSS for the control interior.
    var controlText = document.createElement('div');
    controlText.style.color = 'rgb(25,25,25)';
    controlText.style.fontFamily = 'Roboto,Arial,sans-serif';
    controlText.style.fontSize = '16px';
    controlText.style.lineHeight = '28px';
    controlText.style.paddingLeft = '5px';
    controlText.style.paddingRight = '5px';
    controlText.innerHTML = lang['srv_vprasanje_button_map_clear'];
    controlUI.appendChild(controlText);

    return controlUI;
}

/**
 * create bounds of all points of polygon/polyline
 * @param {type} path gon.getPath()
 * @param {int} spremenljivka - id spremenljivke
 */
function createBoundsFromPath(path, spremenljivka) {
    var pathArray = path.getArray();
    for (var i = 0, n = pathArray.length; i < n; i++) {
        var latLng = pathArray[i];
        bounds[spremenljivka].extend(latLng);
    }
}

//pretvori lat in lng v en string
//uporabi se kot kljuc markerja za ID iz objekta markers
function getMarkerUniqueId(lat, lng, spr) {
    var zdot = spr + '_' + lat + '_' + lng;
    return zdot.split('.').join("-");
}

/**
 * Find out if marker already exist on this variable whit this address
 * @param {type} add - address to find if exist at some marker
 * @param {type} spr - variable id
 * @returns {type} - marker if exist, null otherwise
 */
function findMarkerFromAddress(add, spr) {
    for (var i = 0; i < allMarkers[spr].length; i++) {
        if (allMarkers[spr][i].address === add)
            return allMarkers[spr][i];
    }
    return null;
}

////////////BRISANJE TOCK
function initializeDeleteMenu() {
    /**
     * A menu that lets a user delete a selected vertex of a path.
     * @constructor
     */
    function DeleteMenu() {
        this.div_ = document.createElement('div');
        this.div_.className = 'maps-delete-menu';
        this.div_.innerHTML = lang['srv_vprasanje_delete_point_map'];

        var menu = this;
        google.maps.event.addDomListener(this.div_, 'click', function (e) {
            //this prevents to trigger click event on map (klikNaMapo.js)
            e.cancelBubble = true;
            if (e.stopPropagation) e.stopPropagation(); 

            //remove whole shape
            if(menu.get('shape') && menu.get('vertex') == undefined && menu.get('marker') == undefined)
                menu.removeShape();
            //remove vertex in shape or marker
            else
                menu.removeVertex();
        });
    }
    DeleteMenu.prototype = new google.maps.OverlayView();

    DeleteMenu.prototype.onAdd = function () {
        var deleteMenu = this;
        var map = this.getMap();
        this.getPanes().floatPane.appendChild(this.div_);

        // mousedown anywhere on the map except on the menu div will close the
        // menu.
        this.divListener_ = google.maps.event.addDomListener(map.getDiv(), 'mousedown', function (e) {
            if (e.target != deleteMenu.div_) {
                deleteMenu.close();
            }
        }, true);
    };

    DeleteMenu.prototype.onRemove = function () {
        google.maps.event.removeListener(this.divListener_);
        this.div_.parentNode.removeChild(this.div_);

        // clean up
        this.set('position');
        this.set('shape');
        this.set('vertex');
        this.set('spremenljivka');
        this.set('DrawingControl');
        this.set('drawingManager');
        this.set('marker');
        this.set('markerId');
    };

    DeleteMenu.prototype.close = function () {
        this.setMap(null);
    };

    DeleteMenu.prototype.draw = function () {
        var position = this.get('position');
        var projection = this.getProjection();

        if (!position || !projection) {
            return;
        }

        var point = projection.fromLatLngToDivPixel(position);
        this.div_.style.top = point.y + 'px';
        this.div_.style.left = point.x + 'px';
    };

    /**
     * Opens the menu at a vertex of a given path.
     */
    DeleteMenu.prototype.open = function (map, shape, vertex, spremenljivka, DrawingControl, drawingManager, marker, markerId) {
        if(shape)
            this.set('position', shape.getPath().getAt(vertex));
        else if(marker)
            this.set('position', marker.getPosition());

        this.set('shape', shape);
        this.set('vertex', vertex);
        this.set('spremenljivka', spremenljivka);
        this.set('DrawingControl', DrawingControl);
        this.set('drawingManager', drawingManager);
        this.set('marker', marker);
        this.set('markerId', markerId);
        this.setMap(map);
        this.draw();
    };
    
    /**
     * Opens the menu at a geofence.
     */
    DeleteMenu.prototype.open_shape = function (map, shape, position, spremenljivka) {
        this.set('position', position);
        this.set('shape', shape);
        this.set('spremenljivka', spremenljivka);
        this.setMap(map);
        this.draw();
    };

    /**
     * Deletes the vertex from the path.
     */
    DeleteMenu.prototype.removeVertex = function () {
        var spremenljivka = this.get('spremenljivka');
        //shape
        var shape = this.get('shape');
        var vertex = this.get('vertex');
        var DrawingControl = this.get('DrawingControl');
        var drawingManager = this.get('drawingManager');
        //marker
        var marker = this.get('marker');
        var markerId = this.get('markerId');

        if ((!shape || vertex == undefined) && !marker) {
            this.close();
            return;
        }

        //removing marker
        if(marker){
            //accessed from respondent
            if(markerId)
                removeMarker(spremenljivka, marker, markerId);
            //accessed from admin
            else
                removeMarkerVM(spremenljivka, marker);
        }
        //removing shape
        else if(shape){
            shape.getPath().removeAt(vertex);

            if (shape.getPath().length < 2) {
                //accessed from respondent
                if(DrawingControl != null && drawingManager != null)
                    //handle visibility of tools
                    clearAndMakeDrawableAgain(spremenljivka, shape, DrawingControl, drawingManager);
                //accessed from admin
                else
                    //remove shape from DB
                    deleteLineInDB(spremenljivka, shape);
            }
        }
        this.close();
    };
    
     /**
     * Deletes the whole shape.
     */
    DeleteMenu.prototype.removeShape = function () {
        //shape
        var shape = this.get('shape');

        //removing shape from DB and map
        if(shape){
            maza_delete_geofence(shape);
        }
        this.close();
    };

    deleteMenu = new DeleteMenu();
}