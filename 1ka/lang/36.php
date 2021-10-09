<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"36",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"mt",							// si - slovenian, en - english
	"language"		=> 	"Maltese",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Paġna li jmiss",	//	Next page
	"srv_nextpage_uvod"					=>	"Paġna li jmiss",	//  Next page
	"srv_prevpage"						=>	"Qabel Il-paġna",	//	Previous page
	"srv_lastpage"						=>	"L-aħħar paġna",	//	Last page
	"srv_forma_send"					=>	"Ibgħat",	//  Send
	"srv_konec"							=>	"Tmiem",	//	End
	"srv_remind_sum_hard"				=>	"Inti għandek qabeż il-limitu somma!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Inti għandek qabeż il-limitu somma. Tixtieq li tipproċedi?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Inti għandek qabeż il-limitu numru!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Inti għandek qabeż il-limitu numru. Tixtieq li tipproċedi?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Jekk jogħġbok wieġeb il-mistoqsijiet obbligatorji!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Inti ma wieġeb il-mistoqsijiet obbligatorji. Tixtieq li tipproċedi?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Il-kodiċi inti daħal ma jkunx l-istess bħal fl-istampa!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Il-kodiċi inti daħal ma jkunx l-istess bħal fl-istampa! Tixtieq li tkompli?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Kategoriji disponibbli",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Kategoriji kklassifikati",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Twissija: in-numru diġà jeżistu!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Twissija: in-numru huwa kbir wisq!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Jekk jogħġbok ħu ftit mumenti u jitlesta dan l-istħarriġ billi tikklikkja fuq il-paġna li jmiss.",
	"srv_end"							=>	"Inti lest ma dan l-istħarriġ. Grazzi.",
	"srv_survey_non_active"				=>	"Stħarriġ huwa magħluq.",
	"srv_survey_non_active_notStarted"	=>	"Stħarriġ mhux attiva Stħarriġ jibda: ",
	"srv_survey_non_active_expired"		=>	"Stħarriġ mhux attiva Stħarriġ skada fuq: ",
	"srv_survey_non_active_voteLimit"	=>	"Stħarriġ laħaq għadd massimu għal tweġiba.",
	"srv_previewalert"					=>	"Inti bħalissa qed previewing-istħarriġ! Tweġibiet mhux se jiġu ffrankati!",
	"srv_recognized"					=>	"Inti qed twieġeb dan l-istħarriġ bħala",
	"srv_add_field"						=>	"Żid qasam ġdid",
	"glasovanja_spol_izbira"			=>	"Agħżel sess",
	"glasovanja_spol_moski"				=>	"Maskili",
	"glasovanja_spol_zenska"			=>	"Mara",
	"glasovanja_spol_zenske"			=>	"Mara",
	"srv_potrdi"						=>	"Ikkonferma",
	"results"							=>	"Riżultati",
	"glasovanja_count"					=>	"Votazzjoni għadd",
	"glasovanja_time"					=>	"votazzjoni hija miftuħa minn",
	"glasovanja_time_end"				=>	"biex",
	"hour_all"							=>	"Kulħadd",
	"srv_basecode"						=>	"Daħħal il-password tiegħek",
	"srv_back_edit"						=>	"Lura għall-editjar",
	"srv_nextins"						=>	"daħħal jmiss",
	"srv_insend"						=>	"Finish",
	"srv_alert_msg"						=>	"temm l-istħarriġ",
	"srv_alert_subject"					=>	"stħarriġ rfinuti",
	"srv_question_respondent_comment"	=>	"kumment tiegħek dwar il-kwistjoni",
	"srv_dropdown_select"						=>	'Agħżel',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>