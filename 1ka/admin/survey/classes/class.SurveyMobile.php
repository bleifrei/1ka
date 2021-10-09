<?php

/**
 *
 * 	Class ki skrbi za komunikacijo z mobilno aplikacijo
 *
 */
class SurveyMobile {

    private $anketa;  // id ankete

    function __construct($anketa = 0) {
        global $lang;

        if ((int) $anketa > 0) {

            $this->anketa = $anketa;
        }
    }

    // Vrne string zadnje verzije mobilne app
    //!! to je samo nek ostanek, ki se trenutno rabi samo zato, da se pac gre cez login in da po loginu klice neko funkcijo
    public function getMobileVersion() {

        /* $sql = sisplet_query("SELECT value FROM misc WHERE what='mobileApp_version'");
          if(mysqli_num_rows($sql) > 0){
          $row = mysqli_fetch_array($sql);
          return $row['value'];
          }
          else
          return 0; */
        
        return 'ABORTED';
    }

    // Preveri username in pass ce sta ok za login
    public function checkLogin($pass, $uname) {
        global $lang;
        global $pass_salt;

        $login_check = false;

        $sql = sisplet_query("SELECT id, pass, status FROM users WHERE email='" . $uname . "'");
        if (mysqli_num_rows($sql) > 0) {
            $row = mysqli_fetch_array($sql);

            //if($row['pass'] == base64_encode(bin2hex(hash(SHA256, $pass.$pass_salt))))
            if ($row['pass'] == base64_encode(hash('SHA256', $pass . $pass_salt)))
                $login_check = true;
        }

        if ($login_check && $row['id'] > 0 && $row['status'] > 0)
            return $row['id'];
        else if ($login_check && $row['id'] > 0 && $row['status'] == 0)
            return -1;
        else
            return 0;
    }

    // Login preko google account api
    public function googleLogin($user_email) {

        if ($user_email != '') {
            $res = sisplet_query("SELECT id, pass FROM users WHERE email='" . $user_email . "'");

            // Je noter, ga samo prijavim...
            if (mysqli_num_rows($res) > 0) {
                $r = mysqli_fetch_row($res);
                return $r[0];
            }
            // Ni se registriran, ga je potrebno dodati na prijavno formo
            else {
                // geslo med 00000 in zzzzz
                $pass = base_convert(mt_rand(0x19A100, 0x39AA3FF), 10, 36);

                $EncPass = base64_encode((hash('SHA256', $pass)));
                $fn = explode("@", $user_email);

                sisplet_query("INSERT INTO users (name, surname, email, pass, lang) VALUES ('" . $fn[0] . "', '', '" . $user_email . "', '" . $EncPass . "', '" . (isset($_GET['regFromEnglish']) && $_GET['regFromEnglish'] == "1" ? '2' : '1') . "')");
                $uid = mysqli_insert_id($GLOBALS['connect_db']);

                sisplet_query("INSERT INTO oid_users (uid) VALUES ('$uid')");

                return $uid;
            }
        } else
            return 0;
    }
    
    // update vrstnega reda vprasanj
    public function updateQuestionOrder($ank_id, $data) {
        
        if (!isset($data['to_place']) && !isset($data['que_id'])){
            $json_array['error'] = 'Question ID or order number missing';
            return $json_array;
        }
        
        //poizvedi, koliko je vseh spremenljivk
        $sql = sisplet_query("SELECT MAX(vrstni_red) FROM srv_branching WHERE ank_id = '$ank_id'");
        $row = mysqli_fetch_assoc($sql);
        //ali se da spremenljivko na zadnje mesto? ce da, nastavi page_break = 2
        $page_break = $row['MAX(vrstni_red)'] == $data['to_place'] ? 2 : 0;

        //poizvedi, ali se premakne vprasanje navzgor ali navzdol
        //to se rabi, ker ce se prestavi navzgor, je treba odsteti vrstni_red za 1
        //zato, ker se v branchingu lahko razvrsca med vsako spremenljivko, pred prvo ter za zadnjo - to je skupaj vse +1
        $sql = sisplet_query("SELECT vrstni_red FROM srv_branching WHERE element_spr = '" . $data['que_id'] . "'");
        $row = mysqli_fetch_assoc($sql);
        $vrstni_red = $row['vrstni_red'] >= $data['to_place'] ? $data['to_place'] - 1 : $data['to_place'];

        $ba = new BranchingAjax($ank_id);
        $json_array = $ba->ajax_accept_droppable($data['que_id'], $vrstni_red, $page_break, true);

        if (empty($json_array))
            $json_array['note'] = 'Question order updated';

        Common::getInstance()->updateEditStamp();
        Common::prestevilci();

        return $json_array;
    }

    //MAZA
    /**
     * Check login data for MAZA modul app
     * @param boolean $loginAction - true if action is only to check login, false otherwise
     * @param type $identifier - identifier code for maza app user
     * @param type $id_server - id of maza app user on server
     * @return if $loginAction is true, return object with data, otherwise return id of maza app user
     */
    public function checkMazaLogin($loginAction, $identifier, $id_server, $registration_id) {
        global $lang;
        global $pass_salt;

        $login_check = false;
        $id = 0;
        $response;

        if ($identifier == 'no_identifier') {
            
            $maza = new MAZA();
            $newuser = $maza -> insertNewUsers('self');
            
            $login_check = true;
            $id = $newuser['id'];
            
            $response['note'] = "login OK";
            $response['identifier'] = $newuser['identifier'];
            $response['id_server'] = $newuser['id'];
            $response['update'] = 'deviceInfo';
        } 
        elseif($identifier == 'no_login'){
            $response['note'] = "login OK";
        }
        else {
            //$salted_identifier = base64_encode(hash(SHA256, $identifier . $pass_salt));
            $check_id = '';
            $does_id_serever_exist = false;

            if(!$loginAction || ($loginAction && (isset($id_server) && $id_server!=''))){
                    $check_id = "id='".$id_server."' AND ";
                    $does_id_serever_exist = true;
            }

            $sql = sisplet_query("SELECT id, deviceInfo FROM maza_app_users WHERE ".$check_id."identifier='" . $identifier . "'");
            if (mysqli_num_rows($sql) > 0) {
                $row = mysqli_fetch_array($sql);
                $login_check = true;
                $id = $row['id'];
                $deviceInfo = $row['deviceInfo'];
                
                //update datetime_last_active
                $maza = new MAZA();
                $maza -> maza_update_user_active($id, $registration_id);
                
                if(!isset($deviceInfo))
                    $response['update'] = 'deviceInfo';
                if(!$does_id_serever_exist){
                    $response['identifier'] = $identifier;
                    $response['id_server'] = $id;
                }
                
                $response['note'] = "login OK";
            }
            
            //if only login
            if($loginAction){
                //update datetime_started in table maza_user_srv_access to set datetime of user linking to his survey is already existing (generated users)
                sisplet_query("UPDATE maza_user_srv_access SET datetime_started = NOW() WHERE maza_user_id = '$id' AND datetime_started IS NULL;");
            }
        }

        if ($login_check && $id > 0)
            return $loginAction ? $response : $id;
        else
            return 0;
    }
    
    /**
     * Udates device info with sensors
     * @global type $global_user_id
     * @return array
     */
    public function mazaUpdateDeviceInfo($data) {
        global $global_user_id;
        
        $gotData = json_decode($data);
        $sqldi = sisplet_query("SELECT deviceInfo FROM maza_app_users WHERE id = '$global_user_id'", 'obj');
        
        if($sqldi->deviceInfo[0] == '{'){
            $odlInfo = json_decode($sqldi->deviceInfo);

            $lastInfo = end($odlInfo)->device_info;
            $gotInfo = $gotData->device_info;           
            
            if($lastInfo->os_version == $gotInfo->os_version && $lastInfo->release == $gotInfo->release && 
                    $lastInfo->serial == $gotInfo->serial){
                $json_array['note'] = 'Device info already up to date';
            }
            else{
                $temptime = time();
                $odlInfo->$temptime = $gotData;
                $newInfo = $odlInfo;
                sisplet_query("UPDATE maza_app_users SET deviceInfo = '" . json_encode($newInfo) . "' WHERE id = '$global_user_id'");
                $json_array['note'] = 'Device info added';
            }
        }
        else{
            $newInfo = array(time()=>$gotData); 
            sisplet_query("UPDATE maza_app_users SET deviceInfo = '" . json_encode($newInfo) . "' WHERE id = '$global_user_id'");
            $json_array['note'] = 'Device info inserted';
        }
        
        return $json_array;
    }
    
    /**
     * Get all alarms of surveys for this user
     * @global type $global_user_id
     * @return array
     */
    public function mazaGetAlarms() {
        global $global_user_id;
        
        $sql_arr = sisplet_query("SELECT ma.alarm_notif_title, ma.alarm_notif_message, ma.repeat_by AS alarm_repeat_by, "
                . "ma.alarm_notif_sound, ma.time_in_day AS alarm_time_in_day, ma.day_in_week AS alarm_day_in_week, "
                . "ma.every_which_day AS alarm_every_which_day, mr.every_which_day AS repeater_every_which_day, "
                . "mr.time_in_day AS repeater_time_in_day, mr.repeat_by AS repeater_repeat_by, ma.ank_id, an.url, an.naslov, "
                . "mr.day_in_week AS repeater_day_in_week, mr.datetime_start, mr.datetime_end, MAX(sa.srv_version_datetime) AS last_answered_version_datetime, "
                . "CASE WHEN sa.datetime_started < mr.datetime_start THEN mr.datetime_start ELSE sa.datetime_started END AS datetime_user_started FROM maza_srv_alarms AS ma "
                . "LEFT JOIN (SELECT sua.ank_id, sua.maza_user_id, sua.datetime_started, su.srv_version_datetime FROM maza_user_srv_access AS sua "
                    . "LEFT JOIN (SELECT msu.srv_version_datetime, msu.maza_user_id, su.ank_id FROM maza_srv_users AS msu "
                        . "LEFT JOIN (SELECT id, ank_id FROM srv_user) AS su ON su.id=msu.srv_user_id) AS su "
                    . "ON sua.maza_user_id=su.maza_user_id AND su.ank_id=sua.ank_id) AS sa ON ma.ank_id = sa.ank_id "
                . "LEFT JOIN maza_srv_repeaters AS mr ON mr.ank_id = sa.ank_id "
                . "LEFT JOIN (SELECT id, url, naslov FROM srv_anketa) AS an ON an.id = sa.ank_id "
                . "WHERE sa.maza_user_id='$global_user_id' AND mr.repeater_on='1' GROUP BY ma.ank_id", 'array');
        
        $alarms = array();
        foreach ($sql_arr as $row) {
            $last_answered_version_datetime = ($row['last_answered_version_datetime']) ? $row['last_answered_version_datetime'] : null;
            
            $alarm = array('title' => $row['alarm_notif_title'], 'message' => $row['alarm_notif_message'], 'ank_id' => $row['ank_id'], 'sound' => $row['alarm_notif_sound'], 'link' => $row['url'].'a/'.$row['ank_id'], 'srv_title' => $row['naslov'], 
                'repeat' => array("repeat_by" => $row['alarm_repeat_by'], "time_in_day" => json_decode($row['alarm_time_in_day']), "day_in_week" => json_decode($row['alarm_day_in_week']), 
                    "every_which_day" => $row['alarm_every_which_day']),
                'repeater' => array("repeat_by" => $row['repeater_repeat_by'], "time_in_day" => json_decode($row['repeater_time_in_day']), "day_in_week" => json_decode($row['repeater_day_in_week']), 
                    "every_which_day" => $row['repeater_every_which_day'], "datetime_start" => $row['datetime_start'], "datetime_end" => $row['datetime_end'], 
                    "last_answered_version_datetime" => $last_answered_version_datetime, "datetime_user_started" => $row['datetime_user_started']));
            array_push($alarms, $alarm);
        }
        return $alarms;
    }
    
     /**
     * Get all geofences of surveys for this user
     * @global type $global_user_id
     * @return array
     */
    public function mazaGetGeofences() {
        global $global_user_id;
        
        $sql_arr = sisplet_query("SELECT mg.id, mg.ank_id, mg.geofence_on, mg.lat, mg.lng, mg.radius, mg.address, mg.name, mg.notif_title, mg.trigger_survey, "
                . "mg.notif_message, mg.notif_sound, mg.on_transition, mg.after_seconds, mg.location_triggered, an.url, an.naslov FROM maza_srv_geofences AS mg "
                . "LEFT JOIN (SELECT ank_id, maza_user_id FROM maza_user_srv_access) AS sua ON mg.ank_id = sua.ank_id "
                . "LEFT JOIN (SELECT id, url, naslov, active FROM srv_anketa) AS an ON an.id = sua.ank_id "
                . "WHERE sua.maza_user_id='$global_user_id' AND mg.geofence_on='1' AND an.active='1'", 'array');

        //array of all surveys with geofences
        $surveys = array();
        //object of one survey with its geofences
        $survey = array();
        //array of geofences of one survey
        $geofences = array();
        //temporary survey id
        $srv_id_temp = -1;
        
        //loop trough geofences
        foreach ($sql_arr as $row) {
            //if survey id is not equal to previous, create new survey array
            if($srv_id_temp != $row['ank_id']){
                //ingor first time
                if($srv_id_temp > -1){
                    $survey['geofences'] = $geofences;
                    array_push($surveys, $survey);
                }
                
                //set survey array
                $survey = array('ank_id' => $row['ank_id'], 'link' => $row['url'].'a/'.$row['ank_id'], 'srv_title' => $row['naslov']);
                //reset geofences
                $geofences = array();
                //set temporary survey id
                $srv_id_temp = $row['ank_id'];
            }
                
            //set geofence array
            $geofence = array('id' => $row['id'], 'lat' => $row['lat'], 'lng' => $row['lng'], 'radius' => $row['radius'], 
                'address' => $row['address'], 'notif_title' => $row['notif_title'], 'notif_message' => $row['notif_message'], 
                'notif_sound' => $row['notif_sound'], 'on_transition' => $row['on_transition'], 'name' => $row['name'], 
                'after_seconds' => $row['after_seconds'], 'location_triggered' => $row['location_triggered'], 'trigger_survey' => $row['trigger_survey']);
            
            //add geofence to geofences array of one survey
            array_push($geofences, $geofence);
        }
        if(sizeof($sql_arr) > 0){
            //add geofences to last survey
            $survey['geofences'] = $geofences;
            //add last survey to surveys aray
            array_push($surveys, $survey);
        }
        
        return $surveys;
    }
    
    /**
     * Get all activities of surveys for this user
     * @global type $global_user_id
     * @return array
     */
    public function mazaGetActivities() {
        global $global_user_id;
        
        $sql_arr = sisplet_query("SELECT ma.id, ma.ank_id, ma.activity_on, ma.notif_title, ma.notif_message, ma.notif_sound, "
                . "ma.activity_type, ma.after_seconds, ank.naslov, ank.id as ank_id, ank.url, sua.nextpin_tracking_permitted FROM maza_srv_activity AS ma "
                . "LEFT JOIN (SELECT ank_id, maza_user_id, nextpin_tracking_permitted FROM maza_user_srv_access) AS sua ON ma.ank_id = sua.ank_id "
                . "LEFT JOIN (SELECT id, url, naslov FROM srv_anketa) AS ank ON ma.ank_id = ank.id "
                . "WHERE sua.maza_user_id='$global_user_id' AND ma.activity_on='1'", 'array');

        //array of all surveys with activities
        $surveys = array();
        //object of one survey with its activities
        $survey = array();

        //loop trough activities
        foreach ($sql_arr as $row) {            
                //set survey array
                $survey = array('ank_id' => $row['ank_id'], 'link' => $row['url'].'a/'.$row['ank_id'], 'srv_title' => $row['naslov'], 
                    'notif_title' => $row['notif_title'], 'notif_message' => $row['notif_message'], 'notif_sound' => $row['notif_sound'], 
                    'activity' => array('id' => $row['id'], 'activity_type' => $row['activity_type'], 'after_seconds' => $row['after_seconds']), 'permitted'=>$row['nextpin_tracking_permitted']);
            
                array_push($surveys, $survey);
        }
        return $surveys;
    }
    
    /**
     * Get all tracking of surveys for this user
     * @global type $global_user_id
     * @return array
     */
    public function mazaGetTracking() {
        global $global_user_id;
        
        $sql_arr = sisplet_query("SELECT ank.id, ma.ank_id, ma.tracking_on, ma.activity_recognition, ma.tracking_accuracy, ma.interval_wanted, "
                . "ma.interval_fastes, ma.displacement_min, ma.ar_interval_wanted, ank.naslov, ank.url, sua.tracking_permitted FROM maza_srv_tracking AS ma "
                . "LEFT JOIN (SELECT ank_id, maza_user_id, tracking_permitted FROM maza_user_srv_access) AS sua ON ma.ank_id = sua.ank_id "
                . "LEFT JOIN (SELECT id, url, naslov, active FROM srv_anketa) AS ank ON ma.ank_id = ank.id "
                . "WHERE sua.maza_user_id='$global_user_id' AND ma.tracking_on='1' AND ank.active='1'", 'array');        

        //array of all surveys with tracking
        $surveys = array();
        //object of one survey with its tracking
        $survey = array();

        //loop trough trackings
        foreach ($sql_arr as $row) {            
                //set survey array
                $survey = array('ank_id' => $row['ank_id'], 'link' => $row['url'].'a/'.$row['ank_id'], 'srv_title' => $row['naslov'], 
                    'tracking' => array('id' => $row['id'], 'activity_recognition' => $row['activity_recognition'], 'tracking_accuracy' => $row['tracking_accuracy'],
                    'displacement_min' => $row['displacement_min'], 'interval_wanted' => $row['interval_wanted'], 'interval_fastes' => $row['interval_fastes'], 
                    'ar_interval_wanted' => $row['ar_interval_wanted'], 'permitted'=>$row['tracking_permitted']));
            
                array_push($surveys, $survey);
        }
        return $surveys;
    }
    
    /**
     * Get all data entries of surveys for this user
     * @global type $global_user_id
     * @return array
     */
    public function mazaGetEntries() {
        global $global_user_id;
        
        $sql_arr = sisplet_query("SELECT ank.id, me.ank_id, me.entry_on, me.location_check, ank.naslov, ank.url FROM maza_srv_entry AS me "
                . "LEFT JOIN (SELECT ank_id, maza_user_id FROM maza_user_srv_access) AS sua ON me.ank_id = sua.ank_id "
                . "LEFT JOIN (SELECT id, url, naslov, active FROM srv_anketa) AS ank ON sua.ank_id = ank.id "
                . "WHERE sua.maza_user_id='$global_user_id' AND me.entry_on='1' AND ank.active='1'", 'array');
        
        //array of all surveys with tracking
        $surveys = array();
        //object of one survey with its tracking
        $survey = array();

        //loop trough trackings
        foreach ($sql_arr as $row) {            
                //set survey array
                $survey = array('ank_id' => $row['ank_id'], 'link' => $row['url'].'a/'.$row['ank_id'], 'srv_title' => $row['naslov'], 
                    'entry' => array('id' => $row['id'], 'location_check' => $row['location_check']));
            
                array_push($surveys, $survey);
        }
        return $surveys;
    }
    
    /**
     * Get all locations and AR of user
     * @global type $global_user_id
     * @return array
     */
    public function mazaGetMyLocations() {
        global $global_user_id;
        
        //array of locations of user
        $sql_loc = sisplet_query("SELECT lat, lng, provider, UNIX_TIMESTAMP(timestamp) as timestamp, "
                . "accuracy, altitude, bearing, speed, vertical_acc, bearing_acc, speed_acc, extras, is_mock "
                . "FROM maza_user_locations WHERE maza_user_id='$global_user_id' ORDER BY timestamp ASC", 'array');
        
        //array of all AR of user
        $sql_ar = sisplet_query("SELECT UNIX_TIMESTAMP(timestamp) as timestamp, in_vehicle, on_bicycle, "
                . "on_foot, still, unknown, tilting, running, walking FROM maza_user_activity_recognition "
                . "WHERE maza_user_id='$global_user_id' ORDER BY timestamp ASC", 'array');

        return array("locations" => $sql_loc, "ar" => $sql_ar);
    }

    /**
     * Update tracking log for this user
     * @global type $global_user_id
     * @param type $jsonArray - array of logs to save
     */
    public function mazaUpdateTrackingLog($jsonArray) {
        global $global_user_id;
        
        $update = "";
        foreach ($jsonArray as $obj) {
            $date = new DateTime();
            $date->setTimestamp($obj['tsSec']);

            $update .= $obj['value'] . " " . $obj['event'] . " " . $date->format('Y-m-d H:i:s') . "; ";
        }
        sisplet_query("UPDATE maza_app_users SET tracking_log = CONCAT(tracking_log, '" . $update . "') WHERE id = '$global_user_id'");
    }
    
    /**
     * Insert tracking locations for this user
     * @global type $global_user_id
     * @param type $jsonArray - array of locations to insert
     * @param type $triggered_geofence_id - ID of triggered geofence to pin location on
     */
    public function mazaInsertTrackingLocations($jsonArray, $triggered_geofence_id=null) {
        global $global_user_id;

        $querryFirst = $querry = "INSERT INTO maza_user_locations (maza_user_id, lat, lng, provider, timestamp, accuracy, altitude, bearing, speed, vertical_acc, bearing_acc, speed_acc, extras, is_mock, tgeof_id) VALUES ";
        foreach ($jsonArray as $obj) {            
            $date = new DateTime();
            $date->setTimestamp($obj['timestamp']);
            $date = $date->format('Y-m-d H:i:s');
            
            if(!isset($obj['server_input_id']) || $obj['server_input_id'] == -1){
                $querry .= "($global_user_id, ".$obj['lat'].", ".$obj['lng'].", '".$obj['provider']."', '".$date."', ".($obj['accuracy'] ? $obj['accuracy'] : "NULL").", ".
                        ($obj['altitude'] ? $obj['altitude'] : "NULL").", ".($obj['bearing'] ? $obj['bearing'] : "NULL").", ".($obj['speed'] ? $obj['speed'] : "NULL").", ".
                        ($obj['vertical_acc'] ? $obj['vertical_acc'] : "NULL").", ".($obj['bearing_acc'] ? $obj['bearing_acc'] : "NULL").", ".
                        ($obj['speed_acc'] ? $obj['speed_acc'] : "NULL").", '".($obj['extras'] ? $obj['extras'] : "")."', ".$obj['is_mock'].", ".($triggered_geofence_id ? $triggered_geofence_id : "NULL")."), ";
            }
            else{
                $querry1 = "INSERT INTO maza_user_locations (maza_user_id, lat, lng, provider, timestamp, accuracy, altitude, bearing, speed, vertical_acc, bearing_acc, speed_acc, extras, is_mock, tgeof_id) VALUES "
                        . "($global_user_id, ".$obj['lat'].", ".$obj['lng'].", '".$obj['provider']."', '".$date."', ".($obj['accuracy'] ? $obj['accuracy'] : "NULL").", ".
                        ($obj['altitude'] ? $obj['altitude'] : "NULL").", ".($obj['bearing'] ? $obj['bearing'] : "NULL").", ".($obj['speed'] ? $obj['speed'] : "NULL").", ".
                        ($obj['vertical_acc'] ? $obj['vertical_acc'] : "NULL").", ".($obj['bearing_acc'] ? $obj['bearing_acc'] : "NULL").", ".
                        ($obj['speed_acc'] ? $obj['speed_acc'] : "NULL").", '".($obj['extras'] ? $obj['extras'] : "")."', ".$obj['is_mock'].", ".($triggered_geofence_id ? $triggered_geofence_id : "NULL").");";
                $loc_id = sisplet_query($querry1, 'id');
                $querry2 = "UPDATE maza_srv_users SET loc_id='$loc_id' WHERE srv_user_id='".$obj['server_input_id']."';";
                sisplet_query($querry2);
            }
        }
        if($querryFirst != $querry){
            $querry = substr($querry, 0, -2);
            sisplet_query($querry);
        }
    }
    
    /**
     * Edit tracking locations for this user
     * @global type $global_user_id
     * @param type $jsonArray - array of locations to edit
     */
    public function mazaEditTrackingLocations($jsonArray) {
        global $global_user_id;

        if(isset($jsonArray['delete']) && !empty($jsonArray['delete'])){
            $set_timestamp = "";
            $where_delete="";

            foreach ($jsonArray['delete'] as $obj) { 
                $set_timestamp.=" when UNIX_TIMESTAMP(timestamp)=".$obj['timestamp']." then FROM_UNIXTIME(".$obj['extras'].")";
                $where_delete.=$obj['timestamp'].", ";
            }
            $where_delete = substr($where_delete, 0, -2);

            $querry_delete = "UPDATE maza_user_locations SET timestamp = (case$set_timestamp end), "
                    . "lat=999, lng=999, provider='deleted', accuracy=NULL, altitude=NULL, bearing=NULL, speed=NULL, vertical_acc=NULL, bearing_acc=NULL, speed_acc=NULL, extras=NULL, is_mock=NULL WHERE maza_user_id=$global_user_id AND "
                    . "UNIX_TIMESTAMP(timestamp) IN (".$where_delete.");";

            sisplet_query($querry_delete);
        }
        
        if(isset($jsonArray['edit']) && !empty($jsonArray['edit'])){
            $set_lat = "";
            $set_lng = "";
            $where_edit="";

            foreach ($jsonArray['edit'] as $obj) { 
                $set_lat.=" when UNIX_TIMESTAMP(timestamp)=".$obj['timestamp']." then ".$obj['lat'];
                $set_lng.=" when UNIX_TIMESTAMP(timestamp)=".$obj['timestamp']." then ".$obj['lng'];
                $where_edit.=$obj['timestamp'].", ";
            }
            $where_edit = substr($where_edit, 0, -2);

            $querry_delete = "UPDATE maza_user_locations SET lat = (case$set_lat end), lng = (case$set_lng end), "
                    . "provider='manual' WHERE maza_user_id=$global_user_id AND "
                    . "UNIX_TIMESTAMP(timestamp) IN (".$where_edit.");";

            sisplet_query($querry_delete);
        }
    }
    
    /**
     * Insert tracking locations for this user
     * @global type $global_user_id
     * @param type $jsonArray - array of locations to insert
     */
    public function mazaInsertTrackingAR($jsonArray) {
        global $global_user_id;
        
        $querry = "INSERT INTO maza_user_activity_recognition (maza_user_id, timestamp, in_vehicle, on_bicycle, on_foot, still, unknown, tilting, running, walking) VALUES ";
        foreach ($jsonArray as $obj) {            
            $date = new DateTime();
            $date->setTimestamp($obj['timestamp']);
            $date = $date->format('Y-m-d H:i:s');
            
            $querry .= "($global_user_id, '$date', ".$obj['in_vehicle'].", ".
                    $obj['on_bicycle'].", ".$obj['on_foot'].", ".$obj['still'].", ".
                    $obj['unknown'].", ".$obj['tilting'].", ".
                    $obj['running'].", ".$obj['walking']."), ";
        }
        $querry = substr($querry, 0, -2);
        sisplet_query($querry);
    }
    
    /**
     * Insert new triggered geofences
     * @global type $global_user_id
     * @param type $jsonArray - arrays of triggered geofences to save
     */
    public function mazaInsertTriggeredGeofences($jsonArray) {
        global $global_user_id;
        $new_tgeof_id = 0;

        foreach ($jsonArray as $obj) {

            $date = new DateTime();
            $triggered_timestamp = $date->setTimestamp($obj['geofence']['tsSec'])->format('Y-m-d H:i:s');
            $enter_timestamp = $date->setTimestamp($obj['geofence']['enter_timestamp'])->format('Y-m-d H:i:s');
            $dwell_timestamp = $date->setTimestamp($obj['geofence']['dwell_timestamp'])->format('Y-m-d H:i:s');
            
            $tgeo_id = sisplet_query("INSERT INTO maza_srv_triggered_geofences (geof_id, maza_user_id, triggered_timestamp, enter_timestamp, dwell_timestamp) VALUES "
                    . "('".$obj['geofence']['value']."','$global_user_id', '$triggered_timestamp', '$enter_timestamp' ,'$dwell_timestamp')", "id");
            $this->mazaInsertTrackingLocations($obj['locations'], $tgeo_id);
            
            if(isset($obj['geofence']['return_server_id']) && $obj['geofence']['return_server_id'] == true)
                $new_tgeof_id = $tgeo_id;
        }
        return $new_tgeof_id;
    }
    
    /**
     * Unsubscribe survey for this user
     * @global type $global_user_id
     * @param type $ank_id - survey ID to unsuscribe
     */
    public function mazaUnsubscribeSurvey($ank_id) {
        global $global_user_id;

        sisplet_query("UPDATE maza_user_srv_access SET datetime_unsubscribed = NOW() WHERE maza_user_id = '$global_user_id' AND ank_id = '$ank_id';");
        
        $json_array['note'] = 'Unsubscribed from survey';
        return $json_array;
    }
    
    /**
     * Set agreed permission for tracking and register listener on nextpin API
     * @global type $global_user_id
     * @param type $data - array of data
     */
    public function mazaSetNextpinTrackingPermission($data) {
        global $global_user_id;

        if (isset($data['tracking_permission']) && isset($data['srv_id'])) {
            sisplet_query("UPDATE maza_user_srv_access SET nextpin_tracking_permitted = '" . $data['tracking_permission'] . "' WHERE maza_user_id = '$global_user_id' AND ank_id = '".$data['srv_id']."'");

            if($data['tracking_permission'] == 1){
                
                $sql_pass = sisplet_query("SELECT nextpin_password, identifier FROM maza_app_users WHERE id='$global_user_id';", 'obj');
                if($sql_pass){
                    $maza = new MAZA($data['srv_id']);
                    //password is null, user is not registered on nextpin yet
                    if(!$sql_pass->nextpin_password){
                        //generate new password
                        $newpass = $maza -> randomAlphaNumericCode(4);
                        //set password in db
                        sisplet_query("UPDATE maza_app_users SET nextpin_password = '$newpass' WHERE id = '$global_user_id'");
                        $json_array['data']['nextpin_password'] = $newpass;
                        
                        //create new user in nextpin
                        $maza -> nextpin_create_user($sql_pass->identifier, $newpass);
                    }
                    //create new activity listener for this identifier
                    $maza -> nextpin_set_activity_listener($sql_pass->identifier);
                }
            }
            
            $json_array['note'] = 'Tracking permission updated';
        }
        else
            $json_array['error'] = 'Param tracking_permission or srv_id missing';
        
        return $json_array;
    }
    
    /**
     * Set agreed permission for tracking and register listener
     * @global type $global_user_id
     * @param type $data - array of data
     */
    public function mazaSetTrackingPermission($data) {
        global $global_user_id;

        if (isset($data['tracking_permission']) && isset($data['srv_id'])) {
            sisplet_query("UPDATE maza_user_srv_access SET tracking_permitted = '" . $data['tracking_permission'] . "' WHERE maza_user_id = '$global_user_id' AND ank_id = '".$data['srv_id']."'");
            
            $json_array['note'] = 'Tracking permission updated';
        }
        else
            $json_array['error'] = 'Param tracking_permission or srv_id missing';
        
        return $json_array;
    }   
    
    /**
     * Merge identifiers
     * @global type $global_user_id
     * @param type $identifier - identifier to merge
     */
    public function mazaMergeIdentifier($identifier) {
        global $global_user_id;
        
        //check if this identifier exists
        $sql_arr = sisplet_query("SELECT id FROM maza_app_users WHERE identifier='$identifier';", 'obj');
        
        if($sql_arr->id == $global_user_id)
            return array('error' => 'incorrect identifier');
        //identifier exsits
        else if($sql_arr){
            //get survey access of user
            $sql_user_access = sisplet_query("SELECT ank_id FROM maza_user_srv_access WHERE maza_user_id='$global_user_id';", 'array');
            //get survey access of identifier
            $sql_identifier_access = sisplet_query("SELECT ank_id FROM maza_user_srv_access WHERE maza_user_id='$sql_arr->id';", 'array');
            $user_access_arr = array();
            $identifier_access_arr = array();
            
            //create array of user accesses
            foreach($sql_user_access as $ua)
                array_push($user_access_arr, $ua['ank_id']);
            
            //create array of identifier accesses
            foreach($sql_identifier_access as $ia)
                array_push($identifier_access_arr, $ia['ank_id']);

            //array of which surveys does indentifier has access and not user
            $what_to_add_array = array_diff($identifier_access_arr, $user_access_arr);

            //if not empty, go insert
            if($what_to_add_array){
                $sql_values = "";
                //create string of values(accesses) to insert to user access
                foreach($what_to_add_array as $add)
                    $sql_values .= "('$global_user_id','$add', NOW()),";
                
                //remove last comma
                $sql_values = substr($sql_values, 0, -1);
                //insert new accesses from identifier to user
                sisplet_query("INSERT INTO maza_user_srv_access (maza_user_id, ank_id, datetime_started) VALUES $sql_values");
            }
            
            //delete all accesses of identifier
            sisplet_query("DELETE FROM maza_user_srv_access WHERE maza_user_id = '$sql_arr->id'");
            //delete identifier from user table
            sisplet_query("DELETE FROM maza_app_users WHERE id = '$sql_arr->id'");
            
            return array('note' => 'merge OK');
        }
        //identifier does not exist
        else
            return array('error' => 'identifier does not exist');
    }
    
    /**
     * Get info of surveys to merge (by identifier)
     * @global type $global_user_id
     * @param type $identifier - identifier to get linked surveys info from
     */
    public function mazaGetSurveysInfoByIdentifier($identifier) {
        global $global_user_id;
        
        //check if this identifier exists
        $sql_arr = sisplet_query("SELECT id FROM maza_app_users WHERE identifier='$identifier';", 'obj');
        
        //identifier exsits
        if($sql_arr){
            //get survey access of user
            $sql_user_access = sisplet_query("SELECT ank_id FROM maza_user_srv_access WHERE maza_user_id='$global_user_id';", 'array');
            //get survey access of identifier
            $sql_identifier_access = sisplet_query("SELECT ank_id FROM maza_user_srv_access WHERE maza_user_id='$sql_arr->id';", 'array');
            $user_access_arr = array();
            $identifier_access_arr = array();
            
            //create array of user accesses
            foreach($sql_user_access as $ua)
                array_push($user_access_arr, $ua['ank_id']);
            
            //create array of identifier accesses
            foreach($sql_identifier_access as $ia)
                array_push($identifier_access_arr, $ia['ank_id']);

            //array of which surveys does indentifier has access and not user
            $what_to_add_array = array_diff($identifier_access_arr, $user_access_arr);

            //if not empty, or get info about users identifier, go selelct
            if($sql_arr->id == $global_user_id || $what_to_add_array){
                $sur_ids_in_string = "(".implode( ", ", $what_to_add_array ).")";
            
                //get surveys info
                $sql = "SELECT s.id, s.naslov, s.active, s.starts, s.expire, s.url, "
                        . "ge.geofences, sr.repeater_on, sr.repeat_by, sr.datetime_start, sr.datetime_end, "
                        . "ac.activities, se.entry_on, se.location_check, tr.tracking_on, tr.activity_recognition "
                        . "FROM srv_anketa AS s "
                        . "LEFT JOIN maza_srv_repeaters AS sr ON s.id = sr.ank_id "
                        . "LEFT JOIN (SELECT ank_id, COUNT(id) as geofences FROM maza_srv_geofences WHERE geofence_on='1' GROUP BY ank_id) AS ge ON s.id = ge.ank_id "
                        . "LEFT JOIN (SELECT ank_id, COUNT(id) as activities FROM maza_srv_activity WHERE activity_on='1' GROUP BY ank_id) AS ac ON s.id = ac.ank_id "
                        . "LEFT JOIN (SELECT entry_on, location_check, ank_id FROM maza_srv_entry) AS se ON s.id = se.ank_id "
                        . "LEFT JOIN (SELECT tracking_on, activity_recognition, ank_id FROM maza_srv_tracking) AS tr ON s.id = tr.ank_id "
                        . "WHERE s.id IN $sur_ids_in_string;";

                $sql_surveys_info = sisplet_query($sql, 'array');

                return $sql_surveys_info;
            }
            else
                return array('note' => 'already participant');
            
        }
        //identifier does not exist
        else
            return array('error' => 'identifier does not exist');
    }
    
    /**
     * Get all subscriptions/surveys of user
     * @global type $global_user_id
     * @return type
     */
    public function mazaGetSubscriptionsList($timeZone){
        global $global_user_id;

        //get surveys
        $Sql = "SELECT *, UNIX_TIMESTAMP(datetime_started) as unixstart FROM maza_user_srv_access AS sa "
                . "LEFT JOIN (SELECT id AS srv_id, naslov, active, starts, expire, url FROM srv_anketa) AS s ON s.srv_id = sa.ank_id "
                . "LEFT JOIN maza_srv_repeaters AS sr ON s.srv_id = sr.ank_id "
                . "LEFT JOIN (SELECT ank_id, COUNT(id) as geofences FROM maza_srv_geofences WHERE geofence_on='1' GROUP BY ank_id) AS ge ON s.srv_id = ge.ank_id "
                . "LEFT JOIN (SELECT ank_id, COUNT(id) as activities FROM maza_srv_activity WHERE activity_on='1' GROUP BY ank_id) AS ac ON s.srv_id = ac.ank_id "
                . "LEFT JOIN (SELECT entry_on, location_check, ank_id FROM maza_srv_entry) AS se ON s.srv_id = se.ank_id "
                . "LEFT JOIN (SELECT tracking_on, activity_recognition, ank_id FROM maza_srv_tracking) AS tr ON s.srv_id = tr.ank_id "
                . "LEFT JOIN (SELECT srv_description, srv_id as ank_id FROM maza_survey) AS ms ON ms.ank_id = s.srv_id "
                . "WHERE sa.maza_user_id = '$global_user_id' AND s.active = '1' AND sa.datetime_unsubscribed IS NULL;";

        //all active surveys on which user has access
        $sarray = sisplet_query($Sql, 'array');
        
        $resultarray = array();
        
        //get nuber of unfidished questionnaires
        $surveyListArray = $this->mazaGetSurveyList($timeZone);
        
        foreach($sarray as $survey){
            $survey['unfinished_cnt'] = $surveyListArray[$survey['srv_id']]['unfinished_cnt'];
            $survey['url'] = $survey['url'].'a/'.$survey['srv_id'];
            $resultarray[] = $survey;
        }

        return $resultarray;
    }
        
    /**
     * Vrne seznam anket na katere je uporabnik narocen
     * @param type $timeZone - timezone ID of user
     * @return array
     */
    public function mazaGetSurveyList($timeZone, $srv_id = null) {
        global $global_user_id;
        
        $surveyWhere = $srv_id == null ? '' : ' AND s.srv_id = '.$srv_id;
        $json_array = array();

        //get surveys
        $Sql = "SELECT * FROM maza_user_srv_access AS sa "
                . "LEFT JOIN (SELECT id AS srv_id, naslov, active, starts, expire, url FROM srv_anketa) AS s ON s.srv_id = sa.ank_id "
                . "LEFT JOIN maza_srv_repeaters AS sr ON s.srv_id = sr.ank_id "
                . "LEFT JOIN (SELECT id, ank_id, COUNT(id) as geofences FROM maza_srv_geofences WHERE geofence_on='1' GROUP BY ank_id) AS ge ON s.srv_id = ge.ank_id "
                . "LEFT JOIN (SELECT id, ank_id, COUNT(id) as activities FROM maza_srv_activity WHERE activity_on='1' GROUP BY ank_id) AS ac ON s.srv_id = ac.ank_id "
                . "LEFT JOIN (SELECT tracking_on, ank_id FROM maza_srv_tracking) AS st ON s.srv_id = st.ank_id "
                . "LEFT JOIN (SELECT entry_on, ank_id FROM maza_srv_entry) AS se ON s.srv_id = se.ank_id "
                . "WHERE sa.maza_user_id = '$global_user_id' AND s.active = '1' AND sa.datetime_unsubscribed IS NULL$surveyWhere;";

        //all active surveys on which user has access
        $sarray = sisplet_query($Sql, 'array');

        //get existing surveys for this user
        $Sql = "SELECT *, UNIX_TIMESTAMP(srv_version_datetime) as unixver FROM maza_srv_users AS msu "
            . "LEFT JOIN (SELECT id, cookie, ank_id, last_status, time_insert FROM srv_user) AS su ON msu.srv_user_id = su.id "
            . "LEFT JOIN (SELECT id AS srv_id, active, starts, naslov, url FROM srv_anketa) AS s ON su.ank_id = s.srv_id "
            . "LEFT JOIN (SELECT maza_user_id, ank_id, datetime_unsubscribed FROM maza_user_srv_access) AS sa ON sa.ank_id = s.srv_id "
            . "WHERE s.active = '1' AND msu.maza_user_id = '$global_user_id' AND sa.maza_user_id = '$global_user_id' AND sa.datetime_unsubscribed IS NULL$surveyWhere;";
        
        //cookies of surveys for all active surveys of this user
        $uarray = sisplet_query($Sql, 'array'); 

        //do we have any active survey with data entries?
        $dataEntryArray = null;
        
        //create array of users surveys to later access it
        $cookies_array = array();
        foreach($uarray as $row){ 
            //if we have a entry mode and didnt get data entry array yet, run it
            if($dataEntryArray == null && $row['mode'] == 'entry'){
                $dataEntryArray = $this->getDataEntryArray();
            }
            
            $link = $row['url'].'a/'.$row['srv_id'].'&survey-'.$row['srv_id'].'='.$row['cookie'];
                
            $srv_version_datetime = ($row['srv_version_datetime'] != null && $row['unixver'] != null) ? $row["srv_version_datetime"] : $row["time_insert"];
            $temp_date = new DateTime($srv_version_datetime);
            //$srv_version_datetime = $temp_date->format('Hi_d_m_Y');
            $srv_version_timestamp = $row['unixver'] != null ? $row['unixver'] : $temp_date->getTimestamp();
      
            $cookies_array[$row['srv_id']][$srv_version_timestamp] = array('status'=>$row['last_status'], 'link'=>$link, 'naslov'=>$row['naslov'], 'srv_id'=>$row['ank_id'], 
                'timestamp'=>$srv_version_timestamp, 'datetime'=>$srv_version_datetime, 'mode'=>$row['mode'], 'srv_version'=>$srv_version_datetime, 
                'latitude'=>$dataEntryArray[$row['srv_user_id']]['latitude'], 'longitude'=>$dataEntryArray[$row['srv_user_id']]['longitude'], 'srv_user_id'=>$row['srv_user_id']);
        }
 
        //set users timezone (all calculations are for users timezone)
        $dateTz = new DateTimeZone($timeZone);
        //what is now a time of users device
        $Udate = new DateTime("now", $dateTz);
        //get offset in hours of user timezone versus server timezone (for calculations of datetime from DB)
        $offset = $dateTz->getOffset($Udate)-date('Z');
        //set users timestamp based on time of DB datetimes
        $user_timestamp_now = $Udate->getTimestamp()+$offset;
        
        //geofences
        $sql_geo = sisplet_query("SELECT g.ank_id, tg.id, tg.geof_id, tg.triggered_timestamp, g.address, g.name, g.trigger_survey, s.naslov, s.url, sa.ank_id, sa.maza_user_id, sa.datetime_unsubscribed "
                . "FROM maza_srv_triggered_geofences AS tg "
                . "LEFT JOIN (SELECT ank_id, geofence_on, id, address, name, trigger_survey FROM maza_srv_geofences) AS g ON g.id=tg.geof_id "
                . "LEFT JOIN (SELECT id as srv_id, active, naslov, url FROM srv_anketa) AS s ON g.ank_id = s.srv_id "
                . "LEFT JOIN (SELECT maza_user_id, ank_id, datetime_unsubscribed FROM maza_user_srv_access) AS sa ON sa.ank_id = g.ank_id "
                . "WHERE tg.maza_user_id='$global_user_id' AND s.active = '1' AND sa.maza_user_id = '$global_user_id' AND sa.datetime_unsubscribed IS NULL$surveyWhere;", 'array');
        //this survey has geofencing
        if (count($sql_geo) > 0) {
            foreach($sql_geo as $tgeo){    
                if($tgeo['trigger_survey']!=null){
                    $triggered_datetime = new DateTime($tgeo['triggered_timestamp']);
                    $triggered_timestamp = $triggered_datetime->getTimestamp();
                    $link = isset($cookies_array[$tgeo['ank_id']][$triggered_timestamp]) ? $cookies_array[$tgeo['ank_id']][$triggered_timestamp]['link'] : $tgeo['url'].'a/'.$tgeo['ank_id'];
                    $user_status = isset($cookies_array[$tgeo['ank_id']][$triggered_timestamp]) ? $cookies_array[$tgeo['ank_id']][$triggered_timestamp]['status'] : '';
                    $srv_user_id = isset($cookies_array[$tgeo['ank_id']][$triggered_timestamp]) ? $cookies_array[$tgeo['ank_id']][$triggered_timestamp]['srv_user_id'] : '';
                    //$srv_version = $temp_date->format('Hi_d_m_Y');
                    /*$new_array = array('timestamp' => $triggered_timestamp, 'datetime' => $tgeo['triggered_timestamp'], 'srv_id' => $tgeo['ank_id'], 'naslov' => $tgeo['naslov'], 'name' => $tgeo['name'], 
                        'srv_version' => $tgeo['triggered_timestamp'], 'link' => $link, 'status' => $user_status, 'address' => $tgeo['address'], 'tgeof_id' => $tgeo['id'], 'mode' => 'geofence', 'srv_user_id'=>$srv_user_id);*/
                    $unfinished = $user_status == 6 ? 0 : 1;
                    
                    if(isset($json_array[$tgeo['ank_id']]['surveys'])){
                        $json_array[$tgeo['ank_id']]['surveys'][]=array('timestamp' => $triggered_timestamp, 'datetime' => $tgeo['triggered_timestamp'], 'srv_version' => $tgeo['triggered_timestamp'], 
                            'link' => $link, 'status' => $user_status, 'tgeof_id' => $tgeo['id'], 'mode' => 'geofence', 'srv_user_id'=>$srv_user_id, 'name' => $tgeo['name'], 'address' => $tgeo['address']);
                        $json_array[$tgeo['ank_id']]['unfinished_cnt']+=$unfinished;
                    }
                    else{
                        $json_array[$tgeo['ank_id']]=array('srv_id' => $tgeo['ank_id'], 'naslov' => $tgeo['naslov'], 'unfinished_cnt' => $unfinished, 
                            'surveys' => array(array('timestamp' => $triggered_timestamp, 'datetime' => $tgeo['triggered_timestamp'], 'srv_version' => $tgeo['triggered_timestamp'], 'link' => $link, 
                            'status' => $user_status, 'tgeof_id' => $tgeo['id'], 'mode' => 'geofence', 'srv_user_id'=>$srv_user_id, 'name' => $tgeo['name'], 'address' => $tgeo['address'])));
                    }
                    
                    //array_push($json_array, $new_array);

                    if(isset($cookies_array[$tgeo['ank_id']][$triggered_timestamp]))
                        unset($cookies_array[$tgeo['ank_id']][$triggered_timestamp]);
                }
            }
        }
        
        //activity
        $sql_act = sisplet_query("SELECT a.ank_id, ta.id, ta.act_id, ta.triggered_timestamp, s.naslov, s.url, sa.ank_id, sa.maza_user_id, sa.datetime_unsubscribed "
                . "FROM maza_srv_triggered_activities AS ta "
                . "LEFT JOIN (SELECT ank_id, activity_on, id FROM maza_srv_activity) AS a ON a.id=ta.act_id "
                . "LEFT JOIN (SELECT id as srv_id, active, naslov, url FROM srv_anketa) AS s ON a.ank_id = s.srv_id "
                . "LEFT JOIN (SELECT maza_user_id, ank_id, datetime_unsubscribed FROM maza_user_srv_access) AS sa ON sa.ank_id = g.ank_id "
                . "WHERE ta.maza_user_id='$global_user_id' AND s.active = '1' AND sa.maza_user_id = '$global_user_id' AND sa.datetime_unsubscribed IS NULL$surveyWhere;", 'array');
        //this survey has activities
        if (count($sql_act) > 0) {
            foreach($sql_act as $tact){                    
                $triggered_datetime = new DateTime($tact['triggered_timestamp']);
                $triggered_timestamp = $triggered_datetime->getTimestamp();
                $link = isset($cookies_array[$tact['ank_id']][$triggered_timestamp]) ? $cookies_array[$tact['ank_id']][$triggered_timestamp]['link'] : $tact['url'].'a/'.$tact['ank_id'];
                $user_status = isset($cookies_array[$tact['ank_id']][$triggered_timestamp]) ? $cookies_array[$tact['ank_id']][$triggered_timestamp]['status'] : '';
                $srv_user_id = isset($cookies_array[$tact['ank_id']][$triggered_timestamp]) ? $cookies_array[$tact['ank_id']][$triggered_timestamp]['srv_user_id'] : '';
                //$srv_version = $temp_date->format('Hi_d_m_Y');
                /*$new_array = array('timestamp' => $triggered_timestamp, 'datetime' => $tact['triggered_timestamp'], 'srv_id' => $tact['ank_id'], 'naslov' => $tact['naslov'], 
                    'srv_version' => $tact['triggered_timestamp'], 'link' => $link, 'status' => $user_status, 'tact_id' => $tact['id'], 'mode' => 'activity', 'srv_user_id'=>$srv_user_id);*/
                $unfinished = $user_status == 6 ? 0 : 1;
                
                if(isset($json_array[$tact['ank_id']]['surveys'])){
                    $json_array[$tgeo['ank_id']]['surveys'][]= array('timestamp' => $triggered_timestamp, 'datetime' => $tact['triggered_timestamp'], 'srv_version' => $tact['triggered_timestamp'], 
                        'link' => $link, 'status' => $user_status, 'tact_id' => $tact['id'], 'mode' => 'activity', 'srv_user_id'=>$srv_user_id);
                    $json_array[$tgeo['ank_id']]['unfinished_cnt']+=$unfinished;
                }
                else
                    $json_array[$tact['ank_id']]=array('srv_id' => $tact['ank_id'], 'naslov' => $tact['naslov'], 'unfinished_cnt' => $unfinished, 
                        'surveys' => array(array('timestamp' => $triggered_timestamp, 'datetime' => $tact['triggered_timestamp'], 'srv_version' => $tact['triggered_timestamp'], 
                        'link' => $link, 'status' => $user_status, 'tact_id' => $tact['id'], 'mode' => 'activity', 'srv_user_id'=>$srv_user_id)));
                
                //array_push($json_array, $new_array);
                
                if(isset($cookies_array[$tact['ank_id']][$triggered_timestamp]))
                    unset($cookies_array[$tact['ank_id']][$triggered_timestamp]);
            }
        }        
        
        foreach($sarray as $survey){
            //if($survey["repeater_on"] != '2'){
                //survey with no repeater
                if($survey["repeater_on"] == '0'){
                    //only basic survey with no answer
                    if(($survey['geofences'] == null || $survey['geofences'] == '0') && 
                            ($survey['activities'] == null || $survey['activities'] == '0') && 
                            ($survey['entry_on'] == null || $survey['entry_on'] == '0') && 
                            ($survey['tracking_on'] == null || $survey['tracking_on'] == '0') && 
                            !isset($cookies_array[$survey['srv_id']])){
                        $link = $survey['url'].'a/'.$survey['srv_id'];
                        $user_status = '';
                        $temp_timestamp = strtotime($survey['starts']);
                        //$srv_version = $temp_date->format('Hi_d_m_Y');
                        /*$new_array = array('timestamp' => $temp_timestamp, 'datetime' => $survey['starts'], 'srv_id' => $survey['srv_id'], 'naslov' => $survey['naslov'], 
                            'srv_version' => '', 'link' => $link, 'status' => $user_status, 'mode' => 'basic', 'srv_user_id'=>'');*/
                        $json_array[$survey['srv_id']] = array('srv_id' => $survey['srv_id'], 'naslov' => $survey['naslov'], 'unfinished_cnt' => 1, 
                            'surveys' => array(array('timestamp' => $temp_timestamp, 'datetime' => $survey['starts'], 'link' => $link, 'srv_version' => '', 'status' => $user_status, 'mode' => 'basic', 'srv_user_id'=>'')));
                        //array_push($json_array, $new_array);
                    }
                }
                //survey with repeater
                else{
                    //use latest datetime to start repeater for this user (when repeater started or when user accessed)
                    $Accessdate = new DateTime($survey["datetime_started"]);
                    $Startdate = new DateTime($survey["datetime_start"]);
                    $Startdate = $Startdate < $Accessdate ? $Accessdate : $Startdate;
                    $Startdate_string = $Startdate->format('Y-m-d');

                    //if, based on timezone user-server differences, users timestamp is less than starting of repeater, there are no avalible surveys yet 
                    //we have to wait until users date and time gets at least to date and time of servers datetime of repeater started
                    if(strtotime($Startdate_string) <= $user_timestamp_now /*&& $user_timestamp_now <= $Endtimestamp*/){
                        switch($survey["repeat_by"]){
                            case 'everyday':
                                $temp_date_by_day = new DateTime($Startdate_string);
                                $edited_arr = $this->createArrayRepeater($json_array, $temp_date_by_day, $user_timestamp_now, $survey, $cookies_array, 1);
                                $json_array = $edited_arr['surveys'];
                                $cookies_array = $edited_arr['cookies'];
                                break;
                            case 'daily':
                                $temp_date_by_day = new DateTime($Startdate_string);
                                $edited_arr = $this->createArrayRepeater($json_array, $temp_date_by_day, $user_timestamp_now, $survey, $cookies_array, $survey['every_which_day']);
                                $json_array = $edited_arr['surveys'];
                                $cookies_array = $edited_arr['cookies'];
                                break;
                            case 'weekly':
                                foreach(json_decode($survey['day_in_week']) as $dayinweek){     
                                    $temp_date_by_day = new DateTime($Startdate_string);
                                    $dayinweek_string = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    if($temp_date_by_day->format('l') != $dayinweek_string[$dayinweek-1])
                                        $temp_date_by_day->modify('next '.$dayinweek_string[$dayinweek-1]);

                                    $edited_arr = $this->createArrayRepeater($json_array, $temp_date_by_day, $user_timestamp_now, $survey, $cookies_array, 7);          
                                    $json_array = $edited_arr['surveys'];
                                    $cookies_array = $edited_arr['cookies'];
                                }
                                break;
                        }
                    }
                }
            //}
        }

        //for data entry surveys and basic survey with answer
        foreach($cookies_array as $survey){
            foreach($survey as $cookie){
                //include basic survey with answer and data entry if it is still on
                if(!isset($cookie['mode']) || ($cookie['mode'] == 'entry')){
                    //array_push($json_array, $cookie);
                    if(isset($json_array[$cookie['srv_id']]['surveys']))
                        $json_array[$cookie['srv_id']]['surveys'][] = $cookie;
                    else
                        $json_array[$cookie['srv_id']] = array('srv_id' => $cookie['srv_id'], 'naslov' => $cookie['naslov'], 'surveys'=>array($cookie));
                    
                    $json_array[$cookie['srv_id']]['unfinished_cnt']+=($cookie['status']==6 ? 0:1);
                }
            }
        }
            
        //krsort($json_array);
        //tole mislim da sicer razvrsti, ampak pobrise keye
        /*usort($json_array, function ($a, $b)
        {
            return -strcmp($a['timestamp'], $b['timestamp']);
        });*/

        return $json_array;
    }
    
    /**
     * Get array of data entry latitude and longitude values from answers
     * @global type $global_user_id
     * @return type array of data with usr_id as keys
     */
    private function getDataEntryArray(){
        global $global_user_id;
        $dataEntryArray = array();
                
        $Sql = "SELECT * FROM maza_srv_users AS msu "
                . "LEFT JOIN (SELECT id, ank_id FROM srv_user) AS su ON msu.srv_user_id = su.id "
                . "LEFT JOIN (SELECT id AS srv_id, active FROM srv_anketa) AS s ON su.ank_id = s.srv_id "
                . "LEFT JOIN (SELECT id as gru_id, ank_id FROM srv_grupa) AS gru ON su.ank_id = gru.ank_id "
                . "LEFT JOIN (SELECT id AS lat_spr_id, gru_id FROM srv_spremenljivka WHERE variable='latitude' AND sistem='1') AS spr1 ON gru.gru_id = spr1.gru_id "
                . "LEFT JOIN (SELECT text as latitude, usr_id, spr_id FROM srv_data_text_active) AS dt1 ON msu.srv_user_id = dt1.usr_id AND dt1.spr_id = spr1.lat_spr_id "
                . "LEFT JOIN (SELECT id AS lng_spr_id, gru_id FROM srv_spremenljivka WHERE variable='longitude' AND sistem='1') AS spr2 ON gru.gru_id = spr2.gru_id "
                . "LEFT JOIN (SELECT text as longitude, usr_id, spr_id FROM srv_data_text_active) AS dt2 ON msu.srv_user_id = dt2.usr_id AND dt2.spr_id = spr2.lng_spr_id "
                . "WHERE s.active = '1' AND msu.maza_user_id = '$global_user_id' AND msu.mode='entry' AND dt1.latitude IS NOT NULL AND dt2.longitude IS NOT NULL;";

        //coordinates of data entries for all active surveys of this user
        $entryarray = sisplet_query($Sql, 'array');

        foreach($entryarray as $array){
            $dataEntryArray[$array['srv_user_id']] = $array;
        }
        
        return $dataEntryArray;
    }
    
    /**
     * MAZA function
     * Creates or modifies a json array of surveys with repeater
     * @param type $json_array - array to modify or empty array
     * @param type $temp_date_by_day - DateTime object of starting day
     * @param type $user_timestamp_now - timestamp of users now (by users timezone)
     * @param type $survey - array of surveys and repeaters data for survey, to which user has access
     * @param type $cookies_array - array of existing surveys for this user
     * @param type $every_which_day - every which day has to be repeated (every day - 1, weekly - 7,...)
     * @return type json array of surveys for this user
     */
    private function createArrayRepeater($json_array, $temp_date_by_day, $user_timestamp_now, $survey, $cookies_array, $every_which_day){
        //get ending timestamp time of repeater                  
        $Endtimestamp = $survey["datetime_end"] != null ? (new DateTime($survey["datetime_end"]))->getTimestamp() : null;
                    
        //loop until users timestamp of now
        while($temp_date_by_day->getTimestamp() <= $user_timestamp_now && ($Endtimestamp != null ? $temp_date_by_day->getTimestamp() <= $Endtimestamp : true)){
            //iterate trough times in day
            foreach(json_decode($survey['time_in_day']) as $timeinday){
                //set temporary datetime of given date and time in day
                $temp_date = DateTime::createFromFormat('Y-m-d Hi', $temp_date_by_day->format('Y-m-d').' '.$timeinday);
                $temp_date_timestamp = $temp_date->getTimestamp();

                if(/*$temp_date_timestamp > strtotime($survey["datetime_started"]) &&*/ $temp_date_timestamp < $user_timestamp_now){
                    $srv_version = $temp_date->format('Hi_d_m_Y');
                    $link = isset($cookies_array[$survey['srv_id']][$temp_date_timestamp]) ? $cookies_array[$survey['srv_id']][$temp_date_timestamp]['link'] : $survey['url'].'a/'.$survey['srv_id'];
                    $user_status = isset($cookies_array[$survey['srv_id']][$temp_date_timestamp]) ? $cookies_array[$survey['srv_id']][$temp_date_timestamp]['status'] : '';
                    $srv_user_id = isset($cookies_array[$survey['srv_id']][$temp_date_timestamp]) ? $cookies_array[$survey['srv_id']][$temp_date_timestamp]['srv_user_id'] : '';
                    /*$new_array = array('timestamp' => $temp_date_timestamp, 'datetime' => $temp_date->format('Y-m-d H:i:s'), 'srv_id' => $survey['srv_id'], 'naslov' => $survey['naslov'], 
                        'srv_version' => $srv_version, 'link' => $link, 'status' => $user_status, 'mode' => 'repeater', 'srv_user_id'=>$srv_user_id);*/
                    //array_push($json_array, $new_array);
                    $unfinished = $user_status == 6 ? 0:1;

                    $survey_array = array('timestamp' => $temp_date_timestamp, 'datetime' => $temp_date->format('Y-m-d H:i:s'), 'link' => $link, 
                            'srv_version' => $srv_version, 'status' => $user_status, 'mode' => 'repeater', 'srv_user_id'=>$srv_user_id);
                    
                    if(isset($json_array[$survey['srv_id']]['surveys'])){
                        array_push($json_array[$survey['srv_id']]['surveys'], $survey_array);
                        $json_array[$survey['srv_id']]['unfinished_cnt']+=$unfinished;
                    }
                    else
                        $json_array[$survey['srv_id']] = array('srv_id' => $survey['srv_id'], 'naslov' => $survey['naslov'], 'unfinished_cnt' => $unfinished, 'surveys'=>array($survey_array));
                    
                    if(isset($cookies_array[$survey['srv_id']][$temp_date_timestamp]))
                        unset($cookies_array[$survey['srv_id']][$temp_date_timestamp]);
                }
            }
            //increase date by given number of days
            $temp_date_by_day->modify('+'.$every_which_day.' day');
        }
        
        $result = array('surveys' => $json_array, 'cookies' => $cookies_array);
        return $result;
    }
    //END MAZA
}


