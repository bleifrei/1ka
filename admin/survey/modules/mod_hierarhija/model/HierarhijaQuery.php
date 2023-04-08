<?php

/**
 * Avtor: Robert Šmalc
 * Date: 8/20/15
 */

namespace Hierarhija\Model;

use Hierarhija\Hierarhija;
use Hierarhija\HierarhijaHelper;

class HierarhijaQuery
{

    /**
     *  Pridobimo vse nivoje ravno za trenutno anketo
     *
     * @param string $anketa - ID ankete
     * @param string $id - izbrane ravni
     * @return SLQ results or NULL
     */
    protected $anketa;
    protected $id;
    protected $max_struktura;

    public static function getRavniAdmin($anketa, $id = null)
    {

        $sql = "SELECT * FROM srv_hierarhija_ravni WHERE anketa_id='" . $anketa . "'";
        if (!is_null($id)) {
            $sql .= " AND id='" . $id . "'";
        } else {
            $sql .= "ORDER BY level";
        }
        $sql1 = sisplet_query($sql);
        $results = null;
        if (!empty($sql1) && mysqli_num_rows($sql1) > 0)
            $results = $sql1;

        return $results;
    }

    /**
     *  Pridobimo vse sifrante za trenutno anketo
     *
     * @param string $anketa - ID ankete
     * @param integer $max_struktura - do katerega nivoja je struktura že vnešena
     * @return SLQ results or NULL
     */
    public function getSifrantAdmin($anketa, $max_struktura = 999, $user_level = 0)
    {
        $sql_ravni = sisplet_query("SELECT * FROM srv_hierarhija_ravni WHERE anketa_id='" . $anketa . "' ORDER BY level");
        $sql_sifra = sisplet_query("
            SELECT
                hs.id AS id,
                hr.level AS level,
                hs.ime AS sifrant
            FROM srv_hierarhija_ravni AS hr
            LEFT JOIN
              srv_hierarhija_sifranti AS hs ON hr.id = hs.hierarhija_ravni_id
            WHERE anketa_id='$anketa' AND hr.level > '" . $user_level . "'
            ORDER BY hr.level, hs.ime
            ");

        $results = null;
        if (!empty($sql_ravni) && mysqli_num_rows($sql_ravni) > 0) {
            while ($nivoObj = mysqli_fetch_object($sql_ravni)) {
                //v kolikor pri izgradnji strukture še nimamo nobenega podatka potem ponudimo samo 1.nivo
                if ($nivoObj->level <= ($max_struktura + 1) || $nivoObj->level == 1) {
                    $results['nivoji'][] = [
                        'id' => (int)$nivoObj->id,
                        'level' => $nivoObj->level,
                        'ime' => $nivoObj->ime,
                        'unikaten' => $nivoObj->unikaten
                    ];
                }

            }
            while ($sifraObj = mysqli_fetch_object($sql_sifra)) {
                $results['sifranti'][] = [
                    'id' => (int)$sifraObj->id,
                    'level' => $sifraObj->level,
                    'sifrant' => $sifraObj->sifrant
                ];
            }
        }

        return $results;
    }

    /**
     * Pridobimo ravni, šifrante in strukturo HIERARHIJE, ki jo je že vnesel uporabnik
     *
     * @param string $anketa
     * @return array query
     */
    public function getSifrantHierarhija($anketa)
    {
        $results['struktura'] = (new HierarhijaOnlyQuery())->queryStruktura($anketa)->fetch_object();
        $max_struktura = (new HierarhijaOnlyQuery())->queryStruktura($anketa, ', MAX(str.level) AS max_level')->fetch_object()->max_level;

        $results = $this->getSifrantAdmin($anketa, $max_struktura);

        return $results;
    }

    /**
     * INSERT - srv_Spremenljivka in srv_branching
     *
     * @param (array) $spremenljivka
     * @param (int) $anketa
     * @return
     */
    protected $spremenljivka;
    protected $sql_sifra;
    protected $spremenljivka_id;

    public function insertSpremenljivkaBranching($spremenljivka, $sql_sifra, $anketa, $vloga = null)
    {
        if (is_array($spremenljivka)) {
            $st_vrednosti = (is_null($sql_sifra) ? '2' : $sql_sifra->num_rows);

            // V kolikor je vloga potem je vprašanje zaklenjeno
            $locked = (!empty($vloga) ? 1 : 0);

            $this->spremenljivka_id = sisplet_query("INSERT INTO 
                                                        srv_spremenljivka 
                                                        (id, gru_id, naslov, variable, variable_custom, size, tip, vrstni_red, visible, sistem, locked) 
                                                    VALUES 
                                                        ('', '$spremenljivka[0]', '$spremenljivka[1]', '$spremenljivka[2]', '1', '$st_vrednosti', '1', '$spremenljivka[4]', '0', '1', '$locked')
                                                    ", "id");

        }

        //vstavimoe v branching vse nivoje
        if (is_array($spremenljivka) && $anketa) {
            $sql_branching = sisplet_query("INSERT INTO srv_branching (ank_id, parent, element_spr, element_if, vrstni_red, pagebreak) VALUES ('$anketa', 0, '" . $this->spremenljivka_id . "', 0, '$spremenljivka[4]', 0)");
            $this->sqlError($sql_branching);
        }

        if (!empty($sql_sifra) && !empty($this->spremenljivka_id) && is_null($vloga))
            $this->insertVrednost($sql_sifra);

        if (!empty($this->spremenljivka_id) && !is_null($vloga))
            $this->insertVrednost(null, $vloga);

    }

    /**
     * INSERT - srv_vrednost odogovore za sledečo spremenljivko
     *
     * @param (array) $vrednost
     * @return
     */
    protected $vrednost;

    public function insertVrednost($vrednost, $vloga = null)
    {
        if (!is_null($vrednost)) {
            $vrstni_red_sifre = 1;
            while ($sifrant = $vrednost->fetch_object()) {
                $vrednost_id = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red) VALUES ('', '$this->spremenljivka_id', '$sifrant->ime', '$vrstni_red_sifre', '$vrstni_red_sifre')", "id");

                //vpišemo šifrante in vrednosti odgovorov v pivot tabelo za lažje generiranje URL naslovov
                $sql_pivot = sisplet_query("INSERT INTO srv_hierarhija_sifrant_vrednost (sifrant_id, vrednost_id) VALUES ($sifrant->id, $vrednost_id)");
                $this->sqlError($sql_pivot);

                $vrstni_red_sifre++;
            }
        } elseif ($vloga == 1) {
            $vrstni_red_sifre = 1;
            $vloga = ['učenec', 'učitelj'];
            foreach ($vloga as $row) {
                $sql_vrednost = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red) VALUES ('', '$this->spremenljivka_id', '$row', '$vrstni_red_sifre', '$vrstni_red_sifre')");
                $this->sqlError($sql_vrednost);
                $vrstni_red_sifre++;

            }
        }

    }

    /**
     * Vreno celotno drevesno strukturo za userja v hierarhiji
     *
     * @param (int) $anketa
     * @return (array)
     */


    public function pridobiHierarhijoNavzgor($anketa, $odgovori = null, $user = null)
    {
        global $global_user_id;

        if (is_null($user))
            $user = $global_user_id;

        $sql_str = (new HierarhijaOnlyQuery())->queryStrukturaUsersOnlyId($anketa, $user);
        $this->sqlError($sql_str);

        # pridobimo tudi ID strukture hierarhije, da lahko kasneje pridobimo drevesno strukturo celotnega drevesa in vsak id strukture damo v polje, ker bomo poiskali vso strukturo
        ## V kolikor bi učitelj izvajal samoevalvacijo za dva predmeta
        while ($obj = $sql_str->fetch_object()) {
            $id_nivojev[] = $obj->struktur_id;
        }

        if (is_null($id_nivojev))
            return null;

        # gremo po vseh id-jih strukture in pridobimo strukturo navzgor
        foreach ($id_nivojev as $nivo) {
            $id = $nivo;

            ## v koliko imamo ID strukture potem naredimo zanko in preverimo parent_id, ter ID strukture napolnimo v polje
            while (!is_null($id)) {
                $row = (new HierarhijaOnlyQuery())->queryStruktura($anketa, null, ' AND str.id=' . $id)->fetch_object();

                if (!is_null($odgovori) && $odgovori == TRUE) {

                    $st_odgovora = sisplet_query("
                                      SELECT
                                        sv.vrednost_id AS vrednost_id,
                                        v.variable AS variable
                                      FROM
                                        srv_hierarhija_sifrant_vrednost as sv
                                      LEFT JOIN
                                        srv_vrednost as v ON sv.vrednost_id = v.id
                                      WHERE
                                        sv.sifrant_id='$row->sifrant_id'
                                      ");

                    $hiearhija[$nivo][] = [
                        'nivo' => 'nivo' . $row->level,
                        'id' => $row->sifrant_id,
                        'st_odgovora' => $st_odgovora->fetch_array()['variable']
                    ];
                } else {
                    $hiearhija[$nivo][] = [
                        'nivo' => 'nivo' . $row->level,
                        'id' => $row->sifrant_id
                    ];
                }
                $id = $row->parent_id;
            }
        }

        return $hiearhija;
    }

    public static function posodobiSifranteVrednostiGledeNaTrenutenIdStrukture($id)
    {
        while (!is_null($id)) {
            $row = sisplet_query("SELECT * FROM srv_hierarhija_struktura WHERE id='$id' ORDER BY level");
            $row = $row->fetch_object();

            $hiearhija[$row->level] = [
                'id' => $id,
                'id_sifranta' => $row->hierarhija_sifranti_id,
                'select' => $row->level . '-' . $row->hierarhija_sifranti_id,
                'hierarhija_ravni_id' => $row->hierarhija_ravni_id
            ];

            $id = $row->parent_id;
        }

        return $hiearhija;
    }

    /**
     * Funkcija poišče v PIVOT tabeli srv_hierarhija_sifrant_vrednost vrednost_id
     *
     * @param (int) $sifrant_id
     * @return (int) vrednost_id
     */

    protected $sifrant_id;

    public function getVrednostIdFromPivot($sifrant_id)
    {
        $sql = sisplet_query("SELECT * FROM srv_hierarhija_sifrant_vrednost WHERE sifrant_id='$sifrant_id'");

        if (!empty($sql))
            return $sql->fetch_object()->vrednost_id;
    }

    /**
     * Pridobimo ID vseh struktur, ki je pod določeno strukturi, ker bomo tudi te strukture brisali
     *
     * @param (int) $anketa_id
     * @param (int) $id - strukture
     * @return (array) $this->element
     */
    public function pridobiIdStruktureDoKonca($id, $anketa_id)
    {

        if (!isset($this->element) || !is_array($this->element))
            $this->element = array();

        $sql_id = sisplet_query("SELECT * FROM srv_hierarhija_struktura WHERE parent_id='$id' AND anketa_id='$anketa_id'");
        $this->sqlError($sql_id);

        if ($sql_id->num_rows > 0) {
            while ($obj = $sql_id->fetch_object()) {
                if (!isset($element) || !in_array($obj->id, $element)) {
                    $this->element[] = $obj->id;
                    $this->pridobiIdStruktureDoKonca($obj->id, $anketa_id);
                }
            }
        }

        return $this->element;
    }

    /**
     * Shrani ali posodobi dodatno opcijo pri hierarhiji - nastavitve
     */
    public function saveHierarhijaOptions($anketa_id, $option, $value, $id = null)
    {
        if (is_null($id)) {
            $ce_obstaja_id = $this->getRowOptions($anketa_id, $option);
            if (!empty($ce_obstaja_id->id))
                $id = $ce_obstaja_id->id;
        }

        if (is_null($id)) {
            $sql = sisplet_query("INSERT INTO srv_hierarhija_options (anketa_id, option_name, option_value) VALUES ('" . $anketa_id . "', '" . $option . "', '" . $value . "')");
        } else {
            $sql = sisplet_query("UPDATE srv_hierarhija_options SET option_value='" . $value . "' WHERE anketa_id='" . $anketa_id . "' AND id='" . $id . "'");
        }

        $this->sqlError($sql);
    }

    /**
     * get ali delete dodatnih opcij
     */
    public function getDeleteHierarhijaOptions($anketa_id, $option = null, $id = null, $delete = null, $json = true)
    {
        if (is_null($option) && is_null($id) && is_null($delete)) {
            $sql = sisplet_query("SELECT * FROM srv_hierarhija_options WHERE anketa_id='" . $anketa_id . "'");
        } elseif (is_null($delete)) {
            if (!empty($anketa_id) && !empty($option))
                $sql = sisplet_query("SELECT * FROM srv_hierarhija_options WHERE anketa_id='" . $anketa_id . "' AND option_name='" . $option . "'");

            if (!is_null($id))
                $sql = sisplet_query("SELECT * FROM srv_hierarhija_options WHERE id='" . $id . "'");

        } else {
            if (!empty($anketa_id) && !empty($option) && !is_null($delete))
                $sql = sisplet_query("DELETE FROM srv_hierarhija_options WHERE anketa_id='" . $anketa_id . "' AND option_name='" . $option . "'");

            if (!is_null($id) && !is_null($delete))
                $sql = sisplet_query("DELETE FROM srv_hierarhija_options WHERE id='" . $id . "'");

            $this->sqlError($sql);
            return '';
        }
        $this->sqlError($sql);

        $data = array();
        if ($sql->num_rows > 0) {
            while ($row = $sql->fetch_object()) {
                $data[$row->option_name] = $row->option_value;
            }
        }

        if (!$json && is_null($option)) {
            return $data;
        } elseif (!$json && !is_null($option)) {
            return $data[$option];
        }

        echo json_encode($data);
    }

    /**
     * Pridobimo opcijo, ki jo iščemo ali želimo
     *
     * @param (int) $anketa
     * @param (string) $option
     * @return string | value;
     */
    public static function getOptions($anketa, $option)
    {
        return (new HierarhijaQuery())->getDeleteHierarhijaOptions($anketa, $option, null, null, false);
    }

    /**
     * Shranimo opcijo hierarhije - bližnica
     *
     * @param (int) $anketa
     * @param (string) $option
     * @param (string) $value
     * @return string | value;
     */
    public static function saveOptions($anketa, $option, $value)
    {
        return (new HierarhijaQuery())->saveHierarhijaOptions($anketa, $option, $value);
    }


    /**
     * Preverimo, komu vse pošljemo kode (učencem, učitelju ali nobenemu)
     *
     * @param int $anketa
     * @return null || string
     */

    public static function getOptionsPosljiKode($anketa)
    {
        $poslji = null;

        if (!empty(self::getOptions($anketa, 'ne_poslji_kode_ucencem')))
            $poslji[] = 'ucitelju';

        if (!empty(self::getOptions($anketa, 'ne_poslji_kodo_ucitelju')))
            $poslji[] = 'ucencem';

        if (is_null($poslji))
            return 'vsem';

        if (sizeof($poslji) == 1)
            return $poslji[0];

        return 'nikomur';
    }

    public function getRowOptions($anketa_id, $option, $id = null)
    {
        if (is_null($id))
            $sql = sisplet_query("SELECT * FROM srv_hierarhija_options WHERE anketa_id='" . $anketa_id . "' AND option_name='" . $option . "'");

        if (!is_null($id))
            $sql = sisplet_query("SELECT * FROM srv_hierarhija_options WHERE id='" . $id . "'");

        $this->sqlError($sql);

        if ($sql->num_rows > 0)
            return $sql->fetch_object();

        return null;
    }

    /**
     * Vnre polje vse strukture z imeni, ter tudi email naslove, ki so dodane določeni strukturi
     */
    public function json_jstree($anketa, $array = null)
    {
        global $global_user_id;
        $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : null);

        $user_level = HierarhijaOnlyQuery::queryStrukturaUsersLevel($anketa, $global_user_id);

        //vpisan kot super_admin in ima dostop do celotne hierarhije
        if ($hierarhija_type < 5) {
            $hierarhija = (new HierarhijaOnlyQuery())->queryStruktura($anketa);
        } elseif ($user_level->num_rows > 0) { //preverimo, če je uporabnik že kje vpisan v hierarhiji in na kateri nivo - level
            while ($obj = $user_level->fetch_object()) {
                //dodamo id v array, ker le tako lahko zgradimo drevo za ustreznega uporabnika, ki nima vpogleda v celotno strukturo
                $hierarhija_drevo[] = $obj->struktura_id;
                $hierarhija_drevo[] = $obj->parent_id;

                //pridobimo hierarhijo navzgor
                $hierarhija_navzgor = HierarhijaQuery::posodobiSifranteVrednostiGledeNaTrenutenIdStrukture($obj->struktura_id);
                foreach ($hierarhija_navzgor as $n) {
                    $hierarhija_drevo[] = $n['id']; //vse id,je strukture vnesemo v polje, kjer bomo nato preverjali, če obstaja
                }
            }

            // Pridobimo celotno strukturo hierarhije tudi za elemente, ki jih kasneje ne izpišemo
            $hierarhija = (new HierarhijaOnlyQuery())->queryStruktura($anketa, null, ' AND hr.level>1');

        }

        //pridobimo podate o uporabnikih - email in ne prikazujemo uporabnika, ki je višje v hierarhiji od dejansko prijavljenega uporabnika
        $users = (new HierarhijaOnlyQuery())->queryStrukturaUsersGroupBy($anketa);
        $user_level = null;
        while ($row = $users->fetch_assoc()) {
            ## Pridobimo uporabnika na največjem možnem nivoju
            if ($global_user_id == $row['user_id'] && (is_null($user_level) || $user_level > $row['level']))
                $user_level = $row['level'];

            ## Ko imamo določen nivo uporabnika, potem pod njemu pridobimo vse ostale uporabnike, ki jih omenjen uporabnik lahko vidi
            if (!is_null($user_level) && $row['level'] >= $user_level)
                $hierarhija_users[] = $row;

            ## V kolikor gre za administratorja ankete
            if ($hierarhija_type < 5)
                $hierarhija_users[] = $row;
        }

        # V kolikor imamo polje hierarhije z ID-ji pridobljenimi za hierarhijo navzgor
        if (!is_null($hierarhija_drevo)) {
            while ($obj = $hierarhija->fetch_object()) {
                $up = null;
                foreach ($hierarhija_users as $user) {
                    if ($obj->id == $user['id'])
                        $up = $user['uporabniki'];
                }

                ## Če se id strukture ujema z uporabnikovo hierarhijo
                ## Če se trenutni nivo/level strukture ujema z uporabnikov, potem od tega nivoja naprej izpišemo celotno strukturo
                if (in_array($obj->id, $hierarhija_drevo) || ($obj->ravni_level > $user_level && in_array($obj->parent_id, $hierarhija_drevo))) {
                    $hierarhija_drevo[] = $obj->parent_id;
                    $hierarhija_drevo[] = $obj->id;

                    $struktura[] = [
                        'id' => $obj->id,
                        'parent' => ((is_null($obj->parent_id) || $obj->ravni_level == 2) ? '#' : $obj->parent_id),
                        "text" => $obj->sifrant_ime . (!is_null($up) ? "  -  (" . $up . ")" : null)
                    ];
                }
            }

            #Izpišemo celotno hierarhijo za admin uporabnika
        } elseif ($hierarhija_type < 5) {
            while ($obj = $hierarhija->fetch_object()) {
                $up = null;

                //preverimo, če so uporabniki v hierarhiji
                if (!empty($hierarhija_users)) {
                    foreach ($hierarhija_users as $user) {
                        if ($obj->id == $user['id'])
                            $up = $user['uporabniki'];
                    }
                }

                $struktura[] = [
                    'id' => $obj->id,
                    'parent' => (is_null($obj->parent_id) ? '#' : $obj->parent_id),
                    'text' => $obj->sifrant_ime . (!is_null($up) ? "  -  (" . $up . ")" : null)
                ];

                // v kolikor na gre ja jstree potem potrebujemo tudi številko levela za nadaljne operacije
                if (!is_null($array)) {
                    end($struktura);
                    $key = key($struktura);
                    $struktura[$key]['level'] = $obj->level;
                }

            }
        }
        return $struktura;
    }

    /**
     * SqL poizvedba, kjer pridobimo celotno strukturo in izdela polje, kjer nato pripravimo polje za vpis v Datatables
     *
     * @param $anketa
     * @return array
     */
    public function hierarhijaArrayDataTables($anketa, $vpisani = null, $csv = false)
    {
        $this->anketa = $anketa;

        $sql_vsa_hierarhija = (new HierarhijaOnlyQuery())->queryStruktura($anketa);

        // Izdelamo polje iz sql poizvedbe, da bomo v nadaljevanju izdelali vgnezdeno polje
        $struktura = [];
        while ($obj = $sql_vsa_hierarhija->fetch_object()) {
            // V kolikor ima strukturo že od prej vpisanih elementov, potem na tistem nivoju vpišemo samo ID strukture in vse ostale odstranimo
            if (empty($vpisani[$obj->level - 1]) || $vpisani[$obj->level - 1]['izbrani']['id'] == $obj->id) {
                $struktura[] = [
                    'id' => $obj->id,
                    'parent_id' => (is_null($obj->parent_id) ? 0 : $obj->parent_id),
                    'name' => $obj->sifrant_ime,
                    'level' => $obj->level
                ];
            }
        }

        // Pridobimo max število nivojev
        $max_st = (new HierarhijaOnlyQuery())->getRavni($anketa, 'MAX(level) AS max_level')->fetch_object()->max_level;

        return $this->izdelajVrsticeStrukture($struktura, $max_st, 0, [], $csv);
    }

    /**
     * Iz polja celotne strukture naredimo vrstice za vpis v Datatables
     *
     * @param $elements - polje celotne strukture
     * @param $parentId
     * @param $polje - polje ene vrstice strukture, kjer elemente preneašamo napraj, da jih na koncu zapišemo kot vrstica v tabeli
     * @param $max_st - maksimalno število nivojev, potrebno za generiranje praznih polj
     * @return array
     */

    protected $skupno_polje = [];

    protected function izdelajVrsticeStrukture(array $elements, $max_st = 1, $parentId = 0, $polje = [], $csv = false)
    {
        global $lang;

        foreach ($elements as $key => $element) {
            // Najprej poiščemoprvi nivo
            if ($element['parent_id'] == $parentId) {
                // Preveri, če obstaja uporabnik za omenjeno polje
                $db_user = (new HierarhijaOnlyQuery())->queryStrukturaUsersGroupBy($this->anketa, ' AND hs.id="' . $element['id'] . '"');

                $uporabniki = null;
                if ($db_user->num_rows > 0)
                    $uporabniki = '<br />(' . $db_user->fetch_object()->uporabniki . ')';

                $zacasno_polje = $polje;
                array_push($zacasno_polje, array(
                    'id' => $element['id'],
                    'level' => $element['level'],
                    'label' => $element['name'] . ((!$csv && !empty($uporabniki)) ? '<span style="color:#ffa608;">' . $uporabniki . '</span>' : ' ' . $uporabniki)
                ));

                // Rekurzivno gremo po vseh elementih do zadnjega, da pridobimo strukturo
                $zadnji_element = $this->izdelajVrsticeStrukture($elements, $max_st, $element['id'], $zacasno_polje, $csv);

                // V kolikor smo prišli do zadnjega elementa potem vpišemo v spremenljivko $this->skupno_polje
                if ($zadnji_element == 0) {

                    // Preverimo koliko elementov smo že vpisali od vseh
                    // Kjer še niso vpisani vpišemo NULL, ker DataTables potrebuje vedeti število vseh nivojev
                    if (sizeof($zacasno_polje) < $max_st && !$csv) {
                        for ($i = sizeof($zacasno_polje); $i < ($max_st - 1); $i++) {
                            array_push($zacasno_polje, null);
                        }

                        // Na zadnji nivo vpišemo klicaj, v kolikor še ni dodan predmet in učitelj
                        array_push($zacasno_polje, array(
                            'label' => '<span style="color:#fa4913; font-style: italic;">Manjka zadnji nivo</span><a href="/" id="help_srv_hierarhy_last_level_missing" lang="' . $lang['id'] . '" class="help" onclick="return false;" title_txt="' . $lang['help'] . '" style="color:#fa4913; font-weight: bold;float: right;padding: 0 30px;cursor: pointer;"> 
                                            (!)
                                        </a>'
                        ));
                    }

                    // Dodamo še gumbe
                    if (!$csv) {
                        $gumbi = '<span data-id="' . $element['id'] . '" class="ikona ikona-uporabniki" onclick="urediVrsticoHierarhije(' . $element['id'] . ')"></span> 
                              <span data-id="' . $element['id'] . '" class="ikona ikona-kopiraj" onclick="kopirajVrsticoHierarhije(' . $element['id'] . ')"></span> 
                              <span data-id="' . $element['id'] . '" class="ikona ikona-brisi" onclick="pobrisiVrsticoHierarhije(' . $element['id'] . ', 1)"></span>';

                        array_push($zacasno_polje, array(
                            'label' => $gumbi
                        ));
                    }


                    $this->skupno_polje[] = $zacasno_polje;
                }
            }
        }

        // V kolior ni bilo nobenega elementa vpisanov $začano polje, potem smo prišli do konca drevesne strukture in vrnemo nič
        if (is_null($zacasno_polje))
            return 0;

        return $this->skupno_polje;
    }

    /**
     * Pridobi vse uporabnike za točno določeno strukturo in pripadajoče ID-je, da se jih lahko odstrani
     *
     * @param $id - struktura_id
     * @return (array) $izbran_uporabnik
     */
    public function pridobiVseUporabnikeZaSpecificnoStrukturo($id)
    {
        $sql_izbrani_uporabniki = sisplet_query("SELECT 
                                                    u.id as id,
                                                    u.email as email,
                                                    u.name as name,
                                                    u.surname as surname
                                                FROM 
                                                  srv_hierarhija_struktura_users AS stu 
                                                LEFT JOIN 
                                                  users AS u ON u.id = stu.user_id 
                                                WHERE 
                                                  hierarhija_struktura_id='" . $id . "'");

        $izbran_uporabnik = array();
        if ($sql_izbrani_uporabniki->num_rows > 0) {
            while ($row = $sql_izbrani_uporabniki->fetch_object()) {
                $uporabnik = $row->email;

                // V kolikor obstaja ime in priimek potem tudi to dodamo poleg emaila
                if ($row->email != $row->name || $row->email != $row->surname)
                    $uporabnik .= ' (' . $row->name . ' ' . $row->surname . ')';

                $izbran_uporabnik[] = array(
                    'id' => $row->id,
                    'uporabnik' => $uporabnik
                );
            }
        }

        return $izbran_uporabnik;
    }

    /**
     * Pridobi pravice uporabnika za sledečo anketo (v katerem nivoju se nahaja najvišje)
     * @return object (level, struktura_id, parent_id)
     */
    public function preveriPravicoUporabnika($anketa_id)
    {
        global $global_user_id;
        $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : null);

        // tukaj gre za admina in vrnemo samo to
        if ($hierarhija_type < 5)
            return ['uporabnik' => 1];

        $sql_user = HierarhijaOnlyQuery::queryStrukturaUsersLevel($anketa_id, $global_user_id);

        return $sql_user->fetch_object();
    }

    /**
     * Poiščemo hierarhijo od tu navzgor in pridobimo vse ID vrednosti struktur
     * @return array
     */

    public function poisciHierarhijoNavzgor($id, $polje = array())
    {
        // Ko pridemo na vrh potem sortiramo od 1. nivoja navzdol
        if (is_null($id)) {
            $polje = array_reverse($polje);
            return $polje;
        }


        $sql_ravni = sisplet_query("SELECT id, parent_id, level, hierarhija_sifranti_id, hierarhija_ravni_id FROM srv_hierarhija_struktura WHERE id='" . $id . "'");
        while ($row = $sql_ravni->fetch_object()) {
            // Pridobimo še ime omenjenega šifranta
            $ime = (new HierarhijaOnlyQuery())->getSamoSifrant($row->hierarhija_sifranti_id, true)->fetch_object()->ime;

            $polje[] = [
                'ime' => $ime,
                'izbrani' => [
                    'id' => $row->id,
                    'level' => $row->level,
                    'hierarhija_sifranti_id' => $row->hierarhija_sifranti_id,
                    'hierarhija_ravni_id' => $row->hierarhija_ravni_id,
                    'parent_id' => $row->parent_id
                ]
            ];

            $id = $row->parent_id;
        }

        return $this->poisciHierarhijoNavzgor($id, $polje);
    }

    /**
     * Pridobimo podate o uporabniku
     *
     * @param (int) $id
     * @return object
     */
    public static function getUserSurvey($id = null)
    {
        if (is_null($id)) {
            global $global_user_id;
            $id = $global_user_id;
        }

        $user_db = sisplet_query("SELECT * FROM users WHERE id = '" . $id . "'", "obj");

        if (!is_null($user_db))
            return $user_db;

        return null;
    }

    /**
     * Pridobi podatek iz tabele
     *
     * @param (string) $table
     * @param (string) $row - ime stolpca
     * @return array or value
     */
    public static function getHierarhijaShraniRow($id, $col = false)
    {
        $sql = sisplet_query('SELECT * FROM  srv_hierarhija_shrani  WHERE id="' . $id . '"');
        if ($sql->num_rows == 0)
            return null;

        $polje = [];
        while ($obj = $sql->fetch_assoc()) {
            $polje = $obj;
        }

        if ($col)
            return $polje[$col];

        return $polje;
    }

    /**
     * Vpiši podatke v tabelo
     *
     * @param (array) $array
     */
    public function saveHierarhijaShraniRow($array)
    {
        $sql = sisplet_query('SELECT * FROM  srv_hierarhija_shrani  WHERE id="' . $id . '"');
        if ($sql->num_rows == 0)
            return null;

        $polje = [];
        while ($obj = $sql->fetch_assoc()) {
            $polje = $obj;
        }

        if ($col)
            return $polje[$col];

        return $polje;
    }

    /**
     * Prteveri branching, če imamo kako spremenljivko
     *
     * @param int $anketa
     * @return string || int
     */
    public static function preveriBranchingCeJeKakoVprasanje($anketa)
    {
        $branching = sisplet_query("SELECT ank_id, element_spr FROM srv_branching WHERE ank_id='" . $anketa . "'", "obj");

        // Nimamo še nobenega vprašanja
        if (is_null($branching) || sizeof($branching) == 0)
            return 1;

        // preverimo tip vprašanja
        if (is_array($branching)) {
            $vsi_tipi_vprasanj = [];
            foreach ($branching as $row) {
                $element = sisplet_query("SELECT id, tip, variable FROM srv_spremenljivka WHERE id='" . $row->element_spr . "'", "obj");
                // hierarhije je že bila aktivirana na obstoječi anketi
                if ($element->variable == 'vloga')
                    return '9';

                if (!in_array($element->tip, ['5', '6', '21']))
                    return 2;

                // Vse tipe vprašanj dodamo v polje, da preverimo kasnje, da niso samo besedilana vprašanja
                $vsi_tipi_vprasanj[] = $element->tip;
            }

            // Preverimo, če je samo besedilno vprašanje
            if (!in_array('6', $vsi_tipi_vprasanj))
                return 3;

        } else {
            $element = sisplet_query("SELECT id, tip, variable FROM srv_spremenljivka WHERE id='" . $branching->element_spr . "'", "obj");

            if (!in_array($element->tip, ['6', 21]))
                return 2;

            // Imamo samo besedilo vprašanje
            if ($element->tip == 21)
                return 3;
        }


        return 'ok';

    }

    /**
     * Uporabniko dodamo ali onemogočimo dostop
     *
     * @param integer $user_id
     * @param string $tip - insert ali delete
     */
    public static function dostopZaUporabnika($anketa, $user_id, $tip = 'inset')
    {

        if ($tip == 'insert') {
            $user_dostop = sisplet_query("SELECT ank_id, uid FROM srv_dostop WHERE ank_id='" . $anketa . "' AND uid='" . $user_id . "'");

            if (mysqli_num_rows($user_dostop) == 0)
                sisplet_query("INSERT INTO srv_dostop (ank_id, uid, aktiven, dostop) VALUES ('" . $anketa . "', '" . $user_id . "', 1, 'edit,lock,analyse')");
        }

        if ($tip == 'delete') {
            $user_dostop = sisplet_query("SELECT ank_id, uid FROM srv_dostop WHERE ank_id='" . $anketa . "' AND uid='" . $user_id . "'");

            if (mysqli_num_rows($user_dostop) > 0)
                sisplet_query("DELETE FROM srv_dostop WHERE ank_id='" . $anketa . "' AND uid='" . $user_id . "'");
        }
    }


    /**
     * Generiraj supersifro in shrani kode
     *
     * @param int $anketa
     * @param array $kode
     */
    public static function saveSuperSifra($anketa, $kode)
    {
        $kode = serialize($kode);

        $vpis = false;
        while (!$vpis) {
            $vpis = sisplet_query("INSERT INTO srv_hierarhija_supersifra (koda, anketa_id, kode, datetime) VALUES 
                                                              (CONCAT('s','s',SUBSTRING('abcdefghijklmnoprtuvz0123456789', RAND()*30+1, 1),
                                                                      SUBSTRING('abcdefghijklmnoprtuvz0123456789', RAND()*30+1, 1),
                                                                      SUBSTRING('abcdefghijklmnoprtuvz0123456789', RAND()*30+1, 1),
                                                                      SUBSTRING('abcdefghijklmnoprtuvz0123456789', RAND()*30+1, 1),
                                                                      SUBSTRING('abcdefghijklmnoprtuvz0123456789', RAND()*30+1, 1)), '" . $anketa . "', '" . $kode . "', NOW())");


        }

        return self::vseSuperkodeSpripadajocimiHierarhijami($anketa);
    }

    /**
     * Pridobimo vse kode,ki pripadajo superšifri in vrnemo tudi strukturo ter email
     *
     * @param string $koda
     * @param boolean $return
     * @return array $kode or $polje - multiarray
     */
    public static function supersifraPridobiHierarhijo($koda, $return = false){
       $kode = sisplet_query("SELECT kode, anketa_id FROM srv_hierarhija_supersifra WHERE koda='".$koda."'", "obj");
       $anketa = $kode->anketa_id;
       $kode = unserialize($kode->kode);

       // Vrni samo kode
       if($return)
           return $kode;

       $polje = [];
       foreach($kode as $koda){
           $struktura = sisplet_query("SELECT hierarhija_struktura_id AS id FROM srv_hierarhija_koda WHERE koda='".$koda."'", "obj");
           $uporabnik = (new HierarhijaQuery())->pridobiVseUporabnikeZaSpecificnoStrukturo($struktura->id);

           $polje[strtoupper($koda)] = [
               'hierarhija' => HierarhijaHelper::hierarhijaPrikazNaslovovpriUrlju($anketa, $struktura->id, $uporabnik[0]['id']),
               'ucitelj' => $uporabnik[0]['uporabnik']
           ];
       }

       return $polje;
    }


    /**
     * Pridobi vse superkode s pripadajočimi hierarhijami
     *
     * @param int $anketa
     * @return array
     */
    public static function vseSuperkodeSpripadajocimiHierarhijami($anketa)
    {
        $super_sifre = sisplet_query("SELECT koda, kode FROM srv_hierarhija_supersifra WHERE anketa_id='".$anketa."' ORDER BY datetime", "obj");

        if(is_object($super_sifre))
            $super_sifre = [$super_sifre];

        $polje = [];
        foreach($super_sifre as $sifra){
            $polje[strtoupper($sifra->koda)] = self::supersifraPridobiHierarhijo($sifra->koda);
        }

        return $polje;
    }

    protected
        $sql;

    public function sqlError($sql)
    {
        if (!$sql) {
            echo mysqli_error($GLOBALS['connect_db']);
            die();
        }

    }

}