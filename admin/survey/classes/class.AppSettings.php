<?php

/*
    Class ki skrbi za vse globalne nastavitve aplikacije (ki so bile vcasih v settings_optional.php)

    Spremenljivke:
        
*/



class AppSettings {


    private static $instance = null;
    private $settings = array();

    private $variables = array(

        'basic' => array(
            'debug',

            // INSTALLATION TYPE (0->lastna, 1->www, 2->aai, 3->virtualka)
            'installation_type',

            'confirm_registration',
            'confirm_registration_admin',

            'gdpr_admin_email',

            'meta_admin_ids',
        ),

        // APP SETTINGS
        'info' => array(
            'app_settings-app_name',
            'app_settings-admin_email',
            'app_settings-owner',
            'app_settings-owner_website',
            'app_settings-footer_custom',
            'app_settings-footer_text',
            'app_settings-footer_survey_custom',
            'app_settings-footer_survey_text',
            'app_settings-email_signature_custom',
            'app_settings-email_signature_text',
            'app_settings-survey_finish_url',
            'app_settings-export_type',
        ),

        // APP LIMITS
        'limits' => array(
            'app_limits-clicks_per_minute_limit',
            'app_limits-question_count_limit',
            'app_limits-response_count_limit',
            'app_limits-invitation_count_limit',
            'app_limits-admin_allow_only_ip',
        ),

        // SMTP SETTINGS
        'smtp' => array(
            'email_server_settings-SMTPFrom',
            'email_server_settings-SMTPFromNice',
            'email_server_settings-SMTPReplyTo',
            'email_server_settings-SMTPHost',
            'email_server_settings-SMTPPort',
            'email_server_settings-SMTPSecure',
            'email_server_settings-SMTPAuth',
            'email_server_settings-SMTPUsername',
            'email_server_settings-SMTPPassword',
            'email_server_fromSurvey',
        ),
        
        'modules' => array(

            // GOOGLE
            'google-recaptcha_sitekey',
            'google-secret_captcha',
            'google-login_client_id',
            'google-login_client_secret',
            'google-maps_API_key',

            // FACEBOOK
            'facebook-appid',
            'facebook-appsecret',

            // MODULE MAZA
            'maza-FCM_server_key',
            'maza-APP_special_login_key',
            'maza-NextPinMainToken',
            'maza-NextPinMainPassword',

            // MODULE HIERARHIJA
            'hierarhija-folder_id',
            'hierarhija-default_id',

            // SQUALO MAIL
            'squalo-user',
            'squalo-key',
        )
    );
    

    private function __construct(){

        $this->prepareSettings();
        
    }


    public static function getInstance(){

        if (self::$instance == null){
            self::$instance = new AppSettings();
        }

        return self::$instance;
    }


    // Get all app settings from database (based on domain)
    private function prepareSettings(){
        global $site_domain;

        $sqlSetting = sisplet_query("SELECT what, value FROM app_settings");

        while ($rowSetting = mysqli_fetch_array($sqlSetting)) {
            $this->settings[$rowSetting['what']] = $rowSetting['value'];
        }
    }


    // Get app setting
    public function getSetting($what){

        if(isset($this->settings[$what])){

            // Nastavitev true
            if($this->settings[$what] === '1' || $this->settings[$what] === true || $this->settings[$what] === 'true')
                return true;
            
            // Nastavitev false
            if($this->settings[$what] === '0' || $this->settings[$what] === '' || $this->settings[$what] === false || $this->settings[$what] === 'false')
                return false;

            // Nastavitev array
            if($what == 'confirm_registration_admin' || $what == 'meta_admin_ids' || $what == 'app_limits-admin_allow_only_ip')
                return explode(',', $this->settings[$what]);

            return $this->settings[$what];
        }
        else
            return false;
    }

    // Save app setting
    public function saveSetting($what, $value){
        global $site_domain;

        //$sqlSetting = sisplet_query("UPDATE app_settings SET value='".$value."' WHERE what='".$what."' AND domain='".$site_domain."'");
        $sqlSetting = sisplet_query("INSERT INTO app_settings
                                        (value, what)
                                        VALUES 
                                        ('".$value."', '".$what."')
                                        ON DUPLICATE KEY UPDATE
                                            value='".$value."'
                                    ");

        $this->settings[$what] = $value;
    }


    // Display app settings
    public function displaySettingsGroup($group){

        echo '<br>';

        $setting_variables = $this->variables[$group];

        foreach($setting_variables as $what){
            $this->displaySetting($what);
        }   
    }

    // Display app single setting
    public function displaySetting($what){
        global $lang;
        
        echo '<span class="nastavitveSpan6"><label>'.$lang['as_'.$what].': </label></span>';
            
        echo '<input type="text" size="40" name="as_'.$what.'" value="'.strip_tags($this->settings[$what]).'">';

        echo '<br>';
    }
}

?>