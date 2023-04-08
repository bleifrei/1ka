<?php
/***************************************
 * Description: Odgovoreun za glavo, kjer poberemo tudi vse spremenljivke iz URL naslovov
 * Autor: Robert Šmalc
 * Created date: 28.01.2016
 *****************************************/

namespace App\Controllers;

use App\Controllers\HelperController as Helper;
use App\Models\Model;
use App\Controllers\FindController as Find;
use Common;
use SurveyAdvancedParadataLog;
use SurveyInfo;
use SurveyThemeEditor;
use SurveyPanel;

class HeaderController extends Controller
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

        return new HeaderController();
    }

    /**
     * nastavi default parametre, ki se prenasajo preko urlja
     *
     */
    public static function getSurveyParams($encode = false, $force = null, $forcenot = null)
    {

        if (!is_array($force)) $force = array();
        if (!is_array($forcenot)) $forcenot = array();
        $g = '';

        $paramslist = array(
            'language',
            'preview',
            'testdata',
            'mobile',
            'urejanje',
            'quick_view',
            'disableif',
            'disablealert',
            'disablecode',
            'displayifs',
            'displayvariables',
            'hidecomment',
            'popup',
            'no_preview',
            'theme_profile',
            'theme',
            'ai',
            'pages'
        );

        // add params that are not forced and not notforced
        foreach ($paramslist AS $param) {
            if (isset($_GET[$param]) && !array_key_exists($param, $force) && !in_array($param, $forcenot))
                $g .= '&' . $param . '=' . $_GET[$param];
        }

        // add forced params
        foreach ($force AS $key => $val) {
            $g .= '&' . $key . '=' . $val;
        }

        if ($encode) $g = str_replace('&', '&amp;', $g);

        return $g;

    }

    /************************************************
     * Funcktja pridobi vse url parametre in jih ustrezno doda v globalne spremenljivke razreda SurveyClass
     *
     * @param $_GET
     * @param $var - vse spremenljivke
     * @return (obje ct) $get
     ************************************************/
    public function getAllUrlParameters()
    {
        // Vse GET parametre damo v objekt
        if (!empty($_GET))
            $get = (object)$_GET;

        //V kolikor gre za enkripcijo potem najprej dekriptiramo - URLDECODE SE ZE AVTOMATSKO IZVEDE NA $_GET array-u
        if (isset($get->enc)) {
            //$request_decoded = base64_decode(urldecode($get->enc));
            $request_decoded = base64_decode($get->enc);

            $request_array = array();
            parse_str($request_decoded, $request_array);

            foreach ($request_array as $var => $value) {
                $get->$var = $value;
            }
        }

        //postavimo še ID ankete, ker ga bomo največkrat potrebovali
        if (empty($get->anketa) && !is_int($get->anketa) && empty($_POST['anketa'])) {
            return header('Location: ' . self::$site_url);
            die("Missing anketa id!");
        }
        $anketa = (($get->anketa) ? $get->anketa : $_POST['anketa']);

        // Shranimo vse spremenljivke iz get parametrov v classu VariableClass
        save('anketa', $anketa);
        save('get', $get);

        return $get;
    }


    /************************************************
     * Pridobimo vse parametre iz piškotka
     *
     * @param $_COOKIE
     * @return (object) $cookie
     ************************************************/
    public function getAllCookieParameters()
    {
        $cookie = null;
        // Vse GET parametre damo v objekt
        if (!empty($_COOKIE))
            $cookie = (object)$_COOKIE;

        // Piškot shranimo v spremenljivke VariableClass
        save('cookie', $cookie);

        return $cookie;
    }

    /**
     * @desc zgenereira header
     */
    public function header(){
        global $app_settings;


        // preprecimo caching - tudi s klikom na gumb nazaj!
        header("Last-Modified: " . gmdate("D, j M Y H:i:s") . " GMT");
        header("Expires: " . gmdate("D, j M Y H:i:s", time()) . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
        header("Cache-Control: post-check=0, pre-check=0", FALSE);
        header("Pragma: no-cache"); // HTTP/1.0


        echo '<head>' . "\n";

        $row = SurveyInfo::getInstance()->getSurveyRow();

        $sqlv = sisplet_query("SELECT value FROM misc WHERE what='version'");
        $rowv = mysqli_fetch_array($sqlv);

        // Custom header title
        if(isset($app_settings['head_title_custom']) && $app_settings['head_title_custom'] == 1){
            echo '<title>'.strip_tags(Helper::getInstance()->displayAkronim(0)).' - '.$app_settings['head_title_text'].'</title>' . "\n";
        }
        // Default header title
        else{
            echo '<title>'.strip_tags(Helper::getInstance()->displayAkronim(0)).' - '.self::$lang['1ka_surveys'].'</title>' . "\n";
        }
        
        echo '  <meta charset="utf-8">' . "\n";

        // Preprecimo vklop compatibility moda v IE
        echo '  <meta http-equiv="X-UA-Compatible" content="IE=edge" />' . "\n";

        // nova verzija UI 1.8.10 - includamo minificirano skupaj z script.js (uporabimo iste jQuery fajle kot v adminu)
        echo '  <script src="' . self::$site_url . 'admin/survey/minify/g=jsfrontend?v=' . $rowv['value'] . '"></script>' . "\n";
        echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>' . "\n";

        // tukaj nalozimo CSS za jquery in template temo
        echo '  <link href="' . self::$site_url . 'admin/survey/minify/g=cssfrontend?v=' . $rowv['value'] . '" rel="stylesheet">' . "\n";

        //nalozimo stringe iz langa za JS
        echo '<script type="text/javascript" src="' . self::$site_url . 'admin/survey/script/js-lang.php?lang=' . ($row['lang_admin'] == 1 ? 'si' : 'en') . '&amp;v=' . $rowv['value'] . '"></script>' . "\n";

        // nalozimo open sans fonte za nov design
        echo '<link type="text/css" href="https://fonts.googleapis.com/css?family=Open+Sans:400italic,600italic,700italic,400,600,700&subset=latin,latin-ext" rel="stylesheet" />';
        /*echo '<link type="text/css" href="https://fonts.googleapis.com/css?family=PT+Sans+Narrow:400,700&subset=latin,latin-ext" rel="stylesheet">';*/
		echo '<link type="text/css" href="https://fonts.googleapis.com/css?family=Montserrat:400,400i,500,500i,600,600i,700,700i&amp;subset=latin-ext" rel="stylesheet" />';
	
			
		// CSS mobile skin za telefone in tablice
		if (get('mobile') == 1) {

			echo '  <meta content="width=device-width; initial-scale=1.0;" name="viewport">' . "\n";

			// za MJU anketo nalozimo posebej custom skin
			if(SurveyInfo::getInstance()->checkSurveyModule('mju_theme') == '1'){	
				$this->loadCustomMadeSkin($custom_skin='MJU');
			}	
			// CSS samo za mobilne telefone			
			else{
				$this->themeEditorMobileCustom();
			}	
		} 
		// CSS za tablice je po defaultu enak kot za pc
		elseif(get('mobile') == 2){

            echo '  <meta content="width=device-width" name="viewport">' . "\n";
			
			// za MJU anketo nalozimo posebej custom skin
			if(SurveyInfo::getInstance()->checkSurveyModule('mju_theme') == '1'){	
				$this->loadCustomMadeSkin($custom_skin='MJU');
			}
			// profili tem - skinov
			else{
				$this->themeEditor();
			}
		}
		// CSS za pc
		else{
			
			// Custom skin za Bled
			if($row['skin'] == 'Bled'){
				$this->loadCustomMadeSkin($custom_skin='Bled');	
			}
			// za MJU anketo nalozimo posebej custom skin
			elseif(SurveyInfo::getInstance()->checkSurveyModule('mju_theme') == '1'){	
				$this->loadCustomMadeSkin($custom_skin='MJU');
			}
			// profili tem - skinov
			else{
				$this->themeEditor();
			}
		}
			

        // še css za printer
        // tole je ze out-of-date, po moje je zaenkrat boljs brez posebnega print skina
        //echo '  <link rel="stylesheet" href="'.self::$site_url.'main/survey/skins/printer.css" media="print">'."\n";

        // Skin za glasovanje
        $sqlG = sisplet_query("SELECT skin FROM srv_glasovanje WHERE ank_id='" . get('anketa') . "'");
        $rowG = mysqli_fetch_array($sqlG);
        if (mysqli_num_rows($sqlG) > 0)
            echo '  <link rel="stylesheet" href="' . self::$site_url . 'main/survey/skins/glasovanje/' . $rowG['skin'] . '.css" media="screen">' . "\n";

        // CSS za modul kviz
        if (SurveyInfo::getInstance()->checkSurveyModule('quiz')) {
            echo '  <link rel="stylesheet" href="' . self::$site_url . 'main/survey/skins/quiz/quiz.css" media="screen">' . "\n";
        }

        // CSS za modul SAZU anketo
        if (SurveyInfo::getInstance()->checkSurveyModule('sazu')) {
            echo '  <link rel="stylesheet" href="' . self::$site_url . 'admin/survey/modules/mod_SAZU/css/style_sazu.css" media="screen">' . "\n";
        }

        // CSS za rtl text - glede na lang datoteko (zaenkrat samo arabscina)
        if (in_array(self::$lang['id'], array('39', '43'))) {
            echo '  <link rel="stylesheet" href="' . self::$site_url . 'public/css/main_rtl.css" media="screen">' . "\n";
        }

        // JavaScript za napredne parapodatke
        if (SurveyAdvancedParadataLog::getInstance()->paradataEnabled()){

			// Ce ne postamo oz. ce smo na zadnji strani vkljucimo js kodo
			if(count($_POST) == 0 || Find::getInstance()->findNextGrupa($_GET['grupa']) == 0){
				SurveyAdvancedParadataLog::getInstance()->prepareLogging();
				SurveyAdvancedParadataLog::getInstance()->linkJavaScript();
			}
		}
			
        echo '</head>' . "\n";


        // Ce imamo vklopljen evoli na instalaciji, potem onemogocimo kopiranje
        $evoli_copy_disable = '';
        if (Common::checkModule('evoli') == '1' || Common::checkModule('evoli_employmeter') == '1')
            $evoli_copy_disable = 'oncopy="return false;" oncut="return false;" oncontextmenu="return false;"';

        $preview = '';
        if (isset($_GET['preview']) && $_GET['preview'] == 'on')
            $preview = ' class="preview"';
        elseif (isset($_GET['testdata']) && $_GET['testdata'] == 'on')
            $preview = ' class="preview"';
        echo '<body ' . $preview . ' ' . $evoli_copy_disable . '>' . "\n";

        // Zamenjamo class no_js z js -> test javacsripta za userje (javascript_warning)
        echo "<script>" . "\n";
        $tooltips_maxwitdh = "maxWidth: '880'";
        if ($row['skin'] == 'Embed2')
            $tooltips_maxwitdh = "maxWidth: '340'";
        if ($row['skin'] == 'Otroci3' || $row['skin'] == 'Otroci4')
            $tooltips_maxwitdh = "maxWidth: '680'";

        echo "$(document).ready(function(){
					$('html').removeClass('no_js').addClass('js');
					$('.tooltip.mouseover').tooltipster({
							theme: 'tooltipster-shadow',";
        echo $tooltips_maxwitdh;
        echo "
					});
					$('.tooltip.mouseclick').tooltipster({
							theme: 'tooltipster-shadow',
							trigger: 'click',";
        echo $tooltips_maxwitdh;
        echo "		});";

        // Tega ne rabimo vec, ker urejamo okvir slik direktno s css-jem (na parenta (.variabla) damo class checked)
        //echo "$('div.variabla input:checkbox, div.variabla input:radio').on('change', function() { activateCehckboxImages($(this)); });"."\n";

        # če smo v quick_view disejblamo vse elemente frme
        if (get('quick_view') == true) {
            echo "$('input:[type=radio], input:[type=checkbox], input:[type=text], select, textarea').attr('disabled',true);" . "\n";
        }
        echo " })" . "\n";
        echo "</script>";

        if ($row['user_from_cms'] == 2 && $row['user_from_cms_email'] == 1) { // vnos

            $sql1 = sisplet_query("SELECT user_id FROM srv_user WHERE id = '" . get('usr_id') . "'");
            $row1 = mysqli_fetch_array($sql1);

            $sqlu = Model::db_select_user($row1['user_id']);
            $rowu = mysqli_fetch_array($sqlu);

            if (mysqli_num_rows($sqlu) > 0) {
                echo '<div id="vnos">';
                echo self::$lang['srv_recognized'] . ' <strong>' . $rowu['name'] . ' ' . $rowu['surname'] . '</strong><br>(' . $rowu['email'] . ')';
                echo '</div>' . "\n";
            }

        }
    }

    /**
     * @desc prikaze sistemske spremenljivke
     */
    public function displaySistemske()
    {
        $sql = sisplet_query("SELECT id, recnum FROM srv_user WHERE id='" . get('usr_id') . "'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        echo '  <input type="hidden" id="javascript" name="javascript" value="0">';
        echo '  <input type="hidden" id="srv_meta_anketa_id" name="srv_meta_anketa_id" value="' . get('anketa') . '">';

		// Ce imamo vklopljen modul panel sproti belezimo tudi panel_status
		if(SurveyInfo::checkSurveyModule('panel') == 1){
			
			// Ce gre za prvo nastavimo na default vrednost, drugace beremo iz post-a
			$sp = new SurveyPanel(get('anketa'));
            $panel_status = (isset($_COOKIE['panel_status']) && $_COOKIE['panel_status'] != '') ? $_COOKIE['panel_status'] : $sp->getPanelSettings('status_default');
 
			echo '  <input type="hidden" id="panel_status" name="panel_status" value="'.$panel_status.'">';
        }
		
		// Spremenljivke, ki se rabijo v JS
        echo '  <script>													' . "\n";
						                                
        echo '    var _recnum = \'' . $row['recnum'] . '\';                	' . "\n";
        echo '    var _usr_id = \'' . $row['id'] . '\';                   	' . "\n";
        echo '    var srv_meta_anketa_id = ' . get('anketa') . ';         	' . "\n";
		echo '    var srv_site_url = \'' . self::$site_url . '\';           ' . "\n";
		echo '    var _lang = \'' . self::$lang['language'] . '\';          ' . "\n";
		
		echo '    var is_paused_slideshow = false;                          ' . "\n";
        echo '    document.getElementById(\'javascript\').value = 1;        ' . "\n";

        echo '  </script>                                                   ' . "\n";
    
		// Spremenljivke, ki se rabijo v JS za drag/drop
		echo '
				<script>
					var draggableOnDroppable = [];		// spremenljivka, ki belezi prisotnost odgovora na ustrezni povrsini pri Drag and Drop
					var maxDragDrop = [];				// spremenljivka, ki belezi max stevilo moznih odgovorov

					var draggableOverDroppable = [];	// spremenljivka, ki belezi prisotnost odgovora nad ustreznim okvirjem pri Drag and Drop
					var default_var_height_1 = []; 		// belezi zacetno vrednost visine celotnega vprasanja po usklajevanju visine glede na prisotne kategorije odgovorov
					var data_after_refresh = [];		// belezi, ali je uporabnik refresh-al stran oz. se vraca na stran
					var frame_total_height_right = [];	// belezi visino okvirjev desnega bloka @ drag and drop grids
					var draggableOver = [];
					var last_vre_id = [];				// belezi vre_id zadnjega draggable, ki smo ga premikali @ Drag and drop
					var vre_id_global = []; 			// belezi vre_id trenutne kategorije odgovorov @ Drag and drop
					var last_indeks = [];				// belezi indeks zadnjega okvirja, kjer je bil draggable @ Drag and drop
					var indeks_global = []; 			// belezi trenutni indeks okvirja @ Drag and drop
					var last_drop = [];					// belezi indeks zadnjega okvirja, kjer je bil draggable droppan @ Drag and drop
					var num_grids_global = []; 			// belezi stevilo gridov za doloceno vprasanje
					var draggable_global = [];
					var cat_pushed = [];				// belezi, ali je kategorijo odrinila druga kategorija odgovora @ Drag and drop
				</script>
		';
    }

    public function themeEditor($themePreview = false){

        if (isset($_GET['theme'])) $themePreview = true; // to ne bo vec
        if (isset($_GET['theme-preview']) && $_GET['theme-preview'] == '1') $themePreview = true; // to je pri urejanju CSSa da se refresha mimo cachea

        $row = SurveyInfo::getSurveyRow();

        if (isset($_GET['theme_profile'])) {
            $row['skin_profile'] = (int)$_GET['theme_profile'];
        }
        if (isset($_GET['theme'])) {//to ne bo vec
            $row['skin'] = str_replace('.css', '', $_GET['theme']);
            $row['skin_profile'] = 0;
        }

        // ni nastavljenih profilov, nastavimo navadno temo
        if ($row['skin_profile'] == 0) {
			
			// Ce ne gre za obstojeco anketo nastavimo default skin
			if($row['skin'] == '')
				$row['skin'] = '1kaBlue';
			
			// moznost zip skinov
			if (!is_file(self::$site_path . 'main/survey/skins/' . $row['skin'] . '.css')) {
				echo '  <link rel="stylesheet" href="' . self::$site_url . 'main/survey/skins/' . $row['skin'] . '/' . $row['skin'] . '.css' . ($themePreview ? '?foo=' . mt_rand() : '') . '">' . "\n";
			} else {
				echo '  <link rel="stylesheet" href="' . self::$site_url . 'main/survey/skins/' . $row['skin'] . '.css' . ($themePreview ? '?foo=' . mt_rand() : '') . '">' . "\n";
			}
        } 
		// nastavljen je profil, nastavimo temo in potem še lastne nastavitve
        else {

            $sqla = sisplet_query("SELECT skin, logo FROM srv_theme_profiles WHERE id = '$row[skin_profile]'");
            $rowa = mysqli_fetch_array($sqla);

            // moznost zip skinov
            if (!is_file(self::$site_path . 'main/survey/skins/' . $rowa['skin'] . '.css')) {
                echo '  <link rel="stylesheet" href="' . self::$site_url . 'main/survey/skins/' . $rowa['skin'] . '/' . $rowa['skin'] . '.css' . ($themePreview ? '?foo=' . mt_rand() : '') . '">' . "\n";
            } else {
                echo '  <link rel="stylesheet" href="' . self::$site_url . 'main/survey/skins/' . $rowa['skin'] . '.css' . ($themePreview || true ? '?foo=' . mt_rand() : '') . '">' . "\n";
            }

            $sqlt = sisplet_query("SELECT * FROM srv_theme_editor WHERE profile_id = '" . $row['skin_profile'] . "'");
            if (mysqli_num_rows($sqlt) > 0) {

                echo '<style>';

                while ($rowt = mysqli_fetch_array($sqlt)) {

                    switch ($rowt['id']) {

                        case '1':
                            echo 'h1';
                            break;

                        case '2':
                            echo '.spremenljivka';
                            break;

                        case '3':
                            echo '.variable_holder';
                            break;

                        case '4':
                            echo 'table.grid_table tbody tr:nth-child(2n+1)';
                            break;

                        case '5':
                            echo 'div.spremenljivka';
                            break;

                        case '7':
                            echo 'input[type="checkbox"]+span.enka-checkbox-radio, 
                                  input[type="radio"]+span.enka-checkbox-radio,
                                  .custom_radio_picture.obarvan > label > span.enka-custom-radio:before                                  
                                  ';
                            break;

                        case '6':
                            echo '#container';
                            break;

                        case '8':
                            echo '.tooltipster-shadow';
                            break;

                        case '9':
                            echo 'abbr.tooltip';
                            break;
                    }

                    echo ' {';

                    switch ($rowt['type']) {

                        case '1':
                            if ($rowt['value'] > 0)
                                echo 'font-family: ' . SurveyThemeEditor::getFont($rowt['value']) . ';';
                            break;

                        case '2':
                        case '9':
                        case '15':
                            echo 'color: ' . $rowt['value'] . ' !important;';
                            break;

                        case '3':
                            echo 'background: ' . $rowt['value'] . ';';
                            break;

                        case '4':
                            echo 'font-size: ' . $rowt['value'] . '%;';
                            break;

                        case '5':
                            if ($rowt['value'] == '0') {
                                echo 'border: 0;';
                            } elseif ($rowt['value'] == '2') {
                                echo 'border: 1px #B9C5D9 solid;';
                            } else {
                                echo 'border: 0;';
                                echo 'border-top: 1px #B9C5D9 solid;';
                            }
                            break;

						case '7':
							if ($rowt['value'] != '0')
								echo 'font-size: ' . $rowt['value'] . 'px;';
                            break;
						
                        case '10':
                            echo 'border-color: ' . $rowt['value'] . ';';
                            break;

                        case '11':
                            echo 'border-width: ' . $rowt['value'] . 'px;';
                            break;

                        case '12':
                            echo 'border-radius: ' . $rowt['value'] . 'px;';
                            break;

                        case '13':
                            echo 'background-color: ' . $rowt['value'] . ' !important;';
                            break;

                        case '14':
                            if ($rowt['value'] == 'bold')
                                echo 'font-weight: ' . $rowt['value'] . ' !important;';
                            if ($rowt['value'] == 'italic')
                                echo 'font-style: ' . $rowt['value'] . ' !important;';
                            if ($rowt['value'] == 'underline')
                                echo 'text-decoration: ' . $rowt['value'] . ' !important;';
                            break;


                    }

                    echo '}';
					
					// Accessibility
					if($rowt['type'] == '17' && $rowt['id'] == '10'){
						if($rowt['value'] == '1'){
							
							// Skrijemo barvne ikone
							echo 'input[type="checkbox"]+span.enka-checkbox-radio, input[type="radio"]+span.enka-checkbox-radio{
										display: none !important;
									}';	
									
							// Prikazemo navadne ikone
							echo 'input[type="checkbox"], input[type="radio"] {
										display: inline-block !important;
									}';	
						}
					}
                }

                echo '</style>';
            }

            // Izrisemo css za custom logo
            if ($rowa['logo'] != '') {
                $this->customLogoCSS($rowa['logo']); 
            }
        }
		
		// Za office in nature skina imamo random background
		if($row['skin'] == '1kaOffice' || $row['skin'] == '1kaNature'){		
			$bg_number = rand(1,15);			
			echo '<style> html{ background-image: url(' . self::$site_url . 'main/survey/skins/'.$row['skin'].'/bg'.$bg_number.'.jpg); } </style>';
		}
    }

    public function themeEditorMobileCustom(){

        $row = SurveyInfo::getSurveyRow();

		// Najprej nalozimo mobile template skin
		echo '  <link rel="stylesheet" href="' . self::$site_url . 'public/css/main_mobile.css" media="all">' . "\n";

		
		if (isset($_GET['theme_profile'])) {
            $row['skin_profile_mobile'] = (int)$_GET['theme_profile'];
        }
		if (isset($_GET['theme'])) {
			$themePreview = true;		
			$row['mobile_skin'] = str_replace('.css', '', $_GET['theme']);
		}
		
	
		// Fonti za mobilne skine (vse razen prvega default)
		if($row['mobile_skin'] != 'Mobile')
			echo '<link type="text/css" href="https://fonts.googleapis.com/css?family=Bree+Serif&subset=latin,latin-ext" rel="stylesheet" />';
	
	
		// ni nastavljenih profilov, nastavimo navadno temo
        if ($row['skin_profile_mobile'] == 0) {
			
			if (!is_file(self::$site_path . 'main/survey/skins/' . $row['mobile_skin'] . '.css')) {
				echo '  <link rel="stylesheet" href="' . self::$site_url . 'main/survey/skins/MobileBlue.css'.($themePreview ? '?foo=' . mt_rand() : '').'" media="all">' . "\n";
			} 
			else {
				echo '  <link rel="stylesheet" href="' . self::$site_url . 'main/survey/skins/' . $row['mobile_skin'] . '.css'.($themePreview ? '?foo='. mt_rand() : '').'" media="all">' . "\n";
			}
		}
		// nastavljen je profil, nastavimo temo in potem še lastne nastavitve
        else {
		
			$sqla = sisplet_query("SELECT skin FROM srv_theme_profiles_mobile WHERE id = '$row[skin_profile_mobile]'");
            $rowa = mysqli_fetch_array($sqla);
		
			if (!is_file(self::$site_path . 'main/survey/skins/' . $rowa['skin'] . '.css')) {
				echo '  <link rel="stylesheet" href="' . self::$site_url . 'main/survey/skins/MobileBlue.css'.($themePreview ? '?foo=' . mt_rand() : '').'" media="all">' . "\n";
			} else {
				echo '  <link rel="stylesheet" href="' . self::$site_url . 'main/survey/skins/' . $rowa['skin'] . '.css'.($themePreview || true ? '?foo='. mt_rand() : '').'" media="all">' . "\n";
			}
		
			$sqlt = sisplet_query("SELECT * FROM srv_theme_editor_mobile WHERE profile_id = '" . $row['skin_profile_mobile'] . "'");
			if (mysqli_num_rows($sqlt) > 0) {

				echo '<style>';

				while ($rowt = mysqli_fetch_array($sqlt)) {

					switch ($rowt['id']) {
						
						case '1':
                            echo 'h1';
                            break;

                        case '2':
                            echo '.spremenljivka .naslov';
                            break;

                        case '3':
                            echo '.variable_holder, .variable_holder .variabla label';
                            break;

                        case '4':
                            echo 'table.grid_table tbody tr:nth-child(2n+1)';
                            break;

                        case '5':
                            echo '.spremenljivka';
                            break;

                        case '7':
							echo 'input[type="checkbox"]+span.enka-checkbox-radio, 
									input[type="radio"]+span.enka-checkbox-radio';
							break;

                        case '6':
                            echo '#container h1, #footer_survey';
                            break;

                        case '8':
                            echo '.tooltipster-shadow';
                            break;

                        case '9':
                            echo 'abbr.tooltip';
                            break;
					}

					echo ' {';

					switch ($rowt['type']) {

						case '1':
                            if ($rowt['value'] > 0)
                                echo 'font-family: ' . SurveyThemeEditor::getFont($rowt['value']) . ' !important;';
                            break;

                        case '2':
                        case '9':
							echo 'color: ' . $rowt['value'] . ' !important;';
                            break;

                        case '3':
                            echo 'background: ' . $rowt['value'] . ' !important;';
                            break;

                        case '4':
                            echo 'font-size: ' . $rowt['value'] . '% !important;';
                            break;

                        case '5':
                            if ($rowt['value'] == '0') {
                                echo 'border: 0;';
                            } elseif ($rowt['value'] == '2') {
                                echo 'border: 1px #B9C5D9 solid;';
                            } else {
                                echo 'border: 0;';
                                echo 'border-top: 1px #B9C5D9 solid;';
                            }
                            break;
							
						case '8':
							if ($rowt['value'] != '0')
								echo 'font-size: ' . $rowt['value'] . 'px !important;';
                            break;	

                        case '10':
                            echo 'border-color: ' . $rowt['value'] . ' !important;';
                            break;

                        case '11':
                            echo 'border-width: ' . $rowt['value'] . 'px !important;';
                            break;

                        case '12':
                            echo 'border-radius: ' . $rowt['value'] . 'px !important;';
                            break;

                        case '13':
                            echo 'background-color: ' . $rowt['value'] . ' !important;';
                            break;

                        case '14':
                            if ($rowt['value'] == 'bold')
                                echo 'font-weight: ' . $rowt['value'] . ' !important;';
                            if ($rowt['value'] == 'italic')
                                echo 'font-style: ' . $rowt['value'] . ' !important;';
                            if ($rowt['value'] == 'underline')
                                echo 'text-decoration: ' . $rowt['value'] . ' !important;';
                            break;
					
						case '16':
							echo 'color: ' . $rowt['value'] . ' !important;';
							break;
					}

					echo '}';

					// Accessibility
					if($rowt['type'] == '17' && $rowt['id'] == '10'){
						if($rowt['value'] == '1'){
							
							// Skrijemo barvne ikone
							echo 'input[type="checkbox"]+span.enka-checkbox-radio, input[type="radio"]+span.enka-checkbox-radio{
										display: none !important;
									}';	
									
							// Prikazemo navadne ikone
							echo 'input[type="checkbox"], input[type="radio"] {
										display: inline-block !important;
									}';	
						}
					}
				}

				echo '</style>';
            }
        }
        
        // Izrisemo css za custom logo
        $sqla = sisplet_query("SELECT logo FROM srv_theme_profiles WHERE id = '$row[skin_profile]'");
        $rowa = mysqli_fetch_array($sqla);

        if ($rowa['logo'] != '') {
            $this->customLogoCSS($rowa['logo']); 
        }
    }

	
	// Funkcija ki nalozi custom temo narejeno po narocilu
	private function loadCustomMadeSkin($custom_skin){
		
		if (isset($_GET['theme'])) $themePreview = true; // to ne bo vec
        if (isset($_GET['theme-preview']) && $_GET['theme-preview'] == '1') $themePreview = true; // to je pri urejanju CSSa da se refresha mimo cachea
		
		
		// Custom narejen skin za Bled
		if($custom_skin == 'Bled'){
			
			// Font PT sans
			echo '<link type="text/css" href="https://fonts.googleapis.com/css?family=PT+Sans:400,700&subset=latin,latin-ext" rel="stylesheet">';
			
			// Imamo različne backgrounde
			$bg_number = rand(1,9);
			echo '<style>
				html{
					background-image: url(' . self::$site_url . 'main/survey/skins/___po_narocilu/Bled/bg'.$bg_number.'.jpg);
				}
			</style>';
			
			echo '  <link rel="stylesheet" href="' . self::$site_url . 'main/survey/skins/___po_narocilu/Bled.css' . ($themePreview ? '?foo=' . mt_rand() : '') . '">' . "\n";
			
			// Premaknemo footer na dno
			echo '<script>
				footerBled();

				function footerBled(){
						var footerResize = function() {
								if($("#outercontainer").height() <= $(window).height()-80){
									$(\'#footer_survey\').css(\'position\', "absolute");
									$(\'#footer_survey\').css(\'bottom\', "0px");
								}
								else{
									$(\'#footer_survey\').css(\'position\', "auto");
									$(\'#footer_survey\').css(\'bottom\', "auto");
								}
						};
						$(window).resize(footerResize).ready(footerResize);
				}
			</script>';	
		}
		// Custom skin za MJU anketo
		elseif($custom_skin == 'MJU'){
		
			// Font PT sans
			echo '<link type="text/css" href="https://fonts.googleapis.com/css?family=PT+Sans:400,700&subset=latin,latin-ext" rel="stylesheet">';
				
			// mobitel
			if (get('mobile') == 1) {
				echo '  <link rel="stylesheet" href="' . self::$site_url . 'main/survey/skins/___po_narocilu/MJU_mobile.css' . ($themePreview ? '?foo=' . mt_rand() : '') . '">' . "\n";
			}
			// tablica
			elseif(get('mobile') == 2){
				echo '  <link rel="stylesheet" href="' . self::$site_url . 'main/survey/skins/___po_narocilu/MJU_tablet.css' . ($themePreview ? '?foo=' . mt_rand() : '') . '">' . "\n";
			}
			// navaden racunalnik
			else{
				echo '  <link rel="stylesheet" href="' . self::$site_url . 'main/survey/skins/___po_narocilu/MJU.css' . ($themePreview ? '?foo=' . mt_rand() : '') . '">' . "\n";
			}
		}
    }
    

    // Insertamo css za custom logo
    private function customLogoCSS($logo){

        // Najprej dobimo velikost originalne slike
        $image_orig = self::$site_url . 'main/survey/uploads/' . $logo;
        if (file_exists(survey_path('uploads/' . $logo))) {

            $size_orig = @getimagesize($image_orig);

            $css_bg_image = $image_orig;
            $css_width = '';
            $css_height = '';
            $css_bg_size = 'contain !important';

            // Ce gre za Gorenje prikazemo drug logo
            if(Common::checkModule('gorenje')){
                $css_bg_size = 'auto 50px !important';
            }
            // Ce slucajno ne moremo dobiti siza
            elseif(!$size_orig){
                $css_height = '100px';
                $css_width = '250px';
            }
            // Ce je original visji kot 160 ga avtomatsko pomanjsamo
            elseif($size_orig[1] > 160) {

                $image = self::$site_url . 'function/thumb.php?src=' . self::$site_url . 'main/survey/uploads/' . $logo . '&h=100';
                $size = @getimagesize($image);

                if($size){
                    $css_bg_image = $image;
                    $css_width = $size[0].'px';
                    $css_height = '100px';
                }       
            }
            // Drugace pustimo default size logotipa
            else{
                $css_width = $size_orig[0].'px';
                $css_height = $size_orig[1].'px';
            }


            // CSS za custom logo
            echo '<style type="text/css">';

            echo '#logo, #logo.english, #footer_survey #logo, #footer_survey #logo.english {';
        
            echo ' background-image: url('.$css_bg_image.'); ';
            
            if($css_width != '')
                echo ' width: '.$css_width.'; ';
            
            if($css_height != '')
                echo ' height: '.$css_height.'; ';
            
            if($css_bg_size != '')
                echo ' background-size: '.$css_bg_size.'; ';

            echo '}';

            echo '#footer_survey #logo { max-height: 70px; max-width: 200px; }';

            echo '#logo a, #footer_survey #logo a { display: none; }';

            echo '</style>';
        }
    }

}