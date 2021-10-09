<?php
/** Klass ki vodi seznam anket za prikaz na prvi stranu.
 * se posodablja sproti za ankete ki jih uporabnik trenutno preglejuje
 * 
 * Enter description here ...
 * @author Gorazd_Veselic
 *
 */

define('SRV_LIST_ORDER_BY', 16);				# privzeto: sortiranje po stolpcu 1
define('SRV_LIST_ORDER_TYPE', 1);				# privzeto: sortiranje padajoče
define('SRV_LIST_REC_PER_PAGE', 25);			# privzeto: koliko zapisov na stran prikažemo
define('SRV_LIST_GET_AS_LIST', true);			# privzeto: ali lovimo kot seznam ali kot drevo folderjev
define('SRV_LIST_GET_SUB_FOLDERS', true);		# privzeto: ali poizvedujemo po poddirektorijih
define('SRV_LIST_CHECK_DOSTOP', true);		# ali preverja dostop na nivoju ankete
define('SRV_LIST_GROUP_PAGINATE', 5);			# po kolko strani grupira pri paginaciji
define('SRV_LIST_UPDATE_TIME_LIMIT', 900);	# na koliko minut updejtamo: 15min = 60s*15

if(session_id() == '') {session_start();}


class SurveyList {
	
	private $surveys_ids = array();					# array z id-ji anket
	private $settingsArray = array();				# array z nastavitvami

	private $parentFolder;							# osnovni direktorij
	private $currentFolder;							# trenutni direktorij
	private $folders = array();						# array z direktoriji
	
	private $user_id = null;						# ali filtriramo po userju
	private $g_uid = null;							# globalna nastavitev user_id
	private $g_adminType = null;					# globalna nastavitev adminType
	
	
	private $onlyPhone = false;						# Ali prikazujemo samo telefonske ankete
	
    private $lang_id = 0;							# nastavitev languageType
    
	private $gdpr = 0;							    # nastavitev gdpr filter za ankete

	private $dostopCondition = null;				# shranimo omejitve dostopa (glede na tip uporabnika in uporabniški uid)
	private $folderCondition = null;				# shranimo omejevanja folderjev
	
	private $libraryCondition = null;				# shranimo novo omejevanje folderjev (moja knjiznica)
	private $currentLibrary;						# trenutni direktorij moje knjiznice
	
	private $filter = null;							# filter za ime ankete
	
	private $show_folders = 0;						# ali prikazujemo mape ali ne (default zaenkrat da)
	
	
	private $isSearch = 0;							# ali izvajamo search po anektah
	private $searchString = '';						# geslo po katerem iscemo po anketah
	private $searchStringProcessed = array();			# geslo po katerem iscemo po anketah, obdelano (skrajsano da isce tudi po drugih sklanjatvah)
	private $searchSettings = array();				# nastavitve searcha
	
	
	# privzete nastavitve
	private $pageno = 1;							# na kateri strani navigacije smo 
	private $max_pages = 1;							# koliko strani imamo
	private $sortby = SRV_LIST_ORDER_BY;			# id polja po katerem sortiramo
	private $sorttype = SRV_LIST_ORDER_TYPE;		# tip sortiranja 1= deac, 0 = asc
	private $rec_per_page = SRV_LIST_REC_PER_PAGE;	# število zapisov na stran
	private $orderByText = false;					# ali sortiramo po tekstovnem polju (takrat uporabimo upper)
	
	# privzete možnosti
	private $sort_types_options = array(1=>'DESC',2=>'ASC'); 	# možni načini sortiranja
	private $appropriateStatus = '5,6';							# statusi ki veljajo kot ustrezni
	
	# možni načini sortiranja
	private $order_by_options = array(
		1=>'naslov', 	
		2=>'active',
		3=>'lib_glb',
		4=>'lib_usr',
		5=>'answers',
		6=>'variables',
		7=>'i_name',
		8=>'i_surname',
		9=>'i_email',
		10=>'insert_time',
		11=>'e_name',
		12=>'e_surname',
		13=>'e_email',
		14=>'edit_time',
		15=>'a_first',
		16=>'a_last',
		17=>'starts',
		18=>'expire',
		19=>'survey_type',
		#20=>'del',
		20=>'naslov', # po ikoni za brisanje ne moremo sortirat
		21=>'approp',
	);
										
	# polja za prikaz									
	private $dataFields = array(
		1=>array('id'=>1,'visible'=>1,'order_by'=>'naslov','header_field'=>'sl_naslov','alsoResize'=>'.sl_naslov','minWidth'=>100,'data_field'=>'naslov','data_type'=>'naslov', 'data_css'=>'sl_naslov_padding', 'order_text'=>true),
		2=>array('id'=>2,'visible'=>1,'order_by'=>'active','header_field'=>'sl_active','lang_label'=>'sl_active_1','alsoResize'=>'.sl_active','minWidth'=>20,'maxWidth'=>30,'data_field'=>'active','data_type'=>'active','data_css'=>'anl_ac'),
		3=>array('id'=>3,'visible'=>0,'order_by'=>'lib_glb','header_field'=>'sl_lib_glb','lang_label'=>'sl_lib_glb_1','alsoResize'=>'.sl_lib_glb','minWidth'=>20,'maxWidth'=>30,'data_field'=>'lib_glb','data_type'=>'lib_glb','data_css'=>'anl_ac'),
		4=>array('id'=>4,'visible'=>1,'order_by'=>'lib_usr','header_field'=>'sl_lib_usr','lang_label'=>'sl_lib_usr_1','alsoResize'=>'.sl_lib_usr','minWidth'=>20,'maxWidth'=>100,'data_field'=>'lib_usr','data_type'=>'lib_usr','data_css'=>'anl_ac'),
		5=>array('id'=>5,'visible'=>1,'order_by'=>'answers','header_field'=>'sl_answers','lang_label'=>'sl_answers_1','alsoResize'=>'.sl_answers','minWidth'=>25,'maxWidth'=>100,'data_field'=>'answers','data_css'=>'anl_ac'),
		6=>array('id'=>6,'visible'=>1,'order_by'=>'variables','header_field'=>'sl_variables','lang_label'=>'sl_variables_1','alsoResize'=>'.sl_variables','minWidth'=>25,'maxWidth'=>100,'data_field'=>'variables','data_css'=>'anl_ac'),
		7=>array('id'=>7,'visible'=>0,'order_by'=>'i_name','header_field'=>'sl_i_name','header_grupa'=>'h_sl_avtor','alsoResize'=>'#h_sl_avtor_holder,#h_sl_avtor_title,#h_sl_avtor,.sl_i_name','minWidth'=>30,'data_field'=>'i_name','data_type'=>'iuid','order_text'=>true),
		8=>array('id'=>8,'visible'=>0,'order_by'=>'i_surname','header_field'=>'sl_i_surname','header_grupa'=>'h_sl_avtor','alsoResize'=>'#h_sl_avtor_holder,#h_sl_avtor_title,#h_sl_avtor,.sl_i_surname','minWidth'=>30,'data_field'=>'i_surname','data_type'=>'iuid','order_text'=>true),
		9=>array('id'=>9,'visible'=>0,'order_by'=>'i_email','header_field'=>'sl_i_email','header_grupa'=>'h_sl_avtor','alsoResize'=>'#h_sl_avtor_holder,#h_sl_avtor_title,#h_sl_avtor,.sl_i_email','minWidth'=>30,'data_field'=>'i_email','data_type'=>'iuid','order_text'=>true),
		10=>array('id'=>10,'visible'=>1,'order_by'=>'insert_time','header_field'=>'sl_i_time','header_grupa'=>'h_sl_avtor','alsoResize'=>'#h_sl_avtor_holder,#h_sl_avtor_title,#h_sl_avtor,.sl_i_time','minWidth'=>50,'data_field'=>'i_time'),
		11=>array('id'=>11,'visible'=>1,'order_by'=>'e_name','header_field'=>'sl_e_name','header_grupa'=>'h_sl_spreminjal','alsoResize'=>'#h_sl_spreminjal_holder,#h_sl_spreminjal_title,#h_sl_spreminjal,.sl_e_name','minWidth'=>30,'data_field'=>'e_name','data_type'=>'euid','order_text'=>true),
		12=>array('id'=>12,'visible'=>0,'order_by'=>'e_surname','header_field'=>'sl_e_surname','header_grupa'=>'h_sl_spreminjal','alsoResize'=>'#h_sl_spreminjal_holder,#h_sl_spreminjal_title,#h_sl_spreminjal,.sl_e_surname','minWidth'=>30,'data_field'=>'e_surname','data_type'=>'euid','order_text'=>true),
		13=>array('id'=>13,'visible'=>0,'order_by'=>'e_email','header_field'=>'sl_e_email','header_grupa'=>'h_sl_spreminjal','alsoResize'=>'#h_sl_spreminjal_holder,#h_sl_spreminjal_title,#h_sl_spreminjal,.sl_e_email','minWidth'=>30,'data_field'=>'e_email','data_type'=>'euid','order_text'=>true),
		14=>array('id'=>14,'visible'=>1,'order_by'=>'edit_time','header_field'=>'sl_e_time','header_grupa'=>'h_sl_spreminjal','alsoResize'=>'#h_sl_spreminjal_holder,#h_sl_spreminjal_title,#h_sl_spreminjal,.sl_e_time','minWidth'=>50,'data_field'=>'e_time'),
		15=>array('id'=>15,'visible'=>0,'order_by'=>'vnos_time_first','header_field'=>'sl_vnos_time_first','alsoResize'=>'.sl_vnos_time_first','minWidth'=>30,'data_field'=>'v_time_first'),
		16=>array('id'=>16,'visible'=>1,'order_by'=>'vnos_time_last','header_field'=>'sl_vnos_time_last','alsoResize'=>'.sl_vnos_time_last','minWidth'=>30,'data_field'=>'v_time_last'),
		17=>array('id'=>17,'visible'=>1,'order_by'=>'trajanjeod','header_field'=>'sl_trajanjeod','header_grupa'=>'h_sl_trajanje','alsoResize'=>'#h_sl_trajanje_holder,#h_sl_trajanje_title,#h_sl_trajanje,.sl_trajanjeod','minWidth'=>30,'data_field'=>'trajanjeod'),
		18=>array('id'=>18,'visible'=>1,'order_by'=>'trajanjedo','header_field'=>'sl_trajanjedo','header_grupa'=>'h_sl_trajanje','alsoResize'=>'#h_sl_trajanje_holder,#h_sl_trajanje_title,#h_sl_trajanje,.sl_trajanjedo','minWidth'=>30,'data_field'=>'trajanjedo'),
		19=>array('id'=>19,'visible'=>0,'order_by'=>'survey_type','header_field'=>'sl_survey_type','lang_label'=>'sl_survey_type_1','alsoResize'=>'.sl_survey_type','minWidth'=>30,'data_field'=>'survey_type','data_type'=>'survey_type','order_text'=>true),
		20=>array('id'=>20,'visible'=>1,'order_by'=>'delete','header_field'=>'sl_delete','lang_label'=>'sl_delete_1','alsoResize'=>'.sl_delete','minWidth'=>20,'maxWidth'=>30,'data_field'=>'del','data_type'=>'delete','data_css'=>'anl_ac'),
		21=>array('id'=>21,'visible'=>0,'order_by'=>'approp','header_field'=>'sl_approp','lang_label'=>'sl_approp_1','alsoResize'=>'.sl_approp','minWidth'=>25,'maxWidth'=>100,'data_field'=>'approp','data_css'=>'anl_ac'),
	); 
									
											
	public function __construct() {
		
		global $admin_type, $global_user_id, $site_path;

		$this->lang_id = 0;
		$this->g_uid = $global_user_id;
		$this->g_adminType = $admin_type;
		
		$this->onlyPhone = false;
		if ( (isset($_REQUEST['onlyPhone']) && $_REQUEST['onlyPhone'] == 'true') 
			|| (isset($_REQUEST['a']) && $_REQUEST['a'] == 'phoneSurveys')) {
			$this->onlyPhone = true;
		}
		
		# Anketam ki so potekle popravimo aktivnost
		$this->checkSurveyExpire();
		
		# vsilimo refresh podatkov
		SurveyInfo :: getInstance()->resetSurveyData();
		
		#Inicializiramo (preberemo potrebne nastavitve iz baze, in pogledamo katero stran prikazujemo)
		$this -> init();
		
		# posodobimo podatke prikazanih anket
		$this -> refreshData();		
	}
		
	private function init($parentFolder = 0) {
		global $global_user_id;
				
		# pogledamo ali imamo shranjeno nastavitev po katerem polj u sortiramo
		UserSetting::getInstance()->Init($global_user_id);
		
		$userSettins = UserSetting::getInstance()->getUserSetting('survey_list_order_by');
		if ( isset($userSettins) && $userSettins != null) {
			$old_data = array_unique(explode(",",$userSettins));
			if (isset($old_data[0]) && $old_data[0] > 0) {
				$this->sortby = $old_data[0];
				if (isset($old_data[1]) && $old_data[1] > 0) {
					$this->sorttype = $old_data[1];
				}
			}
		}
		$this->setParentFolder($parentFolder);
		$this->setCurrentFolder($parentFolder);
		
        // Ali prikazujemo folderje ali ne
        $userAccess = UserAccess::getInstance($global_user_id);
        if($userAccess->checkUserAccess('my_survey_folders'))
		    $this->show_folders = UserSetting::getInstance()->getUserSetting('survey_list_folders');
		
		# koliko zapisov prikazujemo na stran
		$survey_list_rows_per_page = UserSetting::getInstance()->getUserSetting('survey_list_rows_per_page');
		if (isset($survey_list_rows_per_page) && $survey_list_rows_per_page != "" && (int)$survey_list_rows_per_page > 0) {
			$this->rec_per_page = (int)$survey_list_rows_per_page;
		} 
		#session_start();
		# preverimo ali imamo filter po userju
		if ($_GET['a'] == 'surveyList_user' && (int)$_POST['uid'] > 0 && ($_POST['list_user_type'] == 'i' || $_POST['list_user_type'] == 'e' || $_POST['list_user_type'] == 'clr')) {
			$this->setUserId();	
		} else if (isset($_SESSION['sl_uid']) && (int)$_SESSION['sl_uid'] > 0 && isset($_SESSION['sl_typ']) && ($_SESSION['sl_typ'] == 'e' || $_SESSION['sl_typ'] == 'i')) {
			$this->user_id = (int)$_SESSION['sl_uid'];
		}
		# nastavimo sort po jezikih
		if (isset($_SESSION['sl_lang_id']) && (int)$_SESSION['sl_lang_id'] > 0 ) {
			$this->lang_id= (int)$_SESSION['sl_lang_id'];
        }
        
        # nastavimo sort po gdpr
		if (isset($_SESSION['sl_gdpr']) && (int)$_SESSION['sl_gdpr'] > 0 ) {
			$this->gdpr = (int)$_SESSION['sl_gdpr'];
		}
		
		# nastavimo filter po folderju moje knjiznice
		if (isset($_SESSION['currentLibrary']) && (int)$_SESSION['currentLibrary'] > 0 ) {
			$this->currentLibrary = (int)$_SESSION['currentLibrary'];
		}
		
		// Preverimo ce gre za search po anketah
		if(isset($_GET['search']) && $_GET['search'] != ''){
			$this->isSearch = 1;
			$this->searchString = str_replace("\\", "", trim($_GET['search']));
			
			// Iscemo po naslovu ali avtorju
			$this->searchSettings['stype'] = (isset($_GET['stype'])) ? $_GET['stype'] : '0';
			
			// Aktivnost ankete
			$this->searchSettings['sstatus'] = (isset($_GET['sstatus'])) ? $_GET['sstatus'] : '0';
			
			// Datum ustvarjanja ankete
			$this->searchSettings['sidatefrom'] = (isset($_GET['sidatefrom'])) ? $_GET['sidatefrom'] : '';
			$this->searchSettings['sidateto'] = (isset($_GET['sidateto'])) ? $_GET['sidateto'] : '';
			
			// Datum zadnjega urejanja ankete
			$this->searchSettings['sedatefrom'] = (isset($_GET['sedatefrom'])) ? $_GET['sedatefrom'] : '';
			$this->searchSettings['sedateto'] = (isset($_GET['sedateto'])) ? $_GET['sedateto'] : '';
			
			// Dodaten filter po avtorju (samo metaadmini)
			$this->searchSettings['onlyAuthor'] = (isset($_GET['onlyAuthor'])) ? str_replace("\\", "", trim($_GET['onlyAuthor'])) : '';
		}
		
		$this->reloadSurveys();
		
		return count($this->surveys_ids);
	}
	
	
	/**
    * @desc prikaze zgornjo navigacijo
    */
    function display_sub_tabs () {
    	global $lang, $global_user_id, $admin_type, $site_domain, $aai_instalacija;

		$SLCount = $this->countSurveys();

		// ***  druga vrstica navigacije  ***//
		echo '<div id="secondNavigation" class="library clr">';    	

    	if ($_GET['a']=='diagnostics') {
            
            if ($_GET['t'] == 'uporabniki') {
                
                // Admini imajo pregled nad vsemi zavihki uporabnikov
                if ($admin_type == 0) {
					echo '<ul class="secondNavigation">';

                    #zavihek osnovni pregled uporabnikov
					echo '<li>';
					echo '<a class="no-img single '.((!isset($_GET['m']) || $_GET['m'] == 'my') ? 'active' : '').'"'
						 .' href="index.php?a=diagnostics&t=uporabniki" title="'.$lang['n_users_list_all'].'">';
					echo $lang['n_users_list'].'</a>';
                    echo '</li>';
                    
                    echo '<li class="space"></li>';

					#zavihek seznam vseh uporabnikov
					echo '<li>';
					echo '<a class="no-img single '.($_GET['m'] == 'all' ? 'active' : '').'"'
						 .' href="index.php?a=diagnostics&t=uporabniki&m=all" title="'.$lang['n_users_list_all'].'">';
					echo $lang['n_users_list_all'].'</a>';
					echo '</li>';

					echo '<li class="space"></li>';
					
					#zavihek izbrisani uporabniki
					echo '<li>';
						echo '<a class="no-img single '.(!empty($_GET['m']) && $_GET['m'] == 'izbrisani' ? 'active' : '').'"'
							.' href="index.php?a=diagnostics&t=uporabniki&m=izbrisani" title="'.$lang['n_deleted_users'].'">';
						echo $lang['n_deleted_users'].'</a>';
					echo '</li>';

					echo '<li class="space"></li>';
					
					#zavihek odjavljeni uporabniki
					echo '<li>';
						echo '<a class="no-img single '.(!empty($_GET['m']) && $_GET['m'] == 'nepotrjeni' ? 'active' : '').'"'
							.' href="index.php?a=diagnostics&t=uporabniki&m=nepotrjeni" title="'.$lang['n_unconfirmed_users'].'">';
						echo $lang['n_unconfirmed_users'].'</a>';
					echo '</li>';

					echo '<li class="space"></li>';
					
					#zavihek nepotrjenih uporabnikov
					echo '<li>';
						echo '<a class="no-img single '.(!empty($_GET['m']) && $_GET['m'] == 'odjavljeni' ? 'active' : '').'"'
							.' href="index.php?a=diagnostics&t=uporabniki&m=odjavljeni" title="'.$lang['n_unsigned_users'].'">';
						echo $lang['n_unsigned_users'].'</a>';
					echo '</li>';

					echo '<li class="space"></li>';
					
					#zavihek sa modul
					echo '<li>';
					echo '<a class="no-img single '.(!empty($_GET['m']) && $_GET['m'] == 'sa-modul' ? 'active' : '').'"'
						.' href="index.php?a=diagnostics&t=uporabniki&m=sa-modul" title="'.$lang['srv_hierarchy_users_access'].'">';
					echo $lang['srv_hierarchy_users_access'].'</a>';
					echo '</li>';

					echo '</ul>';
                }
                
                // Menegerji imajo samo osnovni pregled svojih uporabnikov
                if ($admin_type == 1) {

					echo '<ul class="secondNavigation">';

                    #zavihek osnovni pregled uporabnikov
					echo '<li>';
					echo '<a class="no-img single active" href="index.php?a=diagnostics&t=uporabniki" title="'.$lang['n_users_list_all'].'">';
					echo $lang['n_users_list'].'</a>';
                    echo '</li>';
                    
					echo '</ul>';
				}
            } 
            else {

				if ($admin_type == 0) {
					echo '<ul class="secondNavigation">';
					#zavihek ankete
					echo '<li>';
					echo '<a class="no-img side-left'.(($_GET['t']=='ankete'||$_GET['a']=='diagnostics')&& !isset($_GET['t']) ? ' active' : '').'"'
						 .' href="index.php?a=diagnostics" title="'.$lang['srv_ankete'].'">';
					echo $lang['srv_ankete'].'</a>';
					echo '</li>';
					
					echo '<li class="space"></li>';
					
					echo '<li>';
					echo '<a class="no-img'.(($_GET['t']=='time_span_daily') ? ' active' : '').'"'
						 .' href="index.php?a=diagnostics&t=time_span_daily" title="'.$lang['srv_weekly_diagnostics'].'">';
					echo $lang['srv_weekly_diagnostics'].'</a>';
					echo '</li>';
					
					echo '<li class="space"></li>';
					
					echo '<li>';
					echo '<a class="no-img'.(($_GET['t']=='time_span_monthly') ? ' active' : '').'"'
						 .' href="index.php?a=diagnostics&t=time_span_monthly" title="'.$lang['srv_monthly_diagnostics'].'">';
					echo $lang['srv_monthly_diagnostics'].'</a>';
					echo '</li>';
					
					echo '<li class="space"></li>';
					
					echo '<li>';
					echo '<a class="no-img'.(($_GET['t']=='time_span_yearly') ? ' active' : '').'"'
						 .' href="index.php?a=diagnostics&t=time_span_yearly" title="'.$lang['srv_yearly_diagnostics'].'">';
					echo $lang['srv_yearly_diagnostics'].'</a>';
					echo '</li>';
					
					echo '<li class="space"></li>';
					
					echo '<li>';
					echo '<a class="no-img side-right'.(($_GET['t']=='time_span') ? ' active' : '').'"'
						 .' href="index.php?a=diagnostics&t=time_span&uvoz=0&ustrezni=1&delnoustrezni=1&neustrezni=0" title="'.$lang['srv_all_diagnostics'].'">';
					echo $lang['srv_all_diagnostics'].'</a>';
					echo '</li>';
					
					echo '<li class="space"></li>';
					
					echo '<li>';
					echo '<a class="no-img side-right'.(($_GET['t']=='paradata') ? ' active' : '').'"'
						 .' href="index.php?a=diagnostics&t=paradata" title="'.$lang['srv_metapodatki'].'">';
					echo $lang['srv_metapodatki'].'</a>';
					echo '</li>';
	
					echo '</ul>';
				}
    			
    		}
		}
		#podnavigacija za knjižnico
		if( $_GET['a']=='knjiznica' ) {
			
			echo '<ul class="secondNavigation">';
			
			#zavihek javna knjižnica
			echo '<li>';
			echo '<a class="no-img side-left'.(($_GET['t']=='javne_ankete'||$_GET['a']=='knjiznica')&& !isset($_GET['t']) ? ' active' : '').'"'
				 .' href="index.php?a=knjiznica" title="'.$lang['srv_javna_knjiznica'].'">';
			echo $lang['srv_javna_knjiznica'].'</a>';
			echo '</li>';
			echo '<li class="space"></li>';
			
			# Zavihek moja knjižnica	
			echo '<li>';
			echo '<a class="no-img side-right'.($_GET['t'] == 'moje_ankete' ? ' active' : '').'"'
				 .' href="index.php?a=knjiznica&t=moje_ankete" title="'.$lang['srv_moja_knjiznica'].'">';
			echo $lang['srv_moja_knjiznica'].'</a>';
			echo '</li>';
			
			echo '</ul>';
		}
		#podnavigacija za nastavitve
        if(($_GET['a']=='nastavitve')) {
			
			$tab = $_GET['m'];
					
			echo '<ul class="secondNavigation">';
			
			if ($admin_type == '0') {
				#zavihek sistemske
				echo '<li>';
				echo '<a class="no-img side-left'.($tab=='system' || $tab == '' ? ' active' : '').'"'
					 .' href="index.php?a=nastavitve&amp;m=system" title="'.$lang['srv_settingsSystem'].'">';
				echo $lang['srv_settingsSystem'].'</a>';
				echo '</li>';
				echo '<li class="space"></li>';
				# Zavihek ocenjevanje trajanja	
				echo '<li>';
				echo '<a class="no-img'.($tab=='predvidenicasi' ? ' active' : '').'"'
					 .' href="index.php?a=nastavitve&amp;m=predvidenicasi" title="'.$lang['srv_testiranje_predvidenicas'].'">';
				echo $lang['srv_testiranje_predvidenicas'].'</a>';
				echo '</li>';
				echo '<li class="space"></li>';
				# Zavihek mape	
				echo '<li>';
				echo '<a class="no-img'.($tab=='collectData' ? ' active' : '').'"'
					 .' href="index.php?a=nastavitve&amp;m=collectData" title="'.$lang['srv_collectData'].'">';
				echo $lang['srv_collectData'].'</a>';
				echo '</li>';
				echo '<li class="space"></li>';
				# Zavihek lep url
				echo '<li>';
				echo '<a class="no-img'.($tab=='nice_links' ? ' active' : '').'"'
					 .' href="index.php?a=nastavitve&amp;m=nice_links" title="'.$lang['srv_nice_url'].'">';
				echo $lang['srv_nice_url'].'</a>';
				echo '</li>';
				echo '<li class="space"></li>';
				# ankete z administrativnim dostopom
				echo '<li>';
				echo '<a class="no-img'.($tab=='anketa_admin' ? ' active' : '').'"'
					 .' href="index.php?a=nastavitve&amp;m=anketa_admin" title="'.$lang['srv_anketa_admin'].'">';
				echo $lang['srv_anketa_admin'].'</a>';
				echo '</li>';
				echo '<li class="space"></li>';
				# zbrisane ankete
				echo '<li>';
				echo '<a class="no-img'.($tab=='anketa_deleted' ? ' active' : '').'"'
					 .' href="index.php?a=nastavitve&amp;m=anketa_deleted" title="'.$lang['srv_anketa_deleted'].'">';
				echo $lang['srv_anketa_deleted'].'</a>';
				echo '</li>';
				echo '<li class="space"></li>';
				# Zbrisani podatki
				echo '<li>';
				echo '<a class="no-img'.($tab=='data_deleted' ? ' active' : '').'"'
					 .' href="index.php?a=nastavitve&amp;m=data_deleted" title="'.$lang['srv_data_deleted'].'">';
				echo $lang['srv_data_deleted'].'</a>';
				echo '</li>';
			}
			
			// nastavitve uporabnika
			if($admin_type == 0)
				echo '<li class="space"></li>';
			
			echo '<li>';
			echo '<a class="no-img side-right'.($tab=='global_user_settings' ? ' active' : '').'"'
				 .' href="index.php?a=nastavitve&amp;m=global_user_settings" title="'.$lang['srv_user_settings'].'">';
			echo $lang['srv_user_settings'].'</a>';
			echo '</li>';
			
			echo '<li class="space"></li>';
			
			// Moj profil
            echo '<li>';
            echo '<a class="no-img side-right' . ($tab == 'global_user_myProfile' ? ' active' : '') . '"' . ' href="index.php?a=nastavitve&amp;m=global_user_myProfile" title="' . $lang['edit_data'] . '">';
            echo $lang['edit_data'] . '</a>';
            echo '</li>';
			
			echo '</ul>';
		}
		#podnavigacija za obvestila
        if(($_GET['a']=='obvestila')) {
					
			echo '<ul class="secondNavigation">';
			
			# prejeta obvestila
			echo '<li>';
			echo '<a class="no-img side-right'.(!isset($_GET['t']) ? ' active' : '').'"'
				 .' href="index.php?a=obvestila" title="'.$lang['srv_notifications_recieved'].'">';
			echo $lang['srv_notifications_recieved'].'</a>';
			echo '</li>';
			
			if ($admin_type == '0') {
				
				echo '<li class="space"></li>';
				
				# poslana obvestila
				echo '<li>';
				echo '<a class="no-img side-left'.($_GET['t']=='sent' ? ' active' : '').'"'
					 .' href="index.php?a=obvestila&amp;t=sent" title="'.$lang['srv_notifications_sent'].'">';
				echo $lang['srv_notifications_sent'].'</a>';
				echo '</li>';
			}
			
			echo '</ul>';
        }
        #podnavigacija za narocila
        if(($_GET['a']=='narocila')) {
					
			echo '<ul class="secondNavigation">';
			
			# moja narocila
			echo '<li>';
			echo '<a class="no-img side-right'.(!isset($_GET['m']) ? ' active' : '').'"'
				 .' href="index.php?a=narocila" title="'.$lang['srv_narocila_my'].'">';
			echo $lang['srv_narocila_my'].'</a>';
            echo '</li>';

            echo '<li class="space"></li>';
            
            # placila - samo admini
            if ($admin_type == '0') {
                echo '<li>';
                echo '<a class="no-img side-right'.((isset($_GET['m']) && $_GET['m'] == 'placila') ? ' active' : '').'"'
                    .' href="index.php?a=narocila&m=placila" title="'.$lang['srv_placila'].'">';
                echo $lang['srv_placila'].'</a>';
                echo '</li>';
            }
			
			echo '</ul>';
		}
		# podnavigacija za gdpr
		if($_GET['a']=='gdpr'){
			
			echo '<ul class="secondNavigation">';
			
			// Nastavitve uporabnika
			echo '<li>';
			echo '<a class="no-img side-right'.(!isset($_GET['m']) || $_GET['m']=='gdpr_user' ? ' active' : '').'"'
				 .' href="index.php?a=gdpr&amp;m=gdpr_user" title="'.$lang['srv_gdpr_user_settings'].'">';
			echo $lang['srv_gdpr_user_settings'].'</a>';
			echo '</li>';
			
			echo '<li class="space"></li>';
			
			// Seznam anket
			echo '<li>';
			echo '<a class="no-img side-right'.($_GET['m']=='gdpr_survey_list' ? ' active' : '').'"'
				 .' href="index.php?a=gdpr&amp;m=gdpr_survey_list" title="'.$lang['srv_gdpr_survey_list'].'">';
			echo $lang['srv_gdpr_survey_list'].'</a>';
			echo '</li>';
			
            echo '<li class="space"></li>';
            
            // DPA
			echo '<li>';
			echo '<a class="no-img side-right'.($_GET['m']=='gdpr_dpa' ? ' active' : '').'"'
				 .' href="index.php?a=gdpr&amp;m=gdpr_dpa" title="'.$lang['srv_gdpr_dpa'].'">';
			echo $lang['srv_gdpr_dpa'].'</a>';
			echo '</li>';
			
			echo '<li class="space"></li>';
			
			// Zahteve za izbris
			echo '<li>';
			$request_counter = GDPR::countUserUnfinishedRequests();
			$request_counter_text = ($request_counter > 0) ? ' <sup class="red" style="vertical-align: top;">('.$request_counter.')</sup>' : '';
			echo '<a class="no-img side-right'.($_GET['m']=='gdpr_requests' ? ' active' : '').'"'
				 .' href="index.php?a=gdpr&amp;m=gdpr_requests" title="'.$lang['srv_gdpr_requests'].'">';
			echo $lang['srv_gdpr_requests'].$request_counter_text.'</a>';
			echo '</li>';
			
			// Vse zahteve za izbris - samo ADMINI
			if($admin_type == '0'){
				
				echo '<li class="space"></li>';
				
				echo '<li>';
				echo '<a class="no-img side-right'.($_GET['m']=='gdpr_requests_all' ? ' active' : '').'"'
					 .' href="index.php?a=gdpr&amp;m=gdpr_requests_all" title="'.$lang['srv_gdpr_requests_all'].'">';
				echo $lang['srv_gdpr_requests_all'].'</a>';
				echo '</li>';
			}
		}		
		#podnavigacija za UL evalvacijo
        if($_GET['a']=='ul_evalvation') {
					
			echo '<ul class="secondNavigation">';
			
			# Izvozi za ul evalvacijo
			echo '<li>';
			echo '<a class="no-img side-right'.((!isset($_GET['t']) || $_GET['t'] == 'export') ? ' active' : '').'"'
				 .' href="index.php?a=ul_evalvation" title="Izvozi">';
			echo 'Izvozi</a>';
			echo '</li>';
			
			
			if ($admin_type == 0) {		
				echo '<li class="space"></li>';
				
				# Uvozi - samo admini
				echo '<li>';
				echo '<a class="no-img side-left'.($_GET['t']=='import' ? ' active' : '').'"'
					 .' href="index.php?a=ul_evalvation&amp;t=import" title="Uvozi">';
				echo 'Uvozi</a>';
				echo '</li>';
				
				echo '<li class="space"></li>';
				
				# Testiranje - samo admini
				echo '<li>';
				echo '<a class="no-img side-left'.($_GET['t']=='test' ? ' active' : '').'" href="index.php?a=ul_evalvation&amp;t=test" title="Testiranje">';
				echo 'Testiranje</a>';
				echo '</li>';
				
				echo '<li class="space"></li>';
				
				# GC - samo admini
				echo '<li>';
				echo '<a class="no-img side-left'.($_GET['t']=='gc' ? ' active' : '').'" href="index.php?a=ul_evalvation&amp;t=gc" title="GC">';
				echo 'GC</a>';
				echo '</li>';
			}
			
			echo '</ul>';
		}
		
		echo '<div id="secondNavigation_links"></div>'; # id="secondNavigation_links"
		echo '</div>'; #<div class="secondNavigation" >
		
		$this->language_change();
    }
	
    /**
    * @desc prikaze zgornjo navigacijo
    */
    function display_tabs () {
        global $lang, $global_user_id, $admin_type, $mysql_database_name;
        
    	$css_1 = 'on';
		$css_2 = 'off';
		$css_3 = 'off';
		$css_4 = 'off';
		$css_5 = 'off';
		$css_6 = 'off';
		$css_7 = 'off';
		$css_8 = 'off';
        $css_9 = 'off';
        $css_10 = 'off';
        
		if ($_GET['a'] == 'diagnostics') {
            
            if ($_GET['t'] == 'uporabniki') {
				#uporabniki
				$css_1 = 'off';
				$css_5 = 'on';
            } 
            else {
				#aktivnosti
				$css_1 = 'off';
				$css_2 = 'on';
			}
		}
		if ($_GET['a'] == 'knjiznica') {
			$css_1 = 'off';
			$css_3 = 'on';
		}
		if ($_GET['a'] == 'nastavitve') {
			$css_1 = 'off';
			$css_4 = 'on';
		}
		if ($_GET['a'] == 'nastavitve') {
			$css_1 = 'off';
			$css_4 = 'on';
		}
		if ($_GET['a'] == 'phoneSurveys') {
			$css_1 = 'off';
			$css_6 = 'on';
		}
		if ($_GET['a'] == 'obvestila') {
			$css_1 = 'off';
			$css_7 = 'on';
		}
		if ($_GET['a'] == 'ul_evalvation') {
			$css_1 = 'off';
			$css_8 = 'on';
		}
		if ($_GET['a'] == 'gdpr') {
			$css_1 = 'off';
			$css_9 = 'on';
        }
        if ($_GET['a'] == 'narocila') {
			$css_1 = 'off';
			$css_10 = 'on';
		}
		
		echo '<div id="firstNavigation" class="frontpage">';
		
							
		echo '<ol class="smaller left-side left-1ka">';
		echo '<li class="moja1ka">';
		echo '<a  href="index.php?a=pregledovanje" title="' . $lang['srv_pregledovanje'] . '">';
		echo '<div class="smaller-singlebutton-'.$css_1.'">'. $lang['srv_pregledovanje'] . '</div>';
		echo '</a>';
		echo '</li>';

		$SLCountPhoneSurvey = $this->countPhoneSurveys();
		if ($SLCountPhoneSurvey > 0 && $admin_type != '0') {
			#echo '<li class="spaceLarge">&nbsp;</li>';
			echo '<li class="spaceBig">&nbsp;</li>';
			echo '<li>';
			echo '<a  href="index.php?a=phoneSurveys" title="' . $lang['srv_telephone_surveys'] . '">';
			echo '<div class="smaller-singlebutton-'.$css_6.'">' . $lang['srv_telephone_surveys'] . '</div>';
			echo '</a>';
			echo '</li>';
		}
		
		
		$SLCount = $this->countSurveys();
		if ($SLCount > 0 && $admin_type <= 1) {
			#echo '<li class="spaceLarge">&nbsp;</li>';
			echo '<li class="spaceBig">&nbsp;</li>';
			echo '<li>';
			echo '<a  href="index.php?a=diagnostics" title="' . $lang['srv_diagnostics'] . '">';
			echo '<div class="smaller-singlebutton-'.$css_2.'">'. $lang['srv_diagnostics'] . '</div>';
			echo '</a>';
			echo '</li>';
		}
		
		# uporabniki
		if ($admin_type <= 1 /*or true*/) {
			# ni smiselno da ostali uporabniki vidijo zavihek, ker so tako prikazane samo njihove ankete
			echo '<li class="spaceBig">&nbsp;</li>';
			echo '<li>';
			echo '<a  href="index.php?a=diagnostics&t=uporabniki" title="' . $lang['hour_users'] . '">';
			echo '<div class="smaller-singlebutton-'.$css_5.'">'. $lang['hour_users'] . '</div>';
			echo '</a>';
			echo '</li>';
		}
		echo '<li class="spaceBig">&nbsp;</li>';
		echo '<li>';
		echo '<a  href="index.php?a=knjiznica" title="' . $lang['srv_library'] . '">';
		echo '<div class="smaller-singlebutton-'.$css_3.'">'. $lang['srv_library'] . '</div>';
		echo '</a>';
		echo '</li>';
		
		# Nastavitve
		//if ($admin_type == 0) {	
			echo '<li class="spaceBig">&nbsp;</li>';
		
			echo '<li>';
			echo '<a href="index.php?a=nastavitve'.($admin_type != 0 ? '&m=global_user_settings' : '').'" title="' . $lang['settings'] . '">';
			echo '<div class="smaller-singlebutton-'.$css_4.'">' . $lang['settings'] . '</div>';
			echo '</a>';
			echo '</li>';
		//}	
			
		# Streznik	
		if ($admin_type == 0 && false) {	
			echo '<li class="spaceBig">&nbsp;</li>';
		
			echo '<li>';
			echo '<a  href="http://www.1ka.si/SAR/" title="'.$lang['srv_sar'].'" target="_blank">';
			echo '<div class="smaller-singlebutton-off">'/*<span class="sprites streznik_off"></span>'*/.$lang['srv_sar'].'</div>';
			echo '</a>';
			echo '</li>';
		}
		
		# Obvestila - zaenkrat samo admin	
		if ($admin_type == 0) {	
			echo '<li class="spaceBig">&nbsp;</li>';
		
			echo '<li>';
			echo '<a href="index.php?a=obvestila'.($admin_type == 0 ? '&t=sent' : '').'" title="' . $lang['srv_notifications'] . '">';
			echo '<div class="smaller-singlebutton-'.$css_7.'">'.$lang['srv_notifications'].'</div>';
			echo '</a>';
			echo '</li>';
        }
        
        # Narocila - samo ce imamo vklopljene pakete
        global $app_settings;
        if($app_settings['commercial_packages']){
            echo '<li class="spaceBig">&nbsp;</li>';
            
            echo '<li>';
            echo '<a href="index.php?a=narocila" title="' . $lang['srv_narocila'] . '">';
            echo '<div class="smaller-singlebutton-'.$css_10.'">'.$lang['srv_narocila'].'</div>';
            echo '</a>';
            echo '</li>';
        }
		
		# GDPR
		echo '<li class="spaceBig">&nbsp;</li>';
		
		echo '<li>';
		$request_counter = GDPR::countUserUnfinishedRequests();
		$request_counter_text = (true) ? ' <sup class="red" style="vertical-align: baseline; position: relative; top: -0.4em;">('.$request_counter.')</sup>' : '';
		echo '<a href="index.php?a=gdpr'.($request_counter > 0 ? '&m=gdpr_requests' : '').'" title="' . $lang['srv_gdpr_settings'] . '">';
		echo '<div class="smaller-singlebutton-'.$css_9.'" style="margin-top: -1px">'.$lang['srv_gdpr_settings'].$request_counter_text.'</div>';
		echo '</a>';
		echo '</li>';
		
		# UL evalvacija razne nastavitve, uvozi, izvozi - samo na anketa.uni-lj.si/student (zaenkrat samo admin)
		if (Common::checkModule('evalvacija') == '1' && $admin_type == 0) {	
			echo '<li class="spaceBig">&nbsp;</li>';
		
			echo '<li>';
			echo '<a href="index.php?a=ul_evalvation" title="UL evalvacije">';
			echo '<div class="smaller-singlebutton-'.$css_8.'">UL evalvacije</div>';
			echo '</a>';
			echo '</li>';
		}
	
		echo '</ol>';

		// genialno, rewrite se nismo ucili...
		if ($lang['id'] == "1") $subdomain = "www";
		else $subdomain = "english";

		
		switch ($_GET['a']) {
			case 'pregledovanje':
				$help_url = 'http://' .$subdomain .'.1ka.si/c/790/Moja_anketa/?preid=795&from1ka=1';
			break;
			case 'diagnostics':
				if ($_GET['t'] == 'uporabniki') {
					$help_url = 'http://' .$subdomain .'.1ka.si/c/904/Uporabniki/?preid=795&from1ka=1';
				} else {
					$help_url = 'http://' .$subdomain .'.1ka.si/c/795/Aktivnost/?preid=790&from1ka=1';
				}
			break;
			case 'knjiznica':
				$help_url = 'http://' .$subdomain .'.1ka.si/c/796/Knjiznica/?preid=795&from1ka=1';
			break;
			case 'nastavitve':
				$help_url = 'http://' .$subdomain .'.1ka.si/c/797/Nastavitve/?preid=796&from1ka=1';
			break;
			default:
				$help_url = 'http://' .$subdomain .'.1ka.si/c/790/Moja_anketa/?preid=795&from1ka=1';
			break;
		}
		
		echo '<ol class="smaller left-side help-1ka" >';
		echo '  <li>';
		echo '      <a href="'.$help_url.'" title="'.$lang['srv_settings_help'].'" target="_blank" >';
		echo '          <div class="smaller-singlebutton-off">'.$lang['srv_settings_help'].'</div>';
		echo '      </a>';
		echo '  </li>';
		echo '</ol>';       

		echo '</div>';
    }

    /**
    * prikaze izbiro jezika zgoraj desno
    * 
    */
    function language_change() {
		global $lang;
		global $global_user_id;
		
		$sql = sisplet_query("SELECT lang FROM users WHERE id = '$global_user_id'");
		$row = mysqli_fetch_array($sql);
		$lang_admin = $row['lang'];
		
		echo '<div id="language_select">';
		
		if($lang_admin == 1){
			echo '<a href="#" onClick="language_change(2); return false;"><div class="flag eng"></div> <span>English</span></a>';
		}
		else{
			echo '<a href="#" onClick="language_change(1); return false;"><div class="flag slo"></div> <span>Slovenščina</span></a>';
		}
		
		echo '</div>';
    }
	
	
	
	/** Seznam vseh anket ki so na voljo posameznemu uporabniku
	 * 
	 */
	function reloadSurveys() {
		
		# polovimo seznam uporabnikovih anket
		$this->dostopCondition = null;
		$this->surveys_ids = array();
		
		$stringSurveyList = "SELECT id, backup, active, folder, dostop FROM srv_anketa sa WHERE sa.backup='0' AND sa.id > 0 AND sa.active >= 0 AND sa.invisible = '0' ".$this->getFolderCondition().$this->getDostopAnketa();
		$sqlSurveyList = sisplet_query($stringSurveyList);
		while (	$rowSurveyList = mysqli_fetch_assoc($sqlSurveyList)) {
			$this->surveys_ids[$rowSurveyList['id']] = $rowSurveyList['id'];
		}
	}

	function setUserId() {		
		# nastavimo filter po userju
		if ($_GET['a'] == 'surveyList_user' && (int)$_POST['uid'] > 0 && ($_POST['list_user_type'] == 'i' || $_POST['list_user_type'] == 'e')) {
			$_SESSION['sl_uid'] = (int)$_POST['uid']; 
			$_SESSION['sl_typ'] = $_POST['list_user_type'];
			$this->user_id =  (int)$_POST['uid'];
		} else {
			unset($_SESSION['sl_uid']);
			unset($_SESSION['sl_typ']);
			$this->user_id = null;
		}
		$this->reloadSurveys();
		# posodobimo podatke prikazanih anket
		$this -> refreshData();
		
	}
	function setUserLanguage() {		
		# nastavimo filter po userju
		if ( isset($_POST['lang_id']) && (int)$_POST['lang_id']) {
			$_SESSION['sl_lang_id'] = (int)$_POST['lang_id']; 
			$this->lang_id =  (int)$_POST['lang_id'];
		} else {
			unset($_SESSION['sl_lang_id']);
			$this->lang_id = 0;
		}
		$this->reloadSurveys();
		# posodobimo podatke prikazanih anket
		$this -> refreshData();
		
    }
    function setUserGDPR() {		
		# nastavimo filter po GDPR anketah
		if ( isset($_POST['gdpr']) && (int)$_POST['gdpr']) {
			$_SESSION['sl_gdpr'] = (int)$_POST['gdpr']; 
			$this->gdpr =  (int)$_POST['gdpr'];
		} else {
			unset($_SESSION['sl_gdpr']);
			$this->gdpr = 0;
		}
		$this->reloadSurveys();
		# posodobimo podatke prikazanih anket
		$this -> refreshData();
		
	}
	function setUserLibrary() {		
		# nastavimo filter po folderju knjiznice
		if ( isset($_POST['currentLibrary']) && (int)$_POST['currentLibrary']) {
			$_SESSION['currentLibrary'] = (int)$_POST['currentLibrary']; 
			$this->currentLibrary =  (int)$_POST['currentLibrary'];
		} else {
			unset($_SESSION['currentLibrary']);
			$this->currentLibrary = 0;
		}
		$this->reloadSurveys();
		# posodobimo podatke prikazanih anket
		$this -> refreshData();	
	}
	

	/** Polovimo podatke anket in jih izrišemo 
	 * 
	 */ 
	public function getSurveys() {
		global $global_user_id;
		global $lang;
		
		// ali imamo star napreden vmesnik za moje ankete
		$advancedMySurveys = UserSetting::getInstance()->getUserSetting('advancedMySurveys');
		
		// Nastavitve zgoraj - nekatere niso prikazane pri searchu
		if($this->isSearch != 1){
			
			// Gumb za ustvarjanje ankete	        
			echo '<div id="anketa_new_float">';
			$this->new_anketa_div();
			echo '</div>';
				
			// Paginacija
			echo '<div id="pagination">';
			$this->displayPagiantion();
			echo '</div>';
		}		
		
		// Sort gumb - samo v novem vmesniku
		if($advancedMySurveys != 1 && $this->onlyPhone == false){
			echo '<div id="sortButton">';
			$this->displaySortButton();
			echo '</div>';
		}
		
		// Gumb za filtriranje - samo v novem vmesniku
		//if($this->g_adminType <= 1){
		if($advancedMySurveys != 1 && $this->onlyPhone == false){
			//echo '<div id="filterButton">';
			$this->displayFilterButton();
			//echo '</div>';
		}
		
		// Preklop na pogled s folderji (samo ce imamo nov vmesnik)
		if($this->isSearch != 1){
			if($advancedMySurveys != 1 && $this->onlyPhone == false){
				//echo '<div id="folderSwitch">';
				$this->displayFolderSwitch();
				//echo '</div>';
			}
		}
		
		// Nastavitve zgoraj - nekatere niso prikazane pri searchu
		if($this->isSearch != 1){	
			// Gumb z nastavitvami pogleda - za star vmesnik
			if ($this->onlyPhone == false && $advancedMySurveys == 1){
				$this->displaySettingsUrl();
			}
			
			// Okno za search po mojeih anketah
			echo '<div id="searchMySurveys">';
			$this->displaySearch();	
			echo '</div>';
		}
		
		// Izris seznama anket - star oz. nov design
		if($advancedMySurveys == 1){
			$this->displaySurveyList();
		}
		else{
			if($this->show_folders == 1 && $this->isSearch != 1){
				
				// Info box za posamezno anketo (hover)
				echo '<div id="survey_list_info"></div>';
				
				echo '<div class="clr"></div>';
			
				// Loop po vseh folderjih prvega nivoja - samo na prvi strani
				if($this->pageno == 1){
					// Plus za dodajanje folderja
					echo '<div style="margin: 15px 0 -15px 0"><a style="vertical-align:0px; line-height:18px;" href="#" title="'.$lang['srv_mySurvey_create_folder'].'" onClick="create_folder(\''.$folder['id'].'\'); return false;">';
					echo '<span style="vertical-align:middle;" class="faicon add icon-blue-hover-orange pointer"></span> <span style="vertical-align:middle;" class="bold">'.$lang['srv_mySurvey_create_folder'].'</span>';
					echo '</a></div>';
				
					$sql = sisplet_query("SELECT * FROM srv_mysurvey_folder WHERE usr_id='$global_user_id' AND parent='0' ORDER BY naslov ASC");
					if(mysqli_num_rows($sql) > 0) {
						while($row = mysqli_fetch_array($sql)){
							echo '<div id="folder_holder_'.$row['id'].'" class="folder_holder level1">';
							$this->displayNewFolder($row);
							echo '</div>';
						}
					}
				}
				
				// Na koncu se izpisemo ankete ki niso v nobenem folderju
				$row = array('id'=>0, 'naslov'=>$lang['srv_mySurvey_unallocated']);
				echo '<div id="folder_holder_0" class="folder_holder level1">';
				$this->displayNewFolder($row);
				echo '</div>';
				
				echo '<script type="text/javascript">surveyList_folder_init();</script>';
			}
			// Ce ne prikazujemo map je vse po starem
			else{
				// Info box za posamezno anketo (hover)
				echo '<div id="survey_list_info"></div>';
			
				echo '<div id="div_sl_new_-1" class="div_sl_new">';
				$this->displayNewSurveyList($folder=-1);
				echo '</div>';
			}
			
			// Paginacija - se na dnu - ni prikazana pri searchu
			if($this->isSearch != 1){
				echo '<div id="pagination" class="bottom">';
				$this->displayPagiantion();
				echo '</div><br /><br />';
			}
		}
	}
	
	function displaySurveyList() {
		global $lang;
		
		if ($this->onlyPhone == true) {
			$this->settingsArray = array();
			# prikažemo samo naslov
			$this->settingsArray['naslov'] = $this->dataFields[1];;
			echo '<input type="hidden" id="onlyPhone" name="onlyPhone" value="1">';
		}
		
		echo '<div id="div_sl">';
		if ( SRV_LIST_GET_AS_LIST == true) {
			echo '<br class="clr" />';			
			// labelo za aktivno, in knji?nico popravimo kar ro?no - nardimo slikice
			$lang['srv_h_sl_active_1'] = '<span class="faicon star_on" title="'.$lang['srv_anketa_active'].'"></span>';
			$lang['srv_h_sl_delete_1'] = '';
			$lang['srv_h_sl_lib_glb_1'] = '<span class="sprites library_admin_on" title="'.$lang['srv_ank_lib_on'].'"></span>';
			$lang['srv_h_sl_lib_usr_1'] = '<span class="sprites library_on" title="'.$lang['srv_ank_mylib_on'].'"></span>';

			$ankete = $this->getSurveysAsList();
			
			echo '<input type="hidden" id="sortby" value="'.(isset($_POST['sortby']) ? $_POST['sortby'] : null).'">';
			echo '<input type="hidden" id="sorttype" value="'.(isset($_POST['sorttype']) ? $_POST['sorttype'] : null).'">';
			
			echo '<ul id="surveyList" >';		
			// izpi?emo header celice
			echo '<li class="sl_header">';
			$grupaName = "";
			$groupWidths = array();

			if (count($this->settingsArray) > 0 ){
				foreach ( $this->settingsArray as $opcija ) {
					if ($opcija['visible'] == 1) {
						// preverimo ali smo zaklju?ili prej?njo grupo
						if ((!isset($opcija['header_grupa']) && $grupaName != "") || // nismo ve? v grupi stara ?e obstaja  
							( isset($opcija['header_grupa']) && $grupaName != "" && $opcija['header_grupa'] != $grupaName)) { // smo v grupi ampak ime ni enako prej?njemu
							echo '<div class="clr"></div>';
							echo '</div>';
							echo '</div>';					
							$grupaName = "";		
						}
						// preverimo ali naredimo novo grupo (Vnesel / urejal) 
						if (isset($opcija['header_grupa']) && $grupaName == "") { // smo v grupi polj pod in imamo podpolja: ime priimek, email, datum
							// imamo za?etek grupe nari?emo ?tartne dive
							echo '<div id="'.$opcija['header_grupa'].'_holder" class="floatLeft" style="padding:0px; margin:0px; border:none; height:100%;">';
							echo '<div id="'.$opcija['header_grupa'].'_title" style="height:26px; /*border-bottom: 1px solid #C2D2C9;*/ padding:0px; margin:0px; /*border-right:1px solid #C2D2C9;*/">';
							echo '<div id="'.$opcija['header_grupa'].'" class="floatLeft anl_ac" style="border:none;">'.$lang['srv_'.$opcija['header_grupa']].'</div>';
							// nastavimo sirino grupe na 0
							$groupWidths[$opcija['header_grupa']] = 0;
							echo '<div class="clr"></div>';
							echo '</div>';
							echo '<div style="height:26px;padding:0px; margin:0px; border:none;">';
							$grupaName = $opcija['header_grupa'];
						}
						echo '<div id="h_'.$opcija['header_field'].'" class="floatLeft sl_header_field '.$opcija['header_field'].' anl_ac" baseCss="'.$opcija['header_field'].'">' 
							. $this->createOrderUrl( $opcija['id'], (isset($opcija['lang_label']) ? $lang['srv_h_'.$opcija['lang_label']] : $lang['srv_h_'.$opcija['header_field']] ) )  
							. '</div>';
					}
					flush(); @ob_flush();
				}
			}
			// na koncu ?e preverimo ali imamo kon?ano grupo ?ene nardimo zaklju?na diva
			if ($grupaName != "") {
				echo '<div class="clr"></div>';
				echo '</div>';
				echo '</div>';					
				$grupaName = "";		
			}
       		echo '</li>';
			$cnt = 1;
			if (count($ankete)) {
				foreach ( $ankete as $anketa ) {
					$eavenOdd = $cnt&1;
		        	$cnt++;
		        	$anketa_answers_cnt = $anketa['answers'];
		        	$anketa_is_active = $anketa['active'];
					$anketa_is_copy = isset($anketa['insert_uid']) && $anketa['insert_uid'] == -1 ? true : false;
					$anketa_i_uid= isset($anketa['insert_uid']) ? $anketa['insert_uid'] : null;
					$anketa_e_uid= isset($anketa['edit_uid']) ? $anketa['edit_uid'] : null;
					$anketa_canEdit= $anketa['canEdit'];
		        	echo '<li id="anketa_list_'.$anketa['id'].'" class="sl_bck_'.$eavenOdd.'">';
					// za vsako vidno polje za header izpi?emopodatek 
					foreach ( $this->settingsArray as $opcija ) {
	
						if (isset($opcija['visible']) && $opcija['visible'] == 1) {
							echo '<div class="floatLeft '.$opcija['header_field'].' '.(isset($opcija['data_css']) ? $opcija['data_css'] : null).' sl_bck_br_'.$eavenOdd.'" title="'.$anketa[$opcija['data_field']].'" >';
							if (isset($opcija['data_type'])) {
								$this->echoText($anketa[$opcija['data_field']], $opcija['data_type'], $anketa['id'], array('answers_cnt'=>$anketa_answers_cnt, 'is_active'=>$anketa_is_active, 'anketa_is_copy'=>$anketa_is_copy, 'anketa_i_uid'=>$anketa_i_uid, 'anketa_e_uid'=>$anketa_e_uid,'anketa_canEdit'=>$anketa_canEdit));	
							} else {
								$this->echoText($anketa[$opcija['data_field']], 'text', $anketa['id'], array('answers_cnt'=>$anketa_answers_cnt, 'is_active'=>$anketa_is_active, 'anketa_is_copy'=>$anketa_is_copy, 'anketa_i_uid'=>$anketa_i_uid, 'anketa_e_uid'=>$anketa_e_uid,'anketa_canEdit'=>$anketa_canEdit));
							}
				       		echo '</div>';
						}
					}				
		       		echo '</li>';
				}
			}
			echo '</ul>';
			// izpi?emo javascript za resizable
			echo '<script type="text/javascript">';
			echo '$(document).ready(function() {';
			// echo '$().ready(function() {';
			foreach ( $this->settingsArray as $opcija ) {
				if ($opcija['visible'] == 1) {
					echo '$("#h_'.$opcija['header_field'].'").resizable({handles:"e", alsoResize: "'.$opcija['alsoResize'].'"'.
						(isset($opcija['minWidth'])?', minWidth: '.$opcija['minWidth']:'').(isset($opcija['maxWidth'])?', maxWidth: '.$opcija['maxWidth']:'').
						', stop: function(event, ui) { save_surveyListCssSettings(event, ui); }'.'});';
				}	
			}

			// ponastavimo ?irine celic z JS
			$css_data = $this->getCssSetings();
			if (isset($css_data) && count($css_data)>0) {
				foreach ($css_data as $css_key => $css) {
					echo '$(".'.$css_key.'").width('.$css.');';	
				}
			}	
			
			// prika?emo seznam
			echo '$("#surveyList").show();';
			// header title priredimo sirino

			foreach ( $groupWidths as $grupa => $widths ) {
				//echo '$("#'.$grupa.'").css( { "width": ($("#'.$grupa.'_holder").width()-5)+"px"});';	
				// chrome bux fix --mitja
				echo '$("#'.$grupa.'").css( { "width": "100%"});';	
			}
			echo '});';
			echo '</script>';
		}
		
		echo '</div>'; //  id="div_sl"
				
	}

	
	/** Kreacija nove ankete
     */
    function new_anketa_div(){
        global $lang;
        global $admin_type;
        global $site_url;

        echo '<span id="buttonCreate" class="floatLeft"><a href="' . $site_url . 'admin/survey/index.php?a=ustvari_anketo" title="' . $lang['srv_create_survey'] . '">';
        echo $lang['srv_create_survey'];
        echo '</a></span>';

        // uporabniki z vklopljeno moznostjo imajo tudi hitro ustvarjanje ankete (brez vnosa imena in izbire skina)
        $oneclickCreateMySurveys = UserSetting::getInstance()->getUserSetting('oneclickCreateMySurveys');
        if ($oneclickCreateMySurveys == 1) {
            echo '<span class="floatLeft" style="margin:10px 0 0 20px; font-weight:600;"><a style="vertical-align:0px; line-height:18px;" href="#" onclick="newAnketaBlank();" title="' . $lang['one_click_create'] . '"><span style="vertical-align:top;" class="faicon add icon-blue-hover-orange"></span> <span>'.$lang['one_click_create'].'</span></a>';
        }
    }
	
	function displayNewSurveyList($folder=0) {
		global $lang;
		global $site_url;
		global $global_user_id;
				
		if (SRV_LIST_GET_AS_LIST == true) {
		
			$ankete = $this->getSurveysAsListNew($folder);
			
			// Ce searchamo izrisemo napredne nastavitve za search
			if($this->isSearch == 1){
				echo '<div id="searchSettings">';
				$this->displaySearchSettings();
				echo '</div>';
				
				echo '<div class="clr"></div>';
			}
			
			echo '<input type="hidden" id="sortby" value="'.(isset($_POST['sortby']) ? $_POST['sortby'] : null).'">';
			echo '<input type="hidden" id="sorttype" value="'.(isset($_POST['sorttype']) ? $_POST['sorttype'] : null).'">';
			
			if ($count = count($ankete)) {
			
				// Naslov za search
				if($this->isSearch == 1){
					if($count == 1)
						$hits = $lang['s_hits_1'];
					elseif($count == 2)
						$hits = $lang['s_hits_2'];
					elseif($count == 3 || $count == 4)
						$hits = $lang['s_hits_34'];
					else
						$hits = $lang['s_hits'];
					
					// Ce imamo vec kot 1000 zadetkov izpisemo samo 1000 in opozorilo
					if($count < 1000)
						$cnt_text = '<span class="italic">('.$count.' '.$hits.')</span>';
					else
						$cnt_text = '<span class="italic">('.$lang['s_hits_1000'].')</span>';
					
					echo '<span class="search_title">'.$lang['s_search_mySurvey_title'].' '.$cnt_text.':</span>';
				}
			
				echo '<table id="surveyList_new">';
				
				
				// HEADER VRSTICA
				echo '<tr class="sl_header_new">';
				
				// Info ikona
				echo '<td class="col1">';
				echo '</td>';
				
				// Tip ankete (forma, navadna, glasovanje)
				//echo '<td class="col2"></td>';
				// Aktivnost ankete
				echo '<td class="col2"></td>';
				
				// Naslov
				echo '<td class="col3">';
				echo $this->createOrderUrlNew(1, $lang['title']);		
				echo '</td>';
				
				// Trajanje - status
				echo '<td class="col4">';
				echo $this->createOrderUrlNew(18, 'Status');
				echo '</td>';
				
				// Tip + st. vprasanj
				echo '<td class="col5">';
				echo '';
				echo $this->createOrderUrlNew(19, $lang['srv_tip']);
				echo '</td>';
				
				// Zadnji vnos
				echo '<td class="col6">';
				echo $this->createOrderUrlNew(16, $lang['srv_last_insrt']);
				echo '</td>';
				
				// Sprememba
				echo '<td class="col7">';
				echo $this->createOrderUrlNew(14, $lang['change']);
				echo '</td>';
				
				// Ikona1
				echo '<td class="col8"></td>';
				
				// Ikona2
				echo '<td class="col9"></td>';
						
				echo '</tr>';
			
			
				// VRSTICE Z ANKETAMI
				foreach ( $ankete as $anketa ) {
					
					echo '<tr id="anketa_list_'.$anketa['id'].'" class="anketa_list '.($this->show_folders == 1 ? ' mySurvey_draggable' : '').'" anketa_id="'.$anketa['id'].'">';
					
					// Info ikona
					echo '<td class="col1">';
					//var_dump($anketa);
					echo '<span id="info_icon_'.$anketa['id'].'" anketa="'.$anketa['id'].'" class="faicon info icon-as_link pointer icon-center" title="'.$lang['srv_survey_info'].'" onClick="surveyList_info(\''.$anketa['id'].'\');"></span>';
					echo '</td>';
					
					// Aktivnost - neaktivna, aktivna, zakljucena
					echo '<td class="col2">';
					if ($anketa['active'] == 1) {
						echo '<div class="dot blue" title="'.$lang['srv_anketa_active2'].'"></div>';
					} else {
						$sqlA = sisplet_query("SELECT sid FROM srv_activity WHERE sid='".$anketa['id']."'");
						if (mysqli_num_rows($sqlA) > 0) {
							# anketa je zaključena
							echo '<div class="dot grey" title="'.$lang['srv_survey_non_active'].'"></div>';
						} else {
							# anketa je neaktivna
							echo '<div class="dot grey" title="'.$lang['srv_survey_non_active_notActivated'].'"></div>';
						}
					}
					echo '</td>';
					
					// Naslov
					echo '<td class="col3">';
					if(strlen($anketa['naslov']) > 60)
						$text = substr($anketa['naslov'],0,60);
					else
						$text = $anketa['naslov'];
					
					// Ce gre za search moramo ustrezno pobarvati najden del besede
					$text_searched = $text;
					if($this->isSearch == 1 && $this->searchSettings['stype'] == '0'){
						foreach($this->searchStringProcessed as $search_word){
							
							// Pobarvamo najden niz v naslovu ankete
							preg_match_all("/$search_word+/i", $text_searched, $matches);
						    if (is_array($matches[0]) && count($matches[0]) >= 1) {
						        foreach ($matches[0] as $match) {
						            $text_searched = str_replace($match, '<span class="red">'.$match.'</span>', $text_searched);
						        }
						    }
						}					
					}

					echo '<a href="'.$site_url.'admin/survey/index.php?anketa='.$anketa['id'].'&a='.A_REDIRECTLINK.'" title="'.$text.'">';
					echo '<span class="title">'.$text_searched.'</span>';
					echo '</a>';
					
					$i_time = substr($anketa['i_time'], 0, 8);
					$i_time = explode('.', $i_time);
					echo '<br /><span class="small">';
					if($anketa['mobile_created'] == '1')
						echo '<span class="red spaceRight pointer" title="'.$lang['srv_mobile_survey'].'">M</span>';
					// Ce gre za search moramo ustrezno pobarvati najden del besede
					$name_searched = $anketa['i_name'];
					$surname_searched = $anketa['i_surname'];
					if($this->isSearch == 1 && $this->searchSettings['stype'] == '1'){						
						foreach($this->searchStringProcessed as $search_word){
		
							// Pobarvamo najden niz v imenu
							preg_match_all("/$search_word+/i", $name_searched, $matches);
						    if (is_array($matches[0]) && count($matches[0]) >= 1) {
						        foreach ($matches[0] as $match) {
						            $name_searched = str_replace($match, '<span class="red">'.$match.'</span>', $name_searched);
						        }
						    }
							
							// Pobarvamo najden niz v priimku
							preg_match_all("/$search_word+/i", $surname_searched, $matches);
						    if (is_array($matches[0]) && count($matches[0]) >= 1) {
						        foreach ($matches[0] as $match) {
						            $surname_searched = str_replace($match, '<span class="red">'.$match.'</span>', $surname_searched);
						        }
						    }
						}					
					}
					echo $lang['srv_h_sl_avtor'].': '.$name_searched.' '.$surname_searched.', '.$i_time[0].'.'.$i_time[1].'.20'.$i_time[2].'</span>';
					echo '</td>';
					
					// Trajanje - status
					echo '<td class="col4">';
					if ($anketa['active'] == 1) {
						$now = time();
						$do = explode('.', $anketa['trajanjedo']);
						$do = strtotime($do[0].'.'.$do[1].'.20'.$do[2]);
						$trajanje = $do - $now;
						$trajanje = floor($trajanje/60/60/24) + 1;
						if($trajanje >= 0){
							// Ce je aktivna za vec kot 2000 dni je trajna
							if($trajanje > 2000)
								echo $lang['srv_trajna_anketa'];
							else
								echo $lang['more'].' '.$trajanje.' '.$lang['hour_days'];
						}
						else{
							echo $lang['srv_trajna_anketa'];
						}
					} 
					else {
						$sqlA = sisplet_query("SELECT sid FROM srv_activity WHERE sid='".$anketa['id']."'");
						if (mysqli_num_rows($sqlA) > 0) {
							# anketa je zaključena
							echo $lang['srv_survey_list_closed'];
						} else {
							# anketa je neaktivna
							echo $lang['srv_survey_list_inpreparation'];
						}	
					}
					echo '</td>';
					
					// Tip + st. vprasanj
					echo '<td class="col5">';
					if($anketa['survey_type'] == 0)
						echo $lang['srv_vrsta_survey_type_0'];
					elseif($anketa['survey_type'] == 1)
						echo $lang['srv_vrsta_survey_type_1'];
					elseif(SurveyInfo::checkSurveyModule('hierarhija', $anketa['id']))
                        echo $lang['srv_vrsta_survey_type_10'];
                    else
						echo $lang['srv_vrsta_survey_type_2'];
						
					echo '<br /><span class="small">'.$lang['srv_h_sl_stvprasanj'].': '. $anketa['variables'].'</span>';											
					echo '</td>';
					
					// Zadnji vnos
					echo '<td class="col6">';
					if($anketa['answers'] > 0){
						$v_time = substr($anketa['v_time_last'], 0, 8);
						$v_time = explode('.', $v_time);
						echo $v_time[0].'.'.$v_time[1].'.20'.$v_time[2].'<br />';
					}
					else{
						echo '/<br />';
					}
					echo '<span class="small">'.$lang['srv_info_answers'].': '.$anketa['answers'].'</span>';
					echo '</td>';
					
					// Sprememba
					echo '<td class="col7">';
					$e_time = substr($anketa['e_time'], 0, 8);
					$e_time = explode('.', $e_time);
					echo $e_time[0].'.'.$e_time[1].'.20'.$e_time[2].'<br />';
					
					echo '<a href="#"  onclick="surveyList_user(\'e\',\'#edit_user_'.$anketa['id'].'\');">';
					echo '<span id="edit_user_'.$anketa['id'].'" euid="'.$anketa['edit_uid'].'" class="email">'.$anketa['e_email'].'</span>';
					echo '</a>';
					echo '</td>';
					
					// Knjiznica - uporabnik
					echo '<td class="col8">';
					echo '<a href="#" title="'.($anketa['lib_usr'] == 1 ? $lang['srv_ank_mylib_off'] : $lang['srv_ank_mylib_on']).'" onclick="surveyList_myknjiznica_new(\''.$anketa['id'].'\'); return false;">';
					echo '<span class="sprites '.($anketa['lib_usr'] == 1 ? ' sl_active_on': ' sl_active_off').'"></span>';
					echo '</a>';
					echo '</td>';
					
					// Knjiznica - global (samo admin)
					echo '<td class="col9">';
					if ($this->g_adminType == 0){			
						echo '<a href="#" title="'.($anketa['lib_glb'] == 1 ? $lang['srv_ank_lib_off'] : $lang['srv_ank_lib_on']).'" onclick="surveyList_knjiznica_new(\''.$anketa['id'].'\'); return false;">';
						echo '<span class="sprites '.($anketa['lib_glb'] == 1 ? ' sl_library_on': ' sl_library_off').'"></span>';
						echo '</a>';
					}
					echo '</td>';
	
		       		echo '</tr>';
				}
			
				echo '</table>';	
			}
			else{
				// Naslov za prazen search
				if($this->isSearch == 1)
					echo '<span class="search_title">'.$lang['s_search_mySurvey_nothing'].'.</span>';
			}
		}				
	}
	
	function displayNewFolder($folder){
		global $lang;
		global $global_user_id;
		global $admin_type;
		
		// Izris nerazvrscenih anket
		if($folder['id'] == 0){				
			echo '<div class="folder_title droppable" id="folder_0" folder_id="0">';
			
			echo '<a href="#" onClick="toggle_folder(\''.$folder['id'].'\'); return false;"><span class="faicon minus icon-blue pointer"></span></a>';
				
			echo ' <span class="spaceRight" style="vertical-align:0px;">'.$folder['naslov'].'</span>';
			
			// Ikona za dodajanje folderja
			//echo ' <a href="#" title="'.$lang['srv_mySurvey_create_folder'].'" onClick="create_folder(\''.$folder['id'].'\'); return false;"><span class="sprites add_blue pointer" style="height: 16px;"></span></a>';
			echo '</div>';
			
			
			echo '<div id="folder_content_0" class="folder_content '.(/*$this->pageno == 1*/false ? ' closed' : '').'">';
			$this->displayNewSurveyList($folder=0);
			echo '</div>';
		}
		else{
			echo '<div class="folder_title mySurvey_draggable droppable" id="folder_'.$folder['id'].'" folder_id="'.$folder['id'].'">';
			if($folder['open'] == 1)
				echo '<a href="#" onClick="toggle_folder(\''.$folder['id'].'\'); return false;"><span class="faicon minus icon-blue pointer"></span></a>';
			else
				echo '<a href="#" onClick="toggle_folder(\''.$folder['id'].'\'); return false;"><span class="faicon plus icon-blue pointer"></span></a>';
			
			// Naslov folderja
			echo ' <span class="faicon folder icon-blue"></span> <span id="folder_title_text_'.$folder['id'].'" class="folder_title_text spaceRight" style="vertical-align:0px;"><a href="#" onClick="edit_title_folder(\''.$folder['id'].'\'); return false;">'.$folder['naslov'].'</a></span>';
			
			// Ikona za dodajanje folderja
			echo ' <a href="#" title="'.$lang['srv_mySurvey_create_folder'].'" onClick="create_folder(\''.$folder['id'].'\'); return false;"><span class="faicon add icon-blue pointer map_holder_control"></span></a>';
			
			// Ikona za brisanje folderja
			echo ' <a href="#" title="'.$lang['srv_mySurvey_delete_folder'].'" onClick="delete_folder(\''.$folder['id'].'\'); return false;"><span class="faicon remove icon-orange pointer map_holder_control"></span></a>';
			
			// Ikona za kopiranje folderja - samo admini
			if($admin_type == 0)
				echo ' &nbsp;<a href="#" title="'.$lang['srv_mySurvey_copy_folder'].'" onClick="copy_folder(\''.$folder['id'].'\'); return false;"><span class="faicon copy icon-blue_soft pointer map_holder_control"></span></a>';
			
			echo '</div>';
			
			
			echo '<div id="folder_content_'.$folder['id'].'" class="folder_content '.($folder['open'] == 1 ? '' : ' closed').' '.($folder['parent'] != 0 ? ' subfolder' : '').'">';
		
			$this->displayNewSurveyList($folder['id']);
			
			// Izpisemo se vse folderje znotraj trenutnega folderja
			$sql = sisplet_query("SELECT * FROM srv_mysurvey_folder WHERE usr_id='$global_user_id' AND parent='".$folder['id']."' ORDER BY naslov ASC");
			if(mysqli_num_rows($sql) > 0) {								
				while($row = mysqli_fetch_array($sql)){
					echo '<div id="folder_holder_'.$row['id'].'" class="folder_holder">';
					$this->displayNewFolder($row);
					echo '</div>';
				}
			}	
			
			echo '</div>';
		}
	}
	
			
	/** Prikažemo navigacijo po straneh rezultatov
	 * 
	 */
	function displayPagiantion() {
		global $site_url, $lang;
		
		//$this->max_pages = 100;
		
		# ali sploh izrisujemo paginacijo
		if ($this->max_pages > 1) {
			if ($this->onlyPhone == false) {
				$baseUrl = $site_url."admin/survey/index.php?pageno=";
			} else {
				$baseUrl = $site_url."admin/survey/index.php?a=phoneSurveys&pageno=";
			}

			# povezava na prejšnjo stran
			$prev_page = $this->pageno - 1;
			if($prev_page >= 1) { 
	  			//echo('<div><a href="'.$baseUrl.$prev_page.'">'.$lang['previous_page_short'].'</a></div>');
	  			echo('<div><a href="'.$baseUrl.$prev_page.'"><span class="faicon pagination_left icon-blue"></span></a></div>');
			} else {
				# brez href povezave 
	  			//echo('<div class="disabledPage">'.$lang['previous_page_short'].'</div>'); 
	  			echo('<div class="disabledPage"><span class="faicon pagination_left icon-blue_soft"></span></div>'); 
			}	
			
			# povezave  za vmesne strani
			$middle = $this->max_pages / 2;
			$skipped  = false;
			for($a = 1; $a <= $this->max_pages; $a++) {
				if ($this->max_pages < ((SRV_LIST_GROUP_PAGINATE+1) * 2) || $a <= SRV_LIST_GROUP_PAGINATE || $a > ($this->max_pages-SRV_LIST_GROUP_PAGINATE) 
					
					|| ( abs($a-$this->pageno) < SRV_LIST_GROUP_PAGINATE))  {
					if ($skipped == true) {
						echo '<div class="spacePage">.&nbsp;.&nbsp;.</div>';
						$skipped  = false;
					}
					if($a == $this->pageno) {
		   				# brez href povezave
		      			echo('<div class="currentPage">'.$a.'</div>'); 
			 		} else {
		  				echo('<div><a href="'.$baseUrl.$a.'">'.$a.'</a></div>');
		     		}
				} else {
					$skipped = true;
				}
			} 
			# povezava na naslednjo stran
			$next_page = $this->pageno + 1;
			if($next_page <= $this->max_pages) {
	   			//echo('<div><a href="'.$baseUrl.$next_page.'">'.$lang['next_page_short'].'</a></div>');
				echo('<div><a href="'.$baseUrl.$next_page.'"><span class="faicon pagination_right icon-blue"></span></a></div>');			
			} else {
				# brez href povezave
	   			//echo('<div class="disabledPage">'.$lang['next_page_short'].'</div>'); 
				echo('<div class="disabledPage"><span class="faicon pagination_right icon-blue_soft"></span></div>'); 
			}
 
		}
	}
	
	// Prikazemo gumb za sortiranje seznama anket
	private function displaySortButton(){
		global $lang, $site_url;
		
		echo $lang['orderby'];
		//echo ' <img style="margin-left:5px; vertical-align:middle;" src="'.$site_url.'admin/survey/img_new/bullet_arrow_down.png">';
		
		
		echo '<div id="sortSettings">';
		
		echo '<ul>';
		
		if($this->sorttype == 2){
			$sort = 1;
			$img_src = 'sort_ascending';
		} 
		else{
			$sort = 2;
			$img_src = 'sort_descending';
		}
		
		echo '<a href="#" onClick="surveyList_goTo(\'1\',\''.($this->sortby != 1 ? '1' : $sort).'\')"><li '.($this->sortby == 1 ? ' class="active"' : '').'>'.$lang['sort_by_title'].' <span class="faicon '.($this->sortby != 1 ? 'sort_unsorted' : $img_src).'"></span></li></a>';
		echo '<a href="#" onClick="surveyList_goTo(\'6\',\''.($this->sortby != 6 ? '1' : $sort).'\')"><li '.($this->sortby == 6 ? ' class="active"' : '').'>'.$lang['sort_by_qcount'].' <span class="faicon '.($this->sortby != 6 ? 'sort_unsorted' : $img_src).'"></span></li></a>';
		echo '<a href="#" onClick="surveyList_goTo(\'5\',\''.($this->sortby != 5 ? '1' : $sort).'\')"><li '.($this->sortby == 5 ? ' class="active"' : '').'>'.$lang['sort_by_answercount'].' <span class="faicon '.($this->sortby != 5 ? 'sort_unsorted' : $img_src).'"></span></li></a>';
		echo '<a href="#" onClick="surveyList_goTo(\'16\',\''.($this->sortby != 16 ? '1' : $sort).'\')"><li '.($this->sortby == 16 ? ' class="active"' : '').'>'.$lang['sort_by_insert'].' <span class="faicon '.($this->sortby != 16 ? 'sort_unsorted' : $img_src).'"></span></li></a>';
		echo '<a href="#" onClick="surveyList_goTo(\'14\',\''.($this->sortby != 14 ? '1' : $sort).'\')"><li '.($this->sortby == 14 ? ' class="active"' : '').'>'.$lang['sort_by_edit'].' <span class="faicon '.($this->sortby != 14 ? 'sort_unsorted' : $img_src).'"></span></li></a>';
		echo '<a href="#" onClick="surveyList_goTo(\'18\',\''.($this->sortby != 18 ? '1' : $sort).'\')"><li '.($this->sortby == 18 ? ' class="active"' : '').'>Status <span class="faicon '.($this->sortby != 18 ? 'sort_unsorted' : $img_src).'"></span></li></a>';
		echo '<a href="#" onClick="surveyList_goTo(\'7\',\''.($this->sortby != 7 ? '1' : $sort).'\')"><li '.($this->sortby == 7 ? ' class="active"' : '').'>'.$lang['sort_by_author'].' <span class="faicon '.($this->sortby != 7 ? 'sort_unsorted' : $img_src).'"></span></li></a>';
		echo '<a href="#" onClick="surveyList_goTo(\'11\',\''.($this->sortby != 11 ? '1' : $sort).'\')"><li '.($this->sortby == 11 ? ' class="active"' : '').' style="border:0;">'.$lang['sort_by_editor'].' <span class="faicon '.($this->sortby != 11 ? 'sort_unsorted' : $img_src).'"></span></li></a>';
		
		echo '</ul>';
		
		echo '</div>';
	}
	
	// Prikazemo gumb za filtriranje seznama anket
	private function displayFilterButton(){
		global $lang, $site_url, $admin_languages;
		
		echo '<div id="filterButton" '.(($this->user_id || $this->lang_id != 0 || $this->gdpr != 0) ? 'class="active"' : '').'>';
		
		echo $lang['srv_analiza_filter'];
		//echo ' <img style="margin-left:5px; vertical-align:middle;" src="'.$site_url.'admin/survey/img_new/bullet_arrow_down.png">';
		
		
		echo '<div id="filterSettings">';		
		echo '<ul>';
        
        
		# filter po uporabniku
		echo '<li>';
		
		echo '<span class="filter_title">'.$lang['srv_list_author'].'</span><br />';
		
		// Ce preklapljamo v searchu moramo refreshati celo stran (druga js funkcija)
		$reload = ($this->isSearch == 1) ? '_reload' : '';
		
		echo '<input type="radio" name="filter_mySurveys" id="filter_mySurveys_0" value="0" '.(!$this->user_id ? 'checked="checked"' : '').' onclick="surveyList_user'.$reload.'(\'clr\',\'0\');"> <label for="filter_mySurveys_0">'.$lang['srv_list_all_surveys'].'</label>';
		echo '<br /><input type="radio" name="filter_mySurveys" id="filter_mySurveys_1" value="1" '.($this->user_id ? 'checked="checked"' : '').' onclick="surveyList_user'.$reload.'(\'uid\',\''.$this->g_uid.'\');"> <label for="filter_mySurveys_1">'.$lang['srv_list_my_surveys'].'</label>';
				
		echo '</li>';
        
        
		# filter po jeziku
		echo '<li>';	
		
		echo '<span class="filter_title">'.$lang['srv_sl_set_language'].'</span><br />';
		
		echo '<input type="radio" name="filter_language" id="filter_language_0" value="0" '.((int)$this->lang_id == 0 ? 'checked="checked"' : '').' onclick="surveyList_language'.$reload.'(\'0\');"> <label for="filter_language_0">'.$lang['srv_sl_set_language_all'].'</label>';
		echo '<br /><input type="radio" name="filter_language" id="filter_language_1" value="1" '.((int)$this->lang_id == 1 ? 'checked="checked"' : '').' onclick="surveyList_language'.$reload.'(\'1\');"> <label for="filter_language_1">'.$admin_languages['1'].'</label>';
		echo '<br /><input type="radio" name="filter_language" id="filter_language_2" value="2" '.((int)$this->lang_id == 2 ? 'checked="checked"' : '').' onclick="surveyList_language'.$reload.'(\'2\');"> <label for="filter_language_2">'.$admin_languages['2'].'</label>';
		
        echo '</li>';	


        # filter po GDPR anketah
		echo '<li>';	
		
		echo '<span class="filter_title">'.$lang['srv_gdpr'].'</span><br />';
		
		echo '<input type="radio" name="filter_gdpr" id="filter_gdpr_0" value="0" '.((int)$this->gdpr == 0 ? 'checked="checked"' : '').' onclick="surveyList_gdpr'.$reload.'(\'0\');"> <label for="filter_gdpr_0">'.$lang['srv_list_all_surveys'].'</label>';
		echo '<br /><input type="radio" name="filter_gdpr" id="filter_gdpr_1" value="1" '.((int)$this->gdpr == 1 ? 'checked="checked"' : '').' onclick="surveyList_gdpr'.$reload.'(\'1\');"> <label for="filter_gdpr_1">'.$lang['srv_list_gdpr_gdpr'].'</label>';
		//echo '<br /><input type="radio" name="filter_gdpr" id="filter_gdpr_2" value="2" '.((int)$this->gdpr == 2 ? 'checked="checked"' : '').' onclick="surveyList_gdpr'.$reload.'(\'2\');"> <label for="filter_gdpr_2">'.$lang['srv_list_gdpr_no_gdpr'].'</label>';
		
        echo '</li>';	
        
		
		echo '</ul>';			
		echo '</div>';
		
		echo '</div>';
	}
	
	// Prikazemo preklop med navadnim pogledom in pogledom s folderji
	private function displayFolderSwitch(){			
        global $lang, $global_user_id;
        
        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
        $userAccess = UserAccess::getInstance($global_user_id);
        
        // Ce ni na voljo
        if(!$userAccess->checkUserAccess('my_survey_folders')){
            echo '<div title="'.$lang['srv_mySurvey_show_folders_desc'].'" id="folderSwitch" class="user_access_locked" onClick="popupUserAccess(\'my_survey_folders\');">';
            echo $lang['srv_mySurvey_show_folders'];
		    echo '</div>';
        }
        else{
            echo '<div title="'.$lang['srv_mySurvey_show_folders_desc'].'" id="folderSwitch" '.($this->show_folders == 1 ? ' class="active"' : '').' onClick="switchFolder(\''.$this->show_folders.'\');">';
            echo $lang['srv_mySurvey_show_folders'];
		    echo '</div>';
        }	
	}
	
	private function displaySettingsUrl() {
		global $lang;
				
		echo '<span class="sl_setting_link"><a href="#" onclick="show_surveyListSettings(); return false;"> '.$lang['settings'].'</a></span>';
		
		echo '<div id="survey_ListQickInfo" class="displayNone"></div>';
	}
	
	// Prikazemo nastavitve za napredno iskanje ce iscemo po anketah
	private function displaySearchSettings(){
		global $lang;
		global $site_url;

		// Posebej imamo skrito polje s parametri da jih ohranimo pri ajax klicih
		echo '<input type="hidden" id="searchParams" name="searchParams" value="'.$this->getSearchParams().'" />';	
		
		echo '<span class="title">'.$lang['s_search_settings_my'].'</span>';

		echo '<form method="GET" id="1kasf2" action="'.$site_url.'admin/survey/index.php">';

		// Iskano geslo
		echo '<p>';
        echo '	<span class="bold">'.$lang['s_search2'].':</span> <input type="text" name="search" id="searchMySurveyText" value="'.htmlentities($this->searchString).'" placeholder="' . $lang['s_search'] . '" />';
		echo '</p>';
		
		// Iskanje po naslovu ali avtorju ali besedilu
		echo '<p>';
		echo '	<span>'.$lang['s_thru'].': </span>';
		echo '	<label for="stype_0"><input type="radio" name="stype" id="stype_0" value="0" '.($this->searchSettings['stype'] == '0' ? ' checked="checked"' : '').' />'.$lang['s_title'].'</label>';
		echo '	<label for="stype_1"><input type="radio" name="stype" id="stype_1" value="1" '.($this->searchSettings['stype'] == '1' ? ' checked="checked"' : '').' />'.$lang['s_author'].'</label>';
		echo '	<label for="stype_2"><input type="radio" name="stype" id="stype_2" value="2" '.($this->searchSettings['stype'] == '2' ? ' checked="checked"' : '').' />'.$lang['s_text'].'</label>';
		echo '</p>';
		
		
		// NAPREDNE NASTAVITVE ISKANJA
		$show_advanced_search = false;
		if($this->searchSettings['sstatus'] != '0' 
			|| $this->searchSettings['sidatefrom'] != '' 
			|| $this->searchSettings['sidateto'] != '' 
			|| $this->searchSettings['sedatefrom'] != '' 
			|| $this->searchSettings['sedateto'] != ''){
		
			$show_advanced_search = true;
		}
		
		echo '<span class="advancedSearchButton clr bold spaceLeft">';
		echo '	<a href="#" onClick="showAdvancedSearch(); return false;">';
		echo '<span class="faicon '.($show_advanced_search ? ' minus': 'plus').'"></span> ' . $lang['s_advanced'];
		echo '	</a>';
		echo '</span>';
		
		echo '<div id="advancedSearch" '.($show_advanced_search ? '' : ' style="display:none;"').'>';		
		
		// Metaadmin lahko dodatno omeji search po avtorju
		if(Dostop::isMetaAdmin()){
			echo '<p>';
			echo '	<span class="bold">'.$lang['s_search_metaadmin'].':</span> <input type="text" name="onlyAuthor" id="onlyAuthor" value="'.htmlentities($this->searchSettings['onlyAuthor']).'" placeholder="' . $lang['s_search'] . '" />';
			echo '</p>';
		}
		
		// Status ankete (aktivna, neaktivna, zakljucena)
		echo '<p>';
		echo '	<span>'.$lang['s_activity'].': </span>';
		echo '	<label for="sstatus_0"><input type="radio" name="sstatus" id="sstatus_0" value="0" '.($this->searchSettings['sstatus'] == '0' ? ' checked="checked"' : '').' />'.$lang['s_all_surveys'].'</label>';
		echo '	<label for="sstatus_1"><input type="radio" name="sstatus" id="sstatus_1" value="1" '.($this->searchSettings['sstatus'] == '1' ? ' checked="checked"' : '').' />'.$lang['s_active_surveys'].'</label>';
		echo '	<label for="sstatus_2"><input type="radio" name="sstatus" id="sstatus_2" value="2" '.($this->searchSettings['sstatus'] == '2' ? ' checked="checked"' : '').' />'.$lang['s_nonactive_surveys'].'</label>';
		echo '</p>';
		
		// Datum ustvarjanja ankete
		echo '<p>';
		echo '	<span class="spaceRight">'.$lang['s_itime'].': </span>';
		echo '	<span class="spaceRight">'.$lang['s_from2'].' <input type="text" id="sidatefrom" name="sidatefrom" value="'.$this->searchSettings['sidatefrom'].'" autocomplete="off" size="12" /></span>';
		echo '	<span>'.$lang['s_to'].' <input type="text" id="sidateto" name="sidateto" value="'.$this->searchSettings['sidateto'].'" autocomplete="off" size="12" /></span>';
		echo '</p>';
		
		// Datum zadnjega urejanja ankete
		echo '<p>';
		echo '	<span class="spaceRight">'.$lang['s_etime'].': </span>';
		echo '	<span class="spaceRight">'.$lang['s_from2'].' <input type="text" id="sedatefrom" name="sedatefrom" value="'.$this->searchSettings['sedatefrom'].'" autocomplete="off" size="12" /></span>';
		echo '	<span>'.$lang['s_to'].' <input type="text" id="sedateto" name="sedateto" value="'.$this->searchSettings['sedateto'].'" autocomplete="off" size="12" /></span>';
		echo '</p>';
		
		echo '</div>';
		
		
		// Gumba isci in zapri
		echo '<span style="margin-top: 10px;" class="floatRight spaceRight">';
		echo '	<div class="buttonwrapper floatLeft spaceRight">';
		echo '		<a class="ovalbutton ovalbutton_gray" href="'.$site_url.'admin/survey/index.php"><span>'.$lang['s_search_mySurvey_back'].'</span></a>';
		echo '	</div>';
		echo '	<div class="buttonwrapper floatRight">';
		echo '		<a class="ovalbutton ovalbutton_orange" href="#" onclick="$(\'#1kasf2\').submit(); return false;"><span>'.$lang['s_search'].'</span></a>';
		echo '	</div>';
		echo '</span>';
		
		// Gumb nazaj na moje ankete
		/*echo '<span style="margin:5px 20px 0 5px;" class="floatRight bold">';
		echo '	<a href="'.$site_url.'admin/survey/index.php"><span>'.$lang['s_search_mySurvey_back'].'</span></a>';
		echo '</span>';*/
		
		// Link na isci po knjiznici
		echo '<span class="link"><a href="'.$site_url.'admin/survey/index.php?a=knjiznica&search='.$this->searchString.'">'.$lang['s_search_Library'].'</a></span>';
		
		echo '<input style="display: none;" value="Išči" type="submit">';
		
        echo '</form>';
		
				
		// JS za koledar
		echo '<script type="text/javascript">
				var srv_site_url = \''.$site_url.'\';
				$(document).ready(function () {
					$("#sidatefrom, #sidateto, #sedatefrom, #sedateto").datepicker({
						showOtherMonths: true,
						selectOtherMonths: true,
						changeMonth: true,
						changeYear: true,
						dateFormat: "dd.mm.yy",
						showAnim: "slideDown",
						showOn: "button",
						buttonText: ""
					});
				});
			</script>';			
	}
	
	// Prikazemo search okno za iskanje po anketah
	public function displaySearch(){
		global $lang;
		global $site_url;
		
		echo '<form method="GET" id="1kasmysurvey" action="'.$site_url.'admin/survey/index.php">';      
	    
		//echo '<span class="sprites search"></span> ';
        echo '<input id="searchMySurvey" type="text" value="" placeholder="' . $lang['s_search_mySurvey'] . '" name="search" />';
		
		//echo '<input type="submit" value="' . $lang['s_search'] . '" />'; 
		echo '	<div class="buttonwrapper floatRight">';
		echo '		<a class="ovalbutton ovalbutton_orange" href="#" onclick="$(\'#1kasmysurvey\').submit(); return false;"><span>'.$lang['s_search2'].'</span></a>';
		echo '	</div>';
          		
		echo '</form>';
	}
	
	
	/** polovimo nastavitve prikaza za posameznega uporabnika
	 * 
	 */
	private function getSettings($display_default = false) {
		
		$defaultOrder = $this->dataFields;
		$resultArray = array();
		$izBaze = false;

		if ($this->g_uid > 0) {
			$saved_surveyList_string = UserSetting::getInstance()->getUserSetting('survey_list_order');
			$saved_surveyList_visible_string = UserSetting::getInstance()->getUserSetting('survey_list_visible');
			$saved_surveyList_visible = array_unique(explode(",",$saved_surveyList_visible_string));
			if (isset($saved_surveyList_string) && $saved_surveyList_string != "" && !$display_default ) {
				$saved_surveyList_order = array_unique(explode(",",$saved_surveyList_string));
				$izBaze = true;
				// uporabimo shranjene nastavitve
				foreach ( $saved_surveyList_order as $order) {
						if (in_array($order,$saved_surveyList_visible))
       					{
	       					$defaultOrder[$order]['visible'] = 1; 
       					} else {
       						$defaultOrder[$order]['visible'] = 0;
       					}
       					if (isset($defaultOrder[$order]['order_by']) && isset($defaultOrder[$order]))
       					{
			       			$resultArray[$defaultOrder[$order]['order_by']] = $defaultOrder[$order];
			       			if (isset($defaultOrder[$order]))
			       			{
			       				unset($defaultOrder[$order]);
			       			}
       					}
				}
			} 
		}

		// za vse ostalo uporabimo privzete nastavitve
		foreach ( $defaultOrder as $order) 
		{
			if ($izBaze) 
			{
				$order['visible'] = 0; // ponastavimo vidnost ?e imamo iz baze
			}
			if (isset($order['order_by']))
			{
				$resultArray[$order['order_by']] = $order;
			}
		}
		return $resultArray;
	}
	
	private function getSurveysAsList() {

		$result = array();

		// ce imas hkrati dostop do ankete (srv_dostop) in preko managerskega dostopa (srv_dostop_manage) se brez DISTINCT podvajajo ankete
		$stringSurveyList = "SELECT DISTINCT sa.id, sa.folder, '1' as del, sa.naslov, sa.active, sa.edit_time, ";
		$stringSurveyList .= ( $this->settingsArray['lib_glb']['visible'] == 1 ) ? 'sal.lib_glb AS lib_glb, ' :'';
		$stringSurveyList .= ( $this->settingsArray['lib_usr']['visible'] == 1 ) ? 'sal.lib_usr AS lib_usr, ' : '';
		$stringSurveyList .= ( $this->settingsArray['e_name']['visible'] == 1 ||  $this->settingsArray['e_surname']['visible'] == 1 || $this->settingsArray['e_email']['visible'] == 1 )
									? "sa.edit_uid, sal.e_name AS e_name, sal.e_surname AS e_surname, sal.e_email AS e_email, " : '';
		$stringSurveyList .= ( $this->settingsArray['i_name']['visible'] == 1 ||  $this->settingsArray['i_surname']['visible'] == 1 || $this->settingsArray['i_email']['visible'] == 1 )
									? "sa.insert_uid, sal.i_name AS i_name, sal.i_surname AS i_surname, sal.i_email AS i_email, " : '';
		$stringSurveyList .= ( $this->settingsArray['edit_time']['visible'] == 1 ) ? "date_format(edit_time, '%d.%m.%y %k:%i') AS e_time, " : '';
		$stringSurveyList .= ( $this->settingsArray['insert_time']['visible'] == 1 ) ? "date_format(insert_time, '%d.%m.%y %k:%i') AS i_time, " : '';
		$stringSurveyList .= ( $this->settingsArray['vnos_time_first']['visible'] == 1 || $this->settingsArray['vnos_time_last']['visible'] == 1) 
								? "date_format(sal.a_first, '%d.%m.%y %k:%i') AS v_time_first, date_format(sal.a_last, '%d.%m.%y %k:%i') AS v_time_last, " : '';

		$stringSurveyList .= "sal.answers as answers, "; // vedno prestejemo odgovore
		$stringSurveyList .= ( $this->settingsArray['variables']['visible'] == 1 ) ? "sal.variables as variables, " : '';
		$stringSurveyList .= ( $this->settingsArray['trajanjeod']['visible'] == 1 ) ? "date_format(sa.starts, '%d.%m.%y') as trajanjeod, " : '';
		$stringSurveyList .= ( $this->settingsArray['trajanjedo']['visible'] == 1 ) ? "date_format(sa.expire, '%d.%m.%y') as trajanjedo, " : '';
		$stringSurveyList .= ( $this->settingsArray['approp']['visible'] == 1 ) ? "approp, " : '';
		$stringSurveyList .= "sd.canEdit, sa.survey_type "; // tega pustim tukaj, da ni problemov z vejico
		
		$stringSurveyList .= "FROM srv_anketa sa ";
		$stringSurveyList .= "LEFT OUTER JOIN srv_survey_list AS sal ON sal.id = sa.id " ;
		
		# kdo lahko ureja anketo (briše)
		// tega substringy se ne da dodatno razbit z prepareSubquery, ker selectamo 2 elementa...
		$stringSurveyList .= "LEFT OUTER JOIN (SELECT 1 AS canEdit, ank_id FROM srv_dostop WHERE FIND_IN_SET('edit', dostop ) ='1' AND aktiven = '1' AND uid = '$this->g_uid' OR uid IN (".SurveyCopy::prepareSubquery(sisplet_query("SELECT user FROM srv_dostop_manage WHERE manager = '".$this->g_uid."'")).")) AS sd ON sd.ank_id = sa.id " ;
		#$stringSurveyList .= "LEFT OUTER JOIN (SELECT ank_id, FIND_IN_SET('edit', dostop ) AS canEdit FROM srv_dostop WHERE aktiven = '1' AND uid = '$this->g_uid') AS sd ON sd.ank_id = sa.id " ;

		$stringSurveyList .= "WHERE sa.backup='0' AND sa.id>0 AND active >= '0' AND invisible = '0' ";
		//$stringSurveyList .= $this->getFolderCondition();
		
		$stringSurveyList .= $this->getLanguageLimit();
		$stringSurveyList .= $this->getDostopAnketa();
		$stringSurveyList .= $this->getOrderString();
		$stringSurveyList .= $this->getLimitString();
		
		$sqlSurveyList = sisplet_query($stringSurveyList);
		if (!$sqlSurveyList) {
			print_r("ERROR in query:");
			print_r($stringSurveyList);
			echo mysqli_error($GLOBALS['connect_db']);
		}
		$ids = array();
		while ($rowSurveyList = mysqli_fetch_assoc($sqlSurveyList)) {
			$result[] = $rowSurveyList;
			
		}
		return $result;
	}
	
	// Dobimo seznam anket za nov prikaz (starega se naceloma ne uporablja vec)
	private function getSurveysAsListNew($folder=0) {

		$result = array();

		// ce imas hkrati dostop do ankete (srv_dostop) in preko managerskega dostopa (srv_dostop_manage) se brez DISTINCT podvajajo ankete
		$stringSurveyList = "SELECT DISTINCT sa.id, sa.folder, '1' as del, sa.naslov, sa.active, sa.mobile_created, sa.edit_time, ";
		$stringSurveyList .= 'sal.lib_glb AS lib_glb, ';
		$stringSurveyList .= 'sal.lib_usr AS lib_usr, ';
		$stringSurveyList .= "sa.edit_uid, sal.e_name AS e_name, sal.e_surname AS e_surname, sal.e_email AS e_email, ";
		$stringSurveyList .= "sa.insert_uid, sal.i_name AS i_name, sal.i_surname AS i_surname, sal.i_email AS i_email, ";
		$stringSurveyList .= "date_format(edit_time, '%d.%m.%y %k:%i') AS e_time, ";
		$stringSurveyList .= "date_format(insert_time, '%d.%m.%y %k:%i') AS i_time, ";
		$stringSurveyList .= "date_format(sal.a_first, '%d.%m.%y %k:%i') AS v_time_first, date_format(sal.a_last, '%d.%m.%y %k:%i') AS v_time_last, ";

		$stringSurveyList .= "sal.answers as answers, "; // vedno prestejemo odgovore
		$stringSurveyList .= "sal.variables as variables, ";
		$stringSurveyList .= "date_format(sa.starts, '%d.%m.%y') as trajanjeod, ";
		$stringSurveyList .= "date_format(sa.expire, '%d.%m.%y') as trajanjedo, ";
		$stringSurveyList .= "approp, ";
		$stringSurveyList .= "sd.canEdit, sa.survey_type "; // tega pustim tukaj, da ni problemov z vejico

		if($folder > 0)	
            $stringSurveyList .= ", sf.folder as mysurvey_folder ";
            		
		// Ce searchamo po besedah dodamo se uvod, zakljucek, naslove vprasanj in vrednosti vprasanj
		if($this->isSearch == 1 && $this->searchSettings['stype'] == '2'){
			$stringSurveyList .= ", sa.introduction AS introduction, sa.conclusion AS conclusion ";
			
			$stringSurveyList .= ", sg.id AS sg_id, sg.ank_id AS sg_ank_id ";
			$stringSurveyList .= ", ss.id AS ss_id, ss.naslov AS ss_naslov, ss.gru_id AS ss_gru_id ";
			$stringSurveyList .= ", sv.id AS sv_id, sv.naslov AS sv_naslov, sv.spr_id AS sv_spr_id ";
		}
        
        
		$stringSurveyList .= "FROM srv_anketa sa ";
		$stringSurveyList .= "LEFT OUTER JOIN srv_survey_list AS sal ON sal.id = sa.id " ;
		$stringSurveyList .= "LEFT OUTER JOIN srv_library_anketa AS sla ON sla.ank_id = sa.id " ;
		
		# kdo lahko ureja anketo (briše)
		// tega substringy se ne da dodatno razbit z prepareSubquery, ker selectamo 2 elementa...
		$stringSurveyList .= "LEFT OUTER JOIN (SELECT 1 AS canEdit, ank_id FROM srv_dostop WHERE FIND_IN_SET('edit', dostop ) ='1' AND aktiven = '1' AND uid = '$this->g_uid' OR uid IN (".SurveyCopy::prepareSubquery(sisplet_query("SELECT user FROM srv_dostop_manage WHERE manager = '".$this->g_uid."'")).")) AS sd ON sd.ank_id = sa.id " ;
		#$stringSurveyList .= "LEFT OUTER JOIN (SELECT ank_id, FIND_IN_SET('edit', dostop ) AS canEdit FROM srv_dostop WHERE aktiven = '1' AND uid = '$this->g_uid') AS sd ON sd.ank_id = sa.id " ;

		if($folder > 0)
			$stringSurveyList .= "LEFT OUTER JOIN srv_mysurvey_anketa AS sf ON sf.ank_id = sa.id ";

		// Ce iscemo po kljucnih besedah moramo dodat se tabele srv_grupa, srv_spremenljivka in srv_vrednost
		if($this->isSearch == 1 && $this->searchSettings['stype'] == '2'){
			$stringSurveyList .= "LEFT OUTER JOIN srv_grupa AS sg ON sg.ank_id = sa.id ";
			$stringSurveyList .= "LEFT OUTER JOIN srv_spremenljivka AS ss ON ss.gru_id = sg.id ";
			$stringSurveyList .= "LEFT OUTER JOIN srv_vrednost AS sv ON sv.spr_id = ss.id ";
		}
		
		$stringSurveyList .= "WHERE sa.backup='0' AND sa.id>0 AND active >= '0' AND invisible = '0' ";
		
		//$stringSurveyList .= $this->getFolderCondition();
		//$stringSurveyList .= $this->getLibraryCondition();	// Tega ni vec ker imamo nove folderje v mojih anketah
		if($folder > 0)
			$stringSurveyList .= "AND sf.usr_id='$this->g_uid' AND sf.folder='$folder' ";
		elseif($folder == 0)
			$stringSurveyList .= "AND NOT EXISTS (SELECT * FROM srv_mysurvey_anketa sma WHERE sma.ank_id=sa.id AND sma.usr_id='$this->g_uid') ";
    

        // GDPR filter
        if($this->gdpr == 1)
            $stringSurveyList .= "AND EXISTS (SELECT * FROM srv_gdpr_anketa sgdpr WHERE sgdpr.ank_id=sa.id) ";
		elseif($this->gdpr == 2)
			$stringSurveyList .= "AND NOT EXISTS (SELECT * FROM srv_gdpr_anketa sgdpr WHERE sgdpr.ank_id=sa.id) ";


		// Ce izvajamo search po anketah
		if($this->isSearch == 1){
			// Filter glede na search
			$stringSurveyList .= $this->getSearchString();

			// Filter glede na jezik ankete
			$stringSurveyList .= $this->getLanguageLimit();
			// Filter glede na dostop do ankete
			$stringSurveyList .= $this->getDostopAnketa();
			// Vrstni red anket
			$stringSurveyList .= $this->getOrderString();	

			// Ce iscemo po kljucnih besedah moramo na koncu grupirat po anketi
			if($this->searchSettings['stype'] == '2'){
				//$stringSurveyList .= " GROUP BY id";
				//$stringSurveyList .= " LIMIT 1000";
			}
			else{
				// Limit anket
				//$stringSurveyList .= $this->getLimitString();
				$stringSurveyList .= " LIMIT 1000";
			}
		}
		else{
			// Filter glede na jezik ankete
			$stringSurveyList .= $this->getLanguageLimit();
			// Filter glede na dostop do ankete
			$stringSurveyList .= $this->getDostopAnketa();
			// Vrstni red anket
			$stringSurveyList .= $this->getOrderString();	
			// Limit anket
			$stringSurveyList .= $this->getLimitString();	
		}
		
		
		$sqlSurveyList = sisplet_query($stringSurveyList);
		if (!$sqlSurveyList) {
			print_r("ERROR in query:");
			print_r($stringSurveyList);
			echo mysqli_error($GLOBALS['connect_db']);
		}
		
		while ($rowSurveyList = mysqli_fetch_assoc($sqlSurveyList)) {
			$result[$rowSurveyList['id']] = $rowSurveyList;		
		}

		return $result;
	}
	
	
	// Enostaven seznam anket za mobilno aplikacijo
	public function getSurveysSimple($ank_id = 0, $limit = '', $mobile_created = -1, $include_folders=false) {

		$result = array();

		// ce imas hkrati dostop do ankete (srv_dostop) in preko managerskega dostopa (srv_dostop_manage) se brez DISTINCT podvajajo ankete
		$stringSurveyList = "SELECT DISTINCT sa.id, sa.folder, '1' as del, sa.naslov, sa.active, sa.mobile_created, sa.block_ip, ";
		//$stringSurveyList .= 'sal.lib_glb AS lib_glb, ';
		//$stringSurveyList .= 'sal.lib_usr AS lib_usr, ';
		$stringSurveyList .= "sa.edit_uid, sal.e_name AS e_name, sal.e_surname AS e_surname, sal.e_email AS e_email, ";
		$stringSurveyList .= "sa.insert_uid, sal.i_name AS i_name, sal.i_surname AS i_surname, sal.i_email AS i_email, ";
		$stringSurveyList .= "date_format(edit_time, '%d.%m.%y %k:%i') AS e_time, ";
		$stringSurveyList .= "date_format(insert_time, '%d.%m.%y %k:%i') AS i_time, ";
		$stringSurveyList .= "date_format(sal.a_first, '%d.%m.%y %k:%i') AS v_time_first, date_format(sal.a_last, '%d.%m.%y %k:%i') AS v_time_last, ";

		$stringSurveyList .= "sal.answers as answers, "; // vedno prestejemo odgovore
                $stringSurveyList .= "sal.approp as approp, "; // vedno prestejemo odgovore
		$stringSurveyList .= "sal.variables as variables, ";
		$stringSurveyList .= "date_format(sa.starts, '%d.%m.%y') as trajanjeod, ";
		$stringSurveyList .= "date_format(sa.expire, '%d.%m.%y') as trajanjedo, ";
		$stringSurveyList .= "sa.survey_type "; // tega pustim tukaj, da ni problemov z vejico
		
		$stringSurveyList .= "FROM srv_anketa sa ";
		$stringSurveyList .= "LEFT OUTER JOIN srv_survey_list AS sal ON sal.id = sa.id " ;
		$stringSurveyList .= "LEFT OUTER JOIN srv_library_anketa AS sla ON sla.ank_id = sa.id " ;
		
		# kdo lahko ureja anketo (briše)
		// tega substringy se ne da dodatno razbit z prepareSubquery, ker selectamo 2 elementa...
		$stringSurveyList .= "LEFT OUTER JOIN (SELECT 1 AS canEdit, ank_id FROM srv_dostop WHERE FIND_IN_SET('edit', dostop ) ='1' AND aktiven = '1' AND uid = '$this->g_uid' OR uid IN (".SurveyCopy::prepareSubquery(sisplet_query("SELECT user FROM srv_dostop_manage WHERE manager = '".$this->g_uid."'")).")) AS sd ON sd.ank_id = sa.id " ;
		
		$stringSurveyList .= "WHERE sa.backup='0' AND sa.id>0 AND active >= '0' AND invisible = '0' ";
                
                if($mobile_created == 1)
                    $stringSurveyList .= "AND sa.mobile_created='".$mobile_created."' ";
		
                if(!$include_folders)
                    $stringSurveyList .= "AND NOT EXISTS (SELECT * FROM srv_mysurvey_anketa sma WHERE sma.ank_id=sa.id AND sma.usr_id='$this->g_uid') ";
		
		// Ce imamo podan ank_id vrnemo samo za 1 anketo
		if($ank_id > 0)
			$stringSurveyList .= "AND sa.id='".$_GET['ank_id']."' ";
		
		//$stringSurveyList .= $this->getLanguageLimit();
		$stringSurveyList .= $this->getDostopAnketa();
		//$stringSurveyList .= $this->getOrderString();
		//$stringSurveyList .= $this->getLimitString();
		
		//$stringSurveyList .= 'ORDER BY upper(naslov) ASC';
                
                //zacasno zaradi aplikacije
                if($mobile_created == 1)
                    $stringSurveyList .= "ORDER BY edit_time DESC";
                else
                    $stringSurveyList .= 'ORDER BY sal.a_last DESC';
                
                //@Uros dodal, da vrne samo doloceno stevilo zadnjih anktivnih anket, ce je nastavljeno
                if($limit != '' && $limit != 0)
                    $stringSurveyList .= ' limit '.$limit;
		
		$sqlSurveyList = sisplet_query($stringSurveyList);
		while ($rowSurveyList = mysqli_fetch_assoc($sqlSurveyList)) {
			
			// Pretvorimo vse v utf - drugace vcasih ne dela json_encode
			foreach($rowSurveyList as $key => $val){
				//$rowSurveyList[$key] = utf8_encode($val);
				$rowSurveyList[$key] = mb_convert_encoding($val, 'HTML-ENTITIES', "UTF-8");
            }

			$result[] = $rowSurveyList;	
		}	
		return $result;
	}
	
	private function createOrderUrl($id=null, $txt) {

		if (!isset($id) || $id == null || $id == "") { 
			$id=1;
		}
			
		if ($this->sortby != $id ) {
			$img_src = 'sort_unsorted';
			$result='<div onClick="surveyList_goTo(\''.$id.'\',\'1\')">'.$txt.'</div>';
		} else {

			if ($this->sorttype == 2) {
				$img_src = 'sort_ascending';
				$result='<div onClick="surveyList_goTo(\''.$id.'\',\'1\')" class="red">'.$txt.'<span class="faicon '.$img_src.'" title=""></span></div>';
			} else {
				$img_src = 'sort_descending';
				
				$result='<div onClick="surveyList_goTo(\''.$id.'\',\'2\')" class="red">'.$txt.'<span class="faicon '.$img_src.'" title=""></span></div>';
			}
		}
	
		return $result;
	}
	
	private function createOrderUrlNew($id=null, $txt) {
		global $site_url;
		
		if (!isset($id) || $id == null || $id == "") { 
			$id=1;
		}
			
		if ($this->sortby != $id ) {
			$result='<div onClick="surveyList_goTo(\''.$id.'\',\'1\')">'.$txt.'</div>';
		} else {

			if ($this->sorttype == 2) {
				$result='<div class="active" onClick="surveyList_goTo(\''.$id.'\',\'1\')">'.$txt.'<span class="active faicon after sort_up_arrow icon-orange"/></div>';
			} else {
				$result='<div class="active" onClick="surveyList_goTo(\''.$id.'\',\'2\')">'.$txt.'<span class="active faicon after sort_down_arrow icon-orange"/></div>';
			}
		}
	
		return $result;
	}
	
	private function echoText($text, $type = 'text', $id = null,$options=array()) {
		global $lang;
		global $site_url;

		if ( $type == 'text') {
			$result = ( isset($text) && $text != null && $text != "") ? $text : "&nbsp;";
		} elseif ($type == 'naslov') {
			$result = '<strong><a href="'.$site_url.'admin/survey/index.php?anketa='.$id.'&a='.A_REDIRECTLINK.'" title="'.$text.'">'.$text.'</a></strong>';
		} elseif ($type == 'active') {
 	    	$result = '<a href="/" onclick="anketa_active(\''.$id.'\',\''.(int)$text.'\',\'true\'); return false;">' .
             		  '<span class="faicon '.((int)$text==1?'star_on':'star_off').'" alt="'.(int)$text.'" title="'.((int)$text==1?$lang['srv_anketa_active']:$lang['srv_anketa_noactive']).'"></span>'.
             		  '</a>';
		} elseif ($type == 'delete') {
			if ((int)$options['anketa_canEdit'] > 0) {
 	    	$result = '<a href="/" onclick="anketa_delete_list(\''.$id.'\', \''.$lang['srv_anketadeleteconfirm'].'\'); return false;">' .
             		  '<span class="faicon delete_circle icon-orange_link" title="'.$lang['srv_anketa_delete'].'"></span>'.
             		  '</a>';
			} else {
				$result=' ';
			}
		} elseif ($type == 'lib_glb') {
			if ($this->g_adminType == 0) {
				// samo admin lahko dodaja in odstranjuje v sistemsko knjiznico			
				$result = '<a href="/" onclick="surveyList_knjiznica(\''.$id.'\'); return false;">'.
                 	  '<span class="sprites library_admin_'.((int)$text==1?'on':'off').'" title="'.((int)$text==1?$lang['srv_ank_lib_off']:$lang['srv_ank_lib_on']).'"></span>'.
                 	  '</a>';
			} else {
				$result = '<a href="/" onclick="surveyList_knjiznica_noaccess(\''.$lang['srv_list_library_no_access'].'\'); return false;">'.
                 	  '<span class="sprites library_admin_'.((int)$text==1?'on':'off').'" title="'.((int)$text==1?$lang['srv_ank_lib_off']:$lang['srv_ank_lib_on']).'"></span>'.
                 	  '</a>';
			}
		} elseif ($type == 'lib_usr') {
	        $result = '<a href="/" onclick="surveyList_myknjiznica(\''.$id.'\'); return false;">'.
	             	  '<span class="sprites '.((int)$text==1?'library_on':'library_off').'" title="'.((int)$text==1?$lang['srv_ank_mylib_off']:$lang['srv_ank_mylib_on']).'"></span>'.
	             	  '</a>';
		} elseif ($type == 'survey_type') {
			if ($text == '3') {
				$text = '2';
			} 
			
	        $result = $lang['srv_vrsta_survey_type_'.$text];
		
		} elseif ($type == 'euid' || $type == 'iuid') {
			$text = iconv("iso-8859-2", "utf-8",$text);
			if ($options['anketa_is_copy']) {
	        	$result = $lang['srv_survey_is_copy'];
			} else {
				$result = ( isset($text) && $text != null && $text != "") 
					? '<span class="as_link"'.($type == 'euid' ? ' onclick="surveyList_user(\'e\',this);" euid="'.$options['anketa_e_uid'].'"': ' onclick="surveyList_user(\'i\',this);"  iuid="'.$options['anketa_i_uid'].'"').'>'.$text.'</span>' : 
					"&nbsp;";

					// echoText($text, 'text', $id, $options );
			}
		} else {

			$this->echoText($text, 'text', $id, $options );
		}
	
		echo (isset($result) && $result != "" && $result != null) ? $result : "&nbsp;";
	}
	
	/**
	 * polovimo shranjene ?irine za polja
	 */
	private function getCssSetings() {
		global $global_user_id;
		$result_old_data = array();
		if ($this->g_uid > 0) {
			// najprej iz nastavitev preberemo obstoje?e shranjene ?irine 			
			$saved_old_data_string = UserSetting::getInstance()->getUserSetting('survey_list_widths'); 
			
			if (isset($saved_old_data_string) && $saved_old_data_string != null && $saved_old_data_string != "" ) {
				$old_data = array_unique(explode(";",$saved_old_data_string));				
				foreach ( $old_data as $tmp_old_data ) {
					$_tmp_old_data = array_unique(explode(",",$tmp_old_data));
					$result_old_data[$_tmp_old_data[0]] = $_tmp_old_data[1]; 
				}
			}
		}
		return $result_old_data;
	}

	/** Izrišemo div z nastavitvami
	 * 
	 * Enter description here ...
	 * @param unknown_type $display_default
	 */
	public function displaySettings($display_default = false) {
		global $site_url, $lang;

		# izpisemo dive
		$settingsArray = $this->getSettings();
		echo '<div id="survey_list_inner">';
			echo '<input type="hidden" id="sortby" value="'.$_POST['sortby'].'">';
			echo '<input type="hidden" id="sorttype" value="'.$_POST['sorttype'].'">';
		$grupaName = "";
		$zastopaneGrupe = array();

		echo '<div class="floatLeft" style="width:auto !important"><ul id="sortable" style="width:auto !important;">';
		foreach ( $settingsArray as $opcija ) {
			// preverimo ali je nova grupa
			if ((!isset($opcija['header_grupa']) && $grupaName != "") || // nismo več v grupi stara še obstaja  
				( isset($opcija['header_grupa']) && $grupaName != "" && $opcija['header_grupa'] != $grupaName)) { // smo v grupi ampak ime ni enako prejšnjemu
				echo '</ul>';
				echo '</div>';
				echo '<div class="clr"></div>';
				echo '</li>';
				$grupaName = "";		
			}
			// preverimo ali naredimo novo grupo (Vnesel / urejal) 
			if (isset($opcija['header_grupa']) && $grupaName == "") { // smo v grupi polj pod in imamo podpolja: ime priimek, email, datum
				// imamo začtek grupe 
				echo '<li class="sortable_group">';
				echo '<div style="width:20px; float:left;">';
				echo '<img class="parent movable" src="'.$site_url.'admin/survey/icons/icons/move_updown.png" alt="move" vartical-align="middle" />';
				echo '</div>';
				echo '<div id="group_holder" style="float:left;">';
				echo '<ul id="sortableGroup" name="'.$opcija['header_grupa'].'">';
				$grupaName = $opcija['header_grupa'];
				$zastopaneGrupe[] = $opcija['header_grupa'];
			}
			if (!isset($opcija['header_grupa'])) {
				echo '<li id="'.$opcija['id'].'" class="sortable_noGroup"><span class="">';
				echo '<img class="parent movable" src="'.$site_url.'admin/survey/icons/icons/move_updown.png" alt="move" vartical-align="middle" />';
				// ?e je viden ali ?e je id = 1 (ime ankete) potem dodamo checkbox (imena ankete ne moremo izklju?it)
				echo '<input name="sl_fields" id="sl_fields_'.$opcija['id'].'" value="'.$opcija['id'].'" type="checkbox" '.($opcija['visible'] == 1 || $opcija['id'] == 1 ? 'checked="checked"':'').' '.($opcija['id'] == 1 ? 'disabled="disabled"':'').'>';
				echo $lang['srv_h_'.$opcija['header_field']];
				echo '</span>';
				echo '</li>';
			} else {
				echo '<li id="'.$opcija['id'].'" class="sortable_noGroup"><span class="">';
				echo '<img class="sub_child movable" src="'.$site_url.'admin/survey/icons/icons/move_updown.png" alt="move" vartical-align="middle" />';
				// ?e je viden ali ?e je id = 1 (ime ankete) potem dodamo checkbox (imena ankete ne moremo izklju?it)
				echo '<input name="sl_fields" id="sl_fields_'.$opcija['id'].'" value="'.$opcija['id'].'" type="checkbox" '.($opcija['visible'] == 1 || $opcija['id'] == 1 ? 'checked="checked"':'').' '.($opcija['id'] == 1 ? 'disabled="disabled"':'').'>';
				echo $lang['srv_'.$opcija['header_grupa']]. " - ";
				echo $lang['srv_h_'.$opcija['header_field']];
				echo '</span>';
				echo '</li>';		
			}
		}
		// na koncu ?e preverimo ali imamo kon?ano grupo ?ene nardimo zaključna diva
		if ($grupaName != "") {
			echo '</ul>';
			echo '</div>';
			echo '<div class="clr"></div>';
			
			echo '</li>';
			$grupaName = "";		
		}

		echo '</ul></div>';
		
		echo '<div class="floatRight sl_div_error_holder" style="margin-right:10px; width:420px;">';
		echo '<div id="div_error" class="red sl_div_error"><img src="icons/icons/error.png" alt="" vartical-align="middle" />'.$lang['srv_sl_error_msg'].'</div>';
		echo '<div style="margin-top:20px;">'.$lang['srv_sl_setting_show'].'<input id="rows_per_page" value="'.$this->rec_per_page.'" type="text" />'.$lang['srv_sl_setting_records'];
		echo '</div>';
		echo '<div style="margin-top:20px;">';
		echo '  <div class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="default_surveyListSettings(); return false;"><span><img src="'.$site_url.'admin/survey/icons/icons/page_white_gear.png" alt="" vartical-align="middle" />'.$lang['srv_default'].'</span></a></div></div>';
		echo '  <div class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="cancle_surveyListSettings(); return false;"><span><img src="'.$site_url.'admin/survey/icons/icons/cog_back.png" alt="" vartical-align="middle" />'.$lang['srv_cancel'].'</span></a></div></div>';
		echo '  <div class="floatLeft spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="save_surveyListSettings(); return false;"><span><img src="'.$site_url.'admin/survey/icons/icons/cog_save.png" alt="" vartical-align="middle" />'.$lang['save'].'</span></a></div></div>';
		echo '<div class="clr"></div>';
		echo '</div>';
		echo '</div>';
		
		echo '<div class="clr"></div>';
		echo '</div>';
		echo	'<script type="text/javascript">';
		// echo '$(function() {';
		echo '$(document).ready(function() {';
	 	echo '$("#sortable").sortable({ axis: "y", handle: "img.parent"}).stop();';
		// echo '$("#sortable").disableSelection();';
			foreach ( $zastopaneGrupe as $grupa ) {
    			echo '$("[name='.$grupa.']").sortable({ axis: "y", handle: "img.sub_child", zIndex: 5  }).stop();';
    		}
			// $("#sortableGroup").sortable();
			// $("#sortableGroup").disableSelection();

		echo '})';
        echo '</script>';
	}
	
	public function displayListQickInfo() {
		# prikažemo hitri povzetek anket uporabnika
		
		# preštejemo zadnje ankete v 12 urah
		$ank1hour = "SELECT id FROM srv_anketa sa WHERE sa.backup='0' AND sa.id > 0 AND sa.active >= 0 AND invisible = '0' AND (sa.insert_time > (DATE_SUB(CURDATE(), INTERVAL 1 HOUR)) || sa.edit_time > (DATE_SUB(CURDATE(), INTERVAL 1 HOUR)) ) ".$this->getDostopAnketa();
		$ank12hour = "SELECT id FROM srv_anketa sa WHERE sa.backup='0' AND sa.id > 0 AND sa.active >= 0 AND invisible = '0' AND (sa.insert_time > (DATE_SUB(CURDATE(), INTERVAL 12 HOUR)) || sa.edit_time > (DATE_SUB(CURDATE(), INTERVAL 12 HOUR)) ) ".$this->getDostopAnketa();
		$ank24hour = "SELECT id FROM srv_anketa sa WHERE sa.backup='0' AND sa.id > 0 AND sa.active >= 0 AND invisible = '0' AND (sa.insert_time > (DATE_SUB(CURDATE(), INTERVAL 24 HOUR)) || sa.edit_time > (DATE_SUB(CURDATE(), INTERVAL 23 HOUR)) ) ".$this->getDostopAnketa();
	
		$qry1hour = sisplet_query($ank1hour);
		$qry12hour = sisplet_query($ank12hour);
		$qry24hour = sisplet_query($ank24hour);
		
		$cnt1hour = mysqli_num_rows($qry1hour);
		$cnt12hour = mysqli_num_rows($qry12hour);
		$cnt24hour = mysqli_num_rows($qry24hour);
		

	}
	
	
	// Vrne stevilo vseh anket
	public function countSurveys() {
		return count($this->surveys_ids);
	}
	
	// Vrne stevilo vseh anket v rootu (ce imamo folderje)
	public function countRootSurveys() {
		global $global_user_id;
		
		// Poiscemo vse ankete v custom folderjih
		$sql = sisplet_query("SELECT ank_id FROM srv_mysurvey_anketa WHERE usr_id='$global_user_id'");
		
		// Vrnemo razliko v stevilu anket (odstejemo ankete v custom folderjih)
		$survey_count = count($this->surveys_ids);
		if(mysqli_num_rows($sql) > 0)
			$survey_count -= mysqli_num_rows($sql);
		
		return $survey_count;
	}

	/** Anketam ki so potekle popravimo aktivnost
	 * 
	 */
	public function checkSurveyExpire() {
                //v primeru maza moramo sporociti vsem aplikacijam deaktivacijo ankete
                if(Common::checkModule('maza')){
                    $maza = new MAZA();
                    $maza -> maza_check_expired_surveys();
                }
                
		# Anketam ki so potekle popravimo aktivnost
		sisplet_query("UPDATE srv_anketa SET active = '0' WHERE active = '1' AND expire < CURDATE()");
		// vsilimo refresh podatkov
		SurveyInfo :: getInstance()->resetSurveyData();
	}
	
	/** GETERS && SETTERS **/
	/* GETERS */
	private function getParentFolder()	{ return $this->parentFolder; }
	private function getCurrentFolder()	{ return $this->currentFolder; }
	public function getDef_Rows_per_page() { return SRV_LIST_REC_PER_PAGE; }
	
	/** vrne sql pogoj za folderje na podlagi trenutnega folderja */
	private function getFolderCondition () {
		if ($this->folderCondition == null) {
			$resultString = " AND 0";
			#$this->folders = array();
			$folderArray = $this->getFolderTreeAsArray($this->getCurrentFolder());
			if ( count( $folderArray ) > 0) {
				$prefix="";
				$resultString = " AND sa.folder IN (";
				foreach ($folderArray as $fid => $fname) {
		       		$resultString .= $prefix.$fid;
		       		$prefix=",";
				}
				$resultString .=") ";
			}
			$this->folderCondition = $resultString;
		}
		return $this->folderCondition;
	}
	
	/** vrne array z folderjem in subfolderji (če je SRV_LIST_GET_SUB_FOLDERS = true) */ 
	function getFolderTreeAsArray($parent) {
		# če ni dodan parent ga dodamo
		if (!isset($this->folders[$parent])) {
			$parentSql = sisplet_query('SELECT id, naslov FROM srv_folder WHERE id="'.$parent.'";');
			if (mysqli_num_rows($parentSql)>0) {
				$rowParent = mysqli_fetch_assoc($parentSql);
				$this->folders[$rowParent['id']] = $rowParent['naslov'];
			}
		}
	   	if (SRV_LIST_GET_SUB_FOLDERS || $parent == 0) {
			$result = sisplet_query('SELECT id, naslov FROM srv_folder WHERE parent="'.$parent.'";');
			while ($row = mysqli_fetch_array($result)) {
		   		$this->folders[$row['id']] = $row['naslov'];
		   		$this->getFolderTreeAsArray($row['id']);
			}
	   	}
		return $this->folders;
	} 
	
	/** vrne sql pogoj za ankete v moji knjiznici na podlagi trenutnega folderja */
	private function getLibraryCondition () {

		if ($this->currentLibrary == null) {
			$resultString = '';
		}
		else{
			$children = array();
			$children = $this->getLibraryChildren($this->currentLibrary, $children);

			$childrenString = '';
			if(!empty($children))
				$childrenString = ','.implode(',', $children);

			//$resultString = " AND sla.ank_id=sa.id AND sla.folder='".$this->currentLibrary."'";
			$resultString = " AND sla.ank_id=sa.id AND sla.folder IN (".$this->currentLibrary . $childrenString.")";
		}
		
		$this->libraryCondition = $resultString;
		
		return $this->libraryCondition;
	}
	
	private function getLibraryChildren($folder_id, $children){
		
		// Pridobimo vse childe
		$sql = sisplet_query("SELECT id, parent FROM srv_library_folder WHERE parent='".$folder_id."'");
		
		// Izstopni pogoj
		if(mysqli_num_rows($sql) == 0)
			return ($children);
			
		while($row = mysqli_fetch_array($sql)){
			$children[] = $row['id'];
			$children2 = $this->getLibraryChildren($row['id'], $children);
			
			$children = array_merge($children, $children2);
		}
		
		return array_unique($children);
	}
	
	/** vrne sql string za omejevanje dostopa uporabniku */
	function getLanguageLimit()	{
        global $global_admin_type;
        
		if ((int)$this->lang_id > 0) {
			return " AND lang_admin ='".(int)$this->lang_id."'";
		}
	}
	
	/** vrne sql string za omejevanje dostopa uporabniku */
	function getDostopAnketa()	{
		global $global_admin_type;
		global $global_user_id;
		
		if ($this->dostopCondition == null) {
			// posebej za managerje, ki vidijo ankete svojih uporabnikov
			$manage = '';
			
			#generalni dostop glede na tip uporabnikov -->  $admin_type <= $row['dostop']
			# posebej dostop za vsazga userja posebej  -->  sisplet_query("SELECT * FROM srv_dostop WHERE ank_id = '$anketa' AND uid='$uid'") -> if (mysqli_num_rows($sql) > 0)
			if ($this->user_id == null) {
				if ($this->g_adminType == 1 || $this->g_adminType == 0) 
					$manage = " OR uid IN (".SurveyCopy::prepareSubquery(sisplet_query("SELECT user FROM srv_dostop_manage WHERE manager = '".$this->g_uid."' ")).") ";
				
				$this->dostopCondition = (SRV_LIST_CHECK_DOSTOP ?  " AND (sa.id IN (".SurveyCopy::prepareSubquery(sisplet_query("SELECT ank_id FROM srv_dostop WHERE uid='".$this->g_uid."' $manage"))."))" : "");
			} 
			else {
				if ($this->g_adminType == 1 || $this->g_adminType == 0) 
					$manage = " OR uid IN (".SurveyCopy::prepareSubquery(sisplet_query("SELECT user FROM srv_dostop_manage WHERE manager = '".$this->g_uid."'")).") ";

				$this->dostopCondition = (SRV_LIST_CHECK_DOSTOP ?  " AND (insert_uid = '".$this->user_id."') AND (sa.id IN (".SurveyCopy::prepareSubquery(sisplet_query("SELECT ank_id FROM srv_dostop WHERE uid='".$this->g_uid."' $manage"))."))" : "");
			}
			
			# če ni admin odstranimo ankete kjer je uporabnik označen samo kot anketar
			if ( true /*$this->g_adminType != '0'*/ ) { 
				$this->dostopCondition .= " AND sa.id".($this->onlyPhone == false ? " NOT":"")." IN"
				." (SELECT ank_id FROM srv_dostop AS sd WHERE sd.aktiven = '1' AND sd.uid = '$this->g_uid' AND FIND_IN_SET('phone',sd.dostop )>0 AND FIND_IN_SET('edit',sd.dostop ) = 0) ";
			} 
			else {
			}
		}
		

		// meta admin vidi kao spet vse
		if ( Dostop::isMetaAdmin() ) {
			
			if ($this->user_id == null) {
				$this->dostopCondition = (SRV_LIST_CHECK_DOSTOP ?  " AND (sa.dostop >= '".$this->g_adminType."' OR sa.id IN (".SurveyCopy::prepareSubquery(sisplet_query("SELECT ank_id FROM srv_dostop WHERE uid='".$this->g_uid."' $manage"))."))" : "");
			} 
			// filtriranje
			else {  
				$manage = " OR uid IN (".SurveyCopy::prepareSubquery(sisplet_query("SELECT user FROM srv_dostop_manage WHERE manager = '".$this->g_uid."'")).") ";
				$this->dostopCondition = (SRV_LIST_CHECK_DOSTOP ?  " AND (insert_uid = '".$this->user_id."') AND (sa.id IN (".SurveyCopy::prepareSubquery(sisplet_query("SELECT ank_id FROM srv_dostop WHERE uid='".$this->g_uid."' $manage"))."))" : "");
			}

			// Včasih se za meta admina nekaj porusi in ne prikaze nobene ankete...
			//$this->dostopCondition = '';
		}
		
		return $this->dostopCondition;
	}
	
	/** vrne order string za SQL s katerim sortiramo */
	private function getOrderString() {
		
		if (!isset($this->sortby) || (int)$this->sortby == 0) {
			$this->sortby = SRV_LIST_ORDER_BY;
		}
		
		if (isset($this->order_by_options[$this->sortby])) {
			$order_by = $this->order_by_options[$this->sortby];
		} else {
			$order_by = $this->order_by_options[SRV_LIST_ORDER_BY];
		}
		
		// UPPER damo, ker drugace sortira najprej vlke crke potem pa male
		if($this->sortby == 1 || $this->sortby == 7 || $this->sortby == 11){
			// Zamenjamo nacin sortiranja pri besedilu (drugace zacnemo od zadaj)
			$sorttype = ($this->sorttype == 1) ? 2 : 1;
			$result = " ORDER BY upper(" . $order_by . ") " . $this->sort_types_options[$sorttype];
		}
		// Dodamo opcije sortiranja pri statusu (18), ker ni dovolj sortiranje po "expired"
		elseif($this->sortby == 18){
			$sorttypeReverse = ($this->sorttype == 1) ? 2 : 1;
			$result = " ORDER BY 
						sa.active ".$this->sort_types_options[$this->sorttype].",
						(SELECT IF(COUNT(sac.sid) > 0, 1, 0) FROM srv_activity sac WHERE sac.sid=sa.id) ".$this->sort_types_options[$this->sorttype].",
						".$order_by." ".$this->sort_types_options[$sorttypeReverse];
		}
		else
			$result = " ORDER BY " . $order_by . " ".$this->sort_types_options[$this->sorttype];

		return $result;
	}
	
	/** vrne Limit string za SQL s katerim prika?emo posamezno stran */
	private function getLimitString() {
		$result = " LIMIT " . ($this->pageno * $this->rec_per_page - $this->rec_per_page) . ", " . $this->rec_per_page;
		return $result;
	}
	
	// vrne sql string za search po anketah glede na nastavitve searcha
	private function getSearchString(){

		$search_text = mysqli_real_escape_string($GLOBALS['connect_db'], $this->searchString);
        $search_text = stripslashes(stripslashes($search_text));
        
        // Vse gre v lowerstring
        $search_text = strtolower($search_text);
		
		// Ce gre za string v narekovajih
		if($search_text[0] == '"' && $search_text[strlen($search_text) - 1] == '"'){
			$search_text = trim($search_text, '"');
			$this->searchStringProcessed[] = $search_text;
			$search_text = '%'.$search_text.'%';	
		}
		else{
			// Sklanjamo po search besedi - ce gre za search po naslovu ali kljucnih besedah
			if ($this->searchSettings['stype'] == '0' || $this->searchSettings['stype'] == '2') {        
                
                // odstrani vse zvezdice in pluse in skrajsaj besede za dva znaka, dodaj *.
		        $search_text = explode (" ", $search_text);
		        
				for ($a=0; $a<sizeof($search_text); $a++) {
		            if (strlen ($search_text[$a]) > 5) 
						$search_text[$a] = substr ($search_text[$a], 0, -2);
		            elseif (strlen ($search_text[$a]) > 2) 
						$search_text[$a] = substr ($search_text[$a], 0, -1);
		            else 
						$search_text[$a] = $search_text[$a];
					
					$this->searchStringProcessed[$a] = $search_text[$a];
					$search_text[$a] = '%'.$search_text[$a].'%';
		        }
		        
				$search_text = implode(" ", $search_text);		
		    }
			else{
				$this->searchStringProcessed[] = $search_text;
				$search_text = '%'.$search_text.'%';
			}
		}
		
		// Search po avtorju
		if($this->searchSettings['stype'] == '1'){ 
            $result = " AND (i_name LIKE '".$search_text."' OR i_surname LIKE '".$search_text."' OR i_email LIKE '".$search_text."')";
        }
		// Search po kljucnih besedah znotraj vprasanj (naslovi vprasanj in vrednosti)
		elseif($this->searchSettings['stype'] == '2'){
			$result = " AND (LOWER(introduction) LIKE LOWER('".$search_text."')
								OR LOWER(conclusion) LIKE LOWER('".$search_text."') 
								OR LOWER(ss.naslov) LIKE LOWER('".$search_text."') 
                                OR LOWER(sv.naslov) LIKE LOWER('".$search_text."'))";
        }
		// Search po naslovu
		else{
            $result = " AND (LOWER(sa.naslov) LIKE LOWER('".$search_text."') OR LOWER(sa.akronim) LIKE LOWER('".$search_text."'))";
        }
		
		// Search po statusu (aktivne, neaktivne)
		if($this->searchSettings['sstatus'] == '1')
			$result .= " AND active > '0'";
		if($this->searchSettings['sstatus'] == '2')
			$result .= " AND active = '0'";
		
		// Search po datumu ustvarjanja (od)
		if($this->searchSettings['sidatefrom'] != ''){			
			$date = date('Y-m-d H:i:s', strtotime($this->searchSettings['sidatefrom']));
			$result .= " AND insert_time >= '".$date."'";
		}
		// Search po datumu ustvarjanja (do)
		if($this->searchSettings['sidateto'] != ''){
			$date = date('Y-m-d H:i:s', strtotime($this->searchSettings['sidateto']));
			$result .= " AND insert_time <= '".$date."'";
		}
		
		// Search po datumu zadnjega urejanja (od)
		if($this->searchSettings['sedatefrom'] != ''){
			$date = date('Y-m-d H:i:s', strtotime($this->searchSettings['sedatefrom']));
			$result .= " AND edit_time >= '".$date."'";
		}
		// Search po datumu zadnjega urejanja (do)
		if($this->searchSettings['sedateto'] != ''){
			$date = date('Y-m-d H:i:s', strtotime($this->searchSettings['sedateto']));
			$result .= " AND edit_time <= '".$date."'";
		}
		
		// Dodaten search po avtorju samo za metaadmine
		if($this->searchSettings['onlyAuthor'] != ''){
			$onlyAuthorString = mysqli_real_escape_string($GLOBALS['connect_db'], $this->searchSettings['onlyAuthor']);
			$onlyAuthorString = stripslashes(stripslashes($onlyAuthorString));

			$result .= " AND (i_name LIKE '%".$onlyAuthorString."%' 
							OR i_surname LIKE '%".$onlyAuthorString."%' 
							OR i_email LIKE '%".$onlyAuthorString."%')";
		}
		
		return $result;
	}
	
	// Dobimo vse parametri searcha
	private function getSearchParams(){
		global $site_url;

		$params = '';
		
		if($this->isSearch == 1){
			$params .= 'search='.urlencode($this->searchString);
			
			if(!empty($this->searchSettings)){
				foreach($this->searchSettings as $key => $val){
					$params .= '&'.$key.'='.urlencode($val);
				}
			}
		}
		
		return $params;
	}
	
	
	/**
	 *  shranimo širine celic
	 */
	public function saveCssSettings($data) {
		$new_data = array();
		if (isset($data) && $data != null) {
			$new_data = $this->getCssSetings();
			// nato popravimo vrednost 	
			$_tmp_new_data = array_unique(explode(",",$data));
			$new_data[$_tmp_new_data[0]] = $_tmp_new_data[1];
			// nato zdru?imo v primerno obliko in shranimo
			$saveString = "";
			$saveStringPrefix = "";
			if (isset($new_data) && $new_data != null && count($new_data) > 0) {
				foreach ( $new_data as $tmp_new_key => $tmp_new_data ) {
					$saveString .= $saveStringPrefix.$tmp_new_key.",".$tmp_new_data;	
					$saveStringPrefix = ";";
				}
			}	
			UserSetting::getInstance()->setUserSetting('survey_list_widths', $saveString);
		} else {
			UserSetting::getInstance()->setUserSetting('survey_list_widths', "");
		}
		// shranimo
		UserSetting::getInstance()->saveUserSetting('survey_list_widths', $saveString);
	}
	/* SETERS */
	private function setParentFolder($parentFolder = 0)	{ $this->parentFolder = $parentFolder; }
	private function setCurrentFolder($currentFolder = 0)	{ $this->currentFolder = $currentFolder; }
	
    private function UpdateSystemLibrary () {
		global $lang, $site_url;
		
        $anketa = $_POST['anketa'];

		echo '<a href="/" onclick="surveyList_knjiznica(\''.$anketa.'\'); return false;">';
		
		$sql = sisplet_query("SELECT * FROM srv_library_anketa WHERE ank_id='$anketa' AND uid='0'");
        if (mysqli_num_rows($sql) == 0) {
            $sql1 = sisplet_query("SELECT * FROM srv_library_folder WHERE uid='0' AND tip='1' AND parent='0' AND lang='$lang[id]'");
            $row1 = mysqli_fetch_array($sql1);
            sisplet_query("INSERT INTO srv_library_anketa (ank_id, uid, folder) VALUES ('$anketa', '0', '$row1[id]')");
			echo '<span class="sprites library_admin_on" title="'.$lang['srv_ank_lib_on'].'"></span>';
        } else {
            sisplet_query("DELETE FROM srv_library_anketa WHERE ank_id='$anketa' AND uid='0'");
			echo '<span class="sprites library_admin_off" title="'.$lang['srv_ank_lib_off'].'"></span>';
        }
		
		echo '</a>';
    }

    private function UpdateUserLibrary () {
        global $global_user_id, $site_url, $lang;
		
        $anketa = $_POST['anketa'];
		
		echo '<a href="/" onclick="surveyList_myknjiznica(\''.$anketa.'\'); return false;">';
        
		$sql = sisplet_query("SELECT * FROM srv_library_anketa WHERE ank_id='$anketa' AND uid='$global_user_id'");
        if (mysqli_num_rows($sql) > 0) {
            sisplet_query("DELETE FROM srv_library_anketa WHERE ank_id='$anketa' AND uid='$global_user_id'");
            echo '<span class="sprites library_off" title="'.$lang['srv_ank_mylib_off'].'"></span>';
        } else {
            $sql1 = sisplet_query("SELECT * FROM srv_library_folder WHERE uid='$global_user_id' AND tip='1' AND parent='0'");
            $row1 = mysqli_fetch_array($sql1);
            sisplet_query("INSERT INTO srv_library_anketa (ank_id, uid, folder) VALUES ('$anketa', '$global_user_id', '$row1[id]')");
            echo '<span class="sprites library_on" title="'.$lang['srv_ank_mylib_on'].'"></span>';
        }
		
		echo '</a>';
    }
	
	private function UpdateSystemLibraryNew () {
		global $lang, $site_url;
		
        $anketa = $_POST['anketa'];

        $sql = sisplet_query("SELECT * FROM srv_library_anketa WHERE ank_id='$anketa' AND uid='0'");

        if (mysqli_num_rows($sql) == 0) {
            $sql1 = sisplet_query("SELECT * FROM srv_library_folder WHERE uid='0' AND tip='1' AND parent='0' AND lang='$lang[id]'");
            $row1 = mysqli_fetch_array($sql1);
            
            sisplet_query("INSERT INTO srv_library_anketa (ank_id, uid, folder) VALUES ('$anketa', '0', '$row1[id]')");
			sisplet_query("UPDATE srv_survey_list SET lib_glb='1' WHERE id='$anketa'");
        } 
        else {
            sisplet_query("DELETE FROM srv_library_anketa WHERE ank_id='$anketa' AND uid='0'");
			sisplet_query("UPDATE srv_survey_list SET lib_glb='0' WHERE id='$anketa'");
        }
    }

	private function UpdateUserLibraryNew () {
        global $global_user_id, $site_url;
		
        $anketa = $_POST['anketa'];

        $sql = sisplet_query("SELECT * FROM srv_library_anketa WHERE ank_id='$anketa' AND uid='$global_user_id'");
        if (mysqli_num_rows($sql) > 0) {
            sisplet_query("DELETE FROM srv_library_anketa WHERE ank_id='$anketa' AND uid='$global_user_id'");
			sisplet_query("UPDATE srv_survey_list SET lib_usr='0' WHERE id='$anketa'");
        } else {
            $sql1 = sisplet_query("SELECT * FROM srv_library_folder WHERE uid='$global_user_id' AND tip='1' AND parent='0'");
            $row1 = mysqli_fetch_array($sql1);
            sisplet_query("INSERT INTO srv_library_anketa (ank_id, uid, folder) VALUES ('$anketa', '$global_user_id', '$row1[id]')");
			sisplet_query("UPDATE srv_survey_list SET lib_usr='1' WHERE id='$anketa'");
        }
    }
	
	private function DisplayInfo () {
        global $global_user_id, $site_url;
		
        $anketa = $_POST['anketa'];
		SurveyInfo::getInstance()->SurveyInit($anketa);

        SurveyInfo::DisplayInfoBox();
    }
    
	// ajax, ki poskrbi za vse update glelde razvrscanja mojih anket v folderje
	private function updateMySurveyFolders(){
		global $global_user_id, $site_url, $lang;

		// Prenesli smo anketo v drug folder
		if($_GET['a'] == 'survey_dropped'){
			$parent = isset($_POST['parent']) ? $_POST['parent'] : '0';
			$drag_survey = isset($_POST['drag_survey']) ? $_POST['drag_survey'] : '0';
			
			// Ce smo spustili v root folder samo pobrisemo anketo
			if($parent == '0'){
				$sql = sisplet_query("DELETE FROM srv_mysurvey_anketa WHERE ank_id='".$drag_survey."' AND usr_id='$global_user_id'");
			}
			else{
				// Razpremo parent folder
				$sql = sisplet_query("UPDATE srv_mysurvey_folder SET open='1' WHERE id='".$parent."' AND usr_id='$global_user_id'");

				$sql = sisplet_query("INSERT INTO srv_mysurvey_anketa (ank_id, usr_id, folder) VALUES ('".$drag_survey."', '".$global_user_id."', '".$parent."') ON DUPLICATE KEY UPDATE folder='".$parent."'");
			}
		}
		
		// Prenesli smo celoten folder v drug folder
		elseif($_GET['a'] == 'folder_dropped'){
			$parent = isset($_POST['parent']) ? $_POST['parent'] : '0';
			$drag_folder = isset($_POST['drag_folder']) ? $_POST['drag_folder'] : '0';
		
			// Preverimo da nismo slucajno prenesli v child folder - ne pustimo, ker drugace se zadeva porusi
			$sql = sisplet_query("SELECT * FROM srv_mysurvey_folder WHERE id='".$parent."' AND parent='".$drag_folder."' AND usr_id='$global_user_id'");
			if(mysqli_num_rows($sql) == 0){
				// Razpremo parent folder
				$sql = sisplet_query("UPDATE srv_mysurvey_folder SET open='1' WHERE id='".$parent."' AND usr_id='$global_user_id'");

				$sql = sisplet_query("UPDATE srv_mysurvey_folder SET parent='".$parent."' WHERE id='".$drag_folder."' AND usr_id='$global_user_id'");
			}
		}
		
		// prikazemo/skrijemo ankete znotraj folderja
		elseif($_GET['a'] == 'folder_toggle'){
			$folder = isset($_POST['folder']) ? $_POST['folder'] : '0';
			$open = isset($_POST['open']) ? $_POST['open'] : '0';
			
			$sql = sisplet_query("UPDATE srv_mysurvey_folder SET open='".$open."' WHERE id='".$folder."' AND usr_id='$global_user_id'");
		}
		
		// Ustvarili smo nov folder
		elseif($_GET['a'] == 'folder_create'){
			$parent = isset($_POST['parent']) ? $_POST['parent'] : '0';
			
			// Razpremo parent folder
			$sql = sisplet_query("UPDATE srv_mysurvey_folder SET open='1' WHERE id='".$parent."' AND usr_id='$global_user_id'");
			
			$sql = sisplet_query("INSERT INTO srv_mysurvey_folder (usr_id, parent, naslov) VALUES ('".$global_user_id."','".$parent."', '".$lang['srv_mySurvey_new_folder']."')");
		}
		
		// Pobrisali smo obstojec folder
		elseif($_GET['a'] == 'folder_delete'){
			$folder = isset($_POST['folder']) ? $_POST['folder'] : '0';
						
			//Pobrisemo ankete ki so bile znotraj folderja
			$sql = sisplet_query("DELETE FROM srv_mysurvey_anketa WHERE folder='".$folder."' AND usr_id='$global_user_id'");
			
			// Na koncu se pobrisemo prazen folder
			$sql = sisplet_query("DELETE FROM srv_mysurvey_folder WHERE id='".$folder."' AND usr_id='$global_user_id'");
			
			// Rekurzivno pobrisemo vse poddirektorije z anketami - TODO!!!
		}
		
		// Preimenovali smo obstojec folder
		elseif($_GET['a'] == 'folder_rename'){
			$folder = isset($_POST['folder']) ? $_POST['folder'] : '0';
			$text = isset($_POST['text']) ? $_POST['text'] : '';
			$text = strip_tags($text);
			
			$sql = sisplet_query("UPDATE srv_mysurvey_folder SET naslov='".$text."' WHERE id='".$folder."' AND usr_id='$global_user_id'");
		}
		
		// Kopiramo obstojec folder z vsemi anketami
		elseif($_GET['a'] == 'folder_copy'){
			
			$folder = isset($_POST['folder']) ? $_POST['folder'] : '0';
			if($folder > 0)
				$this->copyMySurveyFolder($folder);
		}
	}
	
	// Kopiramo obstojec folder z vsemi folderji in anketami (rekurzivno)
	private function copyMySurveyFolder($folder_id, $parent=0){
		global $global_user_id;
		
		$sql = sisplet_query("SELECT * FROM srv_mysurvey_folder WHERE id='".$folder_id."' AND usr_id='".$global_user_id."'");
		if(mysqli_num_rows($sql) == 0)
			return;
		
		$row = mysqli_fetch_array($sql);
		
		// Najprej ustvarimo kopijo folderja
		if($parent == 0)
			$sql2 = sisplet_query("INSERT INTO srv_mysurvey_folder (usr_id, parent, naslov, open) VALUES ('".$global_user_id."', '".$row['parent']."', '".$row['naslov']."_copy', '1')");
		else
			$sql2 = sisplet_query("INSERT INTO srv_mysurvey_folder (usr_id, parent, naslov, open) VALUES ('".$global_user_id."', '".$parent."', '".$row['naslov']."_copy', '1')");
		
		$new_folder_id = mysqli_insert_id($GLOBALS['connect_db']);
				
		// Loop cez ankete v folderju
		$sqlA = sisplet_query("SELECT * FROM srv_mysurvey_anketa WHERE folder='".$folder_id."'");
		while($rowA = mysqli_fetch_array($sqlA)){
			
			// Kopiramo anketo
			$sas = new SurveyAdminSettings();
			$ank_id = $sas->anketa_copy($rowA['ank_id']);
			
			// Kopirano anketo vstavimo v nov folder
			$sql2 = sisplet_query("INSERT INTO srv_mysurvey_anketa (ank_id, usr_id, folder) VALUES ('".$ank_id."', '".$global_user_id."', '".$new_folder_id."')");
		}		
		
		// Na koncu rekurzivno kopiramo se vse notranje folderje
		$sqlF = sisplet_query("SELECT id FROM srv_mysurvey_folder WHERE parent='".$folder_id."' AND usr_id='".$global_user_id."'");
		while($rowF = mysqli_fetch_array($sqlF)){
		
			$this->copyMySurveyFolder($rowF['id'], $new_folder_id);			
		}
		
		return;
	}
	
	
	/** Funkcija ki kiče podfunkcije za ajax del 
	 * 
	 */ 
	public function Ajax() {
		switch ( $_GET['a'] ) {
			case 'surveyList_knjiznica':
				$this->UpdateSystemLibrary();
				break;
			case 'surveyList_myknjiznica':
				$this->UpdateUserLibrary();
				break;
			case 'surveyList_myknjiznica_new':
				$this->UpdateUserLibraryNew();
				break;
			case 'surveyList_knjiznica_new':
				$this->UpdateSystemLibraryNew();
				break;
			case 'surveyList_display_info':
				$this->DisplayInfo();
				break;
			case 'survey_dropped':
			case 'folder_dropped':
			case 'folder_create':
			case 'folder_delete':
			case 'folder_toggle':
			case 'folder_rename':
			case 'folder_copy':
				$this->updateMySurveyFolders();
				break;
			case 'language_change':
				$this->switch_language();
				break;	
			default:
				print_r($_POST);
				print_r($_GET);
			break;
		}
	}
	
	
	/** Osveži datoteko z dodatnimi podatki anket
	 * za tiste ankete ki so bile spremenjene ali so imele kakšne vnose
	 */
	private function refreshData() {
		
		# polovimo vse obstoječe podatke v združeni datoteki
		if (count($this->surveys_ids)>0 ) {
			
			// Metaadmin updata samo svoje ankete (drugace jih je prevec in lahko zasteka)
			if(Dostop::isMetaAdmin()){
								
				# polovimo seznam metaadminovih anket
				$meta_surveys_ids = array();
				$dostopCondition = (SRV_LIST_CHECK_DOSTOP ?  " AND (insert_uid = '".$this->user_id."') AND (sa.id IN (".SurveyCopy::prepareSubquery(sisplet_query("SELECT ank_id FROM srv_dostop WHERE uid='".$this->g_uid."'"))."))" : "");
				$stringSurveyList = "SELECT id, backup, active, folder, dostop FROM srv_anketa sa WHERE sa.backup='0' AND sa.id > 0 AND sa.active >= 0 AND sa.invisible = '0' ".$dostopCondition;
				$sqlSurveyList = sisplet_query($stringSurveyList);
				while (	$rowSurveyList = mysqli_fetch_assoc($sqlSurveyList)) {
					$meta_surveys_ids[$rowSurveyList['id']] = $rowSurveyList['id'];
				}
				
				$to_update = $meta_surveys_ids;

				# poiščemmo katere ankete so OK, in jih odstranimo iz seznama anket potrebnih za update
				$stringSurveyList = "SELECT id FROM srv_survey_list WHERE id IN (".implode(',', $meta_surveys_ids).")"
				#. " AND (updated = '0' OR (updated = '1' AND TIME_TO_SEC(TIMEDIFF(NOW(),last_updated)) < ".SRV_LIST_UPDATE_TIME_LIMIT.")) AND ( last_updated IS NOT NULL)";
				. " AND updated = '0' AND last_updated IS NOT NULL";
				$sqlSurveyList = sisplet_query($stringSurveyList);
				while (	$rowSurveyList = mysqli_fetch_assoc($sqlSurveyList)) {
					if (isset($to_update[$rowSurveyList['id']])) {
						unset($to_update[$rowSurveyList['id']]);
					}
				}
			}
			else{		
				$to_update = $this->surveys_ids;
				
				# poiščemmo katere ankete so OK, in jih odstranimo iz seznama anket potrebnih za update
				$stringSurveyList = "SELECT id FROM srv_survey_list WHERE id IN (".implode(',', $this->surveys_ids).")"
				#. " AND (updated = '0' OR (updated = '1' AND TIME_TO_SEC(TIMEDIFF(NOW(),last_updated)) < ".SRV_LIST_UPDATE_TIME_LIMIT.")) AND ( last_updated IS NOT NULL)";
				. " AND updated = '0' AND last_updated IS NOT NULL";
				$sqlSurveyList = sisplet_query($stringSurveyList);
				while (	$rowSurveyList = mysqli_fetch_assoc($sqlSurveyList)) {
					if (isset($to_update[$rowSurveyList['id']])) {
						unset($to_update[$rowSurveyList['id']]);
					}
				}
			}
				 
			# če je treba kaj updejtat
			if (count($to_update) > 0) {
				$stringUpdateList = 
				  " SELECT sa.id, "

				. ' IF(ISNULL(sla1.lib_glb),0,sla1.lib_glb) AS lib_glb,'
				. ' IF(ISNULL(sla2.lib_usr),0,sla2.lib_usr) AS lib_usr,' 
				
				// Po novem ne joinamo s tabelo "users", ker je lahko query pocasen in zaklene tabelo - potem pa vse zasteka (dodano preventivno)
				//. ' sa.edit_uid, us1.name AS e_name, us1.surname AS e_surname, us1.email AS e_email,' 
				//. ' sa.insert_uid, us2.name AS i_name, us2.surname AS i_surname, us2.email AS i_email,'
				
				. " us3.vnos_time_first AS v_time_first, us3.vnos_time_last AS v_time_last," 
				. ' IF(ISNULL(us3.answers),0,us3.answers) as answers,' 
				. ' IF(ISNULL(g.variables),0,g.variables) as variables,'
				. ' IF(ISNULL(us5.approp),0,us5.approp) as approp'
				
				. ' FROM srv_anketa sa'
				
				. " LEFT OUTER JOIN ( SELECT ank_id, uid, COUNT(*) AS lib_glb FROM srv_library_anketa as sla WHERE sla.uid = '0' AND sla.ank_id IN (".implode(',', $to_update).") GROUP BY ank_id ) 
						AS sla1 ON sla1.ank_id = sa.id" 
				. " LEFT OUTER JOIN ( SELECT ank_id, uid, COUNT(*) AS lib_usr FROM srv_library_anketa as sla WHERE sla.uid = '".$this->g_uid."' AND sla.ank_id IN (".implode(',', $to_update).") GROUP BY ank_id ) 
						AS sla2 ON sla2.ank_id = sa.id"

				//. ' LEFT OUTER JOIN users AS us1 ON us1.id = sa.edit_uid' 
				//. ' LEFT OUTER JOIN users AS us2 ON us2.id = sa.insert_uid' 
				
				. ' LEFT OUTER JOIN ( SELECT us3.ank_id, COUNT(us3.ank_id) as answers, MIN( us3.time_insert ) as vnos_time_first, MAX( us3.time_insert ) as vnos_time_last, preview FROM srv_user as us3 WHERE us3.ank_id IN ('.implode(',', $to_update).') AND us3.preview = \'0\' AND us3.deleted=\'0\' GROUP BY us3.ank_id ) 
						AS us3 ON us3.ank_id = sa.id'
				
				. ' LEFT OUTER JOIN ( SELECT g.ank_id, COUNT(s.gru_id) as variables FROM srv_grupa g, srv_spremenljivka s WHERE g.id = s.gru_id AND g.ank_id IN ('.implode(',', $to_update).') GROUP BY g.ank_id ) 
						AS g ON g.ank_id = sa.id'
				//spodaj dodaj  AND us5.lurker=\'0\'
				. ' LEFT OUTER JOIN ( SELECT us5.ank_id, COUNT(us5.ank_id) as approp, preview FROM srv_user as us5 WHERE last_status IN (' . $this->appropriateStatus . ') AND us5.ank_id IN ('.implode(',', $to_update).') AND us5.preview =\'0\' AND us5.deleted=\'0\' GROUP BY us5.ank_id ) 
						AS us5 ON us5.ank_id = sa.id'
				
				. ' WHERE sa.id IN ('.implode(',', $to_update).')';
				
				$sqlUpdateList = sisplet_query($stringUpdateList);
	 			if (!$sqlUpdateList) echo mysqli_error($GLOBALS['connect_db']);
				
	    		if (mysqli_num_rows($sqlUpdateList) > 0) {
					
					// Po novem zakesiramo podatke iz tabele "users" posebej (da ne zaklene zgornji query tabele za dalj casa)
					$users = array();
					$sqlUsers = sisplet_query("SELECT sa.id AS ank_id, sa.edit_uid, us1.name AS e_name, us1.surname AS e_surname, us1.email AS e_email,
														sa.insert_uid, us2.name AS i_name, us2.surname AS i_surname, us2.email AS i_email
												FROM srv_anketa sa
												LEFT OUTER JOIN users AS us1 ON us1.id = sa.edit_uid
												LEFT OUTER JOIN users AS us2 ON us2.id = sa.insert_uid
												WHERE sa.id IN (".implode(',', $to_update).")");
					while($rowUsers = mysqli_fetch_array($sqlUsers)){
						$users[$rowUsers['ank_id']] = $rowUsers;
					}
					
	    			$values = array();
					while (	$row = mysqli_fetch_assoc($sqlUpdateList)) {
						/*$row[i_name] = mysqli_real_escape_string($GLOBALS['connect_db'], $row[i_name]);
						$row[i_surname] = mysqli_real_escape_string($GLOBALS['connect_db'], $row[i_surname]);
						$row[i_email] = mysqli_real_escape_string($GLOBALS['connect_db'], $row[i_email]);
						$row[e_name] = mysqli_real_escape_string($GLOBALS['connect_db'], $row[e_name]);
						$row[e_surname] = mysqli_real_escape_string($GLOBALS['connect_db'], $row[e_surname]);
						$row[e_email] = mysqli_real_escape_string($GLOBALS['connect_db'], $row[e_email]);*/
						
						$row['i_name'] = mysqli_real_escape_string($GLOBALS['connect_db'], $users[$row['id']]['i_name']);
						$row['i_surname'] = mysqli_real_escape_string($GLOBALS['connect_db'], $users[$row['id']]['i_surname']);
						$row['i_email'] = mysqli_real_escape_string($GLOBALS['connect_db'], $users[$row['id']]['i_email']);
						$row['e_name'] = mysqli_real_escape_string($GLOBALS['connect_db'], $users[$row['id']]['e_name']);
						$row['e_surname'] = mysqli_real_escape_string($GLOBALS['connect_db'], $users[$row['id']]['e_surname']);
						$row['e_email'] = mysqli_real_escape_string($GLOBALS['connect_db'], $users[$row['id']]['e_email']);
						 
						$values[] = "('$row[id]','$row[lib_glb]','$row[lib_usr]','$row[answers]','$row[variables]','$row[approp]','$row[i_name]','$row[i_surname]','$row[i_email]','$row[e_name]','$row[e_surname]','$row[e_email]','$row[v_time_first]','$row[v_time_last]','0', NOW())";	
					}
					
					$updateString = "INSERT INTO srv_survey_list (id, lib_glb, lib_usr, answers, variables, approp, i_name, i_surname, i_email, e_name, e_surname, e_email, a_first, a_last, updated, last_updated) "
					 ." VALUES ".implode(',', $values)." ON DUPLICATE KEY UPDATE id=VALUES(id), lib_glb=VALUES(lib_glb), lib_usr=VALUES(lib_usr), answers=VALUES(answers), variables=VALUES(variables), approp=VALUES(approp), i_name=VALUES(i_name), i_surname=VALUES(i_surname), i_email=VALUES(i_email), e_name=VALUES(e_name), e_surname=VALUES(e_surname), e_email=VALUES(e_email), a_first=VALUES(a_first), a_last=VALUES(a_last), updated='0', last_updated=NOW()";
					 
					 sisplet_query($updateString);
	
	    		}
	    		sisplet_query("COMMIT");
			}
		}

		# polovimo nastavitve uporabnika
		$this->settingsArray = $this->getSettings();
		
		
		# koliko imamo strani
		$this->max_pages = (int)$this->rec_per_page > 0 ? ceil($this->countSurveys() / $this->rec_per_page) : 1;
		
		# ce imamo direktorije, imamo manj strani zaradi anket v direktorijih
		if($this->show_folders == 1 && $this->max_pages > 1){
			$this->max_pages = ceil($this->countRootSurveys() / $this->rec_per_page);
		}
				
		# katera je trenutna stran
		if (isset($_GET['pageno']) && (int)$_GET['pageno'] > 0) {
			# izbrana stran ne more biti večja, kot pa imamo vseh strani
			$this->pageno = min((int)$_GET['pageno'], $this->max_pages );
		}	
	}
	
	function setFilter() {
		if (isset($_POST['sl_filter']) && trim($_POST['sl_filter']) != "") {
			$this->filter = trim($_POST['sl_filter']);
		}
	}
	
	public function countPhoneSurveys() {
		# če ni admin odstranimo ankete kjer je uporabnik označen samo kot anketar
		
		$stringSurveyList = "SELECT count(*) FROM srv_anketa sa WHERE sa.backup='0' AND sa.id > 0 AND sa.active >= 0 AND sa.invisible = '0' "
		.$this->getFolderCondition();
		if ($this->g_adminType != '0') {
			$stringSurveyList .= " AND sa.id IN (".SurveyCopy::prepareSubquery(sisplet_query("SELECT ank_id FROM srv_dostop AS sd WHERE sd.aktiven = '1' AND sd.uid = '$this->g_uid' AND FIND_IN_SET('phone',sd.dostop )>0 AND FIND_IN_SET('edit',sd.dostop ) = 0")).")";
		} else {
			$stringSurveyList .= " AND sa.id IN (".SurveyCopy::prepareSubquery(sisplet_query("SELECT ank_id FROM srv_dostop AS sd WHERE sd.aktiven = '1' AND FIND_IN_SET('phone',sd.dostop )>0 AND FIND_IN_SET('edit',sd.dostop ) = 0")).")";
		}
		
		$sqlSurveyList = sisplet_query($stringSurveyList);
		[$count] = mysqli_fetch_row($sqlSurveyList);
		
		return (int)$count;
	}
	
	// Preklopimo jezik
	private function switch_language(){
		global $global_user_id;
		
		$lang = $_POST['lang'];
		
		sisplet_query("UPDATE users SET lang = '$lang' WHERE id = '$global_user_id'");	
	}
}
?>