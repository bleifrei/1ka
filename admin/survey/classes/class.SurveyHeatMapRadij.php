<?php
/** 
 *  November 2016
 * 
 * Pridobi podatke o klikanih tockah na slikah, za njihov prikaz v heatmap
 * 
 * @author Patrik Pucer
 */
class SurveyHeatMapRadij
{	
		
    function __construct() {
    }

    function ajax() {
        if(isset($_POST['heatmapRadij'])) {
			$radij = $_POST['heatmapRadij'];
			$anketa = $_POST['anketa'];
			$sprid = $_POST['sprid'];
			$heatmapId = 'heatmap'.$sprid;
			//$heatmapData = ['radij'=>$radij, 'sprid'=>$sprid];
			
			SurveyUserSession::Init($anketa);
			// Shranimo spremenjene nastavitve radija v bazo
			SurveyUserSession::saveData($radij, $heatmapId);
			//SurveyUserSession::saveData($heatmapData, 'heatmap');
			
        }
		exit();
    }
}