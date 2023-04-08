function dataSetingProfile_init() {
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
	
	$("#dsp_profiles").live('click', function(event) {
		var $target = $(event.target);
		if ($target.hasClass('option')) {
			pid = $target.attr('value');
			$.post( 'ajax.php?t=dataSettingProfile&a=changeProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran,pid:pid }, function() {
				changeViewDataSetingProfile(pid);
			});
		}
	});
};

function changeViewDataSetingProfile(pid) {
	$("#dsp_div").load( 'ajax.php?t=dataSettingProfile&a=showProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran, pid:pid });	
}

function dataSettingProfileAction(action) {
	if (action == 'showProfiles') {
		$('#fade').fadeTo('slow', 1);
		// poiščemo center strani
		$("#dsp_div").show().load( 'ajax.php?t=dataSettingProfile&a=showProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran });
		
	} else if (action == 'cancel'){
		return reloadData('dataSetting');
	} else if (action == 'change_profile'){
		var pid = $("#dsp_dropdown").val();
		
		$.post( 'ajax.php?t=dataSettingProfile&a=changeProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran,pid:pid }, function() {
			return reloadData('dataSetting');
		});
	} else if (action == 'show_create'){
		$("#dsp_cover_div").show();
		$("#newProfileDiv").show();
	} else if (action == 'cancel_create'){
		$("#dsp_cover_div").hide();
		$("#newProfileDiv").hide();
	} else if (action == 'do_create'){
		var profileName = $("#newProfileName").val();
		$("#dsp_div").load('ajax.php?t=dataSettingProfile&a=createProfile', {anketa: srv_meta_anketa_id, podstran: srv_meta_podstran, profileName:profileName}, function(newId) {
			$("#dsp_div").load( 'ajax.php?t=dataSettingProfile&a=showProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran , pid:newId});
		});
	} else if (action == 'removeKategoriesProfile'){
		var pid = $("#dsp_dropdown").val();
		//$.post( 'ajax.php?t=dataSettingProfile&a=removeKategoriesProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran,pid:pid }, function() {
		$.post( 'ajax.php?t=dataSettingProfile&a=removeKategoriesProfile', { anketa : srv_meta_anketa_id, meta_akcija : srv_meta_akcija, podstran : srv_meta_podstran, pid:pid }, function() {
			return reloadData('dataSetting');
		});
	} else if (action == 'show_rename'){
		$("#dsp_cover_div").show();
		$("#renameProfileDiv").show();
	} else if (action == 'cancel_rename'){
		$("#dsp_cover_div").hide();
		$("#renameProfileDiv").hide();
	} else if (action == 'do_rename'){
		var pid = $("#dsp_profiles .active").attr('value');
		var name = $("#renameProfileName").val();
		$.post('ajax.php?t=dataSettingProfile&a=renameProfile', { anketa : srv_meta_anketa_id, pid : pid, name:name  }, function() {
			$("#dsp_div").load( 'ajax.php?t=dataSettingProfile&a=showProfile', {
				anketa : srv_meta_anketa_id,
				meta_akcija : srv_meta_akcija,
				podstran : srv_meta_podstran
			}, function () {
				$("#renameProfileDiv").hide();
				$("#dsp_cover_div").fadeOut();
			});
		});
	} else if (action == 'show_delete'){
		$("#dsp_cover_div").show();
		$("#deleteProfileDiv").show();
	} else if (action == 'cancel_delete'){
		$("#dsp_cover_div").hide();
		$("#deleteProfileDiv").hide();
	} else if (action == 'do_delete'){
		var pid = $("#dsp_profiles .active").attr('value');
		$.post('ajax.php?t=dataSettingProfile&a=deleteProfile', { anketa : srv_meta_anketa_id, pid : pid }, function() {
			$("#dsp_div").load('ajax.php?t=dataSettingProfile&a=showProfile', {
						anketa : srv_meta_anketa_id,
						meta_akcija : srv_meta_akcija,
						podstran : srv_meta_podstran
					});
		});
		$("#deleteProfileDiv").hide();
		$("#dsp_cover_div").fadeOut();
	} else if (action == 'run_profile' || action == 'run_session_profile') {
		// poiščemo id izbranega profila
		if (action == 'run_profile') {
			var pid = $("#dsp_profiles .active").attr('value');
		} else {
			var pid = '-1';
		}
		
		var dsp_ndp = $('#dsp_ndp').val();
		var dsp_nda = $('#dsp_nda').val();
		var dsp_ndd = $('#dsp_ndd').val();
		var dsp_res = $('#dsp_res').val();

		var dsp_sep = $('input[name="radio_dsp_sep"]:checked').val()
		

		var crossChk0 = $("#crossCheck0").is(':checked') ? '1' : '0';
		var crossChk1 = $("#crossCheck1").is(':checked') ? '1' : '0';
		var crossChk2 = $("#crossCheck2").is(':checked') ? '1' : '0';
		var crossChk3 = $("#crossCheck3").is(':checked') ? '1' : '0';

		var crossChkEC = $("#crossCheckEC").is(':checked') ? '1' : '0';
		var crossChkRE = $("#crossCheckRE").is(':checked') ? '1' : '0';
		var crossChkSR = $("#crossCheckSR").is(':checked') ? '1' : '0';
		var crossChkAR = $("#crossCheckAR").is(':checked') ? '1' : '0';
		var doColor	   = $("#crossCheckColor").is(':checked') ? '1' : '0';
		var doValues   = $("#crossCheckValues").is(':checked') ? '1' : '0';
		var showCategories   = $("#showCategories").is(':checked') ? '1' : '0';
		var showOther   = $("#showOther").is(':checked') ? '1' : '0';
		var showNumbers   = $("#showNumbers").is(':checked') ? '1' : '0';
		var showText   = $("#showText").is(':checked') ? '1' : '0';
		var chartNumbering   = $("#chartNumbering").is(':checked') ? '1' : '0';
		var chartFontSize   = $("#chartFontSize").val();
		var chartFP   = $("#chartFP").is(':checked') ? '1' : '0';
		var chartTableAlign   = $('input[name="chartTableAlign"]:checked').val();
		var chartTableMore   = $("#chartTableMore").is(':checked') ? '1' : '0';
		var chartNumerusText   = $("#chartNumerusText").val();
		var chartAvgText   = $("#chartAvgText").val();
		var chartPieZeros   = $("#chartPieZeros").is(':checked') ? '1' : '0';
		var hideEmpty   = $("#hideEmpty").is(':checked') ? '1' : '0';
		var hideAllSystem   = $("#hideAllSystem").is(':checked') ? '1' : '0';
		var numOpenAnswers   = $("#numOpenAnswers").val();
//		var enableInspect = $('input[name="enableInspect"]:checked').val();
		var dataPdfType   = $('#dataPdfType').val();
		var exportDataNumbering   = $("#exportDataNumbering").is(':checked') ? '1' : '0';
		var exportDataShowIf   = $("#exportDataShowIf").is(':checked') ? '1' : '0';
		var exportDataFontSize   = $("#exportDataFontSize").val();
		var exportDataShowRecnum   = $("#exportDataShowRecnum").is(':checked') ? '1' : '0';
		var exportDataPB   = $("#exportDataPB").is(':checked') ? '1' : '0';
		var exportDataSkipEmpty   = $("#exportDataSkipEmpty").is(':checked') ? '1' : '0';
		var exportDataSkipEmptySub   = $("#exportDataSkipEmptySub").is(':checked') ? '1' : '0';
		var exportDataLandscape   = $("#exportDataLandscape").is(':checked') ? '1' : '0';
		var exportNumbering   = $("#exportNumbering").is(':checked') ? '1' : '0';
		var exportShowIf   = $("#exportShowIf").is(':checked') ? '1' : '0';
		var exportFontSize   = $("#exportFontSize").val();
		var exportShowIntro   = $("#exportShowIntro").is(':checked') ? '1' : '0';
		var dataShowIcons   = $('input[name="dataShowIcons"]:checked').val();
		var analysisGoTo   = $('#analysisGoTo').val();
		var analiza_legenda   = $("#analiza_legenda").is(':checked') ? '1' : '0';
		
		$.post("ajax.php?t=dataSettingProfile&a=saveProfile", {anketa:srv_meta_anketa_id, pid:pid, dsp_ndp:dsp_ndp, dsp_nda:dsp_nda, dsp_ndd:dsp_ndd, dsp_res:dsp_res, dsp_sep:dsp_sep,
					crossChk0:crossChk0, crossChk1:crossChk1, crossChk2:crossChk2, crossChk3:crossChk3, 
					crossChkEC:crossChkEC, crossChkRE:crossChkRE, crossChkSR:crossChkSR, crossChkAR:crossChkAR, doColor:doColor, doValues:doValues,
					showCategories:showCategories, showOther:showOther, showNumbers:showNumbers, showText:showText, chartNumbering:chartNumbering, chartFontSize:chartFontSize, 
					chartFP:chartFP, chartTableAlign:chartTableAlign, chartTableMore:chartTableMore, chartNumerusText:chartNumerusText, chartAvgText:chartAvgText, chartPieZeros:chartPieZeros, 
					hideEmpty:hideEmpty, hideAllSystem:hideAllSystem, numOpenAnswers:numOpenAnswers,
					dataPdfType:dataPdfType, exportDataNumbering:exportDataNumbering, exportDataShowIf:exportDataShowIf, exportDataFontSize:exportDataFontSize, exportDataShowRecnum:exportDataShowRecnum, exportDataPB:exportDataPB, exportDataSkipEmpty:exportDataSkipEmpty, exportDataSkipEmptySub:exportDataSkipEmptySub, exportDataLandscape:exportDataLandscape,
					exportNumbering:exportNumbering, exportShowIf:exportShowIf, exportFontSize:exportFontSize, exportShowIntro:exportShowIntro,
					dataShowIcons:dataShowIcons, analysisGoTo:analysisGoTo, analiza_legenda:analiza_legenda // enableInspect:enableInspect,
					}, function(response) {
			return reloadData('dataSetting');
		});
	} else {
		genericAlertPopup('alert_parameter_action',action);
		return false;
	}
}	

function saveSingleProfileSetting(pid, what, value){
	
	$.post("ajax.php?t=dataSettingProfile&a=saveSingleProfileSetting", {anketa:srv_meta_anketa_id, pid:pid, what:what, value:value}, function(response) {
		return reloadData('dataSetting');
	});
}

function saveResidualProfileSetting(pid, checked){
	
	var value = 0;
	if(checked == true){
		value = 1;
	}

	$.post("ajax.php?t=dataSettingProfile&a=saveResidualProfileSetting", {anketa:srv_meta_anketa_id, pid:pid, value:value}, function(response) {
		return reloadData('dataSetting');
	});
}
