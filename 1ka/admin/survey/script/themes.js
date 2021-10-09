// onLoad -> themes_init
function themes_init() {

	$("#edit_theme_css.as_link").live('click', function(event) {
		window.location = 'index.php?anketa='+srv_meta_anketa_id+'&a=edit_css';
	});
	
	$("#theme_progressbar input:radio").live('change', function(event) {
		var progressbar = $(this).val();
		$.post("ajax.php?t=theme&a=changeProgressbar", {anketa:srv_meta_anketa_id, progressbar:progressbar}, function() {});
	});
	
	$("a.theme_links_preview").live('click', function(event) {
		event.preventDefault();
		event.stopPropagation();
		window.open($(this).attr('src')+'&size=full', '_blank');
	});
	
	$("a.theme_delete.theme").live('click', function(event) {
		event.preventDefault();
		event.stopPropagation();
		
		if (confirm(lang['srv_ask_delete'])) {
			var css = $(this).attr('css');
			$.post('ajax.php?t=theme&a=theme_delete', {anketa:srv_meta_anketa_id, css:css}, function () {
				window.location.reload();
			});
		}
	});

	/*$("#div_theme_group span.theme_links_rename").live('click', function(event) {
		var theme = $(this).attr('theme');
		$('#fade').fadeTo('slow', 1);
		$('#fullscreen').html('').fadeIn('slow');
		$("#fullscreen").load('ajax.php?t=theme&a=theme_rename', {anketa:srv_meta_anketa_id, theme:theme});	
	});*/
    
	$("#theme_rename_confirm").live('click', function(event) {
		var theme_new_name = $("#theme_new_name").val(); 
		var theme = $("#theme").val();
		
		$.post('ajax.php?t=theme&a=theme_rename_confirm', {anketa:srv_meta_anketa_id, theme:theme, theme_new_name:theme_new_name}, function(data) {
			data = jQuery.parseJSON(data);
			if (data.error == "0") {
				var gid = $("#sel_theme_group").val();
				$("#div_theme_group_holder").load("ajax.php?t=theme&a=changeGroup", {anketa:srv_meta_anketa_id, gid:gid}, function() {
					// popravimo ime teme:
					$("#div_theme_groups span.theme_header strong").html(data.theme_new_name);
					$('#fade').fadeOut('slow');
					$('#fullscreen').fadeOut('slow').html('');
					return false;
				});
				return false;
			} else {
				$("#fullscreen").load('ajax.php?t=theme&a=theme_rename', {anketa:srv_meta_anketa_id, theme:data.theme, theme_new_name:data.theme_new_name, msg:data.msg});
			}
		});
		return false;
	});
	
	$("#theme_rename_cancle").live('click', function(event) {
		$('#fade').fadeOut('slow');
		$('#fullscreen').fadeOut('slow').html('');
		return false;
	});
	
	// change theme
	$("#div_theme_group img.theme").live('click', function(event) {
		event.preventDefault();
        event.stopPropagation();
        var css = $(this).attr('css');
        var gid = $(this).attr('gid');
        $.post("ajax.php?t=theme&a=changeTheme", {anketa:srv_meta_anketa_id, gid:gid,css:css}, function(data) {
    		$("#div_theme_group_holder").html(data.group_themes);
    		$('#div_theme_groups').html(data.theme_name);
    		$('#div_theme_group_holder').append('<div id="success_save">'+lang['srv_success_save']+'</div>');
    		show_success_save();
    	}, 'json');
	});

    //change checkbox value
    $('#izbira-checkbox-gumbov').change(function(){
        var izbira = $(this).find(":selected").val();
        $.post('ajax.php?t=checboxChangeTheme&a=checboxThemeSave', {
            anketa: srv_meta_anketa_id,
            checkbox: izbira
        });
    });
}

function theme_changeGroup() {
	var gid = $("#sel_theme_group").val();
	
	$("#div_theme_group_holder").load("ajax.php?t=theme&a=changeGroup", {anketa:srv_meta_anketa_id, gid:gid});
}

function add_theme () {
    
    $('#fade').fadeTo('slow', 1);
	$('#vrednost_edit').load('ajax.php?t=theme&a=add_theme', {anketa: srv_meta_anketa_id}).show();	
}

/* -- theme editor -- */

/**
* Init theme editor
*/
function init_themeEditor(id) {
	
	// init farbtastic
	var f = $.farbtastic('#picker');
	var p = $('#picker').hide();
	var selected;
	$('.colorwell')
	  .each(function () { f.linkTo(this); $(this).css('opacity', 0.75); })
	  .focus(function() {
		if (selected) {
		  $(selected).css('opacity', 0.75).removeClass('colorwell-selected');
		}
		f.linkTo(this);
		p.show();
		$(selected = this).css('opacity', 1).addClass('colorwell-selected');
	  });
	
	// progress bar
	$("#theme_progressbar input:radio").live('change', function(event) {
		var progressbar = $(this).val();
		$.post("ajax.php?t=theme&a=changeProgressbar", {anketa:srv_meta_anketa_id, progressbar:progressbar}, function() {
			var iframe = document.getElementById('theme-preview-iframe');
			iframe.src = iframe.src;
		});
	});
	
	// init auto save
	$('.auto-save').change(function () {
		te_auto_save(this, true);
	}).blur(function () {
		te_auto_save(this, true);
		$('#picker').hide();
	});
}

function te_auto_save (_this, refresh) {
	
	var value = $(_this).val();
	
	// za checkbox popravimo vrednost
	if($(_this).is(':checkbox')){
		if($(_this).is(':checked'))
			value = 1;
		else
			value = 0;
	}
	
	$.post('ajax.php?t=themeEditor&a=auto_save&profile='+$('#profile').val()+'&mobile='+$('#mobile').val(), {anketa: srv_meta_anketa_id, id: $(_this).attr('data-id'), type: $(_this).attr('data-type'), value: value }, function () {
		
		// refresh iframe
		if (refresh) {
			var iframe = document.getElementById('theme-preview-iframe');
			iframe.src = iframe.src;
		}	
	});
}

function te_remove_setting (id, type) {
	
	$.post('ajax.php?t=themeEditor&a=auto_save&profile='+$('#profile').val()+'&mobile='+$('#mobile').val(), {anketa: srv_meta_anketa_id, id: id, type: type, value: '' }, function () {
		
		window.location.reload();		
	});	
}

function te_change_profile (profile, redirect, mobile_skin) {
	
	var mobile = 0;
	if(mobile_skin === true)
		mobile = 1;
	
	$.post('ajax.php?t=themeEditor&a=change_profile&profile='+profile+'&mobile='+mobile, {anketa: srv_meta_anketa_id}, function () {
		
		if (redirect === true)
			window.location = 'index.php?anketa='+srv_meta_anketa_id+'&a=tema';		
	});
}

function te_change_profile_oldskin (skin, refresh) {
	
	$.post('ajax.php?t=themeEditor&a=change_profile_oldskin', {skin: skin, anketa: srv_meta_anketa_id}, function (data) {
		
		if (refresh === true)
			window.location = 'index.php?anketa='+srv_meta_anketa_id+'&a=tema';
		else
			window.location = data;
	});	
}

function te_delete_profile (profile, mobile_skin) {
	
	var mobile = 0;
	if(mobile_skin === true)
		mobile = 1;
	
	$.post('ajax.php?t=themeEditor&a=delete_profile&profile='+profile+'&mobile='+mobile, {anketa: srv_meta_anketa_id}, function () {
		
		window.location = 'index.php?anketa='+srv_meta_anketa_id+'&a=tema';		
	});	
}

function te_add_theme () {
    
    $('#fade').fadeTo('slow', 1);
	$('#vrednost_edit').load('ajax.php?t=themeEditor&a=add_theme', {anketa: srv_meta_anketa_id}).show();
}

function te_change_name (_this) {
	
	$.post('ajax.php?t=themeEditor&a=change_name&profile='+$('#profile').val()+'&mobile='+$('#mobile').val(), {anketa: srv_meta_anketa_id, name: $(_this).val()}, function () {
		
		$('select[name=profile-select] option[value='+$('#profile').val()+']').html( $(_this).val() );
	});
}

function toggle_custom_themes () {
	
	$('.user_themes_button').toggleClass("plus minus");
	$('#div_theme_group.custom').toggle('medium');
}