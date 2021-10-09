/**
* 
* Uporaba ProgressBar-a :
* 
* zazene se z init_progressBar(); - enostavni progress bar brez prikaza napredka ne rabi nic drugega
* 
* labelo se lahko spreminja preko funkcije set_progressBar_label('labela ...');
* 
* Ce hocemo sproti osvezevati nas progress, pa pozenemo init_progressBar(true);
* 
* s PHPjem nato v sejo shranjujejo na naslednji nacin, kjer je total stevilo vseh zapisov, current pa trenutni zapis (in $ank_id id ankete)
* 
* 		session_start();
*		$_SESSION['progressBar'][$ank_id]['status'] = 'ok';
*		$_SESSION['progressBar'][$ank_id]['total'] = 100;
*		$_SESSION['progressBar'][$ank_id]['current'] = 35;
*		session_commit();
* 
* na koncu damo se status na end, da v getCollectTimer.php pobrisemo session (da je naslednjic prazna)
* 
* 		session_start();
*		$_SESSION['progressBar'][$ank_id]['status'] = 'end';
*		session_commit();
* 
*/


var pbInterval = '';
var timer = 0;
var starttime = 0;

/**
* inicializira vse za prikaz progressbara
* @param getProgress - ali bomo v sessionu sporocali napredek, ali ne (ce ne, potem samo stejemo cas)
*/
function init_progressBar (getProgress) {
	
	if (typeof(getProgress) == 'undefined') getProgress = false;
	
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').show();
	
	$("#fullscreen").html(	'<div style="border:1px solid red; background-color:#f2f2f2; padding:8px; width:700px; float:right">' +
							'<div class="red" id="pbLabel">'+lang['srv_please_wait']+'</div>' +
							'<br/><br/>' +
							'<div id="pbPercent" class="floatLeft pbLabel">'+lang['srv_collectdata_progress_status']+':</div><div id="pbRowPercent" class="floatLeft"><span id="pbRowPercentLabel"></span><div>&nbsp;</div></div>' +
							'<br class="clr">' +
							'</div>'
						); 
						
	$('#pbRowPercent div').css('width','0%');
	
	$('#pbRowPercent').css('visibility','visible');
	
	timer = 0;
	var d = new Date();
	starttime = d.getTime();
	
	pbInterval=window.setInterval("refresh_progressBar("+getProgress+")",250);	// klicemo na 250ms
	
}

/**
* intervalno refresha prikaz progressBara
*/
function refresh_progressBar (getProgress) {

	// kako hitro se bo premikal bar v nalaganju brez prikaza napredka
	timer ++;
	
	// v time je stevilo sekund izvajanja
	var d = new Date();
	time = parseInt( (d.getTime() - starttime) / 1000 );
	
	// oblikujemo izpis mm:ss
	if (time >= 60) {
		minutes = parseInt( time / 60);
		time = time - minutes*60;
		if (time < 10) time = '0'+time;
		if (minutes < 10) minutes = '0'+minutes;
		time = minutes+':'+time;
	} else {
		if (time < 10) time = '0'+time;
		time = '00:'+time;
	}

	// nalaganje s prikazom napredka
	if (getProgress == true) {
		
		$.post('getCollectTimer.php?getProgress=true&foo='+Math.random(), {anketa: srv_meta_anketa_id}, function(data) {
			
			if (data.status != 'null') {
				width = parseInt( data.current / data.total * 100 );
				
				$('#pbRowPercentLabel').html( data.current + ' / ' + data.total + ' (' + time + ')' );	
				$('#pbRowPercent div').css('width', width+'%');
			}
			
		}, "json");
		
	// brez prikaza napredka, samo premikamo bar
	} else  {
	
		margin = ( timer % 19 ) * 5;
		
		$('#pbRowPercentLabel').html(time);	
		$('#pbRowPercent div').css('width', '10%').css('margin-left', margin+'%');
	}
}

/**
* ustavi izvajanje progress bara
*/
function stop_progressBar () {
	
	window.clearInterval(pbInterval);
    pbInterval='';
    timer = 0;
	
	$('#fade').fadeOut('slow');
	$('#fullscreen').hide();
	$('#fullscreen').html('');

}

function set_progressBar_label (label) {
	$('#pbLabel').html(label);
}