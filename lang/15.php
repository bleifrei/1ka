<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"15",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"pl",							// si - slovenian, en - english
	"language"		=> 	"Polish",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Następna strona",	//	Next page
	"srv_nextpage_uvod"					=>	"Następna strona",	//  Next page
	"srv_prevpage"						=>	"Poprzednia strona",	//	Previous page
	"srv_lastpage"						=>	"Ostatnia strona",	//	Last page
	"srv_forma_send"					=>	"Wysłać",	//  Send
	"srv_konec"							=>	"Koniec",	//	End
	"srv_remind_sum_hard"				=>	"Został przekroczony limit sumy!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Został przekroczony limit sumy. Czy chcesz kontynuować?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Został przekroczony limit liczb.",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Został przekroczony limit liczb. Czy chcesz kontynuować?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Proszę odpowiedzieć na wszystkie obowiązkowe pytania!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Nie odpowiedziałeś na wszystkie obowiązkowe pytania. Czy chcesz kontynuować?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Wprowadzony kod nie jest taki sam jak na zdjęciu!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Wprowadzony kod nie jest taki sam jak na zdjęciu! Czy chcesz kontynuować?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Dostępne kategorie",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Rankingu kategorii",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Alert: numer już istnieje!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Alert: liczba jest za duża!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro" 						=>	"Proszę poświęć chwilę i wypełnić ankietę klikając na następnej stronie.",
	"srv_end"							=>	"została zakończona z tego badania. Dziękuję.",
	"srv_survey_non_active"				=>	"Ankieta jest zamknięta.",
	"srv_survey_non_active_notStarted"	=>	"Badanie nie jest aktywne Badanie rozpoczyna się: ",
	"srv_survey_non_active_expired"		=>	"Ankieta nie jest aktywne Ankieta wygasła w dniu to: ",
	"srv_survey_non_active_voteLimit"	=>	"Badanie osiągnęła maksymalną liczbę odpowiedzi.",
	"srv_previewalert"					=>	"Nie jesteś podglądu badaniu! odpowiedzi nie zostaną zapisane!",
	"srv_recognized"					=>	"Jesteś odpowiedzi na to badanie jako",
	"srv_add_field"						=>	"Dodaj nowe pole",
	"glasovanja_spol_izbira"			=>	"Wybierz płeć",
	"glasovanja_spol_moski"				=>	"Mężczyzna",
	"glasovanja_spol_zenska"			=>	"Kobieta",
	"glasovanja_spol_zenske"			=>	"Kobieta",
	"srv_potrdi"						=>	"Potwierdź",
	"results"							=>	"Wyniki",
	"glasovanja_count"					=>	"Ilość głosów",
	"glasovanja_time"					=>	"Głosowanie jest otwarte od",
	"glasovanja_time_end"				=>	"do",
	"hour_all"							=>	"wszystko",
	"srv_basecode"						=>	"Wstaw hasła",
	"srv_back_edit"						=>	"Powrót do edycji",
	"srv_nextins"						=>	"Next wkładka",
	"srv_insend"						=>	"Zakończ",
	"srv_alert_msg"						=>	"zakończył badanie",
	"srv_alert_subject"					=>	"Zakończono badanie",
	"srv_question_respondent_comment"	=>	"Váš komentář k otázce",
	"srv_dropdown_select"						=>	'Wybierać',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>