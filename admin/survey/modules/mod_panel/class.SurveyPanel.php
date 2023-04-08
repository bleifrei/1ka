<?php
/**
 *
 *	Modul za povezovanje panela (npr. Valicon, GFK...) z 1ka anketo
 *
 */

class SurveyPanel{

	var $anketa;				# id ankete
	var $db_table = '';	

	
	function __construct($anketa){
		global $site_url;

		// Ce imamo anketo, smo v status->ul evealvacija
		if ((int)$anketa > 0){
			$this->anketa = $anketa;

			# polovimo vrsto tabel (aktivne / neaktivne)
			SurveyInfo :: getInstance()->SurveyInit($this->anketa);
			$this->db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
		}
	}
	
	
	// Prikazemo nastavitve pri vklopu naprednega modula
	public function displaySettings(){
		global $lang;	
		
		echo '<fieldset><legend>'.$lang['settings'].'</legend>';
			
		$rowA = SurveyInfo::getInstance()->getSurveyRow();
		$row = $this->getPanelSettings();

		// Url za preusmeritev
		echo '<span class="nastavitveSpan1" >'.$lang['srv_panel_url'].':</span>';
		echo '<input type="text" size="40" name="url" id="url" value="'.$rowA['url'].'" />';
		
		echo '<br /><br />';
		
		// Ime parametra za id respondenta
		echo '<span class="nastavitveSpan1" >'.$lang['srv_panel_user_id_name'].':</span>';
		echo '<input type="text" name="user_id_name" id="user_id_name" value="'.$row['user_id_name'].'" />';

		echo '<br /><br />';
		
		// Ime parametra za status
		echo '<span class="nastavitveSpan1" >'.$lang['srv_panel_status_name'].':</span>';
		echo '<input type="text" name="status_name" id="status_name" value="'.$row['status_name'].'" />';
		
		echo '<br />';
		
		// Privzeta vrednost status parametra
		echo '<span class="nastavitveSpan1" >'.$lang['srv_panel_status_default'].':</span>';
		echo '<input type="text" name="status_default" id="status_default" value="'.$row['status_default'].'" />';
		
		echo '<br /><br />';

		// Primer zacetnega url-ja
		$link = SurveyInfo::getSurveyLink();
		echo '<span class="nastavitveSpan1" >'.$lang['srv_panel_url1_example'].':</span>';
		echo $link.'?'.$row['user_id_name'].'=RESPONDENT_PANEL_ID';
		
		echo '<br /><br />';
		
		// Primer končnega url-ja
		echo '<span class="nastavitveSpan1" >'.$lang['srv_panel_url2_example'].':</span>';
		echo $rowA['url'].'?'.$row['user_id_name'].'=RESPONDENT_PANEL_ID&'.$row['status_name'].'=PANEL_STATUS';

		
		echo '</fieldset>';
		
		
		// Gumb shrani
		echo '<br class="clr" />';
		echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="panel_save_settings(); return false;"><span>';
		echo $lang['edit1337'] . '</span></a></div></span>';
		echo '<div class="clr"></div>';
		echo '<div id="success_save"></div>';	
	}	
	
	// Izvedemo vse potrebno, ko modul aktiviramo (nastavimo parametre za zakljucek, ustvarimo sistemske spremenljivke...)
	public function activatePanel(){
		global $lang;
		
		// Vstavimo vrstico z nastavitvami
		$sql1 = sisplet_query("INSERT INTO srv_panel_settings (ank_id) VALUES ('".$this->anketa."')");

		// Uredimo nastavitve zakljucka
		$sql2 = sisplet_query("UPDATE srv_anketa SET concl_link='1' WHERE id='".$this->anketa."'");
		if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);
		
		// Ustvarimo sistemsko skrito vprasanje za panel id respondenta
		SurveyRespondents:: getInstance()->Init($this->anketa);
        SurveyRespondents:: checkSystemVariables($variable=array('SID'), $setUserbase=false);
	}	
	
	
	// Vrnemo nastavitve panela
	public function getPanelSettings($what = ''){
		
		if($what != ''){
			$sql = sisplet_query("SELECT ".$what." FROM srv_panel_settings WHERE ank_id='".$this->anketa."'");
			$row = mysqli_fetch_array($sql);
			
			return $row[$what];
		}
		else{
			$sql = sisplet_query("SELECT * FROM srv_panel_settings WHERE ank_id='".$this->anketa."'");
			$row = mysqli_fetch_array($sql);
			
			return $row;
		}
	}
	
	// Vrnemo nastavitev statusa na if-u
	public function getPanelIf($if_id){
		
		$sql = sisplet_query("SELECT value FROM srv_panel_if WHERE ank_id='".$this->anketa."' AND if_id='".$if_id."'");
		
		if(mysqli_num_rows($sql) > 0){
			$row = mysqli_fetch_array($sql);
			
			return $row['value'];
		}
		else{
			return '';
		}
	}
	
	
	public function ajax() {
		
		if(isset($_GET['a']) && $_GET['a'] == 'save_settings'){
			
			// Dobimo staro ime parametra za user id
			$user_id_name_old = $this->getPanelSettings($what='user_id_name');
			
			$user_id_name = isset($_POST['user_id_name']) ? $_POST['user_id_name'] : 'SID';
			if($user_id_name == '')
				$user_id_name = $user_id_name_old;
			
			$status_name = isset($_POST['status_name']) ? $_POST['status_name'] : 'status';
			$status_default = isset($_POST['status_default']) ? $_POST['status_default'] : '0';
			$url = isset($_POST['url']) ? $_POST['url'] : '';
			
			$sql = sisplet_query("UPDATE srv_panel_settings SET user_id_name='".$user_id_name."', status_name='".$status_name."', status_default='".$status_default."' WHERE ank_id='".$this->anketa."'");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
			
			if($url != ''){
				$sql2 = sisplet_query("UPDATE srv_anketa SET url='".$url."' WHERE id='".$this->anketa."'");
				if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);
			}
			
			// Popravimo ime sistemskega vprasanja
			$sqlS = sisplet_query("UPDATE srv_spremenljivka s, srv_grupa g 
									SET s.variable='".$user_id_name."' 
									WHERE s.variable='".$user_id_name_old."' AND s.gru_id=g.id AND g.ank_id='".$this->anketa."'");
			
			$this->displaySettings();
		}

	}
}