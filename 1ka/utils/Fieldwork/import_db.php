<?php

$filename = '1ka_offline.sql';
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = '1ka';
$maxRuntime = 8; // less then your max script execution limit

$deadline = time()+$maxRuntime; 
$progressFilename = $filename.'_filepointer'; // tmp file for progress
$errorFilename = $filename.'_error'; // tmp file for erro

mysql_connect($dbHost, $dbUser, $dbPass);
mysql_select_db($dbName);
mysql_query ("SET foreign_key_checks = 0");
mysql_query ("DROP database 1ka");
mysql_query ("CREATE database 1ka");


$fp = fopen($filename, 'r');

// check for previous error
    if( file_exists($errorFilename) ){
        die('<pre> prejsnja napaka: '.file_get_contents($errorFilename));
    }

    // activate automatic reload in browser
    echo '<html><head> <meta http-equiv="refresh" content="'.($maxRuntime+2).'"><pre>';

    // go to previous file position
    $filePosition = 0;
    if( file_exists($progressFilename) ){
        $filePosition = file_get_contents($progressFilename);
        fseek($fp, $filePosition);
    }

    $queryCount = 0;
    $query = '';
    while( $deadline>time() AND ($line=fgets($fp, 1024000)) ) {
        if(substr($line,0,2)=='--' OR trim($line)=='' ){
            continue;
        }

        $query .= $line;
        if( substr(trim($query),-1)==';' ){
            if( !mysql_query($query) ){
                $error = 'Napaka pri uvozu podatkov: ' . mysql_error();
                file_put_contents($errorFilename, $error."\n");
                exit;
            }
            $query = '';
            file_put_contents($progressFilename, ftell($fp)); // save the current file position for 
            $queryCount++;
        }
    }

    if(feof($fp) ){
        
        // Zdaj pa še triggerji!
        mysql_query ("DROP TRIGGER IF EXISTS srv_anketa_zero");
        mysql_query ("CREATE TRIGGER srv_anketa_zero BEFORE DELETE ON srv_anketa FOR EACH ROW BEGIN DECLARE dummy INTEGER; IF OLD.id <= 0 THEN SELECT Cannot_delete_IDs_smaller_than_zero INTO dummy FROM srv_anketa; END IF; END");

        mysql_query ("DROP TRIGGER IF EXISTS srv_grupa_zero");
        mysql_query ("CREATE TRIGGER srv_grupa_zero BEFORE DELETE ON srv_grupa FOR EACH ROW BEGIN DECLARE dummy INTEGER; IF OLD.id <= 0 THEN SELECT Cannot_delete_IDs_smaller_than_zero INTO dummy FROM srv_grupa; END IF; END;");

        mysql_query ("DROP TRIGGER IF EXISTS srv_if_zero");
        mysql_query ("CREATE TRIGGER srv_if_zero BEFORE DELETE ON srv_if FOR EACH ROW BEGIN DECLARE dummy INTEGER; IF OLD.id <= 0 THEN SELECT Cannot_delete_IDs_smaller_than_zero INTO dummy FROM srv_if; END IF; END;");

        mysql_query ("DROP TRIGGER IF EXISTS srv_spremenljivka_zero");
        mysql_query ("CREATE TRIGGER srv_spremenljivka_zero BEFORE DELETE ON srv_spremenljivka FOR EACH ROW BEGIN DECLARE dummy INTEGER; IF OLD.id <= 0 THEN SELECT Cannot_delete_IDs_smaller_than_zero INTO dummy FROM srv_spremenljivka; END IF; END;");

        mysql_query ("DROP TRIGGER IF EXISTS srv_vrednost_zero");
        mysql_query ("CREATE TRIGGER srv_vrednost_zero BEFORE DELETE ON srv_vrednost FOR EACH ROW BEGIN DECLARE dummy INTEGER; IF OLD.id <= 0 THEN SELECT Cannot_delete_IDs_smaller_than_zero INTO dummy FROM srv_vrednost; END IF; END;");
        
        mysql_query ("DROP FUNCTION IF EXISTS MAX_RECNUM");
        mysql_query ("CREATE FUNCTION MAX_RECNUM (aid INT(11)) RETURNS INT(11) BEGIN DECLARE max INT(11); SELECT MAX(recnum) INTO max FROM srv_user WHERE ank_id = aid AND preview='0';  IF max IS NULL THEN SET max = '0' ; END IF; RETURN max+1; END;");


        
        
        
        echo 'Podatki uspesno uvozeni!';
    }else {
        echo ftell($fp).'/'.filesize($filename).' '.(round(ftell($fp)/filesize($filename), 2)*100).'%'."\n";
        echo $queryCount.' zahtevkov obdelanih! Prosimo, cakajte, podatki se uvazajo...';
    }