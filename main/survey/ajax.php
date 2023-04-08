<?php

include_once('../../function.php');
include_once('../../vendor/autoload.php');


// pod do /main/survey/ - kot ROOT direktorij
define('MAIN', __DIR__ . DIRECTORY_SEPARATOR);

//direktorij app
define('APP', MAIN . 'app' . DIRECTORY_SEPARATOR);


// Nastavitve, kjer določamo PHP debuging/error
require APP . 'config.php';
new App\Controllers\Controller();

// Definicija vseh spremenljiv, ki jih uporabljamo med razredi
new App\Controllers\VariableClass();
require_once(APP.'global_function.php');

// začetek aplikacije
new \App\Controllers\SurveyController(true);

// Poklicemo ustrezen ajax
if ($_GET['t'] == 'parapodatki') {
	SurveyAdvancedParadataLog::getInstance()->ajax();
}
else {
	new \App\Controllers\AjaxController();
}

?>