var heatmap_num_clicksGlobal = [];	//belezi stevilo klikov na canvas
var mousePos =  [];	//belezi koordinate izbranih tock
var indeksMousePos = [];

//inicializacija canvas-a
function InitHeatMapCanvas(spremenljivka, quick_view){
	//console.log("InitHeatMapCanvas: "+spremenljivka);
	var imageJQ = $('#hotspot_'+spremenljivka+'_image');
	var visina = imageJQ.height();
	var sirina = imageJQ.width();	
	//var canvasJQ = $('#heatmapCanvas_'+spremenljivka);
	var canvas = document.getElementById('heatmapCanvas_'+spremenljivka);
	
	
	imageJQ.css( "display", "none" );	//skrij sliko
	
	image = new Image();
	image.src = $('#hotspot_'+spremenljivka+'_image').attr("src");
	image.height = visina;
	image.width = sirina;
	
	//canvasJQ.height(visina);
	//canvasJQ.width(sirina);
	canvas.setAttribute('height',visina);
	canvas.setAttribute('width',sirina);
    

	if (canvas.getContext){
	  var context = canvas.getContext('2d');
	  context.drawImage(image, 0, 0, sirina, visina);	//drawImage(image, dx, dy, dWidth, dHeight);	//narisi sliko ustrezne dimenzije (sirina, visina) na canvas
	} else {
	  console.log("Canvas not supported");
	}
	
	if(quick_view == true){
		//console.log("Disable buttons");
		$('#resetHeatMapCanvas_'+spremenljivka).prop('disabled', true);
		$('#resetHeatMapLastPoint_'+spremenljivka).prop('disabled', true);
	}else{
		$('#resetHeatMapCanvas_'+spremenljivka).prop('disabled', false);
		$('#resetHeatMapLastPoint_'+spremenljivka).prop('disabled', false);
	}
	
}


function HeatMapCanvasDelovanje(evt, spremenljivka, heatmap_show_clicks, heatmap_num_clicks, heatmap_click_color, heatmap_click_size, heatmap_click_shape, quick_view){
	if(!quick_view){
		var canvas = document.getElementById('heatmapCanvas_'+spremenljivka);
		//var canvas = document.getElementsByClassName('mapster_el_'+spremenljivka);
		var context = canvas.getContext('2d');
		
		//console.log("heatmap_num_clicksGlobal: "+heatmap_num_clicksGlobal[spremenljivka]);
		if(heatmap_num_clicksGlobal[spremenljivka] != 0){	//dokler je stevilo moznih klikov razlicno od nula zbiraj koordinate klikanih tock
			
			mousePos[spremenljivka][indeksMousePos[spremenljivka]] = getMousePos(canvas, evt);
			//console.log("x: "+mousePos[spremenljivka][indeksMousePos[spremenljivka]].x+" y: "+mousePos[spremenljivka][indeksMousePos[spremenljivka]].y+" za spremenljivko "+spremenljivka);
			
			
			//oznacevanje checkbox vezani na obmocja
			//preveri, ce je izbrana tocka znotraj obmocja
			checkIfPointInsidePoly(spremenljivka);
			//oznacevanje checkbox vezani na obmocja - konec
			
			
			heatmap_num_clicksGlobal[spremenljivka]--;		
			if(heatmap_show_clicks){	//ce je to potrebno, pokazi klike na canvas v obliki okvirja
				//drawRectangleOnCanvas(mousePos[spremenljivka][indeksMousePos[spremenljivka]], canvas, context);
				drawShapeOnCanvas(mousePos[spremenljivka][indeksMousePos[spremenljivka]], canvas, context, heatmap_click_color, heatmap_click_size, heatmap_click_shape);
			}
			
			HeatMapAddInput(spremenljivka, 0, 0);	//dodaj obstojece inpute za tocke iz baze ob morebitnem refreshu ali prehodu na prejsnjo stran

			indeksMousePos[spremenljivka] = indeksMousePos[spremenljivka] + 1;
			
			$('#heatmapClickNumber_'+spremenljivka).text(indeksMousePos[spremenljivka]);	//posodobi stevilo klikov na stevcu klikov
			
			if(heatmap_num_clicksGlobal[spremenljivka] == 0){	//ce ni vec moznih klikov
				$('#heatmapCanvas_'+spremenljivka).css( 'cursor', 'default' );	//spremeni misko v navadno puscico
			}

			
		}else{	//ce ni vec moznih klikov
	/* 		context.clearRect(0, 0, canvas.width, canvas.height);
			//context.clearRect(0, 0, context.canvas.width, context.canvas.height); //Izbriši vse na canvasu
			InitHeatMapCanvas(spremenljivka);
			heatmap_num_clicksGlobal[spremenljivka] = heatmap_num_clicks; */
			//pokazi zbrane tocke
	/* 		for(var i = 0; i<indeksMousePos;i++){
				console.log((i+1)+". točka ("+mousePos[i].x+", "+mousePos[i].y+")");
			} */
			$('#heatmapCanvas_'+spremenljivka).css( 'cursor', 'default' );	//spremeni misko v navadno puscico
		}
		//console.log("indeksMousePos["+spremenljivka+"]"+indeksMousePos[spremenljivka]);
	}
}

//funkcija, ki vrne x pa y koordinate klika oz. tocke na canvas
function getMousePos(canvas, evt) {
	var rect = canvas.getBoundingClientRect();
	return {
	  x: parseInt(evt.clientX - rect.left),
	  y: parseInt(evt.clientY - rect.top)
	};
}

//funkcija, ki doda html inpute za izbrane tocke na canvas
function HeatMapAddInput(spremenljivka, refresh, mousePosRefresh){
		var $variable_holder = $("#heatmapInputs_" + spremenljivka);
		var pointId = "pointId_"+spremenljivka+"_"+indeksMousePos[spremenljivka];
		//console.log(pointId);
		if (refresh){
			var $pointInput = $("<input>", {id: pointId, name: "vrednost_" + spremenljivka + "[]",
			value: pointId + "|" + mousePosRefresh.lat + "|" +	mousePosRefresh.lng});
		}else{
			var $pointInput = $("<input>", {id: pointId, name: "vrednost_" + spremenljivka + "[]",
				value: pointId + "|" + mousePos[spremenljivka][indeksMousePos[spremenljivka]].x + "|" +	mousePos[spremenljivka][indeksMousePos[spremenljivka]].y});
		}
		$variable_holder.append($pointInput);
}

//funkcija, ki narise izbrano obliko, kjer je izbrana tocka
function drawShapeOnCanvas(mousePos, canvas, context, heatmap_click_color, heatmap_click_size, heatmap_click_shape, refresh){
	//context.fillRect(mousePos.x, mousePos.y, 25, 25);
	stranicaRect = heatmap_click_size;
	if(heatmap_click_shape == 1)
	{
		if(refresh == 1){
			context.arc(mousePos.lat, mousePos.lng, stranicaRect, 0, 2 * Math.PI, false);
		}
		else{
			context.arc(mousePos.x, mousePos.y, stranicaRect, 0, 2 * Math.PI, false);
		}
		
	}else if(heatmap_click_shape == 2)
	{
		if(refresh == 1){
			context.rect(mousePos.lat-stranicaRect/2, mousePos.lng-stranicaRect/2, stranicaRect, stranicaRect);
		}else{
			context.rect(mousePos.x-stranicaRect/2, mousePos.y-stranicaRect/2, stranicaRect, stranicaRect);
		}		
	}
    	
	context.fillStyle = heatmap_click_color;
    context.fill();
	context.closePath();	//risanje je potrebno zakljuciti da, konkretno pri krogih, se ti ne povezujemo med sabo in naredijo vecji lik
}

//funkcija, ki resetira canvas in ostale spremenljivke na default
function resetHeatMapCanvas(spremenljivka, heatmap_num_clicks, quick_view){
	//pokazi zbrane tocke
/* 	for(var i = 0; i<indeksMousePos[spremenljivka];i++){
		console.log((i+1)+". točka ("+mousePos[spremenljivka][i].x+", "+mousePos[spremenljivka][i].y+") spremenljivka "+spremenljivka);
	} */
	
	var canvas = document.getElementById('heatmapCanvas_'+spremenljivka);
	//var canvas = document.getElementsByClassName('mapster_el_'+spremenljivka);
	var context = canvas.getContext('2d');
	context.clearRect(0, 0, canvas.width, canvas.height);	//zbrise celoten canvas
	//context.clearRect(0, 0, context.canvas.width, context.canvas.height); //Izbriši vse na canvasu
	InitHeatMapCanvas(spremenljivka);	//initializing canvas, da se ponovno pokaze slika
	heatmap_num_clicksGlobal[spremenljivka] = heatmap_num_clicks;	//stevilo klikov je zacetno
	indeksMousePos[spremenljivka] = 0;
	var $variable_holder = $("#heatmapInputs_" + spremenljivka);
	$variable_holder.children().remove();
	$('#heatmapCanvas_'+spremenljivka).css( 'cursor', 'pointer' );	//spremeni misko v rokico
	
	//resetiranje oznacenih checkbox-ov
	$("#heatmapCheckbox_"+spremenljivka).children().each(function (index, name) {		
		//console.log($(this).find('input').attr('value'));
		$(this).find('input').prop("checked", false);
	});
	
	$('#heatmapClickNumber_'+spremenljivka).text(0);	//stevec klikov resetiraj na 0
	
}

//funkcija, ki zbrise zadnje izbrano tocko iz canvasa in ustrezno uredi ostale zadeve
function resetHeatMapLastPoint(spremenljivka, heatmap_num_clicks, heatmap_show_clicks, heatmap_click_color, heatmap_click_size, heatmap_click_shape, heatmap_data, quick_view){
/* 	//pokazi zbrane tocke
  	for(var i = 0; i<indeksMousePos[spremenljivka];i++){
		console.log((i+1)+". točka ("+mousePos[spremenljivka][i].x+", "+mousePos[spremenljivka][i].y+") spremenljivka "+spremenljivka);
	} */
	
	var canvas = document.getElementById('heatmapCanvas_'+spremenljivka);
	//var canvas = document.getElementsByClassName('mapster_el_'+spremenljivka);
	var context = canvas.getContext('2d');
	context.clearRect(0, 0, canvas.width, canvas.height);	//zbrise celoten canvas
	//context.clearRect(0, 0, context.canvas.width, context.canvas.height); //Izbriši vse na canvasu

	InitHeatMapCanvas(spremenljivka);	//initializing canvas, da se ponovno pokaze slika
	var $variable_holder = $("#heatmapInputs_" + spremenljivka);
	$variable_holder.children().remove();
	//$('#heatmapCanvas_'+spremenljivka).css( 'cursor', 'pointer' );	//spremeni misko v rokico
	
	//resetiranje oznacenih checkbox-ov
	$("#heatmapCheckbox_"+spremenljivka).children().each(function (index, name) {		
		//console.log($(this).find('input').attr('value'));
		$(this).find('input').prop("checked", false);
	});
	
	if((heatmap_num_clicks - heatmap_num_clicksGlobal[spremenljivka]) > 0)
	{
		indeksMousePos[spremenljivka] = indeksMousePos[spremenljivka] - 1;
		heatmap_num_clicksGlobal[spremenljivka] = heatmap_num_clicksGlobal[spremenljivka] + 1;	//stevilo moznih klikov se poveca za ena
		$('#heatmapClickNumber_'+spremenljivka).text(heatmap_num_clicks - heatmap_num_clicksGlobal[spremenljivka]);	//stevec klikov
	}	
	
	//dodaj ostale nezbrisane tocke	******************
	//ce je bil refresh ali se je uporabnik vrnil na prejsnjo stran
	if(refreshed[spremenljivka] == 1){
		for (var row in heatmap_data) {
			var row_object = heatmap_data[row];
			mousePos[spremenljivka][row] = {x: row_object.lat, y: row_object.lng};
			//console.log("mousePos["+spremenljivka+"]["+row+"].x:"+mousePos[spremenljivka][row].x);
		}
		refreshed[spremenljivka] = 0;
	}
	for(var i = 0; i<indeksMousePos[spremenljivka];i++){
		//console.log((i+1)+". točka ("+mousePos[spremenljivka][i].x+", "+mousePos[spremenljivka][i].y+") spremenljivka "+spremenljivka);
		//console.log("Dodaj neizbrisano točko "+i);
		var pointId = "pointId_"+spremenljivka+"_"+i;
		var $pointInput = $("<input>", {id: pointId, name: "vrednost_" + spremenljivka + "[]",
					value: pointId + "|" + mousePos[spremenljivka][i].x + "|" +	mousePos[spremenljivka][i].y});		
		$variable_holder.append($pointInput);
		if(heatmap_show_clicks){	//ce je to potrebno, pokazi klike na canvas v obliki okvirja
			drawShapeOnCanvas(mousePos[spremenljivka][i], canvas, context, heatmap_click_color, heatmap_click_size, heatmap_click_shape);
		}
	}

	//dodaj ostale nezbrisane tocke - konec ******************
}

//funkcija, ki se sprozi ob vrnitvi na prejsnjo stran
function heatmap_data_add(spremenljivka, heatmap_data, heatmap_click_color, heatmap_click_size, heatmap_click_shape, heatmap_show_clicks, heatmap_num_clicks) {
	heatmap_num_clicksGlobal[spremenljivka] = heatmap_num_clicks;
	var i = 0;
	for (var row in heatmap_data) {
        var row_object = heatmap_data[row];
        var mousePos = {lat: row_object.lat, lng: row_object.lng};
		var canvas = document.getElementById('heatmapCanvas_'+spremenljivka);
		var context = canvas.getContext('2d');		
		
		if(heatmap_show_clicks){
			drawShapeOnCanvas(mousePos, canvas, context, heatmap_click_color, heatmap_click_size, heatmap_click_shape, 1);
		}		
		indeksMousePos[spremenljivka]++;		
		i++
		HeatMapAddInput(spremenljivka, 1, mousePos);
    }
	//uredi limit klikov glede na ze prisotne tocke na canvas
	heatmap_num_clicksGlobal[spremenljivka] = heatmap_num_clicksGlobal[spremenljivka] - i;
	$('#heatmapClickNumber_'+spremenljivka).text(i);	//prikazi pravilno stevilo klikov na stevcu klikov
}

//funkcija, ki skrbi za pretvorbo koordinat v pravilno obliko za nadaljnje delovanje
function convertPolyString(polypoints, vre_id){
	var poly = [];
	var polyObjectArray = [];
	var tmpX;
	var tmpY;
	var j = 0;
	poly = polypoints.split(",");
	
	for(var i = 0; i<poly.length; i++){		
  		if(i == 0 || i%2 == 0){
			tmpX = parseInt(poly[i]);
		}else{
			tmpY = parseInt(poly[i]);
			polyObjectArray.push({x: tmpX, y: tmpY});
		}
	}	
	//console.log("dolzina polja polyObjectArray je: "+polyObjectArray.length);
	return polyObjectArray;
}

//funkcija, ki ureja preverjanje prisotnosti trenutne tocke znotraj predefiniranega obmocja
function checkIfPointInsidePoly(spremenljivka){
	$("#hotspot_"+spremenljivka+"_map").children().each(function (index, name) {		
		//console.log('coords: ' + $(this).attr('coords') + ' vre_id: ' + $(this).attr('name'));		
		var vre_id = $(this).attr('name');	
		var polypoints = $(this).attr('coords');		
		
		poly = convertPolyString(polypoints, vre_id);	//pretvori polje s tockami obmocja v ustrezno obliko
		
		//preveri, ce je izbrana tocka znotraj obmocja
		var inside = insidePoly(poly, mousePos[spremenljivka][indeksMousePos[spremenljivka]].x, mousePos[spremenljivka][indeksMousePos[spremenljivka]].y, vre_id);
		//preveri, ce je izbrana tocka znotraj obmocja - konec
		
		if (inside == true){
			//console.log("Točka je znotraj območja "+vre_id);
			$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).prop("checked", true);	//oznacitev ustreznega radio button-a / checkbox v skritem menuju
		}		
	});	
}

function insidePoly(poly, pointx, pointy, vre_id) {
 	//console.log("Za poly: "+vre_id+" je x: "+pointx+" y: "+pointy);
	
    var i, j;
    var inside = false;
    for (i = 0, j = poly.length - 1; i < poly.length; j = i++) {
		//console.log(poly[i].x+" "+poly[i].y);
        if(((poly[i].y > pointy) != (poly[j].y > pointy)) && (pointx < (poly[j].x-poly[i].x) * (pointy-poly[i].y) / (poly[j].y-poly[i].y) + poly[i].x) ) inside = !inside;		
    }
	//console.log("inside je: "+inside);
    return inside;
}