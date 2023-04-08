<?php
/** nov Class ki skrbi za telefonsko anketo
 *  November 2012
 *
 *
 * @author Gorazd_Veselic
 */
define('GROUP_PAGINATE', 4);			# po kolko strani grupira pri paginaciji
define('REC_ON_PAGE', 50);			# kolko zapisov na stran pri urejanju respondentov
define('REC_ON_SEND_PAGE', 20);		# kolko zapisov na stran pri pošiljanju
set_time_limit(2400); # 30 minut


class SurveyTelephone {
	private $sid;						# id ankete
	private $surveySettings;			# zakeširamo nastavitve ankete

	var $status_z = 5;       // cakaj 5 minut, ce je Zaseden
	var $status_n = 60;      // cakaj 60 minut, ce Ni odgovora
	var $status_d = 60;      // cakaj 60 minut, ce se ga prelozi
	var $max_calls = 10;     // klici najvec 10-krat

	var $call_order = 0;	 // vrstni red klicanja (0->nakljucno, 1->fiksno, 2->po abecedi padajoce, 3->po abecedi narascajoce)
	
	var $isAnketar = false;
	var $telephoneSprId = null;
	
	private $inv_variables = array('email','password','ime','priimek','naziv','telefon','drugo');
	private $inv_variables_link = array('email'=>'email','geslo'=>'password','ime'=>'firstname','priimek'=>'lastname','naziv'=>'salutation','telefon'=>'phone','drugo'=>'custom','last_status'=>'last_status','sent'=>'sent','responded'=>'responded','unsubscribed'=>'unsubscribed');
	
	
	function __construct($sid) {
		
		$this->sid = $sid;
		
		SurveyInfo::SurveyInit($this->sid);
		$this->surveySettings = SurveyInfo::getInstance()->getSurveyRow();
		SurveyDataSettingProfiles :: Init($this->sid);
		
		$sql = sisplet_query("SELECT * FROM srv_telephone_setting WHERE survey_id = '$this->sid'");
		$row = mysqli_fetch_array($sql);
		if (mysqli_num_rows($sql) > 0) {
			$this->status_z = $row['status_z'];
			$this->status_n = $row['status_n'];
			$this->status_d = $row['status_d'];
			$this->max_calls = $row['max_calls'];
			$this->call_order = $row['call_order'];
		}
		
		$this->telephoneSprId = $this->get_spr_telefon_id();
		# če spremenljivka telefon ne obstaja jo dodamo
		if ((int)$this->telephoneSprId == 0) {
			$sys = $this->addSystemVariables(array('inv_field_phone'));
			$this->telephoneSprId = $sys['telefon'];
		}
		
		$d = new Dostop();
		$this->isAnketar = $d->isAnketar();
			
		# počistimo polja
		if (isset($_POST['recipients_list']) && $_POST['recipients_list'] != null) {
			$_POST['recipients_list'] = mysql_real_unescape_string($_POST['recipients_list']);
		}
		if (isset($_POST['fields']) && $_POST['fields'] != null) {
			$_POST['fields'] = mysql_real_unescape_string($_POST['fields']);
		}
	}

	function ajax() {
		if (isset($_REQUEST['m']) && trim($_REQUEST['m']) != '') {
			$this->action($_REQUEST['m']);
		} else {
			echo 'Ajax error!';
			return 'Ajax error!';
		}
	}

	function action($action) {
		
		$NoNavi = false;
		if (isset($_POST['noNavi']) && $_POST['noNavi'] == 'true') {
			$NoNavi = true;
		}
		if ($NoNavi == false ) {
			echo '<div id="inv_top_navi">';
			$this->displayNavigation();
			echo '</div>';
		}

		if ($action == 'recipients_lists') {
			$this->recipientsLists();
		} else if ($action == 'view_recipients') {
			$this->viewRecipients();
		} else if ($action == 'start_call') {
			$this->startCall();
		} else if ($action == 'call') {
			$this->Call();
		} else if ($action == 'settings') {
			$this->settings();
		} else if ($action == 'settings_save') {
			$this->settingsSave();
			$this->settings();
		} else if ($action == 'calling_list') {
			$this->callingList();
		} else if ($action == 'setSortField') {
			$this->setSortField();
		} else if ($action == 'set_recipient_filter') {
			$this->setRecipientFilter();
		} else if ($action == 'addmark') {
			$this->addMark();
		} else if ($action == 'preveriStevilkeTimer') {
			$this->preveriStevilkeTimer();
		} else if ($action == 'setNextAction') {
			$this->setNextAction();
		} else if ($action == 'addRecipients') {
			$result = $this->addRecipients();
			# prikažemo napake
			$invalid_recipiens_array = $this->displayRecipentsErrors($result);
			$this->viewRecipients();
		} else if ($action == 'setUserComment') {
			$this->setUserComment();
		} else if ($action == 'deleteProfile') {
			$this->deleteProfile();
		} else if ($action == 'editProfile') {
			$this->editProfile();
		} else if ($action == 'updateProfile') {
			$this->updateProfile();
		} else if ($action == 'getProfileName') {
			$this->getProfileName();
		} else if ($action == 'saveNewProfile') {
			$this->saveNewProfile();
		} else if ($action == 'saveProfile') {
			$attributes = array();
			if (isset($_POST['pid'])) {
				$attributes['pid'] = $_POST['pid'];
			}
			if (isset($_POST['fields'])) {
				$attributes['fields'] = str_replace('inv_field_','',implode(',',$_POST['fields']));
			}
			if (isset($_POST['recipients_list'])) {
				$attributes['recipients'] = mysql_real_unescape_string($_POST['recipients_list']);
			}
			$this->saveProfile($attributes);
		} else if ($action == 'goToUser') {
			$this->goToUser();
		} else if ($action == 'startSurvey') {
			$this->startSurvey();
		} else if ($action == 'showPopupAddMarker') {
			$this->showPopupAddMarker();
		} else if ($action == 'undoLastStatus') {
			$this->undoLastStatus();
		} else {
			$this->showTelephoneStatus();
		}
	}

	function showTelephoneStatus() {
		global $lang, $site_url;
		
		# polovimo statuse respondentov
		# skreiramo query s katerim polovimo userje in pripadajoče sistemske podatke
		$str_fields[] = " u.id AS usr_id";
		$str_fields[] = " u.phone";
		$str_fields[] = " u.last_status as status";
		
		$str_joins[] = " srv_invitations_recipients as u";
		$str_conditions[] =  " u.ank_id = '$this->sid'";
		$str_conditions[] =  " u.deleted ='0'";
		$str_conditions[] =  " TRIM(u.phone) !=''";
		# pripravimo ostale join in condtion stavke
		
		# polovimo še iz baze klicev, vse trenutno začete klice
		$str_fields[] = " scc.rec_id AS sccusr";
		$str_joins[] = " LEFT OUTER JOIN (SELECT rec_id FROM srv_telephone_current) AS scc ON scc.rec_id = u.id";
		
		# polovimo še iz baze klicev, zadnji statuse
		$str_fields[] = " sch.status AS schstatus";
		$str_fields[] = " sch.user_id AS user_id";
		$str_fields[] = " sch.insert_time AS insert_time";
		$str_joins[] = " LEFT OUTER JOIN (SELECT rsch.status, rsch.rec_id, rsch.user_id, rsch.insert_time FROM srv_telephone_history AS rsch INNER JOIN (SELECT MAX(id) as iid, rec_id FROM srv_telephone_history GROUP BY rec_id) as insch ON insch.iid = rsch.id) AS sch ON sch.rec_id = u.id";
		
		# join za pregled po anketarjih
		$str_fields[] = " usr.id AS usrid";
		$str_joins[] = " LEFT OUTER JOIN (SELECT id FROM users) AS usr ON usr.id = sch.user_id";
		
		# zložimo query
		$str_qry_users = "SELECT ".implode(',', $str_fields)." FROM ".implode(' ',$str_joins)." WHERE ".implode(' AND',$str_conditions);
		
		# sortiramo po statusih
		$statusi = array();
		$statusi_anketar = array();
		$contacted = 0;

		$qry = sisplet_query($str_qry_users);
		if (!$qry) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($qry)) {
			
			// Filter na datum
			echo '<div id="phn_dashboard_date_filter">';	
			
			$date_from = '';
			if(isset($_GET['date_from']))
				$date_from = strtotime($_GET['date_from']);

			$date_to = '';
			if(isset($_GET['date_to']))
				$date_to = strtotime($_GET['date_to']);
			
			echo '<span class="spaceRight">'.$lang['s_from'].': <input type="text" name="tel_dash_dateFrom" id="tel_dash_dateFrom" value="'.($date_from == '' ? $date_from : date('d.m.Y', $date_from)).'" style="width:70px" ></span>';
			echo '<span class="spaceLeft spaceRight">'.$lang['s_to'].': <input type="text" name="tel_dash_dateTo" id="tel_dash_dateTo" value="'.($date_to == '' ? $date_to : date('d.m.Y', $date_to)).'" style="width:70px" ></span>';
			echo '<script type="text/javascript">';
			echo '$(document).ready(function() {' .
					'  $("#tel_dash_dateFrom, #tel_dash_dateTo").datepicker({
							showOtherMonths: true,
							selectOtherMonths: true,
							changeMonth: true,
							changeYear: true,
							dateFormat: "dd.mm.yy",
							showAnim: "slideDown",
							showOn: "button",
							buttonText: "",
							onSelect: function(selected, evnt) {
								tel_date_filter(); return false;
							}
						});' .
					'});';
			echo '</script>';		
			echo '<span class="spaceLeft"><a href="'.$site_url.'/admin/survey/index.php?anketa='.$this->sid.'&amp;a='.A_TELEPHONE.'&m=dashboard">'.$lang['srv_clear'].'</a></span>';	
			
			echo '</div>';
			
			
			while ($row = mysqli_fetch_assoc($qry)) {
				
				$date = strtotime($row['insert_time']);

				// Filtriramo po datumu ce imamo nastavljen filter
				if( ($date == null && $date_from == null && $date_to == null)
					|| ($date != null && ($date_from == null || $date >= $date_from) && ($date_to == null || $date <= $date_to))	){

					$statusi[$row['schstatus']] ++;
					$statusi_anketar[$row['usrid']] ++;
					
					if($row['schstatus'] != '')
						$contacted++;
				}
				
				$statusi_all ++;
			}

			//$contacted = (int)($statusi_all - $statusi['']);
			
			echo '<fieldset class="inv_fieldset"><legend>'.$lang['srv_telephone_dashboard_legend'].'</legend>';
			echo '<div class="inv_filedset_inline_div">';
			echo '<p>';
			echo '<table class="inv_dashboard_table">';
			echo '<tr>';
			echo '<th>'.$lang['srv_telephone_dashboard_all_respondents'].'</th>';
			echo '<th>'.(int)$statusi_all.'</th>';
			echo '<th>-</th>';
			echo '<th>100%</th>';
			echo '</tr>';
			# poslano enotam
			echo '<tr>';
			echo '<th>'.$lang['srv_telephone_dashboard_all_contacted'].'</th>';
			echo '<th>'.(int)$contacted .'</th>';
			echo '<th>'.((int)$contacted > 0 ? '100%' : '0%').'</th>';
			echo '<th>'.Common::formatNumber(( (int)$contacted > 0 ? (int)$contacted*100/(int)$statusi_all : 0),0,'','%').'</th>';
			echo '</tr>';
			foreach (array('R','Z','N','T','P','A','U','D') AS $st) {
				if ((int)$statusi[$st] > 0) {
					if ($st == 'U') {
						$css=' class="red"';
					} else {
						$css='';
					}
					echo '<tr>';
					echo '<td>'.$lang['srv_telephone_status_'.$st].'</td>';
					echo '<td'.$css.'>'.(int)$statusi[$st].'</td>';
					echo '<td'.$css.'>'.Common::formatNumber(((int)$statusi[$st] > 0 ? (int)$statusi[$st]*100/(int)$contacted : 0),0,'','%').'</td>';
					echo '<td'.$css.'>'.Common::formatNumber(((int)$statusi[$st] > 0 ? (int)$statusi[$st]*100/(int)$statusi_all : 0),0,'','%').'</td>';
					echo '</tr>';
				}
			}
			echo '</table>';
			echo '</p>';
			echo '</div>';
			echo '</fieldset>';
			
			$recipients_by_status = array();
			$recipients_by_status['contacted'] = $contacted;
			
			$sql_subStr =  "SELECT sir.id, sir.last_status, su.lurker, sth.insert_time FROM srv_invitations_recipients AS sir"
			." INNER join srv_user AS su ON sir.id = su.inv_res_id"
			." RIGHT JOIN srv_telephone_history as sth ON sth.rec_id = su.inv_res_id"
			//." WHERE sth.survey_id='$this->sid' AND sth.status IN ('U','A') AND sir.ank_id='$this->sid' AND sir.deleted ='0' AND su.ank_id='$this->sid' GROUP BY sth.rec_id";
			." WHERE sth.survey_id='$this->sid' AND sth.status IN ('U','A') AND sth.id=(SELECT MAX(id) FROM srv_telephone_history WHERE survey_id='$this->sid' AND status IN ('U','A') AND rec_id=sth.rec_id AND rec_id=sth.rec_id) AND sir.ank_id='$this->sid' AND sir.deleted ='0' AND su.ank_id='$this->sid' GROUP BY sth.rec_id";
			$sql_subQry = sisplet_query($sql_subStr);
		
			if (mysqli_num_rows($sql_subQry) > 0) {
				while (list($uid,$last_status,$lurker,$insert_time) = mysqli_fetch_row($sql_subQry)) {
					
					$date = strtotime($insert_time);
					
					// Filtriramo po datumu ce imamo nastavljen filter
					if( ($date == null && $date_from == null && $date_to == null)
						|| ($date != null && ($date_from == null || $date >= $date_from) && ($date_to == null || $date <= $date_to))	){
						
						switch ((int)$last_status) {
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
								if ((int)$lurker == 1) {
									# če je lurker
									$recipients_by_status['clicked'] ++;
								} else {
									$recipients_by_status['finished'] ++;
								}
							break;
							# 6 - končana
								case 6:
									$recipients_by_status['send'] ++;
									if ((int)$lurker == 1) {
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
				}
			#	var_dump($recipients_by_status);
				
				echo '<br/>';
				echo '<fieldset class="inv_fieldset"><legend>'.$lang['srv_telephone_dashboard_legend_finished'].'</legend>';
				echo '<div class="inv_filedset_inline_div">';
				echo '<table class="inv_dashboard_table">';
				echo '<tr>';
				echo '<th>'.$lang['srv_telephone_dashboard_all_contacted'].'</th>';
				echo '<th>'.(int)$recipients_by_status['contacted'].'</th>';
				echo '<th>-</th>';
				echo '<th>100%</th>';
				echo '</tr>';
				#popslano enotam
				echo '<tr>';
				echo '<th>'.$lang['srv_telephone_dashboard_all_started'].'</th>';
				echo '<th>'.(int)$recipients_by_status['send'].'</th>';
				echo '<th>'.((int)$recipients_by_status['send'] > 0 ? '100%' : '0%').'</th>';
				echo '<th>'.Common::formatNumberSimple(((int)$recipients_by_status['contacted'] > 0 ? (int)$recipients_by_status['send']*100/(int)$recipients_by_status['contacted'] : 0),0,'%').'</th>';
				echo '</tr>';
					
				#neodgovori
				echo '<tr>';
				echo '<td>'.$lang['srv_inv_dashboard_tbl_unanswered'].'</td>';
				$unanswered = ((int)$recipients_by_status['send']-(int)$recipients_by_status['clicked']-(int)$recipients_by_status['finished']);
				echo '<td>'.$unanswered.'</td>';
				echo '<td>'.Common::formatNumberSimple(((int)$recipients_by_status['send'] > 0 ? $unanswered*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
				echo '<td>'.Common::formatNumberSimple(((int)$recipients_by_status['contacted'] > 0 ? $unanswered*100/(int)$recipients_by_status['contacted'] : 0),0,'%').'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>'.$lang['srv_inv_dashboard_tbl_clicked'].'</td>';
				echo '<td>'.(int)$recipients_by_status['clicked'].'</td>';
				echo '<td>'.Common::formatNumberSimple(((int)$recipients_by_status['send'] > 0 ? (int)$recipients_by_status['clicked']*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
				echo '<td>'.Common::formatNumberSimple(((int)$recipients_by_status['contacted'] > 0 ? (int)$recipients_by_status['clicked']*100/(int)$recipients_by_status['contacted'] : 0),0,'%').'</td>';
				echo '</tr>';
					
				#če se slučajno pojavijo kaki neznani statusi
				if ((int)$recipients_by_status['unknown'] > 0) {
					echo '<tr>';
					echo '<td>'.$lang['srv_inv_dashboard_tbl_unknown'].'</td>';
					echo '<td>'.(int)$recipients_by_status['unknown'].'</td>';
					echo '<td>'.Common::formatNumberSimple(((int)$recipients_by_status['send'] > 0 ? (int)$recipients_by_status['unknown']*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
					echo '<td>'.Common::formatNumberSimple(((int)$recipients_by_status['contacted'] > 0 ? (int)$recipients_by_status['unknown']*100/(int)$recipients_by_status['contacted'] : 0),0,'%').'</td>';
					echo '</tr>';
				}
				echo '<tr>';
				echo '<td>'.$lang['srv_inv_dashboard_tbl_finished'].'</td>';
				echo '<td>'.(int)$recipients_by_status['finished'].'</td>';
				echo '<td class="red">'.Common::formatNumberSimple(((int)$recipients_by_status['send'] > 0 ? (int)$recipients_by_status['finished']*100/(int)$recipients_by_status['send'] : 0),0,'%').'</td>';
				echo '<td class="">'.Common::formatNumberSimple(((int)$recipients_by_status['contacted'] > 0 ? (int)$recipients_by_status['finished']*100/(int)$recipients_by_status['contacted'] : 0),0,'%').'</td>';
				echo '</tr>';
				echo '</table>';
				echo '<br>';
				echo '</div>';
				echo '</fieldset>';
			}
		
		
			// Sumarni pregled po anketarjih
			echo '<br/>';
			echo '<fieldset class="inv_fieldset"><legend>'.$lang['srv_telephone_dashboard_legend_anketar'].'</legend>';
			echo '<div class="inv_filedset_inline_div">';
			echo '<table class="inv_dashboard_table">';
			
			echo '<tr>';
			echo '<th>'.$lang['srv_telephone_dashboard_all_contacted'].'</th>';
			echo '<th>'.(int)$contacted .'</th>';
			echo '<th>'.((int)$contacted > 0 ? '100%' : '0%').'</th>';
			echo '</tr>';
			
			# Loop cez vse anketarje
			$d = new Dostop();
			$all_users = $d->getUsersDostop();
			foreach($all_users as $user){
				echo '<tr>';
				echo '<td>'.$user['name'].' '.$user['surname'].' <span class="gray">('.$user['email'].')</span></td>';
				echo '<td>'.(int)$statusi_anketar[$user['id']].'</td>';
				echo '<td>'.Common::formatNumberSimple(((int)$statusi_anketar[$user['id']] > 0 ? (int)$statusi_anketar[$user['id']]*100/(int)$recipients_by_status['contacted'] : 0),0,'%').'</td>';
				echo '</tr>';
			}

			echo '</table>';
			echo '<br>';
			echo '</div>';
			echo '</fieldset>';	
		
		} else {
			echo '<p>'.$lang['srv_telephone_no_respondents'].'</p>';
		}
	}

	function recipientsLists() {

		if (isset($_POST['pid'])) {
			$pid = (int)$_POST['pid'];
		} else {
			$pid = -1;
		}

		list($recipients_list,$fields) = $this->getRecipientsProfile($pid);
		$this->addRecipientsView($fields,$recipients_list);
	}

	function getRecipientsProfile($pid) {
		global $lang, $global_user_id;

		$fields = array();
		$recipients_list=null;
		session_start();
		# če ne obstaja začasen seznam ga naredimo (praznega) pid=-1
		if (!isset($_SESSION['phn_rec_profile'][$this->sid])) {
			$_SESSION['phn_rec_profile'][$this->sid] = array(
					'pid'=>-1,
					'name'=>$lang['srv_invitation_new_templist'],
					'fields'=>'phone',
					'respondents'=>'',
					'comment'=>$lang['srv_invitation_new_templist']);
		}

		#polovimo emaile in poljaiz seznama
		if ( $pid > 0) {
			# če imamo pid in je večji kot nič polovimo podatke iz tabele
			$sql_string = "SELECT fields,respondents FROM srv_invitations_recipients_profiles WHERE pid = '".$pid."'";
		$sql_query = sisplet_query($sql_string);
		$sql_row = mysqli_fetch_assoc($sql_query);
		if (trim($sql_row['respondents']) != '') {
			$recipients_list = explode("\n",trim($sql_row['respondents']));
		}
		$_fields = explode(",",$sql_row['fields']);
		if (count($_fields) > 0) {
			foreach ($_fields AS $field) {
				$fields[] =  'inv_field_'.$field;
			}
		}
		} else if ($pid == -1) {
			# začasen profil iz seje
			$_fields = explode(",",$_SESSION['phn_rec_profile'][$this->sid]['fields']);
			if (count($_fields) > 0) {
				foreach ($_fields AS $field) {
					$fields[] =  'inv_field_'.$field;
				}
			}
			if (trim($_SESSION['phn_rec_profile'][$this->sid]['respondents']) != '') {
				$recipients_list = explode("\n",trim($_SESSION['phn_rec_profile'][$this->sid]['respondents']));
			}

		} else {
			$recipients_list[] = '';
			$fields[]= 'inv_field_phone';
		}

		return array($recipients_list,$fields);

	}

	function addRecipientsView( $fields = array(), $recipients_list=null) {
		#prikažemo vmesnik za dodajanje respondentov
		global $lang;
		echo '<h2>'.$lang['srv_inv_add_recipients_heading'].'</h2>';

		echo '<div id="inv_import">';
		$this->displayAddRecipientsView($fields, $recipients_list);
		echo '</div>'; # id="inv_import"
	}

	function displayAddRecipientsView( $fields = array(), $recipients_list=null) {
		global $lang, $site_path, $site_url;
		$field_list = array();
		# odvisno od tipa sporočil prikažemo različna polja
		# Personalizirano e-poštno vabilo

		$default_fields = array(
				'inv_field_phone' => count($fields) == 0 ? 1 : 0,
				'inv_field_firstname' => 0,
				'inv_field_lastname' => 0,
				'inv_field_email' => 0,
				'inv_field_password' => 0,
				'inv_field_salutation' => 0,
				'inv_field_custom' => 0,
		);

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
		# profili respondentov
		echo '<div id="inv_recipients_profiles_holder">';

		echo '<span>'.$lang['srv_inv_recipient_select_list'].'</span><br/>';

		$this->listRecipientsProfiles();
		echo '</div>'; # id=inv_recipients_profiles_holder

		echo '<div id="inv_import_list_container">';

		$sqlSysMapping = sisplet_query("SELECT * FROM srv_invitations_mapping WHERE sid = '$this->sid'");
		if (mysqli_num_rows($sqlSysMapping) > 0) {
			$sysUserToAddQuery = sisplet_query("SELECT count(*) FROM srv_user where ank_id='".$this->sid."' AND inv_res_id IS NULL AND deleted='0'");
			list($sysUserToAdd) = mysqli_fetch_row($sysUserToAddQuery);
		}

		echo '<span><input name="inv_import_type" id="inv_import_type2" type="radio" value="2" checked="checked" autocomplete="off"><label for="inv_import_type2">'.$lang['srv_inv_recipiens_from_list'].'</label></span>';
		/*echo '<span><input name="inv_import_type" id="inv_import_type2" type="radio" value="2" onclick="inv_change_import_type();"'.($import_type == 2 ? ' checked="checked"' : '').' autocomplete="off"><label for="inv_import_type2">'.$lang['srv_inv_recipiens_from_list'].'</label></span>';
		echo '<span><input name="inv_import_type" id="inv_import_type1" type="radio" value="1" onclick="inv_change_import_type();"'.($import_type == 1 ? ' checked="checked"' : '').' autocomplete="off"><label for="inv_import_type1">'.$lang['srv_inv_recipiens_from_file'].'</label></span>';
		echo '<span><input name="inv_import_type" id="inv_import_type3" type="radio" value="3" onclick="inv_change_import_type();"'.($import_type == 3 ? ' checked="checked"' : '').' autocomplete="off"><label for="inv_import_type3">'.$lang['srv_inv_recipiens_from_system']
		.($sysUserToAdd > 0 ? ' ('.$sysUserToAdd.')' : '').'</label></span>';
		echo Help::display('inv_recipiens_from_system');*/
				
		echo '<br class="clr"/>';
		echo '<br class="clr"/>';
		if ($import_type == 3) {
			#$this->displayFromSystemVariables();
			$this->createSystemVariablesMapping();
		} else {

			# sporočilo za personalizirana e-vabila in respondente iz baze
			echo '<span class="inv_note">'.$lang['srv_inv_recipiens_field_note'].'</span>';
			echo '<br >';
			echo '<div id="inv_field_container">';
			echo '<ul class="connectedSortable">';
			$field_lang = array();
			if (count($field_list ) > 0) {
				foreach ($field_list AS $field => $checked) {
					# ali je polje izbrano ( če imamo personalizirano e-vabilo, moramo nujno imeti polje  email
					$is_selected = ($checked == 1 ) ? true : false;

					# če je polje obkljukano
					$css =  $is_selected ? ' class="inv_field_enabled"' : '';
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

			# iz seznama
			echo '<div id="inv_import_list"'.($import_type != 1 ? '' : ' class="hidden"').'>' ;
			echo '<span class="inv_note">'.$lang['srv_inv_recipiens_email_note'];
			echo '<br class="clr" /><span class="inv_sample" >';
			echo $lang['srv_inv_recipiens_sample'].'&nbsp;</span><span class="inv_sample">';
			echo $lang['srv_telephone_add_sample'];
			echo '</span>';
			echo '<br class="clr" />';
			echo '</span>';
			echo '<br class="clr" />'.$lang['srv_inv_recipiens_fields'].' <span id="inv_field_list" class="inv_type_0">';
			echo implode(',',$field_lang);
			echo '</span>';
			echo '<br class="clr" /><textarea id="inv_recipients_list" cols="50" rows="9" name="inv_recipients_list">';
			if (is_array($recipients_list) && count($recipients_list) > 0 ) {
				echo implode("\n",$recipients_list);
			}
			echo '</textarea>';
			echo '<br class="clr"/>';

			#podatki o profilu
			echo '<br class="clr"/>';
			$ppid = isset($_POST['pid']) ? (int)$_POST['pid'] : -1;

			echo '<span class="floatLeft">';
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
				echo '<div class="gray" title="'.strip_tags($sql_row['comment']).'" style="max-width:202px;">'.$lang['srv_inv_recipiens_list_comment'].trim (strip_tags($sql_row['comment'])).'</div>';

			} else {
				echo '<div class="gray">'.$lang['srv_inv_recipiens_temporary_list'].'</div>';
			}
			echo '</span>';

			# če že imamo prejemnike v bazi ponudimo gumb naprej
			echo '<span class="buttonwrapper floatRight spaceLeft" ><a class="ovalbutton ovalbutton_orange"  href="#" onclick="phn_add_recipients(); return false;"><span>'.$lang['srv_telephone_add'].'</span></a></span>';
			# če je začasen avtor, ne ponudimo shrani
			if ((int)$ppid != 0) {
				echo '<span class="buttonwrapper floatRight spaceLeft" ><a class="ovalbutton ovalbutton_gray"  href="#" onclick="phnSaveProfile(); return false;"><span>'.$lang['srv_telephone_save'].'</span></a></span>';
			}
			echo '<span class="buttonwrapper floatRight spaceLeft" ><a class="ovalbutton ovalbutton_gray"  href="#" onclick="phnGetNewProfileName(); return false;"><span>'.$lang['srv_telephone_save_new'].'</span></a></span>';
				
			echo '</div>';	# id=inv_import_list

		}
		echo '</div>'; # id=inv_import_list_container

		echo '<br class="clr"/>';
	}

	function listRecipientsProfiles() {
		global $lang, $global_user_id;
		$ppid = isset($_POST['pid']) ? (int)$_POST['pid'] : -1;
		# polovimo vse profile
		$array_profiles = array();
		session_start();
		# če obstaja seznam iz seje za to anketo
		if (isset($_SESSION['phn_rec_profile'][$this->sid])) {
			$array_profiles[-1] = array('name' => $_SESSION['phn_rec_profile'][$this->sid]['name']);
		}
		$array_profiles[0] = array('name' => $lang['srv_temp_profile_author']);
		$onlyThisSurvey = (isset($_SESSION['inv_rec_only_this_survey']) && $_SESSION['inv_rec_only_this_survey'] == false) ? 0 : 1;
		if ($onlyThisSurvey == 0) {
			#id-ji profilov do katerih lahko dostopamo
			$sql_string = "SELECT name, pid FROM srv_invitations_recipients_profiles WHERE uid in('".$global_user_id."') OR pid IN (SELECT DISTINCT pid FROM srv_invitations_recipients_profiles_access where uid = '$global_user_id')";
			$sql_query = sisplet_query($sql_string);
		} else {
			# 1
			$sql_string = "SELECT name, pid FROM srv_invitations_recipients_profiles WHERE from_survey = '$this->sid'";
			$sql_query = sisplet_query($sql_string);
		}

		$sql_query = sisplet_query($sql_string);
		while ($sql_row = mysqli_fetch_assoc($sql_query)) {
			$array_profiles[$sql_row['pid']] = array('name' => $sql_row['name']);
		}
		echo '<div id="phn_import_list_profiles">';

		echo '<ol>';
		foreach ($array_profiles AS $_pid => $profile) {
			echo '<li pid="'.$_pid.'" class="'.($ppid === $_pid ? 'active' : '').'"'
			.' onclick="showPhnList(\''.$_pid.'\');" >';
			echo $profile['name'];
			echo '</li>';
		}
		echo '</ol>';
		echo '</div>';
		echo '<br class="clr" />';
		if ((int)$ppid > 0) {
			# polovimo še ostale porfile
			$sql_string = "SELECT pid FROM srv_invitations_recipients_profiles WHERE pid='".(int)$ppid."' AND from_survey ='".$this->sid."' ";
			$sql_query = sisplet_query($sql_string);

			if (mysqli_num_rows($sql_query) > 0) {
				# če je iz iste ankete, potem lahko urejamo
				echo '<a href="#" onclick="phnDeleteProfile();" title="'.$lang['srv_inv_recipients_delete_profile'].'">'.$lang['srv_inv_recipients_delete_profile'].'</a><br/>';
				echo '<a href="#" onclick="phnEditProfile();" title="'.$lang['srv_inv_recipients_edit_profile'].'">'.$lang['srv_inv_recipients_edit_profile'].'</a><br/>';
				echo '<br class="clr"/>';
			}
		}

		echo '<br class="clr" />';
	}

	function displayNavigation() {
		global $lang;
		
		# če je anketar ne vidi navigacije
		if ($this->isAnketar == true) {
		
		} 
		else {
			
			if (!isset($_POST['noNavi']) || (isset($_POST['noNavi']) && $_POST['noNavi'] != 'true')) {
				$_sub_action = $_GET['m'];
				
				$active_step[] = array(1=>'',2=>'',3=>'',4=>'',5=>'',6=>'',7=>'');
				
				switch ($_sub_action) {
					case 'phn_status':
						$active_step['1'] = ' active';
						break;
						
					case 'recipients_lists':
						$active_step['2'] = ' active';
						break;
						
					case 'view_recipients':
					case 'addRecipients':
						$active_step['3'] = ' active';
						break;
						
					case 'goToUser':
					case 'start_call':
					case 'call':
						$active_step['4'] = ' active';
						break;
						
					case 'settings':
					case 'settings_save':
						$active_step['5'] = ' active';
						break;
						
					case 'calling_list':
						$active_step['6'] = ' active';
						break;
	
					default:
						$active_step['1'] = ' active';
						break;
				}
	
				$spaceChar = '&nbsp;';
				
				echo '<div class="phn_step_nav'.$active_step[1].'">';
				echo '<div class="phn_step">';
				echo '<a href="'.$this->addUrl('dashboard').'">';
				echo '<span class="label">'.$lang['srv_telephone_navi_dashboard'].'</span>';
				echo '</a>';
				echo '</div>';
				echo '</div>';
				
				if ($this->telephoneSprId) {
					#space
					echo '<div class="phn_space">&nbsp;</div>';
					$class_yellow = ' yellow';
					echo '<div class="phn_step_nav yellow">';
					
					#navigacija
					echo '<div class="phn_step'.$class_yellow.$active_step[2].'">';
					echo '<a href="'.$this->addUrl('recipients_lists').'">';
					echo '<span class="circle">1</span>';
					echo '<span class="label">'.$lang['srv_telephone_navi_add'].'</span>';
					echo '</a>';
					echo '</div>';
				
					echo '<div class="phn_step_space'.$class_yellow.'">'.$spaceChar.'</div>';
					
					echo '<div class="phn_step'.$class_yellow.$active_step[3].'">';
					echo '<a href="'.$this->addUrl('view_recipients').'">';
					echo '<span class="circle">2</span>';
					echo '<span class="label">'.$lang['srv_telephone_navi_view'].'</span>';
					if ($disabled == false) {
						echo '</a>';
					}
					echo '</div>';
					
					echo '<div class="phn_step_space'.$class_yellow.$css_disabled.'">'.$spaceChar.'</div>';
					
					echo '<div class="phn_step'.$class_yellow.$css_disabled.$active_step[4].'">';
					echo '<a href="'.$this->addUrl('start_call').'">';
					echo '<span class="circle">3</span>';
					echo '<span class="label" >'.$lang['srv_telephone_navi_start_call'].'</span>';
					echo '</a>';
					echo '</div>';
					echo '</div>';
					
					#space
					echo '<div class="phn_space">&nbsp;</div>';
					
					// Cakalni seznam
					echo '<div class="phn_step_nav'.$active_step[6].'" style="margin-right:20px; width:90px;">';
					echo '<div class="phn_step">';
					echo '<a href="'.$this->addUrl('calling_list').'">';
					echo '<span class="label">'.$lang['srv_telephone_navi_waiting_list'].'</span>';
					echo '</a>';
					echo '</div>';
					echo '</div>';
				}

				// Nastavitve
				echo '<div class="phn_step_nav'.$active_step[5].'">';
				echo '<div class="phn_step">';
				echo '<a href="'.$this->addUrl('settings').'">';
				echo '<span class="label">'.$lang['srv_telephone_navi_settings'].'</span>';
				echo '</a>';
				echo '</div>';
				echo '</div>';
				
				echo '<br class="clr" />';
				echo '<br class="clr" />';
			}
		}
	}

	// Seznam stevilk ki so v vrsti za klicanje
	function callingList() {
		global $lang;
		global $site_url;
		
		// Najprej cakalna vrsta (stevilke ki se bodo prikazale kasneje)
		$this->waitingList();
		
		echo '<br /><br />';
		
		// Se vrsta stevilk ki se trenutno klicejo
		echo '<h2>'.$lang['srv_telephone_navi_calling_list'].'</h2>';
		
		// Dobimo seznam vseh ki se niso bili klicani
		$toCall = $this->getAllNumbers();	
		
		
		if (count($toCall) > 0) {
		
			# Katera polja prikazujemo v seznamu prejemnikov
			$fields = array();
			$default_fields = array(
					'phone' => 1,
					'email' => 0,
					'password' => 0,
					'firstname' => 0,
					'lastname' => 0,
					'salutation' => 0,
					'custom' => 0,
			);
			
			$sql_select_fields = array();
			$fields['ps_icon'] = 1;
			$fields['schstatus'] = 1;
			
			$sql_select_fields[] = " i.last_status as ps_icon";
			$sql_select_fields[] = " i.last_status as last_status";
			$sql_select_fields[] = " scc.rec_id AS sccusr";
			$sql_select_fields[] = " scs.call_time AS schedule_call_time";
			# polovimo še iz baze klicev, zadnji statuse
			$sql_select_fields[] = " sch.status AS schstatus";
			$sql_select_fields[] = " scm.comment AS comment";
			
			foreach($toCall as $usr_id => $phone){
				
				#koliko zapisov bi morali prikazovati
				$sql_query_filterd_all = sisplet_query("SELECT i.* FROM srv_invitations_recipients AS i WHERE i.ank_id='".$this->sid."' AND i.id='".$usr_id."'");
				
				$sql_row = mysqli_fetch_assoc($sql_query_filterd_all);
				foreach ($default_fields AS $key => $value) {
					# če polje še ni dodano in če ni prazno, ga dodamo
					if ($fields[$key] == 0 && isset($sql_row[$key]) && trim($sql_row[$key]) != '') {
						$fields[$key] = 1;
						$sql_select_fields[] = 'i.'.$key;
					}
				}
			}

			$fields['schedule_call_time'] = 1;
			$fields['last_status'] = 1;
			$fields['comment'] = 1;
			$fields['date_inserted'] = 1;
			$fields['usr_email'] = 1;
			$fields['list_id'] = 1;
			
			# dodamo še ostala polja
			$sql_select_fields[] = 'i.last_status';
			$sql_select_fields[] = 'i.date_inserted';
			$sql_select_fields[] = 'i.list_id';
			$sql_select_fields[] = 'usrs.email AS usr_email';

			#dodamo paginacijo in poiščemo zapise
			$page = isset($_GET['page']) ? $_GET['page'] : '1';
			$limit_start = ($page * REC_ON_PAGE) - REC_ON_PAGE;
			

			# polovimo sezname
			$lids = array();
			$sql_string_users = "SELECT i.list_id FROM srv_invitations_recipients AS i WHERE i.ank_id = '".$this->sid."' AND i.deleted = '0' AND TRIM(phone) !='' GROUP BY i.list_id ORDER BY i.id LIMIT $limit_start,".REC_ON_PAGE.'';
			$sql_query_users = sisplet_query($sql_string_users);
			while ($row_users = mysqli_fetch_assoc($sql_query_users)) {
				$lids[] = $row_users['list_id'];
			}

			#seznami
			$lists = array();
			$lists['-1'] = array('name'=>$lang['srv_invitation_new_templist']);
			$lists['0'] = array('name'=>$lang['srv_invitation_new_templist_author']);
			if (count($lids) > 0 ) {
				$sql_string_lists = "SELECT name, pid from srv_invitations_recipients_profiles WHERE pid IN(".implode(',',$lids).") ";
				$sql_query_lists = sisplet_query($sql_string_lists);
				while ($row_lists = mysqli_fetch_assoc($sql_query_lists)) {
					$lists[$row_lists['pid']] = array('name'=>$row_lists['name']);
				}
			}

			// Stevilo stevilk v vrsti
			echo '<div id="srv_invitation_note">'.$lang['srv_telephone_calling_list_count'].': '.count($toCall).'</div>';
			
			echo '<br class="clr"/>';
			
			// Izrisemo tabelo
			echo '<div style="display:inline-block;">';
			echo '<table id="tbl_recipients_list" class="phone">';
			
			// Header tabele
			echo '<tr>';
			echo '<th class="tbl_icon">&nbsp;</th>';
			foreach ($fields AS $fkey =>$field) {
				if ($field == 1) {
					if ($fkey == 'sent' || $fkey == 'responded' || $fkey == 'unsubscribed'){
						#echo '<th class="anl_ac tbl_icon_'.$fkey.' inv_'.$fkey.'_1" title="'.$lang['srv_inv_recipients_'.$fkey].'">&nbsp;</th>';
						echo '<th class="anl_ac tbl_icon_'.$fkey.'" title="'.$lang['srv_telephone_respondents_'.$fkey].'">'.$lang['srv_telephone_respondents_'.$fkey].'</th>';
					} else if ($fkey == 'ps_icon' ) {
						echo '<th class="anl_ac tbl_icon" title="'.$lang['srv_telephone_respondents_'.$fkey].'">'.$lang['srv_telephone_respondents_'.$fkey].'</th>';
					} else if ($fkey == 'date_inserted' || $fkey == 'schedule_call_time' ) {
						echo '<th class="anl_ac tbl_date" title="'.$lang['srv_telephone_respondents_'.$fkey].'">'.$lang['srv_telephone_respondents_'.$fkey].'</th>';
					} else if ($fkey == 'schstatus' ) {
						echo '<th class="anl_ac" title="'.$lang['srv_telephone_respondents_'.$fkey].'">'.$lang['srv_telephone_respondents_'.$fkey].'</th>';
					} else if ($fkey == 'last_status' ) {
						echo '<th class="anl_ac" title="'.$lang['srv_inv_recipients_last_status'].'">'.$lang['srv_inv_recipients_last_status'].'</th>';
					} else {
						echo '<th title="'.$lang['srv_telephone_respondents_'.$fkey].'">'.$lang['srv_telephone_respondents_'.$fkey].'</th>';
					}
				}
			}
			echo '</tr>';	
			
			// Podatki tabele
			$cnt = 1;
			foreach($toCall as $usr_id => $phone){

				$sql_query_filterd = sisplet_query("SELECT i.id, ".implode(',',$sql_select_fields)." FROM srv_invitations_recipients AS i"
				# polovimo še iz baze klicev, vse trenutno zacete klice
				." LEFT OUTER JOIN (SELECT rec_id FROM srv_telephone_current) AS scc ON scc.rec_id = i.id"
				
				# polovimo še iz baze klicev, vse trenutno zacete klice
				." LEFT OUTER JOIN (SELECT rec_id, call_time FROM srv_telephone_schedule) AS scs ON scs.rec_id = i.id"
				
				# polovimo še iz baze klicev, zadnji statuse
				." LEFT OUTER JOIN (SELECT rsch.status, rsch.rec_id FROM srv_telephone_history AS rsch INNER JOIN (SELECT MAX(id) as iid, rec_id FROM srv_telephone_history GROUP BY rec_id) as insch ON insch.iid = rsch.id) AS sch ON sch.rec_id = i.id"
				
				# polovimo še morebitne komentarje
				." LEFT OUTER JOIN (SELECT rec_id,comment FROM srv_telephone_comment) AS scm ON scm.rec_id = i.id"

				# polovimo še kdo je dodal
				." LEFT OUTER JOIN (SELECT id, email FROM users) AS usrs ON usrs.id = i.inserted_uid"
				
				." WHERE i.ank_id='".$this->sid."' AND i.id='".$usr_id."'");
				if (!$sql_query_filterd) echo mysqli_error($GLOBALS['connect_db']);
			
				$sql_row = mysqli_fetch_assoc($sql_query_filterd);
				
				
				$icon = ' phn_ico_status_go';
				switch ($sql_row['schstatus']) {
					case 'U':
						$icon = ' phn_ico_status';
						break;
					case 'R':
						$icon = ' phn_ico_status_key';
						break;
					case 'N': #ga ni
					case 'Z': #zaseden
					case 'T': #zmenjen
					case 'D': #prelozen
						$icon = ' phn_ico_status_error';
						break;
							
					default:
						;
						break;
				}
				# če je odjavljen damo isto ikonco za zaklepanje
				if ((int)$row_users['unsubscribed'] == 1) {
					$icon = ' phn_ico_status_key';
				}
				
				echo '<tr>';
				echo '<td>'.$cnt.'</td>';
				foreach ($fields AS $fkey =>$field) {
					if ($field == 1) {
						switch ($fkey) {
							
							case 'ps_icon':
								echo '<td class="anl_ac'.$icon.'" onclick="phnGoToUser(\''.$sql_row['id'].'\')">';
								echo '&nbsp;';
								echo '</td>';
								break;
							case 'last_status':
								echo '<td class="ovwerflowHidden" title="'.$lang['srv_userstatus_'.$sql_row[$fkey]].'">';
								echo '('.$sql_row[$fkey].') - '.$lang['srv_userstatus_'.$sql_row[$fkey]].'</td>';
								break;
							case 'inserted_uid':
								echo '<td>'.$users[$row_users[$fkey]]['email'].'</td>';
								break;
							case 'comment':
								echo '<td class="tbl_inv_left ovwerflowHidden" style="max-width:100px;" title="'.str_replace("<br>","\n",strip_tags($sql_row['comment'])).'">';
								echo substr(strip_tags($sql_row['comment']),0,50).'</td>';
								break;
							case 'date_inserted':
							case 'schedule_call_time':
								echo '<td class="tbl_inv_left ovwerflowHidden" title="'.$sql_row[$fkey].'">';
								echo $sql_row[$fkey].'</td>';
								break;
							case 'inserted_uid':
								echo '<td>'.$users[$sql_row[$fkey]]['email'].'</td>';
								break;
							case 'schstatus':
								echo '<td class="tbl_inv_left ovwerflowHidden" title="'.$lang['srv_telephone_status_'.$sql_row[$fkey]].'">';
								echo $lang['srv_telephone_status_'.$sql_row[$fkey]];
								echo '</td>';
								break;
							case 'email':
								echo '<td>';
								echo '<span class="as_link" onclick="showRecipientTracking(\''.$sql_row['id'].'\'); return false;">';
								if ($filter != '') {
									echo $this->hightlight($sql_row[$fkey],$filter);
								} else {
									echo $sql_row[$fkey];
								}
								echo '</span>';
								echo '</td>';
								break;
							case 'list_id':
								echo '<td>';
								if ((int)$sql_row[$fkey] > 0) {
									if ($lists[$sql_row[$fkey]]['name'] != '') {
										echo '<a href="#" onclick="$(\'#globalSettingsInner\').load(\'ajax.php?t=invitations&a=use_recipients_list\', {anketa:srv_meta_anketa_id, pid:'.(int)$sql_row[$fkey].' });">'.$lists[$sql_row[$fkey]]['name'].'</a>';
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
				
				$cnt++;
			}
			
			echo '</table>';
			echo '</div>';
		}
		else {
			echo $lang['srv_telephone_calling_list_empty'].'<br class="clr">';
		}
		
		echo '<br /><br /><br />';
	}
	
	// Seznam stevilk ki so v vrsti za klicanje
	function waitingList() {
		global $lang;
		global $site_url;
		
		echo '<h2>'.$lang['srv_telephone_navi_waiting_list'].'</h2>';

		
		// Dobimo seznam vseh ki so odlozeni (bodo klicani kasneje)
		$toCall = $this->getAllNumbersWaiting();	
		
		
		if (count($toCall) > 0) {
		
			# Katera polja prikazujemo v seznamu prejemnikov
			$fields = array();
			$default_fields = array(
					'phone' => 1,
					'email' => 0,
					'password' => 0,
					'firstname' => 0,
					'lastname' => 0,
					'salutation' => 0,
					'custom' => 0,
			);
			
			$sql_select_fields = array();
			$fields['ps_icon'] = 1;
			$fields['schstatus'] = 1;
			
			$sql_select_fields[] = " i.last_status as ps_icon";
			$sql_select_fields[] = " i.last_status as last_status";
			$sql_select_fields[] = " scc.rec_id AS sccusr";
			$sql_select_fields[] = " scs.call_time AS schedule_call_time";
			# polovimo še iz baze klicev, zadnji statuse
			$sql_select_fields[] = " sch.status AS schstatus";
			$sql_select_fields[] = " scm.comment AS comment";
			
			foreach($toCall as $usr_id => $phone){
				
				#koliko zapisov bi morali prikazovati
				$sql_query_filterd_all = sisplet_query("SELECT i.* FROM srv_invitations_recipients AS i WHERE i.ank_id='".$this->sid."' AND i.id='".$usr_id."'");
				
				$sql_row = mysqli_fetch_assoc($sql_query_filterd_all);
				foreach ($default_fields AS $key => $value) {
					# če polje še ni dodano in če ni prazno, ga dodamo
					if ($fields[$key] == 0 && isset($sql_row[$key]) && trim($sql_row[$key]) != '') {
						$fields[$key] = 1;
						$sql_select_fields[] = 'i.'.$key;
					}
				}
			}

			$fields['schedule_call_time'] = 1;
			$fields['last_status'] = 1;
			$fields['comment'] = 1;
			$fields['date_inserted'] = 1;
			$fields['usr_email'] = 1;
			$fields['list_id'] = 1;
			
			# dodamo še ostala polja
			$sql_select_fields[] = 'i.last_status';
			$sql_select_fields[] = 'i.date_inserted';
			$sql_select_fields[] = 'i.list_id';
			$sql_select_fields[] = 'usrs.email AS usr_email';

			#dodamo paginacijo in poiščemo zapise
			$page = isset($_GET['page']) ? $_GET['page'] : '1';
			$limit_start = ($page * REC_ON_PAGE) - REC_ON_PAGE;
			

			# polovimo sezname
			$lids = array();
			$sql_string_users = "SELECT i.list_id FROM srv_invitations_recipients AS i WHERE i.ank_id = '".$this->sid."' AND i.deleted = '0' AND TRIM(phone) !='' GROUP BY i.list_id ORDER BY i.id LIMIT $limit_start,".REC_ON_PAGE.'';
			$sql_query_users = sisplet_query($sql_string_users);
			while ($row_users = mysqli_fetch_assoc($sql_query_users)) {
				$lids[] = $row_users['list_id'];
			}

			#seznami
			$lists = array();
			$lists['-1'] = array('name'=>$lang['srv_invitation_new_templist']);
			$lists['0'] = array('name'=>$lang['srv_invitation_new_templist_author']);
			if (count($lids) > 0 ) {
				$sql_string_lists = "SELECT name, pid from srv_invitations_recipients_profiles WHERE pid IN(".implode(',',$lids).") ";
				$sql_query_lists = sisplet_query($sql_string_lists);
				while ($row_lists = mysqli_fetch_assoc($sql_query_lists)) {
					$lists[$row_lists['pid']] = array('name'=>$row_lists['name']);
				}
			}

			// Stevilo stevilk v vrsti
			echo '<div id="srv_invitation_note">'.$lang['srv_telephone_waiting_list_count'].': '.count($toCall).'</div>';
			
			echo '<br class="clr"/>';
			
			// Izrisemo tabelo
			echo '<div style="display:inline-block;">';
			echo '<table id="tbl_recipients_list" class="phone">';
			
			// Header tabele
			echo '<tr>';
			echo '<th class="tbl_icon">&nbsp;</th>';
			foreach ($fields AS $fkey =>$field) {
				if ($field == 1) {
					if ($fkey == 'sent' || $fkey == 'responded' || $fkey == 'unsubscribed'){
						#echo '<th class="anl_ac tbl_icon_'.$fkey.' inv_'.$fkey.'_1" title="'.$lang['srv_inv_recipients_'.$fkey].'">&nbsp;</th>';
						echo '<th class="anl_ac tbl_icon_'.$fkey.'" title="'.$lang['srv_telephone_respondents_'.$fkey].'">'.$lang['srv_telephone_respondents_'.$fkey].'</th>';
					} else if ($fkey == 'ps_icon' ) {
						echo '<th class="anl_ac tbl_icon" title="'.$lang['srv_telephone_respondents_'.$fkey].'">'.$lang['srv_telephone_respondents_'.$fkey].'</th>';
					} else if ($fkey == 'date_inserted' || $fkey == 'schedule_call_time' ) {
						echo '<th class="anl_ac tbl_date" title="'.$lang['srv_telephone_respondents_'.$fkey].'">'.$lang['srv_telephone_respondents_'.$fkey].'</th>';
					} else if ($fkey == 'schstatus' ) {
						echo '<th class="anl_ac" title="'.$lang['srv_telephone_respondents_'.$fkey].'">'.$lang['srv_telephone_respondents_'.$fkey].'</th>';
					} else if ($fkey == 'last_status' ) {
						echo '<th class="anl_ac" title="'.$lang['srv_inv_recipients_last_status'].'">'.$lang['srv_inv_recipients_last_status'].'</th>';
					} else {
						echo '<th title="'.$lang['srv_telephone_respondents_'.$fkey].'">'.$lang['srv_telephone_respondents_'.$fkey].'</th>';
					}
				}
			}
			echo '</tr>';	
			
			// Podatki tabele
			$cnt = 1;
			foreach($toCall as $usr_id => $phone){

				$sql_query_filterd = sisplet_query("SELECT i.id, ".implode(',',$sql_select_fields)." FROM srv_invitations_recipients AS i"
				# polovimo še iz baze klicev, vse trenutno zacete klice
				." LEFT OUTER JOIN (SELECT rec_id FROM srv_telephone_current) AS scc ON scc.rec_id = i.id"
				
				# polovimo še iz baze klicev, vse trenutno zacete klice
				." LEFT OUTER JOIN (SELECT rec_id, call_time FROM srv_telephone_schedule) AS scs ON scs.rec_id = i.id"
				
				# polovimo še iz baze klicev, zadnji statuse
				." LEFT OUTER JOIN (SELECT rsch.status, rsch.rec_id FROM srv_telephone_history AS rsch INNER JOIN (SELECT MAX(id) as iid, rec_id FROM srv_telephone_history GROUP BY rec_id) as insch ON insch.iid = rsch.id) AS sch ON sch.rec_id = i.id"
				
				# polovimo še morebitne komentarje
				." LEFT OUTER JOIN (SELECT rec_id,comment FROM srv_telephone_comment) AS scm ON scm.rec_id = i.id"

				# polovimo še kdo je dodal
				." LEFT OUTER JOIN (SELECT id, email FROM users) AS usrs ON usrs.id = i.inserted_uid"
				
				." WHERE i.ank_id='".$this->sid."' AND i.id='".$usr_id."'");
				if (!$sql_query_filterd) echo mysqli_error($GLOBALS['connect_db']);
			
				$sql_row = mysqli_fetch_assoc($sql_query_filterd);
				
				
				$icon = ' phn_ico_status_go';
				switch ($sql_row['schstatus']) {
					case 'U':
						$icon = ' phn_ico_status';
						break;
					case 'R':
						$icon = ' phn_ico_status_key';
						break;
					case 'N': #ga ni
					case 'Z': #zaseden
					case 'T': #zmenjen
					case 'D': #prelozen
						$icon = ' phn_ico_status_error';
						break;
							
					default:
						;
						break;
				}
				# če je odjavljen damo isto ikonco za zaklepanje
				if ((int)$row_users['unsubscribed'] == 1) {
					$icon = ' phn_ico_status_key';
				}
				
				echo '<tr>';
				echo '<td>'.$cnt.'</td>';
				foreach ($fields AS $fkey =>$field) {
					if ($field == 1) {
						switch ($fkey) {
							
							case 'ps_icon':
								echo '<td class="anl_ac'.$icon.'" onclick="phnGoToUser(\''.$sql_row['id'].'\')">';
								echo '&nbsp;';
								echo '</td>';
								break;
							case 'last_status':
								echo '<td class="ovwerflowHidden" title="'.$lang['srv_userstatus_'.$sql_row[$fkey]].'">';
								echo '('.$sql_row[$fkey].') - '.$lang['srv_userstatus_'.$sql_row[$fkey]].'</td>';
								break;
							case 'inserted_uid':
								echo '<td>'.$users[$row_users[$fkey]]['email'].'</td>';
								break;
							case 'comment':
								echo '<td class="tbl_inv_left ovwerflowHidden" style="max-width:50px;" title="'.str_replace("<br>","\n",strip_tags($sql_row['comment'])).'">';
								echo substr(strip_tags($sql_row['comment']),0,50).'</td>';
								break;
							case 'date_inserted':
							case 'schedule_call_time':
								echo '<td class="tbl_inv_left ovwerflowHidden" title="'.$sql_row[$fkey].'">';
								echo $sql_row[$fkey].'</td>';
								break;
							case 'inserted_uid':
								echo '<td>'.$users[$sql_row[$fkey]]['email'].'</td>';
								break;
							case 'schstatus':
								echo '<td class="tbl_inv_left ovwerflowHidden" title="'.$lang['srv_telephone_status_'.$sql_row[$fkey]].'">';
								echo $lang['srv_telephone_status_'.$sql_row[$fkey]];
								echo '</td>';
								break;
							case 'email':
								echo '<td>';
								echo '<span class="as_link" onclick="showRecipientTracking(\''.$sql_row['id'].'\'); return false;">';
								if ($filter != '') {
									echo $this->hightlight($sql_row[$fkey],$filter);
								} else {
									echo $sql_row[$fkey];
								}
								echo '</span>';
								echo '</td>';
								break;
							case 'list_id':
								echo '<td>';
								if ((int)$sql_row[$fkey] > 0) {
									if ($lists[$sql_row[$fkey]]['name'] != '') {
										echo '<a href="#" onclick="$(\'#globalSettingsInner\').load(\'ajax.php?t=invitations&a=use_recipients_list\', {anketa:srv_meta_anketa_id, pid:'.(int)$sql_row[$fkey].' });">'.$lists[$sql_row[$fkey]]['name'].'</a>';
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
				
				$cnt++;
			}
			
			echo '</table>';
			echo '</div>';
		}
		else {
			echo $lang['srv_telephone_waiting_list_empty'].'<br class="clr">';
		}
	}
	
	
	
	function addUrl($what) {
		global $site_url;
	
		if ($what == null || trim($what) == '') {
			$what = 'add_recipients_view';
		}
		if ($what == 'clear_current') {
			$what = 'call&n=clear_current';
		}
		$url = $site_url . 'admin/survey/index.php?anketa='.$this->sid.'&amp;a='.A_TELEPHONE.'&amp;m='.$what;
	
		return $url;
	}
	
	function viewRecipients() {
		global $lang, $site_url;

		#preglej prejemnike
		#echo '<h2>'.$lang['srv_inv_heading_step2'].$lang['srv_inv_edit_recipients_heading'].'</h2>';
		echo '<h2>'.$lang['srv_inv_edit_recipients_heading'].'</h2>';
		#polovimo prejemnike ki ne želijo prejemati obvestil

		# nastavimo filter
		session_start();
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
			. ")";
			
			$mysql_filter2 = " AND ("
			. "i.email LIKE '%".$filter."%'"
			. "OR i.firstname LIKE '%".$filter."%'"
			. "OR i.lastname LIKE '%".$filter."%'"
			. "OR i.password LIKE '%".$filter."%'"
			. "OR i.salutation LIKE '%".$filter."%'"
			. "OR i.phone LIKE '%".$filter."%'"
			. "OR i.custom LIKE '%".$filter."%'"
			. "OR scm.comment LIKE '%".$filter."%'"
			. ")";
		}

		# preštejemo koliko imamo vseh respondentov in koliko jih je brez e-maila
		$sql_string_all = "SELECT id FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted = '0' AND TRIM(phone) !=''";
		$sql_query_all = sisplet_query($sql_string_all);
		$count_all = mysqli_num_rows($sql_query_all);

		$sql_string_withot_email = "SELECT count(*) FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted = '0' AND email IS NULL AND sent='0'";
		$sql_query_without_email = sisplet_query($sql_string_withot_email);
		$sql_row_without_email  = mysqli_fetch_row($sql_query_without_email);
		$count_without_email = $sql_row_without_email[0];

		#koliko zapisov bi morali prikazovati
		$sql_string_filterd_all = "SELECT i.* FROM srv_invitations_recipients AS i WHERE i.ank_id = '".$this->sid."' AND i.deleted = '0' AND TRIM(phone) !='' ".$mysql_filter." ORDER BY i.id";
		$sql_query_filterd_all = sisplet_query($sql_string_filterd_all);
		$filtred_all = mysqli_num_rows($sql_query_filterd_all);

		$fields = array();
		# Katera polja prikazujemo v seznamu prejemnikov
		$default_fields = array(
				'phone' => 1,
				'email' => 0,
				'password' => 0,
				'firstname' => 0,
				'lastname' => 0,
				'salutation' => 0,
				'custom' => 0,
		);
		
		$sql_select_fields = array();
		$fields['ps_icon'] = 1;
		$fields['schstatus'] = 1;
		
		$sql_select_fields[] = " i.last_status as ps_icon";
		$sql_select_fields[] = " i.last_status as last_status";
		$sql_select_fields[] = " scc.rec_id AS sccusr";
		$sql_select_fields[] = " scs.call_time AS schedule_call_time";
		# polovimo še iz baze klicev, zadnji statuse
		$sql_select_fields[] = " sch.status AS schstatus";
		$sql_select_fields[] = " scm.comment AS comment";
		
		# pogledamo katera polja dejansko prikazujemo
	
		while ($sql_row = mysqli_fetch_assoc($sql_query_filterd_all)) {
			foreach ($default_fields AS $key => $value) {
				# če polje še ni dodano in če ni prazno, ga dodamo
				if ($fields[$key] == 0 && isset($sql_row[$key]) && trim($sql_row[$key]) != '') {
					$fields[$key] = 1;
					$sql_select_fields[] = 'i.'.$key;
				}
			}
		}

		$fields['schedule_call_time'] = 1;
		$fields['last_status'] = 1;
		$fields['comment'] = 1;
		$fields['date_inserted'] = 1;
		$fields['usr_email'] = 1;
		$fields['list_id'] = 1;
		
		# dodamo še ostala polja
		$sql_select_fields[] = 'i.last_status';
		$sql_select_fields[] = 'i.date_inserted';
		$sql_select_fields[] = 'i.list_id';
		$sql_select_fields[] = 'usrs.email AS usr_email';
		#štetje vabil
		#$fields['count_inv'] = 1;
		#$sql_select_fields[] = 'count(siar.arch_id) AS count_inv';

		#dodamo paginacijo in poiščemo zapise
		$page = isset($_GET['page']) ? $_GET['page'] : '1';
		$limit_start = ($page*REC_ON_PAGE)-REC_ON_PAGE;
		
		#dodamo sortiranje
		$sort_string = $this->getSortString();
			
		#		$sql_string_filterd = "SELECT i.id, ".implode(',',$sql_select_fields)." FROM srv_invitations_recipients AS i LEFT JOIN srv_invitations_archive_recipients AS siar ON (i.id = siar.rec_id) WHERE i.ank_id = '".$this->sid."' AND i.deleted = '0'".$mysql_filter.' GROUP BY siar.rec_id '.$sort_string." LIMIT $limit_start,".REC_ON_PAGE;
		$sql_string_filterd = "SELECT i.id, ".implode(',',$sql_select_fields)." FROM srv_invitations_recipients AS i"
			# polovimo še iz baze klicev, vse trenutno zacete klice
			." LEFT OUTER JOIN (SELECT rec_id FROM srv_telephone_current) AS scc ON scc.rec_id = i.id"
			
			# polovimo še iz baze klicev, vse trenutno zacete klice
			." LEFT OUTER JOIN (SELECT rec_id, call_time FROM srv_telephone_schedule) AS scs ON scs.rec_id = i.id"
			
			# polovimo še iz baze klicev, zadnji statuse
			." LEFT OUTER JOIN (SELECT rsch.status, rsch.rec_id FROM srv_telephone_history AS rsch INNER JOIN (SELECT MAX(id) as iid, rec_id FROM srv_telephone_history GROUP BY rec_id) as insch ON insch.iid = rsch.id) AS sch ON sch.rec_id = i.id"
			
			# polovimo še morebitne komentarje
			." LEFT OUTER JOIN (SELECT rec_id,comment FROM srv_telephone_comment) AS scm ON scm.rec_id = i.id"

			# polovimo še kdo je dodal
			." LEFT OUTER JOIN (SELECT id, email FROM users) AS usrs ON usrs.id = i.inserted_uid"
			
			." WHERE i.ank_id = '".$this->sid."' AND i.deleted = '0' AND TRIM(phone) !='' ".$mysql_filter2.' '.$sort_string." LIMIT $limit_start,".REC_ON_PAGE;
		$sql_query_filterd = sisplet_query($sql_string_filterd);
		if (!$sql_query_filterd) echo mysqli_error($GLOBALS['connect_db']);
		
		# polovimo sezname
		$lids = array();
		$sql_string_users = "SELECT i.list_id FROM srv_invitations_recipients AS i WHERE i.ank_id = '".$this->sid."' AND i.deleted = '0' AND TRIM(phone) !=''".$mysql_filter." GROUP BY i.list_id ORDER BY i.id LIMIT $limit_start,".REC_ON_PAGE.'';
		$sql_query_users = sisplet_query($sql_string_users);
		while ($row_users = mysqli_fetch_assoc($sql_query_users)) {
			$lids[] = $row_users['list_id'];
		}

		#seznami
		$lists = array();
		$lists['-1'] = array('name'=>$lang['srv_invitation_new_templist']);
		$lists['0'] = array('name'=>$lang['srv_invitation_new_templist_author']);
		if (count($lids) > 0 ) {
			$sql_string_lists = "SELECT name, pid from srv_invitations_recipients_profiles WHERE pid IN(".implode(',',$lids).") ";
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

			// dodamo filtriranje		
			echo '<div id="inv_rec_filter">';
			echo '<label>'.$lang['srv_invitation_recipients_filter'].'</label> <input id="tel_rec_filter_value" type="text" onchange="tel_filter_recipients(); return false;" value="'.$_SESSION['inv_filter']['value'].'">';		
			echo '</div>';
			
			echo '<form id="frm_inv_rec_export" name="resp_uploader" method="post" autocomplete="off">';
			echo '<input type="hidden" name="anketa" id="anketa" value="'.$this->sid.'">';
			echo '<input type="hidden" name="noNavi" id="noNavi" value="true">';
			
			//echo '<br class="clr"/>';
			
			echo '<div id="srv_invitation_note" style="padding-top:8px;">';
			if ($filter != '') {
				echo '<span class="red strong">';
				printf($lang['srv_inv_list_no_recipients_filter'],$filter);
				echo '</span>';
			}
			else{
				if ($count_all > 0 && mysqli_num_rows($sql_query_filterd) != $count_all )
					echo $lang['srv_invitation_num_respondents_filtred'].(int)mysqli_num_rows($sql_query_filterd);
				else
					echo $lang['srv_invitation_num_respondents'].(int)$this->count_all;
			}
			echo '</div>';
			
			echo '<br class="clr"/>';
			
			if (mysqli_num_rows($sql_query_filterd) > 0 && $count_all > 0) {
			
				$this->displayPagination($filtred_all);
				echo '<div style="display:inline-block;">';

				echo '<table id="tbl_recipients_list" class="phone">';
				echo '<tr>';
				# checkbox
				echo '<th class="tbl_icon" colspan="3" >&nbsp;</th>';
				/*
				 * 				# uredi
				echo '<th class="tbl_liks">&nbsp;</th>';
				# izbrisi
				echo '<th class="tbl_liks">&nbsp;</th>';

				*/

				foreach ($fields AS $fkey =>$field) {
					if ($field == 1) {
						if ($fkey == 'sent' || $fkey == 'responded' || $fkey == 'unsubscribed'){
							#echo '<th class="anl_ac tbl_icon_'.$fkey.' inv_'.$fkey.'_1" title="'.$lang['srv_inv_recipients_'.$fkey].'">&nbsp;</th>';
							echo '<th'.$this->addSortField($fkey).' class="anl_ac tbl_icon_'.$fkey.'" title="'.$lang['srv_telephone_respondents_'.$fkey].'">'.$lang['srv_telephone_respondents_'.$fkey].$this->addSortIcon($fkey).'</th>';
						} else if ($fkey == 'ps_icon' ) {
							echo '<th'.$this->addSortField($fkey).' class="anl_ac tbl_icon" title="'.$lang['srv_telephone_respondents_'.$fkey].'">'.$lang['srv_telephone_respondents_'.$fkey].$this->addSortIcon($fkey).'</th>';
						} else if ($fkey == 'date_inserted' || $fkey == 'schedule_call_time' ) {
							echo '<th'.$this->addSortField($fkey).' class="anl_ac tbl_date pointer" title="'.$lang['srv_telephone_respondents_'.$fkey].'">'.$lang['srv_telephone_respondents_'.$fkey].$this->addSortIcon($fkey).'</th>';
						} else if ($fkey == 'schstatus' ) {
							echo '<th'.$this->addSortField($fkey).' class="anl_ac pointer" title="'.$lang['srv_telephone_respondents_'.$fkey].'">'.$lang['srv_telephone_respondents_'.$fkey].$this->addSortIcon($fkey).'</th>';
						} else if ($fkey == 'last_status' ) {
							echo '<th'.$this->addSortField($fkey).' class="anl_ac pointer" title="'.$lang['srv_inv_recipients_last_status'].'">'.$lang['srv_inv_recipients_last_status'].$this->addSortIcon($fkey).'</th>';
						} else {
							echo '<th'.$this->addSortField($fkey).' class="pointer" title="'.$lang['srv_telephone_respondents_'.$fkey].'">'.$lang['srv_telephone_respondents_'.$fkey].$this->addSortIcon($fkey).'</th>';
						}
					}
				}
				echo '</tr>';
				while ($sql_row = mysqli_fetch_assoc($sql_query_filterd)) {
					$icon = ' phn_ico_status_go';
					switch ($sql_row['schstatus']) {
						case 'U':
							$icon = ' phn_ico_status';
							break;
						case 'R':
							$icon = ' phn_ico_status_key';
							break;
						case 'N': #ga ni
						case 'Z': #zaseden
						case 'T': #zmenjen
						case 'D': #prelozen
							$icon = ' phn_ico_status_error';
							break;
								
						default:
							;
							break;
					}
					# če je odjavljen damo isto ikonco za zaklepanje
					if ((int)$row_users['unsubscribed'] == 1) {
						$icon = ' phn_ico_status_key';
					}
					
					echo '<tr>';
					# checkbox

					echo '<td><input type="checkbox" name="inv_rids[]" value="'.$sql_row['id'].'"></td>';
					#izbriši
					echo '<td class="tbl_inv_left"><span class="faicon delete_circle icon-orange_link" onclick="deleteRecipient_confirm(\''.$sql_row['id'].'\'); return false;" title="'.$lang['srv_inv_list_profiles_delete'].'"></span></td>';
					#uredi
					echo '<td class="tbl_inv_left"><span class="faicon quick_edit edit smaller icon-as_link" onclick="editRecipient(\''.$sql_row['id'].'\'); return false;" title="'.$lang['srv_inv_list_profiles_edit'].'"></span></td>';

					foreach ($fields AS $fkey =>$field) {
						if ($field == 1) {
							switch ($fkey) {
								
								case 'ps_icon':
									echo '<td class="anl_ac'.$icon.'" onclick="phnGoToUser(\''.$sql_row['id'].'\')">';
									echo '&nbsp;';
									echo '</td>';
									break;
								case 'last_status':
									echo '<td title="'.$lang['srv_userstatus_'.$sql_row[$fkey]].'">';
									echo '('.$sql_row[$fkey].') - '.$lang['srv_userstatus_'.$sql_row[$fkey]].'</td>';
									break;
								case 'inserted_uid':
									echo '<td>'.$users[$row_users[$fkey]]['email'].'</td>';
									break;
								case 'comment':
									echo '<td class="tbl_inv_left ovwerflowHidden" style="max-width:100px;" title="'.str_replace("<br>","\n",strip_tags($sql_row['comment'])).'">';
									/*echo substr(strip_tags($sql_row['comment']),0,50).'</td>';*/
									if ($filter != '') {
										echo $this->hightlight(substr(strip_tags($sql_row['comment']),0,50),$filter);
									} else {
										echo substr(strip_tags($sql_row['comment']),0,50);
									}
									echo '</td>';
									break;
								case 'date_inserted':
								case 'schedule_call_time':
									echo '<td class="tbl_inv_left" title="'.$sql_row[$fkey].'">';
									echo $sql_row[$fkey].'</td>';
									break;
								case 'inserted_uid':
									echo '<td>'.$users[$sql_row[$fkey]]['email'].'</td>';
									break;
								case 'schstatus':
									echo '<td class="tbl_inv_left" title="'.$lang['srv_telephone_status_'.$sql_row[$fkey]].'">';
									echo $lang['srv_telephone_status_'.$sql_row[$fkey]];
									echo '</td>';
									break;
								case 'email':
									echo '<td>';
									echo '<span class="as_link" onclick="showRecipientTracking(\''.$sql_row['id'].'\'); return false;">';
									if ($filter != '') {
										echo $this->hightlight($sql_row[$fkey],$filter);
									} else {
										echo $sql_row[$fkey];
									}
									echo '</span>';
									echo '</td>';
									break;
								case 'list_id':
									echo '<td>';
									if ((int)$sql_row[$fkey] > 0) {
										if ($lists[$sql_row[$fkey]]['name'] != '') {
											echo '<a href="#" onclick="$(\'#globalSettingsInner\').load(\'ajax.php?t=invitations&a=use_recipients_list\', {anketa:srv_meta_anketa_id, pid:'.(int)$sql_row[$fkey].' });">'.$lists[$sql_row[$fkey]]['name'].'</a>';
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
				echo '&nbsp;&nbsp;<a href="#" onClick="inv_recipients_form_action(\'delete\');"><span class="faicon delete_circle icon-orange" title="'.$lang['srv_invitation_recipients_delete_selected'].'"/></span>&nbsp;'.$lang['srv_invitation_recipients_delete_selected'].'</a>';
				echo '&nbsp;&nbsp;<a href="#" onClick="inv_recipients_form_action(\'export\');"><span class="sprites xls delete" style="height:14px; width:16px;" title="'.$lang['srv_invitation_recipients_export_selected'].'"/></span>&nbsp;'.$lang['srv_invitation_recipients_export_selected'].'</a>';
				echo '</div>';
				echo '</div>';

			} else {
				echo $lang['srv_inv_list_no_recipients_filtred'].'<br class="clr">';
			}
			echo '</form>';
		} else {
			echo $lang['srv_inv_list_no_recipients'].'<br class="clr">';
		}
		
		echo '<br /><br />';
	}
	
	
	/**
    * @desc prikaze prvo stran z linkom na zacni
    */
	function startCall() {
        global $lang;
        
        #preverimo koliko številk imamo na voljo
        $numbersAvailable = $this->getAllNumbers();
        
        # preverimo aktivnost ankete
        if ($this->surveySettings['active'] != 1) {
        	echo $lang['srv_inv_error9'];
        	if ($this->isAnketar == true) {
        		return;
        	}
        }
        
        if (count($numbersAvailable) > 0 ) {
        	echo '<h2>'.$lang['srv_telephone_call_available'];
        	echo ' '.count($numbersAvailable);
        	echo '</h2>';
        	echo '<h2><a href="index.php?anketa='.$this->sid.'&a='.A_TELEPHONE.'&m=call">'.$lang['srv_call_start'].'</a></h2>';
        } else {
        	$this->getNextTimeCall();     	
        }
	}

	/**
	 * @desc zacne s klicanjem telefonskih stevilk
	 */
	function Call() {
        global $site_root, $global_user_id;
		
        $schedule = false;
		
        // stevilka je izbrana - klicana
        if ($_GET['usr_id'] != '' && (int)$_GET['usr_id'] > 0) {
        	$usr_id = (int)$_GET['usr_id'];
        	
            // zbrisemo cookie za izpolnjevanje -- da ne dobi od prejsnjega, ce je nehu nekje vmes
            setcookie('survey-'.$this->sid, '', time()-3600, str_replace($_SERVER['DOCUMENT_ROOT'], '', $site_root).'main/survey/');
            
            sisplet_query("DELETE FROM srv_telephone_schedule WHERE rec_id = '$usr_id'");
            sisplet_query("INSERT INTO srv_telephone_current (rec_id, user_id, started_time) VALUES ('$usr_id', '".$global_user_id."', NOW())"); 
        } 
		else {
			// Po novem moramo pri kliku "klici drugo" pobrisati current iz baze
			if(isset($_GET['n']) && $_GET['n'] == 'clear_current')
				sisplet_query("DELETE srv_telephone_current FROM srv_telephone_current, srv_invitations_recipients 
								WHERE srv_telephone_current.user_id='".$global_user_id."' AND srv_telephone_current.rec_id = srv_invitations_recipients.id AND srv_invitations_recipients.ank_id='".$this->sid."'");
		
        	#uporabnik ni izbran določimo izberemo ga naklučno oziroma če smo dogovorjeni
        	$row = $this->get_next_number();
        	$usr_id = $row['usr_id'];
        	$schedule = (int)$row['schedule'] == 1 ? true : false;
        }

        if ((int)$usr_id > 0) {
	        # preverimo ali je uporabnik že začel klicat
        	$openedSurvey = ($_GET['usr_id'] != '' && (int)$_GET['usr_id'] > 0) ? true : false;
        	$this->userCallToShow($usr_id,$openedSurvey,$schedule);
			
			// Po novem ze ob prikazu stevilke zaklenemo respondenta (da ga ne moreta 2 anketarja hkrati poklicati preden odpreta anketo)
			$sql = sisplet_query("SELECT * FROM srv_telephone_current WHERE rec_id='".$usr_id."' AND user_id='".$global_user_id."' AND started_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)");
			if(mysqli_num_rows($sql) == 0)
				sisplet_query("INSERT INTO srv_telephone_current (rec_id, user_id, started_time) VALUES ('$usr_id', '".$global_user_id."', NOW())");
        } 
		else {
        	$this->getNextTimeCall();
        }
    }
	
	function settings() {
		global $lang, $site_url;
        
		echo '<fieldset><legend>'.$lang['settings'].'</legend>';
		
        echo '<form id="phn_settings" method="post">';
        echo '<p>'.$lang['srv_call_settings_z'].' <input type="text" name="status_z" value="'.$this->status_z.'" style="width:20px" /> '.$lang['srv_call_settings_min'].'</p>';
        echo '<p>'.$lang['srv_call_settings_n'].' <input type="text" name="status_n" value="'.$this->status_n.'" style="width:20px" /> '.$lang['srv_call_settings_min'].'</p>';
        echo '<p>'.$lang['srv_call_settings_d'].' <input type="text" name="status_d" value="'.$this->status_d.'" style="width:20px" /> '.$lang['srv_call_settings_min'].'</p>';
        echo '<p>'.$lang['srv_call_settings_max'].' <input type="text" name="max_calls" value="'.$this->max_calls.'" style="width:20px" /> '.$lang['srv_call_settings_calls'].'</p>';
		
		// Vrstni red klicanja
        echo '<p>';
		echo $lang['srv_call_settings_call_order'].': <select name="call_order">';
		echo '<option value="0" '.($this->call_order==0 ? ' selected="selected"' : '').'>'.$lang['srv_call_settings_call_order_0'].'</option>';
		echo '<option value="1" '.($this->call_order==1 ? ' selected="selected"' : '').'>'.$lang['srv_call_settings_call_order_1'].'</option>';
		echo '<option value="2" '.($this->call_order==2 ? ' selected="selected"' : '').'>'.$lang['srv_call_settings_call_order_2'].'</option>';
		echo '<option value="3" '.($this->call_order==3 ? ' selected="selected"' : '').'>'.$lang['srv_call_settings_call_order_3'].'</option>';
		echo '</select>';
		echo '</p>';
		
        #$str = "SELECT sd.uid, u.name, u.surname, u.email FROM srv_dostop AS sd LEFT JOIN users AS u ON sd.uid = u.id  WHERE sd.ank_id ='$this->sid' AND FIND_IN_SET('phone',sd.dostop )>0";
        #polovimo vse userje ki imajo dostop
        echo '<p>';
        echo $lang['srv_telephone_settings_access_list'];
        printf ($lang['srv_telephone_settings_access_list_link'],$site_url . 'admin/survey/index.php?anketa='.$this->sid.'&amp;a='.A_DOSTOP);
        echo '<table style="margin-left:20px;"><tr><th>Anketar</th><th class="anl_al">Ime, priimek, email</th></tr>';
        $str = "SELECT sd.uid,FIND_IN_SET('phone',sd.dostop ) AS anketar, u.name, u.surname, u.email FROM srv_dostop AS sd LEFT JOIN users AS u ON sd.uid = u.id WHERE sd.ank_id ='$this->sid'";
        $qry = sisplet_query($str);
        while ($sql_row = mysqli_fetch_assoc($qry)) {
        	$avtor = array();
        	if (trim($sql_row['name'])) {
        		$avtor[] = trim ($sql_row['name']);
        	}
        	if (trim($sql_row['surname'])) {
        		$avtor[] = trim ($sql_row['surname']);
        	}
        	if (trim($sql_row['email'])) {
        		$avtor[] = iconv("iso-8859-2", "utf-8",'<span class="gray">('.trim ($sql_row['email']).')</span>');
        	}
        
        	echo '<tr><td>';
        	echo '<input name="dostop['.$sql_row['uid'].']" type="checkbox"'.($sql_row['anketar']>0?' checked="checked"':'').'>';
        	echo '</td><td>';
        	if ( count($avtor) > 0 ) {
        		echo implode(', ',$avtor);
        	}
        	echo '</td></tr>';
        }
        echo '</table>';
        echo '</p>';
        echo '<span class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_orange"  href="#" onclick="phn_settings_save(); return false;">'.$lang['srv_telephone_settings_save'].'</a></span>';
        echo '</form>';
		echo '<br /><br />';
		
		echo '</fieldset>';
		
		echo '<div id="success_save" style="display:none;">'.$lang['srv_success_save'].'</div>';
	}
	
	function settingsSave() {
		$this->status_z = (int)$_POST[status_z];
		$this->status_n = (int)$_POST[status_n];
		$this->status_d = (int)$_POST[status_d];
		$this->max_calls = (int)$_POST[max_calls];
		$this->call_order = (int)$_POST[call_order];
		
		sisplet_query("REPLACE srv_telephone_setting (survey_id, status_z, status_n, status_d, max_calls, call_order) VALUES ('$this->sid', '$this->status_z ', '$this->status_n', '$this->status_d', '$this->max_calls', '$this->call_order')");
		#dodamo dostop za anketarja
		
		if (count($_POST['dostop']) > 0) {
			$uids = array();
			foreach ($_POST['dostop'] AS $uid => $on) {
				$uids[] = $uid;
			}
			
			#najprej odstranimo vsem kateri niso v post
			$str_remove = "UPDATE srv_dostop SET dostop = TRIM(BOTH ',' FROM REPLACE(CONCAT(',', dostop, ','),CONCAT(',', 'phone', ','), ','))"
			." WHERE ank_id='$this->sid' AND uid NOT IN(".implode(",",$uids).")";
			$s1 = sisplet_query($str_remove);
			if (!$s1) echo 'err_phn_dostop_001'.mysqli_error($GLOBALS['connect_db']);
			
			#nato dodamo vsem ki so v post.
			;
			$str_update = "UPDATE srv_dostop SET dostop = CONCAT(dostop, ',phone')"
			." WHERE ank_id='$this->sid' AND uid IN(".implode(",",$uids).")";
			$s2 = sisplet_query($str_update);
			if (!$s2) echo 'err_phn_dostop_002'.mysqli_error($GLOBALS['connect_db']);
					
		}
	}
	
	/**
	 * @desc vrne ID spremenljivke telefon
	 */
	function get_spr_telefon_id () {
	
		$sql = sisplet_query("SELECT srv_spremenljivka.id
				FROM srv_spremenljivka, srv_grupa
				WHERE srv_spremenljivka.variable = 'telefon'
				AND srv_spremenljivka.sistem = '1'
				AND srv_spremenljivka.gru_id = srv_grupa.id
				AND srv_grupa.ank_id = '$this->sid'
				");
		$row = mysqli_fetch_array($sql);
		return $row['id'];
	}
	
	
	function addSortField($field){
		$type = 'ASC';
		session_start();
		if ($_SESSION['phn_rec_sort_field'] == $field) {
			if ($_SESSION['phn_rec_sort_type'] == 'DESC') {
				$type = 'ASC';
			} else {
				$type = 'DESC';
			}
		} else {
			$type = 'ASC';
		}
		return ' onclick="phn_set_sort_field(\''.$field.'\',\''.$type.'\');" ';
	}
	function addSortIcon($field){
		session_start();
		if ($_SESSION['phn_rec_sort_field'] == $field) {
			if ($_SESSION['phn_rec_sort_type'] == 'DESC') {
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
			$_SESSION['phn_rec_sort_field'] = trim($_POST['field']);
		} else {
			$_SESSION['phn_rec_sort_field'] = 'date_inserted';
		}
		if (isset($_POST['type']) && trim($_POST['type']) != '') {
			$_SESSION['phn_rec_sort_type'] = trim($_POST['type']);
		} else {
			$_SESSION['phn_rec_sort_type'] = 'ASC';
		}
		session_commit();
	}
	
	function getSortString() {
		session_start();
		$sort_string = ' ORDER BY i.id ASC';
		if (isset($_SESSION['phn_rec_sort_field']) && trim($_SESSION['phn_rec_sort_field']) != '') {
			$prefix = 'i.';
			if ($_SESSION['phn_rec_sort_field'] == 'count_inv'
					 || $_SESSION['phn_rec_sort_field'] == 'ps_icon' 
					 || $_SESSION['phn_rec_sort_field'] == 'schedule_call_time'
					 || $_SESSION['phn_rec_sort_field'] == 'comment'
					 || $_SESSION['phn_rec_sort_field'] == 'usr_email'
					 || $_SESSION['phn_rec_sort_field'] == 'schstatus') {
				$prefix = '';
			}
			$sort_string = ' ORDER BY '.$prefix.trim($_SESSION['phn_rec_sort_field']);
			if ($_SESSION['phn_rec_sort_type'] == 'DESC') {
				$sort_string .= ' DESC, i.id DESC';
			} else {
				$sort_string .= ' ASC, i.id ASC';
			}
		}
		return $sort_string ;
		/*
		session_start();
		$sort_string = ' ORDER BY u.id';
	
		if (isset($_SESSION['phn_rec_sort_field']) && trim($_SESSION['phn_rec_sort_field']) != '') {
			#$prefix = 'std_'.$_SESSION['phn_rec_sort_field'].'.';
			$sort_string = ' ORDER BY '.$prefix.trim($_SESSION['phn_rec_sort_field']);
			if ($_SESSION['phn_rec_sort_type'] == 'DESC') {
				$sort_string .= ' DESC';
			} else {
				$sort_string .= ' ASC';
			}
		}
		return $sort_string;
		*/
	}
	
	/**
	 * @desc preveri ce je kaksna nova stevilka (tudi prek ajaxa)
	 */
	function preveri_stevilke () {
		global $lang;
	
		$row = $this->get_next_number();
	
		if ($row['usr_id'] > 0) {
			echo '<h2 style="text-align:center">'.$lang['srv_call_next'].':</h2><br />';
			echo '<h2 style="text-align:center">'.$row['phone'].' - ';
			echo '<a href="index.php?anketa='.$this->sid.'&a='.A_TELEPHONE.'&m=call&usr_id='.$row['usr_id'].'">'.$lang['srv_call_call'].'</a>';
			echo '</h2>';
		} else {
			echo '<h2 style="text-align:center">'.$lang['srv_call_nonumber'].'</h2><br />';
	
			$sql1 = sisplet_query("SELECT srv_telephone_schedule.*
					FROM srv_telephone_schedule, srv_invitations_recipients
					WHERE
						srv_invitations_recipients.deleted ='0' AND
						srv_telephone_schedule.rec_id = srv_invitations_recipients.id AND
						srv_invitations_recipients.ank_id = '$this->sid' AND
						srv_telephone_schedule.call_time > NOW() AND
						srv_telephone_schedule.rec_id NOT IN (
							SELECT srv_telephone_current.rec_id
							FROM srv_telephone_current
							WHERE srv_telephone_current.started_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
						) AND
						srv_telephone_schedule.rec_id NOT IN (
							SELECT srv_telephone_history.rec_id
							FROM srv_telephone_history
							GROUP BY srv_telephone_history.rec_id
							HAVING COUNT(srv_telephone_history.id) >= '$this->max_calls'
						)
						ORDER BY srv_telephone_schedule.call_time ASC LIMIT 1
					");
			$row1 = mysqli_fetch_array($sql1);
			if (mysqli_num_rows($sql1) > 0)
				echo '<p style="text-align:center">'.$lang['srv_call_nextcall'].': <b>'.datetime($row1['call_time']).'</b></p>';
			else
				echo '<p style="text-align:center">'.$lang['srv_call_nonumbers'].'</p>';

			echo '<script>preveriStevilkeTimer();</script>';
		}
	}
	
	/**
	 * @desc vrne naslednji row s stevilko za klic
	 */
	function get_next_number () {
		global $global_user_id;
		
		# najprej pogledamo ce je kaksen v srv_telephone_current - mor ga obvezno razresiti ker je zaklenjen
		$sel = "SELECT srv_invitations_recipients.id AS usr_id, TRIM(srv_invitations_recipients.phone) AS text"
			  ." FROM srv_telephone_current LEFT JOIN srv_invitations_recipients ON srv_telephone_current.rec_id = srv_invitations_recipients.id "
			  ." WHERE srv_invitations_recipients.ank_id ='$this->sid'"
			  ." AND srv_invitations_recipients.deleted ='0'"
			  ." AND srv_telephone_current.user_id = $global_user_id"
			  ." AND srv_telephone_current.rec_id = srv_invitations_recipients.id"
			  ." AND TRIM(srv_invitations_recipients.phone) != ''"
			  ." AND srv_telephone_current.started_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)"
			  ." ORDER BY srv_invitations_recipients.id ASC"
			  ." LIMIT 1";
		$sql = sisplet_query($sel);
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0) {
			return mysqli_fetch_array($sql);
		}
		
		// najprej selectamo, tiste ki so zmenjeni po urniku
		$sel = "SELECT srv_invitations_recipients.id AS usr_id, TRIM(srv_invitations_recipients.phone) AS text, '1' AS schedule"
				." FROM srv_telephone_schedule LEFT JOIN srv_invitations_recipients ON srv_telephone_schedule.rec_id = srv_invitations_recipients.id "		
				." WHERE srv_invitations_recipients.ank_id ='$this->sid'"
				." AND srv_invitations_recipients.deleted ='0'"
				." AND srv_telephone_schedule.rec_id = srv_invitations_recipients.id"
				." AND TRIM(srv_invitations_recipients.phone) != ''"
				." AND srv_telephone_schedule.call_time <= NOW()"
				." AND srv_invitations_recipients.id NOT IN ("
				." SELECT srv_telephone_current.rec_id"
				." FROM srv_telephone_current"
				." WHERE srv_telephone_current.started_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)"
				." ) AND"
					." srv_invitations_recipients.id NOT IN ("
					." SELECT DISTINCT srv_telephone_history.rec_id"
					." FROM srv_telephone_history"
					." WHERE srv_telephone_history.status = 'R' OR srv_telephone_history.status = 'U'"
				." ) AND"
					." srv_invitations_recipients.id NOT IN ("
					." SELECT srv_telephone_history.rec_id"
					." FROM srv_telephone_history"
					." GROUP BY srv_telephone_history.rec_id"
					." HAVING COUNT(srv_telephone_history.id) >= '$this->max_calls'"
				." )"
				." ORDER BY srv_telephone_schedule.call_time ASC"
				." LIMIT 1";
		$sql = sisplet_query($sel);
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0) {
			return mysqli_fetch_array($sql);
		}

		// ce ni nobenega na urniku pa nadaljujemo z random izbiro ostalih stevilk

		/* fetch random from available
		 * exclude from fetch:
		*  - all phones which are currently active
		*  - all phones which are on the schedule for the future
		*  - all phones which have "R" or "U" status
		*  - all phones which are called more that "max_calls" setting
		*/
		
		// Sortiranje
		if($this->call_order == 1)
			$order_by = ' srv_invitations_recipients.id ASC';
		elseif($this->call_order == 2)
			$order_by = ' srv_invitations_recipients.firstname ASC, srv_invitations_recipients.lastname ASC, srv_invitations_recipients.email ASC, srv_invitations_recipients.id ASC';
		elseif($this->call_order == 3)
			$order_by = ' srv_invitations_recipients.firstname DESC, srv_invitations_recipients.lastname DESC, srv_invitations_recipients.email DESC, srv_invitations_recipients.id DESC';
		else
			$order_by = ' RAND()';
		
		$sel = "SELECT srv_invitations_recipients.id AS usr_id, TRIM(srv_invitations_recipients.phone) AS text, '0' AS schedule"
			." FROM srv_invitations_recipients"
			." WHERE srv_invitations_recipients.ank_id ='$this->sid'"
			." AND srv_invitations_recipients.deleted ='0'"
			." AND TRIM(srv_invitations_recipients.phone) != ''"
			." AND srv_invitations_recipients.id NOT IN ("
				." SELECT srv_telephone_current.rec_id"
				." FROM srv_telephone_current"
				." WHERE srv_telephone_current.started_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)"
			." ) AND"
			." srv_invitations_recipients.id NOT IN ("
				." SELECT srv_telephone_schedule.rec_id"
				." FROM srv_telephone_schedule"
				." WHERE srv_telephone_schedule.call_time > NOW()"
			." ) AND"
			." srv_invitations_recipients.id NOT IN ("
				." SELECT srv_telephone_history.rec_id"
				." FROM srv_telephone_history"
				." WHERE srv_telephone_history.status = 'R' OR srv_telephone_history.status = 'U'"
			." ) AND"
			." srv_invitations_recipients.id NOT IN ("
				." SELECT srv_telephone_history.rec_id"
				." FROM srv_telephone_history"
				." GROUP BY srv_telephone_history.rec_id"
				." HAVING COUNT(srv_telephone_history.id) >= '$this->max_calls'"
			." )"
			." ORDER BY ".$order_by.""
			." LIMIT 1";
		$sql = sisplet_query($sel);
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		return mysqli_fetch_array($sql);
	}
	
	/**
	 * @desc vrne vse stevilke ki se trenutno klicejo (s pravim vrstnim redom)
	 */
	function getAllNumbers () {
		global $global_user_id;
		
		$result = array();
		
		# najprej pogledamo ce je kaksen v srv_telephone_current - mor ga obvezno razresiti ker je zaklenjen
		$sel = "SELECT srv_invitations_recipients.id, TRIM(srv_invitations_recipients.phone) AS phone"
			  ." FROM srv_telephone_current LEFT JOIN srv_invitations_recipients ON srv_telephone_current.rec_id = srv_invitations_recipients.id "
			  ." WHERE srv_invitations_recipients.ank_id ='$this->sid'"
			  ." AND srv_invitations_recipients.deleted ='0'"
			  ." AND srv_telephone_current.user_id = $global_user_id"
			  ." AND srv_telephone_current.rec_id = srv_invitations_recipients.id"
			  ." AND TRIM(srv_invitations_recipients.phone) != ''"
			  ." AND srv_telephone_current.started_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)"
			  ." ORDER BY srv_invitations_recipients.id ASC";
		
		$sql = sisplet_query($sel);
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0) {
			while ( list($id,$phone) = mysqli_fetch_row($sql)) {
				$result[$id] = $phone;
			}
		}
		
		# najprej selectamo, tiste ki so zmenjeni po urniku
		$sel = "SELECT srv_invitations_recipients.id, TRIM(srv_invitations_recipients.phone) AS phone"
			  ." FROM srv_telephone_schedule LEFT JOIN srv_invitations_recipients ON srv_telephone_schedule.rec_id = srv_invitations_recipients.id "
			  ." WHERE srv_invitations_recipients.ank_id ='$this->sid'"
			  ." AND srv_invitations_recipients.deleted ='0'"
			  ." AND srv_telephone_schedule.rec_id = srv_invitations_recipients.id"
			  ." AND TRIM(srv_invitations_recipients.phone) != ''"
			  ." AND srv_telephone_schedule.call_time <= NOW()"
			  ." AND srv_invitations_recipients.id NOT IN ("
		  			." SELECT srv_telephone_current.rec_id"
					." FROM srv_telephone_current"
					." WHERE srv_telephone_current.started_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)"
			  ." ) AND"
			  ." srv_invitations_recipients.id NOT IN ("
					." SELECT DISTINCT srv_telephone_history.rec_id"
					." FROM srv_telephone_history"
					." WHERE srv_telephone_history.status = 'R' OR srv_telephone_history.status = 'U'"
			  ." ) AND"
			  ." srv_invitations_recipients.id NOT IN ("
					." SELECT srv_telephone_history.rec_id"
					." FROM srv_telephone_history"
					." GROUP BY srv_telephone_history.rec_id"
					." HAVING COUNT(srv_telephone_history.id) >= '$this->max_calls'"
			  .")"
			  ." ORDER BY srv_invitations_recipients.id ASC";
		
		$sql = sisplet_query($sel);
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0) {
			while ( list($id,$phone) = mysqli_fetch_row($sql)) {
				$result[$id] = $phone;
			}
		}
		# ce ni nobenega na urniku pa nadaljujemo z random izbiro ostalih stevilk
		
		/* fetch random from available
		 * exclude from fetch:
		*  - all phones which are currently active
		*  - all phones which are on the schedule for the future
		*  - all phones which have "R" or "U" status
		*  - all phones which are called more that "max_calls" setting
		*/
		
		// Sortiranje
		if($this->call_order == 1)
			$order_by = ' srv_invitations_recipients.id ASC';
		elseif($this->call_order == 2)
			$order_by = ' srv_invitations_recipients.firstname ASC, srv_invitations_recipients.lastname ASC, srv_invitations_recipients.email ASC, srv_invitations_recipients.id ASC';
		elseif($this->call_order == 3)
			$order_by = ' srv_invitations_recipients.firstname DESC, srv_invitations_recipients.lastname DESC, srv_invitations_recipients.email DESC, srv_invitations_recipients.id DESC';
		else
			$order_by = ' RAND()';	
		
		$sel = "SELECT srv_invitations_recipients.id, TRIM(srv_invitations_recipients.phone) AS phone"
		  	  ." FROM srv_invitations_recipients "
			  ." WHERE srv_invitations_recipients.ank_id ='$this->sid'"
			  ." AND srv_invitations_recipients.deleted ='0'"
			  ." AND TRIM(srv_invitations_recipients.phone) != ''"
			  ." AND srv_invitations_recipients.id NOT IN ("
					." SELECT srv_telephone_current.rec_id"
					." FROM srv_telephone_current"
					." WHERE srv_telephone_current.started_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)"
			  ." ) AND"
			  ." srv_invitations_recipients.id NOT IN ("
			  		." SELECT srv_telephone_schedule.rec_id"
					." FROM srv_telephone_schedule"
					." WHERE srv_telephone_schedule.call_time > NOW()"
			  ." ) AND"
			  ." srv_invitations_recipients.id NOT IN ("
			  		." SELECT srv_telephone_history.rec_id"
					." FROM srv_telephone_history"
					." WHERE srv_telephone_history.status = 'R' OR srv_telephone_history.status = 'U'"
			  ." ) AND"
			  ." srv_invitations_recipients.id NOT IN ("
			  		." SELECT srv_telephone_history.rec_id"
					." FROM srv_telephone_history"
					." GROUP BY srv_telephone_history.rec_id"
					." HAVING COUNT(srv_telephone_history.id) >= '$this->max_calls'"
			  ." )"
			  ."ORDER BY ".$order_by."";
		$sql = sisplet_query($sel);
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0) {
			while ( list($id,$phone) = mysqli_fetch_row($sql)) {
				$result[$id] = $phone;
			}
		}
		return $result;
	}

	/**
	 * @desc vrne vse stevilke ki so na cakanju in bodo kasneje poklicane
	 */
	function getAllNumbersWaiting () {
		$result = array();
		
		# selectamo, tiste ki so zmenjeni po urniku kasneje
		$sel = "SELECT srv_invitations_recipients.id, TRIM(srv_invitations_recipients.phone) AS phone"
			  ." FROM srv_telephone_schedule LEFT JOIN srv_invitations_recipients ON srv_telephone_schedule.rec_id = srv_invitations_recipients.id "
			  ." WHERE srv_invitations_recipients.ank_id ='$this->sid'"
			  ." AND srv_invitations_recipients.deleted ='0'"
			  ." AND srv_telephone_schedule.rec_id = srv_invitations_recipients.id"
			  ." AND TRIM(srv_invitations_recipients.phone) != ''"
			  ." AND srv_telephone_schedule.call_time > NOW()"
			  ." AND srv_invitations_recipients.id NOT IN ("
		  			." SELECT srv_telephone_current.rec_id"
					." FROM srv_telephone_current"
					." WHERE srv_telephone_current.started_time >= DATE_SUB(NOW(), INTERVAL 2 HOUR)"
			  ." ) AND"
			  ." srv_invitations_recipients.id NOT IN ("
					." SELECT DISTINCT srv_telephone_history.rec_id"
					." FROM srv_telephone_history"
					." WHERE srv_telephone_history.status = 'R' OR srv_telephone_history.status = 'U'"
			  ." ) AND"
			  ." srv_invitations_recipients.id NOT IN ("
					." SELECT srv_telephone_history.rec_id"
					." FROM srv_telephone_history"
					." GROUP BY srv_telephone_history.rec_id"
					." HAVING COUNT(srv_telephone_history.id) >= '$this->max_calls'"
			  .")"
			  ." ORDER BY srv_telephone_schedule.call_time ASC";
		
		$sql = sisplet_query($sel);
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0) {
			while ( list($id,$phone) = mysqli_fetch_row($sql)) {
				$result[$id] = $phone;
			}
		}

		return $result;
	}

	function addMark($options = array()) {
		global $site_url,$global_user_id;
		
		if (is_array($options) && isset($options['usr_id']) && (int)$options['usr_id'] > 0) {
			$usr_id = $options['usr_id'];
		} else {
			$usr_id = $_GET['usr_id'];
		}
		if (is_array($options) && isset($options['status']) && (int)$options['status'] > 0) {
			$status = $options['status'];
		} else {
			$status = $_GET['status'];
		}
		if (is_array($options) && isset($options['datetime']) && (int)$options['datetime'] > 0) {
			$datetime = $options['status'];
		} else {
			$datetime = $_GET['datetime'];
		}
		if ($status != '') {
			sisplet_query("INSERT INTO srv_telephone_history (survey_id, user_id, rec_id, insert_time, status) VALUES ('$this->sid', '".$global_user_id."', '$usr_id', NOW(), '$status')");
		}

		if ($status != 'A') {
			sisplet_query("DELETE FROM srv_telephone_current WHERE rec_id='$usr_id'");
		} else {
			$s = sisplet_query("INSERT INTO srv_telephone_current (rec_id, user_id, started_time) VALUES ('$usr_id', '".$global_user_id."', NOW())");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		}
		
		if ($status == 'Z') {
			$s = sisplet_query("INSERT INTO srv_telephone_schedule (rec_id, call_time) VALUES ('$usr_id', NOW() + INTERVAL $this->status_z MINUTE) ON DUPLICATE KEY UPDATE call_time = VALUES(call_time)");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		}
		if ($status == 'N') {
			$s = sisplet_query("INSERT INTO srv_telephone_schedule (rec_id, call_time) VALUES ('$usr_id', NOW() + INTERVAL $this->status_n MINUTE)  ON DUPLICATE KEY UPDATE call_time = VALUES(call_time)");
		}
		if ($status == 'D') {
			$s = sisplet_query("INSERT INTO srv_telephone_schedule (rec_id, call_time) VALUES ('$usr_id', NOW() + INTERVAL $this->status_d MINUTE)  ON DUPLICATE KEY UPDATE call_time = VALUES(call_time)");
		}
		if ($status == 'T' || $status == 'P') {
			$datetime = substr($datetime,6,4).'-'.substr($datetime,3,2).'-'.substr($datetime,0,2).' '.substr($datetime,11,5).':00';
			$s = sisplet_query("INSERT INTO srv_telephone_schedule (rec_id, call_time) VALUES ('$usr_id', '$datetime')  ON DUPLICATE KEY UPDATE call_time = VALUES(call_time)");
		} 

		# če je zavrnil (R) potem izbrišemo morebitne zmenke
		if ($status == 'R' ) {
			sisplet_query("DELETE FROM srv_telephone_schedule WHERE rec_id='$usr_id'");
		} 
		
		#nardimo pravilne redirekte
		
		# ker imamo spodaj exit de ne izvede klasičen komit
		sisplet_query('COMMIT');
		
		if ($status == 'A') {
			header("Location: index.php?anketa=$this->sid&a=".A_TELEPHONE."&m=call&usr_id=".$usr_id.'&status='.$status);
			exit();
				
		} else {
			session_start();
			if (isset($_SESSION['phnNextAction'][$this->sid]) && (int)$_SESSION['phnNextAction'][$this->sid] == '0') {
				# če je anketar je lako samo na klicanu
				if ($this->isAnketar) {
					header("Location: index.php?anketa=$this->sid&a=".A_TELEPHONE."&m=call"); #'&status='.$status
					exit();
				}
				header("Location: index.php?anketa=$this->sid&a=".A_TELEPHONE."&m=view_recipients");
				exit();
			} else {
				header("Location: index.php?anketa=$this->sid&a=".A_TELEPHONE."&m=call"); #.'&status='.$status
				exit();
			}
		}
	}
	
	function preveriStevilkeTimer () {
		$this->preveri_stevilke();
	}
	
	function addRecipients() {
		global $global_user_id;

		$fields = $_POST['fields'];
		$_recipients = $_POST['recipients_list'];
		$recipients_list = str_replace("\n\r", "\n", $recipients_list);
		
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
		);

		$fields = $_POST['fields'];
		if (!is_array($fields)) {
			$fields = array();
		}
		
		# katero polje je za password
		if (in_array('inv_field_password',$fields)) {
			$user_password = true;
		} else {
			$user_password = false;
		}
		
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
		$sql_string = "SELECT firstname,lastname,salutation,phone,custom FROM srv_invitations_recipients WHERE ank_id = '".$this->sid."' AND deleted='0'";
		$sql_query = sisplet_query($sql_string);

		if (mysqli_num_rows($sql_query) > 0 ) {
			while ($sql_row = mysqli_fetch_assoc($sql_query)) {
				$user_in_db[] = $sql_row['firstname'].$sql_row['lastname'].$sql_row['salutation'].$sql_row['phone'].$sql_row['custom'];
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
		/*polovimo prejemnike ki ne želijo prejemati obvestil
		
				$sql_string = "SELECT email FROM srv_invitations_recipients WHERE unsubscribed = '1'";
				$sql_query = sisplet_query($sql_string);
				$unsubscribed = array();
				if (mysqli_num_rows($sql_query) > 0 ) {
					while ($sql_row = mysqli_fetch_assoc($sql_query)) {
						$unsubscribed[] = $sql_row['email'];
					}
				}*/

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
					$line_array = explode(',',$recipient_line);
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
					// if ($user_email == true && $invalid_line == false) {
					if ($invalid_line == false) {
						#						# preberemo uporabniški email
						$email_field = trim($recipent_array['inv_field_firstname'])
						. trim($recipent_array['inv_field_lastname'])
						. trim($recipent_array['inv_field_salutation'])
						. trim($recipent_array['inv_field_phone'])
						. trim($recipent_array['inv_field_custom']);

					
					/*						#ali je email veljaven
											if (!$this->validEmail($email_field) && $invalid_line == false) {
												$invalid_email_array[] = $recipient_line;
												$invalid_line = true;
											}*/

					# ali je email podvojen
					if (in_array(strtolower($email_field),$user_in_db) && $invalid_line == false) {
						$duplicate_email_array[] = strtolower($recipient_line);
						$invalid_line = true;
					}

					/* ali uporabnik ne želi prejemati sporočil (opted out)
											if (in_array($email_field,$unsubscribed) && $invalid_line == false) {
												$unsubscribed_recipiens_array[] = $recipient_line;
												$invalid_line = true;
											}*/

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

		$list_id = (int)$_POST['pid'];
		
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
					$sql_insert .= ", '$field'";
				}
				$sql_insert .= ",'0','0','0','0',NOW(),'".$global_user_id."','".$list_id."')";
				$sql_insert_array[$array_loop][] = $sql_insert;
				if ($cnt >= $max_in_array) {
					$array_loop++;
					$cnt = 0;
				}
			}
			$sql_insert_end = " ON DUPLICATE KEY UPDATE firstname=VALUES(firstname), lastname=VALUES(lastname), salutation=VALUES(salutation), phone=VALUES(phone), custom=VALUES(custom), deleted='0', date_inserted=NOW()";

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
	
	
	function addSystemVariables($variables) {
		$user_base = 0;
		global $site_path;
		$system_fields = array(
				'inv_field_email' => 'email',
				'inv_field_firstname' => 'ime',
				'inv_field_lastname' => 'priimek',
				#			'inv_field_password' => 'geslo', # gesla ne dodajamo kot sistemsko spremenljivko
				'inv_field_salutation' => 'naziv',
				'inv_field_phone' => 'telefon',
				'inv_field_custom' => 'drugo',
		);
		$variablesResult=array();
		$sqlb = sisplet_query("SELECT branching, user_base FROM srv_anketa WHERE id = '".$this->sid."'");
		$rowb = mysqli_fetch_array($sqlb);
	
		$ba = new BranchingAjax($this->sid);
		if (count($variables) > 0) {
			
			// zakaj je bi ta reverse???
			//$variables = array_reverse($variables,true);
			foreach ($variables as $var) {
				if (isset($system_fields[$var])) {
					$spr_id = null;
						
					if (isset($system_fields[$var])) {
						$variable = $system_fields[$var];
					} else {
						$variable = str_replace('inv_field_', '', $var);
					}
	
					$sqlVariable = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.variable='".$variable."' AND s.gru_id=g.id AND g.ank_id='".$this->sid."'");
					if (mysqli_num_rows($sqlVariable) == 0 && $variable!='pass') { // če varabla še ne obstaja jo kreiramo
						// za polje pass - Geslo ne kreiramo sistemske variable
	
						if ($variable != 'language') {
							$user_base = 1;
						}
	
						// dodamo novo spremenljivko na konec, tip je 21
						ob_start();
						$ba->ajax_spremenljivka_new(0, 0, 1, 0, 21);
						$spr_id = $ba->spremenljivka;
						ob_clean();
											
						$s = sisplet_query("UPDATE srv_spremenljivka SET variable='".$variable."', variable_custom='1', naslov='".$variable."', sistem='1', visible='0' WHERE id='$spr_id'");
						if (!$s) echo 'err435'.mysqli_error($GLOBALS['connect_db']);
	
					} else {
						list($spr_id) = mysqli_fetch_row($sqlVariable);
					}
					$variablesResult[$variable] = (int)$spr_id;
					
				}
			}
		}
		// če je potreben updejt (ampak najbrž je itak na 1 zaradi e-mail)
		if ($user_base > 0 && $user_base != $rowb['user_base']) {
			$sql = sisplet_query("UPDATE srv_anketa SET user_base='$user_base' WHERE id='" . $this->sid . "'");
			SurveyInfo :: getInstance()->resetSurveyData();
		}
			
		return $variablesResult;
	}
	
	function generateCode() {
		$cookie = md5(mt_rand(1, mt_getrandmax()) . '@' . $_SERVER['REMOTE_ADDR']);
	
		return array(substr($cookie,0,6), $cookie);
	}
	
	function displayRecipentsErrors($result) {
		global $lang;
		$valid_recipiens = is_array($result['valid_recipiens']) ? $result['valid_recipiens'] : array();
		$invalid_password = is_array($result['invalid_password']) ? $result['invalid_password'] : array();
		$insert_errors = is_array($result['insert_errors']) ? $result['insert_errors'] : array();

		# dodani so bili nekateri uporabniki
		if (count($valid_recipiens) > 0) {
			echo '<div id="inv_recipiens_added">';
			echo $lang['srv_inv_recipiens_add_success_cnt'].'<span class="inv_count"><span class="as_link" onclick="$(\'#invRecipiensList1\').toggle();">'. count($valid_recipiens).'</span></span><br>';
			echo '<div id="invRecipiensList1" class="displayNone"><br/>';
			foreach ($valid_recipiens AS $fields) {
				if (is_array($fields)) {
					echo strtolower($fields['telefon']);
					if (trim($fields['ime']) != '') {
						echo ', '.$fields['ime'];
					}
					if (trim($fields['priimek']) != '') {
						echo ', '.$fields['priimek'];
					}
				} else {
					echo strtolower($fields);
				}
				echo '<br/>';
			}
			echo '</div>';
			echo '</div>';
		}
			
		if ( (count($invalid_password) + count($insert_errors) )  > 0  ) {
			echo '<div id="inv_recipiens_rejected">';

			# ni veljavnih uporabnikov
			if (count($valid_recipiens) == 0 ) {
				echo $lang['srv_inv_recipiens_add_error'].'<br/>';
			}


			# neveljavena gesla
			if (count($invalid_password) > 0) {
				echo $lang['srv_inv_recipiens_add_invalid_password_cnt'].'<span class="inv_count"><span class="as_link" onclick="$(\'#invRecipiensList5\').toggle();">'.count($invalid_password).'!</span></span><br />';
				echo '<div id="invRecipiensList5" class="displayNone"><br/>';
				foreach ($invalid_password AS $fields) {
					if (is_array($fields)) {
						echo strtolower($fields['telefon']);
						if (trim($fields['ime']) != '') {
							echo ', '.$fields['ime'];
						}
						if (trim($fields['priimek']) != '') {
							echo ', '.$fields['priimek'];
						}
					} else {
						echo strtolower($fields);
					}
					echo '<br/>';
				}
				echo '</div>';
			}
			
			# napake pri insertiranju
			if (count($insert_errors) > 0) {
				echo $lang['srv_inv_recipiens_add_invalid_password_cnt'].'<span class="inv_count"><span class="as_link" onclick="$(\'#invRecipiensList5\').toggle();">'.count($invalid_password).'!</span></span><br />';
				echo '<div id="invRecipiensList5" class="displayNone"><br/>';
				foreach ($insert_errors AS $fields) {
					if (is_array($fields)) {
						echo strtolower($fields['telefon']);
						if (trim($fields['ime']) != '') {
							echo ', '.$fields['ime'];
						}
						if (trim($fields['priimek']) != '') {
							echo ', '.$fields['priimek'];
						}
					} else {
						echo strtolower($fields);
					}
					echo '<br/>';
				}
				echo '</div>';
			}

			echo '</div>';

			return array_merge($invalid_password, $insert_errors) ;
		}
		return array();
	}
	
	function setNextAction()  {
		$phnNextAction = (int)$_REQUEST['phnNextAction'];
		session_start();
		$_SESSION['phnNextAction'][$this->sid] =$phnNextAction; 
		session_commit();
	}
	
	function getNextTimeCall() {
		global $lang;
		echo '<h2>'.$lang['srv_call_nonumber'].'</h2>';
		
		# preverimo ali imamo kaj na shedučling
		$str = "SELECT DATE_FORMAT(MIN(scs.call_time), '%d.%m.%Y %H:%i:%s') AS minTime"
		." FROM srv_telephone_schedule AS scs LEFT JOIN srv_invitations_recipients AS sir ON scs.rec_id = sir.id WHERE sir.ank_id='$this->sid' AND sir.deleted='0'";
		$qry = sisplet_query($str);
		if (mysqli_num_rows($qry) > 0) {
			list($nextTime) = mysqli_fetch_row($qry);
				
			echo $lang['srv_call_nonumbers_time'];
			echo ' <span class="strong">'.$nextTime.'</span>';
		}
	}
	
	function setUserComment() {
		
		$usr_id = (int)$_POST['usr_id'];
		$comment = $_POST['comment'];
		
		$comment = trim(strip_tags($comment));
		
		$strInsert = "INSERT INTO srv_telephone_comment (rec_id, comment_time, comment) VALUES ('$usr_id', NOW(), '$comment') ON DUPLICATE KEY UPDATE comment_time = VALUES(comment_time), comment = VALUES(comment)";
		$qryInsert = sisplet_query($strInsert);
	}
	
	function getProfileName(){
		global $lang,$site_url, $global_user_id;

		$array_profiles = array();
			
		# polovimo še ostale porfile
		$sql_string = "SELECT pid, name,comment FROM srv_invitations_recipients_profiles WHERE uid in('".$global_user_id."') AND from_survey = '".$this->sid. "'";
		$sql_query = sisplet_query($sql_string);
		while ($sql_row = mysqli_fetch_assoc($sql_query)) {
			$array_profiles[$sql_row['pid']] = array('name' => $sql_row['name'], 'comment'=>$sql_row['comment']);
		}
		
		
		echo '<div id="inv_recipients_profile_name">';
		echo '<span class="inv_new_list_note">';
		echo $lang[''].'Izberite seznam kamor želite dodati prejemnike. Izbirate lahko med:<br/><ul><li>\'Nov seznam\' - prejemniki se dodajo v nov seznam, kateremu določite ime</li><li>\'Začasen seznam\' - seznam obstaja samo v času seje brskalnika</li><li>ali izberete obstoječ seznam, h kateremu se bodo dodali prejemniki</li></ul><br/>';
		echo '</span>';
		echo $lang['srv_invitation_recipients_list_add'].':&nbsp;';
		echo '<select id="profile_id" onchange="inv_new_recipients_list_change(this);" autofocus="autofocus" tabindex="2">';
		echo '<option value="0" class="gray bold"'.((int)$_POST['pid'] > 0 ? '' : ' selected="selected"'  ).'>'.$lang['srv_invitation_new_list'].'</option>';
		echo '<option value="-1" class="gray bold">'.$lang['srv_invitation_new_templist'].'</option>';
		if (count($array_profiles) > 0){
			foreach($array_profiles AS $key => $profile) {
				echo '<option value="'.$key.'" comment="'.$profile['comment'].'"'.($_POST['pid'] == $key ? ' selected="selected"' : '').'>'.$profile['name'].'</option>';
			}
		}
		echo '</select>';
		echo '<span id="new_recipients_list_span" '.((int)$_POST['pid'] > 0 ? ' class="displayNone"' : ''  ).'>';
		echo '<br><br/>';
		echo '<label>'.$lang['srv_inv_recipient_list_name'];
		# zaporedno številčimo ime seznama1,2.... če slučajno ime že obstaja
		$new_name = $lang['srv_inv_recipient_list_new'];
		$names = array();
		$s = "SELECT name FROM srv_invitations_recipients_profiles WHERE name LIKE '%".$new_name."%' AND uid='$global_user_id' AND from_survey='$this->sid'";
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
		echo '<textarea id="rec_profile_comment" tabindex="3" rows="2" ></textarea>';
		echo '<br class="clr" /><br class="clr" />';
		echo '<span class="buttonwrapper floatLeft spaceRight"  title="'.$lang['srv_cancel'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#fade\').fadeOut(\'slow\');$(\'#fullscreen\').fadeOut(\'slow\').html(\'\');return false;" ><span>'.$lang['srv_cancel'].'</span></a></span>';
		echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['save'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="phnSaveNewProfile(); return false;"><span>'.$lang['save'].'</span></a></span>';
		echo '<br class="clr" />';
		echo '</div>'; # id="inv_view_arch_recipients"
		echo '<script type="text/javascript">';
		echo "$('#rec_profile_name').focus();";
		echo '</script>';
	}
	
	function saveNewProfile() {
		global $lang, $global_user_id;
		
		$profile_id = (int)$_POST['profile_id'];
		$profile_name = $_POST['profile_name'];
		$profile_comment = $_POST['profile_comment'];
		$recipients_list = $_POST['recipients_list'];
		$fields = str_replace('inv_field_','',implode(',',$_POST['fields']));
		
		if (trim($fields) != '' && trim($recipients_list) != '') {
			if ($profile_id < 0) {
				# shranimo v začasen profil
				session_start();
				$_SESSION['phn_rec_profile'][$this->sid] = array(
						'pid'=>-1,
						'name'=>$lang['srv_invitation_new_templist'],
						'fields'=>$fields,
						'respondents'=>$recipients_list,
						'comment'=>$profile_comment
				);
			} else if ($profile_id == 0) {
				#shranjujemo v nov profil
				
				# dodelimo ime
				#zaporedno številčimo ime seznama1,2.... če slučajno ime že obstaja
				if($profile_name == ''){
					$new_name = $lang['srv_inv_recipient_list_new'];
					$names = array();
					$s = "SELECT name FROM srv_invitations_recipients_profiles WHERE name LIKE '%".$new_name."%' AND uid='$global_user_id' AND from_survey='$this->sid'";
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
				}
				else{
					$new_name = $profile_name;
				}
				
				$sql_insert = "INSERT INTO srv_invitations_recipients_profiles".
						" (name,uid,fields,respondents,insert_time,comment, from_survey) ".
						" VALUES ('$new_name', '$global_user_id', '$fields', '$recipients_list', NOW(), '$profile_comment', '$this->sid' )";
				$sqlQuery = sisplet_query($sql_insert);
				if (!$sqlQuery) {
					$return['success'] = '0';
					$return['msg'] = mysqli_error($GLOBALS['connect_db']);
				} else {
					$return['success'] = '1';
					$return['pid'] = mysqli_insert_id($GLOBALS['connect_db']);
				
				}
			} else {
				$this->saveProfile(array('pid'=>$profile_id,'fields'=>$fields,'recipients'=>$recipients_list));
			}
		}
		list($recipients_list,$fields) = $this->getRecipientsProfile($profile_id);
		$_POST['pid'] = $profile_id;
		$this->addRecipientsView($fields,$recipients_list);
	}
	
	function saveProfile($atributes) {
		$pid = (int)$atributes['pid'];
		$fields = $atributes['fields'];
		$recipients = $atributes['recipients'];
		$comment = $atributes['comment'] !== null ? ", comment='".$atributes['comment']."' " : '';
		if (trim($fields) != '' && trim($recipients) != '' && $pid > 0) {
			# updejtamo obstoječ profil
			$sql_update = " UPDATE srv_invitations_recipients_profiles".
			" SET fields = '$fields', respondents ='$recipients', insert_time=NOW() $comment WHERE pid = '$pid'";
			$sqlQuery = sisplet_query($sql_update);
		}
		
		if (!$sqlQuery) {
			$return['success'] = '0';
			$return['msg'] = mysqli_error($GLOBALS['connect_db']);
		} else {
			$return['success'] = '1';
			$return['pid'] = $pid;
		}
		list($recipients_list,$fields) = $this->getRecipientsProfile($pid);
		$_POST['pid'] = $pid;
		$this->addRecipientsView($fields,$recipients_list);
	}
	
	function deleteProfile() {
		global $global_user_id;
		$id = (int)$_POST['pid'];
		if ((int)$id > 0) {

		$sql_string = "DELETE FROM srv_invitations_recipients_profiles WHERE pid='".$id."' ";
		$sqlQuery = sisplet_query($sql_string);
			sisplet_query("COMMIT");
		}
	}
	
	function editProfile() {
		global $lang, $site_url, $global_user_id;
		$return = array('error'=>'0');
		$pid = (int)$_POST['pid'];
		if ($pid > 0) {
			$sql_string = "SELECT name, comment, respondents FROM srv_invitations_recipients_profiles WHERE pid='".$pid."'";
			$sqlQuery = sisplet_query($sql_string);
			$sqlRow = mysqli_fetch_assoc($sqlQuery);
		
			echo '<div id="inv_recipients_profile_name">';
			echo '<div id="inv_error_note" class="hidden"></div>';
			echo '<table>';
			echo '<tr><td>'.$lang['srv_inv_recipient_list_name'].'</td>';
			echo '<td>';
			echo '<input type="text" id="rec_profile_name" value="'.$sqlRow['name'].'" autofocus="autofocus">';
			echo '</td></tr>';
			echo '<tr><td>'.$lang['srv_inv_recipient_list_comment'].'</td>';
			echo '<td>';
			echo '<input type="text" id="rec_profile_comment" value="'.$sqlRow['comment'].'" >';
			echo '</td></tr>';
			echo '<tr><td>'.$lang['srv_inv_recipient_list_recipients'].'</td>';
			echo '<td>';
			echo '<textarea id="rec_profile_respondents" style="width:250px; height:150px;">'.$sqlRow['respondents'].'</textarea>';
			echo '</td></tr>';
			echo '</table>';
		
			echo '<input type="hidden" id="rec_profile_pid" value="'.$pid.'" >';
		
			echo '<br class="clr" /><br class="clr" />';
			echo '<span class="buttonwrapper floatLeft spaceRight"  title="'.$lang['srv_cancel'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#fade\').fadeOut(\'slow\');$(\'#fullscreen\').fadeOut(\'slow\').html(\'\');return false;" ><span>'.$lang['srv_cancel'].'</span></a></span>';
			echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['save'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="phnUpdateProfile(); return false;"><span>'.$lang['save'].'</span></a></span>';
			echo '<br class="clr" />';
			echo '</div>'; # id="inv_view_arch_recipients"
		
		}
	}
	
	function updateProfile() {
		global $lang,$site_url, $global_user_id;
		$return = array('error'=>'0', 'msg'=>'');
		$pid = (int)(int)$_POST['pid'];
			
		$profile_name = (isset($_POST['profile_name']) && trim($_POST['profile_name']) != '') ? trim($_POST['profile_name']) : '';
		$profile_comment = (isset($_POST['profile_comment']) && trim($_POST['profile_comment']) != '') ? trim($_POST['profile_comment']) : '';
		$profile_respondents = (isset($_POST['profile_respondents']) && trim($_POST['profile_respondents']) != '') ? trim($_POST['profile_respondents']) : '';
		if ($pid > 0) {
		
			if ($profile_name != '') {
				$sql_update = "UPDATE srv_invitations_recipients_profiles SET name = '$profile_name', comment = '$profile_comment', respondents = '$profile_respondents' WHERE pid = '$pid'";
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
		return json_encode($return);
	}

	function goToUser() {
		$usr_id = (int)$_POST['showUser'];
		$this->userCallToShow($usr_id,false,false);
	}
	
	
	function userCallToShow($usr_id,$openedSurvey,$schedule) {
		global $lang;
		global $site_url;
		global $site_root;
		
		$sql1 = sisplet_query("SELECT * FROM srv_invitations_recipients WHERE id = '$usr_id' AND deleted='0'");
		$row1 = mysqli_fetch_array($sql1);
		 
		echo '<table id="phn_call_table">';
		#    echo '<table style="width:100%" border="1">';
		echo '<tr>
		<th style="width:33%">'.$lang['srv_telephone_table_history'].'</th>
		<th style="width:33%">'.$lang['srv_telephone_table_calling'].'</th>
		<th style="width:34%">'.$lang['srv_telephone_table_comments'].'</th>
		</tr>';
		 
		echo '<tr><td valign="top">';
		$canCall = true;
		$userStatus = array();
		$last_status = '';
		$sql2 = sisplet_query("SELECT DATE_FORMAT(insert_time, '%d.%m.%Y %H:%i:%s'), status  FROM srv_telephone_history WHERE rec_id='$usr_id' ORDER BY insert_time ASC");
		if (mysqli_num_rows($sql2) > 0 ) {
			while (list($insert_time, $status) = mysqli_fetch_array($sql2)) {
				echo '<p>'.$insert_time.' - <strong>'.$lang['srv_telephone_status_'.$status].'</strong></p>';
				if ($status == 'U' || $status == 'R') {
					# preverimo ali lahko uporabnika še kontaktiramo
					$canCall = false;
				}
				$userStatus[$status] = true;
				$last_status = $status;
			}
		} else {
			echo $lang['srv_telephone_status_'];
		}
		echo '</td>';
		 
		echo '<td valign="top">';
		if ($canCall == true) {
			if ($openedSurvey) {
				# zaprli smo aktivno anketo, prikažemo naslednji korak oz. izbiro zaključne akcije
				echo '<h2 class="red">'.$lang['srv_telephone_calling_number_end'].'</h2>';
				echo '<h2 class="red">'.$row1['phone'].'</h2>';
					
			} else {
				echo '<h2 class="red">'.$lang['srv_telephone_calling_number'].($schedule == true ? $lang['srv_telephone_call_was_schedule']:'').'</h2>';
				echo '<h2 class="red">'.$row1['phone'].'</h2>';
			}
			
			if($row1['firstname'] != '')
				echo '<h2>'.$lang['name'].': '.$row1['firstname'].'</h2>';
			if($row1['lastname'] != '')
				echo '<h2>'.$lang['surname'].': '.$row1['lastname'].'</h2>';
			if($row1['email'] != '')
				echo '<h2>'.$lang['email'].': '.$row1['email'].'</h2>';
			if($row1['custom'] != '')
				echo '<h2>Custom: '.$row1['custom'].'</h2>';
		} 
		else {
			# onemogočimo ponovno klicanje uporabnika ker je zaključena ali je zavrnil
			echo'<h2 class="red">';
			if (isset($userStatus['U'])) {
				echo $lang['srv_telephone_call_action_cant_edit_U'];
			} elseif (isset($userStatus['R'])) {
				echo $lang['srv_telephone_call_action_cant_edit_R'];
			}
			echo'</h2>';
			echo '<h2 class="red">'.$row1['phone'].'</h2>';
			
			if($row1['firstname'] != '')
				echo '<h2>'.$lang['name'].': '.$row1['firstname'].'</h2>';
			if($row1['lastname'] != '')
				echo '<h2>'.$lang['surname'].': '.$row1['lastname'].'</h2>';
			if($row1['email'] != '')
				echo '<h2>'.$lang['email'].': '.$row1['email'].'</h2>';
			if($row1['custom'] != '')
				echo '<h2>Custom: '.$row1['custom'].'</h2>';
			
			echo $lang['srv_telephone_calling_next_step'];
			echo '<div style="padding-left: 20px;">';
			echo '<p class="bold"><a href="'.$this->addUrl('view_recipients').'">'.$lang['srv_telephone_call_action_view_recipients'].'</a></p>';
			echo '</div>';
		}
		
		
		#Uvod
		$intro = $this->surveySettings['introduction'];
		if (trim($intro) == '') {
			$intro = $lang['srv_intro'];
		}
			
		echo '<p>'.$lang['srv_telephone_call_introduction'];
		echo '<div class="phn_user_intro">';
		echo $intro;
		echo '</div>';
		echo '</p>';
		echo '<br/>';
	
		echo '</td>';
		 
		echo '<td valign="top">';
		 
		
		if ($canCall == true) {
			if ($openedSurvey) {
				# zaprli smo aktivno anketo, prikažemo naslednji korak oz. izbiro zaključne akcije
				echo '<p class="red strong">'.$lang['srv_telephone_calling_step_action'].'</p>';
			} else {
				echo '<p class="red strong">'.$lang['srv_telephone_calling_next_step'].'</p>';
			}
		
			echo '<div style="padding-left: 20px;">';
			if ($openedSurvey) {
				# uporqabnik je odprl anketo
				echo '<p><a href="ajax.php?anketa='.$this->sid.'&t='.A_TELEPHONE.'&m=addmark&usr_id='.$usr_id.'&status=U">'.$lang['srv_call_successful2'].'</a></p>';
			} else {
				# uporabnik še ni odprl ankete
				echo '<p><span class="as_link" onclick="phnStartSurvey(\''.$usr_id.'\');">'.$lang['srv_call_open_startCall'].$lang['srv_call_open'].'</span></p>';
			}
			if ($openedSurvey) {
			} else {
				# če smo na userju, potem smo ga dobili, zato ne more bit nedosegljiv ali zaseden
				echo '<p><a href="ajax.php?anketa='.$this->sid.'&t='.A_TELEPHONE.'&m=addmark&usr_id='.$usr_id.'&status=Z">'.$lang['srv_call_zaseden'].'</a></p>';
				echo '<p><a href="ajax.php?anketa='.$this->sid.'&t='.A_TELEPHONE.'&m=addmark&usr_id='.$usr_id.'&status=N">'.$lang['srv_call_ga_ni'].'</a></p>';
			}

			echo '<p><a href="#" onclick="phnShowPopupAddMarker(\''.$usr_id.'\',\'T\'); return false;">'.$lang['srv_call_zmenjen'].'</a></p>';
			echo '<p><a href="#" onclick="phnShowPopupAddMarker(\''.$usr_id.'\',\'P\'); return false;">'.$lang['srv_call_prekinjen'].'</a></p>';
			echo '<p><a href="ajax.php?anketa='.$this->sid.'&t='.A_TELEPHONE.'&m=addmark&usr_id='.$usr_id.'&status=D">'.$lang['srv_call_prelozen'].'</a></p>';
			
			# preverimo koliko številk še imamo razen trenutno izbrane
			$toCall = $allUsers = $this->getAllNumbers();
			unset($allUsers[$usr_id]);
			
			# na voljo je še nekaj številk, izberemo novo
			if ($allUsers > 0) {
				// Dovolimo izbrati novo stevilko samo v primeru ko imamo nakljucno sortiranje
				if($this->call_order == 0)
					echo '<p><br /><a href="'.$this->addUrl('clear_current').'">'.$lang['srv_telephone_call_action_cancel_nextNumber'].' ('.count($toCall).')</a></p>';
			} 
			# na voljo je samo ta številka, ne moremo ponudit druge
			else {		
				echo '<p><br /><a href="'.$this->addUrl('start_call').'">'.$lang['srv_telephone_call_action_cancel_preview'].' ('.count($toCall).')</a></p>';
			}
			
			// Razveljavimo zadnji status (undo)
			if($last_status == 'A')
				echo '<p><a href="#" onClick="phnUndoStatus(\''.$usr_id.'\')">'.$lang['srv_telephone_call_action_undo_status'].' (»'.$last_status.'«)</a></p>';

			echo '<div id="telephone_popup" />';
			echo '</div>';
			
		} else {
			# onemogočimo ponovno klicanje uporabnika ker je zaključena ali je zavrnil
			echo '<p class="red strong">'.$lang['srv_telephone_calling_next_step'].'</p>';
			echo '<div style="padding-left: 20px;">';
						
			// Seznam respondentov
			echo '<p><a href="'.$this->addUrl('view_recipients').'">'.$lang['srv_telephone_call_action_view_recipients'].'</a></p>';
						
			# preverimo koliko številk še imamo razen trenutno izbrane
			$toCall = $allUsers = $this->getAllNumbers();
			unset($allUsers[$usr_id]);
			
			# na voljo je še nekaj številk, izberemo novo
			if ($allUsers > 0) {
				// Dovolimo izbrati novo stevilko samo v primeru ko imamo nakljucno sortiranje
				if($this->call_order == 0)
					echo '<p><a href="'.$this->addUrl('call').'">'.$lang['srv_telephone_call_action_cancel_nextNumber'].' ('.count($toCall).')</a></p>';
			} 
			# na voljo je samo ta številka, ne moremo ponudit druge
			else {
				echo '<p><a href="'.$this->addUrl('start_call').'">'.$lang['srv_telephone_call_action_cancel_preview'].' ('.count($toCall).')</a></p>';
			}
						
			// Razveljavimo zadnji status (undo)
			echo '<p><a href="#" onClick="phnUndoStatus(\''.$usr_id.'\')">'.$lang['srv_telephone_call_action_undo_status'].' (»'.$last_status.'«)</a></p>';
		}
		# spodnje akcije
		# če je anketar ne prikazujemo nextAction
		if ($this->isAnketar == false) {
			echo '<div style="border-top:1px solid gray;">';
			echo '<p>';
			session_start();
			$nextAction = 1;
						
			if (isset($_SESSION['phnNextAction'][$this->sid]) && (int)$_SESSION['phnNextAction'][$this->sid]==0) {
				$nextAction = 0;
			}
			echo '<label><input type="radio" name="phnNextAction" id="phn_exit" value="0"'.($nextAction == 0?' checked="checked"':'').' onchange="phnNextActionChange(this); return false;">';
			echo $lang['srv_telephone_call_action_cancel'].'</label>';
			echo '<label><input type="radio" name="phnNextAction" id="phn_next" value="1"'.($nextAction == 1?' checked="checked"':'').' onchange="phnNextActionChange(this); return false;">';
			echo $lang['srv_telephone_call_action_nextNumber'].'</label>';
			echo '</p>';
			echo '</div>';
		}
				
		$str_comment = "SELECT comment from srv_telephone_comment WHERE rec_id = '$usr_id'";
		$qry_comment = sisplet_query($str_comment);
		$row_comment = mysqli_fetch_assoc($qry_comment);
		echo '<p>';
		echo $lang['srv_telephone_call_comment'];
		echo '<div id="phn_user_comment" class="editable" onblur="phnSetUserComment(this,\''.$usr_id.'\');return false;" contentEditable="true">';
		echo $row_comment['comment'];
		echo '</div>';
		echo '</p>';
		 
		echo '</td></tr>';
		 
		echo '</table>';
	}
	
	
	/* Paginacija za pregled reposndentov
	 *
	*/
	function displayPagination($all_records) {
		global $lang,$site_url;
		#trenutna stran
		$page = isset($_GET['page']) ? $_GET['page'] : '1';
		$current = is_numeric($_GET['page']) && (int)$_GET['page'] > 0 ? $page : '1';
			
		$all = ceil($all_records / REC_ON_PAGE);

		# current nastavimo na zadnji element
		if ( $all > 1) {


			echo '<div id="pagination" style="margin-bottom:10px;">';
			$baseUrl = $site_url.'admin/survey/index.php?anketa='.$this->sid.'&a='.A_TELEPHONE.'&m=view_recipients&page=';

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
		else{
			echo '<br />';
		}
	}

	function startSurvey() {
		# nastavimo marker na A
		# in vrnemo dva urlja, enega za reload strani, drugega pa odpiranje ankete
		global $lang,$site_url, $global_user_id;
		
		$return = array('error'=>'1', 'msg'=>'Napaka','reloadUrl'=>'','surveyUrl'=>'');
		
		if ((int)$_POST['usr_id'] > 0) {
			$usr_id = (int)$_POST['usr_id'];
			
			# nastavimo url za nastavitev statusa in reload strani
			$return['reloadUrl'] = $site_url.'admin/survey/ajax.php?anketa='.$this->sid.'&t='.A_TELEPHONE.'&m=addmark&usr_id='.$usr_id.'&status=A';
			#ajax.php?anketa='.$this->sid.'&t='.A_TELEPHONE.'&m=addmark&usr_id='.$usr_id.'&status=A
			
			#preberemo vse podatke respondenta
			$res_sel = "SELECT * FROM srv_invitations_recipients WHERE id ='$usr_id'";
			$res_query = sisplet_query($res_sel);
			$res_row = mysqli_fetch_assoc($res_query);
			
			#preverimo ali že obstaja povezava med respondentom in userjem
			$chk_user = "SELECT id, pass FROM srv_user WHERE inv_res_id='$usr_id' AND ank_id='".$this->sid."'";
			$chk_query = sisplet_query($chk_user);
			$return['msg'] = $chk_user;
			if (mysqli_num_rows($chk_query) > 0) {
				# user že obstaja
				$user_data = mysqli_fetch_assoc($chk_query);
				
				# sestavimo še url za odpiranje izpolnjevanja ankete
				$return['surveyUrl'] = $site_url.'a/'.Common::encryptAnketaID($this->sid).'&survey-'.$this->sid.'&code='.$user_data['pass'];
				$return['error'] = '';
			} 
			else {
				# user še ne obstaja vstavimo njegove podatke
				# polovimo sistemske spremenljivke z vrednostmi
				$strSistemske = "SELECT s.id, s.naslov, s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.gru_id=g.id AND g.ank_id='".$this->sid."' AND variable IN('" . implode("','",$this->inv_variables)."')  ORDER BY g.vrstni_red, s.vrstni_red";
				$qrySistemske = sisplet_query($strSistemske);
				$sys_vars = array();
				$sys_vars_ids = array();
				
				while ($row = mysqli_fetch_assoc($qrySistemske)) {
					$sys_vars[$row['id']] = array('id'=>$row['id'], 'variable'=>$row['variable'],'naslov'=>$row['naslov']);
					$sys_vars_ids[] =$row['id'];
				}
				
				$sqlVrednost = sisplet_query("SELECT spr_id, id AS vre_id FROM srv_vrednost WHERE spr_id IN(".implode(',',$sys_vars_ids).") ORDER BY vrstni_red ASC ");
				while ($row = mysqli_fetch_assoc($sqlVrednost)) {
					if (!isset($sys_vars[$row['spr_id']]['vre_id'])) {
						$sys_vars[$row['spr_id']]['vre_id'] = $row['vre_id'];
					}
				}
				
				$strInsert = "INSERT INTO srv_user (ank_id, email, cookie, pass, last_status, time_insert, inv_res_id)"
						    ." VALUES ('$this->sid','$res_row[email]','$res_row[cookie]', '$res_row[password]', '0', NOW(), '$res_row[id]') ON DUPLICATE KEY UPDATE cookie = '$res_row[cookie]', pass='$res_row[password]'";
				sisplet_query($strInsert);
				$srv_usr_id = mysqli_insert_id($GLOBALS['connect_db']);
				
				sisplet_query("COMMIT");
				if ($srv_usr_id) {
					$strInsertDataText = array();
									
					# dodamo še podatke za posameznega userja za sistemske spremenljivke
					foreach ($sys_vars AS $sid => $spremenljivka) {
						$_user_variable = $this->inv_variables_link[$spremenljivka['variable']];
						if (trim($res_row[$_user_variable]) != '' && $res_row[$_user_variable] != null) {
							$strInsertDataText[] = "('".$sid."','".$spremenljivka['vre_id']."','".trim($res_row[$_user_variable])."','".$srv_usr_id."')";
						}
					}
					
					# vstavimo v srv_userbase
					$strInsert = "INSERT INTO srv_userbase (usr_id, tip, datetime, admin_id) VALUES ('$srv_usr_id','0',NOW(),'$global_user_id')";
					sisplet_query($strInsert);
					
					# vstavimo v srv_userstatus
					$strInsert = "INSERT INTO srv_userstatus (usr_id, tip, status, datetime) VALUES ('$srv_usr_id', '0', '0', NOW())";
					sisplet_query($strInsert);
					
					# vstavimo v srv_data_text
					$db_table = (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1) ? '_active' : '';
					if (count($strInsertDataText) > 0) {
						$strInsert = "INSERT INTO srv_data_text".$db_table." (spr_id, vre_id, text, usr_id) VALUES ";
							$strInsert .= implode(',',$strInsertDataText);
							sisplet_query($strInsert);
					}
					sisplet_query("COMMIT");
					
					# sestavimo še url za odpiranje izpolnjevanja ankete
					$return['surveyUrl'] = $site_url.'a/'.Common::encryptAnketaID($this->sid).'&survey-'.$this->sid.'&code='.$res_row[password];
					$return['error'] = '';
				}
			}
		}	
		
		echo json_encode($return);
		
		exit;
	}
	
	function showPopupAddMarker() {
		global $lang;
		$newDate = date('d.m.Y H:i', time()+3600 );
		$marker = $_POST['marker'];
		$usr_id = (int)$_POST['usr_id'];
		
		if ($usr_id > 0 && ($marker == 'P' || $marker == 'T')) {
			if ($marker == 'T') {
				# zmenjen
				echo $lang['srv_telephone_call_mark_T_note'];
			} else if ($marker == 'P') {
				#prekinjen
				echo $lang['srv_telephone_call_mark_P_note'];				
			}
			
			echo '<p><form name="'.$marker.'">';
			echo '<input id="'.$marker.'_datetime" type="text" name="'.$marker.'_datetime" value="'.$newDate.'" />
			<span class="faicon calendar_icon icon-as_link" id="'.$marker.'_datetime_image"></span>
			<script type="text/javascript">
			Calendar.setup({
				inputField  : "'.$marker.'_datetime",
				ifFormat    : "%d.%m.%Y %H:%M",
				button      : "'.$marker.'_datetime_image",
				singleClick : true
			});
			</script></form>
			</p>';
				
			if ($marker == 'T') {
				echo '<span class="buttonwrapper floatRight spaceRight" ><a class="ovalbutton ovalbutton_orange"  href="#" onclick="phnAddMarker(\''.$usr_id.'\',\''.$marker.'\'); return false;"><span>'.$lang['srv_call_zmenjen'].'</span></a></span>';
			} else if ($marker == 'P') {
				echo '<span class="buttonwrapper floatRight spaceRight" ><a class="ovalbutton ovalbutton_orange"  href="#" onclick="phnAddMarker(\''.$usr_id.'\',\''.$marker.'\'); return false;"><span>'.$lang['srv_call_prekinjen'].'</span></a></span>';
			}
		}
		echo '<span class="buttonwrapper floatRight spaceRight" ><a class="ovalbutton ovalbutton_gray"  href="#" onclick="$(\'#telephone_popup\').hide(); $(\'#fade\').fadeOut(\'slow\'); "><span>'.$lang['srv_cancel'].'</span></a></span>';
	}
	
	// Nastavimo filter za pregled respondentov
	function setRecipientFilter(){
	
		session_start();
		
		$_SESSION['inv_filter']['value'] = trim($_POST['tel_filter_value']);
		
		session_commit();
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

	// Pobrisemo zadnji status respondenta (undo)
	private function undoLastStatus(){
		
		if(isset($_POST['usr_id'])){
			$usr_id = $_POST['usr_id'];
			
			$sql = sisplet_query("DELETE FROM srv_telephone_history WHERE rec_id='".$usr_id."' AND survey_id='".$this->sid."' ORDER BY insert_time DESC LIMIT 1");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		}
	}
}
