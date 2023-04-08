<?php
/** 
 *  November 2016
 * 
 * Pridobi podatke o klikanih tockah na slikah, za njihov prikaz v heatmap
 * 
 * @author Patrik Pucer
 */
 
include_once('../survey/definition.php');
 
class SurveyHeatMap
{	
    function __construct() {		
    }
	

    function ajax() {
        if(isset($_POST['heatmap_data'])) {
			$heatmap_data = $_POST['heatmap_data'];
			
			$dataPointValue = array();
			$data = array();
			
			#nov del - za pobiranje podatkov iz baze
			$str_query = "SELECT sdm.lat, sdm.lng, sdm.usr_id "
                        . "FROM srv_data_heatmap AS sdm JOIN srv_user AS u ON sdm.usr_id = u.id WHERE u.deleted = '0' AND sdm.spr_id = ". $heatmap_data['spr_id'];
			
			if($heatmap_data['usr_id'] != '-1'){
				$str_query.=" AND usr_id = ". $heatmap_data['usr_id'];
			}		

			if($heatmap_data['loop_id'] != '0' && $heatmap_data['loop_id'] != '-1'){
				$str_query.=" AND loop_id = ". $heatmap_data['loop_id'];				
			}
			
			
			$heatmap_data2 = array();
			$data = sisplet_query($str_query);

			while ($row1 = mysqli_fetch_array($data)) {
				$heatmap_data2[] = $row1;
			}
			
			//error_log(json_encode($heatmap_data));
			//error_log(json_encode($heatmap_data2));

			#nov del - za pobiranje podatkov iz baze - konec			

			
			//************* pridobitev stevila podatkov v object-u
			$i = 0;
			foreach($heatmap_data2 as $key => $value) {
				$i++;
			}
			$heatmap_data_size = $i - 3;
			//error_log("heatmap_data_size ".$heatmap_data_size);
			//************* pridobitev stevila podatkov v object-u - konec
			
			
			for ($i = 0; $i<$heatmap_data_size; $i++){
				//error_log('| '.$i.'. lat: '.$heatmap_data2[$i]['lat'].' lng: '.$heatmap_data2[$i]['lng'].' ');
				$lat = $heatmap_data2[$i]['lat'];
				$lng = $heatmap_data2[$i]['lng'];
				$data = sisplet_query("SELECT COUNT(lat) as pointValue from srv_data_heatmap WHERE lat = $lat AND lng = $lng ");
				$rowPointValue = mysqli_fetch_assoc($data);
				$dataPointValue[$i] = $rowPointValue['pointValue'];				
				//error_log( '|'.$i.'. stevilo tock s temi koordinatami: '.$dataPointValue[$i]);
				$heatmap_data2[$i]['text'] = $dataPointValue[$i];
			}			
			echo json_encode($heatmap_data2);

        }
		exit();
    }
}