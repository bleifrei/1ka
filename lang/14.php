<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"14",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"cz",							// si - slovenian, en - english
	"language"		=> 	"Czech",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Další stránka",	//	Next page
	"srv_nextpage_uvod"					=>	"Další stránka",	//  Next page
	"srv_prevpage"						=>	"Předchozí strana",	//	Previous page
	"srv_lastpage"						=>	"Poslední stránka",	//	Last page
	"srv_forma_send"					=>	"Poslat",	//  Send
	"srv_konec"							=>	"Konec",	//	End
	"srv_remind_sum_hard"				=>	"Překročili jste součet limit!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Překročili jste součet limit. Chcete pokračovat?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Překročili jste počet limit!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Překročili jste počet limit. Chcete pokračovat?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Odpovězte, prosím, všechny povinné otázky!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Neodpověděl jste všechny povinné otázky. Chcete pokračovat?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Kód, který jste zadali, není stejná jako na obrázku!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Kód, který jste zadali, není stejná jako na obrázku! Chcete pokračovat?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Dostupné kategorie",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Hodnocené kategorie",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Upozornění: číslo již existuje!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Upozornění: číslo je příliš velké!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Věnujte prosím několik okamžiků a dokončit tento průzkum kliknutím na následující straně.",
	"srv_end"							=>	"Dokončili jste z tohoto zjišťování. Děkuji vám.",
	"srv_survey_non_active"				=>	"Průzkum je uzavřena.",
	"srv_survey_non_active_notStarted"	=>	"Průzkum není aktivní služba začíná na: ",
	"srv_survey_non_active_expired"		=>	"Průzkum není aktivní služba skončila dne: ",
	"srv_survey_non_active_voteLimit"	=>	"Přehled dosáhl maximální počet odpovědí.",
	"srv_previewalert"					=>	"Právě náhled na průzkum! Odpovědi se neuloží!",
	"srv_recognized"					=>	"Odpovídáte tento průzkum jako",
	"srv_add_field"						=>	"Přidat nové pole",
	"glasovanja_spol_izbira"			=>	"Výběr pohlaví",
	"glasovanja_spol_moski"				=>	"Muž",
	"glasovanja_spol_zenska"			=>	"Žena",
	"glasovanja_spol_zenske"			=>	"Žena",
	"srv_potrdi"						=>	"Potvrdit",
	"results"							=>	"Výsledky",
	"glasovanja_count"					=>	"Počet hlasů",
	"glasovanja_time"					=>	"Hlasování je otevřeno od",
	"glasovanja_time_end"				=>	"na",
	"hour_all"							=>	"Vše",
	"srv_basecode"						=>	"Vložte heslo",
	"srv_back_edit"						=>	"Zpět k úpravám",
	"srv_nextins"						=>	"Další vložit",
	"srv_insend"						=>	"Dokončit",
	"srv_alert_msg"						=>	"Dokončil průzkum",
	"srv_alert_subject"					=>	"Konečným průzkum",
	"srv_question_respondent_comment"	=>	"Váš komentář k otázce",
	"srv_dropdown_select"						=>	'Vybrat',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>