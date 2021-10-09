<?php
/**
 * Created on 8.1.2010
 *
 * @author: Mitja Kuščer
 * 
 * Revriten on 9.11.2010
 * @author: Gorazd Veselič
 *
 * @desc: za shranjevanje in nalaganje profilov zank za posamezno anketo
 *
 * Profil 0 je rezerviran za sejo 
 * Profil 1 je rezerviran za privzet profil - Vse vrednosti 
 *  
 * funkcije:
 * 
 * 	Init		- inicializira profil in naloži trenutne vrednosti iz baze ali seje za določenega userja
 * 
 */
session_start();

define('SZP_DEFAULT_PROFILE', 0);

class SurveyZankaProfiles {

	static private $sid = null;					# id ankete
	static private $uid = null;					# id userja

	static private $currentProfileId = null;	# trenutno profil
	static private $profiles = array();			# seznam vseh profilov od uporabnika
	static private $inited = false;				# ali so profili ze inicializirani
	static private $clearZankaProfile = false;	#
		
	static function Init($sid, $uid = null) {
		# nastavimo sid
		self::$sid = $sid;
		
		if (isset($uid) && $uid > 0) {
			self :: $uid = $uid ;
		} else {
			global $global_user_id;
			self :: $uid = $global_user_id; 
		}
		
		SurveyUserSetting :: getInstance()->Init(self::$sid, self::$uid);
		if (self::$inited == false) {
			self::$inited = self :: RefreshData();
		}
	}

	
	static function RefreshData() {
		
		self::$profiles = array();
		
		# dodamo sistemske profile, skreiramo jih "on the fly"
		self :: addSystemProfiles();
		
		# preberemo podatke vseh porfilov ki so na voljo in jih dodamo v array
		$stringSelect = "SELECT * FROM srv_zanka_profiles WHERE sid='" . self::$sid . "' AND uid='" . self::$uid . "' ";
		$querySelect = sisplet_query($stringSelect);

		if (mysqli_num_rows($querySelect)) {
			while ( $rowSelect = mysqli_fetch_assoc($querySelect) ) {
				self::$profiles[$rowSelect['id']] = $rowSelect;
			}
		}
		# poiscemo privzet profil
		self::$currentProfileId = SurveyUserSetting :: getInstance()->getSettings('default_zanka_profile');

		if (self::$currentProfileId === null) {
			self::$currentProfileId = 0;
		}

		# ce imamo nastavljen curent pid in profil z tem pid ne obstaja nastavomo na privzet profil 
		if (self::$currentProfileId > 0) {
			if (!isset(self::$profiles[self::$currentProfileId])) {
				self::$currentProfileId = 0;
				self::setDefaultProfileId(self::$currentProfileId);
			} 
		}

		# ce ne obstajajo podatki za cpid damo error
		if (!isset(self::$profiles[self::$currentProfileId])) {
			self::$currentProfileId = 0;
			
			if (!isset(self::$profiles[self::$currentProfileId])) {
				echo ("Profile data is missing! (class.SurveyZankaProfile");
				return false;
			} else {
				self::setDefaultProfileId(self::$currentProfileId);
				return true;
			}
		} else {
			return true;
		}

	}
	
	static function setDefaultProfileId($pid = null) {
		if ($pid === null) {
			$pid = 0;
		}

		SurveyUserSetting :: getInstance()->saveSettings('default_zanka_profile', $pid);
		self::$currentProfileId = $pid;
		return true; 
	}
	
	static function addSystemProfiles() {
		global $lang;
		
		# dodamo iz seje
		session_start();
		if ( isset($_SESSION['zanka_profile']) ) {
				self::$profiles['-1'] = array( 'id'	 => $_SESSION['zanka_profile'][self::$sid]['id'], 
										 'name'	 => $_SESSION['zanka_profile'][self::$sid]['name'],
										 'system'=> $_SESSION['zanka_profile'][self::$sid]['system'],
										 'variables'=> $_SESSION['zanka_profile'][self::$sid]['variables'],
										 'mnozenje' => $_SESSION['zanka_profile'][self::$sid]['mnozenje']);
		}
		# skreiramo sistemske profile za vse spremenljivke
		self::$profiles['0'] = array('id'=>'0','uid'=>self::$uid,'name'=>$lang['srv_zanka_profile_all'],'system'=>1);
		
	}
	
	static function DisplayLink($hideAdvanced = true) {
		global $lang;
		// profili statusov
        $allProfiles = self :: $profiles;
        $css = (self::$currentProfileId == SZP_DEFAULT_PROFILE ? ' gray' : '');
        if ($hideAdvanced == false || self::$currentProfileId != SZP_DEFAULT_PROFILE) {
        	echo '<li class="space">&nbsp;</li>';
        	echo '<li>';
        	echo '<span class="as_link'.$css.'" id="link_zanka_profile" title="' . $lang['srv_zanke'] . '" onClick="zankaProfileAction(\'showProfiles\');">' . $lang['srv_zanke'] . '</span>'."\n";
        	echo '</li>';
        	
        }
	}

	static function getProfileData($pid) {
		// preverimo ali smo v razredu že lovili podatke za ta profil, potem jih preberemo čene jih osvežimo
		if ( isset( self::$profiles[$pid] ) ) {
			return self::$profiles[$pid];
		} else {
			self::$inited = self :: RefreshData();
			return self::$profiles[$pid];
		}
	}
	
	static function ajax() {
		$pid = $_POST['pid'];
		switch ($_GET['a']) {
			case 'show_profile' :
				self :: showProfiles($pid);
			break;
			case 'change_profile' :
				self :: setDefaultProfileId($pid);
			break;
			case 'createProfile' :
				self :: createProfile();
			break;
			case 'delete_profile' :
				self :: deleteProfile();
			break;
			case 'rename_profile' :
				self :: renameProfile();
			break;
			case 'run' :
				self :: runProfile();
			break;
			default:
				echo 'ERROR! Missing function for action: '.$_GET['a'].'! (SurveyZankaProfile)';
			break;
		}
	}
	static function showProfiles ($pid = null) {
		global $lang;
        
        echo '<div class="popup_close"><a href="#" onClick="zankaProfileAction(\'cancle\'); return false;">✕</a></div>';

		echo '<h2>'.$lang['srv_zanka_settings'].'</h2>';
		
		if ($pid === null) {
	        # poiščmo uporabniški privzeti profil
            $pid = self::$currentProfileId;
		} 

		# variable profila        
        $szp_pv = explode(',',self::$profiles[$pid]['variables']);
        
        # ali imamo množenje
        $mnozenje = self::$profiles[$pid]['mnozenje'];
        
        #vse možne variable
		$sdf = SurveyDataFile::get_instance();
		$sdf->init(self::$sid);
		$szp_av =$sdf->getSurveyVariables(array(1,2,3));
		// variable razdelimo na dve grupi, na vse možne in posebej izbrane
		$selected_variables = array();
		if (self::$clearZankaProfile == false) {
			foreach ($szp_pv as $key => $variabla) {
				if (isset($szp_av[$variabla])) {
					$selected_variables[$variabla] = $variabla;
					unset($szp_av[$variabla]);
				}			
			}
		}		
		#echo '<div id="currentZankaProfile">'.$lang['srv_analiza_selected_profile'].': <b>' . self::$profiles[$pid]['name'] . '</b></div >'.NEW_LINE;
        
		if ( self::$currentProfileId != SZP_DEFAULT_PROFILE ) {
	       	echo '<div id="not_default_setting">';
	        echo $lang['srv_not_default_setting'];
	        echo '</div><br class="clr displayNone">';
        }
		
        
		echo '<div class="zanka_profile_holder">'.NEW_LINE;
		echo '	<div id="zanka_profile" class="select">'.NEW_LINE;
		if (count(self::$profiles) > 0 ){
			foreach (self::$profiles as $key => $value) {
				
				if ($value['id'] != null) {
				
					echo '<div class="option' . ($pid == $value['id'] ? ' active' : '') . '" id="zanka_profile_' . $value['id'] . '" value="'.$value['id'].'">';

					echo $value['name'];

					if($value['id'] == $pid){
						if ( $pid != 0) {
							# sistemskega ne pustimo izbrisat
							echo '   <a href="#" title="'.$lang['srv_delete_profile'].'" onclick="zankaProfileAction(\'deleteAsk\'); return false;"><span class="faicon delete_circle icon-orange_link floatRight" style="margin-top:1px;"></span></a>'.NEW_LINE;
						}
						if ( $pid > 0) {  
							# seje in sistemskega ne pustimo preimenovat
							echo '   <a href="#" title="'.$lang['srv_rename_profile'].'" onclick="zankaProfileAction(\'renameAsk\'); return false;"><span class="faicon edit icon-as_link floatRight spaceRight"></span></a>'.NEW_LINE;
						}		
					}
					
					echo '</div>'.NEW_LINE;				
				}
			}
		}
		echo '	</div>'.NEW_LINE;

		echo '<div class="clr"></div>'.NEW_LINE;

		echo '</div>'.NEW_LINE;
		
		// izrišemo dva stolpca z možnostjo premikanja enih in drugih variabelS		
		echo '<div id="fs_list">'.NEW_LINE;
		echo '<div class="left link_no_decoration">'.NEW_LINE;
		echo $lang['srv_select'].NEW_LINE; 
		echo '<a href="#" onclick="return $.dds.selectAll(\'fs_list_3\');">'.$lang['srv_all'].'</a>'.NEW_LINE; 
		echo '<a href="#" onclick="return $.dds.selectNone(\'fs_list_3\');">'.$lang['srv_none'].'</a> '.NEW_LINE;
		echo '<a href="#" onclick="return $.dds.selectInvert(\'fs_list_3\');">'.$lang['srv_invert'].'</a>'.NEW_LINE;
		echo '</div>'.NEW_LINE;
		echo '<div class="left link_no_decoration" style="width:200px; text-align:center">'.NEW_LINE;
		echo '<a href="#" onclick="zankaProfileAction(\'clearDdsZanka\'); return false;">'.$lang['srv_clear'].'</a>'.NEW_LINE;
		echo '<input type="checkbox" name="mnozenje" id="mnozenje" value="1"'.($mnozenje==1?' checked="checked"':'').' autocomplete="off"/><label for="mnozenje">'.$lang['srv_analiza_krat'].'</label>'.NEW_LINE;
		echo '</div>'.NEW_LINE;

		echo '<div class="right link_no_decoration">'.NEW_LINE;
		echo $lang['srv_select'].NEW_LINE; 
    		echo '<a href="#" onclick="return $.dds.selectAll(\'fs_list_4\');">'.$lang['srv_all'].'</a>'.NEW_LINE; 
    		echo '<a href="#" onclick="return $.dds.selectNone(\'fs_list_4\');">'.$lang['srv_none'].'</a> '.NEW_LINE;
    		echo '<a href="#" onclick="return $.dds.selectInvert(\'fs_list_4\');">'.$lang['srv_invert'].'</a>'.NEW_LINE;
		echo '</div>'.NEW_LINE;
		echo '<br class="clr" />'.NEW_LINE;
		echo '<br />'.NEW_LINE;
		echo '<div class="left fs_container">'.NEW_LINE;
		echo '<ul id="fs_list_3" class="left">'.NEW_LINE;
		$sdf = SurveyDataFile::get_instance();
		$sdf->init($sid);
		if (count($szp_av) > 0) {
			foreach($szp_av as $key => $variabla) {
				$_name = $sdf->getVariableName($key);
				echo '<li id="variabla_'.$key.'">'.self::limitString($_name).'</li>'.NEW_LINE;
			}
		}
		echo '</ul>'.NEW_LINE;
		echo '</div>'.NEW_LINE;
		echo '<div class="right fs_container">'.NEW_LINE;
		echo '<ul id="fs_list_4" class="left">'.NEW_LINE;
		if (count($selected_variables) > 0 ) {
			foreach($selected_variables as $key => $variabla) {
				$_name = $sdf->getVariableName($key);
				echo '<li id="variabla_'.$key.'">'.self::limitString($_name).'</li>'.NEW_LINE;
			}
		}
		echo '</ul>'.NEW_LINE;
		echo '</div>'.NEW_LINE;
		echo '<script type="text/javascript">'.NEW_LINE;
        echo '$(document).ready(function() {';
        echo '$(function(){'.NEW_LINE;
        echo '	mychange = function ( $list ){'.NEW_LINE;
        echo '	$("#"+$list.attr("id")+"_serialised").html( $.dds.serialize( $list.attr("id")) );'.NEW_LINE;
        echo '}'.NEW_LINE;
        echo '$(".fs_container ul").drag_drop_selectable({'.NEW_LINE;
        echo 'onListChange:mychange'.NEW_LINE;
        echo '});'.NEW_LINE;
        echo '});'.NEW_LINE;
        echo '});'.NEW_LINE;
		echo '</script>'.NEW_LINE;

		echo '<br class="clr" />'.NEW_LINE;

		echo '</div>'.NEW_LINE;

		echo '<div id="missingProfilebuttons">'.NEW_LINE;
		if ((int)$pid < 0 ) {// pri seji in sistemskem ne pustimo shranjevanja
			echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="zankaProfileAction(\'runSession\'); return false;"><span>'.$lang['srv_run_as_session_profile'].'</span></a></span></span>'.NEW_LINE;
		} else {
			echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange"   href="#" onclick="zankaProfileAction(\'run\'); return false;"><span>'.$lang['srv_save_and_run_profile'].'</span></a></span></span>'.NEW_LINE;
			echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="zankaProfileAction(\'runSession\'); return false;"><span>'.$lang['srv_run_as_session_profile'].'</span></a></span></span>'.NEW_LINE;
		}
						
		echo '<span class="floatRight spaceLeft"><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="zankaProfileAction(\'newName\'); return false;"><span>'.$lang['srv_save_new_profile'].'</span></a></span></span>'.NEW_LINE;
		echo '<span class="floatRight spaceLeft"><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="zankaProfileAction(\'cancle\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'.NEW_LINE;
		
		echo '</div>'.NEW_LINE;
		echo '<div id="zankaProfileCoverDiv"></div>'.NEW_LINE;

		// div za shranjevanje novega profila
		echo '<div id="newProfileDiv">'.$lang['srv_missing_profile_name'].': '.NEW_LINE;
		echo '<input id="newProfileName" name="newProfileName" type="text" size="45"  />'.NEW_LINE;
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="zankaProfileAction(\'newCancle\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'.NEW_LINE;
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="zankaProfileAction(\'newCreate\'); return false;"><span>'.$lang['srv_save_profile'].'</span></a></span></span>'.NEW_LINE;			
		echo '</div>'.NEW_LINE;

		// div za preimenovanje
		echo '<div id="renameProfileDiv">'.$lang['srv_missing_profile_name'].': '.NEW_LINE;
		echo '<input id="renameProfileName" name="renameProfileName" type="text" value="' . self::$profiles[$pid]['name'] . '" size="45"  />'.NEW_LINE;
		echo '<input id="renameProfileId" type="hidden" value="' . $czp . '"  />'.NEW_LINE;
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="zankaProfileAction(\'renameConfirm\'); return false;"><span>'.$lang['srv_rename_profile_yes'].'</span></a></span></span>'.NEW_LINE;			
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="zankaProfileAction(\'renameCancle\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'.NEW_LINE;
		
		echo '</div>'.NEW_LINE;

		// div za brisanje
		echo '<div id="deleteProfileDiv">'.$lang['srv_missing_profile_delete_confirm'].': <b>' . self::$profiles[$pid]['name'] . '</b>?'.NEW_LINE;
		echo '<input id="deleteProfileId" type="hidden" value="' . $czp . '"  />'.NEW_LINE;
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="zankaProfileAction(\'deleteConfirm\'); return false;"><span>'.$lang['srv_delete_profile_yes'].'</span></a></span></span>'.NEW_LINE;
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="zankaProfileAction(\'deleteCancle\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>'.NEW_LINE;
		echo '</div>'.NEW_LINE;
	}

	
	static function createProfile() {
		global $lang;
		$profileId = -1;
		$numrows = -1;
		$profileName = $_POST['profileName'];

		// počistimo podatke
		$data = str_replace(array('variabla_', ' '), array('',''), $_POST['data']);
		$mnozenje = $_POST['mnozenje'];

		// ime profila preverima ali obstaja
		if (!$profileName || $profileName == null || $profileName == "")
			$profileName = $lang['srv_new_profile_ime'];

		do { // preverimo ali ime že obstaja
			$selectSqlProfile = "SELECT * FROM srv_zanka_profiles WHERE name = '" . $profileName . "' AND sid = '" . self::$sid . "' AND uid = '" . self::$uid . "'";
			$sqlProfileSetting = sisplet_query($selectSqlProfile);
			$numrows = mysqli_num_rows($sqlProfileSetting);
			if ($numrows != 0) { // ime že obstaja zgeneriramo novo
				srand(time());
				$profileName .= rand(0, 9);
			}
		} while ($numrows != 0);
		
		// poiščemo zadnji id
		$selectProfileId = "SELECT max(id) as last_id FROM srv_zanka_profiles WHERE sid = '" . self::$sid . "' AND uid = '" . self::$uid . "'";
		$sqlProfileId = sisplet_query($selectProfileId);
		$rowProfileId = mysqli_fetch_assoc($sqlProfileId);
		$profileId = $rowProfileId['last_id']+1;

		$stringInsert = "INSERT INTO srv_zanka_profiles (id, sid, uid, name, system, variables, mnozenje) " .
			"VALUES ('".$profileId."', '" . self::$sid . "', '" . self::$uid . "', '" . $profileName . "', '0', '".$data."', '".$mnozenje."')";

		sisplet_query($stringInsert);
		$insertId = mysqli_insert_id($GLOBALS['connect_db']);
		if ($insertId > 0) {
			$profileId = $insertId;
		}
		self::setDefaultProfileId($profileId);				
		return $profileId;	
	}
	
	static function deleteProfile() {
		$pid = $_POST['pid'];
        if ($pid > 0 ) { 
            $deleteString = "DELETE FROM srv_zanka_profiles WHERE id = '" . $pid . "' ";
            $sqlDelete = sisplet_query($deleteString);
            if (!$sqlDelete) echo mysqli_error($GLOBALS['connect_db']);
        } else if ($pid == '-1') {
        	# zbrišemo sejo
        	unset($_SESSION['zanka_profile'][self::$sid]);
        }
        
        $pid = 0;
		SurveyUserSetting :: getInstance()->saveSettings('default_zanka_profile', $pid);
		self::$currentProfileId = $pid;
		self::RefreshData();
 	}
 	
	static function renameProfile() {
        global $lang;
        $sqlInsert = -1;
        $name = $_POST['name'];
    	$pid = $_POST['pid'];
        
        if ( $pid != null && $pid != "" && $pid > 0) {
            if ( $name == null || $name == "" ) {
                $name = $lang['srv_new_profile_ime'];
            }
            
            $updateString = "UPDATE srv_zanka_profiles SET name = '" . $name . "' WHERE id = '" . $pid . "'";
            $sqlInsert = sisplet_query($updateString);
        }            
        return $sqlInsert;
	}
	
	static function runProfile() {
		global $lang;
		
		$data = str_replace(array('variabla_', ' '), array('',''), $_POST['data']);
		$mnozenje = isset($_POST['mnozenje']) && $_POST['mnozenje'] == '1' ? '1' : '0';
		$pid = $_POST['pid'];
		if ($_POST['run'] != 'runSession') {
			# shranimo podatke v normalni profil in ga zaženemo
			
			if ($pid > 0) {
				
				$updateString = "UPDATE srv_zanka_profiles SET variables = '" . $data . "', mnozenje='$mnozenje' WHERE id = '" . $pid . "' AND sid = '". self::$sid."' AND uid='". self::$uid."'";
				
				$sqlupdate = sisplet_query($updateString) or die(mysqli_error($GLOBALS['connect_db']));
				SurveyUserSetting :: getInstance()->saveSettings('default_zanka_profile', $pid);
			} else if ($pid == 0) {
				SurveyUserSetting :: getInstance()->saveSettings('default_zanka_profile', $pid);
			}		
		} else {
			# shranimo podatke v sejo
			$_SESSION['zanka_profile'][self::$sid] = array('id' => '-1',
										 'name'	 => $lang['srv_zanka_profile_session'],
										 'system'=> '1',
										 'variables'=> $data,
										 'mnozenje' => $mnozenje 
				);
			SurveyUserSetting :: getInstance()->saveSettings('default_zanka_profile', '-1');
		}
		
		self::RefreshData();

	}
	
	/** Vrne array z awk stringi filtrov za posamezno variablo v zanki
	 * 
	 */
	static function getFiltersForLoops() {
		$result = array();
		$_spr_for_loops = explode(',',self::$profiles[self::$currentProfileId]['variables']);

		$i = 0;
		if (count($_spr_for_loops) > 0) {
			foreach ($_spr_for_loops AS $_spr) {
				$sdf = SurveyDataFile::get_instance();
				$sdf->init(self::$sid);
				$_spr_data = $sdf->getHeaderVariable($_spr);
				if ($_spr_data['tip'] == 1 || $_spr_data['tip'] == 3) {
					# radio oz, dropdown
					
					# zloopamo skozi opcije
					if (count($_spr_data['options']) > 0 ) {
						foreach ($_spr_data['options'] AS $o_key => $option) {
							$_results[$i][] = array('filter' => '$'.$_spr_data['grids']['0']['variables']['0']['sequence'].' == '.$o_key, 
													'text' => '('.$_spr_data['grids']['0']['variables']['0']['variable'].') ' .$_spr_data['grids']['0']['variables']['0']['naslov'] . ' = '.$option);
						}
					}

				} else if ($_spr_data['tip'] === '2') {
					# checkbox
					#zloopamo po vrednostih in dodamo filtre za 1-checked 2-not checked
					foreach ($_spr_data['grids'] as $g_key => $grid) {
						foreach ($grid['variables'] as $v_key_group => $variable) {
								$_results[$i][] = array('filter' => '$'.$variable['sequence'].' == 0', 
													'text' => '('.$variable['variable'].') ' .$variable['naslov'] . ' = 0');
								$_results[$i][] = array('filter' => '$'.$variable['sequence'].' == 1', 
													'text' => '('.$variable['variable'].') ' .$variable['naslov'] . ' = 1');
						}
					}
				}
				$i++;
				
			}
			
		} 

		# če mamo množenje, lahko izvedemo samo nad dvema spremenljivkama
		if (self::$profiles[self::$currentProfileId]['mnozenje'] == 1) {
			if (count($_results[0]) > 0) {
				foreach ($_results[0] AS $_result0) {
					if (count($_results[1]) > 0) {
						foreach ($_results[1] AS $_result1) {
							$result[] = array('filter' => $_result0['filter'].' && '.$_result1['filter'],
								'text' => $_result0['text'].' && '.$_result1['text']
							);

						}
					} else {
						# imamo samo 1 spremenljivko
						$result[] = array('filter' => $_result0['filter'],
							'text' => $_result0['text']
						);
					}
				}
			}
		} else {
			
			$result = $_results[0];
		}
		return $result;	
	}
	
	/** če v krostabih izberemo tretjo variablo (ctrl+click)
	 * povozimo obstoječe zanke, in tretjo variablo dodamo v loop kot začasen profil
	 * Enter description here ...
	 * @param $variable
	 */
	
	function setLoopsForCrostabs($variable) {
		# poiščemo spremenljivko 
		print_r("v:".$variable);
	}
	static function limitString($input, $limit = 100) {
	    // Return early if the string is already shorter than the limit
	    if(strlen($input) < $limit) {return $input;}
	
	    $regex = "/(.{1,$limit})\b/";
	    preg_match($regex, $input, $matches);
	    return $matches[1].'...';
	}
}

?>