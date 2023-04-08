<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"23",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"al",							// si - slovenian, en - english
	"language"		=> 	"Albanian",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Faqja tjetër",	//	Next page
	"srv_nextpage_uvod"					=>	"Faqja tjetër",	//  Next page
	"srv_prevpage"						=>	"Faqja e mëparshme",	//	Previous page
	"srv_lastpage"						=>	"Faqja e fundit",	//	Last page
	"srv_forma_send"					=>	"Dërgoj",	//  Send
	"srv_konec"							=>	"Fund",	//	End
	"srv_remind_sum_hard"				=>	"Ju kanë tejkaluar limitin hollash!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Ju kanë tejkaluar limitin hollash. A ju doni të vazhdoni?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Ju kanë tejkaluar limitin e numër!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Ju kanë tejkaluar limitin e numër. A ju doni të vazhdoni?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Ju lutem përgjigjuni të gjitha pyetjeve të detyrueshme!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Ju nuk janë përgjigjur të gjitha pyetjeve të detyrueshme. A ju doni të vazhdoni?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Kodi keni hyrë nuk është njëjtë si në foto!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Kodi keni hyrë nuk është njëjtë si në foto! A doni të vazhdoni?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Kategoritë në dispozicion",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Kategoritë renditet",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Alarm: Numri tashmë ekziston!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Alarm: numri është tepër i madh!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Ju lutem, ndalo pak çaste dhe plotësoni këtë anketë duke klikuar në faqen tjetër.",
	"srv_end"							=>	"Ju keni përfunduar me këtë studim. Faleminderit.",
	"srv_survey_non_active"				=>	"Studimi është e mbyllur.",
	"srv_survey_non_active_notStarted"	=>	"Studimi nuk është aktiv Anketa fillon me: ",
	"srv_survey_non_active_expired"		=>	"Studimi nuk është aktiv Sondazhi ka skaduar në: ",
	"srv_survey_non_active_voteLimit"	=>	"Arriti Anketa një akuzë maksimale përgjigje." ,
	"srv_previewalert"					=>	"Jeni duke e parapamje sondazh! Përgjigjet nuk do të ruhen!",
	"srv_recognized"					=>	"Ju jeni duke u përgjigjur në këtë studim si",
	"srv_add_field"						=>	"Shto fushë të re",
	"glasovanja_spol_izbira"			=>	"Zgjidhni seksi",
	"glasovanja_spol_moski"				=>	"Mashkull",
	"glasovanja_spol_zenska"			=>	"Femër",
	"glasovanja_spol_zenske"			=>	"Femër",
	"srv_potrdi"						=>	"Konfirmoj",
	"results"							=>	"Rezultatet",
	"glasovanja_count"					=>	"Numërimi i votave",
	"glasovanja_time"					=>	"Votimi është i hapur nga",
	"glasovanja_time_end"				=>	"Për",
	"hour_all"							=>	"Të gjithë",
	"srv_basecode"						=>	"Fut fjalëkalimin tuaj",
	"srv_back_edit"						=>	"Kthehu tek redaktimi",
	"srv_nextins"						=>	"Fut tjetër",
	"srv_insend"						=>	"Fund",
	"srv_alert_msg"						=>	"Ka përfunduar studimi",
	"srv_alert_subject"					=>	"Mbaroi sondazh",
	"srv_question_respondent_comment"	=>	"Komenti juaj në lidhje me çështjen",
	"srv_dropdown_select"						=>	'Zgjedh',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>