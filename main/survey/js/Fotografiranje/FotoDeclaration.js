/* 
 * Author: Uros Podkriznik
 * Date: 29.7.2016
 * 
 * Deklaracija tipa vprasanja Fotografiranje/Selfi
 */

//osnovni id-ji tagov brez predpon - za vse fotografije v eni grupi
var vse_kamere_ids = [];

/**
 * Deklarira tipe vprasanj fotografiraj
 * Preveri, ce browser podpira funkcije za kamere ter kamero nastavi - odpre se popup za dovoljenje
 * @param {type} inpid - standard id html tagov tagov
 * @returns {undefined}
 */
function FotoDeclaration(inpid, site_url){
    vse_kamere_ids.push(inpid);
    
    // use the proper vendor prefix
    navigator.getMedia = ( navigator.getUserMedia || 
    navigator.webkitGetUserMedia ||
    navigator.mozGetUserMedia ||
    navigator.msGetUserMedia);

    //za IE in Safari, ker se ne podpirata getmedia
    //if(navigator.getMedia){
        //nastavi kamero
        Webcam.set({
            width: 320,
            height: 240,
            image_format: 'jpeg',
            jpeg_quality: 90,
            swfURL: site_url+'main/survey/js/Fotografiranje/webcam.swf'
        });
        Webcam.attach( inpid );
    //}     
}

/**
 * Ob kliku na gumb za fotografiranje se izvede ta F
 * @param {type} inpid - standard id html tagov tagov
 * @returns {undefined}
 */
function take_snapshot(inpid) {
    
    // take snapshot and get image data
    Webcam.snap( function(data_uri) {

        // display results in page
        $('#fotoresults_'+inpid).html('<img src="'+data_uri+'"/>');

        var raw_image_data = data_uri.replace(/^data\:image\/\w+\;base64\,/, '');
        $('#foto_'+inpid).val(raw_image_data);

        $('#fotoresults_delete_'+inpid).show();
    });
}

// Pobrisemo snapshot
function delete_snapshot(inpid) {
    $('#fotoresults_'+inpid).html('<p>'+lang['srv_resevanje_foto_pre_result']+'</p>');
    $('#foto_'+inpid).val('');

    $('#fotoresults_delete_'+inpid).hide();
}

// Pobrisemo upload fotografije
function delete_upload_foto(inpid) {
    $('#'+inpid).val(null);

    //var reader = new FileReader();

    //reader.onload = function (e) {
        $('#upload_foto_result_'+inpid).css("display", "none"); 
        $('#upload_foto_result_'+inpid).attr('src', "#");
    //};

    //reader.readAsDataURL(input.files[0]);

    $('#upload_fotoresults_delete_'+inpid).hide();
}
