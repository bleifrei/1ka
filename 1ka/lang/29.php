<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"29",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"dk",							// si - slovenian, en - english
	"language"		=> 	"Danish",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Næste side",	//	Next page
	"srv_nextpage_uvod"					=>	"Næste side",	//  Next page
	"srv_prevpage"						=>	"Forrige side",	//	Previous page
	"srv_lastpage"						=>	"Sidste side",	//	Last page
	"srv_forma_send"					=>	"Sende",	//  Send
	"srv_konec"							=>	"Ende",	//	End
	"srv_remind_sum_hard"				=>	"Du har overskredet summen grænse!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Du har overskredet summen grænse. Vil du fortsætte?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Du har overskredet det antal grænse!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Du har overskredet det antal grænse. Vil du fortsætte?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Besvar venligst alle obligatoriske spørgsmål!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Du har ikke besvaret alle obligatoriske spørgsmål. Vil du fortsætte?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Den kode, du indtastede, er ikke det samme som på billedet!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Den kode, du indtastede, er ikke det samme som på billedet! Vil du fortsætte?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Tilgængelige kategorier",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Rangerede kategorier",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Advarsel: nummeret allerede findes!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Advarsel: antallet er for stort!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Vær venlig at tage et par øjeblikke og afslutte denne undersøgelse ved at klikke på næste side.",
	"srv_end"							=>	"Du er færdig med denne undersøgelse. Tak.",
	"srv_survey_non_active"				=>	"Undersøgelsen er lukket.",
	"srv_survey_non_active_notStarted"	=>	"Undersøgelsen er ikke aktiv. Undersøgelsen begynder på: ",
	"srv_survey_non_active_expired"		=>	"Undersøgelsen er ikke aktiv. Undersøgelsen udløb den: ",
	"srv_survey_non_active_voteLimit"	=>	"Kortlægning nåede et maksimum respons tæller.",
	"srv_previewalert"					=>	"Du er i øjeblikket gennemse den undersøgelse! svar vil ikke blive gemt!",
	"srv_recognized"					=>	"Du besvarer denne undersøgelse som",
	"srv_add_field"						=>	"Tilføj nyt felt",
	"glasovanja_spol_izbira"			=>	"Vælg køn",
	"glasovanja_spol_moski"				=>	"Mand",
	"glasovanja_spol_zenska"			=>	"Kvinde",
	"glasovanja_spol_zenske" 			=>	"Kvinde",
	"srv_potrdi"						=>	"Bekræft",
	"results"							=>	"Resultater",
	"glasovanja_count"					=>	"Afstemning count",
	"glasovanja_time"					=>	"Afstemningen er åben fra",
	"glasovanja_time_end"				=>	"Til",
	"hour_all"							=>	"Alle",
	"srv_basecode"						=>	"Indsæt dit kodeord",
	"srv_back_edit"						=>	"Tilbage til redigering",
	"srv_nextins"						=>	"Næste indsats",
	"srv_insend"						=>	"Slut",
	"srv_alert_msg"						=>	"Har afsluttet undersøgelsen",
	"srv_alert_subject"					=>	'Færdig undersøgelse',
	"srv_question_respondent_comment"	=>	"Din kommentar til spørgsmålet",
	"srv_dropdown_select"						=>	'Vælg',
	
	// EVOLI //
	'srv_evoli_form_company_name'	=>	"Firma",
	'srv_evoli_form_td'				=>	"Teams/afdelinger",
	'srv_evoli_form_dep_add'		=>	"Add department",
	'srv_evoli_form_dep_remove'		=>	"Remove department",
	'srv_evoli_form_test_s'			=>	"Startdato",
	'srv_evoli_form_test_e'			=>	"Slutdato",
	'srv_evoli_form_email_list'		=>	"Medarbejdernes e-mail adresser<br />(adskil hver adresse med et linjeskift)",
	'srv_evoli_form_send_inv'		=>	"Send invitationer",
	'srv_evoli_form_missing_email'	=>	"At least one email is required",
	'srv_evoli_form_missing_company' =>	"Company name is required",
	'srv_evoli_form_missing_user'	=>	"User email is not defined",
	'srv_evoli_form_invalid_uemail'	=>	"User email is not valid",
	'srv_evoli_form_missing_survey'	=>	"Survey ID is not defined",
	'srv_evoli_form_missing_start'	=>	"Start date is required",
	'srv_evoli_form_missing_end'	=>	"Finish date is required",
	'srv_evoli_form_email_count'	=>	"Maximum number of emails exceeded (39)",
	'srv_evoli_form_invalid_email'	=>	"Invalid email",
	'srv_evoli_form_author_email'	=>	"Author email",
	'srv_evoli_form_company'		=>	"Firma",
	'srv_evoli_form_date_from'		=>	"Startdato",
	'srv_evoli_form_date_to'		=>	"Slutdato",
	'srv_evoli_form_dep'			=>	"Departments",
	'srv_evoli_form_emails'			=>	"Emails",
	'srv_evoli_form_success'		=>	"Success",
	'srv_evoli_form_err_inv'		=>	"Invitations are not enabled for this survey",
	'srv_evoli_form_err_sys'		=>	"Missing system variables (variable email must exist in survey)",
	'srv_evoli_form_err_g1'			=>	"Group",
	'srv_evoli_form_err_g2'			=>	"does not exist",
	'srv_evoli_form_err_server'		=>	"Email server settings and message not set",
	'srv_evoli_form_sent'			=>	"Email succesfully sent",
	'srv_evoli_form_err_sending'	=>	"Email sending error",
	'srv_evoli_form_group_added'	=>	"Group succesfully added",
	'srv_evoli_form_err_parameter1'	=>	"Missing parameters (group title, email, language id and quota are mandatory)",
	'srv_evoli_form_err_parameter2'	=>	"Missing parameters tm_id or departments",
	'srv_evoli_form_footer'			=>	"Contact our help on <a href=\"mailto:info@evoli.si\">info@evoli.si</a>",
	'srv_evoli_form_error'			=>	"Error! Missing email or survey ID!",
	'srv_evoli_form_error_access'	=>	"Error! You don't have access to input form",
	'srv_evoli_form_help1'			=>	"Indtast navnet på din virksomhed/organisation. Dette navn vil blive vist i den endelige rapport.",
	'srv_evoli_form_help2'			=>	"Indtast navnene på de teams/afdelinger, som dine medlemmer skal kunne vælge i spørgeskemaet. Tilføj eller slet teams/afdelinger med + og –",
	'srv_evoli_form_help3'			=>	"Angiv startdato og slutdato for den periode, som testen skal være åben.",
	'srv_evoli_form_help4'			=>	"Indtast e-mail adresserne på de personer, som du vil invitere til at deltage i testen.<br />Indtast kun en e-mail adresse pr. linje.<br />Dobbeltcheck at ingen adresse indeholder stavefejl, mellemrum og kommaer.<br />Du må gerne kopiere adresserne fra et dokument eller fra et regneark, men kontroller alligevel for stavefejl, inden du sender.",
	// END EVOLI //
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>