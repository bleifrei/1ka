// preveri - sesteje vrednosti spremenljivke za kalkulacijo
function checkCalculation (spremenljivka, vrednost, grid, tip, calcMissing) {
    
    var value = 0;
    
    if (tip == 1) {     // radio
        
        value = parseInt( $('input[name=vrednost_'+spremenljivka+']:checked').attr('data-calculation') );
        
    } else if (tip == 2) {  // checkbox
        
        var chbx = $('input#spremenljivka_'+spremenljivka+'_vrednost_'+vrednost+'');
        if ( chbx.is(':checked') )
        	value = parseInt( chbx.attr('data-calculation') );
        else
        	value = 0;
        
        // ce ni izbran noben checkbox, potem je tudi kalkulacija -1
        if ( $('input[name^=vrednost_'+spremenljivka+']:checked').length == 0)	value = NaN;
        
    } else if (tip == 3) {        // dropdown
        
        value = parseInt( $('select[name=vrednost_'+spremenljivka+'] option:selected').attr('data-calculation') );
        
    } else if (tip == 6) {      // multigrid
        
        value = parseInt( $('input[name=vrednost_'+vrednost+(grid==1?'_part_2':'')+']:checked').attr('data-calculation') );		// obicen
        if (isNaN(value)) value = parseInt( $('select[name=vrednost_'+vrednost+']').val() );		// roleta
        
	} else if (tip == 8) {
		
		var date = $('input#vrednost_'+spremenljivka).val();
		date = date.split('.');
    	date = new Date(date[2], date[1]-1, date[0]);	// zakaj se mesci zacnejo z 0?
    	value = Math.ceil( date.getTime() / (1000*60*60*24) );
    	
    } else if (tip == 7) {      // number
        
        value = parseFloat( $('input#spremenljivka_'+spremenljivka+'_vrednost_'+(parseInt(grid)+1)).val() );
    
    } else if (tip == 16) {		// multicheckbox
		
		var chbx = $('input#vrednost_'+vrednost+'_grid_'+grid);
		if ( chbx.is(':checked') )
        	value = parseInt( chbx.attr('data-calculation') );
        else
        	value = 0;
        
        // ce ni izbran noben checkbox v vrstici, potem je tudi kalkulacija -1
        if ( $('input[name^=vrednost_'+vrednost+'_grid_]:checked').length == 0)	value = NaN;
        
    } else if (tip == 20) {		// multinumber
		
		value = parseInt( $('input#vrednost_'+vrednost+'_grid_'+grid).val() );

    } else if (tip == 18) {		// vsota
		
		value = parseInt( $('input[name=spremenljivka_'+spremenljivka+'_vrednost_'+vrednost+']').val() );
		
    } else if (tip == 17) {		// ranking
		
		if ( $('#half2_'+spremenljivka).length > 0 ) {		// prestavljanje
			var arr = $('#half2_'+spremenljivka).sortable('toArray');
		} else if ( $('#sortzone_'+spremenljivka).length > 0 ) {	// premikanje
			var arr = $('#sortzone_'+spremenljivka).sortable('toArray');
		} else {											// ostevilcevanje
			var arr = null;
			value = $('input[name=spremenljivka_'+spremenljivka+'_vrednost_'+vrednost+']').val();
		}
		
		// prestavljanje in premikanje
		if (arr != null) {
			for (i=0; i<arr.length; i++) {
				if ( arr[i] == 'ranking_'+vrednost || arr[i] == 'handle_'+vrednost )
					value = i+1;
			}
			
			if (value == 0) value = NaN;
		}
		
    } else if (tip == 22) { // calculation
		
		value = parseFloat( $('input#vrednost_'+spremenljivka).val() );
		
    }

	
	// Odvisno kako imamo nastavljeno v kalkulaciji da obravnavamo missinge
	// ce je kateri od odgovorov missing, se steje kot 0
	if(calcMissing == 1 && isNaN(value)){
		value = 0;	
	}
	// ce je kateri od odgovorov missing, je tudi cela kalkulacija missing (-88)
	else if(isNaN(value)){
		value = NaN;
	}
    

    return value;
}

// preveri number field, cela - stevilo celih mest, dec - stevilo decimalnih mest
function checkNumber (field, cela, dec) {
    
    var val = field.value;
    var okval = '';
    var decimal = false;
    var separator = false;
    
    for (var i=0; i<val.length; i++) {
        
        if (val.charAt(i) != ' ' && val.charAt(i) >= 0 && val.charAt(i) <= 9) {
            
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
            
        } else if (i == 0 && val.charAt(i) == '-') {
            okval = okval + '-';
        }
    }
    
    if (val != okval)
        field.value = okval;
    
}

function convertDate(date){
	
	var dateParts = date.split('.');
	var newDate = dateParts[2] + '-' + dateParts[1] + '-' + dateParts[0];
	
	return newDate;
}

function calcSum (spremenljivka, counter, limit) {
	
	var sum = 0;
	var def = true;
	
	for(var i=0; i<counter; i++){
		var id = 'spremenljivka_' + spremenljivka + '_sestevanec_' + i;
		
		if ( document.getElementById(id).parentNode.style.display != "none" )
			if (parseFloat(document.getElementById(id).value) > 0) {
				sum += parseFloat(document.getElementById(id).value);
				def = false;
			}
	}
	
	var id = 'spremenljivka_' + spremenljivka + '_vsota';
	document.getElementById(id).value = sum;
	$('#'+id).removeClass("def");
	if (def) $('#'+id).addClass("def");
}

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
// mmt - multi grid table on mobile
function setCheckedClass(element, type, ifId){
    var id = element.value;

    
    // Mobile vprasanje
    if(type == 'mm'){
        if(element.checked) {
            $('[for="vrednost_' + ifId + '_grid_' + id + '"]').parent().siblings().removeClass('checked');
            $('[for="vrednost_' + ifId + '_grid_' + id + '"]').parent().addClass('checked');
        }
        else{
            $('[for="vrednost_' + ifId + '_grid_' + id + '"]').parent().removeClass('checked');
        }
    }
    // Mobile tabela
    else if(type == 'mmt'){

        if(element.checked) {
			
			// Posebej obravnavamo missinge
			if($(element).parent().hasClass("missing")){
				$('#vrednost_if_' + ifId).find('.grid_mobile_variable').removeClass('checked');
				$('#vrednost_if_' + ifId).find('.visual-radio-scale').removeClass('checked');
				
				$('#grid_missing_value_' + ifId + '_grid_' + id).parent().parent().addClass('checked');
			}
			else{
				// Pri checkboxu ne ugasnemo ostalih
				if(!$('#vrednost_if_' + ifId).parent().hasClass('checkbox')){
					$('#vrednost_if_' + ifId).find('.grid_mobile_variable').removeClass('checked');
					$('#vrednost_if_' + ifId).find('.visual-radio-scale').removeClass('checked');
				}
		
				$('#vrednost_' + ifId + '_grid_' + id).parent().parent().addClass('checked');
			}  
        }
        else{
			// Posebej obravnavamo missinge
			if($(element).parent().hasClass("missing")){
				$('#grid_missing_value_' + ifId + '_grid_' + id).parent().parent().removeClass('checked');
			}
			else{
				$('#vrednost_' + ifId + '_grid_' + id).parent().parent().removeClass('checked');
			}
        }
    }
    // Mobile dvojna tabela
    else if(type == 'mmt6-3-1' || type == 'mmt6-3-2'){

        // Drugi del dvojne tabele
        if(type == 'mmt6-3-2'){
            if(element.checked) {

                // Pri checkboxu ne ugasnemo ostalih
                if(!$('#vrednost_if_' + ifId).parent().hasClass('checkbox')){
                    $('#vrednost_if_' + ifId).find('.grid_mobile_variables.part_2').find('.grid_mobile_variable').removeClass('checked');
                }

                $('#vrednost_' + ifId + '_grid_' + id + '_part_2').parent().parent().addClass('checked');
            }
            else{
                $('#vrednost_' + ifId + '_grid_' + id + '_part_2').parent().parent().removeClass('checked');
            }
        }
        else{
            if(element.checked) {

                // Pri checkboxu ne ugasnemo ostalih
                if(!$('#vrednost_if_' + ifId).parent().hasClass('checkbox')){
                    $('#vrednost_if_' + ifId).find('.grid_mobile_variables.part_1').find('.grid_mobile_variable').removeClass('checked');
                }
                
                $('#vrednost_' + ifId + '_grid_' + id).parent().parent().addClass('checked');
            }
            else{
                $('#vrednost_' + ifId + '_grid_' + id).parent().parent().removeClass('checked');
            }
        }

        
    }
    else {
        if(ifId) {
            id = ifId;
    
            if (element.checked) {
                if(type != 16 && type != '6-3-1' && type != '6-3-2')
                    $('#vrednost_if_' + id).find('td').removeClass('checked'); //vse ostale checkboxe odstranimo
    
                // Dvojni grid
                if(type == '6-3-1')
                    $('#vrednost_if_' + id).find('input:not([name$="_part_2"])').closest('td').removeClass('checked');
                else if(type == '6-3-2')
                    $('#vrednost_if_' + id).find('input[name$="_part_2"]').closest('td').removeClass('checked');
    
                $(element).closest('td').addClass('checked');
            }
            else {
                $(element).closest('td').removeClass('checked');
            }
        }

        if (element.checked) {
            $("#vrednost_if_" + id).addClass('checked');
        }
        else {
            $("#vrednost_if_" + id).removeClass('checked');
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
                }
            });
        }
    }
}

function customRadioSelect(idElementa, value){
    //najprej odstranimo class="obarvan" i
      $('#vrednost_if_'+idElementa).siblings().removeClass('obarvan');

    //pobarvamo ustrezno število elementov pred izbranim odgovorom
	var trenutniElement = 'vrednost_if_'+idElementa;
    while(trenutniElement){
		trenutniElement = $('#'+trenutniElement).addClass('obarvan').prev().attr('id');
    }
}

function customRadioTableSelect(idElementa, value){
    //najprej odstranimo class="obarvan" i
    $('#vrednost_if_'+idElementa+' .custom_radio_picture').removeClass('obarvan');

    //pobarvamo ustrezno število elementov pred izbranim odgovorom
    for(var i=value; i > 0; i--){
        $('label[for="vrednost_'+idElementa+'_grid_'+i+'"]').parent().addClass('obarvan');
    }
}
function customRadioTableSelectMobile(idElementa, value){
    //najprej odstranimo class="obarvan" i
    $('#vrednost_if_'+idElementa+' .custom_radio_picture').removeClass('obarvan');

    //pobarvamo ustrezno število elementov pred izbranim odgovorom
    for(var i=value; i > 0; i--){
        $('label[for="vrednost_'+idElementa+'_grid_'+i+'"]').find('.custom_radio_picture').addClass('obarvan');
    }
}


function checkboxLimit (spremenljivka, vrednost, checkbox_limit) {
	
	obj = document.forms['vnos'].elements['vrednost_'+spremenljivka+'[]'];
	var len = obj.length;
	var count = 0;
	
	for (i=0; i<len; i++)
		if (obj[i].checked)
			count++;
	
	if (count > checkbox_limit){
		document.getElementById('spremenljivka_'+spremenljivka+'_vrednost_'+vrednost).checked = false;		
		alert(lang_srv_remind_checkbox_max_violated_hard);
	}
		
}

function checkboxLimitTextbox (spremenljivka, vrednost, checkbox_limit) {
	
	obj = document.forms['vnos'].elements['vrednost_'+spremenljivka+'[]'];
	var len = obj.length;
	var count = 0;
	
	for (i=0; i<len; i++)
		if (obj[i].checked)
			count++;
	
	if (count > checkbox_limit){
		document.getElementById('spremenljivka_'+spremenljivka+'_vrednost_'+vrednost).checked = false;
		document.getElementById('spremenljivka_'+spremenljivka+'_textfield_'+vrednost).blur();
	}
}

function addFormField(spremenljivka) {
	var id = document.getElementById("counter").value;
	$("#divTxt" + spremenljivka).append("<div id='row" + id + "' class='sn_name'><input type='text' value='' size=40 name='spremenljivka_" + spremenljivka + "[]' id='txt" + id + "' onblur='checkName(" + spremenljivka + ", this); checkBranching();' />  <a href='#' onClick='removeFormField(\"#row" + id + "\"); return false;'><span class=\"faicon delete\"></span></a></div>");

	id = (id - 1) + 2;
	document.getElementById("counter").value = id;
}

function removeFormField(id) {
	$(id).remove();
}


// drop pri SN podpori
function accept_droppable (child, parent) {
	$('#' + child).load('ajax.php?a=accept_droppable', {child: child, parent: parent, anketa: srv_meta_anketa_id});
}

// drop pri ranking vprasanju (tip n>k)
function accept_ranking (child, parent, spremenljivka, usr_id) {
	$('#' + child).load('ajax.php?a=accept_ranking', {child: child, parent: parent, spremenljivka: spremenljivka, usr_id: usr_id, anketa: srv_meta_anketa_id});
}


//SN design ??
function dodaj_ime (spremenljivka, ime){
    $('#imena_'+spremenljivka).load('ajax.php?a=dodaj_ime', {spremenljivka: spremenljivka, ime: ime, anketa: srv_meta_anketa_id});
}

//SN design 3
function edit_size (spremenljivka, size) {
    
	var new_fields = '';
	
	for(var i=1; i<=size; i++){
		
		new_fields += '<div id="row'+i+'" class="sn_name"><input type="text" name="spremenljivka_'+spremenljivka+'[]" id="txt'+i+'" size="40" onblur="checkName(\''+spremenljivka+'\', this); checkBranching();"></div>';
	}
	$('#imena_'+spremenljivka).html(new_fields);
}

function checkName (spremenljivka, polje){
	
	var field = polje.value;
	var imena = ['o\u010De','mati','mama','ata','h\u010Di','h\u010Der','sin','brat','sestra','teta','stric','bratranec','sestri\u010Dna','svak','svakinja','ta\u0161\u010Da','tast','dedek','babica','prijatelj','prijatelji','prijateljica','kolegi','kolega','kolegica','sosed','soseda','znanec','znanka','dru\u017Eina','noben','noben drug','nih\u010De','ni\u010D','sodelavec','sodelavka'];
	
	var index = imena.indexOf(field);
	if(index != -1){
		polje.value = '';
		alert('Opozorilo: napa\u010Den vpis\r\n\r\nPonovno vpi\u0161ite ime in za\u010Detnico priimka');
	}		
	//$('#right_'+spremenljivka).load('ajax.php?a=check_name', {spremenljivka: spremenljivka, field: field});	
}


function checkRankingNum (field, max, spremenljivka, count){
	
	var val = field.value;
	var ok = true;
	var temp;
	
	for(var i=0; i<count; i++){
		var id = 'spremenljivka_' + spremenljivka + '_ranking_cifre_' + i;
		temp = document.getElementById(id).value;
		if((temp == val) && (field.id != id)){
			ok = false;
			break;
		}
	}
	
	if(!ok && val != ''){
		alert(lang_srv_alert_number_exists);
		field.value = '';
	}
	
	
	if(val > max){
		alert(lang_srv_alert_number_toobig);
		field.value = '';
	}
}

//preverjamo ce smo dosegli stevilo vnosov
function checkRankingCount (field, max, spremenljivka, count){
	
	var val = field.value;
	var counter = 0;
	
	for(var i=0; i<count; i++){
		var id = 'spremenljivka_' + spremenljivka + '_ranking_cifre_' + i;
		temp = document.getElementById(id).value;
		if(temp != ''){
			counter++;
		}
	}	
	
	for(var i=0; i<count; i++){
		var id = 'spremenljivka_' + spremenljivka + '_ranking_cifre_' + i;
		temp = document.getElementById(id).value;
		if( (temp == '') && (counter == max) ){
			document.getElementById(id).disabled = true;
		}
		else{
			document.getElementById(id).disabled = false;
		}
	}

}


// za missing vrednosti za checkboxe in radio (disejbla ostale možne odgovore če je izbran missing)
function checkMissing (__this) {
	
	// polovimo id vrednosti
	var vrednost_id = $(__this).val(); 
	var spremenljivka_id = $(__this).attr('id'); 
	spremenljivka_id = spremenljivka_id.replace("missing_value_spremenljivka_", "");
	spremenljivka_id = spremenljivka_id.replace("_vrednost_"+vrednost_id+"", "");
	
	//Image HotSpot: za brisanje obmocja
	//identifier za sliko na katero se veze mapa z obmocji
	var image1 = $('#hotspot_'+spremenljivka_id+'_image');
	
	// preštejemo koliko missingov je izbranih
	var missing_selected = $("input[id^=missing_value_spremenljivka_"+spremenljivka_id+"]:checked").length;

	// izbran je lahko samo 1 missing naenkrat
	if (missing_selected > 1) {
		$('#mv_cal_on_'+spremenljivka_id).addClass('hidden');
		$('#mv_cal_off_'+spremenljivka_id).removeClass('hidden');
		$("input[id^=missing_value_spremenljivka_"+spremenljivka_id+"]:checked").each(function (index, value) {
			if ( $(value).val() == vrednost_id) {
				//kliknjen missing je trenutni v loopu
			} else {
				//kliknjen missing ni trenutni v loopu - ga disejblamo
				$(value).attr('checked',false);
			}
		});
	}

	// disejbamo ali enejblamo vnosna polja
	if (missing_selected > 0) {
		$('#mv_cal_on_'+spremenljivka_id).hide();
		$('#mv_cal_off_'+spremenljivka_id).show();

		// diesjblamo vse ostale opcije za vrstico in deselectiramo
		$("input[id^=spremenljivka_"+spremenljivka_id+"]").each(function (index, value) {

			var el_type = $(value).attr('type'); // tip elementa ki ga disejblamo
			$(this).attr('disabled', true); // disejblamo
			// checkboxe še vgasnemo
			if (el_type == 'checkbox') {
				$(this).attr('checked', false); // checkboxe ugasnemo
				//Image HotSpot: brisemo obmocja iz slike 
				image1.mapster('set', false, $(this).val()); //spucaj trenutno obmocje iz slike
			}
			if (el_type == 'text') {
				$(this).val(''); 	// pobrisemo tekst
				$(this).addClass('disabled'); // posivimo
			}
		});
		
		$("div#spremenljivka_"+spremenljivka_id+".tip_8 input[type=text]").val('').attr('disabled', true).addClass('disabled');
		
		// Posebej se disejblamo textarea
		$("textarea[id^=spremenljivka_"+spremenljivka_id+"]").each(function (index, value) {
			$(this).attr('disabled', true); // disejblamo
			$(this).val(''); 	// pobrisemo tekst
			$(this).addClass('disabled'); // posivimo
		});
	
	} else {
		//preverimo ali je kateri checkbox čekiran
		$('#mv_cal_off_'+spremenljivka_id).hide();
		$('#mv_cal_on_'+spremenljivka_id).show();

		// enejblamo vse ostale opcije za vrstico
		$("input[id^=spremenljivka_"+spremenljivka_id+"]").each(function (index, value) {
			var el_type = $(value).attr('type'); // tip elementa ki ga enejblamo
			$(this).attr('disabled', false);	//enejblamo
			if (el_type == 'text') {
				$(this).removeClass('disabled'); // osvetlimo
			}

		});
		
		$("div#spremenljivka_"+spremenljivka_id+".tip_8 input[type=text]").val('').attr('disabled', false).removeClass('disabled');
		
		// Posebej se enablamo textarea
		$("textarea[id^=spremenljivka_"+spremenljivka_id+"]").each(function (index, value) {
			$(this).attr('disabled', false);	//enejblamo
			$(this).removeClass('disabled'); // osvetlimo
		});
	}

}

// za missing vrednosti za tabelarične odgovore
function checkTableMissing (__this) {
	
	// polovimo id vrednosti
	var grid_id = $(__this).val(); 
	var vrednost_id = $(__this).attr('id'); 
	vrednost_id = vrednost_id.replace("grid_missing_value_", "");
	vrednost_id = vrednost_id.replace("_grid_"+grid_id, "");
	
	// preštejemo koliko missingov je izbranih
	var missing_selected = $("input[id^=grid_missing_value_"+vrednost_id+"_grid_]:checked").length;
	// izbran je lahko samo 1 missing naenkraz
	if (missing_selected > 1) {
		$("input[id^=grid_missing_value_"+vrednost_id+"_grid_]:checked").each(function (index, value) {
			if ($(value).attr('id') == 'grid_missing_value_'+vrednost_id+'_grid_'+grid_id) {
				//kliknjen missing je trenutni v loopu
			} else {
				//kliknjen missing ni trenutni v loopu - ga disejblamo
				$(value).attr('checked',false);
			}
		});
	}
	
	// disejbamo ali enejblamo vnosna polja
	if (missing_selected > 0) {
		// diesjblamo vse ostale opcije za vrstico in deselectiramo
		$("input[id^=vrednost_"+vrednost_id+"_grid_], textarea[id^=vrednost_"+vrednost_id+"_grid_],").each(function (index, value) {
			var el_type = $(value).attr('type'); // tip elementa ki ga disejblamo
			
			//TOLE PRI GRIDU Z RADIOBUTTNI NI POTREBNO?
			if (el_type != 'radio') {
				$(this).attr('disabled', true); // disejblamo
			}
			
			// checkboxe še vgasnemo
			if (el_type == 'checkbox') {
				$(this).attr('checked', false); // checkboxe ugasnemo
			}
			if (el_type == 'text' || $(value).is('textarea')) {
				$(this).val(''); 	// pobrisemo tekst
				$(this).addClass('disabled'); // posivimo
			}
			
		});
		// diesjblamo še polje drugo
		$("input[name^=textfield_"+vrednost_id+"], textarea[name^=textfield_"+vrednost_id+"]").each(function (index, value) {
			$(this).attr('disabled', true); // disejblamo
			$(this).addClass('disabled'); // posivimo
			$(this).val('');	// pobrišemo tekst
		});
				
	} else {
		//preverimo ali je kateri checkbox čekiran

		// enejblamo vse ostale opcije za vrstico
		$("input[id^=vrednost_"+vrednost_id+"_grid_], textarea[id^=vrednost_"+vrednost_id+"_grid_]").each(function (index, value) {
			var el_type = $(value).attr('type'); // tip elementa ki ga enejblamo
			$(this).attr('disabled', false);	//enejblamo
			if (el_type == 'text' || $(value).is('textarea')) {
				$(this).removeClass('disabled'); // osvetlimo
			}

		});
		// enejblamo  še polje drugo
		$("input[name^=textfield_"+vrednost_id+"], textarea[name^=textfield_"+vrednost_id+"]").each(function (index, value) {
			$(this).removeClass('disabled');	// osvetlimo
			$(this).attr('disabled', false);	// enejblamo
		});
	}
		
}

function checkBranchingDate() {
	checkBranching();
}


// preklop statistike pri glasovanjih - razvrscanje po spolu
function stat_spol (spremenljivka, spol){
    $('#spremenljivka_statistika').load('../main/survey/ajax.php?a=spol', {spremenljivka: spremenljivka, spol: spol, anketa: srv_meta_anketa_id});
}


function getBodyHeight() {
    var height;
    var scrollHeight;
    var offsetHeight;
	
    if (document.height) {
        height = document.height;
    } else if (document.body) {
        if (document.body.scrollHeight) {
            height = scrollHeight = document.body.scrollHeight;
        }
        if (document.body.offsetHeight) {
            height = offsetHeight = document.body.offsetHeight;
        } 
        if (scrollHeight && offsetHeight) {
            height = Math.max(scrollHeight, offsetHeight);
        }
    }
    return height;
}

function slide_timer_pause_ON() {
	is_paused_slideshow = true;
	$("#btn_pause_on").hide();
	$("#btn_pause_off").show();
}
function slide_timer_pause_OFF() {
	is_paused_slideshow = false;
	$("#btn_pause_off").hide();
	$("#btn_pause_on").show();

}


// Premik vrstice pri dinamicnih multigridih
function rowSlide(spremenljivka, row, next) {

	// Dobimo stevilo vrstic
	var count = $('#dynamic_multigrid_'+spremenljivka).val();

	// Dobimo trenutno prikazanega
	var current = 0;
	for(var i=1; i<=count; i++){
		if($('.'+spremenljivka+'_gridRow_'+i).is(':visible')){
			current = i;
			break;
		}
	}

	// preverimo ce lahko preklopimo na naslednjo (zaradi ifov)
	if( $('.'+spremenljivka+'_gridRow_'+next).hasClass('if_hide') ){
		
		// Ce premikamo naprej poiscemo do konca
		if( parseInt(next) > parseInt(row) ){
			for(var i=parseInt(next); i<=count; i++){
				if( !$('.'+spremenljivka+'_gridRow_'+i).hasClass('if_hide') ){
					next = i;
					break;
				}
				else if(i == count){
					next = undefined;
				}
			}
		}
		// Ce premikamo nazaj poiscemo do zacetka
		else{
			for(var i=parseInt(next); i>0; i--){
				if( !$('.'+spremenljivka+'_gridRow_'+i).hasClass('if_hide') ){
					next = i;
					break;
				}
				else if(i == 1){
					next = undefined;
				}
			}
		}
	}

	if(next != undefined){
		
		// Nastavimo containerju height - drugace pri fade-out skoci na vrh
		var height = $('#spremenljivka_'+spremenljivka).height();
		//$('#spremenljivka_'+spremenljivka).height(height);	// Ugasnjeno, ker drugace ohrani staro visino in lahko pride pri slidu do prekrivanja na dnu
		
		$('.'+spremenljivka+'_gridRow_'+row).fadeOut("medium", function() {$('.'+spremenljivka+'_gridRow_'+next).fadeIn("medium");});
		
		if($('.'+spremenljivka+'_gridRowArrows_'+row).length > 0)
			$('.'+spremenljivka+'_gridRowArrows_'+row).fadeOut("medium", function() {$('.'+spremenljivka+'_gridRowArrows_'+next).fadeIn("medium");});	
	
		current = next;
	}
	
	// Popravimo puscice in counter
	//dynamicMultigridFixArrows(current, count, spremenljivka);
	dynamicMultigridFixAllArrows(spremenljivka);
	
	// Popravimo da vemo da smo ze prej premikali
	$('#dynamic_multigrid_'+spremenljivka+'_load').val('0');
}

// Prikaz/skrivanje vrstic v dinamicnih multigridih zaradi if-ov
function dynamicMultigridSwitchIf(show, id, spremenljivka){
		
	var row = $('#vrednost_if_'+id).attr('seq');
	
	// Dobimo stevilo vrstic
	var count = $('#dynamic_multigrid_'+spremenljivka).val();

	// Dobimo trenutno prikazanega
	var current = 0;
	for(var i=1; i<=count; i++){
		if($('.'+spremenljivka+'_gridRow_'+i).is(':visible')){
			current = i;
			break;
		}
	}
	
	// Prikazemo vrstico v dinamicnem mg zaradi ifa
	if(show == 1){
		$('#vrednost_if_'+id).removeClass('if_hide');
		
		// Ce ni noben element viden prikazemo vklopljenega
		if(current == 0){
			$('.'+spremenljivka+'_gridRow_'+row).show();
		
			if($('.'+spremenljivka+'_gridRowArrows_'+row).length > 0)
				$('.'+spremenljivka+'_gridRowArrows_'+row).show();
				
			current = row;
		}
		// Ce je viden kasnejsi kot trenuten ki bi moral biti prikazan (ker je prvi v ifu, drugi pa ne), premaknemo nazaj (samo ce gre za novo nalaganje strani - ce gre za rowSlide pa ne)
		else if(current > row && $('#dynamic_multigrid_'+spremenljivka+'_load').val() == '1'){	
			rowSlide(spremenljivka, current, row);
		}
	}
	
	// Skrijemo vrstico v dinamicnem mg zaradi ifa
	else{
		$('#vrednost_if_'+id).addClass('if_hide');
		
		// Ce smo izklopili trenutno prikazanega
		if(row == current){

			for(var i=1; i<=count; i++){
				if( !$('.'+spremenljivka+'_gridRow_'+i).hasClass('if_hide') ){
					next = i;
					break;
				}
				else if(i == count){
					next = undefined;
				}
			}
			
			// Preklopimo na prvega ki ga lahko prikazemo
			if(next != undefined){
				$('.'+spremenljivka+'_gridRow_'+row).fadeOut("medium", function() {$('.'+spremenljivka+'_gridRow_'+next).fadeIn("medium");});
				
				if($('.'+spremenljivka+'_gridRowArrows_'+row).length > 0)
					$('.'+spremenljivka+'_gridRowArrows_'+row).fadeOut("medium", function() {$('.'+spremenljivka+'_gridRowArrows_'+next).fadeIn("medium");});
			
				current = next;
			}
			// Ce smo izklopili prikazanega in so vsi ostali skriti
			else{
				$('.'+spremenljivka+'_gridRow_'+row).fadeOut("medium");
				
				if($('.'+spremenljivka+'_gridRowArrows_'+row).length > 0)
					$('.'+spremenljivka+'_gridRowArrows_'+row).fadeOut("medium");
			}
		}
	}
	
	// Popravimo puscice in counter
	//dynamicMultigridFixArrows(current, count, spremenljivka);
	dynamicMultigridFixAllArrows(spremenljivka);
}

function dynamicMultigridFixArrows(current, count, spremenljivka){
	
	// Preverimo ce imamo elemente pred in za, ki jih ne skrivamo z if-i in jih prestejemo
	var show_back = false;
	var show_forward = false;
	var count_valid = 0;
	var count_before = 1;
	for(var i=1; i<=count; i++){

		if(!$('.'+spremenljivka+'_gridRow_'+i).hasClass('if_hide') && i<current){
			show_back = true;
			count_before++;
		}
		else if(!$('.'+spremenljivka+'_gridRow_'+i).hasClass('if_hide') && i>current)	
			show_forward = true;
			
		if(!$('.'+spremenljivka+'_gridRow_'+i).hasClass('if_hide'))
			count_valid++;
	}
	
	
	if($('.'+spremenljivka+'_gridRowArrows_'+current).length > 0){
		
		// Popravimo puscico nazaj
		if(show_back)
			$('.'+spremenljivka+'_gridRowArrows_'+current).find('.arrow_back').show();
		else
			$('.'+spremenljivka+'_gridRowArrows_'+current).find('.arrow_back').hide();
	
		// Popravimo puscico naprej
		if(show_forward)
			$('.'+spremenljivka+'_gridRowArrows_'+current).find('.arrow_forward').show();
		else
			$('.'+spremenljivka+'_gridRowArrows_'+current).find('.arrow_forward').hide();
			
		// Popravimo counter
		$('.'+spremenljivka+'_gridRowArrows_'+current).find('#dynamic_count').html(count_before + ' / ' + count_valid);
	}
	else{
		// Popravimo puscico nazaj
		if(show_back)
			$('.'+spremenljivka+'_gridRow_'+current).find('.arrow_back').show();
		else
			$('.'+spremenljivka+'_gridRow_'+current).find('.arrow_back').hide();
	
		// Popravimo puscico naprej
		if(show_forward)
			$('.'+spremenljivka+'_gridRow_'+current).find('.arrow_forward').show();
		else
			$('.'+spremenljivka+'_gridRow_'+current).find('.arrow_forward').hide();
	
		// Popravimo counter
		$('.'+spremenljivka+'_gridRow_'+current).find('#dynamic_count').html(count_before + ' / ' + count_valid);
	}
}

// Gremo cez vse bloke in vsakemu popravimo paginacijo in puscice
function dynamicMultigridFixAllArrows(spremenljivka){
	
	// Dobimo stevilo vrstic
	var count = $('#dynamic_multigrid_'+spremenljivka).val();
	
	$('.'+spremenljivka+'_gridRow').each(function(){
		
		var current = $(this).attr('seq');
		
		// Preverimo ce imamo elemente pred in za, ki jih ne skrivamo z if-i in jih prestejemo
		var show_back = false;
		var show_forward = false;
		var count_valid = 0;
        var count_before = 1;
        var visible_sequences = new Array();
		for(var i=1; i<=count; i++){

			if(!$('.'+spremenljivka+'_gridRow_'+i).hasClass('if_hide') && i<current){
				show_back = true;
				count_before++;
			}
			else if(!$('.'+spremenljivka+'_gridRow_'+i).hasClass('if_hide') && i>current)	
				show_forward = true;
				
			if(!$('.'+spremenljivka+'_gridRow_'+i).hasClass('if_hide')){
                visible_sequences[i] = true;
                count_valid++;
            }
            else{
                visible_sequences[i] = false;
            }
        }

        // Pri paginaciji skrijemo stevilke, ki so skrite zaradi if-a in prestevilcimo
        var cnt = 1;
        for (i=1; i<visible_sequences.length+1; i++){  

            if(visible_sequences[i] == false){ 
                $(this).find('.sequence_number_' + i).hide(); 
            }
            else{
                $(this).find('.sequence_number_' + i).show(); 
                $(this).find('.sequence_number_' + i).html(cnt); 
                cnt++;
            }
        }
		
		
		if($('.'+spremenljivka+'_gridRowArrows_'+current).length > 0){
			
			// Popravimo puscico nazaj
			if(show_back)
				$('.'+spremenljivka+'_gridRowArrows_'+current).find('.arrow_back').show();
			else
				$('.'+spremenljivka+'_gridRowArrows_'+current).find('.arrow_back').hide();
		
			// Popravimo puscico naprej
			if(show_forward)
				$('.'+spremenljivka+'_gridRowArrows_'+current).find('.arrow_forward').show();
			else
				$('.'+spremenljivka+'_gridRowArrows_'+current).find('.arrow_forward').hide();
				
			// Popravimo counter
			$('.'+spremenljivka+'_gridRowArrows_'+current).find('#dynamic_count').html(count_before + ' / ' + count_valid);
		}
		else{
			// Popravimo puscico nazaj
			if(show_back)
				$('.'+spremenljivka+'_gridRow_'+current).find('.arrow_back').show();
			else
				$('.'+spremenljivka+'_gridRow_'+current).find('.arrow_back').hide();
		
			// Popravimo puscico naprej
			if(show_forward)
				$('.'+spremenljivka+'_gridRow_'+current).find('.arrow_forward').show();
			else
				$('.'+spremenljivka+'_gridRow_'+current).find('.arrow_forward').hide();
		
			// Popravimo counter
			$('.'+spremenljivka+'_gridRow_'+current).find('#dynamic_count').html(count_before + ' / ' + count_valid);
		}
	});
}


// Razpiranje mobilnih tabel
function mobileMultigridExpandable(){
	
	// Pri kliku na naslov podvprasanja razpremo/zapremo podvprasanje
	$(".grid_mobile_title").click(function(){
 
		// Double gridi imajo zaenkrat to izklopljeno
		if(!$(this).parent().parent().hasClass("double")){
		
			// Dobimo pripadajoc div z vsemi vrednostmi
			$(this).parent().find(".grid_mobile_variables").toggle("fast", function(){
				mobileMultigridExpandableArrow(this);
			});
		}
	});

	// Posebna obravnava radio tabel (avtomatsko razpiranje in pomikanje)
	mobileMultigridExpandableRadio();
}
// Razpiranje mobilnih tabel pri radio tabelah
function mobileMultigridExpandableRadio(){
	
	// Na zacetku skrijemo vedno vse razen prvega
	$(".grid_mobile.radio:not(.double)").find("div.grid_mobile_variables:not(:first)").hide(function(){
		mobileMultigridExpandableArrow(this);
	});
	
	// Obrnemo puscico za razpiranje
    $(".grid_mobile.radio:not(.double)").find("div.grid_mobile_variables:not(:first)").parent().find(".mobile_expanding_arrow").toggleClass("arrow_down").toggleClass("arrow_up");
    
    // Pokazemo div z rezultatom
    $(".grid_mobile.radio:not(.double)").find("div.grid_mobile_variables:not(:first)").parent().find(".grid_mobile_result").show();
	
	// Pri kliku na radio zapremo in razpremo naslednji segment
	$("input[type=\"radio\"]").click(function(){

		// Ce gre za radio znotraj tabele
		if($(this).closest(".grid_mobile_variables").length > 0 && !$(this).closest(".grid_mobile_variables").parent().parent().hasClass("double")){
			
			var podvprasanje_current = $(this).closest(".grid_mobile_variables");
			var podvprasanje_next = $(podvprasanje_current).parent().next().find(".grid_mobile_variables");

			// Zapremo trenutno podvprasanje
			$(podvprasanje_current).hide("fast", function(){
				mobileMultigridExpandableArrow(this);
			});

			// Razpremo naslednje vprasanje ce obstaja
			$(podvprasanje_next).show("fast", function(){
				mobileMultigridExpandableArrow(this);
			});

			// Zascrollamo do naslednjega podvprasanja
			$("html, body").animate({
				scrollTop: $(podvprasanje_current).offset().top
			}, 300, "swing");
		}
	});
}
// Razpiranje mobilnih tabel - zamenjava puscice
function mobileMultigridExpandableArrow(el){
    
    var arrow = $(el).parent().find(".mobile_expanding_arrow");
    $(arrow).toggleClass("arrow_down").toggleClass("arrow_up");

    mobileMultigridExpandableData(arrow);
}
// Prikazemo/skrijemo text odgovora pod naslovom
function mobileMultigridExpandableData(arrow){
     
    var result = $(arrow).parent().parent().find(".grid_mobile_result");

    // Dobimo text oznacenega radia in ga zapisemo v result div
    var text = $(arrow).parent().parent().find("input[type=radio]:checked").parent().parent().find(".grid_mobile_variable_title").text();
    $(result).text(text);

    // Glede na puscico prikazemo oz. skrijemo result div
    if($(arrow).hasClass("arrow_down")){
        $(result).show('fast');
    }
    else{
        $(result).hide('fast');
    }
    
}

// Razpiranje vprasanj v bloku - init
function questionsExpandable(){
    
    // Skrijemo vsebino vseh vprasanj razen prvega
    $('.expendable_block .variable_holder:not(:visible:first)').hide();

    // Dodamo pointer in puscico na naslov vprasanj
    $('.expendable_block .naslov').addClass('pointer');
    $('.expendable_block .naslov:not(:visible:first)').append('<span class="faicon arrow_down question_expanding_arrow"></span>');
    $('.expendable_block .naslov:visible:first').append('<span class="faicon arrow_up question_expanding_arrow"></span>');

    // Dodamo se padding na text da ne prekriva puscice
    $('.expendable_block .naslov p').css("padding-right", "35px");


    // Loop cez vse v bloku in zapisemo rezultat v naslov ce je izbran (zaenkrat samo radio)
    $('.expendable_block').each(function() {
        questionExpandableData(this);
    });


	// Klik na naslov - podvprasanje razpremo/zapremo
	$('.expendable_block .naslov').click(function(){

        var spremenljivka = $(this).closest('.spremenljivka');
        questionExpandableToggle(spremenljivka);
    });


    // Pri kliku na radio gremo na naslednje
    $(".expendable_block input[type=radio]").click(function(){

        var spremenljivka = $(this).closest('.spremenljivka');
        var spremenljivka_next = $(spremenljivka).nextAll(":visible").first().find(".variable_holder:hidden:first").closest('.spremenljivka');

        if($(spremenljivka).hasClass("tip_1") && $(spremenljivka).hasClass("expendable_block")){

            // Zapremo trenutnega
            questionExpandableToggle(spremenljivka);

            // Odpremo naslednjega
            questionExpandableToggle(spremenljivka_next);

            // Zascrollamo do naslednjega vprasanja
            $("html, body").animate({
                scrollTop: $(spremenljivka).find('.variable_holder').offset().top
            }, 300, "swing");
        }
    });

    // Pri bluru text vprasanja gremo na naslednje
    $(".expendable_block input[type=text]").blur(function(){

        var spremenljivka = $(this).closest('.spremenljivka');
        var spremenljivka_next = $(spremenljivka).nextAll(":visible").first().find(".variable_holder:hidden:first").closest('.spremenljivka');

        if($(spremenljivka).hasClass("tip_21") && $(spremenljivka).hasClass("expendable_block")){

            // Zapremo trenutnega
            questionExpandableToggle(spremenljivka);

            // Odpremo naslednjega
            questionExpandableToggle(spremenljivka_next);

            // Zascrollamo do naslednjega vprasanja
            $("html, body").animate({
                scrollTop: $(spremenljivka).find('.variable_holder').offset().top
            }, 300, "swing");
        }
    });
}
// Razpiranje vprašanj v bloku - razpiranje/zapiranje posameznega vprasanja
function questionExpandableToggle(spremenljivka){
    
    // Vprasanje razpremo / zapremo
    $(spremenljivka).find('.variable_holder').slideToggle(function(){

        // Obrnemo puscico
        $(spremenljivka).find('.question_expanding_arrow').toggleClass("arrow_up arrow_down");

        // Po potrebi prikazemo/skrijemo rezultat v naslovu
        questionExpandableData(spremenljivka);
    });
}
// Prikazemo/skrijemo text odgovora pod naslovom
function questionExpandableData(spremenljivka){

    // Text prikazujemo samo za radio tip, ki ima kaksno vrednost oznaceno
    if($(spremenljivka).hasClass('tip_1') && $(spremenljivka).find(".variabla.checked").length){
        
        // Ce je vprasanje zaprto prikazemo text
        if($(spremenljivka).find(".variable_holder").is(':hidden')){

            // Extractamo samo text vrednosti (brez radio gumba)
            var label = $(spremenljivka).find(".variabla.checked label").clone();
            $(label).find('input').remove();
            $(label).find('.enka-checkbox-radio').remove();
            
            var result = $(label).text();

            // Dodamo text v naslov
            $(spremenljivka).find('.naslov').append('<p class="expendable_block_result" style="display:none;">' + result + '</p>');
            $(spremenljivka).find('.expendable_block_result').show('fast');
        }
        // Ce je vprasanje zaprto skrijemo text
        else{
            $(spremenljivka).find('.expendable_block_result').hide('fast', function(){
                $(spremenljivka).find('.expendable_block_result').remove();
            });
        }
    }
}


function continue_later (site_url, lang_id) {
	
	if ( $('#continue_later').length ) {
		
		$('#continue_later').remove();
		
	} else {
		$.post(site_url+'main/survey/ajax.php?a=continue_later&language='+lang_id, {anketa: srv_meta_anketa_id, url: window.location.href}, function (data) {
			
			$('#continue_later').remove();
			$('#container h1').after( data );
			
		});	
	}
}

function continue_later_send (site_url, lang_id) {
	
	url = $('#url').val();
	email = $('#email').val();
	
	$.post(site_url+'main/survey/ajax.php?a=continue_later_send&language='+lang_id, {anketa: srv_meta_anketa_id, url:url, email:email}, function (data) {
		
		$('#continue_later').remove();
		
	});	
	
}

function preview_popup_close () {
	
    $('#preview-holder, #preview_switch').fadeOut('medium');

    $('#preview-window').addClass('closed');

    $('.preview_icon_open').show();
    $('.preview_icon_close').hide();

    localStorage.preview_popup = 1;	
}

function preview_popup_open () {
	
    $('#preview-holder, #preview_switch').fadeIn('medium');

    $('#preview-window').removeClass('closed');

    $('.preview_icon_open').hide();
    $('.preview_icon_close').show();    
        	
    localStorage.preview_popup = 0;
}

function inicialke () {
	
	$('form[name=vnos]').append('<input type="hidden" name="inicialke" value="">');
	
	if (localStorage.inicialke) {
		$('#inicialke').val(localStorage.inicialke);
		$('form[name=vnos] input[name=inicialke]').val(localStorage.inicialke);
	}
	
	$('#inicialke').bind('keyup', function (e) {
		localStorage.inicialke = e.target.value;
		$('form[name=vnos] input[name=inicialke]').val(localStorage.inicialke);
	});
	
}

/**
 *preveri, ce so vpisane inicialke  
 */
function check_inicialke () {
	
	if ( $('#inicialke').val() != '' ) {
		return true;
	}
	
	var _return = true;
	$('div.question_comment textarea').each(function (key, elm) {
		if ( $(elm).val() != '' ) {
			_return = false;
		}
	});
	
	return _return;
}

function init_comments_save () {
	
	var comments = {};
	if (localStorage.comments) comments = JSON.parse( localStorage.comments );
		
	return function () {
		for (id in comments) {
			$('textarea#'+id).val( comments[id] );
		}
		
		// ko vpisemo besedilo ga shranimo
		$('textarea[id^=question_comment]').bind('change', function (e) {
			comments[ $(e.target).attr('id') ] = $(e.target).val();	
			localStorage.comments = JSON.stringify(comments);
		});
		
		// ob submitanju forma (in shranjevanju v bazo) pobrisemo iz storaga
		$('form[name=vnos]').submit(function () {
			$('textarea[id^=question_comment]').each(function () {
				delete comments[ $(this).attr('id') ];
				localStorage.comments = JSON.stringify(comments);
			});
		});
		
	}();
}

// respondent se strinja z uporabo piskotkov
function cookie_ok () {
	
	$.post(srv_site_url+'main/survey/ajax.php?a=cookie_ok', {anketa: srv_meta_anketa_id}, function (data) {
		window.location.reload();
	});	
	
	return false;
}

function cookie_check() {
	
	if ( $('#cookie_alert').css('display') == 'block' ) {
		alert(lang['srv_cookie_continue_alert']);
		return false;
	}
	
	$('#container form').submit();
}

function privacy_check() {
	
	if ( $('#privacy_box').length ) {
		if ( ! $('#privacy_box:checked').length ) {
			$('#privacy_box').parent().addClass('required');
			return false;
		}
	}
	
	$('#container form').submit();
}

// max vrstni_red spremenljivke do katere smo prisli, da vemo zaradi validacije
var max_vrstni_red = 0;
$(function () {
	$('.spremenljivka').on('click', function (e) {
		
		//Uros dodal if - pri gogle maps ce se infowindow (nad markerjem) zapre (klikne na X), vrne spodnji if false
		if($(e.target).closest('.spremenljivka')[0]){
			var vrstni_red = parseInt( $(e.target).closest('.spremenljivka')[0].getAttribute('data-vrstni_red') );
			if (vrstni_red > max_vrstni_red)
				max_vrstni_red = vrstni_red;
		}
	});
});

function activateCehckboxImages($what) {
	// zloopamo skozi vse odgovore in če vsebujejo sliko in imajo izbran checkbox naredimo okvir okoli slike
	$what.closest('div.variable_holder').find('div.variabla label:has(img):has(input:checkbox)').each(function(index) {
		$cb = $(this).find('input:checkbox');
		$img = $(this).find('img');
		if ($cb.is(':checked')) {
			$img.addClass('imageselected');
		} else {
			$img.removeClass('imageselected');
		}
	});
	// zloopamo skozi vse odgovore in če vsebujejo sliko in imajo izbran radio naredimo okvir okoli slike
	$what.closest('div.variable_holder').find('div.variabla label:has(img):has(input:radio)').each(function(index) {
		$rd= $(this).find('input:radio');
		$img = $(this).find('img');
		if ($rd.is(':checked')) {
			$img.addClass('imageselected');
		} else {
			$img.removeClass('imageselected');
		}
	});
}

//omeji izbire v Select box le na eno moznost
function omejiSelectBox(spremenljivka){
	
		//console.log('Sem v funkciji omejiSelectBox');
				
		var elem_now;		//belezi id trenutne izbire
		var elem_before;	//belezi id prejsnje izbire
		var klik = 0;		//belezi, ali je bil select že poklikan

		//$('#vrednost_<?=$spremenljivka?> option:selected').each(function(){								
		$('#vrednost_'+spremenljivka+' option:selected').each(function(){
			elem_now = $(this);
			if (!klik){
				elem_before = elem_now.val();
				klik = 1;
			}
										
			var count = $('#vrednost_'+spremenljivka+' option:selected').length;	//koliko izbir je izbranih
			//console.log('Izbranih je: '+count);				
			
			if(elem_now.val() == elem_before){		//ce sta id trenutne in prejsnje izbire enaka
				//console.log("Ista izbira kot prej!");
				//elem_now.prop("selected", true);
			}else{									//ce id trenutne in prejsnje izbire sta enaka
				//console.log("Sta različna!");
				if(count > 1){						//ce je vec izbir izbranih
					elem_now.prop('selected', false);	//odstrani izbiro na trenutno izbrani izbiri						
				}
				elem_before = elem_now.val();		//prejsnja izbira je trenutna								
			}			
		});							
		//});
}

//omeji izbire v Select box le na eno moznost pri Multigrid
function omejiSelectBoxMulti(spremenljivka, id){
	
		//console.log('Sem v funkciji omejiSelectBox');
				
		var elem_now;		//belezi id trenutne izbire
		var elem_before;	//belezi id prejsnje izbire
		var klik = 0;		//belezi, ali je bil select že poklikan

		//$('#vrednost_<?=$spremenljivka?> option:selected').each(function(){								
		$('#vrednost_'+spremenljivka+'_'+id+' option:selected').each(function(){
			elem_now = $(this);
			if (!klik){
				elem_before = elem_now.val();
				klik = 1;
			}
										
			var count = $('#vrednost_'+spremenljivka+'_'+id+' option:selected').length;	//koliko izbir je izbranih
			//console.log('Izbranih je: '+count);				
			
			if(elem_now.val() == elem_before){		//ce sta id trenutne in prejsnje izbire enaka
				//console.log("Ista izbira kot prej!");
				//elem_now.prop("selected", true);
			}else{									//ce id trenutne in prejsnje izbire sta enaka
				//console.log("Sta različna!");
				if(count > 1){						//ce je vec izbir izbranih
					elem_now.prop('selected', false);	//odstrani izbiro na trenutno izbrani izbiri						
				}
				elem_before = elem_now.val();		//prejsnja izbira je trenutna								
			}			
		});							
		//});

}

//ob kliku na isto izbiro v select box se izbira odstrani kot pri radiobuttonih
var elem_now = [];		//belezi id trenutne izbire v select box
var elem_before = [];	//belezi id prejsnje izbire v select box
var klik = [];		//belezi, ali je bil select box že poklikan

function clickSelectBox(spremenljivka, limit){
	var count = $('#vrednost_'+spremenljivka+' option:selected').length;	//koliko izbir je izbranih	
	var allOptions = [];
	var selectedOptions = [];
	/* console.log("count: "+count);*/	
	//console.log("limit: "+limit); 

	$('#vrednost_'+spremenljivka+' option:selected').each(function(){				
		elem_now[spremenljivka] = $(this);
		selectedOptions.push(elem_now[spremenljivka].val());	//v polje dodaj izbrano moznost
		//console.log('Trenutno izbran: '+elem_now[spremenljivka].val()+'');
		//console.log('Klik: '+klik[spremenljivka]+'');
		if ((!klik[spremenljivka] && (elem_before[spremenljivka] != elem_now[spremenljivka].val())) ||(!klik[spremenljivka] && (elem_before[spremenljivka] == elem_now[spremenljivka].val()))){
			elem_before[spremenljivka] = elem_now[spremenljivka].val();
			klik[spremenljivka] = 1;
		}
		else if(klik[spremenljivka] && (elem_before[spremenljivka] != elem_now[spremenljivka].val())){
			elem_before[spremenljivka] = elem_now[spremenljivka].val();
		}
		else if(count == 1 && klik[spremenljivka] && (elem_before[spremenljivka] == elem_now[spremenljivka].val())){
			//console.log('Sta enaka');
			elem_now[spremenljivka].prop('selected', false);
			klik[spremenljivka] = 0;				
		}
	});
	

 	//if(count > limit){	//ce je stevilo izbranih moznosti vecje od limita
 	if(count>=limit && limit!=0){	//ce je stevilo izbranih moznosti vecje od limita
		//zabelezi vse moznosti
		$('#vrednost_'+spremenljivka+' option').each(function(){
			allOptions.push($(this).val());	//v polje dodaj moznost
		});
		
 		for(var i=0;i<count;i++){
			for(var j=0;j<allOptions.length;j++){
				if(allOptions[j] == selectedOptions[i]){	//ce med vsemi moznostmi je trenutna moznost enaka eni od izbranih moznosti
					allOptions.splice(j,1);	//odstrani moznost iz polja
				}
			}
		}
		
		for(var i=0;i<allOptions.length;i++){
			//console.log("Spucane moznosti: "+allOptions[i]);	//pokazi vse moznost
			$('#vrednost_if_'+allOptions[i]).prop("disabled", true);	//disable ne izbrane moznosti
		}
		allOptions = [];
	}else{	//ce stevilo izbranih moznosti ni vecje od limita
		//zabelezi vse moznosti
		$('#vrednost_'+spremenljivka+' option').each(function(){
			allOptions.push($(this).val());	//v polje dodaj moznost
		});
		for(var i=0;i<allOptions.length;i++){		
			$('#vrednost_if_'+allOptions[i]).prop("disabled", false);	//disable ne izbrane moznosti
		}		
	}
}

//ob kliku na isto izbiro v select box, kjer je več možnih odgovorov, se izbira odstrani kot pri radiobuttonih
function clickSelectBoxMulti(spremenljivka, id){
		var spremenljivka_id = spremenljivka+id;
		var count = $('#vrednost_'+spremenljivka+'_'+id+' option:selected').length;	//koliko izbir je izbranih
		$('#vrednost_'+spremenljivka+'_'+id+' option:selected').each(function(){
			elem_now[spremenljivka_id] = $(this);
			 //console.log('Trenutno izbran: '+elem_now[spremenljivka_id].val()+'');
			 //console.log('Klik: '+klik[spremenljivka_id]+'');
			if ((!klik[spremenljivka_id] && (elem_before[spremenljivka_id] != elem_now[spremenljivka_id].val())) ||(!klik[spremenljivka_id] && (elem_before[spremenljivka_id] == elem_now[spremenljivka_id].val()))){
				elem_before[spremenljivka_id] = elem_now[spremenljivka_id].val();
				klik[spremenljivka_id] = 1;
				//console.log('Prvi if');
			}
			else if(klik[spremenljivka_id] && (elem_before[spremenljivka_id] != elem_now[spremenljivka_id].val())){
				elem_before[spremenljivka_id] = elem_now[spremenljivka_id].val();
				//console.log('Drugi if');
			}
			else if(count == 1 && klik[spremenljivka_id] && (elem_before[spremenljivka_id] == elem_now[spremenljivka_id].val())){
				//console.log('Sta enaka');
				elem_now[spremenljivka_id].prop('selected', false);
				klik[spremenljivka_id] = 0;			
			}
		});
}

//ob kliku na isto izbiro v select box v kombinirani tabeli se izbira odstrani kot pri radiobuttonih
function clickSelectBoxMultiCombo(spremenljivka, id, grid){
		var spremenljivka_id = spremenljivka+id+grid;
		var count = $('#multi_'+spremenljivka+'_'+id+'_grid_'+grid+' option:selected').length;	//koliko izbir je izbranih
		$('#multi_'+spremenljivka+'_'+id+'_grid_'+grid+' option:selected').each(function(){
			elem_now[spremenljivka_id] = $(this);
			 //console.log('Trenutno izbran: '+elem_now[spremenljivka_id].val()+'');
			// console.log('Klik: '+klik[spremenljivka_id]+'');
			if ((!klik[spremenljivka_id] && (elem_before[spremenljivka_id] != elem_now[spremenljivka_id].val())) ||(!klik[spremenljivka_id] && (elem_before[spremenljivka_id] == elem_now[spremenljivka_id].val()))){
				elem_before[spremenljivka_id] = elem_now[spremenljivka_id].val();
				klik[spremenljivka_id] = 1;
			}
			else if(klik[spremenljivka_id] && (elem_before[spremenljivka_id] != elem_now[spremenljivka_id].val())){
				elem_before[spremenljivka_id] = elem_now[spremenljivka_id].val();
			}
			else if(count == 1 && klik[spremenljivka_id] && (elem_before[spremenljivka_id] == elem_now[spremenljivka_id].val())){
				//console.log('Sta enaka');
				elem_now[spremenljivka_id].prop('selected', false);
				klik[spremenljivka_id] = 0;				
			}
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


var randomization_inside_block = {};
function blockRandomizeQuestions(parent_block_id, order, usr_id, spr_count){

	// Dobimo array z random vrstnim redom (seedan z usr_id)
	var question_ids = JSON.parse(order);
	
	// Preverimo ce smo ze izvedli randomizacijo
	if(randomization_inside_block[parent_block_id] != true){

		// Najprej wrappamo vsa vprasanja v en div da ga lahko na koncu pobrisemo
		$('.spremenljivka.block_child_' + parent_block_id).wrapAll("<div id='block_id_" + parent_block_id + "'></div>");

		var new_content = '';
		var counter = 0;
		question_ids.forEach(function(el){

			// Stejemo da prikazemo samo omejeno stevilo vprasanj (ce imamo vklopljeno nastavitev)
			if(counter < spr_count){
				// Ce element ne obstaja na strani zakljucimo funkcijo
				if(!$('#spremenljivka_' + el).length)
					return;

				// Dodamo blok v novo vsebino
				new_content += $('#spremenljivka_' + el)[0].outerHTML;

				counter++;
			}
		});

		// Pripnemo novo vsebino in pobrisemo staro
		$("#block_id_" + parent_block_id).after(new_content);
		$("#block_id_" + parent_block_id).remove();
		
		// Zabelezimo da smo izvedli randomizacijo za blok
		randomization_inside_block[parent_block_id] = true;

		// Porezemo elemente, ce imamo omejeno stevilo vprasanj
		question_ids = question_ids.slice(0, spr_count);
		var order_limited = JSON.stringify(question_ids);
		
		// Shranimo vrstni red v bazo
		$.post(srv_site_url+'main/survey/ajax.php?a=save_randomization_order', {anketa: srv_meta_anketa_id, parent_block_id: parent_block_id, order: order_limited, randomization_type: 'spremenljivke', usr_id: usr_id});	
	}
}
function blockRandomizeBlocks(parent_block_id, order, usr_id){

	// Dobimo array z random vrstnim redom (seedan z usr_id)
	var blocks_ids = JSON.parse(order);

	// Preverimo ce smo ze izvedli randomizacijo
	if(randomization_inside_block[parent_block_id] != true){

		// Najprej wrapamo vprasanja v en div
		var new_content = '';
		blocks_ids.forEach(function(el){

			// Ce element ne obstaja na strani zakljucimo funkcijo
			if(!$('.spremenljivka.block_child_' + el).length)
				return;

			// Wrapamo vsa vprasanja znotraj posameznega notranjega bloka
			$('.spremenljivka.block_child_' + el).wrapAll( "<div id='block_id_" + el + "' class='block block_child_" + parent_block_id + "'></div>");

			// Dodamo blok v novo vsebino
			new_content += $('#block_id_' + el).html();
		});

		// Wrappamo bloke v parent blok
		$('.block.block_child_' + parent_block_id).wrapAll("<div id='block_id_" + parent_block_id + "'></div>");

		// Pripnemo novo vsebino in pobrisemo staro
		$("#block_id_" + parent_block_id).after(new_content);
		$("#block_id_" + parent_block_id).remove();

		// Zabelezimo da smo izvedli randomizacijo za blok
		randomization_inside_block[parent_block_id] = true;

		// Shranimo vrstni red v bazo
		$.post(srv_site_url+'main/survey/ajax.php?a=save_randomization_order', {anketa: srv_meta_anketa_id, parent_block_id: parent_block_id, order: order, randomization_type: 'bloki', usr_id: usr_id});
	}
}

//************************ trak @ diferencial
var elem_now_trak = [];		//belezi id trenutne izbire v select box
var elem_before_trak = [];	//belezi id prejsnje izbire v select box
var klik_trak = [];		//belezi, ali je bil select box že poklikan

//funkcija za oznacevanje izbranega odgovora na traku
function trak_change_bg(this_s, diferencial_trak, spremenljivka, missing){
	if (diferencial_trak){	//ce je trak vklopljen
		//console.log(this_s.id);
		
		if (missing == 0){	//ce ni missing radio button
			var children = $(this_s).find('input[type=radio]').attr('id');	//id kliknjenega radio button-a
			var vre_id = $(this_s).find('input[type=radio]').attr('vre_id');	//id kliknjenega radio button-a
		
			setCheckedClass($('#'+children)[0], null, vre_id);
		}else{
			var children = this_s.id;
			var vre_id = $('#'+children).attr('vre_id');
		}
		
		//console.log($('#'+children).val());
		
		elem_now_trak[spremenljivka] = children;
				
		$('#vrednost_if_'+vre_id).children().removeClass('trak_container_bg');	//odstrani barvo ozadja za oznacen odgovor
		
		$('#'+children).attr('checked','checked');	//oznaci ustrezni radio button
		
		var trak = "trak_tbl_" + vre_id + "_" + $('#'+children).val();
		
		if ( !klik_trak[spremenljivka] && (elem_now_trak[spremenljivka] != elem_before_trak[spremenljivka]) ){
			$('#'+trak).addClass('trak_container_bg');	//preuredi ozadje z želeno barvo			
			klik_trak[spremenljivka] = 1;
		}else if ( klik_trak[spremenljivka] && (elem_now_trak[spremenljivka] != elem_before_trak[spremenljivka]) ){
			$('#'+trak).addClass('trak_container_bg');	//preuredi ozadje z želeno barvo			
		}else if ( klik_trak[spremenljivka] && (elem_now_trak[spremenljivka] == elem_before_trak[spremenljivka]) ){
			$('#variabla_'+vre_id).children().removeClass('trak_container_bg');	//odstrani barvo ozadja za oznacen odgovor
			klik_trak[spremenljivka] = 0;
			$('#'+children).attr('checked', false);	//odstrani oznacitev ustreznega radio button
		}else if ( !klik_trak[spremenljivka] && (elem_now_trak[spremenljivka] == elem_before_trak[spremenljivka]) ){
			$('#'+trak).addClass('trak_container_bg');	//preuredi ozadje z želeno barvo
			klik_trak[spremenljivka] = 1;
		}
		elem_before_trak[spremenljivka] = elem_now_trak[spremenljivka];
	}
}

//globalni spremenljivki za elektronski podpis
var podpisposlan = [];
var optionsPodpis = [];


// klik na "vec" pri text vprasanju in nastavitvi prikaz prejsnjih odgovorov
function show_prevAnswers_all (spremenljivka) {
	
	$('#text_prevAnswers_popup_' + spremenljivka).load(srv_site_url+'main/survey/ajax.php?a=show_prevAnswers_all', {spremenljivka: spremenljivka, anketa: srv_meta_anketa_id}, function(){			
		$('#fade').fadeTo('fast', 0.5, function(){
			$('#text_prevAnswers_popup_' + spremenljivka).fadeIn("fast");
		});
	});
}

function hide_prevAnswers_all(spremenljivka){
	
	$('#fade').fadeOut();
	$('#text_prevAnswers_popup_' + spremenljivka).hide();	
}

// gdpr popup "podrobnosti zbiranja podatkov"
function show_gdpr_about(lang_id) {

    $('#popup').addClass('gdpr_about');
    
	$('#popup').load(srv_site_url+'main/survey/ajax.php?a=show_gdpr_about&language='+lang_id, {anketa: srv_meta_anketa_id}, function(){			
		$('#fade').fadeTo('fast', 0.5, function(){
			$('#popup').fadeIn("fast");
		});
	});
}

function hide_gdpr_about(){
	
	$('#fade').fadeOut();
    $('#popup').hide();	
    
    $('#popup').removeClass('gdpr_about');
}

/**
 * Show notification, that respondent has reached maximum number of chars
 * @param {type} input - input or textarea
 * @param {type} sid - id of question
 * @returns {undefined}
 */
function handleMaxTextParam(input, sid){
    if (input.value.length != input.maxLength)
        $('#max_text_notification_'+sid).hide();
    else
        $('#max_text_notification_'+sid).show();
}

/**
 * Refresh char counter of field
 * @param {type} field - an element to count chars from
 * @returns {undefined}
 */
function charCounter(field){
    document.getElementById(field.id+'_counter').innerHTML = field.value.length;
}

/**
 * Refresh char counter of field
 * @param {type} field_id - id of filed to count chars from
 * @returns {undefined}
 */
function set_charCounter(field_id){
    document.getElementById(field_id+'_counter').innerHTML = document.getElementById(field_id).value.length;
}


// Preverjanje in ustrezno disablanje/enablanje polj v dropdownu pri tipu razvrscanje (ostevilcevanje)
function rankingSelect(value, spremenljivka, counter){
	
	$("#spremenljivka_" + spremenljivka + "_ranking_cifre_" + counter).val(value);

    rankingSelectCheck(spremenljivka);
}
// Preverimo vse ranking dropdowne znotraj vprasanja in jih ustrezno omogocimo/onemogocimo
function rankingSelectCheck(spremenljivka){
    var select_values = [];

    // Loop selecte in preberemo vrednosti
    $("#spremenljivka_"+spremenljivka).find(".ranking_select").each(function(i, obj) {
        
        select_values[i] = $(obj).val();
        $(obj).children().attr("disabled", false);
    });

    // Loop cez vse selecte in disable vrednost
    $("#spremenljivka_"+spremenljivka).find(".ranking_select").each(function(i, obj) {

        $(select_values).each(function(j, select_value) {
            if(j != i && select_value != ""){
                $(obj).children("option[value=" + select_value + "]").attr("disabled", true);
            }
        });
    });
}
// Preverimo vse ranking dropdowne znotraj vseh vprasanj in jih ustrezno omogocimo/onemogocimo (na loadu strani)
function rankingSelectCheckAll(){
    var select_values = [];

    // Loop cez vsa ranking vprasanja na strani
    $(".spremenljivka.tip_17").each(function(i, vprasanje) {
        
        // Loop selecte in preberemo vrednosti
        $(vprasanje).find(".ranking_select").each(function(i, obj) {
        
            select_values[i] = $(obj).val();
            $(obj).children().attr("disabled", false);
        });

        // Loop cez vse selecte in disable vrednost
        $(vprasanje).find(".ranking_select").each(function(i, obj) {

            $(select_values).each(function(j, select_value) {
                if(j != i && select_value != ""){
                    $(obj).children("option[value=" + select_value + "]").attr("disabled", true);
                }
            });
        });
    });
}

// Preverjanje in ustrezno disablanje/enablanje polj v dropdownu pri tipu razvrscanje (ostevilcevanje) - SAZU modul
function sazuSelect(value, spremenljivka, counter){
    $("#spremenljivka_" + spremenljivka + "_ranking_cifre_" + counter).val(value);

    sazuSelectCheck();
}
function sazuSelectCheck(){
    var select_values = [];

    // Loop selecte in preberemo vrednosti
    $(".sazu_select").each(function(i, obj) {
        select_values[i] = $(obj).val();
        $(obj).children().attr("disabled", false);
    });

    // Loop cez vse selecte in disable vrednost
    $(".sazu_select").each(function(i, obj) {

        $(select_values).each(function(j, select_value) {
            if(j != i && select_value != ""){
                $(obj).children("option[value=" + select_value + "]").attr("disabled", true);
            }
        });
    });
}


// Ponovimo naslovno vrstico tabele, ce je omogocena nastavitev
function gridRepeatHeader(repeat_every, spr_id){

    var table = $("#spremenljivka_" + spr_id + " table.grid_table");
    
    // Ce ima vrstica class za ponavljanje
    if($(table).find('tr.repeat_header').length){

        var table_header = $(table).find('tr.repeat_header')[0].outerHTML;

        // Za tabelo najprej pobrisemo vse ponovljene vrstice ce so ze prisotne
        $(table).find('tbody tr.repeat_header').remove();

        // Loopamo cez vidne vrstice in vstavimo header vsakih "repeat_every" vrstic
        $(table).find("tbody tr:visible").each(function(index) {

            // Insetamo naslovno vrstico na pravo mesto
            if((index+1) % repeat_every == 0){
                $(this).after(table_header);    
            }
        });    
    }
}


// Preverimo upload file omejitve
function checkUpload(upload, id){

    // Max file size (mb)
    let maxSize = 16;

    // Get file size
    let fileSize = Math.round(( upload.files[0].size / 1024 / 1024 ));
    
    // File is too large
    if(fileSize > maxSize){
        $(upload).val(null);
        alert(lang["srv_alert_upload_size"]);

        return;
    }


    // File extensions allowed
    let extAllowed = ["jpeg", "jpg", "png", "gif", "pdf", "doc", "docx", "xls", "xlsx"];

    // Get file ext
    let fileNameFull = upload.files[0].name;
    let lastDot = fileNameFull.lastIndexOf('.');

    let fileName = fileNameFull.substring(0, lastDot);
    let fileExt = fileNameFull.substring(lastDot + 1).toLowerCase();

    // Wrong file extension
    if(!extAllowed.includes(fileExt)){
        $(upload).val(null);
        alert(lang["srv_alert_upload_ext"]);

        return;
    }


    // Add/remove "remove file" button
    $('#remove_file_' + id).show();
}

function removeUpload(id){
    $('#' + id).val(null);
}


// Disablamo vse inpute in jim dodamo input hidden, da se posta odgovor
function disableSubsequentAnswers(){
    
    // Disable radio and checkbox
    $('input[type="radio"], input[type="checkbox"]').each(function() {

        if($(this).prop("checked") == true){
            var name = $(this).attr("name");
            var value = $(this).val();

            $(this).before("<input type=\"hidden\" name=\""+name+"\" value=\""+value+"\">");
        }

        $(this).prop("disabled", "true");
    });

    // Disable radio and checkbox
    $('input[type="text"], textarea, input[type="password"], input[type="email"]').each(function() {

        var name = $(this).attr("name");
        var value = $(this).val();

        $(this).before("<input type=\"hidden\" name=\""+name+"\" value=\""+value+"\">");

        $(this).prop("disabled", "true");
    });

}
