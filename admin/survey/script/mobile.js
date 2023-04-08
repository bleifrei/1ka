function mobile_init(){

    // init zeynepjs side menu
    var zeynep = $('.mobile_menu').zeynep({
        opened: function () {

        },
        closed: function () {

        }
    })

    // dynamically bind 'closing' event
    zeynep.on('closing', function () {

    })

    // handle zeynepjs overlay click
    $('.mobile_menu_close').on('click', function () {

        $('#fade').fadeOut();

        mobile_menu_close(zeynep);
    })

    // open zeynepjs side menu
    $('.mobile_menu_open').on('click', function () {

        if($('#fade').is(':hidden')){
            $('#fade').fadeIn();
        }
        else{
            mobile_settings_close(zeynep);
        }
        
        mobile_menu_open(zeynep);
    })

    // handle settings overlay click
    $('.mobile_settings_close').on('click', function () {

        $('#fade').fadeOut();

        mobile_settings_close(zeynep);
    })

    // open settings side menu
    $('.mobile_settings_open').on('click', function () {

        if($('#fade').is(':hidden')){
            $('#fade').fadeIn();
        }
        else{
            mobile_menu_close(zeynep);
        }

        mobile_settings_open(zeynep);
    })
}

// Odpremo mobile meni na levi
function mobile_menu_open(zeynep){

    zeynep.open();

    $('.mobile_menu_open').fadeOut('fast', function(){
        $('.mobile_menu_close').fadeIn('fast');
    });
}

// Zapremo mobile meni na levi
function mobile_menu_close(zeynep){

    zeynep.close();

    $('.mobile_menu_close').fadeOut('fast', function(){
        $('.mobile_menu_open').fadeIn('fast');
    });
}

// Odpremo settings meni na desni
function mobile_settings_open(){

    $('.mobile_settings').animate({"margin-right": '+=85vw'},200,'linear');

    $('.mobile_settings_open').fadeOut('fast', function(){
        $('.mobile_settings_close').fadeIn('fast');
    });
}

// Zapremo settings meni na desni
function mobile_settings_close(callback){

    $('.mobile_settings').animate({"margin-right": '-85vw'},200,'linear');

    $('.mobile_settings_close').fadeOut('fast', function(){
        $('.mobile_settings_open').fadeIn('fast');
    });

    if (typeof callback == "function")
        callback();
}


// Popup za dodajanje vprasanja na mobile
function mobile_add_question_popup(){
    $('.mobile_add_question_popup').fadeIn();
}

// Popup za dodajanje vprasanja na mobile
function mobile_add_question_popup_close(){
    $('.mobile_add_question_popup').fadeOut();
}

// Popup za dodajanje vprasanja na mobile
function mobile_add_question(tip){

    mobile_add_question_popup_close();

    $.post('ajax.php?t=branching&a=spremenljivka_new', {
        spremenljivka: 0,
        'if': 0,
        endif: 1,
        copy: 0,
        tip: tip,
        podtip: 0,
        anketa: srv_meta_anketa_id
    }, function (data) {
        if (!data) return;

        refreshLeft(data.nova_spremenljivka_id, function(){

            // Scroll do novega vprasanja
            $("html, body").animate({ 
                scrollTop: $('#spremenljivka_content_'+data.nova_spremenljivka_id).offset().top - 100 
            }, 1000);
        });

    }, 'json');
}

