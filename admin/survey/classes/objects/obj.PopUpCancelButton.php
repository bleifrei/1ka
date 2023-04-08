<?php 
/**
 * 
 * @author veselicg
 *
 * Večina funkcij vrača sam klass da omogočamo chaining
 * 
 * Primer uporabe:
 * 	
 *		$popUp = new PopUp();
 *
 *		# določimo naslovno vrstico popupa. če ni podano je ne prikazuje
 *		$popUp -> setHeaderText('Moj PopUp:')
 *				
 *				# določimo id diva (..<div id="nek_id_diva"...)
 *			   -> setId('nek_id_diva')
 *				
 *				# po potrebi dodamo css je (..<div class="css1 css2"...)
 *			   -> addCss('css1')
 *			   -> addCss('css2')
 *
 *				#dodamo vsebino (osrednji del) popupa
 *			   -> setContent($content);
 *		
 *		# dodamo gumb Prekliči - je standarden gumb
 *		$popUp->addButton(new PopUpCancelButton());
 *
 *	
 *		#dodamo gumb izberi profil
 *		$button = new PopUpButton($lang['srv_save_profile']);
 *		$button -> setFloat('right')
 *				-> setButtonColor('orange')
 *				-> addAction('onClick','changeColectDataStatus(); return false;');
 *		$popUp->addButton($button);
 *
 *		# izrišemo div
 *		echo $popUp; # lahko tudi $popUp->display();
 * 
 */

/** Gumbi
 * 
 * 
 */

/** Sistemski gumb za skritje popupa
 */
class PopUpCancelButton extends PopUpButton
{
	public function __construct()
    {
        // call Parent's (PopUpButton) constructor
    	global $lang;
    	parent::__construct($lang['srv_cancel']);
        $this -> addAction('onClick',"$('#fade').fadeOut('slow');$('#fullscreen').fadeOut('slow').html(''); return false;");
    }
}