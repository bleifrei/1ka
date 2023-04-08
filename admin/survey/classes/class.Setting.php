<?php
// star class. Nasomesti ga class.SurveyMissingValues

/*
 * Created on 20.5.2009
 *
 */

class Setting
{
	static private $instance;

	// nastavitve sistema
	static private $sysSetting = array();

	// nastavitve ankete
	static private $srvSetting = array();

	// nastavitve uporabnika
	static private $usrSetting = array();

	// userId
	static private $uid = null;

	static private $mySqlResult = null;
	static private $mySqlErrNo = null;

	// konstrutor
	protected function __construct() {}
	// kloniranje
	final private function __clone() {}

	/** Poskrbimo za samo eno instanco razreda
	 *
	 */
	static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new Setting();
		}
		return self::$instance;
	}

	/** inicializacija */

	static function Init( $_userId = null )
	{
		if ( $_userId )
			{ self::$uid = $_userId; }
		self::refreshData();
	}


	// nastavimo nov User Id
	static function setUID( $_userId )
	{
		if ( $_userId )
			{ self::$uid = $_userId; }
		$this->refreshData();
	}

	// osvežimo podatke nastavitev
	static function refreshData()
	{
		// nastavitve sistema
		self::getSystemSetting();
		// nastavitve ankete

		// nastavitve uporabika
	}

	static function getSystemSetting()
	{
		//$stringSystemSetting =  "SELECT * FROM srv_settng "

	}

	/** @desc: polovi sistemske filtre */
	private static $_systemFilters = array();
	private static $_systemFiltersArray;
	static function GetSystemFilters($filter=null)
	{
		if (!$filter) {
			if (isset(self::$_systemFiltersArray)) {
				return self::$_systemFiltersArray;
			}
			else {
				$result = array();
				$stringSystemSetting_filters =  "SELECT * FROM srv_sys_filters ORDER BY type, filter ASC";
				$sqlSystemSetting_filters = sisplet_query($stringSystemSetting_filters );
			    while ( $rowSystemSetting_filters = mysqli_fetch_assoc($sqlSystemSetting_filters) )
			    {
			    	$result[] = $rowSystemSetting_filters;
			    }
			    	self::$_systemFiltersArray = $result;
				return self::$_systemFiltersArray;
			}
		}
		else {
			if (isset(self::$_systemFilters[$filter]))
				return self::$_systemFilters[$filter];
			else
			{
				$stringSystemSetting_filters =  "SELECT * FROM srv_sys_filters where filter = '".$filter."'";
				$sqlSystemSetting_filters = sisplet_query($stringSystemSetting_filters );
			    $result = mysqli_fetch_assoc($sqlSystemSetting_filters);
				self::$_systemFilters[$filter] = $result;
				return $result;
			}
		}
	}

	// vrne array z sistemskimi filtri
	private static $_systemFiltersValues;
	static function GetSystemFiltersValues()
	{
		if (isset(self::$_systemFiltersValues)) {
			return self::$_systemFiltersValues;
		}
		else {
			$result = array();
			$stringSystemSetting_filters =  "SELECT filter FROM srv_sys_filters ORDER BY type, filter ASC";
			$sqlSystemSetting_filters = sisplet_query($stringSystemSetting_filters );
		    while ( $rowSystemSetting_filters = mysqli_fetch_assoc($sqlSystemSetting_filters) )
		    {
		    	$result[$rowSystemSetting_filters['filter']] = $rowSystemSetting_filters['filter'];
		    }
		    self::$_systemFiltersValues = $result;
			return self::$_systemFiltersValues;
		}
	}

	private static $_systemFiltersByType = array();
	static function GetSystemFlterByType($type)
	{
		if (isset(self::$_systemFiltersByType[$type]))
			return self::$_systemFiltersByType[$type];
		else {
			$result = array();
			$stringSystemSetting_filters =  "SELECT * FROM srv_sys_filters WHERE type = '".$type."' ORDER BY filter ASC";
			$sqlSystemSetting_filters = sisplet_query($stringSystemSetting_filters );
		    while ( $rowSystemSetting_filters = mysqli_fetch_assoc($sqlSystemSetting_filters) )
		    {
		    	$result[] = $rowSystemSetting_filters;
		    }
		    self::$_systemFiltersByType[$type] = $result;
			return self::$_systemFiltersByType[$type];
		}
	}

	/** desc: vrnemo sistemsko privzete nastavtive filtrov
	 *  (v opisnih statistikah so vsi filtri vključeni, v frekvencah pa izključeni)
	 */
	private static $_systemFiltersDefaultValues = array();
	static function GetSystemFlterDefaultValues($fid)
	{
		if (isset(self::$_systemFiltersDefaultValues[$fid]))
			return self::$_systemFiltersDefaultValues[$fid];
		else {
	    	$result = array('means'=>false, 'crosstab'=>false, 'frequencies'=>false, 'descriptives'=>true);
		    self::$_systemFiltersByType[$fid] = $result;
			return self::$_systemFiltersByType[$fid];
		}
	}


	// vrne true če je $key v tabeli z filtri
	private static $_systemFiltersValue;
	static function isSystemFiltersValue($key)
	{
		$stringSystemSetting_filters =  "SELECT count(id) as cnt FROM srv_sys_filters where filter = '".$key."'";
		$sqlSystemSetting_filters = sisplet_query($stringSystemSetting_filters );
	    $row = mysqli_fetch_assoc($sqlSystemSetting_filters);
	    return $row['cnt'];
	}

	static function AddSystemFilters( $filter, $text, $fid)
	{
		$insertString = "INSERT INTO srv_sys_filters (fid,filter,text,uid,type) ".
			"VALUES ('".$fid."', '".$filter."', '".$text."', '".self::$uid."', '3');";
		self::$mySqlResult = sisplet_query($insertString);
		self::$mySqlErrNo = mysqli_errno($GLOBALS['connect_db']);
	}
	static function DeleteSystemFilters( $id)
	{
		$deleteString = "DELETE FROM srv_sys_filters WHERE id = '".$id."'";
		self::$mySqlResult = sisplet_query($deleteString);
		self::$mySqlErrNo = mysqli_errno($GLOBALS['connect_db']);
	}
	static function SaveSystemFilters($id,$filter,$text)
	{
		$updateString = "UPDATE srv_sys_filters " .
						"SET filter = '".$filter."', text = '".$text."' ".
						"WHERE id = '".$id."'";
		self::$mySqlResult = sisplet_query($updateString);
		self::$mySqlErrNo = mysqli_errno($GLOBALS['connect_db']);
	}

	private static $_systemSysVarFlterData = array();
	static function GetSystemSysVarFlterData($id)
	{
		if (isset(self::$_systemSysVarFlterData[$id])) {
			return self::$_systemSysVarFlterData[$id];
		}
		else {
			$stringSystemSetting_filters =  "SELECT * FROM srv_sys_filters WHERE id = '".$id."'";
			$sqlSystemSetting_filters = sisplet_query($stringSystemSetting_filters );
		  	$result = mysqli_fetch_assoc($sqlSystemSetting_filters);
		  	self::$_systemSysVarFlterData[$id] = $result;
			return self::$_systemSysVarFlterData[$id];
		}
	}

	/** Hendla prikaz filtrov v nastavitvah
	 *
	 */
	static function DisplaySystemFilters($mode='normal')
	{
		global $lang;
		global $s;
		$filtri = self::GetSystemFilters();
		if ( $mode=='normal' )
		{
			echo '<a href="#" onClick="sysFilterEditMode(\'edit\'); return false;">';
			echo '<span class="faicon edit small"></span>'
					.$lang['srv_settings_filter_edit_mode']. '</a>';
			echo '<div class="clr"></div>';
				echo '<div style="clear:both; padding:5px; height:1.2em">';

				echo '<div style="width:40px; float:left; text-align:left; padding-right:15px; text-align:right">';
				echo '<i style="color:gray; text-align:right">'.$lang['srv_filter_id'].'</i>';
				echo '</div>';

				echo '<div style="width:50px; float:left; text-align:left; padding-right:15px;">';
				echo '<i style="color:gray;text-align:right">'.$lang['srv_filter_vrednost'].'</i>';
				echo '</div>';

				echo '<div class="" style="width:250px; float:left; color: gray">';
				echo '<i style="color:gray;">'.$lang['srv_filter_variabla'].'</i>';
				echo '</div>';

				echo '</div>';

			foreach ( $filtri as $key => $filter)
			{
				echo '<div id="sysfilter_div_'.$filter['id'].'" style="clear:both; padding-bottom:0px; height:1.2em;">';
					echo '<div id="sysfilter_filter_'.$filter['id'].'" class="spr_sysFilter'.$filter['type'].'" style="width:40px; float:left; text-align:left; padding-right:15px; color:gray; text-align:right;">'.$filter['fid'].'</div>';
					echo '<div id="sysfilter_filter_'.$filter['id'].'" class="spr_sysFilter'.$filter['type'].'" style="width:50px; float:left; text-align:left; padding-right:15px; text-align:right;">'.$filter['filter'].'</div>';
					echo '<div id="sysfilter_text_'.$filter['id'].'" class="spr_sysFilter'.$filter['type'].'" style="width:250px; float:left;">'.$filter['text'].'</div>';
				echo '</div>';
			}
		}
		else
		if ( $mode=='edit' || $mode=='new')
		{
			echo '<a href="/" onClick="sysFilterEditMode(\'normal\'); return false;">';
			echo '<span class="faicon mapca anketa small"></span>'
					.$lang['srv_settings_filter_view_mode']. '</a>';

				echo '<div style="clear:both; padding:5px; height:1.2em">';

				echo '<div style="width:40px; float:left; text-align:left; padding-right:15px; text-align:right">';
				echo '<i style="color:gray; text-align:right">'.$lang['srv_filter_id'].'</i>';
				echo '</div>';

				echo '<div style="width:50px; float:left; text-align:left; padding-right:15px;">';
				echo '<i style="color:gray;">'.$lang['srv_filter_vrednost'].'</i>';
				echo '</div>';

				echo '<div class="" style="width:250px; float:left; color: gray">';
				echo '<i style="color:gray;">'.$lang['srv_filter_variabla'].'</i>';
				echo '</div>';

				echo '</div>';
			foreach ( $filtri as $key => $filter)
			{

				echo '<div id="sysfilter_div_'.$filter['id'].'" style="clear:both; padding:5px; height:1.2em">';

				echo '<div id="sysfilter_filter_'.$filter['id'].'" class="" style="width:40px; float:left; text-align:left; padding-right:15px; color:gray; text-align:right;">'.$filter['fid'].'</div>';

				echo '<div id="sysfilter_filter_'.$filter['id'].'" class="" style="width:50px; float:left; text-align:left; padding-right:15px; text-align:right;">';
				echo '<input id="sysfilter_filter_input_'.$filter['id'].'" type="text" value="'.$filter['filter'].'" title="'.$filter['filter'].'" size="6" maxlength="6">';
				echo '</div>';

				echo '<div id="sysfilter_text_'.$filter['id'].'" class="" style="width:250px; float:left;">';
				echo  '<input id="sysfilter_text_input_'.$filter['id'].'" type="text" value="'.$filter['text'].'" title="'.$filter['text'].'" size="45" maxlength="100">';
				echo '</div>';

				if ( $filter['type'] == 3)
				{
					echo '<div id="sysfilter_edit_'.$filter['id'].'" class="" style="width:100px; float:left;">';
					echo '<a href="/" onClick="sysFilterDelete('.$filter['id'].'); return false;">';
					echo '<img id="sysfilter_delete_'.$filter['id'].'" src="img_'.$s->skin.'/delete_red.png" alt="'.$lang['srv_filtri_izbrisi_filter'].'" style="vertical-align:text-bottom " />';
					echo '</a>';
					echo '</div>';
				}
				echo '</div>';
				echo '<script>';
				echo '$(document).ready(function() {';
				echo '$("#sysfilter_delete_'.$filter['id'].'").click(function(e)  {});';
				echo '$("#sysfilter_filter_input_'.$filter['id'].'").blur(function(e)  {sysFilterSave('.$filter['id'].'); return false;});';
				echo '$("#sysfilter_text_input_'.$filter['id'].'").blur(function(e)  {sysFilterSave('.$filter['id'].'); return false;});';
				echo '$("#sysfilter_extra_'.$filter['id'].'").click(function(e)  {sysFilterExtraSetting('.$filter['id'].'); return false;});';

				echo '});';
				echo '</script>';
			}
			echo '<div class="clr"></div>';
			// pohendlamo error: duplicate
			if (self::$mySqlErrNo == 1062)
			{
				echo '<div id="error" class="red">';
				echo $lang['srv_duplicateEntry'];
				echo '</div>';
			}

			echo '<div id="sysfilter_new" style="padding-top:10px;">';
			if ($mode == 'new')
			{
				echo '<div id="sysfilter_div_add" style="clear:both; padding:5px; height:1.2em">';

				echo '<div id="sysfilter_filter_add" style="width:40px; float:left; text-align:left; padding-right:15px;">';
				echo '<input id="sysfilter_fid_input_add" type="text" value="'.$filter['fid'].'" title="'.$filter['fid'].'" size="6" maxlength="6">';
				echo '</div>';

				echo '<div id="sysfilter_filter_add" class="" style="width:50px; float:left; text-align:left; padding-right:15px;">';
				echo '<input id="sysfilter_filter_input_add" type="text" value="'.$filter['filter'].'" title="'.$filter['filter'].'" size="6" maxlength="6">';
				echo '</div>';

				echo '<div id="sysfilter_text_add" class="" style="width:250px; float:left;">';
				echo  '<input id="sysfilter_text_input_add" type="text" value="'.$filter['text'].'" title="'.$filter['text'].'" size="45" maxlength="100">';
				echo '</div>';

				echo '<div class="" style="width:50px; float:left;">';
				echo '<img id="sysfilter_add_img" src="icons/icons/accept.png" alt="'.$lang['srv_novfilter'].'" style="vertical-align:text-bottom " />';
				echo '</div>';
			}
			else
				echo '<img id="sysfilter_new_img" src="img_'.$s->skin.'/add.png" alt="'.$lang['srv_novfilter'].'" style="vertical-align:text-bottom " />'.$lang['srv_novfilter'];
			echo '</div>';
			echo '<script>';
			echo '$(document).ready(function() {';
			echo '$("#sysfilter_new_img").click(function(e) {sysFilterEditMode(\'new\'); return false;});';
			echo '$("#sysfilter_add_img").click(function(e) {sysFilterEditMode(\'add\'); return false;});';
			echo '});';
			echo '</script>';

		}

	}

	/**
	 * @desc polovimo nastavitev survey sistema
	 */
	private static $_sysMiscSetting = array();
	function getSysMiscSetting($what=null)
	{
		if (isset(self::$_sysMiscSetting[$what])) {
			return self::$_sysMiscSetting[$what];
		}
		else
		{
			$result = null;
			if (is_string($what))
			{
				$stringSelect = "SELECT value FROM srv_misc WHERE what = '".$what."'";
				$sqlSelect = sisplet_query($stringSelect);
				$rowSelect = mysqli_fetch_array($sqlSelect);
				if (mysqli_num_rows($sqlSelect) > 0)
					$result = $rowSelect['value'];
				else
					$result = '';
			}
			if($result == ''&&$what == 'export_data_type'){
				$result = 2;
			}			
			self::$_sysMiscSetting[$what] = $result;
			return self::$_sysMiscSetting[$what];
		}
	}
	/**
	 * @desc shranimo nastavitev survey sistema
	 */
	function setSysMiscSetting($what=null, $value=null)
	{
		if ( $what && $value )
		{
			if ( is_string($what) && is_string($value) )
			{
				$stringInsert = "INSERT INTO srv_misc (what, value) VALUES ('".$what."', '".$value."') ON DUPLICATE KEY UPDATE value = '".$value."'";
				$sqlInsert = sisplet_query($stringInsert);
				return mysqli_affected_rows($GLOBALS['connect_db']);
			}
			else
				return false;
		}
		else
			return false;
	}
}
/*
function getTableNextAutoIncrement($tableName)
{
	$next_increment 	= 0;
	$qShowStatus 		= "SHOW TABLE STATUS LIKE '$tableName'";
	$qShowStatusResult 	= sisplet_query($qShowStatus) or die ( "Query failed: " . mysqli_error($GLOBALS['connect_db']) . "<br/>" . qShowStatus );


	while ($row = mysqli_fetch_assoc($qShowStatusResult)) {
		$next_increment = $row['Auto_increment'];
	}
	mysqli_free_result($qShowStatusResult);
	return $next_increment;


}
*/
?>
