function change_custom_column_label_option(size, spremenljivka, value){
	
	//console.log("Izbrana možnost: "+value+" spremenljivka: "+spremenljivka+" size: "+size);
	
 	if(value == 2){	//ce je trenutna moznost prilagajanja "le koncne"		
		var g_last = 'g_'+size;
		
 		if(size%2 == 0){	//ce je parno stevilo, spoji polovico label na vsako skupino label
			var colspan_calc_1 = colspan_calc_2 = size / 2;
		}else if(size%2 != 0){	//ce ni parno stevilo, spoji prvi skupini label eno celico vec kot pri drugi skupini label
			var colspan_calc_1 = (size / 2) + 0.5;
			var colspan_calc_2 = (size / 2) - 0.5;
		}		
		
		$('.grid_naslovi_'+spremenljivka+' > td').each(function(){	//preleti labele
			var grd = $(this).attr('grd');
  			if(String(grd) != 'g_1' && String(grd) != g_last){	//ce ni prva ali zadnja labela oz. vmesnim labelam,
				if(grd !== undefined){				
					$(this).children().text("");	//odstrani tekst					
					$(this).remove();	//odstrani celico
				}
			}
 			if(String(grd) == 'g_1'){	//ce je prva labela,
				$(this).attr('colspan', colspan_calc_1);	//razsiri celico oz. spoji z ostalimi prostimi celicami za tole skupino label
				$(this).attr('style', 'text-align: left');	//tekst koncne skupine label poravnaj levo
			}
			if(String(grd) == g_last){	//ce je zadnja labela,
				$(this).attr('colspan', colspan_calc_2);	//razsiri celico oz. spoji z ostalimi prostimi celicami za tole skupino label
				$(this).attr('style', 'text-align: right'); //tekst koncne skupine label poravnaj desno
			}
		});
		
	}else if(value == 3){	//ce je trenutna moznost prilagajanja "koncne in vmesna"		
		var g_last = 'g_'+size;
				
 		if(size % 3 == 0){	//ce je velikost deljiva s 3, spoji vsako tretjino label
			var colspan_calc_1 = colspan_calc_2 = colspan_calc_3 = size / 3;
			var g_middle = 'g_'+(1 + size / 3);
        }
        else if(size % 3 == 1){	//ce pri deljenju z 3 je ostanek 1		
			var colspan_calc_1 = colspan_calc_2 = parseInt(size / 3);
			var colspan_calc_3 = parseInt(size / 3) + 1;
			var g_middle = 'g_'+(1 + Math.ceil(size / 3));
        }
        else if(size % 3 == 2){	//ce pri deljenju z 3 je ostanek 2
			var colspan_calc_1 = colspan_calc_2 =  1 + parseInt(size / 3);
			var colspan_calc_3 = parseInt(size / 3);
			var g_middle = 'g_'+(2 + parseInt(size / 3));
		}		

		$('.grid_naslovi_'+spremenljivka+' > td').each(function(){	//preleti labele
			var grd = $(this).attr('grd');
              
            if(String(grd) != 'g_1' && String(grd) != g_last && String(grd) != g_middle){	//ce ni prva ali zadnja labela oz. vmesnim labelam,
				if(grd !== undefined){				
					$(this).children().text("");	//odstrani tekst					
					$(this).remove();	//odstrani celico
				}
            }
            
 			if(String(grd) == 'g_1'){	//ce je prva labela,
				$(this).attr('colspan', colspan_calc_1);	//razsiri celico oz. spoji z ostalimi prostimi celicami za tole skupino label
				$(this).attr('style', 'text-align: left');	//tekst koncne skupine label poravnaj levo
			}
			if(String(grd) == g_last){	//ce je zadnja labela,
				$(this).attr('colspan', colspan_calc_2);	//razsiri celico oz. spoji z ostalimi prostimi celicami za tole skupino label
				$(this).attr('style', 'text-align: right'); //tekst koncne skupine label poravnaj desno
			}
			if(String(grd) == g_middle){	//ce je vmesna labela,
				$(this).attr('colspan', colspan_calc_3);	//razsiri celico oz. spoji z ostalimi prostimi celicami za tole skupino label
				$(this).attr('style', 'text-align: center'); //tekst koncne skupine label poravnaj sredinsko
			}
		});
		
	}
	else if(value == 4){	//hanging
		
	}	
}


//funkcija ni v uporabi, sem zadevo uredil v vprasanje.js funkcija change_diferencial(spremenljivka, enota)
function custom_column_label_option_visibility (enota, tip){
	if( (tip == 6 || tip == 16) && (enota == 1 || enota == 0) ){
		$('.drop_custom_column_labels').css('display', '');
	}else{
		$('.drop_custom_column_labels').css('display', 'none');
	}	
}