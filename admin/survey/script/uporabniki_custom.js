//Uporabimo custom funkcijo pri uporabnikih, dokler se ne posodobi jQuery
$(document).ready(function () {

    // Search na vrhu po pritisku na enter skoci na drupal search
	$('#searchSurvey').keypress(function (e) {
		if (e.which == 13) {
			executeDrupalSearch();
			return false;
		}
	});

    $('#xtradiv strong').on("click", function (event) {
        $('#xtradivSettings').toggle();
    });

});


function language_change (lang) {
	$.post('ajax.php?t=surveyList&a=language_change', {lang: lang}, function () {
		/*window.location.reload();*/
		window.location = window.location.href.split("?")[0];
	});
}
