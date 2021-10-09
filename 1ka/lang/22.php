<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"22",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"we",							// si - slovenian, en - english
	"language"		=> 	"Welsh",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Tudalen nesaf",	//	Next page
	"srv_nextpage_uvod"					=>	"Tudalen nesaf",	//  Next page
	"srv_prevpage"						=>	"Tudalen flaenorol",	//	Previous page
	"srv_lastpage"						=>	"Tudalen olaf",	//	Last page
	"srv_forma_send"					=>	"Anfon",	//  Send
	"srv_konec"							=>	"Diwedd",	//	End
	"srv_remind_sum_hard"				=>	"Yr ydych wedi rhagori ar y terfyn swm!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Yr ydych wedi rhagori ar y terfyn swm. Ydych chi eisiau symud ymlaen?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Yr ydych wedi rhagori ar y terfyn rhif!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Yr ydych wedi rhagori ar y terfyn rhif. Ydych chi eisiau symud ymlaen?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Atebwch bob cwestiwn gorfodol!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Nid ydych wedi ateb yr holl gwestiynau gorfodol. Ydych chi eisiau symud ymlaen?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Nid yw'r cod roddoch yr un fath ag yn y llun!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Nid yw'r cod roddoch yr un fath ag yn y llun! Ydych chi am barhau?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Categorïau ar gael",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Categorïau eu Trefn",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Rhybudd: y nifer sydd eisoes yn bodoli!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Rhybudd: mae nifer yn rhy fawr!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Cymerwch ychydig o funudau a chwblhau'r arolwg hwn drwy glicio ar dudalen nesaf.",
	"srv_end"							=>	"Rydych wedi gorffen gyda'r arolwg hwn. Diolch yn fawr.",
	"srv_survey_non_active"				=>	"Arolwg ar gau.",
	"srv_survey_non_active_notStarted"	=>	"Nid Arolwg yn weithredol Arolwg yn dechrau ar: ",
	"srv_survey_non_active_expired"		=>	"Nid Arolwg yn weithredol Arolwg dod i ben ar: ",
	"srv_survey_non_active_voteLimit"	=>	"Arolwg cyrraedd cyfrif ymateb mwyaf.",
	"srv_previewalert"					=>	"Ni fyddwch ar hyn o bryd rhagolygu yr arolwg! atebion yn cael eu cadw!",
	"srv_recognized"					=>	"Rydych yn ateb yr arolwg hwn fel",
	"srv_add_field"						=>	"Ychwanegu maes newydd",
	"glasovanja_spol_izbira"			=>	"Dewiswch rhyw",
	"glasovanja_spol_moski"				=>	"Gwryw",
	"glasovanja_spol_zenska"			=>	"Benyw",
	"glasovanja_spol_zenske"			=>	"Benyw",
	"srv_potrdi"						=>	"Cadarnhau",
	"results"							=>	"Canlyniadau",
	"glasovanja_count" 					=>	"Cyfrif Pleidlais",
	"glasovanja_time"					=>	"Pleidleisio ar agor o",
	"glasovanja_time_end"				=>	"i",
	"hour_all"							=>	"Popeth",
	"srv_basecode"						=>	"Rhowch eich cyfrinair",
	"srv_back_edit"						=>	"Nôl i'r golygu",
	"srv_nextins"						=>	"Mewnosoder Nesaf",
	"srv_insend"						=>	"Gorffen",
	"srv_alert_msg"						=>	"Wedi cwblhau'r arolwg",
	"srv_alert_subject"					=>	"Arolwg gorffenedig",
	"srv_question_respondent_comment"	=>	"Mae eich sylwadau ar y cwestiwn",
	"srv_dropdown_select"						=>	'Dewiswch',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>