<?php
define('ZOOM_DEFAULT_PROFILE', 0);
class SurveyZoom
{
	static private $sid;										# id ankete
	static private $uid = null;									# id userja
	static private $db_table;									# katere tabele uporabljamo
	static private $enabled = false;							# ali imamo vklopljen zoom
	static private $profiles = array();							# profili zooma
	static private $currentProfileId = 0;						# trenutno profil
	static private $extended = true;							# ali imamo razširjen prikaz (privzeto)
	static private $showVariables = true;						# ali prikazujemo okno z variablami
	
	function __construct($sid) {
		global $global_user_id;
		
		self::$sid = $sid;
		self::$uid = $global_user_id;
		
		session_start();
		if (isset($_SESSION['surveyZoom'][self::$sid])) {
			if (isset($_SESSION['surveyZoom'][self::$sid]['extended'])
				&& $_SESSION['surveyZoom'][self::$sid]['extended'] == false) {
				self::$extended = false;
			} else {
				self::$extended = true;
			}

			if (isset($_SESSION['surveyZoom'][self::$sid]['showVariables'])
				&& $_SESSION['surveyZoom'][self::$sid]['showVariables'] == false) {
				self::$showVariables = false;
			} else {
				self::$showVariables = true;
			}
		} else {
			self::$extended = true;
			self::$showVariables = true;
		}
		
		self::getProfiles();
	}
	
	function getProfiles() {
		global $lang;
		
		# inicializiramo datoteko z nastavitvami
		SurveyUserSetting :: getInstance()->Init(self::$sid, self::$uid);			
		
		# polovimo profile iz baze
		$stringSelect = "SELECT * FROM srv_zoom_profiles WHERE sid='" . self::$sid . "' AND uid='" . self::$uid . "'";
		$querySelect = sisplet_query($stringSelect);

		#prvi profil je privezti - brez zooma
		self::$profiles[0] = array('id'=>0, 'name'=>$lang['srv_default_without']);
		
		# če obstajajo profili iz baze jih dodamo
		if (mysqli_num_rows($querySelect)) {
			#najprej dodamo sejo če obstaja
			session_start();
			if (isset($_SESSION['surveyZoom'][self::$sid])) {
				self::$profiles[-1] = $_SESSION['surveyZoom'][self::$sid];
			}
				
			while ( $rowSelect = mysqli_fetch_assoc($querySelect) ) {
				self::$profiles[$rowSelect['id']] = $rowSelect;
				self::$profiles[$rowSelect['id']]['vars'] = unserialize($rowSelect['vars']);
				self::$profiles[$rowSelect['id']]['conditions'] = unserialize($rowSelect['conditions']);
			}
		} else {
			# v bazi ni profilov
			
			#nato dodamo sejo če obstaja
			session_start();
			if (isset($_SESSION['surveyZoom'][self::$sid])) {
				self::$profiles[-1] = $_SESSION['surveyZoom'][self::$sid];
			}
						
		}
		
		
		# preverimo ali ima uporabnik nastavljen privzet profil
		$dzp = SurveyUserSetting :: getInstance()->getSettings('default_zoom_profile');
		if ($dzp == -1 || $dzp > 0 ) {
			self::$currentProfileId = $dzp;
		} else {
			self::$currentProfileId = 0;
			self::SetDefaultProfile(0);
		}
		
		
		
		# če uporabnik nima profilov, in obstajajo demografske spremenljivke naredimo nov DM profil
		if (count(self::$profiles) == 1 ) { # 1profil je vedno (privzeti)
			$all_spr = Cache::cache_all_srv_spremenljivka(self::$sid);
			$variables_to_add = array();
			if (is_countable($all_spr) && count($all_spr) > 0) {
				foreach ($all_spr AS $id => $spr) {
					if (in_array($spr['variable'], array('XSPOL','XIZOBRAZBA','XSTAROST','DMspol','DMizobrazba','DMstarost'))
							|| Demografija::getInstance()->isDemografija($spr['variable'])) {
						$variables_to_add[] = $id;
					}
				}
			}
			
			if (count ($variables_to_add) > 0 ) {
				# dodamo profil in ga izberemo za privzetega

				$_SESSION['surveyZoom'][self::$sid] = array('id'=>-1, 'name'=>$lang['srv_zoom_profile_demografija'],'vars'=>$variables_to_add, 'conditions'=> '', 'if_id'=>0);
				self::$profiles[-1] = array('id'=>$pid, 'sid'=>self::$sid, 'uid'=>self::$uid, 'name'=>$lang['srv_zoom_profile_demografija'], 'vars'=>$variables_to_add,'conditions'=>'');
				if (!isset($_POST['pid']) && !((int)$_POST['pid'] > 0)) {
					#self::SetDefaultProfile(-1);
					#self::$currentProfileId = -1;
				}
			}
		}
		
	}
	
	static function DisplayLink($hideAdvanced = true) {
		global $lang;
		
		$css = (self::$currentProfileId == 0 ? ' gray' : '');
		
		if ($hideAdvanced == false || self::$currentProfileId != 0) {
			echo '<li class="space">&nbsp;</li>';
			echo '<li>';
	        echo '<span class="as_link'.$css.'" id="link_zoom" title="'.$lang['srv_zoom_setting'].'" onclick="showZoomSettings();">'.$lang['srv_zoom_setting'].'</span>';
	        echo '</li>';
	     
		}
	}
	
	function ajax() {
		switch ($_GET['a']) {
			case 'showProfile':
				self::showProfile();
			break;
			case 'changeProfile':
				self::changeProfile($_POST['pid']);
			break;
			case 'saveProfile':
				self::saveProfile();
			break;
			case 'deleteProfile':
				self::deleteProfile($_POST['pid']);
				break;
			case 'renameProfile':
				self::renameProfile();
				break;
			case 'changeZoomCheckbox':
				self::changeZoomCheckbox();
				break;
			case 'removeZoomCheckbox':
				self::removeZoomCheckbox();
				break;
			case 'createNewProfile':
				self::createNewProfile();
				break;
			case 'togleExtended':
				self::togleExtended();
				break;
			case 'toggleShowZoomVariables':
				self::toggleShowZoomVariables();
				break;
			
			case 'doZoomFromInspect':
				self::doZoomFromInspect();
				break;
			
			default:
				print_r("<pre>");
				print_r($_GET);
				print_r($_POST);
				print_r("</pre>");
			break;
		}
	}
	
	static function getCurentProfileId()		{ return self::$currentProfileId; }
	/** Ponastavi id privzetega profila
	 * 
	 */
	static function SetDefaultProfile($pid) {
		self::$currentProfileId = $pid;
		$saved = SurveyUserSetting :: getInstance()->saveSettings('default_zoom_profile',$pid);
	}
	
	function changeProfile($pid) {
		$tmp_profiles = self::$profiles;
		# če profil z pid ne obstaja nastavimo prvega iz baze (ki pa ni demografija)
		if (!isset($tmp_profiles[$pid]) && count($tmp_profiles) > 0) {
			 if (isset($tmp_profiles[-1]) && (int)$pid != -1) {
			 	unset($tmp_profiles[-1]);
			 }
			 if (count($tmp_profiles) > 0){
			 	$pid = key(self::$profiles);
			 } else {
			 	$pid = 0;
			 }
			
		}
		self::SetDefaultProfile($pid);
	}
	
	function showProfile() {
		global $lang;

        echo '<div class="popup_close"><a href="#" onClick="zoomProfileAction(\'cancel\'); return false;">✕</a></div>';

		// Naslov
		echo '<h2>'.$lang['srv_zoom_setting'].'</h2>';
		
        if ($current_pid == null) {
        	$current_pid = self::getCurentProfileId();
        }
        
        $currentFilterProfile = self::$profiles[$current_pid];
        if ( self::$currentProfileId != ZOOM_DEFAULT_PROFILE ) {
	       	echo '<div id="not_default_setting">';
	        echo $lang['srv_not_default_setting'];
	        echo '</div><br class="clr displayNone">';
        }
      
       	echo '<div id="zoom_profiles_left">';
       	echo '<span id="zoom_profiles_holder">';
		# zlistamo vse profile
       	echo '<span id="zoom_profiles" class="select">';
		if (count(self::$profiles)) {
			foreach (self::$profiles as $id => $profile) {
				
				echo '<div class="option' . ($current_pid == $id ? ' active' : '') . '" id="zoom_profile_' . $id . '" value="'.$id.'" '.($current_pid == $id ? '' : ' onclick="zoomChangeProfile(\''.$id.'\')"').'>';

				echo $profile['name'];
				
				if($current_pid == $id){
					# sistemskega ne moremo izbrisati
					if ($current_pid != 0) {
						echo '<a href="#" title="'.$lang['srv_delete_profile'].'" onclick="zoomProfileAction(\'showDelete\'); return false;"><span class="faicon delete_circle icon-orange_link floatRight" style="margin-top:1px;"></span></a>'."\n";
					}
					
					# sistemskega in seje ne moremo preimenovati
					if ($current_pid > 0) {
						echo '<a href="#" title="'.$lang['srv_rename_profile'].'" onclick="zoomProfileAction(\'showRename\'); return false;"><span class="faicon edit icon-as_link floatRight spaceRight"></span></a>'."\n";
					}				
				}
				
				echo '</div>';
			}
		}
		echo '</span>'; # zoom_profilea
		echo '</span>'; # zoom_profiles_holder
		
		echo '</div>'; # zoom_profiles_left

		
		echo '<div id="zoom_profiles_right">'."\n";
		if ($current_pid == 0) {
			echo '<div id="zoom_note">';
			echo $lang['srv_change_default_profile'];
			echo '</div>'; // zoom_profile_note
			echo '<br class="clr" />'."\n";
		}	
		
		echo '<div id="zoom_content">';
		self::DisplayProfileData($current_pid);
		echo '</div>'; // zoom_profile_content

		echo '</div>'; // zoom_profile_right
		
		
		echo '<div id="zoom_button_holder">'."\n";
		if ((int)$current_pid <= 0 ) {
			echo '<span class="floatRight" title="'.$lang['srv_run_as_session_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="zoomProfileAction(\'run_session_profile\'); return false;"><span>'.$lang['srv_run_as_session_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_create_new_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="zoomProfileAction(\'newName\'); return false;"><span>'.$lang['srv_create_new_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_close_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="zoomProfileAction(\'cancel\'); return false;"><span>'.$lang['srv_close_profile'] . '</span></a></div></span>';
		} else  {
			echo '<span class="floatRight" title="'.$lang['srv_save_run_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="zoomProfileAction(\'runProfile\'); return false;"><span>'.$lang['srv_run_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_run_as_session_profile'] . '"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="zoomProfileAction(\'run_session_profile\'); return false;"><span>'.$lang['srv_run_as_session_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_create_new_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="zoomProfileAction(\'newName\'); return false;"><span>'.$lang['srv_create_new_profile'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" title="'.$lang['srv_close_profile'].'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="zoomProfileAction(\'cancel\'); return false;"><span>'.$lang['srv_close_profile'] . '</span></a></div></span>';
			
		}
		echo '</div>'."\n"; // zoom_button_holder
		
		
		// cover Div
        //echo '<div id="zoom_cover_div"></div>'."\n";
		
        // div za kreacijo novega
        echo '<div id="newProfileDiv">'.$lang['srv_missing_profile_name'].': '."\n";
        echo '<input id="newProfileName" name="newProfileName" type="text" value="" size="45"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="zoomProfileAction(\'newCreate\'); return false;"><span>'.$lang['srv_analiza_arhiviraj_save'].'</span></a></span></span>'."\n";            
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="zoomProfileAction(\'newCancel\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '</div>'."\n";
        
        // div za preimenovanje
        echo '<div id="renameProfileDiv">'.$lang['srv_missing_profile_name'].': '."\n";
        echo '<input id="renameProfileName" name="renameProfileName" type="text" value="' . $currentFilterProfile['name'] . '" size="45"  />'."\n";
        echo '<input id="renameProfileId" type="hidden" value="' . $currentFilterProfile['id'] . '"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="zoomProfileAction(\'doRename\'); return false;"><span>'.$lang['srv_rename_profile_yes'].'</span></a></span></span>'."\n";            
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="zoomProfileAction(\'cancelRename\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";
        echo '</div>'."\n";
                
        // div za brisanje
        echo '<div id="deleteProfileDiv">'.$lang['srv_missing_profile_delete_confirm'].': <b>' . $currentFilterProfile['name'] . '</b>?'."\n";
        echo '<input id="deleteProfileId" type="hidden" value="' . $currentFilterProfile['id'] . '"  />'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="zoomProfileAction(\'doDelete\'); return false;"><span>'.$lang['srv_delete_profile_yes'].'</span></a></span></span>'."\n";
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="zoomProfileAction(\'cancelDelete\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'."\n";            
        echo '</div>'."\n";
	}
	
	/** Funkcija prikaze osnovnih informacije profila
	 * 
	 */
	function DisplayProfileData($current_pid=null) {
		global $lang;
		if (isset($_POST['error'])) {
			echo '<span class="red">* '.$lang[$_POST['error']].'</span><br/>';
		}
		# podatki profila
		if ($current_pid == null) {
			$current_pid = self::$currentProfileId;
		}
		$cp = self::$profiles[$current_pid];
		$vars = $cp['vars'];
		
		$all_spr = Cache::cache_all_srv_spremenljivka(self::$sid,true);
		
		echo '<span>'.$lang['srv_zoom_choose'].'</span>';
		
		if (count($all_spr) > 0)
		foreach ($all_spr AS $id => $spremenljivka) {
			if ( in_array($spremenljivka['tip'], array(1,2,3) ) ) {
				echo '<div class="zoom_var">';
				
				echo '<label>';
				echo '<input name="zoom_vars" id="zoom_var_'.$spremenljivka['id'].'" value="'.$spremenljivka['id'].'" type="checkbox"'
				.(is_array($vars) && in_array($spremenljivka['id'],$vars) ? ' checked' : '').'>';
				echo strip_tags($spremenljivka['variable']).' - '.strip_tags($spremenljivka['naslov']).'</label>';
				
				echo '</div>';
			} else if ( in_array($spremenljivka['tip'], array(6,16,17))) {
				echo '<div class="zoom_var">';
				
				# izpišemo glavno spremenljivko
				echo '<label style="margin-left:20px;">';
				echo strip_tags($spremenljivka['variable']).' - '.strip_tags($spremenljivka['naslov']).'</label>';
				
				# izpišemo gride (zamaknjeno)
				$sql = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE spr_id='$id' AND other = 0 ORDER BY vrstni_red");
	            while ($row = mysqli_fetch_assoc($sql)) {
					echo '<label style="margin-left:25px; margin-top:5px;">';
					echo '<input name="zoom_vars" id="zoom_var_'.$spremenljivka['id'].'_'.$row['id'].'" value="'.$spremenljivka['id'].'_'.$row['id'].'" type="checkbox"'
					.(is_array($vars) && in_array($spremenljivka['id'].'_'.$row['id'],$vars) ? ' checked' : '').'>';
					echo strip_tags($row['variable']).' - '.strip_tags($row['naslov']).'</label>';
	        	}
				echo '</div>';
			}
		} 
		
		echo '<br class="clr"/>';
	}
	
	/* Shranimo izbrane variable in resetiramo if na 0, ter pobrišemo morebitni pogoj če obstaja
	 * 
	 */
	static function SaveProfile() {
		global $lang, $global_user_id;
		$pid = isset($_POST['pid']) && (int)$_POST['pid'] > 0 
				? (int)$_POST['pid']	# normaln profil
				: -1;					#seja 
		$profil_data = self::$profiles[$pid];
		
		# preverimo ali je star profil imel kak if če ja ga pobrišemo
		if ((int)$profil_data['if_id'] > 0 ) {
			if ((int)$if_id > 0) {
				$delStr = "DELETE FROM srv_if WHERE id = '$if_id'";
				sisplet_query($delStr);
				$delStr = "DELETE FROM srv_condition WHERE if_id = '$if_id'";
				sisplet_query($delStr);
				sisplet_query("COMMIT");
			}
		}
		
		# ali delamo preko seje ali baze
		if ($pid > 0) {
			# shranimo v bazo
			$vars = serialize(isset($_POST['vars']) ? $_POST['vars'] : '');
			$updateString = "UPDATE srv_zoom_profiles SET vars = '$vars', conditions='', if_id=0 WHERE id='$pid'";
			$updatequery = sisplet_query($updateString);
			sisplet_query("COMMIT");
			
		} else {
			session_start();
			# shranjujenmo v sejo
			$pid=-1;
			#LANG		
			$_SESSION['surveyZoom'][self::$sid] = array('id'=>-1,'vars'=>$_POST['vars'], 'conditions'=> '', 'if_id'=>0);
			if (!isset($_SESSION['surveyZoom'][self::$sid]['name']) || $_SESSION['surveyZoom'][self::$sid]['name'] == '') {
				$_SESSION['surveyZoom'][self::$sid]['name'] = $lang['srv_zoom_profile_session']; 
			}
			
			session_commit();
		}
		self::SetDefaultProfile($pid);
	}
	
	/** shranimo nov profil
	 * 
	 * Enter description here ...
	 */
	function createNewProfile() {
		global $lang,$global_user_id;
		$return = array('newId' => -1, 'error'=>'1','msg'=> 'Profila ni bilo mogoče kreirati!' );
	
		#LANG
		$name = isset($_POST['name']) ? $_POST['name'] : 'Nov profil';
		$vars = serialize(isset($_POST['vars']) ? $_POST['vars'] : '');
		
		$iStr = "INSERT INTO srv_zoom_profiles (sid, uid, name,vars,conditions) VALUES ('".self::$sid."', '".self::$uid."', '$name','$vars','')";
        $sql = sisplet_query($iStr);
		$return['msg'] =$iStr; 
        if (!$sql) {
        	$return['error'] = '1';
        	$return['msg'] = 'Pri kreiranju profila so bile težave!';
        } else {
        	$pid = mysqli_insert_id($GLOBALS['connect_db']);
        	sisplet_query("COMMIT");
        	$return['newId'] = $pid;
        	self::getProfiles();
			self::SetDefaultProfile($pid);
			$return['error'] = '0';
			$return['msg'] = 'Profil je bil uspešno kreiran:'.$pid;
        } 
        echo json_encode($return);
		exit;
        
	}
	
	static function deleteProfile($pid = 0) {
		
		#pobrišemo pogoj če obstaha
		$if_id = self::$profiles[$pid]['if_id'];
		if ((int)$if_id > 0) {
			$stringUpdate = "DELETE FROM srv_if WHERE id = ".$if_id;
			$updated = sisplet_query($stringUpdate);
			sisplet_query("COMMIT");
		}

		if (isset($pid) && $pid == -1) {
			session_start();
			unset($_SESSION['surveyZoom'][self::$sid]);
			session_commit();
		} else  if (isset($pid) && $pid > 0) {
			// Izbrišemo profil in nastavimo privzetega 
			$stringUpdate = "DELETE FROM srv_zoom_profiles WHERE id = ".$pid;
			$updated = sisplet_query($stringUpdate);
			sisplet_query("COMMIT");
		}
		# nastavimo privzet profil
		self::getProfiles();
		self::SetDefaultProfile('0');
	}
	
	static function displayZoomConditions($showDiv = true) {
        global $lang;
        
        $vars = self::$profiles[self::$currentProfileId]['vars'];
        
        $all_spr = Cache::cache_all_srv_spremenljivka(self::$sid);
        
		if (is_countable($vars) && count($vars) > 0) {
			$conditions = self::$profiles[self::$currentProfileId]['conditions'];
			if ($showDiv == true) {
				echo '<div id="div_zoom_condition" '.(self::$showVariables == false ? ' style="display:none"' : '').'>';
			}
			echo '<span class="" style="display:inline-block; width:100%">';
			echo '<b>'.$lang['srv_zoom'].'</b>';
			echo '&nbsp;"'.self::$profiles[self::$currentProfileId]['name'].'"';
			echo '<span class="as_link spaceLeft" onclick="showZoomSettings();">'.$lang['srv_profile_edit'].'</span>';
			echo '<span class="as_link spaceLeft" id="span_zoom_condition_remove" onclick="removeZoomProfile();">'.$lang['srv_profile_remove'].'</span>';
			
			# dodamo še +/- za razpiranje variabel
			echo '<span class="floatRight spaceLeft">';
			echo '<span id="zoomSpritesMinus" class="'.(self::$extended == true ? '' : ' displayNone').'">';
			echo '<span class="pointer faicon icon-blue minus" onClick="toggleAllZoom(\'1\')" >&nbsp;</span>';
			echo '</span>';
			echo '<span id="zoomSpritesPlus" class="'.(self::$extended == true ? ' displayNone' : '').'">';
			echo '<span class="pointer faicon icon-blue plus" onClick="toggleAllZoom(\'0\')">&nbsp;</span>';
			echo '</span>';
			echo '</span>';
			
			echo '</span>';
			echo '<br/>';
			echo '<span class="floatLeft'.( self::$extended == false ? ' displayNone': '').'" >';
			echo '<ul >';
			IF (count($vars) > 0 && is_array($vars)) {
				foreach ($vars AS $_spr) {
					$_spr_tmp = explode('_',$_spr);
					
					$spr = $_spr_tmp[0];
					$vre = $_spr_tmp[1];
					$_conditions = $conditions[$spr]; 
					
					if (isset($vre) && $vre > 0) {
						$sql = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE id='$vre'");
		            	$row = mysqli_fetch_assoc($sql);
					} else {
						$row = Cache::srv_spremenljivka($spr);
					}
					echo '<li id="zoom_var_condition_'.$_spr.'">';
					echo '<div class="zoom_short_text" title="('.strip_tags($row['variable']).') '.strip_tags($row['naslov']).'">';
					echo '(<b>'.strip_tags($row['variable']).'</b>) ';
					echo strip_tags($row['naslov']);
					echo '</div>';
					echo '<div class="zoom_short_text">';
					#echo '<div class="zoom_short_text'.( self::$extended == false ? ' displayNone': '').'">';
					
					if (isset($vre) && $vre > 0) {
						# imamo multigride polovimo grids
						$sql = sisplet_query("SELECT id, naslov, variable FROM srv_grid WHERE spr_id='$spr' AND other = 0 ORDER BY vrstni_red");
		            	while ($row = mysqli_fetch_assoc($sql)) {
		            		echo '<label title="'.strip_tags($row['variable']).' - '.strip_tags($row['naslov']).'">';
		            		echo '<input name="zoom_cond_'.$spr.'[]" id="zoom_cond_'.$spr.'_'.$vre.'_'.$row['id'].'" value="'.$spr.'_'.$vre.'_'.$row['id'].'" type="checkbox" onchange="changeZoomCheckbox(); return false;"';
							if (is_array($_conditions) && isset($_conditions[(int)$vre])) {
		            			if (in_array($row['id'],$_conditions[(int)$vre])) {
		            				echo 'checked="checekd"';
		            			}	
		            		}
		            		echo ' autocomplete="off">';
		            		
							echo strip_tags($row['variable']).' - '.strip_tags($row['naslov']).'</label>';
		            		echo "<br>";
		            	}
						
					} else {
						# imamo navaden polovimo vrednosti
						$sql = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE spr_id='$spr' AND other = 0 ORDER BY vrstni_red");
		            	while ($row = mysqli_fetch_assoc($sql)) {
		            		echo '<label title="'.strip_tags($row['variable']).' - '.strip_tags($row['naslov']).'">';
		            		echo '<input neme="zoom_cond_'.$spr.'[]" id="zoom_cond_'.$spr.'_'.$vre.'_'.$row['id'].'" value="'.$spr.'_'.$vre.'_'.$row['id'].'" type="checkbox"  onchange="changeZoomCheckbox(); return false;"';
		            		if (is_array($_conditions) && isset($_conditions[(int)$vre])) {
		            			if (in_array($row['id'],$_conditions[(int)$vre])) {
		            				echo 'checked="checekd"';
		            			}	
		            		}
		            		echo '>';
							echo strip_tags($row['variable']).' - '.strip_tags($row['naslov']).'</label>';
		            		echo "<br>";
		            	}
					}
					echo '</div>';
					echo '</li>';	
				}	
			}

			echo '</ul>';
			echo '</span>';
			
			if ($showDiv == true) {
				echo '</div>';
			}

		}
	}
	
	function removeZoomCheckbox() {
		#odstranimo if
		$if_id=(int)self::$profiles[self::$currentProfileId]['if_id'];
		if ( $if_id> 0) 
		{
			if(self::$currentProfileId == -1) 
			{
				session_start();
				$_SESSION['surveyZoom'][self::$sid]['if_id'] = 0;
				$_SESSION['surveyZoom'][self::$sid]['conditions'] = null;
			} 
			else 
			{
				$update = "UPDATE srv_zoom_profiles SET if_id = 0, conditions=''  where id='".self::$currentProfileId."'";
				$sql = sisplet_query($update);
				sisplet_query("COMMIT");
			}
			
			if ((int)$if_id > 0) 
			{
				$delStr = "DELETE FROM srv_if WHERE id = '$if_id'";
				sisplet_query($delStr);
				sisplet_query("COMMIT");
			}
		}
	}
	
	function changeZoomCheckbox() 
	{
		if (isset($_POST['vars']) && is_array($_POST['vars']) && count($_POST['vars']) > 0 ) 
		{
			$spr_groups = array();
			foreach ($_POST['vars'] AS $tmpvar) {
				$_var = explode('_',$tmpvar);
				# med posameznimi sub grupami imamo OR pogoje med grupami pa AND
				#zgrupiramo po spremenljivkah in grupah
				if (isset($_var[1]) && $_var[1]!=null) {
					 
					$spr_groups[$_var[0]][$_var[1]][] = $_var[2];
				} else {
					$spr_groups[$_var[0]][0][] = $_var[2];
				}
			}
			self::createZoomCondition($spr_groups);
			# še shranimo checkboxe
			session_start();
			#če seja ne obstaja nastavimo privzet porfil na 0 - default
			if(self::$currentProfileId == -1) {
				session_start();
				$_SESSION['surveyZoom'][self::$sid]['conditions'] = $spr_groups;
			} else {
				$conditions = serialize($spr_groups);
				$update = "UPDATE srv_zoom_profiles SET conditions = '$conditions' where id='".self::$currentProfileId."'";
				$sql = sisplet_query($update);
				sisplet_query("COMMIT");
			}
		} else {
			#odstranimo if
			$if_id=(int)self::$profiles[self::$currentProfileId]['if_id'];
			if ( $if_id> 0) {
				if(self::$currentProfileId == -1) {
					session_start();
					$_SESSION['surveyZoom'][self::$sid]['if_id'] = 0;
				} else {
					$update = "UPDATE srv_zoom_profiles SET if_id = 0, conditions=''  where id='".self::$currentProfileId."'";
					$sql = sisplet_query($update);
					sisplet_query("COMMIT");
				}
				if ((int)$if_id > 0) {
					$delStr = "DELETE FROM srv_if WHERE id = '$if_id'";
					sisplet_query($delStr);
					sisplet_query("COMMIT");
				}
			}
			
		}
		SurveyAnalysis::Init(self::$sid);
		SurveyAnalysis::Display();
		
	}
	
	function createZoomCondition($spr_groups) {
		global $lang,$global_user_id;
		
		#najprej skreiramo nov if če še ne obstaja
		if ((int)self::$profiles[self::$currentProfileId]['if_id'] > 0) {
			$if_id = (int)self::$profiles[self::$currentProfileId]['if_id'];
			# preverimo dejanski obstoj ifa
			if ((int)$if_id > 0) {
				$chks1 = "SELECT id FROM srv_if WHERE id='$if_id'";
				$chkq1 = sisplet_query($chks1);
				if (mysqli_num_rows($chkq1) == 0) {
					$if_id = 0;
				}
			}
		}

		if ( (int)$if_id == 0 || $if_id == null) {
			// if še ne obstaja, skreiramo novga
			$sql = sisplet_query("INSERT INTO srv_if (id) VALUES ('')");
			$if_id = mysqli_insert_id($GLOBALS['connect_db']);
			sisplet_query("COMMIT");
		}
		
		if ((int)$if_id > 0) {
			# updejtamo še zoom profil
			self::$profiles[self::$currentProfileId]['if_id'] = (int)$if_id; 
			if ((int)self::$currentProfileId > 0 ) {
				# v bazi
				$updateString = "UPDATE srv_zoom_profiles SET if_id=".(int)$if_id." WHERE id='".(int)self::$currentProfileId."'";
				$sql = sisplet_query($updateString);
				sisplet_query("COMMIT");
			} else {
				# v seji
				session_start();
				$_SESSION['surveyZoom'][self::$sid]['if_id'] = (int)$if_id;
			}
						

			#poiščemo katere condition_id-je lahko obdržimo, ostale pobrišemo
			if (is_array($spr_groups) &&count($spr_groups ) > 0 ) {
				$spr_vre = array();
				foreach ($spr_groups AS $spr_id => $spr_group) {
					$cids = array();
					foreach($spr_group AS $key => $values) {
						$cids[] = (int)$key;
					}
					# pobrišemo predhodne ife
					if (count($cids) > 0) {
						$spr_vre[] = "(spr_id = '$spr_id' AND vre_id IN (".implode(',',$cids)."))";
					}
				}
				$delStr = "DELETE FROM srv_condition WHERE if_id = '$if_id' AND NOT (".implode(' OR ',$spr_vre).")";
				sisplet_query($delStr);
				sisplet_query("COMMIT");
				
				# dodamo pogoje za posamezne skupine spremenljivk
				$vrstni_red = 1;
				foreach ($spr_groups AS $spr_id => $spr_group) {
					$vrstni_red = self::createSubCondition($if_id,$spr_id,$spr_group,$vrstni_red);
				}
			} else {
				$delStr = "DELETE FROM srv_condition WHERE if_id = '$if_id'";
				sisplet_query($delStr);
				sisplet_query("COMMIT");
			} 
		}
	}
	
	function createSubCondition($if_id,$spr_id,$spr_group,$vrstni_red) {
		foreach($spr_group AS $key => $values) {
			# če imamo vrednosti	
			if ((int)$key == 0) {
				# preverimo ali že obstaja condition id
				$sStr = "SELECT id FROM srv_condition WHERE if_id='$if_id' AND spr_id='$spr_id'";
				$sQry = sisplet_query($sStr);
				if (mysqli_num_rows($sQry) > 0) {
					# cond_id že obstaja uporabimo obstoječega
					$sRow = mysqli_fetch_assoc($sQry);
					$cond_id = $sRow['id'];
				} else {
					#vstavimo nov pogoj
					$istr = "INSERT INTO srv_condition (if_id, spr_id, vrstni_red) VALUES ('$if_id', '$spr_id', '$vrstni_red')"
					. " ON DUPLICATE KEY UPDATE spr_id='$spr_id', vrstni_red = '$vrstni_red'";
					$sql = sisplet_query($istr);

					if (!$sql)  {
						echo '<br>-3 :: '.$istr;
						echo mysqli_error($GLOBALS['connect_db']);
					}
					$cond_id = mysqli_insert_id($GLOBALS['connect_db']);
				}
				
				# pobrišemo vrednosti
				if ($cond_id > 0) {
					$delStr = "DELETE FROM srv_condition_vre WHERE cond_id = '$cond_id'";
					sisplet_query($delStr);
				}				
				foreach ($values AS $value) {
					if ((int)$value > 0 || (int)$cond_id > 0) {
						$istr = "INSERT INTO srv_condition_vre (cond_id, vre_id) VALUES ('$cond_id', '$value')";
						$sql = sisplet_query($istr);

						if (!$sql)  {
							echo '<br>-4 :: '.$istr;
							echo mysqli_error($GLOBALS['connect_db']);
						}
					}	
				}
			} else {
				# če imamo grupe
				# preverimo ali že obstaja condition id
				$sStr = "SELECT id FROM srv_condition WHERE if_id='$if_id' AND spr_id='$spr_id' AND vre_id='$key'";
				$sQry = sisplet_query($sStr);
				if (mysqli_num_rows($sQry) > 0) {
					# cond_id že obstaja uporabimo obstoječega
					$sRow = mysqli_fetch_assoc($sQry);
					$cond_id = $sRow['id'];
				} else {
					$istr = "INSERT INTO srv_condition (if_id, spr_id, vre_id, vrstni_red) VALUES ('$if_id', '$spr_id', '$key', '$vrstni_red')"
					. " ON DUPLICATE KEY UPDATE spr_id='$spr_id', vrstni_red = '$vrstni_red'";
					$sql = sisplet_query($istr);
					if (!$sql)  {
						echo '<br>-3 :: '.$istr;
						echo mysqli_error($GLOBALS['connect_db']);
					}
					$cond_id = mysqli_insert_id($GLOBALS['connect_db']);
				}			
				# pobrišemo gride
				if ($cond_id > 0) {
					$delStr = "DELETE FROM srv_condition_grid WHERE cond_id = '$cond_id'";
					sisplet_query($delStr);
				}				
				
				foreach ($values AS $value) {
					if ((int)$value > 0 || (int)$cond_id > 0) {
						$istr = "INSERT INTO srv_condition_grid (cond_id, grd_id) VALUES ('$cond_id', '".$value."')";
						$sql = sisplet_query($istr);
						if (!$sql)  {
							echo '<br>-4 :: '.$istr;
							echo mysqli_error($GLOBALS['connect_db']);
						}
					}	
				}
								
			}
			sisplet_query("COMMIT");
			$vrstni_red++;
		}
		
		return $vrstni_red;
	}
	
	function togleExtended() {
		session_start();
		
		if (isset($_POST['what']) && $_POST['what'] == 1) {
			$_SESSION['surveyZoom'][self::$sid]['extended'] = false;
		} else {
			$_SESSION['surveyZoom'][self::$sid]['extended'] = true;
		}
	}
	function toggleShowZoomVariables() {
		session_start();
		
		if (isset($_POST['what']) && $_POST['what'] == 1) {
			$_SESSION['surveyZoom'][self::$sid]['showVariables'] = false;
		} else {
			$_SESSION['surveyZoom'][self::$sid]['showVariables'] = true;
		}
	}
	
	function renameProfile() {
		$pid = $_POST['pid'];
		$name = $_POST['name'];
		if (isset($pid) && $pid > 0 && isset($name) && trim($name) != "") {
			// popravimo podatek za variables 
			$stringUpdate = "UPDATE srv_zoom_profiles SET name = '".$name."' WHERE id = '".$pid."'";
			$updated = sisplet_query($stringUpdate);
			sisplet_query("COMMIT");
			return $updated;
		} else {
			return -1;
		}
		
	}
	
	
	
	static function generateAwkCondition() {
		global $global_user_id;

		$zoom_if_id = (int)self::$profiles[self::$currentProfileId]['if_id'];
		
		if ($zoom_if_id > 0 ) {
			SurveyConditionProfiles :: Init(self::$sid, $global_user_id);
			return SurveyConditionProfiles:: generateAwkCondition($zoom_if_id);			
		} else {
			return null;
		}
	}
	
	static function getConditionString() {
	 	global $lang;

#	 	$condition_label = self::$profiles[self::$currentProfileId]['condition_label'];
			ob_start();
			$b = new Branching(self::$sid );
			$if_id = (int)self::$profiles[self::$currentProfileId]['if_id'];
			$b->display_if_label($if_id);			
			#$condition_label = mysqli_escape_string(ob_get_contents());
			$condition_label = ob_get_contents();
			ob_end_clean();
			
	 	if ( $if_id > 0 && $condition_label != '') {
			echo '<div id="conditionProfileNote" class="segmenti">';
			echo '<span class="floatLeft spaceRight">';
			# dodamo še +/- za razpiranje variabel
			echo '<span id="zoomSpritesMinus1" class="'.(self::$showVariables == true ? '' : ' displayNone').'">';
			echo '<span class="pointer faicon icon-blue minus" onClick="toggleShowZoomVariables(\'1\')" >&nbsp;</span>';
			echo '</span>';
			echo '<span id="zoomSpritesPlus1" class="'.(self::$showVariables == true ? ' displayNone' : '').'">';
			echo '<span class="pointer faicon icon-blue plus" onClick="toggleShowZoomVariables(\'0\')">&nbsp;</span>';
			echo '</span>';			
			echo '</span >';
			
			echo '<span class="floatLeft">'.$lang['srv_zoom_filter_note'].'</span>';
			echo '<span class="floatLeft spaceLeft">'.$condition_label.'</span>';
/*			
			// ali imamo napake v ifu
			if ((int)self::$profiles[self :: $currentProfileId]['condition_error'] != 0) {
				echo '<br>';
				echo '<span style="border:1px solid #009D91; background-color: #34D0B6; padding:5px; width:auto;"><img src="img_0/error.png" /> ';
				echo '<span class="red strong">'.$lang['srv_profile_condition_has_error'].'</span>';				
				echo '</span>';				
	 		}
	 		*/
			echo '<span class="as_link spaceLeft" onclick="showZoomSettings();">'.$lang['srv_profile_edit'].'</span>';
			echo '<span class="as_link spaceLeft" onclick="removeZoomCheckbox();">'.$lang['srv_profile_remove'].'</span>';
			echo '</div>';
			echo '<br class="clr" />';
			return true;
        }
	 	
		return false; 			
	}
	
	function doZoomFromInspect() {
		global $lang;
		# polovimo id-je variabel iz inspect profila in nastavimo zoom profil
		$SI = new SurveyInspect(self::$sid);
	 	$variables_to_add = $SI->getInspectVariables();
		
		session_start();
		# shranjujenmo v sejo
		# dodamo profil in ga izberemo za privzetega

				$_SESSION['surveyZoom'][self::$sid] = array('id'=>-1, 'name'=>$lang['srv_zoom_profile_session'],'vars'=>$variables_to_add, 'conditions'=> '', 'if_id'=>0);
				self::$profiles[-1] = array('id'=>$pid, 'sid'=>self::$sid, 'uid'=>self::$uid, 'name'=>$lang['srv_zoom_profile_session'], 'vars'=>$variables_to_add,'conditions'=>'');
				self::SetDefaultProfile(-1);
				self::$currentProfileId = -1;
				session_commit();

		
		# prikažemo segmente
		$showDiv = ((int)$_POST['showDiv'] == 1)?true:false;
		self::displayZoomConditions($showDiv);
	}
}