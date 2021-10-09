<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"32",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"hu",							// si - slovenian, en - english
	"language"		=> 	"Hungarian",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Következő oldal",	//	Next page
	"srv_nextpage_uvod"					=>	"Következő oldal",	//  Next page
	"srv_prevpage"						=>	"Előző oldal",	//	Previous page
	"srv_lastpage"						=>	"Utolsó oldal",	//	Last page
	"srv_forma_send"					=>	"Küld",	//  Send
	"srv_konec"							=>	"Vége",	//	End
	"srv_remind_sum_hard"				=>	"Elérte az összeg határ!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Elérte az összeg határt. Szeretné folytatni?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Elérte a szám korlát!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Elérte a szám határt. Szeretné folytatni?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Kérjük, minden kötelező válaszolni a kérdésekre!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Nem válaszolt minden kérdésre kötelező. Szeretné folytatni?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"A beírt kód nem egyezik a képen!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"A beírt kód nem egyezik a képen! Szeretné folytatni?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Elérhető kategóriák",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Rangú kategóriák",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Riasztás: a szám már létezik!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Riasztás: a szám túl nagy!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Kérjük, szánjon néhány percet, és töltse ki ezt a felmérést kattintva a következő oldalon.",
	"srv_end"							=>	"Ön befejezte a felmérésben. Köszönöm.",
	"srv_survey_non_active"				=>	"felmérés zárva.",
	"srv_survey_non_active_notStarted"	=>	"felmérés nem aktív. Felmérés kezdődik: ",
	"srv_survey_non_active_expired"		=>	"felmérés nem aktív. Felmérés lejárt: ",
	"srv_survey_non_active_voteLimit"	=>	"felmérés elérte a maximális hatás számít.",
	"srv_previewalert"					=>	"Ön jelenleg a felmérés sajtóbemutató! válaszok nem lesznek elmentve!",
	"srv_recognized"					=>	"Ön választ erre a felmérés",
	"srv_add_field"						=>	"Új mező hozzáadása",
	"glasovanja_spol_izbira"			=>	"Válassz szexre",
	"glasovanja_spol_moski"				=>	"férfi",
	"glasovanja_spol_zenska"			=>	"Nő",
	"glasovanja_spol_zenske"			=>	"Nő",
	"srv_potrdi"						=>	"Megerősítés",
	"results"							=>	"Eredmények",
	"glasovanja_count"					=>	"szavazat számít",
	"glasovanja_time"					=>	"szavazás van nyitva",
	"glasovanja_time_end"				=>	"hogy",
	"hour_all"							=>	"All",
	"srv_basecode"						=>	"Adja meg jelszavát",
	"srv_back_edit"						=>	"Vissza a szerkesztéshez",
	"srv_nextins"						=>	"Most helyezzen",
	"srv_insend"						=>	"Befejezés",
	"srv_alert_msg"						=>	"A felmérés befejezése",
	"srv_alert_subject"					=>	"Kész felmérés",
	"srv_question_respondent_comment"	=>	"Az Ön megjegyzése a kérdésre",
	"srv_dropdown_select"						=>	'Választ',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>