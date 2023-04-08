<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"				=>		"0",
	"id"								=>		"38",							// ID te jezikovne datoteke (ID.php)
	"lang_short"						=>		"ur",							// si - slovenian, en - english
	"language"							=>	"Urdu",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"اگلا صفحہ",
	"srv_nextpage_uvod"						=>	"اگلا صفحہ",
	"srv_prevpage"						=>	"پچھلا صفحہ",
	"srv_lastpage"						=>	"آخری صفحہ",
	"srv_forma_send"						=>	"بھیجیں",
	"srv_konec"						=>	"اختتام",
	"srv_remind_sum_hard"						=>	"تم نے رقم کی حد سے تجاوز ہے.",
	"srv_remind_sum_soft"						=>	"آپ کو رقم کی حد سے آگے بڑھ گئے ہیں کیا آپ جاری رکھنا چاہتے ہیں؟",
	"srv_remind_num_hard"						=>	"تم نے تعداد کی حد سے تجاوز ہے.",
	"srv_remind_num_soft"						=>	"تم تعداد کی حد سے تجاوز کیا آپ جاری رکھنا چاہتے ہیں؟",
	"srv_remind_hard"						=>	"تمام لازمی سوال کا جواب براہ مہربانی!",
	"srv_remind_soft"						=>	"تم سب لازمی سوال کا جواب نہیں ہے کیا آپ جاری رکھنا چاہتے ہیں؟",
	"srv_remind_captcha_hard"						=>	"آپ کی طرف سے درج کیا گیا کوڈ کے طور پر ایک ہی تصویر میں نہیں ہے!",
	"srv_remind_captcha_soft"						=>	"آپ کی طرف سے درج کیا گیا کوڈ کے طور پر ایک ہی تصویر میں نہیں ہے تو آپ جاری رکھنا چاہتے ہیں کیا؟",
	"srv_ranking_avaliable_categories"						=>	"دستیاب اقسام",
	"srv_ranking_ranked_categories"						=>	"درجہ بندی اقسام",
	"srv_alert_number_exists"						=>	"تعداد میں پہلے سے ہی موجود ہے ہوشیار!",
	"srv_alert_number_toobig"						=>	"انتباہ: تعداد بہت بڑی ہے!",
	// preostalo							
	"srv_intro"						=>	"میں چند لمحے لگ اور اگلے صفحے پر کلک کر کے اس سروے کو مکمل کریں.",
	"srv_end"						=>	"تم سروے مکمل کر چکے شکریہ.",
	"srv_survey_non_active"						=>	"سروے بند کر دیا ہے.",
	"srv_survey_non_active_notStarted"						=>	"سروے فعال نہیں ہے سروے پر شروع ہوتا ہے:.",
	"srv_survey_non_active_expired"						=>	"سروے فعال نہیں ہے پر کی میعاد ختم ہو سروے:.",
	"srv_survey_non_active_voteLimit"  						=>	"سروے ایک زیادہ سے زیادہ جواب شمار تک پہنچ گئی ہے.",
	"srv_previewalert"						=>	"تم اس وقت پیش منظر کے موڈ میں ہیں جواب کو محفوظ نہیں رکھا جائے گا!",
	"srv_recognized"						=>	"آپ کے طور پر اس سروے کا جواب دے رہے ہیں",
	"srv_add_field"						=>	"نیا قطعہ شامل کریں",
	"glasovanja_spol_izbira"						=>	"جنسی انتخاب کریں",
	"glasovanja_spol_moski"						=>	"مرد",
	"glasovanja_spol_zenska"						=>	"عورت",
	"glasovanja_spol_zenske"						=>	"عورت",
	"srv_potrdi"						=>	"تصدیق",
	"results"						=>	"نتائج",
	"glasovanja_count"						=>	"شمار ووٹ",
	"glasovanja_time"						=>	"ووٹنگ سے کھلا ہے",
	"glasovanja_time_end"						=>	"کرنے کے لئے",
	"hour_all"						=>	"تمام",
	"srv_basecode"						=>	"اپنا پاس ورڈ داخل کریں",
	"srv_back_edit"						=>	"واپس ترمیم پر",
	"srv_nextins"						=>	"اگلا، دوسرا داخل",
	"srv_insend"						=>	"ختم",
	"srv_alert_msg"						=>	"سروے مکمل کر لیا ہے",
	"srv_alert_subject"						=>	"سروے ختم",
	"srv_question_respondent_comment"						=>	"سوال پر آپ کی رائے",
	"srv_dropdown_select"						=>	'منتخب کریں',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>