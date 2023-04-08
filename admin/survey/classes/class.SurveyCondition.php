<?php
class SurveyCondition
{
	private $_sid;

	private $_conditions = array();
	
	private $_chooseProfileJSAction = null;
	
	function __construct($sid)
	{
		global $lang;
		$this->_sid = $sid;
		# polovimo vse obstoječe ife
		$this->_conditions[0] = array(
			'id' => '0',
			'name' => $lang['srv_inv_condition_new_filter'],
			'number' => '0',
			'tip' => '0',
			'label' => $lang['srv_inv_condition_new_filter'],
			'collapsed' => '0',
			'folder' => '0',
			'enabled' => '1',
			'tab' => '0'
			);
		$str = "SELECT sif.*, ssc.name FROM srv_if AS sif JOIN srv_survey_conditions AS ssc ON (sif.id = ssc.if_id) WHERE ssc.ank_id = '$this->_sid'";
		$qry = sisplet_query($str);
		while ($row = mysqli_fetch_assoc($qry))
		{
			if (isset($row['id']) && (int)$row['id'] > 0)
			{
				$this->_conditions[$row['id']] = $row;
			}
		}
		if (isset($_REQUEST['surveyConditionProfileAction']) && !empty($_REQUEST['surveyConditionProfileAction']))
		{
			$this->_chooseProfileJSAction = $_REQUEST['surveyConditionProfileAction'];
		}
	}
	
	public function ajax()
	{
		switch ($_GET['a']) {
			case 'showCondition' :
				$this->displayConditions((int)$_POST['cid']);
				break;
			case 'newCondition' :
				$this->newCondition();
				break;
			case 'deleteCondition' :
				$this->deleteCondition((int)$_POST['cid']);
				break;
			case 'showRename' :
				$this->showRename();
				break;
			case 'renameCondition' :
				$this->renameCondition();
				break;
			default:
				echo 'ERROR! Missing function for action: '.$_GET['a'].'! (SurveyConditionProfile)';
				break;
		}
	}
	
	public function displayConditions($pid)
	{
		global $lang;
		
		$popUp = new PopUp();
		$popUp->setId('divConditionProfiles');
		$popUp->setHeaderText($lang[''].'Filtriranje s pogoji');
		
		#vsebino shranimo v buffer

		ob_start();
		
		echo '<input type="hidden" id="chooseProfileJSAction" value="'.$this->_chooseProfileJSAction.'" />';
		echo '<div class="condition_profile_holder">';
		
		echo '<div id="condition_profile" class="select">';
		if (count($this->_conditions) > 0)
		{
			foreach ($this->_conditions as $key => $value) {
				echo '<div class="option' . ( $pid == $value['id'] ? ' active' : '') . '" data-cid="' . $value['id'] . '" onclick="showSurveyCondition(\''.$value['id'].'\')">' . $value['name'] .'</div>';
			}
		}
		echo '</div>';
		echo '</div>';
		# tukaj prikazemo vsebino ifa
		echo '<div id="div_cp_preview">';
		echo '<div id="div_cp_preview_content">';
		if ($pid > 0) 
		{
			$b = new Branching($this->_sid);
			$b->condition_editing($pid, -2);
		
		} else {
			echo 'Dodaj nov pogoj:';
			echo '<input id="newSurveyConditionName" placeholder="Ime pogoja" >';
			echo '<a href="#" onclick="newSurveyCondition(); return false;" class="faicon if_add" style="margin-left: 5px;" title="Dodaj nov pogoj"> Dodaj nov pogoj</a>';
		}
		echo '</div>';
		echo '</div>';
		echo '<div id="surveyConditionCover"></div>';
		echo '<div id="renameProfileDiv"></div>';
		$content = ob_get_clean();
		
		#dodamo vsebino
		$popUp->setContent($content);
		
		# dodamo gumb Prekliči
		$popUp->addButton(new PopUpCancelButton());
		
		#dodamo gumb izberi profil
		$confirmAction = 'genericAlertPopup(\'alert_no_action_set\')';
		if (isset($this->_chooseProfileJSAction) && !empty($this->_chooseProfileJSAction))
		{
			$confirmAction = $this->_chooseProfileJSAction;
		}
		$button = new PopUpButton($lang['srv_choose_profile']);
		$button -> setFloat('right')
				-> setButtonColor('orange')
				-> addAction('onClick',$confirmAction.'; return false;');
		$popUp->addButton($button);
		
		if ($pid > 0) {
			# dodamo gumb preimenuj
			$button = new PopUpButton($lang['srv_rename_profile']);
			$button -> setFloat('right')
				-> addAction('onClick','showRenameSurveyCondition(\''.$pid.'\'); return false;');
			$popUp->addButton($button);
			
			# dodamo gumb izbriši
			$button = new PopUpButton($lang['srv_delete_profile']);
			$button -> setFloat('right')
					-> addAction('onClick','deleteSurveyCondition(\''.$pid.'\'); return false;');
			$popUp->addButton($button);
		}
		
		echo $popUp;
		
						
	}
	
	private function newCondition()
	{
		global $lang;
		$result= array('error'=>1, 'if_id'=>0);
		
		$name = isset($_POST['name']) && !empty($_POST['name']) ? $_POST['name'] : $lang['srv_inv_condition_new_filter_name'];
		$result['name'] = $name;
		
		#kreiramo osnovo za if
		$str = "INSERT INTO srv_if (id) VALUES (NULL)";
		$sql = sisplet_query($str);
		$if_id = mysqli_insert_id($GLOBALS['connect_db']);

		if ((int)$if_id > 0)
		{
			$str = "INSERT INTO srv_condition (id, if_id, vrstni_red) VALUES ('', '$if_id', '1')";
			$sql = sisplet_query($str);
			$cond_id = mysqli_insert_id($GLOBALS['connect_db']);				
			
			if ((int)$cond_id > 0)
			{
				$result['cond_id'] = (int)$cond_id;
				$str = "INSERT INTO srv_survey_conditions (ank_id, if_id, name) VALUES ('$this->_sid', '$if_id', '$name')";
				$sql = sisplet_query($str);
				if (!sql)
				{
				}
				else
				{
					$result['error'] = 0;
					$result['if_id'] = (int)$if_id;
				}
			}
		}
			
		if ($result['error'] > 0)
		{
			# pobrišemo zgoraj kreiran if;
			if ((int)$if_id > 0)
			{
				$sql = sisplet_query("DELETE FROM srv_condition where if_id ='$if_id'");
				$sql = sisplet_query("DELETE FROM srv_if where id ='$if_id'");
			}	
		}
		
		
		echo json_encode($result);
		return;
	}
	
	private function deleteCondition($cid = 0)
	{
		global $lang;
		$result= array('error'=>1, 'cid'=>$cid);
		
		if ($cid > 0)
		{
			/* pobrisemo se za ifom*/
			$sql = sisplet_query("SELECT * FROM srv_condition WHERE if_id = '$cid'");
			while ($row = mysqli_fetch_array($sql)) 
			{
				if ((int)$row['id'] > 0) 
				{
					sisplet_query("DELETE FROM srv_condition_vre WHERE cond_id='".$row['id']."'");
				}
			}
			
			if ((int)$cid> 0) 
			{
				sisplet_query("DELETE FROM srv_condition WHERE if_id = '$cid'");
				sisplet_query("DELETE FROM srv_if WHERE id = '$cid'");
				
			}
			/*-- pobrisemo se za ifom*/
			
			$result['error'] = 0;
			$result['cid'] = 0;
		}
		
		# pobrišemo še morebitne seje
		SurveySession::sessionStart($this->_sid);
		SurveySession::remove('invitationAdvancedConditionId');
		
		echo json_encode($result);
		return;
	}
	
	public function setChooseAction($action)
	{
		if (!empty($action))
		{
			$this->_chooseProfileJSAction = $action;
		}
	}
	
	function getConditionName($if_id)
	{
		if (isset($this->_conditions[$if_id]['name']))
		{
			return $this->_conditions[$if_id]['name'];
		}
		global $lang;
		return $lang['srv_inv_condition_no_filter'];
	}
	
	
	function getConditionString($if_id)
	{
		$condition_label = '';
		ob_start();
		$b = new Branching($this->_sid);
		
		if ((int)$if_id > 0)
		{
			$b->display_if_label($if_id);
			#$condition_label = mysqli_escape_string(ob_get_contents());
			$condition_label = ob_get_contents();
			ob_end_clean();
		}
		return $condition_label;
	}
	
	function showRename() 
	{
		global $lang;
		// div za preimenovanje
	
		echo $lang['srv_missing_profile_name'].': ';
		echo '<input id="renameProfileName" name="renameProfileName" type="text" value="' . $this->_conditions[$_POST['cid']]['name'] . '" size="45"  />';
        echo '<input id="renameProfileId" type="hidden" value="' . $_POST['cid'] . '"  />';
        $button = new PopUpButton($lang['srv_rename_profile_yes']);
        echo $button -> setFloat('right')
        		->setButtonColor('orange')
        		-> addAction('onClick','renameSurveyCondition(); return false;');
        
        $button = new PopUpButton($lang['srv_cancel']);
        echo $button -> setFloat('right')
        		-> addAction('onClick','$(\'#divConditionProfiles #renameProfileDiv, #surveyConditionCover\').hide(); return false;');
	}
	
	function renameCondition()
	{
		global $lang;
		
		$result = array('error'=>1);
		
		$name = $_POST['name'];
		$cid = (int)$_POST['cid'];
		
		if ($this->_sid > 0 && isset($name) && !empty($name) && (int)$cid > 0)
		{
			$str = "UPDATE srv_survey_conditions SET name='$name' WHERE ank_id='$this->_sid' AND if_id='$cid'";
			$qry = sisplet_query($str);
			if ($qry)
			{
				$result['error'] = 0;
				$result['errorMsg'] = '';
				$result['if_id'] = (int)$cid;
			}
			else
			{
				$result['errorMsg'] = 'Prišlo je do napake!';
			}
		}
		if (!isset($name) || empty($name))
		{
			$result['errorMsg'] = 'Ime ne sme biti prazno!';
		}
		echo json_encode($result);
		return;			
	}
}