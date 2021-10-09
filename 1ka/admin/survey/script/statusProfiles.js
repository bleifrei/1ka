function show_status_profile_data(pid) {
	$("#fullscreen").load('ajax.php?t=statusProfile&a=displayProfile', {anketa: srv_meta_anketa_id, pid:pid, meta_akcija: srv_meta_akcija, podstran: srv_meta_podstran});
}

// prikaže skrit div za nastavitev statusov pri vnosih in analizah
function show_status_profile() {
	
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=statusProfile&a=displayProfile', {anketa: srv_meta_anketa_id, meta_akcija:srv_meta_akcija, podstran: srv_meta_podstran});
}
//funkcije ki skrbijo za profil statusov za podatke in izvoze (vse je v eni funkciji ločeno z action)
function statusProfileAction(action) {
	
	if (action == 'choose') 
	{
		$(".divPopUp").fadeOut();
		pid = $("#status_profile .active").attr('value');
		$.post('ajax.php?t=statusProfile&a=chooseProfile', {anketa: srv_meta_anketa_id, pid:pid, meta_akcija: srv_meta_akcija, podstran: srv_meta_podstran}, function() {
			return reloadData('status');
		});
	} 
	else if (action == 'save') 
	{
		pid = $("#status_profile .active").attr('value');
		var statusCnt = 0;
		var srv_userstatus = "";
		prefix="";
		$("input[name^=srv_userstatus]:checked").each(function() {
			srv_userstatus = srv_userstatus + prefix + $(this).attr('id');
			prefix = ",";
			statusCnt=statusCnt+1;
		});
		var lurker = $("input[name=srv_us_lurker]:checked").val();
		var testni = $("input[name=srv_us_testni]:checked").val();
		
		var nonusable = 0;
		var partusable = 0;
		var usable = 0;
		if($("input[name=srv_us_nonusable]").attr('checked'))
			nonusable = 1;
		if($("input[name=srv_us_partusable]").attr('checked'))
			partusable = 1;
		if($("input[name=srv_us_usable]").attr('checked'))
			usable = 1;
		
		$.post('ajax.php?t=statusProfile&a=saveProfile', {anketa: srv_meta_anketa_id, pid:pid, status:srv_userstatus, testni:testni, lurker:lurker, nonusable:nonusable, partusable:partusable, usable:usable, meta_akcija: srv_meta_akcija, podstran: srv_meta_podstran});
	} 
	else if (action == 'newSave') 
	{ // shranimo kot nov profil
		pid = $("#status_profile .active").attr('value');
		name = $("#newProfileName").val();
		// polovimo statuse
		var statusCnt = 0;
		var srv_userstatus = "";
		prefix="";
		$("input[name^=srv_userstatus]:checked").each(function() {
			srv_userstatus = srv_userstatus + prefix + $(this).attr('id');
			prefix = ",";
			statusCnt=statusCnt+1;
		});
		var lurker = $("input[name=srv_us_lurker]:checked").val();
		var testni = $("input[name=srv_us_testni]:checked").val();
		
		var nonusable = 0;
		var partusable = 0;
		var usable = 0;
		if($("input[name=srv_us_nonusable]").attr('checked'))
			nonusable = 1;
		if($("input[name=srv_us_partusable]").attr('checked'))
			partusable = 1;
		if($("input[name=srv_us_usable]").attr('checked'))
			usable = 1;
		
		// kreiramo nov profil z novim id
		$.post('ajax.php?t=statusProfile&a=save_status_profile', {anketa: srv_meta_anketa_id, pid:pid, name:name, status:srv_userstatus, testni:testni, lurker:lurker, nonusable:nonusable, partusable:partusable, usable:usable, meta_akcija: srv_meta_akcija, podstran: srv_meta_podstran}, function(newId) {
			show_status_profile_data(newId);
		});
	} else if (action == 'runSession') {
		var pid = '-1';
		var statusCnt = 0;
		var srv_userstatus = "";
		prefix="";
		$("input[name^=srv_userstatus]:checked").each(function() {
			srv_userstatus = srv_userstatus + prefix + $(this).attr('id');
			prefix = ",";
			statusCnt=statusCnt+1;
		});
		var lurker = $("input[name=srv_us_lurker]:checked").val();
		var testni = $("input[name=srv_us_testni]:checked").val();
		
		var nonusable = 0;
		var partusable = 0;
		var usable = 0;
		if($("input[name=srv_us_nonusable]").attr('checked'))
			nonusable = 1;
		if($("input[name=srv_us_partusable]").attr('checked'))
			partusable = 1;
		if($("input[name=srv_us_usable]").attr('checked'))
			usable = 1;
			
		$.post('ajax.php?t=statusProfile&a=run_status_profile', {anketa: srv_meta_anketa_id, pid:pid, status:srv_userstatus, testni:testni, lurker:lurker, nonusable:nonusable, partusable:partusable, usable:usable, meta_akcija: srv_meta_akcija, podstran: srv_meta_podstran}, function() {
			return reloadData('status');
		});
	} else if (action == 'run') {
		pid = $("#status_profile .active").attr('value');
		// polovimo statuse
		var statusCnt = 0;
		var srv_userstatus = "";
		prefix="";
		$("input[name^=srv_userstatus]:checked").each(function() {
			srv_userstatus = srv_userstatus + prefix + $(this).attr('id');
			prefix = ",";
			statusCnt=statusCnt+1;
		});
		var lurker = $("input[name=srv_us_lurker]:checked").val();
		var testni = $("input[name=srv_us_testni]:checked").val();
		
		var nonusable = 0;
		var partusable = 0;
		var usable = 0;
		if($("input[name=srv_us_nonusable]").attr('checked'))
			nonusable = 1;
		if($("input[name=srv_us_partusable]").attr('checked'))
			partusable = 1;
		if($("input[name=srv_us_usable]").attr('checked'))
			usable = 1;
			
		$.post('ajax.php?t=statusProfile&a=run_status_profile', {anketa: srv_meta_anketa_id, pid:pid, status:srv_userstatus, testni:testni, lurker:lurker, nonusable:nonusable, partusable:partusable, usable:usable, meta_akcija: srv_meta_akcija, podstran: srv_meta_podstran}, function() {
			return reloadData('status');
		});
	} else if (action == 'cancel') {
		$("#div_status_values").hide(200);
//		$('#fade').fadeOut('slow');
		$("#div_status_values").html('');
		return reloadData('status');
	} else if (action == 'newCancel') { // preklicemo nov profil
		$("#statusProfileCoverDiv").hide();
		$("#newProfile").hide();
	} else if (action == 'newName') { // dodelimo novo ime profilu
		$("#statusProfileCoverDiv").show();
		$("#newProfile").show();
	} else if (action == 'deleteAsk') 
	{ // vprašamo po potrditvi za brisanje
		$("#statusProfileCoverDiv").show();
		$("#deleteProfileDiv").show();
	}
	else if (action == 'deleteCancel') 
	{ // preklicemo brisanje
		$("#deleteProfileDiv").hide();
		$("#statusProfileCoverDiv").fadeOut();
	}
	else if (action == 'deleteConfirm') 
	{ // izbrisemo profil
		pid = $("#status_profile .active").attr('value');
		$.post('ajax.php?t=statusProfile&a=deleteProfile', {anketa: srv_meta_anketa_id, meta_akcija: srv_meta_akcija, podstran: srv_meta_podstran, pid:pid}, function() {
			show_status_profile();
		});
	}
	else if (action == 'renameAsk') 
	{ // vprašamo za preimenovanje
		$("#renameProfileDiv").show();
		$("#statusProfileCoverDiv").fadeIn();
	}
	else if (action == 'renameCancel')
	{ // preklicemo preimenovanje
		$("#renameProfileDiv").hide();
		$("#statusProfileCoverDiv").fadeOut();
	}
	else if (action == 'renameProfile') 
	{ // preimenujemo
		pid = $("#status_profile .active").attr('value');
		name = $("#renameProfileName").attr('value');
		//$.post('ajax.php?t=statusProfile&a=renameProfile', {anketa: srv_meta_anketa_id, pid:pid, name:name}, function() {
		$.post('ajax.php?t=statusProfile&a=renameProfile', {anketa: srv_meta_anketa_id, pid:pid, name:name}, function() {
			show_status_profile_data(pid);
		});

	}	
}

function statusProfileRefreshData(pid) {
	// dropdownu izberemo profil
	$("#current_status_profile").val(pid);
	return reloadData('status');
}

function showColectDataSetting() {
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=statusProfile&a=showColectDataSetting', {anketa:srv_meta_anketa_id});
}


function changeColectDataStatus() {
	var collect_all_status = $('input[name="collect_all_status"]').is(':checked') ? '0' : '1';
	$.post('ajax.php?t=statusProfile&a=saveCollectDataSetting', {anketa: srv_meta_anketa_id, collect_all_status:collect_all_status}, function(response) {
		return reloadData('status');
	});
}

function changeOnlyValidRadio() {

	var checked = $('input[name=statusOnlyValid]:checked').val();

	$.post('ajax.php?t=statusProfile&a=changeOnlyValidRadio', {anketa: srv_meta_anketa_id,meta_akcija: srv_meta_akcija, podstran: srv_meta_podstran, checked:checked}, function(response) {
		return reloadData('status');
	});
	
}