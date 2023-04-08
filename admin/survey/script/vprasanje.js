/*
* V tej datoteki naj bodo vse javascipt zadeve za novo - fullscreen urejanje vprasanja
*/

__refresh_on_close = 0;		// ce ob zapiranju popupa vprasanja refreshamo celo stran


function onload_init_vprasanje() {
	$('#vprasanje_float_editing').bind('click', function (event) {
        vprasanje_float_editing_click(event);
    });
}
// prikaze fullscreen urejanje vprasanja
function vprasanje_fullscreen (spremenljivka, cache, chart_edit, no_close) {
	if (locked) return;
	
	//za ureditev custom opisnih label pri drsniku
	switchSliderOpisneLabeleEditMode(spremenljivka, '');
	//za ureditev custom opisnih label pri drsniku - konec
	
	// Preverimo ce je samo vprasanje zaklenjeno
	if($('#spremenljivka_content_'+spremenljivka).hasClass('question_locked')){
		return;
	}
	
	if ( ! (no_close==true) )
		close_all_editing();
	
	// zapremo knjiznico
	$('#toolbox_library').hide();
	
	// ce smo v formi zapremo hitre nastavitve desno zgoraj
	$('#form_settings_obvescanje').hide();
    $('#email_switch').attr("src", "img_0/plus.png");
	$('#obvescanje_switch').attr("src", "img_0/plus.png");
	
	var id;
	if (spremenljivka > 0)
		id = '#branching_'+spremenljivka;
	else
		id = '#'+spremenljivka;
		
	
    $('#branching li.spr_editing').removeClass('spr_editing');
    
	$(id).addClass('spr_editing');
	
	$('#vprasanje_float_editing').html('').show().css('visibility', 'hidden');	// da delajo moseover dropdowni v IE8 mora bit ze tuki show(), potem pa skrijemo z visibility
	
	if (cache == undefined) {
		$.post('ajax.php?t=vprasanje&a=vprasanje_fullscreen', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id}, function(data) {
		
			$('#vprasanje_float_editing').html(data).css('visibility', 'visible');
			
			$('#vprasanje_edit form select, #vprasanje_edit form input, #vprasanje_edit form textarea').not('.no_submit').change(function () {
				vprasanje_save(true);
			});
			
			// odpremo okno za dodajanje nove kategorije
			var last = $('#branching_'+spremenljivka+' .variabla:last-child div.vrednost_inline');
			inline_nova_vrednost(last);
	
			//Preklop na tab za urejanje label grafov
			if(chart_edit == 1){ vprasanje_tab(spremenljivka, 4); }
			
			vprasanje_pozicija(spremenljivka);
		});
	} else {
		
		$('#vprasanje_float_editing').html(cache).css('visibility', 'visible');
		
		$('#vprasanje_edit form select, #vprasanje_edit form input, #vprasanje_edit form textarea').not('.no_submit').change(function () {
			vprasanje_save(true);
		});
		
		// odpremo okno za dodajanje nove kategorije
		var last = $('#branching_'+spremenljivka+' .variabla:last-child div.vrednost_inline');
		inline_nova_vrednost(last);
	
		vprasanje_pozicija(spremenljivka);
		
	}
		
	// pri skrcenem nacinu moramo se prikazat polni predogled vprasanja
	if ($('#branching').hasClass('collapsed')) {
        
        if (spremenljivka > 0) {
			$('#branching_'+spremenljivka).load('ajax.php?t=branching&a=vprasanje_full', {spremenljivka: spremenljivka, anketa:srv_meta_anketa_id},
			function () {
				vprasanje_pozicija(spremenljivka);
			});
        } 
        else {
			$('#'+spremenljivka).load('ajax.php?t=branching&a=vprasanje_full', {spremenljivka: spremenljivka, anketa:srv_meta_anketa_id},
			function () {
				vprasanje_pozicija(spremenljivka);
			});
		}
	}
}

/**
*  nastavi pozicijo float boxa, da se ujema z vprasanjem 
*  in zascrolla okno, da je oboje lepo na strani
*/
function vprasanje_pozicija (spremenljivka) {
	
	var id;
	if (spremenljivka > 0)
		id = '#branching_'+spremenljivka;
	else
		id = '#'+spremenljivka;

	var elTop = $(id).position().top;
	var elHeight = $(id).height();
	
	//$('#vprasanje_float_editing').css('top', elTop-163).show();
	$('#vprasanje_float_editing').css('margin-top', elTop-163).show();
	
	var floatingTop = $('#vprasanje_float_editing').offset().top - 40;	// ker je premaknjeno navzgor
	var floatingHeight = $('#vprasanje_float_editing').height() + 40;
	
	
	var top = floatingTop;
	var height = elHeight;
	if (floatingHeight > height) height = floatingHeight;
	
	var windowHeight = $(window).height();
	var scrollTop = $('html, body').scrollTop();
	
	
	if ( top < scrollTop ) {									// zgornji rob
	
		$('html, body').animate({scrollTop: top-20 });			// 20 za mal prostora
	
	} else if ( (top+height) > (windowHeight+scrollTop) ) {		// spodnji rob
		
		var scroll = top+height-windowHeight+20;				// 20 za mal prostora
		if ( top < scroll ) scroll = top;						// ce je box vecji od zaslona, pozicioniramo zgornji rob
		$('html, body').animate({scrollTop: scroll });
	}
	
}

// zamenja tab pri urejanju vprasanja
function vprasanje_tab (spremenljivka, tab) {
	
	$('.tab', '#vprasanje_edit').hide();
	$('#tab_'+tab).show();
	
	$('.tab_link', '#vprasanje_tabs').removeClass('active');
	$('#tab_link_'+tab).addClass('active');
	
}

// shrani nastavitve vprasanja
function vprasanje_save (silentsave, spr, callback) {
	
	var spremenljivka = $('input[name=spremenljivka]').val() || spr;  // spr se prenese, ce je urejanje na desni zaprto

	if (spremenljivka == undefined) return;
	
	// skrijemo opcijo za dodajanje nove vrednosti
	if (silentsave != true)
		$('#spremenljivka_content_'+spremenljivka+' #variabla_new').hide();
	
	inline_save_editor(spremenljivka);
	
	// shranimo tudi komentar, ce ga je slucajno vpisal in ni pritisnil potrdi
	if ($('#vsebina_'+spremenljivka+'_3').val() != '')
		add_comment(spremenljivka, '1', '3', $('#vsebina_'+spremenljivka+'_3').val());
	
	var id;
	if (spremenljivka > 0)
		id = '#branching_'+spremenljivka;
	else
		id = '#'+spremenljivka;
		
	var form_serialize = $("form[name=vprasanje_edit]").serialize() || {spremenljivka: spremenljivka};
	
	if (silentsave != true) {
        $('#calculation').fadeOut('fast').html('');
        
        $('#vprasanje_float_editing').hide().html('');
        
		$(id).removeClass('spr_editing');
		
		// prikazemo knjiznico ce je odprta
		$('#toolbox_library').show();
	}
	
	$.post('ajax.php?t=vprasanje&a=vprasanje_save&silentsave='+silentsave, form_serialize, function (data) {
		
		if (silentsave != true) {
		
			// normalno sejvanje v urejanju
			if (__refresh_on_close == 0) {		
				$(id).html(data);
            } 
            // ob sejvanju refreshamo celo stran
            else {
				window.location.reload();
			}
			
        } 
        else {

			$(id).html(data);
			
			// odpremo okno za dodajanje nove kategorije
            var last = $('#branching_'+spremenljivka+' .variabla:last-child div.vrednost_inline');
            
			inline_nova_vrednost(last);
		}
		
        if (typeof callback == 'function') { 
            callback(); 
        }
	});
	
}

// preklici nastavitve vprasanja
function vprasanje_cancel () {

	var spremenljivka = $('input[name=spremenljivka]').val();

	$('#calculation').fadeOut('fast').html('');	
	$('#vprasanje').hide();
	$('#fade').fadeOut('slow');
	$('#branching_'+spremenljivka).removeClass('spr_editing');
	
	remove_editor('naslov');
	
	// vprasanje vseeno refreshamo tudi pri cancel gumbu, ker se ene stvari (vrednosti -- zaenkrat) se vedno delajo z ajaxom in se sproti shrani. to se lahko odstrani, ko ne bo slo nic vec preko ajaxa
	$.post('ajax.php?t=vprasanje&a=vprasanje_save', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id}, function (data) { 
		
		// normalno sejvanje v urejanju
		if (__refresh_on_close == 0) {
			$('#branching_'+spremenljivka).html(data);
			$('#vprasanje').html('');
		// ob sejvanju refreshamo celo stran
		} else {
			window.location.reload();
		}	
			
	});
	
}

// pobrise vrednost (v srv_vrednost)
function vrednost_delete(spremenljivka, vrednost, tip, other) {
	
	$('#vrednost_'+vrednost).remove();
	// popravimo še čhekbox za missinge
	$("#missing_value_"+other).attr('checked', false);
	$.post('ajax.php?t=vprasanje&a=vrednost_delete', {spremenljivka: spremenljivka, vrednost: vrednost, other: other, anketa: srv_meta_anketa_id}, function() {
		if(tip == 17) edit_ranking_moznosti();	
	});
}

// doda novo vrednost (mv = missing value)
function vrednost_new (spremenljivka, other, tip, mv) {
	
	$.post('ajax.php?t=vprasanje&a=vrednost_new', {spremenljivka: spremenljivka, other: other, anketa: srv_meta_anketa_id, mv:mv}, function (data) {
		$('ul.vrednost_sort', '#vprasanje_edit #tab_0').append(data);
		$('ul.vrednost_sort li:last-child textarea' ,'#vprasanje_edit #tab_0').focus();
		$('#vprasanje_edit').attr({scrollTop: $('#vprasanje_edit').height()});
		if(tip == 17) edit_ranking_moznosti();
		
		vprasanje_save(true, spremenljivka);
	});
	
}

// doda novo vrednost na mobitelu
function vrednost_new_mobile (spremenljivka, tip) {
	
	$.post('ajax.php?t=vprasanje&a=vrednost_new', {spremenljivka: spremenljivka, other: 0, anketa: srv_meta_anketa_id, mv:0}, function (data) {

        vprasanje_save(true, spremenljivka, function(){
            $('#spremenljivka_contentdiv'+spremenljivka).find('#variable_holder div:last-child').find('.vrednost_inline').focus();
        });        
	});
}

//preverimo ce ze obstaja 
function vrednost_new_dodatne (spremenljivka, mv, tip, checked){
	var vrednost;
		
	if(checked == true){
		vrednost_new(spremenljivka, mv, tip, mv);
	} else{
		
		var vre_id = $('#spremenljivka_content_'+spremenljivka+' #variable_holder [other|="'+mv+'"]').attr('id');
		vre_id = vre_id.replace('variabla_', '');
		inline_vrednost_delete(spremenljivka, vre_id, '0');
        
		return false;
	}
}

//popravljanje dropdowna moznosti pri razvrscanju ko dodamo/brisemo vrednost
function edit_ranking_moznosti(){
		
	//prestejemo vrednosti
	var counter = 0;
	var inputs = document.getElementsByTagName('textarea');
	
	for(var i=0; i < inputs.length; i++)
	{
		if(inputs[i].getAttribute('name').toLowerCase().substr(0,15) == 'vrednost_naslov')
			counter++;
	}
	
	//na novo izrisemo dropdown
	var value = $('select[name=ranking_k]').val();
	var html = '';
	
	html = html + '<option value="0"' + (value == 0 ? ' selected="true"' : '') + '>'+lang['srv_vsi']+'</option>';
	for (var i=1; i<counter; i++) {
		html = html + '<option value="' + i + '"' + (value == i ? ' selected="true"' : '') + '>' + i + '</option>';
	}
	
	$('.ranking_k').html(html);
}

function vrednost_edit (vrednost) {
	
	// Zakaj moramo shranit vprasanje pri odpiranju edit popupa? Ce je tole omogoceno, je tezava zaradi brisanja default vrednosti (recimo ce po inline urejanju prvega poskusis urejati 2. vrednosti)
	//vprasanje_save(true);
	
	$('#fade').fadeTo('slow', 1);
	$('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?t=vprasanje&a=vrednost_edit', {vrednost: vrednost, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id}, 
		function () {
			//create_editor('vrednost_naslov');
		}
	);
}

// Hitro nalaganje slike - V DELU
function vrednost_insert_image (vrednost, create_new) {
	
	// Ce smo kliknili na novo vrednost jo najprej ustvarimo
	if (create_new) {
		
		var div = $('#variabla_'+vrednost);
		
		var spr_id = div.closest('.spremenljivka_content').attr('spr_id');
		div.attr('new', 'waiting');
		$.post('ajax.php?t=vprasanjeinline&a=inline_vrednost_new', {spremenljivka: spr_id, anketa: srv_meta_anketa_id}, function (data) {

			vprasanje_save(true);
			
			$('#fade').fadeTo('slow', 1);
			$('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?t=vprasanje&a=vrednost_insert_image', {vrednost: data, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id}, 
				function () {
					create_editor_hitro_nalaganje_slike('hitro-nalaganje-slike');
				}
			);

			// Skrijemo popum modal, da prikaže samo ckeditor dialog box
			$('#vrednost_edit').css('position', 'fixed').css('top', -800);
		});
	}
	else{
        vprasanje_save(true);
        
        $('#fade').fadeTo('slow', 1);
        
		$('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?t=vprasanje&a=vrednost_insert_image', {vrednost: vrednost, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id}, 
			function () {
				create_editor_hitro_nalaganje_slike('hitro-nalaganje-slike');
			}
		);

		// Skrijemo popum modal, da prikaže samo ckeditor dialog box
		$('#vrednost_edit').css('position', 'fixed').css('top', -800);
	}
}

function vrednost_insert_image_save () {

	// probamo prebrat iz editorja, ce je bil nalozen
	get_editor_close('hitro-nalaganje-slike');

	var vrednost = $('input[name=vrednost]').val();

	$('#fade').fadeOut('slow');
	$('#vrednost_edit').fadeOut('slow');
	

	$.post('ajax.php?t=vprasanje&a=vrednost_save', $("form[name=vrednost_insert_image_form]").serialize(), function (data) {
		$('#vre_id_'+vrednost).html(data);
		$('#vrednost_edit').html('');
		
		vprasanje_save(true);	
		
		var spremenljivka = $('#vre_id_'+vrednost).closest('.spremenljivka_content').attr('spr_id');
		vprasanje_pozicija(spremenljivka);
	});

}

function hotspot_edit (spr_id) {
	//vprasanje_save(true);
	$('#fade').fadeTo('slow', 1);
	$('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?t=vprasanje&a=hotspot_edit', {spr_id: spr_id, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id}, 
		function () {
			create_editor_hotspot('hotspot_image');
		}
	);
}

// shrani nastavitve vrednosti
function vrednost_save () {
	
	// probamo prebrat iz editorja, ce je bil nalozen
	get_editor_close('vrednost_naslov');
	
	var vrednost = $('input[name=vrednost]').val();
	
	var red = false
	if($('#alert_show_99_popup').is(':checked')){
		var red = true;
	}
	
	$('#fade').fadeOut('slow');
	$('#vrednost_edit').fadeOut('slow');
			
	$.post('ajax.php?t=vprasanje&a=vrednost_save', $("form[name=vrednost_edit]").serialize(), function (data) { 
		
		$('#vre_id_'+vrednost).html(data);
		$('#vrednost_edit').html('');
		
		if(red == true){
			$('#vre_id_'+vrednost).addClass('red');
			$('#alert_show_99').attr('checked', true, function(){
				vprasanje_save(true);
			});
		}
		else{
			$('#vre_id_'+vrednost).removeClass('red');
			$('#alert_show_99').attr('checked', false, function(){
				vprasanje_save(true);
			});
		}	
	});
	
}

// shrani text vrednosti pri prevajanju
function vrednost_save_lang () {
	
	// probamo prebrat iz editorja, ce je bil nalozen
	get_editor_close('vrednost_naslov');
	
	$('#fade').fadeOut('slow');
	$('#vrednost_edit').hide();
				
	var spremenljivka = $('form[name=vrednost_edit] input[name=spremenljivka]').val();
	var vrednost = $('form[name=vrednost_edit] input[name=vrednost]').val();
	var naslov = $('form[name=vrednost_edit] textarea[name=vrednost_naslov]').val();
	
	$.post('ajax.php?t=vprasanjeinline&a=inline_vrednost_naslov_save', {spremenljivka:spremenljivka, vrednost: vrednost, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, naslov: naslov}, function (data) {
		
		$('div[vre_id='+vrednost+'][contenteditable=true].vrednost_inline').html(data);
	} );
	
}

// preklici nastavitve vprasanja
function vrednost_cancel () {
	
	remove_editor('vrednost_naslov');
	
	$('#fade').fadeOut('slow');
	$('#vrednost_edit').hide();
}

function vrednost_fastadd (spremenljivka) {
	
	$('#fade').fadeTo('slow', 1);
	$('#vrednost_edit').show().load('ajax.php?t=vprasanje&a=vrednost_fastadd', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id});
}

// doda nove vrednosti preko fast adda
function vrednost_fastadd_save () {
	
	$('#fade').fadeOut('slow');
	$('#vrednost_edit').hide();
	
	$.post('ajax.php?t=vprasanje&a=vrednost_fastadd_save', $("form[name=vrednost_fastadd_form]").serialize(), function (data) {

		$('#vrednost_edit').html('');
		vprasanje_save(true);
	});
}

function vprasanje_refresh (spremenljivka, silentsave) {
	
	$('#branching_'+spremenljivka).load('ajax.php?t=vprasanje&a=vprasanje_refresh&silentsave='+silentsave, {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id});
}

// prikaze/skrije opcijo za moznosti pri rankingu
function show_ranking_k (value) {
	
	if(value < 2)
		document.getElementById('ranking_k').style.display = 'block';
	else
		document.getElementById('ranking_k').style.display = 'none';	
}

// prikaze/skrije opcijo za nastavitev timerja
function show_timer (value) {

	$('#timer').toggle();
}

// prikaze/skrije opcijo dostop do vprasanja (samo admin, manager...) - samo ce je vprasanje vidno
function show_dostop (value) {

	$('#dostop').toggle();
}

function change_number(id1, id2) {
	
	var size = document.getElementById('num_size').value;
	var enota = document.getElementById('num_enota').value;
	
	var taWidth = document.getElementById('width').value;
	if (taWidth == -1)
		taWidth = 10;
	
	var html = '';	
		
	if (enota == 1 || enota == 2) {
		
		if (enota == 2)
			html = html + '<input type="text" style="width: ' + taWidth + 'em;" disabled="disabled"> ';

		if ($('input[name=vrednost_naslov_'+id1+']').length > 0) {
			var value = $('input[name=vrednost_naslov_'+id1+']').val();
		} else {
			var value = '';
		}
		html = html + '<input type="text" name="vrednost_naslov_' + id1 + '" value="' + value + '" />';

		if (enota == 1)
			html = html + ' <input type="text" style="width: ' + taWidth + 'em;" disabled="disabled">';

		//izpis dodatnega polja
		if (size == 2) {
			html = html + '&nbsp&nbsp&nbsp&nbsp';

			if (enota == 2)
				html = html + '<input type="text" style="width: ' + taWidth + 'em;" disabled="disabled"> ';
			
			if ($('input[name=vrednost_naslov_'+id2+']').length > 0) {
				var value = $('input[name=vrednost_naslov_'+id2+']').val();
			} else {
				var value = '';
			}
			html = html + ' <input type="text" name="vrednost_naslov_' + id2 + '" value="' + value + '" />';
			
			if (enota == 1)
				html = html + '<input type="text" style="width: ' + taWidth + 'em;" disabled="disabled">';
		}
	}
	else {
		html = html + '<input type="text" style="width: ' + taWidth + 'em;" disabled="disabled">';

		if (size == 2) {
			html = html + '&nbsp&nbsp&nbsp&nbsp';
			html = html + '<input type="text" style="width: ' + taWidth + 'em;" disabled="disabled">';
		}
	}
	
	$('#number').html(html);
}

function toggle_num_limits(size){
	
	// Ugasnemo limite za 2. polje
	if(size == 1){
		$("#num_limit2").hide();
		$("#num_limit_label").hide();
	}
	// Prizgemo limite za 2. polje
	else{
		$("#num_limit2").show();
		$("#num_limit_label").show();
	}
}

function change_subtype_number (spremenljivka) {
	
	//$.post('ajax.php?t=vprasanje&a=change_subtype_number', {spremenljivka: spremenljivka, ranking_k: $('#spremenljivka_podtip_'+spremenljivka).val(), anketa: srv_meta_anketa_id}, function () {
	$.post('ajax.php?t=vprasanje&a=change_subtype_number', {spremenljivka: spremenljivka, ranking_k: $('input:radio[name=ranking_k]:checked').val(), anketa: srv_meta_anketa_id}, function () {
		$('#vprasanje_float_editing').hide().html('');
		vprasanje_fullscreen(spremenljivka);
	});
}
function change_tip(spremenljivka, tip) {
	
	$.post('ajax.php?t=vprasanje&a=change_tip', {spremenljivka: spremenljivka, tip: tip, anketa: srv_meta_anketa_id}, function (data) {
		vprasanje_fullscreen(spremenljivka, data, false, true);
		vprasanje_save(true);
	});
}

function change_demografija(spremenljivka, podtip) {
	
	$.post('ajax.php?t=vprasanje&a=change_demografija', {spremenljivka: spremenljivka, podtip: podtip, anketa: srv_meta_anketa_id}, function (data) {
		refreshLeft(data);
		vprasanje_fullscreen(data);
	});
}

function change_limittype(limittype) {
	
	if(limittype){
		document.getElementById('vsota_min').disabled = false;
		document.getElementById('vsota_limit').disabled = false;
		document.getElementById('vsota_exact').disabled = true;
	}
	
	else{
		document.getElementById('vsota_min').disabled = true;
		document.getElementById('vsota_limit').disabled = true;
		document.getElementById('vsota_exact').disabled = false;
	}
}

function num_limit(field, checked) {

	if(checked){
		document.getElementById(field).disabled = false;
	}
	
	else{
		document.getElementById(field).disabled = true;
	}
}

function change_diferencial(spremenljivka, enota){
	if(enota == 3)
		$('.grid_subtitle').css('display', 'block');
	else
		$('.grid_subtitle').css('display', 'none');
		
	if(enota == 4 || enota == 5){
		//console.log("Sem v enoti 4");
		$('.drop_grids_num').css('display', 'none');
	}
	else{
		$('.drop_grids_num').css('display', '');
	}
	
	var tip_vpr = $('#spremenljivka_tip_'+spremenljivka+' option:selected').val();
	
	if( tip_vpr == 6 && (enota == 1 || enota == 0) ){		//ce je postavitev "Tabela diferencial" ali "Klasicna tabela"
		$('.diferencial_trak_class').css('display', '');	//pokazi checkbox za trak
		if( $('#diferencial_trak_'+spremenljivka).is( ":checked" )){	//ce je checkbox za trak vklopljen
			$('.diferencial_trak_starting_num_class_'+spremenljivka).css('display', 'block');	//pokazi vnosno polje za zacetno stevilo traku
			$('.grid_defaults_class').css('display', 'none');
			$('.grid_var_class').css('display', 'none');	//skrij moznosti za izbiro privzetih vrednosti
			$('.trak_num_of_titles_class').css('display', 'block');	//pokazi dropdown za izbiro stevila nadnaslovov traku
			$('.drop_custom_column_labels').css('display', 'none');	//skrij "Uporaba label"
		}else{
			$('.drop_custom_column_labels').css('display', 'block');	//pokazi "Uporaba label"
		}
		//$('.grid_inline').toggleClass('trak_class_input');
	}else{	//drugace
		$('.diferencial_trak_class').css('display', 'none');	//skrij checkbox za trak
		$('.diferencial_trak_starting_num_class_'+spremenljivka).css('display', 'none'); //skrij vnosno polje za zacetno stevilo traku
		$('.grid_defaults_class').css('display', 'block');
		$('.grid_var_class').css('display', 'block');	//pokazi moznosti za izbiro privzetih vrednosti
		$('.trak_num_of_titles_class').css('display', 'none');	//pokazi dropdown za izbiro stevila nadnaslovov traku
		//$('.grid_inline').toggleClass('trak_class_input');
	}

	$('#vrednosti_holder').load('ajax.php?t=vprasanje&a=change_diferencial', {spremenljivka: spremenljivka, enota: enota, anketa: srv_meta_anketa_id});
}

function vprasanje_check_variable (_this) {
	
	var input = $(_this);
	var variable = input.val();

    variable1 = check_valid_variable(variable);

    if (variable1 != variable)
  		input.val(variable1);
	
}

function change_grid_width(width){
	
	if(width == -1)	width = 20;

	$('.vrednost_textarea').css('width', width + '%');
}

function edit_grid_variable () {
	
	if ( $('#vprasanje_edit_grid_variable').attr('checked') ) {
		
		$('input[name=edit_grid_variable_edit]').val(1);
		$('table#grids tr:first td[variable]').each( function () {
			$(this).html('<input type="text" name="edit_grid_variable_'+$(this).attr('id')+'" value="'+$(this).html()+'" />');
		});
		
	} else {
		
		$('input[name=edit_grid_variable_edit]').val(0);
		$('table#grids tr:first td[variable]').each( function () {
			$(this).html($(this).attr('variable'));
		});
		
	}
	
}

// prekopira vsebino editorja nazaj v textarea. textarea mora imet id in name nastavljen na tale id
function get_editor_close(id) {

	// probamo prebrat iz editorja, ce je bil nalozen, ker pridobimo vse podatke
	var editor = CKEDITOR.instances[id];

	////var editor = CKEDITOR.get(id);
	if (editor != undefined) {
		try {
			content = editor.getData();
		    editor.isNotDirty = true;
		    $('#'+id).val(content);			// vsebino editorja zapisemo v textarea
		    remove_editor(id);
		} catch (e) {}
	}
}


// prikaze field da manager doda nek komentar obstojecemu komentarju na vprasanje
function comment_on_comment (id) {
	$('#comment_on_comment_'+id).html(
		'<br /><textarea name="vsebina" id="vsebina_comment_on_comment_'+id+'" style="width:100%"></textarea><br />' +
		'<input type="submit" value="'+lang['send']+'" onclick="$.post(\'ajax.php?a=comment_on_comment\', {id: \''+id+'\', vsebina: $(\'#vsebina_comment_on_comment_'+id+'\').val(), anketa: srv_meta_anketa_id}, function() {window.location.reload();}); return false;" />'
	);
}


function change_NG_cancelButton(value){
	if(value == 1){
		$("#NG_cancelText").show();
	} 
	else{
		$("#NG_cancelText").hide();
	}
}

function vprasanje_track(spremenljivka) {
	
	$.post('ajax.php?t=vprasanje&a=vprasanje_tracking', {anketa: srv_meta_anketa_id, spremenljivka: spremenljivka}, function (data) {
		
        $("#dropped_alert").html('<p>'+data+'</p>').fadeIn("fast").animate({opacity: 1.0}, 3000).fadeOut("slow");		
	});	
}

function onchange_submit_show(value){
	
	if(value > 0){
		$('#onchange_submit_div').show();
	} 
	else{
		$('#onchange_submit_div').hide();
	}
}

function show_SN_count(value){

	$('.SN_hidable_settings').hide();
	
	if(value == 0){	
		$('#SN_add_text').show();
	}
	else if(value == 1){	
		$('#SN_count').show();
	}
	else if(value == 3){
		$('#SN_count_text').show();
	}
}

function validation_new (spremenljivka) {
	
	$('#fade').fadeTo('slow', 1);
	$('#div_condition_editing').show().load('ajax.php?t=vprasanje&a=validation_new', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id});
}

function validation_edit (spremenljivka, if_id) {
	
	$('#fade').fadeTo('slow', 1);
	$('#div_condition_editing').show().load('ajax.php?t=vprasanje&a=validation_edit', {spremenljivka: spremenljivka, if_id: if_id, anketa: srv_meta_anketa_id});
}

function validation_if_close (spremenljivka, _if) {
	
	$('#fade').fadeOut('slow');
	$('#div_condition_editing').hide().html('');
	
	if (spremenljivka > 0){
		$('#tab_7').load('ajax.php?t=vprasanje&a=validation_if_close', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id}, 
                        //da se klice vprasanje_save ob spremembah
                        function() {
                                $('#tab_7 select, #tab_7 input, #tab_7 textarea').not('.no_submit').change(function () {
                                        vprasanje_save(true);
                                });
                });
        }
	else
		window.location.reload();
}

function validation_if_remove (spremenljivka, _if) {
	
	if (confirm( lang['srv_brisiifconfirm'] )) {

		$.post('ajax.php?t=branching&a=if_remove', { 'if' : _if, anketa : srv_meta_anketa_id }, function () {
			$('#fade').fadeOut('slow');
			$('#div_condition_editing').hide().html('');
			if (spremenljivka > 0)
				$('#tab_7').load('ajax.php?t=vprasanje&a=validation_if_close', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id});
			else
				window.location.reload();
		});
		
	}
	
}

function grid_plus_minus (type) {
	
	var selected = $('#grids_count').val();
	
	if (type == '1')
		selected = parseInt(selected) + 1;
	else if (type == '0')
		selected = parseInt(selected) - 1;
	
	if (selected >= 2 && selected <= 12)
		$('#grids_count').val( selected );
	
	vprasanje_save(true);
	
	return false;
	
}

function grid_multiple_add (spr_id) {
    
    $('#fade').fadeTo('slow', 1);
	$('#vrednost_edit').show().load('ajax.php?t=vprasanje&a=grid_multiple_add', {spremenljivka:spr_id, anketa:srv_meta_anketa_id});
	
	return false;
}

function grid_multiple_addnew (spr_id, tip, podtip) {
	
	$.post('ajax.php?t=vprasanje&a=grid_multiple_addnew', {spremenljivka:spr_id, tip:tip, podtip: podtip, anketa:srv_meta_anketa_id}, function () {
        $('#vrednost_edit').hide().html('');
        $('#fade').fadeOut('slow');
		vprasanje_save(true);
	});
	
	return false;
}

function grid_multiple_edit (parent, spr_id) {
    
    $('#fade').fadeTo('slow', 1);
	$('#vrednost_edit').show().load('ajax.php?t=vprasanje&a=grid_multiple_edit', {parent:parent, spremenljivka:spr_id, anketa:srv_meta_anketa_id});
		
	return false;
}

function grid_multiple_save (spr) {
	var customRadio = '';
	if($('#spremenljivka_podtip').val() == 12){
		customRadio = $('#customRadioSelect'+spr+' option:selected').val();
	}

	var post_data = {
			anketa: srv_meta_anketa_id, 
			spremenljivka:spr, 
			grids_count: $('#multi_grids_count').val(),
			taWidth: $('#multi_taWidth').val(),
			taHeight: $('#multi_taHeight').val(),
			gridmultiple_width: $('#gridmultiple_width').val(),
			enota: $('#spremenljivka_podtip').val(),
			dostop: $('#spremenljivka_dostop').val(),
			cela: $('#vrednost_edit select[name=cela]').val(),
			decimalna: $('#vrednost_edit select[name=decimalna] ').val(),
			sbSize: $('#selectboxSize'+spr+' option:selected').val(),
			prvaVrstica: $('#prvaVrstica'+spr+' option:selected').val(),
			prvaVrstica_roleta: $('#prvaVrstica_roleta'+spr+' option:selected').val(),
            customRadio: customRadio
	}
	// po potrebi dodamo date_range
	if ($('#date_range_min_'+spr).length)
	{
		post_data['date_range_min'] = $('#date_range_min_'+spr).val(); 
	}
	if ($('#date_range_max_'+spr).length)
	{
		post_data['date_range_max'] = $('#date_range_max_'+spr).val(); 
	}
	$.post('ajax.php?t=vprasanje&a=vprasanje_save&silentsave=true',
		post_data, 
		function () {
            $('#fade').fadeOut('slow');
			$('#vrednost_edit').html('').hide();
            vprasanje_save(true);      
		}
	);
	
}

function show_alert_missing(){

	if($('#missing_value_-97').attr("checked"))
		var missing_97 = 1;
	else
		var missing_97 = 0;
		
	if($('#missing_value_-98').attr("checked"))
		var missing_98 = 1;
	else
		var missing_98 = 0;
		
	if($('#missing_value_-99').attr("checked"))
		var missing_99 = 1;
	else
		var missing_99 = 0;
		
	var reminder = $('select[name=reminder]').val();

	if(reminder == 1 || reminder == 2){
		if(missing_97 == 1){
			$('#alert_show_97').prop('disabled', false);
			$('#alert_show_97_text').removeClass('gray');
		}
		else{
			$('#alert_show_97').prop('disabled', true);
			$('#alert_show_97').attr("checked", false);
			$('#alert_show_97_text').addClass('gray');
		}
		
		if(missing_98 == 1){
			$('#alert_show_98').prop('disabled', false);
			$('#alert_show_98_text').removeClass('gray');
		}
		else{
			$('#alert_show_98').prop('disabled', true);
			$('#alert_show_98').attr("checked", false);
			$('#alert_show_98_text').addClass('gray');
		}
		
		if(missing_99 == 1){
			$('#alert_show_99').prop('disabled', false);
			$('#alert_show_99_text').removeClass('gray');
		}
		else{
			$('#alert_show_99').prop('disabled', true);
			$('#alert_show_99').attr("checked", false);
			$('#alert_show_99_text').addClass('gray');
		}
	}
	else{
		$('#alert_show_97').prop('disabled', true);
		$('#alert_show_97').attr("checked", false);
		$('#alert_show_97_text').addClass('gray');
		
		$('#alert_show_98').prop('disabled', true);
		$('#alert_show_98').attr("checked", false);		
		$('#alert_show_98_text').addClass('gray');
		
		$('#alert_show_99').prop('disabled', true);
		$('#alert_show_99').attr("checked", false);
		$('#alert_show_99_text').addClass('gray');
	}
}

function show_scale_text(value){

	if(value == 1){
		$('#skala_text_ord').hide();
		$('#skala_text_nom').show();
	}
	else{
		$('#skala_text_nom').hide();
		$('#skala_text_ord').show();
	}
}

/**
 * prikaze ali skrije dropdown za max stevilo markerjev/odgovorov
 * in posodobi enoto v srv_spremenljivka ter vkljuci ali izkljuci user_location
 * @param {int} enota -  podtip lokacije 26 1-moja lokacija, 2-multi lokacija
 * @param {int} spremenljivka - id spremenljivke
 */
function change_map(enota, spremenljivka){
                   
        //moja lokacija
	if(enota == 1){
                //set input type to marker
                $('#multi_input_type_'+spremenljivka).val('marker').change();
                
                $('#marker_podvprasanje').show();
		$('#max_markers_map').hide();
                $('#multi_input_type_map').hide();
                $('#user_location_map').show();
                $('#user_location_'+spremenljivka).prop("checked", true);
                $('#fokus_mape').show();
                $('#dodaj_searchbox').show();
                $('#dodaj_searchbox_'+spremenljivka).prop("checked", true);
                vprasanje_save(true, spremenljivka);
	}
        //multilokacija
	else if(enota == 2){
                //get input type from params
                $.post('ajax.php?t=vprasanje&a=get_input_type_map', {spr_id: spremenljivka}, 
                    function (data) {	                        
                        if(data === 'marker'){
                            $('#max_markers_map').show();
                            $('#marker_podvprasanje').show();
                        }
                        else{
                            $('#user_location_map').hide();
                            $('#marker_podvprasanje').show();
                        }

                        $('#marker_podvprasanje').show();
                        $('#multi_input_type_map').show();
                        $('#user_location_'+spremenljivka).prop("checked", false);
                        
                        $('#fokus_mape').show();
                        $('#dodaj_searchbox').show();
                        $('#dodaj_searchbox_'+spremenljivka).prop("checked", true);
                        
                        vprasanje_save(true, spremenljivka);
                    });
	}
        //chooselokacija
        else if(enota == 3){
            //set input type on marker if line or gon before
            $('#multi_input_type_'+spremenljivka).val('marker').change();
            //hide input type dropdown
            $('#multi_input_type_map').hide();
            
            //hide asking for location and uncheck it
            $('#user_location_map').hide();
            $('#user_location_'+spremenljivka).prop("checked", false);

            //hide include subquestion checker and check it
            $('#marker_podvprasanje').hide();            
            $('#marker_podvprasanje_'+spremenljivka).prop("checked", true);
            //show subquestion title - not set if empty
            $('#naslov_podvprasanja_map').show();
            
            //hide focus input
            $('#fokus_mape').hide();
            //hide max markers dropdown
            $('#max_markers_map').hide();
            //hide and uncheck serchbox settings
            $('#dodaj_searchbox').hide();
            $('#dodaj_searchbox_'+spremenljivka).prop("checked", false);
            
            vprasanje_save(true, spremenljivka);
        }
}

/**
 * prikaze ali skrije dropdown za max stevilo markerjev/odgovorov
 * @param {string} tip -  input tip multilokacije - marker, polygon, polyline
 * @param {int} spremenljivka - id spremenljivke
 */
function change_input_map(tip, spremenljivka){
	if(tip === 'marker'){
		$('#max_markers_map').show();
                $('#user_location_map').show();
                $('#marker_podvprasanje').show(); 
	}
	else{
                //by default, set soft reminder
                //$('select[name=reminder]').val('1').change();
                
                //hide include subquestion checker and check it
                $('#marker_podvprasanje').hide();            
                $('#marker_podvprasanje_'+spremenljivka).prop("checked", false);
                $('#naslov_podvprasanja_map').hide();
		$('#max_markers_map').hide();
                $('#user_location_'+spremenljivka).prop("checked", false);
                $('#user_location_map').hide();
                $('#dodaj_searchbox').hide();
                $('#dodaj_searchbox_'+spremenljivka).prop("checked", false);
                vprasanje_save(true, spremenljivka);
	}
}

/**
 * prikaze ali skrije input text za besedilo podvprasanja v infowindow
 */
function show_infowindow_map(){
        $('#naslov_podvprasanja_map').toggle();
}

function show_selectbox_size(spremenljivka, enota, tip){
	
	if (tip == 3){
		var trenutni_tip = $('#spremenljivka_tip_'+spremenljivka+' option:selected').text();
		//console.log(trenutni_tip);
		//console.log(spremenljivka);
		$('#spremenljivka_tip_'+spremenljivka+' option:selected').val(1);
	}
		
	if (tip == 1||tip == 2){
		if(enota != 6 && enota != 4){
			$('.dropselectboxsize').css('display', 'none');
			$('.dropselectboxsizeprvavrstica').css('display', 'none');
			$('.dropselectboxsizeprvavrstica_roleta').css('display', 'none');
        }
		else if (enota == 6){
			$('.dropselectboxsize').css('display', '');
			$('.dropselectboxsizeprvavrstica').css('display', '');
			$('.dropselectboxsizeprvavrstica_roleta').css('display', 'none');
		}
		else if (enota == 4){
			$('.dropselectboxsizeprvavrstica_roleta').css('display', '');
			$('.dropselectboxsize').css('display', 'none');
			$('.dropselectboxsizeprvavrstica').css('display', 'none');
		}
	}
	if (tip == 6){
		if(enota != 6 && enota != 2){
			$('.dropselectboxsize').css('display', 'none');
			$('.dropselectboxsizeprvavrstica').css('display', 'none');
			$('.dropselectboxsizeprvavrstica_roleta').css('display', 'none');
		}
		else if (enota == 6){
			$('.dropselectboxsize').css('display', '');
			$('.dropselectboxsizeprvavrstica').css('display', '');
			$('.dropselectboxsizeprvavrstica_roleta').css('display', 'none');
		}
		else if (enota == 2){
			$('.dropselectboxsizeprvavrstica_roleta').css('display', '');
			$('.dropselectboxsize').css('display', 'none');
			$('.dropselectboxsizeprvavrstica').css('display', 'none');
		}
	}
	
	// if (tip == 3){
	//console.log(tip);
	// }
}

function show_preset_value(spremenljivka, enota, tip){
	
	if (tip == 1){
		if(enota == 0 || enota == 1 || enota == 2 || enota == 7){
			$('.presetValue').css('display', '');
        }
		else {
			$('.presetValue').css('display', 'none');
		}
	}
	if(tip == 2){
		if(enota == 0 || enota == 1 || enota == 2 || enota == 7){
			$('.presetValue').css('display', '');
        }
		else {
			$('.presetValue').css('display', 'none');
		}
	}	
	if (tip == 6){
		if(enota == 0 || enota == 1 || enota == 8){
			$('.presetValue').css('display', '');
        }
		else {
			$('.presetValue').css('display', 'none');
		}
	}
}

function show_custom_picture_radio(spremenljivka, enota){
	// Custom radio
	if(enota == 9 || enota == 12){

        $('#kategorije_odgovorov_'+spremenljivka).children('p').hide();
        $('.vizualna-analogna-skala').hide();
		$('.custom-picture-radio').show();

	// Vizualna analogna skala
	}else if(enota == 11){

        $('#kategorije_odgovorov_'+spremenljivka).children('p').hide();
        $('.custom-picture-radio').hide();
        $('.vizualna-analogna-skala').show();

	}else{

        $('#kategorije_odgovorov_'+spremenljivka).children('p').show();
        $('.vizualna-analogna-skala').hide();
		$('.custom-picture-radio').hide();

	}
}

function change_selectbox_size(spremenljivka, size, tekst){
	
	if($('#selectboxSize'+spremenljivka+' option:selected').text() == tekst){//ce je tekst trenutne izbire "vse", nadaljuj
		var trenutnoStevilo = $('#selectboxSize'+spremenljivka+' option:selected').val();//trenutno izbrano stevilo vnosov, kjer trenunto pise "vse"
		$('#selectboxSize'+spremenljivka+' option:selected').text(trenutnoStevilo);	  //nadomesti tekst "vse" s stevilom
		
		$('#selectboxSize'+spremenljivka).empty();//sprazni dropdown s stevilom vnosov
		
		for (i=1; i<=size; i++){
			if (i==size){
				$('#selectboxSize'+spremenljivka).append('<option value='+i+'>'+tekst+'</option>');
				$('#selectboxSize'+spremenljivka).val(i);//spremeni vrednost dropdown-a s stevilom trenutnih vidnih vnosov
				$('#selectboxSize'+spremenljivka+' option:selected').text(tekst);//izbrano stevilo vnosov naj nadomesti tekst "vse"
			}
			else{
				$('#selectboxSize'+spremenljivka).append('<option value='+i+'>'+i+'</option>');
			}
		}
		
	}
	else if($('#selectboxSize'+spremenljivka+' option:selected').text() != tekst){

		var trenutnoStevilo = $('#selectboxSize'+spremenljivka+' option:selected').val();//trenutno izbrano stevilo vnosov, kjer trenunto pise "vse"
		var selectboxsize = $('#selectboxSize'+spremenljivka+' option').length;
		
		//console.log(selectboxsize);
		
		if (size != selectboxsize){
			
			$('#selectboxSize'+spremenljivka).empty();//sprazni dropdown s stevilom vnosov
			
			for (i=2; i<=size; i++){
				//console.log(i);
				$('#selectboxSize'+spremenljivka).append('<option value='+i+'>'+i+'</option>');
			}
		
		}
		
		for (i = 1; i <= size+1; i++) {			
			var vse = $('#selectboxSize'+spremenljivka+' option[value='+i+']').text();
			if(vse == tekst){
				$('#selectboxSize'+spremenljivka+' option[value='+i+']').text(i);
			}
		}								
		$('#selectboxSize'+spremenljivka+' option[value='+size+']').text(tekst);//izbrano stevilo vnosov naj nadomesti tekst "vse"								
		
		$('#selectboxSize'+spremenljivka).val(trenutnoStevilo);//spremeni vrednost dropdown-a s stevilom trenutnih vidnih vnosov
									
	}
	
}

function change_selectbox_size_1(spremenljivka, tekst){	//spremeni selectbox size ob kliku na + pri dodajanju kategorij odgovorov
	var grids = $('#grids_count option:selected').val();
	var size = parseInt(grids) + 1;
	
	//console.log(size);
	if($('#selectboxSize'+spremenljivka+' option:selected').text() == tekst){//ce je tekst trenutne izbire "vse", nadaljuj
		var trenutnoStevilo = $('#selectboxSize'+spremenljivka+' option:selected').val();//trenutno izbrano stevilo vnosov, kjer trenunto pise "vse"
		$('#selectboxSize'+spremenljivka+' option:selected').text(trenutnoStevilo);	  //nadomesti tekst "vse" s stevilom
		
		$('#selectboxSize'+spremenljivka).empty();//sprazni dropdown s stevilom vnosov
		
		for (i=1; i<=size; i++){
			if (i==size){
				$('#selectboxSize'+spremenljivka).append('<option value='+i+'>'+tekst+'</option>');
				$('#selectboxSize'+spremenljivka).val(i);//spremeni vrednost dropdown-a s stevilom trenutnih vidnih vnosov
				$('#selectboxSize'+spremenljivka+' option:selected').text(tekst);//izbrano stevilo vnosov naj nadomesti tekst "vse"
			}
			else{
				$('#selectboxSize'+spremenljivka).append('<option value='+i+'>'+i+'</option>');
			}
		}
		
	}
	else if($('#selectboxSize'+spremenljivka+' option:selected').text() != tekst){
		var trenutnoStevilo = $('#selectboxSize'+spremenljivka+' option:selected').val();//trenutno izbrano stevilo vnosov, kjer trenunto pise "vse"
		var selectboxsize = $('#selectboxSize'+spremenljivka+' option').length;
		
		//console.log(selectboxsize);
		
		if (size != selectboxsize){
			
			$('#selectboxSize'+spremenljivka).empty();//sprazni dropdown s stevilom vnosov
			
			for (i=2; i<=size; i++){
				//console.log(i);
				$('#selectboxSize'+spremenljivka).append('<option value='+i+'>'+i+'</option>');
			}
		
		}
		
		for (i = 1; i <= size+1; i++) {			
			var vse = $('#selectboxSize'+spremenljivka+' option[value='+i+']').text();
			if(vse == tekst){
				$('#selectboxSize'+spremenljivka+' option[value='+i+']').text(i);
			}
		}								
		$('#selectboxSize'+spremenljivka+' option[value='+size+']').text(tekst);//izbrano stevilo vnosov naj nadomesti tekst "vse"								
		
		$('#selectboxSize'+spremenljivka).val(trenutnoStevilo);//spremeni vrednost dropdown-a s stevilom trenutnih vidnih vnosov
									
	}
	
}
function show_nastavitve_tabela_da_ne(spremenljivka, enota){
	if(enota != 8 && enota != 4){// ce postavitev ni tabela da/ne in max diff in compare
		if (enota != 5) {
			$('.drop_grids_num').css('display', '');		//pokazi nastavitve
		}
		$('.grid_defaults').css('display', '');
		//$('#gridAlign').css('display', 'none');
	}
	else{				//drugace, ce je tabela da/ne
		$('.drop_grids_num').css('display', 'none'); //skrij nastavitve
		$('.grid_defaults').css('display', 'none');	
		//$('.grid_defaults option:selected').val(6);	//izberi Ne - Da
		$('#grid_defaults').val(6);	//izberi Ne - Da
		//$('#gridAlign').css('display', '');
		$('#gridAlign').val(1);	//poravnavo uredi na levo
	}
	if (enota != 8){
		$('.faicon.add.'+spremenljivka).css('display', '');	//pokazi moznost dodajanja novega stolpca
	}
	else{
		$('.faicon.add.'+spremenljivka).css('display', 'none');//skrij moznost dodajanja novega stolpca
	}
	vprasanje_save(true);
	//$('#vrednosti_holder').load('ajax.php?t=vprasanje&a=change_diferencial', {spremenljivka: spremenljivka, enota: enota, anketa: srv_meta_anketa_id});
}

function show_slider_prop(spremenljivka, ranking_k){//prikaze/skrije nastavitve za sliders

	if(ranking_k != 1){	
		$('.dropsliderhandle').css('display', 'none');
		$('.dropsliderwindownumber').css('display', 'none');	//dropsliderwindownumber
		$('.dropMinMaxNumLabel').css('display', 'none');
		//$('.dropMinMaxNumLabelNew').css('display', 'none'); //dropNumLabelNew
		$('.dropNumLabelNew').css('display', 'none');
		//$('.dropMinMaxLabel').css('display', 'none');	//dropDescriptiveLabel
		$('.dropDescriptiveLabel').css('display', 'none');
		$('.dropVmesneLabel').css('display', 'none');
		$('.dropVmesneCrtice').css('display', 'none');		
		$('.dropsliderhandle_step_'+spremenljivka).css('display', 'none');
		$('.MinMaxLabels').css('display', 'none');
		$('.dropslidernakaziodgovore').css('display', 'none');	//dropsliderwindownumber
	}
	else{	//ce je izbran slider
		$('.dropsliderhandle').css('display', '');
		$('.dropsliderwindownumber').css('display', '');	//dropsliderwindownumber
		$('.dropMinMaxNumLabel').css('display', '');
		//$('.dropMinMaxNumLabelNew').css('display', '');
		$('.dropNumLabelNew').css('display', '');
		//$('.dropMinMaxLabel').css('display', '');
		$('.dropDescriptiveLabel').css('display', '');
		$('.dropVmesneLabel').css('display', '');
		$('.dropVmesneCrtice').css('display', '');
		$('.dropsliderhandle_step_'+spremenljivka).css('display', '');
		$('.dropslidernakaziodgovore').css('display', '');
	}

}

function slider_checkbox_prop (spremenljivka){
	if( $('#slider_handle_'+spremenljivka).is( ":checked" )){	//ce hocemo viden slider, slider_handle = 0
		$('#slider_handle_hidden_'+spremenljivka).prop('disabled', true);	//disable hidden polje z obratno vrednostjo, slider_handle = 1
	}
	else {
		$('#slider_handle_hidden_'+spremenljivka).prop('disabled', false);
	}
	
	if( $('#slider_window_number_'+spremenljivka).is( ":checked" )){	//ce hocemo viden slider, slider_handle = 0
		$('#slider_window_number_hidden_'+spremenljivka).prop('disabled', true);	//disable hidden polje z obratno vrednostjo, slider_handle = 1
	}
	else {
		$('#slider_window_number_hidden_'+spremenljivka).prop('disabled', false);
	}
	
	if( $('#slider_nakazi_odgovore_'+spremenljivka).is( ":checked" )){	//ce hocemo vidne bunke/elipse za nakazanje moznih odgovorov
		$('#slider_nakazi_odgovore_hidden_'+spremenljivka).prop('disabled', true);	//disable hidden polje z obratno vrednostjo
		$('#slider_VmesneCrtice_'+spremenljivka).prop('disabled', true);	//disable checkbox za vklop vmesnih crtic
		$('.dropVmesneCrtice').css({opacity: 0.5});	//osivitev celotnega div-a, kjer vklopimo crtice
	}
	else {
		$('#slider_nakazi_odgovore_hidden_'+spremenljivka).prop('disabled', false);
		$('#slider_VmesneCrtice_'+spremenljivka).prop('disabled', false);	//enable checkbox za vklop vmesnih crtic
		$('.dropVmesneCrtice').css({opacity: 1});	//odstranitev osivitve celotnega div-a, kjer vklopimo crtice
	}
	
	if( $('#slider_MinMaxNumLabelNew_'+spremenljivka).is( ":checked" )){	//ce hocemo vidne stevilske oznake min/max, konstanta = 0
		$('#slider_MinMaxNumLabelNew_hidden_'+spremenljivka).prop('disabled', true);	//disable hidden polje z obratno vrednostjo, konstanta = 1
	}
	else {
		$('#slider_MinMaxNumLabelNew_hidden_'+spremenljivka).prop('disabled', false);
	}
	
	if( $('#slider_MinMaxLabel_'+spremenljivka).is( ":checked" )){	//ce hocemo vidne stevilske oznake min/max, konstanta = 0
		$('#slider_MinMaxLabel_hidden_'+spremenljivka).prop('disabled', true);	//disable hidden polje z obratno vrednostjo, konstanta = 1
		$('.MinMaxLabels').css('display', '');	//pokazi polja za urejanje min in max opisni labeli
	}
	else {
		$('#slider_MinMaxLabel_hidden_'+spremenljivka).prop('disabled', false);
		$('.MinMaxLabels').css('display', 'none');	//skrij polja za urejanje min in max opisni labeli
	}
	
	if( $('#slider_VmesneCrtice_'+spremenljivka).is( ":checked" )){	//ce hocemo vidne crtice, konstanta = 0
		$('#slider_VmesneCrtice_hidden_'+spremenljivka).prop('disabled', true);	//disable hidden polje z obratno vrednostjo, konstanta = 1
	}
	else {
		$('#slider_VmesneCrtice_hidden_'+spremenljivka).prop('disabled', false);
	}
	
	if( $('#slider_VmesneNumLabel_'+spremenljivka).is( ":checked" )){	//ce hocemo vidne vmesne stevilske labele, konstanta = 0
		$('#slider_VmesneNumLabel_hidden_'+spremenljivka).prop('disabled', true);	//disable hidden polje z obratno vrednostjo, konstanta = 1
	}
	else {
		$('#slider_VmesneNumLabel_hidden_'+spremenljivka).prop('disabled', false);
	}
	
	if( $('#slider_VmesneDescrLabel_'+spremenljivka).is( ":checked" )){	//ce hocemo vidne vmesne opisne labele, konstanta = 0
		$('#slider_VmesneDescrLabel_hidden_'+spremenljivka).prop('disabled', true);	//disable hidden polje z obratno vrednostjo, konstanta = 1
		$('.slider_DescriptiveLabel_defaults').css('display', '');
		$('.dropNumofDescrLabels').css('display', '');
		$('#slider_handle_step_'+spremenljivka).val(1);	//postavi vrednost dropdown-a na vrednost 1				
		var minimum = 1;
		$('#slider_MinNumLabel_'+spremenljivka).val(minimum);	//postavi vrednost Min na vneseni minumum
		
		var maximum = $('#slider_NumofDescrLabels_'+spremenljivka+' option:selected').val();	//izbrano stevilo opisnih label		
		$('#slider_MaxNumLabel_'+spremenljivka).val(maximum);	////postavi vrednost Max na vneseni maximum postavi vrednost Max na vrednost stevila opisnih label		
		
		var post_data = {	//pripravi podatke za post-anje spremenjenih nastavitev
			anketa: srv_meta_anketa_id, 
			spremenljivka: spremenljivka,
			slider_handle_step: $('#slider_handle_step_'+spremenljivka).val(),
			slider_MinNumLabel: $('#slider_MinNumLabel_'+spremenljivka).val(),
			slider_MaxNumLabel: maximum
		}
		$.post('ajax.php?t=vprasanje&a=vprasanje_save&silentsave=true',	//post-anje podatkov spremenjenih nastavitev
			post_data, 
			function () {
				vprasanje_save(true);
			}
		);
		$('#slider_handle_step_'+spremenljivka).prop('disabled', true);	//disable spreminjanje slider handle step
		$('.dropsliderhandle_step_'+spremenljivka).css({opacity: 0.5});	//osivitev celotnega div-a
		$('.MinMaxNumLabels_'+spremenljivka).css('display', 'none'); //skrij min in max
		$('.dropsliderhandle_step_'+spremenljivka).css('display', 'none'); //skrij korak drsnika
		$('.slider_VmesneCrtice_'+spremenljivka).css('display', 'none'); //skrij nastavitev z vmesne crtice */
		//inline_opisne_labele_'.$row['id'].'
		//$('#inline_opisne_labele_'+spremenljivka).css('display', 'block'); //pokazi spodnje nastavitve za urejanje custom opisnih label
	}
	else {
		$('#slider_VmesneDescrLabel_hidden_'+spremenljivka).prop('disabled', false);
 		$('.slider_DescriptiveLabel_defaults').css('display', 'none');
		$('.dropNumofDescrLabels').css('display', 'none');
		$('#slider_DescriptiveLabel_defaults_'+spremenljivka).val('0');	//daj vrednost default-ov na "brez" slider_DescriptiveLabel_defaults_4091
		$('#slider_handle_step_'+spremenljivka).prop('disabled', false); //enable spreminjanje slider handle step
		$('.dropsliderhandle_step_'+spremenljivka).css({opacity: 1});	//odstranitev osivitve celotnega div-a
		$('.MinMaxNumLabels_'+spremenljivka).css('display', ''); //pokazi min in max
		$('.dropsliderhandle_step_'+spremenljivka).css('display', ''); //pokazi korak drsnika
		$('.slider_VmesneCrtice_'+spremenljivka).css('display', ''); //pokazi nastavitev z vmesne crtice
		
		//$('#inline_opisne_labele_'+spremenljivka).css('display', 'none'); //skrij spodnje nastavitve za urejanje custom opisnih label

		var maximum_100 = $('#slider_MaxNumLabelTemp_'+spremenljivka).val();
		var minimum_100 = $('#slider_MinNumLabelTemp_'+spremenljivka).val();
		
		
		var post_data = {	//pripravi podatke za post-anje spremenjenih nastavitev
			anketa: srv_meta_anketa_id, 
			spremenljivka: spremenljivka,
			slider_handle_step: $('#slider_handle_step_'+spremenljivka).val(),
			slider_MaxNumLabel: maximum_100,
			slider_MinNumLabel: minimum_100
		}
 		$.post('ajax.php?t=vprasanje&a=vprasanje_save&silentsave=true',	//post-anje podatkov spremenjenih nastavitev
			post_data, 
			function () {
				vprasanje_save(true);
			}
		);
	}
	
	if( $('#slider_labele_podrocij_'+spremenljivka).is( ":checked" )){	//ce vklopimo nastavitev za labele podrocij
		$('#slider_labele_podrocij_hidden_'+spremenljivka).prop('disabled', true);	//disable hidden polje z obratno vrednostjo
		$('.drop_slider_stevilo_label_podrocij').css('display', '');	//pokazi dropdown s stevilom label podrocij
		//$('#slider_VmesneCrtice_'+spremenljivka).prop('disabled', true);	//disable checkbox za vklop vmesnih crtic
		//$('.dropVmesneCrtice').css({opacity: 0.5});	//osivitev celotnega div-a, kjer vklopimo crtice
	}
	else {
		$('#slider_labele_podrocij_hidden_'+spremenljivka).prop('disabled', false);
		$('.drop_slider_stevilo_label_podrocij').css('display', 'none'); //skrij dropdown s stevilom label podrocij
		//$('#slider_VmesneCrtice_'+spremenljivka).prop('disabled', false);	//enable checkbox za vklop vmesnih crtic
		//$('.dropVmesneCrtice').css({opacity: 1});	//odstranitev osivitve celotnega div-a, kjer vklopimo crtice
	}
}

function slider_defaultDescrLabels_value(spremenljivka, descLabelId){
	if(descLabelId!=0){
		$('#inline_opisne_labele_'+spremenljivka).css('display', 'none'); //skrij spodnje nastavitve za urejanje custom opisnih label
	}else{
		$('#inline_opisne_labele_'+spremenljivka).css('display', 'block'); //pokazi spodnje nastavitve za urejanje custom opisnih label
	}
	
}

function sliderCopytoMinNumLabelTemp(spremenljivka){
	var minimum = $('#slider_MinNumLabel_'+spremenljivka).val();
	$('#slider_MinNumLabelTemp_'+spremenljivka).val(minimum);
}

function sliderCopytoMaxNumLabelTemp(spremenljivka){
	var maximum = $('#slider_MaxNumLabel_'+spremenljivka).val();
	$('#slider_MaxNumLabelTemp_'+spremenljivka).val(maximum);
}

function max_diff_labels (spremenljivka, enota, label1, label2, label3){
    
    //ce je izbran maxdiff
	if(enota == 5){
		//zapisi v bazo ustrezne labele za maxdiff
		$.post('ajax.php?t=vprasanjeinline&a=inline_grid_naslov_save', {spremenljivka: spremenljivka, grid: '1', anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, naslov: label1});
		$.post('ajax.php?t=vprasanjeinline&a=inline_grid_naslov_save', {spremenljivka: spremenljivka, grid: '2', anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, naslov: label2});
	}
}

function vprasanje_float_editing_click(event) {	
	inline_vprasanje_float_editing_click(event);
}


function show_drag_and_drop_new_look_option(spremenljivka, enota){
	if(enota == 9){		//ce je postavitev Drag and drop
		$('.drag_and_drop_new_look_class').css('display', '');	//pokazi checkbox za trak
		//if( $('#diferencial_trak_'+spremenljivka).is( ":checked" )){	//ce je checkbox za trak vklopljen

		//}
	}else{	//drugace
		$('.drag_and_drop_new_look_class').css('display', 'none');	//skrij checkbox za trak
	}
}

function drag_and_drop_new_look_checkbox_prop (spremenljivka){
	if( $('#drag_and_drop_new_look_'+spremenljivka).is( ":checked" )){	//ce hocemo vidno opcijo za skatlasto obliko
		$('#drag_and_drop_new_look_hidden_'+spremenljivka).prop('disabled', true);	//disable hidden polje z obratno vrednostjo
	}
	else {
		$('#drag_and_drop_new_look_hidden_'+spremenljivka).prop('disabled', false);
	}
}

function signatureProp (spremenljivka){
	if( $('#signature_'+spremenljivka).is( ":checked" )){	//ce je signature vklopljen
		$('#orientation_'+spremenljivka).css('display', 'none');
		$('#kategorijeOdgovorov_'+spremenljivka).css('display', 'none');
	}
	else {
		$('#orientation_'+spremenljivka).css('display', '');
		$('#kategorijeOdgovorov_'+spremenljivka).css('display', '');
	}
}

function textSubtypeToggle (what, value){
	
	// Enable
	if(value == 0){	
		$('select[name=upload]').prop('disabled', false);
		$('input[name=signature]').prop('disabled', false);
		$('input[name=captcha]').prop('disabled', false);
        $('input[name=emailVerify]').prop('disabled', false);
        
        $('.kategorije_odgovorov').show();
        $('.upload_info').hide();
	}
	// Disable
	else{	
		if(what != 'upload')
			$('select[name=upload]').prop('disabled', true);
		if(what != 'signature')
			$('input[name=signature]').prop('disabled', true);
		if(what != 'captcha')
			$('input[name=captcha]').prop('disabled', true);
		if(what != 'emailVerify')
            $('input[name=emailVerify]').prop('disabled', true);
            
        if(what == 'upload' || what == 'signature' || what == 'captcha')
            $('.kategorije_odgovorov').hide();

        if(what == 'upload')
            $('.upload_info').show();
	}
}


//ureja delovanje logike za prikaz opozorila, ce min limit pri checkbox presega max limit
function checkCheckboxLimits(spremenljivka, value, checkbox_limit_name){
	
	if(checkbox_limit_name == "checkbox_min_limit"){
		var other_value = $('#checkbox_limit_' + spremenljivka + ' option:selected').val();
		var max_value = other_value;
		var min_value = value;
	}else{
		var other_value = $('#checkbox_min_limit_' + spremenljivka + ' option:selected').val();
		var max_value = value;
		var min_value = other_value;
	}
	
	//ce je min limit vecji od max limit, je potrebno javiti opozorilo in vrednost min limita dati na 0
	//if(min_value > max_value){
	if(max_value!=0 && min_value > max_value){
		genericAlertPopup('srv_checkbox_min_limit_error_msg'); //opozorilo v obliki pop-up okna		
		$('#checkbox_min_limit_' + spremenljivka).val(0); //spremeni vrednost min limita na 0 oz. Ne	
	}	
}

//ureja skrivanje nastavitve za opozorilo ob izbiri nastavitve min limit pri checkbox
function toggleCheckboxMinLimitReminder(spremenljivka, checkbox_min_limit){
	if(checkbox_min_limit!=0){	//ce je limit nastavljen
		$('#checkboxLimitReminder_' + spremenljivka).css('display', '');	//pokazi nastavitve za opozorilo
	}else{
		$('#checkboxLimitReminder_' + spremenljivka).css('display', 'none');	//skrij nastavitve za opozorilo
	}
}