<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"10",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"fr",							// si - slovenian, en - english
	"language"		=> 	"French",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	"srv_survey_non_active"			=>	"Enquête est fermée.",
	"srv_survey_non_active_notStarted"	=>	"Enquête n'est pas active. Enquete commence le:",
	"srv_survey_non_active_expired"		=>	"Enquête n'est pas active. Enquete  a expiré le: ",
	"srv_survey_non_active_voteLimit"	=>	"Enquête atteint un nombre maximal de réponse.",
	"srv_previewalert"			=>	"Vous etes en train de prévisualiser l'enquête! Les réponses ne seront pas enregistrées!",
	"srv_recognized"			=>	"Vous etes répondant a cette enquête comme:",
	"srv_ranking_avaliable_categories"	=>	"Catégories disponibles",
	"srv_ranking_ranked_categories"		=>	"Catégories classées",
	"srv_add_field"				=>	"Ajouter un nouveau champ",
	"glasovanja_spol_izbira"		=>	"Choisir le sexe",
	"glasovanja_spol_moski"			=>	"Masculin",
	"glasovanja_spol_zenska"		=>	"Féminin",
	"srv_remind_sum_hard"			=>	"Vous avez dépassé la limite de la somme!",
	"srv_remind_sum_soft"			=>	"Vous avez dépassé la limite de la somme. Souhaitez-vous procéder?",
	"srv_remind_num_hard"			=>	"Vous avez dépassé la limite du nombre!",
	"srv_remind_num_soft"			=>	"Vous avez dépassé la limite du nombre. Souhaitez-vous procéder?",
	"srv_remind_hard"			=>	"S'il vous plaît, répondez à toutes les questions obligatoires!",
	"srv_remind_soft"			=>	"Vous n'avez pas répondu à toutes les questions obligatoires. Souhaitez-vous procéder?",
	"srv_potrdi"				=>	"Confirmer",
	"srv_lastpage"				=>	"Dernière page",
	"srv_nextpage"				=>	"Page suivante",
	"srv_nextpage_uvod"				=>	"Page suivante",
	"srv_prevpage"				=>	"Page précédente",
	"srv_konec"							=>	"Fin",
	"results"				=>	"Résultats",
	"glasovanja_count"			=>	"Décompte des voix",
	"glasovanja_time"			=>	"Le vote est ouvert à partir de",
	"glasovanja_time_end"			=>	"à",
	"hour_all"				=>	"Tous",
	"glasovanja_spol_zenske"		=>	"Féminin",
	"srv_intro"				=>	"Veuillez prendre quelques instants et remplir ce questionnaire en cliquant sur la Page suivante",
	"srv_basecode"				=>	"Insérez votre code d'accès.",
	"srv_end"				=>	"Vous avez terminé avec cette enquête. Merci.",
	"srv_back_edit"				=>	"Retour à modifier",
	"srv_nextins"				=>	"Ensuite, insérer",
	"srv_insend"				=>	"Terminer",
	"srv_back_edit"				=>	"Retour à modifier",
	"srv_alert_msg"				=>	"a terminé l'enquête",
	"srv_alert_subject"			=>	"Enquête terminée",
	"srv_remind_captcha_hard"	=> 	"Le code que vous avez entré n'est pas la même que dans l'image!",
	"srv_remind_captcha_soft"	=> 	"Le code que vous avez entré n'est pas la même que dans l'image! Voulez-vous continuer?",
	"srv_alert_number_exists"	=>	"Alerte: le nombre existent déjà!",
	"srv_alert_number_toobig"	=>	"Alerte: nombre est trop grand!",
	"srv_forma_send"			=>	"Envoyer",
	"srv_dropdown_select"						=>	'Sélectionner',
	
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>