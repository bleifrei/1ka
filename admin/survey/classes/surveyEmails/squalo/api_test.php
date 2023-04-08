<?php
/**
 *
 *	Skripta za testiranje Squalo API-ja
 *
**/


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../../../definition.php';
include_once '../../../../../function.php';
include_once '../../../../../vendor/autoload.php';



//$squalo = new SqualoApi();

//$result = $squalo->createList($list_name='Testni seznam Xyz');

/*$result = $squalo->createNewsletter(
    $list_id = '11', 
    $subject = 'Testek', 
    $body = 'Testno sporočilo...', 
    $body_alt = 'Testno sporočilo ALT...', 
    $from_email = 'peter.hrvatin@gmail.com', 
    $from_name = 'Peter', 
    $reply_to_email = 'peter.hrvatin@gmail.com', 
    $language = 'sl'
);*/

//$result = $squalo->addRecipient($email='peter.h1203@gmail.com', $list_id='5');

//$result = $squalo->sendEmails($newsletter_id='24');

//$result = $squalo->deleteRecipient($recipient_id='2');




$squalo = new SurveyInvitationsSqualo($ank_id='10');


// Ustvarimo testni seznam z respondenti
/*$list_name = 'List test 1';
$recipients = array(
    array(
        'email' => 'peter.hrvatin@gmail.com',
        'custom_attributes' => array(
            'firstname' => 'Peterx',
            'lastname'  => 'Hrvax',
            'url'       => 'www.google.si'
        )
    ),
    array(
        'email' => 'peter.h1203@gmail.com',
        'custom_attributes' => array(
            'firstname' => 'Peter12',
            'lastname'  => 'Hrva12',
            'url'       => 'www.najdi.si'
        )
    ),
    array(
        'email' => 'peter.hrvatin@siol.net',
        'custom_attributes' => array(
            'firstname' => 'Petersiol',
            'lastname'  => 'Hrvaxsiol',
            'url'       => 'www.1ka.si'
        )
    )
);

$result = $squalo->createList($list_name, $recipients);*/


// Ustvarimo mail in posljemo
/*$list_id = '11';

$subject = 'Testni subject';
$body = 'Sporočilo v emailu... Še nekaj atributov url: #URL#, ime: #FIRSTNAME#';

$from_email = 'peter.hrvatin@gmail.com';
$from_name = 'Peter Gmail';
$reply_to_email = 'peter.hrvatin@gmail.com';

$result = $squalo->sendEmail($subject, $body, $list_id, $from_email, $from_name, $reply_to_email);*/




echo '<pre>'; print_r($result); echo '</pre>';



	