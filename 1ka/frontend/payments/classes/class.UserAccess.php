<?php

/**
 *
 *  Class ki vsebuje skrbi za pregled in urejanje dostopa uporabnika glede na paket ki ga placuje
 *
 */

class UserAccess{


    private static $instance = false;

    private $usr_id;
    private $user_access;
    private $user_not_author = false;

    private $anketa_old = false;                // Pri starih anketah ne upostevamo paketov in so na voljo vse funkcionalnosti
    private $anketa_old_date = '2020-07-01';    // Anketa je stara, ce je bila ustvarjena pred tem datumom

    // Array z vsemi paketi
    private $packages = array();

    // Array z vsemi funkcionalnostmi, ki so placljive (in v katerem paketu so)
    private $functionality_package = array(

        /* Urejanje ankete */
        'question_type_multitable'  => 2,   // Tip vprasanja - kombinirana tabela - 24
        'question_type_ranking'     => 2,   // Tip vprasanja - razvrscanje - 17
        'question_type_sum'         => 2,   // Tip vprasanja - vsota - 18
        'question_type_location'    => 2,   // Tip vprasanja - lokacija - 26
        'question_type_heatmap'     => 2,   // Tip vprasanja - heatmap - 27
        'question_type_calculation' => 3,   // Tip vprasanja - kalkulacija - 22
        'question_type_quota'       => 3,   // Tip vprasanja - kvota - 25
        'question_type_signature'   => 3,   // Tip vprasanja - podpis - 21_6
        'loop'                      => 3,   // Zanke
        'if'                        => 2,   // If-i
        'block'                     => 2,   // Bloki
        'validation'                => 2,   // Validacija
        'theme-editor'              => 2,   // Urejanje teme ankete, upload logotipa...
        'theme-editor_css'          => 3,   // Urejanje lastnega css-ja !!!
        'theme-editor_upload'       => 3,   // Urejanje lastnega css-ja !!!

        /* Status */
        'para_graph'                => 2,   // Statistika naprav
        'geoip_location'            => 3,   // Statistika ip lokacije
        'nonresponse_graph'         => 3,   // Neodgovor spremenljivke
        'speeder_index'             => 3,   // Speeder index
        'usable_resp'               => 3,   // Uporabni respondenti
        'text_analysis'             => 3,   // Analiza besedil
        'edits_analysis'            => 3,   // Analiza urejanj

        /* Podatki */
        'data_export'               => 2,   // Izvoz podatkov - spss, xls, csv...
        'data_append'               => 3,   // Uvoz - dodaj podatke
        'data_merge'                => 3,   // Uvoz - zdruzi podatke
        'data_calculation'          => 3,   // Izracunane vrednosti
        'data_coding_auto'          => 3,   // Avtomatsko kodiranje
        'data_coding'               => 3,   // Kodiranje
        'data_recoding'             => 3,   // Rekodiranje
        
        /* Analiza */
        'analysis_export'           => 2,   // Izvoz analiz - pdf, rtf, xls
        'analysis_analysis_links'   => 2,   // Javne
        'analysis_charts'           => 2,   // Grafi
        'analysis_crosstabs'        => 2,   // Tabele
        'analysis_break'            => 2,   // Razbitje
        'analysis_ttest'            => 3,   // Ttest
        'analysis_means'            => 3,   // Povprecje
        'analysis_multicrosstabs'   => 3,   // Multitabele
        'analysis_analysis_creport' => 3,   // Porocilo po meri

        /* Napredni moduli */
        'uporabnost'                => 3,   // Evalvacija strani (split screen)
        //'vnos'                      => 2,   // Vnos vprasalnikov
        'kviz'                      => 2,   // Kviz
        'social_network'            => 3,   // Socialna omrezja
        'slideshow'                 => 3,   // Prezentacija
        'telephone'                 => 3,   // Telefonska anketa
        'chat'                      => 3,   // Chat
        'panel'                     => 3,   // Panel

        /* Ostale funkcionalnosti */
        'prevajanje'                => 2,   // Vecjezikovna anketa
        'export'                    => 2,   // Izvozi ankete
        'filters'                   => 2,   // Filtriranje podatkov in analiz
        'nice_url'                  => 2,   // Lepi linki
        'password'                  => 2,   // Dostop do ankete z geslom
        'gdpr_export'               => 2,   // Izvoz porocil evidenc za gdpr
        'skupine'                   => 2,   // Skupine
        'archive'                   => 2,   // Arhiviranje
        'arhivi'                    => 2,   // Arhiviranje - izvoz datoteke ankete, podatkov
        //'arhivi_export'             => 2,   // Arhiviranje - izvoz datoteke vprasalnika in vprasalnika s podatki
        'analysis_anal_arch'        => 2,   // Arhiviranje analiz
        'public_link'               => 3,   // Javne povezave

        //'ustvari_anketo_archive'    => 2,   // Ustvarjanje ankete iz datoteke
        'ustvari_anketo_from_text'  => 2,   // Ustvarjanje ankete iz besedila
        'ustvari_anketo_template'   => 2,   // Ustvarjanje ankete iz predloge

        'komentarji'                => 3,   // Komentarji na anketo
        'komentarji_anketa'         => 3,   // Komentarji na anketo
        'urejanje'                  => 3,   // Komentarji na anketo

        'alert'                     => 2,   // Obvescanje
        'invitations'               => 3,   // Email vabila
        
        /* Moje ankete */
        'my_survey_folders'         => 2,   // Mape v mojih anketah
    );

	
    public static function getInstance($usr_id){
        
        if (!self::$instance)
			self::$instance = new UserAccess($usr_id);
			
		return self::$instance;
	}

    private function __construct($usr_id){
        global $app_settings;

        // Ce so paketi onemogoceni nič ne preverjamo
        if(!isset($app_settings['commercial_packages']) || $app_settings['commercial_packages'] == false){
            return;
        }

        // Ce nimamo usr_id-ja ga poskusimo pridobiti iz id-ja ankete
        if(!isset($usr_id) || $usr_id < 1 || $usr_id == ''){

            if(isset($_POST['anketa']) || isset($_GET['anketa'])){

                $ank_id = (isset($_GET['anketa'])) ? $_GET['anketa'] : $_POST['anketa'];
    
                $sqlU = sisplet_query("SELECT insert_uid FROM srv_anketa WHERE id='".$ank_id."'");
                $rowU = mysqli_fetch_array($sqlU);
    
                $usr_id = $rowU['insert_uid'];
            }
            else{
                $usr_id = 0;
            }
        }

        if($usr_id > 0){
            $this->usr_id = $usr_id;

            // Preverimo, ce smo znotraj dolocene ankete in ce je usr_id enak id-ju avtorja ankete
            $this->checkSurveyAuthor();

            // Zakesiramo vse dostope userja
            $this->cacheUserAccess();
            
            // Zakesiramo vse pakete
            $this->cachePackages();

            // Pogledamo ce smo v anketi in ce gre za staro anketo - stare ankete nimajo vklopljenih paketov
            $this->checkAnketaOld();
        }
        else{
            echo 'Napaka! Manjka ID uporabnika.';
            die();
        }
    }



    // Dobimo podatke o dostopu za posameznega uporabnika
    private function cacheUserAccess(){

        $sqlUserAccess = sisplet_query("SELECT ua.*, up.name AS package_name, up.description AS package_description, up.price AS package_price
                                            FROM user_access ua, user_access_paket up 
                                            WHERE ua.usr_id='".$this->usr_id."' AND up.id=ua.package_id
                                        ");

        // Uporabnik se nima nobenega paketa
        if(mysqli_num_rows($sqlUserAccess) == 0)
            return;

        $rowUserAccess = mysqli_fetch_array($sqlUserAccess);

        // Dodatno preverimo, ce je paket ze potekel
        if(strtotime($rowUserAccess['time_expire']) < time())
            return;

        // Vse ok - uporabniku nastavimo trenuten paket
        $this->user_access = $rowUserAccess;
    }

    // Dobimo podatke o vseh paketih
    private function cachePackages(){

        $sqlPackages = sisplet_query("SELECT * FROM user_access_paket");
        while($row = mysqli_fetch_array($sqlPackages)){
            $this->packages[$row['id']] = $row;
        }
    }

    // Pogledamo ce smo v anketi in ce gre za staro anketo - stare ankete nimajo vklopljenih paketov
    private function checkAnketaOld(){
        
        // Ce nismo znotraj ankete ti ignoriramo
        if(!isset($_GET['anketa'])){
            return;
        }

        // Nastavimo id ankete
        $ank_id = $_GET['anketa'];

        $sqlA = sisplet_query("SELECT insert_time FROM srv_anketa WHERE id='".$ank_id."'");
        $rowA = mysqli_fetch_array($sqlA);

        // Ce je datum kreiranja starejši je stara anketa
        if(strtotime($rowA['insert_time']) < strtotime($this->anketa_old_date)){
            $this->anketa_old = true;
        }
    }

    public function isAnketaOld(){
        return $this->anketa_old;
    }

    public function userNotAuthor(){
        return $this->user_not_author;
    }


    // Preverimo ce ima uporabnik dostop do neke funkcionalnosti
    public function checkUserAccess($what=''){
        global $app_settings;
        global $admin_type;
        global $global_user_id;
        global $mysql_database_name;

        // Ce so paketi onemogoceni vrnemo vedno true
        if(!isset($app_settings['commercial_packages']) || $app_settings['commercial_packages'] == false){
            return true;
        }

        // Ce nimamo usr_id-ja zaenkrat pustimo vse
        if(!isset($this->usr_id) || $this->usr_id < 1 || $this->usr_id == ''){
            return true;
        }

        // Ce je metaadmin ali admin enklikanketa@gmail.com lahko tudi vedno vse uporablja
        if(Dostop::isMetaAdmin() || ($mysql_database_name == 'real1kasi' && $admin_type == 0 && $global_user_id == '440')){
            return true;
        }

        // Ce je anketa ustvarjena pred nekim datumom, ne preverjamo paketov
        if($this->anketa_old == true){
            return true;
        }
        

        // Ce ne nastavimo funkcionalnosti pogledamo url kje se nahajamo
        if($what == ''){
            $what = $this->getFunctionalityFromUrl();
        }

        // Preverimo, ce funkcionalnost ni v paketu, ki ga ima uporabnik
        $package_id = $this->getPackage();
        if(isset($this->functionality_package[$what]) && $this->functionality_package[$what] > $package_id){
            return false;
        }
        
        return true;
    }

    // Vrnemo vse podatke o dostopu uporabnika
    public function getAccess(){
        
        return $this->user_access;
    }

    // Vrnemo paket uporabnika
    public function getPackage(){
        global $app_settings;
        global $admin_type;
    
        // Ce so paketi onemogoceni vrnemo -1
        if(!isset($app_settings['commercial_packages']) || $app_settings['commercial_packages'] == false){
            return -1;
        }

        // Ce je admin ali manager lahko tudi vedno vse uporablja
        /*if($admin_type == 0 || $admin_type == 1){
            return 3;
        }*/
        
        // Ce nima nastavljeno nic je brez paketa
        if(!isset($this->user_access['package_id']))
            return 1;

        return $this->user_access['package_id'];
    }

    // Vrnemo aray vseh paketov
    public function getPackages(){
        return $this->packages;
    }

    // Preverimo, ce smo znotraj dolocene ankete in ce je usr_id enak id-ju avtorja ankete
    private function checkSurveyAuthor(){

        // Nastavimo id ankete
        if(isset($_GET['anketa'])){
            $ank_id = $_GET['anketa'];
        }
        elseif(isset($_POST['anketa']) && $_POST['anketa'] != 'undefined'){
            $ank_id = $_POST['anketa'];
        }
        // Ce nismo znotraj ankete je vse ok
        else{
            return;
        }

        // Ce smo znotraj ankete, preverimo, ce je usr_id enak avtorju ankete
        $sqlA = sisplet_query("SELECT insert_uid FROM srv_anketa WHERE id='".$ank_id."'");
        if(mysqli_num_rows($sqlA) > 0){
            $rowA = mysqli_fetch_array($sqlA);

            // Ce user ni avtor, preverjamo za avtorja
            if($this->usr_id != $rowA['insert_uid']){
                $this->usr_id = $rowA['insert_uid'];
                $this->user_not_author = true;
            }
        }

        return;
    }


    // Izpisemo obvestilo, da je funkcionalnost onemogocena in naj kupi paket
    public function displayNoAccess($what=''){
        global $lang;
        global $site_url;

        // Ce ne nastavimo funkcionalnosti pogledamo url kje se nahajamo
        if($what == ''){
            $what = $this->getFunctionalityFromUrl();
        }

        // Kateri paket je potreben za to funkcionalnost
        $package_required = (isset($this->functionality_package[$what])) ? $this->functionality_package[$what] : 3;
        $package_required_name = $this->packages[$package_required]['name'];

        if($lang['id'] == '2') 
            $drupal_url = $site_url.'d/en/purchase/'.$package_required.'/package';
        else
            $drupal_url = $site_url.'d/izvedi-nakup/'.$package_required.'/podatki';

        echo '<div class="user_access_warning">';

        // Ce user ni avtor ankete
        if($this->user_not_author){
            echo '<p>'.$lang['srv_access_no_access_not_author'].'</p>';
        }

        echo '<p>'.$lang['srv_access_no_access'].' "'.$package_required_name.'".</p>';
        if(!$this->user_not_author)
            echo '<span class="floatLeft"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_blue" href="'.$drupal_url.'" target="_blank">'.$lang['srv_narocila_buy'].'</a></div></span>';

        echo '</div>';
    }

    // Izpisemo popup obvestilo, da je funkcionalnost onemogocena in naj kupi paket
    public function displayNoAccessPopup($what){
        global $lang;
        global $site_url;

        // Kateri paket je potreben za to funkcionalnost
        $package_required = (isset($this->functionality_package[$what])) ? $this->functionality_package[$what] : 3;
        $package_required_name = $this->packages[$package_required]['name'];

        if($lang['id'] == '2') 
            $drupal_url = $site_url.'d/en/purchase/'.$package_required.'/package';
        else
            $drupal_url = $site_url.'d/izvedi-nakup/'.$package_required.'/podatki';

        // Ce user ni avtor ankete
        if($this->user_not_author){
            echo '<p>'.$lang['srv_access_no_access_not_author'].'</p>';
        }

        echo '<p>'.$lang['srv_access_no_access'].' "'.$package_required_name.'".</p>';
        if(!$this->user_not_author)
            echo '<span class="floatRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_blue" href="'.$drupal_url.'" target="_blank">'.$lang['srv_narocila_buy'].'</a></div></span>';
        echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onClick="popupUserAccess_close();">'.$lang['srv_zapri'].'</a></div></span>';
    }

    // Izpisemo obvestilo, da je funkcionalnost onemogocena in naj kupi paket
    public function displayNoAccessText($what=''){
        global $lang;

        // Ce ne nastavimo funkcionalnosti pogledamo url kje se nahajamo
        if($what == ''){
            $what = $this->getFunctionalityFromUrl();
        }

        // Kateri paket je potreben za to funkcionalnost
        $package_required = (isset($this->functionality_package[$what])) ? $this->functionality_package[$what] : 3;
        $package_required_name = $this->packages[$package_required]['name'];

        echo '<p class="user_access_warning_text">';

        // Ce user ni avtor ankete
        if($this->user_not_author){
            echo $lang['srv_access_no_access_not_author'].'<br /><br />';
        }

        echo $lang['srv_access_no_access'].' "'.$package_required_name.'"';

        echo '</p>';
    }


    // Vrnemo funkcionalnost glede na urle kjer se nahajamo
    private function getFunctionalityFromUrl(){

        $what = '';

        // Ce ne nastavimo funkcionalnosti pogledamo url kje se nahajamo
        if(isset($_GET['a'])){

            $what = $_GET['a'];

            if($_GET['a'] == 'analysis' || $_GET['a'] == 'data'){

                if(isset($_GET['m'])){
                    $what .= '_'.$_GET['m'];
                }
            }
            elseif($_GET['a'] == 'ustvari_anketo'){
                
                if(isset($_GET['b'])){
                    $what .= '_'.$_GET['b'];
                }
            } 
            elseif($_GET['a'] == 'theme-editor'){
                
                if(isset($_GET['t'])){
                    $what .= '_'.$_GET['t'];
                }
            } 
            elseif($_GET['a'] == 'langStatistic'){
                $what = 'prevajanje';
            }
            elseif($_GET['a'] == 'skupine'){
                $what = '';
            }
            elseif($_GET['a'] == 'arhivi' && isset($_GET['m']) && ($_GET['m'] == 'survey_data' || $_GET['m'] == 'survey')){
                $what .= '_export';
            }
        }

        return $what;
    }


    // Ajax klici
    public function ajax(){

        if (isset($_POST['what']))
			$what = $_POST['what'];


        // Prikazemo popup z obvestilom, da je funkcionalnost onemogocena in naj kupi paket
        if($_GET['a'] == 'displayNoAccessPopup') {
            $this->displayNoAccessPopup($what);
        }
    }
}