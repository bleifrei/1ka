<?php 

class VariableView {
	
	static private $_instance;			# Singleton static instance
	static private $_sid;				# id ankete
	
	static private $SDF = null;								# class za inkrementalno dodajanje fajlov
	static private $headFileName = null;					# pot do header fajla
	static private $dataFileName = null;					# pot do data fajla
	static private $dataFileStatus = null;					# status data datoteke
	static private $_HEADERS = array();						# shranimo podatke vseh variabel
	
	/**
	* Get the singleton instance of this class and enable writing at shutdown.
	 *
	 *     $VariableView = VariableView::instance();
	 *
	 * @return  VariableView
	 */
	 public static function instance() {
		 if (self::$_instance === NULL) {
		 // Create a new instance
		 	self::$_instance = new self;
		 }
		 return self::$_instance;
	 }
	 
	 public static function init($sid) {
	 	self::$_sid = $sid;
	 	
	 	# polovimo variable ankete
	 	#inicializiramo class za datoteke
		self::$SDF = SurveyDataFile::get_instance();
		self::$SDF->init($sid);
		
		self::$headFileName = self::$SDF->getHeaderFileName();
		self::$dataFileName = self::$SDF->getDataFileName();
		self::$dataFileStatus = self::$SDF->getStatus();
	 	self::$_HEADERS = self::$SDF->getHeader();
	 	
	 }
	 
	 public static function displayVariables() {
	 	global $lang;
				
         echo '<table class="variableView">';
         
	 	echo '<thead><tr>';
	 	echo '<th>'.$lang['srv_variableView_h_'].'</th>';
	 	echo '<th>'.$lang['srv_variableView_h_type'].'</th>';
	 	echo '<th>'.$lang['srv_variableView_h_width'].'</th>';
	 	echo '<th>'.$lang['srv_variableView_h_decimals'].'</th>';
	 	echo '<th>'.$lang['srv_variableView_h_label'].'</th>';
	 	echo '<th>'.$lang['srv_variableView_h_measure'].'</th>';
         echo '</tr></thead>';
         
         echo '<tbody>';
         
        foreach (self::$_HEADERS AS $skey => $spremenljivka) {
             
            if (is_numeric($spremenljivka['tip']) && $spremenljivka['tip'] != '' && $spremenljivka['tip'] != 'm' && $spremenljivka['tip'] != 'sm') {
                 
                $spss = $spremenljivka['grids'][0]['variables'][0]['spss'];
	 			$spss_type = substr($spss,0,1);
	 			$spss_length = explode('.',substr($spss,1));
	 			$spr_id = explode('_',$skey);
	 			$spr_id = $spr_id[0];
                 $legenda = Cache::spremenljivkaLegenda($spr_id);
                 
	 			echo '<tr>';
	 			echo '<td>'.$spremenljivka['variable'].'</td>';
	 			echo '<td>'.$legenda['izrazanje'].'</td>';
	 			echo '<td>'.(int)$spss_length['0'].'</td>';
	 			echo '<td>'.(int)$spss_length['1'].'</td>';
	 			echo '<td>'.$spremenljivka['naslov'].'</td>';
	 			echo '<td>'.$legenda['skala'].'</td>';
	 			echo '</tr>';
	 		}
         }
         
	 	echo '</tbody>';
	 	echo '</table>';
	}
}