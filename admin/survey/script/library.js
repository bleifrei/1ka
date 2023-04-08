//meta podatki
var srv_meta_anketa_id 	= $("#srv_meta_anketa").val();
var srv_meta_podstran 	= $("#srv_meta_podstran").val();
var srv_meta_grupa 		= $("#srv_meta_grupa").val();
var srv_meta_branching 	= $("#srv_meta_branching").val();

// poklice se iz htmlja ob prikazu librarija (init)
function library () {
	var lib_tab = $("input#lib_tab").val();
	var lib_prva = $("input#lib_prva").val();
	
    // nardimo ankete, vprasanja draggable
    $('div#libraryInner div.folder_container, div.anketa_vprasanja span').draggable({opacity:'0.5', zIndex:'50', helper: 'clone', appendTo: 'body', distance:5});
    
    // nardimo folderje draggable
    //if (!$.browser.msie) {		// ne dela zaradi nestable draggables buga
    $('div#libraryInner ul.can_edit li.folder').draggable({revert: 'invalid', opacitiy:'0.5', zIndex:'1', handle:'.movable', distance:5});
	//}
	
    // nardimo folderje droppable
    //$('div#libraryInner .folderdrop').droppable({accept: '.folder_container, ul .folder, .branch', hoverClass: 'grupahover', tolerance: 'pointer', 
    $('div#libraryInner ul.can_edit .folderdrop').droppable({accept: 'ul.can_edit .folder_container, ul.can_edit .folder, .branch, .spr, .if, .block', hoverClass: 'grupahover', tolerance: 'pointer', 
        drop: function (e, ui) {

    		if ($(ui.draggable).attr('name') == 'folder') {	// premikanje folderjev v librariju
            
                $('#libraryInner').load('ajax.php?t=library&a=folder_dropped', {drop: $(ui.draggable).attr('eid'), folder: $(this).attr('eid'), tab: lib_tab, prva: lib_prva});
            
            } else if ($(ui.draggable).attr('name') == 'library') {		// premikanje spremenljivk
            
                $('#libraryInner').load('ajax.php?t=library&a=spr_dropped', {spremenljivka: $(ui.draggable).attr('eid'), folder: $(this).attr('eid'), tab: lib_tab, prva: lib_prva});
            
            } else if ($(ui.draggable).attr('name') == 'library_if') {	// premikanje ifov
            
                $('#libraryInner').load('ajax.php?t=library&a=if_dropped', {'if': $(ui.draggable).attr('eid'), folder: $(this).attr('eid'), tab: lib_tab, prva: lib_prva});
            
            } else {			// dodajanje v library (karkoli ze pac je - spr, if, blok)
            
                $.post('ajax.php?t=library&a=library_add', {spremenljivka: $(ui.draggable).attr('id'), folder: $(this).attr('eid'), tab: lib_tab, anketa: srv_meta_anketa_id}, function(data) {
					
					$('#libraryInner').html(data.folders);
					$('#clipboard').html(data.response).show().delay('3000').slideUp();
					
                }, 'json');
                refreshLeft();
            
            }
        }
    });
    
    // preview vprasanja
    //$('div#libraryInner div.folder_container, div.anketa_vprasanja span').bind('mouseover', function (event) {
    $('div#libraryInner div.folder_container.new_spr, div.anketa_vprasanja span.new_spr').bind('mouseover', function (event) {
        var copy = $(event.target).closest('[copy]').attr('copy');
        
        if (copy > 0 && !is_new_spr_dragable) {
        	show_tip_preview_toolbox(0, copy);
        }
	}).bind('mouseout', function (event) {
		$("#tip_preview").hide();
	});
    
}

function library_spremenljivka_new (spremenljivka) {
	$('html, body').animate({scrollTop: $('body').height()+$('#branching').height()});
	spremenljivka_new(0, 0, 1, spremenljivka);
}

function library_if_new (copy) {
	$('html, body').animate({scrollTop: $('body').height()+$('#branching').height()});
	if_new(0, 0, 1, '0', copy);
}

// prikaze knjiznico
function display_knjiznica(tab) {
	var lib_prva = $("input#lib_prva").val();

    $('#library_holder').load('ajax.php?t=library&a=display_knjiznica', {tab: tab, prva: lib_prva, anketa: srv_meta_anketa_id});
    
}

// zamenja view knjiznice na prvi strani
function change_knjiznica(tab) {
	var lib_prva = $("input#lib_prva").val();
	$('#library').load('ajax.php?t=library&a=display_knjiznica', {tab: tab, prva: lib_prva});        
}

// doda spremenljivko v knjiznico
/*function knjiznica_dodaj (spremenljivka) {
	var lib_tab = $("input#lib_tab").val();
	var lib_prva = $("input#lib_prva").val();
	$('#branching_vprasanja').load('ajax.php?t=library&a=knjiznica_dodaj', {spremenljivka: spremenljivka, tab: lib_tab, prva: lib_prva, anketa: srv_meta_anketa_id});
}*/

function folder_rename (folder) {
	$('#sp'+folder).load('ajax.php?t=library&a=folder_rename', {folder: folder},
        function () {
            $('#naslov_'+folder).focus();
        }
    );
}
function library_folder_newname (folder) {
	var lib_tab = $("input#lib_tab").val();
	var lib_prva = $("input#lib_prva").val();
    $('#libraryInner').load('ajax.php?t=library&a=folder_newname', {folder: folder, naslov: $('#naslov_'+folder).attr('value'), tab: lib_tab, prva: lib_prva});
}

function library_new_folder (folder, uid) {
	var lib_tab = $("input#lib_tab").val();
	var lib_prva = $("input#lib_prva").val();
    $('#libraryInner').load('ajax.php?t=library&a=new_folder', {folder: folder, uid: uid, tab: lib_tab, prva: lib_prva});
}

function library_delete_folder (folder) {
	var lib_tab = $("input#lib_tab").val();
	var lib_prva = $("input#lib_prva").val();
    $('#libraryInner').load('ajax.php?t=library&a=delete_folder', {folder: folder, tab: lib_tab, prva: lib_prva});
}


function library_del_anketa (anketa, text) {
    if (confirm(text)) {
    	var lib_tab = $("input#lib_tab").val();
    	var lib_prva = $("input#lib_prva").val();
        $('#libraryInner').load('ajax.php?t=library&a=library_del_anketa', {anketa: anketa, tab: lib_tab, prva: lib_prva});
    }
}

function library_del_myanketa (anketa, text) {
    if (confirm(text)) {
    	var lib_tab = $("input#lib_tab").val();
    	var lib_prva = $("input#lib_prva").val();
        $('#libraryInner').load('ajax.php?t=library&a=library_del_myanketa', {anketa: anketa, tab: lib_tab, prva: lib_prva});
    }
}

function add_to_my_library () {
	
	$.post('ajax.php?t=library&a=library_add_myanketa', {anketa: srv_meta_anketa_id}, function () {
		window.location.href = 'index.php?anketa='+srv_meta_anketa_id+'&tab=2';
	});
	
}

function anketa_copy (ank_id, text) {
	var lib_prva = $("input#lib_prva").val();
	if (lib_prva == 1 ) {
		var naslov = $("#novaanketa_naslov").val(); 
		$.redirect('ajax.php?t=library&a=anketa_copy_new', {ank_id: ank_id, naslov: naslov});		
	} else {
		$.redirect('ajax.php?t=library&a=anketa_copy', {anketa:srv_meta_anketa_id, ank_id: ank_id});
	}
}

function anketa_copy_top (ank_id, hierarhija) {
	var hierarhija = hierarhija || 0;
	$.redirect('ajax.php?t=library&a=anketa_copy_new', {
		ank_id: ank_id,
		hierarhija: hierarhija
	});
}

function library_folders_plusminus (folder) {
	var lib_tab = $("input#lib_tab").val();
	var lib_prva = $("input#lib_prva").val();
    var sortable_if = document.getElementById('folder_'+folder).style;
    
    if (sortable_if.display != "none") {
    
        $('#folder_'+folder).slideUp();
            
        //$('#f_pm_'+folder).html('<img src="img/plus.png" class="folder_plusminus" style="width:12px; height:12px">');
        
        $('#f_pm_'+folder).load('ajax.php?t=library&a=folder_collapsed', {collapsed: 1, folder: folder, tab: lib_tab, prva:lib_prva});
        
    } else {
            
        $('#folder_'+folder).slideDown();
        
        //$('#f_pm_'+folder).html('<img src="img/minus.png" class="folder_plusminus" style="width:12px; height:12px">');
        
        $('#f_pm_'+folder).load('ajax.php?t=library&a=folder_collapsed', {collapsed: 0, folder: folder, tab:lib_tab, prva:lib_prva});
    }
}

function library_anketa_plusminus (anketa, _this) {
	
    var disp = document.getElementById('anketa_vprasanja_'+anketa).style;
    
    if (disp.display == "block") {
    	$('#anketa_vprasanja_'+anketa).slideUp();
    	$(_this).find('span').removeClass('minus').addClass('plus');
    
    } else {
        $('#anketa_vprasanja_'+anketa).slideDown();
    	$(_this).find('span').removeClass('plus').addClass('minus');
    }
}

// odstrani blok/if iz knjiznice
function library_if_remove (_if) {
	var lib_tab = $("input#lib_tab").val();
	var lib_prva = $("input#lib_prva").val();
	
    if (confirm(lang['srv_brisiifconfirm'])) {
        $('#libraryInner').load('ajax.php?t=library&a=if_remove', {'if': _if, anketa: srv_meta_anketa_id, tab:lib_tab, prva: lib_prva});
    }
}

// izbrise spremenljivko iz knjiznice
function library_brisi_spremenljivko (spremenljivka, text) {
	var lib_tab = $("input#lib_tab").val();
	var lib_prva = $("input#lib_prva").val();

    if (confirm(text)) {
        $('#libraryInner').load('ajax.php?t=library&a=brisi_spremenljivko', {spremenljivka: spremenljivka, grupa: srv_meta_grupa, anketa: srv_meta_anketa_id, branching: srv_meta_branching, tab:lib_tab, prva: lib_prva});
    }
}

function alert_copy_anketa(ank_id) {
	// prikazemo div z moznostmi za kopiranje ankete (1.prepise, 2.predhodno arhivira obstojeco, 3.preklici)
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow').draggable({delay:100, cancel: 'input, .buttonwrapper'});
	$('#fullscreen').load('ajax.php?t=library&a=alert_copy_anketa', {anketa: srv_meta_anketa_id, ank_id:ank_id});
}
function alert_copy_anketa_cancle() {
	$('#fullscreen').fadeOut('slow').html('');
	$('#fade').fadeOut('slow');
}

function anketa_archive_and_copy(anketa, ank_id) {
	$.redirect('ajax.php?t=library&a=anketa_archive_and_copy', {anketa:anketa, ank_id: ank_id});
}

function anketa_copy_no_archive(anketa, ank_id) {
	$.redirect('ajax.php?t=library&a=anketa_copy', {anketa:srv_meta_anketa_id, ank_id: ank_id });
}

function lib_show_vprasanja() {
	window.location = 'index.php?anketa='+srv_meta_anketa_id;
}

function check_library () {
	
	if ( $('input[name=javne_ankete]:checked').val() == '1' ) {
		
		$('input[name=moje_ankete][value=0]').attr('checked', 'true');
		$('#moje_ankete').hide();
		
	} else {
		$('#moje_ankete').show();
	}
	
}

