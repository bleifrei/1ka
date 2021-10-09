//za predogled in quick view oz. quick edit Drag and drop postavitve vprasanj
//var refresh = 0;
var refresh = [];
var skatlastOkvir = [];

var draggableOnDroppable = [];	//spremenljivka, ki belezi prisotnost odgovora na ustrezni povrsini pri Drag and Drop
var maxDragDrop = [];		//spremenljivka, ki belezi max stevilo moznih odgovorov

var draggableOverDroppable = [];	//spremenljivka, ki belezi prisotnost odgovora nad ustreznim okvirjem pri Drag and Drop
var default_var_height_1 = []; //belezi zacetno vrednost visine celotnega vprasanja po usklajevanju visine glede na prisotne kategorije odgovorov
var data_after_refresh = [];	//belezi, ali je uporabnik refresh-al stran oz. se vraca na stran
var frame_total_height_right = [];	//belezi visino okvirjev desnega bloka @ drag and drop grids
var draggableOver = [];
var last_vre_id = [];	//belezi vre_id zadnjega draggable, ki smo ga premikali @ Drag and drop
var vre_id_global = []; //belezi vre_id trenutne kategorije odgovorov @ Drag and drop
var last_indeks = [];	//belezi indeks zadnjega okvirja, kjer je bil draggable @ Drag and drop
var indeks_global = []; //belezi trenutni indeks okvirja @ Drag and drop
var last_drop = [];	//belezi indeks zadnjega okvirja, kjer je bil draggable droppan @ Drag and drop
var num_grids_global = []; //belezi stevilo gridov za doloceno vprasanje
var draggable_global = [];
var cat_pushed = [];	//belezi, ali je kategorijo odrinila druga kategorija odgovora @ Drag and drop

function DraggableAdmin(vre_id){
	//console.log('Hura!');
	var default_cat_height = 15;
	var final_height = 0;
	var cat_text_length = $('#vre_id_'+vre_id).html().length;	//hrani stevilo znakov, ki so vpisani v trenutni kategoriji odgovora
	//console.log('Število znakov v kategoriji: '+cat_text_length);
	
	var num_of_br = $('#vre_id_'+vre_id+' br').length;	//hrani stevilo br oz. ročnih vnosov novih vrstic
	//console.log('Število br v kategoriji: '+num_of_br);
	
	var num_imgs = $('#vre_id_'+vre_id+' img').length; //hrani stevilo img v interesiranem div-u
	//console.log('Število slik v kategoriji: '+num_imgs);
	
	var max_cat_text_length = 30; //hrani max stevilo dolzine teksta do katerega ni potrebno samodejno dodati <br>
	
	if( (cat_text_length >  max_cat_text_length) && (num_of_br == 0) && (num_imgs == 0) ){//ce je tekst daljsi od 30 znakov, nima breakov ali slik dodaj <br>

		var txt = $('#vre_id_'+vre_id).html();
		//console.log(txt);
		var txt_alt = txt.slice(0, max_cat_text_length) + "<br>" + txt.slice(max_cat_text_length);
		//console.log(txt_alt);
		$('#vre_id_'+vre_id).html(txt_alt);
		final_height = final_height + default_cat_height + 25;
		//console.log(final_height);
	}
	
	if (num_imgs != 0){	// ce imamo sliko
		var img_height = 0;
		var max_width = $('.ranking').width();
		var img = $('#vre_id_'+vre_id+' img');
		var img_width = img.width();
		var img_height = img.height();		
		if (img_width > max_width){
			img_height = (img_height / img_width) * max_width;
			$('#vre_id_'+vre_id).children('img').css({width: max_width});
			$('#vre_id_'+vre_id).children('img').css({height: img_height});
			//$('#vre_id_'+vre_id).css({height: height});
		}
			
		//console.log("img_height: "+img_height);
		if(img_height > 25){	//ce je visina slike vecja od default visine kategorije
			final_height = final_height + img_height;		
		}		
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
		$('#vre_id_'+vre_id).css({'height':final_height});	//dodaj atributu style še novo višino
		
		//TO-DO: ureditev visine celotnega vprasanja - kodo je potrebno prenesti nekam drugam, kjer bo se dalo globlano oceniti visino spremenljivke
		//var default_var_height = 271;	//default visina celotnega vprasanja
		//var final_var_height = default_var_height + img_height;
		//$('#vre_id_'+vre_id).css({'height':final_var_height});
	}	
}


//************funkcija, ki skrbi za inicializacijo draggable elementov in delovanje drag and drop ob dvojnem kliku
function init_GridDraggable(spremenljivka, vre_id, grids){
	data_after_refresh[spremenljivka] = false;
	frame_total_height_right[spremenljivka] = 0;
	last_vre_id[spremenljivka] = 0;
	vre_id_global[spremenljivka] = 0;
	last_indeks[spremenljivka] = 0;
	last_drop[vre_id] = 0;
	indeks_global[spremenljivka] = 0;
	num_grids_global[spremenljivka] = grids;//stevilo okvirjev, pomembno za revert kategorije odgovora
	cat_pushed[spremenljivka] = false;
	draggable_global[vre_id] = 0;
	//spremenljvke, ce se ne uporablja polj - konec
	
	//spremenljivke kot polja polj
	draggableOnDroppable[vre_id] = new Array(2);	//inicializacija spremenljivke, ki belezi, ali je trenutna kategorija odgovora prisotna kontejnerju/okvirju
	draggableOverDroppable[vre_id] = new Array(2);					
	for(i = 1; i <= num_grids_global[spremenljivka]; i++){
		draggableOnDroppable[vre_id][i] = false;	
		draggableOverDroppable[vre_id][i] = false;
	}	
}

//************funkcija, ki skrbi za inicializacijo draggable elementov in delovanje drag and drop ob dvojnem kliku
function Draggable(tip, spremenljivka, vre_id, ajax, anketa, site_url, usr_id, other, mobile, quick_view, preview_spremenljivka){
	var top_cat = -1;
	var left_cat = -1;
	if (mobile == 0 || mobile == 2){
		top_cat = -6;
		left_cat = -6;
	}
	
	$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).addClass('drag_and_drop').detach().appendTo('#half_frame_dropping_'+spremenljivka)	//zeleni element s kategorijami dodaj v zacetnem kontejnerju
		.draggable({	//ureditev, da je element draggable
			cursor: 'move',
			//revert: true,
			revert: function(socketObj)
			{
				if(typeof checkBranching == 'function'){
					checkBranching();
				}
				 //if false then no socket object drop occurred, reverting happens
				if(socketObj === false){
					//ce je tip kategorije en odgovor in smo premaknili kategorijo odgovora od levega okvirja proti desnemu
					if (tip == 1 && draggableOver[spremenljivka] == true){
						$('#half2_frame_dropping_'+spremenljivka).toggleClass('frame_dropping_hover_right_single');//sprozi preklop sloga levega okvirja
						//console.log('Yo!');
					}
					draggableOver[spremenljivka] = false;
					return true;
				 }
				 else {
					return false;
				}
			},
			//stack: '#half_'+spremenljivka+' div',
			stack: '#half2_'+spremenljivka+' div',
			opacity: 0.9,
			containment: '#prestavljanje_'+spremenljivka,
		})
		.dblclick(function() {	//ob dvojnem kliku na kategorijo, to prenesi kot odgovor na ustrezno mesto	
			var vre_id = $(this).attr('value');
			var id_parent = $(this).parent().attr('id');	//hrani id bloka v katerem se trenutno nahaja mozen odgovor
			var other = $(this).attr('missing');	//spremenljivka, ki hrani vrednost atributa missing
			var other_present = $('#half2_frame_dropping_'+spremenljivka).children('div').attr('missing');	//missing, ki je trenutno v desnem bloku
			
			if(typeof checkBranching == 'function'){
					checkBranching();
			}

			if (id_parent == 'half_frame_dropping_'+spremenljivka){	//ce je trenuten odgovor v levem bloku
				if( ( (tip == 1 && other == 0) ) || ( (tip == 2) && (other_present != 0) ) || ( (other != 0) ) ) {//ce je preneseni odgovor tipa 1 ("radio") in ni missing-a,
					//ALI ce je preneseni odgovor tipa 2 ("checkbox") in je missing prisoten v desnem bloku ALI ce je preneseni odgovor missing
					//sprazni blok z odgovori oz. prenesi morebitne obstojece odgovore nazaj v levi blok in zbrisi trenutni (missing) odgovor iz baze
					if (ajax){	//zbrisi trenutne odgovore iz baze
						$.post(site_url+'/main/survey/ajax.php?a=delete_dragdrop2_data', {spremenljivka: spremenljivka, usr_id: usr_id, anketa: anketa}); //post-aj potrebne podatke
					}	
					$('.ui-draggable[name="vrednost_'+spremenljivka+'"]').appendTo('#half_frame_dropping_'+spremenljivka);//prenesi skupino odgovorov v levi (zacetni) blok		
				}
				
				$('#half2_frame_dropping_'+spremenljivka).prepend(this);	//pripopaj na zacetek seznama odgovorov (desni blok) izbrani missing
				if (ajax){	//vnesi missing odgovor v bazo
					$.post(site_url+'/main/survey/ajax.php?a=accept_dragdrop1', {spremenljivka: spremenljivka, vre_id: vre_id, anketa: anketa, usr_id: usr_id}); //post-aj potrebne podatke
				}
				
				draggableOver[spremenljivka] = false;
				draggableOnDroppable[vre_id] = true;	//kategorija odgovora je v desnem okviru			
				
			}	//konec if za levi blok
			else if(id_parent == 'half2_frame_dropping_'+spremenljivka){	//ce je trenuten odgovor v desnem bloku
			
				if(other != 0 || other == 0){
					var vre_id = $(this).attr('value');
					$('#half_frame_dropping_'+spremenljivka).prepend(this);	//pripopaj prenesno na zacetek seznama kategorij
					$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).draggable( 'option', 'appendTo',  '#half_frame_dropping_'+spremenljivka);	
					$('#vrednost_if_'+usr_id).remove();
					if (ajax){
						$.post(site_url+'/main/survey/ajax.php?a=delete_dragdrop1_data', {spremenljivka: spremenljivka, vre_id: vre_id, usr_id: usr_id, anketa: anketa}); //post-aj potrebne podatke
					}					
				}

/* 				if(other == 0){
					var vre_id = $(this).attr('value');
					$('#half_'+spremenljivka).prepend(this);	//pripopaj preneseno na zacetek seznama kategorij
					$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).draggable( 'option', 'appendTo',  '#half_'+spremenljivka);	
					$('#vrednost_if_'+usr_id).remove();
					if (ajax){
						$.post(site_url+'/main/survey/ajax.php?a=delete_dragdrop1_data', {spremenljivka: spremenljivka, vre_id: vre_id, usr_id: usr_id, anketa: anketa}); //post-aj potrebne podatke
					}
				} */
				draggableOver[spremenljivka] == false;	//nismo vec v over dogodku
				draggableOnDroppable[vre_id] = false;
			}	//konec if za desni blok			

		});
	
		//dodajanje atributov, ki so prisotni pri vseh ostalih kategorijah odgovorov
		$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).attr({'value':vre_id, 'name':'vrednost_'+spremenljivka, 'onclick':'checkBranching();', 'missing':other});
				
		if(quick_view || preview_spremenljivka){	//ce je le predogled
			$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).draggable( "option", "disabled", true );	//disable-anje drag and drop
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
		//console.log('Število slik v kategoriji: '+num_imgs);
		
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
		//ureditev visine kategorije (div) glede na prisotnost slike ali vecvrsticnega teksta - konec
		

}
//***********************************************************************

//*********funkcija, ki skrbi za delovanje drag and drop funkcionalnosti
function DragDropDelovanje(tip, spremenljivka, site_url, ajax, anketa, usr_id, num_of_cats, mobile){
	//*****za mobilne naprave
	var top_cat = -1;
	var left_cat = -1;
	var default_var_height = 290;
	if (mobile == 0 || mobile == 2){
		top_cat = -6;
		left_cat = -6;
		default_var_height = 220;	//default visina celotnega vprasanja @ mobile == 0 (desktop) in mobile == 2 (tablica)
	}

	//*********************
	//ureditev visine celotnega vprasanja, ce je ta visja od default-a*******************************************************************
	var cat_total_height_left = 0; //hrani trenutno visino levega okvirja kategorij odgovorov
	var cat_margin_left = 0;
	var cat_max_height = 0; 	//hrani trenutno najvecjo visino trenutne kategorije
	var cat_default_inline_text_length = 16;	//default dolzina teksta v eni vrstici kategorij odgovorov
	//var naslov_height = $('#spremenljivka_'+spremenljivka+' .naslov').height();	//visina naslova vprasanja (besedila vprasanja)	
	var naslov_height = $('#spremenljivka_'+spremenljivka+' .naslov').outerHeight(true);	//visina naslova vprasanja (besedila vprasanja)	
	var visinaPaddingovMarginovVprasanja = $('#spremenljivka_'+spremenljivka).outerHeight(true) - $('#spremenljivka_'+spremenljivka).height();
	var visinaPaddingovMarginovHolder = $('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').outerHeight(true) - $('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').height();
	var pomoznoBesediloHeight = 40; //visina besedila "Razpoložljive kategorije:"
	//ureditev visine puscice med blokoma okvirjev
	var variable_holder_height = $('#spremenljivka_'+spremenljivka+' .variable_holder').height();	//visina variable_holder, potrebna za visino puscice med blokoma
	$('#spremenljivka_'+spremenljivka+' td.middle img').css({'top' : (naslov_height + variable_holder_height/2) + 'px'});	//visina puscice med blokoma okvirjev
	
	
	if (ajax){
		//console.log('Getting data on load');
		$.get(site_url+'/main/survey/ajax.php?a=get_dragdrop1_data', {spremenljivka: spremenljivka, anketa: anketa}, function(data){ //get potrebnih podatkov za resevanje missing

			var array_length = data.length;	//hrani koliko podatkov je prisotnih v polju s podatki iz baze
			var vre_id = [];	//polje, ki hrani id-je vrednosti vseh kategorij odgovorov interesirane spremenljivke
			//var cat_total_height = 0; //hrani trenutno visino celotnega trenutnega vprasanja

			//console.log(array_length);	//prikazi koliko podatkov je prisotnih v polju s podatki iz baze
			for(var i = 0; i < array_length; i++){	//sprehodi se po vseh vrednostih polja vre_id
				vre_id[i] = data[i];	//polje iz podatkov iz baze data[], shrani v polje vre_id[]
				
				var cat_height = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id[i]).height();	//visina kategorije z oznako			
				var cat_height_real = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id[i]).outerHeight(true);	//realna visina kategorije z oznako
				
				//console.log('Visina '+(i + 1)+': '+cat_height_real);
				
				if(mobile == 1){	//ko je mobilnik, uredi velikost okvirja kategorije glede na dolzino besedila
					var cat_text_length = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id[i]).text().length;	//dolzina teksta v kategoriji odgovora
					//console.log("Dolžina besedila v kategoriji: "+cat_text_length);
					if(cat_text_length > (cat_default_inline_text_length)*2 ){	//ce je dolzina teksta v kategoriji daljsa 2-krat vec od default (16)
						//console.log("Tekst je daljši!");
						var num_of_rows = cat_text_length / cat_default_inline_text_length;
						//console.log("num_of_rows: "+num_of_rows);
						cat_height_real = cat_height_real + 25 * (num_of_rows - 2);	//trenutno visino kategorije povecaj za 25
						$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id[i]).css({'height':(cat_height_real)+'px'});	//spremeni visino kategorije
					}
				}
				
				cat_total_height_left = cat_total_height_left + cat_height_real;	//izracun koncne realne visine levega okvirja
				
				if (cat_max_height <  cat_height){	//ce je maksimalna visina kategorije manjsa od trenutne visine kategorije
					cat_max_height = cat_height;	//naj bo vrednost max visine kategorije trenutna visina kategorije
				}
				
			}
			
			var koncnaVisinaVprasanja = cat_total_height_left + naslov_height + pomoznoBesediloHeight + visinaPaddingovMarginovVprasanja + visinaPaddingovMarginovHolder*4;

			$('#spremenljivka_'+spremenljivka).css({'height':(koncnaVisinaVprasanja)+'px'});
			
			$('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').css({'height':(koncnaVisinaVprasanja*0.85)+'px'}); //visina variable_holder
			
			//*************urejanje visine levega okvirja kategorij odgovorov
			$('#half_frame_dropping_'+spremenljivka).css({'height':(cat_total_height_left)+'px'});//visina levega okvirja
			
			//*************urejanje visine desnega okvirja glede na visino levega okvirja, ki hrani kategorije odgovorov
			if (tip != 1){ //ce nimamo kategorije vec odgovorov
				$('#half2_frame_dropping_'+spremenljivka).css({'height':(cat_total_height_left)+'px'});	//visina desnega okvirja
			}	
			else if (tip == 1){	//ce imamo kategorije en odgovor
				$('#half2_frame_dropping_'+spremenljivka).css({'height':(cat_max_height)+'px'});//visina desnega okvirja
			}
			
			default_var_height = $('#spremenljivka_'+spremenljivka).height();
			//console.log("default_var_height konec: "+default_var_height);
			
			//urejanje visine na kateri se nahaja gumb za ponastavljanje vprašanja
			ResetButtonHeight(spremenljivka);

		}, "json");				
	}
	//***********************************************************************************************************************************
	$('#half_frame_dropping_'+spremenljivka)
		.droppable({
			//hoverClass: 'frame_ranking_hover',
			hoverClass: 'frame_dropping_hover',
			drop: function (event, ui) {
				if(typeof checkBranching == 'function'){
					checkBranching();
				}
				//$(this).prepend(ui.draggable);	//pripopaj na zacetek seznama odgovorov (desni blok) 
				//ui.draggable.position( { of: $(this), my: 'left top', at: 'left top' } );	//pozicijoniraj levi zgornji del elementa z odgovorom na levem zgornjem delu droppable						
				//ui.draggable.offset( { of: $(this), my: 'left top', at: 'left top' } );
				
				//$(ui.draggable).detach().css({top: -6,left: -6}).appendTo(this);
				$(ui.draggable).detach().css({top: top_cat,left: left_cat}).appendTo(this);
				//$(ui.draggable).addClass('drag_and_drop').detach().appendTo(this);
				$(ui.draggable).addClass('drag_and_drop_right');
				
				//brisi podatke prenesenega odgovora iz baze
				var vre_id = ui.draggable.attr('value');
				if (ajax){
					$.post(site_url+'/main/survey/ajax.php?a=delete_dragdrop1_data', {spremenljivka: spremenljivka, vre_id: vre_id, usr_id: usr_id, anketa: anketa}); //post-aj potrebne podatke za brisanje			
				}
				draggableOver[spremenljivka] == false;	//nismo vec v over dogodku
				draggableOnDroppable[vre_id] = false;
				
			},
			out: function (event, ui) {	//ob izhodu iz drop zone
				//answer_coming[spremenljivka] = true;
				//console.log('out');
				$(ui.draggable).removeClass('drag_and_drop_right');
				$(ui.draggable).addClass('drag_and_drop');
			}
		});
	
	//var stevilo_zdaj = $('#half2_frame_dropping_'+spremenljivka).children('div').attr('missing');
	//var other_present = $(this).children('div').attr('missing');
	//console.log(stevilo_zdaj);
	
	//if (tip == 1 && ($('#half2_frame_dropping_'+spremenljivka).children('.ranking').length != 0) ){	//ce je tip vprasanja kategorije en odgovor
	if (tip == 1){	//ce je tip vprasanja kategorije en odgovor
			$('#half2_frame_dropping_'+spremenljivka)
			.droppable({
				//hoverClass: 'frame_ranking_hover',
				hoverClass: 'frame_dropping_hover_right_single',
				//accept: '#half_'+spremenljivka+' div',
				drop: function (event, ui) {	//ob dropanju odgovora v desni blok
					//console.log("Drop");
					if(typeof checkBranching == 'function'){
						checkBranching();
					}

					var vre_id = ui.draggable.attr('value');
					var other = ui.draggable.attr('missing');	//spremenljivka, ki hrani vrednost atributa missing
					var other_present = $(this).children('div').attr('missing');	//missing, ki je trenutno v desnem bloku
					var cat_right = $(this).children('div').css('height');	//ali je prisotna kaksna kategorija v desnem okvirju? Undefined = ne
					

					//ce je preneseni odgovor tipa 1 ("radio") in ni missing-a,
					//ALI ce je preneseni odgovor tipa 2 ("checkbox") in je missing prisoten v desnem bloku 
					//ALI ce je preneseni odgovor missing
					//sprazni blok z odgovori oz. prenesi morebitne obstojece odgovore nazaj v levi blok
					if( ( (tip == 1 && other == 0 && cat_right) ) || ( (tip == 2) && (other_present != 0) ) || ( (other != 0) ) ) {
						$('.ui-draggable[name="vrednost_'+spremenljivka+'"]').appendTo('#half_frame_dropping_'+spremenljivka);//prenesi skupino odgovorov v levi (zacetni) blok	
					}
					
					//pozicioniranje draggable na pravo mesto
					$(ui.draggable).removeClass('drag_and_drop');//odstranimo, ker je nepotrebno na levi strani
					$(ui.draggable).detach().css({top: top_cat,left: left_cat}).appendTo(this);	//najprej pozicioniramo na zacasni lokaciji
					$(ui.draggable).addClass('drag_and_drop_right');//dodamo slog, ki dokoncno postavi draggable na pravo lokacijo
					//pozicioniranje draggable na pravo mesto - konec
					
					if (ajax){
						//post-aj potrebne podatke za belezenje v bazo
						//in zbrisi trenutno hranjene podatke, ce je to potrebno
						$.post(site_url+'/main/survey/ajax.php?a=accept_dragdrop1', {other_present: other_present, other: other, cat_right: cat_right, tip: tip, spremenljivka: spremenljivka, vre_id: vre_id, anketa: anketa, usr_id: usr_id});
					}
					draggableOver[spremenljivka] = false;	//zabelezi, da draggable ni vec over
					draggableOnDroppable[vre_id] = true;	//kategorija odgovora je v desnem okviru
				},
				over: function (event, ui) {	//ob izhodu iz drop zone
					draggableOver[spremenljivka] = true;	//zabelezi, da je draggable over
					if(typeof checkBranching == 'function'){
						checkBranching();
					}					
				},
				out: function (event, ui) {	//ob izhodu iz drop zone
					draggableOver[spremenljivka] = false;	//zabelezi, da draggable ni vec over
					$(ui.draggable).removeClass('drag_and_drop_right'); //odstranimo slog, ker drugace se draggable ne vidi, ko ga premikamo
/* 					var vre_id = ui.draggable.attr('value');
					if (ajax){
						$.post(site_url+'/main/survey/ajax.php?a=delete_dragdrop1_data', {spremenljivka: spremenljivka, vre_id: vre_id, usr_id: usr_id, anketa: anketa}); //post-aj potrebne podatke za brisanje
					} */
				}
			});
	}
	//else if( tip == 2 || (tip == 1 && ($('#half2_frame_dropping_'+spremenljivka).children('.ranking').length == 0) ) )  {	//ce je tip vprasanja kategorije vec odgovorov in pri kategorije en odgovor ni nobenih odgovorov v desnem okvirju
	else if( tip == 2 )  {	//ce je tip vprasanja kategorije vec odgovorov in pri kategorije en odgovor ni nobenih odgovorov v desnem okvirju
			$('#half2_frame_dropping_'+spremenljivka)
			.droppable({
				//hoverClass: 'frame_ranking_hover',
				hoverClass: 'frame_dropping_hover',
				//accept: '#half_'+spremenljivka+' div',
				drop: function (event, ui) {	//ob dropanju odgovora v desni blok
					if(typeof checkBranching == 'function'){
						checkBranching();
					}
					//console.log("Drop");

					var vre_id = ui.draggable.attr('value');
					var other = ui.draggable.attr('missing');	//spremenljivka, ki hrani vrednost atributa missing
					var other_present = $(this).children('div').attr('missing');	//missing, ki je trenutno v desnem bloku
					

					
					if( ( (tip == 2) && (other_present != 0) ) || ( (other != 0) ) ) {
						//ce je preneseni odgovor tipa 2 ("checkbox") in je missing prisoten v desnem bloku 
						//ALI ce je preneseni odgovor missing
						//sprazni blok z odgovori oz. prenesi morebitne obstojece odgovore nazaj v levi blok
						$('.ui-draggable[name="vrednost_'+spremenljivka+'"]').appendTo('#half_frame_dropping_'+spremenljivka);//prenesi skupino odgovorov v desni (zacetni) blok					
					}
					//pozicioniranje draggable na pravo mesto
					$(ui.draggable).removeClass('drag_and_drop');	//odstranimo, ker je nepotrebno na levi strani
					$(ui.draggable).detach().css({top: top_cat,left: left_cat}).appendTo(this);	//najprej pozicioniramo na zacasni lokaciji
					$(ui.draggable).addClass('drag_and_drop_right');	//dodamo slog, ki dokoncno postavi draggable na pravo lokacijo
					//pozicioniranje draggable na pravo mesto - konec
					if (ajax){
						//post-aj potrebne podatke za belezenje v bazo
						//in zbrisi trenutno hranjene podatke, ce je to potrebno
						$.post(site_url+'/main/survey/ajax.php?a=accept_dragdrop1', {other_present: other_present, other: other, tip: tip, spremenljivka: spremenljivka, vre_id: vre_id, anketa: anketa, usr_id: usr_id}); //post-aj potrebne podatke za belezenje v bazo
					}
				},
				out: function (event, ui) {	//ob izhodu iz drop zone
					$(ui.draggable).removeClass('drag_and_drop_right');	//odstranimo slog, ker drugace se draggable ne vidi, ko ga premikamo
/* 					var vre_id = ui.draggable.attr('value');
					if (ajax){
						$.post(site_url+'/main/survey/ajax.php?a=delete_dragdrop1_data', {spremenljivka: spremenljivka, vre_id: vre_id, usr_id: usr_id, anketa: anketa}); //post-aj potrebne podatke za brisanje
					} */
				}
			});
	}

}
//**************************************************************************************
//************funkcija, ki skrbi za inicializacijo draggable elementov pri gridih
function GridDraggable(tip, spremenljivka, vre_id, ajax, anketa, site_url, usr_id, other, mobile, skatle, quick_view, preview_spremenljivka)
{
	//*****za mobilne naprave	
	var top_cat = -1;
	var left_cat = -1;
	if (mobile == 0 || mobile == 2){
		top_cat = -6;
		left_cat = -6;
		top_cat_right = 30;
	}
	//*********************
	$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).addClass('drag_and_drop').detach().appendTo('#half_frame_dropping_'+spremenljivka)	//zeleni element s kategorijami dodaj v zacetnem kontejnerju
		.draggable({	//ureditev, da je element draggable
			cursor: 'move',
			//revert: true,
			helper: 'original',
			zIndex: 100,
			revert: function(socketObj)
			{
				if(typeof checkBranching == 'function'){
					checkBranching();
				}
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
				var skatle = skatlastOkvir[spremenljivka];
				//console.log("Skatle za "+spremenljivka+" so:"+skatle);
				
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
						//console.log("stevilo_prisotnih:"+stevilo_prisotnih);
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
								dynamic_question_height(spremenljivka, num_grids_global[spremenljivka], mobile, skatle);	
								//************************************ konec - dinamicna visina celotnega vprasanja glede na visino prenesenih desnih okvirjev

							}
						}
						
						//ce so skatlasti okvirji
						if(skatle){
							//console.log("Imamo skatle ob revertu");
							//$(draggable).addClass('drag_and_drop_box_right');//dodamo slog, ki dokoncno postavi draggable na pravo lokacijo
							$(draggable).addClass('drag_and_drop_box_right_after_refresh');//dodamo slog, ki dokoncno postavi draggable na pravo lokacijo
							var pravaVisina = calcPravaVisina(prejsnji_okvir, draggable, 0, spremenljivka, 0, 0, 1); //calcPravaVisina(okvir, draggable, indeks, spremenljivka, refresh, zapStevKategorije, revert)	//visina/pozicija prenesene kategorije v desnem okvirju
							//console.log("pravaVisina:"+pravaVisina);	
							
							//ustavi animacijo revert-a, da za tem lahko takoj pozicioniramo kategorijo odgovora na pravo mesto
							//$(draggable).finish();
							//$(draggable).stop();
							
							//pozicioniraj kategorijo odgovora na pravo mesto
							$(draggable).css({top:pravaVisina+'!important'});
							//pozicioniraj kategorijo odgovora na pravo mesto - konec
							
							
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
			
		if(quick_view || preview_spremenljivka){
			$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).draggable( "option", "disabled", true );
		}
			
		//dodajanje atributov, ki so prisotni pri vseh ostalih kategorijah odgovorov
		$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).attr({'reverted':false,'value':vre_id, 'name':'vrednost_'+spremenljivka, 'onclick':'checkBranching();', 'missing':other});
		
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
function GridDragDropDelovanje(num_grids, indeks, tip, spremenljivka, site_url, ajax, anketa, usr_id, num_of_cats, mobile, skatle){
	//var default_var_height_1 = [];
	//*****za mobilne naprave
	var top_cat = -1;
	var left_cat = -1;
	var default_var_height = 290;	//default visina celotnega vprasanja
	if (mobile == 0 || mobile == 2){
		top_cat = -6;
		left_cat = -6;
		top_cat_right = 30;
		var default_var_height = 220;	//default visina celotnega vprasanja
	}
	//*********************
	//ureditev visine celotnega vprasanja, ce je ta visja od default-a*******************************************************************
	//var cat_margin_left = 10 + 5*2 + 1*2; //hrani rob za ureditev visine levega okvirja = margin_spodnji + padding(spredi pa zadi) + border(spredi pa zadi) + neznanka
	var cat_max_height = 0; 	//hrani trenutno najvecjo visino tretnutne kategorije
	var cat_default_height = 37;
	//var naslov_height = $('#spremenljivka_'+spremenljivka+' .naslov').height();	//visina naslova vprasanja (besedila vprasanja)	
	var naslov_height = $('#spremenljivka_'+spremenljivka+' .naslov').outerHeight(true);	//visina naslova vprasanja (besedila vprasanja)
	var visinaPaddingovMarginovVprasanja = $('#spremenljivka_'+spremenljivka).outerHeight(true) - $('#spremenljivka_'+spremenljivka).height();
	var visinaPaddingovMarginovHolder = $('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').outerHeight(true) - $('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').height();
	var pomoznoBesediloHeight = 40; //visina besedila "Razpoložljive kategorije:"
	var cat_default_inline_text_length = 16;	//default dolzina teksta v eni vrstici kategorij odgovorov
	
	//ureditev visine puscice med blokoma okvirjev
	var variable_holder_height = $('#spremenljivka_'+spremenljivka+' .variable_holder').height();	//visina variable_holder, potrebna za visino puscice med blokoma
	$('#spremenljivka_'+spremenljivka+' td.middle img').css({'top' : (naslov_height + variable_holder_height/2) + 'px'});	//visina puscice med blokoma okvirjev	
	if (ajax){
		//console.log('Getting data on load');
		$.get(site_url+'/main/survey/ajax.php?a=get_dragdrop1_data', {spremenljivka: spremenljivka, anketa: anketa}, function(data){ //get potrebnih podatkov za resevanje missing
			
			//trenutna visina celotnega vprasanja
			var default_var_height = $('#spremenljivka_'+spremenljivka).height();
			//console.log("default_var_height: "+default_var_height + " indeks: "+indeks+" num of grids: "+num_grids);
		
			if(indeks == 1){//samo enkrat pojdi skozi leve kategorije odgovorov
				var cat_total_height_left = 0; //hrani trenutno visino levega okvirja kategorij odgovorov
				var array_length = data.length;	//hrani koliko podatkov je prisotnih v polju s podatki iz baze
				var vre_id = [];	//polje, ki hrani id-je vrednosti vseh kategorij odgovorov interesirane spremenljivke
								
				for(var i = 0; i < array_length; i++){	//sprehodi se po vseh vrednostih polja vre_id v levem bloku
					vre_id[i] = data[i];	//polje iz podatkov iz baze data[], shrani v polje vre_id[]

					var cat_height = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id[i]).outerHeight();	//visina kategorije
					var cat_height_real = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id[i]).outerHeight(true);	//realna visina kategorije z oznako
					
					//console.log('Visina '+(i + 1)+': '+cat_height_real);
					
					if(mobile == 1){	//ko je mobilnik, uredi velikost okvirja kategorije glede na dolzino besedila
						var cat_text_length = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id[i]).text().length;	//dolzina teksta v kategoriji odgovora
						
						if(cat_text_length > (cat_default_inline_text_length)*2.5 ){	//ce je dolzina teksta v kategoriji daljsa 2-krat vec od default (16) - DATI NA 2,5 KRATNIK
							//console.log("Tekst je daljši!");
							//console.log("Dolžina besedila v kategoriji: "+cat_text_length);
							var num_of_rows = cat_text_length / cat_default_inline_text_length;
							//console.log("num_of_rows: "+num_of_rows);
							
							cat_height = cat_height + 25 * (num_of_rows - 1);	//trenutno visino kategorije povecaj za 25
							//cat_height_real = cat_height_real + 25 * (num_of_rows - 1);	//trenutno visino kategorije povecaj za 25
							//console.log("num_of_rows final: "+ (num_of_rows - 1));
							
							//$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id[i]).css({'height':(cat_height_real)+'px'});	//spremeni visino kategorije
							$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id[i]).css({'height':(cat_height)+'px'});	//spremeni visino kategorije
						}
						//console.log("cat_height ajax za spremenljivko:"+spremenljivka+" "+cat_height);
					}
					
					cat_total_height_left = cat_total_height_left + cat_height_real;	//izracun koncne visine levega okvirja
					
					
					if (cat_max_height <  cat_height){	//ce je maksimalna visina kategorije manjsa od trenutne visine kategorije
						cat_max_height = cat_height;	//naj bo vrednost max visine kategorije trenutna visina kategorije
					}				


				}				
				//*************urejanje visine levega okvirja kategorij odgovorov
				$('#half_frame_dropping_'+spremenljivka).css({'height':(cat_total_height_left)+'px'});//visina levega okvirja
			}
			
			if (mobile == 0 || mobile == 2){
				if(skatle){
					var title_heigth_real = $('.frame_dropping_titles_box').outerHeight(true);
				}else{
					var title_heigth_real = $('.frame_dropping_titles').outerHeight(true);
				}				
			}else{
				if(skatle){
					var title_heigth_real = $('.frame_dropping_titles_box_mobile').outerHeight(true);
				}else{
					var title_heigth_real = $('.frame_dropping_titles_mobile').outerHeight(true);
				}
				
			}
			
			if(indeks == 1){	//samo enkrat pojdi skozi desne okvirje
				for(var j = 1; j <= num_grids; j++){	//preglej vse desne okvirje v desnem bloku
					//realna visina trenutnega okvirja***************************************
					var okvir_height_real = $('#half2_frame_dropping_'+j+'_'+spremenljivka).outerHeight(true);
					frame_total_height_right[spremenljivka] = frame_total_height_right[spremenljivka] + okvir_height_real + title_heigth_real;
					//console.log(j+" frame_total_height_right["+spremenljivka+"]:"+frame_total_height_right[spremenljivka]);

					//ureditev pravilnega pozicioniranja kategorij ob refreshu @okvir skatlaste oblike
					//console.log("Refresh je:"+refresh);
					if(refresh[spremenljivka] == 1 && skatle){
						//console.log("Je refresh");
						var desniOkvir = $('#half2_frame_dropping_'+j+'_'+spremenljivka);
						var cat_right = desniOkvir.children('div').outerHeight(true);//belezi visino kategorije odgovora, ce je ta prisotna v trenutnem desnem okvirju
						if(cat_right){	//ce je kaj v okvirju, uredi pravi visino za kategorijo
							var zapStevKategorije = 1;
							desniOkvir.children('div').each(function () {
								var trenutnaVisinaKategorije = $(this).outerHeight(true);
								//console.log("trenutnaVisinaKategorije:"+trenutnaVisinaKategorije);
								//var trenutnaVisinaKategorije = 0;
								var pravaVisina = calcPravaVisina(desniOkvir, $(this), j, spremenljivka, refresh[spremenljivka], zapStevKategorije);
								desniOkvir.prepend($(this).css({top: pravaVisina})); //prenesi ustrezni odgovor
								zapStevKategorije++;
							});
							//var pravaVisina = calcPravaVisina(desniOkvir, 0, j, spremenljivka, refresh);
						}
							
					}else{
						//console.log("Ni bilo refresha");
					}
					//ureditev pravilnega pozicioniranja kategorij ob refreshu @okvir skatlaste oblike - konec
				}
				//console.log(" frame_total_height_right["+spremenljivka+"]:"+frame_total_height_right[spremenljivka]);
				if(refresh[spremenljivka] == 1 && skatle){
					refresh[spremenljivka] = 0;
				}
			}
			
			default_var_height_1[spremenljivka] = $('#spremenljivka_'+spremenljivka).height(); //belezenje celotne zacetne visine spremenljivke

			 if(cat_total_height_left > frame_total_height_right[spremenljivka]){ //ce je trenutna visina levega okvirja z odgovori vecja od koncne visine desnega okvirja
				//console.log("Levi vecji od desnega za "+spremenljivka);
				if( (cat_total_height_left > default_var_height) ){ //ce je koncna visina levega okvirja z odgovori vecja od visine celotnega vprasanja
					dynamic_question_height_sub(cat_total_height_left, spremenljivka, default_var_height_1[spremenljivka]); //ustrezno spremeni visino celotnega vprasanja
				}else if(cat_total_height_left < default_var_height_1[spremenljivka]){	//ce je koncna visina levega okvirja z odgovori manjsi od visine celotnega vprasanja
					dynamic_question_height_sub(cat_total_height_left, spremenljivka, default_var_height_1[spremenljivka]); //ustrezno spremeni visino celotnega vprasanja
				} else{
					dynamic_question_height_sub(cat_total_height_left, spremenljivka, default_var_height_1[spremenljivka]);//ustrezno spremeni visino celotnega vprasanja
				}

			//ce je trenutna visina levega okvirja z odgovori manjsa od koncne visine desnega okvirja
			}else if(cat_total_height_left < frame_total_height_right[spremenljivka]){
				//console.log("Levi manjsi od desnega za "+spremenljivka);
				
				frame_total_height_right[spremenljivka] = frame_total_height_right[spremenljivka] + naslov_height + pomoznoBesediloHeight + visinaPaddingovMarginovVprasanja + visinaPaddingovMarginovHolder*5;

				$('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').height(frame_total_height_right[spremenljivka]*0.9);
				
				$('#spremenljivka_'+spremenljivka).height(frame_total_height_right[spremenljivka]);
			}
			
			//urejanje visine na kateri se nahaja gumb za ponastavljanje vprašanja
			ResetButtonHeight(spremenljivka);

		}, "json");
	}
	
	//************************ konec - ureditev visine celotnega vprasanja, ce je ta visja od default-a
	
	//console.log("Grid delovanje: "+num_grids);
	
	$('#half_frame_dropping_'+spremenljivka)
		.droppable({
			//hoverClass: 'frame_dropping_hover',
			drop: function (event, ui) {
				if(typeof checkBranching == 'function'){
					checkBranching();
				}
				
				//$(ui.draggable).detach().css({top: -6,left: -6}).appendTo(this);
				$(ui.draggable).detach().css({top: top_cat,left: left_cat}).appendTo(this);
				$(ui.draggable).addClass('drag_and_drop_right');
				
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
					dynamic_question_height(spremenljivka, num_grids, mobile, skatle);
					last_indeks[spremenljivka] = 0;
					last_drop[vre_id] = 0;
					last_vre_id[spremenljivka] = 0;
					
				}
				from_left[vre_id] = true;
/* 				var default_var_height = $('#spremenljivka_'+spremenljivka).height();
				default_var_height_1[spremenljivka] = default_var_height; */
			},
			out: function (event, ui) {	//ob izhodu iz drop zone
				$(ui.draggable).removeClass('drag_and_drop_right');
				$(ui.draggable).addClass('drag_and_drop');
				//answer_coming[spremenljivka] = true;
				//var vre_id = ui.draggable.attr('value');
				//console.log("from_left["+vre_id+"]: "+from_left[vre_id]);
			},
			over: function (event, ui) {
				var vre_id = ui.draggable.attr('value');
				//potrebno pridobiti informacijo ze tukaj, ker drugace so tezave @ revert v levi okvir
				vre_id_global[spremenljivka] = vre_id;
				draggable_global[vre_id] = ui.draggable;
				
				$(ui.draggable).removeClass('drag_and_drop_box_right_after_refresh');
				
			}
		});

	
	$('#half2_frame_dropping_'+indeks+'_'+spremenljivka)
		.droppable({
			//hoverClass: 'frame_ranking_hover',
			//hoverClass: 'frame_dropping_hover',
			tolerance: "pointer",
			//accept: '#half_'+spremenljivka+' div',
			drop: function (event, ui) {	//ob dropanju odgovora v desni blok
				//console.log("Drop");
				if(typeof checkBranching == 'function'){
					checkBranching();
				}
				
				if(mobile == 0 || mobile == 2){
					//$(this).toggleClass('frame_dropping_wider'); //spremeni videz trenutnega okvirja
				}else if(mobile == 1){
					//$(this).toggleClass('frame_dropping_wider_mobile'); //spremeni videz trenutnega okvirja
				}
				
				
				num_grids = num_grids_global[spremenljivka];
				var vre_id = ui.draggable.attr('value');
				var other = ui.draggable.attr('missing');	//spremenljivka, ki hrani vrednost atributa missing
				var other_present = $(this).children('div').attr('missing');	//missing, ki je trenutno v desnem bloku
				var cat_right = $(this).children('div').outerHeight(true);	//ali je prisotna kaksna kategorija v trenuntem desnem okvirju? Undefined = ne
				var vre_id_present = $(this).children('div').attr('value'); //vre_id kategorije odgovora, ki je prisotna v okvirju ob dropu
				//var draggable_global[spremenljivka] = ui.draggable;
				draggable_global[vre_id] = ui.draggable;		
				
 				//*******************dinamicna visina celotnega vprasanja glede na vsebino prenesenih desnih okvirjev
				var title_heigth = 26;	//visina okvricka z naslovom
				var height_beside = 40; //visina od zacetka vprasanja do prvega okvirja (in malo po zadnjem okvirju)
				var final_height_right_block = 0;	//hrani koncno visino desnega bloka, torej vseh prisotnih okvirjev
				final_height_right_block = final_height_right_block + height_beside; //koncni visini dodamo se "praznino" med zacetkom vprasanja in prvim okvirjem
				
				for(var j = 1; j <= num_grids; j++){	//preglej vse okvirje
					//notranja visina trenutnega okvirja***************************************			
					var okvir_height = $('#half2_frame_dropping_'+j+'_'+spremenljivka).outerHeight(true);
					//*************************************************************************
					final_height_right_block = final_height_right_block + okvir_height + title_heigth;
					//console.log('Koncna visina desnega bloka: '+final_height_right_block);
				}
				
				//trenutna visina celotnega vprasanja
				var default_var_height = $('#spremenljivka_'+spremenljivka).height();
				//console.log('Default: '+default_var_height);
				//console.log('Final: '+final_height_right_block);
				if(final_height_right_block > default_var_height){
					$('#spremenljivka_'+spremenljivka).css({'height':final_height_right_block+'px'});
					//da ne bo pri mobilnikih prevec skrito vprasanje
					$('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').css({'height':(final_height_right_block - 100)+'px'});
					
				}
				//************************************ konec - dinamicna visina celotnega vprasanja glede na visino prenesenih desnih okvirjev
				
				//ce je tabela - en odgovor
				if(tip == 6){
					//pozicioniranje draggable na pravo mesto
					if (cat_right && skatle){	//ce je ze nekaj v okvirju in imamo okvirje skatlaste oblike
						$(ui.draggable).removeClass('drag_and_drop');//odstranimo, ker je nepotrebno
						$(ui.draggable).removeClass('drag_and_drop_box_right_after_refresh');//odstranimo, ker je nepotrebno					
						var pravaVisina = calcPravaVisina(this, ui.draggable);	//visina/pozicija prenesene kategorije v desnem okvirju
						$(ui.draggable).detach().css({top: (pravaVisina), left: left_cat}).prependTo(this);	//pozicioniraj kategorijo odgovora na pravo mesto tako, da je nad prejsnjim
 						if($(this).children('div').hasClass('drag_and_drop_box_right_after_refresh')){	//ce so v okvirju kategorije, po refreshu
							$(ui.draggable).addClass('drag_and_drop_box_right_after_refresh');//dodamo slog, ki dokoncno postavi draggable na pravo lokacijo
						}else{
							$(ui.draggable).addClass('drag_and_drop_box_right_over');//dodamo slog, ki dokoncno postavi draggable na pravo lokacijo
						}						
					}else{
						$(ui.draggable).removeClass('drag_and_drop');//odstranimo, ker je nepotrebno
						$(ui.draggable).detach().css({top: top_cat,left: left_cat}).appendTo(this);	//najprej pozicioniramo na zacasni lokaciji
						if(skatle){	//ce je okvir skatlaste oblike
							$(ui.draggable).removeClass('drag_and_drop_box_right_after_refresh');//odstranimo, ker je nepotrebno na levi strani
							$(ui.draggable).addClass('drag_and_drop_box_right');//dodamo slog, ki dokoncno postavi draggable na pravo lokacijo
						}else{
							$(ui.draggable).addClass('drag_and_drop_right');//dodamo slog, ki dokoncno postavi draggable na pravo lokacijo
						}					
					}
					
					//pozicioniranje draggable na pravo mesto - konec					
					
					if (ajax){
						$.post(site_url+'/main/survey/ajax.php?a=accept_dragdrop_grid', {vre_id_present: vre_id_present, tip: tip, spremenljivka: spremenljivka, vre_id: vre_id, anketa: anketa, usr_id: usr_id, indeks: indeks, cat_right: cat_right, last_vre_id: last_vre_id[spremenljivka]}); //post-aj potrebne podatke za belezenje v bazo
						if(last_drop[vre_id] != indeks || last_drop[vre_id] != 0){
							$.post(site_url+'/main/survey/ajax.php?a=delete_dragdrop_grid_data_1', {spremenljivka: spremenljivka, vre_id: vre_id, usr_id: usr_id, anketa: anketa, indeks: last_drop[vre_id]}); //post-aj potrebne podatke za brisanje
						}
					}
				}
				else if(tip == 16 && draggableOnDroppable[vre_id][indeks] == false){	//ce je tabela - vec odgovorov in odgovora ni v trenutnem okvirju, uredi clone
					var visina_test = ui.draggable.css('height');					
					//pozicioniranje draggable na pravo mesto**********************
					if(cat_right && skatle){	//ce je ze nekaj v okvirju in je ta skatlaste oblike
						var pravaVisina = calcPravaVisina(this, ui.draggable);	//visina/pozicija prenesene kategorije v desnem okvirju

						$(ui.draggable.clone()).detach().css({top: pravaVisina,left: left_cat, height: visina_test}).prependTo(this);	//pozicioniraj kategorijo odgovora na pravo mesto in dodaj ustrezno visino
						
						if($(this).children('div').hasClass('drag_and_drop_box_right_after_refresh')){	//ce so v okvirju kategorije, po refreshu
							$(this).children(ui.draggable).addClass('drag_and_drop_box_right_after_refresh');//dodamo slog, ki dokoncno postavi draggable na pravo lokacijo
						}else{
							$(this).children(ui.draggable).addClass('drag_and_drop_box_right_over');//dodamo slog, ki dokoncno postavi draggable na pravo lokacijo
						}
					}else{
						if(skatle){	//ce je okvir skatlaste oblike
							$(ui.draggable.clone()).detach().css({top: top_cat,left: left_cat, height: visina_test}).addClass('drag_and_drop_box_right').appendTo(this);	//pozicioniraj kategorijo odgovora na pravo mesto in dodaj ustrezno visino
						}else{
							$(ui.draggable.clone()).detach().css({top: top_cat,left: left_cat, height: visina_test}).addClass('drag_and_drop_right').appendTo(this);	//pozicioniraj kategorijo odgovora na pravo mesto in dodaj ustrezno visino
						}					
					}
					//pozicioniranje draggable na pravo mesto - konec**************************
					
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
				//console.log("Over");
				num_grids = num_grids_global[spremenljivka];
				if(typeof checkBranching == 'function'){
					checkBranching();
				}
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
							dynamic_question_height(spremenljivka, num_grids, mobile, skatle);
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
							dynamic_question_height(spremenljivka, num_grids, mobile, skatle);
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
				console.log("Out");
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
				
				if(skatle){	//ce so okvirji skatlaste oblike
					$(ui.draggable).removeClass('drag_and_drop_box_right'); //odstranimo slog, ker drugace se draggable ne vidi, ko ga premikamo
					$(ui.draggable).removeClass('drag_and_drop_box_right_over'); //odstranimo slog, ker drugace se draggable ne vidi, ko ga premikamo
					$(ui.draggable).removeClass('drag_and_drop_box_right_after_refresh');//drag_and_drop_box_right_after					
					
				}else{
					$(ui.draggable).removeClass('drag_and_drop_right'); //odstranimo slog, ker drugace se draggable ne vidi, ko ga premikamo
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
				//console.log(draggableOnDroppable[vre_id][indeks]);
				last_frame_height(prejsnji_okvir, spremenljivka, num_grids, cat_margin_left, draggable); //spremeni visino zadnje obiskanega okvirja
				draggableOnDroppable[vre_id][indeks] = false;	//oznacimo, da smo trenutno kategorijo odgovora odstranili iz okvirja
				//console.log("draggableOnDroppable["+vre_id+"]["+indeks+"]: "+draggableOnDroppable[vre_id][indeks]);
				draggableOverDroppable[vre_id][indeks] = false;
				from_left[vre_id] = true;
				dynamic_question_height(spremenljivka, num_grids, mobile, skatle);
				//console.log("vre_id: "+vre_id);
				
				ResetButtonHeight(spremenljivka);//povrni gumb na zacetno visino				
			});
		
		});
		
	}
	//********************** konec - odstranitev odgovorov iz desnih okvirjev @ tabela - vec odgovorov
}
//**************************************************************************************


//ureja visino ovirja in kategorije vprasanja
function frame_and_question_height(trenutni_okvir, spremenljivka, num_grids, cat_margin_left, cat_default_height, draggable){
	//console.log('Frame and question height');
	//uredi visino okvirja in celotnega vprasanja		
	var other = draggable.attr('missing');	//spremenljivka, ki hrani vrednost atributa missing
	var other_present = trenutni_okvir.children('div').attr('missing');	//missing, ki je trenutno v desnem bloku
	
	//***************** glede na visino trenutno prenesenih kategorij odgovora, povecaj visino okvirja
	var cat_height_now = draggable.outerHeight(true);	//visina kategorije, trenutno prenesenega odgovora
	var cat_right = trenutni_okvir.children('div').outerHeight(true); //visina kategorije odgovora, ce je ta prisotna v trenutnem desnem okvirju
	
	//ce je v trenutnem desnem okvirju ze prisotna kategorija odgovora,
	if(cat_right){
		var whole_heigth = trenutni_okvir.height();	//trenutna visina desnega okvirja
		var okvir_height = whole_heigth + cat_height_now;	//trenutni visini okvirja dodaj se visino trenutne kategorije
		trenutni_okvir.css({'height':(okvir_height)+'px'});	//visina trenutnega desnega okvirja
		//console.log('Koncna visina: '+(okvir_height));
	}else{	//drugace
		if (cat_height_now < 15){	//ce je visina trenutne kategorije odgovova manjsa od 15
			cat_height_now = 15;	//naj bo visina okvirja 15px, prej 20px
		}
		trenutni_okvir.css({'height':(cat_height_now)+'px'});	//visina trenutnega desnega okvirja
		//console.log('Koncna visina: '+(cat_height_now));
	}
	//************************************ konec - glede na visino trenutno prenesenih kategorij odgovora, povecaj visino okvirja
}

//funkcija za urejanje visine okvirjev
function frame_height(spremenljivka, vre_id, grd_id, revert, refresh){
	//console.log("frame_height");
	//***************** glede na visino trenutno prenesenih kategorij odgovora, povecaj visino okvirja
	//visina trenutno prenesenega odgovora*************
	var cat_default_height = 37;
	//var cat_height_now = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).outerHeight(true);	//visina trenutne kategorije odgovora
	if (vre_id != 0){
		var cat_height_now = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).outerHeight();	//visina trenutne kategorije odgovora
	}	
	//console.log("cat_height_now:"+cat_height_now);
	//*************************************************visina trenutno prenesenega odgovora - konec
	
	
	if (refresh == 1){	//visina trenutnih kategorij v okvirju, ce je refresh
		//var cat_height_now
		var visinaPrisotnihKategorij = 0;
		//console.log("grd_id:"+grd_id);
		$('#half2_frame_dropping_'+grd_id+'_'+spremenljivka).children('div').each(function () {
			var trenutnaVisinaKategorije = $(this).outerHeight(true);
			visinaPrisotnihKategorij = visinaPrisotnihKategorij + trenutnaVisinaKategorije;
		});
		var cat_height_now = visinaPrisotnihKategorij;
		//console.log("cat_height_now:"+cat_height_now);
	}
	
	var cat_right = $('#half2_frame_dropping_'+grd_id+'_'+spremenljivka).children('div').outerHeight(true);//belezi visino kategorije odgovora, ce je ta prisotna v trenutnem desnem okvirju
	
	//ce je v trenutnem desnem okvirju ze prisotna kategorija odgovora, trenutni visini okvirja dodaj se visino trenutne kategorije
	//if(cat_right || revert){
	if(cat_right || revert || refresh){
		var whole_heigth = $('#half2_frame_dropping_'+grd_id+'_'+spremenljivka).outerHeight();
		//console.log("whole_heigth:"+whole_heigth);
		//cat_height_now = cat_height_now + cat_margin_left; //izracun koncne visine desnega okvirja, ce imamo ze kategorije v okvirju
		//var okvir_height = parseInt(whole_heigth) + parseInt(cat_height_now);
		var okvir_height = whole_heigth + cat_height_now;
		$('#half2_frame_dropping_'+grd_id+'_'+spremenljivka).css({'height':(okvir_height)+'px'});	//visina desnega okvirja
		//console.log('Koncna visina: '+(okvir_height));
		
	}else{	//drugace
		//cat_height_now = cat_height_now + cat_default_height;	//izracun koncne visine desnega okvirja, ce ni kategorij v okvirju
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
	//console.log("last_frame_height");
	//****glede na odstrajeno kategorijo odgovora, trenutni visini okvirja odstrani visino odstranjene kategorije

	var trenutna_visina_okvirja = prejsnji_okvir.height();
	var trenutna_visina_kategorije = draggable.outerHeight(true);//visina odnesene kategorije odgovora
	koncna_visina_zapuscenega_okvirja = trenutna_visina_okvirja - trenutna_visina_kategorije;
	
	//console.log("trenutna_visina_okvirja:"+trenutna_visina_okvirja);
	//console.log("trenutna_visina_kategorije:"+trenutna_visina_kategorije);
	//console.log("koncna_visina_zapuscenega_okvirja:"+koncna_visina_zapuscenega_okvirja);
	
	
	if (koncna_visina_zapuscenega_okvirja < 15){
		koncna_visina_zapuscenega_okvirja = 15;
	}
	
	prejsnji_okvir.css({'height':(koncna_visina_zapuscenega_okvirja)+'px'});	//visina trenutnega desnega okvirja
}
//*************** konec - funkcija za urejanje visine zadnje obiskanega okvirja

//********* skrbi za koncno ureditev visine celotnega vprasanja glede na visino (levega ali desnega) bloka @ drag and drop
function dynamic_question_height_sub(frame_height, spremenljivka){
	//console.log("dynamic_question_height_sub za "+spremenljivka);
	
	//var default_var_height = $('#spremenljivka_'+spremenljivka).height();	//trenutna visina celotnega vprasanja
	var default_var_height = $('#spremenljivka_'+spremenljivka).outerHeight(true);	//trenutna visina celotnega vprasanja

	var naslov_height = $('#spremenljivka_'+spremenljivka+' .naslov').outerHeight(true);
	var visinaPaddingovMarginovVprasanja = $('#spremenljivka_'+spremenljivka).outerHeight(true) - $('#spremenljivka_'+spremenljivka).height();
	var visinaPaddingovMarginovHolder = $('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').outerHeight(true) - $('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').height();
	var pomoznoBesediloHeight = 40; //visina besedila "Razpoložljive kategorije:", za mobilne naprave
	
	var vmesnaVisinaOkvirja = realnaVisina(spremenljivka, frame_height);

	//if( (frame_height + naslov_height) > default_var_height){	//ce je koncna visina okvirja vecja od trenutne default visine celotnega vprasanja
	if( (vmesnaVisinaOkvirja) > default_var_height){	//ce je koncna visina okvirja vecja od trenutne default visine celotnega vprasanja
		updateHeight(spremenljivka, frame_height);
		//console.log("Spreminjam 1");
	}else if((vmesnaVisinaOkvirja) < default_var_height){	//ce je koncna visina okvirja manjsa od trenutne default visine celotnega vprasanja
		var koncnaVisinaVprasanja = $('#spremenljivka_'+spremenljivka).outerHeight(true);	//trenutna visina celotnega vprasanja;
		//console.log("koncnaVisinaVprasanja:"+koncnaVisinaVprasanja);
		updateHeight(spremenljivka, 0, koncnaVisinaVprasanja);

		$('#spremenljivka_'+spremenljivka).height(koncnaVisinaVprasanja);	//koncna visina celotnega vprasanja
		
		//da ne bo pri mobilnikih prevec skrito vprasanje, spremeni visino variable_holder
		$('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').height(koncnaVisinaVprasanja*0.85);
		//console.log("Spreminjam 2");
	}	
}

//***********  ureja dinamicno visino celotnega vprasanja glede na visino prenesenih kategorij odgovorov iz desnih okvirjev v levega
function dynamic_question_height(spremenljivka, num_grids, mobile, skatle){
	//console.log("dynamic_question_height za:"+spremenljivka);
		
/* 	if (mobile == 0 || mobile == 2){
		var title_heigth = $('.frame_dropping_titles').outerHeight(true);
	}else{
		var title_heigth = $('.frame_dropping_titles_mobile').outerHeight(true);
	} */
	if (mobile == 0 || mobile == 2){
		if(skatle){
			var title_heigth = $('.frame_dropping_titles_box').outerHeight(true);
		}else{
			var title_heigth = $('.frame_dropping_titles').outerHeight(true);
		}				
	}else{
		if(skatle){
			var title_heigth = $('.frame_dropping_titles_box_mobile').outerHeight(true);
		}else{
			var title_heigth = $('.frame_dropping_titles_mobile').outerHeight(true);
		}
		
	}
	
	var final_height_right_block = 0;	//hrani koncno visino desnega bloka, torej vseh prisotnih okvirjev
	var frame_height_left = $('#half_frame_dropping_'+spremenljivka).outerHeight(true);	//visina celotnega levega okvirja
	//console.log('Koncna visina levega bloka: '+frame_height_left);

	//pridobi visino desnega bloka
	for(var j = 1; j <= num_grids; j++){	//preglej vse okvirje na desni strani		
		var okvir_height = $('#half2_frame_dropping_'+j+'_'+spremenljivka).outerHeight(true); //visina trenutnega okvirja				
		final_height_right_block = final_height_right_block + okvir_height + title_heigth;	//vmesna visina desnega okvirja
	}
	//console.log('Koncna visina desnega bloka: '+final_height_right_block);
	//pridobi visino desnega bloka - konec
	
	var default_var_height = $('#spremenljivka_'+spremenljivka).height();	//trenutna visina celotnega vprasanja
	//console.log("default_var_height: "+default_var_height);
	
	var vmesnaVisinaVprasanjaLevo = realnaVisina(spremenljivka, frame_height_left);
	var vmesnaVisinaVprasanjaDesno = realnaVisina(spremenljivka, final_height_right_block);
	
	//ce je visina celotnega vprasanja manjsa od okvirja/bloka (levi ALI desni)
	if( (default_var_height < (vmesnaVisinaVprasanjaLevo)) || (default_var_height < (vmesnaVisinaVprasanjaDesno)) ){
		if(frame_height_left < final_height_right_block){
			//console.log("Levi manjsi od desnega");
			dynamic_question_height_sub(final_height_right_block, spremenljivka);	//glede na visino desnega bloka uredi velikost celotnega vprasanja
		}else if(frame_height_left > final_height_right_block){
			//console.log("Levi vecji od desnega");
			dynamic_question_height_sub(frame_height_left, spremenljivka);	//glede na visino levega bloka uredi velikost celotnega vprasanja
		}
	}else if (frame_height_left < final_height_right_block){
		updateHeight(spremenljivka, final_height_right_block);	//posodobi visino vprasanja
		//console.log("Spreminjam A1");
	}else if (frame_height_left > final_height_right_block){
		updateHeight(spremenljivka, frame_height_left);	//posodobi visino vprasanja
		//console.log("Spreminjam A2");
	}
}

//posodobi visino celotnega vprasanja in variable_holder
function updateHeight(spremenljivka, final_height_block, visinaVprasanja){
	
	if(visinaVprasanja)	{var koncnaVisinaVprasanja = visinaVprasanja;}
	else                {var koncnaVisinaVprasanja = realnaVisina(spremenljivka, final_height_block);}
	
	//ureditev visine celotnega vprasanja
	$('#spremenljivka_'+spremenljivka).height(koncnaVisinaVprasanja);
	
	//da ne bo pri mobilnikih prevec skrito vprasanje, spremeni visino variable_holder
	$('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').height(koncnaVisinaVprasanja*0.85);	
}

//vrne realno visino vprasanja/bloka
function realnaVisina(spremenljivka, final_height_block){
	var naslov_height = $('#spremenljivka_'+spremenljivka+' .naslov').height();
	var visinaPaddingovMarginovVprasanja = $('#spremenljivka_'+spremenljivka).outerHeight(true) - $('#spremenljivka_'+spremenljivka).height();
	var visinaPaddingovMarginovHolder = $('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').outerHeight(true) - $('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').height();
	var pomoznoBesediloHeight = 40; //visina besedila "Razpoložljive kategorije:"
	
	var realnaVisina = final_height_block + naslov_height + pomoznoBesediloHeight + visinaPaddingovMarginovVprasanja + visinaPaddingovMarginovHolder*4;
	
	return realnaVisina;	
}

//vrne visino/pozicijo prenesene kategorije v desnem skatlastem okvirju
function calcPravaVisina(tole, draggable, indeks, spremenljivka, refresh, zapStevKategorije, revert){
	//console.log("refresh:"+refresh);
	
	var visinaDesnegaOkvirja = $(tole).outerHeight();						
	//console.log("visinaDesnegaOkvirja:"+visinaDesnegaOkvirja);
	var visinaPreneseneKategorije = 0;
	if(draggable){
		visinaPreneseneKategorije = $(draggable).outerHeight(true);
	}
	
	//console.log("visinaPreneseneKategorije:"+visinaPreneseneKategorije);
	
	var visinaPrisotnihKategorij = 0;
	var steviloKategorij = 0;
	$(tole).children('div').each(function () {
		var trenutnaVisinaKategorije = $(this).outerHeight(true);
		visinaPrisotnihKategorij = visinaPrisotnihKategorij + trenutnaVisinaKategorije;
		steviloKategorij++;
	});
	//console.log("steviloKategorij:"+steviloKategorij);
	//console.log("visinaPrisotnihKategorij:"+visinaPrisotnihKategorij);
	//console.log("zapStevKategorije:"+zapStevKategorije);
	
	//ce je refresh ali revert
	if( refresh == 1 || revert == 1){
		if (visinaDesnegaOkvirja < visinaPrisotnihKategorij){ //ce je visina desnega okvirja manjsa od visine prisotnih kategorij
			//console.log("visinaDesnegaOkvirja < visinaPrisotnihKategorij");
			if(refresh == 1){
				$('#half2_frame_dropping_'+indeks+'_'+spremenljivka).css({'height':(visinaPrisotnihKategorij)+'px'});	//visina desnega okvirja
			}
			visinaDesnegaOkvirja = $(tole).outerHeight();
			//console.log("visinaDesnegaOkvirja po refresh:"+visinaDesnegaOkvirja);
		}

		if(steviloKategorij > 1){
			var pravaVisina = visinaDesnegaOkvirja - visinaPrisotnihKategorij;
		}else{
			var pravaVisina = visinaDesnegaOkvirja - visinaPreneseneKategorije;
		}		
	}else{
		var pravaVisina = visinaDesnegaOkvirja - visinaPrisotnihKategorij - visinaPreneseneKategorije;
	}
	
	//console.log("pravaVisina za sprem "+spremenljivka+" :"+pravaVisina);
	//console.log("-------------------------");
	return pravaVisina;
}

//urejanje visine na kateri se nahaja gumb za ponastavljanje vprašanja
function ResetButtonHeight(spremenljivka){				
	$('#resetDragDrop_'+spremenljivka).position({
	  my: "left bottom",
	  at: "left bottom",			  
	  of: "#spremenljivka_"+spremenljivka,
	  collision: 'none'
	});
	
	var currentTop = $('#resetDragDrop_'+spremenljivka).css('top');			
	var newTop = parseInt(currentTop) - 20;			
	$('#resetDragDrop_'+spremenljivka).css('top', newTop+'px');
}