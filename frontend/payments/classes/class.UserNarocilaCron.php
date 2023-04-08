<?php

/**
 *
 *  Class ki skrbi za opomnike trgoovine (cronjob)
 *
 *  Cron jobi nej bodo 3. (ob 9h zjutraj) 
 *  1. 6 dni prej k zgenerira predračun. Vsebina v smislu: vaša naročnina se bo kmalu iztekla. Zagotovite si neomejeno uporabo 2ka/3ka še naprej s plačilom predračuna v priponki. 
 *  2. 1 dan pred potekom. Vsebina v smislu: Danes je zadnji dan veljavnosti vašega paketa. Zagotovite si delovanje 1ka še naprej ...
 *  3. En dan po poteku. Vsebina v smislu: Vaša naročnina se je iztekla. Nov paket lahko naročite na 1ka.si
 *
*/


class UserNarocilaCron{


    public function __construct(){
        global $app_settings;

    }


    /**
	* izvede ustrezno akcijo
	*/
	public function executeAction($action = '') {
		
		// Izvedemo ustrezno akcijo	
		if(method_exists('UserNarocilaCron', $action) && $action != '')
			$this->$action();
		else
			echo 'Method '.$action.' does not exist!';
	}


    // Mail 6 dni pred potekom narocnine
    private function notifyIn6(){

        $expired_users = $this->getExpiredUsers($expire='in6');

        foreach($expired_users as $usr_id => $user){

            $narocilo = $this->getLastNarocilo($usr_id);

            // Nastavimo ustrezen jezik - mail mora biti v istem jeziku kot je bilo zadnje narocilo
            if($narocilo['language'] == 'en')
                include('../lang/2.php');
            else
                include('../lang/1.php');


            // Posljemo mail
            $subject = $lang['srv_access_expire_in6_subject'];  
            $content = str_replace('#PACKAGE_NAME#', $narocilo['package_id'].'ka', $lang['srv_access_expire_in6_content1']);
            $content .= str_replace('#PACKAGE_ID#', $narocilo['package_id'], $lang['srv_access_expire_in6_content2']);
            $content .= $lang['srv_access_expire_in6_content3'] . $user['email'];

            // Podpis
            $signature = Common::getEmailSignature();
            $content .= $signature;

            try{
                $MA = new MailAdapter();
                $MA->addRecipients($user['email']);
                $resultX = $MA->sendMail($content, $subject);
            }
            catch (Exception $e){
            }
        }
        
    }

    // Mail 1 dan pred potekom narocnine
    private function notifyIn1(){

        $expired_users = $this->getExpiredUsers($expire='in1');

        foreach($expired_users as $usr_id => $user){

            $narocilo = $this->getLastNarocilo($usr_id);

            // Nastavimo ustrezen jezik - mail mora biti v istem jeziku kot je bilo zadnje narocilo
            if($narocilo['language'] == 'en')
                include('../lang/2.php');
            else
                include('../lang/1.php');

                
            // Posljemo mail
            $subject = $lang['srv_access_expire_in1_subject'];  
            $content = str_replace('#PACKAGE_NAME#', $narocilo['package_id'].'ka', $lang['srv_access_expire_in1_content1']);
            $content .= str_replace('#PACKAGE_ID#', $narocilo['package_id'], $lang['srv_access_expire_in1_content2']);
            $content .= $lang['srv_access_expire_in1_content3'] . $user['email'];

            // Podpis
            $signature = Common::getEmailSignature();
            $content .= $signature;

            try{
                $MA = new MailAdapter();
                $MA->addRecipients($user['email']);
                $resultX = $MA->sendMail($content, $subject);
            }
            catch (Exception $e){
            }
        }
    }

    // Mail 1 dan po poteku narocnine
    private function notifyExpired(){

        $expired_users = $this->getExpiredUsers($expire='expired');

        foreach($expired_users as $usr_id => $user){

            $narocilo = $this->getLastNarocilo($usr_id);

            // Nastavimo ustrezen jezik - mail mora biti v istem jeziku kot je bilo zadnje narocilo
            if($narocilo['language'] == 'en')
                include('../lang/2.php');
            else
                include('../lang/1.php');

                
            // Posljemo mail
            $subject = $lang['srv_access_expire_expired_subject'];  
            $content = str_replace('#PACKAGE_NAME#', $narocilo['package_id'].'ka', $lang['srv_access_expire_expired_content1']);
            $content .= str_replace('#PACKAGE_ID#', $narocilo['package_id'], $lang['srv_access_expire_expired_content2']);
            $content .= $lang['srv_access_expire_expired_content3'] . $user['email'];

            // Podpis
            $signature = Common::getEmailSignature();
            $content .= $signature;

            try{
                $MA = new MailAdapter();
                $MA->addRecipients($user['email']);
                $resultX = $MA->sendMail($content, $subject);
            }
            catch (Exception $e){
            }
        }
    }



    // Dobimo seznam uporabnikov, ki jim potece paket na dolocen dan
    private function getExpiredUsers($expire){

        if($expire == 'in6'){
            $interval_query = 'DATE(time_expire) = DATE(NOW() + INTERVAL 6 DAY)';
        }
        elseif($expire == 'in1'){
            $interval_query = 'DATE(time_expire) = DATE(NOW() + INTERVAL 1 DAY)';
        }
        if($expire == 'expired'){
            $interval_query = 'DATE(time_expire) = DATE(NOW() - INTERVAL 1 DAY)';
        }
        
        $result = array();

        // Loop po vseh uporabnikih, ki imajo zakupljen paket in jim potece cez 6 dni
        $sqlAccess = sisplet_query("SELECT a.*, u.email 
                                        FROM user_access a, users u 
                                        WHERE (a.package_id = 2 OR a.package_id = 3) 
                                            AND ".$interval_query."
                                            AND u.id=a.usr_id
                                    ");

        while($rowAccess = mysqli_fetch_array($sqlAccess)){
            $result[$rowAccess['usr_id']] = $rowAccess;
        }

        return $result;
    }

    // Dobimo zadnji placan paket uporabnika
    private function getLastNarocilo($usr_id){

        // Dobimo podatke zadnjega placanega narocila za tega uporabnika
        $sqlNarocilo = sisplet_query("SELECT * 
                                        FROM user_access_narocilo 
                                        WHERE usr_id='".$usr_id."' AND status='1'
                                        ORDER BY time DESC
                                        LIMIT 1
                                    ");

        // Uporabnik nima nobenega placanega paketa
        if(mysqli_num_rows($sqlNarocilo) == 0){
            return false;
        }

        $rowNarocilo = mysqli_fetch_array($sqlNarocilo);

        return $rowNarocilo;
    }
}