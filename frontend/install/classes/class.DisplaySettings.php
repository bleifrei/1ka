<?php

	
class DisplaySettings{


	function __construct(){

	}
    
    
    // Izris strani za preverjanje konfiguracije streznika, baze
	public function displaySettingsPage(){
        global $lang;

        echo '<h2>'.$lang['install_settings_title'].'</h2>';

        echo '<p>'.$lang['install_settings_text'].'</p><br/>';
    

        echo '<form name="settings_form" id="settings_form" action="index.php?step=settings" method="post">';
        
        // SEGMENT 1 - app_settings
        echo '<div class="settings_segment app">';
        $this->displaySettingsApp();
        echo '</div>';

        // SEGMENT 2 - email
        echo '<div class="settings_segment email">';
        $this->displaySettingsEmail();
        echo '</div>';

        // SEGMENT 3 - google
        echo '<div class="settings_segment google">';
        $this->displaySettingsGoogle();
        echo '</div>';

        // SEGMENT 3 - subscribe
        echo '<div class="settings_segment subscribe">';
        $this->displaySettingsSubscribe();
        echo '</div>';
        
        // Submit
        echo '<div class="bottom_buttons">';
        echo '  <a href="index.php?step=check"><input name="back" value="'.$lang['back'].'" type="button"></a>';
        echo '  <a href="#" onClick="settingsSubmit();"><input name="submit" value="'.$lang['next1'].'" type="button"></a>';
        echo '</div>';
        
        echo '</form>';


        // Skrijemo nepotrebna text polja
        echo '<script>settingsToggle();</script>';
    }

    private function displaySettingsApp(){
        global $lang;
        global $app_settings;
        global $confirm_registration;

        echo '<h3>'.$lang['install_settings_app_title'].'</h3>';

        // Ime aplikacije
        echo '<div class="settings_item text">';
        echo '  <div class="what">'.$lang['install_settings_app_name'].':</div>';
        echo '  <div class="value"><input type="text" name="app_name" value="'.$app_settings['app_name'].'"></div>';
        echo '</div>';

        // Admin email
        echo '<div class="settings_item text">';
        echo '  <div class="what">'.$lang['install_settings_admin_email'].':</div>';
        echo '  <div class="value"><input type="text" name="admin_email" value="'.$app_settings['admin_email'].'"></div>';
        echo '</div>';

        // Owner
        echo '<div class="settings_item text">';
        echo '  <div class="what">'.$lang['install_settings_owner'].':</div>';
        echo '  <div class="value"><input type="text" name="owner" value="'.$app_settings['owner'].'"></div>';
        echo '</div>';

        // Owner website
        echo '<div class="settings_item text">';
        echo '  <div class="what">'.$lang['install_settings_owner_website'].':</div>';
        echo '  <div class="value"><input type="text" name="owner_website" value="'.$app_settings['owner_website'].'"></div>';
        echo '</div>';


        // Custom head title
        echo '<div class="settings_item radio head_title_custom">';
        echo '  <div class="what">'.$lang['install_settings_head_title_custom'].':</div>';
        echo '  <div class="value">';
        echo '      <input type="radio" name="head_title_custom" id="head_title_custom_0" value="0" '.($app_settings['head_title_custom'] != '1' ? 'checked="checked"' : '').' onClick="settingsToggle();"><label for="head_title_custom_0">'.$lang['no'].'</label>';
        echo '      <input type="radio" name="head_title_custom" id="head_title_custom_1" value="1" '.($app_settings['head_title_custom'] == '1' ? 'checked="checked"' : '').' onClick="settingsToggle();"><label for="head_title_custom_1">'.$lang['yes'].'</label>';
        echo '  </div>';
        echo '</div>';

        // Custom head title text
        echo '<div class="settings_item text head_title_text">';
        echo '  <div class="what">'.$lang['install_settings_head_title_text'].':</div>';
        echo '  <div class="value"><input type="text" name="head_title_text" value="'.$app_settings['head_title_text'].'"></div>';
        echo '</div>';


        // Custom foooter
        echo '<div class="settings_item radio footer_custom">';
        echo '  <div class="what">'.$lang['install_settings_footer_custom'].':</div>';
        echo '  <div class="value">';
        echo '      <input type="radio" name="footer_custom" id="footer_custom_0" value="0" '.($app_settings['footer_custom'] != '1' ? 'checked="checked"' : '').' onClick="settingsToggle();"><label for="footer_custom_0">'.$lang['no'].'</label>';
        echo '      <input type="radio" name="footer_custom" id="footer_custom_1" value="1" '.($app_settings['footer_custom'] == '1' ? 'checked="checked"' : '').' onClick="settingsToggle();"><label for="footer_custom_1">'.$lang['yes'].'</label>';
        echo '  </div>';
        echo '</div>';

        // Custom footer text
        echo '<div class="settings_item text footer_text">';
        echo '  <div class="what">'.$lang['install_settings_footer_text'].':</div>';
        echo '  <div class="value"><input type="text" name="footer_text" value="'.$app_settings['footer_text'].'"></div>';
        echo '</div>';


        // Custom survey foooter
        echo '<div class="settings_item radio footer_survey_custom">';
        echo '  <div class="what">'.$lang['install_settings_footer_survey_custom'].':</div>';
        echo '  <div class="value">';
        echo '      <input type="radio" name="footer_survey_custom" id="footer_survey_custom_0" value="0" '.($app_settings['footer_survey_custom'] != '1' ? 'checked="checked"' : '').' onClick="settingsToggle();"><label for="footer_survey_custom_0">'.$lang['no'].'</label>';
        echo '      <input type="radio" name="footer_survey_custom" id="footer_survey_custom_1" value="1" '.($app_settings['footer_survey_custom'] == '1' ? 'checked="checked"' : '').' onClick="settingsToggle();"><label for="footer_survey_custom_1">'.$lang['yes'].'</label>';
        echo '  </div>';
        echo '</div>';

        // Custom footer survey text
        echo '<div class="settings_item text footer_survey_text">';
        echo '  <div class="what">'.$lang['install_settings_footer_survey_text'].':</div>';
        echo '  <div class="value"><input type="text" name="footer_survey_text" value="'.$app_settings['footer_survey_text'].'"></div>';
        echo '</div>';


        // Custom email sig
        echo '<div class="settings_item radio email_signature_custom">';
        echo '  <div class="what">'.$lang['install_settings_email_signature_custom'].':</div>';
        echo '  <div class="value">';
        echo '      <input type="radio" name="email_signature_custom" id="email_signature_custom_0" value="0" '.($app_settings['email_signature_custom'] != '1' ? 'checked="checked"' : '').' onClick="settingsToggle();"><label for="email_signature_custom_0">'.$lang['no'].'</label>';
        echo '      <input type="radio" name="email_signature_custom" id="email_signature_custom_1" value="1" '.($app_settings['email_signature_custom'] == '1' ? 'checked="checked"' : '').' onClick="settingsToggle();"><label for="email_signature_custom_1">'.$lang['yes'].'</label>';
        echo '  </div>';
        echo '</div>';

        // Custom email sig text
        echo '<div class="settings_item text email_signature_text">';
        echo '  <div class="what">'.$lang['install_settings_email_signature_text'].':</div>';
        echo '  <div class="value"><input type="text" name="email_signature_text" value="'.$app_settings['email_signature_text'].'"></div>';
        echo '</div>';


        // Survey finish url
        echo '<div class="settings_item text">';
        echo '  <div class="what">'.$lang['install_settings_survey_finish_url'].':</div>';
        echo '  <div class="value"><input type="text" name="survey_finish_url" value="'.$app_settings['survey_finish_url'].'"></div>';
        echo '</div>';


        // Export type
        echo '<div class="settings_item radio">';
        echo '  <div class="what">'.$lang['install_settings_export_type'].':</div>';
        echo '  <div class="value">';
        echo '      <input type="radio" name="export_type" id="export_type_0" value="old" '.($app_settings['export_type'] != 'new' ? 'checked="checked"' : '').'><label for="export_type_0">'.$lang['install_settings_export_type_0'].'</label>';
        echo '      <input type="radio" name="export_type" id="export_type_1" value="new" '.($app_settings['export_type'] == 'new' ? 'checked="checked"' : '').'><label for="export_type_1">'.$lang['install_settings_export_type_1'].'</label>';
        echo '  </div>';
        echo '</div>';


        // confirm_registration
        echo '<div class="settings_item radio">';
        echo '  <div class="what">'.$lang['install_settings_confirm_registration'].':</div>';
        echo '  <div class="value">';
        echo '      <input type="radio" name="confirm_registration" id="confirm_registration_0" value="0" '.($confirm_registration != '1' ? 'checked="checked"' : '').'><label for="confirm_registration_0">'.$lang['no'].'</label>';
        echo '      <input type="radio" name="confirm_registration" id="confirm_registration_1" value="1" '.($confirm_registration == '1' ? 'checked="checked"' : '').'><label for="confirm_registration_1">'.$lang['yes'].'</label>';
        echo '  </div>';
        echo '</div>';
    }

    private function displaySettingsEmail(){
        global $lang;
        global $email_server_settings;

        echo '<h3>'.$lang['install_settings_email_title'].'</h3>';

        // Email SMTPFrom
        echo '<div class="settings_item text">';
        echo '  <div class="what">'.$lang['install_settings_SMTPFrom'].':</div>';
        echo '  <div class="value"><input type="text" name="SMTPFrom" value="'.$email_server_settings['SMTPFrom'].'"></div>';
        echo '</div>';

        // Email SMTPFromNice
        echo '<div class="settings_item text">';
        echo '  <div class="what">'.$lang['install_settings_SMTPFromNice'].':</div>';
        echo '  <div class="value"><input type="text" name="SMTPFrom" value="'.$email_server_settings['SMTPFromNice'].'"></div>';
        echo '</div>';

        // Email SMTPReplyTo
        echo '<div class="settings_item text">';
        echo '  <div class="what">'.$lang['install_settings_SMTPReplyTo'].':</div>';
        echo '  <div class="value"><input type="text" name="SMTPReplyTo" value="'.$email_server_settings['SMTPReplyTo'].'"></div>';
        echo '</div>';

        // Email SMTPHost
        echo '<div class="settings_item text">';
        echo '  <div class="what">'.$lang['install_settings_SMTPHost'].':</div>';
        echo '  <div class="value"><input type="text" name="SMTPHost" value="'.$email_server_settings['SMTPHost'].'"></div>';
        echo '</div>';

        // Email SMTPPort
        echo '<div class="settings_item text">';
        echo '  <div class="what">'.$lang['install_settings_SMTPPort'].':</div>';
        echo '  <div class="value"><input type="text" name="SMTPPort" value="'.$email_server_settings['SMTPPort'].'"></div>';
        echo '</div>';


        // Email SMTPAuth
        echo '<div class="settings_item radio">';
        echo '  <div class="what">'.$lang['install_settings_SMTPAuth'].':</div>';
        echo '  <div class="value">';
        echo '      <input type="radio" name="SMTPAuth" id="SMTPAuth_0" value="0" '.($email_server_settings['SMTPAuth'] != '1' ? 'checked="checked"' : '').'><label for="SMTPAuth_0">'.$lang['no'].'</label>';
        echo '      <input type="radio" name="SMTPAuth" id="SMTPAuth_1" value="1" '.($email_server_settings['SMTPAuth'] == '1' ? 'checked="checked"' : '').'><label for="SMTPAuth_1">'.$lang['yes'].'</label>';
        echo '  </div>';
        echo '</div>';

        // Email SMTPSecure
        echo '<div class="settings_item radio">';
        echo '  <div class="what">'.$lang['install_settings_SMTPSecure'].':</div>';
        echo '  <div class="value">';
        echo '      <input type="radio" name="SMTPSecure" id="SMTPSecure_0" value="0" '.($email_server_settings['SMTPSecure'] != 'ssl' && $email_server_settings['SMTPSecure'] != 'tls' ? 'checked="checked"' : '').'><label for="SMTPSecure_0">'.$lang['no'].'</label>';
        echo '      <input type="radio" name="SMTPSecure" id="SMTPSecure_1" value="ssl" '.($email_server_settings['SMTPSecure'] == 'ssl' ? 'checked="checked"' : '').'><label for="SMTPSecure_1">SSL</label>';
        echo '      <input type="radio" name="SMTPSecure" id="SMTPSecure_2" value="tls" '.($email_server_settings['SMTPSecure'] == 'tls' ? 'checked="checked"' : '').'><label for="SMTPSecure_2">TLS</label>';
        echo '  </div>';
        echo '</div>';


        // Email SMTPUsername
        echo '<div class="settings_item text">';
        echo '  <div class="what">'.$lang['install_settings_SMTPUsername'].':</div>';
        echo '  <div class="value"><input type="text" name="SMTPUsername" value="'.$email_server_settings['SMTPUsername'].'"></div>';
        echo '</div>';

        // Email SMTPPassword
        echo '<div class="settings_item text">';
        echo '  <div class="what">'.$lang['install_settings_SMTPPassword'].':</div>';
        echo '  <div class="value"><input type="text" name="SMTPPassword" value="'.$email_server_settings['SMTPPassword'].'"></div>';
        echo '</div>';
    }

    private function displaySettingsGoogle(){
        global $lang;
        global $recaptcha_sitekey;
        global $secret_captcha;
        global $google_maps_API_key;

        echo '<h3>'.$lang['install_settings_google_title'].'</h3>';

        // Google recaptcha_sitekey
        echo '<div class="settings_item text">';
        echo '  <div class="what">'.$lang['install_settings_recaptcha_sitekey'].':</div>';
        echo '  <div class="value"><input type="text" name="recaptcha_sitekey" value="'.$recaptcha_sitekey.'"></div>';
        echo '</div>';      

        // Google secret_captcha
        echo '<div class="settings_item text">';
        echo '  <div class="what">'.$lang['install_settings_secret_captcha'].':</div>';
        echo '  <div class="value"><input type="text" name="secret_captcha" value="'.$secret_captcha.'"></div>';
        echo '</div>';     

        // Google google_maps_API_key
        echo '<div class="settings_item text">';
        echo '  <div class="what">'.$lang['install_settings_google_maps_API_key'].':</div>';
        echo '  <div class="value"><input type="text" name="google_maps_API_key" value="'.$google_maps_API_key.'"></div>';
        echo '</div>'; 
    }

    private function displaySettingsSubscribe(){
        global $lang;

        echo '<h3>'.$lang['install_settings_subscribe_title'].'</h3>';
        
        echo '<p>'.$lang['install_settings_subscribe_text'].'</p>';

        echo '<div class="settings_item radio">';
        echo '  <div class="what">'.$lang['install_settings_subscribe_radio'].':</div>';
        echo '  <div class="value">';
        echo '      <input type="radio" name="subscribe" id="subscribe_1" value="1" checked="checked"><label for="subscribe_1">'.$lang['yes'].'</label>';
        echo '      <input type="radio" name="subscribe" id="subscribe_0" value="0"><label for="subscribe_0">'.$lang['no'].'</label>';
        echo '  </div>';
        echo '</div>';
    }


    // Shranimo nastavitve v settings_optional.php in redirectamo
    public function ajaxSubmitSettings(){

        $new_content = '<?php'.PHP_EOL.PHP_EOL;


        // Prednastavljena polja
        $new_content .= '$debug = \'0\';'.PHP_EOL;
        $new_content .= '$lastna_instalacija = \'1\';'.PHP_EOL;
        $new_content .= '$email_server_fromSurvey = \'1\';'.PHP_EOL.PHP_EOL;
                

        // $app_settings
        $new_content .= '$app_settings = array('.PHP_EOL;

        $app_name = isset($_POST['app_name']) ? $_POST['app_name'] : '';
        $new_content .= ' \'app_name\' => \''.$app_name.'\','.PHP_EOL;

        $admin_email = isset($_POST['admin_email']) ? $_POST['admin_email'] : '';
        $new_content .= ' \'admin_email\' => \''.$admin_email.'\','.PHP_EOL;

        $owner = isset($_POST['owner']) ? $_POST['owner'] : '';
        $new_content .= ' \'owner\' => \''.$owner.'\','.PHP_EOL;

        $owner_website = isset($_POST['owner_website']) ? $_POST['owner_website'] : '';
        $new_content .= ' \'owner_website\' => \''.$owner_website.'\','.PHP_EOL;

        $head_title_custom = isset($_POST['head_title_custom']) ? $_POST['head_title_custom'] : '';
        $new_content .= ' \'head_title_custom\' => \''.$head_title_custom.'\','.PHP_EOL;

        $head_title_text = isset($_POST['head_title_text']) ? $_POST['head_title_text'] : '';
        $new_content .= ' \'head_title_text\' => \''.$head_title_text.'\','.PHP_EOL;

        $footer_custom = isset($_POST['footer_custom']) ? $_POST['footer_custom'] : '';
        $new_content .= ' \'footer_custom\' => \''.$footer_custom.'\','.PHP_EOL;

        $footer_text = isset($_POST['footer_text']) ? $_POST['footer_text'] : '';
        $new_content .= ' \'footer_text\' => \''.$footer_text.'\','.PHP_EOL;

        $footer_survey_custom = isset($_POST['footer_survey_custom']) ? $_POST['footer_survey_custom'] : '';
        $new_content .= ' \'footer_survey_custom\' => \''.$footer_survey_custom.'\','.PHP_EOL;

        $footer_survey_text = isset($_POST['footer_survey_text']) ? $_POST['footer_survey_text'] : '';
        $new_content .= ' \'footer_survey_text\' => \''.$footer_survey_text.'\','.PHP_EOL;

        $email_signature_custom = isset($_POST['email_signature_custom']) ? $_POST['email_signature_custom'] : '';
        $new_content .= ' \'email_signature_custom\' => \''.$email_signature_custom.'\','.PHP_EOL;

        $email_signature_text = isset($_POST['email_signature_text']) ? $_POST['email_signature_text'] : '';
        $new_content .= ' \'email_signature_text\' => \''.$email_signature_text.'\','.PHP_EOL;

        $survey_finish_url = isset($_POST['survey_finish_url']) ? $_POST['survey_finish_url'] : '';
        $new_content .= ' \'survey_finish_url\' => \''.$survey_finish_url.'\','.PHP_EOL;

        $export_type = isset($_POST['export_type']) ? $_POST['export_type'] : '';
        $new_content .= ' \'export_type\' => \''.$export_type.'\','.PHP_EOL;

        $new_content .= ');'.PHP_EOL.PHP_EOL;
        

        // $email_server_settings
        $new_content .= '$email_server_settings = array('.PHP_EOL;

        $SMTPFrom = isset($_POST['SMTPFrom']) ? $_POST['SMTPFrom'] : '';
        $new_content .= ' \'SMTPFrom\' => \''.$SMTPFrom.'\','.PHP_EOL;

        $SMTPFromNice = isset($_POST['SMTPFromNice']) ? $_POST['SMTPFromNice'] : '';
        $new_content .= ' \'SMTPFromNice\' => \''.$SMTPFromNice.'\','.PHP_EOL;

        $SMTPReplyTo = isset($_POST['SMTPReplyTo']) ? $_POST['SMTPReplyTo'] : '';
        $new_content .= ' \'SMTPReplyTo\' => \''.$SMTPReplyTo.'\','.PHP_EOL;

        $SMTPHost = isset($_POST['SMTPHost']) ? $_POST['SMTPHost'] : '';
        $new_content .= ' \'SMTPHost\' => \''.$SMTPHost.'\','.PHP_EOL;

        $SMTPPort = isset($_POST['SMTPPort']) ? $_POST['SMTPPort'] : '';
        $new_content .= ' \'SMTPPort\' => \''.$SMTPPort.'\','.PHP_EOL;

        $SMTPSecure = isset($_POST['SMTPSecure']) ? $_POST['SMTPSecure'] : '';
        $new_content .= ' \'SMTPSecure\' => \''.$SMTPSecure.'\','.PHP_EOL;

        $SMTPAuth = isset($_POST['SMTPAuth']) ? $_POST['SMTPAuth'] : '';
        $new_content .= ' \'SMTPAuth\' => \''.$SMTPAuth.'\','.PHP_EOL;

        $SMTPUsername = isset($_POST['SMTPUsername']) ? $_POST['SMTPUsername'] : '';
        $new_content .= ' \'SMTPUsername\' => \''.$SMTPUsername.'\','.PHP_EOL;

        $SMTPPassword = isset($_POST['SMTPPassword']) ? $_POST['SMTPPassword'] : '';
        $new_content .= ' \'SMTPPassword\' => \''.$SMTPPassword.'\','.PHP_EOL;

        $new_content .= ');'.PHP_EOL.PHP_EOL;


        // Confirm registration, gdpr
        $confirm_registration = isset($_POST['confirm_registration']) ? $_POST['confirm_registration'] : '';
        $new_content .= '$confirm_registration = \''.$confirm_registration.'\';'.PHP_EOL;

        $confirm_registration_admin = $admin_email;
        $new_content .= '$confirm_registration_admin = \''.$confirm_registration_admin.'\';'.PHP_EOL;

        $gdpr_admin_email = $admin_email;
        $new_content .= '$gdpr_admin_email = \''.$gdpr_admin_email.'\';'.PHP_EOL.PHP_EOL;
        

        // Google
        $recaptcha_sitekey = isset($_POST['recaptcha_sitekey']) ? $_POST['recaptcha_sitekey'] : '';
        $new_content .= '$recaptcha_sitekey = \''.$recaptcha_sitekey.'\';'.PHP_EOL;

        $secret_captcha = isset($_POST['secret_captcha']) ? $_POST['secret_captcha'] : '';
        $new_content .= '$secret_captcha = \''.$secret_captcha.'\';'.PHP_EOL;

        $google_maps_API_key = isset($_POST['google_maps_API_key']) ? $_POST['google_maps_API_key'] : '';
        $new_content .= '$google_maps_API_key = \''.$google_maps_API_key.'\';'.PHP_EOL.PHP_EOL;


        // Zapisemo nov content v settings_optional.php
        $this->writeSettings($new_content);


        // Preverimo prijavo na prejemanje obvestil - potem pošljemo obvestilo na www.1ka.si
        $subscribe = isset($_POST['subscribe']) ? $_POST['subscribe'] : '0';
        if($subscribe == '1')
            $this->sendNotification($app_name, $admin_email);
    }

    // Zapisemo nov content v settings_optional.php
    private function writeSettings($new_content){

        $file_handle = fopen("../../settings_optional.php", "w");
        fwrite($file_handle, $new_content);
        fclose($file_handle);   
    }

    // Pošljemo obvestilo o prijava na obvestila za novo instalacijo
    private function sendNotification($app_name, $admin_email){
        global $site_domain;
        global $site_url;

        $parameters = 'site_domain='.urlencode($site_domain);
        $parameters .= '&site_url='.urlencode($site_url);
        $parameters .= '&app_name='.urlencode($app_name);
        $parameters .= '&admin_email='.urlencode($admin_email);

        //$url = 'http://localhost/utils/1kaUtils/custom_install_notify.php?'.$parameters;
        $url = 'https://www.1ka.si/utils/1kaUtils/custom_install_notify.php?'.$parameters;

        // Pripravimo klic
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
        // Izvedemo klic
        $result = curl_exec($ch);
    }

}