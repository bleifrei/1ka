/**
 * Copyright (c) 2014, CKSource - Frederico Knabben. All rights reserved.
 * Licensed under the terms of the MIT License (see LICENSE.md).
 *
 * The abbr plugin dialog window definition.
 *
 * Created out of the CKEditor Plugin SDK:
 * http://docs.ckeditor.com/#!/guide/plugin_sdk_sample_1
 */

// Our dialog definition.
CKEDITOR.dialog.add( 'abbrDialog', function( editor ) {
	var lang = editor.lang.abbr;
	return {

		// Basic properties of the dialog window: title, minimum size.
		title: lang.title,
		minWidth: 400,
		minHeight: 150,

		// Dialog window content definition.
		contents: [
			{
				// Definition of the Basic Settings dialog tab (page).
				id: 'tab-basic',
				label: lang.beseda,

				// The tab content.
				elements: [
					{
						type: 'text',
						id: 'abbr',
						label: lang.beseda,

						// Validation checking whether the field is not empty.
						validate: CKEDITOR.dialog.validate.notEmpty( 'Polje "Izbrana beseda" ne sme biti prazno.' ),

						// Called by the main setupContent method call on dialog initialization.
						setup: function( element ) {
							this.setValue( element.getText() );
						},
                        //če je teks označen, ga vnesemo v polje ime besede
                        onShow: function(element){
                            var s = editor.getSelection().getSelectedText();
                            if(s.length > 0)
                              this.setValue( s );
                        },

						// Called by the main commitContent method call on dialog confirmation.
						commit: function( element ) {
							element.text = element.setText( this.getValue() );
						}
					},
					{
						type: 'textarea',
						id: 'title',
						label: lang.razlaga,
						validate: CKEDITOR.dialog.validate.notEmpty( 'Polje "Razlaga" ne sme biti prazno.' ),

						// Called by the main setupContent method call on dialog initialization.
						setup: function( element ) {
							this.setValue( element.getAttribute( "title" ) );
						},

						// Called by the main commitContent method call on dialog confirmation.
						commit: function( element ) {
							element.setAttribute( "title", this.getValue() );
						}
					},
					{
						type: 'radio',
						id: 'nacinPrikazaSlovarja',
						label: lang.opcija,
						labelStyle: 'color:#555; display:block; padding-bottom: 5px; font-size: 0.95em',
						labelLayout: 'vertical',
						items: [ [ lang.izbira1, 'tooltip mouseover' ], [ lang.izbira2, 'tooltip mouseclick' ] ],
						style: 'color:#555; font-size: 0.95em',
						'default': 'tooltip mouseover',
						validate: function() {
							if ( this.getValue() == null ) {
								alert( 'Niste izbrali: Izberite način prikazovanja razlage besede, ki jo bo videl uporabnik ankete.' );
								return false;
							}
						},
						setup: function( element ) {

							this.setValue( element.getAttribute( "class"));
						},

						// Called by the main commitContent method call on dialog confirmation.
						commit: function( element ) {
							element.setAttribute( "class", this.getValue());
						}

					}
				]
			}
		],

		// Invoked when the dialog is loaded.
		onShow: function() {

			// Get the selection from the editor.
			var selection = editor.getSelection();

			// Get the element at the start of the selection.
			var element = selection.getStartElement();

			// Get the <span> element closest to the selection, if it exists.
			if ( element )
				element = element.getAscendant( 'abbr', true );
			// Create a new <span> element if it does not exist.
			if ( !element || element.getName() != 'abbr' ) {
				//element = editor.document.createElement( 'abbr', true);
				element = editor.document.createElement( 'abbr', true);

				//element.addClass('tooltip');

				// Flag the insertion mode for later use.
				this.insertMode = true;
			}
			else
				this.insertMode = false;

			// Store the reference to the <abbr> element in an internal property, for later use.
			this.element = element;

			// Invoke the setup methods of all dialog window elements, so they can load the element attributes.
			if ( !this.insertMode )
				this.setupContent( this.element );
		},

		// This method is invoked once a user clicks the OK button, confirming the dialog.
		onOk: function() {

			// The context of this function is the dialog object itself.
			// http://docs.ckeditor.com/#!/api/CKEDITOR.dialog
			var dialog = this;

			// Create a new <abbr> element.
			var abbr = this.element;

			// Invoke the commit methods of all dialog window elements, so the <abbr> element gets modified.
			this.commitContent( abbr );

			// Finally, if in insert mode, insert the element into the editor at the caret position.
			if ( this.insertMode )
				editor.insertElement( abbr );

		}
	};
});
