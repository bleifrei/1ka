<?php
/***************************************
 * Description: Ajax requesti, ki se uporabljajo za kreiranje hierarhije, šifrantov in nivojev
 * Autor: Robert Šmalc
 * Created date: 03.02.2017
 *****************************************/

namespace Hierarhija\Ajax;


use Hierarhija\HierarhijaKopiranjeClass;
use Hierarhija\Model\HierarhijaQuery;
use JsonSerializable;
use TrackingClass;

class AjaxHierarhija implements JsonSerializable
{
    private $anketa;
    private $json;
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

        return $this;
    }

    private static $_instance;

    public static function init($anketa)
    {
        if (!static::$_instance)
            return (new AjaxHierarhija($anketa));

        return static::$_instance;
    }

    /**
     * Shranjuje št. in ime nivoja
     *
     * @return html -> select -> option
     */
    public function postNivoji()
    {
        $nivo = $_POST['nivo'];
        $ime = $_POST['ime'];

        // tracking - beleženje sprememb
        $this->trackingChanges();

        sisplet_query("INSERT INTO srv_hierarhija_ravni (anketa_id, user_id, level, ime) VALUES ('" . $this->anketa . "', '" . $this->user_id . "', '" . $nivo . "', '" . $ime . "')");
        $nivo_id = mysqli_insert_id($GLOBALS['connect_db']);

        return $nivo_id;
    }


    /**
     * Shranjuje id nivoja, in šifrant za sledeči nivo
     *
     * @return JSON
     */
    public function postSifranti()
    {
        $idNivoja = $_POST['idNivoja'];
        $imeSifranta = $_POST['imeSifranta'];

        // tracking - beleženje sprememb
        $this->trackingChanges();

        // V podatke shranimo samo kadar pošljemo vrednost polja
        if (!empty($imeSifranta)) {
            $sql_hs = sisplet_query("INSERT INTO srv_hierarhija_sifranti (hierarhija_ravni_id, ime) VALUES ('" . $idNivoja . "', '" . $imeSifranta . "')");
            $this->sqlError($sql_hs);
        }

        //izbrišemo šifrante
        $sql_vsi_sifranti = sisplet_query("SELECT ime FROM srv_hierarhija_sifranti WHERE hierarhija_ravni_id='" . $idNivoja . "' ORDER BY ime");
        $sifranti = array();
        while ($row = mysqli_fetch_row($sql_vsi_sifranti)) {
            $sifranti[] = $row[0];
        }

        // vrnemo json seznam vseh šifrantov za sledeči nivo
        return json_encode($sifranti);
    }

    /**
     * Select box spremeni v ul in ponudi možnost brisanje šifrantov
     *
     * @return JSON
     */
    public function brisiSifrante()
    {
        $idNivoja = $_POST['idNivoja'];

        //izbrišemo šifrante
        $sql_vsi_sifranti = sisplet_query("SELECT * FROM srv_hierarhija_sifranti WHERE hierarhija_ravni_id='" . $idNivoja . "' ORDER BY ime");
        $sifranti = array();
        while ($row = mysqli_fetch_assoc($sql_vsi_sifranti)) {
            $sifranti[] = [
                'id' => $row['id'],
                'ime' => $row['ime']
            ];
        }

        // vrnemo json seznam vseh šifrantov za sledeči nivo
        return json_encode($sifranti);
    }

    /**
     * Delete šifrant
     */
    public function izbrisiSifrant()
    {
        $idSifranta = $_POST['idSifranta'];

        // tracking - beleženje sprememb
        $this->trackingChanges();

        // Najprej preveri, če je v strukturi že uporabljen omenjen šifrant v kolikor je potem ga ne sme dovoliti izbrisati
        $sql_result = sisplet_query("SELECT ID FROM srv_hierarhija_struktura WHERE hierarhija_sifranti_id='" . $idSifranta . "'");

        // V kolkor je kak vpis potem šifrant obstaja, drugače pa ga lahko izbrišemo
        if (mysqli_num_rows($sql_result) > 0) {
            echo 1;
        } else {
            //izbriši šifrant
            sisplet_query("DELETE FROM srv_hierarhija_sifranti WHERE id='" . $idSifranta . "'");
        }
    }

    /**
     * Pridobi število že vpisanih nivojev
     */
    public function stNnivojev()
    {
        $sql = sisplet_query("SELECT COUNT(id) FROM `srv_hierarhija_ravni` WHERE anketa_id='" . $this->anketa . "'");
        echo $sql->fetch_row()[0];
    }

    public function obnoviHierarhijo()
    {
        $hierarhija = (isset($_POST['hierarhija']) ? $_POST['hierarhija'] : null);
        $uporabniki = (isset($_POST['uporabniki']) ? $_POST['uporabniki'] : null);
        $id_shranjene_strukture = (isset($_POST['id']) ? $_POST['id'] : null);

        // tracking - beleženje sprememb
        $this->trackingChanges();

        // Pobrišemo dosedanjo hierarhijo
        $this->izbrisiVseRavni();

        // preverimo, če je json
        if ($this->isJson($hierarhija))
            $hierarhija = $this->isJson($hierarhija, 1);

        $kopiranje = HierarhijaKopiranjeClass::getInstance($this->anketa)->ustvariRavniInSifranteLahkoTudiStrukturo($hierarhija, $id_shranjene_strukture, $uporabniki);

        if ($kopiranje) {
            // Vkolikor je bilo kopiranje uspešno shranimo še podatke srv_hierarhija_shrani

            // Pridobimo vse ravni in šifrante, ki smo jih vpisali skupaj z ID-ji;
            return json_encode($this->pridobiVseRavniSifrante());
        }

        return 'Napaka pri kopiranju hierarhije';
    }

    // izbriše shranjeno hierarhijo
    public function izbrisiHierarhijo()
    {
        $hierarhija_id = $_POST['id'];

        if (empty($hierarhija_id)) {
            echo 'Ni mogoče izbrisati error';
            return false;
        }

        // tracking - beleženje sprememb
        $this->trackingChanges();

        // Preverimo, če je omenjena hierarhija že kje uporabljena
        $uporabljena_hierarhija = sisplet_query("SELECT id FROM srv_hierarhija_options WHERE option_name='srv_hierarhija_shrani_id' AND option_value='" . $hierarhija_id . "'", "obj");

        if (empty($uporabljena_hierarhija) || sizeof($uporabljena_hierarhija) == 0) {
            $sql = sisplet_query("DELETE FROM srv_hierarhija_shrani WHERE id='" . $hierarhija_id . "'");
            $this->sqlError($sql);

            echo 'success';
        }else{
            echo 'nothing';
        }

    }

    // Uvozi hierarhijo
    public function uvoziHierarhijo()
    {
        $izrisi = '<div>';
        $izrisi .= '<h2>' . $this->lang['srv_hierarchy_import_code'] . '</h2>';
        $izrisi .= '<div>';
        $izrisi .= '<a href="/uploadi/dokumenti/primer_csv_sifrantov_in_nivojev.csv" download><h5>' . $this->lang['srv_hierarchy_import_example'] . '</h5></a>';
        $izrisi .= '
               <form action="index.php?anketa=' . $this->anketa . '&a=hierarhija_superadmin&m=uvoz-sifrantov&t=hierarhija-uvoz" method="POST" enctype="multipart/form-data">
                   <div style="display:block !important;width:52%;float:left; clear:both;">
                        <input type="file" name="uvozi-hierarhijo" class="upload-file" required="required"/>
                   </div>
                   <div style="display:block;float:left; padding-left: 20px;">
                        <button type="submit" class="btn btn-moder">Uvozi hierarhijo</button>
                   </div>
               </form>
                ';

        $izrisi .= '</div>';
        $izrisi .= '</div>';

        // Gumb za zapret popup
        $izrisi .= '<div class="buttonwrapper spaceRight floatLeft" style="clear: both;padding-top: 20px;">';
        $izrisi .= '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_cancel(); return false;"><span>' . $this->lang['srv_close_profile'] . '</span></a>' . "\n\r";
        $izrisi .= '</div>';

        return $izrisi;
    }

    /**
     * Pridobimo vse shranjene hierarhije
     *
     * @return $array
     */
    public function seznamVsehShranjenihHierarhij()
    {
        $sql = sisplet_query("SELECT * FROM srv_hierarhija_shrani WHERE user_id='" . $this->user_id . "'");
        $this->sqlError($sql);

        $data = array();
        if ($sql->num_rows > 0) {
            while ($row = $sql->fetch_object()) {
                $data[] = [
                    'id' => $row->id,
                    'ime' => $row->ime,
                    'anketa' => $row->anketa_id,
                    'hierarhija' => $row->hierarhija,
                    'struktura' => (!is_null($row->struktura) ? 1 : 0),
                    'stEvalvirancev' => (!is_null($row->st_uciteljev) ? $row->st_uciteljev : 0),
                    'stUporabnikov' => (!is_null($row->st_vseh_uporabnikov) ? $row->st_vseh_uporabnikov : 0)
                ];
            }
        }

        return $data;
    }

    /**
     * Aktivno/trenutno hierarhijo shrani v  srv_hierarhija_shrani v stringu
     */
    public function updateAktivnoHierarhijo()
    {
        $id = $_POST['id'];
        $hierarhija = $_POST['hierarhija'];

        // tracking - beleženje sprememb
        $this->trackingChanges();

        $sql_insert = sisplet_query("UPDATE srv_hierarhija_shrani SET hierarhija='" . $hierarhija . "' WHERE id='" . $id . "' AND anketa_id='" . $this->anketa . "'");
        $this->sqlError($sql_insert);
    }

    private function trackingChanges()
    {
        TrackingClass::update($this->anketa, '21');
    }

    /**
     * Pobrišemo vse ravni in nastavitve v podatkovni bazi
     */
    public function izbrisiVseRavni()
    {
        //Pobrišemo vse nastavitve za omenjeno anketo
        sisplet_query("DELETE FROM srv_hierarhija_options WHERE anketa_id='" . $this->anketa . "'");

        sisplet_query("DELETE FROM srv_hierarhija_ravni WHERE anketa_id='" . $this->anketa . "'");
    }

    /**
     * Pridobimo seznam vseh ravni skupaj s šifrani in vrnemo v obliki polja
     *
     * @param $anketa_id ;
     * @return array();
     */
    public function pridobiVseRavniSifrante()
    {

        $sql = sisplet_query("SELECT id, level, ime, unikaten FROM `srv_hierarhija_ravni` WHERE anketa_id='" . $this->anketa . "'");

        $polje = array();
        if ($sql->num_rows > 0) {
            while ($row = $sql->fetch_object()) {

                //Poiščemo še šifrante za omenjen nivo
                $sql_sifranti = sisplet_query("SELECT id, ime FROM `srv_hierarhija_sifranti` WHERE hierarhija_ravni_id='" . $row->id . "' ORDER BY ime");
                $sifranti = array();
                if ($sql_sifranti->num_rows > 0) {
                    while ($sifrant = $sql_sifranti->fetch_object()) {
                        $sifranti[] = [
                            'id' => $sifrant->id,
                            'ime' => $sifrant->ime
                        ];
                    }
                }


                $polje[] = [
                    'st' => $row->level,
                    'ime' => $row->ime,
                    'id' => $row->id,
                    'unikaten' => $row->unikaten,
                    'sifranti' => $sifranti
                ];
            }
        }

        return $polje;
    }

    /**
     * Preimenuje ime hierarhije iz seznama hierarhij
     */
    public function preimenujHierarhijo()
    {
        $hierarhija_id = $_POST['id'];
        $ime = strip_tags($_POST['ime']);

        // tracking - beleženje sprememb
        $this->trackingChanges();

        if (!empty($ime)) {
            $sql = sisplet_query("UPDATE srv_hierarhija_shrani SET ime='" . $ime . "' WHERE id='" . $hierarhija_id . "'  AND anketa_id='" . $this->anketa . "'");
            $this->sqlError($sql);
        }
    }

    /**
     * Popravi ime nivoja ali, da je unikaten in se ga lahko uporabi samo 1x , ko je enkrat že vnešeno v bazo
     */
    public function postPopraviNivoSsifranti()
    {
        $id_nivoja = $_POST['id_nivoja'];
        $vsebina = $_POST['besedilo'];
        $unikaten = $_POST['unikaten'];

        // tracking - beleženje sprememb
        $this->trackingChanges();

        if (!empty($vsebina)) {
            sisplet_query("UPDATE srv_hierarhija_ravni SET ime='$vsebina' WHERE id='$id_nivoja' AND anketa_id='" . $this->anketa . "'");
        }

        if (!is_null($unikaten)) {
            sisplet_query("UPDATE srv_hierarhija_ravni SET unikaten='$unikaten' WHERE id='$id_nivoja'  AND anketa_id='" . $this->anketa . "'");
        }
    }

    /**
     * Briši cel nivo skupaj s šifranti
     *
     * @return integer
     */
    public function brisiCelNivoSkupajSsifranti()
    {
        $id_nivoja = $_POST['id_nivoja'];

        // tracking - beleženje sprememb
        $this->trackingChanges();

        # Najprej preverimo, če je za ta nivo že zgrajena hierarhija
        $hierarhija = sisplet_query("SELECT * FROM srv_hierarhija_struktura WHERE hierarhija_ravni_id='$id_nivoja'");
        $this->sqlError($hierarhija);
        if ($hierarhija->num_rows == 0) {
            // sql level
            $sql_level = sisplet_query("SELECT level FROM srv_hierarhija_ravni WHERE id='" . $id_nivoja . "'");
            $this->sqlError($sql_level);

            $nivo_brisi = sisplet_query("DELETE FROM srv_hierarhija_ravni WHERE id='" . $id_nivoja . "'");
            $this->sqlError($nivo_brisi);

            //Vse ostale preštevilčimo
            $sql_prestevilci = sisplet_query("SELECT id, level FROM `srv_hierarhija_ravni` WHERE  anketa_id='" . $this->anketa . "' AND level>'" . $sql_level->fetch_row()[0] . "'");
            if ($sql_prestevilci->num_rows > 0) {
                while ($row = $sql_prestevilci->fetch_object()) {
                    sisplet_query("UPDATE `srv_hierarhija_ravni` SET level='" . ($row->level - 1) . "' WHERE  anketa_id='" . $this->anketa . "' AND id='" . $row->id . "'");
                }
            }


            $sql = sisplet_query("SELECT COUNT(id) FROM `srv_hierarhija_ravni` WHERE anketa_id='" . $this->anketa . "'");
            echo $sql->fetch_row()[0];
        } else {
            echo 0;
        }
    }

    /**
     * Možnost dodajanja komentarja k hierarhiji
     *
     * @return html
     */
    public function htmlPopUpKomentarKhierarhiji()
    {
        $shrani_id = (new HierarhijaQuery)->getRowOptions($this->anketa, 'srv_hierarhija_shrani_id');
        if (!is_null($shrani_id))
            $shrani_id = $shrani_id->option_value;

        // Če komenatar že obstaja
        $sql_shrani = sisplet_query("SELECT komentar FROM srv_hierarhija_shrani WHERE id='" . $shrani_id . "'");
        $komentar = $sql_shrani->fetch_object()->komentar;

        echo '<div>';
        echo '<h2> Komentar k hierarhiji </h2>';
        echo '<div>';

        echo '<div style="padding:15px 0;">';
        echo '<textarea data-id="' . $shrani_id . '" id="hierarhija-komentar" name="komentar" style="height:100px; width:100%;">' . $komentar . '</textarea>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        // Gumb za zapret popup in potrdit
        echo '<div class="buttonwrapper spaceRight floatLeft">';
        echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="shraniKomentar()"; return false;"><span>' . $this->lang['srv_potrdi'] . '</span></a>' . "\n\r";
        echo '</div>';

        echo '<div class="buttonwrapper spaceRight floatLeft">';
        echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_cancel(); return false;"><span>' . $this->lang['srv_close_profile'] . '</span></a>' . "\n\r";
        echo '</div>';
    }

    /**
     * Post komentar k hierarhiji
     *
     * @return integer
     */
    public function postKomentarKhierarhiji()
    {
        // tracking - beleženje sprememb
        $this->trackingChanges();

        $komentar = (!empty($_POST['komentar']) ? $_POST['komentar'] : null);
        $shrani_id = (!empty($_POST['id']) ? $_POST['id'] : null);

        if (is_null($shrani_id))
            return 'Ni ID-ja.';

        sisplet_query("UPDATE srv_hierarhija_shrani SET komentar='" . $komentar . "' WHERE id='" . $shrani_id . "'  AND anketa_id='" . $this->anketa . "'");

        echo 1;
    }

    /**
     * Prikaži popup za nalaganje logotipa
     *
     * @return render HTML
     */
    public function htmlPopUpUploadLogo()
    {
        global $site_url;

        $shrani_id = (new HierarhijaQuery)->getRowOptions($this->anketa, 'srv_hierarhija_shrani_id');

        if (!is_null($shrani_id))
            $shrani_id = $shrani_id->option_value;

        // Če komenatar že obstaja
        $logo = sisplet_query("SELECT logo FROM srv_hierarhija_shrani WHERE id='" . $shrani_id . "'", "obj")->logo;

        echo '<div style="clear: both;" id="hierarhija-container">';
        echo '<h2> Logotip </h2>';
        echo '<div>';
        if (!empty($logo))
            echo '<div id="hierarhija-logo" style="padding-bottom: 15px; display: block; float: left;">
                            <img src="' . $site_url . 'admin/survey/modules/mod_hierarhija/porocila/logo/' . $logo . '" style="max-height: 100px; max-width: 500px;display: block;float: left;">
                            <span style="display: block;float: left; color:#ffa608;cursor: pointer;padding: 10px;" onclick="izbrisiLogo(\'' . $shrani_id . '\')"><i class="fa fa-trash" aria-hidden="true"></i> Izbriši</span>
                            </div>';

        echo '<div style="padding:15px 0;">';
        echo '
                       <form action="index.php?anketa=' . $this->anketa . '&a=hierarhija_superadmin&m=upload-logo" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="' . $shrani_id . '">
                           <div style="display:block !important;width:52%;float:left; clear:both;">
                                <input type="file" name="logo" class="upload-file" required="required"/>
                           </div>
                           
                           <div style="clear: both; padding-top: 20px;">
                               <div  class="buttonwrapper spaceRight floatLeft">
                                    <button type="submit" class="btn btn-moder">Naloži</button>
                               </div>
                               
                               <div  class="buttonwrapper spaceRight floatLeft">
                                    <button class="btn btn-moder" href="#" onclick="vrednost_cancel(); return false;"><span>' . $this->lang['srv_close_profile'] . '</span></button>
                               </div>
                           </div>
                           
                       </form>
                        ';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }


    /**
     *  Preverimo, če je JSON
     *
     * @param (string) $string
     * @return return true ali error
     */
    private function isJson($string, $polje = 0)
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

    private function sqlError($sql)
    {
        if (!$sql) {
            echo mysqli_error($GLOBALS['connect_db']);
            die();
        }

    }

    /**
     * Naredi JSON format
     *
     * @return json response
     */
    public function jsonSerialize()
    {
        return $this->json;
    }

}