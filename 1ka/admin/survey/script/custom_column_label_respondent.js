function change_custom_column_label_respondent(size, spremenljivka, value){
	
	//console.log("Izbrana možnost: "+value+" spremenljivka: "+spremenljivka+" size: "+size);
	
 	if(value == 2){	//ce je trenutna moznost prilagajanja "le koncne"
		var i = 0;
 		if(size%2 == 0){	//ce je parno stevilo, spoji polovico label na vsako skupino label
			var colspan_calc_1 = colspan_calc_2 = size / 2;
		}else if(size%2 != 0){	//ce ni parno stevilo, spoji prvi skupini label eno celico vec kot pri drugi skupini label
			var colspan_calc_1 = (size / 2) + 0.5;
			var colspan_calc_2 = (size / 2) - 0.5;
		}		
		
		$('#spremenljivka_'+spremenljivka+' table.grid_table > thead > tr > td.category').each(function(){	//preleti labele

			//var test = $(this).text();			
			//console.log("Test: "+test);
			i = i + 1;
			//console.log("I-ti tekst: "+i);
  			
			if(i != 1 && i != size){	//ce ni prva ali zadnja labela oz. vmesnim labelam,
				//console.log("v IFu: "+i);
				$(this).children().text("");	//odstrani tekst					
				$(this).remove();	//odstrani celico
			}
  			if(i == 1){	//ce je prva labela,
				$(this).attr('colspan', colspan_calc_1);	//razsiri celico oz. spoji z ostalimi prostimi celicami za tole skupino label
				$(this).attr('style', 'text-align: left');	//tekst koncne skupine label poravnaj levo
				$(this).removeClass('alignLeft');	//odstrani levo poravnavo
			}
			if(i == size){	//ce je zadnja labela,
				$(this).attr('colspan', colspan_calc_2);	//razsiri celico oz. spoji z ostalimi prostimi celicami za tole skupino label
				$(this).attr('style', 'text-align: right'); //tekst koncne skupine label poravnaj desno
				$(this).removeClass('alignLeft');	//odstrani levo poravnavo
			}
		});
		
		$('#spremenljivka_'+spremenljivka+' table.grid_table > tbody > tr > td.category').each(function(){	//preleti vse radio buttone
			$(this).removeClass('alignLeft');	//in odstrani levo poravnavo
		});
				
	}else if(value == 3){	//ce je trenutna moznost prilagajanja "koncne in vmesna"		
		var i = 0;
		
				
 		if(size % 3 == 0){	//ce je velikost deljiva s 3, spoji vsako tretjino label
			var colspan_calc_1 = colspan_calc_2 = colspan_calc_3 = size / 3;
			var middle = 1 + size / 3;
		}else if(size % 3 == 1){	//ce pri deljenju z 3 je ostanek 1		
			var colspan_calc_1 = colspan_calc_2 = parseInt(size / 3);
			var colspan_calc_3 = parseInt(size / 3) + 1;
			var middle = 1 + parseInt(size / 3);
		}else if(size % 3 == 2){	//ce pri deljenju z 3 je ostanek 2
			var colspan_calc_1 = colspan_calc_2 =  1 + parseInt(size / 3);
			var colspan_calc_3 = parseInt(size / 3);
			var middle = size % 3 + parseInt(size / 3);
		}		
		
		$('#spremenljivka_'+spremenljivka+' table.grid_table > thead > tr > td.category').each(function(){	//preleti labele
			
			i = i + 1;
			
  			if(i != 1 && i != size && i != middle){	//ce ni prva ali zadnja labela oz. vmesnim labelam,
					$(this).children().text("");	//odstrani tekst					
					$(this).remove();	//odstrani celico
			}
 			if(i == 1){	//ce je prva labela,
				$(this).attr('colspan', colspan_calc_1);	//razsiri celico oz. spoji z ostalimi prostimi celicami za tole skupino label
				$(this).attr('style', 'text-align: left');	//tekst koncne skupine label poravnaj levo
				$(this).removeClass('alignLeft');	//odstrani levo poravnavo
			}
			if(i == size){	//ce je zadnja labela,
				$(this).attr('colspan', colspan_calc_2);	//razsiri celico oz. spoji z ostalimi prostimi celicami za tole skupino label
				$(this).attr('style', 'text-align: right'); //tekst koncne skupine label poravnaj desno
				$(this).removeClass('alignLeft');	//odstrani levo poravnavo
			}
			if(i == middle){	//ce je vmesna labela,
				$(this).attr('colspan', colspan_calc_3);	//razsiri celico oz. spoji z ostalimi prostimi celicami za tole skupino label
				$(this).attr('style', 'text-align: center'); //tekst koncne skupine label poravnaj sredinsko
				$(this).removeClass('alignLeft');	//odstrani levo poravnavo
			}
		});
		
		$('#spremenljivka_'+spremenljivka+' table.grid_table > tbody > tr > td.category').each(function(){	//preleti vse radio buttone
			$(this).removeClass('alignLeft');	//in odstrani levo poravnavo
		});
	}
	else if(value == 4){	//hanging
		
	}
}