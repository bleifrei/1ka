<?php

	/*
	*	Skripta ki se klice ko respondent poslje zahtevek za izbris oz. vpogled v podatke (GDPR zahtevek)
	*/

	include_once('../admin/survey/definition.php');
	include_once('../function.php');
	include_once('../vendor/autoload.php');

	// Testiranje...
	/*$_POST = array(
		'email' => 'xxx.yy@gg.si',
		'srv-name' => 'Test ime',
		'srv-url' => 'http://1ka/a/75/xcxx?fsad=erwre',
		'gdpr-action' => '3',
		'gdpr-note' => 'blabalbalb asfasf',
		'gdpr-notice-me' => '1'
	);*/

	$gdpr = new GDPR();
	$gdpr->sendGDPRRequest($_POST);	

?>