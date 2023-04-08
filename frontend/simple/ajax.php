<?php 
	
	include_once '../../admin/survey/definition.php';
	include_once('../../function.php');
	include_once('../../vendor/autoload.php');
	
	
	// Poslana zahteva za izbris
	if($_GET['a'] == 'gdpr_request_send'){
		
		$status = array();
		$status = $_POST['json'];
		
		//var_dump($status);
	
		GDPR::displayGDPRRequestForm($status);
	}
	
?>