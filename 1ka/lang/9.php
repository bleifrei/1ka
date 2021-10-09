<?php
// _/ _/ _/  za primerjavo z 1.php uporabi: /utils/checkLanguageKeys.php _/ _/ _/
// Language file

$lang_add = array (
	"useful_translation"	=>		"0",
	"id"			=>		"9",							// ID te jezikovne datoteke (ID.php)
	"lang_short"	=>		"es",							// si - slovenian, en - english
	"language"		=> 	"Spanish",
	
	// tukaj so zbrani vsi teksti za respondentov vmesnik ankete
	"srv_survey_non_active"			=>	"La encuesta está cerrada.",
	"srv_survey_non_active_notStarted"	=>	"La encuesta no está activa. La encuesta comienza el:",
	"srv_survey_non_active_expired"		=>	"La encuesta no está activa. La encuesta expiró el:",
	"srv_survey_non_active_voteLimit"	=>	"La encuesta alcanzó el número máximo de respuestas.",
	"srv_previewalert"			=>	"¡La encuesta se encuentra en modo visualización!¡Las respuestas no se guardarán!",
	"srv_recognized"			=>	"Usted está respondiendo a esta encuesta como:",
	"srv_ranking_avaliable_categories"	=>	"Categorías disponibles",
	"srv_ranking_ranked_categories"		=>	"Categorías clasificadas",
	"srv_add_field"				=>	"Añadir nuevo campo",
	"glasovanja_spol_izbira"		=>	"Elegir el sexo",
	"glasovanja_spol_moski"			=>	"Masculino",
	"glasovanja_spol_zenska"		=>	"Femenino",
	"srv_remind_sum_hard"			=>	"Ha superado el límite de la suma!!",
	"srv_remind_sum_soft"			=>	"Ha superado el límite de la suma. ¿Desea continuar?",
	"srv_remind_num_hard"			=>	"Ha superado el límite del número!",
	"srv_remind_num_soft"			=>	"Ha superado el límite del número! ¿Desea continuar?",
	"srv_remind_hard"			=>	"¡Por favor, conteste a todas las preguntas obligatorias!",
	"srv_remind_soft"			=>	"No ha contestado a todas las preguntas obligatorias. ¿Desea continuar?",
	"srv_potrdi"				=>	"Confirmar",
	"srv_lastpage"				=>	"Última página",
	"srv_nextpage"				=>	"Siguiente página",
	"srv_nextpage_uvod"				=>	"Siguiente página",
	"srv_prevpage"				=>	"Página Anterior",
	"srv_konec"							=>	"Fin",
	"results"				=>	"Resultados",
	"glasovanja_count"			=>	"Recuento de votos",
	"glasovanja_time"			=>	"La votación está abierta desde ",
	"glasovanja_time_end"			=>	"hasta",
	"hour_all"				=>	"Todos",
	"glasovanja_spol_zenske"		=>	"Femenino",
	"srv_intro"				=>	"Por favor, tómese unos minutos y complete esta encuesta haciendo clic en Página siguiente",
	"srv_basecode"				=>	"Introduzca su contraseña",
	"srv_end"				=>	"Ha terminado la encuesta. Gracias.",
	"srv_back_edit"				=>	"Volver a la edición",
	"srv_nextins"				=>	"Próxima insercción",
	"srv_insend"				=>	"Acabar",
	"srv_back_edit"				=>	"Volver a la edición",
	"srv_alert_msg"				=>	"ha acabado la encuesta.",
	"srv_alert_subject"			=>	"Encuesta terminada",
	"srv_remind_captcha_hard"	=> 	"¡El codigo que ha entrado no es el mismo con el de la foto!",
	"srv_remind_captcha_soft"	=> 	"¡El codigo que ha entrado no es el mismo con el de la foto! ¿Desea continuar?",
	"srv_alert_number_exists"	=>	"Alerta: éste número ya existe!",
	"srv_alert_number_toobig"	=>	"Alerta: el número es muy grande!",
	"srv_forma_send"			=>	"Enviar",
	"srv_dropdown_select"		=>	'Seleccionar',
	"srv_question_respondent_comment" 	=> "Su comentario sobre ésta pregunta.",
	"srv_remind_hard_-99" 				=> "No ha contestado a todas las preguntas obligatorias. Ahora tiene la opción.",
	"srv_remind_soft_-99" 				=> "No ha contestado a todas las preguntas obligatorias. Ahora tiene la opción?",
	"srv_remind_email_hard" 			=> "¡La dirección del correo electronico que ha entrado no es valido!",
	"srv_remind_email_soft" 			=> "¡La dirección del correo electronico que ha entrado no es valido! ¿Desea continuar?",
	"srv_continue_later" 				=> "Continuar en otro momento",
	"srv_continue_later_txt" 			=> "Puede continuar con esta encuesta en otro momento guardanto este URL",
	"srv_continue_later_email" 			=> "Sugerimos que envíe este enlace URL a su e-mail",
	"srv_wrongcode" 					=> "Código incorrecto",
	"user_bye_textA" 					=> "ΈTiene éxito a anular su inscripción en recibir invitaciones para esta encuesta.",
	"srv_survey_deleted" 				=> "La encuesta está eliminada.",
	"srv_survey_non_active_notActivated" => "La encuesta todavia no está activa.",
	"srv_remind_hard_-98" 				=> "Por favor, conteste todas las preguntas necesarias! Ahora tiene la oportunidad de responder rechazado.",
	"srv_remind_soft_-98"  				=> "No se requiere a responder a cualquier pregunta. Ahora tiene la oportunidad de responder rechazado. ¿Quiere continuar?",
	"srv_remind_hard_-97" 				=> "Por favor, conteste todas las preguntas necesarias! Ahora tiene la oportunidad de responder inapropiado.",
	"srv_remind_soft_-97"  				=> "No se requiere a responder a cualquier pregunta. Ahora tiene la oportunidad de responder inapropiado. ¿Quiere continuar?",
);

include(dirname(__FILE__).'/2.php');

// povozimo angleski jezik s prevodi
foreach ($lang_add AS $key => $val) {
	$lang[$key] = $val;
}

?>