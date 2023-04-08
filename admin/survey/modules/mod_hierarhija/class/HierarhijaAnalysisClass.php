<?php
/** Kopija razreda class.SurveyMeans povprečja - meanse
 *
 *
 */

define("EXPORT_FOLDER", "admin/survey/SurveyData");

class HierarhijaAnalysis
{
    private $anketa;                                        # id ankete
    private $db_table;                                    # katere tabele uporabljamo
    private $_HEADERS = array();                        # shranimo podatke vseh variabel
    private $struktura = null;                           # ID strukture hierarhije, če želimo prikazati specifične rezultate za posameznega učitelja
    private $struktura_ucitelj = [];                    # Pridobimo vse strukture za posameznega učitelja

    private $headFileName = null;                        # pot do header fajla
    private $dataFileName = null;                        # pot do data fajla
    private $dataFileStatus = null;                        # status data datoteke
    private $SDF = null;                                # class za inkrementalno dodajanje fajlov

    public $variabla1 = array('0' => array('seq' => '0', 'spr' => 'undefined', 'grd' => 'undefined')); # array drugih variable, kamor shranimo spr, grid_id, in sequenco
    public $variabla2 = array('0' => array('seq' => '0', 'spr' => 'undefined', 'grd' => 'undefined')); # array drugih variable, kamor shranimo spr, grid_id, in sequenco

    public $variablesList = null;                        # Seznam vseh variabel nad katerimi lahko izvajamo meanse (zakeširamo)

    public $_CURRENT_STATUS_FILTER = '';        # filter po statusih, privzeto izvažamo 6 in 5

    public $_HAS_TEST_DATA = false;                        # ali anketa vsebuje testne podatke

    public $doValues = true;                            # checkbox Prikaži vrednosti

    private $sessionData;                            # podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...


    public function __construct($sid)
    {
        global $global_user_id;

        if (is_null($sid))
            $sid = $_GET['anketa'];

        // v kolikor ni ID ankete potem nič ne prikazujemo
        if ((int)$sid <= 0) {
            echo 'Invalid Survey ID!';
            exit();
        }

        // ID ankete
        $this->anketa = $sid;

        // Preveri, če ia parameter id strukture
        $this->pregledAnalizeSamoZaEnoStrukturo();

        # polovimo vrsto tabel (aktivne / neaktivne)
        SurveyInfo::getInstance()->SurveyInit($this->anketa);
        $this->db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();

        # Inicializiramo in polovimo nastavitve missing profila
        SurveyStatusProfiles::Init($this->anketa);
        SurveyUserSetting::getInstance()->Init($this->anketa, $global_user_id);

        $this->_CURRENT_STATUS_FILTER = STATUS_FIELD . ' ~ /6|5/';

        SurveyStatusProfiles::Init($this->anketa);
        SurveyMissingProfiles::Init($this->anketa, $global_user_id);
        SurveyConditionProfiles::Init($this->anketa, $global_user_id);
        SurveyZankaProfiles::Init($this->anketa, $global_user_id);
        SurveyTimeProfiles::Init($this->anketa, $global_user_id);
        SurveyVariablesProfiles::Init($this->anketa);
        SurveyDataSettingProfiles:: Init($this->anketa);


        // Poskrbimo za datoteko s podatki
        $SDF = SurveyDataFile::get_instance();
        $SDF->init($this->anketa);           
        $SDF->prepareFiles();  

        $this->headFileName = $SDF->getHeaderFileName();
        $this->dataFileName = $SDF->getDataFileName();
        $this->dataFileStatus = $SDF->getStatus();


        // preberemo nastavitve iz baze (prej v sessionu)
        SurveyUserSession::Init($this->anketa);
        // V kolikor ni shranjeno polje v bazi potem pobrišemo trenutno, kar je nastavljeno, da se izognemo napakam, ki bi se pojavile v nadaljevanju
        if (!empty(SurveyUserSession::getData()) && !is_array(SurveyUserSession::getData()))
            SurveyUserSession::delete();

        $this->sessionData = SurveyUserSession::getData();

        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
            if (!empty($_GET['s'])) {
                $this->sessionData['means']['struktura'] = (int)$_GET['s'];
            } elseif (!empty($this->sessionData['means']['struktura'])) {
                unset($this->sessionData['means']['struktura']);
            }

            SurveyUserSession::saveData($this->sessionData);
        }

        // V kolikor preverjamo anketo za učitelja z že izbranimi parametri potem pobrišemo nastavitve iz baze
        if (!is_null($this->struktura))
            $this->sessionData['means']['filterHierarhija'] = array();


        if ($this->dataFileStatus == FILE_STATUS_NO_DATA || $this->dataFileStatus == FILE_STATUS_NO_FILE || $this->dataFileStatus == FILE_STATUS_SRV_DELETED) {
            Common::noDataAlert();
            exit();
        }

        if ($this->headFileName !== null && $this->headFileName != '') {
            $this->_HEADERS = unserialize(file_get_contents($this->headFileName));
        }

        // Kdar je variable2 prazna in podatki še niso shranjeni v bazi potem prikažemo vse odgovore;
        if (!is_null($this->struktura) || (empty($this->sessionData['means']['means_variables']['variabla2']) || sizeof($this->sessionData['means']['means_variables']['variabla2']) < 1) && !empty($this->_HEADERS)) {
            $polje = array();
            if (!empty($this->getVariableList(2))) {
                foreach ($this->getVariableList(2) as $vprasanje) {
                    if ($vprasanje['canChoose']) {
                        $polje[] = [
                            'seq' => $vprasanje['sequence'],
                            'spr' => $vprasanje['spr_id'],
                            'grd' => 'undefined'
                        ];
                    }
                }
            }

            $this->sessionData['means']['means_variables']['variabla2'] = $polje;

            if (!empty($this->struktura))
                $this->variabla2 = $polje;


            // dodan js, da osveži vse elemente in vse izbrane spremenljivke shrani v bazo
            echo '<script>
                        window.onload = function () {
                            change_hierarhy_means();
                         };
                     </script>';
        }


        # nastavimo vse filtre
//        $this->setUpFilter();

        # nastavimo uporabniške nastavitve
        $this->readUserSettings();

        if (!empty($this->struktura)) {
            // posodobimo vse filtre, ki so bili izbrani
            echo '<script>
                        window.onload = function () {
                            posodobil_filter_analiz();
                         };
                      </script>';
        }

    }


    /**
     * Pogledamo, če imamo id strukture in potem prikažemo rezulatate samo teh reševanj
     */
    private function pregledAnalizeSamoZaEnoStrukturo()
    {
        if (is_null($_GET['s']) || is_string((int)$_GET['s']))
            return null;

        $this->struktura = (int)$_GET['s'];
    }


    private function readUserSettings()
    {
        $sdsp = SurveyDataSettingProfiles:: getSetting();
        $this->doValues = $sdsp['doValues'] == '1' ? true : false;
    }


    public function ajax()
    {
        #nastavimo variable če so postane
        $this->setPostVars();

        # izvedemo akcijo
        switch ($_GET['a']) {
            case 'changeDropdown':
                $this->displayDropdowns();
                break;
            case 'change':
                $this->displayData();
                break;
            case 'add_new_variable':
                $this->addNewVariable();
                break;
            case 'changeMeansSubSetting':
                $this->changeMeansSubSetting();
                break;
            case 'changeMeansShowChart':
                $this->changeMeansShowChart();
                break;
            case 'posodobi-ucitelja':
                $this->posodobiPodatkeZaUcitelja();
                break;
             case 'posodobi-izbran-predmet':
                 $this->posodobiIzbranPredmet();
                 break;
            case 'posodobi-seznam-za-ucitelje':
                $this->posodobiSeznamFiltrovUcitelja();
                breake;
            case 'pobrisi-filter':
                $this->pobrisiFilterUciteljevAliHierarhije();
                break;
            default:
                print_r("<pre>");
                print_r($_GET);
                print_r($_POST);
                break;
        }

    }

    function Display()
    {
        global $lang;
        
        $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : null);

        // meni za izbiranlje za filtre
        $this->displayHierarhijaAliUcitelji();

        # ali imamo testne podatke
        if ($this->_HAS_TEST_DATA) {
            # izrišemo bar za testne podatke
            $SSH = new SurveyStaticHtml($this->anketa);
            $SSH->displayTestDataBar(true);
        }

        # preberemo prednastavljene variable iz seje, če obstajajo
        # v koolikor gledamo za specifični  ID, potem prikažemo trenutne podatke
        if (is_null($_GET['s']))
            $this->presetVariables();

        // v kolikor gre za specifično anketo potem naredimo skrito polje z ID-jem strukture tega učitelja
        if (!is_null($this->struktura))
            echo '<input type="hidden" id="id-strukture" value="' . $this->struktura . '" />';


//        $this->DisplayLinks();
//        $this->DisplayFilters();

        // ruleta za izbiro po učiteljih
        if ($hierarhija_type < 5) {
            $prikazi_fitre = is_array($this->sessionData['means']['strukturaUcitelj']);
            $prikazi_rezultate = (isset($this->sessionData['means']['uciteljFilter']) && $this->sessionData['means']['uciteljFilter'] == 'predmeti');

            echo '<div id="ucitelji" style="display:' . (($prikazi_fitre && !$prikazi_rezultate) ? 'block' : 'none') . ';">';
              // Prikažemo seznam učiteljev za agregirane analize
              $this->displayDropdownSeznamUciteljev();
            echo '</div>';

          echo '<div id="predmeti-in-ucitelji" style="display:' . (($prikazi_fitre && $prikazi_rezultate) ? 'block' : 'none') . ';">';
            $this->displayDropdownSeznamUciteljevZaSpecificniPredmet();
          echo '</div>';
        }


        echo '<div id="div_means_show_filter">';
//            echo '<div class="znak plus"><i class="fa fa-lg fa-plus-circle" aria-hidden="true"></i> Prikaži filtre</div>';
//            echo '<div class="znak minus"><i class="fa fa-lg fa-minus-circle" aria-hidden="true"></i> Skrij filtre</div>';
        echo '<div id="div_means_dropdowns">';
        $this->displayDropdowns();
        echo '</div>';

        // Prikažemo izvoz, če gre za učitelja
        if ($_GET['a'] == 'hierarhija') {
            $href_pdf = makeEncodedIzvozUrlString('izvoz.php?b=export&m=hierarhija_pdf_izpis&anketa=' . $this->anketa);
            $href_rtf = 'index.php?anketa=' . $this->anketa . '&a=hierarhija&m=analize&r=custom';

            echo '<div class="izvozi ucitelj">';
                echo '<a href="#" onClick="printElement(\'Analize\'); return false;"  title="' . $lang['PRN_Izpis'] . '" class="ikone"><span class="hover_export_icon"><span class="faicon print icon-grey_dark_link"></span></span>' . $lang['srv_export_hover_print'] . '</a>';
                echo '<a href="'.$href_pdf.'" id="meansDoPdf" target="_blank" class="ikone"><span class="hover_export_icon"><span class="sprites pdf_large"></span></span>' . $lang['srv_export_hover_pdf'] . '</a>';
                echo '<a href="'.$href_rtf.'" id="meansDoRtf" target="_blank" class="ikone"><span class="hover_export_icon"><span class="sprites rtf_large"></span></span>' . $lang['srv_export_hover_rtf'] . '</a>';
            echo '</div>';

        }
        echo '</div>';

        echo '<div id="div_means_data">';
        $this->displayData();
        echo '</div>'; #id="div_means_data"

    }


    /**
     * Izpišemo opcijo za izbiro ali filtri hierarhije ali filtri po učiteljih
     *
     * @return html
     */
    public function displayHierarhijaAliUcitelji()
    {
        $hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : null);
        global $lang;

        if ($hierarhija_type > 4)
            return null;

            echo '<div id="hierarhija-specificni-ucitelj"><h2>';
            $seja = SurveyUserSession::getData(['means']['imeHierarhije']);
             if(!empty($this->sessionData['means']['imeHierarhije']))
                echo $this->sessionData['means']['imeHierarhije'];
            echo '</h2></div>';


        // V kolikor gre za filtre po hierarhiji ali za filtre samo po učiteljih
        echo '<div id="analize-nastavitve">';
            echo '<div class="filtri">';
                echo '<b>Filtriranje po: </b>';
                echo '<input type="radio" name="hierarhija-ucitelj" id="hierarhija-radio-filter" value="filtri" onclick="posodobiPrikazHierarhije(\'filtri\')" ' . (!isset($this->sessionData['means']['strukturaUcitelj']) ? 'checked="checked"' : null) . '/> <label for="hierarhija-radio-filter">hierarhiji</label>';
                echo '<input type="radio" name="hierarhija-ucitelj" id="hierarhija-radio-ucitelj" value="ucitelji" onclick="posodobiPrikazHierarhije(\'ucitelji\')" ' . (is_array($this->sessionData['means']['strukturaUcitelj']) ? 'checked="checked"' : null) . '/> <label for="hierarhija-radio-ucitelj">učiteljih</label>';

                echo '<div class="filtri-ucitelji" style="display:' . (is_array($this->sessionData['means']['strukturaUcitelj']) ? 'block' : 'none') . ';">';
                  echo '<b>Rezultati bodo prikazani: </b>';
                  echo '<input type="radio" name="ucitelj-filter" id="ucitelj-filter-agregirano" value="agregirano" onclick="posodobiPrikazFiltraPoUciteljih(\'agregirano\')" ' . (!isset($this->sessionData['means']['uciteljFilter']) || $this->sessionData['means']['uciteljFilter'] == 'agregirano' ? 'checked="checked"' : null) . '/> <label for="ucitelj-filter-agregirano">agregirano</label>';
                  echo '<input type="radio" name="ucitelj-filter" id="ucitelj-filter-predmeti" value="predmeti" onclick="posodobiPrikazFiltraPoUciteljih(\'predmeti\')" ' . ($this->sessionData['means']['uciteljFilter'] == 'predmeti' ? 'checked="checked"' : null) . '/> <label for="ucitelj-filter-predmeti">po predmetih</label>';
                echo '</div>';

            echo '</div>';

            echo '<div class="izvoz">';
                echo '<b>Poročila po meri: </b>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&a=hierarhija_superadmin&m=analize&r=custom&t=pdf" class="link-ikona pdf"><span class="faicon pdf black very_large" aria-hidden="true"></span></a>';
                echo '<a href="index.php?anketa=' . $this->anketa . '&a=hierarhija_superadmin&m=analize&r=custom&t=word" class="link-ikona word"><span class="faicon rtf black very_large" aria-hidden="true"></span></a>';
                if($_GET['error'] == 'invalid') {
                    echo '<div class="error-display">'.$lang['srv_hierarchy_analysis_error_invalid_data'].'</div>';
                }
            echo '</div>';
        echo '</div>';
    }

    /**
     * Izriše seznam vseh učiteljev, ki so v strukturi
     *
     * @return html
     */
    private function displayDropdownSeznamUciteljev()
    {
        $vsi_uporabniki_upraviceni_do_evalvacije = (new \Hierarhija\Model\HierarhijaOnlyQuery())->queryStrukturaUsers($this->anketa, ' AND hs.level=(SELECT MAX(level) FROM srv_hierarhija_struktura WHERE anketa_id=' . $this->anketa . ') GROUP BY users.id');

        echo '<label>Izberite ustreznega učitelja:</label>';
        echo '<select name="filter-po-ucitelju" 
                      id="filter-po-ucitelju"
                      class="filter-ucitelji"
                      data-placeholder="' . $lang['srv_hierarchy_label_filter_teacher'] . '"
                      onchange="posodobi_izbranega_ucitelja()"
                             >';
        echo '<option value="" selected="selected">---</option>';
        while ($uporabnik = $vsi_uporabniki_upraviceni_do_evalvacije->fetch_object()) {

            $izpis = $uporabnik->email;
            if (!empty($uporabnik->name) && $uporabnik->name != $uporabnik->email || !empty($uporabnik->surname) && $uporabnik->surname != $uporabnik->email)
                $izpis .= ' (' . $uporabnik->name . ' ' . $uporabnik->surname . ')';

            echo '<option value="' . $uporabnik->user_id . '">' . $izpis . '</option>';
        }
        echo '</select>';
    }

  /**
   * Izriše seznam vseh učiteljev in strukture - za 1 predmet
   *
   * @return html
   */
  private function displayDropdownSeznamUciteljevZaSpecificniPredmet()
  {
    $struktura_uporabnikov = (new \Hierarhija\Model\HierarhijaOnlyQuery())->queryStrukturaUsers($this->anketa, ' AND hs.level=(SELECT MAX(level) FROM srv_hierarhija_struktura WHERE anketa_id=' . $this->anketa . ')');

    echo '<label>Izberite ustrezen predmet:</label>';
    echo '<select name="filter-po-ucitelju-in-predmetu" 
                      id="filter-po-ucitelju-in-predmetu"
                      class="filter-ucitelji"
                      data-placeholder="' . $lang['srv_hierarchy_label_filter_teacher'] . '"
                      onchange="posodobi_izbran_predmet()"
                             >';
    echo '<option value="" selected="selected">---</option>';
    while ($uporabnik = $struktura_uporabnikov->fetch_object()) {

      $izpis = \Hierarhija\HierarhijaHelper::hierarhijaPrikazNaslovovpriUrlju($this->anketa, $uporabnik->id, $uporabnik->user_id);
      $izpis .= '&nbsp; &nbsp; &nbsp; ('.$uporabnik->email.')';

      echo '<option value="' . $uporabnik->id . '">' . $izpis . '</option>';
    }
    echo '</select>';
  }

    public function displayDropdowns()
    {
        global $lang;
        $variables1 = $this->getVariableList(1);
        $variables2 = $this->getVariableList(2);


        // V kolikor je struktura 0 in post request je notri ID strukture potem zapišemo v globalno spremenljivko
        if (is_null($this->struktura) && !empty($_POST['strukturaId']))
            $this->struktura = (int)$_POST['strukturaId'];

        // Pridobimo strukturo za specifično anketo
        $imena_sifrantov_ucitelja = array();
        if (!is_null($this->struktura)) {
            $this->sessionData['means']['filterHierarhija'] = array();
            $imena_sifrantov_ucitelja = $this->pridobiStrukturoZaUcitelja();
        }

        ### Levi meni za prikaz vloge
        echo '<div id="meansLeftDropdowns" style="display:none;">';
        if ((int)$this->variabla1['0']['seq'] > 0) {
            echo '<span class="pointer space_means_new" >&nbsp;</span>';
        }
        echo $lang['srv_means_label1'];
        echo '<br />';

        #iz header datoteke preberemo spremenljivke
        #js: $("#means_variable_1, #means_variable_2").live('click', function() {})
        if (count($this->variabla1) > 0) {
            $br = null;

            foreach ($this->variabla1 AS $_key => $variabla1) {
                echo $_br;
                echo '<span id="v1_' . $_key . '">';

                echo '<select name="means_variable_1" id="means_variable_1" onchange="change_hierarhy_means(); return false;" autocomplete="off">';
//
//                # Tukaj vedno izberemo variablo vloga
                if ($variabla1['seq'] == null || $variabla1['seq'] == 0) {
                    echo '<option value="0" selected="selected" >' . $lang['srv_means_izberi_prvo'] . '</option>';
                }
                foreach ($variables1 as $variable) {
                    echo '<option value="' . $variable['sequence'] . '" spr_id="' . $variable['spr_id'] . '" '
                        . (isset($variable['grd_id']) ? ' grd_id="' . $variable['grd_id'] . '" ' : '')
                        . ' selected="selected"> '
                        . ((int)$variable['sub'] == 0 ? '' : ((int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;'))
                        . $variable['variableNaslov'] . '</option>';

                }

                echo '</select>';
                if (count($this->variabla1) > 1) {
                    echo '<span class="pointer" id="means_remove" onclick="hierarhy_means_remove_variable(this);"><span class="faicon delete_circle icon-orange_link" title=""></span></span>';
                } else {
                    #echo '<span class="space_means_new">&nbsp;</span>';
                }

                $_br = '<br/><span class="space_means_new">&nbsp;</span>';
                echo '</span>';
            }
            $_br = null;
        }

        echo '</div>';

        ### Prikaz elementov hierarhije in nivojev, ki so že zaklenjeni
        echo '<div id="meansLeftDropdowns" class="hierarhija-filtri-levi" ' . ((!empty($this->struktura) || is_array($this->sessionData['means']['struktura']) || isset($this->sessionData['means']['strukturaUcitelj'])) ? ' style="display:none;"' : '') . '>';
        echo $lang['srv_hierarchy_label_analyse'];
        echo '<br />';

        #### Pridobimo strukturo hierarhije
        $struktura_hierarhije = $this->hierarhijaUporabnika(); //tukaj dobimo samo polja, do katerih ima uporabnik dovoljenje

        #### V kolikor je učitelj oz. poljuben uporabnik na določeni ravni potem preverimo max št nivojev
        $max_st_nivojev = sisplet_query("SELECT MAX(level) AS max
                                            FROM
                                              srv_hierarhija_ravni AS r
                                            LEFT JOIN
                                              srv_hierarhija_sifranti AS s
                                            ON
                                              s.hierarhija_ravni_id = r.id
                                            WHERE
                                             r.anketa_id = '" . $this->anketa . "'            
                                            ORDER BY level", "obj")->max;

        // filter za vsak nivo shranimo v polje in v kolikor se filter ponovi potem izpišemo vedno samo enega
        foreach ($this->_HEADERS as $h_key => $header) {
            preg_match('/^(?:nivo)([0-9]+)/', $header['variable'], $match);
            if ((int)$h_key > 0 && sizeof($match) > 0) {
                echo '<div class="hierarhija-filter">';
                echo '<label>';
                echo $header['naslov'] . ': ';
                echo '</label>';
                echo '</div>';

                // Prikaz chosen za vse šifrante, ki jih imamo
                echo '<select name="' . $header['variable'] . '"
                              id="' . $header['variable'] . '"
                              class="filter-analize"
                              data-placeholder="' . $lang['srv_hierarchy_label_filter'] . '"
                              onchange="posodobil_filter_analiz()"
                              ' . ((false && $struktura_hierarhije != 'admin' && $match[1] == 1) ? null : "multiple") . '                        
                              >';

                foreach ($header['options'] as $v_key => $value) {
                    $select = null;

                    // Označi chosen "select" za spremenljivke, ki smo jih izbrali iz filtrov
                    if (
                        !empty($this->sessionData['means']['filterHierarhija'][$header['variable']]) &&
                        sizeof($this->sessionData['means']['filterHierarhija'][$header['variable']]) > 0 &&
                        in_array($v_key, $this->sessionData['means']['filterHierarhija'][$header['variable']])
                    ) {
                        $select = 'selected="selected"';
                    }


                    // Če je izbrana struktura
                    if (!is_null($this->struktura) && sizeof($imena_sifrantov_ucitelja) > 0 && in_array($value, $imena_sifrantov_ucitelja)) {
                        $this->sessionData['means']['filterHierarhija'][$header['variable']][] = $v_key;
                        $select = 'selected="selected"';
                    }

                    #### V Kolikor ni admin, potem prikažemo samo te stvari, ki jih lahk on izbere
                    $st_nivoja = substr($header['variable'], 4);
                    $polje_uporabnikove_hierarhije = $struktura_hierarhije[$st_nivoja];

                    #### Če gre za administratorja, potem prikažemo vse šifrante
                    if ($struktura_hierarhije == 'admin') {
                        echo '<option value="' . $v_key . '" ' . $select . '>' . $value . '</option>';

                        #### Za 1. in 2. nivo ter zadnji nivo prikažemo samo šifrante na katerih je izbran sledeči uporabnik
                    } elseif (
                        $struktura_hierarhije != 'admin' &&
                        !empty($polje_uporabnikove_hierarhije) &&
                        in_array($v_key, $polje_uporabnikove_hierarhije) &&
                        ($st_nivoja < 3 || $max_st_nivojev == $st_nivoja)
                    ) {
                        ##### Če imam samo en podatek v polju potem tega že privzeto izberemo
                        if (sizeof($polje_uporabnikove_hierarhije) == 1) {

                            // V kolikor smo zbrisali zadnji nivo potem ga ponovno dodamo in shranimo sejo
                            if (!in_array($v_key, $this->sessionData['means']['filterHierarhija'][$header['variable']])) {
                                $this->sessionData['means']['filterHierarhija'][$header['variable']] = [$v_key];
                                SurveyUserSession::saveData($this->sessionData);
                            }

                            $select = 'selected="selected"';
                        }

                        if (empty($this->sessionData['means']['filterHierarhija'][$header['variable']]) || in_array($v_key, $this->sessionData['means']['filterHierarhija'][$header['variable']]))
                            echo '<option value="' . $v_key . '" ' . $select . '>' . $value . '</option>';

                        #### Vse umesne šifrante prikažemo vse
                    } elseif ($st_nivoja > 2 && $max_st_nivojev != $st_nivoja) {

                        if (in_array($v_key, $struktura_hierarhije[$match[1]]))
                            echo '<option value="' . $v_key . '" ' . $select . '>' . $value . '</option>';
                    }
                }
                echo '</select>';
                echo '<br />';
            }
        }

        echo '<script>';
        echo '$(".filter-analize").chosen();';
        echo '</script>';

        echo '</div>';


#		echo '<div id="meansImgHolder">';
#		if ($this->isSelectedBothVariables()) {
#			echo '<img src="../images/rotate.png" alt="rotate" onclick="change_means(\'rotate\');return false;" />';
#		} else {
#			echo '<img src="../images/rotate_dis.png" alt="rotate" />';
#		}
#		echo '</div>';

        echo '<div id="meansRightDropdowns" ' . (!empty($this->struktura) ? ' style="display:none;"' : '') . '>';
        if ((int)$this->variabla1['0']['seq'] > 0) {
            echo '<span class="pointer space_means_new" >&nbsp;</span>';
        }
        echo $lang['srv_means_label2'];
        echo '<br />';


        # za vsako novo spremenljivko 2 nardimo svoj select
        if (count($this->variabla2) > 0) {
            if ((int)$this->variabla1['0']['seq'] > 0) {
                echo '<span class="pointer" id="means_add_new" onclick="hierarhy_means_add_new_variable(\'2\');"><span class="faicon add small icon-as_link" title="' . '"></span></span>';
            }

            foreach ($this->variabla2 AS $_key => $variabla2) {
                echo $_br;
                echo '<span id="v2_' . $_key . '">';
                echo '<select name="means_variable_2" id="means_variable_2" onchange="change_hierarhy_means(); return false;" autocomplete="off">';

                # ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
                if ((int)$this->variabla1['0']['seq'] == 0) {
                    echo '<option value="0" selected="selected" >' . $lang['srv_means_najprej_prvo'] . '</option>';
                } else {
                    # če druga variabla ni izbrana dodamo tekst za izbiro druge variable
                    if ($variabla2['seq'] == null || $variabla2['seq'] == 0) {
                        echo '<option value="0" selected="selected" >' . $lang['srv_means_izberi_drugo'] . '</option>';
                    }
                }

                foreach ($variables2 as $variable) {
                    echo '<option value="' . $variable['sequence'] . '" spr_id="' . $variable['spr_id'] . '" '
                        . (isset($variable['grd_id']) ? ' grd_id="' . $variable['grd_id'] . '" ' : '')
                        . (((int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
                        . ($variabla2['seq'] > 0 && $variabla2['seq'] == $variable['sequence'] ? ' selected="selected" ' : '')
                        . '> '
                        . ((int)$variable['sub'] == 0 ? '' : ((int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;'))
                        . $variable['variableNaslov'] . '</option>';

                }
                echo '</select>';
                if (count($this->variabla2) > 1) {
                    echo '<span class="pointer" id="means_remove" onclick="hierarhy_means_remove_variable(this);"><span class="faicon delete_circle icon-orange_link" title=""></span></span>';
                } else {
                    echo '<span class="space_means_new">&nbsp;</span>';
                }

                $_br = '<br/><span class="space_means_new">&nbsp;</span>';
                echo '</span>';
            }
        }
        echo '</div>';

        echo '<span id="meansSubSetting" class="floatLeft spaceLeft">';
        if (count($this->variabla2) > 1) {
            ### Skrijemo možnost preklopa odgovorov v skupno tabelo
            echo '<div style="display:none;">';
            echo '<label><input id="chkMeansSeperate" type="checkbox" onchange="changeHierarhyMeansSubSetting();" ' . ($this->sessionData['means']['meansSeperateTables'] == true ? ' checked="checked"' : '') . '> ' . $lang['srv_means_setting_1'] . '</label>';
            echo '</div>';
            echo '<div class="vprasanja prikazi" onclick="tooglePrikazVprasanja(1)"><i class="fa fa-lg fa-plus-circle" aria-hidden="true"></i> ' . $lang['srv_hierarchy_analysis_show_questions'] . '</div>';
            echo '<div class="vprasanja skrij" onclick="tooglePrikazVprasanja(0)" style="display: none;"><i class="fa fa-lg fa-minus-circle" aria-hidden="true"></i> ' . $lang['srv_hierarchy_analysis_hide_questions'] . '</div>';
//            echo '<br /><span id="spanMeansJoinPercentage"' . ($this->sessionData['means']['meansSeperateTables'] != true ? '' : ' class="displayNone"') . '><label><input id="chkMeansJoinPercentage" type="checkbox" onchange="changeHierarhyMeansSubSetting();" ' . ($this->sessionData['means']['meansJoinPercentage'] == true ? ' checked="checked"' : '') . '> ' . $lang['srv_means_setting_2'] . '</label></span>';
        }
        echo '<div class="prikazi-graf"><input id="showChart" type="checkbox" onchange="showTableChart(\'hierarhy_mean\');" ' . ($this->sessionData['mean_charts']['showChart'] == true ? ' checked="checked"' : '') . '> <label for="showChart">' . $lang['srv_show_charts'] . '</label></div>';
        echo '</span>';
        echo '</span>';

        echo '<br class="clr"/>';


        // Ikone za izvoz (so tukaj da se refreshajo ob ajax klicu)
        $this->displayExport();
    }


    /**
     * Pridobimo hierarhijo uporabnika, ki je prijavljen
     *
     * @return (array) $hierarhija
     */
    public function hierarhijaUporabnika()
    {
        # Podatki za pregled nivojev hierarhije
        $hierarhija_type = \Hierarhija\HierarhijaHelper::preveriTipHierarhije($this->anketa);

        if (empty($hierarhija_type))
            return false;

        if ($hierarhija_type > 4) {
            $struktura_user = (new \Hierarhija\Model\HierarhijaQuery())->pridobiHierarhijoNavzgor($this->anketa, true);

            $hierarhija = array();

            foreach ($struktura_user as $key => $struktura) {
                foreach ($struktura as $row) {
                    $nivo = trim($row['nivo'], 'nivo');

                    ### Gremo skozi hierarhijo in v kolikor ima uporabnik več izvedenih anket (za različne letnike) potem vpišemo vse unikatne  nivoje hierarhije;
                    if (!in_array($nivo, $hierarhija) &&
                        (is_array($hierarhija[$nivo]) &&
                            !in_array($row['st_odgovora'], $hierarhija[$nivo]) || empty($hierarhija[$nivo]))
                    ) {
                        $hierarhija[$nivo][] = $row['st_odgovora'];
                    }
//                    else {
//
////                        $st_last = 0;
//                        ### V kolikor obstaja element potem vedno prepišemo zadnjega vnešenega
////                        if (isset($hierarhija[$nivo]) && sizeof($hierarhija[$nivo]) > 0)
////                            $st_last = sizeof($hierarhija[$nivo]) - 1;
//
////                        $hierarhija[$nivo][$st_last] = $row['st_odgovora']; //če se je nivo ponovil ga vedno vpišemo na prvo mesto
//
//                    }

                }
            }

            return $hierarhija;
        }

        return 'admin';
    }


    /**
     * Pridobimo strukturo za učitelja za specifično anketo in vrnemo imena vseh šifrantov, ker jih bomo uporabili za filtriranje
     *
     * @return (array) $imena_sifrantov
     */
    public function pridobiStrukturoZaUcitelja()
    {
        $struktura = \Hierarhija\Model\HierarhijaQuery::posodobiSifranteVrednostiGledeNaTrenutenIdStrukture($this->struktura);
        $imena_sifrantov = array();
        foreach ($struktura as $row) {
            $sql = sisplet_query("SELECT ime FROM srv_hierarhija_sifranti WHERE id='" . $row['id_sifranta'] . "'", "obj");
            $imena_sifrantov[] = $sql->ime;
        }

        return $imena_sifrantov;
    }

    /**
     * Osvežimo vse podatke, ki so potrebni za pridobitev pdoatkov
     *
     * @return html
     */
    public function displayData($filter_hierarhija = null)
    {
        global $site_path;

        $br = '';
        $means = array();

        if (!file_exists($site_path . EXPORT_FOLDER . '/export_data_' . $this->anketa . '.dat')) {
            echo 'Ni odgovorov';
            die();
        }


        # če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
        SurveyTimeProfiles::printIsDefaultProfile(false);

        # če imamo filter ifov ga izpišemo
        SurveyConditionProfiles::getConditionString();

        # če imamo filter spremenljivk ga izpišemo
        SurveyVariablesProfiles::getProfileString($doNewLine, true);

        # če imamo rekodiranje
        $SR = new SurveyRecoding($this->anketa);
        $SR->getProfileString();

        if ($this->getSelectedVariables(1) !== null && $this->getSelectedVariables(2) !== null) {
            $variables1 = $this->getSelectedVariables(2);
            $variables2 = $this->getSelectedVariables(1);

            $c1 = 0;
            $c2 = 0;

            # odvisno ok checkboxa prikazujemo druge variable v isti tabeli ali v svoji
            if ($this->sessionData['means']['meansSeperateTables'] == true || !isset($this->sessionData['means']['meansSeperateTables'])) {
                #prikazujemo ločeno
                if (is_array($variables2) && count($variables2) > 0) {
                    foreach ($variables2 AS $v_second) {
                        if (is_array($variables1) && count($variables1) > 0) {
                            foreach ($variables1 AS $v_first) {
                                $_means = $this->createMeans($v_first, $v_second);
                                if ($_means != null) {
                                    $means[$c1][0] = $_means;
                                }
                                $c1++;
                            }
                        }
                    }
                }
            } else {
                #prikazujemo v isti tabeli
                if (is_array($variables2) && count($variables2) > 0) {
                    foreach ($variables2 AS $v_second) {
                        if (is_array($variables1) && count($variables1) > 0) {
                            foreach ($variables1 AS $v_first) {
                                $_means = $this->createMeans($v_first, $v_second);
                                if ($_means != null) {
                                    $means[$c1][$c2] = $_means;
                                }
                                $c2++;
                            }
                        }
                        $c1++;
                        $c2 = 0;
                    }
                }
            }

            //ddd($means);
            if (is_array($means) && count($means) > 0) {
                $counter = 0;
                foreach ($means AS $mean_sub_grup) {
                    echo($br);
                    $this->displayMeansTable($mean_sub_grup);
                    $br = '<br />';

                    // Zvezdica za vkljucitev v porocilo
                    $spr2 = $mean_sub_grup[0]['v1']['seq'] . '-' . $mean_sub_grup[0]['v1']['spr'] . '-' . $mean_sub_grup[0]['v1']['grd'];
                    $spr1 = $mean_sub_grup[0]['v2']['seq'] . '-' . $mean_sub_grup[0]['v2']['spr'] . '-' . $mean_sub_grup[0]['v2']['grd'];
                    SurveyAnalysis::Init($this->anketa);
                    SurveyAnalysis::addCustomReportElement($type = 6, $sub_type = 0, $spr1, $spr2);

                    // Izrisemo graf za tabelo - zaenkrat samo admin
                    if ($this->sessionData['mean_charts']['showChart'] && $_GET['m'] != 'analysis_creport') {
                        $tableChart = new SurveyTableChart($this->anketa, $this, 'mean', $counter);
                        $tableChart->display();
                    }

                    $counter++;
                }
            }

        } else {
            # dropdowni niso izbrani
        }


        if ($this->aliImaPravicoDoPrikazaOdprtihOdgovorov()) {
            echo '<div id="div_odprto_vprasanje">';

            foreach ($this->_HEADERS AS $skey => $spremenljivka) {
                if ($spremenljivka['tip'] == 21) {
                    if ($spremenljivka['cnt_all'] == 1) {
                        // če je enodimenzionalna prikažemo kot frekvence
                        // predvsem zaradi vprašanj tipa: language, email...

                        $this->izrisiOdprteOdgovoreZaUcitelja($skey, $spremenljivka);
//                        $this->sumTextVertical($skey, 'sums');
//
                    } else {
                        SurveyAnalysis::sumMultiText($skey, 'sums');
                    }
                }
            }

            echo '</div>';
        }
    }

    /**
     * Preverimo, če je res učitelj in če ima pravico do odprtih odgovorov
     *
     * @return boolean
     */
    public function aliImaPravicoDoPrikazaOdprtihOdgovorov()
    {
        global $global_user_id;

        // Pridobimo max število nivojev
        $max_st = (new \Hierarhija\Model\HierarhijaOnlyQuery())->getRavni($this->anketa, 'MAX(level) AS max_level')->fetch_object()->max_level;

        $struktura = sisplet_query("SELECT s.level AS level FROM srv_hierarhija_struktura_users AS u LEFT JOIN srv_hierarhija_struktura AS s ON s.id=u.hierarhija_struktura_id WHERE s.anketa_id='" . $this->anketa . "' AND u.user_id='" . $global_user_id . "'", "obj");

        if (is_array($struktura) && $struktura[0]->level == $max_st || $struktura->level == $max_st)
            return true;

        return false;
    }

    /*
    * Pridobimo strukture za učitelja na vseh nivojih
    */
    public function posodobiPodatkeZaUcitelja($user_id = null)
    {
        if (empty($_POST['user_id']) && is_null($user_id))
            return null;

        if (is_null($user_id))
            $user_id = $_POST['user_id'];

        // Pridobimo uporabnika za vse njegove predmete
        $vsi_predmeti_uporabnika = (new \Hierarhija\Model\HierarhijaOnlyQuery())->queryStrukturaUsers($this->anketa, ' AND hs.level=(SELECT MAX(level) FROM srv_hierarhija_struktura WHERE anketa_id=' . $this->anketa . ') AND users.id="' . $user_id . '"');

        $struktura_ids = [];
        while ($row = $vsi_predmeti_uporabnika->fetch_object()) {
            $struktura_ids[] = $row->id;
        }

        // Pridobimo vse šifrante od učitelja navzgor - celo strukturo
        $this->struktura_ucitelj = [];
        foreach ($struktura_ids as $struktura_id) {
            $struktura_baza = \Hierarhija\Model\HierarhijaQuery::posodobiSifranteVrednostiGledeNaTrenutenIdStrukture($struktura_id);

            foreach ($struktura_baza as $key => $row) {
                // $struktura_id - je ID strukture na kateremse nahaja učitelj
                // $key - je level na katerem je šifrant
                // $row['id_sifranta'] - je ID sifranta kateri je na tem nivoju
                $this->struktura_ucitelj[$struktura_id][$key]['sifrant'] = $row['id_sifranta'];

                // Šifrant preverimo glede na vrstni red
                $this->struktura_ucitelj[$struktura_id][$key]['stevilka'] = $this->pridobiStZaSpecificniSifrant($row['hierarhija_ravni_id'], $row['id_sifranta']);
            }
        }

        // Filtriranje po učitelju
        if (isset($this->struktura_ucitelj) && sizeof($this->struktura_ucitelj) > 0)
            $this->sessionData['means']['strukturaUcitelj'] = $this->struktura_ucitelj;

        // Shranimo spremenjene nastavitve v bazo
        SurveyUserSession::saveData($this->sessionData);

        return $this->struktura_ucitelj;
    }

  /*
* Pridobimo strukture za učitelja na vseh nivojih
*/
  public function posodobiIzbranPredmet()
  {
    if (empty($_POST['strukutra_id']))
      return null;

    $struktura_id = $_POST['strukutra_id'];

      // Pridobimo vse šifrante od učitelja navzgor - celo strukturo
    $this->struktura_ucitelj = [];

      $struktura_baza = \Hierarhija\Model\HierarhijaQuery::posodobiSifranteVrednostiGledeNaTrenutenIdStrukture($struktura_id);


      foreach ($struktura_baza as $key => $row) {
        // $struktura_id - je ID strukture na kateremse nahaja učitelj
        // $key - je level na katerem je šifrant
        // $row['id_sifranta'] - je ID sifranta kateri je na tem nivoju
        $this->struktura_ucitelj[$struktura_id][$key]['sifrant'] = $row['id_sifranta'];

        // Šifrant preverimo glede na vrstni red
        $this->struktura_ucitelj[$struktura_id][$key]['stevilka'] = $this->pridobiStZaSpecificniSifrant($row['hierarhija_ravni_id'], $row['id_sifranta']);
      }


    // Filtriranje po učitelju
    if (isset($this->struktura_ucitelj) && sizeof($this->struktura_ucitelj) > 0){
      $this->sessionData['means']['strukturaUcitelj'] = $this->struktura_ucitelj;

      $user = sisplet_query("SELECT user_id FROM srv_hierarhija_struktura_users WHERE hierarhija_struktura_id='".$struktura_id."'", "obj")->user_id;
      
      $this->sessionData['means']['imeHierarhije'] = \Hierarhija\HierarhijaHelper::hierarhijaPrikazNaslovovpriUrlju($this->anketa, $struktura_id, $user);
    }

    // Shranimo spremenjene nastavitve v bazo
    SurveyUserSession::saveData($this->sessionData);


    echo $this->sessionData['means']['imeHierarhije'];
  }

  /**
   * Posodobimo seznam filtrov za učitelja ali so samo učitelji ali so izlistani po predmetih:
   */
    public function posodobiSeznamFiltrovUcitelja(){
      $this->sessionData['means']['uciteljFilter'] = (!empty($_POST['vrsta']) ? $_POST['vrsta'] : 'agregirano');

      unset($this->sessionData['means']['imeHierarhije']);

      SurveyUserSession::saveData($this->sessionData);

    }

    /**
     * Pobrišemo filter učitljev ali hierarhije, odvisno kaj je bilo izbrano
     *
     * @return bool
     */
    public function pobrisiFilterUciteljevAliHierarhije()
    {
        if (empty($_POST['vrsta']))
            return null;

        $vrsta = $_POST['vrsta'];

        if ($vrsta == 'ucitelji') {
            $this->struktura = [];
            $this->sessionData['means']['strukturaUcitelj'] = [];
            unset($this->sessionData['means']['filterHierarhija']);
        }

        if ($vrsta == 'filtri') {
            $this->struktura_ucitelj = null;
            unset($this->sessionData['means']['strukturaUcitelj']);
        }

        SurveyUserSession::saveData($this->sessionData);
    }

    /**
     * Pridobimo vrstni red šifranta iz tabele srv_hierarhija_sifranti
     *
     * @param int $sifrant_id
     * @return int
     */
    private function pridobiStZaSpecificniSifrant($ravni_id, $sifrant_id)
    {
        if (!is_numeric($sifrant_id) || !is_numeric($sifrant_id))
            return null;

        $sql = sisplet_query("SELECT id, ime FROM srv_hierarhija_sifranti WHERE hierarhija_ravni_id='" . $ravni_id . "' ORDER BY ime", "obj");

        $st = 0;
        foreach ($sql as $row) {
            $st++;
            if ($row->id == $sifrant_id)
                break;
        }

        return $st;
    }

    static public $textAnswersMore = array('0' => '10', '10' => '30', '30' => '300', '300' => '600', '600' => '900', '900' => '100000');

    static function getNumRecords()
    {
        if (isset($_POST['num_records']) && (int)$_POST['num_records'] > 0) {
            $result = (int)self::$textAnswersMore[$_POST['num_records']];
        } else {
            $result = (int)SurveyDataSettingProfiles:: getSetting('numOpenAnswers');
        }
        return $result;
    }

    /**
     * Izriši odprrte odgovore za specifičnega učitelja
     *
     * @param int $variable - ID vpršanja, ki se nahaja v $_HEADERS
     * @param array $data
     * @return html
     */
    public function izrisiOdprteOdgovoreZaUcitelja($variable, $data)
    {
        global $site_path;
        global $global_user_id;

        if (!is_array($data))
            return null;


        $polje_v_datoteki = '$' . $data['sequences'];
//        $filter_za_specificno_anketo = $this->filterHierarhijeZaSpecificnegaUciteljaIzDatoteke();

        // V sejo shranimo vse strukture, ki ima dotični učitelj
        $this->posodobiPodatkeZaUcitelja($global_user_id);

        if (!empty($this->sessionData['means']['strukturaUcitelj'][$this->sessionData['means']['struktura']])) {
            $ucitelj_filter = '&& (';
            $ostali_fitri = false;
            foreach ($this->sessionData['means']['strukturaUcitelj'][$this->sessionData['means']['struktura']] as $key => $struktura) {

                $ucitelj_filter .= ($ostali_fitri ? ' && ' : null);
                $ucitelj_filter .= '($1' . $key . ' == ' . $struktura['stevilka'] . ')';
                $ostali_fitri = true;
            }
            $ucitelj_filter .= ')';
        }


        if (is_null($ucitelj_filter))
            return null;

        # začasna datoteka za hierarhijo odprti odgovori
        $folder = $site_path . EXPORT_FOLDER . '/';
        $tmp_file = $folder . 'tmp_hierarhija_' . $this->anketa . '.tmp';


        // Na začetku datoteke dodamo <?php
        $file_handler = fopen($tmp_file, "w+");
        fwrite($file_handler, "<?php\n");
        fclose($file_handler);

        $commandHierarhija = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} 1 ' . $ucitelj_filter . ' { print "$odprtiOdgovori[\x27",' . $variable . ',"\x27][]=\x27",' . $polje_v_datoteki . ',"\x27;"}\' ' . $this->dataFileName . ' >> ' . $tmp_file;
        shell_exec($commandHierarhija);

        // Na koncu datoteke dodamo zaključek php dokumenta
        $file_handler = fopen($tmp_file, "a");
        fwrite($file_handler, '?>');
        fclose($file_handler);

        include($tmp_file);

        if (file_exists($tmp_file))
            unlink($tmp_file);

        return $this->izrisiHtmlTabeloZaOdprtOdgovor($odprtiOdgovori);

    }

    /**
     * Izrišemo tabelo za odprt odgovor
     *
     * @param array $odprtiOdgovori
     * @return html
     */
    private function izrisiHtmlTabeloZaOdprtOdgovor($odprtiOdgovori)
    {
        foreach ($odprtiOdgovori as $keyVprasanja => $tabela) {
            $vprasanje = $this->_HEADERS[$keyVprasanja . '_0'];

            echo '<table class="anl_tbl anl_bt anl_br tbl_clps">
                        <tbody>
                            <tr>
                                <td class="anl_bl anl_br anl_bb anl_ac anl_bck_freq_1 anl_w110">
                                <span class="spaceLeft anl_variabla">' . $vprasanje['variable'] . '</span>
                                </td>
                                <td class="anl_br anl_bb anl_al anl_bck_freq_1" colspan="3">
                                <span class="anl_variabla_label">' . $vprasanje['naslov'] . '</span>
                                </td>
                            </tr>
                            <tr>
                            <td class="anl_bl anl_br anl_bb anl_ac anl_bck anl_w110"></td>
                            <td class="anl_br anl_bb anl_ac anl_bck anl_variabla_line">Odgovori</td>
                            <td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">Frekvenca</td>                      
                            </tr>';
            $st = 0;
            foreach ($tabela as $keyOdgovor => $odgovori) {
                if (!is_numeric($odgovori) && is_string($odgovori)) {
                    $st++;
                    echo '<tr id="' . $keyVprasanja . '_0_20_' . $keyOdgovor . '" name="valid_row_20">
                                <td class="anl_bl anl_ac anl_br gray">&nbsp;</td>
                                <td class="anl_br anl_bck_0_0">
                                    <div class="anl_user_text_more">' . $odgovori . '</div>
                                </td>
                                <td class="anl_ac anl_br anl_bck_0_0">1</td>
                            </tr>';
                }
            }
            echo '<tr id="anl_click_missing_tr_15" class="anl_bb">
                        <td class="anl_bl anl_br anl_al gray anl_ti_20 anl_bck_text_1">
                        </td>
                        <td class="anl_br anl_al anl_ita red anl_bck_text_1">Skupaj</td>
                        <td class="anl_ita red anl_br anl_ac anl_bck_text_1">' . $st . '</td>    
                  </tr>';
            echo '</tbody>
                     </table>';
        }
    }


    /** Izriše tekstovne odgovore v vertikalni obliki
     *
     * @param unknown_type $spid
     */
    public function sumTextVertical($spid, $_from)
    {
        global $lang;

        # dajemo v bufer, da da ne prikazujemo vprašanj brez veljavnih odgovorov če imamo tako nastavljeno
        $spremenljivka = $this->_HEADERS[$spid];
        $_FREQUENCYS = SurveyAnalysis::getFrequencys();

        # preverimo ali prikazujemo spremenljivko, glede na veljavne odgovore in nastavitev
        // Izrisujemo naše odgovore
        $only_valid = 0;
        if (count($spremenljivka['grids']) > 0) {
            foreach ($spremenljivka['grids'] AS $gid => $grid) {

                # dodamo dodatne vrstice z albelami grida
                if (count($grid['variables']) > 0)
                    foreach ($grid['variables'] AS $vid => $variable) {
                        $_sequence = $variable['sequence'];    # id kolone z podatki v text datoteki
                        $only_valid += (int)$_FREQUENCYS[$_sequence]['validCnt'];
                    }

            }
        }

        // V kolikor ni odgovorov potem nič ne izrisujemo
        if (SurveyDataSettingProfiles:: getSetting('hideEmpty') == 1 && $only_valid == 0) {
            return;
        }

        # dodamo opcijo kje izrisujemo legendo
        # če je besedilo * in je samo ena kategorija je inline legd('da');enda false
        $inline_legenda = ($this->_HEADERS[$spid]['cnt_all'] == 1 || in_array($spremenljivka['tip'], array(1, 8))) ? false : true;

        # koliko zapisov prikažemo naenkrat
        $num_show_records = self::getNumRecords();

        $options = array('inline_legenda' => $inline_legenda, 'isTextAnswer' => false, 'isOtherAnswer' => false, 'num_show_records' => $num_show_records);

//        if (self :: $show_spid_div == true) {
//            echo '<div id="sum_'.$spid.'" loop="'.self::$_CURRENT_LOOP['cnt'].'" class="div_sum_variable div_analiza_holder">';
//            self::displaySpremenljivkaIcons($spid);
//        }
        # tekst vprašanja
        echo '<table class="anl_tbl anl_bt anl_br tbl_clps">';

        // naslovna vrstica tabele
        echo '<tr>';
        #variabla
        echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck_freq_1 anl_w110">';
        echo '<span class="spaceLeft anl_variabla">';
        echo $spremenljivka['variable'];
        echo '</span>';
        echo '</td>';

        #odgovori
        echo '<td class="anl_br anl_bb anl_al anl_bck_freq_1" colspan="5"><span class="anl_variabla_label">';
        echo $spremenljivka['naslov'] . '</span>';
        echo '</td>';
        echo '</tr>';

        // Druga vrstica glave pri odprtih odgovorih
        echo '<tr>';
        #variabla
        echo '<td class="anl_bl anl_br anl_bb anl_ac anl_bck anl_w110">';
        //        echo self::showIcons($spid,$spremenljivka,$_from);
        echo '</td>';

        #odgovori
        echo '<td class="anl_br anl_bb anl_ac anl_bck anl_variabla_line">' . $lang['srv_analiza_frekvence_titleAnswers'] . '</td>';
        //        if (self::$_SHOW_LEGENDA && $inline_legenda){
        //            echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line">'.$lang['srv_analiza_opisne_variable_expression'].'</td>';
        //            echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_legend anl_variabla_line">'.$lang['srv_analiza_opisne_variable_skala'].'</td>';
        //        }
        echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">' . $lang['srv_analiza_frekvence_titleFrekvenca'] . '</td>';
        echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">' . $lang['srv_analiza_frekvence_titleOdstotek'] . '</td>';
        if ($this->_HEADERS[$spid]['show_valid_percent'] == true) {
            echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">' . $lang['srv_analiza_frekvence_titleVeljavni'] . '</td>';
        }
        echo '<td class="anl_br anl_bb anl_ac anl_bck anl_w70 anl_variabla_line">' . $lang['srv_analiza_frekvence_titleKumulativa'] . '</td>';
        echo '</tr>';
        // end naslovne vrstice

        // Prikažemo naše odgovore
        $_answersOther = array();
        $_grids_count = count($spremenljivka['grids']);

        if ($_grids_count > 0)
            foreach ($spremenljivka['grids'] AS $gid => $grid) {
                $_variables_count = count($grid['variables']);
                if ($_variables_count > 0)
                    foreach ($grid['variables'] AS $vid => $variable) {
                        $_sequence = $variable['sequence'];    # id kolone z podatki

                        if ($variable['other'] != true) {
                            # dodamo dodatne vrstice z labelami grida
                            if ($_variables_count > 1) {
                                self::outputGridLabelVertical($gid, $grid, $vid, $variable, $spid, $options);
                            }

                            $counter = 0;
                            $_kumulativa = 0;

                            //self::$_FREQUENCYS[$_sequence]

                            if (count($_FREQUENCYS[$_sequence]['valid']) > 0) {
                                $_valid_answers = SurveyAnalysis::sortTextValidAnswers($spid, $variable, $_FREQUENCYS[$_sequence]['valid']);

                                foreach ($_valid_answers AS $vkey => $vAnswer) {
                                    if ($counter < $num_show_records || self::$isArchive) {
                                        if ($vAnswer['cnt'] > 0 || true) { # izpisujemo samo tiste ki nisno 0
                                            $options['isTextAnswer'] = true;
                                            $counter = SurveyAnalysis::outputValidAnswerVertical($counter, $vkey, $vAnswer, $_sequence, $spid, $_kumulativa, $options);
                                        }
                                    }
                                }
                                # izpišemo sumo veljavnih
                                $counter = SurveyAnalysis::outputSumaValidAnswerVertical($counter, $_sequence, $spid, $options);
                            }
                            if (count($_FREQUENCYS[$_sequence]['invalid']) > 0) {
                                foreach ($_FREQUENCYS[$_sequence]['invalid'] AS $ikey => $iAnswer) {
                                    if ($iAnswer['cnt'] > 0) { # izpisujemo samo tiste ki nisno 0
                                        $counter = SurveyAnalysis::outputInvalidAnswerVertical($counter, $ikey, $iAnswer, $_sequence, $spid, $options);
                                    }
                                }
                                # izpišemo sumo veljavnih
                                $counter = SurveyAnalysis::outputSumaInvalidAnswerVertical($counter, $_sequence, $spid, $options);
                            }
                            #izpišemo še skupno sumo
//                            $counter = self::outputSumaVertical($counter,$_sequence,$spid,$options);
                        } else {
                            $_answersOther[] = array('spid' => $spid, 'gid' => $gid, 'vid' => $vid, 'sequence' => $_sequence);
                        }
                    }
            }

        echo '</table>';
        # izpišemo še tekstovne odgovore za polja drugo
        if (count($_answersOther) > 0 && self::$_FILTRED_OTHER) {
            foreach ($_answersOther AS $oAnswers) {
                echo '<div class="div_other_text">';
                SurveyAnalysis::outputOtherAnswers($oAnswers);
                echo '</div>';
            }
        }

//        if (self :: $show_spid_div == true) {
//            echo '</div>';
//            echo '<br/>';
//        }

    }

    static function outputValidAnswerVertical($counter, $vkey, $vAnswer, $_sequence, $spid, &$_kumulativa, $_options = array())
    {
        global $lang;
        # opcije

        $options = array('isTextAnswer' => false,    # ali je tekstovni odgovor
            'isOtherAnswer' => false,    # ali je odgovor Drugo
            'inline_legenda' => true,    # ali je legenda inline ali v headerju
        );

        foreach ($_options as $_oKey => $_option) {
            $options[$_oKey] = $_option;
        }
        $cssBck = ' ' . self::$cssColors['0_' . ($counter & 1)];

        $_valid = (self::$_FREQUENCYS[$_sequence]['validCnt'] > 0) ? 100 * $vAnswer['cnt'] / self::$_FREQUENCYS[$_sequence]['validCnt'] : 0;
        $_percent = (self::$_FREQUENCYS[$_sequence]['allCnt'] > 0) ? 100 * $vAnswer['cnt'] / self::$_FREQUENCYS[$_sequence]['allCnt'] : 0;
        $_kumulativa += $_valid;

        # če smo v arhivih dodamovse odgovore vendar so nekateri skriti
        if ($counter >= $options['num_show_records'] && self::$isArchive) {
            $cssHide = ' class="displayNone"';
        }
        echo '<tr id="' . $spid . '_' . $_sequence . '_' . $counter . '" name="valid_row_' . $_sequence . '"' . (self::$enableInspect == true && (int)$vAnswer['cnt'] > 0 ? ' vkey="' . $vkey . '"' : '') . $cssHide . '>';
        echo '<td class="anl_bl anl_ac anl_br gray">&nbsp;</td>';
        echo '<td class="anl_br' . $cssBck . '">';
        echo '<div class="anl_user_text_more">' . $vkey . '</div>';
        echo(($options['isTextAnswer'] == false && (string)$vkey != $vAnswer['text']) ? ' (' . $vAnswer['text'] . ')' : '');
        #		if ( $counter+1 == $options['num_show_records'] && $options['num_show_records'] < count(self::$_FREQUENCYS[$_sequence]['valid'])) {
        #			echo '<div id="valid_row_togle_more_'.$_sequence.'" class="floatRight blue pointer anl_more" onclick="showHidenTextTable(\''.$spid.'\', \''.$options['num_show_records'].'\', \''.self::$_CURRENT_LOOP['cnt'].'\');return false;">'.$lang['srv_anl_more'].'</div>';
        #		}
        echo '</td>';
        if (self::$_SHOW_LEGENDA && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true) {
            echo '<td class="anl_ac anl_br' . $cssBck . '">&nbsp;</td>';
            echo '<td class="anl_ac anl_br' . $cssBck . '">&nbsp;</td>';
        }

        echo '<td class="anl_ac anl_br' . $cssBck . (self::$enableInspect == true && $options['isOtherAnswer'] == false && (int)$vAnswer['cnt'] > 0 ? ' fr_inspect' : '') . '">';
        echo (int)$vAnswer['cnt'];
        echo '</td>';
        echo '<td class="anl_ar anl_br' . $cssBck . ' anl_pr10">';
        echo self::formatNumber($_percent, SurveyDataSettingProfiles:: getSetting('NUM_DIGIT_PERCENT'), '%');
        echo '</td>';
        if (self::$_HEADERS[$spid]['show_valid_percent']) {
            echo '<td class="anl_ar anl_br' . $cssBck . ' anl_pr10">';
            echo self::formatNumber($_valid, SurveyDataSettingProfiles:: getSetting('NUM_DIGIT_PERCENT'), '%');
            echo '</td>';
        }
        echo '<td class="anl_ar' . $cssBck . ' anl_pr10">';
        echo self::formatNumber($_kumulativa, SurveyDataSettingProfiles:: getSetting('NUM_DIGIT_PERCENT'), '%');

        echo '</td>';
        echo '</tr>';

        # če mamo več
        if ($counter + 1 == $options['num_show_records'] && $options['num_show_records'] < count(self::$_FREQUENCYS[$_sequence]['valid'])) {
            if (self::$isArchive == false) {
                echo '<tr id="' . $spid . '_' . $_sequence . '_' . $counter . '" name="valid_row_' . $_sequence . '" >';
                echo '<td class="anl_bl anl_ac anl_br gray">&nbsp;</td>';
                echo '<td class="anl_br' . $cssBck . '">';
                // Pri javni povezavi drugace izpisemo
                if (self::$printPreview == false) {
                    echo '<div id="valid_row_togle_more_' . $_sequence . '" class="floatLeft blue pointer anl_more" onclick="showHidenTextTable(\'' . $spid . '\', \'' . $options['num_show_records'] . '\', \'' . self::$_CURRENT_LOOP['cnt'] . '\');return false;">' . $lang['srv_anl_more'] . '</div>';
                    echo '<div id="valid_row_togle_more_' . $_sequence . '" class="floatRight blue pointer anl_more" onclick="showHidenTextTable(\'' . $spid . '\', \'' . $options['num_show_records'] . '\', \'' . self::$_CURRENT_LOOP['cnt'] . '\');return false;">' . $lang['srv_anl_more'] . '</div>';
                } else {
                    echo '<div id="valid_row_togle_more_' . $_sequence . '" class="floatLeft anl_more">' . $lang['srv_anl_more'] . '</div>';
                    echo '<div id="valid_row_togle_more_' . $_sequence . '" class="floatRight anl_more">' . $lang['srv_anl_more'] . '</div>';
                }
                echo '</td>';
                if (self::$_SHOW_LEGENDA && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true) {
                    echo '<td class="anl_ac anl_br' . $cssBck . '">&nbsp;</td>';
                    echo '<td class="anl_ac anl_br' . $cssBck . '">&nbsp;</td>';
                }
                echo '<td class="anl_ac anl_br' . $cssBck . '">' . '</td>';
                echo '<td class="anl_ar anl_br' . $cssBck . ' anl_pr10">' . '</td>';
                if (self::$_HEADERS[$spid]['show_valid_percent']) {
                    echo '<td class="anl_ar anl_br' . $cssBck . ' anl_pr10">' . '</td>';
                }
                echo '<td class="anl_ar' . $cssBck . ' anl_pr10">' . '</td>';
                echo '</tr>';
            } else {
                #v arhivie dodamo vse odgovore vendar so skriti
                echo '<tr id="' . $spid . '_' . $_sequence . '_' . $counter . '" name="valid_row_' . $_sequence . '" >';
                echo '<td class="anl_bl anl_ac anl_br gray">&nbsp;</td>';
                echo '<td class="anl_br' . $cssBck . '">';
                echo '<div id="valid_row_togle_more_' . $_sequence . '" class="floatLeft blue pointer" onclick="$(this).parent().parent().parent().find(\'tr.displayNone\').removeClass(\'displayNone\');$(this).parent().parent().addClass(\'displayNone\');return false;">' . $lang['srv_anl_all'] . '</div>';
                echo '<div id="valid_row_togle_more_' . $_sequence . '" class="floatRight blue pointer" onclick="$(this).parent().parent().parent().find(\'tr.displayNone\').removeClass(\'displayNone\');$(this).parent().parent().addClass(\'displayNone\');return false;">' . $lang['srv_anl_all'] . '</div>';
                echo '</td>';
                if (self::$_SHOW_LEGENDA && $options['isOtherAnswer'] == false && $options['inline_legenda'] == true) {
                    echo '<td class="anl_ac anl_br' . $cssBck . '">&nbsp;</td>';
                    echo '<td class="anl_ac anl_br' . $cssBck . '">&nbsp;</td>';
                }
                echo '<td class="anl_ac anl_br' . $cssBck . '">' . '</td>';
                echo '<td class="anl_ar anl_br' . $cssBck . ' anl_pr10">' . '</td>';
                if (self::$_HEADERS[$spid]['show_valid_percent']) {
                    echo '<td class="anl_ar anl_br' . $cssBck . ' anl_pr10">' . '</td>';
                }
                echo '<td class="anl_ar' . $cssBck . ' anl_pr10">' . '</td>';
                echo '</tr>';
            }
        }

        $counter++;
        return $counter;
    }


    // Izvoz pdf in rtf
    function displayExport()
    {

        if ($this->isSelectedBothVariables()) {
            $vars1 = $this->getSelectedVariables(1);
            $vars2 = $this->getSelectedVariables(2);

            $data1 = '';
            $data2 = '';

            foreach ($vars1 as $var1) {
                $data1 .= implode(',', array_values($var1)) . ',';
            }
            $data1 = substr($data1, 0, -1);

            foreach ($vars2 as $var2) {
                $data2 .= implode(',', array_values($var2)) . ',';
            }
            $data2 = substr($data2, 0, -1);


            $href_pdf = makeEncodedIzvozUrlString('izvoz.php?b=export&m=hierarhija_pdf_izpis&anketa=' . $this->anketa);
//            $href_rtf = 'index.php?anketa=' . $this->anketa . '&a=hierarhija_superadmin&m=analize&r=custom';
            $href_rtf =  makeEncodedIzvozUrlString('izvoz.php?b=export&m=hierarhija_rtf_izpis&anketa=' . $this->anketa);
//            $href_xls = makeEncodedIzvozUrlString('izvoz.php?b=export&m=mean_izpis_xls&anketa=' . $this->anketa);
            echo '<script>';
            # nastavimopravilne linke
            echo '$("#secondNavigation_links a#meansDoPdf").attr("href", "' . $href_pdf . '");';
            echo '$("#secondNavigation_links a#meansDoRtf").attr("href", "' . $href_rtf . '");';
//            echo '$("#secondNavigation_links a#meansDoXls").attr("href", "' . $href_xls . '");';
            # prikažemo linke
            echo '$("#hover_export_icon").removeClass("hidden");';
            echo '$("#secondNavigation_links a").removeClass("hidden");';
            echo '</script>';
        }
    }

    public function setPostVars()
    {
        if (isset($_POST['sequence1']) && count($_POST['sequence1']) > 0) {
            $i = 0;
            if (is_array($_POST['sequence1']) && count($_POST['sequence1']) > 0) {
                foreach ($_POST['sequence1'] AS $_seq1) {
                    $this->variabla1[$i]['seq'] = $_seq1;
                    $i++;
                }
            }
        }
        if (isset($_POST['spr1']) && count($_POST['spr1']) > 0) {
            $i = 0;
            if (is_array($_POST['spr1']) && count($_POST['spr1']) > 0) {
                foreach ($_POST['spr1'] AS $_spr1) {
                    $this->variabla1[$i]['spr'] = $_spr1;
                    $i++;
                }
            }
        }
        if (isset($_POST['grid1']) && count($_POST['grid1']) > 0) {
            $i = 0;
            if (is_array($_POST['grid1']) && count($_POST['grid1']) > 0) {
                foreach ($_POST['grid1'] AS $_grd1) {
                    $this->variabla1[$i]['grd'] = $_grd1;
                    $i++;
                }
            }
        }

        if (isset($_POST['sequence2']) && count($_POST['sequence2']) > 0) {
            $i = 0;

            if (is_array($_POST['sequence2']) && count($_POST['sequence2']) > 0) {

                foreach ($_POST['sequence2'] AS $_seq2) {
                    $this->variabla2[$i]['seq'] = $_seq2;
                    $i++;
                }
            }
        }
        if (isset($_POST['spr2']) && count($_POST['spr2']) > 0) {
            $i = 0;
            if (is_array($_POST['spr2']) && count($_POST['spr2']) > 0) {
                foreach ($_POST['spr2'] AS $_spr2) {
                    $this->variabla2[$i]['spr'] = $_spr2;
                    $i++;
                }
            }
        }
        if (isset($_POST['grid2']) && is_array($_POST['grid2']) && count($_POST['grid2']) > 0) {
            $i = 0;
            if (count($_POST['grid2']) > 0) {
                foreach ($_POST['grid2'] AS $_grd2) {
                    $this->variabla2[$i]['grd'] = $_grd2;
                    $i++;
                }
            }
        }

        if (isset($_POST['filter_vrednosti']) && count($_POST['filter_vrednosti']) > 0) {
            $this->filter_hierarhija = $_POST['filter_vrednosti'];
        }

        if (is_null($_POST['filter_vrednosti'])) {
            $this->filter_hierarhija = array();
        }

        // Preverimo, če imamo strukturo za uporabnika in , če do sedaj ni bil izbran še noben filter, potem vedno izberemo vse iz danjega nivoja
        $struktura_ucitelja = $this->hierarhijaUporabnika();

        if (empty($this->sessionData['means']['filterHierarhija']) && $struktura_ucitelja != 'admin' && is_array($struktura_ucitelja)) {
            $st_nivojev = sizeof($struktura_ucitelja);
            $this->filter_hierarhija['nivo' . $st_nivojev] = $struktura_ucitelja[$st_nivojev];
        }

        # variable shranimo v sejo, da jih obdržimo tudi če spreminjamo nastavitve ali razne filtre analiz
        if (isset($this->variabla1) && count($this->variabla1) > 0) {
            $this->sessionData['means']['means_variables']['variabla1'] = $this->variabla1;
        }
        if (isset($this->variabla2) && count($this->variabla2) > 0) {
            $this->sessionData['means']['means_variables']['variabla2'] = $this->variabla2;
        }

        // Filtriranje po šifrantih
        if (isset($this->filter_hierarhija) && sizeof($this->filter_hierarhija) > 0) {
            $this->sessionData['means']['filterHierarhija'] = $this->filter_hierarhija;
        }

        // Shranimo spremenjene nastavitve v bazo
        SurveyUserSession::saveData($this->sessionData);
    }


    /**
     * funkcija vrne seznam primern variabel za meanse
     */
    function getVariableList($dropdown)
    {
        if (isset($this->variablesList[$dropdown]) && is_array($this->variablesList[$dropdown]) && count($this->variablesList[$dropdown]) > 0) {
            return $this->variablesList[$dropdown];
        } else {
            # pobrišemo array()
            $this->variablesList = array();


            # zloopamo skozi header in dodamo variable (potrebujemo posamezne sekvence)
            foreach ($this->_HEADERS AS $skey => $spremenljivka) {
                if ((int)$spremenljivka['hide_system'] == 1 && in_array($spremenljivka['variable'], array('email', 'ime', 'priimek', 'telefon', 'naziv', 'drugo'))) {
                    continue;
                }

                $tip = $spremenljivka['tip'];

                $skala = (int)$spremenljivka['skala'];
                # pri drugi, analizirani variabli morajo biti numerične ali ordinalne, v ostalem pa nič)
                # skala - 0 Ordinalna
                # skala - 1 Nominalna
                $_dropdown_condition = $dropdown == 1
                || ($dropdown == 2
                    && ($skala == 0    # ordinalna
                        || $tip == 7    # number
                        || $tip == 6
                        || $tip == 18    # vsota
                        || $tip == 20))    # multi number
                    ? true : false;

                //V kolikor gre za prvi meni, potem notri dodamo samo vlogo, ki jo bomo naknadno skrili pri prikazu
                if ((is_numeric($tip)
                        && $tip != 4    #text
                        && $tip != 5    #label
                        && $tip != 9    #SN-imena
                        && $tip != 22    #compute
                        && $_dropdown_condition    # ali ustreza pogoju za meanse
                        && $dropdown != 1)
                    || (is_numeric($tip)
                        && $tip == 1
                        && $dropdown == 1)
                ) {

                    $cnt_all = (int)$spremenljivka['cnt_all'];
                    # radio in select in checkbox
                    if ($cnt_all == '1' || $tip == 1 || $tip == 3 || $tip == 2) {


                        # pri tipu radio ali select dodamo tisto variablo ki ni polje "drugo"
                        if (($tip == 1 || $tip == 3)) {
                            if (count($spremenljivka['grids']) == 1) {

                                # če imamo samo en grid ( lahko je več variabel zaradi polja drugo.
                                $grid = $spremenljivka['grids'][0];
                                if (count($grid['variables']) > 0) {
                                    foreach ($grid['variables'] AS $vid => $variable) {

                                        //Tukaj zapišemo, samo če gre za vlogo, ker bomo delali anlize po teh vrednostih
                                        if (($variable['other'] != 1 && $dropdown == 2) || ($variable['variable'] == 'vloga' && $dropdown == 1)) {
                                            # imampo samo eno sekvenco grids[0]variables[0]
                                            $this->variablesList[$dropdown][] = array(
                                                'tip' => $tip,
                                                'spr_id' => $skey,
                                                'sequence' => $spremenljivka['grids'][0]['variables'][$vid]['sequence'],
                                                'variableNaslov' => '(' . $spremenljivka['variable'] . ')&nbsp;' . strip_tags($spremenljivka['naslov']),
                                                'canChoose' => true,
                                                'sub' => 0);

                                        }

                                    }
                                }
                            }
                        } else if ($skala == 1 || true) { # ta pogoj skala == 1 je malo sumljiv. ne vem več zakaj je tako

                            # imampo samo eno sekvenco grids[0]variables[0]
                            $this->variablesList[$dropdown][] = array(
                                'tip' => $tip,
                                'spr_id' => $skey,
                                'sequence' => $spremenljivka['grids'][0]['variables'][0]['sequence'],
                                'variableNaslov' => '(' . $spremenljivka['variable'] . ')&nbsp;' . strip_tags($spremenljivka['naslov']),
                                'canChoose' => true,
                                'sub' => 0);
                        }
                    } else if ($cnt_all > 1) {

                        # imamo več skupin ali podskupin, zato zlopamo skozi gride in variable
                        if (count($spremenljivka['grids']) > 0) {
                            $this->variablesList[$dropdown][] = array(
                                'tip' => $tip,

                                'variableNaslov' => '(' . $spremenljivka['variable'] . ')&nbsp;' . strip_tags($spremenljivka['naslov']),
                                'canChoose' => false,
                                'sub' => 0);
                            # ali imamo en grid, ali več (tabele
                            if (count($spremenljivka['grids']) == 1) {
                                # če imamo samo en grid ( lahko je več variabel zaradi polja drugo.
                                $grid = $spremenljivka['grids'][0];
                                if (count($grid['variables']) > 0) {
                                    foreach ($grid['variables'] AS $vid => $variable) {
                                        if ($variable['other'] != 1) {
                                            $this->variablesList[$dropdown][] = array(
                                                'tip' => $tip,
                                                'spr_id' => $skey,
                                                'sequence' => $variable['sequence'],
                                                'variableNaslov' => '(' . $variable['variable'] . ')&nbsp;' . strip_tags($variable['naslov']),
                                                'canChoose' => true,
                                                'sub' => 1);
                                        }
                                    }
                                }

                            } else if ($tip == 16 || $tip == 18) {
                                # imamo multicheckbox
                                foreach ($spremenljivka['grids'] AS $gid => $grid) {
                                    $sub = 0;
                                    if ($grid['variable'] != '') {
                                        $sub++;
                                        $this->variablesList[$dropdown][] = array(
                                            'tip' => $tip,
                                            'spr_id' => $skey,
                                            'grd_id' => $gid,
                                            'sequence' => $grid['variables'][0]['sequence'],
                                            'variableNaslov' => '(' . $grid['variable'] . ')&nbsp;' . strip_tags($grid['naslov']),
                                            'canChoose' => true,
                                            'sub' => 1);
                                    }
                                }
                            } else {
                                # imamo več gridov - tabele
                                foreach ($spremenljivka['grids'] AS $gid => $grid) {
                                    $sub = 0;
                                    if ($grid['variable'] != '') {
                                        $sub++;
                                        $this->variablesList[$dropdown][] = array(
                                            'tip' => $tip,
                                            'variableNaslov' => '(' . $grid['variable'] . ')&nbsp;' . strip_tags($grid['naslov']),
                                            'canChoose' => false,
                                            'sub' => $sub);
                                    }
                                    if (count($grid['variables']) > 0) {
                                        $sub++;
                                        foreach ($grid['variables'] AS $vid => $variable) {
                                            if ($variable['other'] != 1) {
                                                $this->variablesList[$dropdown][] = array(
                                                    'tip' => $tip,
                                                    'spr_id' => $skey,
                                                    'sequence' => $variable['sequence'],
                                                    'variableNaslov' => '(' . $variable['variable'] . ')&nbsp;' . strip_tags($variable['naslov']),
                                                    'canChoose' => true,
                                                    'sub' => $sub);
                                            }
                                        }
                                    }
                                }
                            }

                        }
                    }
                }
            }
            return $this->variablesList[$dropdown];
        }
    }

    function isSelectedBothVariables()
    {
        $selected1 = false;
        $selected2 = false;
        if (count($this->variabla1)) {
            foreach ($this->variabla1 AS $var1) {
                if ((int)$var1['seq'] > 0) {
                    $selected1 = true;
                }
            }
        }
        if (count($this->variabla2)) {
            foreach ($this->variabla2 AS $var2) {
                if ((int)$var2['seq'] > 0) {
                    $selected2 = true;
                }
            }
        }

        return ($selected1 && $selected2);
    }


    function getSelectedVariables($which = 1)
    {
        $selected = array();
        if ($which == 1) {
            if (count($this->variabla1) > 0) {
                foreach ($this->variabla1 AS $var1) {
                    if ((int)$var1['seq'] > 0) {
                        $selected[] = $var1;
                    }
                }
            }
        } else {
            if (count($this->variabla2) > 0) {
                foreach ($this->variabla2 AS $var2) {
                    if ((int)$var2['seq'] > 0) {
                        $selected[] = $var2;
                    }
                }
            }
        }

        return count($selected) > 0 ? $selected : null;
    }


    public function createMeans($v_first, $v_second)
    {
        global $site_path;

        $folder = $site_path . EXPORT_FOLDER . '/';

        if ($this->dataFileName != '' && file_exists($this->dataFileName)) {

            $spr1 = $this->_HEADERS[$v_first['spr']];
            $spr2 = $this->_HEADERS[$v_second['spr']];

            $grid1 = $spr1['grids'][$v_first['grd']];
            $grid2 = $spr2['grids'][$v_second['grd']];

            $sequence1 = $v_first['seq'];
            $sequence2 = $v_second['seq'];

            # za checkboxe gledamo samo odgovore ki so bili 1 in za vse opcije
            $sekvences1 = array();
            $sekvences2 = array();
            $spr_1_checkbox = false;
            $spr_2_checkbox = false;

            if ($spr1['tip'] == 2 || $spr1['tip'] == 16) {
                $spr_1_checkbox = true;
                if ($spr1['tip'] == 2) {
                    $sekvences1 = explode('_', $spr1['sequences']);
                }
                if ($spr1['tip'] == 16) {

                    foreach ($grid1['variables'] AS $_variables) {
                        $sekvences1[] = $_variables['sequence'];
                    }
                }
            } else {
                $sekvences1[] = $sequence1;
            }

            if ($spr2['tip'] == 2 || $spr2['tip'] == 16) {
                $spr_2_checkbox = true;
                if ($spr2['tip'] == 2) {
                    $sekvences2 = explode('_', $this->_HEADERS[$v_second['spr']]['sequences']);
                }
                if ($spr2['tip'] == 16) {
                    foreach ($grid2['variables'] AS $_variables) {
                        $sekvences2[] = $_variables['sequence'];
                    }
                }
            } else {
                $sekvences2[] = $sequence2;
            }

            # pogoji so že dodani v _CURRENT_STATUS_FILTER

            # dodamo filter za loop-e
            if (isset($this->_CURRENT_LOOP['filter']) && $this->_CURRENT_LOOP['filter'] != '') {
                $status_filter = $this->_CURRENT_STATUS_FILTER . ' && ' . $this->_CURRENT_LOOP['filter'];
            } else {
                $status_filter = $this->_CURRENT_STATUS_FILTER;
            }


            # dodamo status filter za vse sekvence checkbox-a da so == 1
            if ($additional_status_filter != null) {
                $status_filter .= $additional_status_filter;
            }

            # odstranimo vse zapise, kjer katerakoli od variabel vsebuje missing
            $_allMissing_answers = SurveyMissingValues::GetMissingValuesForSurvey(array(1, 2, 3));
            $_pageMissing_answers = $this->getInvalidAnswers(MISSING_TYPE_CROSSTAB);
            # polovimo obe sequenci
            $tmp_file = $folder . 'tmp_means_' . $this->anketa . '.tmp';

            // Na začetku datoteke dodamo <?php
            $file_handler = fopen($tmp_file, "w");
            fwrite($file_handler, "<?php\n");
            fclose($file_handler);

            if (count($sekvences1) > 0)
                foreach ($sekvences1 AS $sequence1) {
                    if (count($sekvences2) > 0)
                        foreach ($sekvences2 AS $sequence2) {
                            #skreira variable: $meansArray
                            $additional_filter = '';
                            if ($spr_1_checkbox == true) {
                                $_seq_1_text = '' . $sequence1;

                                # pri checkboxih gledamo samo kjer je 1 ( ne more bit missing)
                                $additional_filter = ' && ($' . $sequence1 . ' == 1)';
                            } else {
                                $_seq_1_text = '$' . $sequence1;

                                # dodamo še pogoj za missinge
                                foreach ($_pageMissing_answers AS $m_key1 => $missing1) {
                                    $additional_filter .= ' && ($' . $sequence1 . ' != ' . $m_key1 . ')';
                                }
                            }

                            if ($spr_2_checkbox == true) {
                                $_seq_2_text = '' . $sequence2;

                                # pri checkboxih gledamo samo kjer je 1 ( ne more bit missing)
                                $additional_filter .= ' && ($' . $sequence2 . ' == 1)';
                            } else {
                                $_seq_2_text = '$' . $sequence2;

                                # dodamo še pogoj za missinge
                                foreach ($_pageMissing_answers AS $m_key2 => $missing2) {
                                    $additional_filter .= ' && ($' . $sequence2 . ' != ' . $m_key2 . ')';
                                }
                            }

                            # V kolikor smo izbrali filtre potem prikažemo samo ustrezne rezultate glede na filtre
                            ## Postavimo filter za hierarhijo na null
                            $hierarhija_filter = $this->filterHierarhijeIzTekstovneDatoteke();

                            // V kolikor imamo filter po učiteljih
                            $ucitelj_filter = $this->filterHierarhijeZaSpecificnegaUciteljaIzDatoteke();

                            if (IS_WINDOWS) {
                                $command = 'awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} ' . $status_filter . $additional_filter . (!empty($ucitelj_filter) ? $ucitelj_filter : $hierarhija_filter) . ' { print \"$meansArray[\x27\",' . $_seq_2_text . ',\"\x27][\x27\",' . $_seq_1_text . ',\"\x27]++;\"}" ' . $this->dataFileName . ' >> ' . $tmp_file;
                            } else {
                                $command = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} ' . $status_filter . $additional_filter . (!empty($ucitelj_filter) ? $ucitelj_filter : $hierarhija_filter) . ' { print "$meansArray[\x27",' . $_seq_2_text . ',"\x27][\x27",' . $_seq_1_text . ',"\x27]++;"}\' ' . $this->dataFileName . ' >> ' . $tmp_file;
                            }
                            $out = shell_exec($command);

                        }

                }

            // Na koncu datoteke dodamo zaključek php dokumenta
            $file_handler = fopen($tmp_file, "a");
            fwrite($file_handler, '?>');
            fclose($file_handler);

            include($tmp_file);

            if (file_exists($tmp_file)) {
                unlink($tmp_file);
            }

            # izračunamo povprečja
            $means = array();
            $max_vrednost = array();
            $min_vrednost = array();
            $sumMin = null;
            $sumMax = null;
            $sumStdDeviation = null;
            $_tmp_sumaMeans = 0;
            $sum_std_dev = array();
            if (is_array($meansArray) && count($meansArray) > 0) {
                foreach ($meansArray AS $f_key => $first) {
                    $tmp_sum = 0;
                    $tmp_cnt = 0;
                    $min = null;
                    $max = null;

                    //$s_key je vrednost odgovora in zato vzamemo min in max
                    foreach ($first AS $s_key => $second) {
                        # preverimo da je vse numeric
                        if (is_numeric($s_key) && is_numeric($second)) {
                            $tmp_sum = $tmp_sum + ($s_key * $second);
                            $tmp_cnt = $tmp_cnt + $second;
                        }

                        if (is_null($min) || $min > $s_key)
                            $min = $s_key;

                        if (is_null($max) || $max < $s_key)
                            $max = $s_key;

                        if (is_null($sumMin) || $sumMin > $s_key)
                            $sumMin = $s_key;

                        if (is_null($sumMax) || $sumMax < $s_key)
                            $sumMax = $s_key;
                    }


                    $_tmp_sumaMeans += $tmp_sum;
                    $key = $f_key;
                    if ($tmp_cnt != 0) {
                        $means[$key] = bcdiv($tmp_sum, $tmp_cnt, 3);
                    } else {
                        $means[$key] = bcdiv(0, 1, 3);
                    }

                    //računamo še standardno deviacijo
                    $st_rezultatov = 0;
                    $std_dev = [];
                    $polje = [];
                    foreach ($first AS $s_key => $second) {
                        # preverimo da je vse numeric
                        if (is_numeric($s_key) && is_numeric($second)) {
                            $std_vmesna = pow(($s_key - $means[$key]), 2);
                            $std_dev[] = $std_vmesna * $second;
                            $st_rezultatov = $st_rezultatov + $second;
                        }
                    }

                    //Prevzeto je standardna deviacija 0, ker pri učiteljih imamo samo 1 rezltat in ne moremo računati po njem
                    $std_deviacija[$key] = 0;

                    //izračunamo standardno diviacio za učence
                    if (array_sum($std_dev) > 0)
                        $std_deviacija[$key] = sqrt((array_sum($std_dev) / ($st_rezultatov - 1)));


                    //Vpišemo min in max vrednost za sledeče vprašanje
                    $max_vrednost[$key] = $max;
                    $min_vrednost[$key] = $min;

                }

            }


            # inicializacija
            $_all_options = array();
            $sumaVrstica = array();
            $sumaSkupna = 0;
            $sumaMeans = 0;

            # poiščemo pripadajočo spremenljivko
            $var_options = $this->_HEADERS[$v_second['spr']]['options'];

            # najprej poiščemo (združimo) vse opcije ki so definirane kot opcije spremenljivke in vse ki so v meansih
            if (count($var_options) > 0 && $spr_2_checkbox !== true) {
                foreach ($var_options as $okey => $opt) {
                    $_all_options[$okey] = array('naslov' => $opt, 'type' => 'o');
                }
            }

            # za checkboxe dodamo posebej vse opcije
            if ($spr_2_checkbox == true) {
                if ($spr2['tip'] == 2) {
                    $grid2 = $this->_HEADERS[$v_second['spr']]['grids']['0'];
                }

                foreach ($grid2['variables'] As $vkey => $variable) {
                    if ($variable['other'] != 1) {
                        $_all_options[$variable['sequence']] = array('naslov' => $variable['naslov'], 'type' => 'o', 'vr_id' => $variable['variable']);
                    }
                }
            }

            # dodamo odgovore iz baze ki niso missingi
            if (count($meansArray) > 0) {
                foreach ($meansArray AS $_kvar1 => $_var1) {
                    # missingov ne dodajamo še zdaj, da ohranimo pravilen vrstni red
                    foreach ($_var1 AS $_kvar2 => $_var2) {
                        if (!isset($_allMissing_answers[$_kvar1]) || (isset($_allMissing_answers[$_kvar1]) && isset($_pageMissing_answers[$_kvar1]))) {
                            $sumaVrstica[$_kvar1] += $_var2;
                        }
                    }
                    # missingov ne dodajamo še zdaj, da ohranimo pravilen vrstni red
                    if (!isset($_allMissing_answers[$_kvar1]) && !isset($_all_options[$_kvar1])) {
                        $_all_options[$_kvar1] = array('naslov' => $_kvar1, 'type' => 't');
                    }

                }
            }
            # dodamo še missinge, samo tiste ki so izbrani z profilom
            foreach ($_allMissing_answers AS $miskey => $_missing) {
                if (!isset($_pageMissing_answers[$miskey])) {
                    if ($spr_2_checkbox !== true) {
                        $_all_options[$miskey] = array('naslov' => $_missing, 'type' => 'm');
                    }
                }
            }
            $sumaSkupna = array_sum($sumaVrstica);
            $sumaMeans = ($sumaSkupna > 0) ? $_tmp_sumaMeans / $sumaSkupna : 0;;

            # če lovimo po enotah, moramo skupne enote za vsako kolono(vrstico) izračunati posebej
            if ($this->crossNavVsEno == 1) {
                $sumaSkupna = 0;
                $sumaVrstica = array();

                # sestavimo filtre za posamezno variablo da ni missing
                if (count($sekvences1) > 0) {
                    $spr1_addFilter = '';

                    foreach ($sekvences1 AS $sequence1) {
                        # dodamo še pogoj za missinge
                        foreach ($_pageMissing_answers AS $m_key1 => $missing1) {
                            $spr1_addFilter .= ' && ($' . $sequence1 . ' != ' . $m_key1 . ')';
                        }
                    }
                }
                if (count($sekvences2) > 0) {
                    $spr2_addFilter = '';

                    foreach ($sekvences2 AS $sequence2) {
                        # dodamo še pogoj za missinge
                        foreach ($_pageMissing_answers AS $m_key2 => $missing2) {
                            $spr2_addFilter .= ' && ($' . $sequence2 . ' != ' . $m_key2 . ')';
                        }
                    }
                }

                # polovimo obe sequenci
                $tmp_file = $folder . 'tmp_means_' . $this->anketa . '.TMP';


                $file_handler = fopen($tmp_file, "w");
                fwrite($file_handler, "<?php\n");

                fclose($file_handler);

                # preštejemo vse veljavne enote (nobena vrednost ne sme bit missing)
                if (IS_WINDOWS) {
                    $command_all = 'awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} ' . $status_filter . $spr1_addFilter . $spr2_addFilter . ' { print \"$sumaSkupna++;\"}" ' . $this->dataFileName . ' >> ' . $tmp_file;
                } else {
                    $command_all = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} ' . $status_filter . $spr1_addFilter . $spr2_addFilter . ' { print "$sumaSkupna++;"}\' ' . $this->dataFileName . ' >> ' . $tmp_file;
                }

                $out_all = shell_exec($command_all);


                #za vsako variablo polovimo število enot
                #najprej za stolpce
                if (count($sekvences1) > 0) {
                    foreach ($sekvences1 AS $sequence1) {
                        if ($spr_1_checkbox == true) {
                            $_seq_1_text = '' . $sequence1;
                            # pri checkboxih lovimo samo tiste ki so 1
                            $chckbox_filter1 = ' && ($' . $sequence1 . ' == 1)';
                        } else {
                            $_seq_1_text = '$' . $sequence1;
                        }

                        if (IS_WINDOWS) {
                            $command_1 = 'awk -F"|" "BEGIN {{OFS=\"\"} {ORS=\"\n\"}} ' . $status_filter . $chckbox_filter1 . $spr2_addFilter . ' { print \"$sumaVrstica[\x27\",' . $_seq_1_text . ',\"\x27]++;\"}" ' . $this->dataFileName . ' >> ' . $tmp_file;
                        } else {
                            $command_1 = 'awk -F"|" \'BEGIN {{OFS=""} {ORS="\n"}} ' . $status_filter . $chckbox_filter1 . $spr2_addFilter . ' { print "$sumaVrstica[\x27",' . $_seq_1_text . ',"\x27]++;"}\' ' . $this->dataFileName . ' >> ' . $tmp_file;
                        }
                        $out = shell_exec($command_1);
                    }
                }
            }

            # skupna standardna deviacija
            if (!is_null($meansArray)) {
                $sum_std_dev = array();
                foreach ($meansArray AS $row) {
                    foreach ($row AS $value => $st) {
                        if (is_numeric($value) && is_numeric($st)) {
                            $sum_vmesna = pow(($value - $sumaMeans), 2);
                            $sum_std_dev[] = $sum_vmesna * $st;
                        }
                    }
                }

                $sum_std_deviacija = 0;
                // Izračunamo skupno standardno deviacijo
                if (array_sum($sum_std_dev) > 0)
                    $sum_std_deviacija = sqrt((array_sum($sum_std_dev) / ($sumaSkupna - 1)));
            }

            $meansArr['v1'] = $v_first;    # prva variabla
            $meansArr['v2'] = $v_second;    # druga variabla
            $meansArr['result'] = $means;    # povprečja
            $meansArr['options'] = $_all_options;    # vse opcije za variablo 2
            $meansArr['max'] = $max_vrednost;    #Max vrednost pri odgovorih
            $meansArr['min'] = $min_vrednost;    #Min vrednost pri odgovorih
            $meansArr['stdDeviation'] = $std_deviacija;
            $meansArr['sumaMin'] = $sumMin; #Min vrednost pri vseh odgovorih
            $meansArr['sumaMax'] = $sumMax; #Max vrednost pri vseh odgovorih
            $meansArr['sumaStdDeviation'] = $sum_std_deviacija;
            $meansArr['sumaVrstica'] = $sumaVrstica;    #št odgovorov glede na vrstice
            $meansArr['sumaSkupna'] = $sumaSkupna;    #skupno št. odgovorov
            $meansArr['sumaMeans'] = $sumaMeans;    #skupno povprečje

            return $meansArr;
        }
    }

    /**
     * Standardna diviacija
     *
     * @param (array) $a
     * @return integer
     */
    private function stats_standard_deviation(array $a, $sample = false)
    {
        $n = count($a);
        if ($n === 0) {
            trigger_error("The array has zero elements", E_USER_WARNING);
            return false;
        }
        if ($sample && $n === 1) {
            trigger_error("The array has only 1 element", E_USER_WARNING);
            return false;
        }
        $mean = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d = ((double)$val) - $mean;
            $carry += $d * $d;
        };
        if ($sample) {
            --$n;
        }
        return sqrt($carry / $n);
    }

    /**
     * Filter po hierarhiji za filtriranje pdoatkov iz tekstovne datoteke
     *
     * @return null || string
     */
    public function filterHierarhijeIzTekstovneDatoteke()
    {
        if (!is_array($this->sessionData['means']['filterHierarhija']) || empty($this->sessionData['means']['filterHierarhija']))
            return null;

        $hierarhija_filter = null;

        if (is_array($this->sessionData['means']['filterHierarhija'])) {
            foreach ($this->sessionData['means']['filterHierarhija'] as $nivo_key => $polje) {
                $hierarhija_filter .= ' && (';

                if (!is_null($polje) && is_array($polje)) {
                    foreach ($polje as $key => $vrednost) {
                        $nivo = trim($nivo_key, 'nivo');

                        if (sizeof($polje) > 1) {
                            if ($key != 0)
                                $hierarhija_filter .= ' || '; //OR uporabimo, kadar iščemo po več spremenljivkah na istem nivoju
                            $hierarhija_filter .= '($1' . $nivo . ' == ' . $vrednost . ')';
                        } else {
                            $hierarhija_filter .= '($1' . $nivo . ' == ' . $vrednost . ')';
                        }

                    }
                }

                $hierarhija_filter .= ')';
            }
        }

        return $hierarhija_filter;
    }

    /**
     * Filter hierarhije za specifičnega učitelja
     *
     * @return null || string
     */
    public function filterHierarhijeZaSpecificnegaUciteljaIzDatoteke()
    {
        global $global_user_id;

//        $this->posodobiPodatkeZaUcitelja($global_user_id);

        if (!empty($this->struktura) || empty($this->sessionData['means']['strukturaUcitelj']) || sizeof($this->sessionData['means']['strukturaUcitelj']) == 0)
            return null;


        $ucitelj_filter = ' && (';

        // Gremo po vsej strukturi, kjer se nahaja učitelj lahko je 1 ali pa jih je več
        // Ključ je ID strukture - srv_hierarhija_struktura table
        $oklepaj = false;
        foreach ($this->sessionData['means']['strukturaUcitelj'] as $key => $struktura) {
            // Vse nadalne poizvedbe vsebujejo OR - kje je treba pridobiti podatke iz vseh struktur
            $ucitelj_filter .= ($oklepaj ? ' || (' : ' (');

            foreach ($struktura as $nivo => $vrednost) {
                $ucitelj_filter .= ($nivo != key($struktura) ? ' && ' : null);
                $ucitelj_filter .= '($1' . $nivo . ' == ' . $vrednost['stevilka'] . ')';
            }

            $ucitelj_filter .= ')';
            $oklepaj = true;
        }

        $ucitelj_filter .= ' )';

        return $ucitelj_filter;

    }


    /**
     * Prikaže tabelo s povprečji, min, max in standardna deviacija
     *
     * @param $_means
     * @return html
     */
    function displayMeansTable($_means)
    {
        global $lang;

        #število vratic in število kolon
        $cols = count($_means);
        # preberemo kr iz prvega loopa
        $rows = count($_means[0]['options']);


        # ali prikazujemo vrednosti variable pri spremenljivkah
        $show_variables_values = $this->doValues;

        $showSingleUnits = $this->sessionData['means']['meansJoinPercentage'] == true && $this->sessionData['means']['meansSeperateTables'] == false;

        # izrišemo tabelo
        echo '<table class="anl_tbl_crosstab fullWidth" style="margin-top:10px;">';
        echo '<colgroup>';
        echo '<col style="width:auto; min-width:30px;" />';
        echo '<col style="width:auto; min-width:30px; " />';
        for ($i = 0; $i < $cols; $i++) {
            echo '<col style="width:auto; min-width:30px;" />';
            if ($showSingleUnits == false) {
                echo '<col style="width:auto; min-width:30px;" />';
            }
        }
        if ($showSingleUnits == true) {
            echo '<col style="width:auto; min-width:30px;" />';
        }
        echo '</colgroup>';

        echo '<tr>';
        #echo '<td>xx&nbsp;</td>';
        # ime variable
        # teksti labele:
        $label2 = $this->getSpremenljivkaTitle($_means[0]['v2']);
        if ($showSingleUnits == false) {
            $span = ' colspan="5"';
        }
        echo '<td class="anl_bt anl_bl anl_ac rsdl_bck_title ctbCll" rowspan="2">';
        echo $label2;
        echo '</td>';

        for ($i = 0; $i < $cols; $i++) {
            echo '<td class="anl_bt anl_bl anl_br anl_ac rsdl_bck_title ctbCll"' . $span . '>';
            $label1 = $this->getSpremenljivkaTitle($_means[$i]['v1']);
            echo $label1;
            echo '</td>';
        }
        if ($showSingleUnits == true) {
            echo '<td class="anl_bl ">&nbsp;</td>';
        }
        echo '</tr>';
        echo '<tr>';

        for ($i = 0; $i < $cols; $i++) {
            #Povprečje
            echo '<td class="anl_bt anl_bl anl_br anl_ac rsdl_bck_variable1 ctbCll" >';
            echo $lang['srv_means_label'];
            echo '</td>';
            #enote
            if ($showSingleUnits == false) {
                echo '<td class="anl_bl anl_bt anl_br anl_ac red anl_ita anl_bck_text_0 rsdl_bck_variable1 ctbCll">' . $lang['srv_hierarchy_label_st'] . '</td>';
            }

            #Min
            echo '<td class="anl_bt anl_bl anl_br anl_ac rsdl_bck_variable1 ctbCll" >';
            echo $lang['srv_hierarchy_label_min'];
            echo '</td>';

            #Max
            echo '<td class="anl_bt anl_bl anl_br anl_ac rsdl_bck_variable1 ctbCll" >';
            echo $lang['srv_hierarchy_label_max'];
            echo '</td>';

            #Standardna deviacija
            echo '<td class="anl_bt anl_bl anl_br anl_ac rsdl_bck_variable1 ctbCll" >';
            echo $lang['srv_hierarchy_label_std_dev'];
            echo '</td>';
        }
        if ($showSingleUnits == true) {
            echo '<td class="anl_bl anl_bt anl_br anl_ac red anl_ita anl_bck_text_0 rsdl_bck_variable1 ctbCll">' . $lang['srv_hierarchy_label_st'] . '</td>';
        }

        echo '</tr>';

        if (count($_means[0]['options']) > 0) {

            foreach ($_means[0]['options'] as $ckey2 => $crossVariabla2) {

                $units_per_row = 0;
                echo '<tr>';
                echo '<td class="anl_bt anl_bl anl_ac rsdl_bck_variable1 ctbCll">';
                echo $crossVariabla2['naslov'];
                # če ni tekstovni odgovor dodamo key
                if ($crossVariabla2['type'] !== 't') {
                    if ($show_variables_values == true) {
                        if ($crossVariabla2['vr_id'] == null) {
                            echo '&nbsp;( ' . $ckey2 . ' )';
                        } else {
                            echo '&nbsp;( ' . $crossVariabla2['vr_id'] . ' )';
                        }
                    }
                }
                echo '</td>';

                # celice z vsebino
                for ($i = 0; $i < $cols; $i++) {
                    echo '<td class="ct_in_cell anl_bt' . '" k1="' . $ckey1 . '" k2="' . $ckey2 . '" n1="' . $crossVariabla1['naslov'] . '" n2="' . $crossVariabla2['naslov'] . '" v1="' . $crossVariabla1['vr_id'] . '" v2="' . $crossVariabla2['vr_id'] . '">';
                    echo $this->formatNumber($_means[$i]['result'][$ckey2], SurveyDataSettingProfiles:: getSetting('NUM_DIGIT_RESIDUAL'));
                    echo '</td>';
                    if ($showSingleUnits == false) {
                        echo '<td class="anl_ac anl_bl anl_bt anl_br rsdl_bck0 crostabSuma">';
                        echo (int)$_means[$i]['sumaVrstica'][$ckey2];
                        echo '</td>';
                    } else {
                        $units_per_row = max($units_per_row, (int)$_means[$i]['sumaVrstica'][$ckey2]);
                    }

                    #Min - rezultati
                    echo '<td class="anl_ac anl_bl anl_bt anl_br rsdl_bck0">';
                    echo $this->formatNumber($_means[$i]['min'][$ckey2], SurveyDataSettingProfiles:: getSetting('NUM_DIGIT_RESIDUAL'));
                    echo '</td>';

                    #Max - rezultati
                    echo '<td class="anl_ac anl_bl anl_bt anl_br rsdl_bck0">';
                    echo $this->formatNumber($_means[$i]['max'][$ckey2], SurveyDataSettingProfiles:: getSetting('NUM_DIGIT_RESIDUAL'));
                    echo '</td>';

                    #Standardna deviacija - rezultati
                    echo '<td class="anl_ac anl_bl anl_bt anl_br rsdl_bck0">';
                    echo $this->formatNumber($_means[$i]['stdDeviation'][$ckey2], SurveyDataSettingProfiles:: getSetting('NUM_DIGIT_RESIDUAL'));
                    echo '</td>';

                }
                if ($showSingleUnits == true) {
                    echo '<td class="anl_ac anl_bl anl_bt anl_br rsdl_bck0 crostabSuma">';
                    echo $units_per_row;
                    echo '</tr>';
                }
                echo '</tr>';
                $max_units += $units_per_row;
            }
        }
        echo '<tr>';
        echo '<td class="anl_bb anl_bt anl_bl anl_ac red anl_ita anl_bck_text_0 rsdl_bck_variable1 ctbCll">' . $lang['srv_means_label3'] . '</td>';

        for ($i = 0; $i < $cols; $i++) {
            echo '<td class="anl_ac anl_bt anl_bl anl_br anl_bb rsdl_bck0 crostabSuma">';
            echo $this->formatNumber($_means[$i]['sumaMeans'], SurveyDataSettingProfiles:: getSetting('NUM_DIGIT_RESIDUAL'));
            echo '</td>';
            if ($showSingleUnits == false) {
                echo '<td class="anl_ac anl_bt anl_bl anl_br anl_bb rsdl_bck0 crostabSuma">';
                echo (int)$_means[$i]['sumaSkupna'];
                echo '</td>';
            }

            #Skupaj Min
            echo '<td class="anl_ac anl_bt anl_bl anl_br anl_bb rsdl_bck0 crostabSuma">';
            echo $this->formatNumber($_means[$i]['sumaMin'], SurveyDataSettingProfiles:: getSetting('NUM_DIGIT_RESIDUAL'));
            echo '</td>';

            #Skupaj Max
            echo '<td class="anl_ac anl_bt anl_bl anl_br anl_bb rsdl_bck0 crostabSuma">';
            echo $this->formatNumber($_means[$i]['sumaMax'], SurveyDataSettingProfiles:: getSetting('NUM_DIGIT_RESIDUAL'));
            echo '</td>';

            #Skupaj standardna deviacija
            echo '<td class="anl_ac anl_bt anl_bl anl_br anl_bb rsdl_bck0 crostabSuma">';
            echo $this->formatNumber($_means[$i]['sumaStdDeviation'], SurveyDataSettingProfiles:: getSetting('NUM_DIGIT_RESIDUAL'));
            echo '</td>';

        }
        if ($showSingleUnits == true) {
            echo '<td class="anl_ac anl_bt anl_bl anl_br anl_bb rsdl_bck0 crostabSuma">';
            echo $max_units;
            echo '</tr>';
        }

        echo '</tr>';
        echo '</table>';
    }

    /** Sestavi array nepravilnih odgovorov
     *
     */
    function getInvalidAnswers($type)
    {
        $result = array();
        $missingValuesForAnalysis = SurveyMissingProfiles:: GetMissingValuesForAnalysis($type);

        foreach ($missingValuesForAnalysis AS $k => $answer) {
            $result[$k] = array('text' => $answer, 'cnt' => 0);
        }
        return $result;
    }


    /** Naredimo formatiran izpis
     *
     * @param $value
     * @param $digit
     * @param $sufix
     */

    static function formatNumber($value, $digit = 0, $sufix = "")
    {
        if ($value <> 0 && $value != null)
            $result = round($value, $digit);
        else
            $result = "0";

        # polovimo decimalna mesta in vejice za tisočice

        $decimal_point = SurveyDataSettingProfiles:: getSetting('decimal_point');
        $thousands = SurveyDataSettingProfiles:: getSetting('thousands');

        $result = number_format($result, $digit, $decimal_point, $thousands) . $sufix;

        return $result;
    }

    //Dodajamo novo variablo iz spustnega seznama, vendar bo v našem primeru tole zaprto
    function addNewVariable()
    {
        global $lang;
        $which = $_POST['which'];
        $variables = $this->getVariableList($which);
        $multiple = true;

        if ($which == '1') {
            echo '<br/>';
            echo '<span class="space_means_new">&nbsp;</span>';
            echo '<select name="means_variable_' . $which . '" id="means_variable_' . $which . '" onchange="change_hierarhy_means(); return false;" autocomplete="off"'
                . '>';
            # ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
            if ($variabla1['seq'] == null || $variabla1['seq'] == 0) {
                echo '<option value="0" selected="selected" >' . $lang['srv_analiza_crosstab_izberi_more'] . '</option>';
            }

            foreach ($variables as $variable) {
                echo '<option value="' . $variable['sequence'] . '" spr_id="' . $variable['spr_id'] . '" '
                    . (isset($variable['grd_id']) ? ' grd_id="' . $variable['grd_id'] . '" ' : '')
                    . (((int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
                    . '> '
                    . ((int)$variable['sub'] == 0 ? '' : ((int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;'))
                    . $variable['variableNaslov'] . '</option>';

            }
            echo '</select>';
            echo '<span class="pointer" id="means_remove" onclick="hierarhy_means_remove_variable(this);"><span class="faicon delete_circle icon-orange_link" title=""></span></span>';

        } else {
            # which = 2
            echo '<br/>';
            echo '<span class="space_means_new">&nbsp;</span>';
            echo '<select name="means_variable_' . $which . '" id="means_variable_' . $which . '" onchange="change_hierarhy_means(); return false;" autocomplete="off"'
                . '>';

            # ce prva variabla ni izbrana, dodamo tekst za izbiro prve variable
            if ((int)$this->variabla1['0']['seq'] > 0) {
                echo '<option value="0" selected="selected" >' . $lang['srv_analiza_crosstab_najprej_prvo'] . '</option>';
            } else {
                # če druga variabla ni izbrana dodamo tekst za izbiro druge variable
                echo '<option value="0" selected="selected">' . $lang['srv_analiza_crosstab_izberi_more'] . '</option>';
            }

            foreach ($variables as $variable) {
                echo '<option value="' . $variable['sequence'] . '" spr_id="' . $variable['spr_id'] . '" '
                    . (isset($variable['grd_id']) ? ' grd_id="' . $variable['grd_id'] . '" ' : '')
                    . (((int)$variable['canChoose'] == 1) ? '' : ' disabled="disabled" ')
                    . '> '
                    . ((int)$variable['sub'] == 0 ? '' : ((int)$variable['sub'] == 1 ? '&nbsp;&nbsp;' : '&nbsp;&nbsp;&nbsp;&nbsp;'))
                    . $variable['variableNaslov'] . '</option>';

            }
            echo '</select>';
            echo '<span class="pointer" id="means_remove" onclick="hierarhy_means_remove_variable(this);"><span class="faicon delete_circle icon-orange_link" title=""></span></span>';
        }
    }

    function getSpremenljivkaTitle($v_first)
    {
        global $lang;
        # podatki spremenljivk
        $spremenljivka_id = $v_first['spr'];
        $grid_id = $v_first['grd'];
        $sekvenca = $v_first['seq'];

        $spremenljivka = $this->_HEADERS[$spremenljivka_id];
        $grid = $spremenljivka['grids'][$grid_id];


        # za multicheckboxe popravimo naslov, na podtip
        $labela = null;
        if ($spremenljivka['tip'] == '6' || $spremenljivka['tip'] == '7' || $spremenljivka['tip'] == '16' || $spremenljivka['tip'] == '17' || $spremenljivka['tip'] == '18' || $spremenljivka['tip'] == '19' || $spremenljivka['tip'] == '20' || $spremenljivka['tip'] == '21') {
            foreach ($spremenljivka['grids'] AS $grids) {
                foreach ($grids['variables'] AS $variable) {
                    if ($variable['sequence'] == $sekvenca) {
                        $labela .= '<span class="anl_variabla">';
                        $labela .= '<a href="/" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="showspremenljivkaSingleVarPopup(\'' . $spremenljivka_id . '\'); return false;">';
                        $labela .= strip_tags($spremenljivka['naslov']);
                        if ($show_variables_values == true) {
                            $labela .= '&nbsp;(' . strip_tags($spremenljivka['variable']) . ')';
                        }
                        $labela .= '</a>';
                        $labela .= '</span>';

                        if ($spremenljivka['tip'] == '16') {
                            if (strip_tags($grid['naslov']) != $lang['srv_new_text']) {
                                $labela .= '<br/>' . strip_tags($grid['naslov']);
                            }
                            $labela .= '&nbsp;(' . strip_tags($grid['variable']) . ')';
                        } else {
                            if (strip_tags($variable['naslov']) != $lang['srv_new_text']) {
                                $labela .= '<br/>' . strip_tags($variable['naslov']);
                            }
                            if ($show_variables_values == true) {
                                $labela .= '&nbsp;(' . strip_tags($variable['variable']) . ')';
                            }
                        }

                    }
                }
            }
        }
        if ($labela == null) {
            $labela = '<span class="anl_variabla">';
            $labela .= '<a href="/" title="' . $lang['srv_predogled_spremenljivka'] . '" onclick="showspremenljivkaSingleVarPopup(\'' . $spremenljivka_id . '\'); return false;">';
            $labela .= strip_tags($spremenljivka['naslov']);
            if ($show_variables_values == true) {
                $labela .= '&nbsp;(' . strip_tags($spremenljivka['variable']) . ')';
            }
            $labela .= '</a>';
            $labela .= '</span>' . NEW_LINE;
        }
        return $labela;
    }

    function changeMeansSubSetting()
    {
        $this->sessionData['means']['meansSeperateTables'] = ($_POST['chkMeansSeperate'] == 1);
        $this->sessionData['means']['meansJoinPercentage'] = ($_POST['chkMeansJoinPercentage'] == 1);

        // Shranimo spremenjene nastavitve v bazo
        SurveyUserSession::saveData($this->sessionData);
    }

    function changeMeansShowChart()
    {
        $this->sessionData['mean_charts']['showChart'] = ($_POST['showChart'] == 'true');
        $this->sessionData['means']['meansSeperateTables'] = ($_POST['showChart'] == 'true') ? true : $this->sessionData['means']['meansSeperateTables'];
        $this->sessionData['means']['meansJoinPercentage'] = ($_POST['showChart'] == 'true') ? true : $this->sessionData['means']['meansJoinPercentage'];

        // Shranimo spremenjene nastavitve v bazo
        SurveyUserSession::saveData($this->sessionData);
    }


    function presetVariables()
    {
        # preberemo prednastavljene variable iz seje, če obstajajo
        if (isset($this->sessionData['means']['means_variables']['variabla1']) && count($this->sessionData['means']['means_variables']['variabla1']) > 0) {
            $this->variabla1 = $this->sessionData['means']['means_variables']['variabla1'];
        }

        if (isset($this->sessionData['means']['means_variables']['variabla2']) && count($this->sessionData['means']['means_variables']['variabla2']) > 0) {

            $this->variabla2 = $this->sessionData['means']['means_variables']['variabla2'];

        }
    }
}