<?php
/***************************************
 * Description: Glavni model, ki skrbi za povezovanje s podatkovno bazo
 * Autor: Robert Šmalc
 * Created date: 22.01.2016
 *****************************************/
namespace App\Models;

// Iz admin/survey
use SurveyAdvancedParadataLog;


class Model
{

    /**
     * @param object $db A PDO database connection
     */
    function __construct($db)
    {
        try {
            $this->db = $db;
        } catch (PDOException $e) {
            exit('Database connection could not be established.');
        }
    }


    /**
     * @desc Vrne vse uporabnike iz baze
     */
    public static function db_select_users()
    {
        return sisplet_query("SELECT name, surname, id, email FROM users ORDER BY name ASC");
    }

    /**
     * @desc Vrne podatke o uporabniku
     */
    public static function db_select_user($uid)
    {
        return sisplet_query("SELECT name, surname, id, email FROM users WHERE id='$uid'");
    }


    /** Ob spremembi označimo da je potrebno osvežit tabelo s seznamom anket
     *
     */
    public static function setUpdateSurveyList()
    {
        # popravimo še polje za osvežitev podatkov seznama anket uporablja class: class.SurveyList.php
        $updateString = "INSERT DELAYED INTO srv_survey_list (id, updated) VALUES ('" . get('anketa') . "', '1') ON DUPLICATE KEY UPDATE updated='1'";

        $s = sisplet_query($updateString);
        if (!$s) echo mysqli_error($GLOBALS['connect_db']);
    }


    /**
     * pobere in zakesira podatke o spremenljivki (ker se to zlo velikokrat bere)
     *
     * @param mixed $spremenljivka
     */
    public static function select_from_srv_spremenljivka($spremenljivka)
    {
        $anketa = get('anketa');
        $srv_premenljivka = get('select_from_srv_spremenljivka');

        if (array_key_exists($spremenljivka, get('select_from_srv_spremenljivka'))) {
            return $srv_premenljivka[$spremenljivka];
        }

        // tole se splaca tam kjer se itak vse spremenljivke preberejo, sam vprasanje, ce se povsod??
        $sql = sisplet_query("SELECT s.* FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$anketa'");
        while ($row = mysqli_fetch_array($sql)) {
            $srv_premenljivka[$row['id']] = save('select_from_srv_spremenljivka[' . $row['id'] . ']', $row, 1);
        }
        if (array_key_exists($spremenljivka, get('select_from_srv_spremenljivka'))) {
            return $srv_premenljivka[$spremenljivka];
        }

        $sql = sisplet_query("SELECT * FROM srv_spremenljivka WHERE id = '$spremenljivka'");

        $srv_premenljivka[$spremenljivka] = mysqli_fetch_array($sql);

        return $srv_premenljivka[$spremenljivka];

    }

    public static function generate_order_by_field($spremenljivka, $usr_id = 0)
    {

        $orderby_array = array();
        $order = '';

        $randomsort = false;

        $sql1 = sisplet_query("SELECT random, vrstni_red FROM srv_vrednost WHERE spr_id = '$spremenljivka' ORDER BY vrstni_red ASC");
        while ($row1 = mysqli_fetch_assoc($sql1)) {

            if ($order != '') $order .= ", ";

            // zapecene na tem mestu (oz. normalno sortirane)
            // pustimo sortiranje po vrstnem redu
            if ($row1['random'] == 0) { 
                $orderby_array[$row1['vrstni_red']] = $row1['vrstni_red'];
                $order .= "'" . $row1['vrstni_red'] . "'";
            } 
            // random sortirane vrednosti
            elseif ($row1['random'] == 1) {

                $randomsort = true;

                // da se pri vracanju nazaj znova ne randomizirajo odgovori
                $seed = '';
                if ($usr_id > 0) {
					$seed_num = (int)$usr_id + (int)$spremenljivka;
                    mt_srand($seed_num);
                    $seed = mt_rand();
                }

                // na random izberemo (tiste, ki se random sortirajo) in jih damo na prosto mesto
                $sql2 = sisplet_query("SELECT vrstni_red FROM srv_vrednost WHERE spr_id='$spremenljivka' AND random='1' ORDER BY RAND(" . $seed . ")");
                while ($row2 = mysqli_fetch_assoc($sql2)) {
                    if (!in_array($row2['vrstni_red'], $orderby_array)) {
                        $orderby_array[$row1['vrstni_red']] = $row2['vrstni_red'];
                        $order .= "'" . $row2['vrstni_red'] . "'";
                        break;
                    }
                }
            } 
            // Sortiranje po abecedi padajoče ali naraščajoče
            elseif ($row1['random'] == 2 || $row1['random'] == 3) {
                
                // mysql default = ASC
                if ($row1['random'] == 3)
                    $asc_desc = "DESC";
                
                $sql2 = sisplet_query("SELECT vrstni_red FROM srv_vrednost WHERE spr_id='$spremenljivka' AND (random='2' OR random='3') ORDER BY naslov " . $asc_desc);
                while ($row2 = mysqli_fetch_assoc($sql2)) {
                    if (!in_array($row2['vrstni_red'], $orderby_array)) {
                        $orderby_array[$row1['vrstni_red']] = $row2['vrstni_red'];
                        $order .= "'" . $row2['vrstni_red'] . "'";
                        
                        break;
                    }
                }
            }

            // Posebno sortiranje posebej za NIJZ glede na dan v tednu (samo na nijz virtualki)
            global $site_domain;
            if(($spremenljivka == '9907732' || $spremenljivka == '9907735' || $spremenljivka == '9921570' || $spremenljivka == '9921573') && $site_domain == 'anketa.nijz.si'){

                $day_of_week = date('w');
                // Popravimo nedeljo iz 0 na 7
                if($day_of_week == 0)
                    $day_of_week = 7;

                $order = '';
                for($i=0; $i<7; $i++){

                    $order .= '\'';
                    $order .= ($day_of_week + $i > 7) ? $day_of_week+$i-7 : $day_of_week+$i;
                    $order .= '\', ';
                } 
                
                $order = substr($order, 0, -2);
            }
        }

        if ($order == '') $order = '1';

        // Shranimo vrstni red ce imamo random
        if($randomsort){

            $vrstni_red = str_replace('\'', "", $order);
            $vrstni_red = str_replace(' ', "", $vrstni_red);

            $sql = sisplet_query("INSERT INTO srv_data_random_spremenljivkaContent 
                                (usr_id, spr_id, vrstni_red) 
                                VALUES 
                                ('".$usr_id."', '".$spremenljivka."', '".$vrstni_red."')
                                ON DUPLICATE KEY UPDATE vrstni_red='".$vrstni_red."'
            ");
            if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        }
		
		// Napredni parapodatki - belezenje vrstnega reda pri random
        if (SurveyAdvancedParadataLog::getInstance()->paradataEnabled() && $randomsort){
			
			$event_type = 'question';
			$event = 'random_sort';
			
			$data = array('usr_id' => $usr_id);
			$data['data'] = array(
				'spr_id' => $spremenljivka,
				'vre_order' => mysqli_real_escape_string($GLOBALS['connect_db'], $order)
			);
			
			SurveyAdvancedParadataLog::getInstance()->logData($event_type, $event, $data);
		}

        return $order;
    }

    /**
     * pri masovnem vnosu, vnasalci vnasajo kot vrednost vrstni_red, ki ga je treba sedaj pretvoriti v ID
     *
     * @param mixed $spr_id
     * @param mixed $vrednost
     */
    public static function mass_insert($spr_id, $tip, $vrednost)
    {

        switch ($tip) {
            case 1:
            case 3:
                $sql = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$spr_id' AND vrstni_red='$vrednost'");
                $row = mysqli_fetch_array($sql);
                return $row['id'];
                break;

            case 2:
                $i = 1;
                foreach ($vrednost AS $key => $val) {
                    if ($val == 1) {
                        $sql = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$spr_id' AND vrstni_red='$i'");
                        $row = mysqli_fetch_array($sql);
                        $vrednost[$key] = $row['id'];
                    }
                    $i++;
                }
                return $vrednost;
                break;

            default:
                return $vrednost;
        }

    }

    /**
     * uporabniku pobrise status lurkerja (da je dejansko nekaj vnesel)
     *
     */
    public static function user_not_lurker()
    {

        // ni vec lurker, vse je ok
        if (get('lurker') == 0)
            return '';

        // cache podatkov iz baze
        if (get('lurker') == -1) {
            $sql = sisplet_query("SELECT lurker FROM srv_user WHERE id='" . get('usr_id') . "'");
            $row = mysqli_fetch_array($sql);
            save('lurker', $row['lurker']);
        }

        // pobrisemo mu status lurkerja
        if (get('lurker') == 1) {
            $sql = sisplet_query("UPDATE srv_user SET lurker='0' WHERE id = '" . get('usr_id') . "'");
            save('lurker', 0);
        }

    }

    /**
     * @desc polovimo vrednost 99,98,97, če jo imamo shranjeno
     */
    public static function getOtherValue($spremenljivka)
    {
        $getOtherValue = get('getOtherValue');

        if (array_key_exists($spremenljivka, $getOtherValue)) {
            return $getOtherValue[$spremenljivka];
        }

        $stringOther = "SELECT vre_id FROM srv_specialdata_vrednost WHERE spr_id='" . $spremenljivka . "' AND usr_id='" . get('usr_id') . "'";

        $sqlOther = sisplet_query($stringOther);
        $rowOther = mysqli_fetch_assoc($sqlOther);

        return save('getOtherValue[' . $spremenljivka . ']', $rowOther['vre_id'], 1);
    }

    /**
     * @desc polovimo vrednost 99,98,97, če jo imamo shranjeno
     */
    public static function setOtherValue($spremenljivka, $vrednost = null)
    {
        if ($vrednost != null)
            $sqlInsert = sisplet_query("INSERT INTO srv_specialdata_vrednost (spr_id, vre_id, usr_id) " .
                "VALUES ('" . $spremenljivka . "', '" . $vrednost . "', '" . get('usr_id') . "') " .
                "ON DUPLICATE KEY UPDATE vre_id='" . $vrednost . "'");
        else
            $sqlDelete = sisplet_query("DELETE from srv_specialdata_vrednost WHERE spr_id = '" . $spremenljivka . "' AND usr_id = '" . get('usr_id') . "'");
        return mysqli_affected_rows($GLOBALS['connect_db']);
    }


}