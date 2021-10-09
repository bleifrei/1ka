<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 04.02.2016
 *****************************************/

namespace App\Controllers;

use App\Controllers\BodyController as Body;
use App\Controllers\CheckController as Check;
use App\Controllers\DisplayController as Display;
use App\Controllers\FindController as Find;
use App\Controllers\HeaderController as Header;
use App\Controllers\HelperController as Helper;
use App\Controllers\LanguageController as Language;
use App\Models\Model;
use App\Models\SaveSurvey;
use Common;
use Mobile_Detect;
use SurveyInfo;
use SurveySetting;
use SurveySlideshow;
use GDPR;
use MAZA;
use UserAccess;


class InitClass extends Controller
{
    protected $get;

    public function __construct()
    {
        parent::getGlobalVariables();

        //inicializiramo $variable v $this, da jih lažje prikličemo
        parent::getAllVariables();

        if (!get('printPreview')) {
            return $this->loadIfPrintPreviewFalse();
        } else {
            return $this->loadIfPrintPreviewTrue();
        }
    }

    /************************************************
     * Print preview je izključen
     ************************************************/
    private function loadIfPrintPreviewFalse()
    {
        // cist na zacetku preverimo referer. Ce je prisel od kje drugje (napacno skopiran link itd...) ga preusmerimo na prvo stran ankete
        if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], self::$site_url) === false && !isset($_GET['code']) && !isset($_GET['return']) && !isset($_GET['params'])) {

            $anketa = (isset($_GET['anketa'])) ? $_GET['anketa'] : ((isset($_POST['anketa'])) ? $_POST['anketa'] : die("Missing anketa id!"));
            save('anketa', $anketa);

            // Pri ul evalvaciji tega ne pustimo, ker drugace narobe preusmeri
            if (Common::checkModule('evalvacija') == '0') {

                // to more bit, ker zgleda da pri redirectu browser ne nastavi novega refererja... (tudi websm anketa ne rabi tega - vedno mora skocit na ustrezno stran)
                if ($_COOKIE['ref'] != get('anketa') && !(get('anketa') == get('webSMSurvey') && Common::checkModule('websmsurvey') == '1')) {

                    // prenesemo sistemske spremenljivke, ki so podane preko URLja
                    $sql1 = sisplet_query("SELECT s.id, s.variable FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='" . get('anketa') . "' AND s.gru_id=g.id AND s.sistem='1' AND (s.tip='4' OR s.tip='21' OR s.tip='1')");
                    $g = '';
                    while ($row1 = mysqli_fetch_array($sql1))
                        if (isset($_GET[$row1['variable']])) $g .= '&' . $row1['variable'] . '=' . $_GET[$row1['variable']];

                    $g .= Header::getSurveyParams();

                    //$redirect_url = self::$site_url."a/".get('anketa').$g;
                    $redirect_url = SurveyInfo::getSurveyLink(false, false) . $g;
                    $request_url = 'http' . ($_SERVER['HTTPS'] ? 's' : null) . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

                    if ($redirect_url != $request_url) {    // to je extra check, da pridejo skozi in se ne vrtijo v neskoncni zanki tudi ljudje (paranoiki :) ) brez cookiejev
                        setcookie('ref', get('anketa'));    // cookie z referalom

                        header("Location: " . $redirect_url);
                        die();
                    }
                }
            }
        }

        // ce se je vrnil na anketo preko URLja
        if (isset($_GET['return'])) {
            Check::getInstance()->check_cookie_return();
        }

        if (isset($_GET['anketa']) || isset($_POST['anketa'])) {
			
            $anketa = (isset($_GET['anketa'])) ? $_GET['anketa'] : ((isset($_POST['anketa'])) ? $_POST['anketa'] : die("Missing anketa id!"));
            save('anketa', $anketa);		

            // Preverimo ce gre za POSEBNO webSM anketo (ki ne shranjuje nicesar - samo direktno preusmeri na ustrezno stran)
            if (get('anketa') == get('webSMSurvey') && Common::checkModule('websmsurvey') == '1')
                $this->jeWebSMSurvey();

            // polovimo podatke o anketi
            SurveyInfo::getInstance()->SurveyInit(get('anketa'));
            if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
                save('db_table', '_active');

            $rowa = SurveyInfo::getInstance()->getSurveyRow();
			
			// Ce anketa sploh ne obstaja
			if (!$rowa) {
                Display::getInstance()->displayNapaka(self::$lang['no_survey']);
                die();
            }

			// Ce nima dostopa do ankete
            if (!((self::$admin_type <= $rowa['odgovarja'] && self::$admin_type >= 0) || ($rowa['odgovarja'] == 4))) {
                Display::getInstance()->displayNapaka(self::$lang['no_access']);
                die();
            }

			
            // pretecena anketa, kontroliramo datum na: starts in expire
            $stringe = "SELECT id , CURDATE(), starts, expire FROM srv_anketa WHERE id='" . get('anketa') . "' AND starts <= CURDATE() AND expire >= CURDATE()";
            $sqle = sisplet_query($stringe);

            // Tudi ce je zakljucena pustimo prikaz pri nastavljenem glas_end -> preklop arhiva statistike pri glasovanju - drugace izpisemo obvestilo o zakljucku
            if (!(isset($_GET['preview']) && $_GET['preview'] == 'on') && ($rowa['active'] < 1 or mysqli_num_rows($sqle) == 0) && (!isset($_GET['glas_end']) && (!isset($_GET['urejanje']))))
                $this->anketaEnd($rowa);

			
            // Preverimo ce je nastavljen staticen uvod (ki ne belezi nicesar) - da se pri embeddanih anketah ne shranjujejo vsi obiskovalci ampak se user ustvari sele na prvi strani po uvodu
            if ($rowa['intro_static'] > 0 && $rowa['show_intro'] == 1 && count($_POST) == 0 && !isset($_GET['grupa'])) {

                // inicializiramo jezik za multilang anketo
                Language::getInstance()->multilang_init();

                // konec ukvarjanja s cookieji, prikazemo header
                Header::getInstance()->header();

                Body::getInstance()->displayStaticIntroduction();
                die();
            } // Ce imamo staticen uvod s captcho in smo kliknili na naslednjo stran, najprej preverimo captcho
            elseif ($rowa['intro_static'] == 2 && $rowa['show_intro'] == 1 && count($_POST) != 0 && isset($_GET['grupa']) && $_GET['grupa'] == '0') {
                Check::getInstance()->check_captcha_intro();
            }
			
			
            // ali je respondent avtor ankete (zaradi posebnih opcij prepoznave)
            $sqlUserAutor = sisplet_query("SELECT ank_id, uid FROM srv_dostop WHERE ank_id='" . get('anketa') . "' AND uid='" . self::$global_user_id . "'");
            if (mysqli_num_rows($sqlUserAutor) > 0)
                save('userAutor', true);

            // Ce imamo nastavljeno da vedno zahtevamo kodo
            if (isset($_GET['code']) && $rowa['usercode_required'] == 1) {
                unset($_GET['code']);
            }

            // pogledamo na kolk je nastavljen expire za cookie v nastavitvi ankete
            $row = SurveyInfo::getInstance()->getSurveyRow();
            save('cookie_expire', $row['cookie']);

            if ($row['cookie'] == 0) {
                $this->expire = 0;
            } elseif ($row['cookie'] == -1) {
                if ($rowa['user_from_cms'] == 1) {
                    $this->expire = time() - 3600;
                } else {
                    $this->expire = 0;
                }
            } elseif ($row['cookie'] == 1) {
                $this->expire = time() + 3600;
            } else {
                $this->expire = time() + 2500000;
            }

            // Preberimo cookie stringiii
            $this->cookie = null;

            // da delata oba načina nastavljanja piškotkov, neglede na nastavitve
            if (isset($_GET['survey-' . get('anketa')]))
                $this->cookie = $_GET['survey-' . get('anketa')];            // Pri nastavitvi brez cookie-ja se cookie string prenaša preko URL-ja
            if (isset($_COOKIE['survey-' . get('anketa')]))
                $this->cookie = $_COOKIE['survey-' . get('anketa')];        // Obicajen cookie


            // EVALVACIJA - Ce je nov respondent preverimo ce se je vrnil (zapis v tabeli eval_data_userCookie) in mu nastavimo ustrezen cookie da ga preusmerimo na ustrezno stran
            if (Common::checkModule('evalvacija') == '1' && $this->cookie == null)
                $this->cookie = $this->evlavacijaNovRespondent();

            // HIERARHIJA - UČITELJ, preverimo, če je učitelj ponovno prišel nazaj
            if (Common::checkModule('hierarhija') == '1' && SurveyInfo::checkSurveyModule('hierarhija') == 2 && is_null($this->cookie))
                    $this->cookie = $this->hierarhijaPreveriCeSeJeUciteljVrnilPonovnoResevatiAnketo();

            // poskrbimo za user ID
            //
            // tuki je kookie ze nastavljen in ga samo preberemo
            if ($this->cookie != null)
                $this->cookieExist($this->cookie);

            // kukija se ni, ga bomo pa nastavil
            if ($this->cookie == null)
                $this->cookieIsNull();


            // tukaj bomo dodatno preverjali, ce slucajno kje&kdaj pride do napake da usr_id ni postavljen
            if (get('usr_id') == 0) {
                $get = '';
                foreach ($_GET AS $key => $val) {
                    if ($get != '')
                        $get .= ', ';
                    $get .= $key . ': "' . $val . '"';
                }

                $post = '';
                foreach ($_POST AS $key => $val) {
                    if ($post != '')
                        $post .= ', ';
                    $post .= $key . ': "' . $val . '"';
                }
                $kuki = '';
                foreach ($_COOKIE AS $key => $val) {
                    if ($kuki != '')
                        $kuki .= ', ';
                    $kuki .= $key . ': "' . $val . '"';
                }
                $text = 'GET: ' . $get . '; POST: ' . $post . '; COOKIE: ' . $kuki;

                $sql_log = sisplet_query("SELECT value FROM srv_survey_misc WHERE sid = '" . get('anketa') . "' AND what = 'usr_id_error'");
                $row_log = mysqli_fetch_array($sql_log);
                $text = $row_log['value'] . "\n" . $text;

                sisplet_query("REPLACE INTO srv_survey_misc (sid, what, value) VALUES ('" . get('anketa') . "', 'usr_id_error', '" . $text . "')");
            }

            // inicializiramo jezik za multilang anketo
            Language::getInstance()->multilang_init();

            // konec ukvarjanja s cookieji, prikazemo header
            Header::getInstance()->header();

            //prikaz konca ankete - posebej statistika pri glasovanju
            if (isset($_GET['glas_end'])) {

                Body::getInstance()->displayKonecGlasovanje();

            } else {
                if (isset($_GET['loop_id'])) save('loop_id', $_GET['loop_id']);

                // nismo se poslali kaksno stran z odgovori
                if (count($_POST) == 0) {

                    // Preverimo kvoto pri Evoli Team Meter
                    if (SurveyInfo::getInstance()->checkSurveyModule('evoli_teammeter') 
                        || SurveyInfo::getInstance()->checkSurveyModule('evoli_quality_climate')
                        || SurveyInfo::getInstance()->checkSurveyModule('evoli_teamship_meter')
                        || SurveyInfo::getInstance()->checkSurveyModule('evoli_organizational_employeeship_meter')
                    ) {

                        // Ce imamo nastavljeno skupino
                        if (isset($_GET['skupina'])) {

                            $skupina_id = $_GET['skupina'];

                            $sqlGroupTM = sisplet_query("SELECT kvota_val, kvota_max, date_to FROM srv_evoli_teammeter WHERE ank_id='" . get('anketa') . "' AND skupina_id='" . $skupina_id . "'");
                            $rowGroupTM = mysqli_fetch_array($sqlGroupTM);

                            // Ce je kvota ze dosezena
                            if ($rowGroupTM['kvota_val'] >= $rowGroupTM['kvota_max']) {
                                Body::getInstance()->displayKonecEvoliTM();
                                die();
                            }

                            // Ce je datum ze presezen
                            if (strtotime($rowGroupTM['date_to']) < time() - (60 * 60 * 24)) {
                                $date_to = date('d.m.Y', strtotime($rowGroupTM['date_to']));
                                Body::getInstance()->displayKonecEvoliTM($date_to);
                                die();
                            }
                        }
                    }

                    if (isset($_GET['grupa']))
                        save('grupa', $_GET['grupa']);

                    if ($rowa['show_intro'] == 0 && !isset($_GET['grupa'])) {
                        $this->set_userstatus(3);
                        SaveSurvey::saveSistemske();
                        save('grupa', Find::getInstance()->findNextGrupa());
                    }

                    if (get('grupa') == 'end') {
                        $this->set_userstatus(6);

                        Body::getInstance()->displayKonec();

                    } 
                    elseif (get('displayAllPages')) {

                        Body::getInstance()->displayAllPages();

                        
                    } 
                    // prikazemo ustrezno stran / grupo
                    elseif (get('grupa') > 0) {

                        $preskok = false;

                        // pogledamo ce preskocimo kaksno stran zaradi branchinga
                        while (!Check::getInstance()->checkGrupa() && get('grupa') > 0 && $_GET['disableif'] != 1) {

                            SaveSurvey::getInstance()->posted(1);
                            save('grupa', Find::getInstance()->findNextGrupa());
                            $preskok = true;
                        }

                        if ($preskok) SaveSurvey::getInstance()->posted_commit();    // pri preskokih se ne shranjuje sproti v bazo, ampak na koncu, vse naenkrat

                        if (get('grupa') > 0) {
                            Body::getInstance()->displayAnketa();
                        } else {
                            $this->set_userstatus(6);
                            Body::getInstance()->displayKonec();
                        }

                        // prikazemo uvodni nagovor - introduction
                    } else {

                        // nastavimo status -- kliknil je na anketo
                        $this->set_userstatus(3);

                        Body::getInstance()->displayIntroduction();
                    }  
                } 
                // poslani so bili odgovori
                else {

                    save('grupa', $_GET['grupa']);

					// Preverimo ce gre za gdpr anketo in ce je sprejel pogoje za sodelovanje
					if(GDPR::isGDPRSurveyTemplate(get('anketa'))){
						
						// Ni sprejel pogojev za sodelovanje - vrzemo na zakljucek
						if(isset($_POST['gdpr']) && $_POST['gdpr_accept'] == '0'){
							Body::getInstance()->displayKonec();
							die();
						}
						// Ni oznacil ali sprejema pogoje
						elseif(isset($_POST['gdpr']) && !isset($_POST['gdpr_accept'])){
							Body::getInstance()->displayIntroduction();
							die();
						}
					}
					
                    if (get('grupa') == 'end') {
                        $this->set_userstatus(6);

                        Body::getInstance()->displayKonec();

                    } 
                    else {
                        // ce je nastavljena grupa (se pravi ni prva stran) in ce nismo v predogledu ankete
                        if (get('grupa') > 0 || get('displayAllPages')) {
                            // nastavimo status -- izpolnjuje anketo
                            $this->set_userstatus(5);

                            // shrani poslano stran v bazo
                            SaveSurvey::getInstance()->posted();

                        } else {    // prva stran z vprasanji

                            $s = sisplet_query("REPLACE INTO srv_user_grupa" . get('db_table') . " (gru_id, usr_id, time_edit) VALUES ('0', '" . get('usr_id') . "', NOW())");
                            if (!$s) echo mysqli_error($GLOBALS['connect_db']);
                            $this->set_userstatus(4);
                        }

                        if (get('loop_AW') == 0 && get('loop_id') == null) {
                            $grupa = Find::getInstance()->findNextGrupa();
                        } 
                        elseif (get('loop_AW') == 1) {
                            $grupa = get('grupa');
                        } 
                        elseif (get('loop_id') != null) {
                            $grupa = get('grupa');
                            save('loop_id', Find::getInstance()->findNextLoopId(), 1);
                            if (get('loop_id') == null)
                                $grupa = Find::getInstance()->findNextGrupa();
                        }

                        if (get('displayAllPages')) {

                            echo '    <script>' . "\n";
                            echo '      window.close();' . "\n";
							if(Common::checkModule('gorenje'))
								echo '      document.location.href=\'https://surveys.gorenje.com/\';' . "\n";
							else
								echo '      document.location.href=\'https://www.1ka.si/\';' . "\n";
                            echo '    </script>' . "\n";

                        } 
                        elseif ($grupa > 0) {

                            save('grupa', $grupa);
                            
                            if (get('loop_AW') == 0 && get('loop_id') == null)
                                header('Location: ' . SurveyInfo::getSurveyLink(false, false) . '&grupa=' . get('grupa') . Header::getSurveyParams() . get('cookie_url') . '');
                            elseif (get('loop_AW') == 1)
                                header('Location: ' . SurveyInfo::getSurveyLink(false, false) . '&grupa=' . get('grupa') . '&ime=' . get('ime_AW') . Header::getSurveyParams() . get('cookie_url') . '');
                            elseif (get('loop_id') != null)
                                header('Location: ' . SurveyInfo::getSurveyLink(false, false) . '&grupa=' . get('grupa') . '&loop_id=' . get('loop_id') . Header::getSurveyParams() . get('cookie_url') . '');

                        } 
                        else {

                            // nastavimo status -- anketo je izpolnil do konca
                            $this->set_userstatus(6);

                            //prikaz konca ankete - pri glasovanju izpisujemo posebej zakljucek in statistiko
                            if ($rowa['survey_type'] == 0) {
                                Body::getInstance()->displayKonecGlasovanje();
                            } else {
                                Body::getInstance()->displayKonec();
                            }

                        }
                    }
                }
            }
        }

        // prisli smo cez vse silne procedure in redirecte, lahko pobrisemo cookie za referer, ker ga (upam) ne rabimo vec
        setcookie('ref', '', time() - 5000);    // pobrisemo cookie
    }

    /**
     * Preverimo, če učitelj rešuje anketo in če že ima usr_id shranjen v bazi ali ga še ni
     *
     * @return mixed $cookie
     */
    private function hierarhijaPreveriCeSeJeUciteljVrnilPonovnoResevatiAnketo()
    {
        $url = base64_decode(urldecode($this->get->enc));

        $cookie = sisplet_query("SELECT su.cookie AS cookie FROM srv_hierarhija_koda AS shk LEFT JOIN srv_user AS su ON (shk.srv_user_id = su.id) WHERE url='" . $url . "'", "obj")->cookie;
        return $cookie;
    }

    /************************************************
     * Naložimo v kolikor imamo vkloplje print preview TRUE
     ************************************************/
    private function loadIfPrintPreviewTrue()
    {
        $anketa = (isset($_GET['anketa'])) ? $_GET['anketa'] : ((isset($_POST['anketa'])) ? $_POST['anketa'] : die("Missing anketa id!"));
        if (get('anketa') != $anketa)
            save('anketa', $anketa);

        // polovimo podatke o anketi
        \SurveyInfo::getInstance()->SurveyInit(get('anketa'));
        if (\SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
            save('db_table', '_active');

        \SurveySetting::getInstance()->Init(get('anketa'));
        save('usr_id', $_REQUEST['usr_id']);

        Language::getInstance()->multilang_init();
    }


    /************************************************
     * Če gre za posebno testno anketo WebSMSruvey
     ************************************************/
    private function jeWebSMSurvey()
    {

        // inicializiramo jezik za multilang anketo
        $row = SurveyInfo::getInstance()->getSurveyRow();
        if (isset($_GET['language'])) {    // jezik podan preko GETa (podan je ze v linku ali spremenimo v previewu)
            $sqll = sisplet_query("SELECT lang_id FROM srv_language WHERE ank_id='" . get('anketa') . "' AND lang_id='$_GET[language]'");
            $rowl = mysqli_fetch_array($sqll);
            save('lang_id', $rowl['lang_id']);
        } elseif (isset($_POST['language'])) {    // jezik podan v POSTu (ce si ga user spremeni na prvi strani)
            save('lang_id', $_POST['language']);
        }
        if (get('lang_id') == null) {    // ni bil podan preko GETa in ni shranjen v bazi -- priredimo default jezik
            $_GET['language'] = self::$lang['id'];    // to damo samo zato, da se shrani ID default jezika v bazo, namesto -1 (ker pri default jeziku ne podamo nič preko URLja)
            // get('lang_id') mora se vedno ostati null !
        } else {        // zamenjamo jezik
            $file = lang_path(get('lang_id'));
            if (@include($file))
                $_SESSION['langX'] = lang_path(get('lang_id'), 1);
        }

        // prikazemo header
        Header::getInstance()->header();

        if (!isset($_GET['grupa'])) {

            if ($row['show_intro'] == 1) {
                Body::getInstance()->displayIntroduction();
            } else {
                $grupa = save('grupa', Find::getInstance()->findNextGrupa(), 1);

                if ($grupa > 0) {
                    if (isset($_GET['language']))
                        header('Location: ' . SurveyInfo::getSurveyLink(false, false) . '&grupa=' . get('grupa') . '&language=' . $_GET['language'] . '');
                    else
                        header('Location: ' . SurveyInfo::getSurveyLink(false, false) . '&grupa=' . get('grupa') . '');
                } else {
                    Body::getInstance()->displayKonec();
                }
            }
        } else {
            save('grupa', $_GET['grupa']);

            // pogledamo ce preskocimo kaksno stran zaradi branchinga
            while (!Check::getInstance()->checkGrupa() && get('grupa') > 0 && $_GET['disableif'] != 1) {
                save('grupa', Find::getInstance()->findNextGrupa());
            }

            // nismo se poslali kaksno stran z odgovori
            if (count($_POST) == 0) {
                if (get('grupa') > 0)
                    Body::getInstance()->displayAnketa();
                else
                    Body::getInstance()->displayKonec();
            } // poslani so bili odgovori
            else {
                $grupa = Find::getInstance()->findNextGrupa();
                save('grupa', $grupa, 1);

                if ($grupa > 0) {
                    if (isset($_GET['language']))
                        header('Location: ' . SurveyInfo::getSurveyLink(false, false) . '&grupa=' . get('grupa') . '&language=' . $_GET['language'] . '');
                    else
                        header('Location: ' . SurveyInfo::getSurveyLink(false, false) . '&grupa=' . get('grupa') . '');
                } else {
                    Body::getInstance()->displayKonec();
                }
            }
        }

        die();
    }

    /************************************************
     * Tudi ce je zakljucena pustimo prikaz pri nastavljenem glas_end -> preklop arhiva statistike pri glasovanju - drugače izpišemo obvestilo o zaključku
     ************************************************/
    private function anketaEnd($rowa)
    {

        if (get('lang_id') != null) $_lang = '_' . get('lang_id'); else $_lang = '';
        SurveySetting::getInstance()->init(get('anketa'));

        if ($rowa['active'] > 0) {

            // preverimo začetek in konec
            $sqlDates = sisplet_query("SELECT id, starts, expire, (IF(CURDATE() >= starts, true, false)) as boolStarts, (IF(CURDATE() <= expire, true, false)) as boolExpire FROM srv_anketa WHERE id='" . get('anketa') . "'");
            $rowDates = mysqli_fetch_assoc($sqlDates);
            if (!$rowDates['boolStarts']) {

                $srv_survey_non_active_notStarted = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_survey_non_active_notStarted' . $_lang);
                if ($srv_survey_non_active_notStarted == '') $srv_survey_non_active_notStarted = self::$lang['srv_survey_non_active_notStarted'];

                Display::getInstance()->displayNapaka($srv_survey_non_active_notStarted . $rowDates['starts']);
            }
            if (!$rowDates['boolExpire']) {

                $srv_survey_non_active_expired = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_survey_non_active_expired' . $_lang);
                if ($srv_survey_non_active_expired == '') $srv_survey_non_active_expired = self::$lang['srv_survey_non_active_expired'];

                Display::getInstance()->displayNapaka(self::$lang['srv_survey_non_active_expired'] . $rowDates['expire']);
            }
        } elseif ($rowa['active'] == 0) {

            # anketa ni aktivna, preverimo ali je bila sploh aktivirana
            $str = "SELECT count(*) FROM srv_activity WHERE sid = '" . get('anketa') . "'";
            $qry = sisplet_query($str);
            list($count_activity) = mysqli_fetch_row($qry);
            if ($count_activity > 0) {

                $srv_survey_non_active = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_survey_non_active' . $_lang);
                if ($srv_survey_non_active == '') $srv_survey_non_active = self::$lang['srv_survey_non_active'];

                Display::getInstance()->displayNapaka($srv_survey_non_active);
            } else {

                $srv_survey_non_active_notActivated = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_survey_non_active_notActivated' . $_lang);
                if ($srv_survey_non_active_notActivated == '') $srv_survey_non_active_notActivated = self::$lang['srv_survey_non_active_notActivated'];

                Display::getInstance()->displayNapaka($srv_survey_non_active_notActivated);
            }
        } else {

            $srv_survey_deleted = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_survey_deleted' . $_lang);
            if ($srv_survey_deleted == '') $srv_survey_deleted = self::$lang['srv_survey_deleted'];

            Display::getInstance()->displayNapaka($srv_survey_deleted);
        }

        die();
    }


    // EVALVACIJA - Ce je nov respondent preverimo ce se je vrnil (zapis v tabeli eval_data_userCookie) in mu nastavimo ustrezen cookie da ga preusmerimo na ustrezno stran
    private function evlavacijaNovRespondent()
    {
        $student = (isset($_GET['sifstud'])) ? $_GET['sifstud'] : 0;
        $predmet = (isset($_GET['sifpredm'])) ? $_GET['sifpredm'] : -1;        // Ce resuje splosno anketo ni predmeta -> predmet = -1

        if ($student > 0) {
            $sqlE = sisplet_query("SELECT u.cookie AS cookie FROM eval_data_userCookie adu, srv_user u
												WHERE adu.ank_id='" . get('anketa') . "' AND adu.student='" . $student . "' AND adu.predmet='" . $predmet . "'
												AND u.ank_id='" . get('anketa') . "' AND u.id=adu.usr_id");
            if (mysqli_num_rows($sqlE) > 0) {
                $rowE = mysqli_fetch_array($sqlE);
                $cookie = $rowE['cookie'];
                return $cookie;
            }
        }
    }


    /************************************************
     * Če cookie obstaja ga samo preberemo
     ************************************************/
    protected $cookie;

    private function cookieExist($cookie)
    {
        $rowa = SurveyInfo::getInstance()->getSurveyRow();

        $sql = sisplet_query("SELECT id, user_id FROM srv_user WHERE cookie='$cookie' AND deleted='0' LIMIT 1");
        $row = mysqli_fetch_array($sql);

        if ($row['id'] > 0) {

            save('usr_id', $row['id']);


            // cookie se enkrat nastavmo, ce se je kaj spreminjal nastavitve expire-ja
            $this->set_cookie('survey-' . get('anketa'), $cookie, $this->expire);

            // v primeru da je cookie ze bil postavljen, pa da se ni bilo povezave s sisplet userjem, ga 'povezemo'
            if ($rowa['user_from_cms'] >= 1)
                if ($row['user_id'] == 0 && self::$global_user_id > 0)
                    $sql = sisplet_query("UPDATE srv_user SET user_id = '" . self::$global_user_id . "' WHERE cookie = '$cookie'");

            // Preverimo, ce je ze koncal anketo (in se kasneje vrnil), ce lahko se ureja svoje odgovore
            // Dodano v pogoj da se to izvede samo ce nima nastavljene grupe (drugace dela narobe klik nazaj na zakljucku, preklop med bloki...)
            if ($rowa['return_finished'] == 0 && !isset($_GET['grupa']) && !isset($_GET['urejanje']) && !isset($_GET['return'])) {
                $sqls = sisplet_query("SELECT MAX(status) AS status FROM srv_userstatus WHERE usr_id='" . get('usr_id') . "'");
                $rows = mysqli_fetch_array($sqls);
                if ($rows['status'] == 6) {  // Koncal anketo
                    Header::getInstance()->header();
                    Body::getInstance()->displayKonec();
                    die();
                }
            }

            // ce ze ima kuki in ni poslana grupa pomeni, da se je vrnil se enkrat resevat anketo
            if (!isset($_GET['grupa'])) {
                Check::getInstance()->check_cookie_return();
            }

        } else {    // dodatno preverjanje, ce se slucajno kaj sfizi, pa da ni tega cookieja v bazi, ga moramo se enkrat nastavit
            $this->cookie = null;
        }
    }

    /************************************************
     * Cookie ni nastavljen is null
     ************************************************/

    private function cookieIsNull()
    {
        if (get('lang_id') != null) $_lang = '_' . get('lang_id'); else $_lang = '';

        $rowa = SurveyInfo::getInstance()->getSurveyRow();

        //preverjamo limit stevila glasov
        if ((!isset($_GET['preview']) || $_GET['preview'] != 'on') && ($rowa['vote_limit'] == 1 || $rowa['vote_limit'] == 2)) {

            // preverimo ce smo presegli limit
            // Stetje samo ustreznih odgovorov
            if ($rowa['vote_limit'] == 2)
                $sqlVotes = sisplet_query("SELECT COUNT(id) FROM srv_user WHERE ank_id='" . get('anketa') . "' AND (last_status='5' OR last_status='6') AND lurker='0' AND deleted='0'");
            // Stetje vseh odgovorov
            else
                $sqlVotes = sisplet_query("SELECT COUNT(id) FROM srv_user WHERE ank_id='" . get('anketa') . "' AND deleted='0'");

            $rowVotes = mysqli_fetch_assoc($sqlVotes);

            if ($rowa['vote_count'] <= $rowVotes['COUNT(id)']) {
                //$this->display->displayNapaka(self::$lang['srv_survey_non_active_voteLimit'].' ('.$rowa['vote_count'].')');

                $srv_survey_non_active_voteLimit = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_survey_non_active_voteLimit' . $_lang);
                if ($srv_survey_non_active_voteLimit == '') $srv_survey_non_active_voteLimit = self::$lang['srv_survey_non_active_voteLimit'];

                Display::getInstance()->displayNapaka($srv_survey_non_active_voteLimit);
                die();
            }
        }

        // splosna koda za dostop do ankete (brez prepoznave userja) - dodatno preverimo ce je ta funkcionalnost na voljo v paketu
        $userAccess = UserAccess::getInstance(self::$global_user_id);
        $sqlp = sisplet_query("SELECT password FROM srv_password WHERE ank_id='" . get('anketa') . "'");
        if (mysqli_num_rows($sqlp) > 0 && $userAccess->checkUserAccess($what='password')) {

            $ok = false;

            while ($rowp = mysqli_fetch_array($sqlp)) {

                if (isset($_POST['password']) && $rowp['password'] == $_POST['password'] /*|| $_COOKIE['password_' .get('anketa')] == $rowp['password']*/) {
                    setcookie('password_' . get('anketa'), $rowp['password']);
                    $ok = true;
                }
            }

            if (!$ok) {
                Header::getInstance()->header();
                Body::getInstance()->displayIntroduction();
                die();
            }
        }

        // preverimo, ce blokiramo IP (to nardimo, samo ce se ni kukija - drugac bi ga takoj zablokiral (ce pa je kuki se ga pa itak prepoza pa potem shendla))
        if ($rowa['block_ip'] > 0) {
            $sqlip = sisplet_query("SELECT id FROM srv_user WHERE ank_id='" . get('anketa') . "' AND ip='" . Helper::remote_address() . "' AND time_edit >= NOW() - INTERVAL $rowa[block_ip] MINUTE");
            if (mysqli_num_rows($sqlip) > 0) {   // je ze dostopal iz tega IPja
                Header::getInstance()->header();
                Body::getInstance()->displayKonec();
                die();
            }
        }

        if (self::$global_user_id == 0) {
            $row1['id'] = 0;
        } 
        else {
            $sql1 = sisplet_query("SELECT id, cookie FROM srv_user WHERE user_id = '" . self::$global_user_id . "' AND user_id > 0 AND ank_id = '" . get('anketa') . "'");

            if(mysqli_num_rows($sql1) > 0)
                $row1 = mysqli_fetch_array($sql1);
            else
                $row1['id'] = 0;
        }

        // ce je izbrana povezava s sisplet userjem in da smo najdl cookie za trenutnega userja, potem nastavimo ta cookie
        if ($row1['id'] > 0 && $rowa['user_from_cms'] == 1) {

            $rand = $row1['cookie'];

            $this->set_cookie('survey-' . get('anketa'), $rand, $this->expire);

            save('usr_id', $row1['id']);


            // Preverimo, ce je ze koncal anketo (in se kasneje vrnil), ce lahko se ureja svoje odgovore
            if ($rowa['return_finished'] == 0) {
                $sqls = sisplet_query("SELECT MAX(status) AS status FROM srv_userstatus WHERE usr_id='" . get('usr_id') . "'");
                $rows = mysqli_fetch_array($sqls);
                if ($rows['status'] == 6) {  // Koncal anketo
                    Header::getInstance()->header();
                    Body::getInstance()->displayKonec();
                    die();
                }
            }

            // ce ze ima kuki in ni poslana grupa pomeni, da se je vrnil se enkrat resevat anketo
            if (!isset($_GET['grupa'])) {
                Check::getInstance()->check_cookie_return();
            }

            // userji iz baze
        } elseif (
            ($rowa['user_base'] == 1) &&
            #če imamo neindividualizirana vabila - poslana preko vabil, vendar bez kode in zato brez sledenja (uporabnik se pri pošiljanju NE prenese iz srv_invitations_recipients v srv_user)
            $rowa['individual_invitation'] != 0 &&
            (        // Omogoči anketo tudi respodentom, ki niso v bazi
                ($rowa['usercode_skip'] == 0)                                                                            // Ne (vsi grejo sem not, ker morajo nujno vnesti kodo)
                || ($rowa['usercode_skip'] == 1 /*&& (isset($_POST['usercode']) or isset($_GET['code']))*/)                    // Da (sem not grejo samo, ce so vnesli kodo, da jih prepoznamo, sicer grejo na else in dobijo nov cookie)
                || ($rowa['usercode_skip'] == 2 && (!get('userAutor') || (isset($_POST['usercode']) or isset($_GET['code']))))    // Samo avtor (ce ni avtor ankete, gre sem noter in mora vnesti kodo. Avtor ankete pa gre sem notr samo ce je podana koda, sicer gre na else in dobi nov cookie)

                //  Respondenti naj vedno vnesejo kodo: Da
                || ($rowa['usercode_required'] == 1 && $rowa['usercode_skip'] != 1 &&
                    (    // Omogoči anketo tudi respodentom, ki niso v bazi
                        $rowa['usercode_skip'] == 0                                                                                    // Ne (vsi grejo sem not, ker morajo nujno vnesti kodo)
                        || ($rowa['usercode_skip'] == 2 && !get('userAutor') /*&& ( !$userAutor || $userAutor || (isset($_POST['usercode']) or isset($_GET['code'])) )*/)        // Samo avtor (ce ni avtor ankete, gre sem noter in mora vnesti kodo. Avtor ankete pa gre sem notr samo ce je podana koda, sicer gre na else in dobi nov cookie)
                    )                                // ^ tale pogoj zgoraj se ni cist ok, ker mora tudi avtor vnesti kodo - AVTORJU NI TREBA VNEST KODE CE JE TO VKLOPLJENO - dodan && !get('userAutor')
                )
            )
        ) {
            if (isset($_POST['usercode']) or isset($_GET['code'])) {

                if (isset($_POST['usercode']))
                    $usercode = strtolower($_POST['usercode']);
                elseif (isset($_GET['code']))
                    $usercode = strtolower($_GET['code']);
                else
                    die();

                $sql2 = sisplet_query("SELECT id, recnum, cookie FROM srv_user WHERE TRIM(pass) = '$usercode' AND ank_id='".get('anketa')."' AND deleted='0'");
                if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);
                $row2 = mysqli_fetch_array($sql2);

                // Pravilna koda
                if (mysqli_num_rows($sql2) > 0) {    
                    
                    // Ce imamo vklopljen modul za volitve preskocimo kar nekaj korakov (anonimizacija)
                    if(!SurveyInfo::checkSurveyModule('voting')){

                        // Dodatno preverimo ce koda se ni potekla
                        $sqlC = sisplet_query("SELECT * FROM srv_invitations_recipients WHERE ank_id='".get('anketa')."' AND TRIM(password)='".$usercode."' AND DATE(NOW())>DATE(date_expired) AND date_expired!='0000-00-00 00:00:00'");
                        if(mysqli_num_rows($sqlC) > 0){
                            Display::getInstance()->displayNapaka(self::$lang['srv_expiredcode']);
                            die();			
                        }

                        # nastavimo še da je uporabnik odgovoril na anketo za nov način e-mail vabil
                        sisplet_query("UPDATE srv_invitations_recipients SET responded = '1', date_responded = NOW() WHERE ank_id='" . get('anketa') . "' AND TRIM(password) ='$usercode' AND responded = '0'");

                        sisplet_query("COMMIT");
                    }

                    $rand = $row2['cookie'];

                    $this->set_cookie('survey-' . get('anketa'), $rand, $this->expire);

                    save('usr_id', $row2['id']);


                    // Preverimo ce imamo vklopljeno da uporabnik nadaljuje kjer je ostal - potem ga preusmerimo na pravo stran
                    Check::getInstance()->check_cookie_return();

                    if ($row2['recnum'] == 0) {

                        if (isset($_POST['referer']))
                            $referer = $_POST['referer'];
                        elseif (isset($_SERVER['HTTP_REFERER']))
                            $referer = $_SERVER['HTTP_REFERER'];
                        else
                            $referer = 0;

                        SurveySetting::getInstance()->Init(get('anketa'));
                        $ip = SurveySetting::getInstance()->getSurveyMiscSetting('survey_ip');
                        $browser = SurveySetting::getInstance()->getSurveyMiscSetting('survey_browser');
                        $referal = SurveySetting::getInstance()->getSurveyMiscSetting('survey_referal');
                        $date = SurveySetting::getInstance()->getSurveyMiscSetting('survey_date');

                        if ($ip == 0) $_ip = Helper::remote_address(); else $ip = "";
                        if ($date == 0) $_time_insert = "NOW()"; else $_time_insert = "''";
                        if ($referal == 0) $_referer = $referer; else $_referer = '';
                        if ($browser == 0) $_useragent = $_SERVER['HTTP_USER_AGENT']; else $_useragent = '';

                        // Ce dovolimo useragent in ce je instaliran browscap
                        $_browser = '';
                        $_device = 0;
                        $_os = '';
                        if ($_useragent != '' && get_cfg_var('browscap')) {

                            $browser_detect = get_browser($_useragent, true);
                            $detect = New Mobile_Detect();
                            $detect->setUserAgent($_useragent);

                            // Detect browserja
                            if ($browser_detect['browser'] == 'Default Browser')
                                $_browser = self::$lang['srv_para_graph_other'];
                            else
                                $_browser = $browser_detect['browser'] . ' ' . $browser_detect['version'];

                            // Detect naprave (pc, mobi, tablet, robot)
                            if ($detect->isMobile()) {
                                if ($detect->isTablet())
                                    $_device = 2;
                                else
                                    $_device = 1;
                            } elseif ($browser_detect['crawler'] == 1)
                                $_device = 3;
                            else
                                $_device = 0;

                            // Detect operacijskega sistema
                            if ($browser_detect['platform'] == 'unknown')
                                $_os = self::$lang['srv_para_graph_other'];
                            else
                                $_os = $browser_detect['platform'];
                        }

                        $recnum = 0;

                        if (isset($_GET['language'])) save('language', (int)$_GET['language']); else save('language', self::$lang['id']);

                        $s = sisplet_query("UPDATE srv_user SET recnum = MAX_RECNUM('" . get('anketa') . "'), time_insert=" . $_time_insert . ", language='" . get('language') . "', ip='" . $_ip . "', useragent='" . $_useragent . "', device='" . $_device . "', browser='" . $_browser . "', os='" . $_os . "' WHERE id = '" . get('usr_id') . "'");
                        if (!$s) {
                            echo 'err3432' . mysqli_error($GLOBALS['connect_db']);
                            die();
                        }
                    }

                    // Preverimo, ce je ze koncal anketo (in se kasneje vrnil), ce lahko se ureja svoje odgovore
                    if ($rowa['return_finished'] == 0) {
                        $sqls = sisplet_query("SELECT MAX(status) AS status FROM srv_userstatus WHERE usr_id='" . get('usr_id') . "'");
                        $rows = mysqli_fetch_array($sqls);
                        
                        if ($rows['status'] == 6) {  // Koncal anketo
                            Header::getInstance()->header();
                            Body::getInstance()->displayKonec();

                            die();
                        }
                    }


                    // ce ze ima kuki in ni poslana grupa pomeni, da se je vrnil se enkrat resevat anketo
                    if (!isset($_GET['grupa'])) {
                        Check::getInstance()->check_cookie_return();
                    }

                } 
                else {        // koda ne obstaja

                    //#TODO Tukaj preverimo userja iz novih e-mail vabil

                    if ($rowa['usercode_skip'] != 1 && !($_GET['preview'] == 'on' && isset($_GET['disablecode']) && $_GET['disablecode'] == 1)) {

                        // Pri Ne in Samo avtor prikazemo obvestilo, pri Da spustimo naprej in kreiramo nov cookie
                        SurveySetting::getInstance()->init(get('anketa'));
                        $srv_wrongcode = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_wrongcode' . $_lang);
                        if ($srv_wrongcode == '') $srv_wrongcode = self::$lang['srv_wrongcode'];

                        Display::getInstance()->displayNapaka($srv_wrongcode);
                        die();
                    }
                }

            } else {

                # če imamo formo
                if ($rowa['survey_type'] == 1) {
                    # TODO.. Mitja, tu je treba vse pravilno pohandlat.. men se niti sanja ne kak in kaj
                    # problem pri formi je da ko enkrat pošlješ email vabilo, potem vedno prikazuje header
                    // TODO: kolk casa je ze tole tukaj? :) -mitja
                    # TODO: ziher več kot leto in pol. :) tak da ni neskončno pomembno
                } 
                else {

                    // baza respondentov - ce ni poslana koda
                    Language::getInstance()->multilang_init();
                    Header::getInstance()->header();
                    Body::getInstance()->displayIntroduction();

                    die();
                }
            }

        }

        // drugace gremo kreirat nov cookie
        if (get('usr_id') == null) {

            // izberemo random hash, ki se ni v bazi
            do {
                $rand = md5(mt_rand(1, mt_getrandmax()) . '@' . Helper::remote_address());
                $sql = sisplet_query("SELECT id FROM srv_user WHERE cookie = '$rand'");
            } while (mysqli_num_rows($sql) > 0);

            $this->set_cookie('survey-' . get('anketa'), $rand, $this->expire);

            // ce je izbrana povezava s sisplet userjem, ga povezemo
            if ($rowa['user_from_cms'] >= 1) {
                $sqlu = Model::db_select_user(self::$global_user_id);
                $rowu = mysqli_fetch_array($sqlu);
            } else
                $rowu['id'] = 0;

            if (isset($_GET['preview']) && $_GET['preview'] == 'on') {
                $preview = 1;
            } else {
                $preview = 0;
            }
            # če smo v prezentaciji (slideshow) in ne beležimo vnosov jih označimo kot preview = 1
            if (isset($rowa['slideshow']) && $rowa['slideshow'] == 1) {
                $ss = new SurveySlideshow(get('anketa'));
                $ss_setings = $ss->getSettings();
                # če beležimo vnose: save_entries == 1
                if ($ss_setings['save_entries'] == 0) {
                    $preview = 1;
                }
            }

            $testdata = 0;
            if (isset($_GET['testdata']) && $_GET['testdata'] == 'on') {
                $preview = 0;
                $testdata = 1;
            }


            if (isset($_POST['referer']))
                $referer = $_POST['referer'];
            elseif (isset($_SERVER['HTTP_REFERER']))
                $referer = $_SERVER['HTTP_REFERER'];
            else
                $referer = 0;

            SurveySetting::getInstance()->Init(get('anketa'));
            $ip = SurveySetting::getInstance()->getSurveyMiscSetting('survey_ip');
            $browser = SurveySetting::getInstance()->getSurveyMiscSetting('survey_browser');
            $referal = SurveySetting::getInstance()->getSurveyMiscSetting('survey_referal');
            $date = SurveySetting::getInstance()->getSurveyMiscSetting('survey_date');

            if ($ip == 0) $_ip = Helper::remote_address(); else $ip = "";
            if ($date == 0) $_time_insert = "NOW()"; else $_time_insert = "''";
            if ($referal == 0) $_referer = $referer; else $_referer = '';
            if ($browser == 0) $_useragent = $_SERVER['HTTP_USER_AGENT']; else $_useragent = '';

            // Ce dovolimo useragent in ce je instaliran browscap
            $_browser = '';
            $_device = 0;
            $_os = '';
            if ($_useragent != '' && get_cfg_var('browscap')) {

                $browser_detect = get_browser($_useragent, true);
                $detect = New Mobile_Detect();
                $detect->setUserAgent($_useragent);

                // Detect browserja
                if ($browser_detect['browser'] == 'Default Browser')
                    $_browser = self::$lang['srv_para_graph_other'];
                else
                    $_browser = $browser_detect['browser'] . ' ' . $browser_detect['version'];

                // Detect naprave (pc, mobi, tablet, robot)
                if ($detect->isMobile()) {
                    if ($detect->isTablet())
                        $_device = 2;
                    else
                        $_device = 1;
                } elseif ($browser_detect['crawler'] == 1)
                    $_device = 3;
                else
                    $_device = 0;

                // Detect operacijskega sistema
                if ($browser_detect['platform'] == 'unknown')
                    $_os = self::$lang['srv_para_graph_other'];
                else
                    $_os = $browser_detect['platform'];
            }

            if (isset($_GET['language'])) save('language', (int)$_GET['language']); else save('language', self::$lang['id']);

            $recnum = 0;

            $sql = sisplet_query("INSERT INTO srv_user (id, ank_id, preview, testdata, cookie, user_id, ip, time_insert, recnum, referer, useragent, device, browser, os, language) VALUES (0, '" . get('anketa') . "', '$preview', '$testdata', '$rand', '$rowu[id]', '" . $_ip . "', " . $_time_insert . ", MAX_RECNUM('" . get('anketa') . "'), '" . $_referer . "', '" . $_useragent . "', '" . $_device . "', '" . $_browser . "', '" . $_os . "', '" . get('language') . "')");
            if (!$sql) {
                echo 'err3431' . mysqli_error($GLOBALS['connect_db']);
                die();
            }
            save('usr_id', mysqli_insert_id($GLOBALS['connect_db']), 1);


            //sisplet_query("COMMIT");
            # če je testni vnos in če že imamo skreirano datoteko s podatki v kateri še ni označeno da imamo testem je potrebno generirati na novo
            if ((int)$testdata > 0) {
                $head_file_name = self::$site_path . 'admin/survey/SurveyData/export_header_' . get('anketa') . '.dat';
                if (file_exists($head_file_name)) {
                    $header = unserialize(file_get_contents($head_file_name));
                    if ((int)$header['_settings']['hasTestData'] == 0) {
                        unlink($head_file_name);
                    }
                }
            }
            
            //potrebuje se za modul MAZA - za povezavo respondenta med tebelama maza_app_users in srv_user
            if(SurveyInfo::checkSurveyModule('maza')){   
                //error_log(json_encode($_GET));
                $maza = new MAZA(get('anketa'));
                //check if id and identifier match
                if($_GET['maza_user_id'] && ($maza->maza_validate_user($_GET['maza_user_id'], $_GET['maza_identifier']))){
                    //update last_active of user
                    $maza->maza_update_user_active($_GET['maza_user_id']);
                    //insert pair of users id's in DB table
                    $maza->maza_save_srv_user($_GET['maza_user_id'], get('usr_id'), $_GET['maza_srv_version'], $_GET['maza_tgeofence_id'], $_GET['maza_tactivity_id'], $_GET['maza_mode']);
                }
            }

            // EVALVACIJA - Ce je nov respondent se to zapise v posebno tabelo, da ga prepoznamo naslednjic brez cookija (da lahko nadaljuje kjer je ostal)
            if (Common::checkModule('evalvacija') == '1') {
                $student = (isset($_GET['sifstud'])) ? $_GET['sifstud'] : 0;
                $predmet = (isset($_GET['sifpredm'])) ? $_GET['sifpredm'] : -1;        // Ce resuje splosno anketo ni predmeta -> predmet = -1

                if ($student > 0)
                    sisplet_query("INSERT INTO eval_data_userCookie (ank_id, usr_id, student, predmet) VALUES ('" . get('anketa') . "', '" . get('usr_id') . "', '" . $student . "', '" . $predmet . "')");
            }

            // V kolikor gre za hierarhijo in je respondent učitelj, potem usr_id shranimo, da lahko obnovimo anketo
            if (Common::checkModule('hierarhija') == '1' && SurveyInfo::checkSurveyModule('hierarhija') == 2) {
                $url = base64_decode(urldecode($this->get->enc));
                $polje = sisplet_query("SELECT koda, vloga FROM srv_hierarhija_koda WHERE url='" . $url . "'", "obj");

                if ($polje->vloga == 'ucitelj')
                    sisplet_query("UPDATE srv_hierarhija_koda SET srv_user_id='" . get('usr_id') . "' WHERE url='" . $url . "'");

                // V kolikor gre za super šifro potem shranimo v bazo, uporabnika in katero anketo je reševal.
                if (!empty($this->get->supersifra)) {
                    $kode = sisplet_query("SELECT kode FROM srv_hierarhija_supersifra WHERE koda='".$this->get->supersifra."'", "obj");
                    $kode = unserialize($kode->kode);

                    sisplet_query("INSERT INTO 
                                     srv_hierarhija_supersifra_resevanje 
                                     (user_id, supersifra, koda, status) 
                                  VALUES 
                                    ('".get('usr_id')."', '".$this->get->supersifra."', '".$kode[$this->get->resujem]."', 1)
                                  ");

                }

            }
        }
    }

    /**
     * nastavi cookie, oz. v primeru ankete brez cookieja, nastavi spremenljivko za url
     */
    protected $anketa, $rand, $expire;

    private function set_cookie($anketa, $rand, $expire)
    {

        $cookie_ok = isset($_COOKIE['cookie_ok']) ? $_COOKIE['cookie_ok'] : '';

        // ce je nastavljeno na brez piskotka ali se ni potrdil shranjevanja piskotkov
        if (get('cookie_expire') == -1 or $cookie_ok != 1) {                        // cookie prenasamo preko URLja
            save('cookie_url', '&' . $anketa . '=' . $rand);            // ta string se bo dodal vsem URLjem na konec
            // &amp; dodamo naknadno samo tam, kjer se pise v html (v header() more bit samo &)
        } else {
            setcookie($anketa, $rand, $expire);                    // ce je cookie, ga obicajno nastavimo
        }
    }

    /**
     * @desc nastavi status za trenutnega userja
     */
    private function set_userstatus($status)
    {
        $sql_userbase = sisplet_query("SELECT MAX(tip) AS tip FROM srv_userbase WHERE usr_id = '" . get('usr_id') . "'");
        if (!$sql_userbase) echo mysqli_error($GLOBALS['connect_db']);  
        $row_userbase = mysqli_fetch_array($sql_userbase);
        
        if ($row_userbase['tip'] > 0) {
            $tip = $row_userbase['tip'];
        } 
        else {
            $tip = 0;
        }

        $sqlu = sisplet_query("SELECT MAX(status) AS status FROM srv_userstatus WHERE usr_id = '" . get('usr_id') . "'");
        if (!$sqlu) echo mysqli_error($GLOBALS['connect_db']);
        $rowu = mysqli_fetch_array($sqlu);

        $sqlu = sisplet_query("SELECT inv_res_id FROM srv_user WHERE id = '" . get('usr_id') . "' AND inv_res_id IS NOT NULL");
        $inv_res_id = null;
        if (mysqli_num_rows($sqlu) > 0) {
            # userj je dodan preko novih vabil zato updejtamo status še tam
            $row_inv_res_id = mysqli_fetch_assoc($sqlu);
            $inv_res_id = (int)$row_inv_res_id['inv_res_id'];
        }

        // spremenimo tip
        if ($status > $rowu['status'] && is_numeric(get('usr_id'))) {

            $s = sisplet_query("REPLACE INTO srv_userstatus (usr_id, tip, status, datetime) VALUES ('" . get('usr_id') . "', '$tip', '$status', NOW())");
            if (!$s) echo mysqli_error($GLOBALS['connect_db']);

            SurveySetting::getInstance()->Init(get('anketa'));
            $date = SurveySetting::getInstance()->getSurveyMiscSetting('survey_date');
            if ($date == 0) $_time_insert = "NOW()"; else $_time_insert = "''";


            if (isset($_GET['language'])) save('language', (int)$_GET['language']); else save('language', self::$lang['id']);

            $s = sisplet_query("UPDATE srv_user SET last_status = '$status', time_edit = " . $_time_insert . ", language='" . get('language') . "' WHERE id = '" . get('usr_id') . "'");
            if (!$s) {
                echo mysqli_error($GLOBALS['connect_db']);
                die();
            }
            # updejtamo še status pri respondentih.
            if ((int)$inv_res_id > 0) {
                # userj je dodan preko novih vabil zato updejtamo status še tam
                $sqlString = "UPDATE srv_invitations_recipients SET last_status='$status' WHERE ank_id='" . get('anketa') . "' AND id ='$inv_res_id' ";
                $u = sisplet_query($sqlString);
            }

            // updatamo samo datum - tip se ni spremenil
        } else {

            SurveySetting::getInstance()->Init(get('anketa'));
            $date = SurveySetting::getInstance()->getSurveyMiscSetting('survey_date');
            if ($date == 0) $_time_insert = "NOW()"; else $_time_insert = "''";


            if (isset($_GET['language'])) save('language', (int)$_GET['language']); else save('language', self::$lang['id']);

            # osvežimo datum spremembe
            sisplet_query("UPDATE srv_user SET time_edit = " . $_time_insert . ", language='" . get('language') . "' WHERE id='" . get('usr_id') . "'");

        }

        // Ce ne belezimo parapodatka za cas responsa, anonimno zabelezimo cas zadnjega responsa
        if(SurveySetting::getInstance()->getSurveyMiscSetting('survey_date') == 1) {
            sisplet_query("UPDATE srv_anketa SET last_response_time=NOW() WHERE id='".get('anketa')."'");
        }

        # dodamo še tracking arhivov vabil
        if (get('user_inv_archive') > 0 && $inv_res_id > 0) {
            
            # ignoriramo podvojene kluče
            $s = sisplet_query("INSERT IGNORE INTO srv_invitations_tracking 
                                    (inv_arch_id, time_insert, res_id, status) 
                                    VALUES 
                                    ('" . (int)get('user_inv_archive') . "',NOW(),'$inv_res_id','$status')
                            ");
            if (!$s) echo mysqli_error($GLOBALS['connect_db']);
        } 
        else {
        }

        # potrebno bo osvežit seznam anket
        Model::setUpdateSurveyList();
    }


}