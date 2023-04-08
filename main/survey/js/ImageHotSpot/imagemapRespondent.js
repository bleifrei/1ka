var vre_id_prej = [];	//polje, ki belezi indeks prejsnjega obmocja
var refresh = [];	//polje, ki belezi, ali je bil refresh ali ne
var refreshed = [];	//polje, ki belezi, ali je bil refresh ali ne
//init za radio grid
function mapinitRadioGrid (spremenljivka, options, hotspot_region_visibility_option, hotspot_tooltips_option, hotspot_region_color, hotspot_visibility_color) {
	
	$('#hotspot_image_'+spremenljivka+' .grid_table').css("display", "none");	//skrij tabelo z odgovori radio button, kjer se bodo bele�ili odgovori
	
	hotspot_visibility_color = hotspot_visibility_color.slice(1);
	
	//identifier za sliko na katero se veze mapa z obmocji
	var image1 = $('#hotspot_'+spremenljivka+'_image');	
	
	//urejanje prikazovanja obmocij za respondente
	if (hotspot_region_visibility_option == 0){	//skrij obmocja
		hotspot_region_visibility_option = false;
		var hotspot_region_visibility_option_highlight = false;
		var hotspot_region_visibility_option_clicked = false;
	}else if (hotspot_region_visibility_option == 1){	//pokazi obmocja
		hotspot_region_visibility_option = true;
		var hotspot_region_visibility_option_highlight = true;
		var hotspot_region_visibility_option_clicked = false;
	}else if (hotspot_region_visibility_option == 2){	//pokazi obmocja ob mouse over
		hotspot_region_visibility_option = false;
		var hotspot_region_visibility_option_highlight = true;
		var hotspot_region_visibility_option_clicked = false;
	}else if (hotspot_region_visibility_option == 3){
		hotspot_region_visibility_option = false;
		var hotspot_region_visibility_option_highlight = false;
		var hotspot_region_visibility_option_clicked = true;
	}
	
	//za handle-anje refresh in naprej/nazaj **********************************************************************
	//vre_id_d belezi, ali ob morebitnem odgovarjanju in naprej/nazaj ali refresh-u, kateri radio button je izbran	
	var vre_id_d = [];
	var grid_id = [];
	
	var hiddenInputs = $('#hotspot_image_'+spremenljivka+' .grid_table').children('tbody').children('tr');
	
 	hiddenInputs.each(function (index, value) {
		//vre_id_d[index] = $(this).val();
		var that = $(this);
		//vre_id_d[index] = $(this).attr('id');
		//console.log(vre_id_d[index]);
		that.each(function (i, value) { 
			var this_id = $(this).attr('id');
			var vre_id_child = $('#'+this_id).find('input[ checked="" ]').attr('vre_id');	//vre_id tistih radio buttonov, ki so izbrani
			var grid_id_child = $('#'+this_id).find('input[ checked="" ]').val();	//vre_id tistih radio buttonov, ki so izbrani
			//console.log("vre_id_child:"+vre_id_child+" z indeksom:"+index);
			vre_id_d[index] = vre_id_child;
			grid_id[vre_id_child] = grid_id_child;
			refreshed[vre_id_child] = false;
			//console.log("refreshed za vre_id:"+vre_id_d[index]+" je false v imagemapRespondent");
			//console.log("vre_id:"+vre_id_d[index]);
		});
		//console.log("vre_id_d: "+vre_id_d[index]);
		
	});
	
/* 	$('#hotspot_image_'+spremenljivka+' .checked input[ checked="" ]').each(function (index, value) { 
		vre_id_d[index] = $(this).val();				
		console.log(vre_id_d[index]);
	}); */


	 
	 if(vre_id_d[0] === undefined){
		refresh[spremenljivka] = false;
		//console.log("Not refreshed");
	}else{
		refresh[spremenljivka] = true;
		//console.log("Refreshed");
	}
	
	//za handle-anje refresh in naprej/nazaj - konec ********************************************************************
	
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
	
	//funkcija, ki se sprozi ob prikazovanju tooltip-a
	function urediRadioGrid(data){
		//console.log("Uredi Radio z data: "+data.key);
		var vre_id = 0;
		var identifikator = '';
		//if(refresh[spremenljivka] && refreshed[data.key] == false){
		if(refreshed[data.key] == false){
			vre_id_d.forEach(function (item, i){
				//console.log("grem cez vse, ce je refresh data.key:"+data.key);
				//console.log("Vred_id z indeksom "+i+" je:"+item);
				if(item == data.key){
					//console.log("Sta enaka:"+data.key);
					vre_id = item;
					//console.log("Vre_id r:"+vre_id)
					identifikator = '#vrednost_'+vre_id+"_grid_"+grid_id[vre_id];
				}
			});
		}else{
			vre_id = data.key;
			identifikator = '#vrednost_if_'+vre_id;
			//console.log("Vre_id not r:"+vre_id);
		}
		//console.log("Vre_id:"+vre_id);
		//var identifikator = '#vrednost_if_'+vre_id;
		//console.log("identifikator:"+identifikator);
		
		if($(identifikator).hasClass('checked')){	//ce je checked
			//oznaci na tooltip ustrezen radio button
			var id = $(identifikator+' input:checked').attr("id");	//id zabelezenega radio button-a v multigrid-u
			id = "im_"+id;
			//console.log("id:"+id);
			$('#'+id).prop( "checked", true );	//oznacitev ustreznega radio button-a v tooltip-u			
		}else if($(identifikator).attr('checked')){
			//oznaci na tooltip ustrezen radio button
			var id = $(identifikator).attr('id');	//id zabelezenega radio button-a v multigrid-u
			id = "im_"+id;
			//console.log("id:"+id);
			$('#'+id).prop( "checked", true );	//oznacitev ustreznega radio button-a v tooltip-u		
		}
		
	}
	
	//funkcija, ki se sprozi ob kliku na obmocje
	function pokaziNamigObKliku(data){
		if (hotspot_tooltips_option == 2){
			var vre_id = data.key;
			image1.mapster('tooltip',vre_id); 
		}
	}
	
	//funkcija, ki uredi delovanje toolTipClose
	function urediZapiranjeNamiga(data){
		if (hotspot_tooltips_option == 2){	//ce je moznost prikazovanja namiga ob kliku na obmocje
			var vre_id = data.key;
			return ["area-click"];
		}else if(hotspot_tooltips_option != 2){	//drugace, naj se namig zapre ob kliku na sam namig
			return ["tooltip-click"];
		}
	}
	
	//funkcija, ki uredi delovanje prikazovanja namiga
	function urediNamig(data){
		//urejanje prikazovanja namigov/tooltips za respondente
		if (hotspot_tooltips_option == 0){	//Prikazi namig ob vstopu miske
			var out = true;
			return out;
		}else if (hotspot_tooltips_option == 1){	//Skrij namig
			var out = false;
			return out;
		} else if (hotspot_tooltips_option == 2){	//Prikazi namig ob kliku miske
			var out = false;
			return out;
		}
	}
	
	//urejanje prikazovanja namigov/tooltips za respondente
	if (hotspot_tooltips_option == 0){	//Prikazi namig ob vstopu miske
		var hotspot_show_tooltips = true;
	}else if (hotspot_tooltips_option == 1){	//Skrij namig
		var hotspot_show_tooltips = false;
	} else if (hotspot_tooltips_option == 2){	//Prikazi namig ob kliku miske
		var hotspot_show_tooltips = false;
	}

	hotspot_region_color = hotspot_region_color.slice(1);
	
	//options za prikaz ze izbranega obmocja
	var izbrano_obmocje_prikaz = {	//uredi prikaz izbranega obmocja za trenutno obmocje
		//fillColor: '00ffff',
		fillColor: hotspot_region_color,
		stroke: false,
		strokeColor: '000000',
		strokeWidth: 2
	};
	
	image1
		.mapster({
			scaleMap: false,	//zelo pomemben parameter, ker drugace se koordinate spremenijo
			//fillOpacity: 0.4,
			fillColor: hotspot_visibility_color,
			//stroke: false,
			//strokeColor: "3320FF",
			//strokeOpacity: 0.8,
			//strokeWidth: 2,
			//singleSelect: true,	//ce je prisoten, ni mozno pokazati vec obmocij na enkrat
			isSelectable: false,	//na false, da se ne skrije prikaz obmocja ob kliku na njega
			mapKey: 'name',
			listKey: 'name',
			//showToolTip: true,
			showToolTip: hotspot_show_tooltips,
			//showToolTip: urediNamig,
			highlight: hotspot_region_visibility_option_highlight,
			//toolTipClose: ["area-click"],   //ob kliku na obmocje, se tooltip zapre
			//toolTipClose: ["tooltip-click"],
			toolTipClose: ["image-mouseout"],
			//toolTipClose: urediZapiranjeNamiga,
			onShowToolTip: urediRadioGrid,
			onClick: pokaziNamigObKliku
		})
		//.mapster('set',true,obmocja_string)	//pokazi na sliki mape obmocij, obmocja naj bodo vidna od samega zacetka
		//.mapster('set',hotspot_region_visibility_option,obmocja_string) //ce je ta nastavitev prisotna, ob refreshu nekaj v knjiznici se sesuje
		.mapster('set_options', options);
		
		if(vre_id_d[0] !== undefined){	//zadeva se sprozi ob morebitnem refresh-u strani ali naprej/nazaj po anketi
			//console.log("Ni undefined za spr: "+spremenljivka);
			//$('.mapster_tooltip').find('').;
		}
		
		
}

//ob kliku na radio button v tooltip - za radio grid
function mapdelovanjeRadioGrid(htmlobject, vre_id_d){
	htmlobjectid = htmlobject.id.substring(3);	//odstrani prve tri crke trenutnega id-ja iz tooltip-a za dobiti id iz multigrid
	
	if ($('#'+htmlobjectid).is(':checked')){	//ce je radio button ze klikan
		$('#'+htmlobject.id).prop( "checked", false );	//odstrani check iz tooltip-a
		$('#'+htmlobjectid).prop( "checked", false );	//v skritem klasicnem multigrid-u odstrani ustrezen odgovor
	}else{
		$('#'+htmlobjectid).prop( "checked", true );	//v skritem klasicnem multigrid-u izberi ustrezen odgovor
		refreshed[vre_id_d] = true;
		//console.log("refreshed za vre_id:"+vre_id_d+" je "+refreshed[vre_id_d]+" v mapdelovanjeRadioGrid");
	}
	
}


//init za radio
/* var vre_id_prej = [];	//polje, ki belezi indeks prejsnjega obmocja
var refresh = [];	//polje, ki belezi, ali je bil refresh ali ne */
function mapinitRadio(spremenljivka, options, tip, hotspot_region_visibility_option, hotspot_tooltips_option, hotspot_region_color, hotspot_visibility_color) {
	
	$('#hotspot_image_'+spremenljivka+' .variabla').css("display", "none");	//skrij radio/checkbox button odgovore, kjer se bodo bele�ili odgovori
	$('#hotspot_image_'+spremenljivka+' .missing').css("display", "");	//pokazi missing radio/checkbox odgovore, ce so prisotni
	
	//urejanje prikazovanja obmocij za respondente
	if (hotspot_region_visibility_option == 0){	//skrij obmocja
		hotspot_region_visibility_option = false;
		var hotspot_region_visibility_option_highlight = false;
		var hotspot_region_visibility_option_clicked = false;
	}else if (hotspot_region_visibility_option == 1){	//pokazi obmocja
		hotspot_region_visibility_option = true;
		var hotspot_region_visibility_option_highlight = true;
		var hotspot_region_visibility_option_clicked = false;
	}else if (hotspot_region_visibility_option == 2){	//pokazi obmocja ob mouse over
		hotspot_region_visibility_option = false;
		var hotspot_region_visibility_option_highlight = true;
		var hotspot_region_visibility_option_clicked = false;
	}else if (hotspot_region_visibility_option == 3){
		hotspot_region_visibility_option = false;
		var hotspot_region_visibility_option_highlight = false;
		var hotspot_region_visibility_option_clicked = true;
	}
	
	//urejanje prikazovanja namigov/tooltips za respondente
	if (hotspot_tooltips_option == 0){	//Prikazi namig ob vstopu miske
		hotspot_tooltips_option = true;
		//console.log("hotspot_tooltips_option: "+hotspot_tooltips_option);
		//var hotspot_region_visibility_option_highlight = false;
		//var hotspot_region_visibility_option_clicked = false;
	}else if (hotspot_tooltips_option == 1){	//Skrij namig
		hotspot_tooltips_option = false;
		//console.log("hotspot_tooltips_option: "+hotspot_tooltips_option);
		//var hotspot_region_visibility_option_highlight = true;
		//var hotspot_region_visibility_option_clicked = false;
	}else if (hotspot_tooltips_option == 2){	//Prikazi namig ob kliku miske
		hotspot_tooltips_option = false;
		//var hotspot_region_visibility_option_highlight = true;
		//var hotspot_region_visibility_option_clicked = false;
	}
	
	
	//vre_id_d belezi, ali ob morebitnem odgovarjanju in naprej/nazaj ali refresh-u, kateri radio button je izbran
	if (tip == 1){
		var vre_id_d = $('#hotspot_image_'+spremenljivka+' .checked input[name=vrednost_'+spremenljivka+']').val();
		//console.log("vre_id_d: "+vre_id_d+" za spr: "+spremenljivka);
	}else if(tip == 2){
		var vre_id_d = [];
		$('#hotspot_image_'+spremenljivka+' .checked input[ checked="" ]').each(function (index, value) { 
			vre_id_d[index] = $(this).val();
			//console.log(vre_id_d[index]);
		});
		
/* 		//var vre_id_d = $('#hotspot_image_'+spremenljivka+' .checked input[name=vrednost_'+spremenljivka+']').val();
		vre_id_d = $('#hotspot_image_'+spremenljivka+' .checked input[ checked="" ]').val();
		console.log(vre_id_d[0]);	 */	
	}

	 
	 if(vre_id_d === undefined){
		refresh[spremenljivka] = false;
	}else{
		refresh[spremenljivka] = true;
	}
	
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
	
	hotspot_visibility_color = hotspot_visibility_color.slice(1);
	//options za default prikaz obmocja
	var default_prikaz = {
		scaleMap: false,	//zelo pomemben parameter, ker drugace se koordinate spremenijo
		isSelectable: false,	//na false, da se ne skrije prikaz obmocja ob kliku na njega
		mapKey: 'name',
		highlight: hotspot_region_visibility_option_highlight,
		//listKey: 'name',
		//showToolTip: true
		showToolTip: hotspot_tooltips_option,
		fillColor: hotspot_visibility_color
	};
	
	hotspot_region_color = hotspot_region_color.slice(1);
	//options za prikaz izbranega obmocja
	var izbrano_obmocje_prikaz = {	//uredi prikaz izbranega obmocja za trenutno obmocje
		//fillColor: '00ffff',
		fillColor: hotspot_region_color,
		stroke: false,
		strokeColor: '000000',
		strokeWidth: 2
	};
	
	//funkcija, ki se sprozi ob kliku na obmocje
/* 	function urediRadio(data){
		//console.log("Uredi Radio z data: "+data.key);
 		var vre_id = data.key;
		if ($('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).is(':checked')){	//ce je radio button ze klikan
			$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).prop( "checked", false);	//odstranitev oznacitve ustreznega radio button-a v skritem menuju spremenljivka_4448_vrednost_23664
			image1.mapster('set', false, vre_id);
			image1.mapster('set', true, vre_id, default_prikaz); //uredi default prikaz obmocja za trenutno obmocje
		}else{
			$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).prop( "checked", true);	//oznacitev ustreznega radio button-a v skritem menuju
			//console.log(image1);
			image1.mapster('set', false, vre_id);	//spucaj trenutno izbrano obmocje iz slike
			image1.mapster('set', false, vre_id_prej[spremenljivka]);	//spucaj prejsnje izbrano obmocje iz slike
			if (vre_id != vre_id_prej[spremenljivka]){	//ce sta razlicna obmocja
				image1.mapster('set', true, vre_id_prej[spremenljivka], default_prikaz); //uredi default prikaz obmocja za prejsnje obmocje
			}
			image1.mapster('set', true, vre_id, izbrano_obmocje_prikaz); //uredi prikaz izbranega obmocja za trenutno obmocje
			vre_id_prej[spremenljivka] = vre_id;
		}
	} */
	
	function urediObmocja(data){
		//console.log("Uredi Radio z data: "+data.key);
		//console.log("Clicked! hotspot_region_visibility_option_highlight je :"+hotspot_region_visibility_option_highlight);
 		var vre_id = data.key;
	//if(hotspot_region_visibility_option_clicked == false){
		//if ($('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).is(':checked')){	//ce je radio button ze klikan
		if ( $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).is(':checked') || $('#missing_value_spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).is(':checked')){	//ce je radio button ali missing ze oznacen 
			
			$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).prop( "checked", false);	//odstranitev oznacitve ustreznega radio button-a v skritem menuju spremenljivka_4448_vrednost_23664
			
			$('#missing_value_spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).prop( "checked", false); //odstranitev oznacitve ustreznega missing-a (missing_value_spremenljivka_4466_vrednost_23822)
			
			image1.mapster('set', false, vre_id);
			
			//image1.mapster('set', true, vre_id, default_prikaz); //uredi default prikaz obmocja za trenutno obmocje
			image1.mapster('set', hotspot_region_visibility_option, vre_id, default_prikaz); //uredi default prikaz obmocja za trenutno obmocje
			
		}else{	//ce radio button ni ze klikan
			
			//console.log(image1);
			
			image1.mapster('set', false, vre_id);	//spucaj trenutno izbrano obmocje iz slike
			
 			if ( (vre_id_d !== undefined) && (refresh[spremenljivka] == true) ){	//ce sta razlicna obmocja
				//console.log("Ni undefined");
				vre_id_prej[spremenljivka] = vre_id_d;
				refresh[spremenljivka] = false;
			}
			
			if (tip == 1){	//ce je radio tip postavitve, je potrebno ustrezno urediti obmocja
				image1.mapster('set', false, vre_id_prej[spremenljivka]);	//spucaj prejsnje izbrano obmocje iz slike
				if (vre_id != vre_id_prej[spremenljivka]){	//ce sta razlicna obmocja
					//image1.mapster('set', true, vre_id_prej[spremenljivka], default_prikaz); //uredi default prikaz obmocja za prejsnje obmocje
					image1.mapster('set', hotspot_region_visibility_option, vre_id_prej[spremenljivka], default_prikaz); //uredi default prikaz obmocja za prejsnje obmocje 
					
				}				
			}
			
			//ce je checkbox in je missing, ki je oznacen
			if( tip == 2 && ($('#hotspot_image_'+spremenljivka).children().hasClass('missing')) && ($('#hotspot_image_'+spremenljivka).children().hasClass('checked')) ){
				//console.log("Missing");	//nic ne narediti
			}else{	//drugace
				$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).prop( "checked", true);	//oznacitev ustreznega radio button-a / checkbox v skritem menuju
				image1.mapster('set', true, vre_id, izbrano_obmocje_prikaz); //uredi prikaz izbranega obmocja za trenutno obmocje
				//image1.mapster('set', hotspot_region_visibility_option, vre_id, izbrano_obmocje_prikaz); //uredi prikaz izbranega obmocja za trenutno obmocje
			}
			
			vre_id_prej[spremenljivka] = vre_id;

		}
	//}else if(hotspot_region_visibility_option_clicked == true){
	//	image1.mapster('set', hotspot_region_visibility_option_clicked, vre_id);
		//console.log("Clicked! hotspot_region_visibility_option_highlight je :"+hotspot_region_visibility_option);
	//}
	}	
	
	
	image1
		.mapster({
			scaleMap: false,	//zelo pomemben parameter, ker drugace se koordinate spremenijo
			isSelectable: false,	//na false, da se ne skrije prikaz obmocja ob kliku na njega
			mapKey: 'name',
			highlight: hotspot_region_visibility_option_highlight,
			//listKey: 'name',
			//showToolTip: true,
			showToolTip: hotspot_tooltips_option,
			onClick: urediObmocja,
			fillColor: hotspot_visibility_color
		})
		//.mapster('set',true,obmocja_string)	//pokazi na sliki mape obmocij, obmocja naj bodo vidna od samega zacetka
		.mapster('set', hotspot_region_visibility_option, obmocja_string)	//pokazi na sliki mape obmocij, obmocja naj bodo vidna od samega zacetka
		.mapster('set_options', options);

 	if(vre_id_d !== undefined){	//zadeva se sprozi ob morebitnem refresh-u strani ali naprej/nazaj po anketi
		//console.log("Ni undefined za spr: "+spremenljivka);
		image1.mapster('set', false, vre_id_d);	//spucaj trenutno izbrano obmocje iz slike
		image1.mapster('set', true, vre_id_d, izbrano_obmocje_prikaz);	//uredi prikaz prej izbranega obmocja za tole obmocje
		//image1.mapster('set', hotspot_region_visibility_option, vre_id_d, izbrano_obmocje_prikaz);	//uredi prikaz prej izbranega obmocja za tole obmocje
	}
}


//init za ranking
function mapinitRanking (spremenljivka, options, hotspot_region_visibility_option, hotspot_tooltips_option, hotspot_region_color, hotspot_visibility_color) {
	
	$('#hotspot_image_'+spremenljivka+' .variabla').css("display", "none");	//skrij ranking ostevilcevanje, kjer se bodo bele�ili odgovori
	//$('#hotspot_image_'+spremenljivka+' .komentar').css("display", "none");	//skrij textarea, kjer se bodo bele�ili komentarji
	
	hotspot_visibility_color = hotspot_visibility_color.slice(1);
	
	//identifier za sliko na katero se veze mapa z obmocji
	var image1 = $('#hotspot_'+spremenljivka+'_image');	
	
	//urejanje prikazovanja obmocij za respondente
	if (hotspot_region_visibility_option == 0){	//skrij obmocja
		hotspot_region_visibility_option = false;
		var hotspot_region_visibility_option_highlight = false;
		var hotspot_region_visibility_option_clicked = false;
	}else if (hotspot_region_visibility_option == 1){	//pokazi obmocja
		hotspot_region_visibility_option = true;
		var hotspot_region_visibility_option_highlight = true;
		var hotspot_region_visibility_option_clicked = false;
	}else if (hotspot_region_visibility_option == 2){	//pokazi obmocja ob mouse over
		hotspot_region_visibility_option = false;
		var hotspot_region_visibility_option_highlight = true;
		var hotspot_region_visibility_option_clicked = false;
	}else if (hotspot_region_visibility_option == 3){
		hotspot_region_visibility_option = false;
		var hotspot_region_visibility_option_highlight = false;
		var hotspot_region_visibility_option_clicked = true;
	}
	
	//za handle-anje refresh in naprej/nazaj **********************************************************************
	//vre_id_d belezi, ali ob morebitnem odgovarjanju in naprej/nazaj ali refresh-u, kateri radio button je izbran	
	var vre_id_d = [];
	
	
	var values = [];	
	
	//preleti skrite odgovore razvrscanje-ostevilcevanje in belezi vrednosti odgovorov ####################
	$('#hotspot_image_'+spremenljivka+' .variabla input').each(function(index){
		//console.log("value: "+$(this).attr('value'));
		var value = $(this).attr('value');
		var vre_id = $(this).attr('vred_id');
		//console.log("vre_id po refreshu: "+vre_id);
 		if(value!=''){
			values[vre_id] = value;
			refreshed[vre_id] = false;
			$(this).parent().addClass('checked');	//dodaj info, da je prisoten odgovor
		}else{
			refreshed[vre_id] = true;
		}
		//console.log("vrednosti odgovorov po refreshu: "+value+" z indeksom: "+index);		
	});
	
	
	var valuesLength = values.length;	//hrani stevilo nepraznih odgovorov
	//console.log("Velikost polja values: "+valuesLength);
	
	//preleti skrite odgovore razvrscanje-ostevilcevanje in belezi vrednosti odgovorov - konec ##############
	
	//za handle-anje refresh in naprej/nazaj - konec ********************************************************************
	
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
	
	//funkcija, ki se sprozi ob prikazovanju tooltip-a
	function urediRadioGrid(data){

		var vre_id = 0;
		var identifikator = '';
		vre_id = data.key;

		// ce se je zgodil refresh, ali se je uporabnik vrnil na stran z vprasanjem, zapolni odgovore
		if(refreshed[data.key] == false){	

			$('#im_vrednost_if_'+vre_id+' .category input').each(function(index){
				var id = $(this).attr('id');					
				var vrstni_red = $(this).attr('vrstni_red');
				
				if(vrstni_red == values[vre_id]){
					$('#'+id).prop( "checked", true );

					// disable ostale odgovore, ki so bili izbrani ze drugje
					disableOthers(spremenljivka, vre_id);
		
					// enable trenuten odgovor, ce je disable-an
					enableCurrent(id);			
				}
			});
		}
		else{
			identifikator = '#vrednost_if_'+vre_id;
		}

		// ce je checked
		if($(identifikator).hasClass('checked')){

			// oznaci na tooltip ustrezen radio button
			var id = $(identifikator+' input').attr('id');	//id zabelezenega odgovora
			id = $('#vrednost_if_'+vre_id).attr('tooltipid');

			id = 'im_'+id;
			$('#'+id).prop( "checked", true );	//oznacitev ustreznega radio button-a v tooltip-u
			
			// disable ali skriti ostale odgovore, ki so bili izbrani ze drugje
			disableOthers(spremenljivka, vre_id);		
			
			// enable trenuten odgovor, ce je disable-an
			enableCurrent(id);
		}
		else if($(identifikator).attr('checked')){
			// oznaci na tooltip ustrezen radio button
			var id = $(identifikator).attr('id');	//id zabelezenega radio button-a v multigrid-u
			id = "im_"+id;

			$('#'+id).prop( "checked", true );	//oznacitev ustreznega radio button-a v tooltip-u		
		}
		else if(refreshed[data.key]==true){	
			// enable trenuten odgovor, ce je disable-an
			enableCurrent(id);		

			// disable ostale odgovore, ki so bili izbrani ze drugje
			disableOthers(spremenljivka, vre_id);
		}

		// ZA KOMENTAR
		var identifikatorKomentar = '#vrednost_if_komentar_'+vre_id;
		if($(identifikatorKomentar+' input').attr('value')){	//ce je komentar prisoten v skritem textarea
			var komentarTooltip = $(identifikatorKomentar+' input').attr('value');
			$('#im_vrednost_komentar_'+vre_id).attr('value', komentarTooltip);
		}
	}
	
	// funkcija, ki se sprozi ob kliku na obmocje
	function pokaziNamigObKliku(data){
		if (hotspot_tooltips_option == 2){
			var vre_id = data.key;
			image1.mapster('tooltip', vre_id); 
		}
	}
	
	//funkcija, ki uredi delovanje toolTipClose
	function urediZapiranjeNamiga(data){
		if (hotspot_tooltips_option == 2){	//ce je moznost prikazovanja namiga ob kliku na obmocje
			var vre_id = data.key;
			return ["area-click"];
		}else if(hotspot_tooltips_option != 2){	//drugace, naj se namig zapre ob kliku na sam namig
			return ["tooltip-click"];
		}
	}
	
	//funkcija, ki uredi delovanje prikazovanja namiga
	function urediNamig(data){
		//urejanje prikazovanja namigov/tooltips za respondente
		if (hotspot_tooltips_option == 0){	//Prikazi namig ob vstopu miske
			var out = true;
			return out;
		}else if (hotspot_tooltips_option == 1){	//Skrij namig
			var out = false;
			return out;
		} else if (hotspot_tooltips_option == 2){	//Prikazi namig ob kliku miske
			var out = false;
			return out;
		}
	}
	
	//urejanje prikazovanja namigov/tooltips za respondente
	if (hotspot_tooltips_option == 0){	//Prikazi namig ob vstopu miske
		var hotspot_show_tooltips = true;
	}
	else if (hotspot_tooltips_option == 1){	//Skrij namig
		var hotspot_show_tooltips = false;
	} 
	else if (hotspot_tooltips_option == 2){	//Prikazi namig ob kliku miske
		var hotspot_show_tooltips = false;
	}

	hotspot_region_color = hotspot_region_color.slice(1);
	
	// options za prikaz ze izbranega obmocja
	var izbrano_obmocje_prikaz = {	//uredi prikaz izbranega obmocja za trenutno obmocje
		//fillColor: '00ffff',
		fillColor: hotspot_region_color,
		stroke: false,
		strokeColor: '000000',
		strokeWidth: 2
	};

	
	// samo pri sazu anketi - obarvamo oznacena obmocja
	var areas = [];
	if($('#hotspot_image_' + spremenljivka + '_sazu').val() == '1'){

		var colors = {
            1: '009900',
            2: '88ff00',
            3: 'ffff00',
			4: 'ff9900',
			5: 'ff3300',
			6: 'bb0000'
		};

		$('#hotspot_image_' + spremenljivka + ' .variabla.checked').each(function(i, obj) {
			var id_string = $(obj).attr('id');
			var id = id_string.split('_');

			var vrstni_red = $(obj).find('input').val();

			// Obarvamo obmocje z vrednostjo
			var area = { 
				key: id[2].toString(),
				fillColor: colors[vrstni_red],
				selected: true
			}

			areas.push(area);
		});
	}

	image1
		.mapster({
			scaleMap: false,	//zelo pomemben parameter, ker drugace se koordinate spremenijo
			
			//fillOpacity: 0.4,
			fillColor: hotspot_visibility_color,
			
			stroke: true,
			strokeColor: "1e88e5",
			strokeOpacity: 0.8,
			strokeWidth: 2,

			singleSelect: false,	//ce je prisoten, ni mozno pokazati vec obmocij na enkrat
			//isSelectable: false,	//na false, da se ne skrije prikaz obmocja ob kliku na njega
			mapKey: 'name',
			listKey: 'name',

			showToolTip: hotspot_show_tooltips,
			highlight: hotspot_region_visibility_option_highlight,
			
			toolTipClose: [],

			onShowToolTip: urediRadioGrid,
			onClick: pokaziNamigObKliku,

			isDeselectable: false,

			areas: areas,	// samo pri sazu anketi - obarvamo oznacena obmocja
		})
		//.mapster('set',hotspot_region_visibility_option,obmocja_string)	//ce je ta nastavitev prisotna, ob refreshu nekaj v knjiznici se sesuje
		.mapster('set_options', options);
}



//ob kliku na radio button v tooltip - za ranking
function mapdelovanjeRanking(htmlobject, vre_id_d){

	htmlobjectid = htmlobject.id.substring(3);	//odstrani prve tri crke trenutnega id-ja iz tooltip-a za dobiti id iz multigrid
	var vrstni_red = 0;

	//ce je ze prisoten odgovor v razvrscanju
	if ($('#vrednost_if_'+vre_id_d+' input').attr('value') == $('#im_'+htmlobjectid).attr('vrstni_red')){	
		
		$('#'+htmlobject.id).prop( "checked", false );	//odstrani check iz tooltip-a
		$('#vrednost_if_'+vre_id_d+' input').val(''); //v skritem razvrscanju odstrani ustrezen odgovor
		$('#vrednost_if_'+vre_id_d+' input').attr('value', '');	//v skritem razvrscanju uredi odgovor, da se bo belezil v bazo
	
		refreshed[vre_id_d] = true;
	}
	else{
		vrstni_red = $('#im_'+htmlobjectid).attr('vrstni_red');

		$('#vrednost_if_'+vre_id_d+' input').val(vrstni_red);	//v skritem razvrscanju uredi odgovor, da se vidi
		$('#vrednost_if_'+vre_id_d+' input').attr('value', vrstni_red);	//v skritem razvrscanju uredi odgovor, da se bo belezil v bazo
		$('#vrednost_if_'+vre_id_d).attr('tooltipid', htmlobjectid);	//zabelezi id tooltip-a za kasneje

		refreshed[vre_id_d] = true;
	}
}
//ob kliku na radio button v tooltip - za ranking za SAZU
function mapdelovanjeRankingSazu(htmlobject, vre_id_d){

	var colors = {
        1: '009900',
        2: '88ff00',
        3: 'ffff00',
        4: 'ff9900',
        5: 'ff3300',
		6: 'bb0000'
	};

	htmlobjectid = htmlobject.id.substring(3);	//odstrani prve tri crke trenutnega id-ja iz tooltip-a za dobiti id iz multigrid
	var vrstni_red = 0;

	//ce je ze prisoten odgovor v razvrscanju
	if ($('#vrednost_if_'+vre_id_d+' input').attr('value') == $('#im_'+htmlobjectid).attr('vrstni_red')){	
		
		$('#'+htmlobject.id).prop( "checked", false );	//odstrani check iz tooltip-a
		$('#vrednost_if_'+vre_id_d+' input').val(''); //v skritem razvrscanju odstrani ustrezen odgovor
		$('#vrednost_if_'+vre_id_d+' input').attr('value', '');	//v skritem razvrscanju uredi odgovor, da se bo belezil v bazo
	
		refreshed[vre_id_d] = true;

		// Razbarvamo obmocje
		colorArea(vre_id_d.toString(), 'ffffff');
	}
	else{
		vrstni_red = $('#im_'+htmlobjectid).attr('vrstni_red');

		$('#vrednost_if_'+vre_id_d+' input').val(vrstni_red);	//v skritem razvrscanju uredi odgovor, da se vidi
		$('#vrednost_if_'+vre_id_d+' input').attr('value', vrstni_red);	//v skritem razvrscanju uredi odgovor, da se bo belezil v bazo
		$('#vrednost_if_'+vre_id_d).attr('tooltipid', htmlobjectid);	//zabelezi id tooltip-a za kasneje

		refreshed[vre_id_d] = true;

		// Obarvamo obmocje
		colorArea(vre_id_d.toString(), colors[vrstni_red]);
	}
}

// ob pisanju v textarea za komentar v tooltip - za ranking
function mapdelovanjeRankingKomentar(textarea_id, comment){

	// v skritem razvrscanju uredi odgovor, da se bo belezil v bazo
	$('#' + textarea_id).attr('value', comment);	
	
	//console.log("komentar: " + comment);
}


//disable ostale odgovore, ki so bili izbrani ze drugje
function disableOthers(spremenljivka, vre_id){

	$('#hotspot_image_'+spremenljivka+' .checked input').each(function(){
		var value = $(this).attr('value');		
		
		$('#im_vrednost_if_'+vre_id+' .category input').each(function(){
			var vrstni_red = $(this).attr('vrstni_red');

			if(vrstni_red == value){

				$(this).parent().addClass('disabled-item');		// dodamo class za disabled
				$(this).attr('disabled','disabled');			// disable odgovor, ki je ze izbran
				$(this).parent().css('cursor','not-allowed');	// spremeni kurzor miske odgovora, ki je ze izbran
			}
		});
	});	
}


//enable trenuten odgovor, ce je disable-an
function enableCurrent(id){

	$('#'+id).parent().removeClass('disabled-item');		// odstrtanimo class za disabled
	$('#'+id).parent().addClass('active-item');				// dodamo class za active

	$('#'+id).prop( "disabled", "" );
	$('#'+id).parent().css('cursor','pointer');
}

// Zapremo tooltip
function removeMapsterTooltip(){
	
	// Dobimo id obmocja za katerega imamo odprt tooltip
	var id_string = $(".mapster_tooltip > div").attr('id');
	var id = id_string.split('_');

	// Deselectamo obmocje tooltipa - samo ce ni oznacen noben radio gumb
	if(!$(".mapster_tooltip .question input:checked").val())
		$('img').mapster('set', false, id);

	$('img').mapster('tooltip');
}

// Pobarvamo doloceno obmocje
function colorArea(area_id, color){

	var newOp = { 
		areas: [
			{ 
				key: area_id,
				fillColor: color
			}
	   ]
	};	
	// Deselectamo obmocje
	$('img').mapster('set', false, area_id);
	// Nastavimo usrezno barvo
	$('img').mapster('set_options', newOp);
	// Ga na novo selectamo
	$('img').mapster('set', true, area_id);
}
