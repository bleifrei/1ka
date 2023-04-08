<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"7",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"lt",							// si - slovenian, en - english
	"language"		=> 	"Lithuanian",
	
	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	"srv_survey_non_active"				=>	"Apklausa baigta.",
	"srv_survey_non_active_notStarted"	=>	"Apklausų nėra aktyvi. Apklausa prasideda: ",
	"srv_survey_non_active_expired"		=>	"Apklausų nėra aktyvi. Apklausa baigėsi: ",
	"srv_survey_non_active_voteLimit"	=>	"Apklausa pasiekė didžiausią atsaką tikėtis.",
	"srv_previewalert"					=>	"Šiuo metu Jūs peržiūrit apklausą! Atsakymai nebus išsaugotas!",
	"srv_recognized"					=>	"Esate Atsakant į šį tyrimą, kaip:",
	"srv_ranking_avaliable_categories"	=>	"Galimos kategorijos",
	"srv_ranking_ranked_categories"		=>	"Reitingo kategorijos",
	"srv_add_field"						=>	"Pridėti naują lauką",
	"glasovanja_spol_izbira"			=>	"Pasirinkite lytį",
	"glasovanja_spol_moski"				=>	"Vyriškoji",
	"glasovanja_spol_zenska"			=>	"Moteriškoji",
	"srv_remind_sum_hard"				=>	"Jūs viršijote leistiną sumą, riba!",
	"srv_remind_sum_soft"				=>	"Jūs viršijote leistiną sumą, riba. Ar norite tęsti?",
	"srv_remind_num_hard"				=>	"Jūs viršijote skaičiaus!",
	"srv_remind_num_soft"				=>	"Jūs viršijote skaičiaus. Ar norite tęsti?",
	"srv_remind_hard"					=>	"Prašome atsakyti į visus privalomus klausimus!",
	"srv_remind_soft"					=>	"Jūs neturite atsakė į visus privalomus klausimus. Ar norite tęsti?",
	"srv_potrdi"						=>	"Patvirtinti",
	"srv_lastpage"						=>	"Paskutinis puslapis",
	"srv_nextpage"						=>	"Kitas puslapis",
	"srv_nextpage_uvod"						=>	"Kitas puslapis",
	"srv_prevpage"						=>	"Ankstesnis puslapis",
	"srv_konec"							=>	"End",
	"results"							=>	"Rezultatai",
	"glasovanja_count"					=>	"Balsų skaičius",
	"glasovanja_time"					=>	"Balsavimas yra atviras nuo iki",
	"glasovanja_time_end"				=>	"iki",
	"hour_all"							=>	"Visi",
	"glasovanja_spol_zenske"			=>	"Moteriškoji",
	"srv_intro"							=>	"Skirkite keletą akimirkų ir visiškas šio tyrimo įstaigai paspaudę ant Kitas puslapis",
	"srv_basecode"						=>	"Įdėkite savo slaptažodį.",
	"srv_end"							=>	"Jūs baigėte šią apklausą. Ačiū.",
	"srv_back_edit"						=>	"Atgal redaguoti",
	"srv_nextins"						=>	"Kitas įterpti",
	"srv_insend"						=>	"Apdaila",
	"srv_back_edit"						=>	"Atgal redaguoti",
	"srv_alert_msg"						=>	"turi sukomplektuoti apklausa.",
	"srv_alert_subject"					=>	"Baigta Apklausa",
	"srv_remind_captcha_hard"			=> 	"Kodas, kurį įvedėte, yra ne tas pats, kaip parodyta paveikslėlyje!",
	"srv_remind_captcha_soft"			=> 	"Kodas, kurį įvedėte, yra ne tas pats, kaip parodyta paveikslėlyje!Ar norite tęsti?",
	"srv_alert_number_exists"			=>	"Perspėjimas: jau egzistuoja!",
	"srv_alert_number_toobig"			=>	"Perspėjimas: skaičius yra per didelis!",
	"srv_forma_send"					=>	"Siųsti",
	"srv_konec"							=>	"Pabaiga",
	"srv_dropdown_select"						=>	'Pasirinkti',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>