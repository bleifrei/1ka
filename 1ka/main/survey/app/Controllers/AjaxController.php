<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 09.03.2016
 *****************************************/

namespace App\Controllers;

// Osnovni razredi
use App\Controllers\StatisticController as Statistic;
use App\Controllers\Vprasanja\VprasanjaController as Vprasanja;
use App\Models\Model;
use Common;
use Branching;
use SurveyAdvancedParadataLog;
use SurveySetting;
use MailAdapter;
use GDPR;


class AjaxController extends Controller
{
    //ajax zahteve
    public function __construct()
    {
        if(empty($_GET['a'])){
			return '';
		}
        if ($_GET['a'] == 'accept_droppable') {
            $this->ajax_accept_droppable();
        } elseif ($_GET['a'] == 'accept_ranking') {
            $this->ajax_accept_ranking();
        } elseif ($_GET['a'] == 'accept_dragdrop1') {
            $this->ajax_accept_dragdrop1();
        } elseif ($_GET['a'] == 'get_dragdrop1_data') {
            $this->ajax_get_dragdrop1_data();
        } elseif ($_GET['a'] == 'delete_dragdrop1_data') {
            $this->ajax_delete_dragdrop1_data();
        } elseif ($_GET['a'] == 'delete_dragdrop2_data') {
            $this->ajax_delete_dragdrop2_data();
        } elseif ($_GET['a'] == 'accept_dragdrop_grid') {
            $this->ajax_accept_dragdrop_grid();
        } elseif ($_GET['a'] == 'accept_sortable_ranking') {
            $this->ajax_accept_sortable_ranking();
        } elseif ($_GET['a'] == 'edit_size') {
            $this->ajax_edit_size();
        } elseif ($_GET['a'] == 'dodaj_ime') {
            $this->ajax_dodaj_ime();
        } elseif ($_GET['a'] == 'spol') {
            $this->ajax_glasovanje_spol();
        } elseif ($_GET['a'] == 'vote_spol') {
            $this->ajax_glasovanje_vote_spol();
        } elseif ($_GET['a'] == 'captcha') {
            $this->ajax_captcha();
        } elseif ($_GET['a'] == 'skin') {
            $this->ajax_skin();
        } elseif ($_GET['a'] == 'grupa_for_if') {
            $this->ajax_grupa_for_if();
        } elseif ($_GET['a'] == 'enable_comments') {
            $this->ajax_enable_comments();
        } elseif ($_GET['a'] == 'continue_later') {
            $this->ajax_continue_later();
        } elseif ($_GET['a'] == 'continue_later_send') {
            $this->ajax_continue_later_send();
        } elseif ($_GET['a'] == 'usr_id_data') {
            $this->ajax_delete_signature_data();
		}elseif ($_GET['a'] == 'get_tip_opozorila') {
            $this->ajax_get_tip_opozorila();
        } // genericna resitev za vse nadaljne
        else {
            $ajax = 'ajax_' . $_GET['a'];
            if (method_exists($this, $ajax))
                return $this->$ajax ();
            else
                echo 'method ' . $ajax . ' does not exist';
        }
    }

    /************************************************
     * Get instance
     ************************************************/
    private static $_instance;

    public static function getInstance()
    {
        if (self::$_instance)
            return self::$_instance;

        return new AjaxController();
    }

    public function ajax_delete_signature_data()
    {

        $usr_id = $_POST['usr_id'];
        $anketa = $_POST['anketa'];
        $spr_id = $_POST['spr_id'];
        $vre_id = $_POST['vre_id'];


        //$sqlsignaturefilename = sisplet_query("SELECT filename FROM srv_data_upload WHERE usr_id = '" . $usr_id . "'");
        $sqlsignaturefilename = sisplet_query("SELECT filename FROM srv_data_upload WHERE usr_id = '" . $usr_id . "' AND code = '" . $spr_id . "' ");

        if (mysqli_num_rows($sqlsignaturefilename) > 0) {
            $rowSignatureFilename = mysqli_fetch_array($sqlsignaturefilename);
            $file_2_delete = $rowSignatureFilename[0];
        }

        //echo $file_2_delete;

        //$path = self::$site_url.'main/survey/uploads/'.$file_2_delete; //tale varianta ne omogoča brisanje na disku
        $path = survey_path('uploads/' . $file_2_delete);

        //echo $path;

        if (is_file($path)) {    //če slikovna datoteka podpisa obstaja
            unlink($path);        //zbriši datoteko
        }

        //sisplet_query("DELETE FROM srv_data_upload WHERE usr_id='" . get('usr_id') . "' AND ank_id='$anketa'");//zbriši iz baze info datoteki podpisa
        sisplet_query("DELETE FROM srv_data_upload WHERE usr_id='" . get('usr_id') . "' AND ank_id='$anketa' AND code='$spr_id'");//zbriši iz baze info datoteki podpisa
        sisplet_query("DELETE FROM srv_data_text" . get('db_table') . " WHERE usr_id='" . get('usr_id') . "' AND spr_id='$spr_id' AND vre_id='$vre_id'");//zbriši iz baze info o vnesenem imenu
    }

    // sprememba skina
    public function ajax_skin()
    {
        $mobile = (int)$_GET['mobile'];
        setcookie('mobile', $mobile, 0, '/');
        header("Location: " . $_SERVER['HTTP_REFERER']);
    }

    //za izris vprasanj pri antonucci designu 1
    public function ajax_edit_size()
    {
        $spremenljivka = $_POST['spremenljivka'];
        $size = $_POST['size'];

        //sisplet_query("UPDATE srv_spremenljivka SET size='$size' WHERE id='$spremenljivka'");

        for ($i = 0; $i < $size; $i++) {
            echo '      <p><input type="text" name="vrednost_' . $spremenljivka . '[]" id="vrednost_' . $spremenljivka . '" size="40" onkeyup="checkBranching();" value=""></p>' . "\n";
        }
    }

    //antonucci design 2
    public function ajax_dodaj_ime()
    {
        $spremenljivka = $_POST['spremenljivka'];
        $ime = $_POST['ime'];

        //sisplet_query("UPDATE srv_spremenljivka SET size='$size' WHERE id='$spremenljivka'");

        echo '      <p><input type="text" value="' . $ime . '" disabled=true name="vrednost_' . $spremenljivka . '[]" id="vrednost_' . $spremenljivka . '" size="40" onkeyup="checkBranching();" value=""></p>' . "\n";

        echo '      <p><input type="text" size="40" disabled=true></p>' . "\n";
        echo '      <p><input type="text" size="40" disabled=true></p>' . "\n";
        echo '      <p><input type="text" size="40" disabled=true></p>' . "\n";

    }

    public function ajax_accept_droppable()
    {

        $child = $_POST['child'];
        $parent = $_POST['parent'];


        if ($parent == 'half2_1') {
            //poiscemo usr_id
            $sql0 = sisplet_query("SELECT usr_id FROM srv_data_imena WHERE id='$child'");
            $row0 = mysqli_fetch_array($sql0);
            $usr_id = $row0[0];

            //poiscemo najvecji count
            $sql = sisplet_query("SELECT MAX(countE) FROM srv_data_imena WHERE (usr_id='" . get('usr_id') . "' AND (emotion=1 AND emotionINT=0))");
            if (mysqli_num_rows($sql) > 0) {
                $row = mysqli_fetch_array($sql);
                $count = $row['MAX(countE)'];
            }

            $count++;

            //update baze
            sisplet_query("UPDATE srv_data_imena SET emotion=1, countE='$count' WHERE id='$child'");
        } elseif ($parent == 'half2_2') {
            //poiscemo usr_id
            $sql0 = sisplet_query("SELECT usr_id FROM srv_data_imena WHERE id='$child'");
            $row0 = mysqli_fetch_array($sql0);
            $usr_id = $row0[0];

            //poiscemo najvecji count
            $sql = sisplet_query("SELECT MAX(countS) FROM srv_data_imena WHERE (usr_id='" . get('usr_id') . "' AND (social=1 AND socialINT=0))");
            if (mysqli_num_rows($sql) > 0) {
                $row = mysqli_fetch_array($sql);
                $count = $row['MAX(countS)'];
            }

            $count++;

            //update baze
            sisplet_query("UPDATE srv_data_imena SET social=1, countS='$count' WHERE id='$child'");
        } elseif ($parent == 'half2_3') {
            $count = 1;

            //update baze
            sisplet_query("UPDATE srv_data_imena SET emotionINT=1, countE='$count' WHERE id='$child'");
        } elseif ($parent == 'half2_4') {
            $count = 1;

            //update baze
            sisplet_query("UPDATE srv_data_imena SET socialINT=1, countS='$count' WHERE id='$child'");
        } //leva stran - brisemo iz baze (emotion)
        elseif ($parent == 'half_1') {
            //preuredimo ostale counterje
            $sql = sisplet_query("SELECT countE, usr_id FROM srv_data_imena WHERE id='$child'");
            $row = mysqli_fetch_array($sql);

            $counter = $row[0];
            $usr_id = $row[1];

            $sql1 = sisplet_query("SELECT * FROM srv_data_imena WHERE usr_id='" . get('usr_id') . "' AND (countE > '$counter') ORDER BY countE");
            while ($row1 = mysqli_fetch_array($sql1)) {
                $count = $row1['countE'];
                $count--;
                sisplet_query("UPDATE srv_data_imena SET countE='$count' WHERE id='$row1[id]'");
            }

            //update baze
            sisplet_query("UPDATE srv_data_imena SET emotion=0, countE=0 WHERE id='$child'");
        } //leva stran - brisemo iz baze (druzenje)
        elseif ($parent == 'half_2') {
            //preuredimo ostale counterje
            $sql = sisplet_query("SELECT countS, usr_id FROM srv_data_imena WHERE id='$child'");
            $row = mysqli_fetch_array($sql);

            $counter = $row[0];
            $usr_id = $row[1];

            $sql1 = sisplet_query("SELECT * FROM srv_data_imena WHERE usr_id='" . get('usr_id') . "' AND (countS > '$counter') ORDER BY countS");
            while ($row1 = mysqli_fetch_array($sql1)) {
                $count = $row1['countS'];
                $count--;
                sisplet_query("UPDATE srv_data_imena SET countS='$count' WHERE id='$row1[id]'");
            }

            //update baze
            sisplet_query("UPDATE srv_data_imena SET social=0, countS=0 WHERE id='$child'");
        } //leva stran - brisemo iz baze (emotionINT)
        elseif ($parent == 'half_3') {
            //update baze
            sisplet_query("UPDATE srv_data_imena SET emotionINT=0, countE=0 WHERE id='$child'");
        } //leva stran - brisemo iz baze (druzenjeINT)
        elseif ($parent == 'half_4') {
            //update baze
            sisplet_query("UPDATE srv_data_imena SET socialINT=0, countS=0 WHERE id='$child'");
        }


        //izpisemo ime v divu po loadu
        $sql1 = sisplet_query("SELECT text FROM srv_data_imena WHERE id='$child'");
        $value = mysqli_fetch_array($sql1);

        echo $value[0];

    }

    /**
     * @desc poskrbi za shranjevanje vrednosti 99,98,97
     */

    //zapisovanje v bazo pri ranking dropih (n>k, n=k)
    public function ajax_accept_ranking()
    {


        Model::user_not_lurker();

        $spremenljivka = $_POST['spremenljivka'];
        $usr_id = $_POST['usr_id'];
        $order = $_POST['order'];

        // Popravimo da se po ajax-u ohrani jezik
        if (isset($_POST['lang_id'])) {
            $lang_id = save('lang_id', $_POST['lang_id'], 1);

            $file = lang_path($lang_id);
            if (@include($file))
                $_SESSION['langX'] = lang_path($lang_id, 1);
        }

        // Napredni parapodatki - za ranking je lažje če je kar tukaj
        /*if (SurveyAdvancedParadataLog::getInstance()->paradataEnabled()){
        	SurveyAdvancedParadataLog::getInstance()->logData(0, $usr_id, 'data change', '', $spremenljivka, $order);
		}*/

        $exploded = explode('&', $order);

        //najprej pobrisemo iz baze vse vnose
        sisplet_query("DELETE FROM srv_data_rating WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "'");

        //sortiranje (vnos v srv_data_rating)
        $i = 1;
        foreach ($exploded AS $key) {
            $key = str_replace('handle_', '', $key);
            $explode = explode('[]=', $key);

            if ($explode[1] != 0)
                sisplet_query("INSERT INTO srv_data_rating (spr_id, vre_id, usr_id, vrstni_red) VALUES ('$spremenljivka', '$explode[1]', '" . get('usr_id') . "', '$i')");

            $i++;
        }

        Vprasanja::getInstance()->displayVnos($spremenljivka);

        echo '<div id="clr" class="clr"></div>';

    }

    //zapisovanje v bazo in brisanje pri drag drop
    public function ajax_accept_dragdrop1()
    {
        Model::user_not_lurker();

        $spremenljivka = $_POST['spremenljivka'];
        $vre_id = $_POST['vre_id'];
        $usr_id = $_POST['usr_id'];
        $anketa = $_POST['anketa'];
        $data_calculation = $_POST['data_calculation'];
        $tip = $_POST['tip'];
        $cat_right = $_POST['cat_right'];
        $other_present = $_POST['other_present'];
        $other = $_POST['other'];

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        //( ( (tip == 1 && other == 0 && cat_right) ) || ( (tip == 2) && (other_present != 0) ) || ( (other != 0) ) )
        if (($tip == 1 && $cat_right && $other == 0) || (($tip == 2) && ($other_present != 0)) || (($other != 0))) {
            sisplet_query("DELETE FROM srv_data_vrednost" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "'");
        }
        sisplet_query("INSERT INTO srv_data_vrednost" . get('db_table') . " (spr_id, vre_id, usr_id) VALUES ('$spremenljivka', '$vre_id', '" . get('usr_id') . "')");
    }

    //asinhrono pobiranje podatkov za ureditev missing
    public function ajax_get_dragdrop1_data()
    {
        Model::user_not_lurker();
        $anketa = $_GET['anketa'];
        $spremenljivka = $_GET['spremenljivka'];
        $vre_id = array();
        //echo 'Spremenljivka: '.$spremenljivka;
        //echo 'Anketa: '.$anketa;
        //$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$spremenljivka' AND other!=0 ");
        $sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$spremenljivka'");
        $num = mysqli_num_rows($sql1);

        /* 		echo '
                    <script>
                        console.log('.$num.');
                    </script>
                '; */

        while ($row1 = mysqli_fetch_array($sql1)) {
            //$vre_id[$i] = $row1['id'];
            array_push($vre_id, $row1['id']);
        }
        //echo 'Podatek je: '.$num;
        //echo $vre_id;
        echo json_encode($vre_id);

    }

    //brisanje odgovorov iz baze pri drag drop
    public function ajax_delete_dragdrop1_data()
    {
        Model::user_not_lurker();

        $spremenljivka = $_POST['spremenljivka'];
        $usr_id = $_POST['usr_id'];
        $vre_id = $_POST['vre_id'];
        $anketa = $_POST['anketa'];

        sisplet_query("DELETE FROM srv_data_vrednost" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND vre_id='$vre_id'");

    }

    //brisanje odgovorov iz baze pri drag drop, ko imamo missing-e
    public function ajax_delete_dragdrop2_data()
    {
        Model::user_not_lurker();

        $spremenljivka = $_POST['spremenljivka'];
        $usr_id = $_POST['usr_id'];
        $vre_id = $_POST['vre_id'];
        $anketa = $_POST['anketa'];

        sisplet_query("DELETE FROM srv_data_vrednost" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "'");
    }

    //zapisovanje v bazo pri drag drop v grid
    public function ajax_accept_dragdrop_grid()
    {
        Model::user_not_lurker();

        $spremenljivka = $_POST['spremenljivka'];
        $vre_id = $_POST['vre_id'];
        $usr_id = $_POST['usr_id'];
        $tip = $_POST['tip'];
        $anketa = $_POST['anketa'];
        $indeks = $_POST['indeks'];
        $last_vre_id = $_POST['last_vre_id'];
        $cat_right = $_POST['cat_right'];
        $vre_id_present = $_POST['vre_id_present'];


        //vnesi podatke v bazo
        if ($tip == 6) {
            sisplet_query("INSERT INTO srv_data_grid" . get('db_table') . " (spr_id, vre_id, usr_id, grd_id) VALUES ('$spremenljivka', '$vre_id', '" . get('usr_id') . "', '$indeks')");
        } else if ($tip == 16) {
            sisplet_query("INSERT INTO srv_data_checkgrid" . get('db_table') . " (spr_id, vre_id, usr_id, grd_id) VALUES ('$spremenljivka', '$vre_id', '" . get('usr_id') . "', '$indeks')");
        }


    }

    //brisanje odgovorov iz baze pri drag drop - tabela - vec odgovorov
    public function ajax_delete_dragdrop_grid_data()
    {
        Model::user_not_lurker();

        $spremenljivka = $_POST['spremenljivka'];
        $usr_id = $_POST['usr_id'];
        $vre_id = $_POST['vre_id'];
        $anketa = $_POST['anketa'];
        $indeks = $_POST['indeks'];
        //brisi podatke iz baze
        sisplet_query("DELETE FROM srv_data_checkgrid" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND vre_id='$vre_id' AND grd_id = '$indeks' ");

    }

    //brisanje odgovorov iz baze pri drag drop
    public function ajax_delete_dragdrop_grid_data_1()
    {
        Model::user_not_lurker();

        $spremenljivka = $_POST['spremenljivka'];
        $usr_id = $_POST['usr_id'];
        $vre_id = $_POST['vre_id'];
        $anketa = $_POST['anketa'];
        $indeks = $_POST['indeks'];

        sisplet_query("DELETE FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND vre_id='$vre_id' AND grd_id = '$indeks'");

    }
	
	//
	//brisanje odgovorov iz baze pri drag drop grid, ko se klikne gumb Ponastavi
    public function ajax_delete_dragdrop_grid_data_reset()
    {
        Model::user_not_lurker();

        $spremenljivka = $_POST['spremenljivka'];
        $usr_id = $_POST['usr_id'];        
        $anketa = $_POST['anketa'];
		$tip = $_POST['tip'];
		
		if($tip == 6){
			sisplet_query("DELETE FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' ");
		}else if($tip == 16){
			sisplet_query("DELETE FROM srv_data_checkgrid" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' ");
		}
    }

    // respondent se strinja z uporabo piskotvkov - nastavimo piskotek ok
    public function ajax_cookie_ok()
    {

        setcookie('cookie_ok', '1', time() + 2500000, '/');

    }

    //izpis statistike glede na spol
    public function ajax_glasovanje_spol()
    {
        $spremenljivka = $_POST['spremenljivka'];
        $spol = $_POST['spol'];

        Statistic::displayStatGlasovanje($spremenljivka, $spol);
    }

    /**
     * preveri, ce je captcha koda pravilno vnesena (preko ajaxa)
     *
     */
    public function ajax_captcha(){
		global $secret_captcha;
		
        $text = strtoupper($_GET['text']);
        $code = $_GET['code'];
        $spr_id = $_GET['spr_id'];
        $usr_id = $_GET['usr_id'];

		$recaptchaResponse = $_POST['g-recaptcha-response'];
		$request = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=".$secret_captcha."&response=".$recaptchaResponse);

		// zdaj pa zabeleži mail (pred pošiljanjem)    
		// zdaj pa še v bazi tistih ki so se ročno dodali
		if(strstr($request,"true")){
			echo '1';
		}
		else {
			echo '0';
		}
    }

    /**
     * poisce v kateri grupi/strani se pojavi if za redirect tabov
     *
     */
    public function ajax_grupa_for_if($parent = 0){

        $parent_if = $_POST['parent_if'];
        if ($parent > 0) $parent_if = $parent;

        ob_start();

        $b = new Branching(get('anketa'));
        $spr = $b->find_first_in_if($parent_if);

        ob_clean();

        $row = Model::select_from_srv_spremenljivka($spr);

        if ($parent == 0)
            echo $row['gru_id'];

        return $row['gru_id'];
    }

    /**
     * vklopi komentarje na anketo (v previewu je ta možnost)
     *
     */
    public function ajax_enable_comments(){

        ob_clean();

        SurveySetting::getInstance()->Init((int)$_POST['anketa']);

        SurveySetting::getInstance()->setSurveyMiscSetting('question_resp_comment', '1');
        SurveySetting::getInstance()->setSurveyMiscSetting('question_resp_comment_viewadminonly', '3');
    }


    public function ajax_continue_later(){
		
        save('lang_id', (int)$_GET['language']);

        $url = $_POST['url'] . '&return=1';

        SurveySetting::getInstance()->Init(get('anketa'));
        if (get('lang_id') != null) $_lang = '_' . get('lang_id'); else $_lang = '';

        $srv_continue_later_txt = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_continue_later_txt' . $_lang);
        if ($srv_continue_later_txt == '') $srv_continue_later_txt = self::$lang['srv_continue_later_txt'];

        $srv_continue_later_email = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_continue_later_email' . $_lang);
        if ($srv_continue_later_email == '') $srv_continue_later_email = self::$lang['srv_continue_later_email'];

        $srv_forma_send = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_forma_send' . $_lang);
        if ($srv_forma_send == '') $srv_forma_send = self::$lang['srv_forma_send'];

        echo '<div id="continue_later">';

        echo '<p>' . $srv_continue_later_txt . ':</p><p class="url">' . $url . '</p>';

        echo '<input type="hidden" name="url" id="url" value="' . $url . '">';

        echo '<p>' . $srv_continue_later_email . ': <input type="email" name="email" id="email" value="" placeholder="' . self::$lang['srv_email_example2'] . '"> <button type="submit" onclick="continue_later_send(\'' . self::$site_url . '\', \'' . get('lang_id') . '\'); return false;">' . $srv_forma_send . '</button></p>';

        echo '</div>';
    }

    public function ajax_continue_later_send(){
        global $mysql_database_name;
        
        ob_clean();

        $s = self::$lang['srv_continue_later_subject'];

        if($mysql_database_name == 'vprasalnikirsrssi')
            $t = self::$lang['srv_continue_later_content_rs-rs'] . ': <a href="' . $_POST['url'] . '">' . $_POST['url'] . '</a>';
        else
            $t = self::$lang['srv_continue_later_content'] . ': <a href="' . $_POST['url'] . '">' . $_POST['url'] . '</a>';
        
        // Podpis
        $signature = Common::getEmailSignature();
        $t .= $signature;

        $mail = $_POST['email'];

        if ($mail == '') return;

        try {
            $MA = new MailAdapter(get('anketa'), $type='alert');
            $MA->addRecipients($mail);
            $result = $MA->sendMail(stripslashes($t), $s);
        } 
		catch (Exception $e) {
        }
    }
	
	public function ajax_get_tip_opozorila() {
		$spr_id = $_POST['spr_id'];

		//poberi podatke o na trenutnem obmocju
		$sqlR = sisplet_query("SELECT reminder FROM srv_validation WHERE spr_id = $spr_id");
		$rowR = mysqli_fetch_assoc($sqlR);
		$tip_opozorila = $rowR['reminder'];
	
		echo $tip_opozorila;
	}

	
	// Izpisemo seznam starih odgovorov v popup (dodatna nastavitev pri text vprasanju)
	public function ajax_show_prevAnswers_all() {
		
		$spremenljivka = $_POST['spremenljivka'];

		echo '<h3>'.self::$lang['srv_prevAnswers'].'</h3>';
		
		$sql = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='".$spremenljivka."' AND usr_id!='".get('usr_id')."' ORDER BY id DESC");
		while($row = mysqli_fetch_array($sql)){	
			echo '<p>';
			echo $row['text'];
			echo '</p>';
		}
		
		// Gumb zapri
		echo '<div class="prevAnswers_button">';
		echo '<a href="#" onClick="hide_prevAnswers_all(\''.$spremenljivka.'\'); return false;"><span>'.self::$lang['srv_zapri'].'</span></a>';
		echo '</div>';
		
		echo '<script>
				$("#fade").on("click", function() {
					hide_prevAnswers_all(\''.$spremenljivka.'\');
			});</script>';
	}
	
	// Izpisemo seznam starih odgovorov v popup (dodatna nastavitev pri text vprasanju)
	public function ajax_show_gdpr_about() {
		global $lang;

		$anketa = $_POST['anketa'];
		
		$gdpr_settings = GDPR::getSurveySettings($anketa);
					
		echo '<h3>'.$lang['srv_gdpr_survey_gdpr_about'].'</h3>';

		if($gdpr_settings['about'] == ''){
            $about_array = GDPR::getGDPRInfoArray($anketa);
            $about_text = GDPR::getGDPRTextFromArray($about_array, $type='html');
            
            echo $about_text;
		}
		else{
			echo nl2br($gdpr_settings['about']);
		}
		
		echo '<br />';
		
		// Gumb zapri
		echo '<div class="prevAnswers_button">';
		echo '<a href="#" onClick="hide_gdpr_about(); return false;"><span>'.$lang['srv_zapri'].'</span></a>';
		echo '</div>';
		
		echo '<script>
				$("#fade").on("click", function() {
					hide_gdpr_about();
            });</script>';
            
        echo '<br />';
    }
    

    // Shranimo vrstni red pri randomizaciji (blokov ali vprasanj)
	public function ajax_save_randomization_order() {

        $usr_id = mysqli_real_escape_string($GLOBALS['connect_db'], $_POST['usr_id']);
        $randomization_type = mysqli_real_escape_string($GLOBALS['connect_db'], $_POST['randomization_type']);
        
        $vrstni_red = stripcslashes($_POST['order']);
        $vrstni_red = str_replace('"', "", substr($vrstni_red, 1, -1));

        // Random vrstni red VPRASANJ oz. BLOKOV znotraj bloka
        // ID bloka znotraj katerega so random elementi
        $parent_block_id = mysqli_real_escape_string($GLOBALS['connect_db'], $_POST['parent_block_id']);

        $sql = sisplet_query("INSERT INTO srv_data_random_blockContent 
                            (usr_id, block_id, vrstni_red) 
                            VALUES 
                            ('".$usr_id."', '".$parent_block_id."', '".$vrstni_red."')
                            ON DUPLICATE KEY UPDATE vrstni_red='".$vrstni_red."'
        ");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
	}
}