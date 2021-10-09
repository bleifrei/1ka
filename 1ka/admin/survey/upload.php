<?php

include_once('../../function.php');
include_once('../../vendor/autoload.php');
include_once('SurveyAdmin.php');


// preverimo dostop
$result = sisplet_query ("SELECT value FROM misc WHERE what='SurveyDostop'");
list ($SurveyDostop) = mysqli_fetch_row ($result);

if (($admin_type <= $SurveyDostop && $SurveyDostop<3) || ($SurveyDostop==3 && ($admin_type>=0))) {

	
	$s = new SurveyAdmin(1);
	
	$s->upload_skin();


} else {
	
	echo '<p>No access.</p>';
}

?>