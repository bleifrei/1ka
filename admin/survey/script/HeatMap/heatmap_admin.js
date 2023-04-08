//funkcija, ki skrbi za delovanje checkbox-a za izbiro prikazovanja klikov na sliko ali ne
function heatmap_show_clicks_checkbox_prop (spremenljivka){
	if( $('#heatmap_show_clicks_'+spremenljivka).is( ":checked" )){	//ce hocemo vidne klike
		$('#heatmap_show_clicks_hidden_'+spremenljivka).prop('disabled', true);	//disable hidden polje z obratno vrednostjo za checkbox
		$('#heatmap_clicks_settings_'+spremenljivka).css("display", "block");	//pokazi dodatne nastavitve za klike
	}
	else {
		$('#heatmap_show_clicks_hidden_'+spremenljivka).prop('disabled', false);
		$('#heatmap_clicks_settings_'+spremenljivka).css("display", "none");	//skrij dodatne nastavitve za klike
	}
}

//funkcija, ki skrbi za delovanje checkbox-a za izbiro prikazovanja stevca klikov ali ne
function heatmap_show_counter_clicks_checkbox_prop (spremenljivka){
	if( $('#heatmap_show_counter_clicks_'+spremenljivka).is( ":checked" )){	//ce hocemo vidne klike
		$('#heatmap_show_counter_clicks_hidden_'+spremenljivka).prop('disabled', true);	//disable hidden polje z obratno vrednostjo za checkbox
	}
	else {
		$('#heatmap_show_counter_clicks_hidden_'+spremenljivka).prop('disabled', false);
	}
}

//funkcija, ki skrbi za dinamično prikazovanje/skrivanje nastavitve za stevec klikov ob spremembi stevila klikov
function showHeatMapClickCounter(heatmap_num_clicks, spremenljivka){
	if(heatmap_num_clicks > 1){	//ce je najvecje stevilo moznih klikov vec kot 1
		$('.heatmap_show_counter_clicks_class').css("display", "block");	//pokazi nastavitev za prikazovanje stevca
	}else{
		$('.heatmap_show_counter_clicks_class').css("display", "none");	//skrij nastavitev za prikazovanje stevca
		$('#heatmap_show_counter_clicks_'+spremenljivka).prop('checked', false);
		$('#heatmap_show_counter_clicks_hidden_'+spremenljivka).prop('disabled', false);
	}
	
}

//funkcija za prikazovanje in skrivanje nastavitev image hotspot iz okna z nastavitvami trenutnega vprasanja
function show_hot_spot_settings_4Heatmap (spremenljivka, tip, hotspot_image){
	var heatmap_region_settings = false;
	$('#hot_spot_fieldset_'+spremenljivka).children().each(function(){	//preleti obmocja
		heatmap_region_settings = $('#hot_spot_fieldset_'+spremenljivka).children().hasClass('hotspot_region');
	});
		
		
	$('#hot_spot_fieldset_'+spremenljivka).css('display', ''); //pokazi hot_spot_fieldset

	//pridobitev hotspot_image
	var hotspot_image = function(){	//dinamicna pridobitev parametra hotspot_image, ki hrani html za prikaz slike
		$.ajaxSetup({async:false});  //execute synchronously
		var tmp = null;		
		$.post('ajax.php?t=vprasanje&a=get_hotspot_image', {
				spremenljivka: spremenljivka
		}, function (data) {
			tmp = data;		
		});
		return tmp;
	}();	

	if( (hotspot_image == '') || (hotspot_image.substring(0,4) != '<img') ) {	//ce ni slike, 
		$('#hot_spot_regions_add_button').css('display', 'none'); //skrij gumb za dodajanje obmocij
		//$('#heatmap_region_settings_'+spremenljivka).css('display', 'none');	//skrij nastavitve obmocja
		$('#hotspot_message').css('display', ''); //pokazi sporocilo, da je potrebno najprej dodati sliko
	}else if (hotspot_image.substring(0,4) == '<img'){	//ce je slika prisotna
		$('#hot_spot_regions_add_button').css('display', '');	//pokazi gumb za dodajanje obmocja
		$('#hotspot_message').css('display', 'none');
	}
	
	if(heatmap_region_settings){
		//$('#heatmap_region_settings_'+spremenljivka).css('display', '');	//pokazi nastavitve obmocja
	}else{			
		//$('#heatmap_region_settings_'+spremenljivka).css('display', 'none');	//skrij nastavitve obmocja
	}
	
	//primerjaj stevilo vnosov v srv_vrednost in srv_hotspot_regions za trenutno spremenljivko in preuredi srv_vrednost, ce je to potrebno
	var enako_stevilo_vnosov_za_hotspot = function(){	//dinamicna pridobitev stevila vnosov
		$.ajaxSetup({async:false});  //execute synchronously
		var tmp = null;		
		$.post('ajax.php?t=vprasanje&a=get_hotspot_stevilo_vnosov', {
				spremenljivka: spremenljivka
		}, function (data) {
			tmp = data;		
		});
		return tmp;
	}();
}


//funkcija, ki skrbi za delovanje sliderja, ki omogoca izbiro velikosti klikov
//var radij = 5;
function UpdateClickSizeSlider(radij, spremenljivka) {
	document.querySelector('#heatmapClickSizeValue_'+spremenljivka).value = radij;
	//console.log(radij);	
}