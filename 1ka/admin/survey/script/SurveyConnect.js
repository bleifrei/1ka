function sc_display (usr_id) {
	
	$('#survey-connect-disp').toggle().load('ajax.php?t=SurveyConnect&a=display', {usr_id: usr_id, anketa: srv_meta_anketa_id});

}