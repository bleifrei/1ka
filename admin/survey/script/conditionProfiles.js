function conditionProfiles_init() {
	
	// klik na opcijo posameznega profila pogojev
	$("#condition_profile .option:not(.active)").live('click', function(event) {
		var $target = $(event.target);
		var pid = $target.attr('value');
		$.post('ajax.php?t=conditionProfile&a=change_condition_profile', { anketa : srv_meta_anketa_id, pid : pid}, function() {
			$("#div_condition_profiles").load( 'ajax.php?t=conditionProfile&a=show_condition_profile', { anketa : srv_meta_anketa_id, pid : pid, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran });
		});
	});	
	$("#link_condition_remove").live("click", function(event) {
		$.post('ajax.php?t=conditionProfile&a=condition_remove', { anketa : srv_meta_anketa_id}, function() {
			return reloadData();
		});
	});
	$("#link_condition_edit").live("click", function(event) {
		conditionProfileAction('showProfiles');
	});
};


// funkcije ki skrbijo za profil conditionov za podatke in izvoze (vse je v eni
// funkciji ločeno z action)
function conditionProfileAction(action) {
	if (action == 'showProfiles') {
		$('#fade').fadeTo('slow', 1);
		// poiščemo center strani
		$("#div_condition_profiles").load( 'ajax.php?t=conditionProfile&a=show_condition_profile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran }).show(200);
		
	} else  if (action == 'change') {
		$("#loading").show();
		var pid = $("#current_condition_profile").val();
		$.post('ajax.php?t=conditionProfile&a=change_condition_profile', { anketa : srv_meta_anketa_id, pid : pid}, function() {
			return reloadData();
		});
	} else if (action == 'cancle') {
		reloadData('condition');
		$("#div_condition_profiles").hide(200);
		//$('#fade').fadeOut('slow');
		$("#div_condition_profiles").html('');
	} else if (action == 'newName') { // dodelimo novo ime profilu
		$("#conditionProfileCoverDiv").show();
		$("#newProfile").show();
	} else if (action == 'newCancle') { // preklicemo nov profil
		$("#conditionProfileCoverDiv").hide();
		$("#newProfile").hide();
	} else if (action == 'newCreate') { // shranimo kot nov profil in pozenemo
		name = $("#newProfileName").val();
		// kreiramo nov profil z novim id
		$.post('ajax.php?t=conditionProfile&a=create_condition_profile', { 
			anketa : srv_meta_anketa_id, name : name, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran }, function(newId) {
			if (newId > 0) {
				$("#div_condition_profiles").load('ajax.php?t=conditionProfile&a=show_condition_profile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran, pid: newId });
				// dropdownu dodamo nov prodil in ga izberemo
					$("#current_condition_profile").append( $("<option></option>").attr("value", newId).attr( "selected", true).text(name));
			} else {
				alert('Error!');
			}

		});
	} else if (action == 'deleteAsk') { // vprašamo po potrditvi za brisanje
		$("#conditionProfileCoverDiv").show();
		$("#deleteProfileDiv").show();
	} else if (action == 'deleteCancle') { // preklicemo brisanje
		$("#deleteProfileDiv").hide();
		$("#conditionProfileCoverDiv").fadeOut();
	} else if (action == 'deleteConfirm') { // izbrisemo profil
		var pid = $("#condition_profile .active").attr('value');
		$.post('ajax.php?t=conditionProfile&a=delete_condition_profile', { anketa : srv_meta_anketa_id, pid : pid }, function() {
			$("#div_condition_profiles").load(
					'ajax.php?t=conditionProfile&a=show_condition_profile', {
						anketa : srv_meta_anketa_id,
						meta_akcija : srv_meta_akcija,
						podstran : srv_meta_podstran
					});
		});
		$("#deleteProfileDiv").hide();
		$("#conditionProfileCoverDiv").fadeOut();

	} else if (action == 'renameAsk') { // vprašamo za preimenovanje
		$("#renameProfileDiv").show();
		$("#conditionProfileCoverDiv").fadeIn();
	} else if (action == 'renameCancle') { // preklicemo preimenovanje
		$("#renameProfileDiv").hide();
		$("#conditionProfileCoverDiv").fadeOut();
	} else if (action == 'renameConfirm') { // preimenujemo  profil
		var pid = $("#condition_profile .active").attr('value');
		var name = $("#renameProfileName").val();
		$.post('ajax.php?t=conditionProfile&a=rename_condition_profile', { anketa : srv_meta_anketa_id, pid : pid, name:name  }, function() {
			$("#div_condition_profiles").load(
					'ajax.php?t=conditionProfile&a=show_condition_profile', {
						anketa : srv_meta_anketa_id,
						meta_akcija : srv_meta_akcija,
						podstran : srv_meta_podstran
					});
		});
		$("#renameProfileDiv").hide();
		$("#conditionProfileCoverDiv").fadeOut();
	} else if (action == 'run') {
		$("#loading").show();
		var pid = $("#condition_profile .active").attr('value');
		var condition_label = $("#div_condition_editing_conditions").html();
		// preverimo ali imamo error v if-u (kar pogledamo ali je error ikona vidna
		var condition_error = $("#div_condition_editing_conditions img").length == 0 ? '0' : '1';

		$.post('ajax.php?t=conditionProfile&a=change_condition_profile', { anketa : srv_meta_anketa_id, pid : pid, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran, condition_label:condition_label, condition_error:condition_error }, function() {
			return reloadData('condition');
		});
	} else {alert('Missing action:'+action)};
}