<?php
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"42",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"hi",							// si - slovenian, en - english
	"language"		=> 	"Hindi",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	"srv_survey_non_active"			=>	" सर्वेक्षण बंद है ",
	"srv_survey_non_active_notStarted"	=>	" सर्वेक्षण सक्रिय नहीं है सर्वेक्षण शुरू होता है: ",
	"srv_survey_non_active_expired"		=>	" सर्वेक्षण सक्रिय नहीं है सर्वेक्षण की अवधि समाप्त हो गई: ",
	"srv_survey_non_active_voteLimit"	=>	" सर्वेक्षण अधिकतम प्रतिक्रिया गिनती पर पहुंच गया। ",
	"srv_previewalert"			=>	"आकड़ा सुरक्षित नहीं किया जा सकेगा।",
	"srv_recognized"			=>	"यदि आप चाहें तो सर्वेक्षण की प्रतिक्रया दे सकते हैं ।",
	"srv_ranking_avaliable_categories"	=>	" उपलब्ध श्रेणियां ",
	"srv_ranking_ranked_categories"		=>	" श्रेणीबद्ध श्रेणियां ",
	"srv_add_field"				=>	"एक नया खण्ड जोड़ें।",
	"glasovanja_spol_izbira"		=>	"Izaberi spol",
	"glasovanja_spol_moski"			=>	"पुरुष",
	"glasovanja_spol_zenska"		=>	"महिला",
	"srv_remind_sum_hard"			=>	" आपने राशि सीमा पार कर ली है! ",
	"srv_remind_sum_soft"			=>	" आपने राशि सीमा पार कर ली है क्या आप आगे बढ़ना चाहते हैं? ",
	"srv_remind_num_hard"			=>	" आपने संख्या सीमा पार कर ली है! ",
	"srv_remind_num_soft"			=>	" आपने संख्या सीमा पार कर ली है क्या आप आगे बढ़ना चाहते हैं? ",
	"srv_remind_hard"			=>	" कृपया सभी अनिवार्य प्रश्नों का उत्तर दें! ",
	"srv_remind_soft"			=>	" आपने सभी अनिवार्य प्रश्नों का उत्तर नहीं दिया है। क्या आप आगे बढ़ना चाहते हैं? ",
	"srv_potrdi"				=>	" पुष्टि करना ",
	"srv_lastpage"				=>	" अंतिम पृष्ठ ",
	"srv_nextpage"				=>	" अगला पृष्ठ ",
	"srv_nextpage_uvod"				=>	" अगला पृष्ठ ",
	"srv_prevpage"				=>	"  पिछला पृष्ठ ",
	"results"				=>	"Rezultati",
	"glasovanja_count"			=>	"Brojanje glasova",
	"glasovanja_time"			=>	"Glasovanje je moguče od",
	"glasovanja_time_end"			=>	"do",
	"hour_all"				=>	"Svi",
	"glasovanja_spol_zenske"		=>	"महिलाए",
	"srv_intro"				=>	"कृपया कुछ समय लें और अगले पृष्ठ पर क्लिक करके सर्वेक्षण (में मांगी गयी सूचना भरना)/ (में अनुक्रिया देना) प्रारंभ करें।",
	"srv_basecode"				=>	"अपना पासवर्ड दर्ज (प्रविष्ठ) करें।",
	"srv_end"				=>	"सभी प्रश्नों का उत्तर देने के लिए धन्यवाद। आपने इस सर्वेक्षण में सभी सभी प्रश्नों का उत्तर दिया। आपके सहयोग के लिए धन्यवाद।",
	"srv_back_edit"				=>	"Natrag na uređivanje",
	"srv_nextins"				=>	"अगली प्रविष्ठी।",
	"srv_insend"				=>	"समाप्त/ अंत",
	"srv_back_edit"				=>	"पुनः संशोधित करें।",
	"srv_alert_msg"				=>	"je završio anketu.",
	"srv_alert_subject"			=>	" सर्वेक्षण बंद है ",
	"srv_remind_captcha_hard"	=> 	" आपके द्वारा दर्ज किया गया कोड तस्वीर में जैसा नहीं है! ",
	"srv_remind_captcha_soft"	=> 	" आपके द्वारा दर्ज किया गया कोड तस्वीर में जैसा नहीं है! क्या आप जारी रखना चाहते हैं? ",
	"srv_alert_number_exists"	=>	" चेतावनी: संख्या पहले से मौजूद है! ",
	"srv_alert_number_toobig"	=>	" चेतावनी: संख्या बहुत बड़ी है! ",
	"srv_forma_send"			=>	" भेजें ",
	"srv_konec"					=>	" समाप्त ",
	"srv_dropdown_select"			=>	' चयन  ',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>