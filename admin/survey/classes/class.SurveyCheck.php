<?php

/* 
 * Preverjanje ankete - limiti velikosti, vabil, preverjanmje phishinga...
 * 
 * Zaenkrat samo preverjamo in posljemo mail adminu
 * 
 */

class SurveyCheck {
	

    var $anketa;

    public function __construct($anketa){

        if($anketa == null || $anketa <= 0)
            return 'ID ankete ne obstaja!';

        $this->anketa = $anketa;
    }


    // Preverimo stevilo vprasanj v anketi
    public function checkLimitSpremenljivke(){

        // Ce limit ni nastavljen ignoriramo
        if(!AppSettings::getInstance()->getSetting('app_limits-question_count_limit'))
            return true;

        // Dobimo stevilo vprasanj v anketi
        $stevilo_vprasanj = SurveyInfo::getInstance()->getSurveyQuestionCount();

        // Obvestilo (mail adminu) posljemo pri dosezeni stevilki
        if($stevilo_vprasanj == AppSettings::getInstance()->getSetting('app_limits-question_count_limit')){
            $this->sendAlert($alert_type='limit_spremenljivke', $stevilo_vprasanj);
        }

        // Ce je v anketi ze vec vprasanj kot je limit
        if($stevilo_vprasanj > AppSettings::getInstance()->getSetting('app_limits-question_count_limit')){
            return true;
        }
        else{
            return false;
        }
    }

    // Preverimo stevilo poslanih vabil
    public function checkLimitVabila(){

        // Ce limit ni nastavljen ignoriramo
        if(!AppSettings::getInstance()->getSetting('app_limits-invitation_count_limit'))
            return true;

        // Prestejemo poslana vabila
        $sql = sisplet_query("SELECT count(id) AS stevilo_vabil
                                FROM srv_invitations_recipients
                                WHERE ank_id='".$this->anketa."' AND sent='1' 
                            ");
        $row = mysqli_fetch_array($sql);

        $stevilo_vabil = $row['stevilo_vabil'];

        // Obvestilo (mail adminu) posljemo pri dosezeni stevilki
        if($stevilo_vabil == AppSettings::getInstance()->getSetting('app_limits-invitation_count_limit')){
            $this->sendAlert($alert_type='limit_vabila', $stevilo_vabil);
        }

        // Ce je poslanih ze vec vabil kot je limit
        if($stevilo_vabil > AppSettings::getInstance()->getSetting('app_limits-invitation_count_limit')){
            return true;
        }
        else{
            return false;
        }
    }

    // Preverimo stevilo responsov na anketo
    public function checkLimitResponses(){

        // Ce limit ni nastavljen ignoriramo
        if(!AppSettings::getInstance()->getSetting('app_limits-response_count_limit'))
            return true;

        // Dobimo stevilo odgovorov na anketo
        $stevilo_odgovorov = SurveyInfo::getInstance()->getSurveyAnswersCount();

        // Obvestilo (mail adminu) posljemo pri dosezeni stevilki
        if($stevilo_odgovorov == AppSettings::getInstance()->getSetting('app_limits-response_count_limit')){
            $this->sendAlert($alert_type='limit_responses', $stevilo_odgovorov);

            // Deaktiviramo anketo, ce je aktivna ?
        }

        // Ce je na anketo ze vec responsov kot je limit
        if($stevilo_odgovorov > AppSettings::getInstance()->getSetting('app_limits-response_count_limit')){
            return true;
        }
        else{
            return false;
        }
    }

    // Preverimo ce je anketa potencialno phishing
    public function checkPhishing(){
        global $global_user_id;


        // Dobimo stevilo vprasanj v anketi
        $stevilo_vprasanj = SurveyInfo::getInstance()->getSurveyQuestionCount();

        // Ce imamo v anketi 0 ali vec kot 5 vprasanj je vse ok
        if($stevilo_vprasanj >= 5 || $stevilo_vprasanj == 0){
            return false;
        }


        // Dobimo stevilo anket uporabnika
        $sqlA = sisplet_query("SELECT count(id) AS count_surveys FROM srv_anketa WHERE insert_uid='".$global_user_id."'");
        $rowA = mysqli_fetch_array($sqlA);

        // Ce ima uporabnik ze vec anket je vse ok
        if($rowA['count_surveys'] > 1){
            return false;
        }


        // Prestejemo vprasanja po tipu
        $sql = sisplet_query("SELECT count(s.id) AS count_questions
                                FROM srv_spremenljivka s, srv_grupa g
                                WHERE g.ank_id='".$this->anketa."' AND g.id=s.gru_id 
                                    AND (tip='21' OR tip='5') 
                            ");
        $row = mysqli_fetch_array($sql);

        // Ce imamo v anketi manj kot 5 vprasanj in so vsa tipa nagovor ali text je potencialen phishing
        if($row['count_questions'] == $stevilo_vprasanj){
            
            // Posljemo mail adminu
            $this->sendAlert($alert_type='phishing');
            
            return true;
        }
        else{
            return false;
        }
    }

    // Pri izpolnjevanju ankete preverimo stevilo klikov na minuto - ce jih je prevec, respondenta zavrnemo, drugace se lahko sql zafila in streznik ni vec odziven
    public function checkClicksPerMinute(){

        // Ce maximum na minuto ni nastavljen ignoriramo limit
        if(!AppSettings::getInstance()->getSetting('app_limits-clicks_per_minute_limit'))
            return true;

        // Preverimo ce gre za izpolnjevanje ankete
        if($_SERVER["SCRIPT_NAME"] != '/main/survey/index.php')
            return true;

        // Preverimo ce gre za prvi prihod na doloceno stran ankete in ne na prvo stran
        if(isset($_GET['grupa']))
            return true;

        // Preverimo ce je id ankete ustrezno nastavljen
        if(!isset($this->anketa) || $this->anketa <= 0)
            return true;


        $click_time = time();

        $sql = sisplet_query("SELECT click_count, click_time FROM srv_clicks WHERE ank_id='".$this->anketa."'");
        if (mysqli_num_rows($sql) > 0) {

            list($click_count, $first_click_time) = mysqli_fetch_array($sql);

            // Ce nismo znotraj minute vse resetiramo in pustimo naprej
            if($click_time - $first_click_time > 60){
                $sqlI = sisplet_query("UPDATE srv_clicks SET click_count='1', click_time='".$click_time."' WHERE ank_id='".$this->anketa."'");
                return true;
            }

            // Click count je ok - pustimo naprej
            if($click_count <= AppSettings::getInstance()->getSetting('app_limits-clicks_per_minute_limit')){
                $sqlI = sisplet_query("UPDATE srv_clicks SET click_count=click_count+1 WHERE ank_id='".$this->anketa."'");      
                
                // Dosegli smo limit - posljemo mail adminu
                if($click_count == AppSettings::getInstance()->getSetting('app_limits-clicks_per_minute_limit')){

                    // Includamo vse da lahko posljemo mail
                    include_once('../../vendor/autoload.php');
            
                    // Posljemo mail adminu
                    $this->sendAlert($alert_type='limit_clicks', $click_count);
                }
                
                return true;
            }
            // Click count je previsok - ZAVRNEMO
            else{
                // Prikazemo error stran ki jo refreshamo na 5 sekund
                $this->displayClicksPerMinuteError();

                return false;
            }
        }
        else{
            $sqlI = sisplet_query("INSERT INTO srv_clicks (ank_id, click_count, click_time) VALUES ('".$this->anketa."', '1', '".$click_time."')");
        }
        
        return true;
    }


    // Posljemo obvestilo adminu o prebitem limitu, phishing anketi...
    private function sendAlert($alert_type, $count=0){
        global $site_url;

        // Alerta ne posljemo na lastnih instalacijah
        if(isLastnaInstalacija())
            return;

        // Dobimo hash ankete
        $anketa_hash = SurveyInfo::getInstance()->getSurveyColumn('hash');

        switch($alert_type){

            case 'limit_spremenljivke':
                $title = 'Opozorilo - dosežena omejitev vprašanj';
                $content = '<a href="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'">Anketa '.$this->anketa.'</a> ima doseženo omejitev števila vprašanj ('.$count.')!';

                break;

            case 'limit_responses':
                $title = 'Opozorilo - dosežena omejitev odgovorov';
                $content = '<a href="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'">Anketa '.$this->anketa.'</a> ima doseženo omejitev števila odgovorov ('.$count.')!';

                break;

            case 'limit_vabila':
                $title = 'Opozorilo - dosežena omejitev vabil';
                $content = '<a href="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'">Anketa '.$this->anketa.'</a> ima doseženo omejitev poslanih vabil ('.$count.')!';

                break;

            case 'phishing':
                $title = 'Opozorilo - potencialna phishing anketa';
                $content = '<a href="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'">Anketa '.$this->anketa.'</a> - potencialen phishing!';

                break;

            case 'limit_clicks':
                $title = 'Opozorilo - dosežena omejitev klikov na minuto';
                $content = '<a href="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'">Anketa '.$this->anketa.'</a> ima doseženo omejitev klikov na minuto ('.$count.')!';

                break;
        }

        // Dodamo se link do predogleda 
        $content .= '<br><br>Predogled ankete: <a href="'.$site_url.'a/'.$anketa_hash.'&preview=on">'.$site_url.'a/'.$anketa_hash.'&preview=on</a>';

        try{
            $MA = new MailAdapter($anketa=null, $type='admin');
            //$MA->addRecipients('peter.hrvatin@gmail.com');
            $MA->addRecipients('info@1ka.si');
            $resultX = $MA->sendMail($content, $title);
        }
        catch (Exception $e){
        }

        // Zalogiramo opozorilo
        $SL = new SurveyLog();
        $SL->addMessage(SurveyLog::ERROR, $title.' - anketa '.$this->anketa);
        $SL->write();
    }

    // Prikazemo stran z errorjem za presezeno stevilo klikov na minuto
    private function displayClicksPerMinuteError(){
        global $site_url;

        $refresh_every = 5;

        echo '<!DOCTYPE html>';
        echo '<html>';

        echo '<head>';
        echo '    <title>Server Limit Reached</title>';
        echo '    <meta http-equiv="refresh" content="'.$refresh_every.'" />';
        echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0" />';
        
        echo '    <style>
            body{
                display: flex;
                align-content: center;
                height: 90vh;
                
                flex-wrap: wrap;
                align-content: center;
            }
            .main{
                max-width: 1200px;
                margin: 50px auto;
                padding: 0 20px;

                font-family: Montserrat, Arial, Sans-Serif !important;
                color: #505050;
            }
            h1{
                color: #1e88e5;
                text-align: center;
                margin: 30px 0;
            }
            hr{
                margin: 50px 0;

                border: 0;
                border-top: 1px solid #ddeffd;
            }
            .loading{
                margin: 50px 0;
                text-align: center;
            }
            img{
                width: 80px;
                height: 80px;
            }
        </style>';
        echo '</head>';

        echo '<body><div class="main">';
        echo '    <div class="loading"><img src="'.$site_url.'/public/img/icons/spinner.gif" /></div>';
        echo '    <h1>Dosežena omejitev strežnika</h1>';
        echo '    <h3>Prosimo, počakajte nekaj trenutkov. Trenutno je doseženo maksimalno število vnosov ankete na minuto.</h3>';
        echo '    <hr>';
        echo '    <h1>Server Limit Reached</h1>';
        echo '    <h3>Please wait a few moments. Currently, the maximum number of survey entries per minute has been reached.</h3>';
        echo '</div></body>';

        echo '</html>';
        
        die();
    }
}

?>