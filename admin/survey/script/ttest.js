function ttest_init() {
}

function ttestSpremenljivkaChange() {
	// drugi dropdown
	var spr2 = $('#ttestSpremenljivka').val();
	var grid2 = $("#ttestSpremenljivka option:selected").attr('grid');
	var seq2 = $("#ttestSpremenljivka option:selected").attr('seq2');
	$("#ttestResults").html('');
	$("#ttestVariablesSpan").load("ajax.php?t=ttest&a=spremenljivkaChange", {anketa:srv_meta_anketa_id, spr2:spr2, seq2:seq2, grid2:grid2}, function() {
		$("#ttestVariablesSpan").fadeTo(100, 1);
	});
	// onemogočimo
	
	$('#ttestVariablesSpan').addClass('active');
	$('#ttestNumerus').attr('disabled',true);
	$('#ttestNumerusSpan').addClass('gray');
}
function ttestVariableChange() {
		// prvi dropdown
		var seq = new Array();
		var spr = new Array();
		var grd = new Array();
		$('#ttestNumerus').each(function(index,el) {
			spr.push($(el).val());
		});
		// omogočimo
		$('#ttestNumerus').attr('disabled',false).focus().attr('size', 1);
		$('#ttestNumerusSpan').removeClass('gray');
		
		$('#ttestNumerus option:selected').each(function(index,el) {
			seq.push($(el).attr("seq"));
			grd.push($(el).attr("grd"));
		});

		
		// drugi dropdown
		var spr2 = $('#ttestSpremenljivka').val();
		var grid2 = $("#ttestSpremenljivka option:selected").attr('grid');
		var seq2 = $("#ttestSpremenljivka option:selected").attr('seq2');
		var label2 = $("#ttestSpremenljivka option:selected").html();
		
		// variable iz checkboxov
		var sub_conditions = new Array();
		$('#ttestVariablesSpan input:checked').each(function(index,el) {
			sub_conditions.push($(el).val());
		});
		$("#ttestResults").load("ajax.php?t=ttest&a=variableChange", {anketa:srv_meta_anketa_id, sub_conditions:sub_conditions, spr:spr, seq:seq, grd:grd, spr2:spr2, seq2:seq2, grid2:grid2, label2:label2}, function() {
			// preverimo da samo dva izbrana checkboxa
			var cnt = $("input[name=subTtest]:checked").length;
			if (cnt == 2) {
				// onemogočimo ostale checkboxe
				$("input[name=subTtest]:not(:checked)").each(function(index,el) {
					$(this).attr("disabled", true);
					$(this).parent().addClass("gray");
				});
				$("#ttestVariablesSpan").removeClass('active');
				
			} else if (cnt < 2) {
				// onemogočimo
				$('#ttestNumerus').attr('disabled',true);
				$('#ttestNumerusSpan').addClass('gray');
				$("#ttestVariablesSpan").addClass('active');
				// omogočimo vse checkboxe
				$("input[name=subTtest]:disabled").each(function(index,el) {
					$(this).removeAttr("disabled");
					$(this).parent().removeClass("gray");
				});
				$("#ttestResults").html('');
			}
		});
}

function doArchiveTTest() {
	//preverimo ali obstaja vsebina crosstaba
	if ($("#ttestResults").length > 0 && $("#ttestResults").html() != '') {
		$("#fullscreen").load('ajax.php?a=doArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran}, function() {

			$('#fade').fadeTo('slow', 1);
			$('#fullscreen').show();
		});	
	} else {
		alert ('Ni podatkov za arhiv! Najprej kreirajte tabele.');
	}
}
function submitArchiveTTest() {
	//preverimo ali obstaja vsebina meansa
	if ($("#ttestResults").html().length > 0 ) {
		var content = $("#ttestResults").html();

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

function createArchiveTTestBeforeEmail() {
	//preverimo ali obstaja vsebina crosstaba
	if ($("#ttestResults").html().length > 0 ) {
		var content = $("#ttestResults").html();
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