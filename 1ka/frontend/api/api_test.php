<?php
/**
 *
 *	Skripta za testiranje API-ja
 *
**/

// Nastavimo url api-ja
$api_url = 'http://localhost/FDV/frontend/api/api.php';

// Nastavimo identifier in key userja
$identifier = '';
$private_key = '';


// Nastavimo parametre
//$action = 'login';
$action = 'logout';


// Izvedemo klic (GET ali POST)
//$result = executeGET();
//$result = executePOST();




// Izvedemo json decode
$result_array = json_decode($result, true);

// redirectamo ce imamo tako nastavljeno
if(isset($result_array['redirect']) && $result_array['redirect'] != ''){
	header('Location: '.$result_array['redirect']);
}
// Drugace izpisemo rezultat
else{
	echo 'REZULTAT (RAW):<br />';
	echo $result;

	echo '<br /><br /><br />';
	
	// Nastavimo nazaj popravljen cookie
	//$_COOKIE = $result_array['cookie'];
	
	echo 'REZULTAT (JSON DECODE):';
	var_dump($result_array);	
}




// GET
function executeGET(){
	global $api_url;
	global $identifier;
	global $private_key;
	global $ank_id;
	global $action;
		
	// GET params
	$params = 'action='.$action;		// Funkcija, ki jo želimo izvesti
	$params .= '&ank_id='.$ank_id;		// ostali parametri potrebni za klic funkcije (id ankete, vprašanja...)
			
	// Pripravimo podatke za hashiranje
	$request_method = 'GET';
	$request = $api_url.'?'.$params;

	$data = $request_method . $request;
		
	// Izracunamo hash (token)
	$token = hash_hmac('sha256', $data, $private_key);
		
	// Pripravimo klic – dodamo parametra »identifikator« in »token«
	$ch = curl_init($request.'&identifier='.$identifier.'&token='.$token);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_method);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
	// Izvedemo klic
	$result = curl_exec($ch);
	
	return $result;
}

// POST
function executePOST(){
	global $api_url;
	global $identifier;
	global $private_key;
	global $ank_id;
	global $action;
	
	// GET params
	$params = 'action='.$action;		// Funkcija, ki jo želimo izvesti
			
	// POST data
	$post_data = array(
		"cookie" => $_COOKIE
	);

	
	// Pripravimo podatke za hashiranje
	$request_method = 'POST';
	$request = $api_url.'?'.$params;
	$raw_post_data = http_build_query($post_data);

	$data = $request_method . $request . $raw_post_data;
		
	// Izracunamo hash (token)
	$token = hash_hmac('sha256', $data, $private_key);
	
	
	// Pripravimo klic – dodamo parametra »identifikator« in »token«
	$ch = curl_init($request.'&identifier='.$identifier.'&token='.$token);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));		// JSON string za POST
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_method);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	curl_setopt($ch, CURLOPT_HEADER  ,1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLINFO_HEADER_OUT, true);
	/*$cookie_file = 'cookie.txt';
	curl_setopt ($ch, CURLOPT_COOKIEFILE, $cookie_file); 
	curl_setopt ($ch, CURLOPT_COOKIEJAR, $cookie_file);*/
	
	
	// Pripravimo cookije ki jih posljemo cez
	$cookie_string = '';
	foreach($_COOKIE as $key => $value){
		$cookie_string .= $key.'='.$value.';';
	}	
	$cookie_string = substr($cookie_string, 0, -2);
	curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);
		
		
	// Izvedemo klic
	$result = curl_exec($ch);

	
	// Popravimo piskotke
	preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches); // get cookie
	$cookies = array();
	foreach($matches[1] as $item) {
		$new_cookie = explode("=", $item);
		setcookie($new_cookie[0], $new_cookie[1], time()-3600, '/', '');
	}	
	
	// Izluscimo samo json response (ker imamo tudi header zraven)
	preg_match_all('{".*"}', $result, $matches);
	$result = '{'.$matches[0][0].'}';
	
	
	return $result;
}
	