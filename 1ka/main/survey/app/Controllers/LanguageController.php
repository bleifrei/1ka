<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 18.02.2016
 *****************************************/

namespace App\Controllers;

use SurveyInfo;

class LanguageController extends Controller
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

        return new LanguageController();
    }

    /************************************************
     * Osnovne jezikovne datoteke
     *
     * @param $anketa
     * @return
     ************************************************/
    public static function getLanguageFile()
    {
        global $lang;

        if (get('anketa') > 0) {
            SurveyInfo::getInstance()->SurveyInit(get('anketa'));
            $row = SurveyInfo::getInstance()->getSurveyRow();

            $jezik = $row['lang_resp'];

        } else {
            $sql = sisplet_query("SELECT value FROM misc WHERE what = 'SurveyLang_resp'");
            $row = mysqli_fetch_array($sql);

            $jezik = $row['value'];
        }

        $file = lang_path($jezik);

        if (@include($file)) {
            $_SESSION['langX'] = lang_path($jezik, 1);
        }
    }


    /**
     * inicializacija multilang ankete. nastavljen mora biti usr_id ali $_GET[language]
     *
     */
    public function multilang_init()
    {
        global $lang;

        // mamo prebran cookie in user id, spremenimo language ce je treba -- multilanguage podpora je vezana na usr_id
        $row = SurveyInfo::getInstance()->getSurveyRow();
        if ($row['multilang'] == 1) {
            if (isset($_GET['language'])) {    // jezik podan preko GETa (podan je ze v linku ali spremenimo v previewu)

                if (!empty(get('usr_id'))) { // (v bazo se shrani v displayintroduction - z ostalimi sistemskimi) (usr_id se ni postavljen)
                    $sqll = sisplet_query("SELECT lang_id FROM srv_language WHERE ank_id='" . get('anketa') . "' AND lang_id='$_GET[language]'");
                    $rowl = mysqli_fetch_array($sqll);
                    save('lang_id', $rowl['lang_id']);
                } else {    // v GETu, ko lahko v previewu spreminjamo jezik na strani (usr_id je ze postavljen)
                    $sql1 = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.variable='language' AND s.gru_id=g.id AND g.ank_id='" . get('anketa') . "'");
                    $row1 = mysqli_fetch_array($sql1);
                    if ($row1['id'] > 0)
                        $sql1 = sisplet_query("UPDATE srv_data_text" . get('db_table') . " dt SET text='$_GET[language]' WHERE spr_id='$row1[id]' AND usr_id='" . get('usr_id') . "'");
                    save('lang_id', $_GET['language']);
                }

            } elseif (isset($_POST['language'])) {    // jezik podan v POSTu (ce si ga user spremeni na prvi strani)

                $sql1 = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.variable='language' AND s.gru_id=g.id AND g.ank_id='" . get('anketa') . "'");
                $row1 = mysqli_fetch_array($sql1);
                if ($row1['id'] > 0)
                    $sql1 = sisplet_query("UPDATE srv_data_text" . get('db_table') . " dt SET text='$_POST[language]' WHERE spr_id='$row1[id]' AND usr_id='" . get('usr_id') . "'");
                save('lang_id', $_POST['language']);

            } else {                        // jezik je ze shranjen v bazi, ga preberemo (naslednje strani)

                $sqll = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " dt, srv_spremenljivka s WHERE dt.spr_id=s.id AND s.variable='language' AND dt.usr_id='" . get('usr_id') . "'");
                $rowl = mysqli_fetch_array($sqll);
                $sqll = sisplet_query("SELECT lang_id FROM srv_language WHERE ank_id='" . get('anketa') . "' AND lang_id='$rowl[text]'");
                $rowl = mysqli_fetch_array($sqll);
                save('lang_id', $rowl['lang_id']);

            }

            if (get('lang_id') == null) {    // ni bil podan preko GETa in ni shranjen v bazi -- priredimo default jezik
                $_GET['language'] = self::$lang['id'];    // to damo samo zato, da se shrani ID default jezika v bazo, namesto -1 (ker pri default jeziku ne podamo nič preko URLja)
                // get('lang_id') mora se vedno ostati null !

            } else {        // zamenjamo jezik
                $file = lang_path(get('lang_id'));
                if (@include($file))
                    $_SESSION['langX'] = lang_path(get('lang_id'), 1);
            }
        }

    }


    /**
     * prevod za srv_spremenljivka
     */
    public static function srv_language_spremenljivka($spremenljivka)
    {
        if (get('lang_id') != null) {

            $sqll = sisplet_query("SELECT * FROM srv_language_spremenljivka WHERE ank_id='" . get('anketa') . "' AND spr_id='$spremenljivka' AND lang_id='" . get('lang_id') . "'");
            $rowl = mysqli_fetch_array($sqll);
            
			return $rowl;
        }

        return false;
    }

    /**
     * vrne prevod za srv_vrednost
     *
     * @param mixed $vrednost
     */
    public function srv_language_vrednost($vrednost, $naslov2=false)
    {

        if (get('lang_id') != null) {

            $sqll = sisplet_query("SELECT naslov, naslov2 FROM srv_language_vrednost WHERE ank_id='" . get('anketa') . "' AND vre_id='$vrednost' AND lang_id='" . get('lang_id') . "'");
            $rowl = mysqli_fetch_array($sqll);

			if($naslov2){
				if ($rowl['naslov2'] != '')
					return $rowl['naslov2'];
			}		
            elseif ($rowl['naslov'] != ''){
                return $rowl['naslov'];
			}
        }

        return false;
    }

    /**
     * vrne prevod za srv_grid
     *
     * @param mixed $vrednost
     */
    public static function srv_language_grid($spremenljivka, $grid)
    {
        if (get('lang_id') != null) {

            $sqll = sisplet_query("SELECT naslov FROM srv_language_grid WHERE ank_id='" . get('anketa') . "' AND spr_id='$spremenljivka' AND grd_id='$grid' AND lang_id='" . get('lang_id') . "'");
            $rowl = mysqli_fetch_array($sqll);
            
			if ($rowl['naslov'] != '') return $rowl['naslov'];
        }

        return false;
    }
	
	    /**
     * vrne prevod podnaslova za dvojno tabelo
     *
     */
    public static function srv_language_grid_podnaslov($spremenljivka, $grid)
    {
        if (get('lang_id') != null) {

            $sqll = sisplet_query("SELECT podnaslov FROM srv_language_grid WHERE ank_id='" . get('anketa') . "' AND spr_id='$spremenljivka' AND grd_id='$grid' AND lang_id='" . get('lang_id') . "'");
            $rowl = mysqli_fetch_array($sqll);
            
			if ($rowl['podnaslov'] != '') return $rowl['podnaslov'];
        }

        return false;
    }

}