var tableStatus;
var kodeKosarica = [];
var anketa_id = 0;

$(document).ready(function () {
    // pridobimo ID ankete, ko je dokument naložen
    anketa_id = $('#srv_meta_anketa_id').val()

    if (document.querySelector('#hierarhija-status')) {
        tableStatus = $('#hierarhija-status-admin').DataTable({
            "language": {
                "url": "modules/mod_hierarhija/js/vendor/datatables-slovenian.json"
            },
            "lengthMenu": [[50, 100, 200, 400, -1], [50, 100, 200, 400, "vse"]],
            // Prevzeto imamo prvo vrstico skrito, ker gre za urejanje
            "columnDefs": [
                {"visible": false, "targets": 0}
            ]
        });

        $.get('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=super-sifra&m=getAll').then(function (response) {
            var tabela = JSON.parse(response);
            sestaviTabeloSuperKodami(tabela);
        });

    }
});

/**
 * Sestavimo tabelo s superkodo in pripadajočimi hierarhijami
 * @param {object} objekt
 */
function sestaviTabeloSuperKodami(objekt) {
    if (objekt.length == 0)
        return null;

    $('.prikaz-superkod').show();

    var vrstice = '';

    $.each(objekt, function (superKoda, hierarhije) {
        vrstice += '<tr><td>' + superKoda + '</td>';
        vrstice += '<td><ul>';

        $.each(hierarhije, function (koda, hierarhija) {
            vrstice += '<li>' + hierarhija.hierarhija + '  |  <b>' + hierarhija.ucitelj + '</b></li>';
        });


        vrstice += '</ul></td></tr>';
    });

    $('.prikaz-superkod tbody').html(vrstice);
}

/**
 * Prikaže možnost urejanja superkode
 */
function prikaziUrejanjeSuperkode() {
    var column = tableStatus.column(0);

    column.visible(!column.visible());

    $('.kosarica').toggle();
}

/**
 * Izberemo ustrezno kodo
 *
 * @param {string} koda
 */
function dodajKodoVKosarico(koda) {

    if (!poisciKodo(kodeKosarica, koda))
        kodeKosarica.push({
            koda: koda,
            hierarhija: $('[data-hierarhija="' + koda + '"]').text(),
            email: $('[data-email="' + koda + '"]').text(),
        });

    generirajSeznamKod();
}

function generirajSeznamKod() {
    $("#seznamKod").html('');
    kodeKosarica.forEach(function (val, index) {
        $("#seznamKod").append('<li class="ui-state-default koda" id="' + val.koda + '"><span>' + (index + 1) + '</span>. hierarhija: <b>' + val.hierarhija + ' - ' + val.email + '</b> <div class="right modra izbrisi" onclick="izbrisiSifro(\'' + val.koda + '\')"><i class="fa fa-lg fa-trash-o" aria-hidden="true"></i></div></li>');
    });
}

function poisciKodo(kosarica, koda) {
    for (i = 0; i < kosarica.length; i++) {

        if (kosarica[i].koda == koda)
            return kosarica.splice(i, 1);

    }

    return false;
}

function izbrisiSifro(koda) {
    poisciKodo(kodeKosarica, koda);
    generirajSeznamKod();

    $('input[value="' + koda + '"]').attr('checked', false);
}


$(function () {
    $("#seznamKod").sortable({
        placeholder: "ui-state-highlight",
        update: function (event, ui) {
            $("#seznamKod li").each(function () {
                $(this).children('span').html($(this).index() + 1)
            });
        },
    }).disableSelection();

    $('#ustvari-superkodo').on('click', function () {
        var kode = $("#seznamKod").sortable("toArray");

        $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=super-sifra&m=shrani', {
            kode: kode
        }).then(function (response) {
            if(response.length == 0)
                return false;

            var tabela = JSON.parse(response);
            sestaviTabeloSuperKodami(tabela);

            kodeKosarica = [];
            $('.tabela-status input').attr('checked', false);
            generirajSeznamKod();
        })
    });
});