<?php 

class SurveyTheme {
	
	private $sid = null;					# id ankete
	private $surveyInfo = null;				# podatki ankete

	public $current_skin = null;			# trenutni skin
	public $current_mobile_skin = null;		# trenutni mobilni skin
	private $current_group = null;			# grupa trenutnega skina
	private $groups = array();				# grupe z skini

	function __construct($sid=0, $themePreview = false) {
		global $site_path;
		global $site_domain;
		global $global_user_id;
		global $admin_type;
		
		$this->sid = $sid;

		SurveyInfo::getInstance()->SurveyInit($sid);
		$this->surveyInfo = SurveyInfo::getInstance()->getSurveyRow();
		$this->current_skin = $this->surveyInfo['skin'];
		$this->current_mobile_skin = $this->surveyInfo['mobile_skin'];

		# polovimo grupe skinov
		# dodamo sistemsko
		$this->groups['0'] = array('name'=>'Sistemske teme');
		$this->groups['-1'] = array('name'=>'Lastne teme');
		$this->groups['-2'] = array('name'=>'Safe teme');
		$this->groups['-3'] = array('name'=>'Mobilne teme');
		
		$skinsArray = array();
		$skinsArrayPersonal = array();
		
		# polovimo vse skine v direktoriju
		$dir = opendir($site_path . 'main/survey/skins/');
		while ($file = readdir($dir)) {
			
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			if ($ext == 'css' && $file[0] != '_' && $file[0] != '.') {
					
				// Lastne teme
				if (strpos($file, $global_user_id.'_') === true) {
					$this->groups[-1]['skins'][] = $file;
					
					if ($file == $this->current_skin.'.css')
						$this->current_group = -1;
				}
				// Ni uporabniska tema
				else {
					
					// Standardni skini
					$standard_skins = array(
						1 => '1kaBlue.css',
						2 => '1kaRed.css',
						3 => '1kaOrange.css', 
						4 => '1kaGreen.css', 
						5 => '1kaPurple.css', 
						6 => '1kaBlack.css',
						7 => '1kaOffice.css',
						8 => '1kaNature.css',
						9 => 'Otroci3.css',
						10 => 'Otroci4.css',
						11 => 'Embed.css', 
						12 => 'Embed2.css', 
						13 => 'Slideshow.css'
					);

                    $standard_skins[14] = 'Uni.css';
                    $standard_skins[15] = 'Fdv.css';
                    $standard_skins[16] = 'Cdi.css';
                    $standard_skins[17] = 'WebSM.css';
					
					// Novi safe skini so v loceni skupini
					$safe_skins = array(
						'Center.css', 
						'Center2.css', 
						'Oko.css', 
						'Oko2.css', 
						'Otroci.css', 
						'Otroci2.css', 
						'Safe.css', 
						'Safe2.css', 
						'Safe3.css'
					);
					
					// Mobile skini
					$mobile_skins = array(
						1 => 'MobileBlue.css', 
						2 => 'MobileRed.css', 
						3 => 'MobileOrange.css', 
						4 => 'MobileGreen.css', 
						5 => 'MobilePurple.css', 
						6 => 'MobileBlack.css'
					);

                    $mobile_skins[7] = 'MobileUni.css';
					$mobile_skins[8] = 'MobileFdv.css';
					$mobile_skins[9] = 'MobileCdi.css';
					
					// Safe skini
					if(in_array($file, $safe_skins)){
						$this->groups[-2]['skins'][] = $file;
						
						if ($file == $this->current_skin.'.css')
							$this->current_group = -2;
					}					
					// Mobile skini
					elseif($key = array_search($file, $mobile_skins)){
						$this->groups[-3]['skins'][$key] = $file;
					}				
					// Navadni skini
					elseif($key = array_search($file, $standard_skins)){
						$this->groups[0]['skins'][$key] = $file;
						
						if ($file == $this->current_skin.'.css')
							$this->current_group = 0;
					}
				}
			}
		}
		
		// Sortiramo skine
		ksort($this->groups[0]['skins']);
		
		// Sortiramo safe skine - po abecedi
		sort($this->groups[-2]['skins']);
		
		// Sortiramo mobilne skine
		ksort($this->groups[-3]['skins']);
	}

	function getGroups () {
	
		return $this->groups;
	}
	
	function Ajax() {
		switch ($_GET['a']) {
			case 'changeGroup':
				$this->displayGroupThemes($_POST['gid']);
			break;
			case 'changeTheme':
				$this->changeTheme($_POST['css'],$_POST['gid']);
			break;
			case 'changeProgressbar':
				$this->changeProgressbar();
			break;
			case 'theme_rename':
				$this->themeRename($_POST['msg']);
			break;
			case 'theme_rename_confirm':
				$this->themeRenameConfirm();
			break;
			case 'theme_delete':
				$this->themeDelete();
			break;
			case 'add_theme':
				$this->ajax_add_theme();
			break;
            case 'checboxThemeSave':
                $this->ajaxSaveChecboxTheme($_POST['anketa'],$_POST['checkbox']);
            breake;
			
			default:
			print_r("<pre>");
			print_r($_POST);
			print_r($_GET);
			break;
		}
	}

	
	function displayGroup($groupId = null) {
		global $lang;
		
		if ($groupId == null)
			$groupId = 0;
		
		echo '<div class="themes-content">';
		
		echo '<div id="div_theme_groups">';
		$this->displayGroupSelector($groupId);
		echo '</div>';

		echo '<div id="div_theme_group_holder">';
		$this->displayGroupThemes($groupId);
		echo '</div>';

		// tole sem premaknil izven onload.js da se ne klice vedno po nepotrebnem
		?><script> themes_init(); </script><?
	
		echo '</div>';
	}
	
	function handleEditing() {
		global $lang;
		global $global_user_id;
		
		// preusmeritev, ko kliknemo na prilagodi pri sistemski temi in naredimo nov profil
		if ( isset($_GET['profile_new']) ) {
			
			if ($_GET['name'] != '')
				$name = $_GET['name'];
			else
				$name = $_GET['profile_new'].'';
			
			$sql = sisplet_query("INSERT INTO srv_theme_profiles (id, usr_id, skin, name) VALUES ('', '$global_user_id', '".$_GET['profile_new']."', '".$name."')");
			$profile = mysqli_insert_id($GLOBALS['connect_db']);
			
			$s = sisplet_query("UPDATE srv_anketa SET skin='".$_GET['profile_new']."', skin_profile='".$profile."' WHERE id = '".$this->sid."'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			
			header("Location: index.php?anketa=".$this->sid."&a=theme-editor&profile=".$profile.'&newalert=1');
			die();
		}
		
		// preusmeritev, ko kliknemo na prilagodi pri sistemski MOBILNI temi in naredimo nov profil
		if ( isset($_GET['profile_new_mobile']) ) {
			
			if ($_GET['name'] != '')
				$name = $_GET['name'];
			else
				$name = $_GET['profile_new_mobile'].'';
			
			$sql = sisplet_query("INSERT INTO srv_theme_profiles_mobile (id, usr_id, skin, name) VALUES ('', '$global_user_id', '".$_GET['profile_new_mobile']."', '".$name."')");
			$profile = mysqli_insert_id($GLOBALS['connect_db']);
			
			$s = sisplet_query("UPDATE srv_anketa SET mobile_skin='".$_GET['profile_new_mobile']."', skin_profile_mobile='".$profile."' WHERE id = '".$this->sid."'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			
			header("Location: index.php?anketa=".$this->sid."&a=theme-editor&profile=".$profile.'&newalert=1&mobile=1');
			die();
		}
		
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		// urejanje CSSa
		if ( $_GET['t'] == 'css' ) {
			
			// CSS za mobilen skin
			if($_GET['mobile'] == '1'){
				$sqlp = sisplet_query("SELECT usr_id, skin FROM srv_theme_profiles_mobile WHERE id = '".$_GET['profile']."'");
				$rowp = mysqli_fetch_array($sqlp);
				
				$skin = $rowp['skin'];
				
				// nastavljena je sistemska tema, moramo jo spremeniti v lastno
				if (strpos($skin, $rowp['usr_id'].'_') === false) {
					
					$skin_name = $rowp['usr_id'].'_'.$skin;
					$css_content = file_get_contents('../../main/survey/skins/'.$skin.'.css');				
					
					$name = $rowp['usr_id'].'_'.$skin;
					
					while ( file_exists('../../main/survey/skins/'.$name.'.css') ) {
						$name = $name.'1';
					}
					
					$f = fopen('../../main/survey/skins/'.$name.'.css', 'w');
					fwrite($f, $css_content);
					fclose($f);
					
					$skin = $name;
					
					sisplet_query("UPDATE srv_theme_profiles_mobile SET skin = '$skin' WHERE id = '".$_GET['profile']."'");
				}
			}
			else{
				$sqlp = sisplet_query("SELECT usr_id, skin FROM srv_theme_profiles WHERE id = '".$_GET['profile']."'");
				$rowp = mysqli_fetch_array($sqlp);
				
				$skin = $rowp['skin'];
				
				// nastavljena je sistemska tema, moramo jo spremeniti v lastno
				if (strpos($skin, $rowp['usr_id'].'_') === false) {
					
					$skin_name = $rowp['usr_id'].'_'.$skin;
					$css_content = file_get_contents('../../main/survey/skins/'.$skin.'.css');				
					
					$name = $rowp['usr_id'].'_'.$skin;
					
					while ( file_exists('../../main/survey/skins/'.$name.'.css') ) {
						$name = $name.'1';
					}
					
					$f = fopen('../../main/survey/skins/'.$name.'.css', 'w');
					fwrite($f, $css_content);
					fclose($f);
					//header("Location: index.php?anketa=".$this->sid."&a=edit_css&skin=".$name."&newalert=1");
					//die();
					
					$skin = $name;
					
					sisplet_query("UPDATE srv_theme_profiles SET skin = '$skin' WHERE id = '".$_GET['profile']."'");
				}
			}
			
			$_GET['skin'] = $skin;
		}
	}
	
	// urejanje teme
	function displayEditing () {
		global $lang;
		global $global_user_id;

		$mobile = (isset($_GET['mobile']) && $_GET['mobile'] == '1') ? '_mobile' : '';
		
		// najprej se pohendla ce gre za nove profile itd...
		$this->handleEditing();
		
		echo '<div class="themes-content">';
		
		$sql = sisplet_query("SELECT name FROM srv_theme_profiles".$mobile." WHERE id = '".$_GET['profile']."'");
		$row = mysqli_fetch_array($sql);
		
		echo '<h2>'.$lang['srv_themes_mod'].': <span class="red">'.$row['name'].'</span></h2>';
		//self::displayTabs();
		
		if ( !isset($_GET['t']) ) {
			
			$ste = new SurveyThemeEditor($this->sid);
			$ste->display();
		
		} elseif ( $_GET['t'] == 'css' ) {
			
			$this->edit_css();
			
		} elseif ( $_GET['t'] == 'upload' ) {
			
			$this->upload_css();
			
		}
		
		echo '</div>';
	}

		
	function displayGroupSelector ($groupId = null) {
		global $lang,$site_url;
		global $global_user_id;

		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		$simple_name = $this->current_skin;
		
		if ($row['skin_profile'] == 0) {
			$skin_name = $this->strip_name($simple_name);
		} else {
			$sqla = sisplet_query("SELECT name FROM srv_theme_profiles WHERE id = '".$row['skin_profile']."'");
			$rowa = mysqli_fetch_array($sqla);
			$skin_name = $rowa['name'];
		}


		echo '<span class="theme_header">'.$lang['srv_current_theme'].': ';
		if($row['skin_profile'] == 0){
			echo '<a href="index.php?anketa=' . $this->sid . '&a=theme-editor&profile_new=' . $simple_name . '"><span class="bold" style="font-size:16px;">' . $skin_name . '</span>' . '</a>';
		}else {
			echo '<a href="index.php?anketa=' . $this->sid . '&a=theme-editor&profile=' . $row['skin_profile'] . '"><span class="bold" style="font-size:16px;">' . $skin_name . '</span>' . '</a>';
		}
		
		echo '</span><br /><br />';
	}
	
	function displayAdvancedSettings ($groupId) {
		global $lang,$site_url;
		global $site_path, $global_user_id;
		global $admin_type;
		
		$row = $this->surveyInfo;
		
		$simple_name = $this->current_skin;
		
		echo '<h2><a href="#" onclick="javascript:$(\'#show_more\').slideToggle(); return false;">'.$lang['srv_show_all_settings'].'</a></h2>';
		
		echo '<fieldset id="show_more" style="display:none; margin-bottom:40px">';
		
		echo '<br /><span class="bold">'.$lang['srv_offline_edit'].':</span>';
		echo '<form name="upload" enctype="multipart/form-data" action="upload.php?anketa=' . $this->sid . '" method="post" />';
		echo '<p><label for="skin">' . $lang['srv_uploadtheme'] . ':</label> ';
		echo '<input type="file" name="fajl" onchange="submit();" onmouseout="survey_upload();" />';
		echo ' (' . $lang['srv_skintmpl1'] . ' <a href="' . $site_url . 'main/survey/skins/'.$row['skin'].'.css" target="_blank">' . $lang['srv_skintmpl'] . '</a>)';
		echo '</p></form>';
		echo '<p style="font-size:90%; color: gray">'.$lang['srv_skin_disclamer'].'</p>';
		
		echo '<br /><span class="bold">'.$lang['srv_upload_pic'].':</span>';
		
		echo '<form name="upload" enctype="multipart/form-data" action="upload.php?anketa=' . $this->sid . '" method="post" />';
		echo '<p><label for="skin">' . $lang['srv_upload_pic2'] . ':</label> ';
		echo '<input type="file" name="fajl" onchange="submit();" onmouseout="survey_upload();" />';
		echo '</p></form>';
		echo '<p style="font-size:90%; color: gray">'.$lang['srv_upload_pic_disclaimer'].'</p><br />';
		
		// prikazemo uploadane slike
		$dir = opendir($site_path . 'main/survey/uploads/');
		$skinsArray = array();
		$skinsArrayPersonal = array();
		while ($file = readdir($dir)) {
			
			if ( $file!='.' && $file!='..') {
				$allowed = false;
				
				if (is_numeric( substr($file, 0, strpos($file, '_')) )) {
					$owner = (int)substr($file, 0, strpos($file, '_')); 
					if ($owner == $global_user_id)
						$allowed = true;
				}
				
				if ($allowed) {
					echo '<a href="'.$site_url.'main/survey/uploads/'.$file.'" target="_blank"><img src="'.$site_url.'main/survey/uploads/'.$file.'" alt="" style="max-width:200px; max-height:200px"></a> ';
				}
			}
		}
		echo '</fieldset>';
	}

    function ajaxSaveChecboxTheme($idAnkete, $value){
        sisplet_query("UPDATE srv_anketa SET skin_checkbox='$value' WHERE id='$idAnkete'");
    }


	function displayGroupThemes ($groupId) {
		global $lang;
		global $site_domain;
		
		// Custom skini
		$this->displayThemes(-1);
		
		// Ostali default skini
		$this->displayThemes($groupId);
		
		// Mobilni skini
		$this->displayThemes(-3);
		
		// Safe skini - samo na domeni safe.si in test.1ka.si (za testiranje)
		if(strpos($site_domain, "safe.si") || $site_domain == "test.1ka.si"){
			$this->displayThemes(-2);
		}
	}
	
	function displayThemes ($groupId) {
		global $lang, $site_url;
		global $global_user_id;
 
		//if (count($this->groups[$groupId]['skins'])>0 || $groupId == -1) {
			
		$rowa = SurveyInfo::getSurveyRow();
		
		if($groupId == -2)
			echo '<h2>'.$lang['srv_safe_themes'].'</h2>';
		elseif ($groupId == -3)
			echo '<h2>'.$lang['srv_mobile_themes'].'</h2>';
		elseif ($groupId != -1)
			echo '<h2>'.$lang['srv_system_themes'].'</h2>';
		else{
			echo '<h2>'.$lang['srv_user_themes'].' ';
			echo '<span class="user_themes_button faicon plus icon-blue pointer" style="margin-bottom:6px;" onClick="toggle_custom_themes(); return false;"> </span>';
			echo '</h2>';
		}

		echo '<div id="div_theme_group" '.($groupId==-1 ? ' class="custom" style="display:none;"' : '').'>';
		
		$sqlg = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$this->sid' ORDER BY vrstni_red ASC LIMIT 1");
		$rowg = mysqli_fetch_array($sqlg);
		$grupa = $rowg['id'];
		
		$profiles = 0;


		// Profili lastnih tem
		if ($groupId == -1) {
					
			// preverimo se, ce trenutno izbran skin pripada drugemu userju (v tem primeru ga vseeno prikazemo)
			$is_current = false;
			if ( isset($this->groups[$groupId]['skins']) && count($this->groups[$groupId]['skins']) > 0 ) {
				foreach ($this->groups[$groupId]['skins'] AS $skinid => $skin) {
					$simple_name = preg_replace("/\.css$/", '', $skin);
					$is_current = ($this->current_skin == $simple_name && $rowa['skin_profile'] == 0) ? true : false;
				}
			}
			$sql = sisplet_query("SELECT id FROM srv_theme_profiles WHERE usr_id = '$global_user_id' ORDER BY name ASC");
			while ($row = mysqli_fetch_array($sql)) {
				$is_current = ($rowa['skin_profile'] == $row['id']) ? true : false;
			}
			if (!$is_current) {
				$append_skin = " OR id = '".$rowa['skin_profile']."' ";
			} else {
				$append_skin = "";
			}
				
			// Custom navadni skini
			$sql = sisplet_query("SELECT id, name, skin FROM srv_theme_profiles WHERE usr_id = '$global_user_id' $append_skin ORDER BY name ASC");
			while ($row = mysqli_fetch_array($sql)) {
				
				$skin = $row['skin'];
				$src = ''.SurveyInfo::getSurveyLink().'&grupa='.$grupa.'&no_preview=1&preview=on&theme_profile='.$row['id'].'';
				$is_current_skin = ($rowa['skin_profile'] == $row['id']) ? true : false;
								
				echo '<div class="custom_theme_holder '.($is_current_skin ? ' active' : '').'">';
				
				// Title
				echo '<span class="custom_theme_title" gid="'.$groupId.'" css="'.urlencode($skin).'" alt="'.$row['name'].'" title="'.$lang['srv_changetheme'].'" onclick="te_change_profile(\''.$row['id'].'\', true); return false;" alt="'.$row['name'].'">'.substr($row['name'], 0, 30).(strlen($row['name']) > 30 ? '...' : '').'</span>';
			
				// Preview theme
				echo ' <a href="#" class="theme_links_preview" src="'.$src.'"><span class="custom_theme_preview"><span class="faicon preview"></span>'.$lang['srv_poglejanketo2'].'</span></a>';

				// Delete theme
				if ($groupId == -1) 
					echo ' <a href="#" onclick="if (confirm(\''.$lang['srv_ask_delete'].'\')) te_delete_profile(\''.$row['id'].'\', false); return false;" class="theme_delete" css="'.urlencode($skin).'"><span class="custom_theme_delete"><span class="faicon delete_circle icon-orange"></span> '.$lang['srv_anketadelete_txt'].'</span></a>';
					
				// Edit theme
				echo ' <a href="index.php?anketa='.$this->sid.'&a=theme-editor&profile='.$row['id'].'"><span class="custom_theme_edit"><span class="faicon palette"></span> '.$lang['edit3'].'</span></a>';	
			
				echo '</div>';
				
				$profiles++;
			}
			
			// Custom mobile skini
			$sql = sisplet_query("SELECT id, name, skin FROM srv_theme_profiles_mobile WHERE usr_id = '$global_user_id' ORDER BY name ASC");
			while ($row = mysqli_fetch_array($sql)) {
				
				$skin = $row['skin'];
				$src = ''.SurveyInfo::getSurveyLink().'&grupa='.$grupa.'&no_preview=1&preview=on&theme_profile='.$row['id'].'&mobile=1';
				$is_current_skin = ($rowa['skin_profile_mobile'] == $row['id']) ? true : false;
								
				echo '<div class="custom_theme_holder '.($is_current_skin ? ' active' : '').'">';
				
				// Title
				echo '<span class="custom_theme_title" gid="'.$groupId.'" css="'.urlencode($skin).'" alt="'.$row['name'].'" title="'.$lang['srv_changetheme'].'" onclick="te_change_profile(\''.$row['id'].'\', true, true); return false;" alt="'.$row['name'].'">'.substr($row['name'], 0, 30).(strlen($row['name']) > 30 ? '...' : '').' <span class="italic">('.$lang['srv_mobile_theme'].')</span></span>';
			
				// Preview theme
				echo ' <a href="#" class="theme_links_preview" src="'.$src.'"><span class="custom_theme_preview"><span class="faicon preview"></span>'.$lang['srv_poglejanketo2'].'</span></a>';

				// Delete theme
				if ($groupId == -1) 
					echo ' <a href="#" onclick="if (confirm(\''.$lang['srv_ask_delete'].'\')) te_delete_profile(\''.$row['id'].'\', true); return false;" class="theme_delete" css="'.urlencode($skin).'"><span class="custom_theme_delete"><span class="faicon delete_circle icon-orange"></span> '.$lang['srv_anketadelete_txt'].'</span></a>';
					
				// Edit theme
				echo ' <a href="index.php?anketa='.$this->sid.'&a=theme-editor&profile='.$row['id'].'&mobile=1"><span class="custom_theme_edit"><span class="faicon palette"></span> '.$lang['edit3'].'</span></a>';	
			
				echo '</div>';
				
				$profiles++;
			}
			
			if(mysqli_num_rows($sql) > 0)
				echo '<br />';
		}
		
		if ( isset($this->groups[$groupId]['skins']) && count($this->groups[$groupId]['skins']) > 0 ) {
			
			foreach ($this->groups[$groupId]['skins'] AS $skinid => $skin) {
				$simple_name = preg_replace("/\.css$/", '', $skin);
				$is_current_skin = ($this->current_skin == $simple_name && $rowa['skin_profile'] == 0) ? true : false;
				$is_current_mobile_skin = ($this->current_mobile_skin == $simple_name && $rowa['skin_profile_mobile'] == 0) ? true : false;
				
				$src = ''.SurveyInfo::getSurveyLink().'&grupa='.$grupa.'&no_preview=1&preview=on&theme='.$skin.'';
				if(substr($skin, 0, 6) == 'Mobile')
					$src .= '&mobile=1';

				echo '<div class="theme_label '.($is_current_skin || $is_current_mobile_skin ? 'span_theme_current' : '').'">';
				
				echo '<div class="theme_label_content">';
				
				// Preview slika
				if ($groupId == -1) echo '<a href="#" class="theme_delete theme" gid="'.$groupId.'" css="'.urlencode($skin).'">'.$lang['srv_anketadelete_txt'].'</a>';
				//echo '<span class="theme_links_rename as_link" theme="'.urlencode($skin).'">Preimenuj</span>';
				if ($groupId == -1)
					echo '<img src="'.$site_url.'public/img/skins_previews/'.($groupId==-1?'usertheme':urlencode($simple_name)).'.png" onclick="te_change_profile_oldskin(\''.$simple_name.'\', true); return false;" gid="'.$groupId.'" css="'.urlencode($skin).'" alt="'.$simple_name.'" title="'.$lang['srv_changetheme'].'">';
				else
					echo '<img src="'.$site_url.'public/img/skins_previews/'.urlencode($simple_name).'.png" class="theme" gid="'.$groupId.'" css="'.urlencode($skin).'" alt="'.$simple_name.'" title="'.$lang['srv_changetheme'].'">';
				
				// Ime teme
				echo '<span class="theme_name">';			
				echo $this->strip_name($simple_name.($simple_name=='1kaBlue' || $simple_name=='MobileBlue' ? ' ('.$lang['default'].')' : ''));
				// Vprasajcki
				if($simple_name == 'Embed' || $simple_name == 'Embed2' || $simple_name == 'Fdv' || $simple_name == 'Uni' || $simple_name == 'Slideshow')
					echo ' '.Help :: display('srv_skins_'.$simple_name);
				if($groupId == -1)
					echo ' (CSS)';					
				echo '</span>';
				
				echo '</div>';
					
				if($is_current_skin)
					echo ' <a href="index.php?anketa='.$this->sid.'&a=theme-editor&profile_new='.$rowa['skin'].'"><span class="faicon palette"></span> '.$lang['srv_te_theme_edit'].'</a>';
				
				if($is_current_mobile_skin)
					echo ' <a href="index.php?anketa='.$this->sid.'&a=theme-editor&profile_new_mobile='.$rowa['mobile_skin'].'"><span class="faicon palette"></span> '.$lang['srv_te_theme_edit'].'</a>';
	
				echo '<a href="#" class="theme_links_preview" src="'.$src.'"><span class="faicon preview"></span> '.$lang['srv_poglejanketo2'].'</a>';
				
				echo '</div>';
			}		
		} 
		elseif ($profiles == 0) {
			echo '<p>'.$lang['srv_te_no_profiles'].'</p>';
			echo '<br />';
		}
						
		echo '</div>';
	}
	
	function changeTheme($css, $gid) {
		global $site_path;
		 
		$_theme = urldecode($_POST['css']);
		
		$dir = $site_path . 'main/survey/skins/';

		if (file_exists($dir.$_theme)) {
			$_theme = preg_replace("/\.css$/", '', $_theme);
			
			// Mobilna anketa
			if($gid == -3){
				$strUpdate = "UPDATE srv_anketa SET mobile_skin = '$_theme', skin_profile_mobile='0' WHERE id=".$this->sid;
				$updated = sisplet_query($strUpdate);
				sisplet_query("COMMIT");
				
				SurveyInfo::getInstance()->resetSurveyData();
				
				$this->current_mobile_skin = $_theme;				
			}		
			else{
				// Nastavimo se mobilni skin glede na osnovnega
				$mobile_skin_update = '';
				if(in_array($_theme, array('1kaBlue', '1kaRed', '1kaOrange', '1kaGreen', '1kaPurple', '1kaBlack'))){
					$mobile_skin = str_replace('1ka', 'Mobile', $_theme);
					$mobile_skin_update = ", mobile_skin='".$mobile_skin."', skin_profile_mobile='0'";
				}
				elseif(in_array($_theme, array('Uni', 'Fdv', 'Cdi'))){
					$mobile_skin = 'Mobile'.$_theme;
					$mobile_skin_update = ", mobile_skin='".$mobile_skin."', skin_profile_mobile='0'";
				}
				
				$strUpdate = "UPDATE srv_anketa SET skin = '$_theme', skin_profile='0' ".$mobile_skin_update." WHERE id=".$this->sid;
				$updated = sisplet_query($strUpdate);
				sisplet_query("COMMIT");
				
				SurveyInfo::getInstance()->resetSurveyData();
				
				$this->current_skin = $_theme;
				
				// Popravimo se mobile skin ce smo ga slucajno preklopili
				if($mobile_skin_update != '')
					$this->current_mobile_skin = $mobile_skin;
			}
		}	
		
		ob_start();
		$this->displayGroupThemes(0);
		$data['group_themes'] = ob_get_clean();
		ob_start();
		$this->displayGroupSelector();
		$data['theme_name'] = ob_get_clean();
		
		echo json_encode($data);
	}

	function changeProgressbar() {
		$progressbar = $_POST['progressbar'];
		$strUpdate = "UPDATE srv_anketa SET progressbar = '$progressbar' WHERE id=".$this->sid;
		$updated = sisplet_query($strUpdate);
		sisplet_query("COMMIT");
		SurveyInfo :: getInstance()->resetSurveyData();
	}
	
	function themeRename($msg = array()) {
		global $lang, $global_user_id;
		echo '<div id="div_theme_fullscreen">';
		echo '<div class="div_theme_fullscreen_content">';
		print_r("<PRE>");
		print_r($msg);
		print_r($_POST);
		print_r("</PRE>");
		$_theme_new = (isset($_POST['theme_new_name']) ? $_POST['theme_new_name'] : $_POST['theme']);
		$_theme_new = urldecode(preg_replace("/\.css$/", '', $_theme_new));
		# Če gre za lastno temo odstranimo $global_user_id+_
		if (is_numeric( substr($_theme_new, 0, strpos($_theme_new, '_')) )) {
			$owner = (int)substr($_theme_new, 0, strpos($_theme_new, '_')); 
			if ($owner == $global_user_id) {
				# odstranimo $global_user_id_ 
				$_theme_new = preg_replace("/^".$global_user_id."_/", '', $_theme_new);
			}
		}
		
		echo '<input id="theme" name="theme" type="hidden" value="'.$_POST['theme'].'">';
		echo '<label>Novo ime:</label><input id="theme_new_name" name="theme_new_name" type="text" value="'.$_theme_new.'">';
		echo '</div>'; 	#inv_FS_content
		echo '<div class="div_theme_fullscreen_btm">';
		echo '<span id="theme_rename_confirm" class="floatRight spaceRight buttonwrapper" ><a class="ovalbutton ovalbutton_orange" href="#" ><span>'.$lang['srv_rename_profile_yes'].'</span></a></span>';
		echo '<span id="theme_rename_cancle" class="floatRight spaceRight buttonwrapper" ><a class="ovalbutton ovalbutton_silver" href="#" ><span>'.$lang['srv_cancel'].'</span></a></span>';
		echo '<div class="clr" />';
		echo '</div>';
		
		echo '</div>';	
	}
	
	function themeRenameConfirm() {
		global $lang, $global_user_id, $site_path;
		$dir = $site_path . 'main/survey/skins/';
		$return = array('msg'=>'', 'error'=>'1', 'theme'=>$_POST['theme'], 'theme_new_name'=>$_POST['theme_new_name']);
		$_theme_old = urldecode($_POST['theme']);

		$_theme_new = preg_replace("/\.css$/", '', urldecode($_POST['theme_new_name']));

		# preverimo ali gre za lastno temo, na začetku dodamo $global_user_id+_
		if (is_numeric( substr($_theme_old, 0, strpos($_theme_old, '_')) )) {
			$owner = (int)substr($_theme_old, 0, strpos($_theme_old, '_')); 
			if ($owner == $global_user_id) {
				# gre za lastno temo, na začetku preventivno odstranimo $global_user_id_ in ga nato dodamo
				$_theme_new = $global_user_id.'_'.preg_replace("/^".$global_user_id."_/", '', $_theme_new);
			}
		}
		
		# novo ime ne sme biti prazno
		if (trim($_theme_new) == '' || $_theme_new == null) {
			$return['error'] = 1;			
			$return['msg'] = 'Ime teme ne sme biti prazno!';
			echo json_encode($return);
			exit;
		}
		$_theme_new = $_theme_new.'.css';		
		# preverimo obstoj stare datoteke
		if (!file_exists($dir.$_theme_old)) {
			$return['error'] = 2;			
			$return['msg'] = 'Izvorna datoteka ne obstaja!';
			echo json_encode($return);
			exit;
		}
		
		#preverimo ali je novo ime enako staremu
		if ($_theme_old == $_theme_new) {
			$return['error'] = 3;			
			$return['msg'] = 'Novo ime je enako staremu!';
			echo json_encode($return);
			exit;
		}
		
		# preverimo in preprečimo obstoj datoteke z novim imenom
		if (file_exists($dir.$_theme_new)) {
			$return['error'] = 4;			
			$return['msg'] = 'Datoteka s tem imenom že obstaja!';
			echo json_encode($return);
			exit;
		}

		#preimenujemo datoteko
		if ((int)rename($dir.$_theme_old,$dir.$_theme_new) == true) {
			#datoteka je bila uspešno preimenovana, popravimo še v bazi, če je potrebno
			$simple_name = preg_replace("/\.css$/", '', $_theme_new);
			$strUpdate = "UPDATE srv_anketa SET skin = '".$simple_name."' WHERE id=".$this->sid;
			$updated = sisplet_query($strUpdate);
			sisplet_query("COMMIT");
			$return = array('msg'=>(int)$updated, 'error'=>'0', 'theme'=>urlencode($_theme_new), 'theme_new_name'=>$_theme_new);
			echo json_encode($return);
			exit;
		} else {
			$return['error'] = 5;			
			$return['msg'] = 'Pri preimenovanju je prišlo do napake!';
			echo json_encode($return);
			exit;
		}
		
		# vse je ok!
		echo json_encode($return);
		exit;
		
 	}
	
	// izbrise temo
	function themeDelete() {
		global $site_path;
		global $global_user_id;
		
		$dir = $site_path . 'main/survey/skins/';
		$skin = urldecode( $_POST['css'] );

		// preverimo, da ima na zacetku user ID, da ne bo brisal kar vsega po vrsti XX_ ter .css na koncu
		if ( substr($skin, 0, strpos($skin, '_')+1 ) == $global_user_id.'_' && substr($skin, -4) == '.css' ) {
			
			unlink($dir.$skin);
		
		}	
		
		$sql = sisplet_query("SELECT skin FROM srv_anketa WHERE id = '{$_POST['anketa']}'");
		$row = mysqli_fetch_array($sql);

		if ($row['skin'] == substr($skin, 0, -4)) {
			sisplet_query("UPDATE srv_anketa SET skin='Default' WHERE id = '{$_POST['anketa']}'");
		}
	}
	
	// iz imena skina odstrani uid stevilko userja in _
	function strip_name ($simple_name) {
		
		// Popravimo se default skine - vstavimo presledek da lepse izgleda
		$skins = array(
			'1kaBlue (Privzeto)', '1kaRed', '1kaOrange', '1kaGreen', '1kaPurple', '1kaBlack', '1kaOffice', '1kaNature',
			'MobileBlue (Privzeto)', 'MobileRed', 'MobileOrange', 'MobileGreen', 'MobilePurple', 'MobileBlack',
			'MobileUni', 'MobileFdv', 'MobileCdi'
		);
		if(in_array($simple_name, $skins)){
			$simple_name = preg_replace('/(?<!\ )[A-Z]/', ' $0', $simple_name);
		}
		
		if ( is_numeric( substr($simple_name, 0, strpos($simple_name, '_')) ) )
			$simple_name = substr($simple_name, strpos($simple_name, '_')+1);
			
		return $simple_name;
	}
	
	function upload_css() {
		global $lang;
		global $site_url;
		
		$row = SurveyInfo::getSurveyRow();
	
		$default = 'Default';
		$skin = ($row['skin'] == '') ? $default : $row['skin'];
		
		echo '<br /><span class="bold">'.$lang['srv_add_theme_upload'].'</span>';
		echo '<form name="upload" enctype="multipart/form-data" action="upload.php?anketa=' . $this->sid . '&profile='.$_GET['profile'].'" method="post">';
		echo '<p><label for="skin">' . $lang['srv_uploadtheme'] . ':</label> ';
		echo '<input type="file" name="fajl" onchange="submit();">';
		echo ' (' . $lang['srv_skintmpl1'] . ' <a href="' . $site_url . 'main/survey/skins/'.$skin.'.css" target="_blank">' . $lang['srv_skintmpl'] . '</a>)';
		echo '</p></form>';
		
		
		echo '<p style="font-size:90%; color: gray">'.$lang['srv_skin_disclamer'].'</p>';
		
		echo '<a href="#" onclick="$(\'#vrednost_edit\').hide().html(\'\'); return false;" style="position:absolute; right:10px; bottom:10px">'.$lang['srv_zapri'].'</a>';
		
	}
	
	function edit_css () {
		global $lang;
		global $site_url;
		global $site_path;
		global $admin_type;
		global $global_user_id;
				
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		$skin = $_GET['skin'];

		echo '<div id="theme-editor">';
		
		echo '<div id="theme-editor-warning">'.$lang['srv_themes_edit_warning'].'</div>';
		
		echo '<form name="editcss" action="ajax.php?anketa='.$this->sid.'&a=save_editcss" method="post" onsubmit="return false;">';
		
		$profile = $_GET['profile'];
		$mobile = (isset($_GET['mobile']) && $_GET['mobile'] == '1') ? true : false;

		$sqlp = sisplet_query("SELECT usr_id FROM srv_theme_profiles".($mobile ? '_mobile' : '')." WHERE id = '$profile'");
		$rowp = mysqli_fetch_array($sqlp);
		
		$skin_name = str_replace($rowp['usr_id'].'_', '', $skin);
		
		//echo '<p>'.$lang['srv_skinname'].': <input type="text" name="skin_name" value="'.$skin_name.'" /></p>';
		echo '<input type="hidden" name="skin_name" value="'.$skin_name.'">';
		echo '<input type="hidden" name="profile" value="'.$_GET['profile'].'">';
		echo '<input type="hidden" name="mobile" value="'.($mobile ? '1' : '0').'">';
		
		echo '<br />';
		
		echo '<span class="bold">'.$lang['srv_themes_edit'].'</span>';
		echo '<p><textarea name="css_content" style="width:100%; height: 400px">';
		
		readfile('../../main/survey/skins/'.$skin.'.css');
		
		echo '</textarea></p>';
				
		echo '<p>';
				
		echo '<p><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings floatLeft spaceRight" href="#" onclick="'; 
			?>$.post('ajax.php?anketa=<?=$this->sid?>&a=save_editcss', $('form[name=editcss]').serialize(), function (data) { 
				//if ( $('input[name=skin_name]').val() != $('input[name=old_name]').val() ) {
				//	window.location.href = 'index.php?anketa=<?=$this->sid?>&a=edit_css&skin='+data;
				//} else {
					var iframe = document.getElementById('theme-preview-iframe');
					iframe.src = iframe.src;
					if ( $('input[name=current_skin]').is(':checked') ) $('input[name=current_skin]').attr('disabled', true); 
				//}
			}); return false;<?php
		echo '"><span>'. $lang['edit1337'] . '</span></a>';
		
		//echo '<a class="ovalbutton ovalbutton_gray spaceRight floatLeft" href="index.php?anketa='.$this->sid.'&a=tema"><span>'.$lang['srv_theme_save_as_new'].'</span></a>';
		
		echo '<a class="ovalbutton ovalbutton_gray floatLeft" href="index.php?anketa='.$this->sid.'&a=tema"><span>'.$lang['back'].'</span></a></p>';
		echo '<div class="clr"></div>';
		echo '</div></p>';
		echo '</form>';
		
		echo '<br /><p><a href="#" onclick="javascript:$(\'#show_more\').slideToggle(); return false;">'.$lang['srv_upload_pic'].'</a></p>';
		
		echo '<fieldset id="show_more" style="'.($_GET['pic']=='open'?'':'display:none;').' margin-bottom:40px">';

		
		echo '<br /><span class="bold">'.$lang['srv_upload_pic'].':</span>';
		/*echo '<form name="upload" enctype="multipart/form-data" action="upload.php?anketa=' . $this->sid . '&logo=1" method="post" />';
		echo '<p><label for="skin">' . $lang['srv_upload_logo'] . ':</label> ';
		echo '<input type="file" name="fajl" onchange="submit();" onmouseout="survey_upload();" />';
		echo '</p></form>';		*/
		
		echo '<form name="upload" enctype="multipart/form-data" action="upload.php?anketa=' . $this->sid . '&skin='.$skin.'&profile='.$_GET['profile'].'" method="post" />';
		echo '<p><label for="skin">' . $lang['srv_upload_pic2'] . ':</label> ';
		echo '<input type="file" name="fajl" onchange="submit();" onmouseout="survey_upload();" />';
		echo '</p></form>';
		echo '<p style="font-size:90%; color: gray">'.$lang['srv_upload_pic_disclaimer'].'</p><br />';
		
		// prikazemo uploadane slike
		$dir = opendir($site_path . 'main/survey/uploads/');
		$skinsArray = array();
		$skinsArrayPersonal = array();
		while ($file = readdir($dir)) {
			
			if ( $file!='.' && $file!='..') {
				$allowed = false;
				
				if (is_numeric( substr($file, 0, strpos($file, '_')) )) {
					$owner = (int)substr($file, 0, strpos($file, '_')); 
					if ($owner == $global_user_id)
						$allowed = true;
				}
				
				if ($allowed) {
					echo '<a href="'.$site_url.'main/survey/uploads/'.$file.'" target="_blank"><img src="'.$site_url.'main/survey/uploads/'.$file.'" alt="" style="max-width:200px; max-height:200px"></a> ';
				}
			}
		}
		echo '</fieldset>';
		
		echo '<div id="success_save"></div>';
		
		echo '</div>';
		
		$sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$this->sid' ORDER BY vrstni_red ASC LIMIT 1");
		$row = mysqli_fetch_array($sql);
		$grupa = $row['id'];
		
		//echo '<div id="theme-preview"><iframe id="theme-preview-iframe" src="'.SurveyInfo::getSurveyLink().'&grupa='.$grupa.'&no_preview=1&preview=on&theme='.$skin.'"></iframe></div>';
		echo '<div id="theme-preview"><iframe id="theme-preview-iframe" src="'.SurveyInfo::getSurveyLink().'&grupa='.$grupa.'&no_preview=1&preview=on&theme_profile='.$_GET['profile'].'&theme-preview=1'.($mobile ? '&mobile=1' : '').'"></iframe><div class="theme-overflow"></div></div>';		

		//echo '</div>';
		
		//echo '<div class="clr"></div>';
		
		//echo '</div>';
		
		SurveyThemeEditor::new_theme_alert($skin_name, true);
	}
	
	function ajax_add_theme () {
		global $lang;
		global $site_url;
		
		$row = SurveyInfo::getSurveyRow();
	
		$default = 'Default';
	
		echo '<h3 style="color:#900">'.$lang['srv_add_theme_css'].'</h3>';
		
		echo '<p>'.$lang['srv_select_base_theme'].': <select name="new_theme" id="new_theme" onchange="$(\'input[name=name]\').val( $(this).val() );">';
		foreach ($this->groups[0]['skins'] AS $key => $val) {
			$skin = str_replace('.css', '', $val);
			echo '<option value="'.$skin.'" '.($skin==$default?'selected':'').'>'.$skin.'</option>';
		}
		echo '</select> <span style="font-size:90%; color: gray">'.$lang['srv_select_base_theme_2'].'</span></p>';
		echo '<p>'.$lang['srv_skinname'].': <input type="text" name="name" value="'.$default.'"></p>';
		
		echo '<p><input type="submit" value="'.$lang['add'].'" onclick="window.location.href=\'index.php?anketa='.$this->sid.'&a=edit_css&new=1&skin=\'+$(\'#new_theme\').val()+\'&name=\'+$(\'input[name=name]\').val(); return false;"></p>';
	
		echo '<br /><h3 style="color:#900">'.$lang['srv_add_theme_upload'].'</h3>';
		echo '<form name="upload" enctype="multipart/form-data" action="upload.php?anketa=' . $this->sid . '" method="post">';
		echo '<p><label for="skin">' . $lang['srv_uploadtheme'] . ':</label> ';
		echo '<input type="file" name="fajl" onchange="submit();">';
		echo ' (' . $lang['srv_skintmpl1'] . ' <a href="' . $site_url . 'main/survey/skins/'.$row['skin'].'.css" target="_blank">' . $lang['srv_skintmpl'] . '</a>)';
		echo '</p></form>';
		
		
		echo '<p style="font-size:90%; color: gray">'.$lang['srv_skin_disclamer'].'</p>';
		
		echo '<a href="#" onclick="$(\'#vrednost_edit\').hide().html(\'\'); return false;" style="position:absolute; right:10px; bottom:10px">'.$lang['srv_zapri'].'</a>';
	}
	
}

?>