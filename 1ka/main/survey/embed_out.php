<?php

include_once('../../function.php');
$anketa = $_GET['anketa'];
$grupa = $_GET['grupa'];

echo 'URI = '.$site_url.'main/survey/index.php?anketa='.$anketa.'&grupa='.$grupa;

?>