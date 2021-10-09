<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 08.02.2016
 *****************************************/

namespace App\Controllers;

use App\Controllers\CheckController as Check;
use App\Controllers\DisplayController as Display;
use App\Controllers\FindController as Find;
use App\Controllers\HeaderController as Header;
use App\Controllers\HelperController as Helper;
use App\Controllers\JsController as Js;
use App\Controllers\StatisticController as Statistic;
use App\Controllers\Vprasanja\VprasanjaController as Vprasanja;
use App\Models\Model;
use App\Models\SaveSurvey;
use Hierarhija\Hierarhija;
use SurveyAdvancedParadataLog;
use SurveyInfo;
use SurveySetting;
use SurveyAdminSettings;
use Common;
use TrackingClass;
use SurveyPanel;
use SurveyQuiz;
use SurveyGorenje;
use SurveyNIJZ;
use GDPR;
use UserAccess;


class BodyController extends Controller
{
    public function __construct()
    {
        parent::getGlobalVariables();
    }

    /************************************************
     * Get instance
     ************************************************/
    private static $_instance;

    public static function getInstance()
    {
        if (self::$_instance)
            return self::$_instance;

        return new BodyController();
    }


    /**
     * @desc prikaze uvodni nagovor
     */
    public function displayIntroduction()
    {
        Header::getInstance()->displaySistemske();

        $row = SurveyInfo::getInstance()->getSurveyRow();
        if (!get('printPreview')) {
            SaveSurvey::saveSistemske();
        }

        // datapiping
        $row['introduction'] = Helper::dataPiping($row['introduction']);

        SurveySetting::getInstance()->Init(get('anketa'));
        $hide_mobile_img = SurveySetting::getInstance()->getSurveyMiscSetting('hide_mobile_img');
        $class = ($hide_mobile_img == '1') ? 'hide_mobile_img' : '';
        if (isset($_GET['mobile']) && $_GET['mobile'] == 1)
            $class .= ' mobile_preview';
        elseif (isset($_GET['mobile']) && $_GET['mobile'] == 2)
            $class .= ' tablet_preview';


		// crn div za ozadje popupov
		echo '<div id="fade"></div>';
		echo '<div id="popup"></div>';


        echo '<div class="outercontainer_holder ' . $class . ' intro">';
        echo '<div class="outercontainer_holder_top"></div>';

        echo '<div id="outercontainer" class="' . $class . ' intro">';
		echo '<div class="outercontainer_header"></div>';
        
        echo '<div id="container">' . "\n";

        Display::getInstance()->logo();

        Display::getInstance()->progress_bar();

        if (!get('printPreview')) {
            echo '<h1 ' . (SurveyInfo::getInstance()->checkSurveyModule('uporabnost') ? ' class="evalvacija"' : '') . '>' . Helper::getInstance()->displayAkronim() . '</h1>' . "\n";
        }

        // opozorilo ce urejamo ze reseno anketo
        if (get('quick_view') == true) {
            echo '<div id="edit_warning">';
            echo self::$lang['srv_quick_view_text'];
            echo '</div>';
        }
        // opozorilo ce urejamo ze reseno anketo
        if (isset($_GET['urejanje'])) {
            echo '<div id="edit_warning">';
            echo self::$lang['srv_edit_text'];
            echo '</div>';

			// Preverimo ce gre za prvo popravljanje podatkov in avtomatsko ustvarimo arhiv podatkov ce je potrebno
			ob_flush();	// ZAKAJ JE TUKAJ TA FLUSH? KER POTEM NE DELA NAKNADNO UREJANJE CE IMA ANKETA LOOPE - Ce ni tega flusha, prvi klik na urejanje odpre poruseno stran (naknadni kliki so pa potem ok)
			$sas = new SurveyAdminSettings();
			$sas->checkFirstDataChange();

			// Updatamo tracking (ker gre za editiranje odgovorov)
            TrackingClass::update(get('anketa'), 4);
        }

        echo '<form method="post" action="' . SurveyInfo::getSurveyLink(false, false) . '&amp;grupa=0' . Header::getSurveyParams(true) . str_replace('&', '&amp;', get('cookie_url')) . '">' . "\n";
        echo '<div class="grupa">' . "\n";
        echo '  <input type="hidden" name="dummy" value="foo">' . "\n";
        echo '  <input type="hidden" name="referer" value="' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') . '">' . "\n";

        $this->displayIntroductionContent();


        if ($row['user_from_cms'] == 1 && $row['user_from_cms_email'] == 1) {

            $sql1 = sisplet_query("SELECT user_id FROM srv_user WHERE id = '" . get('usr_id') . "'");
            $row1 = mysqli_fetch_array($sql1);

            $sqlu = Model::db_select_user($row1['user_id']);
            $rowu = mysqli_fetch_array($sqlu);
            if (mysqli_num_rows($sqlu) > 0) {
                echo '  <div class="spremenljivka">' . "\n";
                echo self::$lang['srv_recognized'] . ' <strong>' . $rowu['name'] . ' ' . $rowu['surname'] . '</strong>';
                if ($row['user_from_cms_email'] == 1)
                    echo ' (' . $rowu['email'] . ')';
                echo '  </div>' . "\n";
            }
        }

        // koda za izponjevanje ankete (splosna - brez prepoznave userjev) - dodatno preverimo ce je ta funkcionalnost na voljo v paketu
        $userAccess = UserAccess::getInstance(self::$global_user_id);
        $sqlp = sisplet_query("SELECT COUNT(*) AS count FROM srv_password WHERE ank_id='" . get('anketa') . "'");
        $rowp = mysqli_fetch_array($sqlp);
        if ($rowp['count'] > 0 && $userAccess->checkUserAccess($what='password')) {
            echo '  <div class="spremenljivka">' . "\n";
            echo '    <p>' . self::$lang['insert_password'] . ': <input type="text" name="password" value="' . (isset($_COOKIE['password_' . get('anketa')]) ? $_COOKIE['password_' . get('anketa')] : '') . '"></p>';
            echo '  </div>' . "\n";
        }

        if ($row['user_base'] == 1
            #če imamo neindividualizirana vabila - poslana preko vabil, vendar bez kode in zato brez sledenja (uporabnik se pri pošiljanju NE prenese iz srv_invitations_recipients v srv_user)
            && $row['individual_invitation'] != 0
        ) {

            if (isset($_GET['code']))
                $usercode = $_GET['code'];
            else
                $usercode = '';

            if ($row['usercode_required'] == 1)
                $usercode == '';

            // �e ni nujno da vnesemo kodo usercode_skip = 1 ali 2
            $canNotSkip = true;
            if ($row['usercode_skip'] > 0) {
                if ($row['usercode_skip'] == 1) { // lahko presko�imo kodo
                    $canNotSkip = false;
                } elseif ($row['usercode_skip'] == 2) { // preverimo �e je uporabnik avtor ankete
                    $sqlUserAutor = sisplet_query("SELECT ank_id, uid FROM srv_dostop WHERE ank_id='" . get('anketa') . "' AND uid='" . self::$global_user_id . "'");
                    if (mysqli_num_rows($sqlUserAutor) > 0)
                        $canNotSkip = false;
                }
            }

            // Ce smo v preview ali testnem vnosu in ce imamo vklopljeno da ne preverjamo kode
            if (isset($_GET['preview']) && $_GET['preview'] == 'on' && isset($_GET['disablecode']) && $_GET['disablecode'] == 1) {
                $canNotSkip = false;
            }

            if ($usercode == '' && $canNotSkip || ($row['usercode_required'] == 1 /*&& $row['usercode_skip'] != 1*/)) {

                $usercode_text = ($row['usercode_text'] != "") ? $row['usercode_text'] : self::$lang['srv_basecode'];
                echo '  <div class="spremenljivka">' . "\n";
                echo '    <p>' . $usercode_text . ': <input type="text" name="usercode" value="' . $usercode . '"></p>';
                echo '  </div>' . "\n";
            } else {
                echo '  <input type="hidden" name="usercode" value="' . $usercode . '">';
            }
        }

        SurveySetting::getInstance()->Init(get('anketa'));

        // izbira jezika za respondenta
        if (SurveySetting::getInstance()->getSurveyMiscSetting('resp_change_lang') == 1 && $row['multilang'] == 1 && !get('printPreview')) {

            $lang_resp = $row['lang_resp'];
            $lang_array = array();

            $current = self::$lang['id'];

            $file = lang_path($lang_resp);
            include($file);
            $lang_array[$lang['id']] = $lang['language'];

            $file = lang_path($current);
            include($file);

            $sqll = sisplet_query("SELECT lang_id, language FROM srv_language WHERE ank_id='" . get('anketa') . "' ORDER BY language");
            while ($rowl = mysqli_fetch_array($sqll)) {
                $lang_array[$rowl['lang_id']] = $rowl['language'];
            }

            echo '  <div class="spremenljivka lang_pick">' . "\n";

            // Izbira jezika z dropdown menijem
            if (SurveySetting::getInstance()->getSurveyMiscSetting('resp_change_lang_type') == 1) {
                echo '    <h3>' . self::$lang['lang'] . ': ';
                echo '    <select 
                            name="language" 
                            id="language" 
                            onchange="window.location.href = 
                                \'' . SurveyInfo::getSurveyLink() . '?language=\'+document.getElementById(\'language\').value+\'' . 
                                '&survey-'.get('anketa').'=' . get('cookie_url') . '\'+\'' .
                                (isset($_GET['skupina']) ? '&skupina=' . $_GET['skupina'] : '') . '\'+\'' . 
                                (isset($_GET['preview']) ? '&preview=' . $_GET['preview'] : '') . '\'+\'' . 
								(isset($_GET['testdata']) ? '&testdata=' . $_GET['testdata'] : '') . '\'+\'' . 
                                (isset($_GET['code']) ? '&code=' . $_GET['code'] : '') . '\'+\'' . 
                                (isset($_GET['params']) ? '&params=' . $_GET['params'] : '') . '\'+\'' . 
                                (isset($_GET['usr_id']) ? '&usr_id=' . $_GET['usr_id'] : '') . '\'+\'' . 
                                (isset($_GET['status']) ? '&status=' . $_GET['status'] : '') . '\'+\'' . 
                                (isset($_GET['recnum']) ? '&recnum=' . $_GET['recnum'] : '') . '\'+\'' . 
                                (isset($_GET['enc']) ? '&enc=' . urlencode($_GET['enc']) : '') . '\'; 
                            return false;">';
               
                foreach ($lang_array AS $key => $val) {
                    echo '<option value="' . $key . '"' . ($key == $current ? ' selected' : '') . '>' . $val . '</option>';
                }
                
                echo '    </select>';
                echo '</h3>' . "\n";
            } 
            // Izbira jezika z radio gumbi (default)
            else {
                echo '<h3>' . self::$lang['lang'] . ': </h3>';
                echo '<span style="font-size:13px;">';
                
                foreach ($lang_array AS $key => $val) {
                    echo '<label for="language_' . $key . '">';
					echo '<input type="radio" name="language" id="language_' . $key . '" 
							value="' . $key . '"' . ($key == $current ? ' 
							checked="checked"' : '') . ' 
							onchange="window.location.href = 
                                \'' . SurveyInfo::getSurveyLink() . '?language=\'+document.getElementById(\'language_' . $key . '\').value+\'' . 
                                '&survey-'.get('anketa').'=' . get('cookie_url') . '\'+\'' .
								(isset($_GET['skupina']) ? '&skupina=' . $_GET['skupina'] : '') . '\'+\'' . 
								(isset($_GET['preview']) ? '&preview=' . $_GET['preview'] : '') . '\'+\'' . 
								(isset($_GET['testdata']) ? '&testdata=' . $_GET['testdata'] : '') . '\'+\'' . 
								(isset($_GET['code']) ? '&code=' . $_GET['code'] : '') . '\'+\'' . 
                                (isset($_GET['params']) ? '&params=' . $_GET['params'] : '') . '\'+\'' . 
                                (isset($_GET['usr_id']) ? '&usr_id=' . $_GET['usr_id'] : '') . '\'+\'' . 
                                (isset($_GET['status']) ? '&status=' . $_GET['status'] : '') . '\'+\'' . 
                                (isset($_GET['recnum']) ? '&recnum=' . $_GET['recnum'] : '') . '\'+\'' . 
								(isset($_GET['enc']) ? '&enc=' . urlencode($_GET['enc']) : '') . '\'; 
							return false;">';

					// Font awesome checkbox
                    echo '<span class="enka-checkbox-radio" '.((Helper::getCustomCheckbox() != 0) ? 'style="font-size:' . Helper::getCustomCheckbox() . 'px;"' : '').'></span>';

					echo $val;

					echo '</label><br />';
                }
                echo '</span>';
            }


            echo '  </div>' . "\n";

        }

        echo '</div>' . "\n";  // - grupa

        SurveySetting::getInstance()->Init(get('anketa'));
        
        if (get('lang_id') != null) 
            $_lang = '_' . get('lang_id'); 
        else 
            $_lang = '';

        $srv_nextpage = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_nextpage_uvod' . $_lang);  
        $label = $srv_nextpage != '' ? $srv_nextpage : self::$lang['srv_nextpage_uvod'];

        if (!get('printPreview')) {
			
			// JS na submitu prve strani - lahko da preverjamo privacy ali sprejetje cookija
			$survey_privacy = SurveySetting::getInstance()->getSurveyMiscSetting('survey_privacy');
			if(($row['cookie_continue'] == 0 && $row['cookie'] != -1) || $survey_privacy == 2){
				$js_submit = ' onclick="';				
                
                if($survey_privacy == 2)
					$js_submit .= 'privacy_check();';
                
                if($row['cookie_continue'] == 0 && $row['cookie'] != -1)
					$js_submit .= 'cookie_check();';				
                
                $js_submit .= 'return false;"';
			}
			else{
				$js_submit = '';
			}
			
            echo '  <div class="buttons"><input class="next" type="submit" value="' . $label . '" '.$js_submit.'>' . "\n";

            if (isset($_GET['popup']) && $_GET['popup'] == '1'){
                echo '<input class="next" type="submit" value="' . self::$lang['srv_zapri'] . '" onclick="$(\'#popup_div, #popup_iframe\', window.parent.document).hide(); return false;">' . "\n";
				// Varianta da zapiranje iframa deluje cross domain
				//echo '<input class="next" type="button" value="' . self::$lang['srv_zapri'] . '" onclick="parent.window.postMessage(\'closePopup\', \'http://HOST_DOMENA_TODO\');">' . "\n"
			}

            echo '</div>';
        }

        echo '</form>' . "\n";

        echo '</div>' . "\n"; // - container

        if (!get('printPreview')) {
            $this->displayFooterNote();
        }

		// TAWK chat, ce je vklopljen
		if(SurveyInfo::getInstance()->checkSurveyModule('chat')){
			Display::getInstance()->displayChatTAWK();
		}

        echo '</div>' . "\n";   // - outercontainer

        echo '<div class="outercontainer_holder_bottom"></div>';
        echo '</div>';  // -outercontainer_holder

        Js::js_tracking();
    }

    public function displayIntroductionContent()
    {
        $row = SurveyInfo::getInstance()->getSurveyRow();

		
		// Ce prikazemo dodaten preduvod za gdpr ankete
		if(GDPR::isGDPRSurveyTemplate(get('anketa'))){			
			self::displayIntroductionGDPR();
		}
		
		
        echo '  <div class="spremenljivka">' . "\n";

        if ($row['introduction'] == '')
            $intro = self::$lang['srv_intro'];
        else
            $intro = Helper::dataPiping($row['introduction']);

        if (get('lang_id') != null) {
            $sql1 = sisplet_query("SELECT naslov FROM srv_language_spremenljivka WHERE ank_id='" . get('anketa') . "' AND spr_id='-1' AND lang_id='" . get('lang_id') . "'");
            $row1 = mysqli_fetch_array($sql1);
            if ($row1['naslov'] != '') $intro = $row1['naslov'];
        }

        echo '<div class="naslov">';

        // ta p je mogoce prevec, ker ga verjetno vedno doda editor?
        echo '<p>' . $intro . '</p>';

        SurveySetting::getInstance()->Init(get('anketa'));
        $privacy = SurveySetting::getInstance()->getSurveyMiscSetting('survey_privacy');

        if ($privacy == 1) {
            echo '<p><br /></p><p style="font-weight:400;">' . self::$lang['srv_privacy_text_1'] . '</p>';

        } elseif ($privacy == 2) {
            echo '<p><br /></p>';
			
			echo '<p style="font-weight:400;"><label for="privacy_box">';
			echo '<input type="checkbox" name="privacy_box" id="privacy_box">';
			// Font awesome checkbox
			echo '<span class="enka-checkbox-radio"></span>';
			echo self::$lang['srv_privacy_text_2'];
			echo '</label></p>';
        }

		// Ce imamo staticen uvod in preverjanje s captcho
		if($row['intro_static'] == 2){
			global $recaptcha_sitekey;

			$captcha_error = (isset($_GET['captcha_error']) && $_GET['captcha_error'] == 1) ? true : false;

			echo '<br />';

			echo '<p>';
			echo '<div class="g-recaptcha" data-sitekey="' .$recaptcha_sitekey .'"></div>';
			echo '</p>';

			if($captcha_error){
				//echo '<p class="red italic">Wrong captcha!<p>';
			}
		}

        echo '</div>' . "\n";

        echo '  </div>' . "\n";
    }

	// Prikazemo dodaten uvod za GDPR
	public function displayIntroductionGDPR()
	{	
		$user_settings = GDPR::getSurveySettings(get('anketa'));

		echo '<div class="spremenljivka '.(isset($_POST['gdpr']) ? ' required_require' : '').'">' . "\n";

        echo '<div class="naslov">';
        
        echo GDPR::getSurveyIntro(get('anketa'));

        // JS za prikaz popup-a
        echo '<script> $(".gdpr_popup_trigger").click(function(){ show_gdpr_about(\''.get('lang_id').'\'); });</script>';
		echo '</div>' . "\n";

		// Radio buttons
		echo '<input type="hidden" name="gdpr" value="1">';
		echo '<div class="variable_holder clr">';
		echo '	<div class="variabla"><label for="gdpr_accept_0"><input type="radio" name="gdpr_accept" id="gdpr_accept_0" value="0"><span class="enka-checkbox-radio"></span>'.self::$lang['srv_gdpr_intro_no'].'</label></div>';
		echo '	<div class="variabla"><label for="gdpr_accept_1"><input type="radio" name="gdpr_accept" id="gdpr_accept_1" value="1"><span class="enka-checkbox-radio"></span>'.self::$lang['srv_gdpr_intro_yes'].'</label></div>';
		echo '</div>';

        echo '</div>' . "\n";
	}
	
	/************************************************
     * Izrišemo staticen uvod, ki ne shrani nicesar (user se ustvari v bazi sele na naslednji strani)
     ************************************************/
	public function displayStaticIntroduction()
	{

        $row = SurveyInfo::getInstance()->getSurveyRow();

        // datapiping
        $row['introduction'] = Helper::dataPiping($row['introduction']);

        SurveySetting::getInstance()->Init(get('anketa'));
        $hide_mobile_img = SurveySetting::getInstance()->getSurveyMiscSetting('hide_mobile_img');
        $class = ($hide_mobile_img == '1') ? 'hide_mobile_img' : '';
        if (isset($_GET['mobile']) && $_GET['mobile'] == 1)
            $class .= ' mobile_preview';
        elseif (isset($_GET['mobile']) && $_GET['mobile'] == 2)
            $class .= ' tablet_preview';


        echo '<div class="outercontainer_holder ' . $class . ' uvod_static">';
        echo '<div class="outercontainer_holder_top"></div>';

        echo '<div id="outercontainer" class="' . $class . ' uvod_static">';
		echo '<div class="outercontainer_header"></div>';
        
        
        echo '<div id="container">' . "\n";

        Display::getInstance()->logo();

        Display::getInstance()->progress_bar();

        if (!get('printPreview')) {
            echo '<h1 ' . (SurveyInfo::getInstance()->checkSurveyModule('uporabnost') ? ' class="evalvacija"' : '') . '>' . Helper::getInstance()->displayAkronim() . '</h1>' . "\n";
        }

        //opozorilo ce urejamo ze reseno anketo
        if (get('quick_view') == true) {
            echo '<div id="edit_warning">';
            echo self::$lang['srv_quick_view_text'];
            echo '</div>';
        }
        //opozorilo ce urejamo ze reseno anketo
        if (isset($_GET['urejanje'])) {
            echo '<div id="edit_warning">';
            echo self::$lang['srv_edit_text'];
            echo '</div>';

			// Preverimo ce gre za prvo popravljanje podatkov in avtomatsko ustvarimo arhiv podatkov ce je potrebno
			ob_flush();	// ZAKAJ JE TUKAJ TA FLUSH? KER POTEM NE DELA NAKNADNO UREJANJE CE IMA ANKETA LOOPE
			$sas = new SurveyAdminSettings();
			$sas->checkFirstDataChange();

			// Updatamo tracking (ker gre za editiranje odgovorov)
            TrackingClass::update(get('anketa'), 4);
        }

        echo '<form method="post" action="' . SurveyInfo::getSurveyLink(false, false) . '&amp;grupa=0' . Header::getSurveyParams(true) . str_replace('&', '&amp;', get('cookie_url')) . '">' . "\n";
        echo '<div class="grupa">' . "\n";
        echo '  <input type="hidden" name="dummy" value="foo">' . "\n";
        echo '  <input type="hidden" name="referer" value="' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '') . '">' . "\n";


        $this->displayIntroductionContent();


        // koda za izponjevanje ankete (splosna - brez prepoznave userjev) - dodatno preverimo ce je ta funkcionalnost na voljo v paketu
        $userAccess = UserAccess::getInstance(self::$global_user_id);
        $sqlp = sisplet_query("SELECT COUNT(*) AS count FROM srv_password WHERE ank_id='" . get('anketa') . "'");
        $rowp = mysqli_fetch_array($sqlp);
        if ($rowp['count'] > 0 && $userAccess->checkUserAccess($what='password')) {
            echo '  <div class="spremenljivka">' . "\n";
            echo '    <p>' . self::$lang['insert_password'] . ': <input type="text" name="password" value="' . (isset($_COOKIE['password_' . get('anketa')]) ? $_COOKIE['password_' . get('anketa')] : '') . '"></p>';
            echo '  </div>' . "\n";
        }

        SurveySetting::getInstance()->Init(get('anketa'));

        // izbira jezika za respondenta
        if (SurveySetting::getInstance()->getSurveyMiscSetting('resp_change_lang') == 1 && $row['multilang'] == 1 && !get('printPreview')) {

            $lang_resp = $row['lang_resp'];
            $lang_array = array();

            $current = self::$lang['id'];

            $file = lang_path($lang_resp);
            include($file);
            $lang_array[$lang['id']] = $lang['language'];

            $file = lang_path($current);
            include($file);

            $sqll = sisplet_query("SELECT lang_id, language FROM srv_language WHERE ank_id='" . get('anketa') . "' ORDER BY language");
            while ($rowl = mysqli_fetch_array($sqll)) {
                $lang_array[$rowl['lang_id']] = $rowl['language'];
            }

            echo '  <div class="spremenljivka lang_pick">' . "\n";

            // Izbira jezika z dropdown menijem
            if (SurveySetting::getInstance()->getSurveyMiscSetting('resp_change_lang_type') == 1) {
                echo '    <h3>' . self::$lang['lang'] . ': ';
                echo '    <select 
                            name="language" 
                            id="language" 
                            onchange="window.location.href = 
                                \'' . SurveyInfo::getSurveyLink() . '?language=\'+document.getElementById(\'language\').value+\'' . 
                                '&survey-'.get('anketa').'=' . get('cookie_url') . '\'+\'' .
                                (isset($_GET['skupina']) ? '&skupina=' . $_GET['skupina'] : '') . '\'+\'' . 
                                (isset($_GET['preview']) ? '&preview=' . $_GET['preview'] : '') . '\'+\'' . 
								(isset($_GET['testdata']) ? '&testdata=' . $_GET['testdata'] : '') . '\'+\'' . 
                                (isset($_GET['code']) ? '&code=' . $_GET['code'] : '') . '\'+\'' . 
                                (isset($_GET['params']) ? '&params=' . $_GET['params'] : '') . '\'+\'' . 
                                (isset($_GET['usr_id']) ? '&usr_id=' . $_GET['usr_id'] : '') . '\'+\'' . 
                                (isset($_GET['status']) ? '&status=' . $_GET['status'] : '') . '\'+\'' . 
                                (isset($_GET['recnum']) ? '&recnum=' . $_GET['recnum'] : '') . '\'+\'' . 
                                (isset($_GET['enc']) ? '&enc=' . urlencode($_GET['enc']) : '') . '\'; 
                            return false;">';
                foreach ($lang_array AS $key => $val) {
                    echo '<option value="' . $key . '"' . ($key == $current ? ' selected' : '') . '>' . $val . '</option>';
                }
                echo '    </select>';
                echo '</h3>' . "\n";
            } // Izbira jezika z radio gumbi (default)
            else {
                echo '<h3>' . self::$lang['lang'] . ': </h3>';
                echo '<span style="font-size:13px;">';
                foreach ($lang_array AS $key => $val) {
                    echo '<label for="language_' . $key . '">';
					echo '<input type="radio" name="language" id="language_' . $key . '" 
							value="' . $key . '"' . ($key == $current ? ' 
							checked="checked"' : '') . ' 
							onchange="window.location.href = 
                                \'' . SurveyInfo::getSurveyLink() . '?language=\'+document.getElementById(\'language_' . $key . '\').value+\'' . 
                                '&survey-'.get('anketa').'=' . get('cookie_url') . '\'+\'' .
								(isset($_GET['skupina']) ? '&skupina=' . $_GET['skupina'] : '') . '\'+\'' . 
								(isset($_GET['preview']) ? '&preview=' . $_GET['preview'] : '') . '\'+\'' . 
								(isset($_GET['testdata']) ? '&testdata=' . $_GET['testdata'] : '') . '\'+\'' . 
								(isset($_GET['code']) ? '&code=' . $_GET['code'] : '') . '\'+\'' . 
                                (isset($_GET['params']) ? '&params=' . $_GET['params'] : '') . '\'+\'' . 
                                (isset($_GET['usr_id']) ? '&usr_id=' . $_GET['usr_id'] : '') . '\'+\'' . 
                                (isset($_GET['status']) ? '&status=' . $_GET['status'] : '') . '\'+\'' . 
                                (isset($_GET['recnum']) ? '&recnum=' . $_GET['recnum'] : '') . '\'+\'' . 
								(isset($_GET['enc']) ? '&enc=' . urlencode($_GET['enc']) : '') . '\'; 
							return false;"> ';

					// Font awesome checkbox
                    echo '<span class="enka-checkbox-radio" '.((Helper::getCustomCheckbox() != 0) ? 'style="font-size:' . Helper::getCustomCheckbox() . 'px;"' : '').'></span>';

					echo $val;

					echo '</label><br />';
                }
                echo '</span>';
            }

            echo '  </div>' . "\n";
        }

        echo '</div>' . "\n";  // - grupa

        SurveySetting::getInstance()->Init(get('anketa'));
        if (get('lang_id') != null) $_lang = '_' . get('lang_id'); else $_lang = '';
        $srv_nextpage = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_nextpage_uvod' . $_lang);
        $label = $srv_nextpage != '' ? $srv_nextpage : self::$lang['srv_nextpage_uvod'];

        if (!get('printPreview')) {
			
			// JS na submitu prve strani - lahko da preverjamo privacy ali sprejetje cookija
			$survey_privacy = SurveySetting::getInstance()->getSurveyMiscSetting('survey_privacy');
			if(($row['cookie_continue'] == 0 && $row['cookie'] != -1) || $survey_privacy == 2){
				$js_submit = ' onclick="';				
				if($survey_privacy == 2)
					$js_submit .= 'privacy_check();';
				if($row['cookie_continue'] == 0 && $row['cookie'] != -1)
					$js_submit .= 'cookie_check();';				
				$js_submit .= 'return false;"';
			}
			else{
				$js_submit = '';
			}
			
            echo '  <div class="buttons"><input class="next" type="submit" value="' . $label . '" '.$js_submit.'>' . "\n";

            if (isset($_GET['popup']) && $_GET['popup'] == '1')
                echo '<input class="next" type="submit" value="' . self::$lang['srv_zapri'] . '" onclick="$(\'#popup_div, #popup_iframe\', window.parent.document).hide(); return false;">' . "\n";

            echo '</div>';
        }

        echo '</form>' . "\n";

        echo '</div>' . "\n"; // - container

        if (!get('printPreview')) {

            $this->displayFooterNote();
        }

        echo '</div>' . "\n"; // - outercontainer

        echo '<div class="outercontainer_holder_bottom"></div>';
        echo '</div>';  // -outercontainer_holder
	}


    /************************************************
     * Izrišemo konec ankete
     ************************************************/
    public function displayKonec()
    {
        global $mysql_database_name;
        global $lang;
        global $admin_type;
        global $site_url;
        global $app_settings;

        Header::getInstance()->displaySistemske();

        if (!get('printPreview')) {

            $row = SurveyInfo::getInstance()->getSurveyRow();
            $sqlAlert = sisplet_query("SELECT * FROM srv_alert WHERE ank_id = '" . get('anketa') . "'AND (finish_respondent=1 OR	finish_respondent_cms=1 OR finish_author=1 OR finish_other =1)");


            // EVALVACIJA - oznacimo, da je student odgovoril
            if (Common::checkModule('evalvacija') == '1') {

                $sql1 = sisplet_query("SELECT student FROM eval_data_user WHERE ank_id='".get('anketa')."' AND usr_id='".get('usr_id')."'");
                $sql2 = sisplet_query("SELECT d.* FROM srv_spremenljivka s, srv_grupa g, srv_data_text" . get('db_table') . " d WHERE g.ank_id='" . get('anketa') . "'AND s.gru_id=g.id AND s.sistem='1' AND s.variable='sifpredm' AND d.spr_id=s.id AND d.usr_id='" . get('usr_id') . "'");
                $sql3 = sisplet_query("SELECT d.* FROM srv_spremenljivka s, srv_grupa g, srv_data_text" . get('db_table') . " d WHERE g.ank_id='" . get('anketa') . "'AND s.gru_id=g.id AND s.sistem='1' AND s.variable='siffaks' AND d.spr_id=s.id AND d.usr_id='" . get('usr_id') . "'");
                $sql4 = sisplet_query("SELECT d.* FROM srv_spremenljivka s, srv_grupa g, srv_data_text" . get('db_table') . " d WHERE g.ank_id='" . get('anketa') . "'AND s.gru_id=g.id AND s.sistem='1' AND s.variable='sifizv1' AND d.spr_id=s.id AND d.usr_id='" . get('usr_id') . "'");
                $sql5 = sisplet_query("SELECT s.* FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='" . get('anketa') . "'AND s.gru_id=g.id AND s.sistem='1' AND s.variable='podipl'");
                $sql6 = sisplet_query("SELECT s.* FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='" . get('anketa') . "'AND s.gru_id=g.id AND s.sistem='1' AND s.variable='podipl2'");

                // Ce imamo sifro predmeta gre za anketo za ocenjevanje predmetov
                if (mysqli_num_rows($sql2) > 0) {
                    $row1 = mysqli_fetch_array($sql1);
                    $row2 = mysqli_fetch_array($sql2);

                    // Ce imamo sifro izvajalca gre za anketo PRED izpiti (eval_data_anketaPred), drugace je anketa PO izpitih (eval_data_anketaPo)
                    if (mysqli_num_rows($sql4) > 0) {
                        // student je v celoti odgovoril na anketo
                        sisplet_query("INSERT INTO eval_data_anketaPred (predmet, student, ank_id) VALUES ('$row2[text]', '$row1[student]', '" . get('anketa') . "')");
                    } else {
                        // student je v celoti odgovoril na anketo
                        sisplet_query("INSERT INTO eval_data_anketaPo (predmet, student, ank_id) VALUES ('$row2[text]', '$row1[student]', '" . get('anketa') . "')");
                    }

					// Pobrisemo md5 (id studenta) iz zacasne tabele eval_data_user zaradi anonimnosti
					sisplet_query("DELETE FROM eval_data_user WHERE usr_id='".get('usr_id')."' AND ank_id='".get('anketa')."'");
                }
				// splosna PODIPLOMSKA anketa (ena na studenta, brez predmetov)
                elseif (mysqli_num_rows($sql5) > 0) {
                    $row1 = mysqli_fetch_array($sql1);

                    // student je v celoti odgovoril na anketo
                    sisplet_query("INSERT INTO eval_data_podipl (student, ank_id) VALUES ('$row1[student]', '" . get('anketa') . "')");

					// Pobrisemo md5 (id studenta) iz zacasne tabele eval_data_user zaradi anonimnosti
					sisplet_query("DELETE FROM eval_data_user WHERE usr_id='".get('usr_id')."' AND ank_id='".get('anketa')."'");
                }
				// splosna PODIPLOMSKA anketa 2 (ena na studenta, brez predmetov)
                elseif (mysqli_num_rows($sql6) > 0) {
                    $row1 = mysqli_fetch_array($sql1);

                    // student je v celoti odgovoril na anketo
                    sisplet_query("INSERT INTO eval_data_podipl2 (student, ank_id) VALUES ('$row1[student]', '" . get('anketa') . "')");

					// Pobrisemo md5 (id studenta) iz zacasne tabele eval_data_user zaradi anonimnosti
					sisplet_query("DELETE FROM eval_data_user WHERE usr_id='".get('usr_id')."' AND ank_id='".get('anketa')."'");
                }
				// SPLOSNA anketa (ena na studenta, brez predmetov)
                elseif (mysqli_num_rows($sql1) > 0) {
                    $row1 = mysqli_fetch_array($sql1);

                    // student je v celoti odgovoril na anketo
                    sisplet_query("INSERT INTO eval_data_splosna (student, ank_id) VALUES ('$row1[student]', '" . get('anketa') . "')");

					// Pobrisemo md5 (id studenta) iz zacasne tabele eval_data_user zaradi anonimnosti
					sisplet_query("DELETE FROM eval_data_user WHERE usr_id='".get('usr_id')."' AND ank_id='".get('anketa')."'");
                }
            }

			// Ce je vklopljen evoli team meter zabelezimo kvoto
            $tm_quota_increase = false;
            $modules = SurveyInfo::getInstance()->getSurveyModules();
            if (isset($modules['evoli_teammeter']) 
                || isset($modules['evoli_quality_climate']) 
                || isset($modules['evoli_teamship_meter']) 
                || isset($modules['evoli_organizational_employeeship_meter'])
            ) {

				// Dobimo id skupine za respondenta
				$sqlGroupTM = sisplet_query("SELECT d.* FROM srv_data_vrednost_active d, srv_spremenljivka s, srv_grupa g
												WHERE g.ank_id='".get('anketa')."' AND d.usr_id='".get('usr_id')."' AND s.skupine='1'
													AND s.id=d.spr_id AND g.id=s.gru_id");
                $rowGroupTM = mysqli_fetch_array($sqlGroupTM);

				if($rowGroupTM['vre_id'] > 0){
					 
                    $tm_quota = 0;
                    
                    // Prestejemo vse response za doloceno skupino/podjetje (to ni vezano na departmente, ker smo lahko tudi brez departmentov!)
                    $sqlTM = sisplet_query("SELECT DISTINCT(usr_id) AS usr_id
                                                FROM srv_data_vrednost_active
                                                WHERE vre_id='".$rowGroupTM['vre_id']."'");

                    while($rowTM = mysqli_fetch_array($sqlTM)){

                        // Dodatno se preverimo, ce je user res koncal anketo (ima status 6 in ni lurker)
                        $sqlTMU = sisplet_query("SELECT last_status, lurker FROM srv_user where id='".$rowTM['usr_id']."'");
                        $rowTMU = mysqli_fetch_array($sqlTMU);

                        if($rowTMU['last_status'] == '6' && $rowTMU['lurker'] == '0')
                            $tm_quota++;
                    }
   
					$sqlTM = sisplet_query("UPDATE srv_evoli_teammeter 
											SET kvota_val='".$tm_quota."' 
											WHERE ank_id='".get('anketa')."' AND skupina_id='".$rowGroupTM['vre_id']."'");
					$tm_quota_increase = true;
				}
            }
			
            // Popravimo url za skok po koncu ankete ce nima http://
            if ($row['url'] != '' && substr($row['url'], 0, 4) != 'http') {
                $finishUrl = 'http://' . $row['url'];
            } else {
                $finishUrl = $row['url'];
            }

			// Dodamo datapiping v url-ju
			if($row['url'] != ''){
				// Počistimo html tage
				$finishUrl = strip_tags(Helper::dataPiping($finishUrl));
			}

            // Preverimo ce imamo nastavljene kaksne get parametre za dodat pri skoku na url
            if ($row['concl_link'] == 1 && $finishUrl != '') {
                SurveySetting::getInstance()->Init(get('anketa'));
                $concl_url_usr_id = SurveySetting::getInstance()->getSurveyMiscSetting('concl_url_usr_id');
                $concl_url_status = SurveySetting::getInstance()->getSurveyMiscSetting('concl_url_status');
                $concl_url_recnum = SurveySetting::getInstance()->getSurveyMiscSetting('concl_url_recnum');

                if ($concl_url_usr_id == '1' || $concl_url_status == '1' || $concl_url_recnum == '1')
                    $finishUrl .= '?';

                if ($concl_url_usr_id == '1') {
                    $finishUrl .= 'usr_id=' . get('usr_id') . '&';
                }

                if ($concl_url_status == '1' || $concl_url_recnum == '1') {
                    $sqlU = sisplet_query("SELECT last_status, recnum FROM srv_user WHERE id = '" . get('usr_id') . "'");
                    $rowU = mysqli_fetch_array($sqlU);

                    if($concl_url_recnum == '1')
                        $finishUrl .= 'recnum=' . $rowU['recnum'] . '&';

                    if($concl_url_status == '1')
                        $finishUrl .= 'status=' . $rowU['last_status'] . '&';    
                }

                if ($concl_url_usr_id == '1' || $concl_url_status == '1' || $concl_url_recnum == '1')
                    $finishUrl = substr($finishUrl, 0, -1);
            }
			
			// Pri Gorenje anketah posljemo id reklamacije preko njihovega api-ja
			if (Common::checkModule('gorenje')){
				$rek_id = SurveyGorenje::getGorenjeVariable(get('anketa'), 'rekid', get('usr_id'));
				if($rek_id != '' && $rek_id > 0){
					
					// Posljemo id reklamacije preko api-ja
					SurveyGorenje::sendGorenjeRekID($rek_id);
				}	
			}
			
			// Preverimo ce imamo vklopljen modul za panel - potem napolnimo url z ustreznimi parametri
			if(isset($modules['panel'])){
				
				// Pridobimo vse nastavitve panela
				$sp = new SurveyPanel(get('anketa'));
				$panel_settings = $sp->getPanelSettings();
                
                // Posebno samo za ipanel - Izraelski projekt
                if($app_settings['app_name'] == 'www.1ka.si' && (get('anketa') == '232992' || get('anketa') == '232795' || get('anketa') == '248217' || get('anketa') == '248757' || get('anketa') == '248762')){

                    // Pridobimo id panelista ki je bil shranjen na zacetku resevanja v sistemsko spremenljivko
                    $sqlP = sisplet_query("SELECT d.*, s.variable FROM srv_data_text".get('db_table')." d, srv_spremenljivka s, srv_grupa g 
                                    WHERE d.usr_id='".get('usr_id')."' AND s.variable IN ('id', 'i_project', 'i_user1', 'i_user4', 'i_user5', 'i_user6', 'i_user7')
                                        AND d.spr_id=s.id AND s.gru_id=g.id AND g.ank_id='".get('anketa')."'");
                    while($rowP = mysqli_fetch_array($sqlP)){

                        if($rowP['variable'] == 'id')
                            $panelist_id = $rowP['text'];
                        elseif($rowP['variable'] == 'i_user1')
                            $panelist_user = $rowP['text'];
                        elseif($rowP['variable'] == 'i_project')
                            $panelist_project = $rowP['text'];
                        elseif($rowP['variable'] == 'i_user4')
                            $user_data4 = $rowP['text'];
                        elseif($rowP['variable'] == 'i_user5')
                            $user_data5 = $rowP['text'];
                        elseif($rowP['variable'] == 'i_user6')
                            $user_data6 = $rowP['text'];
                        elseif($rowP['variable'] == 'i_user7')
                            $user_data7 = $rowP['text'];
                    }
                    
                    // Pridobimo koncen status panelista (ce ni bil nikjer nastavljen uporabimo default)
                    $panel_status = (isset($_COOKIE['panel_status']) && $_COOKIE['panel_status'] != '') ? $_COOKIE['panel_status'] : $panel_settings['status_default'];

                    // Nastavimo koncen url za redirect
                    $finishUrl = 'http://survey.ipanel.co.il/mrIWeb/mrIWeb.dll?';
                    $finishUrl .= 'id='.$panelist_id.'&i.user1='.$panelist_user.'&i.project='.$panelist_project.'&i.user9='.$panel_status;
                    $finishUrl .= '&i.user4='.$user_data4.'&i.user5='.$user_data5.'&i.user6='.$user_data6.'&i.user7='.$user_data7;
                }
                else{
                    // Pridobimo id panelista ki je bil shranjen na zacetku resevanja v sistemsko spremenljivko
                    $sqlP = sisplet_query("SELECT d.* FROM srv_data_text".get('db_table')." d, srv_spremenljivka s, srv_grupa g 
                                    WHERE d.usr_id='".get('usr_id')."' AND s.variable='".$panel_settings['user_id_name']."'
                                        AND d.spr_id=s.id AND s.gru_id=g.id AND g.ank_id='".get('anketa')."'");
                    $rowP = mysqli_fetch_array($sqlP);
                    $panelist_id = $rowP['text'];

                    // Pridobimo koncen status panelista (ce ni bil nikjer nastavljen uporabimo default)
                    $panel_status = (isset($_COOKIE['panel_status']) && $_COOKIE['panel_status'] != '') ? $_COOKIE['panel_status'] : $panel_settings['status_default'];

                    // Nastavimo koncen url za redirect
                    $finishUrl = $row['url'].'?'.$panel_settings['user_id_name'].'='.$panelist_id.'&'.$panel_settings['status_name'].'='.$panel_status;
                } 
			}

			// Na koncu preverimo, če gre za hierarhijo in resevanje supersifre
            // V kolikor gre za hierarhijo, ki uporablja superšifro potem pred redirectom poberemo vse parametre
            if (Common::checkModule('hierarhija') == '1' && SurveyInfo::checkSurveyModule('hierarhija') == 2) {
                $resevanje = sisplet_query("SELECT * FROM srv_hierarhija_supersifra_resevanje WHERE user_id='".get('usr_id')."'", "obj");

                $kode = sisplet_query("SELECT kode FROM srv_hierarhija_supersifra WHERE koda='".$resevanje->supersifra."'", "obj");
                $kode = unserialize($kode->kode);

                sisplet_query("UPDATE srv_hierarhija_supersifra_resevanje SET status=6 WHERE user_id='".get('usr_id')."'");

                // V kolikor prispemo smo ravno zaključili reševanje zadnje ankete v supersifri potem prikažemo konec oz. glede na nastavitve ankete in ne preusmerimo ponovno na začetek
                if(end($kode) != $resevanje->koda){
                    // Glede na vrednost
                    $naslednja = array_search($resevanje->koda, $kode) + 1;

                    $url = sisplet_query("SELECT url FROM srv_hierarhija_koda WHERE koda='".$kode[$naslednja]."'", "obj");

                    $url_encode_spremenljivke = urlencode(base64_encode($url->url . '&supersifra=' . $resevanje->supersifra.'&resujem='.$naslednja));
                    $redirect = $site_url .'a/'.get('anketa').'?enc='.$url_encode_spremenljivke;

                    header("Location: $redirect");
                }

            }
			
			// za posebno MJU anketo pridobimo skupino in preusmerimo nazaj na zacetek z ustreznim lepim linkom
			if(isset($modules['mju_theme'])){	
				
				// Dobimo id skupine za respondenta
				$sqlGroup = sisplet_query("SELECT d.* FROM srv_data_vrednost_active d, srv_spremenljivka s, srv_grupa g
												WHERE g.ank_id='".get('anketa')."' AND d.usr_id='".get('usr_id')."' AND s.skupine='1'
													AND s.id=d.spr_id AND g.id=s.gru_id");
                $rowGroup = mysqli_fetch_array($sqlGroup);
				
				if($rowGroup['vre_id'] > 0){
									
					$sqlGroupName = sisplet_query("SELECT naslov FROM srv_vrednost WHERE id='".$rowGroup['vre_id']."' AND spr_id='".$rowGroup['spr_id']."'");
					$rowGroupName = mysqli_fetch_array($sqlGroupName);
					
					$finishUrl = 'https://gov-ankete.si/'.$rowGroupName['naslov'];
				}	
				else{
					$finishUrl = 'https://gov-ankete.si/';
				}
			}
			// za posebno MJU anketo pridobimo skupino in preusmerimo nazaj na zacetek z ustreznim lepim linkom
			if(isset($modules['mju_redirect'])){	
				
				// Dobimo id skupine za respondenta
				$sqlGroup = sisplet_query("SELECT d.* FROM srv_data_vrednost_active d, srv_spremenljivka s, srv_grupa g
												WHERE g.ank_id='".get('anketa')."' AND d.usr_id='".get('usr_id')."' AND s.skupine='1'
													AND s.id=d.spr_id AND g.id=s.gru_id");
                $rowGroup = mysqli_fetch_array($sqlGroup);
				
				if($rowGroup['vre_id'] > 0){
									
					$sqlGroupName = sisplet_query("SELECT naslov, variable FROM srv_vrednost WHERE id='".$rowGroup['vre_id']."' AND spr_id='".$rowGroup['spr_id']."'");
					$rowGroupName = mysqli_fetch_array($sqlGroupName);
					
					$finishUrl = 'https://gov-ankete.si/UE1_'.$rowGroupName['variable'];
				}	
				else{
					$finishUrl = 'https://gov-ankete.si/';
				}
			}
			
			
			// URL na katerega skocimo ce se zapre okno - pogledamo ce imamo nastavljen custom url (settings_optional.php)
            if(isset($app_settings['survey_finish_url']) && $app_settings['survey_finish_url'] != '')
                $close_url = $app_settings['survey_finish_url'];
            else
                $close_url = 'https://www.1ka.si/';
            
			
            // pri formi posebej pogledamo ce anketo zapremo ali skocimo na url - po novem je lahko vklopljen zakljucek
            if ($row['survey_type'] == 1 && $row['show_concl'] == 0) {
                
                if ($row['concl_link'] == 1) {
                    header("Location: $finishUrl");
                } 
                else {
                    echo '    <script>' . "\n";
                    echo '      window.close();' . "\n";
                    echo '      document.location.href=\''.$close_url.'\';' . "\n";
                    echo '    </script>' . "\n";
                }
            } 
            // zakljucek ankete ni prikazan in oznaceno je da zapremo anketo
            elseif ($row['show_concl'] == 0) {
                
                if ($row['concl_link'] == 0) {
                    echo '    <script>' . "\n";
                    echo '      window.close();' . "\n";
                    echo '      document.location.href=\''.$close_url.'\';' . "\n";
                    echo '    </script>' . "\n";
                } 
                // Rekurzivno
                else if ($row['concl_link'] == 2) {
                    header("Location: " . SurveyInfo::getSurveyLink());
                } 
                else {
                    header("Location: $finishUrl");
                }

            } else {

                SurveySetting::getInstance()->Init(get('anketa'));
                $hide_mobile_img = SurveySetting::getInstance()->getSurveyMiscSetting('hide_mobile_img');
                $class = ($hide_mobile_img == '1') ? 'hide_mobile_img' : '';
                if (isset($_GET['mobile']) && $_GET['mobile'] == 1)
                    $class .= ' mobile_preview';
                elseif (isset($_GET['mobile']) && $_GET['mobile'] == 2)
                    $class .= ' tablet_preview';


                echo '<div class="outercontainer_holder ' . $class . ' concl">';
                echo '<div class="outercontainer_holder_top"></div>';

                echo '<div id="outercontainer" class="' . $class . ' concl">' . "\n";
				echo '<div class="outercontainer_header"></div>';
                
                echo '<div id="container">' . "\n";

                Display::getInstance()->logo();

                Display::getInstance()->progress_bar();

                echo '<h1 ' . (isset($modules['uporabnost']) ? ' class="evalvacija"' : '') . '>' . Helper::getInstance()->displayAkronim() . '</h1>' . "\n";

                echo '<div class="grupa">' . "\n";

                Statistic::displayStatistika(true);

                $this->displayKonecContent();

                echo '</div>' . "\n"; //-grupa

                if ($row['user_from_cms'] == 2) {
                    //echo '<p style="text-align:center"><a href="'.$site_url.'a/'.$this->anketa.'">'.$lang['srv_nextins'].'</a></p>';
                    echo '<p style="text-align:center"><a href="' . SurveyInfo::getSurveyLink() . '">' . $lang['srv_nextins'] . '</a></p>';
                }

                SurveySetting::getInstance()->Init(get('anketa'));
                if (get('lang_id') != null) $_lang = '_' . get('lang_id'); else $_lang = '';
                if (!get('printPreview')) {
                    $srv_prevpage = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_prevpage' . $_lang);
                    if ($srv_prevpage == '') $srv_prevpage = $lang['srv_prevpage'];

                    $display_backlink = SurveySetting::getInstance()->getSurveyMiscSetting('display_backlink');

                    echo '<div class="buttons">';
                    // prejsna stran, prikazemo ce ni v zakjucku drugace nastavljeno
                    if ($row['concl_back_button'] != '0')
                        #echo '<input class="prev" type="button" value="'.$srv_prevpage.'" onclick="history.back()">'."\n";
                        if ($display_backlink != '0') {
                            // Posebej za WebSM anketo - back naredimo tako, da poiscemo prejsnjo stran
                            if (get('anketa') == get('webSMSurvey') && Common::checkModule('websmsurvey') == '1') {

                                $grupa = Find::findPrevGrupa(get('anketa'), get('grupa'));
                                $grupa = ($grupa > 0) ? '&grupa=' . $grupa : '';

                                $language = (isset($_GET['language'])) ? '&language=' . $_GET['language'] : '';

                                $link = SurveyInfo::getSurveyLink(false, false) . $grupa . $language;

                                echo '<input class="prev" type="button" value="' . $srv_prevpage . '" onclick="location.href=\'' . $link . '\';">';
                            } else {
                                echo '<input class="prev" type="button" value="' . $srv_prevpage . '" onclick="javascript:history.go(-1)">' . "\n";
                            }
                        }
                }
                if ($finishUrl != '') {
					
                    if ($row['concl_link'] == 1) {
                        if (!isset($modules['uporabnost']))
                            $js = 'document.location.href=\'' . $finishUrl . '\';';
                        else
                            $js = 'top.location.href=\'' . $finishUrl . '\';';
                    } 
					elseif ($row['concl_link'] == 2) {
                        if (!isset($modules['uporabnost']))
                            $js = 'document.location.href=\'' . SurveyInfo::getSurveyLink() . '\';';
                        else
                            $js = 'top.location.href=\'' . SurveyInfo::getSurveyLink() . '\';';
                    } 
					elseif (!isset($modules['uporabnost'])) {
                        if (Common::checkModule('evalvacija') == '1')
                            $js = 'window.close(); document.location.href=\'https://www.uni-lj.si/\';';
                        else
                            $js = 'window.close(); document.location.href=\''.$close_url.'\';';
                    } 
					// uporabnost
                    else
                        $js = 'top.close();';

                    if (isset($_GET['popup']) && $_GET['popup'] == '1')
                        $js = '$(\'#popup_div, #popup_iframe\', window.parent.document).hide(); ';
					
					
					// Za posebno MJU anketo posebej pohendlamo kaj se zgodi po koncu
					if(isset($modules['mju_theme'])){
						
						// Mobitel ali tablica se po koncu zapre
						if(get('mobile') == 1 || get('mobile') == 2){
							$js = 'window.close(); document.location.href=\'https://http://www.mju.gov.si//\';';
						}
						// Drugace se preusmeri na zacetek
						else{
							$js = 'document.location.href=\'' . $finishUrl . '\';';
						}
					}


                    if (!get('printPreview')) {
                        $srv_konec = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_konec' . $_lang);
                        /*if ($row['text'] != '')	// besedilo koncne povezave shranimo v misc setting, da bo konsistentno z ostalimi prevodi
                            $text = $row['text'];
                        else*/
                        if ($srv_konec != '')
                            $text = $srv_konec;
                        else
                            $text = $lang['srv_konec'];

                        if ($row['concl_end_button'] == 1)
                            echo '<input class="next" type="submit" value="' . $text . '" onclick="' . $js . ' return false;">' . "\n";
                    }
                }

                echo '</div>';

                if ($row['user_from_cms'] == 2 && $row['user_from_cms_email'] == 1 && $admin_type <= 2) { // vnosos
                    $sqlg = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='" . get('anketa') . "'ORDER BY vrstni_red ASC LIMIT 1");
                    $rowg = mysqli_fetch_array($sqlg);
                    //echo '<p class="vnos"><a href="'.$site_url.'a/'.$this->anketa.'&grupa='.$rowg['id'].'">'.$lang['srv_nextins'].'</a> | <a href="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'">'.$lang['srv_insend'].'</a></p>';
                    echo '<p class="vnos"><a href="' . SurveyInfo::getSurveyLink() . '&grupa=' . $rowg['id'] . '">' . $lang['srv_nextins'] . '</a> | <a href="' . $site_url . 'admin/survey/index.php?anketa=' . get('anketa') . '">' . $lang['srv_insend'] . '</a></p>';
                }

                echo '</div>' . "\n"; // -container

                $this->displayFooterNote();
                #$this->displayRespondetnPDF();

                echo '</div>' . "\n"; // -outercontainer

                echo '<div class="outercontainer_holder_bottom"></div>';
                echo '</div>'; // -outercontainer_holder
            }
            
            //is respondent lurker?
            $sqlur = sisplet_query("SELECT lurker FROM srv_user WHERE id = '" . get('usr_id') . "'", "obj");

            // ce je tko oznacen in ce reposndent ni lurker, posljemo se alerte
            if (mysqli_num_rows($sqlAlert) > 0 && $sqlur->lurker == 0)
                Helper::getInstance()->alert();

			// Ce je vklopljen evoli team meter, preverimo, ce posiljamo obvestilo po dosezeni kvoti
            if( (isset($modules['evoli_teammeter']) || isset($modules['evoli_quality_climate']) || isset($modules['evoli_teamship_meter']) || isset($modules['evoli_organizational_employeeship_meter']))
                && $tm_quota_increase
            ){
				Helper::getInstance()->alertTeamMeter();
            }

            Js::js_tracking();

        } else { // za print preview in pdf

            $row = SurveyInfo::getInstance()->getSurveyRow();
            echo '<div id="container">' . "\n";
            echo '<div class="grupa">' . "\n";
            echo '  <div class="spremenljivka">' . "\n";
            if ($row['conclusion'] == '') {
                $concl = $lang['srv_end'];
            } else {
                $concl = $row['conclusion'];
            }
            if (get('lang_id') != null) {
                $sql1 = sisplet_query("SELECT naslov FROM srv_language_spremenljivka WHERE ank_id='" . get('anketa') . "'AND spr_id='-2' AND lang_id='" . get('lang_id') . "'");
                $row1 = mysqli_fetch_array($sql1);
                if ($row1['naslov'] != '') $concl = $row1['naslov'];
            }
            echo '    <p>' . $concl . '</p>' . "\n";
            echo '  </div>' . "\n";
            echo '</div>' . "\n";
            echo '</div>' . "\n";
        }
    }

    public function displayKonecContent(){

        $row = SurveyInfo::getInstance()->getSurveyRow();

        echo '  <div class="spremenljivka">' . "\n";

		// Ce gre za gdpr zakljucek (respondent ni sprejel pogojev)
		if(GDPR::isGDPRSurveyTemplate(get('anketa')) && isset($_POST['gdpr_accept']) && $_POST['gdpr_accept'] == '0'){			
			self::displayKonecGDPR();
		}
		else{
		
			if ($row['conclusion'] == '') {
				$concl = self::$lang['srv_end'];
			} else {
				$concl = $row['conclusion'];
			}
			if (get('lang_id') != null) {
				$sql1 = sisplet_query("SELECT naslov FROM srv_language_spremenljivka WHERE ank_id='" . get('anketa') . "' AND spr_id='-2' AND lang_id='" . get('lang_id') . "'");
				$row1 = mysqli_fetch_array($sql1);
				if ($row1['naslov'] != '') $concl = $row1['naslov'];
			}

			$concl = Helper::dataPiping($concl);

			echo '    <div class="naslov"><p>' . $concl . '</p></div>' . "\n";

			Display::getInstance()->displayReturnEditURL();
			Display::getInstance()->displayRespondetnPDF();
		}
		
        echo '  </div>' . "\n";


		// Prikaz pravilnih rezultatov v primeru kviza
		if(SurveyInfo::getInstance()->checkSurveyModule('quiz')){
			
			// Pridobimo nastavitve kviza
			$sq = new SurveyQuiz(get('anketa'));
			$quiz_settings = $sq->getSettings();
				
			if($quiz_settings['results'] == '1' || $quiz_settings['results_chart'] == '1'){
				
				echo '<div id="quiz_results">';
				
				echo '<h2>'.self::$lang['results'].'</h2>';
				
				// Prikaz grafa rezultatov
				if($quiz_settings['results_chart'] == '1')
					Display::getInstance()->displayQuizChart();
				
				// Prikaz rezultatov
				if($quiz_settings['results'] == '1')				
					Display::getInstance()->displayQuizAnswers();
				
				echo '</div>';
			}
		}
		
		// Prikaz Excelleration matrix grafa ce je vklopljen napredni modul
		if(SurveyInfo::getInstance()->checkSurveyModule('excell_matrix')){
			echo '<div id="excell_matrix">';
			Display::getInstance()->displayExcellChart();
			echo '</div>';
        }
        
        // SKAVTI - prikaz povzetka odgovorov in grafa
        global $mysql_database_name;
        //if($mysql_database_name == '1ka' && get('anketa') == '64'){
        if($mysql_database_name == 'real1kasi' && (get('anketa') == '293926' || get('anketa') == '314856' || get('anketa') == '332793')){
			echo '<div id="skavti_answers">';
			Display::getInstance()->displaySkavtiAnswers();
			echo '</div>';
		}

        // NIJZ - prikaz radar grafa in tabele
        global $site_domain;
        if( ($site_domain == 'test.1ka.si' && get('anketa') == '8892') || ($site_domain == 'anketa.nijz.si' && get('anketa') == '126738') ){	
            
            $nijz = new SurveyNIJZ(get('anketa'), get('usr_id'));
            
            $nijz->displayRadar();
            $nijz->displayTable();
		}
    }
	
	// Prikazemo dodaten uvod za GDPR
	public function displayKonecGDPR()
	{		
		//$user_settings = GDPR::getSurveySettings(get('anketa'));
		
        echo '<div class="naslov">';
        echo '<p>'.self::$lang['srv_gdpr_concl'].'</p>';	
		echo '</div>';
	}
	

    /**
     * @desc prikaze konec ankete
     */
    public function displayKonecGlasovanje(){
        global $app_settings;

        echo '<script>var srv_meta_anketa_id = ' . get('anketa') . ';</script>';

        //izpis zakljucka
        if (isset($_GET['glas_end']) && $_GET['glas_end'] == 1) {
            if (!get('printPreview')) {

                $row = SurveyInfo::getInstance()->getSurveyRow();

                SurveySetting::getInstance()->Init(get('anketa'));
                $endButton = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_konec');
                $endButton = ($endButton == '') ? self::$lang['srv_konec'] : $endButton;
                //$row['text'] == '' ? $endButton = self::$lang['srv_konec'] : $endButton = $row['text'];

                //dodaten naslov gumba zakljucek
                $srv_prevpage = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_prevpage');
                if ($srv_prevpage != '')
                    $text = $srv_prevpage;
                else
                    $text = self::$lang['srv_prevpage'];


				// URL na katerega skocimo ce se zapre okno - pogledamo ce imamo nastavljen custom url (settings_optional.php)
                if(isset($app_settings['survey_finish_url']) && $app_settings['survey_finish_url'] != '')
                    $close_url = $app_settings['survey_finish_url'];
                else
                    $close_url = 'https://www.1ka.si/';
				
                // zakljucek ankete ni prikazan in oznaceno je da zapremo anketo
                if ($row['show_concl'] < 1) {
                    if ($row['concl_link'] == 0) {
                        echo '    <script>' . "\n";
                        echo '      window.close();' . "\n";
                        echo '      document.location.href=\''.$close_url.'\';' . "\n";
                        echo '    </script>' . "\n";
                    } else {
                        header("Location: $row[url]");
                    }
                }


                echo '<div class="outercontainer_holder concl_glasovanje">';
                echo '<div class="outercontainer_holder_top"></div>';

                echo '<div id="outercontainer concl_glasovanje">' . "\n";
				echo '<div class="outercontainer_header"></div>';
				
                echo '<div id="container">' . "\n";

                Display::getInstance()->logo();

                Display::getInstance()->progress_bar();

                echo '<h1>' . Helper::getInstance()->displayAkronim() . '</h1>' . "\n";

                echo '<div class="grupa">' . "\n";

                echo '  <div class="spremenljivka">' . "\n";

                if ($row['conclusion'] == '') {
                    $concl = self::$lang['srv_end'];
                } else {
                    $concl = $row['conclusion'];
                }
                echo '    <h3><p>' . $concl . '</p></h3>' . "\n";

                echo '  </div>' . "\n";

                echo '</div>' . "\n"; // -grupa

                if ($_GET['preview'] == 'on') {
                    echo '<div class="buttons"><a href="' . self::$site_url . 'admin/survey/index.php?anketa=' . get('anketa') . '">' . self::$lang['srv_back_edit'] . '</a></div>' . "\n";
                }

                echo '<div class="buttons">';


                if ($row['concl_back_button'] == 1) {
                    echo '<input class="prev" type="button" value="' . $text . '" onclick="javascript:history.go(-1)">';
                }

                if ($row['concl_end_button'] == 1) {
                    if ($row['concl_link'] == 1)
                        $js = 'document.location.href=\'' . $row['url'] . '\';';
                    else
                        $js = 'window.close(); document.location.href=\''.$close_url.'\';';

                    if (!get('printPreview')) {
                        echo '<input class="next" type="submit" value="' . $endButton . '" onclick="' . $js . ' return false;">' . "\n";
                    }
                }

                echo '</div>';


                echo '</div>' . "\n"; // -container

                $this->displayFooterNote();

                echo '</div>'; // -outercontainer 

                echo '<div class="outercontainer_holder_bottom"></div>';
                echo '</div>'; // -outercontainer_holder

                // ce je tko oznacen posljemo se alerte
                $sqlAlert = sisplet_query("SELECT * FROM srv_alert WHERE ank_id = '" . get('anketa') . "' AND (finish_respondent=1 OR	finish_respondent_cms=1 OR finish_author=1 OR finish_other =1)");
                if (mysqli_num_rows($sqlAlert) > 0)
                    Helper::getInstance()->alert();
            }
			else { // za print preview in pdf

                $row = SurveyInfo::getInstance()->getSurveyRow();
                echo '<div id="container">' . "\n";
                echo '<div class="grupa">' . "\n";
                echo '  <div class="spremenljivka">' . "\n";
                if ($row['conclusion'] == '') {
                    $concl = self::$lang['srv_end'];
                } else {
                    $concl = $row['conclusion'];
                }
                echo '    <p>' . $concl . '</p>' . "\n";
                echo '  </div>' . "\n";
                echo '</div>' . "\n";
            }
        } //izpis statistike
        else {
            $row = SurveyInfo::getInstance()->getSurveyRow();

            $sqls = sisplet_query("SELECT stat FROM srv_spremenljivka s, srv_glasovanje g WHERE g.ank_id = '" . get('anketa') . "' AND s.id = g.spr_id");
            $rows = mysqli_fetch_array($sqls);

            //ce statistike ne prikazujemo skocimo na zakljucek (ce imammo izklopljeno ali nastavleno na samo urednike in ni urednik)
            if ($rows['stat'] == 0 || ($rows['stat'] == 2 && self::$admin_type != 0 && self::$admin_type != 1)) {
                //header('Location: '.self::$site_url.'a/'.get('anketa').'&grupa='.get('grupa').'&glas_end=1'.get('cookie_url').'');
                header('Location: ' . SurveyInfo::getSurveyLink() . '&grupa=' . get('grupa') . (isset($_GET['language']) ? '&language=' . $_GET['language'] : '') . '&glas_end=1' . get('cookie_url') . '');
            } else {

                echo '<div class="outercontainer_holder concl_statistika">';
                echo '<div class="outercontainer_holder_top"></div>';

                echo '<div id="outercontainer concl_statistika">' . "\n";
				echo '<div class="outercontainer_header"></div>';
				
                echo '<div id="container">' . "\n";

                Display::getInstance()->logo();

                Display::getInstance()->progress_bar();

                echo '<h1>' . Helper::getInstance()->displayAkronim() . '</h1>' . "\n";

                echo '<div class="grupa">' . "\n";
                Statistic::displayStatistika(true);
                echo '</div>' . "\n";

                echo '<div class="buttons">';
                //$url_stat = ''.self::$site_url.'a/'.get('anketa').'&grupa='.get('grupa').'&glas_end=1'.get('cookie_url');
                $url_stat = '' . SurveyInfo::getSurveyLink() . '&grupa=' . get('grupa') . (isset($_GET['language']) ? '&language=' . $_GET['language'] : '') . '&glas_end=1' . get('cookie_url');
                $js = 'document.location.href=\'' . $url_stat . '\';';

                // Gumb nazaj
                if ($row['concl_back_button'] == 1) {
                    $srv_prevpage = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_prevpage');
                    if ($srv_prevpage != '')
                        $text = $srv_prevpage;
                    else
                        $text = self::$lang['srv_prevpage'];

                    echo '<input class="prev" type="button" value="' . $text . '" onclick="javascript:history.go(-1)">';
                }

                if ($row['show_concl'] == 1) {
					if (get('lang_id') != null) $_lang = '_' . get('lang_id'); else $_lang = '';

					$srv_potrdi = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_potrdi'.$_lang);
					if ($srv_potrdi == '') $srv_potrdi = self::$lang['srv_potrdi'];

                    echo '<input class="next" type="submit" value="' . $srv_potrdi . '" onclick="' . $js . ' return false;">' . "\n";
                }
				elseif ($row['concl_end_button'] == 1) {
                    $endButton = self::$lang['srv_konec'];
                    echo '<input class="next" type="submit" value="' . $endButton . '" onclick="' . $js . ' return false;">' . "\n";
                }

                echo '</div>' . "\n";

                echo '</div>' . "\n"; // -container

                echo '</div>' . "\n"; // -outercontainer

                echo '<div class="outercontainer_holder_bottom"></div>';
                echo '</div>'; // -outercontainer_holder
            }
        }
    }

	// Konec pri anketi evoli team meter - ce je kvota polna
	public function displayKonecEvoliTM($date_to='')
    {
        global $mysql_database_name;
        global $lang;
        global $admin_type;
        global $site_url;

		$row = SurveyInfo::getInstance()->getSurveyRow();

		// Popravimo url za skok po koncu ankete ce nima http://
		if ($row['url'] != '' && substr($row['url'], 0, 4) != 'http') {
			$finishUrl = 'http://' . $row['url'];
		} else {
			$finishUrl = $row['url'];
		}

		// Dodamo datapiping v url-ju
		if($row['url'] != ''){
			// Počistimo html tage
			$finishUrl = strip_tags(Helper::dataPiping($finishUrl));
		}

		// Preverimo ce imamo nastavljene kaksne get parametre za dodat pri skoku na url
		if ($row['concl_link'] == 1 && $finishUrl != '') {
			SurveySetting::getInstance()->Init(get('anketa'));
			$concl_url_usr_id = SurveySetting::getInstance()->getSurveyMiscSetting('concl_url_usr_id');
			$concl_url_status = SurveySetting::getInstance()->getSurveyMiscSetting('concl_url_status');
			$concl_url_recnum = SurveySetting::getInstance()->getSurveyMiscSetting('concl_url_recnum');

			if ($concl_url_usr_id == '1' || $concl_url_status == '1' || $concl_url_recnum == '1')
				$finishUrl .= '?';

			if ($concl_url_usr_id == '1') {
				$finishUrl .= 'usr_id=' . get('usr_id') . '&';
			}

			if ($concl_url_status == '1' || $concl_url_recnum == '1') {
				$sqlU = sisplet_query("SELECT last_status FROM srv_user WHERE id = '" . get('usr_id') . "'");
                $rowU = mysqli_fetch_array($sqlU);
                
                if($concl_url_recnum == '1')
                    $finishUrl .= 'recnum=' . $rowU['recnum'] . '&';
                    
                if($concl_url_status == '1')
                    $finishUrl .= 'status=' . $rowU['last_status'] . '&';   
			}

			if ($concl_url_usr_id == '1' || $concl_url_status == '1')
				$finishUrl = substr($finishUrl, 0, -1);
		}

		if ($row['show_concl'] == 0) {
            
            if ($row['concl_link'] == 0) {
				echo '    <script>' . "\n";
				echo '      window.close();' . "\n";
				echo '      document.location.href=\'http://www.1ka.si/\';' . "\n";
				echo '    </script>' . "\n";
            } 
            // Rekurzivno
			else if ($row['concl_link'] == 2) {
				header("Location: " . SurveyInfo::getSurveyLink());
            } 
            else {
				header("Location: $finishUrl");
			}

        } 
        else {

			SurveySetting::getInstance()->Init(get('anketa'));
			$hide_mobile_img = SurveySetting::getInstance()->getSurveyMiscSetting('hide_mobile_img');
			$class = ($hide_mobile_img == '1') ? 'hide_mobile_img' : '';
			if (isset($_GET['mobile']) && $_GET['mobile'] == 1)
				$class .= ' mobile_preview';
			elseif (isset($_GET['mobile']) && $_GET['mobile'] == 2)
				$class .= ' tablet_preview';

            echo '<div class="outercontainer_holder ' . $class . '">';
            echo '<div class="outercontainer_holder_top"></div>';

			echo '<div id="outercontainer" class="' . $class . '">' . "\n";
			echo '<div class="outercontainer_header"></div>';
			
			echo '<div id="container">' . "\n";

			Display::getInstance()->logo();

			Display::getInstance()->progress_bar();

			echo '<h1 ' . (SurveyInfo::getInstance()->checkSurveyModule('uporabnost') ? ' class="evalvacija"' : '') . '>' . Helper::getInstance()->displayAkronim() . '</h1>' . "\n";

			// Vsebina zakljucka ce je kvota polna oz je presezen datum
			echo '<div class="grupa">' . "\n";
			echo '  <div class="spremenljivka">' . "\n";

            echo '    <div class="naslov"><p>';
            if($date_to != '')
				echo self::$lang['srv_evoli_survey_expired'].' '.$date_to.' '.self::$lang['srv_evoli_survey_end_help'];
			else
				echo self::$lang['srv_evoli_survey_quota_full'].' '.self::$lang['srv_evoli_survey_end_help'];			
            echo '    </p></div>';

			echo '  </div>' . "\n";
			echo '</div>' . "\n"; //-grupa


			if ($row['user_from_cms'] == 2) {
				echo '<p style="text-align:center"><a href="' . SurveyInfo::getSurveyLink() . '">' . $lang['srv_nextins'] . '</a></p>';
			}

			SurveySetting::getInstance()->Init(get('anketa'));
			if (get('lang_id') != null) $_lang = '_' . get('lang_id'); else $_lang = '';
			if (!get('printPreview')) {
				$srv_prevpage = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_prevpage' . $_lang);
				if ($srv_prevpage == '') $srv_prevpage = $lang['srv_prevpage'];

				$display_backlink = SurveySetting::getInstance()->getSurveyMiscSetting('display_backlink');

				echo '<div class="buttons">';
				// prejsna stran, prikazemo ce ni v zakjucku drugace nastavljeno
				/*if ($row['concl_back_button'] != '0')
					if ($display_backlink != '0') {
						echo '<input class="prev" type="button" value="' . $srv_prevpage . '" onclick="javascript:history.go(-1)">' . "\n";
					}*/
			}
			if ($finishUrl != '') {
				if ($row['concl_link'] == 1) {
					if (!SurveyInfo::getInstance()->checkSurveyModule('uporabnost'))
						$js = 'document.location.href=\'' . $finishUrl . '\';';
					else
						$js = 'top.location.href=\'' . $finishUrl . '\';';
				} elseif ($row['concl_link'] == 2) {
					if (!SurveyInfo::getInstance()->checkSurveyModule('uporabnost'))
						$js = 'document.location.href=\'' . SurveyInfo::getSurveyLink() . '\';';
					else
						$js = 'top.location.href=\'' . SurveyInfo::getSurveyLink() . '\';';
				} elseif (!SurveyInfo::getInstance()->checkSurveyModule('uporabnost')) {
					if (Common::checkModule('evalvacija') == '1')
						$js = 'window.close(); document.location.href=\'https://www.uni-lj.si/\';';
					else
						$js = 'window.close(); document.location.href=\'https://www.1ka.si/\';';
				} // uporabnost
				else
					$js = 'top.close();';

				if (isset($_GET['popup']) && $_GET['popup'] == '1')
					$js = '$(\'#popup_div, #popup_iframe\', window.parent.document).hide(); ';


				if (!get('printPreview')) {
					$srv_konec = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_konec' . $_lang);

					if ($srv_konec != '')
						$text = $srv_konec;
					else
						$text = $lang['srv_konec'];

					if ($row['concl_end_button'] == 1)
						echo '<input class="next" type="submit" value="' . $text . '" onclick="' . $js . ' return false;">' . "\n";
				}
			}

			echo '</div>';

			if ($row['user_from_cms'] == 2 && $row['user_from_cms_email'] == 1 && $admin_type <= 2) { // vnosos
				$sqlg = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='" . get('anketa') . "'ORDER BY vrstni_red ASC LIMIT 1");
				$rowg = mysqli_fetch_array($sqlg);
				//echo '<p class="vnos"><a href="'.$site_url.'a/'.$this->anketa.'&grupa='.$rowg['id'].'">'.$lang['srv_nextins'].'</a> | <a href="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'">'.$lang['srv_insend'].'</a></p>';
				echo '<p class="vnos"><a href="' . SurveyInfo::getSurveyLink() . '&grupa=' . $rowg['id'] . '">' . $lang['srv_nextins'] . '</a> | <a href="' . $site_url . 'admin/survey/index.php?anketa=' . get('anketa') . '">' . $lang['srv_insend'] . '</a></p>';
			}

            echo '</div>' . "\n"; // -container
            
            $this->displayFooterNote();
            
            echo '</div>' . "\n"; // -outercontainer
            echo '<div class="outercontainer_holder_bottom"></div></div>'; // -outercontainer_holder
		}
    }
	

    /**
     * @desc prikaze trenutno anketo
     */
    public function displayAnketa()
    {
        $row = SurveyInfo::getInstance()->getSurveyRow();

        if (!get('printPreview')) {

			// crn div za ozadje popupov
			echo '<div id="fade"></div>';
			echo '<div id="popup"></div>';
		
            SurveySetting::getInstance()->Init(get('anketa'));
            $hide_mobile_img = SurveySetting::getInstance()->getSurveyMiscSetting('hide_mobile_img');
            $class = ($hide_mobile_img == '1') ? 'hide_mobile_img' : '';
            if (isset($_GET['mobile']) && $_GET['mobile'] == 1)
                $class .= ' mobile_preview';
            elseif (isset($_GET['mobile']) && $_GET['mobile'] == 2)
                $class .= ' tablet_preview';


            echo '<div class="outercontainer_holder ' . $class . '">';
            echo '<div class="outercontainer_holder_top"></div>';

            echo '<div id="outercontainer" class="' . $class . '">';
			echo '<div class="outercontainer_header"></div>';
			
            echo '<div id="container">' . "\n";

            $d = new Display();
            $d->logo();
            $d->progress_bar();

            echo '<h1 ' . (SurveyInfo::getInstance()->checkSurveyModule('uporabnost') ? ' class="evalvacija"' : '') . '>' . Helper::getInstance()->displayAkronim() . '</h1>' . "\n";

            if (get('quick_view') == true) {
                echo '<div id="edit_warning">';
                echo self::$lang['srv_quick_view_text'];
                echo '</div>';
            }

            //opozorilo ce urejamo ze reseno anketo
            if (isset($_GET['urejanje'])) {
                echo '<div id="edit_warning">';
                echo self::$lang['srv_edit_text'];
                echo '</div>';

				// Preverimo ce gre za prvo popravljanje podatkov in avtomatsko ustvarimo arhiv podatkov ce je potrebno
				//ob_flush();	// ZAKAJ JE TUKAJ TA FLUSH? KER POTEM NE DELA NAKNADNO UREJANJE CE IMA ANKETA LOOPE
				$sas = new SurveyAdminSettings();
				$sas->checkFirstDataChange();

				// Updatamo tracking (ker gre za editiranje odgovorov)
                TrackingClass::update(get('anketa'), 4);
            }

            $this->displayGrupa();

            echo '</div>' . "\n"; // - container

            $this->displayFooterNote();

			// TAWK chat, ce je vklopljen
			if(SurveyInfo::getInstance()->checkSurveyModule('chat')){
				Display::getInstance()->displayChatTAWK();
			}

            echo '</div>'; // - outercontainer

            echo '<div class="outercontainer_holder_bottom"></div>';
            echo '</div>'; // -outercontainer_holder

            Js::js_tracking();

		} else {

            echo '<div id="container">' . "\n";
            $this->displayGrupa();
            echo '</div>' . "\n";
        }
    }

    public function displayAllPages()
    {
        $row = SurveyInfo::getInstance()->getSurveyRow();

        echo '<div class="outercontainer_holder">';
        echo '<div class="outercontainer_holder_top"></div>';

        echo '<div id="outercontainer">';
		echo '<div class="outercontainer_header"></div>';
		
        echo '<div id="container">' . "\n";

        Display::getInstance()->logo();
        Display::getInstance()->progress_bar();

        if (!get('printPreview')) {
            echo '<h1 ' . (SurveyInfo::getInstance()->checkSurveyModule('uporabnost') ? ' class="evalvacija"' : '') . '>' . Helper::getInstance()->displayAkronim() . '</h1>' . "\n";
        }

        echo '<div class="grupa">';
        $this->displayIntroductionContent();
        echo '</div>';

        $this->displayGrupa();

        echo '<div class="grupa">';
        $this->displayKonecContent();
        echo '</div>';

        echo '</div>' . "\n"; // - container

        echo '</div>' . "\n"; // - outercontainer

        echo '<div class="outercontainer_holder_bottom"></div>';
        echo '</div>'; // -outercontainer_holder

        $this->displayFooterNote();
    }

    /**
     * @desc prikaze trenutno grupo
     */
    public function displayGrupa(){
        global $admin_type;


		// Ce imamo vklopljene napredne parapodatke zabelezimo id-strani
        if(SurveyAdvancedParadataLog::getInstance()->paradataEnabled()){
        	SurveyAdvancedParadataLog::getInstance()->displayGrupa(get('grupa'));
		}

        $sql = sisplet_query("SELECT naslov, vrstni_red FROM srv_grupa WHERE id = '" . get('grupa') . "'");
        $row = mysqli_fetch_array($sql);
        if (!get('printPreview')) {

            Display::getInstance()->display_tabs();

            echo '<form name="vnos" method="post" action="' . SurveyInfo::getSurveyLink(false, false) . '&amp;grupa=' . get('grupa') . (get('loop_id') != null ? '&amp;loop_id=' . get('loop_id') : '') . Header::getSurveyParams(true) . str_replace('&', '&amp;', get('cookie_url')) . '" enctype="multipart/form-data">' . "\n";
            echo '<div class="grupa">' . "\n";
            echo '  <input type="hidden" name="dummy" value="foo">' . "\n";
            
            SurveySetting::getInstance()->Init(get('anketa'));
            $rowa = SurveyInfo::getInstance()->getSurveyRow();

            // izbira jezika za respondenta - Ce nimamo prikazanega uvoda, prikazemo izbiro na prvi strani
            if (SurveySetting::getInstance()->getSurveyMiscSetting('resp_change_lang') == 1 && $rowa['multilang'] == 1 && $rowa['show_intro'] == 0 && $row['vrstni_red'] == 1) {

                $lang_resp = $rowa['lang_resp'];
                $lang_array = array();

                $current = self::$lang['id'];
                $file = lang_path($lang_resp);
                include($file);

                $lang_array[$lang['id']] = $lang['language'];

                $file = lang_path($current);
                include($file);

                $sqll = sisplet_query("SELECT lang_id, language FROM srv_language WHERE ank_id='" . get('anketa') . "' ORDER BY language");
                while ($rowl = mysqli_fetch_array($sqll)) {
                    $lang_array[$rowl['lang_id']] = $rowl['language'];
                }

                echo '  <div class="spremenljivka lang_pick">' . "\n";

                // Izbira jezika z dropdown menijem
                if (SurveySetting::getInstance()->getSurveyMiscSetting('resp_change_lang_type') == 1) {
                    echo '    <h3>' . self::$lang['lang'] . ': ';
                    echo '    <select 
                                name="language" 
                                id="language" 
                                onchange="window.location.href = 
                                    \'' . SurveyInfo::getSurveyLink() . '?language=\'+document.getElementById(\'language\').value+\'' . 
                                    '&survey-'.get('anketa').'=' . get('cookie_url') . '\'+\'' .  
                                    (isset($_GET['skupina']) ? '&skupina=' . $_GET['skupina'] : '') . '\'+\'' . 
                                    (isset($_GET['preview']) ? '&preview=' . $_GET['preview'] : '') . '\'+\'' . 
									(isset($_GET['testdata']) ? '&testdata=' . $_GET['testdata'] : '') . '\'+\'' . 
                                    (isset($_GET['code']) ? '&code=' . $_GET['code'] : '') . '\'+\'' . 
                                    (isset($_GET['params']) ? '&params=' . $_GET['params'] : '') . '\'+\'' . 
                                    (isset($_GET['usr_id']) ? '&usr_id=' . $_GET['usr_id'] : '') . '\'+\'' . 
                                    (isset($_GET['status']) ? '&status=' . $_GET['status'] : '') . '\'+\'' . 
                                    (isset($_GET['recnum']) ? '&recnum=' . $_GET['recnum'] : '') . '\'+\'' . 
                                    (isset($_GET['enc']) ? '&enc=' . urlencode($_GET['enc']) : '') . '\'; 
                                return false;">';
                   
                    foreach ($lang_array AS $key => $val) {
                        echo '<option value="' . $key . '"' . ($key == $current ? ' selected' : '') . '>' . $val . '</option>';
                    }

                    echo '    </select>';
                    echo '</h3>' . "\n";
                } 
                // Izbira jezika z radio gumbi (default)
                else {
                    echo '<h3>' . self::$lang['lang'] . ': </h3>';
                    echo '<span style="font-size:13px;">';
                    
                    foreach ($lang_array AS $key => $val) {
                        echo '<label for="language_' . $key . '">';
						echo '<input type="radio" name="language" id="language_' . $key . '" 
								value="' . $key . '"' . ($key == $current ? ' 
								checked="checked"' : '') . ' 
								onchange="window.location.href = 
                                    \'' . SurveyInfo::getSurveyLink() . '?language=\'+document.getElementById(\'language_' . $key . '\').value+\'' . 
                                    '&survey-'.get('anketa').'=' . get('cookie_url') . '\'+\'' .
									(isset($_GET['skupina']) ? '&skupina=' . $_GET['skupina'] : '') . '\'+\'' . 
									(isset($_GET['preview']) ? '&preview=' . $_GET['preview'] : '') . '\'+\'' . 
									(isset($_GET['testdata']) ? '&testdata=' . $_GET['testdata'] : '') . '\'+\'' . 
									(isset($_GET['code']) ? '&code=' . $_GET['code'] : '') . '\'+\'' . 
                                    (isset($_GET['params']) ? '&params=' . $_GET['params'] : '') . '\'+\'' . 
                                    (isset($_GET['usr_id']) ? '&usr_id=' . $_GET['usr_id'] : '') . '\'+\'' . 
                                    (isset($_GET['status']) ? '&status=' . $_GET['status'] : '') . '\'+\'' . 
                                    (isset($_GET['recnum']) ? '&recnum=' . $_GET['recnum'] : '') . '\'+\'' . 
									(isset($_GET['enc']) ? '&enc=' . urlencode($_GET['enc']) : '') . '\'; 
								return false;"> ';

						// Font awesome checkbox
						echo '<span class="enka-checkbox-radio" '.((Helper::getCustomCheckbox() != 0) ? 'style="font-size:' . Helper::getCustomCheckbox() . 'px;"' : '').'></span>';

						echo $val;

						echo '</label><br />';
                    }
                    echo '</span>';
                }

                echo '  </div>' . "\n";
            }
			
            Vprasanja::getInstance()->displaySpremenljivke();

            echo '</div>' . "\n";  // - grupa

            Js::getInstance()->generateSubmitJS();

            echo '</form>' . "\n";

            Js::getInstance()->generateBranchingJS();
            
            // Disable odgovorov, ce je ze izpolnjeval to stran in ima vklopljeno nastavitev, da ni dovoljeno naknadno vnasanje odogovorov
            if($rowa['subsequent_answers'] == '0' && $admin_type != '0' && $admin_type != '1'){

                // Preverimo, ce je uporabnik ze bil na strani
                if(Check::getInstance()->check_subsequent_visit()){
                    echo '<script>disableSubsequentAnswers();</script>';
                }
            }

            // nov zgornji link pri uporabnosti
            $link = SurveySetting::getInstance()->getSurveyMiscSetting('uporabnost_link_' . get('grupa'));
            if ($link != '' && strlen($link) > 7)
                echo "<script> top.frames['link'].location.href = '" . $link . "'; </script>";
        } 
		else{
            Display::getInstance()->displaySpremenljivke();
		}
    }


    public function displayFooterNote(){
        global $mysql_database_name, $app_settings;

        $row = SurveyInfo::getInstance()->getSurveyRow();

        echo '<div id="footer_survey">';
		
		// Pri mobilnem skinu (in mju skinu pri tablici) prikazemo na dnu se logo
        if (get('mobile') == 1 || (get('mobile') == 2 && SurveyInfo::getInstance()->checkSurveyModule('mju_theme') == '1')) {	
			Display::getInstance()->logo();
        }

        // Custom footer
        if(isset($app_settings['footer_survey_custom']) && $app_settings['footer_survey_custom'] == 1){

            echo '<p>'.$app_settings['footer_survey_text'].'</p>';
        }
        // Default footer
        else{

            $link = (self::$lang['id'] == '1') ? 'https://www.1ka.si' : 'https://www.1ka.si/d/en';
            $link_cookie = (self::$lang['id'] == '1') ? 'https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-1ka/politika-piskotkov' : 'https://www.1ka.si/d/en/about/terms-of-use/cookie-policy';
            $link_privacy = (self::$lang['id'] == '1') ? 'https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-storitve-1ka/politika-zasebnosti' : 'https://www.1ka.si/d/en/about/terms-of-use/privacy-policy';
            $link_paradata = (self::$lang['id'] == '1') ? 'https://www.1ka.si/d/sl/o-1ka/pogoji-uporabe-1ka/izklop-parapodatkov' : 'https://www.1ka.si/d/en/about/terms-of-use/turn-off-paradata';

            echo '<p class="footer_survey_UL">Univerza <i>v Ljubljani</i></p>';
            echo '<p class="footer_survey_FDV"><i>Fakulteta za dru&#382;bene vede</i></p>';		
            echo '<p class="footer_survey_PoweredBy"><a href="'.$link.'" target="_blank">Powered by: 1KA</a></p>';
            echo '<p class="footer_1ka"><a href="'.$link.'" target="_blank">1KA - ' . self::$lang['srv_footer_1ka'] . '</a></p>';

            echo '<p class="privacy">';

            $survey_ip = SurveySetting::getInstance()->getSurveyMiscSetting('survey_ip');
            $ip_show = SurveySetting::getInstance()->getSurveyMiscSetting('survey_show_ip');
            $survey_browser = SurveySetting::getInstance()->getSurveyMiscSetting('survey_browser');
            $survey_referal = SurveySetting::getInstance()->getSurveyMiscSetting('survey_referal');
            $survey_date = SurveySetting::getInstance()->getSurveyMiscSetting('survey_date');


            $privacy_text = '';

            // Ce imamo vse izklopljeno je anketa brez parapodatkov
            if(($survey_ip == 1 || $ip_show == 0) && $survey_browser == 1 && $survey_referal == 1 && $survey_date == 1){

                $privacy_text = '<a href="'.$link_paradata.'" target="_blank">' . self::$lang['srv_no_paradata'] . '</a>';
                
                echo $privacy_text;
            }
            else{
                echo '<a href="'.$link.'" target="_blank">' . self::$lang['srv_no_anketa'] . ' </a>';

                // Piskotki
                if ($row['cookie'] == -1)
                    $privacy_text .= '<a href="'.$link_cookie.'" target="_blank">' . self::$lang['srv_no_cookie'] . '</a>, ';

                // IP sledenje
                if ($survey_ip == 1)
                    $privacy_text .= '<a href="'.$link_paradata.'" target="_blank">' . self::$lang['srv_no_ip'] . '</a>, ';

                // Email povezovanje
                if ($row['user_base'] == 1 && $row['show_email'] == 0)
                    $privacy_text .= '<a href="'.$link_paradata.'" target="_blank">' . self::$lang['srv_no_email'] . '</a>, ';

                echo substr($privacy_text, 0, -2);
            }

            echo '</p>';

            echo '<p class="privacy_link">';
            echo '<a href="' . $link_privacy . '" target="_blank">' . self::$lang['srv_privacy_policy'] . '</a>';
            echo '</p>';
        }

        echo '</div>';


        // OKNO Z NASTAVITVAMI ZA PREVIEW / TEST
        if (isset($_GET['preview']) && $_GET['preview'] == 'on' && $_GET['no_preview'] != '1') {

            echo '<div id="preview-window">';


            // Gump za odprtje/zaprtje okna
            echo '<div id="preview_open" '.($_GET['testdata'] == 'on' ? ' class="testdata"' : '').'>';
            
            echo '<a href="#" onclick="preview_popup_open(); return false;" class="preview_icon_open" style="display:none;"><span class="faicon dropdown_blue"></span></a>';
            echo '<a href="#" onclick="preview_popup_close(); return false;" class="preview_icon_close"><span class="faicon dropup_blue"></span></a>';

            echo '</div>';


            // Preklop na celostranski preview
            echo '<div id="preview_switch">';

            echo '<span class="bold">'.self::$lang['srv_mode'] . ':</span>';
			echo '<span class="'.(get('displayAllPages') ? 'nonactive' : 'active').'"><a href="' . SurveyInfo::getSurveyLink() . Header::getSurveyParams(false, null, array('pages')) . '" title="' . self::$lang['srv_normal_x'] . '">' . self::$lang['srv_normal'] . '</a></span>';
			echo '<span class="'.(get('displayAllPages') ? 'active' : 'nonactive').'"><a href="' . SurveyInfo::getSurveyLink() . Header::getSurveyParams(false, array('pages' => 'all', 'mobile' => '0')) . '" title="' . self::$lang['srv_scrolling_x'] . '">' . self::$lang['srv_scrolling'] . '</a></span>';	
            
            echo '</div>';            


            // Vsebina - preview-holder
            echo '<div id="preview-holder">';

            echo '<div id="preview" class="' . ($_GET['testdata'] == 'on' ? 'testdata' : '') . '">';

            if ($_GET['testdata'] == 'on')
                echo '<p>' . self::$lang['srv_testalert'] . '</p>';
            else
                echo '<p>' . self::$lang['srv_previewalert'] . '</p>';

            if ($row['multilang'] == 1) {
                $lang_resp = $row['lang_resp'];
                $lang_array = array();

                $current = self::$lang['id'];
                $file = lang_path($lang_resp);
                include($file);

                $lang_array[$lang['id']] = $lang['language'];
                $file = lang_path($current);
                include($file);

                $sqll = sisplet_query("SELECT lang_id, language FROM srv_language WHERE ank_id='" . get('anketa') . "' ORDER BY language");
                while ($rowl = mysqli_fetch_array($sqll)) {
                    $lang_array[$rowl['lang_id']] = $rowl['language'];
                }

                // FORM s preklopom jezika
                echo '<form name="change_lang" action="" method="get">';

                if (isset($_GET['code']))
                    echo '<input type="hidden" name="code" value="' . $_GET['code'] . '">';

                if (isset($_GET['preview']))
                    echo '<input type="hidden" name="preview" value="' . $_GET['preview'] . '">';

                if (isset($_GET['disableif']))
                    echo '<input type="hidden" name="disableif" value="' . $_GET['disableif'] . '">';

                if (isset($_GET['displayifs']))
                    echo '<input type="hidden" name="displayifs" value="' . $_GET['displayifs'] . '">';

                if (isset($_GET['displayvariables']))
                    echo '<input type="hidden" name="displayvariables" value="' . $_GET['displayvariables'] . '">';

                if (isset($_GET['disablealert']))
                    echo '<input type="hidden" name="disablealert" value="' . $_GET['disablealert'] . '">';

                if (isset($_GET['disablecode']))
                    echo '<input type="hidden" name="disablecode" value="' . $_GET['disablecode'] . '">';

                if ($row['user_base'] == 1 && $row['individual_invitation'] != 0 && $row['usercode_skip'] == 0)
                    echo '<input type="hidden" name="disablecode" value="1">';

                if (isset($_GET['grupa']))
                    echo '<input type="hidden" name="grupa" value="' . $_GET['grupa'] . '">';


                echo '<p>' . self::$lang['lang'] . ': ';

                echo '    <select name="language" onchange="document.change_lang.submit();">';
                foreach ($lang_array AS $key => $val) {
                    echo '<option value="' . $key . '"' . ($key == $current ? ' selected' : '') . '>' . $val . '</option>';
                }
                echo '    </select></p>' . "\n";

                echo '</form>';
            }


            // Preklop med preview-ji za mobilnik, tablico in pc
            if(!get('displayAllPages')){

                echo '<span id="preview_device"><span>' . self::$lang['srv_para_graph_device'] . ': </span>';
                
                if (isset($_GET['mobile']) && $_GET['mobile'] == '1')
                    $device_value = 1;
                elseif (isset($_GET['mobile']) && $_GET['mobile'] == '2')
                    $device_value = 2;
                else
                    $device_value = 0;

                echo '<a href="#" title="PC" onClick="$(\'#device_value\').val(\'0\'); document.change_diable.submit();"><span class="faicon monitor ' . ($device_value == 0 ? ' active' : '') . '"></span></a>';
                
                // Ce imamo izklopljene mobilne prilagoditve ne pustimo preview-ja na mobile, ker itak ne prikaze scalano
                $mobile_friendly = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_friendly');
                if($mobile_friendly != '0'){
                    echo '<a href="#" title="' . self::$lang['srv_preview_tablet'] . '" onClick="$(\'#device_value\').val(\'2\'); document.change_diable.submit();"><span class="faicon tablet ' . ($device_value == 2 ? ' active' : '') . '"></span></a>';
                    echo '<a href="#" title="' . self::$lang['srv_preview_mobile'] . '" onClick="$(\'#device_value\').val(\'1\'); document.change_diable.submit();"><span class="faicon mobile ' . ($device_value == 1 ? ' active' : '') . '"></span></a>';
                }

                echo '</span>';
            }


            // FORM z dropdownom za preklop med stranmi
            echo '<form name="go_to_page" action="#" method="get">';

            if (isset($_GET['code']))
                echo '<input type="hidden" name="code" value="' . $_GET['code'] . '">';

            if (isset($_GET['preview']))
                echo '<input type="hidden" name="preview" value="' . $_GET['preview'] . '">';

            if (isset($_GET['mobile']))
                echo '<input type="hidden" name="mobile" value="' . $_GET['mobile'] . '">';

            echo '<input type="hidden" name="disableif" value="1">';

            if (isset($_GET['displayifs']))
                echo '<input type="hidden" name="displayifs" value="' . $_GET['displayifs'] . '">';

            if (isset($_GET['displayvariables']))
                echo '<input type="hidden" name="displayvariables" value="' . $_GET['displayvariables'] . '">';

            if (isset($_GET['disablealert']))
                echo '<input type="hidden" name="disablealert" value="' . $_GET['disablealert'] . '">';

            if (isset($_GET['disablecode']))
                echo '<input type="hidden" name="disablecode" value="' . $_GET['disablecode'] . '">';

            if ($row['user_base'] == 1 && $row['individual_invitation'] != 0 && $row['usercode_skip'] == 0)
                echo '<input type="hidden" name="disablecode" value="1">';

            if ($row['multilang'] == 1 && isset($_GET['language']))
                echo '<input type="hidden" name="language" value="' . $_GET['language'] . '">';


            // Skok na doloceno stran - samo ce nimamo prikaza vse na eni strani
            if(!get('displayAllPages')){    
                echo '<p>' . self::$lang['srv_jump_to'] . ': <select name="grupa" onchange="document.go_to_page.submit();">';
                
                $sql1 = sisplet_query("SELECT id, naslov, vrstni_red FROM srv_grupa WHERE ank_id = '" . get('anketa') . "' ORDER BY vrstni_red ASC");
                while ($row1 = mysqli_fetch_array($sql1)) {
                    echo '<option value="' . $row1['id'] . '" ' . ($row1['id'] == get('grupa') ? 'selected' : '') . '>' . self::$lang['srv_stran'] . ' ' . $row1['vrstni_red'] . '</option>';

                    $sql2 = sisplet_query("SELECT * FROM srv_spremenljivka WHERE gru_id='$row1[id]' ORDER BY vrstni_red ASC");
                    while ($row2 = mysqli_fetch_array($sql2)) {

                        if ($row['multilang'] == 1) {
                            $sqlLang = sisplet_query("SELECT * FROM srv_language_spremenljivka WHERE ank_id='" . get('anketa') . "' AND spr_id='" . $row2['id'] . "' AND lang_id='" . get('lang_id') . "'");
                            if (mysqli_num_rows($sqlLang) > 0) {
                                $rowLang = mysqli_fetch_array($sqlLang);
                                $row2['naslov'] = $rowLang['naslov'];
                            }
                        }

                        echo '<option value="' . $row1['id'] . '"> - (' . $row2['variable'] . ') ' . substr($row2['naslov'], 0, 100) . '</option>';
                    }
                }
                echo '</select></p>';
            }

            echo '</form>';


            // FORM s checkbox nastavitvami
            $link = SurveyInfo::getSurveyLink(get('anketa')) . '' . Header::getSurveyParams(false, null, array('disableif', 'disablealert', 'disablecode', 'displayifs', 'displayvariables', 'hidecomment', 'mobile')) . '&survey-' . get('anketa') . '=' . $_GET['survey-' . get('anketa') . ''] . '&grupa=' . $_GET['grupa'];
            echo '<form name="change_diable" action="' . $link . '" method="get">';

            echo '<input type="hidden" name="mobile" id="device_value" value="' . $device_value . '">';

            if ($row['multilang'] == 1 && isset($_GET['language']))
                echo '<input type="hidden" name="language" value="' . $_GET['language'] . '">';

            if (isset($_GET['code']))
                echo '<input type="hidden" name="code" value="' . $_GET['code'] . '">';

            echo '<p><label for="disableif"><input type="checkbox" value="1" ' . ($_GET['disableif'] == '1' ? ' checked' : '') . ' name="disableif" id="disableif" onchange="document.change_diable.submit();"><span class="enka-checkbox-radio"></span>';
            echo ' ' . self::$lang['srv_disableif'] . '</label></p>';

            echo '<p><label for="disablealert"><input type="checkbox" value="1" ' . ($_GET['disablealert'] == '1' ? ' checked' : '') . ' name="disablealert" id="disablealert" onchange="document.change_diable.submit();"><span class="enka-checkbox-radio"></span>';
            echo ' ' . self::$lang['srv_disablealert'] . '</label></p>';

            // Pogoj za izlapljanje kode ce imamo email vabila z zahtevano kodo
            if ($row['user_base'] == 1 && $row['individual_invitation'] != 0 && $row['usercode_skip'] == 0) {
                echo '<p><label for="disablecode"><input type="checkbox" value="1" ' . ($_GET['disablecode'] == '1' ? ' checked' : '') . ' name="disablecode" id="disablecode" onchange="document.change_diable.submit();"><span class="enka-checkbox-radio"></span>';
                echo ' ' . self::$lang['srv_disablecode'] . '</label></p>';
            }

            echo '<p><label for="displayifs"><input type="checkbox" value="1" ' . ($_GET['displayifs'] == '1' ? ' checked' : '') . ' name="displayifs" id="displayifs" onchange="document.change_diable.submit();"><span class="enka-checkbox-radio"></span>';
            echo ' ' . self::$lang['srv_displayifs'] . '</label></p>';

            echo '<p><label for="displayvariables"><input type="checkbox" value="1" ' . ($_GET['displayvariables'] == '1' ? ' checked' : '') . ' name="displayvariables" id="displayvariables" onchange="document.change_diable.submit();"><span class="enka-checkbox-radio"></span>';
            echo ' ' . self::$lang['srv_displayvariables'] . '</label></p>';


            // Komentarji - vidno samo ce imamo ustrezen paket
            $userAccess = UserAccess::getInstance(self::$global_user_id);
            if($userAccess->checkUserAccess($what='komentarji')){

                $question_resp_comment = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment');
                $preview_disable_test_insert = SurveySetting::getInstance()->getSurveyMiscSetting('preview_disable_test_insert');

                if ($_GET['testdata'] == 'on' || $preview_disable_test_insert == 0) {
                    
                    if ($question_resp_comment == 0) {
                        echo '<p><a href="#" onclick="$.post(\'' . self::$site_url . '/main/survey/ajax.php?a=enable_comments\', {anketa: ' . get('anketa') . '}, function () { window.location = window.location + \'&testdata=on\' }); return false;">';
                    
                        if ($_GET['testdata'] == 'on')
                            echo self::$lang['srv_preview_comments'];
                        else
                            echo self::$lang['srv_savedatapreview'];
                        
                        echo '</a></p>';
                    } 
                    else {
                        if ($_GET['testdata'] == 'on') {
                            echo '<p><label for="hidecomment"><input type="checkbox" value="1" ' . ($_GET['hidecomment'] == '1' ? ' checked' : '') . ' name="hidecomment" id="hidecomment" onchange="document.change_diable.submit();"><span class="enka-checkbox-radio"></span>';
                            echo ' ' . self::$lang['srv_preview_comments2'] . '</label></p>';
                        } else {
                            echo '<p><a href="#" onclick="$.post(\'' . self::$site_url . '/main/survey/ajax.php?a=enable_comments\', {anketa: ' . get('anketa') . '}, function () { window.location = window.location + \'&testdata=on\' }); return false;">';
                            echo self::$lang['srv_savedatapreview'];
                            echo '</a></p>';
                        }
                    }
                }
            }

            

            echo '</form>';

            echo '</div>' . "\n"; // preview


            // Komentarji - vidno samo ce imamo ustrezen paket
            if($userAccess->checkUserAccess($what='komentarji')){

                if ($question_resp_comment == 1 && $_GET['testdata'] == 'on') {
                    $question_resp_comment_inicialke = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_inicialke');

                    if ($question_resp_comment_inicialke == 1) {
                        echo '<p class="inicialke">' . self::$lang['srv_inicialke'] . ': <input type="text" value="" size="3" id="inicialke"></p>';
                        echo '<script> inicialke(); </script>';
                    }
                }

                $sqlUserAutor = sisplet_query("SELECT ank_id, uid FROM srv_dostop WHERE ank_id='" . get('anketa') . "' AND uid='" . self::$global_user_id . "'");
                (mysqli_num_rows($sqlUserAutor) > 0) ? save('userAutor', true) : save('userAutor', false);
                // Komentarji urednikov na anketo
                if (get('userAutor')) {

                    echo '<div style="margin: 20px 0 15px; width: 100%;">';

                    $view = 1;
                    $survey_comment = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment');
                    $preview_hide_survey_comment = SurveySetting::getInstance()->getSurveyMiscSetting('preview_hide_survey_comment');
                    if ((self::$admin_type <= $survey_comment && $survey_comment != '') && (/*$preview_hide_survey_comment != 1 ||*/
                            $_GET['testdata'] == 'on')
                    ) {

                        echo '<span class="stngs floatLeft">';

                        $show_survey_comment = $_GET['show_survey_comment'];
                        if ($row['forum'] == 0 || $row['thread'] == 0) {
                            echo '<a href="#" onclick="return false;" class="surveycomment bold" id="surveycomment_0_' . $view . '" type="0" view="' . $view . '" spremenljivka="0">';
                            echo self::$lang['srv_comment_splosen'] . ' ';
                            echo '<span class="faicon comment" title="' . self::$lang['srv_survey_comment'] . '" alt="' . self::$lang['srv_survey_comment'] . '"></span>';
                            echo '</a>';
                        } else {

                            $sqlf = sisplet_query("SELECT COUNT(*) AS count FROM post WHERE tid='$row[thread]'");
                            $rowf = mysqli_fetch_array($sqlf);
                            $rowf['count']--; //zaradi 1. avtomatskega posta

                            echo '<a href="#" onclick="return false;" class="surveycomment bold" id="surveycomment_0_' . $view . '" type="0" view="' . $view . '" spremenljivka="0">';
                            echo self::$lang['srv_comment_splosen'] . ' ';
                            echo '<span class="faicon comment" title="' . self::$lang['srv_survey_comment'] . '" alt="' . self::$lang['srv_survey_comment'] . '"></span>';
                        
                            // ali prikazemo okno odprto - je dodan tak admin komentar
                            $sqlf1 = sisplet_query("SELECT * FROM post p WHERE p.tid='$row[thread]' AND p.ocena='5'");
                            while ($rowf1 = mysqli_fetch_array($sqlf1)) {
                                $s = sisplet_query("SELECT * FROM views WHERE pid='$rowf1[id]' AND uid='self::$global_user_id'");
                                if (mysqli_num_rows($s) == 0)
                                    $show_survey_comment = 1;
                            }
                            echo '</a>';
                        }

                        echo '<script type="text/javascript" src="' . self::$site_url . '/admin/survey/script/js-lang.php?lang='.self::$lang['lang_short'].'&amp;v=13.06.07"></script>';
                        // Ce je tole vklopljeno se js porusi?
                        //echo '<script type="text/javascript" src="'.self::$site_url.'/admin/survey/minify/g=jsnew?v=13.06.07"></script>';
                        echo '<script type="text/javascript" src="' . self::$site_url . '/admin/survey/script/jquery/jquery.qtip-1.0.js"></script>';
                        echo '<script type="text/javascript" src="' . self::$site_url . '/admin/survey/script/script.js"></script>';
                        echo '<script>  $(function() {  load_comment(\'#surveycomment_0_' . $view . '\'' . ($show_survey_comment == '1' ? ', \'2\'' : '') . ');  });</script>';

                        echo '</span>';
                    }

                    // Uredniske nastavitve link
                    echo '<span class="floatRight bold"><a href="' . self::$site_url . '/admin/survey/index.php?anketa=' . get('anketa') . '&a=testiranje&popup=open" target="_blank" title="' . self::$lang['srv_uredniske_nastavitve'] . '">' . self::$lang['settings'] . ' <span class="faicon settings"></span></a></span>';

                    echo '</div>';
                } 
                // Komentarji respondentov na anketo
                elseif (self::$global_user_id > 0 || true) {

                    echo '<p class="stngs">';

                    $view = 1;
                    $survey_comment_resp = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_resp');
                    $preview_hide_survey_comment = SurveySetting::getInstance()->getSurveyMiscSetting('preview_hide_survey_comment');
                    if (((self::$admin_type <= $survey_comment_resp && self::$admin_type != -1 && $survey_comment_resp != '') || $survey_comment_resp == 4) && ($preview_hide_survey_comment != 1 || $_GET['testdata'] == 'on')) {
                        
                        $show_survey_comment = $_GET['show_survey_comment'];
                        
                        if ($row['forum'] == 0 || $row['thread_resp'] == 0) {
                            echo '<a href="#" onclick="return false;" class="surveycomment bold" id="surveycomment_0_' . $view . '" type="4" view="' . $view . '" spremenljivka="0">';
                            echo self::$lang['srv_comment_splosen'] . ' ';
                            //echo '<span class="sprites _comments_add" title="'.self::$lang['srv_survey_comment'].'"></span>';
                            echo '<span class="faicon comment" title="' . self::$lang['srv_survey_comment'] . '" alt="' . self::$lang['srv_survey_comment'] . '"></span>';
                            echo '</a>';
                        } 
                        else {

                            $sqlf = sisplet_query("SELECT COUNT(*) AS count FROM post WHERE tid='$row[thread_resp]'");
                            $rowf = mysqli_fetch_array($sqlf);
                            $rowf['count']--; //zaradi 1. avtomatskega posta
                            echo '<a href="#" onclick="return false;" class="surveycomment bold" id="surveycomment_0_' . $view . '" type="4" view="' . $view . '" spremenljivka="0">';
                            echo self::$lang['srv_comment_splosen'] . ' ';
                            echo '<span class="faicon comment" title="' . self::$lang['srv_survey_comment'] . '" alt="' . self::$lang['srv_survey_comment'] . '"></span>';

                            $sqlf1 = sisplet_query("SELECT * FROM post p WHERE p.tid='$row[thread_resp]' AND p.ocena='5'");
                            while ($rowf1 = mysqli_fetch_array($sqlf1)) {
                                $s = sisplet_query("SELECT * FROM views WHERE pid='$rowf1[id]' AND uid='self::$global_user_id'");
                                if (mysqli_num_rows($s) == 0)
                                    $show_survey_comment = 1;
                            }
                            echo '</a>';
                        }

                        echo '<script type="text/javascript" src="' . self::$site_url . '/admin/survey/script/js-lang.php?lang='.self::$lang['lang_short'].'&amp;v=13.06.07"></script>';
                        // Ce je tole vklopljeno se js porusi?
                        //echo '<script type="text/javascript" src="'.self::$site_url.'/admin/survey/minify/g=jsnew?v=13.06.07"></script>';
                        echo '<script type="text/javascript" src="' . self::$site_url . '/admin/survey/script/jquery/jquery.qtip-1.0.js"></script>';
                        echo '<script type="text/javascript" src="' . self::$site_url . '/admin/survey/script/script.js"></script>';
                        echo '<script>  $(function() {  load_comment(\'#surveycomment_0_' . $view . '\'' . ($show_survey_comment == '1' ? ', \'2\'' : '') . ');  });</script>';
                    }

                    echo '</p>';
                }
            }

            echo '</div>' . "\n";    // preview-holder

            echo '</div>' . "\n";    // preview-window


            ?><script>
                if (localStorage.preview_popup == 1) {
                    $('#preview-holder, #preview_switch').hide();

                    $('.preview_icon_open').show();
                    $('.preview_icon_close').hide();
                    
                    $('#preview-window').addClass('closed');
                }
            </script><?


            // Komentarji - vidno samo ce imamo ustrezen paket
            if($userAccess->checkUserAccess($what='komentarji')){

                // Prikazemo okno s komentarji ce je nastavljeno da je vedno odprto
                $preview_survey_comment_showalways = SurveySetting::getInstance()->getSurveyMiscSetting('preview_survey_comment_showalways');
                if ($preview_survey_comment_showalways == 1) {

                    $view = 1;
                    $show_survey_comment = '1';//$_GET['show_survey_comment'];

                    ?><script>
                        $(function () {
                            <?echo 'load_comment(\'#surveycomment_0_' . $view . '\'' . ($show_survey_comment == '1' ? ', \'2\'' : '') . ');';?>
                        });
                    </script><?
                }
            }
            
        }


        save('cookie_expire', $row['cookie']);

        // alert za shranjevanje cookiejev
        if (get('cookie_expire') != -1 && !(isset($_COOKIE['cookie_ok']) && $_COOKIE['cookie_ok'] == 1) && !empty($row)) {

            echo '<div id="cookie_alert">';
			if($mysql_database_name == 'vprasalnikirsrssi')
				echo self::$lang['srv_cookie_ok_rsrs'];
			else
				echo self::$lang['srv_cookie_ok'];
            echo '</div>';

            ?><script>
                $(function () {
                    $('#cookie_alert').slideDown();
                });
                $('#cookie_ok').on('click', cookie_ok);
            </script><?
        }

        // alert da je testna anketa
        if ((get('cookie_expire') == -1 || (isset($_COOKIE['cookie_ok']) && $_COOKIE['cookie_ok'] == 1)) && isset($_GET['testdata']) && $_GET['testdata'] == 'on') {

            echo '<div id="test_alert">';
            echo self::$lang['srv_test_alert'];
            echo '</div>';

            ?><script>
                $(function () {
                    $('#test_alert').slideDown();
                });
            </script><?
        } 
        // alert da je predogled
        elseif ((get('cookie_expire') == -1 || (isset($_COOKIE['cookie_ok']) && $_COOKIE['cookie_ok'] == 1)) && isset($_GET['preview']) && $_GET['preview'] == 'on') {
           
            echo '<div id="test_alert">';
            echo self::$lang['srv_preview_alert'];
            echo '</div>';

            ?><script>
                $(function () {
                    $('#test_alert').slideDown();
                });
            </script><?
        }


        // Odgovori, ki so disable jih ustrezno skrijemo
        ?><script>
            $('.answer-disabled').find('td.category ').each(function () {
                $(this).find('input, textarea').attr('disabled', 'disabled');
            });
            $('div.variabla.answer-disabled').find('label input').attr('disabled', 'disabled');
            $('span.answer-disabled').on('click', function () {
                return false;
            });
        </script><?


        // Alert ce ni javascripta
        echo '<div id="javascript_alert">';
        echo self::$lang['srv_javascript_warning'];
        echo '</div>';
    }

}