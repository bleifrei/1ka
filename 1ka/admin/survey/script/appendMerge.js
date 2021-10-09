function append_submit (do_submit) {
	
	if ($("#inv_import_type1").is(":checked"))
	{
		$("#inv_recipients_upload_form").submit();
	}
	else
	{
		
		var form = $('form#inv_recipients_upload_form');
		
		if (do_submit == 1) {
			form.append('<input type="hidden" name="do" value="1">');
		}
		
		$.post('ajax.php?t=appendMerge&a=submit', form.serialize(), function (data) {
			
			$('#fade').fadeTo('slow', 1);
			$('#vrednost_edit').show().html(data);
			
		});
	}	
}

function append_submit_close () {
	
	$('#fade').fadeOut('slow');
	$('#vrednost_edit').hide().html('');
	
}

function append_change_import_type(action) {
	// spremenimo akcijo forme
	$('form#inv_recipients_upload_form').get(0).setAttribute('action', action); //this works

	
	var import_type = $('input[name=inv_import_type]:checked').val();
	
	if (import_type == 1) {
		$('#inv_import_file').show();
		$('#inv_import_list').hide();
	} else {
		$('#inv_import_list').show();
		$('#inv_import_file').hide();
	}
	
}

function append_refreshFieldsList() {
	var fields = '';
	var fields_id = '';
	var prefix = '';
	$('ul').children('li.inv_field_enabled').each(function(idx, elm) {
		fields = fields + prefix+$(elm).find('label').html();
		fields_id = fields_id + prefix+$(elm).attr('id');
		prefix = ',';
	});                               
	$("#inv_field_list.inv_type_0, #inv_field_list.inv_type_1").html(fields);
	if ($("#inv_recipients_upoad_fields").length > 0) {
		$("#inv_recipients_upoad_fields").val(fields_id);
	}
}

function append_prepare_add_recipients() {

	//tip izračunamo na podlagi obeh checkboxov
	// dodaj v anketo
	var chck1 = $("input#inv_recipients_add_type1").is(":checked");
	// shrani v seznam
	var chck2 = $("input#inv_recipients_add_type2").is(":checked");
	if (chck1 || chck2 ) {
		// vsaj en checkbox more bit obkljukan
		var type = 1;
		
		if (chck1 == true) {
			if (chck2 == true) {
				// dodamo in shranimo seznam
				type = 1;
			} else {
				// samo dodamo
				type = 0;
			}
			
		} else {
			// samo shranimo seznam
			type = 2
		}
		
		if (type == 1) {
			// prejemnike shranimo v seznam pred dodajanjem v bazo
			var recipients_list = $("#inv_recipients_list").val();
			var fields = [];
	
			$('ul').children('li.inv_field_enabled').each(function(idx, elm) {
				fields.push(elm.id);
			});                               
			
			var doAdd = '1';
			
			// kateri pid imamo da če dodajamo k obstoječmu ga kr izberemo
			var pid = $("#inv_import_list_profiles ol li.active").attr('pid');
			if (fields.length > 0) {
				if (recipients_list.length > 0) {
					$('#fade').fadeTo('slow', 1);
					$('#fullscreen').html('').fadeIn('slow');
					$("#fullscreen").load('ajax.php?t=invitations&a=get_profile_name', {anketa:srv_meta_anketa_id, recipients_list:recipients_list, fields:fields, noNavi:'true', doAdd:doAdd, pid:pid});
				} else {
					alert(lang['srv_invitation_note1']);
				}
			} else {
				alert(lang['srv_invitation_note2']);
			}
	
		} else if (type == 0) {
			// prejemnike samo dodamo v anketo in v začasni seznam
			append_add_recipients();
		} else if (type == 2) {
			//prejemnike samo dodamo v seznam
			inv_recipients_add_to_list()
		}
	} else {
		if (confirm('Niste izbrali akcije. Ali želite podatke dodati v anketo?')) {
			// prejemnike samo dodamo v anketo in v začasni seznam
			append_add_recipients();
		}
	}

}


function append_add_recipients(profile_id) {
	
	if (typeof profile_id === "undefined") {
		var pid = $("#inv_import_list_profiles ol li.active").attr('pid');
	} else {
		var pid = profile_id;
	}
	
	var recipients_list = $("#inv_recipients_list").val();
	var fields = [];
	$('#inv_field_container ul').children('li.inv_field_enabled').each(function(idx, elm) {
		fields.push(elm.id);
	});
	
	var merge;
	if ( $('#do_merge').length > 0 ) {
		merge = $('ul#merge input:checked').attr('id').slice(0, -6) ;
	} else {
		merge = -1;
	}
	
	if (fields.length > 0) {
		if (recipients_list.length > 0) {
			$("#anketa_edit").load('ajax.php?t=appendMerge&a=add_recipients', {anketa:srv_meta_anketa_id, recipients_list:recipients_list, fields:fields, merge:merge, pid:pid});
			// porihtamo še navigacijo - hardcoded
			$elm = $("ul.secondNavigation li.inv_ff_left_on");
			$elm.prev().find('a').removeClass('active');
			$elm.next().find('a').addClass('active');
			$elm.removeClass('inv_ff_left_on').addClass('inv_ff_right_on').next().next().addClass('inv_ff_left_on');
		} else {
			alert(lang['srv_invitation_note1']);
		}
	} else {
		alert(lang['srv_invitation_note2']);
	}

}

function merge_getItems () {
	
	var checkboxes = $('ul.connectedSortable li input:checked');
	
	var fields = [];
	
	var checked = $('ul#merge input:checked').attr('id');
	
	$('ul#merge').empty();
	
	checkboxes.each(function() {
		
		var id = $(this).closest('li').attr('id');
		var label = $(this).closest('li').find('label').text();
		
		var ch, cl;
		if (checked == id+'_radio') { ch=' checked'; cl='inv_field_enabled'; } else { cd=''; cl=''; }
		
		$('ul#merge').append('<li class="'+cl+'"><input type="radio" id="'+id+'_radio"'+ch+' name="merge" value="'+id+'" onclick="merge_labels();"><label style="display: inline;" for="'+id+'_radio">'+label+'</label></li>');
		
	});
	
}

function merge_labels() {
	var radios = $('ul#merge input');
	
	radios.each(function() {
		
		if ( $(this).is(":checked") ) {
			 $(this).closest('li').addClass('inv_field_enabled');
		} else {
			 $(this).closest('li').removeClass('inv_field_enabled');
		}
	
	});
	
}