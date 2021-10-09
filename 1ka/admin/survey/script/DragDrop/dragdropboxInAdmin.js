//************funkcija, ki skrbi za inicializacijo draggable elementov pri gridih
function GridDraggableBox(tip, spremenljivka, vre_id, ajax, anketa, site_url, usr_id, other, mobile){
	//*****za mobilne naprave	
	var top_cat = -1;
	var left_cat = -1;
	if (mobile == 0 || mobile == 2){
		top_cat = -6;
		top_cat_right = 30;
		left_cat = -6;
	}
	//*********************
	
	//$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).detach().css({top: -6,left: -6}).appendTo('#half_frame_dropping_'+spremenljivka)	//zeleni element s kategorijami dodaj v zacetnem kontejnerju
	$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).detach().css({top: top_cat,left: left_cat}).appendTo('#half_frame_dropping_'+spremenljivka)	//zeleni element s kategorijami dodaj v zacetnem kontejnerju
		.draggable({	//ureditev, da je element draggable
			cursor: 'move',
			//revert: true,
			helper: 'original',
			zIndex: 100,
			revert: function(socketObj)
			{
				checkBranching();
				var revert = false;
				var cat_default_height = 37;
				var cat_margin_left = 10 + 5*2 + 1*2;
				//var indeks_revert_1 = last_indeks[spremenljivka];
				var last_vre_id_revert = last_vre_id[spremenljivka];
				var trenutna_vre_id_revert = vre_id_global[spremenljivka];
				//console.log("trenutna_vre_id_revert: "+trenutna_vre_id_revert);
				//var indeks_revert = indeks_global[spremenljivka];
				var indeks_revert = last_drop[trenutna_vre_id_revert];
				vre_id = trenutna_vre_id_revert;
				//grd_id = last_indeks[spremenljivka];
				grd_id = indeks_revert;
				var prejsnji_okvir = $('#half2_frame_dropping_'+last_indeks[spremenljivka]+'_'+spremenljivka);
				var trenutni_okvir = $('#half2_frame_dropping_'+last_drop[trenutna_vre_id_revert]+'_'+spremenljivka);
				var draggable = draggable_global[trenutna_vre_id_revert];
				
				//if false then no socket object drop occurred.
				if(socketObj === false){					
					//revert the peg by returning true
					//console.log("Reverting!")
					revert = true;
					
					if(tip == 6){
						//ce odgovora ni v levem okvirju (ima indeks = 0),
						//ce se odgovor reverta nazaj v desni okvir
						if(indeks_revert != 0){
							//oznacimo, da je trenutna kategorija odgovora v desnem okvirju
							draggableOnDroppable[last_vre_id_revert][indeks_revert] = true;
							//draggableOverDroppable[vre_id] = false;
							draggableOverDroppable[vre_id][indeks_revert] = true;
							//console.log('draggableOverDroppable['+vre_id+']['+indeks_revert+']: '+draggableOverDroppable[vre_id][indeks_revert]);
							//postimajo visino 
							frame_and_question_height(trenutni_okvir, spremenljivka, num_grids_global[spremenljivka], cat_margin_left, cat_default_height, draggable);
							//console.log("Frame and question height iz reverta");
						}
						var prejsnji_okvir_kat_prisotna = [];
						var stevilo_prisotnih = prejsnji_okvir.children('div').length;
						//console.log(stevilo_prisotnih);
						//var prejsnji_okvir_kat_prisotna = prejsnji_okvir.children('div').attr('value'); //belezi, ce je kaj prisotnega v prejsnjem okvirju
						
						for(var z = 1; z <= stevilo_prisotnih; z++){
							prejsnji_okvir_kat_prisotna[z] = prejsnji_okvir.children('div :nth-child('+z+')').attr('value'); //belezi, ce je kaj prisotnega v prejsnjem okvirju, kateri odgovori so prisotni
							//console.log("prejsnji_okvir_kat_prisotna["+z+"]: "+prejsnji_okvir_kat_prisotna[z]);
							//}
							//console.log("Tukaj!");
							//ce zadnje obiskani okvir je bil eden od desnih in smo startali iz levega
							if(last_indeks[spremenljivka] != 0 && indeks_revert == 0){
								//spremeni velikost zadnje obiskanega desnega okvirja
								//console.log("Tukaj!");
								if(prejsnji_okvir_kat_prisotna[z] === undefined){
									last_frame_height(prejsnji_okvir, spremenljivka, num_grids_global[spremenljivka], cat_margin_left, draggable_global[trenutna_vre_id_revert]);
								}
								last_indeks[spremenljivka] = 0;
							}
							//ce prejsnji okvir ni trenutni in ne revert-amo nazaj v levi okvir
							if ( (last_indeks[spremenljivka] != last_drop[trenutna_vre_id_revert]) && (indeks_revert != 0) ){
								//uredi velikost okvirja
								frame_height(spremenljivka, vre_id, grd_id, revert);
								
								//ce ni kategorije z odgovorom v okvirju
								if(prejsnji_okvir_kat_prisotna[z] === undefined){
									//uredi velikost zadnje obiskanega okvirja							
									last_frame_height(prejsnji_okvir, spremenljivka, num_grids_global[spremenljivka], cat_margin_left, draggable_global[trenutna_vre_id_revert]);
								}
								
								//uredi velikost celotnega vprasanja
								//console.log("Uredi velikost celotnega vprašanja");
								//*******************dinamicna visina celotnega vprasanja glede na vsebino prenesenih desnih okvirjev
								//var default_var_height = $('#spremenljivka_'+spremenljivka).height();
								var default_var_height = 1;
								dynamic_question_height(spremenljivka, num_grids_global[spremenljivka], default_var_height);	
								//************************************ konec - dinamicna visina celotnega vprasanja glede na visino prenesenih desnih okvirjev

							}
						}
					}
					return true;
				}
				else{
					//socket object was returned,
					//we can perform additional checks here if we like
					//alert(socketObj.attr('id')); would work fine
					//console.log(socketObj.attr('id'));
					//return false so that the peg does not revert
					//console.log("Success!");
					revert = false;
					return false;
				}
			},
			stack: '#half2_'+spremenljivka+' div',
			opacity: 0.9,
			containment: '#spremenljivka_'+spremenljivka,
		});
			

		//dodajanje atributov, ki so prisotni pri vseh ostalih kategorijah odgovorov
		//$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).attr({'value':vre_id, 'name':'vrednost_'+spremenljivka, 'onclick':'checkBranching();', 'missing':other});
		$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).attr({'reverted':false,'value':vre_id, 'name':'vrednost_'+spremenljivka, 'onclick':'checkBranching();', 'missing':other});
		//$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).attr({'reverted':false,'value':vre_id, 'name':'vrednost_'+spremenljivka, 'onclick':'checkBranching(); checkPosition('+tip+','+spremenljivka+','+ vre_id+','+ usr_id+','+anketa+','+site_url+');', 'missing':other});
		//$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).attr({'reverted':false,'value':vre_id, 'name':'vrednost_'+spremenljivka, 'onclick':'checkBranching(); checkPosition('+tip+','+spremenljivka+','+ vre_id+','+ usr_id+','+anketa+');', 'missing':other});
		
		
		//ce je tabela vec odgovorov, rabimo clone
		if(tip == 16){
				$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).draggable( "option", "helper", "clone" );
		}
			
		//ureditev visine kategorije (div) glede na prisotnost slike ali vecvrsticnega teksta
		var default_cat_height = 15;
		var final_height = 0;
		//var cat_text_length = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).text().length;	//hrani stevilo znakov, ki so vpisani v trenutni kategoriji odgovora
		var cat_text_length = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).html().length;	//hrani stevilo znakov, ki so vpisani v trenutni kategoriji odgovora
		//console.log('Število znakov v kategoriji: '+cat_text_length);
		
		var num_of_br = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id+' br').length;	//hrani stevilo br oz. ročnih vnosov novih vrstic
		//console.log('Število br v kategoriji: '+num_of_br);
		
		var num_imgs = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id+' img').length; //hrani stevilo img v interesiranem div-u
		//console.log('Število slik v kategoriji @grids: '+num_imgs);
		
		var max_cat_text_length = 30; //hrani max stevilo dolzine teksta do katerega ni potrebno samodejno dodati <br>
		
		
		if( (cat_text_length >  max_cat_text_length) && (num_of_br == 0) && (num_imgs == 0) ){//ce je tekst daljsi od 30 znakov, nima breakov ali slik dodaj <br>
		//if( (cat_text_length >  max_cat_text_length) ){//ce je tekst daljsi od 35 znakov dodaj <br>
			//console.log('Tekst je daljši od '+max_cat_text_length+' znakov');
			//var txt2 = txt1.slice(0, 3) + "bar" + txt1.slice(3);
			//var txt = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).text();
			var txt = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).html();
			//console.log(txt);
			var txt_alt = txt.slice(0, max_cat_text_length) + "<br>" + txt.slice(max_cat_text_length);
			//console.log(txt_alt);
			$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).html(txt_alt);
			final_height = final_height + default_cat_height + 25;
			//console.log(final_height);
		}
		/* 	else if( (cat_text_length >  max_cat_text_length) && (num_of_br == 0) && (num_imgs != 0) ){//ce je tekst daljsi od 35 znakov, nima breakov, ima pa sliko dodaj <br>
			
			var txt = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).html();
			console.log(txt);
			var n_img_start = txt.search("<img"); //var n = str.search("W3Schools");	//hrani index, kjer se začne html za sliko
			var n_img_end = txt.indexOf(">");
			console.log(n_img_start);
			console.log(n_img_end);
			//var txt_alt = txt.slice(0, max_cat_text_length) + "<br>" + txt.slice(max_cat_text_length);
			//console.log(txt_alt);
			//$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).html(txt_alt);
			//final_height = final_height + default_cat_height + 25;
			//console.log(final_height);
			
		} */
		

		if (num_imgs != 0){	// ce imamo sliko
			
			var img_height = 0;
			//var max_width = $('.ranking').width();
			var max_width = 230;
			var img = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id+' img');
			var img_width = img.width();
			//console.log("img_width: "+img_width);
			
			var img_height = img.height();
			//console.log("img_height: "+img_height);
			
			if (img_width > max_width){
				img_height = (img_height / img_width) * max_width;
				img.css({width: max_width});
				img.css({height: img_height});
				//$('#vre_id_'+vre_id).css({height: height});
				//console.log("Vecji od max width");
			}
			
			//ureditev mobilne različice prikazovanja slik znotraj kategorij odgovorov
/* 			if (mobile == 1){	//ce je mobilnik				
				img_height = (img_height / img_width) * 100;
				img_width = 100;
				$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id+' img').attr('style', 'margin: auto !important');	//dodaj atribut
				$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id+' img').css({'height':img_height,'width':img_width});	//ustrezno spremeni visino in sirino slike
			} */			
			//ureditev mobilne različice prikazovanja - konec
				
			//console.log("img_height: "+img_height);
			if(img_height > 25){	//ce je visina slike vecja od default visine kategorije
				final_height = final_height + img_height;		
			}	



			//ureditev visine variable_holder, ki je znotraj okvirja !!!!!!!!!!
			//$('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').css({'height':(final_height + img_height)+'px'});
		}
		if(num_of_br != 0){
			var br_height = num_of_br*25;
			
			if (num_imgs == 0){
				final_height = final_height + default_cat_height + br_height;
			}
			else{
				final_height = final_height + br_height;
			}
			
			
			//console.log(final_height);
			if( (img_height < 25) && (img_height != 0) ){			
				final_height = final_height + img_height;
			}
		}
		if (final_height != 0){
			$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).css({'height':final_height});	//dodaj style atributu še novo višino za levi blok
		}

}
//***********************************************************************
//var cat_total_height_left = 0; //hrani trenutno visino levega okvirja kategorij odgovorov
//*********funkcija, ki skrbi za delovanje drag and drop grid funkcionalnosti
function GridDragDropDelovanjeBox(num_grids, indeks, tip, spremenljivka, site_url, ajax, anketa, usr_id, num_of_cats, mobile){
	//var default_var_height_1 = [];
	
	//*****za mobilne naprave	
	var top_cat = -1;
	var left_cat = -1;
	var default_var_height = 290;	//default visina celotnega vprasanja
	if (mobile == 0 || mobile == 2){
		top_cat = -6;
		top_cat_right = 30;
		left_cat = -6;
		var default_var_height = 220;	//default visina celotnega vprasanja
	}
	//*********************
	//ureditev visine celotnega vprasanja, ce je ta visja od default-a*******************************************************************
	//var default_var_height = 287;	//default visina celotnega vprasanja
	//var default_var_height = 220;	//default visina celotnega vprasanja
 	//var cat_total_height = 0; //hrani trenutno visino celotnega trenutnega vprasanja
	//var cat_total_height_left = 0; //hrani trenutno visino levega okvirja kategorij odgovorov
	var cat_margin_left = 10 + 5*2 + 1*2; //hrani rob za ureditev visine levega okvirja = margin_spodnji + padding(spredi pa zadi) + border(spredi pa zadi) + neznanka
	var cat_max_height = 0; 	//hrani trenutno najvecjo visino tretnutne kategorije	
	var grid_title_height = 15 + 5*2 + 1*2;	//hrani default visino naslovov gridov oz. okvirjev
	//var cat_default_height = 2 + 10 + 15 + 10; //hrani default visino kategorije odgovora = border + padding + notranja visina + spodnji margin
	var cat_default_height = 37;
	var title_heigth = 26;	//visina okvricka z naslovom
	var naslov_height = $('#spremenljivka_'+spremenljivka+' .naslov').height();	//visina naslova vprasanja (besedila vprasanja)	
	var margin_now = 10;	//rob po vsakem okvirju
	var cat_default_inline_text_length = 16;	//default dolzina teksta v eni vrstici kategorij odgovorov
	//console.log("naslov_height: "+naslov_height);
	
	//ureditev visine puscice med blokoma okvirjev
	//var top = $('#spremenljivka_'+spremenljivka+' td.middle img').css('top');
	var variable_holder_height = $('#spremenljivka_'+spremenljivka+' .variable_holder').height();	//visina variable_holder, potrebna za visino puscice med blokoma
	//$('#spremenljivka_'+spremenljivka+' td.middle img').css({'top' : (naslov_height + 200) + 'px'});
	$('#spremenljivka_'+spremenljivka+' td.middle img').css({'top' : (naslov_height + variable_holder_height/2) + 'px'});	//visina puscice med blokoma okvirjev
	
	if (ajax){
		//console.log('Getting data on load');
		$.get(site_url+'/main/survey/ajax.php?a=get_dragdrop1_data', {spremenljivka: spremenljivka, anketa: anketa}, function(data){ //get potrebnih podatkov za resevanje missing
 		//trenutna visina celotnega vprasanja
		var default_var_height = $('#spremenljivka_'+spremenljivka).height();
		//console.log("default_var_height: "+default_var_height + " indeks: "+indeks+" num of grids: "+num_grids);
		
			if(indeks == 1){//samo enkrat pojdi skozi leve kategorije odgovorov
			//if(indeks == num_grids || indeks == num_grids-1 ){
/* 				//trenutna visina celotnega vprasanja@indeks = 1
				var default_var_height = $('#spremenljivka_'+spremenljivka).height());
				console.log("default_var_height @ indeks = 1: "+default_var_height); */
				var cat_total_height_left = 0; //hrani trenutno visino levega okvirja kategorij odgovorov
				var array_length = data.length;	//hrani koliko podatkov je prisotnih v polju s podatki iz baze
				var vre_id = [];	//polje, ki hrani id-je vrednosti vseh kategorij odgovorov interesirane spremenljivke
				//var cat_total_height = 0; //hrani trenutno visino celotnega trenutnega vprasanja
				var cat_margin = 30;
				
				//console.log(array_length);	//prikazi koliko podatkov je prisotnih v polju s podatki iz baze
				for(var i = 0; i < array_length; i++){	//sprehodi se po vseh vrednostih polja vre_id v levem bloku
					vre_id[i] = data[i];	//polje iz podatkov iz baze data[], shrani v polje vre_id[]
					
					//var cat_height_px = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id[i]).css('height');	//visina kategorije z oznako px
					//var cat_height = cat_height_px.replace('px','');	//hrani string z visino, kjer odstranimo "px"
					var cat_height = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id[i]).height();	//visina kategorije
					cat_height = parseInt(cat_height);	//sprememba stringa s samo visino brez oznake "px" v stevilo
					
					//console.log('Visina '+(i + 1)+': '+cat_height+' + '+cat_margin_left);
					//cat_total_height = cat_total_height + cat_height + cat_margin;	//izracun koncne visine vprasanja
					
					if(mobile == 1){	//ko je mobilnik, uredi velikost okvirja kategorije glede na dolzino besedila
						var cat_text_length = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id[i]).text().length;	//dolzina teksta v kategoriji odgovora
						
						if(cat_text_length > (cat_default_inline_text_length)*2 ){	//ce je dolzina teksta v kategoriji daljsa 2-krat vec od default (16)
							//console.log("Tekst je daljši!");
							//console.log("Dolžina besedila v kategoriji: "+cat_text_length);
							var num_of_rows = cat_text_length / cat_default_inline_text_length;
							//console.log("num_of_rows: "+num_of_rows);
							cat_height = cat_height + 25 * (num_of_rows - 1);	//trenutno visino kategorije povecaj za 25
							//console.log("num_of_rows final: "+ (num_of_rows - 1));
							//console.log("cat_height: "+cat_height);
							$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id[i]).css({'height':(cat_height)+'px'});	//spremeni visino kategorije
						}
					}
					
					cat_total_height_left = cat_total_height_left + cat_height + cat_margin_left;	//izracun koncne visine levega okvirja
					
					//console.log('Total left: '+cat_total_height_left);
					
					
					if (cat_max_height <  cat_height){	//ce je maksimalna visina kategorije manjsa od trenutne visine kategorije
						cat_max_height = cat_height;	//naj bo vrednost max visine kategorije trenutna visina kategorije
					}				


				}
				//console.log('Total left: '+cat_total_height_left);
				
				//*************urejanje visine levega okvirja kategorij odgovorov
				$('#half_frame_dropping_'+spremenljivka).css({'height':(cat_total_height_left)+'px'});//visina levega okvirja
			}
			

			
			if(indeks == 1){	//samo enkrat pojdi skozi desne okvirje
			//if(indeks == num_grids-1 ){
				for(var j = 1; j <= num_grids; j++){	//preglej vse desne okvirje v desnem bloku
					//notranja visina trenutnega okvirja***************************************
					//var okvir_height = $('#half2_frame_dropping_'+j+'_'+spremenljivka).css('height');
					//okvir_height = parseInt(okvir_height.replace('px',''));
					var okvir_height = $('#half2_frame_dropping_'+j+'_'+spremenljivka).height();
					
					okvir_height = okvir_height + 10 + 2; //notranja visina + padding + border
					//console.log('Visina desnega okvirja'+j+' : '+okvir_height);
					frame_total_height_right[spremenljivka] = frame_total_height_right[spremenljivka] + okvir_height + margin_now + title_heigth;
					//console.log('Koncna visina desnega bloka: '+frame_total_height_right[spremenljivka]);
				}				
				//console.log("Desni blok visok ob indeksu 1: "+frame_total_height_right[spremenljivka]);				
			}
			
			
			default_var_height_1[spremenljivka] = $('#spremenljivka_'+spremenljivka).height(); //belezenje celotne zacetne visine spremenljivke
			//default_var_height_1[spremenljivka] = $('#spremenljivka_'+spremenljivka).css('height');

			 if(cat_total_height_left > frame_total_height_right[spremenljivka]){ //ce je trenutna visina levega okvirja z odgovori vecja od koncne visine desnega okvirja
				//console.log("Levi okvir vecji od desnega");
				//console.log("Levi visok: "+cat_total_height_left);
				//console.log('Zacetna visina celotne spremenljivke: '+default_var_height_1[spremenljivka]);
				//ce je koncna visina levega okvirja vecja od default visine celotnega vprasanja
				//if( (cat_total_height_left > default_var_height) && (data_after_refresh[spremenljivka] == false) ){
				if( (cat_total_height_left > default_var_height) ){ //ce je koncna visina levega okvirja z odgovori vecja od visine celotnega vprasanja
					dynamic_question_height_sub(cat_total_height_left, spremenljivka, default_var_height_1[spremenljivka]); //ustrezno spremeni vesino celotnega vprasanja
				}else if(cat_total_height_left < default_var_height_1[spremenljivka]){	//ce je koncna visina levega okvirja z odgovori manjsi od visine celotnega vprasanja
					//console.log("default_var_height_1: "+default_var_height_1[spremenljivka]);
					dynamic_question_height_sub(cat_total_height_left, spremenljivka, default_var_height_1[spremenljivka]); //ustrezno spremeni vesino celotnega vprasanja
				} else{
					//console.log("Tale druga pot");
					dynamic_question_height_sub(cat_total_height_left, spremenljivka, default_var_height_1[spremenljivka]);//ustrezno spremeni vesino celotnega vprasanja
				}
				//default_var_height_1[spremenljivka] = $('#spremenljivka_'+spremenljivka).css('height');	//belezenje celotne zacetne visine spremenljivke
				//console.log('Zacetna visina celotne spremenljivke: '+default_var_height_1[spremenljivka]);


				//data_after_refresh[spremenljivka] == 0;
				//console.log(cat_total_height_left);
			//ce je trenutna visina levega okvirja z odgovori manjsa od koncne visine desnega okvirja
			}else if(cat_total_height_left < frame_total_height_right[spremenljivka]){
				//console.log("Levi okvir manjsi od desnega");
				$('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').height(frame_total_height_right[spremenljivka] + 50);
				frame_total_height_right[spremenljivka] = frame_total_height_right[spremenljivka] + naslov_height;
				//console.log("Desni blok visok ob indeksu 1 povečan za naslov: "+frame_total_height_right[spremenljivka]);
				//frame_total_height_right[spremenljivka] = frame_total_height_right[spremenljivka] + naslov_height;
				$('#spremenljivka_'+spremenljivka).height(frame_total_height_right[spremenljivka] + 50);				
				//dynamic_question_height_sub(frame_total_height_right[spremenljivka], spremenljivka);			
			}

		}, "json");
	}
	
	//default_var_height_1[spremenljivka] = $('#spremenljivka_'+spremenljivka).css('height');	//belezenje celotne zacetne visine spremenljivke	
	
	//************************ konec - ureditev visine celotnega vprasanja, ce je ta visja od default-a
	
	//console.log("Grid delovanje: "+num_grids);
	
	$('#half_frame_dropping_'+spremenljivka)
		.droppable({
			hoverClass: 'frame_dropping_hover',
			drop: function (event, ui) {
				checkBranching();
				
				//$(ui.draggable).detach().css({top: -6,left: -6}).appendTo(this);
				$(ui.draggable).detach().css({top: top_cat,left: left_cat}).appendTo(this);
				var vre_id = ui.draggable.attr('value');
				
				if (ajax && tip == 6){
					$.post(site_url+'/main/survey/ajax.php?a=delete_dragdrop_grid_data_1', {spremenljivka: spremenljivka, vre_id: vre_id, usr_id: usr_id, anketa: anketa, indeks: last_indeks[spremenljivka]}); //post-aj potrebne podatke za brisanje
				//}
					draggableOnDroppable[vre_id][last_indeks[spremenljivka]] = false;	//oznacimo, da smo trenutno kategorijo odgovora odstranili iz okvirja
					draggableOverDroppable[vre_id][last_indeks[spremenljivka]] = false;
					//console.log('draggableOverDroppable['+vre_id+']['+last_indeks[spremenljivka]+']: '+draggableOverDroppable[vre_id][last_indeks[spremenljivka]])
					var prejsnji_okvir = $('#half2_frame_dropping_'+last_indeks[spremenljivka]+'_'+spremenljivka);				
					var prejsnji_okvir_kat_prisotna = prejsnji_okvir.children('div').attr('value'); //belezi, ce je kaj prisotnega v prejsnjem okvirju
					if(prejsnji_okvir_kat_prisotna === undefined && last_indeks[spremenljivka] != 0){
						//console.log("Levi frame last frame func");
						last_frame_height(prejsnji_okvir, spremenljivka, num_grids, cat_margin_left, ui.draggable);	//spremeni visino zadnje obiskanega okvirja
					}
					dynamic_question_height(spremenljivka, num_grids);
					last_indeks[spremenljivka] = 0;
					last_drop[vre_id] = 0;
					last_vre_id[spremenljivka] = 0;
					
				}
				from_left[vre_id] = true;
/* 				var default_var_height = $('#spremenljivka_'+spremenljivka).height();
				default_var_height_1[spremenljivka] = default_var_height; */
			},
			out: function (event, ui) {	//ob izhodu iz drop zone
				//answer_coming[spremenljivka] = true;
				//var vre_id = ui.draggable.attr('value');
				//console.log("from_left["+vre_id+"]: "+from_left[vre_id]);
			},
			over: function (event, ui) {
				var vre_id = ui.draggable.attr('value');
				//potrebno pridobiti informacijo ze tukaj, ker drugace so tezave @ revert v levi okvir
				vre_id_global[spremenljivka] = vre_id;
				draggable_global[vre_id] = ui.draggable;
			}
		});

	
	$('#half2_frame_dropping_'+indeks+'_'+spremenljivka)
		.droppable({
			//hoverClass: 'frame_ranking_hover',
			hoverClass: 'frame_dropping_hover',
			tolerance: "pointer",
			//accept: '#half_'+spremenljivka+' div',
			drop: function (event, ui) {	//ob dropanju odgovora v desni blok
				checkBranching();
				
				if(mobile == 0 || mobile == 2){
					//$(this).toggleClass('frame_dropping_wider'); //spremeni videz trenutnega okvirja
				}else if(mobile == 1){
					//$(this).toggleClass('frame_dropping_wider_mobile'); //spremeni videz trenutnega okvirja
				}
				
				
				num_grids = num_grids_global[spremenljivka];
				//console.log("Drop");
				var vre_id = ui.draggable.attr('value');
				var other = ui.draggable.attr('missing');	//spremenljivka, ki hrani vrednost atributa missing
				var other_present = $(this).children('div').attr('missing');	//missing, ki je trenutno v desnem bloku
				//var cat_right = $(this).children('div').css('height');	//ali je prisotna kaksna kategorija v trenuntem desnem okvirju? Undefined = ne
				var cat_right = $(this).children('div').height();	//ali je prisotna kaksna kategorija v trenuntem desnem okvirju? Undefined = ne
				//var cat_right = $(this).children('div').outerHeight();	//ali je prisotna kaksna kategorija v trenuntem desnem okvirju? Undefined = ne
				var vre_id_present = $(this).children('div').attr('value'); //vre_id kategorije odgovora, ki je prisotna v okvirju ob dropu
				//var draggable_global[spremenljivka] = ui.draggable;
				draggable_global[vre_id] = ui.draggable;
				
 				//*******************dinamicna visina celotnega vprasanja glede na vsebino prenesenih desnih okvirjev
				var title_heigth = 26;	//visina okvricka z naslovom
				var margin_now = 10;	//rob po vsakem okvirju
				var height_beside = 40; //visina od zacetka vprasanja do prvega okvirja (in malo po zadnjem okvirju)
				var final_height_right_block = 0;	//hrani koncno visino desnega bloka, torej vseh prisotnih okvirjev
				final_height_right_block = final_height_right_block + height_beside; //koncni visini dodamo se "praznino" med zacetkom vprasanja in prvim okvirjem
				
				for(var j = 1; j <= num_grids; j++){	//preglej vse okvirje
					//notranja visina trenutnega okvirja***************************************
					//var okvir_height = $('#half2_frame_dropping_'+j+'_'+spremenljivka).css('height');					
					//okvir_height = parseInt(okvir_height.replace('px',''));
					var okvir_height = $('#half2_frame_dropping_'+j+'_'+spremenljivka).height();					
					//console.log('Visina '+j+' : '+okvir_height);
					okvir_height = okvir_height + 10 + 2; //notranja visina + padding + border
					//*************************************************************************
					final_height_right_block = final_height_right_block + okvir_height + margin_now + title_heigth;
					//console.log('Koncna visina desnega bloka: '+final_height_right_block);
				}
				
				//trenutna visina celotnega vprasanja
				//var default_var_height = $('#spremenljivka_'+spremenljivka).css('height');
				//default_var_height = parseInt(default_var_height.replace('px',''));
				var default_var_height = $('#spremenljivka_'+spremenljivka).height();
				//console.log('Default: '+default_var_height);
				//console.log('Final: '+final_height_right_block);
				if(final_height_right_block > default_var_height){
					$('#spremenljivka_'+spremenljivka).css({'height':final_height_right_block+'px'});
					//da ne bo pri mobilnikih prevec skrito vprasanje
					$('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').css({'height':(final_height_right_block - 100)+'px'});
					
				}
				//************************************ konec - dinamicna visina celotnega vprasanja glede na visino prenesenih desnih okvirjev
				
				
				//$(ui.draggable).detach().css({top: -6,left: -6}).appendTo(this);	//pozicioniraj kategorijo odgovora na pravo mesto
				//$(ui.draggable).detach().css({top: top_cat,left: left_cat}).appendTo(this);	//pozicioniraj kategorijo odgovora na pravo mesto
				
				//ce je tabela - en odgovor
				if(tip == 6){
					//$(ui.draggable).detach().css({top: top_cat,left: left_cat}).appendTo(this);	//pozicioniraj kategorijo odgovora na pravo mesto
					//$(ui.draggable).detach().css({top: 30, left:-6}).appendTo(this);	//pozicioniraj kategorijo odgovora na pravo mesto
					if (cat_right){	//ce je ze nekaj v okvirju
						//$(ui.draggable).detach().css({top: (top_cat_right + cat_right), left:-6}).appendTo(this);	//pozicioniraj kategorijo odgovora na pravo mesto tako, da je nad prejsnjim
						$(ui.draggable).detach().css({top: (top_cat_right), left:-6}).prependTo(this);	//pozicioniraj kategorijo odgovora na pravo mesto tako, da je nad prejsnjim
					}else{
						$(ui.draggable).detach().css({top: top_cat_right, left:-6}).appendTo(this);	//pozicioniraj kategorijo odgovora na pravo mesto	
					}
					
					
/* 					if(cat_right == null){
						$(ui.draggable).detach().css({top: ui.draggable.height(),left: left_cat}).appendTo(this);	//pozicioniraj kategorijo odgovora na pravo mesto
					}
					$(ui.draggable).css({margin: '10px auto 0px auto'}); */
					if (ajax){
						$.post(site_url+'/main/survey/ajax.php?a=accept_dragdrop_grid', {vre_id_present: vre_id_present, tip: tip, spremenljivka: spremenljivka, vre_id: vre_id, anketa: anketa, usr_id: usr_id, indeks: indeks, cat_right: cat_right, last_vre_id: last_vre_id[spremenljivka]}); //post-aj potrebne podatke za belezenje v bazo
						if(last_drop[vre_id] != indeks || last_drop[vre_id] != 0){
							$.post(site_url+'/main/survey/ajax.php?a=delete_dragdrop_grid_data_1', {spremenljivka: spremenljivka, vre_id: vre_id, usr_id: usr_id, anketa: anketa, indeks: last_drop[vre_id]}); //post-aj potrebne podatke za brisanje
						}
					}
				}
				else if(tip == 16 && draggableOnDroppable[vre_id][indeks] == false){	//ce je tabela - vec odgovorov in odgovora ni v trenutnem okvirju, uredi clone
					var visina_test = ui.draggable.css('height');
					//$(ui.draggable.clone()).detach().css({top: top_cat,left: left_cat, height: visina_test}).appendTo(this);	//pozicioniraj kategorijo odgovora na pravo mesto in dodaj ustrezno visino
					$(ui.draggable.clone()).detach().css({top: top_cat_right, left: left_cat, height: visina_test}).prependTo(this);	//pozicioniraj kategorijo odgovora na pravo mesto in dodaj ustrezno visino
					//$(ui.draggable).detach().css({top: 10,left: 10}).appendTo(this);	//pozicioniraj kategorijo odgovora na pravo mesto
					if (ajax){
						$.post(site_url+'/main/survey/ajax.php?a=accept_dragdrop_grid', {vre_id_present: vre_id_present, tip: tip, spremenljivka: spremenljivka, vre_id: vre_id, anketa: anketa, usr_id: usr_id, indeks: indeks, cat_right: cat_right, last_vre_id: last_vre_id[spremenljivka]}); //post-aj potrebne podatke za belezenje v bazo
					}
				}				
			
				if(last_indeks[spremenljivka] !== undefined){
					var prejsnji_okvir = $('#half2_frame_dropping_'+last_indeks[spremenljivka]+'_'+spremenljivka);
					var prejsnji_okvir_kat_prisotna = prejsnji_okvir.children('div').attr('value'); //belezi, ce je kaj prisotnega v prejsnjem okvirju
					//console.log("last_indeks[spremenljivka][indeks]: "+last_indeks[spremenljivka][indeks]);
					if(last_indeks[spremenljivka] == 0 || prejsnji_okvir_kat_prisotna === undefined){
						last_frame_height(prejsnji_okvir, spremenljivka, num_grids, cat_margin_left, ui.draggable); //spremeni visino zadnje obiskanega okvirja
					}
				}
				
				last_drop[vre_id] = indeks;	//zabelezi indeks okvirja zadnjega drop-a
				draggableOnDroppable[vre_id][indeks] = true;	//oznacimo, da je trenutna kategorija odgovora v okvirju
				if(tip == 6){
					from_left[vre_id] = false;
				}
			},
			over: function (event, ui) {	//ob prenosu trenutne kategorije odgovora nad okvirjem
				num_grids = num_grids_global[spremenljivka];
				checkBranching();
				var vre_id = ui.draggable.attr('value');
				var vre_id_present = $(this).children('div').attr('value'); //vre_id kategorije odgovora, ki je prisotna v okvirju ob dropu
				vre_id_global[spremenljivka] = vre_id;
				var trenutni_okvir = $('#half2_frame_dropping_'+indeks+'_'+spremenljivka);
				//console.log("from_left["+vre_id+"]: "+from_left[vre_id]);
				
				if (last_indeks[spremenljivka] == 0 && tip == 6){	//ce prenasamo kategorije odgovora iz levega okvirja
					last_indeks[spremenljivka] = indeks;	//zabelezi indeks prejsnjega okvirja
	
				}else {
	
					var last_vrednost_id_temp = last_vre_id[spremenljivka];
					var prejsnji_okvir = $('#half2_frame_dropping_'+last_indeks[spremenljivka]+'_'+spremenljivka);

					//if(tip == 16 || tip == 6){
					if(tip == 6){
						var prejsnji_okvir_kat_prisotna = [];
						var stevilo_prisotnih = prejsnji_okvir.children('div').length;					
						//console.log(stevilo_prisotnih);
						for(var z = 1; z <= stevilo_prisotnih; z++){
							//prejsnji_okvir_kat_prisotna = prejsnji_okvir.children('div').attr('value'); //belezi, ce je kaj prisotnega v prejsnjem okvirju
							prejsnji_okvir_kat_prisotna[z] = prejsnji_okvir.children('div :nth-child('+z+')').attr('value'); //belezi, ce je kaj prisotnega v prejsnjem okvirju, kateri odgovori so prisotni
							//console.log("prejsnji_okvir_kat_prisotna["+z+"]: "+prejsnji_okvir_kat_prisotna[z]);	
							
							if(last_vrednost_id_temp != 0){
								//ce v prejsnjem okvirju ni nicesar in (v prejsnjem okvirju ni nicesar ali je identifikacija ista trenutni)
								if( (draggableOnDroppable[last_vrednost_id_temp][indeks] == false) && ( (prejsnji_okvir_kat_prisotna === undefined) || (prejsnji_okvir_kat_prisotna == vre_id) ) ){
									if ( (tip == 16 && draggableOnDroppable[vre_id][last_indeks[spremenljivka]] == false) || tip == 6 ){
									//spremeni visino prejsnjega okvirja
									last_frame_height(prejsnji_okvir, spremenljivka, num_grids, cat_margin_left, ui.draggable);
									//console.log("Spreminjam prejsnji okvir");
									}
								}
							}
						}
					}
				}
				

				if(vre_id_present !== undefined){
					//ce ni se nicesar v trenutnem okvirju, ali odgovor je na poti v okvir in je vprasanje tipa 16 (tabela - vec odgovorov) ali 6 (tabela - en odgovor)
					if (draggableOnDroppable[vre_id_present][indeks] != true || (draggableOverDroppable[vre_id][indeks] == false && (tip == 16 || tip == 6) ) ){
						//zabelezi, da je odgovor nad okvirjem
						draggableOverDroppable[vre_id][indeks] = true;
						if ((tip == 16 && draggableOnDroppable[vre_id][indeks] == false) || tip == 6){
						//uredi visino okvirja in celotnega vprasanja
						frame_and_question_height(trenutni_okvir, spremenljivka, num_grids, cat_margin_left, cat_default_height, ui.draggable);
						if(from_left[vre_id] != false){
							dynamic_question_height(spremenljivka, num_grids);
						}
						
						//console.log("Tukaj 1");
						}
					}
				}else{					

					if (vre_id_present === undefined || (draggableOverDroppable[vre_id][indeks] == false && (tip == 16 || tip == 6) ) ){
						//zabelezi, da je odgovor nad okvirjem
						draggableOverDroppable[vre_id][indeks] = true;
						if ( (tip == 16 && (draggableOnDroppable[vre_id][indeks] == false || last_drop[vre_id] == 0 )) || tip == 6){
						//uredi visino okvirja in celotnega vprasanja
						frame_and_question_height(trenutni_okvir, spremenljivka, num_grids, cat_margin_left, cat_default_height, ui.draggable);
						if(from_left[vre_id] != false){
							dynamic_question_height(spremenljivka, num_grids);
						}
						//console.log("Tukaj 2");
						}
					}
				}

				//spremeni videz trenutnega okvirja
				if(draggableOverDroppable[vre_id][indeks] == true){
					if(mobile == 0 || mobile == 2){
						//$(this).toggleClass('frame_dropping_wider');
					}
					else if(mobile == 1){
						//$(this).toggleClass('frame_dropping_wider_mobile');
					}
				}
				
				last_indeks[spremenljivka] = indeks;	//zabelezi indeks (trenutnega oz. bodocega) prejsnjega okvirja
				cat_pushed[spremenljivka] = false;		
			},
			out: function (event, ui) {	//ob izhodu iz drop zone
				num_grids = num_grids_global[spremenljivka];
				var vre_id = ui.draggable.attr('value');
				var vre_id_present = $(this).children('div').attr('value'); //vre_id kategorije odgovora, ki je prisotna v okvirju ob dropu
				var prejsnji_okvir = $('#half2_frame_dropping_'+last_indeks[spremenljivka]+'_'+spremenljivka);
				var prejsnji_okvir_kat_prisotna = prejsnji_okvir.children('div').attr('value'); //belezi, ce je kaj prisotnega v prejsnjem okvirju
				//console.log("Out");
				//draggableOnDroppable[vre_id] = false;	//oznacimo, da smo tretnutno kategorijo odgovora odstranili iz okvirja

				
				//if(draggableOverDroppable[vre_id] == true && tip == 16){
				if(draggableOverDroppable[vre_id][indeks] == true && ( (tip == 16 && draggableOnDroppable[vre_id][last_indeks[spremenljivka]] == false) || tip == 6) ){
					//console.log("Last frame out pri indeksu: "+last_indeks[spremenljivka]);
					last_frame_height(prejsnji_okvir, spremenljivka, num_grids, cat_margin_left, ui.draggable);
				}
				last_vre_id[spremenljivka] = vre_id;
				last_indeks[spremenljivka] = indeks;
				if( tip == 6 || (tip == 16 && draggableOnDroppable[vre_id][last_indeks[spremenljivka]] == false) ){
					draggableOnDroppable[vre_id][indeks] = false;	//oznacimo, da trenutne kategorije odgovora ni v okvirju
				}
				draggableOverDroppable[vre_id][indeks] = false;
				draggable_global[vre_id] = ui.draggable;
				
				//spremeni videz prejsnjega okvirja
				if(draggableOverDroppable[vre_id][indeks] == false){
					if(mobile == 0 || mobile == 2){
						//prejsnji_okvir.toggleClass('frame_dropping_wider');
					}else if(mobile == 1){
						//prejsnji_okvir.toggleClass('frame_dropping_wider_mobile');
					}
				}
				
			}
		});
	
	//********************** odstranitev odgovorov iz desnih okvirjev @ tabela - vec odgovorov
	// ce je tabela - vec odgovorov
	if(tip == 16){	//uredi odstranjevanje kategorij odgovorov iz desnega okvirja ob kliku na njih
	
		$('#half2_frame_dropping_'+indeks+'_'+spremenljivka).click(function(){
			
			$('#half2_frame_dropping_'+indeks+'_'+spremenljivka).children().click(function(){	//ob kliku na kategorijo odgovora
				
				var index1 = $('#half2_frame_dropping_'+indeks+'_'+spremenljivka).children().index(this);	//indeks klikanega odgovora
				var prejsnji_okvir = $('#half2_frame_dropping_'+indeks+'_'+spremenljivka);	//
				var vre_id = $('#half2_frame_dropping_'+indeks+'_'+spremenljivka+' div:nth-child('+(index1 + 1)+')').attr('value');
				var draggable = $('#half2_frame_dropping_'+indeks+'_'+spremenljivka+' div:nth-child('+(index1 + 1)+')');
				
				$('#half2_frame_dropping_'+indeks+'_'+spremenljivka+' div:nth-child('+(index1 + 1)+')').detach();//odstrani odgovor iz okvirja
				
				if (ajax){	//odstrani podatek o odgovoru iz baze
					$.post(site_url+'/main/survey/ajax.php?a=delete_dragdrop_grid_data', {tip: tip, spremenljivka: spremenljivka, vre_id: vre_id, usr_id: usr_id, anketa: anketa, indeks: indeks}); //post-aj potrebne podatke za brisanje			
				}
				console.log(draggableOnDroppable[vre_id][indeks]);
				last_frame_height(prejsnji_okvir, spremenljivka, num_grids, cat_margin_left, draggable); //spremeni visino zadnje obiskanega okvirja
				draggableOnDroppable[vre_id][indeks] = false;	//oznacimo, da smo trenutno kategorijo odgovora odstranili iz okvirja
				//console.log("draggableOnDroppable["+vre_id+"]["+indeks+"]: "+draggableOnDroppable[vre_id][indeks]);
				draggableOverDroppable[vre_id][indeks] = false;
				from_left[vre_id] = true;
				dynamic_question_height(spremenljivka, num_grids);
				//console.log("vre_id: "+vre_id);
			});
		
		});
		
	}
	//********************** konec - odstranitev odgovorov iz desnih okvirjev @ tabela - vec odgovorov
}
//**************************************************************************************

function frame_and_question_height(trenutni_okvir, spremenljivka, num_grids, cat_margin_left, cat_default_height, draggable){
	//console.log('Frame and question height');
	//uredi visino okvirja in celotnega vprasanja		
	var other = draggable.attr('missing');	//spremenljivka, ki hrani vrednost atributa missing
	var other_present = trenutni_okvir.children('div').attr('missing');	//missing, ki je trenutno v desnem bloku
	
	//***************** glede na visino trenutno prenesenih kategorij odgovora, povecaj visino okvirja
	//visina trenutno prenesenega odgovora*************
	//var cat_height_px_now = draggable.css('height');	//visina kategorije z oznako px
	//var cat_height_now = cat_height_px_now.replace('px','');	//hrani string z visino, kjer odstranimo "px"
	var cat_height_now = draggable.height();	//visina kategorije
	//cat_height_now = parseInt(cat_height_now);	//sprememba stringa s samo visino brez oznake "px" v stevilo
	//*************************************************visina trenutno prenesenega odgovora - konec
	
	//cat_height_now = cat_height_now + cat_margin_left;	//izracun koncne visine desnega okvirja
	//cat_height_now = cat_height_now + cat_default_height;	//izracun koncne visine desnega okvirja
	//console.log(cat_height_now);
	
	//var cat_right = trenutni_okvir.children('div').css('height');//belezi visino kategorije odgovora, ce je ta prisotna v trenutnem desnem okvirju
	var cat_right = trenutni_okvir.children('div').height();//belezi visino kategorije odgovora, ce je ta prisotna v trenutnem desnem okvirju
	
	//ce je v trenutnem desnem okvirju ze prisotna kategorija odgovora, trenutni visini okvirja dodaj se visino trenutne kategorije
	if(cat_right){
		//console.log('Ima children');
		//var whole_heigth = trenutni_okvir.css('height');
		//whole_heigth = whole_heigth.replace('px','');
		var whole_heigth = trenutni_okvir.height();
		//console.log(whole_heigth);
		cat_height_now = cat_height_now + cat_margin_left; //izracun koncne visine desnega okvirja, ce imamo ze kategorije v okvirju
		//var okvir_height = parseInt(whole_heigth) + parseInt(cat_height_now);
		var okvir_height = whole_heigth + cat_height_now;
		trenutni_okvir.css({'height':(okvir_height)+'px'});	//visina desnega okvirja
		//console.log('Koncna visina: '+(okvir_height));
	}else{	//drugace
		cat_height_now = cat_height_now + cat_default_height;	//izracun koncne visine desnega okvirja, ce ni kategorij v okvirju
		if (cat_height_now < 15){	//ce je visina trenutne kategorije odgovova manjsa od 15, prej 20
			cat_height_now = 15;	//naj bo visina okvirja 15px, prej 20px
		}
		trenutni_okvir.css({'height':(cat_height_now)+'px'});	//visina trenutnega desnega okvirja
		//console.log('Koncna visina: '+(cat_height_now));
	}
	//************************************ konec - glede na visino trenutno prenesenih kategorij odgovora, povecaj visino okvirja
	//*******************dinamicna visina celotnega vprasanja glede na vsebino prenesenih desnih okvirjev
	//trenutna visina celotnega vprasanja
	//var default_var_height = $('#spremenljivka_'+spremenljivka).height();
	//var default_var_height = 1;
	//dynamic_question_height(spremenljivka, num_grids, default_var_height);
	//************************************ konec - dinamicna visina celotnega vprasanja glede na visino prenesenih desnih okvirjev
}

//funkcija za urejanje visine okvirjev
function frame_height(spremenljivka, vre_id, grd_id, revert){
	
	//***************** glede na visino trenutno prenesenih kategorij odgovora, povecaj visino okvirja
	//visina trenutno prenesenega odgovora*************
	var cat_default_height = 37;
	//var cat_height_px_now = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).css('height');	//visina trenutne kategorije odgovora
	//var cat_height_now = cat_height_px_now.replace('px','');	//hrani string z visino, kjer odstranimo "px"
	var cat_height_now = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).height();	//visina trenutne kategorije odgovora
	//cat_height_now = parseInt(cat_height_now);	//sprememba stringa s samo visino brez oznake "px" v stevilo
	//*************************************************visina trenutno prenesenega odgovora - konec
	//console.log("Frame height");
	//console.log("cat_height_now: "+cat_height_now);
	//console.log("spremenljivka: "+spremenljivka);
	//console.log("vre_id: "+vre_id);
	
	//cat_height_now = cat_height_now + cat_margin_left;	//izracun koncne visine desnega okvirja
	//cat_height_now = cat_height_now + cat_default_height;	//izracun koncne visine desnega okvirja
	//console.log(cat_height_now);
	
	//var cat_right = $('#half2_frame_dropping_'+grd_id+'_'+spremenljivka).children('div').css('height');//belezi visino kategorije odgovora, ce je ta prisotna v trenutnem desnem okvirju
	var cat_right = $('#half2_frame_dropping_'+grd_id+'_'+spremenljivka).children('div').height();//belezi visino kategorije odgovora, ce je ta prisotna v trenutnem desnem okvirju
	
	//ce je v trenutnem desnem okvirju ze prisotna kategorija odgovora, trenutni visini okvirja dodaj se visino trenutne kategorije
	if(cat_right || revert){
		//console.log('Ima children');
		//var whole_heigth = $('#half2_frame_dropping_'+grd_id+'_'+spremenljivka).css('height');
		//whole_heigth = whole_heigth.replace('px','');
		var whole_heigth = $('#half2_frame_dropping_'+grd_id+'_'+spremenljivka).height();
		//console.log(whole_heigth);
		cat_height_now = cat_height_now + cat_margin_left; //izracun koncne visine desnega okvirja, ce imamo ze kategorije v okvirju
		var okvir_height = parseInt(whole_heigth) + parseInt(cat_height_now);
		$('#half2_frame_dropping_'+grd_id+'_'+spremenljivka).css({'height':(okvir_height)+'px'});	//visina desnega okvirja
		//console.log('Koncna visina: '+(okvir_height));
		
	}else{	//drugace
		cat_height_now = cat_height_now + cat_default_height;	//izracun koncne visine desnega okvirja, ce ni kategorij v okvirju
		if (cat_height_now < 15){	//ce je visina trenutne kategorije odgovova manjsa od 15, prej 20
			cat_height_now = 15;	//naj bo visina okvirja 15px, prej 20px
		}
		$('#half2_frame_dropping_'+grd_id+'_'+spremenljivka).css({'height':(cat_height_now)+'px'});	//visina trenutnega desnega okvirja
		//console.log('Koncna visina okvirja z odgovorom: '+(cat_height_now));
	}
	//************************************ konec - glede na visino trenutno prenesenih kategorij odgovora, povecaj visino okvirja	

	//console.log("cat_height_now ["+vre_id+"]: "+cat_height_now);
}
//************** konec - funkcije za urejanje visine okvirjev

//***************funkcija za urejanje visine zadnje obiskanega okvirja @ drag and drop
function last_frame_height(prejsnji_okvir, spremenljivka, num_grids, cat_margin_left, draggable){
	//console.log('last_frame_height: '+prejsnji_okvir.children('div').attr('value'));
	//console.log("num_grids v last frame: "+num_grids);
	//console.log('last_frame_height ');
	//****glede na odstrajeno kategorijo odgovora, trenutni visini okvirja odstrani visino odstranjene kategorije
	//var trenutna_visina_okvirja = $('#half2_frame_dropping_'+last_indeks[spremenljivka]+'_'+spremenljivka).css('height');
	//var trenutna_visina_okvirja = prejsnji_okvir.css('height');
	var trenutna_visina_okvirja = prejsnji_okvir.height();
	//console.log("trenutna_visina_okvirja: "+trenutna_visina_okvirja);
	//var trenutna_visina_kategorije = draggable.css('height')//visina odnesene kategorije odgovora
	var trenutna_visina_kategorije = draggable.height();//visina odnesene kategorije odgovora
	//trenutna_visina_okvirja = trenutna_visina_okvirja.replace('px','');
	//trenutna_visina_kategorije = trenutna_visina_kategorije.replace('px','');
	//koncna_visina_zapuscenega_okvirja = parseInt(trenutna_visina_okvirja) - parseInt(trenutna_visina_kategorije) - cat_margin_left;
	koncna_visina_zapuscenega_okvirja = trenutna_visina_okvirja - trenutna_visina_kategorije - cat_margin_left;
	
	if (koncna_visina_zapuscenega_okvirja < 15){
		koncna_visina_zapuscenega_okvirja = 15;
	}
	
	prejsnji_okvir.css({'height':(koncna_visina_zapuscenega_okvirja)+'px'});	//visina desnega okvirja
	
	
	//var default_var_height = default_var_height_1[spremenljivka];
	//console.log(default_var_height);
	//$('#spremenljivka_'+spremenljivka).css({'height':(default_var_height - koncna_visina_zapuscenega_okvirja + 50)+'px'});
	//****** konec - glede na odstrajeno kategorijo odgovora, trenutni visini okvirja odstrani visino odstranjene kategorije
	
	//*******************dinamicna visina celotnega vprasanja glede na vsebino prenesenih desnih okvirjev
	//var default_height_no_px = default_var_height_1[spremenljivka];
	//var default_height_no_px = 0;
	
	//dynamic_question_height(spremenljivka, num_grids);
	//dynamic_question_height(spremenljivka, num_grids, default_height_no_px);

	//************************************ konec - dinamicna visina celotnega vprasanja glede na visino prenesenih desnih okvirjev
}
//*************** konec - funkcija za urejanje visine zadnje obiskanega okvirja

//********* funkcija, ki skrbi za koncno ureditev visine celotnega vprasanja glede na visino (levega ali desnega) bloka @ drag and drop
//function dynamic_question_height_sub(frame_height, spremenljivka, default_height_no_px){
function dynamic_question_height_sub(frame_height, spremenljivka){
	//var default_height_no_px = default_var_height_1[spremenljivka];
	
	//console.log("default_height_no_px before: "+default_height_no_px);
	//default_var_height_1[spremenljivka] = $('#spremenljivka_'+spremenljivka).css('height');
	//default_height_no_px = parseInt(default_height_no_px.replace('px',''));
	
	default_height_no_px = $('#spremenljivka_'+spremenljivka).height();
	
	var naslov_height = $('#spremenljivka_'+spremenljivka+' .naslov').height();
 	//console.log("default_height_no_px dyn: "+default_height_no_px);
	//console.log("tallest frame_height: "+frame_height);
	//console.log("Dynamic question height sub");
	
	if( (frame_height + naslov_height) > default_height_no_px){	//ce je koncna visina desnega dela z okvirji vecji od trenutne default visine celotnega vprasanja
		$('#spremenljivka_'+spremenljivka).height(frame_height + naslov_height + 50);
		//console.log("Spreminjam 1");
		//da ne bo pri mobilnikih prevec skrito vprasanje, spremeni visino variable_holder
		$('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').height(frame_height + 25);
	}else if((frame_height + naslov_height) < default_height_no_px){	//ce je koncna visina desnega dela z okvirji manjsi od trenutne default visine celotnega vprasanja
		//$('#spremenljivka_'+spremenljivka).css({'height':(default_height_no_px + 50)+'px'});
		$('#spremenljivka_'+spremenljivka).height(default_height_no_px + 50);
		//console.log("Spreminjam 2");
		//da ne bo pri mobilnikih prevec skrito vprasanje, spremeni visino variable_holder
		$('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').height(default_height_no_px - naslov_height + 25);

	}	
}

//***********  @ drag and drop
//function dynamic_question_height(spremenljivka, num_grids, default_height_flag){
function dynamic_question_height(spremenljivka, num_grids){
	//*******************dinamicna visina celotnega vprasanja glede na vsebino prenesenih desnih okvirjev
	var naslov_height = $('#spremenljivka_'+spremenljivka+' .naslov').height();
	var title_heigth = 26;	//visina okvricka z naslovom
	var margin_now = 10;	//rob po vsakem okvirju
	var height_beside = 40; //visina od zacetka vprasanja do prvega okvirja (in malo po zadnjem okvirju)
	var final_height_right_block = 0;	//hrani koncno visino desnega bloka, torej vseh prisotnih okvirjev
	final_height_right_block = final_height_right_block + height_beside; //koncni visini dodamo se "praznino" med zacetkom vprasanja in prvim okvirjem
	
	//var frame_height_left = $('#half_frame_dropping_'+spremenljivka).css('height');	//visina celotnega levega okvirja
	//frame_height_left = parseInt(frame_height_left.replace('px',''));
	var frame_height_left = $('#half_frame_dropping_'+spremenljivka).height();	//visina celotnega levega okvirja
	frame_height_left= frame_height_left + 10 + 2; //notranja visina + padding + border
	frame_height_left = frame_height_left + height_beside;
	//console.log('Koncna visina levega bloka: '+frame_height_left);
	
	for(var j = 1; j <= num_grids; j++){	//preglej vse okvirje
		//notranja visina trenutnega okvirja***************************************
		//var okvir_height = $('#half2_frame_dropping_'+j+'_'+spremenljivka).css('height');
		var okvir_height = $('#half2_frame_dropping_'+j+'_'+spremenljivka).height();
		//okvir_height = parseInt(okvir_height.replace('px',''));			
		//console.log('Visina '+j+' : '+okvir_height);
		okvir_height = okvir_height + 10 + 2; //notranja visina + padding + border
		
		final_height_right_block = final_height_right_block + okvir_height + margin_now + title_heigth;
		//console.log('Koncna visina desnega bloka: '+final_height_right_block);
	}
	//console.log('Koncna visina desnega bloka: '+final_height_right_block);
	/* if (default_height_flag == 1){
		//trenutna visina celotnega vprasanja
		var default_var_height = $('#spremenljivka_'+spremenljivka).height();		
	}else if(default_height_flag == 0){
		var default_var_height = default_var_height_1[spremenljivka];
	} */
	
	
	var default_var_height_now = $('#spremenljivka_'+spremenljivka).height();
	//console.log("default_var_height: "+default_var_height_now);
	var default_var_height = $('#spremenljivka_'+spremenljivka).height();	
	
	//ce je visina okvirja (levi ali desni) z naslovom vprasanja vecja od visine celotnega vprasanja
	if( (default_var_height < (frame_height_left + naslov_height)) || (default_var_height < (final_height_right_block + naslov_height)) ){
		if(frame_height_left < final_height_right_block){
			//console.log("Levi manjsi od desnega");
			//dynamic_question_height_sub(final_height_right_block, spremenljivka, default_var_height);	//glede na visino desnega bloka uredi velikost celotnega vprasanja
			dynamic_question_height_sub(final_height_right_block, spremenljivka);	//glede na visino desnega bloka uredi velikost celotnega vprasanja
		}else if(frame_height_left > final_height_right_block){
			//console.log("Levi vecji od desnega");
			//dynamic_question_height_sub(frame_height_left, spremenljivka, default_var_height);	//glede na visino levega bloka uredi velikost celotnega vprasanja
			dynamic_question_height_sub(frame_height_left, spremenljivka);	//glede na visino levega bloka uredi velikost celotnega vprasanja
		}
	}else if (frame_height_left < final_height_right_block){
		$('#spremenljivka_'+spremenljivka).height(final_height_right_block + naslov_height + 50);
		//console.log("Spreminjam A1");
		//da ne bo pri mobilnikih prevec skrito vprasanje, spremeni visino variable_holder
		$('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').height(final_height_right_block + 25);
	}else if (frame_height_left > final_height_right_block){
		$('#spremenljivka_'+spremenljivka).height(frame_height_left + naslov_height + 50);
		//console.log("Spreminjam A2");
		//da ne bo pri mobilnikih prevec skrito vprasanje, spremeni visino variable_holder
		$('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').height(frame_height_left + 25);
	}
		
	//************************************ konec - dinamicna visina celotnega vprasanja glede na visino prenesenih desnih okvirjev
}