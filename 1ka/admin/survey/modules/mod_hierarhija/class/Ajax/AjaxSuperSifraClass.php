<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 06.07.2017
 *****************************************/

namespace Hierarhija\Ajax;

use Hierarhija\Model\HierarhijaQuery;
use TrackingClass;

class AjaxSuperSifra
{

    private $anketa;
    private $lang;
    private $user_id;

    public function __construct($anketa)
    {
        $this->anketa = $anketa;

        //global
        global $lang;
        global $global_user_id;
        $this->lang = $lang;
        $this->user_id = $global_user_id;

        // tracking - beleženje sprememb
        TrackingClass::update($this->anketa, '22');

        return $this;
    }

    private static $_instance;

    public static function init($anketa)
    {
        if (!static::$_instance)
            return (new AjaxSuperSifra($anketa));

        return static::$_instance;
    }


    /**
     * Shrani superšifro in prikaži v tabeli
     *
     * @return
     */
    public function shrani()
    {
        $kode = ((!empty($_POST['kode']) && is_array($_POST['kode'])) ? $_POST['kode'] : null);

        if(is_null($kode))
            return '';

        $ss = HierarhijaQuery::saveSuperSifra($this->anketa, $kode);

        echo json_encode($ss);
    }

    public function getAll()
    {
        echo json_encode(HierarhijaQuery::vseSuperkodeSpripadajocimiHierarhijami($this->anketa));
    }

}