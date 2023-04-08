var srv_meta_lang_id = 0;

var orientation;

function getOrientation(orientation01){
	orientation = orientation01;
	//orientation = $('#spremenljivka_podtip_'+orientation01+'').val();
	//console.log("Orientation v funkciji je: "+orientation);
}

function getOrientation1(){
	return orientation;
}

/**
* inicializacija za inline urejanje vprasanj
*/
function onload_init_inline () {
	
	if (locked) return;

	// urejanje naslova vprašanj - pokaže tudi ikono za EDITOR
	$("div.naslov_inline").live({
		focus: function() {
			close_all_editing($(this).attr('spr_id'));
			if ($(this).attr('default') == '1' && $(this).attr('contenteditable') == 'true') window.setTimeout( function() { document.execCommand('selectAll',false,null); }, 1 );
			$('#spremenljivka_content_'+$(this).attr('spr_id')+' span.display_editor').addClass('show');
		},
		keypress: function (event) {
			enterKeyPressHandler(event);
			$(this).attr('default', '0');
		},
		blur: function () {
			inline_naslov($(this).attr('spr_id'), this);
			if ( ! $(this).closest('.spr').hasClass('spr_editing')) {
				var spr_id = $(this).attr('spr_id');
				$('#spremenljivka_content_'+spr_id+' span.display_editor').css('opacity', '0');
				window.setTimeout( function() { $('#spremenljivka_content_'+spr_id+' span.display_editor').removeClass('show').css({'opacity':'1'}); }, 300 );	// removamo z delayem, ker drugace se ne da klikniti (smo v bluru)
			}
		},
		//kadar gre za mouseover preko vprašanja
		mouseover: function() {
			$('#spremenljivka_content_'+$(this).attr('spr_id')+' span.display_editor').addClass('show');
		},

		mouseleave: function() {
			var u = $(this).siblings('span.display_editor');
			//setTimeout se uporabi za FF, ker drugače ikona utripa
			setTimeout(function() {
				if(u.is(':hover') == false) {
					u.removeClass('show');
				}
			}, 100);

		}
	});

	// urejanje opombe
	$("div.info_inline").live('focus', function() {
		close_all_editing($(this).attr('spr_id'));
		if ($(this).attr('default') == '1' && $(this).attr('contenteditable') == 'true') window.setTimeout( function() { document.execCommand('selectAll',false,null); }, 1 );
	}).live('keypress', function (event) {
		enterKeyPressHandler(event);
		$(this).attr('default', '0');
	}).live('blur', function () {
		inline_info($(this).attr('spr_id'), this);
	});
	
	// urejanje inline vrednosti
	$("div.variable_inline").live('focus', function() {
		close_all_editing($(this).closest('.spremenljivka_content').attr('spr_id'));
	}).live('keyup', function () {	
		var variable = $(this).html();
		var tip = $(this).closest('.spremenljivka_content').attr('tip');
		if($.trim(variable).length && tip != 1){	
			variable = variable.replace(/(<([^>]+)>)/ig,"");
			var variable1 = check_valid_variable(variable);	
			if (variable1 != variable){
				$(this).html(variable1);
			}
		}
	}).live('keypress', function (event) {
		enterKeyPressHandler(event);
	}).live('blur', function () {
		inline_vrednost_variable($(this).attr('vre_id'), this);
	});


	// urejanje vrednosti
	$('div.vrednost_inline').live({
		focus: function (event) {
			close_all_editing($(this).closest('.spremenljivka_content').attr('spr_id'));
			if ($(this).attr('default') == '1' && $(this).attr('contenteditable') == 'true') window.setTimeout(function () {
				document.execCommand('selectAll', false, null);
			}, 1);
			$(this).closest('.variabla').addClass('inlineedit');

			if (!$(this).closest('.spr').hasClass('spr_editing'))  $(this).parent().find('.inline_edit, .inline_delete, .inline_if_not, .inline_hidden, .correct').addClass('show');

			inline_nova_vrednost(this);		// tale je ce urejamo kategorijo, da se pojavi cim kliknemo
		},
		keypress: function (event) {
			//console.log("keypress");
			var evt = event || window.event;
			var charCode = evt.which || evt.keyCode;
			if (charCode == 9) return;		// ce gremo s tabom naprej, da ne doda nove kategorije
			enterKeyPressHandler(event);	// pohednla <br> na Enter
			$(this).attr('default', '0');
			inline_nova_vrednost(this);

			var div = $(this).closest('.variabla');

			// ko zacnemo pisati v polje za dodajanje nove vrednosti, jo takoj kreiramo, da dobimo nov ID - naprej gre potem isto kot urejanje
			// new="waiting" nam pove, da se je izvrsil post, drugace se ob tipkanju veckrat dodaja
			if (div.attr('id') == 'variabla_new' && div.attr('new') != 'waiting' && ( div.find('div.vrednost_inline:first').html() != '' || div.find('div.vrednost_inline:last').html() != '' )) {
				var spr_id = div.closest('.spremenljivka_content').attr('spr_id');
				div.attr('new', 'waiting');
				$.post('ajax.php?t=vprasanjeinline&a=inline_vrednost_new', {
					spremenljivka: spr_id,
					anketa: srv_meta_anketa_id
				}, function (data) {
					div.attr('id', 'variabla_' + data);
					div.attr('new', '');
					div.find('div.vrednost_inline').attr('id', 'vre_id_' + data).attr('vre_id', data);
                    div.find('span.inline_hidden').attr('odg_id', data);
                    div.find('span.inline_if_follow').attr('onclick', 'follow_up_condition(\''+data+'\'); return false;');
                    div.find('span.inline_if_not').attr('onclick', 'vrednost_condition_editing(\''+data+'\'); return false;');
                    
                    if (div.find('div.vrednost_inline').length > 1)
						div.find('div.vrednost_inline:last').attr('id', 'vre_id_' + data + '_2').attr('vre_id', data + '_2');
				});
			}
		},
		keyup: function (event) {
			inline_nova_vrednost(this);		// tale je, ce kliknemo na novo, da se ne pojavi takoj, ampak sele ko nekaj napisemo
		},
		blur: function () {
			var _this = this;
			var vre_id = $(this).attr('vre_id');
			var spr_id = $(this).closest('div.spremenljivka_content').attr('spr_id');

			// timeout, da se lahko sploh klikne v stvari zunaj contenteditabla - drugace jih takoj skrije
			setTimeout(function () {
				inline_nova_vrednost_hide(spr_id, vre_id);
				if (!$(_this).closest('.spr').hasClass('spr_editing')) $(_this).parent().find('.inline_edit, .inline_delete, .inline_if_not, .inline_if_follow, .inline_hidden').removeClass('show');
			}, 200);

			$(this).closest('.variabla').removeClass('inlineedit');

			inline_vrednost(spr_id, this);
		}
	});

    
	// urejanje vrednosti
	$('.textfield_editable').live({
		blur: function () {
			var _this = this;
			var vre_id = $(this).attr('id');
			var spr_id = $(this).closest('div.spremenljivka_content').attr('spr_id');
                       
			inline_vrednost(spr_id, this);
		}
	});
        
    
	// urejanje vrednosti oz. imena obmocja @image hotspot
	$('div.hotspot_vrednost_inline').live({
		focus: function (event) {
			if ($(this).attr('default') == '1' && $(this).attr('contenteditable') == 'true') window.setTimeout(function () {
				document.execCommand('selectAll', false, null);
			}, 1);
			$(this).closest('.variabla').addClass('inlineedit');

			if (!$(this).closest('.spr').hasClass('spr_editing'))  $(this).parent().find('.inline_edit, .inline_delete, .inline_if_not, .inline_hidden').addClass('show');

			inline_nova_vrednost(this);		// tale je ce urejamo kategorijo, da se pojavi cim kliknemo
		},
		blur: function () {
			//console.log("Blur");
			var _this = this;
			var vre_id = $(this).attr('vre_id');
			var spr_id = $(this).closest('div.spremenljivka_content').attr('spr_id');

			// timeout, da se lahko sploh klikne v stvari zunaj contenteditabla - drugace jih takoj skrije
			setTimeout(function () {
				inline_nova_vrednost_hide(spr_id, vre_id);
				if (!$(_this).closest('.spr').hasClass('spr_editing')) $(_this).parent().find('.inline_edit, .inline_delete, .inline_if_not, .inline_if_follow, .inline_hidden').removeClass('show');
			}, 200);

			$(this).closest('.variabla').removeClass('inlineedit');

			inline_hotspot_vrednost(spr_id, this);
		}
	});
	
	//ko zapustimo variable_holder
	$(document).delegate('.variabla', 'hover', function(event){
		if(event.type == 'mouseleave'){
			$(this).find('.inline_edit, .inline_delete, .inline_if_not, .inline_if_follow, .inline_hidden, .correct').removeClass('show');
		}
		if(event.type == 'mouseenter'){
			$(this).find('.inline_edit, .inline_delete, .inline_if_not, .inline_if_follow, .inline_hidden, .correct').addClass('show');
		}
	});


	// urejanje grida
	$("div.grid_inline").live('focus', function() {
		close_all_editing($(this).closest('.spremenljivka_content').attr('spr_id'));
		if ($(this).attr('default') == '1' && $(this).attr('contenteditable') == 'true') window.setTimeout( function() { document.execCommand('selectAll',false,null); }, 1 );
	}).live('keypress', function (event) {
		enterKeyPressHandler(event);
		$(this).attr('default', '0');
	}).live('blur', function () {
		var spr_id = $(this).attr('spr_id') || $(this).closest('div.spremenljivka_content').attr('spr_id');
		inline_grid(spr_id, this);
	});
	
	// urejanje vrednosti grida
	$("div.grid_variable_inline").live('blur', function () {
		var spr_id = $(this).closest('div.spremenljivka_content').attr('spr_id');
		inline_variable_grid(spr_id, this);
	});
	
	// urejanje vrednosti podnaslova pri dvojnih tabelah
	$("div.grid_subtitle_inline").live('blur', function () {
		var spr_id = $(this).closest('div.spremenljivka_content').attr('spr_id');
		inline_subtitle_grid(spr_id, this);
	});
	
	// sortable na vrednosti
	$('div.spremenljivka_content').live('mouseover', function (event) {
		// tabele 
		var table = $(this).find('div.variable_holder table');
		if (table.length > 0 && !table.hasClass('variabla_vsota')) {
			if (!table.hasClass('ui-sortable')) {
				table.sortable({items: 'tr.variabla', handle: 'span.inline_move', stop: function() { $.post('ajax.php?t=vprasanjeinline&a=inline_vrednost_vrstni_red', {spremenljivka: $(this).closest('div.spremenljivka_content').attr('spr_id'), sortable: $(this).sortable('serialize'), anketa:srv_meta_anketa_id}); } }); 
			}
			
		} else {
			// nastavimo sortable vrednostim
			var div = $(this).find('div.variable_holder');
			if (div.length > 0) {
				if (!div.hasClass('ui-sortable')) {
					div.sortable({items: 'div.variabla', handle: 'span.inline_move', stop: function() { $.post('ajax.php?t=vprasanjeinline&a=inline_vrednost_vrstni_red', {spremenljivka: $(this).closest('div.spremenljivka_content').attr('spr_id'), sortable: $(this).sortable('serialize'), anketa:srv_meta_anketa_id}); } }); 
				}
			}
		}
		
	});
	
	// urejanje label min/max @ sliders
	$("div.label_inline").live('focus', function() {
		close_all_editing($(this).closest('.spremenljivka_content').attr('spr_id'));
		if ($(this).attr('default') == '1' && $(this).attr('contenteditable') == 'true') window.setTimeout( function() { document.execCommand('selectAll',false,null); }, 1 );
	}).live('keypress', function (event) {
		enterKeyPressHandler(event);
		$(this).attr('default', '0');
	}).live('blur', function () {
		var spr_id = $(this).attr('spr_id') || $(this).closest('div.spremenljivka_content').attr('spr_id');
		//inline_grid(spr_id, this);
		var tiplabele = $(this).attr('name');	//shranjuje tip labele oz. atribut name (MinLabel ali MaxLabel)
		inline_minmaxlabel(spr_id, this, tiplabele);
		//console.log(spr_id);
		//console.log("tiplabele: "+tiplabele);
	});
	
	// urejanje label podrocij @ sliders
	$("div.inline_labele_podrocij").live('focus', function() {
		close_all_editing($(this).closest('.spremenljivka_content').attr('spr_id'));
		if ($(this).attr('default') == '1' && $(this).attr('contenteditable') == 'true') window.setTimeout( function() { document.execCommand('selectAll',false,null); }, 1 );
	}).live('keyup', function (event) {	//prej bil keypress
		enterKeyPressHandler(event);
		$(this).attr('default', '0');
	}).live('blur', function () {
		var spr_id = $(this).attr('spr_id') || $(this).closest('div.spremenljivka_content').attr('spr_id');
		//inline_grid(spr_id, this);
		var tiplabele = $(this).attr('name');	//shranjuje tip labele oz. atribut name
		inline_labele_podrocij(spr_id, this, tiplabele);	//shrani labele v bazo
		//console.log(spr_id);
	});
	
	
	// urejanje custom opisnih label @ sliders
	$("div.inline_opisne_labele").live('focus', function() {
		close_all_editing($(this).closest('.spremenljivka_content').attr('spr_id'));
		if ($(this).attr('default') == '1' && $(this).attr('contenteditable') == 'true') window.setTimeout( function() { document.execCommand('selectAll',false,null); }, 1 );
	}).live('blur', function () {
		var spr_id = $(this).attr('spr_id') || $(this).closest('div.spremenljivka_content').attr('spr_id');
		//inline_grid(spr_id, this);
		var tiplabele = $(this).attr('name');	//shranjuje tip labele oz. atribut name		
		inline_opisne_labele(spr_id, this, tiplabele);	//shrani labele v bazo
		//console.log(spr_id);
	});
	
	// urejanje nadnaslovov @ traku
	$("div.trak_inline_nadnaslov").live('focus', function() {
		close_all_editing($(this).closest('.spremenljivka_content').attr('spr_id'));
		if ($(this).attr('default') == '1' && $(this).attr('contenteditable') == 'true') window.setTimeout( function() { document.execCommand('selectAll',false,null); }, 1 );
	}).live('keypress', function (event) {
		enterKeyPressHandler(event);
		$(this).attr('default', '0');
	}).live('blur', function () {
		var spr_id = $(this).attr('spr_id') || $(this).closest('div.spremenljivka_content').attr('spr_id');
		//inline_grid(spr_id, this);
		var tiplabele = $(this).attr('name');	//shranjuje tip labele oz. atribut name
		var grid = $(this).attr('grid');	//shranjuje grid
		trak_inline_nadnaslov(spr_id, this, tiplabele, grid);
		//console.log(spr_id);
	});

	// urejanje label za vsota
	$("div.variabla_vsota_inline").live('focus', function() {
		close_all_editing($(this).closest('.spremenljivka_content').attr('spr_id'));
		if ($(this).attr('default') == '1' && $(this).attr('contenteditable') == 'true') window.setTimeout( function() { document.execCommand('selectAll',false,null); }, 1 );
	}).live('keypress', function (event) {
		enterKeyPressHandler(event);
		$(this).attr('default', '0');
	}).live('blur', function () {
		var spr_id = $(this).attr('spr_id') || $(this).closest('div.spremenljivka_content').attr('spr_id');
		//var vsota = $(this).attr('name');	//shranjuje tip labele oz. atribut name (MinLabel ali MaxLabel)
		inline_variabla_vsota(spr_id, this);
		//console.log(spr_id);
	});
}

/**
* zbindamo clicke na vprasanje za inline urejanje
*/
function inline_bind_click(event) {
	
	var ta = $(event.target);
	var td;
	
	// kategorija - delete
	td = $(event.target).closest('span.inline_delete');
	if (td.hasClass('inline_delete')) {
		var spr = td.closest('div.spremenljivka_content').attr('spr_id');
		var vre = td.closest('.variabla').attr('id');
		vre = vre.replace('variabla_', '');
		inline_vrednost_delete(spr, vre, '0');
		return false;
	}
	
	// kategorija - edit
	td = $(event.target).closest('span.inline_edit');
	if (td.hasClass('inline_edit')) {
		var spr = td.closest('div.spremenljivka_content').attr('spr_id');
		var vre = td.closest('.variabla').attr('id');
		vre = vre.replace('variabla_', '');
		vrednost_edit(vre);
		return false;
	}

    // kategorija - hidden_answer
    td = $(event.target).closest('span.inline_hidden');
    if (td.hasClass('inline_hidden')) {
        var odg = td.attr('odg_vre');
        var odg_id = td.attr('odg_id');
        hidden_answer(odg, odg_id);
        return false;
    }
	
	// kategorija - correct_answer
    td = $(event.target).closest('span.correct');
    if (td.hasClass('correct')) {
        var spr_id = td.attr('spr_id');
        var vre_id = td.attr('vre_id');
        correct_answer(spr_id, vre_id);
        return false;
    }		
}

/**
* zbindamo clicke na nastavitvah vprasanja za inline urejanje
*/
function inline_vprasanje_float_editing_click(event) {
	var ta = $(event.target);
	var td;
	
	// brisanje obstojecega hotspot obmocja iz okna z nastavitvami
	td = ta.closest('span.inline_hotspot_delete_region');
	if (td.hasClass('inline_hotspot_delete_region')) {
		var spr = $('input[name=spremenljivka]').val();

		var region_index = td.closest('.hotspot_region').find('.hotspot_vrednost_inline').attr('region_index');
		var vre_id = td.closest('.hotspot_region').find('.hotspot_vrednost_inline').attr('vre_id');

		inline_hotspot_delete_region(spr, region_index, vre_id);
		return false;
	}
	
	// urejanje obstojecega hotspot obmocja iz okna z nastavitvami
	td = $(event.target).closest('span.inline_hotspot_edit_region');
	if (td.hasClass('inline_hotspot_edit_region')) {
		var spr = $('input[name=spremenljivka]').val();
		//var region_index = td.parent().attr('region_index');
		//var vre_id = td.parent().attr('vre_id');
		var region_index = td.closest('.hotspot_region').find('.hotspot_vrednost_inline').attr('region_index');
		var vre_id = td.closest('.hotspot_region').find('.hotspot_vrednost_inline').attr('vre_id');
		inline_hotspot_edit_region(spr, region_index, vre_id);
		return false;
	}
	
	//urejanje slike iz okna z nastavitvami, ko imamo ikonico s sliko
/* 	td = $(event.target).closest('span.inline_edit_hotspot');
	if (td.hasClass('inline_edit_hotspot')) {		
		var spr_id = $('input[name=spremenljivka]').val();
		hotspot_edit(spr_id);
		return false;
	} */
}

// shrani naslov vprasanja
function inline_naslov (spremenljivka, _this) {
	
	if ($(_this).attr('contenteditable') != 'true') return;
	
	// Pocistimo text pri copy/paste
	if(pasteFromWord == true){
		inline_clear_paste(_this);	
	}

	// shranimo 
	if (parseInt(spremenljivka) == -1) {	// uvod
		$.post('ajax.php?t=vprasanje&a=vprasanje_save', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, introduction: $(_this).html()});
	} else if (parseInt(spremenljivka) == -2) {		// zakljucek
		$.post('ajax.php?t=vprasanje&a=vprasanje_save', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, conclusion: $(_this).html()});
	} else {				// vprasanje
		$.post('ajax.php?t=vprasanje&a=vprasanje_save', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, naslov: $(_this).html()});
	}
}

// shrani naslov vprasanja
function inline_textfield (spremenljivka, _this) {
	
	if ($(_this).attr('contenteditable') != 'true') return;
	
	// Pocistimo text pri copy/paste
	if(pasteFromWord == true){
		inline_clear_paste(_this);	
	}

	// shranimo 
	if (parseInt(spremenljivka) == -1) {	// uvod
		$.post('ajax.php?t=vprasanje&a=vprasanje_save', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, introduction: $(_this).html()});
	} else if (parseInt(spremenljivka) == -2) {		// zakljucek
		$.post('ajax.php?t=vprasanje&a=vprasanje_save', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, conclusion: $(_this).html()});
	} else {				// vprasanje
		$.post('ajax.php?t=vprasanje&a=vprasanje_save', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, naslov: $(_this).html()});
	}
}

// shrani vsebino za hotspot vprasanje
function inline_hotspot (spremenljivka, _this) {
	
	//if ($(_this).attr('contenteditable') != 'true') return;
	
	// Pocistimo text pri copy/paste
	if(pasteFromWord == true){
		inline_clear_paste(_this);	
	}

	// shranimo 
	$.post('ajax.php?t=vprasanjeinline&a=inline_vrednost_naslov_save', {spremenljivka:spremenljivka, vrednost: $(_this).attr('vre_id'), anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, naslov: $(_this).html()} );
}

// shrani info - opombo vprasanja
function inline_info (spremenljivka, _this) {
	
	if ($(_this).attr('contenteditable') != 'true') return;
	
	// Pocistimo text pri copy/paste
	if(pasteFromWord == true){
		inline_clear_paste(_this);	
	}
	
	$.post('ajax.php?t=vprasanjeinline&a=inline_info_save', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, info: $(_this).html()});
}

// shrani ime variable za vrednost
function inline_vrednost_variable(vre_id, _this) {
	
	if ($(_this).attr('contenteditable') != 'true') return;
	
	$.post('ajax.php?t=vprasanjeinline&a=inline_vrednost_variable_save', {vre_id: vre_id, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, variable: $(_this).html()});
	
}

// shrani variablo vprasanja
function inline_variable (spremenljivka, _this) {
	
	if ($(_this).attr('contenteditable') != 'true') return;
	
	$.post('ajax.php?t=vprasanjeinline&a=inline_variable_save', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, variable: $(_this).html()});
	
}


// shrani naslov vrednosti
function inline_vrednost (spremenljivka, _this) {
	if ($(_this).attr('contenteditable') != 'true') return;

    // Pocistimo text pri copy/paste
	if(pasteFromWord == true){
		inline_clear_paste(_this);	
	}
	//var vnosi = $('#spremenljivka_contentdiv3444 div.variabla').length;
	//var vnosi = $('#variable_holder div.variabla').length;
	//var vnosi = $('.edit_mode div.variabla').length;
	var vnosi = $('.edit_mode div.vrednost_inline').length;
	//console.log(spremenljivka);
	//console.log(vnosi);

        // dodatek may: shranimo vrednost textaree in inputa pri text fieldih
        if ($(_this).attr('ETF') == 'true') {
            $.post('ajax.php?t=vprasanjeinline&a=inline_vrednost_naslov_save', {spremenljivka:spremenljivka, vrednost: $(_this).attr('vre_id'), anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, naslov: $(_this).val()} );
        }
        else {
            $.post('ajax.php?t=vprasanjeinline&a=inline_vrednost_naslov_save', {spremenljivka:spremenljivka, vrednost: $(_this).attr('vre_id'), anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, naslov: $(_this).html()} );
        }
	//$.post('ajax.php?t=vprasanjeinline&a=inline_vrednost_naslov_save', {spremenljivka:spremenljivka, vrednost: $(_this).attr('vre_id'), anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, naslov: $(_this).html(), sbSize: vnosi});
}

// shrani ime hotspot obmocja
function inline_hotspot_vrednost (spremenljivka, _this) {
	
	if ($(_this).attr('contenteditable') != 'true') return;
	
	// Pocistimo text pri copy/paste
	if(pasteFromWord == true){
		inline_clear_paste(_this);	
	}
	//var vnosi = $('#spremenljivka_contentdiv3444 div.variabla').length;
	//var vnosi = $('#variable_holder div.variabla').length;
	//var vnosi = $('.edit_mode div.variabla').length;
	var vnosi = $('.edit_mode div.vrednost_inline').length;
	//console.log(spremenljivka);
	//console.log(vnosi);
	$.post('ajax.php?t=vprasanjeinline&a=inline_hotspot_vrednost_save', {spremenljivka:spremenljivka, vrednost: $(_this).attr('vre_id'), anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, naslov: $(_this).html()} );
	//$.post('ajax.php?t=vprasanjeinline&a=inline_vrednost_naslov_save', {spremenljivka:spremenljivka, vrednost: $(_this).attr('vre_id'), anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, naslov: $(_this).html(), sbSize: vnosi});
}
// shrani grid vprasanja
function inline_grid (spremenljivka, _this) {
	
	if ($(_this).attr('contenteditable') != 'true') return;

	// Pocistimo text pri copy/paste
	if(pasteFromWord == true){
		inline_clear_paste(_this);	
	}

	// String zaenkrat pustimo kot html
	//var string = $(_this).text();
	var string = $(_this).html();	
	
	// pri dvojni tabeli popravimo se desna polja
	$('#branching_'+spremenljivka+' td.double[grd=g_'+$(_this).attr('grd_id')+']').html(string);	
	$('#vprlang_'+spremenljivka+' td.double[grd=g_'+$(_this).attr('grd_id')+']').html(string);
	
	$.post('ajax.php?t=vprasanjeinline&a=inline_grid_naslov_save', {spremenljivka: spremenljivka, grid: $(_this).attr('grd_id'), anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, naslov: string});
}

// shrani vrednost grida
function inline_variable_grid (spremenljivka, _this) {
	
	if ($(_this).attr('contenteditable') != 'true') return;
	
	$.post('ajax.php?t=vprasanjeinline&a=inline_grid_variable_save', {spremenljivka: spremenljivka, grid: $(_this).attr('grd_id'), anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, variable: $(_this).html()});
}

// shrani vrednost podnaslova pri dvojni tabeli
function inline_subtitle_grid (spremenljivka, _this) {
	
	if ($(_this).attr('contenteditable') != 'true') return;
	
	// Pocistimo text pri copy/paste
	if(pasteFromWord == true){
		inline_clear_paste(_this);	
	}
	//$.post('ajax.php?t=vprasanjeinline&a=inline_grid_subtitle_save', {spremenljivka: spremenljivka, subtitle: $(_this).attr('grid_subtitle'), anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, value: $(_this).html()});
	$.post('ajax.php?t=vprasanjeinline&a=inline_grid_subtitle_save', {spremenljivka: spremenljivka, subtitle: $(_this).attr('grid_subtitle'), anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, value: $(_this).text(), grid_id: $(_this).attr('grid_id')});
}

// pobrise vrednost (v srv_vrednost)
function inline_vrednost_delete(spremenljivka, vrednost, confirmed/*, tip, other*/, callback) {
	
	text = lang['srv_brisivrednostconfirm'];
        //ce je posebna vrednost, se rabi dodat callbaack in se lahko brise tudi zadnja vednost
        //special vrednost je recimo marker na mapi - ni html taga z id="variabla_"+vrednost
        var special_vrednost = ($("#variabla_"+vrednost).length < 1);
	
	if ( confirmed==1 || confirm(text) ) {
		if (confirmed == undefined) confirmed = 1;
		
		$.post('ajax.php?t=vprasanje&a=vrednost_delete', {spremenljivka: spremenljivka, vrednost: vrednost, confirmed: confirmed, 
                    /*other: other,*/ anketa: srv_meta_anketa_id, can_delete_last: special_vrednost ? 1 : 0}, 
			function(data) {
				if (!data) return;

				/* TODO if(tip == 17) edit_ranking_moznosti(); */

                if (data.error == 0) {
                        //ce je navadna vrednost, kot rec. radio ali checkbox
                	if(!special_vrednost){
                	// popravimo se chekbox za missinge
					var other = $("#variabla_"+vrednost).attr('other');
					if (other != '0') {
						$("#missing_value_"+other).attr('checked', false);						
					}

					// odstranimo element
					$('#variabla_'+vrednost).remove();
					
					//skrij moznost urejanja textarea za Drugo:, ce jo je uporabnik zbrisal
					if (other == '1'){
						$('#fieldset'+spremenljivka).hide();
					}
                        }
                        //ce je posebna vrednost, kot rec. marker na mapi
                        else{
                            callback();
                        }
					
					
                	
                } 
                else if (data.error == 1) {
                    $('#fade').fadeIn("fast");
        			$('#dropped_alert').html(data.output).fadeIn("fast").animate({opacity: 1.0}, 3000).fadeOut("slow");
                } 
                else if (data.error == 2) {

                    $('#fade').fadeIn("fast");
                    $('#dropped_alert').html(data.output).fadeIn("fast").css('width', '400px');

                    //ce je posebna vrednost, se rabi dodat callbaack
                    if(special_vrednost){
                        //treba je dodati callback - sori, vem da je grdo - glej funkcijo ajax_vrednost_delete v class.Vprasanje.php
                        document.getElementById('brisivrednostchecked').onclick = 
                                function(){inline_vrednost_delete(spremenljivka, vrednost, 1, callback); $('#dropped_alert').html('').hide(); return false;}
                        
                        $('#fade').fadeOut("fast");
                    }
                }
			}, 'json'
		);
		
	}
	
}

// narise polje za dodajanje vrednosti
function inline_nova_vrednost (_this) {

	// ce je anketa zaklenjena
	if ($(_this).attr('contenteditable') != 'true') return;

    // Ce smo na mobitelu tega ni
	if ($('.mobile_header:visible').length != 0) return;
	
	// ce imamo v edit modu vprasanja same default odgovore (to je ob novem vprasanju in ce se nic ne spreminja)
	var variable_holder = $(_this).closest('.variable_holder');
	var _default = 1;
	variable_holder.children().each(function () {
		if ( $(this).find('.vrednost_inline').attr('default') != '1' ) _default = 0;
	});
	if (_default == 1) return;
	
	var tip = $(_this).closest('.spremenljivka_content').attr('tip');
    
    // Disablamo ife, ce nima ustreznega paketa
    if ($('#commercial_package').attr('value') == '1') {
        var if_class_locked = 'user_access_locked';
    }
    else{
        var if_class_locked = '';
    }
		
	// ce je zadnje polje in ce lahko znotraj tega parenta dodajamo vrednosti
	if ( $(_this).closest('.variabla').is(':last-child') && $(_this).closest('.variabla').parent().hasClass('allow_new') ) {

		
		// ce ni prazno (da pri novem ne dodamo takoj) && ce je attr new tudi ne dodamo, ker pocakamo da se zgenerira nov id
		if ($(_this).html() != '' && $(_this).attr('vre_id') != 'new')  {
			
			var spremenljivka = $(_this).closest('.spremenljivka_content').attr('spr_id'); //dobimo id spremenljivke
			var orientation = $('#spremenljivka_content_'+spremenljivka).attr('spr_orientation'); //dobimo orientacijo iz dodanega parametra, ker iz prejsnje varianta ne gre, ko je urejevalno okno zaprto
			var enota = $('#spremenljivka_content_'+spremenljivka).attr('spr_enota'); //dobimo orientacijo iz dodanega parametra, ker iz prejsnje varianta ne gre, ko je urejevalno okno zaprto
			
			// radio, checkbox, roleta
			if (tip <= 3) {
				
				if(tip <= 3 && orientation != 7 && orientation != 8){
					var new_div = 	'<div id="variabla_new" class="variabla after_'+$(_this).attr('vre_id')+'">'+
										'<span class="faicon move_updown inline inline_move" title=""></span>';
					
					//if (tip <= 2)
					if (tip <= 2 && orientation != 6)
                       new_div +=		'<input id="foo_new" class="enka-admin-custom enka-inline" type="'+(tip==1?'radio':'checkbox')+'" value="" name="foo_new" /><span class="enka-checkbox-radio"></span>';
                       
						// Ikona za hiter upload slike (ce je vklopljena)
						if($(_this).closest('.variabla').parent().find('.image_upload').length){
							new_div +=		'<span class="sprites image_upload pointer" onclick="vrednost_insert_image(\''+$(_this).attr('vre_id')+'\', true); return false;" title="'+lang['upload_img2']+'"></span>';
							new_div +=		'<div id="vre_id_new" class="vrednost_inline" contenteditable="true" tabindex="1" default="1" vre_id="new">'+lang['srv_new_vrednost']+'</div>'+
										' <span class="inline_other pointer" onclick="vrednost_new(\''+spremenljivka+'\', \'1\', \'\'); $(\'#fieldset'+spremenljivka+'\').show();"><span class="faicon add small icon-as_link" title="'+'"></span> '+lang['srv_novavrednost_drugo']+'</span>'+
										' <span class="faicon delete small inline inline_delete" title="'+lang['srv_brisivrednost']+'"></span>'+
										(($(_this).closest('.variabla').parent().find('.correct').length) ? ' <span class="faicon correct inline" spr_id="'+spremenljivka+'" vre_id="\''+$(_this).attr('vre_id')+'\'" title="'+lang['srv_vrednost_correct']+'"></span>' : '')+
                                        ' <span class="faicon odg_hidden inline inline_hidden" odg_vre="0" odg_id="new" title="'+lang['srv_hide-disable_answer-0']+'"></span>'+
                                        ' <span class="faicon odg_if_follow inline inline_if_follow '+if_class_locked+'" onclick="follow_up_condition(\'new\'); return false;" title="'+lang['srv_follow_up']+'"></span>'+
                                        ' <span class="faicon odg_if_not inline inline_if_not '+if_class_locked+'" onclick="vrednost_condition_editing(\'new\'); return false;" title="'+lang['srv_podif_edit']+'"></span>'+
										' <span class="faicon edit2 inline inline_edit"></span>'+
								    	'</div>';
						}
						else{
							new_div +=		'<div id="vre_id_new" class="vrednost_inline" contenteditable="true" tabindex="1" default="1" vre_id="new">'+lang['srv_new_vrednost']+'</div>'+
										' <span class="inline_other pointer" onclick="vrednost_new(\''+spremenljivka+'\', \'1\', \'\'); $(\'#fieldset'+spremenljivka+'\').show();"><span class="faicon add small icon-as_link" title="'+'"></span> '+lang['srv_novavrednost_drugo']+'</span>'+
										' <span class="faicon delete small inline inline_delete" title="'+lang['srv_brisivrednost']+'"></span>'+
										(($(_this).closest('.variabla').parent().find('.correct').length) ? ' <span class="faicon correct inline" spr_id="'+spremenljivka+'" vre_id="\''+$(_this).attr('vre_id')+'\'" title="'+lang['srv_vrednost_correct']+'"></span>' : '')+
                                        ' <span class="faicon odg_hidden inline inline_hidden" odg_vre="0" odg_id="new" title="'+lang['srv_hide-disable_answer-0']+'"></span>'+
                                        ' <span class="faicon odg_if_follow inline inline_if_follow '+if_class_locked+'" onclick="follow_up_condition(\'new\'); return false;" title="'+lang['srv_follow_up']+'"></span>'+
                                        ' <span class="faicon odg_if_not inline inline_if_not '+if_class_locked+'" onclick="vrednost_condition_editing(\'new\'); return false;" title="'+lang['srv_podif_edit']+'"></span>'+
										' <span class="faicon edit2 inline inline_edit"></span>'+
								    	'</div>';
						}
				}
				else if(tip <= 3 && orientation == 7){
					if (tip <= 2 && orientation != 6){
					
						var new_div = 	'<div id="variabla_new" class="variabla after_'+$(_this).attr('vre_id')+'">'+
											'<span class="faicon move_updown inline inline_move" title=""></span>';										
						
						new_div +=			'<div id="vre_id_new" class="vrednost_inline" contenteditable="true" tabindex="1" default="1" vre_id="new">'+lang['srv_new_vrednost']+'</div>'+
											' <span class="inline_other pointer" onclick="vrednost_new(\''+spremenljivka+'\', \'1\', \'\'); $(\'#fieldset'+spremenljivka+'\').show();"><span class="faicon add small icon-as_link" title="'+'"></span> '+lang['srv_novavrednost_drugo']+'</span>'+
											' <input id="foo_new" type="'+(tip==1?'radio':'checkbox')+'" value="" name="foo_new" />'+
                                            ' <span class="faicon odg_hidden inline inline_hidden" odg_vre="0" odg_id="new" title="'+lang['srv_hide-disable_answer-0']+'"></span>'+
                                            ' <span class="faicon odg_if_follow inline inline_if_follow '+if_class_locked+'" onclick="follow_up_condition(\'new\'); return false;" title="'+lang['srv_follow_up']+'"></span>'+
                                            ' <span class="faicon odg_if_not inline inline_if_not '+if_class_locked+'" onclick="vrednost_condition_editing(\'new\'); return false;" title="'+lang['srv_podif_edit']+'"></span>'+
											' <span class="faicon delete small inline inline_delete" title="'+'"></span>'+
											' <span class="faicon edit2 inline inline_edit"></span>'+
										'</div>';
					}
				}
				else if(tip <= 2 && orientation == 8){	//ce imamo radio ali checkbox z drag-drop
						// Ikona za hiter upload slike (ce je vklopljena) - V DELU...
						var new_div = 	'<div id="variabla_new" class="variabla after_'+$(_this).attr('vre_id')+'">';
						
						if($(_this).closest('.variabla').parent().find('.image_upload').length){
							new_div +=	'<span class="sprites image_upload pointer" onclick="vrednost_insert_image(\''+$(_this).attr('vre_id')+'\', true); return false;" title="'+lang['upload_img2']+'"></span>'+
										'<span class="faicon move_updown inline inline_move" title=""></span>'+
										' <span class="faicon delete small inline inline_delete" title="'+'"></span>'+
										' <span class="faicon odg_hidden inline inline_hidden" odg_vre="0" odg_id="new" title="'+lang['srv_hide-disable_answer-0']+'"></span>'+
										' <span class="faicon odg_if_not inline inline_if_not '+if_class_locked+'" onclick="vrednost_condition_editing(\'new\'); return false;" title="'+lang['srv_podif_edit']+'"></span>'+
										' <span class="faicon edit2 inline inline_edit"></span>'+
										'<div id="vre_id_new" class="vrednost_inline ranking" style="float:none" contenteditable="true" tabindex="1" default="1" vre_id="new">'+lang['srv_new_vrednost']+'</div>'+
										'</div>';
						} else{
							new_div += 	'<span class="faicon move_updown inline inline_move" title=""></span>'+
										' <span class="faicon delete small inline inline_delete" title="'+'"></span>'+
										' <span class="faicon odg_hidden inline inline_hidden" odg_vre="0" odg_id="new" title="'+lang['srv_hide-disable_answer-0']+'"></span>'+
										' <span class="faicon odg_if_not inline inline_if_not '+if_class_locked+'" onclick="vrednost_condition_editing(\'new\'); return false;" title="'+lang['srv_podif_edit']+'"></span>'+
										' <span class="faicon edit2 inline inline_edit"></span>'+
										'<div id="vre_id_new" class="vrednost_inline ranking" style="float:none" contenteditable="true" tabindex="1" default="1" vre_id="new">'+lang['srv_new_vrednost']+'</div>'+
										'</div>';
						}			
				}
 			//} else if(tip == 6 && enota == 9){	//ce imamo drag-drop v gridu
			} else if( (tip == 6 || tip == 16) && enota == 9){	//ce imamo drag-drop v gridu (tabela en ali vec odgovorov)
			//drag and drop grid
					var new_div = 	'<div id="variabla_new" class="variabla after_'+$(_this).attr('vre_id')+'">'+
					'<span class="faicon move_updown inline inline_move" title=""></span>'+
					' <span class="faicon delete small inline inline_delete" title="'+'"></span>'+
					' <span class="faicon odg_hidden inline inline_hidden" odg_vre="0" odg_id="new" title="'+lang['srv_hide-disable_answer-0']+'"></span>'+
					' <span class="faicon odg_if_not inline inline_if_not '+if_class_locked+'" onclick="vrednost_condition_editing(\'new\'); return false;" title="'+lang['srv_podif_edit']+'"></span>'+
					' <span class="faicon edit2 inline inline_edit"></span>'+
					'<div id="vre_id_new" class="vrednost_inline ranking" style="float:none" contenteditable="true" tabindex="1" default="1" vre_id="new">'+lang['srv_new_vrednost']+'</div>'+
					'</div>';
			// ranking
			} else if (tip == 17) {
                
                // ostevilcevanje
				if ($(_this).closest('.variabla').find('select').length > 0) {		

					var new_div = 	'<div id="variabla_new" class="variabla after_'+$(_this).attr('vre_id')+'">'+
										'<span class="faicon move_updown inline inline_move" title=""></span>'+
										' <span class="faicon delete small inline inline_delete" title="'+'"></span>'+
                                        ' <span class="faicon odg_hidden inline inline_hidden" odg_vre="0" odg_id="new" title="'+lang['srv_hide-disable_answer-0']+'"></span>'+
                                        ' <span class="faicon odg_if_not inline inline_if_not '+if_class_locked+'" onclick="vrednost_condition_editing(\'new\'); return false;" title="'+lang['srv_podif_edit']+'"></span>'+
										' <span class="faicon edit2 inline inline_edit"></span>'+
										' <select style="width:50px; margin-top:0; float:left;"> '+
										'   <option></option> '+
										' </select> '+
										'<div id="vre_id_new" class="vrednost_inline" contenteditable="true" tabindex="1" default="1" vre_id="new">'+lang['srv_new_vrednost']+'</div>'+
									'</div>';
					
                } 
                // premikanje in prestavljanje
                else {	
					
					var new_div = 	'<div id="variabla_new" class="variabla after_'+$(_this).attr('vre_id')+'">'+
										'<span class="faicon move_updown inline inline_move" title=""></span>'+
										' <span class="faicon delete small inline inline_delete" title="'+'"></span>'+
                                        ' <span class="faicon odg_hidden inline inline_hidden" odg_vre="0" odg_id="new" title="'+lang['srv_hide-disable_answer-0']+'"></span>'+
                                        ' <span class="faicon odg_if_not inline inline_if_not '+if_class_locked+'" onclick="vrednost_condition_editing(\'new\'); return false;" title="'+lang['srv_podif_edit']+'"></span>'+
										' <span class="faicon edit2 inline inline_edit"></span>'+
										'<div id="vre_id_new" class="vrednost_inline ranking" style="float:none" contenteditable="true" tabindex="1" default="1" vre_id="new">'+lang['srv_new_vrednost']+'</div>'+
									'</div>';
				}
			
			// vsota
			} else if (tip == 18) {
						
				var new_div = 	'<div id="variabla_new" class="variabla variabla_vsota after_'+$(_this).attr('vre_id')+'" style="width:100%">'+
									'<span class="faicon move_updown inline inline_move" title=""></span>'+
									' <span class="faicon delete small inline inline_delete" title="'+'"></span>'+
                                    ' <span class="faicon odg_hidden inline inline_hidden" odg_vre="0" odg_id="new" title="'+lang['srv_hide-disable_answer-0']+'"></span>'+
                                    ' <span class="faicon odg_if_not inline inline_if_not '+if_class_locked+'" onclick="vrednost_condition_editing(\'new\'); return false;" title="'+lang['srv_podif_edit']+'"></span>'+
									' <span class="faicon edit2 inline inline_edit"></span>'+
									'<div id="vre_id_new" class="vrednost_inline" style="width:111px" contenteditable="true" tabindex="1" default="1" vre_id="new">'+lang['srv_new_vrednost']+'</div>'+
									' <input type="text" name="foo_" maxlength="8" size="5">'+
								'</div>';
		
			// tabele			
			} else {
				
				var new_div = $( '<tr id="variabla_new" class="variabla after_'+$(_this).attr('vre_id')+'">' + $(_this).closest('.variabla').html() + '</tr>' );
				new_div.find('*').removeAttr('id');
				
				new_div.find('div').attr('id', 'vre_id_new').attr('vre_id', 'new').attr('default', '1').html(lang['srv_new_vrednost']);
				
				// odstani input polje drugo, ce dodajamo za poljem drugo
				new_div.find('input[type=text]').remove();
				new_div.find('span.inline_other').remove();
				new_div.find('span.red').remove();
				
				// dodamo opcijo za drugo
				new_div.find('td:first-child').append(' <span class="inline_other pointer" onclick="vrednost_new(\''+$(_this).closest('.spremenljivka_content').attr('spr_id')+'\', \'1\', \'\');"><span class="faicon add small icon-as_link" title="'+'"></span> '+lang['srv_novavrednost_drugo']+'</span>');
				
				if (new_div.find('div').length > 1){
					//new_div.find('div:last').attr('id', 'vre_id_new_2').attr('vre_id', 'new_2').html('');
					new_div.find('div:last').attr('id', 'vre_id_new_2').attr('vre_id', 'new_2').attr('default', '1').html(lang['srv_new_vrednost']);
				}	
			}
			
			$(_this).closest('.variabla').parent().append(new_div);
		}
	}	
}

// skrije (odstrani) polje za dodajanje vrednosti
function inline_nova_vrednost_hide (spr_id, vre_id) {
	
	// v editing modu vprasanja, nove vrednosti ne zapiramo, ker je vedno odprta
	if ( $('#branching_'+spr_id).hasClass('spr_editing') ) return;
	
	var div_new = $("#spremenljivka_content_"+spr_id).find('#variabla_new');
	
	// ce smo urejali zadnjo opcijo, odstranimo polje za dodajanje
	if ( div_new.hasClass('after_'+vre_id) && !div_new.hasClass('inlineedit') ) {
		div_new.remove();
	}
	
	// ce smo kliknili na polje za dodajanje in nismo nicesar vpisali, ga tudi odstranimo
	if ( vre_id == 'new' && !div_new.hasClass('inlineedit') ) {
		div_new.remove();
	}
	
}

// Pocisti text ce je bil pastan
function inline_clear_paste(_this){

	// text ki je bil pastan
	var temp = $(_this).html();
	
	// pocistimo tage - dodamo <p> ce je slucajno prazen - drugace vrne prazen string
	temp = $('<p>'+temp+'</p>').text();

	// shranimo nazaj
	$(_this).html(temp);
	
	pasteFromWord = false;
}

// doda novo vrednost (mv = missing value)
/*function inline_vrednost_new (spremenljivka, other, tip, mv) {
	
	$.post('ajax.php?t=vprasanjeinline&a=inline_vrednost_new', {spremenljivka: spremenljivka, other: other, anketa: srv_meta_anketa_id, mv:mv}, function (data) {
		
		if ( $('#vprasanje_preview').is(':visible') ) {
			$('#vprasanje_preview').html(data);
		} else {
			$('#branching_'+spremenljivka+'').html(data);
		}
		$('#spremenljivka_content_'+spremenljivka+' div.variable_holder div').last().click();
		//$('#vprasanje_edit').attr({scrollTop: $('#vprasanje_edit').height()});
		//if(tip == 17) edit_ranking_moznosti();
	
	});
	
}*/

/**
* nastavi editor na contenteditable element za naslov
*/
function inline_load_editor (_this) {
	$(_this).css('display', 'none');
	var el = $(_this).parent().find('div[contenteditable].naslov_inline');
	if (el.attr('contenteditable') == 'true') {
		
		var spr_id = el.attr('spr_id');
        var def_text = el.attr('default');
        var def_pogoj = '';
        if(def_text == 1)
            var def_pogoj = 'default="'+def_text+'"';

		el.replaceWith('<textarea name="naslov_'+spr_id+'" id="naslov_'+spr_id+'" style="width:99%" '+def_pogoj+'>'+el.html()+'</textarea>'+
		'<span class="buttonwrapper floatLeft" style="margin:5px 0"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inline_save_editor(\''+spr_id+'\'); return false;">'+lang['save']+'</a></span>');
		
		editor_display(spr_id);
	}
	
}

function inline_load_editor_hotspot (_this, vre_id) {
	//console.log(_this);
	$(_this).css('display', 'none');
	//var el = $(_this).parent().find('div[contenteditable].naslov_inline');
	
//	echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline_hotspot " contenteditable="false" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_hot_spot_image'])!==false || strpos($row1['naslov'], $lang1['srv_hot_spot_image'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'].'</div>';
	
	var el = $(_this).parent().find('.vrednost_inline_hotspot');
	//if (el.attr('contenteditable') == 'true') {		
		var spr_id = el.attr('spr_id');
		var def_text = el.attr('default');
        var def_pogoj = '';
        if(def_text == 1)
            var def_pogoj = 'default="'+def_text+'"';
		
		//el.replaceWith('<textarea name="hotspot_image_'+spr_id+'" id="hotspot_image_'+spr_id+'" style="width:99%">'+el.html()+'</textarea>'+
		//'<span class="buttonwrapper floatLeft" style="margin:5px 0"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inline_save_editor_hotspot(\''+spr_id+'\'); return false;">'+lang['save']+'</a></span>');
		
		el.replaceWith('<textarea name="hotspot_image_'+vre_id+'" id="hotspot_image_'+vre_id+'" style="width:99%" '+def_pogoj+'>'+el.html()+'</textarea>'+
		'<span class="buttonwrapper floatLeft" style="margin:5px 0"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inline_save_editor_hotspot(\''+vre_id+'\', \''+spr_id+'\'); return false;">'+lang['save']+'</a></span>');
		
		editor_display_hotspot(vre_id);
	//}
	//console.log("HotSpot editor");
	
}

function inline_save_editor_hotspot(vre_id, spr_id, postsave) {
	
	get_editor_close('hotspot_image_'+vre_id);
	
	var el = $('#hotspot_image_'+vre_id);
	var parent = el.parent();
	
	el.replaceWith('<div id="vre_id_'+vre_id+'" vre_id="'+vre_id+'" class="vrednost_inline_hotspot " contenteditable="false" spr_id="'+spr_id+'" tabindex="1">'+el.val()+'</div>');
	parent.find('span.buttonwrapper').remove();
	
 	if ( postsave != false )
		inline_hotspot(spr_id, parent.find('div.vrednost_inline_hotspot'));
		
	$('#spremenljivka_content_'+spr_id+' span.inline_edit_hotspot').css('display', 'inline-block');
}

/**
* shrani editor in nastavi nazaj contenteditable
*/
function inline_save_editor(spr_id, postsave) {
	
	get_editor_close('naslov_'+spr_id);
	
	var el = $('#naslov_'+spr_id);
	var parent = el.parent();
	
	el.replaceWith('<div class="naslov naslov_inline" contenteditable="true" spr_id="'+spr_id+'" tabindex="1">'+el.val()+'</div>');
	parent.find('span.buttonwrapper').remove();
	
	if ( postsave != false )
		inline_naslov(spr_id, parent.find('div.naslov_inline'));
		
	$('#spremenljivka_content_'+spr_id+' span.display_editor').css('display', 'inline-block');
}

/**
* na contenteditable ob tipki Enter vstavi <br>, ker drugace dela vsak po svoje, FF pa sploh ne dela
*/
function enterKeyPressHandler(evt) {
    var sel, range, br, addedBr = false;
    evt = evt || window.event;
    var charCode = evt.which || evt.keyCode;
    if (charCode == 13) {
        if (typeof window.getSelection != "undefined") {
            sel = window.getSelection();
            if (sel.getRangeAt && sel.rangeCount) {
                range = sel.getRangeAt(0);
                range.deleteContents();
                br = document.createElement("br");
                range.insertNode(br);
                range.setEndAfter(br);
                range.setStartAfter(br);
                sel.removeAllRanges();
                sel.addRange(range);
                addedBr = true;
            }
        } else if (typeof document.selection != "undefined") {
            sel = document.selection;
            if (sel.createRange) {
                range = sel.createRange();
                range.pasteHTML("<br>");
                range.select();
                addedBr = true;
            }
        }

        // If successful, prevent the browser's default handling of the keypress
        if (addedBr) {
            if (typeof evt.preventDefault != "undefined") {
                evt.preventDefault();
            } else {
                evt.returnValue = false;
            }
        }
    }
}

// shrani label min/max @ sliders
function inline_minmaxlabel (spremenljivka, _this, tiplabele) {
	
	if ($(_this).attr('contenteditable') != 'true') return;
	
	// Pocistimo text pri copy/paste
	if(pasteFromWord == true){
		inline_clear_paste(_this);	
	}
	
	//posljemo podatke v funkcijo za dinamicno shranjevanje
	//$.post('ajax.php?t=vprasanjeinline&a=inline_grid_naslov_save', {spremenljivka: spremenljivka, grid: $(_this).attr('grd_id'), anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, label: $(_this).html()});	
	$.post('ajax.php?t=vprasanjeinline&a=inline_label_save', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, tiplabele: tiplabele, label: $(_this).html()});	
	//vprasanje_save(true, spremenljivka);
	//vprasanje_save(true);
}

// shrani labele podrocij @ sliders
function inline_labele_podrocij(spremenljivka, _this, tiplabele){
	if ($(_this).attr('contenteditable') != 'true') return;	
	// Pocistimo text pri copy/paste
	if(pasteFromWord == true){
		inline_clear_paste(_this);	
	}
	//posljemo podatke v funkcijo za dinamicno shranjevanje
	$.post('ajax.php?t=vprasanjeinline&a=inline_labele_podrocij_save', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, tiplabele: tiplabele, label: $(_this).html()});	
}

// shrani in posodobi custom opisne labele @ sliders
function inline_opisne_labele(spremenljivka, _this, tiplabele){
	if ($(_this).attr('contenteditable') != 'true') return;	
	// Pocistimo text pri copy/paste
	if(pasteFromWord == true){
		inline_clear_paste(_this);	
	}
	//posljemo podatke v funkcijo za dinamicno shranjevanje
	$.post('ajax.php?t=vprasanjeinline&a=inline_opisne_labele_save', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, tiplabele: tiplabele, label: $(_this).html()});
}

// shrani labelo za variabla vsota
function inline_variabla_vsota (spremenljivka, _this) {
	
	if ($(_this).attr('contenteditable') != 'true') return;
	
	// Pocistimo text pri copy/paste
	if(pasteFromWord == true){
		inline_clear_paste(_this);	
	}
	
	$.post('ajax.php?t=vprasanjeinline&a=inline_variabla_vsota_save', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, inline_variabla_vsota: $(_this).html()});
}

//funkcija za brisanje obstojecega obmocja
function inline_hotspot_delete_region(spr_id, region_index, vre_id){
	//console.log("Brisanje območja s spr_id: "+spr_id+", indeksom: "+region_index+" in vrednostjo: "+vre_id);
	
	//posljemo podatke v funkcijo za dinamicno brisanje podatkov iz baze
	$.post('ajax.php?t=vprasanjeinline&a=inline_hotspot_delete_region', {spr_id: spr_id, region_index: region_index, vre_id: vre_id});
	
	//odstrani iz okna z nastavitvami ime obmocja
	//$('#hotspot_region_name_'+region_index).remove();
	$('#hotspot_region_'+region_index).remove();
	
	//skrij nastavitve obmocij za heatmap
	var heatmap_region_settings = true;
	$('#hot_spot_fieldset_'+spr_id).children().each(function(){	//preleti obmocja
		heatmap_region_settings = $('#hot_spot_fieldset_'+spr_id).children().hasClass('hotspot_region');
	});
	if(heatmap_region_settings){
		$('#heatmap_region_settings_'+spr_id).css('display', '');	//pokazi nastavitve obmocja
	}else{			
		$('#heatmap_region_settings_'+spr_id).css('display', 'none');	//skrij nastavitve obmocja
	}

	//odstrani div z vrednostjo, kjer je prisotno ime obmocja
	$('#variabla_'+vre_id).remove();
	
	//update vrednosti
	var vrednost = $('input[name=vrednost]').val();			
	$.post('ajax.php?t=vprasanje&a=vrednost_save', $("form[name=vrednost_edit]").serialize(), function (data) {		
		$('#vre_id_'+vrednost).html(data);
		$('#vrednost_edit').html('');
		vprasanje_save(true);
	});
	
	//update variable in vrstni_red v srv_hotspot_regions
	//$.post('ajax.php?t=vprasanjeinline&a=inline_hotspot_update_region', {spr_id: spr_id, vre_id: vre_id});
	$.post('ajax.php?t=vprasanjeinline&a=inline_hotspot_update_region', {spr_id: spr_id});
	
}

//funkcija za preurejanje obstojecega obmocja
function inline_hotspot_edit_region(spr_id, region_index, vre_id){
	//console.log("Urejanje območja za spr_id: "+spr_id+" in indeksom: "+region_index+" ter vre_id: "+vre_id);
	hotspot_edit_regions(spr_id, vre_id);
}

// shrani nadnaslove @ uporaba traku
function trak_inline_nadnaslov(spremenljivka, _this, tiplabele, grid){
	if ($(_this).attr('contenteditable') != 'true') return;
	
	// Pocistimo text pri copy/paste
	if(pasteFromWord == true){
		inline_clear_paste(_this);	
	}
	//posljemo podatke v funkcijo za dinamicno shranjevanje
	//$.post('ajax.php?t=vprasanjeinline&a=inline_labele_podrocij_save', {anketa: srv_meta_anketa_id, grid: grid, spremenljivka: spremenljivka, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, tiplabele: tiplabele, label: $(_this).html()});
	$.post('ajax.php?t=vprasanjeinline&a=inline_nadnaslov_save', {anketa: srv_meta_anketa_id, grid: grid, spremenljivka: spremenljivka, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, tiplabele: tiplabele, label: $(_this).html()});
}