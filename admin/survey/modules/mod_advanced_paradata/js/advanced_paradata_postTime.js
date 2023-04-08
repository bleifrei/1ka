// EVENT LEAVE PAGE, ki se mora izvajati sinhrono (drugace se pri nekaterih browserjih izgubi)
$(function () {
	
    // LEAVE PAGE (gledamo unload, beforeunload, klik na submit, klik na back)
    var leavePageFunction_called = false;
	var leavePageFunction = function (){
		var event_type = 'page';
		var event = 'unload_page';

        if(!leavePageFunction_called){
            logEvent(event_type, event);
            leavePageFunction_called = true;
        }
	}
    window.addEventListener('beforeunload', leavePageFunction);
	window.addEventListener('unload', leavePageFunction);
	$("input.next:submit").bind('click', leavePageFunction);
	$("input.prev:button").bind('click', leavePageFunction);

})