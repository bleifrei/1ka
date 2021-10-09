<?php
/**
* 
* 	Skripta, ki uvozi novo sql bazo oz. posodobi obstoječo
*
*/
	set_time_limit(1800); 	# Timeout 30 minut

	include_once('../function.php');
	include_once('class.ImportDB.php');


	echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';


	echo '<head>';
	echo '	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
	echo '	<meta charset="utf-8" >';
		
	echo '	<title>EnKlikAnketa - uvoz baze</title>';
				
	echo '	<!-- FAVICON -->';
	echo '	<link rel="shortcut icon" type="image/ico" href="'.$site_url.'/favicon.ico" />';
	echo '</head>';


	echo '<body>';

	$import = new ImportDB();
	
	// Zaradi varnosti je po defaultu to omogoceno samo v debug nacinu oz. za prijavljene admine oz. ce je baza prazna
	if($debug == 1 || $admin_type == '0' || $import->checkDBEmpty()){
		$import->display();
	}
	else{
		die();
	}

	echo '</body>';


	echo '</html>';
?>