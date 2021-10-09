function slider_init (mobile, spremenljivka, min, max, def, handle, slider_handle_step, vmesne_labels, vmesne_Crtice, slider_MinMaxNumLabelNew, slider_window_number, vmesne_descr_labele, tip_vmesne_descr_labele, def_value, nakazi_odgovore, slider_VmesneDescrLabel, slider_CustomDescriptiveLabels, custom) {
	
	$("input[name^='vrednost_"+spremenljivka+"']").css('display', 'none');
	
	var val = $("label[for^=spremenljivka_"+spremenljivka+"_vrednost_] input").val() || null;
	
	var minmaxlabela = "label";//hrani nastavitev za minmax labele
	var rest = false;	//hrani nastavitve za vmesne èrtice z ("label") in brez label ("pip") ter odsotnost èrtic (false)	
	var vmesne_opisne_labele = false;
	
	//ureditev handle kot bunkica in nakazovanje moznih odgovorov ************************************************************
	
	//ureditev handle kot bunkica	
	if (handle == 1){//ce zelimo skriti handle
		$('#slider_' + spremenljivka).slider().removeClass("classic_slider");	//odstrani razred s klasicnim handle
		$('#slider_' + spremenljivka).slider().addClass("special_slider");	//dodaj razred s handle v obliki bunkice					
		$('#slider_' + spremenljivka + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
	}
	else if (handle == 0){ //drugace
		$('#slider_' + spremenljivka).slider().addClass("classic_slider");	//dodaj klasicen razred
		$('#slider_' + spremenljivka).slider().removeClass("special_slider");	//odstrani razred special slider
		$('#slider_' + spremenljivka + ' .ui-slider-handle').css('visibility', '');
	}
	//konec ureditve handle kot bunkico
	
	//ureditev bunk in elips za nakazovanje moznih odgovorov
	if (nakazi_odgovore == 1 && handle == 1){//ce zelimo bunke za nakazovanje odgovorov
		$('#slider_' + spremenljivka).slider().removeClass("classic_slider");	//odstrani razred s klasicnimi crticami
		$('#slider_' + spremenljivka).slider().addClass("circle_slider");	//dodaj razred z bunkicami za nakazovanje
		//$('#sliderbranching_' + spremenljivka + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
	}
	else if (nakazi_odgovore == 0 && handle == 0){ //drugace
		$('#slider_' + spremenljivka).slider().addClass("classic_slider");	//dodaj klasicen razred
		$('#slider_' + spremenljivka).slider().removeClass("circle_slider");	//odstrani razred circle slider
	}
	else if (nakazi_odgovore == 1 && handle == 0){ //drugace
		$('#slider_' + spremenljivka).slider().addClass("elipse_slider");	//dodaj klasicen razred
		//$('#slider_' + spremenljivka).slider().removeClass("circle_slider");	//odstrani razred circle slider
	}	
	//konec ureditve bunk in elips za nakazovanje moznih odgovorov
	
	if (nakazi_odgovore == 1){	//ce vklopimo nakazovanje odgovorov
		vmesne_Crtice = 1;		//vklopi crtice, ki so v bistvu sredstvo za nakazovanje moznih odgovorov
	}
	
	//konec ureditve handle kot bunkica in nakazovanje moznih odgovorov *******************************************************
	

	if (slider_MinMaxNumLabelNew == 1){
		minmaxlabela = "pip";
	} 
	else{
		minmaxlabela = "label";
	}
	
	if (vmesne_Crtice == 1){//ce je potrebno pokazati vmesne èrtice
		rest = "pip";
		//console.log('Èrtice');
	}

	else if (vmesne_Crtice == 0) {
		rest = false;
		//console.log('Brez èrtic');
	}
	

	//if (vmesne_labels == 1) {
	if (vmesne_labels == 1 || tip_vmesne_descr_labele != 0) {
		rest = "label";
	}
	
	if(slider_VmesneDescrLabel){
		if(tip_vmesne_descr_labele != 0 && custom==''){ //ce se je izbralo prednalozene vmesne opisne labele in ni prevoda prednalozenih label
			vmesne_opisne_labele = vmesne_descr_labele.split(";");
			max = vmesne_opisne_labele.length-1;	
		}else{	//ce se je izbralo Brez oz. custom opisne labele oz. imamo prevod prednalozenih label
			vmesne_opisne_labele = slider_CustomDescriptiveLabels.split(";");		
			max = vmesne_opisne_labele.length;
		}
	}
	
	$('#slider_'+spremenljivka)
		.slider({
			step: slider_handle_step,
			value: def,
			min: min,
			max: max,
			
			
			slide: function(event, ui) {
				$("input[name^='vrednost_"+spremenljivka+"']").attr('value', ui.value );
				if (slider_window_number == 0){
					// Sproti popravljamo vrednost v okencu ob slidu					
					$("#sliderText_"+spremenljivka).html(ui.value);
					
					// Premikamo okencek skupaj z sliderjem
					var delay = function() {
						$("#sliderText_"+spremenljivka).position({
							of: ui.handle,
							offset: "0, -37"
						});
					};
					// wait for the ui.handle to set its position
					setTimeout(delay, 5);
				}
			},
			
			// Prikazemo okencek s vrednostjo
			start: function (event, ui) {
				if (slider_window_number == 0){
					$("#sliderText_" + spremenljivka).position({//postavi okencek na pravo mesto ob neposrednem kliku na rocico
							of: ui.handle,
							offset: "0, -37"
					});
					$("#sliderText_"+spremenljivka).css('visibility', 'visible');
				}
				// Nastavimo vrednost ce samo kliknemo
				$("input[name^='vrednost_"+spremenljivka+"']").attr('value', def);		
				$('#slider_' + spremenljivka + ' .ui-slider-handle').css('visibility', '');
			},
			
			// Skrijemo okencek s vrednostjo
			stop: function (event, ui) {		
				/*$("#sliderText_"+spremenljivka).css('visibility', 'hidden');*/
			},
			
			create: function (event, ui) {
				//console.log('Create');
				//if ($("input[name^='vrednost_"+spremenljivka+"']").val() == ''){
					
					var percent_def = (def-min)/(max-min) * 100;
					if(percent_def < 0 || percent_def > 100){
						percent_def = 50;
					}
					
					//console.log('Tekst je: '+percent_def);
										
					$('#slider_'+spremenljivka+' .ui-slider-handle').css('left', percent_def+'%');
					
					// if(handle == 1){//ce smo skrili handle					
						// $('#slider_' + spremenljivka + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
					// }

				//}
				if (slider_window_number == 0){
					// Postavimo na zacetku okencek na pravo mesto
					$("#sliderText_"+spremenljivka).position({
						of: $('#slider_'+spremenljivka+' .ui-slider-handle'),
						offset: "0, -37"
					});
				}
			},
			change: function (event, ui) {
				//console.log('Create');
				//if ($("input[name^='vrednost_"+spremenljivka+"']").val() == ''){
					
/* 					var percent_def = (def-min)/(max-min) * 100;
					if(percent_def < 0 || percent_def > 100){
						percent_def = 50;
					}
					
					//console.log('Tekst je: '+percent_def);
										
					$('#slider_'+spremenljivka+' .ui-slider-handle').css('left', percent_def+'%'); */
					
					// if(handle == 1){//ce smo skrili handle					
						// $('#slider_' + spremenljivka + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
					// }

				//}
				//ce default vrednost (def_value) se razlikuje od trenutne (def => trenutna vrednost sliderja)
				if (def_value != def){
					$('#slider_' + spremenljivka + ' .ui-slider-handle').css('visibility', '');	//pokazi handle, ce je ta skrit (bunkica)
					// Postavimo na zacetku okencek na pravo mesto
					$("#sliderText_"+spremenljivka).position({
						of: $('#slider_'+spremenljivka+' .ui-slider-handle'),
						offset: "0, -37"
					});
					if(slider_window_number == 0){
						$("#sliderText_"+spremenljivka).css('visibility', 'visible');	//pokazi okno z vrednostjo
					}
				}
			}
		})
		.slider("pips",{
			rest: rest,
			first: minmaxlabela,	//skrij min in max vrednosti
			last: minmaxlabela,
			labels: vmesne_opisne_labele,
		});
	$('#slider_' + spremenljivka).slider("option", "value", def);//postavi rocico na mesto, kjer je izracunana default vrednost
	
	
	if(mobile == 1){//ce je mobilnik, spremeni slog za labele sliderja
		//console.log("mobilnik!");
		//spremeni slog za vse oznake
		$('#slider_' + spremenljivka + ' span.ui-slider-label').toggleClass('ui-slider-label-mobile');
		
		//spremeni slog za prvo oznako
		$('#slider_' + spremenljivka + ' span.ui-slider-pip-first span.ui-slider-label-mobile').toggleClass('ui-slider-label-mobile-first');

		//spremeni slog za zadnjo oznako
		$('#slider_' + spremenljivka + ' span.ui-slider-pip-last span.ui-slider-label-mobile').toggleClass('ui-slider-label-mobile-last');
		
	}
}


function slider_grid_init (mobile, spremenljivka, vrednost, min, max, def, handle, slider_handle_step, vmesne_labels, vmesne_Crtice, slider_MinMaxNumLabelNew, slider_window_number, vmesne_descr_labele, tip_vmesne_descr_labele, def_value, nakazi_odgovore, slider_VmesneDescrLabel, slider_CustomDescriptiveLabels, custom) {
	// Skrijemo vnosno polje - razen ce gre za missing
	$("input[name^=vrednost_"+vrednost+"_grid_]").css('display', 'none');
	$("#vrednost_"+vrednost+"_grid_1").css('display', 'none');
	
	var val = $("input[name^=vrednost_"+vrednost+"_grid_]").val() || null;
	
	var minmaxlabela = "label";//hrani nastavitev za minmax labele
	var rest = false;	//hrani nastavitve za vmesne èrtice z ("label") in brez label ("pip") ter odsotnost èrtic (false)
	var vmesne_opisne_labele = false;
	
	//ureditev handle kot bunkica in nakazovanje moznih odgovorov ************************************************************
	
	//ureditev handle kot bunkica	
	if (handle == 1){//ce zelimo skriti handle
		$('#slider_'+spremenljivka+'_'+vrednost).slider().removeClass("classic_slider");	//odstrani razred s klasicnim handle
		$('#slider_'+spremenljivka+'_'+vrednost).slider().addClass("special_slider");	//dodaj razred s handle v obliki bunkice					
		$('#slider_'+spremenljivka+'_' +vrednost + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
	}
	else if (handle == 0){ //drugace
		$('#slider_'+spremenljivka+'_'+vrednost).slider().removeClass("special_slider");	//odstrani razred special slider
		$('#slider_'+spremenljivka+'_'+vrednost).slider().addClass("classic_slider");	//dodaj klasicen razred
		//$('#slider_'+spremenljivka+'_' +vrednost + ' .ui-slider-handle').css('visibility', '');//pokazi handle
	}
	//konec ureditve handle kot bunkico
	
	//ureditev bunk in elips za nakazovanje moznih odgovorov
	if (nakazi_odgovore == 1 && handle == 1){//ce zelimo bunke za nakazovanje odgovorov
		$('#slider_' + spremenljivka+'_'+vrednost).slider().removeClass("classic_slider");	//odstrani razred s klasicnimi crticami
		$('#slider_' + spremenljivka+'_'+vrednost).slider().addClass("circle_slider");	//dodaj razred z bunkicami za nakazovanje
		//$('#sliderbranching_' + spremenljivka + ' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
	}
	else if (nakazi_odgovore == 0 && handle == 0){ //drugace
		$('#slider_' + spremenljivka+'_'+vrednost).slider().addClass("classic_slider");	//dodaj klasicen razred
		$('#slider_' + spremenljivka+'_'+vrednost).slider().removeClass("circle_slider");	//odstrani razred circle slider
	}
	else if (nakazi_odgovore == 1 && handle == 0){ //drugace
		$('#slider_' + spremenljivka+'_'+vrednost).slider().addClass("elipse_slider");	//dodaj klasicen razred
		//$('#slider_' + spremenljivka).slider().removeClass("circle_slider");	//odstrani razred circle slider
	}
	
	//konec ureditve bunk in elips za nakazovanje moznih odgovorov
	
	if (nakazi_odgovore == 1){	//ce vklopimo nakazovanje odgovorov
		vmesne_Crtice = 1;		//vklopi crtice, ki so v bistvu sredstvo za nakazovanje moznih odgovorov
	}
	
	//konec ureditve handle kot bunkica in nakazovanje moznih odgovorov *******************************************************
	
	if ( slider_MinMaxNumLabelNew == 1 ){
		minmaxlabela = "pip";
	} 
	else{
		minmaxlabela = "label";
	}
	
	if (vmesne_Crtice == 1){//ce je potrebno pokazati vmesne èrtice
		rest = "pip";
		//console.log('Èrtice');
	}
	else if (vmesne_Crtice == 0) {
		rest = false;
		//console.log('Brez èrtic');
	}
	
	//if (vmesne_labels == 1) {
	if (vmesne_labels == 1 || tip_vmesne_descr_labele != 0) {
		rest = "label";
	}
	
	if(slider_VmesneDescrLabel){
		if(tip_vmesne_descr_labele != 0 && custom==''){ //ce se je izbralo prednalozene vmesne opisne labele in ni prevoda prednalozenih label
			vmesne_opisne_labele = vmesne_descr_labele.split(";");
			max = vmesne_opisne_labele.length-1;	
		}else{	//ce se je izbralo Brez oz. custom opisne labele oz. imamo prevod prednalozenih label
			vmesne_opisne_labele = slider_CustomDescriptiveLabels.split(";");		
			max = vmesne_opisne_labele.length;
		}
	}
	
	$('#slider_'+spremenljivka+'_'+vrednost)
		.slider({
		step: slider_handle_step,
		value: def,
		min: min,
		max: max,
				
		slide: function(event, ui) {
				$("input[name^='vrednost_"+vrednost+"_grid_']").attr('value', ui.value );

				if (slider_window_number == 0){
					// Sproti popravljamo vrednost v okencu ob slidu
					$("#sliderText_"+spremenljivka+"_"+vrednost).html(ui.value);

					// Premikamo okencek skupaj z sliderjem
					var delay = function() {

						$("#sliderText_"+spremenljivka+"_"+vrednost).position({
							of: ui.handle,
							offset: "0, -37"
						});
					};
					// wait for the ui.handle to set its position
					setTimeout(delay, 5);
				}
			},
			
			// Prikazemo okencek s vrednostjo
			start: function (event, ui) {
				if (slider_window_number == 0){
					$("#sliderText_" + spremenljivka + "_" + vrednost).position({//postavi okencek na pravo mesto ob neposrednem kliku na rocico
							of: ui.handle,
							offset: "0, -37"
					});
					$("#sliderText_"+spremenljivka+"_"+vrednost).css('visibility', 'visible');
				}
				// Nastavimo vrednost ce samo kliknemo
				$("input[name^='vrednost_"+vrednost+"_grid_']").attr('value', def);
				$('#slider_' + spremenljivka + '_' + vrednost +' .ui-slider-handle').css('visibility', '');
			},
			


			// Skrijemo okencek s vrednostjo
			stop: function (event, ui) {		
				/*$("#sliderText_"+spremenljivka+"_"+vrednost).css('visibility', 'hidden');*/
			},
			

			create: function (event, ui) {
				
				var width = $("input[name^=vrednost_"+vrednost+"_grid_]").parent().width();
				width = width - 80;
				$("#slider_"+spremenljivka+"_"+vrednost).width(width);
				
				//if ($("input[name^='vrednost_"+vrednost+"_grid_']").val() == ''){
					
					var percent_def = (def-min)/(max-min) * 100;
					if(percent_def < 0 || percent_def > 100){
						percent_def = 50;

					}
					$('#slider_'+spremenljivka+'_'+vrednost+' .ui-slider-handle').css('left', percent_def+'%');
					// if(handle == 1){//ce smo skrili handle
						// $('#slider_'+spremenljivka+'_'+vrednost+' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
					// }
				//}


				if (slider_window_number == 0){
					// Postavimo na zacetku okencek na pravo mesto

					$("#sliderText_"+spremenljivka+"_"+vrednost).position({
						of: $('#slider_'+spremenljivka+'_'+vrednost+' .ui-slider-handle'),
						offset: "0, -37"
					});
				}
			},
			change: function (event, ui) {
				
/* 				var width = $("input[name^=vrednost_"+vrednost+"_grid_]").parent().width();
				width = width - 80;
				$("#slider_"+spremenljivka+"_"+vrednost).width(width);
				
				if ($("input[name^='vrednost_"+vrednost+"_grid_']").val() == ''){
					
					var percent_def = (def-min)/(max-min) * 100;
					if(percent_def < 0 || percent_def > 100){
						percent_def = 50;

					}
					$('#slider_'+spremenljivka+'_'+vrednost+' .ui-slider-handle').css('left', percent_def+'%');
					// if(handle == 1){//ce smo skrili handle
						// $('#slider_'+spremenljivka+'_'+vrednost+' .ui-slider-handle').css('visibility', 'hidden');//skrij handle
					// }
				} */
				//ce default vrednost (def_value) se razlikuje od trenutne (def => trenutna vrednost sliderja)
				if (def_value != def){
					$('#slider_'+spremenljivka+'_' +vrednost + ' .ui-slider-handle').css('visibility', '');//pokazi handle
					// Postavimo na zacetku vrednost na pravo mesto
					$("#sliderText_"+spremenljivka+"_"+vrednost).position({
						of: $('#slider_'+spremenljivka+'_'+vrednost+' .ui-slider-handle'),
						offset: "0, -37"
					});
					if(slider_window_number == 0){
						$("#sliderText_"+spremenljivka+"_"+vrednost).css('visibility', 'visible');	//pokazi okno z vrednostjo
					}
				}
			}
			
	//});
		})			
		.slider("pips",{
			rest: rest,
			first: minmaxlabela,	//skrij min in max vrednosti
			last: minmaxlabela,
			labels: vmesne_opisne_labele,
		});	
	$('#slider_' + spremenljivka + '_' + vrednost).slider("option", "value", def);//postavi rocico na mesto, kjer je izracunana default vrednost
	
	if(mobile == 1){//ce je mobilnik, spremeni slog za labele sliderja
		//console.log("mobilnik!");
		//spremeni slog za vse oznake
		$('#slider_' + spremenljivka+"_"+vrednost + ' span.ui-slider-label').toggleClass('ui-slider-label-mobile');
		//$('#slider_' + spremenljivka+"_"+vrednost + ' span.ui-slider-label').removeClass('ui-slider-label');
		
		//spremeni slog za prvo oznako
		$('#slider_' + spremenljivka+"_"+vrednost + ' span.ui-slider-pip-first span.ui-slider-label-mobile').toggleClass('ui-slider-label-mobile-first');
		//$('#slider_' + spremenljivka+"_"+vrednost + ' span.ui-slider-pip-first span.ui-slider-label-mobile').removeClass('ui-slider-label-mobile');
		
		//$('#slider_' + spremenljivka + ' span.ui-slider-pip-first span.ui-slider-label').toggleClass('ui-slider-label-mobile-first');
		//$('#slider_' + spremenljivka + ' span.ui-slider-pip-first span.ui-slider-label').removeClass('ui-slider-label');
				
		//spremeni slog za zadnjo oznako
		$('#slider_' + spremenljivka+"_"+vrednost + ' span.ui-slider-pip-last span.ui-slider-label-mobile').toggleClass('ui-slider-label-mobile-last');
		//$('#slider_' + spremenljivka+"_"+vrednost + ' span.ui-slider-pip-last span.ui-slider-label-mobile').removeClass('ui-slider-label-mobile');
		
		//$('#slider_' + spremenljivka + ' span.ui-slider-pip-last span.ui-slider-label').toggleClass('ui-slider-label-mobile-last');
		//$('#slider_' + spremenljivka + ' span.ui-slider-pip-last span.ui-slider-label').removeClass('ui-slider-label');
		
	}
	
}