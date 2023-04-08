<?php

/***************************************
 * Description: Razred omogoča kopiranja obstoječe strukture in shranjevanje v json
 * Autor: Robert Šmalc
 * Created date: 04.11.2016
 *****************************************/
namespace Hierarhija;

use Hierarhija\Model\HierarhijaOnlyQuery;
use Hierarhija\Model\HierarhijaQuery;

class HierarhijaKopiranjeClass
{
    private static $_instance;
    protected $anketa;
    protected $polje_strukture = [];
    protected $user_id;

    public function __construct($anketa)
    {
        $this->anketa = $anketa;

        global $global_user_id;
        $this->user_id = $global_user_id;
    }

    public static function getInstance($anketa)
    {
        if (self::$_instance)
            return self::$_instance;

        return new HierarhijaKopiranjeClass($anketa);
    }

    /**
     * Pridobimo celotno polje in tudi možnsz serialize v string za shranjevanje v bazo uporabimo
     *
     * @param (boolean) $seialize
     * @return array | string
     */
    public function get($serialize = false)
    {
        $struktura = $this->getStrukturaInUporabniki();

        if ($serialize && !empty($struktura))
            return serialize($struktura);

        return $struktura;
    }

    protected $polje_primerjava;

    /**
     * Shranimo polje in uporabni
     *
     * @param (array) $struktura
     * @return
     */

    public function save($struktura)
    {
        // Ustvarimo sistemsko vprašanje v kolikor kopiramo tudi strukturo k vlogi
        (new Hierarhija($this->anketa))->izrisisSistemskoVprsanjeVloga();

        // Shranimo strukturo
        $this->insert($struktura, 0);

    }

    /**
     * Vpišemo elemente v bazo
     *
     * @param (array) $polje - polje struktura, ki ga pridobimo iz baze
     * @param (int) $parent_id - id parenta iz prejšnje strukture, po kateri iščemo
     * @param (int) $new_parent_id - ID vpisa strukture, ki ga uporabimo za vpis parenta
     */
    private function insert(array $polje, $parent_id, $new_parent_id = null)
    {
        if (isset($parent_id) && is_array($polje) && sizeof($polje) > 0) {
            foreach ($polje as $raven) {

                if ($raven['parent_id'] == $parent_id) {

                    // Pridobimo nove podatke s primerjavo s starimi ID-ji od prej, da shranimo strukturo
                    $hierarhija_ravni_id = (int)$this->primerjava['ravni'][$raven['ravni_id']];
                    $hierarhija_sifrant_id = (int)$this->primerjava['sifranti'][$raven['sifrant_id']];
                    $level = (int)$raven['level'];

                    $sql_raven = sisplet_query("
                                  INSERT INTO srv_hierarhija_struktura 
                                    (hierarhija_ravni_id, parent_id, hierarhija_sifranti_id, level, anketa_id) 
                                  VALUES 
                                     ('" . $hierarhija_ravni_id . "', " . var_export($new_parent_id, true) . " , '" . $hierarhija_sifrant_id . "', '" . $level . "', '" . $this->anketa . "')
                                   ");
                    $this->sqlError($sql_raven);

                    // Pridobimo ID vpisa strukture
                    $id_strukture = mysqli_insert_id($GLOBALS['connect_db']);

                    // Vpišemo uporabnike v kolikor jih imammo
                    if (isset($raven['uporabniki']) && sizeof($raven['uporabniki']) > 0) {
                        foreach ($raven['uporabniki'] as $uporabnik) {
                            $sql_user = sisplet_query("INSERT INTO srv_hierarhija_struktura_users (hierarhija_struktura_id, user_id) 
                                          							VALUES ('" . $id_strukture . "', '" . $uporabnik . "')");
                            $this->sqlError($sql_user);

                            // Omogočimo tudi dostop uporabnikom do hierarhije s pravicami 10, najprej pa preverimo če je že vpisan, ker ne dovolimo duplikatov
                            $sql_uporabnik_obstaja = sisplet_query("SELECT type FROM srv_hierarhija_users WHERE anketa_id='".$this->anketa."' AND user_id='".$uporabnik."'");

                            if(mysqli_num_rows($sql_uporabnik_obstaja)) {
	                            $sql_hierarhija_user = sisplet_query("INSERT INTO srv_hierarhija_users (user_id, anketa_id, type) VALUES ('" . $uporabnik . "', '" . $this->anketa . "', 10)");
	                            $this->sqlError($sql_hierarhija_user);
                            }

                            // Omogočimo dostop omenjenim uporabnikom
                            HierarhijaQuery::dostopZaUporabnika($this->anketa, $uporabnik, 'insert');
                        }
                    }

                    // Vpišemo še vse child elemente, ki so v drevesni strukturi
                    $this->insert($polje, $raven['id'], $id_strukture);


                }
            }

        }

    }


    /**
     * Pridobimo vrednost za primerjavo;
     *
     * @param (array) $primerjava
     * @return $this;
     */
    private $primerjava;

    public function compare($primerjava)
    {
        $this->primerjava = $primerjava;
        return $this;
    }

    /**
     * Pridobimo strukturo v obliki multi arraya in potem dodamo še uporabnike
     *
     * @return (array)
     */
    protected function getStrukturaInUporabniki()
    {

        $sql_vsa_hierarhija = (new HierarhijaOnlyQuery())->queryStruktura($this->anketa);

        // Izdelamo polje iz sql poizvedbe, da bomo v nadaljevanju izdelali vgnezdeno polje
        $struktura = [];
        while ($obj = $sql_vsa_hierarhija->fetch_object()) {
            $struktura[] = [
                'id' => $obj->id,
                'ravni_id' => $obj->ravni_id,
                'parent_id' => (is_null($obj->parent_id) ? 0 : $obj->parent_id),
                'sifrant_id' => $obj->sifrant_id,
                'level' => $obj->level,
                'uporabniki' => $this->getVseUporabnikeZaStrukturo($obj->id)
            ];
        }

        return $struktura;
    }


    /**
     * Pridobimo ID vseh uporabnikov, ki so na določeni strukturi
     *
     * @param (int) $id
     * @return null | (array)
     */

    protected function getVseUporabnikeZaStrukturo($id)
    {
        $sql_uporabniki = sisplet_query("SELECT hierarhija_struktura_id, user_id FROM srv_hierarhija_struktura_users WHERE hierarhija_struktura_id='" . $id . "'");

        if ($sql_uporabniki->num_rows == 0)
            return null;

        $uporabniki = [];
        while ($obj = $sql_uporabniki->fetch_object()) {
            $uporabniki[] = $obj->user_id;
        }

        return $uporabniki;
    }

    /**
     * Iz strukture hierarhije izdelamo vgnezdena polja kot je drevesna struktura
     *
     * @param (array) $elements
     * @param (int) $parentId
     * @return array
     */
    protected function createTreeArray(array $elements, $parentId = 0)
    {
        $polje = [];

        foreach ($elements as $element) {
            // Najprej poiščemoprvi nivo
            if ($element['parent_id'] == $parentId) {
                $naslednji = $this->createTreeArray($elements, $element['id']);

                // V kolikor imamo naslednji element polja, ga zapišemo
                if ($naslednji)
                    $element['child'] = $naslednji;

                $polje[] = $element;
            }
        }

        return $polje;
    }

    public function kopirajHierarhijo($hierarhija, $uporabniki = 0)
    {
        // preverimo, če je json
        if ($this->isJson($hierarhija))
            $hierarhija = $this->isJson($hierarhija, 1);

        // v kolikor želimo kopirati tudi uporabnike/strukturo potem zbiramo stare in nove vrednosti
        if ($uporabniki == 1)
            $primerjava = [
                'ravni' => [],
                'sifranti' => [],
            ];

        // vpisemo vse ravni in šifrante
        if (!empty($hierarhija) && is_array($hierarhija) && sizeof($hierarhija) > 0) {

            foreach ($hierarhija as $ravni) {
                $sql_ravni = sisplet_query("INSERT INTO srv_hierarhija_ravni (anketa_id, user_id, level, ime) VALUES ('$this->anketa', '" . $this->user_id . "', '" . $ravni['st'] . "', '" . $ravni['ime'] . "')");
                $this->sqlError($sql_ravni);
                $id_ravni = mysqli_insert_id($GLOBALS['connect_db']);

                if ($uporabniki == 1 && isset($primerjava))
                    $primerjava['ravni'][$ravni['id']] = $id_ravni;

                // V kolikor so tudi šifranti vpišemo še šifrante
                if (!empty($ravni['sifranti']) && sizeof($ravni['sifranti']) > 0 && !empty($id_ravni)) {
                    foreach ($ravni['sifranti'] as $sifrant) {
                        if (!empty($sifrant['ime'])) {
                            $sql_hs = sisplet_query("INSERT INTO srv_hierarhija_sifranti (hierarhija_ravni_id, ime) VALUES ('" . $id_ravni . "', '" . $sifrant['ime'] . "')");
                            $this->sqlError($sql_hs);

                            if ($uporabniki == 1 && isset($primerjava['sifranti'])) {
                                $primerjava['sifranti'][$sifrant['id']] = mysqli_insert_id($GLOBALS['connect_db']);
                            }

                        }
                    }
                }
            }

        }

        if ($uporabniki == 1 && isset($primerjava) && sizeof($primerjava['ravni']) > 0 && $id_shranjene_strukture > 0) {
            $struktura = HierarhijaQuery::getHierarhijaShraniRow($id_shranjene_strukture, 'struktura');
            $struktura = unserialize($struktura);

            $this->compare($primerjava)->save($struktura);
        }

    }

    /**
     * Ustvarimo ravni in šifrante ter lahko tudi strukturo z uporabniki
     *
     * Funkcija omogoča kopiranje/ustvarjanje novih ravni in šifrantov, ki jih dobi preko večdimenzionalnega polja,
     * lahko pa tudi kopira strukturo in uporabnike, tako da primerja nove ID-je ravni/sifrantov s starimi in gre postari strukturi ter
     * zamenja stare ID-je z novimi ter zapiše tudi uporabnike
     *
     * @param (array) $hierarhija - multi array
     * @param (int) $id_shranjene_strukture - pridobimo id vrstice iz tabele srv_hierarhija_shrani
     * @param (int) $uporabniki - ali se kopirajo tudi uporabniki
     * @return boolean
     */
    public function ustvariRavniInSifranteLahkoTudiStrukturo($hierarhija, $id_shranjene_strukture = null, $uporabniki = 0)
    {
        // v kolikor želimo kopirati tudi uporabnike/strukturo potem zbiramo stare in nove vrednosti
        if ($uporabniki == 1)
            $primerjava = [
                'ravni' => [],
                'sifranti' => [],
            ];

        // Nimamo hierarhije
        if (empty($hierarhija))
            return false;

        // vpisemo vse ravni in šifrante
        if (!empty($hierarhija) && is_array($hierarhija) && sizeof($hierarhija) > 0) {

            foreach ($hierarhija as $ravni) {
                $ravni = (array)$ravni;

                $id_ravni = sisplet_query("INSERT INTO srv_hierarhija_ravni (anketa_id, user_id, level, ime) VALUES ('".$this->anketa."', '".$this->user_id."', '" . $ravni['st'] . "', '" . $ravni['ime'] . "')", "id");

                if ($uporabniki == 1 && isset($primerjava))
                    $primerjava['ravni'][$ravni['id']] = $id_ravni;

                // V kolikor so tudi šifranti vpišemo še šifrante
                if (!empty($ravni['sifranti']) && sizeof($ravni['sifranti']) > 0 && !empty($id_ravni)) {
                    foreach ($ravni['sifranti'] as $sifrant) {
                        $sifrant = (array)$sifrant;

                        if (!empty($sifrant['ime'])) {
                            $sql_hs = sisplet_query("INSERT INTO srv_hierarhija_sifranti (hierarhija_ravni_id, ime) VALUES ('" . $id_ravni . "', '" . $sifrant['ime'] . "')");
                            $this->sqlError($sql_hs);

                            if ($uporabniki == 1 && isset($primerjava['sifranti'])) {
                                $primerjava['sifranti'][$sifrant['id']] = mysqli_insert_id($GLOBALS['connect_db']);
                            }

                        }
                    }
                }
            }

        }

        if ($uporabniki == 1 && isset($primerjava) && sizeof($primerjava['ravni']) > 0 && $id_shranjene_strukture > 0) {
            $struktura = HierarhijaQuery::getHierarhijaShraniRow($id_shranjene_strukture, 'struktura');
            $struktura = unserialize($struktura);

            if (!empty($struktura) && sizeof($primerjava['ravni']) > 0) {
                $this->compare($primerjava)->save($struktura);
            }
        }

        return true;
    }

    /**
     * Kopira celotrno strukturo iz stare ankete na novo anketo
     *
     * @param int $old_id
     */
    public function kopirajCelotroStrukturoKNoviAnketi($old_id)
    {
        // pridobimo ID trenutne hierarhije še s tarim ID-jem ankete
        // $old_id - ID trenutne ankete, preden jo skopiramo
        $id_shranjene_strukture = (new HierarhijaQuery())->getDeleteHierarhijaOptions($old_id, 'srv_hierarhija_shrani_id', null, null, false);
        $shranjeni_podatki_stare_ankete = sisplet_query("SELECT * FROM srv_hierarhija_shrani WHERE id='" . $id_shranjene_strukture . "'", "obj");

        // Kopiramo podatke iz srv_hierarhija_shrani
        $ime_strukture_pri_novi_anketi = $shranjeni_podatki_stare_ankete->ime . '_' . date('H:i:s');
        $id_stranjene_nove_ankete = sisplet_query("INSERT INTO
                          srv_hierarhija_shrani
                          (anketa_id, user_id, ime, hierarhija, struktura, st_uciteljev, st_vseh_uporabnikov, komentar)
                       VALUES
                          (
                              $this->anketa,
                              $this->user_id,
                              '" . $ime_strukture_pri_novi_anketi . "',
                              '" . $shranjeni_podatki_stare_ankete->hierarhija . "',
                              '" . $shranjeni_podatki_stare_ankete->struktura . "',
                              '" . $shranjeni_podatki_stare_ankete->st_uciteljev . "',
                              '" . $shranjeni_podatki_stare_ankete->st_vseh_uporabnikov . "',
                              '" . $shranjeni_podatki_stare_ankete->komentar . "'
                          )
                       ", "id");

        // podatke shranimo še med opcije za specifično anketo
        sisplet_query("INSERT INTO srv_hierarhija_options (anketa_id, option_name, option_value) VALUES ($this->anketa, 'srv_hierarhija_shrani_id',  $id_stranjene_nove_ankete)");
        sisplet_query("INSERT INTO srv_hierarhija_options (anketa_id, option_name, option_value) VALUES ($this->anketa, 'aktivna_hierarhija_ime',  '" . $ime_strukture_pri_novi_anketi . "')");

        if ($this->isJson($shranjeni_podatki_stare_ankete->hierarhija)) {
            $hierarhija = json_decode($shranjeni_podatki_stare_ankete->hierarhija);
        } else {
            $hierarhija = unserialize($shranjeni_podatki_stare_ankete->hierarhija);
        }

        // $new_id je ID nove ankete, ki je bila skopirana
        return $this->ustvariRavniInSifranteLahkoTudiStrukturo($hierarhija, $id_shranjene_strukture, 1);

    }

    /**
     *  Preverimo, če je JSON
     *
     * @param (string) $string
     * @return return true ali error
     */
    public function isJson($string, $polje = 0)
    {
        if (is_string($string)) {
            $array = json_decode(stripslashes($string));

            if (json_last_error() == JSON_ERROR_NONE) {
                if ($polje)
                    return $array;

                return true;
            }
        }

        return false;
    }

    public function sqlError($sql)
    {
        if (!$sql) {
            echo mysqli_error($GLOBALS['connect_db']);
            die();
        }

    }

}