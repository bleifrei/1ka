/**
 * @license Copyright (c) 2003-2014, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

//spremenljivka za ID elementa od txtUrl
var urlsrc = '';

// Url strani za embedd uploaderja
var site_url = $('#srv_site_url').val();

// Jezik za image uploader
var lang_code = 'sl';

CKEDITOR.editorConfig = function (config) {
    //config.language = 'sl';
    config.skin = 'moonocolor';

    lang_code = config.language;

    config.toolbar = [
        {name: 'document', items: ['Source', '-']},
        {name: 'clipboard', items: ['PasteText','RemoveFormat', 'Undo', 'Redo', 'Scayt']},
        {name: 'basicstyles', items: ['Bold', 'Underline', 'Italic', 'Strike', '-']},
        {name: 'links', items: ['Link', 'Unlink', 'Image', 'SpecialChar']},
        {name: 'colors', items: ['TextColor', 'BGColor']},
        {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock','-']},
        {name: 'insert', items: ['CreateDiv', 'Flash', 'Table', 'IFrame', 'Youtube', 'Abbr']},
        {name: 'vec', items:['-', 'Styles', 'Format',  'FontSize', 'Font',  'Outdent', 'Indent', 'HorizontalRule']}
    ];

    config.toolbar_Full = [
        {name: 'document', items: ['Source', '-']},
        {name: 'basicstyles', items: ['Bold', 'Underline', 'Italic', '-']},
        {name: 'links', items: ['Link']},
        {name: 'colors', items: ['TextColor', 'BGColor']},
        {name: 'clipboard', items: ['Undo', 'Redo']},
        {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock','-']},
        {name: 'insert', items: ['Table', 'Image', 'Youtube', 'Abbr']},
        {name: 'vec', items:['-', 'FontSize', 'Font', 'RemoveFormat', 'Strike', 'Unlink', 'Outdent', 'Indent', 'PasteText', 'PasteFromWord', 'HorizontalRule']}
    ];

    config.toolbar_Content = [
        {name: 'document', items: ['Source', '-']},
        {name: 'clipboard', items: ['PasteText','RemoveFormat', 'Undo', 'Redo', 'Scayt']},
        {name: 'basicstyles', items: ['Bold', 'Underline', 'Italic', 'Strike', '-']},
        {name: 'links', items: ['Link', 'Unlink', 'Image', 'SpecialChar']},
        {name: 'colors', items: ['TextColor', 'BGColor']},
        {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock','-']},
        {name: 'insert', items: ['CreateDiv', 'Flash', 'Table', 'IFrame', 'Youtube', 'Abbr']},
        {name: 'vec', items:['-', 'Styles', 'Format', 'FontSize', 'Font',  'Outdent', 'Indent', 'HorizontalRule']}
    ];
    config.toolbar_Database = [
        {name: 'document', items: ['Source', '-']},
        {name: 'clipboard', items: ['PasteText','RemoveFormat', 'Undo', 'Redo', 'Scayt']},
        {name: 'basicstyles', items: ['Bold', 'Underline', 'Italic', 'Strike', '-']},
        {name: 'links', items: ['Link', 'Unlink', 'Image', 'SpecialChar']},
        {name: 'colors', items: ['TextColor', 'BGColor']},
        {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock','-']},
        {name: 'insert', items: ['CreateDiv', 'Flash', 'Table', 'IFrame', 'Youtube', 'Abbr']},
        {name: 'vec', items:['-', 'Styles', 'Format',  'FontSize', 'Font',  'Outdent', 'Indent', 'HorizontalRule']}
    ];
    config.toolbar_Forum = [
        {name: 'document', items: ['Source', '-']},
        {name: 'clipboard', items: ['PasteText','RemoveFormat', 'Undo', 'Redo', 'Scayt']},
        {name: 'basicstyles', items: ['Bold', 'Underline', 'Italic', 'Strike', '-']},
        {name: 'links', items: ['Link', 'Unlink', 'Image', 'SpecialChar']},
        {name: 'colors', items: ['TextColor', 'BGColor']},
        {name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock','-']},
        {name: 'insert', items: ['Table']},
        {name: 'vec', items:['-', 'FontSize', 'Font',  'Outdent', 'Indent']}
    ];
	config.toolbar_HotSpot = [
        //{name: 'insert', items: ['Image', 'ImageMap']}
		{name: 'insert', items: ['Image']}
    ];

    config.removeButtons = 'Cut,Copy,Paste,Subscript,Superscript';

    config.extraPlugins = 'youtube,abbr,vecikon';
    config.extraAllowedContent = {
        'abbr' : {
            attributes: '*',
            classes: '*'
        },
        'span' : {
            attributes: ['color*', 'background-color*','text-align*','font-family*','font-size*'],
            classes: '*'
        },
        'p' : {
            classes: '*'
        },
        'div' : {
            classes: '*'
        },
        'iframe': {
            attributes: ['allowfullscreen*', 'frameborder*', 'height*', 'width*', 'src*']
        },
        's' : {}
    };
    config.disallowedContent = 'h1, h2, h3, h4, h5, h6';

    // Full page mode (allow html, body...)
    //config.fullPage = true;

    //DEV TOOLS
    //config.extraPlugins = 'devtools';

    /*YOUTUBE EMBED CONFIG*/
    config.youtube_width = '560';
    config.youtube_height = '315';
    config.youtube_related = false;
    config.youtube_older = false;
    config.youtube_privacy = false;

    // Ostale privzete nastavitve
    config.entities = false;					// naj uporabi entitije.
    config.entities_processNumerical = true;		// naj entitije spravi v stevilke (in ne &scaron!)
    config.startupFocus = true;
    config.enterMode = CKEDITOR.ENTER_BR;
    config. shiftEnterMode = CKEDITOR.ENTER_P;
    config.pasteFromWordRemoveStyles = true;
    config.keystrokes = [[CKEDITOR.ALT + 84 /*T*/, 'abbr' ]];

    // Ne vem kje se to drugace lahko nastavi, potem naredimo kar tako:)
    if(lang_code == 'en')
        config.image_previewText = "Image preview.";
    else
        config.image_previewText = "Predogled slike.";

}

CKEDITOR.on('instanceReady', function() {
    $(".cke_button__source_label").text("");
});

//CKEDITOR.on('key', function() {
//    alert('nek');
//});

CKEDITOR.on('dialogDefinition', function (ev) {
    // Take the dialog name and its definition from the event data.

    var dialogName = ev.data.name;
    var dialogDefinition = ev.data.definition;

    if (dialogName == 'image') {

         //odstrani 'Link' in 'Advanced' zavihek v pojavnem oknu 'Image'
        dialogDefinition.removeContents('Link');

        // Get a reference to the 'Image Info' tab.
        var infoTab = dialogDefinition.getContents('info');

        //odstranimo polja v pojavnem oknu 'Image'//
        infoTab.remove('txtHSpace');
        infoTab.remove('txtVSpace');
        infoTab.remove('txtBorder');

        //dodamo div browse file
        infoTab.add(
            {
                type: 'html',
                id: 'uploadSlikeEnka',
                label: 'Naloži sliko',
                html: '<div style="display: block; float: left; width: 100%; height: 40px; clear: both;"><iframe src="'+site_url+'editors/ckeditor_4_4/uploader/EnkaUploader.php?image=1&url='+urlsrc+'&lang='+lang_code+'" style="width: 350px; height: 50px; border: none; overflow: hidden;" border="0" frameborder="0"></iframe></div>'
            },
            'txtAlt'
        );
        infoTab.remove('txtAlt'); //alternative text
    }

    if (dialogName == 'link') {

        // Get a reference to the 'Image Info' tab.
        var infoTab = dialogDefinition.getContents('info');


        //dodamo div browse file
        infoTab.add(
            {
                type: 'html',
                id: 'uploadDatotekeEnka',
                label: 'Naloži datoteko',
                html: '<div style="display: block; float: left; width: 100%; height: 40px; clear: both;"><iframe src="'+site_url+'editors/ckeditor_4_4/uploader/EnkaUploader.php?url='+urlsrc+'&lang='+lang_code+'" style="width: 350px; height: 50px; border: none; overflow: hidden;" border="0" frameborder="0"></iframe></div>'
            }
        );
    }
});

