<?php

/**
 *
 *  Class ki skrbi za generiranje pdf-jev (racuni, predracuni) in komunikacijo s cebelico
 *
*/


global $site_root;


// Podatki izdajatelja
define("DDV", 0.22);	                                            // Stopnja DDV
define("IZDAJATELJ_DAVCNA", "49554042");							// davčna številka osebe, ki izda račun
define("IZDAJATELJ_IME", "Goran");									// ime osebe, ki izda račun

// folderji
//define("SITE_ROOT", "C:/xampp/htdocs/cebelca");
define("SITE_ROOT", $site_root."frontend/payments/cebelica");
define("MAPA_RACUNI", "/racuni/");
define("MAPA_PREDRACUNI", "/predracuni/");


class UserNarocilaCebelica{


    private $narocilo;


    public function __construct($narocilo_id=0){
        global $cebelica_api;

        // Api koda za komunikacijo s cebelico
        define("API_KODA", $cebelica_api);

        if($narocilo_id > 0){

            // Dobimo podatke narocila
            $sqlNarocilo = sisplet_query("SELECT un.*, u.name, u.surname, u.email, up.name AS package_name, up.description AS package_description, up.price AS package_price
                                            FROM user_access_narocilo un, users u, user_access_paket up
                                            WHERE un.id='".$narocilo_id."' AND un.usr_id=u.id AND un.package_id=up.id");
            if(mysqli_num_rows($sqlNarocilo) > 0){
                $this->narocilo = mysqli_fetch_array($sqlNarocilo);
            }
            else{
                die("Napaka pri komunikaciji s čebelico! Narocilo ne obstaja.");
            }
        }
        else {
			die("Napaka pri komunikaciji s čebelico! Manjka ID naročila.");
		}
    }


    // Zgeneriramo in vrnemo link do pdf-ja racuna za narocilo
    public function getNarociloRacun($eracun=false){
        global $global_user_id;

        // Preverimo, ce racun ze obstaja
        if($this->narocilo['cebelica_id_racun'] != '0' && file_exists(SITE_ROOT.MAPA_RACUNI.'1ka_racun_'.$this->narocilo['cebelica_id_racun'].'.pdf')){

            // Dobimo hashiran url do dokumenta
            $pdf_url = $this->getPdfUrl($type='racun', $this->narocilo['cebelica_id_racun']);
            
            return $pdf_url;
        }       

        // Ce predracun ne obstaja ga moramo najprej zgenerirati
        if($this->narocilo['cebelica_id_predracun'] == '0'){
            $this->getNarociloPredracun();
        }

        // Zgeneriramo pdf racun na podlagi predracuna
        $cebelica_id_new = $this->generatePdf($this->narocilo['cebelica_id_predracun'], $eracun);

        // Vstavimo id cebelice predracuna v bazo
        $sqlNarocilo = sisplet_query("UPDATE user_access_narocilo SET cebelica_id_racun='".$cebelica_id_new."' WHERE id='".$this->narocilo['id']."'");

        // Dobimo hashiran url do dokumenta
        $pdf_url = $this->getPdfUrl($type='racun', $cebelica_id_new);

        return $pdf_url;
    }

    // Zgeneriramo in vrnemo link do pdf-ja predracuna za narocilo
    public function getNarociloPredracun(){
        global $global_user_id;

        // Preverimo, ce predracun ze obstaja
        if($this->narocilo['cebelica_id_predracun'] != '0' && file_exists(SITE_ROOT.MAPA_PREDRACUNI.'1ka_predracun_'.$this->narocilo['cebelica_id_predracun'].'.pdf')){

            // Dobimo hashiran url do dokumenta
            $pdf_url = $this->getPdfUrl($type='predracun', $this->narocilo['cebelica_id_predracun']);
            
            return $pdf_url;
        }       

        // Zgeneriramo pdf predracun na podlagi podatkov narocila (cebelica)
        $cebelica_id_new = $this->generatePdf($cebelica_id=0);

        // Vstavimo id cebelice predracuna v bazo
        $sqlNarocilo = sisplet_query("UPDATE user_access_narocilo SET cebelica_id_predracun='".$cebelica_id_new."' WHERE id='".$this->narocilo['id']."'");

        // Popravimo se id v arrayu ce gre za generiranje predracuna pred generiranjem racuna
        $this->narocilo['cebelica_id_predracun'] = $cebelica_id_new;

        // Dobimo hashiran url do dokumenta
        $pdf_url = $this->getPdfUrl($type='predracun', $cebelica_id_new);

        return $pdf_url;
    }


    // Poklicemo cebelico in zgeneriramo predracun oz. racun
    private function generatePdf($cebelica_id=0, $eracun=false){
        global $site_path;

        // Api za povezavo s cebelico
        require_once($site_path.'frontend/payments/cebelica/InvoiceFox/cebelcaApi.php');

        $UA = new UserNarocila();

        // Dobimo ceno
        $cena = $UA->getPrice($this->narocilo['package_name'], $this->narocilo['trajanje'], $this->narocilo['discount'], $this->narocilo['time']);

        // Dobimo jezik za predracun/racun
        $lang = $UA->getNarociloLanguage($this->narocilo['id']);    


        // Slovenki racun/predracun
        if($lang == 'si'){

            if($this->narocilo['trajanje'] == 1)
                $months_string = 'mesec';
            elseif($this->narocilo['trajanje'] == 2)
                $months_string = 'meseca';
            elseif($this->narocilo['trajanje'] == 3 || $this->narocilo['trajanje'] == 4)
                $months_string = 'mesece';
            else
                $months_string = 'mesecev';

            $ime_storitve = '1KA naročnina (paket '.strtoupper($this->narocilo['package_name']). ' - '.$this->narocilo['trajanje'].' '.$months_string.')';
        }
        // Angleski racun/predracun
        else{

            if($this->narocilo['trajanje'] == 1)
                $months_string = 'month';
            else
                $months_string = 'months';

            $ime_storitve = '1KA subscription (package '.strtoupper($this->narocilo['package_name']). ' - '.$this->narocilo['trajanje'].' '.$months_string.')';
        }
        

        // Zavezanec iz tujine ima racun/predracun brez ddv
        if($UA->isWithoutDDV($this->narocilo['id'])){
            $ddv = 0;
            $cena_za_placilo = $cena['final_without_tax'];
        }   
        else{
            $ddv = 1;
            $cena_za_placilo = $cena['final'];
        }
        
        // Kartica
        if($this->narocilo['payment_method'] == '3')
            $tip_placila = 3;
        // Paypal
        elseif($this->narocilo['payment_method'] == '2')
            $tip_placila = 5;
        // TRR
        else
            $tip_placila = 1;

        $podatki = array(
            'narocilo_id'	=> $this->narocilo['id'],	                                // id narocila
            'stranka'		=> $this->narocilo['ime'],	                                // ime kupca
            'email'		    => $this->narocilo['email'],				                // email kupca
            'datum'			=> date("j.n.Y"),				                            // datum izdaje računa
            
            'telefon'		=> $this->narocilo['phone'],
     
            'drzava'	    => $this->narocilo['podjetje_drzava'],

            'podjetjeime'	=> $this->narocilo['podjetje_ime'],
            'podjetjenaslov'=> $this->narocilo['podjetje_naslov'],
            'podjetjepostna'=> $this->narocilo['podjetje_postna'],
            'podjetjeposta' => $this->narocilo['podjetje_posta'],
            'podjetjedavcna'=> $this->narocilo['podjetje_davcna'],	// davčna številka kupca, če je podjetje

            'ime_storitve'	=> $ime_storitve,
            
            'cena'			=> $cena['final_without_tax'],	                    // cena brez DDV
            'za_placilo'	=> $cena_za_placilo,	                // znesek za plačilo
            ///'popust'		=> $cena['discount_percentage'],	    // procent s celo številko. 5 pomeni 5%
            'veljavnost'	=> "3",							        // veljavnost predračuna v dnevih

            'ddv'           => $ddv,             // Obracunan ddv (zavezanec iz tujine ga nima)

            'tip_placila'   => $tip_placila    // Tip placila - 1=nakazilo, 3=kartica, 5=paypal
        );

        

        // 0 generira predračun, številka naredi račun iz predračuna
        $cebelica_id_new = vnosRacunaCebelca($podatki, $debug=false, $cebelica_id, $lang, $eracun);			
        
        if($cebelica_id_new){
            return $cebelica_id_new;
        }
        else{
            return "Napaka pri vnosu dokumenta v cebelca.biz.";
        }
    }


    // Dobimo hash za url do pdf-ja
    private function getPdfUrl($type, $id){
        global $site_url;

        $params = array(
            'type'  => $type,       // "racun" ali "predracun"
            'id'    => $id          // ID pdf dokumenta
        );

        // Array s podatki zaheshiramo
        $hash = base64_encode(urlencode(serialize($params)));
        
        $url = $site_url.'/payment/'.$hash;

        return $url;
    }
}