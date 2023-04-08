<?php

/**
 *
 *  Class ki skrbi za pregled, dodajanje, urejanje placil uporabnika
 *
 */

class UserPlacila{


    function __construct(){


    }


    // Dobimo array placil (vseh oz. za dolocen id)
    private function getPlacila(){
        global $admin_type;

        $placila = array();

        // Loop po vseh placilih v sistemu
        $sqlPlacilo = sisplet_query("SELECT * FROM user_access_placilo ORDER BY id DESC");
        while($rowPlacilo = mysqli_fetch_array($sqlPlacilo)){

            $placila[$rowPlacilo['id']] = $rowPlacilo;

            // Dobimo se podatke narocila, ce imamo id
            if($rowPlacilo['narocilo_id'] > 0){

                $sqlNarocilo = sisplet_query("SELECT n.*, u.name, u.surname, u.email 
                                                FROM user_access_narocilo n, users u
                                                WHERE n.usr_id=u.id AND n.id='".$rowPlacilo['narocilo_id']."'
                                            ");
                $rowNarocilo = mysqli_fetch_array($sqlNarocilo);

                $placila[$rowPlacilo['id']]['narocilo'] = $rowNarocilo;
            }
        }

        return $placila;
    }

    // Dobimo podatke placila dolocen id
    private function getPlacilo($placilo_id){
        global $admin_type;

        $placilo = array();

        // Loop po vseh placilih v sistemu
        $sqlPlacilo = sisplet_query("SELECT * FROM user_access_placilo WHERE id='".$placilo_id."'");
        $placilo = mysqli_fetch_array($sqlPlacilo);

        // Dobimo se podatke narocila, ce imamo id
        if($placilo['narocilo_id'] > 0){

            $sqlNarocilo = sisplet_query("SELECT n.*, u.name, u.surname, u.email 
                                            FROM user_access_narocilo n, users u
                                            WHERE n.usr_id=u.id AND n.id='".$placilo['narocilo_id']."'
                                        ");
            $rowNarocilo = mysqli_fetch_array($sqlNarocilo);

            $placilo['narocilo'] = $rowNarocilo;
        }

        return $placilo;
    }

    // Ustvarimo placilo iz narocila
    public function createPlacilo($narocilo, $eracun=false){

        // Preverimo, ce slucajno se obstaja placilo za to narocilo - vrnemo error
        $sqlPlaciloCheck = sisplet_query("SELECT id FROM user_access_placilo WHERE narocilo_id='".$narocilo['id']."'");
        if(mysqli_num_rows($sqlPlaciloCheck) > 0){
            echo 'Napaka! Plačilo za to naročilo že obstaja.';
            return;
        }
        
        $note = 'Plačilo naročila '.$narocilo['id'];

        // Dobimo ceno glede na narocilo in paket
        $sqlPackage = sisplet_query("SELECT name FROM user_access_paket WHERE id='".$narocilo['package_id']."'");
        $rowPackage = mysqli_fetch_array($sqlPackage);
        
        $UA = new UserNarocila();
        $cena = $UA->getPrice($rowPackage['name'], $narocilo['trajanje'], $narocilo['discount'], $narocilo['time']);

        // Zavezanec iz tujine je placal brez ddv
        $cena_placano = ($UA->isWithoutDDV($narocilo['id'])) ? $cena['final_without_tax'] : $cena['final'];

        // Ce je placilo eracuna, bo placano sele cez 30 dni
        $time = ($eracun) ? 'NOW() + INTERVAL 1 MONTH': 'NOW()';

        $sqlPlacilo = sisplet_query("INSERT INTO user_access_placilo 
                                        (narocilo_id, note, time, price, payment_method)
                                        VALUES
                                        ('".$narocilo['id']."', '".$note."', ".$time.", '".$cena_placano."', '".$narocilo['payment_method']."')
                                    ");
        if (!$sqlPlacilo)
            echo mysqli_error($GLOBALS['connect_db']);
    }


    // Izpisemo podatke o placilih
    public function displayPlacila(){
        global $lang, $global_user_id, $app_settings;

        // Tabela vseh placil
        $this->displayPlacilaTable();

        // Gumb za dodajanje placila
        /*echo '<div class="buttons_holder">';
        echo '  <div class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="displayPlaciloPopup();"><span>'.$lang['srv_placila_create'].'</span></a></div>';
        echo '</div>';*/
        
        // Izracun zasluzka in provizij po mesecih - samo Goran
        if($app_settings['app_name'] == 'www.1ka.si' && $global_user_id == '112696')
            $this->displayPlacilaPovzetek();
    }

    // Izpisemo seznam vseh placil
    public function displayPlacilaTable(){
        global $lang, $global_user_id, $app_settings;

        // Admini vidijo vsa placila
        $data = $this->getPlacila();

        echo '<table id="user_placila" class="dataTable user_placila_admin" style="width:100%">';
        
        // Glava tabele
        echo '  <thead>';
        echo '      <tr>';

        echo '          <th>'.$lang['srv_placilo_narocilo'].'</th>';
        echo '          <th>'.$lang['srv_placilo_note'].'</th>';
        echo '          <th>'.$lang['srv_placilo_time'].'</th>';
        echo '          <th>'.$lang['srv_placilo_price'].'</th>';
        echo '          <th>'.$lang['srv_narocilo_ddv'].'</th>';
        echo '          <th>'.$lang['srv_placilo_payment_method'].'</th>';
        echo '          <th>'.$lang['srv_placilo_stornirano'].'</th>';
        echo '          <th>'.$lang['srv_placilo_drzava'].'</th>';
        echo '          <th>'.$lang['srv_placilo_paket'].'</th>';
        echo '          <th>'.$lang['srv_placilo_trajanje'].'</th>';
        echo '          <th>'.$lang['edit2'].'</th>';
        echo '      </tr>';
        echo '  </thead>';


        // Vsebina tabele
        echo '  <tbody>';

        foreach($data as $placilo_id => $data_row){

            echo '<tr>';   

            // Narocilo
            if(isset($data_row['narocilo'])){
                echo '<td>'.$data_row['narocilo']['id'].' ('.$data_row['narocilo']['email'].')</td>';
            }
            else{
                echo '<td>/</td>';
            }

            // Note
            echo '<td>'.$data_row['note'].'</td>';

            // Time
            echo '<td data-order="'.date("Y-n-j", strtotime($data_row['time'])).'">'.date( 'd.m.Y G:i', strtotime($data_row['time'])).'</td>';

            // Price
            echo '<td>'.$data_row['price'].'</td>';

            // Brez ddv (zavezanec iz tujine)
            $UA = new UserNarocila();
            echo '<td>'.($UA->isWithoutDDV($data_row['narocilo']['id']) ? $lang['no'] : $lang['yes']).'</td>';

            // Payment method
            echo '<td>'.$lang['srv_narocilo_nacin_placila_'.$data_row['payment_method']].'</td>';

            // Stornirano method
            echo '<td>'.($data_row['canceled'] == '1' ? $lang['yes'] : $lang['no']).'</td>';

            // Država
            echo '<td>'.$data_row['narocilo']['podjetje_drzava'].'</td>';
            
            // Paket
            echo '<td>'.$data_row['narocilo']['package_id'].'KA</td>';

            // Trajanje
            echo '<td>'.$data_row['narocilo']['trajanje'].'</td>';

            // Edit / delete
            echo '<td>';
            // Na www.1ka.si lahko placilo ureja samo Goran
            if($app_settings['app_name'] != 'www.1ka.si' || $global_user_id == '112696'){
                
                // Uredi
                echo '<a href="#" onClick="displayPlaciloPopup(\''.$data_row['id'].'\')" title="'.$lang['srv_placila_edit'].'"><i class="fa fa-pencil-alt link-sv-moder"></i></a> <span class="no-print"> | </span>';
                
                // Brisi
                echo '<a href="#" onClick="brisiPlacilo(\''.$data_row['id'].'\')" title="'.$lang['srv_placila_delete'].'"><i class="fa fa-times link-sv-moder"></i></a>';
                
                // Storniraj
                if($data_row['canceled'] != '1')
                    echo ' <span class="no-print"> | </span><a href="#" onClick="stornirajPlacilo(\''.$data_row['id'].'\')" title="'.$lang['srv_placila_storniraj'].'">Storniraj</a>';
            }
            else{
                echo '/';
            }
            echo '</td>';

            echo '</tr>';
        }

        echo '  </tbody>';
        
        echo '</table>';

        // Se inicializiramo dataTable jquery
        echo '<script> preparePlacilaTableAdmin(); </script>';
    }

    // Prikazemo popup za dodajanje novega placila
    private function displayPlaciloCreate(){
        global $lang;
        
        echo '<h2>'.$lang['srv_placila_create'].'</h2>';


        echo '<div class="edit_placilo_content">';

        echo '<div class="form_holder"><form name="create_placilo" id="create_placilo">';

        // Podatki narocnika
        echo '<div class="edit_placilo_segment">';

        // Note
        echo '<div class="edit_placilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_placilo_note'].':</span> ';
        //echo '<input type="text" name="note">';
        echo '<textarea name="note"></textarea>';
        echo '</div>';

        // Time
        echo '<div class="edit_placilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_placilo_time'].':</span> ';
        echo '<input type="text" name="time">';
        echo '</div>';

        // Price
        echo '<div class="edit_placilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_placilo_price'].':</span> ';
        echo '<input type="text" name="price">';
        echo '</div>';

        // Payment method
        echo '<div class="edit_placilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_placilo_payment_method'].':</span> ';
        echo '<input type="text" name="payment_method">';
        echo '</div>';

        echo '</div>';

        echo '</form></div>';

        // Gumbi na dnu
		echo '<div class="buttons_holder">';
        echo '  <div class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="createPlaciloSave();"><span>'.$lang['edit1337'].'</span></a></div>';
        echo '  <div class="buttonwrapper floatRight spaceRight"><a class="ovalbutton ovalbutton_gray" href="#" onclick="urediPlaciloClose();"><span>'.$lang['srv_zapri'].'</span></a></div>';
        echo '</div>';

        echo '</div>';
    }

    // Prikazemo popup za pregled in urejanje placila - TODO
    private function displayPlaciloEdit($placilo_id){
        global $lang;

        // Dobimo podatke placila
        $placilo = $this->getPlacilo($placilo_id);


        echo '<h2>'.$lang['srv_placila_create'].'</h2>';

        
        echo '<div class="edit_placilo_content">';

        echo '<div class="form_holder"><form name="edit_placilo" id="edit_placilo">';

        // Podatki narocnika
        echo '<div class="edit_placilo_segment">';

        // Input za id porocila
        echo '<input type="hidden" name="placilo_id" value="'.$placilo_id.'">';

        // Note
        echo '<div class="edit_placilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_placilo_note'].':</span> ';
        //echo '<input type="text" name="note" value="'.$placilo['note'].'">';
        echo '<textarea name="note">'.$placilo['note'].'</textarea>';
        echo '</div>';

        // Time
        echo '<div class="edit_placilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_placilo_time'].':</span> ';
        echo '<input type="text" name="time" value="'.$placilo['time'].'">';
        echo '</div>';

        // Price
        echo '<div class="edit_placilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_placilo_price'].':</span> ';
        echo '<input type="text" name="price" value="'.$placilo['price'].'">';
        echo '</div>';

        // Payment method
        echo '<div class="edit_placilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_placilo_payment_method'].':</span> ';
        echo '<input type="text" name="payment_method" value="'.$placilo['payment_method'].'">';
        echo '</div>';

        echo '</div>';

        echo '</form></div>';

        // Gumbi na dnu
		echo '<div class="buttons_holder">';
        echo '  <div class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="urediPlaciloSave();"><span>'.$lang['edit1337'].'</span></a></div>';
        echo '  <div class="buttonwrapper floatRight spaceRight"><a class="ovalbutton ovalbutton_gray" href="#" onclick="urediPlaciloClose();"><span>'.$lang['srv_zapri'].'</span></a></div>';
        echo '</div>';

        echo '</div>';
    }


    // Prikazemo popup za pregled in urejanje placila
    private function displayPlacilaPovzetek(){
        global $lang;

        /*
        TRR: 0,12 €
        EU kartica 1,4% + 0,25 €
        Non-EU kartica: 2,9% + 0,25 €
        PayPal: 3,4% + 0,35 €
        */

        echo '<div style="clear:both; margin: 50px 0;">';
        echo '<fieldset><legend>Izračun po mesecih</legend>';

        $sqlPlacilo = sisplet_query("SELECT *, MONTH(time) as month, YEAR(time) as year
                                        FROM user_access_placilo
                                    ");
        while($rowPlacilo = mysqli_fetch_array($sqlPlacilo)){

            // Paypal
            if($rowPlacilo['payment_method'] == '2'){
                $placila[$rowPlacilo['year']][$rowPlacilo['month']]['sum_paypal'] += $rowPlacilo['price'];
                $placila[$rowPlacilo['year']][$rowPlacilo['month']]['provizija_paypal'] += ($rowPlacilo['price'] * 0.34) + 0.35;
            }
            // Kartica
            elseif($rowPlacilo['payment_method'] == '3'){
                $placila[$rowPlacilo['year']][$rowPlacilo['month']]['sum_kartica'] += $rowPlacilo['price'];
                $placila[$rowPlacilo['year']][$rowPlacilo['month']]['provizija_kartica'] += ($rowPlacilo['price'] * 0.014) + 0.25;
            }
            // TRR
            else{
                $placila[$rowPlacilo['year']][$rowPlacilo['month']]['sum_trr'] += $rowPlacilo['price'];
                $placila[$rowPlacilo['year']][$rowPlacilo['month']]['provizija_trr'] += 0.12;
            }

            // Suma placil za mesec
            $placila[$rowPlacilo['year']][$rowPlacilo['month']]['sum'] += $rowPlacilo['price'];
        }

        foreach($placila as $year => $placila_leto){

            foreach($placila_leto as $month => $placila_mesec){

                echo '<br>';

                $month_name = date("F", mktime(0, 0, 0, $month, 10)); 
                echo '<span class="bold">'.$month_name.' '.$year.'</span>';

                echo '<br>';

                echo 'Vsota plačil na TRR: '.$placila_mesec['sum_trr'];
                echo '<br>TRR provizija: '.$placila_mesec['provizija_trr'];

                echo '<br><br>';

                echo 'Vsota plačil s kartico: '.$placila_mesec['sum_kartica'];
                echo '<br>Kartica provizija: '.$placila_mesec['provizija_kartica'];

                echo '<br><br>';
                
                echo 'Vsota plačil s paypal: '.$placila_mesec['sum_paypal'];
                echo '<br>Paypal provizija: '.$placila_mesec['provizija_paypal'];

                echo '<br><br>';

                echo 'Vsota plačil: '.$placila_mesec['sum'];

                echo '<br><br>';
            }
        }

        echo '</fieldset>';
        echo '</div>';
    }


    // Ajax klici
    public function ajax(){

        $placilo_id = (isset($_POST['placilo_id'])) ? $_POST['placilo_id'] : 0;


        // Prikazemo popup z urejanjem posameznega narocila
        if($_GET['a'] == 'displayPlaciloPopup') {

            if($placilo_id > 0){
                $this->displayPlaciloEdit($placilo_id);
            }
            else{
                $this->displayPlaciloCreate();
            }
        }

        // Urejamo narocilo
        if($_GET['a'] == 'editPlacilo') {

            if($placilo_id > 0){

                $update = '';
    
                $update .= (isset($_POST['narocilo_id'])) ? " narocilo_id='".$_POST['narocilo_id']."'," : "";
                $update .= (isset($_POST['note'])) ? " note='".$_POST['note']."'," : "";
                $update .= (isset($_POST['time'])) ? " time='".$_POST['time']."'," : "";
                $update .= (isset($_POST['price'])) ? " price='".$_POST['price']."'," : "";
                $update .= (isset($_POST['payment_method'])) ? " payment_method='".$_POST['payment_method']."'," : "";
    
                if($update != ''){

                    $update = substr($update, 0, -1);

                    $sqlPlacilo = sisplet_query("UPDATE user_access_placilo SET ".$update." WHERE id='".$placilo_id."'");
                    if (!$sqlPlacilo)
                        echo mysqli_error($GLOBALS['connect_db']);
                }
            }  

            // Na novo izrisemo tabelo z narocili
            $this->displayPlacila();
        }

        // Urejamo narocilo
        if($_GET['a'] == 'createPlacilo') {

            $narocilo_id = (isset($_POST['narocilo_id'])) ? $_POST['narocilo_id'] : 0;
            $note = (isset($_POST['note'])) ? $_POST['note'] : '';
            $time = (isset($_POST['time'])) ? $_POST['time'] : '';
            $price = (isset($_POST['price'])) ? $_POST['price'] : 0;
            $payment_method = (isset($_POST['payment_method'])) ? $_POST['payment_method'] : '';

            if($price != '' && $price != 0){

                // Preverimo, ce slucajno se obstaja placilo za to narocilo - vrnemo error
                if($narocilo_id != 0){
                    $sqlPlaciloCheck = sisplet_query("SELECT id FROM user_access_placilo WHERE narocilo_id='".$narocilo_id."'");

                    if(mysqli_num_rows($sqlPlaciloCheck) > 0){
                        echo 'Napaka! Plačilo za to naročilo že obstaja.';

                        // Na novo izrisemo tabelo z narocili
                        $this->displayPlacila();

                        return;
                    }
                }
                
                $sqlPlacilo = sisplet_query("INSERT INTO user_access_placilo 
                                                (narocilo_id, note, time, price, payment_method)
                                                VALUES
                                                ('".$narocilo_id."', '".$note."', '".$time."', '".$price."', '".$payment_method."')
                                            ");
                if (!$sqlPlacilo)
                    echo mysqli_error($GLOBALS['connect_db']);
            } 
            else{
                echo 'Napaka! Cana za plačilo ne sme biti 0.';
            }

            // Na novo izrisemo tabelo z narocili
            $this->displayPlacila();
        }

        // Brisemo narocilo
        if($_GET['a'] == 'deletePlacilo') {

            if($placilo_id > 0){
                $sqlPlacilo = sisplet_query("DELETE FROM user_access_placilo WHERE id='".$placilo_id."'");
            }

            // Na novo izrisemo tabelo z narocili
            $this->displayPlacila();
        }

        // Storniramo narocilo
        if($_GET['a'] == 'stornirajPlacilo') {

            if($placilo_id > 0){

                // Nastavimo se status narocila na storniran
                $sqlPlacilo = sisplet_query("SELECT p.*, n.usr_id 
                                                FROM user_access_placilo p, user_access_narocilo n 
                                                WHERE p.id='".$placilo_id."' AND p.narocilo_id=n.id
                                            ");
                if(mysqli_num_rows($sqlPlacilo) > 0){
                    $rowPlacilo = mysqli_fetch_array($sqlPlacilo);

                    // Nastavimo status originalnega placila na stornirano
                    $sqlPlaciloStatus = sisplet_query("UPDATE user_access_placilo SET canceled='1' WHERE id='".$placilo_id."'");

                    // Nastavimo status narocila na stornirano
                    $sqlNarociloStatus = sisplet_query("UPDATE user_access_narocilo SET status='2' WHERE id='".$rowPlacilo['narocilo_id']."'");

                    // Negativni znesek
                    $znesek = number_format((-1) * $rowPlacilo['price'], 2, '.', '');

                    // Ustvarimo kopijo placila z negativnim zneskom
                    $sqlStorniranoPlacilo = sisplet_query("INSERT INTO user_access_placilo 
                                                            (narocilo_id, note, time, price, payment_method, canceled) 
                                                            VALUES 
                                                            ('".$rowPlacilo['narocilo_id']."', '".$rowPlacilo['note']."', NOW(), '".$znesek."', '".$rowPlacilo['payment_method']."', '1')
                                                        ");

                    // Stranki deaktiviramo paket
                    $sqlNarociloStatus = sisplet_query("UPDATE user_access SET package_id='1' WHERE usr_id='".$rowPlacilo['usr_id']."'");
                }
            }

            // Na novo izrisemo tabelo s placili
            $this->displayPlacila();
        }
    }
}