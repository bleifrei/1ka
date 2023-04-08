<?php
/**
 * MINI - an extremely simple naked PHP application
 *
 * @package mini
 * @author Panique
 * @link http://www.php-mini.com
 * @link https://github.com/panique/mini/
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace App\Controllers;

use App\Controllers\VariableClass as Variable;
use App\Models\Model as Model;
use PDO;

class Controller
{
    /**
     * @var null Database Connection
     */
    public $db = null;

    /**
     * @var null Model
     */
    public $model = null;

    /**
     * Whenever controller is created, open a database connection too and load "the model".
     */
    public function __construct()
    {
        $this->openDatabaseConnection();
        $this->loadModel();
        $this->getGlobalVariables();
    }

    /************************************************
     * Vse globalne spremenljivke dodamo v omenjen razred, da jih lahko potem kličemo na posameznem razredu
     *
     * @return $this
     ************************************************/
    public static $global_user_id;
    public static $admin_type;
    public static $site_url;
    public static $site_path;
    public static $lang;
    public static $mysql_database_name;

    public function getGlobalVariables()
    {
        // Definiramo globalne spremenljivke, ki jih kasneje uporabljamo v funkcijah
        global $global_user_id;
        global $admin_type;
        global $site_url;
        global $site_path;
        global $lang;
        global $mysql_database_name;

        self::$global_user_id = $global_user_id;
        self::$admin_type = $admin_type;
        self::$site_url = $site_url;
        self::$site_path = $site_path;
        self::$lang = $lang;
        self::$mysql_database_name = $mysql_database_name;
    }

    /************************************************
     * Pridobimo vse variable, ki se uporabljajo za main/survey in jih dodamo na Controller -> $this variable
     *
     * @return $this
     ************************************************/
    public function getAllVariables()
    {
        // pridobimo vse spremenljivke, ki jih uporabljamo med različnimi razredi
        $var = Variable::getAll();

        // vrnemo kot $this parameter, da jih uporabljamo znotraj razreda in ni potrebno vse popravljati
        // v obliko Variable::get('name') amap preprosto kličemo $this->name
        foreach ($var as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }

    /**
     * Open the database connection with the credentials from application/config/config.php
     */
    private function openDatabaseConnection()
    {
        // set the (optional) options of the PDO connection. in this case, we set the fetch mode to
        // "objects", which means all results will be objects, like this: $result->user_name !
        // For example, fetch mode FETCH_ASSOC would return results like this: $result["user_name] !
        // @see http://www.php.net/manual/en/pdostatement.fetch.php
        $options = array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ, PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING);

        // generate a database connection, using the PDO connector
        // @see http://net.tutsplus.com/tutorials/php/why-you-should-be-using-phps-pdo-for-database-access/
        $this->db = new PDO(DB_TYPE . ':host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS, $options);
    }

    /**
     * Loads the "model".
     * @return object model
     */
    public function loadModel()
    {
        $this->model = new Model($this->db);
    }
}