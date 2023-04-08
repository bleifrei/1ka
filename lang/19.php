<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"19",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"se",							// si - slovenian, en - english
	"language"		=> 	"Swedish",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Nästa sida",	//	Next page
	"srv_nextpage_uvod"					=>	"Nästa sida",	//  Next page
	"srv_prevpage"						=>	"Föregående sida",	//	Previous page
	"srv_lastpage"						=>	"Sista sidan",	//	Last page
	"srv_forma_send"					=>	"Sända",	//  Send
	"srv_konec"							=>	"Änden",	//	End
	"srv_remind_sum_hard"				=>	"Du har överskridit summan gränsen!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Du har överskridit summan gränsen. Vill du fortsätta?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Du har överskridit antalet gränsen!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Du har överskridit antalet gränsen. Vill du fortsätta?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Var vänlig besvara alla obligatoriska frågor!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Du har inte svarat på alla obligatoriska frågor. Vill du fortsätta?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Koden du angav är inte samma som på bilden!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Koden du angav är inte samma som på bilden! Vill du fortsätta?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Tillgängliga kategorierna",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Rankade kategorier",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Varning: det nummer som redan finns!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Varning: antalet är för stort!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Ta några minuter och fylla i denna enkät genom att klicka på nästa sida.",
	"srv_end"							=>	"Du är klar med den här undersökningen. Tack så mycket.",
	"srv_survey_non_active"				=>	"Survey är stängd.",
	"srv_survey_non_active_notStarted"	=>	"Survey är inte aktivt Undersökning börjar på: ",
	"srv_survey_non_active_expired"		=>	"Survey är inte aktivt Undersökning löpte ut den: ",
	"srv_survey_non_active_voteLimit"	=>	"Survey nådde en maximal respons räkning.",
	"srv_previewalert"					=>	"Du är för närvarande förhandsgranska undersökningen mera Svaren kommer inte att sparas!",
	"srv_recognized"					=>	"Du svarar denna enkät som",
	"srv_add_field"						=>	"Lägg till nytt fält",
	"glasovanja_spol_izbira"			=>	"Välj kön",
	"glasovanja_spol_moski"				=>	"Man",
	"glasovanja_spol_zenska"			=>	"Kvinna",
	"glasovanja_spol_zenske"			=>	"Kvinna",
	"srv_potrdi"						=>	"Bekräfta",
	"results"							=>	"Resultat",
	"glasovanja_count"					=>	"Rösta count",
	"glasovanja_time"					=>	"Röstningen är öppen från",
	"glasovanja_time_end"				=>	"till",
	"hour_all"							=>	"Alla",
	"srv_basecode"						=>	"Infoga ditt lösenord",
	"srv_back_edit"						=>	"Tillbaka till redigering",
	"srv_nextins"						=>	"Nästa Infoga",
	"srv_insend"						=>	"Avsluta",
	"srv_alert_msg"						=>	"har genomfört undersökningen",
	"srv_alert_subject"					=>	"Färdig undersökning",
	"srv_question_respondent_comment"	=>	"Din kommentar på frågan",
	"srv_dropdown_select"						=>	'Välj',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>