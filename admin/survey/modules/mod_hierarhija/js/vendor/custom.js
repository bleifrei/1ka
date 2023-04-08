/************************************************
 DOCUMENT READY
 ************************************************/
var tabela = null;
var anketa_id = 0;

$(document).ready(function () {
    // pridobimo ID ankete, ko je dokument naložen
    anketa_id = $('#srv_meta_anketa_id').val();

    //vklopljeno iskanje za vse select box elemente
    $('.h-selected select.hierarhija-select').chosen();
    $('.h-selected.hierarhija-select').chosen();

    //Vklopi nice input file
    $("input[type=file]").nicefileinput({
        label: 'Poišči datoteko...'
    });


    //Data Tables konfiguracija za vpis šifrantov
    if ($('#vpis-sifrantov-admin-tabela').length > 0) {
        tabela = $('#vpis-sifrantov-admin-tabela').DataTable({
            "processing": true,
            "lengthMenu": [[20, 40, 100, 200, -1], [20, 40, 100, 200, "vse"]],
            "ajax": 'ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=get-datatables-data',
            "drawCallback": function (settings) {
                if (tabela.page.info().recordsTotal == 0) {
                    $('#div-datatables').hide();
                    $('#hierarhija-jstree-ime').hide();
                    $('#admin_hierarhija_jstree').hide();
                } else {
                    $('#div-datatables').show();
                    $('#hierarhija-jstree-ime').show();
                    $('#admin_hierarhija_jstree').show();
                }
            },
            "createdRow": function (row, data, rowIndex) {
                // gremo po vseh td elementih
                $.each($('td', row), function (colIndex) {

                    // SQL query nam vrne objek, ki ga uporabimo za datatables vpis vrstice
                    if (data[colIndex] && data[colIndex].label) {
                        // Vsaka celica ima id strukture, ki je potreben za urejanje uporabbnikov za posamezno vrstico
                        $(this).attr('data-struktura', data[colIndex].id);

                        // Vsaka celica ima številko nivoja - level
                        $(this).attr('data-level', data[colIndex].level);

                        // Prikaz podatkov
                        $(this).html(data[colIndex].label);
                    }

                });
            },
            "language": {
                "url": "modules/mod_hierarhija/js/vendor/datatables-slovenian.json"
            }
        });


    }


    // datatables za prikaz že vpisanih šifrantov
    if ($('#pregled-sifrantov-admin-tabela').length > 0) {
        $('#pregled-sifrantov-admin-tabela').DataTable({
            ajax: 'ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=get-datatables-data&m=1&p=1',
            "language": {
                "url": "modules/mod_hierarhija/js/vendor/datatables-slovenian.json"
            }
        });
    }


    // Klik na ikono za komentar
    $('.surveycomment').on('click', function () {
        dodajKomentar();
    });

    // Klik na ikono za upload logo - naloži logotip
    $('.logo-upload').on('click', function () {
        uploadLogo();
    });

    // Skrivamo filtrov in vprašanj pri analizah
    $('.znak').on('click', function (e) {
        var razred = e.currentTarget.className;

        if (razred == 'znak minus') {
            $('#div_means_dropdowns').animate('slow').hide();
            $('.minus').hide();
            $('.plus').show();
        } else {
            $('#div_means_dropdowns').animate('slow').show();
            $('.plus').hide();
            $('.minus').show();
        }
    });

    // Skrijemo error, ki se je pojavil
    $('.error-display').delay(10000).fadeOut('slow');
});
// uredi vrstico
// function urediVrsticoHierarhije(id) {
//     var anketa_id = $('#anketa_id').val();
//     var el = $('.btn-urejanje-hierarhije[data-id="' + id + '"]').parent().siblings().last();
//     var text = el.html().split("  -  ");
//
//     // pridobi vse uporabnike, ki so dodani na trenutno hierarhijo
//     var opcije = [];
//     // $.post("ajax.php?anketa=" + anketa_id + "&t=hierarhija-ajax&a=gradnja-hierarhije&m=get-uporabniki", {
//     //     id: id
//     // }).success(function (data) {
//     //     if (data == 0)
//     //         return opcije;
//     //
//     //     // vse emaili dodamo med opcije in polje, ki ga kasneje združimo v string
//     //     $.each(JSON.parse(data), function (key, val) {
//     //         opcije.push('<option value="' + key + '" ' + val.selected + '>' + val.uporabnik + '</option>');
//     //     });
//     //
//     //     el.html('Izbira: <b>' + text[0] + '</b><br/>Uporabniki:<select id="select2-email-' + id + '" multiple>' + opcije.join("") + '</seclect>');
//     //     $('.btn-urejanje-hierarhije[data-id="' + id + '"]').text('Vpiši').attr('onclick', 'vpisiVrsticoHierarhije(' + id + ')');
//     //
//     //     $('#select2-email-' + id).select2();
//     // });
//
//
// }

var vrsticaAktivnoUrejanje = {
    html: '',
    id: 0,
    izbris: 0
};

function urediVrsticoHierarhije(id) {
    // V kolikor je ponovno kliknil na urejanje, potem samo vrnemo in na ponovno neurejanje
    if (vrsticaAktivnoUrejanje.id == id) {
        // Vpišemo stare podatke vrstice, brez urejanja
        $('#vpis-sifrantov-admin-tabela .aktivno-urejanje').html(vrsticaAktivnoUrejanje.html);

        // Odstranimo razrede
        $('#vpis-sifrantov-admin-tabela .h-uporabnik').remove();
        $('#vpis-sifrantov-admin-tabela .aktivno-urejanje').removeClass();

        // če je bil uporabnik izbrisan potem osvežimo celotno tabelo
        if (vrsticaAktivnoUrejanje.izbris == 1)
            tabela.ajax.reload(null, false);


        // Ponastavimo globalno spremenljivko
        return vrsticaAktivnoUrejanje = {
            html: '',
            id: 0,
            izbris: 0
        };
    }

    // V kolikor obstaja podatek cele vrstice od prej in je aktivni razred . aktivno-urejanje, potem vsebino prekopiramo
    if (vrsticaAktivnoUrejanje.html.length > 0 && $('#vpis-sifrantov-admin-tabela .aktivno-urejanje').length > 0)
        $('#vpis-sifrantov-admin-tabela .aktivno-urejanje').html(vrsticaAktivnoUrejanje.html);


    // Izbriše ikonice za urejanje uprabnikov in odstrani aktivni razred urejanja
    $('#vpis-sifrantov-admin-tabela .h-uporabnik').remove();
    $('#vpis-sifrantov-admin-tabela .aktivno-urejanje').removeClass();


    // Vsi elementi, ki vsebujejo ID strukture
    var vrstica = $('[data-struktura="' + id + '"]').parent();
    var stolpci = vrstica.children('[data-struktura]');

    // Obarvamo ozadje vrstice
    vrstica.addClass('aktivno-urejanje');

    // Celotno vrstico shranimo globalno in tudi id
    vrsticaAktivnoUrejanje = {
        html: $('#vpis-sifrantov-admin-tabela .aktivno-urejanje').html(),
        id: id
    }

    // Pridobimo vse TD celice in v vsaki dodamo ikono ter uporabnike za urejati
    stolpci.each(function (key, val) {
        var self = this;
        var html = $(this).html().split("<br>");
        var idStrukture = $(this).attr('data-struktura');
        var uporabnikiHtml = '';

        // Ajax request, ki pridobi vse uporabnike za vsak nivo posebej
        $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=get-uporabniki', {
            id: idStrukture
        }, function (data) {
            var urejanjeUporabnika = '';

            // Ikona za pop up, kjer dodajamo še email
            urejanjeUporabnika = '<div class="h-uporabnik"><span class="faicon users icon-as_link" onclick="odpriPopup(' + idStrukture + ')"></span></div>';

            // Če imamo uporabnike na tem nivoju potem jih ustrezno dodamo
            if (data.length > 0) {
                var podatki = JSON.parse(data);

                // V kolikor imamo uporabnika samo na zadnjem nivoju, potem ni možnosti urejanja, ker ima opcijo briši nivo in uporabnika
                if ($(self).attr('data-level') == podatki.maxLevel) {

                    // Opcije za urejanje uporabnika ne potrebujemo na zadnjem nivoju
                    urejanjeUporabnika = '<div class="h-uporabnik"><span class="icon user-red" onclick="odpriPopup(' + idStrukture + ', 1)"></span></div>';

                    uporabnikiHtml = '<div class="h-uporabnik-prikazi">Uporabnik/i:' +
                        '<ul>';

                    // Dodamo vse uporabnike, ki so na tem nivoju
                    if (podatki.uporabniki) {
                        $.each(podatki.uporabniki, function (key, val) {
                            uporabnikiHtml += '<li>' + val.uporabnik + '</li>';
                        });
                    }

                    uporabnikiHtml += '</ul></div>';

                }
                else {
                    // Izpišemo uporabnike in možnost brisanja
                    uporabnikiHtml = '<div class="h-uporabnik-prikazi">Uporabnik/i:' +
                        '<ul>';

                    // Dodamo vse uporabnike, ki so na tem nivoju
                    if (podatki.uporabniki) {
                        $.each(podatki.uporabniki, function (key, val) {
                            uporabnikiHtml += '<li>' + val.uporabnik + ' <span class="icon brisi-x" data-id="' + val.id + '" onclick="izbrisiUporabnikaDataTables(' + val.id + ')"></span></li>';
                        });
                    }

                    uporabnikiHtml += '</ul></div>';
                }

            }

            $(self).html(html[0] + urejanjeUporabnika + uporabnikiHtml);

        });


    });

}

/**
 * Prikaži pop-up za uvoz vseh uporabnikov preko tekstovnega polja
 */
function uvoziUporabnike() {
    $('#fade').fadeTo('slow', 1);
    $('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=import-user&s=get');
}

function shraniVseVpisaneUporabnike() {
    var users = $('#users-email-import').val();

    if (users.length < 5)
        return false;

    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=import-user&s=post', {
        users: JSON.stringify(users)
    }).then(function (response) {
        // Pridobimoo uporabnike za select box
        var uporabniki = JSON.parse(response);

        gradnjaHierarhijeApp.user.dropdown = uporabniki;
        gradnjaHierarhijeApp.osebe.prikazi = false;

        // Zapremo Pop up
        vrednost_cancel();
    });
}

/**
 * Vrstico hierarhije kopiramo v možnost za urejanje uporabnikov, pridobimo zadnji id
 */
function kopirajVrsticoHierarhije(id) {
    // Poženemo funkcijo v datoteki custom-vue.js
    gradnjaHierarhijeApp.pridobiIdSifrantovInUporabnike(id);
}

// Odpre PopUp in naloži možnost za dodajanje novega uporabnika - textarea
function odpriPopup(id, last) {
    var last = last || 0;

    $('#fade').fadeTo('slow', 1);
    $('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=uredi-uporabnike-v-strukturi', {
        struktura: id,
        last: last
    });
}

/**
 * Zamenjamo email uporabnika na zadnjem nivoju z novim emailom - find and replace all
 */
function zamenjajUporabnikaZNovim() {
    $('#fade').fadeTo('slow', 1);
    $('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=zamenjaj-uporabnika-v-strukturi');
}

/**
 * Testno preverimo koliko uporabnikov se bo zamenjalo
 */
function testnoPreveriKolikoUporabnikovBoZamnjenihVStrukturi() {
    var findEmail = $('#find-email').val();
    var replaceEmail = $('#replace-email').val();


    if (errorPreverjanjeEmailaZaZamenjavo(findEmail, replaceEmail))
        return false;

    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=post-st-zamenjav', {
        'find_email': findEmail
    }).then(function (response) {
        var besedilo = 'Elektronski naslov <b>' + findEmail + '</b> ni bil najden med obstoječimi uporabniki in ga ni mogoče zamenjati.';

        if (response > 0)
            besedilo = 'Elektronski naslov <b>' + findEmail + '</b> bo zamenjan z naslovom <b>' + replaceEmail + '</b>.<br />Število zamenjav: <b>' + response + '</b>.';

        $('#st_zamenjav_uporabnikov').html(besedilo)
    });
}

function potriZamenjavoUporabnika() {
    var findEmail = $('#find-email').val();
    var replaceEmail = $('#replace-email').val();

    if (errorPreverjanjeEmailaZaZamenjavo(findEmail, replaceEmail))
        return false;

    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=post-zamenjaj-uporabnika-z-novim', {
        'find_email': findEmail,
        'replace_email': replaceEmail
    }).then(function (response) {

        tabela.ajax.reload(null, false);

        // zapremo popup
        $('#fade').fadeOut('slow');
        $('#vrednost_edit').hide();
    });
}

/**
 * Preverimo, če sta emaila pravilno vpisana tist, ki ga iščemo in tisti, ki je za zamenjavo
 * @param findEmail
 * @param replaceEmail
 * @returns {boolean}
 */
function errorPreverjanjeEmailaZaZamenjavo(findEmail, replaceEmail) {
    // Preden preverimo odstranimo vse errorje
    $('#find-email').siblings('.error-label').hide();
    $('#find-email').removeClass('error');
    $('#replace-email').siblings('.error-label').hide();
    $('#replace-email').removeClass('error')

    if (!preveriFormatEmail(findEmail)) {
        $('#find-email').siblings('.error-label').show();
        $('#find-email').addClass('error');

        return true;
    }

    if (!preveriFormatEmail(replaceEmail)) {
        $('#replace-email').siblings('.error-label').show();
        $('#replace-email').addClass('error');

        return true;
    }

    return false;
};


function preveriFormatEmail(email) {
    var EMAIL_REGEX = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    return EMAIL_REGEX.test(email);
}

// Shranimo email vpisanih oseb pri urejanju za specifično strukturo
function shrani_email(id, last) {
    var emails = $('#vpis-email-popup').val().split('\n');
    var last = last || 0;

    //Loop po vseh vpisanih emailih
    $.each(emails, function (key, val) {
        val = val.split(',');

        // V kolikor email ni pravilen ga odstranimo iz polja
        if (!preveriPravilnoVpisanmail(val[0])) {
            emails.splice(key, 1);
        } else {
            emails[key] = val;
        }
    });

    // V kolikor ni bil vpisan email, ampak je samo klik na potrdi
    if (typeof emails[0] == 'undefined')
        return 'error';

    // Posredujemo samo pravilne emaile
    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=gradnja-hierarhije&m=post-dodatne-uporabnike-k-strukturi', {
        uporabniki: JSON.stringify(emails),
        struktura: id,
        last: last
    }, function () {

        // osvežimo celoten DataTables
        tabela.ajax.reload(null, false);

        // Osvežimo tudi jsTree
        jstree_json_data(anketa_id, 1);

        // zapremo popup
        $('#fade').fadeOut('slow');
        $('#vrednost_edit').hide();

        // celotno strukturo shranimo v string in srv_hierarhija_save
        gradnjaHierarhijeApp.shraniUporabnikeNaHierarhijo();
    });

}

function preveriPravilnoVpisanmail(email) {
    var EMAIL_REGEX = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;

    return EMAIL_REGEX.test(email);
}

// Izbriši uporabnika iz DataTables
function izbrisiUporabnikaDataTables(id) {
    var str_id = $('[data-id="' + id + '"]').parents('[data-struktura]').attr('data-struktura');

    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=brisi&m=uporabnika', {
        uporabnik_id: id,
        struktura_id: str_id
    }).then(function () {
        // Če je uporabnik uspešno izbrisan iz baze, potem tudi izbrišemo iz DataTables
        $('[data-id="' + id + '"]').parent().remove();

        vrsticaAktivnoUrejanje.izbris = 1;
    });
}

// vpiši vrstico v bazo
function vpisiVrsticoHierarhije(id) {
    var polje = [];

    // vse izbrani ID oseb
    $('#select2-email-' + id + ' option:selected').each(function () {
        polje.push($(this).val());
    });

    $.post("ajax.php?anketa=" + anketa_id + "&t=hierarhija-ajax&a=gradnja-hierarhije&m=post-uporabniki", {
        uporabniki: JSON.stringify(polje),
        struktura: id
    }, function (data) {
        // v kolikor ni vpisanega uporabnika potem opozorimo
        if (data == 'uporabnik') {
            return swal({
                title: "Opozorilo!",
                text: "Uporabnik mora biti določen.",
                type: "error",
                timer: 2000,
                confirmButtonText: "OK"
            });
        }

        // osvežimo tabelo, ko smo vpisali podatke
        tabela.ajax.reload(null, false);
        jstree_json_data(anketa_id, 1);
    });

}

// datatables urejanje, brisanje
function brisiVrsticoHierarhije(id, osveziTabelo) {

    var osveziTabelo = osveziTabelo || 0;

    $.post("ajax.php?anketa=" + anketa_id + "&t=hierarhija-ajax&a=brisi_element_v_hierarhiji", {
        id: id
    }, function (p) {
        //Hierarhije je že zgrajena
        if (p == 2) {
            swal({
                title: "Hierarhija je zaklenjena!",
                text: "Brisanje ni več mogoče, ker je hierarhija zaklenjena za urejanje.",
                type: "error",
                timer: 2000,
                confirmButtonText: "OK"
            });
            //ko javimo napako moramo jstree osvežiti, ker v nasprotnem primeru js še vedno izbriše omenjen element
            jstree_json_data(anketa_id, 1);
        }

        //Hierarhije je že zgrajena
        if (p == 'obstaja') {
            swal({
                title: "Brisanje ni mogoče!",
                text: "Ne morete izbrisati omenjenega elementa, ker imate pod njem še izbrano hierarhijo.",
                type: "error",
                timer: 2000,
                confirmButtonText: "OK"
            });
            //ko javimo napako moramo jstree osvežiti, ker v nasprotnem primeru js še vedno izbriše omenjen element
            jstree_json_data(anketa_id, 1);
        }


        tabela.ajax.reload(null, false);
        jstree_json_data(anketa_id, 1);

        // celotno strukturo shranimo v string in srv_hierarhija_save
        gradnjaHierarhijeApp.shraniUporabnikeNaHierarhijo();
    });
}


//preverimo, če je obkljukano polje
function preveriCheckDodajEmail() {
    if ($("#dovoli-vpis-emaila").is(':checked')) {
        $('#vpis-emaila').show();
    }
    else {
        $('#vpis-emaila').val('').hide();
    }
}

/**
 * Opoyorimo v kolikor želi uporabni nadaljevati in ni shraniv emaila trenutnega uporabnika
 */
function opozoriUporabnikaKerNiPotrdilPodatkov(href) {
    var level = gradnjaHierarhijeApp.podatki.maxLevel;

    // V kolikor imamo uporabnika na zadnjem nivoju
    if (typeof gradnjaHierarhijeApp.osebe.nove[level] === 'object') {
        swal({
            title: "Opozorilo!",
            text: "Vnesli ste strukturo za dotičnega uporabnika, vendar omenjene podatke niste shranili. Ali jih želite izbrisati?",
            type: "error",
            showCancelButton: true,
            confirmButtonText: "Nadaljuj",
            cancelButtonText: "Prekliči"
        }, function (dismiss) {

            // V kolikor se uporabnik strinja,ga preusmerimo na naslednji korak
            if (dismiss)
                window.location.href = href;

        });
    } else {
        window.location.href = href;
    }

}

/**
 * Shrani komentar k hierarhiji
 */
function shraniKomentar() {

    var komentar = $('#hierarhija-komentar').val();
    var id = $('#hierarhija-komentar').attr('data-id');

    $.post("ajax.php?anketa=" + anketa_id + "&t=hierarhija-ajax&a=komentar-k-hierarhiji&m=post", {
        id: id,
        komentar: komentar
    }).success(function (podatki) {
        if (podatki == 1) {
            // zapremo popup
            $('#fade').fadeOut('slow');
            $('#vrednost_edit').hide();
        }
    });
}

/**
 * Predogled emaila za učitelje in managerje
 *
 *  1 - email za učitelje na zadnjem nivoju
 *  2 - email za managerje na vseh ostalih nivojih
 *
 * @param int vrsta - za katero vrsta emaila gre
 */
function previewMail(vrsta) {

    $('#fade').fadeTo('slow', 1);

    $('#vrednost_edit').html('').fadeIn('slow').load('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=ostalo&m=preview-mail', {
        vrsta: vrsta,
    });
}


/************************************************
 Hierarhija - jstree, bootstrap select
 ************************************************/
function jstree_json_data(anketa, refresh) {
    $.ajax({
        async: true,
        type: "GET",
        url: "ajax.php?anketa=" + anketa + "&t=hierarhija-ajax&a=json_jstree",
        dataType: "json",
        success: function (json) {
            if (typeof refresh === 'undefined') {
                jstree_vkljuci(json);
            }
            else {
                //v kolikor imamo jsTree že postavljen samo osvežimo podatke
                var $jstree = $('#admin_hierarhija_jstree').jstree(true);
                $jstree.settings.core.data = json;
                $jstree.refresh();
            }
        },
        error: function (xhr, ajaxOptions, thrownError) {
            console.log(thrownError);
        }
    });
}

function jstree_vkljuci(jsonData) {
    $("#admin_hierarhija_jstree").jstree({
        //'plugins': ['contextmenu', 'dnd', 'massload', 'sort', 'types'],
        'plugins': ['search', 'massload', 'contextmenu'],
        'contextmenu': {
            "items": function ($node) {
                return {
                    "Delete": {
                        "label": "Briši",
                        "action": function (data) {
                            var ref = $.jstree.reference(data.reference),
                                sel = ref.get_selected();
                            if (!sel.length) {
                                return false;
                            }
                            ref.delete_node(sel);

                            var url = window.location.href;
                            var par = url.match(/(?:anketa=)(\w+)/g);
                            var anketa_id = par[0].slice(7, par[0].length);

                            //pošljemo POST ukaz, da pobrišemo element
                            brisiVrsticoHierarhije($node.id);
                        }
                    },
                    //"Edit": {
                    //    "label": "Urejanje uporabnika",
                    //    'action': function () {
                    //
                    //
                    //    }
                    //}
                }
            }
        },
        'core': {
            "animation": 0,
            "check_callback": true,
            "expand_selected_onload": true,
            "themes": {
                "name": "proton",
                "responsive": true
            },
            "data": jsonData,
        },
        //"types": {
        //    "#": {
        //        "max_children": 1,
        //        "max_depth": 20,
        //        "valid_children": ["root"]
        //    },
        //    "root": {
        //        "icon": "glyphicon glyphicon-home",
        //        "valid_children": ["default"]
        //    },
        //    "default": {
        //        "valid_children": ["default", "file"]
        //    },
        //    "file": {
        //        "icon": "glyphicon glyphicon-home",
        //        "valid_children": []
        //    }
        //}
    }).on('loaded.jstree', function () {
        $("#admin_hierarhija_jstree").jstree('open_all');
    }).bind("select_node.jstree", function (event, data) {
        //V kolikor kliknemo na hierarhijo z levim miškinim klikom, potem v meniju select izberemo ustrezne vrednosti
        // ko vrednost zberemo iz jstree je potrebno baziti, da preverimo, če je neznan event, ker v nasprotnem primeru submit sproži omenjeno skripto
        if (event.isTrigger == 2 && (typeof data.event !== "undefined")) {
            //Pošljemo id, kamor je bil izveden klik in nato prikažemo ustrezne select opcije
            var url = window.location.href;
            var par = url.match(/(?:anketa=)(\w+)/g);
            var anketa_id = par[0].slice(7, par[0].length);

            $.post("ajax.php?anketa=" + anketa_id + "&t=hierarhija-ajax&a=posodobi_sifrante", {
                id: data.node.id
            }).success(function (podatki) {
                var sifrant = JSON.parse(podatki);
                var st_naprej = 2;


                // najprej je potrebno vsa polja skriti, da nato prikažemo samo potrebna
                for (var st = 1; st <= sifrant.user.max_level; st++) {
                    $('.h-nivo-' + st).val('').trigger("liszt:updated"); //update chosen list -> v novejših verzijah je chosen:update
                    $('.h-level-' + st).removeClass('h-selected').hide();
                }

                //naredimo zanko po vseh nivojih
                $.each(sifrant, function (key, val) {
                    //izluščimo samo številke,ker uporabnika ne potrebujemo
                    if ($.isNumeric(key)) {
                        $('.h-level-' + key).addClass('h-selected').show();
                        $('.h-nivo-' + key).val(val.select).chosen().trigger("liszt:updated");
                    }
                });

                // prikažemo še možnost vnos naslednjega elementa
                var naslednjiSifrant = data.node.parents.length + 1;

                // Če uporabnik ni admin, potem ime ŠOLE ne vnesemo v HIERARHIJO in zato nam prikaže en element premalo in je potrebno +1, da nam prikaže možnost vnosa tudi naslednjega elementa
                if (sifrant.user.id_strukture != 'admin')
                    naslednjiSifrant += 1;

                $('.h-level-' + naslednjiSifrant).addClass('h-selected').show();
                $('.h-nivo-' + naslednjiSifrant).val('').chosen();


            });

        }
    });

}

function dodajKomentar() {
    $('#fade').fadeTo('slow', 1);
    $('#vrednost_edit').wrapAll('<div class="fixed-position"></div>').html('').fadeIn('slow').load('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=komentar-k-hierarhiji&m=get');
}

/**
 * Odpre popup za nalaganje logotipa
 */
function uploadLogo() {
    $('#fade').fadeTo('slow', 1);
    $('#vrednost_edit').wrapAll('<div class="fixed-position"></div>').html('').fadeIn('slow').load('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=upload-logo&m=get', function () {

        //Vklopi nice input file
        $("input[type=file]").nicefileinput({
            label: 'Poišči datoteko...'
        });

    });
}

/**
 * Izbriše logotip, ki je že naložen
 * @param $id
 */
function izbrisiLogo($id) {
    var id = $('form > input[name="id"]').val();

    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=upload-logo&m=delete', {
        id: id
    }).success(function () {
        $('#hierarhija-logo').remove();
    });
}
/****************************  HIERARHIJA END ***************************/

function printElement(ime) {
    var divToPrint = $('.printElement');

    if (ime == 'Status') {
        var objekt = divToPrint.html();
        divToPrint = '<table class="printTable" id="printHierarhijaStatus">' + objekt + '</table>';
    } else if (ime == 'Analize') {
        divToPrint = document.getElementById('div_means_data').innerHTML;
    }

    var newWin = window.open('', ime, 'scrollbars=1');

    newWin.document.write('<html><head><title>Okno za tiskanje - ' + ime + '</title>');
    newWin.document.write('<meta http-equiv="Cache-Control" content="no-store"/>');
    newWin.document.write('<meta http-equiv="Pragma" content="no-cache"/>');
    newWin.document.write('<meta http-equiv="Expires" content="0"/>');

    newWin.document.write('<link rel="stylesheet" href="css/print.css#13042017">');
    newWin.document.write('<link rel="stylesheet" href="css/style_print.css" media="print">');
    newWin.document.write('</head><body class="print_analiza">');
    newWin.document.write('<div id="printIcon">');
    newWin.document.write('<a href="#" onclick="window.print(); return false;">Natisni</a>');
    newWin.document.write('</div>');

    newWin.document.write(divToPrint);
    newWin.document.write('</body></html>');
    newWin.focus();

    newWin.document.close();

}

/**
 * Posodobi nastavitve v bazi, za pošiljanje kod samo za učitelja ali tudi za vse
 *
 * @param string {vrednost}
 */
function posodobiPosiljanjeKod(vrednost, val) {
    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=ostalo&m=opcije', {
        name: vrednost,
        value: val,
        method: (val == 0 ? 'delete' : '')
    }).success(function () {
        $('#poslji-kode').val(vrednost);
    });
}

/**
 * POšlji obvestilo učiteljem, kateri še niso bili obveščeni
 *
 * @param {}
 * @return
 */
function obvestiUciteljeZaResevanjeAnkete() {
    $.post('ajax.php?anketa=' + anketa_id + '&t=hierarhija-ajax&a=ostalo&m=poslji-email-samo-uciteljem').success(function () {
        $('#obvesti-samo-ucitelje').html('<span style="color:#fa4913;">Elektronsko sporočilo s kodo je bilo posredovano vsem učiteljem, ki so na zgornjem seznamu</span>');
    });
}