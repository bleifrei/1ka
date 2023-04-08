<?php
class SurveyAnalysisHelper {
	
	static private $instance;
	static private $anketa;
	static private $db_table = '';
	
	/**
	 * Poskrbimo za samo eno instanco
	 */
	static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new SurveyAnalysisHelper();
		}
		return self::$instance;
	}
	
	/**
	 * Inicializacija
	 *
	 * @param int $anketa
	 */
	function Init( $anketa = null )
	{
		if ($anketa) {
			self::$anketa = $anketa;
				
			SurveyInfo::getInstance()->SurveyInit(self::$anketa);
			
            self::$db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();		
		}
	}
	
	function addCustomReportElement($type, $sub_type, $spr1, $spr2=''){
		global $lang;
		global $global_user_id;
	
		if($_GET['m'] != 'analysis_creport' && $_GET['t'] != 'custom_report'){
			$creportProfile = SurveyUserSetting :: getInstance()->getSettings('default_creport_profile');
			$creportProfile = isset($creportProfile) ? $creportProfile : 0;
			
			$creportAuthor = SurveyUserSetting :: getInstance()->getSettings('default_creport_author');
			$creportAuthor = isset($creportAuthor) ? $creportAuthor : $global_user_id;
	
			$sql = sisplet_query("SELECT id FROM srv_custom_report WHERE ank_id='".self::$anketa."' AND usr_id='".$creportAuthor."' AND type='$type' AND sub_type='$sub_type' AND spr1='$spr1' AND spr2='$spr2' AND profile='$creportProfile'");
			$insert = (mysqli_num_rows($sql)) ? 0 : 1;
			$id = $type.'-'.$sub_type.'-'.$spr1.'-'.$spr2;
			// Samo zvezdica (sums, grafi, freq...)
			if($type < 5)
				echo '<a href="#" title="'.($insert == 0 ? $lang['srv_custom_report_inserted_title'] : $lang['srv_custom_report_insert_title']).'" onClick="addCustomReportElement(\''.$type.'\', \''.$sub_type.'\', \''.$spr1.'\', \''.$spr2.'\', 0); return false;"><span style="margin-left: 3px;" id="'.$id.'" class="faicon pointer '.($insert == 0 ? ' star_on' : ' star_off').'"></span></a>';
			// Zvezdica s textom
			else{
				echo '<div class="custom_report_include">';
					
				echo '<a href="#" title="'.($insert == 0 ? $lang['srv_custom_report_inserted_title'] : $lang['srv_custom_report_insert_title']).'" onClick="addCustomReportElement(\''.$type.'\', \''.$sub_type.'\', \''.$spr1.'\', \''.$spr2.'\', 1); return false;">';
				echo '<span id="'.$id.'" class="faicon pointer '.($insert == 0 ? ' star_on' : ' star_off').'"></span>';
				echo '<span id="'.$id.'_insert" '.($insert == 0 ? ' style="display:none;" ' : '').'> '.$lang['srv_custom_report_insert'].'</span>';
				echo '<span id="'.$id.'_inserted" '.($insert == 0 ? '' : ' style="display:none;" ').'> '.$lang['srv_custom_report_inserted'].'</span>';
				echo '</a>';
	
				echo '</div>';
			}
		}
	}
	
	function displayMissingLegend(){
		global $lang;	
		
		echo '<div id="bottom_data_legend" class="floatLeft">';
		echo '<div>';
		echo '<div id="bdld1" class="as_link strong" onclick="$(\'#bottom_data_legend_detail, #bdld1, #bdld2\').toggle();"><span class="faicon plus"></span> '.$lang['srv_bottom_data_legend_note'].'</div>';
		echo '<div id="bdld2" class="as_link strong" style="display:none" onclick="$(\'#bottom_data_legend_detail, #bdld1, #bdld2\').toggle();"><span class="faicon minus"></span> '.$lang['srv_bottom_data_legend_note'].'</div>';
		echo '</div>';
		echo '<div id="bottom_data_legend_detail" style="display:none">';
		echo '<ul>';
		echo '<li>'.$lang['srv_bottom_data_legend_note_li1'].'</li>';
		echo '<li>'.$lang['srv_bottom_data_legend_note_li2'].'</li>';
		echo '<li>'.$lang['srv_bottom_data_legend_note_li3'].'</li>';
		echo '<li>'.$lang['srv_bottom_data_legend_note_li4'].'</li>';
		echo '<li>'.$lang['srv_bottom_data_legend_note_li5'].'</li>';
		echo '<li>'.$lang['srv_bottom_data_legend_note_li0'].'</li>';
		echo '</ul>';
		echo '</div>';
		echo '</div>';
	}
	
	function displayStatusLegend(){
		global $lang;
		
		echo '<div id="bottom_data_legend" class="floatLeft bg_blue">';
		echo '<div>';
		echo '<div id="bdlds1" class="as_link strong" onclick="$(\'#bottom_data_legend_detail_status, #bdlds1, #bdlds2\').toggle();"><span class="faicon plus"></span> '.$lang['srv_bottom_data_legend_status_note'].'</div>';
		echo '<div id="bdlds2" class="as_link strong" style="display:none" onclick="$(\'#bottom_data_legend_detail_status, #bdlds1, #bdlds2\').toggle();"><span class="faicon minus"></span> '.$lang['srv_bottom_data_legend_status_note'].'</div>';
		echo '</div>';
		echo '<div id="bottom_data_legend_detail_status" style="display:none">';
		echo '<ul>';
		for ($i = 0; $i <= 6; $i++) {
			echo '<li>'.$i.' - '.$lang['srv_userstatus_'.$i].'</li>';
		}
		echo '<li>'.$lang['srv_bottom_data_legend_note_li0'].'</li>';
		echo '</ul>';
		echo '</div>';
		echo '</div>';
	}
	
	function displayTestLegend(){
		global $lang;
		
		echo '<div id="bottom_data_legend" class="floatLeft test">';
		echo '<div>';
		echo '<div id="bdldt1" class="as_link strong" onclick="$(\'#bottom_data_legend_detail_test, #bdldt1, #bdldt2\').toggle();"><span class="faicon plus"></span> '.$lang['srv_bottom_data_legend_test_note'].'</div>';
		echo '<div id="bdldt2" class="as_link strong" style="display:none" onclick="$(\'#bottom_data_legend_detail_test, #bdldt1, #bdldt2\').toggle();"><span class="faicon minus"></span> '.$lang['srv_bottom_data_legend_test_note'].'</div>';
		echo '</div>';
		echo '<div id="bottom_data_legend_detail_test" style="display:none">';
		echo '<ul>';
		echo '<li>0 - '.$lang['srv_bottom_data_legend_test_note_li0'].'</li>';
		echo '<li>1 - '.$lang['srv_bottom_data_legend_test_note_li1'].'</li>';
		echo '<li>2 - '.$lang['srv_bottom_data_legend_test_note_li2'].'</li>';
		echo '</ul>';
		echo '</div>';
		echo '</div>';
	}
}