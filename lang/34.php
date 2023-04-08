<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"34",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"ie",							// si - slovenian, en - english
	"language"		=> 	"Irish",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Chéad leathanach eile",	//	Next page
	"srv_nextpage_uvod"					=>	"Chéad leathanach eile",	//  Next page
	"srv_prevpage"						=>	"Leathanach roimhe seo",	//	Previous page
	"srv_lastpage"						=>	"Leathanach deireanach",	//	Last page
	"srv_forma_send"					=>	"Seol",	//  Send
	"srv_konec"							=>	"Deireadh",	//	End
	"srv_remind_sum_hard"				=>	"Tá tú thar an teorainn suim!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Tá tú thar an teorainn suim. Ar mhaith leat dul ar aghaidh?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Tá tú thar an teorainn ar líon!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Tá tú thar an teorainn ar líon. Ar mhaith leat dul ar aghaidh?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Freagair gach ceist éigeantach!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Níor thug tú freagra na ceisteanna go léir éigeantach. Ar mhaith leat dul ar aghaidh?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"Níl an cód iontráil tú mar an gcéanna sa phictiúr!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"Níl an cód iontráil tú mar an gcéanna sa phictiúr! Ar mhaith leat dul ar aghaidh?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Catagóirí atá ar Fáil",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Catagóirí rangaithe",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Airdeall: an uimhir atá ann cheana féin!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Airdeall: Is é an líon rómhór!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Tóg cúpla nóiméad agus an suirbhé seo a chomhlánú trí chliceáil ar an chéad leathanach eile.",
	"srv_end"							=>	"Tá tú críochnaithe leis an suirbhé seo. Go raibh maith agat.",
	"srv_survey_non_active"				=>	"Tá Suirbhé dúnta.",
	"srv_survey_non_active_notStarted"	=>	"Ní Suirbhé gníomhach Suirbhé thosaíonn ar :" ,
	"srv_survey_non_active_expired"		=>	"Ní Suirbhé gníomhach Suirbhé ar éag: ",
	"srv_survey_non_active_voteLimit"	=>	"Shroich Suirbhé ar líon uasta fhreagra.",
	"srv_previewalert"					=>	"Ní bheidh tú ag Réamhamharc faoi láthair leis an suirbhé! freagraí a shábháil!",
	"srv_recognized"					=>	"Tá tú ag freagairt an tsuirbhé seo mar",
	"srv_add_field"						=>	"Add réimse nua",
	"glasovanja_spol_izbira"			=>	"Roghnaigh ghnéas",
	"glasovanja_spol_moski"				=>	"Fireann",
	"glasovanja_spol_zenska"			=>	"Mná",
	"glasovanja_spol_zenske"			=>	"Mná",
	"srv_potrdi"						=>	"Dearbhaigh",
	"results"							=>	"Torthaí",
	"glasovanja_count"					=>	"Líon na vótaí a",
	"glasovanja_time"					=>	"Is é an vótáil oscailte ó",
	"glasovanja_time_end"				=>	"go",
	"hour_all"							=>	"All",
	"srv_basecode"						=>	"Cuir isteach do phasfhocal",
	"srv_back_edit"						=>	"Ar Ais ar eagarthóireacht",
	"srv_nextins"						=>	"Isteach Aghaidh",
	"srv_insend"						=>	"Críochnaigh",
	"srv_alert_msg"						=>	"Curtha i gcrích ag an suirbhé",
	"srv_alert_subject"					=>	"Suirbhé Críochnaithe",
	"srv_question_respondent_comment"	=>	"Do trácht ar an gceist",
	"srv_dropdown_select"						=>	'Roghnaigh',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>