<?php
/** 
 *  November 2016
 * 
 * Pridobi podatke o radiju za heatmap
 * 
 * @author Patrik Pucer
 */
class SurveyGetHeatMapRadij
{	
		
    function __construct() {
    }

    function ajax() {
		
        if(isset($_POST['sprid'])) {
			$anketa = $_POST['anketa'];
			$sprid = $_POST['sprid'];
			$heatmapId = 'heatmap'.$sprid;
			
			SurveyUserSession::Init($anketa);
			// Shranimo spremenjene nastavitve radija v bazo
			$radij = SurveyUserSession::getData($heatmapId);
			echo $radij;		
        }
		exit();
    }
}