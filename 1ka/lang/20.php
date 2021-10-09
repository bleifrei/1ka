<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"20",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"tr",							// si - slovenian, en - english
	"language"		=> 	"Turkish",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Sonraki sayfa",	//	Next page
	"srv_nextpage_uvod"					=>	"Sonraki sayfa",	//  Next page
	"srv_prevpage"						=>	"Önceki sayfa",	//	Previous page
	"srv_lastpage"						=>	"Son sayfa",	//	Last page
	"srv_forma_send"					=>	"Göndermek",	//  Send
	"srv_konec"							=>	"Son",	//	End
	"srv_remind_sum_hard"				=>	"Sen toplamı sınırını aştınız!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Sen toplamı sınırını aştınız. Devam etmek istiyor musunuz?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Bu sayı sınırı aştınız!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Bu sayı sınırını aştınız. Devam etmek istiyor musunuz?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Tüm zorunlu sorulara cevap!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Tüm zorunlu sorularını yanıtladı değil. Devam etmek istiyor musunuz?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Girdiğiniz kod resimde olduğu gibi aynı değildir!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Girdiğiniz kod resimde olduğu gibi aynı değildir! Devam etmek istiyor musunuz?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Mevcut kategoriler",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Sırada kategoriler",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Uyarı: numara zaten var!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Uyarı: sayı çok büyük!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Birkaç dakikanızı ayırın ve Sonraki sayfa tıklayarak bu anketi doldurunuz.",
	"srv_end"							=>	"Bu anket ile bitirdim. teşekkür ederiz.",
	"srv_survey_non_active"				=>	"Survey kapatılır.",
	"srv_survey_non_active_notStarted"	=>	"Anket aktif değil Anketi başlar: ",
	"srv_survey_non_active_expired"		=>	"Anketi sona aktif Anketi değil: ",
	"srv_survey_non_active_voteLimit"	=>	"Anket maksimum yanıt sayısı ulaşmıştır.",
	"srv_previewalert"					=>	"Şu anda anket önizlerken! cevap kaydedilmez!",
	"srv_recognized"					=>	"Sen bu anketi yanıtlayan vardır",
	"srv_add_field"						=>	"Yeni alan ekle",
	"glasovanja_spol_izbira"			=>	"Seçin seks",
	"glasovanja_spol_moski"				=>	"Erkek",
	"glasovanja_spol_zenska"			=>	"Kadın",
	"glasovanja_spol_zenske"			=>	"Kadın",
	"srv_potrdi"						=>	"Onayla",
	"results"							=>	"Sonuçlar",
	"glasovanja_count"					=>	"Oy sayımı",
	"glasovanja_time"					=>	"Oylama açıktır",
	"glasovanja_time_end"				=>	"Için",
	"hour_all"							=>	"Hepsi",
	"srv_basecode"						=>	"Şifreniz Ekle",
	"srv_back_edit"						=>	"Geri düzenleme için",
	"srv_nextins"						=>	"Sonraki ilan",
	"srv_insend"						=>	"Bitirmek",
	"srv_alert_msg"						=>	"Anketi tamamlandı",
	"srv_alert_subject"					=>	"Bitmiş araştırması",
	"srv_question_respondent_comment"	=>	"Sorusu üzerine Mesajiniz",
	"srv_dropdown_select"						=>	'Seçmek',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>