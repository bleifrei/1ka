<?php

/**
 * Ime: Samoocena hierarhija vsi AJAX requesti
 * Avtor: Robert Šmalc
 */

namespace Hierarhija;

use Branching;
use Dostop;
use Common;
use Hierarhija\Ajax\AjaxHierarhija;
use Hierarhija\Ajax\AjaxHierarhijaClass;
use Hierarhija\Ajax\AjaxUporabniki;
use Hierarhija\Ajax\AjaxSuperSifra;
use Hierarhija\Model\HierarhijaOnlyQuery;
use Hierarhija\Model\HierarhijaQuery;
use MailAdapter;
use SurveyInfo;
use TrackingClass;


class HierarhijaAjax
{
    protected $anketa;
    protected $lang;
    protected $user_id;
    protected $hierarhija_type;
    protected $site_url;

    public function __construct($anketa)
    {
        $this->anketa = $anketa;

        if (!(new Dostop())->checkDostop($this->anketa))
            return false;

        if (!$this->isAjax())
            return redirect('/admin/survey/');

        global $lang;
        global $global_user_id;
        global $site_url;
        $this->lang = $lang;
        $this->user_id = $global_user_id;
        $this->hierarhija_type = HierarhijaHelper::preveriTipHierarhije($this->anketa);
        $this->site_url = $site_url;

    }

    /**
     * Preverimo, če je ajax request
     *
     * @return boolean
     */
    private function isAjax()
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
            return true;

        return false;
    }

    //AJAX POST requesti
    public function ajax()
    {
        // Preverimo ustreznost ankete
        if ($_GET['a'] == 'preveri-ustreznost-ankete')
            return $this->preveriUstreznostCeJePrimernaZaHierarhijo();

        if ($_GET['a'] == 'get-all-hierarchy-library')
            return $this->pridobiVseAnketeKiSoVknjizniciZaHierarhijo();


        /********* HIERARHIJA UREJANJE **************/
        /**
         * Shranjuje št. in ime nivoja
         *
         * @return html -> select -> option
         */
        if ($_GET['a'] === 'post_nivoji')
            echo AjaxHierarhija::init($this->anketa)->postNivoji();

        /**
         * Shranjuje id nivoja, in šifrant za sledeči nivo
         *
         * @return JSON
         */
        if ($_GET['a'] === 'post_sifranti')
            echo AjaxHierarhija::init($this->anketa)->postSifranti();

        /**
         * Select box spremeni v ul in ponudi možnost brisanje šifrantov
         *
         * @return JSON
         */
        if ($_GET['a'] === 'brisi_sifrante')
            echo AjaxHierarhija::init($this->anketa)->brisiSifrante();

        /**
         * Delete šifrant
         */
        if ($_GET['a'] === 'izbrisi_sifrant')
            return AjaxHierarhija::init($this->anketa)->izbrisiSifrant();

        /**
         * Pridobi število že vpisanih nivojev
         */
        if ($_GET['a'] === 'st_nivojev') {
            $sql = sisplet_query("SELECT COUNT(id) FROM `srv_hierarhija_ravni` WHERE anketa_id='" . $this->anketa . "'");
            echo $sql->fetch_row()[0];
        }

        /**
         * Popravimo ime nivoja v bazi
         */
        if ($_GET['a'] == 'popravi_nivo_hierarhija')
            return AjaxHierarhija::init($this->anketa)->postPopraviNivoSsifranti();

        /**
         * Briši nivo v hierarhiji preko AJAX ukaza
         */
        if ($_GET['a'] == 'brisi_nivo_hierarhija')
            return AjaxHierarhija::init($this->anketa)->brisiCelNivoSkupajSsifranti();

        if ($_GET['a'] === 'json_nivoji_podatki') {
            echo json_encode(AjaxHierarhija::init($this->anketa)->pridobiVseRavniSifrante());
        }

        // Komentar k izbrani hierarhiji
        if ($_GET['a'] == 'komentar-k-hierarhiji') {
            if ($_GET['m'] == 'get')
                return AjaxHierarhija::init($this->anketa)->htmlPopUpKomentarKhierarhiji();

            if ($_GET['m'] == 'post')
                return AjaxHierarhija::init($this->anketa)->postKomentarKhierarhiji();
        }

        // Naloži logo kj hierarhiji
        if ($_GET['a'] == 'upload-logo') {
            if ($_GET['m'] == 'get')
                return AjaxHierarhija::init($this->anketa)->htmlPopUpUploadLogo();

            if ($_GET['m'] == 'delete')
                return Hierarhija::brisiLogo($this->anketa);
        }
        /********* END HIERARHIJA UREJANJE **************/


        /***************************** HIERARHIJA UPORABNIKI ***********************/
        //Brišemo iz DataTables ali JsTree
        if ($_GET['a'] == 'brisi_element_v_hierarhiji')
            echo AjaxUporabniki::init($this->anketa)->brisiZadnjiElementVstrukturiHierarhije();

        // Pridobimo podatke o sifrantih iz baze
        if ($_GET['a'] == 'posodobi_sifrante')
            echo json_encode(AjaxUporabniki::init($this->anketa)->getPosodobiSifranteZaJsTree());

        /**
         * Shranimo hierarhijo
         */
        if ($_GET['a'] == 'shrani_hierarhijo')
            return AjaxUporabniki::init($this->anketa)->shraniHierarhijo();


        /**
         * Shrani strukturo hierarhije v tabelo srv_hierarhija_shrani
         *
         * @param (int) shrani
         * @param (int) id
         */
        if ($_GET['a'] == 'shrani-strukturo-hierarhije') {
            if ($_POST['id'] != $this->anketa && empty($_POST['shrani']))
                return 'Ni shranjeno';

            // tracking - beleženje sprememb
            $this->trackingChanges();

            $this->shraniStrukturoHierarhijeVString();
        }

        /**
         * Vrnemo JSON podatke hierarhije za jsTree
         */
        if ($_GET['a'] == 'json_jstree') {
            $struktura = (new HierarhijaQuery())->json_jstree($this->anketa);

            echo json_encode($struktura);
        }

        /**
         * Podatki, ko nalagamo prvi element hierarhije
         */
        if ($_GET['a'] == 'json_jstree_first_level') {
            $hierarhija = (new HierarhijaOnlyQuery())->queryStruktura($this->anketa, null, ' AND parent_id IS NULL')->fetch_object();

            echo '<h2>Hierarhija</h2>';
            echo '<b>' . $hierarhija->ravni_ime;
            if ($this->hierarhija_type > 4)
                echo ' - ' . $hierarhija->sifrant_ime;
            echo ': </b>';
        }

        /**
         * Update hierarhije, ki je trenutno aktivna
         */
        if ($_GET['a'] == 'update-aktivno-hierarhijo')
            return AjaxHierarhija::init($this->anketa)->updateAktivnoHierarhijo();


        if ($_GET['a'] == 'json_shranjene_hierarhije')
            echo json_encode(AjaxHierarhija::init($this->anketa)->seznamVsehShranjenihHierarhij());

        // vse nastavitve povezane s hierarhijo
        if ($_GET['a'] == 'hierarhija-options') {

            if ($_GET['m'] == 'get') {
                if (!empty($_POST['option_name'])) {
                    echo (new HierarhijaQuery())->getDeleteHierarhijaOptions($this->anketa, $_POST['option_name'], null, null, false);
                    return '';
                }

                return json_encode((new HierarhijaQuery())->getDeleteHierarhijaOptions($this->anketa));
            }

            if ($_GET['m'] == 'save') {
                $option = $_POST['option_name'];
                $value = (!empty($_POST['option_value']) ? $_POST['option_value'] : 'false');

                // tracking - beleženje sprememb
                $this->trackingChanges();

                if (!empty($option))
                    (new HierarhijaQuery())->saveHierarhijaOptions($this->anketa, $option, $value);
            }
        }

        // pridobimo hierarhijo iz JSON in pobrišemo staro ali pa samo dopišemo nove nivoje in šifrante
        if ($_GET['a'] == 'obnovi-hierarhijo')
            echo AjaxHierarhija::init($this->anketa)->obnoviHierarhijo();


        // izbriše shranjeno hierarhijo
        if ($_GET['a'] == 'izbrisi-hierarhijo')
            return AjaxHierarhija::init($this->anketa)->izbrisiHierarhijo();


        // izbriši trenutno shranjene ravni
        if ($_GET['a'] == 'izbrisi_vse_ravni') {
            // tracking - beleženje sprememb
            $this->trackingChanges();
            return AjaxHierarhija::init($this->anketa)->izbrisiVseRavni();
        }

        // preimenuj hierarhijo
        if ($_GET['a'] == 'preimenuj-hierarhijo') {
            $this->trackingChanges();
            return AjaxHierarhija::init($this->anketa)->preimenujHierarhijo();
        }

        // Uvoz hierarhije
        if ($_GET['a'] == 'uvozi-hierarhijo')
            echo AjaxHierarhija::init($this->anketa)->uvoziHierarhijo();


        // pridobimo celotno strukturo šifrantov za prikaz v tabeli
        if ($_GET['a'] == 'get-datatables-data') {
            $polje = [
                'data' => AjaxUporabniki::init($this->anketa)->getDataTablesPodatkeZaPrikazTabele()
            ];

            echo json_encode($polje);
        }

        // Ukazi za brisanje
        if ($_GET['a'] == 'brisi') {
            // Briši uporabnika iz baze srv_hierarhija_struktura_users in srv_hierarhija_users (tukaj ima pravico do ankete)
            if ($_GET['m'] == 'uporabnika')
                return AjaxUporabniki::init($this->anketa)->brisiUporabnikaIzStrukture();

        }

        // pridobimo podatke iz srv_hierarhija_shrani
        if ($_GET['a'] == 'pridobi-shranjeno-hierarhijo-bool') {
            $id = (!empty($_POST['id']) ? $_POST['id'] : null);
            $polje = (!empty($_POST['polje']) ? $_POST['polje'] : null);

            if (is_null($id))
                return '';

            $polje = HierarhijaQuery::getHierarhijaShraniRow($id, $polje);


            echo(!is_null($polje) ? 1 : 0);
        }


        /**
         * Gradnja hierarhije
         */
        if ($_GET['a'] == 'gradnja-hierarhije') {
            /**
             * Uvoz uporabnikov preko tekstovnega polaj
             */
            if ($_GET['m'] == 'import-user') {
                // PPrikaži popup za uvoz uporabnikov
                if ($_GET['s'] == 'get')
                    return AjaxUporabniki::init($this->anketa)->getUsersImportPopup();

                if ($_GET['s'] == 'getAll')
                    return AjaxUporabniki::init($this->anketa)->getAllUserInJson();

                // Pošlji podatke za shranjevanje
                if ($_GET['s'] == 'post')
                    return AjaxUporabniki::init($this->anketa)->postImportUsers();
            }


            /**
             * Preveri pravice uporabnika in v kolikor ni admin tudi njegove nivoje, ki so nad njim
             *
             * Preveri, če gre za admina in v tem primeru vrne samo uporabnik=1, drugače vrne objekt s podatki o levele, parent_id, struktura_id
             * @return json
             */
            if ($_GET['m'] == 'get-user-level')
                echo json_encode(AjaxUporabniki::init($this->anketa)->getUserLevelPermission());


            /**
             * Pridobimo nivoje in šifrane za sledečo raven, vrenomo polje ['nivoji', 'sifranti']
             *
             * @return json
             */
            if ($_GET['m'] == 'get-sifranti')
                echo json_encode(AjaxUporabniki::init($this->anketa)->getSifrantiZaHierarhijaUserType());


            /**
             * Preveri, če je omenjen šifrant že napisan na nivo in če je pridobi podatke o uporabnikih
             * @return json object
             */
            if ($_GET['m'] == 'preveri-sifrant-za-nivo')
                echo json_encode(AjaxUporabniki::init($this->anketa)->preveriVpisSifrantaZaSledeciNivo());


            /**
             * Shranimo vse nove šifrante in tudi uporabnike, ki so dodani k novim šifrantom
             */
            if ($_GET['m'] == 'post-struktura')
                AjaxUporabniki::init($this->anketa)->shraniStrukturoSifrantovInUporabnikov();

            /**
             * Pridobi uporabnike, ki so dodani na specifični nivo
             * @params $id - struktura id
             * @return json
             */
            if ($_GET['m'] == 'get-uporabniki')
                echo json_encode(AjaxUporabniki::init($this->anketa)->pridobiUporabnikeZaDolocenNivoId());

            /**
             * Pošljemo id uporabnikov in id strukture, da ponovno shranimo druge uporabnike v bazo
             */
            if ($_GET['m'] == 'post-uporabniki')
                return AjaxUporabniki::init($this->anketa)->postUporabnikeZaDolocenNivoId();

            /**
             * Popup z vsebino, kjer se urejajo uporabniki za posamezni nivo / textarea za dodajanje novega uporabnika
             *
             * @return html
             */
            if ($_GET['m'] == 'uredi-uporabnike-v-strukturi')
                return AjaxUporabniki::init($this->anketa)->htmlPopUpDodajUporabnikeZaDolocenNivoId();


            /**
             * Pošljemo dodano dodane uporab
             *
             * @param
             */
            if ($_GET['m'] == 'post-dodatne-uporabnike-k-strukturi')
                return AjaxUporabniki::init($this->anketa)->postDodatneUporabnikeNaNivoId();

            /**
             * Kopiranje vrstice hierarhije iz DataTables ponovno v možnost urejanja
             *
             * @param (int) id - id zadnjega elementa strukture
             * @return json
             */
            if ($_GET['m'] == 'kopiranje-vrstice')
                echo json_encode(AjaxUporabniki::init($this->anketa)->kopirajVrsticoStruktureIzDataTablesVFormo());

            /**
             * Poišči uporabnika glede na vpisan email in ga zamnjaj z novim emailom.
             */
            if ($_GET['m'] == 'zamenjaj-uporabnika-v-strukturi')
                return AjaxUporabniki::init($this->anketa)->htmlPopUpZamenjajUporabnikaVstrukturi();

            /**
             * Pridobimo število oseb, ki jih je potrebno zamenjati
             */
            if ($_GET['m'] == 'post-st-zamenjav')
                return AjaxUporabniki::init($this->anketa)->getTestnoPreveriStZamenjavEmailVstrukturi();

            /**
             * Uporabnika v strukturi zamenjaj z novim
             * Zamenjamo samo na zadnjem nivoju, prejšnjega pa izbrišemo iz sistema
             */
            if ($_GET['m'] == 'post-zamenjaj-uporabnika-z-novim')
                echo AjaxUporabniki::init($this->anketa)->postZamenjajEmailVstrukturi();
            /***************************** HIERARHIJA UPORABNIKI ***********************/

        }

        /**
         * Aktivacija ankete in generiranje sistemskih vprašanj za Hierarhijo
         */
        if ($_GET['a'] == 'aktivacija_ankete') {
            if (SurveyInfo::getSurveyModules('hierarhija') == 1) {
                // tracking - beleženje sprememb
                $this->trackingChanges();

                // Sistemsko vprašanje prestavimo na prvo mesto VLOGA v kolikor ni
                $this->postaviVlogoNaPrvoMestoInIzbrisiCeJeKakNivo();

                //pridobimo vse nivoje za omenjeno anketo, ker potrebujemo število nivojov in imena nivojev
                $nivoji = (new HierarhijaOnlyQuery())->getRavni($this->anketa);

                //preštevilčimo vsa vprašnja na prvi strani za število nivojev pustimo samo vlogo na prvem mestu
                (new HierarhijaAjax($this->anketa))->prestevilciBranching($nivoji->num_rows);

                //Pridobimo  gru_id od vloge, ker je na isti strani
                $grup_id = HierarhijaOnlyQuery::getGrupaId($this->anketa, 1);

                $vrstni_red = 2;
                while ($nivo = $nivoji->fetch_object()) {
                    //vnesemo v srv_spremenljivke in srv_branching
                    $naslov = $nivo->level . ". " . $nivo->ime;
                    $variabla = 'nivo' . $nivo->level;

                    $spr = [$grup_id, $naslov, $variabla, '3', $vrstni_red];
                    $sql_sifranti = (new HierarhijaOnlyQuery())->getSamoSifrant($nivo->id);

                    (new HierarhijaQuery())->insertSpremenljivkaBranching($spr, $sql_sifranti, $this->anketa);

                    $vrstni_red++;
                }

                //Popravimo vrednost pri anleketi, da je sedaj hierarhija enako 2 = je že bila aktivirana
                $anketa_id = SurveyInfo::getInstance()->getSurveyColumn('id');
                sisplet_query("UPDATE srv_anketa_module SET vrednost='2' WHERE ank_id='" . $anketa_id . "'");

                $b = new Branching($anketa_id);
                $b->repare_branching();

                // Shranimo podatke kdaj in kdo je aktiviral hierarhijo
                (new HierarhijaQuery())->saveHierarhijaOptions($this->anketa, 'uporabnik_aktiviral_hierarhijo', $this->user_id);
                (new HierarhijaQuery())->saveHierarhijaOptions($this->anketa, 'cas_aktivacije_hierarhije', date('d.m.Y, G:i'));

                // KO se izdelajo polja za anketo se potem tudi posreduje email za učitelje oz. zadnji nivo
                Hierarhija::aktivacijaAnketePosljiEmail($this->anketa);
            }
        }

        /**
         * Statusi in generiranje superšifre
         */
        if($_GET['a'] == 'super-sifra'){

            //Shrani superšifro
            if ($_GET['m'] == 'shrani')
                return AjaxSuperSifra::init($this->anketa)->shrani();

            //Shrani superšifro
            if ($_GET['m'] == 'getAll')
                return AjaxSuperSifra::init($this->anketa)->getAll();

        }

        /* Ostalo */
        if ($_GET['a'] == 'ostalo') {
            //Obvesti managerje
            if ($_GET['m'] == 'obvesti-managerje') {
                // tracking - beleženje sprememb
                $this->trackingChanges();

                $this->obvestiManagerjeSendEmail();
            }

            if ($_GET['m'] == 'preview-mail') {
                // tracking - beleženje sprememb
                $this->trackingChanges();

                return $this->predogledEmailaZaUciteljeAliManagerje();
            }

            if ($_GET['m'] == 'opcije') {
                // tracking - beleženje sprememb
                $this->trackingChanges();

                return $this->posodobiAliVnesiVtabeloOpcije();
            }

            if ($_GET['m'] == 'poslji-email-samo-uciteljem') {
                // tracking - beleženje sprememb
                $this->trackingChanges();

                return $this->posljiElektronskoSamoUciteljem();
            }
        }
    }

    /**
     * Prestavimo vlogo na prvo stran in rvo mesto v kolikor, bi slučajno bila zmaknjena, kje drugje v anketi
     */
    private function postaviVlogoNaPrvoMestoInIzbrisiCeJeKakNivo()
    {
        //Preverimo, če je sistemsko vprašanje vloga že ustvarjeno
        $grup_ids = sisplet_query("SELECT id, vrstni_red FROM srv_grupa WHERE ank_id='" . $this->anketa . "' ORDER BY vrstni_red", "obj");

        $prva_stran_group_id = null;

        // gremo po vseh straneh, da preverimo, če je kje vloga
        foreach ($grup_ids as $grup_id) {
            $sql_vpisane_spr = sisplet_query("SELECT id, gru_id, variable, vrstni_red FROM srv_spremenljivka WHERE gru_id='" . $grup_id->id . "' AND variable='vloga'", "obj");

            // Pridobimo Group ID za prvo stran
            if ($grup_id->vrstni_red == 1)
                $prva_stran_group_id = $grup_id->id;

            // Vloga je na prvi strani vendar ne na prvem mestu, zato jo postavimo na prvo mesto ali, če je na katerikoli drugi strani jo tudi postzavimo na prvo mesto
            if (!empty($sql_vpisane_spr) && ($sql_vpisane_spr->vrstni_red > 1 || $sql_vpisane_spr->gru_id != $prva_stran_group_id)) {
                sisplet_query("UPDATE srv_branching SET vrstni_red = '1' WHERE ank_id='" . $this->anketa . "' AND element_spr='" . $sql_vpisane_spr->id . "'");
                sisplet_query("UPDATE srv_spremenljivka SET vrstni_red = '1', gru_id = '" . $prva_stran_group_id . "' WHERE id='" . $sql_vpisane_spr->id . "'");
            }
        }
    }

    private function trackingChanges()
    {
        TrackingClass::update($this->anketa, '20');
    }


//    /**
//     * Funkcija, ko poišče parent id, zanka gre od prvega nivoja do predzadnjega, ki je parent ID
//     *
//     * @param array $nivoId
//     * @param int $velikost
//     * @return int $parent_id
//     */
//    protected $nivoId;
//    protected $velikost;
//
//    private function poisciPrentId($nivoId, $velikost)
//    {
//        $parent_id = null;
//        for ($i = 0; $i < ($velikost - 1); $i++) {
//            $nivo = explode('-', $nivoId[$i]);
//            $search = ' AND hr.level=' . $nivo[0] . ' AND hs.id=' . $nivo[1] . (!empty($parent_id) ? " AND str.parent_id='$parent_id'" : '');
//            if ($i == 0)
//                $search = ' AND hr.level=' . $nivo[0] . ' AND hs.id=' . $nivo[1];
//            $parent_id = (new HierarhijaOnlyQuery())->queryStruktura($this->anketa, null, $search)->fetch_object()->id;
//        }
//
//        return $parent_id;
//    }

    protected $sql;

    public function sqlError($sql)
    {
        if (!$sql) {
            echo mysqli_error($GLOBALS['connect_db']);
            die();
        }

    }


    /**
     * Funkcija preštevilci vrstni red vprašanj samo na prvi strani, ker bomo tam dodajali sistemske spremenljivke, vlogo pustimo na prvem mestu
     */
    protected $st_prestevilcenja;

    public function prestevilciBranching($st_prestevilcenja = 0, $vloga_prestevilci = false)
    {
        // Pridobi id srv_grupa - vsa sistemska vprašanaj bodo na strani 1
        $grup_id = HierarhijaOnlyQuery::getGrupaId($this->anketa, 1);

        if (empty($grup_id))
            die('Group ID is null!');

        // Pogleda za že vnesene spremenljivke na prvi strani in povečamo vrstni red za št. nivojev, ki bodo naše sistemske spremenljivke srv_spremenljivke
        $vpisane_spr = sisplet_query("SELECT id, gru_id, variable, vrstni_red FROM srv_spremenljivka WHERE gru_id='" . $grup_id . "' AND variable!='vloga' ORDER BY vrstni_red", "obj");

        // povečamo vrstni red pri srv_spremenljivke
        if (!is_null($vpisane_spr) && sizeof($vpisane_spr) > 0) {
            // ker prvi je še vedno vloga, ko se aktivira anketo
            $i = 2;
            if (!empty($vpisane_spr->id)) {
                $vrstni_red = $st_prestevilcenja + $i;
                sisplet_query("UPDATE srv_spremenljivka SET vrstni_red='" . $vrstni_red . "' WHERE id='" . $vpisane_spr->id . "'");
            } else {
                foreach ($vpisane_spr as $spr) {
                    $vrstni_red = $i + $st_prestevilcenja;
                    $sql_spremenljivka = sisplet_query("UPDATE srv_spremenljivka SET vrstni_red='" . $vrstni_red . "' WHERE id='" . $spr->id . "'");
                    $this->sqlError($sql_spremenljivka);
                    $i++;
                }
            }
        }

        $vloga_je = '';
        if (!$vloga_prestevilci) {
            // Pridobi spremenljivka id za vlogo in ga ne šteje pri srv_branchingu
            $sql_vloga_id = sisplet_query("SELECT id, gru_id, variable, vrstni_red FROM srv_spremenljivka WHERE gru_id='" . $grup_id . "' AND variable='vloga' ORDER BY vrstni_red LIMIT 0,1", "obj")->id;
            $vloga_je = " AND element_spr!='" . $sql_vloga_id . "'";
        }

        // Pridobi branching brez vloge, ker vloga mora ostati na prvem mestu
        $vpisan_branch = sisplet_query("SELECT * FROM srv_branching WHERE ank_id='$this->anketa' " . $vloga_je . " ORDER BY vrstni_red", "obj");

        //povečamo vrstni red tudi pri srv_branching
        if (!is_null($vpisan_branch) && sizeof($vpisan_branch) > 0) {
            if (!empty($vpisan_branch->ank_id)) {
                $vrstni_red = $st_prestevilcenja + 2;
                sisplet_query("UPDATE srv_branching SET vrstni_red='" . $vrstni_red . "' WHERE element_spr='" . $vpisan_branch->element_spr . "' AND ank_id='" . $this->anketa . "'");
            } else {
                foreach ($vpisan_branch as $branch) {
                    $vrstni_red = $branch->vrstni_red + ($st_prestevilcenja + 1);
                    $sql_branching = sisplet_query("UPDATE srv_branching SET vrstni_red='" . $vrstni_red . "' WHERE element_spr='" . $branch->element_spr . "' AND ank_id='" . $this->anketa . "'");
                    $this->sqlError($sql_branching);
                }
            }

        }
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

    /**
     * Iz vgnezdenega polja izdela vrstice z vsemi nivoji, če nekje ni podatka potem izdela prazna polja do konca
     * @return array
     */
    public function array_flatten($array, $koncniArray = [])
    {

        foreach ($array as $key => $value) {
            if (is_array($value) && $key != 0)
                return $this->array_flatten($value, $koncniArray);

        }
        $koncniArray[] = $array;

//        d($koncniArray);
        return $koncniArray;

        foreach ($array as $key => $row) {
            if (is_array($row)) {
                return $this->array_flatten($row, $return);
            } else {
                $return[] = $row;
            }
//            if (count($row) == count($row, COUNT_RECURSIVE))
//               $row = $this->array_flatten($row);


            $return[] = $array;
        }

        return $return;
    }


    /**
     * Shrani trenutno strukturo v srv_hierarhija_shrani
     *
     * @param
     * @return
     */
    public function shraniStrukturoHierarhijeVString()
    {
        // tracking - beleženje sprememb
        $this->trackingChanges();

        if (empty($this->anketa))
            return 'false';

        // ID shranjene strukture
        $id = (new HierarhijaQuery())->getDeleteHierarhijaOptions($this->anketa, 'srv_hierarhija_shrani_id', null, null, false);

        // Celotno strukturo skopiramo v string/serialize()
        $struktura_string = HierarhijaKopiranjeClass::getInstance($this->anketa)->get(true);

        // Kadar še nimamo vpisano strukturo ali izbrišemo vse uporabnike, vedno shranimo potem NULL
        if (sizeof($struktura_string) == 0)
            return sisplet_query("UPDATE srv_hierarhija_shrani SET struktura = NULL WHERE id='" . $id . "'  AND anketa_id='" . $this->anketa . "'");

        // Shrani strukturo
        $sql_insert = sisplet_query("UPDATE srv_hierarhija_shrani SET struktura='" . $struktura_string . "' WHERE id='" . $id . "'  AND anketa_id='" . $this->anketa . "'");
        $this->sqlError($sql_insert);

        // Prešteje število uporabnikov na zadnjem nivoju
        $users_upravicen_do_evalvacije = (new HierarhijaOnlyQuery())->queryStrukturaUsers($this->anketa, ' AND hs.level=(SELECT MAX(level) FROM srv_hierarhija_struktura WHERE anketa_id=' . $this->anketa . ')');
        $st_upravicencev_do_evalvacije = mysqli_num_rows($users_upravicen_do_evalvacije);

        // Število vseh uporabnikov v hierarhiji
        $vseh_uporabnikov = (new HierarhijaOnlyQuery())->queryStrukturaUsers($this->anketa);
        $st_vseh_uporabnikov = mysqli_num_rows($vseh_uporabnikov);

        sisplet_query("UPDATE srv_hierarhija_shrani SET st_uciteljev='" . $st_upravicencev_do_evalvacije . "' WHERE id='" . $id . "' AND anketa_id='" . $this->anketa . "'");
        sisplet_query("UPDATE srv_hierarhija_shrani SET st_vseh_uporabnikov='" . $st_vseh_uporabnikov . "' WHERE id='" . $id . "'  AND anketa_id='" . $this->anketa . "'");
    }

    public function sendMail($email, $content = null, $subject = null)
    {
        if (empty($email))
            return false;

        try {
            $MA = new MailAdapter($this->anketa, $type='account');
            $MA->addRecipients($email);
            $MA->sendMail(stripslashes($content), $subject);
        } catch (Exception $e) {
            print_r('Email ni bil poslan: ' . $e);
            error_log("Email ni bil poslan: $e");
        }
    }

    /**
     * Pošlji email za managerje, ki so na hiearhiji
     *
     * @return string
     */
    public function obvestiManagerjeSendEmail()
    {
        $managerji = (!empty($_POST['managerji']) ? $_POST['managerji'] : null);

        if (sizeof($managerji) > 0) {
            foreach ($managerji as $user_id) {
                $email = sisplet_query("SELECT email FROM users WHERE id='" . $user_id . "'", "obj")->email;

                $subject = 'Dostop do gradnje samooevalvacije - 1ka.si';
                $content = $this->lang['srv_hierarchy_manager_email_1'];
                $content .= '<p>' . $this->lang['srv_hierarchy_manager_email_2'] . '»<b>' . SurveyInfo::getInstance()->getSurveyColumn('naslov') . '</b>«' . $this->lang['srv_hierarchy_manager_email_3'] . '<a href="' . $this->site_url . '" target="_blank">' . $this->site_url . '</a></p>';

                //Zaključek emaila
                $content .= '<p>' . $this->lang['srv_hierarchy_manager_email_4'] . '<a href="' . $this->site_url . '" target="_blank">' . $this->site_url . '</a>.' . $this->lang['srv_hierarchy_manager_email_5'];
                $content .= '»' . $email . '«' . $this->lang['srv_hierarchy_manager_email_6'] . '</p>';

                // Podpis
                $signature = Common::getEmailSignature();
                $content .= $signature;

                $this->sendMail($email, $content, $subject);
            }
        }

        return 'success';
    }


    /**
     * Vrni html predogleda emaila za učitelje ali managerje
     *
     * @return html
     */
    public function predogledEmailaZaUciteljeAliManagerje()
    {
        if (empty($_POST['vrsta']))
            return null;

        // Kodo za učence pokažemo, samo kadar ni podatka v bazi
        if (HierarhijaQuery::getOptionsPosljiKode($this->anketa) == 'nikomur' && SurveyInfo::getSurveyModules('hierarhija') < 2) {

            echo '<div style="padding: 50px 20px;">' . $this->lang['srv_hierarchy_preview_none'] . '</div>';

        } else {

            $vrsta = $_POST['vrsta'];

            echo '<div style="padding-bottom: 20px;">';
            echo '<h2>' . ($vrsta == 1 ? $this->lang['srv_hierarchy_preview_email_teacher_header'] : $this->lang['srv_hierarchy_preview_email_manager_header']) . '</h2>';
            echo '<div>';

            // email učitelji
            if ($vrsta == 1) {
                $koda_za_resevanje_ankete = HierarhijaQuery::getOptionsPosljiKode($this->anketa);

                echo $this->lang['srv_hierarchy_teacher_email_1'];
                echo '<p>' . $this->lang['srv_hierarchy_teacher_email_2'] . '»<b>' . SurveyInfo::getSurveyTitle() . '</b>«' . $this->lang['srv_hierarchy_teacher_email_3'] . '<a href="' . $this->site_url . 'sa" target="_blank">' . $this->site_url . 'sa</a></p>';

                echo '<br /><table style="border-spacing: 0;">';
                echo '<thead>';
                echo '<tr>
                                <th style="border: 1px solid #ddd; padding: 8px 10px; text-align: left; background-color: #EFF2F7;">Hierarhija</th>';

                // Koda samo za ucence
                if (SurveyInfo::getSurveyModules('hierarhija') == 2 || in_array($koda_za_resevanje_ankete, ['vsem', 'ucitelju']))
                    echo '<th style="border: 1px solid #ddd; padding: 8px 10px; text-align: center; background-color: #EFF2F7;">Koda za učitelja</th>';

                // V kolikor nimamo nikakršne izbere potem posredujemo kodo tudi za učence
                if (in_array($koda_za_resevanje_ankete, ['vsem', 'ucencem']))
                    echo '<th style="border: 1px solid #ddd; padding: 8px 10px; text-align: center; background-color: #EFF2F7;">Koda za učence</th>';

                echo '<tr>';
                echo '</thead>';
                echo '<tbody>';

                echo '<tr>';
                echo '<td style="border: 1px solid #ddd; padding: 8px 10px; text-align: left;">Struktura hierarhije</td>';

                if (SurveyInfo::getSurveyModules('hierarhija') == 2 || in_array($koda_za_resevanje_ankete, ['vsem', 'ucitelju']))
                    echo '<td style="border: 1px solid #ddd; padding: 8px 10px; text-align: center;"><span style="letter-spacing: 1px; font-size:16px; font-weight: bold;"> # # # # # </span></td>';

                if (in_array($koda_za_resevanje_ankete, ['vsem', 'ucencem']))
                    echo '<td style="border: 1px solid #ddd; padding: 8px 10px; text-align: center;color:#ffa608;"><span style="letter-spacing: 1px; font-size:16px; font-weight: bold;"> # # # # # </span></td>';
                echo '</tr>';

                echo '</tbody>';
                echo '<table><br />';

                //Zaključek emaila
                // V kolikor se emailpošlje samo učiteljem potem se skrije možnost za dostop učiteljem
                $onemogocenDostopUcitelju =  (new HierarhijaQuery())->getDeleteHierarhijaOptions($this->anketa, 'onemogoci_dostop_uciteljem', NULL, NULL, FALSE);

                if (is_null($onemogocenDostopUcitelju) && in_array($koda_za_resevanje_ankete, ['ucitelju'])) {
                    echo '<p>' . $this->lang['srv_hierarchy_teacher_email_4'] . '<a href="' . $site_url . '" target="_blank">' . $site_url . '</a>' . $this->lang['srv_hierarchy_teacher_email_5'];
                    echo '»' . $uporabnik->email . '«' . $this->lang['srv_hierarchy_teacher_email_6'] . '</p>';
                }

            }


            // email managerji
            if ($vrsta == 2) {
                echo $this->lang['srv_hierarchy_manager_email_1'];
                echo '<p>' . $this->lang['srv_hierarchy_manager_email_2'] . '»<b>' . SurveyInfo::getInstance()->getSurveyColumn('naslov') . '</b>«' . $this->lang['srv_hierarchy_manager_email_3'] . '<a href="' . $this->site_url . '" target="_blank">' . $this->site_url . '</a></p>';

                //Zaključek emaila
                echo '<p>' . $this->lang['srv_hierarchy_manager_email_4'] . '<a href="' . $this->site_url . '" target="_blank">' . $this->site_url . '</a>.' . $this->lang['srv_hierarchy_manager_email_5'];
                echo '»<i>#elektronski naslov uporabnika#</i>«' . $this->lang['srv_hierarchy_manager_email_6'] . '</p>';
            }

            // Podpis
            $signature = Common::getEmailSignature();
            echo $signature . '<br /><br />';

            echo '</div>';
            echo '</div>';
        }

        // Gumb za zapret popup in potrdit
        echo '<div class="buttonwrapper spaceRight floatLeft">';
        echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_cancel(); return false;"><span>' . $this->lang['srv_close_profile'] . '</span></a>' . "\n\r";
        echo '</div>';

    }

    /**
     *  Preverimo, če vprašanja obstajajo in če vsebuje samo dovoljena vprašanja
     *
     * @return int 1 or 0;
     */
    public function preveriUstreznostCeJePrimernaZaHierarhijo()
    {

    	  if(!HierarhijaHelper::preveriDostop($this->anketa)){
    	  	echo 'dostop';
    	  	return false;
	      }


        // Preverimo, če je že kakšno vprašanje v anketi ali ni nič
        $napaka = HierarhijaQuery::preveriBranchingCeJeKakoVprasanje($this->anketa);

        // Izberemo prevzeto anketo, ker v bazi še ni vprašanj
        if ($napaka == 1)
            echo 'privzeta';

        // Ne moremo aktivirati modula, ker vsebuje napačen tip
        if ($napaka == 2)
            echo 'napacen-tip';

        if ($napaka == 3)
            echo 'samo-besedilni-tip';

        if ($napaka == 9)
            echo 'ponovna-aktivacija';

        if ($napaka == 'ok')
            echo 'ok';

    }

    /**
     * Pridobimo vse ankete, ki so v knjižnici za hierarhijo
     *
     * @return HTML
     */
    public function pridobiVseAnketeKiSoVknjizniciZaHierarhijo()
    {
        global $site_url;
        global $hierarhija_folder_id;

        $ime_mape = sisplet_query("SELECT * FROM srv_library_folder where id='" . $hierarhija_folder_id . "'", "obj")->naslov;
        $ankete_v_knjiznici = sisplet_query("SELECT * FROM srv_library_anketa WHERE folder='" . $hierarhija_folder_id . "'", "obj");

        if (empty($ime_mape) && sizeof($ankete_v_knjiznici) == 0) {
            echo 'Knjižnica je prazna.';
            return null;
        }

        echo '<h4>' . $ime_mape . '</h4>';
        echo '<ul style="list-style: none;">';

        if (is_array($ankete_v_knjiznici) && empty($ankete_v_knjiznici->ank_id)) {
            foreach ($ankete_v_knjiznici as $anketa) {
                $srv_anketa = sisplet_query("SELECT id, naslov FROM srv_anketa WHERE id='" . $anketa->ank_id . "'", "obj");

                if(!empty($srv_anketa->naslov)) {
                    echo '<li>';
                    echo '<input type="radio" name="knjiznica_izbira" id="ank_'.$anketa->ank_id.'" value="'.$anketa->ank_id.'" /> ';
                    echo '<span class="enka-checkbox-radio"></span>';
                    echo '<span>
                                <a href="'.$this->site_url.'/main/survey/index.php?anketa='.$anketa->ank_id.'&amp;preview=on" target="_blank" title="Predogled ankete">
                                  <span class="faicon preview"></span>
                                </a>
                          </span>';
                    echo '<label for="ank_'.$anketa->ank_id.'">'.$srv_anketa->naslov.'</label>';
                    echo '</li>';
                }
            }
        } elseif (is_object($ankete_v_knjiznici)) {
            $srv_anketa = sisplet_query("SELECT id, naslov FROM srv_anketa WHERE id='" . $ankete_v_knjiznici->ank_id . "'", "obj");

            if(!empty($srv_anketa->naslov)) {
                echo '<li>';
                echo '<input type="radio" name="knjiznica_izbira" id="ank_'.$ankete_v_knjiznici->ank_id.'" value="'.$ankete_v_knjiznici->ank_id.'" /> ';
                echo '<span>
                            <a href="'.$this->site_url.'main/survey/index.php?anketa='.$ankete_v_knjiznici->ank_id.'&amp;preview=on" target="_blank" title="Predogled ankete">
                              <span class="faicon preview"></span>
                            </a>
                      </span>';
                echo '<label for="ank_'.$ankete_v_knjiznici->ank_id.'">'.$srv_anketa->naslov.'</label>';
                echo '</li>';
            }
        }

        echo '</ul>';

    }

    /**
     * Posodobi, vnesi ali briši iz tabele srv_hierarhija_options
     *
     * @return
     */
    public function posodobiAliVnesiVtabeloOpcije()
    {
        $name = (!empty($_POST['name']) ? $_POST['name'] : null);
        $value = (!empty($_POST['value']) ? $_POST['value'] : null);
        $method = (!empty($_POST['method']) ? $_POST['method'] : null);

        if (empty($name))
            return false;

        if ($method == 'delete') {
            sisplet_query("DELETE FROM srv_hierarhija_options WHERE anketa_id='" . $this->anketa . "' AND option_name='" . $name . "'");

            // Pri ocijah izbrišemo "onemogoci_dostop_uciteljem" potem moramo v tabeli srv_dostop dodati dostop za vse te učitelje
            if ($name == 'onemogoci_dostop_uciteljem')
                $this->preveriDostopDoAnkete('insert');

            echo 'Delete';
            return true;
        }

        // Vsekakor preverimo, če podatek že obstaja v bazi
        $obstaja = sisplet_query("SELECT id FROM srv_hierarhija_options WHERE anketa_id='" . $this->anketa . "' AND option_name='" . $name . "'", "obj");
        if (!is_null($obstaja) && sizeof($obstaja) > 0)
            $method = 'put';

        if ($method == 'put') {
            sisplet_query("UPDATE srv_hierarhija_options SET option_value='" . $value . "' WHERE anketa_id='" . $this->anketa . "' AND option_name='" . $name . "'");
            echo 'Update';
            return true;
        }

        sisplet_query("INSERT INTO srv_hierarhija_options (anketa_id, option_name, option_value) VALUES ($this->anketa, '" . $name . "',  '" . $value . "')");

        // Pri ocijah dodamoo "onemogoci_dostop_uciteljem" potem moramo v tabeli srv_dostop izbrisati vsem učiteljem dostop
        if ($name == 'onemogoci_dostop_uciteljem')
            $this->preveriDostopDoAnkete('delete');

        echo 'Insert';
    }

    /**
     * Prteverimo, če imajo učitelji na hierarhiji dostop drugače jih dodamo ali odstranimo
     *
     * @param string $tip
     */

    private function preveriDostopDoAnkete($tip = null)
    {
        // Vsi uporabniki na zadnjem nivoju
        $users_na_zadnjem_nivoju = (new HierarhijaOnlyQuery())->queryStrukturaUsers($this->anketa, ' AND hs.level=(SELECT MAX(level) FROM srv_hierarhija_struktura WHERE anketa_id=' . $this->anketa . ') GROUP BY users.id');

        if ($tip == 'insert') {
            while ($uporabnik = $users_na_zadnjem_nivoju->fetch_object()) {
                HierarhijaQuery::dostopZaUporabnika($this->anketa, $uporabnik->user_id, 'insert');
            }
        }

        if ($tip == 'delete') {
            while ($uporabnik = $users_na_zadnjem_nivoju->fetch_object()) {
                HierarhijaQuery::dostopZaUporabnika($this->anketa, $uporabnik->user_id, 'delete');
            }
        }
    }


    /**
     * Pošlji vabilo samo učiteljem, če jim predhodno ni bilo poslano
     *
     * @return string
     */
    private function posljiElektronskoSamoUciteljem()
    {

        Hierarhija::posljiEmailSkodamiUcencemAliSamoUciteljem($this->anketa);

        HierarhijaQuery::saveOptions($this->anketa, 'obvesti_samo_ucitelje', 1);

        return true;
    }


}


