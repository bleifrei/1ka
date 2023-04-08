<?php

# Skripta za downloadanje pdf-jev (ker ga izvedemo preko lepega linka "www.1ka.si/payment/hash")


include_once '../../function.php';
global $site_path;

if(isset($_GET['hash'])){

    // Decode hash
    $hash = $_GET['hash'];
    $params = unserialize(urldecode(base64_decode($hash)));
    
    // Vrnemo predracun
    if($params['type'] == 'predracun' || $params['type'] == 'racun'){

        $pdf_name = "1ka_".$params['type']."_".$params['id'].".pdf";

        if($params['type'] == 'predracun')
            $pdf_folder = $site_path."/frontend/payments/cebelica/predracuni/";
        else
            $pdf_folder = $site_path."/frontend/payments/cebelica/racuni/";

        $pdf_path = $pdf_folder . $pdf_name;

        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$pdf_name.'"');

        readfile($pdf_path);
    }
}


?>
