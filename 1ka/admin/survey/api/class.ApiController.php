<?php

/**
 *
 *	Class ki skrbi za povezavo z API-jem
 *
 */

class ApiController{
	
	var $method;	// Metoda klica (post, get, delete...)
	var $params;	// Parametri v url-ju
	var $data;		// Podatki poslani preko post-a
	
	function __construct(){
		global $site_url;
		global $global_user_id;

		// Preberemo poslane podatke
		$this->processCall();
		
        // Preverimo userja (geslo)
        $user_id = $this->checkLogin();

        if($user_id != 0){

            // Nastavimo userja za katerega gre
            $global_user_id = $user_id;

            // Izvedemo akcijo
            $survey = new ApiSurvey();
            //spredaj je @ -> to suppress all error messages
            @$survey->executeAction($this->params, $this->data);
        }                
	}
	
	
	// Preberemo poslane podatke
	private function processCall(){
           
		// Metoda - POST, GET, DELETE...
		$this->method = $_SERVER['REQUEST_METHOD'];
                
		// Preberemo parametre iz url-ja
		$request = parse_url($_SERVER['REQUEST_URI']);
		parse_str($request['query'], $this->params);
                
        // Poberemo parametre iz headerja, ce so
        if(isset($_SERVER['HTTP_TOKEN']))
            $this->params['token'] = $_SERVER['HTTP_TOKEN'];
        if(isset($_SERVER['HTTP_IDENTIFIER']))
            $this->params['identifier'] = $_SERVER['HTTP_IDENTIFIER'];

        //pridobimo action iz GET v lepem linku, ce obstaja
        if(isset($_GET['action'])){
            $requestParts = explode('/',$_GET['action']);
            $this->params['action'] = $requestParts[0];
        }
        
        //pridobimo id ankete iz GET v lepem linku, ce obstaja
        if(isset($_GET['ank_id'])){
            $requestParts = explode('/',$_GET['ank_id']);
            $this->params['ank_id'] = $requestParts[0];
        }

		// Preberemo podatke iz post-a
		$this->data = json_decode(file_get_contents('php://input'), true);
	}
	
	// Preveri username in pass ce sta ok za login
	private function checkLogin(){
		global $lang;
		global $pass_salt;
	
		$login_check = false;

		// Ce smo prejeli podatke za autentikacijo
		if(isset($this->params['identifier']) && isset($this->params['token'])){

			$sql = sisplet_query("SELECT * FROM srv_api_auth WHERE identifier='".$this->params['identifier']."'");
			if(mysqli_num_rows($sql) > 0){
				$row = mysqli_fetch_array($sql);
				
				// Ce postamo ga preberemo
				$raw_post_data = '';
				if($this->method == 'POST' && $this->data){
					//@Uros pri mobilnih se ne kreira query - pretvori v json string
					if(in_array($this->params['identifier'], array('mobileApp', 'mazaApp')))
                        $raw_post_data = json_encode($this->data, JSON_UNESCAPED_UNICODE);
					else
                        $raw_post_data = http_build_query($this->data);
				}
				
				// Dobimo request (brez id in token)
				$request = ($_SERVER["HTTPS"] == 'on') ? 'https://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"] : 'http://'.$_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
                if(!isset($_SERVER['HTTP_IDENTIFIER']))
                    $request = preg_replace('/([?&])identifier=[^&]+(&|$)/', '$1', $request);
                if(!isset($_SERVER['HTTP_TOKEN']))
                    $request = preg_replace('/([?&])token=[^&]+(&|$)/', '$1', $request);
                if(!isset($_SERVER['HTTP_TOKEN']) || !isset($_SERVER['HTTP_IDENTIFIER']))
                    $request = substr($request, 0, -1);
				
				// Na nasi strani naredimo hmac podatkov z ustreznim private key-em
				$data = $this->method . $request . $raw_post_data;
				$token = hash_hmac('sha256', $data, $row['private_key']);
                                
				if($this->params['token'] == $token)
					$login_check = true;
				else
					echo '{error: "Authentication failed"}';
			}
			else
				echo '{error: "User does not exist"}';
			
			
			// Ce se logira iz 1ka mobilne aplikacije je treba preveriti posebej user mail in password ce se ujemata
			if($this->params['identifier'] == 'mobileApp' && $login_check){

				$sm = new SurveyMobile();
                $user_id = 0;
				
				// TUKAJ PRIDE DODATEN POGOJ CE GRE ZA PRIJAVO PREKO GOOGLA, FB... - V TEM PRIMERU NIMAMO PASSWORDA
				if(!isset($this->data['Login']['password']) && isset($this->data['Login']['special_login'])){
                    global $APP_special_login_key;

					// DODATI FUNKCIJO checkSpecialLogin v SurveyMobile, kjer se pogleda samo če obstaja mail in nastavi ustrezno user id
					if($this->data['Login']['special_login'] == 'nekajzavsakslucajv4x7in6' || 
                                                $this->data['Login']['special_login'] == $APP_special_login_key){
						$user_id = $sm->googleLogin($this->data['Login']['username']);
					}
				}
				else if(isset($this->data['Login']['password'])){
					// Preverimo ce so v mobilni aplikaciji podatki za prijavo pravilni in pridobimo ustrezen user id
					$user_id = $sm->checkLogin($this->data['Login']['password'], $this->data['Login']['username']);
				}
				
				// Podatki za prijavo (email in pass) so v mobilni aplikaciji vredu nastavljeni - vrnemo user id
				if($user_id > 0){
                    
                    //ce se samo preverja login, vrni user_id = 0, zato da ne gre v akcije
					if($this->params['action'] == "checkLoginApp" || $this->params['action'] == "getMobileAppVersion"){
						//pri checkLoginApp ze kreiramo response objekt v primeru, da je login OK
						echo '{note: "login OK"}';
						return 0;
                    }
                    
					return $user_id;
                }
                
                if($user_id == -1){
                    echo '{error: "user not allowed"}';
                    return 0;
                }
				// Token je ok, podatki za prijavo pa NE
				else{
					echo '{error: "login error"}';
					return 0;
				}
			}	
            // Ce se logira iz MAZA mobilne aplikacije je treba preveriti posebej identifikator respondenta, ce obstaja
			else if($this->params['identifier'] == 'mazaApp' && $login_check){

				$sm = new SurveyMobile();
                                $user_id = 0;
				
				if(isset($this->data['Login']['identifier'])){
					// Preverimo ce so v mobilni aplikaciji podatki za prijavo pravilni in pridobimo ustrezen user id
					$user_id = $sm->checkMazaLogin(($this->params['action'] == "checkLoginApp" || $this->params['action'] == "mazaGetSurveysInfoByIdentifier"), 
                                                $this->data['Login']['identifier'], $this->data['Login']['id_server'], 
                                                $this->data['Login']['registration_id']);
				}
				
				// Podatki za prijavo (email in pass) so v mobilni aplikaciji vredu nastavljeni - vrnemo user id
				if($user_id > 0){
					//ce se samo preverja login, vrni user_id = 0, zato da ne gre v akcije
					if($this->params['action'] == "checkLoginApp"){
                                            if(isset($user_id['note']))
						//pri checkLoginApp ze kreiramo response objekt v primeru, da je login OK
						echo json_encode($user_id, true);
                                            
                                            //vrnemo 0, da ne gre v ApiSurvey
                                            return 0;
					}
					return $user_id;
				}
				// Token je ok, podatki za prijavo pa NE
				else{
					echo '{error: "login error"}';
					return 0;
				}
				
			}
            // Vrne user id ce NE GRE za mobilno aplikacijo in je autentikacija uspesna
			else if($login_check && $row['usr_id'] > 0){
				return $row['usr_id'];
			}
			else{
				return 0;
			}
		}
        //UNSECURED CALLS!!! only use for internal usage and inserting (do not use for editing or deleting!) - this calls are done from client side (ajax) without credentials (user doesnt have account - not loged in)
        else if($this->params['identifier'] == 'wpn'){
            $this->data = $_POST;
            return -1;
        }
		else
			return 0;
	}
	
}