function break_init() {
}


function breakSpremenljivkaChange() {
	// drugi dropdown
	var spr = $('#breakSpremenljivka').val();
	var seq = $("#breakSpremenljivka option:selected").attr('seq');
	$("#breakResults").html('');
	$("#breakResults").load("ajax.php?t=break&a=spremenljivkaChange", {anketa:srv_meta_anketa_id, spr:spr, seq:seq}, function() {
		$("#breakResults").fadeTo(100, 1);
	}).show();
}

function change_break_percent () {
	var break_percent = $("#break_percent").is(':checked');
	var crossChk1 = break_percent;
	//ponastavimo tudi za crostabe
	$.post("ajax.php?t=crosstab&a=change_cb_percent", {anketa:srv_meta_anketa_id, crossChk1:crossChk1}, function() {
		$.post("ajax.php?t=break&a=change_break_percent", {anketa:srv_meta_anketa_id, break_percent:break_percent}, function() {
		breakSpremenljivkaChange();		
		});
	});
}

function change_break_charts (break_charts) {

	$.post("ajax.php?t=break&a=change_break_charts", {anketa:srv_meta_anketa_id, break_charts:break_charts}, function() {
		breakSpremenljivkaChange();		
	});
}

function doArchiveBreak() {
	//preverimo ali obstaja vsebina breakResults
	if ($("#breakResults").length > 0 && $("#breakResults").html() != '') {
		$("#fullscreen").load('ajax.php?a=doArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran}, function() {

			$('#fade').fadeTo('slow', 1);
			$('#fullscreen').show();
		});	
	} else {
		alert ('Ni podatkov za arhiv! Najprej kreirajte tabele.');
	}
}
function submitArchiveBreak() {
	//preverimo ali obstaja vsebina meansa
	if ($("#breakResults").html().length > 0 ) {
		var content = $("#breakResults").html();

		var name = $("#newAnalysisArchiveName").val();
		var note = $("#newAnalysisArchiveNote").val();
		var access = $("[name=newAnalysisArchiveAccess]:checked").val();
		var duration = $("#newAnalysisArchiveDuration").val();
		var durationType = $("[name=newAADurationType]:checked").val();
		$("#fullscreen").load('ajax.php?a=submitArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, name:name, note:note, access:access, duration:duration, durationType:durationType, content:content}, function() {
			$("#fullscreen").show();
		});
	} else {
		alert ('Ni podatkov za arhiv! Najprej kreirajte tabele.');
	}
}

function createArchiveBreakBeforeEmail() {
	//preverimo ali obstaja vsebina breakResults
	if ($("#breakResults").html().length > 0 ) {
		var content = $("#breakResults").html();
		$.post('ajax.php?a=createArchiveBeforeEmail', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, content:content}, function(response) {
			if (parseInt(response) > 0) {
				var aid = parseInt(response);
				$("#fullscreen").load('ajax.php?a=emailArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, aid:aid}, function() {
					$('#fullscreen').show();
				});
			} else {
				if (parseInt(response) == -1) {
					alert("Nothing to archive!"+response);
				} else {
					alert("Error while creating archive!"+response);
				}
				$('#fullscreen').hide();
				$('#fade').fadeOut('slow');
			}
		});

	} else {
		alert ('Ni podatkov za arhiv! Najprej kreirajte tabele.');
	}
};