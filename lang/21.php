<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"21",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"ua",							// si - slovenian, en - english
	"language"		=> 	"Ukranian",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Наступна сторінка",	//	Next page
	"srv_nextpage_uvod"					=>	"Наступна сторінка",	//  Next page
	"srv_prevpage"						=>	"Попередня сторінка",	//	Previous page
	"srv_lastpage"						=>	"Остання сторінка",	//	Last page
	"srv_forma_send"					=>	"відправляти",	//  Send
	"srv_konec"							=>	"кінець",	//	End
	"srv_remind_sum_hard"				=>	"Ви перевищили суму ліміту!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Ви перевищили суму ліміту. Ви хочете продовжити?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Ви перевищили кількість обмежень!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Ви перевищили кількість обмежень. Ви хочете продовжити?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Будь ласка, дайте відповідь на всі обов'язкові запитання!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Ви не відповіли на всі обов'язкові запитання. Ви хочете продовжити?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Введений вами код не такий же, як на картинці!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Введений вами код не такий же, як на картинці! Ви хочете продовжити?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"доступні категорії",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Pейтинг категорії",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Попередження: число вже існує!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Попередження: число занадто велике!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Будь ласка, приділіть кілька хвилин і завершити це дослідження, натиснувши на наступній сторінці.",
	"srv_end"							=>	"Ви закінчили з цього огляду. Спасибо.",
	"srv_survey_non_active"				=>	"Огляд закрито.",
	"srv_survey_non_active_notStarted"	=>	"Огляд не є активним Огляд починається з: ",
	"srv_survey_non_active_expired"		=>	"Огляд не є активним Огляд закінчився: ",
	"srv_survey_non_active_voteLimit"	=>	"Огляд досягла максимальної кількості відповідей.",
	"srv_previewalert"					=>	"Ви в даний час перегляду опитуванні! Відповіді не буде збережено!",
	"srv_recognized"					=>	"Ви відповісти на це опитування як",
	"srv_add_field"						=>	"Додати нове поле",
	"glasovanja_spol_izbira"			=>	"Вибір підлоги",
	"glasovanja_spol_moski"				=>	"чоловічий",
	"glasovanja_spol_zenska"			=>	"Жіночий",
	"glasovanja_spol_zenske"			=>	"Жіночий",
	"srv_potrdi"						=>	"Підтвердження",
	"results"							=>	"Результати",
	"glasovanja_count"					=>	"підрахунку голосів",
	"glasovanja_time"					=>	"Голосування відкрито з",
	"glasovanja_time_end"				=>	"на",
	"hour_all"							=>	"Все",
	"srv_basecode"						=>	"Установка пароля",
	"srv_back_edit"						=>	"Повернутися до редагування",
	"srv_nextins"						=>	"Далі вставка",
	"srv_insend"						=>	"Готово",
	"srv_alert_msg"						=>	"завершила дослідження",
	"srv_alert_subject"					=>	"Готово дослідження",
	"srv_question_respondent_comment"	=>	"Ваш коментар з цього питання",
	"srv_dropdown_select"						=>	'Вибирати',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>