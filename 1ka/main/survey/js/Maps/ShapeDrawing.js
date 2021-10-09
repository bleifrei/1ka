// Author: Uroš Podkrižnik (24.1.2017)
// Tip vprasanja = 26

//DRAWING POLYLINE

/**
 * Function to set parameters for drawing a shape on map
 * 
 * @param {type} spremenljivka - id spremenljivke
 * @param {google's shape} shape - polyline or polygon if already exists
 * @param shape_type - string - type of shape 'polyline' or 'polygon'
 * @returns {undefined}
 */
function drawShape(spremenljivka, shape, shape_type) {
    var map = document.getElementById("map_"+spremenljivka).gMap;
    
    var drawingMode = (shape_type === 'polyline') ? 
        google.maps.drawing.OverlayType.POLYLINE : google.maps.drawing.OverlayType.POLYGON;
    var startDrawingMode = shape!==null ? null : drawingMode;
    var shapeOptions = (shape_type === 'polyline') ? polylineOptions() : polygonOptions();

    var drawingManager = new google.maps.drawing.DrawingManager({
        drawingMode: startDrawingMode,
        drawingControl: shape===null,
        drawingControlOptions: {
            position: google.maps.ControlPosition.TOP_RIGHT,
            drawingModes: [shape_type]
        },
        polylineOptions: shapeOptions,
        polygonOptions: shapeOptions
    });
  
    //settings for custom drawing controls
    var drawingControlDiv = document.createElement('div');
    var DrawingControl = new drawingControl(drawingControlDiv, map, spremenljivka);
    drawingControlDiv.index = 1;
    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(drawingControlDiv);
    //at start, there is no shape, so no need to show delete button
    DrawingControl.style.display = (shape!==null && !viewMode) ? 'inline-block' : 'none';
      
    if(shape!==null){
        shape.type = shape_type;
        createBoundsFromPath( shape.getPath() , spremenljivka );
        map.fitBounds(bounds[spremenljivka]);
        afterShapeCompleteSettings(drawingManager, DrawingControl, shape, spremenljivka, map);
    }
    
    //listener when user colpetes shape
    google.maps.event.addListener(drawingManager, shape_type+'complete', function(shape) {
        shape.type = shape_type;
        afterShapeCompleteSettings(drawingManager, DrawingControl, shape, spremenljivka, map);
    });

    drawingManager.setMap(map);
}

/**
 * Sets all kind of settings after shape is complete
 * 
 * @param {type} drawingManager - google's drawingManager
 * @param {type} DrawingControl - button for removing a shape
 * @param {google's shape} shape - polyline or polygon if already exists
 * @param {type} spremenljivka - id spremenljivke
 * @param {type} map - google map on which we are drawing shape
 * @returns {undefined}
 */
function afterShapeCompleteSettings(drawingManager, DrawingControl, shape, spremenljivka, map){
    //hide controls
        drawingManager.setOptions({
            drawingControl: false
        });
        //stop drawing
        drawingManager.setDrawingMode(null);
        //show delete button
        DrawingControl.style.display = 'inline-block';

        // Setup the click event listener for delete button
        DrawingControl.addEventListener('click', function() {
            clearAndMakeDrawableAgain(spremenljivka, shape, DrawingControl, drawingManager);
        });
        
        shapeInputHandler(shape, spremenljivka);
        
        //hide warning info for ending a shape
        var end_info_warning = document.getElementById("end_shape_info_"+spremenljivka);
        end_info_warning.style.display  = "none";

        /**
         * Liteners when editing a shape
         */
        //when editing end or start point
        google.maps.event.addListener(shape.getPath(), 'set_at', function(index, obj) {
            shapeInputHandler(shape, spremenljivka);
        });
        //when inserting new point (break old one)
        google.maps.event.addListener(shape.getPath(), 'insert_at', function(index, obj) {
            shapeInputHandler(shape, spremenljivka);
        });
        //when deleting a point -> undo 
        google.maps.event.addListener(shape.getPath(), 'remove_at', function(index, obj) {
            shapeInputHandler(shape, spremenljivka);
        });
        
        setDeleteMenu(shape, map, spremenljivka, DrawingControl, drawingManager)
}

/**
 * Sets rightclick and dblclick listeners to open delete menu
 * @param {type} shape - shape to set listeners on
 * @param {type} map  - map on which to hover menu (shape's map)
 * @param {type} spremenljivka - id of variable
 * @param {type} drawingManager - google's drawingManager
 * @param {type} DrawingControl - button for removing a shape
 * @returns {undefined}
 */
function setDeleteMenu(shape, map, spremenljivka, DrawingControl, drawingManager) {
    google.maps.event.addListener(shape, 'rightclick', function (e) {
        // Check if click was on a vertex control point
        if (e.vertex == undefined) {
            return;
        }
        //removeVertexVM(spremenljivka, shape, e.vertex);
        deleteMenu.open(map, shape, e.vertex, spremenljivka, DrawingControl, drawingManager);
    });
    google.maps.event.addListener(shape, 'dblclick', function (e) {
        // Check if click was on a vertex control point
        if (e.vertex == undefined) {
            return;
        }
        //removeVertexVM(spremenljivka, shape, e.vertex);
        deleteMenu.open(map, shape, e.vertex, spremenljivka, DrawingControl, drawingManager);
    });
}

/**
 * Ce obstajajo podatki v bazi izrisi info shapes
 * Kreirajo se markerji, shranjeni v bazi.
 * @param spremenljivka int id spremenljivke
 * @param map_data JSON Object
 */
function map_data_fill_info_shapes(spremenljivka, map_data) {
    //because of viewing a filled survey at data section
    var fillMode = (document.getElementById("map_"+spremenljivka) !== null);

    //pridobi mapo spremenljivke
    var map;
    if(fillMode)
        map = document.getElementById("map_"+spremenljivka).gMap;
    else
        map = document.getElementById("map_canvas_all").gMap;
    
    
    var shapeOption = polylineOptions(map);
    
    for (var i=0; i<map_data.length; i++)
    {             
        var color = mapShapeColors[i % mapShapeColors.length];

        //set and show the shape
        shapeOption.path = map_data[i].path;
        shapeOption.strokeColor = '#'+color;
        shapeOption.strokeColorOrg = '#'+color;
        shapeOption.editable = false;
        var shape = new google.maps.Polyline(shapeOption);
      
        //Create a div element for container.
        var container = document.createElement("div");
        //margin, da se pri italic ne odreze zadja crka
        container.style.cssText = 'margin-right:1px';

        //Create a label for title
        var Title = document.createElement("label");
        Title.style.cssText = "font-size:1em;";
        Title.innerHTML = '<b>' + map_data[i].address + '</b>';
        container.appendChild(Title);
        
        shape.ifnowin = container;
        shape.address = map_data[i].address;
        var infow = new google.maps.InfoWindow();

        google.maps.event.addListener(shape, 'mouseover', function(ev) { 
            this.setOptions({strokeWeight: 6, zIndex: 2}); 
            //open infowindow if title exists
            if(this.address){
                infow.setContent(this.ifnowin);
                infow.setPosition(ev.latLng);
                infow.open(map);
            }
        });
        google.maps.event.addListener(shape, 'mouseout', function() { 
            this.setOptions({strokeWeight: 4, zIndex: 1}); 
            infow.close();
        });
    }
}

/**
 * Clears the map, deletes all inputs, hides "Clear" button and sets drawing mode on
 * @param {type} spremenljivka - id spremenljivke
 * @param {google's shape} shape - polyline or polygon
 * @param {type} drawingManager - google's drawingManager
 * @param {type} DrawingControl - button for removing a shape
 * @returns {undefined}
 */
function clearAndMakeDrawableAgain(spremenljivka, shape, DrawingControl, drawingManager){
    //remove shape
    shape.setMap(null);  
    //remove all hidden inputs of a shape
    deleteAllShapeInputs(spremenljivka);
    //hide delete button
    DrawingControl.style.display = 'none';
    //show drawing controls
    drawingManager.setOptions({
        drawingControl: true
    });

    //start drawing
    drawingManager.setDrawingMode(shape.type);
    
    //show warning info for ending a shape
    var end_info_warning = document.getElementById("end_shape_info_"+spremenljivka);
    end_info_warning.style.display  = "inline-block";
}

/**
 * Removes a vertex/point of shape and if it after last it clears the map
 * and sets drawing mode on again
 * @param {type} spremenljivka - id spremenljivke
 * @param {google's shape} shape - polyline or polygon if already exists
 * @param {type} vertex - vertex or point which was selected
 * @param {type} drawingManager - google's drawingManager
 * @param {type} DrawingControl - button for removing a shape
 * @returns {undefined}
 */
/*function removeVertex(spremenljivka, shape, vertex, DrawingControl, drawingManager) {
    if (!shape || vertex == undefined)
        return;

    shape.getPath().removeAt(vertex);

    if(shape.getPath().length < 2){
        clearAndMakeDrawableAgain(spremenljivka, shape, DrawingControl, drawingManager);
    }
}*/

/**
 * Handle hidden input - remove old, create new
 * @param {google's shape} shape - polyline or polygon
 * @param {int} spremenljivka - id spremenljivke
 * @returns {undefined}
 */
function shapeInputHandler(shape, spremenljivka){
    deleteAllShapeInputs(spremenljivka);
    createAllShapeInputs( shape.getPath(), spremenljivka );
}

/**
 * create hidden inputs of all points of shape
 * @param {type} path shape.getPath()
 * @param {int} spremenljivka - id spremenljivke
 */
function createAllShapeInputs( path , spremenljivka ) {
    var pathArray = path.getArray();
    for( var i = 0, n = pathArray.length;  i < n;  i++ ) {
        var latLng = pathArray[i];
        createShapeInput(spremenljivka, { lat: latLng.lat(), lng: latLng.lng() }, i+1);
    }
}

/**
 * create hidden input of shape data
 * @param {type} spremenljivka - int - id spremenljivke
 * @param {type} vertices - array of lat and lng
 * @param {type} index - order of point
 * @returns {undefined}
 */
function createShapeInput(spremenljivka, vertices, index) {
    //najdi element za drzanje variabel
    var $variable_holder = $("#spremenljivka_" + spremenljivka + "_variabla");
    
    //kreiraj input s statičnimi podatki markerja
    var $shapeInput = $("<input>", {name: "vrednost_" + spremenljivka + "[]",
        type: "hidden", class: "shape_input",
        value: index + "|" + vertices.lat + "|" + vertices.lng});
    $variable_holder.append($shapeInput);
}

/**
 * delete all hidden inputs of shape
 * @param {type} spremenljivka - id spremenljivke
 */
function deleteAllShapeInputs(spremenljivka) {
    //brisi vse inpute s staticnimi podatki shape
    $('#spremenljivka_' + spremenljivka + '_variabla').find('input.shape_input').remove();
}

/**
 * Ce obstajajo podatki v bazi (rec. uporabnik klikne 'Prejsnja stran'). 
 * Kreira se shape, shranjena v bazi.
 * @param spremenljivka int id spremenljivke
 * @param map_data JSON Object
 * @param shape_type - string - type of shape 'polyline' or 'polygon'
 */
function map_data_fill_shape(spremenljivka, map_data, shape_type){
    //because of viewing a filled survey at data section
    var fillMode = (document.getElementById("map_"+spremenljivka) !== null);

    //pridobi mapo spremenljivke
    var map;
    if(fillMode)
        map = document.getElementById("map_"+spremenljivka).gMap;
    else
        map = document.getElementById("map_canvas_all").gMap;
    
    var shapeOption = shape_type === 'polyline' ? polylineOptions(map) : polygonOptions(map);
    
    //for respondents, fill a map
    if(fillMode){
        //set and show the shape
        shapeOption.path = map_data;
        var shape = shape_type === 'polyline' ? 
            new google.maps.Polyline(shapeOption) : new google.maps.Polygon(shapeOption);
    
        //alsways viewMode = false, except at insight into survey at data
        //if not viewMode, create shape inputs and start drawing
        if(!viewMode){
            //create hidden inputs of shape
            createAllShapeInputs( shape.getPath() , spremenljivka );

            //set all data for shape drawing
            drawShape(spremenljivka, shape, shape_type);
        }
        //if viewMode, just create and fit bounds
        else{
            //set bounds
            createBoundsFromPath( shape.getPath() , spremenljivka );
            map.fitBounds(bounds[spremenljivka]);
        }
    }
    //for admins, show shapes
    else{
        var keys = Object.keys(map_data);  

        for (var i=0; i<keys.length; i++)
        {
            var color = mapShapeColors[i % mapShapeColors.length];

            //set and show the shape
            shapeOption.path = map_data[keys[i]];
            shapeOption.strokeColor = '#'+color;
            shapeOption.strokeColorOrg = '#'+color;
            var shape = shape_type === 'polyline' ? 
                new google.maps.Polyline(shapeOption) : new google.maps.Polygon(shapeOption);

            google.maps.event.addListener(shape, 'mouseover', function() { this.setOptions({strokeWeight: 6, strokeColor: 'black', zIndex: 2}); });
            google.maps.event.addListener(shape, 'mouseout', function() { this.setOptions({strokeWeight: 4, strokeColor: this.strokeColorOrg , zIndex: 1}); });
    
            //set bounds
            createBoundsFromPath( shape.getPath() , 'all' );
            map.fitBounds(bounds['all']);
        }
    }
}

/**
 * Polyline options holder
 * @param map google map, to put polyline in it
 * @returns {polylineOptions.PolylineAnonym$6}
 */
function polylineOptions(map){
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
 * Polygon options holder
 * @param map google map, to put polygon in it
 * @returns {polygonOptions.PolygonAnonym$6}
 */
function polygonOptions(map){
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