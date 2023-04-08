function showZoomSettings() {
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('')
	$('#zoom_div').load('ajax.php?t=zoom&a=showProfile', {anketa:srv_meta_anketa_id}).fadeIn('slow');	
}
function zoomChangeProfile(pid) {
	$.post( 'ajax.php?t=zoom&a=changeProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran, pid:pid }, function() {
		$("#zoom_div").load( 'ajax.php?t=zoom&a=showProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran, pid:pid });
	});
}
function removeZoomProfile() {
	$.post( 'ajax.php?t=zoom&a=changeProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran,pid:0 }, function() {
		return reloadData('zoom');
	});
}
function removeZoomCheckbox() {

	$.post( 'ajax.php?t=zoom&a=removeZoomCheckbox', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran,pid:0 }, function() 
		{
			return reloadData('zoom');
		}
	);
}
function zoomProfileAction(action) {
	
	if (action == 'cancel') {		
		$("#zoom_div").hide(200);	
		return reloadData('zoom');
		
	} else if (action == 'runProfile' || action == 'run_session_profile') {
		// poiščemo id izbranega profila
		if (action == 'runProfile') {
			var pid = $("#zoom_profiles .active").attr('value');
		} else {
			var pid = '-1';
		}
		
		var vars = new Array();
		$('input[name="zoom_vars"]:checked').each(function(index,el) {
			vars.push($(el).val());
		});
		
		if (vars.length > 0) {
			$("#zoom_div").hide(200);
			$("#zoom_div").load("ajax.php?t=zoom&a=saveProfile", {anketa:srv_meta_anketa_id, pid:pid, vars:vars, action:action}, function(response) {
				return reloadData('zoom');
			});
		} else {
			$('#zoom_div').load('ajax.php?t=zoom&a=showProfile', {anketa:srv_meta_anketa_id, error:'srv_zoom_error_no_var'}).fadeIn('slow');
		}
	} else if (action == 'showRename'){
		//$("#zoom_cover_div").show();
		$("#zoom_div div#renameProfileDiv").show();
	} else if (action == 'cancelRename'){
		//$("#zoom_cover_div").hide();
		$("#zoom_div div#renameProfileDiv").hide();
	} else if (action == 'doRename'){
		var pid = $("#zoom_profiles .active").attr('value');
		var name = $("#zoom_div div#renameProfileDiv input#renameProfileName").val();
		
		$.post('ajax.php?t=zoom&a=renameProfile', { anketa : srv_meta_anketa_id, pid : pid, name:name  }, function() {
			$("#zoom_div").load('ajax.php?t=zoom&a=showProfile', {
				anketa : srv_meta_anketa_id,
				meta_akcija : srv_meta_akcija,
				podstran : srv_meta_podstran
			}, function () {
				$("#zoom_div div#renameProfileDiv").hide();
				//$("#zoom_cover_div").fadeOut();
			});
		});

	} else if (action == 'showDelete'){
		//$("#zoom_cover_div").show();
		$("#zoom_div div#deleteProfileDiv").show();
	} else if (action == 'cancelDelete'){
		//$("#zoom_cover_div").hide();
		$("#zoom_div div#deleteProfileDiv").hide();
	} else if (action == 'newName'){
		var vars = new Array();
		$('#zoom_div input[name="zoom_vars"]:checked').each(function(index,el) {
			vars.push($(el).val());
		});

		if (vars.length > 0) {
			//$("#zoom_cover_div").show();
			$("#zoom_div div#newProfileDiv").show();
		} else {
			alert('Najprej izberite variable');
		}

	} else if (action == 'newCancel'){
		//$("#zoom_cover_div").hide();
		$("#zoom_div div#newProfileDiv").hide();
	} else if (action == 'newCreate'){
		var name = $("#zoom_div div#newProfileDiv input#newProfileName").val();
		var vars = new Array();
		$('#zoom_div input[name="zoom_vars"]:checked').each(function(index,el) {
			vars.push($(el).val());
		});
		
		// kreiramo nov profil z novim id
		//$.post('ajax.php?t=zoom&a=create_condition_profile', { 
		$.post('ajax.php?t=zoom&a=createNewProfile', { 
			anketa : srv_meta_anketa_id, name : name, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran, vars:vars }, function(data) {
				data = jQuery.parseJSON(data);
				if (data.error == '0') {
					if (data.newId > 0) {
						zoomChangeProfile(data.newId);
						//$("#div_condition_profiles").load('ajax.php?t=conditionProfile&a=show_condition_profile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran, pid: newId });
						// dropdownu dodamo nov prodil in ga izberemo
							//$("#current_condition_profile").append( $("<option></option>").attr("value", newId).attr( "selected", true).text(name));
					} else {
						alert(data.msg);
					}
				} else {
					alert(data.msg);
				}
		});
		//$("#zoom_cover_div").hide();
		//$("#zoom_div div#newProfileDiv").hide();
	} else if (action == 'doDelete'){
		var pid = $("#zoom_profiles div.active").attr('value');
		$.post('ajax.php?t=zoom&a=deleteProfile', { anketa : srv_meta_anketa_id, pid : pid }, function() {
			$("#zoom_div").load('ajax.php?t=zoom&a=showProfile', {
						anketa : srv_meta_anketa_id,
						meta_akcija : srv_meta_akcija,
						podstran : srv_meta_podstran
					});
		});
		$("#zoom_div div#deleteProfileDiv").hide();
		//$("#zoom_cover_div").fadeOut();
	} else {
		alert(action);	
	}
}

function  changeZoomCheckbox() {
	// poišemo vse obkljukane checkboxe
	var vars = new Array();
	$('#div_zoom_condition input:checked').each(function(index,el) {
		vars.push($(el).val());
	});
	
	$("#div_analiza_data").load('ajax.php?t=zoom&a=changeZoomCheckbox', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran, vars:vars});

	// če imamo pogoj  naj bo gumb odstrani samo spodaj pri pogoju
	if (vars.length > 0 ) {
		$("#span_zoom_condition_remove").hide();
	} else {
		$("#span_zoom_condition_remove").show();
	}
}

function toggleAllZoom(what) {
	// shranimo v sehjo extendet
	$.post('ajax.php?t=zoom&a=togleExtended', { anketa : srv_meta_anketa_id, what:what}, function() {
		
		if (what == 0) {
			$("#div_zoom_condition ul").parent().show();	
			$("#div_zoom_condition #zoomSpritesMinus").show();	
			$("#div_zoom_condition #zoomSpritesPlus").hide();	
		} else {
			$("#div_zoom_condition ul").parent().hide();	
			$("#div_zoom_condition #zoomSpritesMinus").hide();	
			$("#div_zoom_condition #zoomSpritesPlus").show();	
		}
		/*
		if (what == 0) {
			$("#div_zoom_condition ul li div:nth-child(2)").show();	
			$("#div_zoom_condition #zoomSpritesMinus").show();	
			$("#div_zoom_condition #zoomSpritesPlus").hide();	
		} else {
			$("#div_zoom_condition ul li div:nth-child(2)").hide();
			$("#div_zoom_condition #zoomSpritesMinus").hide();	
			$("#div_zoom_condition #zoomSpritesPlus").show();	
		}
		*/
	});
}

function toggleShowZoomVariables(what) {
	// shranimo v sehjo extendet
	$.post('ajax.php?t=zoom&a=toggleShowZoomVariables', { anketa : srv_meta_anketa_id, what:what}, function() {
		if (what == 0) {
			$("#div_zoom_condition").show();	
			$("#conditionProfileNote #zoomSpritesMinus1").show();	
			$("#conditionProfileNote #zoomSpritesPlus1").hide();	
		} else {
			$("#div_zoom_condition").hide();
			$("#conditionProfileNote #zoomSpritesMinus1").hide();	
			$("#conditionProfileNote #zoomSpritesPlus1").show();	
		}
	});
}