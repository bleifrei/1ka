<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"27",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"ct",							// si - slovenian, en - english
	"language"		=> 	"Catalan",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Pàgina següent",	//	Next page
	"srv_nextpage_uvod"					=>	"Pàgina següent",	//  Next page
	"srv_prevpage"						=>	"Pàgina anterior",	//	Previous page
	"srv_lastpage"						=>	"Última pàgina",	//	Last page
	"srv_forma_send"					=>	"Enviar",	//  Send
	"srv_konec"							=>	"Final",	//	End
	"srv_remind_sum_hard"				=>	"S'ha superat el límit de la suma!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"S'ha superat el límit de capital. Voleu continuar?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"S'ha superat el límit de número!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"S'ha superat el límit de nombre. Voleu continuar?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Si us plau, contesti totes les preguntes obligatòries!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"No ha respost a totes les preguntes obligatòries. Voleu continuar?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"El codi que heu introduït no és el mateix que a la foto!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"El codi que heu introduït no és el mateix que a la foto! Voleu continuar?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Les categories disponibles",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Categories classificatòries",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Alerta: el número que ja existeix!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Alerta: el nombre és massa gran!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Si us plau, feu una minuts i completar l'enquesta fent clic a la pàgina següent.",
	"srv_end"							=>	"Vostè ha acabat amb aquesta enquesta. Gràcies.",
	"srv_survey_non_active" 			=>	"Estudi està tancat.",
	"srv_survey_non_active_notStarted"	=>	"Enquesta no està activa l'enquesta comença el: ",
	"srv_survey_non_active_expired"		=>	"Enquesta no està actiu enquesta va expirar el: ",
	"srv_survey_non_active_voteLimit"	=>	"Va arribar a l'enquesta un recompte màxim de resposta.",
	"srv_previewalert"					=>	"No heu una vista prèvia de l'enquesta! Les respostes no se salvaran!",
	"srv_recognized"					=>	"Vostè està responent a aquesta enquesta",
	"srv_add_field"						=>	"Afegir nou camp",
	"glasovanja_spol_izbira"			=>	"Escollir el sexe",
	"glasovanja_spol_moski"				=>	"Masculí",
	"glasovanja_spol_zenska"			=>	"Femení",
	"glasovanja_spol_zenske"			=>	"Femení",
	"srv_potrdi"						=>	"Confirmar",
	"results"							=>	"Resultats",
	"glasovanja_count"					=>	"Recompte de vots",
	"glasovanja_time"					=>	"La votació és oberta de",
	"glasovanja_time_end"				=>	"a",
	"hour_all"							=>	"Tots",
	"srv_basecode"						=>	"Introduïu la contrasenya",
	"srv_back_edit"						=>	"Tornar a l'edició",
	"srv_nextins"						=>	"Inserció de la propera",
	"srv_insend"						=>	"Finalitzar",
	"srv_alert_msg"						=>	"Ha completat l'enquesta",
	"srv_alert_subject"					=>	"Enquesta acabat",
	"srv_question_respondent_comment"	=>	"El seu comentari sobre la qüestió",
	"srv_dropdown_select"						=>	'Selecciona',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>