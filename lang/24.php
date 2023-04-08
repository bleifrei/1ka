<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"24",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"ba",							// si - slovenian, en - english
	"language"		=> 	"Basque",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Hurrengo orria",	//	Next page
	"srv_nextpage_uvod"					=>	"Hurrengo orria",	//  Next page
	"srv_prevpage"						=>	"Aurreko orrialdea",	//	Previous page
	"srv_lastpage"						=>	"Azken orria",	//	Last page
	"srv_forma_send"					=>	"Bidali",	//  Send
	"srv_konec"							=>	"Amaiera",	//	End
	"srv_remind_sum_hard"				=>	"Batura muga gainditu duzu!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Batura muga gainditu duzu. Jarraitzea nahi duzu?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Kopurua muga gainditu duzu!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Zenbaki muga gainditu duzu. Jarraitzea nahi duzu?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Mesedez, derrigorrezko galdera guztiak erantzun!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Ez duzu galdera guztiak erantzun derrigorrezkoak. Jarraitzea nahi duzu?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Idatzitako kodea ez da argazkia bera!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Idatzitako kodea ez da argazkia bera! Aurrera jarraitu nahi al duzu?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Eskuragarri kategoria",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Postu kategoria",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Alerta: zenbakia dagoeneko badago!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Alerta: kopurua handiegia da!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Mesedez une batzuk hartu, eta inkesta bete Hurrengo orria klik.",
	"srv_end"							=>	"Inkesta honen duzu amaitu da. Eskerrik asko.",
	"srv_survey_non_active"				=>	"Inkesta itxita dago.",
	"srv_survey_non_active_notStarted"	=>	"Inkesta aktibo ez da inkesta hasten da: ",
	"srv_survey_non_active_expired"		=>	"Inkesta Inkesta aktibo ez da iraungi on: ",
	"srv_survey_non_active_voteLimit"	=>	"Inkesta iritsi gehienez erantzun Aldaketa.",
	"srv_previewalert"					=>	"Inkesta aurreikusten ari zara! erantzunak ez dira gorde!",
	"srv_recognized"					=>	"Inkesta hau, erantzungailu gisa ari zara",
	"srv_add_field"						=>	"Gehitu eremu berria",
	"glasovanja_spol_izbira"			=>	"Aukeratu sexua",
	"glasovanja_spol_moski"				=>	"Gizonezkoen",
	"glasovanja_spol_zenska"			=>	"Emakumezkoen",
	"glasovanja_spol_zenske"			=>	"Emakumezkoen",
	"srv_potrdi"						=>	"Berretsi",
	"results"							=>	"Emaitzak",
	"glasovanja_count"					=>	"De voto zenbatu",
	"glasovanja_time"					=>	"Bozketa irekita dago",
	"glasovanja_time_end"				=>	"To",
	"hour_all"							=>	"Guztiak",
	"srv_basecode"						=>	"zure pasahitza sartu",
	"srv_back_edit"						=>	"Atzera editatzeko",
	"srv_nextins"						=>	"Hurrengo insert",
	"srv_insend"						=>	"Amaitu",
	"srv_alert_msg"						=>	"Inkesta burutu du",
	"srv_alert_subject"					=>	"Amaiturik inkesta",
	"srv_question_respondent_comment"	=>	"Zure galdera iruzkina",
	"srv_dropdown_select"						=>	'Aukeratu',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>