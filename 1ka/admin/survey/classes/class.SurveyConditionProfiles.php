<?php
/** @author: Gorazd Veselič
 * 
 * 	@Desc: za upravljanje z profili statusov za podatke in izvoze
 * 
 */

session_start();


class SurveyConditionProfiles
{
	static private $sid = null;					# id ankete
	static private $uid = null;					# id userja

	static private $currentProfileId = null;	# trenutno profil
	static private $profiles = array();			# seznam vseh profilov od uporabnika
	static private $inited = false;				# ali so profili ze inicializirani
	
	static private $_HEADER = null;				# Header podatki

	static private $awkConditions = array();	# array z pogoji za awk

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
		
		# preberemo podatke vseh profilov ki so na voljo in jih dodamo v array
		$stringSelect = "SELECT * FROM srv_condition_profiles WHERE sid='" . self::$sid . "' AND uid='" . self::$uid . "' ";
		$querySelect = sisplet_query($stringSelect);

		if (mysqli_num_rows($querySelect)) {
			while ( $rowSelect = mysqli_fetch_assoc($querySelect) ) {
				self::$profiles[$rowSelect['id']] = $rowSelect;
			}
		}
		# poiscemo privzet profil
		self::$currentProfileId = SurveyUserSetting :: getInstance()->getSettings('default_condition_profile');

		if (!self::$currentProfileId) {
			self::$currentProfileId = 1;
		}

		# ce imamo nastavljen curent pid in profil z tem pid ne obstaja nastavomo na privzet profil 
		if (self::$currentProfileId != 1) {
			if (!isset(self::$profiles[self::$currentProfileId])) {
				self::$currentProfileId = 1;
				self::setDefaultProfileId(self::$currentProfileId);
			} 
		}

		# ce ne obstajajo podatki za cpid damo error
		if (!isset(self::$profiles[self::$currentProfileId])) {
			die("Profile data is missing!");
			return false;
		} else {
			return true;
		}

	}
	
	public static function getSystemDefaultProfile() {
		return (int)1;
	}
	
	public static function getCurentProfileId() {
		return (int)self::$currentProfileId; 
	}
	
	public static function setCurrentProfileId($id) {
		if (isset(self::$profiles[$id]))
		{
			self::$currentProfileId = $id;
		}
	}
	
	public function getProfileName($pid) {
		return self::$profiles[$pid]['name'];
	}
	
	/* Vrne ID in ime trenutno izbranega profila
	 *
	*/
	function getCurentProfile() {
		return array('id'=>self::$currentProfileId,'name'=>self::$profiles[self::$currentProfileId]['name']);
	}
	
	
	static function setDefaultProfileId($pid = 0) {
		if (!$pid) {
			$pid = 1;
		}

		# profila inspect ne pustimo nastavit za privzetega, ker je tako izbran preko inspect, pustimo pa urejanje
		if( self::$profiles[$pid]['type'] != 'inspect') {
			# če smo izbrali drug profil resetiramo še profil profilov na trenutne nastavitve
			SurveyUserSetting :: getInstance()->saveSettings('default_profileManager_pid', '0');
		
			SurveyUserSetting :: getInstance()->saveSettings('default_condition_profile', $pid);
			self::$currentProfileId = $pid;
		}
		return true; 
	}
	
	static function addSystemProfiles() {
		global $lang;
		
		# skreiramo sistemske profile za vse spremenljivke
		self::$profiles['1'] = array('id'=>'1','uid'=>self::$uid,'name'=>$lang['srv_condition_profile_all'],'system'=>1, 'if_id'=>0);
	}
	
	static function DisplayLink($hideAdvanced = true) {
		global $lang;
		// profili statusov
        $allProfiles = self :: $profiles;
        $css = (self :: $currentProfileId == SCP_DEFAULT_PROFILE ? ' gray' : '');
        
		if ($hideAdvanced == false || self :: $currentProfileId != SCP_DEFAULT_PROFILE) {
			echo '<li class="space">&nbsp;</li>';
			echo '<li>';
			echo '<span class="as_link'.$css.'" id="link_condition_profile" title="' . $lang['srv_condition'] . '" onClick="conditionProfileAction(\'showProfiles\');">' . $lang['srv_condition'] . '</span>'."\n";
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
			case 'show_condition_profile' :
				self :: showProfiles($pid);
			break;
			case 'change_condition_profile' :
#				if (isset($_POST['condition_label']) && $_POST['condition_label'] != '') {
#					self :: setConditionLabel($pid,$_POST['condition_label']);
#				}
				if (isset($_POST['condition_error']) && $_POST['condition_error'] != '') {
					self :: setConditionError($pid,$_POST['condition_error']);
				}
				self :: setDefaultProfileId($pid);
			break;
			case 'condition_remove' :
				self :: conditionRemove();
			break;
			case 'create_condition_profile' :
				self :: createNewProfile();
			break;
			case 'delete_condition_profile' :
				self :: deleteProfile();
			break;
			case 'rename_condition_profile' :
				self :: renameProfile();
			break;
			default:
				echo 'ERROR! Missing function for action: '.$_GET['a'].'! (SurveyConditionProfile)';
			break;
		}
	}
	
	static function showProfiles ($pid = -1) {
		 global $global_user_id, $lang;
        
        if ($pid > 0) {
        	$_currMPID = $pid;
        } else {
        // poiščmo uporabniški privzeti profil
            $_currMPID = self::$currentProfileId;
        }

		// Naslov
        echo '<h2>'.$lang['srv_condition_settings'].'</h2>';
        
        echo '<div class="popup_close"><a href="#" onClick="conditionProfileAction(\'cancle\'); return false;">✕</a></div>';
		
        if ( self :: $currentProfileId != SCP_DEFAULT_PROFILE ) {
	       	echo '<div id="not_default_setting">';
	        echo $lang['srv_not_default_setting'];
	        echo '</div><br class="clr displayNone">';
        }
        
        echo '<div class="condition_profile_holder">';
        
	        echo '<div id="condition_profile" class="select">';
	        foreach (self :: $profiles as $key => $value) {
	            
				echo '        <div class="option' . ( $_currMPID == $value['id'] ? ' active' : '') . '" id="condition_profile_' . $value['id'] . '" value="'.$value['id'].'">';
				
				echo $value['name'];
				
				if($_currMPID == $value['id']){
					if ( self :: $profiles[$_currMPID]['if_id'] != 0) {
						echo '<a href="#" title="'.$lang['srv_delete_profile'].'" onclick="conditionProfileAction(\'deleteAsk\'); return false;"><span class="faicon delete floatRight"></span></a>';
					}
					if ( self :: $profiles[$_currMPID]['if_id'] != 0) {
						echo '<a href="#" title="'.$lang['srv_rename_profile'].'" onclick="conditionProfileAction(\'renameAsk\'); return false;"><span class="faicon edit floatRight spaceRight"></span></a>';
					}
				}
		
				echo '</div>';
			}
	        echo '</div>';
	        echo '<div class="clr"></div>';
        echo '</div>';


        // tukaj prikazemo vsebino ifa
        echo '<div id="div_cp_preview">';
        echo '  <div id="div_cp_preview_content">';

        if (self :: $profiles[$_currMPID]['if_id'] > 0) {
	        $b = new Branching(self::$sid);
			$b->condition_editing(self :: $profiles[$_currMPID]['if_id'], -2);
        } 
        else {
            echo $lang['srv_filter_profiles_note'];
        }

        echo '  </div>';
        echo '</div>';
        

        echo '</div>';

		
        echo '<div id="conditionProfileButtons">';
        // gumbi: preklici, ustvari nov, pozeni trenutni
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="conditionProfileAction(\'run\'); return false;"><span>'.$lang['srv_run_selected_profile'].'</span></a></span></span>';
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="conditionProfileAction(\'newName\'); return false;"><span>'.$lang['srv_create_new_profile'].'</span></a></span></span>';        
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="conditionProfileAction(\'cancle\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>';            
        echo '</div>';

		
        // cover Div
        echo '<div id="conditionProfileCoverDiv"></div>';
        
        // div za shranjevanje novega profila
        echo '<div id="newProfile">'.$lang['srv_missing_profile_name'].': ';
        echo '<input id="newProfileName" name="newProfileName" type="text" size="45"  />';
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="conditionProfileAction(\'newCancle\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>';
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="conditionProfileAction(\'newCreate\'); return false;"><span>'.$lang['srv_save_profile'].'</span></a></span></span>';            
        echo '</div>';

        // div za preimenovanje
        echo '<div id="renameProfileDiv">'.$lang['srv_missing_profile_name'].': ';
        echo '<input id="renameProfileName" name="renameProfileName" type="text" value="' . self :: $profiles[$_currMPID]['name'] . '" size="45"  />';
        echo '<input id="renameProfileId" type="hidden" value="' . self :: $profiles[$_currMPID]['id'] . '"  />';
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="conditionProfileAction(\'renameCancle\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>';
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="conditionProfileAction(\'renameConfirm\'); return false;"><span>'.$lang['srv_rename_profile_yes'].'</span></a></span></span>';            
        echo '</div>';

        // div za brisanje
        echo '<div id="deleteProfileDiv">'.$lang['srv_missing_profile_delete_confirm'].': <b>' . self :: $profiles[$_currMPID]['name'] . '</b>?';
        echo '<input id="deleteProfileId" type="hidden" value="' . self :: $profiles[$_currMPID]['id'] . '"  />';
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="conditionProfileAction(\'deleteCancle\'); return false;"><span>'.$lang['srv_close_profile'].'</span></a></span></span>';
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="conditionProfileAction(\'deleteConfirm\'); return false;"><span>'.$lang['srv_delete_profile_yes'].'</span></a></span></span>';            
        echo '</div>';
	}
	
	
	static function createNewProfile() {
		$name = isset($_POST['name']) ? $_POST['name'] : 'Nov profil';

		$sql = sisplet_query("INSERT INTO srv_if (id) VALUES ('')");
        if (!$sql) echo '-1';
        $if_id = mysqli_insert_id($GLOBALS['connect_db']);

        sisplet_query("INSERT INTO srv_condition (id, if_id, vrstni_red) VALUES ('', '$if_id', '1')");
        
        $sql = sisplet_query("INSERT INTO srv_condition_profiles (id, sid, uid, name, if_id) VALUES ('', '".self::$sid."', '".self::$uid."', '$name', '$if_id')");
        if (!$sql) echo '-2';
        
        $pid = mysqli_insert_id($GLOBALS['connect_db']);
        echo $pid;
	}
	
	static function deleteProfile() {
		$pid = $_POST['pid'];
        if ($pid > 0 ) { 
            $sql = sisplet_query("SELECT * FROM srv_condition_profiles WHERE id = '$pid'");
            $row = mysqli_fetch_array($sql);
            $if = $row['if_id'];
            
            # če je inspect pobrišemo inspect profil
            if ($row['type'] == 'inspect') {
            	#preverimi ali imamo nastavljen pogoj za inspect
            	$if_id = (int)SurveyUserSetting :: getInstance()->getSettings('inspect_if_id');
            	if ($if_id > 0) {
            		#odstranimo zapis za inspect
            	    SurveyUserSetting :: getInstance()->removeSettings('inspect_if_id');
            	}
            }
            		
            /* pobrisemo se za ifom*/
            $sql = sisplet_query("SELECT * FROM srv_condition WHERE if_id = '$if'");
            while ($row = mysqli_fetch_array($sql)) {
            	if ((int)$row[id] > 0) {
                	sisplet_query("DELETE FROM srv_condition_vre WHERE cond_id='$row[id]'");
            	}
            }
            if ((int)$if > 0) {
	            sisplet_query("DELETE FROM srv_condition WHERE if_id = '$if'");
	            sisplet_query("DELETE FROM srv_if WHERE id = '$if'");
            }
            /*-- pobrisemo se za ifom*/
            
	        $deleteString = "DELETE FROM srv_condition_profiles WHERE id = '" . $pid . "' ";
	        $sqlDelete = sisplet_query($deleteString);
            if (!$sqlDelete) echo mysqli_error($GLOBALS['connect_db']);
        }
        $pid = 1;
		SurveyUserSetting :: getInstance()->saveSettings('default_condition_profile', $pid);
		
		self::$currentProfileId = $pid;
 
	}
	
	static function renameProfile() {
        global $lang;
        $sqlInsert = -1;

        $name = isset($_POST['name']) ? $_POST['name'] : 'Nov profil';
    	$pid = $_POST['pid'];
        
        if ( $pid != null && $pid != "" && $pid > 1) {
            if ( $name == null || $name == "" ) {
                $name = $lang['srv_new_profile_ime'];
            }
            
            $updateString = "UPDATE srv_condition_profiles SET name = '" . $name . "' WHERE id = '" . $pid . "'";
            $sqlInsert = sisplet_query($updateString);
        }            
        return $sqlInsert;
	}
	
	static public function setHeader($_header) {
		self::$_HEADER = $_header;
	}
	
	static function getAwkConditionString($if_id = null) {
		
		$awkFilter = '';
		if ($if_id != null)
		{
			$awkFilter = self :: generateAwkCondition($if_id);
		} 
		else 
		if (self :: $currentProfileId > 1 && (int)self::$profiles[self :: $currentProfileId]['condition_error'] == 0) 
		{
			$awkFilter = self :: $currentProfileId;
			$stringSelect = "SELECT if_id from srv_condition_profiles where id = '".self :: $currentProfileId."'";
			$querySelect = sisplet_query($stringSelect);
			list($if_id) = mysqli_fetch_row($querySelect);
			if ($if_id > 0) {
				$awkFilter = self :: generateAwkCondition($if_id);
			}
		}
		return $awkFilter;
	}
	    
    /**
    * @desc zgenerira pogoje za AWK branching
    */
    static function generateAwkCondition ($if) {
        global $lang;

    	$echo = '';
        
        $sql = Cache::srv_condition($if);
       
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        
        $i = 0;
        while ($row = mysqli_fetch_assoc($sql)) {
        	$_spr_id = $row['spr_id'];

            if ($i++ != 0)
                if ($row['conjunction'] == 0)
                    $echo .= '&&';
                else
                    $echo .= '||';


            for ($i=1; $i<=$row['left_bracket']; $i++)
                $echo .= '(';
                
            # imamo spremenljivke (ni kalkulacija ali modercnum)
            if ($_spr_id > 0) {
            	$echo .= self::getAWKSpremenljivka($row);

            // recnum
            } elseif ($_spr_id == -1) {

                $echo .= '('.MOD_REC_FIELD.' % '.$row['modul'].'=='.$row['ostanek'].')';

            // naprava
            } elseif ($_spr_id == -4) {

                foreach (self::$_HEADER['meta']['grids'] as $vkey => $variables) {

                    if ($variables['variables'][0]['variable'] == 'Device') {

                        $sequence = $variables['variables'][0]['sequence'];
                        $echo .= '($'.$sequence.' == ';

                        $device = $lang['srv_para_graph_device'.$row['text']];

                        # ta tekstovne
                        if (IS_WINDOWS) {
                            # za windows
                            $echo .= "\\\"".$device."\\\"";
                        } 
                        else {
                            # za linux
                            $echo .= '"'.$device.'"';
                        }

                        $echo .= ')';
                    }
                }

            // calculations - TODO
            } elseif ($_spr_id == -2) {

				/*$_calc = self :: generateCalculationAWK($row['id']);

                $echo .= '(';
				$echo .= $_calc;
				
				# dodoamo operacijo
				$echo .= self::echoOperator($row['negation'], $row['operator']);
                
				$echo .= $row['text'];

                $echo .= ')';*/
            }

            for ($i=1; $i<=$row['right_bracket']; $i++)
                $echo .= ')';

        }
        
        // failsafe, ce se poklika if, pa se ne nastavi pogoja
        if ($echo == '') {
        	$echo .= ' true ';
        } 
        else {
        	$echo = '('.$echo.')';
        }

        return $echo;
    }

    private static function getAWKSpremenljivka($row) {
		$_spr_id = $row['spr_id'];
		
    	$row2 = self :: select_from_srv_spremenljivka($_spr_id);

    	$_spr_tip = $row2['tip'];

		$echo = '';
    	// radio, checkbox, dropdown in multigrid, in multi check
    	if ( $_spr_tip <= 3 || $_spr_tip == 6 || $_spr_tip == 16 || $_spr_tip == 17) {

    		#radio, drop, checkbox
    		if ($_spr_tip <= 3) {
    			$sql3 = sisplet_query("SELECT * FROM srv_condition_vre c, srv_vrednost v WHERE cond_id='$row[id]' AND c.vre_id=v.id");
    			if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);

    			$j = 0;

    			# pogoje z več opcijami združenimi z OR dodamo v oklepaj zaradi pravilnosti
    			if (mysqli_num_rows($sql3) > 1) {
    				$echo .= '(';
    			}

    			while ($row3 = mysqli_fetch_assoc($sql3)) {
    				if ($j++ != 0) {
    					$echo .= '||';
    				}

    				if ($_spr_tip == 1 || $_spr_tip == 3|| $_spr_tip == 17) { # radio, dropdown
    					$seq = self ::getSequenceForAWKCondition(array('spr'=>$_spr_id));
    				} else if ($_spr_tip == 2) { # checkbox
    					$seq = self ::getSequenceForAWKCondition(array('spr'=>$_spr_id,'vre'=>$row3['vre_id']));
    				}

    				$echo .= '$'.$seq;

    				# dodoamo operacijo
    				$echo .= self::echoOperator($row['negation'], $row['operator']);

    				# dodamo vrednost ali 1 za checkbox
    				if ($_spr_tip == 1 || $_spr_tip == 3 || $_spr_tip == 17) {
    					$echo .= $row3['variable'];
    				} else if ($_spr_tip == 2) {
    					$echo .='1';
    				}
    			}

    			# pogoje z več opcijami združenimi z OR dodamo v oklepaj zaradi pravilnosti
    			if (mysqli_num_rows($sql3) > 1) {
    				$echo .= ')';
    			}


    			// multigrid tip = 6,16
    		} elseif ( $_spr_tip == 6 || $_spr_tip == 16  || $_spr_tip == 17) {
    		
    			$sql3 = sisplet_query("SELECT * FROM srv_condition_grid c WHERE cond_id='$row[id]'");
    			if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);

    			$sqlMgrid = sisplet_query("SELECT id FROM srv_vrednost WHERE id = '$row[vre_id]'");
    			if (!$sqlMgrid) echo mysqli_error($GLOBALS['connect_db']);
    			$rowMgrid = mysqli_fetch_assoc($sqlMgrid);

    			$j = 0;
    			
    			# pogoje z več opcijami združenimi z OR dodamo v oklepaj zaradi pravilnosti
    			if (mysqli_num_rows($sql3) > 1) {
    				$echo .= '(';
    			}
    			# preverimo ali je dvojna tabela
    			$isDouble == false;
    			if ($row2['enota'] == 3 ) {
    				$isDouble=true;
    			}
    			while ($row3 = mysqli_fetch_assoc($sql3)) {
    				if ($j++ != 0) $echo .= '||';

    				if ($isDouble == false || 1) {
	    				$seq = self ::getSequenceForAWKCondition(array('spr'=>$_spr_id, 'vre'=>$rowMgrid['id'], 'grd'=>$row3['grd_id'], 'isDouble' => $isDouble));
	    				$echo .= '$'.$seq;
	
	    				# dodoamo operacijo
	    				$echo .= self::echoOperator($row['negation'], $row['operator']);
	
	    				if ($row2['tip'] == 16) {
	    					$echo .= '1';
	    				} else {
	    					if (!$isDouble) {
	    						$echo .= $row3['grd_id'];
	    					} else {
	    						$str = "select variable from srv_grid where spr_id='$_spr_id' AND id= '$row3[grd_id]'";
	    						$qry = sisplet_query($str);
	    						list($variable) = mysqli_fetch_row($qry);
	    						$echo .= $variable;
	    					}
	    					
	    				}
    				} else {
    					echo 'Error! (SurveyConditionProdiles)';
    					var_dump($_spr_id);	
    					var_dump($rowMgrid['id']);
    					var_dump($row3['grd_id']);
    					
    				}
    			}
    			# pogoje z več opcijami združenimi z OR dodamo v oklepaj zaradi pravilnosti
    			if (mysqli_num_rows($sql3) > 1) {
    				$echo .= ')';
    			}
    		}

    		// textbox, number in compute majo drugacne pogoje in opcije
    	} 
        elseif ($_spr_tip == 4 || $_spr_tip == 7 || $_spr_tip == 18 || $_spr_tip == 19 || $_spr_tip == 20 || $_spr_tip == 21 || $_spr_tip == 22 || $_spr_tip == 25) 
    	{

    		if ($_spr_tip == 7 || $_spr_tip == 18 || $_spr_tip == 19 || $_spr_tip == 20 ) {
    			$grd = $row['grd_id'];
    		} else {
    			$grd = null;
    		}
    		if (isset($row['vre_id'])) {
    			$vre = $row['vre_id'];
    		} else {
    			$vre = null;
    		}
    			
    		$seq = self ::getSequenceForAWKCondition(array('spr'=>$_spr_id, 'vre'=>$vre, 'grd'=> $grd));
    			
    		$echo .= '$'.$seq;

    		# dodoamo operacijo
    		$echo .= self::echoOperator($row['negation'], $row['operator']);

    		# za numerične
    		if ($_spr_tip == 7 || $_spr_tip == 18) 
    		{
    			$echo .= $row['text'];
    		} 
    		else 
    		{
    			# ta tekstovne
    			if (IS_WINDOWS) {
    				# za windows
    				$echo .= "\\\"".$row['text']."\\\"";
    			} else {
    				# za linux
    				$echo .= '"'.$row['text'].'"';
                }
			}               		
		}
		#DATUM
        elseif ($_spr_tip == 8) 
    	{
   			$grd = $row['grd_id'];
    		if (isset($row['vre_id'])) {
    			$vre = $row['vre_id'];
    		} else {
    			$vre = null;
    		}
    			
    		$seq = self ::getSequenceForAWKCondition(array('spr'=>$_spr_id, 'vre'=>$vre, 'grd'=> $grd));
    			
    		$echo .= 'substr($'.$seq.',7,4)substr($'.$seq.',4,2)substr($'.$seq.',1,2)';

    		# dodoamo operacijo
    		$echo .= self::echoOperator($row['negation'], $row['operator']);

    			$echo .= date("Ymd", strtotime($row['text']));;
    			#$echo .= $row['text'];
		}
		return $echo;
    }

    /** Vrne awk matematični operator glede na polje operator in negacijo
     * 
     * @param (0,1) $negation
     * @param (0,1) $operator
     */
	private static function echoOperator($negation = 0, $operator= 0) {
		$echo = '==';
		if ($negation == 0) {
			if ($operator == 0)
				$echo = '==';
			elseif ($operator == 1)
				$echo = '!=';
			elseif ($operator == 2)
				$echo = '<';
			elseif ($operator == 3)
				$echo = '<=';
			elseif ($operator == 4)
				$echo = '>';
			elseif ($operator == 5)
				$echo = '>=';
		} else {
			if ($operator == 0)
				$echo = '!=';
			elseif ($operator == 1)
				$echo = '==';
			elseif ($operator == 2)
				$echo = '>';
			elseif ($operator == 3)
				$echo = '>=';
			elseif ($operator == 4)
				$echo = '<';
			elseif ($operator == 5)
				$echo = '<=';
		}
		return $echo;
   	}	
    
	 private static  $select_from_srv_spremenljivka = array();
	 /**
	 * pobere in zakesira podatke o spremenljivki (ker se to zlo velikokrat bere)
	 * 
	 * @param mixed $spremenljivka
	 */
	 static function select_from_srv_spremenljivka ($spremenljivka) {
		 
		 if (array_key_exists($spremenljivka, self::$select_from_srv_spremenljivka)) {
			 return self::$select_from_srv_spremenljivka[$spremenljivka];
		 }
		 
		 // tole se splaca tam kjer se itak vse spremenljivke preberejo, sam vprasanje, ce se povsod??
		 $sql = sisplet_query("SELECT s.* FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='".self::$sid."'");
		 while ($row = mysqli_fetch_assoc($sql)) {
		 	self :: $select_from_srv_spremenljivka[$row['id']] = $row;
		 }

		 if (array_key_exists($spremenljivka, self :: $select_from_srv_spremenljivka)) {
			 return self :: $select_from_srv_spremenljivka[$spremenljivka];
		 }
		 
		 $sql = sisplet_query("SELECT * FROM srv_spremenljivka WHERE id = '$spremenljivka'");
		 
		 self::$select_from_srv_spremenljivka[$spremenljivka] = mysqli_fetch_assoc($sql);
		 
		 return self::$select_from_srv_spremenljivka[$spremenljivka];
		 
	 }

	 static function getSequenceForAWKCondition ($options = array()) {
	 	$spr_id = (isset($options['spr']) && $options['spr'] != null) ? $options['spr'] : null;
	 	$vre_id  = (isset($options['vre']) && $options['vre'] != null) ? $options['vre'] : null;
	 	$grd_id  = (isset($options['grd']) && $options['grd'] != null) ? $options['grd'] : null;
	 	$isDouble  = (isset($options['isDouble']) && $options['isDouble'] != null) ? $options['isDouble'] : false;
	 	$tip = self::$_HEADER[$spr_id.'_0']['tip'];
	 	if ( $spr_id != null && count(self::$_HEADER[$spr_id.'_0']['grids']) > 0 ) {
	 		switch ($tip) {
	 			case 1 :
	 			case 3 :
	 				$grd = 0;
	 				$var = 0;
	 				break;
	 			case 2:
	 			case 21:
	 			case 17:
	 			case 18:
	 				$grd = 0;
	 				if ($vre_id > 0 && count(self::$_HEADER[$spr_id.'_0']['grids'][$grd]['variables']) > 0) {
		 				foreach (self::$_HEADER[$spr_id.'_0']['grids'][$grd]['variables'] AS $vkey =>$variables) {
		 					if ($variables['vr_id'] == $vre_id && $variables['other'] != 1) {
		 						$var = $vkey;
		 					}
		 				}
	 				} else {
	 					$var = 0;
	 				}
	 				break;
	 			case 7:
	 				$grd = 0;
	 				$var = $grd_id;
	 				break;
	 			case 8:
	 				$grd = 0;
	 				$var = $grd_id;
	 				break;
	 					
	 			case 6:
	 			case 16:
	 			case 19:
	 			case 20:
	 				if ($isDouble == true) {
	 					#polovimo part
	 					$str = "select part from srv_grid where spr_id='$spr_id' AND id= '$grd_id'";
	 					$qry = sisplet_query($str);
	 					list($part) = mysqli_fetch_row($qry);
	 				} else {
	 					$part = 1;
	 				}
	 				
	 				if (count(self::$_HEADER[$spr_id.'_0']['grids']) > 0) {
	 					
	 					foreach (self::$_HEADER[$spr_id.'_0']['grids'] AS $gkey => $grids) {
	 						if (count ($grids['variables']) > 0) {
	 							foreach ($grids['variables'] AS $vkey => $variables) {
	 								if (($tip == 6 && $variables['vr_id'] == $vre_id  && $variables['other'] != 1 && $part == $grids['part'])
										|| (
										($tip == 16 || $tip == 19 || $tip == 20)
										&& $variables['vr_id'] == $vre_id && $grd_id == $variables['gr_id'])  && $variables['other'] != 1) {
											$grd = $gkey;
											$var = $vkey;
										}
	 							}
	 						}
	 					}
	 				}
	 				break;
	 		}

	 		if ($grd !== null && $var !== null) {
	 			return self::$_HEADER[$spr_id.'_0']['grids'][$grd]['variables'][$var]['sequence'];
	 		}
	 	}
	 	return;

	 }

#	 static function setConditionLabel($pid,$condition_label) {
#	 	if ((int)$pid > 0 ) {
#	 		$updateString = "UPDATE srv_condition_profiles SET condition_label = '" . $condition_label . "' WHERE id = '" . $pid . "'";
#	 		$sqlInsert = sisplet_query($updateString);
#	 	} 	
#	 }

	 static function setConditionError($pid,$condition_error) {
	 	if ((int)$pid > 0 ) {
	 		$updateString = "UPDATE srv_condition_profiles SET condition_error = '" . $condition_error . "' WHERE id = '" . $pid . "'";
	 		$sqlInsert = sisplet_query($updateString);
	 	} 	
	 }
	 
	 static function getConditionString($if_id = null) 
	 {
	 	global $lang;

	 	#	 	$condition_label = self::$profiles[self::$currentProfileId]['condition_label'];
		ob_start();
		$b = new Branching(self::$sid );
		
		if ($if_id == null || (int)$if_id == 0)
		{
			$if_id = (int)self::$profiles[self :: $currentProfileId]['if_id'];
		}
		$b->display_if_label($if_id);			
		#$condition_label = mysqli_escape_string(ob_get_contents());
		$condition_label = ob_get_contents();
		ob_end_clean();
	 			
	 	if ( $if_id > 0 && $condition_label != '') {

			echo '<div id="conditionProfileNote">';
			#if (self::$profiles[self :: $currentProfileId]['type'] == 'inspect') {
			#	echo '<span class="floatLeft">'.$lang['srv_profile_data_is_filtred_zoom'].'</span>';
			#} else {
				echo '<span class="floatLeft">'.$lang['srv_profile_data_is_filtred'].'</span>';	
			#}
			echo '<span class="floatLeft spaceLeft clr_if"><b>('.self::$profiles[self :: $currentProfileId]['name'].')</b></span>';
			echo '<span class="floatLeft spaceLeft">'.$condition_label.'</span>';
			// ali imamo napake v ifu
			if ((int)self::$profiles[$if_id]['condition_error'] != 0) {
				echo '<br>';
				echo '<span style="border:1px solid #009D91; background-color: #34D0B6; padding:5px; width:auto;"><img src="img_0/error.png" /> ';
				echo '<span class="red strong">'.$lang['srv_profile_condition_has_error'].'</span>';				
				echo '</span>';				
	 		}
			echo '<span class="as_link spaceLeft" id="link_condition_edit">'.$lang['srv_profile_edit'].'</span>';
			echo '<span class="as_link spaceLeft" id="link_condition_remove">'.$lang['srv_profile_remove'].'</span>';
			#if (self::$profiles[self :: $currentProfileId]['type'] == 'inspect') {
			#	echo '<span class="as_link spaceLeft" onclick="window.location=\'index.php?anketa='.self::$sid.'&a=data&m=quick_edit&quick_view=1\'">'.$lang['srv_zoom_link_whoisthis'].'</span>';
			#}
			echo '</div>';
			echo '<br class="clr" />';
			return true;
        }
	 	
		return false; 	
	 }
	 
    /**
    * @desc zgenerira kalkulacijo za vstavitev v AWK
    */
	 # TODO!!!!
	 # kalkulacije so rešene samo za silo (delujejo samo za tip radio
    function generateCalculationAWK ($condition) {
		$result = '';
        $sql = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id = '$condition' ORDER BY vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        $i = 0;
        while ($row = mysqli_fetch_assoc($sql)) {
        	$_tmp_result = '';
        	$_valid = false;
			$_spr_id = $row['spr_id'];
			
            if ($i++ != 0) {
                if ($row['operator'] == 0)
                    $_tmp_result .= '+';
                elseif ($row['operator'] == 1)
                    $_tmp_result .= '-';
                elseif ($row['operator'] == 2)
                    $_tmp_result .= '*';
                elseif ($row['operator'] == 3)
                    $_tmp_result .= '/';
            }
            for ($j=1; $j<=$row['left_bracket']; $j++) {
                $_tmp_result .= '(';
            }

            // obi�ajne spremenljivke
            if ($_spr_id > 0) {

                $_tmp_result .= self::getAWKSpremenljivka($row); 

            // konstante
            } elseif ($row['spr_id'] == -1) {
                $_tmp_result .= $row['number'];
                $_valid = true;

            }

            for ($j=1; $j<=$row['right_bracket']; $j++) {
                $_tmp_result .= ')';
            }
			
            if ($_valid === true) {
            	$result .= $_tmp_result;
            }
        }

        return $result;
    }

    static function conditionRemove() {
    	# nastavimo privzet profil oziroma brez pogojev.
    	#Če pa je izbran profil bil slučajno inspect, ga v celoti odstranimo, da pobrišemo predhodne nastavitve zaradi gnezdenja
    	if (isset($_POST['pid']) && (int)$_POST['pid'] > 0) {
    		$currentProfileId = (int)$_POST['pid'];
    	} else {
  			$currentProfileId = SurveyUserSetting :: getInstance()->getSettings('default_condition_profile');
    	}
    	
  		echo ($currentProfileId);
  		
  		# preberemo podatke o profilu
		$stringSelect = "SELECT * FROM srv_condition_profiles WHERE sid='" . self::$sid . "' AND uid='" . self::$uid . "' AND id = '".$currentProfileId."'";
		$querySelect = sisplet_query($stringSelect);
		
		# če je if_profil inspect, ga v celoti zbrišemo
		if (mysqli_num_rows($querySelect)) {
			$rowSelect = mysqli_fetch_assoc($querySelect);
			# če je inspect
			if ($rowSelect['type'] == 'inspect') {
				$if_id = $rowSelect['if_id'];
				if ((int)$if_id > 0) {
					$delStr = "DELETE FROM srv_if WHERE if = '".(int)$if_id."'";
					sisplet_query($delStr);
				}
				# zbrišemo še condition_profil
				if ($currentProfileId > 0) {
					$delStr = "DELETE FROM srv_condition_profiles WHERE sid='" . self::$sid . "' AND uid='" . self::$uid . "' AND id = '".$currentProfileId."'";
					sisplet_query($delStr);
				}
				sisplet_query("COMMIT");
			}

			# če smo izbrali drug profil resetiramo še profil profilov na trenutne nastavitve
			SurveyUserSetting :: getInstance()->saveSettings('default_profileManager_pid', '0');
			
			SurveyUserSetting :: getInstance()->removeSettings('default_condition_profile');
			
		}
		# drugače samo nastavimo na privzet profil = 1
  		$currentProfileId = 1;
  		SurveyUserSetting :: getInstance()->saveSettings('default_condition_profile', $currentProfileId);
  		
  		self::$currentProfileId = $currentProfileId;
  		
    }
    

	/** preveri obstoj profila in vrne enak id če obstaja, če ne vrne id privzetega profila
	 * 
	 * @param unknown_type $pid
	 * @return unknown
	 */
	function checkProfileExist($pid)
	{
		if (isset(self::$profiles[$pid]))
		{
			return true;
		}
		return false;
	}
}
?>