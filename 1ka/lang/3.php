<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array ( 
	"useful_translation"	=>		"0",
	"id"			=>		"3",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"sr-c",							// si - slovenian, en - english
	"language"		=> 	"Serbian - Cyrillic script",
	
	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	"srv_survey_non_active"			=>	"Анкета је затворена",
	"srv_survey_non_active_notStarted"	=>	"Анкета није активна. Анкета почиње: ",
	"srv_survey_non_active_expired"		=>	"Анкета није активна. Анкета је истеклa: ",
	"srv_survey_non_active_voteLimit"	=>	"Анкета је достигла максимални број одговора.",
	"srv_previewalert"			=>	"тренутно гледaтe Преглед анкетe! Одговори неће бити сачувани!",
	"srv_recognized"			=>	"На анкету одговарате као:",
	"srv_ranking_avaliable_categories"	=>	"Доступнe категоријe",
	"srv_ranking_ranked_categories"		=>	"Рангиранe категорије",
	"srv_add_field"				=>	"Додај ново поље",
	"glasovanja_spol_izbira"		=>	"Изаберите пол",
	"glasovanja_spol_moski"			=>	"Мушки",
	"glasovanja_spol_zenska"		=>	"Женски",
	"srv_remind_sum_hard"			=>	"Прекорачили сте ограничење износа!",
	"srv_remind_sum_soft"			=>	"Прекорачили сте ограничење износа. Да ли желите да наставите?",
	"srv_remind_num_hard"			=>	"Прекорачили сте ограничење броја!",
	"srv_remind_num_soft"			=>	"Прекорачили сте ограничење броја. Да ли желите да наставите?",
	"srv_remind_hard"			=>	"Молимо Вас, одговорите на сва обавезнa питања!",
	"srv_remind_soft"			=>	"Нисте одговорили на сва обавезна питања. Да ли желите да наставите?",
	"srv_potrdi"				=>	"Потврди",
	"srv_lastpage"				=>	"Последња страна",
	"srv_nextpage"				=>	"Следећа страна",
	"srv_nextpage_uvod"				=>	"Следећа страна",
	"srv_prevpage"				=>	"Претходна страна",
	"srv_konec"							=>	"Kpaj",
	"results"				=>	"Резултати",
	"glasovanja_count"			=>	"Бројање гласова",
	"glasovanja_time"			=>	"Гласање је отворено од",
	"glasovanja_time_end"			=>	"до",
	"hour_all"				=>	"Сви",
	"glasovanja_spol_zenske"		=>	"Женски",
	"srv_intro"				=>	"Молимо вас да одвојите неколико тренутака и изпуните ову анкету кликом на следећа страна",
	"srv_basecode"				=>	"откуцајте лозинку",
	"srv_end"				=>	"Завршили сте са овом анкетом. Хвала.",
	"srv_back_edit"				=>	"Назад на eдитовање",
	"srv_nextins"				=>	"Следећи уметак",
	"srv_insend"				=>	"Kрај",
	"srv_back_edit"				=>	"Назад на eдитовање",
	"srv_alert_msg"				=>	"je завршиo Анкету.",
	"srv_alert_subject"			=>	"Завршенa Анкета",
	"srv_remind_captcha_hard"	=> 	"Шифра коју сте унели није исти као на слици!",
	"srv_remind_captcha_soft"	=> 	"Шифра коју сте унели није исти као на слици! Да ли желите да наставите?",
	"srv_alert_number_exists"	=>	"Упозорење: број већ постоји!",
	"srv_alert_number_toobig"	=>	"Упозорење: број је превелик!",
	"srv_forma_send"			=>	"послати",
	"srv_dropdown_select"						=>	'Oдабрати',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>