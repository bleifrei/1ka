<?php
    include ('../settings.php');
    include ('../function.php');

    $len = 200;
    $charset = "3g98h%$&/()=+*?-_.:0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";

    $moja = "";

    for ($a=0; $a<200; $a++) {
            $moja .= $charset[rand(0, strlen($charset))];
    }

  
  $njegova = (str_replace ("'", "", base64_decode($_GET['s'])));
  
  sisplet_query ("DELETE FROM aai_prenosi WHERE timestamp < (UNIX_TIMESTAMP() - 600);");
  sisplet_query ("INSERT INTO aai_prenosi (timestamp, moja, njegova) VALUES (UNIX_TIMESTAMP(), '" .$moja ."', '" .$njegova ."')");
  
  die ($moja);


