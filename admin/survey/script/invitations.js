function deleteRecipient_confirm(inv_rid) {
	// vprašamo ali resnično želi izbrisati respondenta
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=invitations&a=delete_recipient_confirm', {anketa:srv_meta_anketa_id, inv_rid:inv_rid,noNavi:'true'});	
}
function inv_delete_recipient() {
	var inv_rid = $("#inv_delete_rec_confirm input#inv_rid").val();
			
	$.post('ajax.php?t=invitations&a=delete_recipient_single', {anketa:srv_meta_anketa_id, inv_rid:inv_rid,noNavi:'true'}, function(data) {
		data = jQuery.parseJSON(data);
		if (data.success == 1) {
			// uporabnik je bil zbrisan, skrijmo njevovo vrstico
			$('#tbl_recipients_list tr td input[value="'+inv_rid+'"]').closest('tr').hide('slow');
			$('#fade').fadeOut('slow');
			$('#fullscreen').fadeOut('slow').html('');
		}
	});
}
function editRecipient(inv_rid) {
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=invitations&a=edit_recipient', {anketa:srv_meta_anketa_id, inv_rid:inv_rid,noNavi:'true'});	
}

function inv_arch_recipients_close() {
	$('#fade').fadeOut('slow');
	$('#fullscreen').fadeOut('slow').html('');
}
function inv_arch_save_comment() {
	var aid = $("#inv_arch_id").val();
	var comment = $("#inv_arch_comment").val();
    
    $.post('ajax.php?t=invitations&a=arch_save_comment', {anketa:srv_meta_anketa_id, aid:aid, comment:comment, noNavi:'true'}, function(data) {
		// to ni vredu.window.location.reload();
		$('#fade').fadeOut('slow');
		$('#fullscreen').fadeOut('slow').html('');
	});
}
function inv_arch_recipients_send() {
	
	var send_type = $('input[name=mailto]:checked').val();
	var prefix = "";
	var checkboxes = "";

	$('input[name="mailto_status[]"]:checked').each(function(el) {
		checkboxes = checkboxes+prefix+$(this).val(); 
		prefix = ",";
	});

	$('#fullscreen').load('ajax.php?t=invitations&a=send_mail', 
			{anketa:srv_meta_anketa_id, noNavi:'true',send_type:send_type, checkboxes:checkboxes}
	);
}
function inv_change_import_type() {
	var import_type = $('input[name=inv_import_type]:checked').val();
	
	$("#inv_import").load('ajax.php?t=invitations&a=change_import_type', {anketa:srv_meta_anketa_id, import_type:import_type,noNavi:'true'}, function(){
        refreshFieldsList();
    });
}
function toggleInvCheckbox(what) {
	var id = $(what).attr("id");

	if ( $(what).is(":checked") ) {
		$(what).parent().addClass('inv_field_enabled');
    } 
    else {
		$(what).parent().removeClass('inv_field_enabled');
	}

	refreshFieldsList();
}

function refreshFieldsList() {
	var fields = '';
	var fields_id = '';
	var prefix = '';
	var pass_field = false;	
	
	$('ul').children('li.inv_field_enabled').each(function(idx, elm) {
		fields = fields + prefix + $(elm).find('label').html();
		fields_id = fields_id + prefix + $(elm).attr('id');
		prefix = ',';
		
		if($(elm).find('label').html() == 'PASSWORD')
			pass_field = true;
	});       
	
	$("#inv_field_list.inv_type_0, #inv_field_list.inv_type_1").html(fields);
	
	if ($("#inv_recipients_upoad_fields").length > 0) {
		$("#inv_recipients_upoad_fields").val(fields_id);
	}
	
	// Prikazemo opozorilo za dolzino passworda (20 znakov)
	if(pass_field)
		$("#inv_field_list_warning").show();
	else
		$("#inv_field_list_warning").hide();
}

function inv_save_rec_profile() {
	
	var profile_name = $("#rec_profile_name").val();
	var profile_id = $("#inv_recipients_profile_name select").find('option:selected').val();
	var profile_comment = $("#rec_profile_comment").val();
	var recipients_list = $("#inv_prof_recipients_list").val();
	var field_list = $("#inv_prof_field_list").val();
	var doAdd = ($("#inv_doAdd").val() == 1 || $("#inv_doAdd").val() == '1') ? true : false;

	$.post('ajax.php?t=invitations&a=save_rec_profile', {anketa:srv_meta_anketa_id, recipients_list:recipients_list, field_list:field_list, profile_name:profile_name, profile_comment:profile_comment, noNavi:'true', profile_id:profile_id}, function(data) {
		data = jQuery.parseJSON(data);

		if (doAdd == true) {
			inv_add_recipients(data.pid);
		} else {
			// prikažemo profil
			$(".anketa_edit_main").load('ajax.php?t=invitations&a=use_recipients_list', {anketa:srv_meta_anketa_id, pid:data.pid });
		}
		
	});
	$('#fade').fadeOut('slow');
	$('#fullscreen').fadeOut('slow').html('');
}

function inv_update_rec_profile() {
	
	var pid = $('#inv_recipients_profile_name').find('#rec_profile_pid').val();
	var profile_name = $('#inv_recipients_profile_name').find('#rec_profile_name').val();
	
	$("#inv_error_note").addClass('hidden');
	
	$.post('ajax.php?t=invitations&a=update_rec_profile', {anketa:srv_meta_anketa_id, pid:pid, profile_name:profile_name, noNavi:'true'}, function(data) {
		data = jQuery.parseJSON(data);
		if (data.error == "0") {
			$('#fade').fadeOut('slow');
			$('#fullscreen').fadeOut('slow').html('');
			// osvežimo polja
			$(".anketa_edit_main").load('ajax.php?t=invitations&a=use_recipients_list', {anketa:srv_meta_anketa_id, pid:pid});
		} else {
			$("#inv_error_note").html(data.msg);
			$("#inv_error_note").show();
			$("#inv_error_note").removeClass('hidden');
		}
	});
}
function inv_add_recipients(profile_id) {
	
	if (typeof profile_id === "undefined") {
		var pid = $("#inv_import_list_profiles ol li.active").attr('pid');
	} else {
		var pid = profile_id;
	}

	// vedno shranjujemo če ne druga v zacasin seznam.
	var save_profile = true;
	
	var recipients_list = $("#inv_recipients_list").val();
	var fields = [];
	$('ul').children('li.inv_field_enabled').each(function(idx, elm) {
		fields.push(elm.id);
	});                               
	if (fields.length > 0) {
		if (recipients_list.length > 0) {
			$(".anketa_edit_main").load('ajax.php?t=invitations&a=add_recipients', {anketa:srv_meta_anketa_id, recipients_list:recipients_list, fields:fields, pid:pid, save_profile:save_profile});
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

}
function inv_save_recipient() {
	
	var inv_rid = $("#inv_rid").val();
	var rec_email = $("#rec_email").val();
	var rec_password = $("#rec_password").val();
	var rec_firstname = $("#rec_firstname").val();
	var rec_lastname = $("#rec_lastname").val();
	var rec_salutation = $("#rec_salutation").val();
	var rec_phone = $("#rec_phone").val();
	var rec_custom = $("#rec_custom").val();
	var rec_relation = $("#rec_relation").val();

	// odstranimo morebitne predhodne napake
	$("#rec_email").removeClass('inv_input_error');
	$("#rec_password").removeClass('inv_input_error');
	
	$.post('ajax.php?t=invitations&a=save_recipient', {anketa:srv_meta_anketa_id, inv_rid:inv_rid, rec_email:rec_email, rec_password:rec_password, 
		rec_firstname:rec_firstname, rec_lastname:rec_lastname, rec_salutation:rec_salutation, rec_phone:rec_phone, rec_custom:rec_custom, rec_relation:rec_relation, noNavi:'true'}, function(data) {
        
            data = jQuery.parseJSON(data);
        
        if (data.error == "0") {
			// smo shranli lahko zapremo okno in refrešamo podatke
			$('#fade').fadeOut('slow');
			$('#fullscreen').fadeOut('slow').html('');
			
			// Refreshamo celotno stran
			location.reload();			
        } 
        else {
			// prikažemo obvestilo o napaki
			$("#inv_error_note").html(data.msg);
			$("#inv_error_note").show();
			$("#inv_error_note").removeClass('hidden');
			if (data.error_email == '1') {
				$("#rec_email").addClass('inv_input_error');
			}
			if (data.error_password == '1') {
				$("#rec_password").addClass('inv_input_error');
			}
		}
	});
	
}
function inv_filter_recipients() {

	var inv_filter_on = $("#inv_rec_filter_on").is(":checked") ? 'true' : 'false';
	var inv_filter_value = $("#inv_rec_filter_value").val();
	var inv_filter_send = $("#inv_rec_filter_send option:selected").val();
	var inv_filter_respondet = $("#inv_rec_filter_respondet option:selected").val();
	var inv_filter_unsubscribed = $("#inv_rec_filter_unsubscribed option:selected").val();
	var inv_filter_list = $("#inv_rec_filter_list option:selected").val();
	var inv_filter_duplicates = $("#inv_rec_filter_duplicates").is(":checked") ? 'true' : 'false';

	$.post('ajax.php?t=invitations&a=set_recipient_filter', {anketa:srv_meta_anketa_id,inv_filter_on:inv_filter_on, inv_filter_value:inv_filter_value, inv_filter_send:inv_filter_send,inv_filter_respondet:inv_filter_respondet,inv_filter_unsubscribed:inv_filter_unsubscribed, inv_filter_list:inv_filter_list, inv_filter_duplicates:inv_filter_duplicates, noNavi:'true'}, function() {
		
		// Ce smo v telefonskem modulu
		if($('#advanced_module_phone').val()){
			$("#globalSettingsInner").load('ajax.php?t=invitations&a=view_recipients', {anketa:srv_meta_anketa_id});
		}
		else{
			$(".anketa_edit_main").load('ajax.php?t=invitations&a=view_recipients', {anketa:srv_meta_anketa_id});	
		}
	});
}

function inv_add_rec_to_db() {
	$('#fade').fadeTo('slow', 1);
	$(".anketa_edit_main").load('ajax.php?t=invitations&a=add_users_to_database', {anketa:srv_meta_anketa_id}, function() {
		$('#fade').fadeOut('slow');
	});
}

function recipientsProfileOnlyThisSurvey() {
	//var checked = $("#inv_rec_only_this_survey").is(":checked");
	var checked = $('input[name=inv_show_list_type]:checked').val() == 1 ? true : false;
	$.post('ajax.php?t=invitations&a=only_this_survey', {anketa:srv_meta_anketa_id, checked:checked, noNavi:'true'}, function(data) {
		var pid = $("#inv_import_list_profiles ol li.active").attr('pid');
		if (pid == 'undefined') {
			pid = '-1';
		}
		$(".anketa_edit_main").load('ajax.php?t=invitations&a=use_recipients_list', {anketa:srv_meta_anketa_id, pid:pid });
	});
}

function mailToRadioChange() {
	
	var send_type = $('input[name=mailto]:checked').val();
	var prefix = "";
	var checkboxes = "";

	if ( $('#mailto4').is(":checked") ) {
		//$('#inv_send_advanced_div').slideDown();
		//disablamo ali enablamo  spodnje checkboxe
		$('#inv_send_advanced_div span').removeClass('gray');
		$('#inv_send_advanced_div span input[type=checkbox]').attr('disabled',false);

	} else {
		//$('#inv_send_advanced_div').slideUp();
		//disablamo ali enablamo  spodnje checkboxe
		$('#inv_send_advanced_div span').addClass('gray');
		$('#inv_send_advanced_div span input[type=checkbox]').attr('disabled',true);
	}
	
	$('input[name="mailto_status[]"]:checked').each(function(el) {
		checkboxes = checkboxes+prefix+$(this).val(); 
		prefix = ",";
	});

	var source_type = $('input[name=mailsource]:checked').val();
	var source_lists = "";
	var prefix = "";
	$('input[name="mailsource_lists[]"]:checked').each(function(el) {
		source_lists = source_lists+prefix+$(this).val(); 
		prefix = ",";
	});

	
	var noMailing = $('input[name=noMailing]').val();
	
	$("#inv_select_mail_to_respondents").load('ajax.php?t=invitations&a=view_send_recipients', {anketa:srv_meta_anketa_id, noNavi:'true',send_type:send_type, checkboxes:checkboxes, source_type:source_type, source_lists:source_lists, noMailing:noMailing}, function(){

		var cb = $('#tbl_recipients_send_list tr td').length;
		if (cb > 0 ) {
			$("#inv_send_mail_btn").show();
		} else {
			$("#inv_send_mail_btn").hide();
		}
		if (cb > 4999 ) {
			$("#inv_send_mail_limit").show();
		} else {
			$("#inv_send_mail_limit").hide();
		}

	});
};

function mailToSourceChange() {
	var send_type = $('input[name=mailto]:checked').val();
	var prefix = "";
	var checkboxes = "";
	$('input[name="mailto_status[]"]:checked').each(function(el) {
		checkboxes = checkboxes+prefix+$(this).val(); 
		prefix = ",";
	});
	var source_type = $('input[name=mailsource]:checked').val();
	var source_lists = "";
	var prefix = "";
	$('input[name="mailsource_lists[]"]:checked').each(function(el) {
		source_lists = source_lists+prefix+$(this).val(); 
		prefix = ",";
	});

	$("#inv_select_mail_to_source_lists").load('ajax.php?t=invitations&a=mailToSourceChange', {anketa:srv_meta_anketa_id, noNavi:'true',source_type:source_type, source_lists:source_lists}, function(){
	});

	$("#inv_select_mail_to_respondents").load('ajax.php?t=invitations&a=view_send_recipients', {anketa:srv_meta_anketa_id, noNavi:'true',send_type:send_type, checkboxes:checkboxes, source_type:source_type, source_lists:source_lists}, function(){
		var cb = $('#tbl_recipients_send_list tr td').length;
		if (cb > 0 ) {
			$("#inv_send_mail_btn").show();
		} else {
			$("#inv_send_mail_btn").hide();
		}
		if (cb > 4999 ) {
			$("#inv_send_mail_limit").show();
		} else {
			$("#inv_send_mail_limit").hide();
		}

	});
}

function mailToSourceCheckboxChange() {

	var send_type = $('input[name=mailto]:checked').val();
	var prefix = "";
    var checkboxes = "";
    
	$('input[name="mailto_status[]"]:checked').each(function(el) {
		checkboxes = checkboxes+prefix+$(this).val(); 
		prefix = ",";
    });
    
	var source_type = $('input[name=mailsource]:checked').val();
	var source_lists = "";
    var prefix = "";
    
	$('input[name="mailsource_lists[]"]:checked').each(function(el) {
		source_lists = source_lists+prefix+$(this).val(); 
		prefix = ",";
	});
	
	$("#inv_select_mail_to_respondents").load('ajax.php?t=invitations&a=view_send_recipients', {anketa:srv_meta_anketa_id, noNavi:'true',send_type:send_type, checkboxes:checkboxes, source_type:source_type, source_lists:source_lists}, function(){
		var cb = $('#tbl_recipients_send_list tr td').length;
        
        if (cb > 0 ) {
			$("#inv_send_mail_btn").show();
        } 
        else {
			$("#inv_send_mail_btn").hide();
		}
        
        if (cb > 4999 ) {
			$("#inv_send_mail_limit").show();
        } 
        else {
			$("#inv_send_mail_limit").hide();
		}
	});
}


function mailTocheCheckboxChange() {

	// izberemo rado za status
	$('#mailto4').attr('checked', true);
    
    var send_type = $('input[name=mailto]:checked').val();
	var prefix = "";
	var checkboxes = "";
    
    $('input[name="mailto_status[]"]:checked').each(function(el) {
		checkboxes = checkboxes+prefix+$(this).val(); 
		prefix = ",";
	});
    
    var source_type = $('input[name=mailsource]:checked').val();
	var source_lists = "";
	var prefix = "";
    
    $('input[name="mailsource_lists[]"]:checked').each(function(el) {
		source_lists = source_lists+prefix+$(this).val(); 
		prefix = ",";
	});
	
	$("#inv_select_mail_to_respondents").load('ajax.php?t=invitations&a=view_send_recipients', {anketa:srv_meta_anketa_id, noNavi:'true',send_type:send_type, checkboxes:checkboxes, source_type:source_type, source_lists:source_lists}, function(){
		var cb = $('#tbl_recipients_send_list tr td').length;
		if (cb > 0 ) {
			$("#inv_send_mail_btn").show();
		} else {
			$("#inv_send_mail_btn").hide();
		}
		if (cb > 4999 ) {
			$("#inv_send_mail_limit").show();
		} else {
			$("#inv_send_mail_limit").hide();
		}
	});
};
function invitations_init() {

	$(".inv_checkbox").live('change', function(event) {
		toggleInvCheckbox(this);
    });
    
	$("#inv_upload_recipients").live('click', function(event) {
		var inv_iid = $("#inv_iid").val();
		$("#inv_recipients_upload_form").submit();
	});

	$(".inv_step").mouseover(function() {
		$(this).addClass("hover");
	}).mouseout(function(){
		$(this).removeClass("hover");
	});
	
	$('#tbl_recipients_send_list tr td input').live('change', function(event) {
		// preštejemo obkljukane
		var cb = $('#tbl_recipients_send_list tr td input').filter(':checked').length;
		$("#inv_num_recipients").html(cb);
		if (cb > 0 ) {
			$("#inv_send_mail_btn").show();
		} else {
			$("#inv_send_mail_btn").hide();
		}
		
		if (cb > 4999 ) {
			$("#inv_send_mail_limit").show();
		} else {
			$("#inv_send_mail_limit").hide();
		}
		
	});
	
	// dodajanje respondentov
	$("#add_recipients").live('click', function(event) {
		inv_add_recipients();
	});
	
	// izbira obstoječega profila prejemnikov
	$("#inv_import_list_profiles ol li").live('click', function(event) {
		
		var target = $(event.target);
		var pid = $(target).attr('pid');
		if (pid != 'undefined') {
			$(".anketa_edit_main").load('ajax.php?t=invitations&a=use_recipients_list', {anketa:srv_meta_anketa_id, pid:pid });
		}
	});
	
	$("#tbl_archive_list .as_link").live('click', function(event) {
		var target = $(event.target);
		var arch_to_view = $(target).attr('id');
		
		if($(target).hasClass('as_view')) {
			$('#fade').fadeTo('slow', 1);
			$('#fullscreen').html('').fadeIn('slow');
            archType = $(target).data('archtype');
			$("#fullscreen").load('ajax.php?t=invitations&a=view_archive_recipients', {anketa:srv_meta_anketa_id, arch_to_view:arch_to_view, archType:archType, noNavi:'true'});
		} else {
			$('#fade').fadeTo('slow', 1);
			$('#fullscreen').html('').fadeIn('slow');
			$("#fullscreen").load('ajax.php?t=invitations&a=edit_archive_comment', {anketa:srv_meta_anketa_id, arch_to_view:arch_to_view, noNavi:'true'});
		}
	});

}
function invChangeMessage(mid) {
	if (mid != 'undefined' && mid > 0) {
		if(CKEDITOR.instances['inv_message_body']) {
			delete CKEDITOR.instances['inv_message_body'];
		}

		$(".anketa_edit_main").load('ajax.php?t=invitations&a=make_default',
			{anketa:srv_meta_anketa_id, mid:mid },
			function(){
				if (!CKEDITOR.instances) {
					CKEDITOR.replace['inv_message_body'];
				}
		});
	}
}

function invMessageDelete() {
	var mid = $("#invitation_messages ol li.active").attr("mid");
	if (confirm(lang['srv_inv_recipients_delete_profile_confirm'])) {
		if(CKEDITOR.instances['inv_message_body']) {
			delete CKEDITOR.instances['inv_message_body'];
		}
		$(".anketa_edit_main").load('ajax.php?t=invitations&a=delete_msg_profile', {anketa:srv_meta_anketa_id, mid:mid });
	}
}	

function invShowMessageRename() {
	// prikažemo popup za preimenovanje
	var mid = $("#invitation_messages ol li.active").attr("mid");
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=invitations&a=show_message_rename', {anketa:srv_meta_anketa_id, noNavi:'true', mid:mid});
}	
function invMessageRename() {
	// prikažemo popup za preimenovanje
	var mid = $("#invitation_messages ol li.active").attr("mid");
	var name =  $("#inv_message_profile_name").val();
	var comment =  $("#inv_message_comment").val();
		
	$.post(
			'ajax.php?t=invitations&a=message_rename',
			{
				anketa : srv_meta_anketa_id,
				mid : mid,
				name : name,
				comment : comment,
				noNavi : 'true'
			},
			function(data) {
				data = jQuery.parseJSON(data);
				if (data.error == "0") {
					$('#fade').fadeOut('slow');
					$('#fullscreen').fadeOut('slow').html('')

				} else {
					$('#fade').fadeOut('slow');
					$('#fullscreen').fadeOut('slow').html('')

				}
			}
		);
}	
function inv_message_save_advanced(mid) {
	// najprej dodatno poeditiramo sporočilo
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=invitations&a=edit_message_details', {anketa:srv_meta_anketa_id, noNavi:'true', mid:mid});
}
function inv_message_save_forward(mid) {
	//v kolikor je CKEditor vklopljen potem, ga odstranimo pred skranjevanjem
	if(CKEDITOR.instances['inv_message_body']){
		CKEDITOR.instances['inv_message_body'].destroy();
	}

	//gremo naprej brez dodatnega editiranja
	//preverimo če so spremembe in če so shranimo v novo sporočilo
	// shranimo v mid
	var replyto = $("#inv_message_replyto").val();
	var subject = $("#inv_message_subject").val();
	var body = $("#inv_message_body").val();
	var url = $("#inv_message_url").val();

	// resetiramo morebitne prejšne napake
	$("#inv_message_replyto").css({
		'border' : 'none'
	});
	$("#inv_message_subject").css({
		'border' : 'none'
	});
	$("#inv_message_body").css({
		'border' : 'none'
	});
	
	$("#inv_error_note").addClass('hidden');
	$.post(
		'ajax.php?t=invitations&a=message_save_forward',
		{
			anketa : srv_meta_anketa_id,
			mid : mid,
			replyto : replyto,
			subject : subject,
			body : body,
			url: url,
			noNavi : 'true',
		},
		function(data) {
			data = jQuery.parseJSON(data);
			if (data.error == "0") {
				// redirektamo na pošiljanje
				var href = 'index.php?anketa='+srv_meta_anketa_id+'&a=invitations&m=send_message';
				window.location = href;
			} else {
				if (!CKEDITOR.instances) {
					CKEDITOR.replace['inv_message_body'];
				}
				// prikažemo obvestilo o napaki
				$("#inv_error_note").html(data.msg);
				$("#inv_error_note").removeClass('hidden');
				if (data.inv_message_replyto == '1') {
					$("#inv_¸ge_replyto").css({
						'border' : '1px solid red'
					});
				}
				if (data.inv_message_subject == '1') {
					$("#inv_message_subject").css({
						'border' : '1px solid red'
					});
				}
				if (data.inv_message_body == '1') {
					$("#inv_message_body").css({
						'border' : '1px solid red'
					});
				}
			}
		});
}

function inv_message_save_forward_noEmail(mid) {
	//v kolikor je CKEditor vklopljen potem, ga odstranimo pred skranjevanjem
	if(CKEDITOR.instances['inv_message_body']){
		CKEDITOR.instances['inv_message_body'].destroy();
	}

	//gremo naprej brez dodatnega editiranja
	//preverimo če so spremembe in če so shranimo v novo sporočilo
	// shranimo v mid
	var subject = $("#inv_message_subject").val();
	var body = $("#inv_message_body").val();
	var url = $("#inv_message_url").val();

	// resetiramo morebitne prejšne napake
	$("#inv_message_subject").css({
		'border' : 'none'
	});
	$("#inv_message_body").css({
		'border' : 'none'
	});
	
	$("#inv_error_note").addClass('hidden');
	$.post(
		'ajax.php?t=invitations&a=message_save_forward_noEmail',
		{
			anketa : srv_meta_anketa_id,
			mid : mid,
			subject : subject,
			body : body,
			url: url,
			noNavi : 'true',
		},
		function(data) {
			data = jQuery.parseJSON(data);
			if (data.error == "0") {
				// redirektamo na pošiljanje
				var href = 'index.php?anketa='+srv_meta_anketa_id+'&a=invitations&m=send_message';
				window.location = href;
			} else {
				if (!CKEDITOR.instances) {
					CKEDITOR.replace['inv_message_body'];
				}
				// prikažemo obvestilo o napaki
				$("#inv_error_note").html(data.msg);
				$("#inv_error_note").removeClass('hidden');
				if (data.inv_message_subject == '1') {
					$("#inv_message_subject").css({
						'border' : '1px solid red'
					});
				}
				if (data.inv_message_body == '1') {
					$("#inv_message_body").css({
						'border' : '1px solid red'
					});
				}
			}
		});
}

function inv_message_save_simple(mid) {
	//v kolikor sporočilosamo shranimo potem editorpustimo odprt
	if(CKEDITOR.instances['inv_message_body']){
		CKEDITOR.instances['inv_message_body'].destroy();
	}

	// shranimo v mid
	var replyto = $("#inv_message_replyto").val();
	var subject = $("#inv_message_subject").val();
	var body = $("#inv_message_body").val();
	var url = $("#inv_message_url").val();
	
	// resetiramo morebitne prejšne napake
	$("#inv_message_replyto").css({
		'border' : 'none'
	});
	$("#inv_message_subject").css({
		'border' : 'none'
	});
	$("#inv_message_body").css({
		'border' : 'none'
	});

	$("#inv_error_note").addClass('hidden');
	$.post(
			'ajax.php?t=invitations&a=save_message_simple',
			{
				anketa : srv_meta_anketa_id,
				mid : mid,
				replyto : replyto,
				subject : subject,
				body : body,
				url : url,
				noNavi : 'true'
			},		
			function(data) {
				create_inv_editor('', true)
				data = jQuery.parseJSON(data);
				if (data.error == 0) {
					return true;
				}else {
					// skrijemo okno in 
					// prikažemo obvestilo o napaki
					$("#inv_error_note").html(data.msg);
					$("#inv_error_note").removeClass('hidden');
					if (data.inv_message_replyto == '1') {
						$("#inv_messge_replyto").css({
							'border' : '1px solid red'
						});
					}
					if (data.inv_message_subject == '1') {
						$("#inv_message_subject").css({
							'border' : '1px solid red'
						});
					}
					if (data.inv_message_body == '1') {
						$("#inv_message_body").css({
							'border' : '1px solid red'
						});
					}
					return false;
				}
			});
}

function inv_message_save_simple_noEmail(mid) {
	//v kolikor sporočilosamo shranimo potem editorpustimo odprt
	if(CKEDITOR.instances['inv_message_body']){
		CKEDITOR.instances['inv_message_body'].destroy();
	}

	// shranimo v mid
	var subject = $("#inv_message_subject").val();
	var body = $("#inv_message_body").val();
	var url = $("#inv_message_url").val();
	
	// resetiramo morebitne prejšne napake
	$("#inv_message_subject").css({
		'border' : 'none'
	});
	$("#inv_message_body").css({
		'border' : 'none'
	});

	$("#inv_error_note").addClass('hidden');
	$.post(
			'ajax.php?t=invitations&a=save_message_simple_noEmail',
			{
				anketa : srv_meta_anketa_id,
				mid : mid,
				subject : subject,
				body : body,
				url : url,
				noNavi : 'true'
			},		
			function(data) {
				create_inv_editor('', true)
				data = jQuery.parseJSON(data);
				if (data.error == 0) {
					return true;
				}else {
					// skrijemo okno in 
					// prikažemo obvestilo o napaki
					$("#inv_error_note").html(data.msg);
					$("#inv_error_note").removeClass('hidden');
					if (data.inv_message_subject == '1') {
						$("#inv_message_subject").css({
							'border' : '1px solid red'
						});
					}
					if (data.inv_message_body == '1') {
						$("#inv_message_body").css({
							'border' : '1px solid red'
						});
					}
					return false;
				}
			});
}

function inv_message_save_details() {
	// najprej shranimo detajle
	var mid = $("#inv_recipients_profile_name select").val();
	var profile_comment = $("#inv_message_comment").val();
	var naslov = $("#rec_profile_name").val();

	//v kolikor je CKEditor vklopljen potem, ga odstranimo pred skranjevanjem
	if(CKEDITOR.instances['inv_message_body']){
		CKEDITOR.instances['inv_message_body'].destroy();
	}

	var subject = $("#inv_message_subject").val();
	var body = $("#inv_message_body").val();
	
	$.post(
			'ajax.php?t=invitations&a=message_save_details',
			{
				anketa : srv_meta_anketa_id,
				mid : mid,
				profile_comment : profile_comment,
				naslov : naslov,
				body : body,
				subject : subject,
				noNavi : 'true'
			},
			function(data) {
				data = jQuery.parseJSON(data);
				if (data.error == "0" && parseInt(data.mid) > 0) {
					inv_message_save_simple(parseInt(data.mid));
					/*var href = 'index.php?anketa='+srv_meta_anketa_id+'&a=invitations&m=send_message';
					window.location = href;*/
					window.location.reload()
				} else {
					// so napake
					alert (' '+data.msg);
				}
			}
		);
}

function inv_new_message_list_change(what) {
	if ($(what).val() == 0) {
		$("#new_message_list_span").show();
		$("#inv_message_comment").val('');
		
	} else {
		$("#new_message_list_span").hide();
		$("#inv_message_comment").val($(what).find('option:selected').attr('comment'));
	}
}

/*
function edit_message_save(mid) {
	// shranimo v mid
	var replyto = $("#inv_message_replyto").val();
	var subject = $("#inv_message_subject").val();
	var body = $("#inv_message_body").val();
	var profile_comment = $("#inv_message_comment").val();
	var quickSave = true;
	if (!mid || mid == 'undefined') {
		mid = $("#inv_recipients_profile_name select").val();
		quickSave = false;
	}
	var naslov = $("#rec_profile_name").val();
	var old_mid = $("#invitation_messages ol li.active").attr('mid');
	// resetiramo morebitne prejšne napake
	$("#inv_message_replyto").css({
		'border' : 'none'
	});
	$("#inv_message_subject").css({
		'border' : 'none'
	});
	$("#inv_message_body").css({
		'border' : 'none'
	});

	$("#inv_error_note").addClass('hidden');

	$.post(
			'ajax.php?t=invitations&a=save_message',
			{
				anketa : srv_meta_anketa_id,
				mid : mid,
				old_mid:old_mid,
				quickSave:quickSave,
				replyto : replyto,
				subject : subject,
				body : body,
				noNavi : 'true',
				profile_comment : profile_comment,
				naslov:naslov
			},
			function(data) {
				data = jQuery.parseJSON(data);
				if (data.error == "0") {
					// redirektamo na pošiljanje
					//var href = 'index.php?anketa='+srv_meta_anketa_id+'&a=invitations&m=send_message';
					//window.location = href;
					window.location.reload()
				} else {
					// skrijemo okno in 
					$('#fade').fadeOut('slow');
					$('#fullscreen').fadeOut('slow').html('')
					// prikažemo obvestilo o napaki
					$("#inv_error_note").html(data.msg);
					$("#inv_error_note").removeClass('hidden');
					if (data.inv_message_replyto == '1') {
						$("#inv_message_replyto").css({
							'border' : '1px solid red'
						});
					}
					if (data.inv_message_subject == '1') {
						$("#inv_message_subject").css({
							'border' : '1px solid red'
						});
					}
					if (data.inv_message_body == '1') {
						$("#inv_message_body").css({
							'border' : '1px solid red'
						});
					}
				}
			});

	return false;

}

function invSendMail() {
	var send_type = $('input[name=mailto]:checked').val();
	var prefix = "";
	var checkboxes = "";

	$('input[name="mailto_status[]"]:checked').each(function(el) {
		checkboxes = checkboxes+prefix+$(this).val(); 
		prefix = ",";
	});

	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	
	$("#fullscreen").load('ajax.php?t=invitations&a=check_send_mail',{anketa:srv_meta_anketa_id, noNavi:'true', send_type:send_type, checkboxes:checkboxes});

};
*/
function inv_del_rec_profile() {

    var pid = $("#inv_import_list_profiles ol li.active").attr("pid");
    
	if (confirm(lang['srv_inv_recipients_delete_profile_confirm'])) {

		$.post('ajax.php?t=invitations&a=delete_rec_profile', {anketa:srv_meta_anketa_id, pid:pid, noNavi:'true'}, function(data) {
            
            // osvežimo polja
            var new_pid = $("#inv_import_list_profiles ol li").first().attr('pid');
            
            // če je slučajno isti kot smo ga zbrisli izberemo nasledenjega
			if (new_pid == pid) {
				var new_pid = $("#inv_import_list_profiles ol li").first().next().attr('pid');
            }
            
			$(".anketa_edit_main").load('ajax.php?t=invitations&a=use_recipients_list', {anketa:srv_meta_anketa_id, pid:new_pid });
		});
	}
}

function inv_edit_rec_profile() {
	var pid = $("#inv_import_list_profiles ol li.active").attr("pid");
	
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=invitations&a=edit_rec_profile', {anketa:srv_meta_anketa_id, pid:pid, noNavi:'true'});
}

function inv_prepare_add_recipients() {
	alert('Deprecated!');
	return false;
}

function inv_recipients_add_to_list() {
	// prejemnike shranimo v seznam pred dodajanjem v bazo
	var recipients_list = $("#inv_recipients_list").val();
	var fields = [];

	$('ul').children('li.inv_field_enabled').each(function(idx, elm) {
		fields.push(elm.id);
	});                               
		
	var doAdd = '0';
		
	if (fields.length > 0) {
		if (recipients_list.length > 0) {
			$('#fade').fadeTo('slow', 1);
			$('#fullscreen').html('').fadeIn('slow');
			$("#fullscreen").load('ajax.php?t=invitations&a=get_profile_name', {anketa:srv_meta_anketa_id, recipients_list:recipients_list, fields:fields, noNavi:'true', doAdd:doAdd});
		} else {
			alert(lang['srv_invitation_note1']);
		}
	} else {
		alert(lang['srv_invitation_note2']);
	}
}

function inv_new_recipients_list_change(what) {
	if ($(what).val() > 0) {
		$("#new_recipients_list_span_note").show();
		$("#new_recipients_list_span_note_name").html($(what).find('option:selected').text());
	} else {
		$("#new_recipients_list_span_note").hide();		
	}
	if ($(what).val() == 0) {
		
		$("#new_recipients_list_span").show();
		$("#rec_profile_comment").val('');
	} else {
		$("#new_recipients_list_span").hide();
		$("#rec_profile_comment").val($(what).find('option:selected').attr('comment'));
	}
}

function invSendPage(page,show_per_page) {
	var start = (page-1) * show_per_page;
	var end = start +show_per_page;
	$("#tbl_recipients_send_list").find('tbody tr').slice(1).addClass('displayNone').slice(start,end).removeClass('displayNone');
	$("#frm_do_send div#pagination div").removeClass('currentPage_small');
	$("#frm_do_send div#pagination div").slice(page-1,page).addClass('currentPage_small');
}

function invSendPageChangeLimit(what,cnt) {
	// osvežimo paginacijo
	$("#inv_pagination_content").load('ajax.php?t=invitations&a=changePaginationLimit', {anketa:srv_meta_anketa_id, noNavi:'true', limit:$(what).val(), cnt:cnt},
		function() { invSendPage(1,$(what).val()); }
	);
	
}
function invTogleSend(what) {
	var checked = $(what).is(":checked") ? true : false;
	 $('#tbl_recipients_send_list tr td input[type=checkbox]').attr('checked', checked );
	// preštejemo obkljukane
		var cb = $('#tbl_recipients_send_list tr td input').filter(':checked').length;
		$("#inv_num_recipients").html(cb);

		if (cb > 0 ) {
			$("#inv_send_mail_btn").show();
		} else {
			$("#inv_send_mail_btn").hide();
		}
		
		if (cb > 4999 ) {
			$("#inv_send_mail_limit").show();
		} else {
			$("#inv_send_mail_limit").hide();
		}
}

function inv_selectAll(val){
	// oznacimo vse checkboxe
	$('#tbl_recipients_list tr td input[type="checkbox"]').each(function() {
		$(this).attr("checked", val);
	});

	if(val == true){
		$("#inv_switch_on").hide();
		$("#inv_switch_off").show();
	} else{
		$("#inv_switch_off").hide();
		$("#inv_switch_on").show();
	}
}

function inv_list_selectAll(val){
    $(document).ready(function(){
        if(val == true){
            $('.test_checkAll').each(function(){
                this.checked = true;
            })
            $("#inv_switch_on").hide();
            $("#inv_switch_off").show();
        }else{
            $('.test_checkAll').each(function(){
                this.checked = false;
            })
            $("#inv_switch_on").show();
            $("#inv_switch_off").hide();
        }
    });
}

function inv_recipients_form_action(action) {
	
	var recipents = $('#tbl_recipients_list input[name="inv_rids[]"]:checked');
	
	if (action == 'delete') {
		// imamo akcijo briši, preverimo če je kak izbran 
		if (recipents.length) {
			if (confirm(lang['srv_inv_recipients_delete_multi'])) {
				$("#frm_inv_rec_export").attr("action", "ajax.php?t=invitations&a=delete_recipient");
				$('#frm_inv_rec_export').submit();
				
			    return false;	
			}
		} else {
			if (confirm(lang['srv_invitation_note10'])) {
				//alert('brisem vse!');
				$.post('ajax.php?t=invitations&a=delete_recipient_all', {anketa:srv_meta_anketa_id,noNavi:'true'}, function(data) {
					data = jQuery.parseJSON(data);
					if (data.success == 1) {
						$(".anketa_edit_main").load('ajax.php?t=invitations&a=view_recipients', {anketa:srv_meta_anketa_id});
					} else {
						alert(data.error);
					}
				});
			}
			
		}
	} 
	else if (action == 'export_all') {
		$("#frm_inv_rec_export").attr("action", "ajax.php?t=invitations&a=export_recipients_all");
		$("#frm_inv_rec_export").attr("target", "_blank");
		$('#frm_inv_rec_export').submit();
		
	    return false;
	}
	else if (action == 'export') {
		// imamo akcijo izvozi
		//če je kak izbran izvozimo tistega, če ne pa izberemo vse in izvozimo vse
		if (recipents.length == 0) {
			// izberemo vse checkboxe
			
			//$('#tbl_recipients_list input[name="inv_rids[]"]').attr('checked', 'true');
			//var recipents = $('#tbl_recipients_list input[name="inv_rids[]"]:checked');
			//$('#tbl_recipients_list input[name="inv_rids[]"]').attr('checked', 'false');
			$("#frm_inv_rec_export").attr("action", "ajax.php?t=invitations&a=export_recipients_all");
			$("#frm_inv_rec_export").attr("target", "_blank");
			$('#frm_inv_rec_export').submit();
		
		} else {
			// izvozimo samo izbrane
			$("#frm_inv_rec_export").attr("action", "ajax.php?t=invitations&a=export_recipients");
			$("#frm_inv_rec_export").attr("target", "_blank");
			$('#frm_inv_rec_export').submit();
			
		}		
		
	    return false;
	} 
	else if (action == 'add') {
		// imamo akcijo dodaj respondente
		if (recipents.length) {
			//if (confirm(lang['srv_inv_recipients_delete_multi'])) {
				$("#frm_inv_rec_export").attr("action", "ajax.php?t=invitations&a=add_checked_users_to_database");
				$('#frm_inv_rec_export').submit();
				
			    return false;	
			//}
		}
	}
}

function inv_recipients_list_action(action){
    if(action == 'delete'){
        var ids = [];
        $('.test_checkAll').each(function(){
           if(this.checked == true){
               ids.push(this.value);
           }
        });


        if(ids.length){
            if(confirm(lang['srv_inv_list_delete_multi'])){
                $.ajax({
                   type: "POST",
                    data: {anketa:srv_meta_anketa_id,noNavi:'true',ids: ids},
                    url: "ajax.php?t=invitations&a=deleteRecipientsListMulti",
                    success: function(data){
                        //console.log("Tle pride");
                        $(".anketa_edit_main").load('ajax.php?t=invitations&a=inv_lists', {anketa:srv_meta_anketa_id});
                    }
                });
            }
        }
    }else{
        confirm(lang['srv_inv_list_delete_multi']);
    }
}
/*
function inv_prepare_save_message() {
	// kateri mid imamo da če dodajamo k obstoječmu ga kr izberemo
	var mid = $("#invitation_messages ol li.active").attr('mid');
	
	// shrani - uredi 
	var chck1 = $("input#inv_message_save_type1").is(":checked");
	// pošlji
	//var chck2 = $("input#inv_message_send_type2").is(":checked");
	
	if (chck1) {
		// vsaj en checkbox more bit obkljukan
		$('#fade').fadeTo('slow', 1);
		$('#fullscreen').html('').fadeIn('slow');
		$("#fullscreen").load('ajax.php?t=invitations&a=prepare_save_message', {anketa:srv_meta_anketa_id, noNavi:'true', mid:mid});
	} else {
		// shranimo v trenutno izbrano sporočilo in redirektmo na pošiljanje
		// shranimoi v novo sporočilo
		edit_message_save('-1');
	}
	return false;
}
*/
/*
function edit_message_save(mid) {
	var replyto = $("#inv_message_replyto").val();
	var subject = $("#inv_message_subject").val();
	var body = $("#inv_message_body").val();
	var profile_comment = $("#inv_message_comment").val();
	var quickSave = true;
	if (!mid || mid == 'undefined') {
		mid = $("#inv_recipients_profile_name select").val();
		quickSave = false;
	}
	var naslov = $("#rec_profile_name").val();
	var old_mid = $("#invitation_messages ol li.active").attr('mid');
	// resetiramo morebitne prejšne napake
	$("#inv_message_replyto").css({
		'border' : 'none'
	});
	$("#inv_message_subject").css({
		'border' : 'none'
	});
	$("#inv_message_body").css({
		'border' : 'none'
	});

	$("#inv_error_note").addClass('hidden');

	$.post(
			'ajax.php?t=invitations&a=save_message',
			{
				anketa : srv_meta_anketa_id,
				mid : mid,
				old_mid:old_mid,
				quickSave:quickSave,
				replyto : replyto,
				subject : subject,
				body : body,
				noNavi : 'true',
				profile_comment : profile_comment,
				naslov:naslov
			},
			function(data) {
				data = jQuery.parseJSON(data);
				if (data.error == "0") {
					// redirektamo na pošiljanje
					//var href = 'index.php?anketa='+srv_meta_anketa_id+'&a=invitations&m=send_message';
					//window.location = href;
					window.location.reload()
				} else {
					// skrijemo okno in 
					$('#fade').fadeOut('slow');
					$('#fullscreen').fadeOut('slow').html('')
					// prikažemo obvestilo o napaki
					$("#inv_error_note").html(data.msg);
					$("#inv_error_note").removeClass('hidden');
					if (data.inv_message_replyto == '1') {
						$("#inv_message_replyto").css({
							'border' : '1px solid red'
						});
					}
					if (data.inv_message_subject == '1') {
						$("#inv_message_subject").css({
							'border' : '1px solid red'
						});
					}
					if (data.inv_message_body == '1') {
						$("#inv_message_body").css({
							'border' : '1px solid red'
						});
					}
				}
			});

	return false;
}
*/
function inv_arch_edit_details(aid) {
	if (aid > 0) {
		$('#fade').fadeTo('slow', 1);
		$('#fullscreen').html('').fadeIn('slow');
		$("#fullscreen").load('ajax.php?t=invitations&a=arch_edit_details', {anketa:srv_meta_anketa_id, aid:aid,noNavi:'true'});	
		
	}
}
function inv_arch_show_details(aid) {
	if (aid > 0) {
		$('#fade').fadeTo('slow', 1);
		$('#fullscreen').html('').fadeIn('slow');
		$("#fullscreen").load('ajax.php?t=invitations&a=arch_show_details', {anketa:srv_meta_anketa_id, aid:aid,noNavi:'true'});	
		
	}
}
function inv_arch_show_recipients(aid) {
	if (aid > 0) {
		$('#fade').fadeTo('slow', 1);
		$('#fullscreen').html('').fadeIn('slow');
		$("#fullscreen").load('ajax.php?t=invitations&a=arch_show_recipients', {anketa:srv_meta_anketa_id, aid:aid,noNavi:'true'});	
		
	}
}
function showRecipientTracking(rid) {
	if (rid > 0) {
		$('#fade').fadeTo('slow', 1);
		$('#fullscreen').html('').fadeIn('slow');
		$("#fullscreen").load('ajax.php?t=invitations&a=showRecipientTracking', {anketa:srv_meta_anketa_id, rid:rid,noNavi:'true'});	

	}
}
function changeInvRecListCheckbox() {
	var pids = "";
	var prefix = "";
	$('#inv_edit_rec_list table tr td input:checked').each(function(idx, elm) {
		pids = pids + prefix + $(elm).attr('value');
		prefix = ',';
	});     
	var onlyThisSurvey = $('input[name=inv_show_list_type]:checked').val();
	
	$("#inv_selected_rec_list").load('ajax.php?t=invitations&a=editRecList', {anketa:srv_meta_anketa_id,noNavi:'true',pids:pids, onlyThisSurvey:onlyThisSurvey});
	
}
/*
//urejanje prejemnikov
$("#inv_edit_rec_list table tr td").live('click', function(event) {
	var isCtrlPressed = event.ctrlKey;
	
	var target = $(event.target).closest("tr");
	var pid = $(target).attr('pid');
	
	
	// če imamo CTRL pritisnjen med klikom omogočimo muli select
	if (isCtrlPressed == true) {
		if (target.hasClass('active')) {
			target.removeClass('active');
		} else {
			target.addClass('active');
		}
	} else {
		// izbiramo vsakega posebej
		$('#inv_edit_rec_list table tr').removeClass('active');
		target.addClass('active');
	}

	var pids = "";
	var prefix = "";
	$('#inv_edit_rec_list table tr.active').each(function(idx, elm) {
		pids = pids + prefix + $(elm).attr('pid');
		prefix = ',';
	});     
	
	$("#inv_selected_rec_list").load('ajax.php?t=invitations&a=editRecList', {anketa:srv_meta_anketa_id,noNavi:'true',pids:pids});
	
});
*/
function inv_list_edit(pid) {
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=invitations&a=invListEdit', {anketa:srv_meta_anketa_id, pid:pid, noNavi:'true'});
}

function inv_list_get_name(saveNew) {
	// prejemnike shranimo v seznam pred dodajanjem v bazo
	var recipients_list = $("#inv_recipients_list").val();
	var fields = [];

	$('div#inv_field_container ul').children('li.inv_field_enabled').each(function(idx, elm) {
		fields.push(elm.id);
	});                               
	// kateri pid imamo da če dodajamo k obstoječmu ga kr izberemo
	//var pid = $("#inv_import_list_profiles ol li.active").attr('pid');
	var pid = "";
	var prefix = "";
	$('#inv_edit_rec_list table tr td input:checked').each(function(idx, elm) {
		pid = pid + prefix + $(elm).attr('value');
		prefix = ',';
	});  
	
	if (fields.length > 0) {
		if (recipients_list.length > 0) {
			$('#fade').fadeTo('slow', 1);
			$('#fullscreen').html('').fadeIn('slow');
			$("#fullscreen").load('ajax.php?t=invitations&a=list_get_name', {anketa:srv_meta_anketa_id, recipients_list:recipients_list, fields:fields, noNavi:'true', pid:pid, saveNew:saveNew});
		} else {
			alert(lang['srv_invitation_note1']);
		}
	} else {
		alert(lang['srv_invitation_note2']);
	}

}
function changeInvRecListEdit() {
	var checked = $("#inv_show_list_edit").is(":checked");
	$.post('ajax.php?t=invitations&a=changeInvRecListEdit', {anketa:srv_meta_anketa_id, checked:checked, noNavi:'true'}, function() {
		changeInvRecListCheckbox();
	});
}
function inv_list_save() {
	
	var profile_id = $("#profile_id").val();
	var profile_name = $("#inv_recipients_profile_name").find("#rec_profile_name").val();
	var profile_comment = $("#inv_recipients_profile_name").find("#rec_profile_comment").val();
	var recipients_list = $("#inv_prof_recipients_list").val();
	var field_list = $("#inv_prof_field_list").val();
	var saveNew = ($("#saveNew").val() == 'true') ? 'true': 'false';
	alert(profile_name);
	$(".anketa_edit_main").load('ajax.php?t=invitations&a=inv_list_save', {anketa:srv_meta_anketa_id, recipients_list:recipients_list, field_list:field_list, profile_id:profile_id, profile_name:profile_name, profile_comment:profile_comment, saveNew:saveNew});
	$('#fade').fadeOut('slow');
	$('#fullscreen').fadeOut('slow').html('');
}
function inv_list_edit_save() {
	var form_serialize = $("#inv_list_edit_form").serializeArray();
	form_serialize[form_serialize.length] = {name:'anketa', value:srv_meta_anketa_id}
	form_serialize[form_serialize.length] = {name:'noNavi', value:'true'}
	
	$(".anketa_edit_main").load('ajax.php?t=invitations&a=invListEditSave', form_serialize);
	$('#fade').fadeOut('slow');
	$('#fullscreen').fadeOut('slow').html('');
}
function inv_list_save_old(profile_id) {
	
	var recipients_list = $("#inv_recipients_list").val();
	var fields = [];
	$('ul').children('li.inv_field_enabled').each(function(idx, elm) {
		fields.push(elm.id);
	});       
	var rec_profile_name = $("#rec_profile_name").val();
	var rec_profile_comment = $("#rec_profile_comment").val();
	
	$(".anketa_edit_main").load('ajax.php?t=invitations&a=invListSaveOld', {anketa:srv_meta_anketa_id, recipients_list:recipients_list, field_list:fields, profile_id:profile_id, rec_profile_name:rec_profile_name, rec_profile_comment:rec_profile_comment});
	$('#fade').fadeOut('slow');
	$('#fullscreen').fadeOut('slow').html('');
}

function deleteRecipientsList_confirm(id) {
	if (confirm(lang['srv_inv_recipients_delete_list_confirm'])) {
		$(".anketa_edit_main").load('ajax.php?t=invitations&a=deleteRecipientsList', {anketa:srv_meta_anketa_id, id:id});
	}
	
}
function inv_listAccess(show_hide) {
	if (show_hide == 'true') {
		$("#invListAccessShow1").toggle();
		$("#invListAccessShow2").toggle();
		$("div[name=listAccess]").each(function(){
			$(this).removeClass('displayNone');
		});
	} else {
		$("#invListAccessShow1").toggle();
		$("#invListAccessShow2").toggle();
		$("div[name=listAccess] label input:not(:checked)").each(function(){
			$(this).parent().parent().addClass('displayNone');
		});
	}
}

function showInvitationListsNames() {
	var onlyThisSurvey = $('input[name=inv_show_list_type]:checked').val();
	var pids = "";
	var prefix = "";
	$('#inv_edit_rec_list table tr td input:checked').each(function(idx, elm) {
		pids = pids + prefix + $(elm).attr('value');
		prefix = ',';
	});    
	
	
	$("#inv_edit_rec_list").load('ajax.php?t=invitations&a=showInvitationListsNames', {anketa:srv_meta_anketa_id,noNavi:'true',onlyThisSurvey:onlyThisSurvey, pids:pids},
			function() {
		// pids pogledamo na novo, ker se lahko vmes kaj spremeni 
		var pids = "";
		var prefix = "";
		$('#inv_edit_rec_list table tr td input:checked').each(function(idx, elm) {
			pids = pids + prefix + $(elm).attr('value');
			prefix = ',';
		});     
		
		$("#inv_selected_rec_list").load('ajax.php?t=invitations&a=editRecList', {anketa:srv_meta_anketa_id,noNavi:'true',pids:pids, onlyThisSurvey:onlyThisSurvey});
				//$('#inv_selected_rec_list div').nextAll().remove();
			}
	);
}

function inv_upload_list() {
	// vprašamo ali resnično želi izbrisati respondenta
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=invitations&a=upload_list', {anketa:srv_meta_anketa_id, noNavi:'true'});	
}

function inv_upload_list_check() {
	// preverimo polja
	
	// preverimo datoteko
	var file = $('#invListFile').val();
	
	if (file != '') {
		// preverimo končnico
		var extension = file.split('.').pop().toLowerCase();
		//var extension = file.substr( (file.lastIndexOf('.') +1) );
		if (extension == 'txt' || extension == 'csv')  {
			$('#inv_recipients_upload_form').submit();
		} else {
			alert("Nepravilna vrsta datoteke!");
		}
	} else {
		alert("Izberite datoteko!");
	}
}


//prikaze editor za ne-spremenljivko (za karkoli druzga pac)
function create_inv_editor (id, focus) {
	id='inv_message_body';

	if (!editor_init) {
		CKEDITOR.replace(id, {
            fullPage: true,
            allowedContent: true
        });

		editor_init = true;
    }
    else{
		CKEDITOR.replace(id, {
            fullPage: true,
            allowedContent: true
        });
    }
}

function mailSourceMesageChange(what) {
	var mid = $(what).val();
	if (mid != 'undefined' && mid > 0) {
		$("#inv_select_mail_preview").load('ajax.php?t=invitations&a=make_default_from_preview', {anketa:srv_meta_anketa_id, mid:mid,noNavi:'true'});
	}
}

function inv_set_sort_field(field,type) {
	$.post('ajax.php?t=invitations&a=setSortField', {anketa:srv_meta_anketa_id,noNavi:'true', field:field,type:type}, function() {
		$(".anketa_edit_main").load('ajax.php?t=invitations&a=view_recipients', {anketa:srv_meta_anketa_id});	
	});
}

function inv_addSysVarsMapping() {
	var form_serialize = $("#inv_ValidateSysVarsMappingFrm").serializeArray();
	form_serialize[form_serialize.length] = {name:'anketa', value:srv_meta_anketa_id};
	form_serialize[form_serialize.length] = {name:'noNavi', value:'true'};
	
	$("#inv_ValidateSysVarsMappingDiv").load('ajax.php?t=invitations&a=addSysVarsMapping', form_serialize);
	
}
function inv_ValidateSysVarsMapping() {
	var form_serialize = $("#inv_ValidateSysVarsMappingFrm").serializeArray();
	form_serialize[form_serialize.length] = {name:'anketa', value:srv_meta_anketa_id};
	form_serialize[form_serialize.length] = {name:'noNavi', value:'true'};
	
	$("#inv_ValidateSysVarsMappingDiv").load('ajax.php?t=invitations&a=validateSysVarsMapping', form_serialize);

}

function invSysVarMapChange(what) {
	var value = $(what).val()+"";
	var name = $(what).attr('name')+"";
	/*	
	if (value != "") {
		// uporabnik je izbral polje, v vseh ostalih selectih je potrebno disejblat to polje

		$('#inv_ValidateSysVarsMappingFrm select > option').each(function(el) {
			if ( $(this).parent().attr('name') != name && value == $(this).val()+"" ) {
				$(this).attr("disabled","disabled");
			}
		});
	} else 
	*/
	{
		//polovimo izbrane vrednosti ostale enejlamo
		var values = [];
		$('#inv_ValidateSysVarsMappingFrm select').each(function(el){
			if ($(this).val()+"" != "") {
				values.push($(this).val()+"");
			}
		});
		// zlopamo skozi vse opcije in najprej odstranimo disabled 
		$('#inv_ValidateSysVarsMappingFrm select > option').each(function(el) {
			$(this).removeAttr("disabled","disabled");
		});
		// zlopamo skozi vse opcije in najprej dodamo disabled 
		$('#inv_ValidateSysVarsMappingFrm select > option').each(function(el) {
			if ( $(this).parent().attr('name') != name && ($.inArray($(this).val()+"", values) !== -1)) {
				$(this).attr("disabled","disabled");
			}
		});
	}
}

function invRenameRecipientsChange() {
	var checked = $("#inv_recipients_rename_profile").is(":checked");
	if (checked  == true) {
		$("#div_inv_recipients_rename_list_type").show();
		$('#rec_profile_name').focus();
	} else {
		$("#div_inv_recipients_rename_list_type").hide();
	}
}

function invRecipientsForward() {
	// ali dodajamo respondente v anketo
	var doAdd = $("#inv_recipients_add").is(":checked") ? true : false;
	
	// vedno shranjujemo če ne druga v zacasin seznam.
	var doSave = true;
	
	var profile_name = $("#rec_profile_name").val();
	// ce ne shranjujemo uporabimo id novega profila
	var profile_id = $("#inv_import_list_profiles ol li.active").attr('pid');
	
	if (doSave == true)
	{ // ce shranjujemo uporabimo id novega profila
		profile_id = $("#sel_inv_list_type").find('option:selected').val();
	} 
	var profile_comment = $("#rec_profile_comment").val();
	var recipients_list = $("#inv_recipients_list").val();
        var recipientsDelimiter = $('input[name=recipientsDelimiter]:checked').val();
        
        var fields = [];
	$('ul').children('li.inv_field_enabled').each(function(idx, elm) 
	{
		fields.push(elm.id);
	});      
	
	if (fields.length > 0)
	{
		if (recipients_list.length > 0)
		{
			// shranimo seznam ali dodamo respondente ali oboje
			$(".anketa_edit_main").load('ajax.php?t=invitations&a=recipientsAddForward', {anketa:srv_meta_anketa_id, doAdd:doAdd, doSave:doSave, recipients_list:recipients_list, fields:fields, profile_name:profile_name, profile_comment:profile_comment, profile_id:profile_id, recipientsDelimiter: recipientsDelimiter});
		}
		else
		{
			alert(lang['srv_invitation_note1']);
		}
	} 
	else 
	{
		alert(lang['srv_invitation_note2']);
	}
}

function showInvitationAdvancedConditions(cid) {
	if (typeof cid === 'undefined')
	{
		if ($("a.faicon.if_add").data('cid').length)
		{
			cid = $("a.faicon.if_add").data('cid');
		}
		else 
		{
			cid = 0;
		}
	}
	
	
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=invitations&a=showAdvancedConditions', {anketa:srv_meta_anketa_id,noNavi:'true', cid:cid});
}

function invitationSetCondition(cid)
{
	var cid = $("#divConditionProfiles #condition_profile").find(".active").data('cid');
	$('#fullscreen').fadeOut('slow').html('');
	$('#fade').fadeOut('slow');

	$(".anketa_edit_main").load('ajax.php?t=invitations&a=setAdvancedCondition', {anketa:srv_meta_anketa_id, cid:cid});
}

function noEmailingToggle(value){
	
	$.post('ajax.php?t=invitations&a=set_noEmailing', {anketa:srv_meta_anketa_id, value:value}, function(data) {
		/*if(value == '1'){
			$('#inv_messages_holder').hide();
			$('#inv_messages_holder_noEmailing').show();
		}
		else{
			$('#inv_messages_holder_noEmailing').hide(); 
			$('#inv_messages_holder').show();
		}*/
		window.location.reload();
	});
}
function noEmailingType(value){
	
	$.post('ajax.php?t=invitations&a=set_noEmailing_type', {anketa:srv_meta_anketa_id, value:value});
}


// AAI - popup pri vklopu ARNES smtp streznika pri vabilih
function smtpAAIPopupShow(){
	
    $('#fade').fadeTo('slow', 1);
	$('#popup_note').html('').fadeIn('slow');
	$("#popup_note").load('ajax.php?t=invitations&a=showAAISmtpPopup', {anketa: srv_meta_anketa_id, noNavi:'true'});
}
function smtpAAIPopupClose(){
	
    // Ni sprejel - vrnemo radio
    $('input[name=SMTPMailMode][value=2]').prop('checked', true);

    $('#popup_note').fadeOut('slow').html('');
	$('#fade').fadeOut('slow');
}
function smtpAAISet(){
	
    // Shranimo formo
    $("form[name='settingsanketa_"+srv_meta_anketa_id+"']").submit();

    // Prikazemo nastavitve za Arnes smtp
    /*$('#send_mail_mode1, #send_mail_mode2').hide();
    $('#send_mail_mode0').show();

    // Zapremo popup
    smtpAAIPopupClose();*/
}
function smtpAAIAccept(){
	
    if($('#aai_smtp_checkbox').is(':checked'))
        $('#aai_smtp_button').show();
    else
        $('#aai_smtp_button').hide();
}


// SQUALO
function squaloSwitch(){

    if($('#squalo_mode').prop('checked')){
        $('#send_mail_mode0, #send_mail_mode1, #send_mail_mode2, .mail_mode_switch, #send_mail_mode_test').hide();
        $('#success_save').hide();
    }
    else{
        $('.squalo_settings').hide();
        $('#send_mail_mode2, .mail_mode_switch, #send_mail_mode_test').show();
        $('#success_save').hide();
    }
}

