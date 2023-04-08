<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"4",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"hr",							// si - slovenian, en - english
	"language"		=> 	"Croatian",
	
	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	"srv_survey_non_active"			=>	"Anketa je zaključena.",
	"srv_survey_non_active_notStarted"	=>	"Anketa nije aktivna. Anketa počinje: ",
	"srv_survey_non_active_expired"		=>	"Anketa nije aktivna. Anketa je istekla: ",
	"srv_survey_non_active_voteLimit"	=>	"Anketa je dosegla maksimalan broj odgovora.",
	"srv_previewalert"			=>	"Trenutno ste u pregledu ankete. Vaši odgovori neće se pohraniti!",
	"srv_recognized"			=>	"Na anketu odgovarate kao",
	"srv_ranking_avaliable_categories"	=>	"Postojeće kategorije",
	"srv_ranking_ranked_categories"		=>	"Rangirane kategorije",
	"srv_add_field"				=>	"Dodaj novo polje",
	"glasovanja_spol_izbira"		=>	"Izaberi spol",
	"glasovanja_spol_moski"			=>	"Muški",
	"glasovanja_spol_zenska"		=>	"Ženski",
	"srv_remind_sum_hard"			=>	"Prešli ste maksimalni zbroj!",
	"srv_remind_sum_soft"			=>	"Prešli ste maksimalni zbroj. Da li želite nastaviti?",
	"srv_remind_num_hard"			=>	"Prešli ste dozvoljenu vrijednost!",
	"srv_remind_num_soft"			=>	"Prešli ste dozvoljenu vrijednost. Da li želite nastaviti?",
	"srv_remind_hard"			=>	"Molimo, odgovorite na sva obavezna pitanja.",
	"srv_remind_soft"			=>	"Niste odgovorili na sva obavezna pitanja. Da li želite nastaviti?",
	"srv_potrdi"				=>	"Potvrdi",
	"srv_lastpage"				=>	"Posljedna stranica",
	"srv_nextpage"				=>	"Sljedeća stranica",
	"srv_nextpage_uvod"				=>	"Sljedeća stranica",
	"srv_prevpage"				=>	"Prethodna stranica",
	"results"				=>	"Rezultati",
	"glasovanja_count"			=>	"Brojanje glasova",
	"glasovanja_time"			=>	"Glasovanje je moguče od",
	"glasovanja_time_end"			=>	"do",
	"hour_all"				=>	"Svi",
	"glasovanja_spol_zenske"		=>	"Ženski",
	"srv_intro"				=>	"Molimo, odvojite nekoliko trenutaka i ispunite ovu anketu sa klikom na \"Sljedeća stranica\"",
	"srv_basecode"				=>	"Unesite svoju zaporku",
	"srv_end"				=>	"Završili ste s anketom. Hvala.",
	"srv_back_edit"				=>	"Natrag na uređivanje",
	"srv_nextins"				=>	"Sljedeći unos",
	"srv_insend"				=>	"Završi",
	"srv_back_edit"				=>	"Natrag na uređivanje",
	"srv_alert_msg"				=>	"je završio anketu.",
	"srv_alert_subject"			=>	"Završena anketa",
	"srv_remind_captcha_hard"	=> 	"Broj koji ste unijeli nije isti kao na slici!",
	"srv_remind_captcha_soft"	=> 	"Broj koji ste unijeli nije isti kao na slici! Želite li nastaviti?",
	"srv_alert_number_exists"	=>	"Upozorenje: broj već postoji!",
	"srv_alert_number_toobig"	=>	"Upozorenje: Broj je prevelik!",
	"srv_forma_send"			=>	"Slati",
	"srv_konec"					=>	"Kraj",
	"srv_dropdown_select"						=>	'Odabrati',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>