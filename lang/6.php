<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"6",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"lv",							// si - slovenian, en - english
	"language"		=> 	"Latvian",
	
	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	"srv_survey_non_active"				=>	"Aptauja ir beigusies.",
	"srv_survey_non_active_notStarted"	=>	"Aptauja nav aktīva. Aptauja sākas ar: ",
	"srv_survey_non_active_expired"		=>	"Aptauja nav aktīva. Aptauja beidzās: ",
	"srv_survey_non_active_voteLimit"	=>	"Aptauja sasniedza maksimālo atbildes skaita.",
	"srv_previewalert"					=>	"Jūs šobrīd priekšskatīšanai aptauja! Atbildes netiks saglabāts!",
	"srv_recognized"					=>	"Jūs esat atbildētu uz šo apsekojumu:",
	"srv_ranking_avaliable_categories"	=>	"Pieejamās kategorijas",
	"srv_ranking_ranked_categories"		=>	"Sieviešu kategorijas",
	"srv_add_field"						=>	"Pievienot jaunu lauku",
	"glasovanja_spol_izbira"			=>	"Izvēlieties dzimums",
	"glasovanja_spol_moski"				=>	"Vīriešu",
	"glasovanja_spol_zenska"			=>	"Sieviešu",
	"srv_remind_sum_hard"				=>	"Jūs esat pārsniedzis summas limits!",
	"srv_remind_sum_soft"				=>	"Jūs esat pārsniedzis summas limits. Vai vēlaties turpināt?",
	"srv_remind_num_hard"				=>	"Jūs esat pārsniedzis skaita limits!",
	"srv_remind_num_soft"				=>	"Jūs esat pārsniedzis skaita limits!. Vai vēlaties turpināt?",
	"srv_remind_hard"					=>	"Lūdzu, atbildiet uz visiem obligātajiem jautājumiem!",
	"srv_remind_soft"					=>	"Jums ir neatbild visas obligātās jautājumiem. Vai vēlaties turpināt?",
	"srv_potrdi"						=>	"Apstiprināt",
	"srv_lastpage"						=>	"Pēdējā lapa",
	"srv_nextpage"						=>	"Nākamā lapa",
	"srv_nextpage_uvod"						=>	"Nākamā lapa",
	"srv_prevpage"						=>	"Iepriekšējā lapa",
	"srv_konec"							=>	"End",
	"results"							=>	"Rezultāti",
	"glasovanja_count"					=>	"Balsojums skaits",
	"glasovanja_time"					=>	"Balsošana ir atvērta no",
	"glasovanja_time_end"				=>	"līdz",
	"hour_all"							=>	"Visi",
	"glasovanja_spol_zenske"			=>	"Sieviešu",
	"srv_intro"							=>	"Lūdzu, veltiet dažas momentus un pabeigtu šo pētījumu, noklikšķinot uz Nākamā lapa",
	"srv_basecode"						=>	"Ievietojiet savu piekļuves kodu.",
	"srv_end"							=>	"Esat beidzis ar šo aptauju. Paldies.",
	"srv_back_edit"						=>	"Atpakaļ uz rediģēt",
	"srv_nextins"						=>	"Nākamais ievietot",
	"srv_insend"						=>	"Apdare",
	"srv_back_edit"						=>	"Atpakaļ uz rediģēt",
	"srv_alert_msg"						=>	"Ir pabeigt aptaujas.",
	"srv_alert_subject"					=>	"Pabeigts apsekojums",
	"srv_remind_captcha_hard"			=> 	"Kods Jūs ievadījāt, nav tas pats kā attēlā!",
	"srv_remind_captcha_soft"			=> 	"Kods Jūs ievadījāt, nav tas pats kā attēlā! Vai jūs vēlaties turpināt?",
	"srv_alert_number_exists"			=>	"Brīdinājums: skaitlis jau eksistē!",
	"srv_alert_number_toobig"			=>	"Brīdinājums: skaitlis ir pārāk liels!",
	"srv_forma_send"					=>	"Nosūtīt",
	"srv_konec"							=>	"Beigas",
	"srv_dropdown_select"						=>	'Atlasīt',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>