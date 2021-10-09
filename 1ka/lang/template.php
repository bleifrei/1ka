<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"id"			=>		"44",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"kk",							// si - slovenian, en - english
	"language"		=> 	"Kazakh",
    
    
	// Translation texts
    "srv_survey_non_active"			    =>	'Survey is closed.',
	"srv_survey_non_active_notStarted"	=>	'Survey is not active. Survey starts on: ',
	"srv_survey_non_active_expired"		=>	'Survey is not active. Survey expired on: ',
	"srv_survey_non_active_voteLimit"	=>	'Survey reached a maximum response count.',
	"srv_previewalert"			        =>	'You are currently in survey preview mode! Answers will not be saved!',
	"srv_recognized"			        =>	'You are answering this survey as',
	"srv_ranking_avaliable_categories"	=>	'Available categories',
	"srv_ranking_ranked_categories"		=>	'Ranked categories',
	"srv_add_field"				        =>	'Add new field',
    
    "glasovanja_spol_izbira"	=>	'Choose sex',
	"glasovanja_spol_moski"		=>	'Male',
	"glasovanja_spol_zenska"	=>	'Female',
    "glasovanja_spol_zenske"	=>	'Female',
	"glasovanja_count"			=>	'Vote count',
	"glasovanja_time"			=>  'Voting is open from',
    "glasovanja_time_end"		=>	'to',

	"srv_potrdi"				=>	'Confirm',
	"srv_lastpage"				=>	'Last page',
	"srv_nextpage"				=>	'Next page',
	"srv_nextpage_uvod"			=>	'Next page',
	"srv_prevpage"				=>	'Previous page',
	"results"					=>	'Results',
	"hour_all"					=>	'All',
	"srv_intro"					=>	'Please take a few moments and complete this survey by clicking on Next page.',
	"srv_basecode"				=>	'Insert your password',
	"srv_end"					=>	'You have finished the survey. Thank you.',
	"srv_back_edit"				=>	'Back to editing',
	"srv_nextins"				=>	'Next insert',
	"srv_insend"				=>  'Finish',
	"srv_back_edit"				=>	'Back to editing',
    
    "srv_alert_msg"				=>	'has completed the survey',
	"srv_alert_subject"			=>	'Finished Survey',
	"srv_alert_number_exists"	=>	'Alert: the number already exists!',
    "srv_alert_number_toobig"	=>	'Alert: the number is too big!',
    
	"srv_forma_send"			=>	'Send',
	"srv_konec"					=>	'End',
	"srv_dropdown_select"		=>	'Select',
    
    "srv_remind_captcha_hard"	=> 	'The code you entered is not the same as in the picture!',
	"srv_remind_captcha_soft"	=> 	'The code you entered is not the same as in the picture! Do you want to continue?',
    "srv_remind_sum_hard"		=>	'You have exceeded the sum limit!',
	"srv_remind_sum_soft"		=>	'You have exceeded the sum limit. Do you want to proceed?',
	"srv_remind_num_hard"		=>	'You have exceeded the number limit!',
	"srv_remind_num_soft"		=>	'You have exceeded the number limit. Do you want to proceed?',
	"srv_remind_hard"			=>	'Please answer all mandatory questions!',
	"srv_remind_soft"			=>	'You have not answered all mandatory questions. Do you want to proceed?',
	"srv_remind_hard_-99"	    =>	'Please answer all mandatory questions! Now you also have option Don\'t know.',
	"srv_remind_soft_-99"	    =>	'You have not answered all mandatory questions. Now you also have option Don\'t know. Do you want to proceed?',
	"srv_remind_hard_-98"	    =>	'Please answer all mandatory questions! Now you also have option Refused.',
	"srv_remind_soft_-98"	    =>	'You have not answered all mandatory questions. Now you also have option Refused. Do you want to proceed?',
	"srv_remind_hard_-97"	    =>	'Please answer all mandatory questions! Now you also have option Not applicable.',
	"srv_remind_soft_-97"	    =>	'You have not answered all mandatory questions. Now you also have option Not applicable. Do you want to proceed?',
	"srv_remind_hard_multi"	    =>	'Please answer all mandatory questions! Now you also have additional options available.',
    "srv_remind_soft_multi"	    =>	'You have not answered all mandatory questions. Now you also have additional options available. Do you want to proceed?',
	"srv_remind_email_hard"	    => 	'The email you entered is not valid!',
    "srv_remind_email_soft"	    => 	'The email you entered is not valid! Do you want to continue?',
    
    "srv_question_respondent_comment"		=>	'Your comment on the question',

    "srv_continue_later"					=>	'Continue later',
	"srv_continue_later_txt"				=>	'You can continue with this survey later by saving this URL',
	"srv_continue_later_email"				=>	'We suggest you send this URL link to your e-mail',

    'srv_wrongcode'							=>	'Wrong code',
    "user_bye_textA"		                =>	'You have successfully unsubscribed from receiving invitations to the survey.',

    "srv_survey_deleted"        			=>	 'Survey was deleted.',
    "srv_survey_non_active_notActivated"	=>	 'Survey was not activated yet.',
	"srv_survey_non_active_notActivated1"	=>	 'Survey was not activated yet.',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>