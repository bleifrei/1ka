<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 15.04.2016
 *****************************************/
include_once('../../settings.php');
include_once('../../function.php');

if (!empty($_POST['koda'])) {
    $koda = strtolower($_POST['koda']);

    $sql = sisplet_query("SELECT url, anketa_id FROM srv_hierarhija_koda WHERE koda='" . $koda . "'");

    if ($sql->num_rows > 0) {
        $row = $sql->fetch_object();

        //kodiramo spremenljivke z base64_encode
        $url_encode_spremenljivke = urlencode(base64_encode($row->url));

        //celotni url do ankete
        header("Location: " . $site_url . "a/" . $row->anketa_id . "?enc=" . $url_encode_spremenljivke);
        die();
    }

    // V kolikor gre za superšifro potem preverimo še med superšiframi
    $sql = sisplet_query("SELECT koda, kode, anketa_id FROM srv_hierarhija_supersifra WHERE koda='" . $koda . "'");
    if ($sql->num_rows > 0) {
        $row = $sql->fetch_object();
        $kode = unserialize($row->kode);

        $koda_resevanje = sisplet_query("SELECT url FROM srv_hierarhija_koda WHERE koda='" . $kode[0] . "' AND anketa_id='" . $row->anketa_id . "'", "obj");

        //kodiramo spremenljivke z base64_encode
        $url_encode_spremenljivke = urlencode(base64_encode($koda_resevanje->url . '&supersifra=' . $koda.'&resujem=0'));

        //celotni url do ankete
        header("Location: " . $site_url . "a/" . $row->anketa_id . "?enc=" . $url_encode_spremenljivke);
        die();
    }

}

header("Location: " . $site_url);
die();
