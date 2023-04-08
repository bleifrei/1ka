<?php

include_once('../../function.php');
include_once '../../vendor/autoload.php';

echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

';

echo '<head>'."\n\r";
echo '  <title>OneClick Survey</title>'."\n\r";
echo '  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n\r";
echo '</head>';

$anketa_hash = $_GET['anketa'];
$anketa = getSurveyIdFromHash($anketa_hash);

$sql = sisplet_query("SELECT uporabnost_link FROM srv_anketa WHERE id = '$anketa'");
$row = mysqli_fetch_array($sql);

SurveySetting::getInstance()->Init($anketa);
$uporabnost_razdeli = SurveySetting::getInstance()->getSurveyMiscSetting('uporabnost_razdeli');

if (true || ($row['uporabnost_link'] != null && $row['uporabnost_link'] != "")) {
	echo '
	<frameset '.($uporabnost_razdeli!=1?'rows':'cols').'="50%,50%">
	    <frame name="link" src="'.(strlen($row['uporabnost_link'])>7?$row['uporabnost_link']:'').'">
	    <frame name="survey" src="'.$site_url.'main/survey/index.php?anketa='.$anketa_hash.''.($_GET['preview']=='on'?'&preview=on':'').''.(isset($_GET['sist_link'])?'&sist_link='.$_GET['sist_link']:'').(isset($_GET['code'])?'&code='.$_GET['code']:'').'">
	</frameset>
	';	
} else {
	echo '
	<frameset>
	    <frame name="survey" src="'.$site_url.'main/survey/index.php?anketa='.$anketa_hash.''.($_GET['preview']=='on'?'&preview=on':'').''.(isset($_GET['sist_link'])?'&sist_link='.$_GET['sist_link']:'').(isset($_GET['code'])?'&code='.$_GET['code']:'').'">
	</frameset>
	';
}

echo '
</body>
</html>';

?>