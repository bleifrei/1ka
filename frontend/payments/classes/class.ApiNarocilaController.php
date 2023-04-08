<?php


/**
 *
 *  Class ki vsebuje funkcije APIJA za narocila (oddaj narocilo, izvedi placilo...)
 *
 */
use GeoIp2\Database\Reader;

class ApiNarocilaController{
    
    
    private $private_key = 'NLFYb67/[pUE%W-s';	// Kljuc za preverjanje tokena
    
	private $params;	// Parametri v url-ju
    private $data;		// Podatki poslani preko post-a
    
    private $response = array();    // Response, ki ga vrnemo v json formatu
    
    
	function __construct(){		

		// Preberemo poslane podatke
		$this->processCall();	
        

        // Preverimo, ce je klic ok (token)
        if($this->checkToken()){

		    // Izvedemo akcijo
            $this->executeAction();
        }


        // Logiramo response klica
        $SL = new SurveyLog();

        if($this->response['success'] == true){

            if(isset($this->data['email']))
                $call_data = ', '.$this->data['email'];
            elseif(isset($this->data['narocilo_id']))
                $call_data = ', '.$this->data['narocilo_id'];
            else
                $call_data = '';

            $SL->addMessage(SurveyLog::PAYMENT, "USPEŠEN KLIC (".$this->params['action'] . $call_data.")");
        }
        else{
            $SL->addMessage(SurveyLog::PAYMENT, "NAPAKA pri klicu za plačevanje ".$this->params['action'].": ".$this->response['error']);
        }
            
        $SL->write();


        // Vrnemo json objekt responsa
        $this->processReturn();
	}
	
	
	// Preberemo poslane podatke (ce posiljamo preko curl)
	private function processCall(){

        // Metoda - POST, GET, DELETE...
        $this->method = $_SERVER['REQUEST_METHOD'];

        // Get parametri
        $this->params = $_GET;
        
		// Preberemo podatke iz post-a
        $this->data = json_decode(file_get_contents('php://input'), true);

        if(is_null($this->data)){
            $this->data = $_POST;
        }
    }

    private function checkToken(){
        $raw_post_data = '';

        if($this->method == 'POST' && $this->data){
            $raw_post_data = http_build_query($this->data);
        }
        
        // Dobimo request (brez id in token)
        $request_url = ($_SERVER["HTTPS"] == 'on') ? 'https://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"] : 'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];

        if(!isset($_SERVER['HTTP_IDENTIFIER']))
            $request_url = preg_replace('/([?&])identifier=[^&]+(&|$)/', '$1', $request_url);

        if(!isset($_SERVER['HTTP_TOKEN']))
            $request_url = preg_replace('/([?&])token=[^&]+(&|$)/', '$1', $request_url);

        if(!isset($_SERVER['HTTP_TOKEN']) || !isset($_SERVER['HTTP_IDENTIFIER']))
            $request_url = substr($request_url, 0, -1);
        
        // Na nasi strani naredimo hmac podatkov z ustreznim private key-em
        $data = $this->method . $request_url . $raw_post_data;
        $token = hash_hmac('sha256', $data, $this->private_key);
                        
        if($this->params['token'] == $token)
            return true;
        else{
            $this->response['error'] = 'Napaka! Napačen token.';
            $this->response['success'] = false;
            
            return false;
        }
    }
    

    // Preveri ce je user ze logiran v 1ko in nastavi globalne spremenljivke in cookie (kopirano iz function.php)
    private function executeAction(){
        global $lang;
        
        if (!isset($this->params['action'])) {
            $this->response['error'] = 'Napaka! Manjkajo parametri!';
            $this->response['success'] = false;
        } 
        else {
            
            // Vedno nastavimo ustrezni jezik (npr. za emaile) ce imamo parameter
            $language = isset($this->data['lang']) ? $this->data['lang'] : 'sl';
            if($language == 'en'){
                include('../../lang/2.php');
            }

            switch ($this->params['action']) {


                // Ustvari novo narocilo
                case 'create_narocilo':
                    $narocilo = new UserNarocila();
                    $this->response = $narocilo->createNarocilo($this->data);

                    break;


                // Posodobi obstoječe narocilo (npr. nastavi nacin placila)
                case 'update_narocilo':
                    $narocilo = new UserNarocila();
                    $this->response = $narocilo->updateNarocilo($this->data);

                    break; 


                // Dobi podatke zadnjega narocila za uporabnika
                case 'get_last_narocilo':

                    $usr_id = 0;

                    // Dobimo user id iz emaila
                    if(isset($this->data['email'])){
                        $sqlU = sisplet_query("SELECT id FROM users WHERE email='".$this->data['email']."'");
                        $rowU = mysqli_fetch_array($sqlU);
                        
                        $usr_id = $rowU['id'];
                    }

                    if($usr_id == '' || $usr_id == 0){
                        $this->response['error'] = 'ERROR! Missing user ID.';
                        $this->response['success'] = false;

                        break;
                    }

                    // Dobimo podatke zadnjega narocila
                    $narocilo = new UserNarocila();
                    $last_narocilo = $narocilo->getLastNarocilo($usr_id);

                    $this->response = $last_narocilo;

                    break;


                // Dobimo pdf predracun (ce ne obstaja ga ustvarimo)
                case 'get_predracun':

                    if(isset($this->data['narocilo_id'])){
                        $cebelica = new UserNarocilaCebelica($this->data['narocilo_id']);
                        $this->response = $cebelica->getNarociloPredracun();
                    }
                    else{
                        $this->response['error'] = 'Napaka! Manjka ID narocila!';
                        $this->response['success'] = false;
                    }

                    break;


                // Dobimo pdf racun
                case 'get_racun':

                    if(isset($this->data['narocilo_id'])){
                        $cebelica = new UserNarocilaCebelica($this->data['narocilo_id']);
                        $this->response = $cebelica->getNarociloRacun();
                    }
                    else{
                        $this->response['error'] = 'Napaka! Manjka ID narocila!';
                        $this->response['success'] = false;
                    }

                    break;


                // Placamo narocilo - aktiviramo uporabniku paket za uporabo, zgeneriramo in vrnemo url do pdf racuna in ga tudi posljemo po mailu
                case 'placaj_narocilo':

                    $narocilo = new UserNarocila();
                    $this->response = $narocilo->payNarocilo($this->data['narocilo_id']);

                    break;


                // Dobimo vse pakete, ki so na voljo
                case 'get_paketi':
                
                    $narocilo = new UserNarocila();

                    $sqlPackages = sisplet_query("SELECT id, name, description FROM user_access_paket");
                    while($row = mysqli_fetch_array($sqlPackages)){

                        $this->response['paketi'][$row['id']] = $row;

                        // Dobimo se ceno za paket za 1, 3 in 12 mesecev
                        if($row['name'] == '2ka' || $row['name'] == '3ka'){

                            // Cene za 1 mesec
                            $cena1 = $narocilo->getPrice($row['name'], 1);
                            foreach($cena1 as $key => $value){
                                $cena1[$key] = str_replace('.', ',', $value);
                            }
                            $this->response['paketi'][$row['id']]['price']['1'] = $cena1;

                            // Cene za 3 mesece
                            $cena3 = $narocilo->getPrice($row['name'], 3);
                            foreach($cena3 as $key => $value){
                                $cena3[$key] = str_replace('.', ',', $value);
                            }
                            $this->response['paketi'][$row['id']]['price']['3'] = $cena3;

                            // Cene za 12 mesecev
                            $cena12 = $narocilo->getPrice($row['name'], 12);
                            foreach($cena12 as $key => $value){
                                $cena12[$key] = str_replace('.', ',', $value);
                            }
                            $this->response['paketi'][$row['id']]['price']['12'] = $cena12;
                        }
                    }

                    break;

                // Poslje maila za povprasevanje za poslovne uporabnike
                case 'send_poslovni_uporabniki':
                    $narocilo = new UserNarocila();
                    $this->response = $narocilo->sendPoslovniUporabniki($this->data);

                    break;

                // Vrne trenutno aktivno narocnino
                case 'get_active_subscription':

                    $usr_id = 0;

                    // Dobimo user id iz emaila
                    if(isset($this->data['email'])){
                        $sqlU = sisplet_query("SELECT id FROM users WHERE email='".$this->data['email']."'");
                        $rowU = mysqli_fetch_array($sqlU);
                        
                        $usr_id = $rowU['id'];
                    }

                    if($usr_id == '' || $usr_id == 0){
                        $this->response['error'] = 'ERROR! Missing user ID.';
                        $this->response['success'] = false;

                        break;
                    }

                    // Dobimo ime paketa iz id-ja
                    $sqlPackage = sisplet_query("SELECT name FROM user_access_paket WHERE id='".$this->data['package_id']."'");
                    $rowPackage = mysqli_fetch_array($sqlPackage);

                    $narocilo = new UserNarocila();

                    $discount = $narocilo->getDiscount($usr_id, $rowPackage['name'], $this->data['trajanje']);
                    $price = $narocilo->getPrice($rowPackage['name'], $this->data['trajanje'], $discount);

                    $this->response = $price;

                    break;
                
                // Dokoncaj narocilo ce je placano preko paypala (ko je stranka potrdila placilo v paypalu)
                case 'capture_narocilo_paypal':

                    if(isset($this->data['narocilo_id'])){
                        $paypal = new UserNarocilaPaypal($this->data['narocilo_id']);
                        $this->response = $paypal->paypalCaptureOrder();
                    }
                    else{
                        $this->response['error'] = 'Napaka! Manjka ID narocila!';
                        $this->response['success'] = false;
                    }  

                    break; 
                
                // Preklici narocilo za paypal (ko je stranka preklicala placilo v paypalu)
                case 'cancel_narocilo_paypal':

                    if(isset($this->data['narocilo_id'])){
                        $paypal = new UserNarocilaPaypal($this->data['narocilo_id']);
                        $this->response = $paypal->paypalCancelOrder();
                    }
                    else{
                        $this->response['error'] = 'Napaka! Manjka ID narocila!';
                        $this->response['success'] = false;
                    }  

                    break; 

                // Preveri, ce je podjetje zavezanec iz tujine (eu) in ustrezno preracuna znesek (odbije ddv)
                case 'check_ddv':

                    $podjetje_drzava = isset($this->data['podjetje_drzava']) ? $this->data['podjetje_drzava'] : '';
                    $podjetje_davcna = isset($this->data['podjetje_davcna']) ? $this->data['podjetje_davcna'] : '';
                    $cena = isset($this->data['cena']) ? str_replace(',', '.', $this->data['cena']) : '';

                    if($podjetje_drzava != '' && $cena != ''){
                        
                        // Mora placati ddv - cena ostane ista
                        if(UserNarocila::checkPayDDV($podjetje_davcna, $podjetje_drzava)){
                            $this->response['cena'] = $cena;
                            $this->response['ddv'] = true;
                        }
                        // Ne placa ddv - placa samo osnovo
                        else{
                            $this->response['cena'] = number_format(floatval($cena) / 1.22, 2, '.', '');
                            $this->response['ddv'] = false;
                        }                       

                        $this->response['success'] = true;
                    }
                    else {
                      $this->response['error'] = 'Napaka! Manjkajo zahtevani parametri!';
                    }

                    break;

                      // Dokoncaj narocilo ce je placano preko stripe (ko je stranka potrdila placilo preko sca)
                case 'stripe_checkout_success':

                    if(isset($this->data['narocilo_id'])){
                        $stripe = new UserNarocilaStripe($this->data['narocilo_id']);
                        $this->response = $stripe->stripeCheckoutSuccess();
                    }
                    else{
                        $this->response['error'] = 'Napaka! Manjka ID narocila!';
                        $this->response['success'] = false;
                    }  

                    break; 
                
                // Preklici narocilo za stripe (ko je stranka preklicala placilo preko sca)
                case 'stripe_checkout_cancel':

                    if(isset($this->data['narocilo_id'])){
                        $stripe = new UserNarocilaStripe($this->data['narocilo_id']);
                        $this->response = $stripe->stripeCheckoutCancel();
                    }
                    else{
                        $this->response['error'] = 'Napaka! Manjka ID narocila!';
                        $this->response['success'] = false;
                    }  

                    break;

                case 'get_lokacija':

                  global $site_path;

                  $reader = new Reader($site_path.'admin/survey/modules/mod_geoIP/db/GeoLite2-City.mmdb');
                  $podatki = $reader->city($this->data['ip']);

                  // Vrnemo ime države
                  $this->response['drzava'] =  $podatki->country->name ?? '';

                break;
            }
        }
    }

    // Sprocesiramo return
    private function processReturn(){

        $json = json_encode($this->response, true);
        
        echo $json;
    }

}