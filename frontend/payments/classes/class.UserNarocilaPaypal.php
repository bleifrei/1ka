<?php

/**
 *
 *  Class ki skrbi za placila s paypalom
 *
*/


use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;


class UserNarocilaPaypal{


    private $narocilo;
    private $paypal_client;


    public function __construct($narocilo_id){
        global $app_settings;        
        global $paypal_client_id;
        global $paypal_secret;
        global $mysql_database_name;
        
        if($narocilo_id > 0){

            // Dobimo podatke narocila
            $sqlNarocilo = sisplet_query("SELECT un.*, u.name, u.surname, u.email, up.name AS package_name, up.description AS package_description, up.price AS package_price
                                            FROM user_access_narocilo un, users u, user_access_paket up
                                            WHERE un.id='".$narocilo_id."' AND un.usr_id=u.id AND un.package_id=up.id");
            if(mysqli_num_rows($sqlNarocilo) > 0){
                $this->narocilo = mysqli_fetch_array($sqlNarocilo);
            }
            else{
                die("Napaka pri komunikaciji s paypal! Narocilo ne obstaja.");
            }


            // Ustvarimo okolje za paypal
            if($mysql_database_name == 'real1kasi')
                $environment = new ProductionEnvironment($paypal_client_id, $paypal_secret);
            else
                $environment = new SandboxEnvironment($paypal_client_id, $paypal_secret);

            $this->paypal_client = new PayPalHttpClient($environment);
        }
        else {
			die("Napaka pri komunikaciji s paypal! Manjka ID naročila.");
		}
    }


    // Placamo narocilo s paypal
    public function paypalCreatePayment(){
        global $site_url;
        
        $response = array();


        $UA = new UserNarocila();
        $cena = $UA->getPrice($this->narocilo['package_name'], $this->narocilo['trajanje'], $this->narocilo['discount'], $this->narocilo['time']);

        if($this->narocilo['trajanje'] == 1)
            $months_string = 'mesec';
        elseif($this->narocilo['trajanje'] == 2)
            $months_string = 'meseca';
        elseif($this->narocilo['trajanje'] == 3 || $this->narocilo['trajanje'] == 4)
            $months_string = 'mesece';
        else
            $months_string = 'mesecev';

            
        // Zavezanec iz tujine ima racun/predracun brez ddv
        if($UA->isWithoutDDV($this->narocilo['id'])){
            $ddv = 0;
            $cena_za_placilo = $cena['final_without_tax'];
        }   
        else{
            $ddv = 1;
            $cena_za_placilo = $cena['final'];
        }
        

        // Podatki narocila
        $orderDetails = array(
            'ime'           => '1KA naročnina (paket '.strtoupper($this->narocilo['package_name']). ' - '.$this->narocilo['trajanje'].' '.$months_string.')',
            'narocilo_id'   => $this->narocilo['id'],
            'cena'          => $cena_za_placilo,      
        );

        // Ustvarimo order na paypal, da se lahko potem user prijavi in ga placa
        $paypal_response = $this->paypalCreateOrder($orderDetails);

        if(!isset($paypal_response['success']) || $paypal_response['success'] == false){
            return $paypal_response;
        }
        

        // Vstavimo plačilo v bazo
        $sqlNarocilo = sisplet_query("INSERT INTO user_access_paypal_transaction 
                                        (transaction_id, narocilo_id, price, currency_type, time, status)
                                        VALUES
                                        ('".$paypal_response['transaction_id']."', '".$this->narocilo['id']."', '".$cena_za_placilo."', 'EUR', NOW(), 'CREATED')
                                    ");
        if (!$sqlNarocilo){
            $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
            $response['success'] = false;

            return $response;
        }


        $response['paypal_link'] = $paypal_response['paypal_link'];

        $response['success'] = true;
        
        return $response;
    }

    // Posljemo podatke za placilo paypalu
    private function paypalCreateOrder($orderDetails){
        global $site_url;
        global $lang;

        $response = array();

        $request = new OrdersCreateRequest();

        $request->prefer('return=representation');
        //$request->headers["prefer"] = "return=representation";

        if($lang['id'] == '2'){
            $drupal_url_confirm = $site_url.'/d/en/paypal-purchase/success?narocilo_id='.$orderDetails['narocilo_id'];
            $drupal_url_cancel = $site_url.'/d/en/paypal-purchase/cancel?narocilo_id='.$orderDetails['narocilo_id'];
        }
        else{
            $drupal_url_confirm = $site_url.'/d/narocilo/paypal?narocilo_id='.$orderDetails['narocilo_id'];
            $drupal_url_cancel = $site_url.'/d/narocilo/paypal-cancel?narocilo_id='.$orderDetails['narocilo_id'];
        }

        $request->body = [
            "intent" => "CAPTURE",
            "purchase_units" => [[
                "reference_id" => $orderDetails['narocilo_id'],
                'description' => $orderDetails['ime'],
                
                "amount" => [
                    "value" => $orderDetails['cena'],
                    "currency_code" => "EUR"
                ]
            ]],
            "application_context" => [
                "cancel_url" => $drupal_url_cancel,
                "return_url" => $drupal_url_confirm,

                'brand_name' => '1KA'
            ] 
        ];

        try {
            // Poklicemo paypal api za ustvarjanje narocila
            $paypal_response = $this->paypal_client->execute($request); 
            
            if($paypal_response->result->status != 'CREATED'){
                $response['error'] = 'ERROR! Order was not created.';
                $response['success'] = false;

                return $response;
            }

            // Dobimo id paypal narocila
            $response['transaction_id'] = $paypal_response->result->id;

            // Dobimo link za preusmeritev stranke, da potrdi narocilo in potem lahko izvedemo "capture"
            foreach($paypal_response->result->links as $link){

                if($link->rel == 'approve')
                    $response['paypal_link'] = $link->href;
            }
        }
        catch (HttpException $e) {
            $response['error'] = $e->getMessage();
            $response['success'] = false;

            return $response;
        }
        

        $response['success'] = true;
   
        return $response;
    }


    // Zakljucimo placilo, ce je bilo placilo ok odobreno preko paypala s strani stranke
    public function paypalCaptureOrder(){

        $response = array();

        // Preverimo  plačilo v bazo
        $sqlNarociloPaypal = sisplet_query("SELECT transaction_id
                                        FROM user_access_paypal_transaction 
                                        WHERE narocilo_id='".$this->narocilo['id']."'
                                    ");
        if (!$sqlNarociloPaypal){
            $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
            $response['success'] = false;

            return $response;
        }

        // Narocilo ne obstaja (ni v bazi paypal narocil)
        if (mysqli_num_rows($sqlNarociloPaypal) == 0){
            $response['error'] = 'ERROR! Paypal order does not exist.';
            $response['success'] = false;

            return $response;
        }

        $rowNarociloPaypal = mysqli_fetch_array($sqlNarociloPaypal);

        // Preverimo, ce je bilo vse ok placano - POST request to /v2/checkout/orders
        $request = new OrdersCaptureRequest($rowNarociloPaypal['transaction_id']);
        //$request->prefer('return=representation');

        try {
            // Poklicemo paypal api kjer preverimo placilo narocila
            $paypal_response = $this->paypal_client->execute($request);
        }
        catch (HttpException $e) {
            $response['error'] = $e->getMessage();
            $response['success'] = false;

            return $response;
        }


        // Posodobimo status narocila
        $sqlNarocilo = sisplet_query("UPDATE user_access_paypal_transaction 
                                        SET status='".$paypal_response->result->status."'
                                        WHERE transaction_id='".$paypal_response->result->id."'
                                    ");
        if (!$sqlNarocilo){
            $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
            $response['success'] = false;

            return $response;
        }


        // Nastavimo narocilo na placano, aktiviramo paket in vrnemo racun
        $narocilo = new UserNarocila();
        $payment_response = $narocilo->payNarocilo($this->narocilo['id']);

        if($payment_response['success'] == true){
            $response['racun'] = $payment_response['racun'];
            $response['success'] = true;
        }
        else{
            $response['error'] = $payment_response['error'];
            $response['success'] = false;
        }

        $response['narocilo_id'] = $this->narocilo['id'];


        $response['success'] = true;

        return $response;
    }


    // Preklicemo placilo, ce je bilo placilo preklicano preko paypala s strani stranke
    public function paypalCancelOrder(){

        $response = array();

        // Posodobimo status narocila
        $sqlNarocilo = sisplet_query("UPDATE user_access_paypal_transaction 
                                        SET status='CANCELLED'
                                        WHERE narocilo_id='".$this->narocilo['id']."'
                                    ");
        if (!$sqlNarocilo){
            $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
            $response['success'] = false;

            return $response;
        }

        // Nastavimo status narocila na storniran
        $sqlNarociloStatus = sisplet_query("UPDATE user_access_narocilo SET status='2' WHERE id='".$this->narocilo['id']."'");
        if (!$sqlNarociloStatus){
            $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
            $response['success'] = false;
            
            return $response;
        }

        $response['success'] = true;

        return $response;
    }
}