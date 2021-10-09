<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"25",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"by",							// si - slovenian, en - english
	"language"		=> 	"Belarusian",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Наступная старонка",	//	Next page
	"srv_nextpage_uvod"					=>	"Наступная старонка",	//  Next page
	"srv_prevpage"						=>	"Папярэдняя старонка",	//	Previous page
	"srv_lastpage"						=>	"Апошняя старонка",	//	Last page
	"srv_forma_send"					=>	"Aдпраўляць",	//  Send
	"srv_konec"							=>	"Канец",	//	End
	"srv_remind_sum_hard"				=>	"Вы перавысілі суму ліміту!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Вы перавысілі суму ліміту. Вы хочаце працягнуць?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Вы перавысілі колькасць мяжа!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Вы перавысілі колькасць абмежаванняў. Вы хочаце працягнуць?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Калі ласка, адкажыце на ўсе абавязковыя пытанні!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Вы не адказалі на ўсе абавязковыя пытанні. Вы хочаце працягнуць?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Yведзены вамі код не такі ж, як на малюначку!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Yведзены вамі код не такі ж, як на малюначку! Вы хочаце працягнуць?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"даступныя катэгорыі",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Pэйтынг катэгорыі",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Папярэджанне: колькасць ужо існуе!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Папярэджанне: колькасць занадта вялікае!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Калі ласка, надасце некалькі хвілін і завяршыць гэта даследаванне, націснуўшы на наступнай старонцы.",
	"srv_end"							=>	"Вы скончылі з гэтага агляду. Дзякуй.",
	"srv_survey_non_active"				=>	"Агляд зачынены.",
	"srv_survey_non_active_notStarted"	=>	"Агляд не з'яўляецца актыўным Агляд пачынаецца з: ",
	"srv_survey_non_active_expired"		=>	"Агляд не з'яўляецца актыўным Агляд скончыўся: ",
	"srv_survey_non_active_voteLimit"	=>	"Агляд дасягнула максімальнай колькасці адказаў.",
	"srv_previewalert"					=>	"Вы ў цяперашні час прагляду апытанні! Адказы не будуць захаваны!",
	"srv_recognized"					=>	"Вы адказаць на гэтае апытанне як",
	"srv_add_field"						=>	"Дадаць новае поле",
	"glasovanja_spol_izbira"			=>	"Выбар падлогі",
	"glasovanja_spol_moski"				=>	"мужчынскі",
	"glasovanja_spol_zenska"			=>	"Жаночы",
	"glasovanja_spol_zenske"			=>	"Жаночы",
	"srv_potrdi"						=>	"Пацвярджэнне",
	"results"							=>	"Вынікі",
	"glasovanja_count"					=>	"падліку галасоў",
	"glasovanja_time"					=>	"Галасаванне адкрыта з",
	"glasovanja_time_end"				=>	"на",
	"hour_all"							=>	"Усе",
	"srv_basecode"						=>	"Усталяванне пароля",
	"srv_back_edit" 					=>	"Вярнуцца да рэдагавання",
	"srv_nextins"						=>	"Далей ўстаўка",
	"srv_insend"						=>	"Гатова",
	"srv_alert_msg"						=>	"завяршыла даследавання",
	"srv_alert_subject"					=>	"Гатова даследаванне",
	"srv_question_respondent_comment"	=>	"Ваш каментар па гэтым пытанні",
	"srv_dropdown_select"						=>	'Выбіраць',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>