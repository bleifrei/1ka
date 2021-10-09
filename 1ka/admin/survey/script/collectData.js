


// Ce generiramo veliko datoteko, prikazemo popup za loading in poklicemo generiranje preko ajaxa
function collectDataAjax(){

    var srv_meta_anketa_id = $("#srv_meta_anketa_id").val();    

    $('#fade').fadeTo('slow', 1);

    // Prikazemo popup
    $('#collect_data_popup').fadeIn('slow', function(){
        
        // Poklicemo ajax za generiranje datoteke
        $.ajax({
            type: 'POST',
            async: false,
            url:  'ajax.php?t=dataFile&a=prepareFiles',
            data: {anketa:srv_meta_anketa_id},
            success:  function(response) {

                // Uspesno kreirana datoteka
                if(response == '1'){
                    location.reload(); 
                }
                else{
                    alert(lang['srv_collectdata_failed']);
                }
            }
        });  
    });
}