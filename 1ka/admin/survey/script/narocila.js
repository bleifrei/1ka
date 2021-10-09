// Disablamo elemente za export ce niso na voljo v paketu
function userAccessExport(){
    
    $("a.user_access_locked").each(function(index) {               
        
        $(this).attr("href", "#");
        $(this).removeAttr("target");
        $(this).removeAttr("onclick");

        
        what = $(this).attr('user-access');
        if(typeof what === typeof undefined || what === false)
            var what = 'export';

        $(this).click(function(){ 
            popupUserAccess(what);
        });
    });
}

// Disablamo elemente za filtriranje ce niso na voljo v paketu
function userAccessFilters(){
    
    $("#div_analiza_filtri_right ul li span").each(function(index) {               
        
        $(this).removeAttr("onclick");

        var what = 'filters';

        $(this).click(function(){ 
            popupUserAccess(what);
        });
    });
}


// Prikaz popupa da funkcionalnost ni na voljo v paketu
function popupUserAccess(what) {
    
    $('#fade').fadeTo('slow', 1);
    $("#popup_user_access").load('ajax.php?t=userAccess&a=displayNoAccessPopup', {what: what, anketa: srv_meta_anketa_id});
    $("#popup_user_access").show();
}

// Prikaz popupa da funkcionalnost ni na voljo v paketu - zapri
function popupUserAccess_close() {

	$("#popup_user_access").hide();
	$('#fade').fadeOut('slow');
}


// Tabela z vsemi narocili za admine
function prepareNarocilaTableAdmin(){

    $("#user_narocila").DataTable({
        order: [[ 4, "desc" ]],
        lengthMenu: [[50, 500, 1000], [50, 500, 1000]],
        select: false,
        lengthChange: true,
        deferRender: true,
        dom: 'Blfrtip',
        responsive: true,
        columnDefs: [
            {responsivePriority: 1, targets: 0},
            {responsivePriority: 2, targets: 7},
            {responsivePriority: 3, targets: 4}
        ],
        language: {
            "url": siteUrl+"admin/survey/script/datatables/Slovenian.json"
        },
        buttons: [
            {
              extend: 'copy',
              exportOptions: {
                columns: ':visible'
              }
            },
            {
              extend: 'print',
              exportOptions: {
                columns: ':visible'
              }
            },
            {
              extend: 'csv',
              title: '1KA - Seznam vseh uporabnikov',
              bom: true,
              exportOptions: {
                columns: ':visible'
              }
            },
            {
              extend: 'excel',
              title: '1KA - Seznam vseh uporabnikov',
              bom: true,
              exportOptions: {
                columns: ':visible'
              }
            },
            {
              extend: 'pdf',
              title: '1KA - Seznam vseh uporabnikov',
              orientation: 'landscape',
              pageSize: 'LEGAL',
              exportOptions: {
                columns: ':visible'
              }
            }/*,
            'colvis'*/
        ]
    });
}

// Tabela z vsemi placili za admine
function preparePlacilaTableAdmin(){

    $("#user_placila").DataTable({
        order: [[ 2, "desc" ]],
        lengthMenu: [[50, 500, 1000], [50, 500, 1000]],
        select: false,
        lengthChange: true,
        deferRender: true,
        dom: 'Blfrtip',
        responsive: true,
        columnDefs: [
            {responsivePriority: 1, targets: 0},
            {responsivePriority: 2, targets: 3},
            {responsivePriority: 3, targets: 2}
        ],
        language: {
            "url": siteUrl+"admin/survey/script/datatables/Slovenian.json"
        },
        buttons: [
            {
              extend: 'copy',
              exportOptions: {
                columns: ':visible'
              }
            },
            {
              extend: 'print',
              exportOptions: {
                columns: ':visible'
              }
            },
            {
              extend: 'csv',
              title: '1KA - Seznam vseh uporabnikov',
              bom: true,
              exportOptions: {
                columns: ':visible'
              }
            },
            {
              extend: 'excel',
              title: '1KA - Seznam vseh uporabnikov',
              bom: true,
              exportOptions: {
                columns: ':visible'
              }
            },
            {
              extend: 'pdf',
              title: '1KA - Seznam vseh uporabnikov',
              orientation: 'landscape',
              pageSize: 'LEGAL',
              exportOptions: {
                columns: ':visible'
              }
            },
            'colvis'
        ]
    });
}


// Pridobimo predracun preko api-ja in redirectamo
function getNarociloPredracun(narocilo_id){

    //$.post(siteUrl+'frontend/payments/api.php?action=get_predracun', {narocilo_id : narocilo_id}, function(response){
    $.post('ajax.php?t=userNarocila&a=getPredracun', {narocilo_id : narocilo_id}, function(response){

        var pdf_url = response.replace(/\\\//g, "/");
        pdf_url = pdf_url.replace(/['"]+/g, '');

        window.location = pdf_url;
    });
}

// Pridobimo racun preko api-ja in redirectamo
function getNarociloRacun(narocilo_id){

    //$.post(siteUrl+'frontend/payments/api.php?action=get_racun', {narocilo_id : narocilo_id}, function(response){
    $.post('ajax.php?t=userNarocila&a=getRacun', {narocilo_id : narocilo_id}, function(response){

        var pdf_url = response.replace(/\\\//g, "/");
        pdf_url = pdf_url.replace(/['"]+/g, '');

        window.location = pdf_url;
    });
}


// Urejanje narocila
function displayNarociloPopup(narocilo_id){

    $('#fade').fadeTo('slow', 1);

    $("#user_narocila_popup").load('ajax.php?t=userNarocila&a=displayNarociloPopup', {narocilo_id: narocilo_id});
    $("#user_narocila_popup").show();
}
// Urejanje narocila - shrani
function urediNarociloSave(){

    var form_serialize = $("#edit_narocilo").serializeArray();
    
    $("#narocila").load('ajax.php?t=userNarocila&a=editNarocilo', form_serialize, function () {

        $('#user_narocila_popup').hide().html('');
	    $('#fade').fadeOut('slow');
    });
}
// Urejanje narocila - placaj
function urediNarociloPay(narocilo_id){
    
    $("#narocila").load('ajax.php?t=userNarocila&a=payNarocilo', {narocilo_id: narocilo_id, payment_method: '1'}, function () {

        $('#user_narocila_popup').hide().html('');
	    $('#fade').fadeOut('slow');
    });
}
// Urejanje narocila - placaj eracun
function urediNarociloPayEracun(narocilo_id){
    
    $("#narocila").load('ajax.php?t=userNarocila&a=payNarociloEracun', {narocilo_id: narocilo_id, payment_method: '1'}, function () {

        $('#user_narocila_popup').hide().html('');
	    $('#fade').fadeOut('slow');
    });
}
// Urejanje narocila - zapri
function urediNarociloClose(){

    $('#user_narocila_popup').hide().html('');
    
	$('#fade').fadeOut('slow');
}

// Brisanje narocila
function brisiNarocilo(narocilo_id){

    if(confirm('Ste prepričani?')){
        
        $.post('ajax.php?t=userNarocila&a=deleteNarocilo', {narocilo_id: narocilo_id}, function () {
        
        });
    }
}


// Urejanje placila
function displayPlaciloPopup(placilo_id){

    $('#fade').fadeTo('slow', 1);

    $("#user_placila_popup").load('ajax.php?t=userPlacila&a=displayPlaciloPopup', {placilo_id: placilo_id});
    $("#user_placila_popup").show();
}
// Urejanje placila - shrani
function urediPlaciloSave(){

    var form_serialize = $("#edit_placilo").serializeArray();
    
    $("#placila").load('ajax.php?t=userPlacila&a=editPlacilo', form_serialize, function () {

        $('#user_placila_popup').hide().html('');
	    $('#fade').fadeOut('slow');
    });
}
// Urejanje placila - shrani
function createPlaciloSave(){

    var form_serialize = $("#create_placilo").serializeArray();
    
    $("#placila").load('ajax.php?t=userPlacila&a=createPlacilo', form_serialize, function () {

        $('#user_placila_popup').hide().html('');
	    $('#fade').fadeOut('slow');
    });
}
// Urejanje placila - zapri
function urediPlaciloClose(){

    $('#user_placila_popup').hide().html('');
    
	$('#fade').fadeOut('slow');
}

// Brisanje placila
function brisiPlacilo(placilo_id){

    if(confirm('Ste prepričani?')){
        $("#placila").load('ajax.php?t=userPlacila&a=deletePlacilo', {placilo_id: placilo_id});
    }
}

// Storniranje placila
function stornirajPlacilo(placilo_id){

    if(confirm('Ste prepričani?')){  
        $("#placila").load('ajax.php?t=userPlacila&a=stornirajPlacilo', {placilo_id: placilo_id});
    }
}

// Nastavi filtriranje po statusu
function filterNarocila(status, checked){

    if(checked)
        var value = 1;
    else
        var value = 0;

    $("#narocila").load('ajax.php?t=userNarocila&a=filterNarocila', {status: status, value: value});
}