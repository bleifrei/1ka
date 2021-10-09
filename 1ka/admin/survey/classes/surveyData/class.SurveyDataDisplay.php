<?php

/**
 *
 * Created on 17.12.2019
 * 
 * @author: Peter Hrvatin
 * 
 * Class za prikaz tabele s  podatki
 *
*/

#KONSTANTE
// spremenljivke, ki se podajajo preko GETa
define('VAR_REC_ON_PAGE', 'rec_on_page');
define('VAR_SPR_LIMIT', 'spr_limit');
define('VAR_CUR_REC_PAGE', 'cur_rec_page');
define('VAR_SPR_PAGE', 'spr_page');
define('VAR_ORDER', 'order');
define('VAR_EDIT', 'edit');
define('VAR_PRINT', 'print');
define('VAR_MONITORING', 'monitoring');
define('VAR_CODING', 'coding');
define('VAR_DATA', 'view_data');
define('VAR_META', 'view_meta');
define('VAR_CIRCLES', 'view_circles');
define('VAR_METAFULL', 'view_fullmeta');
define('VAR_SHOW_SYSTEM', 'view_system');
define('VAR_SORT_SEQ', 'sort_seq');
define('VAR_SORT_TYPE', 'sort_type');
define('VAR_PDF_TYPE', 'type');
define('VAR_RELEVANCE', 'view_relevance');
define('VAR_SHOW_DATE', 'view_date', false);
define('VAR_SHOW_NO', 'view_no', false);
define('VAR_EMAIL', 'email');
define('SRV_LIST_GROUP_PAGINATE', 4);			# po kolko strani grupira pri paginaciji

global $site_path;

class SurveyDataDisplay{

	static private $sid = null; 		        # id ankete

	static private $folder = '';		        # pot do folderja
	static private $headFileName = null;		# pot do header fajla
	static private $dataFileName = null;		# pot do data fajla
	static private $dataFileStatus = null;		# status data datoteke
	static private $dataFileUpdated = null;		# kdaj je bilo updejtano

	static private $SDF = null;					# class za osnovne funkcije data fajla

	static private $inited = false; 	                # ali smo razred inicializirali
	static private $subAction = M_COLLECT_DATA_VIEW; 	# ali smo v urejanju ali pregledu

	static private $survey = null;		# podatki ankete
	static private $db_table = '';		# ali se uporablja aktivna tabela

	static private $file_handler = null;		# ali se uporablja aktivna tabela

	static private $_REC_ORDER = array('recnum'=>'ASC');    // vrstni red zapisov - rekordov
	static private $_REC_LIMIT = ' NR==1,NR==50'; 			# string za limiz tapisov

	static private $_RECORD_COUNT = 0;						# koliko zapisov dobimo po filtriranju
	static private $_TOTAL_PAGES = 0;						# koliko strani dobimo po filtriranju
	static private $_ALL_QUESTION_COUNT = null;				# koliko je vseh vprasanj
	static private $_ALL_VARIABLES_COUNT = null;			# koliko je vseh variables

	static private $SSNDF = null;							# Class za SN_data fajle
	static private $is_social_network = false;				# ali je anketa tipa SN (social network)

	static private $_HEADERS = array();

	static private $_HAS_TEST_DATA = false;					# ali anketa vsebuje testne podatke

	static private $do_sort = false;						# ali sploh sortiramo podatke
	static private $sort_seq = null;						# po katerem stolpcu sortiramo
	static private $sort_type = null;						# na kak način sortiramo sort_asc = / sort_dsc =

	static private $doCMSUserFilter = false;				# ali filtriramo samo svoje ankete

	static private $usr_id = null; 							#id respondenta za ki ga trenutno prikazujemo
	
	static private $showItime = false; 						#ali prikazujemo insert time
	static private $showLineNumber = false; 				#ali vrivamo line number
	static private $lineoffset = 0; 						#po koliko celicah vrivamo line number

	static private $displayEditIcons = array(
			'dataIcons_quick_view' => true,
			'dataIcons_edit' => false,
			'dataIcons_write'=>false); 	#ali prikazujemo ikone za urejanje
			
	static private $displayEditIconsSettings = false;		# ali prikazujemo okno s checkboxi za nastavitve tabele s podatki	
			
	static private $printPreview = false;					# ali prikazujemo podatke kot print preview;

	static private $canDisplayRelevance = true;				# ali prikazujemo ustreznost - relevance;
	
	static private $quickEdit_recnum = array();				# array z prejsnjim in naslednjim recnumom (za vpogled - puscici naprej,nazaj)

	# LIMITI
	static public $_VARS = array(
			VAR_DATA        => 1,	# ali prikazujemo podatke
			VAR_REC_ON_PAGE     => 50,
			VAR_CUR_REC_PAGE    => 1,
			VAR_META        => 0,	# ali prikazujemo meta (status)
			VAR_METAFULL    => 0,	# ali prikazujemo full meta
			VAR_SPR_LIMIT   => 10,
			VAR_SPR_PAGE    => 1,
			VAR_EDIT        => 0,	# ali imamo možnost urejanja (brisanje popravljanje)
			VAR_PRINT       => 0,	# ali imamo možnost izpisa v PDF, RTF
			VAR_MONITORING  => 0,	# ali smo v zavihku monitoring
			VAR_CODING  	=> 0,	# ali smo v zavihku kodiranje
			VAR_SHOW_SYSTEM => 0,	# ali prikazujemo sistemske variable (telefon, email)
			VAR_PDF_TYPE	=> 0,	# tip izpisa pdf (0 -> kratek, 1 -> dolg, 2 -> zelo kratek)
			VAR_SORT_SEQ	=> '',	# po kateri sekvenci sortiramo
			VAR_SORT_TYPE	=> '',	# način sortiranja (naraščajoče, padajoče)
			VAR_RELEVANCE	=> 1,	# ali prikazujemo ustreznost
			VAR_EMAIL		=> 1,	# ali prikazujemo email status
			VAR_CIRCLES		=> 0,	# ali prikazujemo kroge antonučija
			VAR_SHOW_DATE	=> 0,	# ali prikazujemo kroge antonučija
			VAR_SHOW_NO		=> 0,	# ali prikazujemo kroge antonučija
	);


	
	static public $_CURRENT_STATUS_FILTER = '';
	static public $_VARIABLE_FILTER = '';				# sed string array z prikazanimi variablami z upoštevanjem filtrov
	static private $_SVP_PV = array();						# array z prikazanimi variablami z upoštevanjem filtrov

	
	static public $_PROFILE_ID_STATUS = null;
	static public $_PROFILE_ID_VARIABLE = null;
	static public $_PROFILE_ID_CONDITION = null;
	
	function __construct($anketa) {
		self::Init($anketa);
	}
	
	
	/** Inicializacija
	 *
	 * @param $sid
	 */
	static public function Init($sid) {

		# nastavimo privzeto pot do folderjev
		global $site_path, $global_user_id, $lang;

		self::$folder = $site_path . EXPORT_FOLDER.'/';
		# nastavimo id ankete
		self::$sid = $sid;
		
		Common::deletePreviewData($sid);
		
		SurveySession::sessionStart(self::$sid);
	
		# nastavimo limite
        self::setVars();
        
		# podakcija
        self::$subAction = isset($_GET['m']) && $_GET['m'] != '' ? $_GET['m'] : M_COLLECT_DATA_VIEW;
        
		# informacije ankete
		SurveyInfo::getInstance()->SurveyInit($sid);
		self::$survey = SurveyInfo::getInstance()->getSurveyRow();
		
		# aktivne tabele
		if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
			self::$db_table = '_active';

		# ali je anketa tipa SN - social network
        self::$is_social_network = (SurveyInfo::getInstance()->checkSurveyModule('social_network')) ? true : false;
        
        # Ce imamo vklopljeno povezovanje z identifikatorji, potem prikaz identifikatorjev izklopimo
        if(self::$survey['show_email'] == '1'){
            self::$_VARS[VAR_SHOW_SYSTEM] = false;
        }

		# ali prikazujemo datum na začetku ankete (Če smo v identifikatorjih ne smemo zaradi povezovanja)
		if ( (self::$_VARS[VAR_SHOW_DATE] == true || (int)SurveyInfo :: getInstance()->getSurveyColumn('showItime') == 1) && self::$_VARS[VAR_SHOW_SYSTEM] == false ) {
		//if (self::$_VARS[VAR_SHOW_DATE] == true && self::$_VARS[VAR_SHOW_SYSTEM] == false) {
			self::$showItime = true;
		} 
		else {
			self::$showItime = false;
		}
		
		# ali prikazujemo zaporedno številko. (Če smo v identifikatorjih ne smemo zaradi povezovanja)
		if ( (self::$_VARS[VAR_SHOW_NO] == true || (int)SurveyInfo :: getInstance()->getSurveyColumn('showLineNumber') == 1) && self::$_VARS[VAR_SHOW_SYSTEM] == false ) {
		//if (self::$_VARS[VAR_SHOW_NO] == true) {
			self::$showLineNumber = true;
		} 
		else {
			self::$showLineNumber = false;
		}
		
		self::$_CURRENT_STATUS_FILTER = STATUS_FIELD.'~/6|5/';

		if (self::$subAction == M_COLLECT_DATA_MONITORING)
            $monitoring = true; 
        else 
            $monitoring = false;
				
		SurveyStatusProfiles :: Init(self::$sid);
		SurveyVariablesProfiles :: Init($sid, $global_user_id, true, $monitoring);
		SurveyConditionProfiles :: Init($sid, $global_user_id);
		SurveyTimeProfiles :: Init($sid, $global_user_id);
		SurveyUserSetting :: getInstance()->Init($sid, $global_user_id);
		SurveyDataSettingProfiles :: Init($sid);
		SurveySetting::getInstance()->Init($sid);
			
		$sdsp_displayEditIcons = SurveyDataSettingProfiles :: getSetting('dataShowIcons');
		if ($sdsp_displayEditIcons != null && is_array($sdsp_displayEditIcons)) {
			self::$displayEditIcons = $sdsp_displayEditIcons;
		}

		if(isset($_SESSION['sid_'.self::$sid]['dataIcons_settings']))
			self::$displayEditIconsSettings = ($_SESSION['sid_'.self::$sid]['dataIcons_settings']);
		
		# ali filtriramo cms usejreve datotekoe
		session_start();
		self::$doCMSUserFilter = $_SESSION['sid_'.$sid]['doCMSUserFilter'];
		session_commit();


        // Inicializiramo class za datoteke
        self::$SDF = SurveyDataFile::get_instance();
        self::$SDF->init($sid);

        // Ce imamo urlhash gre za javno povezavo in nikoli ne prikazemo loading okna
        $show_loading = (isset($_GET['urlhash'])) ? false : true;

        self::$SDF->prepareFiles($show_loading);
		
		self::$headFileName = self::$SDF->getHeaderFileName();
		self::$dataFileName = self::$SDF->getDataFileName();
        self::$dataFileStatus = self::$SDF->getStatus();	
		self::$dataFileUpdated = self::$SDF->getFileUpdated();
        
        
        // Ce ni datoteke izpisemo samo text
		if ( self::$dataFileStatus == FILE_STATUS_NO_DATA || self::$dataFileStatus == FILE_STATUS_SRV_DELETED) {
			Common::noDataAlert();
			return false;
		}
		
		
		// Ce smo v identifikatorjih potem ne omogočamo urejanja
		if (self::$_VARS[VAR_SHOW_SYSTEM] == true) {
			foreach (self::$displayEditIcons AS $key => $value) {
				self::$displayEditIcons[$key] = false;
			}
		}
		
		# nastavimo ali smo v urejanju. Po novem gledamo url - zaenkrat imamo pregled urejanje in izvoz skupaj

		if(self::$subAction == M_COLLECT_DATA_VIEW){
			self::$_VARS[VAR_PRINT] = true;
		}
		if (self::$subAction ==  M_COLLECT_DATA_EDIT && self::$_VARS[VAR_SHOW_SYSTEM] == false) {
			self::$_VARS[VAR_EDIT] = true;
		}
		// gorazd, ti si nekaj spremenil tole EDIT variablo - tuki se zdej v vsakem primeru nastavi edit
		if (self::$displayEditIcons['dataIcons_edit'] == true && self::$_VARS[VAR_SHOW_SYSTEM] == false) {
			self::$_VARS[VAR_EDIT] = true;
		} else {
			self::$_VARS[VAR_EDIT] = false;
		}
		
		if ( self::$displayEditIcons['dataIcons_multiple'] ) {
			self::$_VARS['spr_limit'] = 'all';
		}

		if (self::$subAction == M_COLLECT_DATA_MONITORING) {
			self::$_VARS[VAR_MONITORING] = true;
			self::$_VARS[VAR_PRINT] = true;
		}

		if (self::$subAction == 'coding') {
			self::$_VARS[VAR_CODING] = true;
		}

		self::$_VARS[VAR_META] = self::$_VARS[VAR_METAFULL];

		# Če so izbrani VSI vnosi, naj bo označena USTREZNOST,
		# če pa so označeni le USTREZNI, je spoloh ni treba tega stolpca, saj so itak vsi ustrezni in je odveč.
		$ssp_pid = SurveyStatusProfiles::getCurentProfileId();
		if ($ssp_pid == 2) {
			self::$canDisplayRelevance = false;
		}

		# preštejemo vsa vabila, če so vsi na ne, potem ne prikažemo vabil
		if (IS_WINDOWS) {
			$awk_cnt_str = 'awk -F"'.STR_DLMT.'" "'.EMAIL_FIELD.'~/1/'.' {cnt++} END {print cnt}" '.self::$dataFileName;
		} else {
			$awk_cnt_str = 'awk -F"'.STR_DLMT.'" \''.EMAIL_FIELD.'~/1/'.' {cnt++} END {print cnt}\' \''.self::$dataFileName.'\'';
		}
		
		$emailCount = shell_exec($awk_cnt_str);
		
		#self::$_VARS[VAR_EMAIL] = self::$_VARS[VAR_RELEVANCE] && ((int)self::$survey['email'] == 1 && (int)self::$survey['user_base'] == 1);
		if (((int)self::$survey['email'] == 0 && (int)self::$survey['user_base'] == 0) ) {
			self::$_VARS[VAR_EMAIL] = 0;
		}
		
		# nastavimo način sortiranja
		self::setUpSort();

		# plovimo privzete id-je uporabniškega filtra
		self::setUserFilters();
		
		# nastavimo uporabniške filtere
		self::setUpFilter();

		# nastavimo SN  class
		if (self :: $is_social_network) {
			self::$SSNDF = new SurveySNDataFile(self::$sid);
			self::$SSNDF->setVars(self::$_VARS);
			self::$SSNDF->setParameter('canDisplayRelevance',self::$canDisplayRelevance);
			self::$SSNDF->setParameter('showItime',self::$showItime);
			self::$SSNDF->setParameter('showLineNumber',self::$showLineNumber);
		}

		# nastavimo trenuten id respondenta (ce ga imamo)
		if(isset($_GET['usr_id'])) {
			self::$usr_id = $_GET['usr_id'];
		}
	}

	public function ajax() {
		
		switch ($_GET['a']) {
			case 'displayDataPrintPreview' :
				self :: displayDataPrintPreview();
				break;
			case 'setSnDisplayFullTableCheckbox' :
				self :: setSnDisplayFullTableCheckbox();
				break;
			case 'set_data_search_filter' :
				self :: setDataSearchFilter();
				break;
			default:
				echo 'Error! (class: SurveyAnalysis->ajax() - missing action)';
				break;
		}
	}



	/** vrne število vseh vprašanj
	 *
	 */
	public static function getQuestionCount() {
		if (self::$_ALL_QUESTION_COUNT == null) {
			self::$_ALL_VARIABLES_COUNT = 0;
			self::$_ALL_QUESTION_COUNT = 0;

			if (self::$headFileName != null && self::$headFileName != '') {
				foreach (unserialize(file_get_contents(self::$headFileName)) AS $_spremenljivka) {
					if (isset($_spremenljivka['tip']) && $_spremenljivka['tip'] != 'm' && $_spremenljivka['tip'] != 'sm') {
						self::$_ALL_VARIABLES_COUNT += $_spremenljivka['cnt_all'];
						self::$_ALL_QUESTION_COUNT++;
					}
				}
			}
		}

		return self::$_ALL_QUESTION_COUNT;
	}

	/** vrne število vseh variabel
	 *
	 */
	public function getVariablesCount() {
		if (self::$_ALL_VARIABLES_COUNT == null) {
			self::$_ALL_VARIABLES_COUNT = 0;
			self::$_ALL_QUESTION_COUNT = 0;
			if (self::$headFileName != null && self::$headFileName != '') {
				foreach (unserialize(file_get_contents(self::$headFileName)) AS $_spremenljivka) {
					if (isset($_spremenljivka['tip']) && $_spremenljivka['tip'] != 'm' && $_spremenljivka['tip'] != 'sm') {
						self::$_ALL_VARIABLES_COUNT += $_spremenljivka['cnt_all'];
						self::$_ALL_QUESTION_COUNT++;
					}
				}
			}
		}
		return self::$_ALL_VARIABLES_COUNT;
	}


	/**
	 * @desc nastavi default vrednosti spremenljivk in prebere kar je blo GETano
	 */
	static function setVars () {
		// preberemo kar je GETano
		$data_view_settings = SurveySession::get('data_view_settings');
		
		foreach (self::$_VARS  AS $var => $val) {
			if (isset($data_view_settings[$var])) 
			{
				$_val = $data_view_settings[$var];
				if (is_string($_val) && $_val == 'true')
				{ 
					$_val = (int)true;
				}
				else if (is_string($_val) && $_val == 'false')
				{ 
					$_val = (int)false;
				}
				else if (is_numeric($_val))
				{
					if( (float)$_val != (int)$_val )
					{
						$_val =  (float)$_val;
					}
					else
					{
						$_val =  (int)$_val;
					}
				}
				self::$_VARS[$var] = $_val;
			} 
			else if (isset($_REQUEST[$var])) 
			{
				self::$_VARS[$var] = $_REQUEST[$var];
			}
		}
	}

	/**
	 * @desc vrne vse spremenljivki v obliki, ki se poslje preko GETa
	 * v parametrih se poda spremenljivko, ki se bo nastavla (se prav ni taka, kot je zdej v self::$_VARS)
	 */
	static function getVars ($new_var=null, $new_val=null) {
		$str = '';
		foreach (self::$_VARS AS $var => $val) {
			if ($var == $new_var) {
				$str .= '&'.$var.'='.$new_val;
			} else {
				$str .= '&'.$var.'='.$val;
			}
		}
		return $str;
	}
	/**
	 * @desc vrne vse spremenljivki v obliki, ki se poslje preko GETa brez sorta, ker dodamo naknadno z javascriptom
	 * v parametrih se poda spremenljivko, ki se bo nastavla (se prav ni taka, kot je zdej v self::$_VARS)
	 */

	static function getVarsNoSort ($new_var=null, $new_val=null) {
		$str = '';
		foreach (self::$_VARS AS $var => $val) {
			if ($var != VAR_SORT_SEQ && $var != VAR_SORT_TYPE) {
				if ($var == $new_var) {
					$str .= '&'.$var.'='.$new_val;
				} else {
					$str .= '&'.$var.'='.$val;
				}
			}
		}
		return $str;
	}

	static private function setUpSort() {
		# če smo postali sort nastavitve preko geta
		if (isset($_GET['sort_seq']) && (int)$_GET['sort_seq'] > 0 ) 
		{
			# sortiramo
			self::$do_sort = true;
	
			# nastavimo po kateri sekvenci / stolpcu sortiramo
			self::$sort_seq = (int)$_GET['sort_seq'];
	
			if (isset($_GET['sort_type']) && $_GET['sort_type'] === 'sort_dsc' ) 
			{
				self::$sort_type = 'sort_dsc';
			} 
			else 
			{
				self::$sort_type = 'sort_asc';
			}
		} 
		else 
		{
			# ne sortiramo
			self::$do_sort = false;
		}
	}
	
	static private function setUserFilters()
	{
		#SurveyStatusProfiles :: setCurentProfileId();
		self::$_PROFILE_ID_STATUS = SurveyStatusProfiles :: getDefaultProfile();
		
		# Nastavimo filtre variabel
		$dvp = SurveyUserSetting :: getInstance()->getSettings('default_variable_profile');
		$_currentVariableProfile = SurveyVariablesProfiles :: checkDefaultProfile($dvp);
		if ($dvp != $_currentVariableProfile) {
			SurveyUserSetting :: getInstance()->saveSettings('default_variable_profile', $_currentVariableProfile);
		}
		self::$_PROFILE_ID_VARIABLE = $_currentVariableProfile;
		
	}
	
	
	static public function setUpFilter() {

		# nastavimo filter po statusih

		if (self::$headFileName != null && self::$headFileName != '' && file_exists(self::$headFileName)) {

		# kadar zbiramo sistemske, moramo obvezno zbirati tudi podatke, ne smemo pa full meta
		if (self::$_VARS[VAR_SHOW_SYSTEM]){
			self::$_VARS[VAR_DATA] = true;
			self::$_VARS[VAR_META] = true;
			self::$_VARS[VAR_METAFULL] = false;
			self::$_VARS[VAR_SHOW_DATE] = false;
		}
		# filtriranje po statusih
		self::$_CURRENT_STATUS_FILTER = SurveyStatusProfiles :: getStatusAsAWKString();
	
		if (self::$dataFileStatus >= 0) {
			self::$_HEADERS = unserialize(file_get_contents(self::$headFileName));
	
			# ali imamo filter na testne podatke
			if (isset(self::$_HEADERS['testdata']['grids'][0]['variables'][0]['sequence']) && (int)self::$_HEADERS['testdata']['grids'][0]['variables'][0]['sequence'] > 0) {
				$test_data_sequence = self::$_HEADERS['testdata']['grids'][0]['variables'][0]['sequence'];
				$filter_testdata = SurveyStatusProfiles :: getStatusTestAsAWKString($test_data_sequence);
			}
			# filtriranje po časih
			$_time_profile_awk = SurveyTimeProfiles :: getFilterForAWK(self::$_HEADERS['unx_ins_date']['grids']['0']['variables']['0']['sequence']);
	
			# ali imamo filter na uporabnost
			if (isset(self::$_HEADERS['usability']['variables'][0]['sequence']) && (int)self::$_HEADERS['usability']['variables'][0]['sequence'] > 0) {
				$usability_data_sequence = self::$_HEADERS['usability']['variables'][0]['sequence'];
				$filter_usability = SurveyStatusProfiles :: getStatusUsableAsAWKString($usability_data_sequence);
			}
	
			# če nismo v indikatorjih (sistemske)
			if (self::$_VARS[VAR_SHOW_SYSTEM] == false) {
				# dodamo še ife
				SurveyConditionProfiles :: setHeader(self::$_HEADERS);
				$_condition_profile_AWK = SurveyConditionProfiles:: getAwkConditionString();
		
				# dodamo še ife za inspect
				$SI = new SurveyInspect(self::$sid);
				$_inspect_condition_awk = $SI->generateAwkCondition();
			}
			
			# dodamo pogoj za filter prepoznave uporabnika iz cms
			# vklopljeno more bit prepoznava userja iz cms
			if (self::$doCMSUserFilter == true) {
				$CMSUserCondition = self::createCMSUserFilter();
			}

			if (($_condition_profile_AWK != "" && $_condition_profile_AWK != null )
					|| ($_inspect_condition_awk != "" && $_inspect_condition_awk != null)
					|| ($_time_profile_awk != "" && $_time_profile_awk != null)
					|| ($CMSUserCondition != "" && $CMSUserCondition != null)
					|| ($filter_testdata != null)
					|| ($filter_usability != null)) {
				self::$_CURRENT_STATUS_FILTER = '('.self::$_CURRENT_STATUS_FILTER;
				if ($_condition_profile_AWK != "" && $_condition_profile_AWK != null ) {
					self::$_CURRENT_STATUS_FILTER .= '&&'.$_condition_profile_AWK;
				}
				if ($_inspect_condition_awk != "" && $_inspect_condition_awk != null ) {
					self::$_CURRENT_STATUS_FILTER .= ' && '.$_inspect_condition_awk;
				}
				if ($_time_profile_awk != "" && $_time_profile_awk != null) {
					self::$_CURRENT_STATUS_FILTER .= '&&'.$_time_profile_awk;
				}
				if ($CMSUserCondition != "" && $CMSUserCondition != null) {
					self::$_CURRENT_STATUS_FILTER .= '&&'.$CMSUserCondition;
				}
				if ($filter_testdata != null ) {
					self::$_CURRENT_STATUS_FILTER .= '&&('.$filter_testdata.')';
				}
				if ($filter_usability != null ) {
					self::$_CURRENT_STATUS_FILTER .= '&&('.$filter_usability.')';
				}
				self::$_CURRENT_STATUS_FILTER .= ')';
			}
				
			# preštejemo vse zapise ki ustrezajo filtru po statusu
			if (IS_WINDOWS) {
				$awk_string = 'awk -F"'.STR_DLMT.'" "'.self::$_CURRENT_STATUS_FILTER.' {cnt++} END {print cnt}" '.self::$dataFileName;
				$recCount = shell_exec($awk_string);
				if ($_GET['debug'] == 1) {
					print_r('<br>'.$awk_string);
				}
	
			} else {
				$awk_string = 'awk -F"'.STR_DLMT.'" \''.self::$_CURRENT_STATUS_FILTER.' {cnt++} END {print cnt}\' \''.self::$dataFileName.'\'';
				$recCount = shell_exec($awk_string);
				if ($_GET['debug'] == 1) {
					print_r('<br>'.$awk_string);
				}
			}
			if ((int)$recCount > 0 ) {
				self::$_RECORD_COUNT = (int)$recCount;
			}

	
			if (self::$_VARS[VAR_REC_ON_PAGE] != 'all') {
				self::$_TOTAL_PAGES = bcdiv(self::$_RECORD_COUNT, self::$_VARS[VAR_REC_ON_PAGE]);
	
				if (bcmod(self::$_RECORD_COUNT, self::$_VARS[VAR_REC_ON_PAGE]) > 0)
					self::$_TOTAL_PAGES += 1;
				if (self::$_VARS[VAR_CUR_REC_PAGE] > self::$_TOTAL_PAGES ) {
					self::$_VARS[VAR_CUR_REC_PAGE] = self::$_TOTAL_PAGES;
				} elseif (self::$_VARS[VAR_CUR_REC_PAGE] < 1 ) {
					self::$_VARS[VAR_CUR_REC_PAGE] = 1;
				}
	
				# nastavimo limit za datoteko
	
				$up = self::$_VARS[VAR_REC_ON_PAGE] * self::$_VARS[VAR_CUR_REC_PAGE];
				$low = $up - self::$_VARS[VAR_REC_ON_PAGE]+1;
	
				self::$_REC_LIMIT = ' NR=='.$low.',NR=='.$up.'';
			} else {
				# nastavimo limit za datoteko
				self::$_REC_LIMIT = '';
			}
		}
		if (self::$_VARS[VAR_DATA]) {
			$tmp_svp_pv = SurveyVariablesProfiles :: getProfileVariables(self::$_PROFILE_ID_VARIABLE );
	
			# če je $svp_pv = null potem prikazujemo vse variable
			# oziroma če je sistemski dodamo tudi vse, ker drugače lahko filter skrije telefon in email
			if (count($tmp_svp_pv) == 0 || self::$_VARS[VAR_SHOW_SYSTEM] == true ) {
				
				$_sv = self::$SDF->getSurveyVariables();
				if (count($_sv) > 0) {
					foreach ( $_sv as $vid => $variable) {
						$tmp_svp_pv[$vid] = $vid;
					}
				}
			}
		}
		self::$lineoffset=1;
		# če prikazujemo sistemske ne prikazujemo recnumber
		if (!self::$_VARS[VAR_SHOW_SYSTEM] && self::$_VARS[VAR_META] && self::$_VARS[VAR_METAFULL]) {
			$svp_pv['recnum'] = 'recnum';
			#$svp_pv['code'] = 'code';
			self::$lineoffset++;
			# za code ni ofseta
			#self::$lineoffset++;
		}
	
	if (self::$_VARS[VAR_DATA] && count($tmp_svp_pv) > 0) {
		foreach ($tmp_svp_pv AS $_svp_pv) {

			# če imamo sistemski email ali telefon, ime, priimek (v header je nastavljno "hide_system" = 1)
			# potem v odvisnosti od nastavitve prikazujemo samo navadne podatke ali pa samo te sistemske, zaradizaščite podatkov
			$_sistemski = false;
			if (!self::$_VARS[VAR_SHOW_SYSTEM] && self::$_HEADERS[$_svp_pv]['hide_system'] == '1') {
				# prikazujemo samo nesistemske (nezaščitene)
				unset(self::$_HEADERS[$_svp_pv]);
			} else if (self::$_VARS[VAR_SHOW_SYSTEM] && self::$_HEADERS[$_svp_pv]['hide_system'] !== '1') {
				# prikazujemo samo sistemske (zaščitene) podatke
				unset(self::$_HEADERS[$_svp_pv]);
			} else {
				# če ne dodamo
				$svp_pv[$_svp_pv] = $_svp_pv;
			}
		}
	}

	#status - če smo v meta ali imamo profil vse enote
	if ( (self::$_VARS[VAR_META] && self::$_VARS[VAR_METAFULL]) 
			|| ( $ssp_pid = SurveyStatusProfiles::getCurentProfileId() == 1 )) {
		$svp_pv['status'] = 'status';
		self::$lineoffset++;
	}
	
	#lurker
	if ( (self::$_VARS[VAR_META] && self::$_VARS[VAR_METAFULL]) 
			|| ( $ssp_pid = SurveyStatusProfiles::getCurentProfileId() == 1 )) {
		// dodamo v array da se prikazujejo tudi ti stolpci
		$svp_pv['lurker'] = 'lurker';
		self::$lineoffset++;
	}
	# ustreznost
	if (self::$_VARS[VAR_RELEVANCE] && self::$canDisplayRelevance  && self::$_VARS[VAR_SHOW_SYSTEM] == false) {
		// dodamo v array da se prikazujejo tudi ti stolpci
		$svp_pv['relevance'] = 'relevance';
		self::$lineoffset++;
	}
	
	# email tion
	#email prikazujemo skupaj z ustreznostjo
	if ( self::$_VARS[VAR_EMAIL]  && self::$_VARS[VAR_SHOW_SYSTEM] == false ) {
		// dodamo v array da se prikazujejo tudi ti stolpci
		$svp_pv['invitation'] = 'invitation';
		self::$lineoffset++;
	}

	if (isset(self::$_HEADERS['testdata'])) {
		self::$_HAS_TEST_DATA = true;
		$svp_pv['testdata'] = 'testdata';
		self::$lineoffset++;
	}
	# $svp_pv['unx_ins_date'] = 'unx_ins_date';

	if (self::$_VARS[VAR_METAFULL]  && self::$_VARS[VAR_SHOW_SYSTEM] == false) {
		# dodamo tudi special meta
		$svp_pv['meta'] = 'meta';
	}
	
	if (self::$showItime == true) {
		$svp_pv['itime'] = 'itime';
		self::$lineoffset++;
	}
	
	// ce imamo vklopljene sistemske ne smemo povezovat podatkov in zato urejamo po abecedi
	if(self::$_VARS[VAR_SHOW_SYSTEM]){
		
		$hasEmail = false;	
		
		// Poiscemo sekvenco sistemske spremenljivke
		foreach (self::$_HEADERS AS $spr => $spremenljivka) {
			if (isset($spremenljivka['sistem']) && $spremenljivka['sistem'] == 1 && $spremenljivka['variable'] == 'email') {
				$sequence = $spremenljivka['sequences'];
				$hasEmail = true;
			}
		}
		
		if($hasEmail){
			# sortiramo
			self::$do_sort = true;
		
			# nastavimo po kateri sekvenci / stolpcu sortiramo
			self::$sort_seq = $sequence;
			
			self::$sort_type = 'sort_asc';
		}
	}
	
	self::getQuestionCount();
	if (self::$_VARS[VAR_SPR_LIMIT] > self::$_ALL_QUESTION_COUNT) {
		self::$_VARS[VAR_SPR_LIMIT] = 'all';
	}
	
	$spr_cont = 0; // za paginacijo spremenljivk
	
	if(self::$_VARS['spr_limit'] == 'all'){
		$_spr_on_pages_start = 0;
		$_spr_on_pages_stop = self::$_VARS['spr_page'];
	}		
	else{
		$_spr_on_pages_start = self::$_VARS['spr_page'] * self::$_VARS['spr_limit'] - self::$_VARS['spr_limit'];
		$_spr_on_pages_stop = self::$_VARS['spr_page'] * self::$_VARS['spr_limit'];
	}

	# skreiramo filter variabel za podatke
	if (count(self::$_HEADERS) > 0) {
		// zloopamo skozi spremenljivke in sestavimo filter po stolpcih
		$_tmp_filter =  '';
		foreach (self::$_HEADERS AS $spid => $spremenljivka) {
			if (isset($svp_pv[$spid])) {
				// paginacija spremenljivk
				if (self::$_VARS['spr_limit'] == 'all' || ($spr_cont >= $_spr_on_pages_start && $spr_cont < $_spr_on_pages_stop)) {
					if (count($spremenljivka['grids']) > 0 ) {
						foreach ($spremenljivka['grids'] AS $gid => $grid) {
							if (count ($grid['variables']) > 0) {
								foreach ($grid['variables'] AS $vid => $variable ){
									$_tmp_filter .= $_prfx.$variable['sequence'];
									$_prfx = ',';
								}
							}
						}
					}
				} // end: paginacija spremenljivk
				$spr_cont++;
			} else
				# če prikazujemo samo sistemske
				if ( self::$_VARS[VAR_SHOW_SYSTEM] && in_array($spremenljivka['variable'], array('email','ime','priimek','telefon','naziv','drugo','odnos'))) {
				if (count($spremenljivka['grids']) > 0 ) {
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						if (count ($grid['variables']) > 0) {
							foreach ($grid['variables'] AS $vid => $variable ){
								$_tmp_filter .= $_prfx.$variable['sequence'];
								$_prfx = ',';
							}
						}
					}
				}
				$svp_pv[$spid] = $spid;
			}
		}
	}

			# prilagodimo array profilov variabel
			self::$_SVP_PV = $svp_pv;
			if ($_tmp_filter != '') 
			{
				self::$_VARIABLE_FILTER = $_tmp_filter;
			}
		}

	}

	/** Prikaže filtre za število podatkov....
	 *
	 */
	public static function displayFilters() {
		global $site_url, $site_path, $lang, $global_user_id;
		
		#debug
		echo '<script>window.onload = function() { __vnosi = 1; }</script>';

		if (self::$dataFileStatus == FILE_STATUS_NO_DATA) {
			return false;
		}	

		# če imamo podatke
		if (self::$dataFileStatus != FILE_STATUS_SRV_DELETED) {
			
            echo '<div id="dataSettingsCheckboxes" '.(self::$displayEditIconsSettings ? '' : ' style="display:none;"').'>';
        
            echo '<div id="toggleDataCheckboxes2" onClick="toggleDataCheckboxes(\'data\');"><span class="faicon close icon-orange" style="padding-bottom:2px;"></span> '.$lang['srv_data_settings_checkboxes2'].'</div>';
        
            if(self::$dataFileStatus != FILE_STATUS_NO_DATA){
                echo '<div id="dataSetingsLinks" class="data noBorder">';
                self::displayLeftFilters();
                echo '</div>'; // konec diva za paginacijo
            }
            
            if (self :: $is_social_network == false || self::$_VARS[VAR_CIRCLES] == 0) {

                echo '<div class="clr" id="dataIconSetingsLinks" >'.$lang['srv_dataIcons_note'].'&nbsp;&nbsp;';
                
                if (self::$_VARS[VAR_SHOW_SYSTEM] == false ) {
                    echo '<input type="checkbox" id="dataIcons_quick_view" onchange="changeDataIcons(); return false;"'.(self::$displayEditIcons['dataIcons_quick_view'] == true ? ' checked="checekd"' : '').'/><label for="dataIcons_quick_view">'.$lang['srv_dataIcons_quick_view'].'</label>';
                    echo '&nbsp;&nbsp;';
                    /*echo '<input type="checkbox" id="dataIcons_edit" onchange="changeDataIcons(); return false;"'.(self::$displayEditIcons['dataIcons_edit'] == true ? ' checked="checekd"' : '').'/><label for="dataIcons_edit">'.$lang['srv_dataIcons_edit'].'</label>';
                    if (self::$displayEditIcons['dataIcons_edit'] == true) {
                        echo ' '.Help::display('srv_podatki_urejanje_inline');
                    }
                    echo '&nbsp;&nbsp;';
                    echo '<input type="checkbox" id="dataIcons_write" onchange="changeDataIcons(); return false;"'.(self::$displayEditIcons['dataIcons_write'] == true ? ' checked="checekd"' : '').'/><label for="dataIcons_write">'.$lang['srv_dataIcons_write'].'</label>';
                    echo '&nbsp;&nbsp;';*/
                    echo '<input type="checkbox" id="dataIcons_labels" onchange="changeDataIcons(); return false;"'.(self::$displayEditIcons['dataIcons_labels'] == true ? ' checked="checekd"' : '').'/><label for="dataIcons_labels">'.$lang['srv_dataIcons_labels'].'</label>';
                    
                    if ( self::showMultiple() ) {
                        echo '&nbsp;&nbsp;';
                        echo '<input type="checkbox" id="dataIcons_multiple" onchange="changeDataIcons(); return false;"'.(self::$displayEditIcons['dataIcons_multiple'] == true ? ' checked="checekd"' : '').' /><label for="dataIcons_multiple">'.$lang['srv_dataIcons_multiple'].'</label>';
                    }
                } else {
                    echo '<input type="checkbox" id="dataIcons_quick_view"  disabled="disabled" /><label for="dataIcons_quick_view" class="gray">'.$lang['srv_dataIcons_quick_view'].'</label>';
                    echo '&nbsp;&nbsp;';
                    /*echo '<input type="checkbox" id="dataIcons_edit" disabled="disabled" /><label for="dataIcons_edit" class="gray">'.$lang['srv_dataIcons_edit'].'</label>';
                    if (self::$displayEditIcons['dataIcons_edit'] == true) {
                        echo ' '.Help::display('srv_podatki_urejanje_inline');
                    }
                    echo '&nbsp;&nbsp;';
                    echo '<input type="checkbox" id="dataIcons_write" disabled="disabled"  /><label for="dataIcons_write" class="gray">'.$lang['srv_dataIcons_write'].'</label>';
                    echo '&nbsp;&nbsp;';*/
                    echo '<input type="checkbox" id="dataIcons_labels" disabled="disabled" /><label for="dataIcons_labels" class="gray">'.$lang['srv_dataIcons_labels'].'</label>';
                    
                    if ( self::showMultiple() ) {
                        echo '&nbsp;&nbsp;';
                        echo '<input type="checkbox" id="dataIcons_multiple" disabled="disabled" /><label for="dataIcons_multiple" class="gray">'.$lang['srv_dataIcons_multiple'].'</label>';
                    }
                }
                        
                # preverimo koliko anket je dejansko uporbaniških
                # za potrebne statuse
                $statuses = SurveyStatusProfiles :: getStatusAsArrayString();
    
                $lurkers = false;
                if (is_array($statuses) && count($statuses) > 0) {
                    # najprej preverimo ali filtriramo lurkereje
                    if (isset($statuses['lurker'])) {
                        $lurkers = true;
                        unset($statuses['lurker']);
                    }
    
                    if (count($statuses) > 0) {
                        $sstring = ' AND last_status IN (';
                        foreach ($statuses AS $skey => $status) {
                            if (is_numeric($skey)) {
                                $sstring.=$prefix.$skey;
                            } else if($skey == 'null') {
                                $sstring.=$prefix.'-1';
                            }
                            $prefix = ',';
                        }
                        $sstring .=')';
        
                    }
                }
                
                $q = sisplet_query("SELECT count(*) FROM srv_user WHERE ank_id = '".self::$sid."' AND user_id  > 0 AND deleted='0' ".$sstring);
                list($cnt) = mysqli_fetch_row($q);

                if ($cnt > 0) {
                    echo '<span class="spaceLeft">';
                    self::displayOnlyCMS();
                    echo '</span>';
                }
                    
                echo '</div>';
                echo '<div class="clr"></div>';
            }
            
            echo '</div>';

						
			# ali imamo testne podatke
			if (self::$_HAS_TEST_DATA) {
                # izrišemo bar za testne podatke
                $SSH = new SurveyStaticHtml(self::$sid);
				$SSH -> displayTestDataBar();
			}
		}
		else {
			print_r("Anketa je bila izbrisana! Prikaz podatkov ni mogoč!");
		}
	}

	public static function displayPaginacija($position='_top') {
		global $lang, $site_url;
		
		if ((int)self::$_RECORD_COUNT  == 0) {
			return false;
		}
		
		# ŠTEVILO VNOSOV NA STRAN
		echo '<div id="div_paginacija_vnosov">';
		echo '<label>'.$lang['srv_show_inserts'].'</label>';
		if (self::$_VARS[VAR_REC_ON_PAGE] > self::$_RECORD_COUNT) {
			self::$_VARS[VAR_REC_ON_PAGE] = 'all';
		}

		//$_tmp_limit = array(10,50,100,250,500,1000,2500,5000,10000);
		$_tmp_limit = array(10,50,100,250,500);
		$_select_records = '<select id="rec_on_page'.$position.'" onchange="setDataView(\''.VAR_REC_ON_PAGE.'\',$(\'select#rec_on_page'.$position.' option:selected\').val());" title="'.$lang['srv_data_pagination_rec_on_page'].'">';
		foreach ($_tmp_limit AS $limit) {
			if ($limit < self::$_RECORD_COUNT) {
				$_select_records .= '<option '.(self::$_VARS[VAR_REC_ON_PAGE] == $limit ? ' selected="selected"' : '').' value="'.$limit.'">';
				$_select_records .= $limit;
				$_select_records .= '</option>';
			}
		}
		// Opcija "vsi" - ni na voljo ce je vec kot 1000 responsov (drugace lahko vse zasteka)
		if(self::$_RECORD_COUNT <= 1000){
			$_select_records .= '<option '.(self::$_VARS[VAR_REC_ON_PAGE] == 'all' ? ' selected="selected"' : '').' value="all">';
			$_select_records .= $lang['srv_vsi'];
			$_select_records .= '</option>';
		}
		
		$_select_records .= '</select>';
		
		echo $_select_records;

		
		# KATERA STRAN
		if (self::$_VARS[VAR_REC_ON_PAGE] != 'all' && self::$_TOTAL_PAGES > 1) {
			
			echo '<label>';
			echo $lang['page'];
			echo '</label>';
			
			// puscica levo		
			if (self::$_VARS[VAR_CUR_REC_PAGE] > 1){
				echo '<a title="'.$lang['previous_page'].'" href="#" onclick="setDataView(\''.VAR_CUR_REC_PAGE.'\',\''.(self::$_VARS[VAR_CUR_REC_PAGE]-1).'\');"><span class="faicon arrow2_l"></span></a>';
			}

			$_records_page = '<select id="cur_rec_page'.$position.'" onchange="setDataView(\''.VAR_CUR_REC_PAGE.'\',$(\'select#cur_rec_page'.$position.' option:selected\').val());" title="'.$lang['srv_data_pagination_rec_current_page'].'">';
			for ($i=1; $i<=self::$_TOTAL_PAGES; $i++) {
				$_records_page .= '<option'.(self::$_VARS[VAR_CUR_REC_PAGE]==$i ? ' selected="selected"' : '' )
				. ' value="'.$i.'" >';
				$_records_page .= $i;
				$_records_page .= '</option>';
					
			}
			$_records_page .= '</select>';
			echo $_records_page;
			
			// puscica desno
			if (self::$_VARS[VAR_CUR_REC_PAGE] < self::$_TOTAL_PAGES){
				echo '<a title="'.$lang['next_page'].'" href="#" onclick="setDataView(\''.VAR_CUR_REC_PAGE.'\',\''.(self::$_VARS[VAR_CUR_REC_PAGE]+1).'\');"><span class="faicon arrow2_r"></span></a>';
			}
		}

		echo '</div>';

		
		# ŠTEVILO SPREMENLJIVK NA STRAN
		echo '<div id="div_paginacija_vprasanj">';
		echo '<label>'.$lang['srv_show_questions'].'</label>';

		self::getQuestionCount();
		if (self::$_VARS[VAR_SPR_LIMIT] > self::$_ALL_QUESTION_COUNT) {
			self::$_VARS[VAR_SPR_LIMIT] = 'all';
		}

		//$_spr_limit = array(5=>'5',10=>'10',20=>'20',30=>'30',50=>'50',100=>'100','all'=>$lang['hour_all2']);
		$_spr_limit = array(5=>'5',10=>'10',20=>'20',30=>'30',50=>'50');
		$_spr_on_page = '<select id="spr_on_page'.$position.'" onchange="setDataView(\''.VAR_SPR_LIMIT.'\',$(\'select#spr_on_page'.$position.' option:selected\').val());" title="'.$lang['srv_data_pagination_spr_on_page'].'">';
		foreach ($_spr_limit AS $key => $label) {
			if ($key < self::$_ALL_QUESTION_COUNT) {
				$_spr_on_page .= '<option '.(self::$_VARS[VAR_SPR_LIMIT] == $key ? ' selected="selected"' : '').' value="'.$key.'">';
				$_spr_on_page .= $label;
				$_spr_on_page .= '</option>';
			}
		}
		// Opcija "vsi" - ni na voljo ce je vec kot 50 vprasanj (drugace lahko vse zasteka)
		if(self::$_ALL_QUESTION_COUNT <= 50){
			$_spr_on_page .= '<option '.(self::$_VARS[VAR_SPR_LIMIT] == 'all' ? ' selected="selected"' : '').' value="all">';
			$_spr_on_page .= $lang['hour_all2'];
			$_spr_on_page .= '</option>';
		}
		
		$_spr_on_page .= '</select>';
		
		echo $_spr_on_page;
		
		
		# KATERA STRAN
		// prestejemo stevilo vprasanj
		$questions = count(self::$_SVP_PV);
		if (self::$_VARS[VAR_SPR_LIMIT] != 'all') {
		
			$spr_pages = bcdiv($questions, self::$_VARS[VAR_SPR_LIMIT]);
			
			if (bcmod($questions, self::$_VARS[VAR_SPR_LIMIT]) > 0)
				$spr_pages += 1;
				
			if ($spr_pages > 1) {

				$_spr_page = '<select id="cur_spr_page'.$position.'" onchange="setDataView(\''.VAR_SPR_PAGE.'\',$(\'select#cur_spr_page'.$position.' option:selected\').val());" title="'.$lang['srv_data_pagination_spr_current_page'].'">';
				echo '<label>';
				echo $lang['page'];
				echo '</label>';
				
				// puscica levo
				if (self::$_VARS[VAR_SPR_PAGE] > 1){
					echo '<a title="'.$lang['previous_page'].'" href="#" onclick="setDataView(\''.VAR_SPR_PAGE.'\',\''.(self::$_VARS[VAR_SPR_PAGE]-1).'\');"><span class="faicon arrow2_l"></span></a>';
				}
				
				for ($i=1; $i<=$spr_pages; $i++) {
					$_spr_page .= '<option'.(self::$_VARS[VAR_SPR_PAGE]==$i ? ' selected="selected"' : '' )
					.' value="'.$i.'"'
					.'>';
					$_spr_page .= $i;
					$_spr_page .= '</option>';

				}
				$_spr_page .= '</select>';
				echo $_spr_page;

				// puscica desno
				if (self::$_VARS[VAR_SPR_PAGE] < $spr_pages){
					echo '<a title="'.$lang['next_page'].'" href="#" onclick="setDataView(\''.VAR_SPR_PAGE.'\',\''.(self::$_VARS[VAR_SPR_PAGE]+1).'\');"><span class="faicon arrow2_r"></span></a>';
				}
				
			}
		}
		echo '</div>';
	}
	
	public static function displayLeftFilters() {
        global $lang, $site_url;
        
		// število zapisov na stran
        echo '<ul class="">';
        
		echo '<li>'.$lang['srv_show'].':</li>';
        
		// ustreznost
		if (self::$canDisplayRelevance) {
            
            echo '<li>';
            
            if (self::$_VARS[VAR_SHOW_SYSTEM] != true) {
				echo '<label for="var_relevance"><input type="checkbox" onchange="setDataView(\''. VAR_RELEVANCE.'\',$(this).is(\':checked\'))" '.( self::$_VARS[VAR_RELEVANCE] ? ' checked="checked"' : '').' id="var_relevance" />'.$lang['srv_displaydata_relevance'].'</label>';
            } 
            else {
				echo '<label for="var_relevance" class="gray"><input type="checkbox" disabled="disabled" />'.$lang['srv_displaydata_relevance'].'</label>';
            }
            
			echo '</li>';
        }
        
		// email prikazujemo skupaj z ustreznost
		if ((int)self::$survey['email'] == 1 && (int)self::$survey['user_base'] == 1) {
            
            echo '<li>';

            if (self::$_VARS[VAR_SHOW_SYSTEM] != true ) {
				echo '<label for="var_email"><input type="checkbox" onchange="setDataView(\''. VAR_EMAIL.'\',$(this).is(\':checked\'))" '.( self::$_VARS[VAR_EMAIL] ? ' checked="checked"' : '').' id="var_email" />'.$lang['srv_displaydata_invitation'].'</label>';
            } 
            else {
				echo '<label for="var_email" class="gray"><input type="checkbox" disabled="disabled" id="var_email" />'.$lang['srv_displaydata_invitation'].'</label>';
            }
            
			echo '</li>';
        }
        
		// podatki
		echo '<li>';
		echo '  <input type="checkbox" onclick="setDataView(\''.VAR_DATA.'\',$(this).is(\':checked\'))" '.( self::$_VARS[VAR_DATA] ? ' checked="checked"' : '').(self::$_VARS[VAR_SHOW_SYSTEM] ? ' disabled' : '').' id="data" /><label for="data" '.(self::$_VARS[VAR_SHOW_SYSTEM] ? ' class="gray"' : '').'>'.$lang['srv_displaydata_data'].'</label>';
		echo '</li>';
		
		// meta        
		echo '<li>';
		echo '  <input type="checkbox" onclick="setDataView(\''.VAR_METAFULL.'\',$(this).is(\':checked\'))" '.( self::$_VARS[VAR_METAFULL] ? ' checked="checked"' : '').(self::$_VARS[VAR_SHOW_SYSTEM] ? ' disabled' : '').' id="fullmeta" /><label for="fullmeta" '.(self::$_VARS[VAR_SHOW_SYSTEM] ? ' class="gray"' : '').'>'.$lang['srv_displaydata_meta'].'</label>';
		echo '</li>';

		// če imamo sistemske podatke katere moramo prikazovati ločeno - IDENTIFIKATORJI
		if (!isset(self::$_HEADERS['_settings']['count_system_data_variables'])	|| (isset(self::$_HEADERS['_settings']['count_system_data_variables']) && (int)self::$_HEADERS['_settings']['count_system_data_variables'] > 0)) {
            
            echo '<li>';
			echo '  <label><input type="checkbox" onclick="setDataView(\''.VAR_SHOW_SYSTEM.'\',$(this).is(\':checked\'))" '.( self::$_VARS[VAR_SHOW_SYSTEM] ? ' checked="checked"' : '').' id="showsystem" />'.$lang['srv_displaydata_system_data'].'</label>';
			echo '</li>';
		}
		// Po novem vedno prikazemo checkbox identifikatorji - samo je odkljukan in disablan
		else{
			echo '<li>';
			echo '  <label class="gray"><input type="checkbox" checked="checked" disabled="disabled" id="showsystem" />'.$lang['srv_displaydata_system_data'].'</label>';
			echo '</li>';
		}
		
		// datum
		echo '<li>';
		echo '<label '.(self::$_VARS[VAR_SHOW_SYSTEM] ? ' class="gray"' : '').'><input type="checkbox" onclick="setDataView(\''.VAR_SHOW_DATE.'\',$(this).is(\':checked\'))" '.( self::$_VARS[VAR_SHOW_DATE] ? ' checked="checked"' : '').(self::$_VARS[VAR_SHOW_SYSTEM] ? ' disabled' : '').' id="showdate" />'.$lang['srv_data_date'].'</label>';
        echo '</li>';
        
		// zaporedna številka
		echo '<li>';
		echo '<label '.(self::$_VARS[VAR_SHOW_SYSTEM] ? ' class="gray"' : '').'><input type="checkbox" onclick="setDataView(\''.VAR_SHOW_NO.'\',$(this).is(\':checked\'))" '.( self::$_VARS[VAR_SHOW_NO] ? ' checked="checked"' : '').(self::$_VARS[VAR_SHOW_SYSTEM] ? ' disabled' : '').' id="showno" />'.$lang['srv_recnum'].'</label>';
		echo '</li>';
		
		// pomoč - ?
		echo '<li>'.Help :: display('displaydata_checkboxes').'</li>';
		echo '</ul>';
	}

	// Search po tabeli s podatki
	public static function displayDataSearch(){
		global $lang;
		
		$search = isset($_SESSION['sid_'.self::$sid]['data_search_filter']) ? $_SESSION['sid_'.self::$sid]['data_search_filter'] : '';

		echo '<div id="data_search_filter">';	
		
		echo '<label>'.$lang['srv_find'].':</label> <input id="data_search_value" type="text" onchange="data_search_filter(); return false;" value="'.$search.'">';		
		if($search != ''){
			echo '<span class="bold red spaceLeft">'.$lang['srv_displayData_search'].' "'.$search.'"!</span>';
		}
		
		echo '</div>';
	}
	
	// Shranimo iskanje v session
	private function setDataSearchFilter(){
		
		session_start();
		
		$search = (isset($_POST['value']) && $_POST['value'] != '') ? trim($_POST['value']) : '';		
		if($search != ''){
			$_SESSION['sid_'.self::$sid]['data_search_filter'] = $search;
		}
		else{
			$_SESSION['sid_'.self::$sid]['data_search_filter'] = '';
		}
		
		session_commit();
		
		// Na koncu se popravimo paginacijo na prvo stran
		SurveySession::sessionStart(self::$sid);
		SurveySession::append('data_view_settings','cur_rec_page',1);
		
		return;
	}
	
	
	// Prikazemo editiranje na dnu (brisanje vecih hkrati...)
	public static function displayBottomEdit(){
		global $lang;
		if ((int)self::$_RECORD_COUNT  > 0){
			echo '<div id="bottom_edit" class="floatLeft'.( self::$displayEditIcons['dataIcons_quick_view'] == true ? '' : ' shifted').'">';
			
			echo '<span class="faicon arrow_up"></span> ';
			echo '<span id="switch_on"><a href="javascript:selectAll(1);">'.$lang['srv_select_all'].'</a></span>';
			echo '<span id="switch_off" style="display:none;"><a href="javascript:selectAll(0);">'.$lang['srv_deselect_all'].'</a></span>';
			echo '&nbsp;&nbsp;<a href="#" onClick="deleteMultipleData();"><span class="faicon delete_circle icon-orange" title="'.$lang['srv_delete_data_multirow'].'"/></span>&nbsp;'.$lang['srv_delete_selected'].'</a>';
			echo '<p>'.$lang['srv_delete_infotext'].' '.Help::display('srv_delete_infotext').'</p>';
			
			echo '</div>';
		}
	}

	/** Naredi output podatkov v HTML tabelo
	 *
	 */
	public static function displayVnosiHTML() {
		global $lang;
		global $site_path;
		global $global_user_id;

		// na vrhu in na dnu izrisemo paginacijo
		if(self::$dataFileStatus != FILE_STATUS_NO_DATA && (int)self::$_RECORD_COUNT  > 0) {
			echo '<div id="vnosi_paginacija" class="top_paginacija">';
			self::displayPaginacija($position='_top');
			echo '</div>';
			
			// Izrisemo search
			if(!self::$_VARS[VAR_CODING])
				self::displayDataSearch();
		

			// Checkboxa za urejanje in izpise podatkov ter razpiranje dodatnih nastavitev
			echo '<div class="dataSettingsBasic">';

			echo '<input type="checkbox" id="dataIcons_edit" onchange="changeDataIcons(); return false;"'.(self::$displayEditIcons['dataIcons_edit'] == true ? ' checked="checekd"' : '').'/><label for="dataIcons_edit">'.$lang['srv_dataIcons_edit'].'</label>';
			if (self::$displayEditIcons['dataIcons_edit'] == true) {
				echo ' '.Help::display('srv_podatki_urejanje_inline');
			}
			echo '&nbsp;&nbsp;';
			
            // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
            $userAccess = UserAccess::getInstance($global_user_id);       
            echo '<input type="checkbox" id="dataIcons_write" onchange="changeDataIcons(); return false;"'.(self::$displayEditIcons['dataIcons_write'] == true ? ' checked="checked"' : '').' '.(!$userAccess->checkUserAccess($what='data_export') ? 'disabled="disabled"' : '').' /><label for="dataIcons_write" '.(!$userAccess->checkUserAccess($what='data_export') ? 'class="user_access_locked"' : '').'>'.$lang['srv_dataIcons_write'].'</label>';

			$arrow = (isset($_SESSION['sid_' . self::$sid]['dataIcons_settings'])) ? $_SESSION['sid_' . self::$sid]['dataIcons_settings'] : 0;
			echo '<div id="toggleDataCheckboxes" ' . $borderLeft . ' onClick="toggleDataCheckboxes(\'data\');"><span class="faicon ' . ($arrow == 1 ? ' dropup_blue' : 'dropdown_blue') . '"></span> ' . $lang['srv_extra_settings'] . '</div>';

			echo '</div>';
		}

        echo '<br class="clr" />';
        

        echo '<div id="displayFilterNotes">';
        
		# če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
		SurveyTimeProfiles :: printIsDefaultProfile();
		
		# če nismo v identifikatorjih
		if (self::$_VARS[VAR_SHOW_SYSTEM] == false) {
			# če imamo filter ifov ga izpišemo
			SurveyConditionProfiles:: getConditionString();
	
			# če imamo filter ifov za inspect ga izpišemo
			$SI = new SurveyInspect(self::$sid);
			$SI->getConditionString();
				
			# če imamo filter spremenljivk ga izpišemo
			SurveyVariablesProfiles:: getProfileString();
        }
        else{
            echo '<p>'.$lang['srv_data_settings_identifier_notice'].'</p>';
        }
		# če imamo rekodiranje
		$SR = new SurveyRecoding(self::$sid);
        $SR -> getProfileString();
        
		echo '</div>';



   


        $folder = $site_path . EXPORT_FOLDER.'/';

        echo '<div id="div_vnosi_data">';

        if ((self::$dataFileStatus == 1 || self::$dataFileStatus == 0) && self::$dataFileName !== null) {
            # filtri morajo prikazovat vsaj eno spremenljivko ali meta podatek
            if (count(self::$_SVP_PV) > 0) {

                # če imamo kaj podatkov za prikaz
                if ((int)self::$_RECORD_COUNT > 0){

                    if (self :: $is_social_network == false) {
                        if ( self::showMultiple() && self::$displayEditIcons['dataIcons_multiple'] )
                            self::DisplayDataMultipleTable();
                        else
                            self::DisplayDataTable(); 
                    } 
                    else {
                        # imamo SN omrežje
                        if (self::$_VARS[VAR_CIRCLES] == 0) {
                        self::DisplaySnLinks();
                        self::DisplayDataTable();
                        }
                    }
                }
                # ni vrstic za prikaz
                else{	
                    echo $lang['srv_data_no_data_filtred'];
                }
            } 
            else {
                echo '<br /><div style="margin: 0 0 40px 0;">Ni podatkov za prikaz. Preverite filtre (Podatki, Para podatki, Polni para podatki)</div>';
            }
        }
            
        if (self :: $is_social_network ) {
            if (self::$SSNDF != null && self::$_VARS[VAR_CIRCLES] == 1) {
                self::DisplaySnLinks();
                self::$SSNDF->outputSNDataFile();
            }
        }

        echo '</div>'; // id="div_vnosi_data">';

        
        #izrišemo legendo statusov
        self::displayStatusLegend();
        self::displayMetaStatusLegend();
        self::displayTestLegend();


		// na vrhu in na dnu izrisemo paginacijo
		if(self::$dataFileStatus != FILE_STATUS_NO_DATA){
			echo '<div id="vnosi_paginacija" class="bottom_paginacija">';
			self::displayPaginacija($position='_bottom');
			echo '</div>';

			echo '<div class="clr"></div>';
		}
		
		// osvetlimo stolpec s spremenljivko
		if (isset($_GET['highlight_spr'])) {
			?><script>
				highlight_spremenljivka(<?=(int)$_GET['highlight_spr']?>);
			</script><?php
		}
		
		// osvetlimo vrstice s spremembami
		if (isset($_GET['highlight_usr'])) {
			$high = explode('-', $_GET['highlight_usr']);
			?><script>
				highlight_user([<?=implode(',',$high)?>]);
			</script><?php
		}
		
		// prikazujemo labele podatkov
		if ( self::$displayEditIcons['dataIcons_labels'] ) {
			?><script>
				data_show_labels();
			</script><?php
		}
	}


	public static function DisplayDataTable() {
		global $lang, $site_path;

		if ( self::$dataFileStatus == FILE_STATUS_OLD) {
			echo "Posodobljeno: ".date("d.m.Y, H:i:s", strtotime(self::$dataFileUpdated));
		}

		$folder = $site_path . EXPORT_FOLDER.'/';

		// paginacija spremenljivk
		if(self::$_VARS['spr_limit'] == 'all'){
			$_spr_on_pages_start = 0;
			$_spr_on_pages_stop = self::$_VARS['spr_page'];
		}		
		else{
			$_spr_on_pages_start = self::$_VARS['spr_page'] * self::$_VARS['spr_limit'] - self::$_VARS['spr_limit'];
			$_spr_on_pages_stop = self::$_VARS['spr_page'] * self::$_VARS['spr_limit'];
		}

		#preberemo HEADERS iz datoteke
		self::$_HEADERS = unserialize(file_get_contents(self::$headFileName));
		// vrinemo userid na začetku ki ga potem skrivamo.
		$_svp_pv['uid'] = 'uid';
		//self::$_SVP_PV = array_merge($_svp_pv, self::$_SVP_PV);

		
		#izpišemo tabelo
		echo '<div style="padding-top:0px; height:5px;" class="clr">&nbsp;</div>';
		
		echo '<div id="tableContainer" class="tableContainer">';
		
		# div v katerem po potrebi prikazujemo gumbe za skrolanje levo in desno
		echo '<div id="dataTableScroller">';
		echo '<span class="faicon arrow_large2_l icon-as_link pointer" onclick="dataTableScroll(\'left\');return false;"></span>';
		echo '&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<span class="faicon arrow_large2_r icon-as_link pointer" onclick="dataTableScroll(\'right\');return false;"></span>';
		echo '</div>';
		
		$display1kaIcon = self::$displayEditIcons['dataIcons_quick_view'] ;

		if (self::$printPreview == true) {
			self::$displayEditIcons['dataIcons_edit'] = false;
			self::$displayEditIcons['dataIcons_write'] = false;
			$display1kaIcon = false;
		}
		# koliko stolpcev je colspan
		$stolpci = ((int)self::$displayEditIcons['dataIcons_edit']*4)
		+ ((int)self::$displayEditIcons['dataIcons_write']*2)
		+ (int)$display1kaIcon;
		
		// Evoli ikona (ce je vklopljen modul)
		if((SurveyInfo::getInstance()->checkSurveyModule('evoli') || SurveyInfo::getInstance()->checkSurveyModule('evoli_employmeter')) && self::$displayEditIcons['dataIcons_write'] == '1')
			$stolpci += 3;
		
		// MFDPS ikona (ce je vklopljen modul)
		if(SurveyInfo::getInstance()->checkSurveyModule('mfdps') && self::$displayEditIcons['dataIcons_write'] == '1')
			$stolpci += 1;
		
		// BORZA ikona (ce je vklopljen modul)
		if(SurveyInfo::getInstance()->checkSurveyModule('borza') && self::$displayEditIcons['dataIcons_write'] == '1')
			$stolpci += 1;

		echo '<input type="hidden" id="tableIconColspan" value="'.($stolpci).'">';
		
		# ali smo v edit načinu ali monitoringu
		$cssEdit = (self::$_VARS[VAR_EDIT] || self::$_VARS[VAR_MONITORING]?' editData':'');
		echo '<table id="dataTable" class="scrollTable no_wrap_td'.$cssEdit.'" '.(self::$_VARS[VAR_EDIT]?' title="'.$lang['srv_edit_data_title'].'"':'').'>';
		
		// Nastavimo colgroup, da na njega vezemo vse sirine v tabeli, zaradi resizinga stolpcev
		echo '<colgroup>';
		# colspan za ikonce
		if ($stolpci > 0) {
			//for ($i=0; $i<$stolpci; $i++)
			//	echo '<col class="data_edit">';
			echo '<col class="data_edit"'.($stolpci > 1 ? (' span="'.$stolpci.'"') : '').'>';
		}

		$spr_cont = 0;
		foreach (self::$_HEADERS AS $spid => $spremenljivka) {
			if (isset(self::$_SVP_PV[$spid]) && count($spremenljivka['grids']) > 0) {
				if(self::$showLineNumber &&  $spr_cont+1 == self::$lineoffset) {
					echo '<col>';
				}
				
				// paginacija spremenljivk
				if (self::$_VARS['spr_limit'] == 'all' || ($spr_cont >= $_spr_on_pages_start && $spr_cont < $_spr_on_pages_stop)) {
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						if (count ($grid['variables']) > 0) {
							foreach ($grid['variables'] AS $vid => $variable ){
								echo '<col seq="'.$variable['sequence'].'"';
								
								if ($spremenljivka['tip'] != 'm' && $spremenljivka['tip'] != 'sm') {
									echo ' spr_id="'.substr($spid, 0, strpos($spid, '_')).'"';
								} else {
									echo ' spr_id="'.$spid.'"';
								}
								
								echo '>';
							}
						}
					}
				}
				$spr_cont++;
			}

		}
		echo '</colgroup>';
		
		echo '<thead class="fixedHeader">';
		echo '<tr>';

		# colspan za ikonce
		if ($stolpci > 0) {
			echo '<th class="data_edit"'.($stolpci > 1 ? (' colspan="'.$stolpci.'"') : '').'>&nbsp;</td>';
		}

		# dodamo skrit stolpec uid
		echo '<th class="data_uid">&nbsp;</th>';

		$spr_cont = 0;
		foreach (self::$_HEADERS AS $spid => $spremenljivka) {

			if (isset(self::$_SVP_PV[$spid])) {
				if(self::$showLineNumber &&  $spr_cont+1 == self::$lineoffset) {
					echo '<th title="'.$lang['srv_line_number'].'" >';
					echo '<div class="headerCell">'.$lang['srv_line_number'].'</div>';
					echo '</th>';
				}
				// 	paginacija spremenljivk
				if (self::$_VARS['spr_limit'] == 'all' || ($spr_cont >= $_spr_on_pages_start && $spr_cont < $_spr_on_pages_stop)) {
					echo '<th colspan="'.$spremenljivka['cnt_all'].'" title="'.$spremenljivka['naslov'].'">';
					echo '<div class="headerCell">'.$spremenljivka['naslov'].'</div>';
					echo '</th>';
				}
				$spr_cont++;
				
			}
		}

		echo '</tr><tr>';

		# colspan za ikonce
		if ($stolpci > 0) {
			echo '<th class="data_edit"'.($stolpci > 1 ? (' colspan="'.$stolpci.'"') : '').'>&nbsp;</td>';
		}

		# dodamo skrit stolpec uid
		echo '<th class="data_uid">&nbsp;</th>';

		$spr_cont = 0;
		foreach (self::$_HEADERS AS $spid => $spremenljivka) {
			if (isset(self::$_SVP_PV[$spid]) && (count($spremenljivka['grids']) > 0 )) {
				
				if(self::$showLineNumber &&  $spr_cont+1 == self::$lineoffset) {
					echo '<th title="'.$lang['srv_line_number'].'" >';
					echo '<div class="headerCell">'.$lang['srv_line_number'].'</div>';
					echo '</th>';
				}
				
				// paginacija spremenljivk
				if (self::$_VARS['spr_limit'] == 'all' || ($spr_cont >= $_spr_on_pages_start && $spr_cont < $_spr_on_pages_stop)) {

					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						if($spremenljivka['tip'] == 16 || $spremenljivka['tip'] == 19 || $spremenljivka['tip'] == 20){
							echo '<th colspan="'.$grid['cnt_vars'].'" title="'.$grid['variable'].'">';
							echo '<div class="headerCell">'.$grid['variable'].'</div>';
							echo '</th>';
						}
						else{
							echo '<th colspan="'.$grid['cnt_vars'].'" title="'.$grid['naslov'].'">';
							echo '<div class="headerCell">'.$grid['naslov'].'</div>';
							echo '</th>';
						}
					}
				}
				$spr_cont++;
			}

		}
		echo '</tr><tr>';

		# colspan za ikonce
		if ($stolpci > 0) {
			//for ($i=0; $i<$stolpci; $i++)
			//	echo '<th class="data_edit">&nbsp;</th>';
			echo '<th class="data_edit"'.($stolpci > 1 ? (' colspan="'.$stolpci.'"') : '').'>&nbsp;</td>';
		}

		# dodamo skrit stolpec uid
		echo '<th class="data_uid">&nbsp;</th>';

		$spr_cont = 0;
		$system_columns = array();
		foreach (self::$_HEADERS AS $spid => $spremenljivka) {
			if (isset(self::$_SVP_PV[$spid]) && count($spremenljivka['grids']) > 0) {
				if(self::$showLineNumber &&  $spr_cont+1 == self::$lineoffset) {
					echo '<th title="'.$lang['srv_line_number'].'" spr_id="lineNo">';
					echo '<div class="dataCell" >'.$lang['srv_line_number'].'</div>';
					echo '</th>';
				}
				
				// paginacija spremenljivk
				if (self::$_VARS['spr_limit'] == 'all' || ($spr_cont >= $_spr_on_pages_start && $spr_cont < $_spr_on_pages_stop)) {
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						if (count ($grid['variables']) > 0) {
							foreach ($grid['variables'] AS $vid => $variable ){
								echo '<th title="'.$variable['naslov'].($variable['other'] ? '&nbsp;(text)' : '').'"'
								.' seq="'.$variable['sequence'].'"';
								
								if ($spremenljivka['tip'] != 'm' && $spremenljivka['tip'] != 'sm') {
									echo ' spr_id="'.substr($spid, 0, strpos($spid, '_')).'"';
								} else {
									echo ' spr_id="'.$spid.'"';
								}
								echo ($spremenljivka['inline_edit']?' 	inline_edit=1':'')
								//												.' inline_edit='.($spremenljivka['inline_edit']?$spremenljivka['inline_edit']:'0')
								.($variable['sequence'] == self::$sort_seq && self::$sort_seq != null ? ' class="hover '.self::$sort_type.'"': '')
								.'>';
								
								if ($variable['sequence'] == self::$sort_seq && self::$sort_seq != null) {
									$img_src = self::$sort_type == 'sort_dsc' ? 'sort_descending' : 'sort_ascending' ;
									echo '<span class="sort_holder"><span class="faicon '.$img_src.'" title=""></span></span>';
								}

								// Zabelezimo sekvenco sistemskih identifikatorjev da jih pobarvamo
								if($spremenljivka['is_system'] == 1)
									$system_columns[] = $spremenljivka['sequences'];
								
								echo '<div class="dataCell">'.$variable['naslov'];
								if ($variable['other'] == 1) {
									echo '&nbsp;(text)';
								}
								
								/*// urejanje kalkulacije -- izracunane vrednosti v podatkih
								 if ($spremenljivka['tip'] == 22) {
								echo ' <a href="" onclick="calculation_editing(\'-'.substr($spid, 0, strpos($spid, '_')).'\'); return false;">('.$lang['edit3'].')</a>';
								}*/

								echo '</div>';

								echo '</th>';
							}
						}
					}
				}
				$spr_cont++;
			}

		}
		echo'</tr>';
		echo '</thead>';
		
		
		# PREBEREMO PODATKE
		$_command = '';
		
		# najprej po potrebi presortiramo
		# na vindowsih ne delamo sorta (zaenkrat)
		if (self::$do_sort == true) {

			$sortString = '-k '.self::$sort_seq;
			
			#ker tekstovnih ne sortira vredu sem odstranil parameter -n
			# iz navodil: sorting keys can be interpreted numerically (-n option) instead of alphabetically (which is the default). 
			# če bodo težave bo potrebno parameter -n dodajat po potrebi 
			# $sort_numeric => 
			$sort_numeric = ''; 
			//$sort_numeric = '-n ';
			
			foreach (self::$_HEADERS AS $spid => $spremenljivka) {
				if (count($spremenljivka['grids']) > 0 ) {
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						if (count ($grid['variables']) > 0) {
							foreach ($grid['variables'] AS $vid => $variable ){
								if (self::$sort_seq == $variable['sequence']) {
									
									// za datumska polja je potrebno malo potelovadit
									if ($variable['spss'] == 'DATETIMEw' || $variable['sortType'] == 'date') {
										#12.09.2011
										$sortString = '-k '.self::$sort_seq.'.7,'.self::$sort_seq.'.10 -k'.self::$sort_seq.'.4,'.self::$sort_seq.'.5 -k'.self::$sort_seq.'.1,'.self::$sort_seq.'.2';
									}
									
									// za numericne spremenljivke in recnum uporabimo parameter -n da ne sortira po stringu
									if ($variable['sortType'] == 'number') {
										$sort_numeric = '-n ';
									}
								}
							}
						}
					}
				}
			}
			
			if (IS_WINDOWS) {
				#Cygwin Sort Command On Windows
				# popravi pot do svojega sort-a
				$_path_to_CygwinSort = PATH_TO_CYGWIN_FOLDER;
				# $_command = $_path_to_CygwinSort.' -t"'.STR_DLMT.'"'.(self::$sort_type == 'sort_dsc' ? '-r' : '').' +'.(int)(self::$sort_seq-1).' '.self::$dataFileName.'';
				$_command = $_path_to_CygwinSort.' -t"'.STR_DLMT.'" '.(self::$sort_type == 'sort_asc' ? '' : '-r ').$sort_numeric.$sortString.' '.self::$dataFileName;
			} else {
				# smo na linuxu
				$_command = 'sort -t \\'.STR_DLMT.' '.(self::$sort_type == 'sort_asc' ? '' : '-r ' ).$sort_numeric.$sortString.' '.self::$dataFileName;
			}
		}
		// polovimo vrstice z statusom 5,6 in jih damo v začasno datoteko
		if (IS_WINDOWS) {
			#$cmdLn1 = 'awk -F"'.STR_DLMT.'" "BEGIN {OFS=\"\x7C\"} '.self::$_CURRENT_STATUS_FILTER.' { print $0 }" '.self::$dataFileName.' > '.$tmp_files['filtred_status'];
			#$out1 = shell_exec($cmdLn1);
			# če smo predhodno sortirali
			if (self::$do_sort == true) 
			{
				$_command .= ' | gawk -F"'.STR_DLMT.'" "BEGIN {OFS=\"\x7C\"} '.self::$_CURRENT_STATUS_FILTER.' { print $0 }" ';
				#$_command = 'awk -F"'.STR_DLMT.'" "BEGIN {OFS=\"\x7C\"} '.self::$_CURRENT_STATUS_FILTER.' { print $0 }" '.self::$dataFileName;
			}
			else 
			{
				$_command = 'gawk -F"'.STR_DLMT.'" "BEGIN {OFS=\"\x7C\"} '.self::$_CURRENT_STATUS_FILTER.' { print $0 }" '.self::$dataFileName;
			}

		} else {
			#$cmdLn1 = 'awk -F"'.STR_DLMT.'" \'BEGIN {OFS="\x7C"} '.self::$_CURRENT_STATUS_FILTER.' { print $0 }\' '.self::$dataFileName.' > '.$tmp_files['filtred_status'];
			#$out1 = shell_exec($cmdLn1);
			# če smo predhodno sortirali
			if (self::$do_sort) 
			{
				$_command .= ' | awk -F"'.STR_DLMT.'" \'BEGIN {OFS="\x7C"} '.self::$_CURRENT_STATUS_FILTER.' { print $0 }\' ';
			} 
			else 
			{
				$_command = 'awk -F"'.STR_DLMT.'" \'BEGIN {OFS="\x7C"} '.self::$_CURRENT_STATUS_FILTER.' { print $0 }\' '.self::$dataFileName;
			}

		}
		
		// pobrisemo vrstice ki ne vsebujejo iskalnega stringa (ce searchamo) in rdece boldamo rezultat
		$search = isset($_SESSION['sid_'.self::$sid]['data_search_filter']) ? $_SESSION['sid_'.self::$sid]['data_search_filter'] : '';
		if($search != ''){
			if (IS_WINDOWS) {
				$_command .= ' | sed "/'.$search.'/!d"';
				$_command .= ' | sed "s*'.$search.'*<span class=\"highlighted\">'.$search.'</strong>*g"';
			} else {
				$_command .= ' | sed \'/'.$search.'/!d\'';
				$_command .= ' | sed \'s*'.$search.'*<span class=\"highlighted\">'.$search.'</strong>*g\'';
			}
		}
		
		// paginacija po stolpcih (spremenljivkah)
		if (IS_WINDOWS) {
			#$cmdLn1_1 = 'cut -d "|" -f 1,'.self::$_VARIABLE_FILTER.' '.$tmp_files['filtred_status'].' > '.$tmp_files['filtred_spr_pagination'];
			#$out1 = shell_exec($cmdLn1_1);
			$_command .= ' | cut -d "|" -f 1,'.self::$_VARIABLE_FILTER;
		} else {
			#$cmdLn1_1 = 'cut -d \'|\' -f 1,'.self::$_VARIABLE_FILTER.' '.$tmp_files['filtred_status'].' > '.$tmp_files['filtred_spr_pagination'];
			#$out1 = shell_exec($cmdLn1_1);
			$_command .= ' | cut -d \'|\' -f 1,'.self::$_VARIABLE_FILTER;
		}

		if (self::$_REC_LIMIT != '') {
			#paginating
			if (IS_WINDOWS) {
			#$cmdLn2 = 'awk '.self::$_REC_LIMIT.' '.$tmp_files['filtred_spr_pagination'].' > '.$tmp_files['filtred_pagination'];
			#$out2 = shell_exec($cmdLn2);
			$_command .= ' | awk '.self::$_REC_LIMIT;
		} else {
			#$cmdLn2 = 'awk '.self::$_REC_LIMIT.' '.$tmp_files['filtred_spr_pagination'].' > '.$tmp_files['filtred_pagination'];
			#$out2 = shell_exec($cmdLn2);
			$_command .= ' | awk '.self::$_REC_LIMIT;
		}
		#$file_sufix = 'filtred_pagination';
		} else {
			#$file_sufix = 'filtred_spr_pagination';
		}
		
		// zamenjamo | z </td><td>
		if (IS_WINDOWS) {
			#$cmdLn3 = 'sed "s*'.STR_DLMT.'*</td><td>*g" '.$tmp_files[$file_sufix].' > '.$tmp_files['filtred_sed'];
			#$out3 = shell_exec($cmdLn3);
			$_command .= ' | sed "s*'.STR_DLMT.'*'.STR_LESS_THEN.'/td'.STR_GREATER_THEN.STR_LESS_THEN.'td'.STR_GREATER_THEN.'*g" >> '.$folder.'tmp_export_'.self::$sid.'_data'.TMP_EXT;

		} else {
			#$cmdLn3 = 'sed \'s*'.STR_DLMT.'*</td><td>*g\' '.$tmp_files[$file_sufix].' > '.$tmp_files['filtred_sed'];
			#$out3 = shell_exec($cmdLn3);
			$_command .= ' | sed \'s*'.STR_DLMT.'*</td><td>*g\' >> '
			.$folder.'tmp_export_'.self::$sid.'_data'.TMP_EXT;	
		}	
		
		if (IS_WINDOWS) {
			# ker so na WINsih težave z sortom, ga damo v bat fajl in izvedemo :D
			$file_handler = fopen($folder.'cmd_'.self::$sid.'_to_run.bat',"w");
			fwrite($file_handler,$_command);
			fclose($file_handler);
			$out_command = shell_exec($folder.'cmd_'.self::$sid.'_to_run.bat');
			unlink($folder.'cmd_'.self::$sid.'_to_run.bat');
		} else {
			$out_command = shell_exec($_command);
		}
		
		echo '<tbody class="scrollContent'.(self::$_VARS[VAR_CODING]?' coding':'').'">';
		#$f = fopen ($tmp_files['filtred_sed'], 'r');
		if (file_exists($folder.'tmp_export_'.self::$sid.'_data'.TMP_EXT)) {
			
			if(self::$_VARS[VAR_REC_ON_PAGE] == 'all'){
				$up = 0;
				$low = 1;
			}		
			else{
				$up = self::$_VARS[VAR_REC_ON_PAGE] * self::$_VARS[VAR_CUR_REC_PAGE];
				$low = $up - self::$_VARS[VAR_REC_ON_PAGE]+1;
			}
						
			$cntLines=$low ;
			$f = fopen ($folder.'tmp_export_'.self::$sid.'_data'.TMP_EXT, 'r');
			while ($line = fgets ($f)) {
				
				echo '<tr>';

				if ((int)$display1kaIcon == 1) {
					echo '<td class="enkaIcon" title="'.$lang['srv_view_data_row_quick'].'"><span class="faicon quick_view icon-as_link"></span></td>';
				}
				if ($stolpci > 0 ) {
					if (self::$displayEditIcons['dataIcons_edit'] == true) {
						// checkbox za brisanje vecih vrstic hkrati
						echo '<td class="data_edit" title="'.$lang['srv_view_data_row_select'].'"><input type="checkbox" class="delete_data_row" /></td>';
						echo '<td class="data_edit"><span class="faicon delete_circle icon-orange_link" title="'.$lang['srv_delete_data_row'].'"/></span></td>';
						echo '<td class="data_edit"><span class="faicon edit_square icon-as_link" title="'.$lang['srv_edit_data_row'].'" /></span></td>';
						echo '<td class="data_edit"><span class="faicon edit smaller icon-as_link" title="'.$lang['srv_edit_data_row_quick'].'" /></span></td>';
					}
					if (self::$displayEditIcons['dataIcons_write'] == true) {
						echo '<td class="data_edit"><span class="faicon pdf icon-as_link" title="'.$lang['srv_view_data_row_pdf'].'"></span></td>';
						echo '<td class="data_edit"><span class="faicon rtf icon-as_link" title="'.$lang['srv_view_data_row_word'].'"></span></td>';
						
						// Evoli ikona (ce je vklopljen modul)
						if(SurveyInfo::getInstance()->checkSurveyModule('evoli')) {
							echo '<td class="data_edit"><span class="sprites evoli_16 evoli icon-as_link" title="Evoli"></span></td>';
							echo '<td class="data_edit"><span class="sprites evoli2_16 evoli2 icon-as_link" title="Evoli - Danish"></span></td>';
							echo '<td class="data_edit"><span class="sprites evoli3_16 evoli3 icon-as_link" title="Evoli - Slovensko"></span></td>';
						}
                        if(SurveyInfo::getInstance()->checkSurveyModule('evoli_employmeter')) {
							echo '<td class="data_edit"><span class="sprites evoli_16 evoliEM icon-as_link" title="Evoli EM"></span></td>';
							echo '<td class="data_edit"><span class="sprites evoli2_16 evoliEM2 icon-as_link" title="Evoli EM - Danish"></span></td>';
							echo '<td class="data_edit"><span class="sprites evoli3_16 evoliEM3 icon-as_link" title="Evoli EM - Slovensko"></span></td>';
						}
                        
						// MFDPS ikona (ce je vklopljen modul)
						if(SurveyInfo::getInstance()->checkSurveyModule('mfdps')) {
							echo '<td class="data_edit"><span class="sprites mfdps_16 mfdps" title="MFDPS"></span></td>';
						}
						
						// BORZA ikona (ce je vklopljen modul)
						if(SurveyInfo::getInstance()->checkSurveyModule('borza')) {
							echo '<td class="data_edit"><span class="sprites borza_16 borza pointer" title="BORZA"></span></td>';
						}
					}
				}

				// URLje v besedilu spremenimo v __hiperlinke__
				$line = stripslashes(self::url_to_link($line));

				# po potrebi vrinemo zaporedno številko
				if (self::$showLineNumber) {
                    $pos = self::getLineNumberCellOffset($line);
                    $line = substr_replace($line, '</td><td>'.$cntLines, $pos, 0);
				}
				
				echo '<td class="data_uid">'.$line.'</td>';		

				echo '</tr>';
				$cntLines++;
			}
		} else {
			echo 'File does not exist (err.No.1)! :'.'tmp_export_'.self::$sid.'_data'.TMP_EXT;
			#echo $folder.'tmp_export_'.self::$sid.'_data'.TMP_EXT;
		}
		echo '</tbody>';
		echo '</table>';
		echo '</div>'; // end div tableContainer

		/**
		 * kliki na ikonice za urejanje in izpis so definirane v script_analiza.js, analiza_init();
		 */

		// JS za urejanje vnosov (click in hover) (funkciji sta definirani v postProcess.js)
		?>
		<script>
		$('#dataTableScroller').followTo($("#dataTable").position().top - $("#dataTableScroller").height()-25);
		dataTableResize(<?=self::$sid?>);
		$('#dataTable').bind('contextmenu', function (event) { data_preview_content(event); return false; } );
		<?php
		if (self::$_VARS[VAR_EDIT] || self::$_VARS[VAR_MONITORING]) 
		{
		?>
			$('#dataTable td').click( function (event) { edit_data(event); } );
			$('#dataTable td').hover( function (event) { edit_data_hover(event) }, function (event) { edit_data_hoverout(event) } );
			edit_data_inline_edit();	// manj utripne, ce takoj za tabelo poklicemo brez cakanja na dom ready
			$('#dataTable tr:nth-child(3) th').hover( function (event) { data_header_hover(event) }, function (event) { data_header_hoverout(event) } );
			$('#dataTable tr:nth-child(3) th').live('click', function(event) { data_header_click(event); } );
		<?php 
		} elseif (self::$_VARS[VAR_CODING]) {
		?>
			$('#dataTable tbody tr td').click( function (event) { coding_click( $(this), event ) } );
			
			$('#dataTable tr:nth-child(3) th').hover( function (event) { data_header_hover(event) }, function (event) { data_header_hoverout(event) } );
			$('#dataTable tr:nth-child(3) th').live('click', function(event) { data_header_click(event); } );
		<?php
		} else {
		?>
			$('#dataTable tr:nth-child(3) th').hover( function (event) { data_header_hover(event) }, function (event) { data_header_hoverout(event) } );
			$('#dataTable tr:nth-child(3) th').live('click', function(event) { data_header_click(event); } );
		<?php 
		} 
			
		?>
		$('#dataTable td.enkaIcon span.quick_view').click( function (event) { showSurveyAnswers(event); } );
		var sort_action_url = '<?php echo 'index.php?anketa='.self::$sid.'&a='.A_COLLECT_DATA.'&m='.self::$subAction.self::getVarsNoSort();?>'
		<?php 
		if (self::$_VARS[VAR_META]) {
			echo  "postProcessAddLurkerTitles(".(self::$_VARS[VAR_RELEVANCE] && self::$canDisplayRelevance  ? (4+(int)self::$_VARS[VAR_EMAIL]) : (3+(int)self::$_VARS[VAR_EMAIL])).");\n";
		}
		
		# pobarvamo celice in dodamo title za statuse
		echo  "postProcessAddTitles();\n";
		echo  "postProcessAddMetaTitles();\n";
		
		// Pobarvamo sistemske identifikatorje
		if(self::$_HEADERS['_settings']['force_show_hiden_system'] == '1')
			echo  "postProcessAddSystem(".json_encode($system_columns).");\n";
		
		if (self::$_VARS[VAR_RELEVANCE] && self::$canDisplayRelevance) {
			echo  "postProcessAddRelevanceTitles();\n";
		}
		if (self::$_VARS[VAR_EMAIL]) {
			echo  "postProcessAddEmailTitles(".(self::$_VARS[VAR_RELEVANCE] && self::$canDisplayRelevance ? 3 : 2).");\n";
		}
		
		?>
		</script>
		<?php
		if ($f) {
			fclose($f);
		}
		if (file_exists($folder.'tmp_export_'.self::$sid.'_data'.TMP_EXT)) {
			unlink($folder.'tmp_export_'.self::$sid.'_data'.TMP_EXT);
		}
		if ($_GET['debug'] == 1) {
			print_r("<pre>".$_command."</pre>");
		}
		
		// Editiranje na dnu - brisanje vec userjev hkrati...
		
		if(self::$dataFileStatus != FILE_STATUS_NO_DATA && (int)self::$displayEditIcons['dataIcons_edit'] == 1){
			self::displayBottomEdit();
		}
	}
	
	static public function DisplayDataMultipleTable() {
		global $lang, $site_path;
		
		if ( self::$dataFileStatus == FILE_STATUS_OLD) {
			echo "Posodobljeno: ".date("d.m.Y, H:i:s", strtotime(self::$dataFileUpdated));
		}
		
		$folder = $site_path . EXPORT_FOLDER.'/';

		// paginacija spremenljivk
		$_spr_on_pages_stop = self::$_VARS['spr_page'] * self::$_VARS['spr_limit'];
		$_spr_on_pages_start = self::$_VARS['spr_page'] * self::$_VARS['spr_limit'] - self::$_VARS['spr_limit'];
        
        $sql = sisplet_query("SELECT s.id, s.tip FROM srv_spremenljivka s, srv_grupa g WHERE s.tip='24' AND s.gru_id=g.id AND g.ank_id='".self::$sid."'");
        if ( mysqli_num_rows($sql) != 1 ) return;
        $row = mysqli_fetch_assoc($sql);
        $parent = $row['id'];
        
        $childs = array();
        $sql1 = sisplet_query("SELECT spr_id FROM srv_grid_multiple WHERE parent='$parent' AND ank_id='".self::$sid."'");
		while ( $row1 = mysqli_fetch_assoc($sql1) ) {
			if ( isset( self::$_SVP_PV[$row1['spr_id'].'_0'] ) )
            	$childs[] = $row1['spr_id'];
        }
        
		#preberemo HEADERS iz datoteke
		self::$_HEADERS = unserialize(file_get_contents(self::$headFileName));
		
        $multiple = array();
        
		foreach (self::$_HEADERS AS $spid => $spremenljivka) {
            
			if ( isset(self::$_SVP_PV[$spid]) && is_numeric($spremenljivka['tip']) ) {
                
                //$spr_id = explode('_', $spid)[0]; // PHP 5.4
                $spr_id = explode('_', $spid);
                $spr_id = $spr_id[0];
                
				if ( in_array($spr_id, $childs) ) {
					$spremenljivka['seq'] = explode('_', $spremenljivka['sequences']);
					$spremenljivka['spr_id'] = $spid;
					$multiple[] = $spremenljivka;
				}
			}
		}
		
		
		$sequences = array(); $subseq = array(); $cols = array();
   			
		for ($spr=0; $spr<count($multiple); $spr++) {
				
			$sequences[$spr] = explode('_', $multiple[$spr]['sequences'] );		// vsi stolpci trenutne spremenljivke (4 - 12)
			$subseq[$spr] = count($multiple[$spr]['grids']);					// stevilo vrstic v vprasanju (4 - 4) (to je za vsa vprasanja enako)
			$cols[$spr] = round(count($sequences[$spr])/$subseq[$spr], 0);		// koliko stolpcev zasede enkratna ponovitev vprasanja (1 - 3)
			
			#echo "\n\r vars: ".count($sequences[$spr]).' '.$subseq[$spr].' '.$cols[$spr].' '.$dataoffset."\n\r";
			
		}
		
		
		$_svp_pv['uid'] = 'uid';
		//self::$_SVP_PV = array_merge($_svp_pv, self::$_SVP_PV);
		#izpišemo tabelo
		echo '<br/>';
		echo '<div id="tableContainer" class="tableContainer">';
		
		# div v katerem po potrebi prikazujemo gumbe za skrolanje levo in desno
		echo '<div id="dataTableScroller">';
		echo '<span class="pointer halfCircleLeft" onclick="dataTableScroll(\'left\');return false;">&lt;</span>';
		echo '&nbsp;';
		echo '<span class="pointer halfCircleRight" onclick="dataTableScroll(\'right\');return false;">&gt;</span>';
		echo '</div>';
		
		$display1kaIcon = self::$displayEditIcons['dataIcons_quick_view'] ;

		if (self::$printPreview == true) {
			self::$displayEditIcons['dataIcons_edit'] = false;
			self::$displayEditIcons['dataIcons_write'] = false;
			$display1kaIcon = false;
		}
		# koliko stolpcev je colspan
		$stolpci = ((int)self::$displayEditIcons['dataIcons_edit']*4)
		+ ((int)self::$displayEditIcons['dataIcons_write']*2)
		+ (int)$display1kaIcon ;

		echo '<input type="hidden" id="tableIconColspan" value="'.($stolpci).'">';
		# ali smo v edit načinu ali monitoringu
		$cssEdit = (self::$_VARS[VAR_EDIT] || self::$_VARS[VAR_MONITORING]?' editData':'');
		echo '<table id="dataTable" class="scrollTable no_wrap_td'.$cssEdit.'" '.(self::$_VARS[VAR_EDIT]?' title="'.$lang['srv_edit_data_title'].'"':'').'>';
		
		// Nastavimo colgroup, da na njega vezemo vse sirine v tabeli, zaradi resizinga stolpcev
		echo '<colgroup>';
		# colspan za ikonce
		if ($stolpci > 0) {
			//for ($i=0; $i<$stolpci; $i++)
			//	echo '<col class="data_edit">';
			echo '<col class="data_edit"'.($stolpci > 1 ? (' span="'.$stolpci.'"') : '').'>';
		}

		$spr_cont = 0;
		foreach (self::$_HEADERS AS $spid => $spremenljivka) {
			if (isset(self::$_SVP_PV[$spid]) && count($spremenljivka['grids']) > 0) {
				
				$spr_id = explode('_', $spid);
                $spr_id = $spr_id[0];
                
				if ( in_array($spr_id, $childs) )
					$repeat = false;
				else
					$repeat = true;
					
				if (self::$showLineNumber &&  $spr_cont+1 == self::$lineoffset) {
					echo '<col>';
				}
				
				// paginacija spremenljivk
				if (self::$_VARS['spr_limit'] == 'all' || ($spr_cont >= $_spr_on_pages_start && $spr_cont < $_spr_on_pages_stop)) {
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						if (count ($grid['variables']) > 0) {
							foreach ($grid['variables'] AS $vid => $variable ){
								echo '<col seq="'.$variable['sequence'].'"';
								
								if ($spremenljivka['tip'] != 'm' && $spremenljivka['tip'] != 'sm') {
									echo ' spr_id="'.substr($spid, 0, strpos($spid, '_')).'"';
								} else {
									echo ' spr_id="'.$spid.'"';
								}
								
								echo '>';
							}
						}
						
						if (!$repeat) break;
					}
				}
				$spr_cont++;
			}

		}
		echo '</colgroup>';
		
		echo '<thead class="fixedHeader">';
		echo '<tr>';

		# colspan za ikonce
		if ($stolpci > 0) {
			echo '<th class="data_edit"'.($stolpci > 1 ? (' colspan="'.$stolpci.'"') : '').'>&nbsp;</td>';
		}

		# dodamo skrit stolpec uid
		echo '<th class="data_uid">&nbsp;</th>';
		
		$spr_cont = 0;
		foreach (self::$_HEADERS AS $spid => $spremenljivka) {
			
			if (isset(self::$_SVP_PV[$spid])) {
				
				$spr_id = explode('_', $spid);
                $spr_id = $spr_id[0];
                
				if ( in_array($spr_id, $childs) ) {
					$colspan = $cols[ array_search($spr_id, $childs) ];
				} else {
					$colspan = $spremenljivka['cnt_all'];
				}
				
				if (self::$showLineNumber &&  $spr_cont+1 == self::$lineoffset) {
					echo '<th title="'.$lang['srv_line_number'].'" >';
					echo '<div class="headerCell">'.$lang['srv_line_number'].'</div>';
					echo '</th>';
				}
				// 	paginacija spremenljivk
				if (self::$_VARS['spr_limit'] == 'all' || ($spr_cont >= $_spr_on_pages_start && $spr_cont < $_spr_on_pages_stop)) {
					echo '<th colspan="'.$colspan.'" title="'.$spremenljivka['naslov'].'">';
					echo '<div class="headerCell">'.$spremenljivka['naslov'].'</div>';
					echo '</th>';
				}
				$spr_cont++;
				
			}
		}

		echo '</tr><tr>';

		# colspan za ikonce
		if ($stolpci > 0) {
			echo '<th class="data_edit"'.($stolpci > 1 ? (' colspan="'.$stolpci.'"') : '').'>&nbsp;</td>';
		}

		# dodamo skrit stolpec uid
		echo '<th class="data_uid">&nbsp;</th>';

		$spr_cont = 0;
		foreach (self::$_HEADERS AS $spid => $spremenljivka) {
			if (isset(self::$_SVP_PV[$spid]) && (count($spremenljivka['grids']) > 0 )) {
				
				$spr_id = explode('_', $spid);
                $spr_id = $spr_id[0];
                
				if ( in_array($spr_id, $childs) )
					$repeat = false;
				else
					$repeat = true;
				
				if(self::$showLineNumber &&  $spr_cont+1 == self::$lineoffset) {
					echo '<th title="'.$lang['srv_line_number'].'" >';
					echo '<div class="headerCell">'.$lang['srv_line_number'].'</div>';
					echo '</th>';
				}
				
				// paginacija spremenljivk
				if (self::$_VARS['spr_limit'] == 'all' || ($spr_cont >= $_spr_on_pages_start && $spr_cont < $_spr_on_pages_stop)) {

					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						echo '<th colspan="'.$grid['cnt_vars'].'" title="'.$grid['naslov'].'">';
						echo '<div class="headerCell">'.$grid['naslov'].'</div>';
						echo '</th>'; 
						
						if (!$repeat) break;
					}
				}
				$spr_cont++;
			}

		}
		echo '</tr><tr>';

		# colspan za ikonce
		if ($stolpci > 0) {
			//for ($i=0; $i<$stolpci; $i++)
			//	echo '<th class="data_edit">&nbsp;</th>';
			echo '<th class="data_edit"'.($stolpci > 1 ? (' colspan="'.$stolpci.'"') : '').'>&nbsp;</th>';
		}

		# dodamo skrit stolpec uid
		echo '<th class="data_uid">&nbsp;</th>';

		$spr_cont = 0;
		foreach (self::$_HEADERS AS $spid => $spremenljivka) {
			if (isset(self::$_SVP_PV[$spid]) && count($spremenljivka['grids']) > 0) {
				
				$spr_id = explode('_', $spid);
                $spr_id = $spr_id[0];
                
				if ( in_array($spr_id, $childs) )
					$repeat = false;
				else
					$repeat = true;
					
				if (self::$showLineNumber &&  $spr_cont+1 == self::$lineoffset) {
					echo '<th title="'.$lang['srv_line_number'].'" >';
					echo '<div class="headerCell">'.$lang['srv_line_number'].'</div>';
					echo '</th>';
				}
				
				// paginacija spremenljivk
				if (self::$_VARS['spr_limit'] == 'all' || ($spr_cont >= $_spr_on_pages_start && $spr_cont < $_spr_on_pages_stop)) {
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						if (count ($grid['variables']) > 0) {
							foreach ($grid['variables'] AS $vid => $variable ){
								echo '<th title="'.$variable['naslov'].($variable['other'] ? '&nbsp;(text)' : '').'"'
								.' seq="'.$variable['sequence'].'"';
								
								if ($spremenljivka['tip'] != 'm' && $spremenljivka['tip'] != 'sm') {
									echo ' spr_id="'.substr($spid, 0, strpos($spid, '_')).'"';
								} else {
									echo ' spr_id="'.$spid.'"';
								}
								echo ($spremenljivka['inline_edit']?' inline_edit=1':'')
								//												.' inline_edit='.($spremenljivka['inline_edit']?$spremenljivka['inline_edit']:'0')
								.($variable['sequence'] == self::$sort_seq && self::$sort_seq != null ? ' class="hover '.self::$sort_type.'"': '')
								.'>';

								echo '<div class="dataCell">'.$variable['naslov'];
								if ($variable['other'] == 1) {
									echo '&nbsp;(text)';
								}
								/*// urejanje kalkulacije -- izracunane vrednosti v podatkih
								 if ($spremenljivka['tip'] == 22) {
								echo ' <a href="" onclick="calculation_editing(\'-'.substr($spid, 0, strpos($spid, '_')).'\'); return false;">('.$lang['edit3'].')</a>';
								}*/
								if ($variable['sequence'] == self::$sort_seq && self::$sort_seq != null) {
									$img_src = self::$sort_type == 'sort_dsc' ? 'sort_descending' : 'sort_ascending' ;
									echo '<span class="floatRight faicon '.$img_src.'" title=""></span>';
								}
								echo '</div>';

								echo '</th>';
							}
						}
						
						if (!$repeat) break;
					}
				}
				$spr_cont++;
			}

		}
		echo'</tr>';
		echo '</thead>';

		$_command = '';
		#preberemo podatke

		# najprej po potrebi presortiramo
		# na vindowsih ne delamo sorta (zaenkrat) // zdej ga že? :)
		if (self::$do_sort == true) {
			#
			$sortString = '-k '.self::$sort_seq;
			
			#ker tekstovnih ne sortira vredu sem odstranil parameter -n
			# iz navodil: sorting keys can be interpreted numerically (-n option) instead of alphabetically (which is the default). 
			# če bodo težave bo potrebno parameter -n dodajat po potrebi 
			# $sort_numeric => 
			$sort_numeric = ''; #$sort_numeric = '-n '
			
			# za datumska polja je potrebno malo potelovadit
			foreach (self::$_HEADERS AS $spid => $spremenljivka) {
				if (count($spremenljivka['grids']) > 0 ) {
					foreach ($spremenljivka['grids'] AS $gid => $grid) {
						if (count ($grid['variables']) > 0) {
							foreach ($grid['variables'] AS $vid => $variable ){
								if (self::$sort_seq == $variable['sequence']) {
									if ($variable['spss'] == 'DATETIMEw' || $variable['sortType'] == 'date') {
										#12.09.2011
										$sortString = '-k '.self::$sort_seq.'.7,'.self::$sort_seq.'.10 -k'.self::$sort_seq.'.4,'.self::$sort_seq.'.5 -k'.self::$sort_seq.'.1,'.self::$sort_seq.'.2';
									}
								}
							}
						}
					}
				}
			}
			
			if (IS_WINDOWS) {
				#Cygwin Sort Command On Windows
				# popravi pot do svojega sort-a
				$_path_to_CygwinSort = PATH_TO_CYGWIN_FOLDER;
				# $_command = $_path_to_CygwinSort.' -t"'.STR_DLMT.'"'.(self::$sort_type == 'sort_dsc' ? '-r' : '').' +'.(int)(self::$sort_seq-1).' '.self::$dataFileName.'';
				$_command = $_path_to_CygwinSort.' -t"'.STR_DLMT.'" '.(self::$sort_type == 'sort_asc' ? '' : '-r ').$sort_numeric.$sortString.' '.self::$dataFileName;
			} else {
				# smo na linuxu
				$_command = 'sort -t \\'.STR_DLMT.' '.(self::$sort_type == 'sort_asc' ? '' : '-r ' ).$sort_numeric.$sortString.' '.self::$dataFileName;
			}
		}
		// polovimo vrstice z statusom 5,6 in jih damo v začasno datoteko
		if (IS_WINDOWS) {
			#$cmdLn1 = 'awk -F"'.STR_DLMT.'" "BEGIN {OFS=\"\x7C\"} '.self::$_CURRENT_STATUS_FILTER.' { print $0 }" '.self::$dataFileName.' > '.$tmp_files['filtred_status'];
			#$out1 = shell_exec($cmdLn1);
			# če smo predhodno sortirali
			if (self::$do_sort == true) {
			$_command .= ' | gawk -F"'.STR_DLMT.'" "BEGIN {OFS=\"\x7C\"} '.self::$_CURRENT_STATUS_FILTER.' { print $0 }" ';
			#$_command = 'awk -F"'.STR_DLMT.'" "BEGIN {OFS=\"\x7C\"} '.self::$_CURRENT_STATUS_FILTER.' { print $0 }" '.self::$dataFileName;
		} else {
			$_command = 'gawk -F"'.STR_DLMT.'" "BEGIN {OFS=\"\x7C\"} '.self::$_CURRENT_STATUS_FILTER.' { print $0 }" '.self::$dataFileName;
		}

		} else {
			#$cmdLn1 = 'awk -F"'.STR_DLMT.'" \'BEGIN {OFS="\x7C"} '.self::$_CURRENT_STATUS_FILTER.' { print $0 }\' '.self::$dataFileName.' > '.$tmp_files['filtred_status'];
			#$out1 = shell_exec($cmdLn1);
			# če smo predhodno sortirali
			if (self::$do_sort) {
			$_command .= ' | awk -F"'.STR_DLMT.'" \'BEGIN {OFS="\x7C"} '.self::$_CURRENT_STATUS_FILTER.' { print $0 }\' ';
		} else {
			$_command = 'awk -F"'.STR_DLMT.'" \'BEGIN {OFS="\x7C"} '.self::$_CURRENT_STATUS_FILTER.' { print $0 }\' '.self::$dataFileName;
		}

		}

		// paginacija po stolpcih (spremenljivkah)
		if (IS_WINDOWS) {
			#$cmdLn1_1 = 'cut -d "|" -f 1,'.self::$_VARIABLE_FILTER.' '.$tmp_files['filtred_status'].' > '.$tmp_files['filtred_spr_pagination'];
			#$out1 = shell_exec($cmdLn1_1);
			$_command .= ' | cut -d "|" -f 1,'.self::$_VARIABLE_FILTER;
		} else {
			#$cmdLn1_1 = 'cut -d \'|\' -f 1,'.self::$_VARIABLE_FILTER.' '.$tmp_files['filtred_status'].' > '.$tmp_files['filtred_spr_pagination'];
			#$out1 = shell_exec($cmdLn1_1);
			$_command .= ' | cut -d \'|\' -f 1,'.self::$_VARIABLE_FILTER;
		}

		if (self::$_REC_LIMIT != '') {
			#paginating
			if (IS_WINDOWS) {
			#$cmdLn2 = 'awk '.self::$_REC_LIMIT.' '.$tmp_files['filtred_spr_pagination'].' > '.$tmp_files['filtred_pagination'];
			#$out2 = shell_exec($cmdLn2);
			$_command .= ' | awk '.self::$_REC_LIMIT;
		} else {
			#$cmdLn2 = 'awk '.self::$_REC_LIMIT.' '.$tmp_files['filtred_spr_pagination'].' > '.$tmp_files['filtred_pagination'];
			#$out2 = shell_exec($cmdLn2);
			$_command .= ' | awk '.self::$_REC_LIMIT;
		}
		#$file_sufix = 'filtred_pagination';
		} else {
			#$file_sufix = 'filtred_spr_pagination';
		}

		// zamenjamo | z </td><td>
		if (IS_WINDOWS) {
			#$cmdLn3 = 'sed "s*'.STR_DLMT.'*</td><td>*g" '.$tmp_files[$file_sufix].' > '.$tmp_files['filtred_sed'];
			#$out3 = shell_exec($cmdLn3);
			$_command .= ' | sed "s*'.STR_DLMT.'*'.STR_LESS_THEN.'/td'.STR_GREATER_THEN.STR_LESS_THEN.'td'.STR_GREATER_THEN.'*g" >> '.$folder.'tmp_export_'.self::$sid.'_data'.TMP_EXT;

		} else {
			#$cmdLn3 = 'sed \'s*'.STR_DLMT.'*</td><td>*g\' '.$tmp_files[$file_sufix].' > '.$tmp_files['filtred_sed'];
			#$out3 = shell_exec($cmdLn3);
			$_command .= ' | sed \'s*'.STR_DLMT.'*</td><td>*g\' >> '
			.$folder.'tmp_export_'.self::$sid.'_data'.TMP_EXT;
				
		}
		if (IS_WINDOWS) {
			# ker so na WINsih težave z sortom, ga damo v bat fajl in izvedemo :D
			$file_handler = fopen($folder.'cmd_'.self::$sid.'_to_run.bat',"w");
			fwrite($file_handler,$_command);
			fclose($file_handler);
			$out_command = shell_exec($folder.'cmd_'.self::$sid.'_to_run.bat');
			unlink($folder.'cmd_'.self::$sid.'_to_run.bat');
		} else {
			$out_command = shell_exec($_command);
		}
		
		echo '<tbody class="scrollContent'.(self::$_VARS[VAR_CODING]?' coding':'').'">';
		#$f = fopen ($tmp_files['filtred_sed'], 'r');
		if (file_exists($folder.'tmp_export_'.self::$sid.'_data'.TMP_EXT)) {

			if(self::$_VARS[VAR_REC_ON_PAGE] == 'all'){
				$up = 0;
				$low = 1;
			}		
			else{
				$up = self::$_VARS[VAR_REC_ON_PAGE] * self::$_VARS[VAR_CUR_REC_PAGE];
				$low = $up - self::$_VARS[VAR_REC_ON_PAGE]+1;
			}
			
			$cntLines=$low ;
			$f = fopen ($folder.'tmp_export_'.self::$sid.'_data'.TMP_EXT, 'r');
			
            $data = array();
            while ($line= fgets ($f)) {
                $data[] = explode('</td><td>', $line);
            }
            
            
   			$dataoffset = 1;
   			$vrstni_red = array();
   			foreach (self::$_HEADERS AS $key => $val) {
   				if ($key != '_settings') {
   					
   					$spr = explode('_', $key);
					$spr = $spr[0];
					$vrstni_red[$key] = $dataoffset;
					
   					if ( in_array($key, self::$_SVP_PV) ) {		// TODO: tukaj je nekaj pocasno..
		   				if (is_numeric($spr)) {
		   					$dataoffset += $val['cnt_all'];
		   				} else {
		   					$dataoffset++;
   						}
					}
				}
   			}
			
   			
   			$newdata = array();
   			for ($dataline=0; $dataline<count($data); $dataline++) {
				
				$origline = $data[$dataline]; //for ($j=0; $j<count($origline); $j++) $origline[$j] = (1+$j).'_'.$origline[$j];
				$newlines = array();
				
				for ($i=0; $i < $subseq[0]; $i++) {	// $subseq mora imeti vse vrednosti enake, cene ne bo slo, zato mamo lahko kar 0
					
					$newlines[$i] = $origline;
					
					$add = false;	// preverjamo da niso same -3 ALI -1 (Ajda pravi da vcasih ni prikazovalo vrstic s samo -1)
					if ($i==0) $add = true;	// prvo vrstico pustimo v vsakem primeru
					for ($spr=count($multiple)-1; $spr>=0; $spr--) {
						for ($j=0; $j<$cols[$spr]; $j++) {
							
							if ( ! in_array(trim($newlines[$i][ $vrstni_red[$multiple[$spr]['spr_id']] + ($cols[$spr]*($i)) + $j ]), array('-1', '-3', '-4')) ) {
								$add = true;
								break;
							}
						}
					}
					
					if ( $add ) {	// preverjamo da niso same -3
					
						// pri vseh, razen prvi vrstici odstranimo podvojene vrednosti
						if ($i > 0) {
							$leave = array();
							for ($spr=count($multiple)-1; $spr>=0; $spr--) {
								for ($j=0; $j<$cols[$spr]; $j++) {
									$leave[] = $vrstni_red[$multiple[$spr]['spr_id']] + ($cols[$spr]*($i)) + $j;
								}
							}
							for ($j=0; $j<count($newlines[$i]); $j++) {
								if ( ! in_array($j, $leave) )
									$newlines[$i][$j] = '';
							}
						}
						
						for ($spr=count($multiple)-1; $spr>=0; $spr--) {	// zacnemo od zadaj da si ne pokvarjamo indexov
														
							// zbrisemo na koncu (najprej na koncu, da si ne pokvarjamo indexov)
							array_splice($newlines[$i], $vrstni_red[$multiple[$spr]['spr_id']] + ($cols[$spr]*($i+1)), $subseq[0]*$cols[$spr] - $cols[$spr]*($i+1) );
														
							// zbrisemo na zacetku
							array_splice($newlines[$i], $vrstni_red[$multiple[$spr]['spr_id']], $cols[$spr]*($i));
				 		}
				 		
				 		# dodamo UID. Mitja, tole sem jaz dodal, 
				 		# da se na začetek vsake vrstice doda UID, kateri je potreben za postprocess JS funkcije
				 		# za urejanje, ker Ajda joka da v kombinirani tabeli ne deluje urejanje za podvojene vrstice
				 		# Gorazd
				 		$newlines[$i][0] = $origline[0];
				 		
	     				// dodamo v nov array
	 					$newdata[] = $newlines[$i];
 						
  					}
			 	}
			 	 	
            }
            
            unset($data);
            $data = &$newdata;
            
			
			foreach ($data AS $line) {
				$line = implode('</td><td>', $line);
				
				echo '<tr>';
				
			
				if ((int)$display1kaIcon == 1) {
					echo '<td class="enkaIcon" title="'.$lang['srv_view_data_row_quick'].'"><span class="faicon quick_view icon-as_link"></span></td>';
				}
				if ($stolpci > 0 ) {
					if (self::$displayEditIcons['dataIcons_edit'] == true) {
						// checkbox za brisanje vecih vrstic hkrati
						echo '<td class="data_edit" title="'.$lang['srv_view_data_row_select'].'"><input type="checkbox" class="delete_data_row" /></td>';
						echo '<td class="data_edit"><span class="faicon delete_circle icon-orange_link" title="'.$lang['srv_delete_data_row'].'"/></span></td>';
						echo '<td class="data_edit"><span class="faicon edit_square icon-as_link" title="'.$lang['srv_edit_data_row'].'" /></span></td>';
						echo '<td class="data_edit"><span class="faicon edit smaller icon-as_link" title="'.$lang['srv_edit_data_row_quick'].'" /></span></td>';
					}
					if (self::$displayEditIcons['dataIcons_write'] == true) {
						echo '<td class="data_edit"><span class="faicon pdf icon-as_link" title="'.$lang['srv_view_data_row_pdf'].'"></span></td>';
						echo '<td class="data_edit"><span class="faicon rtf icon-as_link" title="'.$lang['srv_view_data_row_word'].'"></span></td>';
					}
				}

				// URLje v besedilu spremenimo v __hiperlinke__
				$line = stripslashes(self::url_to_link($line));
				
				# po potrebi vrinemo zaporedno številko
				if (self::$showLineNumber && self::$_VARS[VAR_DATA]) {
					$pos = self::getLineNumberCellOffset($line);
					$line = substr_replace($line, '</td><td>'.$cntLines, $pos, 0);
				}
				
				echo '<td class="data_uid">'.$line.'</td>';

				echo '</tr>';
				$cntLines++;
			}
		} else {
			echo 'File does not exist (err.No.1)! :'.'tmp_export_'.self::$sid.'_data'.TMP_EXT;
			#echo $folder.'tmp_export_'.self::$sid.'_data'.TMP_EXT;
		}
		echo '</tbody>';
		echo '</table>';
		echo '</div>'; // end div tableContainer

		/**
		 * kliki na ikonice za urejanje in izpis so definirane v script_analiza.js, analiza_init();
		 */

		// JS za urejanje vnosov (click in hover) (funkciji sta definirani v postProcess.js)
		?>
		<script>
		$('#dataTableScroller').followTo($("#dataTable").position().top - $("#dataTableScroller").height()-25);
		dataTableResize(<?=self::$sid?>);
		$('#dataTable').bind('contextmenu', function (event) { data_preview_content(event); return false; } );
		<?php
		if (self::$_VARS[VAR_EDIT] || self::$_VARS[VAR_MONITORING]) 
		{
		?>
			$('#dataTable td').click( function (event) { edit_data(event); } );
			$('#dataTable td').hover( function (event) { edit_data_hover(event) }, function (event) { edit_data_hoverout(event) } );
			edit_data_inline_edit();	// manj utripne, ce takoj za tabelo poklicemo brez cakanja na dom ready
			$('#dataTable tr:nth-child(3) th').hover( function (event) { data_header_hover(event) }, function (event) { data_header_hoverout(event) } );
			$('#dataTable tr:nth-child(3) th').live('click', function(event) { data_header_click(event); } );
		<?php 
		} elseif (self::$_VARS[VAR_CODING]) {
		?>	
			$('#dataTable tbody tr td').click( function (event) { coding_click( $(this), event ) } );
		<?php
		} else {
		?>
			$('#dataTable tr:nth-child(3) th').hover( function (event) { data_header_hover(event) }, function (event) { data_header_hoverout(event) } );
			$('#dataTable tr:nth-child(3) th').live('click', function(event) { data_header_click(event); } );
		<?php 
		} 
			
		?>
		$('#dataTable td.enkaIcon span.quick_view').click( function (event) { showSurveyAnswers(event); } );
		var sort_action_url = '<?php echo 'index.php?anketa='.self::$sid.'&a='.A_COLLECT_DATA.'&m='.self::$subAction.self::getVarsNoSort();?>'
		<?php 
		if (self::$_VARS[VAR_META]) {
			echo  "postProcessAddLurkerTitles(".(self::$_VARS[VAR_RELEVANCE] && self::$canDisplayRelevance  ? (4+(int)self::$_VARS[VAR_EMAIL]) : (3+(int)self::$_VARS[VAR_EMAIL])).");\n";
		}
		
		# pobarvamo celice in dodamo title za statuse
		echo  "postProcessAddMetaTitles();\n";
		
		if (self::$_VARS[VAR_RELEVANCE] && self::$canDisplayRelevance) {
			echo  "postProcessAddRelevanceTitles();\n";
		}
		if (self::$_VARS[VAR_EMAIL]) {
			echo  "postProcessAddEmailTitles(".(self::$_VARS[VAR_RELEVANCE] && self::$canDisplayRelevance ? 3 : 2).");\n";
		}
		?>
		</script>
		<?php
		if ($f) {
			fclose($f);
		}
		if (file_exists($folder.'tmp_export_'.self::$sid.'_data'.TMP_EXT)) {
			unlink($folder.'tmp_export_'.self::$sid.'_data'.TMP_EXT);
		}
		if ($_GET['debug'] == 1) {
			print_r("<pre>".$_command."</pre>");
		}
		
		// Editiranje na dnu - brisanje vec userjev hkrati...	
		if(self::$dataFileStatus != FILE_STATUS_NO_DATA && (int)self::$displayEditIcons['dataIcons_edit'] == 1){
			self::displayBottomEdit();
		}
	}

	public static function url_to_link($text) {
		if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) 
			return preg_replace('!(https://[a-z0-9_./?=&-]+)!i', '<a href="$1" target="_blank">$1</a> ', $text." ");
		else 
			return preg_replace('!(http://[a-z0-9_./?=&-]+)!i', '<a href="$1" target="_blank">$1</a> ', $text." ");
	}

	/** Prikaze reseno anketo za posameznega uporabnika v hitrem nacinu
	 *
	 */
	public static function displayQuickEdit(){
		global $lang;
		global $site_path;
		
		if (self::$dataFileStatus == FILE_STATUS_NO_DATA
				|| self::$dataFileStatus == FILE_STATUS_NO_FILE
				|| self::$dataFileStatus == FILE_STATUS_SRV_DELETED){
			return false;
		}
		
		include_once('../../main/survey/app/global_function.php');
		new \App\Controllers\SurveyController(true);
		save('usr_id', self::$usr_id);

		if (isset($_GET['quick_view']) && $_GET['quick_view'] == 0 ) {
			$quick_view = false;
		} else {
			$quick_view = true;
		}


		$rowa = SurveyInfo::getInstance()->getSurveyRow();
		if ($quick_view) {
			# če smo v quick_view disejblamo vse elemente forem
			echo "<script>"."\n";
			//echo "$(document).ready(function(){ $('#edit_survey_data input:[type=radio], #edit_survey_data input:[type=checkbox], #edit_survey_data input:[type=text], #edit_survey_data select, #edit_survey_data textarea').attr('disabled',true); })"."\n";
			echo "$(document).ready(function(){ $('#edit_survey_data input:[type=radio], #edit_survey_data input:[type=checkbox], #edit_survey_data input:[type=text], #edit_survey_data select, #edit_survey_data textarea, #edit_survey_data input:[type=button] , ').attr('disabled',true); })"."\n";
			echo "$(document).ready(function(){ $('#quick_view').attr('disabled',false); })"."\n";
			echo "$(document).ready(function(){ $('.ranking').draggable({disabled:true}); })"."\n";	//disable-anje draggable
			echo "$(document).ready(function(){ $('.dropzone, .sortzone').sortable({disabled:true}); })"."\n";	//disable-anje sortable
			//echo "$(document).ready(function(){ $('canvas').attr('disabled'); })"."\n";	//disable-anje canvas
			echo "$(document).ready(function(){ $('.sig').attr('disabled', true); })"."\n";	//disable-anje canvas
			
			echo "</script>";
		}
		else{
			# če urejamo dodamo prazno js funkcijo submitForm da ne mece errorja (submit izvedemo rocno)
			echo "<script>function submitForm(){}</script>"."\n";
		}

		echo '<div id="edit_survey_data">';
		echo '<div class="inner quick_edit">';

		// title
		echo '<div class="quick_edit_title">';
		
		//echo $rowa['naslov'];	
		if (self::$quickEdit_recnum[3]['hasPrev'] == true) {
			echo '<a href="#" onClick="location.href=\''.self::$quickEdit_recnum[0].'\'" title="'.$lang['srv_prev_resp'].'"><span class="faicon arrow2_l pointer"></span></a>';
		}
		echo 'Recnum '.self::$quickEdit_recnum[2];
		if (self::$quickEdit_recnum[3]['hasNext'] == true) {
			echo '<a href="#" onClick="location.href=\''.self::$quickEdit_recnum[1].'\'" title="'.$lang['srv_next_resp'].'"><span class="faicon arrow2_r pointer"></span></a>';
		}
		
		echo '</div>';

		if ($quick_view == false) {
			echo '<form name="vnos" id="vnos" method="post" action="../survey/index.php?anketa='.$_GET['anketa'].'&a=data&m=quick_edit&usr_id='.$_GET['usr_id'].'&quick_view=0&post=1" enctype="multipart/form-data">'."\n";
		}
			
		if (isset($_GET['anketa']))
		{
			save('anketa', $_GET['anketa']);
			if ($quick_view == false) {
				
				//JS potreben za branching
				\App\Controllers\JsController::getInstance()->generateBranchingJS();
				echo '<script>function checkBranchingDate(){checkBranching();}</script>';
				
				// shranimo popravke v bazo
				if(isset($_GET['post']) && $_GET['post'] == '1'){
					\App\Models\SaveSurvey::getInstance()->posted();
				}
			}
				
				
			$first_loop = 0;

			// prikažemo spremenljivke - vse grupe
			do
			{
				// nastavimo naslednjo grupo / loop
				if(get('loop_AW') == 0 && get('loop_id') == null){
					save('grupa', \App\Controllers\FindController::getInstance()->findNextGrupa());
				} elseif (get('loop_id') != null) {
					save('loop_id', \App\Controllers\FindController::getInstance()->findNextLoopId());

					if (get('loop_id') == null)
						save('grupa', \App\Controllers\FindController::getInstance()->findNextGrupa());
				}

				echo '<div id="container">'."\n";

				// zgeneriramo sistemske spremenljivke
				\App\Controllers\HeaderController::getInstance()->displaySistemske();;


				$offset = 0;
				$zaporedna = 1;
				if (SurveyInfo::getInstance()->getSurveyCountType() > 0)
				{
					// Preštejemo koliko vprašanj je bilo do sedaj
					$sqlg = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id='".get('grupa')."'");
					$rowg = mysqli_fetch_assoc($sqlg);
					$vrstni_red = $rowg['vrstni_red'];

					$sqlCountPast = sisplet_query("SELECT count(*) as cnt FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='".get('anketa')."' AND s.gru_id=g.id AND g.vrstni_red < '$vrstni_red' ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");
					$rowCount = mysqli_fetch_assoc($sqlCountPast);
					$offset = $rowCount['cnt'];
				}

				// poiscemo vprasanja / spremenljivke
				$sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id='".get('grupa')."' AND visible='1' ORDER BY vrstni_red ASC");
				while ($row = mysqli_fetch_array($sql)) {

					//ce gre za glasovanje in smo eno vprasanje ze prikazali, ostalih ne prikazemo
					if( (SurveyInfo::getInstance()->getSurveyType() != 0) || ($zaporedna == 1)){

						// preverimo, ce je na tej strani LOOP
						if (get('loop_id') ==null) {

							// nastavimo pravi id trenutnega loopa
							$if_id = \App\Controllers\FindController::find_parent_loop($row['id']);
							if ($if_id > 0) {

								// Ce je prva spremenljivka v loopih izpisemo warning za urejanje
								$first_loop = ($first_loop == 0) ? 1 : 2;
								if($first_loop == 1)
									echo '<div class="loop_warning">'.$lang['srv_loop_warning'].'<a href="#" title="'.$lang['srv_edit_data_row'].'" onClick="quickEditAction(\'edit\', \''.self::$usr_id.'\');">'.$lang['srv_loop_warning2'].'</a>.</div>';
									
								save('loop_id', \App\Controllers\FindController::getInstance()->findNextLoopId($if_id));
							}
						}
							
						// filtriramo spremenljivke glede na profil
						$dvp = SurveyUserSetting :: getInstance()->getSettings('default_variable_profile');
						$_currentVariableProfile = SurveyVariablesProfiles :: checkDefaultProfile($dvp);

						# V VPOGLEDU NE FILTRIRAMO SPREMENLJIVK (v.v.: 27.11.2011)
						#$tmp_svp_pv = SurveyVariablesProfiles :: getProfileVariables($_currentVariableProfile);

						# če je $svp_pv = null potem prikazujemo vse variable
						# oziroma če je sistemski dodamo tudi vse, ker drugače lahko filter skrije telefon in email
						if (!is_countable($tmp_svp_pv) || count($tmp_svp_pv) == 0 || self::$_VARS[VAR_SHOW_SYSTEM] == true ) {
							$_sv = self::$SDF->getSurveyVariables();

							if (count($_sv) > 0) {
								foreach ( $_sv as $vid => $variable) {
									$tmp_svp_pv[$vid] = substr($vid, 0, strpos($vid, '_'));
								}
							}
						}
						else{
							foreach ( $tmp_svp_pv as $vid => $variable) {
								$tmp_svp_pv[$vid] = substr($vid, 0, strpos($vid, '_'));
							}
						}
						# V VPOGLEDU NE FILTRIRAMO SPREMENLJIVK (v.v.: 27.11.2011)
						//if(in_array($row['id'],$tmp_svp_pv))
						\App\Controllers\Vprasanja\VprasanjaController::getInstance()->displaySpremenljivka($row['id'], $offset, $zaporedna);
					}

					$zaporedna++;
				}
				if ($quick_view == false) {
					\App\Controllers\JsController::getInstance()->generateSubmitJS();
				}

				echo '</div>'."\n";			
			} 
			while (get('grupa') != \App\Controllers\FindController::getInstance()->findNextGrupa() &&
					(\App\Controllers\FindController::getInstance()->findNextGrupa() > 0 || (get('loop_id') != null && \App\Controllers\FindController::getInstance()->findNextLoopId() != null)));
		}
		if ($quick_view == false) {
			//echo '<input type="submit" value="Shrani" /> ';<a href="#" onclick="document.forms['myFormName'].submit(); return false;">...</a>
			echo '<span class="floatRight spaceLeft" ><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="document.forms[\'vnos\'].submit(); return false;"><span>' . $lang['save'] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight" ><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="../survey/index.php?anketa='.self::$sid.'&a='.A_COLLECT_DATA.'"><span>Nazaj na podatke</span></a></div></span>';

			#echo '</form>'."\n";
		}
		else{
			echo '</form>'."\n";
		}

		echo '</div>'; # inner

		echo '<div id="quick_edit_title">';
		self::displayVnosIcons();
		echo '</div>';
		
		echo '<br /><br /><br /><br />';
		
		echo '</div>';


		//div na desni z metapodatki
		echo '<div id="quick_edit_meta">';
		self::displayQuickEditMeta();
		echo '</div>';

		// Preverimo ce gre za prvo urejanje - potem avtomatsko ustvarimo arhiv podatkov
		if ($quick_view == false && isset($_GET['post']) && $_GET['post'] == '1') {
			ob_flush();
			
			$sas = new SurveyAdminSettings();
			$sas->checkFirstDataChange($inserted=true);
		}
	}

	public static function displayQuickEditFilters(){
		global $lang;
		global $site_path;
		
		//echo '<h2>'.$lang['srv_data_title_quick_view'].'</h2>';
		
		if (self::$dataFileStatus == FILE_STATUS_NO_DATA
				|| self::$dataFileStatus == FILE_STATUS_NO_FILE
				|| self::$dataFileStatus == FILE_STATUS_SRV_DELETED){
			return false;
		}
        
        
		self::displayQuickEditPagination();	

		echo '<div id="div_analiza_filtri_right" class="floatRight vpogled">';
		echo '<ul>';
		# div za filtre statusov
		SurveyStatusProfiles::DisplayLink(false);
		# div za profile variabel
		SurveyVariablesProfiles::DisplayLink(false, false);
		# filter za  pogoje - ifi
		SurveyConditionProfiles::DisplayLink(false);
		# filter za čase
		SurveyTimeProfiles::DisplayLink(false,false);
		echo '</ul>';
		echo '</div>';
		
		 
		# če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
		$doNewLine = SurveyTimeProfiles :: printIsDefaultProfile(false);
		if ($doNewLine) {
			echo '<br/>';
		}
		# če imamo filter ifov ga izpišemo
		$doNewLine = SurveyConditionProfiles:: getConditionString($doNewLine ) || $doNewLine;

		# če imamo filter ifov za inspect ga izpišemo
		$SI = new SurveyInspect(self::$sid);
		$SI->getConditionString();

		# če imamo filter spremenljivk ga izpišemo
		# ker v vpogledu ne filtriramo spremenljivk, ne izpisujemo obvestila
		#$doNewLine = SurveyVariablesProfiles:: getProfileString($doNewLine ) || $doNewLine;

		if ($doNewLine) {
			echo '<br/>';
		}

		echo '<div id="quick_edit_title">';
		
		self::displayVnosIcons();
		
		echo '</div>';
	}

	public static function displayQuickEditPagination() {
		global $site_url, $lang;

		#Userje polovimo iz datoteke s pomočjo filtrov z AWK-jem
		$_command = '';
		#preberemo podatke

		$tmp_file = self::$folder.'tmp_export_'.self::$sid.'_data'.TMP_EXT;
		$file_handler = fopen($tmp_file,"w");
		fwrite($file_handler,"<?php\n");
		fclose($file_handler);

		# polovimo vrstice z statusom 5,6 in jih damo v začasno datoteko
		if (IS_WINDOWS) {
			$_command = 'gawk -F"'.STR_DLMT.'" "BEGIN {OFS=\"\"} '.self::$_CURRENT_STATUS_FILTER.' {print \"$uids[]=\",'.USER_ID_FIELD.',\";$uid_rec[\",'.USER_ID_FIELD.',\"]=\",'.MOD_REC_FIELD.',\";\" }" '.self::$dataFileName.' >> '.$tmp_file;
		} else {
			$_command = 'awk -F"'.STR_DLMT.'" \'BEGIN {OFS=""} '.self::$_CURRENT_STATUS_FILTER.' { print "$uids[]=",'.USER_ID_FIELD.',";$uid_rec[",'.USER_ID_FIELD.',"]=",'.MOD_REC_FIELD.',";"}\' '.self::$dataFileName.' >> '.$tmp_file;
		}

		if (IS_WINDOWS) {
			$out_command = shell_exec($_command);
		} else {
			$out_command = shell_exec($_command);
		}
		include($tmp_file);

		if (file_exists($tmp_file)) {
			unlink($tmp_file);
		}
		# če imamo zapise
		$all = count($uids);
		# current nastavimo na zadnji element
		if ( $all > 0) {
			// Če trenutni user ni nastavljen ga nastavimo. Upoštevamo tudi filtre, zato preberemo prvega iz filtriranega seznama
			if(self::$usr_id == 0){
				self::$usr_id = reset($uids);
			}
				
				
			if (isset($_GET['quick_view']) && $_GET['quick_view'] == 0 ) {
				$baseUrl = $site_url.'admin/survey/index.php?anketa='.self::$sid.'&a=data&m=quick_edit&quick_view='.$_GET['quick_view'].'&usr_id=';
			} else {
				$baseUrl = $site_url.'admin/survey/index.php?anketa='.self::$sid.'&a=data&m=quick_edit&usr_id=';
			}
				
			if (self::$usr_id > 0 && isset(self::$usr_id,$uids)) {
				$current = array_search(self::$usr_id,$uids);

			} else {
				$current = count($uids)-1;
			}

			echo '<div id="pagination" class="floatLeft">';

			# povezava -10
			/*if ($all > 10) {
				if ($current - 10 >= 0) {
					echo('<div><a href="'.$baseUrl.$uids[$current - 10].'">-10</a></div>');
				} else {
					# brez href povezave
					echo('<div class="disabledPage">-10</div>');
				}
			}*/
			
			$controls=array('hasPrev'=>true,'hasNext'=>true);
			
			# povezava na prejšnjo stran
			$prev_page = $uids[$current - 1] ? $uids[$current - 1] :$uids[$current];
			if( ($current - 1) >= 0) {
				echo('<div><a href="'.$baseUrl.$prev_page.'"><span class="faicon pagination_left icon-blue"></span></a></div>');
			} else {
				# brez href povezave
				echo('<div class="disabledPage"><span class="faicon pagination_left icon-blue_soft"></span></div>');
				$controls['hasPrev'] = false;
			}

			# povezave  za vmesne strani
			$middle = $all / 2;
			$skipped  = false;
			for($a = 0; $a < $all; $a++) {
				if ($all < ((SRV_LIST_GROUP_PAGINATE+1) * 2) || $a <= SRV_LIST_GROUP_PAGINATE || $a > ($all-SRV_LIST_GROUP_PAGINATE)
							
						|| ( abs($a-$current) < SRV_LIST_GROUP_PAGINATE))  {
					if ($skipped == true) {
						echo '<div class="spacePage">.&nbsp;.&nbsp;.</div>';
						$skipped  = false;
					}
					if($a == $current) {
						# brez href povezave
						echo('<div class="currentPage">'.($a+1).'</div>');
					} else {
						echo('<div><a href="'.$baseUrl.$uids[$a].'">'.($a+1).'</a></div>');
					}
				} else {
					$skipped = true;
				}
			}
			
			# povezava na naslednjo stran
			$next_page = ($uids[$current + 1]) ? $uids[$current + 1] : $uids[$current];
			if(($current + 1) < $all) {
				echo('<div><a href="'.$baseUrl.$next_page.'"><span class="faicon pagination_right icon-blue"></span></a></div>');
			} else {
				# brez href povezave
				echo('<div class="disabledPage"><span class="faicon pagination_right icon-blue_soft"></span></div>');
				$controls['hasNext'] = false;
			}
			
			/*if ($all > 10) {
				if ($current + 10 < $all) {
					echo('<div><a href="'.$baseUrl.$uids[$current + 10].'">+10</a></div>');
				} else {
					# brez href povezave
					echo('<div class="disabledPage">+10</div>');
				}
			}*/
			
			echo '</div>';

			// vrnemo link na prejsnega, link na naslednjega in recnum trenutnega
			//return array($baseUrl.$prev_page, $baseUrl.$next_page, $uid_rec[self::$usr_id], $controls);
			self::$quickEdit_recnum = array($baseUrl.$prev_page, $baseUrl.$next_page, $uid_rec[self::$usr_id], $controls);
		} 
		else {
			// dobimo trenutnega userja - ce ni nastavljen v get-u
			if(self::$usr_id == 0){
				$sqlu = sisplet_query("SELECT id FROM srv_user WHERE ank_id='".self::$sid."' ORDER BY recnum DESC LIMIT 1");
				$rowu = mysqli_fetch_array($sqlu);
				self::$usr_id = $rowu['id'];
			}
		}
	}

	public static function displayQuickEditMeta(){
		global $lang;
		global $site_path;
		global $admin_type;
		
		$rowa = SurveyInfo::getInstance()->getSurveyRow();

		// dobimo trenutnega userja
		if(self::$usr_id > 0){
			$sqlu = sisplet_query("SELECT * FROM srv_user WHERE ank_id='".self::$sid."' AND id='".self::$usr_id."' ");
			$rowu = mysqli_fetch_array($sqlu);
		}
		else{
			$sqlu = sisplet_query("SELECT * FROM srv_user WHERE ank_id='".self::$sid."' ORDER BY recnum DESC ");
			$rowu = mysqli_fetch_array($sqlu);
		}
		self::$usr_id = $rowu['id'];

		echo '<div class="title">'.$lang['srv_metapodatki'].'</div>';

		
		echo '<table>';

		echo '<tr><td class="left">'.$lang['srv_info_type'].':</td>';
		echo '<td class="right">'.$lang['srv_vrsta_survey_type_'.SurveyInfo::getSurveyType()].'</td></tr>';
		
		// IP
		$ip = SurveySetting::getInstance()->getSurveyMiscSetting('survey_ip');
		$ip_show = SurveySetting::getInstance()->getSurveyMiscSetting('survey_show_ip');
		if($ip==0 && $ip_show==1 && ($admin_type == 0 || $admin_type == 1)){
			echo '<tr><td class="left">'.$lang['ip'].':</td>';
			echo '<td class="right">'.($rowu['ip'] ? $rowu['ip'] : '&nbsp;').'</td></tr>';
		}
		
		// recnum
		echo '<tr><td class="left">'.$lang['srv_recnum'].':</td>';
		echo '<td class="right">'.($rowu['recnum'] ? $rowu['recnum']  : '&nbsp;').'</td></tr>';
		
		// browser
		echo '<tr><td class="left">'.$lang['browser'].':</td>';
		echo '<td class="right">'.($rowu['useragent'] ? $rowu['useragent'] : '&nbsp;').'</td></tr>';
		
		// javascript
		echo '<tr><td class="left">'.$lang['javascript'].':</td>';
		echo '<td class="right">'.(($rowu['javascript'] == 1) ? $lang['yes'] : $lang['no1']).'</td></tr>';
		
		// jezik
		// Dobimo vse jezike za katere obstaja jezikovna datoteka
        include_once($site_path.'lang/jeziki.php');
		$jeziki = $lang_all_global['ime'];
		$jeziki['0'] = $lang['language'];
		echo '<tr><td class="left">'.$lang['lang'].':</td>';
		echo '<td class="right">'.$jeziki[$rowu['language']].'</td></tr>';
		
		// status
		echo '<tr><td class="left">'.$lang['status'].':</td>';
		echo '<td class="right">'.($rowu['last_status'] ? $rowu['last_status'] : '&nbsp;').'</td></tr>';
		
		// lurker
		echo '<tr><td class="left">'.$lang['srv_data_lurker'].':</td>';
		echo '<td class="right">'.(($rowu['lurker'] == 1) ? $lang['yes'] : $lang['no1']).'</td></tr>';
		
		//referer
		echo '<tr><td class="left">'.$lang['referer'].':</td>';
		echo '<td class="right">'.($rowu['referer'] ? $rowu['referer'] : '&nbsp;').'</td></tr>';

		//email - samo forma
		if($rowa['survey_type'] == 1){
			echo '<tr><td class="left">'.$lang['email'].':</td>';
			echo '<td class="right">'.($rowu['email'] ? $rowu['email'] : '&nbsp;').'</td></tr>';
		}

		// spreminjal
		$datetime = strtotime($rowu['time_insert']);
		$text = date("d.m.Y, H:i:s", $datetime);
		echo '<tr><td class="left">'.$lang['timeinsert'].':</td>';
		echo '<td class="right">'.$text.'</td></tr>';

		$datetime = strtotime($rowu['time_edit']);
		$text = date("d.m.Y, H:i:s", $datetime);
		echo '<tr><td class="left">'.$lang['timeedit'].':</td>';
		echo '<td class="right">'.$text.'</td></tr>';

		// preberemo popravljanje po straneh
		$sqlG =  sisplet_query("SELECT ug.time_edit, g.naslov FROM srv_user_grupa".self::$db_table." ug, srv_grupa g WHERE g.ank_id = '".self::$sid."' AND ug.usr_id = '".self::$usr_id."' AND g.id = ug.gru_id ORDER BY g.vrstni_red ASC");
		while($rowG = mysqli_fetch_array($sqlG)){

			$datetime = strtotime($rowG['time_edit']);
			$text = date("d.m.Y, H:i:s", $datetime);

			echo '<tr><td class="left">'.$rowG['naslov'].':</td>';
			echo '<td class="right">'.$text.'</td></tr>';
		}

		if ( $admin_type <= 1 /* && what more??? */ ) {
			
			echo '<tr><td class="left">'.$lang['srv_sc_txt1'].':</td>';
			echo '<td class="right"><a href="#" onclick="sc_display(\''.self::$usr_id.'\'); return false;">'.$lang['srv_sc_txt2'].'</a></td></tr>';
			
		}
		
                # preberemo vklopljene module
                //potrebuje se za modul MAZA, da aplikacija izpolni te hidden inpute
                //rabi pa se to za povezavo respondenta med tebelama maza_app_users in srv_user
                if(SurveyInfo::checkSurveyModule('maza')){
                    $maza_query = "SELECT mau.identifier, mau.deviceInfo, mau.tracking_log FROM maza_app_users as mau
                        JOIN maza_srv_users AS msu ON mau.id = msu.maza_user_id
                        JOIN srv_user AS su ON msu.srv_user_id = su.id 
                     WHERE su.id = '".self::$usr_id."';";
                    
                    $sql = sisplet_query($maza_query, 'array');
                    
                    //it is already there
                    if(count($sql) > 0){
                        //NextPin link
                        echo '<tr><td class="left">'.$lang['srv_maza_nextpin_link'].':</td>';
                        echo '<td class="right"><a href="http://traffic.ijs.si/NextPin/?user=1KAPanel_'.$sql[0]['identifier'].'">'
                                . 'http://traffic.ijs.si/NextPin/?user=1KAPanel_'.$sql[0]['identifier'].'</a></td></tr>';
                        //Device info
                        echo '<tr><td class="left">'.$lang['srv_maza_device_info'].':</td>';
                        echo '<td class="right">'.$sql[0]['deviceInfo'].'</td></tr>';
                        //Tracking logs
                        echo '<tr><td class="left">'.$lang['srv_maza_user_app_logs'].':</td>';
                        echo '<td class="right">'.$sql[0]['tracking_log'].'</td></tr>';
                    }
                }
                
		echo '</table>';
		
		
		echo '<div id="survey-connect-disp" style="display:none"></div>';
		//echo '<script> sc_display(\''.self::$usr_id.'\'); </script>';
	
	}


	/* funkcija vrne AWK string za pogoj ali iščemo zapise trenutnega userja
	 * vklopljeno more bit prepoznava userja iz cms
	*
	*/
	static function createCMSUserFilter() {
		#poiščemo sekvenco meta podatka: usr_from_cms
		$found=false;
		$sequence = null;
		if (count(self::$_HEADERS['meta']['grids']) > 0 && $found==false) {
			foreach (self::$_HEADERS['meta']['grids'] AS $gid => $grids) {
				if (count($grids['variables']) > 0 && $found==false) {
					foreach ($grids['variables'] AS $vids => $variables) {
						if ($variables['variable'] == 'usr_from_cms') {
							$sequence = $variables['sequence'];
							$found=true;
						}
					}
				}
			}
		}
		if ($found == true && (int)$sequence > 0) {
			# polovimo email
			global $global_user_id;
			$sqlu = sisplet_query("SELECT email FROM users WHERE id = '".$global_user_id."'");
			list($email) = mysqli_fetch_row($sqlu);
			if ($email != null &&trim($email) != '') {
					
				# nardimo awk string da primerjamo email
				if (IS_WINDOWS) {
				# za windows
				$awkString = '($'.$sequence.'=='."\\\"".$email."\\\"".')';
			} else {
				# za linux
				$awkString = '($'.$sequence.'=='.'"'.$email.'"'.')';
			}
				
			return $awkString;
			}
		}
		return null;
	}

	static function displayOnlyCMS() {
		global $lang;
		# prikažemo samo če imamo ankete uporabnika iz cms
		echo '<label>';
		echo '<input type="checkbox" id="doCMSUserFilterCheckbox"'.(self::$doCMSUserFilter==true?' checked="checked"':'').' onchange="changeDoCMSUserFilterCheckbox();" autocomplete="off">';
		echo $lang['srv_data_onlyMySurvey'];
		echo Help::display('srv_data_onlyMySurvey');
		echo '</label>';
	}

	static function displayPublicData($properties = array()) {
		global $lang;
		global $site_url;
		
		header('Cache-Control: no-cache');
		header('Pragma: no-cache');
		$anketa = self::$sid;
		if ($anketa > 0) {
			$sql = sisplet_query("SELECT lang_admin FROM srv_anketa WHERE id = '$anketa'");
			$row = mysqli_fetch_assoc($sql);
			$lang_admin = $row['lang_admin'];
		} else {
			$sql = sisplet_query("SELECT value FROM misc WHERE what = 'SurveyLang_admin'");
			$row = mysqli_fetch_assoc($sql);
			$lang_admin = $row['value'];
		}

		#izpišemo HTML
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />';
		echo '<script type="text/javascript" src="'.$site_url.'admin/survey/script/js-lang.php?lang='.($lang_admin==1?'si':'en').'"></script>';
		echo '<script type="text/javascript" src="'.$site_url.'admin/survey/minify/g=jsnew"></script>';
		echo '<link type="text/css" href="'.$site_url.'admin/survey/minify/g=css" media="screen" rel="stylesheet" />';
		echo '<link type="text/css" href="'.$site_url.'admin/survey/minify/g=cssPrint" media="print" rel="stylesheet" />';
		echo '<style>';
		echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
		echo '</style>';
		echo '<!--[if lt IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie6hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie7hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 8]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie8hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<style>';
		echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
		echo '</style>';
		echo '<script>';
		echo 'function chkstate(){';
		echo '	if(document.readyState=="complete"){';
		echo '		window.close()';
		echo '	}';
		echo '	else{';
		echo '		setTimeout("chkstate()",2000)';
		echo '	}';
		echo '}';
		echo 'function print_win(){';
		echo '	window.print();';
		echo '	chkstate();';
		echo '}';
		echo 'function close_win(){';
		echo '	window.close();';
		echo '}';
		echo '</script>';
		echo '</head>';

		echo '<body style="margin:5px; padding:5px;" >'; 
			
		echo '<h2>'.$lang['srv_publc_data_title_for'].self::$survey['naslov'].'</h2>';
		echo '<input type="hidden" name="anketa_id" id="srv_meta_anketa_id" value="' . $anketa . '" />';
		
		echo '<div id="analiza_data">';
		//Izvoz v PDF / RTF / XLS
		$_url1 = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
				serialize(
						array(	'b'=>'export',
								'a'=>'list_pdf',
								'anketa'=>$anketa)));
		$_url2 = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
				serialize(
						array(	'b'=>'export',
								'a'=>'list_rtf',
								'anketa'=>$anketa)));
		$_url3 = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
				serialize(
						array(	'b'=>'export',
								'a'=>'list_xls',
								'anketa'=>$anketa)));
		echo '<div class="printHide" style="margin-top:6px; margin-bottom:60px;">';
		echo '<a href="'.$_url1.'" target="_blank"><span class="faicon pdf icon-as_link"></span></a>&nbsp;&nbsp;';
		echo '<a href="'.$_url2.'" target="_blank"><span class="faicon rtf icon-as_link"></span></a>&nbsp;&nbsp;';
		echo '<a href="'.$_url3.'" target="_blank"><span class="faicon xls icon-as_link"></span></a>';
		
		if (isset($properties['profile_id_status']))
		{
			self::$_PROFILE_ID_STATUS = $properties['profile_id_status'];
			SurveyStatusProfiles :: setCurentProfileId(self::$_PROFILE_ID_STATUS);
		}
		
		if (isset($properties['profile_id_variable']))
		{
			self::$_PROFILE_ID_VARIABLE = $properties['profile_id_variable'];
			
			SurveyVariablesProfiles::setCurrentProfileId(self::$_PROFILE_ID_VARIABLE);
		}
		if (isset($properties['profile_id_condition']))
		{
			self::$_PROFILE_ID_CONDITION = $properties['profile_id_condition'];
			
			SurveyConditionProfiles::setCurrentProfileId(self::$_PROFILE_ID_CONDITION);
		}

	
		self::$printPreview = true;
		self::$_VARS[VAR_DATA] = 1;
		self::$_VARS[VAR_EDIT] = 0;
		self::$_VARS[VAR_PRINT] = 0;
		self::$_VARS[VAR_MONITORING] = 0;
		if (isset(self::$_SVP_PV['invitation'])) {
			unset(self::$_SVP_PV['invitation']);
		}

		
		# ponastavimo nastavitve- filter
		self::setUpFilter();
		self::DisplayDataTable();
		
		// JS ki vedno doda labele
		?><script>
			data_show_labels();
		</script><?php
		
		echo '</div>';
			
		echo '<div id="navigationBottom" class="printHide">';

		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="window.print();return false;"><span><img src="'.$site_url.'admin/survey/icons/icons/printer.png" vartical-align="middle" /> '.$lang['hour_print2'].'</span></a></div></span>';
		echo '<span  class="spaceRight floatRight printHide" style="margin-top:6px;">';
		echo '<a href="'.$_url1.'" target="_blank"><span class="faicon pdf icon-as_link"></span></a>&nbsp;&nbsp;';
		echo '<a href="'.$_url2.'" target="_blank"><span class="faicon rtf icon-as_link"></span></a>&nbsp;&nbsp;';
		echo '<a href="'.$_url3.'" target="_blank"><span class="faicon xls icon-as_link"></span></a>';
		echo '</span>';
		
		echo '<br class="clr" />';
		echo '</div>';
		
		echo '</body>';
		echo '</html>';
	}
	
	function displayDataPrintPreview() {
		global $lang;
		global $site_url;
		
		header('Cache-Control: no-cache');
		header('Pragma: no-cache');
		$anketa = $_REQUEST['anketa'];
		if ($anketa > 0) {
			$sql = sisplet_query("SELECT lang_admin FROM srv_anketa WHERE id = '$anketa'");
			$row = mysqli_fetch_assoc($sql);
			$lang_admin = $row['lang_admin'];
		} else {
			$sql = sisplet_query("SELECT value FROM misc WHERE what = 'SurveyLang_admin'");
			$row = mysqli_fetch_assoc($sql);
			$lang_admin = $row['value'];
		}

		#izpišemo HTML
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">';
		echo '<head>';
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		echo '<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE8" />';
		echo '<script type="text/javascript" src="'.$site_url.'admin/survey/script/js-lang.php?lang='.($lang_admin==1?'si':'en').'"></script>';
		echo '<script type="text/javascript" src="'.$site_url.'admin/survey/minify/g=jsnew"></script>';
		echo '<link type="text/css" href="'.$site_url.'admin/survey/minify/g=css" media="screen" rel="stylesheet" />';
		echo '<link type="text/css" href="'.$site_url.'admin/survey/minify/g=cssPrint" media="print" rel="stylesheet" />';
		echo '<style>';
		echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
		echo '</style>';
		echo '<!--[if lt IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie6hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 7]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie7hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<!--[if IE 8]>';
		echo '<link rel="stylesheet" href="<?=$site_url?>admin/survey/css/ie8hacks.css" type="text/css" />';
		echo '<![endif]-->';
		echo '<style>';
		echo '.container {margin-bottom:45px;} #navigationBottom {width: 100%; background-color: #f2f2f2; border-top: 1px solid gray; height:25px; padding: 10px 30px 10px 0px !important; position: fixed; bottom: 0; left: 0; right: 0; z-index: 1000;}';
		echo '</style>';
		echo '<script>';
		echo 'function chkstate(){';
		echo '	if(document.readyState=="complete"){';
		echo '		window.close()';
		echo '	}';
		echo '	else{';
		echo '		setTimeout("chkstate()",2000)';
		echo '	}';
		echo '}';
		echo 'function print_win(){';
		echo '	window.print();';
		echo '	chkstate();';
		echo '}';
		echo 'function close_win(){';
		echo '	window.close();';
		echo '}';
		echo '</script>';
		echo '</head>';

		echo '<body style="margin:5px; padding:5px;" onBlur="window.close();">'; 
		echo '<input type="hidden" name="anketa_id" id="srv_meta_anketa_id" value="' . $_REQUEST['anketa'] . '" />';
		#echo '<div id="div_analiza_single_var" class="container">';
		echo '<div id="analiza_data">';
		//Izvoz v PDF / RTF / XLS
		$_url1 = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
				serialize(
						array(	'b'=>'export',
								'a'=>'list_pdf',
								'anketa'=>$anketa)));
		$_url2 = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
				serialize(
						array(	'b'=>'export',
								'a'=>'list_rtf',
								'anketa'=>$anketa)));
		$_url3 = $site_url.'admin/survey/izvoz.php?dc='.base64_encode(
				serialize(
						array(	'b'=>'export',
								'a'=>'list_xls',
								'anketa'=>$anketa)));
		echo '<div class=" printHide" style="margin-top:6px;">';
		echo '<a href="'.$_url1.'" target="_blank"><span class="faicon pdf icon-as_link"></span></a>&nbsp;&nbsp;';
		echo '<a href="'.$_url2.'" target="_blank"><span class="faicon rtf icon-as_link"></span></a>&nbsp;&nbsp;';
		echo '<a href="'.$_url3.'" target="_blank"><span class="faicon xls icon-as_link"></span></a>';
		
		echo '<br class="clr"/>';
		echo $lang['srv_data_print_preview'];
		echo '</div>';
		
		self::$printPreview = true;
		self::$_VARS[VAR_DATA] = 1;
		self::$_VARS[VAR_SPR_LIMIT] = 5;
		self::$_VARS[VAR_META] = 0;
		self::$_VARS[VAR_EMAIL] = 0;
		self::$_VARS[VAR_RELEVANCE] = 0;
		self::$_VARS[VAR_EDIT] = 0;
		self::$_VARS[VAR_PRINT] = 0;
		self::$_VARS[VAR_MONITORING] = 0;
		if (isset(self::$_SVP_PV['invitation'])) {
			unset(self::$_SVP_PV['invitation']);
		}
		
	
			# ponastavimo nastavitve- filter
		self::setUpFilter();
		self::DisplayDataTable();
		echo '</div>';
			
		echo '<div id="navigationBottom" class="printHide">';

		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="close_win(); return false;"><span>'.$lang['srv_zapri'].'</span></a></div></span>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="window.print();return false;"><span class="faicon print_small icon-grey_dark_link"></span> '.$lang['hour_print2'].'</a></div></span>';
		
		echo '<span  class="spaceRight floatRight printHide" style="margin-top:6px;">';
		echo '<a href="'.$_url1.'" target="_blank"><span class="faicon pdf icon-as_link"></span></a>&nbsp;&nbsp;';
		echo '<a href="'.$_url2.'" target="_blank"><span class="faicon rtf icon-as_link"></span></a>&nbsp;&nbsp;';
		echo '<a href="'.$_url3.'" target="_blank"><span class="faicon xls icon-as_link"></span></a>';
		echo '</span>';
		
		echo '<br class="clr" />';
		echo '</div>';
		
		echo '</body>';
		echo '</html>';
	}

	static function displayVnosIcons() {
		global $lang;
        global $global_user_id;
        
		$userAccess = UserAccess::getInstance($global_user_id);

		// gumbi na levi (delete, edit, izvozi...)
		echo '<div id="left_options">';
		
		echo '<span class="faicon delete_circle large icon-orange_link" title="'.$lang['srv_delete_data_row'].'" onClick="quickEditAction(\'delete\', \''.self::$usr_id.'\');"></span>';
		echo '<span class="faicon edit_square large icon-grey_dark_link" title="'.$lang['srv_edit_data_row'].'" onClick="quickEditAction(\'edit\', \''.self::$usr_id.'\');"></span>';
        echo '<span class="faicon print_small large icon-grey_dark_link" title="'.$lang['PRN_Izpis'].'" onClick="printAnaliza(\'Vpogled\'); return false;"></span>';

        // Ce imamo izvoze v paketu
        if($userAccess->checkUserAccess($what='data_export')){
            echo '<span class="faicon pdf large icon-grey_dark_link" title="'.$lang['PDF_Izpis'].'" onClick="quickEditAction(\'pdf\', \''.self::$usr_id.'\');"></span>';
            echo '<span class="faicon rtf large icon-grey_dark_link" title="'.$lang['RTF_Izpis'].'" onClick="quickEditAction(\'rtf\', \''.self::$usr_id.'\');"></span>';
        }
        else{
            echo '<span class="faicon pdf large icon-grey_dark_link user_access_locked" title="'.$lang['PDF_Izpis'].'" onClick="popupUserAccess(\'data_export\');"></span>';
            echo '<span class="faicon rtf large icon-grey_dark_link user_access_locked" title="'.$lang['RTF_Izpis'].'" onClick="popupUserAccess(\'data_export\');"></span>';
        }

		echo '<span class="faicon copy large icon-grey_dark_link" title="'.$lang['srv_copy_data'].'" onClick="quickEditAction(\'copy\', \''.self::$usr_id.'\');"></span>';
		
		// omogocimo/onemogocimo popravljanje vnosa
		if(isset($_GET['quick_view']) && $_GET['quick_view'] == 0){
			echo '<span class="faicon edit large icon-grey_dark_link_reverse" title="'.$lang['srv_quick_view_off'].'" onClick="quickEditAction(\'quick_view\', \''.self::$usr_id.'\');"></span>';
			echo '<input type="hidden" id="quick_view" value="0">';
		}
		else{
			echo '<span class="faicon edit large icon-grey_dark_link" title="'.$lang['srv_quick_view_on'].'" onClick="quickEditAction(\'quick_view\', \''.self::$usr_id.'\');"></span>';
			echo '<input type="hidden" id="quick_view" value="1">';
		}
		echo '</div>';
	}

	static function displayStatusLegend (){
	
		if (self::$dataFileStatus >= 0) { 
			#status - če smo v meta ali imamo profil vse enote
			if ( (self::$_VARS[VAR_META] && self::$_VARS[VAR_METAFULL])
					|| ( SurveyStatusProfiles::getCurentProfileId() == 1 )) {
				SurveyAnalysisHelper::getInstance()->displayStatusLegend();
			}
		}
	}
	static function displayTestLegend (){
	
		if (self::$dataFileStatus >= 0) { 
			#testni vnosi - samo ce imamo testne
			if (self::$_HAS_TEST_DATA) {
				SurveyAnalysisHelper::getInstance()->displayTestLegend();
			}
		}
	}
	static function displayMetaStatusLegend (){
	
		if (self::$dataFileStatus >= 0) {
			SurveyAnalysisHelper::getInstance()->displayMissingLegend();
		}
	}
	
	static function DisplaySnLinks() {
		global $lang, $site_url;
		
		echo '<div id="data_sn_buttons">';
		
		// Gumb za preklop na EGO
		echo '<span>';
		echo '<a href="'.$site_url.'admin/survey/index.php?anketa='.self::$sid.'&a='.A_COLLECT_DATA.'&m='.self::$subAction.self::getVars(VAR_CIRCLES, '0').'"'.((int)self::$_VARS[VAR_CIRCLES]==0?' class="red"':'').'>'.$lang['srv_lnk_ego'].'</a>';
		echo '</span>';
		
		// Gumb za preklop na ALTER
		echo '<span>';
		echo '<a href="'.$site_url.'admin/survey/index.php?anketa='.self::$sid.'&a='.A_COLLECT_DATA.'&m='.self::$subAction.self::getVars(VAR_CIRCLES, '1').'"'.((int)self::$_VARS[VAR_CIRCLES]==1?' class="red"':'').'>'.$lang['srv_lnk_alter'].'</a>';
		echo '</span>';
		
		echo '</div>';	
	}	
	
	static function setSnDisplayFullTableCheckbox() {
	
		session_start();
		
		$_SESSION['sid_'.self::$sid]['snCreateFullTable'] = (int)$_POST['fullTable'] == 1;
		
		session_commit();
	}
	
	static function getLineNumberCellOffset($line) {
	
		$offset = 0;

        // Ce nismo na prvi strani spremenljivk je offset drugacen in prikazemo stevilko na zacetku
        if(self::$_VARS[VAR_SPR_PAGE] > 1){
            $offset = strpos($line, '</td><td>');
        }
		elseif (self::$lineoffset > 0 ) {
			for ($i = 0; $i < self::$lineoffset; $i++) {
				$offset = strpos($line,'</td><td>',$offset+1);
			}
		}
		
		return $offset;
	}	
	
	static function showMultiple () {
		
		$sql = sisplet_query("SELECT * FROM srv_grid_multiple WHERE ank_id = ".self::$sid." GROUP BY parent");
		if ( mysqli_num_rows($sql) == 1 )
			return true;
			
		return false;
	}
}
?>