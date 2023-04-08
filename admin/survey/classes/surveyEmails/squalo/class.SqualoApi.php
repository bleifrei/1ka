<?php

/**
 *
 *  Class ki vsebuje funkcije Squalo APIJA (dodajanje prejemnikov, posiljanje...)
 *
 */

class SqualoApi {

    // Squalo api url
    var $api_url = 'https://api.squalomail.com/v1/';   


    public function __construct(){

    }


    private function executeCall($action, $method, $data){

        // Add credentials
        $data['apiUser'] = AppSettings::getInstance()->getSetting('squalo-user');
        $data['apiKey'] = AppSettings::getInstance()->getSetting('squalo-key');

        // GET call - set url params
        if($method == 'GET'){
            $response = $this->executeGET($action, $data);
        }
        // POST call
        else{
            $response = $this->executePOST($action, $data);
        }

        // Decode json response
        $response_array = json_decode($response, true);

        // Zalogiramo kaj se je dogajalo
        $SL = new SurveyLog();

        // Error
        if($response_array['errorCode'] != '0'){
            $result['error'] = $response_array["errorMessage"]. ' (code '.$response_array["errorCode"].')';
            $result['success'] = false;

            $SL->addMessage(SurveyLog::MAILER, "NAPAKA pri SQUALO API klicu ('.$action.')! ".$result['error']);
        }
        else{
            $result = $response_array;
            $result['success'] = true;

            $SL->addMessage(SurveyLog::MAILER, "USPEŠEN SQUALO API klic ('.$action.').");
        }
        
        $SL->write();

        return $result;
    }

    // Izvedemo post klic
    private function executePOST($action, $data){

        // Nastavimo url
        $url = $this->api_url.$action;


        // Init curl
        $ch = curl_init($url);

        // JSON string za POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // for debug only!
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $headers = array(
            "Content-Type: application/json",
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


        // Izvedemo klic
        $response = curl_exec($ch);
        curl_close($ch);


        return $response;
    }

    // Izvedemo get klic
    private function executeGET($action, $data){
            
        // GET params
        $params = '?';
        foreach($data as $name => $value){
            $params .= $name.'='.$value.'&';
        }
        $params = substr($params, 0, -1);

        // Nastavimo celoten url s parametri
        $url = $this->api_url.$action.$params;


        // Init curl
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);


        // Izvedemo klic
        $response = curl_exec($ch);

        return $response;
    }
        
     
    /* 
        Ustvarimo seznam uporabnikov za pošiljanje
        create-list

        "name":"String content",
        "description":"String content",
        "listTag":"String content",
        "color":"String content",
        "ordering":2147483647,
        "published":true
        
        {subtag:name}
        {subtag:code}
        {subtag:url}
        
        {date:4}

        {unsubscribe}{/unsubscribe}
    */
    public function createList($list_name){

        $action = 'create-list';
        $method = 'POST';

        $data = array(
            'name'  => $list_name,
            'ordering'  => $list_number,
            'published' => true
        );
       
        $response = $this->executeCall($action, $method, $data);

        $list_id = ($response['success']) ? $response['list']['id'] : '0';

        return $list_id;
    }

    /* 
        Ustvarimo email,ki se bo poslal

        "altBody":"String content",
        "body":"String content",
        "fromEmail":"String content",
        "fromName":"String content",
        "language":"String content",
        "listIds":[2147483647],
        "published":2147483647,
        "replyToEmail":"String content",
        "replyToName":"String content",
        "subject":"String content",
        "visible":true
    */
    public function createNewsletter($list_id, $subject, $body, $body_alt, $from_email, $from_name, $reply_to_email, $language){
        
        $action = 'create-newsletter';
        $method = 'POST';

        $data = array(
            'listIds'       => array($list_id),
            'subject'       => $subject,
            'body'          => $body,
            'altBody'       => $body_alt,
            'fromEmail'     => $from_email,
            'fromName'      => $from_name,
            'replyToEmail'  => $reply_to_email,
            'language'      => $language,
            'visible'       => true
        );

        $response = $this->executeCall($action, $method, $data);

        $newsletter_id = ($response['success']) ? $response['newsletter']['id'] : '0';

        return $newsletter_id;
    }

    /* 
        Dodamo prejemnika

        "accept": true,
        "confirmed": true,
        "customAttributes": [{
                "name": "firstname",
                "value": "John"
            },
            {
                "name": "lastname",
                "value": "Smith"
            }
        ],
        "email": "String content",
        "enabled": true,
        "html": true,
        "listIds": [2147483647],
        "name": "String content",
        "surname": "String content",
        "gender": "null | male | female | other",
        "gdprCanSend": true,
        "gdprCanTrack": true
    */
    public function addRecipient($email, $list_id, $custom_attributes=array()){

        $action = 'create-recipient';
        $method = 'POST';

        // Pretvorimo atribute po meri v pravo obliko
        $custom_attributes_squalo = array();
        $i = 0;
        foreach($custom_attributes as $key => $value){
            
            $custom_attributes_squalo[$i] = array(
                "name" => $key,
                "value" => $value
            );

            $i++;
        }

        $data = array(
            'email'         => $email,
            'listIds'       => array($list_id),
            'accept'        => true,
            'confirmed'     => true,
            'enabled'       => true,
            'gdprCanSend'   => true,
            'gdprCanTrack'  => true,
            'customAttributes'  => $custom_attributes_squalo
        );

        $response = $this->executeCall($action, $method, $data);

        $recipient_id = ($response['success']) ? $response['recipient']['id'] : '0';

        return $recipient_id;
    }

    /* 
        Pošljemo emaile

        "newsletterId":2147483647,
        "sendDate":2147483647
    */
    public function sendEmails($newsletter_id){

        $action = 'send-newsletter';
        $method = 'GET';

        $data = array(
            'newsletterId'  => $newsletter_id,
            'sendDate'  => time()+30
        );

        $response = $this->executeCall($action, $method, $data);

        return $response;
    }


    // Pobrisemo prejemnika
    public function deleteRecipient($recipient_id){

        $action = 'delete-recipient';
        $method = 'GET';

        $data = array(
            'recipientId'  => $recipient_id
        );

        $response = $this->executeCall($action, $method, $data);

        return $response;
    }
    
}