<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"11",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"ru",							// si - slovenian, en - english
	"language"		=> 	"Russian",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	"srv_survey_non_active"				=>	"Опрос закрыт.",
	"srv_survey_non_active_notStarted"		=>	"Опрос не активен. Опрос начинается с: ",
	"srv_survey_non_active_expired"			=>	"Опрос не активен. Время истекло:",
	"srv_survey_non_active_voteLimit"		=>	"Достигнуто максимальное количество ответов.",
	"srv_previewalert"				=>	"В данный момент Вы находитесь в предварительном просмотре! Ответы не будут сохранены!",
	"srv_recognized"				=>	"Вы отвечаете на вопросы этого опроса, как",
	"srv_ranking_avaliable_categories"		=>	"Доступные категории",
	"srv_ranking_ranked_categories"			=>	"Важные категории",
	"srv_add_field"					=>	"Добавить новое поле",
	"glasovanja_spol_izbira"			=>	"Выберите пол",
	"glasovanja_spol_moski"				=>	"Мужской",
	"glasovanja_spol_zenska"			=>	"Женский",
	"srv_remind_sum_hard"				=>	"Вы превысили лимит суммы!",
	"srv_remind_sum_soft"				=>	"Вы превысили лимит суммы. Вы хотите продолжить?",
	"srv_remind_num_hard"				=>	"Вы превысили возможное количество ответов!",
	"srv_remind_num_soft"				=>	"Вы превысили возможное количество ответов. Вы хотите продолжить?",
	"srv_remind_hard"				=>	"Пожалуйста, ответьте на все обязательные вопросы!",
	"srv_remind_soft"				=>	"Вы не ответили на все обязательные вопросы. Вы хотите продолжить?",
	"srv_potrdi"					=>	"Подтвердить",
	"srv_lastpage"					=>	"Последняя страница",
	"srv_nextpage"					=>	"Следующая страница",
	"srv_nextpage_uvod"					=>	"Следующая страница",
	"srv_prevpage"					=>	"Предыдущая страница",
	"srv_konec"							=>	"Конец",
	"results"					=>	"Результаты",
	"glasovanja_count"				=>	"Подсчет голосов",
	"glasovanja_time"				=>	"Голосование открыто с",
	"glasovanja_time_end"				=>	"К",
	"hour_all"					=>	"Все",
	"glasovanja_spol_zenske"			=>	"Женщина",
	"srv_intro"					=>	"Пожалуйста, уделите несколько минут и продолжите этот опрос, нажав на Следующую страницу",
	"srv_basecode"					=>	"Введите код",
	"srv_end"					=>	"Вы завершили этот опрос. Спасибо.",
	"srv_back_edit"					=>	"Вернуться к редактированию",
	"srv_nextins"					=>	"Следующее добавление",
	"srv_insend"					=>	"Конец",
	"srv_back_edit"					=>	"Вернуться к редактированию",
	"srv_alert_msg"					=>	"Вы завершили этот опрос",
	"srv_alert_subject"				=>	"Завершенный опрос",
	"srv_remind_captcha_hard"	=> 	"Введенный вами код не такой же, как на картинке!",
	"srv_remind_captcha_soft"	=> 	"Введенный вами код не такой же, как на картинке! Вы хотите продолжить?",
	"srv_alert_number_exists"	=>	"Предупреждение: номер уже существует!",
	"srv_alert_number_toobig"	=>	"Предупреждение: количество слишком велико!",
	"srv_forma_send"			=>	"отправлять",
	"srv_dropdown_select"						=>	'Выбирать',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>