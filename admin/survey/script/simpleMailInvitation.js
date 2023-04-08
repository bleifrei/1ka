function simpleMailInvitation_init() {
	$("#simpleMailInvitation_div div.profile_holder div.option").live("click", function(event) {
		var $target = $(event.target);
		if ($target.hasClass('active') == false) {
			var pid = $target.attr('value');
			var emails = $("#simpleMailInvitation_div").find("#simpleMailRecipients").val();

			$("#fullscreen").load("ajax.php?t=simpleMailInvitation&a=showInvitation", {anketa:srv_meta_anketa_id, emails:emails, pid:pid});
			return false;
		}
		return false;
	});
}

function previewMailInvitation() {
	var pid = $("#simpleMailInvitation_div div.profile_holder div.active").attr('value');
	var subject = $("#simpleMailSubject").val();
	var body = $("#simpleMailBody").val();
	var emails = $("#simpleMailInvitation_div").find("#simpleMailRecipients").val();
	$("#simpleMailInvitationCoverDiv").fadeIn();
	$("#simpleMailInvitationPreviewDiv").load("ajax.php?t=simpleMailInvitation&a=previewInvitation", {anketa:srv_meta_anketa_id, emails:emails, pid:pid, subject:subject, body:body}).show();
}

function showSimpleMailInvitation() {
	var emails = $("#mails").val();
	
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load("ajax.php?t=simpleMailInvitation&a=showInvitation", {anketa:srv_meta_anketa_id, emails:emails});
}

function sendSimpleMailInvitation() {
	// damo cover, da preprečimo morebitno večkratno pošiljanje
	$("#simpleMailInvitationCoverDiv").fadeIn();
	
	var subject = $("#simpleMailSubject").val();
	var body = $("#simpleMailBody").val();
	var emails = $("#simpleMailInvitation_div").find("#simpleMailRecipients").val();

	$("#fullscreen").load("ajax.php?t=simpleMailInvitation&a=sendInvitation", {anketa:srv_meta_anketa_id, emails:emails, subject:subject, body:body});
}