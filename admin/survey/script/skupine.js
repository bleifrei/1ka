// dodajanje skupine
function add_skupina(skupine){

	var spr_id = $('#skupine_spr_id').val();
	var text = $('input[name=skupina]').val();
	
	$.post('ajax.php?t=skupine&a=add_skupina', {anketa: srv_meta_anketa_id, spr_id:spr_id, text:text, skupine:skupine}, function(response){
		//window.location.reload();
		$(response).insertBefore('.add_skupina_button');
		$('input[name=skupina]').val('').focus();
	});
}

// dodajanje skupine na enter
function add_skupina_enter(skupine, event){

	if(event.keyCode == 13){
		 add_skupina(skupine);
	}
}

// brisanje skupine
function delete_skupina(skupine, vre_id){
	
	var spr_id = $('#skupine_spr_id').val();
	
	$.post('ajax.php?t=skupine&a=delete_skupina', {anketa: srv_meta_anketa_id, skupine:skupine, spr_id:spr_id, vre_id:vre_id}, function(){
		window.location.reload();
	});
}


// Masovno dodajanje gesel - popup
function display_add_passwords_mass(){

    $('#fade').fadeTo('slow', 1);
    $("#popup_import_from_text").load('ajax.php?t=skupine&a=show_add_password_mass', {anketa: srv_meta_anketa_id});
    $("#popup_import_from_text").show();
}

// Masovno dodajanje gesel
function execute_add_passwords_mass(){
    
    // Uvoz vprasanj in variabel iz texta
    var spr_id = $('#skupine_spr_id').val();
    var passwords = $("textarea#add_passwords_mass").val().trim();

	$.redirect('ajax.php?t=skupine&a=add_password_mass', {anketa: srv_meta_anketa_id, spr_id:spr_id, passwords:passwords}, function(){
		popupImportAnketaFromText_close();
	});
}