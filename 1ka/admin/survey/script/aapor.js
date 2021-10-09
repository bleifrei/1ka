/**
 * Created by Andraž Gregorčič on 3.11.2014.
 */

$(function(){
    $( ".main_aapor, .read_aapor" ).qtip({
        content: lang['srv_aapor_automatic'],
        show: 'mouseover',
        hide: 'mouseout',
        position: {
            corner: {
                target: 'rightMiddle',
                tooltip: 'leftMiddle'
            }
        },
        style: {
            width: 200,
            padding: 5,
            background: 'red',
            color: 'black',
            textAlign: 'center',
            border: {
                width: 7,
                radius: 5,
                color: '#A2D959'
            },
            tip: {
                corner: 'leftMiddle',
                color: false
            },
            name: 'blue' // Inherit the rest of the attributes from the preset dark style
        }
    });
});

function prikazi(id){
    if($("#prikazipriblizek").prop('checked')){
        console.log(id);
        $.post("ajax.php?t=aaporCalculation&m=priblizek",{'anketa':id},function(data,status){
            if(data.usable != 'undefined' && data.unusable != 'undefined' && data.partusable != 'undefined' && data.status34 != 'undefined' && data.status02 != 'undefined' && data.status != 'undefined'){
                var obj = JSON.parse(data);
                $( "input" ).val(0);
                $( "input[name='complete']" ).val(obj.usable);
                $( "input[name='partial']" ).val(obj.partusable);
                $( "input[name='breakOff']" ).val(obj.unusable);
                $( "input[name='loggedNotComplete']" ).val(obj.status34);
                $( "input[name='noInvitation']" ).val(obj.status02);
                $( "input[name='nothingReturned']" ).val(obj.status1);
                $( "input[name='e']" ).val(100);
                $(".totalSubDatabaseSpan").text(obj.skupaj/*+obj.status02*/);
                calculateReturne();
                calculateRefusalIm();
                calculateEligible();
                calculateNothingKnownRespondent();
            }
        });
    }else{
        $( "input[name='rq']" ).val('');
        $( "input[name='complete']" ).val('');
        $( "input[name='partial']" ).val('');
        $( "input[name='refusalIm']" ).val('');
        $( "input[name='breakOff']" ).val('');
        $( "input[name='loggedNotComplete']" ).val('');
        $( "input[name='nothingKnown']" ).val('');
        $( "input[name='noInvitation']" ).val('');
        $( "input[name='nothingReturned']" ).val('');
        $(".totalSubDatabaseSpan").text('');
        calculateReturne();
        calculateEligible();
    }
}
function calculateReturne(){
    var com = $( "input[name='complete']" ).val();
    var par = $( "input[name='partial']" ).val();

    if($.trim(com) && $.trim(par)){
        var test = (parseInt(com)+parseInt(par));
        console.log(test);
        $( "input[name='rq']" ).val(test);
        calculateAll();
    }
}

function calculateRefusal(){
    var refusalEx = $( "input[name='refusalEx']").val();
    var refusalIm = $( "input[name='refusalIm']").val();

    if($.trim(refusalEx) && $.trim(refusalIm)){
        var test = parseInt(refusalEx) + parseInt(refusalIm);
        console.log(test);
        $( "input[name='refusal']").val(test);
        calculateAll();
    }
}
function calculateRefusalIm(){
    var loggedNotComplete = $( "input[name='loggedNotComplete']" ).val();
    var readReceiptConfirmation = $( "input[name='readReceiptConfirmation']" ).val();
    console.log(loggedNotComplete+readReceiptConfirmation);
    if($.trim(loggedNotComplete) && $.trim(readReceiptConfirmation)){
        var test = (parseInt(loggedNotComplete) + parseInt(readReceiptConfirmation));
        console.log(test);
        $( "input[name='refusalIm']" ).val(test);
        calculateRefusal();
    }
}
function calculateNonContact(){
    var respondentUnavailable =  $( "input[name='respondentUnavailable']" ).val();
    var completedNotReturned =  $( "input[name='completedNotReturned']" ).val();
    if($.trim(respondentUnavailable) && $.trim(completedNotReturned)){
        var test = (parseInt(respondentUnavailable) + parseInt(completedNotReturned));
        $( "input[name='nonContact']" ).val(test);
        calculateEligible();
    }
}
function calculateOtherEligible(){
    var languageBarrier = $( "input[name='languageBarrier']" ).val();

    if($.trim(languageBarrier)){
        $( "input[name='otherEligible']" ).val(parseInt(languageBarrier));
        calculateEligible();
    }
}
function calculateEligible(){
    var refusal = $( "input[name='refusal']" ).val();
    var breakOff = $( "input[name='breakOff']" ).val();
    var nonContact = $( "input[name='nonContact']" ).val();
    var otherEligible = $( "input[name='otherEligible']" ).val();

    if($.trim(refusal) && $.trim(breakOff) && $.trim(nonContact) && $.trim(otherEligible)){
        var test = parseInt(refusal) + parseInt(breakOff) + parseInt(nonContact) + parseInt(otherEligible);
        console.log(test);
        $( "input[name='eligible']" ).val(test);
        calculateAll();
    }
}
function calculateNothingKnownRespondent(){
    var noInvitation = $( "input[name='noInvitation']" ).val();
    var nothingReturned = $( "input[name='nothingReturned']" ).val();

    if($.trim(noInvitation) && $.trim(nothingReturned)){
        var test = (parseInt(noInvitation) + parseInt(nothingReturned));
        $( "input[name='nothingKnown']" ).val(test);
        calculateUnknownEligibility();
    }
}

function calculateOtherUnknownEligibility(){
    var returnedUnsampledEmail = $( "input[name='returnedUnsampledEmail']" ).val();

    if($.trim(returnedUnsampledEmail)){
        var test = (parseInt(returnedUnsampledEmail));
        $( "input[name='otherUnknownEligible']" ).val(test);
        calculateUnknownEligibility();
    }
}

function calculateUnknownEligibility(){
    var nothingKnown = $( "input[name='nothingKnown']" ).val();
    var invitationReturnedUndelivered = $( "input[name='invitationReturnedUndelivered']" ).val();
    var invitationReturnedForwarding = $( "input[name='invitationReturnedForwarding']" ).val();
    var otherUnknownEligible = $( "input[name='otherUnknownEligible']" ).val();

    if($.trim(nothingKnown) && $.trim(invitationReturnedUndelivered) && $.trim(invitationReturnedForwarding) && $.trim(otherUnknownEligible)){
        var test = parseInt(nothingKnown) + parseInt(invitationReturnedUndelivered) + parseInt(invitationReturnedForwarding) + parseInt(otherUnknownEligible);
        console.log(test);
        $( "input[name='unknownEligible']" ).val(test);
        calculateAll();
    }
}
function calculateQuotaFilled(){
    var duplicateListing = $( "input[name='duplicateListing']" ).val();

    if($.trim(duplicateListing)){
        var test = (parseInt(duplicateListing));
        $( "input[name='quotaFilled']" ).val(test);
        calculateNotEligible();
    }
}

function calculateNotEligible(){
    var selectedRespondent = $( "input[name='selectedRespondent']" ).val();
    var quotaFilled = $( "input[name='quotaFilled']" ).val();
    var otherNotEligible = $( "input[name='otherNotEligible']" ).val();

    if($.trim(selectedRespondent) && $.trim(quotaFilled) && $.trim(otherNotEligible)){
        var test = parseInt(selectedRespondent) + parseInt(quotaFilled) + parseInt(otherNotEligible);
        console.log(test);
        $( "input[name='notEligible']" ).val(test);
        calculateAll();
    }
}

function calculateAll(){
    var rq = $( "input[name='rq']" ).val();
    var eligible = $( "input[name='eligible']" ).val();
    var unknownEligible = $( "input[name='unknownEligible']" ).val();
    var notEligible = $( "input[name='notEligible']" ).val();

    if($.trim(rq) && $.trim(eligible) && $.trim(unknownEligible) && $.trim(notEligible)){
        var test = parseInt(rq) + parseInt(eligible) + parseInt(unknownEligible) + parseInt(notEligible);
        console.log(test);
        $(".totalSubSpan").text(test);
    }
}

function getCallculationAapor(id){
    //var d = $("#aaporForm").serialize();
	var refusal = $( "input[name='refusal']" ).val();
	var breakOff = $( "input[name='breakOff']" ).val();
	var invitationReturnedUndelivered = $( "input[name='invitationReturnedUndelivered']" ).val();
	var invitationReturnedForwarding = $( "input[name='invitationReturnedForwarding']" ).val();
	var otherUnknownEligible = $( "input[name='otherUnknownEligible']" ).val();
	var complete = $( "input[name='complete']" ).val();
	var partial = $( "input[name='partial']" ).val();
	var nonContact = $( "input[name='nonContact']" ).val();
	var otherEligible = $( "input[name='otherEligible']" ).val();
	var nothingKnown = $( "input[name='nothingKnown']" ).val();
	var e = $( "input[name='e']" ).val();

    $.post("ajax.php?t=aaporCalculation",{anketa:id,refusal:refusal,breakoff:breakOff,invitationReturnedUndelivered:invitationReturnedUndelivered,invitationReturnedForwarding:invitationReturnedForwarding,otherUnknownEligible:otherUnknownEligible,complete:complete,partial:partial,nonContact:nonContact,otherEligible:otherEligible,nothingKnown:nothingKnown,e:e},function(data,status){
        var obj = JSON.parse(data);
        $("#rr1").text(obj.rr1);
        $("#rr2").text(obj.rr2);
        $("#rr3").text(obj.rr3);
        $("#rr4").text(obj.rr4);
        $("#rr5").text(obj.rr5);
        $("#rr6").text(obj.rr6);
    });
}
