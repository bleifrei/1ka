<?php

	include_once '../../settings.php';
	include_once '../../function.php';
	include_once '../../vendor/autoload.php';
	
	if (isset ($_GET['code']) && $_GET['code'] != "") {
		
		$login = new ApiLogin();
		$login->executeAction($params=array('action'=>'login_facebook'), $data=array());
	}
	else {
		header ('location: https://www.facebook.com/v2.10/dialog/oauth?client_id=' .AppSettings::getInstance()->getSetting('facebook-appid') .'&redirect_uri=https://www.1ka.si/frontend/api/fb_login.php&auth_type=rerequest&scope=email,public_profile');
	}

?>
