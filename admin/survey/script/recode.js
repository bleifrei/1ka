/**
* inicializacija za inline urejanje rekodiranja
*/
function onload_init_recode () {
	// urejanje naslova
	$("#rec_spremenljivka_naslov").live('focus', function() {
		if ($(this).attr('default') == '1')
			$(this).select();

	}).live('keypress', function (event) {
		enterKeyPressHandler(event);
		$(this).attr('default', '0');
	})
}
function showQuestionRecode(spr_id) {
	$('#fade').fadeTo('slow', 1);
	$("#question_recode").load("ajax.php?t=recode&a=showQuestionRecode", {anketa:srv_meta_anketa_id, spr_id:spr_id}).show();
}

function removeQuestionRecode(spr_id,confirmtext) {
    if (confirm(confirmtext)) {
		$.post("ajax.php?t=recode&a=removeQuestionRecode", {anketa:srv_meta_anketa_id, spr_id:spr_id}, function (response) {
			response = jQuery.parseJSON(response);
			if (response.spr_id > 0) {
				brisi_spremenljivko(response.spr_id,response.confirmtext);
//					alert(response.spr_id);
					// $.post("ajax.php?t=recode&a=removeSpremenljivka", {anketa:srv_meta_anketa_id, spr_id:response.spr_id});
					//  $.post('ajax.php?a=brisi_spremenljivko', {spremenljivka: response.spr_id, anketa: srv_meta_anketa_id});
			}
		window.location.reload();
		});
    }
  }
function cancelQuestionRecode() {
	$("#question_recode").fadeOut().html('');
	$('#fade').fadeOut('slow');
}

function saveQuestionRecode() {
	
	var form_serialize = $("form[name=spremenljivka_recode]").serialize() || {anketa:srv_meta_anketa_id};
	$('#fade').fadeOut('slow');
	$("#question_recode").hide();
	$.post("ajax.php?t=recode&a=saveQuestionRecode", form_serialize, function () {
		window.location.reload();
	});
}


function recode_add_numeric (spremenljivka) {
	var recode_type = $('input[name=recode_type]:checked').val();
	$.post('ajax.php?t=recode&a=add_new_numeric', {anketa: srv_meta_anketa_id, spremenljivka: spremenljivka, recode_type:recode_type}, function (data) {
		if (data) {
			if ( $('ul#recode_number_sort').length) {
				$('ul#recode_number_sort').append(data);
			} else {
				$('#question_recode').append(data);	
			}
			
			$('ul.vrednost_sort li:last-child textarea' ,'#question_recode').focus();
			$('#question_recode').attr({scrollTop: $('#question_recode').height()});
		} else {
			alert('Napaka x!');
		}
	});
	
}

function recode_delete_numeric (spremenljivka,what) {
	//alert($(what).parent().attr('class'));
	//$(what).parent().hide();
	 $(what).closest('li').remove();

}

function recode_number_type_changed(what) {
	var type = $(what).find('option:selected').val();
	var input_el = $(what).closest('li').find('input[name="recode_number_value[]"]');
	if (type == "_") {
		input_el.removeClass('hidden');
		input_el.show();
	} else {
		input_el.hide();
		input_el.addClass('hidden');
		input_el.val(type);
	}
}

function recode_operator_changed(what) {
	var type = $(what).find('option:selected').val();
	var span_el1 = $(what).closest('li').find('span.recode_int_first');
	var span_el2 = $(what).closest('li').find('span.recode_int_seccond');
	if (type == "6") {
		// ce imamo interval
		span_el1.addClass('hidden');
		span_el2.removeClass('hidden');
	} else {
		span_el1.removeClass('hidden');
		span_el2.addClass('hidden');
	}
}

function changeRecodeType() {
	var form_serialize = $("form[name=spremenljivka_recode]").serializeArray();
	if (form_serialize.length == 0) {
		form_serialize[form_serialize.length] ={name:'spr_id', value:$("input[name=spr_id]").val()};
		form_serialize[form_serialize.length] ={name:'recodeToSpr', value:$("input[name=recodeToSpr]").val()};
		form_serialize[form_serialize.length] ={name:'recode_type', value:$('input[name=recode_type]:checked').val()};

	}
	if ($("#recIsCharts").length) {
		form_serialize[form_serialize.length] ={name:'recIsCharts', value:$("#recIsCharts").val()};
	}
	
	form_serialize[form_serialize.length] = {name:'anketa', value:srv_meta_anketa_id}
	$("#recodeToNewSpr").load("ajax.php?t=recode&a=changeRecodeType", form_serialize);
}

function recodeSpremenljivkaNew() {
	var form_serialize = $("form[name=spremenljivka_recode]").serializeArray();
	
	if (form_serialize.length == 0) {
		form_serialize[form_serialize.length] ={name:'spr_id', value:$("input[name=spr_id]").val()};
		form_serialize[form_serialize.length] ={name:'recodeToSpr', value:$("input[name=recodeToSpr]").val()};
		form_serialize[form_serialize.length] ={name:'recode_type', value:$('input[name=recode_type]:checked').val()};
	}
	if ($("#recIsCharts").length) {
		form_serialize[form_serialize.length] ={name:'recIsCharts', value:$("#recIsCharts").val()};
	}
	form_serialize[form_serialize.length] = {name:'anketa', value:srv_meta_anketa_id}
	$("#recodeToNewSpr").load("ajax.php?t=recode&a=recodeSpremenljivkaNew", form_serialize);
}
function recodeVrednostNew() {
	var form_serialize = $("form[name=spremenljivka_recode]").serializeArray();
	
	if (form_serialize.length == 0) {
		form_serialize[form_serialize.length] ={name:'spr_id', value:$("input[name=spr_id]").val()};
		form_serialize[form_serialize.length] ={name:'recodeToSpr', value:$("input[name=recodeToSpr]").val()};
		form_serialize[form_serialize.length] ={name:'recode_type', value:$('input[name=recode_type]:checked').val()};

	}
	if ($("#recIsCharts").length) {
		form_serialize[form_serialize.length] ={name:'recIsCharts', value:$("#recIsCharts").val()};
	}
	form_serialize[form_serialize.length] = {name:'anketa', value:srv_meta_anketa_id}
	$("#recodeToNewSpr").load("ajax.php?t=recode&a=recodeVrednostNew", form_serialize);
}

function runRecodeVredonosti(what) {
	init_progressBar();
	
	$(what).load("ajax.php?t=recode&a=runRecodeVredonosti", {anketa:srv_meta_anketa_id});
}

function enableRecodeVariable(spr_id,what) {
	$(what).load("ajax.php?t=recode&a=enableRecodeVariable", {anketa:srv_meta_anketa_id,spr_id:spr_id});
	
}
function visibleRecodeVariable(spr_id,what) {
	$(what).load("ajax.php?t=recode&a=visibleRecodeVariable", {anketa:srv_meta_anketa_id,spr_id:spr_id});
}