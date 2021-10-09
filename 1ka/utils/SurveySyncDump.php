<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

include_once ('../function.php');
include_once ('../admin/survey/definition.php');
include_once ('../vendor/autoload.php');

// daj to v settings.php za tablice :-)

global $connect_db;
$data = true;

$ftp_server= $terminal_ftp_server;
$ftp_user_name = $terminal_ftp_user_name;
$ftp_user_pass = $terminal_ftp_user_pass;

$dostop = new Dostop();
?>
<html>
    <head>
        <title>Sinhronizacija podatkov</title>
    <meta charset="utf-8">
</head>
<body>
<?
echo 'Pripravljam se na sinhronizacijo podatkov...<br>';

foreach ($terminal_surveys as $anketa) {
    
    echo 'Preverjam dostop do ankete ' .$anketa .'...';
    SurveyInfo::getInstance()->SurveyInit($anketa);
    
    if ($dostop->checkDostop($anketa) == true) {
        
        echo 'OK<br>';
    
        SurveyCopy::setSrcSurvey($anketa);
        SurveyCopy::setSrcConectDb($connect_db);
        SurveyCopy::setDestSite(0);
        $dump = SurveyCopy::downloadArrayVar($data);


        $nd = array();
        foreach ($dump as $kljuc=>$vrednost) {
            $nd[$terminal_id ."||~||" .$terminal_secret][$kljuc] = $vrednost;
        }

        
        $fn = $terminal_id ."-" .$anketa .'-'.date("YmdHis").'.1ka';
        $fp = fopen($site_path .'admin/survey/SurveyBackup/' .$fn, 'w');
        fwrite($fp, serialize($nd));
        fclose($fp);
        
        echo 'Anketa je izvožena, pripravljam prenos na strežnik....';

        $file = $fn;
        $remote_file = $fn;

        // set up basic connection
        $conn_id = ftp_connect($ftp_server);
        $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
        ftp_pasv($conn_id, true);

        // upload a file
        if (ftp_put($conn_id, $remote_file, $site_path .'admin/survey/SurveyBackup/' .$file, FTP_ASCII)) {
            echo 'OK. <br>Prenos ankete je uspel.<br><br>';

        } else {
            echo 'NAPAKA. <br>Prenos ankete ni uspel. ALI STE PRIKLOPLJENI NA INTERNET?<br><br>';
        }

        // close the connection
        ftp_close($conn_id);
    }
    
    else {
        echo 'nimate dostopa, zato ne izvažam!<br><strong>Ali ste prijavljeni?</strong><br>';
    }
}
?>

    <br><br>Opravljeno.