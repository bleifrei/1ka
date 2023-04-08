<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"16",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"sk",							// si - slovenian, en - english
	"language"		=> 	"Slovak",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Dalšia stránka",	//	Next page
	"srv_nextpage_uvod"					=>	"Dalšia stránka",	//  Next page
	"srv_prevpage"						=>	"Predchádzajúci",	//	Previous page
	"srv_lastpage"						=>	"Posledná stránka",	//	Last page
	"srv_forma_send"					=>	"Odoslať",	//  Send
	"srv_konec"							=>	"Koniec",	//	End
	"srv_remind_sum_hard"				=>	"Prekročili ste súčet limit!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Prekročili ste súčet limit. Chcete pokračovať?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Prekročili ste počet limit!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Prekročili ste počet limit. Chcete pokračovať?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Odpovedzte, prosím, všetky povinné otázky!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Neodpovedal ste všetky povinné otázky. Chcete pokračovať?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Kód, ktorý ste zadali, nie je rovnaká ako na obrázku!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Kód, ktorý ste zadali, nie je rovnaká ako na obrázku! Chcete pokračovať?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Dostupné kategórie",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Hodnotené kategórie",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Upozornenie: číslo už existuje!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Upozornenie: číslo je príliš veľké!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Venujte prosím niekoľko okamihov a dokončiť tento prieskum kliknutím na nasledujúcej strane.",
	"srv_end" 							=>	"Dokončili ste z tohto prieskumu. Ďakujem vám.",
	"srv_survey_non_active"				=>	"Prieskum je uzavretá.",
	"srv_survey_non_active_notStarted"	=>	"Prieskum nie je aktívna služba začína na: ",
	"srv_survey_non_active_expired"		=>	"Prieskum nie je aktívna služba skončila dňa: ",
	"srv_survey_non_active_voteLimit"	=>	"Prehľad dosiahol maximálny počet odpovedí.",
	"srv_previewalert"					=>	"Práve náhľad na prieskum! Odpovede sa neuloží!",
	"srv_recognized"					=>	"Odpovedáte tento prieskum ako",
	"srv_add_field"						=>	"Pridať nové pole",
	"glasovanja_spol_izbira"			=>	"Výber pohlavia",
	"glasovanja_spol_moski"				=>	"Muž",
	"glasovanja_spol_zenska"			=>	"Žena",
	"glasovanja_spol_zenske"			=>	"Žena",
	"srv_potrdi"						=>	"Potvrdiť",
	"results"							=>	"Výsledky",
	"glasovanja_count"					=>	"Počet hlasov",
	"glasovanja_time"					=>	"Hlasovanie je otvorené od",
	"glasovanja_time_end"				=>	"Na",
	"hour_all"							=>	"Všetko",
	"srv_basecode"						=>	"Vložte heslo",
	"srv_back_edit"						=>	"Späť do úprav",
	"srv_nextins"						=>	"Ďalší vložiť",
	"srv_insend"						=>	"Dokončiť",
	"srv_alert_msg"						=>	"Dokončil prieskum",
	"srv_alert_subject"					=>	"Konečným prieskum",
	"srv_question_respondent_comment"	=>	"Váš komentár k otázke",
	"srv_dropdown_select"						=>	'Vybrať',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>