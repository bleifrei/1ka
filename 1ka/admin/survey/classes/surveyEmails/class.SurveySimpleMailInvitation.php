<?php
/**
 * @author 	Gorazd Veselič
 * @date	December 2011
 *
 */

define("SIMPLE_MAIL_QUOTA", 20);

class SurveySimpleMailInvitation {

	public $sid;									# id ankete

	function __construct($anketa) {
		$this->sid = $anketa;
		SurveyInfo::SurveyInit($this->sid);
	}
	
	function ajax() {

		switch ($_GET['a']) {
			case 'showInvitation':
				$this->showSimpleMailInvitation();
			break;
			case 'previewInvitation':
				$this->previewInvitation();
			break;
			case 'sendInvitation':
				$this->sendInvitation();
			break;
			
			default:
		print_r("<pre>");
		print_r($_GET);
		print_r($_POST);
		print_r("</pre>");				;
			break;
		}
	}
	
	function showSimpleMailInvitation() {
		global $site_url, $lang, $global_user_id;
		global $admin_type;
		
		
		$pid = (isset($_POST['pid']) && trim($_POST['pid']) != '') ? trim($_POST['pid']) : 'def1';
		
		# najprej preverimo prejemnike
		$emails = explode( "\n",mysql_real_unescape_string($_POST['emails'] ));
		
		#počistimo emaile
		$valid_emails = array();
		foreach ($emails as $email) {
			$email = trim($email);
			if ($this->validEmail($email) && !in_array($email,$valid_emails)) {
				$valid_emails[] = $email;
			}
		}
		if (count($valid_emails) > 0 ) {
			$css_wide = ' wide';
		}
		# izrišemo osnovni div .divPopUp
		echo '<div id="simpleMailInvitation_div" class="divPopUp'.$css_wide.'">';
		echo '<div class="divPopUp_top clr">';
		echo $lang['srv_email_vabila_simple_popup_title'];
		echo '</div>'; #PM_top
		echo '<div class="divPopUp_content">';
		
		
		# če ni admin ali manager preverimo koliko emailov je ta uporabnik že uspešno poslal
		if ($admin_type > 1) {
			$r = sisplet_query("SELECT count(email)FROM srv_simple_mail_invitation WHERE ank_id='".$this->sid."' AND state='ok' AND usr_id='".$global_user_id."'");
			list ($quota) = mysqli_fetch_row($r);
			if ($quota > SIMPLE_MAIL_QUOTA) {
				echo '<div class="simpleMailError">';
				printf ($lang['srv_simple_mail_inv_quota1'], SIMPLE_MAIL_QUOTA);
				echo '</div>';
			} else if ( ($quota+count($valid_emails)) > SIMPLE_MAIL_QUOTA) {
				echo '<div class="simpleMailError">';
				printf ($lang['srv_simple_mail_inv_quota2'], SIMPLE_MAIL_QUOTA);
				echo '</div>';
			}			
		}		
		
		
		# če imamo kaj vlejavnih emailov, potem nadaljujemo
		if (count($valid_emails) > 0 ) {
			# preberemo vsa obvestila ankete (2x iz lang fajla in če obstaja še kaj shranjeno)
			$profiles = $this->getProfiles();
			
			# izrišemo profile
			# dodamo cover div
			echo '<div id="simpleMailInvitationCoverDiv" />';
			echo '<div id="simpleMailInvitationPreviewDiv" />';
			echo '<div class="floatLeft">';
			echo '<div class="profile_holder">';
			foreach ($profiles as $key => $profile) {
				echo '<div value="'.$key.'" class="option'.($key == $pid ? ' active': '').'">'.$profile['subject'].'</div>';	
			}
			echo '</div>';
			echo '</div>'; #floatLeft
			echo '<div class="floatLeft spaceLeft">';
			echo '<div>';
			echo $lang['srv_invitation_subject'].':';
			echo '<br/>';
			echo '<input id="simpleMailSubject" type="text" value="'.$profiles[$pid]['subject'].'">';
			echo '</div>';
			echo '<div>';
			echo $lang['srv_invitation_content'].':';;
			echo '<br/>';
			echo '<textarea id="simpleMailBody">';
			echo $profiles[$pid]['body'];
			echo '</textarea>';
			echo '</div>';
			echo '</div>'; #floatLeft

			echo '<div class="floatLeft spaceLeft">';
			echo '<div>';
			echo $lang['srv_invitation_recipients'].':';
			echo '<br/>';
			echo '<textarea id="simpleMailRecipients">';
			echo implode("\n",$valid_emails);
			echo '</textarea>';
			echo '</div>';
			echo '</div>'; #floatLeft
			$buttonsRight = '<span class="buttonwrapper floatRight spaceRight"><a class="ovalbutton ovalbutton_orange" href="#" onclick="sendSimpleMailInvitation(); return false;"><span>'.$lang['srv_invitation_send'].'</span></a></span>';
			$buttonsRight .= '<span class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_gray" href="#" onclick="previewMailInvitation(); return false;"><span>'.$lang['srv_invitation_preview'].'</span></a></span>';
		} else {
			# nimamo veljavnih emailov, obvestimo in damo gumb zapri
			echo '<div class="simpleMailError">';
			echo $lang['srv_simple_mail_inv_no_emails'];
			echo '</div>';
		}
		
		echo '<br class="clr">';
		echo '</div>'; # class="popUp_content"
		echo '<div class="divPopUp_btm clr">'; #class="inv_FS_btm"
		echo '<div class="floatLeft spaceLeft"><div class="buttonwrapper" title="'.$lang['srv_cancel'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#fullscreen\').hide();$(\'#fade\').fadeOut(\'slow\'); return false;"><span>'.$lang['srv_cancel'].'</span></a></div></div>';
		
		if (count($valid_emails) > 0 ) {
			echo $buttonsRight;
		}

		echo '</div>'; #class="inv_FS_btm"
		echo '</div>'; #class="divPopUp"
	}
	
	function previewInvitation() {
		global $lang;
		# polovimo poslano
		
		# najprej preverimo prejemnike
		$emails = explode( "\n", mysql_real_unescape_string($_POST['emails']) );
		#počistimo emaile
		$valid_emails = array();
		foreach ($emails as $email) {
			$email = trim($email);
			if ($this->validEmail($email) && !in_array($email,$valid_emails)) {
				$valid_emails[] = $email;
			}
		}
		
		reset($valid_emails);
		$email = current($valid_emails);
		#polovimo subject
		$subject = trim($_POST['subject']);
		$body =  nl2br(trim(mysql_real_unescape_string($_POST['body'])));
				
		# če mamo SEO
		$url = SurveyInfo::getSurveyLink(); 
					
		$body = str_replace(
				array('#URL#','#EMAIL#'),
				array('<a href="' . $url . '">' . $url . '</a>',$email),
				$body);
		
		echo '<div><b>'.$subject.'</b></div><br/>';
		echo '<div>';
		echo $body;
		echo '</div>';
		echo '<br class="clr"/>';
		echo '<br/>';
		echo '<span class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_orange" href="#" onclick="$(\'#simpleMailInvitationPreviewDiv\').hide(); $(\'#simpleMailInvitationCoverDiv\').fadeOut(\'slow\'); return false;"><span>'.$lang['srv_zapri'].'</span></a></span>';
	}
	
	
	function sendInvitation() {
		global $lang, $admin_type, $site_path, $global_user_id;
		# polovimo poslano
		
		# najprej preverimo prejemnike
		$emails = explode( "\n", mysql_real_unescape_string($_POST['emails']) );
		#počistimo emaile
		$valid_emails = array();
		foreach ($emails as $email) {
			$email = trim($email);
			if ($this->validEmail($email) && !in_array($email,$valid_emails)) {
				$valid_emails[] = $email;
			}
		}

		#polovimo subject
		$subject = trim(mysql_real_unescape_string($_POST['subject']));
		$body = nl2br(trim(mysql_real_unescape_string($_POST['body'])));

		# če mamo SEO
		$url = SurveyInfo::getSurveyLink(); 
		
		# zamenjamo sistemske vrednosti
		$body = str_replace(array('#URL#', '[URL]'),
							array('<a href="' . $url . '">' . $url . '</a>','<a href="' . $url . '">' . $url . '</a>'),
							$body);

		$c = 0;
		# če ni admin ali manager preverimo koliko emailov je ta uporabnik že uspešno poslal
		if ($admin_type > 1 ) {
			$r = sisplet_query("SELECT count(email)FROM srv_simple_mail_invitation WHERE ank_id='".$this->sid."' AND state='ok' AND usr_id='".$global_user_id."'");
			list ($quota) = mysqli_fetch_row($r);
			$c = (int)$quota;
		}
				
		
		
		# izrišemo osnovni div .divPopUp
		echo '<div id="simpleMailInvitation_div" class="divPopUp'.$css_wide.'">';
		echo '<div class="divPopUp_top clr">';
		echo $lang['srv_email_vabila_simple_popup_title'];
		echo '</div>';
		echo '<div class="divPopUp_content">';
		
		if (is_array($valid_emails) && count($valid_emails) > 0) {
			if ($subject != null && $subject != '') {
				if ($body != null && $body != '') { 
					
					$send_success = array ();
					$send_errors = array ();
					$send_over_quota = array ();
					
					Common::getInstance()->Init($this->sid);
					
					foreach ($valid_emails AS $email) {
						if ( $c < SIMPLE_MAIL_QUOTA ) {

							# povečamo counter za neadmine
							if ($admin_type > 1) {
								$c++;
							} 
							# zamenjamo morebitne meaile
							$body = str_replace(array('#EMAIL#', '[EMAIL]'),
												array($email,
													  $email),
                                                $body);
                                                
							try
							{
								$MA = new MailAdapter($this->anketa, $type='invitation');
								$MA->addRecipients($email);
								$resultX = $MA->sendMail($body, $subject);
							}
							catch (Exception $e)
							{
							}
							
							if ($resultX) {
								# poslalo ok
								$send_success[] = $email;
							} else {
								# ni poslalo
								$send_errors[] = $email;
							}

						} else {
							# presegli smo kvoto, zabeležimo email.
							$send_over_quota[] = $email;
						}
					}
				} else {
					# Polje Vsebina ne sme biti prazno
					echo '<div class="simpleMailError">';
					echo $lang['srv_simple_mail_inv_error1'];
					echo '</div>';
					$has_error = true;
				}
				
			} else {
				# Polje Naslov - (zadeva) ne sme biti prazna
				echo '<div class="simpleMailError">';
				echo $lang['srv_simple_mail_inv_error2'];
				echo '</div>';
				$has_error = true;
			}
		} else {
			# ni vnešenih prejemnikov, ali pa emaili niso pravilni
			echo '<div class="simpleMailError">';
			echo $lang['srv_simple_mail_inv_error3'];
			echo '</div>';
			$has_error = true;
		}
		
		
		echo '<div class="floatLeft" style="width:310px; max-height:400px; overflow:auto;">';
		if (count($send_success) > 0 || count($send_errors) > 0) {
			if (count($send_success) > 0) {
				echo $lang['srv_simple_mail_inv_ok_msg'];
				echo '<br/>';
				echo implode("<br/>",$send_success);
				echo '<br/>';				
			}
			if (count($send_errors) > 0) {
				echo $lang['srv_simple_mail_inv_error4'];
				echo '<br/>';
				echo '<br/>';
				echo implode("<br/>",$send_errors);
			}
		} else {
			echo $lang['srv_simple_mail_inv_error5'];
		}		
		echo '</div>'; #floatLeft
		
		echo '<div class="floatRight spaceLeft" >';
		echo '<strong>'.$lang['srv_invitation_subject'].':'.'</strong>';
		echo '<br/>';
		echo '<div style="background-color: #fefefe; padding:3px; width:500px;">';
		echo $subject;
		echo '</div>';
		echo '<br/>';
		echo '<strong>'.$lang['srv_invitation_content'].':'.'</strong>';
		echo '<br/>';
		echo '<div style="background-color: #fefefe; padding:3px; width:500px;">';
		echo $body;
		echo '</div>';
		echo '</div>'; #floatLeft
		echo '<br class="clr" />';
		
		echo '</div>'; # class="popUp_content"
		echo '<div class="divPopUp_btm clr">'; #class="inv_FS_btm"

		echo '<span class="buttonwrapper floatRight spaceRight"><a class="ovalbutton ovalbutton_orange" href="#" onclick="$(\'#fullscreen\').hide();$(\'#fade\').fadeOut(\'slow\'); return false;"><span>'.$lang['srv_zapri'].'</span></a></span>';
		
		echo '</div>'; #class="inv_FS_btm"
		echo '</div>'; #class="divPopUp"
		
		# poskrbimo za tracking: statusi emailov: ENUM('ok','error','quota_exceeded'),
		$values = '';
		$prefix = '';
		$send_time = date( 'Y-m-d H:i:s');
		if (is_array($send_success) && count($send_success)>0) {
			foreach ($send_success AS $email) {
				$values .= $prefix."('".$this->sid."', '".$email."', '".$send_time."', 'ok', '".$global_user_id."')";
				$prefix = ', ';
			}
		}
		if (is_array($send_errors) && count($send_errors)>0) {
			foreach ($send_errors AS $email) {
				$values .= $prefix."('".$this->sid."', '".$email."', '".$send_time."', 'error', '".$global_user_id."')";
				$prefix = ', ';
			}
		}
		if (is_array($send_over_quota) && count($send_over_quota)>0) {
			foreach ($send_over_quota AS $email) {
				$values .= $prefix."('".$this->sid."', '".$email."', '".$send_time."', 'quota_exceeded', '".$global_user_id."')";
				$prefix = ', ';
			}
		}
		if ($values != '') {
			$insert_string = 'INSERT INTO srv_simple_mail_invitation (ank_id, email, send_time, state, usr_id) VALUES '.$values; 
			sisplet_query($insert_string);
			sisplet_query("COMMIT");
		}
	}
	
	function getProfiles() {
		global $lang;
	 	# preberemo vsa obvestila ankete (2x iz lang fajla in če obstaja še kaj shranjeno)
		$profiles = array();
		$profiles['def1'] = array('subject' => $lang['srv_simple_mail_inv_def1_subject'],
								  'body' => $lang['srv_simple_mail_inv_def1_body']);
		$profiles['def2'] = array('subject' => $lang['srv_simple_mail_inv_def2_subject'],
								  'body' => $lang['srv_simple_mail_inv_def2_body']);
		
		return $profiles;
	}
	
	/**
	 Validate an email address.
	 Provide email address (raw input)
	 Returns true if the email address has the email
	 address format and the domain exists.
	 */
	function validEmail($email = null) {
		$isValid = true;
		$atIndex = strrpos($email, "@");
		if (is_bool($atIndex) && !$atIndex)
		{
			$isValid = false;
		}
		else
		{
			$domain = substr($email, $atIndex+1);
			$local = substr($email, 0, $atIndex);
			$localLen = strlen($local);
			$domainLen = strlen($domain);
			$domain_parts = explode('.',$domain);

			if ($localLen < 1 || $localLen > 64) {
				// local part length exceeded
				$isValid = false;
			} else if ($domainLen < 1 || $domainLen > 255) {
				// domain part length exceeded
				$isValid = false;
			} else if ($local[0] == '.' || $local[$localLen-1] == '.') {
				// local part starts or ends with '.'
				$isValid = false;
			} else if ($domain[0] == '.' || $domain[$domainLen-1] == '.') {
				// domain part starts or ends with '.'
				$isValid = false;
			} else if (preg_match('/\\.\\./', $local))
			{
				// local part has two consecutive dots
				$isValid = false;
			} else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
				// character not valid in domain part
				$isValid = false;
			} else if (preg_match('/\\.\\./', $domain)) {
				// domain part has two consecutive dots
				$isValid = false;
			} else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
				// character not valid in local part unless
				// local part is quoted
				if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
					$isValid = false;
				}
			} else if ( strlen($domain_parts[0]) < 1) {
				// num chars in 
				$isValid = false;
			} else if ( strlen($domain_parts[1]) < 1) {
				$isValid = false;
			}
			
			#if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
			#	// domain not found in DNS
			#	$isValid = false;
			#}
			
		}
		return $isValid;
	}

	function countRecipients() {
		$r = sisplet_query("SELECT count(email)FROM srv_simple_mail_invitation WHERE ank_id='".$this->sid."' AND state='ok'");
		list ($quota) = mysqli_fetch_row($r);
		return (int)$quota;
	}
	
	function getRecipients() {
		$result = array();
		$sql_string = "SELECT smi.*, u.name, u.surname, u.email AS adminmail FROM srv_simple_mail_invitation AS smi LEFT JOIN users AS u ON smi.usr_id = u.id  WHERE smi.ank_id='".$this->sid."' ORDER BY smi.send_time DESC"; 
		$sql_query = sisplet_query($sql_string);
		while ($row = mysqli_fetch_assoc($sql_query)) {
			$result[] = $row;
		}
		return $result;
	}
}
