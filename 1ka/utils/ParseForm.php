<?php

// Deluje pravilno le za eno formo
// Mogoce bi tudi razsiril tako da bi podal form name

// Zaenkrat le radio in checkbox.
class ParseForm {

	var $content;	// vsebina strani
	var $url;

	// cookies
	var $cookies;

	// direktno dumpano
	// [offset][0] -- name , [offset][1] -- value
	var $inputs;
	var $selects;
	var $hidden;
	var $checkboxes;
	var $radio;
	var $textbox;

	// Kam kaze forma
	var $method;
	var $action;
	
	// urejeno
	var $arr_checkbox;
	var $arr_radio;
	var $arr_hidden;

	function ParseForm ($url) {
		$this->url = $url;

		$fv = file_get_contents (str_replace('&amp;', '&', $url));
		
		if (strpos ($fv, "<body") !== false) {
			$fv = substr ($fv, strpos ($fv, "<body"), strpos ($fv, "</body>"));
		}
		$this->content = $fv;
	}
	
	function GetCookies () {
		ob_start();
		$ch = curl_init($this->url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		$ok = curl_exec($ch);
		curl_close($ch);
		$head = ob_get_contents();
		ob_end_clean();

		preg_match_all ("/Set-Cookie\:(.*?)\;/i", $head, $kukiji);
		for ($x = 0; $x <= sizeof ($kukiji[1]); $x++) {
			
			preg_match ("/\ (.*?)\=/i", $kukiji[1][$x], $ime);

			$this->cookies[sizeof($this->cookies)][0] = str_replace (" ", "", str_replace ("=", "", $ime[0]));
			$this->cookies[sizeof($this->cookies)-1][1] = str_replace ($ime[0], "", $kukiji[1][$x]);
		}
	}
	
	function GetTarget () {
		preg_match ("/\<form\ (.*?)>/i", $this->content, $forma);

		preg_match ("/method\=\"(.*?)\"/i", $forma[1], $method);
			$method = str_replace ('method="', '', $method[0]);
			$this->method = str_replace ('"', '', $method);

		preg_match ("/action\=\"(.*?)\"/i", $forma[1], $action);
			$action = str_replace ('action="', '', $action[0]);
			$this->action = str_replace ('"', '', $action);
            
            
        echo 'METHOD: '.$this->method.'<br>';
        echo 'ACTION: '.$this->action.'<br>';
	}

	function GetFields () {

		preg_match_all ("/\<input\ (.*?)>/i", $this->content, $this->inputs);
echo "<hr>KLican sem na getfields<hr>";
		// najprej parsajmo inpute
		for ($x = 0; $x <= sizeof ($this->inputs[1]); $x++) {
			unset ($value);
			unset ($type);
			unset ($name);

			preg_match ("/name\=\"(.*?)\"/i", $this->inputs[1][$x], $name);
				$name = str_replace ('name="', '', $name[0]);
				$name = str_replace ('"', '', $name);
				
			preg_match ("/value\=\"(.*?)\"/i", $this->inputs[1][$x], $value);
				$value = str_replace ('value="', '', $value[0]);
				$value = str_replace ('"', '', $value);

			preg_match ("/type\=\"(.*?)\"/i", $this->inputs[1][$x], $type);
				$type = str_replace ('type="', '', $type[0]);
				$type = str_replace ('"', '', $type);

			if (strtolower ($type) == "checkbox") {
				$this->checkboxes[sizeof($this->checkboxes)][0] = $name;
				$this->checkboxes[sizeof($this->checkboxes)-1][1] = $value;
			}
			if (strtolower ($type) == "radio") {
				$this->radio[sizeof($this->radio)][0] = $name;
				$this->radio[sizeof($this->radio)-1][1] = $value;
			}
			if (strtolower ($type) == "hidden") {
				$this->hidden[sizeof($this->hidden)][0] = $name;
				$this->hidden[sizeof($this->hidden)-1][1] = $value;
			}
			if (strtolower ($type) == "text") {
				$this->textbox[sizeof($this->textbox)][0] = $name;
				$this->textbox[sizeof($this->textbox)-1][1] = $value;
			}
		}
        
	}
	
	// Izpise na zaslon vsa polja
	function DumpFields () {
		for ($a=0; $a<=sizeof($this->checkboxes); $a++) {
			echo "Checkbox" .$this->checkboxes[$a][0] .", val: " .$this->checkboxes[$a][1] ."<br>";
		}
		for ($a=0; $a<=sizeof($this->radio); $a++) {
			echo "Radio name=" .$this->radio[$a][0] .", val: " .$this->radio[$a][1] ."<br>";
		}
		for ($a=0; $a<=sizeof($this->hidden); $a++) {
			echo "Radio name=" .$this->hidden[$a][0] .", val: " .$this->hidden[$a][1] ."<br>";
		}
	}
 
	// Spravi elemente forme v array (arr[offset][0]--name, arr[offset][1..n]-- val)
	function FieldsToArrays () {

		$pre_name = "";

		for ($a=0; $a<=sizeof($this->radio); $a++) {
			if ($this->radio[$a][0] != $pre_name) {
				$this->arr_radio[sizeof($this->arr_radio)][0] = $this->radio[$a][0];
				$pre_name = $this->radio[$a][0];
			}

			$this->arr_radio[sizeof($this->arr_radio)-1][sizeof($this->arr_radio[sizeof($this->arr_radio)-1])] = $this->radio[$a][1];
		}

		for ($a=0; $a<=sizeof($this->hidden); $a++) {
			if ($this->hidden[$a][0] != $pre_name) {
				$this->arr_hidden[sizeof($this->arr_hidden)][0] = $this->hidden[$a][0];
				$pre_name = $this->hidden[$a][0];
			}

			$this->arr_hidden[sizeof($this->arr_hidden)-1][sizeof($this->arr_hidden[sizeof($this->arr_hidden)-1])] = $this->hidden[$a][1];
		}


		for ($a=0; $a<=sizeof($this->checkboxes); $a++) {
			if ($this->checkboxes[$a][0] != $pre_name) {
				$this->arr_checkbox[sizeof($this->arr_checkbox)][0] = $this->checkboxes[$a][0];
				$pre_name = $this->checkboxes[$a][0];
			}

			$this->arr_checkbox[sizeof($this->arr_checkbox)-1][sizeof($this->arr_checkbox[sizeof($this->arr_checkbox)-1])] = $this->checkboxes[$a][1];
		}
	}

	// nakljucno izpolni in submita formo.
	function RandomFill () {
		$radios = "";
		$checks = "";
		$data = "";

		// Najprej random radio
		for ($a = 0; $a < sizeof($this->arr_radio); $a++) {
			$radios .= "&" .$this->arr_radio[$a][0] ."=" .$this->arr_radio[$a][rand(1, sizeof($this->arr_radio[$a])-1)];
			$data[$this->arr_radio[$a][0]] = $this->arr_radio[$a][rand(1, sizeof($this->arr_radio[$a])-1)];
echo "Na ossfet " .$this->arr_radio[$a][0] ." nastavljam vrednost " .$this->arr_radio[$a][rand(1, sizeof($this->arr_radio[$a])-1)];
		}
		// hidden ni random
		for ($a = 0; $a < sizeof($this->arr_hidden); $a++) {
			$hidden .= "&" .$this->arr_hidden[$a][0] ."=" .$this->arr_hidden[$a][sizeof($this->arr_hidden[$a])-1];
			$data[$this->arr_hidden[$a][0]] = $this->arr_hidden[$a][sizeof($this->arr_hidden[$a])-1];
		}
		// random checkbox
		for ($a = 0; $a < sizeof($this->arr_checkbox); $a++) {

			// Obkljukamo nakljucno kljukic
			$kljukic = rand (1, sizeof($this->arr_checkbox[$a]));

			for ($b = 0; $b < $kljukic; $b++) {
				$checks .= "&" .$this->arr_checkbox[$a][0] ."=" .$this->arr_checkbox[$a][rand(1, sizeof($this->arr_checkbox[$a])-1)];
				$data[$this->arr_checkbox[$a][0]] = $this->arr_checkbox[$a][rand(1, sizeof($this->arr_checkbox[$a])-1)];
			}
		}
echo "data[vrednost_2901] je " .$data['vrednost_2901'];
echo "<br><br>PRVIC JE DATA ";
echo var_dump($data);
echo "<br><br>";



		$content = str_replace ("&=", "", str_replace ("&=", "", substr ($radios . $checks .$hidden, 1)));
echo "Izpolnil: $content";

		if (strpos ($this->action, "http://")===false) {
			if (strpos ($this->action, "/")===false) {
				$url = substr ($this->url, 0, strrpos($this->url, "/")) ."/" .$this->action;
			}
			elseif (strpos ($this->action, "/") > 1) {
				$url = substr ($this->url, 0, strrpos($this->url, "/")) .$this->action;
			}
			else {
				$url = substr ($this->url, 0, strpos($this->url, "/")) .$this->action;
			}

		}

        echo "<br>DRUGIC JE DATA: ";
        echo var_dump($data);
        echo "<hr>";
        $url = 'dest.php';
		if (strtolower ($this->method) == "post") {
			$this->do_post_request ($url, $data);
		}
		else {
//			ponavljaj skripto.
		}
		
	}

	function do_post_request ($url, $data) {
		echo '<hr><hr>';
		
		echo "VSEBINA STRANI KI JO POSILJAM JE " .$this->content ."<br>";
		echo "POSTATI HOCEM PODATAKE ";
        echo var_dump($data);
        echo "<br>";
        echo "POSTATI HOCEM NA $url";
		
		foreach($data as $key => $param) {
			if (empty($param)) {
				unset($data[$key]);
			}
		}
		// Create data
		$data = http_build_query($data);

		// Headers
		$headers  = "Content-type: application/x-www-form-urlencoded\r\n";
		$headers .= "Content-Length: " . strlen($data) . "\r\n";
		
		for ($x = 0; $x < sizeof ($this->cookies); $x++) {
			if ($this->cookies[$x][0]!="") {
				$kukis .= "; " .$this->cookies[$x][0] ."=" .$this->cookies[$x][1];
			}
		}
		if (strlen ($kukis) > 1) $headers .= "Cookie: " .substr ($kukis, 2, strlen($kukis)) ."\r\n";

        echo "<br>header: $headers<hr>";

		// Create stream
		$options = array(
            'http' => array(
                'method'  => 'POST', 
                'header'  => $headers, 
                'content' => $data
            )
        );
		
        echo "tu poslal naprej ---_" ;
        echo var_dump($data);
        echo '_-----';
		
        $context = stream_context_create($options);



		// Get the result
		$result = file_get_contents($url, false, $context);
		echo "<br><br><br>KAR DOBIM NAZAJ: $result";
/*		// Lahko bi malo izpopolnil!!!
		if (strpos ($result, '<form') !== false) {
			echo "Grem na naslednjo stran...";
			$this->ParseNextPage ($result);
		}
*/	} 
	
	function ParseNextPage ($content) {
		if (strpos ($content, "<body") !== false) {
			$content = substr ($content, strpos ($content, "<body"), strpos ($content, "</body>"));
		}
		
		
		
		
	 	$this->inputs = "";
		$this->selects= "";
		$this->hidden= "";
		$this->checkboxes= "";
		$this->radio= "";
		$this->textbox= "";

	// Kam kaze forma
		$this->method= "";
		$this->action= "";
	
	// urejeno
		$this->arr_checkbox= "";
		$this->arr_radio= "";
		$this->arr_hidden= "";
	 
		$this->content = $content;
		echo "Sem na parsenextpage, grem klicat getfields";
		// kukijev na naslednjih straneh ne preverjam vec.
		$this->GetTarget();
		$this->GetFields();
		$this->FieldsToArrays();
		$this->RandomFill();
	}


}

?>