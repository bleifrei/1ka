<?php

	require_once ('../../settings.php');	
	require_once ('../../function.php');   
	include_once '../../vendor/autoload.php';
	
	/*require_once ('../../function/ProfileClass.php');
	$profil = new Profile();
	$profil->eduroamAnotherServerLogin();*/
	
	$login = new ApiLogin();
	$login->executeAction($params=array('action'=>'login_AAI'), $data=array());

?>