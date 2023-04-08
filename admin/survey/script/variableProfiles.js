function variableProfiles_init () {
};

function changeVariableProfile(pid)
{
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=variableProfile&a=displayProfile', {anketa:srv_meta_anketa_id, podstran:srv_meta_podstran, pid:pid});
}

//prikaže skrit div za nastavitev profilov variabel
function displayVariableProfile()
{
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=variableProfile&a=displayProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran});
}

function variableProfileSelectAll(val){

	$("#vp_list_ul input:checkbox").each(function() {
	
		if(val == '1'){
			this.checked = true;
		}
		else{
			this.checked = false;
		}
		
		variableProfileCheckboxChange(this);
	});
} 
function variableProfileCheckboxChange(what)
{
	$(what).is(':checked') 
		? $(what).parent().parent().addClass('selected') 
		: $(what).parent().parent().removeClass('selected');
} 
function variableProfileAction(action) {
	// izbere trenutno izbran profil
	if (action == 'choose') 
	{
		// najprej shranimo
		variableProfileAction('save');
		pid = $("#variable_profile div.active").attr('value');
		
		// ce izbiramo default profil (vse variable) in smo ga spremenili
		// ga shranimo v začasnjega
		if (pid == 0 && $("input[name=vp_list_li]:checked").length != $("input[name=vp_list_li]").length)
		{
			pid = -1
		}
		$(".divPopUp").fadeOut();
		$.post('ajax.php?t=variableProfile&a=chooseProfile', {anketa: srv_meta_anketa_id, meta_akcija: srv_meta_akcija, podstran: srv_meta_podstran, pid:pid}, function() {
			return reloadData();
		});
	} 
	else if (action == 'save') 
	{
		pid = $("#variable_profile div.active").attr('value');
		vp_list_li = $("input[name=vp_list_li]:checked").serialize();
		$.post('ajax.php?t=variableProfile&a=saveProfile', {anketa: srv_meta_anketa_id, pid:pid, vp_list_li:vp_list_li, meta_akcija: srv_meta_akcija, podstran: srv_meta_podstran});
	}
	else if (action == 'newName') 
	{ // dodelimo novo ime profilu
		$("#variableProfileCoverDiv").show();
		$("#newProfile").show();
	} 
	else if (action == 'newCancel') 
	{ // preklicemo nov profil
		$("#newProfile").hide();
		$("#variableProfileCoverDiv").fadeOut();
	} 
	else if (action == 'newSave') 
	{ // shranimo kot nov profil
		pid = $("#variable_profile div.active").attr('value');
		vp_list_li = $("input[name=vp_list_li]:checked").serialize();

		name = $("#newProfileName").val();

		// kreiramo nov profil z novim id
		$.post('ajax.php?t=variableProfile&a=saveNewProfile', 
			{anketa: srv_meta_anketa_id, pid:pid, vp_list_li:vp_list_li,name:name, meta_akcija: srv_meta_akcija, podstran: srv_meta_podstran}, 
			function(newId) {
					changeVariableProfile(newId);
			}
		);
	}
	else if (action == 'deleteAsk') 
	{ // vprašamo po potrditvi za brisanje
		$("#variableProfileCoverDiv").show();
		$("#deleteProfileDiv").show();
	}
	else if (action == 'deleteCancel') 
	{ // preklicemo brisanje
		$("#deleteProfileDiv").hide();
		$("#variableProfileCoverDiv").fadeOut();
	}
	else if (action == 'deleteConfirm') 
	{ // izbrisemo profil
		pid = $("#variable_profile div.active").attr('value');
		$.post('ajax.php?t=variableProfile&a=deleteProfile', {anketa: srv_meta_anketa_id, meta_akcija: srv_meta_akcija, podstran: srv_meta_podstran, pid:pid}, function() {
			displayVariableProfile();
		});
	}
	else if (action == 'renameAsk') 
	{ // vprašamo za preimenovanje
		$("#renameProfileDiv").show();
		$("#variableProfileCoverDiv").fadeIn();
	}
	else if (action == 'renameCancel')
	{ // preklicemo preimenovanje
		$("#renameProfileDiv").hide();
		$("#variableProfileCoverDiv").fadeOut();
	}
	else if (action == 'renameProfile') 
	{ // preimenujemo
		pid = $("#variable_profile div.active").attr('value');
		name = $("#renameProfileName").attr('value');
		$.post('ajax.php?t=variableProfile&a=renameProfile', {anketa: srv_meta_anketa_id, pid:pid, name:name}, function() {
			changeVariableProfile(pid);
		});
	}	
}

function removeVariableProfile() 
{
	var pid = '0';
	$.post('ajax.php?t=variableProfile&a=chooseProfile', {anketa: srv_meta_anketa_id, meta_akcija: srv_meta_akcija, podstran: srv_meta_podstran, pid:pid}, function() {
		return reloadData();
	});
}
/* profili variabel */
/*
function removeVariableProfile() 
{
	var profileId = '0';
	$.post('ajax.php?t=variableProfile&a=deleteProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, profileId:profileId}, function () {
		return reloadData();
	});
}


function hideVariablesProfiles() {
	variableProfileAction('cancle');
}
function changeViewVariablesProfile(_profileId){
	// samo posodobimo vsebino okna 
	var _pid = _profileId.split('variable_profile_');
	var profileId = _pid[1];

	$.post('ajax.php?t=variableProfile&a=changeProfileDropdown', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, profileId:profileId }, function() {
		// samo posodobimo vsebino okna 
		$("#div_variable_profiles").load('ajax.php?t=variableProfile&a=changeProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, profileId:profileId });
	});
}

function analiza_runAsSessionVariablesProfile() {
	var pid = 0;
	var data = $.dds.serialize( 'fs_list_2' );
	$.post('ajax.php?t=variableProfile&a=runProfile', {anketa: srv_meta_anketa_id, profileId:pid, data:data}, function() {
		return reloadData();
	});
}

function analiza_runVariablesProfile(msg) {
	var _pid = $(".option.active").attr("id").split('variable_profile_');
	var pid = _pid[1];
	var data = $.dds.serialize( 'fs_list_2' );

	if (pid == 1 && data.length > 0) {//profil vse spremenljivke lahko pozenemo samo če je prazen
		alert (msg);
		return ;
	}
	if (__vnosi == 1) {
		$.post('ajax.php?t=variableProfile&a=runProfile', {anketa: srv_meta_anketa_id, profileId:pid, data:data}, function() {
			return reloadData();
		});
	} else {
		$.post('ajax.php?t=variableProfile&a=analiza_runVariablesProfile', {anketa: srv_meta_anketa_id, profileId:pid, data:data}, function() {
			return reloadData();
		});
	}
}
function showHideNewVariableProfile(showhide) {
	if (showhide=='true') {
		$("#variableProfileCoverDiv").show();
		$("#newVariablesProfile").show();
	}
	else {
		$("#variableProfileCoverDiv").hide();
		$("#newVariablesProfile").hide();
	}
}
function createVariableProfile() {
	var profileName = $("#newVarProfileName").val();
	// počistimo ime profila
	$("#newVarProfileName").val("");
	var data = $.dds.serialize( 'fs_list_2' );	
	$.post('ajax.php?t=variableProfile&a=createProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, profileName:profileName, data:data }, function(newId) {
		variableProfileAction('showProfile');
		return reloadData();

	});
}
function clearDds() {
//	$.dds.moveAll('fs_list_2', 'fs_list_1');
	var _pid = $(".option.active").attr("id").split('variable_profile_');
	var pid = _pid[1];
	$("#div_variable_profiles").load('ajax.php?t=variableProfile&a=clearProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, pid:pid});
}
function showHideDeleteVariableProfile(showhide) {
	if (showhide=='true') {
		$("#variableProfileCoverDiv").show();
		$("#deleteProfileDiv").show();
	}
	else {
		$("#variableProfileCoverDiv").hide();
		$("#deleteProfileDiv").hide();
	}
}
function showHideRenameVariableProfile(showhide) {
	if (showhide=='true') {
		$("#variableProfileCoverDiv").show();
		$("#renameVariableProfileDiv").show();
	}
	else {
		$("#variableProfileCoverDiv").hide();
		$("#renameVariableProfileDiv").hide();
	}
}

function deleteVariableProfile() {
	var profileId = $("#deleteProfileId").val();
	$.post('ajax.php?t=variableProfile&a=deleteProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, profileId:profileId}, function () {
		$("#div_variable_profiles").load('ajax.php?t=variableProfile&a=loadProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran});
		analizaVariableProfileDropdownReloadData();
	});
}

function renameVariableProfile() {
	var newProfileName = $("#renameProfileName").val();
	var profileId = $("#renameProfileId").val();
	$("#renameProfileName").val("");
	$.post('ajax.php?t=variableProfile&a=renameProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, newProfileName: newProfileName, profileId:profileId}, function() {
		analizaVariableProfileDropdownReloadData();
		$("#div_variable_profiles").load('ajax.php?a=loadProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran});
	});
}

*/