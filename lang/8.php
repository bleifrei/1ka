<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"8",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"it",							// si - slovenian, en - english
	"language"		=> 	"Italian",
	
	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	"srv_survey_non_active"            =>	"Il sondaggio è chiuso.",
	"srv_survey_non_active_notStarted" =>	"Sondaggio non attivo. L'indagine avrà inizio il: ",
	"srv_survey_non_active_expired"    =>	"Sondaggio non attivo. L'indagine è scaduta il: ",
	"srv_survey_non_active_voteLimit"  =>	"Il sondaggio ha raggiunto il numero massimo di partecipazioni.",
	"srv_previewalert"                 =>	"Anteprima sondaggio: le vostre risposte non verranno salvate!",
	"srv_recognized"                   =>	"Stai partecipando a questo sondaggio come",
	"srv_ranking_avaliable_categories" =>	"Categorie disponibili",
	"srv_ranking_ranked_categories"    =>	"Categorie ordinate",
	"srv_add_field"                    =>	"Aggiungi nuovo campo",
	"glasovanja_spol_izbira"           =>	"Sesso",
	"glasovanja_spol_moski"            =>	"Maschile",
	"glasovanja_spol_zenska"           =>	"Femminile",
	"srv_remind_sum_hard"              =>	"Hai superato la somma massima.",
	"srv_remind_sum_soft"              =>	"Hai superato la somma massima. Vuoi continuare?",
	"srv_remind_num_hard"              =>	"Hai superato il numero limite.",
	"srv_remind_num_soft"              =>	"Hai superato il numero limite. Vuoi continuare?",
	"srv_remind_hard"                  =>	"Siete pregati di rispondere a tutte le domande obbligatorie.",
	"srv_remind_soft"                  =>	"Non hai risposto a tutte le domande obbligatorie. Vuoi continuare?",
	"srv_potrdi"                       =>	"Confermare",
	"srv_lastpage"                     =>	"Ultima pagina",
	"srv_nextpage"                     =>	"Pagina successiva",
	"srv_nextpage_uvod"                     =>	"Pagina successiva",
	"srv_prevpage"                     =>	"Pagina precedente",
	"srv_konec"							=>	"Fine",
	"results"                          =>	"Risultati",
	"glasovanja_count"                 =>	"Conteggio voti",
	"glasovanja_time"                  =>	"Il voto è aperto da",
	"glasovanja_time_end"              =>	"a",
	"hour_all"                         =>	"Tutte",
	"glasovanja_spol_zenske"           =>	"Femminile",
	"srv_intro"                        =>	"Si prega di dedicare qualche minuto e completare questa indagine cliccando su Pagina successiva",
	"srv_basecode"                     =>	"Inserisci il tuo codice di accesso",
	"srv_end"                          =>	"Sondaggio completato. Grazie.",
	"srv_back_edit"                    =>	"Torna a Modifica",
	"srv_nextins"                      =>	"Prossimo inserimento",
	"srv_insend"                       =>	"Fine",
	"srv_back_edit"                    =>	"Torna a Modifica",
	"srv_alert_msg"                    =>	"ha completato l'indagine",
	"srv_alert_subject"                =>	"Indagine terminata",
	"srv_remind_captcha_hard"			=> 	"Il codice che hai inserito non è lo stesso come nella foto!",
	"srv_remind_captcha_soft"			=> 	"Il codice che hai inserito non è lo stesso come nella foto! Vuoi continuare?",
	"srv_alert_number_exists"			=>	"Alert: numero già esiste!",
	"srv_alert_number_toobig"			=>	"Alert: il numero è troppo grande!",
	"srv_forma_send"					=>	"Inviare",
	"srv_dropdown_select"						=>	'Selezionare',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>