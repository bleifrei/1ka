<?php
/** 
 *  December 2016
 * 
 * Pridobi kodo za prikazovanje gumbov za izvoz
 * 
 * @author Patrik Pucer
 */
class SurveyHeatMapExportIcons
{	
		
    function SurveyHeatMapExportIcons() {
    }

    function ajax() {
        //if(isset($_POST['getheatmapexporticons'])) {
			$anketa = $_POST['anketa'];
			$sprid = $_POST['sprid'];

			echo SurveyAnalysis::displayExportIcons4Heatmap($sprid, $anketa);
        //}
		exit();
    }
}