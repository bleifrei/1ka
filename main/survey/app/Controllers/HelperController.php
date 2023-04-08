<?php
/***************************************
 * Description: Notri so zbrane funkcije, ki jih večkrat lahko uporabimo v vseh razredih
 * Autor: Robert Šmalc
 * Created date: 29.01.2016
 *****************************************/

namespace App\Controllers;

use App\Controllers\CheckController as Check;
use App\Controllers\HelperController as Helper;
use App\Models\Model;
use Cache;
use Common;
use enkaParameters;
use Hierarhija\HierarhijaHelper;
use MailAdapter;
use SurveyInfo;
use SurveySetting;
use SurveyUnsubscribe;
use UserAccess;

class HelperController extends Controller
{

    public function __construct()
    {
        parent::getGlobalVariables();
        parent::getAllVariables();
    }

    /************************************************
     * Get instance
     ************************************************/
    private static $_instance;

    public static function getInstance()
    {
        if (self::$_instance)
            return self::$_instance;

        return new HelperController();
    }

    /************************************************
     * Detekcija za katere vrste naprave gre (mobilnik, tablica ali računalnik)
     * 0 - računalnik oz. normalni pogled
     * 1 - mobilnik
     * 2 - tablica
     * @param $_SERVER ['HTTP_USER_AGENT'];
     * @return string
     ************************************************/
    public static function mobile()
    {
        $detect = New \Mobile_Detect();
        $mobile = 0;        // 0 - klasicna, 1 - mobilna, 2 - dlancniki
        $ismobile = 0;

        // detektiramo, ce gre za mobilni aparat
        if ($detect->isMobile())
            $ismobile = 1;

        if ($ismobile == 1) {
            if ($detect->isTablet())
                $mobile = 2;
            else
                $mobile = 1;
        }

        // prednost pa ima rocno izbrana opcija - ce je nastavljena
        if (isset($_COOKIE['mobile'])) {
            $c = (int)$_COOKIE['mobile'];
            if ($c > 0)
                $mobile = $mobile;
            else
                $mobile = 0;
        }

        // Ce smo slucajno v preview-ju preverimo kateri preview prikazemo
        if (isset($_GET['preview']) && $_GET['preview'] == 'on') {
            if (isset($_GET['mobile']) && $_GET['mobile'] == 1)
                $mobile = 1;
            elseif (isset($_GET['mobile']) && $_GET['mobile'] == 2)
                $mobile = 2;
        };

        return $mobile;
    }

    /**
     * @desc V podanem stringu poisce spremenljivke in jih spajpa z vrednostmi
     */
    public static function dataPiping($text)
    {
        // V kolikor imamo vključen modul hierarhija potem uporabimo ustrezno pajpanje za hierarhijo
        if (SurveyInfo::getInstance()->checkSurveyModule('hierarhija'))
            return HierarhijaHelper::dataPiping($text, get('anketa'));


        Common::getInstance()->Init(get('anketa'));

        return Common::getInstance()->dataPiping($text, get('usr_id'), get('loop_id'), get('lang_id'));

    }

    /**
     * vrne IP naslov (tudi ce gre preko proxyja)
     *
     */
    public static function remote_address()
    {
        return GetIP();
    }

    /************************************************
     * Vrnemo ime ankete
     *
     * @param $show_page = 1 - ali prikažemo omenjen naslov na spletni strani
     * @return string
     ************************************************/
    public function displayAkronim($show_page = 1)
    {
        $row = SurveyInfo::getInstance()->getSurveyRow();
        $srv_novaanketa_kratkoime = null;

        SurveySetting::getInstance()->Init(get('anketa'));
        $survey_hide_title = SurveySetting::getInstance()->getSurveyMiscSetting('survey_hide_title');

        if ($survey_hide_title == 1 /*&& $row['survey_type'] != 0*/)
            return;

        if (get('lang_id') != null) {
            if (get('lang_id') != null)
                $_lang = '_' . get('lang_id');
            else
                $_lang = '';

            $srv_novaanketa_kratkoime = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_novaanketa_kratkoime' . $_lang);
        }

        if ($show_page == 1 && get('grupa') > 0) {
            $sql1 = sisplet_query("SELECT naslov FROM srv_grupa WHERE id = '" . get('grupa') . "'");
            $row1 = mysqli_fetch_array($sql1);
            $grupa = '<span> - ' . $row1['naslov'] . '</span>';
        } else $grupa = '';

        if ($srv_novaanketa_kratkoime != '')
            return $srv_novaanketa_kratkoime . $grupa;
        else
            return (($row['akronim'] == null || $row['akronim'] == '') ? $row['naslov'] . $grupa : $row['akronim'] . $grupa);
    }

    /**
     * @desc vrne array vseh spremenljivk vgnezdenih v podanem ifu
     */
    public static function getElements($if)
    {
        $elements = get('getElements');
        $anketa = get('anketa');

        if (array_key_exists($if, $elements)) {
            return $elements[$if];
        }

        $array = array();

        Cache::cache_all_srv_branching($anketa);

        foreach (Cache::srv_branching_parent($anketa, $if) AS $k => $row) {

            if ($row['element_spr'] > 0)
                array_push($array, $row['element_spr']);
            else
                foreach (self::getElements($row['element_if']) AS $key)
                    array_push($array, $key);

        }

        return save('getElements[' . $if . ']', $array, 1);
    }

    /**
     * @desc vrne ce obstaja parent z nastavitvijo horizontal pri blokih da prikazemo vprasanja vodoravno (horizontal==1) ali z razpiranjem (horizontal==2) ce je vklopljena nastavitev
     */
    public static function checkParentHorizontal($element){

        Cache::cache_all_srv_branching(get('anketa'));

        $parent = $element['parent'];

        $rowb = Cache::srv_if($element['element_if']);
        if ($rowb['horizontal'] == 1 || $rowb['horizontal'] == 2) {
            return $rowb['horizontal'];
        } 
        elseif ($parent == 0) {
            return false;
        } 
        elseif ($parent == 0) {
            return false;
        } 
        else {
            $row = Cache::srv_branching(0, $parent);
            return self::checkParentHorizontal($row);
        }
    }

    public static function getGrupa($spremenljivka)
    {
        $grupa = get('getGrupa');

        if (array_key_exists($spremenljivka, $grupa))
            return $grupa[$spremenljivka];

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        return save('getGrupa[' . $spremenljivka . ']', $row['gru_id'], 1);
    }

    public static function getDatepickerRange($spremenljivka, $date_element)
    {
        # po potrebi pripravimo datum range
        $row = Cache::srv_spremenljivka($spremenljivka);
        $newParams = new enkaParameters($row['params']);
        $min = $newParams->get('date_range_min');
        $max = $newParams->get('date_range_max');
        $range_string = null;

        # ce imamo spodnji range
        $range = ($min != "") ? $min : '-80';

        $range .= ':';

        # ce imamo zgornji range - plus je potrebno dodat posebej
        if ($max == "")
            $range .= '+10';
        else if ($max < 100)
            $range .= '+' . $max;
        else
            $range .= $max;

        # ce imamo nastavljen spodnji ali zgornji range
        $range_string = "$( \"{$date_element}\" ).datepicker( \"option\", \"yearRange\", \"{$range}\" );";

        // Ce ni danasnji dan v rangu -> moramo posebej nastavit trenutni datum (zaradi jquery buga)
        if (substr($max, 0, 1) == '-' || substr($min, 0, 1) == '+') {
            $date = '1.1.' . ((int)date("Y") + (int)$min);
            $range_string .= "$( \"{$date_element}\" ).datepicker( \"option\", \"defaultDate\", \"{$date}\" );";
        } elseif ($min > date("Y") || $max < date("Y")) {
            $date = '1.1.' . $min;
            $range_string .= "$( \"{$date_element}\" ).datepicker( \"option\", \"defaultDate\", \"{$date}\" );";
        }

        // dodamo se, da je prvi dan v tednu ponedeljek
        $range_string .= "$( \"{$date_element}\" ).datepicker( \"option\", \"firstDay\", 1 );";

        # nastavimo datumski range
        echo $range_string;
    }

    public static function getCustomCheckbox()
    {

        //ID skin_profile
        $skin_id = SurveyInfo::getInstance()->getSurveyRow()['skin_profile'];

        //srv_theme_editor -> velikost PC/mobilne ikone
        $type = 7;

        if (get('mobile') > 0)
            $type = 8; //če gre za mobilnik ali tablico potem pridobimo ustrezne ikone za tole

        $sql = sisplet_query("SELECT value FROM srv_theme_editor WHERE profile_id = '$skin_id' AND id='7' AND type='$type'");
        $row = mysqli_fetch_array($sql);
        if (!$row || empty($row) || is_null($row))
            return 0;

        return $row['value']; //vrnemo velikost custom radio/checkboxa
    }

    /**
     * @desc poslje alerte o izpolnjeni anketi
     */
    public function alert()
    {
        global $site_path;

        # kadar popravljamo obstoječe podatke ne pošiljamo več obvestil o končani anketi (Ajda)
        if ($_GET['urejanje'] == 1) {
            return;
        }

        // Ce smo v predogledu ali testiranju ne posiljamo obvestila
        if ($_GET['preview'] == 'on') {
            return;
        }

        // Preverimo ce imamo ustrezen paket kjer je na voljo obvescanje
        $userAccess = UserAccess::getInstance(self::$global_user_id);
        if (!$userAccess->checkUserAccess($what='alert')){
            return;
        }

        $row = SurveyInfo::getInstance()->getSurveyRow();

        $sqlAlert = sisplet_query("SELECT * FROM srv_alert WHERE ank_id = '" . get('anketa') . "'");
        $rowAlert = mysqli_fetch_array($sqlAlert);

        // array z emaili
        $emails = array();
        # TODO
        # dodat tabelo, z emaili, za userje cms-ja če bi se želeli odjavit od obveščanja posamezne ankete
        # ker sedaj nimamo povezave na njihov eemaile in se ne morejo odjaviti
        # not bi blo fajn dat: anketa_id, email, code (kodo za preverjanje istovetnosti)
        # in potem če klikne na link odjaviti in skopirati v tabelo: srv_survey_unsubscribe, da se uporabniku ne pošilja več

        $ime = '';

        // alert Avtorja in ostalih v dostopu
        if ($rowAlert['finish_author'] == 1) {
            $sqlInsertUID = Model::db_select_user($row['insert_uid']);
            $rowInsertUID = mysqli_fetch_array($sqlInsertUID);


            // polovimo se avtorje katerim je omogocen dostop in imajo nastavljeno da dobivajo obvestila - po novem med njimi ni nujno glavni avtor ankete
            $sqlAlertAuthors = sisplet_query("SELECT u.email, u.id FROM users as u "
                . " RIGHT JOIN (SELECT sd.uid, sd.alert_complete FROM srv_dostop as sd WHERE sd.ank_id='" . get('anketa') . "' AND sd.alert_complete = '1') AS dostop ON u.id = dostop.uid");
            while ($rowAlertAuthors = mysqli_fetch_assoc($sqlAlertAuthors)) {

                $sql1 = sisplet_query("SELECT alert_complete_if, uid AS id FROM srv_dostop WHERE ank_id='" . get('anketa') . "' AND uid='" . $rowAlertAuthors['id'] . "'");
                $row1 = mysqli_fetch_array($sql1);

                # kodo preberemo iz srv_users
                $c2s = "SELECT SUBSTRING(cookie,1,6) FROM srv_user where ank_id ='" . get('anketa') . "' AND user_id = '" . $rowAlertAuthors['id'] . "'";
                $c2q = sisplet_query($c2s);
                list($c2) = mysqli_fetch_row($c2q);
                # nastavimo password če še ni
                $strSqlUpd = "UPDATE srv_user SET pass='$c2' WHERE ank_id ='" . get('anketa') . "' AND user_id = '" . $rowAlertAuthors['id'] . "' AND pass IS NULL";
                $qrySqlUpd = sisplet_query($strSqlUpd);

                // ce imamo if na posiljanje mailov
                if ($row1['alert_complete_if'] > 0) {
                    if (Check::getInstance()->checkIf($row1['alert_complete_if'])) {
                        $emails[] = array('mail' => $rowAlertAuthors['email'], 'type' => 'author', 'uid' => $rowAlertAuthors['id']);
                    }
                } else {
                    $emails[] = array('mail' => $rowAlertAuthors['email'], 'type' => 'author', 'uid' => $rowAlertAuthors['id']);
                }
            }
        }
        // alert respondenta (ki vpise svoj mail ob izpolnjevanju)
        if ($rowAlert['finish_respondent'] == 1) {
            // email
            $sql1 = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.variable='email' AND s.gru_id=g.id AND g.ank_id='" . get('anketa') . "'");
            if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
            $row1 = mysqli_fetch_array($sql1);

            $sql2 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$row1[id]' AND usr_id='" . get('usr_id') . "'");
            $row2 = mysqli_fetch_array($sql2);

            $sql11 = sisplet_query("SELECT finish_respondent_if FROM srv_alert WHERE ank_id='" . get('anketa') . "'");
            $row11 = mysqli_fetch_array($sql11);

            # kodo preberemo iz srv_users
            $c2s = "SELECT SUBSTRING(cookie,1,6) FROM srv_user where ank_id ='" . get('anketa') . "' AND id = '" . get('usr_id') . "'";
            $c2q = sisplet_query($c2s);
            list($c2) = mysqli_fetch_row($c2q);
            # nastavimo password če še ni
            $strSqlUpd = "UPDATE srv_user SET pass='$c2' WHERE ank_id ='" . get('anketa') . "' AND id = '" . get('usr_id') . "' AND pass IS NULL";
            $qrySqlUpd = sisplet_query($strSqlUpd);

            // Ce resujemo anketo v drugem jeziku pogledamo ce imamo nastavljen custom mail za tuje jezike
            $sql_ac = sisplet_query("SELECT * FROM srv_alert_custom WHERE ank_id='" . get('anketa') . "' AND type='respondent_lang_" . get('lang_id') . "' AND uid='0'");
            if (mysqli_num_rows($sql_ac) > 0) {
                $row_ac = mysqli_fetch_array($sql_ac);
                $emails[] = array('mail' => $row2['text'], 'type' => $row_ac['type'], 'uid' => '0', 'code' => $c2);
            } // ce imamo if na posiljanje mailov
            elseif ($row11['finish_respondent_if'] > 0) {
                if (Check::getInstance()->checkIf($row11['finish_respondent_if'])) {
                    $emails[] = array('mail' => $row2['text'], 'type' => 'respondent', 'uid' => '0', 'code' => $c2);
                }
            } else {
                $emails[] = array('mail' => $row2['text'], 'type' => 'respondent', 'uid' => '0', 'code' => $c2);
            }

            // ime
            $sql1 = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.variable='ime' AND s.gru_id=g.id AND g.ank_id='" . get('anketa') . "'");
            if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
            $row1 = mysqli_fetch_array($sql1);

            $sql2 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$row1[id]' AND usr_id='" . get('usr_id') . "'");
            $row2 = mysqli_fetch_array($sql2);

            $ime = $row2['text'];

        }

        // alert respondenta prepoznanega iz sispleta
        if ($rowAlert['finish_respondent_cms'] == 1) {
            $sql1 = Model::db_select_user(self::$global_user_id);
            $row1 = mysqli_fetch_array($sql1);

            if ($row1['email'] != '') {

                $sql11 = sisplet_query("SELECT finish_respondent_cms_if FROM srv_alert WHERE ank_id='" . get('anketa') . "'");
                $row11 = mysqli_fetch_array($sql11);

                # kodo preberemo iz srv_users
                $c2s = "SELECT SUBSTRING(cookie,1,6) FROM srv_user where ank_id ='" . get('anketa') . "' AND user_id = '" . get('usr_id') . "'";
                $c2q = sisplet_query($c2s);
                list($c2) = mysqli_fetch_row($c2q);

                $strSqlUpd = "UPDATE srv_user SET pass='$c2' WHERE ank_id ='" . get('anketa') . "' AND user_id = '" . get('usr_id') . "' AND pass IS NULL";
                $qrySqlUpd = sisplet_query($strSqlUpd);

                // ce imamo if na posiljanje mailov
                if ($row11['finish_respondent_cms_if'] > 0) {
                    if (Check::getInstance()->checkIf($row11['finish_respondent_cms_if'])) {
                        $emails[] = array('mail' => $row1['email'], 'type' => 'respondent_cms', 'uid' => '0', 'code' => $c2);
                    }
                } else {
                    $emails[] = array('mail' => $row1['email'], 'type' => 'respondent_cms', 'uid' => '0', 'code' => $c2);
                }

            }

        }

        // alert na ostale maile
        if ($rowAlert['finish_other'] == 1) {

            $email = explode("\n", str_replace("\r", "", str_replace(",", "\r\n", str_replace(" ", "", $rowAlert['finish_other_emails']))));
            if (count($email) > 0)
                foreach ($email AS $mail) {

                    $sql11 = sisplet_query("SELECT finish_other_if FROM srv_alert WHERE ank_id='" . get('anketa') . "'");
                    $row11 = mysqli_fetch_array($sql11);

                    // ce imamo if na posiljanje mailov
                    if ($row11['finish_other_if'] > 0) {
                        if (Check::getInstance()->checkIf($row11['finish_other_if'])) {
                            $emails[] = array('mail' => $mail, 'type' => 'other', 'uid' => '0');
                        }
                    } else {
                        $emails[] = array('mail' => $mail, 'type' => 'other', 'uid' => '0');
                    }
                }
        }

        // sestavimo sporocilo
        if ($row['finish_respondent_cms'] == 1) {
            $sql1 = Model::db_select_user(self::$global_user_id);
            $row1 = mysqli_fetch_array($sql1);
            $ime = '' . $row1['name'] . ' ' . $row1['surname'];
        }

        # meta_url
        $sql_meta_strings = "SELECT referer FROM srv_user where ank_id ='" . get('anketa') . "' AND id = '" . get('usr_id') . "'";
        $sql_meta_query = sisplet_query($sql_meta_strings);
        list($meta_url) = mysqli_fetch_row($sql_meta_query);

        // Podpis
        $signature = Common::getEmailSignature();

        $text = ($rowAlert['finish_text'] == '') ? '[NAME] ' . self::$lang['srv_alert_msg'] . ' [SURVEY] [DATE] ("[URL]")'.$signature : $rowAlert['finish_text'];
        $subject = ($rowAlert['finish_subject'] != '') ? $rowAlert['finish_subject'] : self::$lang['srv_alert_subject'];

        $sqlu = sisplet_query("SELECT email FROM users WHERE id = '$row[insert_uid]'");
        $rowu = mysqli_fetch_array($sqlu);
        $MailReply = $rowu['email'];

        if ($rowAlert['reply_to'] != '') $MailReply = $rowAlert['reply_to'];

        // preprečimo večkratno pošiljanje na iste naslove
        array_unique($emails);

        # preverimo odjavljenost od prejemanja obvestil
        $su = new SurveyUnsubscribe(get('anketa'));
		
        // posljemo maile
        foreach ($emails AS $mail_list) {
            $mail = trim($mail_list['mail']);
            $unsubscribed = $su->isUnsubscribedEmail($mail);


            $pdf_url = self::$site_url . 'admin/survey/izvoz.php?dc=' . base64_encode(
                    serialize(
                        array('a' => 'pdf_results',
                            'anketa' => get('anketa'),
                            'usr_id' => get('usr_id'),
                            'type' => '0',
                            'pdf_usr_type' => $mail_list['type'],
                            'pdf_usr_id' => $mail_list['uid']
                        )));

            $rtf_url = self::$site_url . 'admin/survey/izvoz.php?dc=' . base64_encode(
                    serialize(
                        array('a' => 'rtf_results',
                            'anketa' => get('anketa'),
                            'b' => 'export',
                            'usr_id' => get('usr_id'),
                            'pdf_usr_type' => $mail_list['type'],
                            'pdf_usr_id' => $mail_list['uid']
                        )));


            if ($mail != '' && $unsubscribed == false && (strlen($mail) > 1)) {

                $sql_ac = sisplet_query("SELECT subject, text FROM srv_alert_custom WHERE ank_id='" . get('anketa') . "' AND type='{$mail_list['type']}' AND uid='{$mail_list['uid']}'");
                if (mysqli_num_rows($sql_ac) > 0) {
                    $row_ac = mysqli_fetch_array($sql_ac);
                    $s = $row_ac['subject'];
                    $t = $row_ac['text'];
                } else {
                    $s = $subject;
                    $t = $text;
                }
                // naredimo še data piping za odgovore
                $t = Helper::dataPiping($t);

                #zamenjamo morebitne sistemske variable
                $t = str_replace(
                    array('[NAME]',
                        '[SURVEY]',
                        '[DATE]',
                        '[SITE]',
                        '[URL]',
                        '[PDF]',
                        '[RTF]',
                        '[META_REFERER_URL]',
                        '[DURATION]'
                    ),
                    array($ime,
                        $row['akronim'], #$row['naslov'],
                        date("d.m.Y, H:i:s"),
                        '<a href="' . SurveyInfo::getSurveyLink() . '">' . SurveyInfo::getSurveyLink() . '</a>',
                        '<a href="' . self::$site_url . 'admin/survey/index.php?anketa=' . get('anketa') . '">' . self::$site_url . 'admin/survey/index.php?anketa=' . get('anketa') . '</a>',
                        '<a href="' . $pdf_url . '">' . self::$lang['srv_alert_link_pdf'] . '</a>',
                        '<a href="' . $rtf_url . '">' . self::$lang['srv_alert_link_rtf'] . '</a>',
                        '<a href="' . $meta_url . '">' . $meta_url . '</a>',
                        '<a href="' . self::$site_url . 'admin/survey/index.php?anketa=' . get('anketa') . '&amp;a=trajanje">' . self::$lang['srv_activate_duration'] . '</a>'
                    ),
                    $t);

                $s = str_replace(
                    array('[NAME]',
                        '[SURVEY]',
                        '[DATE]',
                        '[SITE]',
                        '[URL]',
                        '[PDF]',
                        '[RTF]',
                        '[DURATION]'),
                    array($ime,
                        $row['naslov'],
                        date("d.m.Y, H:i:s"),
                        SurveyInfo::getSurveyLink(),
                        self::$site_url . 'admin/survey/index.php?anketa=' . get('anketa'),
                        '<a href="' . $pdf_url . '">' . self::$lang['srv_alert_link_pdf'] . '</a>',
                        '<a href="' . $rtf_url . '">' . self::$lang['srv_alert_link_rtf'] . '</a>',
                        '<a href="' . self::$site_url . 'admin/survey/index.php?anketa=' . get('anketa') . '&amp;a=trajanje">' . self::$lang['srv_activate_duration'] . '</a>'),
                    $s);

                if ($mail_list['code'] == null || trim($mail_list['code']) == '') {
                    # v tabelo srv_survey_unsubscribe_codes dodamo email in kodo za možnost odjave od obveščanja
                    $uc = $su->generateCodeForEmail($mail);
                    $unsubscribe = self::$site_url . 'admin/survey/unsubscribe.php?anketa=' . get('anketa') . '&uc=' . $uc . '&em=' . base64_encode($mail);
                } else {
                    $unsubscribe = self::$site_url . 'admin/survey/unsubscribe.php?anketa=' . get('anketa') . '&code=' . $mail_list['code'];
                }

                $t = str_replace(
                    array('#UNSUBSCRIBE#', '[UNSUBSCRIBE]'),
                    array('<a href="' . $unsubscribe . '">' . self::$lang['user_bye_hl'] . '</a>',
                        '<a href="' . $unsubscribe . '">' . self::$lang['user_bye_hl'] . '</a>',)
                    , $t);


                try {
                    $MA = new MailAdapter(get('anketa'), $type='alert');
                    $MA->addRecipients($mail);

                    // Shranimo email from in reply to (da ju potem nazaj nastavimo - drugace povozimo nastavitve od vabil)
                    $mailFromBCK = $MA->getMailFrom();
                    $mailReplyToBCK = $MA->getMailReplyTo();

                    // Nastavimo na raziskave - obvescanje je iz raziskave@1ka.si ce se ni nastavljeno posebej v smtp nastavitvah
                    $MA->setMailReplyTo($MailReply);
                    if ($mailFromBCK == '' || $mailFromBCK == 'info@1ka.si')
                        $MA->setMailFrom('raziskave@1ka.si');

                    // Posljemo mail
                    $result = $MA->sendMail(stripslashes($t), $s);

                    // Nazaj nastavimo posiljatelja (za nastavitve v vabilih)
                    $MA->setMailReplyTo($mailReplyToBCK);
                    $MA->setMailFrom($mailFromBCK);
                } catch (Exception $e) {
                }
            }
        }

        return;
    }

    /**
     * @desc poslje alert o dosezeni kvoti za evoli - team meter
     */
    public function alertTeamMeter()
    {
        global $site_path;

        # kadar popravljamo obstoječe podatke ne pošiljamo več obvestil o končani anketi (Ajda)
        if ($_GET['urejanje'] == 1) {
            return;
        }

        // Ce smo v predogledu ali testiranju ne posiljamo obvestila
        if ($_GET['preview'] == 'on') {
            return;
        }


        // Preverimo ce smo dosegli kvoto
        $sqlGroupTM = sisplet_query("SELECT d.vre_id FROM srv_data_vrednost_active d, srv_spremenljivka s, srv_grupa g
										WHERE g.ank_id='" . get('anketa') . "' AND d.usr_id='" . get('usr_id') . "' AND s.skupine='1'
											AND s.id=d.spr_id AND g.id=s.gru_id");
        $rowGroupTM = mysqli_fetch_array($sqlGroupTM);

        $sqlTM = sisplet_query("SELECT tm.*, v.vrstni_red AS skupina FROM srv_evoli_teammeter tm, srv_vrednost v
									WHERE tm.ank_id='" . get('anketa') . "' AND tm.skupina_id='" . $rowGroupTM['vre_id'] . "' AND tm.skupina_id=v.id");
        $rowTM = mysqli_fetch_array($sqlTM);
		
		// Preverimo ce smo slucajno ze posiljali porocilo
		$datum_posiljanja = $rowTM['datum_posiljanja'];

        // Kvota je dosezena - POSLJEMO OBVESTILO
        if ($rowTM['kvota_max'] == $rowTM['kvota_val'] && $datum_posiljanja == '0000-00-00 00:00:00') {

            $row = SurveyInfo::getInstance()->getSurveyRow();

            $sqlAlert = sisplet_query("SELECT * FROM srv_alert WHERE ank_id = '" . get('anketa') . "'");
            $rowAlert = mysqli_fetch_array($sqlAlert);

            // Podpis
            $signature = Common::getEmailSignature();

            $text = ($rowAlert['finish_text'] == '') ? '[NAME] ' . self::$lang['srv_alert_msg'] . ' [SURVEY] [DATE] ("[URL]")'.$signature : $rowAlert['finish_text'];
            $subject = ($rowAlert['finish_subject'] != '') ? $rowAlert['finish_subject'] : self::$lang['srv_alert_subject'];

            $MailReply = $rowTM['email'];

            if ($rowAlert['reply_to'] != '') $MailReply = $rowAlert['reply_to'];

            // Mail posljemo avtorju skupine (podjetja)
			$mail = $rowTM['email'];

			$pdf_url = self::$site_url . 'admin/survey/izvoz.php?dc=' . base64_encode(
					serialize(
						array('m' => 'pdf_teammeter',
							'anketa' => get('anketa'),
							'skupina' => $rowTM['skupina'],
							'lang_id' => $rowTM['lang_id']
						)));

			if ($mail != '' && (strlen($mail) > 1)) {

				$s = $subject;
				$t = $text;

				// naredimo še data piping za odgovore
				$t = Helper::dataPiping($t);

				#zamenjamo morebitne sistemske variable
				$t = str_replace(
					array(
						'[NAME]',
						'[SURVEY]',
						'[DATE]',
						'[SITE]',
						'[URL]',
						'[PDF]',
						'[DURATION]'
					),
					array(
						$ime,
						$row['akronim'], #$row['naslov'],
						date("d.m.Y, H:i:s"),
						'<a href="' . SurveyInfo::getSurveyLink() . '">' . SurveyInfo::getSurveyLink() . '</a>',
						'<a href="' . self::$site_url . 'admin/survey/index.php?anketa=' . get('anketa') . '">' . self::$site_url . 'admin/survey/index.php?anketa=' . get('anketa') . '</a>',
						'<a href="' . $pdf_url . '">' . self::$lang['srv_alert_link_pdf'] . '</a>',
						'<a href="' . self::$site_url . 'admin/survey/index.php?anketa=' . get('anketa') . '&amp;a=trajanje">' . self::$lang['srv_activate_duration'] . '</a>'
					),
				$t);

				$s = str_replace(
					array(
						'[NAME]',
						'[SURVEY]',
						'[DATE]',
						'[SITE]',
						'[URL]',
						'[PDF]',
						'[DURATION]'
					),
					array(
						$ime,
						$row['naslov'],
						date("d.m.Y, H:i:s"),
						SurveyInfo::getSurveyLink(),
						self::$site_url . 'admin/survey/index.php?anketa=' . get('anketa'),
						'<a href="' . $pdf_url . '">' . self::$lang['srv_alert_link_pdf'] . '</a>',
						'<a href="' . self::$site_url . 'admin/survey/index.php?anketa=' . get('anketa') . '&amp;a=trajanje">' . self::$lang['srv_activate_duration'] . '</a>'
					),
				$s);

				try {
					$MA = new MailAdapter(get('anketa'), $type='alert');
					$MA->addRecipients($mail);

					// Shranimo email from in reply to (da ju potem nazaj nastavimo - drugace povozimo nastavitve od vabil)
					$mailFromBCK = $MA->getMailFrom();
					$mailReplyToBCK = $MA->getMailReplyTo();

					// Nastavimo na raziskave - obvescanje je iz raziskave@1ka.si ce se ni nastavljeno posebej v smtp nastavitvah
					$MA->setMailReplyTo($MailReply);
					if ($mailFromBCK == '' || $mailFromBCK == 'info@1ka.si')
						$MA->setMailFrom('raziskave@1ka.si');

					// Posljemo mail
					$result = $MA->sendMail(stripslashes($t), $s);

					// Nazaj nastavimo posiljatelja (za nastavitve v vabilih)
					$MA->setMailReplyTo($mailReplyToBCK);
					$MA->setMailFrom($mailFromBCK);
				} 
				catch (Exception $e) {
				}
				
				// Updatamo timestamp posiljanja
				$sql = sisplet_query("UPDATE srv_evoli_teammeter SET datum_posiljanja=NOW() WHERE id='".$rowTM['id']."'");
            }
        }

        return;
    }
}