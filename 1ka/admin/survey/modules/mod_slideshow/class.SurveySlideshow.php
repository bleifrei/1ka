<?php
	
class SurveySlideshow {
	
	private $sid = null;
	private $settings = array();
	
	public function __construct($anketa = null) {
		if ($anketa == null) {
			die("class.SurveySlideshow -> anketa ID missing!");
		}
		
		$this->sid = $anketa;
		SurveyInfo::getInstance()->SurveyInit($anketa);
		$this->reloadSettings(); 

	}
	
	public function ajax() {
		if ($this->sid != null) {
			switch ($_GET['a']) {
				case 'reset_interval' :
					$this->ResetSlideshowInterval();
					$this->ShowSlideshowSetings();
					break;		
				case 'save_settings' :
					$this->SaveSlideshowSettings();
					break;		
				default:			
					print_r("<pre>");
					print_r($_POST);
					print_r($_GET);
					break;
			}			
			
		} else {
			die("Class Slideshow not inited!");
		}
	}
	
	/** prebere nastavitve iz baze
	 * 
	 */
	public function reloadSettings() {
		$slide_settings_qry = sisplet_query("SELECT * FROM srv_slideshow_settings WHERE ank_id='$this->sid'");
		$slide_settings = mysqli_fetch_assoc($slide_settings_qry);
		$this->settings = $slide_settings; 	

	}

	/** vrne vse nastavitve ali posamezno vrednost nastavitev za slideshow
	 * 
	 */
	public function getSettings($what = null) {
		if (!is_countable($this->settings) || !count($this->settings) > 0) {
			$this->reloadSettings();
		}
		
		if ($what == null) {
			return $this->settings;
		} else {
			return $this->settings[$what];
		}
	}
	
	/**
    * posebne opcije in navodile za anketo slideshow
    */
    public function ShowSlideshowSetings () {
    	global $lang;
    	
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		$slide_settings_qry = sisplet_query("SELECT * FROM srv_slideshow_settings WHERE ank_id='$this->sid'");
		$slide_settings = mysqli_fetch_assoc($slide_settings_qry);

		echo '<fieldset><legend>'.$lang['settings'].'</legend>';
		
		#set timer interval
		echo '<div class="slide_sett_option">';
		echo '<span class="slide_sett_option_lbl" ><label for="timer" >' . $lang['srv_slideshow_set_timer'] . '&nbsp;</label></span>';
		echo '<label for="slide_fixed_interval" >' . $lang['srv_slideshow_set_fixed'] . '&nbsp;</label>';
		echo '<input type="checkbox" id="slide_fixed_interval" name="slide_fixed_interval" value="1"' . ($slide_settings['fixed_interval'] == 1 ? ' checked' : '') . '>&nbsp;';
		echo '<select name="slideshow_timer" id="slideshow_timer">';
		# od 1-15 mamo za vsako sekundo
		for ($t = 1; $t < 15; $t += 1) {
			echo '<option value="' . $t . '"' . (($t == $slide_settings['timer']) ? ' selected' : '') . '>';
			echo '' . (substr(bcdiv($t, 60), 0, 4)) . '' . $lang['srv_minutes'] . ' ';
			echo '' . (bcmod($t, 60)) . '' . $lang['srv_seconds'] . '';
			echo '</option>';
		}
		#od 15 do 600 mamo na 15s 		
		for ($t = 15; $t <= 600; $t += 15) {
			echo '<option value="' . $t . '"' . (($t == $slide_settings['timer']) ? ' selected' : '') . '>';
			echo '' . (substr(bcdiv($t, 60), 0, 4)) . '' . $lang['srv_minutes'] . ' ';
			echo '' . (bcmod($t, 60)) . '' . $lang['srv_seconds'] . '';
			echo '</option>';
		}
		echo '</select>&nbsp;';
		echo '<span class="as_link" id="link_slideshow_reset_interval" title="' . $lang['srv_slideshow_link_reset_interval'] . '">' . $lang['srv_slideshow_link_reset_interval'] . '</span><br/>' . NEW_LINE;
		echo '</div>'; // slide_sett_option 

		#save entries
		echo '<div class="slide_sett_option">';
		echo '<span class="slide_sett_option_lbl" ><label for="save_entries" >' . $lang['srv_slideshow_sett_save_entries_lbl'] . '&nbsp;</label></span>';
		echo '<input type="radio" id="slide_save_entries_0" name="slide_save_entries" value="0"' . ($slide_settings['save_entries'] == 0 ? ' checked' : '') . '>&nbsp;';
		echo '<label for="slide_save_entries_0" >' . $lang['srv_slideshow_sett_save_entries_opt_0'] . '&nbsp;</label>';
		echo '<input type="radio" id="slide_save_entries_1" name="slide_save_entries" value="1"' . ($slide_settings['save_entries'] == 1 ? ' checked' : '') . '>&nbsp;';
		echo '<label for="slide_save_entries_1" >' . $lang['srv_slideshow_sett_save_entries_opt_1'] . '&nbsp;</label>';
		echo '</div>'; // slide_sett_option 

		#autostart
		echo '<div class="slide_sett_option">';
		echo '<span class="slide_sett_option_lbl" ><label for="timer" >' . $lang['srv_slideshow_sett_autostart_lbl'] . '&nbsp;</label></span>';
		echo '<input type="radio" id="slide_autostart_0" name="slide_autostart" value="0"' . ($slide_settings['autostart'] == 0 ? ' checked' : '') . '>&nbsp;';
		echo '<label for="slide_autostart_0" title="' . $lang['srv_slideshow_sett_autostart_opt_0'] . '">' . $lang['srv_slideshow_sett_autostart_opt_0_short'] . '&nbsp;</label>';
		echo '<input type="radio" id="slide_autostart_1" name="slide_autostart" value="1"' . ($slide_settings['autostart'] == 1 ? ' checked' : '') . '>&nbsp;';
		echo '<label for="slide_autostart_1" title="' . $lang['srv_slideshow_sett_autostart_opt_1'] . '">' . $lang['srv_slideshow_sett_autostart_opt_1_short'] . '&nbsp;</label>';
		echo '<input type="radio" id="slide_autostart_2" name="slide_autostart" value="2"' . ($slide_settings['autostart'] == 2 ? ' checked' : '') . '>&nbsp;';
		echo '<label for="slide_autostart_2" title="' . $lang['srv_slideshow_sett_autostart_opt_2'] . '">' . $lang['srv_slideshow_sett_autostart_opt_2_short'] . '&nbsp;</label>';
		echo '<input type="radio" id="slide_autostart_3" name="slide_autostart" value="3"' . ($slide_settings['autostart'] == 3 ? ' checked' : '') . '>&nbsp;';
		echo '<label for="slide_autostart_3" title="' . $lang['srv_slideshow_sett_autostart_opt_3'] . '">' . $lang['srv_slideshow_sett_autostart_opt_3_short'] . '&nbsp;</label>';
		echo '</div>'; // slide_sett_option 
		
		#next  button
		echo '<div class="slide_sett_option">';
		echo '<span class="slide_sett_option_lbl" ><label for="next" >' . $lang['srv_slideshow_sett_next_button_lbl'] . '&nbsp;</label></span>';
		echo '<input type="radio" id="slide_next_0" name="slide_next" value="0"' . ($slide_settings['next_btn'] == 0 ? ' checked' : '') . '>&nbsp;';
		echo '<label for="slide_next_0" >' . $lang['srv_slideshow_sett_button_opt_0'] . '&nbsp;</label>';
		echo '<input type="radio" id="slide_next_1" name="slide_next" value="1"' . ($slide_settings['next_btn'] == 1 ? ' checked' : '') . '>&nbsp;';
		echo '<label for="slide_next_1" >' . $lang['srv_slideshow_sett_button_opt_1'] . '&nbsp;</label>';
		echo '</div>'; // slide_sett_option 
		
		#back  button
		echo '<div class="slide_sett_option">';
		echo '<span class="slide_sett_option_lbl" ><label for="back" >' . $lang['srv_slideshow_sett_back_button_lbl'] . '&nbsp;</label></span>';
		echo '<input type="radio" id="slide_back_0" name="slide_back" value="0"' . ($slide_settings['back_btn'] == 0 ? ' checked' : '') . '>&nbsp;';
		echo '<label for="slide_back_0" >' . $lang['srv_slideshow_sett_button_opt_0'] . '&nbsp;</label>';
		echo '<input type="radio" id="slide_back_1" name="slide_back" value="1"' . ($slide_settings['back_btn'] == 1 ? ' checked' : '') . '>&nbsp;';
		echo '<label for="slide_back_1" >' . $lang['srv_slideshow_sett_button_opt_1'] . '&nbsp;</label>';
		echo '</div>'; // slide_sett_option 

		#pause button
		echo '<div class="slide_sett_option">';
		echo '<span class="slide_sett_option_lbl" ><label for="pause" >' . $lang['srv_slideshow_sett_pause_button_lbl'] . '&nbsp;</label></span>';
		echo '<input type="radio" id="slide_pause_0" name="slide_pause" value="0"' . ($slide_settings['pause_btn'] == 0 ? ' checked' : '') . '>&nbsp;';
		echo '<label for="slide_pause_0" >' . $lang['srv_slideshow_sett_button_opt_0'] . '&nbsp;</label>';
		echo '<input type="radio" id="slide_pause_1" name="slide_pause" value="1"' . ($slide_settings['pause_btn'] == 1 ? ' checked' : '') . '>&nbsp;';
		echo '<label for="slide_pause_1" >' . $lang['srv_slideshow_sett_button_opt_1'] . '&nbsp;</label>';
		echo '</div>'; // slide_sett_option 
		echo '</fieldset>';
		
		#saving
		echo '<br class="clr" />';
		echo '<span class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="slideshow_save_settings(); return false;"><span>';
		echo $lang['edit1337'] . '</span></a></div></span>';
		echo '<div class="clr"></div>';
		echo '<div id="success_save"></div>';		
		
    }
    
    function ResetSlideshowInterval() {
    	if ((int)$_POST['timer'] > 0 && $this->sid > 0) {
    		$timer = (int)$_POST['timer'];
    		$fixed_interval = (int)$_POST['fixed_interval'];
			# shranimo v bazo
    		$sqlInsertString = "INSERT INTO srv_slideshow_settings (ank_id, fixed_interval, timer) VALUES ('$this->sid', '$fixed_interval', '$timer' ) ON DUPLICATE KEY UPDATE fixed_interval = '$fixed_interval', timer = '$timer' ";		    		
			$sqlInsertQry = sisplet_query($sqlInsertString);
			
			# ponastavimo timerje pri vprašanjih
			#zloopamo skozi vprašanja
			$_spr_ids = array();
			$sql = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->sid'");
			while($row = mysqli_fetch_assoc($sql)) {
				$_spr_ids[] = $row['id'];
			}

			if (count($_spr_ids) > 0) {
				$update_str = "UPDATE srv_spremenljivka SET timer = '".(int)$_POST['timer']."' WHERE id IN (".implode(',',$_spr_ids).")";
				$update_qry = sisplet_query($update_str);

				# spremenimo timestamp
				Common::updateEditStamp();
			}				
    	}
    }

    /**
     */
    function setSlideshowSkin() {
    	global $site_path, $site_url;
    	# ko prvič nastavimo nastavitve, nastavimo skin ankete na: slideshow, če obstaja
    	$row = SurveyInfo::getInstance()->getSurveyRow();

    	$slide_settings_qry = sisplet_query("SELECT * FROM srv_slideshow_settings WHERE ank_id='$this->sid'");
    	if (mysqli_num_rows($slide_settings_qry) ==  0 ) {
    		$prefix = '';
    		$sql_string = null;
    		
    		# skin nastavimo samo prvič, če uporabnik še ni ničesar spreminjal in če fajl fizično obstaja
    		$dir = $site_path . 'main/survey/skins/';
    		$skin_name = 'Slideshow';

    		if (file_exists($dir.$skin_name.'.css')) {
				$sql_string .= $prefix." skin='$skin_name'";
				$prefix = ',';
			}

			$sql_string .= $prefix." concl_link='1', concl_back_button='0'";
    		$prefix = ',';
    		
    		$sql_string .= $prefix." progressbar='0'";
    		$prefix = ',';
    		
    		$sql_string .= $prefix." url = '".SurveyInfo::getSurveyLink() ."?preview=on'";

    		if ($sql_string != null) {
    			$sql = sisplet_query("UPDATE srv_anketa SET".$sql_string." WHERE id='$this->sid'");
    		}
    		#vstavimo še osnovni zapis v tabelo nastavitev slideshovow
    		$slide_settings_qry = sisplet_query("INSERT INTO srv_slideshow_settings  (ank_id) VALUES ('$this->sid')");

    	}
    }
    
    /** shrani nastavitve prezentacije za posamezno anketo
     * 
     * Enter description here ...
     */
    private function SaveSlideshowSettings() {
		
		$timer = (int)$_POST['timer'];
    	$fixed_interval = (int)$_POST['fixed_interval'];
    	$save_entries = (int)$_POST['save_entries'];
    	$autostart = (int)$_POST['autostart'];
    	$next_btn = (int)$_POST['next_btn'];
    	$back_btn = (int)$_POST['back_btn'];
    	$pause_btn = (int)$_POST['pause_btn'];

    	# shranimo v bazo
    	$sqlInsertString = "INSERT INTO srv_slideshow_settings". 
    		" (ank_id, fixed_interval, timer, save_entries, autostart, next_btn, back_btn, pause_btn)".
    		" VALUES ('$this->sid', '$fixed_interval', '$timer', '$save_entries', '$autostart', '$next_btn', '$back_btn', '$pause_btn' )".
    		" ON DUPLICATE KEY UPDATE fixed_interval = '$fixed_interval', timer = '$timer', save_entries = '$save_entries', autostart = '$autostart', next_btn = '$next_btn', back_btn = '$back_btn', pause_btn = '$pause_btn' ";		    		

    	$sqlInsertQry = sisplet_query($sqlInsertString);
    }
}	