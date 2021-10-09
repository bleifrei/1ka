/* FUNKCIJE ZA UREJANJE GDPR NASTAVITEV */
function editGDPRSurvey(ank_id){
	
	var form_serialize = $('form[name="settingsanketa_'+ank_id+'"]').serializeArray();
	form_serialize[form_serialize.length] = {name: 'ank_id', value: ank_id};
	
	$.post('ajax.php?t=gdpr&a=gdpr_edit_anketa&s=1', form_serialize, function(){
		window.location.reload();
	});
}
// Prikazemo/skrijemo nastavitve katere osebne podatke (gdpr) zbiramo
function showGDPRSettings(){
    
    // Prikazemo/skrijemo identifikatorje
    var is_gdpr = $("input[name='is_gdpr']:checked").val();

    var is_identifier = 0;
    is_identifier += parseInt($("input[name='name']:checked").val());
    is_identifier += parseInt($("input[name='email']:checked").val());
    is_identifier += parseInt($("input[name='location']:checked").val());
    is_identifier += parseInt($("input[name='phone']:checked").val());
    is_identifier += parseInt($("input[name='web']:checked").val());
    is_identifier += parseInt($("input[name='other']:checked").val());

    if(is_gdpr == '1'){
        $("#gdpr_data_identifiers").show('fast');
    }
    else{
        $("#gdpr_data_identifiers").hide('fast');
    }

	if(is_identifier > 0 && is_gdpr == '1'){
        $("#gdpr_data_settings").show('fast');
        $("#gdpr_additional_info").show('fast');
        $("#gdpr_export_individual").show('fast');
        $("#gdpr_export_activity").show('fast');
    }
	else{
        $("#gdpr_data_settings").hide('fast');
        $("#gdpr_additional_info").hide('fast');
        $("#gdpr_export_individual").hide('fast');
        $("#gdpr_export_activity").hide('fast');
    }
}
// Prikazemo/skrijemo opozorilo za uporabo gdpr templata
function showGDPRTemplate(val){
	
	if(val == '1'){
		$("#gdpr_data_template").show();
		$("#gdpr_data_template_warning").hide();
	}
	else{
		$("#gdpr_data_template").hide();
		$("#gdpr_data_template_warning").show();
	}
}
// Prikazemo preview uvoda v gdpr nasatvitvah ankete
function previewGDPRIntro(){
	
	var ank_id = $("input[name=anketa]").val();
	
	$('#fullscreen').html('').fadeIn('slow').draggable({
        delay: 100
    });
    $('#fade').fadeTo('slow', 1);
    $('#fullscreen').load('ajax.php?t=gdpr&a=gdpr_preview_intro', {
        ank_id: ank_id
    }).draggable({
        delay: 100
    });
}
// Prikazemo/skrijemo textarea za drugo pri zbiranju gdpr podatkov
function toggleGDPROtherText(obj){
    
    var val = $(obj).val();

    // Enable text input
    if(val == '1'){
        $('#other_text').show('fast');
    }
    // Disable text input
    else{
        $('#other_text').hide('fast');
    }
}
// Enablamo/disablamo text polja pri dodatnih informacijah
function toggleGDPRInfoText(obj){
    
    var name = $(obj).attr("name");
    var val = $(obj).val();

    // Enable text input
    if(val == '1'){
        $(".line_text." + name).prop("disabled", false);
    }
    // Disable text input
    else{
        $(".line_text." + name).prop("disabled", true);
    }
}

// Prikazemo preview posameznega izvoza
function previewGDPRExport(type){
	
	var ank_id = $("input[name=anketa]").val();
	
	$('#fullscreen').html('').fadeIn('slow').draggable({
        delay: 100
    });
    $('#fade').fadeTo('slow', 1);
    $('#fullscreen').load('ajax.php?t=gdpr&a=gdpr_preview_export', {
        ank_id: ank_id,
        type: type
    }).draggable({
        delay: 100
    });
}

// Nastavimo anketo da je GDPR
function setGDPRSurvey(ank_id, checked){
	
	var gdpr = '0';
	if(checked)
		gdpr = '1';
	
	$("#gdpr_nastavitve").load('ajax.php?t=gdpr&a=gdpr_add_anketa', {ank_id: ank_id, value:gdpr});
}

// Urejamo GDPR profilne nastavitve avtorja
function editGDPRProfile(){
	
	var form_serialize = $("#form_gdpr_user_settings").serializeArray();
	
	$("#gdpr_nastavitve").load('ajax.php?t=gdpr&a=gdpr_edit_user&s=1', form_serialize);
}
// Prikazemo/skrijemo nastavitve katere osebne podatke (gdpr) zbiramo
function editGDPRAuthority(country){

	$("#gdpr_authority_info").load('ajax.php?t=gdpr&a=gdpr_edit_authority', {country: country});
}
// Prikazemo/skrijemo nastavitve organizacije in dpo-ja (ce je zasebnik)
function toggleGDPRDPO(){

	var organization = $('input[name=type]:checked').val();
	var has_dpo = $('input[name=has_dpo]:checked').val();

	if(organization == '1' || has_dpo == '1')
        $("#gdpr_dpo").show();
	else
        $("#gdpr_dpo").hide();
        
    if(organization == '1'){
        $("#gdpr_organization").show();
        $("#gdpr_has_dpo").hide();
    }
	else{
        $("#gdpr_organization").hide();
        $("#gdpr_has_dpo").show();
    }
}
// Prikazemo/skrijemo nastavitve organizacije in dpo-ja (ce je zasebnik)
function toggleGDPRHasDPO(){

	var has_dpo = $('input[name=has_dpo]:checked').val();

	if(has_dpo == '1')
		$("#gdpr_dpo").show();
	else
		$("#gdpr_dpo").hide();
}

// Nastavimo zahtevo za izbris da je opravljena
function setGDPRRequestStatus(request_id, checked){
		
	var value = '0';
	if(checked)
		value = '1';
	
	$("#gdpr_nastavitve").load('ajax.php?t=gdpr&a=gdpr_request_done', {request_id: request_id, value:value});
}
// Nastavimo zahtevo za izbris da je opravljena - znotraj ankete
function setGDPRRequestStatusSurvey(request_id, checked){
	
	var ank_id = $("input[name=anketa]").val();
		
	var value = '0';
	if(checked)
		value = '1';
	
	$("#survey_requests").load('ajax.php?t=gdpr&a=gdpr_request_done_survey', {request_id: request_id, value:value, ank_id:ank_id});
}

// Nastavimo komentar zahtevi za izbris
function setGDPRRequestComment(request_id, text){
		
	var value = text;
	
	$("#anketa_edit").load('ajax.php?t=gdpr&a=gdpr_request_comment', {request_id: request_id, value:value});
}
// Nastavimo komentar zahtevi za izbris - znotraj ankete
function setGDPRRequestCommentSurvey(request_id, text){
	
	var ank_id = $("input[name=anketa]").val();
	var value = text;
	
	$("#survey_requests").load('ajax.php?t=gdpr&a=gdpr_request_comment_survey', {request_id: request_id, value:value, ank_id:ank_id});
}
