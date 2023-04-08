<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"				=>		"0",
	"id"								=>		"40",							// ID te jezikovne datoteke (ID.php)
	"lang_short"						=>		"ch",							// si - slovenian, en - english
	"language"							=>	"Chineese",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"下一页",
	"srv_nextpage_uvod"						=>	"下一页（第一页）",
	"srv_prevpage"						=>	"上一页",
	"srv_lastpage"						=>	"最后一页",
	"srv_forma_send"						=>	"发送",
	"srv_konec"						=>	"结束",
	"srv_remind_sum_hard"						=>	"您已经超过了限额",
	"srv_remind_sum_soft"						=>	"您已经超过了限额，还要继续吗？",
	"srv_remind_num_hard"						=>	"您已经超过了数量限制",
	"srv_remind_num_soft"						=>	"您已经超过了数量限制，还要继续吗？",
	"srv_remind_hard"						=>	"请回答所有强制性问题！",
	"srv_remind_soft"						=>	"您还没有回答所有强制性问题，还要继续吗？",
	"srv_remind_captcha_hard"						=>	"您输入的代码与图片中的不同！",
	"srv_remind_captcha_soft"						=>	"您输入的代码与图片中的不同！还要继续吗？",
	"srv_ranking_avaliable_categories"						=>	"可用分类",
	"srv_ranking_ranked_categories"						=>	"排名的分类",
	"srv_alert_number_exists"						=>	"警告：数字已经存在！",
	"srv_alert_number_toobig"						=>	"警告：数字太大了！",
	// preostalo							
	"srv_intro"						=>	"请占用您一些时间来完成我们的调查，请点击下一页",
	"srv_end"						=>	"您已经完成此次调研。非常感谢！",
	"srv_survey_non_active"						=>	"调查尚未启动",
	"srv_survey_non_active_notStarted"						=>	":调查没启动。调查开始",
	"srv_survey_non_active_expired"						=>	"调查没启动，调查期满",
	"srv_survey_non_active_voteLimit"  						=>	" .调查达到最大相应数量",
	"srv_previewalert"						=>	"您目前处于调查预览模式！答案不会被保存！",
	"srv_recognized"						=>	"您正在回答调查问题",
	"srv_add_field"						=>	"添加新的字段",
	"glasovanja_spol_izbira"						=>	"选择性别",
	"glasovanja_spol_moski"						=>	"男性",
	"glasovanja_spol_zenska"						=>	"女性",
	"glasovanja_spol_zenske"						=>	"女性",
	"results"						=>	"结果",
	"glasovanja_count"						=>	"投票数",
	"glasovanja_time"						=>	"投票是开放的",
	"glasovanja_time_end"						=>	"给",
	"hour_all"						=>	"所有",
	"srv_basecode"						=>	"输入您的密码",
	"srv_back_edit"						=>	"回到编辑页面",
	"srv_nextins"						=>	"添加下一项",
	"srv_insend"						=>	"ختم",
	"srv_alert_msg"						=>	"调查完成",
	"srv_question_respondent_comment"						=>	"您的评论",
	"srv_dropdown_select"						=>	'选择',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>