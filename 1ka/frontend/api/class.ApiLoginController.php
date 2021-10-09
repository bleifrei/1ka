<?php

/**
 *
 *	Class ki skrbi za povezavo z API-jem
 *
 */

class ApiLoginController{
	
	var $method;	// Metoda klica (post, get, delete...)
	var $params;	// Parametri v url-ju
	var $data;		// Podatki poslani preko post-a
	
	function __construct(){
		global $site_url;
		global $global_user_id;	
		global $admin_type;
		global $lang;
		global $site_path;
		global $cookie_domain;
		

		// Preberemo poslane podatke
		//$this->processCall();
		$this->processCallForm();

		
		/*echo 'Params:';
		var_dump($this->params);		
		echo '<br>Data:';
		var_dump($this->data);	
		echo 'Metoda: '.$this->method;*/
		
		
		// Izvedemo akcijo
		$login = new ApiLogin();		
		$login->executeAction($this->params, $this->data);
	}
	
	
	// Preberemo poslane podatke (ce posiljamo preko curl)
	private function processCall(){

		// Metoda - POST, GET, DELETE...
		$this->method = $_SERVER['REQUEST_METHOD'];
		
		// Preberemo parametre iz url-ja
		$request = parse_url($_SERVER['REQUEST_URI']);
		parse_str($request['query'], $this->params);

		// Preberemo podatke iz post-a
		$this->data = json_decode(file_get_contents('php://input'), true);
	}
	
	// Preberemo poslane podatke (ce posiljamo direktno iz forme)
	private function processCallForm(){

		$this->params = $_GET;
		$this->data = $_POST;
	}	
}