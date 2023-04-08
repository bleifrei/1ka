<?php

	require_once ('../../settings.php');	
	require_once ('../../function.php');   
	include_once '../../vendor/autoload.php';
	
	$login = new ApiLogin();
	$login->executeAction($params=array('action'=>'login_AAI'), $data=array());

?>