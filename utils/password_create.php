<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 16.11.2016
 *****************************************/
include_once ("../function.php");
$geslo = 'admin';

global $pass_salt;
$password = base64_encode((hash(SHA256, $geslo .$pass_salt)));

sisplet_query("UPDATE users SET pass='".$password."' WHERE id=1045");