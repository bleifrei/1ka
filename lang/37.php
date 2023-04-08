<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"37",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"no",							// si - slovenian, en - english
	"language"		=> 	"Norwegian",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Neste side",	//	Next page
	"srv_nextpage_uvod"					=>	"Neste side",	//  Next page
	"srv_prevpage"						=>	"Forrige side",	//	Previous page
	"srv_lastpage"						=>	"Siste side",	//	Last page
	"srv_forma_send"					=>	"Send",	//  Send
	"srv_konec"							=>	"Slutt",	//	End
	"srv_remind_sum_hard"				=>	"Du har overskredet summen grensen!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Du har overskredet summen grensen. Vil du fortsette?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Du har overskredet antallet grensen!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Du har overskredet antallet grensen. Vil du fortsette?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Vennligst svar på alle obligatoriske spørsmål!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Du har ikke besvart alle obligatoriske spørsmål. Vil du fortsette?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Koden du skrev inn er ikke det samme som på bildet!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Koden du skrev inn er ikke det samme som på bildet! Vil du fortsette?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Tilgjengelige kategorier",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Rangerte kategoriene",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Alert: antall allerede eksisterer!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Alert: tallet er for stort!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Vennligst ta noen få minutter og fullføre denne undersøkelsen ved å klikke på Neste side.",
	"srv_end"							=>	"Du er ferdig med denne undersøkelsen. Takk.",
	"srv_survey_non_active"				=>	"Survey er stengt.",
	"srv_survey_non_active_notStarted"	=>	"Survey er ikke aktiv Survey starter på: ",
	"srv_survey_non_active_expired"		=>	"Survey er ikke aktiv Survey utløp: ",
	"srv_survey_non_active_voteLimit"	=>	"Survey nådd en maksimal respons teller.",
	"srv_previewalert"					=>	"Du er for øyeblikket forhåndsvisning undersøkelsen! svar ikke vil bli frelst!",
	"srv_recognized"					=>	"Du svarer denne undersøkelsen som",
	"srv_add_field"						=>	"Legg til nytt felt",
	"glasovanja_spol_izbira"			=>	"Velge kjønn",
	"glasovanja_spol_moski"				=>	"Mann",
	"glasovanja_spol_zenska"			=>	"Kvinne",
	"glasovanja_spol_zenske"			=>	"Kvinne",
	"srv_potrdi"						=>	"Bekreft",
	"results"							=>	"Resultater",
	"glasovanja_count"					=>	"Stem teller",
	"glasovanja_time"					=>	"Stemmegivningen er åpen fra",
	"glasovanja_time_end"				=>	"til",
	"hour_all"							=>	"Alle",
	"srv_basecode"						=>	"Sett passord",
	"srv_back_edit"						=>	"Tilbake til redigering",
	"srv_nextins"						=>	"Neste innlegg",
	"srv_insend"						=>	"Ferdig",
	"srv_alert_msg"						=>	"Har gjennomført undersøkelsen",
	"srv_alert_subject"					=>	"Ferdig undersøkelse",
	"srv_question_respondent_comment"	=>	"Din kommentar på spørsmålet",
	"srv_dropdown_select"						=>	'Velg',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>