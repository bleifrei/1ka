 var sidebar = 1; // pove kaj je prikazano na desni
// 0 - nic
// 1 - vprasanja
// 2 - knjiznica

var collapsed_content = 1; // pove ali beremo vsebino IFa z ajaxom (0), ali ga
// imamo vedno v cachu in ga samo skrijemo (1)
// enako je ali imamo na desni samo 1 vprasanje za velike ankete (0), ali pa
// vedno vse za manjse (1)

// meta podatki
var srv_meta_anketa_id = $("#srv_meta_anketa").val();
var srv_meta_podstran = $("#srv_meta_podstran").val();
var srv_meta_grupa = $("#srv_meta_grupa").val();
var srv_meta_branching = $("#srv_meta_branching").val();
var srv_meta_full_screen_edit = ($("#srv_meta_full_screen_edit").val() == 1 ? true : false);
//var srv_meta_lang_id = 0;

var _edit_fullscreen = true;

var cond_focus_field;
var currentFocus = null;

var popup = true;		// tole je ena in edina spremenljivka, za avtomatski fullscreen edit pri novem nacinu urejanja branchinga (ostale niso vec uporabne)
var locked = false;		// ce je anketa zaklenjena


//za prevajanje drsnikov
	var slider_prevod_min = 0;
	var slider_prevod_max = 0;
	var slider_prevod_def = 0;
	var slider_prevod_slider_handle = 0;
	var slider_prevod_slider_handle_step = 0;
	var slider_prevod_vmesne_labels = 0;
	var slider_prevod_vmesne_Crtice = 0;
	var slider_prevod_slider_MinMaxNumLabelNew = 0;
	var slider_prevod_slider_window_number = 0;
	var slider_prevod_vmesne_descr_labele = 0;
	var slider_prevod_tip_vmesne_descr_labele = 0;
	var slider_prevod_nakazi_odgovore = 0;
	var slider_prevod_minTemp = 0;
	var slider_prevod_maxTemp = 0;
	var slider_prevod_slider_VmesneDescrLabel = 0;
//za prevajanje drsnikov - konec

// document ready
function onload_init_branching() {

    if (locked) {
        $('#branching ul.first.locked').bind('click', function (event) {
            if ($("#prevent_unlock").val() == 1) {
                alert(lang['srv_unlock_popup3']);
            }
            else {
                alert(lang['srv_unlock_popup2']);
            }
        });
        return;
    }

    // funkcijo poklicemo ko se stran naloada in ob koncu vsakega ajax klica
    branching_struktura();
    $("body").ajaxStop(function () {
        branching_struktura();
    });

    // tukaj so zbrane vse klik funkcije, ki se zgodijo v #branchingu (nobenih <a href> in onclick ni več v htmlju)
    $('#branching').bind('click', function (event) {
        branching_click(event);
    });

    // podobno je za mouseover (kjer pa ene elemente tukaj tudi šele dodamo)
    $('#branching').bind('mouseover', function (event) {
        branching_mouseover(event);
    });
		
	// hover za ife, bloke, loope
    $('li.if, li.endif, li.block, li.endblock, li.loop, li.endloop').bind({
		mouseover: function (event) {
			branching_if_mouseover(event);
		},
		mouseleave: function (event) {
			branching_if_mouseleave(event);
		}
	});
}

// polovi vse click-e, ki se zgodijo v branchingu
// pomemben je vrstni red, ker morajo bit najprej zgornji elementi, nato pa spodnji (ker se klik lahko zgodi samo na prvem)
function branching_click(event) {

    var ta = $(event.target);
    var td;

    // kliknili smo na komentar, ne naredimo nic, ker tole pohendla qtip
    if (ta.is('a.surveycomment')) {
        return false;
    }

    // spr_edit div - urejanje spremenljivke na desni (samega urejanja ni, ker ga zajame klik na vrstico)
    // prikazi/skrij uvod/zakljucek
    if (ta.is('div.spr_edit a.hide')) {
        var id = ta.parent().attr('id');
        id = id.replace('edit_', '');
        introconcl_visible(id);
        return false;
    }

	// spremeni tip skale (ordinalna/nominalna) za tip 1,3,6
    if (ta.is('div.spr_edit a.scale_ordnom')) {
        var id = ta.parent().parent().attr('id');
        id = id.replace('edit_', '');
		
		value = ta.parent().parent().parent().find('.spremenljivka_content').attr('skala');
		if(value == 0) value = 1;
		else value = 0;
		
        scale_ordnom(id, value);
        return false;
    }
	
    // kopiraj spremenljivko
    if (ta.is('div.spr_edit a.copy')) {

        // Ce nima ustreznega paketa ne dovolimo kreiranja ifa
        if(ta.hasClass("user_access_locked")){

            // Skrcen nacin
            if($('#branching').hasClass('collapsed'))
                var tip = ta.closest('li.spr, li.if, li.block, li.loop').attr('tip');
            else
                var tip = ta.closest('li.spr, li.if, li.block, li.loop').find('div.spremenljivka_content').attr('tip');

            // Kvota in kalkulacija sta v 3. paketu, ostali tipi so v 2.
            if(tip == '22' || tip == '25')
                popupUserAccess('question_type_calculation');
            else if(tip == '21')
                popupUserAccess('question_type_signature');
            else
                popupUserAccess('if');
        }
        else{
            var id = ta.parent().attr('id');
            id = id.replace('edit_', '');
            spremenljivka_new(id, 0, 0, id);
        }
        return false;
    }

    // preview spremenljivke
    if (ta.is('div.spr_edit a.preview')) {
        var id = ta.parent().attr('id');
        id = id.replace('edit_', '');
        preview_spremenljivka(id);
        return false;
    }

    // dodajanje ifa na spremenljivko
    if (ta.is('div.spr_edit a.addif')) {

        // Ce nima ustreznega paketa ne dovolimo kreiranja ifa
        if(ta.hasClass("user_access_locked")){
            popupUserAccess('if');
        }
        else{
            var id = ta.parent().attr('id');
            id = id.replace('edit_', '');
            if_new(id, '0', 1, '0');
        }
        
        return false;
    }

    // izbrisi spremenljivko
    if (ta.is('div.spr_edit a.arhiv')) {
        var id = ta.parent().attr('id');
        id = id.replace('edit_', '');
        vprasanje_track(id);
        return false;
    }

    // izbrisi spremenljivko
    if (ta.is('div.spr_edit a.delete')) {
        var id = ta.parent().attr('id');
        id = id.replace('edit_', '');
        brisi_spremenljivko(id, lang['srv_brisispremenljivkoconfirm'], '0');
        return false;
    }


    // if_remove div - urejanje ifa na desni
    // kopiraj if
    if (ta.is('div.if_remove a.copy')) {

        // Ce nima ustreznega paketa ne dovolimo kopiranja ifa
        if(ta.hasClass("user_access_locked")){
            popupUserAccess('if');
        }
        else{
            var id = ta.parent().attr('id');
            id = id.replace('edit_if_', '');
            if_new(0, id, 1, 0, id);
        }

        return false;
    }

    // kopiraj if brez vsebine
    if (ta.is('div.if_remove a.copycond')) {

        // Ce nima ustreznega paketa ne dovolimo kopiranja ifa
        if(ta.hasClass("user_access_locked")){
            popupUserAccess('if');
        }
        else{
            var id = ta.parent().attr('id');
            id = id.replace('edit_if_', '');
            if_new(0, id, 1, 0, id, 1);   
        }

        return false;
    }

    // izbrisi if
    if (ta.is('div.if_remove a.delete')) {
        var id = ta.parent().attr('id');
        id = id.replace('edit_if_', '');
        if_remove(id);
        return false;
    }

    // izbrisi if in vsa vprašanja znotraj pogoja
    if (ta.is('div.if_remove a.delete_all')) {
        var id = ta.parent().attr('id');
        id = id.replace('edit_if_', '');
        if_remove(id, 1);
        return false;
    }


    // plusminus if, blok
    if (ta.is('a.pm')) {
        var id = ta.parent().attr('id');
        id = id.replace('branching_if', '');
        plusminus(id);
        return false;
    }

    // urejanje ifa (klik na celo vrstico ali ikono)
    td = $(event.target).closest('li.if span.conditions_display, a.edit');
    td = td.closest('.if');
    if (td.hasClass('if')) {
		
		// Odstranimo hover class iz if-ov, loopov in blokov
		td.removeClass('if_hovering');
		td.next().removeClass('if_hovering');
		
        var id = td.attr('id');
        id = id.replace('branching_if', '');
		
        //če vsebuje razred if_editing potem ob ponovnem kliku to zapremo
        if ($('#branching_if' + id).hasClass('if_editing')) {
            condition_editing_close(id);
            return false;
        }
        condition_editing(id, 1);
		
        return false;
    }

    // urejanje bloka (klik na celo vrstico ali ikono)
    td = $(event.target).closest('li.block span.conditions_display, a.edit');
    td = td.closest('.block');
    if (td.hasClass('block')) {
        
		// Odstranimo hover class iz if-ov, loopov in blokov
		td.removeClass('if_hovering');
		td.next().removeClass('if_hovering');
		
		var id = td.attr('id');
        id = id.replace('branching_if', '');
		
        //če blok že vsebuje razred if_editing potem ob ponovnem kliku to zapremo
        if ($('#branching_if' + id).hasClass('if_editing')) {
            condition_editing_close(id);
            return false;
        }
        condition_editing(id);
		
        return false;
    }

    // urejanje loopa (klik na celo vrstico ali ikono)
    td = $(event.target).closest('li.loop span.conditions_display, a.edit');
    td = td.closest('.loop');
    if (td.hasClass('loop')) {
		
		// Odstranimo hover class iz if-ov, loopov in blokov
		td.removeClass('if_hovering');
		td.next().removeClass('if_hovering');
		
        var id = td.attr('id');	
        id = id.replace('branching_if', '');
		
        //če zanka že vsebuje razred if_editing potem ob ponovnem kliku to zapremo
        if ($('#branching_if' + id).hasClass('if_editing')) {
            condition_editing_close(id);
            return false;
        }
        condition_editing(id);
		
        return false;
    }

    // skrcen nacin - klik na celo vrstico li
    if ($('#branching').hasClass('collapsed')) {

        // urejanje vprasanja (klik na celo vrstico)
        td = $(event.target).closest('li.spr');
        if (td.hasClass('spr') && !td.hasClass('spr_editing')) {
            var id = td.attr('id');
            id = id.replace('branching_', '');
            if (td.hasClass('calculation'))
                calculation_editing(-id);
			else if (td.hasClass('quota'))
                quota_editing(-id);
            else
                vprasanje_fullscreen(id);
            return false;
        }

        // razsirjen nacin - klik na zgornjo vrstico predogleda vprasanja
    } else {

        // urejanje vprasanja (klik na celo vrstico)
        td = $(event.target).closest('div.movable');
        if (td.hasClass('movable')) {
            td = td.parent().parent();
            var id = td.attr('id');
            id = id.replace('branching_', '');

            if (td.hasClass('calculation'))
                calculation_editing(-id);
			else if (td.hasClass('quota'))
                quota_editing(-id);
            else
                vprasanje_fullscreen(id);
            return false;
        }

        // urejanje vprasanje v spr_edit
        if (ta.is('div.spr_edit .edit')) {
            var id = ta.closest('li.spr').attr('id');
            id = id.replace('branching_', '');
            if (td.hasClass('calculation'))
                calculation_editing(-id);
			else if (td.hasClass('quota'))
                quota_editing(-id);
            else
                vprasanje_fullscreen(id);
            return false;
        }

    }

    // pagebreak (klik na celo vrstico)
    td = $(event.target).closest('li.drop span, li.nodrop span');
    if (td.hasClass('pb_new') || td.hasClass('pb_on')) {
        var spr;
        if (td.parent().attr('spr_pb'))
            spr = td.parent().attr('spr_pb');
        else
            spr = td.parent().attr('spr');
        if (!td.hasClass('permanent'))
            pagebreak(spr);
        return false;
    }

    /* inline edit vprasanja */
    inline_bind_click(event);
	
    return false;
}

// pohendla mouseoverje, ki se lovijo nad branchingom
function branching_mouseover(event) {

    var ta = $(event.target);

    var movable = ta.closest('li.spr, li.if, li.block, li.endif, li.endblock, li.loop, li.endloop');

    // Ce imamo zakljenjeno vprasanje tega ne dovolimo
    if (movable.attr('id')) {
        spr_id = movable.attr('id').replace('branching_', '');
        if ($('#spremenljivka_content_' + spr_id).hasClass('question_locked'))
            return;
    }

    if (movable.length > 0) {

        // ob mouseover eventu nastavimo elementom draggable funkcionalnost
        if (!movable.hasClass('ui-draggable') && movable.attr('id') != '-1' && movable.attr('id') != '-2') {		// ce se ni draggable, uvod in zakljucek nista draggable
            movable.draggable({
                revert: 'invalid',
                distance: 5,
                opacity: 0.5,
                handle: '.movable',
                start: function (e, ui) {
                    _moved = 1; // oznacimo da smo premaknil, da se ne izvede akcija ki je na klik (urejanje)
                    if (movable.hasClass('if') || movable.hasClass('block') || movable.hasClass('loop')) {
                        var id = movable.attr('id').replace('branching_if', '');
                        $('#if_' + id).css('visibility', 'hidden');
                    }
                },
                stop: function (e, ui) {
                    if (movable.hasClass('if') || movable.hasClass('block') || movable.hasClass('loop')) {
                        var id = movable.attr('id').replace('branching_if', '');
                        $('#if_' + id).css('visibility', 'visible');
                    }
                }
            });
        }

        // mouseover opcije v branchingu - desno zgoraj za urejanje elementov
        var branchborder = ta.closest('li.spr, li.if, li.block, li.loop');
        if (branchborder.length > 0) {
            var edit = branchborder.attr('id');
            if (edit != undefined) {

                // mouseover opcije pri spremenljivki
                edit = edit.replace('branching_', '');
                if (edit > 0) {
                    if (branchborder.find('div.spr_edit').length == 0) {
                        var html_snippet;
		
						// Dodamo izbiro nominalne/ordinalne skale pri tipih 1, 3 in 6		
						var tip = branchborder.find('div.spremenljivka_content').attr('tip');
						var scale_string = '';
						if(tip == 1 || tip == 3 || tip == 6){					
							
							var scale = 0;			
							if (branchborder.find('div.spremenljivka_content').attr('skala') == 1)
								scale = 1;
							
							if(scale == 1){
								scale_string = '<span class="scale_ordnom"><a class="scale_ordnom">' + lang['srv_skala_0'] + '</a>' +
								' / ' +
								'<span class="bold">' + lang['srv_skala_1'] + '</span></span>';	
							}
							else{
								scale_string = '<span class="scale_ordnom"><span class="bold">' + lang['srv_skala_0'] + '</span>' +
								' / ' +
								'<a class="scale_ordnom">' + lang['srv_skala_1'] + '</a></span>';	
							}							
						}
		
                        html_snippet = /*'<div class="spr_edit" id="edit_'+edit+'"><span class="edit">'+lang['srv_editirajspremenljivko']+' </span>'+*/
                            '<div class="spr_edit" id="edit_' + edit + '">' +
							scale_string +						
                            '<a title="' + lang['srv_editirajspremenljivko'] + '" class="edit faicon"></a>';
                        
                        // Disablamo ife, ce nima ustreznega paketa
                        if ($('#commercial_package').attr('value') == '1') {
                            html_snippet = html_snippet +
                            '<a title="' + lang['srv_if_new_question'] + '" class="addif faicon user_access_locked"></a>';
                        }
                        else{
                            html_snippet = html_snippet +
                            '<a title="' + lang['srv_if_new_question'] + '" class="addif faicon"></a>';
                        }

                        if ($('#editing_mode').attr('value') == '1') {
                            
                            var signature = 0;			
							if (branchborder.find('div.spremenljivka_content').attr('signature') == 1)
                                signature = 1;

                            // Ce smo slucajno v skrcenem nacinu
                            if($('#branching').hasClass('collapsed')){
                                tip = branchborder.attr('tip');

                                if (branchborder.attr('signature') == 1)
                                    signature = 1;
                            }
                                
                            // Disablamo kopiranje, ce nima ustreznega paketa za ta tip vprasanja
                            if ( ($('#commercial_package').attr('value') == '1' && ['17','18','24','26','27'].includes(tip)) 
                                || (($('#commercial_package').attr('value') == '1' || $('#commercial_package').attr('value') == '2') && ['22','25'].includes(tip))
                                || (($('#commercial_package').attr('value') == '1' || $('#commercial_package').attr('value') == '2') && ['21'].includes(tip) && signature == 1) ) {

                                html_snippet = html_snippet +
                                    '<a title="' + lang['srv_copy_spr'] + '" class="copy faicon user_access_locked"></a>';
                            }
                            else{
                                html_snippet = html_snippet +
                                    '<a title="' + lang['srv_copy_spr'] + '" class="copy faicon"></a>';
                            }

                            html_snippet = html_snippet +
                                    '<a title="' + lang['srv_predogled_spremenljivka'] + '" class="preview faicon"></a>';
                        }
                        // zarad tega so errorji
                        if (vprasanje_tracking == 2)
                        	html_snippet += '<a title="'+lang['srv_analiza_arhiviraj']+'" class="arhiv faicon"></a>';
							
                        html_snippet += '<a title="' + lang['srv_brisispremenljivko'] + '" class="delete faicon"></a>' +
                        '</div>';
                        branchborder.prepend(html_snippet);
                    }

                    // mouseover pri uvodu in zakljucku
                } else if (edit < 0) {
                    if (branchborder.find('div.spr_edit').length == 0) {
                        var html_snippet;

                        var hidden = 0;
                        if (branchborder.find('div.spremenljivka_content').hasClass('spremenljivka_hidden'))
                            hidden = 1;

                        html_snippet = '<div class="spr_edit" id="edit_' + edit + '">' +
                        '<a title="' + (hidden == 1 ? lang['edit_show'] : lang['edit_hide']) + '" class="hide faicon ' + (hidden == 1 ? 'unhide_icon' : 'hide_icon') + '"></a>' +
                        '<a title="' + (edit == -1 ? lang['srv_editirajuvod'] : lang['srv_editirajzakljucek']) + '" class="faicon edit"></a>' +
                        '</div>';

                        branchborder.prepend(html_snippet);
                    }


                    // mouseover opcije pri ifu
                } else if (edit.replace('if', '') > 0) {

                    edit = edit.replace('if', '');
                    if (branchborder.find('div.if_remove').length == 0) {

                        var html_snippet;

                        html_snippet = '<div class="if_remove" id=edit_if_' + edit + '>' +
                            '<a title="' + (branchborder.hasClass('if') ? lang['srv_if_edit'] : (branchborder.hasClass('block') ? lang['srv_block_edit'] : lang['srv_loop_edit'])) + '" class="edit faicon"></a>';
                        
                        
                        if (branchborder.hasClass('if')){

                            // Disablamo ife, ce nima ustreznega paketa
                            if ($('#commercial_package').attr('value') == '1') {
                                html_snippet = html_snippet +
                                    '<a title="' + (branchborder.hasClass('if') ? lang['srv_copy_ifcond'] : lang['srv_copy_block']) + '" class="copycond faicon user_access_locked"></a>';
                            }
                            else{
                                html_snippet = html_snippet +
                                    '<a title="' + (branchborder.hasClass('if') ? lang['srv_copy_ifcond'] : lang['srv_copy_block']) + '" class="copycond faicon"></a>';
                            }
                        }
      
                        // Disablamo ife, ce nima ustreznega paketa
                        if ($('#commercial_package').attr('value') == '1') {
                            html_snippet = html_snippet +
                                '<a title="' + (branchborder.hasClass('if') ? lang['srv_copy_if'] : (branchborder.hasClass('block') ? lang['srv_copy_block'] : lang['srv_copy_loop'])) + '" class="copy faicon user_access_locked"></a>';
                        }
                        else{
                            html_snippet = html_snippet +
                                '<a title="' + (branchborder.hasClass('if') ? lang['srv_copy_if'] : (branchborder.hasClass('block') ? lang['srv_copy_block'] : lang['srv_copy_loop'])) + '" class="copy faicon"></a>';
                        }

                        html_snippet = html_snippet +
                            '<a title="' + (branchborder.hasClass('if') ? lang['srv_if_rem'] : (branchborder.hasClass('block') ? lang['srv_block_rem'] : lang['srv_loop_rem'])) + '" class="delete faicon"></a>' +
                            '</div>';

                        branchborder.prepend(html_snippet);
                    }
                }
            }
        }
    }
}

// pohendla mouseleave, ki se lovijo nad ifi, loopi, bloki
function branching_if_mouseover(event) {

	var ta = $(event.target);
	
	var movable = ta.closest('li.if, li.block, li.endif, li.endblock, li.loop, li.endloop');

	// Ce editiramo tega ne pustimo
	if(movable.hasClass('if_editing')){
		return;
	}
	
	// Dodamo hover class za ife, loope in bloke
	if(movable.hasClass('endif') || movable.hasClass('endblock') || movable.hasClass('endloop')){
		movable.parent().addClass('if_hovering');
		movable.parent().prev().addClass('if_hovering');
	}
	else{
		movable.addClass('if_hovering');
		movable.next().addClass('if_hovering');
	}
}

// pohendla mouseover, ki se lovijo nad ifi, loopi, bloki
function branching_if_mouseleave(event) {

	var ta = $(event.target);
	
	var movable = ta.closest('li.if, li.block, li.endif, li.endblock, li.loop, li.endloop');
	
	// Odstranimo hover class iz if-ov, loopov in blokov
	if(movable.hasClass('endif') || movable.hasClass('endblock') || movable.hasClass('endloop')){
		movable.parent().removeClass('if_hovering');
		movable.parent().prev().removeClass('if_hovering');
	}
	else{
		movable.removeClass('if_hovering');
		movable.next().removeClass('if_hovering');
	}
}


// poklice se ob nalozitvi strani in ob koncu vsakega ajax klica (da nastavi kar je treba) -- v tej funkciji naj bi bilo cimmanj (idealno nic)
function branching_struktura() {

    // nastavimo droppable
    $('li.drop', $('#branching')).droppable({
        accept: '.spr, .if, .block, .endif, .endblock, .new_spr, .new_adv, .new_if, .new_block, .new_loop, .new_pb, .loop, .endloop',
        hoverClass: 'branchinghover',
        tolerance: 'pointer',
        drop: function (e, ui) {

            if ($(ui.draggable).hasClass('new_spr')) {				// nova spremenljivka (iz toolboxa)
                var spr = $(this).attr('spr');
                var _if = $(this).attr('if');
                var endif = $(this).attr('endif');
                var tip = $(ui.draggable).attr('tip');
                var podtip = 0;
                if ($(ui.draggable).hasClass('podtip')) podtip = $(ui.draggable).attr('podtip');
                var drop = $(this).attr('drop');
                var copy = ($(ui.draggable).attr('copy') != '' ? $(ui.draggable).attr('copy') : 0);
                // pri kreiranju generatorja imen ustvarimo za njim se loop in nagovor, ki mu pripadata
                if (tip == 9) {
                    SN_generator_new(spr, 0);
                }
                else {
                    spremenljivka_new(spr, _if, endif, copy, tip, podtip, drop);
                }

            } else if ($(ui.draggable).hasClass('new_adv')) {		// nova advanced spremenljivka (odpre se popup za izbiro)
                var spr = $(this).attr('spr');
                var _if = $(this).attr('if');
                var endif = $(this).attr('endif');
                toolbox_add_advanced(spr, _if, endif);

            } else if ($(ui.draggable).hasClass('new_if')) {		// nov if iz toolbox
                var spr = $(this).attr('spr');
                var _if = $(this).attr('if');
                var endif = $(this).attr('endif');
                var copy = ($(ui.draggable).attr('copy') != '' ? $(ui.draggable).attr('copy') : 0);
                if_new(spr, _if, endif, '0', copy);

            } else if ($(ui.draggable).hasClass('new_block')) {		// nov blok iz toolboxa
                var spr = $(this).attr('spr');
                var _if = $(this).attr('if');
                var endif = $(this).attr('endif');
                var copy = ($(ui.draggable).attr('copy') != '' ? $(ui.draggable).attr('copy') : 0);
                if_new(spr, _if, endif, '1', copy);

            } else if ($(ui.draggable).hasClass('new_loop')) {		// nov loop iz toolboxa
                var spr = $(this).attr('spr');
                var _if = $(this).attr('if');
                var endif = $(this).attr('endif');
                var copy = ($(ui.draggable).attr('copy') != '' ? $(ui.draggable).attr('copy') : 0);
                if_new(spr, _if, endif, '2', copy);

            } else if ($(ui.draggable).hasClass('new_pb')) {		// potegnjen pagebreak iz toolboxa
                var spr = $(this).attr('spr');
                pagebreak(spr);

            } else {												// premikanje elementov
                accept_droppable($(ui.draggable).attr('id'), $(this).attr('id'));
            }

        }
    });

    load_help();	// help moramo loadati tudi na zacetku in ob vsakem ajax klicu

    // paste from word alert
    $('div[contenteditable=true]').off('paste', pasteFromWordAlert);
    $('div[contenteditable=true]').on('paste', pasteFromWordAlert);

}

var pasteFromWord = false;
function pasteFromWordAlert() {

    if (pasteFromWord == true) return;
    pasteFromWord = true;
}

function pasteFromWordAlertClose() {

    $('#pasteFromWordAlert').hide();
    $('#fade').fadeOut('slow');
}

var is_new_spr_dragable = false; // gledamo ali vlečemo new_spr da skrbimo za skrivanje
// inicializira toolbox na levi strani
function init_toolbox() {

    /*$('#toolbox, #toolbox_basic, #toolbox_settings').draggable({handle: '.handle'});*/
    $('p.new_spr:not(.user_access_locked), p.new_adv:not(.user_access_locked), p.new_if:not(.user_access_locked), p.new_block:not(.user_access_locked), p.new_loop:not(.user_access_locked), p.new_pb', '#toolbox_basic').draggable({
        start: function () {
            is_new_spr_dragable = true, $('#toolbox_add_advanced').addClass('dragging')
        },
        stop: function () {
            is_new_spr_dragable = false, $('#toolbox_add_advanced').removeClass('dragging')
        },
        revert: false,
        helper: 'clone',
        opacity: 0.9,
        appendTo: 'body',
        distance: 5

    }).bind('click', function (event) {

        var ta = $(event.target);
        ta = $(ta).closest('p');
        if (ta.hasClass('new_spr')) {				// nova spremenljivka (iz toolboxa)
            //$('html, body').animate({scrollTop: $('body').height()+$('#branching').height()});		// scrollamo na dno zaslona
            var tip = ta.attr('tip');
            var podtip = 0;
            if (ta.hasClass('podtip')) podtip = ta.attr('podtip');
            // pri kreiranju generatorja imen ustvarimo za njim se loop in nagovor, ki mu pripadata
            if (tip == '9_sn') {
                SN_generator_new(0, 1);
            }
            else {
                spremenljivka_new(0, 0, 1, 0, tip, podtip);
            }

        } else if (ta.hasClass('new_adv')) {		// nova spremenljivka advanced (prikaze se popup z vsemi tipi vprasanj)
            //$('html, body').animate({scrollTop: $('body').height()+$('#branching').height()});		// scrollamo na dno zaslona
            toolbox_add_advanced(0, 0, 1);

        } else if (ta.hasClass('new_if')) {			// nov if iz toolbox
            $('html, body').animate({scrollTop: $('body').height() + $('#branching').height()});		// scrollamo na dno zaslona
            if_new(0, 0, 1, 0);

        } else if (ta.hasClass('new_block')) {		// nov blok iz toolboxa
            $('html, body').animate({scrollTop: $('body').height() + $('#branching').height()});		// scrollamo na dno zaslona
            if_new(0, 0, 1, 1);

        } else if (ta.hasClass('new_loop')) {		// nov loop iz toolboxa
            $('html, body').animate({scrollTop: $('body').height() + $('#branching').height()});		// scrollamo na dno zaslona
            if_new(0, 0, 1, 2);
        }

        $('#toolbox_add_advanced').addClass('dragging');
        setTimeout(function () {
            $('#toolbox_add_advanced').removeClass('dragging');
        }, 500);

    });

    // mousever preview vprasanja
    $('#toolbox_basic').bind('mouseover', function (event) {

        var tip = $(event.target).closest('p.new_spr, p.new_adv, p.new_if, p.new_block, p.new_loop, p.new_sn');
        
        if (tip.hasClass('adv')) { // tooltip za vprasanja v advanced toolboxu
            if (tip.hasClass('podtip')) {  // vprasanja s podtipom
                show_tip_preview_subtype(-1, tip.attr('podtip'), tip.attr('tip'));
            } 
            else if (tip.hasClass('new_sn')) {
                show_tip_preview_toolbox(tip.attr('tip'), 0, 1);
            }
            else if (tip.hasClass('new_spr')) { // osnovna vprasanja, samo z tipom
                show_tip_preview_toolbox(tip.attr('tip'), 0, 1);
            }
        } 
        else if (tip.hasClass('new_spr') && !is_new_spr_dragable) { // tooltip za vprasanja v osnovnemu toolboxu
            var podtip = tip.attr('podtip');
            show_tip_preview_toolbox(tip.attr('tip'), undefined, undefined, podtip);
        } 
        else if (tip.hasClass('new_if') || tip.hasClass('new_block') || tip.hasClass('new_loop')) {
            show_tip_preview_toolbox(tip.attr('tip'));
        }  
        else if (tip.hasClass('new_adv')) {	// gumb za +
            // prikazemo s CSSom
        }

    }).bind('mouseout', function (event) {
        $("#tip_preview").hide();
    });

    // max-height toolboxa
    //$('#toolbox_basic').css('max-height', $(window).height()-188);
}

// prikaze popup za dodajanje naprednih tipov vprasanj
function toolbox_add_advanced(spr, _if, endif) {

    $('#fade').fadeTo('slow', 1);
    $('#fullscreen').show().load('ajax.php?t=branching&a=toolbox_add_advanced', {
        anketa: srv_meta_anketa_id,
        spr: spr,
        'if': _if,
        endif: endif
    });

}

// ----------------------- funkcije, ki se klicejo iz htmlja
// -----------------------

// spremeni nastavitve prikaza in toolboxov
function change_mode(what, value) {

    /*$.redirect('ajax.php?t=branching&a=change_mode', {
     anketa: srv_meta_anketa_id,
     what: what,
     value: value
     });*/

    //window.location.replace ('index.php?anketa='+srv_meta_anketa_id+'&a=branching&change_mode=1&what='+what+'&value='+value);
    $.post('ajax.php?t=branching&a=change_mode', {anketa: srv_meta_anketa_id, what: what, value: value}, function () {
        window.location.reload();
    });
}

// spremeni hitre nastavitve pri formi
function change_form_quicksettings(what) {

    var status = $('#' + what).css('display');
    if (status == 'none') {
        $('#' + what).show();
        if (what == 'form_settings_obvescanje') {
            $('#obvescanje_switch').removeClass("plus").addClass("minus");
        } else {
            $('#email_switch').removeClass("plus").addClass("minus");
        }
    }
    else {
        $('#' + what).hide();
        if (what == 'form_settings_obvescanje') {
            $('#obvescanje_switch').removeClass("minus").addClass("plus");
        } else {
            $('#email_switch').removeClass("minus").addClass("plus");
        }
    }

    vprasanje_save();
}

function toolbox_advanced(checked) {
    if (checked)
        var mode = 2;
    else
        var mode = 1;

    change_mode('toolbox', mode);
}

// doda nov if
function if_new(spremenljivka, _if, endif, tip, copy, no_content, follow_up) {

    close_all_editing();

    $('#branching').load('ajax.php?t=branching&a=if_new', {
        spremenljivka: spremenljivka,
        'if': _if,
        endif: endif,
        tip: tip,
        copy: copy,
        no_content: no_content,
        anketa: srv_meta_anketa_id
    }, function () {
        $('#clipboard').fadeOut();
        if ((popup || $('#vprasanje').css('display') == 'block' ) && !(copy > 0)) {	// pri kopiranju ne odpremo popupa	
			
			var new_if_id = $('#temp_new_if_id').val();
			
			//samo kadar je follow up pogoj
			if (follow_up > 0 && follow_up != null) {

				spremenljivka_new(spremenljivka, new_if_id, 0, 0, 1, 0, 0);

				$.post('ajax.php?t=branching&a=follow_up_condition', {
					ank_id: srv_meta_anketa_id,
					if_id: new_if_id,
					odg_id: follow_up,
					spr_id: spremenljivka
				});
				//dodano, da osveži labelo pri IF pogoju
				condition_editing_close(new_if_id);
			}
			if (true || tip == 0 || tip == 2) { // if in zanka se odpreta in blok tudi
				condition_editing(new_if_id);
			}
        }
    });

}

// doda novo spremenljivko
function spremenljivka_new(spremenljivka, _if, endif, copy, tip, podtip, drop) {

    // skrijemo preview
    $("#tip_preview").hide();
    $('#fade').fadeOut('slow');

    $.post('ajax.php?t=branching&a=spremenljivka_new', {
        spremenljivka: spremenljivka,
        'if': _if,
        endif: endif,
        copy: copy,
        drop: drop,
        tip: tip,
        podtip: podtip,
        anketa: srv_meta_anketa_id
    }, function (data) {
        if (!data) return;

        if (popup && !(copy > 0)) { 	// pri kopiranju ne odpremo popupa
			if (tip == 22)
				calculation_editing(-data.nova_spremenljivka_id, 1);
			else if (tip == 25)
				quota_editing(-data.nova_spremenljivka_id, 1);
            else {
                $('#branching').html(data.branching_struktura);
                vprasanje_fullscreen(data.nova_spremenljivka_id, data.vprasanje_fullscreen);
                
                // Pri ustvarjanju novega vprasanja izvedemo focus na naslov (da ni potreben dodaten klik)
                $('.naslov_inline[spr_id="'+data.nova_spremenljivka_id+'"]').focus();
            }       
        } 
		else {
            refreshLeft(data.nova_spremenljivka_id);
        }

        // dvojne gride moramo na novo shranit da se prepise grid v bazi
        if ((tip == 6 || tip == 16) && podtip == 3) {
            vprasanje_save(true);
        }

        // Opozorilo za bloke -> da smo presegli optimalno st. vprasanj
        alert_block();

    }, 'json');
}

// zapre vsa odprta urejanja vprašanj in ifov
function close_all_editing(spremenljivka) {

    if (spremenljivka == undefined) {

        // zapremo vsa inline urejanja (sprozimo blur)
        $('div[contenteditable=true]:focus').blur();

        // shranimo/zapremo odprto vprasanje
        if ($('li.spr_editing').length > 0) {
            //var spr_id = $('li.spr_editing .spremenljivka_content').attr('spr_id');
            vprasanje_save();
        }

    } else {
        if ($('li.spr_editing').not('#branching_' + spremenljivka).not('#' + spremenljivka).length > 0) {
            vprasanje_save();
        }
    }

    // shranimo/zapremo odprt pogoj
    if ($('li.if_editing').length > 0) {
        var if_id = $('li.if_editing').attr('id');
        if_id = if_id.replace('branching_if', '');
        condition_editing_close(if_id);
    }

}

// zbrise if in odstrani vse spremenljivke iz njega
function if_remove(_if, all, confirmed) {

    var besedilo = null;
    if ($("#branching_if" + _if).hasClass('if'))
        (all == 1) ? besedilo = lang['srv_brisiifconfirm_all'] : besedilo = lang['srv_brisiifconfirm'];
    if ($("#branching_if" + _if).hasClass('block'))
        (all == 1) ? besedilo = lang['srv_brisiblockconfirm_all'] : besedilo = lang['srv_brisiblockconfirm'];
    if ($("#branching_if" + _if).hasClass('loop'))
        (all == 1) ? besedilo = lang['srv_brisiloopconfirm_all'] : besedilo = lang['srv_brisiloopconfirm'];

	if (confirmed == undefined) confirmed = 1;
	
	// Smo ze potrdili in vse pobrisemo
	if(confirmed == 1 && all == 1){
		
		close_all_editing();
		
		$.post('ajax.php?t=branching&a=if_remove', {'if': _if, all: all, anketa: srv_meta_anketa_id, confirmed: confirmed}, 
			function (data) {			
				$('#branching').html(data);
        });
		
        $('#div_condition_editing').hide();
	}
    else if (confirm(besedilo)) {

        //close_all_editing();

		$.post('ajax.php?t=branching&a=if_remove', {'if': _if, all: all, anketa: srv_meta_anketa_id, confirmed: confirmed}, 
			function (data) {

				// Warning da brisemo vsebino pogoja in imamo ze podatke, ki jih bomo pobrisali
				if (data.substring(0, 3) === '<p>' || data.substring(0, 4) === '<h2>') {    
                    $('#fade').fadeIn("fast");                    
					$('#dropped_alert').html(data).fadeIn("fast").css('width', '400px');
				}
				else{					
					$('#branching').html(data);
				}
        });
		
        $('#div_condition_editing').hide();
    }
}

// odstrani podif iz vrednosti
function vrednost_if_remove(_if, vrednost) {

    if (confirm(lang['srv_brisiifconfirm'])) {
        $.post('ajax.php?t=branching&a=vrednost_if_remove', {
            'if': _if,
            vrednost: vrednost,
            anketa: srv_meta_anketa_id
        });
        $('#div_condition_editing').hide();
        $('#fade').fadeOut('slow');

        //odstranimo IF pogoj in opozorila pri odgovoru
        var p = $('#variabla_' + vrednost);
        p.find('#if_notranji_' + vrednost).hide();
        p.find('span.error').remove();
        p.find('span.red').hide();

    }

}

function if_tip(_if, tip) {

    $('#div_condition_editing').load('ajax.php?t=branching&a=if_tip', {
        'if': _if,
        anketa: srv_meta_anketa_id,
        tip: tip
    }, function () {
        centerDiv2Page('#div_condition_editing');
    });
}

function hidden_answer(odg, odg_id) {
    $.ajax({
        type: 'POST',
        url: 'ajax.php?t=branching&a=hidden_answer',
        data: {
            odgovor: odg,
            id: odg_id
        },
        success: function (data) {
            var i = $('[odg_id="' + odg_id + '"]');
            i.attr('odg_vre', data);

            if(data == 0)
                i.removeClass('show-hidden show-disable');

            if(data == 1)
                i.addClass('show-hidden');

            if(data == 2) {
                i.removeClass('show-hidden');
                i.addClass('show-disable');
            }

            //change title
            i.attr('title', lang['srv_hide-disable_answer-'+data]);

            return false;
        }
    });
}

function correct_answer(spr_id, vre_id) {

	if($('#variabla_' + vre_id + ' .correct').hasClass('show-correct')){
		var action = 'delete';
	}
	else{
		var action = 'add';
	}

	$.post('ajax.php?t=branching&a=correct_answer', {spr_id: spr_id, vre_id: vre_id, action: action, anketa: srv_meta_anketa_id}, function(){
		
		if(action == 'delete'){
			$('#variabla_' + vre_id + ' .correct').removeClass('show-correct');
			
			//za odstranjevanje kljukice ob editiranju			
			if($('#branching_' + spr_id).hasClass('spr_editing')){				
				if($('#variabla_' + vre_id + ' .correct').hasClass('kviz-editing-correct')){
					$('#variabla_' + vre_id + ' .correct').removeClass('kviz-editing-correct');					
				}
			}
			//
		}
		else{
			$('#variabla_' + vre_id + ' .correct').addClass('show-correct');
			//za prikazovanje kljukice ob editiranju
			if($('#branching_' + spr_id).hasClass('spr_editing')){
				if($('#variabla_' + vre_id + ' .correct').hasClass('kviz-editing-correct')){					
				}else{
					$('#variabla_' + vre_id + ' .correct').addClass('kviz-editing-correct');
				}
			}
		}		
	});
}

function vrednost_condition_editing(vrednost) {

    close_all_editing();

    if ($('#commercial_package').attr('value') == '1') {
        popupUserAccess('if');
        return false;
    }
	
	$('#fade').fadeTo('slow', 1);
    $('#div_condition_editing').html('');
    $('#div_condition_editing').fadeIn("slow");

    $('#div_condition_editing').load(
        'ajax.php?t=branching&a=vrednost_condition_editing', {
            'vrednost': vrednost,
            'anketa': srv_meta_anketa_id
        }, function () {
            centerDiv2Page('#div_condition_editing');
            $('#div_condition_editing_conditions .clr_if').children('span').show(); //prikažemo zvezdico *IF
        }
    );
}

function condition_editing(_if, odpreminus) {
    close_all_editing();
    // zapremo knjiznico
    $('#toolbox_library').hide();

    $('li#branching_if' + _if).addClass('if_editing');
    $('ul#if_' + _if).addClass('if_editing');
	
    $('#branching_if' + _if + ' .if_content').load(
        'ajax.php?t=branching&a=condition_editing', {
            'if': _if,
            anketa: srv_meta_anketa_id
        }, function () {
            $('.condition_editing_body').hide().slideDown();
            $('#branching_endif' + _if).addClass('endif_editing');

            //omogočimo začetni in končni oklepaj
            var z = parseInt($('#branching_endif' + _if).css('padding-left')); //dobimo št. pikslov od if-a in 5px, ki jih ima prevzeti oklepaj
            var zamik = z + 8;
            var sirina = zamik;
            if (z == 0) {
                sirina = 15;
            }
            $('#zacetni_oklepaj_' + _if).css({'margin-left': '-' + zamik + 'px', 'width': sirina + 'px'}).show();
            //$('#koncni_zaklepaj_' + _if).show();

            //odpremo +
            if (odpreminus == 1)
                plusminus(_if, 1);
            return false;
        });
}
/** Funkcija za prikaz pogojev pri podatkih
 *
 * @param _if
 */
function data_condition_editing(_if) {

    $('#div_cp_preview').load(
        'ajax.php?t=branching&a=data_condition_editing', {
            'if': _if,
            anketa: srv_meta_anketa_id
        }, function () {
            return false;
        });
}


function condition_editing_close(_if, if_nova) {

    $('.condition_editing_body').slideUp();
    $('#branching_endif' + _if).removeClass('endif_editing');
    $('#branching_if' + _if).removeClass('if_editing');
    $('ul#if_' + _if).removeClass('if_editing');


    var plus = 0;
    if ($('#branching_if' + _if + ' a.pm').hasClass('plus'))
        plus = 1;

    $.post('ajax.php?t=branching&a=condition_editing_close', {anketa: srv_meta_anketa_id, 'if': _if, if_nova: if_nova},
        function (data) {

            if (plus == 1)
                $('#branching_if' + _if).html('<a class="pm plus"></a>' + data);
            else
                $('#branching_if' + _if).html('<a class="pm minus"></a>' + data);


            if ($('#vprasanje').css('display') == 'block') {	 // refreshamo tudi preview pogoja v urejanju vprasanja
                $('#if_preview').html(data);
                $('#if_preview_link').html('<a href="" onclick="condition_editing(\'' + _if + '\'); return false;">' + lang['srv_if_edit'] + '</a>');

            } else if ($('#div_condition_profiles').css('display') == 'block') {											// urejanje pogojev pri profilih (podatki, analize)
                // osvežimo podatke
                var pid = $("#condition_profile .active").attr('value');
                $("#div_condition_profiles").load('ajax.php?t=conditionProfile&a=show_condition_profile', {
                    anketa: srv_meta_anketa_id,
                    pid: pid,
                    meta_akcija: srv_meta_akcija,
                    podstran: srv_meta_podstran
                });
            } else {											// normalno urejanje pogojev	
                $('#fade').fadeOut('slow');
            }
        }
    );
}

function load_if_notranji_data(vrednost, srv_meta_anketa_id, _if, tab){

    if(tab != 0) {
      var s = tab;
      var g = 1;
    }
    else{
      var s = $('#if_notranji_' + vrednost);
      var g = 0;
    }

    s.load('ajax.php?t=branching&a=vrednost_condition_editing_close', {
            anketa: srv_meta_anketa_id,
            'if': _if,
            'vrednost': vrednost,
            'grid': g
    });
}

//*IF - notranji pogoj za odgovor shranimo in spremembe prikažemo
function vrednost_condition_editing_close_save(vrednost, _if) {

	$('#fade').fadeOut('slow');
    document.getElementById('div_condition_editing').style.display = "none";
    //$('#div_condition_editing_conditions .clr_if').children('span').hide();

    //v kolikor je pogoj pri odgovoru že prikazan potem samo urejamo možnosti
    var ifNotranji = $('#if_notranji_' + vrednost);
    var v = $('#variabla_' + vrednost);
    var tab = 0;
    v.find('span.error').remove();
    if (ifNotranji.length) {
        v.find('#if_notranji_' + vrednost).show();
        v.find('span.red').show();
       load_if_notranji_data(vrednost, srv_meta_anketa_id, _if, tab);
    }
    //v kolikor obstaja IF za table grid tr td
    if(v.is('tr') && v.find('td.grid_question span.red').length > 0){
        var tab = $('#variabla_'+vrednost).find('td.grid_question span.red');
        v.find('span.red').show();
        load_if_notranji_data(vrednost, srv_meta_anketa_id, _if, tab);

    }
    else {
        //če pa pogoja še nimamo potem moramo najprej narediti span element, kamor bomo vstavili IF pogoj
        //za checkox, radio in tisti, ki imajo div in ne tabele
        if (v.is('div')) {
            v.append('<span style="font-size:9px; cursor:pointer" id="if_notranji_' + vrednost + '" onclick="vrednost_condition_editing(\'' + vrednost + '\'); return false;" title="' + lang['srv_podif_edit'] + '">');
        }//za vsa vprašanja, ki so v tabelah
        if (v.is('tr')) {
            v.find('td.grid_question').closest('td').append('<span class="red" style="cursor:pointer" onclick="vrednost_condition_editing(\'' + vrednost + '\'); return false;" title="' + lang['srv_podif_edit'] + '">*</span>');
            var tab = $('#variabla_'+vrednost).find('td.grid_question span.red');
        }

        load_if_notranji_data(vrednost, srv_meta_anketa_id, _if, tab);
    }
}

function vrednost_condition_editing_close() {
	$('#fade').fadeOut('slow');
    document.getElementById('div_condition_editing').style.display = "none";
}


function condition_add(_if, conjunction, negation, vrednost) {

    $('#div_condition_editing_inner').load('ajax.php?t=branching&a=condition_add', {
        'if': _if,
        'conjunction': conjunction,
        'negation': negation,
        'vrednost': vrednost,
        'noupdate': __vnosi + __analiza,
        'anketa': srv_meta_anketa_id
    }, function () {
        centerDiv2Page('#div_condition_editing');
        $("#div_condition_editing_container").attr({scrollTop: $("#div_condition_editing_container").attr("scrollHeight")});
        $('#div_condition_editing_inner').resize();	// trigger, da se poklice resize event
    });

}

function condition_sort(_if) {

    $('#div_condition_editing_inner').load('ajax.php?t=branching&a=condition_sort', {
        'if': _if,
        sortable: $('#div_condition_editing_inner').sortable('serialize'),
        anketa: srv_meta_anketa_id
    }, function () {
        $('#div_condition_editing_inner').resize();	// trigger, da se poklice resize event
    });

}


function condition_edit(condition) {

    var vrednost = new Array();
    var sel = document.getElementById('vrednost_' + condition);

    var i;
    var count = 0;
    var text = '';
    var ostanek = 0;
    var tip = document.getElementById('tip_' + condition).value;

    if (tip == 4 || tip == 21 || tip == 7 || tip == 8 || tip == 22 || tip == 25 || tip == 19 || tip == 20 || tip == 18) { // text, number, compute
        text = document.getElementById('text_' + condition).value;
    } else if (tip == -1) { // mod recnum
        var ost_id = document.getElementById('ostanek_' + condition);
        ostanek = ost_id.options[ost_id.selectedIndex].value;
    } else if (tip == -2) { // calculation
        text = document.getElementById('text_' + condition).value;
    } else if (tip == -3) { // kvota
        text = document.getElementById('text_' + condition).value;
    } else if (tip == -4) { // naprava
        text = document.getElementById('text_' + condition).value;
    } else { // ostali
        sel = document.getElementsByName('vrednost_' + condition);
        for (i = 0; i < sel.length; i++) {
            if (sel[i].checked) {
                vrednost[count] = sel[i].value;
                count++;
            }
        }
    }

    var spr_id = document.getElementById('spremenljivka_' + condition);
    var spremenljivka = spr_id.options[spr_id.selectedIndex].value;

    var conj = document.getElementById('conjunction_' + condition).value
        .split('_');

    var conjunction = conj[0];
    var negation = conj[1];

    var opr_id = document.getElementById('operator_' + condition);
    var operator = 0;
    if (tip >= 0 || tip == -2 || tip == -3)
        operator = opr_id.options[opr_id.selectedIndex].value;

    $('#div_condition_editing_conditions').load(
        'ajax.php?t=branching&a=condition_edit', {
            text: text,
            operator: operator,
            negation: negation,
            conjunction: conjunction,
            'vrednost[]': vrednost,
            condition: condition,
            spremenljivka: spremenljivka,
            anketa: srv_meta_anketa_id,
            ostanek: ostanek,
            noupdate: __vnosi + __analiza
        }, function () {
            $('#div_condition_editing_inner').resize();	// trigger, da se poklice resize event
        });

}

function if_edit_enabled(_if, enabled) {
    $.post('ajax.php?t=branching&a=if_edit_enabled', {anketa: srv_meta_anketa_id, 'if': _if, enabled: enabled});
}

function if_blok_tab(_if, tab) {
    $.post('ajax.php?t=branching&a=if_blok_tab', {anketa: srv_meta_anketa_id, 'if': _if, tab: tab}, function(){
		
		// Prikazemo opozorilo da ne sme imeti preloma strani
		if(tab > 0){
			$('#blok_pb_warning').show();
		}
		else if($('input[name="if_random"]:checked').val() == -1 && $('select[name="if_blok_horizontal"]').val() == 0 && $('select[name="if_blok_tab"]').val() == 0){
			$('#blok_pb_warning').hide();
		}
	});
}

function if_blok_horizontal(_if, horizontal) {
    $.post('ajax.php?t=branching&a=if_blok_horizontal', {
        anketa: srv_meta_anketa_id,
        'if': _if,
        horizontal: horizontal
    }, 
	function(){
		// Prikazemo opozorilo da ne sme imeti preloma strani
		if(horizontal > 0){
			$('#blok_pb_warning').show();
		}
		else if($('input[name="if_random"]:checked').val() == -1 && $('select[name="if_blok_horizontal"]').val() == 0 && $('select[name="if_blok_tab"]').val() == 0){
			$('#blok_pb_warning').hide();
		}
	});
}

function if_blok_random(_if, random) {
    $.post('ajax.php?t=branching&a=if_blok_random', {
        anketa: srv_meta_anketa_id,
        'if': _if,
        random: random
    }, function(){

        // Random vprasanja znotraj bloka
		if(random >= 0){
            $('#if_blok_random_cnt').show();                
			$('#blok_pb_warning').show();
        }
        // Random bloki znotraj bloka
        else if(random == -2){
            $('#if_blok_random_cnt').hide();
            $('#blok_pb_warning').show();
        }
		else{
			$('#if_blok_random_cnt').hide();
			
			if($('input[name="if_random"]:checked').val() == -1 && $('select[name="if_blok_horizontal"]').val() == 0 && $('select[name="if_blok_tab"]').val() == 0){
				$('#blok_pb_warning').hide();
			}
		}
	});
}

function if_blok_random_cnt(_if, random) {
    $.post('ajax.php?t=branching&a=if_blok_random', {
        anketa: srv_meta_anketa_id,
        'if': _if,
        random: random
    });
}

function follow_up_condition(id) {

    if ($('#commercial_package').attr('value') == '1') {
        popupUserAccess('if');
        return false;
    }

    //pridobimo id od bloka, kjer je izbran odgovor
    var parents_id = $("#variabla_" + id).closest("#variable_holder").siblings(".naslov_inline").attr('spr_id');
    if_new(parents_id, 0, 0, 0, 0, 0, id);
}


function bracket_edit_new(condition, vrednost, who, what) {

    $('#div_condition_editing_inner').load('ajax.php?t=branching&a=bracket_edit_new',
        {
            who: who,
            what: what,
            condition: condition,
            vrednost: vrednost,
            noupdate: __vnosi + __analiza,
            anketa: srv_meta_anketa_id
        }, function () {
            centerDiv2Page('#div_condition_editing');
            $('#div_condition_editing_inner').resize();	// trigger, da se poklice resize event
        });

}

function conjunction_edit(condition, conjunction, negation) {

    $('#div_condition_editing_inner').load('ajax.php?t=branching&a=conjunction_edit',
        {
            condition: condition,
            conjunction: conjunction,
            negation: negation,
            noupdate: __vnosi + __analiza,
            anketa: srv_meta_anketa_id
        }, function () {
            centerDiv2Page('#div_condition_editing');
            $('#div_condition_editing_inner').resize();	// trigger, da se poklice resize event
        });

}

function fill_value(condition, vrednost) {

    var spr_id = document.getElementById('spremenljivka_' + condition);
    var spremenljivka = spr_id.options[spr_id.selectedIndex].value;

    $('#div_condition_editing_inner').load('ajax.php?t=branching&a=fill_value', {
        condition: condition,
        spremenljivka: spremenljivka,
        vrednost: vrednost,
        noupdate: __vnosi + __analiza,
        anketa: srv_meta_anketa_id
    }, function () {
        centerDiv2Page('#div_condition_editing');
        $('#div_condition_editing_inner').resize();	// trigger, da se poklice resize event
    });

}

function edit_fill_value(condition) {
    $('#edit_fill_value_' + condition).show();
    $('#preview_fill_value_' + condition).hide();

    $('#div_condition_editing_inner').resize();	// trigger, da se poklice resize event
}

function fill_ostanek(condition) {

    var mod_id = document.getElementById('modul_' + condition);
    var modul = mod_id.options[mod_id.selectedIndex].value;

    $('#' + condition + '_ostanek').load('ajax.php?t=branching&a=fill_ostanek',
        {
            condition: condition,
            anketa: srv_meta_anketa_id,
            noupdate: __vnosi + __analiza,
            modul: modul
        });
}

function edit_label(_if) {

    var label = document.getElementById('label_' + _if).value;

    $.post('ajax.php?t=branching&a=edit_label', {
        'if': _if,
        label: label,
        noupdate: __vnosi + __analiza,
        anketa: srv_meta_anketa_id
    });
}

function edit_panel_status(_if) {

    var panel_status = document.getElementById('panel_status_' + _if).value;

    $.post('ajax.php?t=branching&a=edit_panel_status', {
        'if': _if,
        panel_status: panel_status,
        noupdate: __vnosi + __analiza,
        anketa: srv_meta_anketa_id
    });
}

function condition_remove(_if, condition, vrednost) {

    $('#div_condition_editing_inner').load('ajax.php?t=branching&a=condition_remove',
        {
            'if': _if,
            condition: condition,
            vrednost: vrednost,
            noupdate: __vnosi + __analiza,
            anketa: srv_meta_anketa_id
        }, function () {
            centerDiv2Page('#div_condition_editing');
            $('#div_condition_editing_inner').resize();	// trigger, da se poklice resize event
        });

}

function calculation_editing(condition, new_spremenljivka, vrednost) {

    $('#fade').fadeTo('slow', 1);
    $('#calculation').html('').fadeIn("slow");
    if (condition < 0) $('#branching_' + (-condition)).addClass('spr_editing');

    $('#calculation').load('ajax.php?t=branching&a=calculation_editing', {
        condition: condition,
        vrednost: vrednost,
        noupdate: __vnosi + __analiza,
        anketa: srv_meta_anketa_id
    });

    // v primeru nove spremenljivke, refreshamo tudi branching
    if (new_spremenljivka == 1 && condition < 0) {
        refreshLeft(-condition);
    }
}

function calculation_editing_close(condition, vrednost) {

    document.getElementById('calculation').style.display = "none";
    if (condition < 0) $('#branching_' + (-condition)).delay('3000').removeClass('spr_editing', 500);

    // kalkulacija v pogojih
    if (condition >= 0) {
        $('#fade').fadeOut('slow');
        $('#div_condition_editing').load(
            'ajax.php?t=branching&a=calculation_editing_close', {
                anketa: srv_meta_anketa_id,
                condition: condition,
                vrednost: vrednost
            }, function () {
                centerDiv2Page('#div_condition_editing');
            });

        // kalkulacija kot tip vprasanja
    } else {

        // ce smo v vnosih, refreshamo stran, da se izpise nova kalkulacija..
        if (__vnosi == 1) {

            window.location.reload();

            // obicajno zapiranje kalkulacije v urejanju
        } else {

            $('#fade').fadeOut('slow');
            $('#branching_' + (-condition)).load(
                'ajax.php?t=branching&a=calculation_editing_close', {
                    anketa: srv_meta_anketa_id,
                    condition: condition
                }, function () {
                    centerDiv2Page('#div_condition_editing');
                });
        }
    }
}

function calculation_save(calculation) {

    $.post('ajax.php?t=branching&a=calculation_save', {
        calculation: calculation,
        expression: $('#expression_' + calculation).val(),
        noupdate: __vnosi + __analiza,
        anketa: srv_meta_anketa_id
    });

}

function calculation_add(condition, operator, vrednost) {

    $('#calculation_editing_inner').load('ajax.php?t=branching&a=calculation_add', {
        condition: condition,
        operator: operator,
        vrednost: vrednost,
        noupdate: __vnosi + __analiza,
        anketa: srv_meta_anketa_id
    }, function () {
        $("#calculation_editing_inner").attr({scrollTop: $("#calculation_editing_inner").attr("scrollHeight")});
        $('#calculation_editing_inner').scroll();
    });
}


function calculation_operator_edit(calculation, operator) {

    $('#calculation_editing_inner').load('ajax.php?t=branching&a=calculation_operator_edit',
        {
            calculation: calculation,
            operator: operator,
            noupdate: __vnosi + __analiza,
            anketa: srv_meta_anketa_id
        }, function () {
            centerDiv2Page('#div_condition_editing');
            $('#calculation_editing_inner').scroll();
        });
}

function calculation_sort(condition) {

    $('#calculation_editing_inner').load('ajax.php?t=branching&a=calculation_sort', {
        'condition': condition,
        sortable: $('#calculation_editing_inner').sortable('serialize'),
        anketa: srv_meta_anketa_id
    }, function () {
        $('#calculation_editing_inner').scroll();
    });
}

function calculation_edit(calculation, vrednost) {

    var spr_id = document
        .getElementById('calculation_spremenljivka_' + calculation);
    var spremenljivka = spr_id.options[spr_id.selectedIndex].value;

    var number = $('#calculation_number_' + calculation).val();

    $('#calculation_editing_inner').load('ajax.php?t=branching&a=calculation_edit', {
        number: number,
        calculation: calculation,
        vrednost: vrednost,
        spremenljivka: spremenljivka,
        noupdate: __vnosi + __analiza,
        anketa: srv_meta_anketa_id
    }, function () {
        $('#calculation_editing_inner').scroll();
    });
}

function calculation_remove(condition, calculation, vrednost) {

    $('#calculation_editing_inner').load('ajax.php?t=branching&a=calculation_remove', {
        condition: condition,
        calculation: calculation,
        vrednost: vrednost,
        noupdate: __vnosi + __analiza,
        anketa: srv_meta_anketa_id
    }, function () {
        $('#calculation_editing_inner').scroll();
    });
}

function calculation_bracket_edit_new(calculation, vrednost, who, what) {

    $('#calculation_editing_inner').load('ajax.php?t=branching&a=calculation_bracket_edit_new',
        {
            who: who,
            what: what,
            calculation: calculation,
            vrednost: vrednost,
            noupdate: __vnosi + __analiza,
            anketa: srv_meta_anketa_id
        }, function () {
            centerDiv2Page('#div_condition_editing');
            $('#calculation_editing_inner').scroll();
        });
}

function pagebreak(spremenljivka) {

    close_all_editing();

    $('#branching').load('ajax.php?t=branching&a=pagebreak', {
        spremenljivka: spremenljivka,
        anketa: srv_meta_anketa_id
    }, function () {
        refreshRight();
    });
}

function pagebreak_all() {

    close_all_editing();

    $('#branching').load('ajax.php?t=branching&a=pagebreak_all', {
        anketa: srv_meta_anketa_id
    }, function () {
        refreshRight();
    });
}

function vprasanje_edit(spremenljivka, buffer) {

    // editor_remove(spremenljivka);
    alleditors_remove();

    if (_moved == 1) {
        _moved = 0;
        return;
    }

    if (sidebar != 1)
        toggle_vprasanja();

    _fullscreen = 1;
    $('#fullscreen').html('').fadeIn('slow').draggable({delay: 100, cancel: 'input, textarea, select, .buttonwrapper'});
    $('#fade').fadeTo('slow', 1);
    refreshRight();

    if (buffer != undefined) {

        $('#fullscreen').html(buffer);
        $('#fullscreen').append('<div id="bottom_icons_holder" ></div><!-- /bottom_icons_holder -->');
        $("#bottom_icons_holder").load('ajax.php?a=show_bottom_icons', {
            branching: srv_meta_branching,
            anketa: srv_meta_anketa_id
        });
        refreshBottomIcons('gray');

    } else {

        $('#fullscreen').load(
            'ajax.php?a=editmode_spremenljivka',
            {
                spremenljivka: spremenljivka,
                branching: srv_meta_branching,
                anketa: srv_meta_anketa_id
            },
            function () {
                // za spremenlivko dodamo se div z spodnimi
                // ikonicami
                //$("#spremenljivka_" + spremenljivka).append('<div id="bottom_icons_holder" ></div><!-- /bottom_icons_holder -->');
                $('#fullscreen').append('<div id="bottom_icons_holder" ></div><!-- /bottom_icons_holder -->');
                $("#bottom_icons_holder").load('ajax.php?a=show_bottom_icons', {
                    branching: srv_meta_branching,
                    anketa: srv_meta_anketa_id
                });
                refreshBottomIcons('gray');
            });

    }

}

function plusminus(id, odpremo) {

    var a = $('#branching_if' + id + ' a.pm');

    // blok/if je prikazan, mi ga bomo skrili
    if (a.hasClass('minus') && odpremo != 1) {

        $('#if_' + id).slideUp();
        a.removeClass('minus').addClass('plus');

        $.post('ajax.php?t=branching&a=if_collapsed', {collapsed: 1, 'if': id, anketa: srv_meta_anketa_id});

        // if/blok ni prikazan, mi ga bomo pa prikazali
    } else {

        $('#if_' + id).slideDown();
        a.removeClass('plus').addClass('minus');

        // shranimo v bazo, da je if razprt
        $.post('ajax.php?t=branching&a=if_collapsed', {collapsed: 0, 'if': id, anketa: srv_meta_anketa_id});
    }

}


// preveri strukturo ifov in podifov, ce je vse ok
function check_pogoji() {

    $.post('ajax.php?t=branching&a=check_pogoji&izpis=long', {anketa: srv_meta_anketa_id}, function (data) {
        $('#fade').fadeIn("slow");
        $('#check_pogoji').html(data).fadeIn("slow");
    });
}

// prikaze urejevalni nacin za (-1) ali conclusion (-2)
function editmode_introconcl(id) {

    // smo v normalni anketi
    $('#spremenljivka_' + id).load('ajax.php?t=branching&a=editmode_introconcl', {
        id: id,
        anketa: srv_meta_anketa_id
    });
}

// prikaze navadni nacin za spremenljivko
function normalmode_introconcl(id, editmode, fulscreen) {
    var text_intro_concl = "";
    var note_intro_concl = $("#opomba_" + id).val();

    // ce mamo editor prebermo iz editorja
    try {
        text_intro_concl = CKEDITOR.get('naslov_' + id).getContent();
        // ce editor se ni naloadan in imamo textarea
    } catch (e) {
        text_intro_concl = $("#naslov_" + id).val();
        text_intro_concl = text_intro_concl.replace("\n", '<br/>');
    }

    // shranimo vrednost
    $.post('ajax.php?t=branching&a=edit_introconcl', {
        id: id,
        anketa: srv_meta_anketa_id,
        branching: srv_meta_branching,
        text: text_intro_concl,
        opomba: note_intro_concl
    }, function () {

        // smo v normalni anketi
        $('#spremenljivka_' + id).load('ajax.php?t=branching&a=normalmode_introconcl', {
            id: id,
            anketa: srv_meta_anketa_id
        });
    });
}

// poslje nov text
function edit_introconcl(id, text, opomba) {

    $.post('ajax.php?t=branching&a=edit_introconcl', {
        id: id,
        text: text,
        opomba: opomba,
        anketa: srv_meta_anketa_id
    });

}

// vkljuci/izkljuci prikaz intro concl
function introconcl_visible(id) {

    $('#' + id).load('ajax.php?t=branching&a=introconcl_visible', {id: id, anketa: srv_meta_anketa_id}, function () {

        if (id == -1)
            $('input[type="radio"][name="show_intro"]').not(':checked').prop("checked", true, function () {
                vprasanje_save();
            });
        else
            $('input[type="radio"][name="show_concl"]').not(':checked').prop("checked", true, function () {
                vprasanje_save();
            });
    });
}

function concl_settings() {
    var text = $("[name=text]").val();
    var url = $("[name=url]").val();
    var concl_link = $("[name=concl_link]").is(':checked');

    var concl_back_button = $("[name=concl_back_button]").is(':checked');

    $.post('ajax.php?t=branching&a=concl_settings', {
        text: text,
        url: url,
        concl_link: concl_link,
        anketa: srv_meta_anketa_id,
        concl_back_button: concl_back_button
    });
}
function intro_concl_preview(spremenljivka) {
    $('#fullscreen').html('').fadeIn('slow').draggable({
        delay: 100
    });
    $('#fade').fadeTo('slow', 1);
    $('#fullscreen').load('ajax.php?a=preview_spremenljivka', {
        anketa: srv_meta_anketa_id,
        spremenljivka: spremenljivka
    }).draggable({
        delay: 100
    });
}

// preklopi ordinalno/nominalno skalo
function scale_ordnom(spremenljivka, value) {

    $('#branching_' + spremenljivka).load('ajax.php?t=branching&a=scale_ordnom', {spremenljivka: spremenljivka, value: value, anketa: srv_meta_anketa_id}, function () {
		$('input[type="radio"][name="skala"]').not(':checked').prop("checked", true, function () {
			vprasanje_save();
		});
		show_scale_text(value);
    });
}

// prikaze sidebar s podano vsebino
function show_sidebar(bar) {

    if (sidebar != bar) {

        sidebar = bar;
        if (bar == 1) {
            $('#branching_vprasanja_tabs #vpr').addClass('active');
            $('#branching_vprasanja_tabs #knj').removeClass('active');
        }
        //$('#branching_vprasanja').slideDown();
    }

}

// toggla prikaz vprasanj na desni
function toggle_vprasanja(spremenljivka) {

    if (sidebar != 1) {

        sidebar = 1;
        $('#branching_vprasanja_tabs #vpr').addClass('active');
        $('#branching_vprasanja_tabs #knj').removeClass('active');
        //$('#branching_vprasanja').slideDown();
        refreshRight(spremenljivka);

    } else {
        sidebar = 0;
        $('#branching_vprasanja_tabs #vpr').removeClass('active');
        //$('#branching_vprasanja').slideUp().html('');
    }

}

function show_vprasanja() {
    refreshRight();
}
function show_library(tab) {
    if (sidebar != 2) {
        sidebar = 2
    }
    display_knjiznica(tab);
    /*
     * if (sidebar != 2) { sidebar = 2; display_knjiznica(); } else { sidebar =
     * 0; }
     */
}

// odpre / zapre vsa vprasanja
function expand(mode) {
    $('#question_holder').load('ajax.php?t=branching&a=expand', {
        mode: mode,
        anketa: srv_meta_anketa_id
    });
}


// na konec doda blok za različne interpretacije (pri kvizu)
function dodaj_blok_interpretacije() {

    $.post('ajax.php?t=branching&a=dodaj_blok_interpretacije', {
        anketa: srv_meta_anketa_id
    }, function () {
        vnos_redirect('index.php?anketa=' + srv_meta_anketa_id);
    });

}

// ----------------------- funkciji za refreshat
// ---------------------------------------

// refresha levo stran z branchingom
function refreshLeft(spremenljivka, removeID) {

    $.post('ajax.php?t=branching&a=refresh_left', {
        spremenljivka: spremenljivka,
        anketa: srv_meta_anketa_id
    }, function (data) {
        $('#branching').html(data);
    });

}

// refresha desno stran z vprasanji
function refreshRight(spremenljivka) {
    return;
}

// ----------------------- nastavitve za draggable -----------------------

// poklice se ko spustimo nek element na droppable element
function accept_droppable(child, parent) {

    close_all_editing();

    $('#branching').load('ajax.php?t=branching&a=accept_droppable', {
        child: child,
        parent: parent,
        anketa: srv_meta_anketa_id
    });
}

// droppable za forme - vrivanje v vmesne dive
function accept_droppable_vrivanje(grupa, child, spremenljivka) {

    $('#vprasanja').load('ajax.php?a=nova_spremenljivka_vrivanje', {
        anketa: srv_meta_anketa_id,
        grupa: srv_meta_grupa,
        spremenljivka: spremenljivka,
        child: child
    }, function () {
        $('#clipboard').fadeOut();

        $.post('ajax.php?t=branching&a=get_new_spr', {
            anketa: srv_meta_anketa_id
        }, function (new_spr) {
            // --editor_display(new_spr);
        });
    });
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ')
            c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0)
            return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function intro_concl_fullscreeen(grupa, fullscreen) {

    // tole mamo, ker pri premikanju vprasanja pride tudi do eventa onclick, in
    // da se ne sprozi
    if (_moved == 1) {
        _moved = 0;
        return;
    }

    if (fullscreen >= 1) {
        if (fullscreen == 2) // ce imamo odprt editing, zbrisemo html, da se
        // IDji ne podvajajo
        {
            $("#spremenljivka_" + grupa).find(".editmenu").remove();
            $("#spremenljivka_" + grupa).find(".spremenljivka_tekst_form")
                .remove();
            $("#spremenljivka_" + grupa).find("#spr_settings_intro_concl")
                .remove();
            $("#spremenljivka_" + grupa).find(".save_button").remove();
            $("#spremenljivka_" + grupa).find(".spr_settings").remove();
        }
        _fullscreen = 1;
        $('#fullscreen').html('').fadeIn('slow').draggable({
            delay: 100
        });
        $('#fade').fadeTo('slow', 1);
    }
    $(getContainer(grupa)).load('ajax.php?a=intro_concl_fullscreeen', {
        grupa: grupa,
        branching: srv_meta_branching,
        anketa: srv_meta_anketa_id,
        grupa: srv_meta_grupa,
        fullscreen: fullscreen,
        introconcl: grupa
    });
}

function expandCollapseAllPlusMinus(what) {

    $('li.if, li.block', '#branching').each(
        function (index) {

            var id = $(this).attr('id').replace('branching_if', '');

            var a = $('#branching_if' + id + ' a.pm');

            if (what == 'expand') {
                // blok/if je skrit
                if (a.hasClass('plus')) {
                    plusminus(id);
                }
            } else if (what == 'collapse') {
                // blok/if je prikazan
                if (a.hasClass('minus')) {
                    plusminus(id);
                }
            }
        }
    );

}

function branch_editmode_grupa(id, spremenljivka) {
    $('#branch_edit_grupa_' + id).load('ajax.php?a=branch_editmode_grupa', {
        anketa: srv_meta_anketa_id,
        grupa: id,
        spremenljivka: spremenljivka
    });
}
function branch_normalmode_grupa(id, spremenljivka) {
    $('#branch_edit_grupa_' + id).load('ajax.php?a=branch_normalmode_grupa', {
        anketa: srv_meta_anketa_id,
        grupa: id,
        spremenljivka: spremenljivka
    });
}

function branch_brisi_grupo(id, text) {
    if (confirm(text)) {
        // $.redirect('ajax.php?a=brisi_grupo', {anketa: srv_meta_anketa_id,
        // grupa: grupa, thisgrupa: srv_meta_grupa});
    }
}

// document ready funkcijo sem prestavil na vrh strani, da je vse skupaj

function centerDiv2Page(id) {
    return false;
}

// prestevilci anketo v branchingu
function prestevilci() {

    $.redirect('ajax.php?t=branching&a=prestevilci', {anketa: srv_meta_anketa_id});
}

// alert naj se zapira bloke
function alert_block() {

    var count_spr = 0;
    count_spr = $('#branching').find('.spr').length - 2;

    if (count_spr > 30) {
        $('#alert_close_block').load('ajax.php?t=branching&a=alert_close_block', {anketa: srv_meta_anketa_id}, function (data) {

            if (data != false) {
                $('#alert_close_block').show('fast');
                $('#fade').fadeTo('slow', 1);
            }
        });
    }
}

// alert naj se zapira bloke
function alert_close_block() {
    $('#alert_close_block').hide('fast');
    $('#fade').hide('slow');
}

// hitre nastavitve
function quick_settings(spremenljivka, results, what) {

    var status1 = $('#form_settings_obvescanje').css('display');
    //var status2 = $('#form_settings_vabila').css('display');

    if (what == 'finish_author' || what == 'finish_respondent_cms' || what == 'finish_respondent' || what == 'finish_other') {
        if (results.checked == true)
            results = 1;
        else
            results = 0;
    }

    //$("#simple").load('ajax.php?a=form_settings', {anketa: srv_meta_anketa_id, spremenljivka: spremenljivka, results: results, what: what});
    //$.post('ajax.php?a=form_settings', {anketa: srv_meta_anketa_id, spremenljivka: spremenljivka, results: results, what: what});

    $("#quick_settings").load('ajax.php?t=branching&a=edit_quick_settings', {
        anketa: srv_meta_anketa_id,
        spremenljivka: spremenljivka,
        results: results,
        what: what,
        status1: status1,
        status2: status2
    });

    if (what == 'finish_respondent')
        $("#branching").load('ajax.php?t=branching&a=refresh_left', {
            anketa: srv_meta_anketa_id,
            spremenljivka: spremenljivka
        });
}

function calculation_edit_variable(spremenljivka) {

    var input = $("#variable_" + spremenljivka);
    var variable = input.val();

    variable = check_valid_variable(variable);

    input.val(variable);

    $.post('ajax.php?t=branching&a=calculation_edit_variable', {
        anketa: srv_meta_anketa_id,
        spremenljivka: spremenljivka,
        variable: variable
    });
}

function calculation_edit_decimalna(spremenljivka) {

    var input = $("#decimalna_" + spremenljivka);
    var decimalna = input.val();

    $.post('ajax.php?t=branching&a=calculation_edit_decimalna', {
        anketa: srv_meta_anketa_id,
        spremenljivka: spremenljivka,
        decimalna: decimalna
    });
}

function calculation_edit_missing(spremenljivka) {

	if ($("#calcMissing_" + spremenljivka).is(':checked')) {
        var missing = 1;
    } 
	else {
        var missing = 0;
    }

    $.post('ajax.php?t=branching&a=calculation_edit_missing', {
        anketa: srv_meta_anketa_id,
        spremenljivka: spremenljivka,
        missing: missing
    });
}

//hitro posiljanje vabil pri formi
function form_send_email() {

    var text = document.getElementById('respondent_profile_value_text').value;

    $('#fullscreen').html('').fadeIn('slow').draggable({delay: 100, cancel: 'input, textarea, select, .buttonwrapper'});
    $('#fade').fadeTo('slow', 1);

    $('#fullscreen').load('ajax.php?t=branching&a=form_send_email', {
        anketa: srv_meta_anketa_id,
        text: text
    }).draggable({delay: 100, cancel: 'input, textarea, select, .buttonwrapper'});

}

function fill_value_loop(_if) {

    var spr_id = document.getElementById('spremenljivka_' + _if);
    var spremenljivka = spr_id.options[spr_id.selectedIndex].value;

    $('#branching_if' + _if).load('ajax.php?t=branching&a=fill_value_loop', {
        'if': _if,
        spremenljivka: spremenljivka,
        //vrednost : vrednost,
        anketa: srv_meta_anketa_id
    });
}


function loop_edit(_if) {

    var vrednost = new Array();

    var count = 0;

    sel = document.getElementsByName('vrednost_' + _if);
    for (i = 0; i < sel.length; i++) {
        if (sel[i].checked) {
            vrednost[count] = sel[i].value;
            count++;
        }
    }

    //$('#div_condition_editing_conditions').load(
    $('#branching_if' + _if).load(
        'ajax.php?t=branching&a=loop_edit', {
            'vrednost[]': vrednost,
            'if': _if,
            anketa: srv_meta_anketa_id
        });
}

function loop_edit_advanced(_if) {

    var vrednost = new Array();

    $('tr#vrednost_' + _if + ' input:checked').each(function (index) {
        vrednost[parseInt($(this).attr('id'))] = parseInt($(this).val());
    });

    $('#branching_if' + _if).load(
        'ajax.php?t=branching&a=loop_edit_advanced', {
            'vrednost[]': vrednost,
            'if': _if,
            anketa: srv_meta_anketa_id
        });
}

function loop_edit_max(_if, max) {
    $.post('ajax.php?t=branching&a=loop_edit_max', {'if': _if, max: max, anketa: srv_meta_anketa_id});
}

/**
 * preveri, ce je element viden (podati je treba tudi container, ki je scrollable (lahko je window))
 */
function isScrolledIntoView(elem, container) {

    if (container != 'window') {
        var containerTop = $(container).offset().top;
        var containerBottom = containerTop + $(container).height();
    } else {
        var containerTop = $(window).scrollTop();
        var containerBottom = containerTop + $(window).height();
    }

    var elemTop = $(elem).offset().top;
    var elemBottom = elemTop + $(elem).height();

    return ((elemBottom >= containerTop) && (elemTop <= containerBottom)
    && (elemBottom <= containerBottom) && (elemTop >= containerTop) );

}
function toggle_toolbox() {
    $('#toolbox_nastavitve').load('ajax.php?t=branching&a=toggle_toolbox', {anketa: srv_meta_anketa_id});
}


function find_replace() {

    $('#fade').fadeTo('slow', 1);
    $('#vrednost_edit').show().load('ajax.php?t=branching&a=find_replace', {anketa: srv_meta_anketa_id}, function () {

        $('input[name=find]').focus().bind('keyup', function () {
            find_replace_count(this)
        });
    });
}

function find_replace_count(t) {

    //console.log($(t).val());

    $.post('ajax.php?t=branching&a=find_replace_count', {
        find: $(t).val(),
        anketa: srv_meta_anketa_id
    }, function (data) {

        $('#find_count').html(data);

    });

}

function find_replace_do() {

    var find = $('input[name=find]').val();
    var replace = $('input[name=replace]').val();

    if (find == '' || replace == '') {

        if (find == '')        $('input[name=find]').css('outline', '2px solid red');
        if (replace == '')    $('input[name=replace]').css('outline', '2px solid red');

        return;
    }

    $.post('ajax.php?t=branching&a=find_replace_do', {
        anketa: srv_meta_anketa_id,
        find: find,
        replace: replace
    }, function (data) {
        if (data == '')
            window.location.reload();
    });

}

// Ustvarjanje novega SN generatorja (in loopa z nagovorom, ki mu pripada)
function SN_generator_new(spremenljivka, endif) {

    // skrijemo preview
    $("#tip_preview").hide();
    $('#fade').fadeOut('slow');

    $.post('ajax.php?t=branching&a=SN_generator_new', {
        spremenljivka: spremenljivka,
        endif: endif,
        anketa: srv_meta_anketa_id
    }, function (data) {
        if (!data) return;

        $('#branching').html(data.branching_struktura);
        vprasanje_fullscreen(data.nova_spremenljivka_id, data.vprasanje_fullscreen);

    }, 'json');
}

// dodajanje demografskih vprasanj s prve strani
function demografija_new(variable) {

    var type = 'remove';
    if ($('#' + variable).is(':checked')) {
        type = 'add';
    } else {
        close_all_editing();
    }

    $.post('ajax.php?t=branching&a=demografija_new', {
        variable: variable,
        type: type,
        anketa: srv_meta_anketa_id
    }, function (data) {
        if (!data) return;

        if (data.branching != '') $('#branching').html(data.branching);

        if (type == 'add') {
            vprasanje_fullscreen(data.spremenljivka);
        }

    }, 'json');
}

//****************funkcija za izris sliderja
function slider_edit_init(spremenljivka, min, max, def, slider_handle, slider_handle_step, vmesne_labels, vmesne_Crtice, slider_MinMaxNumLabelNew, slider_window_number, vmesne_descr_labele, tip_vmesne_descr_labele, nakazi_odgovore, minTemp, maxTemp, slider_VmesneDescrLabel, slider_CustomDescriptiveLabels) {	
    
 	if(!slider_VmesneDescrLabel){
		min = minTemp;
		max = maxTemp;
		$('#slider_MinNumLabel_'+spremenljivka).val(min);
		$('#slider_MaxNumLabel_'+spremenljivka).val(max);		
	}
	
	$("#variabla_limit_" + spremenljivka).css('display', 'none');
	
	var minmaxlabela = "label";//hrani nastavitev za minmax labele
	var rest = false;	//hrani nastavitve za vmesne črtice z ("label") in brez label ("pip") ter odsotnost črtic (false)
	var vmesne_opisne_labele = false;
	
	//ureditev handle kot bunkica in nakazovanje moznih odgovorov ************************************************************
	
	//ureditev handle kot bunkica
	if (slider_handle == 1){//ce zelimo skriti handle
		$('#sliderbranching_' + spremenljivka).slider().removeClass("classic_slider");	//odstrani razred s klasicnim handle
		$('#sliderbranching_' + spremenljivka).slider().addClass("special_slider");	//dodaj razred s handle v obliki bunkice					
		$('#sliderbranching_' + spremenljivka + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
	}
	else if (slider_handle == 0){ //drugace
		$('#sliderbranching_' + spremenljivka).slider().addClass("classic_slider");	//dodaj klasicen razred
		$('#sliderbranching_' + spremenljivka).slider().removeClass("special_slider");	//odstrani razred special slider
	}
	//konec ureditve handle kot bunkico
	
	//ureditev bunk in elips za nakazovanje moznih odgovorov
	if (nakazi_odgovore == 1 && slider_handle == 1){//ce zelimo bunke za nakazovanje odgovorov
		$('#sliderbranching_' + spremenljivka).slider().removeClass("classic_slider");	//odstrani razred s klasicnimi crticami
		$('#sliderbranching_' + spremenljivka).slider().addClass("circle_slider");	//dodaj razred z bunkicami za nakazovanje
		//$('#sliderbranching_' + spremenljivka + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
	}
	else if (nakazi_odgovore == 0 && slider_handle == 0){ //drugace
		$('#sliderbranching_' + spremenljivka).slider().addClass("classic_slider");	//dodaj klasicen razred
		$('#sliderbranching_' + spremenljivka).slider().removeClass("circle_slider");	//odstrani razred circle slider
	}
	else if (nakazi_odgovore == 1 && slider_handle == 0){ //drugace
		$('#sliderbranching_' + spremenljivka).slider().addClass("elipse_slider");	//dodaj klasicen razred
		//$('#sliderbranching_' + spremenljivka).slider().removeClass("circle_slider");	//odstrani razred circle slider
	}
	
	//konec ureditve bunk in elips za nakazovanje moznih odgovorov
	
	if (nakazi_odgovore == 1){	//ce vklopimo nakazovanje odgovorov
		vmesne_Crtice = 1;		//vklopi crtice, ki so v bistvu sredstvo za nakazovanje moznih odgovorov
	}
	
	//konec ureditve handle kot bunkica in nakazovanje moznih odgovorov *******************************************************
	
	if ( slider_MinMaxNumLabelNew == 1 ){
		minmaxlabela = "pip";
	} 
	else{
		minmaxlabela = "label";
	}
	
	if (vmesne_Crtice == 1){//ce je potrebno pokazati vmesne črtice
		rest = "pip";
		//console.log('Črtice');
	}

	else if (vmesne_Crtice == 0) {
		rest = false;
		//console.log('Brez črtic');
	}
	

	//if (vmesne_labels == 1) {
	if (vmesne_labels == 1 || tip_vmesne_descr_labele != 0) {
		rest = "label";
	}
	
	if(slider_VmesneDescrLabel){
		if(tip_vmesne_descr_labele != 0){ //ce se je izbralo prednalozene vmesne opisne labele
			vmesne_opisne_labele = vmesne_descr_labele.split(";");
			max = vmesne_opisne_labele.length-1;	
		}else if(tip_vmesne_descr_labele == 0){	//ce se je izbralo Brez oz. custom opisne labele
			vmesne_opisne_labele = slider_CustomDescriptiveLabels.split(";");		
			max = vmesne_opisne_labele.length;
		}
	}

	$('#sliderbranching_' + spremenljivka)
		.slider({	//uredi slider z labelami in oznakami stopnje
			step: slider_handle_step,
			value: def,
			min: min,
			max: max,
			
			slide: function (event, ui) {
				if (slider_window_number == 0){	//ce rabimo stevilo nad handle
					// Sproti popravljamo vrednost v okencu ob slidu
					$("#sliderTextbranching_" + spremenljivka).html(ui.value);

					// Premikamo okencek skupaj z sliderjem
					var delay = function () {
						$("#sliderTextbranching_" + spremenljivka).position({
							of: ui.handle,
							offset: "0, -37"
						});
					};
					// wait for the ui.handle to set its position
					setTimeout(delay, 5);
				}
			},

			// Prikazemo okencek s vrednostjo
			start: function (event, ui) {
				if (slider_window_number == 0){
					$("#sliderTextbranching_" + spremenljivka).position({//postavi okencek na pravo mesto ob neposrednem kliku na rocico
							of: ui.handle,
							offset: "0, -37"
					});
					$("#sliderTextbranching_" + spremenljivka).css('visibility', 'visible');
				}
				$('#sliderbranching_' + spremenljivka + ' .ui-slider-handle').css('visibility', '');
			},

			// Skrijemo okencek s vrednostjo
			stop: function (event, ui) {
				if(slider_handle == 0){//ce handle ni skrit
					if (slider_window_number == 0){
						//$("#sliderTextbranching_" + spremenljivka).css('visibility', '');	//skrij okence
					}
				}
			},
					
			create: function (event, ui) {
				var percent_def = (def - min) / (max - min) * 100;
				if (percent_def < 0 || percent_def > 100) {
					percent_def = 50;
				}
				
				$('#sliderbranching_' + spremenljivka + ' .ui-slider-handle').css('left', percent_def + '%');
								
				// Postavimo na zacetku okencek na pravo mesto
				if (slider_window_number == 0){
					$("#sliderTextbranching_" + spremenljivka).position({
						of: $('#sliderbranching_' + spremenljivka + ' .ui-slider-handle'),
						//of: $('#sliderbranching_' + spremenljivka + ' .classic_slider'),
						offset: "0, -37"
					});
				}
			}
		})			
		.slider("pips",{
			rest: rest,
			first: minmaxlabela,	//skrij min in max vrednosti
			last: minmaxlabela,
			labels: vmesne_opisne_labele,
		});

    $('#sliderbranching_' + spremenljivka).slider("option", "value", def);//postavi rocico na mesto, kjer je izracunana default vrednost
}
//***************************************************************

//*************funkcija za izris sliderja pri prevajanju
function slider_edit_init_prevajanje(spremenljivka, min, max, def, slider_handle, slider_handle_step, vmesne_labels, vmesne_Crtice, slider_MinMaxNumLabelNew, slider_window_number, vmesne_descr_labele, tip_vmesne_descr_labele, nakazi_odgovore, minTemp, maxTemp, slider_VmesneDescrLabel, slider_CustomDescriptiveLabels) {	
	//globalne spremenljivke, ki so potrebne za posodobitev drsnika v prevodih
	slider_prevod_min = min;
	slider_prevod_max = max;
	slider_prevod_def = def;
	slider_prevod_slider_handle = slider_handle;
	slider_prevod_slider_handle_step = slider_handle_step;
	slider_prevod_vmesne_labels = vmesne_labels;
	slider_prevod_vmesne_Crtice = vmesne_Crtice;
	slider_prevod_slider_MinMaxNumLabelNew = slider_MinMaxNumLabelNew;
	slider_prevod_slider_window_number = slider_window_number;
	slider_prevod_vmesne_descr_labele = vmesne_descr_labele;
	slider_prevod_tip_vmesne_descr_labele = tip_vmesne_descr_labele;
	slider_prevod_nakazi_odgovore = nakazi_odgovore;
	slider_prevod_minTemp = minTemp;
	slider_prevod_maxTemp = maxTemp;
	slider_prevod_slider_VmesneDescrLabel = slider_VmesneDescrLabel;
	//globalne spremenljivke, ki so potrebne za posodobitev drsnika v prevodih - konec
	
    $("#variabla_limit_" + spremenljivka).css('display', 'none');
    //$("input[name^='foo_" + spremenljivka + "']").parent().css('display', 'none');	//pokomnetiral zaradi skrivanja missing-ov
	
	var minmaxlabela = "label";//hrani nastavitev za minmax labele
	var rest = false;	//hrani nastavitve za vmesne črtice z ("label") in brez label ("pip") ter odsotnost črtic (false)
	var vmesne_opisne_labele = false;
	
	//ureditev handle kot bunkica in nakazovanje moznih odgovorov ************************************************************
	
	//ureditev handle kot bunkica	
	if (slider_handle == 1){//ce zelimo skriti handle
		$('#sliderbranching_prevajanje' + spremenljivka).slider().removeClass("classic_slider");	//odstrani razred s klasicnim handle
		$('#sliderbranching_prevajanje' + spremenljivka).slider().addClass("special_slider");	//dodaj razred s handle v obliki bunkice					
		$('#sliderbranching_prevajanje' + spremenljivka + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
	}
	else if (slider_handle == 0){ //drugace
		$('#sliderbranching_prevajanje' + spremenljivka).slider().addClass("classic_slider");	//dodaj klasicen razred
		$('#sliderbranching_prevajanje' + spremenljivka).slider().removeClass("special_slider");	//odstrani razred special slider
	}
	//konec ureditve handle kot bunkico
	
	//ureditev bunk in elips za nakazovanje moznih odgovorov
	if (nakazi_odgovore == 1 && slider_handle == 1){//ce zelimo bunke za nakazovanje odgovorov
		$('#sliderbranching_prevajanje' + spremenljivka).slider().removeClass("classic_slider");	//odstrani razred s klasicnimi crticami
		$('#sliderbranching_prevajanje' + spremenljivka).slider().addClass("circle_slider");	//dodaj razred z bunkicami za nakazovanje
		//$('#sliderbranching_' + spremenljivka + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
	}
	else if (nakazi_odgovore == 0 && slider_handle == 0){ //drugace
		$('#sliderbranching_prevajanje' + spremenljivka).slider().addClass("classic_slider");	//dodaj klasicen razred
		$('#sliderbranching_prevajanje' + spremenljivka).slider().removeClass("circle_slider");	//odstrani razred circle slider
	}
	else if (nakazi_odgovore == 1 && slider_handle == 0){ //drugace
		$('#sliderbranching_prevajanje' + spremenljivka).slider().addClass("elipse_slider");	//dodaj klasicen razred
		//$('#sliderbranching_' + spremenljivka).slider().removeClass("circle_slider");	//odstrani razred circle slider
	}	
	//konec ureditve bunk in elips za nakazovanje moznih odgovorov
	
	
	if (nakazi_odgovore == 1){	//ce vklopimo nakazovanje odgovorov
		vmesne_Crtice = 1;		//vklopi crtice, ki so v bistvu sredstvo za nakazovanje moznih odgovorov
	}
	
	//konec ureditve handle kot bunkica in nakazovanje moznih odgovorov *******************************************************

	if ( slider_MinMaxNumLabelNew == 1 ){
		minmaxlabela = "pip";
	} 
	else{
		minmaxlabela = "label";
	}
	
	if (vmesne_Crtice == 1){//ce je potrebno pokazati vmesne črtice
		rest = "pip";
		//console.log('Črtice');
	}

	else if (vmesne_Crtice == 0) {
		rest = false;
		//console.log('Brez črtic');
	}
	

	//if (vmesne_labels == 1) {
	if (vmesne_labels == 1 || tip_vmesne_descr_labele != 0) {
		rest = "label";
	}
	
/* 	if(tip_vmesne_descr_labele != 0 && prevod==''){ //ce se je izbralo prednalozene vmesne opisne labele in ni prevoda
		vmesne_opisne_labele = vmesne_descr_labele.split(";");
		max = vmesne_opisne_labele.length-1;
	}else if((slider_VmesneDescrLabel)||(tip_vmesne_descr_labele != 0 && prevod)){	//ce se je izbralo Brez oz. custom opisne labele ALI se je izbralo prednalozene vmesne opisne labele in je prevod
		vmesne_opisne_labele = slider_CustomDescriptiveLabels.split(";");		
		max = vmesne_opisne_labele.length;
	}	 */
	
	if((slider_VmesneDescrLabel)||(tip_vmesne_descr_labele != 0)){	//ce se je izbralo Brez oz. custom opisne labele ALI se je izbralo prednalozene vmesne opisne labele
		vmesne_opisne_labele = slider_CustomDescriptiveLabels.split(";");		
		max = vmesne_opisne_labele.length;
	}	
	
	$('#sliderbranching_prevajanje' + spremenljivka)
		.slider({	//uredi slider z labelami in oznakami stopnje
			step: slider_handle_step,
			value: def,
			min: min,
			max: max,
			
			slide: function (event, ui) {
				if (slider_window_number == 0){	//ce rabimo stevilo nad handle
					// Sproti popravljamo vrednost v okencu ob slidu
					$("#sliderTextbranching_" + spremenljivka).html(ui.value);

					// Premikamo okencek skupaj z sliderjem
					var delay = function () {
						$("#sliderTextbranching_" + spremenljivka).position({
							of: ui.handle,
							offset: "0, -37"
						});
					};
					// wait for the ui.handle to set its position
					setTimeout(delay, 5);
				}
				
				//if (slider_handle == 1){//ce si zelimo skriti handle
					//$('#sliderbranching_' + spremenljivka + ' .ui-slider-handle').css('visibility', '');//
				//}
				
			},

			// Prikazemo okencek s vrednostjo
			start: function (event, ui) {
				if (slider_window_number == 0){
					$("#sliderTextbranching_" + spremenljivka).position({//postavi okencek na pravo mesto ob neposrednem kliku na rocico
							of: ui.handle,
							offset: "0, -37"
					});
					$("#sliderTextbranching_" + spremenljivka).css('visibility', 'visible');
				}
				$('#sliderbranching_prevajanje' + spremenljivka + ' .ui-slider-handle').css('visibility', '');
			},

			// Skrijemo okencek s vrednostjo
			stop: function (event, ui) {
				if(slider_handle == 0){//ce handle ni skrit
					if (slider_window_number == 0){
						//$("#sliderTextbranching_" + spremenljivka).css('visibility', '');	//skrij okence
					}
				}
			},
					
			create: function (event, ui) {
				var percent_def = (def - min) / (max - min) * 100;
				if (percent_def < 0 || percent_def > 100) {
					percent_def = 50;
				}
				
				$('#sliderbranching_' + spremenljivka + ' .ui-slider-handle').css('left', percent_def + '%');
				

				//if (slider_handle == 1){//ce si zelimo skriti handle
					//$('#sliderbranching_' + spremenljivka + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
					//$('#sliderbranching_' + spremenljivka + ' .special_slider').css('visibility', '');//skrij handle
			//}

				
				// Postavimo na zacetku okencek na pravo mesto
				if (slider_window_number == 0){
					$("#sliderTextbranching_" + spremenljivka).position({
						of: $('#sliderbranching_' + spremenljivka + ' .ui-slider-handle'),
						//of: $('#sliderbranching_' + spremenljivka + ' .classic_slider'),
						offset: "0, -37"
					});
				}
			}
		})			
		.slider("pips",{
			rest: rest,
			first: minmaxlabela,	//skrij min in max vrednosti
			last: minmaxlabela,
			labels: vmesne_opisne_labele,
		});
	//$( ".selector" ).slider( "option", "value", 10 );
	$('#sliderbranching_prevajanje' + spremenljivka).slider("option", "value", def);//postavi rocico na mesto, kjer je izracunana default vrednost
}
//*********************************************************

//****************funkcija za izris grid sliderjev
function slider_edit_grid_init(spremenljivka, vrednost, min, max, def, vmesne_labels, vmesne_Crtice, slider_MinMaxNumLabelNew, slider_handle, slider_handle_step, slider_window_number, vmesne_descr_labele, tip_vmesne_descr_labele, nakazi_odgovore, minTemp, maxTemp, slider_VmesneDescrLabel, slider_CustomDescriptiveLabels) {	
	
	if(!slider_VmesneDescrLabel){
		min = minTemp;
		max = maxTemp;
		$('#slider_MinNumLabel_'+spremenljivka).val(min);
		$('#slider_MaxNumLabel_'+spremenljivka).val(max);		
	}
	
	$("input[name^=foo_" + vrednost + "]").css('display', 'none');

	var minmaxlabela = "label";//hrani nastavitev za minmax labele
	var rest = false;	//hrani nastavitve za vmesne črtice z ("label") in brez label ("pip") ter odsotnost črtic (false)
	var vmesne_opisne_labele = false;

	//ureditev handle kot bunkica in nakazovanje moznih odgovorov ************************************************************
	
	//ureditev handle kot bunkica	
	if (slider_handle == 1){//ce zelimo skriti handle
		$('#sliderbranching_' + spremenljivka + '_' + vrednost).slider().removeClass("classic_slider");	//odstrani razred s klasicnim handle
		$('#sliderbranching_' + spremenljivka + '_' + vrednost).slider().addClass("special_slider");	//dodaj razred s handle v obliki bunkice					
		$('#sliderbranching_' + spremenljivka + '_' + vrednost + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
	}
	else if (slider_handle == 0){ //drugace
		$('#sliderbranching_' + spremenljivka + '_' + vrednost).slider().removeClass("special_slider");	//odstrani razred special slider
		$('#sliderbranching_' + spremenljivka + '_' + vrednost).slider().addClass("classic_slider");	//dodaj klasicen razred
	}
	//konec ureditve handle kot bunkico
	
	//ureditev bunk in elips za nakazovanje moznih odgovorov
	if (nakazi_odgovore == 1 && slider_handle == 1){//ce zelimo bunke za nakazovanje odgovorov
		$('#sliderbranching_' + spremenljivka + '_' + vrednost).slider().removeClass("classic_slider");	//odstrani razred s klasicnimi crticami
		$('#sliderbranching_' + spremenljivka + '_' + vrednost).slider().addClass("circle_slider");	//dodaj razred z bunkicami za nakazovanje
		//$('#sliderbranching_' + spremenljivka + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
	}
	else if (nakazi_odgovore == 0 && slider_handle == 0){ //drugace
		$('#sliderbranching_' + spremenljivka + '_' + vrednost).slider().addClass("classic_slider");	//dodaj klasicen razred
		$('#sliderbranching_' + spremenljivka + '_' + vrednost).slider().removeClass("circle_slider");	//odstrani razred circle slider
	}
	else if (nakazi_odgovore == 1 && slider_handle == 0){ //drugace
		$('#sliderbranching_' + spremenljivka + '_' + vrednost).slider().addClass("elipse_slider");	//dodaj klasicen razred
		//$('#sliderbranching_' + spremenljivka).slider().removeClass("circle_slider");	//odstrani razred circle slider
	}	
	//konec ureditve bunk in elips za nakazovanje moznih odgovorov
	
	if (nakazi_odgovore == 1){	//ce vklopimo nakazovanje odgovorov
		vmesne_Crtice = 1;		//vklopi crtice, ki so v bistvu sredstvo za nakazovanje moznih odgovorov
	}
	
	//konec ureditve handle kot bunkica in nakazovanje moznih odgovorov *******************************************************

	if ( slider_MinMaxNumLabelNew == 1 ){
		minmaxlabela = "pip";
	} 
	else{
		minmaxlabela = "label";
	}
	
	if (vmesne_Crtice == 1){//ce je potrebno pokazati vmesne črtice
		rest = "pip";
		//console.log('Črtice');
	}
	else if (vmesne_Crtice == 0) {
		rest = false;
		//console.log('Brez črtic');
	}
	
	
	if (vmesne_labels == 1 || tip_vmesne_descr_labele != 0) {
		rest = "label";
	}
	
	if(tip_vmesne_descr_labele != 0){ //ce se je izbralo prednalozene vmesne opisne labele
		vmesne_opisne_labele = vmesne_descr_labele.split(";");
		max = vmesne_opisne_labele.length-1;	
	}else if(slider_VmesneDescrLabel){	//ce se je izbralo Brez oz. custom opisne labele
		vmesne_opisne_labele = slider_CustomDescriptiveLabels.split(";");		
		max = vmesne_opisne_labele.length;
	}

    $('#sliderbranching_' + spremenljivka + '_' + vrednost)
		.slider({
			step: slider_handle_step,
			value: def,
			min: min,
			max: max,

			slide: function (event, ui) {
				if (slider_window_number == 0){
					// Sproti popravljamo vrednost v okencu ob slidu
					$("#sliderTextbranching_" + spremenljivka + "_" + vrednost).html(ui.value);


					// Premikamo okencek skupaj z sliderjem
					var delay = function () {
						$("#sliderTextbranching_" + spremenljivka + "_" + vrednost).position({
							of: ui.handle,
							offset: "0, -37"
						});
					};
					// wait for the ui.handle to set its position
					setTimeout(delay, 5);
				}
			},

			// Prikazemo okencek s vrednostjo
			start: function (event, ui) {
				if (slider_window_number == 0){
					$("#sliderTextbranching_" + spremenljivka + "_" + vrednost).position({//postavi okencek na pravo mesto ob neposrednem kliku na rocico
							of: ui.handle,
							offset: "0, -37"
					});
					$("#sliderTextbranching_" + spremenljivka + "_" + vrednost).css('visibility', 'visible');
				}
				$('#sliderbranching_' + spremenljivka + '_' + vrednost + ' .ui-slider-handle').css('visibility', '');
			},

			// Skrijemo okencek s vrednostjo
			stop: function (event, ui) {
				if(slider_handle == 1){//ce handle ni skrit
					if (slider_window_number == 0){
						//$("#sliderTextbranching_" + spremenljivka + "_" + vrednost).css('visibility', 'hidden');
					}
				}
			},

			create: function (event, ui) {

				var width = $("input[name^=vrednost_" + vrednost + "_grid_]").parent().width();
				width = width - 80;
				$("#sliderbranching_" + spremenljivka + "_" + vrednost).width(width);
				var percent_def = (def - min) / (max - min) * 100;
				if (percent_def < 0 || percent_def > 100) {
					percent_def = 50;
				}
				$('#sliderbranching_' + spremenljivka + '_' + vrednost + ' .ui-slider-handle').css('left', percent_def + '%');
				
				//if($('#slider_handle_'+spremenljivka+' option:selected').val() == 2){//ce si zelimo skriti handle
				//if(slider_handle == 1){//ce si zelimo skriti handle
					//$('#sliderbranching_' + spremenljivka + '_' + vrednost + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
					//$('#slider_' + spremenljivka + ' .ui-slider-handle.ui-state-default.ui-corner-all').css('visibility', 'hidden');//skrij handle
			//}
				if (slider_window_number == 0){
					// Postavimo na zacetku okencek na pravo mesto
					$("#sliderTextbranching_" + spremenljivka + "_" + vrednost).position({
						of: $('#sliderbranching_' + spremenljivka + '_' + vrednost + ' .ui-slider-handle'),

						offset: "0, -37"
					});
				}
			}

    //});
		})			
		.slider("pips",{
			rest: rest,
			first: minmaxlabela,	//skrij min in max vrednosti
			last: minmaxlabela,
			labels: vmesne_opisne_labele,
		});
	$('#sliderbranching_' + spremenljivka + '_' + vrednost).slider("option", "value", def);//postavi rocico na mesto, kjer je izracunana default vrednost
}
//**********************************************************

//*************funkcija za izris grid sliderja pri prevajanju
function slider_edit_grid_init_prevajanje(spremenljivka, vrednost, min, max, def, vmesne_labels, vmesne_Crtice, slider_MinMaxNumLabelNew, slider_handle, slider_handle_step, slider_window_number, vmesne_descr_labele, tip_vmesne_descr_labele, nakazi_odgovore, minTemp, maxTemp, slider_VmesneDescrLabel, slider_CustomDescriptiveLabels) {
	
	//globalne spremenljivke, ki so potrebne za posodobitev drsnika v prevodih
	slider_prevod_min = min;
	slider_prevod_max = max;
	slider_prevod_def = def;
	slider_prevod_slider_handle = slider_handle;
	slider_prevod_slider_handle_step = slider_handle_step;
	slider_prevod_vmesne_labels = vmesne_labels;
	slider_prevod_vmesne_Crtice = vmesne_Crtice;
	slider_prevod_slider_MinMaxNumLabelNew = slider_MinMaxNumLabelNew;
	slider_prevod_slider_window_number = slider_window_number;
	slider_prevod_vmesne_descr_labele = vmesne_descr_labele;
	slider_prevod_tip_vmesne_descr_labele = tip_vmesne_descr_labele;
	slider_prevod_nakazi_odgovore = nakazi_odgovore;
	slider_prevod_minTemp = minTemp;
	slider_prevod_maxTemp = maxTemp;
	slider_prevod_slider_VmesneDescrLabel = slider_VmesneDescrLabel;
	//globalne spremenljivke, ki so potrebne za posodobitev drsnika v prevodih - konec
	
	
	$("input[name^=foo_" + vrednost + "]").css('display', 'none');

	var minmaxlabela = "label";//hrani nastavitev za minmax labele
	var rest = false;	//hrani nastavitve za vmesne črtice z ("label") in brez label ("pip") ter odsotnost črtic (false)
	var vmesne_opisne_labele = false;
	
	//ureditev handle kot bunkica in nakazovanje moznih odgovorov ************************************************************
	
	//ureditev handle kot bunkica	
	if (slider_handle == 1){//ce zelimo skriti handle
		$('#sliderbranching_prevajanje' + spremenljivka + '_' + vrednost).slider().removeClass("classic_slider");	//odstrani razred s klasicnim handle
		$('#sliderbranching_prevajanje' + spremenljivka + '_' + vrednost).slider().addClass("special_slider");	//dodaj razred s handle v obliki bunkice					
		$('#sliderbranching_prevajanje' + spremenljivka + '_' + vrednost + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
	}
	else if (slider_handle == 0){ //drugace
		$('#sliderbranching_prevajanje' + spremenljivka + '_' + vrednost).slider().removeClass("special_slider");	//odstrani razred special slider
		$('#sliderbranching_prevajanje' + spremenljivka + '_' + vrednost).slider().addClass("classic_slider");	//dodaj klasicen razred
	}
	//konec ureditve handle kot bunkico
	
	//ureditev bunk in elips za nakazovanje moznih odgovorov
	if (nakazi_odgovore == 1 && slider_handle == 1){//ce zelimo bunke za nakazovanje odgovorov
		$('#sliderbranching_prevajanje' + spremenljivka + '_' + vrednost).slider().removeClass("classic_slider");	//odstrani razred s klasicnimi crticami
		$('#sliderbranching_prevajanje' + spremenljivka + '_' + vrednost).slider().addClass("circle_slider");	//dodaj razred z bunkicami za nakazovanje
		//$('#sliderbranching_' + spremenljivka + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
	}
	else if (nakazi_odgovore == 0 && slider_handle == 0){ //drugace
		$('#sliderbranching_prevajanje' + spremenljivka + '_' + vrednost).slider().addClass("classic_slider");	//dodaj klasicen razred
		$('#sliderbranching_prevajanje' + spremenljivka + '_' + vrednost).slider().removeClass("circle_slider");	//odstrani razred circle slider
	}
	else if (nakazi_odgovore == 1 && slider_handle == 0){ //drugace
		$('#sliderbranching_prevajanje' + spremenljivka + '_' + vrednost).slider().addClass("elipse_slider");	//dodaj klasicen razred
		//$('#sliderbranching_' + spremenljivka).slider().removeClass("circle_slider");	//odstrani razred circle slider
	}	
	//konec ureditve bunk in elips za nakazovanje moznih odgovorov
	
	if (nakazi_odgovore == 1){	//ce vklopimo nakazovanje odgovorov
		vmesne_Crtice = 1;		//vklopi crtice, ki so v bistvu sredstvo za nakazovanje moznih odgovorov
	}
	
	//konec ureditve handle kot bunkica in nakazovanje moznih odgovorov *******************************************************

	if ( slider_MinMaxNumLabelNew == 1 ){
		minmaxlabela = "pip";
	} 
	else{
		minmaxlabela = "label";
	}
	
	if (vmesne_Crtice == 1){//ce je potrebno pokazati vmesne črtice
		rest = "pip";
		//console.log('Črtice');
	}
	else if (vmesne_Crtice == 0) {
		rest = false;
		//console.log('Brez črtic');
	}
	
	//if (vmesne_labels == 1) {
	if (vmesne_labels == 1 || tip_vmesne_descr_labele != 0) {
		rest = "label";
	}
	
	if((slider_VmesneDescrLabel)||(tip_vmesne_descr_labele != 0)){	//ce se je izbralo Brez oz. custom opisne labele ALI se je izbralo prednalozene vmesne opisne labele
		vmesne_opisne_labele = slider_CustomDescriptiveLabels.split(";");		
		max = vmesne_opisne_labele.length;
	}
	
    $('#sliderbranching_prevajanje' + spremenljivka + '_' + vrednost)
		.slider({
			step: slider_handle_step,
			value: def,
			min: min,
			max: max,

			slide: function (event, ui) {
				if (slider_window_number == 0){
					// Sproti popravljamo vrednost v okencu ob slidu
					$("#sliderTextbranching_" + spremenljivka + "_" + vrednost).html(ui.value);


					// Premikamo okencek skupaj z sliderjem
					var delay = function () {
						$("#sliderTextbranching_" + spremenljivka + "_" + vrednost).position({
							of: ui.handle,
							offset: "0, -37"
						});
					};
					// wait for the ui.handle to set its position
					setTimeout(delay, 5);
				}
			},

			// Prikazemo okencek s vrednostjo
			start: function (event, ui) {
				if (slider_window_number == 0){
					$("#sliderTextbranching_" + spremenljivka + "_" + vrednost).position({//postavi okencek na pravo mesto ob neposrednem kliku na rocico
							of: ui.handle,
							offset: "0, -37"
					});
					$("#sliderTextbranching_" + spremenljivka + "_" + vrednost).css('visibility', 'visible');
				}
				$('#sliderbranching_prevajanje' + spremenljivka + '_' + vrednost + ' .ui-slider-handle').css('visibility', '');
			},

			// Skrijemo okencek s vrednostjo
			stop: function (event, ui) {
				if(slider_handle == 1){//ce handle ni skrit
					if (slider_window_number == 0){
						//$("#sliderTextbranching_" + spremenljivka + "_" + vrednost).css('visibility', 'hidden');
					}
				}
			},

			create: function (event, ui) {

				var width = $("input[name^=vrednost_" + vrednost + "_grid_]").parent().width();
				width = width - 80;
				$("#sliderbranching_prevajanje" + spremenljivka + "_" + vrednost).width(width);
				var percent_def = (def - min) / (max - min) * 100;
				if (percent_def < 0 || percent_def > 100) {
					percent_def = 50;
				}
				$('#sliderbranching_prevajanje' + spremenljivka + '_' + vrednost + ' .ui-slider-handle').css('left', percent_def + '%');
				
				//if($('#slider_handle_'+spremenljivka+' option:selected').val() == 2){//ce si zelimo skriti handle
				//if(slider_handle == 1){//ce si zelimo skriti handle
					//$('#sliderbranching_' + spremenljivka + '_' + vrednost + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
					//$('#slider_' + spremenljivka + ' .ui-slider-handle.ui-state-default.ui-corner-all').css('visibility', 'hidden');//skrij handle
			//}
				if (slider_window_number == 0){
					// Postavimo na zacetku okencek na pravo mesto
					$("#sliderTextbranching_" + spremenljivka + "_" + vrednost).position({
						of: $('#sliderbranching_prevajanje' + spremenljivka + '_' + vrednost + ' .ui-slider-handle'),

						offset: "0, -37"
					});
				}
			}

    //});
		})			
		.slider("pips",{
			rest: rest,
			first: minmaxlabela,	//skrij min in max vrednosti
			last: minmaxlabela,
			labels: vmesne_opisne_labele,
		});
	$('#sliderbranching_prevajanje' + spremenljivka + '_' + vrednost).slider("option", "value", def);//postavi rocico na mesto, kjer je izracunana default vrednost
}
//*********************************************************************

function updateSliderOpisneLabele(spr_id, slider_NumofDescrLabels, prevod, grid){	
	if(prevod){
		var besediloOpisneLabele = '';
		for(var i=1;i<=slider_NumofDescrLabels;i++){
			//var besediloOpisneLabele = $('#slider_Labela_podrocja_'+i+'_'+spr_id).html();	//shranjuje besedilo opisne labele		
			besediloOpisneLabele = besediloOpisneLabele+$('#slider_Labela_opisna_'+i+'_'+spr_id+prevod).html()+'; ';	//shranjuje besedilo opisne labele		
			var tiplabele = $('#slider_Labela_opisna_'+i+prevod+'_'+spr_id).attr('name');	//shranjuje tip labele oz. atribut name			
		}
		postUpdatedSliderOpisneLabelePrevajanje(spr_id, tiplabele, besediloOpisneLabele, grid);
	}else{
		postUpdatedSliderOpisneLabele(spr_id, tiplabele, besediloOpisneLabele);
	}
}

function postUpdatedSliderOpisneLabele(spr_id, tiplabele, besediloOpisneLabele){
	//prenesi posodobljene labele na drsnik
	var post_data = {	//pripravi podatke za post-anje custom opisnih label
		anketa: srv_meta_anketa_id, 
		spremenljivka: spr_id,				
		tiplabele: tiplabele,
		besediloOpisneLabele: besediloOpisneLabele
	}
	$.post('ajax.php?t=vprasanje&a=vprasanje_save&silentsave=true',	//post-anje podatkov custom opisnih label
		post_data, 
		function () {
			vprasanje_save(true);
		}
	);
	//prenesi posodobljene labele na drsnik - konec
}

function postUpdatedSliderOpisneLabelePrevajanje(spr_id, tiplabele, besediloOpisneLabele, grid){
	//console.log(spr_id+' '+srv_meta_anketa_id+' '+srv_meta_lang_id+' '+tiplabele+' '+besediloOpisneLabele);
	
	//prenesi posodobljene prevedene labele za drsnik v bazo
	$.post('ajax.php?t=vprasanjeinline&a=inline_label_save', {spremenljivka: spr_id, anketa: srv_meta_anketa_id, lang_id: srv_meta_lang_id, tiplabele: tiplabele, label: besediloOpisneLabele});
	//prenesi posodobljene prevedene labele za drsnik v bazo - konec
		
	//uredi labele za prenos na drsnik
	var stripedbesediloOpisneLabele = besediloOpisneLabele.replace(/<[^>]+>/g, '');		
	var newStripedbesediloOpisneLabele = stripedbesediloOpisneLabele.substring(0, stripedbesediloOpisneLabele.length - 2);	//preureditev zaradi pojava dodatnega prostora na drsniku, ce se ne odstrani zadnjega ";"
	//console.log(newStripedbesediloOpisneLabele);
	//uredi labele za prenos na drsnik - konec

	//prenesi posodobljene prevedene labele na drsnik
	if(grid=='grid'){
		//pridobi vrednosti vseh drsnikov v tabeli in posodobi prevedene labele na vseh drsnikih v tabeli
		$('#slider_grid_'+spr_id).find('tr').find('td').each(function(){
			var vrednost = $(this).attr('id'); //vrednost trenutnega drsnika
			if(vrednost != null){	//ce trenutna vrednost obstaja
				//posodobitev trenutnega drsnika
				slider_edit_grid_init_prevajanje(spr_id, vrednost, slider_prevod_min, slider_prevod_max, slider_prevod_def, slider_prevod_vmesne_labels, slider_prevod_vmesne_Crtice, slider_prevod_slider_MinMaxNumLabelNew, slider_prevod_slider_handle, slider_prevod_slider_handle_step, slider_prevod_slider_window_number, slider_prevod_vmesne_descr_labele, slider_prevod_tip_vmesne_descr_labele, slider_prevod_nakazi_odgovore, slider_prevod_minTemp, slider_prevod_maxTemp, slider_prevod_slider_VmesneDescrLabel, newStripedbesediloOpisneLabele);
			}		
		});
		//pridobi vrednosti vseh drsnikov v tabeli in posodobi prevedene labele na vseh drsnikih v tabeli - konec
 	}else{
		slider_edit_init_prevajanje(spr_id, slider_prevod_min, slider_prevod_max, slider_prevod_def, slider_prevod_slider_handle, slider_prevod_slider_handle_step, slider_prevod_vmesne_labels, slider_prevod_vmesne_Crtice, slider_prevod_slider_MinMaxNumLabelNew, slider_prevod_slider_window_number, slider_prevod_vmesne_descr_labele, slider_prevod_tip_vmesne_descr_labele, slider_prevod_nakazi_odgovore, slider_prevod_minTemp, slider_prevod_maxTemp, slider_prevod_slider_VmesneDescrLabel, newStripedbesediloOpisneLabele);
	}	
	//prenesi posodobljene prevedene labele na drsnik - konec
}

function switchSliderOpisneLabeleEditMode(spr_id, prevod){
	var spr_id_nastavitev = $("#vprasanje_edit form input[name='spremenljivka'] ").val()
    if (spr_id_nastavitev == spr_id) {	//ce je odprto okno z nastavitvami
		$('#preview_opisne_labele_'+spr_id+prevod).css('display', 'block');
		$('#edit_opisne_labele_'+spr_id+prevod).css('display', 'none');
    } else {	//drugace
		$('#preview_opisne_labele_'+spr_id+prevod).css('display', 'none');
		$('#edit_opisne_labele_'+spr_id+prevod).css('display', 'block');
    }
/*  	console.log("nastavitve: "+spr_id_nastavitev);
	console.log(spr_id); */
}

function selectbox_dynamic_size(spremenljivka, text){	//funkcija za dinamicno urejanje stevila vidnih odgovorov v seznamu, ko je postavitev seznam
	//console.log(spremenljivka);
	//console.log(text);
	
	var vnosi = $('#spremenljivka_contentdiv'+spremenljivka+' span.faicon.move_updown.inline.inline_move').length;	//stevilo vnosov v obmocju editiranja						
	var vnosi_prej = $('#selectboxSize'+spremenljivka+' option').length;
	vnosi_prej = vnosi_prej + 1;
	var dodaj = vnosi - vnosi_prej;
	
	//console.log("Funkcija v seznamu!");

	
	var isEditing = $('#spremenljivka_contentdiv'+spremenljivka+' div.edit_mode.allow_new').is( ':visible' );//belezi ali je urejanje vprasanja vklopljeno
	//var isEditing = $("#spremenljivka_contentdiv'.$spremenljivka.' spremenljivka_content.spr_normalmode").is( ":visible" );//belezi ali je urejanje vprasanja vklopljeno
	var noVariabla_new = $('#spremenljivka_contentdiv'+spremenljivka+' #variabla_new').is( ':visible' ); //belezi, ali je prisotna moznost vnos nove kategorije
	if (isEditing){//ce uporabnik ureja vprasanje 
		vnosi = vnosi - 1;	//zmanjsaj za 1 belezeno število vnosov
		if (!noVariabla_new){//ce uporabnik ureja vprasanje, kjer ni dodane moznosti za nov vnos
			vnosi = vnosi + 1;	//zmanjsaj za 1 belezeno število vnosov
		}

	}
	//console.log("Novi vnosi: "+vnosi);
	//console.log("Is editing: "+isEditing);
	//console.log("No varaibla new: "+noVariabla_new);
	//console.log("Vnosi prej: "+vnosi_prej);
	
	if($('#selectboxSize'+spremenljivka+' option:selected').text() == text){//ce je tekst trenutne izbire "vse", nadaljuj
		var trenutnoStevilo = $('#selectboxSize'+spremenljivka+' option:selected').val();//trenutno izbrano stevilo vnosov, kjer trenunto pise "vse"
		$('#selectboxSize'+spremenljivka+' option:selected').text(trenutnoStevilo);	  //nadomesti tekst "vse" s stevilom
		
		$('#selectboxSize'+spremenljivka).empty();//sprazni dropdown s stevilom vnosov
		
		for (i=2; i<=vnosi; i++){
			if (i==vnosi){
				$('#selectboxSize'+spremenljivka).append('<option value='+i+'>'+text+'</option>');
				$('#selectboxSize'+spremenljivka).val(i);//spremeni vrednost dropdown-a s stevilom trenutnih vidnih vnosov
				$('#selectboxSize'+spremenljivka+' option:selected').text(text);//izbrano stevilo vnosov naj nadomesti tekst "vse"
			}
			else{
				$('#selectboxSize'+spremenljivka).append('<option value='+i+'>'+i+'</option>');
			}
		}
	}
	else if($('#selectboxSize'+spremenljivka+' option:selected').text() != text){//ce tekst trenutne izbire ni "vse"
		var trenutnoStevilo = $('#selectboxSize'+spremenljivka+' option:selected').val();//trenutno izbrano stevilo vnosov
		
		if (vnosi != vnosi_prej){
			$('#selectboxSize'+spremenljivka).empty();//sprazni dropdown s stevilom vnosov
			
			for (i=2; i<=vnosi; i++){
				//console.log(i);
					$('#selectboxSize'+spremenljivka).append('<option value='+i+'>'+i+'</option>');
			}
		
		}								
		
		for (i = 1; i <= vnosi+1; i++) {
			//console.log($("#selectboxSize'.$spremenljivka.' option[value="+i+"]").text());
			var vse = $('#selectboxSize'+spremenljivka+' option[value'+i+']').text();
			if(vse == text){
				$('#selectboxSize'+spremenljivka+' option[value='+i+']').text(i);
			}
		}								
		$('#selectboxSize'+spremenljivka+' option[value='+vnosi+']').text(text);//izbrano stevilo vnosov naj nadomesti tekst "vse"								
		
		$('#selectboxSize'+spremenljivka).val(trenutnoStevilo);//spremeni vrednost dropdown-a s stevilom trenutnih vidnih vnosov
		
	}
	

}


function selectbox_dynamic_size_other(spremenljivka, text){	//funkcija za dinamicno urejanje stevila vidnih odgovorov v seznamu, ko postavitev ni seznam
	var vnosi = $('#spremenljivka_contentdiv'+spremenljivka+' span.faicon.move_updown.inline.inline_move').length;	//stevilo vnosov v obmocju editiranja						
	var vnosi_prej = $('#selectboxSize'+spremenljivka+' option').length;
	vnosi_prej = vnosi_prej + 1;
	var dodaj = vnosi - vnosi_prej;
	
	//console.log("Funkcija izven seznama!");
	
	var isEditing = $('#spremenljivka_contentdiv'+spremenljivka+' div.edit_mode.allow_new').is( ':visible' );//belezi ali je urejanje vprasanja vklopljeno
	var noVariabla_new = $("#spremenljivka_contentdiv'.$spremenljivka.' #variabla_new").is( ":visible" ); //belezi, ali je prisotna moznost vnos nove kategorije
	
	if (noVariabla_new){//ce uporabnik ureja vprasanje, kjer je dodana moznost za nov vnos
		vnosi = vnosi - 1;	//zmanjsaj za 1 belezeno število vnosov
	}
	
	if($('#selectboxSize'+spremenljivka+' option:selected').text() == text){//ce je tekst trenutne izbire "vse", nadaljuj
		var trenutnoStevilo = $('#selectboxSize'+spremenljivka+' option:selected').val();//trenutno izbrano stevilo vnosov, kjer trenunto pise "vse"
		$('#selectboxSize'+spremenljivka+' option:selected').text(trenutnoStevilo);	  //nadomesti tekst "vse" s stevilom
		
		$('#selectboxSize'+spremenljivka).empty();//sprazni dropdown s stevilom vnosov
		
		for (i=2; i<=vnosi; i++){
			if (i==vnosi){
				$('#selectboxSize'+spremenljivka).append('<option value='+i+'>'+text+'</option>');
				$('#selectboxSize'+spremenljivka).val(i);//spremeni vrednost dropdown-a s stevilom trenutnih vidnih vnosov
				$('#selectboxSize'+spremenljivka+' option:selected').text(text);//izbrano stevilo vnosov naj nadomesti tekst "vse"
			}
			else{
				$('#selectboxSize'+spremenljivka).append('<option value='+i+'>'+i+'</option>');
			}
		}
	}
	else if($('#selectboxSize'+spremenljivka+' option:selected').text() != text){//ce tekst trenutne izbire ni "vse"
		var trenutnoStevilo = $('#selectboxSize'+spremenljivka+' option:selected').val();//trenutno izbrano stevilo vnosov
		
		if (vnosi != vnosi_prej){
			$('#selectboxSize'+spremenljivka).empty();//sprazni dropdown s stevilom vnosov
			
			for (i=2; i<=vnosi; i++){
				//console.log(i);
					$('#selectboxSize'+spremenljivka).append('<option value='+i+'>'+i+'</option>');
			}		
		}								
		
		for (i = 1; i <= vnosi+1; i++) {
			//console.log($("#selectboxSize'.$spremenljivka.' option[value="+i+"]").text());
			var vse = $('#selectboxSize'+spremenljivka+' option[value='+i+']').text();
			if(vse == text){
				$('#selectboxSize'+spremenljivka+' option[value='+i+']').text(i);
			}
		}								
		$('#selectboxSize'+spremenljivka+' option[value='+vnosi+']').text(text);//izbrano stevilo vnosov naj nadomesti tekst "vse"								
		
		$('#selectboxSize'+spremenljivka).val(trenutnoStevilo);//spremeni vrednost dropdown-a s stevilom trenutnih vidnih vnosov
		
	}
	

}


function checkbox_limit_dropdown_size(spremenljivka, textNo){	//funkcija za dinamicno urejanje omejitve minimalnega in maksimalnega stevila izbranih checkbox-ov
	var vnosi = $('#spremenljivka_contentdiv'+spremenljivka+' span.faicon.move_updown.inline.inline_move').length;	//stevilo vnosov v obmocju editiranja
	
	//var noVariabla_new = $("#spremenljivka_contentdiv'.$spremenljivka.' #variabla_new").is( ":visible" ); //belezi, ali je prisotna moznost vnos nove kategorije
	var noVariabla_new = $("#spremenljivka_contentdiv"+spremenljivka+" #vre_id_new").is( ':visible' ); //belezi, ali je prisotna moznost vnos nove kategorije
	
	if (noVariabla_new){//ce uporabnik ureja vprasanje, kjer je dodana moznost za nov vnos
		vnosi = vnosi - 1;	//zmanjsaj za 1 belezeno stevilo vnosov
	}

	var trenutnoSteviloMax = $('#checkbox_limit_'+spremenljivka+' option:selected').val();//trenutno izbrano stevilo vnosov za max
	var trenutnoSteviloMin = $('#checkbox_min_limit_'+spremenljivka+' option:selected').val();//trenutno izbrano stevilo vnosov za min
	
	$('#checkbox_limit_'+spremenljivka).empty();//sprazni dropdown s stevilom vnosov za max
	$('#checkbox_min_limit_'+spremenljivka).empty();//sprazni dropdown s stevilom vnosov za min
	
	for (i = 0; i <= vnosi; i++) {
		if(i == 0){
			$('#checkbox_limit_'+spremenljivka).append('<option value='+i+'>'+textNo+'</option>');
			$('#checkbox_min_limit_'+spremenljivka).append('<option value='+i+'>'+textNo+'</option>');
		}else{
			$('#checkbox_limit_'+spremenljivka).append('<option value='+i+'>'+i+'</option>');
			$('#checkbox_min_limit_'+spremenljivka).append('<option value='+i+'>'+i+'</option>');
		}		
	}
	
	$('#checkbox_limit_'+spremenljivka).val(trenutnoSteviloMax);//spremeni vrednost dropdown-a s stevilom za max
	$('#checkbox_min_limit_'+spremenljivka).val(trenutnoSteviloMin);//spremeni vrednost dropdown-a s stevilom trenutnih vidnih vnosov za min

}


//*********** funkcija za resize slik za hotspot, ko so te vecje od 260 px
function hotspot_image_resize(spr_id){
	
	var max_width = 260;
	var width = $('#hotspot_image_'+spr_id).children('img').css('width');
	var height = $('#hotspot_image_'+spr_id).children('img').css('height');
	
	if(width != undefined || height != undefined){	//ce je slika
		width = parseInt(width.replace('px',''));
		height = parseInt(height.replace('px',''));
	
		if (width > max_width){
			height = (height / width) * max_width;
			$('#hotspot_image_'+spr_id).children('img').css({width: max_width});
			$('#hotspot_image_'+spr_id).children('img').css({height: height});
		}
	}
}
//*********** konec - funkcija za resize slik za hotspot, ko so te vecje od 260 px

//********** funkcija za dinamicno spreminjanje teksta gumba za nalaganje ali urejanje slike
function hotspot_image_button_update(spr_id, srv_hot_spot_load_image, srv_hot_spot_edit_image){
	var hotspot_image = $('#hotspot_image').val();
	if ( (hotspot_image == '') || (hotspot_image.substring(0,4) != '<img') ){	//ce ni slika
		$('#hot_spot_regions_add_image_'+spr_id).text(srv_hot_spot_load_image);
	}
	else if (hotspot_image.substring(0,4) == '<img'){	//ce je slika
		$('#hot_spot_regions_add_image_'+spr_id).text(srv_hot_spot_edit_image);
	}
}
//********** konec - funkcija za dinamicno spreminjanje teksta gumba za nalaganje ali urejanje slike