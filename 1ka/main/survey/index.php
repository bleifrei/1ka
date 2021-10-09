<?php

// pod do /main/survey/ - kot ROOT direktorij
define('MAIN', __DIR__ . DIRECTORY_SEPARATOR);

//direktorij app
define('APP', MAIN . 'app' . DIRECTORY_SEPARATOR);


require_once('../../function.php');

// Register composer autoload
require_once('../../vendor/autoload.php');


// Nastavitve, kjer določamo PHP debuging/error
require APP . 'config.php';
new App\Controllers\Controller();

// Definicija vseh spremenljiv, ki jih uporabljamo med razredi
new App\Controllers\VariableClass();
require_once(APP.'global_function.php');


// Začasno vpisemo, ker View še ni posebej
ob_start();

header('Cache-Control: no-cache');
header('Pragma: no-cache');

echo '<!DOCTYPE html><html class="no_js">';

// začetek aplikacije
new \App\Controllers\SurveyController();

echo '</body>';
echo '</html>';

ob_flush();

?>
