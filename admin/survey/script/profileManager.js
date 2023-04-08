$("#profileManager_profile.select div.option").live('click', function(event) {
	var $target = $(event.target);
	var pid = $target.attr('value');
	$("#fullscreen").load('ajax.php?t=profileManager&m=changeProfile', { anketa : srv_meta_anketa_id, pid : pid});
});	

function profileManager_displayProfiles() {
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=profileManager&m=displayProfiles', {anketa:srv_meta_anketa_id});
}

function profileManager_newName(pid) {
	// dodelimo novo ime profilu
	$("#profileManagerCoverDiv").show();
	$("#newProfile").show();
}

function profileManager_saveNew(pid) {
	
	var newName = $('#newProfileName').val();
	var form_serialize = $("#profileManager_form").serializeArray();
	form_serialize[form_serialize.length] = {name:'anketa', value:srv_meta_anketa_id}
	form_serialize[form_serialize.length] = {name:'pid', value:pid}
	form_serialize[form_serialize.length] = {name:'newName', value:newName}
	
	$("#fullscreen").load('ajax.php?t=profileManager&m=saveNew', form_serialize);
}
function profileManager_save(pid,asNew) {

	var form_serialize = $("#profileManager_form").serializeArray();
	form_serialize[form_serialize.length] = {name:'anketa', value:srv_meta_anketa_id}
	form_serialize[form_serialize.length] = {name:'pid', value:pid}
	form_serialize[form_serialize.length] = {name:'asNew', value:asNew}
	
	$("#fullscreen").load('ajax.php?t=profileManager&m=save', form_serialize);
}

function profileManager_delete(pid) {
	$("#fullscreen").load('ajax.php?t=profileManager&m=delete', { anketa : srv_meta_anketa_id, pid : pid});
}
function profileManager_choose(pid) {
	var form_serialize = $("#profileManager_form").serializeArray();
	form_serialize[form_serialize.length] = {name:'anketa', value:srv_meta_anketa_id}
	form_serialize[form_serialize.length] = {name:'pid', value:pid}
	
	$.post('ajax.php?t=profileManager&m=choose', form_serialize, function(){
		reloadData();
	});
}
