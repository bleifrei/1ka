<?php
/* Class skribi za nastavitve missingov,
 * najprej na nivoju sistema.
 * potem na nivoju ankete
 *
 * prav tako skrbi za missinge pri izpolnjevanju ankete in lovljenju podatkov, kakor tudi analizah
 *
 * v srv_anketa na anketi nastavimo missing_values_type = 1, kadar imamo missinge nastavljene na nivoju ankete
 *
 * # neodgovori (type = 1):
 * -1 - ni odgovoril
 * -2 - preskok (if)
 * -3 - prekinjeno
 * -4 - naknadno vprašanje
 *
 * # neopredeljeni odgovori (type = 2)
 * -99 Ne vem
 * -98 Zavrnil
 * -97 Neustrezno
 *
 * # ostali so uporabniško določeni (type = 3)
 * - 96 Drugo.....
 *
 *
 *
 * Created on 21.9.2010 (Gorazd Veselič
 */

class SurveyMissingValues
{
	private static $anketa = null;				# trenutna anketa
	private static $srv_old_missing = 1;		# ali imamo misinge na star način

	private static $sysNeodgovori = array();	# array z sistemskimi neodgovori
	private static $sysNeopredeljeni = array();	# array z sistemskimi neopredeljenimi
	private static $sysUserSet = array();		# array z sistemskimi uporabniško nastavljenimi
	private static $missing_values_type = 0;	# Nivo missingov: 0 - sistemski, 1 - uporabniško nastavljen

	private static $mySqlErrNo = null;			# ali imamo mysql napako

	/**
	 * konstruktor
	 *
	 * @param mixed $anketa
	 */
	function __construct ($anketa = 0) {

		if (self::$anketa == null) {

            if ((int)$anketa > 0) {
				self::$anketa = (int)$anketa;
			}
			elseif (isset ($_GET['anketa']) && (int)$_GET['anketa'] > 0) {
				self::$anketa = $_GET['anketa'];
			} 
            elseif (isset ($_POST['anketa']) && (int)$_POST['anketa'] > 0) {
				self::$anketa = $_POST['anketa'];
			}
            
	
			# polovimo nastavitve ankete če obstaja
			if (self::$anketa != null && (int)self::$anketa > 0) {
				SurveyInfo::getInstance()->SurveyInit(self::$anketa);
				SurveyInfo::getInstance()->resetSurveyData();
				self::$missing_values_type = SurveyInfo::getInstance()->getSurveyColumn('missing_values_type') == 1 ? 1 : 0;
				
				# če še nismo nastavili manjkajočih vrednosti jih prepišemo iz sistemskih
				if ((int)self::$missing_values_type == 0) {
					self :: SetUpSurvyeMissingValues(); 	
				}
			}
			self::Init();
		}
	}

	/** Inicializacija, prebere sistemske vrednoasti iz baze
	 *
	 */
	static function Init() {
		# če nimamo missingov dodamo sistemske
		$countSystem = "SELECT count(*) FROM srv_missing_values WHERE sid='".self::$anketa."'" ;
		list($cntMissing) = mysqli_fetch_row(sisplet_query($countSystem));
		if ((int)$cntMissing == 0) {
			self::useSystemMissingValues();
		}
		# preberemo sistemske nastavitve
		self::selectSysNeodgovori();
		self::selectSysNeopredeljeni();
		self::selectSysUserSet();
	}


	# AJAX funkcije
	/** Ajax handler
	 *
	 */
	public function ajax() {
		if ($_GET['a'] == 'sysMissingValuesChangeMode') {
			$_mode = $_POST['mode'];
			if ($_mode == 'normal') {
				self::displayNormalMode($_mode);
			}
			if ($_mode == 'edit') {
				self::sysMissingValuesChangeMode($_mode);
			}
			if ($_mode == 'new') {
				self::sysMissingValuesChangeMode($_mode);
			}
		}
		if ($_GET['a'] == 'sysMissingValuesSave') {
			self::SaveSystemFilters( $_POST['id'], $_POST['filter'], $_POST['text']);
			self::Init();
			$_mode = 'edit';
			self::sysMissingValuesChangeMode($_mode);
		}
		if ($_GET['a'] == 'sysMissingValuesAdd') {
			self::AddSystemFilters( $_POST['filter'], $_POST['text'], $_POST['filter']);
			self::Init();
			$_mode = 'edit';
			self::sysMissingValuesChangeMode($_mode);
		}
		if ($_GET['a'] == 'sysMissingValuesDelete') {
			self::DeleteSystemFilters($_POST['id']);
			self::Init();
			$_mode = 'edit';
			self::sysMissingValuesChangeMode($_mode);
		}
		if ($_GET['a'] == 'changeSurveyMissingSettings') {
			self::saveMisingValueTypeForSurvey();
			self::displayMissingForSurvey();
		}
		if ($_GET['a'] == 'useSystemMissingValues') {
			self::useSystemMissingValues();
			self::displayMissingForSurvey();
		}
		if ($_GET['a'] == 'saveSurveyMissingValue') {
			self::saveSurveyMissingValue();
			self::displayMissingForSurvey();
		}
		if ($_GET['a'] == 'srv_missing_confirm_delete') {
			self::deleteMissingForSurvey();
			self::displayMissingForSurvey();
		}
		if ($_GET['a'] == 'srv_missing_add_new') {
			self::displayAddMissingValue();
		}
		if ($_GET['a'] == 'srv_missing_confirm_add') {
			self::addSurveyFilters();
		}
		if ($_GET['a'] == 'srv_missing_display') {
			self::displayMissingForSurvey();
		}

	}

	/* polovi sistemske neodgovore,
	 * type = 1
	 */
	private static function selectSysNeodgovori() {
		self::$sysNeodgovori = array();
		$str_select = "SELECT filter,text FROM srv_sys_filters WHERE type = 1";

		$qry_select = sisplet_query($str_select);
		while ($row_select = mysqli_fetch_assoc($qry_select)) {
			self::$sysNeodgovori[$row_select['filter']] = $row_select['text'];
		}
	}

	/* polovi sistemske neopredeljene,
	 * type = 2
	 */
	private static function selectSysNeopredeljeni() {
		self::$sysNeopredeljeni = array();
		$str_select = "SELECT filter,text FROM srv_sys_filters WHERE type = 2";
		$qry_select = sisplet_query($str_select);
		while ($row_select = mysqli_fetch_assoc($qry_select)) {
			self::$sysNeopredeljeni[$row_select['filter']] = $row_select['text'];
		}
	}

	/* polovi sistemske uporabniško nastavljene,
	 * type = 3
	 */
	private static function selectSysUserSet() {
		self::$sysUserSet = array();
		$str_select = "SELECT filter,text FROM srv_sys_filters WHERE type = 3";
		$qry_select = sisplet_query($str_select);
		while ($row_select = mysqli_fetch_assoc($qry_select)) {
			self::$sysUserSet[$row_select['filter']] = $row_select['text'];
		}
	}

	/** polovimo missinge za anketo
	 * če obstaja nastavitev za anketo,  (TODO)
	 * če ne vzamemo privzete sistenmske misinge
	 *
	 *
	 * @var unknown_type
	 */
	private static $_systemFiltersByType = array();
	static function GetSystemFlterByType($type, $forceSystem = false) {
		global $lang;
		
		if (is_array($type)) {
			$type = (string)implode(',',$type);
			$_type = "type IN (".$type.")";
		} else {
			$_type = "type = '".$type."'";
		}

		if (isset(self::$_systemFiltersByType[$type])) {
			return self::$_systemFiltersByType[$type];
		} else {
			$result = array();
			
			# preverimo iz katere tabele lovimo lahko imamo na nivoju ankete ali na nivoju sistema
			if ((int)self::$missing_values_type == 1 && $forceSystem == false) {
				
				$stringSystemSetting_filters =  "SELECT value AS filter, text FROM srv_missing_values WHERE sid='".self::$anketa."' AND ".$_type." AND active = 1 ORDER BY type ASC, value ASC" ;
			} else {

				$stringSystemSetting_filters =  "SELECT filter, text FROM srv_sys_filters WHERE ".$_type." ORDER BY filter ASC";
			}
			$sqlSystemSetting_filters = sisplet_query($stringSystemSetting_filters );
			while ( $rowSystemSetting_filters = mysqli_fetch_assoc($sqlSystemSetting_filters) )
			{
				# naredimo prevode:
				$result[$rowSystemSetting_filters['filter']] = $lang['srv_mv_'.$rowSystemSetting_filters['text']] != '' ? $lang['srv_mv_'.$rowSystemSetting_filters['text']] : $rowSystemSetting_filters['text'];;
			}
			self::$_systemFiltersByType[$type] = $result;
			return self::$_systemFiltersByType[$type];
		}
	}

	# urejanje sistemskih missingov
	/* Prikaže osnovni okvir za urejanje missingov
	 *
	 */
	static public function SystemFilters() {
		global $lang;
		echo '<fieldset>';
		echo '<legend>' . $lang['srv_filtri'] . '</legend>';
		echo '<div class="nastavitveSpan1" style="height:auto; float:left;"><label>' . $lang['srv_filtri_text'] . ':</label></div>';
		echo '<div id="sys_missing_values" style="display:block; float:left; width:auto; margin:0px 0px 5px 5px; padding: 0px; 5px;">';
		self::displayNormalMode();
		echo '</div>';
		echo '</fieldset>';
	}

	/** Prikaže navaden način misingov - predogled
	 *
	 * @param $_mode
	 */
	private static function displayNormalMode($_mode = null) {
		global $lang;

		//echo '<a href="#" onClick="sysFilterEditMode(\'edit\'); return false;">';
		echo '<a href="#" onClick="sysMissingValuesChangeMode(\'edit\'); return false;">';
		echo '<span class="faicon edit small"></span>'
		.$lang['srv_settings_filter_edit_mode']. '</a>';
		echo '<div class="clr"></div>';
		echo '<div style="clear:both; padding:5px; height:1.2em">';
		echo '<div style="width:50px; float:left; text-align:left; padding-right:15px;">';
		echo '<i style="color:gray;text-align:right">'.$lang['srv_filter_vrednost'].'</i>';
		echo '</div>';
		echo '<div class="" style="width:250px; float:left; color: gray">';
		echo '<i style="color:gray;">'.$lang['srv_filter_variabla'].'</i>';
		echo '</div>';
		echo '</div>';
		# neodgovori
		if ( count(self::$sysNeodgovori) > 0 ) {
			foreach ( self::$sysNeodgovori as $key => $text) {
				echo '<div id="sysfilter_div_'.$key.'" style="clear:both; padding-bottom:0px; height:1.2em;">';
				echo '<div id="sysfilter_filter_'.$key.'" class="spr_sysFilter1" style="width:50px; float:left; text-align:left; padding-right:15px; text-align:right;">'.$key.'</div>';
				echo '<div id="sysfilter_text_'.$key.'" class="spr_sysFilter1" style="width:250px; float:left;">'.$text.'</div>';
				echo '</div>';
			}
		}
		# neopredeljeni
		if ( count(self::$sysNeopredeljeni) > 0 ) {
			foreach ( self::$sysNeopredeljeni as $key => $text) {
				echo '<div id="sysfilter_div_'.$key.'" style="clear:both; padding-bottom:0px; height:1.2em;">';
				echo '<div id="sysfilter_filter_'.$key.'" class="spr_sysFilter2" style="width:50px; float:left; text-align:left; padding-right:15px; text-align:right;">'.$key.'</div>';
				echo '<div id="sysfilter_text_'.$key.'" class="spr_sysFilter2" style="width:250px; float:left;">'.$text.'</div>';
				echo '</div>';
			}
		}
		# uporabniški
		if ( count(self::$sysUserSet) > 0 ) {
			foreach ( self::$sysUserSet as $key => $text) {
				echo '<div id="sysfilter_div_'.$key.'" style="clear:both; padding-bottom:0px; height:1.2em;">';
				echo '<div id="sysfilter_filter_'.$key.'" class="spr_sysFilter3" style="width:50px; float:left; text-align:left; padding-right:15px; text-align:right;">'.$key.'</div>';
				echo '<div id="sysfilter_text_'.$key.'" class="spr_sysFilter3" style="width:250px; float:left;">'.$text.'</div>';
				echo '</div>';
			}
		}
	}

	/** Prikaže edit mode za misinge
	 *
	 * @param unknown_type $mode
	 */
	private static function sysMissingValuesChangeMode($mode = '') {
		global $lang;
		echo '<a href="/" onClick="sysMissingValuesChangeMode(\'normal\'); return false;">';
		echo '<span class="faicon mapca anketa small"></span>'
		.$lang['srv_settings_filter_view_mode']. '</a>';

		echo '<div style="clear:both; padding:5px; height:1.2em">';
		echo '<div style="width:50px; float:left; text-align:left; padding-right:15px;">';
		echo '<i style="color:gray;">'.$lang['srv_filter_vrednost'].'</i>';
		echo '</div>';
		echo '<div class="" style="width:250px; float:left; color: gray">';
		echo '<i style="color:gray;">'.$lang['srv_filter_variabla'].'</i>';
		echo '</div>';
		echo '</div>';
		# neodgovori
		if ( count(self::$sysNeodgovori) > 0 ) {
			foreach ( self::$sysNeodgovori as $key => $text) {
				echo '<div id="sysMissingValues_div_'.$key.'" style="clear:both; padding:5px; height:1.2em">';
				echo ' <div id="sysMissingValues_filter_'.$key.'" class="" style="width:50px; float:left; text-align:left; padding-right:15px; text-align:right;">';
				echo ' <input id="sysMissingValues_filter_input_'.$key.'" type="text" value="'.$key.'" title="'.$key.'" size="6" maxlength="6" onBlur="sysMissingValuesSave('.$key.')" autocomplete="off">';
				echo ' </div>';
				echo ' <div id="sysMissingValues_text_'.$key.'" class="" style="width:250px; float:left;">';
				echo ' <input id="sysMissingValues_text_input_'.$key.'" type="text" value="'.$text.'" title="'.$text.'" size="45" maxlength="100" onBlur="sysMissingValuesSave('.$key.')" autocomplete="off">';
				echo ' </div>';
				echo '</div>';
			}
		}
		# neopredeljeni
		if ( count(self::$sysNeopredeljeni) > 0 ) {
			foreach ( self::$sysNeopredeljeni as $key => $text) {
				echo '<div id="sysMissingValues_div_'.$key.'" style="clear:both; padding:5px; height:1.2em">';
				echo ' <div id="sysMissingValues_filter_'.$key.'" class="" style="width:50px; float:left; text-align:left; padding-right:15px; text-align:right;">';
				echo ' <input id="sysMissingValues_filter_input_'.$key.'" type="text" value="'.$key.'" title="'.$key.'" size="6" maxlength="6" onBlur="sysMissingValuesSave('.$key.')" autocomplete="off">';
				echo ' </div>';
				echo ' <div id="sysMissingValues_text_'.$key.'" class="" style="width:250px; float:left;">';
				echo ' <input id="sysMissingValues_text_input_'.$key.'" type="text" value="'.$text.'" title="'.$text.'" size="45" maxlength="100" onBlur="sysMissingValuesSave('.$key.')" autocomplete="off">';
				echo ' </div>';
				echo '</div>';
			}
		}
		# User defined
		if ( count(self::$sysUserSet) > 0 ) {
			foreach ( self::$sysUserSet as $key => $text) {
				echo '<div id="sysMissingValues_div_'.$key.'" style="clear:both; padding:5px; height:1.2em">';
				echo ' <div id="sysMissingValues_filter_'.$key.'" style="width:50px; float:left; text-align:left; padding-right:15px; text-align:right;">';
				echo ' <input id="sysMissingValues_filter_input_'.$key.'" type="text" value="'.$key.'" title="'.$key.'" size="6" maxlength="6"  onBlur="sysMissingValuesSave('.$key.')" autocomplete="off">';
				echo ' </div>';
				echo ' <div id="sysMissingValues_text_'.$key.'" class="" style="width:250px; float:left;">';
				echo ' <input id="sysMissingValues_text_input_'.$key.'" type="text" value="'.$text.'" title="'.$text.'" size="45" maxlength="100" onBlur="sysMissingValuesSave('.$key.')" autocomplete="off">';
				echo ' </div>';
				echo '<div id="sysMissingValues_edit_'.$key.'" class="" style="width:100px; float:left;">';
				echo '<a href="#" onClick="sysMissingValuesDelete('.$key.'); return false;">';
				echo '<img id="sysMissingValues_delete_'.$key.'" src="img_0/delete_red.png" alt="'.$lang['srv_filtri_izbrisi_filter'].'" style="vertical-align:text-bottom " />';
				echo '</a>';
				echo '</div>';
				echo '</div>';
			}
		}
		echo '<div id="sysMissingValues_new" style="padding-top:10px;">';
		if ($mode == 'new')
		{
			echo '<div id="sysMissingValues_div_add" style="clear:both; padding:5px; height:1.2em">';
			echo '<div id="sysMissingValues_filter_add" class="" style="width:50px; float:left; text-align:left; padding-right:15px;">';
			echo '<input id="sysMissingValues_filter_input_add" type="text" value="'.$filter['filter'].'" title="'.$filter['filter'].'" size="6" maxlength="6" autocomplete="off">';
			echo '</div>';
			echo '<div id="sysMissingValues_text_add" class="" style="width:250px; float:left;">';
			echo  '<input id="sysMissingValues_text_input_add" type="text" value="'.$filter['text'].'" title="'.$filter['text'].'" size="45" maxlength="100" autocomplete="off">';
			echo '</div>';
			echo '<div class="" style="width:50px; float:left;">';
			echo '<a href="#" onClick="sysMissingValuesAdd(); return false;"><img id="sysMissingValues_add_img" src="icons/icons/accept.png" alt="'.$lang['srv_novfilter'].'" style="vertical-align:text-bottom " /></a>';
			echo '</div>';
		} else {
			echo '<a href="#" onClick="sysMissingValuesChangeMode(\'new\'); return false;"><span class="faicon add small"></span> '.$lang['srv_novfilter'].'</a>';
		}
		echo '</div>';

		// pohendlamo error: duplicate
		if (self::$mySqlErrNo == 1062)
		{
			echo '<div id="error" class="red">';
			echo $lang['srv_duplicateEntry'];
			echo '</div>';
		}
	}

	/** Doda sistemski missing
	 *
	 * @param $filter
	 * @param $text
	 * @param $fid
	 */
	static function AddSystemFilters( $filter, $text, $fid) {
		global $global_user_id;
		self::$mySqlErrNo = null;
		$insertString = "INSERT INTO srv_sys_filters (fid,filter,text,uid,type) ".
			"VALUES ('".$fid."', '".$filter."', '".$text."', '".$global_user_id."', '3');";
		$mySqlResult = sisplet_query($insertString);
		self::$mySqlErrNo = mysqli_errno($GLOBALS['connect_db']);
	}

	/** Izbriše  sistemski missing
	 *
	 * @param $id
	 */
	static function DeleteSystemFilters( $id) {
		self::$mySqlErrNo = null;
		$deleteString = "DELETE FROM srv_sys_filters WHERE filter = '".$id."' AND type = '3'";
		$mySqlResult = sisplet_query($deleteString);
		self::$mySqlErrNo = mysqli_errno($GLOBALS['connect_db']);
	}

	/** Shrani sistemski missing
	 *
	 * @param $id
	 * @param $filter
	 * @param $text
	 */
	static function SaveSystemFilters($id,$filter,$text) {
		self::$mySqlErrNo = null;
		$updateString = "UPDATE srv_sys_filters " .
						"SET filter = '".$filter."', text = '".$text."' ".
						"WHERE filter = '".$id."'";
		$mySqlResult = sisplet_query($updateString);
		self::$mySqlErrNo = mysqli_errno($GLOBALS['connect_db']);
	}

	/** Vrne manjkajoče vrednosti za anketo
	 * # če ni nastavljeno na nivoju ankete vrne sistemske nastavitve
	 *
	 */
	static public function GetUnsetValuesForSurvey($type = array(2,3)) {
		# TODO preverit ali je na nivoju ankete če ne na nivoju sistema
		return self::GetSystemFlterByType($type);
	}

	/** Vrne neodgovore za anketo
	 * # če ni nastavljeno na nivoju ankete vrne sistemske nastavitve
	 *
	 */
	static public function GetMissingValuesForSurvey($type = array(1)) {
		# TODO preverit ali je na nivoju ankete če ne na nivoju sistema
		return self::GetSystemFlterByType($type);
	}
	static public function GetSurveyMissingValues($anketa_id = null) {
		if ($anketa_id == null) {
			
			$anketa_id = self::$anketa;
		}
		$result = array();

		if ($anketa_id != null && $anketa_id > 0) {
	
			$stringSurveyMissingValues =  "SELECT * FROM srv_missing_values WHERE sid ='".$anketa_id ."' AND active = '1' ORDER BY type ASC, systemValue DESC,value ASC";
			$sqlSurveyMissingValues = sisplet_query($stringSurveyMissingValues);

			while ( $row = mysqli_fetch_assoc($sqlSurveyMissingValues) ) {
				$result[$row['type']][] = array('value'=>$row['value'],'text'=> $row['text'],'defSysVal'=>$row['systemValue']);
			}
		}
		return $result;
		
	}

	static public function displayMissingForSurvey() {
		global $lang;
		
		self::displayLeftNavigation('start');
		
		$si_mv = SurveyInfo::getInstance()->getSurveyColumn('missing_values_type') == 1 ? '1' : '0';

		echo '<fieldset>';
		echo '<legend>'.$lang['srv_survey_missing_title'].':</legend>';
		$_survey_missing_values = self::GetSurveyMissingValues();
		
		if (self::$mySqlErrNo == '1062') {
			echo '<br/><span class="red">'.$lang['srv_survey_missing_error1'].'</span>';
		}
		
		if ( count($_survey_missing_values) > 0 ) {
			echo '<table class="mv_tbl">';
			echo '<tr>';
			
			echo '<th style="width:50px;">';
			echo '  <i style="color:gray;text-align:right">'.$lang['srv_filter_vrednost'].'</i>';
			echo '</th>';
			echo '<th style="width:250px;">';
			echo '  <i style="color:gray;">'.$lang['srv_label'].'</i>';
			echo '</th>';
			echo '<th style="width:20px;">';
			echo '  <i style="color:gray;text-align:right">'.'</i>';
			echo '</th>';
			echo '<th>&nbsp;</th>';
			echo '</tr>';
			foreach ( $_survey_missing_values as $type => $type_missing_values) {
				foreach ($type_missing_values AS $type_missing_value) {
					$key = $type_missing_value['value'];
					$text = $type_missing_value['text'];
					$sysValue = $type_missing_value['defSysVal'];
					echo '<tr class="spr_sysFilter'.$type.'">';
					
					echo '<td>';
					echo '<input name="mv_value_input" id="mv_value_'.$type.'_'.$key.'" type="text" value="'.$key.'" class="mv_value_input">';
					echo '</td>';
					echo '<td>';
					echo '<input name="mv_text_input" id="mv_text_'.$type.'_'.$key.'" type="text" value="'.$text.'" class="mv_text_input">';
					echo '</td>';
					echo '<td class="anl_ac">';
					if ($sysValue != null) {
						echo '['.$sysValue.']';
					}
					echo '</td>';
					echo '<td>';
					echo '&nbsp;<span class="faicon remove smaller orange pointer" name="mv_delete_img" id="mv_img_'.$type.'_'.$key.'" title="'.$lang['srv_filtri_izbrisi_filter'].'"/>';
					echo '</td>';
					echo '</tr>';
				}
			}
			echo '<tr class="spr_sysFilter3">';
			echo '<td>&nbsp;</td>';
			echo '<td>&nbsp;</td>';
			echo '<td>';
			echo '&nbsp;<span name="mv_add_img" id="mv_add_img" class="faicon add icon-blue smaller pointer"></span>';
			echo '</td>';
			echo '</tr>';
			echo '</table>';	
		}		
		if ($si_mv == '0') {
			echo '<span class="mv_link_disabled">'.$lang['srv_survey_missing_default'].'</span><br/>';
		} else {
			echo '<span id="link_use_sistem_mv" onclick="useSystemMissingValues(); return false;" class="as_link">'.$lang['srv_survey_missing_default'].'</span><br/>';
		}
		echo '</div>';
		
		echo '</fieldset>';
		echo '<br class="clr" />';
		echo '</div>';
		self::displayLeftNavigation();
	}
	
	/* shranimo tip missingov za anketo
	 * 
	 */
	static private function saveMisingValueTypeForSurvey() {
		if (self::$anketa) {
			$missing_values_type = (isset($_POST['missing_values_type']) && $_POST['missing_values_type'] == '1') ? '1' : '0';
			$updateString = "UPDATE srv_anketa SET missing_values_type = '".$missing_values_type."' WHERE id = '".self::$anketa."'";
			$mySqlResult = sisplet_query($updateString);
		}
		#uprejtamo timestamp
		Common::updateEditStamp();
		
		SurveyInfo::getInstance()->resetSurveyData();
	} 
	/* Na nivoju ankete nastavimo enake majkajoče vrednosti kot so sistemske 
	 * 
	 */
	static private function useSystemMissingValues() {
		if (self::$anketa) {
			# najprej pobrišemo vse missinge ki so nastavljeni za obstoječo anketo
			$deleteString = "DELETE FROM srv_missing_values WHERE sid = '".self::$anketa."'";
			sisplet_query($deleteString);
			
			# nato prekopiramo sistemske missinge v anketo
			$sf_1 = self::GetSystemFlterByType(array(1),true);
			$sf_2 = self::GetSystemFlterByType(array(2),true);
			$sf_3 = self::GetSystemFlterByType(array(3),true);
			if ( ( count($sf_1) + count($sf_2)+ count($sf_3) ) > 0) {

				$insertString = "INSERT INTO srv_missing_values (sid,type,value,text,active,systemValue) VALUES ";
			
				$prefix = '';
				foreach ($sf_1 AS $key => $value) {
					$insertString.= $prefix."( '".self::$anketa."', '1', '$key', '".addslashes($value)."', '1','$key')";
					$prefix = ', ';
				}
				foreach ($sf_2 AS $key => $value) {
					$insertString.= $prefix."( '".self::$anketa."', '2', '$key', '".addslashes($value)."', '1','$key')";
					$prefix = ', ';
				}
				foreach ($sf_3 AS $key => $value) {
					$insertString.= $prefix."( '".self::$anketa."', '3', '$key', '".addslashes($value)."', '1','$key')";
					$prefix = ', ';
				}

				$mySqlResult = sisplet_query($insertString);
				self::$mySqlErrNo = mysqli_errno($GLOBALS['connect_db']);
			}
			#uprejtamo timestamp
			Common::updateEditStamp();
		}

	}

	private static function saveSurveyMissingValue() {
		list($_mv, $_what, $_type, $_old_value) = explode('_',$_POST['el_id']);
		$new_value = str_replace('_','',$_POST['new_value']);
		if ( isset($_type) && $_type != '' &&
			 ( $_what == 'text' || $_what == 'value' ) &&
			 isset($_old_value) && $_old_value != '' && is_numeric($_old_value) &&
			 isset($new_value) && $new_value != '' && is_numeric($new_value) &&
			 isset(self::$anketa) && self::$anketa > 0) {
			 	$updateString = "UPDATE srv_missing_values " .
						"SET $_what = '".$new_value."' ".
						"WHERE sid = '".self::$anketa."' AND type='".$_type."' AND value = '".$_old_value."'";
			$mySqlResult = sisplet_query($updateString);
			self::$mySqlErrNo = mysqli_errno($GLOBALS['connect_db']);
			
			# updejtamo še srv_vrednost, če smo slučajno imeli že nastavljen missing
			$selStr = "SELECT v.id FROM srv_vrednost v, srv_spremenljivka s, srv_grupa g WHERE v.spr_id=s.id AND s.gru_id=g.id AND g.ank_id='".self::$anketa."' AND v.other='$_old_value'";
			list($vre_id) = mysqli_fetch_row(sisplet_query($selStr));
			if ((int)$vre_id > 0) {
				$updateVreString = "UPDATE srv_vrednost SET variable = '$new_value', other='$new_value' WHERE id ='$vre_id'";
				$sqlVreResult = sisplet_query($updateVreString);
				self::$mySqlErrNo = mysqli_errno($GLOBALS['connect_db']);
			}
			#uprejtamo timestamp
			Common::updateEditStamp();
				
		 }
	}

	private static function deleteMissingForSurvey() {
		list($_mv, $_what, $_type, $_value) = explode('_',$_POST['delete_id']);
		if ( isset($_type) && $_type != '' &&
			 $_what == 'img'  &&
			 isset($_value) && $_value != '' &&
			 isset(self::$anketa) && self::$anketa > 0) {
			 	
			 	$deleteString = "DELETE FROM srv_missing_values " .
					"WHERE sid = '".self::$anketa."' AND type='".$_type."' AND value = '".$_value."'";
				$mySqlResult = sisplet_query($deleteString);
				#uprejtamo timestamp
				Common::updateEditStamp();
				
		}
	}

	private static function SetUpSurvyeMissingValues() {
		self::useSystemMissingValues();
		$updateString = "UPDATE srv_anketa SET missing_values_type = '1' WHERE id = '".self::$anketa."'";
		$mySqlResult = sisplet_query($updateString);
		SurveyInfo::getInstance()->resetSurveyData();
		
		#uprejtamo timestamp
		Common::updateEditStamp();
		
	}
	
	private function displayAddMissingValue() {
		global $lang;
		
		echo '<div id="mv_add_quick">';
		echo '<span>'.$lang['srv_filter_add_note'].'</span>';
		echo '<br /><br />';
		echo '<table class="mv_tbl">';
		echo '<tr>';
		echo '<th style="width:50px;">';
		echo '  <i style="color:gray;text-align:right">'.$lang['srv_filter_vrednost'].'</i>';
		echo '</th>';
		echo '<th style="width:250px;">';
		echo '  <i style="color:gray;">'.$lang['srv_filter_variabla'].'</i>';
		echo '</th>';
		echo '<th>&nbsp;</th>';
		echo '</tr>';
	
		echo '<tr>';
		echo '<th style="width:50px;">';
		echo '<input id="mv_add_filter" type="text" size="6" maxlength="6" autocomplete="off">';
		echo '</th>';
		echo '<th style="width:250px;">';
		echo '<input id="mv_add_text" type="text" size="45" maxlength="100" autocomplete="off">';
		echo '</th>';
		echo '</tr>';
		echo '</table>';
		echo '<br class="clr" />';
		echo '<span class="floatRight spaceRight buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="addSurveyMissingValueConfirm(); return false;"><span>'.$lang['add'].'</span></a></span>';
		echo '<span class="floatRight spaceRight buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="addSurveyMissingValueCancel(); return false;"><span>'.$lang['srv_cancel'].'</span></a></span>';
		echo '<br class="clr" />';
		echo '</div>';
	}
	
	private function addSurveyFilters() {
		if (isset($_POST['mv_add_filter']) && trim($_POST['mv_add_filter']) != ""
			&& isset($_POST['mv_add_text']) && trim($_POST['mv_add_text']) != "") {
			$insertString = "INSERT INTO srv_missing_values (sid,type,value,text,active) VALUES ( '".self::$anketa."', '3', '".$_POST['mv_add_filter']."', '".addslashes($_POST['mv_add_text'])."', '1') ON DUPLICATE KEY UPDATE value='".$_POST['mv_add_filter']."', text='".addslashes($_POST['mv_add_text'])."', active='1'";
						
			$mySqlResult = sisplet_query($insertString);
			
			if (!$mySqlResult) {
				echo 'Napaka! ';
				return;
			} else {
				#updejtamo timestamp
				Common::updateEditStamp();
				echo 'true';
				return; 
			}
		} else {
			echo 'Napaka! Polja ne smejo biti prazna';
			return;
			
		}
	}
	
	private static function displayLeftNavigation($what=null) {
		if ($_REQUEST['a'] != 'missing') {
			if ($what == 'start') {
				$sa=new SurveyAdmin(self::$anketa);
				echo '<span class="floatLeft">';
				echo '<div id="globalSetingsLinks" class="baseSettings">';
				$sa->showGlobalSettingsLinks();
				echo '</div>';
				echo '<br class="clr"/><br/>';
				echo '<div id="globalSetingsLinks" class="aditionalSettings">';
				$sa->showAdditionalSettingsLinks();
				echo '</div>';
				echo '<br class="clr"/>';
				echo '</span>';
				echo '<div id="globalSetingsList" >';
				
			} else {
				echo '</div>';
			}
		}
	}
}
?>