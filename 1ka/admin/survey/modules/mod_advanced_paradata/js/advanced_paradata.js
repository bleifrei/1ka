// Glavna funkcija, ki se vedno klice za logiranje
function logEvent(event_type, event, data){

	//console.log(event_type + ' ' + event + '  ' + _session_id);

	// Pridobimo id sessiona na strani
	var session_id = _session_id;

	// Pridobimo id ankete
	var anketa = $('#srv_meta_anketa_id').val();

	// Pridobimo trenutno stran
	if (typeof srv_meta_grupa_id != 'undefined'){
		var page = srv_meta_grupa_id;
	}
	else if ($('#outercontainer').hasClass('intro')){
		var page = '-1';
	}  
	else if ($('#outercontainer').hasClass('concl')){
		var page = '-2';
	}
	else{
		var page = '0';
	}

	// Pridobimo user id, recnum in jezik
	var user = _usr_id;
	var recnum = _recnum;
	var language = _lang;

	// Zabelezimo cas dogodka
	var timestamp = Date.now();
	
	// Opcijski parameter data
	data = data || '';
    
    
    //  Pri submit oz unload eventu mora biti klic sinhron
    if(event == 'unload_page'){
        $.ajax({
            type: 'POST',
            url: '../main/survey/ajax.php?t=parapodatki&a=logData',
            data: {
                session_id: session_id,
                anketa: anketa,
                page: page,
                usr_id: user,	
                recnum: recnum,
                language: language,
                
                event_type: event_type,	
                event: event,
                timestamp: timestamp,
                    
                data: data
            },
            /*success: success,
            dataType: dataType,*/
            async: false
        });
    }
    else{
        $.post('../main/survey/ajax.php?t=parapodatki&a=logData', {
            session_id: session_id,
            anketa: anketa,
            page: page,
            usr_id: user,	
            recnum: recnum,
            language: language,
            
            event_type: event_type,	
            event: event,
            timestamp: timestamp,
                
            data: data
        });
    }
}


// EVENTI, KI JIH SPREMLJAMO
$(function () {

	// LOAD PAGE
    window.addEventListener('load', function () {
       
		var event_type = 'page';
		var event = 'load_page';
		
		// Belezimo parametre za velikost in zoom
		var data = {
			devicePixelRatio: window.devicePixelRatio,
			width: window.screen.width,
			height: window.screen.height,
			availWidth: window.screen.availWidth,
			availHeight: window.screen.availHeight,
			jquery_windowW: $(window).width(),
			jquery_windowH: $(window).height(),
			jquery_documentW: $(document).width(),
			jquery_documentH: $(document).height(),
		}
		
		// Logiramo load
		logEvent(event_type, event, data);
		
		// Logiramo vidna vprasanja
		//visibleQuestions();
    });
    
    
	// RESIZE
	window.addEventListener('resize', function () {
		
		var data = {
			pos_x: $(window).width(),
			pos_y: $(window).height(),
			value: window.devicePixelRatio
		}

		var event_type = 'other';
		var event = 'resize';
		
		// Logiramo scroll
		logEvent(event_type, event, data);
		
		// Logiramo vidna vprasanja
		//visibleQuestions();
    });

    // PINCH ZOOM
    var pinch_scaling = false;
    var pinch_distance = 0;
        window.addEventListener('touchstart', function (e) {

        // Zaznamo 2 prsta
        if (e.touches.length === 2) {
            pinch_scaling = true;

            // Izracunamo razdaljo med prstoma
            pinch_distance = Math.hypot(
                e.touches[0].screenX - e.touches[1].screenX,
                e.touches[0].screenY - e.touches[1].screenY
            );
        }
    });
    window.addEventListener('touchend', function (e) {

        if(pinch_scaling){
            pinch_scaling = false;

            // Izracunamo razdaljo med prstoma
            var pinch_distance2 = Math.hypot(
                e.touches[0].screenX - e.changedTouches[0].screenX,
                e.touches[0].screenY - e.changedTouches[0].screenY
            );

            // Preracunamo razliko v razdalji
            var diff = pinch_distance2 - pinch_distance;
            var zoom = Math.round((diff / pinch_distance * 100));

            var data = {
                value: zoom
            }

            var event_type = 'other';
            var event = 'pinch_resize';

            // Logiramo scroll
            logEvent(event_type, event, data);
        }
    });
    

    // ORIENTATION CHANGE
	window.addEventListener('orientationchange', function () {
        
        var value = '';

        if(window.orientation == 90 || window.orientation == -90)
            value = 'landscape';
        else
            value = 'portrait';

		var data = {
			value: value
		}

		var event_type = 'other';
		var event = 'orientation_change';
		
		// Logiramo scroll
		logEvent(event_type, event, data);
	});

    // SCROLL
    var scroll_time_prev = 0;
    window.addEventListener('scroll', function () {
        
        // Dobimo trenuten cas
        var scroll_time = new Date().getTime();

        // Ce je ze poteklo dovolj casa od prejsnjega zaznavanja
        if ((scroll_time - scroll_time_prev > 50) || scroll_time_prev == 0) {

            var event_type = 'other';
            var event = 'scroll_page';

            var data = {
                pos_x: (window.pageXOffset || document.documentElement.scrollLeft) - (document.documentElement.clientLeft || 0),
                pos_y: (window.pageYOffset || document.documentElement.scrollTop)  - (document.documentElement.clientTop || 0)
            }

            // Logiramo scroll
            logEvent(event_type, event, data);
            
            // Logiramo vidna vprasanja
            //visibleQuestions();

            // Shranimo cas za interval
            scroll_time_prev = scroll_time; 
        }
    });
		
    // BLUR (leave to another tab etc..)
    window.addEventListener('blur', function () {
        
		var event_type = 'other';
		var event = 'blur';
		
		logEvent(event_type, event);
    });

    // FOCUS
    window.addEventListener('focus', function () {
		
		var event_type = 'other';
		var event = 'focus';
		
		logEvent(event_type, event);
    });
	
	// MOUSE CLICK
	window.addEventListener('click', function (event) {

		var div_id = $(event.target).attr('id');
        
        // Ce je id prazen najdemo prvega parenta z id-jem
        if(div_id == null){
            div_id = $(event.target).closest('[id]').attr('id') + ' (parent)';
        }

		var data = {
			pos_x: event.pageX,
			pos_y: event.pageY,
			div_type: event.target.tagName,
			div_id: div_id,
			div_class: $(event.target).attr('class')
		}
		
		var event_type = 'other';
		var event_name = 'click';
		
        logEvent(event_type, event_name, data);

        // Posebej shranimo se mouse movement do tega klika
        movementClickEvent(event);
	});
	


	// INPUT TEXT, TEXTAREA FOCUS
    $('input[type=text], textarea').bind('focus', function () {
        
		// Ce gre za textbox v selectu ga ignoriramo
		if(!$(this).parent().hasClass('chzn-search')){

			var event_type = 'vrednost';
			var event = 'text_enter';
			
			var id = $(this).attr('id');
			var id_array = id.split('_');
			var spr_id = id_array[1];	
			var vre_id = id_array[3];
			
			var data = {
				spr_id: spr_id,
				vre_id: vre_id,
				value: $(this).val()
			}
			
            logEvent(event_type, event, data);
            

            // Preverimo ce gre za polje drugo - potem oznacimo tudi checkbox/radio
            if($(this).parent().parent().parent().hasClass('tip_1') || $(this).parent().parent().parent().hasClass('tip_2')){

                event = 'radio_checkbox_change';

                data = {
                    spr_id: spr_id,
                    vre_id: vre_id,
                    value: 1
                }
            
                logEvent(event_type, event, data);
            }
		}
    });
	// INPUT TEXT, TEXTAREA BLUR
    $('input[type=text], textarea').bind('blur', function () {
        
		// Ce gre za textbox v selectu ga ignoriramo
		if(!$(this).parent().hasClass('chzn-search')){
			
			var event_type = 'vrednost';
			var event = 'text_leave';
			
			var id = $(this).attr('id');
			var id_array = id.split('_');
			var spr_id = id_array[1];	
			var vre_id = id_array[3];
			
			var data = {
				spr_id: spr_id,
				vre_id: vre_id,
				value: $(this).val()
			}
			
			logEvent(event_type, event, data);
		}
    });
	
    // INPUT RADIO, CHECKBOX CHANGE
    $('input[type=radio], input[type=checkbox]').bind('click', function () {

		var event_type = 'vrednost';
		var event = 'radio_checkbox_change';
		
		var id = $(this).attr('id');
		var id_array = id.split('_');

		// Ce gre za tabelo
		if(id_array[2] == 'grid' || id_array[0] == 'grid'){
			var spr_id = id_array[1];	
			var vre_id = id_array[3];

			// Ce je 'value' gre za missing
			if(spr_id == 'missing'){
				spr_id = id_array[3];
				vre_id = id_array[5];
			}
		}
		// Navadni radio oz. checkbox
		else{
			var spr_id = id_array[1];	
			var vre_id = id_array[3];

			// Ce je 'value' gre za missing
			if(spr_id == 'value'){
				spr_id = id_array[3];
				vre_id = id_array[5];
			}
		}
		
		var val;
		if ($(this).is(':checked'))
			val = 1;
		else
			val = 0
			
		var data = {
			spr_id: spr_id,
			vre_id: vre_id,
			value: val
		}

		logEvent(event_type, event, data);
    });
	
    // SELECT CHANGE
    $('select').bind('change', function () {
		
		var event_type = 'vrednost';
		var event = 'select_change';
		
		var id = $(this).attr('id');
		var id_array = id.split('_');
		var spr_id = id_array[1];	
		var vre_id = $(this).val();
		
		var data = {
			spr_id: spr_id,
			vre_id: vre_id,
			value: vre_id
		}
		
		logEvent(event_type, event, data);
    });



    // MOUSE MOVE
    var movements = [];
    var currentMovement = {
        timeStart: 0,
        timeEnd: 0,
        duration: 0,

        startPosX: 0,
        startPosY: 0,
        endPosX: 0,
        endPosY: 0,

        distance_traveled: 0,
        prevPosX: 0,
        prevPosY: 0
    }

    // Lovimo vse preimke miske
    window.addEventListener('mousemove', function (event) {
        var clicked = false;
        movementTrack(event, clicked);
    });

    // Belezimo premik v array objektov
    var time_prev = new Date().getTime();
    function movementTrack(event, clicked){

        // Dobimo trenuten cas
        var time = new Date().getTime();

        // Ce je ze poteklo dovolj casa od prejsnjega zaznavanja
        if (time - time_prev > 50 || clicked) {
            
            // Preverimo, ce je to ze nov premik - zaenkrat tretiramo nov premik ce je pavze vec kot 300ms
            if(time - currentMovement.timeEnd > 300 || clicked){

                // Dodamo trenuten premik v array premikov - trik da ne insertamo reference ampak dejansko kopijo objekta
                if(currentMovement.timeStart !== 0 && currentMovement.startPosX !== undefined && currentMovement.endPosX !== undefined){
                    movements.push(JSON.parse(JSON.stringify(currentMovement)));
                }

                // Resetiramo trenuten premik in mu nastavimo vse parametre
                currentMovement.timeStart = time;
                currentMovement.timeEnd = time;

                currentMovement.startPosX = event.pageX;
                currentMovement.startPosY = event.pageY;

                currentMovement.endPosX = event.pageX;
                currentMovement.endPosY = event.pageY;

                currentMovement.distance_traveled = 0;
                currentMovement.prevPosX = event.pageX;
                currentMovement.prevPosY = event.pageY;
            }
            // Gre se za star premik - samo posodobimo end time in end position
            else{
                currentMovement.timeEnd = time;
                currentMovement.duration = time - currentMovement.timeStart;

                currentMovement.endPosX = event.pageX;
                currentMovement.endPosY = event.pageY;

                // Izracunamo razdaljo prepotovano od prejsnjega premika       
                var a = currentMovement.prevPosX - event.pageX;
                var b = currentMovement.prevPosY - event.pageY;
                var distance = Math.sqrt(a*a + b*b);

                currentMovement.distance_traveled += distance;
                currentMovement.prevPosX = event.pageX;
                currentMovement.prevPosY = event.pageY;
            }

            // Shranimo cas za interval
            time_prev = time; 

            /*console.log(currentMovement);*/
        } 
    }

    // Poklicemo ob vsakem kliku in shranimo podatke o premikanju med klikoma
    function movementClickEvent(event) {

        var event_type = 'movement';
        var event = 'mouse_move';
        
        var clicked = true;
        movementTrack(event, clicked);

        // Loop cez vse premike
        for (var i=0; i<movements.length; i++) {

            var data = {
                time_start: movements[i].timeStart,
                time_end: movements[i].timeEnd,
                pos_x_start: movements[i].startPosX,
                pos_y_start: movements[i].startPosY,
                pos_x_end: movements[i].endPosX,
                pos_y_end: movements[i].endPosY,
                distance: movements[i].distance_traveled
            }

            logEvent(event_type, event, data);
        }
        
        // Pocitstimo array vseh premikov
        movements = [];
    }

    

    // VISIBLE
    /*function visibleQuestions() {
		
		var event_type = 'other';
		var event = 'visible_question';
		
        var q = $('.grupa').find('.spremenljivka');
        var arr = [];

        $.each(q, function (i, val) {
            if ($(val).is(':visible') && isVisible(val)) {
                if ($(val).attr('id'))
                    arr[arr.length] = $(val).attr('id').substring(14);
            }
        })

        var log = "" + arr;

        if (log != prev_log) {
            			
			var data = {
				log: log
			}
			
			logEvent(event_type, event, data);
			
            prev_log = log;
        }
    }
    var prev_log = '';

	// Vrnemo ce je element viden
    function isVisible(elem) {

        var containerTop = $(window).scrollTop();
        var containerBottom = containerTop + $(window).height();

        var elemTop = $(elem).offset().top;
        var elemBottom = elemTop + $(elem).height();

        return ((elemBottom >= containerTop) && (elemTop <= containerBottom)
        && (elemBottom <= containerBottom) && (elemTop >= containerTop) );
    }*/	
})