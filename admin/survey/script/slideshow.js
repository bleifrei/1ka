// inicializacije
function slideshow_init () {
	$("#link_slideshow_reset_interval").live("click", function(event) {
		if (event.button != 0) { // wasn't the left button - ignore
				return true;
			}
			slideshow_reset_interval();
			return false; // "capture" the click
		});

};

// klik na link s katerim ponastavimo timer na vseh vprašanjih
function slideshow_reset_interval() {
	if ($('#slide_fixed_interval').is(':checked')) {
		var timer = $("#slideshow_timer").val();
		var fixed_interval = $('#slide_fixed_interval').is(':checked') ? '1' : '0';
		$("#globalSettingsInner").load('ajax.php?t=slideshow&a=reset_interval', {anketa: srv_meta_anketa_id, timer:timer, fixed_interval:fixed_interval}, function() {});

	} else {
		// ne moremo resetriat, ker checkbox ni izbran
		alert("Error! Select checkbox");
	}
}

function slideshow_save_settings() {
	

	var timer = $("#slideshow_timer").val();
	var fixed_interval = $('#slide_fixed_interval').is(':checked') ? '1' : '0';

	var save_entries = $('input[name=slide_save_entries]:checked').val();
	var autostart = $('input[name=slide_autostart]:checked').val();
	var next_btn = $('input[name=slide_next]:checked').val();
	var back_btn = $('input[name=slide_back]:checked').val();
	var pause_btn = $('input[name=slide_pause]:checked').val();

	$.post('ajax.php?t=slideshow&a=save_settings', 
			{anketa: srv_meta_anketa_id, timer:timer, fixed_interval:fixed_interval, save_entries:save_entries, autostart:autostart, next_btn:next_btn, back_btn:back_btn, pause_btn:pause_btn},function(){
		show_success_save();
	});

}