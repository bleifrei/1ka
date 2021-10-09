<?php

/*
 *  Modul za chat z respondenti
 * 
 *	Zaenkrat se uporablja storitev TAWK
 *
 */


class SurveyChat{

	var $anketa;				# id ankete

	
	function __construct($anketa){
		global $site_url;

		// Ce imamo anketo
		if ((int)$anketa > 0){
			$this->anketa = $anketa;
		}
	}
	
	
	// Nastavitve chat-a (na kateri strani se prikaze...)
	public function displaySettings(){
		global $lang;
    	
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		
		echo '<fieldset><legend>'.$lang['settings'].'</legend>';
			
		// Koda za embed tawk chat widgeta
		$code = '';
		$sql = sisplet_query("SELECT * FROM srv_chat_settings WHERE ank_id='".$this->anketa."'");
		if(mysqli_num_rows($sql) > 0){
			
			$row = mysqli_fetch_array($sql);
			$code = $row['code'];
		}
		echo '<span class="nastavitveSpan2" style="vertical-align:top;">'.$lang['srv_chat_code'].':</span>';
		echo '<textarea id="chat_code" name="chat_code" rows="5" cold="20">'.$code.'</textarea>';
		
		echo '<br /><br />';
		
		// Prikaz vklopa chata
		echo '<span class="nastavitveSpan2" >'.$lang['srv_chat_type'].':</span>';
		echo '<input type="radio" name="chat_type" id="chat_type_0" value="0" '.(($row['chat_type'] == 0) ? ' checked="checked" ' : '').' /><label for="chat_type_0">'.$lang['srv_chat_type_0'].'</label>';
		echo '<input type="radio" name="chat_type" id="chat_type_1" value="1" '.(($row['chat_type'] == 1) ? ' checked="checked" ' : '').' /><label for="chat_type_1">'.$lang['srv_chat_type_1'].'</label>';	
		echo '<input type="radio" name="chat_type" id="chat_type_2" value="2" '.(($row['chat_type'] == 2) ? ' checked="checked" ' : '').' /><label for="chat_type_2">'.$lang['srv_chat_type_2'].'</label>';	
		
		echo '<br /><br />';

		echo '</fieldset>';
		
		
		// Gumb shrani
		echo '<br class="clr" />';
		echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="chat_save_settings(); return false;"><span>';
		echo $lang['edit1337'] . '</span></a></div></span>';
		echo '<div class="clr"></div>';
		echo '<div id="success_save"></div>';	
	}
	
	
	public function ajax() {
		
		if(isset($_GET['a']) && $_GET['a'] == 'save_settings'){
			
			$code = isset($_POST['code']) ? $_POST['code'] : '';
			$chat_type = isset($_POST['chat_type']) ? $_POST['chat_type'] : '0';
			
			$sql = sisplet_query("INSERT INTO srv_chat_settings 
									(ank_id, code, chat_type) VALUES ('".$this->anketa."', '".$code."', '".$chat_type."') 
									ON DUPLICATE KEY UPDATE code='".$code."', chat_type='".$chat_type."'");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		}

	}
}