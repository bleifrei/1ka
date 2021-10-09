<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"26",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"bg",							// si - slovenian, en - english
	"language"		=> 	"Bulgarian",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Следваща страница",	//	Next page
	"srv_nextpage_uvod"					=>	"Следваща страница",	//  Next page
	"srv_prevpage"						=>	"Предишна страница",	//	Previous page
	"srv_lastpage"						=>	"Последна страница",	//	Last page
	"srv_forma_send"					=>	"Изпратете",	//  Send
	"srv_konec"							=>	"край",	//	End
	"srv_remind_sum_hard"				=>	"Вие сте превишили сумата лимит!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Вие сте превишили сумата граница. Искате ли да продължите?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Вие сте превишили ограничението на броя!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Вие сте превишили ограничението на броя. Искате ли да продължите?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Моля, отговорете на всички задължителни въпроси!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Вие не сте отговорили на всички задължителни въпроси. Искате ли да продължите?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Кодът, който сте въвели не е същото като на снимката!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Кодът, който сте въвели не е същото като на снимката! Искате ли да продължите?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Налични категории",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"класираните категории",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Сигнал: брой вече съществуват!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Сигнал: брой е твърде голям!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Моля, отделете няколко минути и да попълните тази анкета, като кликнете на следващата страница.",
	"srv_end"							=>	"Вие сте готови с това проучване. Благодаря ви.",
	"srv_survey_non_active"				=>	"Изследването е затворена.",
	"srv_survey_non_active_notStarted" 	=>	"Проучване не е активен Проучването започва на: ",
	"srv_survey_non_active_expired"		=>	"Проучването не е активно проучване, е изтекъл на: ",
	"srv_survey_non_active_voteLimit"	=>	"Проучване достигна максималния брой отговор.",
	"srv_previewalert"					=>	"В момента се преглеждат проучването! отговори няма да бъдат спасени!",
	"srv_recognized"					=>	"отговора на този изследване, както е",
	"srv_add_field"						=>	"Добавяне на ново поле",
	"glasovanja_spol_izbira"			=>	"Избери секс",
	"glasovanja_spol_moski"				=>	"мъжки",
	"glasovanja_spol_zenska"			=>	"женски",
	"glasovanja_spol_zenske"			=>	"женски",
	"srv_potrdi"						=>	"Потвърди",
	"results"							=>	"резултати",
	"glasovanja_count"					=>	"преброяването на гласовете",
	"glasovanja_time"					=>	"гласуването е отворен",
	"glasovanja_time_end"				=>	"да",
	"hour_all"							=>	"всички",
	"srv_basecode"						=>	"Вмъкване на парола",
	"srv_back_edit"						=>	"Назад към редактиране",
	"srv_nextins"						=>	"Cледващата вложка",
	"srv_insend"						=>	"край",
	"srv_alert_msg"						=>	"E завършил изследването",
	"srv_alert_subject"					=>	"Готово изследване",
	"srv_question_respondent_comment"	=>	"Вашият коментар по въпроса",
	"srv_dropdown_select"						=>	'Изберете',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>