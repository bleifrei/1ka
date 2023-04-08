//function init_colorPicker(id, type) {	
function init_colorPicker(id) {	
	// init farbtastic
	var colorPicker = '#picker';
	
	//var f = $.farbtastic('#picker_region');
	var f = $.farbtastic(colorPicker);
	//var p = $('#picker_region').hide();
	var p = $(colorPicker).hide();
	var selected;
	$('.colorwell')
	  .each(function () { f.linkTo(this); $(this).css('opacity', 0.75); })
	  .focus(function() {
		if (selected) {
		  $(selected).css('opacity', 0.75).removeClass('colorwell-selected');
		}
		f.linkTo(this);
		p.show();
		$(selected = this).css('opacity', 1).addClass('colorwell-selected');
	  });
		
		
	
	// init auto save
  	$('.auto-save').change(function () {
		//console.log("Changed");
		//te_auto_save(this, true, id);
	}).blur(function () {		
		//$('#picker_region').hide();
		$(colorPicker).hide();
		vprasanje_save(true);
	});
		
	$('.farbtastic').mouseup(function(){	//ko se dvigne prst iz gumba miske pri izbiri barve iz kroga
		vprasanje_save(true);		//shrani barvo
	});
}

//funkcija, ki skrbi za delovanje nastavitve o osvetljevanju
function hotspot_region_visibility_option_checkbox_prop (spremenljivka)
{
	if( $('#hotspot_region_visibility_options_'+spremenljivka).is( ":checked" )){	//ce hocemo vidne klike
		$('#hotspot_region_visibility_option_'+spremenljivka).prop('disabled', true);	//disable hidden polje z obratno vrednostjo za checkbox
		$('#hotspot_region_visibility_'+spremenljivka).css('display', '');
	}
	else {
		$('#hotspot_region_visibility_option_'+spremenljivka).prop('disabled', false);
		$('#hotspot_region_visibility_'+spremenljivka).css('display', 'none');
	}
}

//funkcija, ki skrbi za delovanje nastavitve s komentarjem pri razvrscanju
function hotspot_comment_option_checkbox_prop (spremenljivka)
{
	if( $('#hotspot_comment_options_'+spremenljivka).is( ":checked" )){	//ce hocemo vidne klike
		$('#hotspot_comment_option_'+spremenljivka).prop('disabled', true);	//disable hidden polje z obratno vrednostjo za checkbox
		$('#hotspot_comment_'+spremenljivka).css('display', '');
	}
	else {
		$('#hotspot_comment_option_'+spremenljivka).prop('disabled', false);
		$('#hotspot_comment_'+spremenljivka).css('display', 'none');
	}
}


//funkcija za urejanje in izbiro obmocij @ image hotspot
function hotspot_edit_regions (spr_id, vre_id) {
	
	if (vre_id == 0){	//ce je novo obmocje
		var region_name = "";
		//dobi in pripelji naslednjo vrednost, ki je prisotna po default v tabeli srv_vrednost
		var next_vrednost = function(){
			$.ajaxSetup({async:false});  //execute synchronously
			var tmp = null;		
			$.post('ajax.php?t=vprasanje&a=get_next_hotspot_vrednost', {
					spr_id: spr_id,
			}, function (data) {
				tmp = data;		
			});
			return tmp;
		}();
		
		if(next_vrednost != ""){	//ce je vrednost ze prisotna v tabeli srv_vrednost
			var vrednost = next_vrednost;
		}else{	//ce vrednost ni prisotna v tabeli srv_vrednost, vnesi novo in pripeljijo sem
			//console.log("Next je prazen");
			//pridobitev vrednost oz. vre_id takoj po vnosu nove vrednosti v srv_vrednost + ureditev div za prikazovanje naslova obmocja
			var vrednost = function(){
				$.ajaxSetup({async:false});  //execute synchronously
				var tmp = null;		
				$.post('ajax.php?t=vprasanje&a=hotspot_vrednost_new', {
						spremenljivka: spr_id,
						anketa: srv_meta_anketa_id
				}, function (data) {
					tmp = data;
				});
				return tmp;
			}();		
		}
	}else{	//ce preurejamo ze obstojece obmocje
		var vrednost = vre_id;

		//pridobitev ime obmocja, ki se ga trenutno ureja
		var region_name = function(){
			$.ajaxSetup({async:false});  //execute synchronously
			var tmp = null;		
			$.post('ajax.php?t=vprasanje&a=hotspot_get_region_name', {
					spr_id: spr_id,
					vrednost: vrednost,
					anketa: srv_meta_anketa_id
			}, function (data) {
				tmp = data;
			});
			return tmp;
		}();
	}
	
	//console.log("Vrednost: "+vrednost);
	
	//http slike za hotspot
	var src_image = $('#spremenljivka_contentdiv'+spr_id).find('img').attr('src');
	//var src_image = $('#vre_id_'+vrednost).find('img').attr('src');
	//console.log("Src: "+src_image);
	
	//height in width slike, ki smo jo postimali za hotspot
	var hotspot_image_height = $('#hotspot_image_'+spr_id+'_hidden').find('img').css('height');
	var hotspot_image_width = $('#hotspot_image_'+spr_id+'_hidden').find('img').css('width');
	hotspot_image_height = parseInt(hotspot_image_height.replace('px','')); //nadomesti px s presledkom, da bo samo stevilo
	hotspot_image_width = parseInt(hotspot_image_width.replace('px',''));	
	//console.log("hotspot_image_height: "+hotspot_image_height);
	//console.log("hotspot_image_width: "+hotspot_image_width);
	
	$('#fade').fadeTo('slow', 1);
	$('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?t=vprasanje&a=hotspot_edit_regions', {spr_id: spr_id, hotspot_image_height: hotspot_image_height, hotspot_image_width: hotspot_image_width, src_image: src_image, vrednost: vrednost, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, region_name: region_name}, 
		function () {
			//create_editor_hotspot('vrednost_naslov');
		}
	);
}

//funkcija za prikazovanje in shranjevanje izbrane slike @ image hotspot
function hotspot_image_save (spr_id) {
	var hotspot_image = $('#hotspot_image').val();
	
	// probamo prebrat iz editorja, ce je bil nalozen
	get_editor_close('hotspot_image');
	
	$('#fade').fadeOut('slow');
	$('#vrednost_edit').fadeOut('slow');
	
	var paramArr = $("form[name=hotspot_image_edit]").serialize();

	var query_string = paramArr;


	//ce slika nima dimenzij

	if(decodeURIComponent(query_string).indexOf("style=") == -1){

		hotspot_image = decodeURIComponent(paramArr);
		//console.log(hotspot_image);
		var src_start = hotspot_image.indexOf('src');
		var src_end = hotspot_image.indexOf('"', (src_start+5));
		var src = hotspot_image.substring((src_start+5), src_end);
		
		var img = new Image();
		//img.src = "http://localhost/www/uploadi/editor/1464109632smiley.gif";
		img.src = src;
		
		var width = img.width;
		var height = img.height;
		
		
		query_string = query_string +"&width="+width+"&height="+height;
		hotspot_append_hidden_image(spr_id, width, height, 1);
    }
    else{
		hotspot_append_hidden_image(spr_id, 0, 0, 0);
	}
	
 	$.post('ajax.php?t=vprasanje&a=hotspot_image_save', query_string, function (data) {
		//console.log(data);
		$('#hotspot_image_'+spr_id).html(data);
		$('#hotspot_image_edit').html('');
	});
}

function create_hotspot_input(vre_id, spr_id){

}

// preklici ureditev novega obmocja
function hotspot_region_cancel (spr_id, vre_id) {
	
	//remove_editor('vrednost_naslov');
	//odstrani nepotrebne podatke iz baze
	$.post('ajax.php?t=vprasanje&a=hotspot_region_cancel', {spr_id: spr_id, vre_id: vre_id});
	
	//vrni se iz popup
	$('#fade').fadeOut('slow');
	$('#vrednost_edit').fadeOut('slow');
}

//funkcija za shranjevanje obmocja @ image hotspot
function hotspot_save_regions (spr_id, last_hotspot_region_index, vre_id, hotspot_region_index) {
	//var vrednost = $('input[name=vrednost]').val();
	var hotspot_region_name = $('input[name=hotspot_region_name]').val();

	var hotspot_region_coords = $('#hotspot_region_coords_'+spr_id).val();
	//console.log("Saving region: "+hotspot_region_name);
	//console.log("hotspot_region_coords: "+hotspot_region_coords);

	//if (last_hotspot_region_index != -1){
		last_hotspot_region_index = last_hotspot_region_index + 1;
	//}
		
	//obesi html za prikazovanje imena obmocja v oknu z nastavitvami
	//$('#hot_spot_fieldset_'+spr_id).append('<div id="hotspot_region_name_'+last_hotspot_region_index+'" vre_id="'+vre_id+'" region_index = "'+last_hotspot_region_index+'" class="vrednost_inline" contenteditable="true"><input name="hotspot_region_name_'+last_hotspot_region_index+'" value="'+hotspot_region_name+'"><span class="sprites edit2 inline_hotspot_edit_region"></span><span class="sprites delete_orange inline_hotspot_delete_region"></span></br></div>');
	if(hotspot_region_index == -2){	//ce se ne posodablja obmocja, je potrebno obesiti html za prikazovanje imena obmocja v oknu z nastavitvami
		//$('#hot_spot_fieldset_'+spr_id).append('<div id="hotspot_region_'+last_hotspot_region_index+'" class="hotspot_region"><div id="hotspot_region_name_'+last_hotspot_region_index+'" vre_id="'+vre_id+'" region_index = "'+last_hotspot_region_index+'" class="hotspot_vrednost_inline" contenteditable="true">'+hotspot_region_name+'</div><span class="sprites edit2 inline_hotspot_edit_region"></span><span class="sprites delete_orange inline_hotspot_delete_region"></span></br></div>');
		$('#hot_spot_fieldset_'+spr_id).prepend('<div id="hotspot_region_'+last_hotspot_region_index+'" class="hotspot_region"><div id="hotspot_region_name_'+last_hotspot_region_index+'" vre_id="'+vre_id+'" region_index = "'+last_hotspot_region_index+'" class="hotspot_vrednost_inline" contenteditable="true">'+hotspot_region_name+'</div><span class="sprites edit2 inline_hotspot_edit_region"></span><span class="faicon delete_circle icon-orange_link inline_hotspot_delete_region"></span></br></div>');
	}else{ //drugace posodobi ime obmocja
		$('#hotspot_region_name_'+hotspot_region_index).text(hotspot_region_name);
	}
	
	//pokazi nastavitve obmocja za heatmap
	//$('#heatmap_region_settings_'+spr_id).css('display', '');	//pokazi nastavitve obmocja
	
	//ali potrebujemo nov div variabla?
	var new_hotspot_vrednost_needed = $('#vre_id_'+vre_id).attr('vre_id');
	
	if(new_hotspot_vrednost_needed == undefined){
		//$('#hotspot_regions_hidden_menu_'+spr_id).append('<div id="variabla_'+vre_id+'" class="variabla"><div id="vre_id_'+vre_id+'" class="vrednost_inline" vre_id="'+vre_id+'"></div>'+hotspot_region_name+'</div>');
		$('#hotspot_regions_hidden_menu_'+spr_id).prepend('<div id="variabla_'+vre_id+'" class="variabla" style="display:none;"><div id="vre_id_'+vre_id+'" class="vrednost_inline" vre_id="'+vre_id+'"></div>'+hotspot_region_name+'</div>');
	}else{
		//vnesi v polje z vrednostmi naslov/ime obmocja
		$('#vre_id_'+vre_id).text(hotspot_region_name);
	}
		
	//shrani potrebne podatke v bazo
	$.post('ajax.php?t=vprasanje&a=hotspot_save_regions', {spr_id: spr_id, vre_id: vre_id, hotspot_region_name: hotspot_region_name , hotspot_region_coords: hotspot_region_coords, last_hotspot_region_index: last_hotspot_region_index, hotspot_region_index: hotspot_region_index});
	//console.log("hotspot_save_regions");
	
	//vrni se iz popup
	$('#fade').fadeOut('slow');
	$('#vrednost_edit').fadeOut('slow');
}

//funkcija za dinamicno dodajanje skrite slike, da se pobere dimenzije pri dodajanju obmocij @ image hotspot
function hotspot_append_hidden_image(spr_id, width, height, no_dimensions){
 	var hotspot_image = $('#hotspot_image').val();
	//console.log(hotspot_image);
/* 	var hotspot_image_now = $('#hotspot_image_'+spr_id).html();
	console.log(hotspot_image_now); */
	
	var hotspot_image_hidden_height = $('#hotspot_image_'+spr_id+'_hidden').find('img').css('height');
	if(hotspot_image_hidden_height == undefined){	//ce ni skrite slike, jo dodaj
		$('#hotspot_image_'+spr_id+'_hidden').append(hotspot_image);
	}	
	else if (hotspot_image == ''){	//ce ni slike
		$('#hotspot_image_'+spr_id+'_hidden').find('img').remove();//odstrani skrito sliko
	}
	
	else{		
		$('#hotspot_image_'+spr_id+'_hidden').find('img').remove();//odstrani skrito sliko
		$('#hotspot_image_'+spr_id+'_hidden').append(hotspot_image);
	}
	
	if (no_dimensions == 1){
		//uredi dimenzije skrite slike
		//console.log("No dimensions");
		$('#hotspot_image_'+spr_id+'_hidden').find('img').css('height', height);
		$('#hotspot_image_'+spr_id+'_hidden').find('img').css('width', width);
	}


/* 	console.log("height iz hidden: "+height);
	console.log("width iz hidden: "+width); */
	
}

//funkcija za prikazovanje in skrivanje nastavitev image hotspot iz okna za dodajanje/odstranjevanje slike
function show_hot_spot_settings_from_editor (spremenljivka, enota, tip){
	var hotspot_image = $('#hotspot_image').val();
	
	if((tip == 6 && enota == 10) || (tip == 1 && enota == 10) || (tip == 2 && enota == 10) || tip == 27 || (tip == 17 && enota == 3)){	//ce je tip vprasanja image hotspot ali heatmap
		
		$('#hot_spot_fieldset_'+spremenljivka).css('display', '');//pokazi hot_spot_fieldset
		
		if( (tip == 1) || (tip == 2) || (tip == 17 && enota == 3) ){	//ce je radio ali checkbox ali razvrscanje z image hotspot
			$('#kategorije_odgovorov_'+spremenljivka).css('display', 'none');//skrij fieldset s kategorijami odgovorov
		}
		
		
		if ( (hotspot_image == '') || (hotspot_image.substring(0,4) != '<img') ){	//ce ni slike
			$('#hot_spot_regions_add_button').css('display', 'none');	//skrij gumb za dodajanje obmocij
			$('#hotspot_message').css('display', '');	//pokazi sporocilo, da je potrebno najprej dodati sliko
		}else if (hotspot_image.substring(0,4) == '<img'){	//ce slika je prisotna
			$('#hot_spot_regions_add_button').css('display', '');
			$('#hotspot_message').css('display', 'none');
		}
		
	}else{	//ce ni tip vprasanja image hotspot ali heatmap
		$('#hot_spot_fieldset_'+spremenljivka).css('display', 'none');//skrij hot_spot_fieldset
		$('#kategorije_odgovorov_'+spremenljivka).css('display', '');//pokazi fieldset s kategorijami odgovorov

	}	
}

//funkcija za prikazovanje in skrivanje nastavitev image hotspot iz okna z nastavitvami trenutnega vprasanja
function show_hot_spot_settings (spremenljivka, enota, tip){
	
	//if((tip == 6 && enota == 10) || (tip == 1 && enota == 10) || (tip == 2 && enota == 10)){	//ce je tip vprasanja image hotspot
	if((tip == 6 && enota == 10) || (tip == 1 && enota == 10) || (tip == 2 && enota == 10) || (tip == 17 && enota == 3)){	//ce je tip vprasanja image hotspot
		
		$('#hot_spot_fieldset_'+spremenljivka).css('display', ''); //pokazi hot_spot_fieldset
		
		if( (tip == 1) || (tip == 2) || (tip == 17 && enota == 3)){	//ce je radio ali checkbox ali razvrscanje z image hotspot
			$('#kategorije_odgovorov_'+spremenljivka).css('display', 'none');//skrij fieldset s kategorijami odgovorov
		}

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
			$('#hotspot_message').css('display', ''); //pokazi sporocilo, da je potrebno najprej dodati sliko
		}else if (hotspot_image.substring(0,4) == '<img'){	//ce slika je prisotna
			$('#hot_spot_regions_add_button').css('display', '');
			$('#hotspot_message').css('display', 'none');
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
		
/* 		if (enako_stevilo_vnosov_za_hotspot == 0){
			console.log("Stevilo ni enako!");
		}else{			
			console.log("Stevilo je enako!");
		} */		
	}else{	//ce ni tip vprasanja image hotspot
		$('#hot_spot_fieldset_'+spremenljivka).css('display', 'none');//skrij hot_spot_fieldset	
		$('#kategorije_odgovorov_'+spremenljivka).css('display', '');//pokazi fieldset s kategorijami odgovorov		


	}	
}