<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"18",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"pt",							// si - slovenian, en - english
	"language"		=> 	"Portuguese",

	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	// anketa -> urejanje -> nastavitve -> standardne besede
	"srv_nextpage"						=>	"Página seguinte",	//	Next page
	"srv_nextpage_uvod"					=>	"Página seguinte",	//  Next page
	"srv_prevpage"						=>	"Página anterior",	//	Previous page
	"srv_lastpage"						=>	"Última página",	//	Last page
	"srv_forma_send"					=>	"Enviar",	//  Send
	"srv_konec"							=>	"Final",	//	End
	"srv_remind_sum_hard"				=>	"Você excedeu o limite de soma!",	//	You have exceeded the sum limit!
	"srv_remind_sum_soft"				=>	"Você excedeu o limite de soma. Você quer proceder?",	//	You have exceeded the sum limit. Do you want to proceed?
	"srv_remind_num_hard"				=>	"Você excedeu o número limite!",	//	You have exceeded the number limit!
	"srv_remind_num_soft"				=>	"Você excedeu o número limite. Você quer proceder?",	//	You have exceeded the number limit. Do you want to proceed?
	"srv_remind_hard"					=>	"Por favor, responda todas as perguntas obrigatórias!",	//	Please answer all mandatory questions!
	"srv_remind_soft"					=>	"Você não respondeu todas as perguntas obrigatórias. Você quer proceder?",	//	You have not answered all mandatory questions. Do you want to proceed?
	"srv_remind_captcha_hard"			=> 	"O código que você digitou não é o mesmo que na foto!",	//	The code you entered is not the same as in the picture!
	"srv_remind_captcha_soft"			=> 	"O código que você digitou não é o mesmo que na foto! Você quer continuar?",	// The code you entered is not the same as in the picture! Do you want to continue?
	"srv_ranking_avaliable_categories"	=>	"Categorias disponíveis",	//	Available categories
	"srv_ranking_ranked_categories"		=>	"Categorias classificados",	//	Ranked categories
	"srv_alert_number_exists"			=>	"Alerta: o número já existe!",	//	Alert: the number already exist!
	"srv_alert_number_toobig"			=>	"Alerta: o número é grande demais!",	//	Alert: the number is too big!
	// preostalo
	"srv_intro"							=>	"Por favor, dedique alguns momentos e concluir esta pesquisa, clicando na página seguinte.",
	"srv_end"							=>	"Você ter acabado com esta pesquisa. Obrigado.",
	"srv_survey_non_active"				=>	"Inquérito está fechado.",
	"srv_survey_non_active_notStarted"	=>	"Inquérito não está activo Pesquisa começa em: ",
	"srv_survey_non_active_expired"		=>	"Inquérito não é Pesquisa ativo expirou em: ",
	"srv_survey_non_active_voteLimit"	=>	"Pesquisa atingiu uma contagem máxima resposta.",
	"srv_previewalert"					=>	"Você está visualizando no momento da pesquisa! Respostas não será salvo!",
	"srv_recognized"					=>	"Você está respondendo nesta pesquisa como",
	"srv_add_field"						=>	"Adicionar novo campo",
	"glasovanja_spol_izbira"			=>	"Escolha o sexo",
	"glasovanja_spol_moski"				=>	"Masculino",
	"glasovanja_spol_zenska"			=>	"Feminino",
	"glasovanja_spol_zenske"			=>	"Feminino",
	"srv_potrdi"						=>	"Confirmar",
	"results"							=>	"Resultados",
	"glasovanja_count"					=>	"Contagem de votos",
	"glasovanja_time"					=>	"A votação é aberta a partir de",
	"glasovanja_time_end"				=>	"Para",
	"hour_all"							=>	"Todos",
	"srv_basecode"						=>	"Insira sua senha",
	"srv_back_edit"						=>	"Voltar para a edição",
	"srv_nextins"						=>	"Inserção próxima",
	"srv_insend"						=>	"Terminar",
	"srv_alert_msg"						=>	"Concluiu a pesquisa",
	"srv_alert_subject"					=>	"Concluído inquérito",
	"srv_question_respondent_comment"	=>	"Seu comentário sobre a questão",
	"srv_dropdown_select"						=>	'Selecionar',
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>