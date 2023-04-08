<?php
/**
 * Analize urejanja
 * 
 * Author: Uroš Podkrižnik
 * Created: 14.4.2017
 */

define('GRAPH_REDUCE', '1.22'); # količnik za koliko zmanjšamo širino grafa da ne prebije 

class SurveyEditsAnalysis{

    private $anketa;    # id ankete
    private $db_table;  # katere tabele uporabljamo


    function __construct($anketa){

        if ((int)$anketa > 0){

            $this->anketa = $anketa;

            # polovimo vrsto tabel (aktivne / neaktivne)
            SurveyInfo :: getInstance()->SurveyInit($this->anketa);
            $this->db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
        } 
        else {	
            
            echo 'Invalid Survey ID!';
            exit();
        }
    }


    function displayTable(){
            global $lang;

            // Legenda statusov
            $statuses = array(
                -1 => $lang['srv_vsi'],
                0 => $lang['srv_urejanje'],
                1 => $lang['import_data'],
                2 => $lang['export_analisys'],
                3 => $lang['srv_reporti'],
                4 => $lang['srv_podatki'],
                5 => $lang['srv_inv_nav_email'],
                20 => $lang['srv_hierarchy'], // Splošni podatki o hierarhiji
                21 => $lang['srv_hierarchy_structure'], // Grajenje hierarhije
                22 => $lang['srv_hierarchy_users'], // Urejanje uporabnikov
            );

            //se ponovi v funkciji ajax_drawContinuEditsTable
            if ($_GET['seansa'] > 0)
                    $seansa = $_GET['seansa'];
            else
                    $seansa = '30';
            if (isset ($_GET['time']))
                    $time = $_GET['time'];
            else
                    $time = '1 month';
            if (isset ($_GET['status']))
                    $status = $_GET['status'];
            else
                    $status = 0;
            if (isset ($_GET['from']))
                    $from = $_GET['from'];
            else
                    $from = '';
            if (isset ($_GET['to']))
                    $to = $_GET['to'];
            else
                    $to = '';

            echo '<form id="diagnostics_form" action="index.php" method="get">';

            echo '<input type="hidden" name="a" value="edits_analysis" />';
            echo '<input type="hidden" name="anketa" value="'.$this->anketa.'" />';

            echo ''.$lang['srv_edits_analysis_seansa'].' <select name="seansa" onchange="this.form.submit();">';
            echo '<option value="5"' . ($seansa == '5' ? ' selected' : '') . '>'.$lang['srv_edits_analysis_seansa_5min'].'</option>';
            echo '<option value="10"' . ($seansa == '10' ? ' selected' : '') . '>'.$lang['srv_edits_analysis_seansa_10min'].'</option>';
            echo '<option value="30"' . ($seansa == '30' ? ' selected' : '') . '>'.$lang['srv_edits_analysis_seansa_30min'].'</option>';
            echo '<option value="60"' . ($seansa == '60' ? ' selected' : '') . '>'.$lang['srv_edits_analysis_seansa_1h'].'</option>';
            echo '</select> ';

            echo ''.$lang['status'].' <select name="status" id="edits_analysis_status" onchange="this.form.submit();">';
            echo '<option value="-1"' . ($status == -1 ? ' selected' : '') . '>'.$statuses[-1].'</option>';
            echo '<option value="0"' . ($status == 0 ? ' selected' : '') . '>'.$statuses[0].'</option>';
            echo '<option value="1"' . ($status == 1 ? ' selected' : '') . '>'.$statuses[1].'</option>';
            echo '<option value="2"' . ($status == 2 ? ' selected' : '') . '>'.$statuses[2].'</option>';
            echo '<option value="3"' . ($status == 3 ? ' selected' : '') . '>'.$statuses[3].'</option>';
            echo '<option value="4"' . ($status == 4 ? ' selected' : '') . '>'.$statuses[4].'</option>';
            echo '<option value="5"' . ($status == 5 ? ' selected' : '') . '>'.$statuses[5].'</option>';
            /*echo '<option value="20"' . ($status == 20 ? ' selected' : '') . '>'.$statuses[20].'</option>';
            echo '<option value="21"' . ($status == 21 ? ' selected' : '') . '>'.$statuses[21].'</option>';
            echo '<option value="22"' . ($status == 22 ? ' selected' : '') . '>'.$statuses[22].'</option>';*/
            echo '</select> '.$lang['srv_diagnostics_in'].' ';

            echo '<select id="diagnostics_date_selected" name="time" onchange="diagnosticsChooseDate();">';
            /*echo '<option value="10 minute"' . ($time == '10 minute' ? ' selected' : '') . '>'.$lang['srv_diagnostics_10 minute'].'</option>';
            echo '<option value="30 minute"' . ($time == '30 minute' ? ' selected' : '') . '>'.$lang['srv_diagnostics_30 minute'].'</option>';*/
            echo '<option value="lifetime"' . ($time == 'lifetime' ? ' selected' : '') . '>'.$lang['srv_edits_analysis_period_lifetime'].'</option>';
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

            echo '</form><div>';	

            //create iterval - SQL where statement
            $interval = $this->createInterval($time, $from, $to);
            //get object of all edits data
            $data = $this->getData($status, $interval);

            if(sizeof($data) == 0)
                $this->echoNoData();
            else{
                echo '<div id="edits_analysis_time_tables">';
                echo '<div>';
                $sum_data = $this->drawTimeEdits($data['timeEdits'], $seansa*60, $status);
                echo '</div>';
                $this->GraphData($this->graphQuery($status, $interval));
                echo '</div>';
                
                echo '<table class="dashboard" id="edits_analysis_info">';
                echo '<tr>';

                //draw edits counter and sums of editors
                $this->drawCountEdits($sum_data);
                
                //draw continuous editing box
                $continu_data = $this->continuEditsQuery($status, $interval);
                $this->drawContinuEdits($continu_data, $sum_data);
                
                echo '</tr>';
                echo '</table>';
                
                //prestavi vse tabele na konec, da bo info na vrhu
                echo'<script>var myElement = document.getElementById("edits_analysis_time_tables");'
                . 'myElement.parentNode.appendChild(myElement);</script>';
                
                
            }
            echo '</div>';
    }
    
    function GraphData($data){
        global $lang, $site_url;
     
       //error_log(json_encode($data));
        //$DataSetJson = json_decode('{"Data":[{"Vrednosti":5,"Vrednosti2":2,"Name":0,"Variable":"0-1000"},{"Vrednosti":20,"Vrednosti2":15,"Name":1,"Variable":"1001-5000"},{"Vrednosti":20,"Vrednosti2":20,"Name":2,"Variable":"5001-10000"},{"Vrednosti":0,"Vrednosti2":8,"Name":3,"Variable":"10001-50000"}],'
        //        . '"DataDescription":{"Position":"Variable","Format":{"X":"number","Y":"number"},"Unit":{"X":null,"Y":null},"Values":["Vrednosti", "Vrednosti2"],"Description":{"Vrednosti":"prvi-prvi-prvi-prvi-prvi", "Vrednosti2":"drugi-drugi-drugi-drugi-drugi"}},"numerus":45,"average":4721,"Other":[]}');

        //polnimo podatke
        $DataSet = new pData;

        $vrednosti = array();
        $vrednostiVariable = array();
        $avg_count = 0;
        $avg_sum = 0;

        /*foreach ($DataSetJson->Data as $value){
            $vrednosti[] = $value->Vrednosti;
            $vrednosti2[] = $value->Vrednosti2;
            $avg_sum+=$value->Vrednosti;
            $vrednostiVariable[] = $value->Variable;
            $avg_count++;
        }*/

        $start_date = new DateTime($data['first_date']);
        $temp_date = $start_date;
        $end_date = new DateTime($data['last_date']);
        
        $diff = $this->differDateTimeInDays($start_date, $end_date);
        $DayFormat = '';
        $DayIncrease = 'month';
        if($diff < 32){
            $DayFormat = '-d';
            $DayIncrease = 'day';
        }
        else
            $temp_date->setDate($start_date->format('Y'), $start_date->format('m'), 1);

        while ($temp_date <= $end_date){
            foreach($data['edits'] as $user=>$value){
                $datestring = $temp_date->format('Y-m'.$DayFormat);
                $cnt = isset($value[$datestring]) ? $value[$datestring] : 0;
                $vrednosti[$user][] = $cnt;

                $avg_sum+=$cnt;
            }

            $vrednostiVariable[] = $datestring;
            $avg_count++;
            $temp_date->modify('+1 '.$DayIncrease);
        }
        
        foreach($data['edits'] as $user=>$value){
            $DataSet->AddPoint($vrednosti[$user],$user);
            $DataSet->AddSerie($user);
            $DataSet->SetSerieName($user,$user);
        }
        
        // nastavimo NUMERUS, ki se izpise pod legendo
        $numerus = $avg_sum;
        $DataSet->SetNumerus($numerus);
        
        // nastavimo POVPRECJE		
        $avg = ($avg_count > 0) ? $avg_sum / $avg_count : 0;
        $DataSet->SetAverage(round($avg, 1));


        $DataSet->AddPoint($vrednostiVariable,"Variable");

        $DataSet->SetAbsciseLabelSerie("Variable");
        //$DataSet->SetYAxisUnit("null");
        $DataSet->SetYAxisFormat("number");

        $Cache = new pCache(dirname(__FILE__).'/../../pChart/Cache/');
        $ID = self::generateChartId($DataSet->GetNumerus());	
        
        echo '<div style="margin-top:-2.5em;">';
        echo '<div class="chart_holder" style="margin: auto !important; width: auto !important;" id="chart_edits_'.$this->anketa.'">';			
        echo '<div class="chart_title" style="width: auto !important">'. $lang['srv_edits_analysis_graph'];
        echo '<span class="numerus">';
        echo '(n = '.$DataSet->GetNumerus().')';
        echo '</span>';
        echo '</div>';

        if(!$Cache->isInCache($ID, $DataSet->GetData())){
            $graph = SurveyChart::createLine($DataSet, null, 1);
            $Cache->WriteToCache($ID,$DataSet->GetData(),$graph);  
        }

        // dobimo ime slike c cache-u
        $imgName = $Cache->GetHash($ID,$DataSet->GetData());
        $imgPath = 'pChart/Cache/'.$imgName;

        $imgUrl = $site_url . 'admin/survey/' . $imgPath;

        echo '<div class="chart_img" style="float: none !important" title="'.$lang['srv_edits_analysis_graph'].'">';	
        // dodamo timestamp ker browser shrani sliko v cache in jo v dolocenih primerih ajaxa ne refresha
        echo '<img src="'.$imgUrl.'?'.time().'" />';		
        echo '</div></div></div>';
    }
      
    /**
     * Run querry for action times
     * @param type $status - int of status
     * @param type $interval - where clause for time interval
     * @return type array of results from DB
     */
    function graphQuery($status, $interval){
        $sql2 = "SELECT min(DATE_FORMAT(st.datetime, '%Y-%m-%d')) as first_date, max(DATE_FORMAT(st.datetime, '%Y-%m-%d')) as last_date"
                . " FROM srv_tracking$this->db_table st, users u WHERE st.ank_id='$this->anketa' AND u.id = st.user ". 
                ($status != -1 ? "AND st.status=$status " : "") ."$interval ";
        $output2=sisplet_query($sql2, 'obj');
        
        $diff = $this->differDateTimeInDays(new DateTime($output2->first_date), new DateTime($output2->last_date));
        $queryDayFormat = '';
        $queryDayGroup = '';
        if($diff < 32){
            $queryDayFormat = '-%d';
            $queryDayGroup = ', DAY(st.datetime)';
        }
        
        $sql = "SELECT count(*) as cnt, u.email, u.id, DATE_FORMAT(st.datetime, '%Y-%m$queryDayFormat') as date"
                . " FROM srv_tracking$this->db_table st, users u WHERE st.ank_id='$this->anketa' AND u.id = st.user ". 
                ($status != -1 ? "AND st.status=$status " : "") ."$interval "
                . "GROUP BY u.email, YEAR(st.datetime), MONTH(st.datetime)$queryDayGroup "
                . "ORDER BY u.email, date ASC";
        $output=sisplet_query($sql, 'array');

        $data = array('edits'=>array(), 'first_date'=>$output2->first_date, 'last_date'=>$output2->last_date);
        foreach($output as $row){
            $data['edits'][$row['email']][$row['date']]=$row['cnt'];
        }
        return $data;                       
    }
    
    private function differDateTimeInDays($start_date, $end_date){
        return $end_date->diff($start_date)->format("%a");
    }
    
    // Zgeneriramo ID grafa za hash
    private function generateChartId($numerus){
            $ID = $this->anketa.'_chart_'.$numerus.'edits_analysis';
            return $ID;
    }

    /**
     * Get data to show in table
     * 
     * @param type $status - status or type of edits
     * @param type $interval - where statement including interval for SQL
     * @return type
     */
    function getData($status, $interval){
            $data = array();

            $data_temp = $this->timeEditsQuery($status, $interval);
                    
            if(sizeof($data_temp) == 0)
                    return array();
            else
                $data['timeEdits'] = $data_temp;

            return $data;
    }
    
     /**
     * Create interval for SQL query from criteria
     * 
     * @param type $time - time selected from dropdown
     * @param type $from - from calendat
     * @param type $to - to calendar
     * @return type - string WHERE statement
     */
    function createInterval($time, $from, $to){
            if($time == 'lifetime' || ($time == '99date' && $from == '' && $to == ''))
                $interval = "";
            else if ($from == '' && $to == '')
                $interval = "AND st.datetime > NOW() - INTERVAL $time";
            else if ($to == '')
                $interval = "AND '$from' <= st.datetime";
            else if ($from == '')
                $interval = "AND st.datetime <= '$to'";
            else
                $interval = "AND '$from' <= st.datetime AND st.datetime <= '$to'";

            return $interval;
    }
    
    /**
     * Run querry for action times
     * @param type $status - int of status
     * @param type $interval - where clause for time interval
     * @return type array of results from DB
     */
    function timeEditsQuery($status, $interval){
        $sql = "SELECT u.email, u.id, st.datetime". ($status == 0 ? ", st.get, st.post" : "") . ($status == -1 ? ", st.status" : "")
                . " FROM srv_tracking$this->db_table st, users u WHERE st.ank_id='$this->anketa' AND u.id = st.user ". 
                ($status != -1 ? "AND st.status=$status " : "") ."$interval ORDER BY u.email, st.datetime DESC";
        return sisplet_query($sql, 'array');                       
    }
    
    /**
     * Draw box of number of edit actions
     * @global type $lang
     * @param type $data - object data of sums
     */
    function drawCountEdits($data){
        global $lang;        
        
        echo '<td>';
        $sum_akcij = 0;
        $sum_time = 0;
        $sum_seans = 0;

        echo '<div class="dashboard_cell" name="div_edits_analysis_counter" id="div_edits_analysis_counter" >'."\n";
        echo '<span class="floatLeft dashboard_title">'.$lang['srv_edits_analysis_counter'];
        echo '</span>';

        echo '<br class="clr"/><br/>';

        echo '<span class="dashboard_status_span">' . $lang['srv_edits_analysis_counter_editors'] .' :</span>' . sizeof($data).'<br/><br/>';
        echo '<table id="tbl_answ_state">';
        echo '<tr class="anl_dash_bb "><th><strong>'.$lang['srv_edits_analysis_counter_editor'].'</strong></th>'
                . '<td><strong>'.$lang['srv_edits_analysis_time_time'].'</strong></td>'
                . '<td><strong>'.$lang['srv_edits_analysis_num_sessions'].'</strong></td>'
                . '<td><strong>'.$lang['srv_edits_analysis_time_actions'].'</strong></td></tr>';
        
        foreach ($data as $key => $value) {
            $this->echoCountEditsRow($key, $this->calculateTimeFromSeconds($value['time_sum']), $value['st_akcij_sum'], $value['st_seans_sum'], $value['user_id']);
            $sum_akcij += $value['st_akcij_sum'];
            $sum_time += $value['time_sum'];
            $sum_seans += $value['st_seans_sum'];
        }

        // vsota vlejavnih
        $this->echoCounterEditsFootRow($sum_time, $sum_akcij, $sum_seans);
        echo '</table>';    
        echo '</div>';
        echo '</td>';
    }
    
    /**
     * Run querry for continuous editing
     * @param type $status - int of status
     * @param type $interval - where clause for time interval
     * @param type $interval_criteria - criteria for interval - continued 'day' or 'hour'
     * @return type array of results from DB
     */
    function continuEditsQuery($status, $interval, $interval_criteria = 'day', $user_criteria = 'all'){           
        $interval_criteria = ($interval_criteria == 'day') ? '' : ' %H';
        
        $sqlString = "SELECT DATE_FORMAT(st.datetime, '%Y-%m-%d$interval_criteria') AS formatdate, count(*) as cnt FROM srv_tracking$this->db_table st WHERE ank_id = '$this->anketa' ".
                ($status != -1 ? "AND st.status=$status " : "")."".
                ($user_criteria != 'all' ? "AND st.user=$user_criteria " : "")."$interval GROUP BY formatdate ORDER BY formatdate desc";
       
        return sisplet_query($sqlString, 'array');                       
    }
    
    /**
     * Draw box of continuous editing
     * @global type $lang
     * @param type $data - object data of continued editing
     * @param type $sum_data - object data of sums
     * @param type $interval_criteria - criteria for interval - continued 'day' or 'hour'
     */
    function drawContinuEdits($data, $sum_data, $interval_criteria = 'day'){  
        global $lang;        
        
        echo '<td>';
        echo '<div class="dashboard_cell" name="div_edits_analysis_countinu" id="div_edits_analysis_countinu" >'."\n";
        echo '<span class="floatLeft dashboard_title">'.$lang['srv_edits_analysis_countinu'];
        echo '</span>';

        echo '<br class="clr"/><br/>';
        
        //user/s
        echo '<span id="span_timelineDropDownType">';
        echo $lang['srv_edits_analysis_counter_editor'].': ';
        echo '<select id="edits_analysis_continu_user" name="edits_analysis_continu_user" onchange="editsAnalysisContinuousEditing();">';
        echo '<option value="all" selected>'.$lang['srv_edits_analysis_counter_all'].'</option>';
        foreach ($sum_data as $email => $row) {
            echo '<option value="'.$row['user_id'].'">'.$email.'</option>';
        }
        echo '</select> ';
        echo '</span>';
                
        // Oblika
        echo '<span>';
        echo '<label>'.$lang['srv_statistic_period'].'</label>:'."\n";
        echo '<select id="edits_analysis_continu_period" name="edits_analysis_continu_period" size="1" autocomplete="off" onchange="editsAnalysisContinuousEditing();">'."\n";
        echo '<option value="hour" '.(($interval_criteria == "hour") ? "selected" : "").'>'.$lang['srv_statistic_period_hour_period'].'</option>';
        echo '<option value="day" '.(($interval_criteria == "day") ? "selected" : "").'>'.$lang['srv_statistic_period_day_period'].'</option>';
        echo '</select>'."\n";
        echo '</span>';

        //data table
        echo '<div name="edits_analysis_continu_table" id="edits_analysis_continu_table" >'."\n";
        //draw table
        $this->drawContinuEditsTable($data, $interval_criteria);
        echo '</div>';
    }
    
    /**
     * Draw table with bars od continued editing
     * @param type $data - object data of continued editing
     * @param type $interval_criteria - criteria for interval - continued 'day' or 'hour'
     */
    function drawContinuEditsTable($data, $interval_criteria = 'day'){
        $maxValue = 0;
        
        $interval_seconds = ($interval_criteria == 'day') ? 86400 : 3600;
        $interval_crit = ($interval_criteria == 'day') ? '' : ' H';
        
        echo '<table class="survey_referals_tbl">'."\n";
        if ($data) {
            $temp_time = null;
            //units
            $zapored = 0;
            $results = array();
                    
            foreach ($data as $row) {
                if($temp_time == null)
                    $temp_time = DateTime::createFromFormat('Y-m-d'.$interval_crit, $row['formatdate']);
                else{
                    //calculate seconds between actions (rounded on 3600 or 86400)
                    $interval = $this->calculateTimeBetweenActions($temp_time, DateTime::createFromFormat('Y-m-d'.$interval_crit, $row['formatdate']));
                    
                    //if interval between actions are 1 unit (1 hour or 1 day), add it to continued editing session
                    if($interval/$interval_seconds-$zapored < 2){
                        $zapored++;
                        //set maxValue, needed for width of bars
                        $maxValue = max($maxValue, $zapored);
                    }
                    //interval is more than 1 unit apart, not in continued editing session
                    else{
                        //if there is continued editing session until previous action, store it to array - ignore otherwise
                        if($zapored > 0)
                            array_push($results, array('time' => $temp_time, 'zapored' => $zapored));
                            
                        //restart all
                        $temp_time = DateTime::createFromFormat('Y-m-d'.$interval_crit, $row['formatdate']);
                        $zapored = 0;
                    }
                }
            }
            //if there is continued editing session in last actions, store it to array - ignore otherwise
            if($zapored > 0)
                //$this->drawContinuRow($temp_time, $zapored, $maxValue, $value);
                array_push($results, array('time' => $temp_time, 'zapored' => $zapored));
            
            if(!$results)
                $this->echoNoData();
            else{
                //reduce bars a little
                $maxValue *= GRAPH_REDUCE;//najvecje stevilo
                //draw all data and bars
                foreach ($results as $row) {
                    $this->drawContinuRow($row['time'], $row['zapored'], $maxValue, $interval_criteria);
                }
            }
        } else 
            $this->echoNoData();		

        echo '</table>'."\n";
    }
    
    /**
     * Draws a row with bar of continuous editing
     * @param type $temp_time - the last edit
     * @param type $zapored - hour of continuoed editing
     * @param type $maxValue - max value of bars
     * @param type $interval_criteria - criteria for interval - continued 'day' or 'hour'
     */
    function drawContinuRow($temp_time, $zapored, $maxValue, $interval_criteria){               
        $time_last = clone $temp_time;
        //edit DateTime get starting of continued editting session by subtracting units
        $temp_time->modify('- '.$zapored.' '.$interval_criteria);

        //if hour criteria
        if($interval_criteria == 'hour'){
            //add 1 hour because of from to view
            $time_last->modify('+ 1 '.$interval_criteria);
            $s_time = $temp_time->format('Y-m-d H:00') .' - '. $time_last->format('H:00');
        }
        else if($interval_criteria == 'day')
            $s_time = $temp_time->format('Y-m-d') .' - '. $time_last->format('Y-m-d');

        //echo data
        echo '<tr>'."\n";
        echo '<td style="width:90px;">' . $s_time . '</td>'."\n";
        $width = ($maxValue && $zapored) ? (round($zapored / $maxValue * 100, 0)) : "0";
        echo '<td style=""><div class="graph_db" style="text-align:right; float:left; width:'.$width.'%">&nbsp;</div><span style="display:block; margin:auto; margin-left:5px; width:20px; float:left">'.($zapored+1).'</span></td>'."\n";
        echo '</tr>'."\n";
    }
    
    /**
     * Draw box of editing times
     * @param type $data - $data['timeEdits']
     * @param type $seansa - cas nastavljene dolzine seanse v min
     * @param type $status - code of status criteria
     * @return Object - object of editors e.g. {admin:{time_sum:500, st_akcij_sum:30, st_seans_sum:5}, ...}
     */
    function drawTimeEdits($data, $seansa, $status){
        global $lang;
                
        $sum_data = array();
        
        $datetime_last = null;
        $datetime_start = null;
        $st_akcij = 0;
        $st_akcij_sum = 0;
        $st_seans_sum = 0;
        $time_sum = 0;
        $user_temp = null;
        $user_id = 0;
        $row_id = 0;
        $action_type = null;
        $action_type_sum = null;
        $statuses = null;
        
        if($status == -1){
            $statuses = array(
                0 => array("name"=>$lang['srv_urejanje'], "sum"=>0),
                1 => array("name"=>$lang['import_data'], "sum"=>0),
                2 => array("name"=>$lang['export_analisys'], "sum"=>0),
                3 => array("name"=>$lang['srv_reporti'], "sum"=>0),
                4 => array("name"=>$lang['srv_podatki'], "sum"=>0),
                5 => array("name"=>$lang['srv_inv_nav_email'], "sum"=>0),
                //20 => array("name"=>$lang['srv_hierarchy'],  "sum"=>0),// Splošni podatki o hierarhiji
                //21 => array("name"=>$lang['srv_hierarchy_structure'],  "sum"=>0),// Grajenje hierarhije
                //22 => array("name"=>$lang['srv_hierarchy_users'],  "sum"=>0),// Urejanje uporabnikov
            );
            $action_type = $statuses;
            $action_type_sum = $statuses;
        }
        else if($status == 0){
            $statuses = array();
            $action_type = array();
            $action_type_sum = array();
        }
        
        echo '<h2>'.$lang["srv_edits_analysis_editing_details"].'</h2>';
        
        foreach ($data as $rowGrupa) {
            
            //$post = $this->convertToJSON($rowGrupa['post']);
            $akcija = null;
            if($status == -1)
                $akcija = $rowGrupa['status'];
            else if($status == 0){
                $get = $this->convertToJSON($rowGrupa['get']);
                $akcija = $get['a'];
            }
            
            //zacetek risanja
            if(!isset($user_temp)){
                $user_temp = $rowGrupa['email'];
                $user_id = $rowGrupa['id'];
                echo '<table class="text_analysis_table floatLeft" border="1">'; //border zaradi printa - css na strani ga povozi
                $this->echoTimeTalbeHeader($user_temp, $status, $user_id);
            }
            
            //naslednji editor
            else if($user_temp != $rowGrupa['email']){
                //izrisi se zadnjo vrstico prejsnjega urejevalca
                $time_sum += $this -> drawTimeEditsRow($datetime_start, $datetime_last, $st_akcij, $action_type, $user_id.'_'.$row_id);
                $this -> echoTimeEditsFootRow($time_sum, $st_akcij_sum, $action_type_sum, $user_id.'_sum');
                $sum_data[$user_temp]['time_sum']=$time_sum;
                $sum_data[$user_temp]['st_akcij_sum']=$st_akcij_sum;
                $sum_data[$user_temp]['st_seans_sum']=$st_seans_sum;
                $sum_data[$user_temp]['user_id']=$user_id;
                $action_type_sum = $statuses;
           
                //nova tabela - nov urejevalec
                $user_temp = $rowGrupa['email'];
                $user_id = $rowGrupa['id'];
                $this->echoTimeTalbeHeader($user_temp, $status, $user_id);
                
                //ponastavi spremenljivke
                $datetime_last = null;
                $datetime_start = null;
                $st_akcij = 0;
                $st_akcij_sum = 0;
                $st_seans_sum = 0;
                $time_sum = 0;
            }    
            
            //izpis vrstic
            //nov start seanse
            if(!isset($datetime_start)){
                $datetime_start = new DateTime($rowGrupa['datetime']);
                $st_akcij++;
                $st_seans_sum++;
                $action_type = $statuses;
            }
            //se ni druge akcije
            else if(!isset($datetime_last)){
                $temp_time = new DateTime($rowGrupa['datetime']);
                $interval = $this->calculateTimeBetweenActions($datetime_start, $temp_time);

                //ce je akcija od starta v kriteriju seanse, jo dodaj k seansi
                if($interval <= $seansa){
                    $datetime_last = clone $temp_time;
                    $st_akcij++;
                }
                //akcija je izven kriterija seanse, izpisi samo to akcijo
                else{
                    $datetime_last = clone $datetime_start;
                    $datetime_last->add(new DateInterval('PT5S'));
                    $time_sum += $this -> drawTimeEditsRow($datetime_start, $datetime_last, $st_akcij, $action_type, $user_id.'_'.$row_id);
                    $st_akcij = 1;
                    $st_seans_sum++;
                    $datetime_start = clone $temp_time;
                    $datetime_last = null;
                    $action_type = $statuses;
                }
            }
            //seasna ze ima vsaj dve akciji
            else{
                $temp_time = new DateTime($rowGrupa['datetime']);
                $interval = $this->calculateTimeBetweenActions($datetime_last, $temp_time);
                
                //ce je akcija od prejsnje v kriteriju seanse, jo dodaj k seansi
                if($interval <= $seansa){
                    $datetime_last = clone $temp_time;
                    $st_akcij++;
                }
                //akcija je izven kriterija seanse, izpisi vse prejsnje akcije
                else{
                    $time_sum += $this -> drawTimeEditsRow($datetime_start, $datetime_last, $st_akcij, $action_type, $user_id.'_'.$row_id);
                    $st_akcij = 1;
                    $st_seans_sum++;
                    $datetime_start = clone $temp_time;
                    $datetime_last = null;
                    $action_type = $statuses;
                }
            }
            $st_akcij_sum++;
            $row_id++;
            if($status == -1){
                $action_type[$akcija]['sum'] ++;
                $action_type_sum[$akcija]['sum'] ++;
            }
            else if($status == 0){
                $action_type[$akcija] = isset($action_type[$akcija]) ? $action_type[$akcija]+1 : 1;
                $action_type_sum[$akcija] = isset($action_type_sum[$akcija]) ? $action_type_sum[$akcija]+1 : 1;
            }
        }
        
        //izrisi se zadnjo vrstico, ki jo ni foreach ter footer
        if($datetime_last == null){
            $datetime_last = clone $datetime_start;
            $datetime_last->add(new DateInterval('PT5S'));
        }
        $time_sum += $this -> drawTimeEditsRow($datetime_start, $datetime_last, $st_akcij, $action_type, $user_id.'_'.$row_id);
        $this -> echoTimeEditsFootRow($time_sum, $st_akcij_sum, $action_type_sum, $user_id.'_sum');
        $sum_data[$user_temp]['time_sum']=$time_sum;
        $sum_data[$user_temp]['st_akcij_sum']=$st_akcij_sum;
        $sum_data[$user_temp]['st_seans_sum']=$st_seans_sum;
        $sum_data[$user_temp]['user_id']=$user_id;
        
        echo '</table>';
        
        return $sum_data;
    }
    
    /**
     * Izrisi header tabele za cas urejanja vsakega urejevalca
     * @param type $user_temp - email of user
     * @param type $status - status from criteria
     * @param type $user_num - int sequence nuber of user (unique, for this site, no need to be ID)
     */
    function echoTimeTalbeHeader($user_temp, $status, $user_num){
        global $lang;
        
        echo '<tr id="edits_analysis_user_'.$user_num.'"><th colspan="100"><strong>'.$user_temp.'</strong></th></tr>';
        echo '<tr><th>'.$lang['srv_edits_analysis_time_span'].'</th><th>'.$lang['srv_edits_analysis_time_time'].
                '</th><th>'.$lang['srv_edits_analysis_time_actions'].'</th>'.($status < 1 ? '<th>'.$lang['srv_edits_analysis_action_type'].'</th>' : '').'</tr>';
    }
    
    /**
     * Nastavi in kasneje izrise vrstico urejanja
     * 
     * @param type $datetime_start - datetime start of editing
     * @param type $datetime_last - datetime end of editing
     * @param type $st_akcij - num ob actions during editing
     * @param type $action_type - string of type of action
     * @param type $row_id - int sequence nuber of row (unique, for this site, no need to be ID)
     * @return type int - calculated second of editing session
     */
    function drawTimeEditsRow($datetime_start, $datetime_last, $st_akcij, $action_type = null, $row_id = null){
        $seconds = 0;
        
        //create string of actions type
        $action_type_string = ($action_type != null) ? $this -> createActionsTypeString($action_type, $row_id) : null;
               
        if(isset($datetime_last)){
            $seconds = $this->calculateTimeBetweenActions($datetime_start, $datetime_last);
            $this -> echoTimeEditsRow($datetime_last->format('Y-m-d H:i:s') .' - '. $datetime_start->format('Y-m-d H:i:s'), 
                     $this->calculateTimeFromSeconds($seconds), $st_akcij, $action_type_string);
        }
        //ce je samo ena akcija
        else
            $this -> echoTimeEditsRow($datetime_start->format('Y-m-d H:i:s'), 0 ,1, $action_type_string);
        
        return $seconds;
    }
    
    /**
     * Create/convert array of action types to string for table cell
     * @param type $action_type - array of action types
     * @param type $row_id - int sequence nuber of row (unique user int and row in table)
     * @return string - converter array to string to put it in table cell
     */
    function createActionsTypeString($action_type, $row_id){
        $action_type_string = '';
        //urejanje - ali drug specificen status
        if(!isset($action_type[0]['sum'])){
            global $lang;
            $i = 0;
            foreach ($action_type as $key => $at){
                if($i == 3)
                    $action_type_string .= '<div class="srv_edits_analysis_'.$row_id.' as_link" onclick="$(\'.srv_edits_analysis_'.$row_id.'\').toggle();">'.$lang['srv_more'].'</div>';	
                if($i < 3)
                    $action_type_string .= '<div>'.$key.' ('.$at.')'.'</div>';
                else
                    $action_type_string .= '<div class="srv_edits_analysis_'.$row_id.' displayNone">'.$key.' ('.$at.')'.'</div>';
                $i++;
            }
            if($i > 3)
                $action_type_string .= '<div class="srv_edits_analysis_'.$row_id.' as_link displayNone" onclick="$(\'.srv_edits_analysis_'.$row_id.'\').toggle();">'.$lang['srv_less'].'</div>';	
        }
        //vsi statusi
        else{
            foreach ($action_type as $at){
                if($at['sum'] > 0){
                    if($action_type_string != '')
                        $action_type_string .= '</br>';
                    $action_type_string .= $at['name'].' ('.$at['sum'].')';
                }
            }
        }
        return $action_type_string;
    }
    
    /**
     * Izrise vrstico urejanja
     * @param type $datetime - string from to editing
     * @param type $cas_seanse - editing time
     * @param type $st_akcij - num of editing actions
     * @param type $action_type - string of type of action
     */
    function echoTimeEditsRow($datetime, $cas_seanse, $st_akcij, $action_type = null){
        //casovni razpon urejanja
        echo '<tr><td>'.$datetime.'</td>';
        //cas urejanja
        echo '<td>'.$cas_seanse.'</td>';
        //stevilo akcij
        echo '<td>'.$st_akcij.'</td>';
        if($action_type != null)
            //vrsta akcij
            echo '<td>'.$action_type.'</td>';
        echo '</tr>';
    }
    
     /**
     * Izrise vrstico editor info
     * @param type $user - string of editor
     * @param type $time_sum - editing time
     * @param type $st_akcij - num of sum editing actions
     * @param type $st_seans_sum - num of sessions
     * @param type $user_num - int sequence nuber of user (unique user int and row in table)
     */
    function echoCountEditsRow($user, $time_sum, $st_akcij, $st_seans_sum, $user_num){
        //casovni razpon urejanja
        echo '<tr><th><a href="#edits_analysis_user_'.$user_num.'">'.$user.'</a></th>';
        //cas urejanja
        echo '<td>'.$time_sum.'</td>';
        //stevilo seans
        echo '<td>'.$st_seans_sum.'</td>';
        //stevilo akcij
        echo '<td>'.$st_akcij.'</td>';
        echo '</tr>';
    }
    
    /**
     * Izrise total/footer vrstico urejanja
     * @param type $time - seconds of editing
     * @param type $st_akcij - num of editing actions
     * @param type $action_type - string of type of actions
     * @param type $row_id - int sequence nuber of user (unique, for this site, no need to be ID)
     */
    function echoTimeEditsFootRow($time, $st_akcij, $action_type = null, $row_id = 0){
        global $lang;
        
        //casovni razpon urejanja
        echo '<tr class="colored"><td>'.$lang['srv_edits_analysis_time_total'].'</td>';
        //cas urejanja
        echo '<td>'.$this->calculateTimeFromSeconds($time).'</td>';
        //stevilo akcij
        echo '<td>'.$st_akcij.'</td>';
        if($action_type != null)
            //vrsta akcij
            echo '<td>'.$this->createActionsTypeString($action_type, $row_id).'</td>';
        echo '</tr>';
    }
    
        /**
     * Izrise total/footer vrstico urejanja
     * @param type $time - seconds of editing
     * @param type $st_akcij - num of editing actions
     * @param type $st_seans_sum - num of sessions
     */
    function echoCounterEditsFootRow($time, $st_akcij, $st_seans_sum){
        global $lang;
        
        //casovni razpon urejanja
        echo '<tr class="anl_dash_bt full strong"><th>'.$lang['srv_edits_analysis_time_total'].'</th>';
        //cas urejanja
        echo '<td>'.$this->calculateTimeFromSeconds($time).'</td>';
        //stevilo seans
        echo '<td>'.$st_seans_sum.'</td>';
        //stevilo akcij
        echo '<td>'.$st_akcij.'</td>';
        echo '</tr>';
    }
    
    /**
     * Calculate 
     * @param type $datetime_start - datetime start of editing
     * @param type $datetime_last - datetime end of editing
     * @return type - float in time in minutes between actions
     */
    function calculateTimeBetweenActions($datetime_start, $datetime_last){
        return abs($datetime_last ->getTimestamp() - $datetime_start->getTimestamp());
    }
    
    /**
     * Get readable time from seconds
     * @param type $seconds - time in seconds
     * @return type string - readable time
     */
    function calculateTimeFromSeconds($seconds){
        $hours = floor($seconds / 3600);
        $mins = floor($seconds / 60 % 60);
        $secs = floor($seconds % 60);
        
        return sprintf('%02d:%02d:%02d', $hours, $mins, $secs);
    }
    
    /**
     * Convert false JSON (with keys without quotes and no stat and end braces) 
     * from DB to valid JSON
     * @param type $toJSON string to convert to JSON (with keys without 
     * quotes and no stat and end braces)
     * @return type valid converted JSON
     */
    function convertToJSON($toJSON){
        $toJSON = preg_replace('/("(.*?)"|(\w+))(\s*:\s*(".*?"|.))/s', '"$2$3"$4', $toJSON);
        $toJSON = '{'.$toJSON.'}';
        return json_decode($toJSON, true);
    }
    
    /**
     * Echo 'no data in DB'
     */
    function echoNoData(){
        global $lang;
        echo '<p><b>'.$lang['srv_edits_analysis_no_data'].'</b></p>'."\n";
    }
    
    function ajax_drawContinuEditsTable(){
        if (isset ($_POST['user']))
                $user = $_POST['user'];
        else
                $user = 'all';
        if (isset ($_POST['period']))
                $period = $_POST['period'];
        else
                $period = 'day';
        if (isset($_POST['time']))
                $time = $_POST['time'];
        else
                $time = '1 month';
        if (isset ($_POST['status']))
                $status = $_POST['status'];
        else
                $status = 0;
        if (isset ($_POST['from']))
                $from = $_POST['from'];
        else
                $from = '';
        if (isset ($_POST['to']))
                $to = $_POST['to'];
        else
                $to = '';
        
        //create iterval - SQL where statement
        $interval = $this->createInterval($time, $from, $to);
            
        //get data
        $data = $this->continuEditsQuery($status, $interval, $period, $user);
        
        //draw table
        $this->drawContinuEditsTable($data, $period);
    }
	
    function getList($status, $interval){
        $sql = "SELECT st.datetime, u.email, st.post, st.get FROM srv_tracking$this->db_table st, users u WHERE st.ank_id='$this->anketa' AND u.id = st.user ". ($status != -1 ? "AND st.status=$status " : "") ."$interval";

        // Loop cez vse vrednosti v vprasanjih na straneh v anketi
        $sqlGrupa = sisplet_query($sql);

        $vrstic = 0;
        while($rowGrupa = mysqli_fetch_array($sqlGrupa)){
                $vrstic++;
        }
    }
	
}