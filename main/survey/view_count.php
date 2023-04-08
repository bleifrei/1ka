<?php

/**
* Stevcek, ki steje stevilo ogledov linka na anketo (dodan je kot slika)
*/
	
include_once('../../function.php');
include_once '../../vendor/autoload.php';

$anketa = $_GET['a'];

SurveySetting::getInstance()->setSID($anketa);
$view_count = SurveySetting::getInstance()->getSurveyMiscSetting('view_count');
SurveySetting::getInstance()->setSurveyMiscSetting('view_count', ($view_count+1)."");

?>