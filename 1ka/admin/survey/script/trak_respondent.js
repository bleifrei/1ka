//urejanje videza nadnaslovov traka in dropdown-a
function trak_edit_num_titles_respondent(size, spremenljivka, trak_num_of_titles, trak_nadnaslov){
	//console.log("Izbrana možnost: "+trak_num_of_titles+" spremenljivka: "+spremenljivka+" size: "+size);


	var g_last = 'gr_'+trak_num_of_titles;
	var colspan_calc = [];
	var g_middle = [];
	
	if(size % trak_num_of_titles == 0){	//ce je stevilo deljivo s trenutnim izbranim stevilom label, spoji ustrezno stevilo label na vsako skupino label
		for(var i = 1; i<=trak_num_of_titles; i++){
			colspan_calc[i] = size / trak_num_of_titles;
			//console.log("colspan_calc za "+i+" je "+colspan_calc[i]);
			if(i != 1 && i != trak_num_of_titles){
				g_middle[i] = 'gr_'+i;
				//console.log("g_middle "+i+" je "+g_middle[i]);
			}				
		}
	}else if(size % trak_num_of_titles == 2){		
		for(var i = 1; i<=trak_num_of_titles; i++){
			//console.log("colspan_calc za "+i+" je "+colspan_calc[i]);
			if(i != 1 && i != trak_num_of_titles){
				g_middle[i] = 'gr_'+i;
				//console.log("g_middle "+i+" je "+g_middle[i]);
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
	$('.display_trak_num_of_titles_respondent_'+spremenljivka+' > td').each(function(){	//preleti labele
		var grd = $(this).attr('grd');
		if(String(grd) != 'gr_1' && String(grd) != g_last && String(grd) != g_middle[indeks]){	//ce ni prva ali zadnja labela oz. vmesnim labelam,
			if(grd !== undefined){				
				$(this).children().text("");	//odstrani tekst					
				$(this).remove();	//odstrani celico
			}
		}
		if(String(grd) == 'gr_1'){	//ce je prva labela,
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