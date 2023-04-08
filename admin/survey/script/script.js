//meta podatki
var loaded = false;

var editor_init = false;	// ce smo ze inicializirali editor (se zgodi, ko prvic odpremo editor na strani)

var srv_meta_anketa_id;
var srv_meta_podstran;
var srv_meta_akcija;
var srv_meta_grupa;
var srv_meta_branching;
var srv_meta_full_screen_edit;

var activationTimer; 	// timer za aktivacijo

var _moved = 0; // ce je blo vprasanje premaknjeno, potem ni blo kliknjeno in to preprecimo
var _fullscreen = 0; // pove, ce smo v fullscreen urejanju vprasanja (da vemo katero polje refreshat)

var forma_preview = 0; // ce draggamo novo vprasanje pri formah se nastavi na 1 - da ne prikazemo preview-ja

var __vnosi = 0;        // pove ce smo v vnosih (1)
var __analiza = 0;        // pove ce smo v analizi (1)
var __tabele = 0;        // pove ce smo v analizi v krostabulacijah(1)

// poklice se v onload.js
function load_meta_variables () {
	srv_meta_anketa_id = $("#srv_meta_anketa_id").val();
	srv_meta_anketa_hash = $("#srv_meta_anketa_hash").val();
	srv_meta_podstran = $("#srv_meta_podstran").val();
	srv_meta_akcija = $("#srv_meta_akcija").val();
	srv_meta_grupa = $("#srv_meta_grupa").val();
	srv_meta_branching = $("#srv_meta_branching").val();
	srv_meta_full_screen_edit = ($("#srv_meta_full_screen_edit").val() == 1 ? true : false);
    
	loaded = true;
};

// nastavimo da se prikaze loading ob AJAX klicih, po nekem casu prikazemo vecji loading, da se bolj opazi
function ajax_start_stop () {
	var t;
    $('body').ajaxStart(function() {
    	t=setTimeout(function() {$('body').addClass('waitlong');},1000);
        $('body').addClass('wait');
    }).ajaxStop(function() {
    	clearTimeout(t);
        $('body').removeClass('wait').removeClass('waitlong');
    });
};

// razlicne stvari, ki se nastavijo na zacetku, ko se nalozi stran
function onload_init () {

	// info box
	$("#surveyInfo").hover(
		function() {
			isInfoOver = true;
		},
		function() {
			isInfoOver = false;
			setTimeout(function() {hideBottomInfoBox()}, 350);
		}
	);


	$('#surveyTrajanje_close').click(function() {
        $('#surveyTrajanje').fadeOut('slow');
        $('#fade').fadeOut('slow');
	    return false;
	});


	// prikaz izvozov na hover cez ikono
	hover_show_export();

	// prikaz nastavitev v urejanju ankete na hover cez ikono
	hover_show_settings()

	// prikaz vprasanj za dodajanje na hover cez ikono
	hover_show_qtypes()

	// prikaz filtrov na hover cez ikono
	hover_show_filter()
	hover_show_filter2();


	// vsem input poljem ki imajo nastavljen attribut maxLength dodamo omejitev
	// za izpis števila znakov dodamo span z id-jem, ki je enak input + "_chars"
	$('input[maxlength]').keyup(function(){
	        var max = parseInt($(this).attr('maxlength'));
	        if($(this).val().length > max){
	            $(this).val($(this).val().substr(0, $(this).attr('maxlength')));
	        }
			$("#"+$(this).attr('id')+'_chars').html($(this).val().length + " / "+max);
	    });
	$('textarea[maxlength]').keyup(function(){
	        var max = parseInt($(this).attr('maxlength'));
	        if($(this).val().length > max){
	            $(this).val($(this).val().substr(0, $(this).attr('maxlength')));
	        }
			$("#"+$(this).attr('id')+'_chars').html($(this).val().length + " / "+max);
	    });

	// vse elemente z atributom srv_misc="true" shrani v tabelo srv_misc
	$("[srv_misc=true]").bind("blur", {}, function(e) {
		if ( $(this).attr('srv_misc') == "true" )
			$.post('ajax.php?a=saveSrvMisc', {what:$(this).attr("name"),value:$(this).val(),podstran: srv_meta_podstran});
		});
	// vse elemente z atributom srv_survey_misc="true" shrani v tabelo
	// srv_survey_misc
	$("[srv_survey_misc=true]").bind("blur", {}, function(e) {
		if ( $(this).attr('srv_survey_misc') == "true" )
			$.post('ajax.php?a=saveSrvSurveyMisc', {anketa: srv_meta_anketa_id,what:$(this).attr("name"),value:$(this).val(),podstran: srv_meta_podstran});
		});
	$("#startsManual").on("click", function(event) {
		updateManual();
		return false;
	});
	$("#expireManual").on("click", function(event) {
		updateManual();
		return false;
	});
	$("#startsManual1").on("click", function(event) {
		updateManual1();
		return false;
	});
	$("#expireManual1").on("click", function(event) {
		updateManual1();
		return false;
	});
	$('#anketa_activate_more').on("click", function(event) {
		$('#div_anketa_activate_more').hide();
		$('#anketa_activate_settings').show();
	});
	$('#anketa_activate_note').on("click", function(event) {
		stopActivationTimer();
	});


	$('#xtradiv strong').on("click", function(event) {
		$('#xtradivSettings').toggle();
	});


	$('#test_user_alert span').blink({
        fadeIn: 100, 
        fadeOut: 200,
        pauseShow:500
    });	
	
	// Search na vrhu po pritisku na enter skoci na drupal search
	$('#searchSurvey').keypress(function (e) {
		if (e.which == 13) {
			executeDrupalSearch();
			return false;
		}
	});
}

/**
 * Called from onblur event in element for hash link comment update
 * @param {type} input - input cell
 * @returns {undefined}
 */
function hash_comment_change(input){
    $.post('ajax.php?t=SurveyUrlLinks&a=saveComment',
		{anketa:$(input).data('anketa'),hash:$(input).attr('data-hash'),comment:$(input).text()});
}

/**
 * Call on refresh param change
 * @param {type} input
 * @returns {undefined}
 */
function hash_refresh_change(input){
    var refresh = $(input).is(':checked') ? '1' : '0';
    $.post('ajax.php?t=SurveyUrlLinks&a=saveRefresh',
		{anketa:$(input).data('anketa'),hash:$(input).attr('data-hash'),refresh:refresh});
}

/**
 * Called from onblur event in element for hash link access password update
 * @param {type} input
 * @returns {undefined}
 */
function hash_access_password_change(input){
    $.post('ajax.php?t=SurveyUrlLinks&a=saveAccessPassword',
		{anketa:$(input).data('anketa'),hash:$(input).attr('data-hash'),access_password:$(input).text()});
}

// funkcija za redirectat po poslanem POST ajax klicu
jQuery.redirect = function(url, options) {
    $.post(url, options,
        function (url_redirect) {
            window.location = url_redirect;
        }
    );
}

// vrne ID containerja (diva) kamor se pisejo podatki ob editiranju vprasanj (odvisno je ce imamo fullscreen, normal, ali samo eno na desni v branchingu)
function getContainer (spremenljivka) {
    if (_fullscreen == 1)                           // fullscreen
        return '#fullscreen';
    if (collapsed_content == 1)                     // normalen inline nacin
        return '#spremenljivka_'+spremenljivka;
    else                                            // v branchingu, ko je samo
													// 1 na desni
        return '#branching_vprasanja';
}

// obvestilo za upgrade browserja
function browser_alert () {

	// obvestilo za IE uporabnike pred verzijo 8
	if ($.browser.msie && gup('anketa')=="") {	// samo na prvi strani
		if (parseInt($.browser.version) < 8)
			if (confirm(lang['srv_upgrade_ie'])) {
				window.location = 'http://www.microsoft.com/windows/internet-explorer/worldwide-sites.aspx';
			}
	}

}

// na hover prikaz export ikon
function hover_show_export(){
	var timer;

	$("#hover_export_icon").hover(
		function () {
			clearTimeout(timer);
			$("#hover_export").show();
		},
		function () {
			timer = setTimeout(function () {
				$("#hover_export").hide();
			}, 500);
		}
	);
	$("#hover_export").hover(
		function () {
			clearTimeout(timer);
			$("#hover_export").show();
		},
		function () {
			timer = setTimeout(function () {
				$("#hover_export").hide();
			}, 500);
		}
	);
}

// na hover prikaz nastavitev v urejanju
function hover_show_settings(){
	var timer;

	$("#toolbox_advanced_settings").hover(
		function () {
			clearTimeout(timer);
			$("#toolbox_advanced_settings_holder").show();
		},
		function () {
			timer = setTimeout(function () {
				$("#toolbox_advanced_settings_holder").hide();
			}, 500);
		}
	);
	$("#toolbox_advanced_settings_holder").hover(
		function () {
			clearTimeout(timer);
			$("#toolbox_advanced_settings_holder").show();
		},
		function () {
			timer = setTimeout(function () {
				$("#toolbox_advanced_settings_holder").hide();
			}, 500);
		}
	);
}

// na hover prikaz vprasanj v urejanju
function hover_show_qtypes(){
	var timer;

	$(".new_adv").hover(
		function () {
			clearTimeout(timer);
			$("#toolbox_add_advanced").show();
		},
		function () {
			timer = setTimeout(function () {
				$("#toolbox_add_advanced").hide();
			}, 500);
		}
	);
	$("#toolbox_add_advanced").hover(
		function () {
			clearTimeout(timer);
			$("#toolbox_add_advanced").show();
		},
		function () {
			timer = setTimeout(function () {
				$("#toolbox_add_advanced").hide();
			}, 500);
		}
	);
}

// na hover prikaz filtrov (podatki, analize...)
function hover_show_filter(){
	var timer;

	$("#filters_span").hover(
		function () {
			clearTimeout(timer);
			$("#div_analiza_filtri_right").show();
		},
		function () {
			timer = setTimeout(function () {
				$("#div_analiza_filtri_right").hide();
			}, 500);
		}
	);
	$("#div_analiza_filtri_right").hover(
		function () {
			clearTimeout(timer);
			$("#div_analiza_filtri_right").show();
		},
		function () {
			timer = setTimeout(function () {
				$("#div_analiza_filtri_right").hide();
			}, 500);
		}
	);
}

// na hover prikaz nastavitev (podatki, analize...) - ideja Vasje da se filtre razdeli na 2 ikoni
function hover_show_filter2(){
	var timer;

	$("#filters_span2").hover(
		function () {
			clearTimeout(timer);
			$("#div_analiza_filtri_right2").show();
		},
		function () {
			timer = setTimeout(function () {
				$("#div_analiza_filtri_right2").hide();
			}, 500);
		}
	);
	$("#div_analiza_filtri_right2").hover(
		function () {
			clearTimeout(timer);
			$("#div_analiza_filtri_right2").show();
		},
		function () {
			timer = setTimeout(function () {
				$("#div_analiza_filtri_right2").hide();
			}, 500);
		}
	);
}

// ----------------------- funkcije, ki se klicejo iz htmlja -----------------------

// doda novo anketo
function anketa () {
    var akronim = jQuery.trim($("#novaanketa_akronim").val());
    var naslov = jQuery.trim($("#novaanketa_naslov").val());
    var intro_opomba = jQuery.trim($("#novaanketa_opis").val());
    var survey_type = jQuery.trim($("#survey_type").val());

    $.redirect('ajax.php?a=anketa', {naslov: naslov, intro_opomba: intro_opomba, akronim: akronim, survey_type:survey_type});
}

function new_anketa() {
	var naslov = $("#novaanketa_naslov").val();
	$("#fullscreen").load('ajax.php?a=new_anketa', {naslov:naslov}).fadeIn('fast');
	$('#fade').fadeTo('slow', 1);
}

// prikaže info Box
var isInfoOver = false;
var isInfoLoaded = false;
function showInfoBox(action,e) {
	if (action == 'show') {

		/*
		isInfoOver = true;
		// ugotovimo elementovo pozicijo
		var pos = e.offset();
		// ugotovimo dimenzije info boxa
		var surveyInfo_height = $("#surveyInfo").height();

		// ce infobox ne moremo prikazati navzgor, ga prikazemo navzdol
		// ugotovimo velikost strani
	    var w_height = $(window).height();
	    var w_width = $(document).width();

		if (pos.top - surveyInfo_height && (pos.top + surveyInfo_height) < w_height) {
			// risemo navzdol
			show_top = pos.top + e.height() + 5;
		}
		else { // risemo navzgor
			show_top = pos.top - surveyInfo_height - e.height() - 5;
		}*/

		if (!isInfoLoaded) {
			$('#surveyInfo_msg').load('ajax.php?a=displayInfoBox', {anketa: srv_meta_anketa_id});
			isInfoLoaded = true;
		}
		//$("#surveyInfo").css( { "left": (pos.left+e.width()+5)+ "px", "top": show_top + "px" } ).show().draggable();

	} /*else {
		isInfoOver = false;
		setTimeout(function() {hideBottomInfoBox()}, 350);
	}*/
}

function hideBottomInfoBox() {
	if (isInfoOver == false)
		$('#surveyInfo').fadeOut(400);

}

// spremeni ime ankete
function edit_anketa_naslov (anketa) {
    $('#anketa_naslov').load('ajax.php?a=edit_anketa', {anketa: anketa, naslov: $('#anketa_polnoIme').val()});
}
// spremeni opombo ankete
function edit_anketa_note (anketa){
	$.post('ajax.php?a=edit_anketa_note', {anketa: anketa, note: $('#anketa_note').val()});
}
// spremeni akronim-kratko ime ankete
function edit_anketa_akronim (anketa){
	$.post('ajax.php?a=edit_anketa_akronim', {anketa: anketa, akronim: $('#anketa_akronim').val()});
}

// spremeni active status ankete
function anketa_active (anketa, state, folders, hierarhija) {
	var hierarhija = hierarhija || 0;

	if (state == 0) {

		// Če aktiviramo anketo, najprej vprašamo po datumih
		$.post('ajax.php?t=branching&a=check_pogoji&izpis=short', {anketa: anketa}, function (data) {
			if (data == '1') {	// vse ok, anketa nima napak
				$('#fade').fadeTo('slow', 1);
				$('#fullscreen').html('').fadeIn('slow');
				// aktiviramo anketo in prikažemo okno
				$("#fullscreen").load('ajax.php?a=anketa_show_activation', {anketa: anketa, folders: folders}, function() {
					if(hierarhija == 1){
						$('#divAvtoClose').hide();
						// Pošljemo zahtevek za aktiviranje hierarhije
						hierarhija_active(anketa);
					}else {
						startTimerActivation(anketa, folders);
					}
				});
            } 
            // anketa ima napake
            else {			
				// izpišemo obvestilo o napakah
                $('#fade').fadeIn('slow');
                $('#surveyTrajanje').fadeIn('slow');
				$('#surveyTrajanje_msg').html(data);
			}
		});

	}
	else
	{ // pri deaktvaciji ne sprašujemo po datumih

		// ali lahko disejblamo anketo
		var canDisableSurvey = 'false';

		// Preverimo ali je anketa trajna
		$.post('ajax.php?&a=check_survey_permanent', {anketa: anketa}, function (response) {
			if (response == 'true') {
				// anketa je označena kot trajna, zato damo pred deaktivacijo alert
				if (confirm(lang["srv_permanent_diable"])) {
					canDisableSurvey = 'true';
				} else {
					canDisableSurvey = 'false';
				}
			} else {
				canDisableSurvey = 'true';
			}
			if (canDisableSurvey == 'true') {
				// Vprašamo ali želi deaktivirat
				if (confirm(lang["srv_disable"])) {
					if ( folders == 'true' ) // ali smo v folderjih
					{
						// $('#folders').load('ajax.php?a=anketa_active&ajaxa='+gup("a"), {anketa: anketa, folders: folders});
						// po novem osvezimo samo ikonico za aktivnost - STARA KNJIZNICA
						if($("ul#surveyList").length){
							$("ul#surveyList").find("li#anketa_list_"+anketa).find(".sl_active").load('ajax.php?a=anketa_active&ajaxa='+gup("a"), {anketa: anketa, folders: folders});
						}
						// NOVA KNJIZNICA
						else{
							$.post('ajax.php?a=anketa_active&ajaxa='+gup("a"), {anketa: anketa, folders: folders}, function() {
								window.location.reload();
							});
						}
					}
					else
					{
						//$('#anketa_activation').load('ajax.php?a=anketa_active&ajaxa='+gup("a"), {anketa: anketa, folders: folders}, function() {
						$.post('ajax.php?a=anketa_active&ajaxa='+gup("a"), {anketa: anketa, folders: folders}, function() {
							window.location.reload(); return;
						});
					}
				}
			}

		});
	}
}

function startTimerActivation(anketa,folders) {
	var sec = $('#divAvtoClose span').text() || 0;
	var active = 1;

	activationTimer = setInterval(function() {
		active = $('#divAvtoClose').attr('active');
		if (active == 1 || active == '1') {
			$('#divAvtoClose span').text(--sec);
			if (sec == 0) {
				// ustavimo timer in
				stopActivationTimer();
				// zapremo brez dodatnega shranjevanja anketa je tako že aktivirana

				anketa_activate_save(anketa,folders);
			}
		} else {
			stopActivationTimer();
		}
	}, 1000);
}

//funkcija preveri, če je hierarhija že zgrajena
function hierarhija_active(anketa_id){
	$.post("ajax.php?anketa=" + anketa_id + "&t=hierarhija-ajax&a=aktivacija_ankete");
}

/**
 * Preverimo, če ima anketa ustrezna vprašanja za hierarhijo in ponudimo dodatne opcije
 */
function preveriAnketoZaHierarhijo(anketa_id){
	if($('#advanced_module_hierarhija').prop('checked')) {
        $.post("ajax.php?anketa=" + anketa_id + "&t=hierarhija-ajax&a=preveri-ustreznost-ankete").success(function (response) {

        		if(response == 'dostop')
        			return false;

        	// Vklopi in default nastavitve
            $('#hierarhija-opcije-vklopa').show();
            $('#error').html('').hide();
            $('#hierarhija-knjiznica').html('').hide('');
            $('#hierarhija-prevzeta').html('').hide();
            $('.buttonwrapper a').removeClass('button_gray').addClass('ovalbutton_orange');


            // Vse damona prevzeto
            $('#hierarhija-opcije-vklopa label').each(function(){
                $(this).find('input').removeAttr('checked').removeAttr('disabled');
                $(this).css('color', '');
            });

            if (response == 'privzeta') {
                // Onemogočena izbira lastne ankete, ker je ni
                $('#obstojeca-anketa').attr('disabled', 'disabled');
                $('#obstojeca-anketa').parent().css('color', '#bbb');

                //izbira prevzete ankete
                $('#prevzeta-anketa').attr('checked', 'checked');
                pridobiKnjiznicoZaHierarhijo('privzeta');
            }

            if(response == 'napacen-tip' || response == 'samo-besedilni-tip'){
                // Onemogočena izbira lastne ankete, ker je napaka v njej
                $('#obstojeca-anketa').attr('disabled', 'disabled');
                $('#obstojeca-anketa').parent().css('color', '#bbb');

                // Doamo opozorilo, ker ni pravega tipa
				if(response == 'napacen-tip')
               		 $('#error').show().html(lang['srv_hierarchy_create_error_2']);

				// Opozorilo, ker je samo besedilo brez vprašaj tipa 6
                if(response == 'samo-besedilni-tip')
                    $('#error').show().html(lang['srv_hierarchy_create_error_3']);

            }

            // Ponovno aktiviramo modul
            if(response == 'ponovna-aktivacija')
                return toggleAdvancedModule('hierarhija', 1);


			// Vse v redu izberemo obstoječo anketo
			if(response == 'ok')
                $('#obstojeca-anketa').attr('checked', 'checked');



        });
    }else{
        $('#hierarhija-opcije-vklopa').hide();
	}
}

/**
 * Pridobi vse ankete, ki so v knjižnici za hierarhijo
 */
function pridobiKnjiznicoZaHierarhijo(vrsta){
  $('.ime-ankete').hide();

	if(vrsta == 'vse')
    	$.post('ajax.php?anketa='+srv_meta_anketa_id+'&t=hierarhija-ajax&a=get-all-hierarchy-library', {
    	    vrsta: 'vse'
        }).success(function(response){
		    $('#hierarhija-knjiznica').html(response).show();
         });

	if(vrsta == 'privzeta') {
	    setTimeout(function(){
            var vsebina = '<span><a href="/main/survey/index.php?anketa=122986&amp;preview=on" target="_blank" title="Predogled ankete">'+
                '<span class="sprites preview"></span>'+
                '</a></span>';
            $('#hierarhija-prevzeta').html(vsebina).show();
        }, 100);
	}

	if(vrsta == 'nova'){
		$('.ime-ankete').show();
	}



}

/**
 * Glede na izbiro ankete aktiviraj modul hierarhija
 */
function potrdiIzbiroAnkete(){
	var izbira = $('[name="izberi-anketo"]:checked').val() || 0;

	// nič ni izbrano
	if(izbira == 0)
		return false;

	// če je obstoječa anketa potem samo aktiviramo modul
	if(izbira == 'obstojeca')
		return toggleAdvancedModule('hierarhija', 1);

	if(izbira == 'nova')
		ustvariPraznoAnketoInVkljuciModulSA();

	if(izbira == 'prevzeta')
        kopirajPrevzetoAnketoAliIzKnjizniceZaHierarhijo('privzeta'); // ID anketa 122986 - privzeta na 1ka.si // 5544 je na test.1ka.si

    if(izbira == 'knjiznica'){
        var knjiznica_id = $('[name="knjiznica_izbira"]:checked').val();
        kopirajPrevzetoAnketoAliIzKnjizniceZaHierarhijo(knjiznica_id);
    }

}


/**
 * UStvari prazno anketo in vključi modul SA
 */
function ustvariPraznoAnketoInVkljuciModulSA(){
  var survey_type = 2;

  var naslov = jQuery.trim($("#novaanketa_naslov").val());
  if ($("#novaanketa_naslov_1").length > 0) {
    naslov = jQuery.trim($("#novaanketa_naslov_1").val());
  }

  var akronim = naslov;
  if ($("#novaanketa_akronim_1").length > 0) {
    var akronim = jQuery.trim($("#novaanketa_akronim_1").val());
  }

  var folder = '-1';


  var intro_opomba = jQuery.trim($("#novaanketa_opis").val());

  if ($("#lang_resp").length > 0 && $("#lang_resp").val() > 0) {
    var lang_resp = jQuery.trim($("#lang_resp").val());
  } else {
    var lang_resp = 1;
  }

  var skin =  '1kaBlue';

  $.redirect('ajax.php?a=nova-anketa-in-hierarhija', {
  	naslov: naslov,
		intro_opomba: intro_opomba,
		akronim: akronim,
		survey_type:survey_type,
		lang_resp:lang_resp,
		skin:skin,
		folder:folder,
		vkljuciHierarhijo: 1
  });

  // $.redirect('ajax.php?a=anketa', {naslov: naslov,fgdgdfgdgd intro_opomba: intro_opomba, akronim: akronim, survey_type:survey_type, lang_resp:lang_resp, skin:skin, folder:folder});
}


/**
 * Kopiraj prevzeto anketo ali anketo iz knjižnice
 *
 * @param int id
 * @return reload with new id
 */
function kopirajPrevzetoAnketoAliIzKnjizniceZaHierarhijo(id){
    // Kopiramo prevzeto anketo za SA - anketa
    $.post('ajax.php?t=library&a=anketa_copy_new', {
        ank_id: id,
        hierarhija: 1,
		novaHierarhjia: 1
    }).success(function(response){
        // Shranimo nov id ankete
        srv_meta_anketa_id = response;

        // Vključimo modul hierarhija
        toggleAdvancedModule('hierarhija', 0);

        // Preusmerimo z novim anketa ID na gradnjo hierarhije
        var url = window.location.origin + window.location.pathname;
        window.location = url+'?anketa='+srv_meta_anketa_id+'&a=hierarhija_superadmin&m=uredi-sifrante';
    });
}

function stopActivationTimer() {
	clearInterval(activationTimer);
	$('#divAvtoClose').attr('active',0);
	$('#divAvtoClose').fadeOut('fast');
}

function anketa_activate_save(anketa,folders) {
	// ali imamo odprto okno z dodatnimi nastavitvami
	var doSave = ($("#anketa_activate_settings").css("display") == "none")  ? false : true;

	if (doSave == true) {
		// shraniti je potrebno morebine nove nastavitve
		var durationType = $("input[name=radioTrajanje]:checked").val();
		var durationStarts = $("#startsManual").val();
		var durationExpire = $("#expireManual").val();

		var voteCountLimitType = $("input[name=vote_count_limit]:checked").val();
		var voteCountValue = $("#vote_count_val").val();

		$.post('ajax.php?a=anketa_save_activation', {anketa:anketa,
			durationType:durationType, durationStarts:durationStarts, durationExpire:durationExpire, voteCountLimitType:voteCountLimitType, voteCountValue:voteCountValue}, function (response) {
				refresh_anketa_activation(anketa,folders);
			});
	} else {
		refresh_anketa_activation(anketa,folders);
	}
}

function refresh_anketa_activation(anketa,folders) {
	window.location.reload(); return;
}

function autoCloseActivationDiv(anketa, folders)
{
	timeout = $('#spanAvtoClose').html();
	timeout--;

	if ( $('#divAvtoClose').is(':visible') )
	{
		if (timeout > 0)
		{
			$('#spanAvtoClose').html(timeout);
			closeTimeout = setTimeout(function() {autoCloseActivationDiv(anketa, folders);}, 1000);
		}
		else
		{ // avtomatsko aktiviramo anketo (1 mesec)
			anketa_setActive(anketa, folders);
		}
	}
}

// uporabnik je potrdil datume aktivacije
function anketa_setActive(anketa, folders)
{ // TODO: kontrolo na datume expire < starts = alert(error)
	var manual = $("input[name=radioTrajanje]:checked").val();
	if ( manual == 0 )
	{
		var starts = $("#startsAuto").html();
		var expire = $("#expireAuto").html();
	}
	else
	{
		var starts = $("#startsManual").val();
		var expire = $("#expireManual").val();
    }
    
    $('#surveyTrajanje').fadeOut('slow');
    $('#fade').fadeOut('slow');

	if ( folders == 'true' )
	{
		//		$('#folders').load('ajax.php?a=anketa_active&ajaxa='+gup("a"), {anketa: anketa, starts: starts, expire: expire, manual: manual, folders: folders});
		// po novem osvezimo samo ikonico za aktivnost
		$("ul#surveyList").find("li#anketa_list_"+anketa).find(".sl_active").load('ajax.php?a=anketa_active&ajaxa='+gup("a"), {anketa: anketa, starts: starts, expire: expire, manual: manual, folders: folders});
	}
	else
	{
		$('#anketa_active').load('ajax.php?a=anketa_active&ajaxa='+gup("a"), {anketa: anketa, starts: starts, expire: expire, manual: manual, folders: folders},
				function() {
					//reload tudi spodnjih ikon pri formah
					if ($("#anketa_aktivacija_note").length > 0) {
						$("#anketa_aktivacija_note").load('ajax.php?a=anketa_aktivacija_note', {anketa: anketa});
					}
					if ($("#btn_mailto_preview_holder").length > 0) {
						// popravimo gumb za preview email vabil ce smo v email vabilih
						$("#btn_mailto_preview_holder").load('ajax.php?a=anketa_aktivacija_mailto_preview', {anketa: anketa});
					}
					if ($("#trajna_anketa").length > 0) {
						// ce smo na trajanju osvezimo trajanje
						$("#anketa_edit").load('ajax.php?a=refresh_nastavitve_trajanje', {anketa: anketa});
					}

				});
	}
}

function anketa_lock (anketa, locked, mobile_created) {
	if (locked == 0) {
                var text = lang['srv_unlock_alert'];
                if(mobile_created == 1)
                    text += '\n\n' + lang['srv_unlock_mobile'];
		if (confirm(text)) {
			$.redirect('ajax.php?t=branching&a=anketa_lock', {anketa: anketa, locked: locked});
		}
	} else {
		$.redirect('ajax.php?t=branching&a=anketa_lock', {anketa: anketa, locked: locked});
	}
}

// spremeni ime ankete
function anketa_title_edit (anketa, status, naslov) {
	//status: 1 = start edit; 2 = save; 3 = stop edit and save
	if ( status == null || status == undefined ) {
		status = 1;
	}
	if ( (naslov == null || naslov == undefined ) && $("#naslov_edit_box") && $("#naslov_edit_box").val() !== 'undefined' ) {
		naslov = $("#naslov_edit_box").val();
	}
	// v spodnje okno dodelimo enako ime
	if (naslov != '') {
		$('#anketa_polnoIme').val(naslov);
	}

    $('#fullscreen').html('').fadeIn('slow').draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
    $('#fade').fadeTo('slow', 1);
    $('#fullscreen').load('ajax.php?a=quick_title_edit&ajaxa='+gup("a"), {anketa: anketa, status: status, naslov: naslov}, function(){
		$('#novaanketa_naslov_1').focus();
	});
}

function quick_title_edit_save(quick_settings) {
	var naslov = jQuery.trim($("#novaanketa_naslov_1").val());
	var akronim = jQuery.trim($("#novaanketa_akronim_1").val());
    var intro_opomba = jQuery.trim($("#novaanketa_opis_1").val());

    $.redirect('ajax.php?a=quick_title_edit_save&ajaxa='+gup("a"), {anketa: srv_meta_anketa_id, naslov: naslov, akronim:akronim, intro_opomba:intro_opomba, quick_settings:quick_settings});
}

function quick_title_edit_cancel() {
	$('#fade').fadeOut('slow');
	$('#fullscreen').fadeOut('slow').html('');
}

// izbrise anketo
function anketa_delete (anketa, confirmtext) {
	if (confirm(confirmtext)) {
		$.redirect('ajax.php?a=anketa_delete', {anketa: anketa});
	}
}

// spremeni tip ankete
function anketa_tip (tip) {
//	$('#anketa_active').load('ajax.php?a=anketa_tip&ajaxa='+gup("a"), {anketa: srv_meta_anketa_id, tip: tip});
	$.post('ajax.php?a=anketa_tip', {anketa: srv_meta_anketa_id, tip: tip});
}

// doda novo grupo
function nova_grupa () {

    $.redirect('ajax.php?a=nova_grupa', {anketa: srv_meta_anketa_id, grupa: srv_meta_grupa});
}

// spremeni ime grupe
function edit_grupa (grupa, naslov) {

	$.post('ajax.php?a=edit_grupa', {grupa: grupa, naslov: naslov});
	$("#fieldset_page_"+grupa).find("legend").find("#grupaName").html(naslov);
	$("#fieldset_page_"+grupa).find("legend").find("#naslov_"+grupa).val(naslov);

    $('#pb_line_page_preview_'+grupa).html(naslov);

}
// spremeni ime grupe
function save_edit_grupa(grupa, naslov) {

	$("#fieldset_page_"+grupa+" legend").load('ajax.php?a=save_edit_grupa', {grupa: grupa, naslov: naslov, anketa: srv_meta_anketa_id});
	$("#grupa_"+grupa+" h3 a").html(""+naslov);
	//$("#grupa_"+grupa).find("h3").find("input[name=naslov]").val(""+naslov);
}

// spremeni ime grupe
function save_edit_uporabnost_link(grupa, link) {

	$("#fieldset_page_"+grupa+" legend").load('ajax.php?a=save_edit_uporabnost_link', {grupa: grupa, link: link, anketa: srv_meta_anketa_id});

}

// izbrise grupo
function brisi_grupo (grupa, text) {

	if (confirm(text)) {
		$.redirect('ajax.php?a=brisi_grupo', {grupa: grupa, anketa: srv_meta_anketa_id, thisgrupa: srv_meta_grupa});
	}
}

function insert_grupa_before(grupa) {
	$.redirect('ajax.php?a=insert_grupa_before', {grupa: grupa, anketa: srv_meta_anketa_id});
}

function grupa_recount(prevent_recount) {
	if (prevent_recount) {
		$.post('ajax.php?a=outputLanguageNote', {anketa: srv_meta_anketa_id, note: 'srv_grupa_recount_alert_have_branching'}, function(response) { genericAlertPopup('alert_parameter_response',response);   return false;});
	} else {
		$.redirect('ajax.php?a=grupa_recount', {anketa: srv_meta_anketa_id, grupa: srv_meta_grupa});
	}
}

// doda novo spremenljivko
function nova_spremenljivka (grupa, spremenljivka) {
    $('#vprasanja').load('ajax.php?a=nova_spremenljivka', {anketa: srv_meta_anketa_id, grupa: srv_meta_grupa, spremenljivka: spremenljivka},
        function () {

    		$("#grupe").load('ajax.php?a=refresh_grupe', {anketa: srv_meta_anketa_id, grupa: srv_meta_grupa});

			//$('#clipboard').fadeOut();
            /*$.post('ajax.php?t=branching&a=get_new_spr', {anketa: srv_meta_anketa_id},
                function (new_spr) {
                    //--editor_display(new_spr);
                }
            );*/

    		// updejtamo še levo stran z gupo
    		$("#grupe").load('ajax.php?a=refresh_grupe', {anketa: srv_meta_anketa_id, grupa: grupa});

			// osvezimo spodnji gumb
    	    refreshBottomIcons('gray');
    	}
    );
}

// doda novo spremenljivko v posamezno grupo na pogledu z več stranmi
function nova_spremenljivka_in_grupa(grupa, spremenljivka) {
	if (srv_meta_full_screen_edit) { // edit v full screen načinu
		$.post('ajax.php?a=nova_spremenljivka_in_grupa',{anketa: srv_meta_anketa_id, grupa: grupa, spremenljivka: spremenljivka, full_screen: srv_meta_full_screen_edit},
			function (nova_spremenljivka_id) {
				var movable = (srv_meta_branching == 0) ? ' movable' : '';
				if (spremenljivka) { //vstavimo pred izbrano spremenljivko
					$('<div id="spremenljivka_' + nova_spremenljivka_id + '" class="spremenljivka' + movable + '"></div>').insertBefore("#spremenljivka_"+spremenljivka);
				} else { // vstavimo pred divom #nova_spremenljivka
					$('<div id="spremenljivka_' + nova_spremenljivka_id + '" class="spremenljivka' + movable + '"></div>').insertBefore($("#fieldset_page_"+grupa).find("#nova_spremenljivka"));
				}
				// refreshamo levo stran z grupami
				$("#grupe").load('ajax.php?a=refresh_grupe', {anketa: srv_meta_anketa_id, grupa: grupa});
				// damo spremenljivko v normalmode na desni strani
				$('#spremenljivka_' + nova_spremenljivka_id).load('ajax.php?a=normalmode_spremenljivka', {spremenljivka: nova_spremenljivka_id, branching: srv_meta_branching, anketa: srv_meta_anketa_id});
				// in odpremo edit v FS
				fullscreenmode_spremenljivka(nova_spremenljivka_id);
			}
		);
	} else {	// edit v normalnem načinu
		if (spremenljivka) {
			// vstavimo pred izbrano spremenljivko
			$.post('ajax.php?a=nova_spremenljivka_in_grupa',
					{anketa: srv_meta_anketa_id, grupa: grupa, spremenljivka: spremenljivka},
					function(nova_spremenljivka_response) {
						$(nova_spremenljivka_response).insertBefore($("#spremenljivka_"+spremenljivka));
						// updejtamo še levo stran z grupo
						$("#grupe").load('ajax.php?a=refresh_grupe', {anketa: srv_meta_anketa_id, grupa: grupa});
					});
		} else {
			// vstavimo pred divom #nova_spremenljivka
			$.post('ajax.php?a=nova_spremenljivka_in_grupa',
					{anketa: srv_meta_anketa_id, grupa: grupa, spremenljivka: spremenljivka},
					function(nova_spremenljivka_response) {
						$(nova_spremenljivka_response).insertBefore($("#fieldset_page_"+grupa).find("#nova_spremenljivka"));
						// updejtamo še levo stran z grupo
						$("#grupe").load('ajax.php?a=refresh_grupe', {anketa: srv_meta_anketa_id, grupa: grupa});
					});
		}
	}
}

// izbrise spremenljivko
function brisi_spremenljivko (spremenljivka, text, confirmed) {

	if (text == undefined) text = lang['srv_brisispremenljivkoconfirm'];

	if ( confirmed==1 || confirm(text) ) {

        if (confirmed == undefined) 
            confirmed = 1;

        $.post('ajax.php?a=brisi_spremenljivko', {spremenljivka: spremenljivka, confirmed: confirmed, grupa: srv_meta_grupa, anketa: srv_meta_anketa_id, branching: srv_meta_branching},
            function (data) {

                // to je v vnosih, ko lahko dodajamo dodatne kalkulacije
				if (__vnosi == 1) {          
					window.location.reload();
                } 
                // obicno...
                else {

        			$('#vprasanje_float_editing').hide();
        			$('#calculation').hide();
        			$('#quota').hide();
                    $('#fade').hide();
                    $('#dropped_alert').hide();

                	if (data.error == 0) {
    					//refreshLeft();
		                $('#branching').html(data.output);
		                refreshRight();
                    } 
                    else if (data.error == 1) {
                        $('#fade').fadeIn('slow');
        				$('#dropped_alert').html(data.output).fadeIn('slow').animate({opacity: 1.0}, 3000, function(){
                            $('#fade').fadeOut("slow");
                            $('#dropped_alert').fadeOut("slow");
                        });
                    } 
                    else if (data.error == 2) {
                        $('#fade').fadeIn('slow');
        				$('#dropped_alert').html(data.output).fadeIn('slow').css('width', '400px');
					}

				}
            }, 'json'
        );
    }
}

// doda novo vrednost v spremenljivko
function nova_vrednost (spremenljivka, other) {

    editor_save(spremenljivka, 2);

    $(getContainer(spremenljivka)).load('ajax.php?a=nova_vrednost', {spremenljivka: spremenljivka, other: other, anketa: srv_meta_anketa_id, branching: srv_meta_branching});

}

// spremeni ime vrednosti
function edit_vrednost (vrednost, naslov, naslov2, variable, refresh_spremenljivka) {

    $.post('ajax.php?a=edit_vrednost', {vrednost: vrednost, naslov: naslov, naslov2: naslov2, variable: variable},
        function () {
            // kadar vrednost editiramo s popup editorjem - refreshamo osnovni pogled
            if (refresh_spremenljivka > 0)
                editmode_spremenljivka(refresh_spremenljivka);
        }
    );
}

//spremeni sirino posamezne vrednosti(pri besedilu)
function edit_vrednost_size (spremenljivka, vrednost, size){

    $("#spremenljivka_"+spremenljivka).load('ajax.php?a=edit_vrednost_size', {spremenljivka: spremenljivka, vrednost: vrednost, size: size, anketa: srv_meta_anketa_id, branching: srv_meta_branching});
}

// spremeni ime vrednosti vsote (pri tipu vsota)
function edit_vsota (vrednost, spremenljivka) {

    $.post('ajax.php?a=edit_vsota', {vrednost: vrednost, spremenljivka: spremenljivka});
}

// spremeni omejitev vsote (pri tipu vsota)
function edit_limit (min, vrednost, spremenljivka) {

    $.post('ajax.php?a=edit_limit', {min: min, vrednost: vrednost, spremenljivka: spremenljivka});
}

// spremeni tip omejitve (pri tipu vsota)
function edit_vsota_omejitve (spremenljivka, checkbox) {

	var tip;
	if (checkbox.checked)
		tip = 1;
	else
		tip = 0;

    $('#vsota_'+spremenljivka).load('ajax.php?a=edit_vsota_omejitve', {tip: tip, spremenljivka: spremenljivka});
}

// odpre editor za editiranje vrednosti
function editor_vrednost (vrednost) {

    $('#div_float_editing').html('');
    $('#div_float_editing').fadeIn("slow");

    $('#div_float_editing').load('ajax.php?a=editor_vrednost', {'vrednost': vrednost, anketa: srv_meta_anketa_id},
        function () {
            create_editor('naslovvrednost_'+vrednost);
        }
    ).draggable({delay:100, ghosting:	true , cancel: 'input, textarea, select, .buttonwrapper'});
}

// zapre editor za editiranje vrednosti
function editor_vrednost_close (vrednost, spremenljivka) {

    var content = CKEDITOR.get('naslovvrednost_'+vrednost).getContent();
    content = content.replace('<p>', '');
    content = content.replace('</p>', '');

    edit_vrednost(vrednost, content, $('#naslov2_'+vrednost).val(), $('#variable_'+vrednost).val(), spremenljivka);

    remove_editor('naslovvrednost_'+vrednost);
    $('#div_float_editing').fadeOut("slow");

    // to smo prestavl v edit_vrednost, da se refresha sele, ko je zares
	// shranjeno v bazo (drugac vcasih pokaze se staro vrednost)
    // editmode_spremenljivka(spremenljivka);
}

// odpre editor za editiranje info (opombe)
function editor_note(spremenljivka) {
    $('#div_float_editing').html('');
    $('#div_float_editing').fadeIn("slow");

    $('#div_float_editing').load('ajax.php?a=editor_note', {anketa: srv_meta_anketa_id, spremenljivka: spremenljivka},
        function () {
            create_editor('naslovvnote_'+spremenljivka);
        }
    ).draggable({delay:100,  ghosting:	true , cancel: 'input, textarea, select, .buttonwrapper'});
}
//zapre editor opombe spremenljivke
function editor_note_close(spremenljivka) {
//	remove_editor('naslovvnote_'+spremenljivka);
	var content = CKEDITOR.get('naslovvnote_'+spremenljivka).getContent();
	 $.post('ajax.php?a=editor_note_save', {anketa: srv_meta_anketa_id, spremenljivka: spremenljivka, content: content}, function() {
		 // posodobimo spremenljvko
		 $(getContainer(spremenljivka)).load('ajax.php?a=editmode_spremenljivka', {spremenljivka: spremenljivka, branching: srv_meta_branching, anketa: srv_meta_anketa_id, grupa: srv_meta_grupa});

		    /*if (srv_meta_branching == 1) {
		        $('#branching_'+spremenljivka+' .branchborder').addClass('editing');
		    }*/
		    remove_editor('naslovvnote_'+spremenljivka);
		    $('#div_float_editing').fadeOut("slow");

	 });

}

// izbrise vrednost
function brisi_vrednost (spremenljivka, vrednost) {

    editor_save(spremenljivka, 2);

    $(getContainer(spremenljivka)).load('ajax.php?a=brisi_vrednost', {vrednost: vrednost, anketa: srv_meta_anketa_id, branching: srv_meta_branching});

}

// spremeni ime vrednosti grida
function edit_gridvrednost (spremenljivka, grid, naslov) {

    $.post('ajax.php?a=edit_gridvrednost', {spremenljivka: spremenljivka, grid: grid, naslov: naslov});
}

// prikaze polja za edit IDjev grida
function edit_grids (spremenljivka) {

	editor_save(spremenljivka, 2);

    $(getContainer(spremenljivka)).load('ajax.php?a=edit_grids', {spremenljivka: spremenljivka});
}

// spremeni ime spremenljivke grida
function edit_gridID (spremenljivka, grid, grd_id) {

    $.post('ajax.php?a=edit_gridID', {spremenljivka: spremenljivka, grid: grid, grd_id: grd_id});
}

// spremeni stevilo gridov
function edit_grid_number (spremenljivka, grids) {

	editor_save(spremenljivka, 2);

	$(getContainer(spremenljivka)).load('ajax.php?a=edit_grid_number', {spremenljivka: spremenljivka, grids: grids, anketa: srv_meta_anketa_id});
}

// vkljuci/izkljuci random nacin razvrscanja vrednosti v spremenljivki
function spremenljivka_random (spremenljivka) {

    editor_save(spremenljivka, 2);

    $(getContainer(spremenljivka)).load('ajax.php?a=spremenljivka_random', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id});
}

// toggla random na vrednosti
function random_vrednost (vrednost) {

    $('#random_vrednost_'+vrednost).load('ajax.php?a=random_vrednost', {vrednost: vrednost});

}

// vkljuci/izkljuci statistiko spremenljivke
function spremenljivka_stat (spremenljivka) {

    $('#stat_'+spremenljivka).load('ajax.php?a=spremenljivka_stat', {spremenljivka: spremenljivka});
}

// spremeni orientacijo spremenljivke
function spremenljivka_orientacija (spremenljivka) {

    $('#orientation_'+spremenljivka).load('ajax.php?a=spremenljivka_orientation', {spremenljivka: spremenljivka});
}

function spremenljivka_checkoxhide (spremenljivka) {
    $('#checkbox_hide_'+spremenljivka).load('ajax.php?a=spremenljivka_checkbox_hide', {spremenljivka: spremenljivka});
}



// reminder spremenljivke -- izkljucen / soft / hard
function spremenljivka_reminder (spremenljivka) {

    $('#reminder_'+spremenljivka).load('ajax.php?a=spremenljivka_reminder', {spremenljivka: spremenljivka});
}

// vkljuci/izkljuci sistemko spremenljivko
function spremenljivka_sistem (spremenljivka) {

    $('#sistem_'+spremenljivka).load('ajax.php?a=spremenljivka_sistem', {spremenljivka: spremenljivka});
}

// vkljuci/izkljuci prikaz spremenljivke
function spremenljivka_visible (spremenljivka) {

    $('#visible_'+spremenljivka).load('ajax.php?a=spremenljivka_visible', {spremenljivka: spremenljivka});
}

// vkljuci/izkljuci prikaz textfielda pri spremenljivki
function spremenljivka_textfield (spremenljivka) {

    editor_save(spremenljivka, 2);

    $(getContainer(spremenljivka)).load('ajax.php?a=spremenljivka_textfield', {spremenljivka: spremenljivka});
}

// vkljuci/izkljuci timer spremenljivke
function spremenljivka_timer (spremenljivka, timer) {

    $('#timer_'+spremenljivka).load('ajax.php?a=spremenljivka_timer', {spremenljivka: spremenljivka, timer: timer});
}

// spremeni labelo textfielda pri spremenljivki
function edit_textfield (vrednost, label) {

    $.post('ajax.php?a=edit_textfield', {vrednost: vrednost, label: label});
}

// spremeni ime spremenljivke
function edit_spremenljivka (spremenljivka, naslov, normalmode) {

    info = $('#info_'+spremenljivka).val();

    $.post('ajax.php?a=edit_spremenljivka', {spremenljivka: spremenljivka, naslov: naslov, info: info, anketa: srv_meta_anketa_id, branching: srv_meta_branching, normalmode: normalmode},
        function (response_data) {
            // ce zapremo urejanje (normalmode), se refresha se display, s previewjem namesto editinga
            if (normalmode == 1 || _fullscreen || _edit_fullscreen ) {
        		// ce smo v fullscreen skrijemo fade
       			_edit_fullscreen = false;
            	_fullscreen=0;
            	$('#fullscreen').hide();
            	$('#fade').fadeOut('slow');
                //$(getContainer(spremenljivka)).load('ajax.php?a=normalmode_spremenljivka', {spremenljivka: spremenljivka, branching: srv_meta_branching, anketa: srv_meta_anketa_id});
                $(getContainer(spremenljivka)).html(response_data);

            }
            // kadar editor submitamo ob kaksni drugi akciji (spremembi tipa vprasanja) in ga potem odstranimo - ker se izpise znova
            if (normalmode == 2) {
                editor_remove(spremenljivka);
            }
            // v branchingu refreshamo levo stran (ime spremenljivke)
            if (srv_meta_branching == 1) {
                $('#branching_'+spremenljivka).load('ajax.php?t=branching&a=refresh_spremenljivka_name', {spremenljivka: spremenljivka, branching: srv_meta_branching});
            }
        	// ce je bottom gumb gray ga nardimo orange, da mamo vedno samo en aktiven (orange) gumb
            // po novem se klice iz ajaxa
        }
    );
    return true;
}


// spremeni ime labele, posebej je zato, ker je v editorju
function edit_spremenljivka_label (spremenljivka, naslov) {

    $.post('ajax.php?a=edit_spremenljivka_label', {spremenljivka: spremenljivka, naslov: naslov, branching: srv_meta_branching});
}

// spremeni variablo spremenljivke
function edit_spremenljivka_variable (spremenljivka) {

    var variable = $("#variable_"+spremenljivka).val();

    variable = check_valid_variable(variable);

    $("#variable_"+spremenljivka).val(variable);
    $('#variable_error_'+spremenljivka).load('ajax.php?a=edit_spremenljivka_variable', {spremenljivka: spremenljivka, variable: variable, anketa: srv_meta_anketa_id, branching: srv_meta_branching});

}

// spremeni tip spremenljivke
function edit_spremenljivka_tip (spremenljivka, tip, size, undecided) {
	 // skrijemo div z predogledom vprašanja
	 $("#tip_preview").hide();
    editor_save(spremenljivka, 2);
    $(getContainer(spremenljivka)).load('ajax.php?a=edit_spremenljivka_tip', {spremenljivka: spremenljivka, tip: tip, size: size, undecided: undecided, anketa: srv_meta_anketa_id, branching: srv_meta_branching});
}

function edit_spremenljivka_skala(spremenljivka) {
    var skala = ($("#spremenljivka_skala_"+spremenljivka).val()) ? $("#spremenljivka_skala_"+spremenljivka).val() : 0;
    $.post('ajax.php?a=edit_spremenljivka_skala', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id, branching: srv_meta_branching, skala: skala});
}

// spremeni number nastavitve spremenljivke
function edit_spremenljivka_number (spremenljivka, cela, decimalna, enota) {
    editor_save(spremenljivka, 2);

    $(getContainer(spremenljivka)).load('ajax.php?a=edit_spremenljivka_number', {spremenljivka: spremenljivka, cela: cela, decimalna: decimalna, enota: enota, branching: srv_meta_branching, anketa: srv_meta_anketa_id});
}

// spremeni stevilo kosov pri tipu text
function edit_spremenljivka_textboxes (spremenljivka, size) {
	 // skrijemo div z predogledom vprašanja
	 $("#tip_preview").hide();
    editor_save(spremenljivka, 2);
    $(getContainer(spremenljivka)).load('ajax.php?a=edit_spremenljivka_textboxes', {spremenljivka: spremenljivka, size: size, anketa: srv_meta_anketa_id, branching: srv_meta_branching});
}

// spremeni polzaj besedila pri tipu text
function edit_spremenljivka_text_orientation (spremenljivka, orientation) {
	 // skrijemo div z predogledom vprašanja
	 $("#tip_preview").hide();
    editor_save(spremenljivka, 2);
    $(getContainer(spremenljivka)).load('ajax.php?a=edit_spremenljivka_text_orientation', {spremenljivka: spremenljivka, orientation: orientation, anketa: srv_meta_anketa_id, branching: srv_meta_branching});
}

// spremeni opozorila na preseg vsote
function edit_spremenljivka_vsota_reminder (spremenljivka, reminder) {

    editor_save(spremenljivka, 2);

    $.post('ajax.php?a=edit_spremenljivka_vsota_reminder', {spremenljivka: spremenljivka, reminder: reminder});
}

// spremeni antonuccijev krog nastavitve spremenljivke
function edit_spremenljivka_antonucci (spremenljivka, antonucci) {

    $.post('ajax.php?a=edit_spremenljivka_antonucci', {spremenljivka: spremenljivka, antonucci: antonucci, branching: srv_meta_branching});
}

// spremeni antonuccijev krog nastavitve spremenljivke
function edit_spremenljivka_design (spremenljivka, design) {

    editor_save(spremenljivka, 2);

	$(getContainer(spremenljivka)).load('ajax.php?a=edit_spremenljivka_design', {spremenljivka: spremenljivka, design: design, branching: srv_meta_branching, anketa: srv_meta_anketa_id});
}

// spremeni antonuccijev krog nastavitve spremenljivke
function edit_spremenljivka_ranking_k (spremenljivka, size) {

    $.post('ajax.php?a=edit_spremenljivka_ranking_k', {spremenljivka: spremenljivka, size: size, branching: srv_meta_branching});
}

// spremeni antonuccijev krog nastavitve spremenljivke
function check_length (id, text) {

	var length = text.length;
	if(length > 50)
		document.getElementById('ranking_warning_'+id).style.display = "inline";
	else
		document.getElementById('ranking_warning_'+id).style.display = "none";
}

// spremeni tip socialne podpore
function edit_spremenljivka_podpora (spremenljivka, podpora) {

    $.post('ajax.php?a=edit_spremenljivka_podpora', {spremenljivka: spremenljivka, podpora: podpora, branching: srv_meta_branching});
}

// spremeni parameter spremenljivke
function edit_spremenljivka_param (spremenljivka, paramName, paramValue) {

    editor_save(spremenljivka, 2);

    $(getContainer(spremenljivka)).load('ajax.php?a=edit_spremenljivka_param', {spremenljivka: spremenljivka, paramName: paramName, paramValue: paramValue, branching: srv_meta_branching, anketa: srv_meta_anketa_id});
}

// prikaze urejevalni nacin za grupo
function editmode_grupa (grupa, pages) {
	if (pages == 1) {
		$('#fieldset_page_'+grupa+" legend").load('ajax.php?a=editmode_grupa', {anketa: srv_meta_anketa_id, grupa: grupa, pages: pages});
	} else
    	$('#grupa_'+grupa).load('ajax.php?a=editmode_grupa', {anketa: srv_meta_anketa_id, grupa: grupa});
}

// prikaze navadni nacin za grupo
function normalmode_grupa (grupa) {

    $('#grupa_'+grupa).load('ajax.php?a=normalmode_grupa', {anketa: srv_meta_anketa_id, grupa: grupa});
}

// prikaze urejevalni nacin za spremenljivko
function editmode_spremenljivka (spremenljivka, fullscreen) {
	// najprej damo vse ostale spremenljivke v normal mode ker po novem lahko editiramo samo 1 spremenljivko na enkrat
	$(".spr_editmode").each(function(){
		if (spremenljivka != $(this).attr("id").substr(22))
		normalmode_spremenljivka($(this).attr("id").substr(22))
	})
	// ce je bottom gumb orange ga nardimo gray, da mamo vedno samo en aktiven (orange) gumb

    editor_remove(spremenljivka);

	// tole mamo, ker pri premikanju vprasanja pride tudi do eventa onclick, in
	// da se ne sprozi
	if (_moved == 1) {
		_moved = 0;
		return;
	}

    if (fullscreen >= 1) {
        if (fullscreen == 2)    // ce imamo odprt editing, zbrisemo html, da se
								// IDji ne podvajajo
        {
        	//$(getContainer(spremenljivka)).html('');
        	$(getContainer(spremenljivka)).find(".spremenljivka_tekst_form").remove();
        	$(getContainer(spremenljivka)).find(".spremenljivka_tip_content").remove();
        	$(getContainer(spremenljivka)).find(".save_button").remove();
        	$(getContainer(spremenljivka)).find(".spr_settings").remove();
        }
        _fullscreen = 1;
        $('#fullscreen').html('').fadeIn('slow').draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
        $('#fade').fadeTo('slow', 1);
    }

    $(getContainer(spremenljivka)).load('ajax.php?a=editmode_spremenljivka', {spremenljivka: spremenljivka, branching: srv_meta_branching, anketa: srv_meta_anketa_id, grupa: srv_meta_grupa, fullscreen:fullscreen}, function() {refreshBottomIcons('gray');});

    /*if (srv_meta_branching == 1) {
        $('#branching_'+spremenljivka+' .branchborder').addClass('editing');
    }*/
}
function fullscreenmode_spremenljivka(spremenljivka) {
	// tole mamo, ker pri premikanju vprasanja pride tudi do eventa onclick, in
	// da se ne sprozi
	if (_moved == 1) {
		_moved = 0;
		return;
	}
	_fullscreen = 1;
    $('#fullscreen').html('').fadeIn('slow').draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
    $('#fade').fadeTo('slow', 1);

	// preverimo ali spremenljivka ze v edit mode
	var edit_mode = $(".spremenljivka").find(".spr_editmode").length;
	// spremenljivko damo iz edit mode;
    if (edit_mode == 1) {
    	$('#spremenljivka_'+spremenljivka).load('ajax.php?a=normalmode_spremenljivka', {spremenljivka: spremenljivka, branching: srv_meta_branching, anketa: srv_meta_anketa_id}, function() {
    		// počakamo da se normal mode konča (zato je vgnezdeno)
    		$("#fullscreen").load('ajax.php?a=editmode_spremenljivka',{spremenljivka: spremenljivka, branching: srv_meta_branching, anketa: srv_meta_anketa_id, grupa: srv_meta_grupa, fullscreen:_fullscreen}, function() {refreshBottomIcons('gray');});

    	});
    } else {
        $("#fullscreen").load('ajax.php?a=editmode_spremenljivka',{spremenljivka: spremenljivka, branching: srv_meta_branching, anketa: srv_meta_anketa_id, grupa: srv_meta_grupa, fullscreen:_fullscreen}, function() {refreshBottomIcons('gray');});
        //        $('#fullscreen').load('ajax.php?a=editmode_spremenljivka', {spremenljivka: spremenljivka, branching: srv_meta_branching, anketa: srv_meta_anketa_id, grupa: srv_meta_grupa, fullscreen:fullscreen});
    }

}

// prikaze navadni nacin za spremenljivko
function normalmode_spremenljivka (spremenljivka) {
	refreshBottomIcons('orange');
	editor_save(spremenljivka, 1);
    if (collapsed_content == 1) {
        editor_remove(spremenljivka);

        // tole smo prestavl v edit_spremenljivka, ker drugace je kdaj refreshal
		// preview predn je shranil v bazo in tekst ni bil refreshan
        // $('#spremenljivka_'+spremenljivka).load('ajax.php?a=normalmode_spremenljivka',
		// {spremenljivka: spremenljivka, branching: srv_meta_branching, anketa:
		// srv_meta_anketa_id});
    }

    /*if (srv_meta_branching == 1) {
        $('#branching_'+spremenljivka+' .branchborder').removeClass('editing');
    }*/


}

/************************************************
     CKEDITOR START
************************************************/

// prikaze editor za ne-spremenljivko (za karkoli druzga pac)
function create_editor (id, focus) {

	CKEDITOR.replace(id);

	//namestitev editorja za tekst pri obveščanju
    if(gup('a') == 'alert' && focus == false){
        CKEDITOR.config.width = 560;
    }else if(gup('a') == 'jezik'){
        //editor pri urejanju spremenljivk
        CKEDITOR.config.width = 600;
        CKEDITOR.config.height = 140;
        CKEDITOR.config.enterMode = CKEDITOR.ENTER_BR;
    }else {
        var def = $('#'+id).attr("default");
        if( def == 1) {
			CKEDITOR.instances[id].on('focus', function () {
				this.execCommand('selectAll');
			});
		}

		editor_init = true;
	}
}


/*
	Funkcija za hitro nalaganje slike pri odgovoru
 */
function create_editor_hitro_nalaganje_slike(id) {
	var vrednost = $('[name="vrednost"]').val();


	// ckeditor dialogDefinition mora biti prej nastavljena preden se inicializira ckeditor, ker drugače obvelajo nastavitve iz config.js
	CKEDITOR.on( 'dialogDefinition', function( ev ) {
		// Take the dialog name and its definition from the event data.
		var dialogName = ev.data.name;
		var dialogDefinition = ev.data.definition;

		// Ko se odpre image dialog
		// V kolikor se slika nalaga pri radio gumbu in istočasno želi uporabnik urejanja odgovora (svnčnik na desni), mu ne dovoli, ker
		// ostanejo nastavitve od CKEDITOR-ja dialogDefinition, zato preverimo, če obstaja instanca za hitro nalaganje slik in šele nato
		// dovolimo vse nadaljnje nastavitve
		if ( dialogName == 'image' && typeof  CKEDITOR.instances[id] != 'undefined') {
			// funkcija on dialog hide izvede, da se vrednost skritega ckeditorja value shrani
			dialogDefinition.onHide = function() {
				setTimeout(function(){
					vrednost_insert_image_save(vrednost)
				}, 50);
			};

			// pobere celotno vsebino textarea, ki je sedaj naš CKEDITOR in če ima samo sliko
			var poljeBesedila = CKEDITOR.instances[id].getData();
			if(poljeBesedila.length > 2){
				var src = $(poljeBesedila).attr('src');

				var urlField = dialogDefinition.getContents( 'info' ).get('txtUrl');
				urlField['default']= src;
			}

			// Prevzeta vrednost slike je 150px v kolikor se nalaga nova slika
			var widthField = dialogDefinition.getContents( 'info' ).get('txtWidth');
			widthField['default'] = '150';
		}
	});

    CKEDITOR.replace(id,  {toolbar: 'HotSpot'});
    CKEDITOR.config.removePlugins = 'elementspath';
    CKEDITOR.config.width = 600;
    CKEDITOR.config.height = 200;

	//pobrišemo text v kolikor v besedilu ni regex '<img '
	clear_editor_text(id, '<img ');

    CKEDITOR.instances[id].on('focus', function () {
		this.execCommand('selectAll');
        this.execCommand('image');
    });

	editor_init = true;
}

// Pobrišemo CKEDITOR text, v kolikor ne najde željene sintakse v besedilu
function clear_editor_text(id, search){
	search = search || 0;

	// v kolikor ni texta za iskanje potem vedno pobriše vse
	if(search === 0)
		return CKEDITOR.instances[id].setData('');

	var text = CKEDITOR.instances[id].getData();
	var re = new RegExp(search, "g");
	var matches = text.match(re);

	if(matches === null)
		return CKEDITOR.instances[id].setData('');

}

function create_editor_hotspot (id, focus) {
	CKEDITOR.replace( id, {toolbar: 'HotSpot'});	//prikazi editor s HotSpot configuration
	CKEDITOR.config.removePlugins = 'elementspath';	//odstrani spodnji tag, kjer po default-u so oznake html (body, p, ipd.)
	CKEDITOR.config.width = 700;
    CKEDITOR.config.height = 500;
	CKEDITOR.instances[id].on('focus', function () {
		this.execCommand('selectAll');
	});
	editor_init = true;
}

function create_editor_notification(id) {

	CKEDITOR.replace( id, {toolbar: 'Notification'});	// prikazi editor s Notification configuration

	CKEDITOR.config.removePlugins = 'elementspath';	//odstrani spodnji tag, kjer po default-u so oznake html (body, p, ipd.)

	CKEDITOR.instances[id].on('focus', function () {
		this.execCommand('selectAll');
	});

    editor_init = true;
}

// odstrani editor za ne-spremenljivka (treba preden se odstrani html)
function remove_editor (id) {
	//odstranimo CKEDITOR v kolikor je inicializiran -> če preverjanja potem javi error in ostala javascript datoteka ne deluje
	if (CKEDITOR.instances[id]){
        var u = CKEDITOR.instances[id];
        //if(u.mode == 'source') //v kolikor je urejevalnik v načinu source moramo uporabiti filter za tekst, ki ga je vnesel
        //    u.setMode( 'wysiwyg' );
        u.destroy();

	}
	//spremenljivka za urejevalnik je ponovno izklopljena
	editor_init = false;
}

// prikaze editor za spremenljivko (definiran mora biti textarea za idjem naslov_$spremenljivka
function editor_display (spremenljivka) {
	//if (editor_init != true) {

		CKEDITOR.replace( 'naslov_'+spremenljivka );

        //v kolikor je default vrednost potem  naredimo selectAll
        var def = $('#naslov_'+spremenljivka).attr("default");
		if( def == 1) {
			CKEDITOR.instances['naslov_' + spremenljivka].on('focus', function () {
				this.execCommand('selectAll');
			});
		}
	//	editor_init = true;
	//}
}

function editor_display_hotspot (vre_id) {
	//if (editor_init != true) {

		CKEDITOR.replace( 'hotspot_image_'+vre_id, {toolbar: 'HotSpot', width: 300, removePlugins: 'elementspath'}); //izberi config toolbar HotSpot, sirina naj bo 300px, odstrani spodnji tag, kjer po default-u so oznake html (body, p, ipd.)

        //v kolikor je default vrednost potem  naredimo selectAll
        var def = $('#hotspot_image_'+vre_id).attr("default");
		//if( def == 1) {
			CKEDITOR.instances['hotspot_image_' + vre_id].on('focus', function () {
				this.execCommand('selectAll');
			});
		//}
	//	editor_init = true;
	//}
}

// odstrani editor (treba preden se odstrani html)
function editor_remove (spremenljivka) {
    //odstranimo CKEDITOR
    CKEDITOR.instances[id].destroy();

    //spremenljivka za urejevalnik je ponovno izklopljena
    editor_init = false;
}

// odstrani vse editorje
function alleditors_remove () {
    if (editor_init == true) {
        CKEDITOR.instances.editor1.destroy();
		editor_init = false;
	}
}

// submit editorja  --tukaj dobimo vssebino, ki smo jo vnesli v editor
function editor_save (spremenljivka, normalmode) {
	// vsilimo blur, da shranimo vrednosti na zadnjem elementu
	$("input").prev().focus();

    var editor = CKEDITOR.get('naslov_'+spremenljivka);

    try {
        content = editor.getContent();
        editor.isNotDirty = true;

    // ce editor se ni naloadan in imamo textarea
    } catch (e) {

        content = $('#naslov_'+spremenljivka).val();
    }


    if (spremenljivka > 0)
        var r = edit_spremenljivka(spremenljivka, content, normalmode);
    else
        var r = edit_introconcl(spremenljivka, content, $('#opomba_'+spremenljivka).val());
    return r;
}

function get_full_editor(id){
	CKEDITOR.instances[id].destroy();
	CKEDITOR.replace( id, {toolbar: 'Full'});
}


/****************************  CKEDITOR END ***************************/


// skopira spremenljivko v nas clipboard (uporabimo cookie)
function copy_spremenljivka (spremenljivka, cut) {

    $('#clipboard').load('ajax.php?a=copy_spremenljivka', {spremenljivka: spremenljivka, cut: cut, anketa: srv_meta_anketa_id},
        function () {
    		$('.hidden_plus').show();
            $('.nova_spr, .hidden_plus').effect('pulsate', {times: 3}, 800);
        }
    );
}

// odstrani iz clipboarda (kukija)
function copy_remove () {
	$('.hidden_plus').hide();
    $.post('ajax.php?a=copy_remove', {anketa: srv_meta_anketa_id},
        function (data) {
            $('#clipboard').append(data);
        }
    );
}

// izbri�e vse vnose respondentov (use with care :) )
function delete_all (text) {
    if (confirm (text)) {
        $.redirect('ajax.php?a=delete_all', {anketa: srv_meta_anketa_id});
    }
}



/*Telefon*/

// shrani osnovne nastavitve za klice
function telefon_settings_save (id,elm) {

    //$.post('ajax.php?t=telefon&a=settings_save', {id:id,variable:variable,value:value});
    $.post('ajax.php?t=telefon&a=settings_save', {id:id,variable:elm.name,value:elm.value},function (data){if(data){elm.value=data}});
}

/*DATA*/
function filter_editing () {

    $('#div_float_editing').html('');
    $('#div_float_editing').fadeIn("slow");

    $('#div_float_editing').load('ajax.php?a=filter_editing', {anketa: srv_meta_anketa_id}).draggable({delay:100,  ghosting:	true , cancel: 'input, textarea, select, .buttonwrapper'});
}

function filter_remove () {

    $.redirect('ajax.php?a=filter_remove', {anketa: srv_meta_anketa_id});

}

function filter_close () {

    $.redirect('ajax.php?a=filter_close', {anketa: srv_meta_anketa_id});

}

// ----------------------- nastavitve za sortables -----------------------

// nastavi sortable grupam
function grupa_sortable (preventMove) {
    $('#grupe').sortable({items: 'div.sortable', axis: 'y', opacity: '0.7', scroll: false,
        stop: function () {
	        if (preventMove == true) {
	        	$(this).sortable('cancel');
	        	$.post('ajax.php?a=outputLanguageNote', {anketa: srv_meta_anketa_id, note: 'srv_grupa_move_alert_have_branching'}, function(response) { genericAlertPopup('alert_parameter_response',response);   return false;});
	        } else
	        	$.post('ajax.php?a=vrstnired_grupa', {serialize: $('#grupe').sortable('serialize')});
        }
    });
}

// nastavi droppable grupam
function grupa_droppable (classname) {

    //$('#'+classname).droppable({accept: 'div.spremenljivka', hoverClass: 'grupahover', tolerance: 'pointer',
    //    drop: function (e, ui) {
    //
    //        var grupa = classname.substring(6);
    //        var spremenljivka = $(ui.draggable).attr('id').substr(14);
    //
    //        $.post('ajax.php?a=premakni_vprasanje', {grupa: grupa, spremenljivka: spremenljivka},
    //            function (data) {
    //                window.location = 'index.php?anketa='+srv_meta_anketa_id+'&grupa='+grupa;
    //            }
    //        );
    //    }
    //});

}

// nastavi sortable spremenljivkam
function spremenljivka_sortable (preventMove) {
    $('#vprasanja').sortable({items: 'div.spremenljivka', opacity: '0.7', scroll: false, handle: '.spremenljivka_settings', forcePlaceholderSize: 'ture', revert: 'true', dropOnEmpty: 'true',
    	/* start: function (e, ui) { $('#'+$(ui.item).attr('id')+' .nova_spr').animate({opacity: 0}, {duration: 1}); }, */
    	stop: function (e, ui) {
            _moved = 1;        // premaknili smo, onemogocimo onclick (za edit)

            // $('#'+$(ui.item).attr('id')+' .nova_spr').animate({opacity: 1},
			// {duration: 1});¸
            if (preventMove == true) {
            	$(this).sortable('cancel');
            	$.post('ajax.php?a=outputLanguageNote', {anketa: srv_meta_anketa_id, note: 'srv_spremenljivka_move_alert_have_branching'}, function(response) { genericAlertPopup('alert_parameter_response',response);   return false;});
            } else {
            	var moved = ui.item.attr('id');
            	var topage = $("#"+moved).parent().attr('id');

            	$.post('ajax.php?a=vrstnired_vprasanje', {serialize: $('#vprasanja').sortable('serialize'), anketa: srv_meta_anketa_id, moved:moved, grupa: srv_meta_grupa, topage:topage});
//            	$("#vprasanja").load('ajax.php?a=vrstnired_vprasanje', {serialize: $('#vprasanja').sortable('serialize'), anketa: srv_meta_anketa_id, moved:moved, grupa: srv_meta_grupa, topage:topage});
            }
        }
    });

}

// nastavi sortable spremenljivkam v pogledu FORMA (potreben je reload vseh vprasanj zaradi dodatnih praznih divov)
function spremenljivka_sortable_forma () {

    $('#vprasanja').sortable({items: 'div.spremenljivka', opacity: '0.7', scroll: false,
        /*start: function (e, ui) {
            $('#'+$(ui.item).attr('id')+' .nova_spr').animate({opacity: 0}, {duration: 1});
        },*/
        stop: function (e, ui) {
            _moved = 1;        // premaknili smo, onemogocimo onclick (za edit)
            // $('#'+$(ui.item).attr('id')+' .nova_spr').animate({opacity: 1},
			// {duration: 1});
            $('#vprasanja').load('ajax.php?a=vrstnired_vprasanje_forma', {serialize: $('#vprasanja').sortable('serialize'), anketa: srv_meta_anketa_id});
        }
    });

}

// nastavi sortable vrednostim
function vrednost_sortable (classname) {

    $('div.'+classname).sortable({items: 'div.sortable', opacity: '0.7', scroll: false, handler: 'img.move', axis: 'y',
    	start: function () {
    		$('#vprasanja').sortable('disable');
    	},
    	stop: function () {
            $.post('ajax.php?a=vrstnired_vrednost', {serialize: $('div.'+classname).sortable('serialize')});
            $('#vprasanja').sortable('enable');
        }
    });
}


// ----------------------- funkcije za urejanje vnosov -----------------------

function vnos_redirect (url) {
    window.location = url;
}

function edit_data_vrednost_ch(spr_id, vre_id, usr_id, value) {

    $.post('ajax.php?a=edit_data_vrednost_ch', {spr_id: spr_id, vre_id: vre_id, usr_id:usr_id, value:value, anketa:srv_meta_anketa_id});
}

function edit_data_vrednost(spr_id, vre_id, usr_id) {

    $.post('ajax.php?a=edit_data_vrednost', {spr_id: spr_id, vre_id: vre_id, usr_id:usr_id, anketa:srv_meta_anketa_id});
}

function edit_data_grid(spr_id, vre_id, usr_id, grd_id) {

    $.post('ajax.php?a=edit_data_grid', {spr_id: spr_id, vre_id: vre_id, usr_id:usr_id, grd_id:grd_id, anketa:srv_meta_anketa_id});
}

function edit_data_text(spr_id, vre_id, usr_id, value, textfield) {

    $.post('ajax.php?a=edit_data_text', {spr_id: spr_id, vre_id: vre_id, usr_id:usr_id, value:value, textfield:textfield, anketa:srv_meta_anketa_id});
}

function edit_data_delete (usr_id, confirmtext) {
    if (confirm(confirmtext)) {
    	$.post('ajax.php?a=edit_data_delete', {usr_id:usr_id, anketa:srv_meta_anketa_id});
    	$("#usr_row_"+usr_id).hide();
    }
}

function respondent_data_delete (usr_id, confirmtext) {
    if (confirm(confirmtext)) {
    	$("#respondent_id_"+usr_id).hide();
        $.post('ajax.php?a=edit_data_delete', {usr_id:usr_id, anketa:srv_meta_anketa_id});
    }
}

function highlight_spremenljivka (spr_id) {

	$('th[spr_id='+spr_id+']').each(function(idx, elm) {

		th = $(elm);
		var td_pos = th.parent().children().index(th);

		$('#dataTable tbody td:nth-child('+(td_pos+1)+')').addClass('cellBlue');

	});
}

function highlight_user (usr_id) {

	for (i in usr_id) {
		//console.log(usr_id[i]);
		$('td.data_uid:contains(\''+usr_id[i]+'\')').parent().find('td').not('.enkaIcon').not('.cellGreen').addClass('cellBlue');
	}
}

/**
* v tabeli s podatki prikaže labele
*/
function data_show_labels () {

	// srv_meta_anketa_id se ni postavljen
	srv_meta_anketa_id = srv_meta_anketa_id || $("#srv_meta_anketa_id").val();

	// povemo koliko stolpcev z ikonicami imamo (se nastavi v html)
	var tableIconColspan = parseInt( $("#tableIconColspan").val() ) || 0;

	var tableHeadChildren = $('#dataTable tr:nth-child(3)').children();	// th-ji vrstice header tabele

	var sprList = [];

	// gremo cez vse stolpce ki imajo inline_edit=1 in si shranimo spr_id
	tableHeadChildren.filter("th").each( function (ii, column) {
		if ( ! isNaN( $(column).attr('spr_id') ) )
			if ( $.inArray($(column).attr('spr_id'), sprList) == -1 )
				sprList.push( $(column).attr('spr_id') );
	});

	// poberemo html kodo forme
	$.post('ajax.php?a=get_variable_labels', {anketa: srv_meta_anketa_id, spr: sprList}, function (response) {

		// gremo cez vse stolpce
		for (var i=0, len=response.length; i<len; i++) {

			var columns = $('th[spr_id='+response[i].spr+']');

			columns.each( function(iii, column) {

				var spr_id = $(column).attr('spr_id');

				// na kateri celici smo
				var tableIndex = tableHeadChildren.index(column);
				// nardimo korekcijo zaradi ikonic
				tableIndex = tableIndex + (tableIconColspan > 0 ? tableIconColspan : 1);	// +1 ker je en stolpec uid (skrit)
				// če mamo ikonce mormo prištet še 1 ker mamo prvi stolpec colspanan (headerji z ikoncami nimajo atrubuta inline_edit)

				// gremo cez vse vrstice
				$('#dataTable tr').each( function (ii, tr) {

					var element = $(tr).find(':nth-child('+(tableIndex)+')');

					if ($(element).is('td')) {

						var usr_id = $(tr).find('td.data_uid').html();
						var val = element.html();

						if ( $.trim(element.html()) in response[i]['values'] ) {

							element.append( ' <span class="gray">(' + response[i]['values'][$.trim(element.html())] + ')</span>' );

						}

					}

				});

			});

		}

	}, 'json');

}
// ----------------------- ostale funkcije -----------------------

// upload skina
function survey_upload () {
	val = document.upload.fajl.value;
	if (val.length > 0) document.upload.submit();
}

function survey_remove_logo (profile) {
	$.post('ajax.php?a=remove_logo', {profile: profile, anketa: srv_meta_anketa_id}, function () {
		window.location.reload();
	});
	return false;
}

// vrne GET parameter
function gup( name )
{
  name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
  var regexS = "[\\?&]"+name+"=([^&#]*)";
  var regex = new RegExp( regexS );
  var results = regex.exec( window.location.href );
  if( results == null )
    return "";
  else
    return results[1];
}

// v odvisnosti od mode, odpere ali zapre vse psremenljivke za editiranje
// mode = 1 -> edit mode
// mode = 0 -> normal mode
function expandAll (mode) {
	$('div[id^=spremenljivka_content_]').each(function(index)
	{

		var id = $(this).attr('id').split('spremenljivka_content_');
		var activeString = $(this).attr('class').toString();
		var active = ( activeString !== 'spremenljivka_content')
		if (mode == 1)
		{ //v edit mode damo samo tiste kateri so zaprti
			if (!active)
			{
				if ( id[1] >= 0 )
					editmode_spremenljivka(id[1]);
				else {
					editmode_introconcl(id[1])
				}
			}
		}
		else
		{ //v normal mode damo samo tiste kateri so v edit
			if (active)
			{ // TODO če je uvod ali zaključek je treba drugače
				if ( id[1] >= 0 )
					normalmode_spremenljivka(id[1]);
				else {
					if ($(this).attr('class') == 'spremenljivka_content active') // dodatni
																					// check
						normalmode_introconcl(id[1])
				}

			}
		}
	});
}
// enablamo in disablamo form elemente
function toggleStatusAlertMore(element) {
    if ( $('#alert_more').is(':checked') )
    {
    	$('#'+element).removeAttr('disabled');
        $('#'+element).removeClass("alert_textarea");
    } else {
    	$('#'+element).addClass("alert_textarea");
        $('#'+element).attr('disabled', true);
    }
}
function toggleStatusAlertOtherCheckbox(element) {
    // alert_expire_other
	if ( $('#alert_'+element).is(':checked') )
    {
		$('#alert_holder_'+element+'_emails').show();
    } else {
		$('#alert_holder_'+element+'_emails').hide();    }
}


function clear_analizaFilters()
{
$('input[id^=analiza_mv_checkbox_]').each(function()
{	if ( $(this).is(':checked') )
		$(this).trigger('click');
});
}

/* komentarji */

// funkcija, ki pohendla komentarje ankete in vprasanj (preusmeri v forum in po potrebi (pri vprasanju) kreira novo temo, ce se ni)
function comment_manage(type, spremenljivka) {
    $.redirect('ajax.php?t=branching&a=comment_manage', {anketa: srv_meta_anketa_id, type: type, spremenljivka: spremenljivka});
}

var siteurl = '';
if ( typeof srv_site_url !== 'undefined' ) {	// komentarji v izpolnjevanju ankete
	siteurl = srv_site_url + '/admin/survey/';
}

// doda komentar in osvezi oblacek
// type=0 : anketa, type=1 : vprasanje
function add_comment (spremenljivka, type, view, vsebina) {
    $('div#survey_comment_'+spremenljivka+'_'+view).load(siteurl+'ajax.php?t=branching&a=comment_manage', {type: type, view: view, spremenljivka: spremenljivka, vsebina: vsebina, anketa: srv_meta_anketa_id, refresh: '1'},
    function () {
        if (view == 0) {
            $('#surveycomment_'+spremenljivka+'_0').qtip("hide");               // pri opciji Dodaj komentar, skrijemo oblacek po submitu
            $('#comment_add_'+spremenljivka).css('visibility', 'visible');      // pokazemo opcijo Poglej komentarje
        } else if (view == 4 || view == 5) {
			window.location.reload();
        }
    });
}

// nastavi podanemu linku (a.surveycomment) oblacek za komentarje
function load_comment (__this, show) {
    if (show == 1) {    // opcije za takrat, ko je po defaultu ze na zacetku prikazan - za dodat komentar na anketo
        _when = false;
        _ready = true;
        var corners = ['leftMiddle', 'leftMiddle'];
    	var opposites = ['rightMiddle', 'rightMiddle'];
    } else if (show == 2) {	// opcija za takrat, ko se aktivira preko oblacka, ko se prikaze takoj
        _when = 'click';
        _ready = true;
        var corners = ['topRight', 'topRight'];
    	var opposites = ['bottomLeft', 'bottomLeft'];
    } else {		// default za normalne komentarje, da se odpre na klik
        _when = 'click';
        _ready = false;
        var corners = ['topLeft', 'topRight'];
    	var opposites = ['bottomRight', 'bottomLeft'];
    }

    var width = $(document).width();
    // nastavitve za help

    // preverimo ali prikažemo tip na levo stran
    var position = $(__this).offset(); // position = { left: 42, top: 567 }

    var left = width - position.left;

    var i = (left >= 350) ? 0 : 1;

    if ($(__this).data("qtip")) {
        $(__this).qtip("destroy");
        $('div.qtip').html('');
    }

	// Posebej naslov ce smo v urejanju
	if($(__this).attr('view') == '1' && !show && $('#comment_qtip_title').length > 0 && $(__this).attr('subtype') == undefined){
		var naslov = $('#comment_qtip_title').val();
	}
	// komentar na vprasanje
	else if($(__this).attr('subtype') == 'q_admin_add'){
		var naslov = lang['srv_testiranje_komentar_q_title'];
	}
	// komentar na if
	else if($(__this).attr('subtype') == 'if_admin_add'){
		var naslov = lang['srv_testiranje_komentar_if_title'];
	}
	// komentarji na if
	else if($(__this).attr('subtype') == 'if_admin_all'){
		var naslov = lang['srv_testiranje_komentar_if_all_title'];
	}
	// komentar na blok
	else if($(__this).attr('subtype') == 'blok_admin_add'){
		var naslov = lang['srv_testiranje_komentar_blok_title'];
	}
	// komentarji na blok
	else if($(__this).attr('subtype') == 'blok_admin_all'){
		var naslov = lang['srv_testiranje_komentar_blok_all_title'];
	}
	// komentarji na vprasanja
	else if($(__this).attr('subtype') == 'q_admin_all'){
		var naslov = lang['srv_testiranje_komentar_q_all_title'];
	}
	// komentarji respondentov na vprasanja
	else if($(__this).attr('subtype') == 'q_resp_all'){
		var naslov = lang['srv_testiranje_komentar_q_resp_all_title'];
	}
	else{
		var naslov = lang['srv_testiranje_komentarji_anketa_title2'];
	}

    $(__this).qtip({
        content: {text: '<div id="survey_comment_'+$(__this).attr('spremenljivka')+'_'+$(__this).attr('view')+'"></div>', title: {text: naslov, button: '&#x2715;'}},
        fixed: false, show: {when: _when, ready: _ready, solo: true},hide: {when: 'click'},
        style: {name: 'light', border: {width: 3, radius: 8}, width: 350, tip: {corner: corners[i]}},
        position: {corner: {tooltip: corners[i], target: opposites[i] }, adjust: {screen : true}},
        api: {
            beforeShow: function () {
                // tuki se poklice zato, ker se drugace content: {url: ....} ne refresha, ce zapres in spet odpres oblacek
                _comment = 1;
                $('div#survey_comment_'+$(__this).attr('spremenljivka')+'_'+$(__this).attr('view')).load(siteurl+'ajax.php?t=branching&a=comment_manage', {anketa: srv_meta_anketa_id, type: $(__this).attr('type'), view: $(__this).attr('view'), spremenljivka: $(__this).attr('spremenljivka'), vsebina: '', anketa: srv_meta_anketa_id, refresh: '1'});
            },
            onShow: function () {
            	$('div.qtip').draggable();
            }
        }
    });

}
var _comment = 0;

/*  help  */

function load_help () { // ta funkcija je še mal slaba.
                        // prvi oblacek se ob vsakem ajax klicu overloadajo in se pol prikaze veckrat
                        // drugi oblacek ne dela v IE
	var corners = ['topLeft', 'topRight'];
	var opposites = ['bottomRight', 'bottomLeft'];

    var width = $(document).width();
    // nastavitve za help
    $('a.help').each(function() {

    	if ($(this).attr('qtip') != 'init') {

            var help_text = $(this).attr('title_txt');
            
	        // preverimo ali prikažemo tip na levo stran
    		var position = $(this).offset(); // position = { left: 42, top: 567 }
	        var left = width - position.left;
	        var i = (left >= 350) ? 0 : 1;

	        $(this).qtip({
	            content: {url: 'ajax.php?t=help&a=display_help&what='+$(this).attr('id')+'&lang='+$(this).attr('lang'), title: {text: help_text, button: '&#x2715;'}},
	            fixed: true, show: {when: 'click', solo: true},hide: {when: 'click'},
	            style: {name: 'light', border: {}, width: 300},
	            position: {corner: {tooltip: corners[i], target: opposites[i] }, adjust: {screen : true}}
	        }).attr('qtip', 'init');
		}
    });

    var help_text = "";

    // editiranje helpa za admine
    $('a.edithelp').click(function() {
    	if ($(this).attr('qtip') != 'init') {

    		help_text = $(this).attr('title_txt');
	        // preverimo ali prikažemo tip na levo stran
    		var position = $(this).offset(); // position = { left: 42, top: 567 }
	        var left = width - position.left -250;
	        var i = (left >= 0) ? 0 : 1;
			var el_id = $(this).attr('id');
	        var id = el_id.split('help_');
	        var help_element_id = id[1];

	        // Destroy currrent tooltip if present
	        if($(this).data("qtip"))
                $(this).qtip("destroy");
                
    		$(this).qtip({
	            content: {
	                url: 'ajax.php?t=help&a=display_edit_help&what='+el_id+'&lang='+$(this).attr('lang'),
	                title: {text: help_text, button: '&#x2715;'}
	             },
	            fixed: true,
	            show: {
	                when: false, // Don't specify a show event
	                ready: true // Show the tooltip when ready
	             },
	            hide: {when: 'click'},
	            style: {name: 'light', border: {width: 3, radius: 8}, width: 300, tip: {corner: corners[i]}},
	            position: {corner: {tooltip: corners[i], target: opposites[i] }, adjust: {screen : true}}
	        }).attr('qtip', 'init');

		}
    });
}

// help
function save_help (what, lang) {

    var help = $('#edithelp_'+what).val();

    $.post('ajax.php?t=help&a=save_help&lang='+lang, {what: what, help: help}, function() {
        $('#help_'+what).qtip("hide");
        try { $('#help_'+what).attr('qtip', '').qtip('destroy'); } catch (e) {/*alert("Napaka"+e)*/};
    });
}
function saveGlobalSetting(what)
{
	var state = $("input[name="+what+"]:checked").val();
	$.post('ajax.php?a=save_global', {anketa: srv_meta_anketa_id, what: what, state: state});
}

function saveReportSetting(uid, what)
{
	var state = $("input[name="+what+"]:checked").val();
	$.post('ajax.php?a=save_reportSetting', {anketa: srv_meta_anketa_id, uid: uid, what: what, state: state});
}

function saveUserSetting(uid, what)
{
	var state = $("input[name="+what+"]:checked").val();
	$.post('ajax.php?a=save_userSetting', {uid: uid, what: what, state: state});
}

function statisticChangeDate(what) {
	statisticRefreshAllBoxes(what);
}
function statisticRefreshAllBoxes(what){
	var isInterval = false;
	var stat_interval = '';

	if (what == 'interval') {
		isInterval = true;
		stat_interval = $("#stat_interval").val();
	}

	var type = $("#type").val();
	var period = $("#period").val();
	var hideNullValues_dates = $("#hideNullValues_dates").is(':checked');
	var hideNullValues_status = $("#hideNullValues_status").is(':checked');
	var userStatusBase = $("#userStatusBase").val();
	var filter_email_status = $("#filter_email_status").val();
	var inviation_dropdown = false;
	if (what == 'invitation' && filter_email_status == 1) {
		inviation_dropdown = true;
	}
	// osvežimo invitation filter
	$("#dashboardEmailInvitationFilter").load('ajax.php?a=statisticReloadInvitationFilter', {anketa:srv_meta_anketa_id, filter_email_status:filter_email_status});

	// v vsakem boxu refresamo podatke
	// osnovni info box
	$("#div_statistic_info").load('ajax.php?a=statisticInfoRefresh', {anketa:srv_meta_anketa_id, type: type, period:period, hideNullValues_dates:hideNullValues_dates, hideNullValues_status: hideNullValues_status, isInterval: isInterval, stat_interval:stat_interval,filter_email_status:filter_email_status});
	// answer_state
	$("#div_statistic_answer_state").load('ajax.php?a=statisticAnswerStateRefresh', {anketa:srv_meta_anketa_id, type: type, period:period, hideNullValues_dates:hideNullValues_dates, hideNullValues_status: hideNullValues_status, isInterval: isInterval, stat_interval:stat_interval,userStatusBase:userStatusBase,filter_email_status:filter_email_status, inviation_dropdown:inviation_dropdown});
	// box za pregled statusov
	$("#div_statistic_status").load('ajax.php?a=statisticStatusRefresh', {anketa:srv_meta_anketa_id, type: type, period:period, hideNullValues_dates:hideNullValues_dates, hideNullValues_status: hideNullValues_status, isInterval: isInterval, stat_interval:stat_interval,filter_email_status:filter_email_status});
	// box za pogled klikov po straneh
	$("#div_statistic_pages_state").load('ajax.php?a=statisticPageStateRefresh', {anketa:srv_meta_anketa_id, type: type, period:period, hideNullValues_dates:hideNullValues_dates, hideNullValues_status: hideNullValues_status, isInterval: isInterval, stat_interval:stat_interval,filter_email_status:filter_email_status});
	// box za datumski pregled klikov
	$("#div_statistic_visit_data").load('ajax.php?a=statisticDateRefresh', {anketa:srv_meta_anketa_id, type: type, period:period, hideNullValues_dates:hideNullValues_dates, hideNullValues_status: hideNullValues_status, isInterval: isInterval, stat_interval:stat_interval,filter_email_status:filter_email_status});
	// box z referali
	$("#div_statistic_referals").load('ajax.php?a=statisticReferalRefresh', {anketa:srv_meta_anketa_id, type: type, period:period, hideNullValues_dates:hideNullValues_dates, hideNullValues_status: hideNullValues_status, isInterval: isInterval, stat_interval:stat_interval,filter_email_status:filter_email_status});
}

function statisticFilterDateRefresh() {
	var type = $("#type").val();
	var period = $("#period").val();
	var hideNullValues_dates = $("#hideNullValues_dates").is(':checked');
	var hideNullValues_status = $("#hideNullValues_status").is(':checked');
	var filter_email_status = $("#filter_email_status").val();
	var timelineDropDownType = $("#timelineDropDownType").is(':checked');

	$("#div_statistic_visit_data").load('ajax.php?a=statisticDateRefresh', {anketa:srv_meta_anketa_id, type: type, period:period, hideNullValues_dates:hideNullValues_dates, hideNullValues_status: hideNullValues_status, timelineDropDownType:timelineDropDownType, filter_email_status:filter_email_status});
}


function statisticDropdownChange() {
	var type = $("#type").val();
	var period = $("#period").val();
	var hideNullValues_dates = $("#hideNullValues_dates").is(':checked');
	var hideNullValues_status = $("#hideNullValues_status").is(':checked');
	var timelineDropDownType = $("#timelineDropDownType").is(':checked');
	var filter_email_status = $("#filter_email_status").val();

	$("#span_timelineDropDownType").load('ajax.php?a=statisticTimelineDropdownRefresh', {anketa:srv_meta_anketa_id, type: type, period:period, hideNullValues_dates:hideNullValues_dates, hideNullValues_status: hideNullValues_status, timelineDropDownType:timelineDropDownType}, function(){
		var type = $("#type").val();
		var period = $("#period").val();
		var hideNullValues_dates = $("#hideNullValues_dates").is(':checked');
		var hideNullValues_status = $("#hideNullValues_status").is(':checked');
		var timelineDropDownType = $("#timelineDropDownType").is(':checked');

		$("#div_statistic_visit_data").load('ajax.php?a=statisticDateRefresh', {anketa:srv_meta_anketa_id, type: type, period:period, hideNullValues_dates:hideNullValues_dates, hideNullValues_status: hideNullValues_status, timelineDropDownType:timelineDropDownType,filter_email_status:filter_email_status});
	});
}

function statisticStatusRefresh() {
	var type = $("#type").val();
	var period = $("#period").val();
	var hideNullValues_dates = $("#hideNullValues_dates").is(':checked');
	var hideNullValues_status = $("#hideNullValues_status").is(':checked');
	var filter_email_status = $("#filter_email_status").val();

	$("#div_statistic_status").load('ajax.php?a=statisticStatusRefresh', {anketa:srv_meta_anketa_id, type: type, period:period, hideNullValues_dates:hideNullValues_dates, hideNullValues_status: hideNullValues_status, filter_email_status:filter_email_status});
}

function changeUserStatusBase() {
	var type = $("#type").val();
	var period = $("#period").val();
	var hideNullValues_dates = $("#hideNullValues_dates").is(':checked');
	var hideNullValues_status = $("#hideNullValues_status").is(':checked');
	var userStatusBase = $("#userStatusBase").val();
	var filter_email_status = $("#filter_email_status").val();

	$("#div_statistic_answer_state").load('ajax.php?a=statisticAnswerStateRefresh', {anketa:srv_meta_anketa_id, type: type, period:period, hideNullValues_dates:hideNullValues_dates, hideNullValues_status: hideNullValues_status,userStatusBase:userStatusBase,filter_email_status:filter_email_status});
}

function changePageUserStatusBase() {
	var type = $("#type").val();
	var period = $("#period").val();
	var hideNullValues_dates = $("#hideNullValues_dates").is(':checked');
	var hideNullValues_status = $("#hideNullValues_status").is(':checked');
	var pageUserStatusBase = $("#pageUserStatusBase").val();
	var filter_email_status = $("#filter_email_status").val();

	$("#div_statistic_pages_state").load('ajax.php?a=statisticPageStateRefresh', {anketa:srv_meta_anketa_id, type: type, period:period, hideNullValues_dates:hideNullValues_dates, hideNullValues_status: hideNullValues_status,pageUserStatusBase:pageUserStatusBase,filter_email_status:filter_email_status});
}

//na novo load parametrov za izvoz v pdf
function printStatusPDF() {

	var type ='';
	var userStatusBase = '';
	var period = '';

	//napolnimo parametre
	if ($("#userStatusBase").length) {
		userStatusBase = '&userStatusBase='+$("#userStatusBase").val();
	}
	if ($("#type").length) {
		type = '&type='+$("#type").val();
	}
	if ($("#period").length) {
		period = '&period='+$("#period").val();
	}
	$.post('ajax.php?a=makeEncodedIzvozUrlString', {anketa: srv_meta_anketa_id, string:"izvoz.php?a=status&anketa=" + srv_meta_anketa_id + "&data="+userStatusBase+type+period}, function(url) {
		window.open(url,'_blank');
	});
	//window.open("izvoz.php?a=status&anketa=" + srv_meta_anketa_id + "&data="+userStatusBase+","+type+","+period);
}

//load parametrov za izvoz analiz urejanja v pdf
function printEditsAnalysisPDF() {
        
        var user = '&user='+$("#edits_analysis_continu_user").val();
	var period = '&period='+$("#edits_analysis_continu_period").val();
        var status = '&status='+$("#edits_analysis_status").val();
	var time = '&time='+$("#diagnostics_date_selected").val();
        var from = '&from='+$("#from").val();
        var to = '&to='+$("#to").val();
        
	$.post('ajax.php?a=makeEncodedIzvozUrlString', {anketa: srv_meta_anketa_id, string:"izvoz.php?a=editsAnalysis&anketa=" + srv_meta_anketa_id +user+period+status+time+from+to}, function(url) {
		window.open(url,'_blank');
	});
}

/**
 * When criteria is changed, create new data
 * @returns {undefined}
 */
function editsAnalysisContinuousEditing() {
	var user = $("#edits_analysis_continu_user").val();
	var period = $("#edits_analysis_continu_period").val();
        var status = $("#edits_analysis_status").val();
	var time = $("#diagnostics_date_selected").val();
        var from = $("#from").val();
        var to = $("#to").val();

	$("#edits_analysis_continu_table").load('ajax.php?a=editsAnalysisContinuousEditing', 
            {anketa:srv_meta_anketa_id, user:user, period:period, status:status, time:time, from:from, to:to});
}

function saveSpremenljivkaSpecial_sysvar(spremenljivka,tip)
{
	var sysvar = jQuery.trim($("#special_sysvar_hid_"+spremenljivka).val());
	edit_spremenljivka_tip(spremenljivka, tip, 1, sysvar)
}
function togleSpecialOffer(id)
{
	  $("#specialOptions_div_"+id).slideToggle(600);
}

function add_multigrid_drugo(spremenljivka)
{
//alert(spremenljivka);
}
function enableEmailInvitation(what) {
	//$(what).parent().hide();
	$.redirect('ajax.php?a=enableEmailInvitation', {anketa:srv_meta_anketa_id, what:what});
}

function handleUserCodeSetting()
{
	var phone = $("input[name=phone]:checked").val();
	var email = $("input[name=email]:checked").val();
	$("#userCodeSettings1").load('ajax.php?a=handleUserCodeSetting', {anketa:srv_meta_anketa_id, phone:phone, email:email, all:'1'});
}
function handleUserCodeSkipSetting()
{
	var usercode_skip = $("input[name=usercode_skip]:checked").val();
	$("#userCodeSettings").load('ajax.php?a=handleUserCodeSetting', {anketa:srv_meta_anketa_id, usercode_skip: usercode_skip});
}
function handleUserCodeRequiredSetting()
{
	var usercode_required = $("input[name=usercode_required]:checked").val();
	$("#userCodeSettings").load('ajax.php?a=handleUserCodeSetting', {anketa:srv_meta_anketa_id, usercode_required: usercode_required});
}

function anketa_active_email(status) {
	var anketa=srv_meta_anketa_id;
	$.post('ajax.php?a=anketaActiveEmail', {anketa: anketa}, function () {window.location = 'index.php?anketa='+anketa+'&a=resp'});
}
function editRespondentVrednost(obj) {
	var id = $(obj).attr('id');
	var spr_id = $(obj).attr('spr_id');
	var usr_id = $(obj).attr('usr_id');
	var val = $(obj).val();
	$.post('ajax.php?a=editRespondentVrednost', {anketa:srv_meta_anketa_id, spr_id: spr_id, usr_id: usr_id, val:val});
}
function show_tip_preview_toolbox (tip, copy, advanced, podtip) {

	// predogled novega vprasanja
	if (tip > 0) {

		// preview v popupu za advanced tipe vprasanj
		if (advanced == 1) {

			if ($("#tip_preview_sub_"+tip).length > 0) {
				var pos = $("p[tip="+tip+"]", "#toolbox_add_advanced").offset();
				var width = $("p[tip="+tip+"]", "#toolbox_add_advanced").width();

				$("#tip_preview").css( { "left": (10+pos.left + width) + "px", "right":"auto", "top":(10+pos.top) + "px" } );
				// prikažemo glevni div
				$("#tip_preview").show();

				// priredimo še vsebino
				$("[name=tip_preview_sub]:visible", "#tip_preview").hide();
				$("#tip_preview_sub_"+tip).show();

				// Na koncu zamaknemo gor za visino diva (pri nizkih ekranih)
				//var w_height = $(window).height();
				var height = $("#tip_preview").height();
				//if(w_height < height + pos.top){
					$("#tip_preview").css( { "top":(30+pos.top-height) + "px" } );
				//}
			}

		// preview v toolboxu levo
		} else {

			if ($("#tip_preview_sub_"+tip).length > 0) {
				var pos = $("p[tip="+tip+"]:not(.adv)", "#toolbox_basic").offset();
				var width = $("p[tip="+tip+"]:not(.adv)", "#toolbox_basic").width();

				$("#tip_preview").css( { "left": (13+pos.left + width) + "px", "right":"auto", "top":(pos.top) + "px" } );
				// prikažemo glevni div
				$("#tip_preview").show();

				// priredimo še vsebino
				$("[name=tip_preview_sub]:visible", "#tip_preview").hide();
				if (podtip !== undefined) tip = tip + '_' + podtip;
				$("#tip_preview_sub_"+tip).show();
			}
		}

	// predogled knjiznice
	} else {

		// predogled pri trackingu vprasanj
		if (advanced == 1)
			var container = '';
		else
			var container = '#toolbox_library';

		// --- zdaj je tole samo enkrat za obe varianti
		// knjiznica vprasanj || knjiznica anket || demografija - spremeba tipa
		var pos = $("div[copy="+copy+"]", container).offset() || $("span.new_spr[copy="+copy+"]", container).offset() || $('#vprasanje_float_editing').offset();
		var width = $("div[copy="+copy+"]", container).width() || $("span.new_spr[copy="+copy+"]", container).width() || $('#vprasanje_float_editing').offset();
		var cnt = $('#toolbox_library').offset() || $('#vprasanje_float_editing').offset();

		if (advanced == 1)
			$("#tip_preview").css( { "right": ($(window).width()-cnt.left) + "px", "left":"auto", "top":(10+pos.top) + "px" } );
		else
			$("#tip_preview").css( { "right": ($(window).width()-cnt.left) + "px", "left":"auto", "top":(10+pos.top) + "px" } );
			//$("#tip_preview").css( { "left": (10+pos.left + width) + "px", "right":"auto", "top":(10+pos.top) + "px" } );

		// prikažemo glavni div
		$("#tip_preview").show();

		// priredimo še vsebino
		$("[name=tip_preview_sub]:visible", "#tip_preview").hide();
		$("#tip_preview_sub_0_"+copy).show();
		// --- zdaj je tole samo enkrat za obe varianti


		// ce vprasanje se ni nalozeno, ga nalozimo z ajaxom
		if ($("#tip_preview_sub_0_"+copy).length == 0) {

			$.post('ajax.php?t=branching&a=preview_spremenljivka', {anketa: srv_meta_anketa_id, spremenljivka: copy}, function (data) {
				$('#tip_preview div.inside').append('<div id="tip_preview_sub_0_'+copy+'" class="tip_preview_sub" name="tip_preview_sub"><span>'+lang['srv_new_question']+'</span><div class="tip_sample">'+data+'</div></div>');

				// priredimo še vsebino
				$("[name=tip_preview_sub]:visible", "#tip_preview").hide();
				$("#tip_preview_sub_0_"+copy).show();
			});

		}

	}
}

function show_tip_preview(spr, value) {
//	if ($("#tip_preview").is(':hidden'))

	if ( $('select#spremenljivka_tip_'+spr).attr('data-ajax') == 'true' ) {
		show_tip_preview_toolbox(0, value);
		return;
	}

	//pozicioniramo div
	var pos = $("#spremenljivka_tip_"+spr).offset();
	var width = $("#spremenljivka_tip_"+spr).width();
	var body = $('body').width();
	//$("#tip_preview").css( { "left": (10+pos.left + width) + "px", "top":(10+pos.top) + "px" } );
	$("#tip_preview").css( { "left":"auto", "right": (body - pos.left + 10) + "px", "top":(pos.top) + "px" } );
	// prikažemo glevni div
	$("#tip_preview").show();

	// priredimo še vsebino
	$("[name=tip_preview_sub]:visible").hide();
	$("#tip_preview_sub_"+value).show();
}
function show_tip_preview_first(value) {

	if(value < 5 || value == 7 || value == 21)
		stolpec = 1;
	else if(value == 6 || value == 16 || value == 19 || value == 20)
		stolpec = 2;
	else if(value == 5 || value == 8 || value == 17 || value == 18)
		stolpec = 3;
	else if(value > 8 && value < 16)
		stolpec = 'SN';
	else
		stolpec = 4;

	//pozicioniramo div
	   var pos = $(".questions"+stolpec).offset();
	   var width = $(".questions"+stolpec).width();
	   var height = $(".questions"+stolpec).height();

		if(stolpec == 1 || stolpec == 3)
			$("#tip_preview").css( { "left": (10+pos.left + width) + "px", "right":"auto", "top":(10+pos.top) + "px" } );
		else if(stolpec == 2 || stolpec == 4)
			$("#tip_preview").css( { "left": (pos.left - 200) + "px", "right":"auto", "top":(10+pos.top+height) + "px" } );
		else
			$("#tip_preview").css( { "left": (pos.left - 250) + "px", "right":"auto", "top":(10+pos.top+height) + "px" } );

		// prikažemo glevni div
		$("#tip_preview").show();

		// priredimo še vsebino
		$("[name=tip_preview_sub]:visible").hide();
		$("#tip_preview_sub_"+value).show();

}
function nova_spremenljivka_type(type) {
	$("#tip_preview").hide();

    if (srv_meta_branching == 1) {
	   $('#branching').load('ajax.php?t=branching&a=spremenljivka_new', {anketa: srv_meta_anketa_id, q_type:type}, function() {
		   refreshRight();
   		   refreshBottomIcons('gray');
	   });

    } else {
    	$('#vprasanja').load('ajax.php?a=nova_spremenljivka', {anketa: srv_meta_anketa_id, grupa: srv_meta_grupa, q_type:type}, function() {
    		$("#grupe").load('ajax.php?a=refresh_grupe', {anketa: srv_meta_anketa_id, grupa: srv_meta_grupa});
    		refreshBottomIcons('gray');
    	});
    }
}
//preview pri design, orientation in grid subtype dropdownu (ranking, radio b, multigrid)
function show_tip_preview_subtype(spr, design, tip) {

//	if ($("#tip_preview").is(':hidden'))
	{
		//ranking
		if(tip == '17'){
			if(design == '0')
				val = '17';
			else if(design == '1')
				val = '17_1';
			else
				val = '17_2';
		}

		//SN generator imen
		else if(tip == '9'){
			if(design == '0')
				val = '9';
			else if(design == '1')
				val = '9_1';
			else if(design == '2')
				val = '9_2';
			else if(design == '3')
				val = '9_3';
		}

		//multigrid
		else if(tip == '6'){
			if(design == '0')
				val = '6';
			else if(design == '1')
				val = '6_1';
			else if(design == '2')
				val = '6_2';
			else if(design == '3')
				val = '6_3';
			else if(design == '4')
				val = '6_4';
			else if(design == '5')
				val = '6_5';
			else if(design == '6')
				val = '6_6';
			else if(design == '8')
				val = '6_8';
			else if(design == '9')
				val = '6_9';
			else if(design == '10')
				val = '6_10';
		}

		//radio
		else if(tip == '1'){
			if(design == '0')
				val = '1_1';
			else if(design == '1')
				val = '1';
			else if(design == '2')
				val = '1_2';
			else if(design == '4')
				val = '3';
			else if (design == '5')
				val = '1_5';
			else if (design == '6')
				val = '1_6';
			else if (design == '8')
                val = '1_8';
            else if (design == '9')
                val = '1_9';
			else if (design == '10')
                val = '1_10';
            else if (design == '11')
                val = '1_11';
		}

		//checkbox
		else if(tip == '2'){
			if(design == '1')
				val = '2';
			else if(design == '0')
				val = '2_1';
			else if(design == '2')
				val = '2_2';
			else if (design == '6')
				val = '1_6';
			else if (design == '8')
				val = '2_8';
			else if (design == '10')
				val = '2_10';
			else
				val = '2';

		}

		else if (tip == '21') {
			if (design == '1')
				val = '21_1';
			else if (design == '2')
				val = '21_2';
			else if (design == '3')
				val = '21_3';
			else if (design == '4')
				val = '21_4';
			else if (design == '5')
				val = '21_5';
			else if (design == '6')
				val = '21_6';
			else if (design == '7')
				val = '21_7';
		}

		else if (tip == '23') {
			val = design;
		}

		else if (tip == '5') {
			if(design == '2')
				val = '5_2';
		}

		// lokacija
		else if(tip == '26'){
			if(design == '2')
					val = '26_2';
			else if(design == '1')
					val = '26_1';
		}

		// slider
		else if (tip == '7') {
			val = '7_2';
		}


		// normalno
		if (spr >= 0) {

			//pozicioniramo div
		    var pos = $("#spremenljivka_podtip_"+spr).offset();
		    var width = $("#spremenljivka_podtip_"+spr).width();
		    var body = $('body').width();
			//$("#tip_preview").css( { "left": (pos.left) + "px", "right":"auto", "top":(100+pos.top) + "px" } );
			$("#tip_preview").css( { "left":"auto", "right": (body - pos.left + 10) + "px", "top":(pos.top) + "px" } );

		// pri popuupu za dodajanje advanced tipov vprasanj
		} else {

			//pozicioniramo div
		    var pos = $("#toolbox_add_advanced p[tip="+tip+"][podtip="+design+"]").offset();
		    var width = $("#toolbox_add_advanced p[tip="+tip+"][podtip="+design+"]").width();
			$("#tip_preview").css( { "left": (10+pos.left + width) + "px", "right":"auto", "top":(10+pos.top) + "px" } );
		}

		// prikažemo glavni div
		$("#tip_preview").show();
	}
	// priredimo še vsebino
	$("[name=tip_preview_sub]:visible").hide();
	$("#tip_preview_sub_" + val).show();

	// Na koncu zamaknemo gor za visino diva (pri nizkih ekranih)
	//var w_height = $(window).height();
	var height = $("#tip_preview").height();
	//if(w_height < height + pos.top){
		$("#tip_preview").css( { "top":(30+pos.top-height) + "px" } );
	//}
}

// skrijemo div za preview
function hide_tip_preview () {

	$("#tip_preview").hide();
}

function edit_email_invitations(id) {
   $('#div_float_editing').html('');
   $('#div_float_editing').fadeIn("slow");
	if (id == 0) {
		var email_subject = $("#email_subject").val();
		var email_text    = $("#email_text").val();
	   $('#div_float_editing').load('ajax.php?a=edit_email_invitations', {anketa: srv_meta_anketa_id, id: id, email_subject: email_subject, email_text: email_text},
        function () {
            create_editor('template_text_'+id);
        }
	    ).draggable({delay:100,  ghosting:	true , cancel: 'input, textarea, select, .buttonwrapper'});
	} else {
	   $('#div_float_editing').load('ajax.php?a=edit_email_invitations', {anketa: srv_meta_anketa_id, id: id},
        function () {
				CKEDITOR.replace('template_text_'+id);
// create_editor('template_text_'+id);
        }
	    ).draggable({delay:100,  ghosting:	true , cancel: 'input, textarea, select, .buttonwrapper'});
	}
}
function email_invitations_close(what) {

	var id = $("#template_id").val();
	var template_name = $("#template_name_"+id).val();
	var template_subject = $("#template_subject_"+id).val();

   var editor = CKEDITOR.get('template_text_'+id);
   try {
        template_text = editor.getContent();
        editor.isNotDirty = true;
    } catch (e) {
        template_text = $('#template_text_'+id).val();
    }

	if (what != 'close') {
		$("#email_invitations_templates").load('ajax.php?a=edit_email_invitations_save', {anketa: srv_meta_anketa_id, what: what, id: id, template_name: template_name, template_subject: template_subject, template_text:template_text })
	}
    try {
    	remove_editor('template_text_'+id);
    } catch (e) {}

    $('#div_float_editing').fadeOut("slow");
}
function change_email_invitations_template(id) {
	$("#email_invitations_values").load('ajax.php?a=change_email_invitations_template', {anketa: srv_meta_anketa_id, id: id})
}

function email_invitation_use_template(id) {
	$("#email_subject").val($("#email_invitation_value_subject").val());
	CKEDITOR.get('email_text').setContent($("#email_invitation_value_text").html());
}
function email_invitation_delete_template (id, confirmtext) {
	if (confirm(confirmtext)) {
   	$("#email_invitations_templates").load('ajax.php?a=email_invitation_delete_template', {anketa: srv_meta_anketa_id, id: id})
	}
}
function show_insert_email_respondents(id) {
	$('#fullscreen').html('').fadeIn('slow').draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
	$('#fade').fadeTo('slow', 1);
   $('#fullscreen').load('ajax.php?a=show_insert_email_respondents', {anketa: srv_meta_anketa_id, id: id}).draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
}
function close_insert_email_respondents () {
	$('#fade').fadeOut('slow');
	$('#fullscreen').fadeOut('slow').html('');
}
function show_edit_email_respondents(id) {
	$('#fullscreen').html('').fadeIn('slow').draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
	$('#fade').fadeTo('slow', 1);
   $('#fullscreen').load('ajax.php?a=show_edit_email_respondents', {anketa: srv_meta_anketa_id, id: id}).draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});

}
function close_edit_email_respondents(what) {
	var list_id = $("#list_id").val();
	var list_variables = $("#list_variables_"+list_id).val();
	var list_name = $("#list_name_"+list_id).val();
	var list_text = $("#list_text_"+list_id).val();

	if (what != 'close') {
		$("#userInsertRight").load('ajax.php?a=edit_respondents_list_save', {anketa: srv_meta_anketa_id, what: what, list_id: list_id, list_variables: list_variables, list_name: list_name, list_text: list_text })
	}

 	$('#fade').fadeOut('slow');
	$('#fullscreen').fadeOut('slow').html('');
}
function respondents_list_add(id) {
	show_insert_email_respondents(id);
}
function delete_respondents_list(id, confirmtext) {
	if (confirm(confirmtext)) {
   	$("#userInsertRight").load('ajax.php?a=delete_respondent_list', {anketa: srv_meta_anketa_id, id: id})
	}
}
function change_mailto_radio() {
	//var statusi
	var mailto_radio = $('[name="mailto"]:checked').val();
	var prefix = "";
	var checkboxes = "";
	$('[name="mailto_status[]"]:checked').each(function(el) {
		checkboxes = checkboxes+prefix+$(this).val();
		prefix = ",";
	});

	$("#mailto_right").load('ajax.php?a=change_mailto_radio', {anketa: srv_meta_anketa_id, mailto_radio: mailto_radio, mailto_status: checkboxes })
}
function change_mailto_status() {
	$("#radio_mailto_status").attr("checked","checked");
	change_mailto_radio();
}

function preview_mailto_email() {
	var mailto_radio = $("[name=mailto]:checked").val();
	var prefix = "";
	var checkboxes = "";
	$('[name="mailto_status[]"]:checked').each(function(el) {
		checkboxes = checkboxes+prefix+$(this).val();
		prefix = ",";
	});
        $('#fullscreen').html('').fadeIn('slow').draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
        $('#fade').fadeTo('slow', 1);

// $('#fullscreen').html('');
// $('#fullscreen').fadeIn("slow");
   $('#fullscreen').load('ajax.php?a=preview_mailto_email', {anketa: srv_meta_anketa_id, mailto_radio: mailto_radio, mailto_status: checkboxes}).draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
}

function preview_mailto_email_cancle() {
    $('#fullscreen').hide();
    $('#fade').fadeOut('slow');
}

function preview_mailto_email_submit() {
	$('#frm_mailto_preview').submit();
	$('#fullscreen').hide();
	$('#fade').fadeOut('slow');
}


function show_surveyListSettings() {

	var sortby = $('input#sortby').val();
	var sorttype = $('input#sorttype').val();

	$('#fullscreen').html('').fadeIn('slow');
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').load('ajax.php?a=show_surveyListSettings', {sortby:sortby, sorttype:sorttype}).draggable({handle: '#survey_list_inner', cancel: 'input, #sortable, .buttonwrapper, #rows_per_page'});
}

function show_surveyListQickInfo() {
	$('#fade').fadeTo('slow', 1);
	$('#survey_ListQickInfo').load('ajax.php?a=show_surveyListQickInfo').show();
}

function show_surveyFind() {
	// če je polje za filter vidno, ga počistimo in skrijemo
	if ($("#sl_find").is(":visible")) {
		$("#sl_find").hide();
		$("#sl_find_survey").val('');
	} else {
		$("#sl_find").show();
	}
}
function sl_filter() {
	if ($("#sl_find").is(":visible")) {
		var sl_filter = $("#sl_find_survey").val();
		$('#survey_list').load('ajax.php?a=surveyListFilter', {sl_filter:sl_filter});

		//$('#survey_list').load('ajax.php?a=save_surveyListSettings', {data:data, vrstniRed:vrstniRed, sortby:sortby, sorttype:sorttype, rows_per_page:rows_per_page});

	}
}

// prekli�emo
function cancle_surveyListSettings() {
	$('#fullscreen').hide();
	$('#fade').fadeOut('slow');
}
// obnovimo privzete vrednosti
function default_surveyListSettings() {
	$('#fullscreen').html('').fadeIn('slow');
	$('#fullscreen').load('ajax.php?a=default_surveyListSettings').draggable({handle: '#survey_list_inner', cancel: 'input, #sortable, .buttonwrapper'});
}
// shranimo katere celice prikazujemo in v kak�nem vrstnem redu
function save_surveyListSettings(){
	var data = "";
	var prefix="";

	var vrstniRed = "";
	var vrstniRedPrefix = "";
	$("input[name=sl_fields]").each(function() {
		if ( $(this).is(':checked') ) {
			data=data+prefix+$(this).attr('value');
			prefix =",";
		}
		vrstniRed=vrstniRed+vrstniRedPrefix+$(this).attr('value');
		vrstniRedPrefix =",";

	});

	var sortby = $('input#sortby').val();
	var sorttype = $('input#sorttype').val();
	var rows_per_page = $('input#rows_per_page').val();
	
	$('#survey_list').load('ajax.php?a=save_surveyListSettings', {data:data, vrstniRed:vrstniRed, sortby:sortby, sorttype:sorttype, rows_per_page:rows_per_page});
	$('#fullscreen').hide();
	$('#fade').fadeOut('slow');
}

// polovimo in shranimo �irine header celic po resizanju
function save_surveyListCssSettings(event, ui) {
	var data = ui.helper.attr('baseCss')+","+ ui.size['width'];
	var sortby = $('input#sortby').val();
	var sorttype = $('input#sorttype').val();
	
	$('#survey_list').load('ajax.php?a=save_surveyListCssSettings', {data:data, sortby:sortby, sorttype:sorttype});
}

// Sortiramo moje ankete po stolpcih
function surveyList_goTo(sortbyid, sorttype) {
	var onlyPhone = ($("#onlyPhone").val() == 1) ? true : false;
	
	// Pogledamo, ce smo slucajno v iskanju in nastavimo ustrezne parametre
	var searchParams = '';
	if($("#searchParams").val() != ''){
		searchParams = '&' + $("#searchParams").val();
	}
	
	$('#survey_list').load('ajax.php?a=surveyList_goTo' + searchParams, {sortby:sortbyid, sorttype:sorttype, onlyPhone:onlyPhone} );
	// window.location = url;
}

function surveyList_user(what,el) {
	if (what == 'i') {
		var uid = $(el).attr('iuid');
	} else if (what == 'e') {
		var uid = $(el).attr('euid');
	} else if (what == 'uid') {
		what = 'i';
		uid = el;
	} else {
		var uid = 0;
	}
	$('#survey_list').load('ajax.php?a=surveyList_user', {list_user_type:what, uid:uid});
}
function surveyList_user_reload(what,el) {
	if (what == 'i') {
		var uid = $(el).attr('iuid');
	} else if (what == 'e') {
		var uid = $(el).attr('euid');
	} else if (what == 'uid') {
		what = 'i';
		uid = el;
	} else {
		var uid = 0;
	}
	$.post('ajax.php?a=surveyList_user', {list_user_type:what, uid:uid}, function(){
		location.reload();
	});
}

function surveyList_language(lang_id) {
	$('#survey_list').load('ajax.php?a=surveyList_language', {lang_id:lang_id});
}
function surveyList_language_reload(lang_id) {
	$.post('ajax.php?a=surveyList_language', {lang_id:lang_id}, function(){
		location.reload();
	});
}

function surveyList_gdpr(gdpr) {
	$('#survey_list').load('ajax.php?a=surveyList_gdpr', {gdpr:gdpr});
}
function surveyList_gdpr_reload(gdpr) {
	$.post('ajax.php?a=surveyList_gdpr', {gdpr:gdpr}, function(){
		location.reload();
	});
}

function surveyList_library() {
	var currentLibrary = $("#library_filter :selected").val();
	$('#survey_list').load('ajax.php?a=surveyList_library', {currentLibrary:currentLibrary});
}

function surveyList_info(anketa) {

	if($('#survey_list_info').is(":visible")){

		// Ce smo kliknili drug info
		if($('.info.icon-orange').attr('anketa') != anketa){

			// Najprej ugasnemo odprtega
			$('.info').removeClass('icon-orange');

			// Potem prikazemo novega
			var position = $('#info_icon_' + anketa).position();
			$('#survey_list_info').load('ajax.php?t=surveyList&a=surveyList_display_info', {anketa: anketa}, function(){
				$('#info_icon_' + anketa).toggleClass('icon-orange');
				$('#survey_list_info').css('top', position.top+34);
				$("#survey_list_info").show();
			});
		}
		else{
			// Samo ugasnemo odprtega
			$("#survey_list_info").hide();
			$('.info').removeClass('icon-orange');
		}
	}
	// Prikazemo info box
	else{
		var position = $('#info_icon_' + anketa).position();

		$('#survey_list_info').load('ajax.php?t=surveyList&a=surveyList_display_info', {anketa: anketa}, function(){
			$('#info_icon_' + anketa).toggleClass('icon-orange');
			$('#survey_list_info').css('top', position.top+34);
			$("#survey_list_info").show();
		});
	}
}

function survey_chaneg_type(new_type, change_type_submit) {
	$('#fullscreen').html('').fadeIn('slow').draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').load('ajax.php?a=survey_chaneg_type', {anketa: srv_meta_anketa_id, new_type: new_type, change_type_submit:change_type_submit}).draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
}
function survey_chaneg_type_cancle() {
	$('#fullscreen').hide();
	$('#fade').fadeOut('slow');
}
function preview_spremenljivka(spremenljivka, lang_id) {
	$('#fullscreen').html('').fadeIn('slow').draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
	$('#fade').fadeTo('slow', 1);

	$('#fullscreen').load('ajax.php?a=preview_spremenljivka', {anketa: srv_meta_anketa_id, spremenljivka: spremenljivka, lang_id: lang_id,podstran: srv_meta_podstran}).draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
}
function preview_spremenljivka_analiza(spremenljivka, lang_id) {
	$('#fullscreen').html('').fadeIn('slow').draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
	$('#fade').fadeTo('slow', 1);

	$('#fullscreen').load('ajax.php?t=analysis&a=preview_spremenljivka', {anketa: srv_meta_anketa_id, spremenljivka: spremenljivka, lang_id: lang_id,podstran: srv_meta_podstran}).draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
}
function preview_spremenljivka_cancle() {
	$('#fullscreen').hide();
	if ($('#vprasanje').css('display') != 'block') {
		$('#fade').fadeOut('slow');
	}
}
function preview_page() {
	$('#fullscreen').html('').fadeIn('slow').draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').load('ajax.php?a=preview_page', {anketa: srv_meta_anketa_id, grupa: srv_meta_grupa}).draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
}
function preview_page_cancle() {
	$('#fullscreen').hide();
	$('#fade').fadeOut('slow');
}

/*function rename_variable(spremenljivka, value, variable_custom, show) {
	$("#div_variable_"+spremenljivka).load('ajax.php?a=rename_variable', {anketa: srv_meta_anketa_id, spremenljivka:spremenljivka, variable_custom: variable_custom, value:value, show:show});
}*/


// Odpravimo težave z IE7, kateri ne omogoča disejblat posameznih opcij
function IE7_select_disabled_fix () {

	if (jQuery.browser.msie && parseInt(jQuery.browser.version) < 8) {
		$('option[disabled]').css({'color': '#cccccc'});
		$('select').change(function() {
			if(this.options[this.selectedIndex].disabled) {
				if(this.options.length == 0) {
					this.selectedIndex = -1;
				} else {
					this.selectedIndex--;
				}
				$(this).trigger('change');
			}
		});
		$('select').each(function(it) {
			if(this.options[this.selectedIndex].disabled) {
				this.onchange();
			}
		});
	}
};

function change_alert_respondent(what,el) {
	var id = el.attr("id");
	var checked = $("#alert_"+what).is(':checked');
	$("span#label_alert_"+what).load('ajax.php?a=change_alert_respondent', {anketa: srv_meta_anketa_id, checked: checked, what:what});
}
function chnage_alert_instruction(el) {
	var checked = el.is(':checked');
	if (checked) {
		$("#alert_respondent_cms_instruction").show();
	} else {
		$("#alert_respondent_cms_instruction").hide();
	}
}
function alert_add_necessary_sysvar(what,el) {
	var id = el.attr("id");
	var checked = $("#alert_"+what).is(':checked');
	$("span#label_alert_"+what).load('ajax.php?a=alert_add_necessary_sysvar', {anketa: srv_meta_anketa_id, checked: checked, what:what});
}
function alert_change_user_from_cms(what, el) {
	$("span#label_alert_"+what).load('ajax.php?a=alert_change_user_from_cms', {anketa: srv_meta_anketa_id});
}

function alert_edit_if (type, uid) {

	$('#fade').fadeTo('slow', 1);
	$('#div_condition_editing').load('ajax.php?a=alert_edit_if', {anketa: srv_meta_anketa_id, uid: uid, type: type}).show();
}

function alert_if_remove (_if) {

	if (confirm( lang['srv_brisiifconfirm'] )) {

		$.post('ajax.php?t=branching&a=if_remove', { 'if' : _if, anketa : srv_meta_anketa_id }, function () {
			window.location.reload();
		});
		$('#div_condition_editing').hide();
		//$('#fade').fadeOut('slow');
	}
}

function alert_if_close (_if) {

	$('#div_condition_editing').hide();
	window.location.reload();
}

//nastavitve za glasovanje - what je spremenljivka v tabeli srv_glasovanje, ki jo spreminjamo
function edit_glasovanje(spremenljivka, results, what){

	if(what == 'finish_author' || what == 'finish_respondent_cms' || what == 'finish_other' || what == 'show_results' || what == 'show_percent' || what == 'show_graph'){
		if(results.checked == true)
			results = 1;
		else
			results = 0;
	}

	if($("#glas_extra_settings").is(":visible") == true)
		var displayExtra = 1;
	else
		var displayExtra = 0;

	$("#glas_settings").load('ajax.php?a=glasovanje_settings&t=glasovanje', {anketa: srv_meta_anketa_id, spremenljivka: spremenljivka, results: results, what: what, displayExtra: displayExtra}, function() {
		if(what == 'show_intro' || what == 'show_concl' || what == 'stat' || what == 'embed')
			$("#branching").load('ajax.php?a=glasovanje_vprasanja&t=glasovanje', {anketa: srv_meta_anketa_id, spremenljivka: spremenljivka, what: what});
	});
}

function glas_extra_settings(){
	$('.more').toggle();
	$('.less').toggle();
	$('#glas_extra_settings').toggle();
}

//reload za editiranje uvoda/zakljucka pri formi
/*function load_formIO(spremenljivka, what){
	$("#question_holder").load('ajax.php?a=form_extra', {anketa: srv_meta_anketa_id, spremenljivka: spremenljivka, what: what});
}

//reload za editiranje uvoda/zakljucka pri glasovanju
function load_glasIO(spremenljivka, what){
	$("#question_holder").load('ajax.php?a=form_extra', {anketa: srv_meta_anketa_id, spremenljivka: spremenljivka, what: what});
}*/

//hitre nastavitve za formo - what je spremenljivka, ki jo spreminjamo
function edit_form_settings(spremenljivka, results, what){

	if(what == 'finish_author' || what == 'finish_respondent_cms' || what == 'finish_other'){
		if(results.checked == true)
			results = 1;
		else
			results = 0;
	}

	$("#simple").load('ajax.php?a=form_settings', {anketa: srv_meta_anketa_id, spremenljivka: spremenljivka, results: results, what: what});
	//$.post('ajax.php?a=form_settings', {anketa: srv_meta_anketa_id, spremenljivka: spremenljivka, results: results, what: what});

	if(what == 'show_intro' || what == 'show_concl')
		$("#vprasanja").load('ajax.php?a=glasovanje_vprasanja', {anketa: srv_meta_anketa_id, spremenljivka: spremenljivka, what: what});

}

function newAnketaBlank(type) {

	var survey_type = type || $("input[name=newAnketaBlank]:checked").val();

	var naslov = jQuery.trim($("#novaanketa_naslov").val());
	if ($("#novaanketa_naslov_1").length > 0) {
		naslov = jQuery.trim($("#novaanketa_naslov_1").val());
	}

	var akronim = naslov;
	if ($("#novaanketa_akronim_1").length > 0) {
		var akronim = jQuery.trim($("#novaanketa_akronim_1").val());
	}

	var folder = '-1';
	if ($("#novaanketa_folder").length) {
		folder = $("#novaanketa_folder").val();
	}

    var intro_opomba = jQuery.trim($("#novaanketa_opis").val());

    if ($("#lang_resp").length > 0 && $("#lang_resp").val() > 0) {
    	var lang_resp = jQuery.trim($("#lang_resp").val());
	} else {
		var lang_resp = 1;
	}

	var skin = jQuery.trim($("#noSurvey_skin_id").val());
	if(skin == '')
		skin = '1kaBlue';

	$.redirect('ajax.php?a=anketa', {naslov: naslov, intro_opomba: intro_opomba, akronim: akronim, survey_type:survey_type, lang_resp:lang_resp, skin:skin, folder:folder});
}
function newAnketaCancle() {
	$('#fade').fadeOut('slow');
	$('#fullscreen').html('').fadeOut('slow');
}
// Preklop skina pri ustvarjanju ankete
function change_noSurvey_skin(skin){

	// Deaktiviramo
	$('.selected').removeClass('selected');

	// Aktiviramo novega
	$("#skin_" + skin).addClass('selected');

	$('input[name=skin]').val(skin);
}
// Scroll po skinih
function scroll_noSurvey_skin(direction){

	var leftPos = $('#skins_holder').scrollLeft();

	if(direction == 'left'){
		$("#skins_holder").animate({scrollLeft: leftPos - 800}, 1000, 'easeOutQuart');
	}
	else{
		$("#skins_holder").animate({scrollLeft: leftPos + 800}, 1000, 'easeOutQuart');
	}
}

// Nova anketa s kopiranjem obstojece
function newAnketaCopy() {

	var ank_id = $("#my_surveys").val();

	$.redirect('ajax.php?t=library&a=anketa_copy_new', {ank_id: ank_id});
}

// Preklop predloge pri ustvarjanju ankete iz predloge
function newAnketaTemplate_change(id){

	// Deaktiviramo in aktiviramo novega
	$('.selected').removeClass('selected');
	$("#template_" + id).addClass('selected');

	// Nastavimo naslov
	var title = $("#template_title_" + id).val();
	$('input[name=novaanketa_naslov_1]').val(title);
	$('input[name=novaanketa_akronim_1]').val(title);

	// Nastavimo id za post
	$('input[name=noSurvey_template_id]').val(id);
}
// Nova anketa s kopiranjem iz templata
function newAnketaTemplate() {
	
	var ank_id = $("#noSurvey_template_id").val();
	
	var naslov = jQuery.trim($("#novaanketa_naslov_1").val());
	var akronim = jQuery.trim($("#novaanketa_akronim_1").val());
	
	var folder = '-1';
	if ($("#novaanketa_folder").length) {
		folder = $("#novaanketa_folder").val();
	}
	
	if(ank_id == '' || ank_id < 1){
		genericAlertPopup('srv_newSurvey_survey_template_error');
	}
	else{
		$.redirect('ajax.php?t=library&a=anketa_copy_new', {ank_id:ank_id, naslov:naslov, akronim:akronim, folder:folder});
	}
}

// Preview vprasanj pri uvozu ankete iz besedila
function anketaFromText_preview(text) {
	
	var text = $("textarea#anketa_from_text_textarea").val().trim();
	
	$("#preview_field").load('ajax.php?t=newSurvey&a=from_text_preview', {text: text});
}
// Ustvarimo anketo in ji uvozimo vprasanja iz texta
function newAnketaFromText() {
	
	var survey_type = '2';
	var skin = '1kaBlue';

	var naslov = jQuery.trim($("#novaanketa_naslov").val());
	if ($("#novaanketa_naslov_1").length > 0) {
		naslov = jQuery.trim($("#novaanketa_naslov_1").val());
	}

	var akronim = naslov;
	if ($("#novaanketa_akronim_1").length > 0) {
		var akronim = jQuery.trim($("#novaanketa_akronim_1").val());
	}

    if ($("#lang_resp").length > 0 && $("#lang_resp").val() > 0) {
    	var lang_resp = jQuery.trim($("#lang_resp").val());
	} else {
		var lang_resp = 1;
	}

	// Uvoz vprasanj in variabel iz texta
	var from_text = $("textarea#anketa_from_text_textarea").val().trim();

	$.redirect('ajax.php?a=anketa_from_text', {naslov: naslov, akronim: akronim, survey_type:survey_type, lang_resp:lang_resp, skin:skin, from_text:from_text});
}

// Prikaz popupa znotraj ankete za uvoz iz texta
function popupImportAnketaFromText() {
	
	$('#fade').fadeTo('slow', 1);
    $("#popup_import_from_text").load('ajax.php?a=show_import_from_text');
    $("#popup_import_from_text").show();
}
// Prikaz popupa znotraj ankete za uvoz iz texta
function popupImportAnketaFromText_close() {
	
	$("#popup_import_from_text").hide();
	$('#fade').fadeOut('slow');
}
// Uvozimo vprasanja v anketo iz texta
function importAnketaFromText() {
	
	// Uvoz vprasanj in variabel iz texta
	var from_text = $("textarea#anketa_from_text_textarea").val().trim();

	$.redirect('ajax.php?a=import_from_text', {anketa: srv_meta_anketa_id, from_text:from_text}, function(){
		popupImportAnketaFromText_close();
	});
}

/* funkcije za manipulacijo profilov respondentov */
function respondent_run(pid) {
	init_progressBar(true);
	var variables = $("#respondent_profile_values").find("#respondent_profile_variables").val();
	var data = $("#respondent_profile_values").find("#respondent_profile_value_text").val();
	var profile_from = $("#profile_from").val();
//	$("#survey_respondents").load('ajax.php?a=survey_respondents&b=run_respondent_profile', {anketa: srv_meta_anketa_id, pid:pid, variables:variables, data:data});
	$.redirect('ajax.php?a=survey_respondents&b=run_respondent_profile', {anketa: srv_meta_anketa_id, pid:pid, variables:variables, data:data, profile_from:profile_from});
}

function respondent_save(pid) {
	var variables = $("#respondent_profile_values").find("#respondent_profile_variables").val();
	var data = $("#respondent_profile_values").find("#respondent_profile_value_text").val();
	$("#survey_respondents").load('ajax.php?a=survey_respondents&b=save_respondent_profile', {anketa: srv_meta_anketa_id, pid:pid, variables:variables, data:data});
}

function respondent_save_new(pid) {
    $('#fade').fadeTo('slow', 1);
    $("#respondent_new_dialog").find("#newProfileId").val(pid);
	$("#respondent_new_dialog").show();
}
function change_respondent_profile(pid) {
	var profile_from = $("#profile_from").val();
	$("#survey_respondents").load('ajax.php?a=survey_respondents&b=change_respondent_profile', {anketa: srv_meta_anketa_id, pid: pid, profile_from:profile_from})
}
function respondent_saveNewProfile() {
	var pid = $("#respondent_new_dialog").find("#newProfileId").val();
	var name = $("#respondent_new_dialog").find("#newProfileName").val();
	var variables = $("#respondent_profile_values").find("#respondent_profile_variables").val();
	var data = $("#respondent_profile_values").find("#respondent_profile_value_text").val();

	$("#survey_respondents").load('ajax.php?a=survey_respondents&b=save_new_respondent_profile', {anketa: srv_meta_anketa_id, name:name, pid:pid, variables:variables, data:data}, function() {
		$("#respondent_new_dialog").hide();
		$('#fade').fadeOut('slow');
	});
}
function respondent_renameProfile() {
	var pid = $("#respondent_rename_dialog").find("#renameProfileId").val();
	var name = $("#respondent_rename_dialog").find("#renameProfileName").val();
	$("#survey_respondents").load('ajax.php?a=survey_respondents&b=rename_respondent_profile', {anketa: srv_meta_anketa_id, name:name, pid:pid}, function() {
		$("#respondent_rename_dialog").hide();
		$('#fade').fadeOut('slow');
	});

}
function respondent_deleteProfile() {
	var pid = $("#respondent_delete_dialog").find("#deleteProfileId").val();
	$("#survey_respondents").load('ajax.php?a=survey_respondents&b=delete_respondent_profile', {anketa: srv_meta_anketa_id, pid:pid}, function() {
		$("#respondent_delete_dialog").hide();
		$('#fade').fadeOut('slow');
	});

}
function showRenameRespondentProfile() {
	var pid = $("#respondent_profiles").find(".option.active").attr('value');
    $('#fade').fadeTo('slow', 1);
    $("#respondent_rename_dialog").find("#renameProfileId").val(pid);
    $("#respondent_rename_dialog").show();
}
function showDeleteRespondentProfile() {
	var pid = $("#respondent_profiles").find(".option.active").attr('value');
    $('#fade').fadeTo('slow', 1);
    $("#respondent_delete_dialog").find("#deleteProfileId").val(pid);
    $("#respondent_delete_dialog").show();
}
function respondent_change_variable(el) {
	var pid = $("#respondent_profiles").find(".option.active").attr('value');
	var checked = "";
	var prefix = "";
	var manual = $("input[name=resp_check]:checked").each( function() {
		checked = checked+prefix+$(this).val();
		prefix = ",";
	});
	$("#respondent_profile_variables").val(checked);
}
/* konec funkcij za manipulacijo profilov respondentov */


/* funkcije za manipulacijo profilov email vabil */
function invitation_run(pid) {
	var title = $("#invitation_profile_values").find("#invitation_profile_title").val();
	var replyto = $("#invitation_profile_values").find("#invitation_profile_replyto").val();
//	var content = $("#invitation_profile_values").find("#invitation_profile_content").val();
	var content = CKEDITOR.get('invitation_profile_content').getContent();
	remove_editor("invitation_profile_content");

	//$("#survey_invitation").load('ajax.php?a=survey_invitation&b=run_invitation_profile', {anketa: srv_meta_anketa_id, pid:pid, title:title, content:content}, function() {
	//	create_editor("invitation_profile_content");
	//});
	$.redirect('ajax.php?a=survey_invitation&b=run_invitation_profile', {anketa: srv_meta_anketa_id, pid:pid, title:title, content:content, replyto:replyto});
}

function invitation_save(pid) {
	var title = $("#invitation_profile_values").find("#invitation_profile_title").val();
	var replyto = $("#invitation_profile_values").find("#invitation_profile_replyto").val();
//	var content = $("#invitation_profile_values").find("#invitation_profile_content").val();
	var content = CKEDITOR.get('invitation_profile_content').getContent();
	remove_editor("invitation_profile_content");
	$("#survey_invitation").load('ajax.php?a=survey_invitation&b=save_invitation_profile', {anketa: srv_meta_anketa_id, pid:pid, title:title, content:content, replyto:replyto}, function() {
		create_editor("invitation_profile_content");
	});
}

function invitation_save_new(pid) {
    $('#fade').fadeTo('slow', 1);
    $("#invitation_new_dialog").find("#newProfileId").val(pid);
	$("#invitation_new_dialog").show();
}
function change_invitation_profile(pid) {
	remove_editor("invitation_profile_content");
	$("#survey_invitation").load('ajax.php?a=survey_invitation&b=change_invitation_profile', {anketa: srv_meta_anketa_id, pid: pid}, function() {
		create_editor("invitation_profile_content");
	});
}
function invitation_saveNewProfile() {
	var pid = $("#invitation_new_dialog").find("#newProfileId").val();
	var name = $("#invitation_new_dialog").find("#newProfileName").val();
	var title = $("#invitation_profile_values").find("#invitation_profile_title").val();
	var replyto = $("#invitation_profile_values").find("#invitation_profile_replyto").val();
//	var content = $("#invitation_profile_values").find("#invitation_profile_content").val();
	var content = CKEDITOR.get('invitation_profile_content').getContent();
	remove_editor("invitation_profile_content");
	$("#survey_invitation").load('ajax.php?a=survey_invitation&b=save_new_invitation_profile', {anketa: srv_meta_anketa_id, name:name, pid:pid, title:title, content:content, replyto:replyto}, function() {
		create_editor("invitation_profile_content");
		$("#invitation_new_dialog").hide();
		$('#fade').fadeOut('slow');
	});
}
function invitation_renameProfile() {
	var pid = $("#invitation_rename_dialog").find("#renameProfileId").val();
	var name = $("#invitation_rename_dialog").find("#renameProfileName").val();
	remove_editor("invitation_profile_content");
	$("#survey_invitation").load('ajax.php?a=survey_invitation&b=rename_invitation_profile', {anketa: srv_meta_anketa_id, name:name, pid:pid}, function() {
		create_editor("invitation_profile_content");
		$("#invitation_rename_dialog").hide();
		$('#fade').fadeOut('slow');
	});

}
function invitation_deleteProfile() {
	var pid = $("#invitation_delete_dialog").find("#deleteProfileId").val();
	remove_editor("invitation_profile_content");
	$("#survey_invitation").load('ajax.php?a=survey_invitation&b=delete_invitation_profile', {anketa: srv_meta_anketa_id, pid:pid}, function() {
		create_editor("invitation_profile_content");
		$("#invitation_delete_dialog").hide();
		$('#fade').fadeOut('slow');
	});

}
function showRenameInvitationProfile() {
	var pid = $("#invitation_profiles").find(".option.active").attr('value');
    $('#fade').fadeTo('slow', 1);
    $("#invitation_rename_dialog").find("#renameProfileId").val(pid);
    $("#invitation_rename_dialog").show();
}
function showDeleteInvitationProfile() {
	var pid = $("#invitation_profiles").find(".option.active").attr('value');
    $('#fade').fadeTo('slow', 1);
    $("#invitation_delete_dialog").find("#deleteProfileId").val(pid);
    $("#invitation_delete_dialog").show();
}
/* konec funkcij za manipulacijo profilov email vabil */

function recalc_alert_expire(days) {
	$("#calc_alert_expire").load('ajax.php?a=recalc_alert_expire', {anketa: srv_meta_anketa_id, days:days});
}

// enablamo in disablamo vnos max stevila glasov (trajanje)
function voteCountStatus(status) {
	if (status == 1 || status == 2) {
		$('#vote_count').removeAttr('disabled');
		$('.vote_limit_warning').show();
	} else {
		$('#vote_count').attr('disabled', true);
		$('.vote_limit_warning').hide();
	}
}
function voteCountToggle(status) {
    if (status == 1 || status == 2) {
    	$('#voteCountToggle1').show();
    } else {
        $('#voteCountToggle1').hide();
    }
}

function survey_statistic_referal(what) {
	if ($(what).attr('value') == '0') { // nalozimo vsebino samo prvi klik
		var type = $("#type").val();
		var period = $("#period").val();
		var hideNullValues_dates = $("#hideNullValues_dates").is(':checked');
		var hideNullValues_status = $("#hideNullValues_status").is(':checked');

		$("#survey_referals").load('ajax.php?a=survey_statistic_referal', {anketa:srv_meta_anketa_id, type: type, period:period, hideNullValues_dates:hideNullValues_dates, hideNullValues_status: hideNullValues_status}, function() {
			$("#survey_referals").slideDown();
			$(what).attr('value','1');
		});
	} else if ($(what).attr('value') == '1') { // skrijemo
		$("#survey_referals").slideUp();
		$(what).attr('value','2');
	} else { // prikazemo
		$("#survey_referals").slideDown();
		$(what).attr('value','1');
	}
}
function ip_list_podrobno (what) {
	if ($(what).attr('value') == '0') { // nalozimo vsebino samo prvi klik
		var type = $("#type").val();
		var period = $("#period").val();
		var hideNullValues_dates = $("#hideNullValues_dates").is(':checked');
		var hideNullValues_status = $("#hideNullValues_status").is(':checked');

		$("#ip_list_podrobno").load('ajax.php?a=survey_statistic_ip_list', {anketa:srv_meta_anketa_id, type: type, period:period, hideNullValues_dates:hideNullValues_dates, hideNullValues_status: hideNullValues_status}, function() {
			$("#ip_list_podrobno").slideDown();
			$(what).attr('value','1');
		});
	} else if ($(what).attr('value') == '1') { // skrijemo
		$("#ip_list_podrobno").slideUp();
		$(what).attr('value','2');
	} else { // prikazemo
		$("#ip_list_podrobno").slideDown();
		$(what).attr('value','1');
	}
/*
	if ($('#ip_list_podrobno').css('display') == 'none') {
		$('#ip_list_podrobno').slideDown();
	} else {
		$('#ip_list_podrobno').slideUp();
	}
	*/
}
function survey_statistic_status(status) {
	$('#fullscreen').html('').fadeIn('slow').draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').load('ajax.php?a=survey_statistic_status', {anketa: srv_meta_anketa_id, status: status}).draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
}
function close_statistic_float_div() {
	$('#fullscreen').fadeOut('slow');
	$('#fade').fadeOut('slow');
}

function refreshBottomIcons(color) {
	if (color == undefined && $(".spr_editmode").length != 0)
		{ color = 'gray'; }

	if (color == 'gray') {

		$("#bottom_invitaton_button").removeClass('ovalbutton_orange').addClass('ovalbutton_gray');
    } else {

    	$("#bottom_invitaton_button").removeClass('ovalbutton_gray').addClass('ovalbutton_orange');
    }
}

function save_user_settings() {
	var icons_always_on = $("input[name=icons_always_on]:checked").val();
	var full_screen_edit = $("input[name=full_screen_edit]:checked").val();
	$.post('ajax.php?a=save_user_settings', {icons_always_on: icons_always_on, full_screen_edit: full_screen_edit}, function() {
		show_success_save();

	});
}
function updateManual() {
	$("#radioManual").attr("checked", "checked");
}
function updateManual1() {
	$("#radioManual1").attr("checked", "checked");
}

function show_success_save(timeout){

	if (timeout == undefined)
		timeout = 2500;

	$(".btn_savesettings").addClass('ovalbutton_orange').removeClass('ovalbutton_orange');
	$('#success_save').load('ajax.php?a=display_success_save', {anketa: srv_meta_anketa_id}).show();

	setTimeout(function() {$('#success_save').animate({opacity:0})}, timeout);
}

function chnage_full_screen_edit() {

	//srv_meta_full_screen_edit = $("input[name=full_screen_edit]:checked").val();
	srv_meta_full_screen_edit = $("input[name=full_screen_edit]").is(':checked') ? 1 : 0;

	$.post('ajax.php?a=save_user_settings', {anketa: srv_meta_anketa_id, full_screen_edit: srv_meta_full_screen_edit}, function() {
		show_success_save();
	});
}


function doTxtExport() {
	$('#export_txt_form').submit();
	/*
	var fullMeta = ( $('#fullMeta').is(':checked') ) ? '&fullMeta=1' : '';
	var hiddenSystem = ( $('#hiddenSystem').is(':checked') ) ? '&hiddenSystem=1' : '';
	window.open( 'ajax.php?t=export&a=doexport'+'&m=txt'+'&anketa=' + srv_meta_anketa_id + fullMeta+hiddenSystem);
	*/
	return false;
}

function doExcelXlsExport() {
	$('#export_excel_xls_form').submit();
}

function doExcelExport() {
	$('#export_excel_form').submit();
	/*
	// preberemo katere statuse odpiramo
	var fullMeta = ( $('#fullMeta').is(':checked') ) ? '&fullMeta=1' : '';
	var hiddenSystem = ( $('#hiddenSystem').is(':checked') ) ? '&hiddenSystem=1' : '';
	var export_labels = ( $('#export_labels').is(':checked') ) ? '&export_labels=1' : '';

	/ *
	var replace = '';
	if ( $('#do_replace').is(':checked') ) {
		replace = '&replace='+$('#replace_with').val();
	}
	* /

	// tole nardimo malo bol zahtevno če bomo kdaj hoteli dodati možnost izbire večih zamenjevalnih polj
	var checked = $('input[name="export_delimit"]:checked').val();
	if (checked == 0) {
		var $div_selector = $('#replace_export_delimit_semicolon');
	} else {
		var $div_selector = $('#replace_export_delimit_comma');
	}
	var all_imputs = ($div_selector.find('input[type="text"]').length) / 2;


	//alert(replaces);
	//	window.open( 'ajax.php?t=export&a=doexport'+'&m=excel'+'&anketa=' + srv_meta_anketa_id + fullMeta +hiddenSystem+ export_labels + replaces, {replaces:replaces});
	//return false;
	*/
}
function excelExportChangeDelimit() {
	$('#replace_export_delimit_semicolon').toggle();
	$('#replace_export_delimit_comma').toggle();
    return false;
}

function doSpssExport(data) {
	if (data == 'yes') {
		var input = $("<input>").attr("type", "hidden").attr("name", "exportData").val("1");
		$('#export_spss_form').append($(input));
	} else {
		var input = $("<input>").attr("type", "hidden").attr("name", "exportData").val("0");
		$('#export_spss_form').append($(input));
	}
	$('#export_spss_form').submit();

	/*
	var exportData = ( data == 'yes' ) ? 'exportData&=1' : '';
	var fullMeta = ( $('#fullMeta').is(':checked') ) ? '&fullMeta=1' : ''
	var hiddenSystem = ( $('#hiddenSystem').is(':checked') ) ? '&hiddenSystem=1' : '';
	window.open( 'ajax.php?t=export&a=doexport'+'&m=spss'+'&anketa=' + srv_meta_anketa_id + fullMeta + hiddenSystem+ exportData );
    */
}

function doSAVExport() {
	$('#export_sav_form').submit();
}



// Read a page's GET URL variables and return them as an associative array.
function getUrlVars()
{
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}

function check_valid_variable(variable) {

	var ValidPattern = /^[A-Za-z0-9]*$/;
	var ValidFirstChar = /^[A-Za-z]*$/;
	var result = '';

	for (var i=0; i<variable.length; i++) {
		var chr = variable.charAt(i);

		// prvi znak ne sme biti number
		if (i == 0) {
			if (ValidFirstChar.test(chr) ){
				result = result + chr;
			}
		} else if (ValidPattern.test(chr) ){ // ostali znaki so lahko tudi number
			result = result + chr;
        }
	}

	result = result.substring(0, 15);

	// Dodaten pogoj da ponovimo, ker v nekaterih primerih ostane stevilka na prvem mestu
	if(!ValidFirstChar.test(result.charAt(0))){
		result = check_valid_variable(result);
	}

	return result;
}

function clearDefaultValue(el) {
  if (el.defaultValue==el.value) el.value = ""
}

function showSearch() {
	//$('#searchSurvey').toggle("blind", { direction: 'horizontal', start:  }, 500);
	//$('#searchSurvey').animate({width: 'toggle'});

	$('#searchSurvey').animate({width: 'toggle'});
}

function showSearchb() {
	//$('#searchSurvey').toggle("blind", { direction: 'horizontal', start:  }, 500);
	//$('#searchSurvey').animate({width: 'toggle'});

	$('#searchSurveyb').animate({width: 'toggle'});
}

function executeDrupalSearch() {

	var url = $('#drupal_search_url').val();
    var searchString = $('#searchSurvey').val();

    window.open(url + encodeURIComponent(searchString), '_blank');
}

function showAdvancedSearch(){
	
	if ($('#advancedSearch').is(":visible")) {
		$('#advancedSearch').slideUp('slow');
		$('#searchSettings').find('.minus').removeClass('minus').addClass('plus');
	} 
	else {
		$('#advancedSearch').slideDown('slow');
		$('#searchSettings').find('.plus').removeClass('plus').addClass('minus');
	}
}


function max_stevilo_vnosov() {

	var input = $("input[name=stevilo_vnosov]");
	if ( value = parseInt( input.val() ) ) {

		if (value > 1000)
			value = 1000;

	} else {

		value = 0;

	}

	input.val(value);

}
function link_enable_addvance (what) {
	// najprej aktiviramo telefonsko ali e-mail anketo če še ni, nato pa redirektamo
	$.redirect('ajax.php?a=enable_addvance', {anketa:srv_meta_anketa_id, what:what});


}

function toggle_standardne_besede () {

	if ($('.standardne_besede').css('display') == 'none') {
		$('input[name=std_besede]').attr('checked', true);
		$('.standardne_besede').show();
	} else {
		$('input[name=std_besede]').attr('checked', false);
		$('.standardne_besede').hide();
	}
}

function dostopActiveShowAll(show_hide) {
	if (show_hide == 'true') {
		$("#dostop_active_show_1").hide();
		$("#dostop_active_show_2").show();
		/*$("div[name=dostop_active_uid]").each(function(){
			$(this).css('display', 'block');
		});*/

		$('#dostop_users_list').load('ajax.php?a=dostop_active_show_all', {show_all:1, anketa:srv_meta_anketa_id});
	}
	else {
		$("#dostop_active_show_1").show();
		$("#dostop_active_show_2").hide();
		/*$("div[name=dostop_active_uid] input:not(:checked)").each(function(){
			$(this).parent().css('display', 'none');
		});*/

		$('#dostop_users_list').load('ajax.php?a=dostop_active_show_all', {show_all:0, anketa:srv_meta_anketa_id});
	}
}

function dostopPassiveShowAll(show_hide) {
	if (show_hide == 'true') {
		$("#dostop_passive_show_1").hide();
		$("#dostop_passive_show_2").show();
		$("div[name=dostop_passive_uid]").each(function(){
			$(this).css('display', 'block');
		});
	} else {
		$("#dostop_passive_show_1").show();
		$("#dostop_passive_show_2").hide();
		$("div[name=dostop_passive_uid] input:not(:checked)").each(function(){
			$(this).parent().css('display', 'none');
		});
	}
}

function dostopNoteToggle () {

    if($('#addusers_note_checkbox').is(':checked')){
        $('#addusers_note').show();
    }
    else{
        $('#addusers_note').hide();
    }
}

// Ajax klic za dodajanje dostopa in posiljanje obvestila
function dostopAddAccess () {

    var addusers = $('#addusers').val();
    
    var addusers_note = '';
    if($('#addusers_note_checkbox').is(':checked')){
        addusers_note = $('#addusers_note').val();
    }
    
    // Popup z rezultatom (uspesno ali neuspesno dodajanje dostopa)
    $('#fade').fadeTo('slow', 1);
    $('#popup_note').html('').fadeIn('slow');
    $("#popup_note").load('ajax.php?a=add_survey_dostop_popup', {addusers:addusers, addusers_note:addusers_note, anketa:srv_meta_anketa_id}, function(){

        // Refresh vsebine v ozadju
        $("#globalSetingsList").load('ajax.php?a=refresh_dostop_settings', {anketa:srv_meta_anketa_id});
    });
}
function dostopAddAccessPopupClose(){   
    $('#popup_note').fadeOut('slow').html('');
    $('#fade').fadeOut('slow');
}

function comments_admin_toggle (type) {
	if ( $('#comments_admin'+type).attr('admin_on') == 'true' ) {
		comments_admin_off(type);
	} else {
		comments_admin_on(type);
	}
}

function comments_admin_on (type) {

	$('#comments_admin'+type).attr('admin_on', 'true');
	$('#comments_admin'+type).attr('checked', true);
	if (type == 1) {
		$('select[name=survey_comment]').val('3');
		$('select[name=survey_comment_viewadminonly]').val('3');
		//$('input#survey_comment_showalways_0').attr('checked', true);
	} else {
		$('select[name=question_note_view]').val('3');
		$('select[name=question_note_write]').val('0');

		$('select[name=question_comment]').val('3');
		$('select[name=question_comment_viewadminonly]').val('3');
	}
}

function comments_admin_off (type) {

	$('#comments_admin'+type).attr('admin_on', 'false');
	$('#comments_admin'+type).attr('checked', false);
	if (type == 1) {
		$('select[name=survey_comment]').val('');
		$('select[name=survey_comment_viewadminonly]').val('3');
		//$('input#survey_comment_showalways_0').attr('checked', true);
	} else {
		$('select[name=question_note_view]').val('');
		$('select[name=question_note_write]').val('');

		$('select[name=question_comment]').val('');
		$('select[name=question_comment_viewadminonly]').val('4');
	}
}

function check_comments_admin (type) {
	if (type == 1) {
		if (
				$('select[name=survey_comment]').val() != '' /*&&
				$('select[name=survey_comment_viewadminonly]').val() == '3' /*&&
				$('input#survey_comment_showalways_0').attr('checked') == 'checked'*/
			)
				return true;
			else
				return false;
	} else {
		if (
				/*$('select[name=question_note_view]').val() == '3' &&
				$('select[name=question_note_write]').val() == '0' &&*/

				$('select[name=question_comment]').val() != '' /*&&
				$('select[name=question_comment_viewadminonly]').val() == '3'*/
			)
				return true;
			else
				return false;
	}
}

function check_comments_admin_off (type) {
	if (type == 1) {
		if (
				$('select[name=survey_comment]').val() == '' /*&&
				$('select[name=survey_comment_viewadminonly]').val() == '4' &&
				$('input#survey_comment_showalways_0').attr('checked') == 'checked'*/
			)
				return true;
			else
				return false;
	} else {
		if (
				/*$('select[name=question_note_view]').val() == '' &&
				$('select[name=question_note_write]').val() == '' &&*/

				$('select[name=question_comment]').val() == '' /*&&
				$('select[name=question_comment_viewadminonly]').val() == '4'*/
			)
				return true;
			else
				return false;
	}
}

function comments_resp_toggle (type) {

	// Komentarji respondentov na vprasanje
	if (type == 1) {
		if ( $('#comments_resp').attr('resp_on') == 'true' ) {
			comments_resp_off();
		} else {
			comments_resp_on();
		}
	}
	// Komentarji respondentov na anketo
	else {
		if ( $('#comments_resp2').attr('resp_on') == 'true' ) {
			$('#comments_resp2').attr('resp_on', 'false');
			$('#comments_resp2').attr('checked', false);

			$('select[name=survey_comment_resp]').val('');
			$('select[name=survey_comment_viewadminonly_resp]').val('4');
		} else {
			$('#comments_resp2').attr('resp_on', 'true');
			$('#comments_resp2').attr('checked', true);

			$('select[name=survey_comment_resp]').val('4');
			$('select[name=survey_comment_viewadminonly_resp]').val('4');
		}
	}
}

function comments_resp_on () {

	$('#comments_resp').attr('resp_on', 'true');
	$('#comments_resp').attr('checked', true);

	$('input#question_resp_comment_1').attr('checked', true);
	$('select[name=question_resp_comment_viewadminonly]').val('3');
	$('input#question_resp_comment_show_open_0').attr('checked', true);

}

function comments_resp_off () {

	$('#comments_resp').attr('resp_on', 'false');
	$('#comments_resp').attr('checked', false);

	$('input#question_resp_comment_0').attr('checked', true);
	$('select[name=question_resp_comment_viewadminonly]').val('');
	$('input#question_resp_comment_show_open_0').attr('checked', true);

}

function check_comments_resp (type) {

	// Komentarji respondentov na vprasanje
	if (type == 1) {
		if (
			$('input#question_resp_comment_1').attr('checked') == 'checked' &&
			$('select[name=question_resp_comment_viewadminonly]').val() != '' /*&&
			$('input#question_resp_comment_show_open_0').attr('checked') == 'checked'*/
		)
			return true;
		else
			return false;
	}
	// Komentarji respondentov na anketo
	else {
		if ($('select[name=survey_comment_resp]').val() == '')
			return false;
		else
			return true;
	}
}

function check_comments_resp_off () {

	if (
		$('input#question_resp_comment_0').attr('checked') == 'checked' &&
		$('select[name=question_resp_comment_viewadminonly]').val() == '' /*&&
		$('input#question_resp_comment_show_open_0').attr('checked') == 'checked'*/
	)
		return true;
	else
		return false;
}


function testiranje_settings () {

	if ( $('#question_resp_comment_0').attr('checked') == 'checked' ) {
		$('.question_resp_comment').hide();
	} else {
		$('.question_resp_comment').show();

		if ( $('#question_resp_comment_inicialke_0').attr('checked') == 'checked' ) {
			$('.question_resp_comment_inicialke').hide();
		} else {
			$('.question_resp_comment_inicialke').show();
		}

	}

}

// Brisanje testnih podatkov
function delete_test_data () {

    if (confirm(lang['srv_delete_testdata_warning'])) {
        window.location.href = 'index.php?anketa='+srv_meta_anketa_id+'&a=testiranje&m=testnipodatki&delete_testdata=1';
    }
}

function archivePopup() {
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow').draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
    $('#fullscreen').load('ajax.php?a=archivePopup', {anketa:srv_meta_anketa_id});
}
function archivePopupView() {
	$('#fullscreen').fadeOut('slow').html('');
	window.location = 'index.php?anketa='+srv_meta_anketa_id+'&a=arhivi';
}
function archivePopupClose() {
	$('#fullscreen').fadeOut('slow').html('');
    $('#fade').fadeOut('slow');
}

function add_to_library(anketa,where) {
	$.post('ajax.php?a=add_to_library', {anketa:anketa, where:where});
}

function create_archive_survey(anketa, msg) {
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow').draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
    $('#fullscreen').append('<div id="survey_archive_note">'+msg+'</div>');
	$.post('index.php?anketa='+anketa+'&a=backup_create', {intro_opomba: $('#intro_opomba').val()},
		function() {
			window.location.reload();
		});
	return false;
}

function create_archive_survey_data(anketa, msg) {
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow').draggable({delay:100, cancel: 'input, textarea, select, .buttonwrapper'});
    $('#fullscreen').append('<div id="survey_archive_note">'+msg+'</div>');
	$.post('ajax.php?anketa='+anketa+'&a=backup_data&data=true', {intro_opomba: $('#intro_opomba').val()},
		function() {
			window.location.reload();
		});
	return false;
}

function submitSurveyDuration() {
	var durationType = 1;
	var durationStarts = $("#startsManual1").val();
	var durationExpire = $("#expireManual1").val();

	var voteCountLimitType = $("input[name=vote_limit]:checked").val();
	var voteCountValue = $("#vote_count").val();

	$.post('ajax.php?a=anketa_save_activation', {anketa:srv_meta_anketa_id, durationType:durationType, durationStarts:durationStarts, durationExpire:durationExpire, voteCountLimitType:voteCountLimitType, voteCountValue:voteCountValue}, function() {
		window.location.reload();
		//window.location = 'index.php?anketa='+srv_meta_anketa_id+'&a=vabila';
	});
}
function diag_display_if() {
	$.post('ajax.php?t=branching&a=check_pogoji&izpis=long', {anketa: srv_meta_anketa_id}, function (data) {
        $('#fade').fadeIn("slow");
        $('#check_pogoji').html(data).fadeIn("slow");
	});

}
function changeDataIcons() {
	var dataIcons_quick_view = $('#dataIcons_quick_view').is(':checked') ? '1' : '0';
	var dataIcons_write = $('#dataIcons_write').is(':checked') ? '1' : '0';
	var dataIcons_edit = $('#dataIcons_edit').is(':checked') ? '1' : '0';
	var dataIcons_labels = $('#dataIcons_labels').is(':checked') ? '1' : '0';
	var dataIcons_multiple = $('#dataIcons_multiple').is(':checked') ? '1' : '0';
	$.post('ajax.php?t=dataSettingProfile&a=changeDataIcons', {anketa: srv_meta_anketa_id, dataIcons_write:dataIcons_write,dataIcons_edit:dataIcons_edit, dataIcons_quick_view:dataIcons_quick_view,dataIcons_labels:dataIcons_labels, dataIcons_multiple:dataIcons_multiple}, function (data) {
		window.location.reload();
	});
}

function surveyBaseSettingRadio(what,foreceReload) {

	// Posebej obravnavamo dostop brez kode, ker je kombinacija checkboxa in radia
	if(what == 'usercode_skip'){
		// Dostop brez - vsi ali samo avtor
		if($('#usercode_skip_0').is(":checked")){
			var value = $("input[name="+what+"]:checked").val();
			if(value == null)
				value = '1';
		}
		// Ni dostopa brez kode
		else{
			var value = '0';
		}
	}
	else{
		var value = $("input[name="+what+"]:checked").val();
	}

	if(foreceReload == true) {
		var foreceReload = true;
	} else {
		var foreceReload = false;
	}

	$.post('ajax.php?t=surveyBaseSetting&a=radio', {anketa: srv_meta_anketa_id, what:what, value:value}, function (data) {
		data = jQuery.parseJSON(data);
		if (data.error == 0) {
			//if(data.action == 0) {
			if( foreceReload == true ) {
				window.location.reload();
			//} else if (data.action == 1) {
			}
		} else {
			genericAlertPopup('alert_parameter_datamsg',data.msg);
		}
	});
}

function surveyBaseSettingText(what,refresh) {
	var value = '';
	if ( $('#'+what).length ) {
		value = $('#'+what).val();
	} else if ($('[name='+what+']').length) {
		value = $('[name='+what+']').val();
	} else {
		genericAlertPopup('alert_save_error');
		return false;
	}

	$.post('ajax.php?t=surveyBaseSetting&a=text', {anketa: srv_meta_anketa_id, what:what, value:value, refresh:refresh}, function (data) {
		data = jQuery.parseJSON(data);
		if (data.error == 0) {
			if(data.action == 0 && refresh == true) {
				window.location.reload();
			} else if (data.action == 1) {
				// todo show save window
			}
		} else {
			genericAlertPopup('alert_parameter_datamsg',data.msg);
		}
	});
}

function alert_custom(type, uid) {

	$('#fade').fadeTo('slow', 1);
	$('#vrednost_edit').show().load('ajax.php?a=alert_custom', {anketa:srv_meta_anketa_id, type:type, uid:uid}, function () {
		if ($("#text")) create_editor('text', false);
	});

}

function exportChangeCheckbox (name) {
	
	$.post('ajax.php?a=exportChangeCheckbox', {anketa:srv_meta_anketa_id, name:name});
}
function exportChangeRadio (id,name) {

	var value = $("input[name="+name+"]:checked").val();
	$.post('ajax.php?a=exportChangeRadio', {anketa:srv_meta_anketa_id, id:id, name:name, value:value});
}
function setExpirePermanent() {
	makePermanent = $("#expirePermanent").is(':checked');
	$.post('ajax.php?a=setExpirePermanent', {anketa:srv_meta_anketa_id, makePermanent:makePermanent}, function (data) {
		data = jQuery.parseJSON(data);
		if (data.permanent == '0') {
			// pokažemo koledarcek
			$("#expire_img_manual1").show();
		} else {
			// skrijemo koledarcek
			$("#expire_img_manual1").hide();
		}
		$("#expireManual1").val(data.expire);
	});
}


function changeDoCMSUserFilterCheckbox() {
	var checked = $("#doCMSUserFilterCheckbox").is(":checked");
	$.post('ajax.php?a=doCMSUserFilterCheckbox', {anketa: srv_meta_anketa_id,meta_akcija: srv_meta_akcija, podstran: srv_meta_podstran, checked:checked}, function(response) {
		return reloadData('status');
	});

}

function anketa_restore(anketa) {

	if ( confirm('?') ) {

		$.post('ajax.php?a=anketa_restore', {id: anketa}, function () {

			window.location.href = 'index.php?anketa='+anketa;

		})

	}

}

function data_restore(anketa) {

	if ( confirm('?') ) {

		$.post('ajax.php?a=data_restore', {id: anketa}, function () {

			window.location.href = 'index.php?anketa='+anketa;

		})

	}

}

function deleteSurveyDataFile(note) {
	if (confirm(note)) {
		$.post('ajax.php?a=deleteSurveyDataFile', {anketa:srv_meta_anketa_id}, function (result) {genericAlertPopup('alert_parameter_response',response)});
	}
}
function userGlobalSettingChange(what) {

	var type = $(what).attr('type');

	if (type == 'checkbox') {
		var name = $(what).attr('name');
		var value = ($(what).attr('value') !== undefined && $(what).attr('value') !== 'on') ? $(what).attr('value') : '1';
		var state = $(what).is(':checked') ? value : '0';
		$.post('ajax.php?t=globalUserSettings', {name:name, value:state}, function (data) {});
	} else {
		genericAlertPopup('alert_userGlobalSettingChange',type);
	}
}
function changeSurveyLock(what) {
	var value = ($(what).attr('value') !== undefined && $(what).attr('value') !== 'on') ? $(what).attr('value') : '1';
	var state = $(what).is(':checked') ? value : '0';
	$("#div_lock_survey").load('ajax.php?t=changeSurveyLock', {anketa: srv_meta_anketa_id, name:'lockSurvey', value:state});
}

$.fn.blink = function(opts) {
	   // allows $elem.blink('stop');
	   if (opts == 'stop') {
	     // sets 'blinkStop' on element to true, stops animations,
	     // and shows the element.  Return this for chaining.
	     return this.data('blinkStop', true).stop(true, true).show();
	   }

	   // we aren't stopping, so lets set the blinkStop to false,
	   this.data('blinkStop', false);

	   // load up some default options, and allow overriding them:
	   opts = $.extend({}, {
	     fadeIn: 100,
	     fadeOut: 300,
	     pauseShow: 5000
	   }, opts || {} );

	   function doFadeOut($elem) {
	     $elem = $elem || $(this); // so it can be called as a callback too
	     if ($elem.data('blinkStop')) return;
	     $elem.delay(opts.pauseShow).fadeOut(opts.fadeOut, doFadeIn);
	   }
	   function doFadeIn($elem) {
	     $elem = $elem || $(this);
	     if ($elem.data('blinkStop')) return;
	     $elem.fadeIn(opts.fadeIn, doFadeOut);
	   }
	   doFadeOut(this);
	   return this;
	 };

function dostop_admin (remove) {

	$('#request_help_content').load('ajax.php?a=dostop_admin', {anketa: srv_meta_anketa_id, remove: remove}, function (data) {
		$('#request_help_content').addClass('displayBlock').mouseover(function() {$(this).removeClass('displayBlock')});
	});

	return false;
}

function setDataView(what,value) {
	$.post('ajax.php?t=setDataView', {anketa: srv_meta_anketa_id, what:what, value:value}, function() {
		window.location.reload(); return;
	});
}
function testiranje_preview_settings () {

    $('#fade').fadeTo('slow', 1);
	$('#vrednost_edit').load('ajax.php?a=testiranje_preview_settings', {anketa: srv_meta_anketa_id}).show();
}

function testiranje_preview_settings_save () {

    $('#vrednost_edit').hide();
    $('#fade').fadeOut('slow');

	$.post('ajax.php?a=testiranje_preview_settings_save', $('form[name="testiranje_preview_settings"]').serialize(), function () {
		/*window.location = 'index.php?anketa=' + srv_meta_anketa_id + '&a=testiranje';*/
		location.reload();
	});
}

function showTestSurveySMTP()
{
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=showTestSurveySMTP', $('form[name="settingsanketa_'+srv_meta_anketa_id+'"]').serialize(), function() {});
    
    return false;
}

function showSurveyUrlLinks(podstran, m)
{
	$('#fade').fadeTo('slow', 1);
	$('#fullscreen').html('').fadeIn('slow');
	$("#fullscreen").load('ajax.php?t=SurveyUrlLinks&a=showLinks',
	{
		anketa:srv_meta_anketa_id,
		podstran: podstran,
		m: m
	});
	return false;
}

function addSurveyUrlLink(podstran ,m)
{
	$("#fullscreen").load('ajax.php?t=SurveyUrlLinks&a=addLink',
			{
			anketa:srv_meta_anketa_id,
			podstran: podstran,
			m:m
			 });
	return false;
}

function deleteSurveyUrlLinks(anketa, hash, podstran, m)
{
	if (confirm(lang['srv_urlLinks_delete'])) {
		$("#fullscreen").load('ajax.php?t=SurveyUrlLinks&a=deleteLink',
			{
			anketa:anketa,
			hash: hash,
			podstran: podstran,
			m:m
			 });
	}
	return false;
}

function changeParaAnalysisCbx(what, reverse) {
    
    var value = $(what).is(':checked');   
    if (reverse){
		value = !value;
	}  
    var what_id = $(what).attr('id');
    
    $.post('ajax.php?t=ParaAnalysis&a=setCbx', {anketa: srv_meta_anketa_id,value:value,what:what_id}, function () {
		window.location.reload();
    });
    
	return false;
}
function changeParaAnalysisSelect(what) {
    
    var value = $(what).val();
	var what_id = $(what).attr('id');
    
    $.post('ajax.php?t=ParaAnalysis&a=setValue', {anketa: srv_meta_anketa_id,value:value,what:what_id}, function () {
		window.location.reload();
    });
    
	return false;
}

function changeParaGraphFilter(){

	var status = $('input[name=paraGraph_filter_status]:checked').val();

	if($('#paraGraph_filter_pc').is(':checked'))
		var pc = 1;
	else
		var pc = 0;

	if($('#paraGraph_filter_tablet').is(':checked'))
		var tablet = 1;
	else
		var tablet = 0;

	if($('#paraGraph_filter_mobi').is(':checked'))
		var mobi = 1;
	else
		var mobi = 0;

	if($('#paraGraph_filter_robot').is(':checked'))
		var robot = 1;
	else
		var robot = 0;

	window.location = 'index.php?anketa='+srv_meta_anketa_id+'&a=para_graph&status='+status+'&pc='+pc+'&tablet='+tablet+'&mobi='+mobi+'&robot='+robot;
}

function changeUsableRespSetting(what){

	var what_id = $(what).attr('id');

	if(what_id == 'show_with_zero' || what_id == 'show_with_text' || what_id == 'show_with_other' || what_id == 'show_details' || what_id == 'show_calculations'){
		if($(what).is(':checked'))
			var value = true;
		else
			var value = false;
	}
	else{
		var value = $(what).val();
	}

	$.post('ajax.php?t=surveyUsableResp&a=changeSetting', {anketa: srv_meta_anketa_id, value:value, what:what_id}, function () {
		window.location.reload();
	});

	return false;
}

function cookie_alert() {

	function cookie_alert_do () {
		if ( $('input[name=cookie]:checked').val() != '-1' ) {
			$('#cookie_alert').show();
		} else {
			$('#cookie_alert').hide();
		}
	};

	$('input[name=cookie]').on('change', cookie_alert_do );
	cookie_alert_do();
}

/* Napredni moduli -> vklop/izklop (po novem v urejanje->nastavitve) */
function toggleAdvancedModule(what, reload){
    if(typeof reload == 'undefined')
        reload = 1;

	if($('#advanced_module_'+what).is(':checked')){
		if(what == 'user_from_cms')
			var value = 2;
		else
			var value = 1;
	}
	else{
		var value = 0;
	}

	$('#globalSettingsInner').load('ajax.php?a=toggle_advanced_module', {what:what, value:value, anketa: srv_meta_anketa_id}, function(){
        if(reload == 1)
		      window.location.reload();
	});
}

function toggleDataCheckboxes(podstran){

	if($('#dataSettingsCheckboxes').css('display') == 'none'){
		$("#dataSettingsCheckboxes").slideDown("slow");
		var showSettings = 1;

		$(".dropdown_blue").switchClass("dropdown_blue", "dropup_blue");
	}
	else{
		$("#dataSettingsCheckboxes").slideUp("slow");
		var showSettings = 0;

		$(".dropup_blue").switchClass("dropup_blue", "dropdown_blue");
	}

	if(podstran == 'data'){
		$.post('ajax.php?t=dataSettingProfile&a=changeDataIconsSettings', {anketa: srv_meta_anketa_id, dataIcons_settings:showSettings});
	} else if (podstran == 'paraAnalysisGraph'){
		$.post('ajax.php?t=dataSettingProfile&a=changeParaAnalysisGraphSettings', {anketa: srv_meta_anketa_id, paraAnalysisGraph_settings:showSettings});
	}
	else{
		$.post('ajax.php?t=dataSettingProfile&a=changeUsabilityIconsSettings', {anketa: srv_meta_anketa_id, usabilityIcons_settings:showSettings});
	}
}

// Iskanje po tabeli s podatki
function data_search_filter(){

	var value = $("#data_search_value").val();

	$.post('ajax.php?t=displayData&a=set_data_search_filter', {anketa:srv_meta_anketa_id, value:value}, function() {
		window.location.reload();
	});
}

// Za tabele z fiksnim headerjem
/** FLOAT HEADER function for tables and div **/
/** container:  class="persist-area"
 * header2flow: class="persist-header"
 */
function UpdateTableHeaders() {
    $(".persist-area").each(function() {

        var el             = $(this),
            offset         = el.offset(),
            scrollTop      = $(window).scrollTop(),
            floatingHeader = $(".floatingHeader", this)

        if ((scrollTop > offset.top) && (scrollTop < offset.top + el.height())) {
            floatingHeader.css({
             "visibility": "visible"
            });
        } else {
            floatingHeader.css({
             "visibility": "hidden"
            });
        };
    });
 }

 // DOM Ready
 $(function() {
     var $floatingHeader = $(".persist-header", this).clone();

     $floatingHeader.children().width(function (i, val) {
     	return $(".persist-header").children().eq(i).width();
     });

     $floatingHeader.css("width", $(".persist-header", this).width()).addClass("floatingHeader");
     $(".persist-header", this).before($floatingHeader);

    $(window)
     .scroll(UpdateTableHeaders)
     .trigger("scroll");

    // V kolikor gre za modul hierarhija, potem skrijemo podatke o knjižnici ali o prevzeti anketi
    if($('[name="izberi-anketo"]')){
        $('[name="izberi-anketo"]').on('change', function(){
        	$('#hierarhija-knjiznica').html('').hide();
            $('#hierarhija-prevzeta').hide();
		});
	}
   


 });
 
 //uporablja se tudi pri analizah urejanja - class.SurveyEditsAnalysis.php
function diagnosticsChooseDate(){
    var selected = $("#diagnostics_date_selected").find(":selected");
    var data = $("#diagnostics_date_selected").find(":selected").val();
    if(data == '99date'){
        $("#from").prop('disabled', false);
        $("#to").prop('disabled', false);
        Calendar.setup({
            inputField  : "from",
            ifFormat    : "%Y-%m-%d %H-%M",
            button      : "from_img",
            singleClick : true
        });
        Calendar.setup({
            inputField  : "to",
            ifFormat    : "%Y-%m-%d %H-%M",
            button      : "to_img",
            singleClick : true
        });
    }else{
        $("#from").prop('disabled', true);
        $("#from").val('');
        $("#to").prop('disabled', true);
        $("#to").val('');
        $("#diagnostics_form").submit();
    }

}
function diagnosticsParadataChooseDate(){

	Calendar.setup({
		inputField  : "from",
		ifFormat    : "%d.%m.%Y",
		button      : "from_img",
		singleClick : true
	});
	Calendar.setup({
		inputField  : "to",
		ifFormat    : "%d.%m.%Y",
		button      : "to_img",
		singleClick : true
	});
}
function changeSelectOption(){
    //console.log($("#diagnostics_date_selected").val());
    //console.log($("#diagnostics_date_selected").find(":selected").val());
    $("#diagnostics_date_selected").find(":selected").prop('selected',false);
    //$("#diagnostics_date_selected option").find("[value=99data]").prop('selected',true);
    $("#option_99date").prop('selected',true);
    //console.log($("#diagnostics_date_selected").find(":selected").val());
    diagnosticsChooseDate();
}
 /** END FLOAT HEADER function for tables and div **/

/* START FUNKCIJA ZA UREJANJE STANDARDNIH BESED*/
function inline_jezik_edit(id_value){
 
 	$('#fade').fadeTo('slow', 1);
	
	var id =  $("#"+id_value);

	id.siblings('.sb-edit').hide();

	id.replaceWith('<div class="fixed-position"><div id="vrednost_edit">'+
	
	'<br><textarea name="'+id_value+'" id="'+id_value+'">'+id.html()+'</textarea>'+
	
	// Hidden textarea kamor shranimo staro vrednost, da jo lahko ponastavimo
	'<textarea name="old_val_'+id_value+'" style="visibility:hidden; display:none;">'+id.html()+'</textarea><br />'+	

    // Gumb shrani
	'<span class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inline_jezik_save(\''+id_value+'\');">'+
	'<span>'+lang['save']+'</span>'+
	'</a></span>'+	
	
	// Gumb zapri
	'<span class="buttonwrapper floatRight spaceRight"><a class="ovalbutton ovalbutton_gray" href="#" onclick="inline_jezik_reset(\''+id_value+'\');">'+
	'<span>'+lang['srv_zapri']+'</span>'+
    '</a></span><br><br>'+	
	
	'</div></div>');

	create_editor(id_value);
}

function inline_jezik_reset(id_value){
	
	$('#fade').fadeOut('slow');
	
	var id = $('#'+id_value);
	var old_val = $("textarea[name=old_val_"+id_value+"]").val();

    var par = id.closest('.fixed-position');
	
    CKEDITOR.instances[id_value].destroy();
    
	par.replaceWith('<div contentEditable="true" class="standardna-beseda-urejanje" name="'+id_value+'" id="'+id_value+'">'+old_val+'</div>'); 
    $('textarea#polje_'+id_value).html(old_val);  
	id.siblings('.sb-edit').hide();
	
	// Na novo inicializiramo on focus
	inline_jezik_hover();
	
    //postavimo se tam, kjer smo urejalejali textarea
    var t = $('#polje_'+id_value).offset().top;
    $('html, body').animate({
        scrollTop: t
    }, 100);
}

function inline_jezik_save(id_value){
	
    var id = $('#'+id_value);
    var par = id.closest('.fixed-position');
	
    CKEDITOR.instances[id_value].destroy();
    
	par.replaceWith('<div contentEditable="true" class="standardna-beseda-urejanje" name="'+id_value+'" id="'+id_value+'">'+id.val()+'</div>'); 
    $('textarea#polje_'+id_value).html(id.val());  
	id.siblings('.sb-edit').hide();
	
	// Na novo inicializiramo on focus
	inline_jezik_hover();
	
    //postavimo se tam, kjer smo urejalejali textarea
    var t = $('#polje_'+id_value).offset().top;
    $('html, body').animate({
        scrollTop: t
    }, 100);
	//alert("form[name=settingsanketa_"+$("input[name=anketa]").val()+"]");
	$("form[name=settingsanketa_"+$("input[name=anketa]").val()+"]").submit();
}

function inline_jezik_close(id_value){
	
    var id = $('#'+id_value);
    var par = id.closest('.fixed-position');
	
    CKEDITOR.instances[id_value].destroy();
    
	par.replaceWith('<div contentEditable="true" class="standardna-beseda-urejanje" name="'+id_value+'" id="'+id_value+'">'+id.val()+'</div>'); 
    $('textarea#polje_'+id_value).html(id.val());  
	id.siblings('.sb-edit').hide();
	
	// Na novo inicializiramo on focus
	inline_jezik_hover();
	
    //postavimo se tam, kjer smo urejalejali textarea
    var t = $('#polje_'+id_value).offset().top;
    $('html, body').animate({
        scrollTop: t
    }, 100);
}

function inline_jezik_hover() {
    $("div.standardna-beseda-urejanje").on({
        focus: function() {
            var id = $(this).attr('id');
            $(this).siblings('.sb-edit').show();
			
			$(this).parent().addClass('sb-editing');
        },
        blur: function () {
            var id = $(this).attr('id');
            var value = $(this).html();
            $('#polje_'+id).html(value);
            if ( !$(this).siblings().hasClass('.sb-edit'))  {
                window.setTimeout( function() {
                    $('#'+id).siblings('.sb-edit').hide();
                }, 210 );
            }

			$(this).parent().removeClass('sb-editing');
        }
    });
}
function ponastavi_prevod(id){

    $.post('ajax.php?a=editanketasettings', {
        anketa: $('[name="anketa"]').val(),
        extra_translations: true,
        lang: id,
        remove_lang: 1,
        data: $('form').serialize()
    }).success(function(){
        window.location.reload();
    });
}
/* END STANDARDNE BESEDE */

// Generiranje API kredenc
function generate_API_key(){

	$('#fade').fadeTo('slow', 1);
	$("#unread_notifications").load('ajax.php?a=generate_API_key', {anketa:srv_meta_anketa_id}, function(){
		$('#unread_notifications').show();
	});
}

function close_API_window(){

	$('#fade').fadeOut('slow');
	$('#unread_notifications').fadeOut('slow');
}


// Nastavitve modula za chat
function chat_save_settings() {

	var code = $("#chat_code").val();
	var chat_type = $('input[name=chat_type]:checked').val();

	$.post('ajax.php?t=chat&a=save_settings', {anketa: srv_meta_anketa_id, code:code, chat_type:chat_type}, function(){
		show_success_save();
	});
}


// Nastavitve modula za kviz
function quiz_save_settings() {

	var results = $("input[name=quiz_results]:checked").val();
	var results_chart = $("input[name=quiz_results_chart]:checked").val();

	$.post('ajax.php?t=quiz&a=save_settings', {anketa: srv_meta_anketa_id, results:results, results_chart:results_chart}, function(){
		show_success_save();
	});
}


// Nastavitve modula napredni parapodatki
function advanced_paradata_save_settings() {

	var collect_post_time = $("input[name=collect_post_time]:checked").val();

	$.post('ajax.php?t=advanced_paradata&a=save_settings', {anketa: srv_meta_anketa_id, collect_post_time:collect_post_time}, function(){
		show_success_save();
	});
}
// brisanje vseh podatkov ankete pri naprednih parapodatkih
function advanced_paradata_data_delete(){		

    if(confirm("Are you sure?")){
        $.post('ajax.php?t=advanced_paradata&=advanced_paradata&a=logDataDelete', {anketa: srv_meta_anketa_id}, function(){
            location.reload();
        });
    }
}


// Nastavitve modula za panel
function panel_save_settings() {

	var user_id_name = $('input[name=user_id_name]').val();
	var status_name = $('input[name=status_name]').val();
	var status_default = $('input[name=status_default]').val();
	var url = $('input[name=url]').val();

	$("#globalSettingsInner").load('ajax.php?t=panel&a=save_settings', {anketa: srv_meta_anketa_id, user_id_name:user_id_name, status_name:status_name, status_default:status_default, url:url}, function(){
		show_success_save();
	});
}


//za predogled radio/checkbox tipov vprasanj
var radio_list = new Array(); // seznam obkljukanih radio buttnov (kamor spadajo tudi multigrid radii)
var radio_vals = new Array(); // value za skupino radio buttnov iz radio_list (kater je dejansko obkljukan)
// preveri, ce je bil radio obkljukan in v primeru, da smo se enkrat kliknili nanj, ga odkljuka
function checkChecked (radio) {
    // najprej preverimo ce je trenutni radio checked (in ga v tem primeru odkljuka)
    for (var i=0; i<radio_list.length; i++) {
        if (radio_list[i] == radio.name && radio_vals[i] == radio.value) {
            radio_list.splice(i, 1);
            radio_vals.splice(i, 1);
            radio.checked = false;
            return;
        }
    }

    // ni checked, torej ga bomo dodali na seznam
    // najprej preverimo ce je bil ze izbran kater drug iz skupine
    for (var i=0; i<radio_list.length; i++) {
        if (radio_list[i] == radio.name) {
            radio_vals[i] = radio.value;
            return;
        }
    }

    // checkan je bil prvi v skupini, tko da ga mormo na novo dodat
    radio_list[radio_list.length] = radio.name;
    radio_vals[radio_vals.length] = radio.value;

}

// Nastavi razred parentu da je odkljukan (da lahko odkljukanim textom nastavljamo css)
// mm - multi grid on mobile
function setCheckedClass(element, type, ifId){
    var id = element.value;
	
    if(ifId && type != 'mm') {
        id = ifId;

        if (element.checked) {
            if(type != 16 && type != '6-3-1' && type != '6-3-2')
                $('#vrednost_if_' + id).find('td').removeClass('checked'); //vse ostale checkboxe odstranimo

            if(type == '6-3-1')
                $('#vrednost_if_' + id).find('input:not([name$="_part_2"])').closest('td').removeClass('checked');

            if(type == '6-3-2')
                $('#vrednost_if_' + id).find('input[name$="_part_2"]').closest('td').removeClass('checked');


            $(element).closest('td').addClass('checked');

        }
        else {
            $(element).closest('td').removeClass('checked');
        }
    }
   if(type == 'mm'){
        if(element.checked) {
            $('[for="vrednost_' + ifId + '_grid_' + id + '"]').parent().siblings().removeClass('checked');
            $('[for="vrednost_' + ifId + '_grid_' + id + '"]').parent().addClass('checked');
        }else{
            $('[for="vrednost_' + ifId + '_grid_' + id + '"]').parent().removeClass('checked');
        }
    }
    else {

        if (element.checked) {
            $("#vrednost_if_" + id).addClass('checked');
        }
        else {
            $("#vrednost_if_" + id).removeClass('checked');
        }
    }

	// za radio gumbe se ugasnemo ostale
	if(type == 1){

		var name = $(element).attr('name');
        var idVprasanja = name.substring(9); //dobimo ID vprasanja

		//Image HotSpot: za brisanje obmocja
		//identifier za sliko na katero se veze mapa z obmocji
		var image1 = $('#hotspot_'+idVprasanja+'_image');

		$("input[name="+name+"]").each(function(){
			var loop_id = this.value;
            id = element.value;
			if(loop_id != id){
				$("#vrednost_if_" + loop_id).removeClass('checked');
                $('#spremenljivka_'+idVprasanja+'_vrednost_'+loop_id).closest('td').removeClass('checked');

				//Image HotSpot: brisemo obmocja iz slike
				image1.mapster('set', false, loop_id); //spucaj trenutno obmocje iz slike
				//console.log(loop_id);
			}
		});

	}
}


// spremeni nastavitve za evoli teammeter skupino
function evoli_tm_edit(tm_id, what, value) {
	
	$.post('ajax.php?t=evoliTM&a=teammeter_edit', {tm_id: tm_id, what: what, value: value, anketa:srv_meta_anketa_id});
}

// spremeni oddelek ki ga je oznacil respondent za evoli teammeter skupino
function evoli_tm_change_oddelek(department_id, usr_id) {
	
	$.post('ajax.php?t=evoliTM&a=teammeter_change_oddelek', {department_id: department_id, usr_id: usr_id, anketa:srv_meta_anketa_id});
}

// doda nov oddelek za evoli teammeter skupino
function evoli_tm_add_oddelek(tm_id, oddelek) {
	
	$.post('ajax.php?t=evoliTM&a=teammeter_add_oddelek', {tm_id: tm_id, oddelek: oddelek, anketa:srv_meta_anketa_id});
}

// doda nov oddelek za evoli teammeter skupino
function evoli_tm_settings_add_oddelek(tm_id) {
	
	var oddelek = $("#tm_add_oddelek").val();
	
	$.post('ajax.php?t=evoliTM&a=teammeter_add_oddelek', {tm_id: tm_id, oddelek: oddelek, anketa:srv_meta_anketa_id}, function(){
		window.location.reload();	
	});
}


// Popravimo crte med vprasanji ce imamo blok s horizontalnim izrisom vprasanj
function blockHorizontalLine(spr_id){
	
	$('.spremenljivka.horizontal_block').each(function() {  
		if(!$(this).prev().hasClass('horizontal_block') && !$(this).prev().hasClass('lineOnly') && !$(this).prev().hasClass('tip_5')){
			$(this).before('<div class="spremenljivka lineOnly"></div>');	
		}	
		if(!$(this).next().hasClass('horizontal_block') && !$(this).next().hasClass('clr') && !$(this).next().hasClass('tip_5')){
			$(this).after('<div class="clr"></div>');	
		}
	});	
}

//globalni spremenljivki za elektronski podpis
var podpisposlan = [];
var optionsPodpis = [];

/**
 * Izbrišemo 1ka račun - status v bazi prestavimo na 0
 */
function izbrisi1kaRacun(){
  if (confirm(lang['delete_account_conformation'])) {
		$.post('ajax.php?a=editanketasettings&m=global_user_myProfile', {
			izbrisiRacun: 1
		}).success(function(response){
      window.location.href = "/";
		});
  }
}

/**
 * Shranimo spremembe racuna - ce je spremenil geslo prikazemo popup
 */
function save1kaRacunSettings(){

    // Preverimo, ce gre za popravljanje gesla
    var geslo1 = $("#p1").val();
    var geslo2 = $("#p2").val();

    // Ce ne gre zas popravljanje gesla samo submitamo
    if(geslo1 == 'PRIMERZELODOLGEGAGESLA' && geslo2 == 'PRIMERZELODOLGEGAGESLA'){
        document.settingsanketa.submit();
    }
    // Gesla nista enaka
    else if(geslo1 != geslo2){
        genericAlertPopup('cms_error_password_incorrect');
    }
    // Geslo ni dovolj kompleksno
    else if(!complexPassword(geslo1)){
        genericAlertPopup('password_err_complex');
    }
    // Pri popravljanju gesla ga opozorimo, da bo odjavljen
    else{
        if (confirm(lang['change_account_pass_conformation'])) {
            document.settingsanketa.submit();
        }
    }
}
// Preverjamo ce je geslo dovolj kompleksno
function complexPassword(password){

    // Geslo mora imeti vsaj 8 znakov
    if (password.length < 8) {
        return false;
    }

    // Geslo mora vsebovati vsaj eno stevilko
    var digits = "0123456789";
    if (!stringContains(password, digits)) {
        return false;
    }

    // Geslo mora vsebovati vsaj 1 crko
    var letters = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
    if (!stringContains(password, letters)) {
        return false;
    }

    return true;
}
// Pomozna funkcija, ki preverja, ce string vsebuje dolocene znake
function stringContains(password, allowedChars){
 
    for (i=0; i<password.length; i++){
        var char = password.charAt(i);
        
        if (allowedChars.indexOf(char) >= 0)
            return true;
    }
 
    return false;
}


/**
 * Izbrišemo alternativni email
 *
 * @param id
 */
function izbrisiAlternativniEmail(id){
  if (confirm(lang['delete_alternative_email'])) {
    $.post('ajax.php?a=editanketasettings&m=global_user_myProfile', {
    	izbrisiAlternativniEmail: 1,
      alternativniEmailId: id
    }).success(function(response){
      window.location.reload();
    });
  }
}

function dodajAlternativniEmail(){
	var email = $('#alternativni-email').val();
	$('#alternativni-obvestilo').hide().removeClass('error').removeClass('success');

	$.post('ajax.php?a=editanketasettings&m=global_user_myProfile', {
		'alternative_email': email
	}).success(function(response){

    if(response == 'success') {
    	$('.dodaj-alternativni-email .vnos').hide();
    	var besedilo = lang['login_alternative_emails_success'].replace(/#email#/g, email);
      $('#alternativno-obvestilo').addClass('success').fadeIn('slow').text(besedilo);

      // Ko uporabnik prebere obvestilo osvežimo stran
      setTimeout(function(){
        window.location.reload();
			}, 8000);
    } else {
      $('#alternativno-obvestilo').fadeIn('slow').addClass('error').text(lang['login_alternative_emails_error']).delay(5000).fadeOut('slow');
    }

	});
}

function check_akronim() {
  if ( $('#novaanketa_akronim_1').attr('changed') == '0') {
    $('#novaanketa_akronim_1').val($('#novaanketa_naslov_1').val());
  }

  var max = $('#novaanketa_akronim_1').attr('maxlength');
  var leng = $('#novaanketa_akronim_1').val().length;

  $('#novaanketa_akronim_1_chars').html(leng + ' / '+max);
}


/* START FUNKCIJA ZA UREJANJE zakljucka po deaktivaciji - popup v urejanju zakljucka */
function vprasanje_jezik_edit_zakljucek(id_value){
 
 	/*$('#fade').fadeTo('slow', 1);*/
	
	var id =  $("#"+id_value);

	id.replaceWith('<div class="fixed-position"><div id="vrednost_edit">'+
	
	'<br /><textarea name="'+id_value+'" id="'+id_value+'">'+id.val()+'</textarea>'+
	
	// Hidden textarea kamor shranimo staro vrednost, da jo lahko ponastavimo
	'<textarea name="old_val_'+id_value+'" style="visibility:hidden; display:none;">'+id.val()+'</textarea><br />'+	

	// Gumb shrani
	'<span class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_orange" href="#" onclick="vprasanje_jezik_save_zakljucek(\''+id_value+'\');">'+
	'<span>'+lang['save']+'</span>'+
	'</a></span>'+	
	
	// Gumb zapri
	'<span class="buttonwrapper floatRight spaceRight"><a class="ovalbutton ovalbutton_gray" href="#" onclick="vprasanje_jezik_reset_zakljucek(\''+id_value+'\');">'+
	'<span>'+lang['srv_zapri']+'</span>'+
	'</a></span>'+	
	
	'</div></div>');

	create_editor(id_value);
}

function vprasanje_jezik_reset_zakljucek(id_value){
	
	//$('#fade').fadeOut('slow');
	
	var id = $('#'+id_value);
	var old_val = $("textarea[name=old_val_"+id_value+"]").val();

    var par = id.closest('.fixed-position');
	
    CKEDITOR.instances[id_value].destroy();
	
	par.replaceWith('<textarea name="srvlang_srv_survey_non_active" id="srvlang_srv_survey_non_active" style="width:190px">'+old_val+'</textarea>'); 
		
	// postavimo se na dno strani
	$('html, body').animate({scrollTop:$(document).height()}, 100);
}

function vprasanje_jezik_save_zakljucek(id_value){
	
    var id = $('#'+id_value);
    var par = id.closest('.fixed-position');
	
    CKEDITOR.instances[id_value].destroy();

	par.replaceWith('<textarea name="srvlang_srv_survey_non_active" id="srvlang_srv_survey_non_active"  style="width:190px">'+id.val()+'</textarea>'); 
	
	$('html, body').animate({scrollTop:$(document).height()}, 100, function(){
		vprasanje_save(true);
	});
}

function prikaziGoogle2faKodo(){
	$('#2fa-display').toggle();
}

function aktivirajGoogle2fa(){
	var koda = $('[name="google-2fa-validate"]').val();

	$.post('ajax.php?a=editanketasettings&m=global_user_myProfile', {
		'google_2fa_koda_validate': koda,
	}).success(function(response){

		if(response == 'success') {
				window.location.reload();
		} else {
			$('#google-2fa-bvestilo').fadeIn('slow').show().delay(5000).fadeOut('slow');
		}

	});
}

function ponastaviGoogle2fa(){
	$.post('ajax.php?a=editanketasettings&m=global_user_myProfile', {
		'google_2fa_akcija': 'reset'
	}).success(function(response){

		if(response == 'success') {
			window.location.reload();
		} else {
			$('#google-2fa-bvestilo').fadeIn('slow').show().delay(5000).fadeOut('slow');
		}

	});
}

function prikaziGoogle2faDeaktivacija(){
	$('#2fa-display').toggle();
}

function deaktivirajGoogle2fa(){
	var koda = $('[name="google-2fa-deactivate"]').val();

	$.post('ajax.php?a=editanketasettings&m=global_user_myProfile', {
		'google_2fa_deaktiviraj': koda,
		'google_2fa_akcija': 'deactivate'
	}).success(function(response){

		if(response == 'success') {
			window.location.reload();
		} else {
			$('#google-2fa-bvestilo').fadeIn('slow').show().delay(5000).fadeOut('slow');
		}

	});
}


// Ko je stran naložena
$(document).ready(function(){

	$('#klik-dodaj-email').on('click', function(){
		$('.dodaj-alternativni-email').toggle();
	});


  /**
	 * Funkcije se uporabljajo pri ustvarjanju enkete
   */
  $('#novaanketa_naslov_1').keyup(function(){
    var max = parseInt($(this).attr('maxlength'));
    if($(this).val().length > max){
      $(this).val($(this).val().substr(0, $(this).attr('maxlength')));
    }

    $('#'+$(this).attr('id')+'_chars').html($(this).val().length + ' / '+max);
    check_akronim();

  });

  $('#novaanketa_akronim_1').keyup(function(){
    var max = parseInt($(this).attr('maxlength'));
    if($(this).val().length > max){
      $(this).val($(this).val().substr(0, $(this).attr('maxlength')));
    }
    $('#'+$(this).attr('id')+'_chars').html($(this).val().length + ' / '+max);
  });

  $("#novaanketa_naslov_1").focus();
  $("#novaanketa_opis").keypress(function(e) {
    if (e.keyCode == 13) {
      return false;
    }
  });

  $('#novaanketa_akronim_1, #novaanketa_naslov_1').keypress(function (e) {

    if (e.keyCode == 13) {
      newAnketaBlank();
    }

    if (e.keyCode == 27) {
      window.onkeypress = function() {};
      newAnketaCancle();
    }

  });
});


// Popup za individualno svetovanje
function consultingPopupOpen(){
	
    $('#fade').fadeTo('slow', 1);
	$('#popup_note').html('').fadeIn('slow');
	$("#popup_note").load('ajax.php?a=consulting_popup_open', {anketa: srv_meta_anketa_id});
}
function consultingPopupClose(){
	
    $('#popup_note').fadeOut('slow').html('');
	$('#fade').fadeOut('slow');
}


// Brisanje datoteke iz podatkov
function removeUploadFromData(usr_id, spr_id, code){
    
    $("#fullscreen").load('ajax.php?t=postprocess&a=edit_data_question_upload_delete', {anketa: srv_meta_anketa_id, usr_id: usr_id, spr_id: spr_id, code: code});
}

// Kopiranje URLja za anketo
function CopyToClipboard(copyText){
	var temp_copy = $('<input>').val(copyText).appendTo('body').select()
	document.execCommand('copy')
  }

// Popup - opozorilo na vsa vprašanja
function popupAlertAll(alert_type){

    $('#fade').fadeTo('slow', 1);
    $('#popup_note').html('').fadeIn('slow');
    $("#popup_note").load('ajax.php?a=alert_all_popup', {alert_type:alert_type, anketa:srv_meta_anketa_id});
}

function AlertAllPopupClose(){   
    $('#popup_note').fadeOut('slow').html('');
    $('#fade').fadeOut('slow');
}

//Generičen alert popup
function genericAlertPopup(name, optional_parameter){

	if (optional_parameter === undefined) {
		optional_parameter = "";
	}

    $('#fade').fadeTo('slow', 1);
	$('#popup_note').addClass('popup_orange');
    $('#popup_note').html('').fadeIn('slow');
    $("#popup_note").load('ajax.php?a=genericAlertPopup', {name:name, optional_parameter:optional_parameter});
}

function genericAlertPopupClose(){   
    $('#popup_note').fadeOut('slow').html('');
    $('#fade').fadeOut('slow');
	$('#popup_note').removeClass('popup_orange');
}
