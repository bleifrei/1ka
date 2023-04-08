// inicializiramo drag/drop anket in folderjev
function surveyList_folder_init() {

	$('#survey_list div.droppable').droppable({
		accept: '.mySurvey_draggable', 
		hoverClass: 'folderhover', 
		tolerance: 'pointer',
        drop: function (e, ui) {

			// Drop folderja
			if($(ui.draggable).hasClass('folder_title')){
				var drag_folder = $(ui.draggable).attr('folder_id');
				var parent = $(this).attr('folder_id');
				
				$.post('ajax.php?t=surveyList&a=folder_dropped', {parent: parent, drag_folder: drag_folder}, function(){
					window.location.reload();
				});
			}
			
			// Drop ankete
			if($(ui.draggable).hasClass('anketa_list')){
				var drag_survey = $(ui.draggable).attr('anketa_id');
				var parent = $(this).attr('folder_id');

				$.post('ajax.php?t=surveyList&a=survey_dropped', {parent: parent, drag_survey: drag_survey}, function(){
					window.location.reload();
				});
			}
        }
    });
	
	$('.mySurvey_draggable').draggable({
		revert: 'invalid', 
		opacitiy: '0.7', 
		helper: 'clone',
		cursor: 'move',
		cursorAt: { left: 20 },
		start: function(e, ui){
			$(ui.helper).addClass('mySurvey_draggable_helper');
		}
	});
}

// prikazemo/skrijemo ankete v folderju
function toggle_folder (folder) {
	
	var open = 0;
	if($('#folder_content_'+folder).hasClass('closed')){
		open = 1;
	}
	
	$.post('ajax.php?t=surveyList&a=folder_toggle', {folder: folder, open: open}, function(){
		if(open == 1){
			$('#folder_content_'+folder).removeClass('closed');
			$('#folder_'+folder).find('.plus').removeClass('plus').addClass('minus');
		}
		else{
			$('#folder_content_'+folder).addClass('closed');		
			$('#folder_'+folder).find('.minus').removeClass('minus').addClass('plus');
		}
	});
}

// Pobrisemo folder
function delete_folder (folder) {
	$.post('ajax.php?t=surveyList&a=folder_delete', {folder: folder}, function(){
		window.location.reload();
	});
}

// Ustvarimo folder
function create_folder (parent) {
	
    $('#survey_list').load('ajax.php?t=surveyList&a=folder_create', {parent: parent}, function(){
		
        var added_folder_id = $('#new_added_folder').val();
        
        edit_title_folder(added_folder_id);    
	});
}

// Urejamo ime folderja
function edit_title_folder (folder) {	
	
	var text = $('#folder_title_text_'+folder).text();

	$('#folder_title_text_'+folder).html('<input type="text" name="folder_title_edit" folder="'+folder+'" id="folder_title_edit_'+folder+'" class="folder_title_edit" value="'+text+'" onBlur="rename_folder(\''+folder+'\'); return false;" />');
	$('#folder_title_edit_'+folder).select();
}
// Preimenujemo folder
function rename_folder(folder){

	var text = $('#folder_title_edit_'+folder).val();

	$.post('ajax.php?t=surveyList&a=folder_rename', {folder: folder, text: text}, function(){
		$('#folder_title_text_'+folder).html('<a href="#" onClick="edit_title_folder(\''+folder+'\'); return false;">'+text+'</a>');
	});
}
// Kopiramo folder
function copy_folder(folder){

	$.post('ajax.php?t=surveyList&a=folder_copy', {folder: folder}, function(){
		window.location.reload();
	});
}


// Preklopimo med prikazom folderjev in navadnim prikazom
function switchFolder(show){
	if(show == 1)
		var show_folders = 0;
	else
		var show_folders = 1;

	$('#survey_list').load('ajax.php?a=surveyList_folders', {show_folders:show_folders});
}


/** Izbrise anketo
 * 
 * @param anketa
 * @param confirmtext
 * @return
 */
function anketa_delete_list (anketa, confirmtext) {
	if (confirm(confirmtext)) {
		$("#anketa_list_"+anketa).slideUp();
        $.post('ajax.php?a=anketa_delete', {anketa: anketa, 'inList': 'true'}, function(response) {
        	if (response == '0') {
        		window.location = 'index.php';
        	}
        });
	}
}


/** doda/odstrani anketo v sistemsko knjiznico in refresa ikono za knjiznico ankete
 * 
 */
function surveyList_knjiznica (anketa) {
	$("ul#surveyList").find("li#anketa_list_"+anketa).find(".sl_lib_glb").load('ajax.php?t=surveyList&a=surveyList_knjiznica', {anketa: anketa});
}
/** navadnega uporabnika obvesti da nima dostopa za dodajanje v sistemsko knjiznico
 * 
 */
function surveyList_knjiznica_noaccess (msg) {
	genericAlertPopup('alert_parameter_msg');
}

/** doda/odstrani anketo v uporabnisko knjiznico in refresa ikono za knjiznico ankete
* 
*/
function surveyList_myknjiznica (anketa) {
	$("ul#surveyList").find("li#anketa_list_"+anketa).find(".sl_lib_usr").load('ajax.php?t=surveyList&a=surveyList_myknjiznica', {anketa: anketa});
    //$('#folders').load('ajax.php?t=folders&a=folders_myknjiznica', {anketa: anketa});
}

function surveyList_myknjiznica_new (anketa) {
	$.post('ajax.php?t=surveyList&a=surveyList_myknjiznica_new', {anketa: anketa}, function() {
		window.location.reload();
	});
}
function surveyList_knjiznica_new (anketa) {
	$.post('ajax.php?t=surveyList&a=surveyList_knjiznica_new', {anketa: anketa}, function() {
		window.location.reload();
	});
}
