/**
 * Inicializira datepicker 
 * @param {Object} selector Selektor za element, kjer bomo imeli date
 * @param {Object} event_trigger Ali sprozimo event change
 */
function datepicker (selector, event_trigger, datetime) {

	var datetime = datetime || false;
	
	// Nastavljamo tudi cas
	if(datetime == true){
		$( selector ).datetimepicker({
			showOtherMonths: true,
			selectOtherMonths: true,
			changeMonth: true,
			changeYear: true,
			dateFormat: "dd.mm.yy",
			showAnim: "slideDown",
			showOn: "button",
			/*buttonImage: srv_site_url + "admin/survey/script/calendar/calendar.gif",
			buttonImageOnly: true,*/
			buttonText: "",
			controlType: 'select',
			oneLine: true,
			timeFormat: 'HH:mm',
			stepMinute: 5,
			hour: 8,
			onSelect: function(selected,evnt) {
				if (event_trigger) {
					checkBranchingDate();
					$(selector).trigger('change'); 
					return false;
				}
			}
		});	
	}	
	else{
		$( selector ).datepicker({
			showOtherMonths: true,
			selectOtherMonths: true,
			changeMonth: true,
			changeYear: true,
			dateFormat: "dd.mm.yy",
			showAnim: "slideDown",
			showOn: "button",
			/*buttonImage: srv_site_url + "admin/survey/script/calendar/calendar.gif",*/
			/*buttonImageOnly: true,*/
			buttonText: "",
			onSelect: function(selected,evnt) {
				if (event_trigger) {
					checkBranchingDate();
					$(selector).trigger('change'); 
					return false;
				}
			}
		});
	}	
}
