<?php
/*
 * Created on 5.2.2010
 *
 * primerja oba language fajla 1.php in 2.php in izpiše ključe ki manjkajo v posameznem 
 */
 include_once('../function.php');

 

 $_tmp_slo = array();
 $_tmp_eng = array();
 
 include_once($site_path.'lang/1.php');
 $_tmp_slo = $lang;
 include_once($site_path.'lang/2.php');
 $_tmp_eng = $lang;
 
	// $_dif = array_diff_key($_tmp_slo, $_tmp_eng);
 
 $_slo_not_in_eng = array();
 $_eng_not_in_slo = array();
 
 foreach($_tmp_slo as $key => $value) {
	if (!isset($_tmp_eng[$key])) {
		$_eng_not_in_slo[] = $key;
	}
 }

 foreach($_tmp_eng as $key => $value) {
	if (!isset($_tmp_slo[$key])) {
		$_slo_not_in_eng[] = $key;
	}
 }
 echo "<b>Kljuci kateri so v slovenscini in jih ni v anglescini:</b><br/>"; 
 foreach ($_eng_not_in_slo as $value) {
 	print_r($value."<br>");
 }
  
 echo "<hr/><br/><b>Kljuci kateri so v anglescini in jih ni v slovenscini:</b><br/>"; 
 foreach ($_slo_not_in_eng as $value) {
 	print_r($value."<br>");
 }
?>
