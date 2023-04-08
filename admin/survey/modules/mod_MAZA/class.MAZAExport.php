<?php

/**
 * Created on 23.1.2018
 *
 * @author: Uroš Podkrižnik
 *
 * @desc: for exports of module MAZA - mobile app for respondents
 *
 *
 */
class MAZAExport {

    var $_ank_id; // id ankete

    function __construct($ank_id = 0) {
        $this->_ank_id = $ank_id;
    }

    /*private function getDataInactiveIdentifiers() {
        $Query = "SELECT au.datetime_inserted, au.identifier FROM maza_app_users AS au " .
                "LEFT JOIN (SELECT ank_id, maza_user_id FROM maza_user_srv_access) AS sa " .
                "ON au.id = sa.maza_user_id WHERE sa.ank_id='$this->_ank_id' AND au.datetime_last_active IS NULL;";

        $podatki = array(array('inserted', 'identifier'));

        $sqlQuery = sisplet_query($Query);

        if (mysqli_num_rows($sqlQuery)) {
            while ($sql_row = mysqli_fetch_assoc($sqlQuery)) {
                $podatki[] = array($sql_row['datetime_inserted'], $sql_row['identifier']);
            }
        }

        return $podatki;
    }
    
    private function getDataActiveIdentifiers() {
        $Query = "SELECT au.datetime_inserted, au.identifier, au.datetime_last_active, au.deviceInfo, au.tracking_log FROM maza_app_users AS au " .
                "LEFT JOIN (SELECT ank_id, maza_user_id FROM maza_user_srv_access) AS sa " .
                "ON au.id = sa.maza_user_id WHERE sa.ank_id='$this->_ank_id' AND au.datetime_last_active IS NOT NULL;";

        $podatki = array(array('inserted', 'identifier', 'last_active', 'device_info', 'tracking_log'));

        $sqlQuery = sisplet_query($Query);

        if (mysqli_num_rows($sqlQuery)) {
            while ($sql_row = mysqli_fetch_assoc($sqlQuery)) {
                $podatki[] = array($sql_row['datetime_inserted'], $sql_row['identifier'], $sql_row['datetime_last_active'], $sql_row['deviceInfo'], $sql_row['tracking_log']);
            }
        }

        return $podatki;
    }*/
    
    private function getDataIdentifiers() {
        $act = isset($_POST['maza_active_ident']) && $_POST['maza_active_ident'] == 1;
        $inact = isset($_POST['maza_inactive_ident']) && $_POST['maza_inactive_ident'] == 1;
        $deact = isset($_POST['maza_deactive_ident']) && $_POST['maza_deactive_ident'] == 1;
        
        $where = '';
        if($act && !$inact)
            $where .= ' AND au.datetime_last_active IS NOT NULL';
        else if(!$act && $inact && $deact)
            $where .= ' AND (au.datetime_last_active IS NULL OR sa.datetime_unsubscribed IS NOT NULL)';
        else if(!$act && $inact)
            $where .= ' AND au.datetime_last_active IS NULL';
        if($deact && !$act && !$inact)
            $where .= ' AND sa.datetime_unsubscribed IS NOT NULL';
        else if(!$deact)
            $where .= ' AND sa.datetime_unsubscribed IS NULL';
        
        $Query = "SELECT au.datetime_inserted, au.identifier, au.datetime_last_active, au.deviceInfo, au.tracking_log, sa.datetime_unsubscribed FROM maza_app_users AS au " .
                "LEFT JOIN (SELECT ank_id, maza_user_id, datetime_unsubscribed FROM maza_user_srv_access) AS sa " .
                "ON au.id = sa.maza_user_id WHERE sa.ank_id='$this->_ank_id'$where;";
        
        $podatki = array(array('inserted', 'identifier', 'last_active', 'device_info', 'tracking_log', 'unsubscribed'));

        $sqlQuery = sisplet_query($Query);

        if (mysqli_num_rows($sqlQuery)) {
            while ($sql_row = mysqli_fetch_assoc($sqlQuery)) {
                $podatki[] = array($sql_row['datetime_inserted'], $sql_row['identifier'], $sql_row['datetime_last_active'], $sql_row['deviceInfo'], $sql_row['tracking_log'], $sql_row['datetime_unsubscribed']);
            }
        }

        return $podatki;
    }
    
    private function getDataTrackingLocations() {
        $Query = "SELECT au.identifier, ml.maza_user_id, ml.lat, ml.lng, ml.provider, ml.timestamp, ml.accuracy, ml.altitude, "
                . "ml.bearing, ml.speed, ml.vertical_acc, ml.speed_acc, ml.bearing_acc, ml.extras, ml.is_mock FROM maza_user_locations AS ml "
                . "LEFT JOIN (SELECT ank_id, maza_user_id, datetime_started FROM maza_user_srv_access) AS sa "
                . "ON ml.maza_user_id = sa.maza_user_id "
                . "LEFT JOIN (SELECT id, identifier FROM maza_app_users) AS au ON au.id = ml.maza_user_id "
                . "WHERE sa.ank_id='$this->_ank_id' AND sa.datetime_started IS NOT NULL AND sa.datetime_started <= ml.timestamp "
                . "ORDER BY sa.datetime_started, au.identifier, ml.timestamp DESC;";

        $podatki = array(array('Identifier', 'timestamp', 'latitude', 'longitude', 'provider', 'accuracy', 
            'altitude', 'bearing', 'speed', 'vertical_acc', 'speed_acc', 'bearing_acc', 'extras', 'is_mock'));

        $sqlQuery = sisplet_query($Query);

        if (mysqli_num_rows($sqlQuery)) {
            while ($row = mysqli_fetch_assoc($sqlQuery)) {
                $podatki[] = array($row['identifier'], $row['timestamp'], $row['lat'], $row['lng'], $row['provider'], 
                    $row['accuracy'], $row['altitude'], $row['bearing'], $row['speed'], $row['vertical_acc'], $row['speed_acc'], 
                    $row['bearing_acc'], $row['extras'], $row['is_mock']);
            }
        }
        return $podatki;
    }
    
    private function getDataTrackingAR() {
        $Query = "SELECT au.identifier, ml.maza_user_id, ml.timestamp, ml.in_vehicle, ml.on_bicycle, ml.on_foot, ml.walking, "
                . "ml.running, ml.still, ml.tilting, ml.unknown FROM maza_user_activity_recognition AS ml "
                . "LEFT JOIN (SELECT ank_id, maza_user_id, datetime_started FROM maza_user_srv_access) AS sa "
                . "ON ml.maza_user_id = sa.maza_user_id "
                . "LEFT JOIN (SELECT id, identifier FROM maza_app_users) AS au ON au.id = ml.maza_user_id "
                . "WHERE sa.ank_id='$this->_ank_id' AND sa.datetime_started IS NOT NULL AND sa.datetime_started <= ml.timestamp "
                . "ORDER BY sa.datetime_started, au.identifier, ml.timestamp DESC;";

        $podatki = array(array('identifier', 'timestamp', 'in_vehicle', 'on_bicycle', 'on_foot', 'walking', 'running', 
            'still', 'tilting', 'unknown'));

        $sqlQuery = sisplet_query($Query);

        if (mysqli_num_rows($sqlQuery)) {
            while ($row = mysqli_fetch_assoc($sqlQuery)) {
                $podatki[] = array($row['identifier'], $row['timestamp'], $row['in_vehicle'], $row['on_bicycle'], $row['on_foot'], 
                    $row['walking'], $row['running'], $row['still'], $row['tilting'], $row['unknown']);
            }
        }

        return $podatki;
    }
    
    private function getDataAlarmRespondents() {
        $Query = "SELECT au.identifier, su.maza_user_id, su.srv_user_id, su.mode, su.srv_version_datetime, res.id, res.recnum, re.ank_id FROM maza_srv_users AS su "
                . "LEFT JOIN (SELECT ank_id, maza_user_id, datetime_started FROM maza_user_srv_access) AS sa ON su.maza_user_id = sa.maza_user_id "
                . "LEFT JOIN (SELECT ank_id FROM maza_srv_repeaters) AS re ON re.ank_id = sa.ank_id "
                . "LEFT JOIN (SELECT id, identifier FROM maza_app_users) AS au ON au.id = su.maza_user_id "
                . "LEFT JOIN (SELECT id, recnum FROM srv_user) AS res ON res.id = su.srv_user_id "
                . "WHERE sa.ank_id='$this->_ank_id' AND re.ank_id='$this->_ank_id' AND sa.datetime_started IS NOT NULL AND (mode='repeater' OR (su.geof_id IS NULL AND su.activity_id IS NULL)) "//todo zadnji OR je samo zacasno, pobrisi enkrat po APP verziji 25
                . "ORDER BY sa.datetime_started, au.identifier, su.srv_version_datetime DESC;";

        $podatki = array(array('identifier', 'recnum', 'time_version'));

        $sqlQuery = sisplet_query($Query);

        if (mysqli_num_rows($sqlQuery)) {
            while ($row = mysqli_fetch_assoc($sqlQuery)) {
                $podatki[] = array($row['identifier'], $row['recnum'], $row['srv_version_datetime']);
            }
        }
        return $podatki;
    }
        
    private function getDataTriggeredGeofences() {
        $Query = "SELECT tg.id, tg.maza_user_id, tg.geof_id, tg.triggered_timestamp, tg.enter_timestamp, tg.dwell_timestamp, au.identifier, ge.name, su.maza_user_id, su.srv_user_id, su.srv_version_datetime, su.mode, su.tgeof_id, res.id, res.recnum "
                . ", ml.maza_user_id, ml.lat, ml.lng, ml.provider, ml.timestamp, ml.accuracy, ml.altitude, ml.bearing, ml.speed, ml.vertical_acc, ml.speed_acc, ml.bearing_acc, ml.extras, ml.is_mock"
                . " FROM maza_srv_triggered_geofences AS tg "
                . "LEFT JOIN (SELECT tgeof_id, maza_user_id, lat, lng, provider, timestamp, accuracy, altitude, bearing, speed, vertical_acc, speed_acc, bearing_acc, extras, is_mock FROM maza_user_locations) AS ml ON tg.id = ml.tgeof_id "
                . "LEFT JOIN (SELECT ank_id, id, name FROM maza_srv_geofences) AS ge ON ge.id = tg.geof_id "
                . "LEFT JOIN (SELECT ank_id, maza_user_id, datetime_started FROM maza_user_srv_access) AS sa ON tg.maza_user_id = sa.maza_user_id "
                . "LEFT JOIN (SELECT id, identifier FROM maza_app_users) AS au ON au.id = tg.maza_user_id "
                . "LEFT JOIN (SELECT maza_user_id, srv_user_id, srv_version_datetime, tgeof_id, mode FROM maza_srv_users) AS su ON su.maza_user_id = tg.maza_user_id AND su.tgeof_id = tg.id "
                . "LEFT JOIN (SELECT id, recnum FROM srv_user) AS res ON res.id = su.srv_user_id "
                . "WHERE sa.ank_id='$this->_ank_id' AND ge.ank_id='$this->_ank_id' AND sa.datetime_started IS NOT NULL "
                . "ORDER BY sa.datetime_started, au.identifier, tg.triggered_timestamp DESC;";
        
        $podatki = array(array('identifier', 'recnum', 'geofence_id', 'geofence_name', 'triggered_timestamp', 'enter_timestamp', 'dwell_timestamp', 'location_timestamp', 'latitude', 'longitude', 'provider', 'accuracy', 
            'altitude', 'bearing', 'speed', 'vertical_acc', 'speed_acc', 'bearing_acc', 'extras', 'is_mock'));

        $sqlQuery = sisplet_query($Query);

        if (mysqli_num_rows($sqlQuery)) {
            while ($row = mysqli_fetch_assoc($sqlQuery)) {
                $podatki[] = array($row['identifier'], $row['recnum'], $row['geof_id'], $row['name'], $row['triggered_timestamp'], $row['enter_timestamp'], $row['dwell_timestamp'], 
                    $row['timestamp'], $row['lat'], $row['lng'], $row['provider'], $row['accuracy'], $row['altitude'], $row['bearing'], $row['speed'], $row['vertical_acc'], $row['speed_acc'], 
                    $row['bearing_acc'], $row['extras'], $row['is_mock']);
            }
        }
        return $podatki;
    }
    
        private function getDataTriggeredGeofencesAnswers() {
        $Query = "SELECT tg.id, tg.maza_user_id, tg.geof_id, tg.triggered_timestamp, au.identifier, ge.name, su.maza_user_id, su.srv_user_id, su.srv_version_datetime, su.mode, su.tgeof_id, res.id, res.recnum "
                . ", ml.maza_user_id, ml.lat, ml.lng, ml.provider, ml.timestamp, ml.accuracy, ml.altitude, ml.bearing, ml.speed, ml.vertical_acc, ml.speed_acc, ml.bearing_acc, ml.extras, ml.is_mock"
                . " FROM maza_srv_triggered_geofences AS tg "
                . "LEFT JOIN (SELECT tgeof_id, maza_user_id, lat, lng, provider, timestamp, accuracy, altitude, bearing, speed, vertical_acc, speed_acc, bearing_acc, extras, is_mock FROM maza_user_locations) AS ml ON tg.id = ml.tgeof_id "
                . "LEFT JOIN (SELECT ank_id, id, name FROM maza_srv_geofences) AS ge ON ge.id = tg.geof_id "
                . "LEFT JOIN (SELECT ank_id, maza_user_id, datetime_started FROM maza_user_srv_access) AS sa ON tg.maza_user_id = sa.maza_user_id "
                . "LEFT JOIN (SELECT id, identifier FROM maza_app_users) AS au ON au.id = tg.maza_user_id "
                . "LEFT JOIN (SELECT maza_user_id, srv_user_id, srv_version_datetime, tgeof_id, mode FROM maza_srv_users) AS su ON su.maza_user_id = tg.maza_user_id AND su.tgeof_id = tg.id "
                . "LEFT JOIN (SELECT id, recnum FROM srv_user) AS res ON res.id = su.srv_user_id "
                . "WHERE sa.ank_id='$this->_ank_id' AND ge.ank_id='$this->_ank_id' AND sa.datetime_started IS NOT NULL AND su.mode='geofence' "
                . "ORDER BY res.recnum DESC;";
        
        $podatki = array(array('identifier', 'recnum', 'geofence_id', 'geofence_name', 'triggered_timestamp', 'location_timestamp', 'latitude', 'longitude', 'provider', 'accuracy', 
            'altitude', 'bearing', 'speed', 'vertical_acc', 'speed_acc', 'bearing_acc', 'extras', 'is_mock'));

        $sqlQuery = sisplet_query($Query);

        if (mysqli_num_rows($sqlQuery)) {
            while ($row = mysqli_fetch_assoc($sqlQuery)) {
                $podatki[] = array($row['identifier'], $row['recnum'], $row['geof_id'], $row['name'], $row['triggered_timestamp'], $row['timestamp'], $row['lat'], $row['lng'], $row['provider'], 
                    $row['accuracy'], $row['altitude'], $row['bearing'], $row['speed'], $row['vertical_acc'], $row['speed_acc'], 
                    $row['bearing_acc'], $row['extras'], $row['is_mock']);
            }
        }
        return $podatki;
    }
    
    private function getDataEntryLocations() {
        $Query = "SELECT au.identifier, su.maza_user_id, su.srv_user_id, su.srv_version_datetime, su.mode, res.id, res.recnum, su.loc_id, ml.id "
                . ", ml.maza_user_id, ml.lat, ml.lng, ml.provider, ml.timestamp, ml.accuracy, ml.altitude, ml.bearing, ml.speed, ml.vertical_acc, ml.speed_acc, ml.bearing_acc, ml.extras, ml.is_mock"
                . " FROM maza_srv_users AS su "
                . "LEFT JOIN (SELECT ank_id, maza_user_id, datetime_started FROM maza_user_srv_access) AS sa ON su.maza_user_id = sa.maza_user_id "
                . "LEFT JOIN (SELECT id, identifier FROM maza_app_users) AS au ON au.id = su.maza_user_id "
                . "LEFT JOIN (SELECT * FROM maza_user_locations) AS ml ON su.maza_user_id = ml.maza_user_id AND su.loc_id = ml.id "
                . "LEFT JOIN (SELECT id, recnum FROM srv_user) AS res ON res.id = su.srv_user_id "
                . "WHERE sa.ank_id='$this->_ank_id' AND sa.datetime_started IS NOT NULL AND su.mode='entry' "
                . "ORDER BY res.recnum DESC;";

        $podatki = array(array('identifier', 'recnum', 'entry_timestamp', 'location_timestamp', 'latitude', 'longitude', 'provider', 'accuracy', 
            'altitude', 'bearing', 'speed', 'vertical_acc', 'speed_acc', 'bearing_acc', 'extras', 'is_mock'));
        
        $sqlQuery = sisplet_query($Query);

        if (mysqli_num_rows($sqlQuery)) {
            while ($row = mysqli_fetch_assoc($sqlQuery)) {
                $podatki[] = array($row['identifier'], $row['recnum'], $row['srv_version_datetime'], $row['timestamp'], $row['lat'], $row['lng'], $row['provider'], 
                    $row['accuracy'], $row['altitude'], $row['bearing'], $row['speed'], $row['vertical_acc'], $row['speed_acc'], 
                    $row['bearing_acc'], $row['extras'], $row['is_mock']);
            }
        }
        return $podatki;
    }
            
    private function getDataGeofences() {
        $Query = "SELECT * FROM maza_srv_geofences WHERE ank_id='$this->_ank_id' ORDER BY id;";

        $podatki = array(array('id', 'survey_id', 'geofence_on', 'center_latitude', 'center_longitude', 'radius', 'center_address', 'name', 
            'notification_title', 'notification_message', 'notification_sound', 'on_transition', 'after_seconds', 'location_after_triggered'));

        $sqlQuery = sisplet_query($Query);

        if (mysqli_num_rows($sqlQuery)) {
            while ($row = mysqli_fetch_assoc($sqlQuery)) {
                $podatki[] = array($row['id'], $row['ank_id'], $row['geofence_on'], $row['lat'], $row['lng'], $row['radius'], $row['address'], $row['name'], $row['notif_title'], 
                    $row['notif_message'], $row['notif_sound'], $row['on_transition'], $row['after_seconds'], $row['location_triggered']);
            }
        }

        return $podatki;
    }

    private function exportCSVTable($data, $name) {
        // Izvozimo CSV
        $fp = fopen('php://output', 'w');

        header('Content-Type: application/csv charset=UTF-8');
        header('Content-Disposition: attachement; filename="'.$name.'_' . time() .'_'. $this->_ank_id . '.csv";');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM

        foreach ($data as $row) {
            fputcsv($fp, $row, ',');
        }

        fclose($fp);
    }
    
    public function exportCSVIdentifiers(){
        $data = $this->getDataIdentifiers();
        $this->exportCSVTable($data, 'maza_identifiers');
    }
    
    /*public function exportCSVInactiveIdentifiers(){
        $data = $this->getDataInactiveIdentifiers();
        $this->exportCSVTable($data, 'maza_inactive');
    }
    
    public function exportCSVActiveIdentifiers(){
        $data = $this->getDataActiveIdentifiers();
        $this->exportCSVTable($data, 'maza_active');
    }*/
    
    public function exportCSVTrackingLocations(){
        $data = $this->getDataTrackingLocations();
        $this->exportCSVTable($data, 'maza_tracking_locations');
    }
    
    public function exportCSVTrackingAR(){
        $data = $this->getDataTrackingAR();
        $this->exportCSVTable($data, 'maza_tracking_ar');
    }
    
    public function exportCSVEntryLocations(){
        $data = $this->getDataEntryLocations();
        $this->exportCSVTable($data, 'maza_entry_locations');
    }
        
    public function exportCSVTriggeredGeofences(){
        $data = $this->getDataTriggeredGeofences();
        $this->exportCSVTable($data, 'maza_triggered_geofences');
    }
    
    public function exportCSVTriggeredGeofencesAnswered(){
        $data = $this->getDataTriggeredGeofencesAnswers();
        $this->exportCSVTable($data, 'maza_triggered_geofences_answers');
    }
    
    public function exportCSVGeofences(){
        $data = $this->getDataGeofences();
        $this->exportCSVTable($data, 'maza_geofences');
    }
    
    public function exportCSVAlarmRespondets(){
        $data = $this->getDataAlarmRespondents();
        $this->exportCSVTable($data, 'maza_alarm_respondets');
    }
}

?>