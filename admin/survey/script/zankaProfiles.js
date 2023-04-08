function zankaProfiles_init() {
	
	$("#zanka_profile").live('click', function(event) {
		var $target = $(event.target);
		if ($target.hasClass('option')) {
			pid = $target.attr('value');
			changeViewZankaProfile(pid);
		}
	});
};
function changeViewZankaProfile(pid){
	// samo posodobimo vsebino okna 
	$("#div_zanka_profiles").load('ajax.php?t=zankaProfile&a=show_profile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, pid:pid });
}
	
// funkcije ki skrbijo za profil zank 
function zankaProfileAction(action) {
	if (action == 'showProfiles') {
		$('#fade').fadeTo('slow', 1);
		// poiščemo center strani
		$("#div_zanka_profiles").load( 'ajax.php?t=zankaProfile&a=show_profile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran }, function() {
			var msg = $('#div_zanka_profiles');
			var height = $(window).height();
			var width = $(document).width();
			var left = width - (msg.width()) - 42;
			var top = height / 2 - (msg.height() / 2);
			
		}).show(200).draggable( { delay : 100, cancel : '#fs_list, input, .buttonwrapper, .select' });
	} else if (action == 'cancle') {
		$("#div_zanka_profiles").hide(200);
		$('#fade').fadeOut('slow');
		$("#div_zanka_profiles").html('');
		return reloadData();
	} else if (action == 'newName') { // dodelimo novo ime profilu
		$("#zankaProfileCoverDiv").show();
		$("#newProfileDiv").show();

	} else if (action == 'newCancle') { // preklicemo nov profil
		$("#zankaProfileCoverDiv").hide();
		$("#newProfileDiv").hide();
	} else if (action == 'newCreate') { // shranimo kot nov profil in pozenemo
		var profileName = $("#newProfileName").val();
		var data = $.dds.serialize( 'fs_list_4' );	
		var mnozenje = 0;
		if ($('#mnozenje').is(':checked')) mnozenje = $('#mnozenje').val();
		$.post('ajax.php?t=zankaProfile&a=createProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, profileName:profileName, data:data, mnozenje:mnozenje }, function(newId) {
			zankaProfileAction('showProfiles');
		});
	} else if (action == 'deleteAsk') { // vprašamo po potrditvi za brisanje
		$("#zankaProfileCoverDiv").show();
		$("#deleteProfileDiv").show();
	} else if (action == 'deleteCancle') { // preklicemo brisanje
		$("#deleteProfileDiv").hide();
		$("#zankaProfileCoverDiv").fadeOut();
	} else if (action == 'deleteConfirm') { // izbrisemo profil
		var pid = $("#zanka_profile .active").attr('value');
		$.post('ajax.php?t=zankaProfile&a=delete_profile', { anketa : srv_meta_anketa_id, pid : pid }, function() {
			$("#div_zanka_profiles").load(
					'ajax.php?t=zankaProfile&a=show_profile', {
						anketa : srv_meta_anketa_id,
						meta_akcija : srv_meta_akcija,
						podstran : srv_meta_podstran
					});
		});
		$("#deleteProfileDiv").hide();
		$("#zankaProfileCoverDiv").fadeOut();
	} else if (action == 'renameAsk') { // vprašamo za preimenovanje
		$("#renameProfileDiv").show();
		$("#zankaProfileCoverDiv").fadeIn();
	} else if (action == 'renameCancle') { // preklicemo preimenovanje
		$("#renameProfileDiv").hide();
		$("#zankaProfileCoverDiv").fadeOut();
	} else if (action == 'renameConfirm') { // preimenujemo  profil
		var pid = $("#zanka_profile .active").attr('value');
		var name = $("#renameProfileName").val();
		
		$.post('ajax.php?t=zankaProfile&a=rename_profile', { anketa : srv_meta_anketa_id, pid : pid, name:name  }, function() {
			$("#div_zanka_profiles").load( 'ajax.php?t=zankaProfile&a=show_profile', {
				anketa : srv_meta_anketa_id,
				meta_akcija : srv_meta_akcija,
				podstran : srv_meta_podstran
			}, function () {
				$("#renameProfileDiv").hide();
				$("#zankaProfileCoverDiv").fadeOut();
			});
		});
	} else if (action == 'run' || action == 'runSession') { // shranimo kot nov profil in pozenemo
		var pid = $("#zanka_profile .active").attr('value');
		var data = $.dds.serialize( 'fs_list_4' );	
		var mnozenje = 0;
		if ($('#mnozenje').is(':checked')) mnozenje = 1;

		// ce imamo mnozjenje pustimo max 2 variabli
		if ( mnozenje * $("#fs_list_4 li").length > 2) {
			alert(lang['srv_loop_multiplication_error']);
		} else {
			$.post('ajax.php?t=zankaProfile&a=run', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, data:data, mnozenje:mnozenje, run:action, pid:pid }, function() {
//				window.location.reload();
//		    	return '';
				return reloadData('zanka');
			});
		}
	} else  if (action == 'change') {
		$("#loading").show();
		var pid = $("#current_zanka_profile").val();

		$.post('ajax.php?t=zankaProfile&a=change_profile', { anketa : srv_meta_anketa_id, pid : pid}, function() {
//			window.location.reload();
//	    	return '';
			return reloadData();
		});
	} else {
		alert('Missing action:'+action)
	};
}