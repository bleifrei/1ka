<?php
/**
 * 
 * MAZA - mobilna aplikacija za anketirance
 * Class za posiljanje sporocil uporabnikom (v mojih anketah)
 * 
 * Uroš Podkrižnik 16.10.2017
 */
class MAZA {

    var $_ank_id;
    var $_ank_title;
    var $_ank_link;
    var $isRepeaterRunning;
    //if change is needed, synch with mobile app
    var $nextpin_token_prefix = '1KAPanel_';

    function __construct($ank_id = 0) {
        $this->_ank_id = $ank_id;
        //get survey title and link
        if($ank_id > 0){
            SurveyInfo::getInstance()->SurveyInit($ank_id);
            $this->_ank_title = SurveyInfo::getSurveyColumn('naslov');
            $this->_ank_link = SurveyInfo::getSurveyLink();
        }
    }

    public function display() {
        global $admin_type, $lang;

        // Izpis vseh poslanih sporocil
        if ($admin_type == 0) {
            //navigacija
            $this->displayNavigation();
            
            //is survey activated
            $act = sisplet_query("SELECT active FROM srv_anketa WHERE id='" . $this->_ank_id . "'", 'obj');
            if($act->active != 1)
                echo '<p class="red">'.$lang['srv_anketa_noactive2'].'<p>';

            $_sub_action = isset($_GET['m']) ? $_GET['m'] : 'maza_dashboard';

            $active_step[] = array(1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '', 7 => '');

            switch ($_sub_action) {
                case 'maza_dashboard':
                    $this -> surveyDescription();
                    echo '<br>';
                    $this -> getUsersStatistics();
                    echo '<br>';
                    $this -> generateNewUsersForm();
                    echo '<br>';
                    $this -> exportIdentifiersForm();
                    break;

                case 'maza_send_notification':
                    // obrazec za posiljanje sporocil
                    $this->sendMessageForm();
                    break;

                case 'maza_set_alarm':
                    // obrazec za posiljanje alarmov
                    $this->setRepeaterForm();
                    echo '<br>';
                    $this->setAlarmForm();
                    echo '<script type="text/javascript">onAlarmsFormsLoad();</script>';
                    break;

                case 'maza_set_geofencing':
                    //obrazec za posiljanje geofences
                    $sql_r = sisplet_query("SELECT COUNT(geofence_on) AS cnt FROM maza_srv_geofences WHERE ank_id='" . $this->_ank_id . "' AND geofence_on=1", 'obj');
                    $this->setGeofencesForm($sql_r->cnt);
                    echo '<script type="text/javascript">onGeofencingFormsLoad('.$this->_ank_id.', '.($sql_r->cnt>0).');</script>';
                    break;
                
                case 'maza_set_activity':
                    //obrazec za posiljanje aktivnosti
                    $this->setActivityForm();
                    break;
                
                case 'maza_set_tracking':
                    //obrazec za posiljanje sledenja
                    $this->setTrackingForm();
                    //echo '<script type="text/javascript">onTrackingFormsLoad();</script>';
                    break;
                
                case 'maza_set_entry':
                    //obrazec za posiljanje sledenja
                    $this->setEntryForm();
                    //echo '<script type="text/javascript">onTrackingFormsLoad();</script>';
                    break;

                default:
                    $active_step['1'] = ' active';
                    break;
            }
            if(isset($_GET['FCM_response']))
                echo '<br><br>'.$_GET['FCM_response'];
        }
    }

    function displayNavigation() {
        global $lang;

        $_sub_action = isset($_GET['m']) ? $_GET['m'] : 'maza_dashboard';

        $active_step[] = array(1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '', 7 => '');

        switch ($_sub_action) {
            case 'maza_dashboard':
                $active_step['1'] = ' active';
                break;

            case 'maza_send_notification':
                $active_step['2'] = ' active';
                break;

            case 'maza_set_alarm':
                $active_step['3'] = ' active';
                break;

            case 'maza_set_geofencing':
                $active_step['4'] = ' active';
                break;
            
            case 'maza_set_activity':
                $active_step['5'] = ' active';
                break;
            
            case 'maza_set_tracking':
                $active_step['6'] = ' active';
                break;
            
            case 'maza_set_entry':
                $active_step['7'] = ' active';
                break;

            default:
                $active_step['1'] = ' active';
                break;
        }

        echo '<div class="secondNavigation">';
        echo '<ul class="secondNavigation">';

        echo'<li>';
        echo '<a class="no-img ' . $active_step[1] . '" href="' . $this->addUrl('maza_dashboard') . '">';
        echo '<span class="label">' . $lang['srv_telephone_navi_dashboard'] . '</span>';
        echo '</a>';
        echo'</li>';

        #space
        echo'<li class="space">';

        #navigacija
        echo'<li>';
        echo '<a class="no-img ' . $active_step[2] . '" href="' . $this->addUrl('maza_send_notification') . '">';
        echo '<span class="label">' . $lang['srv_maza_send_notification'] . '</span>';
        echo '</a>';
        echo'</li>';

        echo'<li class="space">';

        echo'<li>';
        echo '<a class="no-img ' . $active_step[3] . '" href="' . $this->addUrl('maza_set_alarm') . '">';
        echo '<span class="label">' . $lang['srv_maza_send_alarm'] . '</span>';
        echo '</a>';
        echo'</li>';

        echo'<li class="space">';

        echo'<li>';
        echo '<div >';
        echo '<a class="no-img ' . $active_step[4] . '" href="' . $this->addUrl('maza_set_geofencing') . '">';
        echo '<span class="label">'. $lang['srv_maza_geofencing'] .'</span>';
        echo '</a>';
        echo '</div>';
        echo'</li>';
                
        echo'<li class="space">';

        echo'<li>';
        echo '<div >';
        echo '<a class="no-img ' . $active_step[6] . '" href="' . $this->addUrl('maza_set_tracking') . '">';
        echo '<span class="label">'. $lang['srv_maza_tracking'] .'</span>';
        echo '</a>';
        echo '</div>';
        echo'</li>';
        
        echo'<li class="space">';

        echo'<li>';
        echo '<div >';
        echo '<a class="no-img ' . $active_step[5] . '" href="' . $this->addUrl('maza_set_activity') . '">';
        echo '<span class="label">'. $lang['srv_maza_activity'] .'</span>';
        echo '</a>';
        echo '</div>';
        echo'</li>';
                
        echo'<li class="space">';

        echo'<li>';
        echo '<div >';
        echo '<a class="no-img ' . $active_step[7] . '" href="' . $this->addUrl('maza_set_entry') . '">';
        echo '<span class="label">'. $lang['srv_maza_entry'] .'</span>';
        echo '</a>';
        echo '</div>';
        echo'</li>';

        echo'</ul>';
        echo '</div>';

        echo '<br class="clr" />';
        echo '<br class="clr" />';
    }

    // Obrazec za posiljanje notificationa
    private function sendMessageForm() {
        global $lang;

        //FORM FOR NOTIFICATION
        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_send_notification'] . '</legend>';
        echo '<form name="maza_send_notification_form" id="maza_send_notification_form" method="post" action="ajax.php?t=MAZA&a=maza_send_notification">';
        /* echo '<span class="clr bold">'.$lang['srv_notifications_send_reciever'].': </span><input type="text" name="recipient" id="recipient">';

          // Checkboxa za posiljenje vsem uporabnikoom (slo in ang)
          echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="recipient_all_slo" id="recipient_all_slo" onClick="recipient_all_disable_email();"> <label for="recipient_all_slo"><span class="clr bold">'.$lang['srv_notifications_send_all_slo'].'</span></label></div>';
          echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="recipient_all_ang" id="recipient_all_ang" onClick="recipient_all_disable_email();"> <label for="recipient_all_ang"><span class="clr bold">'.$lang['srv_notifications_send_all_ang'].'</span></label></div><br />';
         */
        echo '<input type="hidden" name="maza_action" value="notification">';
        echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';
        echo '<span class="clr bold">' . $lang['srv_notifications_send_title'] . ': </span><input type="text" name="maza_title" id="maza_title" size="35" maxlength="35"><br><br>';
        echo '<span class="clr bold">' . $lang['srv_notifications_send_text'] . ': </span><input type="text" name="maza_message" id="maza_message" size="45" maxlength="45"></textarea><br><br>';

        echo '<label><input type="checkbox" id="maza_notification_priority" name="maza_notification_priority" value="1" />';
        echo $lang['srv_maza_notification_priority'] . '</label><br><br>';

        //echo '<label><input type="checkbox" id="maza_notification_sound" name="maza_notification_sound" value="1" />';
        //echo $lang['srv_maza_notification_sound'] . '</label><br><br>';

        echo '<span class="floatLeft spaceRight"><div class="buttonwrapper">'
        . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#maza_send_notification_form\').submit();">';
        echo $lang['srv_notifications_send'];
        echo '</a></div></span>';

        echo '<br><br><p id="maza_result">' . $_POST['maza_result'] . '</p>';

        echo '</form>';
        echo '</fieldset><br><br>';
        
        //FORM FOR WEB PUSH NOTIFICATIONs
        /*echo '<fieldset>';
        echo '<legend>PWA notification (test and fixed)</legend>';
        echo '<form name="maza_send_notification_form_pwa" id="maza_send_notification_form_pwa" method="post" action="ajax.php?t=MAZA&a=maza_send_notification_pwa">';
        /* echo '<span class="clr bold">'.$lang['srv_notifications_send_reciever'].': </span><input type="text" name="recipient" id="recipient">';

          // Checkboxa za posiljenje vsem uporabnikoom (slo in ang)
          echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="recipient_all_slo" id="recipient_all_slo" onClick="recipient_all_disable_email();"> <label for="recipient_all_slo"><span class="clr bold">'.$lang['srv_notifications_send_all_slo'].'</span></label></div>';
          echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="recipient_all_ang" id="recipient_all_ang" onClick="recipient_all_disable_email();"> <label for="recipient_all_ang"><span class="clr bold">'.$lang['srv_notifications_send_all_ang'].'</span></label></div><br />';
         */
        /*echo '<input type="hidden" name="maza_action" value="notification">';
        echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';
        echo '<span class="clr bold">' . $lang['srv_notifications_send_title'] . ': </span><input type="text" name="wpn_title" id="wpn_title" size="35" value="You have new survey!" maxlength="35"><br><br>';
        echo '<span class="clr bold">' . $lang['srv_notifications_send_text'] . ': </span><input type="text" name="wpn_message" id="wpn_message" size="45" value="'.$this->_ank_title.'" maxlength="45"></textarea><br><br>';

        //echo '<label><input type="checkbox" id="maza_notification_priority" name="maza_notification_priority" value="1" />';
        //echo $lang['srv_maza_notification_priority'] . '</label><br><br>';

        //echo '<label><input type="checkbox" id="maza_notification_sound" name="maza_notification_sound" value="1" />';
        //echo $lang['srv_maza_notification_sound'] . '</label><br><br>';

        echo '<span class="floatLeft spaceRight"><div class="buttonwrapper">'
        . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#maza_send_notification_form_pwa\').submit();">';
        echo $lang['srv_notifications_send'];
        echo '</a></div></span>';

        echo '<br><br><p id="maza_result">' . $_POST['maza_result'] . '</p>';

        echo '</form>';
        echo '</fieldset>';*/
    }

    // Obrazec za posiljanje alarmov
    private function setAlarmForm() {
        global $lang;

        //FORM FOR ALARM
        $sql_r = sisplet_query("SELECT * FROM maza_srv_alarms WHERE ank_id='" . $this->_ank_id . "'", 'obj');

        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_send_alarm'] . '</legend>';

        $disabled = '';
        if ($this->isRepeaterRunning && $sql_r->alarm_on == '1') {
            echo '<form name="maza_cancel_alarm_form" id="maza_cancel_alarm_form" method="post" action="ajax.php?t=MAZA&a=maza_cancel_alarm">';
            echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';
            //$disabled = ' disabled="disabled"';
            echo '<i class="red">' . $lang['srv_maza_alarm_on'] . '</i><br>';

            ///////////////////////////////////AKCIJA
            echo '<span class="spaceRight floatLeft"><div class="buttonwrapper">'
            . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#maza_cancel_alarm_form\').submit();">';
            echo $lang['srv_maza_alarm_turn_off'];
            echo '</a></div></span><br><br>';
            echo '</form>';
        }

        //form to set or update alarm
        echo '<form name="maza_send_alarm_form" id="maza_send_alarm_form" method="post" action="ajax.php?t=MAZA&a=maza_send_notification">';
        /* echo '<span class="clr bold">'.$lang['srv_notifications_send_reciever'].': </span><input type="text" name="recipient" id="recipient">';

          // Checkboxa za posiljenje vsem uporabnikoom (slo in ang)
          echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="recipient_all_slo" id="recipient_all_slo" onClick="recipient_all_disable_email();"> <label for="recipient_all_slo"><span class="clr bold">'.$lang['srv_notifications_send_all_slo'].'</span></label></div>';
          echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="recipient_all_ang" id="recipient_all_ang" onClick="recipient_all_disable_email();"> <label for="recipient_all_ang"><span class="clr bold">'.$lang['srv_notifications_send_all_ang'].'</span></label></div><br />';
         */

        if ((sizeof($sql_r) == 0 || $sql_r->alarm_on == '0') && $this->isRepeaterRunning)
            echo '<i>' . $lang['srv_maza_alarm_off'] . '</i><br>';
        else
            echo '<fieldset disabled="disabled">';

        echo '<input type="hidden" name="maza_action" value="alarm">';
        echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';
        
        echo '<div style="overflow: hidden;">';
        echo '<div style="float: left;margin-right:5em;">';
        
        $sql_r->alarm_notif_title = ($sql_r->alarm_notif_title) ? $sql_r->alarm_notif_title : $lang['srv_maza_alarm_default_title'];
        $sql_r->alarm_notif_message = ($sql_r->alarm_notif_message) ? $sql_r->alarm_notif_message : $lang['srv_maza_alarm_default_message'].$this->_ank_title;
        echo '<span class="clr bold">' . $lang['srv_notifications_send_title'] . ': </span><input type="text" name="maza_title" id="maza_title" size="35" maxlength="35" '
        . $disabled . 'value="' . $sql_r->alarm_notif_title . '" disabled="disabled"><br><br>';
        echo '<span class="clr bold">' . $lang['srv_notifications_send_text'] . ': </span><input type="text" name="maza_message" id="maza_message" size="45" maxlength="45" '
        . $disabled . 'value="' . $sql_r->alarm_notif_message . '"><br><br>';

        /*echo '<label><input type="checkbox" id="maza_notification_priority" name="maza_notification_priority" ' . $disabled . ' value="1" />';
        echo $lang['srv_maza_notification_priority'] . '</label><br><br>';*/

        //echo '<label><input type="checkbox" id="maza_notification_sound" name="maza_notification_sound" ' . $disabled . ' value="1" ' . (($sql_r->alarm_notif_sound == 1) ? 'checked' : '') . '/>';
        //echo $lang['srv_maza_notification_sound'] . '</label><br><br>';

        echo '</div>';
        
        echo '<div style="float: left;">';
        
        echo '<span class="clr bold">' . $lang['srv_maza_alarm_when_to_show'] . '</span>';

        echo '
        <label class="middle"><input type="radio" value="everyday" name="maza_alarm_intervalby" '. (($sql_r->repeat_by == 'everyday') ? "checked" : "")  .'>' . $lang['srv_maza_alarm_intervalby_every_day'] . '</label>
        <label class="middle"><input type="radio" value="daily" name="maza_alarm_intervalby" '. (($sql_r->repeat_by == 'daily') ? "checked" : "")  .'>' . $lang['srv_maza_alarm_intervalby_daily'] . '</label>
        <label class="middle"><input type="radio" value="weekly" name="maza_alarm_intervalby" '. (($sql_r->repeat_by == 'weekly') ? "checked" : "")  .'>' . $lang['srv_maza_alarm_intervalby_weekly'] . '</label><br><br>';
        
        echo '<div id="maza_alarm_div_daily" '. (($sql_r->repeat_by == 'daily') ? '' : 'style="display: none;"')  .'>';
        echo '<span class="clr bold">' . $lang['srv_maza_alarm_daily_every'] . ': </span><input style="float: none;" type="number" name="maza_alarm_everywhichday"'
        . ' min="2" max="6" ' . $disabled . ' value="' . $sql_r->every_which_day . '">'
        . '<span class="clr"> ' . $lang['srv_maza_alarm_days'] . '</span><br></div>';
        
        $weekly_array = (isset($sql_r->day_in_week) && $sql_r->day_in_week != 'null') ? json_decode($sql_r->day_in_week) : array();
        echo '<div id="maza_alarm_div_weekly" '. (($sql_r->repeat_by == 'weekly') ? '' : 'style="display: none;"')  .'>';
        echo '<span class="clr bold">' . $lang['srv_maza_alarm_day_week'] . ':</span>';
        echo '<table>';
        //echo '<caption><span class="clr bold">At time in day:</span></caption>';
        echo '<tr><td><label class="middle"><input type="checkbox" value="1" name="maza_alarm_dayinweek[]" '. (in_array("1", $weekly_array) ? 'checked' : '') .'>' . $lang['Mon'] . '</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="2" name="maza_alarm_dayinweek[]" '. (in_array("2", $weekly_array) ? 'checked' : '') .'>' . $lang['Tue'] . '</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="3" name="maza_alarm_dayinweek[]" '. (in_array("3", $weekly_array) ? 'checked' : '') .'>' . $lang['Wed'] . '</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="4" name="maza_alarm_dayinweek[]" '. (in_array("4", $weekly_array) ? 'checked' : '') .'>' . $lang['Thu'] . '</label></td></tr>';
        echo '<td><label class="middle"><input type="checkbox" value="5" name="maza_alarm_dayinweek[]" '. (in_array("5", $weekly_array) ? 'checked' : '') .'>' . $lang['Fri'] . '</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="6" name="maza_alarm_dayinweek[]" '. (in_array("6", $weekly_array) ? 'checked' : '') .'>' . $lang['Sat'] . '</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="7" name="maza_alarm_dayinweek[]" '. (in_array("7", $weekly_array) ? 'checked' : '') .'>' . $lang['Sun'] . '</label></td></tr>';
        echo '</table><br></div>';
        
        $time_array = (isset($sql_r->time_in_day) && $sql_r->time_in_day != 'null') ? json_decode($sql_r->time_in_day) : array();
        echo '<span class="clr bold">' . $lang['srv_maza_alarm_time_day'] . ':</span>';
        echo '<table>';
        //echo '<caption><span class="clr bold">At time in day:</span></caption>';
        echo '<tr><td><label class="middle"><input type="checkbox" value="0600" name="maza_alarm_timeinday[]" '. (in_array("0600", $time_array) ? 'checked' : '') .'>6:00 </label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0700" name="maza_alarm_timeinday[]" '. (in_array("0700", $time_array) ? 'checked' : '') .'>7:00 </label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0800" name="maza_alarm_timeinday[]" '. (in_array("0800", $time_array) ? 'checked' : '') .'>8:00 </label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0900" name="maza_alarm_timeinday[]" '. (in_array("0900", $time_array) ? 'checked' : '') .'>9:00 </label></td></tr>';
        echo '<td><label class="middle"><input type="checkbox" value="1000" name="maza_alarm_timeinday[]" '. (in_array("1000", $time_array) ? 'checked' : '') .'>10:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1100" name="maza_alarm_timeinday[]" '. (in_array("1100", $time_array) ? 'checked' : '') .'>11:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1200" name="maza_alarm_timeinday[]" '. (in_array("1200", $time_array) ? 'checked' : '') .'>12:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1300" name="maza_alarm_timeinday[]" '. (in_array("1300", $time_array) ? 'checked' : '') .'>13:00</label></td></tr>';
        echo '<td><label class="middle"><input type="checkbox" value="1400" name="maza_alarm_timeinday[]" '. (in_array("1400", $time_array) ? 'checked' : '') .'>14:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1500" name="maza_alarm_timeinday[]" '. (in_array("1500", $time_array) ? 'checked' : '') .'>15:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1600" name="maza_alarm_timeinday[]" '. (in_array("1600", $time_array) ? 'checked' : '') .'>16:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1700" name="maza_alarm_timeinday[]" '. (in_array("1700", $time_array) ? 'checked' : '') .'>17:00</label></td></tr>';
        echo '<td><label class="middle"><input type="checkbox" value="1800" name="maza_alarm_timeinday[]" '. (in_array("1800", $time_array) ? 'checked' : '') .'>18:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1900" name="maza_alarm_timeinday[]" '. (in_array("1900", $time_array) ? 'checked' : '') .'>19:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="2000" name="maza_alarm_timeinday[]" '. (in_array("2000", $time_array) ? 'checked' : '') .'>20:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="2100" name="maza_alarm_timeinday[]" '. (in_array("2100", $time_array) ? 'checked' : '') .'>21:00</label></td></tr>';
        echo '<td><label class="middle"><input type="checkbox" value="2200" name="maza_alarm_timeinday[]" '. (in_array("2200", $time_array) ? 'checked' : '') .'>22:00</label></td>';
        echo '</table>';
        
        echo '</div>';
        echo '</div>';
        
        if ((sizeof($sql_r) == 0 || $sql_r->alarm_on == '0') && $this->isRepeaterRunning){
            echo '<span id="maza_submit_alarms" class="floatLeft spaceRight" style="display:none;"><div class="buttonwrapper">'
            . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#maza_send_alarm_form\').submit();">';
            echo $lang['srv_notifications_save_send'];
            echo '</a></div></span>';
        }
        else
            echo '</fieldset>';
        
        echo '<p id="maza_result">' . $_POST['maza_result'] . '</p>';
        
        echo '</form>';
        echo '</fieldset>';
                                
        echo '<br>';
        
        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_data_export'] . '</legend>';
        echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=maza_csv&anketa=' . $this->_ank_id) . '&m=maza_csv&a=alarm_respondents" class="srv_ico">'
                . '<span class="hover_export_icon"><span class="sprites xls_large"></span></span>' . $lang['srv_maza_alarm_export_respondents'] . '</a>';
        echo '</fieldset>';
    }
    
    // Obrazec za posiljanje alarmov
    private function setRepeaterForm() {
        global $lang;

        //FORM FOR ALARM
        $sql_r = sisplet_query("SELECT * FROM maza_srv_repeaters WHERE ank_id='" . $this->_ank_id . "'", 'obj');
        
        $dateToday = DateTime::createFromFormat('Y-m-d', date("Y-m-d"));
        //if start date of repeater exists
        if($sql_r->datetime_start){
            $dateTimeStart = new DateTime($sql_r->datetime_start);
            $dateStart = DateTime::createFromFormat('Y-m-d', $dateTimeStart->format('Y-m-d'));
            //is repeater start date <= of today? if yes, repeater has started
            $hasRepeaterStarted = ($dateStart->getTimestamp() <= $dateToday->getTimestamp());
            $this->isRepeaterRunning = $hasRepeaterStarted && $sql_r->repeater_on == '1';
            $dateTimeStart = $dateTimeStart->format('d.m.Y');
        }
        else
            $dateTimeStart = "";
        
        //if end date of repeater exists
        if($sql_r->datetime_end){
            $dateTimeEnd = new DateTime($sql_r->datetime_end);
            $dateEnd = DateTime::createFromFormat('Y-m-d', $dateTimeEnd->format('Y-m-d'));
            //is repeater end date < of today? if yes, repeater has ended
            $hasRepeaterEnd = ($dateEnd->getTimestamp() < $dateToday->getTimestamp());
            $this->isRepeaterRunning = $hasRepeaterStarted && $sql_r->repeater_on == '1' && !$hasRepeaterEnd;
            $dateTimeEnd = $dateTimeEnd->format('d.m.Y');
        }
        else
            $dateTimeEnd = "";

        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_repeater_title'] . '</legend>';

        $disabled = '';
        if ($this->isRepeaterRunning) {
            echo '<form name="maza_cancel_repeater_form" id="maza_cancel_repeater_form" method="post" action="ajax.php?t=MAZA&a=cancelRepeater">';
            echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';
            //$disabled = ' disabled="disabled"';
            echo '<i class="red">' . $lang['srv_maza_repeater_on'] . '</i><br>';

            ///////////////////////////////////AKCIJA
            echo '<span id="maza_cancel_repeater" class="floatLeft spaceRight"><div class="buttonwrapper">'
            . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="maza_repeater_cancel_click();">';
            echo $lang['srv_maza_repeater_cancel_repeater'];
            echo '</a></div></span><br><br>';
            echo '</form>';
        }

        //form to set or update alarm
        echo '<form name="maza_save_repeater_form" id="maza_save_repeater_form" method="post" action="ajax.php?t=MAZA&a=maza_save_repeater">';
        /* echo '<span class="clr bold">'.$lang['srv_notifications_send_reciever'].': </span><input type="text" name="recipient" id="recipient">';

          // Checkboxa za posiljenje vsem uporabnikoom (slo in ang)
          echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="recipient_all_slo" id="recipient_all_slo" onClick="recipient_all_disable_email();"> <label for="recipient_all_slo"><span class="clr bold">'.$lang['srv_notifications_send_all_slo'].'</span></label></div>';
          echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="recipient_all_ang" id="recipient_all_ang" onClick="recipient_all_disable_email();"> <label for="recipient_all_ang"><span class="clr bold">'.$lang['srv_notifications_send_all_ang'].'</span></label></div><br />';
         */
        
        if ($sql_r->repeater_on > 0 && $hasRepeaterStarted)
            echo '<fieldset disabled="disabled">';
        
        //echo '<input type="hidden" name="maza_action" value="repeater">';
        echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';
        
        //echo '<div style="float: left;">';
        
        echo '<div><div><span class="clr bold">' . $lang['srv_maza_repeater_when_to_start'] . '</span>';
        echo '<input type="text" id="maza_repeater_date_start" name="maza_repeater_date_start" value="' . $dateTimeStart . '" />';
        if (!$hasRepeaterStarted && $dateTimeStart)
            echo '<i id="maza_repeater_start_date_warning"> '.$lang['srv_maza_repeater_edit_warning'].'</i>';
        echo '</div><br>';
        
        echo '<div><span class="clr bold">' . $lang['srv_maza_repeater_when_to_end'] . '</span>';
        echo '<input type="text" id="maza_repeater_date_end" name="maza_repeater_date_end" class="" value="' . $dateTimeEnd . '" />';
        echo '</div></div><br><br>';
        
        echo '<div><span class="clr bold">' . $lang['srv_maza_repeater_when_to_repeat'] . '</span>';
        echo '
        <label class="middle"><input type="radio" value="everyday" name="maza_repeater_intervalby" '. (($sql_r->repeat_by == 'everyday') ? "checked" : "")  .'>' . $lang['srv_maza_alarm_intervalby_every_day'] . '</label>
        <label class="middle"><input type="radio" value="daily" name="maza_repeater_intervalby" '. (($sql_r->repeat_by == 'daily') ? "checked" : "")  .'>' . $lang['srv_maza_alarm_intervalby_daily'] . '</label>
        <label class="middle"><input type="radio" value="weekly" name="maza_repeater_intervalby" '. (($sql_r->repeat_by == 'weekly') ? "checked" : "")  .'>' . $lang['srv_maza_alarm_intervalby_weekly'] . '</label></div><br><br>';
        
        echo '<div id="maza_repeater_div_daily" '. (($sql_r->repeat_by == 'daily') ? '' : 'style="display: none;"')  .'>';
        echo '<span class="clr bold">' . $lang['srv_maza_alarm_daily_every'] . ': </span><input style="float: none;" type="number" name="maza_repeater_everywhichday"'
        . ' min="2" max="6" ' . $disabled . ' value="' . $sql_r->every_which_day . '">'
        . '<span class="clr"> ' . $lang['srv_maza_alarm_days'] . '</span><br></div>';
        
        $weekly_array = (isset($sql_r->day_in_week) && $sql_r->day_in_week != 'null') ? json_decode($sql_r->day_in_week) : array();
        echo '<div id="maza_repeater_div_weekly" '. (($sql_r->repeat_by == 'weekly') ? '' : 'style="display: none;"')  .'>';
        echo '<span class="clr bold">' . $lang['srv_maza_alarm_day_week'] . ':</span>';
        echo '<table>';
        //echo '<caption><span class="clr bold">At time in day:</span></caption>';
        echo '<tr><td><label class="middle"><input type="checkbox" value="1" name="maza_repeater_dayinweek[]" '. (in_array("1", $weekly_array) ? 'checked' : '') .'>' . $lang['Mon'] . '</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="2" name="maza_repeater_dayinweek[]" '. (in_array("2", $weekly_array) ? 'checked' : '') .'>' . $lang['Tue'] . '</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="3" name="maza_repeater_dayinweek[]" '. (in_array("3", $weekly_array) ? 'checked' : '') .'>' . $lang['Wed'] . '</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="4" name="maza_repeater_dayinweek[]" '. (in_array("4", $weekly_array) ? 'checked' : '') .'>' . $lang['Thu'] . '</label></td></tr>';
        echo '<td><label class="middle"><input type="checkbox" value="5" name="maza_repeater_dayinweek[]" '. (in_array("5", $weekly_array) ? 'checked' : '') .'>' . $lang['Fri'] . '</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="6" name="maza_repeater_dayinweek[]" '. (in_array("6", $weekly_array) ? 'checked' : '') .'>' . $lang['Sat'] . '</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="7" name="maza_repeater_dayinweek[]" '. (in_array("7", $weekly_array) ? 'checked' : '') .'>' . $lang['Sun'] . '</label></td></tr>';
        echo '</table><br></div>';
        
        $time_array = (isset($sql_r->time_in_day) && $sql_r->time_in_day != 'null') ? json_decode($sql_r->time_in_day) : array();
        echo '<span class="clr bold">' . $lang['srv_maza_alarm_time_day'] . ':</span>';
        echo '<table id="maza_repeater_div_everyday">';
        //echo '<caption><span class="clr bold">At time in day:</span></caption>';
        echo '<tr><td><label class="middle"><input type="checkbox" value="0000" name="maza_repeater_timeinday[]" '. (in_array("0000", $time_array) ? 'checked' : '') .'>0:00 </label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0030" name="maza_repeater_timeinday[]" '. (in_array("0030", $time_array) ? 'checked' : '') .'>0:30 </label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0100" name="maza_repeater_timeinday[]" '. (in_array("0100", $time_array) ? 'checked' : '') .'>1:00 </label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0130" name="maza_repeater_timeinday[]" '. (in_array("0130", $time_array) ? 'checked' : '') .'>1:30 </label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0200" name="maza_repeater_timeinday[]" '. (in_array("0200", $time_array) ? 'checked' : '') .'>2:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0230" name="maza_repeater_timeinday[]" '. (in_array("0230", $time_array) ? 'checked' : '') .'>2:30</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0300" name="maza_repeater_timeinday[]" '. (in_array("0300", $time_array) ? 'checked' : '') .'>3:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0330" name="maza_repeater_timeinday[]" '. (in_array("0330", $time_array) ? 'checked' : '') .'>3:30</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0400" name="maza_repeater_timeinday[]" '. (in_array("0400", $time_array) ? 'checked' : '') .'>4:00 </label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0430" name="maza_repeater_timeinday[]" '. (in_array("0430", $time_array) ? 'checked' : '') .'>4:30 </label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0500" name="maza_repeater_timeinday[]" '. (in_array("0500", $time_array) ? 'checked' : '') .'>5:00 </label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0530" name="maza_repeater_timeinday[]" '. (in_array("0530", $time_array) ? 'checked' : '') .'>5:30 </label></td></tr>';
        echo '<tr><td><label class="middle"><input type="checkbox" value="0600" name="maza_repeater_timeinday[]" '. (in_array("0600", $time_array) ? 'checked' : '') .'>6:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0630" name="maza_repeater_timeinday[]" '. (in_array("0630", $time_array) ? 'checked' : '') .'>6:30</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0700" name="maza_repeater_timeinday[]" '. (in_array("0700", $time_array) ? 'checked' : '') .'>7:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0730" name="maza_repeater_timeinday[]" '. (in_array("0730", $time_array) ? 'checked' : '') .'>7:30</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0800" name="maza_repeater_timeinday[]" '. (in_array("0800", $time_array) ? 'checked' : '') .'>8:00 </label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0830" name="maza_repeater_timeinday[]" '. (in_array("0830", $time_array) ? 'checked' : '') .'>8:30 </label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0900" name="maza_repeater_timeinday[]" '. (in_array("0900", $time_array) ? 'checked' : '') .'>9:00 </label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="0930" name="maza_repeater_timeinday[]" '. (in_array("0930", $time_array) ? 'checked' : '') .'>9:30 </label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1000" name="maza_repeater_timeinday[]" '. (in_array("1000", $time_array) ? 'checked' : '') .'>10:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1030" name="maza_repeater_timeinday[]" '. (in_array("1030", $time_array) ? 'checked' : '') .'>10:30</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1100" name="maza_repeater_timeinday[]" '. (in_array("1100", $time_array) ? 'checked' : '') .'>11:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1130" name="maza_repeater_timeinday[]" '. (in_array("1130", $time_array) ? 'checked' : '') .'>11:30</label></td></tr>';
        echo '<tr><td><label class="middle"><input type="checkbox" value="1200" name="maza_repeater_timeinday[]" '. (in_array("1200", $time_array) ? 'checked' : '') .'>12:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1230" name="maza_repeater_timeinday[]" '. (in_array("1230", $time_array) ? 'checked' : '') .'>12:30</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1300" name="maza_repeater_timeinday[]" '. (in_array("1300", $time_array) ? 'checked' : '') .'>13:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1330" name="maza_repeater_timeinday[]" '. (in_array("1330", $time_array) ? 'checked' : '') .'>13:30</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1400" name="maza_repeater_timeinday[]" '. (in_array("1400", $time_array) ? 'checked' : '') .'>14:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1430" name="maza_repeater_timeinday[]" '. (in_array("1430", $time_array) ? 'checked' : '') .'>14:30</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1500" name="maza_repeater_timeinday[]" '. (in_array("1500", $time_array) ? 'checked' : '') .'>15:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1530" name="maza_repeater_timeinday[]" '. (in_array("1530", $time_array) ? 'checked' : '') .'>15:30</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1600" name="maza_repeater_timeinday[]" '. (in_array("1600", $time_array) ? 'checked' : '') .'>16:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1630" name="maza_repeater_timeinday[]" '. (in_array("1630", $time_array) ? 'checked' : '') .'>16:30</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1700" name="maza_repeater_timeinday[]" '. (in_array("1700", $time_array) ? 'checked' : '') .'>17:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1730" name="maza_repeater_timeinday[]" '. (in_array("1730", $time_array) ? 'checked' : '') .'>17:30</label></td></tr>';
        echo '<tr><td><label class="middle"><input type="checkbox" value="1800" name="maza_repeater_timeinday[]" '. (in_array("1800", $time_array) ? 'checked' : '') .'>18:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1830" name="maza_repeater_timeinday[]" '. (in_array("1830", $time_array) ? 'checked' : '') .'>18:30</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1900" name="maza_repeater_timeinday[]" '. (in_array("1900", $time_array) ? 'checked' : '') .'>19:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="1930" name="maza_repeater_timeinday[]" '. (in_array("1930", $time_array) ? 'checked' : '') .'>19:30</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="2000" name="maza_repeater_timeinday[]" '. (in_array("2000", $time_array) ? 'checked' : '') .'>20:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="2030" name="maza_repeater_timeinday[]" '. (in_array("2030", $time_array) ? 'checked' : '') .'>20:30</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="2100" name="maza_repeater_timeinday[]" '. (in_array("2100", $time_array) ? 'checked' : '') .'>21:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="2130" name="maza_repeater_timeinday[]" '. (in_array("2130", $time_array) ? 'checked' : '') .'>21:30</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="2200" name="maza_repeater_timeinday[]" '. (in_array("2200", $time_array) ? 'checked' : '') .'>22:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="2230" name="maza_repeater_timeinday[]" '. (in_array("2230", $time_array) ? 'checked' : '') .'>22:30</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="2300" name="maza_repeater_timeinday[]" '. (in_array("2300", $time_array) ? 'checked' : '') .'>23:00</label></td>';
        echo '<td><label class="middle"><input type="checkbox" value="2330" name="maza_repeater_timeinday[]" '. (in_array("2330", $time_array) ? 'checked' : '') .'>23:30</label></td></tr>';
        echo '</table>';
        
        //echo '</div>';
        echo '<br>';
        
        //repeater not in motion yet
        if ((sizeof($sql_r) == 0 || $sql_r->repeater_on < 2) && !$hasRepeaterStarted){
            echo '<span id="maza_submit_repeater" class="floatLeft spaceRight" style="display:none;"><div class="buttonwrapper">'
            . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="maza_repeater_submit_button_click();">';
            echo $lang['save'];
            echo '</a></div></span>';
        }
        //repeater ended
        else
            echo '</fieldset>';
        
        echo '<p id="maza_result">' . $_POST['maza_result'] . '</p>';
        
        echo '</form>';
        echo '</fieldset>';
    }
    
    /**
     * Obrazec za posiljanje geofencev
     * @global type $admin_type
     * @global type $global_user_id
     * @global type $lang
     * @param type $geocnt - count of all geofences that are on
     */
    private function setGeofencesForm($geocnt) {
        global $lang;

        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_geofencing'] . '</legend>';
        
        //FORM FOR GEOFENCES
        if ($geocnt > 0) {
            echo '<form name="maza_cancel_geofencing_form" id="maza_cancel_geofencing_form" method="post" action="ajax.php?t=MAZA&a=maza_cancel_geofencing">';
            echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';
            //$disabled = ' disabled="disabled"';
            echo '<i class="red">' . $lang['srv_maza_geofencing_on'] . '</i><br>';

            ///////////////////////////////////AKCIJA
            echo '<span class="spaceRight floatLeft"><div class="buttonwrapper">'
            . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#maza_cancel_geofencing_form\').submit();">';
            echo $lang['srv_maza_geofencing_turn_off'];
            echo '</a></div></span><br><br>';
            echo '</form>';
        }
        else
            echo '<i>' . $lang['srv_maza_geofencing_off'] . '</i><br>';

        //form to set or update GEOFENCES
        echo '<form name="maza_send_geofences_form" id="maza_send_geofences_form" method="post" action="ajax.php?t=MAZA&a=maza_run_geofences">';

        echo '<input type="hidden" name="maza_action" value="geofencing">';
        echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';

        //izrisi search box za v mapo
        echo '<input id="pac-input_maza_map_geofencing" class="pac-input" type="text" style="display:none" onkeypress="return event.keyCode != 13;">';

        //DIV for google map
        echo '<div id="maza_map_geofencing" style="width:100%;height:400px;margin:0px 30px 0px 0px;border-style: solid;border-width: 1px;border-color: #b4b3b3;"></div>';
        echo '<br>';
        
        //NOTIFICATION SETTINGS
        //FORM FOR geofence notification
        $sql_r = sisplet_query("SELECT * FROM maza_srv_geofences WHERE ank_id='" . $this->_ank_id . "' LIMIT 1", 'obj');
        
        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_geofencing_notification'] . '</legend>';
        
        echo '<div style="overflow: hidden;">';
        echo '<div style="float: left;margin-right:5em;">';
        
        if(!$sql_r)
            $sql_r = (object)array('notif_title'=>'', 'notif_message'=>'', 'notif_sound'=>1, 'trigger_survey' => "1", 'on_transition'=>'dwell', 'after_seconds' => 300);    
        
        $disable = ($sql_r->trigger_survey == null) ? ' disabled="disabled"' : '';
        
        echo '<div>';
        echo '<label><input type="checkbox" id="maza_geofence_trigger_survey" name="maza_geofence_trigger_survey" value="1" ' . (($sql_r->trigger_survey == null) ? 'checked' : '') . ' />';
        echo $lang['srv_maza_geofence_dont_trigger_survey'] . '</label><br><br>';
        echo '</div>';
        
        $sql_r->notif_title = ($sql_r->notif_title) ? $sql_r->notif_title : $lang['srv_maza_geofence_default_title'];
        $sql_r->notif_message = ($sql_r->notif_message) ? $sql_r->notif_message : $lang['srv_maza_alarm_default_message'].$this->_ank_title;
        echo '<span class="clr bold">' . $lang['srv_notifications_send_title'] . ': </span><input type="text" name="maza_title" id="maza_title" size="35" maxlength="35" '
        . 'value="' . $sql_r->notif_title . '"'.$disable.'><br><br>';
        echo '<span class="clr bold">' . $lang['srv_notifications_send_text'] . ': </span><input type="text" name="maza_message" id="maza_message" size="45" maxlength="45" '
        . 'value="' . $sql_r->notif_message . '"'.$disable.'><br><br>';

        /*echo '<label><input type="checkbox" id="maza_notification_priority" name="maza_notification_priority" ' . $disabled . ' value="1" />';
        echo $lang['srv_maza_notification_priority'] . '</label><br><br>';*/

        //echo '<label><input type="checkbox" id="maza_notification_sound" name="maza_notification_sound" value="1" ' . (($sql_r->notif_sound == 1) ? 'checked' : '') . '/>';
        //echo $lang['srv_maza_notification_sound'] . '</label><br><br>';

        echo '</div>';
        
        echo '<div style="float: left;">';
        
        echo '<div>';
        echo '<label><input type="checkbox" id="maza_geofence_location_triggered" name="maza_geofence_location_triggered" value="1" ' . (($sql_r->location_triggered == 1) ? 'checked' : '') . ' />';
        echo $lang['srv_maza_geofence_location_triggered'] . '</label><br><br>';
        echo '</div>';
        
        echo '<span class="clr bold">' . $lang['srv_maza_alarm_when_to_show'] . '</span>';

        echo '
        <label class="middle"><input type="radio" value="dwell" name="maza_geofence_on_transition" '. (($sql_r->on_transition  == 'dwell') ? "checked" : "").'>' . $lang['srv_maza_geofence_dwell'] . '</label>
        <label class="middle"><input type="radio" value="exit" name="maza_geofence_on_transition" '. (($sql_r->on_transition  == 'exit') ? "checked" : "").'>' . $lang['srv_maza_geofence_exit'] . '</label><br><br>';
        
        echo '<div id="maza_alarm_div_daily">';
        echo '<span class="clr bold">' . $lang['srv_maza_geofence_after'] . ': </span><input style="float: none;" type="number" name="maza_geofence_after_time"'
        . ' value="' . $sql_r->after_seconds . '">'
        . '<span class="clr"> ' . $lang['srv_maza_geofence_time_unit'] . '</span><br></div>';
        
        echo '</div>';
        echo '</div>';
        echo '</fieldset><br>';
        
        echo '<span id="maza_submit_geofencing" class="floatLeft spaceRight"><div class="buttonwrapper">'
        . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#maza_send_geofences_form\').submit();">';
        echo $lang['srv_notifications_save_send'];
        echo '</a></div></span>';
        
        echo '<p id="maza_result">' . $_POST['maza_result'] . '</p>';
        
        echo '</form>';
        echo '</fieldset>';
     
        echo '<br>';
        
        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_data_export'] . '</legend>';
        echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=maza_csv&anketa=' . $this->_ank_id) . '&m=maza_csv&a=triggered_geofences_answers" target="_blank" class="srv_ico">'
                . '<span class="hover_export_icon"><span class="sprites xls_large"></span></span>' . $lang['srv_maza_geofence_export_triggered_answered'] . '</a>';
        echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=maza_csv&anketa=' . $this->_ank_id) . '&m=maza_csv&a=triggered_geofences" target="_blank" class="srv_ico">'
                . '<span class="hover_export_icon"><span class="sprites xls_large"></span></span>' . $lang['srv_maza_geofence_export_triggered'] . '</a>';
        echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=maza_csv&anketa=' . $this->_ank_id) . '&m=maza_csv&a=geofences" target="_blank" class="srv_ico">'
                . '<span class="hover_export_icon"><span class="sprites xls_large"></span></span>' . $lang['srv_maza_geofence_export'] . '</a>';
        echo '</fieldset>';
    }
    
    // Obrazec za posiljanje nextpin aktivnosti
    private function setActivityForm() {
        global $lang;

        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_activity'] . '</legend>';
        
        //FORM FOR activity
        $sql_r = sisplet_query("SELECT COUNT(activity_on) AS cnt FROM maza_srv_activity WHERE ank_id='" . $this->_ank_id . "' AND activity_on=1", 'obj');

        if ($sql_r->cnt > 0) {
            echo '<form name="maza_cancel_activity_form" id="maza_cancel_activity_form" method="post" action="ajax.php?t=MAZA&a=maza_cancel_activity">';
            echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';
            //$disabled = ' disabled="disabled"';
            echo '<i class="red">' . $lang['srv_maza_activity_on'] . '</i><br>';

            ///////////////////////////////////AKCIJA
            echo '<span class="spaceRight floatLeft"><div class="buttonwrapper">'
            . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#maza_cancel_activity_form\').submit();">';
            echo $lang['srv_maza_activity_turn_off'];
            echo '</a></div></span><br><br>';
            echo '</form>';
        }
        else
            echo '<i>' . $lang['srv_maza_activity_off'] . '</i><br>';

        //form to set or update activity
        echo '<form name="maza_send_activity_form" id="maza_send_activity_form" method="post" action="ajax.php?t=MAZA&a=maza_run_activity">';

        echo '<input type="hidden" name="maza_action" value="activity">';
        echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';
        
        //NOTIFICATION SETTINGS
        //FORM FOR activity notification
        $sql_r = sisplet_query("SELECT * FROM maza_srv_activity WHERE ank_id='" . $this->_ank_id . "' LIMIT 1", 'obj');
        
        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_geofencing_notification'] . '</legend>';
        
        echo '<div style="overflow: hidden;">';
        echo '<div style="float: left;margin-right:5em;">';
        
        if(!$sql_r)
            $sql_r = (object)array('notif_title'=>'', 'notif_message'=>'', 'notif_sound'=>1, 'activity_type'=>'path', 'after_seconds' => 300);
        
        $sql_r->notif_title = ($sql_r->notif_title) ? $sql_r->notif_title : $lang['srv_maza_geofence_default_title'];
        $sql_r->notif_message = ($sql_r->notif_message) ? $sql_r->notif_message : $lang['srv_maza_alarm_default_message'].$this->_ank_title;
        echo '<span class="clr bold">' . $lang['srv_notifications_send_title'] . ': </span><input type="text" name="maza_title" id="maza_title" size="35" maxlength="35" '
        . 'value="' . $sql_r->notif_title . '"><br><br>';
        echo '<span class="clr bold">' . $lang['srv_notifications_send_text'] . ': </span><input type="text" name="maza_message" id="maza_message" size="45" maxlength="45" '
        . 'value="' . $sql_r->notif_message . '"><br><br>';

        /*echo '<label><input type="checkbox" id="maza_notification_priority" name="maza_notification_priority" ' . $disabled . ' value="1" />';
        echo $lang['srv_maza_notification_priority'] . '</label><br><br>';*/

        //echo '<label><input type="checkbox" id="maza_notification_sound" name="maza_notification_sound" value="1" ' . (($sql_r->notif_sound == 1) ? 'checked' : '') . '/>';
        //echo $lang['srv_maza_notification_sound'] . '</label><br><br>';

        echo '</div>';
        
        echo '<div style="float: left;">';
        
        echo '<span class="clr bold">' . $lang['srv_maza_alarm_when_to_show'] . '</span>';

        echo '
        <label class="middle"><input type="radio" value="staypoint" name="maza_activity_type" '. (($sql_r->activity_type  == 'staypoint') ? "checked" : "")  .'>' . $lang['srv_maza_activity_staypoint'] . '</label>
        <label class="middle"><input type="radio" value="path" name="maza_activity_type" '. (($sql_r->activity_type  == 'path') ? "checked" : "")  .'>' . $lang['srv_maza_activity_path'] . '</label><br><br>';
        
        echo '<div id="maza_alarm_div_daily">';
        echo '<span class="clr bold">' . $lang['srv_maza_activity_after'] . ': </span><input style="float: none;" type="number" name="maza_activity_after_time"'
        . ' value="' . $sql_r->after_seconds . '">'
        . '<span class="clr"> ' . $lang['srv_maza_geofence_time_unit'] . '</span><br></div>';
        
        echo '</div>';
        echo '</div>';
        echo '</fieldset><br>';
        
        echo '<span id="maza_submit_activity" class="floatLeft spaceRight"><div class="buttonwrapper">'
        . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#maza_send_activity_form\').submit();">';
        echo $lang['srv_notifications_save_send'];
        echo '</a></div></span>';
        
        echo '<p id="maza_result">' . $_POST['maza_result'] . '</p>';
        
        echo '</form>';
        echo '</fieldset>';
    }
    
    // Obrazec za posiljanje sledenja
    private function setTrackingForm() {
        global $lang;

        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_tracking'] . '</legend>';
        
        //FORM FOR activity
        $sql_r = sisplet_query("SELECT COUNT(tracking_on) AS cnt FROM maza_srv_tracking WHERE ank_id='" . $this->_ank_id . "' AND tracking_on=1", 'obj');

        if ($sql_r->cnt > 0) {
            echo '<form name="maza_cancel_tracking_form" id="maza_cancel_tracking_form" method="post" action="ajax.php?t=MAZA&a=maza_cancel_tracking">';
            echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';
            //$disabled = ' disabled="disabled"';
            echo '<i class="red">' . $lang['srv_maza_tracking_on'] . '</i><br>';

            ///////////////////////////////////AKCIJA
            echo '<span class="spaceRight floatLeft"><div class="buttonwrapper">'
            . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#maza_cancel_tracking_form\').submit();">';
            echo $lang['srv_maza_tracking_turn_off'];
            echo '</a></div></span><br><br>';
            echo '</form>';
        }
        else
            echo '<i>' . $lang['srv_maza_tracking_off'] . '</i><br>';

        //form to set or update activity
        echo '<form name="maza_send_tracking_form" id="maza_send_tracking_form" method="post" action="ajax.php?t=MAZA&a=maza_run_tracking">';

        echo '<input type="hidden" name="maza_action" value="tracking">';
        echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';
        
        //NOTIFICATION SETTINGS
        //FORM FOR activity notification
        $sql_r = sisplet_query("SELECT * FROM maza_srv_tracking WHERE ank_id='" . $this->_ank_id . "' LIMIT 1", 'obj');
        
        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_tracking_settings'] . '</legend>';
        
        echo '<div style="overflow: hidden;">';
        
        if(!$sql_r)
            $sql_r = (object)array('activity_recognition'=>0, 'tracking_accuracy'=>'high', 'interval_wanted' => 30, 'interval_fastes' => 10, 'displacement_min' => 10, 'ar_interval_wanted' => 30);
        
        echo '<div style="float: left;">';
        
        echo '<span class="clr bold">' . $lang['srv_maza_tracking_accuracy'] . ': </span>';

        echo '
        <label><input type="radio" value="high" name="maza_tracking_accuracy" '. (($sql_r->tracking_accuracy  == 'high') ? "checked" : "")  .'>' . $lang['srv_maza_tracking_accuracy_high'] . '</label>
        <label><input type="radio" value="balanced" name="maza_tracking_accuracy" '. (($sql_r->tracking_accuracy  == 'balanced') ? "checked" : "")  .'>' . $lang['srv_maza_tracking_accuracy_balanced'] . '</label><br><br>';
        
        echo '<div>';
        echo '<span class="clr bold">' . $lang['srv_maza_tracking_interval_wanted'] . ': </span><input style="float: none;" type="number" name="maza_tracking_interval_wanted"'
        . ' value="' . $sql_r->interval_wanted . '">'
        . '<span class="clr"> ' . $lang['srv_maza_geofence_time_unit'] . '</span><br></div>';
        echo '<div>';
        
        echo '<div>';
        echo '<span class="clr bold">' . $lang['srv_maza_tracking_interval_fastes'] . ': </span><input style="float: none;" type="number" name="maza_tracking_interval_fastes"'
        . ' value="' . $sql_r->interval_fastes . '">'
        . '<span class="clr"> ' . $lang['srv_maza_geofence_time_unit'] . '</span><br></div>';
        echo '<div>';
        
        echo '<div>';
        echo '<span class="clr bold">' . $lang['srv_maza_tracking_displacement_min'] . ': </span><input style="float: none;" type="number" name="maza_tracking_displacement_min"'
        . ' value="' . $sql_r->displacement_min . '">'
        . '<span class="clr"> ' . $lang['srv_maza_tracking_displacement_m'] . '</span><br></div>';
        echo '</div>';
        
        echo '<br>';
        
        echo '<div>';
        echo '<label><input type="checkbox" id="maza_tracking_activity_recognition" name="maza_tracking_activity_recognition" value="1" ' . (($sql_r->activity_recognition == 1) ? 'checked' : '') . 
                ' onclick="toggleARInterval(this);" />';
        echo $lang['srv_maza_tracking_activity_recognition'] . '</label><br><br>';
        echo '</div>';
        
        echo '<div id="maza_ar_interval_div" '.(($sql_r->activity_recognition == 1) ? '' : 'hidden').'>';
        echo '<span class="clr bold">' . $lang['srv_maza_tracking_interval_wanted'] . ': </span><input style="float: none;" type="number" name="maza_tracking_ar_interval_wanted"'
        . ' value="' . $sql_r->ar_interval_wanted . '">'
        . '<span class="clr"> ' . $lang['srv_maza_geofence_time_unit'] . '</span><br></div>';
        echo '<div>';
        
        echo '</div>';
        echo '</fieldset><br>';
        
        echo '<span id="maza_submit_tracking" class="floatLeft spaceRight"><div class="buttonwrapper">'
        . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#maza_send_tracking_form\').submit();">';
        echo $lang['srv_notifications_save_send'];
        echo '</a></div></span>';
        
        echo '<p id="maza_result">' . $_POST['maza_result'] . '</p>';
        
        echo '</form>';
        echo '</fieldset>';
        
        echo '<br>';
        
        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_data_export'] . '</legend>';
        echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=maza_csv&anketa=' . $this->_ank_id) . '&m=maza_csv&a=tracking_locations" target="_blank" class="srv_ico">'
                . '<span class="hover_export_icon"><span class="sprites xls_large"></span></span>' . $lang['srv_maza_tracking_export_all_locations'] . '</a>';
        echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=maza_csv&anketa=' . $this->_ank_id) . '&m=maza_csv&a=tracking_ar" target="_blank" class="srv_ico">'
                . '<span class="hover_export_icon"><span class="sprites xls_large"></span></span>' . $lang['srv_maza_tracking_export_all_ar'] . '</a>';
        echo '</fieldset>';
    }
    
    // Obrazec za nastavljanje vnosov
    private function setEntryForm() {
        global $lang;

        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_entry'] . '</legend>';
        
        //FORM FOR DATA ENTRIES
        $sql_r = sisplet_query("SELECT COUNT(entry_on) AS cnt FROM maza_srv_entry WHERE ank_id='" . $this->_ank_id . "' AND entry_on=1", 'obj');

        if ($sql_r->cnt > 0) {
            echo '<form name="maza_cancel_entry_form" id="maza_cancel_entry_form" method="post" action="ajax.php?t=MAZA&a=maza_cancel_entry">';
            echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';
            //$disabled = ' disabled="disabled"';
            echo '<i class="red">' . $lang['srv_maza_entry_on'] . '</i><br>';

            ///////////////////////////////////AKCIJA
            echo '<span class="spaceRight floatLeft"><div class="buttonwrapper">'
            . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#maza_cancel_entry_form\').submit();">';
            echo $lang['srv_maza_entry_turn_off'];
            echo '</a></div></span><br><br>';
            echo '</form>';
        }
        else
            echo '<i>' . $lang['srv_maza_entry_off'] . '</i><br>';

        //form to set or update entry
        echo '<form name="maza_send_entry_form" id="maza_send_entry_form" method="post" action="ajax.php?t=MAZA&a=maza_run_entry">';

        echo '<input type="hidden" name="maza_action" value="entry">';
        echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';

        $sql_r = sisplet_query("SELECT * FROM maza_srv_entry WHERE ank_id='" . $this->_ank_id . "' LIMIT 1", 'obj');
                
        echo '<br><div>';
        echo '<label><input type="checkbox" id="maza_entry_location_check" name="maza_entry_location_check" value="1" ' . (($sql_r->location_check == 1) ? 'checked' : '') . ' />';
        echo $lang['srv_maza_entry_location_check'] . '</label><br><br>';
        echo '</div><br>';
        
        echo '<span id="maza_submit_entry" class="floatLeft spaceRight"><div class="buttonwrapper">'
        . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#maza_send_entry_form\').submit();">';
        echo $lang['srv_notifications_save_send'];
        echo '</a></div></span>';
        
        echo '<p id="maza_result">' . $_POST['maza_result'] . '</p>';
        
        echo '</form>';
        echo '</fieldset>';
     
        echo '<br>';
        
        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_data_export'] . '</legend>';
        echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=maza_csv&anketa=' . $this->_ank_id) . '&m=maza_csv&a=entry_locations" target="_blank" class="srv_ico">'
                . '<span class="hover_export_icon"><span class="sprites xls_large"></span></span>' . $lang['srv_maza_tracking_export_all_locations'] . '</a>';
        echo '</fieldset>';
    }
    
    // Obrazec za generiranje novih identifikatorjev
    private function generateNewUsersForm() {
        global $lang;

        //FORM FOR NOTIFICATION
        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_users_generator'] . '</legend>';
        echo '<form name="maza_generate_users_form" id="maza_generate_users_form" method="post" action="ajax.php?t=MAZA&a=maza_generate_users">';

        echo '<input type="hidden" name="maza_action" value="generate_users">';
        echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';
        echo '<span class="clr bold">' . $lang['srv_maza_users_generator_number'] . ': <input type="text" name="maza_users_generator_number" id="maza_users_generator_number" size="3" maxlength="3" onkeyup="checkNumber(this, 3, 0, true);"></span><br>';

        echo '<span class="floatLeft spaceRight"><div class="buttonwrapper">'
        . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#maza_generate_users_form\').submit();">';
        echo $lang['srv_maza_generate'];
        echo '</a></div></span>';
        
        echo '</form>';
        echo '</fieldset>';
    }
    
        // Obrazec za urejanje opisa raziskave
    private function surveyDescription() {
        global $lang;
        
        $sql_r = sisplet_query("SELECT srv_description FROM maza_survey WHERE srv_id='" . $this->_ank_id . "'", 'obj');

        //FORM FOR NOTIFICATION
        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_users_description'] . '</legend>';
        echo '<form name="maza_survey_description_form" id="maza_survey_description_form" method="post" action="ajax.php?t=MAZA&a=maza_survey_description">';

        echo '<input type="hidden" name="maza_action" value="survey_description">';
        echo '<input type="hidden" name="ank_id" value="' . $this->_ank_id . '">';
        echo '<textarea name="srv_maza_users_description" id="srv_maza_users_description" rows="6" maxlength="511">'.$sql_r->srv_description.'</textarea><br><br>';

        echo '<span class="floatLeft spaceRight"><div class="buttonwrapper">'
        . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#maza_survey_description_form\').submit();">';
        echo $lang['srv_notifications_save_send'];
        echo '</a></div></span>';
        
        echo '</form>';
        echo '</fieldset>';
    }
    
    // Obrazec za izvoz vseh identifikatorjev
    private function exportIdentifiersForm() {
        global $lang;
        
        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_data_export'] . '</legend>';
        echo '<form name="maza_ident_export_form" id="maza_ident_export_form" method="post" action="'.makeEncodedIzvozUrlString('izvoz.php?b=export&m=maza_csv&anketa=' . $this->_ank_id) . '&m=maza_csv&a=ident_export'.'">';
        
        echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="maza_active_ident" id="maza_active_ident" checked> <label for="maza_active_ident"><span class="clr">'.$lang['srv_maza_users_activated'].'</span></label></div>';
        echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="maza_inactive_ident" id="maza_inactive_ident" checked> <label for="maza_inactive_ident"><span class="clr">'.$lang['srv_maza_users_inactive'].'</span></label></div>';
        echo '<div style="padding-top:5px;"><input type="checkbox" value="1" name="maza_deactive_ident" id="maza_deactive_ident" checked> <label for="maza_deactive_ident"><span class="clr">'.$lang['srv_maza_users_deactive'].'</span></label></div><br />';

        echo '<a href="#" onclick="$(\'#maza_ident_export_form\').submit();" class="srv_ico">'
                . '<span class="hover_export_icon"><span class="sprites xls_large"></span></span>' . $lang['srv_lnk_excel'] . '</a>';

        echo '</form>';
        echo '</fieldset>';
        
        //header('location: ' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=maza_csv&anketa=' . $this->_ank_id) . '&m=maza_csv&a=ident_export');
    }
    
    // Obrazec za posiljanje notificationa
    private function getUsersStatistics() {
        global $lang;
        
        $Query = "SELECT COUNT(*) cnt FROM maza_user_srv_access AS sa " .
                "LEFT JOIN (SELECT id, datetime_last_active FROM maza_app_users) AS au " . 
                "ON au.id = sa.maza_user_id WHERE sa.ank_id='$this->_ank_id' AND au.datetime_last_active IS NOT NULL AND datetime_unsubscribed IS NULL;";
        $resultActivated = sisplet_query($Query, "obj");
        
        $Query = "SELECT COUNT(*) cnt FROM maza_user_srv_access " .
                "WHERE ank_id='$this->_ank_id' AND datetime_unsubscribed IS NOT NULL;";
        $resultDeactivated = sisplet_query($Query, "obj");
        
        $Query = "SELECT COUNT(*) cnt FROM maza_user_srv_access WHERE ank_id='$this->_ank_id';";
        $resultAll = sisplet_query($Query, "obj");

        //TABLE OF USERS STATISTICS
        echo '<fieldset>';
        echo '<legend>' . $lang['srv_maza_users_statistics'] . '</legend>';
        
        echo '<span class="dashboard_status_span">' . $lang['srv_maza_users_activated'] . ':</span>' . $resultActivated->cnt.'<br/>';
        echo '<span class="dashboard_status_span">' . $lang['srv_maza_users_inactive'] . ':</span>' . ($resultAll->cnt - $resultActivated->cnt - $resultDeactivated->cnt) .'<br/>';
        echo '<span class="dashboard_status_span">' . $lang['srv_maza_users_deactive'] . ':</span>' . $resultDeactivated->cnt .'<br/>';
        echo '<div class="anl_dash_bt full strong"><span class="dashboard_status_span">'.$lang['srv_maza_users_sum'].': </span>'.$resultAll->cnt.'<br/></div><br/>';
        
        //echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=maza_csv&anketa=' . $this->_ank_id) . '&m=maza_csv&a=inactive_identifiers" target="_blank" class="srv_ico"><span class="hover_export_icon"><span class="sprites xls_large"></span></span>' . $lang['srv_lnk_excel'] . ' (' . $lang['srv_maza_users_inactive'] . ')</a>';
        //echo '<a href="' . makeEncodedIzvozUrlString('izvoz.php?b=export&m=maza_csv&anketa=' . $this->_ank_id) . '&m=maza_csv&a=active_identifiers" target="_blank" class="srv_ico"><span class="hover_export_icon"><span class="sprites xls_large"></span></span>' . $lang['srv_lnk_excel'] . ' (' . $lang['srv_maza_users_activated'] . ')</a>';
        
        echo '</fieldset>';
    }

    function addUrl($what) {
        global $site_url;

        if ($what == null || trim($what) == '') {
            $what = 'maza_dashboard';
        }
        $url = $site_url . 'admin/survey/index.php?anketa=' . $this->_ank_id . '&amp;a=' . A_MAZA . '&amp;m=' . $what;

        return $url;
    }

    private function send_FCM_message($msg, $action) {
        //get all registration ids
        $reg_ids = array();
        $Query = "SELECT registration_id FROM maza_app_users AS au "
                . "LEFT JOIN (SELECT maza_user_id, ank_id FROM maza_user_srv_access) AS sa "
                . "ON au.id = sa.maza_user_id WHERE sa.ank_id='$this->_ank_id' AND "
                . "au.registration_id != 'NULL' AND au.registration_id != ''";
        $sql_array = sisplet_query($Query, 'array');
        //$rql = mysqli_fetch_assoc ($reg_ids);
        foreach ($sql_array as $pair)
            array_push($reg_ids, $pair['registration_id']);

        $fields = array
            (
            //can send to max 1000 reg_ids at once - split it on 1000 and send each chuck
            'registration_ids' => $reg_ids,
            'data' => $msg
        );

        if ((isset($_POST['maza_notification_priority']) && $_POST['maza_notification_priority'] == 1)
                 || $action == 'alarm' || $action == 'geofencing' || $action == 'activity' || $action == 'tracking' || $action == 'entry' || $action == 'all')
            $fields['priority'] = 'high';

        //do a curl to send notifications
        $result = $this->send_FCM_mesage_curl($fields);
        $this->send_FCM_message_navigate($action, $fields, $result);
    }
    
    /**
     * Do a curl to firebase to send notifications to devices
     * @global type $FCM_server_key - FCM server key
     * @param type $fields - json object containig FCM registration ids of devices and data to send
     * @return type - json result of curl output
     */
    private function send_FCM_mesage_curl($fields){
        #API access key from Google API's Console
        global $FCM_server_key;
        
        $headers = array
            (
            'Authorization: key=' . $FCM_server_key,
            'Content-Type: application/json'
        );
        #Send Reponse To FireBase Server	
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        curl_close($ch);
        
        #Echo Result Of FireBase Server               
        //error_log($result);
        //error_log(json_encode($headers));
        //error_log(json_encode($fields));
        //error_log(json_encode($result));
        
        return $result;
    }
    
    /**
     * After sending FCM message, navigate to right submodule 
     * @param type $action - action or name of submodule
     * @param type $fields - optional (just for test)
     * @param type $headers - optional (just for test)
     * @param type $result - optional (just for test)
     */
    private function send_FCM_message_navigate($action, $fields, $result){
        switch ($action) {
            case 'alarm':
                $action = '&m=maza_set_alarm';
                break;

            case 'notification':
                $action = '&m=maza_send_notification';
                break;
            
            case 'geofencing':
                $action = '&m=maza_set_geofencing';
                break;
            
            case 'activity':
                $action = '&m=maza_set_activity';
                break;
            
            case 'tracking':
                $action = '&m=maza_set_tracking';
                break;
            
            case 'entry':
                $action = '&m=maza_set_entry';
                break;
            
            case 'survey_info':
                $action = '&m=maza_dashboard';
                break;

            default:
                $action = '&m=maza_dashboard';
                break;
        }

        if($action)
            header('location: index.php?anketa=' . $this->_ank_id . '&a=maza' . $action . '&FCM_response=' . json_encode($fields, JSON_UNESCAPED_SLASHES). /*json_encode($headers, JSON_UNESCAPED_SLASHES).*/ json_encode($result, JSON_UNESCAPED_SLASHES));
    }
    
    public function ajax_sendNotification() {
        $action = $_POST['maza_action'];
        $title = $_POST['maza_title'];
        $message = $_POST['maza_message'];
        $srv_title = "";
        $repeat = 0;
        $repeater = 0;
        $geofences = 0;
        $activity = 0;
        $entry = 0;

        if ($action == 'alarm'){
            $this->maza_save_alarm($title, $message, /*$_POST['maza_notification_sound']*/1);
            $sql_a = sisplet_query("SELECT repeat_by, time_in_day, day_in_week, every_which_day FROM maza_srv_alarms WHERE ank_id='" . $this->_ank_id . "'", 'obj');
            $repeat = array("repeat_by" => $sql_a->repeat_by, "time_in_day" => json_decode($sql_a->time_in_day), "day_in_week" => json_decode($sql_a->day_in_week), "every_which_day" => $sql_a->every_which_day);
            $sql_r = sisplet_query("SELECT repeat_by, time_in_day, day_in_week, every_which_day, datetime_start, datetime_end FROM maza_srv_repeaters WHERE ank_id='" . $this->_ank_id . "'", 'obj');
            $repeater = array("repeat_by" => $sql_r->repeat_by, "time_in_day" => json_decode($sql_r->time_in_day), "day_in_week" => json_decode($sql_r->day_in_week), "every_which_day" => $sql_r->every_which_day,
                     "datetime_start" => $sql_r->datetime_start, "datetime_end" => $sql_r->datetime_end);
        } 
        else if ($action == 'geofencing'){
            $geofences = $this->getRunningGeofences();
        }
        else if ($action == 'activity'){
            $sql_a = sisplet_query("SELECT id, activity_type, after_seconds FROM maza_srv_activity WHERE ank_id='" . $this->_ank_id . "'", 'obj');
            $activity = array("id" => $sql_a->id, "activity_type" => $sql_a->activity_type, "after_seconds" => $sql_a->after_seconds);
            
            $sql_n = sisplet_query("SELECT naslov FROM srv_anketa WHERE id='" . $this->_ank_id . "'", 'obj');
            $srv_title = $sql_n->naslov;
        }
        else if ($action == 'tracking'){
            $sql_a = sisplet_query("SELECT * FROM maza_srv_tracking WHERE ank_id='" . $this->_ank_id . "'", 'obj');
            $tracking = array("id" => $sql_a->id, "activity_recognition" => $sql_a->activity_recognition, 
                "tracking_accuracy" => $sql_a->tracking_accuracy, "interval_wanted" => $sql_a->interval_wanted, 
                "interval_fastes" => $sql_a->interval_fastes, "displacement_min" => $sql_a->displacement_min,
                "ar_interval_wanted" => $sql_a->ar_interval_wanted);
            
            $sql_n = sisplet_query("SELECT naslov FROM srv_anketa WHERE id='" . $this->_ank_id . "'", 'obj');
            $srv_title = $sql_n->naslov;
        }
        else if ($action == 'entry'){
            $entry = $this->getRunningEntry();
            $sql_n = sisplet_query("SELECT naslov FROM srv_anketa WHERE id='" . $this->_ank_id . "'", 'obj');
            $srv_title = $sql_n->naslov;
        }

        #prep the bundle
        $msg = array
            (
            'action' => $action,
            'ank_id' => $this->_ank_id,
            'message' => $message,
            'title' => $title,
            'link' => $this->_ank_link,
            'srv_title' => $srv_title,
            'repeat' => $repeat,
            'repeater' => $repeater,
            'geofences' => $geofences,
            'activity' => $activity,
            'tracking' => $tracking,
            'entry' => $entry,
            'sound' => /*(isset($_POST['maza_notification_sound']) && $_POST['maza_notification_sound'] == 1) ?*/ 1 //: 0
        );

        $this->send_FCM_message($msg, $action);
    }
    
    private function send_FCM_message_pwa($msg, $action) {
        $reg_ids = sisplet_query("SELECT endpoint_key FROM browser_notifications_respondents", 'onevalarray');

         //get all registration ids
        /*$reg_ids = array('cJyD7jXFuGY:APA91bGmB4qXhZE2QOxPbjHm8ZTeyslXaejUfyVDmr06FDm-2p76sF2_A8Q0HGm0EZGdJ_O_iUACfJcsXznVWGe5SeDuI9S8F5HgqwZ1d7G2hN0PQiJL7Q8TlqjWCpbajjAWZ_OmxzLU', 
            'e0jGT0OYYCY:APA91bGRjTI7iAuuvevYEtz9QLg5bQE2ha1L44PLSVg_kkrFn4up7ZeHzh50dW9vOxCch_hdTs-cEmx3HKuKIoxOnvCMuj7faInTkU4t80fxOjL7D1isuEmWXBM52pheEiE9nScF-i63');*/
        /*$Query = "SELECT registration_id FROM maza_app_users AS au "
                . "LEFT JOIN (SELECT maza_user_id, ank_id FROM maza_user_srv_access) AS sa "
                . "ON au.id = sa.maza_user_id WHERE sa.ank_id='$this->_ank_id' AND "
                . "au.registration_id != 'NULL' AND au.registration_id != ''";
        $sql_array = sisplet_query($Query, 'array');
        //$rql = mysqli_fetch_assoc ($reg_ids);
        foreach ($sql_array as $pair)
            array_push($reg_ids, $pair['registration_id']);*/

        $fields = array
            (
            //can send to max 1000 reg_ids at once - split it on 1000 and send each chuck
            'registration_ids' => $reg_ids,
            'data' => $msg
        );

        //do a curl to send notifications
        $result = $this->send_FCM_mesage_curl($fields);
        $this->send_FCM_message_navigate($action, $fields, $result);
    }
    
    public function ajax_sendNotification_pwa() {
        $action = $_POST['maza_action'];
        $title = $_POST['maza_title'];
        $message = $_POST['maza_message'];
        
        $sql_n = sisplet_query("SELECT naslov FROM srv_anketa WHERE id='" . $this->_ank_id . "'", 'obj');

        #prep the bundle
        $msg = array
            (
            'action' => $action,
            'ank_id' => $this->_ank_id,
            'message' => $message,
            'title' => $title,
            'link' => $this->_ank_link,
            'srv_title' => $sql_n->naslov,
            'sound' => /*(isset($_POST['maza_notification_sound']) && $_POST['maza_notification_sound'] == 1) ?*/ 1 //: 0
        );

        $this->send_FCM_message_pwa($msg, $action);
    }

    /**
     * Check if id of maza app user and indentifier match
     * @param type $maza_uid - id of maza app auser
     * @param type $maza_identifier - string identifier of maza app user
     * @return boolean - true if match, false otherwise
     */
    public function maza_validate_user($maza_uid, $maza_identifier) {
        $sql = sisplet_query("SELECT * FROM maza_app_users WHERE id='$maza_uid' AND identifier='$maza_identifier'");
        if (mysqli_num_rows($sql) > 0)
            return true;
        else
            return false;
    }

    /**
     * Update datetime of user last_active
     * @param type $maza_uid - id of maza app user
     */
    public function maza_update_user_active($maza_uid, $registration_id = null) {
        $registration_id_query = '';
        if (isset($registration_id) && $registration_id != '')
            $registration_id_query = ", registration_id = '$registration_id'";

        sisplet_query("UPDATE maza_app_users SET datetime_last_active = NOW()$registration_id_query WHERE id='$maza_uid'");
    }

    /**
     * Insert pair of users id's in maza_srv_users table if not already exists
     * @param type $maza_uid - id of maza app user
     * @param type $srv_uid - id of survey respondent
     * @param type $srv_version - timestamp version of survey (important with repeaters)
     * @param type $tgeofence_id - id of triggered geofence
     * @param type $tactivity_id - id of triggered activity
     * @param type $mode - mode of survey (repeater, geofence, activity, entry)
     */
    public function maza_save_srv_user($maza_uid, $srv_uid, $srv_version, $tgeofence_id=null, $tactivity_id=null, $mode) {
        //check if pair already existst in DB
        $sql = sisplet_query("SELECT * FROM maza_srv_users WHERE maza_user_id='$maza_uid' AND srv_user_id='$srv_uid'");
        //it is already there
        if (mysqli_num_rows($sql) > 0)
            return false;
        else {
            if(!$tgeofence_id)
                $tgeofence_id = 'NULL';
            if(!$tactivity_id)
                $tactivity_id = 'NULL';
                
            //get datetime from version
            if($srv_version){
                $myDateTime = new DateTime();
                $myDateTime->setTimestamp($srv_version);
                $newDateString = $myDateTime->format('Y-m-d H:i:s');
            }

            //insert in table
            sisplet_query("INSERT INTO maza_srv_users (maza_user_id, srv_user_id, srv_version_datetime, tgeof_id, tact_id, mode) VALUES ('" . $maza_uid . "', '" . $srv_uid . "', '$newDateString', $tgeofence_id, $tactivity_id, ".($mode ? "'$mode'" : 'NULL').");");
            return true;
        }
    }

    public function ajax_maza_on_off() {
        $on_off = json_decode($_POST['on_off']);
        if (isset($on_off) && $on_off)
            $this->maza_on();
        else
            $this->maza_off();
    }

    
    public function ajax_maza_cancel_alarm() {
        $msg = array
            (
            'action' => 'cancel_alarm',
            'srv_id' => $this->_ank_id
        );

        sisplet_query("UPDATE maza_srv_alarms SET alarm_on='0' WHERE ank_id='" . $this->_ank_id . "'");

        $this->send_FCM_message($msg, 'alarm');
    }

    public function maza_off($ank_id = null) {
        if($ank_id)
            $this->_ank_id = $ank_id;
        
        $this->maza_cancel_repeater_db();
        $this->maza_cancel_geofencing_db();
        $this->maza_cancel_entry_db();
        $this->maza_cancel_activity_db();
        $this->maza_cancel_tracking_db();
        
        $msg = array
            (
            'action' => 'cancel_all',
            'srv_id' => $this->_ank_id
        );
        $this->send_FCM_message($msg, 'all');
    }
    
    public function maza_cancel_repeater(){
        $msg = array
            (
            'action' => 'stop_repeater',
            'srv_id' => $this->_ank_id
        );
        $this->send_FCM_message($msg, 'alarm');
        
        $this->maza_cancel_repeater_db();
        header('location: index.php?anketa=' . $this->_ank_id . '&a=maza&m=maza_set_alarm');
    }
    
    public function maza_cancel_repeater_db(){
        sisplet_query("UPDATE maza_srv_alarms SET alarm_on='0' WHERE ank_id = '" . $this->_ank_id . "'");
        //for now, we cannot change repeater of survey - repeater_on = 2 means that survey was canceled while repeater was on
        sisplet_query("UPDATE maza_srv_repeaters SET repeater_on='2', datetime_end=NOW() WHERE ank_id='" . $this->_ank_id . "'");
    }

    public function maza_on() {
        $sel = sisplet_query("SELECT COUNT(id) as cnt FROM maza_srv_alarms WHERE ank_id = '" . $this->_ank_id . "';", 'obj');
        if($sel->cnt == 0)
            sisplet_query("INSERT INTO maza_srv_alarms (ank_id) VALUES ('" . $this->_ank_id . "')");
        $sel = sisplet_query("SELECT COUNT(id) as cnt FROM maza_srv_repeaters WHERE ank_id = '" . $this->_ank_id . "';", 'obj');
        if($sel->cnt == 0)
            sisplet_query("INSERT INTO maza_srv_repeaters (ank_id) VALUES ('" . $this->_ank_id . "')");
        $sel = sisplet_query("SELECT COUNT(id) as cnt FROM maza_survey WHERE srv_id = '" . $this->_ank_id . "';", 'obj');
        if($sel->cnt == 0)
            sisplet_query("INSERT INTO maza_survey (srv_id) VALUES ('" . $this->_ank_id . "')");
    }

    private function maza_save_alarm($title, $message, $sound) {
        //for now, available title is only default
        sisplet_query("UPDATE maza_srv_alarms SET alarm_on='1', "//alarm_notif_title='$title', "
                . "alarm_notif_message='$message', alarm_notif_sound='$sound' WHERE ank_id='" . $this->_ank_id . "'");
    }
    
    //returns random alphanumerical code
    public function randomAlphaNumericCode($length) {
        $token = "";
        $codeAlphabet = "ABCDEFGHJKLMNOPQRSTUVWXYZ";
        $codeAlphabet .= "abcdefghijkmnopqrstuvwxyz";
        $codeAlphabet .= "0123456789";
        $max = /*strlen($codeAlphabet)-1;*/59; // edited

        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[mt_rand(0, $max)];
        }

        return $token;
    }
    
    /**
     * Generate new users or 
     * @param type $howManyUsers - number of new users to generate or 'self' for self identifier creation by app via API
     * @return array array of identifiers or pair of id and identifier if 'self' 
     */
    public function insertNewUsers ($howManyUsers = 0){
        //create users from modul
        if($howManyUsers > 0){
            $identifiers = array();
            $query = "BEGIN; ";
            for($i = 0; $i < $howManyUsers; $i++){
                $generated_identifier = $this -> randomAlphaNumericCode(8);
                //$salted_identifier = base64_encode(hash(SHA256, $generated_identifier . $pass_salt));

                array_push($identifiers, $generated_identifier);
                $query .= "INSERT INTO maza_app_users (identifier) VALUES('$generated_identifier'); INSERT INTO maza_user_srv_access (maza_user_id, ank_id) VALUES(LAST_INSERT_ID(), '$this->_ank_id'); ";
            }
            $query .= "COMMIT;";
            sisplet_query($query, 'multi_query');
                      
            return $identifiers;
        }
        //user self creates an identifier (from mobile app via API)
        elseif($howManyUsers == 'self'){
            $generated_identifier = $this -> randomAlphaNumericCode(8);
            sisplet_query("INSERT INTO maza_app_users (identifier, datetime_last_active) VALUES ('".$generated_identifier."', NOW())");
            $id =  mysqli_insert_id($GLOBALS['connect_db']);
            return array("id"=>$id, "identifier"=>$generated_identifier);
        }
    }
    
    public function ajax_maza_generate_users() {
        $userNumb = $_POST['maza_users_generator_number'];
        $this->insertNewUsers($userNumb);
        header('location: index.php?anketa=' . $this->_ank_id . '&a=maza&m=maza_dashboard');
    }
    
    public function ajax_maza_survey_description() {
        $desc = $_POST['srv_maza_users_description'];
        
        sisplet_query("UPDATE maza_survey SET srv_description='$desc' WHERE srv_id='" . $this->_ank_id . "'");     
        
        /*$msg = array
            (
            'action' => 'survey_info',
            'description' => $desc,
            'srv_id' => $this->_ank_id
        );
        $this->send_FCM_message($msg, 'survey_info');*/
        header('location: index.php?anketa=' . $this->_ank_id . '&a=maza&m=maza_dashboard');
    }
    
    public function ajax_changeRepeatBy() {
        $repeatby = $_POST['maza_repeatby'];
        $table = $_POST['maza_table'];
        sisplet_query("UPDATE maza_srv_$table SET repeat_by='$repeatby' WHERE ank_id='" . $this->_ank_id . "'");
    }
    
    public function ajax_changeTimeInDay() {
        $time_in_day = json_encode($_POST['maza_time_in_day']);
        $table = $_POST['maza_table'];
        sisplet_query("UPDATE maza_srv_$table SET time_in_day='$time_in_day' WHERE ank_id='" . $this->_ank_id . "'");
    }
    
    public function ajax_changeDayInWeek() {
        $day_in_week = json_encode($_POST['maza_day_in_week']);
        $table = $_POST['maza_table'];
        sisplet_query("UPDATE maza_srv_$table SET day_in_week='$day_in_week' WHERE ank_id='" . $this->_ank_id . "'");
    }
    
    public function ajax_changeEveryWhichDay() {
        $every_which_day = $_POST['maza_every_which_day'];
        $table = $_POST['maza_table'];
        sisplet_query("UPDATE maza_srv_$table SET every_which_day='$every_which_day' WHERE ank_id='" . $this->_ank_id . "'");
    }
    
    public function ajax_saveRepeater() {        
        $repeatby = $_POST['maza_repeater_intervalby'];
        $time_in_day = json_encode($_POST['maza_repeater_timeinday']);
        $every_which_day = ($_POST['maza_repeater_everywhichday']) ? $_POST['maza_repeater_everywhichday'] : 1;
        $day_in_week = json_encode($_POST['maza_repeater_dayinweek']);
        $Start_date = $_POST['maza_repeater_date_start'];
        $End_date = $_POST['maza_repeater_date_end'];

        if($Start_date){
            if($End_date){
                $End_date = DateTime::createFromFormat('d.m.Y', $End_date);
                $End_date = $End_date->format("Y-m-d");
                $End_date = "datetime_end='$End_date', ";
            }
            $Start_date = DateTime::createFromFormat('d.m.Y', $Start_date);
            $Start_date = $Start_date->format("Y-m-d");

            sisplet_query("UPDATE maza_srv_repeaters SET repeater_on='1', datetime_start='$Start_date', $End_date"
                    . "every_which_day='$every_which_day', day_in_week='$day_in_week', "
                    . "time_in_day='$time_in_day', repeat_by='$repeatby' WHERE ank_id='" . $this->_ank_id . "'");
        }
        header('location: index.php?anketa=' . $this->_ank_id . '&a=maza&m=maza_set_alarm');
    }
    
    /**
     * Insert/save new geofence
     */
    function ajax_insert_geofence(){     
        $add = $_POST['address'];
        $lat = $_POST['lat'];
        $lng = $_POST['lng'];
        $rad = $_POST['radius'];
        $trigger_survey = "NOW()";
        
        $sql = sisplet_query("SELECT trigger_survey FROM maza_srv_geofences WHERE ank_id=".$this->_ank_id." LIMIT 1", 'obj');
        if($sql && $sql->trigger_survey==null){
            $trigger_survey="NULL";
        }
        
        //last decimals of coordiates are not exact same in database, because float in mySql is not precise - practical variations are minimal
        $id = sisplet_query("INSERT INTO maza_srv_geofences (ank_id, address, lat, lng, radius, trigger_survey) "
                . "VALUES ('$this->_ank_id', '$add', '$lat', '$lng', '$rad', $trigger_survey)", "id");

        echo $id;
    }
    
    /**
     * Update geofence by ID
     */
    function ajax_update_geofence(){ 
        if($_POST['id']){
            $update = '';
            $update .= $_POST['address'] ? "address = '".$_POST['address']."', " : '';
            $update .= $_POST['lat'] ? "lat = '".$_POST['lat']."', " : '';
            $update .= $_POST['lng'] ? "lng = '".$_POST['lng']."', " : '';
            $update .= $_POST['radius'] ? "radius = '".$_POST['radius']."', " : '';
            $update = substr($update, 0, -2);
            
            //last decimals of coordiates are not exact same in database, because float in mySql is not precise - practical variations are minimal
            sisplet_query("UPDATE maza_srv_geofences SET $update WHERE id='".$_POST['id']."'");
        }
    }
    
    /**
     * Update geofenceinternal name by ID
     */
    function ajax_update_geofence_name(){ 
        if($_POST['id']){           
            sisplet_query("UPDATE maza_srv_geofences SET name='".$_POST['name']."' WHERE id='".$_POST['id']."'");
        }
    }
    
    /**
     * Delete geofence by ID
     */
    function ajax_delete_geofence(){ 
        if($_POST['id']){
            $sql = sisplet_query("DELETE FROM maza_srv_geofences WHERE id='".$_POST['id']."'");
            if($sql)
                echo 'OK';
        }
    }
    
    /**
     * Get array of geofences for this survey
     * @return type string JSON array of all geofences for this survey
     */
    function ajax_get_all_geofences(){ 
        $sql = sisplet_query("SELECT * FROM maza_srv_geofences WHERE ank_id=".$this->_ank_id, 'array');
        echo json_encode($sql);
    }
    
    /**
     * Calncel all geofences for this survey
     * Turn geofences off in DB and send cancelation to all subscriptors via FCM
     */
    public function maza_cancel_geofencing(){
        $msg = array
            (
            'action' => 'cancel_geofencing',
            'srv_id' => $this->_ank_id
        );
        $this->send_FCM_message($msg, 'geofencing');
        
        $this->maza_cancel_geofencing_db();
        header('location: index.php?anketa=' . $this->_ank_id . '&a=maza&m=maza_set_geofencing');
    }
    
    public function maza_cancel_geofencing_db(){
        sisplet_query("UPDATE maza_srv_geofences SET geofence_on='0' WHERE ank_id = '" . $this->_ank_id . "'");
    }
    
    /**
     * Run all geofences for this survey
     * Turn geofences on in DB and send them to subscriptors via FCM
     */
    public function maza_run_geofences(){
        $title = $_POST['maza_title'];
        $message = $_POST['maza_message'];
        $sound = /*$_POST['maza_notification_sound']*/1;
        $transition = $_POST['maza_geofence_on_transition'];
        $time = $_POST['maza_geofence_after_time'];
        $location_triggered = $_POST['maza_geofence_location_triggered'];
        $trigger_survey = $_POST['maza_geofence_trigger_survey'];
        
        sisplet_query("UPDATE maza_srv_geofences SET geofence_on='1', notif_title='$title', "
                . "notif_message='$message', notif_sound='$sound',  after_seconds='$time', "
                . "on_transition='$transition', location_triggered='$location_triggered', "
                . "trigger_survey=".($trigger_survey ? 'NULL' : 'NOW()' )." "
                . "WHERE ank_id='" . $this->_ank_id . "'");
        
        $this->ajax_sendNotification();
    }
      
    /**
     * Get array of running geofences for this survey
     * @return type array of all running geofences for this survey
     */
    public function getRunningGeofences(){
        return sisplet_query("SELECT * FROM maza_srv_geofences WHERE geofence_on=1 AND ank_id='" . $this->_ank_id . "'", 'array');
    }
        
    /**
     * Get object of running data entry for this survey
     * @return type object of running data entry for this survey
     */
    private function getRunningEntry(){
        return sisplet_query("SELECT * FROM maza_srv_entry WHERE entry_on=1 AND ank_id='" . $this->_ank_id . "'", 'obj');
    }
    
    /**
     * Calncel data entry for this survey
     * Turn data entry off in DB and send cancelation to all subscriptors via FCM
     */
    public function maza_cancel_entry(){
        $msg = array
            (
            'action' => 'cancel_entry',
            'srv_id' => $this->_ank_id
        );
        $this->send_FCM_message($msg, 'entry');
        
        $this->maza_cancel_entry_db();
        header('location: index.php?anketa=' . $this->_ank_id . '&a=maza&m=maza_set_entry');
    }
    
    public function maza_cancel_entry_db(){
        sisplet_query("UPDATE maza_srv_entry SET entry_on='0' WHERE ank_id = '" . $this->_ank_id . "'");
    }
    
    /**
     * Run all data entry for this survey
     * Turn data entry on in DB and send them to subscriptors via FCM
     */
    public function maza_run_entry(){
        $location_triggered = $_POST['maza_entry_location_check'];
        
        $sel = sisplet_query("SELECT COUNT(id) as cnt FROM maza_srv_entry WHERE ank_id = '" . $this->_ank_id . "';", 'obj');
        if($sel->cnt == 0)
            sisplet_query("INSERT INTO maza_srv_entry (ank_id, location_check, entry_on) VALUES ('" . $this->_ank_id . "', '$location_triggered', '1')");
        else
            sisplet_query("UPDATE maza_srv_entry SET entry_on='1', location_check='$location_triggered' WHERE ank_id='" . $this->_ank_id . "'");
        
        //if location is on, create system variables for coordinates
        if($location_triggered == '1'){
            //is there already system varibles in survey for coordinates
            $sel = sisplet_query("SELECT COUNT(s.id) as cnt, g.id, s.gru_id, g.ank_id, s.variable FROM srv_spremenljivka as s "
                    . "LEFT JOIN (SELECT id, ank_id FROM srv_grupa) AS g ON g.id = s.gru_id "
                    . "WHERE g.ank_id='$this->_ank_id' AND variable='latitude';", 'obj');
            
            //sys variables not created yet
            if($sel->cnt == 0){
                //create empty variables
                $ba = new BranchingAjax($this->_ank_id);
                $spr_lng = $ba->spremenljivka_new(0, 0, 0, 0, true);
                $spr_lat = $ba->spremenljivka_new(0, 0, 0, 0, true); 
                
                //set system variables
                sisplet_query("UPDATE srv_spremenljivka SET naslov='latitude', variable='latitude', tip='21', "
                        . "visible='0', sistem='1', variable_custom='1' WHERE id='$spr_lat';");
                sisplet_query("UPDATE srv_spremenljivka SET naslov='longitude', variable='longitude', tip='21', "
                        . "visible='0', sistem='1', variable_custom='1' WHERE id='$spr_lng';");
                
                sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red, variable_custom, hidden) VALUES ('', '$spr_lat', 'latitude', 'latitude', '1', '1', '1');");
                sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red, variable_custom, hidden) VALUES ('', '$spr_lng', 'longitude', 'longitude', '1', '1', '1');");
                
                Common::getInstance()->updateEditStamp();
            }
        }
        $this->ajax_sendNotification();
    }
    
    /**
     * Run activity for this survey
     * Turn activity on in DB and send them to subscriptors via FCM
     */
    public function maza_run_activity(){
        $title = $_POST['maza_title'];
        $message = $_POST['maza_message'];
        $sound = /*$_POST['maza_notification_sound']*/1;
        $transition = $_POST['maza_activity_type'];
        $time = $_POST['maza_activity_after_time'];
        
        $sel = sisplet_query("SELECT COUNT(id) as cnt FROM maza_srv_activity WHERE ank_id = '" . $this->_ank_id . "';", 'obj');
        if($sel->cnt > 0)
            sisplet_query("UPDATE maza_srv_activity SET activity_on='1', notif_title='$title', "
                . "notif_message='$message', notif_sound='$sound',  after_seconds='$time', activity_type='$transition' WHERE ank_id='" . $this->_ank_id . "'");
        else
            sisplet_query("INSERT INTO maza_srv_activity (ank_id, activity_on, notif_title, notif_message, notif_sound, after_seconds, activity_type) "
                    . "VALUES ('".$this->_ank_id."', '1', '".$title."', '".$message."', '".$sound."', '".$time."', '".$transition."')");
        
        $this->ajax_sendNotification();
    }
    
    /**
     * Calncel all activities for this survey
     * Turn activities off in DB and send cancelation to all subscriptors via FCM
     */
    public function maza_cancel_activity(){
        $msg = array
            (
            'action' => 'cancel_activity',
            'srv_id' => $this->_ank_id
        );
        $this->send_FCM_message($msg, 'activity');
        
        $this->maza_cancel_activity_db();
        header('location: index.php?anketa=' . $this->_ank_id . '&a=maza&m=maza_set_activity');
    }
    
    public function maza_cancel_activity_db(){
        sisplet_query("UPDATE maza_srv_activity SET activity_on='0' WHERE ank_id = '" . $this->_ank_id . "'");
    }
    
    /**
     * Send post request to nextpin API to create new user
     * @param type $identifier - identifier of user to register on nextpin
     * @param type $password - passwor to set in nextpin for this user
     */
    public function nextpin_create_user($identifier, $password){
        #credentials to use nextpin API
        global $NextPinMainToken;
        
        $fields = array
            (
            'username' =>  $this->nextpin_token_prefix . $identifier,
            'password' => $password
        );

        $this -> nextpin_API_call($NextPinMainToken, $fields, 'auth/createUser');
    }
        
    /**
     * Send post request to nextpin API to set new activity listener
     * @param type $identifier - identifier of user to register on nextpin
     * @param type $password - passwor to set in nextpin for this user
     */
    public function nextpin_set_activity_listener($identifier){
        global $site_url;

        $sql_a = sisplet_query("SELECT id, activity_type, after_seconds FROM maza_srv_activity WHERE ank_id='" . $this->_ank_id . "'", 'obj');

        $target = $sql_a->activity_type == 'path' ? 'P' : 'S';
            
        $fields = array
            (
            'target' =>  $target,
            'trigger_group' => "1KAPanel_trigger_".$this->_ank_id,
            'threshold' => $sql_a->after_seconds,
            'host' => $site_url,
            'port' => "80",
            'path' => "/admin/survey/api/api.php?action=nextpinalarmgetter&act_id=".$sql_a->id."&identifier=".$identifier
        );

        $this -> nextpin_API_call($this->nextpin_token_prefix.$identifier, $fields, 'alerts/setActivityForUser');
    }
    
    /**
     * Send post request to nextpin API
     * @param type $token - token/username to use it in request header
     * @param type $body - data to put it in body of request
     * @param type $method - method to append in link after .../NextPin/
     */
    private function nextpin_API_call($token, $body, $method){
        $headers = array
            (
            'token: ' . $token,
            'Content-Type: application/json'
        );
        //error_log(json_encode($body, JSON_UNESCAPED_SLASHES));
        //error_log(json_encode($headers));
        #Send Reponse To FireBase Server	
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://traffic.ijs.si/NextPin/'.$method);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
        $result = curl_exec($ch);
        curl_close($ch);
        
        $myfile = fopen("nextpin_test3.txt", "a+") or die("Unable to open file!");
                //if($this->params['action'] != 'mazaUpdateTrackingLog'){
                $date = new DateTime();
                $date = $date->format('d.m.Y H:i:s');
                //}
                    fwrite($myfile, $date.'   nextpin_API_call'."\n");
                    fwrite($myfile, "\n".json_encode($headers)."\n");
                    
                    fwrite($myfile, json_encode($body, JSON_UNESCAPED_SLASHES)."\n");
                    
                    fwrite($myfile, json_encode($result, JSON_UNESCAPED_SLASHES)."\n");
                    
                    $txt = "------------------------------------------------------------------------------------------------\n\n";
                    fwrite($myfile, $txt);
                    fclose($myfile);
        
        //error_log(json_encode($result));
    }
    
    public function writeintxt ($text){
                $myfile = fopen("nextpin_test3.txt", "a+") or die("Unable to open file!");
                //if($this->params['action'] != 'mazaUpdateTrackingLog'){
                $date = new DateTime();
                $date = $date->format('d.m.Y H:i:s');
                //}
                    fwrite($myfile, $date.'    '.$text."\n");
                    
                    $txt = "------------------------------------------------------------------------------------------------\n\n";
                    fwrite($myfile, $txt);
                    fclose($myfile);
    }
    
    /**
     * Run tracking for this survey
     * Turn tracking on in DB and send them to subscriptors via FCM
     */
    public function maza_run_tracking(){
        $activity_recognition = $_POST['maza_tracking_activity_recognition'];
        $tracking_accuracy = $_POST['maza_tracking_accuracy'];
        $interval_wanted = $_POST['maza_tracking_interval_wanted'];
        $interval_fastes = $_POST['maza_tracking_interval_fastes'];
        $displacement_min = $_POST['maza_tracking_displacement_min'];
        $ar_interval_wanted = $_POST['maza_tracking_ar_interval_wanted'];
        
        $sel = sisplet_query("SELECT COUNT(id) as cnt FROM maza_srv_tracking WHERE ank_id = '" . $this->_ank_id . "';", 'obj');
        if($sel->cnt > 0)
            sisplet_query("UPDATE maza_srv_tracking SET tracking_on='1', activity_recognition='$activity_recognition', "
                . "tracking_accuracy='$tracking_accuracy', interval_wanted='$interval_wanted',  "
                . "interval_fastes='$interval_fastes', displacement_min='$displacement_min', "
                . "ar_interval_wanted='$ar_interval_wanted' WHERE ank_id='" . $this->_ank_id . "'");
        else
            sisplet_query("INSERT INTO maza_srv_tracking (ank_id, tracking_on, activity_recognition, tracking_accuracy, interval_wanted, interval_fastes, displacement_min, ar_interval_wanted) "
                    . "VALUES ('".$this->_ank_id."', '1', '".$activity_recognition."', '".$tracking_accuracy."', '".$interval_wanted."', '".$interval_fastes."', '".$displacement_min."', '".$ar_interval_wanted."')");
        
        $this->ajax_sendNotification();
    }
    
    /**
     * Calncel all activities for this survey
     * Turn activities off in DB and send cancelation to all subscriptors via FCM
     */
    public function maza_cancel_tracking(){
        $msg = array
            (
            'action' => 'cancel_tracking',
            'srv_id' => $this->_ank_id
        );
        $this->send_FCM_message($msg, 'tracking');
        
        $this->maza_cancel_tracking_db();
        header('location: index.php?anketa=' . $this->_ank_id . '&a=maza&m=maza_set_tracking');
    }
    
    public function maza_cancel_tracking_db(){
        sisplet_query("UPDATE maza_srv_tracking SET tracking_on='0' WHERE ank_id = '" . $this->_ank_id . "'");
    }
    
    public function maza_check_expired_surveys(){
        $arr = sisplet_query("SELECT ank.id, ank.expire, ank.active, mo.ank_id, mo.modul, mo.vrednost FROM srv_anketa AS ank "
                    . "LEFT JOIN (SELECT * FROM srv_anketa_module) AS mo ON ank.id = mo.ank_id "
                    . "WHERE ank.active = '1' AND ank.expire < CURDATE() AND mo.modul = 'maza' AND mo.vrednost = '1'", 'array');
        
        if($arr){
            foreach ($arr as $ank){
                $this -> maza_off($ank['ank_id']);
            }
        }
    }
}
?>