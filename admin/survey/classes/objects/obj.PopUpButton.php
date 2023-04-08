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
class PopUpButton
{
	# tekst gumba
	private $_caption = null;
	#mouse over title gumba, privzeto je enak caption
	private $_title = null;
	private $_float = 'floatLeft';
	private $_space = 'spaceLeft';
	
	private $_buttonColor = 'gray';
	private $_actions = array();
	
	public function __construct($caption = null)
	{
		$this->setCaption($caption);
		
		# for chaining
		return $this;
	}
	
	public function setCaption($caption)
	{
		$this->_caption = $caption;
		# če title ni nastavljen ga nastavimo enako kot caption
		if ($this->_title === null)
		{
			$this->setTitle($caption);
		}
		
		# for chaining
		return $this;
		
	}

	public function setTitle($title)
	{
		$this->_title = $title;
		
		# for chaining
		return $this;
		
	}
	
	public function setFloat($float = 'Left')
	{
		$this->_float = 'float'.ucfirst($float); 
		$this->_space = 'space'.ucfirst($float);
		
		# for chaining
		return $this;
		
	}
	
	public function setButtonColor($buttonColor)
	{
		switch ($buttonColor) {
			case 'orange':
				$this->_buttonColor = 'orange';
			break;
			
			default:
				$this->_buttonColor = 'gray';
			break;
		}
		
		
		# for chaining
		return $this;
	}
	
	public function addAction($actionTriger, $action)
	{
		$this->_actions[] = $actionTriger.'="'.$action.'"';
		
		# for chaining
		return $this;
	}
	
	public function __toString() {
		$str  = '<div class="'.$this->_float.' '.$this->_space.'">';
		$str .= '<div class="buttonwrapper" title="'.$this->_title.'">';
		$str .= '<a class="ovalbutton ovalbutton_'.$this->_buttonColor.'" href="#" '.implode(' ', $this->_actions).'>';
		$str .= '<span>'.$this->_caption.'</span>';
		$str .= '</a>';
		$str .= '</div>';
		$str .= '</div>';
		return $str;
	}
}
