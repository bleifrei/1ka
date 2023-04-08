<?php
# mysql_real_unescape_string
/** Class ki skrbi za različna vabila
 *  Julij 2011
 *
 *
 * Enter description here ...
 * @author Gorazd_Veselic
 *
 */

define('GROUP_PAGINATE', 4);			# po kolko strani grupira pri paginaciji
define('REC_ON_PAGE', 10);			# kolko zapisov na stran pri urejanju respondentov
define('REC_ON_SEND_PAGE', 20);		# kolko zapisov na stran pri pošiljanju
define('NOTIFY_INFO1KA', 5);			# Nad koliko emaili obveščamo info@1ka.si

set_time_limit(2400); # 30 minut

class SurveyInvitationsNew {
	private $sid;
	private $count_all = 0;				# koliko prejemnikov je v bazi
	private $surveySettings;			# zakeširamo nastavitve ankete
	private $rec_send_page_limit = 20;	# Koliko zapisov imamo za paginacijo
	
	private $newTracking = false;		# Ali imamo podroben tracking za anketo

	private $inv_variables = array('email','password','ime','priimek','naziv','telefon','drugo','odnos');
	private $inv_variables_link = array('email'=>'email','geslo'=>'password','ime'=>'firstname','priimek'=>'lastname','naziv'=>'salutation','telefon'=>'phone','drugo'=>'custom','odnos'=>'relation','last_status'=>'last_status','sent'=>'sent','responded'=>'responded','unsubscribed'=>'unsubscribed');
	private $inv_variables_excel = array('email'=>'email','geslo'=>'password','ime'=>'firstname','priimek'=>'lastname','naziv'=>'salutation','telefon'=>'phone','drugo'=>'custom','odnos'=>'relation','last_status'=>'last_status','sent'=>'sent','responded'=>'responded','unsubscribed'=>'unsubscribed'
			,'date_inserted'=>'date_inserted','date_sent'=>'date_sent','date_responded'=>'date_responded','date_unsubscribed'=>'date_unsubscribed','list_name'=>'list_name');
	private $inv_variables_tel_excel = array('status'=>'status','email'=>'email','geslo'=>'password','ime'=>'firstname','priimek'=>'lastname','naziv'=>'salutation','telefon'=>'phone','drugo'=>'custom','odnos'=>'relation','call_time'=>'call_time','last_status'=>'last_status'
			,'comment'=>'comment','date_inserted'=>'date_inserted','list_name'=>'list_name');

	#private $inv_sys_db_map = array('email'=>'email','password'=>'geslo','firstname'=>'ime','lastname'=>'priimek','salutation'=>'naziv','phone'=>'telefon','custom'=>'drugo');

	private $invitationAdvancedConditionId = 0;
	private $user_inv_ids = array();
	
	private $db_table = '';
	
	function __construct($sid) {
	
		$this->sid = $sid;
		
		SurveyInfo::SurveyInit($this->sid);
		
		if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
			$this->db_table = '_active';
		
		$this->surveySettings = SurveyInfo::getInstance()->getSurveyRow();
		
		# koliko respondentov je že v bazi
		$sql_query_all 	 = sisplet_query("SELECT count(*) FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted = '0'");
		$sql_row_all 	 = mysqli_fetch_row($sql_query_all);
		$this->count_all = (int)$sql_row_all[0];
		
		# preverimo ali prikazujemo nov ali star način  odvisno od nastavitve v misc
		$sql_query = sisplet_query("SELECT count(*) FROM srv_anketa AS a WHERE id ='".$this->sid."' AND insert_time > (SELECT value FROM misc WHERE what = 'invitationTrackingStarted' LIMIT 1)");
		list($newTracking) = mysqli_fetch_row($sql_query);
		$this->newTracking = (int)$newTracking > 0 ? true : false;
		
		SurveyDataSettingProfiles :: Init($this->sid);
		
		if (isset($_SESSION['rec_on_send_page']) && (int)$_SESSION['rec_on_send_page'] > 0) {
			$this->rec_send_page_limit = (int)$_SESSION['rec_on_send_page'];
		} else {
			$this->rec_send_page_limit = REC_ON_SEND_PAGE;
		}
		
		SurveySession::sessionStart($this->sid);
		$this->invitationAdvancedConditionId = (int)SurveySession::get('invitationAdvancedConditionId');
	}

	function ajax() {
        
        if (isset($_REQUEST['a']) && trim($_REQUEST['a']) != '') {
			if (isset($_POST['recipients_list']) && $_POST['recipients_list'] != null) {
				$_POST['recipients_list'] = mysql_real_unescape_string($_POST['recipients_list']);
            }
            
			if (isset($_POST['fields']) && $_POST['fields'] != null) {
				$_POST['fields'] = mysql_real_unescape_string($_POST['fields']);
			}
            
            $this->action($_REQUEST['a']);
        } 
        else {
			echo 'Ajax error!';
			return 'Ajax error!';
		}
	}

	function action($action) {
		global $lang;
		global $site_url;
		global $app_settings;
		global $global_user_id;
		
		$NoNavi = $_POST['noNavi'];

		if ($action == 'inv_lists') {
			#$NoNavi = true;
		}
		if ($action == 'view_archive') {
			$NoNavi = true;
		}

		if ($NoNavi == false) {
			echo '<div id="inv_top_navi">';
			$this->displayNavigation();
			echo '</div>';
		}
		
		// Warning za nastavitev streznika
		$MA = new MailAdapter($this->sid, $type='invitation');
		if(!$MA->is1KA()){

			if($MA->isGoogle())
				$mail_settings = $MA->getGoogleSettings();
			else
				$mail_settings = $MA->getSMTPSettings();
			
            $userAccess = UserAccess::getInstance($global_user_id);

			$isEmail = (int)SurveyInfo::getInstance()->checkSurveyModule('email');
            if( (empty($mail_settings) || $mail_settings['SMTPFrom'] == '' || $mail_settings['SMTPUsername'] == '') 
                && $userAccess->checkUserAccess($what='invitations') 
                && $isEmail ){
				
				// Gorenje tega nima
				if (!Common::checkModule('gorenje')){

					// Pri ajax klicih nikoli tega ne izpišemo, ravno tako ne izpisujemo, ce imamo vklopljeno posiljanje brez emaila (navadna posta, sms)
					$noEmailing = SurveySession::get('inv_noEmailing');
					if($NoNavi == false && $noEmailing != 1){
						echo '<div id="email_server_warning">';
						echo '<span class="faicon warning icon-orange"></span> '.$lang['srv_invitation_server_warning'].'!';
						if($action != 'inv_server')
							echo '<span class="spaceLeft"><a href="'.$site_url . 'admin/survey/index.php?anketa='.$this->sid.'&a=invitations&m=inv_settings">'.$lang['srv_usermailing_setting'].'</a></span>';
						echo '</div>';
					}
				}
			}
		}	
		
		if ($action == M_INVITATIONS) {
			$this->useRecipientsList();
		}
		else if ($action == 'add_recipients_view') {
			$this->useRecipientsList();
		} 
        else if ($action == 'add_recipients') {
			$this->addRecipients();
		} 
        else if ($action == 'view_recipients') {
			$this->viewRecipients();
		} 
        else if ($action == 'view_message') {
			$this->viewMessage($_POST['mid']);
		} 
        else if ($action == 'make_default') {
			$this->makeDefaultMessage($_POST['mid']);
		} 
        else if ($action == 'make_default_from_preview') {
			$this->makeDefaultFromPreview($_POST['mid']);
		} 
        else if ($action == 'save_message_simple') {
			$this->save_message_simple();
		} 
        else if ($action == 'save_message_simple_noEmail') {
			$this->save_message_simple_noEmail();
		} 
        else if ($action == 'send_message') {
			$this->sendMessage($_POST['mid']);
		} 
        else if ($action == 'view_archive') {
			$this->viewAarchive($_POST['mid']);
		} 
        else if ($action == 'view_send_recipients') {
			if(isset($_POST['noMailing']) && $_POST['noMailing'] == '1')
				$this->selectSendToNoEmailing();
			else
				$this->selectSendTo();
		} 
        else if ($action == 'mailToSourceChange') {
			$this->mailToSourceChange();
		} 
        else if ($action == 'send_mail') {
			
            // Posiljanje brez emailov (samo aktivacija prejemnikov)
            if(isset($_GET['noemailing']) && $_GET['noemailing'] == '1'){
				$this->sendMailNoEmailing();
            }
            // Klasicno posiljanje preko smtp-ja
			else{
				$this->sendMail();
            }
		} 
        else if ($action == 'upload_recipients') {
			$this->uploadRecipients();
		} 
        else if ($action == 'view_archive_recipients') {
			$this->viewArchiveRecipients();
		} 
        else if ($action == 'edit_archive_comment') {
			$this->editArchiveComment();
		} 
        else if ($action == 'delete_recipient_confirm') {
			$this->deleteRecipientConfirm();
		} 
        else if ($action == 'delete_recipient') {
			$this->deleteRecipient();
		} 
        else if ($action == 'delete_recipient_single') {
			$this->deleteRecipientSingle();
		} 
        else if ($action == 'delete_recipient_all') {
			$this->deleteRecipientAll();
		} 
        else if ($action == 'export_recipients') {
			$this->exportRecipients();
		} 
        else if ($action == 'export_recipients_all') {
			$this->exportRecipients_all();
		} 
        else if ($action == 'use_recipients_list') {
			$this->useRecipientsList();
		} 
        else if ($action == 'change_import_type') {
			$this->changeImportType();
		} 
        else if ($action == 'save_recipient_list') {
			$this->saveRecipientList();
		} 
        else if ($action == 'get_profile_name') {
			echo "DEPRECATED!";
			die();
			$this->getProfileName();
		} 
        else if ($action == 'list_get_name') {
			$this->listGetName();
		} 
        else if ($action == 'invListEdit') {
			$this->invListEdit();
		} 
        else if ($action == 'save_rec_profile') {
			$this->saveRecProfile();
		} 
        else if ($action == 'delete_rec_profile') {
			$this->deleteRecProfile();
		} 
        else if ($action == 'edit_rec_profile') {
			$this->editRecProfile();
		} 
        else if ($action == 'update_rec_profile') {
			$this->updateRecProfile();
		} 
        else if ($action == 'delete_msg_profile') {
			$this->deleteMsgProfile();
		} 
        else if ($action == 'message_rename') {
			$this->messageRename();
		} 
        else if ($action == 'show_message_rename') {
			$this->showMessageRename();
		} 
        else if ($action == 'edit_recipient') {
			$this->editRecipient();
		} 
        else if ($action == 'save_recipient') {
			$this->saveRecipient();
		} 
        else if ($action == 'set_recipient_filter') {
			$this->setRecipientFilter();
		} 
        else if ($action == 'only_this_survey') {
			$this->onlyThisSurvey();
		} 
        else if ($action == 'add_users_to_database') {
			$this->add_users_to_database();
		} 
        else if ($action == 'add_checked_users_to_database') {
			$this->add_checked_users_to_database();
		} 
        else if ($action == 'arch_save_comment') {
			$this->saveArchiveComment();
		} 
        else if ($action == 'edit_message_details') {
			$this->editMessageDetails();
		} 
        else if ($action == 'message_save_details') {
			$this->messageSaveDetails();
		} 
        else if ($action == 'message_save_forward') {
			$this->messageSaveforward();
		} 
        else if ($action == 'message_save_forward_noEmail') {
			$this->messageSaveforwardNoEmail();
		} 
        else if ($action == 'prepare_save_message') {
			$this->prepareSaveMessage();		
		} 
        else if ($action == 'showRecipientTracking') {
			$this->showRecipientTracking();
		} 
        else if ($action == 'arch_show_recipients') {
			$this->showArchiveRecipients();
		} 
        else if ($action == 'arch_show_details') {
			$this->showArchiveDetails();
		} 
        else if ($action == 'arch_edit_details') {
			$this->editArchiveDetails();
		} 
        else if ($action == 'inv_status') {
			$this->showInvitationStatus();
		} 
        else if ($action == 'inv_lists') {
			$this->showInvitationLists();
		} 
        else if ($action == 'inv_list_save') {
			$this->listSave();
		} 
        else if ($action == 'invListSaveOld') {
			$this->invListSaveOld();
		} 
        else if ($action == 'invListEditSave') {
			$this->invListEditSave();
		} 
        else if ($action == 'editRecList') {

			$doEdit = $_SESSION['inv_edit_rec_profile'][$this->sid] == 'true' ? true : false;
			
            if ($doEdit) {
				$this->showEditRecList();
			} 
            else {
				$this->showNoEditRecList();
			}
		} 
        else if ($action == 'deleteRecipientsList') {
			$this->deleteRecipientsList();
        } 
        else if($action == 'deleteRecipientsListMulti'){
            $this->deleteRecipientsListMulti();
		} 
        else if ($action == 'showInvitationListsNames') {
			$this->showInvitationListsNames();
		} 
        else if ($action == 'changeInvRecListEdit') {
			$this->changeInvRecListEdit();
		} 
        else if ($action == 'upload_list') {
			$this->upload_list();
		} 
        else if ($action == 'send_upload_list') {
			$this->send_upload_list();
		} 
        else if ($action == 'changePaginationLimit') {
			$this->changePaginationLimit();
		} 
        else if ($action == 'setSortField') {
			$this->setSortField();
		} 
        else if ($action == 'validateSysVarsMapping') {
			$this->validateSysVarsMapping();
		} 
        else if ($action == 'addSysVarsMapping') {
			$this->addSysVarsMapping();
		} 
        else if ($action == 'recipientsAddForward') {
			$this->recipientsAddForward();
		} 
        else if ($action == 'showAdvancedConditions') {
			$this->showAdvancedConditions();
		} 
        else if ($action == 'setAdvancedCondition') {
			$this->setAdvancedCondition();
		} 
        else if ($action == 'inv_server') {
			$this->viewServerSettings();
		} 
        else if ($action == 'inv_settings') {
			$this->showInvitationSettings();
		} 
        else if ($action == 'set_noEmailing') {
			$this->setNoEmailing();
		} 
        else if ($action == 'set_noEmailing_type') {
			$this->setNoEmailingType();
		} 
        else if ($action == 'showAAISmtpPopup') {
			$this->showAAISmtpPopup();
		} 
        else {			
			$sql = sisplet_query("SELECT EXISTS (SELECT 1 FROM srv_invitations_archive WHERE ank_id='".$this->sid."')");
			$row = mysqli_fetch_array($sql);
			
			// Ce imamo ze posiljanje je default stran "Pregled"
			if($row[0] == 1)
				$this->showInvitationStatus();
			// Ce se nimamo nobenega posiljanja je default stran "Nastavitve"
			else
				$this->showInvitationSettings();
		}
	}

	function addRecipients() {
		global $lang,$global_user_id;
		# dodamo uporabnike
		$fields = $_POST['fields'];
		$recipients_list = $this->getCleanString($_POST['recipients_list']);

		$new_profile_id = (int)$_POST['pid'];

		# če so bile spremembe v profilu ga shranimo kot novega
		list($old_recipients_list,$old_fields) = $this->getRecipientsProfile((int)$_POST['pid']);
		$old_recipients_list = $old_recipients_list != '' ? implode("\n", $old_recipients_list) : $old_recipients_list;
		$new_recipients_list = str_replace("\n\r", "\n", $recipients_list);
		$old_fields = implode(",", $old_fields);
		$new_fields = implode(",", $fields);

		if ($_POST['save_profile'] == 'true' && ($old_recipients_list !== $new_recipients_list || $old_fields !== $new_fields)) {

			# shranjujemo v nov profil
			$post_fields = str_replace('inv_field_','',implode(',',$_POST['fields']));
			$post_recipients = $this->getCleanString($_POST['recipients_list']);

			#zaporedno številčimo ime seznama1,2.... če slučajno ime že obstaja
			$new_name = $lang['srv_inv_recipient_list_new'];
			$names = array();
			$s = "SELECT name FROM srv_invitations_recipients_profiles WHERE name LIKE '%".$new_name."%' AND uid='$global_user_id'";
			$q = sisplet_query($s);
			while ($r = mysqli_fetch_assoc($q)) {
				$names[] = $r['name'];
			}
			if (count($names) > 0) {
				$cnt = 1;
				while (in_array($lang['srv_inv_recipient_list_new'].$cnt, $names)) {
					$cnt++;
				}
				$new_name = $lang['srv_inv_recipient_list_new'].$cnt;
			}

			$sql_insert = "INSERT INTO srv_invitations_recipients_profiles".
					" (name,uid,fields,respondents,insert_time,comment, from_survey) ".
					" VALUES ('$new_name', '$global_user_id', '$post_fields', '$post_recipients', NOW(), '', '".$this->sid."' )";
			$sqlQuery = sisplet_query($sql_insert);

			if (!$sqlQuery) {
				$error = mysqli_error($GLOBALS['connect_db']);
				echo 'Napaka!';
			} else {
				$new_profile_id = mysqli_insert_id($GLOBALS['connect_db']);
			}
			sisplet_query("COMMIT");
		}
			
		#dodamo polja

		$result = $this->addMassRecipients($recipients_list, $fields, $new_profile_id);


		/*
		 # ni sprememb shranimo v sejo
		$this->saveSessionRecipients($recipients_list, $fields, $profile_comment);
		*/
		# prikažemo napake
		$invalid_recipiens_array = $this->displayRecipentsErrors($result);

		# po novem gremo na respondente
		$this->viewRecipients();

		return ;
	}

	function saveSessionRecipients($recipients_list, $fields, $profile_comment=null ) {
		global $lang;
		# polja
		$field_list = (isset($_POST['field_list']) && trim($_POST['field_list']) != '') ? trim($_POST['field_list']) : 'email';


		# shranimo v začasni profil
		$new_recipients = str_replace("\n\r", "\n", $recipients_list);
		$new_recipients = explode("\n",$new_recipients);

		session_start();
		if (is_array($_SESSION['inv_rec_profile'][$this->sid]['respondents'])) {
			$old_recipients = str_replace("\n\r", "\n", $_SESSION['inv_rec_profile'][$this->sid]['respondents']);
			$old_recipients = explode("\n",$old_recipients);
			$new_recipients = array_unique(array_merge($old_recipients, $new_recipients));
		}

		if (is_array($new_recipients)) {
			$recipients_list = implode("\n",$new_recipients);
		} else {
			$recipients_list = '';
		}
		$_SESSION['inv_rec_profile'][$this->sid] = array(
				'pid'=>-1,
				'name'=>$lang['srv_invitation_new_templist'],
				'fields'=>$field_list,
				'respondents'=>$recipients_list,
				'comment'=>$profile_comment
		);
	}

	function changeImportType() {
		$this->displayAddRecipientsView();
	}

	#prikažemo vmesnik za dodajanje respondentov
	function addRecipientsView( $fields = array(), $recipients_list=null) {	
		global $lang;
		global $site_url;
		
		//echo '<h2>'.$lang['srv_inv_add_recipients_heading'].'</h2>';
		$noEmailing = SurveySession::get('inv_noEmailing');
		
		$row = $this->surveySettings;
		
		echo '<h2 style="margin-left: 15px; color:#333 !important;">';
		// Text s podatki o nastavitvah posiljanja
		$settings_text = '<span class="bold spaceRight">'.$lang['srv_inv_message_type'].':</span>';
		
		$individual = (int)$this->surveySettings['individual_invitation'];
		if($individual == 0){
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_individual_0'].'</span>';
		}		
		else{
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_individual_1'].'</span>';
		}
		
		$settings_text .= ' - ';

		if($noEmailing == 0){
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_noEmail_0'].'</span>';
		}		
		else{
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_noEmail_1'].'</span>';
		}
		
		$settings_text .= ' - ';
		
		if($row['usercode_required'] == 0 && $individual != 0){
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_URL_0'];
			$settings_text .= ' ('.$lang['srv_inv_settings_code_0'].')</span>';
		}		
		else{
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_URL_1'];
			
			if($row['usercode_skip'] == 1 || $individual == 0){
				$settings_text .= ' ('.$lang['srv_inv_settings_code_2'].')</span>';
			}		
			else{
				$settings_text .= ' ('.$lang['srv_inv_settings_code_1'].')</span>';
			}
		}
			
		$settings_text .= '<span class="spaceLeft"> <a href="'.$site_url . 'admin/survey/index.php?anketa='.$this->sid.'&a=invitations&m=inv_settings">'.$lang['edit4'].'</a></span>';
			
		echo $settings_text;
		echo '</h2>';
		
		
		echo '<div id="srv_invitation_note">';
		echo $lang['srv_invitation_num_respondents'].(int)$this->count_all;
		echo '</div>';
		
		echo '<div id="inv_import">';
		$this->displayAddRecipientsView($fields, $recipients_list);
		echo '</div>'; # id="inv_import"
	}

	function displayAddRecipientsView( $fields = array(), $recipients_list=null) {
		global $lang, $site_path, $site_url;
		
		$field_list = array();
		
		# odvisno od tipa sporočil prikažemo različna polja
		# Personalizirano e-poštno vabilo

		// Ce ne posiljamo z emailom sta default polja ime in priimek
		$noEmailing = SurveySession::get('inv_noEmailing');
		if($noEmailing == 1){
			$default_fields = array(
					'inv_field_email' => 0,
					'inv_field_firstname' => 1,
					'inv_field_lastname' => 1,
					'inv_field_password' => 0,
					'inv_field_salutation' => 0,
					'inv_field_phone' => 0,
					'inv_field_custom' => 0,
			);
		}
		else{
			$default_fields = array(
					'inv_field_email' => count($fields) == 0 ? 1 : 0,
					'inv_field_firstname' => 0,
					'inv_field_lastname' => 0,
					'inv_field_password' => 0,
					'inv_field_salutation' => 0,
					'inv_field_phone' => 0,
					'inv_field_custom' => 0,
			);
		}
		
		// Ce imamo modul 360 imamo tudi odnos
		if(SurveyInfo::getInstance()->checkSurveyModule('360_stopinj')){
			$default_fields['inv_field_relation'] = 0;
		}
		
		# skreiramo nov vrstni red polj
		if (count($fields) > 0) {
			foreach ($fields as $key=>$field) {
				$field_list[$field] = 1;
				if (isset($default_fields[$field])) {
					unset($default_fields[$field]);
				}
			}
		}

		if (count($default_fields) > 0) {
			foreach ($default_fields as $key =>$field) {
				$field_list[$key] = $field;
				unset($default_fields[$key]);
			}
		}
		$import_type = isset($_POST['import_type']) ? (int)$_POST['import_type'] : 2;
		session_start();
		$checked = (isset($_SESSION['inv_rec_only_this_survey']) && (int)$_SESSION['inv_rec_only_this_survey'] == 1) ? '1' : '0';
        
        # profili respondentov
		echo '<div id="inv_recipients_profiles_holder">';
		echo '<label><input name="inv_show_list_type" id="inv_show_list_type1" type="radio" value="0" onclick="recipientsProfileOnlyThisSurvey();"'.($checked == '0' ? ' checked="checked"':'').' autocomplete="off">'.$lang['srv_inv_list_edit_from_this_survey'].'</label><br/>';
		echo '<label><input name="inv_show_list_type" id="inv_show_list_type2" type="radio" value="1" onclick="recipientsProfileOnlyThisSurvey();"'.($checked == '1' ? ' checked="checked"':'').' autocomplete="off">'.$lang['srv_inv_list_edit_from_all_surveys'].'</label><br/>';
		
		echo '<span>'.$lang['srv_inv_recipient_select_list'].'</span><br/>';

		$this->listRecipientsProfiles();
		echo '</div>'; # id=inv_recipients_profiles_holder

		echo '<div id="inv_import_list_container">';

		$sqlSysMapping = sisplet_query("SELECT * FROM srv_invitations_mapping WHERE sid = '$this->sid'");
		if (mysqli_num_rows($sqlSysMapping) > 0) {
			$sysUserToAddQuery = sisplet_query("SELECT count(*) FROM srv_user where ank_id='".$this->sid."' AND inv_res_id IS NULL AND deleted='0'");
			list($sysUserToAdd) = mysqli_fetch_row($sysUserToAddQuery);
		}
		
		echo '<span><input name="inv_import_type" id="inv_import_type2" type="radio" value="2" onclick="inv_change_import_type();"'.($import_type == 2 ? ' checked="checked"' : '').' autocomplete="off"><label for="inv_import_type2">'.$lang['srv_inv_recipiens_from_list'].'</label></span>';
		echo '<span><input name="inv_import_type" id="inv_import_type1" type="radio" value="1" onclick="inv_change_import_type();"'.($import_type == 1 ? ' checked="checked"' : '').' autocomplete="off"><label for="inv_import_type1">'.$lang['srv_inv_recipiens_from_file'].'</label></span>';
		echo '<span><input name="inv_import_type" id="inv_import_type3" type="radio" value="3" onclick="inv_change_import_type();"'.($import_type == 3 ? ' checked="checked"' : '').' autocomplete="off"><label for="inv_import_type3">'.$lang['srv_inv_recipiens_from_system']
		.($sysUserToAdd > 0 ? ' ('.$sysUserToAdd.')' : '').'</label></span>';
        
        echo Help::display('inv_recipiens_from_system');
        
        echo '<br class="clr"/>';
		echo '<br class="clr"/>';
        
        if ($import_type == 3) {
			$this->createSystemVariablesMapping();
        } 
        else {
			
		
			# sporočilo za personalizirana e-vabila in respondente iz baze
			echo '<span class="inv_note">'.$lang['srv_inv_recipiens_field_note'].'</span>';
	
			echo '<br >';
	
			echo '<div id="inv_field_container">';
	
			echo '<ul class="connectedSortable">';
			$field_lang = array();
			if (count($field_list ) > 0) {
				foreach ($field_list AS $field => $checked) {

					$is_selected = ($checked == 1 ) ? true : false;
	
					# če je polje obkljukano
					$css =  $is_selected ? ' class="inv_field_enabled"' : '';

					$label_for = ' for="'.$field.'_chk"';
					
					if(SurveyInfo::getInstance()->checkSurveyModule('360_stopinj') && $field == 'inv_field_relation')
						echo '<br />';
					
					echo '<li id="'.$field.'"'.$css.'>';
					echo '<input id="'.$field.'_chk" type="checkbox" class="inv_checkbox' . $hidden_checkbox . '"'.($is_selected == true ? ' checked="checked"' : '').'>';
					echo '<label'.$label_for.'>'.$lang['srv_'.$field].'</label>';
					echo '</li>';

					if ($is_selected == 1) {
						$field_lang[] = $lang['srv_'.$field];
					}
				}
			}
			echo '</ul>';
			echo '</div>';
			echo '<br class="clr" />';
			echo '<script type="text/javascript">';
			echo "$('ul.connectedSortable').sortable({update : function () { refreshFieldsList(); }, forcePlaceholderSize:'true', tolerance:'pointer', placeholder:'inv_field_placeholder', cancel:'#inv_field_relation'});";
			echo '</script>';
	
			# iz seznama
			echo '<div id="inv_import_list"'.($import_type != 1 ? '' : ' class="hidden"').'>' ;
			echo '<span class="inv_note">'.$lang['srv_inv_recipiens_email_note'];
			echo '<br class="clr" /><span class="inv_sample" >';
			echo $lang['srv_inv_recipiens_sample'].'&nbsp;</span><span class="inv_sample">';
			echo $lang['srv_inv_recipiens_sample1'];
			echo '</span>';
			echo '<br class="clr" />';
			echo '</span>';
			echo '<br class="clr" />'.$lang['srv_inv_recipiens_fields'].' <span id="inv_field_list" class="inv_type_0">';
			echo implode(',',$field_lang);
			echo '</span>';

			// Opozorilo za limit znakov pri passwordu (20)
			echo '<span id="inv_field_list_warning" class="red" style="display:none;">';
			echo '<br class="clr" /><br class="clr" />';
			echo $lang['srv_inv_recipiens_pass_warning'];
			echo '</span>';
			     
			echo '<br class="clr" /><br class="clr" />';
			
			// delimiter 
			echo $lang['srv_inv_recipient_list_delimiter']
                                .'<label for="recipientsDelimiter1"><input id="recipientsDelimiter1" type="radio" ' .(!isset ($_POST['recipientsDelimiter']) || $_POST['recipientsDelimiter']==","?'checked="checked"':'') .' value="," name="recipientsDelimiter">' .$lang['srv_inv_recipient_delimiter_comma'] .' (,)</label>&nbsp;&nbsp;&nbsp;'
                                .'<label for="recipientsDelimiter4"><input id="recipientsDelimiter4" type="radio" ' .(isset ($_POST['recipientsDelimiter']) && $_POST['recipientsDelimiter']=="|~|"?'checked="checked"':'') .' value="|~|" name="recipientsDelimiter">' .$lang['srv_inv_recipient_delimiter_1KA'] .' (|~|)</label>&nbsp;&nbsp;&nbsp;'
                                .'<label for="recipientsDelimiter2"><input id="recipientsDelimiter2" type="radio" ' .(isset ($_POST['recipientsDelimiter']) && $_POST['recipientsDelimiter']==";"?'checked="checked"':'') .' value=";" name="recipientsDelimiter">' .$lang['srv_inv_recipient_delimiter_semicolon'] .' (;) </label>&nbsp;&nbsp;&nbsp;'
                                .'<label for="recipientsDelimiter3"><input id="recipientsDelimiter3" type="radio" ' .(isset ($_POST['recipientsDelimiter']) && $_POST['recipientsDelimiter']=="|"?'checked="checked"':'') .' value="|" name="recipientsDelimiter">' .$lang['srv_inv_recipient_delimiter_pipe'] .' (|)</label>&nbsp;&nbsp;&nbsp;';
			
			echo '</span>';                        
                        
			echo '<br class="clr" /><br class="clr" />';
			
			echo '<textarea id="inv_recipients_list" cols="50" rows="9" name="inv_recipients_list">';
			if (is_array($recipients_list) && count($recipients_list) > 0 ) {
				echo implode("\n",$recipients_list);
			}
			echo '</textarea>';
			
			echo '<br class="clr"/>';
	
                        
			#podatki o profilu
			echo '<br class="clr"/>';
			$ppid = isset($_POST['pid']) ? (int)$_POST['pid'] : -1;
	
			echo '<span class="floatLeft" style="min-width:200px;">';
			if ((int)$ppid > 0) {
				# polovimo podatke profila
				$sql_string = "SELECT rp.*, u.name, u.surname FROM srv_invitations_recipients_profiles AS rp LEFT JOIN users AS u ON rp.uid = u.id WHERE rp.pid = '".(int)$ppid."'";
				$sql_query = sisplet_query($sql_string);
				$sql_row = mysqli_fetch_assoc($sql_query);
					
				$avtor = array();
				if (trim($sql_row['name'])) {
					$avtor[] = trim ($sql_row['name']);
				}
				if (trim($sql_row['surname'])) {
					$avtor[] = trim ($sql_row['surname']);
				}
				if ( count($avtor) > 0 ) {
					echo '<div class="gray">'.$lang['srv_inv_recipiens_list_created_by'].implode(' ',$avtor).'</div>';
				}
				if ( count($avtor) > 0 ) {
					echo '<div class="gray" title="'.date("d.m.Y H:i:s",strtotime($sql_row['insert_time'])).'">'.$lang['srv_inv_recipiens_list_created_day'].date("d.m.Y",strtotime($sql_row['insert_time'])).'</div>';
				}
				echo '<div class="gray" title="'.$sql_row['comment'].'" style="max-width:202px;">'.$lang['srv_inv_recipiens_list_comment'].trim (strip_tags($sql_row['comment'])).'</div>';
					
			} else {
				echo '<div class="gray">'.$lang['srv_inv_recipiens_temporary_list'].'</div>';
			}
			echo '</span>';
			
			echo '<div class="floatLeft spaceLeft">';
			
			echo '<div>';
			echo '<label class="spaceRight"><input type="checkbox" id="inv_recipients_add" value="1" checked="checked">'.$lang['srv_invitation_recipients_add_type4'].'</label>';	
			/*echo '<label class="spaceRight"><input type="checkbox" id="inv_recipients_rename_profile" onchange="invRenameRecipientsChange();">'.$lang['srv_inv_recipients_rename_list'].'</label>';	
			echo Help::display('srv_invitation_rename_profile');*/
			#echo '&nbsp;';
			#echo '<label class="spaceLeft"><input type="checkbox" id="inv_recipients_add_type2" value="2" >'.$lang['srv_invitation_recipients_add_type5'].'</label>';
			echo '</div>';

			echo '<div id="div_inv_recipients_rename_list_type" class="displayNone">';
			$this->saveRecipientListName();
			echo '</div>';
			
			echo '</div>';
			
			echo '<br />';

			# če že imamo prejemnike v bazi ponudimo gumb naprej
			echo '<span class="buttonwrapper floatRight spaceLeft spaceRight"><a class="ovalbutton ovalbutton_orange"  href="#" onclick="invRecipientsForward(); return false;"><span>'.$lang['srv_invitation_forward'].'</span></a></span>';
			echo '<br /><br />';
			
			echo '</div>';	# id=inv_import_list
	
			# iz datoteke
			echo '<div id="inv_import_file"'.($import_type == 1 ? '' : ' class="hidden"').'>' ;
			echo '<form id="inv_recipients_upload_form" name="resp_uploader" method="post" enctype="multipart/form-data" action="'.$site_url.'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=upload_recipients" autocomplete="off">';
			echo '<input type="hidden" name="fields" id="inv_recipients_upoad_fields" value="'.implode(',',$fields).'" />';
			echo '<input type="hidden" name="posted" value="1" />';
			echo '<span class="inv_note">'.$lang['srv_inv_recipiens_file_note_1'].'</span>';
	
			echo '<br class="clr" />'.$lang['srv_inv_recipiens_fields'].' <span id="inv_field_list" class="inv_type_1">';
			echo implode(',',$field_lang);
			echo '</span>';
			echo '<br class="clr" />';
			echo $lang['srv_mailing_upload_list'];
			echo '<input type="file" name="recipientsFile" id="recipientsFile" size="42" >';
			if (count($errors) > 0) {
				echo '<br class="clr" />';
				echo '<span class="inv_error_note">';
				foreach($errors as $error) {
					echo '* '.$error.'<br />';
				}
				echo '</span>';
			}
			echo '<br/><br/><label>'.$lang['srv_inv_recipient_import_file_delimiter'].'</label> <input type="radio" name="recipientsDelimiter" id="recipientsDelimiter1" value="," checked><label for="recipientsDelimiter1">'.$lang['srv_inv_recipient_delimiter_comma'].' (,)</label>';
			echo ' <input type="radio" name="recipientsDelimiter" id="recipientsDelimiter2" value=";"><label for="recipientsDelimiter2">'.$lang['srv_inv_recipient_delimiter_semicolon'].' (;)</label>';
			echo ' <input type="radio" name="recipientsDelimiter" id="recipientsDelimiter3" value="|"><label for="recipientsDelimiter3">'.$lang['srv_inv_recipient_delimiter_pipe'].' (|)</label>';
			echo ' <input type="radio" name="recipientsDelimiter" id="recipientsDelimiter4" value="|~|"><label for="recipientsDelimiter4">'.$lang['srv_inv_recipient_delimiter_1KA'].' (|~|)</label>';
			echo '</form>';
			echo '<br class="clr" /><span class="inv_sample" >';
			echo $lang['srv_inv_recipiens_sample'].'&nbsp;</span><span class="inv_sample">';
			echo $lang['srv_inv_recipiens_sample1'];
			echo '</span>';
			echo '<br class="clr" />';
			echo '<br class="clr" />';
			echo '<span id="inv_upload_recipients" class="buttonwrapper floatLeft spaceLeft" ><a class="ovalbutton ovalbutton_orange" ><span>'.$lang['srv_inv_btn_add_recipients_add'].'</span></a></span>';
			echo '</div>'; # id=inv_import_file
		}
		echo '</div>'; # id=inv_import_list_container

		echo '<br class="clr"/>';
	}
	

	/**
	 *
	 * Enter description here ...
	 * @param $_recipients - prejemniki vsak v svoji vrstici, ločeni z vejico
	 * @param $fields - array polij ki jih dodajamo
	 */
	function addMassRecipients($_recipients = '', $fields = array(), $new_profile_id = null) {
		global $global_user_id;

        # vabila z e-maili  naredimo tukaj, brez e-mailov pa s podebno funkcijo
		if (in_array('inv_field_email',$fields)) {
			# vabila z emaili
			$inv_iid = $this->inv_iid;
                    # povezava imena polji iz forem, z imeni polji v bazi
                    $db_vs_form_array = array(
                                    'inv_field_email' => 'email',
                                    'inv_field_firstname' => 'firstname',
                                    'inv_field_lastname' => 'lastname',
                                    'inv_field_password' => 'password',
                                    'inv_field_cookie' => 'cookie',
                                    'inv_field_salutation' => 'salutation',
                                    'inv_field_phone' => 'phone',
                                    'inv_field_custom' => 'custom',
									'inv_field_relation' => 'relation',
                    );

                    #dodamo potrebna sistemska polja
                    $this->addSystemVariables($fields);

                    # dodamo ustrezne uporabnike, neustrezne izpišemo še enkrat da se lahko popravijo
                    $_recipients = str_replace("\n\r", "\n", $_recipients);                
                    $recipients_list = explode("\n",$_recipients);
                    $num_recipients_list = count($recipients_list);

                    # katero polje je za e-mail
                    if (in_array('inv_field_email',$fields)) {
                            $user_email = true;
                    } else {
                            #za tip 0 - Personalizirano e-poštno vabilo kjer je polje e-mail obvezno
                            # dodamo polje email
                            $user_email = true;
                            $fields[] = 'inv_field_email';
                    }

                    # polje cookie mora bit zraven
                    if (!in_array('inv_field_cookie',$fields)) {
                            $fields[] = 'inv_field_cookie';
                    }

                    /* brez preverjanja unikatnosti
                    # polovimo že dodane prejemnike iz baze
                    $email_in_db = array();
                    $sql_string = "SELECT email FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted='0'";
                    $sql_query = sisplet_query($sql_string);

                    if (mysqli_num_rows($sql_query) > 0 ) {
                            while ($sql_row = mysqli_fetch_assoc($sql_query)) {
                                    $email_in_db[] = strtolower($sql_row['email']);
                            }
                    }
                    */

                    # katero polje je za password
                    if (in_array('inv_field_password',$fields)) {
                            $user_password = true;
                    } else {
                            $user_password = false;
                            # dodamo polje password
                            $fields[] = 'inv_field_password';
                    }

                    # polja za bazo
                    $db_fields = '';
                    foreach ($fields as $field) {
                            $db_fields .= ', '.$db_vs_form_array[$field];
                    }

                    # katera gesla (code) že imamo v bazi za to anketo
                    $password_in_db = array();
                    $sql_string = "SELECT password FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted = '0'";
                    $sql_query = sisplet_query($sql_string);
                    while ($sql_row = mysqli_fetch_assoc($sql_query)) {
                            $password_in_db[$sql_row['password']] = $sql_row['password'];
                    }

                    $unsubscribed = array();
                    #polovimo prejemnike ki ne želijo prejemati obvestil
                    $sql_string = "SELECT email FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND unsubscribed = '1'";
                    $sql_query = sisplet_query($sql_string);
                    $unsubscribed = array();
                    if (mysqli_num_rows($sql_query) > 0 ) {
                            while ($sql_row = mysqli_fetch_assoc($sql_query)) {
                                    $unsubscribed[] = $sql_row['email'];
                            }
                    }

                    #polovimo prejemnike ki ne želijo prejemati obvestil i datoteje srv_survey_unsubscribed
                    $condition = (count($unsubscribed) > 0 ) ? " AND email NOT IN('".implode('\',\'',$unsubscribed)."')" : '';
                    $sql_string = "SELECT email FROM srv_survey_unsubscribe WHERE ank_id = '".$this->sid."'".$condition;
                    $sql_query = sisplet_query($sql_string);
                    if (mysqli_num_rows($sql_query) > 0 ) {
                            while ($sql_row = mysqli_fetch_assoc($sql_query)) {
                                    $unsubscribed[] = $sql_row['email'];
                            }
                    }

                    #array z veljavnimi zapisi
                    $valid_recipiens_array = array();
                    # array z zapisi kjer so napake v geslih
                    $invalid_password_array = array();
                    #array z zapisi kjer so neveljavna gesla
                    $invalid_email_array = array();
                    #array z podvojenimi zapisi
                    $duplicate_email_array = array();
                    #aray z zapisi kjer so uporabniki izbrali da ne želijo prejemat e-mailov
                    $unsubscribed_recipiens_array = array();
                    if ( $num_recipients_list > 0 ) {
                            foreach ($recipients_list AS $recipient_line) {
                                    $recipient_line = trim($recipient_line);
                                    if ($recipient_line != null && $recipient_line != '') {


                                            // interni delimiter in ne vejicaa!!!!
                                            $line_array = explode('|~|',$recipient_line);
                                            
                                            //$line_array = explode(',',$recipient_line);
                                            # predpostavljamo da je vrstica vredu
                                            $invalid_line = false;

                                            #prilagodimo izbrana polja
                                            $recipent_array = array();
                                            $i = 0;
                                            foreach ($fields AS $field) {
                                                    $recipent_array[$field] = $line_array[$i];
                                                    $i++;
                                            }

                                            # izvedemo validacijo posameznih polij

                                            # najprej preverimo gesla, če niso uporabniško določena, jih dodelimo sami
                                            if ( $invalid_line == false ) {
                                                    # če še ni bilo napake	( da ne podvajamo zapisov pri katerih je več napak)
                                                    if ($user_password == false) {
                                                    # gesla določamo avtomatsko, (ne bo problemov :] )

                                                    # Izberemo random hash, ki se ni v bazi
                                                    do {
                                                    list($code,$cookie) = $this->generateCode();
                                                    #} while (in_array($code,$password_in_db) && !is_numeric($code));
                                            } while (in_array($code,$password_in_db));	# je bil problem kadar so same številke
                                            # polje za geslo je na zadnjem mestu (smo ga dodali zgoraj)
                                            $recipent_array['inv_field_password'] = $code;
                                            $recipent_array['inv_field_cookie'] = $cookie;

                                            # če je vse ok, geslo dodamo v seznam že uporabljenih
                                            $password_in_db[$code] = $code;
                                            } else {
                                                    # gesla je določil uporabnik, (dajmo ga malo preverit)
                                                    $user_password = trim($recipent_array['inv_field_password']);

                                                    # preverimo ali je geslo že v bazi
                                                    if ($user_password == null || $user_password == '' || in_array($user_password,$password_in_db)) {
                                                    $invalid_password_array[] = $recipient_line;
                                                    $invalid_line = true;
                                            }

                                            # če je vse ok, geslo dodamo v seznam že uporabljenih
                                            if ($invalid_line == false) {
                                                    $password_in_db[$user_password] = $user_password;
                                                    #dodamo še piškotek
                                                    list($code,$cookie) = $this->generateCode();
                                                    $recipent_array['inv_field_cookie'] = $cookie;
                                            }
                                            }
                                            }

                                            # če imamo emaile naredimo validacijo, preverimo zavrnitve.. itd
                                            if ($user_email == true && $invalid_line == false) {
                                                    # preberemo uporabniški email
                                                    $email_field = trim($recipent_array['inv_field_email']);

                                            #ali je email veljaven
                                            if (!$this->validEmail($email_field) && $invalid_line == false) {
                                                    $invalid_email_array[] = $recipient_line;
                                                    $invalid_line = true;
                                            }

                                            # ali je email podvojen
                                            /* brez preverjanja unikatnosti
                                            if (in_array(strtolower($email_field),$email_in_db) && $invalid_line == false) {
                                                    $duplicate_email_array[] = strtolower($recipient_line);
                                                    $invalid_line = true;
                                            }
                                            */

                                            # ali uporabnik ne želi prejemati sporočil (opted out)
                                            if (in_array($email_field,$unsubscribed) && $invalid_line == false) {
                                                    $unsubscribed_recipiens_array[] = $recipient_line;
                                                    $invalid_line = true;
                                            }

                                            # če je vse ok, email dodamo v seznam že uporabljenih
                                            if ( $invalid_line == false) {
                                                    $email_in_db[] = strtolower($email_field);
                                            }
                                            }
                                            # če je vse ok dodamo userja k veljavnim
                                            if ( $invalid_line == false) {
                                                    $valid_recipiens_array[] = $recipent_array;
                                            }
                                    }
                            }
                    }

                    if ($new_profile_id == null) {
                            $list_id = (int)$_POST['pid'];
                    } else {
                            $list_id = $new_profile_id;
                    }

                    # pripravimo sql stavek za vstavljanje
                    if (count($valid_recipiens_array ) > 0) {
                            $sql_insert_start = "INSERT INTO srv_invitations_recipients (ank_id".$db_fields.",sent,responded,unsubscribed,deleted,date_inserted,inserted_uid,list_id) VALUES ";
                            $count = 0;

                            $sql_insert_array = array();
                            $cnt = 0;
                            $max_in_array = 1000;	# po koliko respondentov dodajamo naenkeat
                            $array_loop = 0;
                            foreach ( $valid_recipiens_array AS $recipent_fields) {
                                    $cnt++;
                                    $sql_insert = "('".$this->sid."'";
                                    foreach ($recipent_fields as $field) {
                                            $sql_insert .= ", '" .str_replace (array('\\', "'"), array('', '&#39;'), $field) ."'";
                                    }
                                    $sql_insert .= ",'0','0','0','0',NOW(),'".$global_user_id."','".$list_id."')";
                                    $sql_insert_array[$array_loop][] = $sql_insert;
                                    if ($cnt >= $max_in_array) {
                                            $array_loop++;
                                            $cnt = 0;
                                    }
                            }
                            $sql_insert_end = " ON DUPLICATE KEY UPDATE firstname=VALUES(firstname), lastname=VALUES(lastname), salutation=VALUES(salutation), phone=VALUES(phone), custom=VALUES(custom), relation=VALUES(relation), deleted='0', date_inserted=NOW()";

                            # v loopu dodamo posamezne respondente po skupinah (ker kadar je respondentov veliko mysql crkne)
                            if (count($sql_insert_array) > 0) {

                                    foreach ($sql_insert_array AS $sub_insert_array) {
                                            $query_insert = $sql_insert_start. implode(',',$sub_insert_array) .$sql_insert_end;
                                            $sqlQuery = sisplet_query($query_insert);
                                            $rows = mysqli_affected_rows($GLOBALS['connect_db']);
                                            if (!$sqlQuery) {
                                                    $error = mysqli_error($GLOBALS['connect_db']);
                                            }
                                    }
                                    sisplet_query("COMMIT");

                            }
                    }
                    return array(	'valid_recipiens' => $valid_recipiens_array,
                                    'invalid_password' => $invalid_password_array,
                                    'invalid_email' => $invalid_email_array,
                                    'duplicate_email' => $duplicate_email_array,
                                    'unsubscribed' => $unsubscribed_recipiens_array);
		} else {
			# vabila brez emailov
			return $this->addMassRecipientsWithoutEmail($_recipients, $fields, $new_profile_id);
		}
	}


	function addMassRecipientsWithoutEmail($_recipients='', $fields=array(), $new_profile_id=null) {
		global $global_user_id;

		$inv_iid = $this->inv_iid;

		# povezava imena polji iz forem, z imeni polji v bazi
		$db_vs_form_array = array(
				'inv_field_email' => 'email',
				'inv_field_firstname' => 'firstname',
				'inv_field_lastname' => 'lastname',
				'inv_field_password' => 'password',
				'inv_field_cookie' => 'cookie',
				'inv_field_salutation' => 'salutation',
				'inv_field_phone' => 'phone',
				'inv_field_custom' => 'custom',
				'inv_field_relation' => 'relation',
		);

		#dodamo potrebna sistemska polja
		$this->addSystemVariables($fields);

		# dodamo ustrezne uporabnike, neustrezne izpišemo še enkrat da se lahko popravijo
		$_recipients = str_replace("\n\r", "\n", $_recipients);
		$recipients_list = explode("\n",$_recipients);
		$num_recipients_list = count($recipients_list);

		# katero polje je za e-mail
		if (in_array('inv_field_email',$fields)) {
			$user_email = true;
		} else {
		}

		# polje cookie mora bit zraven
		if (!in_array('inv_field_cookie',$fields)) {
			$fields[] = 'inv_field_cookie';
		}

		# polovimo že dodane prejemnike iz baze
		$user_in_db = array();
		$sql_string = "SELECT firstname,lastname,salutation,phone,custom,relation,password FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted='0'";
		$sql_query = sisplet_query($sql_string);

		if (mysqli_num_rows($sql_query) > 0 ) {
			while ($sql_row = mysqli_fetch_assoc($sql_query)) {
				$user_in_db[] = $sql_row['firstname'].$sql_row['lastname'].$sql_row['salutation'].$sql_row['phone'].$sql_row['custom'].$sql_row['relation'].$sql_row['password'];
			}
		}
		# katero polje je za password
		if (in_array('inv_field_password',$fields)) {
			$user_password = true;
		} else {
			$user_password = false;
			# dodamo polje password
			$fields[] = 'inv_field_password';
		}

		# polja za bazo
		$db_fields = '';
		foreach ($fields as $field) {
			$db_fields .= ', '.$db_vs_form_array[$field];
		}

		# katera gesla (code) že imamo v bazi za to anketo
		$password_in_db = array();
		$sql_string = "SELECT password FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted='0'";
		$sql_query = sisplet_query($sql_string);
		while ($sql_row = mysqli_fetch_assoc($sql_query)) {
			$password_in_db[$sql_row['password']] = $sql_row['password'];
		}

		$unsubscribed = array();
		#polovimo prejemnike ki ne želijo prejemati obvestil
		#
		#		$sql_string = "SELECT email FROM srv_invitations_recipients WHERE unsubscribed = '1'";
		#		$sql_query = sisplet_query($sql_string);
		#		$unsubscribed = array();
		//		if (mysqli_num_rows($sql_query) > 0 ) {
		#			while ($sql_row = mysqli_fetch_assoc($sql_query)) {
		#				$unsubscribed[] = $sql_row['email'];
		#			}
		#		}

		#array z veljavnimi zapisi
		$valid_recipiens_array = array();
		# array z zapisi kjer so napake v geslih
		$invalid_password_array = array();
		#array z zapisi kjer so neveljavna gesla
		$invalid_email_array = array();
		#array z podvojenimi zapisi
		$duplicate_email_array = array();
		#aray z zapisi kjer so uporabniki izbrali da ne želijo prejemat e-mailov
		$unsubscribed_recipiens_array = array();

		if ( $num_recipients_list > 0 ) {
			foreach ($recipients_list AS $recipient_line) {
				$recipient_line = trim($recipient_line);
				if ($recipient_line != null && $recipient_line != '') {
                                    
					$line_array = explode('|~|',$recipient_line);
					# predpostavljamo da je vrstica vredu
					$invalid_line = false;
						
					#prilagodimo izbrana polja
					$recipent_array = array();
					$i = 0;
					foreach ($fields AS $field) {
						$recipent_array[$field] = $line_array[$i];
						$i++;
					}

					# izvedemo validacijo posameznih polij
						
					# najprej preverimo gesla, če niso uporabniško določena, jih dodelimo sami
					if ( $invalid_line == false ) {
						# če še ni bilo napake	( da ne podvajamo zapisov pri katerih je več napak)
						if ($user_password == false) {
						# gesla določamo avtomatsko, (ne bo problemov :] )
							
						# Izberemo random hash, ki se ni v bazi
						do {
						list($code,$cookie) = $this->generateCode();
					} while (in_array($code,$password_in_db));
					# polje za geslo je na zadnjem mestu (smo ga dodali zgoraj)
					$recipent_array['inv_field_password'] = $code;
					$recipent_array['inv_field_cookie'] = $cookie;

					# če je vse ok, geslo dodamo v seznam že uporabljenih
					$password_in_db[$code] = $code;
						
					} else {
						# gesla je določil uporabnik, (dajmo ga malo preverit)
						$user_password = trim($recipent_array['inv_field_password']);
							
						# preverimo ali je geslo že v bazi
						if ($user_password == null || $user_password == '' || in_array($user_password,$password_in_db)) {
						$invalid_password_array[] = $recipient_line;
						$invalid_line = true;
					}

					# če je vse ok, geslo dodamo v seznam že uporabljenih
					if ($invalid_line == false) {
						$password_in_db[$user_password] = $user_password;
						#dodamo še piškotek
						list($code,$cookie) = $this->generateCode();
						$recipent_array['inv_field_cookie'] = $cookie;
					}
					}
					}
					# če imamo emaile naredimo validacijo, preverimo zavrnitve.. itd
					//					if ($user_email == true && $invalid_line == false) {
					if ($invalid_line == false) {
						#						# preberemo uporabniški email
						$email_field = trim($recipent_array['inv_field_firstname'])
						. trim($recipent_array['inv_field_lastname'])
						. trim($recipent_array['inv_field_salutation'])
						. trim($recipent_array['inv_field_phone'])
						. trim($recipent_array['inv_field_custom'])
						. trim($recipent_array['inv_field_relation'])
						. trim($recipent_array['inv_field_password']);

						#
						#						#ali je email veljaven
						//						if (!$this->validEmail($email_field) && $invalid_line == false) {
						#							$invalid_email_array[] = $recipient_line;
						#							$invalid_line = true;
						#						}
						
					# ali je email podvojen
					if (in_array(strtolower($email_field),$user_in_db) && $invalid_line == false) {
						$duplicate_email_array[] = strtolower($recipient_line);
						$invalid_line = true;
					}

					# ali uporabnik ne želi prejemati sporočil (opted out)
					//						if (in_array($email_field,$unsubscribed) && $invalid_line == false) {
					#							$unsubscribed_recipiens_array[] = $recipient_line;
					#							$invalid_line = true;
					#						}

					# če je vse ok, email dodamo v seznam že uporabljenih
					if ( $invalid_line == false) {
						$user_in_db[] = $email_field;
					}
					}
					# če je vse ok dodamo userja k veljavnim
					if ( $invalid_line == false) {
						$valid_recipiens_array[] = $recipent_array;
					}

				}
			}
		}

		# pripravimo sql stavek za vstavljanje
		if ($new_profile_id == null) {
			$list_id = (int)$_POST['pid'];
		} 
		else {
			$list_id = $new_profile_id;
		}
					
		if (count($valid_recipiens_array ) > 0) {
			$sql_insert_start = "INSERT INTO srv_invitations_recipients (ank_id".$db_fields.",sent,responded,unsubscribed,deleted,date_inserted,inserted_uid,list_id) VALUES ";
			$count = 0;

			$sql_insert_array = array();
			$cnt = 0;
			$max_in_array = 1000;	# po koliko respondentov dodajamo naenkeat
			$array_loop = 0;
			foreach ( $valid_recipiens_array AS $recipent_fields) {
				$cnt++;
				$sql_insert = "('".$this->sid."'";
				foreach ($recipent_fields as $field) {
					$sql_insert .= ", '$field'";
				}
				$sql_insert .= ",'0','0','0','0',NOW(),'".$global_user_id."','".$list_id."')";
				$sql_insert_array[$array_loop][] = $sql_insert;
				if ($cnt >= $max_in_array) {
					$array_loop++;
					$cnt = 0;
				}
			}
			$sql_insert_end = " ON DUPLICATE KEY UPDATE firstname=VALUES(firstname), lastname=VALUES(lastname), salutation=VALUES(salutation), phone=VALUES(phone), custom=VALUES(custom), relation=VALUES(relation), deleted='0', date_inserted=NOW()";

			# v loopu dodamo posamezne respondente po skupinah (ker kadar je respondentov veliko mysql crkne)
			if (count($sql_insert_array) > 0) {
				foreach ($sql_insert_array AS $sub_insert_array) {
					$query_insert = $sql_insert_start. implode(',',$sub_insert_array) .$sql_insert_end;
					$sqlQuery = sisplet_query($query_insert);
					$rows = mysqli_affected_rows($GLOBALS['connect_db']);
					if (!$sqlQuery) {
						$error = mysqli_error($GLOBALS['connect_db']);
					}
				}
				sisplet_query("COMMIT");

			}
		}
			
		return array(	'valid_recipiens' => $valid_recipiens_array,
				'invalid_password' => $invalid_password_array,
				'invalid_email' => $invalid_email_array,
				'duplicate_email' => $duplicate_email_array,
				'unsubscribed' => $unsubscribed_recipiens_array);
	}

	function generateCode() {
		
		// Zgeneriramo cookie
		$cookie = md5(mt_rand(1, mt_getrandmax()) . '@' . $_SERVER['REMOTE_ADDR']);
		
		// Ce je prvi znak stevilka jo spremenimo v crko ker drugace vcasih izvoz v excel ne dela ok
		$letters = array('a', 'b', 'c', 'd', 'e', 'f');
		if(is_numeric(substr($cookie, 0, 1)))
			$cookie = $letters[array_rand($letters)].substr($cookie, 1);

		// Koda je prvi del cookija
		$code = substr($cookie, 0, 6);
		
		return array($code, $cookie);
	}

	#preglej prejemnike
	function viewRecipients($errors = array(), $msgs = array()) {
		global $lang, $site_url, $admin_type;

        $noEmailing = SurveySession::get('inv_noEmailing');
		
		$row = $this->surveySettings;
		
		echo '<h2 style="margin-left: 15px; color:#333 !important;">';
		// Text s podatki o nastavitvah posiljanja
		$settings_text = '<span class="bold spaceRight">'.$lang['srv_inv_message_type'].':</span>';
		
		$individual = (int)$this->surveySettings['individual_invitation'];
		if($individual == 0){
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_individual_0'].'</span>';
		}		
		else{
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_individual_1'].'</span>';
		}
		
		$settings_text .= ' - ';

		if($noEmailing == 0){
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_noEmail_0'].'</span>';
		}		
		else{
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_noEmail_1'].'</span>';
		}
		
		$settings_text .= ' - ';
		
		if($row['usercode_required'] == 0 && $individual != 0){
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_URL_0'];
			$settings_text .= ' ('.$lang['srv_inv_settings_code_0'].')</span>';
		}		
		else{
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_URL_1'];
			
			if($row['usercode_skip'] == 1 || $individual == 0){
				$settings_text .= ' ('.$lang['srv_inv_settings_code_2'].')</span>';
			}		
			else{
				$settings_text .= ' ('.$lang['srv_inv_settings_code_1'].')</span>';
			}
		}
			
		$settings_text .= '<span class="spaceLeft"> <a href="'.$site_url . 'admin/survey/index.php?anketa='.$this->sid.'&a=invitations&m=inv_settings">'.$lang['edit4'].'</a></span>';
			
		echo $settings_text;
		echo '</h2>';
		
		#polovimo prejemnike ki ne želijo prejemati obvestil
		
		# nastavimo filter
		session_start();
		$filter_duplicated = $_SESSION['inv_filter']['duplicated'];
		$filter = $_SESSION['inv_filter']['value'];
		if ($filter != '') {
			$mysql_filter = " AND ("
			. "i.email LIKE '%".$filter."%'"
			. "OR i.firstname LIKE '%".$filter."%'"
			. "OR i.lastname LIKE '%".$filter."%'"
			. "OR i.password LIKE '%".$filter."%'"
			. "OR i.salutation LIKE '%".$filter."%'"
			. "OR i.phone LIKE '%".$filter."%'"
			. "OR i.custom LIKE '%".$filter."%'"
			. "OR i.relation LIKE '%".$filter."%'"
			. ")";
		}
		

		if (isset($_SESSION['inv_filter_on']) && $_SESSION['inv_filter_on'] == true )  {

			if (!isset($_SESSION['inv_filter']['send']) || (int)$_SESSION['inv_filter']['send'] == 0) {
				$mysql_filter .= "";
			} else if ($_SESSION['inv_filter']['send'] == 2) {
				$mysql_filter .= " AND i.sent='1'";
			} else if ($_SESSION['inv_filter']['send'] == 1) {
				$mysql_filter .= " AND i.sent='0'";
			}
			if (!isset($_SESSION['inv_filter']['respondet']) || (int)$_SESSION['inv_filter']['respondet'] == 0) {
				$mysql_filter .= "";
			} else if ($_SESSION['inv_filter']['respondet'] == 2) {
				$mysql_filter .= " AND i.responded='1'";
			} else if ($_SESSION['inv_filter']['respondet'] == 1) {
				$mysql_filter .= " AND i.responded='0'";
			}
			if (!isset($_SESSION['inv_filter']['unsubscribed']) || (int)$_SESSION['inv_filter']['unsubscribed'] == 0) {
				$mysql_filter .= "";
			} else if ($_SESSION['inv_filter']['unsubscribed'] == 2) {
				$mysql_filter .= " AND i.unsubscribed='1'";
			} else if ($_SESSION['inv_filter']['unsubscribed'] == 1) {
				$mysql_filter .= " AND i.unsubscribed='0'";
			}

			if (!isset($_SESSION['inv_filter']['list']) || (int)$_SESSION['inv_filter']['list'] == -2) {
				$mysql_filter .= "";
			} else  {
				$mysql_filter .= " AND i.list_id='".(int)$_SESSION['inv_filter']['list']."'";
			}
			
		}
		# preštejemo koliko imamo vseh respondentov in koliko jih je brez e-maila
		$sql_query_all = sisplet_query("SELECT id FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted = '0'");
		$count_all = mysqli_num_rows($sql_query_all);

		$sql_string_withot_email = "SELECT count(*) FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted = '0' AND email IS NULL AND sent='0'";
		$sql_query_without_email = sisplet_query($sql_string_withot_email);
		$sql_row_without_email  = mysqli_fetch_row($sql_query_without_email);
		$count_without_email = $sql_row_without_email[0];

		
		#koliko zapisov bi morali prikazovati
		$sql_string_filterd_all = "SELECT i.* FROM srv_invitations_recipients AS i WHERE i.ank_id = '".$this->sid."' AND i.deleted = '0'".$mysql_filter." ORDER BY i.id";
		$sql_query_filterd_all = sisplet_query($sql_string_filterd_all);
		$filtred_all = mysqli_num_rows($sql_query_filterd_all);


		# Katera polja prikazujemo v seznamu prejemnikov
		$default_fields = array(
				'sent' => 1,
				'email' => 1,
				'firstname' => 0,
				'lastname' => 0,
				'salutation' => 0,
				'phone' => 0,
				'custom' => 0,
		);

        // Volitve nimajo nekaterih polj
        if(!SurveyInfo::getInstance()->checkSurveyModule('voting')){
            $default_fields['responded'] = 1;
            $default_fields['unsubscribed'] = 1;
            $default_fields['password'] = 1;
        }
		
		// Ce imamo modul 360 imamo tudi odnos
		if(SurveyInfo::getInstance()->checkSurveyModule('360_stopinj')){
			$default_fields['relation'] = 0;
		}

		# pogledamo katera polja dejansko prikazujemo
		$sql_select_fields = array();
		while ($sql_row = mysqli_fetch_assoc($sql_query_filterd_all)) {
			foreach ($default_fields AS $key => $value) {
				# če polje še ni dodano in če ni prazno, ga dodamo
				if ($fields[$key] == 0 && isset($sql_row[$key]) && trim($sql_row[$key]) != '') {
					$fields[$key] = 1;
					$sql_select_fields[] = 'i.'.$key;
				}
			}
		}

		// Dodamo še ostala polja
        // Volitve nimajo nekaterih polj
        if(!SurveyInfo::getInstance()->checkSurveyModule('voting')){
		    $fields['last_status'] = 1;
            $fields['date(date_expired)'] = 1;
        }
            
		$sql_select_fields[] = 'i.last_status';
		$fields['date_inserted'] = 1;
		
		$fields['inserted_uid'] = 1;
		$sql_select_fields[] = 'i.inserted_uid';
		$sql_select_fields[] = 'i.date_inserted';
		$sql_select_fields[] = 'date(date_expired)';
		$fields['list_id'] = 1;
		$sql_select_fields[] = 'i.list_id';
		

		#dodamo paginacijo in poiščemo zapise
		$page = isset($_GET['page']) ? $_GET['page'] : '1';
		$limit_start = ($page*REC_ON_PAGE)-REC_ON_PAGE;
		
		$sort_string = $this->getSortString();
		$sql_string_duplicated = null;
		if ($filter_duplicated == true)
		{
			$sql_string_duplicated = " JOIN(
					SELECT email, COUNT( email ) AS email_duplicated
					FROM srv_invitations_recipients
					WHERE ank_id='".$this->sid."' AND deleted = '0'
					GROUP BY email
					HAVING email_duplicated >1
			) AS dup ON dup.email = i.email ";
		}
				
		$sql_string_filterd = "SELECT i.id, 
			".implode(',',$sql_select_fields)." 
			FROM srv_invitations_recipients AS i
			".$sql_string_duplicated." 
			WHERE i.ank_id = '".$this->sid."' AND i.deleted = '0' 
			".$mysql_filter." 
			".$sort_string." 
			LIMIT $limit_start,".REC_ON_PAGE;

		#koliko zapisov bi morali prikazovati
		# po potrebi upoštevamo filter pogojev
		$this->user_inv_ids = array();
		if ((int)$this->invitationAdvancedConditionId > 0) {
			$this->user_inv_ids = $this->getConditionUserIds($this->invitationAdvancedConditionId);
			if (isset($this->user_inv_ids) && is_array($this->user_inv_ids) && count($this->user_inv_ids) > 0 )
			{
			
				$sql_string_filterd = "SELECT i.id, ".implode(',',$sql_select_fields)." FROM srv_invitations_recipients AS i "
				. $sql_string_duplicated
				. " INNER JOIN srv_user AS su ON i.id = su.inv_res_id"
				." WHERE su.ank_id = '$this->sid' AND su.inv_res_id IS NOT NULL AND su.deleted = '0' AND su.id IN ('".(implode('\',\'',$this->user_inv_ids))."')"
				." AND i.ank_id = '".$this->sid."' AND i.deleted = '0'".$mysql_filter.' '.$sort_string." LIMIT $limit_start,".REC_ON_PAGE;
			}		
		}
		
		$sql_query_filterd = sisplet_query($sql_string_filterd);
		# polovimo userje
		$uids = array();
		$sql_string_users = "SELECT DISTINCT i.inserted_uid FROM srv_invitations_recipients AS i WHERE i.ank_id = '".$this->sid."' AND i.deleted = '0'".$mysql_filter." GROUP BY i.inserted_uid ORDER BY i.id";
		$sql_query_users = sisplet_query($sql_string_users);
		while ($row_users = mysqli_fetch_assoc($sql_query_users)) {
			$uids[] = $row_users['inserted_uid'];
		}


		$users = array();
		if (count($uids) > 0) {
			$sql_string_users = "SELECT id, email FROM users WHERE id IN(".implode(',',$uids).")";
			$sql_query_users = sisplet_query($sql_string_users);
			while ($row_users = mysqli_fetch_assoc($sql_query_users)) {
				$users[$row_users['id']] = array('email'=>$row_users['email']);
			}
		}

		# polovimo sezname
		$lids = array();
		$sql_string_users = "SELECT i.list_id FROM srv_invitations_recipients AS i WHERE i.ank_id = '".$this->sid."' AND i.deleted = '0'".$mysql_filter." GROUP BY i.list_id ORDER BY i.id";
		$sql_query_users = sisplet_query($sql_string_users);
		while ($row_users = mysqli_fetch_assoc($sql_query_users)) {
			$lids[] = $row_users['list_id'];
		}

		#seznami
		$lists = array();
		$lists['-1'] = array('name'=>$lang['srv_invitation_new_templist']);
		$lists['0'] = array('name'=>$lang['srv_invitation_new_templist_author']);

		if (count($lids) > 0 ) {
			$sql_string_lists = "SELECT * from srv_invitations_recipients_profiles WHERE pid IN(".implode(',',$lids).") ";
			$sql_query_lists = sisplet_query($sql_string_lists);
			while ($row_lists = mysqli_fetch_assoc($sql_query_lists)) {
				$lists[$row_lists['pid']] = array('name'=>$row_lists['name']);
			}
		}

		if (count($msgs) > 0) {
			echo '<span class="inv_msg_note">';
			foreach($msgs as $msg) {
				echo '* '.$msg.'<br />';
			}
			echo '</span>';
		}

		if (count($errors) > 0) {
			echo '<span class="inv_error_note">';
			foreach($errors as $error) {
				echo '* '.$error.'<br />';
			}
			echo '</span>';
		}

		if ($count_all > 0 ) {

			# dodamo filtriranje
				
			echo '<div id="inv_rec_filter">';
			echo '<label>'.$lang['srv_invitation_recipients_filter'].'</label> <input id="inv_rec_filter_value" type="text" onchange="inv_filter_recipients(); return false;" value="'.$_SESSION['inv_filter']['value'].'">';

            echo '&nbsp;&nbsp;&nbsp;<label><input id="inv_rec_filter_on" type="checkbox" onchange="inv_filter_recipients(); return false;"'.(isset($_SESSION['inv_filter_on']) && $_SESSION['inv_filter_on'] == true ? ' checked="true"' : '').'>';
			echo $lang['srv_invitation_recipients_filter_advanced'].'</label>';
			
            if (isset($_SESSION['inv_filter_on']) && $_SESSION['inv_filter_on'] == true )  {
				echo '&nbsp;';
				echo '&nbsp;';
				echo '<label>'.$lang['srv_invitation_recipients_filter_sent'];
				$selected = (int)(isset($_SESSION['inv_filter']['send']) ? (int)$_SESSION['inv_filter']['send'] : 0);
				echo ' <select id="inv_rec_filter_send" onchange="inv_filter_recipients();">';
				echo '<option value="0"'.((int)$selected == 0 ? ' selected="selected"' : '').'>'.$lang['srv_invitation_filter0'].'</option>';
				echo '<option value="1"'.((int)$selected == 1 ? ' selected="selected"' : '').'>'.$lang['srv_invitation_filter1'].'</option>';
				echo '<option value="2"'.((int)$selected == 2 ? ' selected="selected"' : '').'>'.$lang['srv_invitation_filter2'].'</option>';
				echo '</select></label>';

				echo '&nbsp;';
				echo '<label>'.$lang['srv_invitation_recipients_filter_answered'];
				$selected = (int)(isset($_SESSION['inv_filter']['respondet']) ? (int)$_SESSION['inv_filter']['respondet'] : 0);
				echo ' <select id="inv_rec_filter_respondet" onchange="inv_filter_recipients();">';
				echo '<option value="0"'.((int)$selected == 0 ? ' selected="selected"' : '').'>'.$lang['srv_invitation_filter0'].'</option>';
				echo '<option value="1"'.((int)$selected == 1 ? ' selected="selected"' : '').'>'.$lang['srv_invitation_filter1'].'</option>';
				echo '<option value="2"'.((int)$selected == 2 ? ' selected="selected"' : '').'>'.$lang['srv_invitation_filter2'].'</option>';
				echo '</select></label>';

				echo '&nbsp;';
				echo '<label>'.$lang['srv_invitation_recipients_filter_unsubscribed'];
				$selected = (int)(isset($_SESSION['inv_filter']['unsubscribed']) ? (int)$_SESSION['inv_filter']['unsubscribed'] : 0);
				echo ' <select id="inv_rec_filter_unsubscribed" onchange="inv_filter_recipients();">';
				echo '<option value="0"'.((int)$selected == 0 ? ' selected="selected"' : '').'>'.$lang['srv_invitation_filter0'].'</option>';
				echo '<option value="1"'.((int)$selected == 1 ? ' selected="selected"' : '').'>'.$lang['srv_invitation_filter1'].'</option>';
				echo '<option value="2"'.((int)$selected == 2 ? ' selected="selected"' : '').'>'.$lang['srv_invitation_filter2'].'</option>';
				echo '</select></label>';

				$this->listCondition();
				
				$this->advancedCondition();				
			}
			
				
			echo '</div>';
			
            
			echo '<form id="frm_inv_rec_export" name="resp_uploader" method="post" autocomplete="off">';
			echo '<input type="hidden" name="anketa" id="anketa" value="'.$this->sid.'">';
			echo '<input type="hidden" name="noNavi" id="noNavi" value="true">';
			echo '<br class="clr"/>';
		
			if ($filter != '') {
				echo '<span class="red strong">';
				printf($lang['srv_inv_list_no_recipients_filter'],$filter);
				#Podatki so filtrirani: "'.$filter.'"<br/>';
				echo '</span>';
			}
			
			if ($count_all > 0 && mysqli_num_rows($sql_query_filterd) != $count_all ) {
				echo '<div id="srv_invitation_note" class="floatLeft spaceRight">';
				echo $lang['srv_invitation_num_respondents_filtred'].(int)mysqli_num_rows($sql_query_filterd);
				echo '</div>';
			} else {
				echo '<div id="srv_invitation_note" class="floatLeft spaceRight">';
				echo $lang['srv_invitation_num_respondents'].(int)$this->count_all;
				echo '</div>';
			}

			
			# duplicated
			echo '<label><input type="checkbox" id="inv_rec_filter_duplicates" onchange="inv_filter_recipients(); return false" '. ($filter_duplicated ?' checked="checked"':'') .'>' . $lang['srv_inv_recipient_show_only_duplicates'] .'</label><br class="clr"/>';
			
			if (mysqli_num_rows($sql_query_filterd) > 0 && $count_all > 0) {
			
				echo '<br class="clr"/>';		
				
				$this->displayPagination($filtred_all);
				
				echo '<br class="clr"/>';
				
				
				echo '<div style="display:inline-block; margin-right: 20px;">';		
				
				# če že imamo prejemnike v bazi več kot 20 ponudimo gumb naprej tudi zgoraj
				if ($this->count_all > 20) {
					echo '<span class="buttonwrapper floatRight spaceLeft" style="margin-bottom:10px;"><a class="ovalbutton ovalbutton_orange"  href="'.$this->addUrl('view_message').'"><span>'.$lang['srv_invitation_forward'].'</span></a></span>';
				}
				
				// Izvoz vseh v excel
				echo '<span class="floatLeft" style="line-height:45px; padding-left:10px;">';
				echo '<a onclick="inv_recipients_form_action(\'export_all\');" href="#">';
				echo '<span class="faicon xls" title="'.$lang['srv_invitation_recipients_export_all'].'" style="height:14px; width:16px;"></span>';
				echo ' '.$lang['srv_invitation_recipients_export_all'];
				echo '</a>';
				echo '</span>';
				

				echo '<table id="tbl_recipients_list">';

				echo '<tr>';
				# checkbox
				echo '<th class="tbl_icon" colspan="'.($this->surveySettings['show_email']==1?'4':'3').'" >&nbsp;</th>';
				
				foreach ($fields AS $fkey =>$field) {
					if ($field == 1) {
						if ($fkey == 'sent' || $fkey == 'responded' || $fkey == 'unsubscribed' ) {
							#echo '<th class="anl_ac tbl_icon_'.$fkey.' inv_'.$fkey.'_1" title="'.$lang['srv_inv_recipients_'.$fkey].'">&nbsp;</th>';
							echo '<th'.$this->addSortField($fkey).' class="anl_ac pointer tbl_icon_'.$fkey.'" title="'.$lang['srv_inv_recipients_'.$fkey].'">'.$lang['srv_inv_recipients_'.$fkey].$this->addSortIcon($fkey).'</th>';
						} else if ($fkey == 'last_status' ) {
							echo '<th'.$this->addSortField($fkey).' class="anl_ac pointer" title="'.$lang['srv_inv_recipients_'.$fkey].'">'.$lang['srv_inv_recipients_'.$fkey].$this->addSortIcon($fkey).'</th>';
						} else {
							echo '<th'.$this->addSortField($fkey).' class="pointer" title="'.$lang['srv_inv_recipients_'.$fkey].'">'.$lang['srv_inv_recipients_'.$fkey].$this->addSortIcon($fkey).'</th>';
						}
					}
				}
				echo '</tr>';
				while ($sql_row = mysqli_fetch_assoc($sql_query_filterd)) {

					echo '<tr>';
					# checkbox
						
					echo '<td><input type="checkbox" name="inv_rids[]" value="'.$sql_row['id'].'"></td>';
					#izbriši
					#echo '<td class="tbl_inv_left"><span class="as_link rec_delete_confirm" inv_rid="'.$sql_row['id'].'">'.$lang['srv_inv_list_profiles_delete'].'</span></td>';
					echo '<td class="tbl_inv_left"><span class="faicon delete_circle icon-orange_link" onclick="deleteRecipient_confirm(\''.$sql_row['id'].'\'); return false;" title="'.$lang['srv_inv_list_profiles_delete'].'"></span></td>';
					#uredi
					#echo '<td class="tbl_inv_left"><span class="as_link rec_edit" inv_rid="'.$sql_row['id'].'">'.$lang['srv_inv_list_profiles_edit'].'</span></td>';
					echo '<td class="tbl_inv_left"><span class="faicon edit smaller icon-as_link" onclick="editRecipient(\''.$sql_row['id'].'\'); return false;" title="'.$lang['srv_inv_list_profiles_edit'].'"></span></td>';
					
					// Skoci na urejanje odgovorov - ce imamo identifikatorje povezane s podatki
					if($this->surveySettings['show_email'] == 1)
						echo '<td class="tbl_inv_left"><span class="icon-grey_dark_link" onclick="window.open(\''.$site_url.'/main/survey/edit_anketa.php?anketa='.$this->sid.'&usr_id='.$sql_row['id'].'&code='.$sql_row['password'].'\', \'blank\')" title="'.$lang['srv_edit_data_row'].'"></span></td>';

					foreach ($fields AS $fkey =>$field) {
						if ($field == 1) {
							switch ($fkey) {
								case 'sent':
									echo '<td class="anl_ac pointer" onclick="showRecipientTracking(\''.$sql_row['id'].'\'); return false;">';
									echo '<span class="faicon '.((int)$sql_row['sent'] == 1 ? ('inv_sent_1') : 'inv_sent_0').' icon-as_link" title="'.((int)$sql_row['sent'] == 1 ? $lang['sent'] : $lang['not_sent']).'"></span>';
									echo '</td>';
									break;
								case 'responded':
									echo '<td class="anl_ac">';
									echo '<span class="faicon '.((int)$sql_row['responded'] == 1 ? ('inv_responded_1') : 'inv_responded_0').' icon-orange"></span>';
									echo '</td>';
									break;
								case 'unsubscribed':
									echo '<td class="anl_ac ">';
									echo '<span class="faicon '.((int)$sql_row['unsubscribed'] == 1 ? ('inv_unsubscribed_1') : 'inv_unsubscribed_0').'"></span>';
									echo '</td>';
									break;
								case 'last_status':
									echo '<td>('.$sql_row[$fkey].') - '.$lang['srv_userstatus_'.$sql_row[$fkey]].'</td>';
									break;
								case 'inserted_uid':
									echo '<td>'.$users[$sql_row[$fkey]]['email'].'</td>';
									break;
								case 'email':
									echo '<td>';
									if ($filter != '') {
										echo $this->hightlight($sql_row[$fkey],$filter);
									} else {
										echo $sql_row[$fkey];
									}
									echo '</td>';
									break;
								case 'list_id':
									echo '<td>';
									if ((int)$sql_row[$fkey] > 0) {
										if ($lists[$sql_row[$fkey]]['name'] != '') {
											echo '<a href="#" onclick="$(\'#anketa_edit\').load(\'ajax.php?t=invitations&a=use_recipients_list\', {anketa:srv_meta_anketa_id, pid:'.(int)$sql_row[$fkey].' });">'.$lists[$sql_row[$fkey]]['name'].'</a>';
										} else {
											echo $lang['srv_inv_recipient_list_deleted'];
										}
									} else {
										echo $lists[$sql_row[$fkey]]['name'];
									}
									echo '</td>';
									break;
								default:
									echo '<td class="tbl_inv_left">';
									if ($filter != '') {
										echo $this->hightlight($sql_row[$fkey],$filter);
									} else {
										echo $sql_row[$fkey];
									}
										
									echo '</td>';
									break;
							}

						}
					}
					echo '</tr>';
					@ob_flush();
				}
				echo '</table>';
				
				echo '<div id="inv_bottom_edit">';
				echo '<span class="faicon arrow_up"></span> ';
				echo '<span id="inv_switch_on"><a href="javascript:inv_selectAll(true);">'.$lang['srv_select_all'].'</a></span>';
				echo '<span id="inv_switch_off" style="display:none;"><a href="javascript:inv_selectAll(false);">'.$lang['srv_deselect_all'].'</a></span>';
				echo '&nbsp;&nbsp;<a href="#" onClick="inv_recipients_form_action(\'delete\');"><span class="faicon delete_circle icon-orange" title="'.$lang['srv_invitation_recipients_delete_selected'].'"></span>&nbsp;'.$lang['srv_invitation_recipients_delete_selected'].'</a>';
				echo '&nbsp;&nbsp;<a href="#" onClick="inv_recipients_form_action(\'export\');"><span class="faicon xls delete" style="height:14px; width:16px;" title="'.$lang['srv_invitation_recipients_export_selected'].'"></span>&nbsp;'.$lang['srv_invitation_recipients_export_selected'].'</a>';
				echo '&nbsp;&nbsp;<a href="#" onClick="inv_recipients_form_action(\'add\');">&nbsp;'.$lang['srv_invitation_recipients_activate3'].'</a>';
				
				// Aktivira vse v seznamu (jih doda v podatke, kot da so poslani)
				echo '<br /><span style="line-height:40px;"><a href="#" onclick="inv_add_rec_to_db(); return false;" target="_blank">'.$lang['srv_invitation_recipients_activate2'].'</a></span>';
				
				echo '</div>';
				
				# če že imamo prejemnike v bazi ponudimo gumb naprej
				if ($count_all > 0) {
					echo '<div class="buttonwrapper floatRight spaceLeft" style="margin-top:-30px;"><a class="ovalbutton ovalbutton_orange"  href="'.$this->addUrl('view_message').'"><span>'.$lang['srv_invitation_forward'].'</span></a></div>';
					echo '<br class="clr"/><br>';
				}
				echo '</div>';

			} else {
				echo $lang['srv_inv_list_no_recipients_filtred'].'<br class="clr">';
			}
			echo '</form>';
		} else {
			echo $lang['srv_inv_list_no_recipients'].'<br class="clr">';
		}
	}

	function viewMessage($mid = null) {
		global $lang, $global_user_id, $site_url;
		
		$row = $this->surveySettings;
		
	
		echo '<h2 style="margin-left: 15px; color:#333 !important;">';
			
		// Text s podatki o nastavitvah posiljanja
		$settings_text = '<span class="bold spaceRight">'.$lang['srv_inv_message_type'].':</span>';
		
		$individual = (int)$this->surveySettings['individual_invitation'];
		if($individual == 0){
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_individual_0'].'</span>';
		}		
		else{
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_individual_1'].'</span>';
		}
		
		$settings_text .= ' - ';
		
		$noEmailing = SurveySession::get('inv_noEmailing');
		if($noEmailing == 0){
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_noEmail_0'].'</span>';
		}		
		else{
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_noEmail_1'].'</span>';
		}
		
		$settings_text .= ' - ';
		
		if($row['usercode_required'] == 0 && $individual != 0){
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_URL_0'];
			$settings_text .= ' ('.$lang['srv_inv_settings_code_0'].')</span>';
		}		
		else{
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_URL_1'];
			
			if($row['usercode_skip'] == 1 || $individual == 0){
				$settings_text .= ' ('.$lang['srv_inv_settings_code_2'].')</span>';
			}		
			else{
				$settings_text .= ' ('.$lang['srv_inv_settings_code_1'].')</span>';
			}
		}
			
		$settings_text .= '<span class="spaceLeft"> <a href="'.$site_url . 'admin/survey/index.php?anketa='.$this->sid.'&a=invitations&m=inv_settings">'.$lang['edit4'].'</a></span>';
			
		echo $settings_text;
		
		//echo '<span style="padding-left:15px; padding-right:15px;"><input type="radio" name="inv_messages_noEmailing" id="inv_messages_noEmailing_1" '.($noEmailing == 0 ? ' checked="checked"' : '').' style="margin-bottom:4px;" onClick="noEmailingToggle(\'0\');" /> <label for="inv_messages_noEmailing_1">'.$lang['srv_inv_message_noemailing_0'].'</label></span>';
		//echo '<span><input type="radio" name="inv_messages_noEmailing" id="inv_messages_noEmailing_2" '.($noEmailing == 1 ? ' checked="checked"' : '').' style="margin-bottom:4px;" onClick="noEmailingToggle(\'1\');" /> <label for="inv_messages_noEmailing_2">'.$lang['srv_inv_message_noemailing_1'].'</label></span>';
				
		echo '</h2>';
		
		
		// Ce posiljamo preko emaila
		if($noEmailing != 1){
			echo '<div id="inv_messages_holder">';
			
			if ($this->checkDefaultMessage() == false) {
				echo '<span class="inv_error_note">';
				echo $lang['srv_invitation_note6'];
				echo '</span>';
			} else {
				$sql_string = "SELECT id, naslov, subject_text, body_text, reply_to, isdefault, comment, url FROM srv_invitations_messages WHERE ank_id = '$this->sid'";
				$sql_query = sisplet_query($sql_string);
				$array_messages = array();
				while ( list($id, $naslov, $subject_text, $body_text, $reply_to ,$isdefault, $comment, $url) = mysqli_fetch_row($sql_query) ) {
					$array_messages[$id] = array('id'=>$id, 'naslov' => $naslov, 'subject_text'=>$subject_text, 'body_text'=>$body_text, 'reply_to'=>$reply_to ,'isdefault'=>$isdefault, 'comment'=>$comment, 'url'=>$url);
					if ($isdefault == '1') {
						# če izbiramo profile in nismo postali še nobenga
						if ( $mid == null ) {
							$mid = $id;
						}
					}
				}
				$preview_message = $array_messages[$mid];
					
				echo '<div id="inv_messages_profiles_holder" class="floatLeft">';
				echo '<span>'.$lang['srv_invitation_message_choose'].':</span><br/>';
				echo '<div id="invitation_messages" >';
				echo '<ol>';
				foreach ($array_messages AS $_m => $message) {
					echo '<li mid="'.$message['id'].'" class="'
					.($message['id'] == $mid ? ' active' : '')
					.'" onclick="invChangeMessage(\''.$message['id'].'\')">';
					echo $message['naslov'];
					echo '</li>';
				}
				echo '</ol>';
				echo '</div>';	#invitation_messages
				echo '<br class="clr" />';
				if (count($array_messages) > 1) {
					echo '<span class="as_link" id="inv_del_msg_profile" onclick="invMessageDelete();" title="'.$lang['srv_inv_message_delete_profile'].'">'.$lang['srv_inv_message_delete_profile'].'</span><br/>';
				}
				echo '<span class="as_link" id="inv_ren_msg_profile" onclick="invShowMessageRename();" title="'.$lang['srv_inv_message_rename_profile'].'">'.$lang['srv_inv_message_rename_profile'].'</span>';
				{
					# polovimo podatke profila
					$sql_string = "SELECT sim.*, u.name, u.surname, e.name as ename, e.surname as esurname FROM srv_invitations_messages AS sim LEFT JOIN users AS u ON sim.uid = u.id LEFT JOIN users AS e ON sim.edit_uid = e.id WHERE sim.id = '".(int)$mid."'";
					$sql_query = sisplet_query($sql_string);
					$sql_row = mysqli_fetch_assoc($sql_query);

					$avtor = array();
					$edit = array();
					if (trim($sql_row['name'])) {
						$avtor[] = trim ($sql_row['name']);
					}
					if (trim($sql_row['surname'])) {
						$avtor[] = trim ($sql_row['surname']);
					}

					if ( count($avtor) > 0 ) {
						echo '<div class="gray">'.$lang['srv_invitation_author'].' '.implode(' ',$avtor).'</div>';
					}
					if ( count($avtor) > 0 ) {
						echo '<div class="gray" title="'.date("d.m.Y H:i:s",strtotime($sql_row['insert_time'])).'">'.$lang['srv_invitation_author_day'].' '.date("d.m.Y",strtotime($sql_row['insert_time'])).'</div>';
					}
					if (trim($sql_row['ename'])) {
						$edit[] = trim ($sql_row['ename']);
					}
					if (trim($sql_row['esurname'])) {
						$edit[] = trim ($sql_row['esurname']);
					}

					if ( count($edit) > 0 && $edit != $avtor) {
						echo '<div class="gray">'.$lang['srv_invitation_changed'].' '.implode(' ',$edit).'</div>';
					}
					if ($sql_row['insert_time'] != $sql_row['edit_time']) {
						echo '<div class="gray" title="'.date("d.m.Y H:i:s",strtotime($sql_row['edit_time'])).'">'.$lang['srv_invitation_changed_day'].' '.date("d.m.Y",strtotime($sql_row['insert_time'])).'</div>';
					}
						
					echo '<div class="gray" style="max-width:202px">'.$lang['srv_invitation_comment'].' '. trim ($sql_row['comment']).'</div>';
				}
					
				echo '</div>'; #inv_messages_profiles_holder
					
				$MA = new MailAdapter($this->sid, $type='invitation');
				# zlistamo seznam vseh sporočil
				# izpišemo primer besedila
				echo '<div id="inv_msg_preview_hld" class="floatLeft">';
				echo '<span class="h2 spaceRight floatLeft">'.$lang['srv_inv_message_draft_content_heading'].'</span> '.Help::display('srv_inv_message_title');

				//echo '<span class="spaceRight floatRight"><a href="'.$site_url . 'admin/survey/index.php?anketa='.$this->sid.'&a=invitations&m=inv_server&show_back=true">'.$lang['srv_inv_message_draft_settings'].'</a></span>';
				echo '<br class="clr"/>';
				echo '<div id="inv_error_note" class="hidden"></div>';
				echo '<div id="inv_msg_preview">';
				echo '<table>';
				echo '<tr><th>'.$lang['srv_inv_message_draft_content_from'].':</th>';
				echo '<td class="inv_bt">';
				if($MA->getMailFrom() == '')
					echo '<a href="'.$site_url . 'admin/survey/index.php?anketa='.$this->sid.'&a=invitations&m=inv_settings">'.$lang['srv_usermailing_setting'].'</a>';	
				else
					echo $MA->getMailFrom();
				echo '<input type="hidden" id="inv_message_replyto" value="'.$MA->getMailFrom().'" autocomplete="off" readonly>';
				echo '</td></tr>';
				echo '<tr><th>'.$lang['srv_inv_message_draft_content_reply'].':</th>';
				echo '<td class="inv_bt">';
				echo $MA->getMailReplyTo();
				echo '<input type="hidden" id="inv_message_replyto" value="'.$MA->getMailReplyTo().'" autocomplete="off" readonly>';
				echo '</td></tr>';
				echo '<tr><th>'.$lang['srv_inv_message_draft_content_subject'].':</th>';
				echo '<td class="inv_bt">';
				echo '<input type="text" id="inv_message_subject" value="'.$preview_message['subject_text'].'" autocomplete="off">';
				echo '</td></tr>';
				echo '<tr><th>'.$lang['srv_inv_message_draft_content_body'].':</th>';
				echo '<td ><div class="msgBody">';
				echo '<textarea id="inv_message_body" name="inv_message_body" autocomplete="off">'.($preview_message['body_text']).'</textarea>';
				echo '</div>';
				?>
				<script type="text/javascript">
				create_inv_editor('inv_message_body', false);
				</script><?php 
				echo '</td></tr>';
				$urls = $this->getUrlLists();
				
				if (count($urls) > 0) {
					echo '<tr><th>'.$lang['srv_inv_message_draft_url'].'</th>';
					echo '<td>';
					echo '<select id="inv_message_url">';
					foreach ($urls AS $url) {
						$selected = '';
						if ($preview_message['url'] == '') {
							if ($preview_message['dc'] == true) {
								$selected = ' selected="selected"';
							}
						} else if ($preview_message['url'] == $url['url']) {
							$selected = ' selected="selected"';
						}
						echo '<option value="'.$url['url'].'"'.$selected.'>'.$url['name'].'</option>';
					}
					echo '</select>';
					echo '</td>';
					echo '</tr>';
				}
				echo '</table>';
				echo '</div>';
				
				echo '<br class="clr"/>';

				echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['srv_invitation_forward'].'"><a class="ovalbutton" href="#" onclick="inv_message_save_forward(\''.$mid.'\'); return false;"><span>'.$lang['srv_invitation_forward'].'</span></a></span>';
				echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['srv_invitation_forward'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="if(inv_message_save_simple(\''.$mid.'\')) { window.location.reload() }; return false;"><span>'.$lang['srv_inv_message_save'].'</span></a></span>';
				echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['srv_invitation_message_saveNew'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="inv_message_save_advanced(\''.$mid.'\'); return false;"><span>'.$lang['srv_invitation_message_saveNew'].'</span></a></span>';
				
				echo '</div>';

				echo '<div id="invitation_profile_notes"><p>';
				
				$_indicators = $this->getAvailableIndicators();

				$_sysVars = $this->getAvailableSysVars();
				
				echo $lang['srv_inv_message_help'];
				// Poiščemo še sistemske spremenljivke iz ankete
				$prefix='';
				if (count($_indicators ) > 0) {
					echo $lang['srv_inv_message_help_identifikators'];
					
					foreach ($_indicators AS $_identifikator) {
						echo $prefix.'<br/>#'.strtoupper($_identifikator).'#'.$lang['srv_inv_message_help_system_'.strtolower($_identifikator)];
						$prefix = ', ';
					}
									
					# preverimo ali imamo nastavljen mapping
					$prefix = '';
					$sqlSysMapping = sisplet_query("SELECT * FROM srv_invitations_mapping WHERE sid = '$this->sid'");
					if (count($_sysVars ) > 0 && mysqli_num_rows($sqlSysMapping) > 0) {
						echo '<br/><br/>'.$lang['srv_inv_message_help_systemvars'];
						foreach ($_sysVars AS $_sys_var => $_sysLabel) {
							echo $prefix.'<br/>#'.strtoupper($_sys_var).'#'.$lang['srv_inv_message_help_system_'.strtolower($_sys_var)];
							$prefix = ', ';
						}
							
					}
				} else {
					echo $lang['srv_invitation_note12'];
				}
				
				echo '</p></div>';
			}
			
			echo '</div>';
		}
		// Ce samo dokumentiramo - navadna posta, SMS...
		else{
			echo '<div id="inv_messages_holder_noEmailing">';
			
			echo $lang['srv_inv_message_noemailing_text'];
					
			// Izbira nacina posiljanja (navadna posta, sms...) - prestavljeno pod nastavitve
			/*echo '<div id="inv_select_noMail_type">';				
			
			$noEmailingType = SurveySession::get('inv_noEmailing_type');
			echo '<span class="bold">'.$lang['srv_inv_message_noemailing_type'].':</span>';
		
			echo '<span class="inv_send_span spaceLeft"><input name="noMailType" id="noMailType1" value="0" type="radio" '.($noEmailingType == 0 ? ' checked="checked"' : '').' onClick="noEmailingType(\'0\');"><label for="noMailType1">' . $lang['srv_inv_message_noemailing_type1'] . '</label></span>';
			echo '<span class="inv_send_span spaceLeft"><input name="noMailType" id="noMailType2" value="1" type="radio" '.($noEmailingType == 1 ? ' checked="checked"' : '').' onClick="noEmailingType(\'1\');"><label for="noMailType2">' . $lang['srv_inv_message_noemailing_type2'] . '</label></span>';
			echo '<span class="inv_send_span spaceLeft"><input name="noMailType" id="noMailType3" value="2" type="radio" '.($noEmailingType == 2 ? ' checked="checked"' : '').' onClick="noEmailingType(\'2\');"><label for="noMailType3">' . $lang['srv_inv_message_noemailing_type3'] . '</label></span>';
			
			echo '</div>';*/

			// Gumb naprej
			/*echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['srv_invitation_forward'].'">';
			echo '<a class="ovalbutton ovalbutton_orange" href="'.$site_url.'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=send_message&noemailing=1"><span>'.$lang['srv_invitation_forward'];
			echo '</span></a></span>';
			
			echo '<br class="clr"/>';*/
			
			echo '</div>';
			
			
			echo '<div id="inv_messages_holder">';
			
			if ($this->checkDefaultMessage() == false) {
				echo '<span class="inv_error_note">';
				echo $lang['srv_invitation_note6'];
				echo '</span>';
			} else {
				$sql_string = "SELECT id, naslov, subject_text, body_text, reply_to, isdefault, comment, url FROM srv_invitations_messages WHERE ank_id = '$this->sid'";
				$sql_query = sisplet_query($sql_string);
				$array_messages = array();
				while ( list($id, $naslov, $subject_text, $body_text, $reply_to ,$isdefault, $comment, $url) = mysqli_fetch_row($sql_query) ) {
					$array_messages[$id] = array('id'=>$id, 'naslov' => $naslov, 'subject_text'=>$subject_text, 'body_text'=>$body_text, 'reply_to'=>$reply_to ,'isdefault'=>$isdefault, 'comment'=>$comment, 'url'=>$url);
					if ($isdefault == '1') {
						# če izbiramo profile in nismo postali še nobenga
						if ( $mid == null ) {
							$mid = $id;
						}
					}
				}
				$preview_message = $array_messages[$mid];
					
				echo '<div id="inv_messages_profiles_holder" class="floatLeft">';
				echo '<span>'.$lang['srv_invitation_message_choose'].':</span><br/>';
				echo '<div id="invitation_messages" >';
				echo '<ol>';
				foreach ($array_messages AS $_m => $message) {
					echo '<li mid="'.$message['id'].'" class="'
					.($message['id'] == $mid ? ' active' : '')
					.'" onclick="invChangeMessage(\''.$message['id'].'\')">';
					echo $message['naslov'];
					echo '</li>';
				}
				echo '</ol>';
				echo '</div>';	#invitation_messages
				echo '<br class="clr" />';
				if (count($array_messages) > 1) {
					echo '<span class="as_link" id="inv_del_msg_profile" onclick="invMessageDelete();" title="'.$lang['srv_inv_message_delete_profile'].'">'.$lang['srv_inv_message_delete_profile'].'</span><br/>';
				}
				echo '<span class="as_link" id="inv_ren_msg_profile" onclick="invShowMessageRename();" title="'.$lang['srv_inv_message_rename_profile'].'">'.$lang['srv_inv_message_rename_profile'].'</span>';
				{
					# polovimo podatke profila
					$sql_string = "SELECT sim.*, u.name, u.surname, e.name as ename, e.surname as esurname FROM srv_invitations_messages AS sim LEFT JOIN users AS u ON sim.uid = u.id LEFT JOIN users AS e ON sim.edit_uid = e.id WHERE sim.id = '".(int)$mid."'";
					$sql_query = sisplet_query($sql_string);
					$sql_row = mysqli_fetch_assoc($sql_query);

					$avtor = array();
					$edit = array();
					if (trim($sql_row['name'])) {
						$avtor[] = trim ($sql_row['name']);
					}
					if (trim($sql_row['surname'])) {
						$avtor[] = trim ($sql_row['surname']);
					}

					if ( count($avtor) > 0 ) {
						echo '<div class="gray">'.$lang['srv_invitation_author'].' '.implode(' ',$avtor).'</div>';
					}
					if ( count($avtor) > 0 ) {
						echo '<div class="gray" title="'.date("d.m.Y H:i:s",strtotime($sql_row['insert_time'])).'">'.$lang['srv_invitation_author_day'].' '.date("d.m.Y",strtotime($sql_row['insert_time'])).'</div>';
					}
					if (trim($sql_row['ename'])) {
						$edit[] = trim ($sql_row['ename']);
					}
					if (trim($sql_row['esurname'])) {
						$edit[] = trim ($sql_row['esurname']);
					}

					if ( count($edit) > 0 && $edit != $avtor) {
						echo '<div class="gray">'.$lang['srv_invitation_changed'].' '.implode(' ',$edit).'</div>';
					}
					if ($sql_row['insert_time'] != $sql_row['edit_time']) {
						echo '<div class="gray" title="'.date("d.m.Y H:i:s",strtotime($sql_row['edit_time'])).'">'.$lang['srv_invitation_changed_day'].' '.date("d.m.Y",strtotime($sql_row['insert_time'])).'</div>';
					}
						
					echo '<div class="gray" style="max-width:202px">'.$lang['srv_invitation_comment'].' '. trim ($sql_row['comment']).'</div>';
				}
					
				echo '</div>'; #inv_messages_profiles_holder
					
				$MA = new MailAdapter($this->sid, $type='invitation');
				# zlistamo seznam vseh sporočil
				# izpišemo primer besedila
				echo '<div id="inv_msg_preview_hld" class="floatLeft">';
				echo '<span class="h2 spaceRight floatLeft">'.$lang['srv_inv_message_draft_content_heading'].'</span> '.Help::display('srv_inv_message_title_noEmail');

				//echo '<span class="spaceRight floatRight"><a href="'.$site_url . 'admin/survey/index.php?anketa='.$this->sid.'&a=invitations&m=inv_server&show_back=true">'.$lang['srv_inv_message_draft_settings'].'</a></span>';
				echo '<br class="clr"/>';
				echo '<div id="inv_error_note" class="hidden"></div>';
				echo '<div id="inv_msg_preview">';
				echo '<table>';
				echo '<tr><th>'.$lang['srv_inv_message_draft_content_subject'].':</th>';
				echo '<td class="inv_bt">';
				echo '<input type="text" id="inv_message_subject" value="'.$preview_message['subject_text'].'" autocomplete="off">';
				echo '</td></tr>';
				echo '<tr><th>'.$lang['srv_inv_message_draft_content_body'].':</th>';
				echo '<td ><div class="msgBody">';
				echo '<textarea id="inv_message_body" name="inv_message_body" autocomplete="off">'.($preview_message['body_text']).'</textarea>';
				echo '</div>';
				?>
				<script type="text/javascript">
				create_inv_editor('inv_message_body', false);
				</script><?php 
				echo '</td></tr>';
				
				if (count($urls) > 0) {
					echo '<tr><th>'.$lang['srv_inv_message_draft_url'].'</th>';
					echo '<td>';
					echo '<select id="inv_message_url">';
					foreach ($urls AS $url) {
						$selected = '';
						if ($preview_message['url'] == '') {
							if ($preview_message['dc'] == true) {
								$selected = ' selected="selected"';
							}
						} else if ($preview_message['url'] == $url['url']) {
							$selected = ' selected="selected"';
						}
						echo '<option value="'.$url['url'].'"'.$selected.'>'.$url['name'].'</option>';
					}
					echo '</select>';
					echo '</td>';
					echo '</tr>';
				}
				echo '</table>';
				echo '</div>';
				
				echo '<br class="clr"/>';

				echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['srv_invitation_forward'].'"><a class="ovalbutton" href="#" onclick="inv_message_save_forward_noEmail(\''.$mid.'\'); return false;"><span>'.$lang['srv_invitation_forward'].'</span></a></span>';
				echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['srv_invitation_forward'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="if(inv_message_save_simple_noEmail(\''.$mid.'\')) { window.location.reload() }; return false;"><span>'.$lang['srv_inv_message_save'].'</span></a></span>';
				echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['srv_invitation_message_saveNew'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="inv_message_save_advanced(\''.$mid.'\'); return false;"><span>'.$lang['srv_invitation_message_saveNew'].'</span></a></span>';
				
				echo '</div>';
			}
			
			echo '</div>';
		}
		
		echo '<br class="clr"/>';	
	}

	function checkDefaultMessage() {
		global $lang, $global_user_id;

		$sql_query = sisplet_query("SELECT id FROM srv_invitations_messages WHERE ank_id = '$this->sid' AND isdefault='1'");

		$row = $this->surveySettings;
		
		# če privzeto sporočilo ne obstaja ga skreiramo
		if (mysqli_num_rows($sql_query) == 0 ) {

			Common::getInstance()->Init($this->sid);

			$reply_to = Common::getInstance()->getReplyToEmail();

			# poiščemo ime seznama za sporočila
			$naslov = $this->generateMessageName();

			$body_text = ($row['usercode_required'] == 1) ? $lang['srv_inv_message_body_text'].$lang['srv_inv_message_body_text_pass'] : $lang['srv_inv_message_body_text'];
			
			# skreiramo osnovno sporočilo				
			$sqlQuery = sisplet_query("INSERT INTO srv_invitations_messages (ank_id, naslov, subject_text, body_text, reply_to, isdefault, uid, insert_time, comment, edit_uid, edit_time, url ) VALUES ('$this->sid', '".$naslov."', '".$lang['srv_inv_message_subject_text']."', '".$body_text."', '".$reply_to."', '1', '".$global_user_id."', NOW(), '', '".$global_user_id."', NOW(), '')");
			if (!$sqlQuery) {
				$error = mysqli_error($GLOBALS['connect_db']);
			}

			$new_msg_id = mysqli_insert_id($GLOBALS['connect_db']);
				
			if ((int)$new_msg_id > 0) {
				return true;
			} 
            else {
				# insert ni uspel, in privzetega sporočila nimamo
				return false;
			}
		} 
        else {
			# če smo tu, imamo privzeto sporočilo
			return true;
		}
	}

	function makeDefaultMessage($mid = null) {
		# preverimo kater message je trenutno privzet
		$sql_string = "SELECT id FROM srv_invitations_messages WHERE ank_id = '$this->sid' AND isdefault='1'";
		$sql_query = sisplet_query($sql_string);
		list($def_id) = mysqli_fetch_row($sql_query);
		if ((int)$def_id > 0 && (int)$mid > 0 && (int)$def_id != (int)$mid) {
			# odstranimo privzet id in ga nastavimo na novo
			$sql_string = "UPDATE srv_invitations_messages SET isdefault = '0' WHERE ank_id = '$this->sid' AND isdefault='1'";
			$sqlQuery = sisplet_query($sql_string);
				
			# nastavimo na nov id
			$sql_string = "UPDATE srv_invitations_messages SET isdefault = '1' WHERE ank_id = '$this->sid' AND id='$mid'";
			$sqlQuery = sisplet_query($sql_string);
			sisplet_query("COMMIT");
		}
		$this->viewMessage($mid);
	}
	
	function makeDefaultFromPreview($mid = null) {
		# preverimo kater message je trenutno privzet
		$sql_string = "SELECT id FROM srv_invitations_messages WHERE ank_id = '$this->sid' AND isdefault='1'";
		$sql_query = sisplet_query($sql_string);
		list($def_id) = mysqli_fetch_row($sql_query);
		if ((int)$def_id > 0 && (int)$mid > 0 && (int)$def_id != (int)$mid) {
			# odstranimo privzet id in ga nastavimo na novo
			$sql_string = "UPDATE srv_invitations_messages SET isdefault = '0' WHERE ank_id = '$this->sid' AND isdefault='1'";
			$sqlQuery = sisplet_query($sql_string);
				
			# nastavimo na nov id
			$sql_string = "UPDATE srv_invitations_messages SET isdefault = '1' WHERE ank_id = '$this->sid' AND id='$mid'";
			$sqlQuery = sisplet_query($sql_string);
			sisplet_query("COMMIT");
		}
		$this->displayMessagePreview();
	}

	/**
	 * shranimo v obstoječ profil
	 */
	function save_message_simple() {
		global $lang, $global_user_id;
		$return = array('msg'=>'', 'error'=>'0');

		# shranimo vsebino
		#če so kakšne napake jih prikažemo v float oknu
		$mid = (int)$_POST['mid'];
		$subject = trim($_POST['subject']);
		$replyto = trim($_POST['replyto']);
		$body = trim($_POST['body']);
		$url = trim($_POST['url']);

		$newline = '';
		if ($replyto == null || $replyto == '' ) {
			$return['error'] = '1';
			$return['msg'] .= $newline.$lang['srv_inv_msg_field'].'"'.$lang['srv_inv_message_draft_content_from'].'"'.$lang['srv_inv_msg_3_not_empty'];
			$return['inv_message_replyto'] = '1';
			$newline= '<br/>';
		} else {
			if (!$this->validEmail($replyto)) {
				$return['error'] = '1';
				$return['msg'] .= $newline.$lang['srv_inv_msg_field'].'"'.$lang['srv_inv_message_draft_content_from'].'"'.$lang['srv_inv_msg_3_not_valid_email'];
				$return['inv_message_replyto'] = '1';
				$newline= '<br/>';
			}
				
		}
		if ($subject == null || $subject == '' ) {
			$return['error'] = '1';
			$return['msg'] .= $newline.$lang['srv_inv_msg_field'].'"'.$lang['srv_inv_message_draft_content_subject'].'"'.$lang['srv_inv_msg_3_not_empty'];
			$return['inv_message_subject'] = '1';
			$newline= '<br/>';
		}

		if ($body == null || $body == '' ) {
			$return['error'] = '1';
			$return['msg'] .= $newline.$lang['srv_inv_msg_field'].'"'.$lang['srv_inv_message_draft_content_body'].'"'.$lang['srv_inv_msg_3_not_empty'];
			$return['inv_message_body'] = '1';
			$newline= '<br/>';
		}
		# če ni napak shranim:
		if ( $return['error'] == '0') {
				
			if ((int)$mid > 0) {
				# shranjujemo v obstoječ msg
				$sql_string = "UPDATE srv_invitations_messages SET subject_text = '$subject', body_text = '$body', reply_to = '$replyto', edit_uid = '".$global_user_id."', edit_time = NOW(), url='".$url."' WHERE ank_id = '$this->sid' AND id='$mid'";
				$sqlQuery = sisplet_query($sql_string);
				$return['mid'] = $mid;

				if ( $sqlQuery != 1) {
					$return['error'] = '1';
					$return['msg'] .= $newline.$lang['srv_inv_msg_4'];
				}
			} else {
				# mid manjka
				$return['error'] = '1';
				$return['msg'] .= $newline.$lang['srv_inv_msg_4'];				$newline= '<br/>';
			}
			sisplet_query("COMMIT");
		}
		$return['msg'].=' '.$sql_string.$sql_insert;
		echo json_encode($return);
		exit;
	}
	
	/**
	 * shranimo v obstoječ profil
	 */
	function save_message_simple_noEmail() {
		global $lang, $global_user_id;
		$return = array('msg'=>'', 'error'=>'0');

		# shranimo vsebino
		#če so kakšne napake jih prikažemo v float oknu
		$mid = (int)$_POST['mid'];
		$subject = trim($_POST['subject']);
		$body = trim($_POST['body']);
		$url = trim($_POST['url']);

		$newline = '';
		if ($subject == null || $subject == '' ) {
			$return['error'] = '1';
			$return['msg'] .= $newline.$lang['srv_inv_msg_field'].'"'.$lang['srv_inv_message_draft_content_subject'].'"'.$lang['srv_inv_msg_3_not_empty'];
			$return['inv_message_subject'] = '1';
			$newline= '<br/>';
		}

		if ($body == null || $body == '' ) {
			$return['error'] = '1';
			$return['msg'] .= $newline.$lang['srv_inv_msg_field'].'"'.$lang['srv_inv_message_draft_content_body'].'"'.$lang['srv_inv_msg_3_not_empty'];
			$return['inv_message_body'] = '1';
			$newline= '<br/>';
		}
		# če ni napak shranim:
		if ( $return['error'] == '0') {
				
			if ((int)$mid > 0) {
				# shranjujemo v obstoječ msg
				$sql_string = "UPDATE srv_invitations_messages SET subject_text = '$subject', body_text = '$body', edit_uid = '".$global_user_id."', edit_time = NOW(), url='".$url."' WHERE ank_id = '$this->sid' AND id='$mid'";
				$sqlQuery = sisplet_query($sql_string);
				$return['mid'] = $mid;

				if ( $sqlQuery != 1) {
					$return['error'] = '1';
					$return['msg'] .= $newline.$lang['srv_inv_msg_4'];
				}
			} else {
				# mid manjka
				$return['error'] = '1';
				$return['msg'] .= $newline.$lang['srv_inv_msg_4'];				$newline= '<br/>';
			}
			sisplet_query("COMMIT");
		}
		$return['msg'].=' '.$sql_string.$sql_insert;
		echo json_encode($return);
		exit;
	}

	function messageSaveforward() {
		global $lang, $global_user_id;
		$return = array('msg'=>'', 'error'=>'0');

		$mid = (int)$POST['mid'];

		#če so kakšne napake jih prikažemo v float oknu
		$subject = trim($_POST['subject']);
		$replyto = trim($_POST['replyto']);
		$body = trim($_POST['body']);
		$url = trim($_POST['url']);

		$newline = '';
		if ($replyto == null || $replyto == '' ) {
			$return['error'] = '1';
			$return['msg'] .= $newline.$lang['srv_inv_msg_field'].'"'.$lang['srv_inv_message_draft_content_from'].'"'.$lang['srv_inv_msg_3_not_empty'];
			$return['inv_message_replyto'] = '1';
			$newline= '<br/>';
		} else {
			if (!$this->validEmail($replyto)) {
				$return['error'] = '1';
				$return['msg'] .= $newline.$lang['srv_inv_msg_field'].'"'.$lang['srv_inv_message_draft_content_from'].'"'.$lang['srv_inv_msg_3_not_valid_email'];
				$return['inv_message_replyto'] = '1';
				$newline= '<br/>';
			}
		}
		if ($subject == null || $subject == '' ) {
			$return['error'] = '1';
			$return['msg'] .= $newline.$lang['srv_inv_msg_field'].'"'.$lang['srv_inv_message_draft_content_subject'].'"'.$lang['srv_inv_msg_3_not_empty'];
			$return['inv_message_subject'] = '1';
			$newline= '<br/>';
		}

		if ($body == null || $body == '' ) {
			$return['error'] = '1';
			$return['msg'] .= $newline.$lang['srv_inv_msg_field'].'"'.$lang['srv_inv_message_draft_content_body'].'"'.$lang['srv_inv_msg_3_not_empty'];
			$return['inv_message_body'] = '1';
			$newline= '<br/>';
		}
		# če ni napak shranim:
		if ( $return['error'] == '0') {
				
			# preverimo ali je kakšna sprememba, če je sprememba shranimo v nov profil
			$sql_string = "SELECT subject_text, body_text, reply_to, url FROM srv_invitations_messages WHERE ank_id = '$this->sid' AND id='".(int)$_POST['mid']."'";
		$sql_query = sisplet_query($sql_string);
		list($old_subject, $old_body_text, $old_reply_to, $old_url) = mysqli_fetch_row($sql_query);
			
		if ($old_subject != $subject || $old_body_text != $body || $old_reply_to != $replyto || $old_url != $url) {

			# shranjujemo v novo sporočilo
			$naslov = $this->generateMessageName();
			$sql_insert = "INSERT INTO srv_invitations_messages (ank_id, naslov, subject_text, body_text, reply_to, isdefault, uid, insert_time, comment, edit_uid, edit_time, url ) ".
					"VALUES ('$this->sid', '$naslov', '$subject', '$body', '$replyto', '1', '$global_user_id', NOW(), '$comment', '$global_user_id', NOW(), '$url')";
			$sqlQuery = sisplet_query($sql_insert);

			$newID = mysqli_insert_id($GLOBALS['connect_db']);
			if ($newID > 0) {

				$return['mid'] = $newID;
					
				# popravmo še isdefault pri starem zapisz
				$sql_string = "UPDATE srv_invitations_messages SET isdefault = '0' WHERE ank_id = '$this->sid' AND id != '$newID'";
				$sqlQuery = sisplet_query($sql_string);
					
			} else {
				$return['error'] = '1';
				$return['msg'] .= $newline.$lang['srv_inv_msg_4'];
			}

		}
		sisplet_query("COMMIT");
		}
		$return['msg'].=' '.$sql_string.$sql_insert;
		echo json_encode($return);
		exit;
	}

	function messageSaveforwardNoEmail() {
		global $lang, $global_user_id;
		$return = array('msg'=>'', 'error'=>'0');

		$mid = (int)$POST['mid'];

		#če so kakšne napake jih prikažemo v float oknu
		$subject = trim($_POST['subject']);
		$body = trim($_POST['body']);
		$url = trim($_POST['url']);

		$newline = '';

		if ($subject == null || $subject == '' ) {
			$return['error'] = '1';
			$return['msg'] .= $newline.$lang['srv_inv_msg_field'].'"'.$lang['srv_inv_message_draft_content_subject'].'"'.$lang['srv_inv_msg_3_not_empty'];
			$return['inv_message_subject'] = '1';
			$newline= '<br/>';
		}

		if ($body == null || $body == '' ) {
			$return['error'] = '1';
			$return['msg'] .= $newline.$lang['srv_inv_msg_field'].'"'.$lang['srv_inv_message_draft_content_body'].'"'.$lang['srv_inv_msg_3_not_empty'];
			$return['inv_message_body'] = '1';
			$newline= '<br/>';
		}
		# če ni napak shranim:
		if ( $return['error'] == '0') {
				
			# preverimo ali je kakšna sprememba, če je sprememba shranimo v nov profil
			$sql_string = "SELECT subject_text, body_text, reply_to, url FROM srv_invitations_messages WHERE ank_id = '$this->sid' AND id='".(int)$_POST['mid']."'";
		$sql_query = sisplet_query($sql_string);
		list($old_subject, $old_body_text, $old_reply_to, $old_url) = mysqli_fetch_row($sql_query);
			
		if ($old_subject != $subject || $old_body_text != $body || $old_url != $url) {

			# shranjujemo v novo sporočilo
			$naslov = $this->generateMessageName();
			$sql_insert = "INSERT INTO srv_invitations_messages (ank_id, naslov, subject_text, body_text, isdefault, uid, insert_time, comment, edit_uid, edit_time, url ) ".
					"VALUES ('$this->sid', '$naslov', '$subject', '$body', '1', '$global_user_id', NOW(), '$comment', '$global_user_id', NOW(), '$url')";
			$sqlQuery = sisplet_query($sql_insert);

			$newID = mysqli_insert_id($GLOBALS['connect_db']);
			if ($newID > 0) {

				$return['mid'] = $newID;
					
				# popravmo še isdefault pri starem zapisz
				$sql_string = "UPDATE srv_invitations_messages SET isdefault = '0' WHERE ank_id = '$this->sid' AND id != '$newID'";
				$sqlQuery = sisplet_query($sql_string);
					
			} else {
				$return['error'] = '1';
				$return['msg'] .= $newline.$lang['srv_inv_msg_4'];
			}

		}
		sisplet_query("COMMIT");
		}
		$return['msg'].=' '.$sql_string.$sql_insert;
		echo json_encode($return);
		exit;
	}
	
	function addUrl($what) {
		global $site_url;

		if ($what == null || trim($what) == '') {
			$what = 'add_recipients_view';
		}
		$url = $site_url . 'admin/survey/index.php?anketa='.$this->sid.'&amp;a='.A_INVITATIONS.'&amp;m='.$what;

		return $url;
	}

	/**
	 Validate an email address.
	 */
	function validEmail($email = null) {
		return Common::getInstance()->validEmail($email);
	}

	function displayRecipentsErrors($result) {
		global $lang;
		$valid_recipiens = is_array($result['valid_recipiens']) ? $result['valid_recipiens'] : array();
		$invalid_password = is_array($result['invalid_password']) ? $result['invalid_password'] : array();
		$invalid_email = is_array($result['invalid_email']) ? $result['invalid_email'] :array();
		$duplicate_email = is_array($result['duplicate_email']) ? $result['duplicate_email'] : array();
		$unsubscribed = is_array($result['unsubscribed']) ? $result['unsubscribed'] : array();
			
		# dodani so bili nekateri uporabniki
		if (count($valid_recipiens) > 0) {
			echo '<div id="inv_recipiens_added">';
			echo $lang['srv_inv_recipiens_add_success_cnt'].'<span class="inv_count"><span class="as_link" onclick="$(\'#invRecipiensList1\').toggle();">'. count($valid_recipiens).'</span></span>';
			echo '<br />';
			echo '<div id="invRecipiensList1" class="displayNone"><br/>';
				
			foreach ($valid_recipiens AS $fields) {
				if (is_array($fields)) {
					
					$text = '';			
					$text .= mb_strtolower($fields['inv_field_email']);
					
					if (trim($fields['inv_field_firstname']) != '') {
						$text .= ', '.str_replace("|~|", ",", mb_strtolower($fields['inv_field_firstname'], 'UTF-8'));
					}
					if (trim($fields['inv_field_lastname']) != '') {
						$text .= ', '.str_replace("|~|", ",", mb_strtolower($fields['inv_field_lastname'], 'UTF-8'));
					}
					
					echo $text;
				} 
				else {
					echo mb_strtolower($fields, 'UTF-8');
				}
				echo '<br/>';
			}
			
			echo '</div>';
			echo '</div>';
		}
			
		if ( (count($invalid_password) + count($invalid_email) + count($duplicate_email) + count($unsubscribed))  > 0  ) {
			echo '<div id="inv_recipiens_rejected">';

			# ni veljavnih uporabnikov
			if (count($valid_recipiens) == 0 ) {
				echo '<span class="red bold">'.$lang['srv_inv_recipiens_add_error'].'</span><br/>';
			}
			# zavrnjeni uporabniki
			if (count($unsubscribed)> 0) {
				echo $lang['srv_inv_recipiens_add_optedout_cnt'].'<span class="inv_count"><span class="as_link" onclick="$(\'#invRecipiensList2\').toggle();">'.count($unsubscribed).'</span></span>';
				echo '<br />';
				echo '<div id="invRecipiensList2" class="displayNone">';
				foreach ($unsubscribed AS $fields) {
					if (is_array($fields)) {
						echo strtolower($fields['inv_field_email']);
						if (trim($fields['inv_field_firstname']) != '') {
							echo ', '.$fields['inv_field_firstname'];
						}
						if (trim($fields['inv_field_lastname']) != '') {
							echo ', '.$fields['inv_field_lastname'];
						}
					} else {
						echo $fields;
					}
					echo '<br/>';
				}
				echo '</div>';

			}

			# podvojeni uporabniki
			if (count($duplicate_email)> 0) {
				echo $lang['srv_inv_recipiens_add_exist_cnt'].'<span class="inv_count"><span class="as_link" onclick="$(\'#invRecipiensList3\').toggle();">'.count($duplicate_email).'</span></span>';
				echo '<br />';
				echo '<div id="invRecipiensList3" class="displayNone">';
				foreach ($duplicate_email AS $fields) {
					if (is_array($fields)) {
						echo strtolower($fields['inv_field_email']);
						if (trim($fields['inv_field_firstname']) != '') {
							echo ', '.$fields['inv_field_firstname'];
						}
						if (trim($fields['inv_field_lastname']) != '') {
							echo ', '.$fields['inv_field_lastname'];
						}
					} else {
						echo strtolower($fields);
					}
					echo '<br/>';
				}
				echo '</div>';
			}
				
			# neveljaven e-mail
			if (count($invalid_email) > 0) {
				echo $lang['srv_inv_recipiens_add_invalid_cnt'].'<span class="inv_count"><span class="as_link" onclick="$(\'#invRecipiensList4\').toggle();">'.count($invalid_email).'!</span></span>';
				echo '<br />';
				echo '<div id="invRecipiensList4" class="displayNone">';
				foreach ($invalid_email AS $fields) {
					if (is_array($fields)) {
						echo str_replace("|~|", ",", mb_strtolower($fields['inv_field_email'], 'UTF-8'));
						if (trim($fields['inv_field_firstname']) != '') {
							echo ', '.str_replace("|~|", ",", $fields['inv_field_firstname']);
						}
						if (trim($fields['inv_field_lastname']) != '') {
							echo ', '.str_replace("|~|", ",", $fields['inv_field_lastname']);
						}
					} else {
						echo str_replace("|~|", ",", mb_strtolower($fields, 'UTF-8'));
					}
					echo '<br/>';
				}
				echo '</div>';
			}
				
			# neveljavena gesla
			if (count($invalid_password) > 0) {
				echo $lang['srv_inv_recipiens_add_invalid_password_cnt'].'<span class="inv_count"><span class="as_link" onclick="$(\'#invRecipiensList5\').toggle();">'.count($invalid_password).'!</span></span>';
				echo '<br />';
				echo '<div id="invRecipiensList5" class="displayNone">';
				foreach ($invalid_password AS $fields) {
					if (is_array($fields)) {
						echo strtolower($fields['inv_field_email']);
						if (trim($fields['inv_field_firstname']) != '') {
							echo ', '.$fields['inv_field_firstname'];
						}
						if (trim($fields['inv_field_lastname']) != '') {
							echo ', '.$fields['inv_field_lastname'];
						}
					} else {
						echo strtolower($fields);
					}
					echo '<br/>';
				}
				echo '</div>';
			}
				
			if (count($invalid_email) > 0 || count($invalid_password) > 0) {
				//echo ''.$lang['srv_inv_recipiens_add_invalid_note'];
				echo '<br /><span class="red">'.$lang['srv_inv_recipiens_add_invalid_note2'].'!</span>';
				echo Help::display('srv_inv_recipiens_add_invalid_note');
			}
			echo '</div>';
				
			return array_merge($unsubscribed, $duplicate_email, $invalid_email, $invalid_password) ;
		}
		return array();
	}

	function displayNavigation() {
		global $lang, $admin_type, $global_user_id, $app_settings;
		
		$isEmail = (int)SurveyInfo::getInstance()->checkSurveyModule('email');
		
		$userAccess = UserAccess::getInstance($global_user_id);
        
        #če ni dostopa mu ne prikažemo linkov
		if ((int)$isEmail > 0  && $userAccess->checkUserAccess($what='invitations')) {
        } 
        else {
			return false;
		}
				
		if (!isset($_POST['noNavi']) || (isset($_POST['noNavi']) && $_POST['noNavi'] != 'true')) {
			$_sub_action = $_GET['m'];

			if ($_sub_action == null && $_GET['t'] == 'invitations') {
				if ($_GET['a'] == 'use_recipients_list') {
					$_sub_action = 'add_recipients_view';
				}
				if ($_GET['a'] == 'view_message'
						|| $_GET['a'] == 'make_default'
						|| $_GET['a'] == 'delete_msg_profile' ) {
					$_sub_action = 'view_message';
				}
				if ($_GET['a'] == 'delete_recipient'
						|| $_GET['a'] == 'add_recipients'
						|| $_GET['a'] == 'view_recipients'
						|| $_GET['a'] == 'export_recipients'
						|| $_GET['a'] == 'add_checked_users_to_database'
						|| $_GET['a'] == 'setAdvancedCondition'
						|| $_GET['a'] == 'recipientsAddForward') {
					$_sub_action = 'view_recipients';
				}
				if ($_GET['a'] == 'view_archive' ) {
					$_sub_action = 'view_archive';
				}
				if ($_GET['a'] == 'send_mail') {
					$_sub_action = 'send_message';
					#$_sub_action = 'view_archive';
				}
			} else if( $_sub_action == 'send_mail') {
				#$_sub_action = 'view_archive';
				$_sub_action = 'send_message';
			}

			$active_step[] = array(1=>'',2=>'',3=>'',4=>'',5=>'',6=>'',7=>'',8=>'');
			switch ($_sub_action) {
				case 'inv_settings':
					$active_step['1'] = ' active';
					break;
				case 'add_recipients_view':
					$active_step['2'] = ' active';
					break;
				case 'view_recipients':
					$active_step['3'] = ' active';
					break;
				case 'view_message':
					$active_step['4'] = ' active';
					break;
				case 'send_message':
					$active_step['5'] = ' active';
					break;
				case 'view_archive':
					$active_step['6'] = ' active';
					break;
				case 'inv_lists':
					$active_step['7'] = ' active';
					break;
				case 'inv_server':
					$active_step['8'] = ' active';
					break;
				case 'inv_status':
					$active_step['9'] = ' active';
					break;

				default:
					$sql = sisplet_query("SELECT EXISTS (SELECT 1 FROM srv_invitations_archive WHERE ank_id='".$this->sid."')");
					$row = mysqli_fetch_array($sql);
					
					// Ce imamo ze posiljanje je default stran "Pregled"
					if($row[0] == 1)
						$active_step['9'] = ' active';
					// Drugace je default stran "Nastavitve"
					else
						$active_step['1'] = ' active';
					break;
			}
			if (SurveyInfo::getInstance()->checkSurveyModule('email') || SurveyInfo::getInstance()->checkSurveyModule('phone')) {
				$disabled = false;
				$css_disabled = '';
			} else {
				$disabled = true;
				$css_disabled = '_disabled';
			}
		
			if($isEmail) {
				#$spaceChar = '&#187;';
				$spaceChar = '&nbsp;';
				echo '<div id="inv_step_nav">';
				echo '<div class="inv_step'.$active_step[1].'">';
				echo '<a href="'.$this->addUrl('inv_settings').'">';
				//echo '<span class="circle">1</span>';
				echo '<span class="label">'.$lang['srv_inv_nav_email_settings'].'</span>';
				echo '</a>';
				echo '</div>';
				echo '</div>';
				
				#space
				echo '<div class="inv_space">&nbsp;</div>';
				echo '<div id="inv_step_nav" class="yellow">';
				
				$class_yellow = ' yellow';
					
				#navigacija
				echo '<div class="inv_step'.$class_yellow.$css_disabled.$active_step[2].'">';
				if ($disabled == false) {
					echo '<a href="'.$this->addUrl('add_recipients_view').'">';
				}
				echo '<span class="circle">1</span>';
				echo '<span class="label">'.$lang['srv_inv_nav_add_recipients'].'</span>';
				if ($disabled == false) {
					echo '</a>';
				}
				echo '</div>';
			
				echo '<div class="inv_step_space'.$class_yellow.$css_disabled.'">'.$spaceChar.'</div>';
				echo '<div class="inv_step'.$class_yellow.$css_disabled.$active_step[3].'">';
				if ($disabled == false) {
					echo '<a href="'.$this->addUrl('view_recipients').'">';
				}
				echo '<span class="circle">2</span>';
				echo '<span class="label">'.$lang['srv_inv_nav_edit_recipiens'].'</span>';
				if ($disabled == false) {
					echo '</a>';
				}
				echo '</div>';
				echo '<div class="inv_step_space'.$class_yellow.$css_disabled.'">'.$spaceChar.'</div>';
				if ($disabled == false) {
					echo '<a href="'.$this->addUrl('view_message').'">';
				}
				echo '<div class="inv_step'.$class_yellow.$css_disabled.$active_step[4].'">';
				echo '<span class="circle">3</span>';
				echo '<span class="label" >'.$lang['srv_inv_nav_edit_message'].'</span>';
				if ($disabled == false) {
					echo '</a>';
				}
				echo '</div>';
				echo '<div class="inv_step_space'.$class_yellow.$css_disabled.'">'.$spaceChar.'</div>';
				echo '<div class="inv_step'.$class_yellow.$css_disabled.$active_step[5].'">';
				if ($disabled == false) {
					echo '<a href="'.$this->addUrl('send_message').'">';
				}
				echo '<span class="circle">4</span>';
				echo '<span class="label" >'.$lang['srv_inv_nav_send_message'].'</span>';
				if ($disabled == false) {
					echo '</a>';
				}
				echo '</div>';
				
				echo '</div>';
				
				
				// Pregled
				#space
				echo '<div class="inv_space">&nbsp;</div>';
				
				echo '<div id="inv_step_nav">';
				echo '<div class="inv_step'.$active_step[9].'">';
				echo '<a href="'.$this->addUrl('inv_status').'">';
				//echo '<span class="circle">1</span>';
				echo '<span class="label">'.$lang['srv_inv_nav_email_review'].'</span>';
				echo '</a>';
				echo '</div>';
				echo '</div>';
				
				
				// Seznami
				#space
				echo '<div class="inv_space">&nbsp;</div>';
				
				echo '<div id="inv_step_nav">';
				echo '<div class="inv_step'.$active_step[7].'">';
				echo '<a href="'.$this->addUrl('inv_lists').'">';
				//echo '<span class="circle">1</span>';
				echo '<span class="label">'.$lang['srv_inv_nav_email_lists'].'</span>';
				echo '</a>';
				echo '</div>';
				echo '</div>';

				
				echo '<br class="clr" />';
				echo '<br class="clr" />';
			}

		}
		echo '<input type="hidden" id="surveyConditionPage" value="invitations">';
	}

	function sendMessage() {
		global $lang, $site_url;
		
		// Ali posiljamo maile ali ne
		$noEmailing = SurveySession::get('inv_noEmailing');
		
		$row = $this->surveySettings;
		
		# Pripravimo izbor komu lahko pošiljamo
		echo '<h2 style="margin-left: 15px; color:#333 !important;">';
		
        // Text s podatki o nastavitvah posiljanja
		$settings_text = '<span class="bold spaceRight">'.$lang['srv_inv_message_type'].':</span>';
		
		$individual = (int)$this->surveySettings['individual_invitation'];
		if($individual == 0){
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_individual_0'].'</span>';
		}		
		else{
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_individual_1'].'</span>';
		}
		
		$settings_text .= ' - ';

		if($noEmailing == 0){
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_noEmail_0'].'</span>';
		}		
		else{
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_noEmail_1'].'</span>';
		}
		
		$settings_text .= ' - ';
		
		if($row['usercode_required'] == 0 && $individual != 0){
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_URL_0'];
			$settings_text .= ' ('.$lang['srv_inv_settings_code_0'].')</span>';
		}		
		else{
			$settings_text .= '<span class="spaceLeft spaceRight">'.$lang['srv_inv_settings_URL_1'];
			
			if($row['usercode_skip'] == 1 || $individual == 0){
				$settings_text .= ' ('.$lang['srv_inv_settings_code_2'].')</span>';
			}		
			else{
				$settings_text .= ' ('.$lang['srv_inv_settings_code_1'].')</span>';
			}
		}
			
		$settings_text .= '<span class="spaceLeft"> <a href="'.$site_url . 'admin/survey/index.php?anketa='.$this->sid.'&a=invitations&m=inv_settings">'.$lang['edit4'].'</a></span>';
			
		echo $settings_text;
		echo '</h2>';
		
		
		if ($this->checkDefaultMessage() == false) {
			echo '<span class="inv_error_note">';
			echo $lang['srv_invitation_note6'];
			echo '</span>';
			exit();
		}
				
		echo '<div id="inv_send_mail">';
		
		# damo v tabelo zaradi prilagajanja oblike levo/desno
		echo '<table><tr>';
        
        // Pri volitvah vedno posiljamo samo tistim, katerim se nismo poslali
        if(!SurveyInfo::getInstance()->checkSurveyModule('voting')){

            echo '<td>';
            
            echo '<div>';

            echo $lang['srv_inv_send_who_database'].'<br/>';
            echo '<span class="floatLeft">';
            echo '<label><input type="radio" name="mailsource" value="0" onclick="mailToSourceChange();" checked="checked">'.$lang['srv_inv_send_who_all_units'].'</label>';
            echo '</span>';
                
            $this->advancedCondition();
            echo '<br class="clr"/>';
            
            echo '<label><input type="radio" name="mailsource" value="1" onclick="mailToSourceChange();">'.$lang['srv_inv_send_who_archive'].'</label>';
            echo '<br/><label><input type="radio" name="mailsource" value="2" onclick="mailToSourceChange();">'.$lang['srv_inv_send_who_lists'].'</label>';
            echo '<br/>';

            echo '<div id="inv_select_mail_to_source_lists">';
            $this->displayMailToSourceLists((int)$_POST['source_type']);
            echo '</div>'; #id="inv_select_mail_to_source_lists"

            echo '</div>';

            # polovimo sporočilo in prejemnike
            $sql_query_m = sisplet_query("SELECT id, naslov, subject_text, body_text, reply_to, isdefault, comment, url FROM srv_invitations_messages WHERE ank_id = '$this->sid' AND isdefault='1'");
            if (mysqli_num_rows($sql_query_m) > 0 ) {
                $preview_message = mysqli_fetch_assoc($sql_query_m);
            } 
            else {
                #nimamo še vsebine sporočila skreiramo privzeto.
                echo '<span class="inv_error_note">';
                echo $lang['srv_invitation_note6'];
                echo '</span>';

                exit();
            }
            
            echo '</td>';
        }
        
        echo '<td>';
		
		// Ce posiljamo preko navadne poste ali smsov, nimamo sporocila
		if($noEmailing == 0){
			echo '<input type="hidden" name="noMailing" value="0" />';
			
			echo '<div id="inv_select_mail_preview">';
			$this->displayMessagePreview();
			echo '</div>'; // inv_select_mail_preview
			echo '<br class="clr"/>';
			
			echo '<div id="inv_select_mail_to_respondents">';
			$this->selectSendTo();
			echo '</div>'; // inv_select_mail_to_respondents
		}
		else{		
			echo '<input type="hidden" name="noMailing" value="1" />';
			$noEmailingType = SurveySession::get('inv_noEmailing_type');
			echo '<input type="hidden" name="noMailingType" value="'.$noEmailingType.'" />';
			
			echo '<div id="inv_select_mail_to_respondents">';
			$this->selectSendToNoEmailing();
			echo '</div>'; // inv_select_mail_to_respondents	
		}
		
		echo '</td>';

        echo '</tr></table>';

		echo '</div>'; //inv_send_mail
	}

	function displayMailToSourceLists($source_type) {
        global $lang, $site_url;
        
        $canShowSubOption = false;
        
		echo '<p style="margin-left:25px;">';
		echo $lang['srv_inv_send_who_create1'].'<a href="'.$site_url.'admin/survey/index.php?anketa='.$this->sid.'&a=invitations&m=inv_lists">'.$lang['srv_inv_send_who_create2'].'</a><br/>';
        
        if ((int)$source_type == 0) {
			# vsi respondenti v bazi
			echo $lang['srv_inv_send_who_database_note'];
			$canShowSubOption = true;
        } 
        elseif ((int)$source_type == 1) {
			# Arhivi pošiljanja
				
			# poiščemo arhiv mailingov
			# zloopamo še po posameznih pošiljanjih
			$sql_string_arc = "SELECT sia.*, DATE_FORMAT(sia.date_send,'%d.%m.%Y, %T') AS ds,  u.name, u.surname, u.email FROM srv_invitations_archive AS sia LEFT JOIN users AS u ON sia.uid = u.id WHERE ank_id = '".$this->sid."'  ORDER BY sia.date_send ASC;";
			$sql_query_arc = sisplet_query($sql_string_arc);
				
			if (mysqli_num_rows($sql_query_arc) > 0) {
                
                $canShowSubOption = true;
                
                echo $lang['srv_inv_send_who_archive_note'];
				echo '<table id="tbl_recipients_source_list">';
				echo '<tr>';
				echo '<th class="tbl_icon">&nbsp;</th>';
				echo '<th>'.$lang['srv_inv_send_who_table_address'].'</th>';
				echo '<th>'.$lang['srv_inv_send_who_table_respondents'].'</th>';
				echo '<th>'.$lang['srv_inv_send_who_table_date_create'].'</th>';
				echo '</tr>';
                
                while ($row_arc = mysqli_fetch_assoc($sql_query_arc)) {
					echo '<tr>';
					echo '<td class="tbl_icon"><input type="checkbox" name="mailsource_lists[]" onchange="mailToSourceCheckboxChange();" value="'.$row_arc['id'].'"></td>';
					echo '<td>'.$row_arc['naslov'].'</td>';
					echo '<td class="anl_ac">'.((int)$row_arc['cnt_succsess']+(int)$row_arc['cnt_error']).'</td>';
					echo '<td>'.$row_arc['ds'].'</td>';
					echo '</tr>';
                }
                
				echo '</table>';
            } 
            else{
				echo $lang['srv_inv_send_who_archive_no_archive'];
			}	
        } 
        elseif ((int)$source_type == 2) {
			# seznami respondentov
				
			# zloopamo skozi posamezne sezname respondentov
			$sql_string_arc ="";
			$sql_query_arc = sisplet_query("SELECT list_id as id, COUNT(*) as cnt_succsess, list_id, sirp.name as naslov, DATE_FORMAT(sirp.insert_time,'%d.%m.%Y, %T') AS ds  
                                                FROM srv_invitations_recipients AS sir 
                                                    LEFT JOIN srv_invitations_recipients_profiles AS sirp 
                                                    ON sir.list_id = sirp.pid 
                                                WHERE ank_id ='".$this->sid."' AND sir.deleted ='0' group BY list_id
                                        ");
            
            if (mysqli_num_rows($sql_query_arc) > 0) {

                $canShowSubOption = true;
                
                echo $lang['srv_inv_send_who_all_units_note'];
                
				echo '<table id="tbl_recipients_source_list">';
				echo '<tr>';
				echo '<th class="tbl_icon">&nbsp;</th>';
				echo '<th>'.$lang['srv_inv_send_who_table_list_name'].'</th>';
				echo '<th>'.$lang['srv_inv_send_who_table_respondents'].'</th>';
				echo '<th>'.$lang['srv_inv_send_who_table_date_create'].'</th>';
				echo '</tr>';
				while ($row_arc = mysqli_fetch_assoc($sql_query_arc)) {
                    
                    if ($row_arc['id'] > 0 && $row_arc['naslov'] == '') {
						# če ni imena in je id < 0 je bil izbrisan
                    } 
                    else {
						if ($row_arc['id'] > 0) {
							if ($row_arc['naslov'] != '') {
								$_naslov = $row_arc['naslov'];
                            } 
                            else {
								$_naslov = $lang['srv_inv_send_who_table_list_deleted'];
							}
                        } 
                        else if ($row_arc['id'] == 0) {
							$_naslov = $lang['srv_inv_send_who_table_list_temporary'];
                        } 
                        else if ($row_arc['id'] < 0) {
							$_naslov = $lang['srv_inv_send_who_table_list_noname'];
                        }
                        
						echo '<tr>';
						echo '<td class="tbl_icon"><input type="checkbox" name="mailsource_lists[]"  onchange="mailToSourceCheckboxChange();" value="'.$row_arc['id'].'"></td>';
						echo '<td>'.$_naslov.'</td>';
						echo '<td class="anl_ac">'.$row_arc['cnt_succsess'].'</td>';
						echo '<td>'.$row_arc['ds'].'</td>';
						echo '</tr>';
					}
				}
				echo '</table>';
            } 
            else{
				echo $lang['srv_inv_send_who_no_lists'];
			}
		}
		echo '</p>';

		if ($canShowSubOption == true) {
            
			echo '<span id="inv_select_mail_to">';
			echo '<span class="bold">'.$lang['srv_inv_send_note'].'</span><br/>';
			echo '<span class="inv_send_span"><input name="mailto" id="mailto0" value="0" type="radio" checked="checked" onclick="mailToRadioChange();"><label for="mailto0">' . $lang['srv_inv_send_recipients0'] . '</label></span><br/>';
			echo '<span class="inv_send_span"><input name="mailto" id="mailto1" value="1" type="radio" onclick="mailToRadioChange();"><label for="mailto1">' . $lang['srv_inv_send_recipients1'] . '</label></span><br/>';
			echo '<span class="inv_send_span"><input name="mailto" id="mailto2" value="2" type="radio" onclick="mailToRadioChange();"><label for="mailto2">' . $lang['srv_inv_send_recipients2'] . '</label></span><br/>';
			echo '<span class="inv_send_span"><input name="mailto" id="mailto3" value="3 " type="radio" onclick="mailToRadioChange();"><label for="mailto3">' . $lang['srv_inv_send_recipients3'] . '</label></span><br/>';
				
			echo '<span class="inv_send_span">'.$lang['srv_invitation_send_advanced'].'</span><br/>';
			echo '<span class="inv_send_span"><input name="mailto" id="mailto4" value="4 " type="radio" onclick="mailToRadioChange();"><label for="mailto4">' . $lang['srv_inv_send_recipients4'] . '</label></span><br/>';
			echo '<div id="inv_send_advanced_div" >';
			echo '<span class="inv_send_span shift gray"><label><input name="mailto_status[]" value="0" type="checkbox" id="mailto_status_0" onclick="mailTocheCheckboxChange();" disabled="disabled">0 - ' . $lang['srv_userstatus_0'] . '</label></span><br/>';
			echo '<span class="inv_send_span shift gray"><label><input name="mailto_status[]" value="1" type="checkbox" id="mailto_status_1" onclick="mailTocheCheckboxChange();" disabled="disabled">1 - ' . $lang['srv_userstatus_1'] . '</label></span><br/>';
			echo '<span class="inv_send_span shift gray"><label><input name="mailto_status[]" value="2" type="checkbox" id="mailto_status_2" onclick="mailTocheCheckboxChange();" disabled="disabled">2 - ' . $lang['srv_userstatus_2'] . '</label></span><br/>';
			echo '<span class="inv_send_span shift gray"><label><input name="mailto_status[]" value="3" type="checkbox" id="mailto_status_3" onclick="mailTocheCheckboxChange();" disabled="disabled">3 - ' . $lang['srv_userstatus_3'] . '</label></span><br/>';
			echo '<span class="inv_send_span shift gray"><label><input name="mailto_status[]" value="4" type="checkbox" id="mailto_status_4" onclick="mailTocheCheckboxChange();" disabled="disabled">4 - ' . $lang['srv_userstatus_4'] . '</label></span><br/>';
			echo '<span class="inv_send_span shift gray"><label><input name="mailto_status[]" value="5" type="checkbox" id="mailto_status_5" onclick="mailTocheCheckboxChange();" disabled="disabled">5 - ' . $lang['srv_userstatus_5'] . '</label></span><br/>';
			echo '<span class="inv_send_span shift gray"><label><input name="mailto_status[]" value="6" type="checkbox" id="mailto_status_6" onclick="mailTocheCheckboxChange();" disabled="disabled">6 - ' . $lang['srv_userstatus_6'] . '</label></span><br/>';
			echo '</div>';
			echo '</span>'; // inv_select_mail_to
		}
	}

	function selectSendTo($send_type = 0, $checkboxes = array()) {
		global $lang, $site_url, $global_user_id;

		if ((int)$this->surveySettings['active'] !== 1) {

			$activity = SurveyInfo:: getSurveyActivity();
			$_last_active = end($activity);

			echo $lang['srv_inv_error9'];
			echo '<a href="#" onclick="anketa_active(\'' . $this->sid . '\',\'' . (int)$this->surveySettings['active'] . '\'); return false;" title="' . $lang['srv_anketa_noactive'] . '">';
			if ((int)$_last_active > 0 ) {
				# anketa je zaključena
				echo ' <span id="srv_inactive">'.$lang['srv_inv_activate_survey_here'].'</span>';
			} else {
				# anketa je neaktivna
				echo ' <span id="srv_inactive">'.$lang['srv_inv_activate_survey_here'].'</span>';
			}
			echo '</a>';
        } 
        # anketa je aktivna lahko pošiljamo
        else {

            // Preverimo ce je vklopljen modul za volitve - obvestilo, da ni naknadnega posiljanja
            if(SurveyInfo::getInstance()->checkSurveyModule('voting')){
                echo '<p class="bold red">'.$lang['srv_voting_no_duplicates'].'</p>';
            }

			$sql_string = "SELECT comment FROM srv_invitations_messages WHERE ank_id = '$this->sid' AND isdefault='1'";
			$sql_query = sisplet_query($sql_string);
			list($comment) = mysqli_fetch_row($sql_query);
				
			$_msg = '<span>'.$lang['srv_invitation_note3'].'</span>';
			if (isset($_POST['send_type'])) {
				$send_type = (int)$_POST['send_type'];
			}
			$checkboxes = array();
			if (isset($_POST['checkboxes']) && trim($_POST['checkboxes']) != '') {
				$checkboxes = explode(',',$_POST['checkboxes']);
			}
				
			$source_type = (int)$_POST['source_type'];
			$source_lists = trim($_POST['source_lists']);
				
			$respondents = $this->getRespondents2Send($send_type, $checkboxes, $source_type, $source_lists);
			#koliko strani imamp
			$numRespondents = count($respondents);
			$pages = ceil($numRespondents / $this->rec_send_page_limit);
			if (count($respondents) > 0) {
				
				echo '<div class="inv_send_mail_send_type">';
				
				// Način pošiljanja
				echo '<span class="bold">';
				echo $lang['srv_inv_message_type'].': ';
				echo $lang['email'];
				echo '</span><br /><br />';
				
				echo '<form id="frm_do_send" action="'.$site_url.'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=send_mail" method="post">';

				// Komentar pri posiljanju
				echo '<label>'.$lang['srv_inv_send_comment'].' '.Help::display('srv_inv_sending_comment').': ';
				echo '<input type="text" name="comment" id="msg_comment" value="'.$comment.'">';
				echo '</label><br class="clr"><br />';
				
				// Pobrisi podvojene maile
				echo '<label><input type="checkbox" id="dont_send_duplicated" name="dont_send_duplicated" checked="checked">'.$lang['srv_inv_send_remove_duplicates'].'</label></span> '.Help::display('srv_inv_sending_double').'<br />';
				
				// Gumb Poslji
				echo '<br /><div id="inv_send_mail_btn"><span class="buttonwrapper floatLeft"><a href="#" onclick="$(\'#fade\').fadeTo(\'slow\', 1); $(\'#inv_send_note\').fadeTo(\'slow\',1); $(\'#frm_do_send\').submit();" class="ovalbutton ovalbutton_orange" ><span>'.$lang['srv_inv_send'].'</span></a></span>';
				echo '<br class="clr"/><br /></div>';

				// Komentiram kot workaround (če ni zakomentiran, ob ajaxu tu vrine konec forme) - MISLIM DA JE TA POPRAVEK ŠE VEDNO POTREBEN (v kombinaciji z Robertovim)
				//echo '</div>';
				
				// Seznam mailov na katere bomo poslali
				if ((int)$this->invitationAdvancedConditionId > 0)
				{
					//if (is_array($this->user_inv_ids) && count($this->user_inv_ids) > 0)
					{
						echo '<span class="floatLeft">';
						$scp = new SurveyCondition($this->sid);
						$note = $scp -> getConditionString($this->invitationAdvancedConditionId );
						echo $note;
						#$scp -> displayConditionNote($this->invitationAdvancedConditionId );
						echo '</span>';
						echo '<br/>';
					}
				}
				# izpišemo seznam e-mailov in dodamo checkboxe
				echo '<div class="strong">'.$lang['srv_inv_potencial_respondents'].'&nbsp;<span id="inv_num_recipients">'.count($respondents).'</span></div>';

				# izpišemo opozorilo kadar pošiljamo na več kakor 5000 naslovov
				$text = (Common::checkModule('gorenje')) ? $lang['srv_inv_potencial_respondents_limit_gorenje'] : $lang['srv_inv_potencial_respondents_limit'];
				echo '<div id="inv_send_mail_limit" class="red strong'.(count($respondents) > 4999?'':' hidden').'">'.$text.'</div>';
					
				echo '<input type="hidden" name="anketa" id="anketa" value="'.$this->sid.'">';
				# da preprečimo večkratno pošiljanje
				session_start();
				list($short,$long) = $this->generateCode();
				$_SESSION['snd_inv_token'][$this->sid] = $long;
				echo '<input type="hidden" name="_token" id="_token" value="'.$long.'">';
				if ($pages > 1 || $numRespondents > REC_ON_SEND_PAGE) {
					echo '<div id="inv_pagination_content">';
					$this->displaySendPagination($numRespondents);
					echo '</div>';
				}

				# polovimo sezname
				$lists = array();
				$sql_string = "SELECT pid, name,comment FROM srv_invitations_recipients_profiles WHERE uid in('".$global_user_id."')";
				$sql_query = sisplet_query($sql_string);
				while ($sql_row = mysqli_fetch_assoc($sql_query)) {
					$lists[$sql_row['pid']] = $sql_row['name'];
				}

				$lists['-1'] = $lang['srv_invitation_new_templist'];
				$lists['0'] = $lang['srv_invitation_new_templist_author'];

				echo '<div id="inv_send_note">Pošiljam . . . Prosimo počakajte.</div>';
					
				echo '<br/><table id="tbl_recipients_send_list">';
				echo '<tr>';
				echo '<th class="tbl_icon"><input type="checkbox" checked="checked" onclick="invTogleSend(this);">'.'</th>';
				echo '<th title="'.$lang['srv_inv_recipients_email'].'">'.$lang['srv_inv_recipients_email'].'</th>';
				echo '<th title="'.$lang['srv_inv_recipients_last_status'].'">'.$lang['srv_inv_recipients_last_status'].'</th>';
				echo '<th title="'.$lang['srv_inv_recipients_last_status'].'">'.$lang['srv_inv_recipients_list_id'].'</th>';
				echo '</tr>';
				$cnt=1;
				foreach ($respondents as $pass => $respondent) {
					echo '<tr'.($cnt > $this->rec_send_page_limit ? ' class="displayNone"' : '').'>';
					echo '<td><input type="checkbox" name="rids[]" value="'.$respondent['id'].'" checked="checekd"></td>';
					echo '<td>'.$respondent['email'].'</td>';
					echo '<td>'.$lang['srv_userstatus_'.$respondent['status']].' ('.$respondent['status'].')'.'</td>';
					if ($lists[$respondent['list_id']] != '') {
						echo '<td>'.$lists[$respondent['list_id']].'</td>';
					} else {
						echo '<td>'.$lang['srv_inv_send_who_table_list_deleted'].'</td>';
					}
					echo '</tr>';
					$cnt++;
				}
				echo '</table>';
				echo '</form>';
				echo '</div>';
			}
		}
		if ($cnt == 0) {
			# ni respondentov
			echo $_msg;
		}
	}
	
	function selectSendToNoEmailing($send_type = 0, $checkboxes = array()) {
		global $lang, $site_url, $global_user_id;

		if ((int)$this->surveySettings['active'] !== 1) {
			#anketa ni aktivna, ne pustimo pošiljanja
			# aktivnost

			$activity = SurveyInfo:: getSurveyActivity();
			$_last_active = end($activity);

			echo $lang['srv_inv_error9'];
			echo '<a href="#" onclick="anketa_active(\'' . $this->sid . '\',\'' . (int)$this->surveySettings['active'] . '\'); return false;" title="' . $lang['srv_anketa_noactive'] . '">';
			if ((int)$_last_active > 0 ) {
				# anketa je zaključena
				echo ' <span id="srv_inactive">'.$lang['srv_inv_activate_survey_here'].'</span>';
			} else {
				# anketa je neaktivna
				echo ' <span id="srv_inactive">'.$lang['srv_inv_activate_survey_here'].'</span>';
			}
			echo '</a>';
		} else {
			# anketa je aktivna lahko pošiljamo

			$sql_string = "SELECT comment FROM srv_invitations_messages WHERE ank_id = '$this->sid' AND isdefault='1'";
			$sql_query = sisplet_query($sql_string);
			list($comment) = mysqli_fetch_row($sql_query);
				
			$_msg = '<span>'.$lang['srv_invitation_note3'].'</span>';
			if (isset($_POST['send_type'])) {
				$send_type = (int)$_POST['send_type'];
			}
			$checkboxes = array();
			if (isset($_POST['checkboxes']) && trim($_POST['checkboxes']) != '') {
				$checkboxes = explode(',',$_POST['checkboxes']);
			}
				
			$source_type = (int)$_POST['source_type'];
			$source_lists = trim($_POST['source_lists']);
				
			$respondents = $this->getRespondents2Send($send_type, $checkboxes, $source_type, $source_lists, $noEmailing=1);
			#koliko strani imamp
			$numRespondents = count($respondents);
			$pages = ceil($numRespondents / $this->rec_send_page_limit);
			if (count($respondents) > 0) {
				
				echo '<div class="inv_send_mail_send_type">';
				
				// Način pošiljanja
				$noEmailingType = SurveySession::get('inv_noEmailing_type');
				echo '<span class="bold">';
				echo $lang['srv_inv_message_type_external'].': </span>';
				if($noEmailingType == 1)
					echo $lang['srv_inv_message_noemailing_type2'];
				elseif($noEmailingType == 2)
					echo $lang['srv_inv_message_noemailing_type3'];
				else
					echo $lang['srv_inv_message_noemailing_type1'];
				echo '<br /><br />';
				
				echo '<form id="frm_do_send" action="'.$site_url.'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=send_mail&noemailing=1" method="post">';

				// Komentar pri posiljanju
				echo '<label>'.$lang['srv_inv_send_comment'].' '.Help::display('srv_inv_sending_comment').': ';
				echo '<input type="text" name="comment" id="msg_comment" value="'.$comment.'">';
				echo '</label><br class="clr"><br />';
				
				// Pobrisi podvojene maile
				echo '<label><input type="checkbox" id="dont_send_duplicated" name="dont_send_duplicated" checked="checked">'.$lang['srv_inv_send_remove_duplicates'].'</label></span> '.Help::display('srv_inv_sending_double').'<br />';
				
				// Gumb poslji
				echo '<br /><div id="inv_send_mail_btn"><span class="buttonwrapper floatLeft"><a href="#" onclick="$(\'#fade\').fadeTo(\'slow\', 1); $(\'#inv_send_note\').fadeTo(\'slow\',1); $(\'#frm_do_send\').submit();" class="ovalbutton ovalbutton_orange" ><span>'.$lang['srv_inv_nav_send_noEmailing'].'</span></a></span>';
				echo '<br class="clr"/><br /></div>';
				
				echo '</div>';
				
				// Seznam mailov na katere bomo poslali
				if ((int)$this->invitationAdvancedConditionId > 0)
				{
					#if (is_array($this->user_inv_ids) && count($this->user_inv_ids) > 0)
					{
						echo '<span class="floatLeft">';
						$scp = new SurveyCondition($this->sid);
						$note = $scp -> getConditionString($this->invitationAdvancedConditionId );
						echo $note;
						#$scp -> displayConditionNote($this->invitationAdvancedConditionId );
						echo '</span>';
						echo '<br/>';
					}
				}
				# izpišemo seznam e-mailov in dodamo checkboxe
				echo '<div class="strong">'.$lang['srv_inv_potencial_respondents'].'&nbsp;<span id="inv_num_recipients">'.count($respondents).'</span></div>';

				# izpišemo opozorilo kadar pošiljamo na več kakor 5000 naslovov
				$text = (Common::checkModule('gorenje')) ? $lang['srv_inv_potencial_respondents_limit_gorenje'] : $lang['srv_inv_potencial_respondents_limit'];
				echo '<div id="inv_send_mail_limit" class="red strong'.(count($respondents) > 4999?'':' hidden').'">'.$text.'</div>';
					
				echo '<input type="hidden" name="anketa" id="anketa" value="'.$this->sid.'">';
				# da preprečimo večkratno pošiljanje
				session_start();
				list($short,$long) = $this->generateCode();
				$_SESSION['snd_inv_token'][$this->sid] = $long;
				echo '<input type="hidden" name="_token" id="_token" value="'.$long.'">';
				if ($pages > 1 || $numRespondents > REC_ON_SEND_PAGE) {
					echo '<div id="inv_pagination_content">';
					$this->displaySendPagination($numRespondents);
					echo '</div>';
				}

				# polovimo sezname
				$lists = array();
				$sql_string = "SELECT pid, name,comment FROM srv_invitations_recipients_profiles WHERE uid in('".$global_user_id."')";
				$sql_query = sisplet_query($sql_string);
				while ($sql_row = mysqli_fetch_assoc($sql_query)) {
					$lists[$sql_row['pid']] = $sql_row['name'];
				}

				$lists['-1'] = $lang['srv_invitation_new_templist'];
				$lists['0'] = $lang['srv_invitation_new_templist_author'];

				echo '<div id="inv_send_note">Pošiljam . . . Prosimo počakajte.</div>';

				echo '<br/><table id="tbl_recipients_send_list">';
				echo '<tr>';
				echo '<th class="tbl_icon"><input type="checkbox" checked="checked" onclick="invTogleSend(this);">'.'</th>';
				echo '<th title="'.$lang['srv_inv_recipients_email'].'">'.$lang['srv_inv_recipients_email'].'</th>';
				echo '<th title="'.$lang['srv_inv_recipients_firstname'].'">'.$lang['srv_inv_recipients_firstname'].'</th>';
				echo '<th title="'.$lang['srv_inv_recipients_last_status'].'">'.$lang['srv_inv_recipients_last_status'].'</th>';
				echo '<th title="'.$lang['srv_inv_recipients_last_status'].'">'.$lang['srv_inv_recipients_list_id'].'</th>';
				echo '</tr>';
				$cnt=1;
				foreach ($respondents as $pass => $respondent) {
					echo '<tr'.($cnt > $this->rec_send_page_limit ? ' class="displayNone"' : '').'>';
					echo '<td><input type="checkbox" name="rids[]" value="'.$respondent['id'].'" checked="checekd"></td>';
					echo '<td>'.$respondent['email'].'</td>';
					echo '<td>'.$respondent['firstname'].'</td>';
					echo '<td>'.$lang['srv_userstatus_'.$respondent['status']].' ('.$respondent['status'].')'.'</td>';
					if ($lists[$respondent['list_id']] != '') {
						echo '<td>'.$lists[$respondent['list_id']].'</td>';
					} else {
						echo '<td>'.$lang['srv_inv_send_who_table_list_deleted'].'</td>';
					}
					echo '</tr>';
					$cnt++;
				}
				echo '</table>';
				echo '</form>';
			}
		}
		if ($cnt == 0) {
			# ni respondentov
			echo $_msg;
		}
	}

	function mailToSourceChange() {
		$this->displayMailToSourceLists((int)$_POST['source_type']);
	}

	function viewAarchive() {
		global $lang;
			
        echo '<div id="div_archive_content">';
        
		#preglej prejemnike
		#echo '<h2>'.$lang['srv_inv_heading_step5'].$lang['srv_inv_archive_heading'].'</h2>';
		//echo '<h2>'.$lang['srv_inv_archive_heading'].'</h2>';
		
		# normalno pošiljanje
		$sql_string = "SELECT sia.*, u.name, u.surname, u.email, DATE_FORMAT(sia.date_send,'%d.%m.%Y') AS ds, DATE_FORMAT(sia.date_send,'%T') AS hs FROM srv_invitations_archive AS sia LEFT JOIN users AS u ON sia.uid = u.id WHERE ank_id = '".$this->sid."'  ORDER BY sia.date_send DESC;";
		$sql_query = sisplet_query($sql_string);

		# enostavno pošiljanje na posamezne maile
		$SSMI = new SurveySimpleMailInvitation($this->sid);
		$simple_recipents = $SSMI -> getRecipients();

		if (mysqli_num_rows($sql_query) > 0 || count($simple_recipents) > 0) {

			echo '<h2>'.$lang['srv_archive_invitation'].'</h2>';	
			
			echo '<table id="tbl_archive_list">';
			echo '<tr>';
			echo '<th class="tbl_inv_center" title="'.$lang['srv_inv_archive_date_send'].'">'.$lang['srv_inv_archive_date_send'].'</th>';
			echo '<th class="tbl_inv_center" title="'.$lang['srv_inv_archive_hour_send'].'">'.$lang['srv_inv_archive_hour_send'].'</th>';
			echo '<th title="'.$lang['srv_inv_archive_subject_text'].'">'.$lang['srv_inv_archive_naslov'].'</th>';
			echo '<th title="'.$lang['srv_inv_archive_subject_text'].'">'.$lang['srv_inv_archive_subject_text'].'</th>';
			#echo '<th title="'.$lang['srv_inv_archive_body_text'].'">'.$lang['srv_inv_archive_body_text'].'</th>';
			echo '<th class="tbl_inv_center" title="'.$lang['srv_inv_message_type'].'">'.$lang['srv_inv_message_type'].'</th>';
			echo '<th class="tbl_inv_center" title="'.$lang['srv_inv_archive_cnt_succsess'].'">'.$lang['srv_inv_archive_cnt_succsess'].' '.Help::display('srv_inv_archive_sent').'</th>';
			echo '<th class="tbl_inv_center" title="'.$lang['srv_inv_archive_cnt_error'].'">'.$lang['srv_inv_archive_cnt_error'].'</th>';
			echo '<th title="'.$lang['srv_inv_archive_sender'].'">'.$lang['srv_inv_archive_sender'].'</th>';
			echo '<th title="'.$lang['srv_inv_archive_comment'].'">'.$lang['srv_inv_archive_comment'].'</th>';
			#echo '<th >&nbsp;</th>';
			echo '</tr>';
			while ($row = mysqli_fetch_assoc($sql_query)) {
				echo '<tr>';
				
				// Datum
				echo '<td class="tbl_inv_center">'.$row['ds'].'</td>';
				
				// Ura
				echo '<td class="tbl_inv_center">'.$row['hs'].'</td>';
				
				// Email sporocilo
				echo '<td class="tbl_inv_lef inv_arch_subject" title="'.$row['naslov'].'">';
				echo '<a href="#" onclick="inv_arch_edit_details(\''.$row['id'].'\'); return false;">'.$row['naslov'].'</a>';
				echo '</td>';
				
				// Subject
				echo '<td class="tbl_inv_lef inv_arch_subject" title="'.$row['naslov'].'">';
				echo $row['subject_text'];
				echo '</td>';
				#echo '<td class="tbl_inv_left inv_arch_text" title="'.$row['body_text'].'">'.$row['body_text'].'</td>';
				
				// Nacin posiljanja (email, posta, sms...)
				echo '<td class="tbl_inv_center">';
				if ($row['tip'] == '0')
					echo '<span>'.$lang['srv_inv_message_noemailing_type1'].'</span>';
				elseif($row['tip'] == '1')
					echo '<span>'.$lang['srv_inv_message_noemailing_type2'].'</span>';
				elseif($row['tip'] == '2')
					echo '<span>'.$lang['srv_inv_message_noemailing_type3'].'</span>';
				else
					echo '<span>'.$lang['email'].'</span>';
				echo '</td>';				
				
				# uspešno poslani
				echo '<td class="tbl_inv_center">';
				if ((int)$row['cnt_succsess'] > 0 ) {
					echo '<span class="as_link as_view strong" id="inv_arch_1_'.$row['id'].'" data-archtype="succ">'.$row['cnt_succsess'].'</span>';
				} else {
					echo '<span>'.$row['cnt_succsess'].'</span>';
				}
				echo '</td>';
				
				# neuspešno poslani
				echo '<td class="tbl_inv_center">';
				if ((int)$row['cnt_error'] > 0 ) {
					echo '<span class="as_link as_view strong" id="inv_arch_0_'.$row['id'].'" data-archtype="err">'.$row['cnt_error'].'</span>';
				} else {
					echo '<span>'.$row['cnt_error'].'</span>';
				}
				echo '</td>';
				
				# poslal
				$avtor = array();
				if (trim($row['name'])) {
					$avtor[] = trim ($row['name']);
				}
				if (trim($row['surname'])) {
					$avtor[] = trim ($row['surname']);
				}

				echo '<td>';
				echo '<span title="'.(isset($row['email']) ? $row['email'] : implode(' ',$avtor)).'">'.implode(' ',$avtor).'</span>';
				echo '</td>';
				
				# komentar
				echo '<td>';
				echo '<a href="#" onclick="inv_arch_edit_details(\''.$row['id'].'\'); return false;">'.$row['comment'];
				echo '</td>';
				
				echo '</tr>';
			}
			echo '</tr>';
			echo '</table>';
				
			# dodamo simpl pošiljanje
			if ( count($simple_recipents) > 0 ) {
				if (mysqli_num_rows($sql_query) > 0) {
					echo '<br>';
					echo '<br>';
				}
				echo '<b>Prejemniki enostavnih email vabil:</b>';
				echo '<br>';
				echo '<br>';
				echo '<table id="tbl_archive_list">';
				echo '<tr>';
				echo '<th class="tbl_inv_center" title="'.$lang['srv_inv_archive_date_send'].'">'.$lang['srv_inv_archive_date_send'].'</th>';
				echo '<th class="tbl_inv_center" title="'.$lang['srv_inv_archive_email_address'].'">'.$lang['srv_inv_archive_email_address'].'</th>';
				echo '<th class="tbl_inv_center" title="'.$lang['srv_inv_archive_status'].'">'.$lang['srv_inv_archive_status'].'</th>';
				echo '<th title="'.$lang['srv_inv_archive_sender'].'">'.$lang['srv_inv_archive_sender'].'</th>';
				echo '</tr>';
				foreach ($simple_recipents as $row) {
					echo '<tr>';
					echo '<td>'.$row['send_time'].'</td>';
					echo '<td>'.$row['email'].'</td>';
					echo '<td class="tbl_inv_center">'.$row['state'].'</td>';
					# poslal
					$avtor = array();
					if (trim($row['name'])) {
						$avtor[] = trim ($row['name']);
					}
					if (trim($row['surname'])) {
						$avtor[] = trim ($row['surname']);
					}
					echo '<td>';
					echo '<span title="'.(isset($row['adminmail']) ? $row['adminmail'] : implode(' ',$avtor)).'">'.implode(' ',$avtor).'</span>';
					echo '</td>';
						
					echo '</tr>';
				}
				echo '</table>';
			}

		} else {
			echo '<fieldset>';
			echo '<legend>'.$lang['srv_archive_invitation'].'</legend>';
		
			echo $lang['srv_invitation_note4'].'';
			
			echo '</fieldset>';
		}

		
			
		echo '</div>'; # id="div_archive_content">';
		echo '<br class="clr">';

	}


    // Glavno posiljanje mail vabil
	function sendMail() {
		global $lang, $site_path, $site_url, $global_user_id, $lastna_instalacija;
		
		Common::getInstance()->Init($this->sid);
	
		if (isset($_POST['rids'])) {
			
			session_start();
			
			# preverimo token, da ne pošiljamo večkrat
			if (isset($_SESSION['snd_inv_token'][$this->sid]) 
				&& isset($_POST['_token']) 
				&& $_SESSION['snd_inv_token'][$this->sid] != null 
				&& $_SESSION['snd_inv_token'][$this->sid] == isset($_POST['_token'])){
						
				// na send smo kliknili samo 1x
				unset($_SESSION['snd_inv_token'][$this->sid]);
				
				session_commit();
				
				$dont_send_duplicated = false;
				if (isset($_POST['dont_send_duplicated']) && $_POST['dont_send_duplicated'] == 'on') {
					$dont_send_duplicated = true;
				}

				$rids = $_POST['rids'];

				$return = array();
				$return['error'] = '0';
				$return['msg'] = '<div class="inv_send_message">'.$lang['srv_invitation_note5'].'</div>';

				// Shranimo komentar h posiljanju
				if(isset($_POST['comment']) && $_POST['comment'] != ''){
					$comment = $_POST['comment'];
					$sqlC = sisplet_query("UPDATE srv_invitations_messages SET comment='$comment' WHERE ank_id='$this->sid' AND isdefault='1'");
				}
				
				if ($this->checkDefaultMessage() == false) {
					echo '<span class="inv_error_note">';
					echo $lang['srv_invitation_note6'];
					echo '</span>';
                    
                    exit();
                } 
                else {
					// polovimo sporočilo in prejemnike
					$sql_query_m = sisplet_query("SELECT id, subject_text, body_text, reply_to, isdefault, comment, naslov, url FROM srv_invitations_messages WHERE ank_id = '$this->sid' AND isdefault='1'");
                    
                    if (mysqli_num_rows($sql_query_m) > 0 ) {
						$sql_row_m = mysqli_fetch_assoc($sql_query_m);
                    } 
                    else {
						#nimamo še vsebine sporočila skreiramo privzeto.
						echo '<span class="inv_error_note">';
						echo $lang['srv_invitation_note6'];
						echo '</span>';
                        
                        exit();
					}
				}

				$subject_text = $sql_row_m['subject_text'];
				$body_text = $sql_row_m['body_text'];
				$msg_url = $sql_row_m['url'];
                
				// naslov za odgovor je avtor ankete
				if ($this->validEmail($sql_row_m['reply_to'])) {
					$reply_to = $sql_row_m['reply_to'];
                } 
                else {
					$reply_to = Common::getInstance()->getReplyToEmail();
                }


                // prejeminki besedila
				$sql_query = sisplet_query("SELECT id, firstname, lastname, email, password, password, cookie, phone, salutation, custom, relation 
                                                FROM srv_invitations_recipients 
                                                WHERE ank_id = '".$this->sid."' AND deleted='0' AND id IN (".implode(',',$rids).") 
                                                ORDER BY id
                                            ");
				
                # zloopamo skozi prejemnike in personaliziramo sporočila in jih pošljemo
				$date_sent = date ("Y-m-d H:i:s");

                $numRows = mysqli_num_rows($sql_query);
                
				# če pošiljamo na večje število reposndentov obvestimo info@1ka.si
				if ($numRows > NOTIFY_INFO1KA && (!isset($lastna_instalacija) || $lastna_instalacija == false)) {
					
					// Gorenje tega nima
					if (!Common::checkModule('gorenje')){
						global $site_url, $global_user_id;
						
						$sqlinfo_query = sisplet_query("SELECT email, name, surname FROM users WHERE id = '".$global_user_id."'");
						list($infoEmail,$infoName,$infoSurname) = mysqli_fetch_row($sqlinfo_query);
                        
                        $infourl = '<a href="'.$site_url.'admin/survey/index.php?anketa='.$this->sid.'">anketi</a>';
						$format = $lang['srv_inv_send_finish_note'];
							
						$info1ka_mass_email_note = sprintf($format, $infoName, $infoSurname, $infoEmail, $infourl, $numRows);
						
						try{
							$MA = new MailAdapter($this->sid, $type='admin');
							$MA->addRecipients('info@1ka.si');
							$resultX = $MA->sendMail($info1ka_mass_email_note, 'Masovno pošiljanje vabil (poslanih več kot '.NOTIFY_INFO1KA.')');
						}
						catch (Exception $e){
						}
					}
				}

				// Pripravimo arhiv pošiljanj, da dobimo arch_id
				$sql_query_all 	 = sisplet_query("SELECT count(*) FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted = '0'");
				list($count_all) = mysqli_fetch_row($sql_query_all);

				$archive_naslov = 'mailing_'.date("d.m.Y").', '.date("H:i:s");
				$sqlQuery = sisplet_query("INSERT INTO srv_invitations_archive 
                                            (id, ank_id, date_send, subject_text, body_text, uid, comment, naslov, rec_in_db)
                                            VALUES 
                                            (NULL , '$this->sid', '$date_sent', '".addslashes($subject_text)."', '".addslashes($body_text)."', '$global_user_id','$comment','$archive_naslov','$count_all')
                                        ");

				$arch_id = mysqli_insert_id($GLOBALS['connect_db']);
				
                // Podatki posiljatelja
                list($name, $surname, $email) = mysqli_fetch_row(sisplet_query("SELECT name, surname, email FROM users WHERE id='$global_user_id'"));

                // Podatki za posiljanje
                $sending_data = array(
                    'body_text'         => $body_text, 
                    'subject_text'      => $subject_text, 
                    'arch_id'           => $arch_id, 
                    'msg_url'           => $msg_url, 
                    'date_sent'         => $date_sent, 
                    'from_email'        => $email, 
                    'from_name'         => $name.' '.$surname,
                    'reply_to_email'    => $reply_to
                );
				
                // Loop po prejemnikih in posiljanje mailov
                $squalo = new SurveyInvitationsSqualo($this->sid);
                if($squalo->getSqualoActive()){
                    $sending_results = $squalo->sendSqualoInvitations($sql_query, $sending_data);
                }
                else{
                    $sending_results = $this->sendMailToUsers($sql_query, $sending_data);
                }
                
                $send_ok = $sending_results['send_ok'];
                $send_ok_ids = $sending_results['send_ok_ids'];
                $send_users_data = $sending_results['send_users_data'];
                $send_error = $sending_results['send_error'];
                $send_error_ids = $sending_results['send_error_ids'];


				// dodajmo še userje v povezovalno tabelo (arhiv)
				if ($arch_id > 0) {

					// updejtamo še tabelo arhivov
					$sqlQuery = sisplet_query("UPDATE srv_invitations_archive SET cnt_succsess='".count($send_ok_ids)."', cnt_error='".count($send_error_ids)."' WHERE id ='$arch_id'");
					if (!$sqlQuery) {
						$error = mysqli_error($GLOBALS['connect_db']);
					}
					
					// za arhive
					$_archive_recipients = array();

                    // za tracking
					$_tracking = array();

					if (count($send_ok_ids) > 0) {
						foreach ( $send_ok_ids AS $id) {
							$_archive_recipients[] = "('$arch_id','$id','1')";
							#status 1=pošta poslana
							$_tracking[] = "('$arch_id',NOW(),'$id','1')";
						}
                    }
                    
					if (count($send_error_ids) > 0) {
						foreach ( $send_error_ids AS $id) {
							$_archive_recipients[] = "('$arch_id','$id','0')";
							#status 2=pošta - napaka
							$_tracking[] = "('$arch_id',NOW(),'$id','2')";
						}
					}
					
					if (count($_archive_recipients) > 0) {
						$sqlString = 'INSERT INTO srv_invitations_archive_recipients (arch_id,rec_id,success) VALUES ';
						$sqlString .= implode(', ', $_archive_recipients);
						$sqlQuery = sisplet_query($sqlString);    
                    }
                    
					if (count($_tracking) > 0) {
						$sqlStrTracking = "INSERT INTO srv_invitations_tracking (inv_arch_id, time_insert, res_id, status) VALUES ";
						$sqlStrTracking .= implode(', ', $_tracking);
						$sqlQueryTracking = sisplet_query($sqlStrTracking);    
					}
				}


                // Izpis rezultatov - errors and successes
				if (count($send_error) > 0 ) {
					$return['error'] = '1';

					$return['msg'] = '<div class="inv_send_message">'.$lang['srv_invitation_note7'].count($send_error).'</div>';
                } 
                else if (count($send_ok) > 0 ) {
                    
					$who='';
                    
                    if (trim($name) != '') {
						$who = $name;
                    }
                    
					if (trim($surname) != '') {
						if ($who != '') {
							$who .=' ';
						}
						$who .= $surname;
                    }
                    
					if ($email != '') {
						if ($who != '') {
							$who .=' ('.$email.')';
						} else {
							$who = $email;
						}
					}
					
					$return['error'] = '0';
					
					// Uspesno poslano sporocilo
					$return['msg'] = '<br /><span class="bold" style="line-height:30px;">'.$lang['srv_invitation_note8a'].'</span><br />';
					$return['msg'] .= '<div class="inv_send_message">';
					$return['msg'] .= '<table id="inv_send_mail_preview">';
					$return['msg'] .= 	'<tr><th><span>'.$lang['srv_inv_message_draft_content_subject'].':</span></th>';
					$return['msg'] .= 		'<td class="inv_bt">';
					$return['msg'] .= 		'<span>'.$sql_row_m['subject_text'].'</span>';
					$return['msg'] .= 		'</td></tr>';
					$return['msg'] .= 	'<tr><th>'.$lang['srv_inv_message_draft_content_body'].':</th>';
					$return['msg'] .= 		'<td>';
					$return['msg'] .= 		'<span class="nl2br">'.($sql_row_m['body_text']).'</span>';
					$return['msg'] .= 	'</td></tr>';
					$return['msg'] .= '</table>';
					$return['msg'] .= '</div>';
					
					// Je uporabnik poslal na ...
					$return['msg'] .= sprintf($lang['srv_invitation_note8b'], $who, date("d.m.y", time()));
					$return['msg'] .= '<span class="bold" style="line-height:30px;">'.sprintf($lang['srv_invitation_note8'], count($send_ok)).'</span><br />';
					
					// Arhivi
					$return['msg'] .= '<span class="bold" style="line-height:20px;">'.sprintf($lang['srv_invitation_note8c'], $site_url.'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=view_archive').'</span><br />';
					
					// Seznam emailov...
					$return['msg'] .= '<span class="bold" style="line-height:30px;">'.$lang['srv_invitation_note8d'].'</span><br />';
					
					// Seznam mailov na katere je bilo uspesno poslano
					if (count($send_ok) > 0) {
                        $return['msg'] .= '<div class="inv_send_message">';
                        
						foreach ($send_ok AS $email) {
							$return['msg'] .= '&nbsp;'.$email.'<br/>';
                        }
                        
						$return['msg'] .= '</div>';
					}
				}
				else {
					$return['error'] = '0';
					$return['msg'] = '<div class="inv_send_message">'.'<strong>'.$lang['srv_invitation_note9'].'</strong></div><br/>';	
				}
			} 
			else {
				#old session token
				$return['msg'] = '<div class="inv_send_message"><span class="red strong">'.$lang['srv_invitation_note13'].'</span>'.'</div>';
			}
		} 
		else {
			#nimamo $rids
			$return['msg'] = '<div class="inv_send_message">'.$lang['srv_invitation_note14'].'</div>';
		}

		# popravimo timestamp za regeneracijo dashboarda
		Common::getInstance()->Init($anketa);
		Common::getInstance()->updateEditStamp();

		#$this->viewAarchive($return['msg']);
		$this->viewSendMailFinish($return['msg']);
	}
    
    // Posljemo mail userjem - loop in send
    private function sendMailToUsers($sql_recipients_query, $sending_data){
        global $global_user_id;
        global $site_url;


        // Preverimo ce je vklopljen modul za volitve
        $voting = SurveyInfo::getInstance()->checkSurveyModule('voting');
        
        # če mamo SEO
		$nice_url = SurveyInfo::getSurveyLink();

        // Polovimo sistemske spremenljivke
        $sys_vars = $this->getSystemVars();

        # zakeširamo user_id za datapiping
        $arryDataPiping = array();
        $qryDataPiping = sisplet_query("SELECT id, inv_res_id FROM srv_user WHERE ank_id='$this->sid' AND inv_res_id IS NOT NULL");
        while (list($dpUid, $dpInvResId) = mysqli_fetch_row($qryDataPiping)) {
            
            if ((int)$dpInvResId > 0 && (int)$dpUid > 0) {
                $arryDataPiping[$dpInvResId] = (int)$dpUid;
            }
        }

        $duplicated = array();

        # array za rezultate
		$send_ok = array();
		$send_ok_ids = array();
		$send_users_data = array();
		$send_error = array();
		$send_error_ids = array();

        // Loop po prejemnikih
        while ($sql_row = mysqli_fetch_assoc($sql_recipients_query)) {

            $password = $sql_row['password'];
                
            $email = $sql_row['email'];

            // Preverimo ce je duplikat
            if ($dont_send_duplicated == true && isset($duplicated[$email])) {
                $duplicated[$email] ++;
                continue;
            }
            
            $duplicated[$email] = 1;

            $individual = (int)$this->surveySettings['individual_invitation'];
            
            if ( ($individual  == 1 && trim($email) != '' && trim($password) != '') || ($individual == 0 && trim($email) != '') ){

                // odvisno ali imamo url za jezik.
                if ($sending_data['msg_url'] != null && trim($sending_data['msg_url']) != '' ) {
                    $url = $sending_data['msg_url'] . ($individual  == 1  ? '?code='.$password : '');
                } 
                else {
                    $url = $nice_url . ($individual  == 1  ? '&code='.$password : '');
                }

                $url .= '&ai='.(int)$sending_data['$arch_id'];
                
                // odjava
                $unsubscribe = $site_url . 'admin/survey/unsubscribe.php?anketa=' . $this->sid . '&code='.$password;

                $user_body_text = str_replace(
                    array(
                            '#URL#',
                            '#URLLINK#',
                            '#UNSUBSCRIBE#',
                            '#FIRSTNAME#',
                            '#LASTNAME#',
                            '#EMAIL#',
                            '#CODE#',
                            '#PASSWORD#',
                            '#PHONE#',
                            '#SALUTATION#',
                            '#CUSTOM#',
                            '#RELATION#',
                    ),
                    array(
                            '<a href="' . $url . '">' . $url . '</a>',
                            $url,
                            '<a href="' . $unsubscribe . '">' . $lang['user_bye_hl'] . '</a>',
                            $sql_row['firstname'],
                            $sql_row['lastname'],
                            $sql_row['email'],
                            $sql_row['password'],
                            $sql_row['password'],
                            $sql_row['phone'],
                            $sql_row['salutation'],
                            $sql_row['custom'],
                            $sql_row['relation'],
                    ),
                    $sending_data['body_text']
                );

                
                // naredimo DataPiping;
                if (isset($arryDataPiping[$sql_row['id']])) {
                    $user_body_text = Common::getInstance()->dataPiping($user_body_text, $arryDataPiping[$sql_row['id']], 0);
                }
                $resultX = null;

                try{
                    $MA = new MailAdapter($this->sid, $type='invitation');
                    $MA->addRecipients($email);
                    $resultX = $MA->sendMail($user_body_text, $sending_data['subject_text']);
                   
                }
                catch (Exception $e){
                    // todo fajn bi bilo zalogirat kaj se dogaja
                    $__error = $e->getMessage();
                    $__errStack = $e->getTraceAsString();
                }
                
                $_user_data = $sql_row;
                if ($resultX) {
                    $send_ok[] = $email;
                    $send_ok_ids[] = $sql_row['id'];
                    $_user_data['status'] = 1;
                    # poslalo ok
                } 
                else {
                    // ni poslalo
                    $send_error[] = $email;
                    $send_error_ids[] = $sql_row['id'];
                    $_user_data['status'] = 2;
                }

                $send_users_data[] = $_user_data;
                

                // updejtamo userja da mu je bilo poslano - PO NOVEM TO DELAMO SPROTI
                if ( count($send_ok_ids) > 0) {
                    
                    $sqlQuery = sisplet_query("UPDATE srv_invitations_recipients SET sent='1', date_sent='".$sending_data['date_sent']."' WHERE id IN (".implode(',',$send_ok_ids).")");
                    if (!$sqlQuery) {
                        $error = mysqli_error($GLOBALS['connect_db']);
                    }
                    
                    // statuse popravimo samo če vabilo še ni bilo poslano ali je bila napaka
                    $sqlQuery = sisplet_query("UPDATE srv_invitations_recipients SET last_status='1' WHERE id IN (".implode(',',$send_ok_ids).") AND last_status IN ('0','2')");
                    if (!$sqlQuery) {
                        $error = mysqli_error($GLOBALS['connect_db']);
                    }

                    // Pri volitvah za sabo pobrisemo podatke preko katerih bi lahko povezali prejemnike z responsi
                    if($voting){
                        $sqlQuery = sisplet_query("UPDATE srv_invitations_recipients 
                                                SET cookie='', password=''
                                                WHERE id IN (".implode(',',$send_ok_ids).") AND sent='1' AND last_status='1' AND ank_id='".$this->sid."'
                                            ");
                        if (!$sqlQuery) {
                            $error = mysqli_error($GLOBALS['connect_db']);
                        }
                    }
                }

                # updejtamo status za errorje
                if ( count($send_error_ids) > 0) {

                    $sqlQuery = sisplet_query("UPDATE srv_invitations_recipients SET last_status = GREATEST(last_status,2) WHERE id IN (".implode(',',$send_error_ids).") AND last_status IN ('0')");
                    if (!$sqlQuery) {
                        $error = mysqli_error($GLOBALS['connect_db']);
                    }
                }

                // če mamo personalizirana email vabila, userje dodamo v bazo
                if ($individual == 1) {
                                    
                    // dodamo še userja v srv_user da je kompatibilno s staro logiko
                    $strInsertDataText = array();
                    $strInsertDataVrednost = array();

                    // Pri volitvah zaradi anonimizacije ignoriramo vse identifikatorje
                    if($voting){
                        $_r = sisplet_query("INSERT INTO srv_user 
                                                (ank_id, cookie, pass, last_status, inv_res_id) 
                                                VALUES 
                                                ('".$this->sid."', '".$_user_data['cookie']."', '".$_user_data['password']."', '".$_user_data['status']."', '-1') ON DUPLICATE KEY UPDATE cookie = '".$_user_data['cookie']."', pass='".$_user_data['password']."'
                                            ");

                        // Ce ne belezimo parapodatka za cas responsa, anonimno zabelezimo cas zadnjega responsa
                        sisplet_query("UPDATE srv_anketa SET last_response_time=NOW() WHERE id='".$this->sid."'");
                    }
                    else{
                        $_r = sisplet_query("INSERT INTO srv_user 
                                                (ank_id, email, cookie, pass, last_status, time_insert, inv_res_id) 
                                                VALUES 
                                                ('".$this->sid."', '".$_user_data['email']."', '".$_user_data['cookie']."', '".$_user_data['password']."', '".$_user_data['status']."', NOW(), '".$_user_data['id']."') ON DUPLICATE KEY UPDATE cookie = '".$_user_data['cookie']."', pass='".$_user_data['password']."'
                                            ");
                    }
                    $usr_id = mysqli_insert_id($GLOBALS['connect_db']);

                    if ($usr_id) {

                        // dodamo še srv_userbase in srv userstatus
                        sisplet_query("INSERT INTO srv_userbase (usr_id, tip, datetime, admin_id) VALUES ('".$usr_id."','0',NOW(),'".$global_user_id."')");
                        sisplet_query("INSERT INTO srv_userstatus (usr_id, tip, status, datetime) VALUES ('".$usr_id."', '0', '0', NOW())");
                            
                        // dodamo še podatke za posameznega userja za sistemske spremenljivke
                        foreach ($sys_vars AS $sid => $spremenljivka) {
                            
                            $_user_variable = $this->inv_variables_link[$spremenljivka['variable']];
                            
                            if (trim($_user_data[$_user_variable]) != '' && $_user_data[$_user_variable] != null) {
                                if($spremenljivka['variable'] == 'odnos')
                                    $strInsertDataVrednost[] = "('".$sid."','".$spremenljivka['vre_id'][trim($_user_data[$_user_variable])]."','".$usr_id."')";
                                else
                                    $strInsertDataText[] = "('".$sid."','".$spremenljivka['vre_id']."','".trim($_user_data[$_user_variable])."','".$usr_id."')";
                            }
                        }
                    } 
                    else {
                        // lahko da user že obstaja in je šlo za duplicated keys
                    }             
                                   

                    // Pri volitvah zaradi anonimizacije ne vsatvimo nicesar v sistemske spremenljivke
                    if(!$voting){
                        
                        // vstavimo v srv_data_text
                        if (count($strInsertDataText) > 0) {
                            $strInsert = "INSERT INTO srv_data_text".$this->db_table." (spr_id, vre_id, text, usr_id) VALUES ";
                            $strInsert .= implode(',',$strInsertDataText);
                            sisplet_query($strInsert);
                        }
                        // vstavimo v srv_data_vrednost
                        if (count($strInsertDataVrednost) > 0) {
                            $strInsert = "INSERT INTO srv_data_vrednost".$this->db_table." (spr_id, vre_id, usr_id) VALUES ";
                            $strInsert .= implode(',',$strInsertDataVrednost);
                            sisplet_query($strInsert);
                        }
                    }
                }
            }
        }

        $results = array(
            'send_ok'           => $send_ok,
            'send_ok_ids'       => $send_ok_ids,
            'send_users_data'   => $send_users_data,
            'send_error'        => $send_error,
            'send_error_ids'    => $send_error_ids,
        );

        return $results;
    }

    private function getSystemVars(){

        // polovimo sistemske spremenljivke z vrednostmi
        $qrySistemske = sisplet_query("SELECT s.id, s.naslov, s.variable 
                                        FROM srv_spremenljivka s, srv_grupa g 
                                        WHERE s.sistem='1' AND s.gru_id=g.id AND g.ank_id='".$this->sid."' AND variable IN ("."'" . implode("','",$this->inv_variables)."')
                                        ORDER BY g.vrstni_red, s.vrstni_red
                                    ");
        $sys_vars = array();
        $sys_vars_ids = array();

        while ($row = mysqli_fetch_assoc($qrySistemske)) {
            $sys_vars[$row['id']] = array('id'=>$row['id'], 'variable'=>$row['variable'],'naslov'=>$row['naslov']);
            $sys_vars_ids[] = $row['id'];
        }

        $sqlVrednost = sisplet_query("SELECT spr_id, id AS vre_id, vrstni_red, variable FROM srv_vrednost WHERE spr_id IN(".implode(',',$sys_vars_ids).") ORDER BY vrstni_red ASC ");
        while ($row = mysqli_fetch_assoc($sqlVrednost)) {

            // Ce gre za odnos imamo radio
            if($sys_vars[$row['spr_id']]['variable'] == 'odnos'){

                if(!isset($sys_vars[$row['spr_id']]['vre_id'][$row['vrstni_red']]))
                $sys_vars[$row['spr_id']]['vre_id'][$row['variable']] = $row['vre_id'];
            }
            elseif (!isset($sys_vars[$row['spr_id']]['vre_id'])) {				
             $sys_vars[$row['spr_id']]['vre_id'] = $row['vre_id'];
            }
        }

        return $sys_vars;
    }

    // Rocna aktivacija vabil
	function sendMailNoEmailing() {
		global $lang, $site_path, $site_url, $global_user_id;
		
		Common::getInstance()->Init($this->sid);
	
		if (isset($_POST['rids'])) {
			
			session_start();
			
			# preverimo token, da ne pošiljamo večkrat
			if (isset($_SESSION['snd_inv_token'][$this->sid])
					&& isset($_POST['_token'])
					&& $_SESSION['snd_inv_token'][$this->sid] != null
					&& $_SESSION['snd_inv_token'][$this->sid] == isset($_POST['_token'])) {
                
                # na send smo kliknili samo 1x
				unset($_SESSION['snd_inv_token'][$this->sid]);
				
				session_commit();
				
				$dont_send_duplicated = false;
				if (isset($_POST['dont_send_duplicated']) && $_POST['dont_send_duplicated'] == 'on') {
					$dont_send_duplicated = true;
				}

				$rids = $_POST['rids'];

				$return = array();
				$return['error'] = '0';
				$return['msg'] = '<div class="inv_send_message">'.sprintf($lang['srv_invitation_note5_noEmailing'], $site_url.'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=view_archive').'<br /><br/>';
				$return['msg'] .= sprintf($lang['srv_invitation_note5_noEmailing2'], $site_url.'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=view_recipients').'</div>';

				// Shranimo komentar h posiljanju
				if(isset($_POST['comment']) && $_POST['comment'] != ''){
					$comment = $_POST['comment'];
					$sqlC = sisplet_query("UPDATE srv_invitations_messages SET comment='$comment' WHERE ank_id='$this->sid' AND isdefault='1'");
				}
				
				// Preberemo tip posiljanja (navadna posta, sms...)
				$noMail_type = SurveySession::get('inv_noEmailing_type');

				// Pripravimo arhiv pošiljanj, da dobimo arch_id
				$sql_query_all 	 = sisplet_query("SELECT count(*) FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted = '0'");
				list($count_all) = mysqli_fetch_row($sql_query_all);

				$date_sent = date ("Y-m-d H:i:s");		
				$archive_naslov = 'mailing_'.date("d.m.Y").', '.date("H:i:s");
				
				// Naslov in body
				// polovimo sporočilo in prejemnike
				$sql_query_m = sisplet_query("SELECT id, subject_text, body_text, reply_to, isdefault, comment, naslov, url FROM srv_invitations_messages WHERE ank_id = '$this->sid' AND isdefault='1'");
				if (mysqli_num_rows($sql_query_m) > 0 ) {
					$sql_row_m = mysqli_fetch_assoc($sql_query_m);
                } 
                else {
					$subject_text = $lang['srv_inv_message_noemailing_subject'];
					$body_text = '';
				}

				$subject_text = $sql_row_m['subject_text'];
				$body_text = $sql_row_m['body_text'];
				$msg_url = $sql_row_m['url'];				
				
				// Vstavimo podatke v arhiv
				$sqlQuery = sisplet_query("INSERT INTO srv_invitations_archive 
                                            (id, ank_id, date_send, subject_text, body_text, tip, uid, comment, naslov, rec_in_db)
                                            VALUES 
                                            (NULL , '$this->sid', '$date_sent', '".addslashes($subject_text)."', '".addslashes($body_text)."', '$noMail_type', '$global_user_id', '$comment', '$archive_naslov', '$count_all')
                                        ");

				$arch_id = mysqli_insert_id($GLOBALS['connect_db']);
				$duplicated = array();
				                
                // Polovimo sistemske spremenljivke
                $sys_vars = $this->getSystemVars();
				
				// prejeminki besedila
				$sql_query = sisplet_query("SELECT id, firstname, lastname, email, password, cookie, phone, salutation, custom, relation 
                                            FROM srv_invitations_recipients 
                                            WHERE ank_id = '".$this->sid."' AND deleted='0' AND id IN (".implode(',',$rids).") 
                                            ORDER BY id
                                        ");
				while ($sql_row = mysqli_fetch_assoc($sql_query)) {

					$password = $sql_row['password'];
						
					$email = $sql_row['email'];
					if ($dont_send_duplicated == true && isset($duplicated[$email]) && $email != '') {
						$duplicated[$email] ++;
						continue;
					}
					
					$duplicated[$email] = 1;
					$individual = (int)$this->surveySettings['individual_invitation'];

					$_user_data = $sql_row;
					
					$send_ok[] = $email;
					$send_ok_ids[] = $sql_row['id'];
					$_user_data['status'] = 1;

					$send_users_data[] = $_user_data;
				}

				// updejtamo userja da mu je bilo poslano
				if ( count($send_ok_ids) > 0) {

					$sqlQuery = sisplet_query("UPDATE srv_invitations_recipients SET sent = '1', date_sent = '".$date_sent."' WHERE id IN (".implode(',',$send_ok_ids).")");
					if (!$sqlQuery) {
						$error = mysqli_error($GLOBALS['connect_db']);
                    }
                    
					// statuse popravimo samo če vabilo še ni bilo poslano ali je bila napaka
					$sqlQuery = sisplet_query("UPDATE srv_invitations_recipients SET last_status = '1' WHERE id IN (".implode(',',$send_ok_ids).") AND last_status IN ('0','2')");
					if (!$sqlQuery) {
						$error = mysqli_error($GLOBALS['connect_db']);
					}
				}

				$comment = $_POST['comment'];
					
				// dodajmo še userje v povezovalno tabelo
				if ($arch_id > 0) {

					// updejtamo še tabelo arhivov
					$sqlQuery = sisplet_query("UPDATE srv_invitations_archive SET cnt_succsess='".count($send_ok_ids)."' WHERE id ='$arch_id'");
					if (!$sqlQuery) {
						$error = mysqli_error($GLOBALS['connect_db']);
					}
					
					# za arhive
					$_archive_recipients = array();
					# za tracking
					$_tracking = array();

					if (count($send_ok_ids) > 0) {
						foreach ( $send_ok_ids AS $id) {
							$_archive_recipients[] = "('$arch_id','$id','1')";
							#status 1=pošta poslana
							$_tracking[] = "('$arch_id',NOW(),'$id','1')";
						}
					}
					
					if (count($_archive_recipients) > 0) {
						$sqlString = 'INSERT INTO srv_invitations_archive_recipients (arch_id,rec_id,success) VALUES ';
						$sqlString .= implode(', ', $_archive_recipients);
						$sqlQuery = sisplet_query($sqlString);    
					}
					if (count($_tracking) > 0) {
						$sqlStrTracking = "INSERT INTO srv_invitations_tracking (inv_arch_id, time_insert, res_id, status) VALUES ";
						$sqlStrTracking .= implode(', ', $_tracking);
						$sqlQueryTracking = sisplet_query($sqlStrTracking);    
					}
				}

				sisplet_query("COMMIT");

				# če mamo personalizirana email vabila, userje dodamo v bazo
				if ($individual == 1 && count($send_users_data) > 0) {
					# dodamo še userja v srv_user da je kompatibilno s staro logiko
					$strInsertDataText = array();
					$strInsertUserbase = array();
					$strInsertUserstatus = array();

					foreach ($send_users_data AS $user_data) {

						$_r = sisplet_query("INSERT INTO srv_user 
                                                (ank_id, email, cookie, pass, last_status, time_insert, inv_res_id) 
                                                VALUES 
                                                ('".$this->sid."', '".$user_data['email']."', '".$user_data['cookie']."', '".$user_data['password']."', '".$user_data['status']."', NOW(), '".$user_data['id']."') ON DUPLICATE KEY UPDATE cookie = '".$user_data['cookie']."', pass='".$user_data['password']."'
                                            ");
						$usr_id = mysqli_insert_id($GLOBALS['connect_db']);
						sisplet_query("COMMIT");

						if ($usr_id) {
							# dodamo še srv_userbase in srv userstatus
							$strInsertUserbase[] = "('".$usr_id."','0',NOW(),'".$global_user_id."')";
							$strInsertUserstatus[] = "('".$usr_id."', '0', '0', NOW())";
							
							# dodamo še podatke za posameznega userja za sistemske spremenljivke
							foreach ($sys_vars AS $sid => $spremenljivka) {
								$_user_variable = $this->inv_variables_link[$spremenljivka['variable']];
								if (trim($user_data[$_user_variable]) != '' && $user_data[$_user_variable] != null) {
									$strInsertDataText[] = "('".$sid."','".$spremenljivka['vre_id']."','".trim($user_data[$_user_variable])."','".$usr_id."')";
								}
							}
                        } 
                        else {
							// lahko da user že obstaja in je šlo za duplicated keys
						}
					}
						
					// vstavimo v srv_userbase
					if (count($strInsertUserbase) > 0) {
						$strInsert = "INSERT INTO srv_userbase (usr_id, tip, datetime, admin_id) VALUES ";
						$strInsert .= implode(',',$strInsertUserbase);
						sisplet_query($strInsert);
					}
					// vstavimo v srv_userstatus
					if (count($strInsertUserstatus) > 0) {
						$strInsert = "INSERT INTO srv_userstatus (usr_id, tip, status, datetime) VALUES ";
						$strInsert .= implode(',',$strInsertUserstatus);
						sisplet_query($strInsert);
					}
					// vstavimo v srv_data_text
					if (count($strInsertDataText) > 0) {
						$strInsert = "INSERT INTO srv_data_text".$this->db_table." (spr_id, vre_id, text, usr_id) VALUES ";
						$strInsert .= implode(',',$strInsertDataText);
						sisplet_query($strInsert);
					}
					
					sisplet_query("COMMIT");
				}


				if (count($send_ok) > 0 ) {

					list($name,$surname,$email) = mysqli_fetch_row(sisplet_query("SELECT name, surname, email FROM users WHERE id='$global_user_id'"));
                    $who='';
                    
					if (trim($name) != '') {
						$who = $name;
                    }
                    
					if (trim($surname) != '') {
						if ($who != '') {
							$who .=' ';
						}
						$who .= $surname;
                    }
                    
					if ($email != '') {
						if ($who != '') {
							$who .=' ('.$email.')';
						} else {
							$who = $email;
						}
                    }
                    
					$return['error'] = '0';
				} 
				else {
					$return['error'] = '0';
					$return['msg'] = '<div class="inv_send_message">'.'<strong>'.$lang['srv_invitation_note9'].'</strong></div><br/>';	
				}	
            } 
            else {
				#old session token
				$return['msg'] = '<div class="inv_send_message"><span class="red strong">'.$lang['srv_invitation_note13'].'</span>'.'</div>';
			}
        } 
        else {
			#nimamo $rids
			$return['msg'] = '<div class="inv_send_message">'.$lang['srv_invitation_note14'].'</div>';
		}
		
		# popravimo timestamp za regeneracijo dashboarda
		Common::getInstance()->Init($anketa);
		Common::getInstance()->updateEditStamp();

		$this->viewSendMailFinish($return['msg']);
	}


	function viewSendMailFinish($msg) {
		global $lang, $site_url;
		
		echo $msg;
		
		echo '<br class="clr" />';
		echo '<span class="floatLeft"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="'.$site_url.'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=inv_status"><span>'.$lang['srv_inv_nav_email_status'].'</span></a></div></span>';
	}

	function uploadRecipients() {
        global $lang;
        
		$errors = array();
		$allowedExtensions = array("txt","csv","dat");
        
        $_fields = trim($_POST['fields']);
		if ($_fields != null && $_fields != '') {
			$fields = explode(',',$_fields);
        } 
        else {
			$fields = array();
		}

		$file_name = $_FILES["recipientsFile"]["name"];
		$file_type = $_FILES["recipientsFile"]["type"];
		$file_size = $_FILES["recipientsFile"]["size"] > 0 ? $_FILES["recipientsFile"]["size"] / 1024 : 0;
		$file_tmp = $_FILES["recipientsFile"]["tmp_name"];

		$okFileType = ( $file_type == 'text/plain' || $file_type == 'text/csv' || $file_type == 'application/vnd.ms-excel' );
		$okFileEnd = (pathinfo($file_name, PATHINFO_EXTENSION) != 'txt' || pathinfo($file_name, PATHINFO_EXTENSION) != 'csv');
		# preverimo ali smo uploadali datoteko in če smo izbrali katero polje
		if ($_POST['posted'] == '1' && count($fields) == 0) {
			$errors[] = $lang['srv_inv_recipiens_upload_error_no_fields'];
		}
		#preverimo ime datoteke
		if ( trim($file_name) == '' || $file_name == null ) {
			$errors[] = $lang['srv_respondents_invalid_file'];
				
			# preverimo tip:
		} else if ( $okFileType == false ) {
			$errors[] = $lang['srv_respondents_invalid_file_type'];

			# prevermio še končnico (.txt)
		} else if ($okFileEnd == false) {
			$errors[] = $lang['srv_respondents_invalid_file_type'];
		}

		# preverimo velikost
		else if ( (float)$file_size == 0 ) {
			$errors[] = $lang['srv_respondents_invalid_file_size'];
		}

		# če so napake jih prikažemo če ne obdelamo datoteko
		if (count($errors) > 0) {
				
            echo '<br class="clr" />';
            
            echo '<span class="inv_message_errors">'.$lang['srv_inv_recipiens_upload_error'].'</span>';
            
			echo '<br class="clr" />';
            echo '<br class="clr" />';
            
			echo '<span class="inv_error_note">';
            foreach($errors as $error) {
				echo '* '.$error.'<br />';
			}
            echo '</span>';
            
			$this->addRecipientsView($fields, $invalid_recipiens_array);
        } 
        else {

			$fh = @fopen($file_tmp, "rb");
			if ($fh) {
				$recipients_list = fread($fh, filesize($file_tmp));
				fclose($fh);
			}

			# po potrebi zamenjamo delimiter iz (;) v (,)
            // Vejica NI kul, ker se uporablja pri nazivih in v custom poljih Za interni delimiter naj bo recimo " | "...
            $recipients_list = str_replace ($_POST['recipientsDelimiter'], "|~|", $recipients_list);
                 
            // Shranimo v seznam
            $pid = $this->saveAppendRecipientList($pid=0, $fields, $recipients_list, $profileName='', $profileComment='');

			// Dodamo polja
            $result = $this->addMassRecipients($recipients_list, $fields, $pid);
            				
			// Prikažemo napake
			$invalid_recipiens_array = $this->displayRecipentsErrors($result);

			$this->addRecipientsView($fields, $invalid_recipiens_array);	
		}
	}

	function viewArchiveRecipients() {
	
		$data = explode('_',$_POST['arch_to_view']);
        
		$_success = (int)$data[2];
		$_arch_id = $data[3];
        $archType = $_POST['archType'];

		# za novejše ankete prikažemo nov način 
		if ($this->newTracking) {
			$this->showArchiveRecipients($_arch_id, $archType);
			return;
		} 
		global $lang,$site_url,$global_user_id;
		echo '<div id="inv_view_arch_recipients">';
		# polovimo sezname
		$lists = array();
		$sql_string = "SELECT pid, name,comment FROM srv_invitations_recipients_profiles WHERE uid in('".$global_user_id."')";
		$sql_query = sisplet_query($sql_string);
		while ($sql_row = mysqli_fetch_assoc($sql_query)) {
			$lists[$sql_row['pid']] = $sql_row['name'];
		}

		$lists['-1'] = $lang['srv_invitation_new_templist'];
		$lists['0'] = $lang['srv_invitation_new_templist_author'];


		$data = explode('_',$_POST['arch_to_view']);
		$_success = (int)$data[2];
		$_arch_id = $data[3];
		$sql_string = "SELECT * FROM srv_invitations_archive WHERE id = '$_arch_id'";
		$sql_query = sisplet_query($sql_string);
		$sql_a_row = mysqli_fetch_assoc($sql_query);

		$sql_string = "SELECT email,firstname,lastname,	password,salutation,phone,custom,relation,sent,responded,unsubscribed,deleted,last_status,list_id FROM srv_invitations_archive_recipients AS siar LEFT JOIN srv_invitations_recipients AS sir on siar.rec_id = sir.id  WHERE arch_id = '$_arch_id' AND success = '$_success'";
		$sql_query = sisplet_query($sql_string);


		echo '<div class="inv_FS_content">';
		
		echo '<table id="tbl_recipients_list">';
		
		echo '<tr>';

        // Pri volitvah ne prikazemo nekaterih stolpcev
        if(SurveyInfo::getInstance()->checkSurveyModule('voting')){
            echo '<th class="tbl_icon" title="'.$lang['srv_inv_recipients_sent'].'">'.$lang['srv_inv_recipients_sent'].'</th>';
            echo '<th class="tbl_inv_left">'.$lang['srv_inv_recipients_email'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_firstname'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_lastname'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_list_id'].'</th>';
        }
        else{
            echo '<th class="tbl_icon" title="'.$lang['srv_inv_recipients_sent'].'">'.$lang['srv_inv_recipients_sent'].'</th>';
            echo '<th class="tbl_icon" title="'.$lang['srv_inv_recipients_responded'].'">'.$lang['srv_inv_recipients_responded'].'</th>';
            echo '<th class="tbl_icon" title="'.$lang['srv_inv_recipients_unsubscribed'].'">'.$lang['srv_inv_recipients_unsubscribed'].'</th>';
            echo '<th class="tbl_inv_left">'.$lang['srv_inv_recipients_email'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_password'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_firstname'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_lastname'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_last_status'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_list_id'].'</th>';
        }

		echo '</tr>';
		
		while ($sql_row = mysqli_fetch_assoc($sql_query)) {
			echo '<tr>';
            
            // Pri volitvah ne prikazemo nekaterih stolpcev
            if(SurveyInfo::getInstance()->checkSurveyModule('voting')){
                echo '<td><img src="'.$site_url.'admin/survey/img_0/'.((int)$sql_row['sent'] == 1 ? 'email_sent.png' : 'email_open.png').'"></td>';
                echo '<td class="tbl_inv_left">'.$sql_row['email'].'</td>';
                echo '<td>'.$sql_row['firstname'].'</td>';
                echo '<td>'.$sql_row['lastname'].'</td>';
                echo '<td>'.$lists[$sql_row['list_id']].'</td>';
            }
            else{
                echo '<td><img src="'.$site_url.'admin/survey/img_0/'.((int)$sql_row['sent'] == 1 ? 'email_sent.png' : 'email_open.png').'"></td>';
                echo '<td><img src="'.$site_url.'admin/survey/icons/icons/'.((int)$sql_row['responded'] == 1 ? 'star_on.png' : 'star_off.png').'"></td>';
                echo '<td><img src="'.$site_url.'admin/survey/img_0/'.((int)$sql_row['unsubscribed'] == 1 ? 'opdedout_on.png' : 'opdedout_off.png').'"></td>';
                echo '<td class="tbl_inv_left">'.$sql_row['email'].'</td>';
                echo '<td>'.$sql_row['password'].'</td>';
                echo '<td>'.$sql_row['firstname'].'</td>';
                echo '<td>'.$sql_row['lastname'].'</td>';
                echo '<td>'.$lang['srv_userstatus_'.$sql_row['last_status']].' ('.$sql_row['last_status'].')'.'</td>';
                echo '<td>'.$lists[$sql_row['list_id']].'</td>';
            }
			

			echo '</tr>';
		}
		
		echo '</table>';
		
		echo '</div>'; // id="arc_content"
		echo '<div class="inv_FS_btm">';
		echo '<div id="navigationBottom" class="printHide">';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inv_arch_recipients_close(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
		echo '<div class="clr" />';
		echo '</div>';

		echo '</div>';
	}

	function editArchiveComment() {
		global $lang,$site_url;
		
		echo '<div id="inv_view_arch_recipients">';

		$data = explode('_',$_POST['arch_to_view']);
		$_success = (int)$data[2];
		$_arch_id = $data[3];

		#polovimo podatke arhiva
		$sql_string = "SELECT comment FROM srv_invitations_archive WHERE id = '".$_arch_id."'";
		$sql_query = sisplet_query($sql_string);
		list($comment) = mysqli_fetch_row($sql_query);

		echo '<div class="inv_FS_content">';
		echo $lang['srv_invitation_comment'];
		echo '<input id="inv_arch_id" type="hidden" value="'.$_arch_id.'">';
		echo '<input id="inv_arch_comment" type="text" value="'.$comment.'">';
		echo '</div>'; // id="arc_content"
		echo '<div class="inv_FS_btm">';
		echo '<div id="navigationBottom" class="printHide">';
		echo '<span class="floatRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inv_arch_save_comment(); return false;"><span>'.$lang['save'].'</span></a></div></span>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="inv_arch_recipients_close(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
		echo '<div class="clr" />';
		echo '</div>';

		echo '</div>';
	}


	function deleteRecipientConfirm() {
		global $lang;
		
		if (isset($_POST['inv_rid']) && trim($_POST['inv_rid']) != '') {
			$rid = $_POST['inv_rid'];
			echo '<div id="inv_delete_rec_confirm">';


			echo '<span class="h2">Ali ste prepričani da želite izbrisati respondenta:</span>';
			echo '<br class="clr"/>';
			echo '<br class="clr"/>';

			# polovimo podatke respondenta
			$sql_string = "SELECT * FROM srv_invitations_recipients WHERE id = '".(int)$_POST['inv_rid']."'";
			$sql_query = sisplet_query($sql_string);
			$sql_row = mysqli_fetch_assoc($sql_query);
				
			echo '<div id="inv_error_note" class="hidden"/>';
				
			echo '<input type="hidden" id="inv_rid" value="'.$sql_row['id'].'">';
			echo '<table id="inv_edit_recipient">';
			#email
			if (trim($sql_row['email']) != '') {
				echo '<tr><th>'.$lang['srv_inv_field_email'].'</th><td>';
				echo $sql_row['email'];
				echo '</td></tr>';
			}
			#geslo
			if (trim($sql_row['password']) != '') {
				echo '<tr><th>'.$lang['srv_inv_field_password'].'</th><td>';
				echo $sql_row['password'];
				echo '</td></tr>';
			}
			#ime
			if (trim($sql_row['firstname']) != '') {
				echo '<tr><th>'.$lang['srv_inv_field_firstname'].'</th><td>';
				echo $sql_row['firstname'];
				echo '</td></tr>';
			}
			#priimek
			if (trim($sql_row['lastname']) != '') {
				echo '<tr><th>'.$lang['srv_inv_field_lastname'].'</th><td>';
				echo $sql_row['lastname'];
				echo '</td></tr>';
			}
			#naziv
			if (trim($sql_row['salutation']) != '') {
				echo '<tr><th>'.$lang['srv_inv_field_salutation'].'</th><td>';
				echo $sql_row['salutation'];
				echo '</td></tr>';
			}
			#telefon
			if (trim($sql_row['phone']) != '') {
				echo '<tr><th>'.$lang['srv_inv_field_phone'].'</th><td>';
				echo $sql_row['phone'];
				echo '</td></tr>';
			}
			#drugo
			if (trim($sql_row['custom']) != '') {
				echo '<tr><th>'.$lang['srv_inv_field_custom'].'</th><td>';
				echo $sql_row['custom'];
				echo '</td></tr>';
			}
			#odnos
			if(SurveyInfo::getInstance()->checkSurveyModule('360_stopinj')){
				if (trim($sql_row['relation']) != '') {
					echo '<tr><th>'.$lang['srv_inv_field_relation'].'</th><td>';
					echo $sql_row['relation'];
					echo '</td></tr>';
				}
			}
			echo '</table>';
				
			echo '<br class="clr"/>';
			echo '<br class="clr"/>';
			echo '<span id="inv_delete_recipent" class="buttonwrapper floatRight" title="'.$lang['srv_inv_list_profiles_delete'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inv_delete_recipient();return false;" ><span>'.$lang['srv_inv_list_profiles_delete'].'</span></a></span>';

			echo '<span class="buttonwrapper floatRight spaceRight"  title="'.$lang['srv_cancel'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#fade\').fadeOut(\'slow\');$(\'#fullscreen\').fadeOut(\'slow\').html(\'\');return false;" ><span>'.$lang['srv_cancel'].'</span></a></span>';
			echo '<br class="clr"/>';

			echo '</div>'; # id="inv_delete_rec_confirm"
		}
	}
		
	function deleteRecipient() {
		global $lang, $global_user_id, $site_url;
		
		$return = array('success'=>'0');

		#array z napakami
		$errors = array();
		$rids = $_POST['inv_rids'];
		if (isset($rids) && is_array($rids) && count($rids)) {
			$sqlString = "UPDATE srv_invitations_recipients SET deleted='1', date_deleted=NOW(), uid_deleted='".$global_user_id."' WHERE ank_id='".$this->sid."' AND id IN(".implode(',',$rids).")";
			$sqlQuery = sisplet_query($sqlString);
			sisplet_query("COMMIT");

			if (!$sqlQuery) {
				$errors[] = $lang['srv_inv_recipient_delete_error'];
			} else {
				# updejtamo še srv_users
				$sqlString = "UPDATE srv_user SET inv_res_id=NULL WHERE ank_id='".$this->sid."' AND inv_res_id IN(".implode(',',$rids).")";
				$sqlQuery = sisplet_query($sqlString);
				sisplet_query("COMMIT");
				
				$return['success'] = 2;
				//$this->viewRecipients();
			}
		} else {
			$errors[] = $lang['srv_inv_recipient_delete_error'];
		}
		
		header('location: ' . $site_url . 'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=view_recipients');
	}

	function deleteRecipientSingle() {
		global $lang, $global_user_id, $site_url;
		
		$return = array('success'=>'0');

		# single delete
		$inv_rid = $_POST['inv_rid'];
		if ((int)$inv_rid > 0) {
			$sqlString = "UPDATE srv_invitations_recipients SET deleted='1', date_deleted=NOW(), uid_deleted='".$global_user_id."' WHERE ank_id='".$this->sid."' AND id ='$inv_rid'";
			sisplet_query("COMMIT");
			$sqlQuery = sisplet_query($sqlString);
			if (!$sqlQuery) {
				$return['error'] = $lang['srv_inv_recipient_delete_error'];
			} else {
				$return['success'] = 1;
				echo json_encode($return);
				exit;
			}
		} else {
			$return['error'] = $lang['srv_inv_recipient_delete_error'];
		}
		
		echo json_encode($return);
		exit;
	}

	function deleteRecipientAll() {
		global $lang, $global_user_id, $site_url;
		
		$return = array('success'=>'0');
                
		# all delete
		$sqlString = "UPDATE srv_invitations_recipients SET deleted='1', date_deleted=NOW(), uid_deleted='".$global_user_id."' WHERE ank_id='".$this->sid."' AND deleted ='0'";
		sisplet_query("COMMIT");
		$sqlQuery = sisplet_query($sqlString);
		if (!$sqlQuery) {
			$return['error'] = $lang['srv_inv_recipient_delete_error'];
		} else {
			$return['success'] = 1;
			echo json_encode($return);
			exit;
		}
		
		echo json_encode($return);
		exit;
	}

	function addSystemVariables($variables) {
		global $site_path, $lang;
		
        // Pri modulu za volitve so responsi anonimni, zato nimamo nobenih sistemskih spremenljivk
        if(SurveyInfo::getInstance()->checkSurveyModule('voting'))
            return;

		$system_fields = array(
				'inv_field_email' => 'email',
				'inv_field_firstname' => 'ime',
				'inv_field_lastname' => 'priimek',
				#			'inv_field_password' => 'geslo', # gesla ne dodajamo kot sistemsko spremenljivko
				'inv_field_salutation' => 'naziv',
				'inv_field_phone' => 'telefon',
				'inv_field_custom' => 'drugo',
				'inv_field_relation' => 'odnos',
		);

		$ba = new BranchingAjax($this->sid);

		if (count($variables) > 0) {
			// zakaj je bi ta reverse???
			//$variables = array_reverse($variables,true);
			foreach ($variables as $var) {
				if (isset($system_fields[$var])) {
					$spr_id = null;
					
					$variable = $system_fields[$var];

					$sqlVariable = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.variable='".$variable."' AND s.gru_id=g.id AND g.ank_id='".$this->sid."'");
					if (mysqli_num_rows($sqlVariable) == 0 && $variable!='pass') { // če varabla še ne obstaja jo kreiramo
						// za polje pass - Geslo ne kreiramo sistemske variable

						if ($variable != 'language') $user_base = 1;

						// za polje odnos (module 360 - adecco) ustvarimo radio tip spremenljivke
						if($system_fields[$var] == 'odnos'){						
							ob_start();
							
							$ba->ajax_spremenljivka_new(0, 0, 1, 0, 1);
							$spr_id = $ba->spremenljivka;
							
							ob_clean();
							 
							$s = sisplet_query("UPDATE srv_spremenljivka SET variable='".$variable."', variable_custom='1', naslov='".$variable."', sistem='1', visible='0' WHERE id='$spr_id'");
							if (!$s) echo 'err435'.mysqli_error($GLOBALS['connect_db']);
							
							// če gre za sistemsko "odnos" za module 360 (adecco) ustvarimo 4 vrednosti (nadrejeni, podrejeni, sodelavec, samoocenjevalec)
							$sql = sisplet_query("UPDATE srv_vrednost SET naslov='".$lang['srv_inv_field_relation_1']."', variable='1' WHERE spr_id='".$spr_id."' AND vrstni_red='1'");
							$sql = sisplet_query("UPDATE srv_vrednost SET naslov='".$lang['srv_inv_field_relation_2']."', variable='2' WHERE spr_id='".$spr_id."' AND vrstni_red='2'");
							$sql = sisplet_query("UPDATE srv_vrednost SET naslov='".$lang['srv_inv_field_relation_3']."', variable='3' WHERE spr_id='".$spr_id."' AND vrstni_red='3'");
							$sql = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red) VALUES ('', '$spr_id', '".$lang['srv_inv_field_relation_4']."', '4', '4')");
						}
						// dodamo novo spremenljivko na konec, tip je 21
						else{
							ob_start();
							
							$ba->ajax_spremenljivka_new(0, 0, 1, 0, 21);
							$spr_id = $ba->spremenljivka;
							
							ob_clean();
							 
							$s = sisplet_query("UPDATE srv_spremenljivka SET variable='".$variable."', variable_custom='1', naslov='".$variable."', sistem='1', visible='0' WHERE id='$spr_id'");
							if (!$s) echo 'err435'.mysqli_error($GLOBALS['connect_db']);

							#MAPPING za povezavo podatkov
							# če smo dodajali email, ga dodamo tudi v mapping
							if ($variable == 'email' && (int)$spr_id > 0) {
								$insertString = "INSERT INTO srv_invitations_mapping (sid, spr_id, field) VALUES ('$this->sid','$spr_id','email')";
								sisplet_query($insertString);
							}
						}
					}
				}
			}
		}
			
	}

	function listRecipientsProfiles() {
		global $lang, $global_user_id;
		
		$ppid = isset($_POST['pid']) ? (int)$_POST['pid'] : -1;
		
		# polovimo vse profile
		$array_profiles = array();
		
		session_start();
		
		# če obstaja seznam iz seje za to anketo
		if (isset($_SESSION['inv_rec_profile'][$this->sid])) {
			$array_profiles[-1] = array('name' => $_SESSION['inv_rec_profile'][$this->sid]['name']);
		}
		$array_profiles[0] = array('name' => $lang['srv_temp_profile_author']);
		

		$onlyThisSurvey = (isset($_SESSION['inv_rec_only_this_survey']) && (int)$_SESSION['inv_rec_only_this_survey'] == 1) ? false : true;
		if ($onlyThisSurvey == 0) {
			#id-ji profilov do katerih lahko dostopamo
			$sql_string = "SELECT * FROM srv_invitations_recipients_profiles WHERE uid in('".$global_user_id."') OR pid IN (SELECT DISTINCT pid FROM srv_invitations_recipients_profiles_access where uid = '$global_user_id')";
			$sql_query = sisplet_query($sql_string);
		} else {
			# 1
			$sql_string = "SELECT rp.* FROM srv_invitations_recipients_profiles AS rp WHERE from_survey = '$this->sid'";
			$sql_query = sisplet_query($sql_string);
		}
		
		$sql_query = sisplet_query($sql_string);
		while ($sql_row = mysqli_fetch_assoc($sql_query)) {
			$array_profiles[$sql_row['pid']] = array('name' => $sql_row['name']);
		}
		echo '<div id="inv_import_list_profiles">';

		echo '<ol>';
		foreach ($array_profiles AS $_pid => $profile) {
			echo '<li pid="'.$_pid.'" class="'
			#				.($_pid['isdefault'] == 1 ? ' strong' : '')
			.($ppid === $_pid ? ' active' : '')
			.'">';
			echo $profile['name'];
			echo '</li>';
		}
		echo '</ol>';
		echo '</div>';
		echo '<br class="clr" />';
		if ((int)$ppid > 0) {
			# polovimo še ostale porfile
			$sql_string = "SELECT * FROM srv_invitations_recipients_profiles WHERE pid='".(int)$ppid."' AND from_survey ='".$this->sid."' ";
			$sql_query = sisplet_query($sql_string);

			if (mysqli_num_rows($sql_query) > 0) {
				# če je iz iste ankete, potem lahko urejamo
				echo '<a href="#" onclick="inv_del_rec_profile();" title="'.$lang['srv_inv_recipients_delete_profile'].'">'.$lang['srv_inv_recipients_delete_profile'].'</a><br/>';
				echo '<a href="#" onclick="inv_edit_rec_profile();" title="'.$lang['srv_inv_recipients_edit_profile'].'">'.$lang['srv_inv_recipients_edit_profile'].'</a><br/>';
				echo '<br class="clr"/>';
			}
		}

		echo '<br class="clr" />';
	}

	function getRecipientsProfile($pid) {
		global $lang, $global_user_id;

		session_start();
		
		$fields = array();
		$recipients_list = null;	
		$noEmailing = SurveySession::get('inv_noEmailing');

		# če ne obstaja začasen seznam ga naredimo (praznega)
		if (!isset($_SESSION['inv_rec_profile'][$this->sid])) {

			$_SESSION['inv_rec_profile'][$this->sid] = array(
                'pid'           => -1,
                'name'          => $lang['srv_invitation_new_templist'],
                'fields'        => ($noEmailing == 1 ? 'firstname,lastname' : 'email'),
                'respondents'   => '',
                'comment'       => $lang['srv_invitation_new_templist']
            );
		}

		#polovimo emaile in poljaiz seznama
		if ($pid > 0) {

			# če imamo pid in je večji kot nič polovimo podatke iz tabele
            $sql_query = sisplet_query("SELECT fields,respondents FROM srv_invitations_recipients_profiles WHERE pid = '".$pid."'");
            $sql_row = mysqli_fetch_assoc($sql_query);
            
            if (trim($sql_row['respondents']) != '') {
                //$recipients_list = explode("\n",trim($sql_row['respondents']));
                // Zamenjamo 1ka delimiter z default vejico, ker drugače je v seznamih porušeno
                $recipients_list = explode("\n",str_replace ("|~|", ",", trim($sql_row['respondents'])));
            }

            $_fields = explode(",", $sql_row['fields']);

            if (count($_fields) > 0) {
                foreach ($_fields AS $field) {
                    $fields[] =  'inv_field_'.$field;
                }
            }
		}
        else if ($pid == 0) {
			
            # če ne je začasin porfil - avtor
			$sql_query = sisplet_query("SELECT email, name, surname FROM users WHERE id = '".$global_user_id."'");
			$rowEmail = mysqli_fetch_assoc($sql_query);

			// default smo rekli je vejica, ane?
            $recipients_list[] = $rowEmail['email'];
            //$recipients_list[] = $rowEmail['email'].','.$rowEmail['name'].','.$rowEmail['surname'];

			$fields[]= 'inv_field_email';
			/*$fields[]= 'inv_field_firstname';
			$fields[]= 'inv_field_lastname';*/
		} 
        else if ($pid == -1) {
			# začasen profil iz seje
			$_fields = explode(",",$_SESSION['inv_rec_profile'][$this->sid]['fields']);
			
            if (count($_fields) > 0) {
				foreach ($_fields AS $field) {
					$fields[] =  'inv_field_'.$field;
				}
			}

			if (trim($_SESSION['inv_rec_profile'][$this->sid]['respondents']) != '') {
				$recipients_list = explode("\n",trim($_SESSION['inv_rec_profile'][$this->sid]['respondents']));
			}

		} 
        else {
			$recipients_list[] = '';
			$fields[]= 'inv_field_email';
		}

		return array($recipients_list,$fields);

	}

	function useRecipientsList($profile_id = null) {
		if (isset($profile_id) && !is_null($profile_id))
		{
			$pid = $profile_id;
			$_POST['pid'] = $profile_id;
		}
		else if (isset($_POST['pid'])) 
		{
			$pid = (int)$_POST['pid'];
		}
		else 
		{
			if (isset($_SESSION['inv_rec_profile'][$this->sid])) 
			{
				$pid = -1;
			}
			else
			{
				$pid = 0;
			}
		}

		list($recipients_list,$fields) = $this->getRecipientsProfile($pid);

		$this->addRecipientsView($fields,$recipients_list);
	}

	function saveRecipientList() {
		global $lang,$site_url, $global_user_id;
		echo 'DEPRCATED!';
		return false;
		$return = array('success'=>'0');
		
		# shranjujemo v nov profil
		$post_fields = str_replace('inv_field_','',implode(',',$_POST['fields']));
		$post_recipients = $this->getCleanString($_POST['recipients_list']);
		
		$pid = (int)$_POST['pid'];
		# če je pid < 0 shranimo v nov porfil
		if ($pid <= 0) {
			# dodelimo ime
			#zaporedno številčimo ime seznama1,2.... če slučajno ime že obstaja
			$new_name = $lang['srv_inv_recipient_list_new'];
			$names = array();
			$s = "SELECT name FROM srv_invitations_recipients_profiles WHERE name LIKE '%".$new_name."%' AND uid='$global_user_id'";
			$q = sisplet_query($s);
			while ($r = mysqli_fetch_assoc($q)) {
				$names[] = $r['name'];
			}
			if (count($names) > 0) {
				$cnt = 1;
				while (in_array($lang['srv_inv_recipient_list_new'].$cnt, $names)) {
					$cnt++;
				}
				$new_name = $lang['srv_inv_recipient_list_new'].$cnt;
			}
			$sql_insert = "INSERT INTO srv_invitations_recipients_profiles".
					" (name,uid,fields,respondents,insert_time,comment, from_survey) ".
					" VALUES ('$new_name', '$global_user_id', '$post_fields', '$post_recipients', NOW(), '', '".$this->sid."' )";
			$sqlQuery = sisplet_query($sql_insert);
			if (!$sqlQuery) {
				$return['success'] = '0';
				$return['msg'] = mysqli_error($GLOBALS['connect_db']);
			} else {
				$return['success'] = '1';
				$return['pid'] = mysqli_insert_id($GLOBALS['connect_db']);
				
			}
		
		} else {
			# updejtamo obstoječ profil
			$sql_update = " UPDATE srv_invitations_recipients_profiles".
					" SET fields = '$post_fields', respondents ='$post_recipients' WHERE pid = '$pid'";
		
			$sqlQuery = sisplet_query($sql_update);
			if (!$sqlQuery) {
				$return['success'] = '0';
				$return['msg'] = mysqli_error($GLOBALS['connect_db']);
			} else {
				$return['success'] = '1';
				$return['pid'] = $pid;
			}
		}
		sisplet_query("COMMIT");
		echo json_encode($return);
		exit;
		
	}
	
	function getProfileName() {
		echo 'DEPRECATED';
	}

	function saveRecProfile() {
		global $lang, $site_url, $global_user_id;

		$return = array('error'=>'0');
		$profile_id = isset($_POST['profile_id'])? (int)$_POST['profile_id'] : -1;
		$profile_name = (isset($_POST['profile_name']) && trim($_POST['profile_name']) != '') ? trim($_POST['profile_name']) : $lang['srv_invitation_new_templist'];
		$profile_comment = (isset($_POST['profile_comment']) && trim($_POST['profile_comment']) != '') ? trim($_POST['profile_comment']) : '';
		$recipients_list = trim($this->getCleanString($_POST['recipients_list']));
		$field_list = (isset($_POST['field_list']) && trim($_POST['field_list']) != '') ? trim($_POST['field_list']) : 'email';

		if ((int)$profile_id == -1) {
			# shranimo v začasni profil
			session_start();
			$_SESSION['inv_rec_profile'][$this->sid] = array(
					'pid'=>-1,
					'name'=>$lang['srv_invitation_new_templist'],
					'fields'=>$field_list,
					'respondents'=>$recipients_list,
					'comment'=>$profile_comment
			);
			$return = array('error'=>'0', 'msg'=>'x0', 'pid'=>-1);
		}
		else if ((int)$profile_id == 0) {
			# shranjujemo v nov profil
			$sql_insert = "INSERT INTO srv_invitations_recipients_profiles (name,uid,fields,respondents,insert_time,comment, from_survey) VALUES ('$profile_name', '$global_user_id', '$field_list', '$recipients_list', NOW(), '$profile_comment', '".$this->sid."' )";
			$sqlQuery = sisplet_query($sql_insert);
				
			if (!$sqlQuery) {
				$error = mysqli_error($GLOBALS['connect_db']);
				$return = array('error'=>'1', 'msg'=>$error, 'pid'=>mysqli_insert_id($GLOBALS['connect_db']));
			} else {
				$return = array('error'=>'0', 'msg'=>'x1', 'pid'=>mysqli_insert_id($GLOBALS['connect_db']));
			}
			sisplet_query("COMMIT");
		} 
		else {
			# dodajamo v obstoječ profil
			# polovimo podatke obstoječega profila
			$sql_string = "SELECT * FROM srv_invitations_recipients_profiles WHERE uid in('".$global_user_id."') AND pid = '".$profile_id."'";
			$sql_query = sisplet_query($sql_string);
			$sql_row = mysqli_fetch_assoc($sql_query);
			$respondents = $sql_row['respondents']."\n".$recipients_list;
				
			$sql_string_update = "UPDATE srv_invitations_recipients_profiles SET respondents = '".$respondents."', comment='".$profile_comment."' WHERE uid in('".$global_user_id."') AND pid = '".$profile_id."'";
			$sqlQuery = sisplet_query($sql_string_update);
			sisplet_query("COMMIT");
			if (!$sqlQuery) {
				$error = mysqli_error($GLOBALS['connect_db']);
				$return = array('error'=>'1', 'msg'=>$error, 'pid'=>$profile_id());
			} else {
				$return = array('error'=>'0', 'msg'=>'x2', 'pid'=>$profile_id);
			}
		}
		
		echo json_encode($return);
		
		exit;
	}
	
	function updateRecProfile() {
		global $lang,$site_url, $global_user_id;
		
		$return = array('error'=>'0', 'msg'=>'');
		$pid = (int)(int)$_POST['pid'];	
		$profile_name = (isset($_POST['profile_name']) && trim($_POST['profile_name']) != '') ? trim($_POST['profile_name']) : '';
		
		/*$profile_comment = (isset($_POST['profile_comment']) && trim($_POST['profile_comment']) != '') ? trim($_POST['profile_comment']) : '';
		$profile_respondents = (isset($_POST['profile_respondents']) && trim($_POST['profile_respondents']) != '') ? trim($_POST['profile_respondents']) : '';*/
		
		if ($pid > 0) {
				
			if ($profile_name != '') {
				//$sql_update = "UPDATE srv_invitations_recipients_profiles SET name = '$profile_name', comment = '$profile_comment', respondents = '$profile_respondents' WHERE pid = '$pid'";
				$sql_update = "UPDATE srv_invitations_recipients_profiles SET name = '$profile_name' WHERE pid = '$pid'";
				$sqlQuery = sisplet_query($sql_update);
				sisplet_query("COMMIT");
				if (!$sqlQuery) {
					$error = mysqli_error($GLOBALS['connect_db']);
					$return = array('error'=>'1', 'msg'=>$error);
				} else {
					$return = array('error'=>'0', 'msg'=>$sql_update);
						
				}
				sisplet_query("COMMIT");
			} else {
				$return = array('error'=>'1', 'msg'=>$lang['srv_inv_msg_1']);
			}
				
		} else {
			$return = array('error'=>'1', 'msg'=>$lang['srv_inv_msg_2']);
		}

		echo json_encode($return);
		
		exit;
	}

	function deleteRecProfile() {
		global $lang, $site_url, $global_user_id;
		
		$return = array('error'=>'0');
		$pid = isset($_POST['pid']) && (int)$_POST['pid'] > 0 ? (int)$_POST['pid'] : null;

		if ($_POST['pid']) {				
			$sql_string = "DELETE FROM srv_invitations_recipients_profiles WHERE pid='".$pid."' AND uid='".$global_user_id."'";
			$return['str'] = $sql_string;
			$sqlQuery = sisplet_query($sql_string);
			if (!$sqlQuery) {
				$error = mysqli_error($GLOBALS['connect_db']);
				$return['error'] = '1';
				$return['msg'] = $error;
			}
			sisplet_query("COMMIT");
		}
		
		echo json_encode($return);
		
		exit;
	}

	function editRecProfile() {
		global $lang, $site_url, $global_user_id;
		
		$return = array('error'=>'0');
		$pid = (int)$_POST['pid'];
		
		if ($pid > 0) {
			$sql_string = "SELECT * FROM srv_invitations_recipients_profiles WHERE pid='".$pid."'";
			$sqlQuery = sisplet_query($sql_string);
			$sqlRow = mysqli_fetch_assoc($sqlQuery);
				
            echo '<div id="inv_recipients_profile_name">';
            
            echo '<div id="inv_error_note" class="hidden"></div>';
            
			echo '<table>';
			echo '<tr><td class="bold">'.$lang['srv_inv_recipient_list_name'].'</td>';
			echo '<td>';
			echo '<input type="text" id="rec_profile_name" value="'.$sqlRow['name'].'" autofocus="autofocus" style="width: 200px;">';
			echo '</td></tr>';
			echo '</table>';
				
			echo '<input type="hidden" id="rec_profile_pid" value="'.$pid.'" >';
				
			echo '<br class="clr" />';
			echo '<span class="buttonwrapper floatRight spaceRight"  title="'.$lang['srv_cancel'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#fade\').fadeOut(\'slow\');$(\'#fullscreen\').fadeOut(\'slow\').html(\'\');return false;" ><span>'.$lang['srv_cancel'].'</span></a></span>';
			echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['save'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inv_update_rec_profile(); return false;"><span>'.$lang['save'].'</span></a></span>';
			echo '<br class="clr" />';
            
            echo '</div>'; # id="inv_view_arch_recipients"
				
			sisplet_query("COMMIT");
		}
		
		echo json_encode($return);
		
		exit;
	}

	function deleteMsgProfile() {
		global $lang, $site_url, $global_user_id;

		$return = array('error'=>'0');
		$mid = isset($_POST['mid']) && (int)$_POST['mid'] > 0 ? (int)$_POST['mid'] : null;

		# preštejemo koliko profilov imamo. Zadnjega ne pustimo izbrisati
		$sql_string = "SELECT id FROM srv_invitations_messages WHERE ank_id = '$this->sid' AND id <> '".$mid."' LIMIT 1";
		$sql_query = sisplet_query($sql_string);
		list($id) = mysqli_fetch_row($sql_query);

		if ((int)$id > 0 ) {
			# nastavimo na nov id
			$sql_string = "UPDATE srv_invitations_messages SET isdefault = '1' WHERE ank_id = '$this->sid' AND id='$id'";
			$sqlQuery = sisplet_query($sql_string);

			# če imamo še kak profil pustimo zbrisat izbranega
			if ((int)$mid > 0) {

				$sql_string = "DELETE FROM srv_invitations_messages WHERE id='".$mid."'";
				$return['str'] = $sql_string;
				$sqlQuery = sisplet_query($sql_string);
				if (!$sqlQuery) {
					$error = mysqli_error($GLOBALS['connect_db']);
					$return['error'] = '1';
					$return['msg'] = $error;
				}

			}
			sisplet_query("COMMIT");
			$this->viewMessage($id);

		}

		#$this->viewMessage();
		# echo json_encode($return);
		# exit;
	}

	function showMessageRename() {
		global $lang;
		$mid = (int)$_POST['mid'];

		echo '<div id="inv_recipients_profile_name">';
		echo $lang['srv_inv_message_rename_new_name'].'&nbsp;';

		# polovimo vsa sporočila
		$sql_string = "SELECT naslov, comment FROM srv_invitations_messages WHERE ank_id = '$this->sid' AND id = '$mid'";
		list($naslov, $comment) = mysqli_fetch_row(sisplet_query($sql_string));

		echo '<input type="text" id="inv_message_profile_name" value="'.$naslov.'" tabindex="1" autofocus="autofocus">';
		echo '<br/><br/>';
		echo $lang['srv_inv_message_draft_list_comment'];
		echo '<textarea id="inv_message_comment" tabindex="3" rows="2" style="width:200px;">'.($comment).'</textarea>';

			
		echo '<br class="clr" /><br class="clr" />';
		echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['save'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="invMessageRename(); return false;"><span>'.$lang['save'].'</span></a></span>';
		echo '<span class="buttonwrapper floatRight spaceRight"  title="'.$lang['srv_cancel'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#fade\').fadeOut(\'slow\');$(\'#fullscreen\').fadeOut(\'slow\').html(\'\');return false;" ><span>'.$lang['srv_cancel'].'</span></a></span>';

		echo '<br class="clr" />';
		echo '</div>';
	}


	function messageRename() {
		global $lang;

		$return = array('msg'=>'', 'error'=>'0');

		$mid = (int)$_POST['mid'];
		$return['mid'] = $mid;

		$name = trim($_POST['name']);
		$comment = trim($_POST['comment']);

		if ($name == '' || $name == null) {
			$name = $this->generateMessageName();
		}

		if ($mid > 0) {
			#updejtamo obstoječ profil
			$sql_string = "UPDATE srv_invitations_messages SET naslov='".$name."', comment='".$comment."', edit_uid='".$global_user_id."', edit_time=NOW() WHERE ank_id = '$this->sid' AND id='$mid'";
			$sqlQuery = sisplet_query($sql_string);

			if ( $sqlQuery != 1) {
				$return['error'] = '1';
				$return['msg'] .= $newline.$lang['srv_inv_msg_4'];
			}
			sisplet_query("COMMIT");

		} else {
			$return['error'] = '1';
			$return['msg'] .= $newline.$lang['srv_inv_msg_4'];
		}

		echo json_encode($return);
		exit;

	}
	function editRecipient() {
		global $lang;
		
		echo '<div id="inv_recipient_edit">';

        echo '<h2>Urejanje respondenta</h2>';
                
		if ((int)$_POST['inv_rid'] > 0) {
			# polovimo podatke respondenta
			$sql_string = "SELECT * FROM srv_invitations_recipients WHERE id = '".(int)$_POST['inv_rid']."'";
			$sql_query = sisplet_query($sql_string);
			$sql_row = mysqli_fetch_assoc($sql_query);
				
			echo '<div id="inv_error_note" class="hidden"/>';
				
			echo '<input type="hidden" id="inv_rid" value="'.$sql_row['id'].'">';
			echo '<table id="inv_edit_recipient">';
			#email
			echo '<tr><th>'.$lang['srv_inv_field_email'].'</th><td>';
			echo '<input type="text" id="rec_email" value="'.$sql_row['email'].'" autocomplete="off" maxlength="100">';
			echo '</td></tr>';
			#geslo
			echo '<tr><th>'.$lang['srv_inv_field_password'].'</th><td>';
			echo '<input type="text" id="rec_password" value="'.$sql_row['password'].'" autocomplete="off" maxlength="45">';
			echo '</td></tr>';
			#ime
			echo '<tr><th>'.$lang['srv_inv_field_firstname'].'</th><td>';
			echo '<input type="text" id="rec_firstname" value="'.$sql_row['firstname'].'" autocomplete="off" maxlength="45">';
			echo '</td></tr>';
			#priimek
			echo '<tr><th>'.$lang['srv_inv_field_lastname'].'</th><td>';
			echo '<input type="text" id="rec_lastname" value="'.$sql_row['lastname'].'" autocomplete="off" maxlength="45">';
			echo '</td></tr>';
			#naziv
			echo '<tr><th>'.$lang['srv_inv_field_salutation'].'</th><td>';
			echo '<input type="text" id="rec_salutation" value="'.$sql_row['salutation'].'" autocomplete="off" maxlength="45">';
			echo '</td></tr>';
			#telefon
			echo '<tr><th>'.$lang['srv_inv_field_phone'].'</th><td>';
			echo '<input type="text" id="rec_phone" value="'.$sql_row['phone'].'" autocomplete="off" maxlength="45">';
			echo '</td></tr>';
			#drugo
			echo '<tr><th>'.$lang['srv_inv_field_custom'].'</th><td>';
			echo '<input type="text" id="rec_custom" value="'.$sql_row['custom'].'" autocomplete="off" maxlength="100">';
			echo '</td></tr>';
			#odnos
			if(SurveyInfo::getInstance()->checkSurveyModule('360_stopinj')){
				echo '<tr><th>'.$lang['srv_inv_field_relation'].'</th><td>';
				echo '<input type="text" id="rec_relation" value="'.$sql_row['relation'].'" autocomplete="off" maxlength="100">';
				echo '</td></tr>';
			}
				
			echo '</table>';
				
			echo '<br class="clr"/>';
			echo '<br class="clr"/>';
			echo '<span id="save_recipients" class="buttonwrapper floatRight" ><a class="ovalbutton ovalbutton_orange" href="#" onclick="inv_save_recipient();return false;" ><span>'.$lang['srv_inv_recipient_save'].'</span></a></span>';

		}
		echo '<span class="buttonwrapper floatRight spaceRight"  title="'.$lang['srv_cancel'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#fade\').fadeOut(\'slow\');$(\'#fullscreen\').fadeOut(\'slow\').html(\'\');return false;" ><span>'.$lang['srv_cancel'].'</span></a></span>';
		echo '<br class="clr"/>';
		echo '</div>';
	}

	function saveRecipient() {
		global $lang;

		$return = array('msg'=>$lang['srv_inv_error1'], 'error'=>'0');

		$rid = (int)trim($_POST['inv_rid']);
		$rec_email = trim($_POST['rec_email']);
		$rec_password = trim($_POST['rec_password']);
		$rec_firstname = trim($_POST['rec_firstname']);
		$rec_lastname = trim($_POST['rec_lastname']);
		$rec_salutation = trim($_POST['rec_salutation']);
		$rec_phone = trim($_POST['rec_phone']);
		$rec_custom = trim($_POST['rec_custom']);
		$rec_relation = (int)trim($_POST['rec_relation']);

		$return['rid'] = $rid;

		$sql_string = "SELECT email FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND id = '".$rid."'";
		$sql_query = sisplet_query($sql_string);
		$sql_row = mysqli_fetch_assoc($sql_query);

		$newline= '<br/>';
		# če smo imeli polje email ga preverjamo
		if ($sql_row['email'] != null || trim($sql_row['email']) != '' || ($rec_email != null && $rec_email != '')) {
			# email ne sme biti prazen
			if (($sql_row['email'] != null || trim($sql_row['email']) != '') && ($rec_email == null || $rec_email == '')) {
			$return['error'] = '1';

			$return['msg'] .= $newline.$lang['srv_inv_error2'];
			$return['error_email'] = '1';
			$newline= '<br/>';
		} else if (!$this->validEmail($rec_email)) {
			# email mora biti pravilne oblike
			$return['error'] = '1';

			$return['msg'] .= $newline.$lang['srv_inv_error3'];
			$return['error_email'] = '1';
			$newline= '<br/>';
		}
		}
		# password ne sme biti prazen
		if ($rec_password == null || $rec_password == '') {
			$return['error'] = '1';
				
			$return['msg'] .= $newline.$lang['srv_inv_error4'];
			$return['error_password'] = '1';
			$newline= '<br/>';
		} else {
			#preverimo da geslo še ni uporabljeno za to anketo za katerega drugega respondenta
			$sql_string = "SELECT * FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND password = '".$rec_password."' AND id != '".$rid."'";
			$sql_query = sisplet_query($sql_string);
			if (mysqli_num_rows($sql_query) > 0) {
				$return['error'] = '1';

				$return['msg'] .= $newline.$lang['srv_inv_error5'];
				$return['error_password'] = '1';
				$newline= '<br/>';
			}
		}

		# če ni napak shranimo
		if ( $return['error'] == '0') {
			# ali shranjujemo obstoječ msg
			$sql_string = "UPDATE srv_invitations_recipients SET"
			." email = '".strtolower($rec_email)."',"
			." password = '$rec_password',"
			." firstname = '$rec_firstname',"
			." lastname = '$rec_lastname',"
			." salutation = '$rec_salutation',"
			." phone = '$rec_phone',"
			." custom = '$rec_custom',"
			." relation = '$rec_relation'"
			." WHERE ank_id = '$this->sid' AND id='$rid'";
			$sqlQuery = sisplet_query($sql_string);
	
			sisplet_query("COMMIT");
			if ( $sqlQuery != 1) {
				$return['error'] = '1';
	
				$return['msg'] .= $newline.$lang['srv_inv_error6'];
			}
		}
		
		# MAP: če imamo mapirano, updejtamo tudi pri podatkih
		$strMap = "SELECT spr_id FROM srv_invitations_mapping WHERE sid = '".$this->sid."' AND field='email'";
		$qryMap = sisplet_query($strMap);
		list($mapSprId) = mysqli_fetch_row($qryMap);
		if ((int)$mapSprId > 0) {
			# preverimo ali ima respondent povezavo na srv_user
			$selectUser = "SELECT id FROM srv_user where ank_id='".$this->sid."' AND inv_res_id='$rid' AND deleted='0'";
			$qryUser = sisplet_query($selectUser);
			list($uid) = mysqli_fetch_row($qryUser);
			
			if ((int)$uid > 0 && $this->validEmail($rec_email)) {
				$updateStr = "UPDATE srv_data_text".$this->db_table." SET text = '$rec_email' WHERE spr_id='$mapSprId' AND usr_id='".(int)$uid."'";
				$qryUpdate = sisplet_query($updateStr);
				if ((int)$qryUpdate > 0) {
					# updejtamo še timestamp userja
					$updateUserString = "UPDATE srv_user SET time_edit=NOW() WHERE id='".(int)$uid."'";
					$qryUserUpdate = sisplet_query($updateUserString);
				}
			}
		}
		
		$sql_string = "SELECT * FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND id = '".$rid."'";
		$sql_query = sisplet_query($sql_string);

		$return['rec'] = mysqli_fetch_assoc($sql_query);
			
		echo json_encode($return);
		exit;
	}

	function setRecipientFilter(){

		session_start();

		if (isset($_POST['inv_filter_on']) && $_POST['inv_filter_on'] == 'true') {
			$_SESSION['inv_filter_on'] = true;
			
		} else {
			$_SESSION['inv_filter_on'] = false;
		}
		
		$_SESSION['inv_filter']['value'] = trim($_POST['inv_filter_value']);
		$_SESSION['inv_filter']['send'] = (int)$_POST['inv_filter_send'];
		$_SESSION['inv_filter']['respondet'] = (int)$_POST['inv_filter_respondet'];
		$_SESSION['inv_filter']['unsubscribed'] =  (int)$_POST['inv_filter_unsubscribed'];
		
		# če ni seznama privzeto damo na vsi
		if (!isset($_POST['inv_filter_list']) && !isset($_SESSION['inv_filter']['list'])) {
			$_SESSION['inv_filter']['list'] = '-2';
		} else {
			$_SESSION['inv_filter']['list'] =  (int)$_POST['inv_filter_list'];
		}
		if (isset($_POST['inv_filter_duplicates']) && $_POST['inv_filter_duplicates'] == 'true') {
			$_SESSION['inv_filter']['duplicated'] = true;
		} else {
			$_SESSION['inv_filter']['duplicated'] = false;
		}
		
		session_commit();
		return;
	}

	function exportRecipients() {
		global $lang;
		
		$convertTypes = array('charSet'	=> 'UTF-8', # windows-1250',
			 'delimit'	=> ';',
			 'newLine'	=> "\n",
			 'BOMchar'	=> "\xEF\xBB\xBF");

		#header('Content-Type: application/octet-stream; charset='.$convertTypes['charSet']);
		header('Content-type: application/csv; charset='.$convertTypes['charSet']);
		header('Content-Transfer-Encoding: binary');
		header('Content-Disposition: attachment; filename="respondenti_anketa_'.$this->sid.'-'.date('Y-m-d').'.csv"');
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private',false);

		ob_clean();
		
		# dodami boomchar za utf-8
		echo $convertTypes['BOMchar'];
		
		#array z napakami
		$errors = array();
		$inv_rids = $_POST['inv_rids'];
		if (is_array($inv_rids) && count($inv_rids) > 0) {
			
			// Ce delamo izvoz za telefonski modul
			if(SurveyInfo::getInstance()->checkSurveyModule('phone')){	
				$delimit = '';
				foreach ($this->inv_variables_tel_excel AS $vkey => $inv_variable) {
					echo $delimit.$lang['srv_inv_recipients_'.$inv_variable];
					$delimit = $convertTypes['delimit'];
				}
							
				#echo $delimit.$lang['srv_inv_recipients_count_inv']; 
				echo $convertTypes['newLine'];
				
				$sqlString = "SELECT sir.*, IF(sirp.name IS NULL, '".$lang['srv_invitation_new_templist_author']."', sirp.name) AS list_name, scm.comment, scs.call_time, sch.status "
				 ." FROM srv_invitations_recipients AS sir"
				 ." LEFT JOIN srv_invitations_recipients_profiles AS sirp ON (sir.list_id = sirp.pid)"
				 ." LEFT JOIN srv_telephone_comment AS scm ON (scm.rec_id = sir.id)"
				 ." LEFT JOIN srv_telephone_schedule AS scs ON (scs.rec_id = sir.id)"
				 ." LEFT JOIN srv_telephone_history AS sch ON (sch.rec_id = sir.id)"

				 ." WHERE sir.id IN(".implode(',',$inv_rids).") ORDER BY id";
				 
				 $sqlQuery = sisplet_query($sqlString);
				if (mysqli_num_rows($sqlQuery)) {
					while ($sql_row = mysqli_fetch_assoc($sqlQuery)) {
						foreach ($this->inv_variables_tel_excel AS $vkey => $inv_variable) {
							if($inv_variable == 'status' && $sql_row[$inv_variable] == '')
								echo $lang['srv_telephone_status_'].$convertTypes['delimit'];
							else
								echo $sql_row[$inv_variable].$convertTypes['delimit'];
						}
						
						echo $convertTypes['newLine'];
					}
				}
			}
			// Izvoz za navadna vabila
			else{	
				$delimit = '';
				foreach ($this->inv_variables_excel AS $vkey => $inv_variable) {
					echo $delimit.$lang['srv_inv_recipients_'.$inv_variable];
					$delimit = $convertTypes['delimit'];
				}
				
				#echo $delimit.$lang['srv_inv_recipients_count_inv']; 
				echo $convertTypes['newLine'];
				
				$sqlString = "SELECT sir.*, IF(sirp.name IS NULL, '".$lang['srv_invitation_new_templist_author']."', sirp.name) AS list_name "
				 ." FROM srv_invitations_recipients AS sir"
				 ." LEFT JOIN srv_invitations_recipients_profiles AS sirp ON (sir.list_id = sirp.pid)"
				#." LEFT JOIN srv_invitations_archive_recipients AS siar ON (sir.id = siar.rec_id)"

				 ." WHERE sir.id IN(".implode(',',$inv_rids).") ORDER BY id";
				 
				 /*
				$sqlString = "SELECT sir.*, IF(sirp.name IS NULL, '".$lang['srv_invitation_new_templist_author']."', sirp.name) AS list_name, count(siar.arch_id) AS count_inv"
				 ." FROM srv_invitations_recipients AS sir"
				 ." LEFT JOIN srv_invitations_recipients_profiles AS sirp ON (sir.list_id = sirp.pid)"
				 ." LEFT JOIN srv_invitations_archive_recipients AS siar ON (sir.id = siar.rec_id)"
				 ." WHERE sir.id IN(".implode(',',$inv_rids).") GROUP BY siar.rec_id ORDER BY id";
				*/
				
				$sqlQuery = sisplet_query($sqlString);
				if (mysqli_num_rows($sqlQuery)) {
					while ($sql_row = mysqli_fetch_assoc($sqlQuery)) {
						foreach ($this->inv_variables_excel AS $vkey => $inv_variable) {
							echo $sql_row[$inv_variable].$convertTypes['delimit'];
						}

	#					echo $sql_row['count_inv'];
						echo $convertTypes['newLine'];
					}
				}
			}			
		} 
		else {
			echo $lang['srv_inv_error7'];
		}
		
		ob_flush();
	}

	function exportRecipients_all() {
		global $lang;
		
		$convertTypes = array('charSet'	=> 'UTF-8', # windows-1250',
				'delimit'	=> ';',
				'newLine'	=> "\n",
				'BOMchar'	=> "\xEF\xBB\xBF");

		#header('Content-Type: application/octet-stream; charset='.$convertTypes['charSet']);
		header('Content-type: application/csv; charset='.$convertTypes['charSet']);
		header('Content-Transfer-Encoding: binary');
		header('Content-Disposition: attachment; filename="respondenti_anketa_'.$this->sid.'-'.date('Y-m-d').'.csv"');
		header('Pragma: public');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Cache-Control: private',false);

		ob_clean();
		# dodami boomchar za utf-8
		echo $convertTypes['BOMchar'];

		
		// Ce delamo izvoz za telefonski modul
		if(SurveyInfo::getInstance()->checkSurveyModule('phone')){		
			#array z napakami
			$errors = array();
			$delimit = '';
			foreach ($this->inv_variables_tel_excel AS $vkey => $inv_variable) {
				echo $delimit.$lang['srv_inv_recipients_'.$inv_variable];
				$delimit = $convertTypes['delimit'];
			}
			
			#echo $delimit.$lang['srv_inv_recipients_count_inv'];
			echo $convertTypes['newLine'];
			
			$sqlString = "SELECT sir.*, IF(sirp.name IS NULL, '".$lang['srv_invitation_new_templist_author']."', sirp.name) AS list_name, scm.comment, scs.call_time, sch.status "
				." FROM srv_invitations_recipients AS sir"
				." LEFT JOIN srv_invitations_recipients_profiles AS sirp ON (sir.list_id = sirp.pid)"
				." LEFT JOIN srv_telephone_comment AS scm ON (scm.rec_id = sir.id)"
				." LEFT JOIN srv_telephone_schedule AS scs ON (scs.rec_id = sir.id)"
				." LEFT JOIN srv_telephone_history AS sch ON (sch.rec_id = sir.id)"

				." WHERE sir.ank_id = '$this->sid' AND deleted='0' ORDER BY id";
				
			$sqlQuery = sisplet_query($sqlString);
			if (mysqli_num_rows($sqlQuery)) {
				while ($sql_row = mysqli_fetch_assoc($sqlQuery)) {
					foreach ($this->inv_variables_tel_excel AS $vkey => $inv_variable) {
						if($inv_variable == 'status' && $sql_row[$inv_variable] == '')
							echo $lang['srv_telephone_status_'].$convertTypes['delimit'];
						else
							echo $sql_row[$inv_variable].$convertTypes['delimit'];
					}

					echo $convertTypes['newLine'];
				}
			}	
		}
		// Izvoz za navadna vabila
		else{			
			#array z napakami
			$errors = array();
			$delimit = '';
			foreach ($this->inv_variables_excel AS $vkey => $inv_variable) {
				echo $delimit.$lang['srv_inv_recipients_'.$inv_variable];
				$delimit = $convertTypes['delimit'];
			}
			
			echo $convertTypes['newLine'];
			
			$sqlString = "SELECT sir.*, IF(sirp.name IS NULL, '".$lang['srv_invitation_new_templist_author']."', sirp.name) AS list_name "
				." FROM srv_invitations_recipients AS sir"
				." LEFT JOIN srv_invitations_recipients_profiles AS sirp ON (sir.list_id = sirp.pid)"
				." WHERE sir.ank_id = '$this->sid' AND deleted='0' ORDER BY id";
							
			$sqlQuery = sisplet_query($sqlString);
			if (mysqli_num_rows($sqlQuery)) {

				while ($sql_row = mysqli_fetch_assoc($sqlQuery)) {
                    
					foreach ($this->inv_variables_excel AS $vkey => $inv_variable) {
						echo $sql_row[$inv_variable].$convertTypes['delimit'];
					}

					echo $convertTypes['newLine'];
				}
			}
		}
		
		ob_flush();
	}

	function onlyThisSurvey() {
		session_start();
		$_SESSION['inv_rec_only_this_survey'] = (isset($_POST['checked']) && $_POST['checked'] == 'true');
	}

	function hightlight($str, $keywords = '') {
		$keywords = preg_replace('/\s\s+/', ' ', strip_tags(trim($keywords))); // filter
		$style = 'inv_high';
		$style_i = 'inv_high_i';
		/* Apply Style */
		$var = '';

		foreach(explode(' ', $keywords) as $keyword)
		{
			$replacement = "<span class='".$style."'>".$keyword."</span>";
			$var .= $replacement." ";
			$str = str_ireplace($keyword, $replacement, $str);
		}

		/* Apply Important Style */

		$str = str_ireplace(rtrim($var), "<span class='".$style_i."'>".$keywords."</span>", $str);

		return $str;
	}
	
	// Dodamo vse userje v bazo podatkov kot respondente
	function add_users_to_database() {

        // Preverimo ce je vklopljen modul za volitve
        $voting = SurveyInfo::getInstance()->checkSurveyModule('voting');

		# prejeminki besedila
		$sql_query = sisplet_query("SELECT id, firstname, lastname, email, password, password, cookie, phone, salutation, custom, relation 
                                        FROM srv_invitations_recipients 
                                        WHERE ank_id = '".$this->sid."' AND deleted='0' AND sent='0'
                                ");

		# polovimo sistemske spremenljivke z vrednostmi
		$qrySistemske = sisplet_query("SELECT s.id, s.naslov, s.variable 
                                        FROM srv_spremenljivka s, srv_grupa g 
                                        WHERE s.sistem='1' AND s.gru_id=g.id AND g.ank_id='".$this->sid."' 
                                            AND variable IN("."'" . implode("','",$this->inv_variables)."') 
                                        ORDER BY g.vrstni_red, s.vrstni_red
                                    ");

		$sys_vars = array();
		$sys_vars_ids = array();

		while ($row = mysqli_fetch_assoc($qrySistemske)) {
			$sys_vars[$row['id']] = array('id'=>$row['id'], 'variable'=>$row['variable'],'naslov'=>$row['naslov']);
			$sys_vars_ids[] = $row['id'];
		}
		
        $sqlVrednost = sisplet_query("SELECT spr_id, id AS vre_id, vrstni_red, variable 
                                        FROM srv_vrednost 
                                        WHERE spr_id IN(".implode(',',$sys_vars_ids).") 
                                        ORDER BY vrstni_red ASC
                                    ");
		while ($row = mysqli_fetch_assoc($sqlVrednost)) {
			
            // Ce gre za odnos imamo radio
			if($sys_vars[$row['spr_id']]['variable'] == 'odnos'){
				if(!isset($sys_vars[$row['spr_id']]['vre_id'][$row['vrstni_red']]))
					$sys_vars[$row['spr_id']]['vre_id'][$row['variable']] = $row['vre_id'];
			}
			elseif (!isset($sys_vars[$row['spr_id']]['vre_id'])) {				
				$sys_vars[$row['spr_id']]['vre_id'] = $row['vre_id'];
			}
		}

		# array za rezultate
		$send_users_data = array();

		# zloopamo skozi prejemnike in personaliziramo sporočila in jih pošljemo
		$date_sent = date ("Y-m-d H:i:s");
		while ($sql_row = mysqli_fetch_assoc($sql_query)) {
			$_user_data = $sql_row;
			$_user_data['status'] = 1;
			$send_users_data[] = $_user_data;
		}

		# dodamo še userja v srv_user da je kompatibilno s staro logiko
		$strInsertDataText = array();
		$strInsertUserbase = array();
		$strInsertUserstatus = array();
		foreach ($send_users_data AS $user_data) {

            // Pri volitvah zaradi anonimizacije ignoriramo vse identifikatorje
            if($voting){
                sisplet_query("INSERT INTO srv_user 
                                (ank_id, cookie, pass, last_status, inv_res_id) 
                                VALUES 
                                ('".$this->sid."', '".$user_data['cookie']."', '".$user_data['password']."', '".$user_data['status']."', '-1') ON DUPLICATE KEY UPDATE last_status=VALUES(last_status)
                            ");

                // Ce ne belezimo parapodatka za cas responsa, anonimno zabelezimo cas zadnjega responsa
                sisplet_query("UPDATE srv_anketa SET last_response_time=NOW() WHERE id='".$this->sid."'");
            }
            else{
                sisplet_query("INSERT INTO srv_user 
                            (ank_id, email, cookie, pass, last_status, time_insert, inv_res_id) 
                            VALUES 
                            ('".$this->sid."', '".$user_data['email']."', '".$user_data['cookie']."', '".$user_data['password']."', '".$user_data['status']."', NOW(), '".$user_data['id']."') ON DUPLICATE KEY UPDATE last_status=VALUES(last_status), inv_res_id=VALUES(inv_res_id)
                        ");
            }

			
			
            $usr_id = mysqli_insert_id($GLOBALS['connect_db']);

			if ($usr_id) {
				# za update v srv_invitations_respondents
				$send_ok_ids[] = $user_data['id'];
			} 
            else {
				$send_error_ids[] = $user_data;
			}

			# dodamo še srv_userbase in srv userstatus
			$strInsertUserbase[] = "('".$usr_id."','0',NOW(),'".$global_user_id."')";
			$strInsertUserstatus[] = "('".$usr_id."', '0', '0', NOW())";
				
			# dodamo še podatke za posameznega userja za sistemske spremenljivke
			foreach ($sys_vars AS $sid => $spremenljivka) {
				$_user_variable = $this->inv_variables_link[$spremenljivka['variable']];
				if (trim($user_data[$_user_variable]) != '' && $user_data[$_user_variable] != null) {
					if($spremenljivka['variable'] == 'odnos')
						$strInsertDataVrednost[] = "('".$sid."','".$spremenljivka['vre_id'][trim($user_data[$_user_variable])]."','".$usr_id."')";
					else
						$strInsertDataText[] = "('".$sid."','".$spremenljivka['vre_id']."','".trim($user_data[$_user_variable])."','".$usr_id."')";
				}
			}
				
			sisplet_query("COMMIT");
		}

		# vstavimo v srv_userbase
		if (count($strInsertUserbase) > 0) {
			$strInsert = "INSERT INTO srv_userbase (usr_id, tip, datetime, admin_id) VALUES ";
			$strInsert .= implode(',',$strInsertUserbase);
			sisplet_query($strInsert);
		}
		# vstavimo v srv_userstatus
		if (count($strInsertUserstatus) > 0) {
			$strInsert = "INSERT INTO srv_userstatus (usr_id, tip, status, datetime) VALUES ";
			$strInsert .= implode(',',$strInsertUserstatus);
			sisplet_query($strInsert);				
		}

        // Pri volitvah zaradi anonimizacije ne vsatvimo nicesar v sistemske spremenljivke
        if(!$voting){

            # vstavimo v srv_data_text
            if (count($strInsertDataText) > 0) {
                $strInsert = "INSERT INTO srv_data_text".$this->db_table." (spr_id, vre_id, text, usr_id) VALUES ";
                $strInsert .= implode(',',$strInsertDataText);
                sisplet_query($strInsert);
            }
            # vstavimo v srv_data_vrednost
            if (count($strInsertDataVrednost) > 0) {
                $strInsert = "INSERT INTO srv_data_vrednost".$this->db_table." (spr_id, vre_id, usr_id) VALUES ";
                $strInsert .= implode(',',$strInsertDataVrednost);
                sisplet_query($strInsert);
            }
        }

		sisplet_query("COMMIT");
			
		# zloopamo skozi prejemnike in personaliziramo sporočila in jih pošljemo
		$date_sent = date ("Y-m-d H:i:s");

		# updejtamo userja da mu je bilo poslano
		if ( count($send_ok_ids) > 0) {
            
			$sqlQuery = sisplet_query("UPDATE srv_invitations_recipients SET sent='1', date_sent = '".$date_sent."' WHERE id IN (".implode(',',$send_ok_ids).")");   
            if (!$sqlQuery)
				$error = mysqli_error($GLOBALS['connect_db']);
            
			$sqlQuery = sisplet_query("UPDATE srv_invitations_recipients SET last_status='1' WHERE id IN (".implode(',',$send_ok_ids).") AND last_status IN ('0','2')");     
            if (!$sqlQuery)
				$error = mysqli_error($GLOBALS['connect_db']);
		}

		$msg = array($lang['srv_inv_activate_respondents']. count($send_ok_ids));
        
        if (count($send_error_ids) > 0) {
			print_r("<pre>");
			print_r($lang['srv_inv_error0']);
			print_r($send_error_ids);
			print_r("</pre>");
        }
        
		# popravimo timestamp za regeneracijo dashboarda
		Common::getInstance()->Init($anketa);
		Common::getInstance()->updateEditStamp();

		$this->viewRecipients(/*array(),$msg*/);
	}
	
	// Dodamo samo izbrane userje v bazo podatkov kot respondente
	function add_checked_users_to_database() {
		global $site_url;
	
		// Prejemniki, ki jih ročno dodajamo med respondente
		$inv_rids = $_POST['inv_rids'];
	
		# prejeminki besedila
		$sql_string = "SELECT id, firstname, lastname, email, password, cookie, phone, salutation, custom, relation FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted='0' AND sent='0' AND id IN(".implode(',',$inv_rids).")";
		$sql_query = sisplet_query($sql_string);

		# polovimo sistemske spremenljivke z vrednostmi
		$strSistemske = "SELECT s.id, s.naslov, s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.gru_id=g.id AND g.ank_id='".$this->sid."' AND variable IN("."'" . implode("','",$this->inv_variables)."')  ORDER BY g.vrstni_red, s.vrstni_red";
		$qrySistemske = sisplet_query($strSistemske);
		$sys_vars = array();
		$sys_vars_ids = array();
		while ($row = mysqli_fetch_assoc($qrySistemske)) {
			$sys_vars[$row['id']] = array('id'=>$row['id'], 'variable'=>$row['variable'],'naslov'=>$row['naslov']);
			$sys_vars_ids[] =$row['id'];
		}
		$sqlVrednost = sisplet_query("SELECT spr_id, id AS vre_id, vrstni_red, variable FROM srv_vrednost WHERE spr_id IN(".implode(',',$sys_vars_ids).") ORDER BY vrstni_red ASC ");
		while ($row = mysqli_fetch_assoc($sqlVrednost)) {
			// Ce gre za odnos imamo radio
			if($sys_vars[$row['spr_id']]['variable'] == 'odnos'){
				if(!isset($sys_vars[$row['spr_id']]['vre_id'][$row['vrstni_red']]))
					$sys_vars[$row['spr_id']]['vre_id'][$row['variable']] = $row['vre_id'];
			}
			elseif (!isset($sys_vars[$row['spr_id']]['vre_id'])) {				
				$sys_vars[$row['spr_id']]['vre_id'] = $row['vre_id'];
			}
		}

		# array za rezultate
		$send_users_data = array();

		# zloopamo skozi prejemnike in personaliziramo sporočila in jih pošljemo
		$date_sent = date ("Y-m-d H:i:s");
		while ($sql_row = mysqli_fetch_assoc($sql_query)) {
			$_user_data = $sql_row;
			$_user_data['status'] = 1;
			$send_users_data[] = $_user_data;
		}

		# dodamo še userja v srv_user da je kompatibilno s staro logiko
		$strInsertDataText = array();
		$strInsertUserbase = array();
		$strInsertUserstatus = array();
		foreach ($send_users_data AS $user_data) {
			$strInsert = "INSERT INTO srv_user (ank_id, email, cookie, pass, last_status, time_insert, inv_res_id) VALUES ('".$this->sid."', '".$user_data['email']."', '".$user_data['cookie']."', '".$user_data['password']."', '".$user_data['status']."', NOW(), '".$user_data['id']."') ON DUPLICATE KEY UPDATE last_status=VALUES(last_status), inv_res_id=VALUES(inv_res_id)";
				
			sisplet_query($strInsert);
			$usr_id = mysqli_insert_id($GLOBALS['connect_db']);
			if ($usr_id) {
				# za update v srv_invitations_respondents
				$send_ok_ids[] = $user_data['id'];
			} else {
				$send_error_ids[] = $user_data;
			}
			# dodamo še srv_userbase in srv userstatus
			$strInsertUserbase[] = "('".$usr_id."','0',NOW(),'".$global_user_id."')";
			$strInsertUserstatus[] = "('".$usr_id."', '0', '0', NOW())";
				
			# dodamo še podatke za posameznega userja za sistemske spremenljivke
			foreach ($sys_vars AS $sid => $spremenljivka) {
				$_user_variable = $this->inv_variables_link[$spremenljivka['variable']];
				if (trim($user_data[$_user_variable]) != '' && $user_data[$_user_variable] != null) {
					if($spremenljivka['variable'] == 'odnos')
						$strInsertDataVrednost[] = "('".$sid."','".$spremenljivka['vre_id'][trim($user_data[$_user_variable])]."','".$usr_id."')";
					else
						$strInsertDataText[] = "('".$sid."','".$spremenljivka['vre_id']."','".trim($user_data[$_user_variable])."','".$usr_id."')";
				}
			}
				
			sisplet_query("COMMIT");
		}

		# vstavimo v srv_userbase
		if (count($strInsertUserbase) > 0) {
			$strInsert = "INSERT INTO srv_userbase (usr_id, tip, datetime, admin_id) VALUES ";
			$strInsert .= implode(',',$strInsertUserbase);
			sisplet_query($strInsert);
		}
		# vstavimo v srv_userstatus
		if (count($strInsertUserstatus) > 0) {
			$strInsert = "INSERT INTO srv_userstatus (usr_id, tip, status, datetime) VALUES ";
			$strInsert .= implode(',',$strInsertUserstatus);
			sisplet_query($strInsert);			
		}
		# vstavimo v srv_data_text
		if (count($strInsertDataText) > 0) {
			$strInsert = "INSERT INTO srv_data_text".$this->db_table." (spr_id, vre_id, text, usr_id) VALUES ";
			$strInsert .= implode(',',$strInsertDataText);
			sisplet_query($strInsert);
		}
		# vstavimo v srv_data_vrednost
		if (count($strInsertDataVrednost) > 0) {
			$strInsert = "INSERT INTO srv_data_vrednost".$this->db_table." (spr_id, vre_id, usr_id) VALUES ";
			$strInsert .= implode(',',$strInsertDataVrednost);
			sisplet_query($strInsert);
		}
		sisplet_query("COMMIT");
			
		# zloopamo skozi prejemnike in personaliziramo sporočila in jih pošljemo
		$date_sent = date ("Y-m-d H:i:s");

		# updejtamo userja da mu je bilo poslano
		if ( count($send_ok_ids) > 0) {
			$sqlString = "UPDATE srv_invitations_recipients SET sent = '1', date_sent = '".$date_sent."' WHERE id IN (".implode(',',$send_ok_ids).")";
			$sqlQuery = sisplet_query($sqlString);
			if (!$sqlQuery) {
				$error = mysqli_error($GLOBALS['connect_db']);
			}
			$sqlString = "UPDATE srv_invitations_recipients SET last_status = '1' WHERE id IN (".implode(',',$send_ok_ids).") AND last_status IN ('0','2')";
			$sqlQuery = sisplet_query($sqlString);
			if (!$sqlQuery) {
				$error = mysqli_error($GLOBALS['connect_db']);
			}

		}

		$msg = array($lang['srv_inv_activate_respondents']. count($send_ok_ids));
		if (count($send_error_ids) > 0) {
			print_r("<pre>");
			print_r($lang['srv_inv_error0']);
			print_r($send_error_ids);
			print_r("</pre>");
		}
		# popravimo timestamp za regeneracijo dashboarda
		Common::getInstance()->Init($anketa);
		Common::getInstance()->updateEditStamp();

		header('location: ' . $site_url . 'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=view_recipients');
	}

	function getRespondents2Send($send_type, $checkboxes, $source_type, $source_lists, $noEmailing=0) {
		$respondenti = array();

		# če imamo dodatne omejitve source_type > 0 (arhivi, seznami) dodamo dodatno kontrolo na id-je respondentov
		$advancedConditionJoin = '';
		$advancedCondition = '';
		if ($source_type == 0) 
		{
			$this->user_inv_ids = array();
			if ((int)$this->invitationAdvancedConditionId > 0)
			{
				$this->user_inv_ids = $this->getConditionUserIds($this->invitationAdvancedConditionId);
				if (isset($this->user_inv_ids) && is_array($this->user_inv_ids) && count($this->user_inv_ids) > 0 )
				{
					$advancedConditionJoin = " INNER JOIN srv_user AS su ON i.id = su.inv_res_id";
					$advancedCondition = " AND su.ank_id = '$this->sid' AND su.inv_res_id IS NOT NULL AND su.deleted = '0' AND su.id IN ('".(implode('\',\'',$this->user_inv_ids))."')";
				}
			}
		}
		else if ($source_type == 1) 
		{
			# arhivi
			if ($source_lists != '') 
			{
				$sub_query = " AND i.id IN(SELECT rec_id AS id FROM srv_invitations_archive_recipients WHERE arch_id IN(".$source_lists.")) ";
			} else {
				$sub_query = " AND 0=1 ";
			}
			
		} 
		else if ($source_type == 2) 
		{
			if ($source_lists != '') 
			{
				$sub_query = " AND i.list_id IN(".$source_lists.") ";
			} 
			else 
			{
				$sub_query = " AND 0=1 ";
			}
		}
		# polovimo respondente ki ustrezajo posameznemu statusu
		if ($send_type == 0 )
		{
		}
		if ($send_type == 1) 
		{
			$sql_sub_condition = " AND i.sent = '0'";
		}
		if ($send_type == 2) 
		{
			$sql_sub_condition = " AND i.sent = '1' AND i.responded = '0'";
		}
		if ($send_type == 3) 
		{
			$sql_sub_condition = " AND i.sent = '1' AND i.responded = '1'";
		}
		if ($send_type == 4) 
		{
			if ($_POST['checkboxes'] != null && trim($_POST['checkboxes']) != '' ) 
			{
				$sql_sub_condition = " AND i.last_status IN (".$_POST['checkboxes'].")";
			}
		}

        // Ce imamo vklopljene volitve potem posiljamo samo tistim, katerim še nismo poslali vabila (ponovno posiljanje ni mogoce)
        $sql_voting_condition = (SurveyInfo::getInstance()->checkSurveyModule('voting')) ? " AND i.sent = '0' AND i.cookie != '' AND i.password != ''" : "";
		
		// Ce imamo posiljanje brez emaila, ni potrebno da je email vnesen za posameznega respondenta
		if($noEmailing == 1){
			$sql_fields = "SELECT DISTINCT i.password, i.id, i.email, i.firstname, i.last_status, i.list_id FROM srv_invitations_recipients AS i";
			$sql_main_condition = " WHERE i.ank_id = '".$this->sid."' AND i.deleted = '0' AND i.unsubscribed = '0'";
			$sql_sort = " ORDER BY i.id ASC";
			
			$sql_string = $sql_fields
						. $advancedConditionJoin
						. $sql_main_condition
						. $advancedCondition
						. $sql_sub_condition
						. $sub_query
						. $sql_sort;
			if ($sql_string != null) {
				$qry = sisplet_query($sql_string);
				while ($row = mysqli_fetch_assoc($qry)) {
					$respondenti[$row['password']] = array('id'=>$row['id'], 'email'=>$row['email'], 'firstname'=>$row['firstname'], 'status'=>$row['last_status'], 'list_id'=>$row['list_id']);
				}
			}	
		}
		else{
			$sql_fields = "SELECT DISTINCT i.password, i.id, i.email, i.last_status, i.list_id FROM srv_invitations_recipients AS i";
			$sql_main_condition = " WHERE i.ank_id = '".$this->sid."' AND i.deleted = '0' AND i.unsubscribed = '0' AND i.email IS NOT NULL";
			$sql_sort = " ORDER BY i.id ASC";            
			
			$sql_string = $sql_fields
						. $advancedConditionJoin
						. $sql_main_condition
                        . $sql_voting_condition
						. $advancedCondition
						. $sql_sub_condition
						. $sub_query
						. $sql_sort;
			if ($sql_string != null) {
				$qry = sisplet_query($sql_string);
				while ($row = mysqli_fetch_assoc($qry)) {
					$respondenti[$row['password']] = array('id'=>$row['id'], 'email'=>$row['email'], 'status'=>$row['last_status'], 'list_id'=>$row['list_id']);
				}
			}			
		}	
		
		return($respondenti);
	}

	/* Paginacija za pregled respondentov pred pošiljanjem
	 *
	*/
	function displaySendPagination($all_records) {
		global $lang,$site_url;
		#trenutna stran
		$page = isset($_GET['page']) ? $_GET['page'] : '1';
		$current = is_numeric($_GET['page']) && (int)$_GET['page'] > 0 ? $page : '1';
			
		$all = ceil($all_records / $this->rec_send_page_limit);

		# current nastavimo na zadnji element
		if ( $all > 1 ) {
			echo '<div id="pagination">';
			# povezava na prejšnjo stran
			#			$prev_page = $current - 1 ? $current - 1 :$current;
			# 			echo('<div><a href="#" onclick="invSendPage('.($prev_page).','.$this->rec_send_page_limit.')">'.$lang['previous_page_short'].'</a></div>');
			# povezave  za vmesne strani
			for($a = 1; $a <= $all; $a++) {
				echo('<div value="'.$a.'" '.($a == 1 ? ' class="currentPage_small"':'').'><a href="#" onclick="invSendPage('.($a).','.$this->rec_send_page_limit.')">'.($a).'</a></div>');
			}
			# povezava na naslednjo stran
			#			$next_page = ($current + 1) ? ($current + 1) : $current;
			#   			echo('<div><a href="#" onclick="invSendPage('.($next_page).','.$this->rec_send_page_limit.')">'.$lang['next_page_short'].'</a></div>');

			$rec_on_page = $all != $current ?  $this->rec_send_page_limit : ( $all_records - ($all-1)*$this->rec_send_page_limit);
		
			echo '</div>';
		}
		echo '<br/><div class="justtext">'.$lang['srv_inv_pagination_shown'];
		$rec_on_page_options = array(20,50,100,200,500,1000);
		$none_added = true;
		$added_over = false;
		echo '<select onchange="invSendPageChangeLimit(this,\''.$all_records.'\'); return false;">';
		foreach ($rec_on_page_options AS $option) {
			if ($all_records >= $option || $none_added == true || $added_over == false) {
				echo '<option value="'.$option.'"'.($option == $this->rec_send_page_limit ? ' selected="selected"' : '').'>'.$option.'</option>';
				$none_added = false;
				if ($option > $all_records) {
					$added_over = true;
				}
					
			}
		#$rec_on_page;
		}
		echo '</select>';
		echo $lang['srv_inv_pagination_shown_records'].'</div>';
		
	}

	/* Paginacija za pregled reposndentov
	 *
	*/
	function displayPagination($all_records) {
		global $lang, $site_url;
		
		#trenutna stran
		$page = isset($_GET['page']) ? $_GET['page'] : '1';
		$current = is_numeric($_GET['page']) && (int)$_GET['page'] > 0 ? $page : '1';
			
		$all = ceil($all_records / REC_ON_PAGE);

		# current nastavimo na zadnji element
		if ( $all > 1) {
		
			echo '<div id="pagination">';
			$baseUrl = $site_url.'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=view_recipients&page=';

			# povezava -10
			if ($all > 10) {
				if ($current - 10 >= 0) {
					echo('<div><a href="'.$baseUrl.($current - 10).'">-10</a></div>');
				} else {
					# brez href povezave
					echo('<div class="disabledPage">-10</div>');
				}
			}
				
			# povezava na prejšnjo stran
			$prev_page = $current - 1 ? $current - 1 :$current;
			if( ($current - 1) >= 1) {
				echo('<div><a href="'.$baseUrl.$prev_page.'">'.$lang['previous_page_short'].'</a></div>');
			} else {
				# brez href povezave
				echo('<div class="disabledPage">'.$lang['previous_page_short'].'</div>');
			}
				
			# povezave  za vmesne strani
			$middle = $all / 2;
			$skipped  = false;
			for($a = 1; $a <= $all; $a++) {
				if ($all < ((GROUP_PAGINATE+1) * 2) || $a <= GROUP_PAGINATE || $a > ($all-GROUP_PAGINATE)
							
						|| ( abs($a-$current) < GROUP_PAGINATE))  {
					if ($skipped == true) {
						echo '<div class="spacePage">.&nbsp;.&nbsp;.</div>';
						$skipped  = false;
					}
					if($a == $current) {
						# brez href povezave
						echo('<div class="currentPage">'.($a).'</div>');
					} else {
						echo('<div><a href="'.$baseUrl.$a.'">'.($a).'</a></div>');
					}
				} else {
					$skipped = true;
				}
			}
			# povezava na naslednjo stran
			$next_page = ($current + 1) ? ($current + 1) : $current;
			if(($current ) < $all) {
				echo('<div><a href="'.$baseUrl.$next_page.'">'.$lang['next_page_short'].'</a></div>');
			} else {
				# brez href povezave
				echo('<div class="disabledPage">'.$lang['next_page_short'].'</div>');
			}
			if ($all > 10) {
				if ($current + 10 < $all) {
					echo('<div><a href="'.$baseUrl.($current + 10).'">+10</a></div>');
				} else {
					# brez href povezave
					echo('<div class="disabledPage">+10</div>');
				}
			}

			$rec_on_page = $all != $current ?  REC_ON_PAGE : ( $all_records - ($all-1)*REC_ON_PAGE);
			echo '<div class="justtext">'.$lang['srv_inv_pagination_shown'].$rec_on_page.$lang['srv_inv_pagination_shown_records'].'</div>';
			echo '</div>';
		}
	}

	function saveArchiveComment() {
		$id = $_POST['aid'];
		$comment = $_POST['comment'];
		if ((int)$id > 0) {
			$sql_string = "UPDATE srv_invitations_archive SET comment= '".$comment ."' WHERE id = '".$id."'";
			$sqlQuery = sisplet_query($sql_string);
			sisplet_query("COMMIT");
		}
	}

	function generateMessageName() {
		global $lang;
		# poiščemo nov naslov
		# zaporedno številčimo ime sporočilo1,2.... če slučajno ime že obstaja
		$new_name = $lang['srv_inv_message_draft_name'];
		$names = array();
		$s = "SELECT naslov FROM srv_invitations_messages WHERE ank_id = '".$this->sid."' AND naslov LIKE '%".$new_name."%'";
		$q = sisplet_query($s);
		while (list($naslov) = mysqli_fetch_row($q)) {
			$names[] = $naslov;
		}
		if (count($names) > 0) {
			$cnt = 1;
			while (in_array($lang['srv_inv_message_draft_name'].$cnt, $names)) {
				$cnt++;
			}
			$new_name = $lang['srv_inv_message_draft_name'].$cnt;
		}
		return $new_name;
	}

	function editMessageDetails() {
		global $lang;

		echo '<div id="inv_recipients_profile_name">';
		echo $lang['srv_inv_message_draft_new_save'].':&nbsp;';

		# polovimo vsa sporočila
		$sql_string = "SELECT * FROM srv_invitations_messages WHERE ank_id = '$this->sid'";
		$sql_query = sisplet_query($sql_string);

		echo '<select onchange="inv_new_message_list_change(this);"  autofocus="autofocus" tabindex="2">';
		echo '<option value="0" selected="selected" class="gray bold">'.$lang['srv_inv_message_draft_new'].'</option>';
		$messages = array();
		while ( $row = mysqli_fetch_assoc($sql_query) ) {
			$messages[$row['id']] = $row;
			#'.((int)$_POST['mid'] == $row['id'] ? ' selected="selected"' : '').'
			echo '<option value="'.$row['id'].'" comment="'.$row['comment'].'">'.$row['naslov'].'</option>';
		}
		echo '</select>';
		#'.((int)$_POST['mid'] > 0 ? ' class="displayNone"' : '').'
		echo '<span id="new_message_list_span">';
		echo '<br><br/>';
		echo '<label>'.$lang['srv_inv_message_rename_new_name'];
		$newName = $this->generateMessageName();

		echo '<input type="text" id="rec_profile_name" value="'.$newName.'" tabindex="1" autofocus="autofocus">';
		echo '</label>';
		echo '</span>';
		echo '<br/><br/>';
		echo $lang['srv_inv_message_draft_list_comment'];
		#.((int)$_POST['mid'] > 0 ? $messages[(int)$_POST['mid']]['comment'] : '').
		echo '<textarea id="inv_message_comment" tabindex="3" rows="2" style="width:200px;"></textarea>';
		echo '<br class="clr" /><br class="clr" />';
		echo '<span class="buttonwrapper floatRight" title="'.$lang['save'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inv_message_save_details(); return false;"><span>'.$lang['save'].'</span></a></span>';
		echo '<span class="buttonwrapper floatRight spaceRight"  title="'.$lang['srv_cancel'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#fade\').fadeOut(\'slow\');$(\'#fullscreen\').fadeOut(\'slow\').html(\'\');return false;" ><span>'.$lang['srv_cancel'].'</span></a></span>';
		echo '<br class="clr" />';
		echo '</div>'; # id="inv_view_arch_recipients"

	}

	function messageSaveDetails() {
		global $lang, $global_user_id;
		$return = array('msg'=>'', 'error'=>'0');

		#echo json_encode($return);
		$mid = (int)$_POST['mid'];
		$return['mid'] = $mid;

		$comment = trim($_POST['profile_comment']);
		$naslov = trim($_POST['naslov']);
		
		$body = $_POST['body'];
		$subject = $_POST['subject'];

		if ($mid > 0) {
			#updejtamo obstoječ profil
			$sql_string = "UPDATE srv_invitations_messages SET subject_text='".$subject."', body_text='".$body."', comment='".$comment."', edit_uid='".$global_user_id."', edit_time=NOW() WHERE ank_id = '$this->sid' AND id='$mid'";
			$sqlQuery = sisplet_query($sql_string);
			$return['mid'] = $mid;
				
			if ( $sqlQuery != 1) {
				$return['error'] = '1';
				$return['msg'] .= $newline.$lang['srv_inv_msg_4'];
			}
			sisplet_query("COMMIT");

		} else {
			# shranimo v nov profil
			# ali shranjujemo v novo sporočilo
			$sql_insert = "INSERT INTO srv_invitations_messages (ank_id, naslov, isdefault, uid, insert_time, comment, edit_uid, edit_time, subject_text, body_text) "
			."VALUES ('$this->sid', '$naslov', '1', '$global_user_id', NOW(), '$comment', '$global_user_id', NOW(), '$subject', '$body')";
			$sqlQuery = sisplet_query($sql_insert);
				
			$mid = mysqli_insert_id($GLOBALS['connect_db']);
			if ($mid > 0) {
				$return['mid'] = $mid;
				# popravmo še isdefault pri starem zapisz
				$sql_string = "UPDATE srv_invitations_messages SET isdefault = '0' WHERE ank_id = '$this->sid' AND id != '$mid'";
				$sqlQuery = sisplet_query($sql_string);
			} else {
				$return['error'] = '1';
				$return['msg'] .= $newline.$lang['srv_inv_msg_4'];
			}
			sisplet_query("COMMIT");
		}
		echo json_encode($return);
		exit;
	}

	function prepareSaveMessage() {
		global $lang;

		echo '<div id="inv_recipients_profile_name">';
		echo $lang['srv_inv_message_draft_new_save'].':&nbsp;';

		# polovimo vsa sporočila
		$sql_string = "SELECT * FROM srv_invitations_messages WHERE ank_id = '$this->sid'";
		$sql_query = sisplet_query($sql_string);

		echo '<select onchange="inv_new_message_list_change(this);"  autofocus="autofocus" tabindex="2">';
		echo '<option value="0" class="gray bold">'.$lang['srv_inv_message_draft_new'].'</option>';
		$messages = array();
		while ( $row = mysqli_fetch_assoc($sql_query) ) {
			$messages[$row['id']] = $row;
			echo '<option value="'.$row['id'].'" comment="'.$row['comment'].'"'.((int)$_POST['mid'] == $row['id'] ? ' selected="selected"' : '').'>'.$row['naslov'].'</option>';
		}
		echo '</select>';
		echo '<span id="new_message_list_span"'.((int)$_POST['mid'] > 0 ? ' class="displayNone"' : '').'>';
		echo '<br><br/>';
		echo '<label>'.$lang['srv_inv_message_draft_list_name'];
		$newName = $this->generateMessageName();

		echo '<input type="text" id="rec_profile_name" value="'.$newName.'" tabindex="1" autofocus="autofocus">';
		echo '</label>';
		echo '</span>';
		echo '<br/><br/>';
		echo $lang['srv_inv_message_draft_list_comment'];
		echo '<textarea id="inv_message_comment" tabindex="3" rows="2" style="width:200px;">'.((int)$_POST['mid'] > 0 ? $messages[(int)$_POST['mid']]['comment'] : '').'</textarea>';
		echo '<br class="clr" /><br class="clr" />';
		echo '<span class="buttonwrapper floatLeft spaceRight"  title="'.$lang['srv_cancel'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#fade\').fadeOut(\'slow\');$(\'#fullscreen\').fadeOut(\'slow\').html(\'\');return false;" ><span>'.$lang['srv_cancel'].'</span></a></span>';
		echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['save'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inv_message_save_details(); return false;"><span>'.$lang['save'].'</span></a></span>';
		echo '<br class="clr" />';
		echo '</div>'; # id="inv_view_arch_recipients"

	}
	
	function showRecipientTracking() {
		global $lang,$site_url,$global_user_id;
		$_rec_id = $_POST['rid'];
		
		# polovimo podatke o uporabniku
		$sql_string = "SELECT firstname,lastname,email,last_status, DATE_FORMAT(date_inserted,'%d.%m.%Y, %T') AS di FROM srv_invitations_recipients WHERE id = '".(int)$_rec_id."'";
		$sql_query = sisplet_query($sql_string);
		$sql_row = mysqli_fetch_assoc($sql_query);

		$avtor = array();
		if (trim($sql_row['firstname'])) {
			$avtor[] = iconv("iso-8859-2", "utf-8",trim ($sql_row['firstname']));
		}
		if (trim($sql_row['lastname'])) {
			$avtor[] = iconv("iso-8859-2", "utf-8",trim ($sql_row['lastname']));
		}
		$lastStatus = $sql_row['last_status'];
		
		echo '<div id="inv_view_arch_recipients" class="singleRec">';
		
		
		echo '<div class="inv_FS_content">';
		echo '<div id="inv_arch_mail_preview">';
		
		echo '<span class="strong" style="font-size: 14px;">'.$lang['srv_invitation_user_chronology_note'];
		if ( count($avtor) > 0 ) {
			echo '<span>';
			echo implode(' ',$avtor);
			if($sql_row['email'] != '')
				echo ' ('.trim($sql_row['email']).')';
			echo '</span>';
		} else {
			# izpišemo samo email
			echo trim($sql_row['email']);
		}
		echo '</span>';
		
			echo '<br/>';
			echo $lang['srv_inv_recipients_date_inserted'].': '.$sql_row['di'];
		# polovimo podatke uporabnikovih arhivov
		$sql_string = "SELECT ia.*, u.name, u.surname, u.email FROM srv_invitations_archive AS ia LEFT JOIN users AS u ON ia.uid = u.id WHERE ia.id IN (SELECT inv_arch_id FROM srv_invitations_tracking WHERE res_id = '$_rec_id' ) ";
			$sql_query = sisplet_query($sql_string);
		
		$cnt =0;
		
		while ($sql_row = mysqli_fetch_assoc($sql_query)) {
			$cnt++;
			$avtor_email = iconv("iso-8859-2", "utf-8",trim ($sql_row['email']));
			$avtor = array();
			if (trim($sql_row['name'])) {
				$avtor[] = trim ($sql_row['name']);
			}
			if (trim($sql_row['surname'])) {
				$avtor[] = trim ($sql_row['surname']);
			}
			if ( count($avtor) > 0 ) {
				$avtor_name = implode(' ',$avtor);
			} else {
				$avtor_name = $avtor_email;
			}
			
			echo '<div style="font-weight:600; padding:5px 0px;	">';
			echo $cnt.$lang['srv_invitation_user_chronology_sending'];
			echo '  ('.$lang['srv_invitation_user_chronology_send_by'];
			echo ' <span title="'.$avtor_email.'">'.$avtor_name.'</span>';
			echo ')';
			echo '</div>';
			

			echo '<div style="margin-left:25px;margin-bottom:10px;">';

			echo '<table id="tbl_respondentArchive">';

			echo '<tr>';
			
            echo '<th>'.$lang['srv_invitation_user_chronology_date'].'</th>';
            // Volitve nimajo nekaterih polj
            if(!SurveyInfo::getInstance()->checkSurveyModule('voting'))
			    echo '<th>'.$lang['srv_invitation_user_chronology_status'].'</th>';
			echo '<th>'.$lang['srv_inv_message_type'].'</th>';
			
            echo '</tr>';
				
			$sql_string1 = "SELECT status, DATE_FORMAT(time_insert,'%d.%m.%Y, %T') AS status_time FROM srv_invitations_tracking WHERE res_id = '$_rec_id' AND inv_arch_id='".$sql_row['id']."' ORDER BY uniq ASC";
			$sql_query1 = sisplet_query($sql_string1);
			while ($sql_row1 = mysqli_fetch_assoc($sql_query1)) {
				echo '<tr>';
				
				echo '<td>'.$sql_row1['status_time'].'</td>';
				
                // Volitve nimajo nekaterih polj
                if(!SurveyInfo::getInstance()->checkSurveyModule('voting'))
			    	echo '<td>('.$sql_row1['status'].') - '.$lang['srv_userstatus_'.$sql_row1['status']].'</td>';

				echo '<td>';
							if ($sql_row['tip'] == '0')
				echo $lang['srv_inv_message_noemailing_type1'];
				elseif($sql_row['tip'] == '1')
					echo $lang['srv_inv_message_noemailing_type2'];
				elseif($sql_row['tip'] == '2')
					echo $lang['srv_inv_message_noemailing_type3'];
				else
					echo $lang['email'];
				echo '</td>';
				
				echo '</tr>';
			}
			echo '</table>';
			echo '</div>';
		}

        // Volitve nimajo nekaterih polj
        if(!SurveyInfo::getInstance()->checkSurveyModule('voting')){
            echo '<div style="padding:5px 0px;">';
            echo '<span style="font-weight:600;">'.$lang['srv_inv_recipients_final_status'].'</span> ('.$lastStatus.') - '.$lang['srv_userstatus_'.$lastStatus];
            echo '</div>';
        }	
		
		echo '</div>'; // inv_select_mail_preview
		
		echo '</div>'; // id="arc_content"
		echo '<div class="inv_FS_btm">';
		echo '<div id="navigationBottom" class="printHide">';
		#echo '<span class="floatRight spaceLeft"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inv_arch_save_comment(); return false;"><span>'.$lang['save'].'</span></a></div></span>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="inv_arch_recipients_close(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
		echo '<div class="clr" />';
		echo '</div>';
		
		echo '</div>';
		
	}
	function showArchiveRecipients($_arch_id = null, $archType = 'all') {
		global $lang,$site_url,$global_user_id;
		echo '<div id="inv_view_arch_recipients" class="fromArchive">';
		if ($_arch_id == null) {
			$_arch_id = $_POST['aid'];
		}
	
		#polovimo podatke arhiva
		$sql_string = "SELECT sia.*, DATE_FORMAT(sia.date_send,'%d.%m.%Y, %T') AS ds,  u.name, u.surname, u.email FROM srv_invitations_archive AS sia LEFT JOIN users AS u ON sia.uid = u.id WHERE sia.id = '".$_arch_id."'";
		$sql_query = sisplet_query($sql_string);
		$row = mysqli_fetch_assoc($sql_query);
	
		# polovimo sezname
		$lists = array();
		$sql_string = "SELECT pid, name,comment FROM srv_invitations_recipients_profiles WHERE uid in('".$global_user_id."')";
		$sql_query = sisplet_query($sql_string);
		while ($sql_row = mysqli_fetch_assoc($sql_query)) {
			$lists[$sql_row['pid']] = $sql_row['name'];
		}
	
		$lists['-1'] = $lang['srv_invitation_new_templist'];
		$lists['0'] = $lang['srv_invitation_new_templist_author'];

		#max ststusi po userjih
		$arch_user_max_status = array();
		$str_max_status = "select res_id AS rid, max(status) AS usr_status from srv_invitations_tracking  where inv_arch_id = '$_arch_id' GROUP BY res_id";
		$qry_max_status = sisplet_query($str_max_status);
		while (list($res_id,$arch_status) = mysqli_fetch_row($qry_max_status)) {
			$arch_user_max_status[$res_id] = $arch_status; 
		}
		#$data = explode('_',$_POST['arch_to_view']);
		#$_success = (int)$data[2];
		#$_arch_id = $data[3];
		$sql_string = "SELECT * FROM srv_invitations_archive WHERE id = '$_arch_id'";
		$sql_query = sisplet_query($sql_string);
		$sql_a_row = mysqli_fetch_assoc($sql_query);

		#$sql_string = "SELECT id as res_id,email,firstname,lastname, password,sent,responded,unsubscribed,deleted,list_id,last_status FROM srv_invitations_recipients WHERE id IN (SELECT DISTINCT res_id FROM srv_invitations_tracking WHERE inv_arch_id = '$_arch_id' )";
		$sql_string = "SELECT DISTINCT sir.id as res_id,sir.email,sir.firstname,sir.lastname, sir.password,sir.sent,sir.responded,sir.unsubscribed,sir.deleted,"
			."sir.list_id,sir.last_status FROM srv_invitations_recipients AS sir INNER JOIN srv_invitations_tracking AS sit ON sir.id = sit.res_id WHERE sit.inv_arch_id = '$_arch_id'";
        
        // prikazujemo samo napake
        if ($archType == 'err') {
            $sql_string = "SELECT DISTINCT sir.id as res_id,sir.email,sir.firstname,sir.lastname, sir.password,sir.sent,sir.responded,sir.unsubscribed,sir.deleted,"
                ."sir.list_id,sir.last_status FROM srv_invitations_recipients AS sir INNER JOIN srv_invitations_tracking AS sit ON sir.id = sit.res_id "
                ." JOIN srv_invitations_archive_recipients siar ON sir.id = siar.rec_id AND siar.arch_id = sit.inv_arch_id AND siar.success = '0'"
                ."WHERE sit.inv_arch_id = '$_arch_id'";
          
        }
        // priazujemo samo ok
        if ($archType == 'succ') {
            $sql_string = "SELECT DISTINCT sir.id as res_id,sir.email,sir.firstname,sir.lastname, sir.password,sir.sent,sir.responded,sir.unsubscribed,sir.deleted,"
                ."sir.list_id,sir.last_status FROM srv_invitations_recipients AS sir INNER JOIN srv_invitations_tracking AS sit ON sir.id = sit.res_id "
                ." JOIN srv_invitations_archive_recipients siar ON sir.id = siar.rec_id AND siar.arch_id = sit.inv_arch_id AND siar.success = '1'"
                ."WHERE sit.inv_arch_id = '$_arch_id'";
          
        }
        
		$sql_query = sisplet_query($sql_string);

		echo '<div class="inv_FS_content">';
		
		$avtor_email = iconv("iso-8859-2", "utf-8",trim ($row['email']));
		$avtor = array();
		if (trim($row['name'])) {
			$avtor[] = trim ($row['name']);
		}
		if (trim($row['surname'])) {
			$avtor[] = trim ($row['surname']);
		}
		if ( count($avtor) > 0 ) {
			$avtor_name = implode(' ',$avtor);
		} else {
			$avtor_name = $avtor_email;
		}
		
		echo '<br />';
		
		echo '<span class="inv_dashboard_sub_detail">';
		echo $lang['srv_inv_archive_naslov'];
		echo ': <span class="bold"><a href="#" onclick="inv_arch_edit_details(\''.$row['id'].'\'); return false;">'.$row['naslov'].'</a>';
		echo '</span></span><br />';
		
		echo '<span class="inv_dashboard_sub_detail">';
		echo $lang['srv_invitation_user_chronology_send_by'];
		echo ' <span class="bold"><span title="'.$avtor_email.'">'.$avtor_name.'</span>';
		echo ', ';
		echo $row['ds'];
		echo '</span></span><br />';
		
		echo '<span class="inv_dashboard_sub_detail">';
		echo $lang['srv_inv_message_type'];
		echo ': <span class="bold">';
		if ($row['tip'] == '0')
			echo $lang['srv_inv_message_noemailing_type1'];
		elseif($row['tip'] == '1')
			echo $lang['srv_inv_message_noemailing_type2'];
		elseif($row['tip'] == '2')
			echo $lang['srv_inv_message_noemailing_type3'];
		else
			echo $lang['email'];
		echo '</span></span>';

		echo '<div id="inv_arch_mail_preview">';

		echo '<table id="tbl_recipients_list">';

		echo '<tr>';

        // Pri volitvah ne prikazemo nekaterih stolpcev
        if(SurveyInfo::getInstance()->checkSurveyModule('voting')){
            echo '<th class="tbl_icon" title="'.$lang['srv_inv_recipients_sent'].'">'.$lang['srv_inv_recipients_sent'].'</th>';
            echo '<th class="tbl_inv_left">'.$lang['srv_inv_recipients_email'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_firstname'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_lastname'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_list_id'].'</th>';
        }
        else{
            echo '<th class="tbl_icon" title="'.$lang['srv_inv_recipients_sent'].'">'.$lang['srv_inv_recipients_sent'].'</th>';
            echo '<th class="tbl_icon" title="'.$lang['srv_inv_recipients_responded'].'">'.$lang['srv_inv_recipients_responded'].'</th>';
            echo '<th class="tbl_icon" title="'.$lang['srv_inv_recipients_unsubscribed'].'">'.$lang['srv_inv_recipients_unsubscribed'].'</th>';
            echo '<th class="tbl_inv_left">'.$lang['srv_inv_recipients_email'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_password'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_firstname'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_lastname'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_max_archive_status'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_last_status'].'</th>';
            echo '<th>'.$lang['srv_inv_recipients_list_id'].'</th>';
        }
		
		echo '</tr>';

		while ($sql_row = mysqli_fetch_assoc($sql_query)) {
			echo '<tr>';

            // Pri volitvah ne prikazemo nekaterih stolpcev
            if(SurveyInfo::getInstance()->checkSurveyModule('voting')){
                echo '<td><span class="as_link" onclick="showRecipientTracking(\''.$sql_row['res_id'].'\'); return false;"><img src="'.$site_url.'admin/survey/img_0/'.((int)$sql_row['sent'] == 1 ? 'email_sent.png' : 'email_open.png').'"></span></td>';
                echo '<td class="tbl_inv_left">'.$sql_row['email'].'</td>';
                echo '<td>'.$sql_row['firstname'].'</td>';
                echo '<td>'.$sql_row['lastname'].'</td>';
                echo '<td>'.$lists[$sql_row['list_id']].'</td>';
            }
            else{
                echo '<td><span class="as_link" onclick="showRecipientTracking(\''.$sql_row['res_id'].'\'); return false;"><img src="'.$site_url.'admin/survey/img_0/'.((int)$sql_row['sent'] == 1 ? 'email_sent.png' : 'email_open.png').'"></span></td>';
                echo '<td><img src="'.$site_url.'admin/survey/icons/icons/'.((int)$sql_row['responded'] == 1 ? 'star_on.png' : 'star_off.png').'"></td>';
                echo '<td><img src="'.$site_url.'admin/survey/img_0/'.((int)$sql_row['unsubscribed'] == 1 ? 'opdedout_on.png' : 'opdedout_off.png').'"></td>';
                echo '<td class="tbl_inv_left">'.$sql_row['email'].'</td>';
                echo '<td>'.$sql_row['password'].'</td>';
                echo '<td>'.$sql_row['firstname'].'</td>';
                echo '<td>'.$sql_row['lastname'].'</td>';
                $status = $arch_user_max_status[$sql_row['res_id']];
                echo '<td>'.$lang['srv_userstatus_'.$status].' ('.$status.')'.'</td>';
                echo '<td>'.$lang['srv_userstatus_'.$sql_row['last_status']].' ('.$sql_row['last_status'].')'.'</td>';
                echo '<td>'.$lists[$sql_row['list_id']].'</td>';
            }

			echo '</tr>';
		}

		echo '</table>';

		echo '</div>'; // inv_select_mail_preview

		echo '</div>'; // id="arc_content"
		
		echo '<br />';
		
		echo '<div class="inv_FS_btm">';
		echo '<div id="navigationBottom" class="printHide">';
		#echo '<span class="floatRight spaceLeft"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inv_arch_save_comment(); return false;"><span>'.$lang['save'].'</span></a></div></span>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="inv_arch_recipients_close(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
		echo '<div class="clr" />';
		echo '</div>';

		echo '</div>';
	}

	function editArchiveDetails() {
		global $lang,$site_url;
		echo '<div id="inv_view_arch_recipients">';

		$_arch_id = $_POST['aid'];

		#polovimo podatke arhiva
		$sql_string = "SELECT * FROM srv_invitations_archive WHERE id = '".$_arch_id."'";
		$sql_query = sisplet_query($sql_string);
		$row = mysqli_fetch_assoc($sql_query);
				
		echo '<div class="inv_FS_content">';
		echo '<div id="inv_arch_mail_preview">';

		echo '<input id="inv_arch_id" type="hidden" value="'.$_arch_id.'">';
		
		echo '<table id="inv_arch_mail_preview">';
		echo '<tr><td class="bold">'.$lang['srv_inv_message_draft_content_subject'].':</td>';
		echo '<td class="inv_bt bold">';
		echo '<span>'.$row['subject_text'].'</span>';
		echo '</td></tr>';
		echo '<tr><td>'.$lang['srv_inv_message_draft_content_body'].':</td>';
		echo '<td class="inv_bt">';
		echo '<span class="nl2br">'.($row['body_text']).'</span>';
		echo '</td></tr>';
		echo '<tr><td>'.$lang['srv_inv_message_draft_comment'].':</td>';
		echo '<td>';
		echo '<span>';
		echo '<textarea id="inv_arch_comment" rows="2" style="width:380px;">'.$row['comment'].'</textarea>';
		echo '</span>';
		echo '</td></tr>';
		echo '</table>';
		echo '</div>'; // inv_select_mail_preview

		echo '</div>'; // id="arc_content"
		echo '<div class="inv_FS_btm">';
		echo '<div id="navigationBottom" class="printHide">';
		echo '<span class="floatRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inv_arch_save_comment(); return false;"><span>'.$lang['save'].'</span></a></div></span>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="inv_arch_recipients_close(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
		echo '<div class="clr" />';
		echo '</div>';

		echo '</div>';
	}
	function showArchiveDetails() {
		global $lang,$site_url;
		echo '<div id="inv_view_arch_recipients">';

		$_arch_id = $_POST['aid'];

		#polovimo podatke arhiva
		$sql_string = "SELECT * FROM srv_invitations_archive WHERE id = '".$_arch_id."'";
		$sql_query = sisplet_query($sql_string);
		$row = mysqli_fetch_assoc($sql_query);
		echo '<div class="inv_FS_content">';
		echo '<div id="inv_arch_mail_preview">';

		echo '<table id="inv_arch_mail_preview">';
		echo '<tr><td>'.$lang['srv_inv_message_draft_content_subject'].':</td>';
		echo '<td class="inv_bt">';
		echo '<span>'.$row['subject_text'].'</span>';
		echo '</td></tr>';
		echo '<tr><td>'.$lang['srv_inv_message_draft_content_body'].':</td>';
		echo '<td class="inv_bt">';
		echo '<span class="nl2br">'.($row['body_text']).'</span>';
		echo '</td></tr>';
		echo '<tr><td>'.$lang['srv_inv_message_draft_comment'].':</td>';
		echo '<td>';
		echo '<span>';
		echo '<div id="inv_arch_comment" rows="2" style="width:380px;">'.$row['comment'].'</div>';
		echo '</span>';
		echo '</td></tr>';
		echo '</table>';
		echo '</div>'; // inv_select_mail_preview

		echo '</div>'; // id="arc_content"
		echo '<div class="inv_FS_btm">';
		echo '<div id="navigationBottom" class="printHide">';
		#echo '<span class="floatRight spaceLeft"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inv_arch_save_comment(); return false;"><span>'.$lang['save'].'</span></a></div></span>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="inv_arch_recipients_close(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
		echo '<div class="clr" />';
		echo '</div>';

		echo '</div>';
	}

	function showInvitationStatus() {
		global $admin_type, $app_settings, $global_user_id;
		
		$isEmail = (int)SurveyInfo::getInstance()->checkSurveyModule('email');	
		$d = new Dostop();
		
		echo '<table style="width:50%"><tr>';	
		
        // Pri volitvah prikazemo samo osnovne stevilke - zaradi anonimizacije ni trackinga
        if(SurveyInfo::getInstance()->checkSurveyModule('voting')){

            $userAccess = UserAccess::getInstance($global_user_id);

			// Ce so izklopljena ne prikazemo leve strani
			if((int)$isEmail > 0 && $userAccess->checkUserAccess($what='invitations')){
				echo '<td style="padding-right:10px;vertical-align: top;">';
				$this->displayInvitationStatusVoting();
				echo '</td>';
			}
        }
		// Nov način z trackingom
		elseif($this->newTracking == true) {

            $userAccess = UserAccess::getInstance($global_user_id);

			// Ce so izklopljena ne prikazemo leve strani
			if((int)$isEmail > 0 && $userAccess->checkUserAccess($what='invitations')){
				echo '<td style="padding-right:10px;vertical-align: top;">';
				$this->displayInvitationStatusNew();
				echo '</td>';
			}
		} 
		# star način brez trackinga
		else {
			echo '<td style="padding-right:10px;vertical-align: top;">';
			$this->displayInvitationStatusOld();
			echo '</td>';
		}
			
		echo '</tr></table>';
	}
	
	function displayInvitationStatusOld() {
		global $lang, $admin_type, $global_user_id, $site_url, $site_path;
		
		$isEmail = (int)SurveyInfo::getInstance()->checkSurveyModule('email');
		
		# polovimo lurkerje
		echo '<fieldset class="inv_fieldset"><legend>'.$lang['srv_inv_nav_email_status'].'</legend>';
		echo '<div class="inv_filedset_inline_div">';
		echo '<p>';
		if ((int)$isEmail > 0) {
	
			# preštejemo respondente po statusu
			$recipients_by_status = array();
			$sql_string  = "SELECT count(*) as cnt, last_status FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted='0' GROUP BY last_status";
			$sql_query = sisplet_query($sql_string);
			if (mysqli_num_rows($sql_query) > 0) {
				while($row  = mysqli_fetch_assoc($sql_query)) {
					$recipients_by_status['all'] += (int)$row['cnt'];
					switch ((int)$row['last_status']) {
						# 0 - E-pošta - ni poslana
						case 0:
						$recipients_by_status['not_send'] += (int)$row['cnt'];
						break;
						# 1 - E-pošta - neodgovor
						case 1:
							$recipients_by_status['send'] += (int)$row['cnt'];
						break;
						# 2 - E-pošta - napaka
						case 2:
							$recipients_by_status['not_send'] += (int)$row['cnt'];
						$recipients_by_status['error'] += (int)$row['cnt'];
						break;
						# 3 - klik na nagovor
						case 3:
							$recipients_by_status['send'] += (int)$row['cnt'];
						$recipients_by_status['clicked'] += (int)$row['cnt'];
						break;
						# 4 - klik na anketo
						case 4:
							$recipients_by_status['send'] += (int)$row['cnt'];
						$recipients_by_status['clicked'] += (int)$row['cnt'];
						break;
						# 5 - delno prazna
						case 5:
							$recipients_by_status['send'] += (int)$row['cnt'];
							$recipients_by_status['clicked'] += (int)$row['cnt'];
						break;
						# 6 - končana
						case 6:
							$recipients_by_status['send'] += (int)$row['cnt'];
						#$recipients_by_status['clicked'] += (int)$row['cnt'];
						$recipients_by_status['finished'] += (int)$row['cnt'];
						break;
						# null - neznan
						default:
							$recipients_by_status['unknown'] += (int)$row['cnt'];
						break;
					}
				}
				$all_rec_in_survey = (int)$recipients_by_status['all'];
	
				echo '<table class="inv_dashboard_table">';
				echo '<tr>';
				echo '<th>'.$lang['srv_inv_dashboard_tbl_all'].'</th>';
				echo '<th>'.(int)$recipients_by_status['all'].'</th>';
				echo '<th>-</th>';
				echo '<th>100%</th>';
				echo '</tr>';
				#popslano enotam
				echo '<tr>';
				echo '<th>'.$lang['srv_inv_dashboard_tbl_send'].'</th>';
				echo '<th>'.(int)$recipients_by_status['send'].'</th>';
				echo '<th>'.((int)$recipients_by_status['send'] > 0 ? '100%' : '0%').'</th>';
				echo '<th>'.$this->formatNumber(((int)$recipients_by_status['send'] > 0 ? (int)$recipients_by_status['send']*100/(int)$recipients_by_status['all'] : 0),0,'%').'</th>';
				echo '</tr>';
					
				#neodgovori
				echo '<tr>';
				echo '<td>'.$lang['srv_inv_dashboard_tbl_unanswered'].'</td>';
				$unanswered = ((int)$recipients_by_status['send']-(int)$recipients_by_status['clicked']-(int)$recipients_by_status['finished']);
				echo '<td>'.$unanswered.'</td>';
				echo '<td>'.$this->formatNumber(($unanswered > 0 ? $unanswered*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
				echo '<td>'.$this->formatNumber(($unanswered > 0 ? $unanswered*100/(int)$recipients_by_status['all'] : 0),0,'%').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>'.$lang['srv_inv_dashboard_tbl_clicked'].'</td>';
				echo '<td>'.(int)$recipients_by_status['clicked'].'</td>';
				echo '<td>'.$this->formatNumber(((int)$recipients_by_status['clicked'] > 0 ? (int)$recipients_by_status['clicked']*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
				echo '<td>'.$this->formatNumber(((int)$recipients_by_status['clicked'] > 0 ? (int)$recipients_by_status['clicked']*100/(int)$recipients_by_status['all'] : 0),0,'%').'</td>';
				echo '</tr>';
					
				#če se slučajno pojavijo kaki neznani statusi
				if ((int)$recipients_by_status['unknown'] > 0) {
					echo '<tr>';
					echo '<td>'.$lang['srv_inv_dashboard_tbl_unknown'].'</td>';
					echo '<td>'.(int)$recipients_by_status['unknown'].'</td>';
					echo '<td>'.$this->formatNumber(((int)$recipients_by_status['unknown'] > 0 ? (int)$recipients_by_status['unknown']*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
					echo '<td>'.$this->formatNumber(((int)$recipients_by_status['unknown'] > 0 ? (int)$recipients_by_status['unknown']*100/(int)$recipients_by_status['all'] : 0),0,'%').'</td>';
					echo '</tr>';
				}
				echo '<tr>';
				echo '<td>'.$lang['srv_inv_dashboard_tbl_finished'].'</td>';
				echo '<td>'.(int)$recipients_by_status['finished'].'</td>';
				echo '<td class="red">'.$this->formatNumber(((int)$recipients_by_status['finished'] > 0 ? (int)$recipients_by_status['finished']*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
				echo '<td class="">'.$this->formatNumber(((int)$recipients_by_status['finished'] > 0 ? (int)$recipients_by_status['finished']*100/(int)$recipients_by_status['all'] : 0),0,'%').'</td>';
				echo '</tr>';
				echo '</table>';
				echo '<br>';
				# zloopamo še po posameznih pošiljanjih
				$sql_string_arc = "SELECT sia.*, DATE_FORMAT(sia.date_send,'%d.%m.%Y, %T') AS ds,  u.name, u.surname, u.email FROM srv_invitations_archive AS sia LEFT JOIN users AS u ON sia.uid = u.id WHERE ank_id = '".$this->sid."' AND cnt_succsess > 0 ORDER BY sia.date_send ASC;";
				$sql_query_arc = sisplet_query($sql_string_arc);
	
				if (mysqli_num_rows($sql_query_arc) > 1) {
					$cnt=0;
					while($row_arc  = mysqli_fetch_assoc($sql_query_arc)) {
						$cnt++;
						# preštejemo respondente po statusu
						$recipients_by_status = array();
						$sql_string  = "SELECT count(*) as cnt, last_status FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted='0' AND id IN (select rec_id from srv_invitations_archive_recipients where arch_id = ".$row_arc['id']." AND success !='0') GROUP BY last_status";
						$sql_query = sisplet_query($sql_string);
						if (mysqli_num_rows($sql_query) > 0) {
							while($row  = mysqli_fetch_assoc($sql_query)) {
								$recipients_by_status['all'] += (int)$row['cnt'];
								switch ((int)$row['last_status']) {
									# 0 - E-pošta - ni poslana
									case 0:
									$recipients_by_status['not_send'] += (int)$row['cnt'];
									break;
									# 1 - E-pošta - neodgovor
									case 1:
										$recipients_by_status['send'] += (int)$row['cnt'];
									break;
									# 2 - E-pošta - napaka
									case 2:
										$recipients_by_status['not_send'] += (int)$row['cnt'];
									$recipients_by_status['error'] += (int)$row['cnt'];
									break;
									# 3 - klik na nagovor
									case 3:
										$recipients_by_status['send'] += (int)$row['cnt'];
									$recipients_by_status['clicked'] += (int)$row['cnt'];
									break;
									# 4 - klik na anketo
									case 4:
										$recipients_by_status['send'] += (int)$row['cnt'];
									$recipients_by_status['clicked'] += (int)$row['cnt'];
									break;
									# 5 - delno prazna
									case 5:
										$recipients_by_status['send'] += (int)$row['cnt'];
									$recipients_by_status['clicked'] += (int)$row['cnt'];
									break;
									# 6 - končana
									case 6:
										$recipients_by_status['send'] += (int)$row['cnt'];
									#$recipients_by_status['clicked'] += (int)$row['cnt'];
									$recipients_by_status['finished'] += (int)$row['cnt'];
									break;
									# null - neznan
									default:
										$recipients_by_status['unknown'] += (int)$row['cnt'];
									break;
								}
							}
							$avtor_email = iconv("iso-8859-2", "utf-8",trim ($row_arc['email']));
							$avtor = array();
							if (trim($row_arc['name'])) {
								$avtor[] = trim ($row_arc['name']);
							}
							if (trim($row_arc['surname'])) {
								$avtor[] = trim ($row_arc['surname']);
							}
							if ( count($avtor) > 0 ) {
								$avtor_name = implode(' ',$avtor);
							} else {
								$avtor_name = $avtor_email;
							}
								
							echo '<span class="pointer span_list_archive" onClick="$(this).next().next().next().toggle(); $(this).find(\'.plus\').toggle();$(this).find(\'.minus\').toggle(); $(this).next(\'.link_archive\').toggle();">';
							echo '<span class="inv_dashboard_sub_title as_link">';
							echo '<span class="plus"  style="color: inherit;">+ </span>';
							echo '<span class="minus displayNone"  style="color: inherit;">- </span>';
							echo $cnt.$lang['srv_inv_dashboard_list_cnt_title'];
							echo '</span>';
							echo '<span class="inv_dashboard_sub_detail" title="'.$avtor_email.'">'.$avtor_name.'</span>';
							echo ', ';
							echo '<span class="inv_dashboard_sub_detail" >'.$row_arc['ds'].'</span>';
							echo '</span>';
							echo '<span class="link_archive as_link displayNone" ><a href="#" onclick="inv_arch_show_details(\''.$row_arc['id'].'\'); return false;"> arhiv </a></span>';
							echo '<br/>';
	
							echo '<table class="inv_dashboard_table sub displayNone">';
							echo '<tr>';
							echo '<th>'.$lang['srv_inv_dashboard_tbl_all'].'</th>';

							echo '<th>'.(int)$all_rec_in_survey.'</th>';
							#echo '<th>'.(int)$recipients_by_status['all'].'</th>';
							echo '<th>&nbsp;</th>';
							echo '<th>100%</th>';
							echo '</tr>';
							#popslano enotam
							echo '<tr>';
							echo '<td>'.$lang['srv_inv_dashboard_tbl_send'].'</td>';
							echo '<td>'.(int)$recipients_by_status['send'].'</td>';
							echo '<td>'.((int)$recipients_by_status['send'] > 0 ? '100%' : '0%').'</td>';
							echo '<td>'.$this->formatNumber(((int)$recipients_by_status['send'] > 0 ? (int)$recipients_by_status['send']*100/(int)$all_rec_in_survey : 0),0,'%').'</td>';
							echo '</tr>';
								
	
							#neodgovori
							echo '<tr>';
							echo '<td>'.$lang['srv_inv_dashboard_tbl_unanswered'].'</td>';
							$unanswered = ((int)$recipients_by_status['send']-(int)$recipients_by_status['clicked']-(int)$recipients_by_status['finished']);
									echo '<td>'.$unanswered.'</td>';
									echo '<td>'.$this->formatNumber(($unanswered > 0 ? $unanswered*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
									echo '<td>'.$this->formatNumber(($unanswered > 0 ? $unanswered*100/(int)$all_rec_in_survey : 0),0,'%').'</td>';
									echo '</tr>';
									echo '<tr>';
									echo '<td>'.$lang['srv_inv_dashboard_tbl_clicked'].'</td>';
									echo '<td>'.(int)$recipients_by_status['clicked'].'</td>';
									echo '<td>'.$this->formatNumber(((int)$recipients_by_status['clicked'] > 0 ? (int)$recipients_by_status['clicked']*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
									echo '<td>'.$this->formatNumber(((int)$recipients_by_status['clicked'] > 0 ? (int)$recipients_by_status['clicked']*100/(int)$all_rec_in_survey : 0),0,'%').'</td>';
									echo '</tr>';
										
									#če se slučajno pojavijo kaki neznani statusi
									if ((int)$recipients_by_status['unknown'] > 0) {
									echo '<tr>';
									echo '<td>'.$lang['srv_inv_dashboard_tbl_unknown'].'</td>';
									echo '<td>'.(int)$recipients_by_status['unknown'].'</td>';
									echo '<td>'.$this->formatNumber(((int)$recipients_by_status['unknown'] > 0 ? (int)$recipients_by_status['unknown']*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
									echo '<td>'.$this->formatNumber(((int)$recipients_by_status['unknown'] > 0 ? (int)$recipients_by_status['unknown']*100/(int)$all_rec_in_survey : 0),0,'%').'</td>';
									echo '</tr>';
									}
									echo '<tr>';
									echo '<td>'.$lang['srv_inv_dashboard_tbl_finished'].'</td>';
									echo '<td>'.(int)$recipients_by_status['finished'].'</td>';
									echo '<td class="red">'.$this->formatNumber(((int)$recipients_by_status['finished'] > 0 ? (int)$recipients_by_status['finished']*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
									echo '<td class="">'.$this->formatNumber(((int)$recipients_by_status['finished'] > 0 ? (int)$recipients_by_status['finished']*100/(int)$all_rec_in_survey : 0),0,'%').'</td>';
									echo '</tr>';
									echo '</table>';
						}
	
					}
	
				}
			} else {
				#Vabil še nismo pošiljali
				echo $lang['srv_inv_dashboard_empty'].' <a href="'.$site_url . 'admin/survey/index.php?anketa='.$this->sid.'&amp;a='.A_INVITATIONS.'&amp;m=add_recipients_view">'.$lang['srv_inv_dashboard_add_link'].'</a>';
			}
	
		} else {
			echo $lang['srv_inv_dashboard_not_enabled'];
		}
		echo '</p>';
		echo '</div>';
		echo '</fieldset>';
	
		#pošiljanje po enotah
		$cnt_by_sendings = array();
		$all_units_count = 0;
		# najprej koliko enotam še ni bilo poslano
		$sel = "select count(*) FROM srv_invitations_recipients WHERE ank_id='$this->sid' AND sent = '0'";
		$query = sisplet_query($sel);
		list($count) = mysqli_fetch_row($query);
		if ($count > 0) {
		$cnt_by_sendings[0] = (int)$count;
		}
		$all_units_count = (int)$count;
		$sel1 = "select count(*) as cnt, rec_id FROM srv_invitations_archive_recipients WHERE arch_id in (select id from srv_invitations_archive where ank_id = '".$this->sid."') AND success !='0' group by rec_id ORDER BY cnt ASC;";
		$query1 = sisplet_query($sel1);
	
		while (list($count, $rec_id) = mysqli_fetch_row($query1)) {
			$cnt_by_sendings[(int)$count] ++;
			$all_units_count++;
		}
		if (count($cnt_by_sendings) > 0) {
			echo '<fieldset class="inv_fieldset">';
			echo '<legend >';
			echo '<span class="pointer legend" onClick="$(this).parent().parent().find(\'.inv_filedset_inline_div\').toggle(); $(this).find(\'.plus\').toggle();$(this).find(\'.minus\').toggle();">';
			echo '<span class="plus red strong">+ </span>';
			echo '<span class="minus red strong displayNone">- </span>';
			echo $lang['srv_inv_nav_email_sending_status'];
			echo '</span>';
			echo Help::display('srv_inv_cnt_by_sending');
			echo '</legend>';
			echo '<br/>';
			echo '<div class="inv_filedset_inline_div displayNone">';
			echo '<table style="border-spacing: 0px;padding: 0px;margin: 0px;">';
			echo '<colgrup>';
			echo '<col style="min-width:150px;"/>';
			echo '<col style="min-width:150px;"/>';
			echo '<col style="min-width:150px;"/>';
			echo '</colgrup>';
			echo '<tr>';
			echo '<th class="anl_al">'.$lang['srv_inv_sending_overview_cnt'].'</th>';
			echo '<th class="anl_al">'.$lang['srv_inv_sending_overview_units'].'</th>';
			echo '<th class="anl_al">'.$lang['srv_inv_sending_overview_percentage'].'</th>';
			echo '</tr>';
			foreach ($cnt_by_sendings AS $cnt => $units) {
				echo '<tr>';
				echo '<td>'.$cnt.'</td>';
				echo '<td>'.$units.'</td>';
				$percent = ($all_units_count > 0) ? $units / $all_units_count * 100 : 0;
				echo '<td>'.Common::formatNumber ($percent,0,null,'%').'</td>';
				echo '</tr>';
			}
			echo '<tr>';
			echo '<td class="anl_bt_dot red">'.$lang['srv_inv_sending_overview_sum'].'</td>';
			echo '<td class="anl_bt_dot red">'.$all_units_count.'</td>';
			$percent = ($all_units_count > 0) ? $all_units_count / $all_units_count * 100 : 0;
			echo '<td class="anl_bt_dot red">'.Common::formatNumber ($percent,0,null,'%').'</td>';
			echo '</tr>';
			echo '</table>';
			echo '</div>';
			echo '</fieldset>';
		}
	}
	
    // Prikaz statusov posiljanj
	private function displayInvitationStatusNew() {
		global $lang, $admin_type, $global_user_id, $site_url, $site_path, $app_settings;
		
		$isEmail = (int)SurveyInfo::getInstance()->checkSurveyModule('email');

		$userAccess = UserAccess::getInstance($global_user_id);

		// Email vabila so omogocena
		if ((int)$isEmail > 0 && $userAccess->checkUserAccess($what='invitations')) {
			
			echo '<fieldset class="inv_fieldset"><legend>'.$lang['srv_inv_nav_email_status'].'</legend>';
			echo '<div class="inv_filedset_inline_div">';
			echo '<p>';	
			
			#koliko je vseh uporabnikov v bazi
			$sql_query = sisplet_query("SELECT count(*) as cnt FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted ='0'");
			list($cnt_all_in_db) =  mysqli_fetch_row($sql_query);
				
			#zloopamo skozi posamezna pošiljanja in preštejemo vse potrebno
			$sql_query = sisplet_query("SELECT sia.id, sia.tip, rec_in_db, DATE_FORMAT(sia.date_send,'%d.%m.%Y, %T') AS ds,  u.name, u.surname, u.email 
                                            FROM srv_invitations_archive AS sia 
                                                INNER JOIN users AS u ON sia.uid = u.id 
                                            WHERE ank_id = '".$this->sid."' 
                                            ORDER BY sia.date_send ASC;
                                    ");

            $array_dashboard = array();
			$array_archive_subdata = array();
			$user_max_status = array();
			$user_lurker = array();
            
            # štetje po pošiljanjih
            $cnt_by_user = array();
            
			if (mysqli_num_rows($sql_query) > 0) {

				#loop po vseh arhivih
				while($row  = mysqli_fetch_assoc($sql_query)) {
					$array_archive_subdata[$row['id']] = $row;
					$sql_subStr =  "SELECT sit.res_id,sit.status FROM srv_invitations_tracking AS sit WHERE sit.inv_arch_id = '".$row['id']."' AND sit.res_id IN (SELECT id FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted ='0')";
					$sql_subStr =  "SELECT sit.res_id,sit.status, su.lurker FROM srv_invitations_tracking AS sit"
						." INNER JOIN srv_invitations_recipients AS sir ON sit.res_id = sir.id"
						." INNER join srv_user AS su ON sit.res_id = su.inv_res_id"
						." WHERE sir.ank_id='$this->sid' AND sir.deleted ='0' AND su.ank_id='$this->sid' AND sit.inv_arch_id = '$row[id]'";
					$sql_subQry = sisplet_query($sql_subStr);
					$sub_max = array();
					#loop po vseh trackingih posameznega arhiva
					while($subRow  = mysqli_fetch_assoc($sql_subQry)) {
						if ((int)$subRow['status'] == 2) {
							$subRow['status'] = -2;
							
						}
						if ((int)$subRow['status'] == 1) {
							$cnt_by_user[$subRow['res_id']]++;
						}
						#maximalni status uporabnika za posamezen arhiv
						$sub_max[$subRow['res_id']] = max($sub_max[$subRow['res_id']],$subRow['status']);
						
						#globalni max statusi posameznih uporabnikov
						$_userMaxStatus = max($user_max_status[$subRow['res_id']],$subRow['status']);
						$user_max_status[$subRow['res_id']] = $_userMaxStatus;
						$user_lurker[$subRow['res_id']] = $subRow['lurker'];
					}
					#maximalni statusi uporabniak v posameznem arhivu
					$array_dashboard[$row['id']] = $sub_max;
				}
			}
			
			# preštejemo respondente po statusu
			$recipients_by_status = array();
			$recipients_by_status['all']=(int)$cnt_all_in_db;
			$user_by_status_for_archive = array();
			if (count($user_max_status) > 0) {

				foreach ($user_max_status AS $uid => $status) {
					switch ((int)$status) {
						# 2 - E-pošta - napaka
						case -2:
							$recipients_by_status['not_send'] ++;
							$recipients_by_status['error'] ++;
						break;
						# 0 - E-pošta - ni poslana
						case 0:
							$recipients_by_status['not_send'] ++;
						break;
						# 1 - E-pošta - neodgovor
						case 1:
							$recipients_by_status['send'] ++;
						break;
						
						# 3 - klik na nagovor
						case 3:
							$recipients_by_status['send'] ++;
							$recipients_by_status['clicked'] ++;
						break;
						# 4 - klik na anketo
						case 4:
							$recipients_by_status['send'] ++;
							$recipients_by_status['clicked'] ++;
						break;
						# 5 - delno prazna
						case 5:
							$recipients_by_status['send'] ++;
							#$recipients_by_status['clicked'] ++;
							if ($user_lurker[$uid] == 1) {
								# če je lurker
								$recipients_by_status['clicked'] ++;
							} else {
								$recipients_by_status['finished'] ++;
							}
						break;
						# 6 - končana
						case 6:
							$recipients_by_status['send'] ++;
							if ($user_lurker[$uid] == 1) {
								# če je lurker
								$recipients_by_status['clicked'] ++;
							} else {
								$recipients_by_status['finished'] ++;
							}
						break;
						# null - neznan
						default:
							$recipients_by_status['unknown'] ++;
						break;
					}
				}
				echo '<table class="inv_dashboard_table">';
				echo '<tr>';
				echo '<th>'.$lang['srv_inv_dashboard_tbl_all'].'</th>';
				echo '<th>'.(int)$recipients_by_status['all'].'</th>';
				echo '<th>-</th>';
				echo '<th>100%</th>';
				echo '</tr>';
				#popslano enotam
				echo '<tr>';
				echo '<th>'.$lang['srv_inv_dashboard_tbl_send'].'</th>';
				echo '<th>'.(int)$recipients_by_status['send'].'</th>';
				echo '<th>'.((int)$recipients_by_status['send'] > 0 ? '100%' : '0%').'</th>';
				echo '<th>'.$this->formatNumber(((int)$recipients_by_status['send'] > 0 ? (int)$recipients_by_status['send']*100/(int)$recipients_by_status['all'] : 0),0,'%').'</th>';
				echo '</tr>';
					
				#neodgovori
				echo '<tr>';
				echo '<td>'.$lang['srv_inv_dashboard_tbl_unanswered'].'</td>';
				$unanswered = ((int)$recipients_by_status['send']-(int)$recipients_by_status['clicked']-(int)$recipients_by_status['finished']);
				echo '<td>'.$unanswered.'</td>';
				echo '<td>'.$this->formatNumber(($unanswered > 0 ? $unanswered*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
				echo '<td>'.$this->formatNumber(($unanswered > 0 ? $unanswered*100/(int)$recipients_by_status['all'] : 0),0,'%').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>'.$lang['srv_inv_dashboard_tbl_clicked'].'</td>';
				echo '<td>'.(int)$recipients_by_status['clicked'].'</td>';
				echo '<td>'.$this->formatNumber(((int)$recipients_by_status['clicked'] > 0 ? (int)$recipients_by_status['clicked']*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
				echo '<td>'.$this->formatNumber(((int)$recipients_by_status['clicked'] > 0 ? (int)$recipients_by_status['clicked']*100/(int)$recipients_by_status['all'] : 0),0,'%').'</td>';
				echo '</tr>';
					
				#če se slučajno pojavijo kaki neznani statusi
				if ((int)$recipients_by_status['unknown'] > 0) {
					echo '<tr>';
					echo '<td>'.$lang['srv_inv_dashboard_tbl_unknown'].'</td>';
					echo '<td>'.(int)$recipients_by_status['unknown'].'</td>';
					echo '<td>'.$this->formatNumber(((int)$recipients_by_status['unknown'] > 0 ? (int)$recipients_by_status['unknown']*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
					echo '<td>'.$this->formatNumber(((int)$recipients_by_status['unknown'] > 0 ? (int)$recipients_by_status['unknown']*100/(int)$recipients_by_status['all'] : 0),0,'%').'</td>';
					echo '</tr>';
				}
				echo '<tr>';
				echo '<td>'.$lang['srv_inv_dashboard_tbl_finished'].'</td>';
				echo '<td>'.(int)$recipients_by_status['finished'].'</td>';
				echo '<td class="red">'.$this->formatNumber(((int)$recipients_by_status['finished'] > 0 ? (int)$recipients_by_status['finished']*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
				echo '<td class="">'.$this->formatNumber(((int)$recipients_by_status['finished'] > 0 ? (int)$recipients_by_status['finished']*100/(int)$recipients_by_status['all'] : 0),0,'%').'</td>';
				echo '</tr>';
				echo '</table>';
				echo '<br>';

				
				# POSAMEZNA pošiljanja
				if (count($array_dashboard ) > 0) {
					foreach ($array_dashboard AS $archive_id => $archive_data) {
						if (count($archive_data ) > 0) {
							foreach ($archive_data AS $uid => $status) {
								if ((int)$status == 6 && $user_lurker[$uid] == 1) {
									$user_by_status_for_archive[$archive_id]['6l']++;
								} else	if ((int)$status == 5 && $user_lurker[$uid] == 1) {
									$user_by_status_for_archive[$archive_id]['5l']++;
								} else {
									$user_by_status_for_archive[$archive_id][$status]++;
								}
								
							}
						}
					}
				}
				$cnt = 0;
				if (count($user_by_status_for_archive ) > 0) {
					foreach ($user_by_status_for_archive AS $arch_id => $archive_data) {
						$cnt++;
						if (count($archive_data ) > 0) {
							$recipients_by_status = array();
							$recipients_by_status['all']=(int)$array_archive_subdata[$arch_id]['rec_in_db'];
							foreach ($archive_data AS $status => $cntUsers) {
									# 0 - E-pošta - ni poslana
									if( $status == '0') {
										$recipients_by_status['not_send'] +=$cntUsers;
									# 1 - E-pošta - neodgovor'
									} else if( $status == '1') {
										$recipients_by_status['send'] +=$cntUsers;
									# 2 - E-pošta - napaka
									} else if( $status == '2') {
										$recipients_by_status['error'] +=$cntUsers;
									# 3 - klik na nagovor
									} else if( $status == '3') {
										$recipients_by_status['send'] +=$cntUsers;
										$recipients_by_status['clicked'] +=$cntUsers;
									# 4 - klik na anketo
									} else if( $status == '4') {
										$recipients_by_status['send'] +=$cntUsers;
										$recipients_by_status['clicked'] +=$cntUsers;
									# 5 - delno prazna
									} else if( $status == '5') {
										$recipients_by_status['send'] +=$cntUsers;
										#$recipients_by_status['clicked'] +=$cntUsers;
										$recipients_by_status['finished'] +=$cntUsers;
										
									# 5 - delno prazna -lurker
									} else if( $status == '5l') {
										$recipients_by_status['send'] +=$cntUsers;
										$recipients_by_status['clicked'] +=$cntUsers;
										
									# 6 - končana
									} else if( $status == '6') {
										$recipients_by_status['send'] +=$cntUsers;
										$recipients_by_status['finished'] +=$cntUsers;
									# 6 - končana - lurker
									} else if( $status == '6l') {
										$recipients_by_status['send'] +=$cntUsers;
										$recipients_by_status['clicked'] +=$cntUsers;
									} else {
									# null - neznan
										$recipients_by_status['unknown'] +=$cntUsers;
									}
							}
							$avtor_email = iconv("iso-8859-2", "utf-8",trim ($array_archive_subdata[$arch_id]['email']));
							$avtor = array();
							if (trim($array_archive_subdata[$arch_id]['name'])) {
								$avtor[] = trim ($array_archive_subdata[$arch_id]['name']);
							}
							if (trim($array_archive_subdata[$arch_id]['surname'])) {
								$avtor[] = trim ($array_archive_subdata[$arch_id]['surname']);
							}
							if ( count($avtor) > 0 ) {
								$avtor_name = implode(' ',$avtor);
							} else {
								$avtor_name = $avtor_email;
							}
							$all_rec_in_survey = (int)$recipients_by_status['all'];
							
							echo '<span class="pointer span_list_archive" onClick="$(this).next().next().next().toggle(); $(this).find(\'.plus\').toggle();$(this).find(\'.minus\').toggle(); $(this).next(\'.link_archive\').toggle();">';
							
							echo '<span class="inv_dashboard_sub_title as_link">';
							echo '<span class="plus" style="color: inherit;">+ </span>';
							echo '<span class="minus displayNone" style="color: inherit;">- </span>';
							echo $cnt.$lang['srv_inv_dashboard_list_cnt_title'];
							echo '</span>';
							
							// avtor
							echo '<span style="font-size: 13px;" title="'.$avtor_email.'">'.$avtor_name.'</span>';
							
							// datum
							echo ', ';
							echo '<span style="font-size: 13px;">'.$array_archive_subdata[$arch_id]['ds'].'</span>';
							
							// nacin posiljanja
							echo ', ';
							echo '<span style="font-size: 13px;">';
							if ($array_archive_subdata[$arch_id]['tip'] == '0')
								echo '<span>'.$lang['srv_inv_message_noemailing_type1'].'</span>';
							elseif($array_archive_subdata[$arch_id]['tip'] == '1')
								echo '<span>'.$lang['srv_inv_message_noemailing_type2'].'</span>';
							elseif($array_archive_subdata[$arch_id]['tip'] == '2')
								echo '<span>'.$lang['srv_inv_message_noemailing_type3'].'</span>';
							else
								echo '<span>'.$lang['email'].'</span>';
							echo '</span>';
							
							echo '</span>';
							
							// arhiv
							echo '<span class="link_archive as_link displayNone" style="margin-left:10px;"><a href="#" onclick="inv_arch_show_recipients(\''.$array_archive_subdata[$arch_id]['id'].'\'); return false;"> arhiv </a></span>';
							echo '<br/>';
								
								
							echo '<table class="inv_dashboard_table sub displayNone">';
							
							echo '<tr>';
							echo '<th>'.$lang['srv_inv_dashboard_tbl_all'].'</th>';
							echo '<th>'.(int)$all_rec_in_survey.'</th>';
							#echo '<th>'.(int)$recipients_by_status['all'].'</th>';
							echo '<th>&nbsp;</th>';
							echo '<th>100%</th>';
							echo '</tr>';	
							
							# poslano enotam
							echo '<tr>';
							echo '<td>'.$lang['srv_inv_dashboard_tbl_send'].'</td>';
							echo '<td>'.(int)$recipients_by_status['send'].'</td>';
							echo '<td>'.((int)$recipients_by_status['send'] > 0 ? '100%' : '0%').'</td>';
							echo '<td>'.$this->formatNumber(((int)$all_rec_in_survey > 0 ? (int)$recipients_by_status['send']*100/(int)$all_rec_in_survey : 0),0,'%').'</td>';
							echo '</tr>';
								
							# neodgovori
							echo '<tr>';
							echo '<td>'.$lang['srv_inv_dashboard_tbl_unanswered'].'</td>';
							$unanswered = ((int)$recipients_by_status['send']-(int)$recipients_by_status['clicked']-(int)$recipients_by_status['finished']);
							echo '<td>'.$unanswered.'</td>';
							echo '<td>'.$this->formatNumber(($recipients_by_status['send'] > 0 ? $unanswered*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
							echo '<td>'.$this->formatNumber(($all_rec_in_survey > 0 ? $unanswered*100/(int)$all_rec_in_survey : 0),0,'%').'</td>';
							echo '</tr>';
							echo '<tr>';
							echo '<td>'.$lang['srv_inv_dashboard_tbl_clicked'].'</td>';
							echo '<td>'.(int)$recipients_by_status['clicked'].'</td>';
							echo '<td>'.$this->formatNumber(((int)$recipients_by_status['send'] > 0 ? (int)$recipients_by_status['clicked']*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
							echo '<td>'.$this->formatNumber(((int)$all_rec_in_survey > 0 ? (int)$recipients_by_status['clicked']*100/(int)$all_rec_in_survey : 0),0,'%').'</td>';
							echo '</tr>';

							#če se slučajno pojavijo kaki neznani statusi
							if ((int)$recipients_by_status['unknown'] > 0) {
								echo '<tr>';
								echo '<td>'.$lang['srv_inv_dashboard_tbl_unknown'].'</td>';
								echo '<td>'.(int)$recipients_by_status['unknown'].'</td>';
								echo '<td>'.$this->formatNumber(((int)$recipients_by_status['send'] > 0 ? (int)$recipients_by_status['unknown']*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
								echo '<td>'.$this->formatNumber(((int)$all_rec_in_survey > 0 ? (int)$recipients_by_status['unknown']*100/(int)$all_rec_in_survey : 0),0,'%').'</td>';
								echo '</tr>';
							}
							echo '<tr>';
							echo '<td>'.$lang['srv_inv_dashboard_tbl_finished'].'</td>';
							echo '<td>'.(int)$recipients_by_status['finished'].'</td>';
							echo '<td class="red">'.$this->formatNumber(((int)$recipients_by_status['send'] > 0 ? (int)$recipients_by_status['finished']*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
							echo '<td class="">'.$this->formatNumber(((int)$all_rec_in_survey > 0 ? (int)$recipients_by_status['finished']*100/(int)$all_rec_in_survey : 0),0,'%').'</td>';
							echo '</tr>';

							#napake
							if ((int)$recipients_by_status['error'] > 0) {
								echo '<tr>';
								echo '<td class="anl_bt">'.$lang['srv_inv_dashboard_tbl_error'].'</td>';
							
								echo '<td class="anl_bt">'.(int)$recipients_by_status['error'].'</td>';
								echo '<td class="anl_bt">&nbsp;</td>';
								echo '<td class="anl_bt">'.$this->formatNumber(((int)$recipients_by_status['error'] > 0 ? (int)$recipients_by_status['error']*100/(int)$all_rec_in_survey : 0),0,'%').'</td>';
								echo '</tr>';
							} 
									
							echo '</table>';
								
						}
					}
				}
			} else {
				// Imamo sezname, ni pa poslanih vabil
				if ((int)$cnt_all_in_db > 0){
					echo $lang['srv_inv_dashboard_has_list2'];

					//echo '<p class="spaceLeft bold"><a href="'.$site_url . 'admin/survey/index.php?anketa='.$this->sid.'&amp;a='.A_INVITATIONS.'&amp;m=add_recipients_view">'.$lang['srv_inv_dashboard_add_list'].'</a></p>';
					echo '<div class="buttonwrapper"><a class="ovalbutton floatLeft spaceLeft" href="'.$site_url.'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=add_recipients_view">'.$lang['srv_adding_email_respondents'].'</a></div>';
					echo '<div class="buttonwrapper"><a class="ovalbutton floatLeft spaceLeft" href="'.$site_url.'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=send_message">'.$lang['srv_inv_message_draft_send'].'</a></div>';
					echo '<br />';
					
					//echo '<p class="spaceLeft bold"><a href="'.$site_url . 'admin/survey/index.php?anketa='.$this->sid.'&amp;a='.A_INVITATIONS.'&amp;m=view_recipients">'.$lang['srv_inv_dashboard_view_list'].'</a></p>';
				}
				// Ni seznamov in ni poslanih vabil
				else{
					echo $lang['srv_inv_dashboard_empty'];
					
					//echo '<p class="spaceLeft bold"><a href="'.$site_url . 'admin/survey/index.php?anketa='.$this->sid.'&amp;a='.A_INVITATIONS.'&amp;m=add_recipients_view">'.$lang['srv_inv_dashboard_add_list'].'</a></p>';
					echo '<div class="buttonwrapper"><a class="ovalbutton floatLeft spaceLeft" href="'.$site_url.'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=add_recipients_view">'.$lang['srv_adding_email_respondents'].'</a></div>';
					echo '<br /><br />';
				}
			}
			
			echo '</p>';
			echo '</div>';
			echo '</fieldset>';
		}
		// Email vabila niso omogocena
		else {
			echo '<fieldset class="inv_fieldset"><legend>'.$lang['srv_inv_nav_email_status'].'</legend>';
			echo '<div class="inv_filedset_inline_div">';
			echo '<p>';
				
			echo $lang['srv_inv_dashboard_not_enabled'];
		
			# uporabnik nima pravic omogočit vabil
			if (!$userAccess->checkUserAccess($what='invitations')) {
				echo '<br/>'.$lang['srv_inv_dashboard_no_permissions'];
			} 
			# uporabnik lahko vklopi email vabila
			else {
				echo '&nbsp;<a href="#" onclick="enableEmailInvitation(this);">'.$lang['srv_omogoci'].'</a>';
			}
			
			echo '</p>';
			echo '</div>';
			echo '</fieldset>';
		}
		
        
        // predpripravimo podatke za vsa pošiljanja
        $cnt_by_sendings = array();
        
		$all_units_count = count($cnt_by_user);
		if ($all_units_count > 0) {
			foreach ($cnt_by_user AS $uid => $ucnt) {
				$cnt_by_sendings[$ucnt]++;
			}	
            
			echo '<br/>';
			
			#pregled po pošiljanjih	
			echo '<fieldset class="inv_fieldset">';
			
			echo '<legend>';
			echo '<span class="pointer" onClick="$(this).parent().parent().find(\'.inv_filedset_inline_div\').toggle(); $(this).find(\'.plus\').toggle();$(this).find(\'.minus\').toggle();">';
			echo '<span class="plus strong displayNone blue">+ </span>';
			echo '<span class="minus strong blue">- </span>';
			echo '<span class="legend blue">'.$lang['srv_inv_nav_email_sending_status'].'</span>';
			echo '</span>';
			echo Help::display('srv_inv_cnt_by_sending');
			echo '</legend>';
			
			echo '<br/>';
			
			echo '<div class="inv_filedset_inline_div">';
			echo '<table style="border-spacing:0px; padding:0px; margin:0 0 20px 15px;">';
			echo '<colgrup>';
			echo '<col style="min-width:150px;"/>';
			echo '<col style="min-width:150px;"/>';
			echo '<col style="min-width:150px;"/>';
			echo '</colgrup>';
			echo '<tr>';
			echo '<th class="anl_al">'.$lang['srv_inv_sending_overview_cnt'].'</th>';
			echo '<th class="anl_al">'.$lang['srv_inv_sending_overview_units'].'</th>';
			echo '<th class="anl_al">'.$lang['srv_inv_sending_overview_percentage'].'</th>';
			echo '</tr>';
			if ($cnt_by_sendings > 0) {
				foreach ($cnt_by_sendings AS $cnt => $units) {
					echo '<tr>';
					echo '<td>'.$cnt.'</td>';
					echo '<td>'.$units.'</td>';
					$percent = ($all_units_count > 0) ? $units / $all_units_count * 100 : 0;
					echo '<td>'.Common::formatNumber ($percent,0,null,'%').'</td>';
					echo '</tr>';
				}
			}
			echo '<tr>';
			echo '<td class="anl_bt_dot red">'.$lang['srv_inv_sending_overview_sum'].'</td>';
			echo '<td class="anl_bt_dot red">'.$all_units_count.'</td>';
			$percent = ($all_units_count > 0) ? $all_units_count / $all_units_count * 100 : 0;
			echo '<td class="anl_bt_dot red">'.Common::formatNumber ($percent,0,null,'%').'</td>';
			echo '</tr>';
			echo '</table>';
			echo '</div>';
			echo '</fieldset>';
		}
	}

    // Prikaz statusov posiljanj pri volitvah
    private function displayInvitationStatusVoting() {
		global $lang, $admin_type, $global_user_id, $site_url, $site_path, $app_settings;
		
		$isEmail = (int)SurveyInfo::getInstance()->checkSurveyModule('email');

		$userAccess = UserAccess::getInstance($global_user_id);

		// Email vabila so omogocena
		if ((int)$isEmail > 0 && $userAccess->checkUserAccess($what='invitations')) {
			
			echo '<fieldset class="inv_fieldset"><legend>'.$lang['srv_inv_nav_email_status'].'</legend>';
			echo '<div class="inv_filedset_inline_div">';
			echo '<p>';	
			
			#koliko je vseh uporabnikov v bazi in kolkim je bil mail poslan
			$sql_count = sisplet_query("SELECT count(id) as cnt, sent
                                            FROM srv_invitations_recipients 
                                            WHERE ank_id='".$this->sid."' AND deleted ='0'
                                            GROUP BY sent
                                    ");

            $cnt_all_in_db = 0;
            $cnt_sent_in_db = 0;
			while($row_count = mysqli_fetch_array($sql_count)){

                $cnt_all_in_db += (int)$row_count['cnt'];

                if($row_count['sent'] == '1'){
                    $cnt_sent_in_db += (int)$row_count['cnt'];
                }
            }

				
            echo '<table class="inv_dashboard_table">';

            // Vsi v bazi
            echo '<tr>';
            echo '<th>'.$lang['srv_inv_dashboard_tbl_all'].'</th>';
            echo '<th>'.(int)$cnt_all_in_db.'</th>';
            echo '<th>-</th>';
            echo '<th>100%</th>';
            echo '</tr>';

            // Poslani
            echo '<tr>';
            echo '<td>'.$lang['srv_inv_dashboard_tbl_send'].'</td>';
            echo '<td>'.(int)$cnt_sent_in_db.'</td>';
            echo '<td>'.((int)$cnt_sent_in_db > 0 ? '100%' : '0%').'</td>';
            echo '<td>'.$this->formatNumber(((int)$cnt_sent_in_db > 0 ? (int)$cnt_sent_in_db*100/(int)$cnt_all_in_db : 0),0,'%').'</td>';
            echo '</tr>';

            echo '</table>';

            echo '</p>';
			echo '</div>';
			echo '</fieldset>';
		}
		// Email vabila niso omogocena
		else {
			echo '<fieldset class="inv_fieldset"><legend>'.$lang['srv_inv_nav_email_status'].'</legend>';
			echo '<div class="inv_filedset_inline_div">';
			echo '<p>';
				
			echo $lang['srv_inv_dashboard_not_enabled'];
		
			# uporabnik nima pravic omogočit vabil
			if (!$userAccess->checkUserAccess($what='invitations')) {
				echo '<br/>'.$lang['srv_inv_dashboard_no_permissions'];
			} 
			# uporabnik lahko vklopi email vabila
			else {
				echo '&nbsp;<a href="#" onclick="enableEmailInvitation(this);">'.$lang['srv_omogoci'].'</a>';
			}
			
			echo '</p>';
			echo '</div>';
			echo '</fieldset>';
		}
		
        
        // predpripravimo podatke za vsa pošiljanja
        /*$cnt_by_sendings = array();
        
		$all_units_count = count($cnt_by_user);
		if ($all_units_count > 0) {
			foreach ($cnt_by_user AS $uid => $ucnt) {
				$cnt_by_sendings[$ucnt]++;
			}	
            
			echo '<br/>';
			
			#pregled po pošiljanjih	
			echo '<fieldset class="inv_fieldset">';
			
			echo '<legend>';
			echo '<span class="pointer" onClick="$(this).parent().parent().find(\'.inv_filedset_inline_div\').toggle(); $(this).find(\'.plus\').toggle();$(this).find(\'.minus\').toggle();">';
			echo '<span class="plus strong displayNone blue">+ </span>';
			echo '<span class="minus strong blue">- </span>';
			echo '<span class="legend blue">'.$lang['srv_inv_nav_email_sending_status'].'</span>';
			echo '</span>';
			echo Help::display('srv_inv_cnt_by_sending');
			echo '</legend>';
			
			echo '<br/>';
			
			echo '<div class="inv_filedset_inline_div">';
			echo '<table style="border-spacing:0px; padding:0px; margin:0 0 20px 15px;">';
			echo '<colgrup>';
			echo '<col style="min-width:150px;"/>';
			echo '<col style="min-width:150px;"/>';
			echo '<col style="min-width:150px;"/>';
			echo '</colgrup>';
			echo '<tr>';
			echo '<th class="anl_al">'.$lang['srv_inv_sending_overview_cnt'].'</th>';
			echo '<th class="anl_al">'.$lang['srv_inv_sending_overview_units'].'</th>';
			echo '<th class="anl_al">'.$lang['srv_inv_sending_overview_percentage'].'</th>';
			echo '</tr>';
			if ($cnt_by_sendings > 0) {
				foreach ($cnt_by_sendings AS $cnt => $units) {
					echo '<tr>';
					echo '<td>'.$cnt.'</td>';
					echo '<td>'.$units.'</td>';
					$percent = ($all_units_count > 0) ? $units / $all_units_count * 100 : 0;
					echo '<td>'.Common::formatNumber ($percent,0,null,'%').'</td>';
					echo '</tr>';
				}
			}
			echo '<tr>';
			echo '<td class="anl_bt_dot red">'.$lang['srv_inv_sending_overview_sum'].'</td>';
			echo '<td class="anl_bt_dot red">'.$all_units_count.'</td>';
			$percent = ($all_units_count > 0) ? $all_units_count / $all_units_count * 100 : 0;
			echo '<td class="anl_bt_dot red">'.Common::formatNumber ($percent,0,null,'%').'</td>';
			echo '</tr>';
			echo '</table>';
			echo '</div>';
			echo '</fieldset>';
		}*/
	}


	function showInvitationSettings() {
		global $lang, $admin_type, $global_user_id, $site_url, $site_path, $app_settings;
		
		$row = $this->surveySettings;
		$_email = (int)SurveyInfo::getInstance()->checkSurveyModule('email');
		
		$sqlu = sisplet_query("SELECT email FROM users WHERE id='".$global_user_id."'");
		$rowu = mysqli_fetch_array($sqlu);
		if ($rowu['email'] == '') {
			$sqlm = sisplet_query("SELECT * FROM misc WHERE what = 'AlertFrom'");
			$rowm = mysqli_fetch_array($sqlm);
			$rowu['email'] = $rowm['value'];
		}

		$userAccess = UserAccess::getInstance($global_user_id);
         
		$noEmailing = SurveySession::get('inv_noEmailing');

		# Admini, managerji in Clani, ki imajo odobren dostop - lahko vklopijo vabila
		if ($userAccess->checkUserAccess($what='invitations')) {

			// Vklop vabil
			if ($_email == 0) {
				
				echo '<fieldset class="inv_fieldset" style="max-width:800px; padding-bottom:15px;"><legend>'.$lang['srv_invitation_nonActivated_title'].'</legend>';
				echo '<div class="inv_filedset_inline_div">';
				
				echo '<p>';
				echo $lang['srv_invitation_nonActivated_text1'];
				echo '</p>';
				
                echo '<p>';
                if($lang['id'] == '1')
                    echo sprintf($lang['srv_invitation_nonActivated_text2'], 'https://www.1ka.si/d/sl/pomoc/prirocniki/posiljanje-email-vabil-pridobitev-dovoljenja?from1ka=1');
                else
                    echo sprintf($lang['srv_invitation_nonActivated_text2'], 'https://www.1ka.si/d/en/help/manuals/sending-email-invitations-and-obtaining-authorization?from1ka=1');
				echo '</p>';
				
				echo '<p>';
				// Za gorenje popravimo text
				$text3 = (Common::checkModule('gorenje')) ? str_replace('1KA', 'ESurvey', $lang['srv_invitation_nonActivated_text3']) : $lang['srv_invitation_nonActivated_text3'];
				echo $text3;
				echo '</p>';
				
				// Gumb OMOGOCI VABILA
				$text_button = (Common::checkModule('gorenje')) ? str_replace('1KA', 'ESurvey', $lang['srv_invitation_nonActivated_button_activate']) : $lang['srv_invitation_nonActivated_button_activate'];
				echo '<span class="buttonwrapper floatLeft spaceRight"><a class="ovalbutton ovalbutton_orange"  href="#" onclick="enableEmailInvitation(\'1\');">'.$text_button.'</a></span>';
				//echo '<span class="spaceLeft bold" style="line-height:25px;"><a href="https://www.1ka.si/c/804/Email_vabila/?preid=793&from1ka=1">'.$lang['srv_invitation_nonActivated_more'].'</a></span>';
				echo '<br />';

				echo '</div>';
				echo '</fieldset>';
			}
			// Vabila so vklopljena - NASTAVITVE
			else{
			
				if($noEmailing == 1){
					echo '<table class="invitations_settings" style="width:50%;">';					
				}
				else{
					echo '<table class="invitations_settings" style="width:100%;">';	
					echo '<colgroup style="width:48%;"></colgroup>';	
					echo '<colgroup style="width:48%;"></colgroup>';
				}
				
				echo '<tr>';
				
				
				// Leva stran - navadne nastavitve
				echo '<td style="padding-right:20px;vertical-align: top;">';
				echo '<fieldset class="inv_fieldset"><legend>'.$lang['srv_inv_nav_invitations_settings_general'].' '.Help::display('srv_inv_general_settings').'</legend>';
				echo '<div class="inv_filedset_inline_div">';
				
				echo '<div id="surveyInvitationSetting">';

                // Preverimo ce je vklopljen modul za volitve - potem ne pustimo nobenih preklopov
                $voting_disabled = '';
                if(SurveyInfo::getInstance()->checkSurveyModule('voting')){
                    $voting_disabled = ' disabled';

                    // Warning za volitve
                    echo '<p class="red bold">'.$lang['srv_voting_warning'].'</p>';
                }
				
				$individual = (int)$this->surveySettings['individual_invitation'];
				
				// Individualizirana vabila - GLAVNA NASTAVITEV
				echo '<p>';
				echo '<label class="lbl_email_setting">'.$lang['srv_user_base_individual_invitaition'];
				if($individual == 0)
					echo ' '.Help::display('srv_user_base_individual_invitaition_note2').' </label>';
				else
					echo ' '.Help::display('srv_user_base_individual_invitaition_note').' </label>'; 
				echo '<label><input type="radio" name="individual_invitation" value="0" id="individual_invitation_0"'.($individual == 0 ? ' checked="checked"' : '').' '.$voting_disabled.' onChange="surveyBaseSettingRadio(\'individual_invitation\',true);"/>'.$lang['no1'].'</label>';
				echo '<label><input type="radio" name="individual_invitation" value="1" id="individual_invitation_1"'.($individual == 1 ? ' checked="checked"' : '').' '.$voting_disabled.' onChange="surveyBaseSettingRadio(\'individual_invitation\',true);"/>'.$lang['yes'].'</label>';				
				echo '</p>';

				// Ce niso indvidualizirana imamo samo nacin posiljanja
				if ($individual == 0) {
					
					// Nacin posiljanja (email, posta, sms...)
					echo '<p>';
					echo '<label class="lbl_email_setting">'.$lang['srv_inv_message_type'].': '.Help::display('srv_inv_sending_type').'</label>';
					echo '<label><input type="radio" name="inv_messages_noEmailing" value="0" id="inv_messages_noEmailing_1"'.($noEmailing == 0 ? ' checked="checked"' : '').' '.$voting_disabled.' onChange="noEmailingToggle(\'0\');"/>'.$lang['srv_inv_message_noemailing_0'].'</label>';
					echo '<label><input type="radio" name="inv_messages_noEmailing" value="1" id="inv_messages_noEmailing_1"'.($noEmailing == 1 ? ' checked="checked"' : '').' '.$voting_disabled.' onChange="noEmailingToggle(\'1\');"/>'.$lang['srv_inv_message_noemailing_1'].'</label>';
					echo '</p>';
					
					// Nacin dokumentiranja (posta, sms, drugo)
					if($noEmailing == 1){
						$noEmailingType = SurveySession::get('inv_noEmailing_type');
						echo '<p>';
						echo '<label class="lbl_email_setting">'.$lang['srv_inv_message_type_external'].':</label>';
						echo '<label><input type="radio" name="noMailType" value="0" id="noMailType1"'.($noEmailingType == 0 ? ' checked="checked"' : '').' '.$voting_disabled.' onClick="noEmailingType(\'0\');" />'.$lang['srv_inv_message_noemailing_type1'].'</label>';
						echo '<label><input type="radio" name="noMailType" value="1" id="noMailType2"'.($noEmailingType == 1 ? ' checked="checked"' : '').' '.$voting_disabled.' onClick="noEmailingType(\'1\');" />'.$lang['srv_inv_message_noemailing_type2'].'</label>';
						echo '<label><input type="radio" name="noMailType" value="2" id="noMailType3"'.($noEmailingType == 2 ? ' checked="checked"' : '').' '.$voting_disabled.' onClick="noEmailingType(\'2\');" />'.$lang['srv_inv_message_noemailing_type3'].'</label>';
						echo '</p>';
					}
				} 
				# Normalna vabila z unikatinim URL
				else {
					
					// Nacin posiljanja (email, posta, sms...)
					echo '<p>';
					echo '<label class="lbl_email_setting">'.$lang['srv_inv_message_type'].': '.Help::display('srv_inv_sending_type').'</label>';
					echo '<label><input type="radio" name="inv_messages_noEmailing" value="0" id="inv_messages_noEmailing_1"'.($noEmailing == 0 ? ' checked="checked"' : '').' '.$voting_disabled.' onChange="noEmailingToggle(\'0\');"/>'.$lang['srv_inv_message_noemailing_0'].'</label>';
					echo '<label><input type="radio" name="inv_messages_noEmailing" value="1" id="inv_messages_noEmailing_1"'.($noEmailing == 1 ? ' checked="checked"' : '').' '.$voting_disabled.' onChange="noEmailingToggle(\'1\');"/>'.$lang['srv_inv_message_noemailing_1'].'</label>';
					echo '</p>';
					
					// Nacin dokumentiranja (posta, sms, drugo)
					if($noEmailing == 1){
						$noEmailingType = SurveySession::get('inv_noEmailing_type');
						echo '<p>';
						echo '<label class="lbl_email_setting">'.$lang['srv_inv_message_type_external'].':</label>';
						echo '<label><input type="radio" name="noMailType" value="0" id="noMailType1"'.($noEmailingType == 0 ? ' checked="checked"' : '').' '.$voting_disabled.' onClick="noEmailingType(\'0\');" />'.$lang['srv_inv_message_noemailing_type1'].'</label>';
						echo '<label><input type="radio" name="noMailType" value="1" id="noMailType2"'.($noEmailingType == 1 ? ' checked="checked"' : '').' '.$voting_disabled.' onClick="noEmailingType(\'1\');" />'.$lang['srv_inv_message_noemailing_type2'].'</label>';
						echo '<label><input type="radio" name="noMailType" value="2" id="noMailType3"'.($noEmailingType == 2 ? ' checked="checked"' : '').' '.$voting_disabled.' onClick="noEmailingType(\'2\');" />'.$lang['srv_inv_message_noemailing_type3'].'</label>';
						echo '</p>';
					}
					
					// Vnos kode - samo ce je email (drugace itak vedno rocni vnos)
					if($noEmailing != 1){
						echo '<p>';
						echo '<label class="lbl_email_setting">'.$lang['usercode_required1'].':'.Help::display('usercode_required').'</label>';
						echo '<label><input type="radio" name="usercode_required" value="0" id="usercode_required_0"'.($row['usercode_required'] == 0 ? ' checked="checked"' : '').' '.$voting_disabled.' onChange="surveyBaseSettingRadio(\'usercode_required\',true);"/>'.$lang['usercode_required2'].'</label>';
						echo '<label><input type="radio" name="usercode_required" value="1" id="usercode_required_1"'.($row['usercode_required'] == 1 ? ' checked="checked"' : '').' '.$voting_disabled.' onChange="surveyBaseSettingRadio(\'usercode_required\',true);"/>'.$lang['usercode_required3'].'</label>';
						echo '</p>';
					}
					
					if ($row['usercode_required'] != 0) {
						echo '<p>';
						if($noEmailing == 1)
							echo '<label class="lbl_email_setting">'.$lang['usercode_text2'].': </label><br />';
						else
							echo '<label class="lbl_email_setting">'.$lang['usercode_text'].': </label><br />';			
						$nagovorText = ($row['usercode_text'] && $row['usercode_text'] != null && $row['usercode_text'] != "") ? $row['usercode_text'] : $lang['srv_basecode'];
						echo '<textarea style="width:430px; margin-left:15px; margin-top:5px;" name="usercode_text" onblur="surveyBaseSettingText(\'usercode_text\',false);return false;">'.$nagovorText.'</textarea>';
						echo '</p>';
					}
					
					// Dostop brez kode
					
					
					echo '<p><label for="usercode_skip_0" class="lbl_email_setting">';
					echo $lang['srv_user_base_access_check'].' '.Help::display('srv_inv_no_code');
					echo '<input type="checkbox" name="usercode_skip_checkbox" value="0" id="usercode_skip_0"'.($row['usercode_skip'] != 0 ? ' checked="checked"' : '').' '.$voting_disabled.' onChange="surveyBaseSettingRadio(\'usercode_skip\',true);" />';
					echo '</label></p>';
					if($row['usercode_skip'] > 0){
						echo '<div style="float: left; margin: -10px 0 0 15px;">';
						echo '<label class="lbl_email_setting">'.$lang['srv_user_base_access'].Help::display('usercode_skip').' </label>';
						echo '<label><input type="radio" name="usercode_skip" value="1" id="usercode_skip_1"'.($row['usercode_skip'] == 1 ? ' checked="checked"' : '').' onChange="surveyBaseSettingRadio(\'usercode_skip\',true);"/>'.$lang['srv_vsi'].'</label>';
						echo '<label><input type="radio" name="usercode_skip" value="2" id="usercode_skip_2"'.($row['usercode_skip'] == 2 ? ' checked="checked"' : '').' onChange="surveyBaseSettingRadio(\'usercode_skip\',true);"/>'.$lang['srv_setting_onlyAuthor'].'</label>';
						echo '</div>';
					}			

					/*echo '<p>';
					echo '<label class="lbl_email_setting">'.$lang['srv_user_base_access'].Help::display('usercode_skip').' </label>';
					echo '<label><input type="radio" name="usercode_skip" value="0" id="usercode_skip_0"'.($row['usercode_skip'] == 0 ? ' checked="checked"' : '').' onChange="surveyBaseSettingRadio(\'usercode_skip\',true);"/>'.$lang['no1'].'</label>';
					echo '<label><input type="radio" name="usercode_skip" value="1" id="usercode_skip_1"'.($row['usercode_skip'] == 1 ? ' checked="checked"' : '').' onChange="surveyBaseSettingRadio(\'usercode_skip\',true);"/>'.$lang['yes'].'</label>';
					echo '<label><input type="radio" name="usercode_skip" value="2" id="usercode_skip_2"'.($row['usercode_skip'] == 2 ? ' checked="checked"' : '').' onChange="surveyBaseSettingRadio(\'usercode_skip\',true);"/>'.$lang['srv_setting_onlyAuthor'].'</label>';
					echo '<br/><i class="small">* '.$lang['srv_user_base_access_alert_'.$row['usercode_skip'].''].'</i>';
					echo '</p>';*/					
				}
				
				echo '<br />';
				
				// Gumb shrani - samo provizorično
				echo '<div class="buttonwrapper"><a class="ovalbutton floatRight" href="#" title="'.$lang['save'].'">'.$lang['save'].'</a></div>';
				echo '<div class="buttonwrapper"><a class="ovalbutton floatRight spaceRight" href="'.$site_url.'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=add_recipients_view">'.$lang['srv_adding_email_respondents'].'</a></div>';

				
				echo '</div>';
				
				echo '</div>';				
				echo '</fieldset>';	
				echo '</td>';
				
				
				// desna stran - nastavitve streznika - samo ce imamo posiljanje preko emaila
				if($noEmailing != 1){
					
					// Gorenje tega nima
					if (!Common::checkModule('gorenje')){

						echo '<td style="padding-right:10px;vertical-align: top;">';
						echo '<fieldset class="inv_fieldset"><legend>'.$lang['srv_email_setting_title'].'</legend>';
						echo '<div class="inv_filedset_inline_div">';
						
						echo '<div id="surveyInvitationSettingServer">';
						$this->viewServerSettings();
						echo '</div>';
						
						echo '</div>';				
						echo '</fieldset>';	
						echo '</td>';
					}
				}
		
		
				echo '</tr></table>';
			}
		} 
		# navadni uporabniki, ki nimajo dostopa - text kako lahko pridobijo dostop
		else {
			echo '<fieldset class="inv_fieldset" style="max-width:800px; padding-bottom: 15px;"><legend>'.$lang['srv_invitation_nonActivated_title'].'</legend>';
			echo '<div class="inv_filedset_inline_div">';
			
			echo '<p>';
			echo $lang['srv_invitation_nonActivated_text1'];
			echo '</p>';
			
			echo '<p>';
            if($lang['id'] == '1')
                echo sprintf($lang['srv_invitation_nonActivated_text2'], 'https://www.1ka.si/d/sl/pomoc/prirocniki/posiljanje-email-vabil-pridobitev-dovoljenja?from1ka=1');
            else
                echo sprintf($lang['srv_invitation_nonActivated_text2'], 'https://www.1ka.si/d/en/help/manuals/sending-email-invitations-and-obtaining-authorization?from1ka=1');

			echo '</p>';
			
			echo '<p>';
			echo $lang['srv_invitation_nonActivated_text3'];
			echo '</p>';
			
			// Gumb ZAPROSI ZA DOSTOP DO VABIL
			echo '<span class="buttonwrapper floatLeft spaceRight"><a class="ovalbutton ovalbutton_orange" href="https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/objava/1ka-vabila/?from1ka=1">'.$lang['srv_invitation_nonActivated_button_details'].'</a></span>';
			//echo '<span class="spaceLeft bold" style="line-height:25px;"><a href="https://www.1ka.si/c/804/Email_vabila/?preid=793&from1ka=1">'.$lang['srv_invitation_nonActivated_more'].'</a></span>';
			echo '<br />';

			echo '</div>';
			echo '</fieldset>';
		}			
	}

	function formatNumber ($value, $digit = 0, $sufix = "") {
		if ($value <> 0 && $value != null)
			$result = round($value, $digit);
		else
			$result = "0";
			
		# polovimo decimalna mesta in vejice za tisočice

		$decimal_point = SurveyDataSettingProfiles :: getSetting('decimal_point');
		$thousands = SurveyDataSettingProfiles :: getSetting('thousands');
			
		$result = number_format($result, $digit, $decimal_point, $thousands) . $sufix;

		return $result;
	}

	function showInvitationLists($profile_id=null) {
		global $lang,$global_user_id;
		
		echo '<h2>'.$lang['srv_inv_list_edit_header'].'</h2>';
		
		echo '<table>';
		echo '<tr>';
		echo '<td style="vertical-align:top;min-height:500px; min-width:550px;">';
		echo '<div style="height:25px; width:100%; ">';
		echo '<label><input name="inv_show_list_type" id="inv_show_list_type1" type="radio" value="1" onclick="showInvitationListsNames();" checked="checked" autocomplete="off">'.$lang['srv_inv_list_edit_from_this_survey'].'</label>';
		echo '<label><input name="inv_show_list_type" id="inv_show_list_type2" type="radio" value="0" onclick="showInvitationListsNames();" autocomplete="off">'.$lang['srv_inv_list_edit_from_all_surveys'].'</label>';
		echo '<label><input name="inv_show_list_type" id="inv_show_list_type3" type="radio" value="2" onclick="showInvitationListsNames();" autocomplete="off">'.$lang['srv_inv_list_edit_from_archive'].'</label>';
		echo '<label class="as_link spaceLeft" onclick="inv_upload_list();">'.$lang['srv_inv_list_upload_file'].'</label>';
		echo '</div>';
		echo '<div id="inv_edit_rec_list">';
		$this->showInvitationListsNames($profile_id);
		echo '</div>';
		echo '</td><td style="padding-left:20px; vertical-align:top;">';
		echo '<div id="inv_selected_rec_list">';
		$doEdit = $_SESSION['inv_edit_rec_profile'][$this->sid] == 'true' ? true : false;
		if ($doEdit) {
			$this->showEditRecList($profile_id);
		} else {
			$this->showNoEditRecList($profile_id);
		}
		echo '</div>';
		echo '</td></tr>';
		echo '</table>';
	}

	function showInvitationListsNames($profile_id=null) {
		global $lang,$global_user_id;

		$onlyThisSurvey = isset($_POST['onlyThisSurvey']) ? (int)$_POST['onlyThisSurvey'] : 1;
		if ($profile_id == null) {
			$pids = explode(',',$_POST['pids']);
		} else {
			$pids = explode(',',$profile_id);
		}

		if ($onlyThisSurvey == 0) {
			#id-ji profilov do katerih lahko dostopamo
			$accPid = '';
			$accStr = "SELECT DISTINCT pid FROM srv_invitations_recipients_profiles_access where uid = '$global_user_id'";
			$accQry = sisplet_query($accStr);
			while (list($pid) = mysqli_fetch_row($accQry)) {
				$accPid .= $prefix ."'".$pid."'";
				$prefix = ',';
			}

			# polovimo še ostale porfile
			$sql_string = "SELECT rp.*,DATE_FORMAT(rp.insert_time,'%d.%m.%Y, %T') AS ds, u.name as firstname, u.surname, u.email FROM srv_invitations_recipients_profiles AS rp LEFT JOIN users AS u ON rp.uid = u.id WHERE rp.uid in('".$global_user_id."')".($accPid != '' ? ' OR pid IN ('.$accPid.')':'');

			$sql_query = sisplet_query($sql_string);
		} else if ($onlyThisSurvey == 2) {
			$sql_string = "SELECT sia.*,sia.id as pid, sia.naslov AS name, DATE_FORMAT(sia.date_send,'%d.%m.%Y, %T') AS ds,  u.name as firstname, u.surname, u.email FROM srv_invitations_archive AS sia LEFT JOIN users AS u ON sia.uid = u.id WHERE ank_id = '".$this->sid."'  ORDER BY sia.date_send DESC;";
			$sql_query = sisplet_query($sql_string);
		} else {
			# 1
			$sql_string = "SELECT rp.*, DATE_FORMAT(rp.insert_time,'%d.%m.%Y, %T') AS ds , u.name as firstname, u.surname, u.email FROM srv_invitations_recipients_profiles AS rp LEFT JOIN users AS u ON rp.uid = u.id WHERE from_survey = '$this->sid'";
			$sql_query = sisplet_query($sql_string);
		}

		if (mysqli_num_rows($sql_query)>0) {
			echo '<table class="inv_edit_rec_list">';
			echo '<tr>';
			echo '<th>&nbsp;</th>';
			if ($onlyThisSurvey != 2) {
				echo '<th>&nbsp;</th>';
				echo '<th>&nbsp;</th>';
			}
			echo '<th>';
			echo $lang['srv_inv_list_table_name'];
			echo '</th>';
			echo '<th>';
			echo $lang['srv_inv_list_table_cnt_receive'];
			echo '</th>';
			echo '<th>';
			echo $lang['srv_inv_list_table_comment'];
			echo '</th>';
			echo '<th>';
			echo $lang['srv_inv_list_table_date_create'];
			echo '</th>';
			echo '<th>';
			echo $lang['srv_inv_list_table_author'];
			echo '</th>';
			echo '</tr>';
			while ($sql_row = mysqli_fetch_assoc($sql_query)) {
				echo '<tr>';
				echo '<td>';
				echo '<input type="checkbox" class="test_checkAll" id="inv_list_chck_'.$sql_row['pid'].'" onclick="changeInvRecListCheckbox();" value="'.$sql_row['pid'].'" '.(in_array($sql_row['pid'],$pids) ? ' checked="checked"' : '').'autocomplete="off">';
				echo '</td>';
				if ($onlyThisSurvey != 2) {
					echo '<td>';
					echo '<span class="faicon delete_circle icon-orange_link" onclick="deleteRecipientsList_confirm(\''.$sql_row['pid'].'\'); return false;" title="'.$lang['srv_inv_list_profiles_delete'].'"></span>';
					echo '</td>';
					echo '<td>';
					echo '<span class="faicon quick_edit user smaller icon-as_link" onclick="inv_list_edit(\''.$sql_row['pid'].'\'); return false;" title="'.$lang['srv_inv_list_profiles_edit_access'].'"></span>';
					echo '</td>';
				}
				echo '<td>';
				echo '<label for="inv_list_chck_'.$sql_row['pid'].'">';
				echo $sql_row['name'];
				echo '</label>';
				echo '</td>';
				echo '<td>';
				if (isset($sql_row['respondents'])) {
					$_recipients = str_replace("\n\r", "\n", $sql_row['respondents']);
					$_recipients = explode("\n",$_recipients);
					echo count($_recipients);
				} else if (isset($sql_row['cnt_succsess']) || isset($sql_row['cnt_error'])) {
					echo (int)$sql_row['cnt_succsess']+(int)$sql_row['cnt_error'];
				}
				echo '</td>';
				#echo '<td>';
				#$_fields = explode(",",$sql_row['fields']);
				#$_fields_lang = array();
				#foreach ($_fields as $_field) {
				#	$_fields_lang[] = $lang['srv_inv_field_'.$_field];
				#}
				#echo implode(',',$_fields_lang);
				#echo '</td>';
				echo '<td>';
				echo $sql_row['comment'];
				echo '</td>';
				echo '<td>';
				echo $sql_row['ds'];
				echo '</td>';
				echo '<td title="'.$sql_row['email'].'">';
				echo $sql_row['firstname'];
				echo ' '.$sql_row['surname'];
				echo '</td>';
				echo '</tr>';
			}
			echo '</table>';
            /*
             * Osznačevanje vseh seznamov in brisanje le teh*/
            echo '<div id="inv_bottom_edit">';
            echo '<span class="faicon arrow_up"></span> ';
            echo '<span id="inv_switch_on"><a href="#" onClick="inv_list_selectAll(true)">'.$lang['srv_select_all'].'</a></span>';
            echo '<span id="inv_switch_off" style="display:none;"><a href="#" onClick="inv_list_selectAll(false)">'.$lang['srv_deselect_all'].'</a></span>';
            echo '&nbsp;&nbsp;<a href="#" onclick="inv_recipients_list_action(\'delete\');"><span class="faicon delete_circle icon-orange" title="'.$lang['srv_invitation_recipients_delete_selected'].'"/></span>&nbsp;'.$lang['srv_invitation_recipients_delete_selected'].'</a>';
            //echo '&nbsp;&nbsp;<a href="#" onclick="inv_recipients_form_action(\'export\');"><span class="sprites xls delete" style="height:14px; width:16px;" title="'.$lang['srv_invitation_recipients_export_selected'].'"/></span>&nbsp;'.$lang['srv_invitation_recipients_export_selected'].'</a>';
            echo '</div>';
		} else {
			echo $lang['srv_inv_list_no_lists'];
		}
		return (int)mysqli_num_rows($sql_query);
	}

	function showNoEditRecList($profile_id = null) {
		global $lang;
		$pids=array();
		$onlyThisSurvey = (int)$_POST['onlyThisSurvey'];
		if ($profile_id == null) 
		{
			# preberemo id-je profilov in respondente
			if ($_POST['pids'] != '') 
			{
				$pids = explode(',',$_POST['pids']);
			}
		} else 
		{
			# preberemo id-je profilov in respondente
			if ($profile_id != '') 
			{
				$pids = explode(',',$profile_id);
			}
		}
		
		if (empty($pids)) 
		{
			echo $lang['srv_inv_list_choose_left'].'<br>';
		}
		
		session_start();
		$infoBox = null;
		if (is_array($pids) && count($pids) > 0) 
		{
			echo '<div style="width:100%; height:25px;"><label><input name="inv_show_list_edit" id="inv_show_list_edit" type="checkbox" value="1" onclick="changeInvRecListEdit();" autocomplete="off">'.$lang['srv_inv_list_edit'].'</label></div>';
			$respondents = array();
			$fields = array();
			# info box prikazujemo samo ko imamo izbran 1 seznam
			if ($onlyThisSurvey <= 1) 
			{
				# če imamo normalne sezname

				if (is_array($pids) && count($pids) == 1) {
					$sql_string = "SELECT rp.respondents, fields,rp.name, rp.comment, u.email AS iemail, DATE_FORMAT(rp.insert_time,'%d.%m.%Y, %T') AS fitime, e.email AS eemail, DATE_FORMAT(rp.edit_time,'%d.%m.%Y, %T') AS fetime FROM srv_invitations_recipients_profiles AS rp LEFT JOIN users AS u ON rp.uid = u.id LEFT JOIN users AS e ON rp.uid = e.id  WHERE rp.pid IN(".(implode(',',$pids)).")";
				} else {
					$sql_string = "SELECT rp.respondents, fields FROM srv_invitations_recipients_profiles AS rp WHERE rp.pid IN(".(implode(',',$pids)).")";
				}
				
				$sql_query = sisplet_query($sql_string);
					
				while ($sql_row = mysqli_fetch_assoc($sql_query)) 
				{
					# info box prikazujemo samo ko imamo izbran 1 seznam
					if (is_array($pids) && count($pids) == 1) 
					{
						$infoBox .= '<span class="gray" style="display:inline-block; margin:10px;5px;">';
						$infoBox .= $lang['srv_inv_list_edit_added'];
						$infoBox .= trim($sql_row['iemail']) != '' ? $sql_row['iemail'] : $lang['srv_inv_list_edit_1kasi'];
						$infoBox .= $lang['srv_inv_list_edit_date'].$sql_row['fitime'];
						if ($sql_row['fitime'] != $sql_row['fetime']) {
							$infoBox .= '<br/>'.$lang['srv_inv_list_edit_changed'];
							$infoBox .= trim($sql_row['eemail']) != '' ? $sql_row['eemail'] : $lang['srv_inv_list_edit_1kasi'];
							$infoBox .= $lang['srv_inv_list_edit_date'].$sql_row['fetime'];
						}
		
						$infoBox .= '<br/><label>'.$lang['srv_inv_list_edit_name'].$sql_row['name'].'</label>';
						$infoBox .= '<br/><label>'.$lang['srv_inv_list_edit_comment'].$sql_row['comment'].'</label>';
		
					}
					#$array_profiles[$sql_row['pid']] = array('name' => $sql_row['name']);
					
					$respondents_list = str_replace("\n\r", "\n", $sql_row['respondents']);
					$respondents_list = explode("\n",$respondents_list);
					$respondents = array_merge($respondents,$respondents_list);
		
					$_fields = explode(",",$sql_row['fields']);
					foreach ($_fields as $_field) 
					{
						if (!in_array($_field,$fields)) {
							$fields[] = $_field;
						}
					}
				} # end-while
			} 
			else 
			{
				# imamo arhive
				$fields = array();
				$_recipients = array();
				$sql_string = "SELECT email,firstname,lastname,salutation,phone,custom,relation FROM srv_invitations_recipients AS sir WHERE sir.id IN (SELECT siar.rec_id FROM srv_invitations_archive_recipients siar WHERE siar.arch_id IN (".(implode(',',$pids))."))";
				$sql_query = sisplet_query($sql_string);
				while ($row =  mysqli_fetch_assoc($sql_query) ) {
					$_recipients[] = $row;
					foreach ($row AS $key => $value) {
						if ($value !== null && !in_array($key,$fields)) {
							$fields[] = $key;
						}
					}
				}

				# pripravimo respondente
				foreach ($_recipients AS $_recipient) {
					$recipient = '';
					$prefix='';
					foreach ($fields AS $field) {
						$recipient.=$prefix.$_recipient[$field];
						$prefix=',';
					}
					if ($recipient != '') {
						$respondents[] = $recipient;
					}
				}
			
			}
			
			# pohandlamo polja
			$field_list = array();
			$default_fields = array(
					'inv_field_email' => count($fields) == 0 ? 1 : 0,
					'inv_field_firstname' => 0,
					'inv_field_lastname' => 0,
					'inv_field_password' => 0,
					'inv_field_salutation' => 0,
					'inv_field_phone' => 0,
					'inv_field_custom' => 0,
			);
			
			// Ce imamo modul 360 imamo tudi odnos
			if(SurveyInfo::getInstance()->checkSurveyModule('360_stopinj')){
				$default_fields['inv_field_relation'] = 0;
			}

			# skreiramo nov vrstni red polj
			if (count($fields) > 0) {
				foreach ($fields as $field) {
					$field_list['inv_field_'.$field] = 1;
					if (isset($default_fields['inv_field_'.$field])) {
						unset($default_fields['inv_field_'.$field]);
					}
				}
			}
				
			if (count($default_fields) > 0) 
			{
				foreach ($default_fields as $key =>$field) {
					$field_list[$key] = $field;
					unset($default_fields[$key]);
				}
			}
			$respondents = array_unique($respondents);
			echo '<table class="inv_edit_rec_list">';
			echo '<tr>';
			$fields_cnt = 0;
			foreach ($field_list AS $field => $checked) 
			{
				if ($checked == 1) {
					$fields_cnt++;
					echo '<th title="'.$lang['srv_inv_recipients_'.$field].'">'.$lang['srv_'.$field].'</th>';
				}
			}
			echo '</tr>';
			if (is_array($respondents) && count($respondents) > 0 ) 
			{
				foreach ($respondents AS $respondent_data) {
					$row_cnt = 0;
					echo '<tr>';
					$respondent_data_array = explode('|~|',$respondent_data);
					if (count($respondent_data_array) > 0) {
						foreach ($respondent_data_array AS $tekst) {
							if ($row_cnt < $fields_cnt) {
								echo '<td>'.str_replace ("|~|", ",", $tekst).'</td>';
								$row_cnt++;
							}
						}
					}
					echo '</tr>';
				}
			}
			echo '</table>';
		}
		echo $infoBox;
	}
		
	function showEditRecList($profile_id = null)
	{
		global $lang;
		$pids=array();
		$onlyThisSurvey = (int)$_POST['onlyThisSurvey'];
		if ($profile_id == null) {
			# preberemo id-je profilov in respondente
			if ($_POST['pids'] != '') {
			$pids = explode(',',$_POST['pids']);
		}
		} else {
			# preberemo id-je profilov in respondente
			if ($profile_id != '') {
			$pids = explode(',',$profile_id);
		}
		}
		if (is_array($pids) && count($pids) == 0) {
			echo $lang['srv_inv_list_choose_left'].'<br>';
		}
		session_start();
		if (is_array($pids) && !empty($pids)) 
		{
			echo '<div style="height:25px;width:100%;"><label><input name="inv_show_list_edit" id="inv_show_list_edit" type="checkbox" value="1" onclick="changeInvRecListEdit();" checked="checked" autocomplete="off">'.$lang['srv_inv_list_edit'].'</label></div>';
			$respondents = array();
			$fields = array();
			$infoBox = null;
			if ($onlyThisSurvey <= 1)
			{
				# če imamo normalne sezname
				if (is_array($pids) && count($pids) == 1) {
					$sql_string = "SELECT rp.respondents, fields,rp.name, rp.comment, u.email AS iemail, DATE_FORMAT(rp.insert_time,'%d.%m.%Y, %T') AS fitime, e.email AS eemail, DATE_FORMAT(rp.edit_time,'%d.%m.%Y, %T') AS fetime FROM srv_invitations_recipients_profiles AS rp LEFT JOIN users AS u ON rp.uid = u.id LEFT JOIN users AS e ON rp.uid = e.id  WHERE rp.pid IN(".(implode(',',$pids)).")";
				} else {
					$sql_string = "SELECT rp.respondents, fields FROM srv_invitations_recipients_profiles AS rp WHERE rp.pid IN(".(implode(',',$pids)).")";
				}
				
				$sql_query = sisplet_query($sql_string);

				while ($sql_row = mysqli_fetch_assoc($sql_query))
				{
					# info box prikazujemo samo ko imamo izbran 1 seznam
					if (is_array($pids) && count($pids) == 1) 
					{
						$infoBox = '<span class="gray" style="display:inline-block; margin:10px;5px;">';
						$infoBox .= $lang['srv_inv_list_edit_added'];
						$infoBox .= trim($sql_row['iemail']) != '' ? $sql_row['iemail'] : $lang['srv_inv_list_edit_1kasi'];
						$infoBox .= $lang['srv_inv_list_edit_date'].$sql_row['fitime'];
						if ($sql_row['fitime'] != $sql_row['fetime']) 
						{
							$infoBox .= '<br/>'.$lang['srv_inv_list_edit_changed'];
							$infoBox .= trim($sql_row['eemail']) != '' ? $sql_row['eemail'] : $lang['srv_inv_list_edit_1kasi'];
							$infoBox .= $lang['srv_inv_list_edit_date'].$sql_row['fetime'];
						}
						$infoBox .= '<br/><label>'.$lang['srv_inv_list_edit_name'].'<input type="text" id="rec_profile_name" value="'.$sql_row['name'].'" tabindex="1" ></label>';
						$infoBox .= '<br/><label>'.$lang['srv_inv_list_edit_comment'].'<input type="text" id="rec_profile_comment" value="'.$sql_row['comment'].'" tabindex="2"></label>';
					}
					#$array_profiles[$sql_row['pid']] = array('name' => $sql_row['name']);
						
					$respondents_list = str_replace("\n\r", "\n", $sql_row['respondents']);
					$respondents_list = explode("\n",$respondents_list);
					$respondents = array_merge($respondents,$respondents_list);

					$_fields = explode(",",$sql_row['fields']);
					foreach ($_fields as $_field)
					{
						if (!in_array($_field,$fields))
						{
							$fields[] = $_field;
						}
					}
				}
			}
			else
			{
				# imamo arhive
				$fields = array();
				$_recipients = array();
				$sql_string = "SELECT email,firstname,lastname,salutation,phone,custom,relation FROM srv_invitations_recipients AS sir WHERE sir.id IN (SELECT siar.rec_id FROM srv_invitations_archive_recipients siar WHERE siar.arch_id IN (".(implode(',',$pids))."))";
				$sql_query = sisplet_query($sql_string);
				while ($row =  mysqli_fetch_assoc($sql_query) ) {
					$_recipients[] = $row;
					foreach ($row AS $key => $value) {
						if ($value !== null && !in_array($key,$fields)) {
							$fields[] = $key;
						}
					}
				}

				# pripravimo respondente
				foreach ($_recipients AS $_recipient) {
					$recipient = '';
					$prefix='';
					foreach ($fields AS $field) {
						$recipient.=$prefix.$_recipient[$field];
						$prefix=',';
					}
					if ($recipient != '') {
						$respondents[] = $recipient;
					}
				}

			}
			# pohandlamo polja
			$field_list = array();
			$default_fields = array(
					'inv_field_email' => count($fields) == 0 ? 1 : 0,
					'inv_field_firstname' => 0,
					'inv_field_lastname' => 0,
					'inv_field_password' => 0,
					'inv_field_salutation' => 0,
					'inv_field_phone' => 0,
					'inv_field_custom' => 0,
			);
			
			// Ce imamo modul 360 imamo tudi odnos
			if(SurveyInfo::getInstance()->checkSurveyModule('360_stopinj')){
				$default_fields['inv_field_relation'] = 0;
			}

			# skreiramo nov vrstni red polj
			if (count($fields) > 0) {
				foreach ($fields as $field) {
					$field_list['inv_field_'.$field] = 1;
					if (isset($default_fields['inv_field_'.$field])) {
						unset($default_fields['inv_field_'.$field]);
					}
				}
			}
				
			if (count($default_fields) > 0) {
				foreach ($default_fields as $key =>$field) {
					$field_list[$key] = $field;
					unset($default_fields[$key]);
				}
			}
			$respondents = array_unique($respondents);
			echo '<div id="inv_field_container">';
			echo '<ul class="connectedSortable">';
			$field_lang = array();
			if (count($field_list ) > 0) {
				foreach ($field_list AS $field => $checked)
				{
					# ali je polje izbrano ( če imamo personalizirano e-vabilo, moramo nujno imeti polje  email
					$is_selected = ($checked == 1 ) ? true : false;
						
					# če je polje obkljukano
					$css =  $is_selected ? ' class="inv_field_enabled"' : '';
						
					# ali labela sproži klik checkboxa
					$label_for = ' for="'.$field.'_chk"';
					echo '<li id="'.$field.'"'.$css.'>';
					echo '<input id="'.$field.'_chk" type="checkbox" class="inv_checkbox' . $hidden_checkbox . '"'.($is_selected == true ? ' checked="checked"' : '').'>';
					echo '<label'.$label_for.'>'.$lang['srv_'.$field].'</label>';
					echo '</li>';
				}
				if ($is_selected == 1) {
					$field_lang[] = $lang['srv_'.$field];
				}
			}
			echo '</ul>';
			echo '</div>';
			echo '<br class="clr" />';
			echo '<script type="text/javascript">';
			echo "$('ul.connectedSortable').sortable({update : function () { refreshFieldsList(); }, forcePlaceholderSize: 'true',tolerance: 'pointer',placeholder: 'inv_field_placeholder',});";
			echo '</script>';
			echo '<div>';
			echo '<textarea id="inv_recipients_list" name="inv_recipients_list">';
			if (is_array($respondents) && count($respondents) > 0 ) {
				echo str_replace ("|~|", ",", implode("\n",$respondents));
			}
			echo '</textarea>';
			echo '</div>';
			
			echo $infoBox;
			echo '<br class="clr" />';
			echo '<br class="clr" />';
			if (count($pids) <= 1 ) {
				echo '<span class="buttonwrapper floatLeft spaceLeft"><a class="ovalbutton ovalbutton_gray"  href="#" onclick="inv_list_save_old(\''.implode('',$pids).'\'); return false;"><span>'.$lang['srv_inv_list_save_old'].'</span></a></span>';
			}
			echo '<span class="buttonwrapper floatLeft spaceLeft"><a class="ovalbutton ovalbutton_orange"  href="#" onclick="inv_list_get_name(\'true\'); return false;"><span>'.$lang['srv_inv_list_save_new'].'</span></a></span>';
			echo '<br class="clr" />';

		}

	}

	function invListEdit() {
		global $lang,$site_url, $global_user_id;

		# polovimo podatke profila
		$sql_string = "SELECT pid, name, comment, uid FROM srv_invitations_recipients_profiles WHERE pid='".$_POST['pid']."'";
		$sql_query = sisplet_query($sql_string);
		list($pid, $name, $comment, $uid) = mysqli_fetch_row($sql_query);
		echo '<div id="inv_recipients_profile_name" class="access">';
		echo '<form id="inv_list_edit_form" name="inv_list_edit_form" autocomplete="off">';
		/*
		 echo '<span id="new_recipients_list_span" >';
		echo '<label>'.$lang['srv_inv_recipient_list_name'];
		echo '<input type="text" id="rec_profile_name" name="rec_profile_name" value="'.$name.'" tabindex="1" autofocus="autofocus">';
		echo '</label>';
		echo '</span>';

		echo '<br/><br/>';
		echo $lang['srv_inv_recipient_list_comment'];

		echo '<textarea id="rec_profile_comment" name="rec_profile_comment" tabindex="3" rows="5" >'.$comment.'</textarea>';
		echo '<br/>';
		*/
		$this->displayListAccess($pid);
		# skrita polja za respondente in polja
		echo '<input id="profile_id" name="profile_id" type="hidden" value="'.($_POST['pid']).'" >';
		echo '<br class="clr" /><br/>';
		echo '<span class="buttonwrapper floatRight" title="'.$lang['save'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inv_list_edit_save(); return false;"><span>'.$lang['save'].'</span></a></span>';
		echo '<span class="buttonwrapper floatRight spaceRight"  title="'.$lang['srv_cancel'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#fade\').fadeOut(\'slow\');$(\'#fullscreen\').fadeOut(\'slow\').html(\'\');return false;" ><span>'.$lang['srv_cancel'].'</span></a></span>';
		echo '<br class="clr" />';
		echo '</form>';
		echo '</div>'; # id="inv_view_arch_recipients"
		/*	echo '<script type="text/javascript">';
		 echo "$('#rec_profile_name').focus();";
		echo '</script>';
		*/
	}

	function listGetName() {
		global $lang,$site_url, $global_user_id;

		$saveNew = $_POST['saveNew'] == 'true' ? true : false;

		$array_profiles = array();
		#ne vem če je fino da lahko dodaja kar na vse sezname
		session_start();
			
		# polovimo še ostale porfile
		$sql_string = "SELECT pid, name,comment FROM srv_invitations_recipients_profiles WHERE uid in('".$global_user_id."')";
		$sql_query = sisplet_query($sql_string);
		while ($sql_row = mysqli_fetch_assoc($sql_query)) {
			$array_profiles[$sql_row['pid']] = array('name' => $sql_row['name'], 'comment'=>$sql_row['comment']);
		}

		echo '<div id="inv_recipients_profile_name">';
		if ($saveNew == true) {
			echo '<span id="new_recipients_list_span" >';
			echo '<label>'.$lang['srv_inv_recipient_list_name'];
			# zaporedno številčimo ime seznama1,2.... če slučajno ime že obstaja
			$new_name = $lang['srv_inv_recipient_list_new'];
			$names = array();
			$s = "SELECT name FROM srv_invitations_recipients_profiles WHERE name LIKE '%".$new_name."%' AND uid='$global_user_id'";
			$q = sisplet_query($s);
			while ($r = mysqli_fetch_assoc($q)) {
				$names[] = $r['name'];
			}
			if (count($names) > 0) {
				$cnt = 1;
				while (in_array($lang['srv_inv_recipient_list_new'].$cnt, $names)) {
					$cnt++;
				}
				$new_name = $lang['srv_inv_recipient_list_new'].$cnt;
			}
				
			echo '<input type="text" id="rec_profile_name" value="'.$new_name.'" tabindex="1" autofocus="autofocus">';
			echo '</label>';
			echo '</span>';
		} else {
			echo '<span id="new_recipients_list_span" >';
			echo '<label>'.$lang['srv_inv_recipient_list_name'];
			echo '<input type="text" id="rec_profile_name" value="'.$array_profiles[$_POST['pid']]['name'].'" tabindex="1" autofocus="autofocus">';
			echo '</label>';
			echo '</span>';
		}

		echo '<br/><br/>';
		echo $lang['srv_inv_recipient_list_comment'];

		echo '<textarea id="rec_profile_comment" tabindex="3" rows="2" >'.$array_profiles[$_POST['pid']]['comment'].'</textarea>';
		# skrita polja za respondente in polja
		$_fields = str_replace('inv_field_','',implode(',',$_POST['fields']));
		echo '<input id="inv_prof_field_list" type="hidden" value="'.$_fields.'" >';
		echo '<input id="inv_prof_recipients_list" type="hidden" value="'.$this->getCleanString($_POST['recipients_list']).'" >';
		echo '<input id="saveNew" type="hidden" value="'.($saveNew == true ? 'true' : 'false').'" >';
		echo '<input id="profile_id" type="hidden" value="'.($_POST['pid']).'" >';
		echo '<br class="clr" /><br/>';
		echo '<span class="buttonwrapper floatLeft spaceRight"  title="'.$lang['srv_cancel'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#fade\').fadeOut(\'slow\');$(\'#fullscreen\').fadeOut(\'slow\').html(\'\');return false;" ><span>'.$lang['srv_cancel'].'</span></a></span>';
		echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['save'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inv_list_save(); return false;"><span>'.$lang['save'].'</span></a></span>';
		echo '<br class="clr" />';
		echo '</div>'; # id="inv_view_arch_recipients"
		echo '<script type="text/javascript">';
		echo "$('#rec_profile_name').focus();";
		echo '</script>';
	}

	function invListSaveOld() {
		global $lang,$site_url, $global_user_id;

		$return = array('error'=>'0');
		$recipients_list = trim($this->getCleanString($_POST['recipients_list']));

		$field_list = (is_array($_POST['field_list']) && count($_POST['field_list']) > 0)  ? implode(',',$_POST['field_list']) : trim($_POST['field_list']);
		$field_list = str_replace('inv_field_','',$field_list);

		$profile_id = explode(',',$_POST['profile_id']);
		$profile_id = $profile_id[0];

		$rec_profile_name = $_POST['rec_profile_name'];
		$rec_profile_comment = $_POST['rec_profile_comment'];

		# dodajamo v obstoječ profil
		$sql_string_update = "UPDATE srv_invitations_recipients_profiles SET name='$rec_profile_name', comment='$rec_profile_comment', respondents = '$recipients_list', fields='$field_list' WHERE uid in('$global_user_id') AND pid = '$profile_id'";
		$sqlQuery = sisplet_query($sql_string_update);
		sisplet_query("COMMIT");
		$this->removeDuplicates($profile_id);

		if (!$sqlQuery) {
			$error = mysqli_error($GLOBALS['connect_db']);
			$return = array('error'=>'1', 'msg'=>$error, 'pid'=>$profile_id());
		} else {
			$return = array('error'=>'0', 'msg'=>'x2', 'pid'=>$profile_id);
		}
		#echo json_encode($return);

		$this->showInvitationLists($profile_id);
	}


	function listSave() {
		global $lang,$site_url, $global_user_id;

		$return = array('error'=>'0');
		$profile_name = (isset($_POST['profile_name']) && trim($_POST['profile_name']) != '') ? trim($_POST['profile_name']) : $lang['srv_invitation_new_templist'];
		$profile_comment = (isset($_POST['profile_comment']) && trim($_POST['profile_comment']) != '') ? trim($_POST['profile_comment']) : '';
		$recipients_list = trim($this->getCleanString($_POST['recipients_list']));
		$field_list = (isset($_POST['field_list']) && trim($_POST['field_list']) != '') ? trim($_POST['field_list']) : 'email';
		$profile_id = explode(',',$_POST['profile_id']);
		$profile_id = $profile_id[0];


		$saveNew = $_POST['saveNew'] == 'true' ? true : false;
		if ($saveNew == true) {
			# shranjujemo v nov profil
			$sql_insert = "INSERT INTO srv_invitations_recipients_profiles (name,uid,fields,respondents,insert_time,comment, from_survey) VALUES ('$profile_name', '$global_user_id', '$field_list', '$recipients_list', NOW(), '$profile_comment', '".$this->sid."' )";
			$sqlQuery = sisplet_query($sql_insert);
				
			$new_pid = mysqli_insert_id($GLOBALS['connect_db']);
			sisplet_query("COMMIT");
				
			#odstranimo podvojene
			$this->removeDuplicates($new_pid);

			if (!$sqlQuery) {
				$error = mysqli_error($GLOBALS['connect_db']);
				$return = array('error'=>'1', 'msg'=>$error, 'pid'=>$new_pid);
			} else {
				$return = array('error'=>'0', 'msg'=>'x1', 'pid'=>$new_pid);
			}
			sisplet_query("COMMIT");
		} else {
			# dodajamo v obstoječ profil
			$sql_string_update = "UPDATE srv_invitations_recipients_profiles SET name='$profile_name', respondents = '$recipients_list', comment='$profile_comment' WHERE uid in('$global_user_id') AND pid = '$profile_id'";
			$sqlQuery = sisplet_query($sql_string_update);
			sisplet_query("COMMIT");
				
			#odstranimo podvojene
			$this->removeDuplicates($profile_id);

			if (!$sqlQuery) {
				$error = mysqli_error($GLOBALS['connect_db']);
				$return = array('error'=>'1', 'msg'=>$error, 'pid'=>$profile_id());
			} else {
				$return = array('error'=>'0', 'msg'=>'x2', 'pid'=>$profile_id);
			}
		}
		#echo json_encode($return);
		$this->showInvitationLists();
	}

	function invListEditSave() {
		global $lang,$site_url, $global_user_id;
		/*
		 * rec_profile_name] => Seznam1 [rec_profile_comment] => Komentar [uid] => Array ( [0] => 1045 [1] => 1049 [2] => 1046 ) [profile_id] => 2 [anketa] => 94 )
		*/
		$return = array('error'=>'0');
		$profile_name = (isset($_POST['rec_profile_name']) && trim($_POST['rec_profile_name']) != '') ? trim($_POST['rec_profile_name']) : $lang['srv_invitation_new_templist'];
		$profile_comment = (isset($_POST['rec_profile_comment']) && trim($_POST['rec_profile_comment']) != '') ? trim($_POST['rec_profile_comment']) : '';

		$uids = $_POST['uid'];
		$pid = (int)$_POST['profile_id'];
		# pripravimo insert query (id avtorja ne dodajamo v dostope, ker je tako v uid profila)
		$insert_string = array();
		if (count($uids) > 0) {
			foreach ($uids AS $key => $uid) {
				$insert_string[] = "('".$pid."','".$uid."')";
			}
		}
		# pobrišemo stare vrednosti dostopov in jih nastavimo na novo
		$delStr = "DELETE FROM srv_invitations_recipients_profiles_access WHERE pid = '$pid'";
		$delQuery = sisplet_query($delStr);

		if (count($insert_string)) {
			# dodamo nove vrednosti dostopov
			$insStr = "INSERT INTO srv_invitations_recipients_profiles_access VALUES ".implode(',',$insert_string);
			$insQuery = sisplet_query($insStr);
		}
		sisplet_query("COMMIT");



		# dodajamo v obstoječ profil
		#		$sql_string_update = "UPDATE srv_invitations_recipients_profiles SET name='$profile_name', comment='$profile_comment' WHERE pid = '$pid'";
		#		$sqlQuery = sisplet_query($sql_string_update);
		#		sisplet_query("COMMIT");
		//		if (!$sqlQuery) {
		#			$error = mysqli_error($GLOBALS['connect_db']);
		#			$return = array('error'=>'1', 'msg'=>$error, 'pid'=>$pid);
		#		} else {
		#			$return = array('error'=>'0', 'msg'=>'x2', 'pid'=>$pid);
		#		}
		#echo json_encode($return);
		$this->showInvitationLists();
	}


	function deleteRecipientsList()  {
		global $global_user_id;
		$id = (int)$_POST['id'];

		if ($id > 0 ) {
			$sql_string = "DELETE FROM srv_invitations_recipients_profiles WHERE pid='".$id."' AND uid='".$global_user_id."'";
			$sqlQuery = sisplet_query($sql_string);
			sisplet_query("COMMIT");
		}
		$this->showInvitationLists();
	}

    function deleteRecipientsListMulti() {
        $return = array('success'=>'0');
        global $global_user_id;

        $ids = $_POST['ids'];

        if(count($ids) > 0){
            $sql_string = "DELETE FROM srv_invitations_recipients_profiles WHERE uid='".$global_user_id."' AND pid IN(".implode(',',$ids).")";
            $sqlQuery = sisplet_query($sql_string);
            $sqlQuery = sisplet_query("COMMIT");
        }
        if (!$sqlQuery) {
            $errors[] = $lang['srv_inv_recipient_delete_error'];
        } else {
            $return['success'] = 2;
            //$this->showInvitationLists();
        }
    }

	function removeDuplicates($pid) {
		# dodamo tracking
		if ((int)$pid > 0) {
		$sql_string = "SELECT respondents  FROM srv_invitations_recipients_profiles WHERE pid = '$pid'";
		$sql_query = sisplet_query($sql_string);
		$respondents = array();
		list ($new_recipients) = mysqli_fetch_row($sql_query);
		$new_recipients = str_replace("\n\r", "\n", $new_recipients);
		$new_recipients = explode("\n",$new_recipients);
		$new_recipients = implode("\n",array_unique($new_recipients));
			
		$sql_string_update = "UPDATE srv_invitations_recipients_profiles SET respondents='".$new_recipients."' WHERE pid = '$pid'";
		$sqlQuery = sisplet_query($sql_string_update);
		sisplet_query("COMMIT");
	}
	}

	function displayListAccess($pid) {
		global $lang, $global_user_id, $admin_type;

		# polovimo avtorja profila
		$uidQuery = sisplet_query("SELECT uid FROM srv_invitations_recipients_profiles WHERE pid ='$pid'");
		list($uid) = mysqli_fetch_row($uidQuery);

		# polovimo id-je userjev ki imajo dostop do tega profila
		$accessArray = array();
		$accessQuery = sisplet_query("SELECT uid FROM srv_invitations_recipients_profiles_access WHERE pid ='$pid'");
		while (list($uid_) = mysqli_fetch_row($accessQuery)) {
			$accessArray[] = $uid_;
		}

		$sqlQuery = null;
		// tip admina:  0=>admin, 1=>manager, 2=>clan, 3=>user
		switch ( $admin_type ) {
			case 0: // admin vidi vse
				$sqlQuery = sisplet_query("SELECT name, surname, id, email FROM users ORDER BY name ASC");
				break;
			case 1: // manager vidi ljudi pod sabo
				// polovimo vse clane ki spo pod managerjem
				$sqlQuery = sisplet_query("SELECT DISTINCT name, surname, id, email FROM users WHERE id IN (SELECT DISTINCT user FROM srv_dostop_manage WHERE manager='$global_user_id') OR id = '$global_user_id'");

				break;
			case 2:
			case 3:
				// TODO
				// clani in userji lahko vidijo samo tiste ki so jim poslali maile in so se registrirali
				$sqlQuery = sisplet_query("SELECT name FROM users WHERE 1 = 0");
				break;
		}
		echo '<span>'.$lang['srv_inv_list_access'].'</span><br/>';
		echo '<span class="gray small">'.$lang['srv_inv_list_access_legend'].'</span><br/><br/>';
		echo '<span id="invListAccessShow1"><a href="#" onClick="inv_listAccess(\'true\'); return false;">'.$lang['srv_dostop_show_all'].'</a></span>';
		echo '<span id="invListAccessShow2" class="displayNone"><a href="#" onClick="inv_listAccess(\'false\'); return false;">'.$lang['srv_dostop_hide_all'].'</a></span>';
		echo '<div id="invListAccess">';

		while ($row1 = mysqli_fetch_assoc($sqlQuery)) {

			$checked = ( in_array($row1[id],$accessArray) ||$uid == $row1['id']) ? ' checked="checked"' : '';

			$_css_hidden = ($checked != '' ? '' : ' displayNone');
			echo '<div id="div_for_uid_' . $row1['id'] . '" name="listAccess" class="floatLeft listAccess_uid'.$_css_hidden.'">';
			echo '<label nowrap title="' . $row1['email'] . '"'.($uid == $row1['id']?' class="gray"':'').'>';
			echo '<input type="checkbox" name="uid[]" value="' . $row1['id'] . '" id="uid_' . $row1['id'] . '"' . $checked .($uid == $row1['id'] ? ' disabled="disabled"' : ''). ' autocomplete="off"/>';
			echo $row1['name'] . ($uid == $row1['id'] ? ' (' . $lang['author'] . ')' : '') . '</label>';
			echo '</div>';
		}

		echo '</div>';
			
	}

	function changeInvRecListEdit() {
		session_start();

		$_SESSION['inv_edit_rec_profile'][$this->sid] = ($_POST['checked'] == 'true' ? true : false);
			
		session_commit();
	}

	function getUrlLists() {
		global $lang,$site_url;

		$result = array();

		$p = new Prevajanje($this->sid);
		$lang_array = $p->get_all_translation_langs();

		$link = SurveyInfo::getSurveyLink();
		$sqll = sisplet_query("SELECT link FROM srv_nice_links WHERE ank_id = '$this->sid' ORDER BY id ASC");

		$default_checked =false;
		$cnt=0;
		#lepi linki
		while ($rowl = mysqli_fetch_assoc($sqll)) {
			
			$result[$cnt] = array('url'=>$site_url.$rowl['link'], 'name'=>$site_url.$rowl['link'].(count($lang_array) > 0 ? ' - '.$lang['language'].' ' : ''));
			if ($default_checked == false) {
				$result[$cnt]['dc'] = true;
				$default_checked = true;
			}
			$cnt++;
			if (count($lang_array) > 0) {
				#jezikovni podlinki
				foreach ($lang_array AS $lang_id => $lang_name) {
					$result[$cnt] = array('url'=>$site_url.$rowl['link'].'?language='.$lang_id, 'name'=>$site_url.$rowl['link'].'?language='.$lang_id.' - '.$lang_name);
					$cnt++;
				}
			}
		}					
		
		$link1 = $site_url.'a/'.Common::encryptAnketaID($this->sid);
		# normalen link
		$result[$cnt] = array('url'=>$link1, 'name'=>$link1.(count($lang_array) > 0 ? ' - '.$lang['language'] : ''));
		if ($default_checked == false) {
			$result[$cnt]['dc'] = true;
			$default_checked = true;
		}
		$cnt++;
		
		#jezikovni link
		if (count($lang_array) > 0) {
			foreach ($lang_array AS $lang_id => $lang_name) {
				$result[$cnt] = array('url'=>$link1.'?language='.$lang_id, 'name'=>$link1.'?language='.$lang_id.' - '.$lang_name);
				if ($default_checked == false) {
					$result[$cnt]['dc'] = true;
					$default_checked = true;
				}
				$cnt++;
			}
		}
		
		return $result;
	}

	function upload_list() {
		global $lang, $site_path, $site_url;
		
		$fields = array();
		$field_list = array();
		# odvisno od tipa sporočil prikažemo različna polja
		# Personalizirano e-poštno vabilo

		$default_fields = array(
				'inv_field_email' => 1,
				'inv_field_firstname' => 0,
				'inv_field_lastname' => 0,
				'inv_field_password' => 0,
				'inv_field_salutation' => 0,
				'inv_field_phone' => 0,
				'inv_field_custom' => 0,
		);
		
		// Ce imamo modul 360 imamo tudi odnos
		if(SurveyInfo::getInstance()->checkSurveyModule('360_stopinj')){
			$default_fields['inv_field_relation'] = 0;
		}
			
		# pri personaliziranih aporočilih je e-mail obvezno polje
		array_push($fields, 'inv_field_email');			

		# skreiramo nov vrstni red polj
		if (count($fields) > 0) {
			foreach ($fields as $key=>$field) {
				$field_list[$field] = 1;
				if (isset($default_fields[$field])) {
					unset($default_fields[$field]);
				}
			}
		}

		if (count($default_fields) > 0) {
			foreach ($default_fields as $key =>$field) {
				$field_list[$key] = $field;
				unset($default_fields[$key]);
			}
		}


        echo '<div id="inv_upload_list">';
        
        echo '<h2>'.$lang['srv_inv_list_upload_header'].'</h2>';
        
		echo '<span class="inv_note">'.$lang['srv_inv_recipiens_field_note'].':</span>';

		echo '<div id="inv_field_container">';

		echo '<ul class="connectedSortable">';
		$field_lang = array();
		if (count($field_list ) > 0) {
			foreach ($field_list AS $field => $checked) {
				# ali je polje izbrano ( če imamo personalizirano e-vabilo, moramo nujno imeti polje  email
				#				$is_selected = ($checked == 1 || $field == 'inv_field_email' ) ? true : false;
				$is_selected = ($checked == 1 ) ? true : false;

				# če je polje obkljukano
				$css =  $is_selected ? ' class="inv_field_enabled"' : '';

				# ali prikazujemo checkbox
				#				$hidden_checkbox = $field == 'inv_field_email' ? ' hidden' : '';

				# ali labela sproži klik checkboxa
				#				$label_for = $field != 'inv_field_email' ? ' for="'.$field.'_chk"' : '';
				$label_for = ' for="'.$field.'_chk"';
					
				echo '<li id="'.$field.'"'.$css.'>';
				echo '<input id="'.$field.'_chk" type="checkbox" class="inv_checkbox' . $hidden_checkbox . '"'.($is_selected == true ? ' checked="checked"' : '').'>';
				echo '<label'.$label_for.'>'.$lang['srv_'.$field].'</label>';
				echo '</li>';
				if ($is_selected == 1) {
					$field_lang[] = $lang['srv_'.$field];
				}
			}
		}
		echo '</ul>';
		echo '</div>';
		echo '<br class="clr" />';
		echo '<script type="text/javascript">';
		echo "$('ul.connectedSortable').sortable({update : function () { refreshFieldsList(); }, forcePlaceholderSize: 'true',tolerance: 'pointer',placeholder: 'inv_field_placeholder',});";
		echo '</script>';

		echo '<form id="inv_recipients_upload_form" name="resp_uploader" method="post" enctype="multipart/form-data" action="'.$site_url.'admin/survey/index.php?anketa='.$this->sid.'&a='.A_INVITATIONS.'&m=send_upload_list" autocomplete="off">';
		echo '<input type="hidden" name="fields" id="inv_recipients_upoad_fields" value="'.implode(',',$fields).'" />';
		echo '<input type="hidden" name="posted" value="1" />';
		echo '<span class="inv_note">'.$lang['srv_inv_recipiens_file_note_1'].'</span>';

		echo '<br class="clr" />'.$lang['srv_inv_recipiens_fields'].' <span id="inv_field_list" class="inv_type_1">';
		echo implode(',',$field_lang);
		echo '</span>';
		echo '<br class="clr" />';
		echo $lang['srv_mailing_upload_list'];
		echo '<input type="file" name="invListFile" id="invListFile" size="42" >';
		if (count($errors) > 0) {
			echo '<br class="clr" />';
			echo '<span class="inv_error_note">';
			foreach($errors as $error) {
				echo '* '.$error.'<br />';
			}
			echo '</span>';
		}
		echo '<br/><br/><label>'.$lang['srv_inv_recipient_import_file_delimiter'].'</label> <input type="radio" name="recipientsDelimiter" id="recipientsDelimiter1" value="," checked><label for="recipientsDelimiter1">'.$lang['srv_inv_recipient_delimiter_comma'].' (,)</label>';
		echo ' <input type="radio" name="recipientsDelimiter" id="recipientsDelimiter2" value=";"><label for="recipientsDelimiter2">'.$lang['srv_inv_recipient_delimiter_semicolon'].' (;)</label>';
        echo '</form>';
        
		echo '<span class="inv_sample" >';
		echo $lang['srv_inv_recipiens_sample'].'&nbsp;</span><span class="inv_sample">';
		echo $lang['srv_inv_recipiens_sample1'];
		echo '</span>';

        echo '<br class="clr" />';

        echo '<div class="buttons_holder">';
		echo '<span class="buttonwrapper floatRight spaceLeft" ><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#inv_upload_list, #fullscreen, #fade\').hide();return false;"><span>'.$lang['srv_cancel'].'</span></a></span>';
		echo '<span class="buttonwrapper floatRight spaceLeft" ><a class="ovalbutton ovalbutton_orange" href="#" onclick="inv_upload_list_check(); return false;"><span>'.$lang['srv_inv_btn_add_recipients_add'].'</span></a></span>';
        echo '</div>';

        echo '</div>';
	}

	function send_upload_list() {
		global $lang, $global_user_id;
		$errors = array();
		$allowedExtensions = array("txt","csv","dat");
		$_fields = trim($_POST['fields']);
		if ($_fields != null && $_fields != '') {
			$fields = explode(',',$_fields);
		} else {
			$fields = array();
		}

		$file_name = $_FILES["invListFile"]["name"];
		$file_type = $_FILES["invListFile"]["type"];
		$file_size = $_FILES["invListFile"]["size"] > 0 ? $_FILES["invListFile"]["size"] / 1024 : 0;
		$file_tmp = $_FILES["invListFile"]["tmp_name"];

		$okFileType = ( $file_type == 'text/plain' || $file_type == 'text/csv' || $file_type == 'application/vnd.ms-excel' );
		$okFileEnd = (	pathinfo($file_name, PATHINFO_EXTENSION) != 'txt'
				|| pathinfo($file_name, PATHINFO_EXTENSION) != 'csv'
				|| pathinfo($file_name, PATHINFO_EXTENSION) != 'dat');
		# preverimo ali smo uploadali datoteko in če smo izbrali katero polje
		if ($_POST['posted'] == '1' && count($fields) == 0) {
			$errors[] = $lang['srv_inv_recipiens_upload_error_no_fields'];
		}
		#preverimo ime datoteke
		if ( trim($file_name) == '' || $file_name == null ) {
			$errors[] = $lang['srv_respondents_invalid_file'];

			# preverimo tip:
		} else if ( $okFileType == false ) {
			$errors[] = $lang['srv_respondents_invalid_file_type'];

			# prevermio še končnico (.txt)
		} else if ($okFileEnd == false) {
			$errors[] = $lang['srv_respondents_invalid_file_type'];
		}

		# preverimo velikost
		else if ( (float)$file_size == 0 ) {
			$errors[] = $lang['srv_respondents_invalid_file_size'];
		}

		# če so napake jih prikažemo če ne obdelamo datoteko
		if (count($errors) > 0) {

			echo '<br class="clr" />';
			echo '<span class="inv_message_errors">'.$lang['srv_inv_recipiens_upload_error'].'</span>';
			echo '<br class="clr" />';
			echo '<br class="clr" />';
			echo '<span class="inv_error_note">';
			foreach($errors as $error) {
				echo '* '.$error.'<br />';
			}
			echo '</span>';
			#$this->addRecipientsView($fields, $invalid_recipiens_array);
		} else {

			$fh = @fopen($file_tmp, "rb");
			if ($fh) {
				$_recipients = fread($fh, filesize($file_tmp));
				fclose($fh);
			}
			$_recipients = str_replace("\n\r", "\n", $_recipients);
			$recipients_list = explode("\n",$_recipients);
			$num_recipients_list = count($recipients_list);

			# pri prejemnikih odrežemo "polja", katerih nismo navedli
			$fields_cont = (int)count($fields);
			$fields = str_replace('inv_field_','',$fields);

			$clean_recipients = array();
			if (count($recipients_list) > 0) {
                            
                            if (isset ($_POST['recipientsDelimiter'])) {
                                $delimiter = $_POST['recipientsDelimiter'];
                            }
                            else {
                                $delimiter = "|~|";
                            }
				foreach ($recipients_list AS $recipient) {
					#poiščemo n-ti delimiter
					$clean_recipients[] = trim(substr($recipient, 0, $this->strpos_offset($delimiter, $recipient, $fields_cont)));
				}
			}
			$clean_fields = trim(implode(',', $fields));
			$clean_recipients = trim(implode("\n", $clean_recipients));
			if ($fields!=null && $fields != '' && $clean_recipients != null && $clean_recipients != '') {
				$sql_insert = "INSERT INTO srv_invitations_recipients_profiles".
						" (name,uid,fields,respondents,insert_time,comment, from_survey) ".
						" VALUES ('Uploaded list', '$global_user_id', '$clean_fields', '$clean_recipients', NOW(), 'Uploaded list', '".$this->sid."' )";
				$sqlQuery = sisplet_query($sql_insert);

			}
				
		}
		$this->showInvitationLists();

	}
	/*
	function strpos_index($haystack = '',$needle = '',$offset = 0,$limit = 99,$return = null)
	{
		$length = strlen($needle);
		$occurances = array();
		while((($count = count($occurances)) < $limit) && (false !== ($offset = strpos($haystack,$needle,$offset))))
		{
		$occurances[$count]['length'] = $length;
		$occurances[$count]['start'] = $offset;
		$occurances[$count]['end'] = $offset = $offset + $length;
		}
		return $return === null ? $occurances : $occurances[$return];
	}
	*/
	/**
	 * Find position of Nth $occurrence of $needle in $haystack
	 * Starts from the beginning of the string
	 **/
	function strpos_offset($needle, $haystack, $occurrence) {
		// explode the haystack
		$arr = explode($needle, $haystack);

		// check the needle is not out of bounds
		switch( $occurrence ) {
			case $occurrence == 0:
				return false;
			case $occurrence > max(array_keys($arr)):
				return strlen($haystack);
			default:
				$index = (int)strlen(implode($needle, array_slice($arr, 0, $occurrence)));
				return $index;
		}
	}
	
	function changePaginationLimit() {
		session_start();
		if (isset($_POST['limit']) && (int)$_POST['limit'] > 0) {
			$_SESSION['rec_on_send_page'] = (int)$_POST['limit'];
			$this->rec_send_page_limit = (int)$_POST['limit'];
		}
		session_commit();
		$this->displaySendPagination((int)$_POST['cnt']);
	}
	
	function displayMessagePreview() {
		global $lang;
		
		$sql_string_m = "SELECT id, naslov, subject_text, body_text, reply_to, isdefault, comment, url FROM srv_invitations_messages WHERE ank_id = '$this->sid' AND isdefault='1'";
		
		$sql_query_m = sisplet_query($sql_string_m);
		$preview_message = mysqli_fetch_assoc($sql_query_m);
		# polovimo imena vseh sporocil
		$sql_string_m = "SELECT id, naslov FROM srv_invitations_messages WHERE ank_id = '$this->sid'";
		
		echo '<table id="inv_send_mail_preview">';
		echo '<tr><th>'.$lang['srv_inv_message_draft_content_name'].':</th>';
		echo '<td class="inv_bt">';
		echo '<span>';
		$sql_query_m = sisplet_query($sql_string_m);
		if (mysqli_num_rows($sql_query_m) > 0 ) {
			echo '<select onchange="mailSourceMesageChange(this);">';
			while ($row = mysqli_fetch_assoc($sql_query_m)) {
				echo '<option value="'.$row['id'].'"'.($row['id'] ==$preview_message['id']?' selected="selected"':'' ).'>'.$row['naslov'].'</option>';
			}
			echo '</select>';
		}
		#.$preview_message['naslov'];
		echo '</span>';
		echo '</td></tr>';
		echo '<tr><th>'.$lang['srv_inv_message_draft_content_subject'].':</th>';
		echo '<td class="inv_bt">';
		echo '<span>'.$preview_message['subject_text'].'</span>';
		echo '</td></tr>';
		echo '<tr><th>'.$lang['srv_inv_message_draft_content_body'].':</th>';
		echo '<td >';
		echo '<span class="nl2br">'.($preview_message['body_text']).'</span>';
		echo '</td></tr>';
		echo '</table>';
		
		// Opozorilo ce manjka #URL# v besedilu maila in imamo individualizirano vabilo
		if(strpos($preview_message['body_text'], '#URL#') == false && $this->surveySettings['usercode_required'] == 0 && $this->surveySettings['individual_invitation'] != 0)
			echo '<span class="red">'.$lang['srv_inv_message_draft_nourl_warning'].'</span><br />';
		
		// Opozorilo ce je #URL# v besedilu maila in imamo neindividualizirano vabilo
		elseif(strpos($preview_message['body_text'], '#URL#') == true && $this->surveySettings['individual_invitation'] == 0)
			echo '<span class="red">'.$lang['srv_inv_message_draft_url_warning'].'</span><br />';
		
		// Popravi sporocilo
		echo '<a href="'.$this->addUrl('view_message').'">'.$lang['srv_invitation_reedit_message'].'</a>';
	}

	function addSortField($field){
		$type = 'ASC';
		session_start();
		if ($_SESSION['rec_sort_field'] == $field) {
			if ($_SESSION['rec_sort_type'] == 'DESC') {
				$type = 'ASC';
			} else {
				$type = 'DESC';
			}
		} else {
			$type = 'ASC';
		}
		return ' onclick="inv_set_sort_field(\''.$field.'\',\''.$type.'\');" ';
	}
	function addSortIcon($field){
		session_start();
		if ($_SESSION['rec_sort_field'] == $field) {
			if ($_SESSION['rec_sort_type'] == 'DESC') {
				return ' <span class="faicon sort_descending icon-blue"></span>';
				
			} else {
				return ' <span class="faicon sort_ascending icon-blue"></span>';
			}
		}
		return;
	}
	
	function setSortField() {
		session_start();
		if (isset($_POST['field']) && trim($_POST['field']) != '') {
			$_SESSION['rec_sort_field'] = trim($_POST['field']); 
		} else {
			$_SESSION['rec_sort_field'] = 'date_inserted';
		}
		if (isset($_POST['type']) && trim($_POST['type']) != '') {
			$_SESSION['rec_sort_type'] = trim($_POST['type']); 
		} else {
			$_SESSION['rec_sort_type'] = 'ASC';
		}
		session_commit();
	}
	
	
	function getSortString() {
		session_start();
		
		$sort_string = ' ORDER BY i.last_status';
		
		if (isset($_SESSION['rec_sort_field']) && trim($_SESSION['rec_sort_field']) != '') {
			$prefix = 'i.';
			if ($_SESSION['rec_sort_field'] == 'count_inv') {
				$prefix = '';
			}

			if ($_SESSION['rec_sort_field'] == 'date(date_expired)') {
				$sort_string = ' ORDER BY '.$prefix.'date_expired';
			}
			else{
				$sort_string = ' ORDER BY '.$prefix.trim($_SESSION['rec_sort_field']);
			}
			
			if ($_SESSION['rec_sort_type'] == 'DESC') {
				$sort_string .= ' DESC';
			} else {
				$sort_string .= ' ASC';
			}
		}
		
		// Vedno dodatno se sortirtamo po mailu
		$sort_string .= ', i.email';
		
		return $sort_string;
	}
	
	function getAvailableSysVars() {
		$result = array();
		$qry = sisplet_query("SELECT s.variable, s.naslov FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='".$this->sid."' AND s.gru_id=g.id AND s.tip!='5' AND (s.tip < '10' OR s.tip = '22' OR s.tip = '25' OR s.tip='21') AND s.sistem='1'");
		while ($row = mysqli_fetch_assoc($qry)) {
			$result[$row['variable']] = $row['naslov'];
		}
		return $result;
	}
	
	function getAvailableIndicators() {
		$result = array();
		$_indicators = array('email','password','firstname','lastname','salutation','phone','custom','relation');
		#za vsako spremenljivko preverimo zapise v bazi
		foreach ($_indicators AS $indicator) {
			$sql_string = "SELECT count(*) FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted = '0' AND $indicator IS NOT NULL";
			list($cnt) = mysqli_fetch_row(sisplet_query($sql_string));
			if ((int)$cnt > 0) {
				$result[] = $indicator;
			}
		}
		return $result;
	}

	function createSystemVariablesMapping() {
		global $lang;
		# polovimo sistemske variable
		$sys_db_maps = array('email');
		
		$strSelect = "SELECT spr_id, field FROM srv_invitations_mapping WHERE sid = '".$this->sid."'";
		$qrySelect = sisplet_query($strSelect);
		$mappingArray = array();
		while (list($spr_id,$field) = mysqli_fetch_row($qrySelect)) {
			$mappingArray[$spr_id] = $field;
		}
		$qryString = "SELECT s.id, s.naslov, s.variable, s.variable_custom, s.coding FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.tip IN (1,3,17,21) AND s.gru_id=g.id AND g.ank_id='".$this->sid."' ORDER BY g.vrstni_red, s.vrstni_red";
		$sqlSpremenlivka = sisplet_query($qryString);
		
		if (mysqli_num_rows($sqlSpremenlivka) > 0) {
			echo '<form id="inv_ValidateSysVarsMappingFrm" name="inv_ValidateSysVarsMappingFrm">';
			echo $lang['srv_invitation_system_email_choose']; 
			echo '<table>';
			while ($row = mysqli_fetch_assoc($sqlSpremenlivka)) {
				$system_variables[$row['id']] = $row;
				echo '<tr>';
				echo '<td>';
				$checked = (isset($mappingArray[$row['id']]) && $mappingArray[$row['id']] == 'email' )?' checked="checked"':'';
				echo '<input type="radio" name="sysVarMap" id="sysVarMap_'.$row['id'].'" value="'.$row['id'].'"'.$checked.'>';
				echo '</td>';
				echo '<td><label for="sysVarMap_'.$row['id'].'"><span style="color: #78a971;">'.$row['variable'].'</span></label></td>';
				echo '<td><label for="sysVarMap_'.$row['id'].'">'.$row['naslov'].'</label></td>';
			
				echo '</tr>';
			}
			echo '</table>';
			echo '<form/>';
			echo '<br/>';
			echo '<div id="inv_ValidateSysVarsMappingDiv">';
			echo '<span onclick="inv_ValidateSysVarsMapping();" class="buttonwrapper floatLeft spaceLeft" ><a class="ovalbutton ovalbutton_orange" ><span>'.$lang['srv_invitation_system_validate'].'</span></a></span>';
			echo '</div>';
				
		} else {
			echo '<span>'.$lang['srv_invitation_system_error3'].'</span>';
		}
	}
	
	function validateSysVarsMapping() {
		global $lang,$global_user_id;
		# preverimo sistemske spremenljivke
		$strSistemske = "SELECT count(*) FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.gru_id=g.id AND g.ank_id='".$this->sid."' AND variable IN("."'" . implode("','",$this->inv_variables)."')  ORDER BY g.vrstni_red, s.vrstni_red";
		list($cntSistemske) = mysqli_fetch_row(sisplet_query($strSistemske));
		
		$emailsToAdd = array();
		$invalidEmails = array();
		$errors = array();
		$emailSpr = (int)$_POST['sysVarMap'];
		if ((int)$emailSpr > 0) {
			# preverimo ali lovimo samo ustrezne ali vse userje , preverimo kako imamo nastavljeno pri podatkih
			global $global_user_id;
			$_POST['meta_akcija'] = 'data';
			SurveyStatusProfiles :: Init($this->sid, $global_user_id);
			$currentProfileId = SurveyStatusProfiles :: getCurentProfileId();
			$statusProfileCondition = SurveyStatusProfiles :: getStatusAsQueryString($currentProfileId);
			
			#zloopamo skozi userje in dodamo kateri še niso bili dodani
			$selectUser = "SELECT id,cookie,pass,last_status,lurker,unsubscribed FROM srv_user where ank_id='".$this->sid."' AND inv_res_id IS NULL AND deleted='0' ".$statusProfileCondition;
			$queryUser = sisplet_query($selectUser );
			
			if (mysqli_num_rows($queryUser) ) {
				#zakeširamo vrednosti za email
				$this->getUsersDataForSpr($emailSpr);
			
				while ($row = mysqli_fetch_assoc($queryUser)) {
					$email = trim($this->cacheArrayVrednost[$emailSpr][$row['id']]);
					
					if ($this->validEmail($email)) {
						$emailsToAdd[] = $email;
					} else {
						$invalidEmails[] = $email;
					}
				}
				if (count($invalidEmails) > 0) {
					$errors[] = $lang['srv_invitation_system_error1']."(".count($invalidEmails).')';
				}					
			} else {
				$errors[] = $lang['srv_invitation_system_error2'];
			}
		} else {
				
			if ((int)$cntSistemske == 0) {
				# ni sistemskih spremenljivk
				$errors[] = $lang['srv_invitation_system_error3'];
			} else {
				# ni določena email spremenljivka
				$errors[] = $lang['srv_invitation_system_error4'];
			}
		}
		echo '<span onclick="inv_ValidateSysVarsMapping();" class="buttonwrapper floatLeft spaceLeft spaceRight" ><a class="ovalbutton ovalbutton_gray" ><span>'.$lang['srv_invitation_system_validate'].'</span></a></span>';
		if (count($emailsToAdd)) {
			echo '<span onclick="inv_addSysVarsMapping();" class="buttonwrapper floatLeft spaceLeft spaceRight" ><a class="ovalbutton ovalbutton_orange" ><span>'.$lang['srv_invitation_system_validateAndAdd'].'</span></a></span>';
		}
		if (mysqli_num_rows($queryUser) > 0)
		{ # če je kaj novih zapisov v bazi
			
			# če že imamo prejemnike v bazi ponudimo gumb naprej
			echo '<span class="buttonwrapper floatLeft spaceLeft" ><a class="ovalbutton ovalbutton_orange" href="'.$this->addUrl('view_recipients').'"><span>'.$lang['srv_invitation_forward'].'</span></a></span>';
			
			echo '<br class="clr"/>';
			echo '<br/>';
			echo '<span class="strong">';
			printf($lang['srv_invitation_system_found'],mysqli_num_rows($queryUser));
			echo '</span>';
			echo '<br/>';
			echo $lang['srv_invitation_system_from_this'];
			
			if (count($emailsToAdd)) {
				echo $lang['srv_invitation_system_from_this_valid'].(int)count($emailsToAdd);
				if (count($invalidEmails) > 0) {
					echo '<br/>';
					echo $lang['srv_invitation_system_and'];
				}
			}
			if (count($invalidEmails) > 0) {
				echo $lang['srv_invitation_system_from_this_invalid'].(int)count($invalidEmails);
			}	
		}
		else
		{
			echo '<br class="clr"/>';
			echo '<br/>';
			echo $lang['srv_invitation_system_not_found'];
		}
	}
	function addSysVarsMapping() {
		global $lang,$global_user_id;
		
		$addedEmails = array();
		$errorEmails = array();
		$invalidEmails = array();
		$errors = array();

		#pobrišemo obstoječe povezave
		$strDelete = "DELETE FROM srv_invitations_mapping WHERE sid = '".$this->sid."'";
		$qryDelete = sisplet_query($strDelete);
		
		$emailSpr = (int)$_POST['sysVarMap'];
		if ((int)$emailSpr > 0) {
			$insertString = "INSERT INTO srv_invitations_mapping (sid, spr_id, field) VALUES ('$this->sid','$emailSpr','email')";
			sisplet_query($insertString);
			sisplet_query("COMMIT");
			
			# preverimo ali lovimo samo ustrezne ali vse userje , preverimo kako imamo nastavljeno pri podatkih
			global $global_user_id;
			$_POST['meta_akcija'] = 'data';
			SurveyStatusProfiles :: Init($this->sid, $global_user_id);
			$currentProfileId = SurveyStatusProfiles :: getCurentProfileId();
			$statusProfileCondition = SurveyStatusProfiles :: getStatusAsQueryString($currentProfileId);
			
			#zloopamo skozi userje in dodamo kateri še niso bili dodani
			$selectUser = "SELECT id,cookie,pass,last_status,lurker,unsubscribed FROM srv_user where ank_id='".$this->sid."' AND inv_res_id IS NULL AND deleted='0' ".$statusProfileCondition;
			$queryUser = sisplet_query($selectUser );
			if (mysqli_num_rows($queryUser)) {
				#zakeširamo vrednosti za email
				$this->getUsersDataForSpr($emailSpr);
				
				while ($row = mysqli_fetch_assoc($queryUser)) {
					$email = trim($this->cacheArrayVrednost[$emailSpr][$row['id']]);
					if ($this->validEmail($email)) {
						$pass = (trim($row['pass']) != '') ? trim($row['pass']) : substr($row['cookie'],0,6);
						#dodamo respondenra in naredimo povezav
						$sql_insert = "INSERT IGNORE INTO srv_invitations_recipients (ank_id,email,password,cookie,sent,responded,unsubscribed,deleted,date_inserted,inserted_uid,list_id,last_status) VALUES ";
						$sql_insert .= "('".$this->sid."','$email','$pass','".$row['cookie']."'";
						$sql_insert .= ",'0','0','".(int)$row['unsubscribed']."','0',NOW(),'".$global_user_id."','".$list_id."','".$row['last_status']."')";
						$sqlQuery = sisplet_query($sql_insert);
						if (!$sqlQuery) {
							$error = mysqli_error($GLOBALS['connect_db']);
							$errorEmails[] = $email;
						} else {
							$rid = mysqli_insert_id($GLOBALS['connect_db']);
							if ((int)$rid > 0) {
								# updejtamo srv user
								$sqlString2 = "UPDATE srv_user SET inv_res_id='$rid' WHERE id='".$row['id']."'";
								$updateQuery2 = sisplet_query($sqlString2);
								$addedEmails[] = $email;
							} else {
								$errorEmails[] = $email;
							}
						}
						sisplet_query("COMMIT");
					} else {
						if ($email != '') {
							$invalidEmails[] = $email;
						} else {
							$invalidEmails[] = $lang['srv_invitation_system_empty_email'];
						}
					}
				}
			} else {
				#V bazi ni respondentov, katere lahko dodam v vabila!
				$errors[] = $lang['srv_invitation_system_error2'];
			}
		} else {
			# ni določena email spremenljivka
			$errors[] = $lang['srv_invitation_system_error4'];
		}
		echo '<span onclick="inv_ValidateSysVarsMapping();" class="buttonwrapper floatLeft spaceLeft" ><a class="ovalbutton ovalbutton_orange" ><span>'.$lang['srv_invitation_system_validate'].'</span></a></span>';
		echo '<br class="clr"/>';
		echo '<br/>';
		if (count($errors) > 0) {
			foreach ($errors AS $error) {
				echo '<br/><span class="strong">'.$error.'</span>';
			}
		}
		if (count($addedEmails) > 0) {
			echo '<span class="strong">'.$lang['srv_invitation_system_added_users_finish']."(".(int)count($addedEmails)."): </span>";
			foreach ($addedEmails AS $email) {
				echo '<br/><span>'.$email.'</span>';
			}
		}
		if (count($errorEmails) > 0) {
			echo $lang['srv_invitation_system_error6']." (".(int)count($errorEmails)."): ";
			foreach ($errorEmails AS $email) {
				echo '<br/><span>'.$email.'</span>';
			}
			
		}
		if (count($invalidEmails) > 0) {
			echo '<span class="strong gray">'.$lang['srv_invitation_system_error6'].'('.(int)count($invalidEmails).'): </span><br/>';
			foreach ($invalidEmails AS $email) {
				echo '<span>'.$email.'</span><br/>';
			}
			
		}
	}
	
	private $cacheArrayVrednost = array();
	function getUsersDataForSpr($spr_id = 0) {

		if ((int)$spr_id > 0 ) {
			if (!isset($this->cacheArrayVrednost[$spr_id])) {
				list($tip) = mysqli_fetch_row(sisplet_query("SELECT tip FROM srv_spremenljivka WHERE id = '$spr_id'"));
				switch ($tip) {
					case 21:
						$str = "SELECT u.id, sdt.text FROM srv_data_text".$this->db_table." AS sdt LEFT JOIN srv_user AS u ON u.id=sdt.usr_id WHERE sdt.spr_id ='$spr_id' AND ank_id='".$this->sid."' AND inv_res_id IS NULL AND deleted='0'";
						$sql = sisplet_query($str);
						while (list($uid,$txt) = mysqli_fetch_row($sql)) {
							if ((int)$uid > 0 && trim($txt) != '') {
								$this->cacheArrayVrednost[$spr_id][$uid] = $txt;
							}
						}
					break;
					return $this->cacheArrayVrednost[$spr_id];
				}
			} else {
				return $this->cacheArrayVrednost[$spr_id];
			}
		}
	}
	
	function saveRecipientListName() {
		global $lang,$site_url, $global_user_id;
	
		$array_profiles = array();
		#ne vem če je fino da lahko dodaja kar na vse sezname
		session_start();
		$_only_this_survey = (isset($_SESSION['inv_rec_only_this_survey']) && (int)$_SESSION['inv_rec_only_this_survey'] == 1) ? '' : " AND from_survey = '".$this->sid. "'" ;
		
		# polovimo še ostale porfile
		$sql_string = "SELECT pid, name,comment FROM srv_invitations_recipients_profiles WHERE uid in('".$global_user_id."')".$_only_this_survey;
		$sql_query = sisplet_query($sql_string);
		while ($sql_row = mysqli_fetch_assoc($sql_query)) {
			$array_profiles[$sql_row['pid']] = array('name' => $sql_row['name'], 'comment'=>$sql_row['comment']);
		}
	
		echo '<div style="margin-top:10px; padding:5px; background-color:#f2f2f2; border:1px solid #c2c2c2;">';
	#	echo '<span class="inv_new_list_note">';
	#	echo $lang[''].'Izberite seznam kamor želite dodati prejemnike. Izbirate lahko med:<br/><ul><li>\'Nov seznam\' - prejemniki se dodajo v nov seznam, kateremu določite ime</li><li>\'Začasen seznam\' - seznam obstaja samo v času seje brskalnika</li><li>ali izberete obstoječ seznam, h kateremu se bodo dodali prejemniki</li></ul><br/>';
	#	echo '</span>';
		echo $lang['srv_invitation_recipients_list_add'].':&nbsp;';
		echo '<select id="sel_inv_list_type" onchange="inv_new_recipients_list_change(this);" autofocus="autofocus">';
		echo '<option value="0" class="gray bold"'.((int)$_POST['pid'] > 0 ? '' : ' selected="selected"'  ).'>'.$lang['srv_invitation_new_list'].'</option>';
		echo '<option value="-1" class="gray bold">'.$lang['srv_invitation_new_templist'].'</option>';
		if (count($array_profiles) > 0){
			foreach($array_profiles AS $key => $profile) {
				echo '<option value="'.$key.'" comment="'.$profile['comment'].'"'.($_POST['pid'] == $key ? ' selected="selected"' : '').'>'.$profile['name'].'</option>';
			}
		}
		echo '</select>';
		echo '<span id="new_recipients_list_span_note" '.((int)$_POST['pid'] < 1 ? ' class="displayNone"' : ''  ).'>';
		echo '<br>';
		echo '<br>';
		echo $lang[''].'Uporabniki bodo dodani v seznam: ';
		echo $array_profiles[$_POST['pid']]['name'];
		echo '<span id="new_recipients_list_span_note_name"></span>';
		echo '</span>';
		echo '<span id="new_recipients_list_span" '.((int)$_POST['pid'] > 0 ? ' class="displayNone"' : ''  ).'>';
		
		echo '<br>';
		echo '<br>';
		echo '<label>'.$lang['srv_inv_recipient_list_name'];
		# zaporedno številčimo ime seznama1,2.... če slučajno ime že obstaja
		$new_name = $lang['srv_inv_recipient_list_new'];
		$names = array();
		$s = "SELECT name FROM srv_invitations_recipients_profiles WHERE name LIKE '%".$new_name."%' AND uid='$global_user_id'";
		$q = sisplet_query($s);
		while ($r = mysqli_fetch_assoc($q)) {
			$names[] = $r['name'];
		}
		if (count($names) > 0) {
		$cnt = 1;
			while (in_array($lang['srv_inv_recipient_list_new'].$cnt, $names)) {
			$cnt++;
			}
			$new_name = $lang['srv_inv_recipient_list_new'].$cnt;
		}
	
		echo '<input type="text" id="rec_profile_name" value="'.$new_name.'" tabindex="1" autofocus="autofocus">';
		echo '</label>';
		echo '</span>';
		echo '<br/><br/>';
		echo $lang['srv_inv_recipient_list_comment'];
		echo '<textarea id="rec_profile_comment" tabindex="3" rows="2" >';
		echo $array_profiles[$_POST['pid']]['comment'];
		echo '</textarea>';
		echo '</div>'; 

	}
	
	function getCleanString($string) {
		return preg_replace ("/'/", "`", $string);
	}
	
	function recipientsAddForward() {

		$doSave = $_POST['doSave'] == 'true' ? true : false; 
		$doAdd = $_POST['doAdd'] == 'true' ? true : false; 

		$fields = $_POST['fields'];
		$recipients_list = $this->getCleanString($_POST['recipients_list']);
        $delimiter = $_POST['recipientsDelimiter'];

		$profileName = $_POST['profile_name'];
		$profileComment = $_POST['profile_comment'];
		$pid = $_POST['profile_id'];
		
        // Bom kar tule rešil konverzijo iz delimiterja v |~|
        $recipients_list = str_replace ($delimiter, "|~|", $recipients_list);
		
		# če shranjujemo respondente 
		if ($doSave == true){
			$pid = $this->saveAppendRecipientList($pid, $fields, $recipients_list, $profileName, $profileComment);
		}	

		# če dodajamo respondente v bazo
		if ($doAdd == true){
			#dodamo polja
			$result = $this->addMassRecipients($recipients_list, $fields, $pid);

			# prikažemo napake
			$invalid_recipiens_array = $this->displayRecipentsErrors($result);
		}
		
		# če smo dodajali repsondente v bazo, prikažemo tabelo respondentov
		if ($doAdd == true){
			$this->viewRecipients();
		}
		# če ne prikažemo profile respondentov
		else {
			$this->useRecipientsList($pid);
		}
		
		// Ce smo dodajali respondente rocno v bazo, to shranimo, ker potem ne smemo povezati mailov s podatki
		// TEGA NE SMEMO NAREDITI, KER DRUGACE SE VSAKIC OB DODAJANJU NA NOVO UGASNE
		/*if ($doAdd == true) {
			$update = sisplet_query("UPDATE srv_anketa SET show_email='0' WHERE id='".$this->sid."'");
        }*/
	}
	
	function saveAppendRecipientList($pid, $fields, $recipients, $profileName, $profileComment) {
		global $lang,$site_url, $global_user_id;
	
		# shranjujemo v nov profil
		$post_fields = str_replace('inv_field_','',implode(',',$fields));
		$post_recipients = $this->getCleanString($recipients);

		$pid = (int)$pid;
		# če je pid < 0 shranimo v nov porfil
		if ($pid <= 0) 
		{
			# ali shranjujemo v sejo
			if ($pid == -1)
			{
				$this->saveSessionRecipients($post_recipients,$post_fields,$profileComment);
			}
			else
			{
				# dodelimo ime
				if ($profileName == NULL || trim($profileName) == '') 
				{
					#zaporedno številčimo ime seznama1,2.... če slučajno ime že obstaja
					$profileName = $lang['srv_inv_recipient_list_new'];
					$names = array();
					$s = "SELECT name FROM srv_invitations_recipients_profiles WHERE name LIKE '%".$new_name."%' AND uid='$global_user_id'";
					$q = sisplet_query($s);
					while ($r = mysqli_fetch_assoc($q)) 
					{
						$names[] = $r['name'];
					}
					if (count($names) > 0) 
					{
						$cnt = 1;
						while (in_array($lang['srv_inv_recipient_list_new'].$cnt, $names)) 
						{
							$cnt++;
						}
						$profileName = $lang['srv_inv_recipient_list_new'].$cnt;
					}
				}
				$sql_insert = "INSERT INTO srv_invitations_recipients_profiles".
								" (name,uid,fields,respondents,insert_time,comment, from_survey) ".
								" VALUES ('$profileName', '$global_user_id', '$post_fields', '$post_recipients', NOW(), '$profileComment', '".$this->sid."' )";
				$sqlQuery = sisplet_query($sql_insert);
				if (!$sqlQuery) 
				{
				}
				else 
				{
					$pid = mysqli_insert_id($GLOBALS['connect_db']);
				}
			}
		} else {
			# polovimo obstoječe podatke
			
			$s = "SELECT fields, respondents FROM srv_invitations_recipients_profiles WHERE pid ='$pid'";
			$q = sisplet_query($s);
			list($old_fields, $old_respondents) = mysqli_fetch_row($q);

			# najprej polja
			$old_fields = explode(',',$old_fields);
			$post_fields = explode(',',$post_fields);
			foreach ($post_fields AS $post_field ) 
			{
				#če polje še ni v bazi ga dodamo
				if (!in_array($post_field, $old_fields)) 
				{
					$old_fields[] = $post_field;
				}
			}
			
			# nato porihtamo podatke
			$old_recipients_list = explode("\n", $old_respondents);
			$new_recipients_list = explode("\n", $post_recipients);
			
			foreach ($new_recipients_list AS $post_recipient ) 
			{
				#če polje še ni v bazi ga dodamo
				if (!in_array($post_recipient, $old_recipients_list)) 
				{
					$old_recipients_list[] = $post_recipient;
				}
			}
			
			# počistimo prazne
			foreach ($old_recipients_list AS $k => $post_recipient )
			{
				if (is_null($post_recipient) || $post_recipient == '') 
				{
					unset($old_recipients_list[$k]);
				}	
			}		
			
			$post_fields = implode(",",$old_fields);;
			$post_recipients = implode("\n",$old_recipients_list);
			
			# updejtamo obstoječ profil
			$sql_update = " UPDATE srv_invitations_recipients_profiles".
				" SET fields = '$post_fields', respondents ='$post_recipients' WHERE pid = '$pid'";
	
			$sqlQuery = sisplet_query($sql_update);
			if (!$sqlQuery) 
			{
			}
			else 
			{
			}
		}
		sisplet_query("COMMIT");
		
		#vrnemo pid seznama (če smo kreirali nov seznam je tako now pid)
		return $pid;
	}
	
	
	function showAdvancedConditions()
	{
		global $lang;
		
		$scp = new SurveyCondition($this->sid);
		$scp->setChooseAction('invitationSetCondition()');
		$scp->displayConditions($_POST['cid']);
		
	}
	
	function setAdvancedCondition()
	{
		global $lang;
		SurveySession::sessionStart($this->sid);
		SurveySession::set('invitationAdvancedConditionId', (int)$_POST['cid']);
		$this->invitationAdvancedConditionId = (int)$_POST['cid'];
		$this->viewRecipients();
	}
	
	function getConditionUserIds($cid = 0){
        global $global_user_id;
        
		if ($cid > 0){

            // Poskrbimo za datoteko s podatki
            $SDF = SurveyDataFile::get_instance();
            $SDF->init($this->sid);           
            $SDF->prepareFiles();  

            $headFileName = $SDF->getHeaderFileName();
            $dataFileName = $SDF->getDataFileName();
            $dataFileStatus = $SDF->getStatus();

			
			if ($dataFileStatus >= 0){

                $_HEADERS = unserialize(file_get_contents($headFileName));
                
				SurveyConditionProfiles :: Init($this->sid, $global_user_id);
				SurveyConditionProfiles :: setHeader($_HEADERS);
			
                $_condition_profile_AWK = SurveyConditionProfiles:: getAwkConditionString($cid);
                
				if (IS_WINDOWS) {
					$_command = 'gawk -F"'.STR_DLMT.'" "'.$_condition_profile_AWK.' { if ('.USER_ID_FIELD.'!=0) print \"$invUsrId[]=\",'.USER_ID_FIELD.',\";\" }" '.$dataFileName .' >> '. $dataFileName.'.tmp';
                } 
                else {
					$_command = 'awk -F"'.STR_DLMT.'" \' '.$_condition_profile_AWK.' { if ('.USER_ID_FIELD.'!=0) print "$invUsrId[]=",'.USER_ID_FIELD.',";" }\' '.$dataFileName .' >> '.$dataFileName.'.tmp';
                }
                
				$file_handler = fopen($dataFileName.'.tmp',"w");
				fwrite($file_handler,"<?php\n");
				fclose($file_handler);
				$out = shell_exec($_command);
				
				$file_handler = fopen($dataFileName.'.tmp',"a");
				fwrite($file_handler,'?>');
				fclose($file_handler);
				include($dataFileName.'.tmp');
				
				if (file_exists($dataFileName.'.tmp')) {
					unlink($dataFileName.'.tmp');
                }
                
				if (is_array($invUsrId) && count($invUsrId) > 0){
					return $invUsrId;
				} 
			} 
			else{
				echo '!!!OLD DATA FILE!!!';
			}
        }
        
		return array();
	}
	
	function advancedCondition()
	{
		global $lang;
		echo '<span id="conditionProfileNote" class="simple">';
		echo $lang['srv_inv_condition_filter'].' ';
			
		if ((int)$this->invitationAdvancedConditionId > 0)
		{
			$scp = new SurveyCondition($this->sid);
			echo ' <strong>'.$scp->getConditionName((int)$this->invitationAdvancedConditionId).'</strong> ';
			$this->user_inv_ids = $this->getConditionUserIds($this->invitationAdvancedConditionId);
			$note = $scp -> getConditionString($this->invitationAdvancedConditionId );
			echo $note;
			if (is_array($this->user_inv_ids) && count($this->user_inv_ids) > 0)
			{
			}
			else
			{
			}
			echo '<span class="as_link spaceLeft" onclick="showInvitationAdvancedConditions(\''.(int)$this->invitationAdvancedConditionId.'\'); return false;">'.$lang['srv_profile_edit'].'</span>';
			echo '<span class="as_link spaceLeft" onclick="invitationSetCondition(\'0\'); return false;">'.$lang['srv_profile_remove'].'</span>';
		}  
		else
		{
			echo '<strong>'.$lang['srv_inv_condition_no_filter'].'</strong>';
			echo '<a href="#" class="faicon if_add" onclick="showInvitationAdvancedConditions(); return false;" data-cid="'.(int)$this->invitationAdvancedConditionId.'">&nbsp;</a>';
		}
		echo '</span>';
	}
	
	function listCondition()
	{
		global $lang;
		# get all lists
		$lists = $this->getSurveyInvitationLists(true);
		
		$selected = (int)(isset($_SESSION['inv_filter']['list']) ? (int)$_SESSION['inv_filter']['list'] : -2);
		echo '&nbsp;';
		echo '<label>'.$lang['srv_invitation_recipients_filter_list'];
		echo '<select id="inv_rec_filter_list" onchange="inv_filter_recipients();">';
		foreach ($lists AS $list_id => $list)
		{
			echo '<option value="'.$list_id.'"'.((int)$selected == $list_id ? ' selected="selected"' : '').'>'.$list['name'].'</option>';
		}
		echo '</select></label>';
	}

	function getSurveyInvitationLists($add_all = false)
	{
		global $lang;
		# polovimo sezname
		$lists = array();
		if ($add_all)
		{
			$lists['-2'] = array('name'=>$lang['srv_invitation_recipients_filter_list_all']);
		}
		$lists['-1'] = array('name'=>$lang['srv_invitation_new_templist']);
		$lists['0'] = array('name'=>$lang['srv_invitation_new_templist_author']);
		$sql_string_lists = "SELECT * from srv_invitations_recipients_profiles WHERE pid IN(SELECT i.list_id FROM srv_invitations_recipients AS i WHERE i.ank_id = '{$this->sid}' AND i.deleted = '0' GROUP BY i.list_id ORDER BY i.id) ";
		$sql_query_lists = sisplet_query($sql_string_lists);
		while ($row_lists = mysqli_fetch_assoc($sql_query_lists)) {
			$lists[$row_lists['pid']] = array('name'=>$row_lists['name']);
		}
		
		return $lists;
	}				

	
	function viewServerSettings(){
		global $lang;
		global $site_url;
		global $site_url;
		global $site_path;
		global $admin_type;
		global $global_user_id;
		global $mysql_database_name;
		global $aai_instalacija;

		
		$row = SurveyInfo::getInstance()->getSurveyRow();

		echo '<form name="settingsanketa_' . $row['id'] . '" action="ajax.php?a=editanketasettings&m=inv_server" method="post" autocomplete="off">' . "\n\r";
		echo '	<input type="hidden" name="anketa" value="' . $this->sid . '" />' . "\n\r";
				echo '  <input type="hidden" name="location" value="' . $_GET['a'] . '" />' . "\n\r";
		echo '  <input type="hidden" name="submited" value="1" />' . "\n\r";
		
		$MA = new MailAdapter($this->sid, $type='invitation');
		
		// Dostop za posiljanje mailov preko 1ka serverja
		$enabled1ka = $MA->is1KA() ? true : false;
		
        // Admini na testu, www in virtualkah imajo 1ka smtp
        if(($admin_type == 0) && ($mysql_database_name == 'www1kasi' || $mysql_database_name == 'test1kasi' || $mysql_database_name == 'real1kasi'))
            $enabled1ka = true;

        
        // Squalo
        $squalo = new SurveyInvitationsSqualo($this->sid);
        if($squalo->getSqualoEnabled()){

            // Vklop squalo
            echo '<div class="squalo_switch"><p>';

            echo '<span class="bold">'.$lang['srv_squalo'].':</span>&nbsp;';

            echo '<input type="hidden" name="squalo_mode" value="0">';
            echo '<label><input type="checkbox" name="squalo_mode" id="squalo_mode" value="1" '.($squalo->getSqualoActive() ? 'checked ="checked" ' : '').' style="vertical-align:-2px;" onclick="squaloSwitch();">';
            echo $lang['srv_squalo_sending'].' </label>';

            echo '</p></div>';


            // Squalo nastavitve...
            echo '<div class="squalo_settings '.(!$squalo->getSqualoActive() ? ' displayNone' : '').'">';

            echo $lang['srv_squalo_active'];

            echo '</div>';
        }


        // Izbira streznika
        echo '<div class="mail_mode_switch '.($squalo->getSqualoActive() ? ' displayNone' : '').'">';

        // Opozorilo, ce imamo vklopljena vabila, da gre za iste nastavitve
		echo '<p class="red bold">'.$lang['srv_email_server_settings_warning'].'</p>';

        // Izbira SMTP streznika
		echo '<span class="bold">'.$lang['srv_email_setting_select_server'].'</span>&nbsp;';
		
        // AAI ima Arnesov smtp
        if($aai_instalacija){
            echo '<label><input type="radio" name="SMTPMailMode" value="0" '.($MA->is1KA() ? 'checked ="checked" ' : '').' onclick="smtpAAIPopupShow();">';
            echo $lang['srv_email_setting_adapter0_aai']. ' </label>';  
        }
        else{
            echo '<label><input type="radio" name="SMTPMailMode" value="0" '.($MA->is1KA() ? 'checked ="checked" ' : '').' '.($enabled1ka ? '' : ' disabled="disabled"').' onclick="$(\'#send_mail_mode1, #send_mail_mode2\').hide();$(\'#send_mail_mode0\').show();">';
            echo $lang['srv_email_setting_adapter0']. ' </label>';
        }

        // Google smtp je viden samo starim, kjer je ze vklopljen
        if($MA->isGoogle()){
            echo '<label><input type="radio" name="SMTPMailMode" value="1" '.($MA->isGoogle() ? 'checked ="checked" ' : '').' onclick="$(\'#send_mail_mode0, #send_mail_mode2\').hide(); $(\'#send_mail_mode1\').show();">';
            echo $lang['srv_email_setting_adapter1'].' </label>';
        }
		
        // Lastni smtp
        echo '<label><input type="radio" name="SMTPMailMode" value="2" '.($MA->isSMTP() ? 'checked ="checked" ' : '').' onclick="$(\'#send_mail_mode0, #send_mail_mode1\').hide(); $(\'#send_mail_mode2\').show();">';
        echo $lang['srv_email_setting_adapter2'].' </label>';

		echo Help :: display('srv_mail_mode');

        echo '</div>';

		
		#1KA
		$enkaSettings = $MA->get1KASettings($raziskave=true);
		echo '<div id="send_mail_mode0" '.(!$MA->is1KA() || $squalo->getSqualoActive() ? ' class="displayNone"' : '').'>';
		echo '<br /><span class="bold">'.$lang['srv_email_setting_settings'].'</span>';
		echo '<br />';	
		# from
		echo '<p><label>'.$lang['srv_email_setting_from'].'<span>'.$enkaSettings['SMTPFrom'].'</span><input type="hidden" name="SMTPFrom0" value="'.$enkaSettings['SMTPFrom'].'"></label>';
		echo '</p>';
		# replyTo
		echo '<p><label>'.$lang['srv_email_setting_reply'].'<input type="text" name="SMTPReplyTo0" value="'.$enkaSettings['SMTPReplyTo'].'" ></label>';
        echo '</p>';
        #delay 
		echo '<p><label>'.$lang['srv_email_setting_smtp_delay'].' '.Help::display('srv_inv_delay').': <select name="SMTPDelay0">'
            /*. '<option value="0" '.($enkaSettings['SMTPDelay']=="0"?'selected="selected"':'') .'>0 </option>'
            . '<option value="10000" '.($enkaSettings['SMTPDelay']=="10000"?'selected="selected"':'') .'>0.01 sec (max 100 / sec)</option>'
            . '<option value="20000" '.($enkaSettings['SMTPDelay']=="20000"?'selected="selected"':'') .'>0.02 sec (max 50 / sec)</option>'
            . '<option value="50000" '.($enkaSettings['SMTPDelay']=="50000"?'selected="selected"':'') .'>0.05 sec (max 20 / sec)</option>'
            . '<option value="100000" '.($enkaSettings['SMTPDelay']=="100000"?'selected="selected"':'') .'>0.1 sec (max 10 / sec)</option>'
            . '<option value="200000" '.($enkaSettings['SMTPDelay']=="200000"?'selected="selected"':'') .'>0.2 sec (max 5 / sec)</option>'*/
            . '<option value="500000" '.($enkaSettings['SMTPDelay']=="500000"?'selected="selected"':'') .'>0.5 sec (max 2 / sec)</option>'
            . '<option value="1000000" '.($enkaSettings['SMTPDelay']=="1000000"?'selected="selected"':'') .'>1 sec (max 1 / sec)</option>'
            . '<option value="2000000" '.($enkaSettings['SMTPDelay']=="2000000"?'selected="selected"':'') .'>2 sec (max 30 / min)</option>'
            . '<option value="4000000" '.($enkaSettings['SMTPDelay']=="4000000"?'selected="selected"':'') .'>4 sec (max 15 / min)</option>'
            . '<option value="5000000" '.($enkaSettings['SMTPDelay']=="5000000"?'selected="selected"':'') .'>5 sec (max 12 / min)</option>'
            . '<option value="10000000" '.($enkaSettings['SMTPDelay']=="10000000"?'selected="selected"':'') .'>10 sec (max 6 / min)</option>'
            . '<option value="20000000" '.($enkaSettings['SMTPDelay']=="20000000"?'selected="selected"':'') .'>20 sec (max 3 / min)</option>'
            . '<option value="30000000" '.($enkaSettings['SMTPDelay']=="30000000"?'selected="selected"':'') .'>30 sec (max 2 / min)</option>'
            . '</select></label>';
        echo '</p>';
		echo '</div>';
		
		#GMAIL - Google
		$enkaSettings = $MA->getGoogleSettings();
		echo '<div id="send_mail_mode1" '.(!$MA->isGoogle() || $squalo->getSqualoActive() ? ' class="displayNone"' : '').'>';
		echo '<br /><span class="italic">'.$lang['srv_email_setting_adapter1_note'].'</span><br />';
		echo '<br /><span class="bold">'.$lang['srv_email_setting_settings'].'</span><br />';
		# from
		echo '<p><label>'.$lang['srv_email_setting_from'].'<input type="text" name="SMTPFrom1" value="'.$enkaSettings['SMTPFrom'].'"></label>';
		echo '</p>';
		# replyTo
		echo '<p><label>'.$lang['srv_email_setting_reply'].'<input type="text" name="SMTPReplyTo1" value="'.$enkaSettings['SMTPReplyTo'].'" ></label>';
		echo '</p>';
		#Password
		echo '<p><label>'.$lang['srv_email_setting_password'].'<input type="password" name="SMTPPassword1" placeholder="'.$lang['srv_email_setting_password_placeholder'].'"></label>';
		echo '</p>';
		echo '</div>';

		#SMTP
		$enkaSettings = $MA->getSMTPSettings();
		echo '<div id="send_mail_mode2" '.(!$MA->isSMTP() || $squalo->getSqualoActive() ? ' class="displayNone"' : '').'>';
		echo '<br /><span class="italic">'.$lang['srv_email_setting_adapter2_note'].'</span><br />';
		echo '<br /><span class="bold">'.$lang['srv_email_setting_settings'].'</span><br />';
		# from - NICE
		echo '<p><label>'.$lang['srv_email_setting_from_nice'].'<input type="text" name="SMTPFromNice2" value="'.$enkaSettings['SMTPFromNice'].'"></label>';
		echo '</p>';
		# from
		echo '<p><label>'.$lang['srv_email_setting_from'].'<input type="text" name="SMTPFrom2" value="'.$enkaSettings['SMTPFrom'].'"></label>';
		echo '</p>';
		# replyTo
		echo '<p><label>'.$lang['srv_email_setting_reply'].'<input type="text" name="SMTPReplyTo2" value="'.$enkaSettings['SMTPReplyTo'].'" ></label>';
		echo '</p>';
		#Username
		echo '<p><label>'.$lang['srv_email_setting_username'].'<input type="text" name="SMTPUsername2" value="'.$enkaSettings['SMTPUsername'].'" ></label>';
		echo '</p>';
		#Password
		echo '<p><label>'.$lang['srv_email_setting_password'].'<input type="password" name="SMTPPassword2" placeholder="'.$lang['srv_email_setting_password_placeholder'].'"></label>';
		echo '</p>';
		#autentikacija
		echo '<p>';
		echo $lang['srv_email_setting_autentication'];
		echo '<label><input type="radio" name="SMTPAuth2" value="0" '.((int)$enkaSettings['SMTPAuth'] != 1 ? 'checked ="checked" ' : '').'>';
		echo $lang['srv_email_setting_no'].'</label>';
		echo '<label><input type="radio" name="SMTPAuth2" value="1" '.((int)$enkaSettings['SMTPAuth'] == 1 ? 'checked ="checked" ' : '').'>';
		echo $lang['srv_email_setting_yes'].'</label>';
		echo '</p>';
		#Varnost SMTPSecure
		echo '<p>';
		echo $lang['srv_email_setting_encryption'];
		echo '<label><input type="radio" name="SMTPSecure2" value="0" '.((int)$enkaSettings['SMTPSecure'] == 0 ? 'checked ="checked" ' : '').'>';
		echo $lang['srv_email_setting_encryption_none'].'</label>';
		echo '<label><input type="radio" name="SMTPSecure2" value="ssl" '.($enkaSettings['SMTPSecure'] == 'ssl' ? 'checked ="checked" ' : '').'>';
		echo $lang['srv_email_setting_encryption_ssl'].'</label>';
		echo '<label><input type="radio" name="SMTPSecure2" value="tls" '.($enkaSettings['SMTPSecure'] == 'tls' ? 'checked ="checked" ' : '').'>';
		echo $lang['srv_email_setting_encryption_tls'].'</label>';
		echo '</p>';
		#port
		echo '<p><label>'.$lang['srv_email_setting_port'].'<input type="number" min="0" max="65535" name="SMTPPort2" value="'.(int)$enkaSettings['SMTPPort'].'" style="width:80px;"></label>';
		echo ' '.$lang['srv_email_setting_port_note'];
		echo '</p>';
		#host
		echo '<p><label>'.$lang['srv_email_setting_host'].'<input type="text" name="SMTPHost2" value="'.$enkaSettings['SMTPHost'].'" ></label>';
        echo '</p>';
        
        #delay 
		echo '<p><label>'.$lang['srv_email_setting_smtp_delay'].' '.Help::display('srv_inv_delay').': <select name="SMTPDelay2">'
                        /*. '<option value="0" '.($enkaSettings['SMTPDelay']=="0"?'selected="selected"':'') .'>0 </option>'
                        . '<option value="10000" '.($enkaSettings['SMTPDelay']=="10000"?'selected="selected"':'') .'>0.01 sec (max 100 / sec)</option>'
                        . '<option value="20000" '.($enkaSettings['SMTPDelay']=="20000"?'selected="selected"':'') .'>0.02 sec (max 50 / sec)</option>'
                        . '<option value="50000" '.($enkaSettings['SMTPDelay']=="50000"?'selected="selected"':'') .'>0.05 sec (max 20 / sec)</option>'
                        . '<option value="100000" '.($enkaSettings['SMTPDelay']=="100000"?'selected="selected"':'') .'>0.1 sec (max 10 / sec)</option>'
                        . '<option value="200000" '.($enkaSettings['SMTPDelay']=="200000"?'selected="selected"':'') .'>0.2 sec (max 5 / sec)</option>'*/
                        . '<option value="500000" '.($enkaSettings['SMTPDelay']=="500000"?'selected="selected"':'') .'>0.5 sec (max 2 / sec)</option>'
                        . '<option value="1000000" '.($enkaSettings['SMTPDelay']=="1000000"?'selected="selected"':'') .'>1 sec (max 1 / sec)</option>'
                        . '<option value="2000000" '.($enkaSettings['SMTPDelay']=="2000000"?'selected="selected"':'') .'>2 sec (max 30 / min)</option>'
                        . '<option value="4000000" '.($enkaSettings['SMTPDelay']=="4000000"?'selected="selected"':'') .'>4 sec (max 15 / min)</option>'
                        . '<option value="5000000" '.($enkaSettings['SMTPDelay']=="5000000"?'selected="selected"':'') .'>5 sec (max 12 / min)</option>'
                        . '<option value="10000000" '.($enkaSettings['SMTPDelay']=="10000000"?'selected="selected"':'') .'>10 sec (max 6 / min)</option>'
                        . '<option value="20000000" '.($enkaSettings['SMTPDelay']=="20000000"?'selected="selected"':'') .'>20 sec (max 3 / min)</option>'
                        . '<option value="30000000" '.($enkaSettings['SMTPDelay']=="30000000"?'selected="selected"':'') .'>30 sec (max 2 / min)</option>'
                        . '</select></label>';
		echo '</p>';

        echo '</div>';
		
		echo '</form>';
		
		echo '<br class="clr" />';

		// Gumb shrani
		echo '<span class="floatRight" ><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.settingsanketa_' . $row['id'] . '.submit(); return false;">';
		echo $lang['srv_email_setting_btn_save'] . '</a></div></span>';
		
		// Gumb preveri nastavitve
        echo '<span id="send_mail_mode_test" class="floatRight spaceRight '.($squalo->getSqualoActive() ? ' displayNone' : '').'"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_green" href="#" onclick="showTestSurveySMTP(); return false;">';
        echo $lang['srv_email_setting_btn_test'].'</a></div></span>';
		
		
		if ($_GET['s'] == '1') {
			echo '<div id="success_save" style="float:left; display:inline; margin: -2px 0 0 0;"></div>';
			echo '<script type="text/javascript">$(document).ready(function() {show_success_save();});</script>';
		}
	}
	
	// V session nastavimo nastavitev da se posilja z/brez email posiljanja
	function setNoEmailing(){

		if(isset($_POST['value'])){
			SurveySession::sessionStart($this->sid);
			SurveySession::set('inv_noEmailing', (int)$_POST['value']);
			
			// Ce preklopimo na drugo posiljanje (posta, sms...) moramo tudi preklopiti na rocni vnos kode
			if($_POST['value'] == '1'){
				sisplet_query("UPDATE srv_anketa SET usercode_required='1' WHERE id='".$this->sid."'");
			}
		}
	}
	
	// V session nastavimo tip posiljanja (ce ni email - posta, sms, drugo...)
	function setNoEmailingType(){
				
		if(isset($_POST['value'])){
			SurveySession::sessionStart($this->sid);
			SurveySession::set('inv_noEmailing_type', (int)$_POST['value']);
		}		
	}

    // Prikazemo popup za vklop arnes smtp-ja na aai
    private function showAAISmtpPopup(){
        global $lang;

        echo '<div class="popup_close"><a href="#" onClick="quick_title_edit_cancel(); return false;">✕</a></div>';
			
        echo '<h2>'.$lang['srv_email_setting_adapter0_aai_title'].'</h2>';

        echo '<div class="popup_content">';
        echo $lang['srv_email_setting_adapter0_aai_popup']; 
        echo '<br /><br />';   
        echo '<input type="checkbox" id="aai_smtp_checkbox" name="aai_smtp_checkbox" class="pointer" onClick="smtpAAIAccept();">';
        echo ' <label for="aai_smtp_checkbox"><b>'.$lang['srv_email_setting_adapter0_aai_popup2'].'</b></label>';
        echo '</div>';

        echo '<div class="buttons_holder">';
        echo '<span class="buttonwrapper floatRight spaceLeft" id="aai_smtp_button" style="display:none;" title="'.$lang['srv_save_profile_yes'].'"><a class="ovalbutton ovalbutton_orange" href="#" onClick="smtpAAISet(); return false;"><span>'.$lang['srv_save_profile_yes'].'</span></a></span>';
        echo '<span class="buttonwrapper floatRight" title="'.$lang['srv_cancel'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="smtpAAIPopupClose(); return false;"><span>'.$lang['srv_cancel'].'</span></a></span>';
        echo '</div>';
    }
}
