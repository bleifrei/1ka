<?php

    ob_start('KeepDomain');

    header('Cache-Control: no-cache');
    header('Pragma: no-cache');

    include_once 'definition.php';
    include_once '../../function.php';
    include_once '../../vendor/autoload.php';


    # error reporting
    if(isDebug()){
        error_reporting(E_ALL ^ E_NOTICE);
        ini_set('display_errors', '1');
    }
    else{
        error_reporting(E_ALL ^ E_NOTICE ^ E_STRICT);
        ini_set('display_errors', '0');
    }


    global $site_url, $global_user_id, $app_settings;

    $surveySkin = 0;

    
    /**************** LANGUAGE ****************/
    // Ce prihajamo iz drupala preverimo se parameter jezik, ce mogoce preklopimo
    if(isset($_GET['lang']) && ($_GET['lang'] == 'sl' || $_GET['lang'] == 'en')){
        $lang = ($_GET['lang'] == 'en') ? '2' : '1';
        $sqlL = sisplet_query("UPDATE users SET lang = '$lang' WHERE id = '$global_user_id'");	
    }

    $anketa = isset($_REQUEST['anketa']) ? $_REQUEST['anketa'] : null;
    $lang_admin = 0;

    if ($anketa > 0) {
        $sql = sisplet_query("SELECT lang_admin FROM srv_anketa WHERE id = '$anketa'");
        $row = @mysqli_fetch_array($sql);
        $lang_admin = $row['lang_admin'];
    }
    if ($lang_admin == 0) {
        $sql = sisplet_query("SELECT lang FROM users WHERE id = '$global_user_id'");
        $row = @mysqli_fetch_array($sql);
        $lang_admin = $row['lang'];
    }
    if ($lang_admin == 0) {
        $sql = sisplet_query("SELECT value FROM misc WHERE what = 'SurveyLang_admin'");
        $row = @mysqli_fetch_array($sql);
        $lang_admin = $row['value'];
    }
    if ($lang_admin == 0) 
        $lang_admin = 2; // za vsak slucaj, ce ni v bazi

    // Naložimo jezikovno datoteko
    $file = '../../lang/'.$lang_admin.'.php';
    include($file);
    $_SESSION['langX'] = $site_url .'lang/'.$lang_admin.'.php';
    /**************** END LANGUAGE ****************/


    // Poseben redirect za gorenje instalacijo (ce ima nastavljen default password ga preusmerimo na urejanje profila in prisilimo, da spremeni geslo)
    if (Common::checkModule('gorenje')){	
        SurveyGorenje::redirectGorenjePassword();
    }


    
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
    echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';

    /**************** HEAD ****************/
    echo '<head>';

    // Google analytics
    if($site_domain == 'www.1ka.si'){
        echo "<!-- Google Tag Manager --><script>
                    (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','GTM-KS6CJHL');
                </script><!-- End Google Tag Manager -->";
    }

    $sqlv = sisplet_query("SELECT value FROM misc WHERE what='version'");
    $rowv = mysqli_fetch_array($sqlv);

    // Custom head title
    if(isset($app_settings['head_title_custom']) && $app_settings['head_title_custom'] == 1){
        echo '<title>'.$app_settings['head_title_text'].'</title>' . "\n";
    }
    // Default head title
    else{
        echo '<title>'.$lang['1ka_surveys'].'</title>' . "\n";
    }
    
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
    echo '<meta name="site-url" content="'.$site_url.'" />';

    // Responsive
    echo '<meta content="width=device-width; initial-scale=1.0;" name="viewport">';
    

    // ZAMENJAMO UREJEVALNIK ZA CKEDITOR
    echo '<script type="text/javascript" src="'.$site_url.'editors/ckeditor_4_4/ckeditor.js"></script>';
    echo '<script>';
    echo '    CKEDITOR.disableAutoInline = true;';
    echo '    CKEDITOR.config.contentsCss = \'css/modules/themes.css\';';
    echo '    CKEDITOR.config.language = '.($lang_admin == 2 ? '\'en\'' : '\'sl\'').';';
    echo '</script>';
    // END CKEDITOR

    // LANG JS
    echo '<script type="text/javascript" src="script/js-lang.php?lang='.($lang_admin==1 ? 'si' : 'en').'&v='.$rowv['value'].'"></script>';
 
    // JS
    if (isset($_GET['mode']) && $_GET['mode'] == 'old') {
        echo '<script type="text/javascript" src="minify/g=js?v='.$rowv['value'].'"></script>';
    } 
    else {
        if(isset($_GET['a']) && ($_GET['a'] == 'hierarhija_superadmin' || $_GET['a'] == 'hierarhija')){
            echo '<script type="text/javascript" src="minify/g=jshierarhija?v='.$rowv['value'].'"></script>';
        }
        elseif($_GET['a'] == 'narocila' || $_GET['t'] == 'uporabniki'){
            echo '<script type="text/javascript" src="minify/g=jsLastLib?v='.$rowv['value'].'"></script>';
        }
        else{
            echo '<script type="text/javascript" src="minify/g=jsnew?v='.$rowv['value'].'"></script>';
        }
    } 

    echo '<link type="text/css" href="minify/g=css?v='.$rowv['value'].'" media="screen" rel="stylesheet" />';
    echo '<link type="text/css" href="minify/g=cssPrint?v='.$rowv['value'].'" media="print" rel="stylesheet" />';

    // Gorenje js
    if(Common::checkModule('gorenje')){
        echo '<script type="text/javascript" src="modules/mod_gorenje/script/gorenje.js"></script>';
    }

    // Fonts
    echo '<link type="text/css" href="https://fonts.googleapis.com/css?family=Montserrat:400,400i,500,500i,600,600i,700,700i&amp;subset=latin-ext" rel="stylesheet" />';

    ?>
    <!--[if lt IE 7]>
    <link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie6hacks.css" type="text/css" />
    <![endif]-->
    <!--[if IE 7]>
    <link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie7hacks.css" type="text/css" />
    <![endif]-->
    <!--[if IE 8]>
    <link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie8hacks.css" type="text/css" />
    <![endif]-->
    <!--[if IE 9]>
    <link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie9hacks.css" type="text/css" />
    <![endif]-->
    <?php

    // FAVICON
    echo '<link rel="shortcut icon" type="image/ico" href="'.$site_url.'/favicon.ico" />';
        
    echo '</head>'."\n";
    /**************** END HEAD ****************/


    // Zaenkrat preusmeritev za demo anketo - boljse bi blo z rewrite ampak noce delat:)
    if (isset($_GET['anketa']) && $_GET['anketa'] == '15313'){
        $query = $_GET;
        $query['anketa'] = '32173';
        $query_new = http_build_query($query);
        
        header ('location: '.$site_url.'admin/survey/index.php?'.$query_new);
    }


    // za css - barve ozadja
    if (isset($_GET['anketa']))
        $cssBodyClass = ' body_anketa';
    else if (isset($_GET['a']) && $_GET['a'] == 'knjiznica' )
        $cssBodyClass = ' body_library';
    else
        $cssBodyClass = ' body_folders';
            
    $sql = sisplet_query("SELECT email FROM users WHERE id='$global_user_id'");
    $row = mysqli_fetch_assoc($sql);

    if ( $row['email'] == 'test@1ka.si') {
        $cssBodyClass .= ' test_user';
    }


    /**************** BODY ****************/
    echo '<body class="mainBody'.$cssBodyClass.'">'."\n";

    // Google analytics
    if($site_domain == 'www.1ka.si'){
        echo '<!-- Google Tag Manager (noscript) -->
                <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-KS6CJHL" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
            <!-- End Google Tag Manager (noscript) -->';
    }

    // Opozorilo za update browserja -> IE8 ali manj
    ?>
    <!--[if lte IE 8]>
        <div id="ie_alert"><?=$lang['srv_upgrade_ie']?></div>
    <![endif]-->
    <?php

    // preverimo dostop
    $result = sisplet_query ("SELECT value FROM misc WHERE what='SurveyDostop'");
    list ($SurveyDostop) = mysqli_fetch_row ($result);

    if ( ( ($admin_type <= $SurveyDostop && $SurveyDostop<3) || ($SurveyDostop==3) ) && ($admin_type>=0) ) {
        $s = new SurveyAdmin();
        $s->display();
    } 
    else {	
        global $site_frontend;

        // Popravljen redirect za drupal
        if($site_url == "https://www.1ka.si/" || $site_frontend == 'drupal'){
            global $cookie_domain;

            $piskotSpremembaGesla = (!empty($_COOKIE['spremembaGesla']) ? 1 : 0);
            
            setcookie('spremembaGesla', '', time() - 3600, '/', $cookie_domain);
            header('location: ' . $site_url . '/d/' . ($lang_admin == 2 ? 'en' : 'sl') . ($piskotSpremembaGesla == 0 ? '#neregistriran-uporabnik' : null));
        }
        else{
            header ('location: ' .$site_url .'/index.php');
        }
    }

    echo '</body>';
    /**************** END BODY ****************/

    echo '</html>';


    function KeepDomain($buffer) {
        global $originating_domain;
        global $keep_domain;
        
        if ($originating_domain != '' && $keep_domain != '') {
            return str_replace ($originating_domain, $keep_domain, str_replace ("https://" .$originating_domain, "http://" .$keep_domain, $buffer));
        }

        return $buffer;
    }


    ob_end_flush();
