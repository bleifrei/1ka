<?php

include_once('../../function.php');

$anketa = $_GET['anketa'];
$usr_id = $_GET['usr_id'];
$preview = $_GET['preview'];
$code = isset($_GET['code']) ? '&code='.$_GET['code'] : '';

$sql1 = sisplet_query("SELECT cookie FROM srv_anketa WHERE id = '$anketa'");
$row1 = mysqli_fetch_array($sql1);

$sql = sisplet_query("SELECT cookie FROM srv_user WHERE id = '$usr_id'");
$row = mysqli_fetch_array($sql);

if ($_GET['quick_view'] == 1) {
	$urejanje = '&quick_view=1'; 
} else {
	$urejanje = '&urejanje=1';
}

if ($row1['cookie'] == -1) {
	
	header("Location: ".$site_url."main/survey/index.php?anketa=$anketa&survey-".$anketa."=".$row['cookie'].$urejanje.$code);
} else {
	setcookie('survey-'.$anketa, $row['cookie'], 0);
	header("Location: ".$site_url."main/survey/index.php?anketa=$anketa".$urejanje.$code);
}
?>