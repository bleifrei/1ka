<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"17",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"ro",							// si - slovenian, en - english
	"language"		=> 	"Romanian",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Pagina următoare",	//	Next page
	"srv_nextpage_uvod"					=>	"Pagina următoare",	//  Next page
	"srv_prevpage"						=>	"Pagina anterioară",	//	Previous page
	"srv_lastpage"						=>	"Ultima pagină",	//	Last page
	"srv_forma_send"					=>	"Trimite",	//  Send
	"srv_konec"							=>	"Capăt",	//	End
	"srv_remind_sum_hard"				=>	"Aţi depăşit limita de suma!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Aţi depăşit limita de suma. Nu vă doriţi să continuaţi?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Aţi depăşit limita de număr!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Aţi depăşit limita de număr. Nu vă doriţi să continuaţi?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Vă rugăm să răspundeţi la toate întrebările obligatorii!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Nu v-aţi răspuns la toate întrebările obligatorii. Nu vă doriţi să continuaţi?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Codul introdus nu este aceeaşi ca în imagine!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Codul introdus nu este aceeaşi ca în imagine! Nu vă doriţi să continuaţi?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Categorii disponibile",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Categorii bine clasate",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Alertă: numărul există deja!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Alertă: numărul este prea mare!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Vă rugăm să luaţi câteva momente şi să completaţi acest sondaj făcând clic pe pagina următoare.",
	"srv_end"							=>	"Ai terminat cu acest sondaj Mulţumesc.",
	"srv_survey_non_active"				=>	"Ancheta este închis.",
	"srv_survey_non_active_notStarted"	=>	"Ancheta nu este activ Ancheta începe pe: ",
	"srv_survey_non_active_expired"		=>	"Ancheta nu este activ Ancheta a expirat pe: ",
	"srv_survey_non_active_voteLimit"	=>	"Ancheta a ajuns la un număr maxim de răspuns.",
	"srv_previewalert"					=>	"Vă prezentam în prezent sondaj! răspunsuri, nu va fi salvat!",
	"srv_recognized"					=>	"Vă răspunde la această anchetă drept",
	"srv_add_field"						=>	"Adauga domeniu nou",
	"glasovanja_spol_izbira"			=>	"Alege sexul",
	"glasovanja_spol_moski"				=>	"Masculin",
	"glasovanja_spol_zenska"			=>	"Femeie",
	"glasovanja_spol_zenske"			=>	"Femeie",
	"srv_potrdi"						=>	"Confirm",
	"results"							=>	"Rezultate",
	"glasovanja_count"					=>	"Numărarea voturilor",
	"glasovanja_time"					=>	"Votul este deschis de la",
	"glasovanja_time_end"				=>	"Pentru",
	"hour_all"							=>	"Toate",
	"srv_basecode"						=>	"Introduceţi parola",
	"srv_back_edit"						=>	"Înapoi la editare",
	"srv_nextins"						=>	"insert Next",
	"srv_insend"						=>	"Termina",
	"srv_alert_msg"						=>	"A finalizat ancheta",
	"srv_alert_subject"					=>	"Sondaj finite ",
	"srv_question_respondent_comment"	=>	"Comentariul tau pe problema",
	"srv_dropdown_select"						=>	'Selecta',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>