<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 08.02.2016
 *****************************************/

namespace App\Controllers;

use App\Controllers\CheckController as Check;
use App\Controllers\FindController as Find;
use App\Controllers\HeaderController as Header;
use App\Controllers\HelperController as Helper;
use App\Models\Model;
use Cache;
use enkaParameters;
use Mobile_Detect;
use SurveyAdvancedParadataLog;
use SurveyInfo;
use SurveySetting;
use SurveySlideshow;
use Common;
use SurveyPanel;
use AppSettings;

class JsController extends Controller
{

    public function __construct($var = null)
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

        return new JsController();
    }

    // izpise kodo za JS sledenje, ce je nastavljena
    public static function js_tracking()
    {

        $row = SurveyInfo::getSurveyRow();

        if ($row['js_tracking'] != '') {
            echo '<!-- JS tracking code -->';
            echo $row['js_tracking'];
            echo '<!-- / JS tracking code -->';
        }

    }

    /**
     * @desc generira JavaScript za alert pri vprasanjih z reminderjem
     */
    public function generateSubmitJS()
    {
        global $lang;
        global $site_url;
		global $admin_type;

        // Dodaten text pri alertu ce smo v testnem vnosu
        $test_alert = (isset($_GET['testdata']) && $_GET['testdata'] == 'on') ? '\n\n' . $lang['srv_remind_preview'] : '';

        // Custom texti za opozorila...
        SurveySetting::getInstance()->Init(get('anketa'));
        if (get('lang_id') != null) $_lang = '_' . get('lang_id'); else $_lang = '';
        $srv_remind_sum_hard = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_sum_hard' . $_lang);
        if ($srv_remind_sum_hard == '') $srv_remind_sum_hard = $lang['srv_remind_sum_hard'];
        $srv_remind_sum_hard = strip_tags(addslashes(stripslashes($srv_remind_sum_hard))).$test_alert;
        $srv_remind_sum_soft = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_sum_soft' . $_lang);
        if ($srv_remind_sum_soft == '') $srv_remind_sum_soft = $lang['srv_remind_sum_soft'];
        $srv_remind_sum_soft = strip_tags(addslashes(stripslashes($srv_remind_sum_soft))).$test_alert;
        $srv_remind_num_hard = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_num_hard' . $_lang);
        if ($srv_remind_num_hard == '') $srv_remind_num_hard = $lang['srv_remind_num_hard'];
        $srv_remind_num_hard = strip_tags(addslashes(stripslashes($srv_remind_num_hard))).$test_alert;
        $srv_remind_num_soft = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_num_soft' . $_lang);
        if ($srv_remind_num_soft == '') $srv_remind_num_soft = $lang['srv_remind_num_soft'];
        $srv_remind_num_soft = strip_tags(addslashes(stripslashes($srv_remind_num_soft))).$test_alert;
        
        $srv_remind_text_num_hard = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_text_num_hard' . $_lang);
        if ($srv_remind_text_num_hard == '') $srv_remind_text_num_hard = $lang['srv_remind_text_num_hard'];
        $srv_remind_text_num_hard = strip_tags(addslashes(stripslashes($srv_remind_text_num_hard))).$test_alert;
        $srv_remind_text_num_soft = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_text_num_soft' . $_lang);
        if ($srv_remind_text_num_soft == '') $srv_remind_text_num_soft = $lang['srv_remind_text_num_soft'];
        $srv_remind_text_num_soft = strip_tags(addslashes(stripslashes($srv_remind_text_num_soft))).$test_alert;
		
		//za minimalno stevilo izbranih checkbox-ov
		$srv_remind_checkbox_min_violated_hard = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_checkbox_min_violated_hard' . $_lang);
        if ($srv_remind_checkbox_min_violated_hard == '') $srv_remind_checkbox_min_violated_hard = $lang['srv_remind_checkbox_min_violated_hard'];
        $srv_remind_checkbox_min_violated_hard = strip_tags(addslashes(stripslashes($srv_remind_checkbox_min_violated_hard))).$test_alert;
		
		$srv_remind_checkbox_min_violated_soft = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_checkbox_min_violated_soft' . $_lang);
        if ($srv_remind_checkbox_min_violated_soft == '') $srv_remind_checkbox_min_violated_soft = $lang['srv_remind_checkbox_min_violated_soft'];
        $srv_remind_checkbox_min_violated_soft = strip_tags(addslashes(stripslashes($srv_remind_checkbox_min_violated_soft))).$test_alert;
		//za minimalno stevilo izbranih checkbox-ov - konec

        $srv_remind_hard = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_hard' . $_lang);
        if ($srv_remind_hard == '') $srv_remind_hard = $lang['srv_remind_hard'];
        $srv_remind_hard = strip_tags(addslashes(stripslashes($srv_remind_hard))).$test_alert;
        $srv_remind_soft = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_soft' . $_lang);
        if ($srv_remind_soft == '') $srv_remind_soft = $lang['srv_remind_soft'];
        $srv_remind_soft = strip_tags(addslashes(stripslashes($srv_remind_soft))).$test_alert;

        $srv_remind_hard_99 = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_hard_-99' . $_lang);
        if ($srv_remind_hard_99 == '') $srv_remind_hard_99 = $lang['srv_remind_hard_-99'];
        $srv_remind_hard_99 = strip_tags(addslashes(stripslashes($srv_remind_hard_99))).$test_alert;
        $srv_remind_soft_99 = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_soft_-99' . $_lang);
        if ($srv_remind_soft_99 == '') $srv_remind_soft_99 = $lang['srv_remind_soft_-99'];
        $srv_remind_soft_99 = strip_tags(addslashes(stripslashes($srv_remind_soft_99))).$test_alert;
        $srv_remind_hard_98 = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_hard_-98' . $_lang);
        if ($srv_remind_hard_98 == '') $srv_remind_hard_98 = $lang['srv_remind_hard_-98'];
        $srv_remind_hard_98 = strip_tags(addslashes(stripslashes($srv_remind_hard_98))).$test_alert;
        $srv_remind_soft_98 = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_soft_-98' . $_lang);
        if ($srv_remind_soft_98 == '') $srv_remind_soft_98 = $lang['srv_remind_soft_-98'];
        $srv_remind_soft_98 = strip_tags(addslashes(stripslashes($srv_remind_soft_98))).$test_alert;
        $srv_remind_hard_97 = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_hard_-97' . $_lang);
        if ($srv_remind_hard_97 == '') $srv_remind_hard_97 = $lang['srv_remind_hard_-97'];
        $srv_remind_hard_97 = strip_tags(addslashes(stripslashes($srv_remind_hard_97))).$test_alert;
        $srv_remind_soft_97 = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_soft_-97' . $_lang);
        if ($srv_remind_soft_97 == '') $srv_remind_soft_97 = $lang['srv_remind_soft_-97'];
        $srv_remind_soft_97 = strip_tags(addslashes(stripslashes($srv_remind_soft_97))).$test_alert;
        $srv_remind_hard_multi = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_hard_multi' . $_lang);
        if ($srv_remind_hard_multi == '') $srv_remind_hard_multi = $lang['srv_remind_hard_multi'];
        $srv_remind_hard_multi = strip_tags(addslashes(stripslashes($srv_remind_hard_multi))).$test_alert;
        $srv_remind_soft_multi = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_soft_multi' . $_lang);
        if ($srv_remind_soft_multi == '') $srv_remind_soft_multi = $lang['srv_remind_soft_multi'];
        $srv_remind_soft_multi = strip_tags(addslashes(stripslashes($srv_remind_soft_multi))).$test_alert;

        $srv_remind_captcha_soft = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_captcha_soft' . $_lang);
        if ($srv_remind_captcha_soft == '') $srv_remind_captcha_soft = $lang['srv_remind_captcha_soft'];
        $srv_remind_captcha_soft = strip_tags(addslashes(stripslashes($srv_remind_captcha_soft))).$test_alert;
        $srv_remind_captcha_hard = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_captcha_hard' . $_lang);
        if ($srv_remind_captcha_hard == '') $srv_remind_captcha_hard = $lang['srv_remind_captcha_hard'];
        $srv_remind_captcha_hard = strip_tags(addslashes(stripslashes($srv_remind_captcha_hard))).$test_alert;

        $srv_remind_email_soft = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_email_soft' . $_lang);
        if ($srv_remind_email_soft == '') $srv_remind_email_soft = $lang['srv_remind_email_soft'];
        $srv_remind_email_soft = strip_tags(addslashes(stripslashes($srv_remind_email_soft))).$test_alert;
        $srv_remind_email_hard = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_remind_email_hard' . $_lang);
        if ($srv_remind_email_hard == '') $srv_remind_email_hard = $lang['srv_remind_email_hard'];
        $srv_remind_email_hard = strip_tags(addslashes(stripslashes($srv_remind_email_hard))).$test_alert;

        $srv_alert_number_toobig = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_alert_number_toobig' . $_lang);
        if ($srv_alert_number_toobig == '') $srv_alert_number_toobig = $lang['srv_alert_number_toobig'];
        $srv_alert_number_toobig = strip_tags(addslashes(stripslashes($srv_alert_number_toobig))).$test_alert;
        $srv_alert_number_exists = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_alert_number_exists' . $_lang);
        if ($srv_alert_number_exists == '') $srv_alert_number_exists = $lang['srv_alert_number_exists'];
        $srv_alert_number_exists = strip_tags(addslashes(stripslashes($srv_alert_number_exists))).$test_alert;

        $mobile_tables = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_tables');
		
		$srv_remind_checkbox_max_violated_hard = SurveySetting::getInstance()->getSurveyMiscSetting('srv_remind_checkbox_max_violated_hard' . $_lang);
		if ($srv_remind_checkbox_max_violated_hard == '') $srv_remind_checkbox_max_violated_hard = $lang['srv_remind_checkbox_max_violated_hard'];
		$srv_remind_checkbox_max_violated_hard = strip_tags(addslashes(stripslashes($srv_remind_checkbox_max_violated_hard))).$test_alert;

	
        echo '        <script> ' . "\n";
		
        ?>
        var lang_srv_alert_number_exists = "<?= $srv_alert_number_exists ?>";
        var lang_srv_alert_number_toobig = "<?= $srv_alert_number_toobig ?>";
        var lang_srv_remind_checkbox_max_violated_hard = "<?= $srv_remind_checkbox_max_violated_hard ?>";
        <?

        $survey_hint = SurveySetting::getInstance()->getSurveyMiscSetting('survey_hint');
        if ($survey_hint == '') $survey_hint = 1;

        echo '          // generateSubmitJS() ' . "\n";
        ?>
        function submitAlert (id, action, type, bol, validation, txt, alert_sum, alert_num, alert_validation) {

			if (validation == true) {

				if (<?= $survey_hint ?> == 1) {

					// pri tabelah izberemo celo spremenljivko
					if ( ! $(id).hasClass('spremenljivka') ) {
						id = '#' + $(id).closest('.spremenljivka')[0].id;
					}
					
					if (type == 'require' || type == 'require2' || type == 'require3' || type == 'require4' || type == 'require5') {						
						if ( $(id)[0].getAttribute('data-vrstni_red') >= max_vrstni_red ) return;
					} else {						
						if ( $(id)[0].getAttribute('data-vrstni_red') > max_vrstni_red ) return;
					}

					var text;
					if (type == 'require')
					text = '<?= str_replace('\n', '<br />', $srv_remind_hard) ?>';
					if (type == 'require2')
					text = '<?= str_replace('\n', '<br />', $srv_remind_hard_99) ?>';
					if (type == 'require3')
					text = '<?= str_replace('\n', '<br />', $srv_remind_hard_98) ?>';
					if (type == 'require4')
					text = '<?= str_replace('\n', '<br />', $srv_remind_hard_97) ?>';
					if (type == 'require5')
					text = '<?= str_replace('\n', '<br />', $srv_remind_hard_multi) ?>';
					if (type == 'limit')
					text = '<?= str_replace('\n', '<br />', $srv_remind_num_hard) ?>';
                    if (type == 'text_limit')
					text = '<?= str_replace('\n', '<br />', $srv_remind_text_num_hard) ?>';
					if (type == 'validation')
					text = txt;
					if (type == 'checkbox_min_limit')
					text = '<?= str_replace('\n', '<br />', $srv_remind_checkbox_min_violated_hard) ?>';

					if (action == 'add') {
						if ($(id+' div.validation_alert.'+type+'').length == 0){

							$(id).append('<div class="validation_alert '+type+'">'+text+'</div>');

							// Ce je opozorilo izven ekrana (ozki ekran, mobitel, tablica...) ga prikazemo na desni
							$('div.validation_alert').each(function(){
								if($(this).offset().left < 0){
									$(this).addClass('alignRight');
								}
							});
						}			
						showMissing(id);
					}
					else {
						$(id+' div.validation_alert.'+type+'').remove();
					}
				}				
			}
			else {
				if (action == 'add') {
					$(id).addClass('required_'+type);

					// Ce imamo vklopljene napredne parapodatke zabelezimo opozorilo
					<?if (SurveyAdvancedParadataLog::getInstance()->paradataEnabled()){?>
						dodaj_opozorilo(alert_sum, alert_num, alert_validation, bol, id);
					<?}?>

					showMissing(id);
				}
				else {
					$(id).removeClass('required_'+type);
				}
			}
        }

        function isEmail(email) {
			var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
			return regex.test(email);
        }

        function showMissing(id) {
			$(id).find('.missing').slideDown();
        }
        <?
		
        echo '          function submitForm(parent_if, validation) { ' . "\n";
        echo '            if (is_paused_slideshow == true) return false' . "\n";
        echo '            var hard = true; ' . "\n";
        echo '            var soft = true; ' . "\n";
        echo '            var vsota = true; ' . "\n";
        echo '            var num = true; ' . "\n";
        echo '            var text_num = true; ' . "\n";
		echo '            var checkbox_min_limit_alert = false; ' . "\n";	


        $sql = sisplet_query("SELECT * FROM srv_spremenljivka WHERE gru_id='" . get('grupa') . "' AND gru_id != '0' AND reminder > 0 ORDER BY vrstni_red ASC");
        while ($row = mysqli_fetch_array($sql)) {

            echo '           try { ' . "\n";

            // katero spremenljivko bomo nastavili
            if ($row['reminder'] == 1) {
                $bol = 'soft';
            } else {
                $bol = 'hard';
            }

            // ce prikazujemo naknadno vec moznosti (recimo nevem in neustrezno)
            if (($row['alert_show_99'] == 1 && $row['alert_show_98'] == 1) || ($row['alert_show_99'] == 1 && $row['alert_show_97'] == 1) || ($row['alert_show_98'] == 1 && $row['alert_show_97'] == 1)) {
                $require = 'require5';
            } // ce prikazujemo naknadno ne vem
            elseif ($row['alert_show_99'] == 1) {
                $require = 'require2';
            } // ce prikazujemo naknadno zavrnil
            elseif ($row['alert_show_98'] == 1) {
                $require = 'require3';
            } // ce prikazujemo naknadno neustrezno
            elseif ($row['alert_show_97'] == 1) {
                $require = 'require4';
            } else {
                $require = 'require';
            }

            // najprej damo pogoj, da se uposteva, samo ce je vprasanje vidno (ker je lahko v ifu)
            //echo '            if ( $(\'#spremenljivka_'.$row['id'].'\').css(\'display\') != \'none\' ) { '."\n";
            echo '            if ( document.getElementById(\'spremenljivka_' . $row['id'] . '\').style.display != \'none\' ) { ' . "\n";
			
            // radio in checkbox
            if ($row['tip'] <= 2 && ($row['orientation'] != 8 || get('mobile') == 1)) {
				
				if ($row['tip'] == 1)
					$ime = 'vrednost_' . $row['id'];
				else
					$ime = 'vrednost_' . $row['id'] . '[]';

				echo '              var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
				echo '              var len = obj.length; ' . "\n";

				echo '              var bol = false; ' . "\n";

				// ce je samo 1 (spodnja procedura ne prime, je treba posebej)
				echo '              if (len == undefined) {  ' . "\n";

				echo '                if (obj.checked)  ' . "\n";
				echo '                  bol = true;  ' . "\n";

				// sicer gremo normalno čez vse
				//preureditev za pregled textboxa za drugo
				echo '              } else { ' . "\n";
				
				echo '					var bolTextBox = [] ' . "\n"; //polje, ki shranjuje info o boolean za "drugo"
				
				echo '					for (i=0; i<len; i++){ ' . "\n";						
				echo '                  	if (obj[i].checked){ ' . "\n";	//ce je odgovor izbran
				echo '                    		bol = true; ' . "\n";
				
					//urejanje za textbox za drugo
 				echo '                  		var drugo_temp = obj[i].id.replace("vrednost", "textfield"); ' . "\n";				
 				echo '                  		if($("#"+drugo_temp).length){ ' . "\n";
				echo '              				var drugo_text_empty = true; ' . "\n";				
				echo '                  			if($("#"+drugo_temp).val() != ""){ ' . "\n";
				echo '                  				drugo_text_empty = false; ' . "\n";			
				echo '                  			} ' . "\n";
				echo '              				if (drugo_text_empty){ ' . "\n";
				echo '               					bolTextBox[i] = false; ' . "\n";
				echo '              				}else{ ' . "\n";
				echo '               					bolTextBox[i] = true; ' . "\n";
				echo '                  			} ' . "\n";
				
				echo '                  		} ' . "\n";
					//urejanje za textbox za drugo - konec
				echo '               		} ' . "\n";	//konec if (obj[i].checked)
				echo '              	} ' . "\n";	//konec for
				
					//ce polje za textbox "drugo" ni prazno, ustrezno uredi spremenljivko bol
				echo '                  if(bolTextBox.indexOf(false)>0){ ' . "\n";
				echo '               		bol = false; ' . "\n";
				echo '              	} ' . "\n";
					//ce polje za textbox "drugo" ni prazno, ustrezno uredi spremenljivko bol - konec
				

				echo '              }  ' . "\n";
				//preureditev za pregled textboxa za drugo - konec
					
			
				// Posebna funkcionalnost za ministrstvo za obrambo, ker se mudi (q5,q6,q8,q9,q11,q14)
				if($site_url == 'https://www.1ka.si/' && get('anketa') == '180402' && in_array($row['id'], array('10654896', '10654916', '10668668', '10677046', '10668603', '10657205'))){
					
					// Minimalno št. checkboxov (3 oz. 5)
					if(in_array($row['id'], array('10654896', '10654916', '10668668', '10677046'))){
						$cnt_special = ($row['id'] == '10668668') ? 5 : 3;
						
						echo '              var cnt_special = 0; ' . "\n";

						echo '              for (i=0; i<len; i++){ ' . "\n";
						echo '                if (obj[i].checked) ' . "\n";
						echo '                  cnt_special ++; ' . "\n";
						echo '              } ' . "\n";
						
						echo '              if (cnt_special != '.$cnt_special.') ' . "\n";
						echo '                bol = false; ' . "\n";
					}
					
					// Kliknil drugo brez vnosa v text polje
					if(in_array($row['id'], array('10654896', '10668603', '10657205'))){
						
						echo '              for (i=0; i<len; i++){ ' . "\n";
						echo '                if (obj[i].checked){ ' . "\n";				
						echo '                  var drugo_temp = obj[i].id.replace("vrednost", "textfield"); ' . "\n";						
						echo '                  if($("#"+drugo_temp).length){ ' . "\n";				
						
						echo '              		var drugo_text_empty = true; ' . "\n";
						
						echo '                  	if($("#"+drugo_temp).val() != "") ' . "\n";
						echo '                  		drugo_text_empty = false; ' . "\n";			

						echo '              		if (drugo_text_empty) ' . "\n";
						echo '               			bol = false; ' . "\n";

						echo '                  } ' . "\n";					
						echo '                } ' . "\n";
						echo '              } ' . "\n";
					}
				}

				
				echo '              if (!bol) { ' . "\n";
				echo '                ' . $bol . ' = false; ' . "\n";
				echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'' . $require . '\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";				
				echo '              } else { ' . "\n";
				echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'' . $require . '\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";		
				echo '              }' . "\n";
                
            }
            //drag and drop - kategorije en in vec odgovorov
            if ($row['tip'] <= 2 && ($row['orientation'] == 8 && get('mobile') != 1)) {
                $ime = 'vrednost_' . $row['id'];
                echo '
					var bol = false;
					var prisotno = $("#half2_frame_dropping_' . $row['id'] . '").children("div").attr("value");
					if (prisotno){
						bol = true;
					}
					if (!bol) {
						' . $bol . ' = false;
						submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'' . $require . '\', \'' . $bol . '\', validation, false, false, false, false);
					}else{
						submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'' . $require . '\', \'' . $bol . '\', validation, false, false, false, false);
					}
				';


                // dropdown
            } elseif ($row['tip'] == 3) {

                $ime = 'vrednost_' . $row['id'];

                echo '              var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
                echo '              if (!obj.value > 0) { ' . "\n";
                echo '                ' . $bol . ' = false; ' . "\n";
                echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo '              } else { ' . "\n";
                echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo '              } ' . "\n";

            } //tabele @ drag and drop
            elseif (($row['tip'] == 6 && $row['enota'] == 9) || ($row['tip'] == 16 && $row['enota'] == 9)) {
                echo '
					//console.log("Drag and drop Tabela en odgovorov");
					var bol = true;
					var prisotno = $("#half_frame_dropping_' . $row['id'] . '").children("div").attr("value");
					if (prisotno){
						bol = false;
					}
					if (!bol) {
						' . $bol . ' = false;
						submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'' . $require . '\', \'' . $bol . '\', validation, false, false, false, false);
					}else{
						submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'' . $require . '\', \'' . $bol . '\', validation, false, false, false, false);
					}


				';

                // tabele
                //} elseif ($row['tip'] == 6 || $row['tip'] == 16 || $row['tip'] == 19 || $row['tip'] == 20) {
            } elseif (($row['tip'] == 6 && $row['enota'] != 9) || $row['tip'] == 16 || $row['tip'] == 19 || $row['tip'] == 20) {

                echo '                  var totalbol = true; ';

                $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$row[id]' AND other!='1' ORDER BY vrstni_red");
                while ($row1 = mysqli_fetch_array($sql1)) {

                    // Pri postopnem resevanju multigrida tega nimamo, ker drugace ne dela ok (ker deluje na principu da so vse vrstice razen tekoce display:none;)
                    if ($row['dynamic_mg'] == 0) {
                        echo '              if ( document.getElementById(\'vrednost_if_' . $row1['id'] . '\').style.display != \'none\' ) {  ' . "\n";
                    }

                    // dropdown
                    if ($row['enota'] == 2) { // zakaj je tole? pride do bugov, ce je ostala enota nastavljena na ostalih tipih

                        // todo
                        $ime = 'vrednost_' . $row1['id'];

                        echo '                var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
                        echo '                if (!obj.value > 0) { ' . "\n";
                        echo '                  ' . $bol . ' = false; ' . "\n";
                        echo '                  totalbol = false; ' . "\n";
                        echo '                  if (!validation) submitAlert(\'#vrednost_if_' . $row1['id'] . '\', \'add\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                        echo '                } else { ' . "\n";
                        echo '                  if (!validation) submitAlert(\'#vrednost_if_' . $row1['id'] . '\', \'remove\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                        echo '                } ' . "\n";


                        // multi radio
                        //} elseif ($row['tip'] == 6) {
                    } elseif ($row['tip'] == 6 && $row['enota'] != 9) {
						
						$ime = 'vrednost_' . $row1['id'];
												
						// Pri postopnem resevanju moramo skrite z ifom posebej izlociti iz preverjanja
						if ($row['dynamic_mg'] != 0) {
							echo '       if( !$(\'#vrednost_if_' . $row1['id'] . '\').hasClass(\'if_hide\') ){ ' . "\n";
						}

						//echo 'console.log("Klasična tabela!"); ';
						echo '                var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
						echo '                var len = obj.length; ' . "\n";
						echo '                var bol = false; ' . "\n";
						
						// ce je samo 1 (spodnja procedura ne prime, je treba posebej)
						echo '                if (len == undefined) {  ' . "\n";

						echo '                  if (obj.checked)  ' . "\n";
						echo '                    bol = true;  ' . "\n";

						// sicer gremo normalno čez vse
						echo '                } else { ' . "\n";

						echo '                  for (i=0; i<len; i++) ' . "\n";
						echo '                    if (obj[i].checked) ' . "\n";
						echo '                      bol = true; ' . "\n";

						echo '                }  ' . "\n";
											
						// Pri dvojnih tabelah moramo posebej preverjati se desno stran
						echo '                var bol2 = true; ' . "\n";		
						if ($row['enota'] == 3) {
							$ime2 = 'vrednost_' . $row1['id'].'_part_2';
							
							echo '                var obj2 = document.forms[\'vnos\'].elements[\'' . $ime2 . '\']; ' . "\n";
							echo '                var len2 = obj2.length; ' . "\n";
							echo '                var bol2 = false; ' . "\n";
							
							echo '                for (i=0; i<len2; i++) ' . "\n";
							echo '                  if (obj2[i].checked) ' . "\n";
							echo '                    bol2 = true; ' . "\n";
						}					

						echo '                if (!bol || !bol2) { ' . "\n";
						echo '                  ' . $bol . ' = false; ' . "\n";
						echo '                  totalbol = false; ' . "\n";
						echo '                  if (!validation) submitAlert(\'#vrednost_if_' . $row1['id'] . '\', \'add\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
						echo '                } else { ' . "\n";
						echo '                  if (!validation) submitAlert(\'#vrednost_if_' . $row1['id'] . '\', \'remove\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
						echo '                } ' . "\n";
												
						// Pri postopnem resevanju moramo skrite z ifom posebej izlociti iz preverjanja
						if ($row['dynamic_mg'] != 0) {
							echo '        } ' . "\n";
						}
		
                        // multicheck
                        //} elseif ($row['tip'] == 16) {
                    } elseif ($row['tip'] == 16 && $row['enota'] != 9) {

                        $ime = 'vrednost_' . $row1['id'];

                        echo '                var bol = false; ' . "\n";

                        echo '                for (var i=1; i<=' . $row['grids'] . '; i++) { ' . "\n";
                        echo '                    obj = document.getElementById(\'' . $ime . '_grid_\'+i); ' . "\n";

                        echo '                    if (obj.checked || obj.disabled) ' . "\n";
                        echo '                        bol = true; ' . "\n";

                        echo '                } ' . "\n";

                        echo '                if (!bol) { ' . "\n";
                        echo '                  ' . $bol . ' = false; ' . "\n";
                        echo '                  totalbol = false; ' . "\n";
                        echo '                  if (!validation) submitAlert(\'#vrednost_if_' . $row1['id'] . '\', \'add\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                        echo '                } else { ' . "\n";
                        echo '                  if (!validation) submitAlert(\'#vrednost_if_' . $row1['id'] . '\', \'remove\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                        echo '                } ' . "\n";

                        // tabela besedilo in tabela number
                    } elseif ($row['tip'] == 19 || $row['tip'] == 20) {

                        $ime = 'vrednost_' . $row1['id'];

                        echo '                var bol = false; ' . "\n";

                        echo '                for (var i=1; i<=' . $row['grids'] . '; i++) { ' . "\n";
                        echo '                    obj = document.getElementById(\'' . $ime . '_grid_\'+i); ' . "\n";

                        echo '                    if (obj.value != "" || obj.disabled) ' . "\n";
                        echo '                        bol = true; ' . "\n";

                        echo '                } ' . "\n";

                        echo '                if (!bol) { ' . "\n";
                        echo '                  ' . $bol . ' = false; ' . "\n";
                        echo '                  totalbol = false; ' . "\n";
                        echo '                  if (!validation) submitAlert(\'#vrednost_if_' . $row1['id'] . '\', \'add\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                        echo '                } else { ' . "\n";
                        echo '                  if (!validation) submitAlert(\'#vrednost_if_' . $row1['id'] . '\', \'remove\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                        echo '                } ' . "\n";

                    }

                    // Pri postopnem resevanju multigrida tega nimamo, ker drugace ne dela ok
                    if ($row['dynamic_mg'] == 0) {
                        echo '              } ' . "\n";;
                    }
                }

                echo '              if (validation) {																								';
                echo '                if (!totalbol)																								';
                echo '					submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo '				  else 																											';
                echo '					submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo ' 			    }																												';

                // besedilo *
            } elseif ($row['tip'] == 21) {

                // captcha
                $spremenljivkaParams = new enkaParameters($row['params']);
                $captcha = ($spremenljivkaParams->get('captcha') ? $spremenljivkaParams->get('captcha') : 0);

				if ($captcha == 1) {
                    ?>
                    var response = grecaptcha.getResponse();
					
					if (response.gelgth != 0 || validation==true) {
						// ok
					} 
					else {
						<?
						if ($bol == 'soft') {
							?>
							if (!confirm('<?=$srv_remind_captcha_soft?>'))
								return;
							<?
						} 
						else {
							?>
							alert('<?=$srv_remind_captcha_hard?>');
							return;
							<?
						}
						?>
					}
					<?
				}

                //for ($i=0; $i<$row['text_kosov']; $i++) {
                $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
                while ($row1 = mysqli_fetch_array($sql1)) {

                    $ime = 'vrednost_' . $row['id'] . '_kos_' . $row1['id'];

                    echo '              var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
                    echo '              if (obj.value == "" && obj.disabled == false) { ' . "\n";
                    echo '                ' . $bol . ' = false; ' . "\n";
                    echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'' . $require . '\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                    echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'validation\', \'' . $bol . '\', validation); ' . "\n";

                    // Email validation
                    $emailVerify = ($spremenljivkaParams->get('emailVerify') ? $spremenljivkaParams->get('emailVerify') : 0);
                    if ($emailVerify == 1) {
                        echo '              } else if(isEmail(obj.value) == false) { ' . "\n";
                        echo '                ' . $bol . ' = false; ' . "\n";
                        echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'validation\', \'' . $bol . '\', validation, \'' . ($bol == 'soft' ? $srv_remind_email_soft : $srv_remind_email_hard) . '\'); ' . "\n";
                        echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'' . $require . '\', \'' . $bol . '\', validation); ' . "\n";
                        
                        echo '              } else if(isEmail(obj.value) == true) { ' . "\n";
                        echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'validation\', \'' . $bol . '\', validation, \'' . ($bol == 'soft' ? $srv_remind_email_soft : $srv_remind_email_hard) . '\'); ' . "\n";
                        echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'' . $require . '\', \'' . $bol . '\', validation); ' . "\n";
                    }

                    echo '              } else { ' . "\n";
                    echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'' . $require . '\', \'' . $bol . '\', validation); ' . "\n";
                    echo '              } ' . "\n";
                }

                // textbox, compute, quota
            } elseif ($row['tip'] == 4 || $row['tip'] == 22 || $row['tip'] == 25 || $row['tip'] == 8) {

                $ime = 'vrednost_' . $row['id'];

                echo '              var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
                echo '              if (obj.value == "" && obj.disabled == false) { ' . "\n";
                echo '                ' . $bol . ' = false; ' . "\n";
                echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'' . $require . '\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo '              } else { ' . "\n";
                echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'' . $require . '\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo '              } ' . "\n";

                // number
            } elseif ($row['tip'] == 7) {

                $ime = 'spremenljivka_' . $row['id'] . '_vrednost';

                echo '              var obj1 = document.getElementById(\'' . $ime . '_1\'); 	' . "\n";
                if ($row['size'] == 2)
                    echo '              var obj2 = document.getElementById(\'' . $ime . '_2\'); 	' . "\n";
                if ($row['size'] == 1)
                    echo '              if (obj1.value == "" && obj1.disabled == false) { ' . "\n";
                elseif ($row['size'] == 2)
                    echo '              if ((obj1.value == "" && obj1.disabled == false) || (obj2.value == "" && obj2.disabled == false)) { ' . "\n";
                echo '                ' . $bol . ' = false; ' . "\n";
                echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'' . $require . '\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo '              } else { ' . "\n";
                echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'' . $require . '\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo '              } ' . "\n";
            }

            // razvrscanje - ostevilcevanje
            elseif ($row['tip'] == 17 && ($row['design'] == 1 || get('mobile') > 0)) {

                // max stevilo vnesenih vrednosti
                $max = $row['ranking_k'];

                // nimamo omejenega stevila vnosov (morajo biti vnesena vsa stevila)
                if ($max == 0) {
                    $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
                    while ($row1 = mysqli_fetch_array($sql1)) {

                        $ime = 'spremenljivka_' . $row['id'] . '_vrednost_' . $row1['id'];

                        echo '              var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
                        echo '              if (obj.value == "") { ' . "\n";
                        echo '                ' . $bol . ' = false; ' . "\n";
                        echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                        echo '              } else { ' . "\n";
                        echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                        echo '              } ' . "\n";
                    }
                } 
                // imamo omejeno stevilo vnosov
                else {
                    echo '              var count = ' . $max . '; ' . "\n";

                    $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
                    while ($row1 = mysqli_fetch_array($sql1)) {
                        $ime = 'spremenljivka_' . $row['id'] . '_vrednost_' . $row1['id'];

                        echo '              var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
                        echo '              if (obj.value != "") ' . "\n";
                        echo '                count --; ' . "\n";
                    }

                    echo '              if (count > 0) { ' . "\n";
                    echo '                ' . $bol . ' = false; ' . "\n";
                    echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                    echo '              } else { ' . "\n";
                    echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                    echo '              } ' . "\n";
                }
            } 
            // razvrscanje - prestavljanje
            elseif ($row['tip'] == 17 && ($row['design'] == 0)) {

                //max stevilo vnesenih vrednosti
                $max = $row['ranking_k'];

                //nimamo omejenega stevila vnosov (morajo biti vnesena vsa stevila)
                if ($max == 0) {
                    $sql1 = sisplet_query("SELECT COUNT(id) FROM srv_vrednost WHERE spr_id='$row[id]'");
                    $row1 = mysqli_fetch_array($sql1);
                    $max = $row1['COUNT(id)'];
                }

                $ime = 'half2_' . $row['id'];
                echo '              var count = $(\'#' . $ime . '\').children().size(); ' . "\n";

                echo '              if (count < \'' . $max . '\') { ' . "\n";
                echo '                ' . $bol . ' = false; ' . "\n";
                echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo '              } else { ' . "\n";
                echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo '              } ' . "\n";
            } 
            // razvrscanje - heatmap
            elseif ($row['tip'] == 17 && ($row['design'] == 3)) {

                $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$row[id]'");
                while($row1 = mysqli_fetch_array($sql1)){

                    $ime = 'spremenljivka_' . $row['id'] . '_vrednost_' . $row1['id'];

                    echo '              var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
                    echo '              if (obj.value == "") { ' . "\n";
                    echo '                ' . $bol . ' = false; ' . "\n";
                    echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                    echo '              } else { ' . "\n";
                    echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                    echo '              } ' . "\n";
                }
            } 
            
            // vsota
            elseif ($row['tip'] == 18) {

                $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
                while ($row1 = mysqli_fetch_array($sql1)) {

                    $ime = 'spremenljivka_' . $row['id'] . '_vrednost_' . $row1['id'];

                    echo '              var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
                    echo '              if (obj.value == "") { ' . "\n";
                    echo '                ' . $bol . ' = false; ' . "\n";
                    echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                    echo '              } else { ' . "\n";
                    echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                    echo '              } ' . "\n";
                }

                // sn
            } elseif ($row['tip'] == 9) {

                echo '              var obj = document.getElementById(\'txt1\'); 	' . "\n";
                echo '              if (obj.value == "") { ' . "\n";
                echo '                ' . $bol . ' = false; ' . "\n";
                echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo '              } else { ' . "\n";
                echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo '              } ' . "\n";


                // kombinirana tabela
            } elseif ($row['tip'] == 24) {

                $first_spr = 0;

                $sqlm = sisplet_query("SELECT s.* FROM srv_spremenljivka s, srv_grid_multiple gm WHERE s.id=gm.spr_id AND gm.parent = '$row[id]' ORDER BY gm.vrstni_red ASC");
                $rowm = mysqli_fetch_array($sqlm);
                $first_spr = $rowm['id'];

                $sql1 = sisplet_query("SELECT id, vrstni_red FROM srv_vrednost WHERE spr_id='$first_spr' ORDER BY vrstni_red");
                				
                while ($row1 = mysqli_fetch_array($sql1)) {
				
					// Preverjamo ce je if na trenutni vrstici
					$vrednost = '';
					$sqlVrednostIf = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$row[id]' AND if_id>0 AND vrstni_red='".$row1['vrstni_red']."'");
					if(mysqli_num_rows($sqlVrednostIf) > 0){
						$rowVrednostIf = mysqli_fetch_array($sqlVrednostIf);
						$vrednost = 'vrednost_if_' . $rowVrednostIf['id'];
					}
					echo '            if ( !$(\'#'.$vrednost.'\').length || document.getElementById(\'' . $vrednost . '\').style.display != \'none\' ) {  ' . "\n";
					
                    echo '                var totalBol = true; ' . "\n";

                    mysqli_data_seek($sqlm, 0);
                    while ($rowm = mysqli_fetch_array($sqlm)) {

                        $sql2 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$rowm[id]' AND vrstni_red='$row1[vrstni_red]'");
                        $row2 = mysqli_fetch_array($sql2);

                        // dropdown
                        if ($rowm['tip'] == 6 && $rowm['enota'] == 2) {

                            // todo
                            $ime = 'multi_' . $rowm['id'] . '_' . $row2['id'];

                            echo '                var bol = true; ' . "\n";
                            echo '                var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
                            echo '                if (!obj.value > 0) { ' . "\n";
                            echo '                  bol = false; ' . "\n";
                            echo '                }' . "\n";
                            echo '                if (bol==false) totalBol = false; ' . "\n";
                            //selectbox
                        } elseif ($rowm['tip'] == 6 && $rowm['enota'] == 6) {

                            // todo
                            $ime = 'multi_' . $rowm['id'] . '_' . $row2['id'];

                            echo '                var bol = true; ' . "\n";
                            echo '                var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
                            echo '                if (!obj.value > 0) { ' . "\n";
                            echo '                  bol = false; ' . "\n";
                            echo '                }' . "\n";
                            echo '                if (bol==false) totalBol = false; ' . "\n";

                            // multi radio
                        } elseif ($rowm['tip'] == 6) {

                            $ime = 'multi_' . $rowm['id'] . '_' . $row2['id'];

                            echo '                var bol = false; ' . "\n";
                            echo '                var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
                            echo '                var len = obj.length; ' . "\n";


                            // ce je samo 1 (spodnja procedura ne prime, je treba posebej)
                            echo '                if (len == undefined) {  ' . "\n";

                            echo '                  if (obj.checked)  ' . "\n";
                            echo '                    bol = true;  ' . "\n";

                            // sicer gremo normalno čez vse
                            echo '                } else { ' . "\n";

                            echo '                  for (i=0; i<len; i++) ' . "\n";
                            echo '                    if (obj[i].checked) ' . "\n";
                            echo '                      bol = true; ' . "\n";

                            echo '                }  ' . "\n";
                            echo '                if (bol==false) totalBol = false; ' . "\n";

                            // multicheck
                        } elseif ($rowm['tip'] == 16) {

                            $ime = 'multi_' . $rowm['id'] . '_' . $row2['id'];

                            echo '                var bol = false; ' . "\n";
                            echo '                for (var i=1; i<=' . $rowm['grids'] . '; i++) { ' . "\n";
                            echo '                    obj = document.getElementById(\'' . $ime . '_grid_\'+i); ' . "\n";

                            echo '                    if (obj.checked || obj.disabled) ' . "\n";
                            echo '                        bol = true; ' . "\n";

                            echo '                } ' . "\n";
                            echo '                if (bol==false) totalBol = false; ' . "\n";

                            // tabela besedilo in tabela number
                        } elseif ($rowm['tip'] == 19 || $rowm['tip'] == 20) {

                            $ime = 'multi_' . $rowm['id'] . '_' . $row2['id'];

                            echo '                var bol = false; ' . "\n";
                            echo '                for (var i=1; i<=' . $rowm['grids'] . '; i++) { ' . "\n";
                            echo '                    obj = document.getElementById(\'' . $ime . '_grid_\'+i); ' . "\n";

                            echo '                    if (obj.value != "" || obj.disabled) ' . "\n";
                            echo '                        bol = true; ' . "\n";

                            echo '                } ' . "\n";
                            echo '                if (bol==false) totalBol = false; ' . "\n";

                        }
                    }

                    echo '                if (!totalBol) { ' . "\n";
                    echo '                  ' . $bol . ' = false; ' . "\n";
                    echo '                  submitAlert(\'#vrednost_if_' . $row1['id'] . '\', \'add\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                    echo '                } else {' . "\n";
                    echo '                  submitAlert(\'#vrednost_if_' . $row1['id'] . '\', \'remove\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                    echo '                }' . "\n";

                    echo '              } '."\n";;
                }
            // Lokacija - maps
            } elseif ($row['tip'] == 26) {

                $ime = 'vrednost_' . $row['id'] . '[]';

                echo '              var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
                echo '              if (!obj) { ' . "\n";
                echo '                ' . $bol . ' = false; ' . "\n";
                echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo '              } else { ' . "\n";
                echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo '              } ' . "\n";

            // Heatmap
            } elseif ($row['tip'] == 27) {

                $ime = 'vrednost_' . $row['id'] . '[]';

                echo '              var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
                echo '              if (!obj) { ' . "\n";
                echo '                ' . $bol . ' = false; ' . "\n";
                echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo '              } else { ' . "\n";
                echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'require\', \'' . $bol . '\', validation, false, false, false, false); ' . "\n";
                echo '              } ' . "\n";
            }

            // konec pogoja, ce je vprasanje vidno
            echo '            }     //-- if display != none ' . "\n";

            echo '           } catch (e) {} ' . "\n";

        }


        // Alert za EVOLI departmente
        if(SurveyInfo::getInstance()->checkSurveyModule('evoli_teammeter') 
            || SurveyInfo::getInstance()->checkSurveyModule('evoli_quality_climate')
            || SurveyInfo::getInstance()->checkSurveyModule('evoli_teamship_meter')
            || SurveyInfo::getInstance()->checkSurveyModule('evoli_organizational_employeeship_meter')
        )
            $evoli_teammeter = true;
        else
            $evoli_teammeter = false;

        if($evoli_teammeter){
            echo '              var obj = document.forms[\'vnos\'].elements[\'evoli_tm_department\']; ' . "\n";

            echo '              if (typeof(obj) != "undefined" && obj != null) { ' . "\n";

            echo '                  if (obj.value == 0) { ' . "\n";
            echo '                    hard = false; ' . "\n";
            echo '                    submitAlert(\'#spremenljivka_evoli_tm_department\', \'add\', \'require\', \'hard\', validation, false, false, false, false); ' . "\n";
            echo '                  } else { ' . "\n";
            echo '                    submitAlert(\'#spremenljivka_evoli_tm_department\', \'remove\', \'require\', \'hard\', validation, false, false, false, false); ' . "\n";
            echo '                  } ' . "\n";

            echo '              } ' . "\n";
        }


        // posebno testiranje za vsoto in num ce smo presegli limit
        //$sql = sisplet_query("SELECT * FROM srv_spremenljivka WHERE gru_id='".get('grupa')."' AND vsota_reminder > 0 ORDER BY vrstni_red ASC");
        $sql = sisplet_query("SELECT * FROM srv_spremenljivka WHERE gru_id='" . get('grupa') . "'  AND gru_id != '0' AND (tip='18' OR tip='7' OR tip='20' OR tip='21') ORDER BY vrstni_red ASC");
        while ($row = mysqli_fetch_array($sql)) {

            if ($row['vsota_reminder'] > 0) {

                if ($row['vsota_reminder'] == 1) {
                    $bol_vsota = 'soft';
                } else {
                    $bol_vsota = 'hard';
                }

                // najprej damo pogoj, da se uposteva, samo ce je vprasanje vidno (ker je lahko v ifu)
                //echo '            if ( $(\'#spremenljivka_'.$row['id'].'\').css(\'display\') != \'none\' ) { '			."\n";
                echo '            if ( document.getElementById(\'spremenljivka_' . $row['id'] . '\').style.display != \'none\' ) { ' . "\n";

                // vsota
                if ($row['tip'] == 18) {
                    $ime = 'spremenljivka_' . $row['id'] . '_vsota';
					
                    echo '              var obj = document.forms[\'vnos\'].elements[\'' . $ime . '\']; ' . "\n";
                    echo '              if ( (obj.value > ' . $row['vsota_limit'] . ' && !$(obj).hasClass("def") && ' . $row['vsota_limit'] . ' != 0) || (obj.value < ' . $row['vsota_min'] . ' && !$(obj).hasClass("def") && ' . $row['vsota_min'] . ' != 0) ){ ' . "\n";
                    echo '               	' . $bol_vsota . ' = false;' . "\n";
                    echo '                	vsota = false; ' . "\n";
                    echo '                  submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'limit\', \'' . $bol_vsota . '\', validation, false, true, false, false); ' . "\n";
                    echo '              } else { ' . "\n";
                    echo '                  submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'limit\', \'' . $bol_vsota . '\', validation, false, true, false, false); ' . "\n";
                    echo '              } ' . "\n";

                } // multinumber
                elseif ($row['tip'] == 20) {

                    echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'limit\', \'' . $bol_vsota . '\', validation, false, false, true, false); ' . "\n";

                    $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
                    while ($row1 = mysqli_fetch_array($sql1)) {
                        $ime = 'vrednost_' . $row1['id'];

                        echo '                var bol = false; ' . "\n";

                        echo '                for (var i=1; i<=' . $row['grids'] . '; i++) { ' . "\n";
                        echo '                    obj = document.getElementById(\'' . $ime . '_grid_\'+i); ' . "\n";

                        if ($row['num_useMax'] == 1) {
                            echo '              if (obj.value > ' . $row['vsota_limit'] . ' && obj.value != \'\' && ' . $row['num_useMax'] . ' == 1){ ' . "\n";
                            echo '               	' . $bol_vsota . ' = false; ' . "\n";
                            echo '                	num = false; ' . "\n";
                            echo '                  submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'limit\', \'' . $bol_vsota . '\', validation, false, false, true, false); ' . "\n";
                            echo '              } ' . "\n";
                        }

                        if ($row['num_useMin'] == 1) {
                            echo '              if (obj.value < ' . $row['vsota_min'] . ' && obj.value != \'\'){ ' . "\n";
                            echo '               	' . $bol_vsota . ' = false; ' . "\n";
                            echo '                	num = false; ' . "\n";
                            echo '                  submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'limit\', \'' . $bol_vsota . '\', validation, false, false, true, false); ' . "\n";
                            echo '              } ' . "\n";
                        }

                        echo '                } ' . "\n";
                    }
                } 
				// number in text
                else {
                    //za text se ne gleda value amapak value.length
                    $length = $row['tip'] == 21 ? '.length' : '';
                    $num_var = $row['tip'] == 21 ? 'text_' : '';
                    
                    $ime = 'spremenljivka_' . $row['id'] . '_vrednost_1';

                    ///echo '              var obj = document.forms[\'vnos\'].elements[\''.$ime.'\']; '					."\n";
                    echo '                var obj = document.getElementById(\'' . $ime . '\'); ' . "\n";

                    echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \''.$num_var.'limit\', \'' . $bol_vsota . '\', validation, false, false, true, false); ' . "\n";

                    if ($row['num_useMax'] == 1) {
                        echo '              if (obj.value'.$length.' > ' . $row['vsota_limit'] . ' && obj.value != \'\' && ' . $row['num_useMax'] . ' == 1){ ' . "\n";
                        echo '               	' . $bol_vsota . ' = false; ' . "\n";
                        echo '                	'.$num_var.'num = false; ' . "\n";
                        echo '                  submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \''.$num_var.'limit\', \'' . $bol_vsota . '\', validation, false, false, true, false); ' . "\n";
                        //echo '              } else { '																		."\n";
                        //echo '                  $(\'#spremenljivka_'.$row['id'].'\').removeClass(\'required\'); '			."\n";
                        echo '              } ' . "\n";
                    }

                    if ($row['num_useMin'] == 1) {
                        echo '              if (obj.value'.$length.' < ' . $row['vsota_min'] . ' && obj.value != \'\'){ ' . "\n";
                        echo '               	' . $bol_vsota . ' = false; ' . "\n";
                        echo '                	'.$num_var.'num = false; ' . "\n";
                        echo '                  submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \''.$num_var.'limit\', \'' . $bol_vsota . '\', validation, false, false, true, false); ' . "\n";
                        //echo '              } else { '																		."\n";
                        //echo '                  $(\'#spremenljivka_'.$row['id'].'\').removeClass(\'required\'); '			."\n";
                        echo '              } ' . "\n";
                    }

                    if ($row['size'] > 1) {
                        $ime = 'spremenljivka_' . $row['id'] . '_vrednost_2';

                        //echo '              var obj = document.forms[\'vnos\'].elements[\''.$ime.'\']; '				."\n";
                        echo '              var obj = document.getElementById(\'' . $ime . '\'); ' . "\n";

                        echo '                submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \''.$num_var.'limit\', \'' . $bol_vsota . '\', validation, false, false, true, false); ' . "\n";

                        if ($row['num_useMax2'] == 1) {
                            echo '              if (obj.value'.$length.' > ' . $row['num_max2'] . ' && obj.value != \'\'){ ' . "\n";
                            echo '               	' . $bol_vsota . ' = false; ' . "\n";
                            echo '                	'.$num_var.'num = false; ' . "\n";
                            echo '                  submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \''.$num_var.'limit\', \'' . $bol_vsota . '\', validation, false, false, true, false); ' . "\n";
                            //echo '              } else { '																		."\n";
                            //echo '                  $(\'#spremenljivka_'.$row['id'].'\').removeClass(\'required\'); '			."\n";
                            echo '              } ' . "\n";
                        }

                        if ($row['num_useMin2'] == 1) {
                            echo '              if (obj.value'.$length.' < ' . $row['num_min2'] . ' && obj.value != \'\'){ ' . "\n";
                            echo '               	' . $bol_vsota . ' = false; ' . "\n";
                            echo '                	'.$num_var.'num = false; ' . "\n";
                            echo '                  submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \''.$num_var.'limit\', \'' . $bol_vsota . '\', validation, false, false, true, false); ' . "\n";
                            //echo '              } else { '																		."\n";
                            //echo '                  $(\'#spremenljivka_'.$row['id'].'\').removeClass(\'required\'); '			."\n";
                            echo '              } ' . "\n";
                        }
                    }

                    if($row['tip'] == 7){
                        //Alert za mobile slider *************************************
                        echo '
                                                            //console.log(' . $row['ranking_k'] . ');
                                                            //console.log(' . $row['id'] . ');
                                            ';

                        if ($row['ranking_k'] == '1' && get('mobile') != 0) {
                            echo '
                                                            //console.log(' . $row['ranking_k'] . ');
                                                            //console.log(' . $row['id'] . ');
                                            ';

                            $slider_MaxNumLabel = ($spremenljivkaParams->get('slider_MaxNumLabel') ? $spremenljivkaParams->get('slider_MaxNumLabel') : 100);

                            echo '              if (obj.value > ' . $slider_MaxNumLabel . ' && obj.value != \'\'){ ' . "\n";
                            echo '               	' . $bol_vsota . ' = false; ' . "\n";
                            echo '                	num = false; ' . "\n";
                            echo '                  submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'limit\', \'' . $bol_vsota . '\', validation, false, false, true, false); ' . "\n";
                            //echo '              } else { '																		."\n";
                            //echo '                  $(\'#spremenljivka_'.$row['id'].'\').removeClass(\'required\'); '			."\n";
                            echo '              } ' . "\n";
                        }
                    }
                }

                // konec pogoja, ce je vprasanje vidno
                echo '            }     //-- if display != none ' . "\n";

            }

        }
		
		
		// posebno testiranje ce respondent ni izbral minimalnega stevila checkbox-ov
        $sql = sisplet_query("SELECT * FROM srv_spremenljivka WHERE gru_id='" . get('grupa') . "' AND gru_id != '0' AND (tip='2') ORDER BY vrstni_red ASC");
		
        while ($row = mysqli_fetch_array($sql)) {
			$spremenljivkaParams = new enkaParameters($row['params']);
			$checkbox_min_limit = ($spremenljivkaParams->get('checkbox_min_limit') ? $spremenljivkaParams->get('checkbox_min_limit') : 0);
			$checkbox_min_limit_reminder = ($spremenljivkaParams->get('checkbox_min_limit_reminder') ? $spremenljivkaParams->get('checkbox_min_limit_reminder') : 0);
			
			if ($checkbox_min_limit > 0) {	//ce je minimalni limit nastavljen
				
				if ($checkbox_min_limit_reminder == 1) {
                    $bol_min_limit = 'soft';
                } else {
                    $bol_min_limit = 'hard';
                }
				
				// najprej damo pogoj, da se uposteva, samo ce je vprasanje vidno (ker je lahko v ifu)
				echo '            if ( document.getElementById(\'spremenljivka_' . $row['id'] . '\').style.display != \'none\' ) { ' . "\n";
				
					echo '            var checkbox_min_limit = '.$checkbox_min_limit.'; ' . "\n";
					echo '            var checkbox_min_limit_reminder = '.$checkbox_min_limit_reminder.'; ' . "\n";			
                    
                    //ce je checkbox in ni drag drop, preveri, koliko je oznacenih odgovorov
					if($row['tip'] == 2 && ($row['orientation'] != 8 || get('mobile') == 1)){	

						echo '            obj = document.forms[\'vnos\'].elements[\'vrednost_\'+'.$row['id'].'+\'[]\']; ' . "\n";
						echo '            var len = obj.length; ' . "\n";
						echo '            var count = 0; ' . "\n";
						echo '            for (i=0; i<len; i++){ ' . "\n";
						echo '            	if (obj[i].checked){ ' . "\n";
						echo '            		count++; ' . "\n";
						echo '            	} ' . "\n";
						echo '            } ' . "\n";
						echo '			if (count < checkbox_min_limit){ 
											checkbox_min_limit_alert = true;
											submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'checkbox_min_limit\', \'' . $bol_min_limit . '\', validation, false, true, false, false);
										}else{
											submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'checkbox_min_limit\', \'' . $bol_min_limit . '\', validation, false, true, false, false);
										} ' . "\n";
                    }
                    //ce je check box in drag drop, preveri, koliko je oznacenih odgovorov
                    elseif($row['tip'] == 2 && $row['orientation'] == 8){	
	 					echo '
							var prisotno = $("#half2_frame_dropping_' . $row['id'] . '").children("div").attr("value");
							var len = $("#half2_frame_dropping_' . $row['id'] . '").children().length;
							
							if(len < checkbox_min_limit){
								checkbox_min_limit_alert = true;								
								submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'add\', \'checkbox_min_limit\', \'' . $bol_min_limit . '\', validation, false, true, false, false);
							}else{
								submitAlert(\'#spremenljivka_' . $row['id'] . '\', \'remove\', \'checkbox_min_limit\', \'' . $bol_min_limit . '\', validation, false, true, false, false);
							}							
						';		
					}
				// konec pogoja, ce je vprasanje vidno
				echo '}     //-- if display != none ' . "\n";
			}
		}
		
		
        if (isset($_GET['disablealert']) && $_GET['disablealert'] == 1) {
            echo '           vsota = true; ' . "\n";
            echo '           hard = true;  ' . "\n";
            echo '           soft = true;  ' . "\n";
        }

        echo ' if ( validation ) {    ';

        // validacija
        $sqlv = sisplet_query("SELECT v.* FROM srv_validation v, srv_spremenljivka s WHERE v.spr_id=s.id AND s.gru_id='" . get('grupa') . "' AND s.gru_id != '0' AND v.reminder>'0'");
        while ($rowv = mysqli_fetch_array($sqlv)) {

			if ($rowv['reminder'] == 1) {
                $bol = 'soft';
            } else {
                $bol = 'hard';
            }

			// Ce imamo vklopljene napredne parapodatke zabelezimo opozorilo
	        if(SurveyAdvancedParadataLog::getInstance()->paradataEnabled()){
				echo 'odstrani_opozorilo(\'#spremenljivka_' . $rowv['spr_id'] . '\', false, false, true); ';
			}       
			
			echo 'submitAlert(\'#spremenljivka_' . $rowv['spr_id'] . '\', \'remove\', \'validation\', \'' . $bol . '\', validation, false, false, false, true); '; 
        }
		
        if (mysqli_num_rows($sqlv) > 0) mysqli_data_seek($sqlv, 0);
		
        while ($rowv = mysqli_fetch_array($sqlv)) {
			if ($rowv['reminder'] == 1) {
			    $bol = 'soft';
			} else {
			    $bol = 'hard';
			}

			?>
			if ( <? $this->generateCondition($rowv['if_id']); ?> ) {

			<?
			// Ce imamo vklopljene napredne parapodatke zabelezimo opozorilo
			if (SurveyAdvancedParadataLog::getInstance()->paradataEnabled()){
				?>
					<?= ' dodaj_opozorilo_val(\'' . $bol . '\', \'#spremenljivka_' . $rowv['spr_id'] . '\'); ' ?>
				<?
			}?>

			<?=	' submitAlert(\'#spremenljivka_' . $rowv['spr_id'] . '\', \'add\', \'validation\', \'' . $bol . '\', validation, \'' . $rowv['reminder_text'] . '\', false, false, true); ' ?>

			}
			<?
        }

        echo '        } else {               ';
            
        //alert za preseg limita vsote
        echo '           if (!vsota) { ' . "\n";
        echo '            if (!hard) { ' . "\n";
        echo '              alert(\'' . $srv_remind_sum_hard . '\'); ' . "\n";
        echo '              return; ' . "\n";
        echo '            } else if (!soft) { ' . "\n";
        echo '              if (!confirm(\'' . $srv_remind_sum_soft . '\')) { ' . "\n";
        echo '                return; ' . "\n";
        echo '              } ' . "\n";
        echo '            } ' . "\n";
        echo '           } ' . "\n";

        //alert za preseg limita stevila
        echo '           if (!num) { ' . "\n";
        echo '            if (!hard) { ' . "\n";
        echo '              alert(\'' . $srv_remind_num_hard . '\'); ' . "\n";
        echo '              return; ' . "\n";
        echo '            } else if (!soft) { ' . "\n";
        echo '              if (!confirm(\'' . $srv_remind_num_soft . '\')) { ' . "\n";
        echo '                return; ' . "\n";
        echo '              } ' . "\n";
        echo '            }	' . "\n";
        echo '           } ' . "\n";
        
        //alert za preseg limita besedila
        echo '           if (!text_num) { ' . "\n";
        echo '            if (!hard) { ' . "\n";
        echo '              alert(\'' . $srv_remind_text_num_hard . '\'); ' . "\n";
        echo '              return; ' . "\n";
        echo '            } else if (!soft) { ' . "\n";
        echo '              if (!confirm(\'' . $srv_remind_text_num_soft . '\')) { ' . "\n";
        echo '                return; ' . "\n";
        echo '              } ' . "\n";
        echo '            }	' . "\n";
        echo '           } ' . "\n";
		
		//alert za premalo izbranih checkboxov
        echo '           if (checkbox_min_limit_alert) { ' . "\n";
        echo '            if (checkbox_min_limit_reminder == 2) { ' . "\n";
        echo '              alert(\'' . $srv_remind_checkbox_min_violated_hard . '\'); ' . "\n";
        echo '              return; ' . "\n";
        echo '            } else if (checkbox_min_limit_reminder == 1) { ' . "\n";
        echo '              if (!confirm(\'' . $srv_remind_checkbox_min_violated_soft . '\')) { ' . "\n";
        echo '                return; ' . "\n";
        echo '              } ' . "\n";
        echo '            }	' . "\n";
        echo '           } ' . "\n";

        //alert za ostale tipe
        echo '           if(text_num && num && vsota) {	' . "\n";
        echo '            if (!hard) { ' . "\n";
        echo '              if(\'' . $require . '\' == \'require2\') ' . "\n";
        echo '              	alert(\'' . $srv_remind_hard_99 . '\'); ' . "\n";
        echo '              else if(\'' . $require . '\' == \'require3\') ' . "\n";
        echo '              	alert(\'' . $srv_remind_hard_98 . '\'); ' . "\n";
        echo '              else if(\'' . $require . '\' == \'require4\') ' . "\n";
        echo '              	alert(\'' . $srv_remind_hard_97 . '\'); ' . "\n";
        echo '              else if(\'' . $require . '\' == \'require5\') ' . "\n";
        echo '              	alert(\'' . $srv_remind_hard_multi . '\'); ' . "\n";
        echo '              else ' . "\n";
        echo ' 		            alert(\'' . $srv_remind_hard . '\'); ' . "\n";
        echo '              return; ' . "\n";
        echo '            } else if (!soft) { ' . "\n";
        echo '              if(\'' . $require . '\' == \'require2\'){ ' . "\n";
        echo '             		if (!confirm(\'' . $srv_remind_soft_99 . '\')) { ' . "\n";
        echo '                		return; ' . "\n";
        echo '              	} ' . "\n";
        echo '              }else if(\'' . $require . '\' == \'require3\'){ ' . "\n";
        echo '             		if (!confirm(\'' . $srv_remind_soft_98 . '\')) { ' . "\n";
        echo '                		return; ' . "\n";
        echo '              	} ' . "\n";
        echo '              }else if(\'' . $require . '\' == \'require4\'){ ' . "\n";
        echo '             		if (!confirm(\'' . $srv_remind_soft_97 . '\')) { ' . "\n";
        echo '                		return; ' . "\n";
        echo '              	} ' . "\n";
        echo '              }else if(\'' . $require . '\' == \'require5\'){ ' . "\n";
        echo '             		if (!confirm(\'' . $srv_remind_soft_multi . '\')) { ' . "\n";
        echo '                		return; ' . "\n";
        echo '              	} ' . "\n";
        echo '              }else{ ' . "\n";
        echo '             		 if (!confirm(\'' . $srv_remind_soft . '\')) { ' . "\n";
        echo '                		return; ' . "\n";
        echo '              	} ' . "\n";
        echo '              } ' . "\n";
        echo '            }	' . "\n";
        echo '          } ' . "\n";


        // validacija
        $sqlv = sisplet_query("SELECT v.* FROM srv_validation v, srv_spremenljivka s WHERE v.spr_id=s.id AND s.gru_id='" . get('grupa') . "' AND s.gru_id != '0'");
        while ($rowv = mysqli_fetch_array($sqlv)) {

            ?>
            if ( <? $this->generateCondition($rowv['if_id']); ?> ) {

            <? if ($rowv['reminder'] == 1) {

                ?> $('#spremenljivka_<?= $rowv['spr_id'] ?>').addClass('required'); <?
                /*?> if ( ! confirm('<?= $rowv['reminder_text'] ?>') ) return; else $('#spremenljivka_<?= $rowv['spr_id'] ?>').removeClass('required'); <?*/
                /*?> console.log('Soft validacija se je sprozila za spremenljivko: <?= $rowv['spr_id'] ?>'); if ( ! confirm('<?= $rowv['reminder_text'] ?>') ) return; else $('#spremenljivka_<?= $rowv['spr_id'] ?>').removeClass('required'); <?*/
                ?> if ( ! confirm('<?= $rowv['reminder_text'] ?>') ) return; else $('#spremenljivka_<?= $rowv['spr_id'] ?>').removeClass('required'); <?

            } elseif ($rowv['reminder'] == 2) {

                ?> $('#spremenljivka_<?= $rowv['spr_id'] ?>').addClass('required'); <?
                /*?> alert('<?= $rowv['reminder_text'] ?>'); return; <?*/
                /*?> console.log('Hard validacija se je sprozila za spremenljivko: <?= $rowv['spr_id'] ?>'); alert('<?= $rowv['reminder_text'] ?>'); return; <?*/
                ?> alert('<?= $rowv['reminder_text'] ?>'); return; <?

            } ?>

            }
            <?

        }

        // preverjanje, da vnese inicialke
        $question_resp_comment_inicialke = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_inicialke');
        $question_resp_comment_inicialke_alert = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_inicialke_alert');
        if ($question_resp_comment_inicialke == 1 && $question_resp_comment_inicialke_alert == 2) {
            ?>
            if ( !check_inicialke() ) {
            alert('<?= $lang['srv_enter_inicialke'] ?>');
            preview_popup_open();
            return;
            }
            <?php
        } elseif ($question_resp_comment_inicialke == 1 && $question_resp_comment_inicialke_alert == 1) {
            ?>
            if ( !check_inicialke() ) {
            if ( !confirm('<?= $lang['srv_enter_inicialke2'] ?>') ) {
            preview_popup_open();
            return;
            }
            }
            <?php
        }

        echo '          if (parent_if == undefined) {  ' . "\n";

        // Ce imamo vklopljene napredne parapodatke zabelezimo cas post-a
        if(SurveyAdvancedParadataLog::getInstance()->paradataEnabled() && SurveyAdvancedParadataLog::getInstance()->collectPostTime()){
            echo 'logEvent(\'page\', \'unload_page\', function(){
                $("form[name=vnos]").submit(); 
            });';
        }
        else{
            echo '            $("form[name=vnos]").submit(); ' . "\n";
        }

        echo '          } else { ' . "\n";
        echo '            $.post($(\'form[name=vnos]\').attr(\'action\'), $(\'form[name=vnos]\').serialize(), function () { ' . "\n";
        echo '              $.post(\'' . $site_url . 'main/survey/ajax.php?a=grupa_for_if\', {anketa:' . get('anketa') . ', parent_if: parent_if}, function (data) { ' . "\n";
        echo '                window.location.href = \'' . SurveyInfo::getSurveyLink(false, false) . '&grupa=\'+data+\'' . Header::getSurveyParams() . get('cookie_url') . '\';       ' . "\n";
        echo '              }) ' . "\n";
        echo '            } ) 	' . "\n";
        echo '          } ' . "\n";

        // if validation else
        echo '          } ';

        echo '          } ' . "\n";
		//echo 'spr_id_indeks = 0; ' . "\n";	//za sledenje opozoril
        echo '        </script>	' . "\n";


        $sqlG = sisplet_query("SELECT spol FROM srv_glasovanje WHERE ank_id='" . get('anketa') . "'");
        $rowG = mysqli_fetch_array($sqlG);

        $sql1 = sisplet_query("SELECT MAX(vrstni_red) AS vrstni_red FROM srv_grupa WHERE ank_id = '" . get('anketa') . "'");
        $row1 = mysqli_fetch_array($sql1);

        $rowa = SurveyInfo::getInstance()->getSurveyRow();

        //namesto naprej in nazaj se pri glasovanju izrise gumb potrdi oz. gumba moski/zenska pri izbiri spola
        if ($rowa['survey_type'] == 0) {
			
            if (!get('printPreview')) {
                if ($rowG['spol'] == 0) {
					
					$srv_potrdi = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_potrdi'.$_lang);
					if ($srv_potrdi == '') $srv_potrdi = $lang['srv_potrdi'];
					
                    echo '  <div class="buttons">
							<input class="next" type="submit" value="' . $srv_potrdi . '" onclick="submitForm(); return false;">
							</div>' . "\n";
                } else {
                    echo '  <div class="buttons">
							<input class="next" type="submit" name="submit" value="' . $lang['glasovanja_spol_moski'] . '" onclick="submitForm(); return false;">
							<input class="next" type="submit" name="submit" value="' . $lang['glasovanja_spol_zenska'] . '" onclick="submitForm(); return false;">
							</div>' . "\n";
                }
            }

            //namesto naprej in nazaj izrise pri formi gumb poslji
        } elseif ($rowa['survey_type'] == 1) {

            SurveySetting::getInstance()->Init(get('anketa'));

            $srv_nextpage = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_forma_send' . $_lang);
            if ($srv_nextpage == '') $srv_nextpage = $lang['srv_forma_send'];

            if (!get('printPreview')) {
                echo '  <div class="buttons">';
                echo '	<input class="next" type="submit" value="' . $srv_nextpage . '" onclick="submitForm(); return false;">
						</div>' . "\n";
            }

        } else {

            SurveySetting::getInstance()->Init(get('anketa'));

            $row = SurveyInfo::getInstance()->getSurveyRow();
            $sqlg = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE ID = '" . get('grupa') . "'");
            $rowg = mysqli_fetch_array($sqlg);

			// Smo na zadnji strani
            if ($row1['vrstni_red'] == $rowg['vrstni_red']) {
		
				// Dodatno preverimo ce imamo loop
				if(isset($_GET['loop_id']) && $_GET['loop_id'] > 0){				
					
					// Ce obstaja naslednji loop izpisemo "naslednja stran" in ne "zadnja stran"
					if(Find::getInstance()->findNextLoopId() > 0){
						$srv_nextpage = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_nextpage' . $_lang);
						if ($srv_nextpage == '') $srv_nextpage = $lang['srv_nextpage'];
					}
					else{
						$srv_nextpage = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_lastpage' . $_lang);
						if ($srv_nextpage == '') $srv_nextpage = $lang['srv_lastpage'];					
					}
				}
				else{
					$srv_nextpage = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_lastpage' . $_lang);
					if ($srv_nextpage == '') $srv_nextpage = $lang['srv_lastpage'];
				}
            } 
			else {
                $srv_nextpage = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_nextpage' . $_lang);
                if ($srv_nextpage == '') $srv_nextpage = $lang['srv_nextpage'];
            }

            $srv_prevpage = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_prevpage' . $_lang);
            if ($srv_prevpage == '') $srv_prevpage = $lang['srv_prevpage'];

            $display_backlink = SurveySetting::getInstance()->getSurveyMiscSetting('display_backlink');

            if (!get('printPreview')) {
                echo '<div class="buttons" id="buttons_gru_' . get('grupa') . '">';
                //echo '			<input class="prev" type="submit" value="'.$lang['srv_prevpage'].'" onclick="document.location.href=\'index.php?anketa='.get('anketa').''.($this->findPrevGrupa()>0?'&grupa='.$this->findPrevGrupa().'':'').get('cookie_url').'\'; return false;">
                if ($row['show_intro'] == 1 || $rowg['vrstni_red'] > 1) {
                    #echo '<input class="prev" type="button" value="'.$srv_prevpage.'" onclick="history.back()">';

                    if (!SurveyInfo::getInstance()->checkSurveyModule('slideshow')) {

                        // Posebej za WebSM anketo - back naredimo tako, da poiscemo prejsnjo stran
                        if (get('anketa') == get('webSMSurvey') && Common::checkModule('websmsurvey') == '1') {

                            $grupa = Find::findPrevGrupa();
                            $grupa = ($grupa > 0) ? '&grupa=' . $grupa : '';

                            $lang_s = (isset($_GET['language'])) ? '&language=' . $_GET['language'] : '';
                            $language = save('language', $lang_s, 1);

                            $link = SurveyInfo::getSurveyLink(false, false) . $grupa . $language;

                            echo '<input class="prev" type="button" value="' . $srv_prevpage . '" onclick="location.href=\'' . $link . '\';">';
                        } elseif ($display_backlink != '0') {
                            echo '<input class="prev" type="button" value="' . $srv_prevpage . '" onclick="javascript:history.go(-1)">';
                        }
                    } else {

                        # če smo v slideshowu prikazujemo gumb nazaj ali naprej na zahtevo
                        $ss = new SurveySlideshow(get('anketa'));
                        $ss_setings = $ss->getSettings();

                        if ($ss_setings['back_btn'] == 1) {
                            echo '<input class="prev" type="button" value="' . $srv_prevpage . '" onclick="javascript:history.go(-1)">';
                        }
                    }
                }

                if (SurveyInfo::getInstance()->checkSurveyModule('slideshow')) {
                    # če smo v prezentaciji in imamo nastavljen pause button
                    $ss = new SurveySlideshow(get('anketa'));
                    $ss_setings = $ss->getSettings();

                    if ($ss_setings['pause_btn'] == 1) {
                        echo '<input id="btn_pause_on" class="pause" type="button" value="' . $lang['srv_slideshow_btn_pause_on'] . '" onclick="slide_timer_pause_ON();">';
                        echo '<input id="btn_pause_off" class="pause display_none" type="button" value="' . $lang['srv_slideshow_btn_pause_off'] . '" onclick="slide_timer_pause_OFF();">';
                    }
                }
                if (!SurveyInfo::getInstance()->checkSurveyModule('slideshow')) {
                    echo ' <input class="next" type="submit" value="' . $srv_nextpage . '" onclick="submitForm(); return false;">';
                } else {
                    # če smo v slideshowu prikazujemo gumb nazaj ali naprej na zahtevo
                    $ss = new SurveySlideshow(get('anketa'));
                    $ss_setings = $ss->getSettings();
                    if ($ss_setings['next_btn'] == 1) {
                        echo ' <input class="next" type="submit" value="' . $srv_nextpage . '" onclick="submitForm(); return false;">';
                    }
                }

                echo '</div>' . "\n";
            }
        }
    }

    /**
     * @desc poklice generatorje JS kode za branching
     */
    public function generateBranchingJS()
    {
        echo '<script> ' . "\n";

        echo '  function checkBranching () { ' . "\n";

        $this->generateComputeJS();

        // ce je preverjanje pogojev izklopljeno
        if ($_GET['disableif'] != 1) {

            $this->generateBranching();

            $this->generateVrednostIf();
        }

        // sprotna validacija v balonckih
        echo '      submitForm(undefined, true);                ';
        
        // Popravek footerja pri temi Bled
        $row = SurveyInfo::getSurveyRow();
        if($row['skin'] == 'Bled'){
            echo '      footerBled(); ' . "\n";
        }

        echo '  }  //-- function checkBranching() ' . "\n";

		
        echo '  checkBranching(); ' . "\n";

        echo '</script> ' . "\n";     
    }

    /**
     * zgenerira koda za compute spremenljivke, ki si vrednost napolnijo samodejno iz calculation izraza
     *
     */
    public function generateComputeJS()
    {

        if (get('generateComputeJS') != '') {

            echo 'try { ' . "\n";
            echo '  ' . get('generateComputeJS') . "\n";
            echo '} catch (e) {} ' . "\n";
        }

    }

    /**
     * za podano spremenljivko zgenerira compute kodo, ki se shrani v začasno spremenljivko, da se na koncu izpiše s funkcijo generateComputeJS()
     *
     * @param mixed $spremenljivka
     */
    public function generateCompute($spremenljivka)
    {

        // vrednost kalkulacije za ife
        add('generateComputeJS', "document.getElementById('vrednost_" . $spremenljivka . "').value = " . $this->generateCalculationJS(-$spremenljivka) . "; \n\r");

        // vrednost se zapise v class .data-piping-SPR_ID za data-piping da se v zivo refresha
        add('generateComputeJS', " var val; if ( ! isNaN(document.getElementById('vrednost_" . $spremenljivka . "').value) ) val = document.getElementById('vrednost_" . $spremenljivka . "').value; else val = ''; \n\r");
        add('generateComputeJS', " $('.data-piping-$spremenljivka').html( val ); \n\r");
    }

    /**
     * @desc zgenerira kodo za IFe na vrednostih (podifi)
     */
    public function generateVrednostIf()
    {

        SurveySetting::getInstance()->Init(get('anketa'));
        $mobile_tables = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_tables');

        $sql = sisplet_query("SELECT v.*, s.tip AS tip, s.id AS spr_id, s.dynamic_mg AS dynamic_mg, s.vsota_limit, s.design, s.params FROM srv_vrednost v, srv_spremenljivka s WHERE v.if_id>'0' AND v.spr_id=s.id AND s.gru_id='" . get('grupa') . "' AND s.gru_id != '0'");
        while ($row = mysqli_fetch_array($sql)) {

            // Ce je odgovor skrit ga nikoli ne prikazemo in preskocimo kar celotno proceduro za to vrednost
            if($row['hidden'] == '1'){
                continue;
            }

            //stavek za pobiranje informacij o tipu in orienataciji spremenljivke, potrebno za nadaljnje notranje pogoje za drag and drop
            $sqldd = sisplet_query("SELECT id, enota, orientation FROM srv_spremenljivka WHERE id = '$row[spr_id]'");
            while ($rowdd = mysqli_fetch_array($sqldd)) {
                $spremenljivka_dd = $rowdd['id'];
                $orientation_dd = $rowdd['orientation'];
                $enota_dd = $rowdd['enota'];
            }

            // vsota - treba je se enkrat pognati racunanje
            if ($row['tip'] == 18) {
                $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$row[spr_id]' AND vrstni_red>0");
                $max = mysqli_num_rows($sql1);
                $sum = '   calcSum(\'' . $row['spr_id'] . '\', \'' . $max . '\', \'' . $row['vsota_limit'] . '\'); ' . "\n";
            } 
            else
                $sum = '';


            // dinamicni multigrid (za mobilne naprave)
            if ($row['tip'] == 6 && $enota_dd != 9 && $enota_dd != 3 && $row['dynamic_mg'] > 0) {

                echo ' try { 																										' . "\n";
                echo '  if ( ';
                $this->generateCondition($row['if_id']);
                echo ' ) { ' . "\n";

                echo '   dynamicMultigridSwitchIf(1, ' . $row['id'] . ', ' . $row['spr_id'] . '); ' . "\n";

                echo '  } else { ' . "\n";

                echo '   dynamicMultigridSwitchIf(0, ' . $row['id'] . ', ' . $row['spr_id'] . '); ' . "\n";

                echo '  } ' . "\n";

                echo ' } catch (e) {} ' . "\n";
            }
            // Navaden multigrid
            else if ($row['tip'] == 6 && $enota_dd != 9 && $enota_dd != 3)  {
                                
                echo ' try { 																										' . "\n";
                
                echo '  if ( ';
                $this->generateCondition($row['if_id']);
                echo ' ) { ' . "\n";

                // Element je viden
                echo '  document.getElementById(\'vrednost_if_' . $row['id'] . '\').style.display = \'\'; ' . "\n";

                echo '  var el = document.getElementById("branch_spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '")' . "\n";
                echo '  el.parentNode.removeChild( el );' . "\n";

                // Element ni viden
                echo '  } else { ' . "\n";

                // ker je element neviden, dodamo novega hidz vrednostjo -2
                echo '  var el = document.getElementById(\'vrednost_if_' . $row['id'] . '\');' . "\n";
                echo '  var hiddenEl = document.getElementById("branch_spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '")' . "\n";
                echo '  var parent = el.parentNode.parentNode.parentNode;' . "\n";
               
                // V kolikor je hidden polje že postavljeno potem ga ponovno ne ustvarjamo -> se izognemo podvojenim vpisom v bazi
                echo '  if(!hiddenEl){' . "\n"; 
                echo        'var newElement = document.createElement(\'input\');' . "\n";
                echo '      newElement.setAttribute("id", "branch_spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '");' . "\n";
                echo '      newElement.setAttribute("name", "cond_vrednost_' . $row['id'] . '");' . "\n";
                echo '      newElement.setAttribute("value", "-2");' . "\n";
                echo '      newElement.setAttribute("type", "hidden");' . "\n";
                echo '      parent.appendChild(newElement);' . "\n";
                echo '  }' . "\n";

                echo '   document.getElementById(\'vrednost_if_' . $row['id'] . '\').style.display = \'none\'; ' . "\n";

                echo '  } ' . "\n";

                echo ' } catch (e) {} ' . "\n";
            }
            // checkbox in ne drag and drop
            else if ($row['tip'] == 2 && ($row['orientation'] != 8 || get('mobile') == 1)) {

                if ($spremenljivka_dd == $row['spr_id'] && ($orientation_dd == 8 || $enota_dd == 9)) {//ce je drag and drop
                    echo ' try {																									' . "\n";
                    echo '  if ( ';
                    $this->generateCondition($row['if_id']);
                    echo ' ) { ' . "\n";

                    echo '   document.getElementById(\'spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '\').style.display = \'\'; ' . "\n";

                    echo '  } else { ' . "\n";
                    echo '   document.getElementById(\'spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '\').style.display = \'none\'; ' . "\n";

                    echo '  } ' . "\n";

                    echo ' } catch (e) {} ' . "\n";
                } 
                else {
                    echo ' try { 																										' . "\n";
                    
                    echo '  if ( ';
                    $this->generateCondition($row['if_id']);
                    echo ' ) { ' . "\n";

                    // Element je viden
                    echo '  document.getElementById(\'vrednost_if_' . $row['id'] . '\').style.display = \'\'; ' . "\n";

                    echo '  var el = document.getElementById("branch_spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '")' . "\n";
                    echo '  el.parentNode.removeChild( el );' . "\n";

                    // Element ni viden
                    echo '  } else { ' . "\n";

                    // ker je element neviden, dodamo novega hidz vrednostjo -2
                    echo '  var el = document.getElementById(\'spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '\');' . "\n";
                    echo '  var hiddenEl = document.getElementById("branch_spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '")' . "\n";
                    echo '  var parent = el.parentNode.parentNode.parentNode;' . "\n";
                   
                    // V kolikor je hidden polje že postavljeno potem ga ponovno ne ustvarjamo -> se izognemo podvojenim vpisom v bazi
                    echo '  if(!hiddenEl){' . "\n"; 
                    echo        'var newElement = document.createElement(\'input\');' . "\n";
                    echo '      newElement.setAttribute("id", "branch_spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '");' . "\n";
                    echo '      newElement.setAttribute("name", "cond_vrednost_' . $row['spr_id'] . '[]");' . "\n";
                    echo '      newElement.setAttribute("value", "' . $row['id'] . '");' . "\n";
                    echo '      newElement.setAttribute("type", "hidden");' . "\n";
                    echo '      parent.appendChild(newElement);' . "\n";
                    echo '  }' . "\n";

                    echo '   document.getElementById(\'vrednost_if_' . $row['id'] . '\').style.display = \'none\'; ' . "\n";

                    echo '  } ' . "\n";

                    echo $sum;

                    echo ' } catch (e) {} ' . "\n";
                }

            } 
            // roleta
            else if ($row['tip'] == 3) {
                echo ' try { 																										' . "\n";
                echo '  if ( ';
                $this->generateCondition($row['if_id']);
                echo ' ) { ' . "\n";

                echo '   document.getElementById(\'vrednost_' . $row['spr_id'] . '_chzn_o_' . $row['vrstni_red'] . '\').style.display = \'\'; ' . "\n";

                echo '  } else { ' . "\n";
                echo '   document.getElementById(\'vrednost_' . $row['spr_id'] . '_chzn_o_' . $row['vrstni_red'] . '\').style.display = \'none\'; ' . "\n";

                echo '  } ' . "\n";

                echo $sum;

                echo ' } catch (e) {} ' . "\n";
            } 
            // ranking premikanje
            else if ($row['tip'] == 17 && $row['design'] == 2 && get('mobile') == '0') {
                echo ' try { 																										' . "\n";
                echo '  if ( ';
                $this->generateCondition($row['if_id']);
                echo ' ) { ' . "\n";

                echo '   document.getElementById(\'spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '\').style.display = \'\'; ' . "\n";

                echo '  } else { ' . "\n";
                
                echo '   $(\'#spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '\').hide(); ' . "\n";                 
                echo '   $(\'#frame_spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '\').hide(); ' . "\n";                 

                echo '  } ' . "\n";

                echo $sum;

                echo ' } catch (e) {} ' . "\n";
            } 
            // ranking prestavljanje
            else if ($row['tip'] == 17 && $row['design'] == 0 && get('mobile') == '0') {
                
                echo ' try { 																										' . "\n";
                echo '  if ( ';
                $this->generateCondition($row['if_id']);
                echo ' ) { ' . "\n";

                echo '   document.getElementById(\'spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '\').style.display = \'\'; ' . "\n";
                
                echo '  } else { ' . "\n";
                
                echo '   $(\'#spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '\').hide(); ' . "\n";                 
                echo '   $(\'#frame_spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '\').hide(); ' . "\n";                 

                echo '  } ' . "\n";


                // Pri rankingu prestejemo in skrijemo tudi prazne okvirje na desni
                // Prestejemo skrite
                echo ' var count = $(\'#prestavljanje_'.$row['spr_id'].'\').find(\'.ranking:hidden\').length;   ' . "\n"; 

                // Prikazemo vse
                echo ' $(\'#prestavljanje_'.$row['spr_id'].'\').find(\'.dropholder\').find(\'ul li\').show();   ' . "\n"; 

                // Skrijemo toliko okvirjev kolikor je skritih elementov
                echo ' for(var i=0; i<count; i++){ $(\'#prestavljanje_'.$row['spr_id'].'\').find(\'.dropholder\').find(\'ul li:visible\').last().hide(); }' . "\n"; 


                echo $sum;

                echo ' } catch (e) {} ' . "\n";
            } 
            // ranking ostevilcevanje
            else if ($row['tip'] == 17 && ($row['design'] == 1 || get('mobile') != '0')) {

                echo ' try { 																										' . "\n";
                echo '  if ( ';
                $this->generateCondition($row['if_id']);
                echo ' ) { ' . "\n";

                echo '   document.getElementById(\'vrednost_if_' . $row['id'] . '\').style.display = \'\'; ' . "\n";

                echo '  } else { ' . "\n";
                echo '   document.getElementById(\'vrednost_if_' . $row['id'] . '\').style.display = \'none\'; ' . "\n";

                echo '  } ' . "\n";


                // Pri rankingu prestejemo in skrijemo tudi odvecne vrednosti v dropdownu
                // Prestejemo vidne
                echo ' var count_visible = $(\'#spremenljivka_'.$row['spr_id'].'\').find(\'.variabla:visible\').length;   ' . "\n"; 

                // Na novo napolnimo select
                echo ' $(\'#spremenljivka_'.$row['spr_id'].'\').find(\'select\').empty()' . "\n"; 
                echo ' $(\'#spremenljivka_'.$row['spr_id'].'\').find(\'select\').append(\'<option></option>\')' . "\n"; 
                echo ' for(var i=1; i<=count_visible; i++){ $(\'#spremenljivka_'.$row['spr_id'].'\').find(\'select\').append(\'<option value="\'+i+\'">\'+i+\'</option>\'); }' . "\n"; 


                echo $sum;

                echo ' } catch (e) {} ' . "\n";
            }
            else {
                if ($spremenljivka_dd == $row['spr_id'] && ($orientation_dd == 8 || $enota_dd == 9)) {//ce je drag and drop
                    echo ' try {																									' . "\n";
                    echo '  if ( ';
                    $this->generateCondition($row['if_id']);
                    echo ' ) { ' . "\n";

                    echo '   document.getElementById(\'spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '\').style.display = \'\'; ' . "\n";

                    echo '  } else { ' . "\n";
                    echo '   document.getElementById(\'spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['id'] . '\').style.display = \'none\'; ' . "\n";

                    echo '  } ' . "\n";

                    echo $sum;

                    echo ' } catch (e) {} ' . "\n";
                } 
                else {
                    echo ' try { 																										' . "\n";
                    echo '  if ( ';
                    $this->generateCondition($row['if_id']);
                    echo ' ) { ' . "\n";

                    echo '   document.getElementById(\'vrednost_if_' . $row['id'] . '\').style.display = \'\'; ' . "\n";

                    echo '  } else { ' . "\n";
                    echo '   document.getElementById(\'vrednost_if_' . $row['id'] . '\').style.display = \'none\'; ' . "\n";

                    echo '  } ' . "\n";

                    echo $sum;

                    echo ' } catch (e) {} ' . "\n";
                }
            }

            // Po notranjih pogojih prikazemo ponavljanje naslovne vrstice za tabele ce je to vklopljeno
            if($row['tip'] == 6 || $row['tip'] == 16){

                $spremenljivkaParams = new enkaParameters($row['params']);
                $grid_repeat_header = ($spremenljivkaParams->get('grid_repeat_header') ? $spremenljivkaParams->get('grid_repeat_header') : 0);        
                
                if($grid_repeat_header > 0){
                    echo ' gridRepeatHeader(\''.$grid_repeat_header.'\', \''.$row['spr_id'].'\'); ';
                }
            }
        }
    }

    /**
     * @desc zgenerira kodo za branching
     */
    public function generateBranching($parent = 0)
    {
        Cache::cache_all_srv_branching(get('anketa'));
        Cache::cache_all_srv_if(get('anketa'));

        foreach (Cache::srv_branching_parent(get('anketa'), $parent) AS $k => $row) {

            if ($row['element_if'] > 0) {

                $rowb = Cache::srv_if($row['element_if']);

                // build conditions
                //echo 'console.log("V generateBranching");';
                echo ' try { ' . "\n";
				
                echo '  if (';

                if ($rowb['tip'] == 0) {    // if

                    if ($rowb['enabled'] == 1)
                        echo 'true';
                    elseif ($rowb['enabled'] == 2)
                        echo 'false';
                    else
                        $this->generateCondition($row['element_if']);

                } else {    // blok

                    if ($rowb['enabled'] != 2)
                        echo 'true';
                    else
                        echo 'false';

                }
                echo '  ) { ' . "\n";

                // ko prikazujemo, prikazemo samo trenutni nivo
                foreach (Cache::srv_branching_parent(get('anketa'), $row['element_if']) AS $k1 => $row1) {

                    if ($row1['element_spr'] > 0) {

                        if (Helper::getGrupa($row1['element_spr']) == get('grupa')) {

                            $rowc = Model::select_from_srv_spremenljivka($row1['element_spr']);
                            if ($rowc['tip'] != 22 && $rowc['tip'] != 25) { // spremenljivka tipa compute in quota je izvzeta iz pogojev

                                echo '      try { ' . "\n";
                                echo '        document.getElementById(\'spremenljivka_' . $row1['element_spr'] . '\').style.display = "block"; ' . "\n";
                                echo '        document.getElementById(\'visible_' . $row1['element_spr'] . '\').value = \'1\'; ' . "\n";
                               
                                // Dodamo class da je vprasanje v bloku
                                echo '			$(\'#spremenljivka_' . $row1['element_spr'] . '\').addClass(\'block_child\'); ' . "\n";

                                // Dodamo class ce je vprasanje v bloku in je prikazano horizontalno
                                if (Helper::checkParentHorizontal($row1) == 1) {
                                    echo '			$(\'#spremenljivka_' . $row1['element_spr'] . '\').addClass(\'horizontal_block\'); ' . "\n";
                                }
                                // Dodamo class ce je vprasanje v bloku in je prikazano z razpiranjem
                                if (Helper::checkParentHorizontal($row1) == 2) {
                                    echo '			$(\'#spremenljivka_' . $row1['element_spr'] . '\').addClass(\'expendable_block\'); ' . "\n";
                                }

                                // Dodamo class z id-jem bloka
                                echo '			$(\'#spremenljivka_' . $row1['element_spr'] . '\').addClass(\'block_child_'.$row['element_if'].'\'); ' . "\n";
                                
                                
                                //Uros - samo za tip 26
                                //ker se mapa ne kreira vredu, ce je hidden, jo je ob prikazu treba resizat ter nastavit bounds mape
                                echo '        
                                    if('.$rowc['tip'].' == 26){
                                        //resize map, ker je zaradi display=none postala velika 0
                                        var map = document.getElementById("map_"+' . $row1['element_spr'] . ').gMap;
                                        google.maps.event.trigger(map, \'resize\');
                                        //ce je 0 ali 1 marker, centriraj kot nastavi admin, drugace prilagodi markerjem
                                        if (st_markerjev[' . $row1['element_spr'] . '] == 0){
                                            if(map.centerInMapKoordinate){
                                                map.setCenter({lat:  parseFloat(map.centerInMapKoordinate.center.lat), 
                                                    lng:  parseFloat(map.centerInMapKoordinate.center.lng)});
                                            }
                                            else
                                                centrirajMap(map.centerInMap, map);
                                        }
                                        else
                                            map.fitBounds(bounds[' . $row1['element_spr'] . ']);
                                    }';
                                
                                echo '      } catch (e) {} ' . "\n";

                            }
                        }

                    }
                }

                $this->generateBranching($row['element_if']);
				
				// Ce imamo vklopljen modul panel moramo nastaviti status panelista glede na izpolnjen if
				if(SurveyInfo::checkSurveyModule('panel') == 1){
					
					$sp = new SurveyPanel(get('anketa'));
					$panel_if = $sp->getPanelIf($row['element_if']);
					
					if($panel_if != ''){
						echo '$(\'#panel_status\').val(\''.$panel_if.'\'); ' . "\n";
					}
				}

                echo '  } else { ' . "\n";

                // ko skrivamo, skrijemo vse (tudi podnivoje) (zato klic rekurzivne funkcije)
                foreach (Helper::getElements($row['element_if']) AS $key) {

                    if (Helper::getGrupa($key) == get('grupa')) {

                        $rowc = Model::select_from_srv_spremenljivka($key);
                        if ($rowc['tip'] != 22 && $rowc['tip'] != 25) { // spremenljivka tipa compute in quota je izvzeta iz pogojev

                            echo '      try { ' . "\n";
                            echo '        document.getElementById(\'spremenljivka_' . $key . '\').style.display = "none"; ' . "\n";
                            echo '        document.getElementById(\'visible_' . $key . '\').value = \'0\'; ' . "\n";
                           
                            // Dodamo class da je vprasanje v bloku
                            echo '			$(\'#spremenljivka_' . $row1['element_spr'] . '\').addClass(\'block_child\'); ' . "\n";

                            // Dodamo class ce je vprasanje v bloku in je prikazano horizontalno
                            if (Helper::checkParentHorizontal($row) == 1) {
                                echo '			$(\'#spremenljivka_' . $key . '\').addClass(\'horizontal_block\'); ' . "\n";
                            }
                            // Dodamo class ce je vprasanje v bloku in je prikazano z razpiranjem
                            if (Helper::checkParentHorizontal($row) == 2) {
                                echo '			$(\'#spremenljivka_' . $key . '\').addClass(\'expendable_block\'); ' . "\n";
                            }

                            // Dodamo class z id-jem bloka
                            echo '			$(\'#spremenljivka_' . $key . '\').addClass(\'block_child_' . $row['element_if'] . '\'); 			' . "\n";
                            echo '      } catch (e) {} ' . "\n";

                        }
                    }
                }

                echo '  } ' . "\n";

                echo ' } catch (e) {} ' . "\n";


                // RANDOMIZACIJA VSEBINE BLOKA
                // Randomiziramo vprasanja v bloku (ce imamo to vklopljeno)
                if ($rowb['random'] >= 0) {

                    $questions = [];

                    foreach (Cache::srv_branching_parent(get('anketa'), $rowb['id']) AS $key => $val) {
                        if ($val['element_spr'] > 0) {
                            $questions[] = $val['element_spr'];
                        }
                    }

                    // Ce imamo nastavljen prikaz samo dolocenega stevila vprasanj
                    $spr_count = ($rowb['random'] > 0) ? $rowb['random'] : count($questions);

                    // Napolnimo seed za posameznega respondenta (da dobi vsakic isti vrstni red)
                    mt_srand((int)get('usr_id') + (int)$rowb['id']);

                    // Zgeneriramo random vrstni red
                    $order = array_map(create_function('$val', 'return mt_rand();'), range(1, count($questions)));
                    array_multisort($order, $questions);

                    $order_in_block = json_encode($questions);

                    echo ' blockRandomizeQuestions(\''.$rowb['id'].'\', \''.$order_in_block.'\', \''.get('usr_id').'\', \''.$spr_count.'\');	' . "\n";
                }
                // Randomiziramo bloke v bloku (ce imamo to vklopljeno)
                elseif ($rowb['random'] == -2) {

                    $blocks = [];

                    foreach (Cache::srv_branching_parent(get('anketa'), $rowb['id']) AS $key => $val) {
                        if ($val['element_if'] > 0) {

                            $if_el = Cache::srv_if($val['element_if']);

                            // blok
                            if ($if_el['tip'] == 1) {
                                $blocks[] = $if_el['id'];
                            }
                        }
                    }

                    // Napolnimo seed za posameznega respondenta (da dobi vsakic isti vrstni red)
                    mt_srand((int)get('usr_id') + (int)$rowb['id']);

                    // Zgeneriramo random vrstni red
                    $order = array_map(create_function('$val', 'return mt_rand();'), range(1, count($blocks)));
                    array_multisort($order, $blocks);

                    $order_in_block = json_encode($blocks);

                    echo ' blockRandomizeBlocks(\'' . $rowb['id'] . '\', \'' . $order_in_block . '\', \'' . get('usr_id') . '\');	' . "\n";
                }
            }            
        }

        // dodamo crte pri blokih, kjer so vprasanja postavljena horizontalno
        echo ' blockHorizontalLine();	' . "\n";
    }

    /**
     * @desc zgenerira pogoje za JS branching
     */
    public function generateCondition($if)
    {
        $rowa = SurveyInfo::getInstance()->getSurveyRow();

		$echo = '';
        $sql = Cache::srv_condition($if);
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        $i = 0;
        while ($row = mysqli_fetch_array($sql)) {

            if ($i++ != 0)
                if ($row['conjunction'] == 0)
                    $echo .= ' && ';
                else
                    $echo .= ' || ';

            if ($row['negation'] == 1)
                $echo .= ' ! ';

            for ($i = 1; $i <= $row['left_bracket']; $i++)
                $echo .= ' ( ';

            // obicajne spremenljivke
            if ($row['spr_id'] > 0) {

                $row2 = Model::select_from_srv_spremenljivka($row['spr_id']);

                // obicne spremenljivke
                if ($row['vre_id'] == 0) {
                    $row1 = Model::select_from_srv_spremenljivka($row['spr_id']);
                    // multigrid
                } elseif ($row['vre_id'] > 0) {
                    $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE id = '$row[vre_id]'");
                    if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
                    $row1 = mysqli_fetch_array($sql1);
                } else
                    $row1 = null; //�ud not hepen


                // kombinirana tabela
                if (in_array($row2['tip'], array(6, 16, 19, 20)) && $row2['gru_id'] == '-2') {

                    if (in_array($row2['tip'], array(6, 16))) {
                        $sql3 = sisplet_query("SELECT * FROM srv_condition_grid c WHERE cond_id='$row[id]'");
                        if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);

                        $j = 0;
                        while ($row3 = mysqli_fetch_array($sql3)) {
                            if ($j++ != 0) $echo .= ' || ';

                            if ($rowa['mass_insert'] != 1 || $row2['tip'] != 6 || $_GET['m'] == 'quick_edit') {    // normalno izpolnjevanje

                                // Ce imamo if pogoj za -1 -> neodgovor
                                if ($row3['grd_id'] == '-1') {
                                    $condition = '';

                                    // loop cez vse gride in preverimo ce je kaksen odkljukan
                                    $sqlG = sisplet_query("SELECT id, part, other FROM srv_grid WHERE spr_id='$row2[id]'");
                                    while ($rowG = mysqli_fetch_array($sqlG)) {
                                        if ($row2['enota'] == 0 || $row2['enota'] == 1 || $row2['enota'] == 3) {
                                            if ($rowG['other'] == 0)
                                                $condition .= '!document.getElementById(\'multi_' . $row2['id'] . '_' . $row1['id'] . '_grid_' . $rowG['id'] . ($rowG['part'] > 1 ? '_part_2' : '') . '\').checked ';
                                            else
                                                $condition .= '!document.getElementById(\'grid_missing_value_' . $row1['id'] . '_grid_' . $rowG['id'] . ($rowG['part'] > 1 ? '_part_2' : '') . '\').checked ';

                                        } elseif ($row2['enota'] == 2 || $row2['enota'] == 6) {
                                            $condition .= '!document.getElementsByName(\'multi_' . $row2['id'] . '_' . $row1['id'] . '\')[0].options[document.getElementsByName(\'multi_' . $row2['id'] . '_' . $row1['id'] . '\')[0].selectedIndex].value == \'' . $rowG['id'] . '\' ';
                                        }
                                        $condition .= ' && ';
                                    }
                                    $echo .= '(' . substr($condition, 0, -3) . ')';
                                } else {
                                    if ($row2['enota'] == 0 || $row2['enota'] == 1 || $row2['enota'] == 3) {

                                        $sql4 = sisplet_query("SELECT part, other FROM srv_grid WHERE spr_id='$row2[id]' AND id='$row3[grd_id]'");
                                        $row4 = mysqli_fetch_array($sql4);

                                        if ($row4['other'] == 0)
                                            $echo .= 'document.getElementById(\'multi_' . $row2['id'] . '_' . $row1['id'] . '_grid_' . $row3['grd_id'] . ($row4['part'] > 1 ? '_part_2' : '') . '\').checked ';
                                        else
                                            $echo .= 'document.getElementById(\'grid_missing_value_' . $row1['id'] . '_grid_' . $row3['grd_id'] . ($row4['part'] > 1 ? '_part_2' : '') . '\').checked ';

                                    } elseif ($row2['enota'] == 2 || $row2['enota'] == 6) {
                                        $echo .= 'document.getElementsByName(\'multi_' . $row2['id'] . '_' . $row1['id'] . '\')[0].options[document.getElementsByName(\'multi_' . $row2['id'] . '_' . $row1['id'] . '\')[0].selectedIndex].value == \'' . $row3['grd_id'] . '\' ';
                                    }
                                }

                                // masovni vnos
                            } else {
                                $echo .= 'document.getElementById(\'spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['vre_id'] . '\').value == "' . $row3['grd_id'] . '"';
                            }

                        }
                    } elseif (in_array($row2['tip'], array(19, 20))) {
                        $echo .= ' ( ';

                        $echo .= ' document.getElementById(\'multi_' . $row2['id'] . '_' . $row['vre_id'] . '_grid_' . $row['grd_id'] . '\').value';

                        if ($row['operator'] == 0)
                            $echo .= ' == ';
                        elseif ($row['operator'] == 1)
                            $echo .= ' !== ';
                        elseif ($row['operator'] == 2)
                            $echo .= ' < ';
                        elseif ($row['operator'] == 3)
                            $echo .= ' <= ';
                        elseif ($row['operator'] == 4)
                            $echo .= ' > ';
                        elseif ($row['operator'] == 5)
                            $echo .= ' >= ';


                        //if ($row['text'] == '')
                        $echo .= '"' . $row['text'] . '"';
                        /*else
                            $echo .= $row['text'];*/

                        $echo .= ' ) ';
                    }

                    // radio, checkbox, dropdown in multigrid (brez drag and drop)
                } elseif ((($row2['tip'] <= 3 && ($row2['orientation'] != 8 || get('mobile') == 1)) || ($row2['tip'] == 6 || $row2['tip'] == 16) && ($row2['enota'] != 9)) && ($row['spr_id'] || $row['vre_id'])) {

                    if ($row['operator'] == 0)
                        $echo .= ' ';
                    else
                        $echo .= ' ! ';

                    $echo .= ' ( ';

                    // obicne spremenljivke
                    if ($row['vre_id'] == 0) {

                        $sql3 = sisplet_query("SELECT c.vre_id, v.id, v.vrstni_red, v.other FROM srv_condition_vre c, srv_vrednost v WHERE cond_id='$row[id]' AND c.vre_id=v.id");
                        if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);

                        $j = 0;
                        while ($row3 = mysqli_fetch_array($sql3)) {
                            if ($j++ != 0) $echo .= ' || ';

                            if ($rowa['mass_insert'] != 1 || $_GET['m'] == 'quick_edit') {    // normalno izpolnjevanje

                                // Ce imamo if pogoj za -1 -> neodgovor
                                if ($row3['vre_id'] == '-1') {
                                    $condition = '';

                                    // loop cez vse odgovore in preverimo ce je kaksen odkljukan
                                    $sqlV = sisplet_query("SELECT id, other FROM srv_vrednost WHERE spr_id='$row[spr_id]'");
                                    while ($rowV = mysqli_fetch_array($sqlV)) {
                                        if ($row2['tip'] <= 2 && ($row2['orientation'] != 8 || get('mobile') == 1)) {
                                            if ($rowV['other'] >= 0)
                                                $condition .= '!document.getElementById(\'spremenljivka_' . $row['spr_id'] . '_vrednost_' . $rowV['id'] . '\').checked ';
                                            else
                                                $condition .= '!document.getElementById(\'missing_value_spremenljivka_' . $row['spr_id'] . '_vrednost_' . $rowV['id'] . '\').checked ';
                                        } else {
                                            $condition .= '!document.getElementById(\'vrednost_' . $row['spr_id'] . '\').value == \'' . $rowV['id'] . '\' ';
                                        }
                                        $condition .= ' && ';
                                    }
                                    $echo .= '(' . substr($condition, 0, -3) . ')';
                                } else {
                                    //if ($row2['tip'] <= 2) {
                                    if ($row2['tip'] <= 2 && ($row2['orientation'] != 8 || get('mobile') == 1)) {
                                        if ($row3['other'] >= 0)
                                            $echo .= 'document.getElementById(\'spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row3['id'] . '\').checked ';
                                        else
                                            $echo .= 'document.getElementById(\'missing_value_spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row3['id'] . '\').checked ';
                                    } else {
                                        $echo .= 'document.getElementById(\'vrednost_' . $row['spr_id'] . '\').value == \'' . $row3['id'] . '\' ';
                                    }
                                }

                                // masovni vnos
                            } else {
                                if ($row2['tip'] != 2) {    // radio, dropdown
                                    $echo .= 'document.getElementById(\'vrednost_' . $row['spr_id'] . '\').value == "' . $row3['vrstni_red'] . '"';
                                } else {    // checkbox
                                    $echo .= 'document.getElementById(\'spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row3['id'] . '\').value == "1"';
                                }
                            }
                        }

                        // multigrid
                    } elseif ($row['vre_id'] > 0) {
                        $sql3 = sisplet_query("SELECT grd_id FROM srv_condition_grid WHERE cond_id='$row[id]'");
                        if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);

                        $j = 0;
                        while ($row3 = mysqli_fetch_array($sql3)) {
                            if ($j++ != 0) $echo .= ' || ';

                            if ($rowa['mass_insert'] != 1 || $row2['tip'] != 6 || $_GET['m'] == 'quick_edit') {    // normalno izpolnjevanje

                                // Ce imamo if pogoj za -1 -> neodgovor
                                if ($row3['grd_id'] == '-1') {
                                    $condition = '';

                                    // loop cez vse gride in preverimo ce je kaksen odkljukan
                                    $sqlG = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row2[id]'");
                                    while ($rowG = mysqli_fetch_array($sqlG)) {
                                        if ($row2['enota'] == 0 || $row2['enota'] == 1 || $row2['enota'] == 3) {
                                            if ($rowG['other'] == 0)
                                                $condition .= '!document.getElementById(\'vrednost_' . $row1['id'] . '_grid_' . $rowG['id'] . ($rowG['part'] > 1 ? '_part_2' : '') . '\').checked ';
                                            else
                                                $condition .= '!document.getElementById(\'grid_missing_value_' . $row1['id'] . '_grid_' . $rowG['id'] . ($rowG['part'] > 1 ? '_part_2' : '') . '\').checked ';

                                            //} elseif ($row2['enota'] == 2) {
                                        } elseif ($row2['enota'] == 2 || $row2['enota'] == 6) {
                                            $condition .= '!document.getElementsByName(\'vrednost_' . $row1['id'] . '\')[0].options[document.getElementsByName(\'vrednost_' . $row1['id'] . '\')[0].selectedIndex].value == \'' . $rowG['id'] . '\' ';
                                        }
                                        $condition .= ' && ';
                                    }
                                    $echo .= '(' . substr($condition, 0, -3) . ')';
                                } else {
                                    if ($row2['enota'] == 0 || $row2['enota'] == 1 || $row2['enota'] == 3 || $row2['enota'] == 4 || $row2['enota'] == 8) {

                                        $sql4 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row2[id]' AND id='$row3[grd_id]'");
                                        $row4 = mysqli_fetch_array($sql4);

                                        if ($row4['other'] == 0)
                                            $echo .= 'document.getElementById(\'vrednost_' . $row1['id'] . '_grid_' . $row3['grd_id'] . ($row4['part'] > 1 ? '_part_2' : '') . '\').checked ';
                                        else
                                            $echo .= 'document.getElementById(\'grid_missing_value_' . $row1['id'] . '_grid_' . $row3['grd_id'] . ($row4['part'] > 1 ? '_part_2' : '') . '\').checked ';

                                        //} elseif ($row2['enota'] == 2) {
                                    } elseif ($row2['enota'] == 2 || $row2['enota'] == 6) {
                                        $echo .= 'document.getElementsByName(\'vrednost_' . $row1['id'] . '\')[0].options[document.getElementsByName(\'vrednost_' . $row1['id'] . '\')[0].selectedIndex].value == \'' . $row3['grd_id'] . '\' ';
                                    }
                                }

                                // masovni vnos
                            } else {
                                $echo .= 'document.getElementById(\'spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['vre_id'] . '\').value == "' . $row3['grd_id'] . '"';
                            }

                        }
                    }

                    $echo .= ' ) ';
                    //drag and drop @ kategorije en odgovor
                } elseif ($row2['tip'] == 1 && ($row2['orientation'] == 8 && get('mobile') != 1)) {

                    if ($row['operator'] == 0)
                        $echo .= ' ';
                    else
                        $echo .= ' ! ';

                    $echo .= ' ( ';
                    //SELECT * FROM srv_condition_vre c, srv_vrednost v WHERE cond_id='$row[id]' AND c.vre_id=v.id
                    $sql3 = sisplet_query("SELECT c.vre_id FROM srv_condition_vre c, srv_vrednost v WHERE cond_id='$row[id]' AND c.vre_id=v.id");
                    if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);

                    $j = 0;
                    while ($row3 = mysqli_fetch_array($sql3)) {
                        if ($j++ != 0) $echo .= ' || ';

                        //if ($row2['design'] == 0 && get('mobile') == 0)
                        $echo .= ' $("#half2_frame_dropping_' . $row2['id'] . '").children("div").attr("value") == ' . $row3['vre_id'];

                        //half2_frame_dropping_4271, half2_frame_dropping_1_4276		//spremenljivka_4276_vrednost_22811

                    }

                    $echo .= ' ) ';
                    //drag and drop @ kategorije vec odgovorov
                } elseif ($row2['tip'] == 2 && ($row2['orientation'] == 8 && get('mobile') != 1)) {
                    $z = 0;
                    if ($row['operator'] == 0)
                        $echo .= ' ';
                    else
                        $echo .= ' ! ';

                    $echo .= ' ( ';
                    //SELECT * FROM srv_condition_vre c, srv_vrednost v WHERE cond_id='$row[id]' AND c.vre_id=v.id
                    $sql3 = sisplet_query("SELECT c.vre_id FROM srv_condition_vre c, srv_vrednost v WHERE cond_id='$row[id]' AND c.vre_id=v.id");
                    if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);

                    $j = 0;
                    while ($row3 = mysqli_fetch_array($sql3)) {
                        if ($j++ != 0) $echo .= ' || ';

                        //if ($row2['design'] == 0 && get('mobile') == 0)
                        for ($z = 1; $z <= $row2['grids']; $z++) {    //preleti vse mozne odgovore in sestavi pogoj
                            $echo .= ' $("#half2_frame_dropping_' . $row2['id'] . '").children("div :nth-child(' . $z . ')").attr("value") == ' . $row3['vre_id'];
                            if ($z < $row2['grids']) {    //ce ni zadnji mozni odgovor dodaj ali (||)
                                $echo .= '||';
                            }
                        }
                    }

                    $echo .= ' ) ';

                    //drag and drop @ tabela en odgovor
                } elseif (($row2['tip'] == 6) && ($row2['enota'] == 9)) {
                    if ($row['operator'] == 0)
                        $echo .= ' ';
                    else
                        $echo .= ' ! ';

                    $echo .= ' ( ';

                    $sql3 = sisplet_query("SELECT grd_id FROM srv_condition_grid WHERE cond_id='$row[id]'");

                    if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);

                    $j = 0;
                    while ($row3 = mysqli_fetch_array($sql3)) {
                        if ($j++ != 0) $echo .= ' || ';

                        //if ($row2['design'] == 0 && get('mobile') == 0)
                        $echo .= ' $("#half2_frame_dropping_' . $row3['grd_id'] . '_' . $row2['id'] . '").children("div").attr("value") == ' . $row['vre_id'];
                    }

                    $echo .= ' ) ';
                    //drag and drop @ tabela vec odgovorov
                } elseif (($row2['tip'] == 16) && ($row2['enota'] == 9)) {
                    if ($row['operator'] == 0)
                        $echo .= ' ';
                    else
                        $echo .= ' ! ';

                    $echo .= ' ( ';

                    $sql3 = sisplet_query("SELECT grd_id FROM srv_condition_grid WHERE cond_id='$row[id]'");

                    if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);

                    $j = 0;
                    while ($row3 = mysqli_fetch_array($sql3)) {
                        if ($j++ != 0) $echo .= ' || ';

                        //if ($row2['design'] == 0 && get('mobile') == 0)
                        for ($z = 1; $z <= $row2['grids']; $z++) {    //preleti vse mozne odgovore in sestavi pogoj
                            $echo .= ' $("#half2_frame_dropping_' . $row3['grd_id'] . '_' . $row2['id'] . '").children("div :nth-child(' . $z . ')").attr("value") == ' . $row['vre_id'];
                            if ($z < $row2['grids']) {    //ce ni zadnji mozni odgovor dodaj ali (||)
                                $echo .= '||';
                            }
                        }
                    }

                    $echo .= ' ) ';


                    // razvrscanje
                } elseif ($row2['tip'] == 17) {

                    if ($row['operator'] == 0)
                        $echo .= ' ';
                    else
                        $echo .= ' ! ';

                    $echo .= ' ( ';

                    $sql3 = sisplet_query("SELECT grd_id FROM srv_condition_grid WHERE cond_id='$row[id]'");
                    if (!$sql3) echo mysqli_error($GLOBALS['connect_db']);

                    $j = 0;
                    while ($row3 = mysqli_fetch_array($sql3)) {
                        if ($j++ != 0) $echo .= ' || ';

                        if ($row2['design'] == 0 && get('mobile') == 0)    // prestavljanje
                            $echo .= ' $(\'#half2_' . $row2['id'] . '\').sortable(\'toArray\')[' . ($row3['grd_id'] - 1) . '] == \'spremenljivka_'.$row['spr_id'].'_vrednost_' . $row['vre_id'] . '\' ';
                        elseif ($row2['design'] == 1 || get('mobile') > 0)    // ostevilcevanje
                            $echo .= ' document.getElementsByName(\'spremenljivka_' . $row2['id'] . '_vrednost_' . $row['vre_id'] . '\')[0].value == \'' . ($row3['grd_id']) . '\' ';
                        elseif ($row2['design'] == 2 && get('mobile') == 0)    // premikanje
                            $echo .= ' $(\'#sortzone_' . $row2['id'] . '\').sortable(\'toArray\')[' . ($row3['grd_id'] - 1) . '] == \'spremenljivka_'.$row['spr_id'].'_vrednost_' . $row['vre_id'] . '\' ';

                    }

                    $echo .= ' ) ';


                    // tabela text, tabela stevilo
                } elseif ($row2['tip'] == 19 || $row2['tip'] == 20) {

                    $echo .= ' ( ';

                    $echo .= ' document.getElementById(\'vrednost_' . $row['vre_id'] . '_grid_' . $row['grd_id'] . '\').value';

                    if ($row['operator'] == 0)
                        $echo .= ' == ';
                    elseif ($row['operator'] == 1)
                        $echo .= ' !== ';
                    elseif ($row['operator'] == 2)
                        $echo .= ' < ';
                    elseif ($row['operator'] == 3)
                        $echo .= ' <= ';
                    elseif ($row['operator'] == 4)
                        $echo .= ' > ';
                    elseif ($row['operator'] == 5)
                        $echo .= ' >= ';


                    //if ($row['text'] == '')
                    $echo .= '"' . $row['text'] . '"';
                    /*else
                        $echo .= $row['text'];*/

                    $echo .= ' ) ';

                    // textbox
                } elseif ($row2['tip'] == 21) {

                    $echo .= ' ( ';

                    if ($row['operator'] <= 5)
                        $echo .= 'document.getElementsByName(\'vrednost_' . $row['spr_id'] . '_kos_' . $row['vre_id'] . '\')[0].value';
                    else
                        $echo .= 'document.getElementsByName(\'vrednost_' . $row['spr_id'] . '_kos_' . $row['vre_id'] . '\')[0].value.length';

                    if ($row['operator'] == 0)
                        $echo .= ' == ';
                    elseif ($row['operator'] == 1)
                        $echo .= ' !== ';
                    elseif ($row['operator'] == 6)
                        $echo .= ' == ';
                    elseif ($row['operator'] == 7)
                        $echo .= ' < ';
                    elseif ($row['operator'] == 8)
                        $echo .= ' > ';

                    $echo .= '"' . $row['text'] . '"';

                    $echo .= ' ) ';

                    // number, compute in kvota majo drugacne pogoje in opcije
                } elseif ($row2['tip'] == 4 || $row2['tip'] == 7 || $row2['tip'] == 22 || $row2['tip'] == 25) {

                    $echo .= ' ( ';

                    if ($row2['tip'] == 7)    // number ma drugacen ID, ker ima lahko dva polja
                        $echo .= 'document.getElementById(\'spremenljivka_' . $row['spr_id'] . '_vrednost_' . ($row['grd_id'] + 1) . '\').value';
                    else
                        $echo .= 'document.getElementById(\'vrednost_' . $row['spr_id'] . '\').value';

                    if ($row['operator'] == 0)
                        $echo .= ' == ';
                    elseif ($row['operator'] == 1)
                        $echo .= ' !== ';
                    elseif ($row['operator'] == 2)
                        $echo .= ' < ';
                    elseif ($row['operator'] == 3)
                        $echo .= ' <= ';
                    elseif ($row['operator'] == 4)
                        $echo .= ' > ';
                    elseif ($row['operator'] == 5)
                        $echo .= ' >= ';

                    if ($row2['tip'] == 4 || $row['operator'] == 0 || $row['operator'] == 1)
                        $echo .= '"' . $row['text'] . '"';
                    else {
                        if ($row['text'] == '')
                            $echo .= '"' . $row['text'] . '"';
                        else
                            $echo .= $row['text'];
                    }

                    $echo .= ' ) ';

                    // datum
                } elseif ($row2['tip'] == 8) {

                    $echo .= ' ( ';

                    $echo .= 'Date.parse(convertDate(document.getElementById(\'vrednost_' . $row['spr_id'] . '\').value))';

                    if ($row['operator'] == 0)
                        $echo .= ' == ';
                    elseif ($row['operator'] == 1)
                        $echo .= ' !== ';
                    elseif ($row['operator'] == 2)
                        $echo .= ' < ';
                    elseif ($row['operator'] == 3)
                        $echo .= ' <= ';
                    elseif ($row['operator'] == 4)
                        $echo .= ' > ';
                    elseif ($row['operator'] == 5)
                        $echo .= ' >= ';

                    if ($row['text'] == '')
                        $echo .= '"' . $row['text'] . '"';
                    else
                        $echo .= 'Date.parse(convertDate("' . $row['text'] . '"))';

                    $echo .= ' ) ';

                    // vsota
                } elseif ($row2['tip'] == 18) {

                    $echo .= ' ( ';

                    $echo .= 'document.getElementsByName(\'spremenljivka_' . $row['spr_id'] . '_vrednost_' . $row['vre_id'] . '\')[0].value';

                    if ($row['operator'] == 0)
                        $echo .= ' == ';
                    elseif ($row['operator'] == 1)
                        $echo .= ' !== ';
                    elseif ($row['operator'] == 2)
                        $echo .= ' < ';
                    elseif ($row['operator'] == 3)
                        $echo .= ' <= ';
                    elseif ($row['operator'] == 4)
                        $echo .= ' > ';
                    elseif ($row['operator'] == 5)
                        $echo .= ' >= ';

                    if ($row['text'] == '')
                        $echo .= '"' . $row['text'] . '"';
                    else
                        $echo .= $row['text'];

                    $echo .= ' ) ';

                }

                // recnum
            } elseif ($row['spr_id'] == -1) {

                $echo .= ' ( _recnum % ' . $row['modul'] . ' == ' . $row['ostanek'] . ' ) ';

                // calculations
            } elseif ($row['spr_id'] == -2) {

                $echo .= ' ( ';

                $echo .= $this->generateCalculationJS($row['id']);

                if ($row['operator'] == 0)
                    $echo .= ' == ';
                elseif ($row['operator'] == 1)
                    $echo .= ' !== ';
                elseif ($row['operator'] == 2)
                    $echo .= ' < ';
                elseif ($row['operator'] == 3)
                    $echo .= ' <= ';
                elseif ($row['operator'] == 4)
                    $echo .= ' > ';
                elseif ($row['operator'] == 5)
                    $echo .= ' >= ';

                if ($row['text'] == '')
                    $echo .= '"' . $row['text'] . '"';
                else
                    $echo .= $row['text'];

                $echo .= ' ) ';

                // quotas
            } elseif ($row['spr_id'] == -3) {

                $quota = Check::getInstance()->checkQuota($row['id']);

                $echo .= ' ( ';

                $echo .= $quota;

                if ($row['operator'] == 0)
                    $echo .= ' == ';
                elseif ($row['operator'] == 1)
                    $echo .= ' !== ';
                elseif ($row['operator'] == 2)
                    $echo .= ' < ';
                elseif ($row['operator'] == 3)
                    $echo .= ' <= ';
                elseif ($row['operator'] == 4)
                    $echo .= ' > ';
                elseif ($row['operator'] == 5)
                    $echo .= ' >= ';

                if ($row['text'] == '')
                    $echo .= '"' . $row['text'] . '"';
                else
                    $echo .= $row['text'];

                $echo .= ' ) ';
				
            // naprava
            } elseif ($row['spr_id'] == -4) {
				
				if (in_array($row['text'], array('0','1','2','3'))){
				
                    // Star nacin detekcije - vedno vezan na prvi prihod, po novem detektiramo vsakic posebej
					/*$sqlU = sisplet_query("SELECT device FROM srv_user WHERE ank_id='".get('anketa')."' AND id='".get('usr_id')."'");
					$rowU = mysqli_fetch_array($sqlU);

                    $echo .= $row['text'] . ' == ' . $rowU['device'];*/

                    $device = 0;
                    $useragent = $_SERVER['HTTP_USER_AGENT'];

                    if ($useragent != '' && get_cfg_var('browscap')) {

                        $browser_detect = get_browser($useragent, true);

                        $detect = New Mobile_Detect();
                        $detect->setUserAgent($useragent);

                        // Detect naprave (pc, mobi, tablet, robot)
                        if ($detect->isMobile()) {
                            if ($detect->isTablet())
                                $device = 2;
                            else
                                $device = 1;
                        } 
                        elseif ($browser_detect['crawler'] == 1){
                            $device = 3;
                        }
                    }
				
					$echo .= ' ( ';	
					$echo .= $row['text'] . ' == ' . $device;
					$echo .= ' ) ';
				}
            }

            for ($i = 1; $i <= $row['right_bracket']; $i++)
                $echo .= ' ) ';
        }

        // failsafe, ce se poklika if, pa se ne nastavi pogoja
        if ($echo == '')
            $echo .= ' true ';

        echo $echo;
    }

    /**
     * @desc zgenerira kalkulacijo za vstavitev v JS
     */
    public function generateCalculationJS($condition)
    {
		$calculationSpr = Cache::srv_spremenljivka(-$condition);
	
        $sql = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id = '$condition' ORDER BY vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        $i = 0;
        $expression = '';
        while ($row = mysqli_fetch_array($sql)) {

            if ($i++ != 0)
                if ($row['operator'] == 0)
                    $expression .= ' + ';
                elseif ($row['operator'] == 1)
                    $expression .= ' - ';
                elseif ($row['operator'] == 2)
                    $expression .= ' * ';
                elseif ($row['operator'] == 3)
                    $expression .= ' / ';

            for ($i = 1; $i <= $row['left_bracket']; $i++)
                $expression .= ' ( ';

            // spremenljivke
            if ($row['spr_id'] > 0) {

                // obicne spremenljivke
                if ($row['vre_id'] == 0) {
                    $row1 = Model::select_from_srv_spremenljivka($row['spr_id']);

                    $spr = $row1['id'];
                    $vre = 0;
                    $grd = $row['grd_id'];
                    $tip = $row1['tip'];
                } // checkbox, multigrid
                elseif ($row['vre_id'] > 0) {
                    $sql1 = sisplet_query("SELECT v.spr_id, v.id, s.tip AS tip FROM srv_vrednost v, srv_spremenljivka s WHERE v.id = '$row[vre_id]' AND v.spr_id=s.id");
                    if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);

                    $row1 = mysqli_fetch_array($sql1);

                    $spr = $row1['spr_id'];
                    $vre = $row1['id'];
                    $tip = $row1['tip'];
                    $grd = $row['grd_id'];
                } else {
                    $spr = 0;
                    $vre = 0;
                    $tip = 0;
                    $grd = 0;
                }

				// Preverimo kako obravnavamo missinge - posamezno kot 0 ali kot -88 za celo kalkulacijo
				$newParams = new enkaParameters($calculationSpr['params']);
				$calcMissing = $newParams->get('calcMissing', '0');
			
                $expression .= " checkCalculation('{$spr}', '{$vre}', '{$grd}', '{$tip}', '{$calcMissing}') ";
            } 
			// konstante
            elseif ($row['spr_id'] == -1) {
                $expression .= $row['number'];
            }
            // recnum
            elseif ($row['spr_id'] == -2) {
                $sqlu = sisplet_query("SELECT recnum FROM srv_user WHERE id='".get('usr_id')."'");
                $rowu = mysqli_fetch_array($sqlu);

                $expression .= $rowu['recnum'];
            }

            for ($i = 1; $i <= $row['right_bracket']; $i++)
                $expression .= ' ) ';
        }

        // Zaokrozimo na doloceno stevilo decimalk
        $decimals = $calculationSpr['decimalna'];
        $expression = 'parseFloat((' . $expression . ').toFixed(' . $decimals . '))';

        return '(' . $expression . ')';
    }
}