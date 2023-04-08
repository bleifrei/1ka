<?php

/** Class ki je nadomestil session pri nastavitvah userja
 *  Januar 2013
 *
 *
 *
 * @author Peter Hrvatin
 *
 */
class SurveyUserSessionDestructor
{
    /** pokli�emo destruktor funkcijo
     *
     */
    public function __destruct()
    {
        SurveyUserSession::destruct();
    }
}

class SurveyUserSession
{

    private static $destructorInstance;
    private static $ank_id = null;                // ID ankete
    private static $dataArray = array();


    static function Init($anketa = null)
    {
        global $global_user_id;

        if (null === self::$destructorInstance)
            self::$destructorInstance = new SurveyUserSessionDestructor();

        if ($anketa == null || (int)$anketa == 0 || !is_numeric($anketa)) {
            throw new Exception('Survey ID is mandatory for SurveyUserSession!');
        }
        self::$ank_id = $anketa;

        # preberemo vse nastavitve za to anketo - ce jih se nimamo
        if (!is_countable(self::$dataArray) || count(self::$dataArray) == 0) {
            $sql = sisplet_query("SELECT data FROM srv_user_session WHERE ank_id='" . self::$ank_id . "' AND usr_id='$global_user_id'");
            $row = mysqli_fetch_array($sql);

            self::$dataArray = unserialize($row['data']);
        }
    }

    static function getData($what = null)
    {

        if (self::$ank_id == null) {
            throw new Exception('Survey ID is mandatory for SurveyUserSession!');
            return null;
        }

        if ($what == null) {
            return self::$dataArray;
        } else if (isset(self::$dataArray[$what])) {
            return self::$dataArray[$what];
        }

        return null;
    }

    public static function saveData($value, $what = null)
    {
        global $global_user_id;

        if (self::$ank_id == null) {
            throw new Exception('Survey ID is mandatory for SurveyUserSession!');
            return null;
        }


        // Ce smo uporabili vse podatke
        if ($what == null) {
            self::$dataArray = $value;

            // Brez tega break nekje ne dela - TODO
            $dataString = mysqli_real_escape_string($GLOBALS['connect_db'], serialize(self::$dataArray));
            $sql = sisplet_query("INSERT INTO srv_user_session (ank_id, usr_id, data) VALUES ('" . self::$ank_id . "', '$global_user_id', '$dataString') ON DUPLICATE KEY UPDATE data='$dataString'");

            return true;
        } // Ce smo uporabljali podatke samo za posamezno podstran
        else {
            if (!is_string($what)) {
                throw new Exception('Variable \'what\' must be string!');
                return null;
            } else {
                self::$dataArray[$what] = $value;
                return true;
            }
        }
    }

    /**
     * Izbriši seje za dotičnega uporabnika
     */
    public static function delete()
    {
        global $global_user_id;

        if (self::$ank_id == null) {
            throw new Exception('Survey ID is mandatory for SurveyUserSession!');
            return null;
        }

        sisplet_query("DELETE FROM srv_user_session WHERE ank_id = '" . self::$ank_id . "' AND  usr_id='" . $global_user_id . "'");
    }

    /** ob ukinitvi klassa shranimo vse vrednosti
     *
     */
    static public function destruct()
    {
        global $global_user_id;
        global $connect_db;

        if (self::$ank_id == null) {
            throw new Exception('Survey ID is mandatory for SurveyUserSession!');
            return null;
        }

        // v destructu več nimamo connect_db resoursa
        if ($connect_db == null) {
            // poizkusimo še 1x
            global $mysql_server, $mysql_username, $mysql_password, $mysql_database_name;
            if (!$connect_db = mysqli_connect($mysql_server, $mysql_username, $mysql_password, $mysql_database_name)) {
                die ('Please try again later [ERR: DB])');
            }
        }

        # pripravimo string za shranjevanje
        if (is_countable(self::$dataArray) && count(self::$dataArray) > 0) {
            $dataString = mysqli_real_escape_string($GLOBALS['connect_db'], serialize(self::$dataArray));

            $sql = sisplet_query("INSERT INTO srv_user_session (ank_id, usr_id, data) VALUES ('" . self::$ank_id . "', '$global_user_id', '$dataString') ON DUPLICATE KEY UPDATE data='$dataString'");
            if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

            // Dodano ker drugace ne zapise v bazo (zaradi ExclusiveLock classa)
            sisplet_query("COMMIT");
        }
    }
}

?>