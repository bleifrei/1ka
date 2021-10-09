<?php

/**
* skripta ki zaporedno klice izpolnjevanje ankete na podanem naslovu $_GET['url'] in stevilu iteracij $_GET['iterate']
*/

include('../function.php');

if (!isset($_GET['url'])) $_GET['url'] = 'http://www.1ka.si/loadtest';
if (!isset($_GET['iterate'])) $_GET['iterate'] = 10;
if (!isset($_GET['uniqueID'])) $_GET['uniqueID'] = '';

$lt = new LoadTest();
$lt->url($_GET['url']);
$lt->run($_GET['iterate']);

/**
* 
* Class, ki na podanem URLju zacne izpolnjevati formo.
* Ce je na naslednji strani (ki jo dobi nazaj) zopet forma, nadaljuje izpolnjevanje (za izpolnjevanje celotne ankete na vecih straneh)
* 
* Izpolnjevanje od zacetka se pozene v podanem stevilu iteracij.
* 
*/
class LoadTest {
	
	private $time_start;
	private $start;
	private $url;
	private $subrequests = false;
	
	/**
	* zabelezimo zacetek izvajanja skripte
	* 
	*/
	function __construct () {
		$this->start = microtime(true);	
	}
	
	/**
	* URL na katerem bomo zaceli izpolnjevati formo
	* 
	* @param mixed $url
	*/
	function url ($url) {
		$this->url = $url;
	}
	
	/**
	* pozenemo izpolnjevanje forme v stevilu iteracij
	* 
	* @param mixed $iterate
	*/
	function run ($iterate = 1) {
		
		for ($i=1; $i<=$iterate; $i++) {
			$this->time_start = microtime(true);
			
			$this->fill_form($this->url);
			
			$time_seconds = microtime(true) - $this->time_start;
			echo $i.'. form filled in '.$time_seconds.' seconds<br>';
			flush(); @ob_flush();
		}
	
		$time_seconds = microtime(true) - $this->start;
		echo '<br><b>All '.$iterate.' forms filled in '.$time_seconds.' seconds</b>';
		
		flush(); @ob_flush();
	}
	
	/**
	* izpolnjuje nek form, dokler ne pride do strani brez form elementa
	* 
	* @param string $url
	* @param mixed $post
	*/
	function fill_form ($url, $post=null) {
		if ($url == '') return;
		$i = 0;
		
		do {
			
			list($header, $content) = $this->post_request($url, $post);
			if ($this->subrequests) {
				$this->post_request('http://www.1ka.si/admin/survey/minify/g=jsfrontend');
				$this->post_request('http://www.1ka.si/admin/survey/script/calendar/calendar.js');
				$this->post_request('http://www.1ka.si/admin/survey/script/calendar/lang/calendar-si.js');
				$this->post_request('http://www.1ka.si/admin/survey/script/calendar/calendar-setup.js');
				$this->post_request('http://www.1ka.si/admin/survey/script/calendar/calendar.css');
				$this->post_request('http://www.1ka.si/main/survey/skins/Default.css');
			}
			
			$url = '';
			
			// ce stran poslje redirect
			if (strpos($header, 'HTTP/1.1 302 Found') !== false) {
				
				$h = explode("\n", $header);
				foreach ($h AS $l) if (strpos($l, 'Location:') !== false) $location = $l;
				
				$url = trim( substr($location, 10) );
				$post = null;
				
			// obicen page, ki ga gremo parsat
			} else {
			
				$form = $this->parse_form($content);
				
				if ( isset($form['action']) ) $url = $form['action'];
				
				$form['input'] = $this->randomize_form($form['input']);
		
				if ( isset($form['input']) ) $post = $form['input'];

			}
			
			if (++$i >= 10000) { echo 'BREAK'; break; }	// preprecimo, da se ne zacikla
		
		} while ($url != '');
		
		/*if (strpos($content, 'Hvala za sodelovanje') === false)
			echo '<hr>'.$header.'<br>'.$content.'<hr>';
		else
			echo '<hr>KONEC<hr>';*/
	}
	
	/**
	* sparsa podano HTML vsebino strani in vrne array s podatki form-a
	* 
	* @param mixed $content
	*/
	function parse_form ($content) {
		$form = array();
		
		$dom = new DOMDocument();
		@$dom->loadHTML($content);
		$dom->preserveWhiteSpace = false; 
		
		$form_el = $dom->getElementsByTagName('form');
		foreach ($form_el AS $oneform) // na strani mora biti samo en form... ker drugace ne vemo katerega izbrati
			$form['action'] = $oneform->getAttribute('action');
		
		// gremo cez input polja
		$inputs = $dom->getElementsByTagName('input');
		
		foreach ($inputs AS $input) {
			$name = $input->getAttribute('name');
			$value = $input->getAttribute('value');
			$type = $input->getAttribute('type');
			if ($name != '') {
				$form['input'][$name]['type'] = $type;
				$form['input'][$name][] = $value;
			}
		}
		
		return $form;
	}
	
	/**
	* zrandomizira vrednosti forma
	* 
	* @param mixed $form
	*/
	function randomize_form($form) {
		/*echo '<pre>';
		echo "\noriginale: ";
		print_r($form);*/
		
		if ( count($form) == 0 ) return $form;
		
		foreach ($form AS $key => $input) {
		
			// radio button - izberemo enega nakljucno
			if ($input['type'] == 'radio') {
				$pos = rand(0, count($input)-2);
				$form[$key] = $input[$pos];
			
			// checkbox (razlika je v tabeli in navadnih, ker imajo razlicen nacin poimenovanja, in se ne da drugace zaznati skupin... zakompliciran..)
			} elseif ($input['type'] == 'checkbox') {
				
				// navaden checkbox - izberemo enega nakljucno
				if ( count($input) > 2 ) {
					$pos = rand(0, count($input)-2);
					$form[$key] = $input[$pos];
					
				// multigrid checkbox - vsak checkbox obkljukamo z verjetnostjo 50% (ker se ne da razbrati vrstic zaradi takega poimenovanja)
				} else {
					foreach ($input AS $k => $v) {
						if ($v != 'checkbox')
							if (rand(0,1) >= 0.5) $form[$key] = $v; else unset($form[$key]);
					}
				}
			
			// textfield - vpisemo nek random string
			} elseif ($input['type'] == 'text') {
				$form[$key] = ($_GET['uniqueID']!=''?$_GET['uniqueID'].'-':'') . substr(sha1(rand(0,1).time()), 0, 10);
			
			
			// ce je samo 1 element, nimamo kaj randomizirat (count je 2, ker je en type)
			} else {
				$form[$key] = $input[0];
				
			}
		
			
		}
		
		/*echo "\nrandomized:";
		print_r($form);
		echo '</pre>';*/
		
		return $form;
	}
	
	/**
	* en primercek, ki poslje vse parametre. request_show.php pa izpise vse post, get in cookieje ki jih prejme
	* 
	*/
	function test_example() {

		$post = array('test' => 'foobar', 'okay' => 'yes', '6' => 'test');

		$get = array ('get'=>'gett', 'get222'=>'123');

		$cookie = array ('ena' => 'prvi', 'dva' => 'drugiff');
		 
 		list($header, $content) = $this->post_request(
		    "http://test.1ka.si/utils/request_show.php?pa_v_urlju=tudi_dela",
		    $post,
		    $get,
		    $cookie
		);
		 
		echo $header.'<hr>'.$content;
	}

	/**
	* naredi request (POST oz GET, nastavi tudi COOKIE) in vrne rezultat
	*  
	*/
	function post_request($url, $_post=null, $_get=null, $_cookie=null, $referer='') {
 		
 		if ($referer == '') $referer = $url;
 		
 		if ($_post != null) {
		    $data = array();    
		    while (list($n, $v) = each($_post)) {
		        $data[] = "$n=$v";
		    }    
		    $data = implode('&', $data);
		}
		
 		if ($_get != null) {
 			$get = array();
 			while (list($n,$v) = each($_get)) {
				$get[] = "$n=$v";
 			}
 			$get = '?'.implode('&', $get);
		} else $get = '';
	 
 		if ($_cookie != null) {
 			$cookie = array();
 			while (list($n,$v) = each($_cookie)) {
				$coookie[] = "$n=$v";
 			}
 			$cookie = implode('; ', $coookie);
		}
				
	    // sparsamo url
	    $url = parse_url($url);
	    if ($url['scheme'] != 'http') { 
	        die('Only HTTP request are supported !');
	    }
	 
	    $host = $url['host'];
	    $path = $url['path'];
	    if (isset($url['query'])) $query = ($get==''?'?':'&').$url['query']; else $query = '';
	
		set_time_limit(0);
		
	    $fp = fsockopen($host, 80);
	 
	 	//stream_set_blocking($fp, false);
		stream_set_timeout($fp, 86400);
	
	    // posljemo header
	    if ($_post != null)
	    	fputs($fp, "POST {$path}{$get}{$query} HTTP/1.1\r\n");
	    else
	    	fputs($fp, "GET {$path}{$get}{$query} HTTP/1.1\r\n");
	    fputs($fp, "Host: $host\r\n");
	    fputs($fp, "Referer: $referer\r\n");
	    if ($_cookie != null)
	    	fputs($fp, "Cookie: $cookie\r\n");
	    fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
	    if ($_post != null)
	    	fputs($fp, "Content-length: ". strlen($data) ."\r\n");
	    fputs($fp, "Connection: close\r\n\r\n");
	    if ($_post != null)
	    	fputs($fp, $data);
	 
	    $result = ''; 
	    while(!feof($fp)) {
	        $result .= fgets($fp, 128);
	    }
	 
	    fclose($fp);
	 
	    // locimo header od podatkov
	    $result = explode("\r\n\r\n", $result, 2);
	 
	    $header = isset($result[0]) ? $result[0] : '';
	    $content = isset($result[1]) ? $result[1] : '';
	 
	 	// header in podatke vrnemo v arrayu
	    return array($header, $content);
	}

}

?>