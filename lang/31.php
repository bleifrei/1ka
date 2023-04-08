<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"31",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"fi",							// si - slovenian, en - english
	"language"		=> 	"Finnish",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Seuraava sivu",	//	Next page
	"srv_nextpage_uvod"					=>	"Seuraava sivu",	//  Next page
	"srv_prevpage"						=>	"Edellinen sivu",	//	Previous page
	"srv_lastpage"						=>	"Viimeinen sivu",	//	Last page
	"srv_forma_send"					=>	"Lähettää",	//  Send
	"srv_konec"							=>	"Loppu",	//	End
	"srv_remind_sum_hard"				=>	"Olet ylittänyt summa raja!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Olet ylittänyt summa rajan. Haluatko jatkaa?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Olet ylittänyt määrän raja!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Olet ylittänyt määrän raja. Haluatko jatkaa?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Vastatkaa kaikki pakolliset kysymyksiin!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Et ole vastannut kaikkiin pakollisia kysymyksiin. Haluatko jatkaa?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Antamasi koodi ei ole sama kuin kuvassa!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Antamasi koodi ei ole sama kuin kuvassa! Haluatko jatkaa?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Käytettävissä olevat luokat",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Ranked luokat",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Hälytys: numero on jo olemassa!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Hälytys: numero on liian iso!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Ota hetki ja täytä kyselyn klikkaamalla Seuraava.",
	"srv_end"							=>	"Olet valmis Tästä tutkimuksesta. Kiitos.",
	"srv_survey_non_active"				=>	"Survey on suljettu.",
	"srv_survey_non_active_notStarted"	=>	"Tutkimus ei ole aktiivinen. Kysely alkaa: ",
	"srv_survey_non_active_expired"		=>	"Tutkimus ei ole aktiivinen. Kysely päättynyt: ",
	"srv_survey_non_active_voteLimit"	=>	"Tutkimus saavutti maksimivasteen määrä.",
	"srv_previewalert"					=>	"Olet nyt esikatselun kyselyyn! vastauksia ei tallenneta!",
	"srv_recognized"					=>	"Olet vastaamassa tähän kyselyyn kuin",
	"srv_add_field"						=>	"Lisää uusi kenttä",
	"glasovanja_spol_izbira"			=>	"Valitse sukupuoli",
	"glasovanja_spol_moski"				=>	"Mies",
	"glasovanja_spol_zenska"			=>	"Nainen",
	"glasovanja_spol_zenske"			=>	"Nainen",
	"srv_potrdi"						=>	"Vahvista",
	"results"							=>	"Tulokset",
	"glasovanja_count"					=>	"Vote count",
	"glasovanja_time"					=>	"Äänestys on auki",
	"glasovanja_time_end"				=>	"ja",
	"hour_all"							=>	"Kaikki",
	"srv_basecode"						=>	"Aseta salasana",
	"srv_back_edit"						=>	"Takaisin muokkaus",
	"srv_nextins"						=>	"Seuraava Lisää",
	"srv_insend"						=>	"Valmis",
	"srv_alert_msg"						=>	"on valmistunut tutkimus",
	"srv_alert_subject"					=>	"Valmis survey",
	"srv_question_respondent_comment"	=>	"Kommenttisi on kysymys",
	"srv_dropdown_select"						=>	'Valita',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>