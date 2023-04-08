
var spr_id_variable = []; 				//za sledenje opozoril: polje, ki hrani spr_id, kjer se pojavijo opozorila
var tip_opozorila = []; 				//za sledenje opozoril: polje, ki hrani tip reminderja/opozorila
var spr_id_indeks = 0; 					//za sledenje opozoril: indeks za polja, ki hranita spr_id, kjer se pojavijo opozorila in tip reminderja
var opozorila_sum = [];					//za sledenje opozoril
var opozorila_num = []; 				//za sledenje opozoril
var opozorila_validation = []; 			//za sledenje opozoril

var tip_opozorila_temp = [];
var validacijaZabelezena = [];
var zacetnaValidacijaZabelezena = 1;	//belezi, ce se je zabelezila validacija ne dinamicno, brez da bi respondent kliknu na bilo kateri odgovor in sel neposredno na naslednjo stran ankete
var spremenljivkaVal = [];				//polje, ki belezi katere spremenljivke imajo opozorilo val



// ZAZNAMO PROŽENJE ALERTA
$(function () {

	var time_display;

	 // remember the normal alert
	 var oldAlert = (function(){ return this.alert; }()),
	 oldConfirm = (function(){ return this.confirm; }());

	// inject ourself into the window.alert and window.confirm globals
	alert = function (msg) {
		time_display = Date.now();

		oldAlert.call(window, msg);
		window.onAlert(msg);
	};
	confirm = function (msg) {
		time_display = Date.now();

		var result = oldConfirm.call(window, msg);
		window.onConfirm(msg, result);

		return result;
	};

	// these just chill and listen for events
	window.onAlert = function (text) {
		logAlert({text:text, type:'alert box', ignorable:0, action:'ok', time_display:time_display});
	};
	window.onConfirm = function (text, result) {
		var action = result ? 'yes' : 'no';
		logAlert({text:text, type:'confirm box', ignorable:1, action:action, time_display:time_display});
	};
});



// Logiramo alert (ob submitu)
function logAlert(box_data){
	
	//console.log("Trenutna dolzina polja: "+spr_id_variable.length);
	//console.log(box_data);

	var spremenljivkaVal = []; //polje, ki shranjuje spr_id spremenljivk s sprozenim val opozorilom
	var tip_opozorila_tmp = [];
	
	spr_id_variable.forEach(function(variable, index) {
	
		// ce tip opozorila vsebuje vejico, pomeni, da belezi dva opozorila hkrati
		// razbij opozorilo na dva dela
		if(tip_opozorila[variable].includes(",")){	
			var opozorilo = tip_opozorila[variable].split(",");
			opozorilo[1] = opozorilo[1].substring(1); //odstrani presledek na zacetku opozorila
			
			//$.post('../main/survey/ajax.php?t=parapodatki&a=logData', {usr_id: _usr_id, what: opozorilo[0], what2: opozorilo[1], gru_id: page, anketa: srv_meta_anketa_id, spr_id_variable: variable});
			var event_type = 'alert';
			var event = 'alert';
			
			opozorilo_type[0] = opozorilo[0].substring(4);
			opozorilo_trigger_type[0] = opozorilo[0].substring(0, 3);
			opozorilo_type[1] = opozorilo[1].substring(4);
			opozorilo_trigger_type[1] = opozorilo[1].substring(0, 3);

			var data = {
				type: opozorilo_type[0] + ' ' + opozorilo_type[1] + ' (' + box_data.type + ')',
				trigger_id: variable,
				trigger_type: opozorilo_trigger_type[0] + ' ' + opozorilo_trigger_type[1],
				text: box_data.text,
				ignorable: box_data.ignorable,		
				action: box_data.action,
				time_display: box_data.time_display
			};
			
			logEvent(event_type, event, data);
		}
		else{
			//$.post('../main/survey/ajax.php?t=parapodatki&a=logData', {usr_id: _usr_id, what: tip_opozorila[variable], gru_id: page, anketa: srv_meta_anketa_id, spr_id_variable: variable});
			var event_type = 'alert';
			var event = 'alert';
			
			var opozorilo_type = tip_opozorila[variable].substring(4);
            var opozorilo_trigger_type = tip_opozorila[variable].substring(0, 3);
            
            // Ce gre za multigrid zabelezimo samo id vprasanja
            if(variable.substring(0, 12) == '#vrednost_if'){

                var spremenljivka_id = $(""+variable).closest('.spremenljivka').attr('id').substring(14);
                
                //variable = spremenljivka_id + '_' + variable.substring(13);
                variable = spremenljivka_id;
            }

			var data = {
				type: opozorilo_type + ' (' + box_data.type + ')',
				trigger_id: variable,
				trigger_type: opozorilo_trigger_type,
				text: box_data.text,
				ignorable: box_data.ignorable,
				action: box_data.action,
				time_display: box_data.time_display
			};
			
			logEvent(event_type, event, data);
		}
					
		//console.log("Spr_id opozorila: "+variable+", indeks "+index+", tip:"+tip_opozorila[variable]+" za user: "+_usr_id+"  zacetnaValidacijaZabelezena: "+zacetnaValidacijaZabelezena);
		
		//ce se ne belezi prvic validacije in tip opozorila je validacija
		if(zacetnaValidacijaZabelezena == 0 && tip_opozorila[variable].includes("val"))
		{
			spremenljivkaVal.push(variable);

			//ce se belezi dvojno opozorilo
			if(tip_opozorila[variable].includes(",")){
				tip_opozorila_tmp[variable] = opozorilo[0];
			}
			else{
				tip_opozorila_tmp[variable] = tip_opozorila[variable];
			}				
		}	
	});
	
	spr_id_variable = [];	// pucanje polja
	tip_opozorila = [];
	
	if(spremenljivkaVal.length != 0){
		spremenljivkaVal.forEach(function(valSprem) {
			spr_id_variable.push(valSprem);				
			tip_opozorila[valSprem] = tip_opozorila_tmp[valSprem];
		});
	}
	
	//console.log("Polje spremenljivkaVal: "+spremenljivkaVal.length);	
}



function dodaj_opozorilo_val(bol, id){
	
	var spr_id = id.replace('#spremenljivka_', '');
	
	//console.log("Tip opozorila prej:" + tip_opozorila[spr_id]);
	
	if(zacetnaValidacijaZabelezena == 1){		
		zacetnaValidacijaZabelezena = 0;
	}
	else{
	}
	
	if(!validacijaZabelezena[spr_id]){
		spr_id_variable.push(spr_id);
	}
	
	
	var tip_alerta = '';	

	tip_alerta = 'val';
	validacijaZabelezena[spr_id] = 1;
	spremenljivkaVal[spr_id] = spr_id;
	
	tip_opozorila[spr_id] = tip_alerta + ' ' + bol + ' alert';
}

function dodaj_opozorilo(alert_sum, alert_num, alert_validation, bol, id){
	
	//console.log("Dodaj opozorilo");
	
	var spr_id = id.replace('#spremenljivka_', '');
	
	var tip_alerta = '';
	
	if(alert_sum) tip_alerta = 'sum';
	if(alert_num) tip_alerta = 'num';
	
	if(tip_alerta == ''){
		
		// ce ni se nobenega opozorila zabelezenega za to spremenljivko
		if(tip_opozorila[spr_id] == undefined){	
			tip_opozorila[spr_id] = bol+' alert';
			spr_id_variable.push(spr_id);
		}
		else{	
			//drugace, ce je zabelezeno kaksno opozorilo, po navadi, ko je validation
			tip_opozorila_temp[spr_id] = bol+' alert';
			
			//ce je prejsnje opozorilo enako trenutnemu
			if(tip_opozorila[spr_id] == tip_opozorila_temp[spr_id]){
							
				tip_opozorila[spr_id] = tip_opozorila_temp[spr_id];
				tip_opozorila_temp[spr_id] = '';
				
				spr_id_variable.push(spr_id);				
			} 
			// drugace imamo dva razlicna opozorila, ki ju je potrebno zabeleziti
			else if(validacijaZabelezena[spr_id] == 1){		
						
				tip_opozorila[spr_id] = tip_opozorila[spr_id]+', '+bol+' alert';
				tip_opozorila_temp[spr_id] = '';
				
				//console.log("Dvojno opozorilo");
			}
		}		
	}
	else{
		// ce ni se nobenega opozorila zabelezenega za to spremenljivko
		if(tip_opozorila[spr_id] == undefined){
			
			tip_opozorila[spr_id] = tip_alerta+' '+bol+' alert';
			spr_id_variable.push(spr_id);
		}
		//drugace, ce je zabelezeno kaksno opozorilo, po navadi, ko je validation
		else{	
			tip_opozorila_temp[spr_id] = tip_alerta+' '+bol+' alert';
			
			// ce je prejsnje opozorilo enako trenutnemu
			if(tip_opozorila[spr_id] == tip_opozorila_temp[spr_id]){
				tip_opozorila[spr_id] = tip_opozorila_temp[spr_id];
				tip_opozorila_temp[spr_id] = '';
				spr_id_variable.push(spr_id);
			} 
			// drugace imamo dva razlicna opozorila, ki ju je potrebno zabeleziti
			else if(validacijaZabelezena[spr_id] == 1){	
				tip_opozorila[spr_id] = tip_opozorila[spr_id]+', '+tip_alerta+' '+bol+' alert';
				tip_opozorila_temp[spr_id] = '';
			}			
		}
	}
}

function odstrani_opozorilo(id, alert_sum, alert_num, alert_validation){
	
	//console.log("Odstrani");
	
	var spr_id = id.replace('#spremenljivka_', '');
	
	tip_opozorila.splice(spr_id, 1);	//odstrani opozorilo iz polja
	
	if(alert_validation){
		
		//odstrani iz polja zabelezeno validacijo
		spr_id_variable.forEach(function(variable, index) {
			if(variable == spremenljivkaVal[variable]){
				spr_id_variable.splice(index, 1);
			}
		});		
		
		validacijaZabelezena[spr_id] = 0;
	}
}
