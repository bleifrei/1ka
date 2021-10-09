<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"28",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"nl",							// si - slovenian, en - english
	"language"		=> 	"Dutch",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Volgende pagina",	//	Next page
	"srv_nextpage_uvod"					=>	"Volgende pagina",	//  Next page
	"srv_prevpage"						=>	"Vorige pagina",	//	Previous page
	"srv_lastpage"						=>	"Laatste pagina",	//	Last page
	"srv_forma_send"					=>	"Sturen",	//  Send
	"srv_konec"							=>	"Einde",	//	End
	"srv_remind_sum_hard"				=>	"U heeft het bedrag limiet!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"U heeft het bedrag limiet. Wil je doorgaan?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"U heeft het nummer limiet!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"U heeft het nummer limiet. Wil je doorgaan?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Wilt u alle verplichte vragen!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Je hebt niet beantwoord alle verplichte vragen. Wil je doorgaan?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"De code die u heeft ingevoerd is niet hetzelfde als in de afbeelding!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"De code die u heeft ingevoerd is niet hetzelfde als in de afbeelding! Wil je doorgaan?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Beschikbare categorieën",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Gerangschikt categorieën",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Waarschuwing: het nummer aanwezig zijn!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Waarschuwing: het aantal is te groot!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Neem even de tijd en deze enquête in te vullen door te klikken op de volgende pagina.",
	"srv_end"							=>	"U bent klaar met dit onderzoek. Dank je wel.",
	"srv_survey_non_active"				=>	"Opmeting is gesloten.",
	"srv_survey_non_active_notStarted"	=>	"Opmeting is niet actief. Opmeting start op: ",
	"srv_survey_non_active_expired" 	=>	"Opmeting is niet actief. Opmeting is verstreken op: ",
	"srv_survey_non_active_voteLimit"	=>	"Opmeting bereikte een maximale respons tellen.",
	"srv_previewalert"					=>	"U hebt op dit moment de voorvertoning van de enquête! antwoorden worden niet opgeslagen!",
	"srv_recognized"					=>	"U hebt het beantwoorden van deze enquête als",
	"srv_add_field"						=>	"Voeg een nieuw veld",
	"glasovanja_spol_izbira"			=>	"Kies geslacht",
	"glasovanja_spol_moski"				=>	"Man",
	"glasovanja_spol_zenska"			=>	"Vrouw",
	"glasovanja_spol_zenske"			=>	"Vrouw",
	"srv_potrdi"						=>	"Bevestig",
	"results"							=>	"resultaten",
	"glasovanja_count"					=>	"Vote count",
	"glasovanja_time"					=>	"Stemmen is geopend van",
	"glasovanja_time_end"				=>	"Naar",
	"hour_all"							=>	"Alles",
	"srv_basecode"						=>	"Voer uw wachtwoord",
	"srv_back_edit"						=>	"Terug naar het bewerken van",
	"srv_nextins"						=>	"Volgende insert",
	"srv_insend"						=>	"Afmaken",
	"srv_alert_msg"						=>	"Heeft de enquête ingevuld",
	"srv_alert_subject"					=>	"Klaar onderzoek",
	"srv_question_respondent_comment"	=>	"Reageer op de vraag",
	"srv_dropdown_select"						=>	'Kiezen',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>