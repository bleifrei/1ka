//funkcije za gladko delovanje prikazovanja visjih blokov odgovorov @ rangiranje (razvrščanja) (tip = 17)

function UrediOkvir(vre_id){
	custom_image_view(vre_id);	//ureditev visine okvirja glede na visino slike

	$('#vre_id_'+vre_id)
		.mousemove(function(){// //ko se miska premakne
			custom_image_view(vre_id);	//ureditev visine okvirja glede na visino slike
	})
		.keyup(function(){ //ko se dvigne prste iz tipk
			custom_image_view(vre_id);	//ureditev visine okvirja glede na visino slike in dolzine teksta
	})
}


function custom_image_view(vre_id){
	
	var default_cat_height = 15;
	var final_height = 0;
	
	var content = $('#vre_id_'+vre_id).html();
	content = content.replace(/<img[^>]*>/gi,"");	//odstrani tekst med img tag-i, da se v dolzini besedila ne steje tudi html koda slike
	
	var cat_text_length = content.length;	//hrani stevilo znakov, ki so vpisani v trenutni kategoriji odgovora
	//console.log('Število znakov v kategoriji: '+cat_text_length);
	
	var num_of_br = $('#vre_id_'+vre_id+' br').length;	//hrani stevilo br oz. ročnih vnosov novih vrstic
	//console.log('Število br v kategoriji: '+num_of_br);
	
	var num_imgs = $('#vre_id_'+vre_id+' img').length; //hrani stevilo img v interesiranem div-u
	//console.log('Število slik v kategoriji: '+num_imgs);
	
	var max_cat_text_length = 30; //hrani max stevilo dolzine teksta za eno vrstico
	
	var oneLineHeight = 20;
	

	if( (cat_text_length > max_cat_text_length) ){//ce je tekst daljsi od 30 znakov
		//final_height = final_height + default_cat_height + 25;
		var kvocient = Math.ceil(cat_text_length/max_cat_text_length);
		final_height = final_height + kvocient*oneLineHeight;		
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
	
	
 	if(num_of_br != 0){	//ce imamo vnesene rocne skoke v novo vrstico </br>
		var br_height = num_of_br*oneLineHeight;
		final_height = final_height + br_height;
	}
	
	if (final_height != 0){
		$('#vre_id_'+vre_id).css({'height':final_height});	//dodaj atributu style še novo višino
	}
	//console.log("final_height: "+final_height+" za :"+vre_id);
}

//*********** funkcija za resize slik, ko so te vecje od 260 px
function ranking_image_resize_admin(vre_id){
	//console.log("ranking_image_resize_admin vre_id: "+vre_id);
 	//var max_width = 220;
 	var max_width = $('.ranking').width();
	
	//vre_id_24194
	var img = $('#vre_id_'+vre_id+' img');
	var num_imgs = img.length; //hrani stevilo img v interesiranem div-u
	if(num_imgs != 0){
		var width = img.width();
		var height = img.height();		
		if (width > max_width){
			height = (height / width) * max_width;
			$('#vre_id_'+vre_id).children('img').css({width: max_width});
			$('#vre_id_'+vre_id).children('img').css({height: height});
			//$('#vre_id_'+vre_id).css({height: height});
			return height;
		}
	}


}
//*********** konec - funkcija za resize slik, ko so te vecje od 260 px

//******************** za predogled
//funkcije za gladko delovanje prikazovanja visjih blokov odgovorov @ rangiranje (razvrščanja) (tip = 17)
//function customizeImageView4Respondent(tip, spremenljivka, vre_id, ajax, anketa, site_url, usr_id, other, mobile, num_grids){
function customizeImageView4Respondent(tip, spremenljivka, vre_id, ajax, anketa, site_url, usr_id, other, mobile, quick_view, preview_spremenljivka){
	
	
	var top_cat = -1;
	var left_cat = -1;
	if (mobile == 0 || mobile == 2){
		top_cat = -6;
		left_cat = -6;
	}
		//console.log("quick_view:"+quick_view);
		//console.log("preview_spremenljivka:"+preview_spremenljivka);
		
		if(quick_view || preview_spremenljivka){	//ce je le predogled
			$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).draggable( "option", "disabled", true );	//disable-anje drag and drop
			//console.log("Disabled vre_id:"+vre_id);
		}
	
		//ureditev visine kategorije (div) glede na prisotnost slike ali vecvrsticnega teksta **************************************************
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
		//ureditev visine kategorije (div) glede na prisotnost slike ali vecvrsticnega teksta - konec********************************************
		
		//ureditev visine celotnega vprasanja****************************************************************************************************
		
		//dynamic_question_height_ranking(spremenljivka, num_grids);
		
		//ureditev visine celotnega vprasanja - konec*******************************************************************************************
}

//funkcija, ki ureja visina celotnega vprasanja glede na skupno visino kategorij v okvirjih
function question_height_ranking(spremenljivka){	
	var final_height_kategorije_left = final_height_kategorije_right = final_height = tmp_height_kategorije_left = tmp_height_kategorije_right =  naslov_height = 0;
	var i = 1;
	var j = 1;
	
	var default_var_height_now = $('#spremenljivka_'+spremenljivka).height();
	
	$('#half_'+spremenljivka).children('div').each(function(){	//preleti leve kategorije
		tmp_height_kategorije_left = $(this).height();
		//console.log("tmp_height_kategorije_left "+i+": "+tmp_height_kategorije_left);
		i++;
		final_height_kategorije_left = final_height_kategorije_left + tmp_height_kategorije_left;
	});
	
	$('#half2_'+spremenljivka).children('div').each(function(){	//preleti desne kategorije
		tmp_height_kategorije_right = $(this).height();
		//console.log("tmp_height_kategorije_right "+j+": "+tmp_height_kategorije_right);
		j++;
		final_height_kategorije_right = final_height_kategorije_right + tmp_height_kategorije_right;
	});
	
	if(final_height_kategorije_left > final_height_kategorije_right){
		final_height_kategorije = final_height_kategorije_left;
	}else{
		final_height_kategorije = final_height_kategorije_right;
	}
	
	//console.log("final_height_kategorij: "+final_height_kategorije);
	
	naslov_height = $('#spremenljivka_'+spremenljivka+' .naslov').height();
	//console.log("naslov_height: "+naslov_height);
	
	final_height = naslov_height + final_height_kategorije + 120;
	
	if(final_height > default_var_height_now){	//ce je nova visina vecja od default zacetne
		$('#spremenljivka_'+spremenljivka).height(final_height);
		$('#spremenljivka_'+spremenljivka+' div.variable_holder.clr').height(final_height + 25);
	}
	//console.log("default_var_height_now: "+default_var_height_now);
	//console.log("final_height: "+final_height);	
}

function frame_height_ranking_premikanje_dyn (ui, spremenljivka){
	var trenutna_visina_prenesenega = ui.item.height();
	var trenutni_okvir = $('#sortzone_'+spremenljivka);
	var stevilo_prisotnih = trenutni_okvir.children('div').length;
	var iscem = 'vrednost_';
	var tmp = ui.item.attr('id');
	//var pos = tmp.search(iscem);
	//var vre_id = tmp.slice(pos);
	var vre_id = tmp.slice(tmp.search(iscem));
	vre_id = vre_id.replace(iscem, '');
	//console.log("Trenutni indeks: "+ui.item.index());
	var sortedID = trenutni_okvir.sortable( "toArray" );	//trenutni vrstni red odgovorov v polje
	
	var i = 0;
	trenutni_okvir.siblings().children('li').children('div').each(function(){	//preleti okvirje
		var visina = $('#'+sortedID[i]).height();	//visina i-tega odgovora
		//console.log("Visina odgovora "+i+" : "+$('#'+sortedID[i]).height());		
		$(this).height(visina);	//visina trenutnega okvirja naj bo enaka visini ustreznega odgovora
		i = i + 1;
	});
}

function frame_height_ranking_premikanje (spremenljivka, vre_id, vrstni_red){
	var trenutna_visina_prenesenega = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).height();
	//console.log(trenutna_visina_prenesenega);
	var i = 0;
	$('#sortzone_'+spremenljivka).siblings().children('li').children('div').each(function(){	//preleti prazne okvirje
		i = i + 1;
		if(vrstni_red == i){
			$(this).height(trenutna_visina_prenesenega);
			//console.log(i);
		}
	});
}

//************ za pravilno velikost slik
//*********** funkcija za resize slik, ko so te vecje od max_width
function ranking_image_resize(spremenljivka, vre_id){
	
 	//var max_width = 220;
 	var max_width = $('.ranking').width();
 	
	//spremenljivka_4582_vrednost_24243
	
	var img = $('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id+' img');
	var num_imgs = img.length; //hrani stevilo img v interesiranem div-u
	if(num_imgs != 0){
		var width = img.width();
		var height = img.height();		
		if (width > max_width){
			height = (height / width) * max_width;
			img.css({width: max_width});	//sirina slike
			img.css({height: height});	//visina slike
			$('#spremenljivka_'+spremenljivka+'_vrednost_'+vre_id).css({height: height}); //visina okvirja
		}
	}
}
//*********** konec - funkcija za resize slik, ko so te vecje od max_width

//*************************************** za pravilno velikost slik - konec

//******************** za predogled - konec