//heatmap_data_holder postane holder id-jev potrebnih za prikaz markerjev kasneje
//kasneje se v ta objekt nafilajo podatki za prikaz vsakega markerja
var heatmap_data_holder = {};
var heatmap;
var heatmapData = [];
var heatmapBackground_data_holder;
var max = 0; //stevilo isto klikanih tock
//var r = 50;
var r = 1;
var spr_id = 0;
var anketa_id;
var slikaNalozena = 0;

function heatmapInit(spremenljivka){	
	//ureditev vrednosti sliderja za radij in njegove output labele
 	$('#heatmapRadij').val(r);
	$('#heatmapRadijValue').text(r);	
}

function drawHeatmap(){
	//narisi heatmap
	var min = 0;
	for (var row in heatmap_data_holder) {
		var row_object = heatmap_data_holder[row];
		//x in y morajo biti int, ker drugace ne dela, zato parseInt()
		heatmapData.push({x: parseInt(row_object.lat), y: parseInt(row_object.lng), value: parseInt(row_object.text), radius: r});
		//za pridobitev najvecjega stevila isto klikanih tock
		if(max < parseInt(row_object.text)){
			max = parseInt(row_object.text);
		}
	}
	//console.log("Final max: "+max);
	// set the generated dataset
	heatmap.setData({
		min: min,
		max: max,
		data: heatmapData
	});
	//console.log(heatmapData);
	exportHeatmapAsImage(spr_id);	
}
    
//kreiraj colorbox (popup, kjer bo heatmap poročilo) in sliderja za radij
$(document).ready(function () {	
    $(".fHeatMap").colorbox({
        scrolling:false,
        width:"80%",
        height:"80%",
        title: "",
        html:'<div id="heatmap_canvas_all" style="width:100%;height:95%"></div><div style="display:inline-block">'+lang["srv_heatmap_radius"]+': <input id="heatmapRadij" type="range" min="1" max="500" step="1" value="'+r+'" oninput="outputUpdate(value)"/><output for="heatmapRadij" id="heatmapRadijValue">'+r+'</output></div><div style="display:inline-block">&emsp;</div><div id="izvozHeatmap" style="display:inline-block"></div>',
        onComplete:function(){getHeatMapDataAjax(); }
    });
});



function outputUpdate(radij) {
	document.querySelector('#heatmapRadijValue').value = radij;
	//console.log(radij);
	r = radij;
	heatmapData = [];	//sprazni trenutni data set za risanje heatmap
	drawHeatmap();	//ponovno narisi heatmap z novim radijem
	max = 0;
	//shrani vrednost radija
	$.ajax({
            cache: false,
            crossDomain: true,
            type: 'post',
            dataType: "text",
            url: 'ajax.php?t=heatmapRadij',
            data: { heatmapRadij: radij, anketa:  anketa_id, sprid: spr_id},
            error: function(response) {
                console.log("Error in Ajax4HeatMap connection! Please try later!");
            }
    });
	
	//odstrani sporocilo o moznosti shranjevanja slike, ce je ta generirana
	if($('#heatMapSaveAsMsg_'+spr_id).text() != '')
	{
		//console.log($('#heatMapSaveAsMsg_'+spr_id).text());
		$('#heatMapSaveAsMsg_'+spr_id).remove();
	}
}

/**
 * ob kliku na link prenesi podatke o zeljeni spremenljivki
 * @param {type} sprid - int - id spremenljivke
 * @param {type} usrid - int - id userja
 * @param {type} loopid - int - id loopa
 * @returns {undefined}
 */
function passHeatMapData(sprid, usrid, loopid, anketa){
    //map_data_holder postane holder id-jev potrebnih za prikaz markerjev kasneje
    heatmap_data_holder = {spr_id: sprid, usr_id: usrid, loop_id: loopid};
	//console.log("Radij: "+radij);
	if (spr_id != sprid){
		//console.log("Rabimo nove podatke za heatmap");
		heatmapData = [];	//sprazni trenutni data set za risanje heatmap
		max = 0;
	}
	//console.log("Anketa v pass: "+anketa);
	anketa_id = anketa;
    spremenljivka = spr_id = sprid;
	
	getHeatMapRadij(sprid, anketa);
	getHeatMapExportIcons(sprid, anketa);
}

function getHeatMapRadij(sprid, anketa){	
	$.ajax({
		cache: false,
		crossDomain: true,
		type: 'post',
		dataType: "text",
		url: 'ajax.php?t=getheatmapradij',
		data: { sprid: sprid, anketa: anketa},
		error: function(response) {
			console.log("Error in AjaxHeatMapBackGround connection! Please try later!");
		},
		success: function(response) {
			r = response;
			if(r){
				//console.log("Radij iz baze je: "+r);
			}else{
				//console.log("Default radij");
				//r = 50;
				r = 1;
				//console.log("Radij je po novem: "+r);
			}			
 			//ureditev vrednosti sliderja za radij in njegove output labele
			$('#heatmapRadij').val(r);
			$('#heatmapRadijValue').text(r);
		}
    });
}

function getHeatMapExportIcons(sprid, anketa){	
	$.ajax({
		cache: false,
		crossDomain: true,
		type: 'post',
		dataType: "html",
		url: 'ajax.php?t=getheatmapexporticons',
		data: { sprid: sprid, anketa: anketa},
		error: function(response) {
			console.log("Error in AjaxHeatMapBackGround connection! Please try later!");
		},
		success: function(response) {
 			//vnesi v mesto za gumbe za izvoz zeleno kodo
			$('#izvozHeatmap').html(response);
		}
    });
}
 

/* function prikaziPodatke(){
 	var i = 1;
	//var r = 50;
	var r = 1;
 	for (var row in heatmap_data_holder) {
        var row_object = heatmap_data_holder[row];
        //var pos = {lat: row_object.lat, lng: row_object.lng};
        var pos = {x: row_object.lat, y: row_object.lng, value: row_object.text, radius: r};
        //kreiraj marker
        //createMarker(spremenljivka, row_object.address, pos, row_object.text);
		$('#heatmap_canvas_all'+spremenljivka).append(i+". appended pos: "+pos.x+" "+pos.y+" value: "+pos.value+"<br />");
		i++;		
    }
} */


//pridobi podatke za ureditev ozadja heatmap
function getHeatMapBackGround() {
	$.ajax({
            cache: false,
            crossDomain: true,
            type: 'post',
            dataType: "text",
            url: 'ajax.php?t=heatmapBackgroundData',
            data: { heatmapBackground_data: spremenljivka},
            error: function(response) {
                console.log("Error in AjaxHeatMapBackGround connection! Please try later!");
            },
            success: function(response) {
                heatmapBackground_data_holder = response;			
				
				//dodajanje slike, da pobere potrebne podatke za ureditev slike v ozadju
				$('#heatmap_canvas_all_'+spremenljivka).append(heatmapBackground_data_holder);
				//dodaj id background sliki, ki je koristno za mozen izvoz slike
 				$('#heatmap_canvas_all_'+spr_id).find('img').attr('id', 'heatmap-background');
				
				//****** risanje ozadja heatmap porocila
				//pobiranje potrebnih podatkov
				var bgSrc = $('#heatmap_canvas_all_'+spr_id).find('img').attr('src');
				//ali je online slika ali lokalna		
				var slikaNaStrezniku = checkBgSrc(bgSrc);
				if(slikaNaStrezniku<=0){	//ce slika ni na strezniku, uporabi tmp sliko, ki je na strezniku
					var url = getSiteUrl();					
					bgSrc = url+"uploadi/editor/"+spr_id+"tmpImage.png";
					$('#heatmap_canvas_all_'+spr_id).find('img').attr('src', bgSrc);					
				}
				//ali je online slika ali lokalna - konec
				var bgWidth = $('#heatmap_canvas_all_'+spr_id).find('img').width();
				var bgHeight = $('#heatmap_canvas_all_'+spr_id).find('img').height();
				
				//pravilno dimezioniranje celotnega canvas-a, za kasnejso pravilno velikost slike
				$('#heatmap_canvas_all_'+spr_id).width(bgWidth);
				$('#heatmap_canvas_all_'+spr_id).height(bgHeight);				
				
				//risanje slike v ozadju canvasa
				$('#heatmap-canvas').width(bgWidth);
				$('#heatmap-canvas').height(bgHeight);
				$('#heatmap-canvas').css("background", "transparent url('"+bgSrc+"') no-repeat left top");
				$('#heatmap-canvas').css("background-size", bgWidth+"px "+bgHeight+"px");
				
				//inicializiraj heatmap
				heatmap = h337.create({
				  container: document.getElementById('heatmap_canvas_all_'+spr_id),
				  opacity:.5,
				  //radius: 10,
				  // this line makes datapoints unblurred
				  blur: 0.7
				});
				
				drawHeatmap();	//izrisi heatmap
            }
    });
}

function checkBgSrc(bgSrc){
	var findme = 'editor/';
	var pos = bgSrc.indexOf(findme);	//najdi pozicijo teksta 'editor/'
	return pos;	
}


function getSiteUrl(){	
	$.ajax({
		cache: false,
		crossDomain: true,
		type: 'post',
		dataType: "text",
		url: 'ajax.php?t=getSiteUrl',
		error: function(response) {
			console.log("Error in AjaxHeatMapBackGround connection! Please try later!");
		},
		success: function(response) {
			siteUrl = response;
		}
    });
	return siteUrl;
}


//pridobi podatke o tockah iz baze in za risanje heatmap
function getHeatMapDataAjax() {	
    $.ajax({
            cache: false,
            crossDomain: true,
            type: 'post',
            dataType: "json",
            url: 'ajax.php?t=heatmapData',
            data: { heatmap_data: heatmap_data_holder, anketa:  anketa_id},
            error: function(response) {
                console.log("Error in AjaxHeatMapDataAjax connection! Please try later!");
            },
            success: function(response) {
				$('#heatmap_canvas_all').attr('id', 'heatmap_canvas_all_'+spremenljivka);
				eliminateDuplicates();
                //map_data_holder zdaj postane holder markerjev
                heatmap_data_holder = response;
				//heatmapInit();				
				heatmapInit(spremenljivka);
				getHeatMapBackGround();
            }
    });
}
//pridobi podatke o tockah iz baze in za risanje heatmap - konec


function eliminateDuplicates(){	
	var i = 1;
	//odstranitev odvecnih podatkov v object-u, saj se podvojijo
	for (var row in heatmap_data_holder) {
        var row_object = heatmap_data_holder[row];
		if (row == 0){
/* 			console.log("row:"+row);
			console.log("row_object[0]"+row_object[0]);
			console.log("row_object[1]"+row_object[1]);
			console.log("row_object[2]"+row_object[2]);
			console.log("row_object[3]"+row_object[3]); */
			delete row_object[0];
			delete row_object[1];
			delete row_object[2];
			delete row_object[3];
		}

    }
}

function insert(str, index, value) {   
   return str.substring(0, index) + value + str.substring(index);
}


function exportHeatmapAsImage(spr_id){
	//console.log("radij v exportHeatmapAsImage: "+r);
	//******* pretvorba canvasa v sliko	
	var canvas = document.getElementById("heatmap-canvas");

	var img = document.getElementById("heatmap-background");	//background slika
	
	//pobiranje potrebnih podatkov
	var bgWidth = $('#heatmap_canvas_all_'+spr_id).find('img').width();
	var bgHeight = $('#heatmap_canvas_all_'+spr_id).find('img').height();
	
	var ctx = canvas.getContext("2d");
	
	//risi background sliko na canvas
	if(slikaNalozena){
		izrisiSliko(canvas, img, bgWidth, bgHeight, ctx);
	}else{
		img.onload = function(){
			izrisiSliko(canvas, img, bgWidth, bgHeight, ctx);
			slikaNalozena = 1;
		}
	}

}

function izrisiSliko(canvas, img, bgWidth, bgHeight, ctx){
	//potrebna alfa transparenca, da sta vidni tako background slika kot heatmap
	ctx.globalAlpha = 0.4;
	
	ctx.drawImage(img, 0, 0, bgWidth, bgHeight);
	
	var image = canvas.toDataURL("image/png");
	//console.log("Raw image data:"+image);

	$.ajax({
		cache: false,
		crossDomain: true,
		type: 'post',
		dataType: "text",
		url: 'ajax.php?t=saveHeatmapImage',
		data: { sprid: spr_id, image: image},
		error: function(response) {
			console.log("Error in SurveyHeatMapSaveImage connection! Please try later!");
		},
		success: function(response) {
			//console.log("Image saved!");
		}
	});	
}