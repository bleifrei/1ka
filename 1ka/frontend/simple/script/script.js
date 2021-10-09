function CookieAlertSubmit (form) {
	
	$("#kuki").dialog({
		modal: true,
		buttons: {
			"DA, Nadaljuj s prijavo": function() {
				$(this).dialog( "close" );
				$('#' + form).submit();
			},
			"Ne": function() {
				$(this).dialog( "close" );
			}
		}
	});
}

function CookieAlertFB () {

	$("#kuki").dialog({
		modal: true,
		buttons: {
			"DA, Nadaljuj s prijavo": function() {
				$(this).dialog( "close" );
				document.location.href='../api/fb_login.php';
			},
			"Ne": function() {
				$(this).dialog( "close" );
			}
		}
	});
}

function LostPassword(alert_text) {
	
	var email = document.getElementById('em').value;
	
	if (email === '') {
		alert(alert_text);
	}
	else {

        var lang_param = '';
        var lang_id = $('input[name="lang_id"]').val();
        if(lang_id == '1' || lang_id == '2'){
            lang_param = '&lang_id=' + lang_id;
        }

		document.location.href = '../api/api.php?action=reset_password&email=' + email + lang_param;
	}
}

// function CheckEmailFP() {
//   var Email = document.getElementById('em');
//   console.log(Email.value);
//   if (Email.search('@') == -1 || Email.indexOf('.') == -1)
//     alert ('Prosimo, popravite email');
// }


// Posljemo zahtevo za izbris (iz simple frontenda)
function sendGDPRRequest(){
	
	var form_serialize = $("#gdpr").serializeArray();

    $.ajax({
        url : '../../utils/gdpr_request.php',
        type: "POST",
        data : form_serialize,
        success:function(response){
			$("#gdpr_holder").load('frontend/simple/ajax.php?a=gdpr_request_send', {json: JSON.parse(response)});
        }
    });
}


function switchLoginRegistration(clicked_tab){

    if($(clicked_tab).hasClass('active'))
        return;

    $("#registration_holder").toggle('fast');
    $("#login_holder").toggle('fast');

    $(".tab").toggleClass('active');
}
