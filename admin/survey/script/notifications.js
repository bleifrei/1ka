// poslje novo sporocilo
function sendNotification() {

    if(CKEDITOR.instances['notification']){
		CKEDITOR.instances['notification'].destroy();
	}

	var recipient = $( "input[name='recipient']" ).val();	
	var title = $( "input[name='title']" ).val();
    //var notification = $( "textarea[name='notification']" ).val();
    var notification = $("#notification").val();
	
	if ($( "input[name='recipient_all_slo']" ).is(':checked'))
		var recipient_all_slo = 1;
	else
		var recipient_all_slo = 0;
		
	if ($( "input[name='recipient_all_ang']" ).is(':checked'))
		var recipient_all_ang = 1;
	else
		var recipient_all_ang = 0;
	
	if ($( "input[name='force_show']" ).is(':checked'))
		var force_show = 1;
	else
		var force_show = 0;
	
	$('#notifications').load('ajax.php?t=notifications&a=sendNotification', {
        anketa:srv_meta_anketa_id, 
        recipient:recipient, 
        recipient_all_slo:recipient_all_slo, 
        recipient_all_ang:recipient_all_ang, 
        title:title, 
        notification:notification, 
        force_show:force_show,

        function(){
            if (!CKEDITOR.instances) {
                CKEDITOR.replace['notification'];
            }
        }
    });
}

// prikaze sporocilo in ga oznaci kot viewed
function viewMessage(id) {
	
	$('#notifications').load('ajax.php?t=notifications&a=viewMessage', {anketa:srv_meta_anketa_id, id:id});
}

// oznaci sporocilo kot prebrano vsem prejemnikom
function resolveMessages(id) {
	
	$('.sent_list').load('ajax.php?t=notifications&a=resolveMessages', {anketa:srv_meta_anketa_id, id:id});
}

// Prikaze popup z neprebranimi sporocili
function showUnreadMessages(){

	$('#unread_notifications').load('ajax.php?t=notifications&a=viewUnreadMessages', {anketa:srv_meta_anketa_id}, function (data) {
		$('#unread_notifications').show();
		$('#fade').fadeTo('slow', 1);
	});
}

function closeUnreadMessages(){
	
	// Pobrisemo opozorilo na vrhu strani
	$('#new_notification_alert').remove();
	
	// Zapremo okno
	$('#fade').fadeOut('slow');
	$("#unread_notifications").fadeOut();
}

function recipient_all_disable_email(){
	
	if ($( "input[name='recipient_all_slo']" ).is(':checked'))
		var recipient_all_slo = 1;
	else
		var recipient_all_slo = 0;
		
	if ($( "input[name='recipient_all_ang']" ).is(':checked'))
		var recipient_all_ang = 1;
	else
		var recipient_all_ang = 0;
		
	if(recipient_all_slo == 0 && recipient_all_ang == 0)
		$("#recipient").attr('disabled', false);
	else
		$("#recipient").attr('disabled', true);
}


// Prikaze popup z neprebranimi sporocili
function showGDPRMessage(){

	$('#unread_notifications').load('ajax.php?t=notifications&a=viewGDPRMessage', {anketa:srv_meta_anketa_id}, function (data) {
		$('#unread_notifications').show();
		$('#fade').fadeTo('slow', 1);
	});
}

function enableGDPRPopupButton(){
	$("#GDPR_popup_button").css("visibility", "visible");
}

function saveGDPRMessage(){
	
	var gdpr_agree = '-1';
	
	if ($("input[name='gdpr_agree']:checked").val())
		gdpr_agree = $("input[name=gdpr_agree]:checked").val();
	
	if(gdpr_agree == '0' || gdpr_agree == '1'){
		$.post('ajax.php?t=notifications&a=saveGDPRAgree', {gdpr_agree:gdpr_agree, anketa:srv_meta_anketa_id}, function (data) {
			
			// Zapremo okno
			$('#fade').fadeOut('slow');
			$("#unread_notifications").fadeOut();
		});
	}
}

function toggleGDPRMore(){
	
	$("#gdpr_popup_more").toggle();
}
