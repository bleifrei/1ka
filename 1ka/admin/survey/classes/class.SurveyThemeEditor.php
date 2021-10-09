<?php 

class SurveyThemeEditor {
	
	private $anketa = null;
	private $profile = null;
	
	private $mobile = '';
	
	static $fonts = array(
		1	=>	'Palatino Linotype, Book Antiqua, Palatino, serif',
		2	=>	'Times New Roman, Times, serif',
		3	=>	'Arial, Helvetica, sans-serif',
		4	=>	'Arial Black, Gadget, sans-serif',
		5	=>	'Comic Sans MS, cursive, sans-serif',
		6	=>	'Impact, Charcoal, sans-serif',
		7	=>	'Lucida Sans Unicode, Lucida Grande, sans-serif',
		8	=>	'Tahoma, Geneva, sans-serif',
		9	=>	'Trebuchet MS, Helvetica, sans-serif',
		10	=>	'Verdana, Geneva, sans-serif',
		11	=>	'Courier New, Courier, monospace',
		12	=>	'Lucida Console, Monaco, monospace',
		13	=>	'Georgia, serif',

	);
	
	function __construct ($anketa, $ajax=false) {
		global $site_path, $global_user_id;
		
		$this->anketa = $anketa;
		
		SurveyInfo::getInstance()->SurveyInit($anketa);
		$row = SurveyInfo::getSurveyRow();
		
		$this->profile = (int)$_GET['profile'];
		
		$this->mobile = (isset($_GET['mobile']) && $_GET['mobile'] == '1') ? '_mobile' : '';
		
		if ($ajax) return;
		
		if ( ! $this->profile > 0 )	die();		
	}

	function display () {
		global $lang;
		global $global_user_id;
				
		SurveyInfo::getInstance()->SurveyInit($this->anketa);
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		$sqla = sisplet_query("SELECT id, name, logo FROM srv_theme_profiles".$this->mobile." WHERE id = '$this->profile'");
		$rowa = mysqli_fetch_array($sqla);
		
		self::new_theme_alert($rowa['name']);
		
		echo '<div id="theme-editor">';
		echo '<input type="hidden" name="profile" id="profile" value="'.$this->profile.'">';
		
		$mobile = (isset($_GET['mobile']) && $_GET['mobile'] == '1') ? 1 : 0;
		echo '<input type="hidden" name="mobile" id="mobile" value="'.$mobile.'">';
		
		echo '<div id="picker"></div>';
			
		echo '<fieldset><legend>'.$lang['srv_skinname'].'</legend>';
		echo '<p>'.$lang['srv_skinname'].': <input type="text" name="skin-name" value="'.$rowa['name'].'" onblur="te_change_name(this);"></p>';
		echo '</fieldset>';
	
		echo '<br />';

		// Mobilni skin nima logotipa
		if($mobile != 1){
			echo '<fieldset><legend>'.$lang['srv_upload_logo'].'</legend>';
			echo '<form name="upload" enctype="multipart/form-data" action="upload.php?anketa=' . $this->anketa . '&logo=1&te=1&profile='.$this->profile.'" method="post" />';
			echo '<p>' . $lang['srv_upload_logo'] . ': ';
			echo '<input type="file" name="fajl" onchange="submit();" onmouseout="survey_upload();" />';				
			if ($rowa['logo'] != '') {
				echo '<p>'.$rowa['logo'];
				echo ' <a href="#" onclick="survey_remove_logo(\''.$this->profile.'\'); return false" title="'.$lang['srv_te_remove_setting'].'"><span class="faicon delete_circle icon-orange_link"></span></a>';
				echo '</p>';
			}	
			echo '</p></form>';
			echo '</fieldset>';
			
			echo '<br />';
		}

		// Mobilni skin nima progressbara
		if($mobile != 1){
			$sqlg = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id = '$this->anketa'");	
			if (mysqli_num_rows($sqlg) > 1) {
				echo '<fieldset><legend>'.$lang['srv_te_progressbar'].'</legend>';
				echo '<div id="theme_progressbar">';
				echo '<p><label>' . $lang['srv_te_progressbar_show'] . ':</label> ';
				echo '<label for="theme_progressbar_1"><input type="radio" id="theme_progressbar_1" name="progressbar" value="1"' . ($row['progressbar'] == 1 ? ' checked="checked"' : '') . ' autocomplete="off"/>' . $lang['yes'] . '</label> ';
				echo '<label for="theme_progressbar_0"><input type="radio" id="theme_progressbar_0" name="progressbar" value="0"' . ($row['progressbar'] == 0 ? ' checked="checked"' : '') . ' autocomplete="off"/>' . $lang['no1'] . '</label> ';
				echo '</p></div>'; # id="theme_progressbar"
				echo '</fieldset>';
				
				echo '<br />';
			}
		}
		
		echo '<fieldset><legend>'.$lang['srv_te_survey_h_text'].'</legend>';
		$this->displayOption(1, 1);
		$this->displayOption(1, 4);
		$this->displayOption(1, 2);
		$this->displayOption(1, 3);
		echo '</fieldset>';
		
		echo '<br />';
		
		echo '<fieldset><legend>'.$lang['srv_te_outer_frame'].'</legend>';
		$this->displayOption(6, 3);
		echo '</fieldset>';
		
		echo '<br />';
		
		echo '<fieldset><legend>'.$lang['srv_te_question_border'].'</legend>';
		$this->displayOption(5, 5);
		echo '</fieldset>';
		
		echo '<br />';
		
		echo '<fieldset><legend>'.$lang['srv_te_question_text'].'</legend>';
		$this->displayOption(2, 1);
		$this->displayOption(2, 4);
		$this->displayOption(2, 2);
		$this->displayOption(5, 3);
		echo '</fieldset>';
		
		echo '<br />';
		
		echo '<fieldset><legend>'.$lang['srv_te_answers_text'].'</legend>';
		$this->displayOption(3, 1);
		$this->displayOption(3, 4);
		$this->displayOption(3, 2);
		$this->displayOption(4, 3);
		echo '</fieldset>';

        echo '<br />';

		// Custom checkbox/radio (stars, smilies, thumbs)
        echo '<fieldset>';
		// Radio/checkboxi za pc
		if($mobile != 1){
			echo '<legend>'.$lang['srv_te_custom_checkbox_radio'].'</legend>';		
			$this->displayOption(7, 7);
			$this->displayOption(7, 15);
			//$this->displayOption(10, 17);
		}
		// Radio/checkboxi za mobitel
		else{
			echo '<legend>'.$lang['srv_te_custom_mobile_checkbox_radio'].'</legend>';	
			$this->displayOption(7, 8);
			$this->displayOption(7, 16);
			// Za mobitel ne rabimo accessibility ikon
			//$this->displayOption(10, 17);
		}
        echo '</fieldset>';

		echo '<br />';
		
		// Tooltipster/slovar/glossary
		echo '<fieldset><legend>'.$lang['srv_te_custom_glossary_popup'].'</legend>';
		$this->displayOption(8, 9);
		$this->displayOption(8, 11);
		$this->displayOption(8, 10);
		$this->displayOption(8, 12);
		echo '</fieldset>';

		echo '<br />';
		
		echo '<fieldset><legend>'.$lang['srv_te_custom_glossary_keywords'].'</legend>';
		$this->displayOption(9, 9);
		$this->displayOption(9, 13);
		$this->displayOption(9, 14);
		echo '</fieldset>';
		
		
		echo '<p><label><input type="checkbox" name="current_skin" value="1" '.($row['skin_profile'] == $rowa['id']?'checked disabled':'').' onchange="$(this).attr(\'disabled\', true); te_change_profile(\''.$this->profile.'\'); return false;"> '.$lang['srv_save_set_theme'].'</label></p>';
		
		echo '<p>';
		echo '<div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange floatLeft" href="index.php?anketa='.$this->anketa.'&a=tema"><span>'.$lang['edit1337'].'</span></a></div>';
		echo '</p>';
		
		echo '</div>';
		
		$this->preview();
		
		?>
		<script>
			$(document).ready(function() {
				init_themeEditor();
			});
		</script>
		<?
		
		//echo '</div>';
			
	}
	
	static function new_theme_alert ($name, $css=false) {
		global $lang;
		
		return;
		if ($_GET['newalert'] != '1') return;
		
		echo '<div id="new_theme_alert">';
		echo '<p>';
		printf($lang['srv_new_theme_alert'.($css?'_css':'')], $name);
		echo '</p>';
		echo '</div>';
		
		?>
		<script>
			$('#new_theme_alert').delay(5000).slideUp();
		</script>
		<?
		
	}
	
	/**
	* poskrbi za razlicne opcije nastavitev teme
	* 
	* za prikaz v anketi skrbi funkcija themeEditor(); v main/Survey.php
	* 
	* @param mixed $id
	* @param mixed $type
	*/
	function displayOption ($id, $type) {
		global $lang;

		$sql = sisplet_query("SELECT value FROM srv_theme_editor".$this->mobile." WHERE profile_id='$this->profile' AND id='$id' AND type='$type'");
		$row = mysqli_fetch_array($sql);
		
		echo '<p>';
		
		// pisava
		if ($type == 1) {
			
			echo ''.$lang['srv_te_font_family'].': <select name="font'.$id.'" data-id="'.$id.'" data-type="'.$type.'" class="auto-save">';
			echo '<option value=""'.(''==$row['value']?' selected':'').' style="font-size:13px">'.$lang['srv_te_default'].'</option>';
			foreach (self::$fonts AS $key => $val) {
				echo '<option value="'.$key.'"'.($key==$row['value']?' selected':'').' style="font-family: '.$val.'; font-size:13px">'.substr($val, 0, strpos($val, ',')).'</option>';
			}
			echo '</select> ';

			if ($row['value'] != '')
				echo '<a href="#" onclick="te_remove_setting(\''.$id.'\', \''.$type.'\'); return false;" title="'.$lang['srv_te_remove_setting'].'"><span class="sprites arrow_undo"></span></a>';

		// barva pisave, barva ozadja
		} elseif (($type == 3 && $id != 1) || in_array($type, [2, 9, 10, 13, 15, 16])) {
			
			if ($row['value'] == '') $value = '#000000'; else $value = $row['value'];
			
			if ($type == 2 || $type == 9)
				echo ''.$lang['srv_te_font_color'].': ';
			elseif ($type == 3)
				echo ''.$lang['srv_te_background_color'].': ';
			elseif($type == 10)
				echo ''.$lang['srv_te_custom_border_color'].': ';
			elseif($type == 13)
				echo ''.$lang['srv_te_custom_background_keywords'].': ';
            elseif($type == 15)
                echo ''.$lang['srv_te_custom_icon_pc_color'].': ';
            elseif($type == 16)
                echo ''.$lang['srv_te_custom_icon_mobile_color'].': ';
			
			if ($row['value'] == '') echo '<span><a href="#" onclick="$(\'#color-'.$id.'-'.$type.'\').show(); $(this).parent().hide(); return false;" title="'.$lang['edit4'].'">'.$lang['srv_te_default'].' <span class="faicon edit"></span></a></span>';
			
			echo '<span id="color-'.$id.'-'.$type.'" '.($row['value']==''?'style="display:none;"':'').'>';
			echo '<input type="text" id="color'.$id.'-'.$type.'" class="colorwell auto-save" name="color'.$id.'-'.$type.'" value="'.$value.'" data-id="'.$id.'" data-type="'.$type.'"> ';
			echo '<a href="#" onclick="te_remove_setting(\''.$id.'\', \''.$type.'\'); return false;" title="'.$lang['srv_te_remove_setting'].'"><span class="sprites arrow_undo"></span></a>';
			echo '</span>';
		
		// velikost pisave
		} elseif ($type == 4) {
			
			echo ''.$lang['srv_te_font_size'].': <select name="fontsize'.$id.'" data-id="'.$id.'" data-type="'.$type.'" class="auto-save">';
			echo '<option value=""'.(''==$row['value']?' selected':'').'>'.$lang['srv_te_default'].'</option>';
			for ($i=50; $i<=200; $i+=10) {
				echo '<option value="'.$i.'"'.($i==$row['value']?' selected':'').' style="font-size: '.$i.'%;">'.$i.'%</option>';
			}
			echo '</select> ';
			
			if ($row['value'] != '')
				echo '<a href="#" onclick="te_remove_setting(\''.$id.'\', \''.$type.'\'); return false;" title="'.$lang['srv_te_remove_setting'].'"><span class="sprites arrow_undo"></span></a>';
			
		// border vprasanja	
		} elseif ($type == 5) {
			
			if ($row['value'] == '') $value = '1'; else $value = $row['value'];

			echo $lang['srv_te_question_border'].': <select name="question_border'.$id.'" data-id="'.$id.'" data-type="'.$type.'" class="auto-save">';
			echo '<option value="" '.(''==$row['value']?' selected':'').'>'.$lang['default'].'</option>';
			echo '<option value="0" '.($row['value']=='0'?' selected':'').'>'.$lang['srv_te_question_border_0'].'</option>';
			echo '<option value="1" '.($row['value']=='1'?' selected':'').'>'.$lang['srv_te_question_border_1'].'</option>';
			echo '<option value="2" '.($row['value']=='2'?' selected':'').'>'.$lang['srv_te_question_border_2'].'</option>';
			echo '</select> ';

        // izbira custom checkbox/radio gumbov
        } elseif($type == 7 ) {
            echo $lang['srv_te_custom_icon_pc'] . ': ';
            echo '<select id="izbira-checkbox-gumbov" data-id="'.$id.'" data-type="'.$type.'" class="auto-save">
                <option value="0" '.(($row['value'] == 0 || is_null($row['value'])) ? " selected":"").'>'.$lang['default'].'</option>
                <option value="18" '.($row['value'] == 18 ? " selected":"").'>18 px</option>
                <option value="21" '.($row['value'] == 21 ? " selected":"").'>21 px</option>
                <option value="25" '.($row['value'] == 25 ? " selected":"").'>25 px</option>
                <option value="30" '.($row['value'] == 30 ? " selected":"").'>30 px</option>
                <option value="35" '.($row['value'] == 35 ? " selected":"").'>35 px</option>
                <option value="40" '.($row['value'] == 40 ? " selected":"").'>40 px</option>
                <option value="45" '.($row['value'] == 45 ? " selected":"").'>45 px</option>
                <option value="50" '.($row['value'] == 50 ? " selected":"").'>50 px</option>
                <option value="55" '.($row['value'] == 55 ? " selected":"").'>55 px</option>
              </select>';


        } elseif($type == 8) {
            echo $lang['srv_te_custom_icon_mobile'] . ': ';
            echo '<select id="izbira-checkbox-gumbov" data-id="'.$id.'" data-type="'.$type.'" class="auto-save">
                <option value="0" '.(($row['value'] == 0 || is_null($row['value'])) ? " selected":"").'>'.$lang['default'].'</option>
                <option value="21" '.($row['value'] == 20 ? " selected":"").'>20 px</option>
                <option value="25" '.($row['value'] == 25 ? " selected":"").'>25 px</option>
                <option value="30" '.($row['value'] == 30 ? " selected":"").'>30 px</option>
                <option value="35" '.($row['value'] == 35 ? " selected":"").'>35 px</option>
                <option value="40" '.($row['value'] == 40 ? " selected":"").'>40 px</option>
                <option value="45" '.($row['value'] == 45 ? " selected":"").'>45 px</option>
                <option value="50" '.($row['value'] == 50 ? " selected":"").'>50 px</option>
                <option value="55" '.($row['value'] == 55 ? " selected":"").'>55 px</option>
              </select>';

		// Izpišemo border opcije
        } elseif($type == 11) {
				echo $lang['srv_te_custom_border_size'].': ';
				echo '<select name="bordersize'.$id.'" data-id="'.$id.'" data-type="'.$type.'" class="auto-save">';
				echo '<option value=""'.(''==$row['value']?' selected':'').'>'.$lang['srv_te_default'].'</option>';
				for ($i=1; $i<=6; $i++) {
					echo '<option value="'.$i.'"'.($i==$row['value']?' selected':'').'>'.$i.'px</option>';
				}
				echo '</select> ';

		} elseif($type == 12) {
			echo $lang['srv_te_custom_border_radius'].': ';
			echo '<select name="borderradius'.$id.'" data-id="'.$id.'" data-type="'.$type.'" class="auto-save">';
			echo '<option value=""'.(''==$row['value']?' selected':'').'>'.$lang['srv_te_default'].'</option>';
			for ($i=5; $i<=30; $i+=5) {
				echo '<option value="'.$i.'"'.($i==$row['value']?' selected':'').'>'.$i.'px</option>';
			}
			echo '</select> ';
		
		// Stil pisave bold, italic, underline
		} elseif($type == 14) {
			echo $lang['srv_te_custom_font_style_keywords'].': ';
			echo '<select name=""keyword_style'.$id.'" data-id="'.$id.'" data-type="'.$type.'" class="auto-save">';
			echo '<option value=""'.(''==$row['value']?' selected':'').'>'.$lang['srv_te_default'].'</option>';
			echo '<option value="bold" '.('bold'==$row['value']?' selected':'').'>'.$lang['srv_te_custom_font_style_keywords_bold'].'</option>';
			echo '<option value="italic" '.('italic'==$row['value']?' selected':'').'>'.$lang['srv_te_custom_font_style_keywords_italic'].'</option>';
			echo '<option value="underline" '.('underline'==$row['value']?' selected':'').'>'.$lang['srv_te_custom_font_style_keywords_underline'].'</option>';
			echo '</select> ';
			
		// Accessibility checkbox (radio/checkboxi niso obarvani)
		} elseif($type == 17) {
			echo '<label for="accessibility'.$id.'">'.$lang['srv_te_custom_icon_accessibility'].': ';
			echo '<input type="checkbox" value="1" name="accessibility'.$id.'" id="accessibility'.$id.'" data-id="'.$id.'" data-type="'.$type.'" class="auto-save" '.($row['value'] == '1' ? ' checked="checked"' : '').'></label>';
		}

		echo '</p>';
	}
	
	function preview () {
		
		$sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$this->anketa' ORDER BY vrstni_red ASC LIMIT 1");
		$row = mysqli_fetch_array($sql);
		$grupa = $row['id'];
		
		$mobile = (isset($_GET['mobile']) && $_GET['mobile'] == '1') ? '&mobile=1' : '';
		
		echo '<div id="theme-preview"><iframe id="theme-preview-iframe" src="'.SurveyInfo::getSurveyLink().'&grupa='.$grupa.'&no_preview=1&preview=on&theme_profile='.$this->profile.'&theme-preview=1'.$mobile.'"></iframe><div class="theme-overflow"></div></div>';
	}
	
	static function getFont ($font) {
		return self::$fonts[$font];
	}
	
	function ajax() {
		
		if ($_GET['a'] == 'auto_save') {
			$this->ajax_auto_save();
			
		} elseif ($_GET['a'] == 'change_profile') {
			$this->ajax_change_profile();
		
		} elseif ($_GET['a'] == 'change_profile_oldskin') {
			$this->ajax_change_profile_oldskin();
			
		} elseif ($_GET['a'] == 'delete_profile') {
			$this->ajax_delete_profile();
			
		} elseif ($_GET['a'] == 'add_theme') {
			$this->ajax_add_theme();
			
		} elseif ($_GET['a'] == 'change_name') {
			$this->ajax_change_name();
			
		}
		
	}
	
	function ajax_auto_save() {
		
		$id = $_POST['id'];
		$type = $_POST['type'];
		$value = $_POST['value'];
		
		if ($value == '') {
			$s = sisplet_query("DELETE FROM srv_theme_editor".$this->mobile." WHERE profile_id='$this->profile' AND id='$id' AND type='$type'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		} else {
			$s = sisplet_query("REPLACE INTO srv_theme_editor".$this->mobile." (profile_id, id, type, value) VALUES ('$this->profile', '$id', '$type', '$value')");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		}
	}
	
	function ajax_change_profile () {
		
		$sql = sisplet_query("SELECT skin FROM srv_theme_profiles".$this->mobile." WHERE id = '$this->profile'");
		$row = mysqli_fetch_array($sql);
		
		if($this->mobile == '_mobile')
			$s = sisplet_query("UPDATE srv_anketa SET mobile_skin='".$row['skin']."', skin_profile_mobile='".$this->profile."' WHERE id = '".$this->anketa."'");
		else
			$s = sisplet_query("UPDATE srv_anketa SET skin='".$row['skin']."', skin_profile='".$this->profile."' WHERE id = '".$this->anketa."'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
	}
	
	function ajax_change_profile_oldskin () {
		global $global_user_id;
		
		$skin = $_POST['skin'].'';
		$name = str_replace($global_user_id.'_', '', $skin);
		
		$sql = sisplet_query("INSERT INTO srv_theme_profiles".$this->mobile." (id, usr_id, skin, name) VALUES ('', '$global_user_id', '$skin', '$name')");
		$profile = mysqli_insert_id($GLOBALS['connect_db']);
		
		$s = sisplet_query("UPDATE srv_anketa SET skin='".$skin."', skin_profile='".$profile."' WHERE id = '".$this->anketa."'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		echo 'index.php?anketa='.$this->anketa.'&a=theme-editor&profile='.$profile.'&newalert=1';
		
	}
	
	function ajax_delete_profile () {
		global $site_path;
		global $global_user_id;
		
		$row = SurveyInfo::getSurveyRow();
		
		if($this->mobile == '_mobile'){
			if ($row['skin_profile_mobile'] == $this->profile) {
				$s = sisplet_query("UPDATE srv_anketa SET skin_profile_mobile='0', mobile_skin='MobileBlue' WHERE id = '".$this->anketa."'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
			
			$sql = sisplet_query("SELECT skin FROM srv_theme_profiles_mobile WHERE id = '$this->profile'");
			$row = mysqli_fetch_array($sql);
			
			// ce ima svojo temo, jo zbrisemo
			if ( strpos($row['skin'], $global_user_id.'_') !== false ) {
				$dir = $site_path . 'main/survey/skins/';
				unlink($dir.$row['skin'].'.css');
			}
			
			$s = sisplet_query("DELETE FROM srv_theme_profiles_mobile WHERE id = '$this->profile'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		}
		else{
			if ($row['skin_profile'] == $this->profile) {
				$s = sisplet_query("UPDATE srv_anketa SET skin_profile='0', skin='1kaBlue' WHERE id = '".$this->anketa."'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
			
			$sql = sisplet_query("SELECT skin FROM srv_theme_profiles WHERE id = '$this->profile'");
			$row = mysqli_fetch_array($sql);
			
			// ce ima svojo temo, jo zbrisemo
			if ( strpos($row['skin'], $global_user_id.'_') !== false ) {
				$dir = $site_path . 'main/survey/skins/';
				unlink($dir.$row['skin'].'.css');
			}
			
			$s = sisplet_query("DELETE FROM srv_theme_profiles WHERE id = '$this->profile'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		}
	}
	
	function ajax_add_theme() {
		global $lang;
		global $site_url;
		
		ob_clean();
		
		$row = SurveyInfo::getSurveyRow();
	
		$st = new SurveyTheme($this->anketa, true);
		$groups = $st->getGroups();
		
		$default = 'Default';
		
		echo '<h3 style="color:#900">'.$lang['srv_add_theme'].'</h3>';
		
		echo '<p>'.$lang['srv_select_base_theme'].': <select name="new_theme" id="new_theme" onchange="$(\'input[name=name]\').val( $(this).val() + \'\' );">';
		foreach ($groups[0]['skins'] AS $key => $val) {
			$skin = str_replace('.css', '', $val);
			echo '<option value="'.$skin.'" '.($skin==$default?'selected':'').'>'.$skin.'</option>';
		}
		echo '</select> <span style="font-size:90%; color: gray">'.$lang['srv_select_base_theme_2'].'</span></p>';
		
		echo '<p>'.$lang['srv_skinname'].': <input type="text" name="name" value="'.$default.'"></p>';
		
		echo '<p><input type="submit" value="'.$lang['add'].'" onclick="window.location.href=\'index.php?anketa='.$this->anketa.'&a=theme-editor&profile_new=\'+$(\'#new_theme\').val()+\'&name=\'+$(\'input[name=name]\').val(); return false;"></p>';

		echo '<a href="#" onclick="$(\'#vrednost_edit\').hide().html(\'\'); return false;" style="position:absolute; right:10px; bottom:10px">'.$lang['srv_zapri'].'</a>';
	}
	
	function ajax_change_name() {
		
		$s = sisplet_query("UPDATE srv_theme_profiles".$this->mobile." SET name='".$_POST['name']."' WHERE id = '".$_GET['profile']."'");
	}
	
}

?>