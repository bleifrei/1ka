<?php
/** Class ki skrbi za osnovne nastavitve ankete - tabela srv_survey
 *  November 2011
 * 
 * 
 * Enter description here ...
 * @author Gorazd_Veselic
 *
 */
class SurveyBaseSetting
{
	private $sid;
	private $return = array('error' => 1, 'msg'=>'','action'=>'0');
	
	function __construct($sid) {
		if ((int)$sid > 0) {
			$this->sid = $sid;
			$this->return['msg'] = 'Napaka!'; # osnovno sporočilo o napaki
		} else {
			echo json_encode(array('error' => 1, 'msg'=>'Invalid Survey ID!'));
			exit();
		}
	}
	
	function ajax() {
		switch ($_GET['a']) {
			case 'radio':
				$this->saveSettingRadio();
			break;

			case 'text':
				$this->saveSettingText();
			break;
			
			default:
				;
			break;
		}
		echo json_encode($this->return);
		exit();
	}
	
	function saveSettingRadio() {
		if (isset($_POST['what']) && isset($_POST['value'])) {
			$what = trim($_POST['what']);
			$value = trim($_POST['value']);
			
			# ali refreshamo timestamp, da vsilimo refresh podatkov, oziroma ponastavimo še kakšne druge stvari
			switch ($what) {
				case 'show_email':
					global $global_user_id;
					$sql = sisplet_query("UPDATE srv_anketa SET edit_uid = '$global_user_id', edit_time=NOW() WHERE id='".$this->sid."'");
				break;
				# če nastavimo individualiziran url nazaj na 1 (=DA) potem obvezno vklopimo prikazovanje uvoda
				case 'individual_invitation':
					if ((int)$value > 0) {
						$sql = sisplet_query("UPDATE srv_anketa SET show_intro = '1' WHERE id='".$this->sid."'");
					}
				default:
					;
				break;
			} 
			
			$updateString = "UPDATE srv_anketa SET ".$what."='".$value."' WHERE id='".$this->sid."'";
			$update = sisplet_query($updateString);
			sisplet_query("COMMIT");
			$this->return['msg'] = 'Updated:'.$update;		
			$this->return['error'] = 0;		
		}
	}
	
	function saveSettingText() {
		if (isset($_POST['what']) && isset($_POST['value'])) {
			$what = trim($_POST['what']);
			$value = trim($_POST['value']);
			$updateString = "UPDATE srv_anketa SET ".$what."='".$value."' WHERE id='".$this->sid."'";
			$update = sisplet_query($updateString);
			sisplet_query("COMMIT");
			$this->return['msg'] = 'Updated:'.$update;		
			$this->return['error'] = 0;		
		}
	}
	
}