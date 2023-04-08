function showSurveyCondition(cid)
{
	var chooseProfileJSAction = $("#chooseProfileJSAction").val();
	$("#fullscreen").load('ajax.php?t=surveyCondition&a=showCondition', {anketa:srv_meta_anketa_id,cid:cid, surveyConditionProfileAction:chooseProfileJSAction});
}

function newSurveyCondition()
{
	var name = $("#newSurveyConditionName").val();
	var chooseProfileJSAction = $("#chooseProfileJSAction").val();
	// kreiramo nov profil z novim id
	$.post('ajax.php?t=surveyCondition&a=newCondition', { anketa : srv_meta_anketa_id, name : name, surveyConditionProfileAction:chooseProfileJSAction}, 
		function(response) 
		{
		var data = jQuery.parseJSON(response);
			if (data.if_id > 0) 
			{
				showSurveyCondition(data.if_id);
			} 
			else 
			{
				alert('Error! (errCode:'+data.error+')');
			}
		}
	)
}

function deleteSurveyCondition(cid)
{
	var chooseProfileJSAction = $("#chooseProfileJSAction").val();
	// kreiramo nov profil z novim id
	$.post('ajax.php?t=surveyCondition&a=deleteCondition', { anketa : srv_meta_anketa_id, cid:cid, surveyConditionProfileAction:chooseProfileJSAction}, 
			function(response) 
			{
		var data = jQuery.parseJSON(response);
		if (data.error == 0) 
		{
			showSurveyCondition(data.cid);
			
			if ( $("#surveyConditionPage").val() == 'invitations')
			{
				// osvežimo background
				$("#conditionProfileNote").hide();
				//
				$("#inv_rec_filter a").data('cid',0);
			}
		} 
		else 
		{
			alert('Error! (errCode:'+data.error+')');
		}
	}
	);
}
function showRenameSurveyCondition(cid)
{
	var chooseProfileJSAction = $("#chooseProfileJSAction").val();	
	$("#surveyConditionCover").show();
	$("#divConditionProfiles #renameProfileDiv").load('ajax.php?t=surveyCondition&a=showRename', { anketa : srv_meta_anketa_id, cid:cid}).show();
	
}
function renameSurveyCondition()
{
	var cid = $("#divConditionProfiles #renameProfileId").val();
	var name = $("#divConditionProfiles #renameProfileName").val();
	$.post('ajax.php?t=surveyCondition&a=renameCondition', { anketa : srv_meta_anketa_id, cid:cid, name:name}, 
		function(response) 
		{
			var data = jQuery.parseJSON(response);
			if (data.error == 0 && data.if_id > 0) 
			{
				showSurveyCondition(data.if_id);
			} 
			else 
			{
				alert(data.errorMsg+'');
			}
		}
	);
	
}