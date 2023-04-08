<?php

/**
* Javascript datoteka generirana s PHPjem, ki includa posamezne vrstice jezikovne datoteke, da se lahko uporabijo tudi v Javascriptu
* primer: lang['besedilo'] -- 'besedio' je isto kot v PHP arrayu $lang['besedilo']
* 
* Datoteko poklicemo s parametrom ?lang=si ?lang=en 
*/

// seconds, minutes, hours, days
$expires = 60*60*24*14;
header("Pragma: public");
header("Cache-Control: maxage=".$expires);
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+$expires) . ' GMT');
header('Content-Type: application/x-javascript; charset=utf-8'); 



// includamo ustrezen jezik
if (isset($_GET['lang']))
	$lang = $_GET['lang'];

if ($lang == 'si')
	include dirname(__FILE__).'/../../../lang/1.php';
else
	include dirname(__FILE__).'/../../../lang/2.php';

//include google maps API key/**
$settings_optional = dirname(__FILE__).'/../../../settings_optional.php';
if(file_exists($settings_optional)){
    include $settings_optional;
}
echo "var google_maps_API_key = '".(!empty($google_maps_API_key) ? $google_maps_API_key : '')."';";

// funkcija, ki skreira nov element v arrayu
function lang ($key) {
	global $lang;
	
	if (array_key_exists($key, $lang))
		echo 'lang[\''.$key.'\'] = \''.$lang[$key].'\';'."\n";
		
}


// javascript
echo 'var lang = new Array();'."\n\n";

lang('srv_brisispremenljivkoconfirm');
lang('srv_brisispremenljivko');
lang('srv_editirajspremenljivko');
lang('srv_copy_spr');
lang('srv_predogled_spremenljivka');
lang('srv_if_edit');
lang('srv_bruto_v_cas');
lang('srv_block_edit');
lang('srv_loop_edit');
lang('srv_copy_if');
lang('srv_copy_ifcond');
lang('srv_copy_block');
lang('srv_copy_loop');
lang('srv_if_rem');
lang('srv_if_rem_all');
lang('srv_block_rem');
lang('srv_block_rem_all');
lang('srv_loop_rem');
lang('srv_loop_rem_all');
lang('srv_brisiifconfirm');
lang('srv_brisiifconfirm_all');
lang('srv_brisiblockconfirm');
lang('srv_brisiblockconfirm_all');
lang('srv_brisiloopconfirm');
lang('srv_brisiloopconfirm_all');
lang('srv_vsi');
lang('srv_nevem');
lang('srv_zavrnil');
lang('srv_neustrezno');
lang('srv_editirajuvod');
lang('srv_editirajzakljucek');
lang('srv_permanent_date');
lang('srv_permanent_diable');
lang('srv_upgrade_ie');
lang('srv_missing_value_not_empty');
lang('srv_missing_confirm_use_system');
lang('srv_missing_confirm_delete');
lang('srv_collectdata_progress_status');
lang('srv_collectdata_progress_status1');
lang('srv_collectdata_progress_status2');
lang('srv_collectdata_progress_status3');
lang('srv_collectdata_progress_status8');
lang('srv_collectdata_progress_status9');
lang('srv_collectdata_progress_status0');
lang('srv_collectdata_progress_status0');
lang('srv_collectdata_failed');
lang('comments');
lang('srv_testiranje_komentarji_anketa_title2');
lang('srv_testiranje_komentar_q_all_title');
lang('srv_testiranje_komentar_q_resp_all_title');
lang('srv_testiranje_komentar_q_title');
lang('srv_testiranje_komentar_if_title');
lang('srv_testiranje_komentar_if_all_title');
lang('srv_testiranje_komentar_blok_title');
lang('srv_testiranje_komentar_blok_all_title');
lang('srv_loop_multiplication_error');
lang('srv_please_wait');
lang('send');
lang('srv_time_profile_error_interval');
lang('srv_disable');
lang('srv_if_new');
lang('srv_if_new_question');
lang('srv_chart_edit_warning');
lang('srv_inv_recipients_delete_profile_confirm');
lang('srv_inv_recipients_delete_list_confirm');
lang('srv_inv_recipients_delete_multi');
lang('srv_inv_recipients_delete_all');
lang('srv_inv_list_delete_multi');
lang('save');
lang('srv_new_vrednost');
lang('srv_analiza_arhiviraj');
lang('srv_success_save');
lang('srv_invitation_note1');
lang('srv_invitation_note2');
lang('srv_invitation_note10');
lang('srv_new_question');
lang('srv_data_delete_not_selected');
lang('srv_novavrednost_drugo');
lang('srv_unlock_alert');
lang('srv_custom_report_first');
lang('srv_ask_delete');
lang('srv_brisivrednostconfirm');
lang('srv_brisivrednost');
lang('srv_incremental_hs1');
lang('srv_incremental_hs2');
lang('srv_incremental_hs3');
lang('srv_incremental_hs4');
lang('srv_incremental_hs5');
lang('srv_incremental_hs6');
lang('srv_incremental_hs7');
lang('srv_incremental_hs99');
lang('srv_chart_num_limit_warning');
lang('srv_unlock_popup2');
lang('srv_unlock_popup3');
lang('srv_unlock_mobile');
lang('srv_custom_report_insert_title');
lang('srv_custom_report_inserted_title');
lang('srv_advanced_slideshow');
lang('srv_urlLinks_delete');
lang('srv_zapri');
lang('srv_remind_preview');
lang('srv_language_technology_flagged_wordings');
lang('srv_language_technology_wording_properites');
lang('srv_language_technology_wording');
lang('srv_language_technology_freguency');
lang('srv_language_technology_word_type');
lang('srv_language_technology_word_type');
lang('srv_language_technology_meanings');
lang('srv_language_technology_noun');
lang('srv_language_technology_verb');
lang('srv_language_technology_adjective');
lang('srv_language_technology_adverb');
lang('srv_language_technology_existential');
lang('srv_language_technology_relevant_meanings');
lang('srv_language_technology_alternative_wordings');
lang('srv_language_technology_no_alternative');
lang('srv_language_technology_no_alternative_selected');
lang('srv_aapor_automatic');
lang('edit_hide');
lang('edit_show');
lang('srv_hide-disable_answer-0');
lang('srv_hide-disable_answer-1');
lang('srv_hide-disable_answer-2');
lang('srv_podif_edit');
lang('srv_follow_up');
lang('srv_skala_0');
lang('srv_skala_1');
lang('upload_img2');
lang('srv_cookie_continue_alert');
lang('delete_account_conformation');
lang('change_account_pass_conformation');
lang('cms_error_password_incorrect');
lang('password_err_complex');
lang('srv_survey_list_users_confirm_delete');
lang('srv_survey_list_users_confirm_delete_warning');
lang('login_alternative_emails_error');
lang('login_alternative_emails_success');
lang('srv_user_exist');
lang('srv_user_not_exist');
lang('srv_newSurvey_survey_template_error');
lang('srv_checkbox_min_limit_error_msg');
lang('srv_heatmap_radius');
lang('srv_delete_testdata_warning');
lang('srv_alert_upload_size');
lang('srv_alert_upload_ext');
lang('srv_trans_lang');
lang('srv_manager_remove_alert');
lang('srv_resevanje_foto_pre_result');


//LOKACIJA
lang('srv_branching_no_results_geo_map');
lang('srv_vprasanje_fokus_button_map_set');
lang('srv_vprasanje_fokus_button_map_save');
lang('srv_vprasanje_fokus_button_map_cancel');
lang('srv_vprasanje_fokus_button_map_set_title');
lang('srv_vprasanje_fokus_button_map_save_title');
lang('srv_vprasanje_fokus_button_map_cancel_title');
lang('srv_vprasanje_delete_line_map');
lang('srv_vprasanje_delete_line_confirm_map');
lang('srv_vprasanje_delete_point_map');
//za resevanje ankete lokacija
lang('srv_resevanje_alert_location_not_found_map');
lang('srv_resevanje_user_denied_geo_map');
lang('srv_resevanje_browser_not_support_geo_map');
lang('srv_resevanje_position_unavailable_geo_map');
lang('srv_resevanje_timeout_geo_map');
lang('srv_resevanje_unknown_error_geo_map');
lang('srv_vprasanje_button_map_clear');

// HIARHIJA
lang('srv_hierarchy_create_error_2');
lang('srv_hierarchy_create_error_3');

// DRUPAL
lang('register_new_user');
lang('srv_gdpr_frontend_external_login');
lang('srv_gdpr_frontend_external_login_agree');
lang('srv_gdpr_frontend_register');

//MAZA
lang('srv_maza_repeater_edit_warning_alert');
lang('srv_maza_repeater_finish_warning_alert');
lang('srv_maza_geofence_infowin_name');
lang('srv_maza_geofence_infowin_radius');
lang('srv_maza_geofence_infowin_radius_unit');
lang('srv_maza_geofence_delete_confirm_map');

// EOF