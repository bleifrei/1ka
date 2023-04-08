/**
k* pozene racunanje kalkulacij za vse izracunane spremenljivke na anketi
*/
function postprocess_start_calculation () {
		
	init_progressBar();
	
	$.redirect('ajax.php?t=postprocess&a=postprocess_start_calculation', {anketa: srv_meta_anketa_id});
}



/**
*  prikaze urejanje zapisa spremenljivke v urejanju podatkov
*/
function edit_data(event) {
	// povemo koliko stolpcev z ikonicami imamo (se nastavi v html)
	var tableIconColspan = parseInt($("#tableIconColspan").val());

	var target = $(event.target);
	target = target.closest('td');
	if ( ! target.is('td') ) return;
	
	var target_parent_children = target.parent().children();				// td-ji vrstice kamor smo kliknili
	var table_head_children = $('#dataTable tr:nth-child(3)').children();	// th-ji vrstice header tabele (s spr_id)
	
	var td = target.prevAll('td.data_uid');		// td, ki vsebuje usr_id
	
	if ( td.hasClass('data_uid') && !isNaN(td.html()) ) {
		
		var usr_id = td.html();
		
		// nad katero celico je kurosr
		var cell_over = target_parent_children.index(target);
		// naredimo korekcijo: katera je head celica z spr_id za celico z kurzorjem
		var spr_index = cell_over-tableIconColspan+(tableIconColspan > 0 ? 1 : 0);

		// če nismo nad celeco z spr id gremo ven
		if ( isNaN(table_head_children.filter(":eq("+spr_index+")").attr('spr_id')) ) return;	
		
		var spr_id = table_head_children.filter(":eq("+spr_index+")").attr('spr_id');

		// pri inline_edit-u ne odpiramo popupa zato tud ni hoverja
		if ( table_head_children.filter(":eq("+spr_index+")").attr('inline_edit') == 1 ) return;	
		

		
		// gremo urejat vprasanje
		if ( !isNaN(usr_id) && usr_id!='' && !isNaN(spr_id) && spr_id!='' ) {
			// pobarvamo celice vprasanja, ki ga bomo urejali
			$.each( table_head_children.filter("th[spr_id="+spr_id+"]") , function (i, value) {
				var table_index = table_head_children.index(value);

				// naredimo korekcijo indexov če imamo stolpce z ikonami
				var corect_index = table_index + (tableIconColspan == 0 ? 0 : tableIconColspan-1);

				target_parent_children.filter(":eq("+corect_index+")").addClass("data_spr_editing");
			});
			
			
			// prikazemo urejanje vprasanja
			$('#fullscreen').html('').fadeIn('slow');
			$('#fade').fadeTo('slow', 1);
			
			$('#fullscreen').load('ajax.php?t=postprocess&a=edit_data_question', {anketa: srv_meta_anketa_id, spr_id:spr_id, usr_id: usr_id});
		}	
	}	
}

/**
* zapre urejanje (cancel)
*/
function edit_data_close () {
	
	$('#fullscreen').html('').fadeOut();
	$('#fade').fadeOut('slow');
	$('#dataTable td.data_spr_editing').delay('2000').removeClass('data_spr_editing', 500);
	
}

/**
* shrani vprasanje 
*/
function edit_data_question_save () {
	
	$('#fullscreen').hide();
	$('#loading').show();
	$('#fade').fadeOut('slow');
	
	var cell = $('#dataTable td.data_spr_editing').removeClass('data_spr_editing');
	
	$.post('ajax.php?t=postprocess&a=edit_data_question_save&anketa='+srv_meta_anketa_id, $("form[name=edit_data]").serialize(), function () {
		cell.css('color', 'lightgray');
        //window.location.reload();
        
        $('#loading').hide();
	});
	
}

/**
 * obarva ozadje celic ob hoverju
 */
function edit_data_hover (event) {
	// povemo koliko stolpcev z ikonicami imamo (se nastavi v html)
	var tableIconColspan = parseInt($("#tableIconColspan").val());
	
	var target = $(event.target);
	
	if ( ! target.is('td') ) return;

	var target_parent_children = target.parent().children();				// td-ji vrstice kamor smo kliknili
	var table_head_children = $('#dataTable tr:nth-child(3)').children();	// th-ji vrstice header tabele (s spr_id)
	 
	// nad katero celico je kurosr
	var cell_over = target_parent_children.index(target);
	// naredimo korekcijo: katera je head celica z spr_id za celico z kurzorjem

	var spr_index = cell_over-tableIconColspan+(tableIconColspan > 0 ? 1 : 0);

	// če nismo nad celeco z spr id gremo ven
	if ( isNaN(table_head_children.filter(":eq("+spr_index+")").attr('spr_id')) ) return;	
	
	var spr_id = table_head_children.filter(":eq("+spr_index+")").attr('spr_id');

	// pri inline_edit-u ne odpiramo popupa zato tud ni hoverja
	if ( table_head_children.filter(":eq("+spr_index+")").attr('inline_edit') == 1 ) return;	

	var inline_edit = table_head_children.filter(":eq("+spr_index+")").attr('inline_edit');

	// gremo pobarvat celice
	if ( !isNaN(spr_id) && spr_id!='' ) {
		$.each( table_head_children.filter("th[spr_id="+spr_id+"]") , function (i, value) {
			var table_index = table_head_children.index(value);

			// naredimo korekcijo indexov če imamo stolpce z ikonami
			var corect_index = table_index + (tableIconColspan == 0 ? 0 : tableIconColspan-1);

			target_parent_children.filter(":eq("+corect_index+")").addClass("hover");
		});
	}
}


/**
 * odstrani hover s celic
 */
function edit_data_hoverout (event) {
	
	$(event.target).parent().find('td.hover').removeClass("hover");
	
}

/**
* izrise rolete za inline edit vprasanja
*/
function edit_data_inline_edit () {
	
	// srv_meta_anketa_id se ni postavljen
	srv_meta_anketa_id = srv_meta_anketa_id || $("#srv_meta_anketa_id").val();
	
	// povemo koliko stolpcev z ikonicami imamo (se nastavi v html)
	var tableIconColspan = parseInt( $("#tableIconColspan").val() ) || 0;
	
	var tableHeadChildren = $('#dataTable tr:nth-child(3)').children();	// th-ji vrstice header tabele
	
	var sprList = [];
	
	// gremo cez vse stolpce ki imajo inline_edit=1 in si shranimo spr_id
	tableHeadChildren.filter("th[inline_edit=1]").each( function (ii, column) {
		sprList.push( $(column).attr('spr_id') );
	});
	
	// poberemo html kodo forme
	$.post('ajax.php?t=postprocess&a=get_inline_edit_all', {anketa: srv_meta_anketa_id, spr: sprList}, function (response) {
		
		// gremo cez vse stolpce ki imajo inline_edit=1
		for (var i=0, len=response.length; i<len; i++) {
						
			var column = $('th[spr_id='+response[i].spr+']');
			var spr_id = $(column).attr('spr_id');
			
			// na kateri celici smo
			var tableIndex = tableHeadChildren.index(column);
			// nardimo korekcijo zaradi ikonic
			tableIndex = tableIndex + (tableIconColspan > 0 ? tableIconColspan : 1);	// +1 ker je en stolpec uid (skrit) 
			// če mamo ikonce mormo prištet še 1 ker mamo prvi stolpec colspanan (headerji z ikoncami nimajo atrubuta inline_edit)
			
			// gremo cez vse vrstice
			$('#dataTable tr').each( function (ii, tr) {
				
				var element = $(tr).find('td:nth-child('+(tableIndex)+')');
				
				if ($(element).is('td')) {
					
					var usr_id = $(tr).find('td.data_uid').html();
					var val = element.html();
					
					if (val && val.trim()!='') {
						// naredimo fragment, da samo 1x updatamo dom
						var fragment = $(response[i].html);
						
						// tuki se sprozi en jquery error, samo gre za bug: http://bugs.jquery.com/ticket/12072
						
						// option ne obstaja, dodamo novo
						if (fragment.find('select option.'+val+'').length == 0) {
							
							fragment.find('select').prepend('<option value="'+val+'" selected="selected">'+val+'</option>').val( val );
						
						// option obstaja, jo selectamo
						} else {
							
							fragment.find('select').val( fragment.find('select option.'+val+'').val() );
						
						}
						
						fragment.filter('form').prepend('<input type="hidden" name="usr_id" value="'+usr_id+'" />').attr('name', 'inline_edit_'+usr_id+'_'+spr_id);
						
						element.html(fragment);
					}
				}
				
			});	

		}
		
	}, 'json');
	
}

/**
* shrani podatke pri inline urejanju
*/
function edit_data_inline_edit_save (name) {
	
	$.post('ajax.php?t=postprocess&a=edit_data_question_save&anketa='+srv_meta_anketa_id, $("form[name="+name+"]").serialize(), function () {
		//window.location.reload();
	});
	
}


function data_preview_content (event) {
	
	var target = $(event.target).closest('td');
	
	if ( target.closest('tr').parent().is('tbody') ) {
		
		if ( !target.hasClass('enkaIcon') && !target.hasClass('data_edit') && !target.hasClass('cellGreen') ) {
			
			if ( target.find('.data_preview_content').length == 0 ) {
				
				if ( target.html() != '' )
					target.append('<div class="data_preview_content">'+target.html()+'</div>');
			
			} else {
				
				target.find('.data_preview_content').remove();
			}
			
		}
		
	}
	
	return false;
}

/**
* obarva ozadje celic ob hoverju headerja za sortiranje
*/
function data_header_hover (event) {
	
	var target = $(event.target);

	// ce smo kliknili na notranji div z classom: .dataCell 
	if ( target.is('.dataCell') ) {
		target = target.parent(); 
	}

	if ( ! target.is('th') ) return;

	var seq = target.attr('seq');
	if ( !isNaN(seq) && seq!='' ) {
		target.addClass("hover");
	}
	
}
/**
* odstrani hover s header celic
*/
function data_header_hoverout (event) {
	var target = $(event.target);
	target.parent().parent().find(':not(.sort_asc, .sort_dsc)').removeClass("hover");
	
}

function data_header_click (event) {
	
	var target = $(event.target);
	// ce smo kliknili na notranji div z classom: .dataCell 
	if ( target.is('.dataCell') ) {
		target = target.parent(); 
	}
	
	if ( ! target.is('th') ) return;
	
	$("#loading").show();
	
	var sort_seq = target.attr('seq');
	if ( !isNaN(sort_seq) && sort_seq!='' ) {
		var sort_type = target.is('.sort_asc') ? 'sort_dsc' : 'sort_asc'
		vnos_redirect(sort_action_url+'&sort_seq='+sort_seq+'&sort_type='+sort_type);	
	}
}

var coding_cache = new Array();

function coding_click ( el, event ) {
	
	var usr_id = el.closest('tr').find('td.data_uid').html();
	usr_id = parseInt(usr_id);
	
	var td_pos = el.parent().children().index(el);
	
	var spr_id = $('#dataTable tr:nth-child(3) th:eq('+td_pos+')').attr('spr_id');
	
	if ( ! spr_id > 0) return;
	
	var pos = el.offset();
	$("#coding").html('').css( { "left": ( event.pageX ) + "px", "top": ( event.pageY + 20 ) + "px" } ).show();
	$('#dataTable tr td').removeClass('active');
	el.addClass('active');
	
	$('#dataTable tbody td').removeClass('cellBlue');
	$('#dataTable tbody td:nth-child('+(td_pos+1)+')').addClass('cellBlue');
	
	if ( false && usr_id in coding_cache ) {
		
		$('#coding').html(coding_cache[usr_id]);
		
	} else {
		
		$('#coding').load('ajax.php?t=postprocess&a=coding', {anketa: srv_meta_anketa_id, spr_id: spr_id, usr_id: usr_id}, function(data) {
			coding_cache[usr_id] = data;
		});
		
	}
			
}

function coding_save (usr_id) {
	
	delete coding_cache[usr_id];
	
	
	$.post('ajax.php?t=postprocess&a=coding_save', $('form#coding_'+usr_id).serialize(), function (data) {} );
	
	$('#dataTable td.active').css('color', 'lightgray');
	$('#dataTable td').removeClass('active');
	$('#dataTable tbody td').removeClass('cellBlue');
	$('#coding').hide();
	
	$('.coding-refresh').fadeIn();
}

function coding_vrednost_new (spr_id, usr_id, naslov) {
	
	coding_cache = new Array();
	
	$('#coding').load('ajax.php?t=postprocess&a=vrednost_new', {anketa:srv_meta_anketa_id, spr_id:spr_id, naslov:naslov, usr_id:usr_id});
	
}

function coding_spremenljivka_new (spr_id, usr_id, naslov) {
	
	coding_cache = new Array();
	
	$('#coding').load('ajax.php?t=postprocess&a=spremenljivka_new', {anketa:srv_meta_anketa_id, naslov:naslov, spr_id:spr_id, usr_id:usr_id});
	
}


function coding_tip (spr_id, usr_id, tip) {
	
	coding_cache = new Array();
	$('#coding').load('ajax.php?t=postprocess&a=tip', {anketa:srv_meta_anketa_id, spr_id:spr_id, usr_id:usr_id, tip:tip});
	
}


function mass_coding (seq, coding_type) {
	
	$('#vrednost_edit').load('ajax.php?t=postprocess&a=mass_coding', {anketa:srv_meta_anketa_id, seq:seq, coding_type:coding_type}, function (data) {
		$('#vrednost_edit').show(); 
		$('#fade').fadeTo('slow', 1); 
	});
	
}

function coding_filter (seq) {
	
	$.post('ajax.php?t=postprocess&a=coding_filter', {anketa:srv_meta_anketa_id, seq:seq}, function (data) { 
		window.location.reload() 
	});
}


function coding_merge (spr_id, vre_id, usr_id, merge) {
	
	if (spr_id > 0 && vre_id > 0 && merge > 0) {
		
		$('#coding').load('ajax.php?t=postprocess&a=coding_merge', {anketa:srv_meta_anketa_id, spr_id:spr_id, vre_id:vre_id, usr_id:usr_id, merge:merge});
	}
}

function postProcessAddTitles() {
	$.each ( $('#dataTable tbody tr td') ,
		function (i, value) {
			var element = $(value);
			if (!$(element).attr('title') && $(element).attr('class')!='data_edit') {
				var txt = element.html();
				element.attr('title',txt);
			}
	});
}
function postProcessAddMetaTitles() {
	// povemo koliko stolpcev z ikonicami imamo (se nastavi v html)
	var tableIconColspan = parseInt($("#tableIconColspan").val());
	if (tableIconColspan == 0)
	{
		tableIconColspan = 1;		
	}
	
	var fields = {};
	fields['status'] = 0;
	fields['lurker'] = 0;
	fields['recnum'] = 0;
	fields['code'] = 0;
	fields['itime'] = 0;
	fields['lineNo'] = 0;
	fields['meta'] = 0;
	$.each ( $('#dataTable tr:nth-child(3) th') ,function (i, element) 
	{
		if ($(element).attr('spr_id') == 'status') 
		{
			fields['status'] = i+tableIconColspan;
		}
		else if ($(element).attr('spr_id') == 'lurker') 
		{
			fields['lurker'] = i+tableIconColspan;
		}
		else if ($(element).attr('spr_id') == 'recnum') 
		{
			fields['recnum'] = i+tableIconColspan;
		}
		else if ($(element).attr('spr_id') == 'code') 
		{
			fields['code'] = i+tableIconColspan;
		}
		else if ($(element).attr('spr_id') == 'itime') 
		{
			fields['itime'] = i+tableIconColspan;
		}
		else if ($(element).attr('spr_id') == 'lineNo') 
		{
			fields['lineNo'] = i+tableIconColspan;
		}
		else if ($(element).attr('spr_id') == 'meta' && fields['meta'] == 0) 
		{
			fields['meta'] = i+tableIconColspan;
		}
	});
	var langs = new Array(); 
	// polovimo tekste
	$.post('ajax.php?a=getDataStatusTitles', {anketa: srv_meta_anketa_id}, function (data) {
		data = jQuery.parseJSON(data);
		langs[0]=data.status0;
		langs[1]=data.status1;
		langs[2]=data.status2;
		langs[3]=data.status3;
		langs[4]=data.status4;
		langs[5]=data.status5;
		langs[6]=data.status6;
		// preletimo skozi kolono in dodamo title
		$.each ( $('#dataTable tr') , function (i, value) 
		{
			// status
			if (fields['status'] > 0)
			{
				var element = $(value).find(':nth-child('+(fields['status'])+')');
				if ($(element).is('td')) {
					// pobarvamo celice 
					element.addClass("cellBlue");
					// in dodamo naslove
					var status = parseInt(element.html());
					var new_val = langs[status];
					element.attr('title',status+' - '+new_val);
				}
			}
			// lurker
			if (fields['lurker'] > 0)
			{
				var element = $(value).find(':nth-child('+(fields['lurker'])+')');
				if ($(element).is('td')) {
					element.addClass("cellBlue");
				}
			}

			// recnum
			if (fields['recnum'] > 0)
			{
				var element = $(value).find(':nth-child('+(fields['recnum'])+')');
				if ($(element).is('td')) {
					element.addClass("cellBlue");
				}
			}

			if (fields['code'] > 0 )
			{
				var element = $(value).find(':nth-child('+(fields['code'])+')');
				if ($(element).is('td')) {
					element.addClass("cellBlue");
				}
			}
			// itime
			if (fields['itime'] > 0)
			{
				var element = $(value).find(':nth-child('+(fields['itime'])+')');
				if ($(element).is('td')) {
					// pobarvamo celice 
					element.addClass("cellYellow");
				}
			}
			// lineNo
			if (fields['lineNo'] > 0)
			{
				var element = $(value).find(':nth-child('+(fields['lineNo'])+')');
				if ($(element).is('td')) {
					// pobarvamo celice 
					element.addClass("cellYellow");
				}
			}
			// lineNo
			if (fields['meta'] > 0)
			{
				var element = $(value).find(':nth-child(n+'+(fields['meta'] )+')');
				if ($(element).is('td')) {
					// pobarvamo celice 
					element.addClass("cellBlue");
				}
			}
		});
	});
}

function postProcessAddSystem(columns) {
	
	var tableIconColspan = parseInt($("#tableIconColspan").val());
	var indexes = [];
	$.each(columns, function(j, col){
		var index = $("th[seq="+col+"]").index() + tableIconColspan;
		indexes.push(index);
	});
	
	$.each($('#dataTable tr'), function(i, value){
		$.each(indexes, function(j, pos){		
			var element = $(value).find(':nth-child('+(pos)+')');
			if ($(element).is('td')) {
				element.addClass("cellRed");
			}
		});
	});
}

function postProcessAddLurkerTitles(column) {
	// povemo koliko stolpcev z ikonicami imamo (se nastavi v html)
	var tableIconColspan = parseInt($("#tableIconColspan").val());
	// zamenjamo statuse
	// poiščemo elemente z sekvenco: 2-ustreznost 4 - status, 5 - lurker
	var table_head_children = $('#dataTable tr:nth-child(3)').children();	// th-ji vrstice header tabele (s spr_id)
	var table_index = table_head_children.filter("th[seq=5]").index();
	// če sploh prikazujemo statuse
	if (table_index > 0) {
		//naredimo korekcijo: katera je head celica z spr_id za celico z kurzorjem
		table_index = tableIconColspan+column;
		
		var langs = new Array(); 
		// polovimo tekste
		$.post('ajax.php?a=getDataLurkerTitles', {anketa: srv_meta_anketa_id}, function (data) {
			data = jQuery.parseJSON(data);
			langs[0]=data.status0;
			langs[1]=data.status1;
			// preletimo skozi kolono in dodamo title
			$.each ( $('#dataTable tr') ,
					function (i, value) {
				var element = $(value).find(':nth-child('+(table_index)+')');
				if ($(element).is('td')) {
					var status = parseInt(element.html());
					var new_val = langs[status];
					element.attr('title',new_val);
					element.html(new_val);
				}
				
			});
		});
	}
}
function postProcessAddEmailTitles(column) {
	// povemo koliko stolpcev z ikonicami imamo (se nastavi v html)
	var tableIconColspan = parseInt($("#tableIconColspan").val());
	// zamenjamo statuse
	// poiščemo elemente z sekvenco: 2-ustreznost 3 - status, 5 - lurker
	var table_head_children = $('#dataTable tr:nth-child(3)').children();	// th-ji vrstice header tabele (s spr_id)
	var table_index = table_head_children.filter("th[seq=3]").index();
	// če sploh prikazujemo statuse
	if (table_index > 0) {
		//naredimo korekcijo: katera je head celica z spr_sid za celico z kurzorjem
		table_index = tableIconColspan+column;
		var langs = new Array(); 
		// polovimo tekste
		$.post('ajax.php?a=getDataEmailTitles', {anketa: srv_meta_anketa_id}, function (data) {
			data = jQuery.parseJSON(data);
			langs[0]=data.email0;
			langs[1]=data.email1;
			langs[2]=data.email2;
			// preletimo skozi kolono in dodamo title
			$.each ( $('#dataTable tbody tr') ,
					function (i, value) {
						var element = $(value).find('td:nth-child('+(table_index)+')');
						if ($(element).is('td')) {
							var status = parseInt(element.html());
							var new_val = langs[status];
							element.attr('title',new_val);
							element.html(new_val);
							element.addClass("cellGreen");
							
							if(status == 1)
								element.addClass("invitation_cell");
						}
				
			});
		});
	}
}
function postProcessAddRelevanceTitles() {
	// povemo koliko stolpcev z ikonicami imamo (se nastavi v html)
	var tableIconColspan = parseInt($("#tableIconColspan").val());
	// zamenjamo statuse
	// poiščemo elemente z sekvenco: 2-ustreznost 3 - status, 5 - lurker
	var table_head_children = $('#dataTable tr:nth-child(3)').children();	// th-ji vrstice header tabele (s spr_id)
	var table_index = table_head_children.filter("th[seq=2]").index();
	// če sploh prikazujemo statuse
	if (table_index > 0) {
		//naredimo korekcijo: katera je head celica z spr_sid za celico z kurzorjem
		table_index = tableIconColspan+2;
		
		var langs = new Array(); 
		// polovimo tekste
		$.post('ajax.php?a=getDataLurkerTitles', {anketa: srv_meta_anketa_id}, function (data) {
			data = jQuery.parseJSON(data);
			langs[0]=data.status0;
			langs[1]=data.status1;
			// preletimo skozi kolono in dodamo title
			$.each ( $('#dataTable tbody tr') ,
				function (i, value) {
					var element = $(value).find('td:nth-child('+(table_index)+')');
					if ($(element).is('td')) {
						var status = parseInt(element.html());
						var new_val = langs[status];
						element.attr('title',new_val);
						element.html(new_val);
						element.addClass("cellGreen");
					}
					
			});
		});
	}
}

function showSurveyAnswers(event) {
/*
	var srv_site_url = $("#srv_site_url").val();
	var target = $(event.target);
	var uid = $(target).parent().find('.data_uid').html();
	var href = srv_site_url+'main/survey/edit_anketa.php?anketa='+srv_meta_anketa_id+'&usr_id='+uid+'&quick_view=1';
	//alert(href);
	//return false;
	if (uid > 0 ){
		window.open(href, '_blank');
	}		
*/
	// polovimo user id
	var srv_site_url = $("#srv_site_url").val();
	var target = $(event.target);
	var uid = $(target).parent().parent().find('.data_uid').html();
//	var href = srv_site_url+'main/survey/edit_anketa.php?anketa='+srv_meta_anketa_id+'&usr_id='+uid+'&quick_view=1';
	var href = srv_site_url+'admin/survey/index.php?anketa='+srv_meta_anketa_id+'&a=data&m=quick_edit&usr_id='+uid+'&quick_view=1';
	if (uid > 0 ){
		window.location = href;
	}
}	

function dataTableResize (sid) {
	
	var mousedown = false;
	var startObj = undefined;
	var startX, startWidth;
	var anketa = sid;
	var widths = {};
	
	if (localStorage.dataTableWidths)
		widths = JSON.parse(localStorage.dataTableWidths);
	
	return function () {
	
		for (var seq in widths[anketa]) {
			$('#dataTable col[seq='+seq+']').width(widths[anketa][seq]);
		}
		
		$('#dataTable tr:nth-child(3) th div.dataCell').append('<div class="tableResize"></div>');
		
		$('#dataTable tr:nth-child(3) th .tableResize').bind('mousedown', function (e) {
			
			startObj = $('#dataTable col[seq='+ $(this).closest('th').attr('seq') +']');
			mousedown = true;
			startX = e.pageX;
			startWidth = startObj[0].offsetWidth || parseInt( startObj[0].style.width.substring(0, (startObj[0].style.width.length)-2) ) || 100;	// 100 je default vrednost, ce se ni nastavljen v CSSju (offsetWidth pa prime samo v FF - ??)
		
			document.onmousemove = function(e) {
				if (mousedown) {
					var width = startWidth + (e.pageX-startX);
					if (width < 20) width = 20;
					
					startObj.width ( width );
					var seq = startObj.attr('seq');
					
					if ( widths[anketa] == undefined ) widths[anketa] = {};
					widths[anketa][seq] = width;
				}
			};
			
			document.onmouseup = function() {
				if (mousedown) {
					mousedown = false;
					localStorage.dataTableWidths = JSON.stringify(widths);
					document.onmousemove = null;
					document.onmouseup = null;
				}
			};
			
			return false;
		})
			
		// double click - avtomatsko prilagajanje sirine
		.bind('dblclick', function (e) {
			
			var textWidth = $('<span class="textWidth" style="position:absolute; visibility:hidden; white-space:nowrap;"></span>');
			var textWidthJS = textWidth[0];
			$('#tableContainer').append(textWidth);
			
			var index = $(e.target).closest('tr').find('th').index( $(e.target).closest('th') );
			
			var maxWidth = 0;
			var tWidth = 0;
			var rows = $('#dataTable tbody td:nth-child('+(index+1)+')');
			for (var i=0, len=rows.length; i<len; i++) {
				textWidthJS.innerHTML = rows[i].innerHTML;
				tWidth = textWidthJS.offsetWidth;
				if ( tWidth > maxWidth ) maxWidth = tWidth;
			}
			
			maxWidth += 10; // padding itd..
			if (maxWidth < 20) maxWidth = 20;
			
			var seq = $('#dataTable col:nth-child('+(index)+')').width(maxWidth).attr('seq');		// ni +1 ker ne upošteva hidden stolpcev
			
			if ( widths[anketa] == undefined ) widths[anketa] = {};
			widths[anketa][seq] = maxWidth;
			localStorage.dataTableWidths = JSON.stringify(widths);
			
			textWidth.remove();
			
		});
    	
	}();
	
}
