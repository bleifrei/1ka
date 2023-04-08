<?php

	include_once ('../function.php');

	$fn = $_GET['fn'];
	$fn = str_replace ("'", "", $fn);

	$result = sisplet_query ("INSERT INTO UlCounter (filename, timestamp) VALUES ('$fn', NOW())");
	header ('location: ' .$site_url .'uploadi/editor/' .$fn);
?>