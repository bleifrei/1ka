function inspect_init() {
	
	$("#inspectListSpr").live('click', function(event) {
		//$('#dsp_div').fadeTo('slow', 0.5, function() {
		$("#inspect_cover_div").show();
		//});
		
		$('#fullscreen').html('').fadeIn('slow');
		$("#fullscreen").load('ajax.php?t=inspect&a=show_inspectListSpr', {anketa : srv_meta_anketa_id}).addClass('z-index200');
	});
	
	$("#dsp_inspect_cancel").live('click', function(event) {
		$("#inspect_cover_div").hide();
		$("#fullscreen").removeClass('z-index200').fadeOut();
	});
	
	$("#dsp_inspect_save").live('click', function(event) {
		var vars = new Array();
		$('input[name="dsp_inspect_vars"]:checked').each(function(index,el) {
			vars.push($(el).val());
		});
			
		$.post('ajax.php?t=inspect&a=saveInspectListVars', {anketa : srv_meta_anketa_id, vars:vars}, function() {
			$("#inspectListSpr").load('ajax.php?t=inspect&a=displayInspectVars', {anketa : srv_meta_anketa_id});
			$("#inspect_cover_div").hide();
			$("#fullscreen").removeClass('z-index200').fadeOut();
		});
	});

}


function show_inspect_settings() {
	$('#fade').fadeTo('slow', 1);
	$("#inspect_div").load("ajax.php?t=inspect&a=showInspectSettings", {anketa:srv_meta_anketa_id}, function() {
		
	}).show(200).draggable({delay:100, cancel: 'input, .buttonwrapper, .select'});

}

function inspectSaveSettings() {

	var enableInspect = $('input[name="enableInspect"]:checked').val();
	var inspectGoto = $('input[name=inspectGoto]:checked').val();
	$.post("ajax.php?t=inspect&a=saveSettings", {anketa:srv_meta_anketa_id, enableInspect:enableInspect, inspectGoto:inspectGoto}, function() {
		return reloadData('inpect');
	});
}
function inspectCloseSettings() {
	$("#fade").fadeOut();
	$("#inspect_div").hide(200);
}

function inspectRadioChange() {
	var inspectGoto = $('input[name=inspectGoto]:checked').val();
	if (inspectGoto == 2) {
		$("#inspectListDiv").show();
	} else {
		$("#inspectListDiv").hide();
	}
}

function inspectRemoveCondition(inspect_comeFrom) {
	$.post("ajax.php?t=inspect&a=removeInspect", {anketa:srv_meta_anketa_id}, function() {
		window.location = inspect_comeFrom;
		//return reloadData('inpect');
	});	
}

function doZoomFromInspect() {
	if ($("#div_zoom_condition").length > 0) {
		$.post("ajax.php?t=zoom&a=doZoomFromInspect", {anketa:srv_meta_anketa_id, showDiv:0}, function(response) {
			$("#div_zoom_condition").html(response).show();
		});
	} else {
		$.post("ajax.php?t=zoom&a=doZoomFromInspect", {anketa:srv_meta_anketa_id, showDiv:1}, function(response) {
			$("#globalSetingsHolder").append(response);	
		});
	}
}