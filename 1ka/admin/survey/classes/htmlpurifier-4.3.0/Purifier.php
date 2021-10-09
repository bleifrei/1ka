<?php

/**
* 
* Pripravi default klice za HTML Purifier
* 
*/
class Purifier {
	
	var $purifier = null;
	
	/**
	* v konstruktorju nastavimo lastno konfiguracijo
	* 
	*/
	function __construct () {
		
		$config = HTMLPurifier_Config::createDefault();
		$config->set('HTML.DefinitionID', '1ka anketa');
		$config->set('HTML.DefinitionRev', 6);
		//$config->set('Cache.DefinitionImpl', null); // use when developing
		if ($def = $config->maybeGetRawHTMLDefinition()) {
		    $def->addAttribute('a', 'target', 'Enum#_blank,_self,_target,_top,link');
		    
			$iframe = $def->addElement('iframe', 'Block', 'Flow', 'Common',
				array(
					'src*' => 'URI',
					'height' => 'Length',
					'width' => 'Length',
					'frameborder' => 'Number'
				)
			);
			$iframe->excludes = array('iframe' => true);
			
			$object = $def->addElement('object', 'Inline', 'Optional: #PCDATA | Flow | param', 'Common',
				array(
					'archive' => 'URI',
					'classid' => 'URI',
					'codebase' => 'URI',
					'codetype' => 'Text',
					'data' => 'URI',
					'declare' => 'Bool#declare',
					'height' => 'Length',
					'name' => 'CDATA',
					'standby' => 'Text',
					'tabindex' => 'Number',
					'type' => 'ContentType',
					'width' => 'Length'
				)
			);
			
			$param = $def->addElement('param', false, 'Empty', false,
				array(
					'id' => 'ID',
					'name*' => 'Text',
					'type' => 'Text',
					'value' => 'Text',
					'valuetype' => 'Enum#data,ref,object'
				)
         	);
			
		}
		$this->purifier = new HTMLPurifier($config);
		
	}
	
	/**
	* Navaden purify, ce se bo kje rabil
	* 
	*/
	function purify ( $string ) {
		
		if ($this->purifier == null) return;
		
		return $this->purifier->purify($string);
		
	}
	
	/**
	* Ocistimo string in ga pripravimo za insert v bazo
	* 
	*/
	function purify_DB ( $string ) {
		
		if ($this->purifier == null) return;
		
		// tukaj moramo najprej stripat slashe, drugace purify ne dela
		return mysqli_real_escape_string($GLOBALS['connect_db'], $this->purifier->purify(mysql_real_unescape_string($string)) );
		
	}
	
}

?>