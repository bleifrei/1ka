<?php

error_reporting(E_ALL ^ E_NOTICE);

if (!function_exists('apache_request_headers')) {
    function apache_request_headers()
    {
        $arh = array();
        $rx_http = '/\AHTTP_/';
        foreach ($_SERVER as $key => $val) {
            if (preg_match($rx_http, $key)) {
                $arh_key = preg_replace($rx_http, '', $key);
                $rx_matches = array();
                // do some nasty string manipulations to restore the original letter case
                // this should work in most cases
                $rx_matches = explode('_', $arh_key);
                if (count($rx_matches) > 0 and strlen($arh_key) > 2) {
                    foreach ($rx_matches as $ak_key => $ak_val) $rx_matches[$ak_key] = ucfirst($ak_val);
                    $arh_key = implode('-', $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }
        return ($arh);
    }
}


/**
 * Osnovne nastavitve instalacije (path, sql baza)
 */
include('settings.php');

//  overridi za kopije
if (getenv('apache_site_path') != '') $site_url = getenv('apache_site_url');
if (getenv('apache_site_path') != '') $site_path = getenv('apache_site_path');
if (getenv('apache_site_domain') != '') $site_domain = getenv('apache_site_domain');
if (getenv('apache_originating_domain') != '') $originating_domain = getenv('apache_originating_domain');
if (getenv('apache_keep_domain') != '') $keep_domain = getenv('apache_keep_domain');

// se MSN in FB
if (getenv('apache_facebook_appid') != '') $facebook_appid = getenv('apache_facebook_appid');
if (getenv('apache_facebook_appsecret') != '') $facebook_appsecret = getenv('apache_facebook_appsecret');


if ($pass_salt == "") die ("Please set unique pass_salt in settings.php!");


/**
 * Dodatne opcijske nastavitve
 * Če se ne potrebujejo ni potrebno da datoteka obstaja
 */
if(file_exists($site_path.'settings_optional.php')){
    include $site_path.'settings_optional.php';
}


// igramo se z jezikom...
if (isset ($_GET['overridelang']) && is_numeric($_GET['overridelang'])) {
    $_SESSION['overridelang'] = $_GET['overridelang'];
}


if (is_file($site_path . 'install')) die ("Please, finish instalation with removing 'install/' folder.");

// Aplication type -- tip aplikacije
$aplication_type = 1;        // 1 - navadna z backupom
// 2 - arhivska
// 3 - navadna brez backupa

// Za 1 je treba spremeniti pot v /admin/backup.php
// Za 2 je treba spremeniti pot v /admin/install.php

$_SESSION['site_url'] = $site_url;
if (!$connect_db = mysqli_connect($mysql_server, $mysql_username, $mysql_password, $mysql_database_name)) {
    die ('Please try again later [ERR: DB])');
}


// To je ostanek sispleta in verjetno ne sme biti več prisotno?
//sisplet_query("SET character_set_results=latin1");


// FIRST CHECK FOR SQL INJECT!!!!
// WEB user MUST NOT have privileges to DROP or ALTER

// mysql escaping used on all GPC variables
function stripslashes_gpc(&$value)
{
    /*if (get_magic_quotes_gpc()) {
        $value = stripslashes($value);
    }*/
    $value = mysqli_real_escape_string($GLOBALS['connect_db'], $value);
}

array_walk_recursive($_GET, 'stripslashes_gpc');
array_walk_recursive($_POST, 'stripslashes_gpc');
array_walk_recursive($_COOKIE, 'stripslashes_gpc');

// ker se sedaj vse escapa z mysql funcijo, se kjer se to potrebuje vse skupaj unescapa z mysql_real_unescape_string()  (definirana v function.php)

function sisplet_query($q, $special_connect_db = null, $single = false)
{
    global $site_domain;

    if ($special_connect_db !== null && !in_array($special_connect_db, ['array', 'obj', 'id', 'valarray', 'onevalarray']) && is_resource($special_connect_db)) {
        $connect_db = $special_connect_db;
    } else {
        global $connect_db;
    }

    if (!$connect_db) {
        die ('Invalid DB resource! [ERR: DB])');
    }
    
    //ce je nastavljen drugi parameter == multi_query, potem zazeni opcijo za multi_query
    $res;
    if($special_connect_db != 'multi_query')
        $res = mysqli_query($connect_db, $q);
    else
        $res = mysqli_multi_query($connect_db, $q);
    
    mysqli_store_result($connect_db);

    // Za razvoj in test SQL napake prikažemo, za ostale inštlacije pa zapišemo v error log
    // V kolikor je napaka potem beležimo v error log za naštete domene
    if (!$res && in_array($site_domain, ['localhost', '1ka.test', 'test.1ka.si'])) {
	        error_log(mysqli_error($connect_db));
    }

    // V kolikor imamo posebne zahteve, če v bazi ne obstaja query, potem vrnemo FALSE
    if (!empty($res) && !is_null($special_connect_db) && $special_connect_db != 'multi_query') {
        if (preg_match('/(^SELECT)/', $q) && in_array($special_connect_db, ['array', 'obj', 'valarray', 'onevalarray']) ) {

            $rezultat = [];
            while ($row = mysqli_fetch_assoc($res)) {
                if($special_connect_db == 'obj'){
                    $rezultat[] = (object) $row;
                }else if($special_connect_db == 'valarray'){
	                $rezultat[] = array_values($row);
                }else if($special_connect_db == 'onevalarray'){
	                $rezultat[] = reset($row);
                }else{
                    $rezultat[] = $row;
                }
            }


            // V koliko imamo samo en rezultat
            if (mysqli_num_rows($res) == 1 && ($single || $special_connect_db == 'obj'))
                return $rezultat[0];

            return $rezultat;

        } elseif (preg_match('/(^INSERT)/', $q) && $special_connect_db == 'id') {
            // V kolikor imamo insert in želimo vrniti id vnosa
            return mysqli_insert_id($GLOBALS['connect_db']);
        }
    }

    return $res;
}

if (isset($_POST)) {
    $postArray = &$_POST;

    foreach ($postArray as $sForm => $value) {
        if (is_string($value) && strpos(strtolower($value), "insert into") === true) hack();
        if (is_string($value) && strpos(strtolower($value), "delete from") === true) hack();
        if (is_string($value) && strpos(strtolower($value), "alter table") === true) hack();
        if (is_string($value) && strpos(strtolower($value), "<script") === true) hack();
        if (is_string($value) && strpos(strtolower($value), "<meta") === true) hack();
    }
}

if (isset($_GET)) {
    $getArray = &$_GET;

    foreach ($getArray as $sForm => $value) {
        if (is_string($value) && strpos(strtolower($value), "insert into") !== false) hack();
        elseif (is_string($value) && strpos(strtolower($value), "delete from") !== false) hack();
        elseif (is_string($value) && strpos(strtolower($value), "alter table") !== false) hack();
        elseif (is_string($value) && strpos(strtolower($value), "<script") !== false) hack();
        elseif (is_string($value) && strpos(strtolower($value), "<meta") !== false) hack();
        elseif (is_string($value) && strpos(strtolower($value), "select") !== false) hack();
    }
}

if (isset($_COOKIE)) {
    $getArray = &$_COOKIE;

    foreach ($getArray as $sForm => $value) {
        if (is_string($value) && strpos(strtolower($value), "insert into") !== false) hack();
        elseif (is_string($value) && strpos(strtolower($value), "delete from") !== false) hack();
        elseif (is_string($value) && strpos(strtolower($value), "alter table") !== false) hack();
        elseif (is_string($value) && strpos(strtolower($value), "<script") !== false) hack();
        elseif (is_string($value) && strpos(strtolower($value), "<meta") !== false) hack();
        elseif (is_string($value) && strpos(strtolower($value), "select") !== false) hack();
    }
}
// SQL INJECT CHECK END


// Pohendlamo language
unset ($lang);

if (isset ($_SESSION['overridelang']) && is_numeric($_SESSION['overridelang'])) {
    if (is_file('lang/' . $_SESSION['overridelang'] . '.php')) {
        include('lang/' . $_SESSION['overridelang'] . '.php');
        
        if ($lang['useful_translation'] != "1") 
            unset ($lang);
    }
}

// Nalozimo jezikovno datoteko
if (!isset ($lang)) {
	include('lang/1.php');
}


// Preberemo uid uporabnika
// Uporabi global admin_type namesto da klices login 100x!
$admin_type = login();

if ($admin_type > -1) {
    $result = sisplet_query("SELECT id FROM users WHERE email='" . base64_decode($_COOKIE['uid']) . "'");

    if (mysqli_num_rows($result) > 0) {
        $r = mysqli_fetch_row($result);
        $global_user_id = $r[0];
    } elseif (isset ($_COOKIE['ME'])) {
        $db_meta_exists = mysqli_select_db($GLOBALS['connect_db'], "meta");
        if ($db_meta_exists)
            $result = sisplet_query("SELECT aid FROM administratorji WHERE email='" . base64_decode($_COOKIE['uid']) . "'");

        if (mysqli_num_rows($result) > 0) {
            $r = mysqli_fetch_row($result);
            $global_user_id = $r[0];
        } else {
            $global_user_id = 0;
        }
        mysqli_select_db($GLOBALS['connect_db'], $mysql_database_name);

    } else {
        $global_user_id = 0;
    }
}


// Preverimo ce je spremenljivka countable (zaradi ogromno warningov v kodi, kjer se counta prazno spremenljivko)
if (!function_exists('is_countable')) {
    function is_countable($var) {
        return (is_array($var) || $var instanceof Countable);
    }
}


// Preverimo klike na minuto pri izpolnjevanju anekte da se ne zapolni sql
if(!checkClicksPerMinute()){
    global $site_url;

    $refresh_every = 5;

    echo '<!DOCTYPE html>';
    echo '<html>';

    echo '<head>';
    echo '    <title>Server Limit Reached</title>';
    echo '    <meta http-equiv="refresh" content="'.$refresh_every.'" />';
    echo '    <meta name="viewport" content="width=device-width, initial-scale=1.0" />';
    
    echo '    <style>
        body{
            display: flex;
            align-content: center;
            height: 90vh;
            
            flex-wrap: wrap;
            align-content: center;
        }
        .main{
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px;

            font-family: Montserrat, Arial, Sans-Serif !important;
            color: #505050;
        }
        h1{
            color: #1e88e5;
            text-align: center;
            margin: 30px 0;
        }
        hr{
            margin: 50px 0;

            border: 0;
            border-top: 1px solid #ddeffd;
        }
        .loading{
            margin: 50px 0;
            text-align: center;
        }
        img{
            width: 80px;
            height: 80px;
        }
    </style>';
    echo '</head>';

    echo '<body><div class="main">';
    echo '    <div class="loading"><img src="'.$site_url.'/public/img/icons/spinner.gif" /></div>';
    echo '    <h1>Dosežena omejitev strežnika</h1>';
    echo '    <h3>Prosimo, počakajte nekaj trenutkov. Trenutno je doseženo maksimalno število vnosov ankete na minuto.</h3>';
    echo '    <hr>';
    echo '    <h1>Server Limit Reached</h1>';
    echo '    <h3>Please wait a few moments. Currently, the maximum number of survey entries per minute has been reached.</h3>';
    echo '</div></body>';

    echo '</html>';
    
    die();
}


// Preverimo tip hierarhije
$hierarhija_type = preveriTipHierarhije();



/******* SPLOSNE FUNKCIJE *******/

// Skrajsa string, in ga odreze lepo za besedo in ne kar vmes :)
function skrajsaj($string, $dolzina)
{
    if (strlen($string) > $dolzina) {
        preg_match('/(.{' . $dolzina . '}.*?)\b/', $string, $matches);
        return rtrim($matches[1]) . "...";
    }
    return $string;
}

// Preveri, ce je administrator loginan - vrne true ce je, false ce ni
function login()
{
    global $admin_type;    // tip admina: 0:admin, 1:manager, 2:clan, 3- user
    global $global_user_id;
    global $mysql_database_name;
    global $pass_salt;
    global $is_meta;
    global $cookie_domain;

    $is_meta = 0;
    $global_user_id = 0;
    $admin_type = 3;
    $cookie_pass = $_COOKIE['secret'];

    // UID je v resnici base64 od emaila, ker sicer odpove meta!!!
    // najprej testiram meto, potem sele userje.

    if (isset ($_COOKIE['uid'])) {

        $user_email = base64_decode($_COOKIE['uid']);

        $db_meta_exists = mysqli_select_db($GLOBALS['connect_db'], "meta");
        if ($db_meta_exists)
            $result = sisplet_query("SELECT geslo, aid, 0 as type FROM administratorji WHERE email='$user_email'");

        // NI META
        if (!$result || mysqli_num_rows($result) == 0) {
            mysqli_select_db($GLOBALS['connect_db'], $mysql_database_name);
            $meta = 0;

            $result = sisplet_query("SELECT pass, id, type FROM users WHERE email='$user_email'");
            if (!$result || mysqli_num_rows($result) == 0) {
                // najprej poradiraij cookije!
                setcookie('uid', "", time() - 3600, $cookie_domain);
                setcookie('secret', "", time() - 3600, $cookie_domain);

                if (substr_count($cookie_domain, ".") > 1) {
                    $nd = substr($cookie_domain, strpos($cookie_domain, ".") + 1);

                    setcookie('uid', "", time() - 3600, $nd);
                    setcookie('secret', "", time() - 3600, $nd);
                }

                return -1;
            } else {
                $r = mysqli_fetch_row($result);

                if ($cookie_pass != $r[0]) {
                    // najprej poradiraij cookije!
                    setcookie('uid', "", time() - 3600, $cookie_domain);
                    setcookie('secret', "", time() - 3600, $cookie_domain);

                    if (substr_count($cookie_domain, ".") > 1) {
                        $nd = substr($cookie_domain, strpos($cookie_domain, ".") + 1);

                        setcookie('uid', "", time() - 3600, $nd);
                        setcookie('secret', "", time() - 3600, $nd);
                    }
                    return -1;
                } else {
                    $admin_type = $r[2];
                    $global_user_id = $r[1];
                    return $r[2];
                }
            }

        } // JE META
        else {
            $r = mysqli_fetch_row($result);

            if ($cookie_pass == base64_encode((hash('SHA256', base64_decode($r[0]) . $pass_salt)))) {

                $is_meta = 1;
                $admin_type = "0";

                mysqli_select_db($GLOBALS['connect_db'], $mysql_database_name);

                $result = sisplet_query("SELECT pass, id, type FROM users WHERE email='$user_email'");
                if (mysqli_num_rows($result) > 0) {
                    $r = mysqli_fetch_row($result);
                    $global_user_id = $r[1];
                }

                return 0;
            } else {
                mysqli_select_db($GLOBALS['connect_db'], $mysql_database_name);
                // Obstaja tudi primer ko je IN meta IN navaden- in se je pac prijavil kot navaden user


                $result = sisplet_query("SELECT pass, id, type FROM users WHERE email='$user_email'");
                if (!$result || mysqli_num_rows($result) == 0) {
                    return -1;
                } else {
                    $r = mysqli_fetch_row($result);

                    if ($cookie_pass != $r[0]) {
                        // najprej poradiraij cookije!
                        setcookie('uid', "", time() - 3600, $cookie_domain);
                        setcookie('secret', "", time() - 3600, $cookie_domain);

                        if (substr_count($cookie_domain, ".") > 1) {
                            $nd = substr($cookie_domain, strpos($cookie_domain, ".") + 1);

                            setcookie('uid', "", time() - 3600, $nd);
                            setcookie('secret', "", time() - 3600, $nd);
                        }

                        return -1;
                    } else {
                        $admin_type = $r[2];
                        $global_user_id = $r[1];
                        return $r[2];
                    }
                }
            }
        }
    } // Ni prijavljen
    else {
        $admin_type = -1;
        return -1;
    }
}

// Iz DATETIME MySQL polja ustvari lepsi izpis datuma in casa
function datetime($time)
{
    return substr($time, 8, 2) . '.' . substr($time, 5, 2) . '.' . substr($time, 0, 4) . ' ' . substr($time, 11, 2) . ':' . substr($time, 14, 2) . ':' . substr($time, 17, 2);
}

function redirect($to)
{
    $schema = $_SERVER['SERVER_PORT'] == '443' ? 'https' : 'http';
    $host = strlen($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'];
    if (headers_sent()) {
        ?>
        <html>
        <head>
            <meta http-equiv="refresh" content="0;URL=<?= $to ?>">
            <SCRIPT LANGUAGE="JavaScript">
                <!--
                window.location = "<?=$to?>";
                // -->
            </script>
        </head>
        <body>
        <a href="<?= $to ?>"><?= $lang['back'] ?></a>
        </body>
        </html>
        <?php
    } else {
        header('location: ' . $to);
    }
}

function DrawDate($Unformatted, $Type, $To = 0)
{
    $datum = $Unformatted;

    SWITCH ($Type) {
        CASE 0:
            $Clean = "";
            break;

        CASE 1:
            $Clean = "<span>" . $datum[8] . $datum[9] . "." . $datum[5] . $datum[6] . "</span>";
            break;

        CASE 2:
            $Clean = "<span>" . $datum[8] . $datum[9] . "." . $datum[5] . $datum[6] . "." . $datum[2] . $datum[3] . "</span>";
            break;

        CASE 3:
            $Clean = "<span>" . $datum[8] . $datum[9] . "." . $datum[5] . $datum[6] . "." . $datum[0] . $datum[1] . $datum[2] . $datum[3] . "</span>";
            break;

        CASE 4:
            $Mes = $datum[5] . $datum[6];
            if ($Mes == "01") $M = "Jan";
            elseif ($Mes == "02") $M = "Feb";
            elseif ($Mes == "03") $M = "Mar";
            elseif ($Mes == "04") $M = "Apr";
            elseif ($Mes == "05") $M = "May";
            elseif ($Mes == "06") $M = "Jun";
            elseif ($Mes == "07") $M = "Jul";
            elseif ($Mes == "08") $M = "Aug";
            elseif ($Mes == "09") $M = "Sep";
            elseif ($Mes == "10") $M = "Oct";
            elseif ($Mes == "11") $M = "Nov";
            elseif ($Mes == "12") $M = "Dec";

            $Clean = "<span>" . $M . " " . $datum[8] . $datum[9] . "</span>";
            break;

        CASE 5:
            $Mes = $datum[5] . $datum[6];
            if ($Mes == "01") $M = "Jan";
            elseif ($Mes == "02") $M = "Feb";
            elseif ($Mes == "03") $M = "Mar";
            elseif ($Mes == "04") $M = "Apr";
            elseif ($Mes == "05") $M = "May";
            elseif ($Mes == "06") $M = "Jun";
            elseif ($Mes == "07") $M = "Jul";
            elseif ($Mes == "08") $M = "Aug";
            elseif ($Mes == "09") $M = "Sep";
            elseif ($Mes == "10") $M = "Oct";
            elseif ($Mes == "11") $M = "Nov";
            elseif ($Mes == "12") $M = "Dec";

            $Clean = "<span>" . $M . " " . $datum[8] . $datum[9] . " " . $datum[0] . $datum[1] . $datum[2] . $datum[3] . "</span>";
            break;

        CASE 6:
            $MesA = $Unformatted[5] . $Unformatted[6];
            if ($MesA == "01") $MA = "Jan";
            elseif ($MesA == "02") $MA = "Feb";
            elseif ($MesA == "03") $MA = "Mar";
            elseif ($MesA == "04") $MA = "Apr";
            elseif ($MesA == "05") $MA = "May";
            elseif ($MesA == "06") $MA = "Jun";
            elseif ($MesA == "07") $MA = "Jul";
            elseif ($MesA == "08") $MA = "Aug";
            elseif ($MesA == "09") $MA = "Sep";
            elseif ($MesA == "10") $MA = "Oct";
            elseif ($MesA == "11") $MA = "Nov";
            elseif ($MesA == "12") $MA = "Dec";

            $MesB = $Unformatted[5] . $Unformatted[6];
            if ($MesB == "01") $MB = "Jan";
            elseif ($MesB == "02") $MB = "Feb";
            elseif ($MesB == "03") $MB = "Mar";
            elseif ($MesB == "04") $MB = "Apr";
            elseif ($MesB == "05") $MB = "May";
            elseif ($MesB == "06") $MB = "Jun";
            elseif ($MesB == "07") $MB = "Jul";
            elseif ($MesB == "08") $MB = "Aug";
            elseif ($MesB == "09") $MB = "Sep";
            elseif ($MesB == "10") $MB = "Oct";
            elseif ($MesB == "11") $MB = "Nov";
            elseif ($MesB == "12") $MB = "Dec";


            $Clean = "<span>" . $MA . " " . $Unformatted[8] . $Unformatted[9] . "-" . $MB . $To[8] . $To[9] . ", " . $To[0] . $To[1] . $To[2] . $To[3] . "</span>";
            break;

        CASE 7:
            $Clean = "<span>" . $datum[5] . $datum[6] . "/" . $datum[8] . $datum[9] . "</span>";
            break;

        CASE 8:
            $Clean = "<span>" . $datum[5] . $datum[6] . "/" . $datum[8] . $datum[9] . "/" . $datum[0] . $datum[1] . $datum[2] . $datum[3] . "</span>";
            break;

        CASE 12:
            $Mes = $datum[5] . $datum[6];
            if ($Mes == "01") $M = "Jan";
            elseif ($Mes == "02") $M = "Feb";
            elseif ($Mes == "03") $M = "Mar";
            elseif ($Mes == "04") $M = "Apr";
            elseif ($Mes == "05") $M = "May";
            elseif ($Mes == "06") $M = "Jun";
            elseif ($Mes == "07") $M = "Jul";
            elseif ($Mes == "08") $M = "Aug";
            elseif ($Mes == "09") $M = "Sep";
            elseif ($Mes == "10") $M = "Oct";
            elseif ($Mes == "11") $M = "Nov";
            elseif ($Mes == "12") $M = "Dec";

            //$Clean = "<div class=\"date_box\">".$M."<span>".$datum[8].$datum[9]."</span>".$datum[0].$datum[1].$datum[2].$datum[3]."</div>";
            $Clean = "<div class=\"date_box\">" . $M . "<span>" . $datum[8] . $datum[9] . "</span></div>";
            break;

        DEFAULT:
            $Clean = "";
    }

    return $Clean;

}

function hack()
{
    die ("HACK ATTEMPT, BYE");
}

function CleanXSS($w)
{

    $w = preg_replace('/\<script(.*?)\/script>/i', "", $w);
    $w = preg_replace('/\<meta(.*?)\>/i', "", $w);

    return $w;

}

function str_replace_once($search, $replace, $subject)
{
    $firstChar = strpos($subject, $search);

    if ($firstChar !== false) {
        $beforeStr = substr($subject, 0, $firstChar);
        $afterStr = substr($subject, $firstChar + strlen($search));
        return $beforeStr . $replace . $afterStr;
    } else {
        return $subject;
    }
}

function GetHtaccessPath($NiceLink)
{
    global $site_url;
    global $site_path;

    // lep link je http://url/NEKAJ/dalje
    // zanima te NEKAJ

    $nl = str_replace($site_url, "", $NiceLink);
    $KAJ = substr($nl, 0, strpos($nl, "/"));
    $nl = substr($nl, (strpos($nl, "/") + 1));

    if (!is_dir($site_path . str_replace("/", "", $KAJ))) {
        mkdir($site_path . str_replace("/", "", $KAJ));

        $fh = fopen($site_path . str_replace("/", "", $KAJ) . '/.htaccess', 'a');
        $stringData = "RewriteEngine On " . "\n\n";
        fwrite($fh, $stringData);
        fclose($fh);
    }

    return $KAJ . "|" . $nl;
}

// NE POZABI PODATI DIREKTORIJA IZ KJER NAJ ODSTRANI!!!!
// brez prvega slasha.
function RemoveNiceLink($what, $dir)
{

    global $site_path;

    exec('cat ' . $site_path . $dir . '.htaccess | grep -v "' . $what . '&%{QUERY_STRING}" > ' . $site_path . $dir . 'zacasno');
    exec('mv -f ' . $site_path . $dir . 'zacasno ' . $site_path . $dir . '.htaccess');

}

// Odstrani celo drevo lepih linkov
// Primerno za brisanje drevesa navigacij ipd.
// povej mu da ves kaj delas (gl. parametre)
function RemoveNiceLinkRecursive($what, $check)
{
    global $site_path;
    global $site_url;

    if ($check != "YES_I_AM_SURE") return;

    else {
        // Noter dobis cel lep link.
        // odstrani mu site_url in poglej prvi direktorij
        $whole = $what;
        $what = str_replace($site_url, "", $what);
        $slash = strpos($what, "/");
        $dir = substr($what, 0, $slash);
        $what = substr($what, $slash + 1);

        exec('cat ' . $site_path . $dir . '/.htaccess | grep -v "\^' . $what . '" | grep -v "' . $whole . '" > ' . $site_path . $dir . '/zacasno');
        exec('mv -f ' . $site_path . $dir . '/zacasno ' . $site_path . $dir . '/.htaccess');
    }
}

function AddRedirect($what, $where)
{
    global $site_path;
    global $site_url;

    // popravi, naj preveri ce je noter kaka crka a-zA-Z0-9
    // Noter morajo biti vsaj tri crke in stevilke, sicer ne bomo delali!!

    if ($what != $where && $what != "" && $where != "") {
        // Najprej poglej direktorije!!!!!

        $path_what = explode("|", GetHtaccessPath($what));
        $path_where = explode("|", GetHtaccessPath($where));

        if (strpos($path_what[1], "//") !== false || strpos($path_where[1], "//") !== false) return;

        // Odstrani obratnega da ne bos naredil endless loop!!!
        exec('cat ' . $site_path . $path_where[0] . '/.htaccess | grep -v "Redirect ' . str_replace($site_url, "/", $where) . '" > ' . $site_path . $path_where[0] . '/zacasno');
        exec('mv -f ' . $site_path . $path_where[0] . '/zacasno ' . $site_path . $path_where[0] . '/.htaccess');

        $what = str_replace($site_url, "/", $what);

        $fh = fopen($site_path . $path_what[0] . '/.htaccess', 'a');
        $stringData = "Redirect " . $what . "\t\t\t$where" . "\n";
        fwrite($fh, $stringData);
        fclose($fh);
    }
}

function CleanNiceLinkText($what)
{
    $what = iconv("ISO-8859-2", "YU//TRANSLIT", $what);
    $what = str_replace("&scaron;", "s", $what);
    $what = str_replace("&Scaron;", "S", $what);
    $what = str_replace("[", "S", $what);
    $what = str_replace("{", "s", $what);
    $what = str_replace("^", "C", $what);
    $what = str_replace("~", "c", $what);
    $what = str_replace("`", "z", $what);
    $what = str_replace("@", "Z", $what);

    // %
    $what = str_replace("%", "", $what);

    $what = str_replace("&quot;", "_", $what);

    $what = str_replace(" ", "_", $what);
    $what = preg_replace("/[^a-zA-Z0-9_\/]/", "", $what);

    return $what;
}

// Vrne true ce so v tekstu vec kot MIN a-z,A-Z,0-1
// Uporabljas da ne dobis praznih lepih linkov.
function ValidNiceLink($str, $min = 3)
{

    if (strlen(CleanNiceLinkText($str)) >= $min) return true;
    else return false;
}

function browser_info($agent = null)
{
    // Declare known browsers to look for
    $known = array('msie', 'firefox', 'safari', 'webkit', 'opera', 'netscape', 'konqueror', 'gecko');

    // Clean up agent and build regex that matches phrases for known browsers
    // (e.g. "Firefox/2.0" or "MSIE 6.0" (This only matches the major and minor
    // version numbers.  E.g. "2.0.0.6" is parsed as simply "2.0"

    $agent = strtolower($agent ? $agent : $_SERVER['HTTP_USER_AGENT']);
    $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9]+?)#';

    // Find all phrases (or return empty array if none found)
    if (!preg_match_all($pattern, $agent, $matches)) return array();

    // Since some UAs have more than one phrase (e.g Firefox has a Gecko phrase,
    // Opera 7,8 have a MSIE phrase), use the last one found (the right-most one
    // in the UA).  That's usually the most correct.
    $i = count($matches['browser']) - 1;
    return array(0 => $matches['browser'][$i], 1 => $matches['version'][$i]);
}

function GenerateRandomCode($len = 3)
{
    $RandomId = md5(uniqid(time()));
    $confirm_chars = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'Z', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    list($usec, $sec) = explode(' ', microtime());
    mt_srand($sec * $usec);
    $max_chars = count($confirm_chars) - 1;
    $code = '';

    for ($i = 0; $i < 3; $i++) {
        $code .= $confirm_chars[mt_rand(0, $max_chars)];
    }

    // shrani kodo v bazo, ob tem izbrisi kode, starejse od 10 minut (600 sekund)
    $cas = time();
    $casb = time() - 600;

    $headers = apache_request_headers();
    if (array_key_exists('X-Forwarded-For', $headers)) {
        $hostname = $headers['X-Forwarded-For'];
    } else {
        $hostname = $_SERVER["REMOTE_ADDR"];
    }

    $result = sisplet_query("DELETE FROM registers WHERE lasttime<$casb");
    $result = sisplet_query("INSERT INTO registers (ip, lasttime, handle, code) VALUES ('$hostname', '$cas', '$RandomId', '$code')");

    return $RandomId;

}

function GetIP()
{
    $headers = apache_request_headers();

    if (array_key_exists('X-Forwarded-For', $headers)) {
        return $headers['X-Forwarded-For'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    return $_SERVER["REMOTE_ADDR"];
}

function CZSToSearch($what)
{

    $originali = array("&scaron;", "&Scaron", "[", "{", "}", "]", "^", "~", "`", "@", "&#268;", "&#269;", "&#381;", "&#382;", "'", "č", "ž", "š", "Č", "Ž", "Š");
    $zamenjave = array("s", "S", "S", "s", "c", "C", "C", "c", "z", "Z", "C", "c", "Z", "z", "", "c", "z", "s", "C", "Z", "S");

    $what = iconv("ISO-8859-2", "YU//TRANSLIT", $what);
    $what = str_replace($originali, $zamenjave, $what);
    /*
        $what = str_replace ("&scaron;", "s", $what);
        $what = str_replace ("&Scaron;", "S", $what);
        $what = str_replace ("[", "S", $what);
        $what = str_replace ("{", "s", $what);
        $what = str_replace ("}", "c", $what);
        $what = str_replace ("]", "C", $what);
        $what = str_replace ("^", "C", $what);
        $what = str_replace ("~", "c", $what);
        $what = str_replace ("`", "z", $what);
        $what = str_replace ("@", "Z", $what);



        // narekovaji
        $what = str_replace ("'", "", $what);
    */

    return $what;
}

function UlCounter($text)
{
    // enostavno ob urejanju vsebine spremeni /uploadi/editor v /ul
    // /ul naj bo Rewrite na /uploadi/counter.php!

    return str_replace($site_url . 'uploadi/editor/', $site_url . '/ul/', $text);
}

function str_lreplace($search, $replace, $subject)
{
    $pos = strrpos($subject, $search);

    if ($pos === false) {
        return $subject;
    } else {
        return substr_replace($subject, $replace, $pos, strlen($search));
    }
}

function do_post_request($url, $data, $optional_headers = null)
{
    $params = array('http' => array('method' => 'POST', 'content' => $data));

    if ($optional_headers !== null) {
        $params['http']['header'] = $optional_headers;
    }

    $ctx = stream_context_create($params);
    $fp = @fopen($url, 'rb', false, $ctx);

    if (!$fp) {
        throw new Exception("Napaka s postanjem na $url, $php_errormsg");
    }

    $response = @stream_get_contents($fp);
    if ($response === false) {
        throw new Exception("Napaka s prebiranjem podatkov iz $url, $php_errormsg");
    }

    return $response;
}

/* Zakodira get parametre urlja v serializiran array z funkcijo base64_encode 
 *  Tako da se iz urlja ne vidi direkt parametrov ankete
 *  se uporablja za izvoz.php
 */
function makeEncodedIzvozUrlString($url = null)
{
    $resultString = '';
    $decodedUrl = '';
    $arrayUrl = array();
    if ($url != null && trim($url) != '') {
        list($base_link, $baseUrl) = explode('?', $url);
        $resultString = $base_link;
        if ($baseUrl != null && trim($baseUrl) != '') {
            $urlGets = explode('&', $baseUrl);
            if (is_array($urlGets) && count($urlGets) > 0) {
                foreach ($urlGets AS $urlGet) {
                    if ($urlGet != null && trim($urlGet) != '') {
                        list($attr, $value) = explode('=', $urlGet);
                        $arrayUrl[$attr] = $value;
                    }

                }
            }
        }
    }
    if (is_array($arrayUrl) && count($arrayUrl) > 0) {
        $decodedUrl = base64_encode(serialize($arrayUrl));
        $resultString .= '?dc=' . $decodedUrl;
    }
    return $resultString;
}

// reversa string escapan z mysqli_real_escape_string
function mysql_real_unescape_string($string)
{

    $string = str_replace("\\n", "\n", $string);
    $string = str_replace("\\r", "\r", $string);
    $string = str_replace("\\\\", "\\", $string);
    $string = str_replace("\\'", "'", $string);
    $string = str_replace('\\"', '"', $string);

    return $string;
}

/**
 * Validate an email address.
 * Provide email address (raw input)
 * Returns true if the email address has the email
 * address format and the domain exists.
 */
function validEmail($email = null){

    $isValid = true;
    $atIndex = strrpos($email, "@");

    if (is_bool($atIndex) && !$atIndex) {
        $isValid = false;
    } 
    else {
        $domain = substr($email, $atIndex + 1);
        $local = substr($email, 0, $atIndex);
        $localLen = strlen($local);
        $domainLen = strlen($domain);
        $domain_parts = explode('.', $domain);

        if ($localLen < 1 || $localLen > 64) {
            // local part length exceeded
            $isValid = false;
        } else if ($domainLen < 1 || $domainLen > 255) {
            // domain part length exceeded
            $isValid = false;
        } else if ($local[0] == '.' || $local[$localLen - 1] == '.') {
            // local part starts or ends with '.'
            $isValid = false;
        } else if ($domain[0] == '.' || $domain[$domainLen - 1] == '.') {
            // domain part starts or ends with '.'
            $isValid = false;
        } else if (preg_match('/\\.\\./', $local)) {
            // local part has two consecutive dots
            $isValid = false;
        } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
            // character not valid in domain part
            $isValid = false;
        } else if (preg_match('/\\.\\./', $domain)) {
            // domain part has two consecutive dots
            $isValid = false;
        } else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\", "", $local))) {
            // character not valid in local part unless
            // local part is quoted
            if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\", "", $local))) {
                $isValid = false;
            }
        } else if (strlen($domain_parts[0]) < 1) {
            // num chars in
            $isValid = false;
        } else if (strlen($domain_parts[1]) < 1) {
            $isValid = false;
        }

        #if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
        #	// domain not found in DNS
        #	$isValid = false;
        #}
    }

    return $isValid;
}

/**
 * Preverimo, ce je geslo dovolj kompleksno
 */
function complexPassword($password){
    
    // Geslo mora imeti vsaj 8 znakov
    if (strlen($password) < 8) {
        return false;
    }

    // Geslo mora vsebovati vsaj eno stevilko
    if (!preg_match("#[0-9]+#", $password)) {
        return false;
    }

    // Geslo mora vsebovati vsaj 1 crko
    if (!preg_match("#[a-zA-Z]+#", $password)) {
        return false;
    }     

    return true;
}

/************************************************
 * Preverimo user type za hierarhijo - default NULL
 *
 * @return INT || null
 ************************************************/
function preveriTipHierarhije()
{
    $type = null;

    global $global_user_id;
    $anketa = isset($_REQUEST['anketa']) ? $_REQUEST['anketa'] : null;

    if (!empty($_SESSION['hierarhija'][$anketa]['type']))
        return false;

    //Ali tabela obstaja
    if(mysqli_num_rows(sisplet_query("SHOW TABLES LIKE 'srv_hierarhija_users'")) == 0){
        return false;
    }


    $sql = sisplet_query("SELECT type FROM srv_hierarhija_users WHERE user_id='" . $global_user_id . "' AND anketa_id='" . $anketa . "'");

    $type = null;
    if (!empty($sql) && mysqli_num_rows($sql) > 0) {
        $row = mysqli_fetch_object($sql);
        $type = $row->type;
    }

    $_SESSION['hierarhija'][$anketa]['type'] = $type;

    return $type;
}

/**
 * Zgenerira url slug samo z dovoljenimi znaki
 *
 * @param string $ime
 * @return string
 */
function slug($ime, $zamenjaj = '-'){
    $tabela = array(
        'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
        'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
        'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
        'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
        'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o',
        'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
        'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r', '/' => '-', ' ' => $zamenjaj
    );

    // Počistimo, če je presledek
    $pocisceno = preg_replace(array('/\s{2,}/', '/[\t\n]/'), ' ', $ime);

    // -- Returns the slug
    return strtolower(strtr($pocisceno, $tabela));
}

/**
 * Preverimo če email obstaja me users ali mes user_emails
 */
function unikatenEmail($email = null){

	$primarni_email = sisplet_query("SELECT email FROM users WHERE email='".$email."'", "obj");
	if(!empty($primarni_email))
		return false;

	$alternativni_email = sisplet_query("SELECT email FROM user_emails WHERE email='".$email."'", "obj");
	if(!empty($alternativni_email))
		return false;

	return true;
}

// Funkcija za debug
function isDebug(){
    global $admin_type, $debug, $site_domain;

    if(isset($debug) && $debug > 0){
        if ($admin_type == 0 || in_array($site_domain, ['test.1ka.si', 'localhost', '1ka.test'])) {
            return true;
        }
    }

    return false;
}

/**
 * Počasno nalaganje polja iz baze
 *
 * Funkcija naredi poizvedbo in vse rezultate shrani v polje, kar manj obremenjuje RAM
 *
 * @param $query
 * @return \Generator
 */
function lazyLoadSqlArray($query)
{
    $polje =  [];
    while($row = mysqli_fetch_assoc($query)){
        yield $polje[] = $row;
    }
}

/**
 * Počasno nalaganje polja iz baze
 *
 * Funkcija naredi poizvedbo in vse rezultate shrani v polje kot objekte
 *
 * @param $query
 * @return \Generator
 */
function lazyLoadSqlObj($query)
{
    $polje =  [];
    while($row = mysqli_fetch_assoc($query)){
        yield $polje[] = (object) $row;
    }
}

/**
 * Default admin temporary directory
 *
 * @param null $file
 * @return string
 */
function admin_temp($file = null)
{
    if(empty($file)){
        return __DIR__ . '/admin/survey/tmp/';
    }

    // V kolikor imamo /, ga odstranimo
    if(substr($file, 0,1) == '/'){
        $file = substr($file, 1);
    }

    return __DIR__ . '/admin/survey/tmp/'.$file;
}

/**
 * Default root directory
 *
 * @param null $file
 * @return string
 */
function root_dir($file = null)
{
    if(empty($file)){
        return __DIR__;
    }

    // V kolikor imamo /, ga odstranimo
    if(substr($file, 0,1) == '/'){
        $file = substr($file, 1);
    }

    return __DIR__ .'/'. $file;
}


// Pri izpolnjevanju ankete preverimo stevilo klikov na minuto - ce jih je prevec, respondenta zavrnemo, drugace se lahko sql zafila in streznik ni vec odziven
function checkClicksPerMinute(){
    global $clicks_per_minute_limit;

    // Ce maximum na minuto ni nastavljen ignoriramo limit
    if(!isset($clicks_per_minute_limit) || $clicks_per_minute_limit == 0)
        return true;

    // Preverimo ce gre za izpolnjevanje ankete
    if($_SERVER["SCRIPT_NAME"] != '/main/survey/index.php')
        return true;

    // Preverimo ce gre za prvi prihod na doloceno stran ankete in ne na prvo stran
    if(isset($_GET['grupa']))
        return true;

    // Preverimo ce je id ankete ustrezno nastavljen
    if(!isset($_GET['anketa']) || $_GET['anketa'] <= 0)
        return true;


    $click_time = time();

    $sql = sisplet_query("SELECT click_count, click_time FROM srv_clicks WHERE ank_id='".$_GET['anketa']."'");
    if (mysqli_num_rows($sql) > 0) {

        list($click_count, $first_click_time) = mysqli_fetch_array($sql);

        // Ce nismo znotraj minute vse resetiramo in pustimo naprej
        if($click_time - $first_click_time > 60){
            $sqlI = sisplet_query("UPDATE srv_clicks SET click_count='1', click_time='".$click_time."' WHERE ank_id='".$_GET['anketa']."'");
            return true;
        }

        // Click count je ok - pustimo naprej
        if($click_count < $clicks_per_minute_limit){
            $sqlI = sisplet_query("UPDATE srv_clicks SET click_count=click_count+1 WHERE ank_id='".$_GET['anketa']."'");         
            return true;
        }
        // Click count je previsok - ZAVRNEMO
        else{
            return false;
        }
    }
    else{
        $sqlI = sisplet_query("INSERT INTO srv_clicks (ank_id, click_count, click_time) VALUES ('".$_GET['anketa']."', '1', '".$click_time."')");
    }
    

    return true;
}

?>