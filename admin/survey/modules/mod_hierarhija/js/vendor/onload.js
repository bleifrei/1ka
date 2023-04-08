/**
 * Created by Robert Smalc on 24.7.2015.
 */
$(function() {
    load_meta_variables();		// script.js
    ajax_start_stop();			// script.js
    onload_init();				// script.js
    inline_jezik_hover();       // script.js


    means_init();				// nastavitve v meansih

    load_help();                // poskrbi, da naloži HELP/qtip js
    //charts_init();				// nastavitve v charts
    //creport_init();
});