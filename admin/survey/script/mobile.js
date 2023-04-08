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
            zeynep.close();
            $('#fade').fadeOut();

            $('.mobile_menu_close').fadeOut('fast', function(){
                $('.mobile_menu_open').fadeIn('fast');
            });
      })

      // open zeynepjs side menu
      $('.mobile_menu_open').on('click', function () {
            zeynep.open();
            $('#fade').fadeIn();

            $('.mobile_menu_open').fadeOut('fast', function(){
                $('.mobile_menu_close').fadeIn('fast');
            });
      })
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

