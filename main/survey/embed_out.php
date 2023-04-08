<?php

include_once('../../function.php');

$anketa_hash = $_GET['anketa'];
$grupa = $_GET['grupa'];

echo 'URI = '.$site_url.'main/survey/index.php?anketa='.$anketa_hash.'&grupa='.$grupa;

?>