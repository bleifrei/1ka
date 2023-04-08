<?php
/**
 * Groups configuration for default Minify implementation
 *
 * @package Minify
 */

/**
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 **/

return [


    /*********** JAVASCRIPT **********/

    // JAVASCRIPT - admin vmesnik
    'jsnew' => [

        // jquery in jquery ui vkljucimo ze minificirana, da bo slo mal hitrej
        new Minify_Source(['filepath' => dirname(__FILE__).'/../script/jquery/ui-1.8.18/js/jquery-1.7.1.min.js',]),
        new Minify_Source(['filepath' => dirname(__FILE__).'/../script/jquery/ui-1.8.18/js/jquery-ui-1.8.18.custom.min.js',]),

        new Minify_Source(['filepath' => dirname(__FILE__).'/../script/jquery/ui.drag_drop_selectable.js',]),
        new Minify_Source(['filepath' => dirname(__FILE__).'/../script/jquery/jquery_touch_punch/jquery.ui.touch-punch.min.js',]),

        dirname(__FILE__).'/../script/mobileMenu/zeynep.min.js',

        dirname(__FILE__).'/../script/jquery/jquery.timer.js',
        dirname(__FILE__).'/../script/jquery/farbtastic.js',
        dirname(__FILE__).'/../script/jquery/jquery.qtip-1.0.js',
        dirname(__FILE__).'/../script/jquery/jquery.selectbox-0.6.1/jquery.selectbox-0.6.1.js',
        dirname(__FILE__).'/../script/jquery/jquery.ui.Slider.Pips/jquery-ui-slider-pips.min.js',

        dirname(__FILE__).'/../script/jquery/timepicker/jquery-ui-timepicker-addon.js',

        dirname(__FILE__).'/../script/onload.js',
        dirname(__FILE__).'/../script/script.js',
        dirname(__FILE__).'/../script/mobile.js',
        dirname(__FILE__).'/../script/branching.js',
        dirname(__FILE__).'/../script/folders.js',
        dirname(__FILE__).'/../script/surveyList.js',
        dirname(__FILE__).'/../script/library.js',
        dirname(__FILE__).'/../script/collectData.js',
        dirname(__FILE__).'/../script/script_analiza.js',
        dirname(__FILE__).'/../script/statistika.js',
        dirname(__FILE__).'/../script/vprasanje.js',
        dirname(__FILE__).'/../script/vprasanjeInline.js',
        dirname(__FILE__).'/../script/prevajanje.js',
        dirname(__FILE__).'/../script/telefon.js',
        dirname(__FILE__).'/../script/missingValues.js',
        dirname(__FILE__).'/../script/missingProfiles.js',
        dirname(__FILE__).'/../script/variableProfiles.js',
        dirname(__FILE__).'/../script/statusProfiles.js',
        dirname(__FILE__).'/../script/conditionProfiles.js',
        dirname(__FILE__).'/../script/zankaProfiles.js',
        dirname(__FILE__).'/../script/timeProfiles.js',
        dirname(__FILE__).'/../script/postProcess.js',
        dirname(__FILE__).'/../script/progressBar.js',
        dirname(__FILE__).'/../script/crosstab.js',
        dirname(__FILE__).'/../script/multiCrosstabs.js',
        dirname(__FILE__).'/../script/slideshow.js',
        dirname(__FILE__).'/../script/dataSettingProfiles.js',
        dirname(__FILE__).'/../script/invitations.js',
        dirname(__FILE__).'/../script/themes.js',
        dirname(__FILE__).'/../script/inspect.js',
        dirname(__FILE__).'/../script/means.js',
        dirname(__FILE__).'/../script/ttest.js',
        dirname(__FILE__).'/../script/simpleMailInvitation.js',
        dirname(__FILE__).'/../script/zoom.js',
        dirname(__FILE__).'/../script/break.js',
        dirname(__FILE__).'/../script/dostop.js',
        dirname(__FILE__).'/../script/recode.js',
        dirname(__FILE__).'/../script/appendMerge.js',
        dirname(__FILE__).'/../script/charts.js',
        dirname(__FILE__).'/../script/cReport.js',
        dirname(__FILE__).'/../script/telephone.js',
        dirname(__FILE__).'/../script/profileManager.js',
        dirname(__FILE__).'/../script/SurveyConnect.js',
        dirname(__FILE__).'/../script/surveyCondition.js',
        dirname(__FILE__).'/../script/datepicker.js',
        dirname(__FILE__).'/../script/skupine.js',
        dirname(__FILE__).'/../script/aapor.js',
        dirname(__FILE__).'/../script/notifications.js',
        dirname(__FILE__).'/../script/quota.js',
        dirname(__FILE__).'/../script/ImageHotSpot/imagemap.js',
        dirname(__FILE__).'/../script/trak.js',
        dirname(__FILE__).'/../script/trak_respondent.js',
        dirname(__FILE__).'/../script/GDPR.js',
        dirname(__FILE__).'/../script/narocila.js',
        dirname(__FILE__).'/../script/HeatMap/heatmap_admin.js',
        dirname(__FILE__).'/../script/HeatMap/HeatMapCanvasAdmin.js',
        dirname(__FILE__).'/../script/HeatMap/HeatMapSumarnikPopUp.js',
        dirname(__FILE__).'/../script/HeatMap/heatmap.js',
        dirname(__FILE__).'/../script/DragDrop/dragdropboxInAdmin.js',
        dirname(__FILE__).'/../script/DragDrop/dragdropInAdmin.js',
        dirname(__FILE__).'/../script/custom_column_label_respondent.js',
        dirname(__FILE__).'/../script/ImageHotSpot/imagemap_question_editor.js',

        dirname(__FILE__).'/../script/jquery/jquery.imagemapster.js',

        dirname(__FILE__).'/../script/calendar/calendar.js',
        dirname(__FILE__).'/../script/calendar/lang/calendar-en.js',
        dirname(__FILE__).'/../script/calendar/calendar-setup.js',
        dirname(__FILE__).'/../script/slider.js',
        dirname(__FILE__).'/../script/ImageHotSpot/imageHotspot.js',
        dirname(__FILE__).'/../script/custom_column_label_option.js',
        dirname(__FILE__).'/../script/customizeImageView.js',

        dirname(__FILE__).'/../script/jquery/jquery_touch_punch/jquery.ui.touch-punch.min.js',

        //za prikaz zemljevida v popup oknu
        dirname(__FILE__).'/../script/jquery/colorbox/jquery.colorbox.js',
        dirname(__FILE__).'/../script/Maps/mapInBox.js',
        dirname(__FILE__).'/../script/Maps/markerclusterer.js',
        dirname(__FILE__).'/../../../main/survey/js/Maps/MapDeclaration.js',
        dirname(__FILE__).'/../../../main/survey/js/Maps/Markers.js',
        dirname(__FILE__).'/../../../main/survey/js/Maps/KlikNaMapo.js',
        dirname(__FILE__).'/../../../main/survey/js/Maps/Geocoding.js',
        dirname(__FILE__).'/../../../main/survey/js/Maps/UserLocation.js',
        dirname(__FILE__).'/../../../main/survey/js/Maps/SearchBox.js',
        dirname(__FILE__).'/../../../main/survey/js/Maps/ShapeDrawing.js',
        dirname(__FILE__).'/../script/Maps/CenterControl.js',
        dirname(__FILE__).'/../script/Maps/VnaprejMarkers.js',

        //webcam - fotografija
        dirname(__FILE__).'/../../../main/survey/js/Fotografiranje/webcam.js',
        dirname(__FILE__).'/../../../main/survey/js/Fotografiranje/FotoDeclaration.js',

        #Analize za hierarhijo
        new Minify_Source(['filepath' => dirname(__FILE__).'/../script/jquery/jquery.chosen/chosen.jquery.min.js',]),
        dirname(__FILE__).'/../modules/mod_hierarhija/js/vendor/hierarhija_analize.js',

        #JS od modula MAZA
        dirname(__FILE__).'/../modules/mod_MAZA/js/MAZA.js',
    ],


    // JAVASCRIPT - admin hierarhija
    'jshierarhija' => [
        dirname(__FILE__).'/../script/jquery/jquery-1.12.4.min.js',
        //TODO: samo za test in razvoj
        //dirname(__FILE__).'/../script/jquery/jquery-migrate-1.4.1.js',

        // Za produkcijo se uporabi MIN, da ne izpisuje nepotrebnih console.log komentarjev
        dirname(__FILE__).'/../script/jquery/jquery-migrate-1.4.1.min.js',

        dirname(__FILE__).'/../script/jquery/ui-1.11.4/ui/jquery-ui.min.js',
        dirname(__FILE__).'/../script/jquery/jquery.qtip-1.0.js',
        dirname(__FILE__).'/../script/jquery/jquery.selectbox-0.6.1/jquery.selectbox-0.6.1.js',
        dirname(__FILE__).'/../script/sweetalert/sweetalert.min.js',
        dirname(__FILE__).'/../script/calendar/calendar.js',
        dirname(__FILE__).'/../script/calendar/lang/calendar-en.js',
        dirname(__FILE__).'/../script/calendar/calendar-setup.js',

        //dirname(__FILE__) . '/../script/onload.js',
        dirname(__FILE__).'/../script/vprasanje.js',
        dirname(__FILE__).'/../script/vprasanjeInline.js',
        dirname(__FILE__).'/../script/branching.js',
        dirname(__FILE__).'/../script/folders.js',
        dirname(__FILE__).'/../script/library.js',
        dirname(__FILE__).'/../script/charts.js',


        #JS od modula Hierarhija
        dirname(__FILE__).'/../modules/mod_hierarhija/js/vendor/onload.js',
        new Minify_Source(['filepath' => dirname(__FILE__).'/../modules/mod_hierarhija/js/vendor/hierarhija_analize.js',]),
        new Minify_Source(['filepath' => dirname(__FILE__).'/../script/jquery/jquery.chosen/chosen.jquery.min.js',]),
        dirname(__FILE__).'/../modules/mod_hierarhija/js/vendor/jquery.searchabledropdown.min.js',
        dirname(__FILE__).'/../modules/mod_hierarhija/js/vendor/jquery.nicefileinput.min.js',
        dirname(__FILE__).'/../modules/mod_hierarhija/js/vendor/datatables.min.js',
        dirname(__FILE__).'/../modules/mod_hierarhija/js/vendor/select2.min.js',
        //dirname(__FILE__) . '/../modules/mod_hierarhija/js/vendor/vue.js',
        //dirname(__FILE__) . '/../modules/mod_hierarhija/js/vendor/vue-resource.min.js',
        dirname(__FILE__).'/../script/script.js',
        //dirname(__FILE__) . '/../modules/mod_hierarhija/js/vue-main.js',
        //dirname(__FILE__) . '/../modules/mod_hierarhija/js/vendor/custom.js',
        //dirname(__FILE__) . '/../modules/mod_hierarhija/js/vendor/custom-vue.js',
        dirname(__FILE__).'/../../../public/js/hierarhija_modul.js',
        dirname(__FILE__).'/../modules/mod_hierarhija/js/vendor/status.js',
    ],


    // JAVASCRIPT - zadnje JS knjižnice (datatables za uporabnike, narocila)
    'jsLastLib' => [
        new Minify_Source(['filepath' => dirname(__FILE__).'/../script/jquery/ui-1.8.18/js/jquery-1.7.1.min.js',]),
        new Minify_Source(['filepath' => dirname(__FILE__).'/../script/jquery/ui-1.8.18/js/jquery-ui-1.8.18.custom.min.js',]),
        
        dirname(__FILE__).'/../script/script.js',

        dirname(__FILE__).'/../script/mobileMenu/zeynep.min.js',
        dirname(__FILE__).'/../script/mobile.js',

        dirname(__FILE__).'/../script/datatables/jquery.dataTables.min.js',
        dirname(__FILE__).'/../script/select2/select2.min.js',
        dirname(__FILE__).'/../script/datatables/dataTables.buttons.min.js',
        dirname(__FILE__).'/../script/datatables/moment.min.js',
        dirname(__FILE__).'/../script/datatables/datetime-moment.js',
        dirname(__FILE__).'/../script/datatables/buttons.flash.min.js',
        dirname(__FILE__).'/../script/datatables/jszip.min.js',
        dirname(__FILE__).'/../script/datatables/pdfmake.min.js',
        dirname(__FILE__).'/../script/datatables/vfs_fonts.js',
        dirname(__FILE__).'/../script/datatables/buttons.html5.min.js',
        dirname(__FILE__).'/../script/datatables/buttons.print.min.js',
        dirname(__FILE__).'/../script/datatables/buttons.colVis.min.js',
        dirname(__FILE__).'/../script/datatables/dataTables.select.min.js',
        dirname(__FILE__).'/../script/datatables/dataTables.responsive.min.js',
        dirname(__FILE__).'/../script/uporabniki_custom.js',
        dirname(__FILE__).'/../script/dostop.js',
        dirname(__FILE__).'/../script/narocila.js',
        dirname(__FILE__).'/../script/datepicker.js',
    ],


    // JAVASCRIPT - frontend izpolnjevanje ankete
    'jsfrontend' => [

        // JQuery
        new Minify_Source(['filepath' => dirname(__FILE__).'/../script/jquery/ui-1.8.18/js/jquery-1.7.1.min.js',]),
        new Minify_Source(['filepath' => dirname(__FILE__).'/../script/jquery/ui-1.8.18/js/jquery-ui-1.8.18.custom.min.js',]),
        new Minify_Source(['filepath' => dirname(__FILE__).'/../script/jquery/jquery.chosen/chosen.jquery.min.js',]),
        new Minify_Source(['filepath' => dirname(__FILE__).'/../script/jquery/jquery.ui.Slider.Pips/jquery-ui-slider-pips.min.js',]),
        new Minify_Source(['filepath' => dirname(__FILE__).'/../script/jquery/jquery_touch_punch/jquery.ui.touch-punch.min.js',]),
        new Minify_Source(['filepath' => dirname(__FILE__).'/../script/jquery/timepicker/jquery-ui-timepicker-addon.js',]),

        dirname(__FILE__).'/../script/datepicker.js',

        //toltipster
        dirname(__FILE__).'/../../../main/survey/js/tooltipster.min.js',

        dirname(__FILE__).'/../../../main/survey/js/script.js',
        dirname(__FILE__).'/../script/slider.js',
        dirname(__FILE__).'/../script/jquery/jquery.imagemapster.js',
        dirname(__FILE__).'/../script/jquery/jquery_touch_punch/jquery.ui.touch-punch.min.js',

        dirname(__FILE__).'/../../../main/survey/js/DragDrop/dragdropbox.js',
        dirname(__FILE__).'/../../../main/survey/js/DragDrop/dragdrop.js',
        dirname(__FILE__).'/../../../main/survey/js/trak_respondent.js',
        dirname(__FILE__).'/../../../main/survey/js/custom_column_label_respondent.js',
        dirname(__FILE__).'/../../../main/survey/js/customizeImageView4Respondent.js',

        //js HeatMap
        dirname(__FILE__).'/../../../main/survey/js/HeatMap/heatmap4Respondents.js',
        dirname(__FILE__).'/../../../main/survey/js/HeatMap/HeatMapCanvas.js',

        //js Image HotSpot
        dirname(__FILE__).'/../../../main/survey/js/ImageHotSpot/imagemapRespondent.js',

        //js za zemljevid
        dirname(__FILE__).'/../../../main/survey/js/Maps/MapDeclaration.js',
        dirname(__FILE__).'/../../../main/survey/js/Maps/Markers.js',
        dirname(__FILE__).'/../../../main/survey/js/Maps/KlikNaMapo.js',
        dirname(__FILE__).'/../../../main/survey/js/Maps/Geocoding.js',
        dirname(__FILE__).'/../../../main/survey/js/Maps/UserLocation.js',
        dirname(__FILE__).'/../../../main/survey/js/Maps/SearchBox.js',
        dirname(__FILE__).'/../../../main/survey/js/Maps/ShapeDrawing.js',

        //webcam - fotografija
        dirname(__FILE__).'/../../../main/survey/js/Fotografiranje/webcam.js',
        dirname(__FILE__).'/../../../main/survey/js/Fotografiranje/FotoDeclaration.js',
    ],

    /*********** KONEC JAVASCRIPT **********/

    
    
    /*********** CSS **********/
    
    // CSS - frontend izpolnjevanje ankete
    'cssfrontend' => [
        dirname(__FILE__).'/../script/jquery/jquery.chosen/chosen.css',
        dirname(__FILE__).'/../script/jquery/ui-1.8.18/js/jquery-ui.css',
        
        # nov css kamor se bo vse preneslo (public/main.css)
        dirname(__FILE__).'/../../../public/css/main.css',
        
        dirname(__FILE__).'/../../../main/survey/skins/tooltipster/tooltipster.css',
        dirname(__FILE__).'/../script/jquery/jquery.ui.Slider.Pips/jquery-ui-slider-pips.css',
        dirname(__FILE__).'/../script/jquery/timepicker/jquery-ui-timepicker-addon.css',
    ],

    // CSS - admin vmesnik
    'css' => [

        dirname(__FILE__).'/../script/calendar/calendar.css',
        
        # Potrebno prenesti do konca
        dirname(__FILE__).'/../css/style_sprites_test.css', 
        
        # css za admin
        dirname(__FILE__).'/../../../public/css/admin.css',

        dirname(__FILE__).'/../script/jquery/jquery.selectbox-0.6.1/css/jquery.selectbox.css',
        dirname(__FILE__).'/../script/jquery/jquery.ui.Slider.Pips/jquery-ui-slider-pips.css',

        dirname(__FILE__).'/../script/jquery/timepicker/jquery-ui-timepicker-addon.css',
        dirname(__FILE__).'/../script/select2/select2.min.css',

        # Modul hierarhija
        dirname(__FILE__).'/../script/jquery/jquery.chosen/chosen.css',
        dirname(__FILE__).'/../modules/mod_hierarhija/css/vendor/jstree/proton/style.css',
        # css za jstree
        dirname(__FILE__).'/../modules/mod_hierarhija/css/vendor/datatables.min.css',
        # css za Data tables
        dirname(__FILE__).'/../modules/mod_hierarhija/css/vendor/select2.min.css',
        # css za select2
        dirname(__FILE__).'/../../../public/css/hierarhija.css',
        # custom css za modul Hierarhija
        dirname(__FILE__).'/../script/sweetalert/sweetalert.css',

        # Modul 1kapanel
        dirname(__FILE__).'/../modules/mod_MAZA/css/MAZA.css',

        // za zemljevid v oknu
        dirname(__FILE__).'/../script/jquery/colorbox/colorbox.css',

        new Minify_Source(['filepath' => dirname(__FILE__).'/../script/jquery/ui-1.8.18/js/jquery-ui.css',]),
    ],


    // CSS - admin vmesnik za media = print
    'cssPrint' => [
        dirname(__FILE__).'/../css/style_print.css',
    ],

    /*********** KONEC CSS **********/
];