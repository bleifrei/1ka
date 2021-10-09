<?php
/** 
 *  Januar 2017
 * 
 * Shrani heatmap porocilo v obliki slike
 * 
 * @author Patrik Pucer
 */
class SurveyHeatMapImageSave
{	
		
    function __construct() {
    }

    function ajax() {
		
		global $site_url;
		global $site_path;
		
        if(isset($_POST['sprid'])) {
			$sprid = $_POST['sprid'];
			$heatmapId = 'heatmap'.$sprid;
			$img = $_POST['image'];
			
			define('UPLOAD_DIR', $site_path.'main/survey/uploads/');
 			
			$img = str_replace('data:image/png;base64,', '', $img);
			$img = str_replace(' ', '+', $img);
			$data = base64_decode($img);
			$file = UPLOAD_DIR . $heatmapId . '.png';
			$success = file_put_contents($file, $data);
			print $success ? $file : 'Unable to save the file.';
        }
		//exit();
    }
}