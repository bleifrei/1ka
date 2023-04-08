//hierarhija means - povprečja
function means_init() {
}

//posodobimo filter analiz
function posodobil_filter_analiz() {
    var filter_vrednosti = {};
    $('.filter-analize').each(function () {
        if ($(this).val())
            filter_vrednosti[$(this).attr('name')] = $(this).val(); //poberemo samo vpisane podatke
    });

    //če imamo prazen objekt, ni izbranega filtra
    if (jQuery.isEmptyObject(filter_vrednosti))
        filter_vrednosti = 0;


    $.post("ajax.php?t=hierarhy-means&a=change", {
        anketa: srv_meta_anketa_id,
        filter_vrednosti: filter_vrednosti,
    }, function () {
        change_hierarhy_means();
    });


}

/**
 * Funkcija spremeni izbiro šifrantov v kolikor gre za izbiro po šifrantih ali pa po učiteljih
 *
 * @param {string} vrsta
 * @return html reload
 */
function posodobiPrikazHierarhije(vrsta) {
    var vrsta = vrsta || 'filtri';


    if (vrsta == 'ucitelji') {
        $('#ucitelji').show();
        $('.filtri-ucitelji').show();
        $('.hierarhija-filtri-levi').hide();
        $('#filter-po-ucitelju').chosen();
    }

    if (vrsta == 'filtri') {
        $('#ucitelji').hide();
        $('.filtri-ucitelji').hide();
        $('#predmeti-in-ucitelji').hide();
        $('#hierarhija-specificni-ucitelj h2').html('').hide();
        $('.hierarhija-filtri-levi').show();
    }

    $.post("ajax.php?t=hierarhy-means&a=pobrisi-filter", {
        anketa: srv_meta_anketa_id,
        vrsta: vrsta
    }, function () {
        setTimeout(function () {
            change_hierarhy_means();
        }, 200)
    });

}

/**
 * Posodobimo filter prikaza rezultatov, na podlagi izbire osvežimo Izbiro ustreznega učitelja predmet
 *  - agregirano: za enega učitelja skupaj povsod kjer uči
 *  - predmetih: prikažemo rezultate samo za specifični predmet
 * @param vrednost {string}
 */
function posodobiPrikazFiltraPoUciteljih(vrednost){
  var vrednost = vrednost || 'agregirano';

  if (vrednost == 'predmeti') {
    $('#ucitelji').hide();
    $('#predmeti-in-ucitelji').show();
    $('#hierarhija-specificni-ucitelj h2').html('').show();
    $('#filter-po-ucitelju-in-predmetu').chosen();

  }else {
    $('#predmeti-in-ucitelji').hide();
    $('#ucitelji').show();
    $('#hierarhija-specificni-ucitelj h2').html('').hide();
    $('#filter-po-ucitelju').chosen();
  }

  $.post("ajax.php?t=hierarhy-means&a=posodobi-seznam-za-ucitelje", {
    anketa: srv_meta_anketa_id,
    vrsta: vrednost
  }, function () {
    setTimeout(function () {
      change_hierarhy_means();
    }, 200)
  });

}

/**
 * Prikaže seznam vseh vprašanj, ki jih imamo
 *
 * @param {intiger} prikaz
 */
function tooglePrikazVprasanja(prikazi) {
    var prikazi = prikazi || 0;

    if (prikazi == 1) {
        $('#meansRightDropdowns').animate('slow').show();
        $('.prikazi').hide();
        $('.skrij').show();
    } else {
        $('#meansRightDropdowns').animate('slow').hide();
        $('.skrij').hide();
        $('.prikazi').show();
    }
}

function posodobi_izbranega_ucitelja() {
    var id = $('#filter-po-ucitelju').val();

    $.post("ajax.php?t=hierarhy-means&a=posodobi-ucitelja", {
        anketa: srv_meta_anketa_id,
        user_id: id
    }, function(response) {
        change_hierarhy_means();
    });
}

function posodobi_izbran_predmet() {
  var id = $('#filter-po-ucitelju-in-predmetu').val();

  $.post("ajax.php?t=hierarhy-means&a=posodobi-izbran-predmet", {
    anketa: srv_meta_anketa_id,
    strukutra_id: id
  }, function(response) {
      $('#hierarhija-specificni-ucitelj h2').html(response);

    change_hierarhy_means();
  });
}

function change_hierarhy_means(action) {
    $("#div_means_dropdowns").fadeTo(100, 0.2);
    $("#div_means_data").fadeTo(100, 0.2);

    var sequence1 = new Array();
    var spr1 = new Array();
    var grid1 = new Array();
    var sequence2 = new Array();
    var spr2 = new Array();
    var grid2 = new Array();

    var filter_vrednosti = {};
    $('.filter-analize').each(function () {
        if ($(this).val())
            filter_vrednosti[$(this).attr('name')] = $(this).val(); //poberemo samo vpisane podatke
    });


    if (action == 'rotate') {
        $('select[name=means_variable_2]').each(function (index, el) {
            sequence1.push($(el).val());
        });
        $('select[name=means_variable_1]').each(function (index, el) {
            sequence2.push($(el).val());
        });
        $('select[name=means_variable_2] option:selected').each(function (index, el) {
            spr1.push($(el).attr("spr_id"));
            grid1.push($(el).attr("grd_id"));
        });
        $('select[name=means_variable_1] option:selected').each(function (index, el) {
            spr2.push($(el).attr("spr_id"));
            grid2.push($(el).attr("grd_id"));
        });

    } else {
        // prebereomo dropdown variabli
        $('select[name=means_variable_1]').each(function (index, el) {
            sequence1.push($(el).val());
        });
        $('select[name=means_variable_2]').each(function (index, el) {
            sequence2.push($(el).val());
        });
        $('select[name=means_variable_1] option:selected').each(function (index, el) {
            spr1.push($(el).attr("spr_id"));
            grid1.push($(el).attr("grd_id"));
        });
        $('select[name=means_variable_2] option:selected').each(function (index, el) {
            spr2.push($(el).attr("spr_id"));
            grid2.push($(el).attr("grd_id"));
        });
    }

    // pridobimo strukturo, če obstzaja
    var strukturaId = $('#id-strukture').val() || null;

    $("#div_means_dropdowns").load("ajax.php?t=hierarhy-means&a=changeDropdown", {
        anketa: srv_meta_anketa_id,
        sequence1: sequence1,
        sequence2: sequence2,
        spr1: spr1,
        spr2: spr2,
        grid1: grid1,
        grid2: grid2,
        strukturaId: strukturaId
    }, function () {
        if (spr1 && spr2) {
            $("#div_means_data").load("ajax.php?t=hierarhy-means&a=change", {
                anketa: srv_meta_anketa_id,
                sequence1: sequence1,
                sequence2: sequence2,
                spr1: spr1,
                spr2: spr2,
                grid1: grid1,
                grid2: grid2,
                strukturaId: strukturaId
            }, function () {

                $("#div_means_dropdowns").fadeTo(100, 1);
                $("#div_means_data").fadeTo(100, 1);
            });
        }
    });
}


function hierarhy_means_add_new_variable(which) {

    var sequence = new Array();
    var spr = new Array();
    var grid = new Array();

    if (which == '2') {
        //$('#crossRightHolder #crosstab_add_new').hide();
    } else {
        //$('#crossLeftHolder #crosstab_add_new').hide();
    }

    $('select[name=means_variable_' + which + ']').each(function (index, el) {
        sequence.push($(el).val());
    });
    $('select[name=means_variable_' + which + '] option:selected').each(function (index, el) {
        spr.push($(el).attr("spr_id"));
        grid.push($(el).attr("grd_id"));
    });

    $.post("ajax.php?t=hierarhy-means&a=add_new_variable", {
        anketa: srv_meta_anketa_id,
        which: which,
        sequence: sequence,
        spr: spr,
        grid: grid
    }, function (response) {
        if (which == '2') {
            $(response).appendTo('#meansRightDropdowns');
        } else {
            $(response).appendTo('#meansLeftDropdowns');
        }
    });
}

function hierarhy_means_remove_variable(what) {

    $(what).parent().remove();
    if ($(what).parent().find('select').val() > 0) {
        change_hierarhy_means();
    }
}
function changeHierarhyMeansSubSetting() {
    var chkMeansSeperate = $("#chkMeansSeperate").is(':checked') ? 1 : 0;
    var chkMeansJoinPercentage = $("#chkMeansJoinPercentage").is(':checked') ? 1 : 0;
    if (chkMeansSeperate == 1) {
        $("#spanMeansJoinPercentage").removeClass('displayNone');
    } else {
        $("#spanMeansJoinPercentage").removeClass('displayNone');
    }
    $.post("ajax.php?t=hierarhy-means&a=changeMeansSubSetting",
        {
            anketa: srv_meta_anketa_id,
            chkMeansSeperate: chkMeansSeperate,
            chkMeansJoinPercentage: chkMeansJoinPercentage
        },
        function (response) {
            change_hierarhy_means();

        }
    );
}

function doArchiveMeans() {
    //preverimo ali obstaja vsebina meansa
    if ($("#div_means_data").html().length > 0) {
        $("#fullscreen").load('ajax.php?a=doArchiveAnaliza', {
            anketa: srv_meta_anketa_id,
            podstran: srv_meta_podstran
        }, function () {

            $('#fade').fadeTo('slow', 1);
            $('#fullscreen').show();
        });
    } else {
        genericAlertPopup('alert_no_archive_tables');
    }
}
function submitArchiveMeans() {
    //preverimo ali obstaja vsebina meansa
    if ($("#div_means_data").html().length > 0) {
        var content = $("#div_means_data").html();

        var name = $("#newAnalysisArchiveName").val();
        var note = $("#newAnalysisArchiveNote").val();
        var access = $("[name=newAnalysisArchiveAccess]:checked").val();
        var duration = $("#newAnalysisArchiveDuration").val();
        var durationType = $("[name=newAADurationType]:checked").val();
        $("#fullscreen").load('ajax.php?a=submitArchiveAnaliza', {
            anketa: srv_meta_anketa_id,
            podstran: srv_meta_podstran,
            name: name,
            note: note,
            access: access,
            duration: duration,
            durationType: durationType,
            content: content
        }, function () {
            $("#fullscreen").show();
        });
    } else {
        genericAlertPopup('alert_no_archive_tables');
    }
}

function createArchiveMeansBeforeEmail() {
    //preverimo ali obstaja vsebina crosstaba
    if ($("#div_means_data").html().length > 0) {
        var content = $("#div_means_data").html();
        $.post('ajax.php?a=createArchiveBeforeEmail', {
            anketa: srv_meta_anketa_id,
            podstran: srv_meta_podstran,
            content: content
        }, function (response) {
            if (parseInt(response) > 0) {
                var aid = parseInt(response);
                $("#fullscreen").load('ajax.php?a=emailArchiveAnaliza', {
                    anketa: srv_meta_anketa_id,
                    podstran: srv_meta_podstran,
                    aid: aid
                }, function () {
                    $('#fullscreen').show();
                });
            } else {
                if (parseInt(response) == -1) {
                    genericAlertPopup('alert_no_archive_response',response);
                } else {
                    genericAlertPopup('alert_archive_error_response',response);
                }
                $('#fullscreen').hide();
                $('#fade').fadeOut('slow');
            }
        });

    } else {
        genericAlertPopup('alert_no_archive_tables');
    }
};

