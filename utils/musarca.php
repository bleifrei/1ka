<?php

/**********************************************
 * 
 * v .htaccess moraš dodati
 * 
 * /cookie_da => utils/musarca.php?c=1
 * /cookie_ne => utils/musarca.php?c=0
 * 
 */


if ($_GET['c']=="1") {
	// setcookie,...
	setcookie ('AllowCookie', '1', time()+(3600*24*365*10), '/');
}
else {
	// setcookie....
	setcookie ('AllowCookie', '0', time()+(3600*24*365*10), '/');
}
header ('location: /index.php');
?>
