function charts_init () {

	// Izbira skina grafov
	$("#link_chart_color").on("click", function(event) {
		if (event.button != 0) { // wasn't the left button - ignore
			return true;
		}
		analiza_show_chart_color();
		return false; // "capture" the click
	});
	
	// preview za skine grafov
	/*$("#chart_skin").live("change", function(event) {
		$(".div_chart_skin_preview").hide();
		$("#div_chart_skin_preview_"+this.value).show();
	});
	// preview za skine grafov - mouseover (ne dela v IE)
	$('#chart_skin > option').live('mouseover', function(){ 
		$(".div_chart_skin_preview").hide();
		$("#div_chart_skin_preview_"+this.value).show();
    });*/
	
	$('.chart_holder,.tableChart').on({
		mouseenter: function(){
			$(this).find('.chart_settings').stop().animate({opacity:1},  600);
		},
		mouseleave: function(){
			$(this).find('.chart_settings').stop().animate({opacity:0},  600);
		}
	});
	

	
	// klik na link kateri odpre okno z nastavitvami profilov intervalov
	$("#dsp_link, #link_variableType_profile_setup").live("click", function(event) {
		if (event.button != 0) { // wasn't the left button - ignore
			return true;
		}
		dataSettingProfileAction('showProfiles');
		return false; // "capture" the click
	});
	// klik na link kateri odstrani filtre kategorij
	$("#link_variableType_profile_remove").live("click", function(event) {
		if (event.button != 0) { // wasn't the left button - ignore
			return true;
		}
		dataSettingProfileAction('removeKategoriesProfile');
		return false; // "capture" the click
	});
	
	$(".chart_profiles").live('click', function(event) {
		var $target = $(event.target);
		if ($target.hasClass('option')) {
			var skin = $target.attr('value');
				
			$("#div_chart_settings_profiles").load('ajax.php?t=charts&a=analiza_change_chart_color', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, skin:skin});
		}
	});
}
	

// Preklop grafa ( tip -> krozni, stolpicni..., sort, prikaz podatkov -> frekvence, procenti...)
function changeChart(spid, spr_type, what, loop) {

	if(what == 'show_legend' || what == 'barLabel' || what == 'barLabelSmall' || what == 'show_numerus' || what == 'show_avg' || what == 'scale_limit' || what == 'open_up' || what == 'open_down' || what == '3d_pie' || what == 'hideEmptyVar'|| what == 'noFixedScale'){
		if(document.getElementById('chart_'+what+'_'+spid+'_loop_'+loop).checked){
			var value = 1;
		}
		else{
			var value = 0;
		}
	}
	else{
		var value = document.getElementById('chart_'+what+'_'+spid+'_loop_'+loop).value;
	}
	
	// Ohranjamo pravi zavihek v nastavitvah (osnovno/napredno)
	if($("#chart_settings_basic_"+spid+'_loop_'+loop).css("display") == 'none'){
		var settings_mode = 1;
	}
	else{
		var settings_mode = 0;
	}
	
	
	//$('#chart_'+spid+'_loop_'+loop).load('ajax.php?t=charts&a=change_chart', {anketa:srv_meta_anketa_id, spid:spid, loop:loop, spr_type:spr_type, what:what, value:value, settings_mode:settings_mode});	
	$.post('ajax.php?t=charts&a=change_chart', {anketa:srv_meta_anketa_id, spid:spid, loop:loop, spr_type:spr_type, what:what, value:value, settings_mode:settings_mode}, function(data) {
		$('#chart_'+spid+'_loop_'+loop).replaceWith(data);
	});
}

// Nastavitve tabele other
function changeOther(spid, what, loop) {
	
	if(document.getElementById('chart_other_'+what+'_'+spid+'_loop_'+loop).checked){
		var value = 1;
	}
	else{
		var value = 0;
	}
	
	$('#chart_other_text_'+spid+'_loop_'+loop).load('ajax.php?t=charts&a=change_other', {anketa:srv_meta_anketa_id, spid:spid, loop:loop, what:what, value:value});
}

function changeChartHq(){
	
	if($('#chart_hq').is(':checked')){
		var value = 1;
	}
	else{
		var value = 0;
	}
	
	if(value == 1){
		$.post('ajax.php?a=outputLanguageNote', {note: 'srv_chart_hq_warning'}, function(lang_note) {
			if (confirm(lang_note)) {		
				$.post('ajax.php?t=charts&a=change_hq_settings', {anketa:srv_meta_anketa_id, value:value, podstran:'charts'}, function() {
					window.location.reload();
				});
			}
			else{
				$('#chart_hq').attr('checked', false);
			}
		});
	}
	else{
		$.post('ajax.php?t=charts&a=change_hq_settings', {anketa:srv_meta_anketa_id, value:value, podstran:'charts'}, function() {
			window.location.reload();
		});
	}
}

// Preklop grafa pri grafih tabel (crosstab, ttest, mean)
function changeTableChart(chartID, podstran, what) {

	if(what == 'barLabel' || what == 'barLabelSmall' || (what == 'sort' && podstran != 'break') || what == 'show_numerus'){
		if(document.getElementById('tablechart_'+what+'_'+chartID).checked){
			var value = 1;
		}
		else{
			var value = 0;
		}
	}
	else if(what == 'hq'){
		if(document.getElementById('tablechart_'+what+'_'+chartID).checked){
			var value = 3;
		}
		else{
			var value = 1;
		}
	}
	else{
		var value = document.getElementById('tablechart_'+what+'_'+chartID).value;
	}

	
	// V breaku ne refreshamo cele strani
	var url = window.location.search.substring(1);
	var url_variables = url.split('&');
	for (var i = 0; i < url_variables.length; i++) 
	{
		var sParameterName = url_variables[i].split('=');
		if (sParameterName[0] == 'm') 
		{
			var location = sParameterName[1];
		}
	}
	
	if(location == 'break'){
		$('#tableChart_'+chartID).load('ajax.php?t=table_chart&a=change_chart', {anketa:srv_meta_anketa_id, chartID:chartID, podstran:podstran, what:what, value:value});	
	}
	else{	
		$.post('ajax.php?t=table_chart', {anketa:srv_meta_anketa_id, chartID:chartID, podstran:podstran, what:what, value:value}, function(){	
			window.location.reload();
		});
	}
}

function showTableChart (podstran) {
	var showChart = $("#showChart").is(':checked');

	if(podstran == 'crosstab'){
		$.post("ajax.php?t=crosstab&a=change_show_chart", {anketa:srv_meta_anketa_id, showChart:showChart}, function() {
			change_crosstab();
		});
	}
	else if(podstran == 'mean'){
		$.post("ajax.php?t=means&a=changeMeansShowChart", 
			{anketa:srv_meta_anketa_id, showChart:showChart}, 
			function(response) {
				change_means();
			}
		);
	}
	else if(podstran == 'hierarhy_mean'){
		$.post("ajax.php?t=hierarhy-means&a=changeMeansShowChart",
			{anketa:srv_meta_anketa_id, showChart:showChart},
			function(response) {
				change_hierarhy_means();
			}
		);
	}
	else if(podstran == 'ttest'){
		$.post("ajax.php?t=ttest&variableChange", {anketa:srv_meta_anketa_id, showChart:showChart}, function() {
			window.location.reload();			
		});
	}
}

function clearCache(){
	$.post('ajax.php?t=charts&a=clear_cache', {anketa:srv_meta_anketa_id});
}


// prikaze napredno urejanje grafa
function chartAdvancedSettings (spremenljivka, tab, loop) {
		
	var id = '#chart_'+spremenljivka+'_loop_'+loop;
	
	$('#fade').fadeTo('slow', 1, function(){
				
		$('#chart_float_editing').html('').show().css('visibility', 'hidden');	// da delajo moseover dropdowni v IE8 mora bit ze tuki show(), potem pa skrijemo z visibility
		
		$.post('ajax.php?t=charts&a=chart_advanced_settings', {spid: spremenljivka, loop: loop, anketa: srv_meta_anketa_id}, function(data) {
			
			$('#chart_float_editing').html(data).css('visibility', 'visible');
			
			// nastavimo visino okna
			var height = $('#chartSettingsArea1').height();
			if(height < 290){ height = 290; }
			$('#chart_float_editing').height(height+145).show();
			
			// se preklopimo na pravi zavihek ce je potrebno
			if(tab == 2 || tab == 3 || tab == 4){
				chartTabAdvancedSettings(tab);
			}
			
		});
	});
}

// zapre napredno urejanje grafa
function chartCloseAdvancedSettings () {
	
	$('#fade').fadeOut('slow');
	$('.chartSettingsArea').css('visibility', 'hidden');
	$('#chart_float_editing').css('visibility', 'hidden');	
}

// zapre napredno urejanje grafa
function chartSaveAdvancedSettings (spid, loop) {
	
	// Ohranjamo pravi zavihek v nastavitvah (osnovno/napredno)
	if($("#chart_settings_basic_"+spid+"_loop_"+loop).css("display") == 'none'){
		var settings_mode = 1;
	}
	else{
		var settings_mode = 0;
	}
	
	var form = $("form[name=chart_advanced_settings]").serialize() + '&settings_mode=' + settings_mode;
	
	// Preverimo ce imamo vklopljene number napredne meje - potem morajo biti vnesena vsa polja za meje intervalov
	var checkEmpty = chartAdvancedSettingsLimitEmpty();
	
	if(checkEmpty == true){
		genericAlertPopup('srv_chart_num_limit_warning');
	}
	else{
		$.post('ajax.php?t=charts&a=chart_save_advanced_settings', form, function(data) {
			
			$('#fade').fadeOut('slow');
			$('.chartSettingsArea').css('visibility', 'hidden');
			$('#chart_float_editing').css('visibility', 'hidden');	
			$('#chart_'+spid+'_loop_'+loop).html(data);
		});
	}
}

// prikaze napredno urejanje grafa
function chartTabAdvancedSettings (tab) {
	
	$('.chartSettingsArea').css('visibility', 'hidden');
	$('.chartTab').removeClass('active');
	
	$('#chartSettingsArea'+tab).css('visibility', 'visible');
	$('#chartTab'+tab).addClass('active');
}

// spremenimo skalo (ordinalna nominalna) za tipe 1,3,6
function chartAdvancedSettingsSkala(spid, skala, loop) {
	$.post('ajax.php?t=charts&a=chart_advanced_settings_skala', {spid: spid, loop: loop, skala: skala, anketa: srv_meta_anketa_id}, function() {
		window.location.reload();
	});
}

// nastavimo barvo barvo posameznega grafa
function chartAdvancedSettingsSetColor (skin) {
	
	var colors = new Array();
	
	// custom globalen skin
	if(skin.charAt(0) == '#'){
		colors = skin.split('_');
	}
	
	else{
		switch(skin){	
            case '1ka':	
				colors = ["#1e88e5", "#ffa608", "#48e5c2", "#f25757", "#754668", "#f8ca00", "#ff70a6"];
				break;
			case 'lively':	
				colors = ["#e9090d", "#0417e3", "#00ff08", "#fff703", "#ff9500", "#00fbff", "#a600ff"];
				break;
			case 'mild':	
				colors = ["#bce02e", "#e0642e", "#e0d62e", "#2e97e0", "#b02ee0", "#00fbff", "#5ce02e"];
				break;
			case 'office':	
				colors = ["#4f81bd", "#c0504d", "#9bbb59", "#8064a2", "#4bacc6", "#f79646", "#92a9cf"];
				break;
			case 'pastel':	
				colors = ["#799f0b", "#d7a125", "#9264be", "#188484", "#4cc68b", "#8a8823", "#6c99d2"];
				break;
			case 'green':	
				colors = ["#a8bc38", "#b8c948", "#c8d658", "#d8e468", "#e8e178", "#ffff00", "#e803b6"];
				break;
			case 'blue':	
				colors = ["#1e88e5", "#4f97ea", "#6ea6ee", "#89b5f3", "#a2c4f7", "#bad3fb", "#d1e3ff"];
				break;
			case 'red':	
				colors = ["#ff0000", "#dc0202", "#b90404", "#960606", "#730808", "#ffff00", "#e803b6"];
				break;
			case 'multi':	
				colors = ["#8c0000", "#f00800", "#ff8a82", "#f2c4c8", "#0b0387", "#0400fc", "#9794f2"];
				break;	
			default:
				break;
		}
	}

	for(var i=0; i<7; i++){
		var value = colors[i];
		$('#color'+(i+1)).attr("value", value);		
	}
	
	// refreshamo color picker
	var fb = $.farbtastic('#picker');		
	$('.colorwell').each( function () {
		fb.linkTo(this);
	});
}

// preklop med number limits nastavitvami (osnovne/napredne)
function chartAdvancedSettingsLimitSwitch(mode){
	
	if(mode == 1){
		$("#chart_number_limits_basic").hide('fast');
		$("#chart_number_limits_advanced").show('fast');
	}
	else{
		$("#chart_number_limits_advanced").hide('fast');
		$("#chart_number_limits_basic").show('fast');
	}
}

// preklop med number limits nastavitvami (osnovne/napredne)
function chartAdvancedSettingsLimitInterval(interval, spid, loop){
	
	$("#chartSettingsArea4").load('ajax.php?t=charts&a=analiza_num_limit_interval', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, spid: spid, loop: loop, interval: interval});	
}

// vnos min/max prenesemo v labelo
function chartAdvancedSettingsLimitLabel(interval, spid, loop){
	
	var min = $("#interval_" + interval + "_min_" + spid + "_loop_" + loop).val();
	var max = $("#interval_" + interval + "_max_" + spid + "_loop_" + loop).val();

	$("#interval_" + interval + "_label_" + spid + "_loop_" + loop).val(min + "-" + max);
}

// preverjamo vnos posameznih intervalov in prikazemo opozorilo ce niso urejeni po velikosti
function chartAdvancedSettingsLimitCheck(interval, spid, loop){
	
	var min = parseFloat($("#interval_" + interval + "_min_" + spid + "_loop_" + loop).val());
	var max = parseFloat($("#interval_" + interval + "_max_" + spid + "_loop_" + loop).val());

	if(interval > 0)
		var prev_max = parseFloat($("#interval_" + (parseInt(interval) - 1) + "_max_" + spid + "_loop_" + loop).val());
		
	if($("#interval_" + (parseInt(interval) + 1) + "_min_" + spid + "_loop_" + loop).length != 0)
		var next_min = parseFloat($("#interval_" + (parseInt(interval) + 1) + "_min_" + spid + "_loop_" + loop).val());
	
	
	if(min >= max && min != '' && max != ''){
		$("#chart_advanced_warning_1_interval_" + interval).show();
	}
	else{
		$("#chart_advanced_warning_1_interval_" + interval).hide();
	}
	
	if(prev_max >= min && prev_max != '' && min != ''){
		$("#chart_advanced_warning_2_interval_" + interval).show();
	}
	else{
		$("#chart_advanced_warning_2_interval_" + interval).hide();
	}
	
	if(next_min <= max && next_min != '' && max != ''){
		$("#chart_advanced_warning_2_interval_" + (parseInt(interval) + 1)).show();
	}
	else{
		$("#chart_advanced_warning_2_interval_" + (parseInt(interval) + 1)).hide();
	}
}

function chartAdvancedSettingsLimitEmpty(){

	var checkEmpty = false;
	
	// Ce gre za nastavitve number grafa (kjer imamo meje)
	if($("#chartSettingsArea4").length != 0){

		// Ce smo v naprednem nacinu
		if($('input[name=chart_number_limits_switch]:checked').val() == '1'){
			textboxes = $('#chart_number_limits_advanced').find('.advanced_interval');
			textboxes.each( function(){
				if(this.value.length == 0){
					checkEmpty = true;
					return false;
				}
			});
		}
	}
	
	return checkEmpty;
}

// prikaze napredno urejanje grafa
function tableChartAdvancedSettings (chartID, podstran) {
	
	$('#fade').fadeTo('slow', 1, function(){	
		
		$('#chart_float_editing').html('').show().css('visibility', 'hidden');	// da delajo moseover dropdowni v IE8 mora bit ze tuki show(), potem pa skrijemo z visibility
		
		$.post('ajax.php?t=table_chart&a=table_chart_advanced_settings', { chartID:chartID, podstran:podstran, anketa: srv_meta_anketa_id}, function(data) {
			
			$('#chart_float_editing').html(data).css('visibility', 'visible');
			
			// nastavimo visino okna
			var height = $('#chartSettingsArea1').height();
			if(height < 290){ height = 290; }
			$('#chart_float_editing').height(height+145).show();			
		});
	});
}

// shrani napredno urejanje grafa
function tableChartSaveAdvancedSettings (chartID) {
		
	// V breaku ne refreshamo cele strani
	var url = window.location.search.substring(1);
	var url_variables = url.split('&');
	for (var i = 0; i < url_variables.length; i++) 
	{
		var sParameterName = url_variables[i].split('=');
		if (sParameterName[0] == 'm') 
		{
			var location = sParameterName[1];
		}
	}
	
	if(location == 'break'){
		var form = $("form[name=table_chart_advanced_settings]").serializeArray();
	
		$('#tableChart_'+chartID).load('ajax.php?t=table_chart&a=chart_reload_advanced_settings', form, function(data) {
			
			$('#fade').fadeOut('slow');
			$('.chartSettingsArea').css('visibility', 'hidden');
			$('#chart_float_editing').css('visibility', 'hidden');	
		});
	}
	
	else{
		var form = $("form[name=table_chart_advanced_settings]").serialize();
		
		$.post('ajax.php?t=table_chart&a=chart_save_advanced_settings', form, function(data) {
			
			$('#fade').fadeOut('slow');
			$('.chartSettingsArea').css('visibility', 'hidden');
			$('#chart_float_editing').css('visibility', 'hidden');	
			//$('#tableChart_'+chartID).html(data);
			
			window.location.reload();
		});
	}
}


// Omogoci/onemogoci editiranje label za grafe
function edit_labels(val){
	if(val == 0) {
		$('.chart_editing :input').attr('disabled', true);
    } 
	else {
        $('.chart_editing :input').removeAttr('disabled');
	}
}

// Preklop med navadnimi nastavitvami in naprednimi na desni
function chartSwitchSettings(spid, mode, loop){
	
	if(mode == 1){
		$("#chart_settings_advanced_"+spid+"_loop_"+loop).show();
		$("#chart_settings_basic_"+spid+"_loop_"+loop).hide();

		$("#switch_left_"+spid+"_loop_"+loop).addClass('non-active');
		$("#switch_right_"+spid+"_loop_"+loop).removeClass('non-active');
		
		$("#switch_middle_"+spid+"_loop_"+loop).attr('class', 'rightHighlight');
	}
	else{
		$("#chart_settings_basic_"+spid+"_loop_"+loop).show();
		$("#chart_settings_advanced_"+spid+"_loop_"+loop).hide();
		
		$("#switch_left_"+spid+"_loop_"+loop).removeClass('non-active');
		$("#switch_right_"+spid+"_loop_"+loop).addClass('non-active');
		
		$("#switch_middle_"+spid+"_loop_"+loop).attr('class', 'leftHighlight');
	}
}



// nastavitve za grafe - skin, globalne nastavitve
function analiza_show_chart_color() {
	$('#fade').fadeTo('slow', 1);
	
	$("#div_chart_settings_profiles").load('ajax.php?t=charts&a=analiza_show_chart_color', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran}, function(){
		$("#div_chart_settings_profiles").show(200);
		
		var skin = $("#chart_skin :selected").val();
		if(skin != -1){
			$("#div_chart_skin_preview_"+skin).show();
		}
	});	
}

function changeChartGlobalSettings(what, value){
	
	if(what == 'numbering' || what == 'frontpage' || what == 'otherTables' || what == 'textTables'){
		if($('#chart_'+what).is(':checked')){
			value = 1;
		}
		else{
			value = 0;
		}
	}
	
	$.post('ajax.php?t=charts&a=change_global_settings', {anketa:srv_meta_anketa_id, what:what, value:value, podstran:'charts'});
}

function change_chart_color(value){
	
	// custom skin
	if(value == -1){
		$("#div_chart_skin_previews").hide();
		$("#chart_custom_skin").show();		
	}
	else{
		$("#chart_custom_skin").hide();		
		
		var skin = $("#chart_skin :selected").val();
		$("#div_chart_skin_previews").show(200, function() {
			$("#div_chart_skin_preview_"+skin).show();
		});		
	}
}

function close_chartColor() {
    $("#div_chart_settings_profiles").fadeOut();
	$("#div_chart_skin_previews").fadeOut();
	$("#fade").fadeOut();
    /*reloadData();	*/
}
function save_chartColor() {

	var what = 'skin';
	//var value = document.getElementById('chart_skin').value;
	var value = $(".chart_profiles .active").attr('value');
	
	
	// Shranimo custom skin
	if($(".chart_profiles .active").parent().attr('id') == 'chart_profiles_custom'){
		
		// Preberemo nastavljene barve (ce so slucajno spremenjene)
		colors = '';
		for(var i=0; i<7; i++){
			colors = colors + document.getElementById('color' + (i+1)).value;
			if(i<6){
				colors = colors + "_";
			}
		}
		
		$.post('ajax.php?t=charts&a=editSkin', {anketa:srv_meta_anketa_id, id:value, colors:colors}, function(){
			$.post('ajax.php?t=charts&a=save_global_settings', {anketa:srv_meta_anketa_id, what:what, value:value, podstran:'charts'}, function(){	
				
				var srv_site_url = $("#srv_site_url").val();
				srv_site_url += 'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=analysis&m=charts&refresh=1';			
				window.location.href = srv_site_url;
				
				//reloadData();
			});
		});
	}
	
	// Sranimo prednastavljen skin
	else{	
		$.post('ajax.php?t=charts&a=save_global_settings', {anketa:srv_meta_anketa_id, what:what, value:value, podstran:'charts'}, function(){	
			var srv_site_url = $("#srv_site_url").val();
			srv_site_url += 'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=analysis&m=charts';		
			window.location.href = srv_site_url;
			//reloadData();
		});
	}
}

// Preimenujemo custom skin
function chart_skin_action(action){
	
	if (action == 'show_rename') {
		$("#dsp_cover_div").show();
		$("#renameChartSkin").show();
	}
	else if(action == 'cancel_rename'){
		$("#dsp_cover_div").hide();
		$("#renameChartSkin").hide();
	}
	else if(action == 'rename'){
		var id = $(".chart_profiles .active").attr('value');
		var name = $("#renameChartSkinName").val();
		
		$("#div_chart_settings_profiles").load('ajax.php?t=charts&a=renameSkin', {anketa:srv_meta_anketa_id, id:id, name:name}, function() {
			$("#renameChartSkin").hide();
			$("#dsp_cover_div").fadeOut();
		});
	}
	
	else if(action == 'show_delete'){
		$("#dsp_cover_div").show();
		$("#deleteChartSkin").show();
	}
	else if(action == 'cancel_delete'){
		$("#dsp_cover_div").hide();
		$("#deleteChartSkin").hide();
	}
	else if(action == 'delete'){
		var id = $(".chart_profiles .active").attr('value');
		
		$("#div_chart_settings_profiles").load('ajax.php?t=charts&a=deleteSkin', {anketa:srv_meta_anketa_id, id:id}, function() {
			$("#deleteChartSkin").hide();
			$("#dsp_cover_div").fadeOut();
		});
	}
	
	else if(action == 'show_new'){
		$("#dsp_cover_div").show();
		$("#newChartSkin").show();
	}
	else if(action == 'cancel_new'){
		$("#dsp_cover_div").hide();
		$("#newChartSkin").hide();
	}
	else if(action == 'new'){
		var name = $("#newChartSkinName").val();
		
		var colors = '';
		for(var i=0; i<7; i++){
			colors = colors + document.getElementById('color' + (i+1)).value;
			if(i<6){
				colors = colors + "_";
			}
		}
		
		$("#div_chart_settings_profiles").load('ajax.php?t=charts&a=newSkin', {anketa:srv_meta_anketa_id, name:name, colors:colors}, function() {
			$("#newChartSkin").hide();
			$("#dsp_cover_div").fadeOut();
		});
	}

}

// preveri number field, cela - stevilo celih mest, dec - stevilo decimalnih mest
function checkNumber (field, cela, dec, absolute, min, max) {
    var val = field.value;
    var okval = '';
    var decimal = false;
    var separator = false;
    
    for (var i=0; i<val.length; i++) {
        
        if (val.charAt(i) != ' ' && val.charAt(i) >= 0 && val.charAt(i) <= 9 
                && (absolute ? !(i == 0 && val.charAt(i) == 0) : true)) {

            if (!decimal) {
                
                if (cela > 0) {
                    okval = okval + val.charAt(i);
                    cela = cela - 1;
                }
                
            } else {
                
                if (dec > 0) {
                    okval = okval + val.charAt(i);
                    dec = dec - 1;
                }
                
            }
            
        } else if (val.charAt(i) == '.' || val.charAt(i) == ',') {

            if (i == 0 || dec == 0) 
				break;
            
			if (!separator)
                okval = okval + '.';
            
            separator = true;
            decimal = true;
            
        } else if (!absolute && i == 0 && val.charAt(i) == '-') {
            okval = okval + '-';
        }
    }

    if (min && parseFloat(okval) < min)
        field.value = min;
    else if (max && parseFloat(okval) > max)
        field.value = max;
    else if (val != okval)
        field.value = okval;    
}