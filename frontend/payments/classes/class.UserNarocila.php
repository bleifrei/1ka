<?php

/**
 *
 *  Class ki skrbi za pregled, dodajanje, urejanje narocil uporabnika
 *
 */

class UserNarocila{


    // Array z vsemi paketi
    private $packages = array();

    // Filter narocil
    private $narocila_filter = array('neplacana' => 1, 'pacana' => 0, 'stornirana' => 0);

    
    function __construct(){

        // Zakesiramo vse pakete
        $this->cachePackages();

        if(isset($_SESSION['narocila_filter']['neplacana']) && $_SESSION['narocila_filter']['neplacana'] == '0')
            $this->narocila_filter['neplacana'] = 0;

        if(isset($_SESSION['narocila_filter']['placana']) && $_SESSION['narocila_filter']['placana'] == '1')
            $this->narocila_filter['placana'] = 1;

        if(isset($_SESSION['narocila_filter']['stornirana']) && $_SESSION['narocila_filter']['stornirana'] == '1')
            $this->narocila_filter['stornirana'] = 1;
    }


    // Dobimo array narocil (vseh oz. za dolocenega uporabnika)
    private function getNarocila($usr_id=0){
        global $admin_type;

        $narocila = array();

        // Vsa narocila lahko pregledujejo samo admini
        if($admin_type == 0 && $usr_id == 0){
            
            // Filter po statusu
            $status = ' AND un.status IN (';
            $status .= ($this->narocila_filter['neplacana'] == 1) ? '0,' : '';
            $status .= ($this->narocila_filter['placana'] == 1) ? '1,' : '';
            $status .= ($this->narocila_filter['stornirana'] == 1) ? '2,' : '';
            $status = substr($status, 0, -1);
            $status .= ') ';

            // Loop po vseh narocilih v sistemu
            $sqlNarocilo = sisplet_query("SELECT un.*, u.name, u.surname, u.email, up.name AS package_name, up.description AS package_description, up.price AS package_price
                                            FROM user_access_narocilo un, users u, user_access_paket up
                                            WHERE un.usr_id=u.id AND un.package_id=up.id ".$status."
                                            ORDER BY un.id DESC
                                        ");
            while($rowNarocilo = mysqli_fetch_array($sqlNarocilo)){

                $narocila[] = $rowNarocilo;
            }
        }
        elseif($usr_id > 0){

            // Loop po vseh narocilih uporabnika
            $sqlNarocilo = sisplet_query("SELECT un.*, u.name, u.surname, u.email, up.name AS package_name, up.description AS package_description, up.price AS package_price
                                            FROM user_access_narocilo un, users u, user_access_paket up
                                            WHERE un.usr_id='".$usr_id."' AND un.usr_id=u.id AND un.package_id=up.id
                                            ORDER BY un.id DESC
                                        ");
            while($rowNarocilo = mysqli_fetch_array($sqlNarocilo)){

                $narocila[] = $rowNarocilo;
            }
        }

        return $narocila;
    }

    // Dobimo podatke zadnjega narocila za dolocenega uporabnika
    public function getLastNarocilo($usr_id){
        global $admin_type;

        $return = array();

        // Dobimo zadnje narocilo uporabnika
        $sqlNarocilo = sisplet_query("SELECT un.*, up.name AS package_name, up.description AS package_description, up.price AS package_price
                                        FROM user_access_narocilo un, user_access_paket up
                                        WHERE un.usr_id='".$usr_id."' AND un.package_id=up.id
                                        ORDER BY un.time DESC
                                    ");

        // Uporabnik nima se nobenega narocila
        if(mysqli_num_rows($sqlNarocilo) == 0){
            $return['id'] = '0';
        }
        else{
            $return = mysqli_fetch_array($sqlNarocilo);
        }
                                
        return $return;
    }

    // Izracunamo koncno ceno glede na paket, trajanje in popust (v eur)
    public function getPrice($package_name, $trajanje, $discount=0, $time=''){

        $cena = array();

        // Mesecna cena paketa
        $package_price = $this->packages[$package_name]['price'];

        // Narocila pred 7.12. morajo imeti stare cene
        if($time != '' && strtotime($time) < strtotime('2020-12-06 20:00:00')){
         
            // Mesecno ceno zmanjsamo glede na trajanje
            if($package_name == '2ka'){
                if((int)$trajanje >= 12){
                    $cena['monthly'] = number_format(11.90 - 2, 2, '.', '');
                }
                elseif((int)$trajanje >= 3){
                    $cena['monthly'] = number_format(11.90 - 1, 2, '.', '');
                }
                else{
                    $cena['monthly'] = number_format(11.90, 2, '.', '');
                }
            }
            elseif($package_name == '3ka'){
                if((int)$trajanje >= 12){
                    $cena['monthly'] = number_format(21.90 - 2, 2, '.', '');
                }
                elseif((int)$trajanje >= 3){
                    $cena['monthly'] = number_format(21.90 - 1, 2, '.', '');
                }
                else{
                    $cena['monthly'] = number_format(21.90, 2, '.', '');
                }
            }
        }
        else{

            // Mesecno ceno zmanjsamo glede na trajanje
            if($package_name == '2ka'){
                if((int)$trajanje >= 12){
                    $cena['monthly'] = number_format($package_price - 4, 2, '.', '');
                }
                elseif((int)$trajanje >= 3){
                    $cena['monthly'] = number_format($package_price - 2, 2, '.', '');
                }
                else{
                    $cena['monthly'] = number_format($package_price, 2, '.', '');
                }
            }
            elseif($package_name == '3ka'){
                if((int)$trajanje >= 12){
                    $cena['monthly'] = number_format($package_price - 3, 2, '.', '');
                }
                elseif((int)$trajanje >= 3){
                    $cena['monthly'] = number_format($package_price - 1.5, 2, '.', '');
                }
                else{
                    $cena['monthly'] = number_format($package_price, 2, '.', '');
                }
            }
        }
        

        // Se brez davka za monthly
        $cena['monthly_without_tax'] = number_format(floatval($cena['monthly']) / 1.22, 2, '.', '');


        // Cena za celotno obdobje
        $cena['full'] = number_format((int)$trajanje * floatval($cena['monthly']), 2, '.', '');

        // Se brez davka za full
        $cena['full_without_tax'] = number_format(floatval($cena['full']) / 1.22, 2, '.', '');


        // Cena s popustom
        $cena['full_discount'] = $cena['full'];
        $cena['discount'] = $discount;

        // Odstejemo se popust ce je posebej nastavljen
        if($discount != 0){

            // Ce je popust vecji od celotnega zneska, je cena 0 (cena ne more biti negativna)
            if($discount > $cena['full']){
                $cena['full_discount'] = 0;
                $cena['discount'] = $cena['full'];
            }
            else{
                //$cena['full_discount'] = number_format(floatval($cena['full_discount']) - (floatval($discount) * floatval($cena['full']) / 100), 2, '.', '');
                $cena['full_discount'] = number_format(floatval($cena['full_discount']) - floatval($discount), 2, '.', '');
            }
        }

        // Dodatno se izracunamo popust v %
        if($cena['full'] > 0)
            $cena['discount_percentage'] = round(floatval($cena['discount']) / floatval($cena['full']) * 100);
        else
            $cena['discount_percentage'] = 0;

        // Se davek
        $cena['final_without_tax'] = number_format(floatval($cena['full_discount']) / 1.22, 2, '.', '');
        $cena['tax'] = number_format($cena['full_discount'] - $cena['final_without_tax'], 2, '.', '');
        $cena['final'] = $cena['full_discount'];
        
        return $cena;
    }

    // Izracunamo popust glede na uporabnika (pri upgradu / downgradu paketa)
    public function getDiscount($usr_id, $package_name, $trajanje){

        // Dobimo trenuten dostop userja
        $ua = UserAccess::getInstance($usr_id);
        $user_access = $ua->getAccess();

        // Dobimo polno ceno za paket
        $price = $this->getPrice($package_name, $trajanje);

        // UPGRADE oz. DOWNGRADE - iz 2ka na 3ka ali iz 3ka na 2ka
        if( isset($user_access['package_name']) && (($user_access['package_name'] == '2ka' && $package_name == '3ka') || ($user_access['package_name'] == '3ka' && $package_name == '2ka')) ){

            // Mesecna cena obstojecega paketa
            $package_price = floatval($this->packages[$user_access['package_name']]['price']);

            // Stevilo dni dokler je obstojeci paket se veljaven
            $now = time();
            $expire = strtotime($user_access['time_expire']);
            $expire_in_days = floor(($expire - $now) / (60 * 60 * 24));

            // Popravimo ceno, ce ima veljaven paket se za 3 mesece ali vec (pomeni, da je imel popust pri nakupu kar upostevamo)
            if($package_name == '2ka'){
                if($expire_in_days > 92){
                    $package_price = number_format($package_price - 4, 2, '.', '');
                }
                elseif($expire_in_days > 31){
                    $package_price = number_format($package_price - 2, 2, '.', '');
                }
            }
            elseif($package_name == '3ka'){
                if($expire_in_days > 92){
                    $package_price = number_format($package_price - 3, 2, '.', '');
                }
                elseif($expire_in_days > 31){
                    $package_price = number_format($package_price - 1.5, 2, '.', '');
                }
            }

            // Popust izracunamo kot delez cene paketa in 
            $dayly_discount = number_format($package_price / 31, 2, '.', '');
            $discount = number_format($dayly_discount * $expire_in_days, 2, '.', '');

            return $discount;
        }
        // Drugace nimamo nobenega popusta
        else{
            return 0;
        }
    }

    // Preverimo, ce narocilo slucajno nima ddv-ja (zavezanec za ddv iz tujine)
    public function isWithoutDDV($narocilo_id){

        $sqlNarocilo = sisplet_query("SELECT podjetje_drzava, podjetje_no_ddv 
                                        FROM user_access_narocilo 
                                        WHERE id='".$narocilo_id."'
                                    ");
        if(mysqli_num_rows($sqlNarocilo) == 1){

            $rowNarocilo = mysqli_fetch_array($sqlNarocilo);

            // Slovenija ima vedno ddv
            if($rowNarocilo['podjetje_drzava'] == 'Slovenija' || $rowNarocilo['podjetje_drzava'] == 'Slovenia'){
                return false;
            }

            // Ce ni iz slovenije in ima oznaceno da ne placa ddv-ja
            if($rowNarocilo['podjetje_no_ddv'] == '1'){
                return true;
            }
        }

        return false;
    }


    // Dobimo jezik narocila - v istem jeziku so potem emaili in racun/predracun
    public function getNarociloLanguage($narocilo_id){

        $sqlNarocilo = sisplet_query("SELECT language
                                        FROM user_access_narocilo 
                                        WHERE id='".$narocilo_id."'
                                    ");
        if(mysqli_num_rows($sqlNarocilo) == 1){

            $rowNarocilo = mysqli_fetch_array($sqlNarocilo);

            if($rowNarocilo['language'] == 'sl'){
                return 'si';
            }
            else{
                return 'en';
            }
        }

        return 'en';
    }


    // Izpisemo podatke o narocilih uporabnika
    public function displayNarocila(){
        global $lang, $global_user_id;

        // Podatki o trenutnem paketu uporabnika
        echo '<fieldset>';
        echo '<legend>'.$lang['srv_narocila_current'].'</legend>'; 
        
        $ua = UserAccess::getInstance($global_user_id);
        $user_access = $ua->getAccess();

        // Ce ni polja v bazi oz je nastavljen paket na 1 ima osnovni paket
        if(!$user_access || $user_access['package_id'] == '1'){
            echo '<p>'.$lang['srv_narocila_current_package'].':</span> <span class="bold">1KA</span></p>';
        }
        // Imamo aktiviran paket - izpisemo podatke
        else{
            echo '<div class="data"><span class="setting_title">'.$lang['srv_narocila_current_package'].':</span> <span class="bold">'.$user_access['package_name'],'</span></div>';
            echo '<div class="data"><span class="setting_title">'.$lang['srv_narocila_current_start'].':</span> <span class="bold">'.date( 'd.m.Y', strtotime($user_access['time_activate'])).'</span></div>';
            echo '<div class="data"><span class="setting_title">'.$lang['srv_narocila_current_expire'].':</span> <span class="bold">'.date( 'd.m.Y', strtotime($user_access['time_expire'])),'</span></div>';
        }

        echo '</fieldset>';


        // Tabela vseh narocil uporabnika
        echo '<fieldset>';
        echo '<legend>'.$lang['srv_narocila_list'].'</legend>'; 

        $sqlNarocilaCount = sisplet_query("SELECT count(id) FROM user_access_narocilo WHERE usr_id='".$global_user_id."'");
        $rowNarocilaCount = mysqli_fetch_array($sqlNarocilaCount);
        if($rowNarocilaCount['count(id)'] > 0){
            $this->displayNarocilaTable();
        }
        else{
            echo '<p>'.$lang['srv_narocila_no_package_text'].'</p>';
        }

        echo '</fieldset>';
    }

    // Izpisemo seznam vseh narocil uporabnika
    public function displayNarocilaTable(){
        global $lang, $global_user_id;

        // Dobimo vsa narocila uporabnika
        $data = $this->getNarocila($global_user_id);

        echo '<table id="user_narocila" class="user_narocila">';
        
        // Glava tabele
        echo '  <thead>';
        echo '      <tr>';

        echo '          <th>'.$lang['srv_narocilo_paket'].'</th>';
        echo '          <th>'.$lang['srv_narocilo_trajanje'].' ('.$lang['srv_narocilo_trajanje_mesecev'].')</th>';
        echo '          <th>'.$lang['srv_narocilo_cas'].'</th>';
        echo '          <th>'.$lang['srv_narocilo_nacin_placila'].'</th>';
        echo '          <th>'.$lang['srv_narocilo_cena'].'</th>';
        echo '          <th>'.$lang['srv_narocilo_status'].'</th>';
        echo '          <th>'.$lang['srv_narocilo_pdf'].'</th>';

        echo '      </tr>';
        echo '  </thead>';

        // Vsebina tabele
        echo '  <tbody>';

        foreach($data as $usr_id => $data_row){

            echo '<tr>';   

            echo '<td>'.$data_row['package_name'].'</td>';
            echo '<td>'.$data_row['trajanje'].'</td>';
            echo '<td>'.date("j.n.Y H:i", strtotime($data_row['time'])).'</td>';
            echo '<td>'.$data_row['payment_method'].'</td>';

            // Cena
            $cena = $this->getPrice($data_row['package_name'], $data_row['trajanje'], $data_row['discount'], $data_row['time']);

            // Zavezanec iz tujine nima ddv-ja
            if($this->isWithoutDDV($data_row['id']))
                echo '<td>'.$cena['final_without_tax'].'</td>';
            else
                echo '<td>'.$cena['final'].'</td>';

            if($data_row['status'] == '0')
                $status_color = 'red';
            elseif($data_row['status'] == '1')
                $status_color = 'green';
            else
                $status_color = 'black';
            echo '<td class="'.$status_color.'">'.$lang['srv_narocilo_status_'.$data_row['status']].'</td>';

            // PDF
            echo '<td>';
            echo '<span class="pointer as_link" onClick="getNarociloPredracun(\''.$data_row['id'].'\')">'.$lang['srv_narocilo_pdf_predracun'].'</span>';
            if($data_row['status'] == '1'){
                echo ' | <span class="pointer as_link" onClick="getNarociloRacun(\''.$data_row['id'].'\')">'.$lang['srv_narocilo_pdf_racun'].'</span>';
            }
            echo '</td>';

            echo '</tr>';
        }

        echo '  </tbody>';
        
        echo '</table>';
    }

    // Izpisemo seznam vseh narocil - admin
    public function displayNarocilaTableAdmin(){
        global $lang, $global_user_id;

        // Admini vidijo vsa narocila
        $data = $this->getNarocila();

        // Filtri po statusu
        echo '<div class="narocila_filters">';
        echo '<label for="filter_narocila_0"><input type="checkbox" id="filter_narocila_0" '.($this->narocila_filter['neplacana'] == 1 ? 'checked="checked"' : '').' onClick="filterNarocila(\'0\', this.checked)">'.$lang['srv_narocilo_filter_status_0'].'</label>';
        echo '<label for="filter_narocila_1"><input type="checkbox" id="filter_narocila_1" '.($this->narocila_filter['placana'] == 1 ? 'checked="checked"' : '').' onClick="filterNarocila(\'1\', this.checked)">'.$lang['srv_narocilo_filter_status_1'].'</label>';
        echo '<label for="filter_narocila_2"><input type="checkbox" id="filter_narocila_2" '.($this->narocila_filter['stornirana'] == 1 ? 'checked="checked"' : '').' onClick="filterNarocila(\'2\', this.checked)">'.$lang['srv_narocilo_filter_status_2'].'</label>';
        echo '</div>';
        
        echo '<table id="user_narocila" class="dataTable user_narocila_admin" style="width:100%">';
        
        // Glava tabele
        echo '  <thead>';
        echo '      <tr>';
        echo '          <th>ID</th>';
        echo '          <th>'.$lang['srv_narocilo_ime'].'</th>';
        echo '          <th>'.$lang['email'].'</th>';
        echo '          <th>'.$lang['srv_narocilo_paket'].'</th>';
        echo '          <th>'.$lang['srv_narocilo_trajanje'].' ('.$lang['srv_narocilo_trajanje_mesecev'].')</th>';
        echo '          <th>'.$lang['srv_narocilo_cas'].'</th>';
        echo '          <th>'.$lang['srv_narocilo_nacin_placila'].'</th>';
        echo '          <th>'.$lang['srv_narocilo_ddv'].'</th>';
        echo '          <th>'.$lang['srv_narocilo_cena'].'</th>';
        echo '          <th>'.$lang['srv_narocilo_status'].'</th>';
        echo '          <th>'.$lang['srv_narocilo_podjetje_eracun'].'</th>';
        echo '          <th>'.$lang['srv_narocilo_pdf'].'</th>';
        echo '          <th>'.$lang['edit2'].'</th>';
        echo '      </tr>';
        echo '  </thead>';


        // Vsebina tabele
        echo '  <tbody>';

        foreach($data as $usr_id => $data_row){

            if($data_row['status'] == '0')
                $status_color = 'red';
            elseif($data_row['status'] == '1')
                $status_color = 'green';
            else
                $status_color = 'black';

            echo '<tr class="'.$status_color.'_bg">';   

            echo '<td>'.$data_row['id'].'</td>';
            echo '<td>'.$data_row['ime'].' '.($data_row['podjetje_ime'] != '' ? '('.$data_row['podjetje_ime'].')' : '').'</td>';
            echo '<td><span class="as_link" onClick="edit_user(\''.$data_row['usr_id'].'\'); return false;">'.$data_row['email'].'</span></td>';
            echo '<td>'.$data_row['package_name'].'</td>';
            echo '<td>'.$data_row['trajanje'].'</td>';
            echo '<td data-order="'.date("Y-n-j", strtotime($data_row['time'])).'">'.date("j.n.Y H:i", strtotime($data_row['time'])).'</td>';
            echo '<td>'.$lang['srv_narocilo_nacin_placila_'.$data_row['payment_method']].'</td>';

            // Ali placa ddv (podjetje - zavezanec iz tujine ga ne)
            echo '<td>'.($this->isWithoutDDV($data_row['id']) ? $lang['no'] : $lang['yes']).'</td>';

            // Cena
            $cena = $this->getPrice($data_row['package_name'], $data_row['trajanje'], $data_row['discount'], $data_row['time']);

            // Zavezanec iz tujine nima ddv-ja
            if($this->isWithoutDDV($data_row['id']))
                echo '<td>'.$cena['final_without_tax'].'</td>';
            else
                echo '<td>'.$cena['final'].'</td>';

            echo '<td class="'.$status_color.'">';

            echo $lang['srv_narocilo_status_'.$data_row['status']];

            // Na www.1ka.si lahko narocilo placa samo Goran
            if($data_row['status'] != '1' && $data_row['status'] != '2' && (AppSettings::getInstance()->getSetting('app_settings-app_name') != 'www.1ka.si' || $global_user_id == '112696')){
                echo '<br />';
                echo '<span class="as_link" onClick="urediNarociloPay(\''.$data_row['id'].'\')">'.$lang['srv_narocilo_placaj'].'</span>';

                if($data_row['podjetje_eracun'] == '1')
                    echo ' | <span class="as_link" onClick="urediNarociloPayEracun(\''.$data_row['id'].'\')">'.$lang['srv_narocilo_placaj_eracun'].'</span>';
            }
    
            echo '</td>';

            // Eračun
            echo '<td>'.($data_row['podjetje_eracun'] == '1' ? $lang['yes'] : $lang['no']).'</td>';

            echo '<td>';
            // Ce je bila cena 0 je bil avtomatsko "placan" in nima racuna oz. predracuna
            if($cena['final'] == 0){
                echo '/';
            }
            else{
                echo '<span class="pointer as_link" onClick="getNarociloPredracun(\''.$data_row['id'].'\')">'.$lang['srv_narocilo_pdf_predracun'].'</span>';
                if($data_row['status'] == '1'){
                    echo ' | <span class="pointer as_link" onClick="getNarociloRacun(\''.$data_row['id'].'\')">'.$lang['srv_narocilo_pdf_racun'].'</span>';
                }
            }
            echo '</td>'; 

            echo '<td>';
            echo '<a href="#" onClick="displayNarociloPopup(\''.$data_row['id'].'\')" title="'.$lang['srv_narocila_edit'].'"><i class="fa fa-pencil-alt link-sv-moder"></i>';
            // Narocilo se lahko pobrise samo ce se ni placano
            if($data_row['status'] != '1')
                echo ' <span class="no-print"> | </span><a href="#" onClick="brisiNarocilo(\''.$data_row['id'].'\')" title="'.$lang['srv_narocila_delete'].'"><i class="fa fa-times link-sv-moder"></a>';
            echo '</td>';

            echo '</tr>';
        }

        echo '  </tbody>';
        
        echo '</table>';

        // Se inicializiramo dataTable jquery
        echo '<script> prepareNarocilaTableAdmin(); </script>';
    }

    // Prikazemo popup za pregled in urejanje narocilo
    private function displayNarociloEdit($narocilo_id){
        global $lang;

        // Loop po vseh narocilih uporabnika
        $sqlNarocilo = sisplet_query("SELECT un.*, u.name, u.surname, u.email, up.name AS package_name, up.description AS package_description, up.price AS package_price 
                                        FROM user_access_narocilo un, users u, user_access_paket up
                                        WHERE un.id='".$narocilo_id."' AND un.usr_id=u.id AND un.package_id=up.id
                                    ");
        
        if(mysqli_num_rows($sqlNarocilo) == 0){
            echo 'Naročilo ne obstaja!';
            return;
        }
        
        $rowNarocilo = mysqli_fetch_array($sqlNarocilo);

        echo '<h2>'.$lang['srv_narocilo_number'].' '.$narocilo_id.'</h2>';


        echo '<div class="edit_narocilo_content">';

        echo '<div class="form_holder"><form name="edit_narocilo" id="edit_narocilo">';
        
        echo '<input type="hidden" name="narocilo_id" value="'.$narocilo_id.'">';


        // Podatki narocnika
        echo '<div class="edit_narocilo_segment">';

        // Ime in uporabnik
        echo '<div class="edit_narocilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_ime'].':</span> '.$rowNarocilo['ime'];
        echo '</div>';

        echo '<div class="edit_narocilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_uporabnik'].':</span> '.$rowNarocilo['name'].' '.$rowNarocilo['surname'].' ('.$rowNarocilo['email'].')';
        echo '</div>';

        // Telefon
        echo '<div class="edit_narocilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_telefon'].':</span> ';
        //echo '<input type="hidden" name="phone" value="'.$rowNarocilo['phone'].'">';
        echo '<input type="text" name="phone" value="'.$rowNarocilo['phone'].'">';
        echo '</div>';

        echo '</div>';


        // Podatki narocila
        $cena = $this->getPrice($rowNarocilo['package_name'], $rowNarocilo['trajanje'], $rowNarocilo['discount'], $rowNarocilo['time']);

        echo '<div class="edit_narocilo_segment">';

        // Cas narocila
        echo '<div class="edit_narocilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_cas'].':</span> '.date("j.n.Y H:i", strtotime($rowNarocilo['time']));
        echo '<input type="hidden" name="time" value="'.$rowNarocilo['time'].'">';
        echo '</div>';

        // Paket
        echo '<div class="edit_narocilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_paket'].':</span> ';
        if($rowNarocilo['status'] == '1'){
            echo $rowNarocilo['package_id'].'ka';
            echo '<input type="hidden" name="package_id" value="'.$rowNarocilo['package_id'].'">';
        }
        else{
            echo '<select name="package_id">';
            echo '<option value="1" '.($rowNarocilo['package_id'] == '1' ? 'selected="selected"' : '').'>1ka</option>';
            echo '<option value="2" '.($rowNarocilo['package_id'] == '2' ? 'selected="selected"' : '').'>2ka</option>';
            echo '<option value="3" '.($rowNarocilo['package_id'] == '3' ? 'selected="selected"' : '').'>3ka</option>';
            echo '</select>';
        }
        echo '</div>';

        // Trajanje v mesecih
        echo '<div class="edit_narocilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_trajanje'].':</span> ';
        if($rowNarocilo['status'] == '1'){
            echo $rowNarocilo['trajanje'].' '.$lang['srv_narocilo_trajanje_mesecev'];
            echo '<input type="hidden" name="trajanje" value="'.$rowNarocilo['trajanje'].'">';
        }
        else{
            echo '<input type="text" name="trajanje" value="'.$rowNarocilo['trajanje'].'" size="4"> '.$lang['srv_narocilo_trajanje_mesecev'];
        }
        echo '</div>';

        // Cena paketa * trajanje
        echo '<div class="edit_narocilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_cena_brez_popusta'].':</span> '.$cena['full'].' € ('.$rowNarocilo['trajanje'].' '.$lang['srv_narocilo_trajanje_mesecev'].')';
        echo '</div>';

        // Popust
        echo '<div class="edit_narocilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_popust'].':</span> ';
        if($rowNarocilo['status'] == '1'){
            echo $rowNarocilo['discount'].' %';
            echo '<input type="hidden" name="discount" value="'.$rowNarocilo['discount'].'">';
        }
        else{
            echo '<input type="text" name="discount" value="'.$rowNarocilo['discount'].'" size="4"> %';
        }
        echo '</div>';

        // Koncna cena - zavezanec iz tujine nima ddv-ja
        echo '<div class="edit_narocilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_cena'].':</span> '.($this->isWithoutDDV($data_row['id']) ? $cena['final_without_tax'] : $cena['final']).' €';
        echo '</div>';

        // Nacin placila
        echo '<div class="edit_narocilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_nacin_placila'].':</span> ';
        if($rowNarocilo['status'] == '1'){
            echo $lang['srv_narocilo_nacin_placila_'.$rowNarocilo['payment_method']];
            echo '<input type="hidden" name="payment_method" value="'.$rowNarocilo['payment_method'].'">';
        }
        else{
            echo '<select name="payment_method">';
            echo '<option value="1" '.($rowNarocilo['payment_method'] == '1' ? 'selected="selected"' : '').'>'.$lang['srv_narocilo_nacin_placila_1'].'</option>';
            echo '<option value="2" '.($rowNarocilo['payment_method'] == '2' ? 'selected="selected"' : '').'>'.$lang['srv_narocilo_nacin_placila_2'].'</option>';
            echo '<option value="3" '.($rowNarocilo['payment_method'] == '3' ? 'selected="selected"' : '').'>'.$lang['srv_narocilo_nacin_placila_3'].'</option>';
            echo '</select>';
        }
        echo '</div>';

        // Status narocila
        echo '<div class="edit_narocilo_line">';
        echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_status'].':</span> ';
        echo $lang['srv_narocilo_status_'.$rowNarocilo['status']];
        echo '<input type="hidden" name="status" value="'.$rowNarocilo['status'].'">';
        /*echo '<select name="status">';
        echo '<option value="0" '.($rowNarocilo['status'] == '0' ? 'selected="selected"' : '').'>'.$lang['srv_narocilo_status_0'].'</option>';
        echo '<option value="1" '.($rowNarocilo['status'] == '1' ? 'selected="selected"' : '').' disabled="disabled">'.$lang['srv_narocilo_status_1'].'</option>';
        echo '<option value="2" '.($rowNarocilo['status'] == '2' ? 'selected="selected"' : '').'>'.$lang['srv_narocilo_status_2'].'</option>';
        echo '</select>';*/
        echo '</div>';

        echo '</div>';


        // Podatki podjetja ce je racun na podjetje
        if($rowNarocilo['podjetje_ime'] != ''){
            echo '<div class="edit_narocilo_segment">';

            echo '<div class="edit_narocilo_line">';
            echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_podjetje_ime'].':</span> ';
            //echo '<input type="hidden" name="podjetje_ime" value="'.$rowNarocilo['podjetje_ime'].'">';
            echo '<input type="text" name="podjetje_ime" value="'.$rowNarocilo['podjetje_ime'].'">';
            echo '</div>';

            echo '<div class="edit_narocilo_line">';
            echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_podjetje_naslov'].':</span> ';
            //echo '<input type="hidden" name="podjetje_naslov" value="'.$rowNarocilo['podjetje_naslov'].'">';
            echo '<input type="text" name="podjetje_naslov" value="'.$rowNarocilo['podjetje_naslov'].'">';
            echo '</div>';

            echo '<div class="edit_narocilo_line">';
            echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_podjetje_postna'].':</span> ';
            //echo '<input type="hidden" name="podjetje_postna" value="'.$rowNarocilo['podjetje_postna'].'">';
            echo '<input type="text" name="podjetje_postna" value="'.$rowNarocilo['podjetje_postna'].'">';
            echo '</div>';

            echo '<div class="edit_narocilo_line">';
            echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_podjetje_posta'].':</span> ';
            //echo '<input type="hidden" name="podjetje_posta" value="'.$rowNarocilo['podjetje_posta'].'">';
            echo '<input type="text" name="podjetje_posta" value="'.$rowNarocilo['podjetje_posta'].'">';
            echo '</div>';

            echo '<div class="edit_narocilo_line">';
            echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_podjetje_drzava'].':</span> ';
            echo '<input type="text" name="podjetje_drzava" value="'.$rowNarocilo['podjetje_drzava'].'">';
            echo '</div>';

            echo '<div class="edit_narocilo_line">';
            echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_podjetje_davcna'].':</span> ';
            //echo '<input type="hidden" name="podjetje_davcna" value="'.$rowNarocilo['podjetje_davcna'].'">';
            echo '<input type="text" name="podjetje_davcna" value="'.$rowNarocilo['podjetje_davcna'].'">';
            echo '</div>';

            echo '<div class="edit_narocilo_line">';
            echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_podjetje_no_ddv'].':</span> ';
            echo '<select name="podjetje_no_ddv">';
            echo '<option value="0" '.($rowNarocilo['podjetje_no_ddv'] == '0' ? 'selected="selected"' : '').'>'.$lang['no'].'</option>';
            echo '<option value="1" '.($rowNarocilo['podjetje_no_ddv'] == '1' ? 'selected="selected"' : '').'>'.$lang['yes'].'</option>';
            echo '</select>';
            echo '</div>';

            echo '<div class="edit_narocilo_line">';
            echo '<span class="nastavitveSpan5">'.$lang['srv_narocilo_podjetje_eracun'].':</span> ';
            echo '<select name="podjetje_eracun">';
            echo '<option value="0" '.($rowNarocilo['podjetje_eracun'] == '0' ? 'selected="selected"' : '').'>'.$lang['no'].'</option>';
            echo '<option value="1" '.($rowNarocilo['podjetje_eracun'] == '1' ? 'selected="selected"' : '').'>'.$lang['yes'].'</option>';
            echo '</select>';
            echo '</div>';

            echo '</div>';
        }

        echo '</form></div>';

        // Gumbi na dnu
		echo '<div class="buttons_holder">';
        echo '  <div class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="urediNarociloSave();"><span>'.$lang['edit1337'].'</span></a></div>';
        echo '  <div class="buttonwrapper floatRight spaceRight"><a class="ovalbutton ovalbutton_gray" href="#" onclick="urediNarociloClose();"><span>'.$lang['srv_zapri'].'</span></a></div>';
        echo '</div>';

        echo '</div>';
    }

    

    // Ustvari novo narocilo za uporabnika
    public function createNarocilo($narocilo_data){
        global $global_user_id;
        global $lang;

        $response = array();

        if(isset($narocilo_data['email'])){
            $uporabnik = sisplet_query("SELECT id, email FROM users WHERE email='".$narocilo_data['email']."'", "obj");
            $usr_id = $uporabnik->id;
        }

        $status = isset($narocilo_data['status']) ? $narocilo_data['status'] : 0;
        $package_id = isset($narocilo_data['package_id']) ? $narocilo_data['package_id'] : 1;
        $payment_method = isset($narocilo_data['payment_method']) ? $narocilo_data['payment_method'] : 0;
        //$discount = isset($narocilo_data['discount']) ? $narocilo_data['discount'] : 0;
        $trajanje = isset($narocilo_data['trajanje']) ? $narocilo_data['trajanje'] : 0;

        $ime_na_racunu = isset($narocilo_data['ime']) ? $narocilo_data['ime'] : '';
        $phone = isset($narocilo_data['phone']) ? $narocilo_data['phone'] : '';

        $podjetje_ime = isset($narocilo_data['podjetje_ime']) ? $narocilo_data['podjetje_ime'] : '';
        $podjetje_naslov = isset($narocilo_data['podjetje_naslov']) ? $narocilo_data['podjetje_naslov'] : '';
        $podjetje_postna = isset($narocilo_data['podjetje_postna']) ? $narocilo_data['podjetje_postna'] : '';
        $podjetje_posta = isset($narocilo_data['podjetje_posta']) ? $narocilo_data['podjetje_posta'] : '';
        $podjetje_drzava = isset($narocilo_data['podjetje_drzava']) ? $narocilo_data['podjetje_drzava'] : '';
        $podjetje_davcna = isset($narocilo_data['podjetje_davcna']) ? $narocilo_data['podjetje_davcna'] : '';
        $podjetje_eracun = isset($narocilo_data['podjetje_eracun']) ? '1' : '0';
        
        $language = isset($narocilo_data['lang']) ? $narocilo_data['lang'] : 'sl';
        
        if($usr_id <= 0){
            $response['error'] = 'ERROR! Missing user ID.';
            $response['success'] = false;
            
            return $response;
        }

        // Ce je slucajno drzava prazna jo nastavimo na slovenijo - zankrat pustimo, da vidimo, ce se se kdaj poslje prazno polje (naceloma se nebi smelo)
        /*if($podjetje_drzava == '')
            $podjetje_drzava = 'Slovenija';*/
        
        // Nastavimo ce placa DDV (zavezanci iz EU ga ne placajo)
        if(self::checkPayDDV($podjetje_davcna, $podjetje_drzava))
            $podjetje_no_ddv = '0';
        else
            $podjetje_no_ddv = '1';

        $brezplacen_preklop = false;

        // Preverimo, ce ima uporabnik ze aktiven paket - po novem lahko to predhodno preklaplja, ker se to preracuna v popust
        $sqlAccess = sisplet_query("SELECT * FROM user_access WHERE usr_id='".$usr_id."' AND package_id != '1' AND time_expire > NOW()");
        if(mysqli_num_rows($sqlAccess) > 0){

            $rowAccess = mysqli_fetch_array($sqlAccess);

            // Ce zeli uporabnik kupiti drug placljiv paket kot ga ima trenutno, preracunamo obstojec paket v popust
            if($rowAccess['package_id'] != $package_id){
                $sqlPackage = sisplet_query("SELECT name FROM user_access_paket WHERE id='".$package_id."'");
                $rowPackage = mysqli_fetch_array($sqlPackage);

                $discount = $this->getDiscount($usr_id, $rowPackage['name'], $trajanje);

                // Preverimo, ce je cena slucajno 0 - oznacimo, da gre za brezplacen preklop
                $cena = $this->getPrice($rowPackage['name'], $trajanje, $discount);
                if($cena['final'] == 0){
                    $brezplacen_preklop = true;

                    // Dodamo piškotek, če gre za brezplačni nakup
                    global $cookie_domain;
                    setcookie('brezplacen_preklop', 1, time()+1800, '/', $cookie_domain);
                }
            }
        }

        $sqlNarocilo = sisplet_query("INSERT INTO user_access_narocilo 
                                        (usr_id, status, time, package_id, ime, payment_method, discount, trajanje, phone, podjetje_ime, podjetje_naslov, podjetje_postna, podjetje_posta, podjetje_drzava, podjetje_davcna, podjetje_no_ddv, podjetje_eracun, language)
                                        VALUES
                                        ('".$usr_id."', '".$status."', NOW(), '".$package_id."', '".$ime_na_racunu."', '".$payment_method."', '".$discount."', '".$trajanje."', '".$phone."', '".$podjetje_ime."', '".$podjetje_naslov."', '".$podjetje_postna."', '".$podjetje_posta."', '".$podjetje_drzava."', '".$podjetje_davcna."', '".$podjetje_no_ddv."', '".$podjetje_eracun."', '".$language."')
                                    ");
        if (!$sqlNarocilo){
            $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
            $response['success'] = false;

            return $response;
        }
     
        $narocilo_id = mysqli_insert_id($GLOBALS['connect_db']);


        // Ce gre za brezplacen preklop izvedemo placilo (aktivacijo paketa) brez izdaje racuna, maila...
        if($brezplacen_preklop){
            $response = $this->payNarocilo($narocilo_id, $brezplacen_preklop);
        }
        // Drugace izdamo predracun oz. placamo s kartico/paypalom
        else{

            // Glede na tip plačila dobmo ustrezen url (predracun, paypal, kartica)
            // Paypal
            if($payment_method == '2'){
                $response = $this->finishNarociloPaypal($narocilo_id, $narocilo_data);
            }
            // Kartica
            elseif($payment_method == '3'){
                $response = $this->finishNarociloStripe($narocilo_id, $narocilo_data);                      
            }
            // Predracun
            else{
                $response = $this->finishNarociloPredracun($narocilo_id, $narocilo_data);
            }
        }
        

        return $response;
    }

    // Dokoncaj narocilo s placilom preko predracuna
    private function finishNarociloPredracun($narocilo_id, $narocilo_data){
        global $lang;

        $response = array();
        $response['narocilo_id'] = $narocilo_id;

        $cebelica = new UserNarocilaCebelica($narocilo_id);
        $response['payment_link'] = $cebelica->getNarociloPredracun();

        // Posljemo mail s predracunom
        $subject = $lang['srv_narocilo_email_predracun_subject'].' '.$narocilo_id;  

        $content = $lang['srv_narocilo_email_predracun_content1'];
        $content .= '<br /><br />'.$lang['srv_narocilo_email_predracun_content2'];
        $content .= '<br /><a href="'.$response['payment_link'].'">'.$lang['srv_narocilo_email_predracun_file'].'</a>';

        // Podpis
        $signature = Common::getEmailSignature();
        $content .= $signature;

        try{
            $MA = new MailAdapter($anketa=null, $type='payments');
            
            $MA->addRecipients($narocilo_data['email']);

            // Dodamo predracun v attachment
            $MA->addAttachment(file_get_contents($response['payment_link']), $file_name='1ka_narocilo_'.$narocilo_id.'_predracun.pdf');

            // Posljemo mail
            $resultX = $MA->sendMail($content, $subject);

            $response['success'] = true;
        }
        catch (Exception $e){
            $response['error'] = 'ERROR! Sending email with invoice failed.';
            $response['success'] = false;

            return $response;
        }

        return $response;
    }

    // Dokoncaj narocilo s placilom preko predracuna
    private function finishNarociloStripe($narocilo_id, $narocilo_data){
        global $lang;

        $response = array();

        // Inicializiramo paypal
        $stripe = new UserNarocilaStripe($narocilo_id);

        // Ustvarimo stripe session za placilo in vrnemo id sessiona, da uporabnik potrdi placilo
        $stripe_response = $stripe->stripeCreateSession();

        // Ce je bilo placilo preko stripa uspesno zgeneriramo racun in uporabniku aktiviramo paket
        if($stripe_response['success'] == true){   
            $response['session_id'] = $stripe_response['session_id'];
            $response['success'] = true;
        }
        else{
            $response['error'] = $stripe_response['error'];
            $response['success'] = false;
        }

        return $response;
    }

    // Dokoncaj narocilo s placilom preko predracuna
    private function finishNarociloPaypal($narocilo_id, $narocilo_data){
        global $lang;

        $response = array();

        // Inicializiramo paypal
        $paypal = new UserNarocilaPaypal($narocilo_id);

        // Ustvarimo paypal placilo in vrnemo url, da se uporabnik prijavi v paypal in potrdi placilo
        $paypal_response = $paypal->paypalCreatePayment();

        // Ce je bilo placilo preko stripa uspesno zgeneriramo racun in uporabniku aktiviramo paket
        if($paypal_response['success'] == true){   
            $response['paypal_link'] = $paypal_response['paypal_link'];
            $response['success'] = true;
        }
        else{
            $response['error'] = $paypal_response['error'];
            $response['success'] = false;
        }

        return $response;
    }



    // Posodobi obstojece narocilo za uporabnika
    public function updateNarocilo($narocilo_data){
        global $global_user_id;

        $response = array();

        // ce nimamo id-ja narocila vrnemo error
        if(!isset($narocilo_data['narocilo_id']) || $narocilo_data['narocilo_id'] == '0'){
            $response['error'] = 'Napaka! Manjka ID narocila!';
            $response['success'] = false;

            return $response;
        }

        $update = '';

        $update .= isset($narocilo_data['status']) ? ', status='.$narocilo_data['status'] : '';
        $update .= isset($narocilo_data['package_id']) ? ', package_id='.$narocilo_data['package_id'] : '';
        $update .= isset($narocilo_data['payment_method']) ? ', payment_method='.$narocilo_data['payment_method'] : '';
        $update .= isset($narocilo_data['discount']) ? ', discount='.$narocilo_data['discount'] : '';
        $update .= isset($narocilo_data['ime']) ? ', ime='.$narocilo_data['ime'] : '';
        $update .= isset($narocilo_data['trajanje']) ? ', trajanje='.$narocilo_data['trajanje'] : '';

        $update .= isset($narocilo_data['phone']) ? ', phone='.$narocilo_data['phone'] : '';

        $update .= isset($narocilo_data['podjetje_ime']) ? ', podjetje_ime='.$narocilo_data['podjetje_ime'] : '';
        $update .= isset($narocilo_data['podjetje_naslov']) ? ', podjetje_naslov='.$narocilo_data['podjetje_naslov'] : '';
        $update .= isset($narocilo_data['podjetje_postna']) ? ', podjetje_postna='.$narocilo_data['podjetje_postna'] : '';
        $update .= isset($narocilo_data['podjetje_posta']) ? ', podjetje_posta='.$narocilo_data['podjetje_posta'] : '';
        $update .= isset($narocilo_data['podjetje_drzava']) ? ', podjetje_drzava='.$narocilo_data['podjetje_drzava'] : '';
        $update .= isset($narocilo_data['podjetje_davcna']) ? ', podjetje_davcna='.$narocilo_data['podjetje_davcna'] : '';
        $update .= isset($narocilo_data['podjetje_no_ddv']) ? ', podjetje_no_ddv='.$narocilo_data['podjetje_no_ddv'] : '';
        $update .= isset($narocilo_data['podjetje_eracun']) ? ', podjetje_eracun='.$narocilo_data['podjetje_eracun'] : '';

        $update = substr($update, 1);

        // Update narocila in pobrisemo id racuna in predracuna, ker ga moramo generirati na novo
        $sqlNarocilo = sisplet_query("UPDATE user_access_narocilo SET ".$update.", cebelica_id_racun='0', cebelica_id_predracun='0' WHERE id='".$narocilo_data['narocilo_id']."'");
        if (!$sqlNarocilo){
            $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
            $response['success'] = false;

            return $response;
        }

        $response['success'] = true;

        return $response;
    }

    // Placa obstojece narocilo, uporabniku aktivira paket, zgenerira racun in ga poslje po mailu
    public function payNarocilo($narocilo_id, $brezplacen_preklop=false){
        global $global_user_id;
        global $lang;

        $response = array();

        // Ce nimamo id-ja narocila vrnemo error
        if($narocilo_id == 0){
            $response['error'] = 'Napaka! Manjka ID narocila!';
            $response['success'] = false;
            
            return $response;
        }


        // Dobimo podatke narocila
        $sqlNarocilo = sisplet_query("SELECT n.*, u.email FROM user_access_narocilo n, users u WHERE n.id='".$narocilo_id."' AND u.id=n.usr_id");
        $rowNarocilo = mysqli_fetch_array($sqlNarocilo);

        // Ce je bil racun ze placan ne naredimo nicesar
        if($rowNarocilo['status'] == 1){
            $response['error'] = 'Napaka! Račun je že plačan!';
            $response['success'] = false;
            
            return $response;
        }


        // Nastavimo ustrezen jezik - mail mora biti v istem jeziku kot je bilo narocilo
        if($rowNarocilo['language'] == 'en'){
            include('../../lang/2.php');
        }


        // Preverimo, ce ima uporabnik ze aktiven paket in ce je ta paket isti kot ta, ki ga je kupil
        $sqlAccessCheck = sisplet_query("SELECT * FROM user_access WHERE usr_id='".$rowNarocilo['usr_id']."' AND package_id != '1' AND time_expire > NOW()");
        if(mysqli_num_rows($sqlAccessCheck) > 0){

            $rowAccessCheck = mysqli_fetch_array($sqlAccessCheck);

            // Ce zeli uporabnik kupiti drug placljiv paket kot ga ima trenutno, ga zavrnemo - po novem normalno izvedemo ker preracunamo v popust
            if($rowAccessCheck['package_id'] != $rowNarocilo['package_id']){

                // Nastavimo dostop uporabniku
                $sqlAccess = sisplet_query("INSERT INTO user_access 
                                            (usr_id, time_activate, time_expire, package_id) 
                                            VALUES
                                            ('".$rowNarocilo['usr_id']."', NOW(), NOW() + INTERVAL '".$rowNarocilo['trajanje']."' MONTH, '".$rowNarocilo['package_id']."')
                                            ON DUPLICATE KEY UPDATE 
                                                time_activate=NOW(), time_expire=NOW() + INTERVAL '".$rowNarocilo['trajanje']."' MONTH, package_id='".$rowNarocilo['package_id']."'
                                        ");
                if (!$sqlAccess){
                    $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
                    $response['success'] = false;

                    return $response;
                }
            }
            // Uporabnik kupuje isti paket kot ga ze ima - mu ga samo podaljsamo
            else{
                $sqlAccess = sisplet_query("UPDATE user_access SET time_expire = time_expire + INTERVAL '".$rowNarocilo['trajanje']."' MONTH WHERE usr_id='".$rowNarocilo['usr_id']."'");
                if (!$sqlAccess){
                    $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
                    $response['success'] = false;

                    return $response;
                }
            }
        }
        else{
            
            // Nastavimo dostop uporabniku
            $sqlAccess = sisplet_query("INSERT INTO user_access 
                                            (usr_id, time_activate, time_expire, package_id) 
                                            VALUES
                                            ('".$rowNarocilo['usr_id']."', NOW(), NOW() + INTERVAL '".$rowNarocilo['trajanje']."' MONTH, '".$rowNarocilo['package_id']."')
                                            ON DUPLICATE KEY UPDATE 
                                                time_activate=NOW(), time_expire=NOW() + INTERVAL '".$rowNarocilo['trajanje']."' MONTH, package_id='".$rowNarocilo['package_id']."'
                                        ");
            if (!$sqlAccess){
                $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
                $response['success'] = false;

                return $response;
            }
        }


        // Nastavimo status narocila na placan
        $sqlNarociloStatus = sisplet_query("UPDATE user_access_narocilo SET status='1' WHERE id='".$narocilo_id."'");
        if (!$sqlNarociloStatus){
            $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
            $response['success'] = false;
            
            return $response;
        }


        // Brezplacen preklop - samo posljemo mail z obvestilom o vklopu paketa
        if($brezplacen_preklop){

            // Posljemo mail z obvestilom
            $subject = $lang['srv_narocilo_free_email_subject'].' '.$rowNarocilo['id'];  

            $content = $lang['srv_narocilo_free_email_content1'];
            $content .= '<br /><br />'.$lang['srv_narocilo_free_email_content2'];

            // Podpis
            $signature = Common::getEmailSignature();
            $content .= $signature;

            try{
                $MA = new MailAdapter($anketa=null, $type='payments');
                $MA->addRecipients($rowNarocilo['email']);
                $resultX = $MA->sendMail($content, $subject);
            }
            catch (Exception $e){
                $response['false'] = true;
            }	
        }
        // Ce ne gre za brezplacen preklop zapisemo v placila, izdamo racun in posljemo mail
        else{

            // Ustvarimo placilo v tabeli placil
            $up = new UserPlacila();
            $up->createPlacilo($rowNarocilo);

            
            // Ustvarimo racun
            $cebelica = new UserNarocilaCebelica($narocilo_id);
            $response['racun'] = $cebelica->getNarociloRacun();


            // Posljemo mail z racunom
            $subject = $lang['srv_narocilo_email_subject'].' '.$rowNarocilo['id'];  

            $content = $lang['srv_narocilo_email_content1'];
            $content .= '<br /><br />'.$lang['srv_narocilo_email_content2'];
            $content .= '<br /><a href="'.$response['racun'].'">'.$lang['srv_narocilo_email_file'].'</a>';


            // Podpis
            $signature = Common::getEmailSignature();
            $content .= $signature;

            try{
                $MA = new MailAdapter($anketa=null, $type='payments');

                $MA->addRecipients($rowNarocilo['email']);

                // Dodamo predracun v attachment
                $MA->addAttachment(file_get_contents($response['racun']), $file_name='1ka_narocilo_'.$rowNarocilo['id'].'_racun.pdf');

                $resultX = $MA->sendMail($content, $subject);
            }
            catch (Exception $e){
                $response['false'] = true;
            }	
        }


        $response['success'] = true;

        return $response;
    }

    // Placa narocilo - za eracune, kjer se jih zabelezi kot placane ampak imajo 30 dnevni rok
    public function payNarociloEracun($narocilo_id){
        global $global_user_id;
        global $lang;

        $response = array();

        // Ce nimamo id-ja narocila vrnemo error
        if($narocilo_id == 0){
            $response['error'] = 'Napaka! Manjka ID narocila!';
            $response['success'] = false;
            
            return $response;
        }


        // Dobimo podatke narocila
        $sqlNarocilo = sisplet_query("SELECT n.*, u.email FROM user_access_narocilo n, users u WHERE n.id='".$narocilo_id."' AND u.id=n.usr_id");
        $rowNarocilo = mysqli_fetch_array($sqlNarocilo);

        // Ce je bil racun ze placan ne naredimo nicesar
        if($rowNarocilo['status'] == 1){
            $response['error'] = 'Napaka! Račun je že plačan!';
            $response['success'] = false;
            
            return $response;
        }


        // Nastavimo ustrezen jezik - mail mora biti v istem jeziku kot je bilo narocilo
        if($rowNarocilo['language'] == 'en'){
            include('../../lang/2.php');
        }


        // Preverimo, ce ima uporabnik ze aktiven paket in ce je ta paket isti kot ta, ki ga je kupil
        $sqlAccessCheck = sisplet_query("SELECT * FROM user_access WHERE usr_id='".$rowNarocilo['usr_id']."' AND package_id != '1' AND time_expire > NOW()");
        if(mysqli_num_rows($sqlAccessCheck) > 0){

            $rowAccessCheck = mysqli_fetch_array($sqlAccessCheck);

            // Ce zeli uporabnik kupiti drug placljiv paket kot ga ima trenutno, ga zavrnemo - po novem normalno izvedemo ker preracunamo v popust
            if($rowAccessCheck['package_id'] != $rowNarocilo['package_id']){

                // Nastavimo dostop uporabniku
                $sqlAccess = sisplet_query("INSERT INTO user_access 
                                            (usr_id, time_activate, time_expire, package_id) 
                                            VALUES
                                            ('".$rowNarocilo['usr_id']."', NOW(), NOW() + INTERVAL '".$rowNarocilo['trajanje']."' MONTH, '".$rowNarocilo['package_id']."')
                                            ON DUPLICATE KEY UPDATE 
                                                time_activate=NOW(), time_expire=NOW() + INTERVAL '".$rowNarocilo['trajanje']."' MONTH, package_id='".$rowNarocilo['package_id']."'
                                        ");
                if (!$sqlAccess){
                    $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
                    $response['success'] = false;

                    return $response;
                }
            }
            // Uporabnik kupuje isti paket kot ga ze ima - mu ga samo podaljsamo
            else{
                $sqlAccess = sisplet_query("UPDATE user_access SET time_expire = time_expire + INTERVAL '".$rowNarocilo['trajanje']."' MONTH WHERE usr_id='".$rowNarocilo['usr_id']."'");
                if (!$sqlAccess){
                    $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
                    $response['success'] = false;

                    return $response;
                }
            }
        }
        else{
            
            // Nastavimo dostop uporabniku
            $sqlAccess = sisplet_query("INSERT INTO user_access 
                                            (usr_id, time_activate, time_expire, package_id) 
                                            VALUES
                                            ('".$rowNarocilo['usr_id']."', NOW(), NOW() + INTERVAL '".$rowNarocilo['trajanje']."' MONTH, '".$rowNarocilo['package_id']."')
                                            ON DUPLICATE KEY UPDATE 
                                                time_activate=NOW(), time_expire=NOW() + INTERVAL '".$rowNarocilo['trajanje']."' MONTH, package_id='".$rowNarocilo['package_id']."'
                                        ");
            if (!$sqlAccess){
                $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
                $response['success'] = false;

                return $response;
            }
        }


        // Nastavimo status narocila na placan
        $sqlNarociloStatus = sisplet_query("UPDATE user_access_narocilo SET status='1' WHERE id='".$narocilo_id."'");
        if (!$sqlNarociloStatus){
            $response['error'] = 'ERROR! '.mysqli_error($GLOBALS['connect_db']);
            $response['success'] = false;
            
            return $response;
        }


        // Ustvarimo placilo v tabeli placil
        $up = new UserPlacila();
        $up->createPlacilo($rowNarocilo, $eracun=true);

        
        // Ustvarimo racun - nima "markPayed"!
        $cebelica = new UserNarocilaCebelica($narocilo_id);
        $response['racun'] = $cebelica->getNarociloRacun($eracun=true);


        // Posljemo mail z racunom
        $subject = $lang['srv_narocilo_email_subject'].' '.$rowNarocilo['id'];  

        $content = $lang['srv_narocilo_email_content1'];
        $content .= '<br /><br />'.$lang['srv_narocilo_email_content2_eracun'];
        $content .= '<br /><a href="'.$response['racun'].'">'.$lang['srv_narocilo_email_file'].'</a>';


        // Podpis
        $signature = Common::getEmailSignature();
        $content .= $signature;

        try{
            $MA = new MailAdapter($anketa=null, $type='payments');

            $MA->addRecipients($rowNarocilo['email']);

            // Dodamo racun (brez "markPayed") v attachment
            $MA->addAttachment(file_get_contents($response['racun']), $file_name='1ka_narocilo_'.$rowNarocilo['id'].'_racun.pdf');

            $resultX = $MA->sendMail($content, $subject);
        }
        catch (Exception $e){
            $response['false'] = true;
        }	


        $response['success'] = true;

        return $response;
    }


    // Poslje mail z povprasevanjem za poslovne uporabnike (virtualna domena ali lastna instalacija)
    public function sendPoslovniUporabniki($narocilo_data){
        global $lang;
        global $global_user_id;

        $response = array();


        $ime = isset($narocilo_data['ime']) ? $narocilo_data['ime'] : '';
        $organizacija = isset($narocilo_data['organizacija']) ? $narocilo_data['organizacija'] : '';
        $naslov = isset($narocilo_data['naslov']) ? $narocilo_data['naslov'] : '';
        $telefon = isset($narocilo_data['telefon']) ? $narocilo_data['telefon'] : '';
        $email = isset($narocilo_data['email']) ? $narocilo_data['email'] : '';

        $paket = isset($narocilo_data['paket']) ? $narocilo_data['paket'] : '';
        
        // Virtualna domena ali instalacija na 1ka strezniku
        $vrsta_domene = isset($narocilo_data['vrsta_domene']) ? $narocilo_data['vrsta_domene'] : '';
        $domena = isset($narocilo_data['domena']) ? $narocilo_data['domena'] : '';
        
        // Lastna instalacija - paket
        $strinjanje_s_pogoji = isset($narocilo_data['strinjanje_s_pogoji']) ? $narocilo_data['strinjanje_s_pogoji'] : '';

        // Varnostno preverimo, če robot izpolni polje
        $varnostno_polje = isset($narocilo_data['varnostno-polje']) ? $narocilo_data['varnostno-polje'] : '';
        if(!empty($varnostno_polje)){
            return ['false' => true];
        }

          // Preverimo ReCaptcha
        if (in_array($paket, [1,2,3]) && AppSettings::getInstance()->getSetting('google-secret_captcha') !== false) {
            $recaptchaResponse = isset($narocilo_data['g-recaptcha-response']) ? $narocilo_data['g-recaptcha-response'] : '';
            $requestReCaptcha = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . AppSettings::getInstance()->getSetting('google-secret_captcha') . '&response=' . $recaptchaResponse);

            if (!strstr($requestReCaptcha, 'true')) {
                return ['false' => true];
            }
        }


        // Posljemo mail s podatki povprasevanja
        $subject = $lang['srv_narocilo_poslovni_email_1ka_subject'];  

        $content = $lang['srv_narocilo_poslovni_email_1ka_text'];
        $content .= '<br /><br />'.$lang['srv_narocilo_poslovni_email_1ka_text2'];
        $content .= '<br />'.$lang['srv_narocilo_poslovni_email_1ka_ime'].' <b>'.$ime.'</b>';
        $content .= '<br />'.$lang['srv_narocilo_poslovni_email_1ka_organizacija'].' <b>'.$organizacija.'</b>';
        $content .= '<br />'.$lang['srv_narocilo_poslovni_email_1ka_naslov'].' <b>'.$naslov.'</b>';
        $content .= '<br />'.$lang['srv_narocilo_poslovni_email_1ka_telefon'].' <b>'.$telefon.'</b>';
        $content .= '<br />'.$lang['srv_narocilo_poslovni_email_1ka_email'].' <b>'.$email.'</b>';

        // lastna instalacija na 1ka streziku
        if($paket == 2){
            $content .= '<br /><br />'.$lang['srv_narocilo_poslovni_email_1ka_paket'].' <b>'.$lang['srv_narocilo_poslovni_email_1ka_paket2'].'</b>';
            $content .= '<br />'.$lang['srv_narocilo_poslovni_email_1ka_paket1_1'].' <b>'.($vrsta_domene == '2' ? 'xxx.yyy.zz' : 'xxx.1ka.si').'</b>';
            $content .= '<br />'.$lang['srv_narocilo_poslovni_email_1ka_paket1_2'].' <b>'.$domena.'</b>';
        }
        // lastna instalacija - paket
        elseif($paket == 3){
            $content .= '<br /><br />'.$lang['srv_narocilo_poslovni_email_1ka_paket'].' <b>'.$lang['srv_narocilo_poslovni_email_1ka_paket3'].'</b>';
        }
        // Virtualna domena
        else{
            $content .= '<br /><br />'.$lang['srv_narocilo_poslovni_email_1ka_paket'].' <b>'.$lang['srv_narocilo_poslovni_email_1ka_paket1'].'</b>';
            $content .= '<br />'.$lang['srv_narocilo_poslovni_email_1ka_paket1_1'].' <b>'.($vrsta_domene == '2' ? 'xxx.yyy.zz' : 'xxx.1ka.si').'</b>';
            $content .= '<br />'.$lang['srv_narocilo_poslovni_email_1ka_paket1_2'].' <b>'.$domena.'</b>';
        }

        // Podpis
        $signature = Common::getEmailSignature();
        $content .= $signature;

        try{
            $MA = new MailAdapter();
            $MA->addRecipients('info@1ka.si');
            $resultX = $MA->sendMail($content, $subject);
		}
        catch (Exception $e){
            $response['false'] = true;
        }


        // Posljemo mail stranki o uspesnem prejemu
        // lastna instalacija na 1ka strezniku
        if($paket == 2){
            $subject = $lang['srv_narocilo_poslovni_email_stranka_subject_2'];  

            $content = $lang['srv_narocilo_poslovni_email_stranka_text_1_2'];
            $content .= ' <b>'.$domena.'</b> ';
            $content .= $lang['srv_narocilo_poslovni_email_stranka_text_2'];
        }
        // lastna instalacija - paket
        elseif($paket == 3){
            $subject = $lang['srv_narocilo_poslovni_email_stranka_subject_3'];  

            $content = $lang['srv_narocilo_poslovni_email_stranka_text_1_3'];
            $content .= ' ';
            $content .= $lang['srv_narocilo_poslovni_email_stranka_text_2'];
        }
        // Virtualna domena
        else{
            $subject = $lang['srv_narocilo_poslovni_email_stranka_subject_1'];  

            $content = $lang['srv_narocilo_poslovni_email_stranka_text_1_1'];
            $content .= ' <b>'.$domena.'</b> ';
            $content .= $lang['srv_narocilo_poslovni_email_stranka_text_2'];
        }

        // Podpis
        $signature = Common::getEmailSignature();
        $content .= $signature;

        try{
            $MA = new MailAdapter();
            $MA->addRecipients($email);
            $resultX = $MA->sendMail($content, $subject);
		}
        catch (Exception $e){
            $response['false'] = true;
        }


        $response['success'] = true;

        return $response;
    }

    // Izvede api klic kjer preveri davcno stevilko in zavezanost za DDV
    public static function checkPayDDV($davcna_stevilka, $drzava){
        global $lang;
        global $global_user_id;

        // Drzave EU brez slovenije
        $countries_eu = array();
        $countries_eu['Austria'] = 'AT';
        $countries_eu['Belgium'] = 'BE';
        $countries_eu['Bulgaria'] = 'BG';
        $countries_eu['Cyprus'] = 'CY';
        $countries_eu['Czech Republic'] = 'CZ';
        $countries_eu['Germany'] = 'DE';
        $countries_eu['Denmark'] = 'DK';
        $countries_eu['Estonia'] = 'EE';
        $countries_eu['Spain'] = 'ES';
        $countries_eu['Finland'] = 'FI';
        $countries_eu['France'] = 'FR';
        $countries_eu['United Kingdom'] = 'GB';
        $countries_eu['Greece'] = 'GR';
        $countries_eu['Hungary'] = 'HU';
        $countries_eu['Croatia'] = 'HR';
        $countries_eu['Ireland'] = 'IE';
        $countries_eu['Italy'] = 'IT';
        $countries_eu['Lithuania'] = 'LT';
        $countries_eu['Luxembourg'] = 'LU';
        $countries_eu['Latvia'] = 'LV';
        $countries_eu['Malta'] = 'MT';
        $countries_eu['Netherlands'] = 'NL';
        $countries_eu['Poland'] = 'PL';
        $countries_eu['Portugal'] = 'PT';
        $countries_eu['Romania'] = 'RO';
        $countries_eu['Sweden'] = 'SE';
        //$countries_eu['Slovenia'] = 'SI';
        $countries_eu['Slovakia'] = 'SK';


        // Ce drzava ni oznacena - placa DDV
        if($drzava == '')
            return true;

        // Slovenija - vedno placa DDV
        if($drzava == 'Slovenija' || $drzava == 'Slovenia')
            return true;

        // Ce ni drzava s seznama in ni Slovenija - po novem nikoli ne placa DDV
        if(!isset($countries_eu[$drzava]))
            return false;

        // Drugace gre za tujca iz EU
        // Pocistimo davcno stevilko - ohranimo samo stevilke ce je vnesel v obliki "DE12345678" -> "12345678"
        $davcna_stevilka = preg_replace('[\D]', '', $davcna_stevilka);

        // Preverimo, ce je zavezanec
        $client = new SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
        try{
            $response = $client->checkVat( array('countryCode' => $countries_eu[$drzava], 'vatNumber' => $davcna_stevilka) );
        }
        catch (Exception $e) {
            return true;
        }

        // Je valid zavezanec iz EU
        if(isset($response->valid) && $response->valid == true)
            return false;

        return true;
    }


    // Dobimo podatke o vseh paketih
    private function cachePackages(){

        $sqlPackages = sisplet_query("SELECT * FROM user_access_paket");
        while($row = mysqli_fetch_array($sqlPackages)){
            $this->packages[$row['name']] = $row;
        }
    }


    // Ajax klici
    public function ajax(){

        $narocilo_id = (isset($_POST['narocilo_id'])) ? $_POST['narocilo_id'] : 0;


        // Prikazemo popup z urejanjem posameznega narocila
        if($_GET['a'] == 'displayNarociloPopup') {

            if($narocilo_id > 0)
                $this->displayNarociloEdit($narocilo_id);
        }

        // Urejamo narocilo
        if($_GET['a'] == 'editNarocilo') {

            if($narocilo_id > 0){

                $update = '';
    
                $update .= (isset($_POST['status'])) ? " status='".$_POST['status']."'," : "";
                $update .= (isset($_POST['package_id'])) ? " package_id='".$_POST['package_id']."'," : "";
                $update .= (isset($_POST['payment_method'])) ? " payment_method='".$_POST['payment_method']."'," : "";
                $update .= (isset($_POST['discount'])) ? " discount='".$_POST['discount']."'," : "";
                $update .= (isset($_POST['trajanje'])) ? " trajanje='".$_POST['trajanje']."'," : "";
    
                $update .= (isset($_POST['phone'])) ? " phone='".$_POST['phone']."'," : "";
    
                $update .= (isset($_POST['podjetje_ime'])) ? " podjetje_ime='".$_POST['podjetje_ime']."'," : "";
                $update .= (isset($_POST['podjetje_naslov'])) ? " podjetje_naslov='".$_POST['podjetje_naslov']."'," : "";
                $update .= (isset($_POST['podjetje_postna'])) ? " podjetje_postna='".$_POST['podjetje_postna']."'," : "";
                $update .= (isset($_POST['podjetje_posta'])) ? " podjetje_posta='".$_POST['podjetje_posta']."'," : "";
                $update .= (isset($_POST['podjetje_drzava'])) ? " podjetje_drzava='".$_POST['podjetje_drzava']."'," : "";
                $update .= (isset($_POST['podjetje_davcna'])) ? " podjetje_davcna='".$_POST['podjetje_davcna']."'," : "";
                $update .= (isset($_POST['podjetje_no_ddv'])) ? " podjetje_no_ddv='".$_POST['podjetje_no_ddv']."'," : "";
                $update .= (isset($_POST['podjetje_eracun'])) ? " podjetje_eracun='".$_POST['podjetje_eracun']."'," : "";
    
                if($update != ''){

                    $update = substr($update, 0, -1);

                    $sqlNarocilo = sisplet_query("UPDATE user_access_narocilo SET ".$update.", cebelica_id_racun='0', cebelica_id_predracun='0' WHERE id='".$narocilo_id."'");
                    if (!$sqlNarocilo)
                        echo mysqli_error($GLOBALS['connect_db']);
                }
            }  

            // Na novo izrisemo tabelo z narocili
            $this->displayNarocilaTableAdmin();
        }

        // Urejamo narocilo
        if($_GET['a'] == 'payNarocilo') {

            if($narocilo_id > 0){

                // Ce imamo nastavljen payment_method na 1 pomeni da gre za klik na "placano" v tabeli (Goran) in potem popravimo narocilu, da ima vedno nacin placila preko trr
                $payment_method = (isset($_POST['payment_method'])) ? $_POST['payment_method'] : 0;
                if($payment_method == '1'){
                    $sqlNarociloNacin = sisplet_query("UPDATE user_access_narocilo SET payment_method='1' WHERE id='".$narocilo_id."'");
                }

                $this->payNarocilo($narocilo_id);
            }  

            // Na novo izrisemo tabelo z narocili
            $this->displayNarocilaTableAdmin();
        }

        // Placamo narocilo brez racuna
        if($_GET['a'] == 'payNarociloEracun') {

            if($narocilo_id > 0){

                // Ce imamo nastavljen payment_method na 1 pomeni da gre za klik na "placano" v tabeli (Goran) in potem popravimo narocilu, da ima vedno nacin placila preko trr
                $payment_method = (isset($_POST['payment_method'])) ? $_POST['payment_method'] : 0;
                if($payment_method == '1'){
                    $sqlNarociloNacin = sisplet_query("UPDATE user_access_narocilo SET payment_method='1' WHERE id='".$narocilo_id."'");
                }

                $this->payNarociloEracun($narocilo_id);
            }  

            // Na novo izrisemo tabelo z narocili
            $this->displayNarocilaTableAdmin();
        }

        // Brisemo narocilo
        if($_GET['a'] == 'deleteNarocilo') {

            if($narocilo_id > 0){
                $sqlNarocilo = sisplet_query("DELETE FROM user_access_narocilo WHERE id='".$narocilo_id."'");
            }
        }

        // Filter narocil
        if($_GET['a'] == 'filterNarocila') {

            $status = (isset($_POST['status'])) ? $_POST['status'] : '';
            $value = (isset($_POST['value'])) ? $_POST['value'] : '';

            if($status != '' && $value != ''){

                if($status == '0'){
                    $_SESSION['narocila_filter']['neplacana'] = $value;
                    $this->narocila_filter['neplacana'] = $value;
                }
                elseif($status == '1'){
                    $_SESSION['narocila_filter']['placana'] = $value;
                    $this->narocila_filter['placana'] = $value;
                }
                elseif($status == '2'){
                    $_SESSION['narocila_filter']['stornirana'] = $value;
                    $this->narocila_filter['stornirana'] = $value;
                }
            }

            // Na novo izrisemo tabelo z narocili
            $this->displayNarocilaTableAdmin();
        }

        // Vrnemo predracun
        if($_GET['a'] == 'getPredracun') {

            $narocilo_id = (isset($_POST['narocilo_id'])) ? $_POST['narocilo_id'] : '';

            if($narocilo_id != ''){
                $cebelica = new UserNarocilaCebelica($narocilo_id);
                $predracun = $cebelica->getNarociloPredracun();

                echo $predracun;
            }
        }

        // Vrnemo racun
        if($_GET['a'] == 'getRacun') {

            $narocilo_id = (isset($_POST['narocilo_id'])) ? $_POST['narocilo_id'] : '';

            if($narocilo_id != ''){
                $cebelica = new UserNarocilaCebelica($narocilo_id);
                $predracun = $cebelica->getNarociloRacun();

                echo $predracun;
            }
        }
    }
}