function analiza_init () {
	var srv_site_url = $("#srv_site_url").val();
	// dodamo klike za odpiranje divov profilov (ker so z linki ble tezave)
	$("#link_filter_profile").live("click", function(event) {
		if (event.button != 0) { // wasn't the left button - ignore
			return true;
		}
		analiza_show_filter_profiles();
		return false; // "capture" the click
	});
	
/*
	$("#link_collect_data_setting").live("click", function(event) {
		if (event.button != 0) { // wasn't the left button - ignore
			return true;
		}
		show_collect_data_setting();
		return false; // "capture" the click
	});
*/
	$("#link_status_casi").live("click", function(event) {
		if (event.button != 0) { // wasn't the left button - ignore
			return true;
		}
		vnosi_show_status_casi();
		return false; // "capture" the click
	});

	// za stiri stopnje prikaza manjkajocih
	$(".anl_click_missing").live('click', function() { toggle_click_missing(this)});
	
	// za skrivanje pri treh stopnjah missingov
	$(".anl_click_missing_hide").live('click', function() {
		var variabla = $(this).attr('id').substr(21); // odrezemo prvih 21 znakov : single_missing_title_ 
		toggle_click_missing($('#click_missing_'+variabla))
	});

	$("#status_casi").live('click', function(event) {
		var $target = $(event.target);
		if ($target.hasClass('option')) {
			pid = $target.attr('value');
			vnosi_show_casi_data(pid);
		}
	});
	
	$('#div_analiza_single_var_close_button').live('click', function(event) {
		hideAnalizaSingleVarPopup();
	});
	
	// brisanje vnosa
	$('#dataTable td .delete_circle').live('click', function(event) {
    	
		// polovimo user id
		var usr_id = $(this).parent().parent().find('.data_uid').html();
		var row = $(this).parent().parent();
		
		var note = 'srv_ask_delete';
	
		// Preverimo ce je med njimi tudi kaksno vabilo - dodatno opozorilo
		if($(this).parent().parent().find('.invitation_cell').length == 1){
			note = 'srv_ask_delete_inv';
		}
		
		$.post('ajax.php?a=outputLanguageNote', {note: note}, function(lang_note) {
			// Povprašamo uporabnika ali je ziher
			if (confirm(lang_note)) {
				if (usr_id > 0) {
					
					$.post('ajax.php?a=dataDeleteRow', {anketa: srv_meta_anketa_id, usr_id:usr_id }, function(response) {
						if (response == '0') {
							row.hide();
						} else {
							genericAlertPopup('alert_parameter_response',response);
						}
					});
				} else {
					genericAlertPopup('alert_delete_error');
				}
		    }
		});
	});
	// editiranje starega vnosa
	$('#dataTable td .edit_square').live('click', function(event) {
		// polovimo user id
		var uid = $(this).parent().parent().find('.data_uid').html();
		var href = srv_site_url+'main/survey/edit_anketa.php?anketa='+srv_meta_anketa_hash+'&usr_id='+uid+'';
		if (uid > 0 ){
			window.open(href, '_blank');
		}
		
	});
	// hitro editiranje starega vnosa
	$('#dataTable td .edit').live('click', function(event) {
		// polovimo user id
		var uid = $(this).parent().parent().find('.data_uid').html();
		var href = srv_site_url+'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=data&m=quick_edit&usr_id='+uid+'&quick_view=0';
		if (uid > 0 ){
			window.location = href;
		}
		
	});
	// pdf vprašalnika
	$('#dataTable td .pdf').live('click', function(event) {
    	// polovimo user id
		var uid = $(this).parent().parent().find('.data_uid').html();

		if (uid > 0 ){
			var href = srv_site_url+'admin/survey/izvoz.php?a=pdf_results&anketa='+srv_meta_anketa_id+'&usr_id='+uid;
			$.post('ajax.php?a=makeEncodedIzvozUrlString', {anketa: srv_meta_anketa_id, string:href}, function(url) {
				window.open(url,'_blank');	
			});
		}		
	});
	// rtf vprašalnika
	$('#dataTable td .rtf').live('click', function(event) {
    	// polovimo user id
		var uid = $(this).parent().parent().find('.data_uid').html();
					
		if (uid > 0 ){
			var href = srv_site_url+'admin/survey/izvoz.php?a=rtf_results&anketa='+srv_meta_anketa_id+'&usr_id='+uid;
			$.post('ajax.php?a=makeEncodedIzvozUrlString', {anketa: srv_meta_anketa_id, string:href}, function(url) {
				window.open(url,'_blank');	
			});
		}			
	});
	// evoli
	$('#dataTable td .evoli').live('click', function(event) {
    	// polovimo user id
		var uid = $(this).parent().parent().find('.data_uid').html();
					
		if (uid > 0){
			/*var href = srv_site_url+'admin/survey/izvoz.php?a=evoli_results&anketa='+srv_meta_anketa_id+'&usr_id='+uid;
			$.post('ajax.php?a=makeEncodedIzvozUrlString', {anketa: srv_meta_anketa_id, string:href}, function(url) {
				window.open(url,'_blank');	
			});*/
			var url = srv_site_url+'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=data&m=evoli&usr_id='+uid;
			window.open(url, '_blank');
		}			
	});
	$('#dataTable td .evoli2').live('click', function(event) {
    	// polovimo user id
		var uid = $(this).parent().parent().find('.data_uid').html();
					
		if (uid > 0){
			var url = srv_site_url+'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=data&m=evoli&lang=dan&usr_id='+uid;
			window.open(url, '_blank');
		}			
	});
	$('#dataTable td .evoli3').live('click', function(event) {
    	// polovimo user id
		var uid = $(this).parent().parent().find('.data_uid').html();
					
		if (uid > 0){
			var url = srv_site_url+'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=data&m=evoli&lang=slo&usr_id='+uid;
			window.open(url, '_blank');
		}			
    });
    // evoli EM
	$('#dataTable td .evoliEM').live('click', function(event) {
    	// polovimo user id
		var uid = $(this).parent().parent().find('.data_uid').html();
					
		if (uid > 0){
			var url = srv_site_url+'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=data&m=evoli_employmeter&usr_id='+uid;
			window.open(url, '_blank');
		}			
	});
	$('#dataTable td .evoliEM2').live('click', function(event) {
    	// polovimo user id
		var uid = $(this).parent().parent().find('.data_uid').html();
					
		if (uid > 0){
			var url = srv_site_url+'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=data&m=evoli_employmeter&lang=dan&usr_id='+uid;
			window.open(url, '_blank');
		}			
	});
	$('#dataTable td .evoliEM3').live('click', function(event) {
    	// polovimo user id
		var uid = $(this).parent().parent().find('.data_uid').html();
					
		if (uid > 0){
			var url = srv_site_url+'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=data&m=evoli_employmeter&lang=slo&usr_id='+uid;
			window.open(url, '_blank');
		}			
	});
	
	// mfdps
	$('#dataTable td .mfdps').live('click', function(event) {
    	// polovimo user id
		var uid = $(this).parent().parent().find('.data_uid').html();
					
		if (uid > 0){
			var url = srv_site_url+'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=data&m=mfdps&usr_id='+uid;
			window.open(url, '_blank');
		}			
	});
	
	// borza
	$('#dataTable td .borza').live('click', function(event) {
    	// polovimo user id
		var uid = $(this).parent().parent().find('.data_uid').html();
					
		if (uid > 0){
			var url = srv_site_url+'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=data&m=borza&usr_id='+uid;
			window.open(url, '_blank');
		}			
	});
	
	$("#span_rsdl_legend_togle").live("click", function(event) {
		$("#span_color_residual_legend1").toggle();
		$("#span_color_residual_legend2").toggle();
	});
	
	/*
	$(document).ready(function() {

		  $('#dataTable tbody tr:odd').addClass('odd');

		  $('#dataTable tbody tr:even').addClass('even');

		});
	*/
	// inspect za frekvence
	$("td.fr_inspect").live("click", function(event) {
		doInspectFromFrequency(this,event);
		return false;
	});
	// inspect za multi grid
	$("td.mg_inspect").live("click", function(event) {
		doInspectMultiGrid(this,event);
		return false;
	});
	// inspect za doublemulti grid
	$("td.dmg_inspect").live("click", function(event) {
		doInspectDoubleMultiGrid(this,event);
		return false;
	});
	// inspect za multi check
	$("td.mc_inspect").live("click", function(event) {
		doInspectMultiCheck(this,event);
		return false;
	});
	// inspect za multi text
	$("td.mt_inspect").live("click", function(event) {
		doInspectMultiText(this,event);
		return false;
	});
	
	$('.div_analiza_holder').live({
		mouseenter: function(){
			$(this).find('.div_analiza_icons').stop().animate({opacity:1},  600);
			$(this).find('.div_analiza_scale').stop().animate({opacity:1},  600);
		},
		mouseleave: function(){
			$(this).find('.div_analiza_icons').stop().animate({opacity:0},  600);
			$(this).find('.div_analiza_scale').stop().animate({opacity:0},  600);
		}
	});
	
	// prikazovanje gumbov za hitro skrolanje levo desno v podatkih
	$('#tableContainer').live(
	{
		mouseenter: function()
		{
			var windowWidth = $(window).width(); //retrieve current window width
			var dtWidth = $('#dataTable').outerWidth();
			if ( (dtWidth - windowWidth) > 0)
			{
				$(this).find('#dataTableScroller').stop().animate({opacity:1},  600);
			}
		},
		mouseleave: function()
		{
			var windowWidth = $(window).width(); //retrieve current window width
			var dtWidth = $('#dataTable').outerWidth();
			if ( (dtWidth - windowWidth) > 0)
			{
				$(this).find('#dataTableScroller').stop().animate({opacity:0},  600);
			}
		}
	});
};

/**
 * Funkcije za case
 */

function statusCasiAction(action) {
	$("#loading").show();
	if (action == 'change') {
		pid = $("#vnosi_current_status_casi").val();
		$.post('ajax.php?a=vnosi_change_status_casi', {anketa: srv_meta_anketa_id, pid:pid}, function() {
			return reloadData();
		});
	} else if (action == 'run') {
		$("#loading").show();
		pid = $("#status_casi .active").attr('value');
		// polovimo statuse
		var statusCnt = 0;
		var srv_userstatus = "";
		prefix="";
		$("input[name^=srv_userstatus]:checked").each(function() {
			srv_userstatus = srv_userstatus + prefix + $(this).attr('id');
			prefix = ",";
			statusCnt=statusCnt+1;
		});

		$.post('ajax.php?a=vnosi_run_status_casi', {anketa: srv_meta_anketa_id, pid:pid, status:srv_userstatus}, function() {
			// dropdownu izberemo profil
			$("#vnosi_current_status_casi").val(pid);
			// osvezimo vnose
			return reloadData();
		});
		// skrijemo vse dive
		$("#div_status_values").hide(200);
		//$('#fade').fadeOut('slow');
		$("#div_status_values").html('');
	} else if (action == 'run_rezanje') {
		$("#loading").show();
		var rezanje = $("input[name=rezanje]:checked").val();
		var rezanje_meja_sp = $("select[name=rezanje_meja_sp]").val();
		var rezanje_meja_zg = $("select[name=rezanje_meja_zg]").val();
		var rezanje_predvidena_sp = $("select[name=rezanje_predvidena_sp]").val();
		var rezanje_predvidena_zg = $("select[name=rezanje_predvidena_zg]").val();
		var rezanje_preskocene = 0;
		if ($("input[name=rezanje_preskocene]:checked").length > 0)
			rezanje_preskocene = 1;
		
		$.post('ajax.php?a=vnosi_run_rezanje_casi', {anketa: srv_meta_anketa_id, rezanje:rezanje, rezanje_meja_sp:rezanje_meja_sp, rezanje_meja_zg:rezanje_meja_zg, rezanje_predvidena_sp:rezanje_predvidena_sp, rezanje_predvidena_zg:rezanje_predvidena_zg, rezanje_preskocene:rezanje_preskocene}, function() {
			// osvezimo vnose
			return reloadData();
		});
		$("#div_status_values").hide(200);
		//$('#fade').fadeOut('slow');
		$("#div_status_values").html('');
	} else if (action == 'cancle') {
		$('#loading').hide();
		$("#div_status_values").hide(200);
		$('#fade').fadeOut('slow');
		$("#div_status_values").html('');
	} else if (action == 'newName') { // dodelimo novo ime profilu
		$("#statusCasiCoverDiv").show();
		$("#newProfile").show();
	} else if (action == 'newSave') { // shranimo kot nov profil in pozenemo
		$("#loading").show();
		pid = $("#status_casi .active").attr('value');
		name = $("#newProfileName").val();
		$("#newProfile").hide();
		$("#statusProfileCoverDiv").fadeOut();
		// polovimo statuse
		var statusCnt = 0;
		var srv_userstatus = "";
		prefix="";
		$("input[name^=srv_userstatus]:checked").each(function() {
			srv_userstatus = srv_userstatus + prefix + $(this).attr('id');
			prefix = ",";
			statusCnt=statusCnt+1;
		});
		$.post('ajax.php?a=vnosi_save_status_casi', {anketa: srv_meta_anketa_id, pid:pid, name:name, status:srv_userstatus}, function(newId) {
			// dropdownu dodamo nov prodil in ga izberemo
			$("#vnosi_current_status_casi").append($("<option></option>").attr("value",newId).attr("selected",true).text(name));
			// osvezimo vnose
			return reloadData();
		});
		// skrijemo vse dive
		$("#div_status_values").hide(200);
		$('#fade').fadeOut('slow');
		$("#div_status_values").html('');
	} else if (action == 'deleteAsk') { // vprašamo po potrditvi
		$("#statusProfileCoverDiv").show();
		$("#deleteProfileDiv").show();
	
	} else if (action == 'deleteCancle') { // prekicemo brisanje
		$("#deleteProfileDiv").hide();
		$("#statusProfileCoverDiv").fadeOut();
	} else if (action == 'deleteConfirm') { // izbrisemo profil
		$("#loading").show();
		pid = $("#status_casi .active").attr('value');
		$.post('ajax.php?a=vnosi_delete_status_casi', {anketa: srv_meta_anketa_id, pid:pid}, function() {
			// dropdownu izberemo profil
			$("#vnosi_current_status_casi").val('1');
			// osvezimo vnose
			return reloadData();
		});
		$("#deleteProfileDiv").hide();
		$("#statusProfileCoverDiv").fadeOut();
		$("#div_status_values").hide(200);
		$('#fade').fadeOut('slow');
		$("#div_status_values").html('');
	}
}

//prikaže skrit div za nastavitev statusov pri casih
function vnosi_show_status_casi()
{
	$('#fade').fadeTo('slow', 1);

	// poiščemo center strani
	$("#div_status_values").load('ajax.php?a=vnosi_show_status_casi', {anketa: srv_meta_anketa_id});
	var msg = $('#div_status_values');
    var height = $(window).height();
    var width = $(document).width();
	var left = width  - (msg.width() )-42;
	var top = height/2 - (msg.height() / 2);
	// pozicioniramo na center strani
	$("#div_status_values").show(200).draggable({delay:100, cancel: 'input, .buttonwrapper, .select'});
}

function vnosi_show_casi_data(pid) {
	$("#div_status_values").load('ajax.php?a=vnosi_show_status_casi', {anketa: srv_meta_anketa_id, pid:pid});
}

//prikaže skrit div za nastavitev rezanja pri casih
function vnosi_show_rezanje_casi()
{
	$('#fade').fadeTo('slow', 1);

	// poiščemo center strani
	$("#div_status_values").load('ajax.php?a=vnosi_show_rezanje_casi', {anketa: srv_meta_anketa_id});
	var msg = $('#div_status_values');
    var height = $(window).height();
    var width = $(document).width();
	var left = width  - (msg.width() )-42;
	var top = height/2 - (msg.height() / 2);
	// pozicioniramo na center strani
	$("#div_status_values").show(200).draggable({delay:100, cancel: 'input, .buttonwrapper, select'});
}
// prikaže skrit div za nastavitev manjkajočih vrednosti


// nastavitve manjkajocih vrednosti
function changeViewMissingProfile(_profileId){
	var _pid = _profileId.split('missing_profile_');
	var profileId = _pid[1];
	// samo posodobimo vsebino okna 
	$("#div_analiza_missing_values").load('ajax.php?a=analiza_changeViewMissingProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, profileId:profileId });
}

function changeMissingProfileDropdown(){	
	var profileId = $("#analiza_current_missing_profile").val();
	$.post('ajax.php?a=changeMissingProfileDropdown', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, profileId:profileId }, function() {
		$("#missingi").load('ajax.php?a=analiza_update_missing_checkbox', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, profileId:profileId }, function() {
			return reloadData();
		});
	});
}

function reloadData(subwindow) {
	window.location.reload();
	return false;
}

function analizaMissingProfileDropdownReloadData(){	
	$("#div_analiza_missing_profile_dropdown").load('ajax.php?a=analizaMissingProfileDropdownReloadData', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran});
}

// prikaže div z nastavitvami za analize
function analiza_show_settings(visible) {
	if (visible == 'true') { 
		//getter
		$("#div_analiza_missing_values").show().draggable({ delay: 500 , cancel: 'input, .buttonwrapper'});
	}
	else {
		$("#div_analiza_missing_values").hide();
	}
}

function showspremenljivkaSingleVarPopup(id) {
	var size = "location=0,height=500,width=700,scrollbars=1,fullscreen=0,menubar=0,status=0,titlebar=0,toolbar=0,channelmode=0,directories=0";
	var recipe =  window.open('','RecipeWindow',size);
	
	$.post('ajax.php?t=analysis&a=showspremenljivkaSingleVarPopup&setSkipCreate=1', {anketa: srv_meta_anketa_id,  id:id}, function(response) {
		recipe.document.open();
		recipe.document.write(response);
		recipe.document.close();
		recipe.focus();
		return false;    
	});
}

function showSpremenljivkaTextAnswersPopup(id,seq) {
	var size = "location=0,height=500,width=700,scrollbars=1,fullscreen=0,menubar=0,status=0,titlebar=0,toolbar=0,channelmode=0,directories=0";
	var recipe =  window.open('','RecipeWindow',size);
	
	$.post('ajax.php?t=analysis&a=showSpremenljivkaTextAnswersPopup&setSkipCreate=1', {anketa: srv_meta_anketa_id,  id:id,seq:seq}, function(response) {
		recipe.document.open();
		recipe.document.write(response);
		recipe.document.close();
		recipe.focus();
		return false;    
	});
}

function showAnalizaSingleVarPopup(id,zaPodstran,navedbe,loop) {
	var size = "location=0,height=500,width=700,scrollbars=1,fullscreen=0,menubar=0,status=0,titlebar=0,toolbar=0,channelmode=0,directories=0";
	var recipe =  window.open('','RecipeWindow',size);
	
	$.post('ajax.php?t=analysis&a=showAnalizaSingleVarPopup&setSkipCreate=1', {anketa: srv_meta_anketa_id, podstran: zaPodstran, zaPodstran:zaPodstran, id:id,navedbe:navedbe,loop:loop}, function(response) {
	    recipe.document.open();
	    recipe.document.write(response);
	    recipe.document.close();
	    recipe.focus();
	    return false;    
	});
	/*
	$("#fullscreen").load('ajax.php?t=analysis&a=showAnalizaSingleVar', {anketa: srv_meta_anketa_id, podstran: zaPodstran, zaPodstran:zaPodstran, id:id}, function() {
		$('#fullscreen').show();
		$('#fade').fadeTo('slow', 1);
	}).draggable({handle: '#div_analiza_single_var_close, #div_analiza_single_var' });
	*/
}

function showAnalizaSingleChartPopup(id,zaPodstran) {
	var size = "location=0,height=550,width=830,scrollbars=1,fullscreen=0,menubar=0,status=0,titlebar=0,toolbar=0,channelmode=0,directories=0";
	var recipe =  window.open('','RecipeWindow',size);
	
	$.post('ajax.php?t=analysis&a=showAnalizaSingleVarPopup&setSkipCreate=1', {anketa: srv_meta_anketa_id, podstran: zaPodstran, zaPodstran:zaPodstran, id:id}, function(response) {
	    recipe.document.open();
	    recipe.document.write(response);
	    recipe.document.close();
	    recipe.focus();
	    return false;    
	});
}

function printPreviewSingleVar(id,auto) {
	
	var size = "location=0,height=700,width=700,scrollbars=1,fullscreen=0,menubar=0,status=0,titlebar=0,toolbar=0,channelmode=0,directories=0";
	var recipe =  window.open('','RecipeWindow',size);
	$.post('ajax.php?t=analysis&a=printPreview_spremenljivka&setSkipCreate=1', {anketa: srv_meta_anketa_id, id:id}, function(response) {
	    recipe.document.open();
	    recipe.document.write(response);
	    recipe.document.close();
	    recipe.focus();
	    return false;    
	});

}

function hideAnalizaSingleVarPopup() {
	$('#fade').fadeOut('slow');
	$('#fullscreen').fadeOut('slow').html('');
}

function show_single_missing(id, what) {
	// 
	if (what == 0) {
		$('tr[name=missing_detail_'+id+']').each(function() {$(this).removeClass('displayNone');});
		$("#single_missing_0"+id).hide();
		$("#single_missing_1"+id).show();
		$("#single_missing_suma_"+id).show();
		$("#single_missing_suma_freq_"+id).show();
		$("#single_missing_percent_"+id).hide();
		$("#single_missing_title_"+id).hide();

	} else {
		$('tr[name=missing_detail_'+id+']').each(function() {$(this).addClass('displayNone');});
		$("#single_missing_0"+id).show();
		$("#single_missing_1"+id).hide();
		$("#single_missing_suma_"+id).hide();
		$("#single_missing_suma_freq_"+id).hide();
		$("#single_missing_percent_"+id).show();
		$("#single_missing_title_"+id).show();
		
	}
}

function show_single_other(id, what) {
	if (what == 0) {
		$('tr[name=other_detail_'+id+']').each(function() {$(this).removeClass('displayNone');});
		$("#single_other_suma_"+id).show();
		$("#single_other_suma_freq_"+id).show();
		$("#single_other_freq_"+id).addClass('silver');
		$("#single_other_perc_"+id).addClass('silver');
		$("#single_other_gray_suma_"+id).hide();
		$("#single_other_0"+id).hide();
		$("#single_other_1"+id).show();
	} else {
		$('tr[name=other_detail_'+id+']').each(function() {$(this).addClass('displayNone');});
		$("#single_other_suma_"+id).hide();
		$("#single_other_suma_freq_"+id).hide();
		$("#single_other_freq_"+id).removeClass('silver');
		$("#single_other_perc_"+id).removeClass('silver');
		$("#single_other_0"+id).show();			
		$("#single_other_1"+id).hide();			
	}
}


//-- stvari za filter profileso skupaj tule spodaj


// prikaze skrit div za nastavitev profilov filtriranja
function analiza_show_filter_profiles()
{
    $('#fade').fadeTo('slow', 1);

    // poiscemo center strani
    $("#div_analiza_filter_profiles").html("");
    $("#div_analiza_filter_profiles").load('ajax.php?a=analiza_loadFilterProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran});
    var msg = $('#div_analiza_filter_profiles');
    var height = $(window).height();
    var width = $(document).width();
    var left = width  - (msg.width() )-42;
    var top = height/2 - (msg.height() / 2);
    // pozicioniramo na center strani
    $("#div_analiza_filter_profiles").show(200).draggable({delay:100, cancel: 'input, .buttonwrapper'});
    
}


// dropdown za filter profile
function changeFilterProfileDropdown(){    
    var profileId = $("#analiza_current_filter_profile").val();
    $.post('ajax.php?a=changeFilterProfileDropdown', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, profileId:profileId }, function() {
    	return reloadData();
    });
}

// nastavitve filtrov
function changeViewFilterProfile(_profileId){
    var _pid = _profileId.split('filter_profile_');
    var profileId = _pid[1];
    // samo posodobimo vsebino okna 
    $("#div_analiza_filter_profiles").load('ajax.php?a=analiza_changeViewFilterProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, profileId:profileId });
}

function hideFilterProfile() {
    $('#fade').fadeOut('slow');
    $("#div_analiza_filter_profiles").hide("slow");
}

function analiza_runFilterProfile() {
    var _pid = $(".option.active").attr("id").split('filter_profile_');
    var pid = _pid[1];
    
    $.post('ajax.php?a=analiza_runFilterProfile', {anketa: srv_meta_anketa_id, profileId:pid}, function() {
        return reloadData();
    });

}

/*function showHideNewFilterProfile(showhide) {
    if (showhide=='true') {
        //$("#filterProfileCoverDiv").show();
        $("#newFilterProfile").show();
    }
    else {
        //$("#filterProfileCoverDiv").hide();
        $("#newFilterProfile").hide();
    }
}*/

function createFilterProfile() {
    var profileName = $("#newFilterProfileName").val();
       
    $.post('ajax.php?a=analiza_createFilterProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, profileName:profileName }, function() {
        $("#div_analiza_filter_profiles").load('ajax.php?a=analiza_loadFilterProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran}, function(){
        	return reloadData();
        });
    });
}

function analizaFilterProfileDropdownReloadData() {
    if (__vnosi == 0)
        $("#div_analiza_filter_profile_dropdown").load('ajax.php?a=analizaFilterProfileDropdownReloadData', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran});
    else
        location.reload();
}

function deleteFilterProfile() {
    var profileId = $("#deleteFilterProfileId").val();
    $.post('ajax.php?a=analiza_deleteFilterProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, profileId:profileId}, function () {
        $("#div_analiza_filter_profiles").load('ajax.php?a=analiza_loadFilterProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran});
        if (__vnosi == 0)
            analizaFilterProfileDropdownReloadData();
    });
}

function renameFilterProfile() {
    var newProfileName = $("#renameFilterProfileName").val();
    var profileId = $("#renameFilterProfileId").val();
    $.post('ajax.php?a=analiza_renameFilterProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, newProfileName: newProfileName, profileId:profileId}, function() {
        if (__vnosi == 0)
            analizaFilterProfileDropdownReloadData();
        $("#div_analiza_filter_profiles").load('ajax.php?a=analiza_loadFilterProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran});
    });
}

/*function showHideRenameFilterProfile(showhide) {
    if (showhide=='true') {
        //$("#filterProfileCoverDiv").show();
        $("#renameFilterProfileDiv").show();
    }
    else {
        //$("#filterProfileCoverDiv").hide();
        $("#renameFilterProfileDiv").hide();
    }
}
function showHideDeleteFilterProfile(showhide) {
    if (showhide=='true') {
        //$("#filterProfileCoverDiv").show();
        $("#deleteFilterProfileDiv").show();
    }
    else {
        //$("#filterProfileCoverDiv").hide();
        $("#deleteFilterProfileDiv").hide();
    }
}*/

function toggle_click_missing(what) {
	
	var variabla = $(what).attr('id').substr(14); // odrezemo prvih 14 znakov : click_missing_

	var state = $(what).attr('value');
	if (state === '') {
		state = 1;
	}
	
	if (state == 0) {
		// odstranimo spodnjo polno črto, ostane rdeca crtkana
		$("#anl_click_missing_tr_"+variabla).removeClass('anl_bb');
		$("#anl_click_missing_tr_"+variabla).addClass('anl_dash_red_bb');
		// nič od missingov še ni vidno. prikažemo osnovne missinge in skupo sumo
		$("#click_missing_1_"+variabla).show();
		$("#click_missing_suma_"+variabla).show();
		$(what).attr('value',1);
		// skrijemo osnovni link za vklop manjkajocih (manjkajoci)
		$("#click_missing_"+variabla).hide();
		$("#single_missing_title_"+variabla).show();
	} else if (state == 1 || state == 2 || state == ""){
		// dodamo spodnjo polno črto, skrijemo rdeco crtkana
		$("#anl_click_missing_tr_"+variabla).addClass('anl_bb');
		$("#anl_click_missing_tr_"+variabla).removeClass('anl_dash_red_bb');
		// nič od missingov še ni vidno. prikažemo osnovne missinge in skupo sumo
		$("#click_missing_1_"+variabla).hide();
		$("#click_missing_suma_"+variabla).hide();
		$(what).attr('value',0);
		$("#click_missing_"+variabla).show();
		$("#single_missing_title_"+variabla).hide();
	}
}

function show_single_percent(id,status) {

	if (status == 0) {
		// pokazemo frekvence in skrijemo procente
		$('[name=single_sums_percent_'+id+']').each(function() {$(this).hide();});
		$('[name=single_sums_percent_cnt_'+id+']').each(function() {$(this).show();});
		$('[name=single_sums_percent_cnt_'+id+']').removeClass('anl_dash_bb');

		// popravimo css link-e
		$("#img_analysis_f_p_1_"+id+", #img_analysis_f_p_2_"+id).addClass("displayNone");
		$("#img_analysis_f_1_"+id+", #img_analysis_f_2_"+id).removeClass("displayNone");
		$("#img_analysis_p_1_"+id+", #img_analysis_p_2_"+id).addClass("displayNone");
		
	} else if (status == 1) {
		// pokazemo oboje
		$('[name=single_sums_percent_cnt_'+id+']').addClass('anl_dash_bb');
		$('[name=single_sums_percent_'+id+']').each(function() {$(this).show();});
		$('[name=single_sums_percent_cnt_'+id+']').each(function() {$(this).show();});

		// popravimo css link-e
		$("#img_analysis_f_p_1_"+id+", #img_analysis_f_p_2_"+id).removeClass("displayNone");
		$("#img_analysis_f_1_"+id+", #img_analysis_f_2_"+id).addClass("displayNone");
		$("#img_analysis_p_1_"+id+", #img_analysis_p_2_"+id).addClass("displayNone");

		// skrijemo še vrednosti v vrstici z procenti
		$("#span_do_hide_1"+id).hide();
		$("#span_do_hide_2"+id).hide();
		$("#span_do_hide_3"+id).hide();
		$("#span_do_hide_4"+id).hide();
		$("#span_do_hide_5"+id).hide();
	
	} else {
		$('[name=single_sums_percent_cnt_'+id+']').removeClass('anl_dash_bb');
		// pokazemo procente skrijemo frekvence
		$('[name=single_sums_percent_'+id+']').each(function() {$(this).show();});
		$('[name=single_sums_percent_cnt_'+id+']').each(function() {$(this).hide();});

		// popravimo css link-e
		$("#img_analysis_f_p_1_"+id+", #img_analysis_f_p_2_"+id).addClass("displayNone");
		$("#img_analysis_f_1_"+id+", #img_analysis_f_2_"+id).addClass("displayNone");
		$("#img_analysis_p_1_"+id+", #img_analysis_p_2_"+id).removeClass("displayNone");
		
		// skrijemo še vrednosti v vrstici z procenti
		$("#span_do_hide_1"+id).show();
		$("#span_do_hide_2"+id).show();
		$("#span_do_hide_3"+id).show();
		$("#span_do_hide_4"+id).show();
		$("#span_do_hide_5"+id).show();

	}
}

function printAnaliza(ime)
{  
  if(ime == 'Crosstab')
	var divToPrint=document.getElementById('crosstab_table');
  else if(ime == 'MultiCrosstab')
	var divToPrint=document.getElementById('mc_holder');
  else if(ime == 'Means')
	var divToPrint=document.getElementById('div_means_data');
  else if(ime == 'TTest')
	var divToPrint=document.getElementById('ttestResults');
  else if(ime == 'Break')
	var divToPrint=document.getElementById('breakResults');
  else if(ime == 'Status')
	var divToPrint=document.getElementById('surveyStatistic');
  else if(ime == 'EditsAnalysis')
	var divToPrint=document.getElementById('surveyEditsAnalysis');
  else if(ime == 'Vpogled')
	var divToPrint=document.getElementById('edit_survey_data');
  else if(ime == 'CReport')
	var divToPrint=document.getElementById('custom_report_elements');
  else
	var divToPrint=document.getElementById('div_analiza_data');

  newWin = window.open('',ime,'scrollbars=1');
  
  newWin.document.write('<html><head><title>Okno za tiskanje - '+ime+'</title>');
  newWin.document.write('<meta http-equiv="Cache-Control" content="no-store"/>');
  newWin.document.write('<meta http-equiv="Pragma" content="no-cache"/>');
  newWin.document.write('<meta http-equiv="Expires" content="0"/>');

  newWin.document.write('<link rel="stylesheet" href="css/print.css">');
  newWin.document.write('<link rel="stylesheet" href="css/style_print.css" media="print">');
  newWin.document.write('</head><body class="print_analiza">');
  newWin.document.write('<div id="printIcon">');
  newWin.document.write('<a href="#" onclick="window.print(); return false;">Natisni</a>');
  newWin.document.write('</div>');
  
  newWin.document.write(divToPrint.innerHTML);
  newWin.document.write('</body></html>');
  newWin.focus();
  
  newWin.document.close();
  
  //newWin.print();
}


function scrollToProfile(_target) {

	var target = $(_target);
	if (target.length) {
		var top = target.offset().top;
		if ($('#status_profile').length) {
			top = target.offset().top - $('#status_profile').offset().top;
		} else if ($('#missing_profile').length) {
			top = target.offset().top - $('#missing_profile').offset().top;
		}
		$('#status_profile').animate({scrollTop: top});
		return false;

	}
} 

function cancleArchiveAnaliza() {
	$('#fullscreen').hide();
	$('#fade').fadeOut('slow');
}
function doArchiveAnaliza() {
	$("#fullscreen").load('ajax.php?a=doArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran}, function() {

		$('#fade').fadeTo('slow', 1);
		$('#fullscreen').show();
	});
}
function submitArchiveAnaliza() {
	$("#fullscreen").show();
	//$("#fullscreen").fadeOut('slow');
	//var content = $("#div_analiza_data").html();
	var name = $("#newAnalysisArchiveName").val();
	var note = $("#newAnalysisArchiveNote").val();
	var access = $("[name=newAnalysisArchiveAccess]:checked").val();
        var access_password = $("#newAnalysisArchiveAccessPassword").val();
	var duration = $("#newAnalysisArchiveDuration").val();
	var durationType = $("[name=newAADurationType]:checked").val();
	
	//$("#fullscreen").load('ajax.php?a=submitArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, content:content, name:name, note:note, access:access, duration:duration, durationType:durationType}, function() {
	$("#fullscreen").load('ajax.php?a=submitArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, name:name, note:note, access:access, duration:duration, durationType:durationType, access_password:access_password}, function() {
		$("#fullscreen").show();
	});
	
}
function closeArchiveAnaliza() {
	$('#fullscreen').hide();
	$('#fade').fadeOut('slow');
}

function emailArchiveAnaliza(aid) {
	//$('#fade').fadeTo('slow', 1);
	$("#fullscreen").show();
	$("#fullscreen").load('ajax.php?a=emailArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, aid:aid}, function() {
		$('#fullscreen').show();
	});
}

function sendEmailArchiveAnaliza(aid) {
	var subject = $("#email_archive_subject").val();
    var editor = CKEDITOR.instances.email_archive_text;
    try {
        var content = editor.getData();
        editor.isNotDirty = true;
    // ce editor se ni naloadan in imamo textarea
    } catch (e) {
        content = $('#email_archive_text').val();
    }
    
	var emails = $("#email_archive_list").val();
	$("#fullscreen").load('ajax.php?a=sendEmailArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, aid:aid, subject:subject, text:content, emails:emails}, function() {
	});
}

function AnalysisArchiveEdit(aid) {
	$('#fade').fadeTo('slow', 1);
	$("#fullscreen").load('ajax.php?a=editArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, aid:aid}, function() {
		$('#fullscreen').show();
	});	
}

function saveArchiveAnaliza(aid) {
	var name = $("#newAnalysisArchiveName").val();
	var note = $("#newAnalysisArchiveNote").val();
	var access = $("[name=newAnalysisArchiveAccess]:checked").val();
        var access_password = $("#newAnalysisArchiveAccessPassword").val();
	var duration = $("#newAnalysisArchiveDuration").val();
	
	$("#fullscreen").load('ajax.php?a=saveArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, aid:aid, name:name, note:note, access:access, duration:duration, access_password:access_password}, function(response) {
		if (response > 0 ) {
			$('#div_archive_content').load('ajax.php?a=refreshArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran}, function() {
				$('#fullscreen').hide();
				$('#fade').fadeOut('slow');
			});
			
		} else {
			genericAlertPopup('error');
			$('#fullscreen').hide();
			$('#fade').fadeOut('slow');

		}
	});

}

/**
 * Show or hide text input(div) for password access
 * @returns {undefined}
 */
function toggleAnalysisArchiveAccessPassword() {
    var value = $("input[name=newAnalysisArchiveAccess]:checked").val();
    var pass_div = document.getElementById("newAnalysisArchiveAccessPasswordDiv");
    value == 2 ? pass_div.style.visibility='visible' :  pass_div.style.visibility='hidden';
}

function AnalysisArchiveDelete(aid) {
	$('#fade').fadeTo('slow', 1);
	$("#fullscreen").load('ajax.php?a=askDeleteArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, aid:aid}, function() {
		$('#fullscreen').show();
	});
}
function doDeleteArchiveAnaliza(aid) {
	$.post('ajax.php?a=doDeleteArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, aid:aid}, function(response) {
		if (response > 0 ) {
			$('#div_archive_content').load('ajax.php?a=refreshArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran}, function() {
				$('#fullscreen').hide();
				$('#fade').fadeOut('slow');
			});
		} else {
			genericAlertPopup('error');
			$('#fullscreen').hide();
			$('#fade').fadeOut('slow');
		}
	});
}

function createArchiveBeforeEmail() {
	// kreira arhiv v ozadju in avtomatsko odpre okno za pošiljanje e-maila
	//var content = $("#div_analiza_data").html();
	$('#fade').fadeTo('slow', 1);
	//$.post('ajax.php?a=createArchiveBeforeEmail', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, content:content}, function(response) {
	$.post('ajax.php?a=createArchiveBeforeEmail', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran}, function(response) {
		if (parseInt(response) > 0) {
			var aid = parseInt(response);
			$("#fullscreen").load('ajax.php?a=emailArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, aid:aid}, function() {
				$('#fullscreen').show();
			});
		} else {
			if (parseInt(response) == -1) {
				genericAlertPopup('alert_no_archive_response',response);
			} else {
				genericAlertPopup('alert_archive_error_response',response);
			}
			$('#fullscreen').hide();
			$('#fade').fadeOut('slow');
		}
	});
}
function showHidenTextRow($sequence) {
	$("tr[name=valid_row_"+$sequence+"].displayNone").each(function() {$(this).removeClass('displayNone')});
	$("div#valid_row_togle_"+$sequence).addClass('displayNone');
	
}

function showHidenTextTable(spid,num_records,loop_id) {
	if ($("#srv_meta_podstran").length == 0 ) {
		var podstran = srv_meta_podstran;
	} else {
		var podstran = $("#srv_meta_podstran").val();
	}
	if ($("#srv_meta_anketa_id").length == 0 ) {
		var srv_meta_anketa_id = srv_meta_anketa_id;
	} else {
		var srv_meta_anketa_id = $("#srv_meta_anketa_id").val();
	}

	if (podstran == 'frequency') {
		$('#freq_'+spid+'[loop="'+loop_id+'"]').load('ajax.php?t=analysis&a=show_spid_more_table', {anketa:srv_meta_anketa_id, podstran: podstran, spid:spid, num_records:num_records, loop_id:loop_id});
	} else if (podstran == 'sumarnik') {
		$('#sum_'+spid+'[loop="'+loop_id+'"]').load('ajax.php?t=analysis&a=show_spid_more_table', {anketa:srv_meta_anketa_id, podstran: podstran, spid:spid, num_records:num_records, loop_id:loop_id});
	} else if (podstran == 'charts') {
		$('#chart_'+spid+'_loop_'+loop_id).load('ajax.php?t=charts&a=show_spid_more_table', {anketa:srv_meta_anketa_id, spid:spid, num_records:num_records, loop:loop_id});
	}
}
/*
var intervalPB='';
var hasStarted=false;

function createCollectData(auto) {
	var _automatic = '&automatic=false';
	if (arguments.length > 0 ) {
		if (auto == true) {
			_automatic = '';
		}
	}

	if (hasStarted == false) {
		// skrijemo tekst z file statusom
		$('#vnosi_file_status').hide();

		// nalozimo div za izris poteka
		$('#fade').fadeTo('slow', 1);
		$('#fullscreen').show();

		$("#fullscreen").load('prepareDataCrontab.php?action=LoadProgresBar', {anketa: srv_meta_anketa_id}, function () {
			$('#pbAllPercent div').css('width','0%');
			$('#pbCurrPercent div').css('width','0%');
			$('#pbRowPercent div').css('width','0%');

			$('#pbAllPercent').css('visibility','visible');
			$('#pbCurrPercent').css('visibility','visible');
			$('#hpbRowPercent, #pbRowPercent').css('visibility','visible');

		    $.ajax({
		        cache: false,
		        type: 'post',
		        url: 'prepareDataCrontab.php?action=collectSingle'+_automatic+'&anketa='+srv_meta_anketa_id,
		        beforeSend: start_display_progressBar()
		    });
		});
	}
}

var pbLabels= new Array()
pbLabels['1'] = lang['srv_collectdata_progress_status1'];
pbLabels['2'] = lang['srv_collectdata_progress_status2'];
pbLabels['3'] = lang['srv_collectdata_progress_status3'];
pbLabels['8'] = lang['srv_collectdata_progress_status8'];
pbLabels['9'] = lang['srv_collectdata_progress_status9']
pbLabels['-1'] = lang['srv_collectdata_progress_status0']

function start_display_progressBar(type) {
	if (type == undefined) type = 'true';
    if (intervalPB=="") {
    	$('#start').css('visibility','hidden');
        intervalPB=window.setInterval("display_progressBar('"+type+"')",750); // interval = 400 ms
        $('#loader').css('display','inline');
    } else {
        stop_display_progressBar();
    }
}

function stop_display_progressBar() {
	$("#loading").show();
    if (intervalPB!="") {
    	$('#start').css('visibility','visible');
        window.clearInterval(intervalPB);
        intervalPB="";

//		$('#fade').fadeOut('slow');
		$('#fullscreen').hide();
		$('#fullscreen').html('');

        hasStarted = true;
    	return reloadData();
    }
}
function display_progressBar(type) {
	if (type == undefined) type = 'true';
	$.ajax({
		cache: false,
		type: 'get',
		url: 'getCollectTimer.php?ajaxTimer='+type,
		success: function(response) {
			var data = jQuery.parseJSON(response);
			
			$('#actionLabel').html(pbLabels[data.id]+' ('+data.t+')');
			$('#pbAllPercent div').css('width',data.pa+'%');
			if ( data.id == '2') {
				// na koliko % smo v header datoteki
				$('#pbCurrPercent div').css('width',data.ph+'%');
				$('#pbCurrPercentLabel').html(data.ph+'%');
				// na katerem zapisu smo
				$('#pbRowPercent div').css('width',data.pr+'%');
				$('#pbRowPercentLabel').html(data.nr);
				
			}
			if ( data.id == '3') {
//				$('#hpbRowPercent, #pbRowPercent').css('visibility','visible');
				// na koliko % smo v data datoteki
				$('#pbCurrPercent div').css('width',data.pd+'%');
				$('#pbCurrPercentLabel').html(data.pd+'%');
				// na katerem zapisu smo
				$('#pbRowPercent div').css('width',data.pr+'%');
				$('#pbRowPercentLabel').html(data.nr);
			}

			if ( data.id == '9'  || data.id == '-1' ) { // 9 = konec
				hasStarted = true;
				// konec prenosa
				$('#pbCurrPercent div').css('width','100%');
				$('#pbRowPercent div').css('width','100%');
				$('#pbCurrPercentLabel').html('100%');
				$('#pbRowPercentLabel').html('100%');
				stop_display_progressBar();
			}
		}
	});
}
*/
function show_navedbe(spid,status) {
		if (status == 1) {
			// vidno je oboje
			// pokažemo pravilne linke
//			$("[name=span_show_navedbe_1_"+spid+"]").each(function() {$(this).addClass('displayNone'); });
			$("[name=span_show_navedbe_2_"+spid+"]").each(function() {$(this).removeClass('displayNone'); });
			$("[name=span_show_navedbe_3_"+spid+"]").each(function() {$(this).addClass('displayNone'); });
			$("#div_navedbe_1_"+spid).removeClass('displayNone');
			$("#div_navedbe_2_"+spid).addClass('displayNone');
			
		} else if (status == 2) {
			// pokažemo samo navedbe
			// pokažemo pravilne linke
//			$("[name=span_show_navedbe_1_"+spid+"]").each(function() {$(this).removeClass('displayNone'); });
			$("[name=span_show_navedbe_2_"+spid+"]").each(function() {$(this).addClass('displayNone'); });
			$("[name=span_show_navedbe_3_"+spid+"]").each(function() {$(this).removeClass('displayNone'); });
			$("#div_navedbe_1_"+spid).addClass('displayNone');
			$("#div_navedbe_2_"+spid).removeClass('displayNone');
			/*
		} else {
		
			// pokažemo samo odgovore

			// pokažemo pravilne linke
			$("[name=span_show_navedbe_1_"+spid+"]").each(function() {$(this).addClass('displayNone'); });
			$("[name=span_show_navedbe_2_"+spid+"]").each(function() {$(this).removeClass('displayNone'); });
			$("[name=span_show_navedbe_3_"+spid+"]").each(function() {$(this).addClass('displayNone'); });
			$("#div_navedbe_1_"+spid).removeClass('displayNone');
			$("#div_navedbe_2_"+spid).addClass('displayNone');
*/
		}	
}

// Pobrisemo vec vnosov hkrati
function deleteMultipleData(){
		
	var userArray = new Array;
	
	var note = 'srv_ask_delete';
	
	// Napolnimo array z checkanimi userji
	$('#div_vnosi_data input:checked').each(function() {
		userArray.push($(this).parent().parent().find('.data_uid').html());
		
		// Preverimo ce je med njimi tudi kaksno vabilo - dodatno opozorilo
		if($(this).parent().parent().find('.invitation_cell').length == 1){
			note = 'srv_ask_delete_inv';
		}
	});
	
	if (userArray.length > 0) {
		//var users = JSON.stringify(userArray);
		$.post('ajax.php?a=outputLanguageNote', {note: note}, function(lang_note) {
			// Povprašamo uporabnika ali je ziher
			if (confirm(lang_note)) {				
				$.post('ajax.php?a=dataDeleteMultipleRow', {anketa: srv_meta_anketa_id, users:userArray},function(){
					//skrijemo vrstice
					$('#div_vnosi_data input:checked').each( function() {
						var row = $(this).parent().parent();
						row.hide();
					});
				});
			}
		});
	} else {
		
		genericAlertPopup('srv_data_delete_not_selected');
	}
}

function selectAll(val){
	// oznacimo vse checkboxe
	if(val == 1){
		$(".delete_data_row").attr("checked", "true");
		$("#switch_on").hide();
		$("#switch_off").show();
	}
	else{
		$('.delete_data_row').removeAttr('checked');
		$("#switch_off").hide();
		$("#switch_on").show();
	}
}

function quickEditAction(action, usr_id){
	var srv_site_url = $("#srv_site_url").val();
	
	// brisanje vnosa
	if(action == 'delete'){
		$.post('ajax.php?a=outputLanguageNote', {note: 'srv_ask_delete'}, function(lang_note) {
			// Povprašamo uporabnika ali je ziher
			if (confirm(lang_note)) {
				if (usr_id > 0) {
					
					$.post('ajax.php?a=dataDeleteRow', {anketa: srv_meta_anketa_id, usr_id:usr_id }, function(response) {
						if (response == '0') {
							var href = srv_site_url+'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=data&m=quick_edit';
							window.location = href;
						} else {
							genericAlertPopup('alert_parameter_response',response);
						}
					});
				} else {
					genericAlertPopup('alert_delete_error');
				}
			}
		});
	}	
	
	// editiranje starega vnosa
	if(action == 'edit'){
		var href = srv_site_url+'main/survey/edit_anketa.php?anketa='+srv_meta_anketa_hash+'&usr_id='+usr_id+'';
		if (usr_id > 0 ){
			window.open(href, '_blank');
		}
	}
	
	// pdf vprašalnika
	if(action == 'pdf'){
		if (usr_id > 0 ){
			var href = srv_site_url+'admin/survey/izvoz.php?a=pdf_results&anketa='+srv_meta_anketa_id+'&usr_id='+usr_id;
			$.post('ajax.php?a=makeEncodedIzvozUrlString', {anketa: srv_meta_anketa_id, string:href}, function(url) {
				window.open(url,'_blank');	
			});
		}	
	}	

	// rtf vprašalnika
	if(action == 'rtf'){
		if (usr_id > 0 ){
			var href = srv_site_url+'admin/survey/izvoz.php?a=rtf_results&anketa='+srv_meta_anketa_id+'&usr_id='+usr_id;
			$.post('ajax.php?a=makeEncodedIzvozUrlString', {anketa: srv_meta_anketa_id, string:href}, function(url) {
				window.open(url,'_blank');	
			});
		}		
	}
	
	// editiranje omogoceno/onemogoceno
	if(action == 'quick_view'){
		
		if(document.getElementById('quick_view').value == 1){
			var editing = '&quick_view=0';
		}
		else{
			var editing = '&quick_view=1';
		}

		var href = srv_site_url+'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=data&m=quick_edit'+editing+'&usr_id='+usr_id+'';

		if (usr_id > 0 ){
			window.location = href;
		}
	}
	
	// kopiranje vnosa
	if(action == 'copy'){
		$.post('ajax.php?a=outputLanguageNote', {note: 'srv_ask_copy'}, function(lang_note) {
			// Povprašamo uporabnika ali je ziher
			if (confirm(lang_note)) {
				if (usr_id > 0) {
					
					$.post('ajax.php?a=dataCopyRow', {anketa: srv_meta_anketa_id, usr_id:usr_id }, function(response) {
						var href = srv_site_url+'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=data&m=quick_edit&usr_id='+response;
						window.location = href;
					});
				} else {
					genericAlertPopup('alert_copy_error');
				}
			}
		});
	}	
}

function doInspectFromFrequency(el,event) {
	var $el_td = $(el);
	var $el_tr = $(el).parent();
	var spr_data = $el_tr.attr('id');
	var vkey = $el_tr.attr('vkey');

	$.post("ajax.php?t=inspect&a=analizaPrepareInspect", {anketa:srv_meta_anketa_id, from_podstran:srv_meta_podstran, spr_data:spr_data, vkey:vkey}, function(response) {
		window.location = "index.php?anketa="+srv_meta_anketa_id+response; //"&a=data";
	});
}

function doInspectMultiGrid(el,event) {
	var $el_td = $(el);
	var $el_tr_parent = $(el).parent().parent().closest('tr');
	var spr_data = $el_tr_parent.attr('id');
	
	var vkey = $el_td.attr('vkey');
	
	$.post("ajax.php?t=inspect&a=analizaPrepareInspect", {anketa:srv_meta_anketa_id, from_podstran:srv_meta_podstran,spr_data:spr_data, vkey:vkey}, function(response) {
		window.location = "index.php?anketa="+srv_meta_anketa_id+response;//"&a=data";
	}); 
}
function doInspectDoubleMultiGrid(el,event) {
	var $el_td = $(el);
	var $el_tr_parent = $(el).parent().parent().closest('tr');
	var spr_data = $el_tr_parent.attr('id');
	
	var vkey = $el_td.attr('gid');
	
	$.post("ajax.php?t=inspect&a=analizaPrepareInspect", {anketa:srv_meta_anketa_id, from_podstran:srv_meta_podstran,spr_data:spr_data, vkey:vkey}, function(response) {
		window.location = "index.php?anketa="+srv_meta_anketa_id+response;//"&a=data";
	}); 
}

function doInspectMultiCheck(el,event) {
	var $el_td = $(el);
	var $el_tr = $(el).parent().closest('tr');
	var spr_data = $el_tr.attr('id');
	var vkey = '1';
	
	$.post("ajax.php?t=inspect&a=analizaPrepareInspect", {anketa:srv_meta_anketa_id, from_podstran:srv_meta_podstran, spr_data:spr_data, vkey:vkey}, function(response) {
		window.location = "index.php?anketa="+srv_meta_anketa_id+response;//"&a=data";
	});
}

function doInspectMultiText(el,event) {
	var $el_td = $(el);
	var $el_tr = $(el).parent().closest('tr');
	var spr_data = $el_tr.closest('table').attr('id');
	
	if ($el_td.attr('vkey')!== undefined) {
		// ker vcasih risemo print ikonco smo takrat dali key v atrribut, ker se v html pojavi <span>
		var vkey = $el_td.attr('vkey');
	} else {
		var vkey = $el_td.html();	
	}
	
	$.post("ajax.php?t=inspect&a=analizaPrepareInspect", {anketa:srv_meta_anketa_id, from_podstran:srv_meta_podstran, spr_data:spr_data, vkey:vkey}, function(response) {
		window.location = "index.php?anketa="+srv_meta_anketa_id+response;// "&a=data";
	});
}


function changeSessionInspectAnaliza() {
	$("#spanSessionInspect").load("ajax.php?t=inspect&a=changeSessionInspect", {anketa:srv_meta_anketa_id, isAnaliza:1}, function() {
		reloadData();	
	});
}

function displayDataPrintPreview() {
	var size = "location=0,height=500,width=700,scrollbars=1,fullscreen=0,menubar=0,status=0,titlebar=0,toolbar=0,channelmode=0,directories=0";
	var recipe =  window.open('','RecipeWindow',size);
	var rec_on_page = $("#rec_on_page_top").val();
	
	$.post('ajax.php?t=displayData&a=displayDataPrintPreview&limit='+rec_on_page, {anketa: srv_meta_anketa_id, limit:rec_on_page}, function(response) {
		recipe.document.open();
		recipe.document.write(response);
		recipe.document.close();
		recipe.focus();
		return false;    
	});	
}

function toggleAnalysisAdvanced(what) {
	$("#div_analiza_filtri_right").load('ajax.php?t=analysis&a=toggleAnalysisAdvanced', 
		{anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, what:what}, function(response) {
	});
}

function change_analiza_preview() {
	var value = $('#cbx_shoq_analiza_preview').is(':checked') ? '1' : '0';
	$("#analizaSubNav").load('ajax.php?t=analysis&a=changeAnalizaPreview&anketa='+srv_meta_anketa_id+'&m='+srv_meta_podstran,
			{anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, value:value}, function(response) {
	});
}



function doArchiveChart() {
	//preverimo ali obstaja vsebina div_analiza_data.charts
	if ($("#div_analiza_data.charts").length > 0 && $("#div_analiza_data.charts").html() != '') {
		$("#fullscreen").load('ajax.php?a=doArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran}, function() {

			$('#fade').fadeTo('slow', 1);
			$('#fullscreen').show();
		});	
	} else {
		genericAlertPopup('alert_no_archive_tables');
	}
}
function submitArchiveChart() {
	//preverimo ali obstaja vsebina meansa
	if ($("#div_analiza_data.charts").html().length > 0 ) {
		//var content = $("#div_analiza_data.charts").html();

		var name = $("#newAnalysisArchiveName").val();
		var note = $("#newAnalysisArchiveNote").val();
		var access = $("[name=newAnalysisArchiveAccess]:checked").val();
		var duration = $("#newAnalysisArchiveDuration").val();
		var durationType = $("[name=newAADurationType]:checked").val();
		$("#fullscreen").load('ajax.php?a=submitArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, name:name, note:note, access:access, duration:duration, durationType:durationType
			//, content:content
			}, function() {
			$("#fullscreen").show();
		});
	} else {
		genericAlertPopup('alert_no_archive_tables');
	}
}

function createArchiveChartBeforeEmail() {
	//preverimo ali obstaja vsebina div_analiza_data.charts
	if ($("#div_analiza_data.charts").html().length > 0 ) {
		//var content = $("#div_analiza_data.charts").html();
		$.post('ajax.php?a=createArchiveBeforeEmail', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran
			//, content:content
			}, function(response) {
			if (parseInt(response) > 0) {
				var aid = parseInt(response);
				$("#fullscreen").load('ajax.php?a=emailArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, aid:aid}, function() {
					$('#fullscreen').show();
				});
			} else {
				if (parseInt(response) == -1) {
					genericAlertPopup('alert_no_archive_response',response);
				} else {
					genericAlertPopup('alert_archive_error_response',response);
				}
				$('#fullscreen').hide();
				$('#fade').fadeOut('slow');
			}
		});

	} else {
		genericAlertPopup('alert_no_archive_tables');
	}
};

function surveyAnalisysIncludeTestData() {
	var includeTestData = $("#cnx_include_test_data").is(':checked');
	$.post('ajax.php?a=analisysIncludeTestData', {anketa: srv_meta_anketa_id, includeTestData:includeTestData}, function() {
		return reloadData();
	});
}

function setSnDisplayFullTableCheckbox() {
	var fullTable = $("#snCreateFullTable").is(':checked') ? '1' : '0';
	$.post('ajax.php?t=displayData&a=setSnDisplayFullTableCheckbox', {anketa: srv_meta_anketa_id, fullTable:fullTable}, function(response) {
		return reloadData();
	});	
}

function changeSpremenljivkaLestvica(spid,skala) {
	$("#sum_"+spid).load('ajax.php?t=analysis&a=changeSpremenljivkaLestvica', {anketa:srv_meta_anketa_id,spid:spid,skala:skala}, function() {
		window.location.reload();
	});
}

function printCurrentAnalysis(spid) {
	var content = document.getElementById("sum_"+spid);
	var pri = document.getElementById("ifmcontentstoprint").contentWindow;
	pri.document.open();
	pri.document.write(content.innerHTML);
	$("link[rel=stylesheet]").clone().appendTo($("#ifmcontentstoprint").contents().find("head"));
	pri.document.close();
	pri.focus();
	pri.print();
}

/*
 * $.fn.center = function() {
				    this.css({
				        'position': 'fixed',
				        'left': '50%',
				        'top': '50%'
				    });
				    this.css({
				        'margin-left': -this.width() / 2 + 'px',
				        'margin-top': -this.height() / 2 + 'px'
				    });
		
				    return this;
				}
				
				
 */

$.fn.followTo = function ( pos ) {
    var $this = this,
        $window = $(window);
    // fiksiramo začetno višino
    $this.css({top: (pos)});
    
    $window.scroll(function(e){
        var $position = $this.position().top;
    	var wst = $window.scrollTop();
        if (wst < pos) {
        	$this.css({
                top: ((pos - wst) > 0) ? (pos - wst) : 0  
            });
        } else {
            $this.css({
                top: 0
            });
        }
    });
};




/**
 * Copyright (c) 2007-2012 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
 * Dual licensed under MIT and GPL.
 * @author Ariel Flesler
 * @version 1.4.3.1
 */
//(function($){var h=$.scrollTo=function(a,b,c){$(window).scrollTo(a,b,c)};h.defaults={axis:'xy',duration:parseFloat($.fn.jquery)>=1.3?0:1,limit:true};h.window=function(a){return $(window)._scrollable()};$.fn._scrollable=function(){return this.map(function(){var a=this,isWin=!a.nodeName||$.inArray(a.nodeName.toLowerCase(),['iframe','#document','html','body'])!=-1;if(!isWin)return a;var b=(a.contentWindow||a).document||a.ownerDocument||a;return/webkit/i.test(navigator.userAgent)||b.compatMode=='BackCompat'?b.body:b.documentElement})};$.fn.scrollTo=function(e,f,g){if(typeof f=='object'){g=f;f=0}if(typeof g=='function')g={onAfter:g};if(e=='max')e=9e9;g=$.extend({},h.defaults,g);f=f||g.duration;g.queue=g.queue&&g.axis.length>1;if(g.queue)f/=2;g.offset=both(g.offset);g.over=both(g.over);return this._scrollable().each(function(){if(e==null)return;var d=this,$elem=$(d),targ=e,toff,attr={},win=$elem.is('html,body');switch(typeof targ){case'number':case'string':if(/^([+-]=)?\d+(\.\d+)?(px|%)?$/.test(targ)){targ=both(targ);break}targ=$(targ,this);if(!targ.length)return;case'object':if(targ.is||targ.style)toff=(targ=$(targ)).offset()}$.each(g.axis.split(''),function(i,a){var b=a=='x'?'Left':'Top',pos=b.toLowerCase(),key='scroll'+b,old=d[key],max=h.max(d,a);if(toff){attr[key]=toff[pos]+(win?0:old-$elem.offset()[pos]);if(g.margin){attr[key]-=parseInt(targ.css('margin'+b))||0;attr[key]-=parseInt(targ.css('border'+b+'Width'))||0}attr[key]+=g.offset[pos]||0;if(g.over[pos])attr[key]+=targ[a=='x'?'width':'height']()*g.over[pos]}else{var c=targ[pos];attr[key]=c.slice&&c.slice(-1)=='%'?parseFloat(c)/100*max:c}if(g.limit&&/^\d+$/.test(attr[key]))attr[key]=attr[key]<=0?0:Math.min(attr[key],max);if(!i&&g.queue){if(old!=attr[key])animate(g.onAfterFirst);delete attr[key]}});animate(g.onAfter);function animate(a){$elem.animate(attr,f,g.easing,a&&function(){a.call(this,e,g)})}}).end()};h.max=function(a,b){var c=b=='x'?'Width':'Height',scroll='scroll'+c;if(!$(a).is('html,body'))return a[scroll]-$(a)[c.toLowerCase()]();var d='client'+c,html=a.ownerDocument.documentElement,body=a.ownerDocument.body;return Math.max(html[scroll],body[scroll])-Math.min(html[d],body[d])};function both(a){return typeof a=='object'?a:{top:a,left:a}}})(jQuery);


/**
 * Copyright (c) 2007-2014 Ariel Flesler - aflesler<a>gmail<d>com | http://flesler.blogspot.com
 * Licensed under MIT
 * @author Ariel Flesler
 * @version 1.4.14
 */
//(function(k){'use strict';k(['jquery'],function($){var j=$.scrollTo=function(a,b,c){return $(window).scrollTo(a,b,c)};j.defaults={axis:'xy',duration:0,limit:!0};j.window=function(a){return $(window)._scrollable()};$.fn._scrollable=function(){return this.map(function(){var a=this,isWin=!a.nodeName||$.inArray(a.nodeName.toLowerCase(),['iframe','#document','html','body'])!=-1;if(!isWin)return a;var b=(a.contentWindow||a).document||a.ownerDocument||a;return/webkit/i.test(navigator.userAgent)||b.compatMode=='BackCompat'?b.body:b.documentElement})};$.fn.scrollTo=function(f,g,h){if(typeof g=='object'){h=g;g=0}if(typeof h=='function')h={onAfter:h};if(f=='max')f=9e9;h=$.extend({},j.defaults,h);g=g||h.duration;h.queue=h.queue&&h.axis.length>1;if(h.queue)g/=2;h.offset=both(h.offset);h.over=both(h.over);return this._scrollable().each(function(){if(f==null)return;var d=this,$elem=$(d),targ=f,toff,attr={},win=$elem.is('html,body');switch(typeof targ){case'number':case'string':if(/^([+-]=?)?\d+(\.\d+)?(px|%)?$/.test(targ)){targ=both(targ);break}targ=win?$(targ):$(targ,this);if(!targ.length)return;case'object':if(targ.is||targ.style)toff=(targ=$(targ)).offset()}var e=$.isFunction(h.offset)&&h.offset(d,targ)||h.offset;$.each(h.axis.split(''),function(i,a){var b=a=='x'?'Left':'Top',pos=b.toLowerCase(),key='scroll'+b,old=d[key],max=j.max(d,a);if(toff){attr[key]=toff[pos]+(win?0:old-$elem.offset()[pos]);if(h.margin){attr[key]-=parseInt(targ.css('margin'+b))||0;attr[key]-=parseInt(targ.css('border'+b+'Width'))||0}attr[key]+=e[pos]||0;if(h.over[pos])attr[key]+=targ[a=='x'?'width':'height']()*h.over[pos]}else{var c=targ[pos];attr[key]=c.slice&&c.slice(-1)=='%'?parseFloat(c)/100*max:c}if(h.limit&&/^\d+$/.test(attr[key]))attr[key]=attr[key]<=0?0:Math.min(attr[key],max);if(!i&&h.queue){if(old!=attr[key])animate(h.onAfterFirst);delete attr[key]}});animate(h.onAfter);function animate(a){$elem.animate(attr,g,h.easing,a&&function(){a.call(this,targ,h)})}}).end()};j.max=function(a,b){var c=b=='x'?'Width':'Height',scroll='scroll'+c;if(!$(a).is('html,body'))return a[scroll]-$(a)[c.toLowerCase()]();var d='client'+c,html=a.ownerDocument.documentElement,body=a.ownerDocument.body;return Math.max(html[scroll],body[scroll])-Math.min(html[d],body[d])};function both(a){return $.isFunction(a)||$.isPlainObject(a)?a:{top:a,left:a}}return j})}(typeof define==='function'&&define.amd?define:function(a,b){if(typeof module!=='undefined'&&module.exports){module.exports=b(require('jquery'))}else{b(jQuery)}}));

function dataTableScroll(where)
{
	if (where == 'left'){
		//$(window).scrollTo('-=500px', 300, { axis:'x' });
		
		var leftPos = $('html').scrollLeft();
		$("html").animate({scrollLeft: leftPos - 700}, 800);
	}	  
	if (where == 'right'){
		//$(window).scrollTo('+=500px', 300, { axis:'x' });
		
		var leftPos = $('html').scrollLeft();
		$("html").animate({scrollLeft: leftPos + 700}, 800);
	}
}