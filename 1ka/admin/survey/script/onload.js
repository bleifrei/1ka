
/*

	V tej datoteki so vsi klici, ki se zgodijo ob onload (oz. jquerijeva varianta dom-ready) eventu
	Ostale datoteke, naj ne kličejo $(document).ready, ampak se vse kliče iz te datoteke, ki nato pokliče funkcijo v posamezni datoteki
	To je zato da je vsaj malo pregledno kaj se zgodi ob document ready

*/

$(function() {
	
	//console.time('onload');
	
	load_meta_variables();		// script.js
	ajax_start_stop();			// script.js
	onload_init();				// script.js
	IE7_select_disabled_fix();	// script.js
        inline_jezik_hover();       // script.js
	
	onload_init_branching();	// branching.js
	onload_init_inline();		// vprasanjeInline.js
	
	onload_init_vprasanje();	//vprasanje.js
	
	analiza_init();				// script_analiza.js
	
	statistika_init();			// statistika.js
	//console.timeEnd('onload');
	
	//browser_alert();

	missingProfiles_init();		// missingProfiles.js
	missingValues_init();		// missingValues.js
	variableProfiles_init();	// variableProfiles.js
	conditionProfiles_init();	// conditionProfiles.js (If-i)
	zankaProfiles_init();		// tankaProfiles.js (zanke - loopi)
	timeProfiles_init();		// timeProfiles.js (časovni intervali)
	crosstab_init();			// korstabulacije (analiza)
	multiCrosstabs_init();		// multicrosstabi (analiza)
	slideshow_init();			// prezendatcije (slideshow)
	dataSetingProfile_init();	// nastavitve v analizah in podatkov
	invitations_init();	// nastavitve v analizah in podatkov
	//themes_init();				// nastavitve v temah
	inspect_init();				// nastavitve v inspect
	means_init();				// nastavitve v meansih
	ttest_init();				// nastavitve v ttest
	simpleMailInvitation_init();// nastavitve v simpleMailInvitation
	charts_init();				// nastavitve v charts
	creport_init();				// nastavitve v creport
        onload_init_recode();        // nastavitve v recodiranju(function($) {   
	//onload_init_language_technology();		// nastavitve v language technology{   

	// prestejemo stevilo DOM elementov
	//alert(document.getElementsByTagName("*").length);
});
