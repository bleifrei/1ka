<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"33",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"is",							// si - slovenian, en - english
	"language"		=> 	"Icelandic",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Næsta síða",	//	Next page
	"srv_nextpage_uvod"					=>	"Næsta síða",	//  Next page
	"srv_prevpage"						=>	"Fyrri síða",	//	Previous page
	"srv_lastpage"						=>	"Síðasta síða",	//	Last page
	"srv_forma_send"					=>	"Senda",	//  Send
	"srv_konec"							=>	"Endir",	//	End
	"srv_remind_sum_hard"				=>	"Þú hefur farið yfir summu takmörk!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Þú hefur farið yfir SÚM takmörk. Viltu halda áfram?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Þú hefur farið yfir fjölda mörk!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Þú hefur farið yfir fjölda takmörk. Viltu halda áfram?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Vinsamlegast svarið lögboðin spurningum!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Þú hefur ekki svarað öllum skylt spurningum. Viltu halda áfram?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Kóðann sem þú slóst inn er ekki það sama og í myndinni!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Kóðann sem þú slóst inn er ekki það sama og í myndinni! Viltu halda áfram?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Laus flokkar",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Raðast flokkar",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Viðvörun: númer þegar til!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Viðvörun: fjöldi er of stór!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Vinsamlegast taktu smástund og ljúka þessari könnun með því að smella á næstu síðu.",
	"srv_end"							=>	"Þú hefur lokið við þessa könnun. Þakka þér.",
	"srv_survey_non_active"				=>	"Könnun er lokað.",
	"srv_survey_non_active_notStarted"	=>	"Könnun er ekki virkur Könnun byrjar á: ",
	"srv_survey_non_active_expired"		=>	"Könnun er ekki virkur könnun rann út á: ",
	"srv_survey_non_active_voteLimit"	=>	"könnun náði hámarki svar telja.",
	"srv_previewalert"					=>	"Þú ert nú forsýning könnun inn svör verða ekki vistaðar!",
	"srv_recognized"					=>	"Þú ert að svara þessari könnun eins og",
	"srv_add_field"						=>	"Bæta við nýju svæði",
	"glasovanja_spol_izbira"			=>	"Velja kynlíf",
	"glasovanja_spol_moski"				=>	"Karlkyns",
	"glasovanja_spol_zenska"			=>	"Kvenkyns",
	"glasovanja_spol_zenske"			=>	"Kvenkyns",
	"srv_potrdi"						=>	"Staðfesta",
	"results"							=>	"Niðurstöður",
	"glasovanja_count"					=>	"atkvæði telja",
	"glasovanja_time"					=>	"Kosningin er opin frá",
	"glasovanja_time_end"				=>	"í",
	"hour_all"							=>	"Allt",
	"srv_basecode"						=>	"Setja lykilorð",
	"srv_back_edit"						=>	"Til baka til að breyta",
	"srv_nextins"						=>	"Næsta setja",
	"srv_insend"						=>	"Ljúka",
	"srv_alert_msg"						=>	"hefur lokið við könnun",
	"srv_alert_subject"					=>	'Lokið könnun',
	"srv_question_respondent_comment"	=>	"comment þín á spurningunni",
	"srv_dropdown_select"						=>	'Veldu',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>