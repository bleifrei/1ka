<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"30",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"ee",							// si - slovenian, en - english
	"language"		=> 	"Estonian",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Järgmine leht",	//	Next page
	"srv_nextpage_uvod"					=>	"Järgmine leht",	//  Next page
	"srv_prevpage"						=>	"Eelmine",	//	Previous page
	"srv_lastpage"						=>	"Viimane lehekülg",	//	Last page
	"srv_forma_send"					=>	"Saatma",	//  Send
	"srv_konec"							=>	"Lõpp",	//	End
	"srv_remind_sum_hard"				=>	"Oled ületanud summa piiratud!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Oled ületanud summa piires. Kas soovite jätkata?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Oled ületanud arv piiratud!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Oled ületanud number piiri. Kas soovite jätkata?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Palun vastake kõikidele kohustuslikele küsimustele!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Sa ei ole vastanud kõigile kohustuslik küsimustele. Kas soovite jätkata?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Sisestatud kood ei ole sama mis pildil!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Sisestatud kood ei ole sama mis pildil! Kas soovite jätkata?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Saadaval kategooriad",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Järjestatud kategooriad",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Hoiatus: number juba olemas!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Hoiatus: number on liiga suur!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Palun mõtle hetk ja lõpetada see uuring klõpsates Järgmine lehekülg.",
	"srv_end"							=>	"Te olete lõpetanud selle uuringu. Aitäh.",
	"srv_survey_non_active"				=>	"Survey on suletud.",
	"srv_survey_non_active_notStarted"	=>	"Survey ei ole aktiivne. Survey algab: ",
	"srv_survey_non_active_expired"		=>	"Survey ei ole aktiivne. Survey lõppes: ",
	"srv_survey_non_active_voteLimit"	=>	"Survey saavutas maksimumi loota.",
	"srv_previewalert"					=>	"Sa oled praegu eelvaate Uuringu! Vastused ei salvestata!",
	"srv_recognized"					=>	"Sa vastamiseks uuringu",
	"srv_add_field"						=>	"Lisa uus väli",
	"glasovanja_spol_izbira"			=>	"Vali sugu",
	"glasovanja_spol_moski"				=>	"Mees",
	"glasovanja_spol_zenska"			=>	"Naine",
	"glasovanja_spol_zenske"			=>	"Naine",
	"srv_potrdi"						=>	"Kinnita",
	"results"							=>	"tulemused",
	"glasovanja_count"					=>	"Hindeid",
	"glasovanja_time"					=>	"Hääletamine on avatud",
	"glasovanja_time_end"				=>	"Kuni",
	"hour_all"							=>	"Kõik",
	"srv_basecode"						=>	"Sisestage oma parool",
	"srv_back_edit"						=>	"Tagasi toimetamine",
	"srv_nextins"						=>	"Järgmine lisada",
	"srv_insend"						=>	"Lõpp",
	"srv_alert_msg"						=>	"on valminud uuring",
	"srv_alert_subject"					=>	"Valmis uuring",
	"srv_question_respondent_comment"	=>	"Sinu kommentaar on küsimus",
	"srv_dropdown_select"						=>	'Valima',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>