<?php

/**
* 
* prenese nastavitve aplikacije iz settings_optional.php v sql bazo
* 
*/


ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);


include_once('../../function.php');
include_once('../../vendor/autoload.php');
include_once('../../settings_optional.php');


global $site_domain;


// Debuging - 0 ali 1, ali imamo debug vključen (privzeto je izključen)
global $debug;
$sql = sisplet_query("INSERT INTO app_settings SET value='".$debug."', domain='".$site_domain."', what='debug'");


// tip instalacije (lastna - 0, www - 1, aai - 2, virtualka - 3)
global $virtual_domain;
global $lastna_instalacija;
global $aai_instalacija;

if($virtual_domain)
    sisplet_query("INSERT INTO app_settings SET value='3', domain='".$site_domain."',  what='installation_type'");
elseif($lastna_instalacija)
    sisplet_query("INSERT INTO app_settings SET value='0', domain='".$site_domain."', what='installation_type'");
elseif($aai_instalacija)
    sisplet_query("INSERT INTO app_settings SET value='2', domain='".$site_domain."', what='installation_type'");
else
    sisplet_query("INSERT INTO app_settings SET value='1', domain='".$site_domain."', what='installation_type'");



/* DODATNE NASTAVITVE APLIKACIJE ZA LASTNE NAMESTITVE */
global $app_settings;

sisplet_query("INSERT INTO app_settings SET value='".$app_settings['app_name']."', domain='".$site_domain."', what='app_settings-app_name'");
sisplet_query("INSERT INTO app_settings SET value='".$app_settings['admin_email']."', domain='".$site_domain."', what='app_settings-admin_email'");
sisplet_query("INSERT INTO app_settings SET value='".$app_settings['owner']."', domain='".$site_domain."', what='app_settings-owner'");
sisplet_query("INSERT INTO app_settings SET value='".$app_settings['owner_website']."', domain='".$site_domain."', what='app_settings-owner_website'");

sisplet_query("INSERT INTO app_settings SET value='".$app_settings['footer_custom']."', domain='".$site_domain."', what='app_settings-footer_custom'");
sisplet_query("INSERT INTO app_settings SET value='".$app_settings['footer_text']."', domain='".$site_domain."', what='app_settings-footer_text'");
sisplet_query("INSERT INTO app_settings SET value='".$app_settings['footer_survey_custom']."', domain='".$site_domain."', what='app_settings-footer_survey_custom'");
sisplet_query("INSERT INTO app_settings SET value='".$app_settings['footer_survey_text']."', domain='".$site_domain."', what='app_settings-footer_survey_text'");

sisplet_query("INSERT INTO app_settings SET value='".$app_settings['email_signature_custom']."', domain='".$site_domain."', what='app_settings-email_signature_custom'");
sisplet_query("INSERT INTO app_settings SET value='".$app_settings['email_signature_text']."', domain='".$site_domain."', what='app_settings-email_signature_text'");

sisplet_query("INSERT INTO app_settings SET value='".$app_settings['survey_finish_url']."', domain='".$site_domain."', what='app_settings-survey_finish_url'");

//sisplet_query("INSERT INTO app_settings SET value='".$app_settings['admin_allow_only_ip']."', domain='".$site_domain."', what='app_settings-admin_allow_only_ip'");

sisplet_query("INSERT INTO app_settings SET value='".$app_settings['export_type']."', domain='".$site_domain."', what='app_settings-export_type'");

sisplet_query("INSERT INTO app_settings SET value='".$app_settings['commercial_packages']."', domain='".$site_domain."', what='app_settings-commercial_packages'");


/* OMEJITVE APLIKACIJE */
global $app_limits;

sisplet_query("INSERT INTO app_settings SET value='".$app_limits['clicks_per_minute_limit']."', domain='".$site_domain."', what='app_limits-clicks_per_minute_limit'");
sisplet_query("INSERT INTO app_settings SET value='".$app_limits['question_count_limit']."', domain='".$site_domain."', what='app_limits-question_count_limit'");
sisplet_query("INSERT INTO app_settings SET value='".$app_limits['response_count_limit']."', domain='".$site_domain."', what='app_limits-response_count_limit'");
sisplet_query("INSERT INTO app_settings SET value='".$app_limits['invitation_count_limit']."', domain='".$site_domain."', what='app_limits-invitation_count_limit'");

if(is_array($$app_settings['admin_allow_only_ip']))
    $admin_allow_only_ip_string = implode(',', $app_settings['admin_allow_only_ip']);
else
    $admin_allow_only_ip_string = $app_settings['admin_allow_only_ip'];

sisplet_query("INSERT INTO app_settings SET value='".$admin_allow_only_ip_string."', domain='".$site_domain."', what='app_limits-admin_allow_only_ip'");  // !!!!


// Nastavitev email streznika za posiljanje mailov
global $email_server_settings;
global $email_server_fromSurvey;

sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['SMTPFrom']."', domain='".$site_domain."', what='email_server_settings-SMTPFrom'");
sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['SMTPFromNice']."', domain='".$site_domain."', what='email_server_settings-SMTPFromNice'");
sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['SMTPReplyTo']."', domain='".$site_domain."', what='email_server_settings-SMTPReplyTo'");
sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['SMTPHost']."', domain='".$site_domain."', what='email_server_settings-SMTPHost'");
sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['SMTPPort']."', domain='".$site_domain."', what='email_server_settings-SMTPPort'");
sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['SMTPSecure']."', domain='".$site_domain."', what='email_server_settings-SMTPSecure'");
sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['SMTPAuth']."', domain='".$site_domain."', what='email_server_settings-SMTPAuth'");
sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['SMTPUsername']."', domain='".$site_domain."', what='email_server_settings-SMTPUsername'");
sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['SMTPPassword']."', domain='".$site_domain."', what='email_server_settings-SMTPPassword'");

sisplet_query("INSERT INTO app_settings SET value='".$email_server_fromSurvey."', domain='".$site_domain."', what='email_server_fromSurvey'");

if(isset($email_server_settings['secondary_mail'])){
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['secondary_mail']['SMTPFrom']."', domain='".$site_domain."', what='email_server_settings-secondary_mail-SMTPFrom'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['secondary_mail']['SMTPFromNice']."', domain='".$site_domain."', what='email_server_settings-secondary_mail-SMTPFromNice'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['secondary_mail']['SMTPReplyTo']."', domain='".$site_domain."', what='email_server_settings-secondary_mail-SMTPReplyTo'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['secondary_mail']['SMTPHost']."', domain='".$site_domain."', what='email_server_settings-secondary_mail-SMTPHost'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['secondary_mail']['SMTPPort']."', domain='".$site_domain."', what='email_server_settings-secondary_mail-SMTPPort'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['secondary_mail']['SMTPSecure']."', domain='".$site_domain."', what='email_server_settings-secondary_mail-SMTPSecure'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['secondary_mail']['SMTPAuth']."', domain='".$site_domain."', what='email_server_settings-secondary_mail-SMTPAuth'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['secondary_mail']['SMTPUsername']."', domain='".$site_domain."', what='email_server_settings-secondary_mail-SMTPUsername'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['secondary_mail']['SMTPPassword']."', domain='".$site_domain."', what='email_server_settings-secondary_mail-SMTPPassword'");
}

if(isset($email_server_settings['payments_mail'])){
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['payments_mail']['SMTPFrom']."', domain='".$site_domain."', what='email_server_settings-payments_mail-SMTPFrom'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['payments_mail']['SMTPFromNice']."', domain='".$site_domain."', what='email_server_settings-payments_mail-SMTPFromNice'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['payments_mail']['SMTPReplyTo']."', domain='".$site_domain."', what='email_server_settings-payments_mail-SMTPReplyTo'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['payments_mail']['SMTPHost']."', domain='".$site_domain."', what='email_server_settings-payments_mail-SMTPHost'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['payments_mail']['SMTPPort']."', domain='".$site_domain."', what='email_server_settings-payments_mail-SMTPPort'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['payments_mail']['SMTPSecure']."', domain='".$site_domain."', what='email_server_settings-payments_mail-SMTPSecure'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['payments_mail']['SMTPAuth']."', domain='".$site_domain."', what='email_server_settings-payments_mail-SMTPAuth'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['payments_mail']['SMTPUsername']."', domain='".$site_domain."', what='email_server_settings-payments_mail-SMTPUsername'");
    sisplet_query("INSERT INTO app_settings SET value='".$email_server_settings['payments_mail']['SMTPPassword']."', domain='".$site_domain."', what='email_server_settings-payments_mail-SMTPPassword'");
}


/* DODATNE NASTAVITVE APLIKACIJE ZA ADMINISTRATORJE */
global $confirm_registration;
global $confirm_registration_admin;
global $gdpr_admin_email;

if(is_array($confirm_registration_admin))
    $confirm_registration_admin_string = implode(',', $confirm_registration_admin);
else
    $confirm_registration_admin_string = $confirm_registration_admin;

sisplet_query("INSERT INTO app_settings SET value='".$confirm_registration."', domain='".$site_domain."', what='confirm_registration'");
sisplet_query("INSERT INTO app_settings SET value='".$confirm_registration_admin_string."', domain='".$site_domain."', what='confirm_registration_admin'");
sisplet_query("INSERT INTO app_settings SET value='".$gdpr_admin_email."', domain='".$site_domain."', what='gdpr_admin_email'");


/* METAADMINI - opcijsko */
/*
// WWW - vvasja@gmail.com - 100, peter.h1203@gmail.com - 12611, 1ka.techsupport - 72253
$meta_admin_ids = array('100', '12611', '72253');

// VIRTUALKE - vvasja@gmail.com - 100, peter.h1203@gmail.com - 12611, 1ka.techsupport - 49089
$meta_admin_ids = array('100', '12611', '49089');

// AAI - Manca Jeras - 13147, Klavdija Mezek - 1417
$meta_admin_ids = array('1417', '13147');

// LOCALHOST
$meta_admin_ids = array('1046');
*/
global $meta_admin_ids;

if(is_array($meta_admin_ids))
    $meta_admin_ids_string = implode(',', $meta_admin_ids);
else
    $meta_admin_ids_string = $meta_admin_ids;
    
sisplet_query("INSERT INTO app_settings SET value='".$meta_admin_ids_string."', domain='".$site_domain."', what='meta_admin_ids'");


/* DODATNE FUNKCIONALNOSTI APLIKACIJE - GOOGLE */
global $recaptcha_sitekey;
global $secret_captcha;
global $google_login_client_id;
global $google_login_client_secret;
global $google_maps_API_key;

if($recaptcha_sitekey != '')
    sisplet_query("INSERT INTO app_settings SET value='".$recaptcha_sitekey."', domain='".$site_domain."', what='google-recaptcha_sitekey'");
if($secret_captcha != '')
    sisplet_query("INSERT INTO app_settings SET value='".$secret_captcha."', domain='".$site_domain."', what='google-secret_captcha'");
if($google_login_client_id != '')
    sisplet_query("INSERT INTO app_settings SET value='".$google_login_client_id."', domain='".$site_domain."', what='google-login_client_id'");
if($google_login_client_secret != '')
    sisplet_query("INSERT INTO app_settings SET value='".$google_login_client_secret."', domain='".$site_domain."', what='google-login_client_secret'");
if($google_maps_API_key != '')
    sisplet_query("INSERT INTO app_settings SET value='".$google_maps_API_key."', domain='".$site_domain."', what='google-maps_API_key'");


/* DODATNE FUNKCIONALNOSTI APLIKACIJE - FB */
global $facebook_appid;
global $facebook_appsecret;

if($facebook_appid != '')
    sisplet_query("INSERT INTO app_settings SET value='".$facebook_appid."', domain='".$site_domain."', what='facebook-appid'");
if($facebook_appid != '')
    sisplet_query("INSERT INTO app_settings SET value='".$facebook_appsecret."', domain='".$site_domain."', what='facebook-appsecret'");


/**
 * Potrebno za delovanje modula "maza"
 * Firebase Cloud Messaging server key is needed to work with module "Mobile aplication for respondents"
 * NextPinMainToken and password is needed to work with NextPin API
 */
global $FCM_server_key;
global $APP_special_login_key;
global $NextPinMainToken;
global $NextPinMainPassword;

if($FCM_server_key != '')
    sisplet_query("INSERT INTO app_settings SET value='".$FCM_server_key."', domain='".$site_domain."', what='maza-FCM_server_key'");
if($APP_special_login_key != '')
    sisplet_query("INSERT INTO app_settings SET value='".$APP_special_login_key."', domain='".$site_domain."', what='maza-APP_special_login_key'");
if($NextPinMainToken != '')
    sisplet_query("INSERT INTO app_settings SET value='".$NextPinMainToken."', domain='".$site_domain."', what='maza-NextPinMainToken'");
if($NextPinMainPassword != '')
    sisplet_query("INSERT INTO app_settings SET value='".$NextPinMainPassword."', domain='".$site_domain."', what='maza-NextPinMainPassword'");

/**
 * Nastavitve hierarhije
 */
global $hierarhija_folder_id;
global $hierarhija_default_id;

if($hierarhija_folder_id != '')
    sisplet_query("INSERT INTO app_settings SET value='".$hierarhija_folder_id."', domain='".$site_domain."', what='hierarhija-folder_id'");
if($hierarhija_default_id != '')
    sisplet_query("INSERT INTO app_settings SET value='".$hierarhija_default_id."', domain='".$site_domain."', what='hierarhija-default_id'");


/**
 * Squalo API za vabila
*/
global $squalo_user;
global $squalo_key;

if($squalo_user != '')
    sisplet_query("INSERT INTO app_settings SET value='".$squalo_user."', domain='".$site_domain."', what='squalo-user'");
if($squalo_key != '')
    sisplet_query("INSERT INTO app_settings SET value='".$squalo_key."', domain='".$site_domain."', what='squalo-key'");


/* PLACEVANJE */
/**
 * Izdajanje racunov preko cebelice
 */
global $cebelica_api;

if($cebelica_api != '')
    sisplet_query("INSERT INTO app_settings SET value='".$cebelica_api."', domain='".$site_domain."', what='cebelica_api'");


/**
 * Stripe za placevanje s kreditno kartico
 */
global $stripe_key;
global $stripe_secret;

if($stripe_key != '')
    sisplet_query("INSERT INTO app_settings SET value='".$stripe_key."', domain='".$site_domain."', what='stripe-key'");
if($stripe_secret != '')
    sisplet_query("INSERT INTO app_settings SET value='".$stripe_secret."', domain='".$site_domain."', what='stripe-secret'");


/**
 * Placevanje s paypalom
 */
global $paypal_account;
global $paypal_client_id;
global $paypal_secret;

if($paypal_account != '')
    sisplet_query("INSERT INTO app_settings SET value='".$paypal_account."', domain='".$site_domain."', what='paypal-account'");
if($paypal_client_id != '')
    sisplet_query("INSERT INTO app_settings SET value='".$paypal_client_id."', domain='".$site_domain."', what='paypal-client_id'");
if($paypal_secret != '')
    sisplet_query("INSERT INTO app_settings SET value='".$paypal_secret."', domain='".$site_domain."', what='paypal-secret'");

    

echo 'settings_optional.php spremenljivke uspešno prenešene v bazo';


?>