function statistika_init() {
	// klikable profili za statistiko
	$("#statistic_profile .option").live('click', function() {
		$("#statistic_profile .active").removeClass("active");
		$(this).toggleClass("active");
		var pid = $(this).attr("id").substr(18);
		
		$("#div_statistic_date_select").load("ajax.php?t=dashboard&a=loadStatisticProfile", {anketa:srv_meta_anketa_id, pid:pid});
	});
}

function changeStatisticProfile () {
	var pid = $("#select_stat_profile").val();
	$.post("ajax.php?t=dashboard&a=changeStatisticProfile", {anketa:srv_meta_anketa_id, pid:pid}, function() {
		$("#surveyStatistic").load("ajax.php?t=dashboard&a=statisticRefresh", {anketa:srv_meta_anketa_id}); 
	});
}

// funkcije ki se kličejo v poročilih statistika

// preklaplja radio gumbe pri izbiri datuma ali intervala v oknu z profili statistik
function changeStatisticDate(isInterval) {
	if (isInterval == 'interval') {
		// spremenili smo dropdown
		$("#statistic_date_interval").attr("checked", "checked");
	} else {
		// spremenili smo datumska polja
		$("#statistic_date_timeline").attr("checked", "checked");
	}
	
}

// funkcija požene inshrani 
function run_statistic_interval_filter(asSession) {

	// poiščemo id izbranega profila
	var pid = $("#statistic_profile .active").attr("id").substr(18);
	var timeline = $("input[name=timeline]:checked").val();
	var startDate  = $("#startDate").val();
	var endDate = $("#endDate").val();
	var stat_interval = $("#stat_interval").val();
	$.post("ajax.php?t=dashboard&a=runStatisticProfile", {anketa:srv_meta_anketa_id, pid:pid, timeline:timeline,startDate:startDate,endDate:endDate,stat_interval:stat_interval, asSession:asSession}, function(response) {

		if (!response) {
			$("#surveyStatistic").load("ajax.php?t=dashboard&a=statisticRefresh", {anketa:srv_meta_anketa_id}, function() {$('#fade').fadeOut('slow');}); 
		} else { 
			// prišlo je do napake;

		    $('#fade').fadeOut('slow');
		}
	});
}
// zapre okno za izbiro profila
function close_statistic_interval_filter() {
	$("#div_statistic_date_select").fadeOut('slow');
    $('#fade').fadeOut('slow');
}
// prikaze / skrije div za brisanje profila
function showHideDeleteStatisticProfile(showhide) {
	if (showhide=='true') {
		$("#statisticProfileCoverDiv").show();
		$("#deleteProfileDiv").show();
	}
	else {
		$("#statisticProfileCoverDiv").hide();
		$("#deleteProfileDiv").hide();
	}
}

// prikaze / skrije div za preimenovanje profila
function showHideRenameStatisticProfile(showhide) {
	if (showhide=='true') {
		// polovimo pid aktivnega prifila
		var pid = $("#statistic_profile .active").attr("id").substr(18);
		
		// popravimo ime profila
		$("#renameProfileName").val($("#statistic_profile_"+pid).html());
		
		$("#statisticProfileCoverDiv").show();
		$("#renameProfileDiv").show();
	}
	else {
		$("#statisticProfileCoverDiv").hide();
		$("#renameProfileDiv").hide();
	}
}

// za preimenovanje izbranega profila
function renameStatisticProfile() {
	var pid = $("#statistic_profile .active").attr("id").substr(18);
	var name = $("#renameProfileName").val();
	$.post("ajax.php?t=dashboard&a=renameStatisticProfile", {anketa:srv_meta_anketa_id, pid:pid, name:name}, function(response) {
		
		$("#statisticProfileCoverDiv").hide();
		$("#renameProfileDiv").hide();

		if (response > 0) {
			$("#statistic_profile .active").html(name);
		} else if (response < 0){
			alert("Error!");
		}
	});
}

// za brisanje izbranega profila
function deleteStatisticProfile() {
	var pid = $("#statistic_profile .active").attr("id").substr(18);
	$.post("ajax.php?t=dashboard&a=deleteStatisticProfile", {anketa:srv_meta_anketa_id, pid:pid}, function(response) {

		$("#statisticProfileCoverDiv").hide();
		$("#deleteProfileDiv").hide();
		$("#div_statistic_date_select").load("ajax.php?t=dashboard&a=loadStatisticProfile", {anketa:srv_meta_anketa_id});
	});
	
}
function show_statistic_interval_filter() {
    $('#fade').fadeTo('slow', 1);
    $("#div_statistic_date_select").load("ajax.php?t=dashboard&a=loadStatisticProfile", {anketa:srv_meta_anketa_id}).fadeIn('slow').draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
}


//prikaze / skrije div za brisanje profila
function showHideCreateStatisticProfile(showhide) {
	if (showhide=='true') {
		$("#statisticProfileCoverDiv").show();
		$("#newProfileDiv").show();
	}
	else {
		$("#statisticProfileCoverDiv").hide();
		$("#newProfileDiv").hide();
	}
}

function create_new_statistic_interval_filter() {
	var timeline = $("input[name=timeline]:checked").val();
	var startDate  = $("#startDate").val();
	var endDate = $("#endDate").val();
	var stat_interval = $("#stat_interval").val();
	var name= $("#newProfileName").val();

	$.post("ajax.php?t=dashboard&a=createStatisticProfile", {anketa:srv_meta_anketa_id, timeline:timeline,startDate:startDate,endDate:endDate,stat_interval:stat_interval, name:name}, function(response) {
		$("#statisticProfileCoverDiv").hide();
		$("#renameProfileDiv").hide();
		if (!response) {
			$("#div_statistic_date_select").load("ajax.php?t=dashboard&a=loadStatisticProfile", {anketa:srv_meta_anketa_id});
		} else { 
			// prišlo je do napake;
			alert(response);
		}
	});
}
