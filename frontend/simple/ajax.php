<?php 
	
	include_once('../../admin/survey/definition.php');
	include_once('../../function.php');
	include_once('../../vendor/autoload.php');
	include_once('classes/DisplayController.php');
	
	
	// Poslana zahteva za izbris
	if($_GET['a'] == 'gdpr_request_send'){
		
		$status = array();
		$status = $_POST['json'];
		
		//var_dump($status);
	
		GDPR::displayGDPRRequestForm($status);
	}
    // Potrditev piskotka
	elseif($_GET['a'] == 'cookie_confirm'){
		
        $dc = new DisplayController();
        $dc->cookieConfirm();
	}
	
?>