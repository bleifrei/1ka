var global_expanded = 0;

function creport_init () {
	
	global_expanded = $("#creport_expanded").val();
	
	// sortable za custom report
	$('.report_element').live('mouseover', function (event) {
		$('#custom_report_sortable').sortable({
			items: 'li', 
			handle: '.report_element_title', 
			opacity: 0.8,
			revert: true,
			create:function(){
				$('#custom_report_sortable').css('min-height', $('#custom_report_sortable').height());
			},
			stop: function() { 
				$.post('ajax.php?t=custom_report&a=change_order', {anketa: srv_meta_anketa_id, sortable: $('#custom_report_sortable').sortable('serialize')}); 
			}
		});
	});
	
	$(".report_element").live({
		mouseenter: function(){
			$(this).addClass('hover');
			$(this).find('.report_element_head').addClass('hover');
		},
		mouseleave:	function(){
			$(this).removeClass('hover');
			$(this).find('.report_element_head').removeClass('hover');
		}
	});
	
	$(".report_element_separator").live({
		mouseenter: function(){
			$(this).find('.add_element').stop().animate({opacity:1},  500);
		},
		mouseleave:	function(){
			$(this).find('.add_element').stop().animate({opacity:0},  500);
		}
	});
	
	// click report elementa
	$(".report_element_title").live("click", function(event) {
	
		var element = $(this).parent().parent();
		var id = $(element).attr('id').substr(15);

		expandCustomReportElement(id);
	});

	
	// urejanje inline texta 
	$('div.creport_text_inline').live('focus', function (event) {		
		$(this).parent().addClass('writing');
		
	}).live('blur', function () {		
		$(this).parent().removeClass('writing');
		
		var expanded = 0;
		if($(this).parent().parent().hasClass('active')){
			expanded = 1;
		}
		
		var id = $(this).attr('el_id');
		var value = $(this).html();;
		
		//editCustomReportElement(id, 'text', value);
		//$('#report_element_'+id).load('ajax.php?t=custom_report&a=edit_element', {anketa: srv_meta_anketa_id, element_id:id, what:'text', value:value, expanded:expanded});
		$.post('ajax.php?t=custom_report&a=edit_element', {anketa: srv_meta_anketa_id, element_id:id, what:'text', value:value, expanded:expanded});
	});	
	
	// urejanje inline naslova 
	$('div.creport_title_inline').live('focus', function (event) {		
		$(this).parent().addClass('writing');
		
	}).live('blur', function () {		
		$(this).parent().removeClass('writing');
		
		var value = $(this).html();;
		
		$.post('ajax.php?t=custom_report&a=edit_title', {anketa: srv_meta_anketa_id, value:value});
	});	
	
	
	// Izbira/save profila reporta
	$("#creport_profile_setting_text").live("click", function (event) {
		if (event.button != 0) { // wasn't the left button - ignore
			return true;
		}
		
		showCReportProfiles(false);
	});
	
	$(".creport_profiles").live('click', function(event) {
		var $target = $(event.target);
		if ($target.hasClass('option')) {
			var id = $target.attr('value');
			var author = $target.attr('author');
				
			$("#div_creport_settings_profiles").load('ajax.php?t=custom_report&a=creport_change_profile', {anketa: srv_meta_anketa_id, id:id, author:author});
		}
	});
	
	// Dodajanje novega porocila (plusek)
	$("#creport_profile_setting_plus").live("click", function (event) {
		//showCReportProfiles(true);
		creport_profile_action('show_new');
	});
	// Urejanje vseh porocil (edit)
	$("#creport_profile_setting_edit").live("click", function (event) {
		showCReportProfiles();		
	});
}


// doda element v custom report
function addCustomReportElement(type, sub_type, spr1, spr2, with_text) {

	var element = document.getElementById(type+'-'+sub_type+'-'+spr1+'-'+spr2);
	var insert = $(element).hasClass('star_on') ? 0 : 1;

	// vstavljamo - prizgemo zvezdico
	if(insert == 1){
		$(element).removeClass('star_off');
		$(element).addClass('star_on');
		
		// popravimo title zvezdice
		$(element).parent().attr('title', lang['srv_custom_report_inserted_title']);
		
		// Ce imamo zraven zvezdice se text
		if(with_text == 1){
			var insert_text = document.getElementById(type+'-'+sub_type+'-'+spr1+'-'+spr2+'_insert');
			var inserted_text = document.getElementById(type+'-'+sub_type+'-'+spr1+'-'+spr2+'_inserted');
			
			$(insert_text).hide();
			$(inserted_text).show();		
		}
	}
	// brisemo
	else{
		$(element).removeClass('star_on');
		$(element).addClass('star_off');
		
		// popravimo title zvezdice
		$(element).parent().attr('title', lang['srv_custom_report_insert_title']);
		
		// Ce imamo zraven zvezdice se text
		if(with_text == 1){
			var insert_text = document.getElementById(type+'-'+sub_type+'-'+spr1+'-'+spr2+'_insert');
			var inserted_text = document.getElementById(type+'-'+sub_type+'-'+spr1+'-'+spr2+'_inserted');
			
			$(inserted_text).hide();
			$(insert_text).show();
		}
	}	
	
	$.post('ajax.php?t=custom_report&a=add_element', {anketa: srv_meta_anketa_id, type:type, sub_type:sub_type, spr1:spr1, spr2:spr2, insert:insert}, 
		function(response){
			if(response == '1'){				
				$('#fade').fadeTo('slow', 1, function(){				
					$('#custom_report_alert').show();
					$('#custom_report_alert').load('ajax.php?t=custom_report&a=first_alert', {anketa: srv_meta_anketa_id});
				});
			}
		}
	);
}

// doda prazen element v custom report
function addEmptyCustomReportElement(id) {
	$('#anketa_custom_report').load('ajax.php?t=custom_report&a=add_empty_element', {anketa: srv_meta_anketa_id, expanded: global_expanded, element_id:id}, function(){
			
		// poiscemo id vstavljenega
		var added_el = $('#added_element').attr('el_id');
		var element = document.getElementById('report_element_'+added_el);
		
		// razsirimo vstavljen element (ce je zaprt)
		if(!$(element).find('.report_element_head').hasClass('active')){
			
			$(element).addClass('active');

			$("#report_element_"+ added_el).load('ajax.php?t=custom_report&a=expand_element', {anketa: srv_meta_anketa_id, element_id:added_el, expanded:1}); 
		}		
	});
}

// doda textovni element v custom report
function addTextCustomReportElement(id) {
	$('#anketa_custom_report').load('ajax.php?t=custom_report&a=add_text_element', {anketa: srv_meta_anketa_id, expanded: global_expanded, element_id:id}, function(){
		
		// poiscemo id vstavljenega
		var added_el = $('#added_element').attr('el_id');
		var element = document.getElementById('report_element_'+added_el);
		
		// razsirimo vstavljen element (ce je zaprt)
		if(!$(element).find('.report_element_head').hasClass('active')){
			
			$(element).addClass('active');

			$("#report_element_"+ added_el).load('ajax.php?t=custom_report&a=expand_element', {anketa: srv_meta_anketa_id, element_id:added_el, expanded:1}, function(){
				// vklopimo focus na dodani element
				$('#report_element_' + added_el).find('.creport_text_inline').focus();
			}); 
		}
		else{
			// vklopimo focus na dodani element
			$('#report_element_' + added_el).find('.creport_text_inline').focus();
		}
	});
}

// doda pagebreak v custom report
function addPBCustomReportElement(id) {
	$('#anketa_custom_report').load('ajax.php?t=custom_report&a=add_pb_element', {anketa: srv_meta_anketa_id, expanded: global_expanded, element_id:id});
}

// zbrise element iz custom reporta
function deleteCustomReportElement(element_id) {
	$('#anketa_custom_report').load('ajax.php?t=custom_report&a=delete_element', {anketa: srv_meta_anketa_id, expanded: global_expanded, element_id:element_id});
}

// razsirjanje elementa v custom reportu
function expandCustomReportElement(id) {
	
	var element = document.getElementById('report_element_'+id);

	if($(element).find('.report_element_head').hasClass('active')){
		var expanded = 0;
		$(element).removeClass('active');
	}
	else{
		var expanded = 1;
		$(element).addClass('active');
	}

	$("#report_element_"+ id).load('ajax.php?t=custom_report&a=expand_element', {anketa: srv_meta_anketa_id, element_id:id, expanded:expanded}); 
}

// urejanje elementa v custom reportu
function editCustomReportElement(id, what, value) {
	$('#report_element_'+id).load('ajax.php?t=custom_report&a=edit_element', {anketa: srv_meta_anketa_id, element_id:id, what:what, value:value});
}

// urejanje ttest elementa v custom reportu
function editCustomReportTTestVar(id) {
	
	var what = 'spr1';
	
	// zdruzimo vrednost selecta prve spr in checkboxov
	var value = document.getElementById('report_element_spr_id_'+id).value;
	
	$('input:checkbox.subTtest_'+id).each(function () {
       value = value + (this.checked ? '-' + $(this).val() : "");
	});

	$('#report_element_'+id).load('ajax.php?t=custom_report&a=edit_element', {anketa: srv_meta_anketa_id, element_id:id, what:what, value:value});
}

// kopiranje elementa v custom reportu
function copyCustomReportElement(id) {
	$('#anketa_custom_report').load('ajax.php?t=custom_report&a=copy_element', {anketa: srv_meta_anketa_id, expanded: global_expanded, element_id:id});
}

function printCustomReportElement(ime, id){  
	var divToPrint = document.getElementById(id);
	
	newWin = window.open('',ime,'scrollbars=1');
  
	newWin.document.write('<html><head><title>Okno za tiskanje - '+ime+'</title>');
	newWin.document.write('<link rel="stylesheet" href="css/print.css">');
	newWin.document.write('<link rel="stylesheet" href="css/style_print.css" media="print">');
	newWin.document.write('</head><body>');
	newWin.document.write('<div id="printIcon">');
	newWin.document.write('<a href="#" onclick="window.print(); return false;">Natisni</a>');
	newWin.document.write('</div>');
	  
	newWin.document.write(divToPrint.innerHTML);
	newWin.document.write('</body></html>');
	newWin.focus();
	  
	newWin.document.close();
}

// alert za dodajanje vseh elementov istega tipa v custom report
function addCustomReportAllElementsAlert(type) {
	
	if(type > 0){
		$('#fade').fadeTo('slow', 1, function(){
			$('#custom_report_alert').show();
			$('#custom_report_alert').load('ajax.php?t=custom_report&a=all_elements_alert', {anketa: srv_meta_anketa_id, type:type});
		});
	}
}

// zapremo alert za dodajanje vseh elementov istega tipa v custom report
function addCustomReportAllElementsClose() {

	$('#fade').fadeOut('slow', function(){
		$('#custom_report_alert').hide();
	});
}

// doda vse elemente istega tipa v custom report (vse grafe, opisne, frekvence, sumarnike)
function addCustomReportAllElements(type) {

	// Napolnimo report z vsemi elementi istega tipa - BREAK
	if(type == 9){
		
		var sub_type = $('input:radio[name=break_charts]:checked').val();
		var spr = $('#breakSpremenljivka').val();
		var seq = $('option:selected', '#breakSpremenljivka').attr('seq');
		var spr1 = seq + '-' + spr + '-undefined';
	
		$.post('ajax.php?t=custom_report&a=all_elements_add', {anketa: srv_meta_anketa_id, type:type, sub_type:sub_type, spr1:spr1}, function(){			
				
				// skocimo na custom report
				var srv_site_url = $("#srv_site_url").val();
				srv_site_url += 'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=analysis&m=analysis_creport';		
				window.location.href = srv_site_url;
			}
		);
	}
	
	// Napolnimo report z vsemi elementi istega tipa
	else{
		$.post('ajax.php?t=custom_report&a=all_elements_add', {anketa: srv_meta_anketa_id, type:type}, function(){			
				
				// skocimo na custom report
				var srv_site_url = $("#srv_site_url").val();
				srv_site_url += 'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=analysis&m=analysis_creport';		
				window.location.href = srv_site_url;
			}
		);
	}
}


/**
* nastavi editor na contenteditable element za naslov
*/
function creport_load_editor (_this) {
	
	/*$(_this).css('display', 'none');
	var el = $(_this).parent().find('.creport_textarea');

	var el_id = el.attr('el_id');
	
	el.replaceWith('<textarea id="report_element_texteditor_'+el_id+'" class="creport_textarea" style="width:99%">'+el.html()+'</textarea>'+
	'<span class="buttonwrapper" style="margin:5px 0"><a class="ovalbutton ovalbutton_orange" href="#" onclick="creport_save_editor(\''+el_id+'\'); return false;"><span>'+lang['save']+'</span></a></span>');
		
	create_editor('report_element_texteditor_'+el_id);*/
	

	
	$(_this).css('display', 'none');
	var el = $(_this).parent().find('.creport_text_inline');

	var el_id = el.attr('el_id');
	
	
	
	el.replaceWith('<textarea id="report_element_texteditor_'+el_id+'" class="creport_textarea" style="width:99%">'+el.html()+'</textarea>'+
	'<span class="buttonwrapper floatLeft" style="margin:5px 0"><a class="ovalbutton ovalbutton_orange" href="#" onclick="creport_save_editor(\''+el_id+'\'); return false;"><span>'+lang['save']+'</span></a></span>');
		
	create_editor('report_element_texteditor_'+el_id);
}

/**
* shrani editor in nastavi nazaj contenteditable
*/
function creport_save_editor(el_id) {
	
	/*get_editor_close('report_element_texteditor_'+el_id);
	
	var el = $('#report_element_texteditor_'+el_id);
	var parent = el.parent();
	
	el.replaceWith('<textarea style="width:90%; height: 80px;" class="creport_textarea" el_id="'+el_id+'" id="report_element_text_'+el_id+'" onBlur="editCustomReportElement('+el_id+', text, this.value)">');
	parent.find('span.buttonwrapper').remove();

	editCustomReportElement(el_id, 'text', el.val());*/
	
	get_editor_close('report_element_texteditor_'+el_id);
	
	var el = $('#report_element_texteditor_'+el_id);
	var parent = el.parent();
	

	el.replaceWith('<div class="creport_text_inline" contenteditable="true" el_id="'+el_id+'">'+el.html+'</div>');
	parent.find('span.buttonwrapper').remove();

	editCustomReportElement(el_id, 'text', el.val());
}

function doArchiveCReport() {
	//preverimo ali obstaja vsebina breakResults
	if ($("#custom_report_elements").length > 0 && $("#custom_report_elements").html() != '') {
		$("#fullscreen").load('ajax.php?a=doArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran}, function() {

			$('#fade').fadeTo('slow', 1);
			$('#fullscreen').show();
		});	
	} else {
		alert ('Ni podatkov za arhiv! Najprej kreirajte tabele.');
	}
}
function submitArchiveCReport() {
	//preverimo ali obstaja vsebina meansa
	if ($("#custom_report_elements").html().length > 0 ) {
		//var content = $("#custom_report_elements").html();

		var name = $("#newAnalysisArchiveName").val();
		var note = $("#newAnalysisArchiveNote").val();
		var access = $("[name=newAnalysisArchiveAccess]:checked").val();
		var duration = $("#newAnalysisArchiveDuration").val();
		var durationType = $("[name=newAADurationType]:checked").val();
		$("#fullscreen").load('ajax.php?a=submitArchiveAnaliza', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, name:name, note:note, access:access, duration:duration, durationType:durationType}, function() {
			$("#fullscreen").show();
		});
	} else {
		alert ('Ni podatkov za arhiv! Najprej kreirajte tabele.');
	}
}

function createArchiveCReportBeforeEmail() {
	//preverimo ali obstaja vsebina custom_report_elements
	if ($("#custom_report_elements").html().length > 0 ) {
		//var content = $("#custom_report_elements").html();
		$.post('ajax.php?a=createArchiveBeforeEmail', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran/*, content:content*/}, function(response) {
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

function showCReportPreview() {	
	var size = "location=0,height=800,scrollbars=1,fullscreen=0,menubar=0,status=0,titlebar=0,toolbar=0,channelmode=0,directories=0";
	var recipe =  window.open('','RecipeWindow',size);
	
	$.post('ajax.php?t=custom_report&a=report_preview', {anketa: srv_meta_anketa_id}, function(response) {
	    recipe.document.open();
		
		recipe.document.write('<html><head><title>Predogled poro&#269;ila po meri</title>');
		
		recipe.document.write('<link rel="stylesheet" href="css/style_font.css">');
		recipe.document.write('<link rel="stylesheet" href="css/style_basic.css">');
		recipe.document.write('<link rel="stylesheet" href="css/style_main.css">');
		recipe.document.write('<link rel="stylesheet" href="css/style_new2.css">');
		recipe.document.write('<link rel="stylesheet" href="css/print.css">');
		recipe.document.write('<link rel="stylesheet" href="css/style_print.css" media="print">');
		
		recipe.document.write('<style>');
		recipe.document.write('.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}');
		recipe.document.write('</style>');
		
		recipe.document.write('</head><body>');
	    
		recipe.document.write(response);
		
		recipe.document.write('</body></html>');
		
	    recipe.document.close();
	    recipe.focus();
	    return false;    
	});
}

function showCReportProfiles(){
	$('#fade').fadeTo('slow', 1);
	
	$("#div_creport_settings_profiles").load('ajax.php?t=custom_report&a=creport_show_profiles', {anketa: srv_meta_anketa_id}, function(){
		$("#div_creport_settings_profiles").show(200);
	});	
	
	return false; // "capture" the click
}

function add_creport_profile() {
	$('#fade').fadeTo('slow', 1);	
    $("#newCReportProfile").show();
}
function delete_creport_profile() {
	$('#fade').fadeTo('slow', 1);	
    $("#deleteCReportProfile").show();
}

function close_creport_profile() {
    $("#div_creport_settings_profiles").fadeOut();
	$("#fade").fadeOut();
}
function use_creport_profile() {

	var value = $(".creport_profiles .active").attr('value');
	var author = $(".creport_profiles .active").attr('author');
	
	$.post('ajax.php?t=custom_report&a=use_creport_profile', {anketa:srv_meta_anketa_id, id:value, author:author}, function(){	
		var srv_site_url = $("#srv_site_url").val();
		srv_site_url += 'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=analysis&m=analysis_creport&expanded='+global_expanded;		
		window.location.href = srv_site_url;
	});
}

// popravljamo custom report profil
function creport_profile_action(action){
	
	// Rename actions
	if (action == 'show_rename') {
		$("#dsp_cover_div").show();
		$("#renameCReportProfile").show();
		
		// Popravimo naslov izbranega porocila
		var title = $("#creport_profiles").find('.active').html();
		$("#renameCReportProfileName").val(title);
	}
	else if(action == 'cancel_rename'){	
		$("#dsp_cover_div").hide();
		$("#renameCReportProfile").hide();
	}
	else if(action == 'rename'){	
		var id = $(".creport_profiles .active").attr('value');
		var name = $("#renameCReportProfileName").val();

		$("#div_creport_settings_profiles").load('ajax.php?t=custom_report&a=renameProfile', {anketa:srv_meta_anketa_id, id:id, name:name}, function() {
			$("#renameCReportProfile").hide();
			$("#dsp_cover_div").fadeOut();
		});
	}
	
	// Delete actions
	else if(action == 'show_delete'){	
		if($("#div_creport_settings_profiles").is(':visible'))
			$("#dsp_cover_div").show();	
		else
			$('#fade').fadeTo('slow', 1);	

		$("#deleteCReportProfile").show();
		
		// Popravimo naslov izbranega porocila
		var title = $("#creport_profiles").find('.active').html();
		$("#deleteCReportProfileName").html(title);
	}
	else if(action == 'cancel_delete'){		
		if($("#div_creport_settings_profiles").is(':visible')){
			$("#deleteCReportProfile").hide();
			$("#dsp_cover_div").hide();			
		}
		else{
			$("#deleteCReportProfile").hide();
			$("#fade").fadeOut();
		}
	}
	else if(action == 'delete'){		
		var id = $(".creport_profiles .active").attr('value');
		
		$("#div_creport_settings_profiles").load('ajax.php?t=custom_report&a=deleteProfile', {anketa:srv_meta_anketa_id, id:id}, function() {
			$("#deleteCReportProfile").hide();
			$("#dsp_cover_div").fadeOut();
		});
	}
	
	// Add actions
	else if(action == 'show_new'){		
		if($("#div_creport_settings_profiles").is(':visible'))
			$("#dsp_cover_div").show();		
		else
			$('#fade').fadeTo('slow', 1);	
		
		$("#newCReportProfile").show();
	}
	else if(action == 'cancel_new'){		
		if($("#div_creport_settings_profiles").is(':visible')){
			$("#newCReportProfile").hide();
			$("#dsp_cover_div").hide();
		}
		else{	
			$("#newCReportProfile").hide();
			$("#fade").fadeOut();
		}
	}
	else if(action == 'new'){	
		var name = $("#newCReportProfileName").val();
		var comment = $("#newCReportProfileComment").val();
		
		$.post('ajax.php?t=custom_report&a=newProfile', {anketa:srv_meta_anketa_id, name:name, comment:comment}, function() {		
			var srv_site_url = $("#srv_site_url").val();
			srv_site_url += 'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=analysis&m=analysis_creport';		
			window.location.href = srv_site_url;
		});	
	}
	
	// Share actions
	if (action == 'show_share') {
		var id = $(".creport_profiles .active").attr('value');
		
		$("#dsp_cover_div").show();
		$("#shareCReportProfile").load('ajax.php?t=custom_report&a=shareProfileShow', {anketa:srv_meta_anketa_id, id:id}, function(){
			$("#shareCReportProfile").show(200);
		});
	}
	else if(action == 'cancel_share'){	
		$("#dsp_cover_div").hide();
		$("#shareCReportProfile").hide();
	}
	else if(action == 'share'){	
		var id = $(".creport_profiles .active").attr('value');
		
		var users = [];	
		$("#shareCReportProfile input:checked").each(function() {
			users.push($(this).val());
		});

		$("#div_creport_settings_profiles").load('ajax.php?t=custom_report&a=shareProfile', {anketa:srv_meta_anketa_id, id:id, users:users}, function() {
			$("#shareCReportProfile").hide();
			$("#dsp_cover_div").fadeOut();
		});
	}
}


// urejanje komentarja profila 
function creport_profile_comment(value){

	$.post('ajax.php?t=custom_report&a=edit_profile_comment', {anketa: srv_meta_anketa_id, value:value});
}