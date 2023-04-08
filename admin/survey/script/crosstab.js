/** Skripte potrebne za Tabele (crosstabs - Analiza)
 */

function crosstab_init() {
	$("td.ct_inspect").live("click", function(event) {
		doInspectFromCrosstab(this,event);
		return false;
	});
}

function add_new_variable(which) {

	var sequence = new Array();
	var spr = new Array();
	var grid = new Array();

	if (which == '2' ) {
		//$('#crossRightHolder #crosstab_add_new').hide();
	} else {
		//$('#crossLeftHolder #crosstab_add_new').hide();
	}

	$('select[name=crosstab_variable_'+which+']').each(function(index,el) {
		sequence.push($(el).val());
	});
	$('select[name=crosstab_variable_'+which+'] option:selected').each(function(index,el) {
		spr.push($(el).attr("spr_id"));
		grid.push($(el).attr("grd_id"));
	});
	
	var crossNavVsEno = $('#crossNavVsEno1').is(':checked') ? '1' : '0';

	$.post("ajax.php?t=crosstab&a=add_new_variable", {anketa:srv_meta_anketa_id, which:which, sequence:sequence, spr:spr, grid:grid, crossNavVsEno:crossNavVsEno}, function(response) {
		if (which == '2' ) {
			$(response).appendTo('#crossRightHolder');
		} else {
			$(response).appendTo('#crossLeftHolder');
		}
	});
}

function crs_remove_variable(what) {
	
	$(what).parent().remove();
	if ( $(what).parent().find('select').val() > 0 ) {
		change_crosstab();
	}
}

function change_crosstab (action) {
	var sequence1 = new Array();
	var spr1 = new Array();
	var grid1 = new Array();
	var sequence2 = new Array();
	var spr2 = new Array();
	var grid2 = new Array();

	if (action == 'rotate') {
		// obrnemo dropdown variabli 
//		var sequence1 = $("#crosstab_variable_2").val();
		// polovimo še id spremenljivke od variable
//		var spr1 = $("#crosstab_variable_2 option:selected").attr("spr_id");
//		var grid1 = $("#crosstab_variable_2 option:selected").attr("grd_id");
//		var sequence2= $("#crosstab_variable_1").val();
//		var spr2 = $("#crosstab_variable_1 option:selected").attr("spr_id");
//		var grid2 = $("#crosstab_variable_1 option:selected").attr("grd_id");

		$('select[name=crosstab_variable_2]').each(function(index,el) {
			sequence1.push($(el).val());
		});
		$('select[name=crosstab_variable_2] option:selected').each(function(index,el) {
			spr1.push($(el).attr("spr_id"));
			grid1.push($(el).attr("grd_id"));
		});
		$('select[name=crosstab_variable_1]').each(function(index,el) {
			sequence2.push($(el).val());
		});
		$('select[name=crosstab_variable_1] option:selected').each(function(index,el) {
			spr2.push($(el).attr("spr_id"));
			grid2.push($(el).attr("grd_id"));
		});

	} else {
		
		// prebereomo dropdown variabli
//		var sequence1 = $("#crosstab_variable_1").val();
		// polovimo še id spremenljivke od variable
//		var spr1 = $("#crosstab_variable_2 option:selected").attr("spr_id");
//		var grid1 = $("#crosstab_variable_2 option:selected").attr("grd_id");
//		var sequence2= $("#crosstab_variable_2").val();
//		var spr2 = $("#crosstab_variable_2 option:selected").attr("spr_id");
//		var grid2 = $("#crosstab_variable_2 option:selected").attr("grd_id");
		$('select[name=crosstab_variable_1]').each(function(index,el) {
			sequence1.push($(el).val());
		});
		$('select[name=crosstab_variable_1] option:selected').each(function(index,el) {
			spr1.push($(el).attr("spr_id"));
			grid1.push($(el).attr("grd_id"));
		});
		$('select[name=crosstab_variable_2]').each(function(index,el) {
			sequence2.push($(el).val());
		});
		$('select[name=crosstab_variable_2] option:selected').each(function(index,el) {
			spr2.push($(el).attr("spr_id"));
			grid2.push($(el).attr("grd_id"));
		});
	}
	var crossNavVsEno = $('#crossNavVsEno1').is(':checked') ? '1' : '0';

	$("#crosstab_drobdowns").fadeTo(100, 0.2);
	$("#crosstab_table").fadeTo(100, 0.2);
	$("#crosstab_drobdowns").load("ajax.php?t=crosstab&a=changeDropdown", {anketa:srv_meta_anketa_id, sequence1:sequence1, sequence2:sequence2,
		spr1:spr1, spr2:spr2 , crossNavVsEno:crossNavVsEno, grid1:grid1, grid2:grid2}, function() {
			if (isNaN(spr1) && isNaN(spr2)) {
				$("#div_analiza_data").load("ajax.php?t=crosstab&a=change", {anketa:srv_meta_anketa_id, sequence1:sequence1, sequence2:sequence2,
					spr1:spr1, spr2:spr2 , crossNavVsEno:crossNavVsEno, grid1:grid1, grid2:grid2}, function() {
					});
			} else {
				$("#crosstab_drobdowns").fadeTo(100, 1);
				$("#crosstab_table").fadeTo(100, 1);

			}
		});
	}

function change_crosstab_cb () {
	$("#crosstab_drobdowns").fadeTo(100, 0.2);
	$("#crosstab_table").fadeTo(100, 0.2);
	
	
	// prebereomo dropdown variabli
	var sequence1 = $("#crosstab_variable_1").val();
	var sequence2= $("#crosstab_variable_2").val();
	// polovimo še id spremenljivke od variable
	var spr1 = $("#crosstab_variable_1 option:selected").attr("spr_id");
	var spr2 = $("#crosstab_variable_2 option:selected").attr("spr_id");
	
	if ($("#crosstab_variable_1 option:selected").attr("grd_id") !== undefined) {
		var grid1 = $("#crosstab_variable_1 option:selected").attr("grd_id");
	} else {
		var grid1 = '';
	}
	if ($("#crosstab_variable_2 option:selected").attr("grd_id") !== undefined) {
		var grid2 = $("#crosstab_variable_2 option:selected").attr("grd_id");
	} else {
		var grid2 = '';
	}
	

	var crossNavVsEno = $('#crossNavVsEno1').is(':checked') ? '1' : '0';

	$("#crosstab_table").load("ajax.php?t=crosstab&a=change_cb", {anketa:srv_meta_anketa_id, sequence1:sequence1, sequence2:sequence2, 
		//crossChk0:crossChk0, crossChk1:crossChk1, crossChk2:crossChk2, crossChk3:crossChk3,
		//crossChkEC:crossChkEC, crossChkRE:crossChkRE,crossChkSR:crossChkSR,crossChkAR:crossChkAR, doColor:doColor, 
		spr1:spr1, spr2:spr2, crossNavVsEno:crossNavVsEno, grid1:grid1, grid2:grid2 }, function () {
			$("#crosstab_drobdowns").fadeTo(100, 1);
			$("#crosstab_table").fadeTo(100, 1);
		});
}

function change_crosstab_percent () {
	var crossChk1 = $("#crossCheck1").is(':checked');
	$.post("ajax.php?t=crosstab&a=change_cb_percent", {anketa:srv_meta_anketa_id, crossChk1:crossChk1}, function() {
		change_crosstab();		
	});
}

function change_crosstab_color () {
	var doColor = $("#crossDoColor").is(':checked');
	if (doColor) {
		$("#span_color_residual_legend").show();
	} else {
		$("#span_color_residual_legend").hide();
	}
	$.post("ajax.php?t=crosstab&a=change_cb_color", {anketa:srv_meta_anketa_id, doColor:doColor}, function() {
		change_crosstab();		
	});
}
function doInspectFromCrosstab(el,event) {
	var k1 = $(el).attr('k1');
	var	k2 = $(el).attr('k2');
	var n1 = $(el).attr('n1');
	var n2 =  $(el).attr('n2');
	var v1 =  $(el).attr('v1');
	var v2 =  $(el).attr('v2');

	var sp1 =  $(el).parent().closest('table').attr('sp1');
	var sp2 =  $(el).parent().closest('table').attr('sp2');

	var sq1 =  $(el).parent().closest('table').attr('sq1');
	var sq2 =  $(el).parent().closest('table').attr('sq2');
	
	var gd1 =  $(el).parent().closest('table').attr('gd1');
	var gd2 =  $(el).parent().closest('table').attr('gd2');
	
//	$("#inspect").load("ajax.php?t=crosstab&a=prepareInspect", {anketa:srv_meta_anketa_id,k1:k1,k2:k2,n1:n1,n2:n2,v1:v1,v2:v2,sp1:sp1,sp2:sp2,sq1:sq1,sq2:sq2,gd1:gd1,gd2:gd2}, function(response) {
	$.post("ajax.php?t=crosstab&a=prepareInspect", {anketa:srv_meta_anketa_id,k1:k1,k2:k2,n1:n1,n2:n2,v1:v1,v2:v2,sp1:sp1,sp2:sp2,sq1:sq1,sq2:sq2,gd1:gd1,gd2:gd2, from_podstran:srv_meta_podstran}, function(response) {
		//window.open("index.php?anketa="+srv_meta_anketa_id+"&a=data", '_blank');
		window.location = "index.php?anketa="+srv_meta_anketa_id+response;//"&a=data";
	});

}

function doArchiveCrosstab() {
	//preverimo ali obstaja vsebina crosstaba
	if ($("#crosstab_table").length > 0 && $("#crosstab_table").html() != '') {
		$("#fullscreen").load('ajax.php?a=doArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran}, function() {

			$('#fade').fadeTo('slow', 1);
			$('#fullscreen').show();
		});	
	} else {
		alert ('Ni podatkov za arhiv! Najprej kreirajte tabele.');
	}
}
function createArchiveCrosstabBeforeEmail() {
	//preverimo ali obstaja vsebina crosstaba
	if ($("#crosstab_table").length > 0 && $("#crosstab_table").html() != '') {
		var content = $("#crosstab_table").html();
		//global replace
		var regex = new RegExp('ct_inspect', "g");
		content = content.replace(regex, '');

		//$("#fullscreen").load('ajax.php?a=submitArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, content:content, name:name, note:note, access:access, duration:duration, durationType:durationType}, function() {
		$.post('ajax.php?a=createArchiveCrosstabBeforeEmail', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, content:content}, function(response) {
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
}
function submitArchiveCrosstabs() {
	//preverimo ali obstaja vsebina crosstaba
	if ($("#crosstab_table").length > 0 && $("#crosstab_table").html() != '') {
		var content = $("#crosstab_table").html();
		//global replace
		var regex = new RegExp('ct_inspect', "g");
		content = content.replace(regex, '');

		var name = $("#newAnalysisArchiveName").val();
		var note = $("#newAnalysisArchiveNote").val();
		var access = $("[name=newAnalysisArchiveAccess]:checked").val();
		var duration = $("#newAnalysisArchiveDuration").val();
		var durationType = $("[name=newAADurationType]:checked").val();
		//$("#fullscreen").load('ajax.php?a=submitArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, content:content, name:name, note:note, access:access, duration:duration, durationType:durationType}, function() {
		$("#fullscreen").load('ajax.php?a=submitArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, name:name, note:note, access:access, duration:duration, durationType:durationType, content:content}, function() {
			$("#fullscreen").show();
		});

	
	} else {
		alert ('Ni podatkov za arhiv! Najprej kreirajte tabele.');
	}
}

function changeSessionInspect() {
	$("#spanSessionInspect").load("ajax.php?t=crosstab&a=changeSessionInspect", {anketa:srv_meta_anketa_id}, function() {
		change_crosstab();	
	});
}