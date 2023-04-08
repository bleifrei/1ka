function mapinit_editor (spremenljivka) {
	
	//$('#hotspot_image_'+spremenljivka+' .grid_table').css("display", "none");	//skrij tabelo z odgovori radio button, kjer se bodo beležili odgovori
	
	var map = $('#hotspot_'+spremenljivka+'_map');
	var izbranaObmocja = {};
	
	//pokazi vse atribute name, ki so children od map
/* 	test.children().each(function (index, value) { 
	console.log('div' + index + ':' + $(this).attr('name')); 
	}); */
	var obmocja = [];	//polje za belezenje imen obmocij
	
	//poberi imena obmocij (area) map in jih zabelezi b polje "obmocja"
	map.children().each(function (index, value) { 
		obmocja[index] = $(this).attr('name');
	});
	
	//polje obmocja preuredi v besedilo
	var obmocja_string = obmocja.toString();

	//identifier za sliko na katero se veze mapa z obmocji
	var image1 = $('#hotspot_'+spremenljivka+'_image');
	
	image1
		.mapster({
			scaleMap: false,	//zelo pomemben parameter, ker drugace se koordinate spremenijo
			fillOpacity: 0.4,
			fillColor: "d42e16",
			stroke: true,
			strokeColor: "3320FF",
			strokeOpacity: 0.8,
			//strokeWidth: 2,
			//singleSelect: true,	//ce je prisoten, ni mozno pokazati vec obmocij na enkrat
			isSelectable: false,	//na false, da se ne skrije prikaz obmocja ob kliku na njega
			mapKey: 'name',
			listKey: 'name'
		})
		.mapster('set',true,obmocja_string);	//pokazi na sliki mape obmocij, obmocja naj bodo vidna od samega zacetka
}