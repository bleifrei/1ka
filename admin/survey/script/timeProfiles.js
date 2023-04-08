function timeProfiles_init() {

	$("#link_time_profile_remove").live("click", function(event) {
		var pid = '0';
		$.post( 'ajax.php?t=timeProfile&a=changeProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran,pid:pid }, function() {
			return reloadData('interval');
		});
	});
	$("#link_time_profile_edit").live("click", function(event) {
		timeProfileAction('showProfiles');
	});
	
	
	$("#time_profile").live('click', function(event) {
		var $target = $(event.target);
		if ($target.hasClass('option')) {
			pid = $target.attr('value');
			$.post( 'ajax.php?t=timeProfile&a=changeProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran,pid:pid }, function() {
				changeViewTimeProfile(pid);
			});
		}
	});
};

function changeViewTimeProfile(pid) {
	$("#div_time_profiles").load( 'ajax.php?t=timeProfile&a=showProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran, pid:pid });	
}

function changeTimeProfileType(what) {
	if (what == 'interval') {
		$("#time_date_interval").attr('checked', true);
	} else {
		$("#time_date_type").attr('checked', true);
	}
	
}

function timeProfileAction(action) {
	if (action == 'showProfiles') {
		$('#fade').fadeTo('slow', 1);
		// poiščemo center strani
		$("#div_time_profiles").load( 'ajax.php?t=timeProfile&a=showProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran }, function() {
			var msg = $('#div_time_profiles');
			var height = $(window).height();
			var width = $(document).width();
			var left = width - (msg.width()) - 42;
			var top = height / 2 - (msg.height() / 2);
		}).show(200); 
		//.draggable( { delay : 100, cancel : 'input, .buttonwrapper, .select, #time_profile_left_right' });
	} else if (action == 'cancel'){
		reloadData('interval');
		$("#div_time_profiles").hide(200);
		//$('#fade').fadeOut('slow');
		$("#div_time_profiles").html('');
	} else if (action == 'change_profile'){
		var pid = $("#current_time_profile").val();
		$.post( 'ajax.php?t=timeProfile&a=changeProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran,pid:pid }, function() {
			return reloadData('interval');
		});
	} else if (action == 'show_create'){
		$("#timeProfileCoverDiv").show();
		$("#newProfileDiv").show();
	} else if (action == 'cancel_create'){
		$("#timeProfileCoverDiv").hide();
		$("#newProfileDiv").hide();
	} else if (action == 'do_create'){
		var profileName = $("#newProfileName").val();
		$("#div_time_profiles").load('ajax.php?t=timeProfile&a=createProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, profileName:profileName}, function(newId) {
			$("#div_time_profiles").load( 'ajax.php?t=timeProfile&a=showProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran , pid:newId});
		});
	} else if (action == 'show_rename'){
		$("#timeProfileCoverDiv").show();
		$("#renameProfileDiv").show();
	} else if (action == 'cancel_rename'){
		$("#timeProfileCoverDiv").hide();
		$("#renameProfileDiv").hide();
	} else if (action == 'do_rename'){
		var pid = $("#time_profile .active").attr('value');
		var name = $("#renameProfileName").val();
		$.post('ajax.php?t=timeProfile&a=renameProfile', { anketa : srv_meta_anketa_id, pid : pid, name:name  }, function() {
			$("#div_time_profiles").load( 'ajax.php?t=timeProfile&a=showProfile', {
				anketa : srv_meta_anketa_id,
				meta_akcija : srv_meta_akcija,
				podstran : srv_meta_podstran
			}, function () {
				$("#renameProfileDiv").hide();
				$("#timeProfileCoverDiv").fadeOut();
			});
		});
	} else if (action == 'show_delete'){
		$("#timeProfileCoverDiv").show();
		$("#deleteProfileDiv").show();
	} else if (action == 'cancel_delete'){
		$("#timeProfileCoverDiv").hide();
		$("#deleteProfileDiv").hide();
	} else if (action == 'do_delete'){
		var pid = $("#time_profile .active").attr('value');
		$.post('ajax.php?t=timeProfile&a=deleteProfile', { anketa : srv_meta_anketa_id, pid : pid }, function() {
			$("#div_time_profiles").load('ajax.php?t=timeProfile&a=showProfile', {
						anketa : srv_meta_anketa_id,
						meta_akcija : srv_meta_akcija,
						podstran : srv_meta_podstran
					});
		});
		$("#deleteProfileDiv").hide();
		$("#timeProfileCoverDiv").fadeOut();
	} else if (action == 'run_profile' || action == 'run_session_profile'){
		// poiščemo id izbranega profila
		if (action == 'run_profile') {
			var pid = $("#time_profile .active").attr('value');
		} else {
			var pid = -1;
		}
		var type = $('input[name=type]:checked').val();
		var startDate  = $("#startDate").val();
		var endDate = $("#endDate").val();
		var stat_interval = $("#stat_interval").val();
		if (type == 1 && stat_interval == '') {
			// če je type 1 (intervalni način) in interval ni izbran, damo opozorilo
			genericAlertPopup('srv_time_profile_error_interval');
			return false;
		} else {
			$.post("ajax.php?t=timeProfile&a=saveProfile", {anketa:srv_meta_anketa_id, pid:pid, type:type, startDate:startDate,endDate:endDate,stat_interval:stat_interval}, function(response) {
				return reloadData('interval');
			});
		}
	} else {
		genericAlertPopup('alert_parameter_action',action);
		return false;
	}
}