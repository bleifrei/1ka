<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"5",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"de",							// si - slovenian, en - english
	"language"		=> 	"German",
	
	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	"srv_survey_non_active"            => "Die Umfrage ist geschlossen.",
	"srv_survey_non_active_notStarted" => "Die Umfrage ist nicht aktiv, Die Umfrage beginnt am: ",
	"srv_survey_non_active_expired"    => "Die Umfrage ist nicht aktiv. Die Umfrage endete am:",
	"srv_survey_non_active_voteLimit"  => "Die Umfrage hat die maximale Antwortzahl erreicht.",
	"srv_previewalert"                 => "Sie befinden sich in der Umfragevorscau! Antworten werden nicht gespeichert!",
	"srv_recognized"                   => "Sie nehmen an der Umfrage teil als:",
	"srv_ranking_avaliable_categories" => "Verfügbare Kategorien",
	"srv_ranking_ranked_categories"    => "Eingeordnete Kategorien",
	"srv_add_field"                    => "Neues Feld einfügen",
	"glasovanja_spol_izbira"           => "Wählen Sie bitte ihres Geschlecht",
	"glasovanja_spol_moski"            => "Männlich",
	"glasovanja_spol_zenska"           => "Weiblich",
	"srv_remind_sum_hard"              => "Sie haben die maximale Summe überschritten!",
	"srv_remind_sum_soft"              => "Sie haben die maximale Summe. Möchten Sie fortsetzen?",
	"srv_remind_num_hard"              => "Sie haben die maximale Zahl überschritten!",
	"srv_remind_num_soft"              => "Sie haben die maximale Zahl überschritten. Möchten Sie fortsetzen?",
	"srv_remind_hard"                  => "Bitte beantworten Sie alle erforderlichen Fragen!",
	"srv_remind_soft"                  => "Sie haben nicht alle erforderliche Fragen beantwortet. Möchten Sie fortsetzen?",
	"srv_potrdi"                       => "Bitte bestätigen",
	"srv_lastpage"                     => "Letzte Seite",
	"srv_nextpage"                     => "Nächste Seite",
	"srv_nextpage_uvod"                     => "Nächste Seite",
	"srv_prevpage"                     => "Vorige Seite",
	"srv_konec"							=>	"Ende",
	"results"                          => "Ergebnisse",
	"glasovanja_count"                 => "Stimmenauszählung",
	"glasovanja_time"                  => "Stimmabgabe beginnt um",
	"glasovanja_time_end"              => "bis",
	"hour_all"                         => "Alle",
	"glasovanja_spol_zenske"           => "Weiblich",
	"srv_intro"                        => "Bitte nehmen Sie sich ein paar Augenblicke Zeit und vervollständigen die Umfrage",
	"srv_basecode"                     => "Geben Sie bitte ihr Kennwort ein",
	"srv_end"                          => "Sie haben die Befragung beendet. Vielen Dank.",
	"srv_back_edit"                    => "Zurück zu Bearbeiten",
	"srv_nextins"                      => "Nächste Eingabe",
	"srv_insend"                       => "Ende",
	"srv_back_edit"                    => "Zurück zu bearbeiten",
	"srv_alert_msg"                    => "hat die Umfrage abgeschlossen.",
	"srv_alert_subject"					=> "Vollständige Umfrage",
	"srv_remind_captcha_hard"			=> 	"Der eingegebene Code ist nicht das gleiche wie auf dem Bild!",
	"srv_remind_captcha_soft"			=> 	"Der eingegebene Code ist nicht das gleiche wie auf dem Bild! Wollen Sie fortsetzen?",
	"srv_alert_number_exists"			=>	"Alert: Nummer bereits existieren!",
	"srv_alert_number_toobig"			=>	"Alert: Zahl ist zu groß!",
	"srv_forma_send"					=>	"Senden",
	"srv_dropdown_select"						=>	'Auswählen',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>