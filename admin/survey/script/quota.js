function quota_editing(condition, new_spremenljivka, vrednost) {

    $('#fade').fadeTo('slow', 1);
    $('#quota').html('').fadeIn("slow");
    if (condition < 0) $('#branching_' + (-condition)).addClass('spr_editing');

    $('#quota').load('ajax.php?t=quota&a=quota_editing', {
        condition: condition,
        vrednost: vrednost,
        noupdate: __vnosi + __analiza,
        anketa: srv_meta_anketa_id
    });

    // v primeru nove spremenljivke, refreshamo tudi branching
    if (new_spremenljivka == 1 && condition < 0) {
        refreshLeft(-condition);
    }
}

function quota_editing_close(condition, vrednost) {

    document.getElementById('quota').style.display = "none";
    if (condition < 0) $('#branching_' + (-condition)).delay('3000').removeClass('spr_editing', 500);

    // kalkulacija v pogojih
    if (condition >= 0) {
        $('#fade').fadeOut('slow');
        $('#div_condition_editing').load(
            'ajax.php?t=quota&a=quota_editing_close', {
                anketa: srv_meta_anketa_id,
                condition: condition,
                vrednost: vrednost
            }, function () {
                centerDiv2Page('#div_condition_editing');
            });

        // kalkulacija kot tip vprasanja
    } else {

        // ce smo v vnosih, refreshamo stran, da se izpise nova kalkulacija..
        if (__vnosi == 1) {

            window.location.reload();

            // obicajno zapiranje kalkulacije v urejanju
        } else {

            $('#fade').fadeOut('slow');
            $('#branching_' + (-condition)).load(
                'ajax.php?t=quota&a=quota_editing_close', {
                    anketa: srv_meta_anketa_id,
                    condition: condition
                }, function () {
                    centerDiv2Page('#div_condition_editing');
                });
        }
    }
}

function quota_save(quota) {

    $.post('ajax.php?t=quota&a=quota_save', {
        quota: quota,
        expression: $('#expression_' + quota).val(),
        noupdate: __vnosi + __analiza,
        anketa: srv_meta_anketa_id
    });

}

function quota_add(condition, operator, vrednost) {

    $('#quota_editing_inner').load('ajax.php?t=quota&a=quota_add', {
        condition: condition,
        operator: operator,
        vrednost: vrednost,
        noupdate: __vnosi + __analiza,
        anketa: srv_meta_anketa_id
    }, function () {
        $("#quota_editing_inner").attr({scrollTop: $("#quota_editing_inner").attr("scrollHeight")});
        $('#quota_editing_inner').scroll();
    });
}


function quota_operator_edit(quota, operator) {

    $('#quota_editing_inner').load('ajax.php?t=quota&a=quota_operator_edit',
        {
            quota: quota,
            operator: operator,
            noupdate: __vnosi + __analiza,
            anketa: srv_meta_anketa_id
        }, function () {
            centerDiv2Page('#div_condition_editing');
            $('#quota_editing_inner').scroll();
        });
}

function quota_sort(condition) {

    $('#quota_editing_inner').load('ajax.php?t=quota&a=quota_sort', {
        'condition': condition,
        sortable: $('#quota_editing_inner').sortable('serialize'),
        anketa: srv_meta_anketa_id
    }, function () {
        $('#quota_editing_inner').scroll();
    });
}

function quota_edit(quota, vrednost) {

    var spr_id = document.getElementById('quota_spremenljivka_' + quota);
    var spremenljivka = spr_id.options[spr_id.selectedIndex].value;

    var value = $('#quota_value_' + quota).val();

    $('#quota_editing_inner').load('ajax.php?t=quota&a=quota_edit', {
        value: value,
        quota: quota,
        vrednost: vrednost,
        spremenljivka: spremenljivka,
        noupdate: __vnosi + __analiza,
        anketa: srv_meta_anketa_id
    }, function () {
        $('#quota_editing_inner').scroll();
    });
}

function quota_value_edit(spremenljivka) {

    var value = $('#quota_value').val();

    $.post('ajax.php?t=quota&a=quota_value_edit', {
        value: value,
        spremenljivka: spremenljivka,
        noupdate: __vnosi + __analiza,
        anketa: srv_meta_anketa_id
    });
}

function quota_remove(condition, quota, vrednost) {

    $('#quota_editing_inner').load('ajax.php?t=quota&a=quota_remove', {
        condition: condition,
        quota: quota,
        vrednost: vrednost,
        noupdate: __vnosi + __analiza,
        anketa: srv_meta_anketa_id
    }, function () {
        $('#quota_editing_inner').scroll();
    });
}

function quota_bracket_edit_new(quota, vrednost, who, what) {

    $('#quota_editing_inner').load('ajax.php?t=quota&a=quota_bracket_edit_new',
        {
            who: who,
            what: what,
            quota: quota,
            vrednost: vrednost,
            noupdate: __vnosi + __analiza,
            anketa: srv_meta_anketa_id
        }, function () {
            centerDiv2Page('#div_condition_editing');
            $('#quota_editing_inner').scroll();
        });
}

function quota_edit_variable(spremenljivka) {

    var input = $("#variable_" + spremenljivka);
    var variable = input.val();

    variable = check_valid_variable(variable);

    input.val(variable);

    $.post('ajax.php?t=quota&a=quota_edit_variable', {
        anketa: srv_meta_anketa_id,
        spremenljivka: spremenljivka,
        variable: variable
    });
}