<?php

/**
* skripta, ki asinhrono poklice podani url $_GET['url'] hkrati N-krat $_GET['n']
* 
*/

// nastavitve za razlicne streznike, da lahko preprosto startamo skripto iz vecih serverjev

if (!isset($_GET['server'])) $_GET['server'] = 2;

if ($_GET['server'] == 1) {	// test.1ka.si
	
	if (!isset($_GET['url'])) $_GET['url'] = 'http://test.1ka.si/utils/load_test';
	if (!isset($_GET['n'])) $_GET['n'] = 10; $n = (int)$_GET['n'];	// kolikokrat asinhrono poklicemo zgornjo povezavo
	if (!isset($_GET['iterate'])) $_GET['iterate'] = 10; $iterate = (int)$_GET['iterate'];	// parameter ki ga podamo naprej - kolikokrat se znotraj ene povezave izpolnjuje form

} elseif ($_GET['server'] == 2) {
	
	if (!isset($_GET['url'])) $_GET['url'] = 'http://www.1ka.si/utils/load_test.php';
	if (!isset($_GET['n'])) $_GET['n'] = 2; $n = (int)$_GET['n'];	// kolikokrat asinhrono poklicemo zgornjo povezavo
	if (!isset($_GET['iterate'])) $_GET['iterate'] = 2; $iterate = (int)$_GET['iterate'];	// parameter ki ga podamo naprej - kolikokrat se znotraj ene povezave izpolnjuje form
	
}


// zacnemo

$start = microtime(true);

for ($i=0; $i<$n; $i++) {
	$fp[$i] = JobStartAsync($_GET['url'].'?iterate='.$iterate.'&uniqueID='.$i);
}

while (true) {
	sleep(1);
	
	for ($i=0; $i<$n; $i++)
		$r[$i] = JobPollAsync($fp[$i]);
	
	$break = true;
	for ($i=0; $i<$n; $i++)	
		if ($r[$i] !== false) $break = false;
	if ($break) break;
	
	for ($i=0; $i<$n; $i++)	{
		$result = explode("\r\n\r\n", $r[$i], 2);
	    $header = isset($result[0]) ? $result[0] : '';
	    $content = isset($result[1]) ? $result[1] : '';
		echo "<b>r{$i} = </b>{$content}<br>";
	}
	
	echo "<hr>";
	
	flush(); @ob_flush();
}

$time_seconds = microtime(true) - $start;
echo "<h3>All Jobs Complete in {$time_seconds} seconds</h3>";


	
// odpre asinhrono povezavo na skripto
function JobStartAsync($url, $conn_timeout=30, $rw_timeout=86400)
{
	$errno = '';
	$errstr = '';
	
	$url = parse_url($url);
	if ($url['scheme'] != 'http') { 
	    die('Only HTTP request are supported !');
	}
 
	$host = $url['host'];
	$path = $url['path'];
	$query = $url['query'] != '' ? "?{$url['query']}" : "";
	
	set_time_limit(0);
	
	$fp = fsockopen($host, 80);
	
	stream_set_blocking($fp, false);
	stream_set_timeout($fp, $rw_timeout);

	fputs($fp, "GET {$path}{$query} HTTP/1.1\r\n");
	fputs($fp, "Host: $host\r\n");
	fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
	fputs($fp, "Connection: close\r\n\r\n");
	
	return $fp;
}

// zaporedoma bere fp in vraca kaj se dogaja: returns false if HTTP disconnect (EOF), or a string (could be empty string) if still connected
function JobPollAsync(&$fp) 
{
	if ($fp === false) return false;
	
	if (feof($fp)) {
		fclose($fp);
		$fp = false;
		return false;
	}
	
	return fread($fp, 10000);
}
	
?>