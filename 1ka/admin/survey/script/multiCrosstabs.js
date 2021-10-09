/** Skripte potrebne za multi crosstabe (multi crosstabs - Analiza)
 */

function multiCrosstabs_init() {

	// draggable variable
	$('.draggable').draggable({
		helper: 'clone', 
		revert: 'invalid', 
		opacity:'1', 
		zIndex:'1', 
		delay:100,
		appendTo: 'body'
	});

	$("td.ct_inspect").live("click", function(event) {
		doInspectFromCrosstab(this,event);
		return false;
	});
	
	// urejanje inline naslova 
	$('div.multicrosstab_title_inline').live('focus', function (event) {		
		$(this).parent().addClass('writing');
		
	}).live('blur', function () {		
		$(this).parent().removeClass('writing');
		
		var table_id = $(this).parent().attr('id').substr(6);
		var value = $(this).html();;
		
		$.post('ajax.php?t=multicrosstabs&a=edit_title', {anketa: srv_meta_anketa_id, value:value, table_id:table_id});
	});	
	
	// Preklop med posameznimi tabelami
	$(".mc_tables").live('click', function(event) {
		var $target = $(event.target);
		if ($target.hasClass('option')) {
			var id = $target.attr('value');
				
			$("#div_mc_tables").load('ajax.php?t=multicrosstabs&a=mc_change_table', {anketa: srv_meta_anketa_id, id:id});
		}
	});
	
	// Dodajanje nove tabele (plusek)
	$("#mc_tables_plus").live("click", function (event) {
		mc_table_action('show_new');
	});
}

// nardimo folderje droppable
function createDroppable(){	
	$('.droppable').droppable({accept: '.draggable', hoverClass: 'drophover', tolerance: 'pointer', 
		drop: function (e, ui) {
			
			// nastavimo id tabele kamor spuscamo
			var table_id = $(this).closest('table').attr('id');
			
			// Nastavimo ali spuscamo v navpicno celico ali vodoravno
			var position = 0;
			if($(this).hasClass('vertical')){
				var position = 1;
			}
			
			// Nastavimo vrstni red parenta ce spuscamo na 2. nivo
			if($(this).attr('id') != 'undefined'){
				var parent = $(this).attr('id');
			}
			// Nastavimo vrstni red in parent ce spuscamo na 1. nivo
			else{
				var parent = '';
			}
			
			$('#mc_table_holder_'+table_id).load('ajax.php?t=multicrosstabs&a=add_variable', {anketa: srv_meta_anketa_id, table_id:table_id, spr:$(ui.draggable).attr('id'), parent:parent, position:position});
		}
	});
}


function deleteVariable(element){
	
	// nastavimo id tabele
	var table_id = $(element).closest('table').attr('id');
	
	// element ki ga brisemo
	var vrstni_red = $(element).closest('td').attr('id');
	
	// Nastavimo ali spuscamo v navpicno celico ali vodoravno
	var position = 0;
	if($(element).closest('td').hasClass('vertical')){
		var position = 1;
	}
	
	// Nastavimo vrstni red parenta
	var parent = $(element).closest('td').attr('parent');

	$('#mc_table_holder_'+table_id).load('ajax.php?t=multicrosstabs&a=remove_variable', {anketa: srv_meta_anketa_id, table_id:table_id, vrstni_red:vrstni_red, position:position, parent:parent});
}

function changeMCSettings(table_id, what){
	
	if(what == 'navVsEno'){
		var value = 1;
		value = $('input[name=navVsEno]:checked').val();
	}
	else{
		var value = 0;
		if($('#'+what+'_'+table_id).is(':checked')){
			value = 1;
		}
	}

	$('#mc_table_holder_'+table_id).load('ajax.php?t=multicrosstabs&a=change_settings', {anketa: srv_meta_anketa_id, table_id:table_id, what:what, value:value});
}


// Prikazemo popup z vsemi nastavitvami
function showMCSettings(table_id){

	$('#fade').fadeTo('slow', 1, function(){				
		$('#mc_table_settings').show();
	});
}

// Ugasnemo popup z vsemi nastavitvami
function closeMCSettings(table_id){
	
	$('#fade').fadeOut('slow');
	$('#mc_table_settings').hide();
}

// Shranimo nastavitve
function saveMCSettings(table_id){

	var form = $("form[name=mc_settings]").serializeArray();

	$('#mc_table_holder_'+table_id).load('ajax.php?t=multicrosstabs&a=save_settings', form, function(){
		$('#fade').fadeOut('slow');
		$('#mc_table_settings').hide();
	});
}

// Prikazemo/skrijemo dropdown za variablo (delez ali povprecje)
function toggleMCSetting(setting){
	$('#'+setting).toggle();
	
	if(setting == 'delezVar'){
		$('#delez').toggle();
	}
}

// Pri vklopu deleza prikazemo mozne vrednosti
function setDelez(delezVar){

	$('#delez').load('ajax.php?t=multicrosstabs&a=set_delez', {anketa: srv_meta_anketa_id, delezVar:delezVar});
}



function show_mc_tables(){
	$('#fade').fadeTo('slow', 1);
	
	$("#div_mc_tables").load('ajax.php?t=multicrosstabs&a=mc_show_tables', {anketa: srv_meta_anketa_id}, function(){
		$("#div_mc_tables").show(200);
	});	
	
	return false; // "capture" the click
}

function add_mc_table() {
	$('#fade').fadeTo('slow', 1);	
    $("#newMCTable").show();
}
function delete_mc_table() {
	$('#fade').fadeTo('slow', 1);	
    $("#deleteMCTable").show();
}

function close_mc_tables() {
    $("#div_mc_tables").fadeOut();
	$("#fade").fadeOut();
}
function use_mc_table() {

	var value = $(".mc_tables .active").attr('value');
	
	$.post('ajax.php?t=multicrosstabs&a=use_mc_table', {anketa:srv_meta_anketa_id, value:value}, function(){	
		/*var srv_site_url = $("#srv_site_url").val();
		srv_site_url += 'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=analysis&m=multicrosstabs';		
		window.location.href = srv_site_url;*/
		location.reload();
	});
}

// popravljamo ime tabele...
function mc_table_action(action){
	
	// Rename actions
	if (action == 'show_rename') {
		$("#dsp_cover_div").show();
		$("#renameMCTable").show();
		
		// Popravimo naslov izbranega porocila
		var title = $("#mc_tables").find('.active').html();
		$("#renameMCTableName").val(title);
	}
	else if(action == 'cancel_rename'){	
		$("#dsp_cover_div").hide();
		$("#renameMCTable").hide();
	}
	else if(action == 'rename'){	
		var id = $(".mc_tables .active").attr('value');
		var name = $("#renameMCTableName").val();

		$("#div_mc_tables").load('ajax.php?t=multicrosstabs&a=rename_table', {anketa:srv_meta_anketa_id, id:id, name:name}, function() {
			$("#renameMCTable").hide();
			$("#dsp_cover_div").fadeOut();
		});
	}
	
	// Delete actions
	else if(action == 'show_delete'){	
		if($("#div_mc_tables").is(':visible'))
			$("#dsp_cover_div").show();	
		else
			$('#fade').fadeTo('slow', 1);	

		$("#deleteMCTable").show();
		
		// Popravimo naslov izbranega porocila
		var title = $("#mc_tables").find('.active').html();
		$("#deleteMCTableName").html(title);
	}
	else if(action == 'cancel_delete'){		
		if($("#div_mc_tables").is(':visible')){
			$("#deleteMCTable").hide();
			$("#dsp_cover_div").hide();			
		}
		else{
			$("#deleteMCTable").hide();
			$("#fade").fadeOut();
		}
	}
	else if(action == 'delete'){		
		var id = $(".mc_tables .active").attr('value');
		
		$("#div_mc_tables").load('ajax.php?t=multicrosstabs&a=delete_table', {anketa:srv_meta_anketa_id, id:id}, function() {
			$("#deleteMCTable").hide();
			$("#dsp_cover_div").fadeOut();
		});
	}
	
	// Add actions
	else if(action == 'show_new'){		
		if($("#div_mc_tables").is(':visible'))
			$("#dsp_cover_div").show();		
		else
			$('#fade').fadeTo('slow', 1);	
		
		$("#newMCTable").show();
	}
	else if(action == 'cancel_new'){		
		if($("#div_mc_tables").is(':visible')){
			$("#newMCTable").hide();
			$("#dsp_cover_div").hide();
		}
		else{	
			$("#newMCTable").hide();
			$("#fade").fadeOut();
		}
	}
	else if(action == 'goto_archive'){
		$("#newMCTable").hide();
		show_mc_tables();
	}
	else if(action == 'new'){	
		var name = $("#newMCTableName").val();
		
		$.post('ajax.php?t=multicrosstabs&a=new_table', {anketa:srv_meta_anketa_id, name:name}, function() {		
			/*var srv_site_url = $("#srv_site_url").val();
			srv_site_url += 'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=analysis&m=analysis_creport';		
			window.location.href = srv_site_url;*/
			location.reload();
		});	
	}
}
