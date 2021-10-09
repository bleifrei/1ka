<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"35",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"mk",							// si - slovenian, en - english
	"language"		=> 	"Macedonian",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Следна страница",	//	Next page 
	"srv_nextpage_uvod"					=>	"Следна страница",	//  Next page
	"srv_prevpage"						=>	"Претходна страница",	//	Previous page
	"srv_lastpage"						=>	"Последна страница",	//	Last page
	"srv_forma_send"					=>	"Испрати",	//  Send
	"srv_konec"							=>	"Крај",	//	End
	"srv_remind_sum_hard"				=>	"Го имате надминато максималниот износ!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Го имате надминато максималниот износ! Дали сакате да продолжите?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Го имате надминато максималниот број!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Го имате надминато максималниот број! Дали сакате да продолжите?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Ве молиме одговорете на сите задолжителни прашања!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Не одговоривте на сите задолжителни прашања. Дали сакате да продолжите?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Кодот што го внесовте не соодветствува со тој сликата!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Кодот што го внесовте не соодветствува со тој сликата! Дали сакате да продолжите?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Достапни категории",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Рангирани категории",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Внимание: бројот веќе постои!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Внимание: бројот е преголем!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Ве молам да потрае неколку моменти и се заврши оваа анкета со кликнување на следната страница.",
	"srv_end"							=>	"ќе завршите со оваа анкета. Ви благодарам.",
	"srv_survey_non_active"				=>	"Истражувањето е затворена.",
	"srv_survey_non_active_notStarted"	=>	"Истражувањето не е активен Анкета почнува на: ",
	"srv_survey_non_active_expired"		=>	"Истражувањето не е активен Анкета истече на: ",
	"srv_survey_non_active_voteLimit"	=>	"достигна Анкета максимум одговор брои.",
	"srv_previewalert"					=>	"Вие сте моментално преглед на анкетата! Одговори не ќе биде спасена!",
	"srv_recognized"					=>	"Вие сте одговарање на ова истражување како",
	"srv_add_field"						=>	"Додај нов поле",
	"glasovanja_spol_izbira"			=>	"Избери секс",
	"glasovanja_spol_moski"				=>	"машки",
	"glasovanja_spol_zenska"			=>	"Женски",
	"glasovanja_spol_zenske"			=>	"Женски",
	"srv_potrdi"						=>	"Потврди",
	"results"							=>	"Резултати",
	"glasovanja_count"					=>	"пребројувањето на гласовите",
	"glasovanja_time"					=>	"Гласањето е отворен од",
	"glasovanja_time_end"				=>	"за да",
	"hour_all"							=>	"сите",
	"srv_basecode"						=>	"Внесете го Вашиот лозинка",
	"srv_back_edit"						=>	"Назад кон уредување",
	"srv_nextins"						=>	"Напред вметнете",
	"srv_insend"						=>	"Крај",
	"srv_alert_msg"						=>	"има завршено истражувањето",
	"srv_alert_subject"					=>	"готови анкета",
	"srv_question_respondent_comment"	=>	"Твојот коментар на прашањето",
	"srv_dropdown_select"						=>	'Изберете',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>