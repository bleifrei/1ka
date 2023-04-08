<?php

class SurveyAktivnost{


	public function __construct(){
		global $lang;
		
		//echo '<br><br>Začasno onemogočeno zaradi težav z bazo!';
		//die();
	}

	
	/**
	 * @desc prikaze diagnostiko anket
	 */
	public function diagnostics() {
		global $lang, $global_user_id, $admin_type;

        $sum = 0;
        $sum_survey = 0;
		
		if ($_GET['time'] > 0)
			$time = $_GET['time'];
		else
			$time = '1 month';
		if (isset ($_GET['type']))
			$type = $_GET['type'];
		else
			$type = 'users';
		if (isset ($_GET['from']))
			$from = $_GET['from'];
		else
			$from = '';
		if (isset ($_GET['to']))
			$to = $_GET['to'];
		else
			$to = '';
			
					
		echo '<form id="diagnostics_form" action="index.php" method="get">';
		
		echo '<input type="hidden" name="a" value="diagnostics" />';
        
		$testdata = (isset($_GET['testdata']) && $_GET['testdata']=='1') ? 1 : 0;
		$testdataauto = (isset($_GET['testdataauto']) && $_GET['testdataauto']=='1') ? 1 : 0;
		$uvoz = (isset($_GET['uvoz']) && $_GET['uvoz']=='1') ? 1 : 0;
		
		$ustrezni = (isset($_GET['ustrezni']) && $_GET['ustrezni']=='0') ? 0 : 1;
		$delnoustrezni = (isset($_GET['delnoustrezni']) && $_GET['delnoustrezni']=='0') ? 0 : 1;
		$neustrezni = (isset($_GET['neustrezni']) && $_GET['neustrezni']=='1') ? 1 : 0;
		$mailsent = (isset($_GET['mailsent']) && $_GET['mailsent']=='1') ? 1 : 0;
		
		$language = (isset($_GET['language'])) ? $_GET['language'] : 0;
    
        if(AppSettings::getInstance()->getSetting('app_settings-commercial_packages') === true){

            $package_1ka = (isset($_GET['package_1ka']) && $_GET['package_1ka']=='0') ? 0 : 1;
            $package_2ka = (isset($_GET['package_2ka']) && $_GET['package_2ka']=='0') ? 0 : 1;
            $package_3ka = (isset($_GET['package_3ka']) && $_GET['package_3ka']=='0') ? 0 : 1;
            
            echo '<span>'.$lang['srv_narocilo_paket'].':</span>';

            echo '<input type="hidden" name="package_1ka" id="package_1ka_hidden" value="'.$package_1ka.'" />';
            echo '<input type="checkbox" value="1" id="package_1ka" '.($package_1ka == 1 ? ' checked="checked"' : '').'" onchange="$(\'#package_1ka_hidden\').val('.($package_1ka==1 ? '0' : '1').');"><label for="package_1ka">1KA</label>';
            echo '<input type="hidden" name="package_2ka" id="package_2ka_hidden" value="'.$package_2ka.'" />';
            echo '<span class="spaceLeft"><input type="checkbox" value="1" id="package_2ka" '.($package_2ka == 1 ? ' checked="checked"' : '').' onchange="$(\'#package_2ka_hidden\').val('.($package_2ka==1 ? '0' : '1').');"><label for="package_2ka">2KA</label></span>';
            echo '<input type="hidden" name="package_3ka" id="package_3ka_hidden" value="'.$package_3ka.'" />';
            echo '<span class="spaceLeft"><input type="checkbox" value="1" id="package_3ka" '.($package_3ka == 1 ? ' checked="checked"' : '').' onchange="$(\'#package_3ka_hidden\').val('.($package_3ka==1 ? '0' : '1').');"><label for="package_3ka">3KA</label></span>';
            
            echo '<span class="spaceLeft spaceRight bold">|</span>';
        }

		echo '<input type="checkbox" value="1" id="testdata" name="testdata" '.($testdata == 1 ? ' checked="checked"' : '').'"><label for="testdata">'.$lang['srv_diagnostics_filter_test'].'</label>';
		echo '<span class="spaceLeft"><input type="checkbox" value="1" id="testdataauto" name="testdataauto" '.($testdataauto == 1 ? ' checked="checked"' : '').'"><label for="testdataauto">'.$lang['srv_diagnostics_filter_autotest'].'</label></span>';
		echo '<input type="hidden" name="uvoz" id="uvoz_hidden" value="'.$uvoz.'" />';
		echo '<span class="spaceLeft"><input type="checkbox" id="uvoz" '.($uvoz == 1 ? ' checked="checked"' : '').' onchange="$(\'#uvoz_hidden\').val('.($uvoz==1 ? '0' : '1').');"><label for="uvoz">'.$lang['srv_diagnostics_filter_import'].'</label></span>';
		
		echo '<input type="hidden" name="ustrezni" id="ustrezni_hidden" value="'.$ustrezni.'" />';
		echo '<span class="spaceLeft bold">|</span><span class="spaceLeft"></span><input type="checkbox" id="ustrezni" '.($ustrezni == 1 ? ' checked="checked"' : '').' onchange="$(\'#ustrezni_hidden\').val('.($ustrezni==1 ? '0' : '1').');"><label for="ustrezni">'.$lang['srv_diagnostics_filter_6'].'</label>';
		echo '<input type="hidden" name="delnoustrezni" id="delnoustrezni_hidden" value="'.$delnoustrezni.'" />';
		echo '<span class="spaceLeft"><input type="checkbox" id="delnoustrezni" '.($delnoustrezni == 1 ? ' checked="checked"' : '').' onchange="$(\'#delnoustrezni_hidden\').val('.($delnoustrezni==1 ? '0' : '1').');"><label for="delnoustrezni">'.$lang['srv_diagnostics_filter_5'].'</label></span>';
		echo '<input type="hidden" name="neustrezni" id="neustrezni_hidden" value="'.$neustrezni.'" />';
		echo '<span class="spaceLeft"><input type="checkbox" value="1" id="neustrezni" '.($neustrezni == 1 ? ' checked="checked"' : '').' onchange="$(\'#neustrezni_hidden\').val('.($neustrezni==1 ? '0' : '1').');"><label for="neustrezni">'.$lang['srv_diagnostics_filter_34'].'</label></span>';
		echo '<span class="spaceLeft"><input type="checkbox" value="1" id="mailsent" name="mailsent" '.($mailsent == 1 ? ' checked="checked"' : '').'><label for="mailsent">'.$lang['srv_diagnostics_filter_012'].'</label></span>';
		
		echo '<span class="spaceLeft bold">|</span><span class="spaceLeft"></span>'.$lang['lang'].': <select id="language" name="language">';
		echo '<option value="0" '.($language=='0' ? ' selected' : '').'>'.$lang['srv_diagnostics_filter_lang_all'].'</option>';
		echo '<option value="1" '.($language=='1' ? ' selected' : '').'>'.$lang['srv_diagnostics_filter_lang_slo'].'</option>';
		echo '<option value="2" '.($language=='2' ? ' selected' : '').'>'.$lang['srv_diagnostics_filter_lang_ang'].'</option>';
		
		echo '<input type="button" class="spaceLeft" value="'.$lang['srv_coding_filter'].'" onClick="this.form.submit();">';
		
		echo '<br /><br />';
		
		echo ''.$lang['srv_diagnostics_total'].' <select name="type" onchange="this.form.submit();">';
		echo '<option value="users"' . ($type == 'users' ? ' selected' : '') . '>'.$lang['srv_diagnostics_respondentov'].'</option>';
		echo '<option value="emails"' . ($type == 'emails' ? ' selected' : '') . '>'.$lang['srv_email-vabila'].'</option>';
		echo '<option value="pages"' . ($type == 'pages' ? ' selected' : '') . '>'.$lang['srv_diagnostics_strani'].'</option>';
		echo '<option value="analiza"' . ($type == 'analiza' ? ' selected' : '') . '>'.$lang['srv_diagnostics_analiza'].'</option>';
		echo '<option value="graph"' . ($type == 'graph' ? ' selected' : '') . '>'.$lang['srv_diagnostics_graph'].'</option>';
		echo '<option value="editing"' . ($type == 'editing' ? ' selected' : '') . '>'.$lang['srv_diagnostics_editing'].'</option>';
		echo '</select> '.$lang['srv_diagnostics_in'].' ';
		
		echo '<select id="diagnostics_date_selected" name="time" onchange="diagnosticsChooseDate()">';
		echo '<option value="1 minute"' . ($time == '1 minute' ? ' selected' : '') . '>'.$lang['srv_diagnostics_1 minute'].'</option>';
		echo '<option value="5 minute"' . ($time == '5 minute' ? ' selected' : '') . '>'.$lang['srv_diagnostics_5 minute'].'</option>';
		echo '<option value="10 minute"' . ($time == '10 minute' ? ' selected' : '') . '>'.$lang['srv_diagnostics_10 minute'].'</option>';
		echo '<option value="30 minute"' . ($time == '30 minute' ? ' selected' : '') . '>'.$lang['srv_diagnostics_30 minute'].'</option>';
		echo '<option value="1 hour"' . ($time == '1 hour' ? ' selected' : '') . '>'.$lang['srv_diagnostics_1 hour'].'</option>';
		echo '<option value="6 hour"' . ($time == '6 hour' ? ' selected' : '') . '>'.$lang['srv_diagnostics_6 hour'].'</option>';
		echo '<option value="12 hour"' . ($time == '12 hour' ? ' selected' : '') . '>'.$lang['srv_diagnostics_12 hour'].'</option>';
		echo '<option value="1 day"' . ($time == '1 day' ? ' selected' : '') . '>'.$lang['srv_diagnostics_1 day'].'</option>';
		echo '<option value="2 day"' . ($time == '2 day' ? ' selected' : '') . '>'.$lang['srv_diagnostics_2 day'].'</option>';
		echo '<option value="5 day"' . ($time == '5 day' ? ' selected' : '') . '>'.$lang['srv_diagnostics_5 day'].'</option>';
		echo '<option value="7 day"' . ($time == '7 day' ? ' selected' : '') . '>'.$lang['srv_diagnostics_7 day'].'</option>';
		echo '<option value="14 day"' . ($time == '14 day' ? ' selected' : '') . '>'.$lang['srv_diagnostics_14 day'].'</option>';
		echo '<option value="1 month"' . ($time == '1 month' ? ' selected' : '') . '>'.$lang['srv_diagnostics_1 month'].'</option>';
		echo '<option value="3 month"' . ($time == '3 month' ? ' selected' : '') . '>'.$lang['srv_diagnostics_3 month'].'</option>';
		echo '<option value="6 month"' . ($time == '6 month' ? ' selected' : '') . '>'.$lang['srv_diagnostics_6 month'].'</option>';
		echo '<option id="option_99date" value="99date"' . ($time == '99date' ? ' selected' : '') . '>'.$lang['srv_diagnostics_choose_date'].'</option>';
		echo '</select> ';
		
		// Datum - od
		echo ''.$lang['srv_diagnostics_orfrom'].' <input type="text" id="from" name="from" value="' . $from . '" '. ($time != '99date' ? ' disabled' : '') .' />';
		echo ' <span class="faicon calendar_icon icon-as_link" onclick="changeSelectOption()" id="from_img"></span>';

		// Datum - do
		echo ' '.$lang['srv_diagnostics_to'].' <input type="text" id="to" name="to" value="' . $to . '" '. ($time != '99date' ? ' disabled' : '') .'/>';
		echo ' <span class="faicon calendar_icon icon-as_link" onclick="changeSelectOption()" id="to_img"></span>';

		echo '<input type="submit" class="spaceLeft pointer" value="'.$lang['hour_show'].'" />';
        echo '<label class="srv_diagnostic_total_sub">'.$lang['srv_diagnostics_sum_total'].'</label><label class="srv_diagnostic_total_sub_label">'.$sum.'</label>';
        echo '<label class="srv_diagnostic_total_sub">'.$lang['srv_diagnostics_sum_total_survey'].'</label><label class="srv_diagnostic_total_survey_label">'.$sum_survey.'</label>';
		
		echo '</form><br />';

		if ($type == 'pages')
			$time_edit = 'srv_user_grupa_active.time_edit';
		elseif ($type == 'users')
			$time_edit = 'srv_user.time_edit';
		elseif ($type=='analiza')
			$time_edit = 'srv_tracking_active.datetime';
		elseif ($type=='editing')
			$time_edit = 'srv_tracking_active.datetime';
		elseif ($type=='graph')
			$time_edit = 'srv_tracking_active.datetime';
		elseif ($type=='emails')
			$time_edit = 'date_sent';
			

        if($time == '99date' && $from == '' && $to == ''){
			if ($type == 'users')
				$interval = "AND ($time_edit > NOW() - INTERVAL 1 month OR (srv_user.time_edit='0000-00-00 00:00:00' AND srv_user.time_insert > NOW() - INTERVAL 1 month))";
			else
				$interval = "AND $time_edit > NOW() - INTERVAL 1 month";
        }
		else if ($from == '' && $to == ''){
			if ($type == 'users')
				$interval = "AND ($time_edit > NOW() - INTERVAL $time OR (srv_user.time_edit='0000-00-00 00:00:00' AND srv_user.time_insert > NOW() - INTERVAL $time))";
			else
				$interval = "AND $time_edit > NOW() - INTERVAL $time";
		} 
		else{
			if ($type == 'users')
				$interval = "AND ('$from' <= $time_edit AND $time_edit <= '$to' OR (srv_user.time_edit='0000-00-00 00:00:00' AND '$from' <= srv_user.time_insert AND srv_user.time_insert <= '$to'))";
			else
				$interval = "AND '$from' <= $time_edit AND $time_edit <= '$to' ";
		}
		
		if ($type == 'pages') {
			$sql = sisplet_query("
			                SELECT COUNT(grupe.usr_id) AS responses, grupe.ank_id, srv_anketa.naslov
			                FROM (
			                    SELECT *
			                    FROM srv_user_grupa_active, srv_grupa
			                    WHERE srv_user_grupa_active.gru_id = srv_grupa.id
			                    $interval
			                ) AS grupe, srv_anketa
			                WHERE grupe.ank_id=srv_anketa.id
			                    AND (srv_anketa.dostop >= '" . $admin_type . "' OR srv_anketa.id IN 
			                        (SELECT ank_id FROM srv_dostop WHERE uid='" . $global_user_id . "'))
			                GROUP BY grupe.ank_id
			                ORDER BY responses DESC
			            ");
						
		} elseif ($type == 'users') {
            $filter = $this->diagnostics_get_user_settings();
            $filter_package = $this->diagnostics_get_user_package();
			$filter_lang = $this->diagnostics_get_lang_filter();
			
			$sql = sisplet_query("
			                SELECT COUNT(srv_user.id) AS responses, users.email, srv_user.ank_id, srv_anketa.naslov, user_access.package_id
                            FROM srv_user, srv_anketa, users
                            LEFT JOIN user_access
                                ON user_access.usr_id=users.id
			                WHERE ".$filter." AND ".$filter_lang." ".$filter_package." srv_user.ank_id > '0'
                                AND srv_anketa.id=srv_user.ank_id
                                AND (srv_anketa.dostop >= '" . $admin_type . "' OR srv_anketa.id IN 
                                (SELECT ank_id FROM srv_dostop WHERE uid='" . $global_user_id . "'))
                                $interval
                                AND srv_anketa.insert_uid = users.id
			                GROUP BY ank_id
			                ORDER BY responses DESC
			            ");
		} elseif ($type == 'analiza') {
				
			$sql = sisplet_query("
				SELECT COUNT(srv_tracking_active.ank_id) AS responses, srv_tracking_active.ank_id, srv_anketa.naslov
				FROM srv_tracking_active, srv_anketa
				WHERE srv_anketa.id = srv_tracking_active.ank_id
                    AND (`get` LIKE '%analiza%' OR `get` LIKE '%analysis%' )
                    AND NOT (`get` LIKE '%charts%') 
                    AND (srv_anketa.dostop >= '" . $admin_type . "' OR srv_anketa.id IN 
                        (SELECT ank_id FROM srv_dostop WHERE uid='" . $global_user_id . "'))
                        $interval
				GROUP BY ank_id
				ORDER BY responses DESC
			");
			    		
		} elseif ($type == 'graph') {
				
			$sql = sisplet_query("
				SELECT COUNT(srv_tracking_active.ank_id) AS responses, srv_tracking_active.ank_id, srv_anketa.naslov
				FROM srv_tracking_active, srv_anketa
				WHERE srv_anketa.id = srv_tracking_active.ank_id
                    AND (`get` LIKE '%analiza%' OR `get` LIKE '%analysis%' )
                    AND (`get` LIKE '%charts%')
                    AND (srv_anketa.dostop >= '" . $admin_type . "' OR srv_anketa.id IN 
                        (SELECT ank_id FROM srv_dostop WHERE uid='" . $global_user_id . "'))
                        $interval
				GROUP BY ank_id
				ORDER BY responses DESC
			");
			    		
		} elseif ($type == 'editing') {
				
			$sql = sisplet_query("
				SELECT COUNT(srv_tracking_active.ank_id) AS responses, srv_tracking_active.ank_id, srv_anketa.naslov
				FROM srv_tracking_active, srv_anketa
				WHERE srv_anketa.id = srv_tracking_active.ank_id
                    AND (srv_anketa.dostop >= '" . $admin_type . "' OR srv_anketa.id IN 
                        (SELECT ank_id FROM srv_dostop WHERE uid='" . $global_user_id . "'))
                        $interval
				GROUP BY ank_id
				ORDER BY responses DESC
			");
			    		
		} elseif ($type == 'emails') {
			
			$sql = sisplet_query("
				SELECT COUNT(srv_invitations_recipients.id) AS responses, srv_invitations_recipients.ank_id, srv_anketa.naslov
				FROM srv_invitations_recipients, srv_anketa
				WHERE srv_anketa.id = srv_invitations_recipients.ank_id AND srv_invitations_recipients.sent = '1'
                    AND (srv_anketa.dostop >= '" . $admin_type . "' OR srv_anketa.id IN 
                        (SELECT ank_id FROM srv_dostop WHERE uid='" . $global_user_id . "'))
                        $interval
				GROUP BY ank_id
				ORDER BY responses DESC
			");
		
			
		}

		if (!$sql) {
			echo mysqli_error($GLOBALS['connect_db']);
		}

		global $site_url;
		
		echo '<table style="width:70%">';
		$max = -1;
		
		while ($row = mysqli_fetch_array($sql)) {
			if ($max == -1)
			    $max = max($row['responses'], $max)*1.3;

			echo '<tr>';
			echo '<td><a href="'.SurveyInfo::getSurveyLink($row['ank_id']).'?preview=on&pages=all" target="_blank">' . $row['naslov'] . '</a></td>';
			echo '<td>'.$row['email'].'</td>';
            $sum = $sum + $row['responses'];
            $sum_survey = $sum_survey + 1;
			echo '<td style="width:60%"><div class="graph_lb" style="float: left; width:' . (round($row['responses'] / $max * 100, 0)) . '%">&nbsp;</div><div style="float:left">&nbsp;'.$row['responses'].'</div></td>';
			echo '</tr>';
		}
		
        echo '<script type="text/javascript">
            function runSubTotal(){
                $(".srv_diagnostic_total_sub_label").text('.$sum.');
				$(".srv_diagnostic_total_survey_label").text('.$sum_survey.');
            }
            window.onload = runSubTotal;
        </script>';


		echo '</table>';
	}
	
	public function diagnostics_time_span () {
		global $lang;
		
		$interval = $this->diagnostics_get_interval('month');
        $filter = $this->diagnostics_get_user_settings();
        $filter_package = $this->diagnostics_get_user_package();
		$filter_lang = $this->diagnostics_get_lang_filter();
		
		$this->diagnostics_show_interval('time_span');

		$sql = sisplet_query("SELECT COUNT(*) as count, $interval[srv_anketa] AS datedate, YEAR(insert_time) AS color, insert_time AS date 
                                FROM srv_anketa 
                                LEFT JOIN user_access
                                    ON user_access.usr_id=srv_anketa.insert_uid 
                                WHERE ".$filter_lang." ".$filter_package." insert_time > 0 AND YEAR(insert_time)>=2009 
                                GROUP BY datedate 
                                ORDER BY insert_time ASC
                            ");
		$this->diagnostics_graph($sql, $lang['srv_diagnostics_graph_month_survey'], 'day', 'year', false);
		$this->diagnostics_graph($sql, $lang['srv_diagnostics_graph_sum_survey'], 'day', 'year', true);
		
		$sql = sisplet_query("SELECT COUNT(*) as count, $interval[users] AS datedate, YEAR(when_reg) AS color, when_reg AS date 
                                FROM users 
                                LEFT JOIN user_access
                                    ON user_access.usr_id=users.id 
                                WHERE ".$filter_package." when_reg > 0 AND YEAR(when_reg)>=2009 
                                GROUP BY datedate 
                                ORDER BY when_reg ASC
                            ");
		$this->diagnostics_graph($sql, $lang['srv_diagnostics_graph_month_register'], 'day', 'year', false);
		$this->diagnostics_graph($sql, $lang['srv_diagnostics_graph_sum_register'], 'day', 'year', true);
		
		$sql = sisplet_query("SELECT COUNT(*) as count, $interval[srv_user] AS datedate, YEAR(srv_user.time_insert) AS color, srv_user.time_insert AS date
                                FROM srv_user, srv_anketa
                                LEFT JOIN user_access
                                    ON user_access.usr_id=srv_anketa.insert_uid 
                                WHERE srv_user.ank_id=srv_anketa.id AND ".$filter." AND ".$filter_lang." ".$filter_package." time_insert > 0 AND YEAR(time_insert)>=2009 
                                GROUP BY datedate 
                                ORDER BY time_insert ASC
                            ");
		$this->diagnostics_graph($sql, $lang['srv_diagnostics_graph_month_answer'], 'day', 'year', false);
		$this->diagnostics_graph($sql, $lang['srv_diagnostics_graph_sum_answer'], 'day', 'year', true);	
	}
	
	public function diagnostics_time_span_yearly () {
		global $lang;
		
		$interval = $this->diagnostics_get_interval('day');
        $filter = $this->diagnostics_get_user_settings();
        $filter_package = $this->diagnostics_get_user_package();
		$filter_lang = $this->diagnostics_get_lang_filter();
		
		$this->diagnostics_show_interval('time_span_yearly');
		
		$sql = sisplet_query("SELECT COUNT(*) as count, $interval[srv_anketa] AS datedate, MONTH(insert_time) AS color, insert_time AS date 
                                FROM srv_anketa 
                                LEFT JOIN user_access
                                    ON user_access.usr_id=srv_anketa.insert_uid 
                                WHERE ".$filter_lang." ".$filter_package." insert_time > 0 AND insert_time >= '".date("Y-m-1 0:00:00", strtotime("-11 month"))."' 
                                GROUP BY datedate 
                                ORDER BY insert_time ASC
                            ");
		$this->diagnostics_graph($sql, $lang['srv_diagnostics_graph_month_survey'], 'day', 'month', false);

		$sql = sisplet_query("SELECT COUNT(*) as count, $interval[users] AS datedate, MONTH(when_reg) AS color, when_reg AS date 
                                FROM users 
                                LEFT JOIN user_access
                                    ON user_access.usr_id=users.id 
                                WHERE ".$filter_package." when_reg > 0 AND when_reg >= '".date("Y-m-1 0:00:00", strtotime("-11 month"))."' 
                                GROUP BY datedate 
                                ORDER BY when_reg ASC
                            ");
		$this->diagnostics_graph($sql, $lang['srv_diagnostics_graph_month_register'], 'day', 'month', false);
		
		$sql = sisplet_query("SELECT COUNT(*) as count, $interval[srv_user] AS datedate, MONTH(srv_user.time_insert) AS color, srv_user.time_insert AS date 
                                FROM srv_user, srv_anketa 
                                LEFT JOIN user_access
                                    ON user_access.usr_id=srv_anketa.insert_uid 
                                WHERE srv_user.ank_id=srv_anketa.id AND ".$filter." AND ".$filter_lang." ".$filter_package." time_insert > 0 AND time_insert >= '".date("Y-m-1 0:00:00", strtotime("-11 month"))."' 
                                GROUP BY datedate 
                                ORDER BY time_insert ASC
                            ");
		$this->diagnostics_graph($sql, $lang['srv_diagnostics_graph_month_answer'], 'day', 'month', false);	
	}
	
	public function diagnostics_time_span_monthly () {
		global $lang;
		
		$interval = $this->diagnostics_get_interval('day');
        $filter = $this->diagnostics_get_user_settings();
        $filter_package = $this->diagnostics_get_user_package();
		$filter_lang = $this->diagnostics_get_lang_filter();
		
		$this->diagnostics_show_interval('time_span_monthly');
		
		$sql = sisplet_query("SELECT COUNT(*) as count, $interval[srv_anketa] AS datedate, MONTH(insert_time) AS color, insert_time AS date 
                                FROM srv_anketa 
                                LEFT JOIN user_access
                                    ON user_access.usr_id=srv_anketa.insert_uid 
                                WHERE ".$filter_lang." ".$filter_package." insert_time > 0 AND insert_time >= '".date("Y-m-1 0:00:00", strtotime("-2 month"))."' 
                                GROUP BY datedate 
                                ORDER BY insert_time ASC
                            ");
		$this->diagnostics_graph($sql, $lang['srv_diagnostics_graph_month_survey'], 'day', 'month', false);

		
		$sql = sisplet_query("SELECT COUNT(*) as count, $interval[users] AS datedate, MONTH(when_reg) AS color, when_reg AS date 
                                FROM users 
                                LEFT JOIN user_access
                                    ON user_access.usr_id=users.id 
                                WHERE ".$filter_package." when_reg > 0 AND when_reg >= '".date("Y-m-1 0:00:00", strtotime("-2 month"))."' 
                                GROUP BY datedate 
                                ORDER BY when_reg ASC
                            ");
		$this->diagnostics_graph($sql, $lang['srv_diagnostics_graph_month_register'], 'day', 'month', false);
		
		
		$sql = sisplet_query("SELECT COUNT(*) as count, $interval[srv_user] AS datedate, MONTH(srv_user.time_insert) AS color, srv_user.time_insert AS date 
                                FROM srv_user, srv_anketa 
                                LEFT JOIN user_access
                                    ON user_access.usr_id=srv_anketa.insert_uid 
                                WHERE srv_user.ank_id=srv_anketa.id AND ".$filter." AND ".$filter_lang." ".$filter_package." time_insert > 0 AND time_insert >= '".date("Y-m-1 0:00:00", strtotime("-2 month"))."' 
                                GROUP BY datedate 
                                ORDER BY time_insert ASC
                            ");
		$this->diagnostics_graph($sql, $lang['srv_diagnostics_graph_month_answer'], 'day', 'month', false);
		
	}
	
	public function diagnostics_time_span_daily () {
		global $lang;
		
		$interval = $this->diagnostics_get_interval('hour');
		$filter = $this->diagnostics_get_user_settings();
		$filter_package = $this->diagnostics_get_user_package();
		$filter_lang = $this->diagnostics_get_lang_filter();
		
		$this->diagnostics_show_interval('time_span_daily');
		
		$sql = sisplet_query("SELECT COUNT(*) as count, $interval[srv_anketa] AS datedate, DAYOFWEEK(insert_time) AS color, insert_time AS date 
                                FROM srv_anketa 
                                LEFT JOIN user_access
                                    ON user_access.usr_id=srv_anketa.insert_uid 
                                WHERE ".$filter_lang." ".$filter_package." insert_time >= '".date("Y-m-d 0:00:00", strtotime("-1 week"))."' 
                                GROUP BY datedate 
                                ORDER BY insert_time
                            ");
		$this->diagnostics_graph($sql, $lang['srv_diagnostics_graph_week_survey'], 'hour', 'day', false);
		
		
		$sql = sisplet_query("SELECT COUNT(*) as count, $interval[srv_tracking_active] AS datedate, DAYOFWEEK(datetime) AS color, datetime AS date 
                                FROM srv_tracking_active 
                                LEFT JOIN user_access
                                    ON user_access.usr_id=srv_tracking_active.user
                                WHERE ".$filter_package." datetime >= '".date("Y-m-d 0:00:00", strtotime("-1 week"))."' 
                                GROUP BY datedate 
                                ORDER BY datetime
                            ");
		$this->diagnostics_graph($sql, $lang['srv_diagnostics_graph_week_edit'], 'hour', 'day', false);
		
		
		$sql = sisplet_query("SELECT COUNT(*) as count, $interval[srv_user] AS datedate, DAYOFWEEK(srv_user.time_insert) AS color, srv_user.time_insert AS date 
                                FROM srv_user, srv_anketa 
                                LEFT JOIN user_access
                                    ON user_access.usr_id=srv_anketa.insert_uid 
                                WHERE srv_user.ank_id=srv_anketa.id AND ".$filter." AND ".$filter_lang." ".$filter_package." time_insert >= '".date("Y-m-d 0:00:00", strtotime("-1 week"))."' 
                                GROUP BY datedate 
                                ORDER BY time_insert
                            ");
		$this->diagnostics_graph($sql, $lang['srv_diagnostics_graph_week_answer'], 'hour', 'day', false);	
	}

	/**
	 * @desc prikaze diagnostiko anket
	 */
	public function diagnostics_paradata() {
		global $lang, $global_user_id, $admin_type, $site_url;

		set_time_limit(1800); # 30 minut
		
		// Koliko respondentov vzamemo (random)
		$limit = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? $_GET['limit'] : 1000;	
		
		// Obdobje
		$date_from = (isset($_GET['from'])) ? $_GET['from'] : date('j.n.Y', strtotime("-1 year"));
		$date_from_mysql = date("Y-m-d", strtotime($date_from)).' 00:00:00';
		$date_to = (isset($_GET['to'])) ? $_GET['to'] : date('j.n.Y');
		$date_to_mysql = date("Y-m-d", strtotime($date_to)).' 00:00:00';

		// Forma za filtriranje
		echo '<form name="diagnostics_paradata" action="index.php" method="get">';
		
		echo '<input type="hidden" name="a" value="diagnostics">';
		echo '<input type="hidden" name="t" value="paradata">';
		
		// Vseh random enot
		echo '<span>'.$lang['srv_inv_dashboard_tbl_all'].'</span> <input type="text" name="limit" value="'.$limit.'" style="padding:1px 3px; font-size:12px; width:70px; margin-right:20px;" />';	
		
		// Datum
		echo $lang['s_from'].' <input type="text" id="from" name="from" value="' . $date_from . '" />';
		echo ' <span class="faicon calendar_icon icon-as_link" onclick="diagnosticsParadataChooseDate();" id="from_img"></span> ';

		echo $lang['s_to'].' <input type="text" id="to" name="to" value="' . $date_to . '" />';
		echo ' <span class="faicon calendar_icon icon-as_link" onclick="diagnosticsParadataChooseDate();" id="to_img"></span>';
		
		echo '<script>diagnosticsParadataChooseDate();</script>';
		
		// Gumb prikazi
		echo '<input type="button" class="pointer" value="'.$lang['hour_show'].'" onClick="this.form.submit();" style="margin-left:20px;">';
		
		echo '</form><br />';
	
			
		// Gledamo vse veljavne respondente iz leta 2016
		$sql = sisplet_query("SELECT id, ank_id, useragent 
								FROM srv_user 
								WHERE last_status IN ('5', '6') AND lurker='0' AND time_insert<'".$date_to_mysql."' AND time_insert>='".$date_from_mysql."'
								ORDER BY rand()
								LIMIT ".$limit."");
		if (!$sql) {echo mysqli_error($GLOBALS['connect_db']); die();}


		$statistics = array();
		
		while ($row = mysqli_fetch_array($sql)) {
			
			//var_dump($row);
			
			$browser_detect = get_browser($row['useragent'], true);
			//var_dump($browser_detect);
			
			// Naprava
			$statistics['device'][$browser_detect['device_type']]++;
			$statistics['device']['title'] = $lang['srv_para_graph_device'];
			
			// Mobilnik, tablica...
			$browser_detect['ismobiledevice'] = ($browser_detect['ismobiledevice'] == '') ? '0' : $browser_detect['ismobiledevice'];
			$browser_detect['istablet'] = ($browser_detect['istablet'] == '') ? '0' : $browser_detect['istablet'];
			
			$statistics['mobile'][$browser_detect['ismobiledevice']]++;
			$statistics['mobile']['title'] = $lang['srv_para_graph_device1'];
			
			$statistics['tablet'][$browser_detect['istablet']]++;
			$statistics['tablet']['title'] = $lang['srv_para_graph_device2'];
			
			// Brskalnik
			$statistics['browser_name'][$browser_detect['browser']]++;
			$statistics['browser_name']['title'] = $lang['srv_para_graph_browser'];
			//$statistics['browser_version'][$browser_detect['version']]++;
			
			// Operacijski sistem
			$statistics['os'][$browser_detect['platform']]++;
			$statistics['os']['title'] = $lang['srv_para_graph_os'];
			
			// Brskalnik (verzija)
			$statistics['browser'][$browser_detect['parent']]++;
			$statistics['browser']['title'] = $lang['srv_para_graph_browser'];			
		}

		foreach($statistics as $key => $vals){
	
			echo '<fieldset style="width:60%;"><legend>'.$vals['title'].'</legend>';
	
			// Sortiramo vrednosti po velikosti (od najvecje do najmanjse)
			//ksort($vals);
			arsort($vals);

			echo '<table style="width:100%;">';
				
			$max = -1;
			foreach($vals as $key2 => $val){

				if($key2 !== 'title'){
					echo '<tr>';

					if($key2 == '0')
						$key2 = $lang['no'];
					elseif($key2 == '1')
						$key2 = $lang['yes'];
						
					echo '<td>'.$key2.'</td>';
				
					$max = max($val, $max) * 1.2;
					echo '<td style="width:80%"><div class="graph_lb" style="float: left; width:' . (round($val / $max * 100, 0)) . '%">&nbsp;</div><div style="float:left">&nbsp;'.$val.'</div></td>';

					echo '</tr>';
				}
			}
			
			echo '</table>';
			
			echo '</fieldset><br />';
		}
	}
	
	
	/**
	* izrise graf za aktivnosti / diagnostiko anket, uporabnikov itd...
	* 
	* @param mixed $sql
	* @param mixed $text
	* @param mixed $interval
	* @param mixed $color
	* @param mixed $sumarium
	*/
	private function diagnostics_graph ($sql, $text, $interval, $color, $sumarium) {
		
		ob_flush();
		
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0) mysqli_data_seek($sql, 0);
		
		$interval = $_GET['interval'];
		
		$max = 0;
		$sum = 0;
		$prev = 0;
		$c = 0;
		$cols = 0;
		$prev_color = '';
		$avg = array();
		$cur_sum = 0;
		$cur_tot = 0;
		
		while ($row = mysqli_fetch_assoc($sql)) { 
			if ($row['count'] > $max) $max = $row['count'];
			$sum += $row['count'];
			
			if ($prev_color != $row['color']) {
				if ($cur_tot != 0) $avg[$prev_color] = round($cur_sum/$cur_tot, 1);
				//echo $cur_tot.' '.$cur_sum.' '.$prev_color.': '.$avg[$prev_color].'<br>';
				$cur_sum = 0;
				$cur_tot = 0;
				$prev_color = $row['color'];
			}
			$cur_tot ++;
			$cur_sum += $row['count'];
			//echo $cur_tot.' '.$cur_sum.'<br>';
		}
		if ($cur_tot != 0) $avg[$prev_color] = round($cur_sum/$cur_tot, 1);
		
		$prev_color = '';
		
		$max = $max/100; 
		$sum = $sum/100;
		
		echo '<div class="time_span_header">'.$text.' ('.($sumarium?'sum':'max').': '.number_format(($sumarium?$sum:$max)*100, 0, ',', '.').')</div>';
		echo '<div class="time_span_container">';
		
		if (mysqli_num_rows($sql) > 0) mysqli_data_seek($sql, 0);
		while ($row = mysqli_fetch_assoc($sql)) {
			
			if ($interval == 'hour')
				$plusone = date("Y-m-d G", strtotime('+1 '.$interval.'', strtotime($prev.':00')));
			elseif ($interval == 'day')
				$plusone = date("Y-m-d", strtotime('+1 '.$interval.'', strtotime($prev)));
			elseif ($interval == 'week')
				$plusone = date("Y-W", strtotime('+1 '.$interval.'', strtotime($prev)));
			elseif ($interval == 'month')
				$plusone = date("Y-m", strtotime('+1 '.$interval.'', strtotime($prev.'-1')));
			elseif ($interval == 'year')
				$plusone = date("Y", strtotime('+1 '.$interval.'', strtotime($prev.'-1-1')));
			
			$i=0;
			while ( $plusone != $row['datedate'] && $prev!=0 ) {
				if ($interval == 'hour' || $interval == 'day') {} else break;
				
				echo '<div class="time_span '.$color.'_'.$row['color'].'" style="_WIDTH_; height:'.($sumarium?($c/$sum):'0').'%" title="'.$row['datedate'].' (0)"></div>';
				$cols++;
				
				if ($interval == 'hour')
					$plusone = date("Y-m-d G", strtotime('+1 '.$interval.'', strtotime($plusone.':00')));
				elseif ($interval == 'day')
					$plusone = date("Y-m-d", strtotime('+1 '.$interval.'', strtotime($plusone)));
				elseif ($interval == 'week')
					$plusone = date("Y-W", strtotime('+1 '.$interval.'', strtotime($plusone)));
				elseif ($interval == 'month')
					$plusone = date("Y-m", strtotime('+1 '.$interval.'', strtotime($plusone.'-1')));
				elseif ($interval == 'year')
					$plusone = date("Y", strtotime('+1 '.$interval.'', strtotime($plusone.'-1-1')));
				
				if ($i++ > 100) { break; }	// safety break
			}
			$prev = $row['datedate'];
			
			$c += $row['count'];
			
			if ($prev_color != $row['color']) {
				if ($color == 'year') $label = date('Y', strtotime($row['date']));
				if ($color == 'month') $label = date('M Y', strtotime($row['date']));
				if ($color == 'day') $label = date('j.n.Y', strtotime($row['date']));
				
				echo '<span style="position: absolute; bottom: -15px; width: 90px;">'.$label;
				if ( !$sumarium ) echo ' <span style="color:darkgray; font-size:70%" title="x̄ = '.$avg[$row['color']].'">('.$avg[$row['color']].')</span>';
				echo '</span>';
				$prev_color = $row['color'];
			}
			
			echo '<div class="time_span '.$color.'_'.$row['color'].'" style="_WIDTH_; height:'.($sumarium?($c/$sum):($row['count']/$max)).'%" title="'.$row['datedate'].' ('.number_format(($sumarium?$c:$row['count']), 0, ',', '.').')"></div>';
			$cols++;
			
		}
		echo '</div>';
		
		// sirino nastavimo na koncu, ko vidimo koliko je stolpcev (zaradi praznih)
		$var = ob_get_clean();
		if ($cols == 0) $cols = 1;
		$var = str_replace('_WIDTH_', 'width:'.(100/$cols).'%', $var);
		echo $var;
		
		ob_start();
	}

	private function diagnostics_show_interval ($t) {
		global $lang;
		
		echo '<form name="intr" action="index.php" method="get">';
		echo '<input type="hidden" name="a" value="diagnostics">';
		echo '<input type="hidden" name="t" value="'.$t.'">';
		
		$testdata = (isset($_GET['testdata']) && $_GET['testdata']=='1') ? 1 : 0;
		$testdataauto = (isset($_GET['testdataauto']) && $_GET['testdataauto']=='1') ? 1 : 0;
		$uvoz = (isset($_GET['uvoz']) && $_GET['uvoz']=='1') ? 1 : 0;
		
		$ustrezni = (isset($_GET['ustrezni']) && $_GET['ustrezni']=='0') ? 0 : 1;
		$delnoustrezni = (isset($_GET['delnoustrezni']) && $_GET['delnoustrezni']=='0') ? 0 : 1;
		$neustrezni = (isset($_GET['neustrezni']) && $_GET['neustrezni']=='1') ? 1 : 0;
		$mailsent = (isset($_GET['mailsent']) && $_GET['mailsent']=='1') ? 1 : 0;
		
		$language = (isset($_GET['language'])) ? $_GET['language'] : 0;
        
        if(AppSettings::getInstance()->getSetting('app_settings-commercial_packages') === true){

            $package_1ka = (isset($_GET['package_1ka']) && $_GET['package_1ka']=='0') ? 0 : 1;
            $package_2ka = (isset($_GET['package_2ka']) && $_GET['package_2ka']=='0') ? 0 : 1;
            $package_3ka = (isset($_GET['package_3ka']) && $_GET['package_3ka']=='0') ? 0 : 1;
            
            echo '<span>'.$lang['srv_narocilo_paket'].':</span>';

            echo '<input type="hidden" name="package_1ka" id="package_1ka_hidden" value="'.$package_1ka.'" />';
            echo '<input type="checkbox" value="1" id="package_1ka" '.($package_1ka == 1 ? ' checked="checked"' : '').'" onchange="$(\'#package_1ka_hidden\').val('.($package_1ka==1 ? '0' : '1').');"><label for="package_1ka">1KA</label>';
            echo '<input type="hidden" name="package_2ka" id="package_2ka_hidden" value="'.$package_2ka.'" />';
            echo '<span class="spaceLeft"><input type="checkbox" value="1" id="package_2ka" '.($package_2ka == 1 ? ' checked="checked"' : '').' onchange="$(\'#package_2ka_hidden\').val('.($package_2ka==1 ? '0' : '1').');"><label for="package_2ka">2KA</label></span>';
            echo '<input type="hidden" name="package_3ka" id="package_3ka_hidden" value="'.$package_3ka.'" />';
            echo '<span class="spaceLeft"><input type="checkbox" value="1" id="package_3ka" '.($package_3ka == 1 ? ' checked="checked"' : '').' onchange="$(\'#package_3ka_hidden\').val('.($package_3ka==1 ? '0' : '1').');"><label for="package_3ka">3KA</label></span>';
            
            echo '<span class="spaceLeft spaceRight bold">|</span>';
        }

		echo '<input type="checkbox" value="1" id="testdata" name="testdata" '.($testdata == 1 ? ' checked="checked"' : '').'"><label for="testdata">'.$lang['srv_diagnostics_filter_test'].'</label>';
		echo '<span class="spaceLeft"><input type="checkbox" value="1" id="testdataauto" name="testdataauto" '.($testdataauto == 1 ? ' checked="checked"' : '').'"><label for="testdataauto">'.$lang['srv_diagnostics_filter_autotest'].'</label></span>';
		echo '<input type="hidden" name="uvoz" id="uvoz_hidden" value="'.$uvoz.'" />';
		echo '<span class="spaceLeft"><input type="checkbox" id="uvoz" '.($uvoz == 1 ? ' checked="checked"' : '').' onchange="$(\'#uvoz_hidden\').val('.($uvoz==1 ? '0' : '1').');"><label for="uvoz">'.$lang['srv_diagnostics_filter_import'].'</label></span>';
		
		echo '<input type="hidden" name="ustrezni" id="ustrezni_hidden" value="'.$ustrezni.'" />';
		echo '<span class="spaceLeft bold">|</span><span class="spaceLeft"></span><input type="checkbox" id="ustrezni" '.($ustrezni == 1 ? ' checked="checked"' : '').' onchange="$(\'#ustrezni_hidden\').val('.($ustrezni==1 ? '0' : '1').');"><label for="ustrezni">'.$lang['srv_diagnostics_filter_6'].'</label>';
		echo '<input type="hidden" name="delnoustrezni" id="delnoustrezni_hidden" value="'.$delnoustrezni.'" />';
		echo '<span class="spaceLeft"><input type="checkbox" id="delnoustrezni" '.($delnoustrezni == 1 ? ' checked="checked"' : '').' onchange="$(\'#delnoustrezni_hidden\').val('.($delnoustrezni==1 ? '0' : '1').');"><label for="delnoustrezni">'.$lang['srv_diagnostics_filter_5'].'</label></span>';
		echo '<input type="hidden" name="neustrezni" id="neustrezni_hidden" value="'.$neustrezni.'" />';
		echo '<span class="spaceLeft"><input type="checkbox" value="1" id="neustrezni" '.($neustrezni == 1 ? ' checked="checked"' : '').' onchange="$(\'#neustrezni_hidden\').val('.($neustrezni==1 ? '0' : '1').');"><label for="neustrezni">'.$lang['srv_diagnostics_filter_34'].'</label></span>';
		echo '<span class="spaceLeft"><input type="checkbox" value="1" id="mailsent" name="mailsent" '.($mailsent == 1 ? ' checked="checked"' : '').'><label for="mailsent">'.$lang['srv_diagnostics_filter_012'].'</label></span>';

		echo '<span class="spaceLeft bold">|</span><span class="spaceLeft"></span>'.$lang['lang'].': <select id="language" name="language">';
		echo '<option value="0" '.($language=='0' ? ' selected' : '').'>'.$lang['srv_diagnostics_filter_lang_all'].'</option>';
		echo '<option value="1" '.($language=='1' ? ' selected' : '').'>'.$lang['srv_diagnostics_filter_lang_slo'].'</option>';
		echo '<option value="2" '.($language=='2' ? ' selected' : '').'>'.$lang['srv_diagnostics_filter_lang_ang'].'</option>';
		
		echo '<input type="button" class="spaceLeft" value="'.$lang['srv_coding_filter'].'" onClick="this.form.submit();">';
		
		echo '<br /><br />';
	
		echo 'Interval: <select name="interval" onchange="document.intr.submit();">';
		if ( ! in_array($t, array('time_span_yearly', 'time_span')) ) {
			echo '<option value="hour" '.($_GET['interval']=='hour'?'selected':'').'>'.$lang['srv_diagnostics_interval_hour'].'</option>';
		}
		echo '<option value="day" '.($_GET['interval']=='day'?'selected':'').'>'.$lang['srv_diagnostics_interval_day'].'</option>';
		echo '<option value="week" '.($_GET['interval']=='week'?'selected':'').'>'.$lang['srv_diagnostics_interval_week'].'</option>';
		echo '<option value="month" '.($_GET['interval']=='month'?'selected':'').'>'.$lang['srv_diagnostics_interval_month'].'</option>';
		echo '<option value="year" '.($_GET['interval']=='year'?'selected':'').'>'.$lang['srv_diagnostics_interval_year'].'</option>';
		echo '</select>';		
		
		echo '</form><br />';		
	}
	
	private function diagnostics_get_interval ($def = '') {
		
		if ($_GET['interval'] == '') $_GET['interval'] = $def;
		
		if ($_GET['interval'] == 'year') {
			$interval['srv_anketa'] = "YEAR(srv_anketa.insert_time)";
			$interval['users'] = "YEAR(users.when_reg)";
			$interval['srv_user'] = "YEAR(srv_user.time_insert)";
			$interval['srv_tracking_active'] = "YEAR(srv_tracking_active.datetime)";
		} elseif ($_GET['interval'] == 'month') {
			$interval['srv_anketa'] = "CONCAT( YEAR(srv_anketa.insert_time), '-', MONTH(srv_anketa.insert_time) )";
			$interval['users'] = "CONCAT( YEAR(users.when_reg), '-', MONTH(users.when_reg) )";
			$interval['srv_user'] = "CONCAT( YEAR(srv_user.time_insert), '-', MONTH(srv_user.time_insert) )";
			$interval['srv_tracking_active'] = "CONCAT( YEAR(srv_tracking_active.datetime), '-', MONTH(srv_tracking_active.datetime) )";
		} elseif ($_GET['interval'] == 'week') {
			$interval['srv_anketa'] = "CONCAT( YEAR(srv_anketa.insert_time), '-', WEEKOFYEAR(srv_anketa.insert_time) )";
			$interval['users'] = "CONCAT( YEAR(users.when_reg), '-', WEEKOFYEAR(users.when_reg) )";
			$interval['srv_user'] = "CONCAT( YEAR(srv_user.time_insert), '-', WEEKOFYEAR(srv_user.time_insert) )";
			$interval['srv_tracking_active'] = "CONCAT( YEAR(srv_tracking_active.datetime), '-', WEEKOFYEAR(srv_tracking_active.datetime) )";
		} elseif ($_GET['interval'] == 'day') {
			$interval['srv_anketa'] = "DATE(srv_anketa.insert_time)";
			$interval['users'] = "DATE(users.when_reg)";
			$interval['srv_user'] = "DATE(srv_user.time_insert)";
			$interval['srv_tracking_active'] = "DATE(datetime)";
		} elseif ($_GET['interval'] == 'hour') {
			$interval['srv_anketa'] = "CONCAT( DATE(srv_anketa.insert_time), ' ', HOUR(srv_anketa.insert_time)) ";
			$interval['users'] = "CONCAT( DATE(users.when_reg), ' ', HOUR(users.when_reg)) ";
			$interval['srv_user'] = "CONCAT( DATE(srv_user.time_insert), ' ', HOUR(srv_user.time_insert)) ";
			$interval['srv_tracking_active'] = "CONCAT( DATE(srv_tracking_active.datetime), ' ', HOUR(srv_tracking_active.datetime)) ";
		}
		
		return $interval;
	}
	
	// Vrnemo filter za srv_user (filtriranje testnih podatkov, preview, email vabil, ustreznih...)
	private function diagnostics_get_user_settings(){
                
		$testdata = (isset($_GET['testdata']) && $_GET['testdata']=='1') ? 1 : 0;
		$testdataauto = (isset($_GET['testdataauto']) && $_GET['testdataauto']=='1') ? 1 : 0;
		$uvoz = (isset($_GET['uvoz']) && $_GET['uvoz']=='1') ? 1 : 0;
		
		$ustrezni = (isset($_GET['ustrezni']) && $_GET['ustrezni']=='0') ? 0 : 1;
		$delnoustrezni = (isset($_GET['delnoustrezni']) && $_GET['delnoustrezni']=='0') ? 0 : 1;
		$neustrezni = (isset($_GET['neustrezni']) && $_GET['neustrezni']=='1') ? 1 : 0;
		$mailsent = (isset($_GET['mailsent']) && $_GET['mailsent']=='1') ? 1 : 0;
	
		// Vedno filtriramo preview vnose
		$filter = " srv_user.preview='0'";		
		
		// Filter testnih in avtomatsko generiranih testnih
		$filter .= " AND (srv_user.testdata='0'";
		if($testdata == 1)
			$filter .= " OR srv_user.testdata='1'";
		if($testdataauto == 1)
			$filter .= " OR srv_user.testdata='2'";
		$filter .= ")";
		
		
		$filter .= " AND srv_user.last_status!='-1' AND (";
		if($ustrezni == 1){
			$filter .= " srv_user.last_status='6' OR";
		}
		if($delnoustrezni == 1){
			$filter .= " srv_user.last_status='5' OR";
		}
		if($neustrezni == 1){
			$filter .= " srv_user.last_status='3' OR srv_user.last_status='4' OR";
		}
		if($mailsent == 1){
			$filter .= " srv_user.last_status='0' OR srv_user.last_status='1' OR srv_user.last_status='2' OR";
		}
		if(substr($filter, -2) == 'OR'){
			$filter = substr($filter, 0, -2);
			$filter .= ")";
		}
		else
			$filter .= "srv_user.last_status NOT IN ('0','1','2','3','4','5','6'))";
			
		// Filtriramo uvozene podatke
		if($uvoz == 0){
			$filter .= " AND !(srv_user.referer='' AND testdata='0' AND ip='' AND recnum='0')";
        }
    
		return $filter;
    }
    
    // Filter po paketih, ce jih imamo
	private function diagnostics_get_user_package(){

        $filter = '';

        if(AppSettings::getInstance()->getSetting('app_settings-commercial_packages') === true){

            $package_1ka = (isset($_GET['package_1ka']) && $_GET['package_1ka']=='0') ? 0 : 1;
            $package_2ka = (isset($_GET['package_2ka']) && $_GET['package_2ka']=='0') ? 0 : 1;
            $package_3ka = (isset($_GET['package_3ka']) && $_GET['package_3ka']=='0') ? 0 : 1;

            if($package_1ka == 0){
                $filter .= " ((user_access.package_id='2' OR user_access.package_id='3') AND user_access.time_expire > NOW()) AND ";
            }
            if($package_2ka == 0){
                $filter .= " (user_access.package_id!='2' OR user_access.time_expire < NOW() OR user_access.package_id IS NULL) AND ";
            }
            if($package_3ka == 0){
                $filter .= " (user_access.package_id!='3' OR user_access.time_expire < NOW() OR user_access.package_id IS NULL) AND ";
            }
        }

        return $filter;
    }		
	
	// Vrnemo filter za srv_user (filtriranje testnih podatkov, preview, email vabil, ustreznih...)
	private function diagnostics_get_lang_filter(){
		
		$language = (isset($_GET['language'])) ? $_GET['language'] : 0;
				
		// Filtriramo po jeziku urednika
		$filter = '';
		if($language > 0){
			$filter .= " srv_anketa.lang_admin='".$language."' AND ";
		}

		return $filter;
	}

}