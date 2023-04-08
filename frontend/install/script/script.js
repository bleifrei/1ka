// Ajax za submit nastavitev za zapis v settings_optional
function settingsSubmit(){

    var form = $('form#settings_form').serializeArray();

    $.post('ajax.php?a=submit_settings', form, function () {
       
        // Redirectamo na naslednji korak
        window.location = "index.php?step=database";
    });
}

function settingsToggle(){

    if($('input[name="footer_survey_custom"]:checked').val() == '0'){
        $('.footer_survey_text').hide('medium');
    }
    else{
        $('.footer_survey_text').show('medium');
    }

    if($('input[name="footer_custom"]:checked').val() == '0'){
        $('.footer_text').hide('medium');
    }
    else{
        $('.footer_text').show('medium');
    }

    if($('input[name="head_title_custom"]:checked').val() == '0'){
        $('.head_title_text').hide('medium');
    }
    else{
        $('.head_title_text').show('medium');
    }

    if($('input[name="email_signature_custom"]:checked').val() == '0'){
        $('.email_signature_text').hide('medium');
    }
    else{
        $('.email_signature_text').show('medium');
    }
}


// Ajax za uvoz celotne baze
function databaseImport(){

    $('#fade').fadeIn();
    $('#popup').fadeIn();

    $('#db_response').load('ajax.php?a=import_database', function () { 
        $('#fade').fadeOut();
        $('#popup').fadeOut();
    });
}

// Ajax za posodobitev baze
function databaseUpdate(){

    $('#fade').fadeIn();
    $('#popup').fadeIn();

    $('#db_response').load('ajax.php?a=update_database', function () {
        $('#fade').fadeOut();
        $('#popup').fadeOut();
    });
}