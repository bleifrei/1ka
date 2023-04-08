//funkcija, ki ureja skviranje/prikazovanje ob izbiri checkbox-a za trak
function diferencial_trak_checkbox_prop (spremenljivka, num_grids){
	if( $('#diferencial_trak_'+spremenljivka).is( ":checked" )){	//ce hocemo viden trak
		$('#diferencial_trak_hidden_'+spremenljivka).prop('disabled', true);	//disable hidden polje z obratno vrednostjo
		$('.diferencial_trak_starting_num_class_'+spremenljivka).css('display', 'block');	//pokazi vnosno polje za zacetno stevilo traku
		$('.trak_num_of_titles_class').css('display', 'block');	//pokazi dropdown za izbiro stevila nadnaslovov traku
		$('.grid_defaults_class').css('display', 'none');	//skrij dropdowna za privzete vrednosti gridov
		$('.grid_var_class').css('display', 'none');	//skrij moznosti za izbiro privzetih vrednosti
		diferencial_trak_change_values(spremenljivka, num_grids); //spremeni vrednosti skritih vrednosti odgovorov
		$('.drop_custom_column_labels').css('display', 'none');	//skrij "Uporaba label"		
	}
	else {
		$('#diferencial_trak_hidden_'+spremenljivka).prop('disabled', false);
		$('.diferencial_trak_starting_num_class_'+spremenljivka).css('display', 'none'); //skrij vnosno polje za zacetno stevilo traku
		$('.trak_num_of_titles_class').css('display', 'none');	//skrij dropdown za izbiro stevila nadnaslovov traku
		$('.grid_defaults_class').css('display', 'block');	//pokazi dropdowna za privzete vrednosti gridov
		$('.grid_var_class').css('display', 'block');	//pokazi moznosti za izbiro privzetih vrednosti
		$('.drop_custom_column_labels').css('display', 'block');	//pokazi "Uporaba label"
	}
}

//funkcija, ki spremeni vrednosti skritih vrednosti odgovorov
function diferencial_trak_change_values(spremenljivka, num_grids){
	num_grids = $('.drop_grids_num option:selected').val();
	
	var diferencial_trak_starting_num = parseInt($('#diferencial_trak_starting_num_'+spremenljivka).val());	//trenutna rocno vpisana zacetna vrednost traku
	
	var new_vrednosti_odgovorov = [];	//hrani nove vrednosti skritih vrednosti odgovorov
	new_vrednosti_odgovorov[0] = diferencial_trak_starting_num;	//prva vrednost je rocno vnesena vrednost s katero se trak zacne
	//console.log(new_vrednosti_odgovorov[0]);
	for(var i = 1; i < num_grids; i++){	//iz zacetne rocno vpisane vrednosti zgeneriraj se ostale glede na izbrano stevilo odgovorov
		new_vrednosti_odgovorov[i] = new_vrednosti_odgovorov[i - 1] + 1;
	}
	
	var j = 0;
	$('#grid_variable_'+spremenljivka+' .grid_variable_inline').each(function(){	//pojdi skozi vse skrite vrednosti odgovorov in jih ustrezno spremeni
		$(this).text(new_vrednosti_odgovorov[j]);
		j = j + 1;
	});
	
	//dinamicno spremeni skrite vrednosti odgovorov v bazi
	$.post('ajax.php?t=vprasanje&a=diferencial_trak_skrite_vrednosti', {spr_id: spremenljivka, num_grids: num_grids, diferencial_trak_starting_num: diferencial_trak_starting_num});
	
}

//urejanje videza nadnaslovov traka in dropdown-a
function trak_edit_num_titles(size, spremenljivka, trak_num_of_titles, trak_nadnaslov){
	//console.log("Izbrana možnost: "+trak_num_of_titles+" spremenljivka: "+spremenljivka+" size: "+size);

	var g_last = 'g_'+trak_num_of_titles;
	var colspan_calc = [];
	var g_middle = [];
	
	if(size % trak_num_of_titles == 0){	//ce je stevilo deljivo s trenutnim izbranim stevilom label, spoji ustrezno stevilo label na vsako skupino label
		for(var i = 1; i<=trak_num_of_titles; i++){
			colspan_calc[i] = size / trak_num_of_titles;
			//console.log("colspan_calc za "+i+" je "+colspan_calc[i]);
			if(i != 1 && i != trak_num_of_titles){
				g_middle[i] = 'g_'+i;
				//console.log("g_middle "+i+" je "+g_middle[i]);	
			}				
		}
	}else if(size % trak_num_of_titles == 2){		
		for(var i = 1; i<=trak_num_of_titles; i++){
			//console.log("colspan_calc za "+i+" je "+colspan_calc[i]);
			if(i != 1 && i != trak_num_of_titles){
				g_middle[i] = 'g_'+i;
				colspan_calc[i] = parseInt(size / trak_num_of_titles);
			}else{
				colspan_calc[i] = 1 + parseInt(size / trak_num_of_titles);				
			}
		}
	}else if(trak_num_of_titles == 2){	//
		if(size % trak_num_of_titles == 0){
			for(var i = 1; i<=trak_num_of_titles; i++){
				colspan_calc[i] = size / trak_num_of_titles;
			}
		}else{
			colspan_calc[1] = (size / trak_num_of_titles) + 0.5;
			colspan_calc[trak_num_of_titles] = (size / trak_num_of_titles) - 0.5;
		}
	}
	
	var indeks = 1;
	$('.display_trak_num_of_titles_'+spremenljivka+' > td').each(function(){	//preleti labele
		var grd = $(this).attr('grd');
		if(String(grd) != 'g_1' && String(grd) != g_last && String(grd) != g_middle[indeks]){	//ce ni prva ali zadnja labela oz. vmesnim labelam,
			if(grd !== undefined){				
				$(this).children().text("");	//odstrani tekst					
				$(this).remove();	//odstrani celico
			}
		}
		if(String(grd) == 'g_1'){	//ce je prva labela,
			$(this).attr('colspan', colspan_calc[1]);	//razsiri celico oz. spoji z ostalimi prostimi celicami za tole skupino label
			$(this).attr('style', 'text-align: left');	//tekst koncne skupine label poravnaj levo
			$(this).children().text(trak_nadnaslov[String(1)]);
			indeks++;
		}
		if(String(grd) == g_last){	//ce je zadnja labela,
			$(this).attr('colspan', colspan_calc[trak_num_of_titles]);	//razsiri celico oz. spoji z ostalimi prostimi celicami za tole skupino label
			$(this).attr('style', 'text-align: right'); //tekst koncne skupine label poravnaj desno
			$(this).children().text(trak_nadnaslov[String(trak_num_of_titles)]);
		}
		if(String(grd) == g_middle[indeks]){	//ce je vmesna labela,
			$(this).attr('colspan', colspan_calc[indeks]);	//razsiri celico oz. spoji z ostalimi prostimi celicami za tole skupino label
			$(this).attr('style', 'text-align: center'); //tekst koncne skupine label poravnaj sredinsko
			$(this).children().text(trak_nadnaslov[String(indeks)]);
			indeks++;
		}
		
	});
}


function change_trak_num_of_titles(spremenljivka, size){

	var trakNumOfTitlesSize = $('#trak_num_of_titles option').length;
	var isTrakNumOfTitlesNow = false;	//belezi, ce je med novimi vrednostmi dropdown-a trenutno izbrano stevilo vnosov
	var deljivaStevila = [];	//polje, ki belezi deljiva stevila po trenutnem izbranem trakNumOfTitlesSize
	var trakNumOfTitles = $('#trak_num_of_titles option:selected').val();

	$('#trak_num_of_titles').empty();//sprazni dropdown s stevilom vnosov
	
	deljivaStevila = soDeljiva(size); //najdi vsa stevila, ki so deljiva z izbranim stevilom odgovorov
	
	for (var i=0; i<deljivaStevila.length; i++){	//napolni dropdown z ustreznimi stevili vnosov
		//console.log(i);
		$('#trak_num_of_titles').append('<option value='+deljivaStevila[i]+'>'+deljivaStevila[i]+'</option>');
		if(deljivaStevila[i] == $('#trak_num_of_titles option:selected').val()){
			isTrakNumOfTitlesNow = true;
		}
	}

 	if(isTrakNumOfTitlesNow){
		var trakNumOfTitlesNow = trakNumOfTitles;//trenutno izbrano stevilo vnosov
	}else{
		var trakNumOfTitlesNow = deljivaStevila[0];//prvo stevilo v novem seznamu deljivih stevil
	}
	
	$('#trak_num_of_titles').val(trakNumOfTitlesNow);//spremeni vrednost dropdown-a s stevilom trenutnih vidnih vnosov
	
}

function soDeljiva(size){
	var deljivaStevila = [];
	var indeksDeljivihStevil = 1;
	deljivaStevila[0] = 2;	//ker si zelimo, da je mozno pri vseh imeti vsaj dva nasnaslova
	for(var i = 3; i<=size; i++){
		if(size%i == 0){
			deljivaStevila[indeksDeljivihStevil] = i;
			indeksDeljivihStevil++;
		}else if(size%i == 2){
			deljivaStevila[indeksDeljivihStevil] = i;
			indeksDeljivihStevil++;
		}
	}
	return deljivaStevila;
}


var elem_now_trak = [];		//belezi id trenutne izbire v select box
var elem_before_trak = [];	//belezi id prejsnje izbire v select box
var klik_trak = [];		//belezi, ali je bil select box že poklikan

function trak_change_bg(this_s, diferencial_trak, spremenljivka){
	if (diferencial_trak){	//ce je trak vklopljen
		var children = $(this_s).find('input[type=radio]').attr('id');	//id kliknjenega radio button-a
		var vre_id = $(this_s).find('input[type=radio]').attr('vre_id');	//id kliknjenega radio button-a

		elem_now_trak[spremenljivka] = children;
				
		$('#variabla_'+vre_id).children().removeClass('trak_container_bg');	//odstrani barvo ozadja za oznacen odgovor
		
		$('#'+children).attr('checked','checked');	//oznaci ustrezni radio button
		var trak = children.replace("foo", "trak_tbl");

		if ( !klik_trak[spremenljivka] && (elem_now_trak[spremenljivka] != elem_before_trak[spremenljivka]) ){
			$('#'+trak).addClass('trak_container_bg');	//preuredi ozadje z želeno barvo			
			klik_trak[spremenljivka] = 1;
		}else if ( klik_trak[spremenljivka] && (elem_now_trak[spremenljivka] != elem_before_trak[spremenljivka]) ){
			$('#'+trak).addClass('trak_container_bg');	//preuredi ozadje z želeno barvo			
		}else if ( klik_trak[spremenljivka] && (elem_now_trak[spremenljivka] == elem_before_trak[spremenljivka]) ){
			$('#variabla_'+vre_id).children().removeClass('trak_container_bg');	//odstrani barvo ozadja za oznacen odgovor
			klik_trak[spremenljivka] = 0;
		}else if ( !klik_trak[spremenljivka] && (elem_now_trak[spremenljivka] == elem_before_trak[spremenljivka]) ){
			$('#'+trak).addClass('trak_container_bg');	//preuredi ozadje z želeno barvo
			klik_trak[spremenljivka] = 1;
		}
		/* 		
		if( $('#'+children).is(":checked") ){	//ce trenutni odgovor je ze izbran, 
			$('#variabla_'+vre_id).children().removeClass('trak_container_bg');	//odstrani barvo ozadja za oznacen odgovor
			console.log("Dodaj ozadje");
		} */
		elem_before_trak[spremenljivka] = elem_now_trak[spremenljivka];
	}
}