<?php
ob_start();
	include_once('../../settings.php');
	include_once($site_path.'function.php');
	include_once('../../vendor/autoload.php');

	global $site_path, $admin_type, $lang;
	$anketa = $_GET['anketa'];
	$aid = $_GET['aid']; // arhiv id

        session_start();
        checkArchiveAccessSessionValues($aid, $anketa);
        
	if (isset($anketa) && isset($aid)) {
		SurveyAnalysisArchive :: Init($anketa);
		SurveyAnalysisArchive :: ViewArchive($aid);
	} else {
		echo 'Error!';
	}
        
        /**
         * Just for acces with password
         */
        function checkArchiveAccessSessionValues($aid, $anketa){
            if(isset($_POST['archive_access_pass'])){
                # polovimo podatke o arhivu
                $s = sisplet_query("SELECT access_password FROM srv_analysis_archive WHERE id='".$aid."' AND sid='".$anketa."'");		
                if($s){
                    $row = mysqli_fetch_assoc($s);
                    if($row['access_password'] == $_POST['archive_access_pass'])
                        $_SESSION['archive_access'][$aid] = '1';
                    else
                        $_SESSION['archive_access'][$aid] = '0';
                }
            }
        }
ob_flush();
?>