<?php
/** 
 * 
 * 
 * Class za posiljanje sporocil uporabnikom (v mojih anketah)
 * 
 *
 */

 
class Notifications {
		
	function __construct() {
		global $admin_type, $global_user_id;

	}
	
	
	public function display($tab=0){
		global $admin_type, $global_user_id, $lang;
		
		// Prikazemo poslana sporocila
		if((isset($_GET['t']) && $_GET['t']=='sent') || $tab == 1){
			
			// Izpis vseh poslanih sporocil
			if($admin_type == 0){
				echo '<div class="sent_list">';
				$this->displaySentMessages();
				echo '</div>';
				
				// obrazec za posiljanje sporocil
				$this->sendMessageForm();
			}
			else
				echo $lang['srv_notifications_admin_alert'];			
		}
		// Prikazemo prejeta sporocila
		else{
			echo '<div class="recieved_list">';
			$this->displayRecievedMessages();
			echo '</div>';
			
			echo '<div id="message" style="display:none;"></div>';
		}
		
		echo '<div class="clr"></div>';
	}
	
	private function displayRecievedMessages($active=0){
		global $admin_type, $global_user_id, $lang;
		
		echo '<span class="bold">'.$lang['srv_notifications_recieved'].':</span>';
		
		// Napolnimo array prejetih sporocil
		$recievedMessages = array();
		
		$sql = sisplet_query("SELECT n.id AS id, n.recipient AS recipient, n.viewed AS viewed, m.id AS message_id, m.date AS date, m.title AS title, m.text AS text
								FROM srv_notifications n, srv_notifications_messages m 
								WHERE n.recipient='".$global_user_id."' AND n.message_id=m.id
								ORDER BY m.date DESC");
		while($row = mysqli_fetch_array($sql)){
			$recievedMessages[$row['id']] = $row;
		}	
				
		
		echo '<ul>';
		
		if(count($recievedMessages) > 0){
			foreach($recievedMessages as $message){
				echo '<a href="#" onclick="viewMessage(\''.$message['id'].'\'); return false;">';
				echo '<li class="'.($message['viewed'] == 0 ? ' unread':'').' '.($active > 0 && $message['id'] == $active ? ' active':'').'">';
				
				echo '<span class="bold">'.$message['title'].' <span class="italic">('.$message['date'].')</span></span>';
				
				$text = strip_tags((strlen($message['text']) > 70) ? substr($message['text'], 0, 70).'...' : $message['text']);
				echo '<br />'.$text;
				
				echo '</li>';
				echo '</a>';
			}
		}
		
		echo '</ul>';
	}
	
	private function displaySentMessages(){
		global $admin_type, $global_user_id, $lang;
		
		echo '<span class="bold">'.$lang['srv_notifications_sent'].':</span>';
		
		// Napolnimo array poslanih sporocil
		$sentMessages = array();
		
		$sql = sisplet_query("SELECT * FROM srv_notifications_messages WHERE author='".$global_user_id."' ORDER BY date DESC");
		while($row = mysqli_fetch_array($sql)){
			$sentMessages[$row['id']] = $row;
		}
		
		
		echo '<ul>';
		
		if(count($sentMessages) > 0){
			foreach($sentMessages as $message_id => $message){
				//echo '<li '.($message['viewed'] == 0 ? ' class="unread"':'').'>';
				echo '<li>';
				
				echo '<span class="bold">'.$message['title'].' <span class="italic">('.$message['date'].')</span></span>';
				
				$text = strip_tags((strlen($message['text']) > 70) ? substr($message['text'], 0, 70).'...' : $message['text']);
				echo '<br />'.$text;
				
				// Gumb da se sporocilo razresi vsem  (kot da so ga pregledali)
				$sqlN = sisplet_query("SELECT message_id FROM srv_notifications WHERE message_id='".$message_id."' AND viewed='0'");
				$count = mysqli_num_rows($sqlN);
				if($count > 0){
					echo '<br />';
					echo '<a href="#" onclick="resolveMessages(\''.$message_id.'\'); return false;"><span class="link">'.$lang['srv_notifications_sent_resolve'].' ('.$count.')'.'</span></a>';
				}
				
				echo '</li>';
			}	
		}
		
		echo '</ul>';
	}
	
	// Obrazec za posiljanje sporocila
	private function sendMessageForm($note=''){
		global $admin_type, $global_user_id, $lang;
		
		echo '<div class="send_form">';
		echo '<span class="clr bold">'.$lang['srv_notifications_send_reciever'].': </span><input type="text" name="recipient" id="recipient">';
		
		// Checkboxa za posiljenje vsem uporabnikoom (slo in ang)
		echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="recipient_all_slo" id="recipient_all_slo" onClick="recipient_all_disable_email();"> <label for="recipient_all_slo"><span class="clr bold">'.$lang['srv_notifications_send_all_slo'].'</span></label></div>';
		echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="recipient_all_ang" id="recipient_all_ang" onClick="recipient_all_disable_email();"> <label for="recipient_all_ang"><span class="clr bold">'.$lang['srv_notifications_send_all_ang'].'</span></label></div><br />';

        // Naslov sporocila
		echo '<span class="clr bold">'.$lang['srv_notifications_send_title'].': </span><input type="text" name="title"><br /><br />';

        // Besedilo sporocila (editor)
		echo '<span class="clr bold">'.$lang['srv_notifications_send_text'].': </span><textarea id="notification" name="notification" autocomplete="off"></textarea><br />';
		
		// Avtomatsko prikaži obvestilo po prijavi
		echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="force_show" id="force_show"> <label for="force_show"><span class="clr bold">'.$lang['srv_notifications_force_show'].'</span></label></div><br />';
		
		echo '<span class="buttonwrapper floatLeft spaceRight"><a class="ovalbutton ovalbutton_orange" href="#" onclick="sendNotification(); return false;">';
		echo '<span>'.$lang['srv_notifications_send'].'</span>';
		echo '</a></span>';
		
		if($note != ''){
			echo '<br /><br />';
			echo '<span class="bold red">'.$note.'</span>';
		}
		
		echo '</div>';

        // Inicializiramo editor
        echo '<script type="text/javascript">create_editor_notification(\'notification\');</script>';
	}	
	
	
	// Vrnemo stevilo sporocil
	public function countMessages($type='unread'){
		global $global_user_id;
		
		$count = 0;

		switch($type){
			case 'recieved':
				$sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_notifications WHERE recipient='$global_user_id' AND viewed='1'");
				break;
				
			case 'sent':
				$sql = sisplet_query("SELECT COUNT(n.*) AS count FROM srv_notifications n, srv_notifications_messages m WHERE m.author='$global_user_id' AND m.id=n.message_id");
				break;

			case 'unread':
			default:
				$sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_notifications WHERE recipient='$global_user_id' AND viewed='0'");
				break;
		}

		
		if(mysqli_num_rows($sql) > 0){
			$row = mysqli_fetch_array($sql);
			$count = $row['count'];
		}

		return $count;
	}
	
	// Preveri ce avtomatsko prikaze sporocilo po prijavi
	public function checkForceShow(){
		global $global_user_id;
		
		$sql = sisplet_query("SELECT m.force_show FROM srv_notifications n, srv_notifications_messages m WHERE n.message_id=m.id AND n.recipient='$global_user_id' AND n.viewed='0' AND m.force_show='1'");
		
		if(mysqli_num_rows($sql) > 0)
			return true;
		else
			return false;		
	}
	
	
	public function ajax() {
		global $global_user_id;
		
		switch ($_GET['a']) {
			case 'sendNotification':
				$this->ajax_sendNotification();
				break;
			case 'viewMessage':
				$this->ajax_viewMessage();
				break;
			case 'viewUnreadMessages':
				$this->ajax_viewUnreadMessages();
				break;
			case 'viewGDPRMessage':
				$this->ajax_viewGDPRMessage();
				break;
			case 'saveGDPRAgree':
				$this->ajax_saveGDPRAgree();
				break;	
			case 'resolveMessages':
				$this->ajax_resolveMessages();
				break;
			default:
				break;				
		}
	}
	
	public function ajax_sendNotification(){
		global $lang, $global_user_id;
		
		if(isset($_POST['recipient']))
			$recipient = $_POST['recipient'];
		if(isset($_POST['recipient_all_slo']))
			$recipient_all_slo = $_POST['recipient_all_slo'];
		if(isset($_POST['recipient_all_ang']))
			$recipient_all_ang = $_POST['recipient_all_ang'];
		if(isset($_POST['title']))
			$title = $_POST['title'];
		if(isset($_POST['notification']))
			$notification = $_POST['notification'];
		if(isset($_POST['force_show']))
			$force_show = $_POST['force_show'];
		
				
		// Ce posiljamo vsem uporabnikom nimamo maila
		if($recipient_all_slo == 1 || $recipient_all_ang == 1){
			
			// Dodamo novo sporocilo v bazo
			$sql = sisplet_query("INSERT INTO srv_notifications_messages (author, date, title, text, force_show) VALUES ('".$global_user_id."', NOW(), '".$title."', '".$notification."', '".$force_show."')");
			$message_id = mysqli_insert_id($GLOBALS['connect_db']);
			
			$note = '';
					
			// Loop cez vse uporabnike v bazi - vsakemu dodamo sporocilo
			if($recipient_all_slo == 1 && $recipient_all_ang == 1)
				$sqlU = sisplet_query("SELECT id, name, surname, email FROM users WHERE status!='0' AND status!='5' AND status!='6'");
			elseif($recipient_all_ang == 1)
				$sqlU = sisplet_query("SELECT id, name, surname, email FROM users WHERE status!='0' AND status!='5' AND status!='6' AND lang='2'");
			else
				$sqlU = sisplet_query("SELECT id, name, surname, email FROM users WHERE status!='0' AND status!='5' AND status!='6' AND lang='1'");
			while($rowU = mysqli_fetch_array($sqlU)){
	
				$sql = sisplet_query("INSERT INTO srv_notifications (message_id, recipient, viewed) VALUES ('".$message_id."', '".$rowU['id']."', '0')");
				if(!$sql) 
					$note .= mysqli_error($GLOBALS['connect_db']).'<br />';	
			}		
		}
		// Posiljamo na specificen mail
		else{		
			// Dobimo user id glede na vnesen mail prejemnika
			$sqlU = sisplet_query("SELECT id, name, surname, email FROM users WHERE email='$recipient'");
			if(mysqli_num_rows($sqlU) > 0){
				$rowU = mysqli_fetch_array($sqlU);
				
				// Dodamo novo sporocilo v bazo
				$sql = sisplet_query("INSERT INTO srv_notifications_messages (author, date, title, text, force_show) VALUES ('".$global_user_id."', NOW(), '".$title."', '".$notification."', '".$force_show."')");
				$message_id = mysqli_insert_id($GLOBALS['connect_db']);
				
				// Dodamo novo sporocilo v bazo
				$sql = sisplet_query("INSERT INTO srv_notifications (message_id, recipient, viewed) VALUES ('".$message_id."', '".$rowU['id']."', '0')");
				if(!$sql) 
					$note = mysqli_error($GLOBALS['connect_db']);
				else
					$note = 'Sporočilo je bilo uspešno poslano uporabniku '.$recipient.' ('.$rowU['name'].' '.$rowU['surname'].').';		
			}
			else{
				$note = 'Vnešeni email ('.$recipient.') ne pripada nobenemu uporabniku!';
			}
		}
		
		// Refresh vseh poslanih sporocil
		echo '<div class="sent_list">';
		$this->displaySentMessages();
		echo '</div>';
		
		// Refresh obrazca za posiljanje sporocil
		$this->sendMessageForm($note);
		
		echo '<div class="clr"></div>';
	}
	
	public function ajax_resolveMessages(){
		
		if(isset($_POST['id']))
			$message_id = $_POST['id'];
			
		// Oznacimo sporocila kot pregledana
		$sql = sisplet_query("UPDATE srv_notifications SET viewed='1' WHERE message_id='$message_id'");
		
		$this->displaySentMessages();
	}
	
	public function ajax_viewMessage(){
		global $lang, $global_user_id;
		
		if(isset($_POST['id']))
			$id = $_POST['id'];
		
		
		// Oznacimo sporocilo kot pregledano
		$sql = sisplet_query("UPDATE srv_notifications SET viewed='1' WHERE id='$id'");
		
		// Dobimo sporocilo
		$sql = sisplet_query("SELECT n.id AS id, m.title AS title, m.text AS text, m.date AS date 
								FROM srv_notifications n, srv_notifications_messages m 
								WHERE n.id='$id' AND m.id=n.message_id");
		$row = mysqli_fetch_array($sql);
		
		// Refresh vseh prejetih sporocil
		echo '<div class="recieved_list">';
		$this->displayRecievedMessages($active=$id);
		echo '</div>';
		
		// Prikaz izbranega sporocila
		echo '<div id="message">';

		echo '<span class="bold">'.$row['title'].' <span class="italic">('.$row['date'].')</span></span>';

        // Stara sporocila so brez editorja
        $text = (strtotime($row['date']) < strtotime('2021-08-26')) ? nl2br($row['text']) : $row['text'];
		echo '<p>'.$text.'</p>';

		echo '</div>';
		
		echo '<div class="clr"></div>';
	}
	
	public function ajax_viewUnreadMessages(){
		global $lang, $global_user_id;	
		
		echo '<h2>'.$lang['srv_notifications_unread'].'</h2>';
		
		echo '<ul>';
		
		// Loop cez vsa neprebrana sporocila
		$sql = sisplet_query("SELECT n.id AS id, m.title AS title, m.text AS text, m.date AS date, n.viewed AS viewed 
								FROM srv_notifications n, srv_notifications_messages m 
								WHERE n.recipient='".$global_user_id."' AND n.viewed='0' AND m.id=n.message_id 
								ORDER BY m.date DESC");
		while($row = mysqli_fetch_array($sql)){
			echo '<li class="'.($row['viewed'] == 0 ? ' unread':'').' '.($active > 0 && $row['id'] == $active ? ' active':'').'">';
			
			echo '<span class="bold">'.$row['title'].' <span class="italic">('.$row['date'].')</span></span>';
			
            // Stara sporocila so brez editorja
            $text = (strtotime($row['date']) < strtotime('2021-08-26')) ? nl2br($row['text']) : $row['text'];
			echo '<br />'.$text;
			
			echo '</li>';
		}
		
		echo '</ul>';
		
				
		// Gumb za zapiranje
		echo '<div class="buttons_holder">';
		echo '<div class="buttonwrapper" title="'.$lang['srv_zapri'].'">';
		echo '<a class="ovalbutton ovalbutton" onclick="closeUnreadMessages(); return false;" href="#">';
		echo '<span>'.$lang['srv_zapri'].'</span>';
		echo '</a>';
		echo '</div>';
		echo '</div>';
	
	
		// Oznacimo sporocilo kot pregledano
		$sql = sisplet_query("UPDATE srv_notifications SET viewed='1' WHERE recipient='".$global_user_id."' AND viewed='0'");
	}
	
	public function ajax_viewGDPRMessage(){
		global $lang, $global_user_id;	
		
		echo '<h2>'.$lang['srv_notifications_unread'].'</h2>';
		
		echo '<ul>';
		echo '<li class="unread active">';
        
        if(isAAI()){
            echo '<span class="bold">'.$lang['srv_gdpr_notification_title_aai'].'</span>';
        }
        else{
            echo '<span class="bold">'.$lang['srv_gdpr_notification_title'].'</span>';
        }
		
		echo '<br /><br />';
		
		echo $lang['srv_gdpr_notification_text1'];
		
		echo '<br /><br />';
		
		echo $lang['srv_gdpr_notification_question'];
		echo '<br />';
		echo '<div class="spaceLeft gdpr_popup_radio"><input type="radio" name="gdpr_agree" id="gdpr_agree_1" value="1" onClick="enableGDPRPopupButton();"> <label for="gdpr_agree_1">'.$lang['srv_gdpr_notification_da'].'</label></div>';
		//echo ' <a href="#" onClick="toggleGDPRMore();"><span class="bold">'.$lang['srv_invitation_nonActivated_more'].'</span></a>';
		echo '<div class="spaceLeft gdpr_popup_radio"><input type="radio" name="gdpr_agree" id="gdpr_agree_0" value="0" onClick="enableGDPRPopupButton();"> <label for="gdpr_agree_0">'.$lang['srv_gdpr_notification_ne'].'</label></div>';

		echo '<div class="italic red gdpr_popup_radio">'.$lang['srv_gdpr_notification_reminder'].'</div>';

		echo '<br />';
		
		echo $lang['srv_gdpr_notification_pogoji'];

		echo '</li>';
		echo '</ul>';
		
				
		// Gumb za zapiranje
		//echo '<div class="floatRight spaceRight" id="GDPR_popup_button">';
		echo '<div class="floatRight spaceRight" id="GDPR_popup_button" style="visibility: hidden;">';
		echo '<div class="buttonwrapper" title="'.$lang['save'].'">';
		echo '<a class="ovalbutton ovalbutton" onclick="saveGDPRMessage();" href="#">';
		echo '<span>'.$lang['save'].'</span>';
		echo '</a>';
		echo '</div>';
		echo '</div>';
	}
	
	public function ajax_saveGDPRAgree(){
		global $global_user_id;
		
		if(isset($_POST['gdpr_agree']))
			$gdpr_agree = $_POST['gdpr_agree'];
			
		// Oznacimo sporocila kot pregledana
		$sql = sisplet_query("UPDATE users SET gdpr_agree='".$gdpr_agree."' WHERE id='".$global_user_id."'");
	}
}
?>