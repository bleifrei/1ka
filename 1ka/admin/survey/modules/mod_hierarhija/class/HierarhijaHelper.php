<?php
/***************************************
 * Description: Funkcije za pomoč pri hierarhiji
 * Autor: Robert Šmalc
 * Created date: 11.04.2017
 *****************************************/

namespace Hierarhija;

use Hierarhija\Model\HierarhijaOnlyQuery;
use Hierarhija\Model\HierarhijaQuery;
use SurveyInfo;
use Common;

class HierarhijaHelper
{
    /**
     * Iz teksa zamenjamo besede, ki so med lojtrami (#nivo1#, #nivo2#, #ime
     * ucitelja#, #email ucitelja#)
     *
     * @param text $text
     * @param int $ank_id
     *
     * @return text
     */
    public static function dataPiping($text, $ank_id = null)
    {

        if (is_null($ank_id)) {
            $ank_id = (! empty(get('anketa')) ? get('anketa') : $_GET['anketa']);
        }

        // Preverimo, če tekst vsebuje kaj
        if (! preg_match_all('/#([\w\s]+)#/', $text, $matches) || is_null($ank_id)) {
            return $text;
        }

        // Pridobimo ID vrednosti spremenljivk, za katere moramo dobiti naslove, da jih lahko prikažemo
        $get = get('get');

        // V kolikor smo na katerikoli strani, potem gledamo po piškotkih
        if (isset($get->{'survey-'.$ank_id})) {

            $active = '';
            if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1) {
                $active = '_active';
            }

            // ID uporabnika, glede na njegov piškot
            $srv_user = sisplet_query("SELECT id FROM srv_user WHERE cookie='".$get->{'survey-'.$ank_id}."'", "obj")->id;

            // Pridobimo vse odgovore - strukturo hierarhije
            $vrednosti = sisplet_query("SELECT 
                                          v.naslov AS naslov,
                                          ss.variable AS variable,
                                          v.id AS spr_id
                                        FROM 
                                            srv_data_vrednost".$active."  AS sd
                                        LEFT JOIN
                                            srv_vrednost AS v
                                        ON 
                                            sd.vre_id=v.id
                                        LEFT JOIN 
                                           srv_spremenljivka AS ss
                                        ON 
                                            v.spr_id=ss.id
                                        WHERE sd.usr_id='".$srv_user."' ORDER BY ss.variable DESC", "obj");
        }

        // Zanka po vseh besedah v texstu, k so med lojtrama (
        foreach ($matches[0] as $key => $match) {
            $iskanje = strtolower($matches[1][$key]);

            // Zamenjava nivojev (primer: nivo1, nivo2, ...) in vloge (učenec, učitelj)
            if (! empty($get->{$iskanje})) {
                $variabla = sisplet_query("SELECT naslov FROM srv_vrednost WHERE id='".$get->{$matches[1][$key]}."'", "obj");
                $text = str_ireplace($match, $variabla->naslov, $text);
            } elseif ($iskanje === 'hierarhija') {
                $hierarhija_pot = Hierarhija::displayPodatkeOhierarhijiZaRespondente($get, true);
                $text = str_ireplace($match, $hierarhija_pot, $text);
            }

            // Kadar nivoje menjamo na ostalih vprašanjih in nimamo več Get parametrov, potem pridobimo iz že shranjenih sistemskih odgovorov
            if (! empty($vrednosti)) {
                $url_variables = '';

                foreach ($vrednosti as $vrednost) {
                    if ($vrednost->variable == $matches[1][$key]) {
                        $text = str_ireplace($match, $vrednost->naslov, $text);
                    }

                    if (! empty($vrednost->variable)) {
                        $url_variables .= $vrednost->variable.'='.$vrednost->spr_id.'&';
                    }
                }
            }

            // Ostranimo lojtro iz zadnjega nivoja, da lahko nato preverimo pa url naslovu v bazi
            $url_variables = substr($url_variables, 0, strlen($url_variables) - 1);

            // zamenjava podatkov o učitelju/uporabniku - ime, priimek, email
            if (in_array($iskanje, ['ime ucitelja', 'email ucitelja'])) {
                // Pridobimo podatke u uporabniku za sledečo anketo
                if (! empty($get->enc)) {
                    $url_variables = base64_decode($get->enc);
                }

                $user = sisplet_query("SELECT u.email AS email, u.name AS name, u.surname AS surname FROM srv_hierarhija_koda AS h LEFT JOIN users AS u ON u.id=h.user_id WHERE h.anketa_id='".$ank_id."' AND h.url='".$url_variables."'", "obj");

                $zamenjaj = $match;

                // email ne sme biti enak imenu ali priimku
                if ($iskanje == 'ime ucitelja' && ! in_array($user->email, [
                        $user->name,
                        $user->surname,
                    ])) {
                    $zamenjaj = $user->name." ".$user->surname;
                } elseif ($iskanje == 'ime ucitelja') {
                    preg_match('/(\w+)((?:\.)(\w+))?/', $user->email, $ucitelj);

                    $zamenjaj = self::velikaZacetnica($ucitelj[1]);

                    if (! empty($ucitelj[3])) {
                        $zamenjaj .= " ".self::velikaZacetnica($ucitelj[3]);
                    }
                }

                if ($iskanje == 'email ucitelja') {
                    $zamenjaj = $user->email;
                }

                $text = str_ireplace($match, $zamenjaj, $text);
            }
        }

        return $text;
    }

    /**
     * Iz besedila najprej pretvorimo v male črke in nato veliko začetnico
     *
     * @param string $string
     *
     * @return string
     */
    private static function velikaZacetnica($string)
    {
        return ucfirst(mb_strtolower($string, 'UTF-8'));
    }

    /**
     * Preverimo dostop določenega uporabnika do modula SA
     *
     * @param (int) $anketa
     */
    public static function preveriDostop($anketa_id = 0)
    {
        global $admin_type;

        // Če ima dostop in je modul vključen
        if (Common::checkModule('hierarhija') == '1') {

            //anketa še ni ustvarjena, potem mu je dostop omogočen
            if ($anketa_id == 0 && self::aliImaDostopDoIzdelovanjaHierarhije()) {
                return true;
            }

            // Imamo anketa ID in smo znotraj ankete
            if ($anketa_id > 0) {

                // Če je modul vključen potem lahko do hierarhije dostopajo samo (admini, oseba, ki je mod vključila in osebe, katerim je bil dodan dostop do urejanja hierarhije)
                if (SurveyInfo::checkSurveyModule('hierarhija', $anketa_id) && ($admin_type == 1 || self::preveriTipHierarhije($anketa_id) < 5)) {

                    return true;

                    // Če modul še ni bil vključen imajo dostop samo uporabniki s predhodnimi pravicami
                } elseif (! SurveyInfo::checkSurveyModule('hierarhija', $anketa_id) && self::aliImaDostopDoIzdelovanjaHierarhije()) {

                    return true;

                }
            }
        }

        return false;
    }

    /**
     * Preverimo, če ima dostop do grajenja hierarhije, dostop omogočimo:
     * AAI uporabnikom
     * 1ka administrator
     * Uporabniku, kateremu je bil dodeljen dostop do ankete
     */
    public static function aliImaDostopDoIzdelovanjaHierarhije()
    {

        global $site_url;

        $strani = [
            'https://www.1ka.si/',
            'http://test.1ka.si/',
            'http://1ka.test/',
            'https://1ka.arnes.si/'
        ];

        // Če je domena prava in če je modul vključen na inštalaciji
        $modul = Common::checkModule('hierarhija');
        if ($modul == '0' || $modul == '1' && ! in_array($site_url, $strani)) {
            return false;
        }

        // Ali je administrator
        global $admin_type;
        if ($admin_type == 0) {
            return true;
        }

        //AAI uporabnik
        if (! empty($_COOKIE['aai']) && $_COOKIE['aai'] == 1) {
            return true;
        }

        // Ima dostop, ker je bil dodan v bazo uporabnikov za dostop do hierarhije
        if (sizeof(sisplet_query("SHOW TABLES LIKE 'srv_hierarhija_dostop'", "array")) == 1) {

            global $global_user_id;
            $dostop = sisplet_query("SELECT dostop FROM srv_hierarhija_dostop WHERE user_id='".$global_user_id."'", "obj");

            if (! empty($dostop) && $dostop->dostop == 1) {
                return true;
            }
        }

        return false;
    }

    public static function preveriTipHierarhije($anketa)
    {
        global $global_user_id;
        $type = null;

        if (is_null($anketa)) {
            return null;
        }

        if (! SurveyInfo::checkSurveyModule('hierarhija', $anketa)) {
            return null;
        }

        // V kolikor je tip hierarhije že v seji
        if (! empty($_SESSION['hierarhija'][$anketa]['type']) && $_SESSION['hierarhija'][$anketa]['type'] > 5) {
            return $_SESSION['hierarhija'][$anketa]['type'];
        }

        $sql = sisplet_query("SELECT type FROM srv_hierarhija_users WHERE user_id='".$global_user_id."' AND anketa_id='".$anketa."'");

        if (! empty($sql) && mysqli_num_rows($sql) > 0) {
            $row = mysqli_fetch_object($sql);
            $type = $row->type;
        }

        $_SESSION['hierarhija'][$anketa]['type'] = $type;

        return $type;
    }

    /**
     * Preverimo, komu so bile poslane kode in vrnemo ustrezno besedilo
     *
     * @param int $anketa
     * @param string $string
     *
     * @return text
     */
    public static function textGledeNaOpcije($anketa, $string = null)
    {
        global $lang;

        if (is_null($string)) {
            return null;
        }

        $poslji_kode = HierarhijaQuery::getOptionsPosljiKode($anketa);

        return $lang[$string.'_'.$poslji_kode];
    }

    /**
     * Izpišemo nivoje hierarhije za našega uporabnika za prikaz linkov
     */
    public static function hierarhijaPrikazNaslovovpriUrlju(
        $anketa,
        $struktura_id,
        $email = null
    ) {
        global $global_user_id;

        $predmet = (new HierarhijaOnlyQuery())->queryStruktura($anketa, null, ' AND str.id='.$struktura_id)->fetch_object();

        // V kolikor pošiljamo email vabilo admin in je aktiviral ali je $email že user_id
        if ($email && is_numeric($email)) {
            $user_id = $email;
        } elseif ($email) {
            $user_id = sisplet_query("SELECT user_id FROM srv_hierarhija_struktura_users WHERE hierarhija_struktura_id='".$predmet->id."'")->fetch_object()->user_id;
        } else {
            $user_id = $global_user_id;
        }
        $veja_hierarhije = (new HierarhijaQuery())->pridobiHierarhijoNavzgor($anketa, null, $user_id);

        $ime_strukture = [];
        foreach ($veja_hierarhije as $key_struktura => $value_sifranta) {

            ### Če ključ trenutnega predmeta ustreza ključi polja za to hierarhijo, potem izpišemo pot za ta predmet (ime, razred, itd...)
            if ($key_struktura == $predmet->id) {
                #### Sortiramo polje od nivoja 1 do 6, ker drugače imamo obratno
                krsort($value_sifranta);
                $st_vnosov = sizeof($value_sifranta);

                $nivo_ime = null;
                foreach ($value_sifranta as $key => $element) {

                    // Tukaj prikaže tudi najvišji nivo, če je potrebno, če želimo najviši nivo izpustu
                    if ($key < $st_vnosov) {
                        $zacas_ime = (new HierarhijaOnlyQuery())->getSamoSifrant($element['id'], true)->fetch_object();
                        $nivo_ime .= $zacas_ime->ime;
                        if ($key != 0) {
                            $nivo_ime .= '  -  ';
                        }
                    }
                }

                // V kolikor nas zanima naslov samo za eno strukturo
                if (! is_null($struktura_id)) {
                    return $nivo_ime;
                }

                $ime_strukture[$key_struktura] = $nivo_ime;
            }
        }

        return $ime_strukture;
    }
}