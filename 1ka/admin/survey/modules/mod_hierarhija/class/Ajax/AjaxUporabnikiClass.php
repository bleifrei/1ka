<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 03.02.2017
 *****************************************/

namespace Hierarhija\Ajax;


use Hierarhija\HierarhijaAjax;
use Hierarhija\HierarhijaHelper;
use Hierarhija\Model\HierarhijaOnlyQuery;
use Hierarhija\Model\HierarhijaQuery;
use SurveyInfo;
use TrackingClass;

class AjaxUporabniki
{
    private $anketa;
    private $lang;
    private $hierarhija_type;

    public function __construct($anketa)
    {
        if (empty($anketa))
            return null;

        $this->anketa = $anketa;

        // global variable
        global $lang;
        global $global_user_id;
        $this->lang = $lang;
        $this->user_id = $global_user_id;
        $this->hierarhija_type = HierarhijaHelper::preveriTipHierarhije($this->anketa);

        return $this;
    }

    private static $_instance;

    public static function init($anketa)
    {
        if (!static::$_instance)
            return (new AjaxUporabniki($anketa));

        return static::$_instance;
    }

    /**
     * Pridobimo vse celotno strukturo za prikaz v dataTables
     *
     * @return $array - večdimenzionalni
     */
    public function getDataTablesPodatkeZaPrikazTabele()
    {
        // če je admin
        if ($this->hierarhija_type < 5) {
            $podatki = (new HierarhijaQuery())->hierarhijaArrayDataTables($this->anketa);
        } else {
            $hierarhija = (new HierarhijaQuery());
            $uporabnik = $hierarhija->preveriPravicoUporabnika($this->anketa);
            $struktura = $hierarhija->poisciHierarhijoNavzgor($uporabnik->struktura_id);

            $podatki = (new HierarhijaQuery())->hierarhijaArrayDataTables($this->anketa, $struktura);
        }

        return $podatki;
    }

    /**
     * Preverimo, če je omenjen šifrant že napisan za ustrezen nivo
     *
     * @return $array or null
     */
    public function preveriVpisSifrantaZaSledeciNivo()
    {
        $level = $_POST['level'];
        $hierarhija_sifranti_id = $_POST['hierarhija_sifranti_id'];

        // Če je parent_id = potem moramo pogledati na prvi nivo IS NULL
        if (empty($_POST['parent_id'])) {
            $parent = "parent_id IS NULL";
        } else {
            $parent = "parent_id='" . $_POST['parent_id'] . "'";
        }

        $sql = sisplet_query("SELECT * FROM srv_hierarhija_struktura WHERE hierarhija_sifranti_id='" . $hierarhija_sifranti_id . "' AND level='" . $level . "' AND " . $parent);

        // imamo zapis v bazi, potem pogledamo še če obstajajo uporabniki
        if ($sql->num_rows > 0) {

            $podatki = [];
            while ($row = $sql->fetch_object()) {

                $podatki = [
                    'id' => $row->id,
                    'level' => $row->level,
                    'parent_id' => $row->parent_id,
                    'hierarhija_sifranti_id' => $row->hierarhija_sifranti_id

                ];

                $sql_user = sisplet_query("
                                SELECT 
                                  users.id as id,
                                  users.email as email,
                                  users.name as name,
                                  users.surname as surname
                                 FROM 
                                    srv_hierarhija_struktura_users as u 
                                LEFT JOIN
                                    users ON users.id = u.user_id 
                                WHERE 
                                    u.hierarhija_struktura_id = '" . $row->id . "'
                                  ");

                // V kolikor so v bazi uporabniki potem pridobimo vse in zapišemo v polje
                if ($sql_user->num_rows > 0) {
                    while ($user = $sql_user->fetch_object()) {
                        $podatki['uporabniki'][] = [
                            'id' => $user->id,
                            'email' => $user->email,
                            'ime' => $user->name,
                            'priimek' => $user->surname
                        ];
                    }
                }

            }

            return ($podatki);
        }

        return 0;
    }

    /**
     * Posodobimo sifrante za JS tree, če brišemo element iz drevesne strukture
     *
     * @return array
     */
    public function getPosodobiSifranteZaJsTree()
    {
        $id = $_POST['id'];

        # Pridobimo strukturo navzgor od trenutnega ID-ja za vse šifrante
        $nivoji = HierarhijaQuery::posodobiSifranteVrednostiGledeNaTrenutenIdStrukture($id);

        # Pridobimo max st nivojev
        $max_st = sisplet_query("SELECT MAX(level) AS max FROM srv_hierarhija_ravni WHERE anketa_id='$this->anketa'")->fetch_object()->max;

        # Preverimo na katerem ID-ju (nivoju) se nahaja uporabnik, ki je kliknil na strukturo
        $user_db = HierarhijaOnlyQuery::queryStrukturaUsersLevel($this->anketa, $this->user_id);
        if ($user_db->num_rows == 0 && $this->hierarhija_type < 5) {
            $nivoji['user'] = [
                'id_strukture' => 'admin',
                'max_level' => $max_st
            ];
        }

        if ($user_db->num_rows > 0) {
            $user_db = $user_db->fetch_object();
            $nivoji['user'] = [
                'id_strukture' => $user_db->struktura_id,
                'level' => $user_db->level,
                'max_level' => $max_st
            ];
        }

        return $nivoji;
    }

    /**
     * Shrani strukturo v bazo (novi šifranti in uporabniki za sledeči nivo
     *
     * @return string or intiger
     */
    public function shraniStrukturoSifrantovInUporabnikov()
    {
        // tracking - beleženje sprememb
        $this->trackingChanges();

        $vnos = $_POST['vnos'];
        $osebe = $_POST['osebe'];
        $hierarhija_id = null;

        if (empty($vnos))
            return 0;

        // Najprev shranimo strukturo, da pridobimo id za vpis oseb
        foreach ($vnos as $nivo => $vpis) {
            // Vpišemo samo nivoje večje od 0, ker post request vsebuje tudi polje 0, ki za naš ni relavanto

            if ($nivo > 0 && is_array($vpis) && empty($vpis['id'])) {
                $ravni_id = $vpis['hierarhija_ravni_id'];
                $sifrant_id = $vpis['hierarhija_sifranti_id'];
                $level = $vpis['level'];

                // Če je prvi nivo potem je parent_id NULL, za vse ostale primere pa moramo met parenta, če naprej v hierarhiji ni parenta potem uporabimo $hierarhija_id - predhodni element vpisa
                if ($level == 1)
                    $parent_id = null;
                elseif ($level > 1 && empty($vpis['parent_id']))
                    $parent_id = $hierarhija_id;
                else
                    $parent_id = $vpis['parent_id'];

                // Vpis nove strukture, kjer dobimo ID in uporabimo potem za parent id
                $hierarhija_id = sisplet_query("INSERT INTO    
                                    srv_hierarhija_struktura (anketa_id, hierarhija_ravni_id, parent_id, hierarhija_sifranti_id, level) 
                                VALUES 
                                    ('" . $this->anketa . "', '" . $ravni_id . "', " . var_export($parent_id, true) . ",'" . $sifrant_id . "', ' " . $level . "')", "id");

                // Vpišemo še uporabnike, če obstaja
                if (!empty($osebe[$vpis['level']]) && is_array($osebe[$vpis['level']])) {
                    // Osebe so nanizane v poljih, vsaka oseba je podana preko polja, kjer [ 0 => email, 1=>ime, 2=>priimek], obvezen je samo prvi element
                    foreach ($osebe[$vpis['level']] as $oseba) {
                        $this->dodajUporabnikaVbazo($oseba, $hierarhija_id);
                    }
                }

            } elseif ($nivo > 0 && is_array($vpis) && !empty($vpis['id'])) {
                // Vpišemo samo uporabnika
                if (!empty($osebe[$vpis['level']]) && is_array($osebe[$vpis['level']])) {
                    // Osebe so nanizane v poljih, vsaka oseba je podana preko polja, kjer [ 0 => email, 1=>ime, 2=>priimek], obvezen je samo prvi element
                    foreach ($osebe[$vpis['level']] as $oseba) {
                        $this->dodajUporabnikaVbazo($oseba, $vpis['id']);
                    }
                }
            }
        }

        // Podatke shranimo še v srv_hierarhija_shrani
        (new HierarhijaAjax($this->anketa))->shraniStrukturoHierarhijeVString();

        return 'success';
    }

    /**
     * pridobimo pravice trenutnega uporabnika
     *
     * level 1 - super admin ima vse pravice, ponavadi je lastnik ankete oz. jo je ustvaril in vključil hierarhijo
     * levle 10 - običajni uporabnik, ki je dodan na določen nivo hierarhije
     *
     * @return array
     */
    public function getUserLevelPermission()
    {
        // vrne polje leve, struktura_id in parent_id
        $hierarhija = (new HierarhijaQuery());
        $uporabnik = $hierarhija->preveriPravicoUporabnika($this->anketa);

        if (is_array($uporabnik) && $uporabnik['uporabnik'] == 1) {
            echo json_encode($uporabnik);
            die();
        }

        $struktura = $hierarhija->poisciHierarhijoNavzgor($uporabnik->struktura_id);

        return [
            'uporabnik' => $uporabnik,
            'struktura' => $struktura
        ];
    }

    /**
     * Pridobimo nivoje in šifrane za sledečo raven pri tem če ni super admin ga omejimo
     *
     * @return array - ['nivoji', 'sifranti', 'maxLevel]
     */
    public function getSifrantiZaHierarhijaUserType()
    {
        if ($this->hierarhija_type < 5) {
            // Pridobimo vse ravni in šifrante
            $podatki = (new HierarhijaQuery())->getSifrantAdmin($this->anketa);
        } else {
            // Pridobimo nivo uporabnika, ki se nahaja najvišje v strukturi
            $uporabnik = (new HierarhijaQuery())->preveriPravicoUporabnika($this->anketa);

            // Pridobimo vse ravni in šifrante samo od uporabnikovega nivoja/level navzdol
            $podatki = (new HierarhijaQuery())->getSifrantAdmin($this->anketa, 999, $uporabnik->level);
        }

        // Pridobimo max število nivojev, ki jih lahko vnesemo
        $podatki['maxLevel'] = sisplet_query("SELECT MAX(level) as level FROM srv_hierarhija_ravni WHERE anketa_id='" . $this->anketa . "'")->fetch_object()->level;

        return $podatki;
    }

    /**
     * Dodamo uporabnika v bazo za sledečo strukturo
     */
    private function dodajUporabnikaVbazo($oseba, $struktura_id, $last = false)
    {
        global $pass_salt;

        // Podatki o uporabniku
        $email = (is_array($oseba) ? trim($oseba[0]) : $oseba);
        $update = false;

        //Iz emaila pridobimo podatke
        preg_match('/(\w+)((?:\.)(\w+))?/', $email, $matches);

        $name = !empty($oseba[1]) ? trim($oseba[1]) : ucfirst(mb_strtolower($matches[1]));

        // V kolikor je primek ga
        if(!empty($oseba[2])) {
            $surname = trim($oseba[2]);
            $update = true;
        }elseif(!empty($matches[3])){
            $surname = ucfirst(mb_strtolower($matches[3]));
        }else{
            $surname = '';
        }


        if ($email != '' && validEmail($email)) {
            $sql_user = sisplet_query("SELECT id FROM users WHERE email='$email'");

            if (mysqli_num_rows($sql_user) == 0) {
                sisplet_query("INSERT INTO users (name, surname, email, pass, type, when_reg, came_from) VALUES ('$name', '$surname', '$email', '" . base64_encode((hash(SHA256, '' . $pass_salt))) . "', '3', DATE_FORMAT(NOW(), '%Y-%m-%d'), '1')");
                $user_id = mysqli_insert_id($GLOBALS['connect_db']);
            } else {
                $row = mysqli_fetch_array($sql_user);
                $user_id = $row['id'];

                // V kolikor smo mi vnesli ime in prrimek potem popravimo tudi v bazi
                if($update)
                    sisplet_query("UPDATE users SET name='".$name."', surname='".$surname."' WHERE id='".$user_id."'");
            }

            // V kolikor smo dodali nov email na zadnji nivo potem samo zamenjamo z obstoječim
            if ($last) {
                $query = sisplet_query("UPDATE srv_hierarhija_struktura_users SET user_id='" . $user_id . "' WHERE hierarhija_struktura_id='" . $struktura_id . "'");
            } else {
                $query = sisplet_query("INSERT INTO srv_hierarhija_struktura_users (hierarhija_struktura_id, user_id) VALUES ('" . $struktura_id . "', '" . $user_id . "')");
            }
            if (!$query) echo mysqli_error($GLOBALS['connect_db']);

            // Preverimo, če je uporabnik že dodan in če ni ga nato šele dodamo
            $user_search = sisplet_query("SELECT * FROM srv_hierarhija_users WHERE user_id='" . $user_id . "' AND anketa_id='$this->anketa'");
            if ($user_search->num_rows == 0) {
                $user_query = sisplet_query("INSERT INTO srv_hierarhija_users (user_id, anketa_id, type) VALUES ('" . $user_id . "', '" . $this->anketa . "', 10)");
                if (!$user_query) echo mysqli_error($GLOBALS['connect_db']);
            }

            // Preverimo, če ima uporabnik za omenjeno anketo že pravice in mu nato dodamo pravice
            HierarhijaQuery::dostopZaUporabnika($this->anketa, $user_id, 'insert');
        }

    }

    /**
     * Pridobi vse uporabnike za določen nivo
     * uporablja se prid DataTables edit mode, da izpiše uporabnike pod vsak nivo
     *
     * @var $id - struktura id
     * @return $array
     */
    public function pridobiUporabnikeZaDolocenNivoId()
    {
        // id strukture, da preverimo kateri uporabnikise nahajajo na omenjeni strukturi
        $id = $_POST['id'];

        $uporabniki = (new HierarhijaQuery())->pridobiVseUporabnikeZaSpecificnoStrukturo($id);

        if (!is_array($uporabniki) || sizeof($uporabniki) == 0)
            return 0;

        //Preverimo max število nivojev/ravni za omenjeno anketo
        $max_level = sisplet_query("SELECT MAX(level) AS level FROM srv_hierarhija_ravni WHERE anketa_id='" . $this->anketa . "'")->fetch_object()->level;

        return [
            'uporabniki' => $uporabniki,
            'maxLevel' => $max_level
        ];
    }

    /**
     * Shrani uporabnike na določen nivo strukture - pri DataTables edit mode
     */
    public function postUporabnikeZaDolocenNivoId()
    {
        // tracking - beleženje sprememb
        $this->trackingChanges();

        $uporabniki = json_decode(stripslashes($_POST['uporabniki']));
        $struktura_id = $_POST['struktura'];

        if (sizeof($uporabniki) == 0)
            return 'uporabnik';

        // pobrišemo vse že dodane uporabnike
        sisplet_query("DELETE FROM srv_hierarhija_struktura_users WHERE hierarhija_struktura_id='" . $struktura_id . "'");

        // vpišemo nove uporabnike
        foreach ($uporabniki as $uporabnik) {
            sisplet_query("INSERT INTO srv_hierarhija_struktura_users (hierarhija_struktura_id, user_id) VALUES ('" . $struktura_id . "', '" . $uporabnik . "')");
        }
    }

    /**
     * Prikaži popup za vnos uporabnikov
     *
     * @return html
     */
    public function getUsersImportPopup(){

        // tracking - beleženje sprememb
        $this->trackingChanges();

        echo '<div>';
        echo '<h2>' . $this->lang['srv_hierarchy_import_user_title'] . '</h2>';
        echo '<div>';
        echo $this->lang['srv_hierarchy_import_user_text'];

        echo '<div style="padding:15px 0;">';
            echo '<textarea id="users-email-import" name="emails" style="height:300px; width:45%;float: left;" placeholder="jan.nov@sola.si, Janez, Novak"></textarea>';
            echo '<div style="width:50%;display: block;float: left;height: 320px;clear: right;overflow: auto;padding-left: 18px;">';
            echo '<b>Seznam učiteljev:</b>';

            $vsi_ucitelji = $this->getAllUserInJson(true);

            if(!empty($vsi_ucitelji)) {
                echo '<div style="height: 269px;overflow: auto;"><ul>';
                    foreach($vsi_ucitelji as $ucitelj) {
                        echo '<li>'.$ucitelj['label'].'</li>';
                    }
                echo '</ul></div>';
            }
            echo '</div>';
        echo '</div>';

        echo '</div>';
        echo '</div>';

        // Gumb za zapret popup in potrdit
        echo '<div class="buttonwrapper spaceRight floatLeft">';
        echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="shraniVseVpisaneUporabnike(); return false;"><span>' . $this->lang['srv_potrdi'] . '</span></a>' . "\n\r";
        echo '</div>';

        echo '<div class="buttonwrapper spaceRight floatLeft">';
        echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_cancel(); return false;"><span>' . $this->lang['srv_close_profile'] . '</span></a>' . "\n\r";
        echo '</div>';
        }

    /**
     * POST: vnos podatkov za vpis uporabnikov*
     */
    public function postImportUsers(){
        $users = (!empty($_POST['users']) ?  json_decode(stripslashes($_POST['users'])) : null);

        if(is_null($users))
            return 'null';

        // Vsakega uporabnika pridobimo v svoj array
        $users =  explode(PHP_EOL, $users);

        // Pridobimo ID za izbrano shranjeno hierarhijo
        $id = HierarhijaQuery::getOptions($this->anketa, 'srv_hierarhija_shrani_id');

        // Pridobimo podatke od prej, če niso NULL
        $ze_vpisani_uporabniki = HierarhijaQuery::getHierarhijaShraniRow($id, 'uporabniki_list');

        // Če imamo obstoječe upoabnik, jih sedajali nove
        $polje = [];
        if(!empty($ze_vpisani_uporabniki))
            $polje = unserialize($ze_vpisani_uporabniki);

        foreach($users as $user){
            $user = trim($user);

            $uporabnik = explode(',', $user);

            if(validEmail($uporabnik[0])) {
                preg_match('/(\w+)((?:\.)(\w+))?/', $uporabnik[0], $matches);

                $name = ( !empty($uporabnik[1]) ? trim($uporabnik[1]) : ucfirst(mb_strtolower($matches[1])) );
                $surname = ( !empty($uporabnik[2]) ? trim($uporabnik[2]) : ucfirst(mb_strtolower($matches[2])) );

                $polje[] = [
                    'id' => $user,
                    'label' => $name. ' '.$surname.' - ('.$uporabnik[0].')'
                ];
            }
        }

        sisplet_query("UPDATE srv_hierarhija_shrani SET uporabniki_list='".serialize($polje)."' WHERE id='" . $id . "'  AND anketa_id='" . $this->anketa . "'");

        echo json_encode($polje);
    }

    /**
     * Pridobimo vse uporabnike
     *
     * @return json
     */
    public function getAllUserInJson($return = false)
    {
        $id = HierarhijaQuery::getOptions($this->anketa, 'srv_hierarhija_shrani_id');
        $uporabniki = HierarhijaQuery::getHierarhijaShraniRow($id, 'uporabniki_list');

        if($return)
            return unserialize($uporabniki);

        echo json_encode(unserialize($uporabniki));
    }


    /**
     * Pop up obrazec za dodajanje uporabnikov na določen nivo
     *
     * @return html
     */
    public function htmlPopUpDodajUporabnikeZaDolocenNivoId()
    {

        // tracking - beleženje sprememb
        $this->trackingChanges();

        $struktura_id = $_POST['struktura'];
        $last = $_POST['last'];

        echo '<div>';
        echo '<h2>' . ($last ? $this->lang['srv_hierarchy_add_new_user_popup_last'] : $this->lang['srv_hierarchy_add_new_user_popup']) . '</h2>';
        echo '<div>';
        echo($last ? $this->lang['srv_hierarchy_edit_users_last'] : $this->lang['srv_hierarchy_edit_users']);
        echo '<div style="padding:15px 0;">';
        if ($last) {
            echo '<input type="text" id="vpis-email-popup" name="emails" style="height: 16px; width:100%;" />';
        } else {
            echo '<textarea id="vpis-email-popup" name="emails" style="height:100px; width:100%;"></textarea>';
        }
        echo '</div>';

        echo '</div>';
        echo '</div>';

        // Gumb za zapret popup in potrdit
        echo '<div class="buttonwrapper spaceRight floatLeft">';
        echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="shrani_email(' . $struktura_id . ', ' . $last . '); return false;"><span>' . $this->lang['srv_potrdi'] . '</span></a>' . "\n\r";
        echo '</div>';

        echo '<div class="buttonwrapper spaceRight floatLeft">';
        echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_cancel(); return false;"><span>' . $this->lang['srv_close_profile'] . '</span></a>' . "\n\r";
        echo '</div>';
    }

    /**
     * Pošlji ID uporabnikov za vpis na strukturo
     *
     * @return boolean
     */
    public function postDodatneUporabnikeNaNivoId()
    {
        $struktura = $_POST['struktura'];
        $uporabniki = $_POST['uporabniki'];
        $last = $_POST['last'];

        if (empty($struktura) || empty($uporabniki))
            return false;

        $this->trackingChanges();

        $uporabniki = json_decode(stripslashes($uporabniki));

        foreach ($uporabniki as $uporabnik) {
            $this->dodajUporabnikaVbazo($uporabnik, $struktura, $last);
        }

        return true;
    }

    /**
     * Kopiraj vrstico iz DataTables v urejanje
     *
     * @return array $struktura
     */
    public function kopirajVrsticoStruktureIzDataTablesVFormo()
    {
        $idLastStrukture = $_POST['id'];
        if (empty($idLastStrukture))
            return false;

        // tracking - beleženje sprememb
        $this->trackingChanges();

        return (new HierarhijaQuery())->poisciHierarhijoNavzgor($idLastStrukture);
    }

    /**
     * Pop up  zamenjaj uporabnika v strukturi
     *
     * @return html
     */
    public function htmlPopUpZamenjajUporabnikaVstrukturi()
    {
        echo '<div class="zamenjava-uporabnika-v-strukturi">';
        echo '<div>';
        echo '<h2>' . $this->lang['srv_hierarchy_title_find_and_replace_user'] . '</h2>';

        echo '<div>';
        echo $this->lang['srv_hierarchy_text_find_and_replace_user'];

        echo '<div style="padding:15px 0;">';
        echo '<label style="font-weight: bold;padding-right: 10px;">' . $this->lang['srv_hierarchy_label_find_email'] . '</label>';
        echo '<input type="text" id="find-email" name="emails" style="height: 16px; width:60%;float: right;margin-right: 10px;" />';
        echo '<span class="error-label">' . $this->lang['srv_hierarchy_error_wrong_email_format'] . '</span>';
        echo '</div>';

        echo '<div style="padding:15px 0;">';
        echo '<label style="font-weight: bold;padding-right: 10px;">' . $this->lang['srv_hierarchy_label_replace_email'] . '</label>';
        echo '<input type="text" id="replace-email" name="emails" style="height: 16px; width:60%;float: right;margin-right: 10px;" />';
        echo '<span class="error-label">' . $this->lang['srv_hierarchy_error_wrong_email_format'] . '</span>';
        echo '</div>';

        echo '</div>';

        echo '</div>';

        echo '<div id="st_zamenjav_uporabnikov"></div>';

        // Gumb za zapret popup in potrdit
        echo '<div class="buttonwrapper spaceRight floatLeft">';
        echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="potriZamenjavoUporabnika(); return false;"><span>' . $this->lang['srv_potrdi'] . '</span></a>' . "\n\r";
        echo '</div>';

        echo '<div class="buttonwrapper spaceRight floatLeft">';
        echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="testnoPreveriKolikoUporabnikovBoZamnjenihVStrukturi(); return false;"><span>' . $this->lang['srv_hierarchy_button_count_user_emails'] . '</span></a>' . "\n\r";
        echo '</div>';

        echo '<div class="buttonwrapper spaceRight floatLeft">';
        echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_cancel(); return false;"><span>' . $this->lang['srv_close_profile'] . '</span></a>' . "\n\r";
        echo '</div>';
        echo '</div>';
    }

    /**
     * testno preveri, kolikokrat bi bil email zamenjan
     *
     * @return integer
     */
    public function getTestnoPreveriStZamenjavEmailVstrukturi()
    {
        $email_za_zamenjavo = $_POST['find_email'];

        $user = sisplet_query("SELECT id, email FROM users WHERE email='" . $email_za_zamenjavo . "'", "obj");

        if (sizeof($user) == 0)
            return 'Ni v baz';

        // Pridobimo uporabnika samo na zadnjem nivoju
        $users_upravicen_do_evalvacije = (new HierarhijaOnlyQuery())->queryStrukturaUsers($this->anketa, ' AND users.id="' . $user->id . '" AND hs.level=(SELECT MAX(level) FROM srv_hierarhija_struktura WHERE anketa_id=' . $this->anketa . ')');

        if (mysqli_num_rows($users_upravicen_do_evalvacije) > 0) {
            echo mysqli_num_rows($users_upravicen_do_evalvacije);
        } else {
            echo 0;
        }
    }

    /**
     * Briši uporabnika iz strukture pri DataTables pogledu oz. če je že vpisan v bazo
     */
    public function brisiUporabnikaIzStrukture()
    {
        // tracking - beleženje sprememb
        $this->trackingChanges();

        if (empty($_POST['uporabnik_id']) || !is_numeric($_POST['uporabnik_id']) || empty($_POST['struktura_id'])) {
            echo 'Ni mogoče';
            return false;
        }

        $uporabnik_id = $_POST['uporabnik_id'];
        $struktura_id = $_POST['struktura_id'];

        // Uporabnika najprej izbrišemo iz strukture hierarhije
        $sql_user = sisplet_query("DELETE FROM srv_hierarhija_struktura_users WHERE hierarhija_struktura_id='" . $struktura_id . "' AND user_id='" . $uporabnik_id . "'");
        $this->sqlError($sql_user);

        // Preverimo, če je uporabnik še kje drugje dodan v hierarhiji, drugače ga moramo odstraniti še iz srv_hierarhija_users, da nima dostopa do ankete ob aktivaciji in tudi da ne prejme emaila za dostop
        $this->preveriCeJeUporabnikPrisotenSeKjeVStrukturi($uporabnik_id);
    }

    /**
     * Zamenjaj email uporabnika v strukturi z novim emailom
     *
     * @param
     * @return
     */
    public function postZamenjajEmailVstrukturi()
    {
        // tracking - beleženje sprememb
        $this->trackingChanges();

        $find_email = $_POST['find_email'];
        $replace_email = $_POST['replace_email'];

        // Validacija, če email obstaja
        if (empty($find_email) && !validEmail($find_email) || empty($replace_email) && !validEmail($replace_email))
            return 'Manjka email';

        // Poiščemo ID uporabnika
        $user = sisplet_query("SELECT id, email FROM users WHERE email='" . $find_email . "'", "obj");

        // Pridobimo uporabnika samo na zadnjem nivoju
        $users_upravicen_do_evalvacije = (new HierarhijaOnlyQuery())->queryStrukturaUsers($this->anketa, ' AND users.id="' . $user->id . '" AND hs.level=(SELECT MAX(level) FROM srv_hierarhija_struktura WHERE anketa_id=' . $this->anketa . ')');

        // Dodamo novega uporabnika v
        if (mysqli_num_rows($users_upravicen_do_evalvacije) > 0) {
            while ($evalviranec = $users_upravicen_do_evalvacije->fetch_object()) {
                $this->dodajUporabnikaVbazo($replace_email, $evalviranec->id, true);
            }
        }

        // Preverimo, če je uporabnik, ki smo ga želeli zamenjati prisoten še kje
        $this->preveriCeJeUporabnikPrisotenSeKjeVStrukturi($user->id);

        return 1;
    }

    /**
     * Briši element vhiertarhiji, ki se prikaže v data tables ali  jstree
     *
     * @param
     * @return
     */
    public function brisiZadnjiElementVstrukturiHierarhije()
    {
        $id = $_POST['id'];

        #V kolikor je anketa aktivna potem brisanje ni več možno
        if (SurveyInfo::getSurveyModules('hierarhija') == 2)
            return 2;


        // tracking - beleženje sprememb
        $this->trackingChanges();

        # Omenjen Id je tudi parent id, zato brisanje ni mogoče
        $parent_obstaja = sisplet_query("SELECT id, parent_id FROM srv_hierarhija_struktura WHERE parent_id='$id'");
        if ($parent_obstaja->num_rows > 0) {
            echo 'obstaja';
            die();
        }

//            # Pridobimo vse ID strukture hierarhije, ki so pod to drevesno strukturo, da lahko potem izbrišemo vse te elemente
//            $el = (new HierarhijaQuery())->pridobiIdStruktureDoKonca($id, $this->anketa);
//            # Dodamo naš $id in sortiramo po zadnjih elementih, ter brišemo hierarhijo od spodaj navzgor. Do našega ID-ja
//            array_push($el, $id);
//            rsort($el);


        # Preverimo, če je bil dodan uporabnik na to strukturo
        $user_id = sisplet_query("SELECT user_id FROM srv_hierarhija_struktura_users WHERE hierarhija_struktura_id='" . $id . "'", "obj");

        # Izbrišemo uporabnika, ki je bil pri določeni strukturi v hierarhiji
        $sql_us = sisplet_query("DELETE FROM srv_hierarhija_struktura_users WHERE hierarhija_struktura_id='" . $id . "'");
        $this->sqlError($sql_us);

        // V kolikor je bil dodan uporabnik na strukturo potem tudi odstranimo vse pravice tega uporabnika, če ga ni več v strukturi
        if (!is_null($user_id))
            $this->preveriCeJeUporabnikPrisotenSeKjeVStrukturi($user_id->user_id);

        # Izbrišemo strukturo
        $sql_hi = sisplet_query("DELETE FROM srv_hierarhija_struktura WHERE id='" . $id . "' AND anketa_id='" . $this->anketa . "'");
        $this->sqlError($sql_hi);

        # Pobriši opcijo, če ni več strukture
        $st_vpisov = sisplet_query("SELECT COUNT(ID) as vsota FROM srv_hierarhija_struktura WHERE anketa_id='" . $this->anketa . "'", "obj");
        if ($st_vpisov->vsota == 0)
            (new HierarhijaQuery())->getDeleteHierarhijaOptions($this->anketa, 'vpisana_struktura', null, true);
    }

    /**
     * Shrani hierarhijo in prido ID polja v bazi
     *
     * @return integer
     */
    public function shraniHierarhijo()
    {
        $ime = $_POST['ime'];

        // Za decoding je potrebno json_decode(stripslashes($_POST['hierarhija'])), za shranjevanje v bazo pustomo kar json format
        $hierarhija = $_POST['hierarhija'];

        echo sisplet_query("INSERT INTO srv_hierarhija_shrani (anketa_id, user_id, ime, hierarhija) VALUES ('$this->anketa', '$this->user_id','$ime', '$hierarhija')", "id");
    }

    /**
     * Preverimo, če je user id prisoten še kje v strukturi, v kolikor ga ni potem odstranimo pravice dostopa do ankete in strukture
     *
     * @param $user_id
     * @return boolean
     */
    private function preveriCeJeUporabnikPrisotenSeKjeVStrukturi($user_id)
    {
        $uporabnik_db = (new HierarhijaOnlyQuery())->queryStrukturaUsers($this->anketa, ' AND users.id="' . $user_id . '"');

        // Uporabnik je še prisoten
        if (mysqli_num_rows($uporabnik_db) > 0)
            return true;

        // Uporabnik ni prisoten in odstranimo vse pravice za dostop do ankete in hierarhije
        // Preverimo, če je uporabnik med privilegiji za dostop do hierarhije
        $user_search = sisplet_query("SELECT id FROM srv_hierarhija_users WHERE user_id='" . $user_id . "' AND anketa_id='" . $this->anketa . "' AND type='10'", 'obj');
        if (sizeof($user_search) > 0)
            sisplet_query("DELETE FROM srv_hierarhija_users WHERE id='" . $user_search->id . "'");


        // Preverimo, če ima uporabnik ima pravice za dostop do ankete in potem tudi to odstranimo
        HierarhijaQuery::dostopZaUporabnika($this->anketa, $user_id, 'delete');

        return false;
    }

    private function trackingChanges()
    {
        TrackingClass::update($this->anketa, '22');
    }

    private function sqlError($sql)
    {
        if (!$sql) {
            echo mysqli_error($GLOBALS['connect_db']);
            die();
        }

    }


}