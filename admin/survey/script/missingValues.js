function missingValues_init () {
	
	$("input[name=missing_values_type]").live("click", function(event) {
		if (event.button != 0) { // wasn't the left button - ignore
			return true;
		}
		changeSurveyMissingSettings();
//		return false; // "capture" the click
	});
	/* dodan onclick
	 $("#link_use_sistem_mv").live("click", function(event) {
		if (event.button != 0) { // wasn't the left button - ignore
			return true;
		}
		useSystemMissingValues();
		return false; // "capture" the click
	});
	*/
	$('input[name=mv_value_input], input[name=mv_text_input]').live('keypress', function (evt) {
			evt = evt || window.event;
		    // START CHANGE: Allow arrows
			if(/^(37|39)$/i.test(evt.keyCode)) { return; }
		    // END CHANGE
		    if ( evt.keyCode == 13 || evt.keyCode == 9 ){
				saveSurveyMissingValue(evt,this);
			} else {
			    var charCode = evt.keyCode || evt.which;
			    var charStr = String.fromCharCode(charCode);
			    // ne pustimo znaka _
			    if (charStr == '_') {
			    	evt.preventDefault();
			    }
			    // če smo spremenili vrednost to zabelezimo
			    $(this).data('changed',true);
			}
		}).live('blur', function (e) { saveSurveyMissingValue(e,this); });
	$('span[name=mv_delete_img]').live('click', function (evt) {
		deleteSurveyMissingValue(this);
	});
	$('#mv_add_img').live('click', function (evt) {
		addSurveyMissingValue();
	});

};
function sysMissingValuesChangeMode(mode) {
	$("#sys_missing_values").load('ajax.php?t=missingValues&a=sysMissingValuesChangeMode', {mode: mode});
}

function sysMissingValuesAdd()
{
	var filter = jQuery.trim($("#sysMissingValues_filter_input_add").val());
    var text = jQuery.trim($("#sysMissingValues_text_input_add").val());
	if (filter != undefined && !(filter === '') && text != undefined && !(text === '' )) {
		$("#sys_missing_values").load('ajax.php?t=missingValues&a=sysMissingValuesAdd', {filter:filter, text:text});
	} else {
		genericAlertPopup('srv_missing_value_not_empty');
	}
}
function sysMissingValuesDelete(id)
{
	// najprej skrijemo div
// $("#sysMissingValues_div_"+id).hide();
	// nato z ajaksom pobrišemo vrednost v bazi
	$("#sys_missing_values").load('ajax.php?t=missingValues&a=sysMissingValuesDelete', {id:id});	
	// nato izbrišemo element v html
}
function sysMissingValuesSave(id) {
	var filter = jQuery.trim($("#sysMissingValues_filter_input_"+id).val());
    var text = jQuery.trim($("#sysMissingValues_text_input_"+id).val());
	if (filter != undefined && !(filter === '') && text != undefined && !(text === '') ) {
		$("#sys_missing_values").load('ajax.php?t=missingValues&a=sysMissingValuesSave', {filter:filter, text:text, id:id});
	} else {
		genericAlertPopup('srv_missing_value_not_empty');
	}

}

function changeSurveyMissingSettings() {
	var  missing_values_type = $("input[name=missing_values_type]:checked").val();
	$("#anketa_edit").load('ajax.php?t=missingValues&a=changeSurveyMissingSettings', {anketa: srv_meta_anketa_id, missing_values_type:missing_values_type});

}

function useSystemMissingValues() {
	if (confirm(lang['srv_missing_confirm_use_system'])) {
		$("#anketa_edit").load('ajax.php?t=missingValues&a=useSystemMissingValues', {anketa: srv_meta_anketa_id});
    }
}

function saveSurveyMissingValue(event,what) {
	var changed = $(what).data('changed');
	// samo če je bil tekst spremenjen, poženemo ajax za shranjevanje
	if (changed == true) {
		$("#anketa_edit").load('ajax.php?t=missingValues&a=saveSurveyMissingValue', {anketa: srv_meta_anketa_id, el_id:$(what).attr('id'),new_value:$(what).val()});
	}
}

function deleteSurveyMissingValue(what) {
	var delete_id = $(what).attr('id');
	var data = delete_id.split('_');
	var missing_value_label = $("#mv_value_"+data[2]+ "_"+ data[3]).val() + ' > ' + $("#mv_text_"+data[2]+ "_"+ data[3]).val(); 
	if (confirm(lang['srv_missing_confirm_delete'] + ' ' + missing_value_label +' ?')) {
		$("#anketa_edit").load('ajax.php?t=missingValues&a=srv_missing_confirm_delete', {anketa: srv_meta_anketa_id, delete_id:delete_id});		
	}
}

function addSurveyMissingValue(what) {
    $('#fullscreen').html('').fadeIn().draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
    $('#fade').fadeTo('fast', 0.5);
    $('#fullscreen').load('ajax.php?t=missingValues&a=srv_missing_add_new', {anketa: srv_meta_anketa_id} );
}

function addSurveyMissingValueCancel() {
	$('#fade').fadeOut('slow');
	$('#fullscreen').fadeOut();
}

function addSurveyMissingValueConfirm() {
	var mv_add_filter = $("#mv_add_filter").val();
	var mv_add_text = $("#mv_add_text").val();

	$.post('ajax.php?t=missingValues&a=srv_missing_confirm_add', {anketa: srv_meta_anketa_id, mv_add_filter:mv_add_filter, mv_add_text: mv_add_text}, function(response) {
		if (response == 'true') {
			$("#anketa_edit").load('ajax.php?t=missingValues&a=srv_missing_display', {anketa: srv_meta_anketa_id}, function() {
				$('#fade').fadeOut('slow');
				$('#fullscreen').fadeOut('slow');
			});
		} else {
			genericAlertPopup('alert_parameter_response',response);
		}
	});
}