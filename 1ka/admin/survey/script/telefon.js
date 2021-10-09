// meta podatki
var srv_meta_anketa_id 	= $("#srv_meta_anketa").val();

// avtomatsko vsake 30 sekund preverimo, ce se je pojavila kaksna nova stevilka
function preveri_nove_stevilke () {
    $.timer(30000, function (timer) {
        
        $('#preveri_stevilke').load('ajax.php?t=telefon&a=preveri_stevilke', {anketa: srv_meta_anketa_id});
        timer.stop();
    });
}

// dashboard filter na datum 
function tel_date_filter () {
	
	// Ce imamo nastavljen datum "od"
	var dateFrom = $('#tel_dash_dateFrom').val();
	var dateFromText = '';
	if(dateFrom != '')
		dateFromText = '&date_from=' + dateFrom;
		
	// Ce imamo nastavljen datum "do"	
	var dateTo = $('#tel_dash_dateTo').val();
	var dateToText = '';
	if(dateTo != '')
		dateToText = '&date_to=' + dateTo;

	var srv_site_url = $("#srv_site_url").val();
	srv_site_url += 'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=telephone&m=dashboard'+dateFromText+dateToText;			
	window.location.href = srv_site_url;
}