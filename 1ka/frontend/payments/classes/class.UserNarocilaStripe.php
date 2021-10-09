<?php

/**
 *
 *  Class ki skrbi za placila s kreditno kartico (stripe) - TODO
 *
*/


use \Stripe\Stripe;
use \Stripe\Customer;
use \Stripe\ApiOperations\Create;
use \Stripe\Charge;

use \Stripe\StripeClient;


class UserNarocilaStripe{


    private $narocilo;

    private $apiKey;
    private $stripeService;


    public function __construct($narocilo_id){
        global $app_settings;
        global $stripe_secret;
        global $stripe_key;

        $this->stripeService = new \Stripe\StripeClient($stripe_secret);

        if($narocilo_id > 0){

            // Dobimo podatke narocila
            $sqlNarocilo = sisplet_query("SELECT un.*, u.name, u.surname, u.email, up.name AS package_name, up.description AS package_description, up.price AS package_price
                                            FROM user_access_narocilo un, users u, user_access_paket up
                                            WHERE un.id='".$narocilo_id."' AND un.usr_id=u.id AND un.package_id=up.id");
            if(mysqli_num_rows($sqlNarocilo) > 0){
                $this->narocilo = mysqli_fetch_array($sqlNarocilo);
            }
            else{
                die("Napaka pri komunikaciji s stripe! Narocilo ne obstaja.");
            }
        }
        else {
			die("Napaka pri komunikaciji s stripe! Manjka ID naročila.");
		}
    }


    // Ustvarimo session za placilo v stripe - V DELU
    public function stripeCreateSession(){
        global $site_url;
        global $lang;
        
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
        

        // URL po potrditvi oz preklicu
        if($lang['id'] == '2'){
            $drupal_url_confirm = $site_url.'/d/en/stripe-purchase/success?narocilo_id='.$this->narocilo['id'];
            $drupal_url_cancel = $site_url.'/d/en/stripe-purchase/cancel?narocilo_id='.$this->narocilo['id'];
        }
        else{
            $drupal_url_confirm = $site_url.'/d/narocilo/stripe?narocilo_id='.$this->narocilo['id'];
            $drupal_url_cancel = $site_url.'/d/narocilo/stripe-cancel?narocilo_id='.$this->narocilo['id'];
        }

        // Ustvarimo checkout session
        try {
            $session = $this->stripeService->checkout->sessions->create([
                'success_url'           => $drupal_url_confirm,      
                'cancel_url'            => $drupal_url_cancel,   

                'payment_method_types'  => ['card'],
                'mode'                  => 'payment',

                'customer_email'        => $this->narocilo['email'],

                'line_items' => [
                    [
                        'price_data'    => array(
                            'currency'      => 'EUR',
                            'product_data'  => array(
                                'name'          => '1KA naročnina (paket '.strtoupper($this->narocilo['package_name']). ' - '.$this->narocilo['trajanje'].' '.$months_string.')',
                            ),  
                            'unit_amount'   => $cena_za_placilo * 100,
                        ),
                        'quantity'      => 1,
                    ],
                ],
            ]);

            // Dobimo id paypal narocila
            $stripe_response['session_id'] = $session->id;
        }
        catch (HttpException $e) {
            $response['error'] = $e->getMessage();
            $response['success'] = false;

            return $response;
        }          
        

        // Vstavimo stripe charge v bazo
        $sqlNarocilo = sisplet_query("INSERT INTO user_access_stripe_charge
                                        (session_id, narocilo_id, price, time, status)
                                        VALUES
                                        ('".$stripe_response['session_id']."', '".$this->narocilo['id']."', '".$cena_za_placilo."', NOW(), 'CREATED')
                                    ");
        if (!$sqlNarocilo){
            $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
            $response['success'] = false;

            return $response;
        }


        $response['session_id'] = $stripe_response['session_id'];

        $response['success'] = true;
        
        return $response;
    }


    // Zakljucimo placilo, ce je bilo placilo ok odobreno preko stripe s strani stranke - V DELU
    public function stripeCheckoutSuccess(){

        $response = array();

        // Preverimo  plačilo v bazo
        $sqlNarociloStripe = sisplet_query("SELECT session_id
                                        FROM user_access_stripe_charge 
                                        WHERE narocilo_id='".$this->narocilo['id']."'
                                    ");
        if (!$sqlNarociloStripe){
            $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
            $response['success'] = false;

            return $response;
        }

        // Narocilo ne obstaja (ni v bazi stripe narocil)
        if (mysqli_num_rows($sqlNarociloStripe) == 0){
            $response['error'] = 'ERROR! Stripe order session does not exist.';
            $response['success'] = false;

            return $response;
        }

        $rowNarociloStripe = mysqli_fetch_array($sqlNarociloStripe);


        // Preverimo, ce je bilo vse ok placano
        try{
            // Poklicemo paypal api kjer preverimo placilo narocila
            $session = $this->stripeService->checkout->sessions->retrieve($rowNarociloStripe['session_id']);
        }
        catch(HttpException $e) {
            $response['error'] = $e->getMessage();
            $response['success'] = false;

            return $response;
        }

        // Ce je session placan, posodobimo status narocila
        if($session->payment_status == 'paid'){

            $sqlNarocilo = sisplet_query("UPDATE user_access_stripe_charge 
                                            SET status='PAID'
                                            WHERE session_id='".$paypal_response->result->id."'
                                        ");
            if (!$sqlNarocilo){
                $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
                $response['success'] = false;

                return $response;
            }
        }
        else{
            $response['error'] = 'ERROR! SESSION IS NOT PAID!';
            $response['success'] = false;

            return $response;
        }
        

        // Nastavimo narocilo na placano, aktiviramo paket in vrnemo id narocila
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

    // Preklicemo placilo, ce je bilo placilo preklicano preko stripe s strani stranke
    public function stripeCheckoutCancel(){

        $response = array();

        // Posodobimo status narocila
        $sqlNarocilo = sisplet_query("UPDATE user_access_stripe_charge 
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