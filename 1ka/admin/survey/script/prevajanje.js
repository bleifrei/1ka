var _vprasanje_prevod_preview = 0;

/**
* prikaze urejanje prevoda besedila ankete
*/
function extra_translation (text) {
	
	if (!$('#srvlang_'+text).hasClass('editing'))
		$('#srvlang_'+text).addClass('editing').load('ajax.php?t=prevajanje&a=extra_translation', {text: text, lang_id: srv_meta_lang_id, anketa: srv_meta_anketa_id},
			function () {
				$('#srvlang_'+text+'_'+srv_meta_lang_id).focus();
			}
		);
}

/**
* shrani prevod besedila
*/
function extra_translation_save (text) {
	
	//value = $('#srvlang_'+text+'_'+srv_meta_lang_id).val();
	value = $('#srvlang_'+text+'_'+srv_meta_lang_id).html();
	
	
	$.post('ajax.php?t=prevajanje&a=extra_translation_save', {value: value, text: text, lang_id: srv_meta_lang_id, anketa: srv_meta_anketa_id});
	/*$('#srvlang_'+text).load('ajax.php?t=prevajanje&a=extra_translation_save', {value: value, text: text, lang_id: srv_meta_lang_id, anketa: srv_meta_anketa_id}, 
		function () {
			$('#srvlang_'+text).removeClass('editing');
		}
	);*/

}

/**
* urejanje prevoda vprasanja
*/
function vprasanje_prevod (spremenljivka) {
	
	if (_vprasanje_prevod_preview == 1) {
		_vprasanje_prevod_preview = 0;
		return;
	}
	
	if (!$('#vprlang_'+spremenljivka).hasClass('editing'))
		$('#vprlang_'+spremenljivka).addClass('editing').load('ajax.php?t=prevajanje&a=vprasanje_prevod', {spremenljivka: spremenljivka, lang_id: srv_meta_lang_id, anketa: srv_meta_anketa_id});
		
	
}

/**
* shrani prevod vprasanja
*/
function vprasanje_prevod_save (spremenljivka) {
	
	$.post('ajax.php?t=prevajanje&a=vprasanje_prevod_save', $('form#vprasanje_prevod_'+spremenljivka).serialize(),
		function (data) {
			$('#vprlang_'+spremenljivka).html(data).removeClass('editing');
		}
	);
	
}

function prevajanje_bind_click () {
	
	$('fieldset.locked').bind('click', function (event) {
		inline_bind_click(event);
	});
	
}



