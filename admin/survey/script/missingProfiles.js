function missingProfiles_init () {

	// klik na opcijo posameznega profila statusov	
	$("#missing_profile").live('click', function(event) {
		var $target = $(event.target);
		if ($target.hasClass('option')) {
			pid = $target.attr('value');
			$.post('ajax.php?t=missingProfiles&a=change_profile', {anketa: srv_meta_anketa_id, pid:pid}, function() {
				show_missing_profile_data(pid);
			});
		}
	});
};

function show_missing_profile_data(pid) {
	$("#div_missing_profiles").load('ajax.php?t=missingProfiles&a=show_profile', {anketa: srv_meta_anketa_id, pid:pid, meta_akcija:srv_meta_akcija});
}

function show_missing_profiles()
{
	$('#fade').fadeTo('slow', 1);

	// poiščemo center strani
	$("#div_missing_profiles").load('ajax.php?t=missingProfiles&a=show_profile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran});
	var msg = $('#div_analiza_missing_values');
    var height = $(window).height();
    var width = $(document).width();
	var left = width  - (msg.width() )-42;
	var top = height/2 - (msg.height() / 2);
	// pozicioniramo na center strani
	$("#div_missing_profiles").show(200).draggable({delay:100, cancel: 'input, .buttonwrapper'});
}

//funkcije ki skrbijo za profil missingov za missing
function missingProfileAction(action) {
	if (action == 'change') {
		$("#loading").show();
		var pid = $("#current_missing_profile").val();
		$.post('ajax.php?t=missingProfiles&a=change_profile', {anketa: srv_meta_anketa_id, pid:pid}, function() {
			missingProfileRefreshData(pid);
		});
	} else if (action == 'runSession' || action == 'run') {
		if (action == 'run') {
			var pid = $("#missing_profile .active").attr('value');
		} else {
			var pid = '-1';
		}
		
		var missing_values = "";
		var prefix="";
		
		$('input[name="profile_value[]"]:checked').each(function() {
			missing_values = missing_values + prefix + $(this).attr('id');
			prefix = ",";
		});
		
		var display_mv_type = $('input[name="display_mv_type"]:checked').val();
		var show_zerro = $("#show_zerro").is(':checked');
		var merge_missing = $("#merge_missing").is(':checked');

		$.post('ajax.php?t=missingProfiles&a=run_profile', {anketa: srv_meta_anketa_id, pid:pid, missing_values:missing_values, display_mv_type:display_mv_type, show_zerro: show_zerro, merge_missing: merge_missing}, function() {
			missingProfileRefreshData(pid);
		});
		// skrijemo vse dive
		missingProfileAction('cancle');
	} else if (action == 'newCancle') { // preklicemo nov profil
		$("#missingProfileCoverDiv").hide();
		$("#newProfile").hide();
	} else if (action == 'newName') { // dodelimo novo ime profilu
		$("#missingProfileCoverDiv").show();
		$("#newProfile").show();
	} else if (action == 'newSave') { // shranimo kot nov profil in pozenemo
		var pid = $("#missing_profile .active").attr('value');
		var name = $("#newProfileName").val();

		var missing_values = "";
		var prefix="";
		$('input[name="profile_value[]"]:checked').each(function() {
			missing_values = missing_values + prefix + $(this).attr('id');
			prefix = ",";
		});
		var display_mv_type = $('input[name="display_mv_type"]:checked').val();
		var show_zerro = $("#show_zerro").is(':checked');
		var merge_missing = $("#merge_missing").is(':checked');

		// kreiramo nov profil z novim id
		$.post('ajax.php?t=missingProfiles&a=save_profile', {anketa: srv_meta_anketa_id, pid:pid, name:name, missing_values:missing_values, display_mv_type:display_mv_type, show_zerro: show_zerro, merge_missing: merge_missing}, function(newId) {
			if (parseInt(newId) > 0) {
				$("#div_missing_profiles").load('ajax.php?t=missingProfiles&a=show_profile', {anketa: srv_meta_anketa_id, meta_akcija:srv_meta_akcija});
				// dropdownu dodamo nov prodil in ga izberemo
				$("#current_missing_profile").append($("<option></option>").attr("value",newId).attr("selected",true).text(name));
				$("#newProfile").hide();
				$("#missingProfileCoverDiv").fadeOut();

				missingProfileRefreshData(newId);
			}
		});

	} else if (action == 'cancle') {
		$("#div_missing_profiles").hide(200);
		$('#fade').fadeOut('slow');
		$("#div_missing_profiles").html('');
		return reloadData();
	} else if (action == 'deleteAsk') { // vprašamo po potrditvi za brisanje
		$("#missingProfileCoverDiv").show();
		$("#deleteProfileDiv").show();

	} else if (action == 'deleteCancle') { // preklicemo brisanje
		$("#deleteProfileDiv").hide();
		$("#missingProfileCoverDiv").fadeOut();
	} else if (action == 'deleteConfirm') { // izbrisemo profil
		var pid = $("#missing_profile .active").attr('value');

		$.post('ajax.php?t=missingProfiles&a=delete_profile', {anketa: srv_meta_anketa_id, pid:pid}, function() {
			$("#div_missing_profiles").load('ajax.php?t=missingProfiles&a=show_profile', {anketa: srv_meta_anketa_id, meta_akcija:srv_meta_akcija, pid:'1'});
			missingProfileRefreshData('1');
		});
		$("#deleteProfileDiv").hide();
		$("#missingProfileCoverDiv").fadeOut();
	} else if (action == 'renameAsk') { // vprašamo za preimenovanje
		$("#renameProfileDiv").show();
		$("#missingProfileCoverDiv").fadeIn();
	} else if (action == 'renameCancle') { // preklicemo preimenovanje
		$("#renameProfileDiv").hide();
		$("#missingProfileCoverDiv").fadeOut();
	} else if (action == 'rename') { // preimenujemo
		var pid = $("#missing_profile .active").attr('value');
		var name = $("#renameProfileName").val();
		$.post('ajax.php?t=missingProfiles&a=rename_profile', {anketa: srv_meta_anketa_id, pid:pid, name:name}, function(response) {
			if (parseInt(response) == 0) {
				$("#div_missing_profiles").load('ajax.php?t=missingProfiles&a=show_profile', {anketa: srv_meta_anketa_id, meta_akcija:srv_meta_akcija, pid:pid});
				$("#renameProfileDiv").hide();
				$("#missingProfileCoverDiv").fadeOut();
			} else {
				genericAlertPopup('alert_parameter_response',response);
			}
		});
	} else {
		genericAlertPopup('alert_parameter_action',action);
	}
}


function missingProfileRefreshData(pid) {
/*	// dropdownu izberemo profil
	$("#current_missing_profile").val(pid);
	if (__vnosi == 1) {
		$("#div_vnosi_data").load('ajax.php?a=vnosiReloadData', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran});
		// osvežimo tudi filtre
		$("#data_left_filter").load('ajax.php?a=vnosiReloadLeftFilter', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran});
		
	} else { 
		$("#div_analiza_data").load('ajax.php?t=analysis&a=reloadData', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran});
	}
*/
	return reloadData();
}