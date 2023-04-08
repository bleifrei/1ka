<?php
	
	/**
	* da se lahko uporabnik odjavi iz "mailing liste"
	* 
	* zbrisemo ga iz baze respondentov glede na njegov cookie ID
	* 
	*/

	header('Cache-Control: no-cache');
	header('Pragma: no-cache');
	
	include('../../function.php');
	include_once 'definition.php';
	include_once('../../vendor/autoload.php');
	
	
	echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
	echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
	
	echo '<head>';
	echo '	<title>'.$lang['user_bye_hl'].'</title>';
	echo '	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
	echo '</head>';
	
	
	echo '<body>';
	
	$anketa = $_GET['anketa'];
	if ((int)$anketa > 0) {
		$su = new SurveyUnsubscribe($anketa);
		$su::doUnsubscribe();
		
	} else {
		echo $lang['user_bye_missing_id'];
		exit;
	}
	
	echo '</body>';
?>