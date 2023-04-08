function anketa_user_dostop(uid, aid) {

  var anketa = aid || srv_meta_anketa_id;

  $('#fade').fadeTo('slow', 1);
  $('#vrednost_edit').show().load('ajax.php?t=dostop&a=anketa_user_dostop', {
    uid: uid,
    anketa: anketa
  });
}
function anketa_user_dostop_close() {

    $('#vrednost_edit').html('').removeClass('edit_user').hide();

    $('#fade').fadeOut('slow');
}
function anketa_user_dostop_save(aid) {

  var anketa = aid || srv_meta_anketa_id;
  var form = $('form#dostop').serializeArray();

  form[form.length] = {name: 'anketa', value: anketa};

  $.post('ajax.php?t=dostop&a=anketa_user_dostop_save', form, function () {
    window.location.reload();
  });
}

function edit_user(uid) {

    $('#fade').fadeTo('slow', 1);

    $('#vrednost_edit').addClass('edit_user').show().load('ajax.php?t=dostop&a=edit_user', {uid: uid});
}
function edit_user_close() {
    
    $('#vrednost_edit').removeClass('edit_user').hide().html('');
    
	$('#fade').fadeOut('slow');
}

function dodeljeni_uporabniki_display(manager) {

    $('#fade').fadeTo('slow', 1);

    $('#vrednost_edit').addClass('dodeljeni_uporabniki').show().load('ajax.php?t=dostop&a=dodeljeni_uporabniki_display', {manager: manager});
}
function dodeljeni_uporabniki_close() {
    
    $('#vrednost_edit').removeClass('dodeljeni_uporabniki').hide().html('');

	$('#fade').fadeOut('slow');

    //location.reload();
}
function dodeljeni_uporabniki_remove(manager, user) {

    if (confirm(lang['srv_manager_remove_alert'])){
        $('#vrednost_edit').load('ajax.php?t=dostop&a=edit_remove_user_admin', {manager: manager, user: user});
    }
}
function dodeljeni_uporabniki_add(manager, user) {

    var user = $('#add_user_id').val();

    $('#vrednost_edit').load('ajax.php?t=dostop&a=admin_add_user_popup', {manager: manager, user: user});
}

function dostop_language(chk) {

  var edit = $('#edit').is(':checked');
  var test = $('#test').is(':checked');
  var publish = $('#publish').is(':checked');

  if (edit && test && publish /*$(chk).is(':checked')*/) {

    $('#dostop_language_edit').attr('value', '0');
    $('.dostop_language').attr('disabled', true).attr('checked', true);

  }
  else {

    $('#dostop_language_edit').attr('value', '1');
    $('.dostop_language').removeAttr('disabled');
  }
}

function dostop_anketar(chk) {

  // Ce vklopimo da je anketar ugasnemo ostale checkboxe in jih disablamo
  if (chk.checked) {
    $('#dostop input[type=checkbox]').each(function () {
      if ($(this).attr('id') != 'phone') {
        $(this).attr('disabled', true).attr('checked', false);
      }
    });
  }
  else {
    $('#dostop input[type=checkbox]').each(function () {
      if ($(this).attr('id') != 'phone') {
        $(this).attr('disabled', false);
      }

      if ($(this).attr('id') == 'dashboard' || $(this).attr('id') == 'edit' || $(this).attr('id') == 'test' || $(this).attr('id') == 'publish' || $(this).attr('id') == 'data' || $(this).attr('id') == 'analyse') {
        $(this).attr('checked', true);
      }
    });
  }
}

/**
 * V kolikor je vključen modul hierarhija, potem za vsakega uporabnika vidimo
 * tudi pravice - hierarchy type
 */
function hierarhijaPravice(anketa_id, user) {
  var anketa = anketa_id || srv_meta_anketa_id;
  var user = user || null;

  if (user == null) {
    return false;
  }

  var tip = $('#hierarchy-type-change option:selected').val();

  $.post('ajax.php?anketa=' + anketa + '&t=hierarhija-ajax&a=spremeni_tip_hierarhije', {
    anketa: anketa,
    user: user,
    tip: tip
  });
}

/**
 * Obstoječemu uporabniku omogočimo dostop do SA modula
 */
function dodeliSAdostopUporabniku() {
  $('#fade').fadeTo('slow', 1);

  $('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?t=sa-uporabniki&a=get');
}

function shraniSAuporabnika() {
  $.post('ajax.php?t=sa-uporabniki&a=add', {
    email: $('#sa-email').val(),
    id: $('#sa-id').val(),
    ustanova: $('#sa-ustanova').val(),
    aai: $('#sa-aai').val()
  }).success(function () {
    location.reload();
  });
}

function preveriVpisanEmailZaSAdostop() {
  var email = $('#sa-email').val();

  $('.sa-potrdi').show();
  $('#sa-aai').removeAttr('disabled');
  $('#sa-organizacija').removeAttr('disabled');
  $('#sa-email-sporocilo').html('');
  $('#sa-id').val('');

  $.post('ajax.php?t=sa-uporabniki&a=check', {
    email: email
  }).success(function (data) {
    data = JSON.parse(data);

    if (data.tip == 'error') {
      $('.sa-potrdi').hide();
      $('#sa-aai').attr('disabled');
      $('#sa-organizacija').attr('disabled');


      $('#sa-email-sporocilo').html(data.sporocilo).removeClass('moder').addClass('red');
    }
    else if (data.tip == 'success') {
      $('#sa-email-sporocilo').html(data.sporocilo).removeClass('red').addClass('moder');
      $('#sa-id').val(data.id);
    }
  });
}

/**
 * Izbrišemo pravice uporabniku za dostop do SA modula
 * @param id
 */
function izbrisiSAuporabnika(id) {
  $.post('ajax.php?t=sa-uporabniki&a=delete', {
    id: id
  }).success(function () {
    location.reload();
  });
}

function urediSAuporabnika(id) {
  $.post('ajax.php?t=sa-uporabniki&a=edit', {
    id: id
  }).success(function (data) {
    $('#fade').fadeTo('slow', 1);

    $('#vrednost_edit').html(data).fadeIn('slow');
  });

}

/**
 * Posodobimo podatke obstoječemu uporabniku
 * @param id
 */
function posodobiSAuporabnika(id) {
  $.post('ajax.php?t=sa-uporabniki&a=update', {
    id: id,
    email: $('#sa-email').val(),
    ustanova: $('#sa-ustanova').val(),
    aai: $('#sa-aai').val()
  }).success(function () {
    location.reload();
  });
}

/**
 * Vpogled v kartico uporabnika
 * @param id
 */
function preveriSAuporabnika(id) {
  $.post('ajax.php?t=sa-uporabniki&a=show', {
    id: id
  }).success(function (data) {
    $('#fade').fadeTo('slow', 1);

    $('#vrednost_edit').html(data).fadeIn('slow');
  });
}

/**** END SA modul ****/

//Dodamo jQuery za DataTables
var tabelaDataTables;
var siteUrl = $('meta[name="site-url"]').attr("content");

$(document).ready(function () {

  // Naloži datatables samo, kadar je knjižnica tudi naložena
  if ($.fn.dataTable) {
    $.fn.dataTable.moment('DD.MM.YYYY');

    if ($('#survey_list_users').length > 0) {
      $('#survey_list_users').DataTable({
        dom: 'Bfrtip',
        deferRender: true
      });
    }

    if ($('#all_users_list').length > 0) {
      tabelaDataTables = $('#all_users_list').DataTable({
        lengthMenu: [[50, 500, 1000, 5000, 10000], [50, 500, 1000, 5000, 10000]],
        select: true,
        order: [[ 11, "desc" ]],
        lengthChange: true,
        serverSide: true,
        ajax: {
          "url": siteUrl+"admin/survey/ajax.php?t=dostop&a=all_users_list",
          "type": "post"
        },
        dom: 'Blfrtip',
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
        ],
        deferRender: true,
        language: {
          "url": siteUrl+"admin/survey/script/datatables/Slovenian.json"
        }
      });
    }

    if ($('#my_users_list').length > 0) {
        tabelaDataTables = $('#my_users_list').DataTable({
                "ajax": {
                "url": siteUrl+"admin/survey/ajax.php?t=dostop&a=my_users_list",
                "type": "post"
            },
            serverSide: true,
            lengthMenu: [[50, 500, 1000, 5000, 10000], [50, 500, 1000, 5000, 10000]],
            dom: 'Blfrtip',
            deferRender: true,
            select: true,
            buttons: [
            ],
            language: {
            "url": siteUrl+"admin/survey/script/datatables/Slovenian.json"
            },
            fnInitComplete : function() {
                if ($(this).find('tbody tr td').hasClass('dataTables_empty')) {
                    $(this).parent().parent().hide();
                }
            } 
        });
    }

    if ($('#deleted_users_list').length > 0) {
      $('#deleted_users_list').DataTable({
        "ajax": {
          "url": siteUrl+"admin/survey/ajax.php?t=dostop&a=delete_users_list",
          "type": "post"
        },
        dom: 'Blfrtip',
        deferRender: true,
        select: true,
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
            title: '1KA - Izbrisani uporabniki',
            bom: true,
            exportOptions: {
              columns: ':visible'
            }
          },
          {
            extend: 'excel',
            title: '1KA - Izbrisani uporabniki',
            bom: true,
            exportOptions: {
              columns: ':visible'
            }
          },
          {
            extend: 'pdf',
            title: '1KA - Izbrisani uporabniki',
            exportOptions: {
              columns: ':visible'
            }
          },
          'colvis'
        ],
        language: {
          "url": siteUrl+"admin/survey/script/datatables/Slovenian.json"
        }
      });
    }

    if ($('#unsigned_users_list').length > 0) {
      $('#unsigned_users_list').DataTable({
        "ajax": {
          "url": siteUrl+"admin/survey/ajax.php?t=dostop&a=unsigned_users_list",
          "type": "post"
        },
        lengthMenu: [[50, 500, 1000, 5000, 10000], [50, 500, 1000, 5000, 10000]],
        dom: 'Blfrtip',
        deferRender: true,
        select: true,
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
            title: '1KA - Odjavljeni uporabniki',
            bom: true,
            exportOptions: {
              columns: ':visible'
            }
          },
          {
            extend: 'excel',
            title: '1KA - Odjavljeni uporabniki',
            bom: true,
            exportOptions: {
              columns: ':visible'
            }
          },
          {
            extend: 'pdf',
            title: '1KA - Odjavljeni uporabniki',
            exportOptions: {
              columns: ':visible'
            }
          },
          'colvis'
        ],
        language: {
          "url": siteUrl+"admin/survey/script/datatables/Slovenian.json"
        }
      });
    }

    if ($('#unconfirmed_mail_user_list').length > 0) {
      tabelaDataTables = $('#unconfirmed_mail_user_list').DataTable({
        "ajax": {
          "url": siteUrl+"admin/survey/ajax.php?t=dostop&a=unconfirmed_mail_user_list",
          "type": "post"
        },
        lengthMenu: [[50, 500, 1000, 5000, 10000], [50, 500, 1000, 5000, 10000]],
        dom: 'Blfrtip',
        deferRender: true,
        select: true,
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
            title: '1KA - Nepotrjeni uporabniki',
            bom: true,
            exportOptions: {
              columns: ':visible'
            }
          },
          {
            extend: 'excel',
            title: '1KA - Nepotrjeni uporabniki',
            bom: true,
            exportOptions: {
              columns: ':visible'
            }
          },
          {
            extend: 'pdf',
            title: '1KA - Nepotrjeni uporabniki',
            exportOptions: {
              columns: ':visible'
            }
          },
          'colvis'
        ],
        language: {
          "url": siteUrl+"admin/survey/script/datatables/Slovenian.json"
        }
      });
    }

    // Select2 za dodajanje uporabnika
    if($('.js-obstojeci-uporabniki-admin-ajax').length>0){
      $('.js-obstojeci-uporabniki-admin-ajax').select2({
        minimumInputLength: 3,
        ajax: {
          url: 'ajax.php?t=dostop&a=find_user',
          dataType: 'json'
        }
      });
    }
  }

  $('#manager-email').on('keyup', function(){
    if($(this).val().length > 3) {
      $.post('ajax.php?t=dostop&a=find_user', {
        uemail: $(this).val()
      }, function (data) {

        if(data == "error"){
          $('#manager-email').removeClass('success').addClass('error');
          $('#manager-email-obvestilo').text(lang['srv_user_not_exist']).removeClass('success').addClass('error').show();
          $('#manager-email-submit').hide();
        } else {
          $('#manager-email').removeClass('error').addClass('success');
          $('#manager-email-obvestilo').text(lang['srv_user_exist']).removeClass('error').addClass('success').show();
          $('#manager-email-submit').show();
        }

      });
    } else {
      $('#manager-email-obvestilo').hide();
    }
  });

});

/**
 * Uporabnika potrdimo, da iz seznama nepotrjenih pride v seznam potrjenih
 * @param id
 */
function potrdiNepotrjenegaUporabnika(id) {
  $.post(siteUrl+"admin/survey/ajax.php?t=dostop&a=unconfirmed_mail_user_list&m=accept", {
    uid: id
  }, function (request) {
    tabelaDataTables.ajax.reload();
  });
}

/**
 * Izbrišemo uporabnika iz seznama nepotrjenih uporabnikov
 * @param id
 */
function izbrisiNepotrjenegaUporabnika(id) {
  $.post(siteUrl+"admin/survey/ajax.php?t=dostop&a=unconfirmed_mail_user_list&m=delete", {
    uid: id
  }, function (request) {
    tabelaDataTables.ajax.reload();
  });
}

/**
 * Pri vseh uporabnikih lahko uporabnika izbrišemo ali bannamo
 * @param id
 * @param action
 */
function vsiUporabnikiAkcija(id, action) {
  var action = action || 'delete';

  if(action == 'delete' && !confirm(lang['srv_survey_list_users_confirm_delete_warning'])){
      return false;
  }



  $.post(siteUrl+"admin/survey/ajax.php?t=dostop&a=all_users_list&m=" + action, {
    uid: id
  }, function (request) {
    tabelaDataTables.ajax.reload();
  });
}
