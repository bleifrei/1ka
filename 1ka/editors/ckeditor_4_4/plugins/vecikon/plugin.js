/**
 * Created by pc on 13.2.2015.
 */

CKEDITOR.plugins.add( 'vecikon', {

    icons: 'vecikon',
    lang: 'en,sl',
    init: function( editor ) {
        //// Create a toolbar button that executes the above command.
        editor.ui.addButton( 'Vecikon', {

            // The text part of the button (if available) and the tooltip.
            label: editor.lang.vecikon.title,

            // The command to execute on click.
            command: 'vecikon',

            // The button placement in the toolbar (toolbar group name).
            toolbar: 'insert, 100'
        });

        editor.addCommand('vecikon', new CKEDITOR.command( editor, {
            exec: function (editor) {
                var id = editor.name;
                get_full_editor(id);
                //CKEDITOR.instances[id].destroy();
                //CKEDITOR.replace( '#'+id, {toolbar: 'Full'});
            }
        }));


    }
});
