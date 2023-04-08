<?php

class Cache
{

    private static $srv_spremenljivka = null;

    private static $srv_branching__el_spr__el_if = null;    // tabela s pointerji ce iscemo po element_spr in element_if
    private static $srv_branching__parent = null;            // tabela s pointerji ce iscemo po parentu (zraven je se order by)

    private static $srv_if = null;

    private static $srv_condition = null;

    private static $_spremenljivkaLegenda = null;

    /**
     * vrne vrstico za podano spremenljivko iz tabele srv_spremenljivka
     * če je podana vrednost vračamo samo specifično vrednost
     *
     * @param mixed $spr
     * @param $vrednost $spr
     * @return Cache
     */
    static function srv_spremenljivka($spr, $vrednost = null)
    {

        if (isset(self::$srv_spremenljivka[$spr])) {
            if ($vrednost == null) {
                return self::$srv_spremenljivka[$spr];
            } else {
                if (isset(self::$srv_spremenljivka[$spr][$vrednost])) {
                    return self::$srv_spremenljivka[$spr][$vrednost];
                } else {
                    return null;
                }
            }
        }

        $sql = sisplet_query("SELECT * FROM srv_spremenljivka WHERE id = '$spr'");
        self::$srv_spremenljivka[$spr] = mysqli_fetch_assoc($sql);

        if ($vrednost == null) {
            return self::$srv_spremenljivka[$spr];
        } else {
            if (isset(self::$srv_spremenljivka[$spr][$vrednost])) {
                return self::$srv_spremenljivka[$spr][$vrednost];
            } else {
                return null;
            }
        }
    }

    /**
     * naenkrat prebere vse spremenljivke za podano anketo (da ne delamo queryja vsakic posebej, kjer prikazujemo vse spremenljivke)
     *
     * @param mixed $anketa
     */
    static function cache_all_srv_spremenljivka($anketa, $force = false)
    {
        if (self::$srv_spremenljivka != null && $force == false) return;

        $sql = sisplet_query("SELECT s.* FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$anketa' ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");
        while ($row = mysqli_fetch_assoc($sql)) {
            self::$srv_spremenljivka[$row['id']] = $row;
        }
		
        return self::$srv_spremenljivka;
    }

    static function get_spremenljivka($spr, $column)
    {
        $row = self::srv_spremenljivka($spr);

        return $row[$column];
    }

    static function clear_cache()
    {
        self::$srv_spremenljivka = array();
    }

    static function clear_branching_cache()
    {
        self::$srv_branching__el_spr__el_if = null;
        self::$srv_branching__parent = null;
    }

    static function clear_cache_all()
    {
        self::$srv_spremenljivka = null;

        self::$srv_branching__el_spr__el_if = null;
        self::$srv_branching__parent = null;

        self::$srv_if = null;

        self::$srv_condition = null;
    }

    static function cache_all_srv_branching($anketa, $force = false)
    {
        if (self::$srv_branching__el_spr__el_if !== null && self::$srv_branching__parent !== null && $force == false) return;

        self::$srv_branching__el_spr__el_if = array();        // to je zaradi preverjanja if != null (ce je spodnji select prazen)
        self::$srv_branching__parent = array();

        $sql = sisplet_query("SELECT * FROM srv_branching WHERE ank_id='$anketa' ORDER BY vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        while ($row = mysqli_fetch_assoc($sql)) {
            self::$srv_branching__el_spr__el_if[$row['element_spr']][$row['element_if']] = $row;
            self::$srv_branching__parent[$row['parent']][] = $row;
        }
    }

    static function srv_branching($element_spr, $element_if)
    {
        if (isset(self::$srv_branching__el_spr__el_if[$element_spr][$element_if])) {
            return self::$srv_branching__el_spr__el_if[$element_spr][$element_if];
        }

        $sql = sisplet_query("SELECT * FROM srv_branching WHERE element_spr='$element_spr' AND element_if='$element_if'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        self::$srv_branching__el_spr__el_if[$element_spr][$element_if] = mysqli_fetch_assoc($sql);

        return self::$srv_branching__el_spr__el_if[$element_spr][$element_if];
    }

    static function srv_branching_parent($anketa, $parent)
    {
        if (self::$srv_branching__parent === null) {
            self::cache_all_srv_branching($anketa);
        }

        if (isset(self::$srv_branching__parent[$parent])) {
            return self::$srv_branching__parent[$parent];
        }

        return array(); // ce ni zadetka, vrnemo prazen array
    }

    static function srv_if($if)
    {
        if (isset(self::$srv_if[$if])) {
            return self::$srv_if[$if];
        }

        $sql = sisplet_query("SELECT * FROM srv_if WHERE id = '$if'");
        self::$srv_if[$if] = mysqli_fetch_assoc($sql);

        return self::$srv_if[$if];
    }

    static function cache_all_srv_if($anketa, $force = false)
    {
        if (self::$srv_if != null && $force == false) return;

        $sql = sisplet_query("SELECT srv_if.* FROM srv_if, srv_branching b WHERE b.element_if=srv_if.id AND b.ank_id='$anketa'");
        while ($row = mysqli_fetch_assoc($sql)) {
            self::$srv_if[$row['id']] = $row;
        }
		
		return self::$srv_if;
    }

    static function srv_condition($if)
    {
        if (isset(self::$srv_condition[$if]) && is_resource(self::$srv_condition[$if])) {
            if (mysqli_num_rows(self::$srv_condition[$if]) > 0)
                mysqli_data_seek(self::$srv_condition[$if], 0);
            return self::$srv_condition[$if];
        }

        self::$srv_condition[$if] = sisplet_query("SELECT * FROM srv_condition WHERE if_id = '$if' ORDER BY vrstni_red ASC");
        return self::$srv_condition[$if];
    }

    static function spremenljivkaLegenda($spr_id)
    {
        if (is_array(self::$_spremenljivkaLegenda) && isset(self::$_spremenljivkaLegenda[$spr_id])) {
            return self::$_spremenljivkaLegenda[$spr_id];
        } else {
            global $lang;

            $result = array();
            $spremenljivka = self::srv_spremenljivka($spr_id);

            switch ($spremenljivka['tip']) {
                case 0 : // Polje drugo
                    $result['tip'] = $lang['srv_sklop_polje_drugo'];
                    break;
                case 1 : // radio
                case 2 : // check
                case 3 : // drop
                case 7 : // number
                case 21 : // besedilo
                    $result['tip'] = $lang['srv_sklop_osnovna_short'] . ' - ' . $lang['srv_vprasanje_tip_' . $spremenljivka['tip']];
                    break;
                case 6 : // mgrid
                case 16 : // mcheck
                case 19 : // mtext
                case 20 : // mnumber
                    $result['tip'] = $lang['srv_sklop_tabele_short'] . ' - ' . $lang['srv_vprasanje_tip_' . $spremenljivka['tip']];
                    break;
                default : // mnumber
                    //$result = $lang['srv_sklop_posebna'];
                    $result['tip'] = $lang['srv_sklop_posebna_short'] . ' - ' . $lang['srv_vprasanje_tip_' . $spremenljivka['tip']];
                    break;
            }
            switch ($spremenljivka['tip']) {
                case 1 : // radio
                case 2 : // check
                case 3 : // drop
                case 6 : // mradio
                case 16 : // mcheck
                case 17 : // razvrščanje
                    $result['izrazanje'] = $lang['srv_analiza_vrsta_kate'];
                    break;
                case 4 : // text
                case 19 : // mtext
                case 21 : // text*
                    $result['izrazanje'] = $lang['srv_analiza_vrsta_bese'];
                    break;
                case 7 : // number
                case 18 : // vsota
                case 20 : // mnumber
                case 22 : // kalkulacija
                case 25 : // kvota
                    $result['izrazanje'] = $lang['srv_analiza_vrsta_stev'];
                    break;
                case 8 : // datum
                    $result['izrazanje'] = $lang['srv_analiza_vrsta_stev'];
                    break;
                case 5 : // nagovor
                    $result['izrazanje'] = $lang['srv_analiza_vrsta_nago'];
                    break;
            }

            # skalo rešimo objektno
            $objectSkala = new SpremenljivkaSkala($spr_id);
            $result['skalaAsValue'] = $objectSkala->getSkala();
            $result['skala'] = $objectSkala->getSkalaAsText();

            self::$_spremenljivkaLegenda[$spr_id] = $result;
            return $result;
        }
    }

    /************************************************
     * Vreno srv_vrednosti za določeno spremenljivko_id
     *
     * @param (int) $spremenljivka_id
     * @return (object)
     ************************************************/
    protected $spremenljivka_id;

    public static function cache_all_srv_vrednost($spremenljivka_id)
    {
        $sql = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$spremenljivka_id'");
        $polje = array();
        while ($row = $sql->fetch_object()) {
            $polje[] = $row;
        }

        return $polje;
    }
}

?>