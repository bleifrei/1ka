<?php

/**
 *
 *  Class ki vsebuje funkcije potrebne za posiljanje vabil preko SQUALO
 *
 */

class SurveyInvitationsSqualo {

    private $anketa;

    private $squaloEnabled;
    private $squaloActive;


    public function __construct($anketa){
	
		$this->anketa = $anketa;

        // Preverimo ce je squalo omogocen na tej instalaciji in anketi
        $this->squaloEnabled = $this->checkSqualoEnabled() ? true : false;

        // Preverimo ce je squalo vklopljen na anketi
        $this->squaloActive = $this->checkSqualoActive() ? true : false;
    }


    public function getSqualoEnabled(){
        return $this->squaloEnabled;
    }

    public function getSqualoActive(){
        return $this->squaloActive;
    }

    // Preverimo ce je squalo omogocen na instalaciji
    private function checkSqualoEnabled(){
        global $mysql_database_name;
        global $admin_type;
        global $squalo_user;
        global $squalo_key;
  
        // Zaenkrat imajo squalo samo admini
        if($admin_type != 0)
            return false;

        // Squalo je omogocen samo na testu, www in virtualkah
        if($mysql_database_name != 'www1kasi' && $mysql_database_name != 'test1kasi' && $mysql_database_name != 'real1kasi')
            return false;

        // Zaenkrat imajo squalo samo admini
        if(!isset($squalo_user) || $squalo_user == '' || !isset($squalo_key) || $squalo_key == '')
            return false;

        return true;
    }

    // Preverimo ce je squalo vklopljen na anketi
    private function checkSqualoActive(){

        $vabila_type = SurveyInfo::getSurveyModules('email');

        // Vklopljen squalo
        if($vabila_type === '2'){
            return true;
        }

        return false;
    }


    // Izvedemo squalo pošiljanje (ustvarimo seznam, newsletter, pošljemo maile)
    public function sendSqualoInvitations($sql_recipients_query, $sending_data){
        global $global_user_id;
        global $site_url;


        // Preverimo ce je vklopljen modul za volitve
        $voting = SurveyInfo::getInstance()->checkSurveyModule('voting');
        
        // Ce mamo SEO
		$nice_url = SurveyInfo::getSurveyLink();

        // Ali imamo individualizirana vabila s kodo
        $surveySettings = SurveyInfo::getInstance()->getSurveyRow();
        $individual = (int)$surveySettings['individual_invitation'];

        # zakeširamo user_id za datapiping
        $arryDataPiping = array();
        $qryDataPiping = sisplet_query("SELECT id, inv_res_id FROM srv_user WHERE ank_id='$this->anketa' AND inv_res_id IS NOT NULL");
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
        $recipients = array();
        while ($sql_row = mysqli_fetch_assoc($sql_recipients_query)) {

            $password = $sql_row['password'];
                
            $email = $sql_row['email'];

            // Preverimo ce je duplikat
            if ($dont_send_duplicated == true && isset($duplicated[$email])) {
                $duplicated[$email] ++;
                continue;
            }
            
            $duplicated[$email] = 1;

            
            
            if ( ($individual  == 1 && trim($email) != '' && trim($password) != '') || ($individual == 0 && trim($email) != '') ){

                // odvisno ali imamo url za jezik.
                if ($sending_data['msg_url'] != null && trim($sending_data['msg_url']) != '' ) {
                    $url = $sending_data['msg_url'] . ($individual  == 1  ? '?code='.$password : '');
                } 
                else {
                    $url = $nice_url . ($individual  == 1  ? '&code='.$password : '');
                }

                $url .= '&ai='.(int)$sending_data['arch_id'];
                
                // odjava
                $unsubscribe = $site_url . 'admin/survey/unsubscribe.php?anketa=' . $this->anketa . '&code='.$password;

                $custom_attributes = array(
                    'url'           => '<a href="' . $url . '">' . $url . '</a>',
                    'urllink'       => $url,
                    'firstname'     => $sql_row['firstname'],
                    'lastname'      => $sql_row['lastname'],
                    //'email'         => $sql_row['email'],
                    'code'          => $sql_row['password'],
                    'password'      => $sql_row['password'],
                    'phone'         => $sql_row['phone'],
                    'custom'        => $sql_row['custom'],
                    'unsubscribe'   => $unsubscribe,
                );

                $recipients[] = array(
                    'email'             => $sql_row['email'],
                    'name'              => $sql_row['firstname'],
                    'surname'           => $sql_row['lastname'],
                    'custom_attributes' => $custom_attributes
                );       
                    
                $_user_data = $sql_row; 
                $send_users_data[] = $_user_data;

                $send_emails[] = $email;
                $send_ids[] = $sql_row['id'];
            }
        }

        // Ustvarimo squalo seznam z respondenti
        $list_name = $global_user_id.'_'.$this->anketa.'_'.$sending_data['arch_id'];       

        $list_id = $this->createList($list_name, $recipients);


        // Ce so vsi prejemniki ok dodani na seznam, ustvarimo mail in posljemo
        if($list_id != false)
            $squalo_sending_result = $this->sendEmail($sending_data['subject_text'], $sending_data['body_text'], $list_id, $sending_data['from_email'], $sending_data['from_name'], $sending_data['reply_to_email']);

 
        // Napaka pri squalo posiljanju oz. dodajanju - zabelezimo kot da ni noben ok poslan
        if(!$squalo_sending_result || !$list_id){

            $send_error = $send_emails;
            $send_error_ids = $send_ids;

            foreach($send_users_data as $key => $val){
                $val['status'] = 2;
                $send_users_data[$key] = $val;
            }
            
            $send_ok = array();
            $send_ok_ids = array();

            # updejtamo status za errorje
            if (count($send_error_ids) > 0) {

                $sqlQuery = sisplet_query("UPDATE srv_invitations_recipients SET last_status = GREATEST(last_status,2) WHERE id IN (".implode(',',$send_error_ids).") AND last_status IN ('0')");
                if (!$sqlQuery) {
                    $error = mysqli_error($GLOBALS['connect_db']);
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


        // Ok squalo posiljanje - zabelezimo da so bili vsi ok poslani
        $send_ok = $send_emails;
        $send_ok_ids = $send_ids;

        foreach($send_users_data as $key => $val){
            $val['status'] = 1;
            $send_users_data[$key] = $val;
        }

        $send_error = array();
        $send_error_ids = array();

        // updejtamo userja da mu je bilo poslano - SQUALO je vedno vse ok
        if (count($send_ok_ids) > 0) {
                    
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
                                        WHERE id IN (".implode(',',$send_ok_ids).") AND sent='1' AND last_status='1' AND ank_id='".$this->anketa."'
                                    ");
                if (!$sqlQuery) {
                    $error = mysqli_error($GLOBALS['connect_db']);
                }
            }
        }
               

        // če mamo personalizirana email vabila, userje dodamo v bazo
        if ($individual == 1) {
                       
            if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
			    $db_table = '_active';

            $inv_variables_link = array('email'=>'email','geslo'=>'password','ime'=>'firstname','priimek'=>'lastname','naziv'=>'salutation','telefon'=>'phone','drugo'=>'custom','odnos'=>'relation','last_status'=>'last_status','sent'=>'sent','responded'=>'responded','unsubscribed'=>'unsubscribed');
                
            $sys_vars = $this->getSystemVars();

            foreach($send_users_data as $_user_data){
 
                // dodamo še userja v srv_user da je kompatibilno s staro logiko
                $strInsertDataText = array();
                $strInsertDataVrednost = array();

                // Pri volitvah zaradi anonimizacije ignoriramo vse identifikatorje
                if($voting){
                    $_r = sisplet_query("INSERT INTO srv_user 
                                            (ank_id, cookie, pass, last_status, inv_res_id) 
                                            VALUES 
                                            ('".$this->anketa."', '".$_user_data['cookie']."', '".$_user_data['password']."', '".$_user_data['status']."', '-1') ON DUPLICATE KEY UPDATE cookie = '".$_user_data['cookie']."', pass='".$_user_data['password']."'
                                        ");

                    // Ce ne belezimo parapodatka za cas responsa, anonimno zabelezimo cas zadnjega responsa
                    sisplet_query("UPDATE srv_anketa SET last_response_time=NOW() WHERE id='".$this->anketa."'");
                }
                else{
                    $_r = sisplet_query("INSERT INTO srv_user 
                                            (ank_id, email, cookie, pass, last_status, time_insert, inv_res_id) 
                                            VALUES 
                                            ('".$this->anketa."', '".$_user_data['email']."', '".$_user_data['cookie']."', '".$_user_data['password']."', '".$_user_data['status']."', NOW(), '".$_user_data['id']."') ON DUPLICATE KEY UPDATE cookie = '".$_user_data['cookie']."', pass='".$_user_data['password']."'
                                        ");
                }
                $usr_id = mysqli_insert_id($GLOBALS['connect_db']);

                if ($usr_id) {

                    // dodamo še srv_userbase in srv userstatus
                    sisplet_query("INSERT INTO srv_userbase (usr_id, tip, datetime, admin_id) VALUES ('".$usr_id."','0',NOW(),'".$global_user_id."')");
                    sisplet_query("INSERT INTO srv_userstatus (usr_id, tip, status, datetime) VALUES ('".$usr_id."', '0', '0', NOW())");
                        
                    

                    // dodamo še podatke za posameznega userja za sistemske spremenljivke
                    foreach ($sys_vars AS $sid => $spremenljivka) {
                        
                        $_user_variable = $inv_variables_link[$spremenljivka['variable']];
                        
                        if (trim($_user_data[$_user_variable]) != '' && $_user_data[$_user_variable] != null) {
                            if($spremenljivka['variable'] == 'odnos')
                                $strInsertDataVrednost[] = "('".$sid."','".$spremenljivka['vre_id'][trim($_user_data[$_user_variable])]."','".$usr_id."')";
                            else
                                $strInsertDataText[] = "('".$sid."','".$spremenljivka['vre_id']."','".trim($_user_data[$_user_variable])."','".$usr_id."')";
                        }
                    }
                }             
                            
                // Pri volitvah zaradi anonimizacije ne vsatvimo nicesar v sistemske spremenljivke
                if(!$voting){
                    
                    // vstavimo v srv_data_text
                    if (count($strInsertDataText) > 0) {
                        $strInsert = "INSERT INTO srv_data_text".$db_table." (spr_id, vre_id, text, usr_id) VALUES ";
                        $strInsert .= implode(',',$strInsertDataText);
                        sisplet_query($strInsert);
                    }
                    // vstavimo v srv_data_vrednost
                    if (count($strInsertDataVrednost) > 0) {
                        $strInsert = "INSERT INTO srv_data_vrednost".$db_table." (spr_id, vre_id, usr_id) VALUES ";
                        $strInsert .= implode(',',$strInsertDataVrednost);
                        sisplet_query($strInsert);
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


    // Ustvarimo nov seznam in nanj dodamo respondente
    private function createList($list_name, $recipients){

        $squalo_api = new SqualoApi();

        // Ustvarimo prazen seznam
        $list_id = $squalo_api->createList($list_name);

        // Napaka pri ustvarjanju seznama
        if($list_id == '0'){
            echo 'Napaka pri ustvarjanju Squalo seznama.';
            return false;
        }

        // Dodamo respondente na ta seznam
        foreach($recipients as $recipient){

            $email = $recipient['email'];

            $custom_attributes = $recipient['custom_attributes'];

            $recipient_id = $squalo_api->addRecipient($email, $list_id, $custom_attributes);

            // Napaka pri ustvarjanju seznama
            if($recipient_id == '0'){
                echo 'Napaka pri dodajanju prejemnika '.$email.' na Squalo seznam.';
                return false;
            }
        }

        return $list_id;
    }

    // Ustvarimo nov email z vsebino (api klic createNewsletter) in posljemo na vse naslove v seznamu
    private function sendEmail($subject, $body, $list_id, $from_email, $from_name, $reply_to_email){

        $squalo_api = new SqualoApi();

        // Zamenjamo datapiping npr. #URL# -> {subtag:url}...
        $subject = self::squaloDatapiping($subject);
        $body = self::squaloDatapiping($body);

        $language = 'sl';

        // Api klic za ustvarjanje emaila
        $newsletter_id = $squalo_api->createNewsletter($list_id, $subject, $body, $body, $from_email, $from_name, $reply_to_email, $language='sl');

        // Napaka pri ustvarjanju newsletterja
        if($newsletter_id == '0'){
            echo 'Napaka pri ustvarjanju Squalo newsletterja.';
            return false;
        }

        // Api klic za posiljanje na naslove
        $result = $squalo_api->sendEmails($newsletter_id);

        // Napaka pri ustvarjanju newsletterja
        if(!$result['success']){
            echo 'Napaka pri pošiljanju emailov za Squalo newsletter '.$newsletter_id.'.';
            return false;
        }

        return $result;
    }


    private static function squaloDatapiping($text){

        $text_fixed = str_replace(
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
                    '{subtag:url}',
                    '{subtag:urllink}',
                    '{subtag:unsubscribe}',
                    '{subtag:firstname}',
                    '{subtag:lastname}',
                    '{subtag:email}',
                    '{subtag:code}',
                    '{subtag:password}',
                    '{subtag:phone}',
                    '{subtag:salutation}',
                    '{subtag:custom}'
            ),
            $text
        );

        return $text_fixed;
    }

    private function getSystemVars(){

        $inv_variables = array('email','password','ime','priimek','naziv','telefon','drugo','odnos');

        // polovimo sistemske spremenljivke z vrednostmi
        $qrySistemske = sisplet_query("SELECT s.id, s.naslov, s.variable 
                                        FROM srv_spremenljivka s, srv_grupa g 
                                        WHERE s.sistem='1' AND s.gru_id=g.id AND g.ank_id='".$this->anketa."' AND variable IN ("."'" . implode("','",$inv_variables)."')
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
}