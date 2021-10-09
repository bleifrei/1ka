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
class PopUp
{
	private $_id = null;
	private $_css = array('divPopUp');

	private $_headerText = null;
	private $_content = null;
	private $_buttons = array();
	
	protected $_displayed = false;
	
	public function setId($id)
	{
		$this->_id = $id; 
		return $this;
	}

	public function addCss($css)
	{
		$this->_css[] = $css;
		return $this;
	}
	
	public function setHeaderText($headerText)
	{
		$this->_headerText = $headerText;
		return $this;
	}
	
	public function setContent($content)
	{
		$this->_content = $content;
		return $this;
	}

	public function addContent($content)
	{
		$this->_content .= $content;
		return $this;
	}
	
	
	public function addButton(PopUpButton $button)
	{
		$this->_buttons[] = $button;
		return $this;
	}
	
	public function display()
	{
		$this->_displayed = true;
		
		#začnemo osnovni div
		echo '<div';
		if ($this->_id != null)
		{
			echo ' id="'.$this->_id.'"';
		}
		if (count($this->_css) > 0)
		{
			echo ' class="'. implode(' ',$this->_css).'"';
		}
		echo '>';
		
		#dodamo header 
		if ($this->_headerText != null)
		{
			echo '<div class="divPopUp_top">';
			echo $this->_headerText;
			echo '</div>'; #PM_top
		}
		
		#dodamo vsebino - content
		echo '<div class="divPopUp_content">';
		echo $this->_content;
		echo '</div>'; # class="divPopUp_content"
		
		# začnemo div z gumbi
		echo '<div class="divPopUp_btm">';
		
		# izrišemo gumbe
		if (count($this->_buttons) > 0) {
			foreach ($this->_buttons AS $button)
			{
				echo $button;
			}
		}
		
		#zaključimo div z gumbi
		echo '</div>'; #class="inv_FS_btm clr"
		
		#zaključimo div z gumbi
		echo '</div>';
		return $this;
	}
	
	public function __toString() {
		ob_start();
		$this->display();
		$content = ob_get_clean();
		return $content;
	}
	
	public function __destruct() {
		if ($this->_displayed == false)
		{
			$this->display();
		}
		return $this;
	}
}