
function showPhnList(pid) {
	$("#globalSettingsInner").load('ajax.php?t=telephone&m=recipients_lists', {anketa:srv_meta_anketa_id, pid:pid });
}

function phn_settings_save() {
	var form_serialize = $("#phn_settings").serializeArray();
	form_serialize[form_serialize.length] = {name:'anketa', value:srv_meta_anketa_id}
	
	$("#globalSettingsInner").load('ajax.php?t=telephone&m=settings_save', form_serialize, function(){
		show_success_save();	
	});
}

function phn_set_sort_field(field,type) {
	$.post('ajax.php?t=telephone&m=setSortField', {anketa:srv_meta_anketa_id,noNavi:'true', field:field,type:type}, function() {
		$("#globalSettingsInner").load('ajax.php?t=telephone&m=view_recipients', {anketa:srv_meta_anketa_id});	
	});
}

//avtomatsko vsake 10 sekund preverimo, ce se je pojavila kaksna nova stevilka
function preveriStevilkeTimer () {
    $.timer(10000, function (timer) {
        $('#preveri_stevilke').load('ajax.php?t=telephone&m=preveriStevilkeTimer', {anketa:srv_meta_anketa_id, noNavi: false});
        timer.stop();
    });
}

function phn_add_recipients() {

	var recipients_list = $("#inv_recipients_list").val();
	var fields = [];
	var pid = $("#phn_import_list_profiles ol li.active").attr('pid');

	$('ul').children('li.inv_field_enabled').each(function(idx, elm) {
		fields.push(elm.id);
	});   
	
	if (fields.length > 0) {
		if (recipients_list.length > 0) {
			$("#globalSettingsInner").load('ajax.php?t=telephone&m=addRecipients', {anketa:srv_meta_anketa_id, pid:pid, recipients_list:recipients_list, fields:fields});
			// porihtamo še navigacijo - hardcoded
			$elm = $("ul.secondNavigation li.inv_ff_left_on");
			$elm.prev().find('a').removeClass('active');
			$elm.next().find('a').addClass('active');
			$elm.removeClass('inv_ff_left_on').addClass('inv_ff_right_on').next().next().addClass('inv_ff_left_on');
		} else {
			alert(lang['srv_invitation_note1']);
		}
	} else {
		alert(lang['srv_invitation_note2']);
	}

	return true;
}

function phnStartSurvey(usr_id){
	// na trenutrni strani nastavimo marker na A
	/*$.post('ajax.php?t=telephone&m=startSurvey', {anketa:srv_meta_anketa_id, usr_id:usr_id, noNavi:'true'}, function(data) {
		data = jQuery.parseJSON(data);
		if (data.error == "" && data.reloadUrl != "" && data.surveyUrl != "") {
			window.open(data.surveyUrl, '_blank');
			window.location = data.reloadUrl;
			return false;
		} else {
			alert(data.msg);
		}
	});*/
	
	// Ajax mora bit sync, ker drugace nekateri browserji blokirajo window.open popup
    $.ajax({
		type: 'POST',
		url:  'ajax.php?t=telephone&m=startSurvey',
		data: {anketa:srv_meta_anketa_id, usr_id:usr_id, noNavi:'true'},
		success:  function(return_data) {
			return_data = jQuery.parseJSON(return_data);
			if (return_data.error == "" && return_data.reloadUrl != "" && return_data.surveyUrl != "") {
				window.open(return_data.surveyUrl, '_blank');
				window.location = return_data.reloadUrl;
				return false;
			} else {
				alert(return_data.msg);
			}
		},
		async: false
    });
	
}

function phnNextActionChange() {
	var phnNextAction = $("input[name=phnNextAction]:checked").val();
	$.post('ajax.php?t=telephone&m=setNextAction', {anketa:srv_meta_anketa_id,noNavi:'true',phnNextAction:phnNextAction});
}


function phnAddMarker(usr_id,marker) {
	if (marker == 'T') {
		window.location = ('ajax.php?anketa='+srv_meta_anketa_id+'&t=telephone&m=addmark&usr_id='+usr_id+'&status='+marker+'&datetime='+$("#T_datetime").val());
	} else if (marker== 'P') {
		window.location = ('ajax.php?anketa='+srv_meta_anketa_id+'&t=telephone&m=addmark&usr_id='+usr_id+'&status='+marker+'&datetime='+$("#P_datetime").val());
	} else {
		window.location = ('ajax.php?anketa='+srv_meta_anketa_id+'&t=telephone&m=addmark&usr_id='+usr_id+'&status='+marker);
	}
}

function phnSetUserComment(what,usr_id) {
	var comment = $(what).html();
	$.post('ajax.php?t=telephone&m=setUserComment', {anketa:srv_meta_anketa_id,noNavi:'true',usr_id:usr_id,comment:comment});

}

function phnDeleteProfile() {
	var pid = $("#phn_import_list_profiles ol li.active").attr("pid");
	if (confirm(lang['srv_inv_recipients_delete_profile_confirm'])) {
		$.post('ajax.php?t=telephone&m=deleteProfile', {anketa:srv_meta_anketa_id, pid:pid, noNavi:'true'}, function(data) {
		 	// osvežimo polja
			//inv_change_import_type();
			var new_pid = $("#phn_import_list_profiles ol li").first().attr('pid');
			// če je slučajno isti kot smo ga zbrisli izberemo nasledenjega
			if (new_pid == pid) {
				var new_pid = $("#phn_import_list_profiles ol li").first().next().attr('pid');
			}
			$("#globalSettingsInner").load('ajax.php?t=telephone&m=recipients_lists', {anketa:srv_meta_anketa_id, pid:new_pid });
		});
	}
}
function phnEditProfile(inv_rid) {
	var pid = $("#phn_import_list_profiles ol li.active").attr("pid");
	
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=telephone&m=editProfile', {anketa:srv_meta_anketa_id, pid:pid, noNavi:'true'});
}
function phnUpdateProfile() {
	var pid = $("#rec_profile_pid").val();
	var profile_name = $("#rec_profile_name").val();
	var profile_comment = $("#rec_profile_comment").val();
	var profile_respondents = $("#rec_profile_respondents").val();
	$("#inv_error_note").addClass('hidden');
	$.post('ajax.php?t=telephone&m=updateProfile', {anketa:srv_meta_anketa_id, pid:pid, profile_name:profile_name, profile_comment:profile_comment, profile_respondents:profile_respondents, noNavi:'true'}, function(data) {
		data = jQuery.parseJSON(data);
		if (data.error == "0") {
			$('#fade').fadeOut('slow');
			$('#fullscreen').fadeOut('slow').html('');
			// osvežimo polja
			$("#globalSettingsInner").load('ajax.php?t=telephone&m=recipients_lists', {anketa:srv_meta_anketa_id, pid:pid });
		} else {
			$("#inv_error_note").html(data.msg);
			$("#inv_error_note").show();
			$("#inv_error_note").removeClass('hidden');
		}
	});	
}
function phnSaveProfile() {
	var pid = $("#phn_import_list_profiles ol li.active").attr("pid");
	var recipients_list = $("#inv_recipients_list").val();
	var fields = [];
	$('ul').children('li.inv_field_enabled').each(function(idx, elm) {
		fields.push(elm.id);
	});   
	 	
	$("#globalSettingsInner").load('ajax.php?t=telephone&m=saveProfile', {anketa:srv_meta_anketa_id, recipients_list:recipients_list, fields:fields, pid:pid});
}
function phnGetNewProfileName() {
	var pid = $("#phn_import_list_profiles ol li.active").attr("pid");
	var recipients_list = $("#inv_recipients_list").val();
	var fields = [];
	$('ul').children('li.inv_field_enabled').each(function(idx, elm) {
		fields.push(elm.id);
	});   

	// ponudimo box za ime ipd
	if ( pid != 'undefined' ) {
		if (fields.length > 0) {
			if (recipients_list.length > 0) {
				$('#fade').fadeTo('slow', 1);
				$('#fullscreen').html('').fadeIn('slow');
				$("#fullscreen").load('ajax.php?t=telephone&m=getProfileName', {anketa:srv_meta_anketa_id, recipients_list:recipients_list, fields:fields, noNavi:'true', pid:pid});
			} else {
				alert(lang['srv_invitation_note1']);
			}
		} else {
			alert(lang['srv_invitation_note2']);
		}
	} else {
		alert('Invalid PID!');
	}
}

function phnSaveNewProfile() {
	var pid = $("#phn_import_list_profiles ol li.active").attr("pid");
	var recipients_list = $("#inv_recipients_list").val();
	var fields = [];
	$('ul').children('li.inv_field_enabled').each(function(idx, elm) {
		fields.push(elm.id);
	});   

	var profile_id = $("#profile_id").val();
	var profile_name = $("#inv_recipients_profile_name").find("#rec_profile_name").val();
	var profile_comment = $("#inv_recipients_profile_name").find("#rec_profile_comment").val();
	
	$("#globalSettingsInner").load('ajax.php?t=telephone&m=saveNewProfile', {anketa:srv_meta_anketa_id, recipients_list:recipients_list, fields:fields, profile_id:profile_id, profile_name:profile_name, profile_comment:profile_comment});
	$('#fade').fadeOut('slow');
	$('#fullscreen').fadeOut('slow').html('');

}

function phn_new_recipients_list_change(what) {
	if ($(what).val() == 0) {
		$("#new_recipients_list_span").show();
		$("#rec_profile_comment").val('');
		
	} else {
		$("#new_recipients_list_span").hide();
		$("#rec_profile_comment").val($(what).find('option:selected').attr('comment'));
	}
}

function phnGoToUser(usr_id) {
	$("#globalSettingsInner").load('ajax.php?t=telephone&m=goToUser', {anketa:srv_meta_anketa_id, noNavi:'false', showUser:usr_id});	
}

function phnShowPopupAddMarker(usr_id,marker) {
	$('#fade').fadeTo('slow', 1);
	$('#telephone_popup').show().load('ajax.php?t=telephone&m=showPopupAddMarker', {anketa: srv_meta_anketa_id, usr_id:usr_id,marker:marker, noNavi:'true'}, function () {
	});
}

function tel_filter_recipients() {

	var tel_filter_value = $("#tel_rec_filter_value").val();
	$.post('ajax.php?t=telephone&m=set_recipient_filter', {anketa:srv_meta_anketa_id, tel_filter_value:tel_filter_value}, function() {
		$("#globalSettingsInner").load('ajax.php?t=telephone&m=view_recipients', {anketa:srv_meta_anketa_id});	
	});
}

// Pobrisemo zadnji status da se lahko respondent ponovno kliče
function phnUndoStatus(usr_id) {
	
	$.post('ajax.php?t=telephone&m=undoLastStatus', {anketa:srv_meta_anketa_id, usr_id:usr_id}, function() {
		phnGoToUser(usr_id);
	});
}
