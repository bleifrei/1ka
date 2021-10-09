<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 22.01.2016
 *****************************************/

/**
 * Nastavimo vrsta okolja ali gre za produkcijo ali development/debugingp
 *  DEV: - prikažemo vse errorje
 *       - izpis spremenljiv z ukazom d() ali ddd()
 *       - blackscreen error display
 */
global $site_url;
if (in_array($site_url, [
    'http://localhost/',
    'http://1ka.dev/',
    'http://1ka-framework.dev/'
])) {
    define('ENVIRONMENT', 'dev');
} else {
    define('ENVIRONMENT', 'production');
}

// Error reporting
if (/*ENVIRONMENT == 'dev' ||*/ isDebug()) {
    error_reporting(E_ALL);
    ini_set("display_errors", 1);

    //ERROR blackscreen prikaz
    if (class_exists('\Whoops\Run)')) {
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        $whoops->register();
    }

} else {
    error_reporting(0);
    ini_set("display_errors", 0);
}


//DB connection
define('DB_TYPE', 'mysql');
define('DB_HOST', $mysql_server);
define('DB_NAME', $mysql_database_name);
define('DB_USER', $mysql_username);
define('DB_PASS', $mysql_password);

