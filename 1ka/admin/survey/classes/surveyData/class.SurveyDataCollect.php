<?php

/**
 *
 * Created on 17.12.2019
 * 
 * @author: Peter Hrvatin
 * 
 * Class za ustvarjanje datotek s podatki
 *
*/

set_time_limit(2400); # 30 minut

global $site_path;

class SurveyDataCollect{


    // Osnovni podatki (id ankete, path...)
	private $folder = ''; 			// Folder kamor shranjujemo vse podatke
	private $sid = null; 			// id ankete
	private $db_table = '';			// ali se uporablja aktivna tabela
	private $survey  = null;		// podatki ankete

    
    // Errorji
	private $noErrors = true;		        // ali smo naleteli na napako, prekinemo izvajanje
    private $errors = array();	            // beležimo napake
    
    // Zacsno za logiranje in testiranje
    private $log = array();
    

    // Ostale nastavitve
	private $header_file_name = null;		// Ime header datoteke
	private $data_file_name = null;			// Ime data datoteke
	private $data_file_time = null;			// datum zadnjega userja v data datoteki
    
    private $max_anketa_time = null;		// datum zadnje spremembe v anketi
	private $max_usr_time = null;			// datum zadnje spremembe v tabelu userjev

	private $collect_all_status = 1;		// ali so zbrani vsi statusi
	private $has_test_data = false;			// ali vnosi vsebujejo testne podatke
	private $is_valid_user_limit = '';		// sql ali zbiramo vse statuse
	private $force_show_hiden_system = false;	// ali prisilimo prikaz sistemskih vprašanj (email. ime, priimek)
	private $last_update = null; 			// kdaj je bila zadnja sprememba

	private $fileStatus = FILE_STATUS_NO_DATA;	// status datoteke z podatki 1 = OK, 0 = OLD .....	


	// Pointer do headerja
	private $_HEADER = null;


    // Kesiranje respondentov
    private $_qry_users = null;			    // cache query za vprašanja
	private $_str_users = null;			    // cache string za user_id ji
    private $_cnt_all_users = 0; 		    // cache za število userjev

    // Kesiranje strani
    private $_str_groups = null;		    // cache string za strani
    private $_array_groups = null;		    // cache array za strani
    private $_cnt_groups = 0;		        // cache število za strani
    
    // Kesiranje vprasanj
    private $AllQuestionsData = null;	    // cache array z podatki vseh vprašanj
    private $AllQuestionsOrder = null;	    // cache array z vrstnim redom vseh vprašanj, upoštevajoč loope
    private $_str_questions = null; 	    // cache string za id-je vprašanj
    private $_cnt_questions = 0; 		    // cache za število vprašanj
    private $_cnt_questions_types = null;	// cache za število vprasanj po posameznih tipih (da ne klicemo funkcij po nepotrebnem)

    // Kesiranje vrednosti v vprasanjih
    private $_array_vrednosti = null;	    // cache array z vrednostmi
       
    // Kesiranje gridov
	private $_array_gridi = null;		    // cache array z gridi

	// Prikaz vrstnega reda v randomiziranih vprasanjih / blokih
    private $_array_random = null;				// cache array za random bloke in vprasanja
    private $_str_blocks = null; 	            // cache string za id-je blokov
	
	// Kesiranje loopov
	private $_array_loop_on_spr = null;		    // cache array z loopi in na katero spremenljvko se nanaša
	private $_array_loop_parent = null;		    // cache array z spremenljivkami in pripadajočimiloop idji
	private $_array_vre_on_loop = null;		    // cache array z vrednostmi v posameznem loopu
	private $_array_spr_in_loop = null;		    // cache array z id-lopi za posamezno spremenljvko
	private $_array_loop_has_spr = null;	    // cache array katere spremenljvike vsebuje posamezen loop
	private $_array_vrednosti_in_loops = null;	// cache array vseh vrednosti ki se pojavljajo v loopih
    private $_cnt_loop = 0;				        // cache za število vseh loopov
    
    // Kesiranje ostalih dodatnih nastavitev
    private $SNVariablesForSpr = null;	        // cache array spremenljivk z variablami
    private $_array_users_from_CMS = null;      // cache za e-maile uporanikov iz CMS
	private $_array_SPSS = null;	            // cache array za spss izvoz za uporabnika
    private $_array_user_grupa = null;	        // cache array casov respondentov po straneh
    private $_user_spr_answer_count = array(); 	// stejemo variable na spremenljivko da spreminjamo -3 samo za variable katerih spremenljivka nima veljavnih vrednosti
    private $sysMissingMap = array(); 	        // za osnovni mapping sistemskih missingov, -1,-2,-3,-4,-5

    // Rekodiranje
    private $_array_recode = null;			// cache array vseh rekodiranih vrednosti
	private $_array_recoded = array();		// cache array vseh že rekodiranih vrednosti


	// POINTER - Array-i do podatkov
    private $_array_data_vrednost = null;   
    private $_array_data_vrednost_cond = null;    
    private $_array_data_text = null;    
    private $_array_data_grids = null;    
    private $_array_data_check_grids = null;   
    private $_array_data_rating = null; 
    private $_array_data_text_grid = null; 
    private $_array_data_text_upload = null;  
    private $_array_data_map = null; 
    private $_array_data_heatmap = null;
	private $_array_data_heatmap_regions = null;
	private $_array_data_random = null;



    function __construct($sid = null) {
        global $site_path, $lang, $mysql_database_name;
        
        // Zacasno povecamo ram samo za veliko nijz anketo
        if($sid == '123146' && $mysql_database_name == 'www1kasi'){
            ini_set('memory_limit', '2048M');
        }

		if (is_numeric($sid) && (int)$sid > 0) {

            // Nastavimo id ankete
            $this->sid = $sid;
            
            // Nastavimo globalni folder kjer se generirajo datoteke
			$this->folder = $site_path . EXPORT_FOLDER.'/';
            
            // Nastavimo spremenljivke, ki so potrebne pri generiranju datoteke (cas zadnjega vnosa, status datoteke...)
            $this->prepareVariables();

			// Za vsak slucaj resetiramo vse pointerje
			$this->cleanup();


            // Inicializiramo razrede za dodatne nastavitve
			SurveyStatusProfiles::Init($this->sid);	
			SurveySetting::getInstance()->Init($this->sid);
		} 
		else {
            
            $SL = new SurveyLog();
            $SL->addMessage(SurveyLog::DEBUG, 'SurveyDataCollect constructor - survey id == 0!');
            $SL->write();	
            
            echo 'Napaka! Manjka ID ankete!';

            /*return false;
            exit();*/

            die();
		}
	}


    // Nastavimo spremenljivke potrebne za generiranje datoteke
    private function prepareVariables(){

        // Dobimo instanco razreda SurveyDataFile
        $sdf = SurveyDataFile::get_instance();
   
        // Nastavimo spremenljivke, ki so ze nastavljene v razredu SurveyDataFile
		$this->header_file_name = $sdf->getHeaderFileName();
		$this->data_file_name = $sdf->getDataFileName();
        $this->data_file_time = $sdf->getDataFileTime();

        $this->last_update = $sdf->getFileUpdated();
        $this->fileStatus = $sdf->getStatus();
        $this->has_test_data = $sdf->getHasTestData();
        $this->max_usr_time = $sdf->getLastResponseTime();

        $this->collect_all_status = $sdf->getCollectAllStatus();

        if ((int)$this->collect_all_status == 0)
            $this->is_valid_user_limit = " AND u.last_status IN (5,6) ";
        else
            $this->is_valid_user_limit = '';


        // Dobimo podatke o anketi
        $qry_survey = sisplet_query("SELECT *, UNIX_TIMESTAMP(edit_time) AS srv_edit_time  FROM srv_anketa WHERE id='".$this->sid."'");
        $this->survey = mysqli_fetch_assoc($qry_survey);
        
        // Aktivne tabele za podatke v bazi
        if ((int)$this->survey['db_table'] == 1)
            $this->db_table = '_active';
        else
            $this->db_table = '';
        
        // Zadnji cas editiranja ankete
        $this->max_anketa_time = (int)$this->survey['srv_edit_time'];

		// Ali prisilimo prikaz sistemskih spremenljivk
		if ($this->survey['show_email'] == 1 || SurveyInfo::getInstance()->checkSurveyModule('360_stopinj'))
			$this->force_show_hiden_system = true;
        else
			$this->force_show_hiden_system = false;
    }
   

    // Ustvarimo vse nove datoteke s podatki
    public function createFiles() {
		global $site_url, $lang, $site_path;

        // Najprej zakesiramo vse podatke
        $this->cache_data();

        // Zgeneriramo datoteko z naslovno vrstico
        $CH = (int)$this->createHeadFile();

        // Zgeneriramo datoteko s podatki
        $CD = (int)$this->createDataFile();
    }
    
    // Posodobimo datoteke ker so zastarele
    public function updateFilesIncremental() {
		global $site_url, $lang, $site_path;
        
		// Pobrišemo odvecne response
        $this->deleteUsers();
        
        // Zakesiramo vse podatke
        $this->cache_data();
        
        // Zgeneriramo datoteko z naslovno vrstico
        //$CH = (int)$this->createHeadFile();

        // Zgeneriramo datoteko s podatki
        $CD = (int)$this->createDataFile();
    }
    

    // Kreiramo datoteko z naslovno vrstico
    private function createHeadFile() {

        $result = false;

        // za vsak slučaj
        $this->_HEADER = null;     

        // Ce imamo query za vprasanja
        if ($this->_str_questions !== '') {

            // Izvedemo zbiranje podatkov v $this->_HEADER array
            $this->CollectHeaders();
                
            // Zapišemo header datoteko
            file_put_contents($this->header_file_name, serialize($this->_HEADER));
            unset($this->_HEADER);
            
            // Ce ni napak updejtamo zapis v bazi
            if ($this->noErrors) {
                
                // data time damo na 0 ker moramo datoteko s podatki zgenerirati čisto na novo.
                $updated = sisplet_query("INSERT INTO srv_data_files 
                                            (sid, head_file_time, data_file_time) VALUES ('".$this->sid."', FROM_UNIXTIME('".$this->max_anketa_time."'), '0000-00-00') 
                                            ON DUPLICATE KEY UPDATE head_file_time = FROM_UNIXTIME('".$this->max_anketa_time."'), data_file_time = '0000-00-00'");
                sisplet_query("COMMIT");
                
                if ($updated) {
                    $result = true;
                } 
                else {
                    $this->trigerError('updateHeader',  mysqli_error($GLOBALS['connect_db']));
                }
            }
        } 
        else {
            $this->trigerError('createHeaderFile',  'Not set this->survey[edit_header_time] ('.$this->sid.')');
        }	
        
		return $result;
	}

    // Kreiramo datoteko z responsi
	private function createDataFile() {
            
		$result = false;
						
        // Pobrišemo še morebitne prazne vrstice
        if (IS_WINDOWS) {
            $cmd1 = 'sed "/^$/d" '.$this->data_file_name.' > '.$this->data_file_name.'.empt && mv '.$this->data_file_name.'.empt '.$this->data_file_name;
        } 
        else {
            $cmd1 = 'sed \'/^$/d\' '.$this->data_file_name.' > '.$this->data_file_name.'.empt && mv '.$this->data_file_name.'.empt '.$this->data_file_name;
        }
        $out_command1 = shell_exec($cmd1);

        if (file_exists($this->data_file_name.'.empt')) {
            unlink($this->data_file_name.'.empt');
        }

        // preverimo ali datoteka obstaja in ali že imamo zapise da prilagodimo line seperator
        $this->new_line_seperator = null;
        
        $lines = $this->getLinesCnt();
        
        if ((int)$lines > 0) {
            $this->new_line_seperator = NEW_LINE;
        }

        // Datoteko pripravimo za dodajanje
        $file_handler = fopen($this->data_file_name, "a");

        if ($this->noErrors) {

            $_tmpCnt = 0;
        
            // v loopu dodamo podatke v data file
            if ($this->noErrors && count($this->_str_users) > 0) {

                foreach ($this->_str_users AS $c => $string_user) {

                    // Zakesiramo podatke za vse odgovore respondenta
                    $this->cache_data_respondent($string_user);

                    // Ce ni errorjev izvedemo zbiranje podatkov 
                    if ($this->noErrors) {
                        $this->CollectData($c, $file_handler);
                    }

                    $_tmpCnt++;
                }
            }
            
            // Peverimo da res ni prišlo do napak
            if ($this->noErrors) {

                // Nastavimo cas zadnjega respondenta
                $sdf = SurveyDataFile::get_instance();
                $this->max_usr_time = $sdf->setLastResponseTime();

                $updated = sisplet_query("INSERT INTO srv_data_files 
                                            (sid, data_file_time, last_update ) VALUES ('".$this->sid."', FROM_UNIXTIME('".$this->max_usr_time."'), NOW())
                                            ON DUPLICATE KEY UPDATE data_file_time = FROM_UNIXTIME('".$this->max_usr_time."'), last_update = NOW()");
                                
                if ($updated) {
                    $result = true;
                } 
                else {
                    $this->trigerError('updateDataSql', mysqli_error($GLOBALS['connect_db']));
                }
            }
        }

        // Zapremo datoteko
        if ($file_handler !== null) {
            fclose($file_handler);

            // Se enkrat izvedemo generiranje ce imamo filter za uporabnost (ker ga racunamo na podlagi ze obstojece datoteke)
            if (SurveyStatusProfiles::usabilitySettings()) {
                $SUR = new SurveyUsableResp($this->sid, $generateDataFile=true);
                
                // Resetiramo status filter (da izracunamo za vse enote)
                $SUR->setStatusFilter($status_filter='');
                
                // Izracunamo uporabnost za vsako enoto
                $usability = $SUR->calculateData();
                
                $file_handler_old = fopen($this->data_file_name,"r");
                
                $data_file_name_new = $this->folder . 'export_data_'.$this->sid.'_2.dat';
                $file_handler_new = fopen($data_file_name_new,"a");
                
                // Beremo datoteko vrstico po vrstico
                $cnt = 1;
                while (($line = fgets($file_handler_old)) !== false) {
                    
                    // dodamo vrstici na koncu uporabnost
                    $usability_status = (isset($usability['data'][$cnt]['status'])) ? $usability['data'][$cnt]['status'] : 0;
                    $line = str_replace($this->new_line_seperator, '', $line);
                    $line .= STR_DLMT . $usability_status;
                    
                    fwrite($file_handler_new,  $line . $this->new_line_seperator);
                    
                    $cnt++;
                }			
                
                fclose($file_handler_old);
                fclose($file_handler_new);
                
                // Pobrisemo staro datoteko
                unlink($file_handler_old);
                
                // Preimenujemo novo datoteko
                rename($data_file_name_new, $this->data_file_name);
            }
        }
		
		// Naredimo še cleanup pointerjev
        $this->cleanup();
        		
		return $result;
    }



    /***** ZBIRANJE PODATKOV ZA DATOTEKO *****/

    /**
	 * Vsako spremenljivko shranimo pod svojim ID-jem
	 * spremenljivka ima enega ali več gridov ( odviso od dimenzi: eno=>(radio, check, vsota...) multi=>(multigrid, multicheck, multibox)
	 * vsak grid pa lahko ima eno ali več variabel (odgovor, tekstovni odgovor... )
	 *
	 * vse skupaj shranjujemo v urejenem arrayu
	 * 	HEADERS[SPR_ID] => array( tip		=> tip variable
	 *                                variable	=> ime Variable
	 * 				  naslov	=> Naslov variable
	 *                                cnt_grids	=> koliko je gridov
	 * 				  grids[ID]	=> array(
	 * 					variables[ID] => array(variable	=> ime variable
	 * 						naslov		=> naslov variable
	 * 						other		=> ali je polje drugo
	 * 						text		=> ali je tekstovni odgovor
	 * 						spss		=> polje za spss
	 * 					)
	 * 				)
	 *			)
	 */
	public function CollectHeaders() {
		global $lang;
		global $admin_type;
        
		// v header dodomo userid
		$sequence = 1;	# vodimo zaporedno števiko polja v bazi
		$_data_sequence = 1;	# vodimo zaporedno števiko polja v bazi kjer se začnejo "resni" podatki


        // DODATNE NASTAVITVE 
        // Vsi status
		if ((int)$this->collect_all_status > 0) {
			$_HEADER['_settings']['collectAllStatus'] = '1';
        } 
        // Samo ustrezni statusi 6 in 5
        else {
			$_HEADER['_settings']['collectAllStatus'] = '0';
		}

        // Obstajajo testni podatki
		if ($this->has_test_data == true) {
			$_HEADER['_settings']['hasTestData'] = '1';
        } 
        else {
			$_HEADER['_settings']['hasTestData'] = '0';
        }
        
        // Force-amo zbiranje skritih sistemskih vprasanj
		if ($this->force_show_hiden_system == true) {
			$_HEADER['_settings']['force_show_hiden_system'] = '1';
        } 
        else {
			$_HEADER['_settings']['force_show_hiden_system'] = '0';
        }
        

		// Preštejemo normalne variable, ki niso sistemske kot je email, ime.... in se v podatkih prikazujejo normalno
        $_HEADER['_settings']['count_normal_data_variables'] = 0;
        
		// Preštejemo sistemske kot je email, ime.... in se v podatkih ne smejo prikazovat
		$_HEADER['_settings']['count_system_data_variables'] = 0;


		// user ID
		$_HEADER['uid']= array ('tip'=>'m', 'variable'=>'uid', 'naslov' =>'User ID',
								 'grids' => array(0 => Array('variables'=>array(0 => Array ('variable'=>'uid', 'naslov'=>'User ID','spss'=>'F11.0','sequence'=>$sequence))
		,'naslov'=>'uid','cnt_vars' => 1)));
		$_HEADER['uid']['sequences'] = $sequence;
		$sequence++;
		$_data_sequence++;
		
		// ustreznost  uporabnika
		$_HEADER['relevance']= array ('tip'=>'m', 'variable'=>'relevance', 'naslov' =>$lang['srv_data_relevance'],
				'grids' => array(0 => Array('variables'=>array(0 => Array ('variable'=>'relevance', 'naslov'=>$lang['srv_data_relevance'],'spss'=>'F3.0','sequence'=>$sequence))
						,'naslov'=>$lang['srv_data_relevance'],'cnt_vars' => 1)));
		$_HEADER['relevance']['sequences'] = $sequence;
		$sequence++;
		$_data_sequence++;
		
		// EMAIL VABILO - invitation
		$_HEADER['invitation']= array ('tip'=>'m', 'variable'=>'invitation', 'naslov' =>$lang['srv_data_invitation'],
				'grids' => array(0 => Array('variables'=>array(0 => Array ('variable'=>'invitation', 'naslov'=>$lang['srv_data_invitation'],'spss'=>'F3.0','sequence'=>$sequence))
						,'naslov'=>$lang['srv_data_invitation'],'cnt_vars' => 1)));
		$_HEADER['invitation']['sequences'] = $sequence;
		$sequence++;
		$_data_sequence++;
		
		// status uporabnika
		$_HEADER['status']= array ('tip'=>'m', 'variable'=>'status', 'naslov' =>$lang['srv_data_status'],
								 	'grids' => array(0 => Array('variables'=>array(0 => Array ('variable'=>'status', 'naslov'=>$lang['srv_data_status'],'spss'=>'F3.0','sequence'=>$sequence))
		,'naslov'=>$lang['srv_data_status'],'cnt_vars' => 1)));
		$_HEADER['status']['sequences'] = $sequence;
		$sequence++;
		$_data_sequence++;

		// ali je uporabnik lurker
		$_HEADER['lurker']= array ('tip'=>'m', 'variable'=>'lurker', 'naslov' =>$lang['srv_data_lurker'],
								 	'grids' => array(0 => Array('variables'=>array(0 => Array ('variable'=>'lurker', 'naslov'=>$lang['srv_data_lurker'],'spss'=>'F3.0','sequence'=>$sequence))
		,'naslov'=>$lang['srv_data_lurker'],'cnt_vars' => 1)));
		$_HEADER['lurker']['sequences'] = $sequence;
		$sequence++;
		$_data_sequence++;

		// Cas vnosa
		$_HEADER['unx_ins_date']= array ('tip'=>'m', 'variable'=>'unx_ins_date', 'naslov' =>'unx_ins_date',
								 	'grids' => array(0 => Array('variables'=>array(0 => Array ('variable'=>'unx_ins_date', 'naslov'=>'unx_ins_date','spss'=>'F20.0','sequence'=>$sequence))
		,'naslov'=>'unx_ins_date','cnt_vars' => 1)));
		$_HEADER['unx_ins_date']['sequences'] = $sequence;
		$sequence++;
		$_data_sequence++;

        // Recnum
		$_HEADER['recnum'] = array('tip'=>'m', 'variable'=>'recnum', 'naslov' =>'Record number',
				'grids' => array(0 => Array('variables'=>array(0 => Array ('variable'=>'recnum','naslov'=>'Record number','spss'=>'F5.0','sortType'=>'number','sequence'=>$sequence)),
						'naslov'=>'recnum','cnt_vars' => 1)));
		$_HEADER['recnum']['sequences'] = $sequence;
		$sequence++;
		$_data_sequence++;

		// Dodamo geslo - code
		if ($this->force_show_hiden_system == true) {
			$_HEADER['code'] = array('tip'=>'m', 'variable'=>'code', 'naslov' =>'Geslo',
					'grids' => array(0 => Array('variables'=>array(0 => Array ('variable'=>'code','naslov'=>'Geslo','spss'=>'A6','sequence'=>$sequence)),
							'naslov'=>'Geslo','cnt_vars' => 1)));
			$_HEADER['code']['sequences'] = $sequence;
			$sequence++;
			$_data_sequence++;
		}
		
		// Ce vsebuje testne podatke dodamo tudi to polje
		if ($this->has_test_data) {
			$_HEADER['testdata']= array ('tip'=>'m', 'variable'=>'testdata', 'naslov' =>$lang['srv_data_test'],
								 'grids' => array(0 => Array('variables'=>array(0 => Array ('variable'=>'testdata', 'naslov'=>$lang['srv_data_test'],'spss'=>'F3.0','sequence'=>$sequence))
			,'naslov'=>$lang['srv_data_test'],'cnt_vars' => 1)));
			$_HEADER['testdata']['sequences'] = $sequence;
			$sequence++;
			$_data_sequence++;
		}

		// date insert (Meta)
		$_HEADER['itime']= array ('tip'=>'m', 'variable'=>'itime', 'naslov' =>$lang['srv_data_date'],
								 'grids' => array(0 => 
								 		Array('variables'=>array(0 => 
								 				Array ('variable'=>'itime', 'naslov'=>$lang['srv_data_date'],'spss'=>'DATETIMEw','sortType'=>'date','sequence'=>$sequence))
								 			,'naslov'=>$lang['srv_data_date'],'cnt_vars' => 1)));
		$_HEADER['itime']['sequences'] = $sequence;
		$sequence++;
		$_data_sequence++;
		
		
		
		// s katero sekvenco se začnejo podatki
		$_HEADER['_settings']['dataSequence'] = $_data_sequence;

		// naredimo pointerje na podatke če še ne obstajajo
        $_array_vrednosti = $this->get_vrednosti();     // za vrednosti ankete
		$_array_gridi = $this->get_gridi();			    // za vrednosti ankete

				
		$cntHs = 0;
		
		// Stejemo loope za numeric vprasanje
		$num_loop_cnt = array();
		
		// dodamo vprašanja ki so v loopu
		if ($this->noErrors && count($this->AllQuestionsOrder) > 0) {
            
            foreach ($this->AllQuestionsOrder AS $_vprasanje_array) {
                
                $cntHs++;

				# dodelimo vrednosti loopa
				$rowVprasanje = $this->AllQuestionsData[$_vprasanje_array['id']];
				
				// nastavimo vrstni red ce loopamo po numericu
				$num_loop_cnt[$_vprasanje_array['id']]++;

				# spremenljivki dodamo loop_id da je konsistentno z podatki
				$rowVprasanje['spr_id'] = $rowVprasanje['spr_id'].'_'.$_vprasanje_array['loop_id'];
				$_vrednosti = $this->get_vrednosti($this->_array_loop_on_spr[$rowVprasanje['if_id']]);
				$_loop_vrednost = $this->_array_vre_on_loop[$rowVprasanje['if_id']][$_vprasanje_array['loop_id']];

				$spr_id = $rowVprasanje['spr_id'];
                $vrednostLoopSufix = '';
                
				# popravimo ime variable če smo v loopu
				if(isset($this->_array_vre_on_loop[$rowVprasanje['if_id']][$_vprasanje_array['loop_id']])) {
					
					// Posebej obravnavamo loop po numeric vprasanju - samo nastavimo suffix _1, _2...
					if($this->_array_vre_on_loop[$rowVprasanje['if_id']][$_vprasanje_array['loop_id']] == 'num_loop'){
						$rowVprasanje['variable'] = strip_tags($rowVprasanje['variable'])."_".$num_loop_cnt[$_vprasanje_array['id']];
					}
					else{
						# id spremenljivke po kateri loopamo
                        $_loop_on_spr_id = $this->_array_loop_on_spr[$rowVprasanje['if_id']];
                        
						#variabla spremenljivke po kateri loopamo
                        $_loop_on_spr_variable = $this->AllQuestionsData[$_loop_on_spr_id]['variable'];
                        
						# id variable po kateri loopamo (v okviru $_loop_on_spr_id)
                        $_loop_on_variable_id = $_vrednosti[$_loop_vrednost];
                        
						# naslov variable po keteri loopamo (v okviru $_loop_on_spr_id)
						$_vrednost_naslov = $_array_vrednosti[$_loop_on_spr_id][$_loop_on_variable_id['id']]['naslov'];
						$rowVprasanje['variable'] = strip_tags($rowVprasanje['variable'])."_".$_vrednosti[$_loop_vrednost]['variable'];
						$vrednostLoopSufix = "_".$_vrednosti[$_loop_vrednost]['variable'];
                        
                        if ($_vrednost_naslov != '' && $_loop_on_spr_variable != '' && $rowVprasanje['naslov'] != '') {
							# zamenjamo ime spremenljivke med #q1# (#q1# z naslovom trenutne variable)
							$rowVprasanje['naslov'] = str_replace("#$_loop_on_spr_variable#", "$_vrednost_naslov", $rowVprasanje['naslov']);
						}	
					}
				}

				# pomožne variable
				$tip = 	$rowVprasanje['tip'];
				$_tmp_spr_id = explode('_',$rowVprasanje['spr_id']);
				$spr_id = $_tmp_spr_id[0];
				$spr_data_id = $rowVprasanje['spr_id'];

                // Pri kalkulaciji izpisemo labelo v imenu, ce jo imamo
                $spr_naslov = ($tip == '22' && $rowVprasanje['label'] != '') ? strip_tags($rowVprasanje['label']) : strip_tags($rowVprasanje['naslov']);

                $spr_naslov_graf = strip_tags($rowVprasanje['naslov_graf']);
				$spr_edit_graf = $rowVprasanje['edit_graf'];
				$spr_wide_graf = $rowVprasanje['wide_graf'];
				$spr_antonucci = $rowVprasanje['antonucci'];
				$spr_variable = strip_tags($rowVprasanje['variable']);
				$spr_size = $rowVprasanje['size'];
				$spr_cela = $rowVprasanje['cela'];
				$grid_subtitle1 = $rowVprasanje['grid_subtitle1'];
				$grid_subtitle2 = $rowVprasanje['grid_subtitle2'];
				$spr_decimalna = $rowVprasanje['decimalna'];
				$spr_skala = $rowVprasanje['skala'];
				$spr_sistem = $rowVprasanje['sistem'];
				$spr_upload = $rowVprasanje['upload'];
				$spr_signature = $rowVprasanje['signature'];
				$spr_random = $rowVprasanje['random'];

				#!!! po novem tako ali tako število znakov za spss lovimo iz datoteke s podatki
				 
				# TODO $spss_lngth mora biti enak za vse in sicer je enak največjemu možnemu številu znakov
				# TODO zato je potrebno zdužit vse  ($spss_lngth, $spss_lngth2, $spss_lngth3)
				$spss_lngth = $this->_array_SPSS[$spr_id]['text'];
				$spss_lngth2 = $this->_array_SPSS[$spr_id]['text2'];
				$spss_lngth3 = isset($this->_array_SPSS[$spr_id]['vrednost']) && $this->_array_SPSS[$spr_id]['vrednost'] != '' ? $this->_array_SPSS[$spr_id]['vrednost'] : 0;

				$_HEADER[$spr_data_id] = array('spr_id'=>$spr_id, 'tip'=>$tip, 'variable'=>$spr_variable, 'naslov'=>$spr_naslov, 'sistem'=>$spr_sistem, 'skala'=>$spr_skala, 'naslov_graf'=>$spr_naslov_graf, 'edit_graf'=>$spr_edit_graf, 'wide_graf'=>$spr_wide_graf);
				# kontrola sistemskih skritih spremenljivk
				# kadar ne vsilimo prikaza sistemskih spremenljivk (lahko omogoči le admin)
				if ($this->force_show_hiden_system == false 
					&& $spr_sistem == '1' 
					&& in_array($spr_variable,unserialize (SYSTEM_VARIABLES)) # unserialize (SYSTEM_VARIABLES) -> definition.php = array('email','telefon','ime','priimek','naziv','drugo')
					# pri formi ali glasovanjzu prikazujemo tudi sistemske
					&& ( $this->survey['survey_type'] != 1 && $this->survey['survey_type'] != 0)) {

						$_HEADER[$spr_data_id]['hide_system'] = '1';

						# povečamo števec sistemskih variabel
						$_HEADER['_settings']['count_system_data_variables'] ++;
				} else {
					# povečamo števec normalnih
					$_HEADER['_settings']['count_normal_data_variables'] ++;
				}
				
				# če je sistemska variabla jo označimo
				if (in_array($spr_variable,unserialize (SYSTEM_VARIABLES))) {
					$_HEADER[$spr_data_id]['is_system'] = '1';
				}
				
				if ( $spr_upload == '1' || $spr_upload == '2') {
					$_HEADER[$spr_data_id]['upload'] = '1';
				}
				
				if ( $spr_signature == '1' ) {
					$_HEADER[$spr_data_id]['signature'] = '1';
				}

				if (isset($rowVprasanje['enota']) && $rowVprasanje['enota'] > 0) {
					$_HEADER[$spr_data_id]['enota'] = $rowVprasanje['enota'];
				}

				# dodoamo loop parent
				if (isset($rowVprasanje['loop_parent']) && $rowVprasanje['loop_parent'] > 0) {
					$_HEADER[$spr_data_id]['loop_parent'] = $rowVprasanje['loop_parent'];
					$_HEADER[$spr_data_id]['antonucci'] = $rowVprasanje['antonucci'];
				}

				# dodoamo parent loop id
				if (isset($_vprasanje_array['parent_loop_id']) && $_vprasanje_array['parent_loop_id'] > 0) {
					$_HEADER[$spr_data_id]['parent_loop_id'] = $_vprasanje_array['parent_loop_id'];
				}
				# Dodamo še loop id
				$_HEADER[$spr_data_id]['loop_id'] = $_vprasanje_array['loop_id'];

				$_GRIDS = array();
				$_seq_prefix = '';
				$_sequences = '';
                                
				// v odvisnosti od tipa vprašanja pohandlamo podatke
				switch ( $tip ) {

					# RADIO
					case 1:
					# DROPDOWN
					case 3:
						$cnt=0;
						# dodamo header variable
						$_GRIDS[0]['variables'][$cnt] = array('vr_id'=>null, 'naslov'=>$spr_naslov, 'variable'=>$spr_variable, 'other'=>false,'text'=>false,'spss'=>'F'.$spss_lngth3.'.0','sequence'=>$sequence);
						$_sequences .= $_seq_prefix.$sequence;
						$_seq_prefix = '_';
						$sequence++;
						$cnt++;
						$arrayVrednost = $_array_vrednosti[$spr_id];

						if (count($arrayVrednost)>0) {
							foreach ($arrayVrednost as $kid=> $vrednost) {
								# dodamo še eno polje za tekstovne odgovore drugo
								if ($vrednost['other'] == 1) {
									$_GRIDS[0]['variables'][$cnt] = array('vr_id'=>$vrednost['id'],'naslov'=>$vrednost['naslov'],
											'variable'=>$spr_variable.'_'.$vrednost['vrstni_red'].STR_OTHER_TEXT,  'other'=>true,'text'=>true,'spss'=>'A'.$spss_lngth,'sequence'=>$sequence,'naslov_graf'=>$vrednost['naslov_graf']);
									$_sequences .= $_seq_prefix.$sequence;
									$_seq_prefix = '_';
									$sequence++;
									$cnt++;
									$_GRIDS[0]['cnt_other'] += 1;
								} // end if
								#dodamo opcije (za spss)
								if ($vrednost['other'] == 0 || $vrednost['other'] == 1) {
									$_HEADER[$spr_data_id]['options'][$vrednost['variable']] = ($vrednost['naslov'] != null) ? $vrednost['naslov'] : $vrednost['variable'];
									$_HEADER[$spr_data_id]['options_graf'][$vrednost['variable']] = ($vrednost['naslov_graf'] != null) ? $vrednost['naslov_graf'] : $_HEADER[$spr_data_id]['options'][$vrednost['variable']];
								}
							} // end foreach

						} // end if
						
						$_GRIDS[0]['cnt_vars'] = $cnt;
						$_GRIDS[0]['naslov'] = $spr_variable;
						$_HEADER[$spr_data_id]['cnt_grids'] = 1;
						$_HEADER[$spr_data_id]['cnt_all'] = $cnt;
						$_HEADER[$spr_data_id]['sequences'] = $_sequences;
						
						if (isset($rowVprasanje['inline_edit'])) $_HEADER[$spr_data_id]['inline_edit'] = $rowVprasanje['inline_edit'];
					break;

					
					# CHECKBOX
					case 2:
						$arrayVrednost = $_array_vrednosti[$spr_id];
						$cnt=0;
						if (count($arrayVrednost)>0) {
							foreach ($arrayVrednost as $vrednost) {

								# dodamo header variable samo za ne -missing variable
								if ($vrednost['other'] == 0 || $vrednost['other'] == 1) {
									$_GRIDS[0]['variables'][$cnt] = array('vr_id'=>$vrednost['id'], 'naslov'=>$vrednost['naslov'], 'variable'=>$vrednost['variable'].$vrednostLoopSufix, 'other'=>false,'text'=>false,'spss'=>'F'.$spss_lngth3.'.0','sequence'=>$sequence,'naslov_graf'=>$vrednost['naslov_graf']);
									$_sequences .= $_seq_prefix.$sequence;
									$_seq_prefix = '_';
									$sequence++;
									$cnt++;
									#dodamo še header za polja drugo
									if ($vrednost['other'] == 1) {
										// dodamo v array 'variables'
										$_GRIDS[0]['variables'][$cnt] = array('vr_id'=>$vrednost['id'], 'naslov'=>$vrednost['naslov'], 'variable'=>$vrednost['variable'].$vrednostLoopSufix.STR_OTHER_TEXT, 'other'=>true,'text'=>true,'spss'=>'A'.$spss_lngth,'sequence'=>$sequence,'naslov_graf'=>$vrednost['naslov_graf']);
										$_sequences .= $_seq_prefix.$sequence;
										$_seq_prefix = '_';
										$sequence++;
										$cnt++;
										$_GRIDS[0]['cnt_other'] += 1;
									} // end if
								}
							} // end foreach
						} // end if
						
						#dodamo opcije (za spss)
						$_HEADER[$spr_data_id]['options']['0'] = '0';
						$_HEADER[$spr_data_id]['options']['1'] = '1';

						$_GRIDS[0]['cnt_vars'] = $cnt;
						$_GRIDS[0]['naslov'] = $spr_variable;
						$_HEADER[$spr_data_id]['cnt_grids'] = 1;
						$_HEADER[$spr_data_id]['cnt_all'] = $cnt;
						$_HEADER[$spr_data_id]['sequences'] = $_sequences;
					break;
						
						
					# TEXT
					case 4:
						$cnt=0;
						# dodamo header variable
						$_GRIDS[0]['variables'][$cnt] = array('vr_id'=>null, 'naslov'=>$spr_naslov, 'variable'=>$spr_variable, 'other'=>false, 'text'=>true, 'spss'=>'A'.$spss_lngth,'sequence'=>$sequence);
						$_sequences .= $_seq_prefix.$sequence;
						$_seq_prefix = '_';
						$sequence++;
						$cnt++;
						$_GRIDS[0]['cnt_vars'] = $cnt;
						$_GRIDS[0]['naslov'] = $spr_variable;
						$_HEADER[$spr_data_id]['cnt_grids'] = 1;
						$_HEADER[$spr_data_id]['cnt_all'] = $cnt;
						$_HEADER[$spr_data_id]['sequences'] = $_sequences;
					break;
						
					
					# MULTIRADIO
					case 6:				
						# ali je dvojni grid
						$double = (int)($rowVprasanje['enota'] == 3);
					
						$double_data = array();
						# Pri multigridu je logika obratna. variable predstavljajo podvprašanja, srv_grid pa odgovore
						$cntGrid = 0;
						$arrayVrednost = $_array_vrednosti[$spr_id];
						if (count($arrayVrednost)>0) {
							# če imamo dvojni grid gremo 2_skozi
							for ($i=1; $i<=$double+1;$i++) {
								// for $double
								$double_data[$i]['subtitle'] = $rowVprasanje['grid_subtitle'.$i];
								if ($double == 1) {
									$var_appendix = '_'.$i;
								} else {
									$var_appendix = '';
								}
								$part = $i;
								foreach ($arrayVrednost as $kid=> $vrednost) {
									
									// Za kombinirane tabele popravimo ime variable
									if($rowVprasanje['gru_id'] == -2){
										$arr = explode("_", $spr_variable, 2);
										$sprVar = $arr[0];
										$vrednost['variable'] = $sprVar.'_'.$vrednost['variable'];
									}
									
									# dodamo header samo za nemissing variable
									if ($vrednost['other'] == 0 || $vrednost['other'] == 1) {
										$cnt = 0;
										$_GRIDS[$cntGrid]['variables'][$cnt] = array('vr_id'=>$vrednost['id'],
																	'naslov'=>$vrednost['naslov'],
																	'naslov2'=>$vrednost['naslov2'],
																	'variable'=>$vrednost['variable'].$var_appendix.$vrednostLoopSufix, 
																	'other'=>false,
																	'text'=>false,
																	'spss'=>'F'.$spss_lngth3.'.0','sequence'=>$sequence,
																	'naslov_graf'=>$vrednost['naslov_graf']);
										$_sequences .= $_seq_prefix.$sequence;
										$_seq_prefix = '_';
										$sequence++;
										$cnt++;
										if ($vrednost['other'] == 1) {
											$_GRIDS[$cntGrid]['variables'][$cnt] = array('vr_id'=>$vrednost['id'],'naslov'=>$vrednost['naslov'], 'variable'=>$vrednost['variable'].$var_appendix.$vrednostLoopSufix.STR_OTHER_TEXT,  'other'=>true,'text'=>true,'spss'=>'A'.$spss_lngth,'sequence'=>$sequence,'naslov_graf'=>$vrednost['naslov_graf']);
											$_sequences .= $_seq_prefix.$sequence;
											$_seq_prefix = '_';
											$sequence++;
											$cnt++;
											$_GRIDS[$cntGrid]['cnt_other'] += 1;
										} // end if
										$_GRIDS[$cntGrid]['cnt_vars'] = $cnt;
										$_GRIDS[$cntGrid]['naslov'] = $vrednost['variable'].$var_appendix;
										$_GRIDS[$cntGrid]['part'] = $i;
										$_HEADER[$spr_data_id]['cnt_all'] += $cnt;
										$cntGrid++;
									}
								} // end foreach
			
							} // end for $double
						} // end if
						
						#dodamo opcije (za spss)
						$arrayGrids = $_array_gridi[$spr_id];
						if (count($arrayGrids) > 0) {
							foreach ($arrayGrids AS $kid => $grid) {
								if ($grid['other'] == 0 || $grid['other'] == 1) {
									$_HEADER[$spr_data_id]['options'][$grid['variable']] = ($grid['naslov'] != null) ? $grid['naslov'] : $grid['variable'];
									// Opcije za dodaten naslov grida pri grafu
									$_HEADER[$spr_data_id]['options_graf'][$grid['variable']] = ($grid['naslov_graf'] != null) ? $grid['naslov_graf'] : $_HEADER[$spr_data_id]['options'][$grid['variable']];
								}
							}
						}
						
						$_HEADER[$spr_data_id]['cnt_grids'] = $cntGrid;
						$_HEADER[$spr_data_id]['sequences'] = $_sequences;
						
						if ($double == 1) {
							$_HEADER[$spr_data_id]['double'] = $double_data;
						}
					break;
					
						
					# NUMBER
					case 7:

						# pri number lahko imamo dve variabli grida, če je size 2
						$cnt_v = 0;
						$arrayVrednost = $_array_vrednosti[$spr_id];
						if (count($arrayVrednost) > 0) {
							$cnt=0;
							foreach ($arrayVrednost as $kid=> $vrednost) {
								if ($vrednost['other'] == 0) {
                                    
                                    if ($spr_size > 1) {
										# če imamo več variabel
										$_variable = $vrednost['variable'].$vrednostLoopSufix;
                                    } 
                                    else { #imamo samo eno variablo
										$_variable = $spr_variable.$vrednostLoopSufix;
                                    }
                                    
                                    // Ce nimamo enote je naslov enak naslovu vprasanja
                                    if(!isset($rowVprasanje['enota']) || $rowVprasanje['enota'] == 0)
                                        $vrednost['naslov'] = $spr_naslov;

									$_GRIDS[0]['variables'][$cnt] = array('vr_id'=>$vrednost['id'], 'naslov'=>$vrednost['naslov'], 'variable'=>$_variable, 'other'=>false, 'text'=>false,'spss'=>'F'.($spr_cela+$spr_decimalna).($spr_decimalna > 0 ? ('.'.$spr_decimalna) : '.0'),'sortType'=>'number','sequence'=>$sequence,'naslov_graf'=>$vrednost['naslov_graf']);
									$_sequences .= $_seq_prefix.$sequence;
									$_seq_prefix = '_';
									$sequence++;
									$cnt_v++;
									$cnt++;
								} // end if

							} // end foreach
							$_GRIDS[0]['cnt_vars'] = $cnt_v;
							$_GRIDS[0]['naslov'] = $spr_variable;

						} // end if
						$_GRIDS[0]['enota'] = $spr_variable;
						$_HEADER[$spr_data_id]['cnt_grids'] = 1;
						$_HEADER[$spr_data_id]['cnt_all'] += $cnt_v;
						$_HEADER[$spr_data_id]['sequences'] = $_sequences;
					break;
						
						
					# DATUM
					case 8:
						$cnt=0;
		
						# dodamo header variable
						$_GRIDS[0]['variables'][$cnt] = array('vr_id'=>null, 'naslov'=>$spr_naslov, 'variable'=>$spr_variable, 'other'=>false, 'text'=>true,'spss'=>'A10','sortType'=>'date','sequence'=>$sequence);
						
						$_sequences .= $_seq_prefix.$sequence;
						$_seq_prefix = '_';
						$sequence++;
						$cnt++;
						
						$_HEADER[$spr_data_id]['cnt_grids'] = 1;
						$_GRIDS[0]['cnt_vars'] = $cnt;
						$_GRIDS[0]['naslov'] = $spr_variable;
						$_HEADER[$spr_data_id]['cnt_all'] += $cnt;
						$_HEADER[$spr_data_id]['sequences'] = $_sequences;
					break;
						
						
					# MULTICHECKBOX
					case 16:
					# MULTITEXT
					case 19:
					# MULTINUMBER
					case 20:
						// srv_variable predstavljajo podvprašanja, srv_grid pa možne pododkovore
						$_spss = ($tip == 16)
							? 'F'.$spss_lngth3.'.0'
							: (($tip == 19)
								? 'A'.$spss_lngth2
								: 'F'.($spr_cela+$spr_decimalna).($spr_decimalna > 0 ? ('.'.$spr_decimalna) : '.0'));
								
						$row = Cache::srv_spremenljivka($spr_id);
						$newParams = new enkaParameters($row['params']);
						$is_datum = $newParams->get('multigrid-datum');
						#'sortType'=>'date'
						$grid_id=0;
						
						$arrayGrids = $_array_gridi[$spr_id];
						$arrayVrednost = $_array_vrednosti[$spr_id];
						if (count($arrayVrednost) > 0) {
							foreach ($arrayVrednost as $kid=> $vrednost) {
							
								// Za kombinirane tabele popravimo ime variable
								if($rowVprasanje['gru_id'] == -2){
									$arr = explode("_", $spr_variable, 2);
									$sprVar = $arr[0];
									$vrednost['variable'] = $sprVar.'_'.$vrednost['variable'];
								}
							
								$cnt=0;
								if (count($arrayGrids) > 0) {
									foreach ($arrayGrids AS $kid => $grid) {
										if ($grid['other'] == 0 || $grid['other'] == 1) {
											$_GRIDS[$grid_id]['variables'][$cnt] = array('vr_id'=>$vrednost['id'], 'gr_id'=>$grid['id'], 'naslov'=>$grid['naslov'], 'variable'=>$vrednost['variable'].'_'.$grid['variable'].$vrednostLoopSufix, 'other'=>false, 'text'=>false,'spss'=>$_spss,'sequence'=>$sequence,'naslov_graf'=>$grid['naslov_graf']);

                                                                                        if((int)$is_datum === 1) {
												$_GRIDS[$grid_id]['variables'][$cnt]['sortType'] ='date';
											}
											$_sequences .= $_seq_prefix.$sequence;
											$_seq_prefix = '_';
											$sequence++;
											$cnt++;
										}
									} // end foreach
									if ($vrednost['other'] == 1) {
										$_GRIDS[$grid_id]['variables'][$cnt] = array('vr_id'=>$vrednost['id'],'naslov'=>$vrednost['naslov'], 'variable'=>$vrednost['variable'].$vrednostLoopSufix.STR_OTHER_TEXT,  'other'=>true,'text'=>true,'spss'=>'A'.$spss_lngth,'sequence'=>$sequence,'naslov_graf'=>$vrednost['naslov_graf']);
										$_sequences .= $_seq_prefix.$sequence;
										$_seq_prefix = '_';
										$sequence++;
										$cnt++;
										$_GRIDS[$grid_id]['cnt_other'] += 1;
									} // end if

								} // end if
								
								$_GRIDS[$grid_id]['cnt_vars'] = $cnt;
								$_GRIDS[$grid_id]['naslov'] = $vrednost['naslov'];
								$_GRIDS[$grid_id]['variable'] = $vrednost['variable'];
								$_GRIDS[$grid_id]['naslov_graf'] = $vrednost['naslov_graf'];
								$_HEADER[$spr_data_id]['cnt_all'] += $cnt;
								$grid_id++;
							} // end foreach
						} // end if
						
						if ($tip == 16) {
							#dodamo opcije (za spss)
							$_HEADER[$spr_data_id]['options']['0'] = '0';
							$_HEADER[$spr_data_id]['options']['1'] = '1';
						}

						$_HEADER[$spr_data_id]['cnt_grids'] = $grid_id;
						$_HEADER[$spr_data_id]['sequences'] = $_sequences;
					break;

					
					# RANKING
					case 17:
					# VSOTA
					case 18:
						$_spss = ($tip == 17)
						? 'F'.$spss_lngth3.'.0'
						: 'F'.($spr_cela+$spr_decimalna).($spr_decimalna > 0 ? ('.'.$spr_decimalna) : '.0');

						$cnt=0;
						$arrayVrednost = $_array_vrednosti[$spr_id];
						if (count($arrayVrednost) > 0) {
							foreach ($arrayVrednost as $kid=> $vrednost) {
								$_GRIDS[0]['variables'][$cnt] = array('vr_id'=>$vrednost['id'],'naslov'=>$vrednost['naslov'], 'variable'=>$vrednost['variable'].$vrednostLoopSufix, 'other'=>false, 'text'=>false,'spss'=>$_spss,'sequence'=>$sequence,'naslov_graf'=>$vrednost['naslov_graf']);
								$_sequences .= $_seq_prefix.$sequence;
								$_seq_prefix = '_';
								$sequence++;
								$cnt++;
								if ($tip == 17) {
									$_HEADER[$spr_data_id]['options'][$vrednost['vrstni_red']] = $vrednost['vrstni_red'];
								}
							} // end foreach
						} // end if
						$_HEADER[$spr_data_id]['cnt_grids'] = 1;
						$_GRIDS[0][	'cnt_vars'] = $cnt;
						$_GRIDS[0]['naslov'] = $spr_variable;
						$_HEADER[$spr_data_id]['cnt_all'] += $cnt;
						$_HEADER[$spr_data_id]['sequences'] = $_sequences;
					break;
						
						
					# BESEDILO*
					case 21:
						$cnt=0;
						$arrayVrednost = $_array_vrednosti[$spr_id];
						if (count($arrayVrednost) > 0) {
							foreach ($arrayVrednost as $kid=> $vrednost) {
								if ($vrednost['other'] == 0) {

									$_variable = (count($arrayVrednost) == 1)
									? $spr_variable
									: $vrednost['variable'].$vrednostLoopSufix ;
									$_naslov = (trim($vrednost['naslov']) != '' && trim($vrednost['naslov']) != $lang['srv_new_text']) ? $vrednost['naslov'] : $spr_naslov;
                                                                        $_GRIDS[0]['variables'][$cnt] = array('vr_id'=>$vrednost['id'],'naslov'=>$_naslov, 'variable'=>$_variable, 'other'=>false, 'text'=>true,'spss'=>'A'.$spss_lngth,'sequence'=>$sequence);
									$_sequences .= $_seq_prefix.$sequence;
									$_seq_prefix = '_';
									$sequence++;
									$cnt++;
								} // end if
							} // end foreach
						} // end if
						$_HEADER[$spr_data_id]['cnt_grids'] = 1;
						$_GRIDS[0]['cnt_vars'] = $cnt;
						$_GRIDS[0]['naslov'] = $spr_variable;
						$_HEADER[$spr_data_id]['cnt_all'] += $cnt;
						$_HEADER[$spr_data_id]['sequences'] = $_sequences;
					break;
						
						
					# KALKULACIJA
					case 22:
						$cnt=0;
						# dodamo header variable
						$_GRIDS[0]['variables'][$cnt] = array('vr_id'=>null, 'naslov'=>$spr_naslov, 'variable'=>$spr_variable, 'other'=>false, 'text'=>true,'spss'=>'F'.$spss_lngth.'.0','sequence'=>$sequence);
						$_sequences .= $_seq_prefix.$sequence;
						$_seq_prefix = '_';
						$sequence++;
						$cnt++;
						$_HEADER[$spr_data_id]['cnt_grids'] = 1;
						$_GRIDS[0]['cnt_vars'] = $cnt;
						$_GRIDS[0]['naslov'] = $spr_variable;
						$_HEADER[$spr_data_id]['cnt_all'] += $cnt;
						$_HEADER[$spr_data_id]['sequences'] = $_sequences;
					break;
					
					
					# KVOTA
					case 25:
						$cnt=0;
						# dodamo header variable
						$_GRIDS[0]['variables'][$cnt] = array('vr_id'=>null, 'naslov'=>$spr_naslov, 'variable'=>$spr_variable, 'other'=>false, 'text'=>true,'spss'=>'F'.$spss_lngth.'.0','sequence'=>$sequence);
						$_sequences .= $_seq_prefix.$sequence;
						$_seq_prefix = '_';
						$sequence++;
						$cnt++;
						$_HEADER[$spr_data_id]['cnt_grids'] = 1;
						$_GRIDS[0]['cnt_vars'] = $cnt;
						$_GRIDS[0]['naslov'] = $spr_variable;
						$_HEADER[$spr_data_id]['cnt_all'] += $cnt;
						$_HEADER[$spr_data_id]['sequences'] = $_sequences;
					break;

					
					# SN - IMENA
					case 9:
						$cnt=0;
						$arrayVrednost = $this->SNVariablesForSpr[$_vprasanje_array['id']];
						if (count($arrayVrednost) > 0) {
							foreach ($arrayVrednost as $kid=> $vrednost) {
								$_GRIDS[0]['variables'][$cnt] = array('vr_id'=>$vrednost,'naslov'=>($spr_variable.'_'.($cnt+1)), 'variable'=>($spr_variable.'_'.($cnt+1)), 'other'=>false, 'text'=>true,'spss'=>'A'.$spss_lngth,'sequence'=>$sequence);
								$_sequences .= $_seq_prefix.$sequence;
								$_seq_prefix = '_';
								$sequence++;
								$cnt++;
							} // end foreach
						} // end if
						$_HEADER[$spr_data_id]['cnt_grids'] = 1;
						$_GRIDS[0]['cnt_vars'] = $cnt;
						$_GRIDS[0]['naslov'] = $spr_variable;
						$_HEADER[$spr_data_id]['cnt_all'] += $cnt;
						$_HEADER[$spr_data_id]['sequences'] = $_sequences;
					break;
                            
							
                                        # Lokacija
					case 26:					
						$row = Cache::srv_spremenljivka($spr_id);
						
						if($row['enota'] == 3){
							$cnt=0;
							$arrayVrednost = $_array_vrednosti[$spr_id];
							if (count($arrayVrednost) > 0) {
								foreach ($arrayVrednost as $kid=> $vrednost) {
									if ($vrednost['other'] == 0) {

										$_variable = (count($arrayVrednost) == 1) ? $spr_variable
										: $vrednost['variable'].$vrednostLoopSufix ;
										$_naslov = (trim($vrednost['naslov']) != '' && trim($vrednost['naslov']) != $lang['srv_new_text']) ? $vrednost['naslov'] : $_variable;
										$_GRIDS[0]['variables'][$cnt] = array('vr_id'=>$vrednost['id'],'naslov'=>$_naslov, 'variable'=>$_variable, 'other'=>false, 'text'=>true,'spss'=>'A'.$spss_lngth,'sequence'=>$sequence);
                                                                                $_sequences .= $_seq_prefix.$sequence;
										$_seq_prefix = '_';
										$sequence++;
										$cnt++;
									} // end if
								} // end foreach
							} // end if
							$_HEADER[$spr_data_id]['cnt_grids'] = 1;
							$_GRIDS[0]['cnt_vars'] = $cnt;
							$_GRIDS[0]['naslov'] = $spr_variable;
							$_HEADER[$spr_data_id]['cnt_all'] += $cnt;
							$_HEADER[$spr_data_id]['sequences'] = $_sequences;
						}
						else{
							$newParams = new enkaParameters($row['params']);
							$is_podvprasanje = $newParams->get('marker_podvprasanje') == 1;
							$multi_input_type = $newParams->get('multi_input_type');
							if($multi_input_type == 'marker'){
								$arrayNaslovov = ($is_podvprasanje) ? 
										array(array('naslov' => $lang['srv_data_column_naslov_map'], 'variable' => $spr_variable.'a'), 
                                                                                    array('naslov' => $lang['srv_data_column_vrednost_map'], 'variable' => $spr_variable.'b'),
                                                                                    array('naslov' => $lang['srv_data_column_koordinate_map'], 'variable' => $spr_variable.'c'))
										: array(array('naslov' => $lang['srv_data_column_naslov_map'], 'variable' => $spr_variable.'a'), 
                                                                                    array('naslov' => $lang['srv_data_column_koordinate_map'], 'variable' => $spr_variable.'b'));
							}
							else
								$arrayNaslovov = array(array('naslov' => $lang['srv_data_column_koordinate_map'], 'variable' => $spr_variable.'a'));

							//kot grid
							$cnt=0;
							# dodamo header variable
							foreach($arrayNaslovov as $naslo){
								$_GRIDS[0]['variables'][$cnt] = array('vr_id'=>null, 'naslov'=>$naslo['naslov'], 'variable'=>$naslo['variable'], 'other'=>false, 'text'=>true, 'spss'=>'A'.$spss_lngth,'sequence'=>$sequence);
                                                                $_sequences .= $_seq_prefix.$sequence;
								$_seq_prefix = '_';
								$sequence++;

								$cnt++;
							}
							//$cnt++;
							$_GRIDS[0]['cnt_vars'] = count($arrayNaslovov);
							$_GRIDS[0]['naslov'] = $spr_variable;
							$_HEADER[$spr_data_id]['cnt_grids'] = count($arrayNaslovov);
							$_HEADER[$spr_data_id]['cnt_all'] = count($arrayNaslovov);
							$_HEADER[$spr_data_id]['sequences'] = $_sequences;
						}
					break;
					
					
					# HEATMAP
					case 27:
						$arrayNaslovov = array($lang['srv_data_column_koordinate_map']);
						//kot grid
						$cnt=0;
						# dodamo header variable
						foreach($arrayNaslovov as $naslo){
							$_GRIDS[0]['variables'][$cnt] = array('vr_id'=>null, 'naslov'=>$naslo, 'variable'=>$spr_variable, 'other'=>false, 'text'=>true, 'spss'=>'A'.$spss_lngth,'sequence'=>$sequence);
							$_sequences .= $_seq_prefix.$sequence;
							$_seq_prefix = '_';
							$sequence++;							
							$cnt++;
						}
						//checkbox vrednosti za imena obmocij
						$arrayVrednost = $_array_vrednosti[$spr_id];
						if (count($arrayVrednost)>0) {
							foreach ($arrayVrednost as $vrednost) {

								# dodamo header variable samo za ne -missing variable
								if ($vrednost['other'] == 0 || $vrednost['other'] == 1) {
									$_GRIDS[0]['variables'][$cnt] = array('vr_id'=>$vrednost['id'], 'naslov'=>$vrednost['naslov'], 'variable'=>$vrednost['variable'].$vrednostLoopSufix, 'other'=>false,'text'=>false,'spss'=>'F'.$spss_lngth3.'.0','sequence'=>$sequence,'naslov_graf'=>$vrednost['naslov_graf']);
									$_sequences .= $_seq_prefix.$sequence;
									$_seq_prefix = '_';
									$sequence++;
									$cnt++;
									#dodamo še header za polja drugo
								}
							} // end foreach
						} // end if
						//checkbox vrednosti za imena obmocij - konec
												#dodamo opcije (za spss)
						$_HEADER[$spr_data_id]['options']['0'] = '0';
						$_HEADER[$spr_data_id]['options']['1'] = '1';
						
						
						$_GRIDS[0]['cnt_vars'] = $cnt;	//stevilo stolpcev pod enim vprasanjem
						$_GRIDS[0]['naslov'] = $spr_variable;
						$_HEADER[$spr_data_id]['cnt_grids'] = 1;
						$_HEADER[$spr_data_id]['cnt_all'] = $cnt;	//koliko stolpcev mora pokriti naslov tega vprasanja
						$_HEADER[$spr_data_id]['sequences'] = $_sequences;
						
					break;
				}

				$_HEADER[$spr_data_id]['grids'] = $_GRIDS;
			} // end foreach ($this->AllQuestionsOrder AS $_vprasanje_array) {
		} // end if (count($this->AllQuestionsOrder) > 0)


		// DODAMO SPECIAL META         
        // s katero sekvenco se začnejo meta podatki
        $_HEADER['_settings']['metaSequence'] = $sequence;
    
        $_HEADER['meta'] = array('tip'=>'sm', 'variable'=>'smeta', 'naslov' =>$lang['srv_displaydata_meta'],'cnt_all'=>2);
        
        // Datum insert, datum edit, datume in čase za posamezno stran
        $_g_cnt = 0;
        $_tmp_seq = $sequence;

        // Na zacetku prikazemo randomizacijo ce je prisotna pri kaksnem vprasanju ali bloku
        foreach ($this->_array_random as $random_parent) {

            if($random_parent['type'] == 'spr'){
                $_HEADER['meta']['grids'][$_g_cnt] = Array('naslov'=>'Random '.$random_parent['variable']);	
                $_HEADER['meta']['grids'][$_g_cnt]['variables'][0] = Array ('variable'=>'random_'.$random_parent['id'],'naslov'=>$random_parent['variable'],'sequence'=>$sequence);
                $_g_cnt++;
                $sequence++;
            }
            elseif($random_parent['type'] == 'blok_spr'){
                $_HEADER['meta']['grids'][$_g_cnt] = Array('naslov'=>'Random B'.$random_parent['number']);
                $_HEADER['meta']['grids'][$_g_cnt]['variables'][0] = Array ('variable'=>'random_'.$random_parent['id'],'naslov'=>'B'.$random_parent['number'],'sequence'=>$sequence);
                $_g_cnt++;
                $sequence++;
            }
            elseif($random_parent['type'] == 'blok_blok'){
                $_HEADER['meta']['grids'][$_g_cnt] = Array('naslov'=>'Random B'.$random_parent['number']);
                $_HEADER['meta']['grids'][$_g_cnt]['variables'][0] = Array ('variable'=>'random_'.$random_parent['id'],'naslov'=>'B'.$random_parent['number'],'sequence'=>$sequence);
                $_g_cnt++;
                $sequence++;
            }	
        }

        // če mamo da prepozna uporabnika iz sispleta
        if ((int)$this->survey['user_from_cms'] > 0) {
            $_HEADER['meta']['grids'][$_g_cnt] = Array('naslov'=>'E-mail iz CMS');
            $_HEADER['meta']['grids'][$_g_cnt]['variables'][0] = Array ('variable'=>'usr_from_cms','naslov'=>'E-mail iz CMS','spss'=>'A256','sequence'=>$sequence);
            $sequence++;
            $_g_cnt ++;
        }

        // datum
        $_HEADER['meta']['grids'][$_g_cnt] = Array('naslov'=>$lang['date']);
        
        // time insert
        $_HEADER['meta']['grids'][$_g_cnt]['variables'][0] = Array ('variable'=>'t_insert','naslov'=>$lang['date_insert'],'spss'=>'DATETIMEw','sortType'=>'date','sequence'=>$sequence);
        $sequence++;

        // time header
        $_HEADER['meta']['grids'][$_g_cnt]['variables'][1] = Array ('variable'=>'t_edit','naslov'=>$lang['date_edit'],'spss'=>'DATETIMEw','sortType'=>'date','sequence'=>$sequence);
        $sequence++;

        // dodamo št. variabel na grupo
        $_HEADER['meta']['grids'][$_g_cnt]['cnt_vars'] = 2;
        $_g_cnt ++;

        # datumi in časi po posameznih straneh
        # zaloopamo skozi strani

        // zaloopamo skozi strani
        $page = 1;
        foreach ($this->_array_groups as $gid => $grupa) {

            $_HEADER['meta']['grids'][$_g_cnt] = Array('naslov'=>$lang['page'].' '.$page);
            
            # date on page
            $_HEADER['meta']['grids'][$_g_cnt]['variables'][0] = Array ('variable'=>'date_'.$page,'naslov'=>'datum_'.$page,'spss'=>'DATETIMEw','sortType'=>'date','sequence'=>$sequence);
            $sequence++;
            
            $_HEADER['meta']['grids'][$_g_cnt]['cnt_vars'] = 1;
            
            $page++;
            $_g_cnt++;
        }
        
        // IP
        $ip = SurveySetting::getInstance()->getSurveyMiscSetting('survey_ip');
        $ip_show = SurveySetting::getInstance()->getSurveyMiscSetting('survey_show_ip');
        if($ip==0 && $ip_show==1 && ($admin_type == 0 || $admin_type == 1)){
            $_HEADER['meta']['grids'][$_g_cnt] = Array('naslov'=>'IP',
                                                        'variables' => array( 0 => Array ('variable'=>'IP','naslov'=>'IP','spss'=>'A32','sequence'=>$sequence)));
            $sequence++; $_g_cnt++;
        }

        // Browser
        $_HEADER['meta']['grids'][$_g_cnt] = Array('naslov'=>$lang['browser'],
                                                    'variables' => array( 0 => Array ('variable'=>'Browser','naslov'=>$lang['browser'],'spss'=>'A256','sequence'=>$sequence)));
        $sequence++; $_g_cnt++;
        
        // Browser version
        $_HEADER['meta']['grids'][$_g_cnt] = Array('naslov'=>$lang['browser_version'],
                                                    'variables' => array( 0 => Array ('variable'=>'BrowserVersion','naslov'=>$lang['browser_version'],'spss'=>'A256','sequence'=>$sequence)));
        $sequence++; $_g_cnt++;
        
        // OS
        $_HEADER['meta']['grids'][$_g_cnt] = Array('naslov'=>'OS',
                                                    'variables' => array( 0 => Array ('variable'=>'OS','naslov'=>$lang['srv_para_graph_os'],'spss'=>'A256','sequence'=>$sequence)));
        $sequence++; $_g_cnt++;
        
        // Device
        $_HEADER['meta']['grids'][$_g_cnt] = Array('naslov'=>$lang['srv_para_graph_device'],
                                                    'variables' => array( 0 => Array ('variable'=>'Device','naslov'=>$lang['srv_para_graph_device'],'spss'=>'A256','sequence'=>$sequence)));
        $sequence++; $_g_cnt++;

        // Referer
        $_HEADER['meta']['grids'][$_g_cnt] = Array('naslov'=>'Referer',
                                                    'variables' => array( 0 => Array ('variable'=>'Referer','naslov'=>'Referer','spss'=>'A256','sequence'=>$sequence)));
        $sequence++; $_g_cnt++;

        // unsubscribed
        $_HEADER['meta']['grids'][$_g_cnt] = Array('naslov'=>'Unsubscribed',
                                                    'variables' => array( 0 => Array ('variable'=>'Unsubscribed','naslov'=>'Unsubscribed','spss'=>'A2','sequence'=>$sequence)));
        $sequence++; $_g_cnt++;

        // jezik - language
        $_HEADER['meta']['grids'][$_g_cnt] = Array('naslov'=>$lang['lang'],
                                                    'variables' => array( 0 => Array ('variable'=>'Language','naslov'=>$lang['lang'],'spss'=>'A256','sequence'=>$sequence)));
        $sequence++; $_g_cnt++;
        
        // dodamo št. variabel za celotno skupino
        $_HEADER['meta']['cnt_all'] = $sequence - $_tmp_seq;
        
        
		// USABILITY - ce vsebuje nastavitev da filtriramo po uporabnosti dodamo tudi to polje
		if (SurveyStatusProfiles::usabilitySettings()) {		
			$_HEADER['usability'] = Array('naslov'=>$lang['srv_usableResp_usability'],
											'variables' => array( 0 => Array ('variable'=>'Usability','naslov'=>$lang['srv_usableResp_usability'],'spss'=>'A2','sequence'=>$sequence)));
											
			$sequence++;
		}
		
		$this->_HEADER = $_HEADER;
	}

	// Zbiramo podatke
	public function CollectData($c, $file_handler) {
		global $site_path, $site_url, $lang, $admin_type;

		# osvežimo podatke
		if (mysqli_num_rows($this->_qry_users[$c]) > 0) {
			mysqli_data_seek($this->_qry_users[$c], 0);
        }
        
		$_tmpCnt = $c * MAX_USER_PER_LOOP;

		$_dataLine = "";

		// Dobimo vse jezike za katere obstaja jezikovna datoteka
        include_once($site_path.'lang/jeziki.php');
		$jeziki = $lang_all_global['ime'];
		// lang od 0 je privzet
		$jeziki[0] = $lang['language'];
        $jeziki[$lang['id']] = $lang['language'];

		if ($this->noErrors) {

			while ($rowUser = mysqli_fetch_assoc($this->_qry_users[$c])) {
                
                #sleep(1);
				$_tmpCnt++;
				
				$uid = $rowUser['usr_id'];
				# dodamo usr id k podatkom
				$_dataLine .= $rowUser['usr_id'];
				
				# dodamo ustreznost k podatkom - relevance
				$_dataLine .= STR_DLMT. (($rowUser['status'] == 5 || $rowUser['status'] == 6) && $rowUser['lurker'] == 0 ? '1' : '0');
				
				# dodamo email (invitation)k podatkom - če je bilo poslano z emailom ali je uporabnik ročno vnesel email
				$_dataLine .= STR_DLMT. ((int)$rowUser['inv_res_id'] > 0
						# uporabnik je bil dodan z email vabilom
						? ((int)$rowUser['status'] == 1 || (int)$rowUser['status'] >= 3
								# email je bil odposlan
								? '1'
								# email ni bil odposlan ali je bila napaka
								: '2')
				# uporabnik ni bil dodan z email vabilom
				: '0');
					
				# dodamo status k podatkom
				$_dataLine .= STR_DLMT.$rowUser['status'];
				
				# dodamo lurkerje
				$_dataLine .= STR_DLMT.$rowUser['lurker'];
					
				#dodamo unx_ins_date
				$_dataLine .= STR_DLMT.max($rowUser['unx_ins_date'],$rowUser['unx_edt_date']);
	
				#dodamo recnum
				$_dataLine .= STR_DLMT.$rowUser['recnum'];
	
				#dodamo geslo
				if ($this->force_show_hiden_system == true) {
					$_dataLine .= STR_DLMT.$rowUser['pass'];
				}
				
				# če vsebuje testne podatke dodamo tudi kolono z tem statusom
				if ($this->has_test_data) {
					$_dataLine .= STR_DLMT.(int)$rowUser['testdata'];
				}
	
				# dodamo insert time k podatkom
				$_dataLine .= STR_DLMT.date("d.m.Y",strtotime($rowUser['time_insert']));
				
				#dodamo uporabnikove podatke
				# zloopamo skozi vprašanja
				
				# dodamo vprašanja ki so v loopu
				$userAnswerLine = '';
				
				#resetiramo
				$this->_user_spr_answer_count = array('cnt'=>0, 'last'=>0, 'spremenljivke'=>array());
				$pages = array();
				if ($this->noErrors && count($this->AllQuestionsOrder) > 0) {
					foreach ($this->AllQuestionsOrder AS $_vprasanje_array) {
                        
                        $rowVprasanje = $this->AllQuestionsData[$_vprasanje_array['id']];
                        
                        # spremenljivki dodamo loop_id da je konsistentno z podatki
						$rowVprasanje['spr_id'] = $rowVprasanje['spr_id'].'_'.$_vprasanje_array['loop_id'];
						if (isset($this->_array_loop_on_spr[$rowVprasanje['if_id']])) {
							$_vrednosti = $this->get_vrednosti($this->_array_loop_on_spr[$rowVprasanje['if_id']]);
                        }
                        
						if(isset($this->_array_vre_on_loop[$rowVprasanje['if_id']][$_vprasanje_array['loop_id']])) {
							$_loop_vrednost = $this->_array_vre_on_loop[$rowVprasanje['if_id']][$_vprasanje_array['loop_id']];
                        }
                        
						# popravimo ime variable če smo v loopu
						if(isset($this->_array_vre_on_loop[$rowVprasanje['if_id']][$_vprasanje_array['loop_id']])) {
							$rowVprasanje['variable'] = strip_tags($rowVprasanje['variable'])."_".$_vrednosti[$_loop_vrednost]['variable'];
						}
	
						# pomožne variable
						$tip = 	$rowVprasanje['tip'];
						$_tmp_spr_id = explode('_',$rowVprasanje['spr_id']);
						$spr_id = $_tmp_spr_id[0];
						$spr_data_id = $rowVprasanje['spr_id'];
						$spr_naslov = strip_tags($rowVprasanje['naslov']);
						$spr_variable = strip_tags($rowVprasanje['variable']);
						$spr_size = $rowVprasanje['size'];
						$spr_cela = $rowVprasanje['cela'];
						$grid_subtitle1 = $rowVprasanje['grid_subtitle1'];
						$grid_subtitle2 = $rowVprasanje['grid_subtitle2'];
						$spr_decimalna = $rowVprasanje['decimalna'];
						$spr_skala = $rowVprasanje['skala'];
						$spr_sistem = $rowVprasanje['sistem'];
						$spr_page = $rowVprasanje['gru_id'];
						$spr_upload = $rowVprasanje['upload'];
						$spr_signature = $rowVprasanje['signature'];
						$spr_visible = $rowVprasanje['visible'];
						$spr_dostop = $rowVprasanje['dostop'];
						$pages[$spr_page] = true;
						
						# ponastavimo katere vrednosti se beležijo kot neodgovori na spremenljivko
						# ali je to -3 ali -1. Če uporabnik še ni bil na tej strani je -3 če ne je -1
						# ali pa če gre za email vabila dodamo status -3
						if ((($rowUser['status'] == 6 || $rowUser['status'] == 5  ) && $rowUser['lurker'] != 1) )  {			
							// Ce je skrito je -2 (-2d da ga pobrisemo z -3 ce je potrebno)
							if($spr_visible == 0 || !(($admin_type <= $spr_dostop && $admin_type>=0) || ($admin_type==-1 && $spr_dostop==4))){
								$VALUE_FOR_MISSING = '-2d';
							}
							else{							
								# če so veljavni odgovori damo -1 ali -3
								# na -3 popravimo na okncu
								#$VALUE_FOR_MISSING = isset($this->_array_user_grupa[$uid][$spr_page]) ? '-1' : '-1';
								$VALUE_FOR_MISSING = '-1';
							}
						} 
						else if ( $rowUser['status'] == 0 || $rowUser['status'] == 1 || $rowUser['status'] == 2 ) {
							# če je email vabilo
							#$VALUE_FOR_MISSING = '-3';
							$VALUE_FOR_MISSING = '-5';
						} 
						else {
							# to so lurkerji
							# če imamo neveljavne odgovore damo -5
							$VALUE_FOR_MISSING = '-5';
						}
						# array z vrednosmi rabimo za podatke in za polja drugo
						if (isset($this->_array_vrednosti[$spr_id])) {
							$spr_vrednosti = $this->_array_vrednosti[$spr_id];
						}
						#gridi
						if (isset($this->_array_gridi[$spr_id])) {
							$spr_gridi = $this->_array_gridi[$spr_id];
						}

						#ODGOVORI
						#tekstovno odgovori
						$spr_data_text = $this->get_array_data_text($uid,$spr_data_id);
						unset($this->_array_data_text[$uid][$spr_data_id]);
						# vrednostni odgovori
						$spr_data_vred = $this->get_array_data_vrednost($uid,$spr_data_id);
						unset($this->_array_data_vrednost[$uid][$spr_data_id]);
						# preslkočeni vrednostni odgovori
						$spr_data_vrednost_cond = $this->get_array_data_vrednost_cond($uid,$spr_data_id);
						unset($this->_array_data_vrednost_cond[$uid][$spr_data_id]);
						# grid odgovori
						$spr_data_grid = $this->get_array_data_grids($uid,$spr_data_id);
						unset($this->_array_data_grids[$uid][$spr_data_id]);
						# grid odgovori za checkbox
						$spr_data_grid_check = $this->get_array_data_check_grids($uid,$spr_data_id);
						unset($this->_array_data_check_grids[$uid][$spr_data_id]);
						# ranking odgovori
						$spr_data_ranking = $this->get_array_data_rating($uid,$spr_data_id);
						unset($this->_array_data_rating[$uid][$spr_data_id]);
						# textgrid odgovori
						$spr_data_grid_text = $this->get_array_data_text_grid($uid,$spr_data_id);
						unset($this->_array_data_text_grid[$uid][$spr_data_id]);
                        # lokacijski odgovori
						$spr_data_map = $this->get_array_data_map($uid,$spr_data_id);
						unset($this->_array_data_map[$uid][$spr_data_id]);
						# heatmap odgovori
						$spr_data_heatmap = $this->get_array_data_heatmap($uid,$spr_data_id);
						unset($this->_array_data_heatmap[$uid][$spr_data_id]);
						$spr_data_heatmap_regions = $this->get_array_data_heatmap_regions($uid,$spr_data_id);
						unset($this->_array_data_heatmap_regions[$uid][$spr_data_id]);

                        
                        // v odvisnosti od tipa vprašanja pohandlamo podatke
						switch ( $tip ) {
							
							# RADIO BUTTON in DROPDOWN - SELECT
							case 1:
							case 3:
	
                                $answer = null;
                                $_vred = null;

                                if (is_array($spr_data_vred)) {
                                    $key = key($spr_data_vred);
                                    
                                    if (isset($spr_data_vred[$key])) {
                                        $_vred = $spr_data_vred[$key];
                                    }
                                } 
                                else {
                                    $_vred = $VALUE_FOR_MISSING;
                                }

                                if ($_vred > 0) {
                                    $answer = $spr_vrednosti[$_vred]['variable'];
                                } 
                                else {
                                    $answer = $_vred;
                                }

                                if ( $answer == '' || $answer == null) {
                                    $answer = $VALUE_FOR_MISSING;
                                }
        
                                # dodamo podatek
                                $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answer,$uid);
                                
                                // poiščemo polja drugo
                                if (count($spr_vrednosti) > 0){

                                    foreach ($spr_vrednosti AS $vid => $vrednost) {
                                        
                                        if ($vrednost['other'] == 1) {

                                            if ($answer == -1 || $answer == -2 || $answer == -3 || $answer == -4 || $answer == -96 || $answer == -97 || $answer == -98 || $answer == -99 ) {
                                                $answerOther = $answer;
                                            }
                                            else {

                                                if (isset($spr_data_text[$vid]['text'])) {
                                                    $answerOther = ($spr_data_text[$vid]['text'] == "" || $spr_data_text[$vid]['text'] == null) ? $VALUE_FOR_MISSING : $spr_data_text[$vid]['text'];
                                                }
                                                elseif($spr_vrednosti[$vid]['variable'] != $answer){
                                                    // Ce je bil -1 texta ni mogel vnesti in mora bit -2
                                                    if($VALUE_FOR_MISSING == -1)
                                                        $answerOther = '-2d';
                                                    else
                                                        $answerOther = $VALUE_FOR_MISSING;
                                                }
                                                else {
                                                    $answerOther = $VALUE_FOR_MISSING;
                                                }
                                            }

                                            // dodamo podatek
                                            $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answerOther,$uid);         
                                        }
                                    }
                                }

                                break;
    
                                
							# CHECKBOX
							case 2:
								$answer = null;
								$_tmp_answers = array();

								$_anything_set = false;
								$_has_missing = NULL;
								
								# najprej dodelimo odgovore posameznim vrednostim če obstatajo
								if (count($spr_vrednosti) > 0){
                                    foreach ($spr_vrednosti AS $vid => $vrednost) {
                                    
                                        if (isset($spr_data_vred[$vid]) && $_has_missing == NULL) {
                                            
                                            // če je spr_data_vrednost obstaja je checkbox obkljukan
                                            // preverimo ali imamo missing
                                            if ($vrednost['other'] != 0 && $vrednost['other'] != 1 ) {
                                                # odgovor je missing, vse vrednosti nastavimo na ta missing, zato pobrišemo morebitne že dodane odgovore
                                                unset($_tmp_answers);
                                                unset($spr_data_vred[$vid]);

                                                $_has_missing = $spr_vrednosti[$vid]['variable'];
                                            } 
                                            else {
                                                $_tmp_answers[$vid] = 1;
                                                unset($spr_data_vred[$vid]);
                                                $_anything_set = true;
                                            }
                                        } 
                                        else if (isset($spr_data_vrednost_cond[$vid])) {
                                            $_tmp_answers[$vid] = $spr_data_vrednost_cond[$vid];
                                        }
                                    }
								}
								
								// če ni nič nastavljeno, je lahko samo -1, -2, -3,-4
								$missing_answers = 0;
								# preverimo ali so ostale še kakšne vrednosti, potem so najbrž missingi
								#najprej preverimo missing na variabli
								if (is_array($spr_data_vred) && count($spr_data_vred) > 0 ) {
									unset($_tmp_answers);
									$_has_missing = end($spr_data_vred);
									$missing_answers = $_has_missing;
									$_anything_set = false;
								}
								
								if ($_anything_set == false || $_has_missing != null) {
									if ($_has_missing != null) {
										# immamo misssing value
										$missing_answers = $_has_missing;
									} else if (is_array($spr_data_vred) && count($spr_data_vred) > 0 ) {
										$missing_answers = end($spr_data_vred); # lahko da je -2, -4
									} else {
										// lahko je -1, -3
										$missing_answers = $VALUE_FOR_MISSING;
									}
								}
		
								# še enkrat zloopamo skozi vrednosti in dodelimo odgovore, tudi za polja drugo
								if (count($spr_vrednosti) > 0) {
									if ($missing_answers == 0 ) {
										$missing_answers = $VALUE_FOR_MISSING;
									}
									foreach ($spr_vrednosti AS $vid => $vrednost) {
										# dodamo samo variable ki niso missing
										if ($vrednost['other'] == 0 || $vrednost['other'] == 1) {
											if (isset($_tmp_answers[$vid])) {
												$answer = $_tmp_answers[$vid];
											} else if ($_anything_set == true){
												#pri checkboxu je lahko tudi 0
												$answer = 0;
											} else {
												$answer = $missing_answers;
											}
											
											# dodamo odgovor
											$userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answer,$uid);
											
											#dodamo še polje drugo
											if ($vrednost['other'] == 1) {
										
												#ce smo meli checkboc obkljukan
												if (isset($_tmp_answers[$vid])) {
													$answerOther = ($spr_data_text[$vid]['text'] == "" || $spr_data_text[$vid]['text'] == null) ? $VALUE_FOR_MISSING : $spr_data_text[$vid]['text'];
												} else if ($missing_answers !== null) {
													// Ce je bil 0 texta ni mogel vnesti in mora bit -2
													if($VALUE_FOR_MISSING == -1)
														$answerOther = '-2d';
													else
														$answerOther = $missing_answers;
												} else if ($answer == 0) {
													$answerOther = $VALUE_FOR_MISSING;
												} else {
													$answerOther = $answer;
												}
												# dodamo odgvor
												$userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answerOther,$uid);
												
											}
										}
									}
								}
		
							    break;
	
							# TEXT
							case 4:
                                $answer = null;
                                
                                # ce obstaja $spr_data_vred je po vsej vrjetnosti missing
                                if ( count($spr_data_vred) > 0 ) {
                                    $key = key($spr_data_vred);
                                    $answer = $spr_data_vred[$key];
                                    //unset ($spr_data_vred[$key]);
                                } 
                                else {
                                    $answer = $spr_data_text[0]['text'];
                                }

                                if ( $answer == '' || $answer == null) {
                                    $answer = $VALUE_FOR_MISSING;
                                }

                                // dodamo odgovor
                                $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answer,$uid);
                                
                                break;
        
                                
							# MULTI RADIO BUTTON
							case 6:
								
                                # dvojni gridi so zapisani v $spr_data_grid_check
                                if ((int)$rowVprasanje['enota'] != 3) {

                                    # ni dvojni grid
                                    $answer = null;

                                    # zloopamo skozi podvprašanja (srv_vrednost
                                    if (count($spr_vrednosti) > 0){
                                        foreach ($spr_vrednosti AS $vid => $vrednost) {

                                            $missing_answer = false;

                                            if (is_array($spr_data_vred) && count($spr_data_vred) == 1 ) {
                                                $answer = end($spr_data_vred); # lahko da je -2, -4
                                                $missing_answer = true;
                                            } 
                                            else if (isset($spr_data_grid[$vid])) {
                                                if ($spr_data_grid[$vid] > 0) {
                                                    if ($spr_gridi[$spr_data_grid[$vid]]['other'] == 0) {
                                                        # preberemo grid vrednost
                                                        $answer = $spr_gridi[$spr_data_grid[$vid]]['variable']; // tukaj dodelimo ime variable, lahko bi tudi id ali vrstni red???
                                                        $missing_answer = false;
                                                    } else {
                                                        $answer = $spr_gridi[$spr_data_grid[$vid]]['other'];
                                                    }
                                                }else {
                                                    # je missing
                                                    $answer = $spr_data_grid[$vid];
                                                    $missing_answer = true;
                                                }
                                            } 
                                            else {
                                                $answer = $VALUE_FOR_MISSING;
                                                $missing_answer = true;
                                            }
                                            
                                            # dodamo odgovor
                                            $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answer,$uid);
                                            
                
                                            if ($vrednost['other'] == 1) {
                                                # če answer ni missing
                                                if ($missing_answer == false) {
                                                    if (isset($spr_data_text[$vid]['text'])) {
                                                        $answerOther = ($spr_data_text[$vid]['text'] == "" || $spr_data_text[$vid]['text'] == null) ? $VALUE_FOR_MISSING : $spr_data_text[$vid]['text'];
                                                    }else {
                                                        $answerOther = $VALUE_FOR_MISSING;
                                                    }
                                                } 
                                                else {
                                                    $answerOther = $answer;
                                                }

                                                # dodamo odgvor
                                                $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answerOther,$uid);       
                                            }
                                        }
                                    }
                                } 
                                else {
                                    # DOUBLE GRID
                                    
                                    # če imamo dvojni grid gremo 2_skozi
                                    $double = (int)($rowVprasanje['enota'] == 3);
                                    $_tmp_answers = array();
                
                                    for ($i=1; $i<=$double+1;$i++) {
                                        // for $double
                
                                        # sfiltriramo odgovore za posamezen part
                                        $_tmp_part_answers = array();
                                        if (count($spr_data_grid_check) > 0) {
                                            foreach($spr_data_grid_check as $vre_id => $grids) {
                                                if (count($grids) > 0) {
                                                    foreach($grids AS $gid => $grid) {
                                                        if ($spr_gridi[$gid]['part'] == $i) {
                                                            if ($spr_gridi[$gid]['other'] == 0) {
                                                                $_tmp_part_answers[$vre_id] = $spr_gridi[$gid]['variable'];
                                                            } else {
                                                                $_tmp_part_answers[$vre_id] = $spr_gridi[$gid]['other'];
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        $answer = null;
                                        # zloopamo skozi podvprašanja (srv_vrednost
                                        if (count($spr_vrednosti) > 0) {
                                            foreach ($spr_vrednosti AS $vid => $vrednost) {
                                                $missing_answer=false;
                                                if (is_array($spr_data_vred) && count($spr_data_vred) == 1 ) {
                                                    $answer = end($spr_data_vred); # lahko da je -2, -4
                                                    $missing_answer = true;
                                                } else if (isset($_tmp_part_answers[$vid])) {
                                                    if ($_tmp_part_answers[$vid] > 0) {
                                                        # preberemo grid vrednost
                                                        $answer = $_tmp_part_answers[$vid]; // tukaj dodelimo ime variable, lahko bi tudi id ali vrstni red???
                                                        $missing_answer = false;
                                                    } else {
                                                        $answer = $_tmp_part_answers[$vid]; // tukaj dodelimo ime variable, lahko bi tudi id ali vrstni red???
                                                        $missing_answer = true;
                                                    }
                                                } else {
                                                    $answer = $VALUE_FOR_MISSING;
                                                    $missing_answer = true;
                                                }
                    
                                                # dodamo odgovor
                                                $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answer,$uid);
                                                
                    
                                                if ($vrednost['other'] == 1) {
                                                    # če answer ni missing
                                                    if ($missing_answer == false) {
                                                    $answerOther = ($spr_data_text[$vid]['text'] == "" || $spr_data_text[$vid]['text'] == null) ? $VALUE_FOR_MISSING : $spr_data_text[$vid]['text'];
                                                } else {
                                                    $answerOther = $answer;
                                                }
                                                # dodamo odgvor
                                                $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answerOther,$uid);
                                                
                                                }
                                            }
                                        }
                                    } // for $double
                                }

                                break;


							# NUMBER in DATE
							case 7:
							case 8:
	
                                $_vred = null;
                                $answer0 = null;
            
                                # ce imamo odgovor
                                if (isset ($spr_data_text[0])) {
                                    $answer0 = ($spr_data_text[0]['text'] != '') ? $spr_data_text[0]['text'] : $VALUE_FOR_MISSING;
            
                                } 
                                else if (is_array($spr_data_vred)) {
                                    $key = key($spr_data_vred);
                                    if (isset($spr_data_vred[$key])) {
                                        $_vred = $spr_data_vred[$key];
                                    }
                                    #pogledamo če je odgovor missing:
                                    if (isset($spr_vrednosti[$_vred])) {
                                        $answer0 = $spr_vrednosti[$_vred]['variable'];
                                    } else {
                                        $answer0 = $_vred;
                                    }
            
                                }
        
                                $answer0 = ($answer0 == null ) ? $VALUE_FOR_MISSING : $answer0;
                                
                                # dodamo odgovor
                                $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answer0,$uid);
                                
        
                                # ve imamo size = 2
                                if ($tip == 7 && $spr_size > 1) {
            
                                    $answer1 = null;
                                    # ce imamo odgovor
                                    if (isset ($spr_data_text[0])) {
                                        $answer1 = ($spr_data_text[0]['text2'] != '') ? $spr_data_text[0]['text2'] : $VALUE_FOR_MISSING;
                                    } else if (is_array($spr_data_vred)) {
                                        $key = key($spr_data_vred);
                                        if (isset($spr_data_vred[$key])) {
                                            $_vred = $spr_data_vred[$key];
                                        }
                                        #pogledamo če je odgovor missing:
                                        if (isset($spr_vrednosti[$_vred])) {
                                            $answer1 = $spr_vrednosti[$_vred]['variable'];
                                        } else {
                                            $answer1 = $_vred;
                                        }
                                    }
            
                                    $answer1 = ($answer1 == null ) ? $VALUE_FOR_MISSING : $answer1;
                                        
                                    # dodamo odgovor
                                    $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answer1,$uid);
                                    
                                }

							    break;
	
	
						    # MULTI CHECKBOX
							case 16:
                                # delamo na nivoju $spr_vrednosti kar predstavlja podvprašanje
                                if (count($spr_vrednosti) > 0) {
                                foreach ($spr_vrednosti AS $vid => $vrednost) {
                                    $_tmp_answers = array();
                                    $_anything_set = false;
                                    $_missing_answer = NULL;
                                    # zloopamo skozi podvprašanja in nastavimo izbrane odgovore
                                    if (count($spr_gridi) > 0) {
                                        foreach ($spr_gridi AS $gid => $grid) {
                                            if (isset($spr_data_grid_check[$vid][$gid]) && $_missing_answer == NULL) {
                                                # če je spr_data_vrednost obstaja je checkbox obkljukan
                                                #Najprej preverimo ali je odgovro missing...
                                                if ($spr_gridi[$gid]['other'] == 0 || $spr_gridi[$gid]['other'] == 1) {
                                                $_tmp_answers[$vid][$gid] = 1;
                                                $_anything_set = true;
                                            } else {
                                                $_missing_answer = $spr_gridi[$gid]['other'];
                                                # izrišemo morebitne  1 ke pri vrednostih ki imajo kakršnkoli missing
                                                # ker ne more bit hkrati -99 ne vem in veljavni odgovor
                                                unset($_tmp_answers);
                                                $_anything_set = false;
                                            }
                                            unset($spr_data_grid_check[$vid][$gid]);
                                            }
                                        }
                                    }
                                    # nastavimo ali missinge, ali neveljavne ali 0;
                                    if ($_anything_set == false) {
                                        if ($_missing_answer != NULL) {
                                            # missing je že nastavljen
                                        } else  if (is_array($spr_data_vred) && count($spr_data_vred) > 0 ) {
                                            $_missing_answer = end($spr_data_vred);
                                        } else {
                                            $_missing_answer = $VALUE_FOR_MISSING;
                                        }
                                    }
                                    # dodamo odgovore
                                    if (count($spr_gridi)>0) {
                                        foreach ($spr_gridi AS $gid => $grid) {
                                            # dodamo samo gride ki niso missingi
                                            if ($spr_gridi[$gid]['other'] == 0 || $spr_gridi[$gid]['other'] == 1) {
                                                $answer = '0';
                                                # če je nastavljen missing so vsi gridi na podvprašanje enaki missingu
                                                if ($_missing_answer != NULL ) {
                                                    $answer = $_missing_answer;
                                                } else if (isset($_tmp_answers[$vid][$gid])) {
                                                    $answer = $_tmp_answers[$vid][$gid];
                                                }
                                                $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id, $answer,$uid);
                                                
                                            }
                                        }
                                        # dodamo še odgovore other text
                                        if ($vrednost['other'] == 1) {
                                            if ($_missing_answer != null) {
                                                $answerOther = $_missing_answer;
                                            } else if (isset($spr_data_text[$vid]['text']) && $spr_data_text[$vid]['text'] !== '') {
                                                $answerOther = $spr_data_text[$vid]['text'];
                                            } else {
                                                if ($_anything_set == false) {
                                                    if (is_array($spr_data_vred) && count($spr_data_vred) > 0 ) {
                                                        $answerOther = end($spr_data_vred);
                                                    } else {
                                                        $answerOther = $VALUE_FOR_MISSING;
                                                    }
                                                } else {
                                                    $answerOther = $VALUE_FOR_MISSING;
                                                }
                                            }
                                            # dodamo odgvor
                                            $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answerOther,$uid);
                                            
                                        }
                                    }
                                } // end foreach spr_vrednost
                            } // end if count spr_vrednost
        
                            break;
    
                            
						# RANKING
                        case 17:
                            
							if (count($spr_vrednosti ) > 0){
                                foreach ($spr_vrednosti AS $vid => $vrednost) {
                                    $answer = null;
                                    if (isset($spr_data_ranking[$vid])) {
                                        $answer = $spr_data_ranking[$vid];
                                    } else if (is_array($spr_data_vred) && count($spr_data_vred) > 0 ) {
                                        $answer = end($spr_data_vred);
                                    }
            
                                    if ($answer == "" || $answer == null) {
                                        $answer = $VALUE_FOR_MISSING;
                                    } // end if
            
                                    # dodamo odgvor
                                    $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answer,$uid);
                                    
                                } // end foreach
                            }

						    break;
	
	
						#VSOTA
                        case 18:
                            
							if (count($spr_vrednosti ) > 0){
                                foreach ($spr_vrednosti AS $vid => $vrednost) {
                                    $answer = null;
                                    
                                    if (isset($spr_data_text[$vid]['text'])) {
                                        $answer = $spr_data_text[$vid]['text'];
                                    } 
                                    else if (is_array($spr_data_vred) && count($spr_data_vred) > 0 ) {
                                        $answer = end($spr_data_vred);
                                    }
                                        
                                    if ($answer == "" || $answer == null) {
                                        $answer = $VALUE_FOR_MISSING;
                                    } // end if
            
                                    # dodamo odgvor
                                    $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answer,$uid);
                                    
                                } // end foreach
                            }

                            break;
                            
	
						# MULTITEXT in MULTINUMBER
						case 19:
						case 20:
	
							$answer = null;
                            $_tmp_answers = array();
                            $_anything_set = array();
        
                            $missing_value_temp = $VALUE_FOR_MISSING;
                                                    
                            # preverimo al mamo missing (-2 ali -4, ce je bila naknadno dodana) nad celo spremenljivko
                            if (isset($spr_data_vred) && count($spr_data_vred) == 1) {
                                $missing_value_temp = (is_array($spr_data_vred) && count($spr_data_vred) == 1 ) ? end($spr_data_vred) : $VALUE_FOR_MISSING;
                            }
        
                            $is_grid_missing = array();

                            # zloopamo skozi podvprašanja in nastavimo izbrane odgovore
                            if (count($spr_vrednosti ) > 0) {

                                foreach ($spr_vrednosti AS $vid => $vrednost) {

                                    if (count($spr_gridi) > 0) {

                                        foreach ($spr_gridi AS $gid => $grid) {

                                            # dodajamo samo odgovore ki so veljavni
                                            if ($grid['other'] == 0) {

                                                # imamo veljaven odgovor
                                                if (isset($spr_data_grid_text[$vid][$gid])) {           
                                                    $_tmp_answers[$vid][$gid] = $spr_data_grid_text[$vid][$gid];
                                                    $_anything_set[$vid] = true;
                                                } 
                                                else if (isset($spr_data_grid[$vid])) {
                                                    $_tmp_answers[$vid][$gid] = $spr_gridi[$spr_data_grid[$vid]]['other'];
                                                } 
                                                else {
                                                    $_tmp_answers[$vid][$gid] = $missing_value_temp;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
        
                            # dodamo odgovore
                            if(count($spr_vrednosti) > 0)
                            foreach ($spr_vrednosti AS $vid => $vrednost) {
                                if(count($spr_gridi) > 0)
                                foreach ($spr_gridi AS $gid => $grid) {
                                    if ($grid['other'] == 0) {
                                        # dodamo samo veljavne gride
                                        $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$_tmp_answers[$vid][$gid],$uid);
                                        
        
                                    }
                                }
                                    
                                # dodamo še odgovore other text
                                if ($vrednost['other'] == 1) {
                                    # če mamo missing dodamo missing
                                    if (isset($is_grid_missing[$vid])) {
                                        $answerOther = $is_grid_missing[$vid];
                                    } else if ($_anything_set[$vid]) {
                                        $answerOther = ($spr_data_text[$vid]['text'] == "" || $spr_data_text[$vid]['text'] == null) ? $missing_value_temp : $spr_data_text[$vid]['text'];
                                    } else {
                                        $answerOther = $missing_value_temp;
                                    }
        
                                    # dodamo odgvor
                                    $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answerOther,$uid);         
                                }
                            }

                            break;
                            

						# BESEDILO *
						case 21:
                            
                            # zloopamo skozi podvprašanja in nastavimo izbrane odgovore
                            if(count($spr_vrednosti) > 0){

                                foreach ($spr_vrednosti AS $vid => $vrednost) {

                                    if ($vrednost['other'] == 0) {
                                        
                                        $answer = null;
                                        
                                        # imamo signature vprašanje
                                        if($spr_signature == 1){
                                        
                                            if(isset($spr_data_text[$vid]['text']))
                                                $answer = $spr_data_text[$vid]['text'] . ' ';
                                            
                                            if(@getimagesize($site_url.'main/survey/uploads/'.$rowUser['usr_id'].'_'.$spr_id.'_'.$this->sid.'.png'))
                                                $answer .= '('.$site_url.'main/survey/uploads/'.$rowUser['usr_id'].'_'.$spr_id.'_'.$this->sid.'.png)';
                                        } 
                                        else if (isset($spr_data_text[$vid]['text']) && $spr_signature != 1) {
                                        
                                            if ($spr_upload == 1 || $spr_upload == 2) {
                                                # imamo upload vprašanje
                                                # imena datotek
                                                $filename = substr($this->get_array_data_text_upload($spr_data_text[$vid]['text']),strlen($uid.'_'));
                                                $answer = ''.$site_url.'main/survey/download.php?anketa='.$this->sid.'&code='.$spr_data_text[$vid]['text'].'';
                                            } 
                                            else {
                                                # imamo normalno text vprašanje
                                                $answer = $spr_data_text[$vid]['text'];
                                            }
                                                
                                        } 
                                        else if (is_array($spr_data_vred) && count($spr_data_vred) > 0 ) {
                                        
                                            #$answer = end($spr_data_vred);
                                                
                                            $key = key($spr_data_vred);
                                            if (isset($spr_data_vred[$key])) {
                                                $_vred = $spr_data_vred[$key];
                                            }
                                            #pogledamo če je odgovor missing:
                                            if (isset($spr_vrednosti[$_vred])) {
                                                $answer = $spr_vrednosti[$_vred]['variable'];
                                            } else {
                                                $answer = $_vred;
                                            }
                                        }
            
                                        if ($answer == "" || $answer == null) {
                                            $answer = $VALUE_FOR_MISSING;
                                        } // end if
                                        
                                        $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answer,$uid);		
                                    }
                                }
                            }

                            break;
                            
	
						# Kalkulacija
						case 22:
	
							$_vred = null;
							$answer0 = null;
		
							# ce imamo odgovor
							if (isset ($spr_data_text[0]) && $spr_data_text[0]['text'] != '') {
								$answer0 = $spr_data_text[0]['text'];
							} else if (is_array($spr_data_vred) && count($spr_data_vred) > 0 ) {
								$answer0 = end($spr_data_vred);
							}
		
							$answer0 = ($answer0 == null ) ? $VALUE_FOR_MISSING : $answer0;
							# dodamo odgovor
							$userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answer0,$uid);
							
                        break;
                        
						
						# Kvota
						case 25:
	
							$_vred = null;
							$answer0 = null;
		
							# ce imamo odgovor
							if (isset ($spr_data_text[0]) && $spr_data_text[0]['text'] != '') {
								$answer0 = $spr_data_text[0]['text'];
							} else if (is_array($spr_data_vred) && count($spr_data_vred) > 0 ) {
								$answer0 = end($spr_data_vred);
							}
		
							$answer0 = ($answer0 == null ) ? $VALUE_FOR_MISSING : $answer0;
							# dodamo odgovor
							$userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answer0,$uid);
									
						    break;
    
                            
						# SN - IMENA *
						case 9:
							
							# zloopamo skozi podvprašanja in nastavimo izbrane odgovore
							$arrayVrednost = $this->SNVariablesForSpr[$_vprasanje_array['id']];
							if (!is_array($arrayVrednost)) {
								$arrayVrednost = array();
							}
							if(count($spr_vrednosti) > 0)
							foreach ($spr_vrednosti AS $vid => $vrednost) {
								if (in_array($vid,$arrayVrednost)) {
									$answer = null;
									if (isset($spr_data_text[$vid]['text'])) {
										# imamo normalno text vprašanje
										$answer = $spr_data_text[$vid]['text'];
		
									} else if (is_array($spr_data_vred) && count($spr_data_vred) > 0 ) {
										$answer = end($spr_data_vred);
									}
		
									if ($answer == "" || $answer == null) {
										$answer = $VALUE_FOR_MISSING;
									} // end if
									$userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answer,$uid);
									
								}
							}	
							
                            break;
                            
                                                
						# LOKACIJA
                        case 26:
                            
                            $row = Cache::srv_spremenljivka($spr_id);

                            if($row['enota'] == 3){

                                if(count($spr_vrednosti) > 0){
                                    # zloopamo skozi podvprašanja in nastavimo izbrane odgovore
                                    if (isset ($spr_data_map)) {
                                        if(empty($spr_data_map)){
                                            //missingi, lahko da je -4
                                            $missing_value_temp = (is_array($spr_data_vred) && count($spr_data_vred) == 1 ) ? end($spr_data_vred) : $VALUE_FOR_MISSING;
                                            for($i = 0; $i < count($spr_vrednosti); $i++){
                                                $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$missing_value_temp,$uid);
                                            }
                                        }
                                        else{
                                            $answerArr = (count($spr_data_map['izpis']) > 0) ? $spr_data_map['izpis'] : $VALUE_FOR_MISSING;

                                            if(count($spr_data_map) > 0)
                                                foreach ($spr_data_map['izpis'] AS $izpis) {
                                                    $answer = null;

                                                    if (isset($izpis['vrednost'])) {
                                                            # imamo normalno text vprašanje
                                                            $answer = $izpis['vrednost'];
                                                    }
                                                    if ($answer == "" || $answer == null) {
                                                            $answer = $VALUE_FOR_MISSING;
                                                    } // end if

                                                    $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answer,$uid);		
                                                }
                                        }
                                    }
                                }   
                            }
                            else{
                                $_vred = null;
                                $answerArr = null;
                                $answerAdd = null;
                                $answerVre = null;
                                $answerKoo = null;

                                $newParams = new enkaParameters($row['params']);
                                $is_podvprasanje = $newParams->get('marker_podvprasanje') == 1;
                                //$podvprasanje_naslov = '';
                                $is_podvprasanje ? $podvprasanje_naslov = $newParams->get('naslov_podvprasanja_map') : 
                                $podvprasanje_naslov = '';

                                $multi_input_type = $newParams->get('multi_input_type');

                                # ce imamo odgovor
                                if (isset ($spr_data_map)) {
                                    $missing_value_temp = (is_array($spr_data_vred) && count($spr_data_vred) == 1 ) ? end($spr_data_vred) : $VALUE_FOR_MISSING;
                                    $answerArr = (is_countable($spr_data_map['izpis']) && count($spr_data_map['izpis']) > 0) ? $spr_data_map['izpis'] : $missing_value_temp;
                                }
                                else if (is_array($spr_data_map)) {
                                    $key = key($spr_data_map);
                                    
                                    if (isset($spr_data_map[$key])) {
                                                    $_vred = $spr_data_map[$key];
                                    }

                                    // pogledamo če je odgovor missing:
                                    if (isset($spr_vrednosti[$_vred])) {
                                                    $answerAdd = $spr_vrednosti[$_vred]['variable'];
                                                    $answerVre = $spr_vrednosti[$_vred]['variable'];
                                                    $answerKoo = $spr_vrednosti[$_vred]['variable'];
                                    } else {
                                                    $answerAdd = $_vred;
                                                    $answerVre = $_vred;
                                                    $answerKoo = $_vred;
                                    }
                                }

                                if ($answerArr == null || $answerArr == $missing_value_temp){
                                        $answerAdd = $answerVre = $answerKoo = $missing_value_temp;
                                }
                                elseif($answerArr[0]['address'] == '-2'){
                                        $answerAdd = $answerVre = $answerKoo = '-2';
                                }
                                else{
                                    foreach($answerArr as $varArr){
                                        if($answerKoo != null){
                                                $answerAdd .= '<br>'.$varArr['address'];
                                                $answerVre .= '<br>'.$varArr['vrednost'];
                                                $answerKoo .= '<br>'.$varArr['koordinate'];
                                        }
                                        else{
                                                $answerAdd = $varArr['address'];
                                                $answerVre = $varArr['vrednost'];
                                                $answerKoo = $varArr['koordinate'];
                                        }
                                    }
                                }

                                # dodamo odgovor
                                if($multi_input_type == 'marker')
                                    $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answerAdd,$uid);
                                if($is_podvprasanje)
                                        $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answerVre,$uid);
                                
                                        $userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answerKoo,$uid);
                            }

							break;
                           

						// HEATMAP
						case 27:
								$_vred = null;
								$answerArr = null;
								$answerAdd = null;
								$answerVre = null;
								$answerKoo = null;
								
								$row = Cache::srv_spremenljivka($spr_id);
								$newParams = new enkaParameters($row['params']);

                                // ce imamo odgovor
								if (isset ($spr_data_heatmap)) {
										$answerArr = (is_countable($spr_data_heatmap['izpis']) && count($spr_data_heatmap['izpis']) > 0) ? $spr_data_heatmap['izpis'] : $VALUE_FOR_MISSING;
                                } 
                                else if (is_array($spr_data_heatmap)) {
										$key = key($spr_data_heatmap);
										if (isset($spr_data_heatmap[$key])) {
												$_vred = $spr_data_heatmap[$key];
												$_vred = $spr_data_heatmap[$key];
										}
										#pogledamo če je odgovor missing:
										if (isset($spr_vrednosti[$_vred])) {
												$answerAdd = $spr_vrednosti[$_vred]['variable'];
												$answerVre = $spr_vrednosti[$_vred]['variable'];
												$answerKoo = $spr_vrednosti[$_vred]['variable'];
										} else {
												$answerAdd = $_vred;
												$answerVre = $_vred;
												$answerKoo = $_vred;
										}
								}

								if ($answerArr == null || $answerArr == $VALUE_FOR_MISSING){
									$answerAdd = $answerVre = $answerKoo = $VALUE_FOR_MISSING;
								}
								elseif($answerArr[0]['address'] == '-2'){
									$answerAdd = $answerVre = $answerKoo = '-2';
								}
								else{
									foreach($answerArr as $varArr){
										$answerAdd .= '<br>'.$varArr['address'];
										$answerVre .= '<br>'.$varArr['vrednost'];
										$answerKoo .= '<br>'.$varArr['koordinate'];
									}
								}
								
								# dodamo odgovor za koordinate
								$userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answerKoo,$uid);

								// za območja
								if (is_array($spr_data_heatmap_regions)) {

									//priprava spremenljivk za koordinate točk
									$pointx = $this->preparePointCoords($answerKoo, 1, 0);
									$pointy = $this->preparePointCoords($answerKoo, 0, 1);
									$numberOfPointsInside=array();
																			
									if (count($spr_vrednosti) > 0) {																		
										$i=0;												
										foreach ($this->_array_data_heatmap_regions[(int)$spr_id] AS $regions){
											$answerReg = null;
											$numberOfPointsInside[$regions['region_name']] = 0;     // Belezi stevilo tock znotraj trenutnega obmocja
                                            
                                            // Pretvori polje s tockami obmocja v ustrezno obliko
											$poly = $this->convertPolyString($regions['region_coords']); 
											
											// preveri, ali je posamezna tocka znotraj trenutnega obmocja
											for ($z=0; $z<sizeof($pointx); $z++){
												$inside = $this->insidePoly($poly, $pointx[$z]["x"], $pointy[$z]["y"]);
												if ($inside == true){
													$numberOfPointsInside[$regions['region_name']]++;
												}
											}
											
                                            // Priprava odgovora za preglednico s podatki
                                            // Ce je missing
											if($answerKoo < 0){
												$answerReg = $answerKoo;
                                            }
                                            else{
												$answerReg = $numberOfPointsInside[$regions['region_name']];	
											}
											
                                            $i++;
                                            
											// dodamo odgovor s stevilom tock znotraj obmocja
											$userAnswerLine .= STR_DLMT.$this->recode_answer($spr_id,$answerReg,$uid);
											
										}
									}
								}
                                //za območja - konec
                                
							break;
						}
					} // end while
				} // end if ($this->noErrors)


				// popravimo -1, -4 in -2d (text za drugo) => -3, začnemo odzadaj in spreminjamo -1, -4 in -2d v -3 dokler obstajajo ampak samo če status ni 6
                $changed = false;
				if ((int)$rowUser['status'] !== 6 && !empty($userAnswerLine)) {
					
					// ugotovimo do katere spremenljivke sploh zamenjujemo vrednosti ker če je pri tabelah odgovor vsaj na 1 variablo, potem tam pustimo -1
					$valuesToChange = $this->calculateValuesToChange($uid);
					$userAnswerLineArray = explode('|',$userAnswerLine);

					$reversed = array_reverse($userAnswerLineArray);
					foreach ($reversed AS $key => $value) {
						
						if ($key+1 > $valuesToChange) {
							break;
						}

						if ($value == -1 || $value == -4 || $value == '-2d') {
							$reversed[$key] = -3;
							$changed = true;
                        } 
                        else {
                            $reversed[$key] = $value;
						}
                    } 
                    
					if ($changed) {
						$userAnswerLineArray = array_reverse($reversed);
						$userAnswerLine = (implode('|', $userAnswerLineArray));
					}
				}
			
				// Popravimo -2d (text za drugo, ce je naknadno nastavljen na -2, ker ni bil oznacen radio/checkbox za drugo)
				$userAnswerLine = str_replace('-2d','-2',$userAnswerLine);
				
				// Naknadno popravimo se -2, ce je lurker (-5), ker nekatere drugace ostanejo
				if ($VALUE_FOR_MISSING == -5) {
					$userAnswerLine = str_replace('-2','-5',$userAnswerLine);
				}
				
				// dodamo podatke k userju
				$_dataLine .= $userAnswerLine;
				

				// DODAMO META PODATKE
				if ($this->noErrors) {
					
					// Random vrstni redi
					
					foreach ($this->_array_random as $random_parent) {

						$type = ($random_parent['type'] == 'spr') ? 'spr' : 'block';
						$random_data = $this->get_array_data_random($uid, $random_parent['id'], $type);
						unset($this->_array_data_random[$uid][$type][$random_parent['id']]);

						$_dataLine .= STR_DLMT.$random_data;
					}

					# če mamo da prepozna uporabnika iz sispleta
					if ((int)$this->survey['user_from_cms'] > 0) {
						$_dataLine .= STR_DLMT;
						if ((int)$rowUser['user_id'] > 0) {
							$_dataLine .= $this->get_user_CMS_email((int)$rowUser['user_id']);
						}
					}
		
					# datum insert, datum edit
					$_dataLine .= STR_DLMT.datetime($rowUser['time_insert']);
					$_dataLine .= STR_DLMT.datetime($rowUser['time_edit']);
		
					# strani in časi
					foreach ($this->_array_groups AS $gid => $grupa) {
		
						if ($this->_array_user_grupa[$uid][$gid] != '') {
							$_dataLine .= STR_DLMT.datetime($this->_array_user_grupa[$uid][$gid]);
						} else {
							$_dataLine .= STR_DLMT.'';
						}
					}
				
					/* ZARADI VAROVANJA PODATKOV SMO ZAČASNO ONEMOGOČILI LOVLJENJE IP-jev */
					$ip = SurveySetting::getInstance()->getSurveyMiscSetting('survey_ip');
					$ip_show = SurveySetting::getInstance()->getSurveyMiscSetting('survey_show_ip');
					if($ip==0 && $ip_show==1 && ($admin_type == 0 || $admin_type == 1)){
						$_dataLine .= STR_DLMT.$rowUser['ip'];
					}
					
					$_dataLine .= STR_DLMT.$rowUser['useragent'];
					$_dataLine .= STR_DLMT.$rowUser['browser'];
					$_dataLine .= STR_DLMT.$rowUser['os'];
					$_dataLine .= STR_DLMT.$lang['srv_para_graph_device'.$rowUser['device']];
					$_dataLine .= STR_DLMT.$rowUser['referer'];
					$_dataLine .= STR_DLMT.$rowUser['unsubscribed'];
					$_dataLine .= STR_DLMT.$jeziki[$rowUser['language']];
				}
                
                
				// Pocistimo vrednosti, ker drugace v nekaterih primereih prihaja do zelo cudnih bugov
				unset($rowVprasanje);
				unset($spr_vrednosti);
				unset($spr_gridi);
                
                
				// Zapišemo vsako vrstico posebej
				if ($this->noErrors && !empty($_dataLine)) {

					// zapišemo vrstico z predhodnim vrivom nove vrstice
					$success = fwrite($file_handler, $this->new_line_seperator . $_dataLine);
					fflush($file_handler);
                    
                    // Imamo napako - vse skupaj prekinemo
                    if ((int)$success <= 0) {
                        $this->noErrors = false;                    
                    } 
                    
					$_dataLine = null;
					unset($_dataLine);
                } 
                else {
					# dodamo v log napako
					$SL = new SurveyLog();
					$SL->addMessage(SurveyLog::ERROR, " ERROR user ".$rowUser['usr_id']." for ank_id".$this->sid);
					$SL->write();			
                }
                
                $this->new_line_seperator = NEW_LINE;
                
			} // end while user loop
        } // end if ($this->noErrors)
    }

    /***** KONEC - ZBIRANJE PODATKOV ZA DATOTEKO *****/
    


    /***** KESIRANJE PODATKOV ANKETE (vprasanja, strani, gridi...) *****/

    // Glavna funkcija preko katere na zacetku izvedemo vso kesiranje
    private function cache_data(){

        // Zakesiramo query za respondente
        $this->create_qry_users();


        // Zakesiramo strani v anketi
        $this->get_groups();

        // Zakesiramo vsa vprasanja v anketi
        $this->get_vprasanja();   

        // Randomizacija v blokih in vprasanjih
        $this->get_random();
                    
        // Za vrednosti ankete
        $this->get_vrednosti();
        
        // Za gride ankete
        $this->get_gridi();		   

        // Skreira array z polji za spss 
        $this->get_SPSS();


        // Polovimo missing vrednosti ce so nastavljene drugače kot privzeto
        // TODO če uporabnik na anketi spremeni da naj bo -1 -6 se to ne upošteva.
        $this->setSurveyMissingValues();
    }

    // Naredimo query za respondente (srv_users)
	private function create_qry_users() {
			
		if ($this->_qry_users === null ) {
            
            if ((int)$this->data_file_time > 0 && file_exists($this->folder . 'export_data_'.$this->sid.'.dat')) {
				# delamo inkremental
				$inkremental_user_limit = " AND u.time_edit > FROM_UNIXTIME('".(int)$this->data_file_time."') ";
            } 
            else {
				# lovimo vse userje - datoteko generiramo na novo
				$inkremental_user_limit = '';
			}

			$_qry_cnt = sisplet_query("SELECT count(*) FROM srv_user AS u WHERE u.ank_id = '".$this->sid."' AND u.preview='0' AND u.deleted='0' ".$inkremental_user_limit .$this->is_valid_user_limit);
			$_allUsers_count = mysqli_fetch_row($_qry_cnt);

			$this->_cnt_all_users = 0;
			
            // naredimo array querijev za userje, limitirano po max vrednosti userjev na loop (MAX_USER_PER_LOOP)
            $c = 0;
			do {
                // Naredimo query
                $this->_qry_users[$c] = sisplet_query("SELECT u.id AS usr_id, u.cookie, u.recnum, u.last_status as status, u.pass, u.testdata, u.lurker, u.unsubscribed, UNIX_TIMESTAMP(u.time_insert) AS unx_ins_date, UNIX_TIMESTAMP(u.time_edit) AS unx_edt_date, u.user_id, u.inv_res_id, u.time_insert, u.time_edit, u.ip, REPLACE(u.useragent,'|',' ') as useragent, u.browser, u.os, u.device, REPLACE(u.referer,'|',' ') as referer, language 
                                                        FROM srv_user AS u 
                                                        WHERE u.ank_id = '".$this->sid."' AND u.preview='0' AND u.deleted='0' ".$inkremental_user_limit . $this->is_valid_user_limit." 
                                                        ORDER BY u.id ASC
                                                        LIMIT ".($c * MAX_USER_PER_LOOP).",".MAX_USER_PER_LOOP."");

				if (!$this->_qry_users[$c]) {
					$this->trigerError('create_qry_users', mysqli_error($GLOBALS['connect_db']));
				}

				// Naredimo še string z id userjev za večkratno uporabo
				$str = '';
				if (mysqli_num_rows($this->_qry_users[$c]) > 0) {
					$prefix ='';
					while ($row = mysqli_fetch_assoc($this->_qry_users[$c])) {
						$str .= $prefix.$row['usr_id'];
						$prefix = ',';
					}
					$this->_str_users[$c] = ' AND usr_id IN ('.$str.') ';
				}
				$this->_cnt_all_users += mysqli_num_rows($this->_qry_users[$c]);
	
				$c ++;
            } 
            while ($c * MAX_USER_PER_LOOP <= $_allUsers_count['0']);
		}
    }
    
    // Zakesiramo strani v anketi (srv_grupa)
	private function get_groups() {

		if ($this->_cnt_groups == 0) {
				
			$qry_groups = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='".$this->sid."' ORDER BY vrstni_red");
			if (!$qry_groups) {
				$this->trigerError('get_groups',  mysqli_error($GLOBALS['connect_db']));
			}

			// koliko strani imamo v anketi
			$this->_cnt_groups = mysqli_num_rows($qry_groups);

			// naredimo še string z id grupami za večkratno uporabo
			$str = '';
            $cnt = 0;
			if ($this->_cnt_groups > 0) {
				$this->_array_groups = array();
				$prefix ='';
                
                while ($row = mysqli_fetch_assoc($qry_groups)) {
					$cnt++;
						
					$this->_array_groups[$row['id']] = $row['id'];
					$str .= $prefix.$row['id'];
					$prefix = ',';
                }
                
				$this->_str_groups = $str;
			}
        }
    }

    // Zakesiramo vsa vprasanja v anketi
    private function get_vprasanja(){

        // Delamo samo 1x
		if ( $this->AllQuestionsData !== null &&  $this->AllQuestionsOrder != null) {
			return array('AllQuestionsData'=>$this->AllQuestionsData, 'AllQuestionsOrder'=>$this->AllQuestionsOrder);
        }
        

        // Po novem lovimo tudi sistemske pa jih odstranimo pri prikazu in izvozih
        $_qry_questions = sisplet_query("SELECT s.id, s.tip, s.variable, REPLACE(REPLACE(REPLACE(s.naslov,'\n',' '),'\r','<br>'),'|','<br>') as naslov, s.label, s.gru_id, s.gru_id, s.random, s.size, s.cela, s.decimalna, s.skala, s.enota, s.sistem, s.upload, s.signature, s.grid_subtitle1, s.grid_subtitle2, s.inline_edit, REPLACE(REPLACE(REPLACE(s.naslov_graf,'\n',' '),'\r','<br>'),'|','<br>') as naslov_graf, s.edit_graf, s.wide_graf, antonucci, s.visible, s.dostop 
                                                FROM srv_spremenljivka AS s, srv_grupa AS g 
                                                WHERE s.gru_id = g.id AND g.ank_id = '".$this->sid."' AND s.tip != '5' 
                                                ORDER BY g.vrstni_red, s.vrstni_red");
        if (!$_qry_questions) {
            $this->trigerError('get_vprasanja',  mysqli_error($GLOBALS['connect_db']));
            return false;
        }

        // Prestejemo vprasanja v anketi
        $this->_cnt_questions = mysqli_num_rows($_qry_questions);

        // Ce nimamo vprasanj v anketi ne gremo naprej
        if ($this->_cnt_questions == 0) {
            $this->trigerError('get_vprasanja',  'Ni vprasanj v anketi');
            return false;
        }


        // Za SN - imena (modul social network)
		if(SurveyInfo::getInstance()->checkSurveyModule('social_network')){

            $_result = array();

            while ($rowVprasanje = mysqli_fetch_assoc($_qry_questions)) {
                if($rowVprasanje['tip'] == 9) {
                    $_result[] = $rowVprasanje['id'];
                }
            }

            if (count($_result) > 0) {
                foreach ($_result AS $spr_id) {
                    $qry = sisplet_query("SELECT vre_id FROM srv_data_text".$this->db_table." WHERE spr_id = '".$spr_id."' GROUP BY vre_id");
                    while ( $row = mysqli_fetch_assoc($qry)) {
                        if ( $row['vre_id'] > 0 ) {
                            $this->SNVariablesForSpr[$spr_id][$row['vre_id']] = $row['vre_id'];
                        }
                    }
                }
            }

            mysqli_data_seek($_qry_questions, 0);
        }


        // Polovimo spremenljivke za survey Grid Multiple - kombinirano tabelo
		$sgmMap = array();
		$sqlSgmMap = sisplet_query("SELECT sgm.parent AS sgm_parrent, s.id, s.tip, s.variable, REPLACE(REPLACE(REPLACE(s.naslov,'\n',' '),'\r','<br>'),'|','<br>') as naslov, s.gru_id, s.size, s.random, s.cela, s.decimalna, s.skala, s.enota, s.sistem, s.upload, s.signature, s.grid_subtitle1, s.grid_subtitle2, s.inline_edit, REPLACE(REPLACE(REPLACE(s.naslov_graf,'\n',' '),'\r','<br>'),'|','<br>') as naslov_graf, s.edit_graf, s.wide_graf, s.antonucci, s.visible, s.dostop 
                                        FROM srv_spremenljivka AS s JOIN srv_grid_multiple AS sgm ON (s.id = sgm.spr_id) 
                                        WHERE sgm.ank_id = '$this->sid' 
                                        ORDER BY sgm.vrstni_red");
		while ($sgmRow = mysqli_fetch_assoc($sqlSgmMap)) {
			$sgmMap[$sgmRow['sgm_parrent']][] = $sgmRow;
        }
        

		// Preverimo ali so bili skreirani loopi
		if ($this->_array_loop_on_spr == null || $this->_array_spr_in_loop == null) {
			$this->get_loops();
		}


        // Pripravimo array-e za kesiranje vprasanj
		$this->AllQuestionsData = array();
		$this->AllQuestionsOrder = array();
        $star_loop_id = null;	
        
        // Spremenljivka za kreacijo stringa z id spremenljivkami za večkratno uporabo
        $sprIds = array();
        $cnt = 0;

        // Loop po vseh vprasanjih v anketi
        while ($rowMainVprasanje = mysqli_fetch_assoc($_qry_questions)) {

            // Naredimo array z id spremenljivkami za večkratno uporabo - za kombinirano tabelo posebej dodamo se podtabele
            if ($rowMainVprasanje['tip'] != 24) {
                $sprIds[] = $rowMainVprasanje['id'];
            } 
            else {
                $sqlSub = sisplet_query("SELECT s.id FROM srv_spremenljivka AS s JOIN srv_grid_multiple AS sgm ON (s.id = sgm.spr_id) 
                                            WHERE sgm.parent = '".$rowMainVprasanje['id']."' 
                                            ORDER BY sgm.vrstni_red");
                while (list($subSprId) = mysqli_fetch_row($sqlSub)) {
                    $sprIds[] = $subSprId;
                }
            }


            $rowVprasanja = array();
            
            if (!is_countable($sgmMap[$rowMainVprasanje['id']]) || count($sgmMap[$rowMainVprasanje['id']]) == 0) {
                $rowVprasanja[] = $rowMainVprasanje;
            } 
            else {
                // preverimo parent tip. mora biti 24
                if ((int)$rowMainVprasanje['tip'] == 24){
                    
                    // imamo kombinirano vprašanje
                    foreach ($sgmMap[$rowMainVprasanje['id']] AS $sgmRow) {
                        $tmpVprasanje = $sgmRow;

                        // popravimo variablo in besedilo
                        $tmpVprasanje['sgm_parrent'] = $rowMainVprasanje['id'];
                        $tmpVprasanje['variable'] = $rowMainVprasanje['variable'].'_'.$sgmRow['variable'];
                        $tmpVprasanje['naslov'] = $rowMainVprasanje['naslov'];
                        $rowVprasanja[] = $tmpVprasanje;
                    }
                }
                // če ne ni kombinirana tabela
                else { 
                    $rowVprasanja[] = $rowMainVprasanje;
                }            
            }

            if (count($rowVprasanja) > 0) {

                foreach ($rowVprasanja AS $rowVprasanje) {

                    $spr_id = $rowVprasanje['id'];

                    $this->AllQuestionsData[$rowVprasanje['id']] = array(
                            'id' 		    => $rowVprasanje['id'],	
                            'spr_id'	    => $spr_id,	
                            'sgm_parrent'   => $rowVprasanje['sgm_parrent'],
                            'tip'		    => $rowVprasanje['tip'],
                            'gru_id'	    => $rowVprasanje['gru_id'],
                            'variable'  	=> strip_tags($rowVprasanje['variable']),
                            'naslov' 	    => strip_tags($rowVprasanje['naslov']),
                            'label' 	    => strip_tags($rowVprasanje['label']),
                            'size' 		    => $rowVprasanje['size'],
                            'cela' 		    => $rowVprasanje['cela'],
                            'grid_subtitle1' => $rowVprasanje['grid_subtitle1'],
                            'grid_subtitle2' => $rowVprasanje['grid_subtitle2'],
                            'decimalna'	    => $rowVprasanje['decimalna'],
                            'skala' 	    => $rowVprasanje['skala'],
                            'sistem'	    => $rowVprasanje['sistem'],
                            'enota'		    => $rowVprasanje['enota'],
                            'if_id'		    => (isset($this->_array_spr_in_loop[$spr_id]) ? $this->_array_spr_in_loop[$spr_id] : 0),
                            'upload'	    => $rowVprasanje['upload'],
                            'signature'	    => $rowVprasanje['signature'],
                            'naslov_graf'	=> $rowVprasanje['naslov_graf'],
                            'edit_graf'	    => $rowVprasanje['edit_graf'],
                            'wide_graf'     => $rowVprasanje['wide_graf'],
                            'antonucci'     => $rowVprasanje['antonucci'],
                            'visible'       => $rowVprasanje['visible'],
                            'dostop'        => $rowVprasanje['dostop'],
                            'random'        => $rowVprasanje['random']
                    );

                    if ($rowVprasanje['inline_edit'] != 0) { 
                        $this->AllQuestionsData[$rowVprasanje['id']]['inline_edit'] = $rowVprasanje['inline_edit'];
                    }
                    
                    // Zabelezimo stevilo vprasanj po tipu
                    $this->_cnt_questions_types[$rowVprasanje['tip']] = (isset($this->_cnt_questions_types[$rowVprasanje['tip']])) ? $this->_cnt_questions_types[$rowVprasanje['tip']]+1 : 1;
                    
                    // če je spremenljivka parent od loopa jo dodamo
                    if (isset($this->_array_loop_parent[$spr_id]) && $this->_array_loop_parent[$spr_id] > 0) {
                        $this->AllQuestionsData[$rowVprasanje['id']]['loop_parent'] = $this->_array_loop_parent[$spr_id];
                        $this->AllQuestionsData[$rowVprasanje['id']]['antonucci'] = $rowVprasanje['antonucci'];
                    }

                    // dodamo vprašanje v vrstni red, če je vprašanje v loopu dodamo loop
                    if (!isset($this->_array_spr_in_loop[$spr_id])) {

                        // vprašanje ni v loopu
                        $this->AllQuestionsOrder[] = array('id'=>$spr_id, 'loop_id'=>0);

	                    // nismo v loopu
                        $star_loop_id = null;
                    } 
                    else {
                        // vprašanje je v lopu
                        $trenutni_loop_id = $this->_array_spr_in_loop[$spr_id]; # id loopa v katerem se nahaja spremenljivk
                        
                        // na katero spremenljivko se dela loop
                        $loop_on_spr = $this->_array_loop_on_spr[$trenutni_loop_id];
    
                        // če ta loop in pripadajoča vprašanja še niso bila dodana v vrstni red, jih dodamoše ni bil
                        if ($trenutni_loop_id != $star_loop_id) {
    
                            // loop še ni bil sprocesiran;
                            $_vrednosti_v_loopu = $this->_array_vre_on_loop[$trenutni_loop_id];
    
                            // preverimo ali smo v navadnem loopu ali v SN loopu
                            if (isset($this->SNVariablesForSpr[$loop_on_spr]) == false) {

                                // smo v navadnem loopu
                                // kolikokrat moramo iti skozi loop
                                if (count($_vrednosti_v_loopu)) {

                                    foreach ($_vrednosti_v_loopu AS $li_id => $vrednost) {

                                        // dodamo vprašanja ki so v posameznemm loopu.
                                        if (count($this->_array_loop_has_spr[$trenutni_loop_id])) {
                                            foreach($this->_array_loop_has_spr[$trenutni_loop_id] AS $vprasanje) {
                                                $this->AllQuestionsOrder[] = array('id'=>$vprasanje,'loop_id'=>$li_id,'vre_id'=>$vrednost, 'parent_loop_id'=>$trenutni_loop_id);
                                            }
                                        }
                                    }
                                }
                            } 
                            else {
                                // smo v SN loopu - skozi loop gremo samo za vrednosti ki so dodane kot sn imena
                                if (count($_vrednosti_v_loopu)) {
                                    foreach ($_vrednosti_v_loopu AS $li_id => $vrednost) {

                                        if (in_array($vrednost,$this->SNVariablesForSpr[$loop_on_spr])) {

                                            // dodamo vprašanja ki so v posameznemm loopu.
                                            if (count($this->_array_loop_has_spr[$trenutni_loop_id])) {
                                                foreach($this->_array_loop_has_spr[$trenutni_loop_id] AS $vprasanje) {
                                                    $this->AllQuestionsOrder[] = array('id'=>$vprasanje,'loop_id'=>$li_id,'vre_id'=>$vrednost, 'parent_loop_id'=>$trenutni_loop_id);
                                                }
                                            }
                                        }
                                    }
                                }
    
                            }
    
                            # priredimo kateri loop smo že obdelali, da ne bomo 2x
                            $star_loop_id = $trenutni_loop_id;
                        }
                    }
                }
            }
        }

        // Naredimo string z id spremenljivkami za večkratno uporabo
        if (count($sprIds) > 0) {
            $this->_str_questions = implode(',', $sprIds);
        }

		return array('AllQuestionsData'=>$this->AllQuestionsData, 'AllQuestionsOrder'=>$this->AllQuestionsOrder);
    }

    // Polovimo podatke za loop ce je kaksen v anketi
    private function get_loops() {
			
        // na katero spremenljivko se veže loop
        $this->_array_loop_on_spr = array();
        $this->_array_loop_parent = array();
        
        $qryLoop_spr_select = sisplet_query("SELECT l.if_id, l.spr_id 
                                                FROM srv_branching AS b, srv_loop AS l 
                                                WHERE b.ank_id = '".$this->sid."' AND b.element_if = l.if_id");
        while (list($loop_id,$spr_id) = mysqli_fetch_row($qryLoop_spr_select)) {
            $this->_array_loop_parent[$spr_id] = $loop_id;
            $this->_array_loop_on_spr[$loop_id] = $spr_id;

            $_cnt_loop++;
        }
    
        // katere vrednosti uporablja posamezen loop ( kolikokrat se loop zavrti)
        $this->_array_vre_on_loop = array();
        $this->_array_vrednosti_in_loops = array();

        $qry_vre_on_loop_select = sisplet_query("SELECT ld.id, ld.if_id, ld.vre_id 
                                                    FROM srv_loop_data AS ld 
                                                    WHERE ld.if_id IN 
                                                        (SELECT l.if_id 
                                                            FROM srv_branching AS b, srv_loop AS l 
                                                            WHERE b.ank_id = '".$this->sid."' AND b.element_if = l.if_id) 
                                                    ORDER BY if_id, ld.vre_id, ld.id");
        while (list($id, $loop_id,$vre_id) = mysqli_fetch_row($qry_vre_on_loop_select)) {
            
            // če imamp SN -imena in je loop na SN - imena ne loopamo po vseh vrednostih, ampak samo po max vnosih
            $this->_array_vre_on_loop[$loop_id][$id] = $vre_id;
            $this->_array_vrednosti_in_loops[] = $vre_id;
            
            // Posebej nastavimo ce gre za loop po numeric vprasanju
            if($vre_id == null){
                $this->_array_vre_on_loop[$loop_id][$id] = 'num_loop';
                $this->_array_vrednosti_in_loops[] = 'num_loop';
            }
        }

        // preverimo katere spremenljvke so v katerem loopu
        $this->_array_spr_in_loop = array();
        $this->_array_loop_has_spr = array();
        $b = new Branching ($this->sid);
        $_loops = $b->spremenljivke_in_loop();
            
        if (count($_loops) > 0 ) {
            
            foreach ($_loops AS $lkey => $spr_ids) {
    
                if (count($spr_ids) > 0 ) {
                    $this->_array_loop_has_spr[$lkey] = array();
                    
                    foreach ($spr_ids AS $spr_id) {
                        $this->_array_spr_in_loop[$spr_id] = $lkey;
                        $this->_array_loop_has_spr[$lkey][] = $spr_id;
                    }
                }
            }
        }
    }
    
    // Zakesiramo vrednosti vprasanj v anketi
	private function get_vrednosti($spid=null) {

        // Ce ze imamo podatek in iscemo za posamezno spremenljivko
        if ($spid != null && (int)$spid > 0 && isset($this->_array_vrednosti[$spid])) {		
		    return $this->_array_vrednosti[$spid];
        } 

        // Ce ze imamo pripravljen cel array
        if ($spid == null && $this->_array_vrednosti !== null) {
            return $this->_array_vrednosti;
        }
        

        if ($this->_str_questions != '') {
					
            $_qry_vrednosti = sisplet_query("SELECT id, spr_id, REPLACE(REPLACE(REPLACE(naslov,'\n',' '),'\r','<br>'),'|',' ') as naslov, REPLACE(REPLACE(REPLACE(naslov2,'\n',' '),'\r','<br>'),'|',' ') as naslov2, variable, vrstni_red, other, REPLACE(REPLACE(REPLACE(naslov_graf,'\n',' '),'\r','<br>'),'|',' ') as naslov_graf 
                                                FROM srv_vrednost 
                                                WHERE spr_id IN (".$this->_str_questions.") 
                                                ORDER BY spr_id, vrstni_red"); 
            if (!$_qry_vrednosti) {
                $this->trigerError('get_vrednosti',  mysqli_error($GLOBALS['connect_db']));
            }
            
            $_cnt_vrednosti = mysqli_num_rows($_qry_vrednosti);
        
            if ($_cnt_vrednosti > 0) {

                while (list($id, $spr_id, $naslov, $naslov2, $variable, $vrstni_red, $other, $naslov_graf) = mysqli_fetch_row($_qry_vrednosti)) {

                    $this->_array_vrednosti[(int)$spr_id][(int)$id] = array(
                        'id'            => (int)$id, 
                        'naslov'        => strip_tags($naslov), 
                        'naslov2'       => strip_tags($naslov2), 
                        'variable'      => $variable, 
                        'vrstni_red'    => (int)$vrstni_red, 
                        'other'         => (int)$other, 
                        'naslov_graf'   => strip_tags($naslov_graf) 
                    );
                }
            }
        }

        
		if ($spid != null && (int)$spid > 0) {
			return $this->_array_vrednosti[$spid];
        } 
        else {
			return $this->_array_vrednosti;
		}
    }
    
	// Zakesiramo gride tabel vprasanj v anketi
	private function get_gridi($grid=null) {

        // Ce ze imamo podatek in iscemo za posamezno spremenljivko
		if ($grid != null && (int)$grid > 0 && isset($this->_array_gridi[$grid])) {
		    return $this->_array_gridi[$grid];
        } 

        // Vrenmo celoten array
        if ($grid == null && $this->_array_gridi !== null) {
            return $this->_array_gridi;
		}


        if ($this->_str_questions != '') {

            $_qry_gridi = sisplet_query("SELECT id, spr_id, REPLACE(REPLACE(REPLACE(naslov,'\n',' '),'\r','<br>'),'|',' ') as naslov, variable, other, part, REPLACE(REPLACE(REPLACE(naslov_graf,'\n',' '),'\r','<br>'),'|',' ') as naslov_graf, vrstni_red 
                                            FROM srv_grid 
                                            WHERE spr_id IN (".$this->_str_questions.") 
                                            ORDER BY spr_id, vrstni_red");
            
            if (!$_qry_gridi) {
                $this->trigerError('get_gridi',  mysqli_error($GLOBALS['connect_db']));
            }

            $_cnt_gridi = mysqli_num_rows($_qry_gridi);

            if ($_cnt_gridi > 0) {

                while (list($id, $spr_id, $naslov, $variable, $other, $part, $naslov_graf) = mysqli_fetch_row($_qry_gridi)) {

                    $this->_array_gridi[(int)$spr_id][(int)$id] = array(
                        'id'            => (int)$id, 
                        'naslov'        => strip_tags($naslov), 
                        'variable'      => $variable, 
                        'other'         => (int)$other, 
                        'part'          => (int)$part, 
                        'naslov_graf'   => strip_tags($naslov_graf)
                    );
                }
            }
        }
     
        
        if ($grid != null && (int)$grid > 0 ) {
			return $this->_array_gridi[$grid];
        } 
        else {
			return $this->_array_gridi;
		}
    }
    
    // Zakesiramo podatke za SPSS
    private function get_SPSS() {
        
        if ($this->_array_SPSS === null && $this->_str_questions != '') {
            
            $this->_array_SPSS = array();
            
            $_qry_SPSS = sisplet_query("SELECT dt.spr_id, MAX(LENGTH(dt.text)) AS length, MAX(LENGTH(dt.text2)) AS length2 
                                            FROM srv_data_text".$this->db_table." dt, srv_grupa g, srv_spremenljivka s 
                                            WHERE dt.spr_id = s.id AND s.gru_id=g.id AND g.ank_id=".$this->sid." 
                                            GROUP BY dt.spr_id");
            while (list($spr_id, $text, $text2) = mysqli_fetch_row($_qry_SPSS)) {
                $this->_array_SPSS[$spr_id] = array('text'=>(int)$text, 'text2'=>(int)$text2);
            }
    
            $_qry_SPSS1 = sisplet_query("SELECT spr_id, max(LENGTH(text)) AS length 
                                            FROM srv_data_textgrid".$this->db_table." 
                                            WHERE spr_id IN (".$this->_str_questions.") 
                                            GROUP BY spr_id");
            while (list($spr_id, $text) = mysqli_fetch_row($_qry_SPSS1)) {
                $this->_array_SPSS[$spr_id]['text2'] = $text;
            }

            // polovimo še max vrednosti za variable
            $tmp_qry_SPSS = sisplet_query("SELECT spr_id, max(LENGTH(vrstni_red)) AS length 
                                            FROM srv_vrednost 
                                            WHERE spr_id IN (".$this->_str_questions.") 
                                            GROUP BY spr_id 
                                            ORDER BY spr_id, vrstni_red");
            while (list($spr_id, $vrstni_red) = mysqli_fetch_row($tmp_qry_SPSS)) {
                $this->_array_SPSS[$spr_id]['vrednost'] = $vrstni_red;
            }
        }
        
		return $this->_array_SPSS;
	}

    // Zakesiramo vse bloke in vprasanja, ki imajo vklopljeno randomizacijo
	private function get_random () {
		
		// ce smo ze zakesirali vrednosti
		if ($this->_array_random != null) {
			return $this->_array_random;
		}

		$this->_array_random = array();
            
        
		// Preberemo vsa VPRASANJA ki imajo vklopljeno randomizacijo
		$_qry_random_spr = sisplet_query("SELECT s.id, s.variable FROM srv_spremenljivka s, srv_grupa g 
                                            WHERE g.ank_id='".$this->sid."' AND s.gru_id=g.id AND s.random='1' 
                                            ORDER BY s.vrstni_red");
		if (!$_qry_random_spr) {
			$this->trigerError('get_random', mysqli_error($GLOBALS['connect_db']));
		}

		# koliko zapicov
		$cnt_rows = mysqli_num_rows($_qry_random_spr);

		// Napolnimo array z randomiziranimi vprasanji
		if ($cnt_rows > 0) {
			while ($row = mysqli_fetch_array($_qry_random_spr)) {
					
				$this->_array_random[$row['id']]['id'] = $row['id'];
				$this->_array_random[$row['id']]['variable'] = $row['variable'];
				$this->_array_random[$row['id']]['type'] = 'spr';
			}
		}


		// Preberemo vse BLOKE ki imajo vklopljeno randomizacijo
		$_qry_random_blok = sisplet_query("SELECT i.id, i.random, i.label, i.number 
                                            FROM srv_if i, srv_branching b 
                                            WHERE b.ank_id='".$this->sid."' AND b.element_if=i.id AND (i.random>=0 OR i.random=-2) AND i.tip='1'");
		if (!$_qry_random_blok) {
			$this->trigerError('get_random',  mysqli_error($GLOBALS['connect_db']));
		}

		# koliko zapicov
		$cnt_rows = mysqli_num_rows($_qry_random_blok);

		// Napolnimo array z randomiziranimi bloki
		if ($cnt_rows > 0) {
            
            $prefix = '';

			while ($row = mysqli_fetch_array($_qry_random_blok)) {
					
				$this->_array_random[$row['id']]['id'] = $row['id'];
				$this->_array_random[$row['id']]['variable'] = $row['label'];
                $this->_array_random[$row['id']]['number'] = $row['number'];
                
                // Napolnimo string z id-ji blokov
                $this->_str_blocks .= $prefix.$row['id'];
                $prefix = ',';
		
				// Blok ima randomizirane bloke
				if($row['random'] == -2){
					$this->_array_random[$row['id']]['type'] = 'blok_blok';
				}
				// Blok ima randomizirana vprasanja
				else{
					$this->_array_random[$row['id']]['type'] = 'blok_spr';
				}					
			}
		}
    }

    /***** KONEC - KESIRANJE PODATKOV ANKETE (vprasanja, strani, gridi...) *****/
    


    /***** KESIRANJE ODGOVOROV POSAMEZNEGA RESPONDENTA *****/

    // Zakesiramo odgovore respondenta
    private function cache_data_respondent($string_user){

        if ($this->_str_questions != '') {

            // resetiramo POINTER-je - Array-je do podatkov
            $this->_array_user_grupa = null;
            $this->_array_data_vrednost = null;
            $this->_array_data_text = null;
            $this->_array_data_grids = null;
            $this->_array_data_check_grids = null;
            $this->_array_data_text_grid = null;
            $this->_array_data_rating = null;
            $this->_array_data_text_upload = null;
            $this->_array_data_map = null;
            $this->_array_data_heatmap = null;
            $this->_array_data_heatmap_regions = null;
            $this->_array_data_vrednost_cond = null;
            $this->_array_data_random = null;


            // polovimo id-je strani do katerih je prišel posamezen uporabnik
            $this->get_user_grupa($string_user);


            // Popravimo string za omejevanje userjev
            $_string_user = str_replace(array(' AND usr_id IN (', ')'), '', $string_user);
            $_string_user = explode(',', $_string_user);
            $string_user = ' AND usr_id BETWEEN '.$_string_user[0].' AND '.end($_string_user);

            // Zakesiramo vse podatke - ce ni nobenega vprasanja tega tipa v anketi, ne rabimo tega izvajat
            $this->cache_data_respondent_vrednost($string_user);
            $this->cache_data_respondent_text($string_user);

            // Kesiranje podatkov za srv_data_grid
            if((isset($this->_cnt_questions_types['6']) && $this->_cnt_questions_types['6'] > 0) 
                || (isset($this->_cnt_questions_types['16']) && $this->_cnt_questions_types['16'] > 0)
                || (isset($this->_cnt_questions_types['19']) && $this->_cnt_questions_types['19'] > 0)
                || (isset($this->_cnt_questions_types['20']) && $this->_cnt_questions_types['20'] > 0)
                || (isset($this->_cnt_questions_types['24']) && $this->_cnt_questions_types['24'] > 0)
            )
                $this->cache_data_respondent_grid($string_user);

            // Kesiranje podatkov za srv_data_checkgrid
            if((isset($this->_cnt_questions_types['6']) && $this->_cnt_questions_types['6'] > 0) 
                || (isset($this->_cnt_questions_types['16']) && $this->_cnt_questions_types['16'] > 0)
                || (isset($this->_cnt_questions_types['24']) && $this->_cnt_questions_types['24'] > 0)
            )
                $this->cache_data_respondent_check_grid($string_user);

            // Kesiranje podatkov za srv_data_textgrid
            if((isset($this->_cnt_questions_types['19']) && $this->_cnt_questions_types['19'] > 0) 
                || (isset($this->_cnt_questions_types['20']) && $this->_cnt_questions_types['20'] > 0)
                || (isset($this->_cnt_questions_types['24']) && $this->_cnt_questions_types['24'] > 0)
            )
                $this->cache_data_respondent_text_grid($string_user);

            // Kesiranje podatkov za srv_data_rating
            if(isset($this->_cnt_questions_types['17']) && $this->_cnt_questions_types['17'] > 0)
                $this->cache_data_respondent_rating($string_user);

            // Kesiranje podatkov za srv_data_text_upload
            if(isset($this->_cnt_questions_types['21']) && $this->_cnt_questions_types['21'] > 0)
                $this->cache_data_respondent_text_upload($string_user);

            // Kesiranje podatkov za srv_data_map
            if(isset($this->_cnt_questions_types['26']) && $this->_cnt_questions_types['26'] > 0)          
                $this->cache_data_respondent_map($string_user);

            // Kesiranje podatkov za srv_data_heatmap in srv_data_heatmap_regions
            if(isset($this->_cnt_questions_types['27']) && $this->_cnt_questions_types['27'] > 0){
                $this->cache_data_respondent_heatmap($string_user);
                $this->cache_data_respondent_heatmap_regions($string_user);
            }
   
            // Kesiranje podatkov za srv_data_vrednost_cond
            if((isset($this->_cnt_questions_types['2']) && $this->_cnt_questions_types['2'] > 0) 
                || (isset($this->_cnt_questions_types['27']) && $this->_cnt_questions_types['27'] > 0)
            )
                $this->cache_data_respondent_vrednost_cond($string_user);

            // Kesiranje podatkov za randomizirane vrednosti oz. randomizirana vprasanja v blokih
            $this->cache_data_respondent_random($string_user);
        }
    }

    // Zakesiramo case odgovorov po straneh za respondenta
	private function get_user_grupa($string_user) {

		if ($this->_str_groups != '') {

			$qry = sisplet_query("SELECT gru_id, usr_id, time_edit, preskocena FROM srv_user_grupa".$this->db_table." WHERE gru_id IN (".$this->_str_groups.") ".$string_user."");
			if (!$qry) {
				$this->trigerError('get_user_grupa',  mysqli_error($GLOBALS['connect_db']));
			}

            $this->_array_user_grupa = array();
            
			while ($row = mysqli_fetch_assoc($qry)) {
				$this->_array_user_grupa[(int)$row['usr_id']][(int)$row['gru_id']] = $row['time_edit'];
			}
		}
			
    }

    // Zakesiramo podatke tabele srv_data_vrednost
    private function cache_data_respondent_vrednost($string_user){

        if ($this->_array_data_vrednost === null) {

            $_qry_data_vrednost = sisplet_query("SELECT spr_id, vre_id, usr_id, loop_id 
                                                    FROM srv_data_vrednost".$this->db_table." 
                                                    WHERE spr_id IN (".$this->_str_questions.")" . $string_user);
            if (!$_qry_data_vrednost) {
                $this->trigerError('cache_data_respondent_vrednost',  mysqli_error($GLOBALS['connect_db']));
            }	
     			
			if (mysqli_num_rows($_qry_data_vrednost) > 0) {

				while (list($spr_id, $vre_id, $usr_id,$loop_id) = mysqli_fetch_row($_qry_data_vrednost)) {

					$loop_id = ($loop_id == null || $loop_id == '') ? 0 : $loop_id;
					$this->_array_data_vrednost[(int)$usr_id][$spr_id.'_'.$loop_id][(int)$vre_id] = (int)$vre_id;
				}
            }
        } 
    }

    // Vrnemo vrednost za uid in spr_id za tabelo srv_data_vrednost
	private function get_array_data_vrednost ($uid, $spid) {
        
        if (isset($this->_array_data_vrednost[$uid][$spid]))
			return $this->_array_data_vrednost[$uid][$spid];
        else
			return array();                
	}

    // Zakesiramo podatke tabele srv_data_text
    private function cache_data_respondent_text($string_user){

        if ($this->_array_data_text === null) {

            $_qry_data_text = sisplet_query("SELECT spr_id, vre_id, usr_id, REPLACE(REPLACE(REPLACE(text,'\n',' '),'\r',' '),'|',' ') as text, REPLACE(REPLACE(REPLACE(text2,'\n',' '),'\r',' '),'|',' ') as text2, loop_id 
                                                FROM srv_data_text".$this->db_table." 
                                                WHERE spr_id IN (".$this->_str_questions.") ".$string_user);
            if (!$_qry_data_text) {
                $this->trigerError('cache_data_respondent_text',  mysqli_error($GLOBALS['connect_db']));
            }
            
            if (mysqli_num_rows($_qry_data_text) > 0) {

                while (list($spr_id, $vre_id, $usr_id, $text, $text2, $loop_id) = mysqli_fetch_row($_qry_data_text)) {
                    $loop_id = ($loop_id == null || $loop_id == '') ? 0 : $loop_id;
                    
                    // Addslashes je potreben za analize
                    $this->_array_data_text[(int)$usr_id][$spr_id.'_'.$loop_id][(int)$vre_id] = array(
                        'text'=>trim(addslashes(strip_tags($text))), 
                        'text2'=>trim(addslashes(strip_tags($text2)))
                    );
                }
            }
        }
    }

    // Vrnemo vrednost za uid in spr_id za tabelo srv_data_text
	private function get_array_data_text ($uid, $spid) {
		
		if (isset($this->_array_data_text[$uid][$spid]))
			return $this->_array_data_text[$uid][$spid];
        else
			return array();
	}

    // Zakesiramo podatke tabele srv_data_grid
    private function cache_data_respondent_grid($string_user){
                
        if ($this->_array_data_grids === null) {

            $_qry_data_grids = sisplet_query("SELECT spr_id, vre_id, usr_id, grd_id, loop_id 
                                                FROM srv_data_grid".$this->db_table." 
                                                WHERE spr_id IN (".$this->_str_questions.") ".$string_user);
            if (!$_qry_data_grids) {
                $this->trigerError('cache_data_respondent_grid',  mysqli_error($GLOBALS['connect_db']));
            }
                                
            if (mysqli_num_rows($_qry_data_grids) > 0) {

                while (list($spr_id, $vre_id, $usr_id, $grd_id, $loop_id) = mysqli_fetch_row($_qry_data_grids)) {

                    $loop_id = ($loop_id == null || $loop_id == '') ? 0 : $loop_id;
                    $this->_array_data_grids[(int)$usr_id][$spr_id.'_'.$loop_id ][(int)$vre_id] = (int)$grd_id;
                }
            }
		}
    }

    // Vrnemo vrednost za uid in spr_id za tabelo srv_data_grids
	private function get_array_data_grids ($uid, $spid) {

		if (isset($this->_array_data_grids[$uid][$spid]))
			return $this->_array_data_grids[$uid][$spid];
		else
			return array();			
	}

    // Zakesiramo podatke tabele srv_data_checkgrid
    private function cache_data_respondent_check_grid($string_user){
            
        if ($this->_array_data_check_grids === null) {

            $_qry_data_check_grids = sisplet_query("SELECT spr_id, vre_id, usr_id, grd_id, loop_id 
                                                            FROM srv_data_checkgrid".$this->db_table." 
                                                            WHERE spr_id IN (".$this->_str_questions.") ".$string_user);
            if (!$_qry_data_check_grids) {
                $this->trigerError('cache_data_respondent_check_grid', mysqli_error($GLOBALS['connect_db']));
            }
                                
            if (mysqli_num_rows($_qry_data_check_grids) > 0) {

                while (list($spr_id, $vre_id, $usr_id, $grd_id, $loop_id) = mysqli_fetch_row($_qry_data_check_grids)) {

                    $loop_id = ($loop_id == null || $loop_id == '') ? 0 : $loop_id;
                    $this->_array_data_check_grids[(int)$usr_id][$spr_id.'_'.$loop_id][(int)$vre_id][(int)$grd_id] = (int)$grd_id;
                }
            }
		}
    }

    // Vrnemo vrednost za uid in spr_id za tabelo srv_data_checkgrid
	private function get_array_data_check_grids ($uid, $spid) {
        
        if (isset($this->_array_data_check_grids[$uid][$spid]))
			return $this->_array_data_check_grids[$uid][$spid];
		else
			return array();
	}

    // Zakesiramo podatke tabele srv_data_textgrid
    private function cache_data_respondent_text_grid($string_user){
        
        if ($this->_array_data_text_grid === null) {

            $_qry_data_text_grid = sisplet_query("SELECT spr_id, vre_id, usr_id, grd_id, REPLACE(REPLACE(REPLACE(REPLACE(text, '\\\\', '/'),'\n',' '),'\r','<br>'),'|',' ') as text, loop_id 
                                                    FROM srv_data_textgrid".$this->db_table." 
                                                    WHERE spr_id IN (".$this->_str_questions.") ".$string_user);
            if (!$_qry_data_text_grid) {
                $this->trigerError('cache_data_respondent_text_grid',  mysqli_error($GLOBALS['connect_db']));
            }
            
            if (mysqli_num_rows($_qry_data_text_grid) > 0) {

                while ( list($spr_id, $vre_id, $usr_id, $grd_id, $text, $loop_id) = mysqli_fetch_row($_qry_data_text_grid)) {

                    $loop_id = ($loop_id == null || $loop_id == '') ? 0 : $loop_id;
                    $this->_array_data_text_grid[(int)$usr_id][$spr_id.'_'.$loop_id][(int)$vre_id][(int)$grd_id] = trim(strip_tags($text));
                }
            }
		}
    }

    // Vrnemo vrednost za uid in spr_id za tabelo srv_data_textgrid
	private function get_array_data_text_grid ($uid, $spid) {
        
        if (isset($this->_array_data_text_grid[$uid][$spid]))
			return $this->_array_data_text_grid[$uid][$spid];
		else
			return array();
	}

    // Zakesiramo podatke tabele srv_data_rating
    private function cache_data_respondent_rating($string_user){
           
        if ($this->_array_data_rating === null) {

            $_qry_data_rating = sisplet_query("SELECT spr_id, vre_id, usr_id, vrstni_red, loop_id 
                                                FROM srv_data_rating 
                                                WHERE spr_id IN (".$this->_str_questions.") ".$string_user);
            if (!$_qry_data_rating) {
                $this->trigerError('cache_data_respondent_rating', mysqli_error($GLOBALS['connect_db']));
            }
                        
            if (mysqli_num_rows($_qry_data_rating) > 0) {
                
                while (list($spr_id, $vre_id, $usr_id, $vrstni_red, $loop_id) = mysqli_fetch_row($_qry_data_rating)) {

                    $loop_id = ($loop_id == null || $loop_id == '') ? 0 : $loop_id;
                    $this->_array_data_rating[(int)$usr_id][$spr_id.'_'.$loop_id][(int)$vre_id] = (int)$vrstni_red;
                }
            }
        }
    }

    // Vrnemo vrednost za uid in spr_id za tabelo srv_data_rating
	private function get_array_data_rating ($uid, $spid) {
        
        if (isset($this->_array_data_rating[$uid][$spid]))
			return $this->_array_data_rating[$uid][$spid];
		else
			return array();
	}
    
    // Zakesiramo podatke tabele srv_data_upload
    private function cache_data_respondent_text_upload($string_user){

        if ($this->_array_data_text_upload === null) {

            $_qry_data_text_upload = sisplet_query("SELECT usr_id, code, filename 
                                                        FROM srv_data_upload 
                                                        WHERE ank_id='".$this->sid."' ".$string_user);
            if (!$_qry_data_text_upload) {
                $this->trigerError('cache_data_respondent_text_upload', mysqli_error($GLOBALS['connect_db']));
            }
            
            if (mysqli_num_rows($_qry_data_text_upload) > 0) {

                while (list($usr_id, $code, $filename) = mysqli_fetch_row($_qry_data_text_upload)) {
                    $this->_array_data_text_upload[$code] = strip_tags($filename);
                }
            }
		}
    }

    // Vrnemo vrednost za filename za posamezen code za tabelo srv_data_upload
	private function get_array_data_text_upload ($_code) {
        
        if (isset($this->_array_data_text_upload[$_code]))
			return $this->_array_data_text_upload[$_code];
		else
			return 'Download';
	}

    // Zakesiramo podatke tabele srv_data_map
    private function cache_data_respondent_map($string_user){
              
        if ($this->_array_data_map === null) {

            $_qry_data_map = sisplet_query("SELECT spr_id, usr_id, REPLACE(REPLACE(REPLACE(address,'\n',' '),'\r',' '),'|',' ') as address, REPLACE(REPLACE(REPLACE(text,'\n',' '),'\r',' '),'|',' ') as text, lat, lng, loop_id 
                                                FROM srv_data_map 
                                                WHERE spr_id IN (".$this->_str_questions.") ".$string_user);
            if (!$_qry_data_map) {
                $this->trigerError('cache_data_respondent_map', mysqli_error($GLOBALS['connect_db']));
            }

            $i = 0;
			if (mysqli_num_rows($_qry_data_map) > 0) {

				while (list($spr_id, $usr_id, $address, $text, $lat, $lng, $loop_id) = mysqli_fetch_row($_qry_data_map)) {
                   
                    $loop_id = ($loop_id == null || $loop_id == '') ? 0 : $loop_id;
                    
                    // Ce respondent ni vnesel vrednosti v infowindow
                    if($text == '' || $text == null) $text = '-1';
                    
                    // Addslashes je potreben za analize
                    if($this->_array_data_map[(int)$usr_id][$spr_id.'_'.$loop_id]){
                        $this->_array_data_map[(int)$usr_id][$spr_id.'_'.$loop_id]['izpis'][$i] = array(
                            'address'=>addslashes(strip_tags($address)), 
                            'vrednost'=>addslashes(strip_tags($text)), 
                            'koordinate'=> $lat . ', ' . $lng);
                    }
                    else{
                        $this->_array_data_map[(int)$usr_id][$spr_id.'_'.$loop_id] = array(
                            'izpis'=>array(array(
                                            'address'=>addslashes(strip_tags($address)), 
                                            'vrednost'=>addslashes(strip_tags($text)),
                                            'koordinate'=>$lat . ', ' . $lng)),
                            'spr_id' => $spr_id, 'usr_id' => $usr_id, 'loop_id' => $loop_id);
                    }

                    $i++;
				}
			}
        } 
    }

    // Vrnemo vrednost za uid in spr_id za tabelo srv_data_map
	private function get_array_data_map ($uid, $spid) {
		
		if (isset($this->_array_data_map[$uid][$spid]))
			return $this->_array_data_map[$uid][$spid];
        else
			return array();
	}

    // Zakesiramo podatke tabele srv_data_heatmap
    private function cache_data_respondent_heatmap($string_user){
        
        if ($this->_array_data_heatmap === null) {

            $_qry_data_heatmap = sisplet_query("SELECT spr_id, usr_id, REPLACE(REPLACE(REPLACE(address,'\n',' '),'\r',' '),'|',' ') AS address, REPLACE(REPLACE(REPLACE(text,'\n',' '),'\r',' '),'|',' ') as text, lat, lng, loop_id 
                                                    FROM srv_data_heatmap 
                                                    WHERE spr_id IN (".$this->_str_questions.") ".$string_user);
            if (!$_qry_data_heatmap) {
                $this->trigerError('cache_data_respondent_heatmap', mysqli_error($GLOBALS['connect_db']));
            }

            $i = 0;
			if (mysqli_num_rows($_qry_data_heatmap)) {

				while (list($spr_id, $usr_id, $address, $text, $lat, $lng, $loop_id) = mysqli_fetch_row($_qry_data_heatmap)) {

                    $loop_id = ($loop_id == null || $loop_id == '') ? 0 : $loop_id;
                    
                    // ce respondent ni vnesel vrednosti v infowindow
                    if($text == '' || $text == null) $text = '-1';
                    
                    // adslashes je potreben za analize
                    if($this->_array_data_heatmap[(int)$usr_id][$spr_id.'_'.$loop_id]){
                        $this->_array_data_heatmap[(int)$usr_id][$spr_id.'_'.$loop_id]['izpis'][$i] = array(
                            'address'=>addslashes(strip_tags($address)), 
                            'vrednost'=>addslashes(strip_tags($text)), 
                            'koordinate'=> $lat . ', ' . $lng);
                    }
                    else{
                        $this->_array_data_heatmap[(int)$usr_id][$spr_id.'_'.$loop_id] = array(
                            'izpis'=>array(array(
                                            'address'=>addslashes(strip_tags($address)), 
                                            'vrednost'=>addslashes(strip_tags($text)),
                                            'koordinate'=>$lat . ', ' . $lng)),
                            'spr_id' => $spr_id, 'usr_id' => $usr_id, 'loop_id' => $loop_id);
                    }

                    $i++;
				}
			}
		}
    }

    // Vrnemo vrednost za uid in spr_id za tabelo srv_data_heatmap
	private function get_array_data_heatmap ($uid, $spid) {
		
		if (isset($this->_array_data_heatmap[$uid][$spid]))
			return $this->_array_data_heatmap[$uid][$spid];
		else
			return array();
    }

    // Zakesiramo podatke tabele srv_data_heatmap_regions
    private function cache_data_respondent_heatmap_regions($string_user){
            
        if ($this->_array_data_heatmap_regions === null) {

            $spremenljivke_id = explode(",", $this->_str_questions);
            $_qry_data_heatmap_regions = array();
            
            foreach ($spremenljivke_id AS $val) {
                $str_query_regions = "SELECT id, vre_id, spr_id, region_name, region_coords FROM srv_hotspot_regions WHERE spr_id =".$val.' ';
                array_push($_qry_data_heatmap_regions, $str_query_regions);
            }

            foreach($_qry_data_heatmap_regions as $query){

                $regionsQuery = sisplet_query($query);			
                if ($regionsQuery === null) {
                    $this->trigerError('cache_data_respondent_heatmap_regions', mysqli_error($GLOBALS['connect_db']));
                }

                $i = 0;
                if (mysqli_num_rows($regionsQuery) > 0) {

                    while (list($region_id, $vre_id, $spr_id, $region_name, $region_coords) = mysqli_fetch_row($regionsQuery)) {   
                        
                        // adslashes je potreben za analize
                        $this->_array_data_heatmap_regions[(int)$spr_id][$i] = array(
                            'region_name'   => addslashes(strip_tags($region_name)), 
                            'region_coords' => $region_coords);

                        $i++;
                    }
                }			
            }
 		}
    }

    // Vrnemo vrednost za uid in spr_id za tabelo srv_data_heatmap_regions
	private function get_array_data_heatmap_regions ($uid, $spid) {
					
        if (isset($this->_array_data_heatmap_regions[$uid][$spid]))
           return $this->_array_data_heatmap_regions[$uid][$spid];
        else
           return array();
    }

    // Zakesiramo podatke tabele srv_data_vrednost_cond
    private function cache_data_respondent_vrednost_cond($string_user){

        if ($this->_array_data_vrednost_cond === null) {

            $_qry_data_vrednost_cond = sisplet_query("SELECT spr_id, vre_id, usr_id, text, loop_id 
                                                        FROM srv_data_vrednost_cond 
                                                        WHERE spr_id IN (".$this->_str_questions.") ".$string_user);
            if (!$_qry_data_vrednost_cond) {
                $this->trigerError('cache_data_respondent_vrednost_cond', mysqli_error($GLOBALS['connect_db']));
            }
       
			if (mysqli_num_rows($_qry_data_vrednost_cond) > 0) {

				while ( list($spr_id, $vre_id, $usr_id, $text, $loop_id) = mysqli_fetch_row($_qry_data_vrednost_cond)) {
					$loop_id = ($loop_id == null || $loop_id == '') ? 0 : $loop_id;
                    
                    // adslashes je potreben za analize
					$this->_array_data_vrednost_cond[(int)$usr_id][$spr_id.'_'.$loop_id][(int)$vre_id] = addslashes(strip_tags($text));
				}
			}
		}
    }

    // Vrnemo vrednost za uid in spr_id za tabelo srv_data_vrednost_cond
	private function get_array_data_vrednost_cond ($uid, $spid) {
        
        if (isset($this->_array_data_vrednost_cond[$uid][$spid]))
			return $this->_array_data_vrednost_cond[$uid][$spid];
        else
			return array();
	}

    // Zakesiramo podatke randomiziranih vprasanj
    private function cache_data_respondent_random($string_user){

        // Ce smo ze zakesirali ne rabimo naprej
        if ($this->_array_data_random === null){
                        
            // Najprej dobimo random podatke za randomizacijo znotraj vprasanj
            $_qry_data_random = sisplet_query("SELECT spr_id, usr_id, vrstni_red 
                                                FROM srv_data_random_spremenljivkaContent 
                                                WHERE spr_id IN (".$this->_str_questions.") ".$string_user);
                                                //WHERE ".$string_user);
            if (!$_qry_data_random) {
                $this->trigerError('cache_data_respondent_random', mysqli_error($GLOBALS['connect_db']));
            }

            if (mysqli_num_rows($_qry_data_random) > 0) {
                while($row1 = mysqli_fetch_array($_qry_data_random)){
                    $this->_array_data_random[$row1['usr_id']]['spr'][$row1['spr_id']] = $row1['vrstni_red'];
                }
            }


            // Dobimo se random podatke za randomizacijo znotraj blokov
            if($this->_str_blocks != ''){

                $_qry_data_random = sisplet_query("SELECT block_id, usr_id, vrstni_red 
                                                    FROM srv_data_random_blockContent 
                                                    WHERE block_id IN (".$this->_str_blocks.") ".$string_user);
                                                    //WHERE ".$string_user);
                if (!$_qry_data_random) {
                    $this->trigerError('cache_data_respondent_random', mysqli_error($GLOBALS['connect_db']));
                }

                if (mysqli_num_rows($_qry_data_random) > 0) {

                    while($row2 = mysqli_fetch_array($_qry_data_random)){

                        // Zgradimo string z labelami vprasanj za prikaz v podatkih
                        $vrstni_red_string = '';

                        // Spremenljivke v blokih
                        if($this->_array_random[$row2['block_id']]['type'] == 'blok_spr'){

                            // Pridobimo imena vprasanj za izpis
                            $sqlLabele1 = sisplet_query("SELECT variable 
                                            FROM srv_spremenljivka
                                            WHERE id IN (".$row2['vrstni_red'].")
                                            ORDER BY FIND_IN_SET(id, '".$row2['vrstni_red']."')
                                        ");
                                        
                            while($rowLabele1 = mysqli_fetch_array($sqlLabele1)){
                                $vrstni_red_string .= $rowLabele1['variable'].',';
                            }
                        }
                        // Bloki v blokih
                        else{
                            // Pridobimo imena blokov za izpis
                            $sqlLabele2 = sisplet_query("SELECT number 
                                            FROM srv_if
                                            WHERE id IN (".$row2['vrstni_red'].")
                                            ORDER BY FIND_IN_SET(id, '".$row2['vrstni_red']."')
                                        ");

                            if($sqlLabele2){
                                while($rowLabele2 = mysqli_fetch_array($sqlLabele2)){
                                    $vrstni_red_string .= 'B'.$rowLabele2['number'].',';
                                }
                            }
                        }	

                        $vrstni_red_string = substr($vrstni_red_string, 0, -1);
                        $this->_array_data_random[$row2['usr_id']]['block'][$row2['block_id']] = $vrstni_red_string;
                    }
                }
            }
        }
    }

    // Vrnemo podatke vrstnega reda za userja in element (vprasanje ali blok)
	private function get_array_data_random ($uid, $el_id, $el_type) {

		if (isset($this->_array_data_random[$uid][$el_type][$el_id]))
			return $this->_array_data_random[$uid][$el_type][$el_id];
		else
			return '';
    }
    
    /***** KONEC - KESIRANJE ODGOVOROV POSAMEZNEGA RESPONDENTA *****/



    // Iz datoteke pobrisemo response, ki so zastareli / pobrisani
    private function deleteUsers() {

		// Datoteka s podatki
        $f1 = $this->data_file_name;
        
		// Datoteka kam zapišemo user id-je ki jih je potrebno pobrisat
        $f2 = $this->data_file_name.'.todel';
        
		// Datoteka kamor začasno shranimo original
		$f3 = $this->data_file_name.'_'.time().'.orig';

		if ((int)$this->sid > 0 && file_exists($f1)) {
            
            try {

                // Preberemo max time iz datoteke in uporabimo manjšega
                if (IS_WINDOWS) {
                    $command = 'awk -F"|" "BEGIN {max = 0} {if ('.TIME_FIELD.' > max && '.TIME_FIELD.' > 0) max='.TIME_FIELD.' } END {print max}" '.$f1;
                } 
                else {
                    $command = 'awk -F\'|\' \'BEGIN {max = 0} {if ('.TIME_FIELD.' > max && '.TIME_FIELD.' > 0) max='.TIME_FIELD.' } END {print max}\' '.$f1;
                }
                $user_time_from_file = shell_exec($command);
                
                $inkremental_user_limit = " AND u.time_edit > FROM_UNIXTIME('".max((int)$this->data_file_time,(int)$user_time_from_file)."') ";
                    
                // Za inkrementalno brisanje datotek
                $qry_users_deleted = sisplet_query("SELECT u.id AS usr_id  FROM srv_user AS u WHERE u.ank_id='".$this->sid."' ".$inkremental_user_limit);
                if (mysqli_num_rows($qry_users_deleted) > 0) {
                    
                    // Zapišemo v datoteko idje respondentov, ki jih brišemo
                    $file = fopen($f2, 'w');
                    
                    while (list($usr_id) = mysqli_fetch_row($qry_users_deleted)) {
                        fwrite($file, $usr_id . "\n");
                    }
                    
                    fclose($file);
                    
                    if (IS_WINDOWS) {
                        $cmd = 'cp '.$f1.' '.$f3.' && awk -F"|" "NR==FNR {++a[$0]; next} { f = $1; if (!a[$1]) print $0}" '.$f2.' '.$f3.' > '.$f1;
                    } 
                    else {
                        $cmd = 'cp '.$f1.' '.$f3.' && awk -F\'|\' \'NR==FNR {++a[$0]; next} { f = $1; if (!a[$1]) print $0}\' '.$f2.' '.$f3.' > '.$f1;
                    }

                    $out_command = shell_exec($cmd);
                }		

                // Ce je vse ok, pobrišemo datoteke
                if (file_exists($f1)) {
                   
                    // Pobrišemo datoteko z userji katere smo brisali
                    if (file_exists($f2)) {
                        unlink($f2);
                    }
                    
                    // Pobrišemo backup datoteko
                    if (file_exists($f3)) {
                        unlink($f3);
                    }
                }
            } 
            catch (Exception $e) {
            }
		}
    }

    // Resetiramo vse pointerje   
    private function cleanup() {
		
		$this->noErrors = true;
		
        $this->_HEADER = null;
        
		$this->_str_groups = null;		# cache string za strani
        $this->_cnt_groups = 0;			# cache število za strani
        
		$this->_str_questions = null; 	# cache string za vprašanja
        $this->_cnt_questions = 0; 		# cache za število vprašanj
        
		$this->_qry_users = null;		# cache query za vprašanja
        $this->_str_users = null;		# cache string za user_id ji
        $this->_cnt_all_users = 0; 		# cache za število userjev
        
		$this->_array_vrednosti = null;	# cache array z vrednostmi   
		$this->_array_gridi = null;		# cache array z gridi
        $this->_array_random = null;	# cache array z random vprasanji in bloki
        $this->_array_SPSS = null;			# zakeširamo array usergrup za uporabnika
		$this->_array_user_grupa = null;	# zakeširamo array usergrup za uporabnika
		$this->_array_users_from_CMS = null;
                
		$this->_array_data_vrednost = null;
		$this->_array_data_text = null;
		$this->_array_data_grids = null;
		$this->_array_data_check_grids = null;
		$this->_array_data_text_grid = null;
		$this->_array_data_rating = null;
        $this->_array_data_text_grid = null;
        $this->_array_data_text_upload = null;
        $this->_array_data_map = null;
        $this->_array_data_heatmap = null;
        $this->_array_data_heatmap_regions = null;
        $this->_array_data_vrednost_cond = null;
        $this->_array_data_random = null;
    }
    
    
    
    // Zakesiramo in vrnemo respondente, ki so tudi prijavljeni v 1ki (ce ima anketa to nastavitev vklopljeno)
    private function get_user_CMS_email($user_from_cms_id) {

		// če še niammo arraya z e-maili
		if ($this->_array_users_from_CMS == null) {
            
            // polovimo emaile uporrabnikov iz CMS
            $this->_array_users_from_CMS = array();

            $qry = sisplet_query("SELECT su.user_id, u.email 
                                    FROM srv_user AS su JOIN users AS u ON su.user_id = u.id 
                                    WHERE su.ank_id = '".$this->sid."' AND su.preview='0' AND su.deleted='0' AND su.user_id > 0");
            while ( $row = mysqli_fetch_assoc($qry)) {
                $this->_array_users_from_CMS[$row['user_id']] = $row['email'];
            }
        }

        // ko smo že polovili e-maile
        if (is_array($this->_array_users_from_CMS) && isset($this->_array_users_from_CMS[$user_from_cms_id])) {
            return $this->_array_users_from_CMS[$user_from_cms_id];
        } 
        else {
            return '';
        }
    }
    
    // Pripravimo missing vrednosti za anketo
	private function setSurveyMissingValues() {
        
        $smv = new SurveyMissingValues($sid);
		$smv -> Init();
		
        $_sys_missings = $smv->GetSurveyMissingValues();
        
		if (is_array($_sys_missings) && count($_sys_missings) > 0){
            
            foreach ($_sys_missings[1] AS $_sys_missing) {
                
                // nastavimo mapping za rekodiranje sistemskih vrednosti
                $this->sysMissingMap[$_sys_missing['defSysVal']] = $_sys_missing['value'];
            }
        }
    }

    // Prestejemo stevilo vrstic v datoteki
	private function getLinesCnt(){

		if (IS_WINDOWS) {
			$command = 'awk "NF != 0 {++count} END {print count}" '.$this->data_file_name;
        } 
        else {
			$command = 'awk \'NF != 0 {++count} END {print count}\' '.$this->data_file_name;
        }
        
        $lines = shell_exec($command);
        
		return (int)$lines;
    }
    
    // Ugotovimo do katere spremenljivke zamenjujemo vrednosti
	private function calculateValuesToChange($uid) {

		$result = 0;
		
		if (count($this->_user_spr_answer_count['spremenljivke']) == 0) {
			return $result;
        }
        
		if ($this->_user_spr_answer_count['last_seen'] == $this->_user_spr_answer_count['cnt']) {
			return $result;
        }
        
		if (count($this->_user_spr_answer_count['spremenljivke']) == 0) {
			return $result;
        }
        
		foreach ($this->_user_spr_answer_count['spremenljivke'] AS $cnt) {

            if ($this->_user_spr_answer_count['last_seen'] > $result) {
				$result += $cnt;
			}
		}
		 
		return $this->_user_spr_answer_count['cnt'] - $result;
    }
        

    // Rekodiranje odgovora
    private function recode_answer($spr_id, $answer, $uid) {

		$this->addUserSprAnswerCount($spr_id, $answer, $uid);
		
		// Najprej preverimo ali imamo mapping za sistemske missige
		$answer = isset($this->sysMissingMap[$answer]) && $this->sysMissingMap[$answer] != null 
			? $this->sysMissingMap[$answer]
            : $answer;
            
        $_recoded = $answer;
        
		if ( $this->_array_recode === null) {
			$this->_array_recode = array();

			// Polovimo vse spremenljivke za katere moramo rekodirat
			$sqlSelect = sisplet_query("SELECT spr_id, search, value, operator FROM srv_recode WHERE ank_id = '".$this->sid."' AND enabled='1' ORDER BY vrstni_red");
			if (!$sqlSelect) {
				$this->trigerError('recode_answer', mysqli_error($GLOBALS['connect_db']));
            }
            
			if (mysqli_num_rows($sqlSelect)) {
				while ($rowSelect = mysqli_fetch_assoc($sqlSelect)) {
					$this->_array_recode[$rowSelect['spr_id']][] = array('s'=>$rowSelect['search'],'v'=>$rowSelect['value'],'o'=>$rowSelect['operator']);
				}
			}
        }
        
		if (isset($this->_array_recode[$spr_id])) {

            $recoded = false;
            
			if (isset($this->_array_recoded[$spr_id][$answer])) {
				return $this->_array_recoded[$spr_id][$answer];
            } 
            else if (count($this->_array_recode[$spr_id]) > 0 && $recoded == false) {
                
                foreach ($this->_array_recode[$spr_id] AS $k => $do_recode) {
                    
                    if ( $answer == $do_recode['s'] || (int)$do_recode['o'] > 0 && $recoded == false) {
						$original = $_recoded;
						$_recoded = $this->check_recode($_recoded,$do_recode['s'],$do_recode['v'],$do_recode['o']);
                        
                        if ($original != $_recoded) {
							$recoded = true;
						}
					}
                }
                
				$this->_array_recoded[$spr_id][$answer] = $_recoded;
			}
		}

		// Vrnemo rezultat
		return $_recoded;
    }

    // Rekodiranje odgovora - stejemo zadnmi veljaven odgovor
    private function addUserSprAnswerCount($spr, $answer, $uid) {

        // štejemo zadnji veljaven odgovor
        $this->_user_spr_answer_count['cnt']++;
        
		if ($answer != -1 && $answer != -4 && $answer !== '-2d') {
            
            // sistemskih in skritih ne upoštevamo
            if ($this->AllQuestionsData[$spr]['sistem'] == 1 || $this->AllQuestionsData[$spr]['visible'] == 0) {
                // preskočimo
            } 
            else {
			    $this->_user_spr_answer_count['last'] = $this->_user_spr_answer_count['cnt']; 
			    $this->_user_spr_answer_count['last_seen'] = $this->_user_spr_answer_count['cnt']; 
            }
		}	
		
		// Pogledamo se ce je respondent videl vprasanje (tudi ce je -1, ker ga potem ne smemo spremenit v -3)					
		if ($answer == -1 || $answer === '-2d') {
            if (is_array($this->_array_user_grupa[$uid]) && array_key_exists($this->AllQuestionsData[$spr]['gru_id'], $this->_array_user_grupa[$uid])) {
				$this->_user_spr_answer_count['last_seen'] = $this->_user_spr_answer_count['cnt'] - 1; 
            }
		}	
		
		if (!isset($this->_user_spr_answer_count['spremenljivke'][$spr])) {
			$this->_user_spr_answer_count['spremenljivke'][$spr] = 0;
		}
		
		$this->_user_spr_answer_count['spremenljivke'][$spr]++;
    }

    // Rekodiranje odgovora - preverimo glede na operator
	private function check_recode ($answer, $search, $value, $operand) {

        $_recoded = $answer;
        
		switch ($operand) {
            
			case 0:	# ==
				$_recoded = $answer == $search ? $value : $answer;
                break;
                
			case 1:	# <>
				$_recoded = $answer != $search ? $value : $answer;
                break;
                
			case 2:	# <
				$_recoded = $answer < $search ? $value : $answer;
                break;
                
			case 3:	# >
				$_recoded = $answer > $search ? $value : $answer;
                break;
                
			case 4:	# <=
				$_recoded = $answer <= $search ? $value : $answer;
				break;
            
            case 5:	# >=
				$_recoded = $answer >= $search ? $value : $answer;
				break;
            
            case 6:	# >=
				$search = explode(',',$search);
				$s1 = (int)$search[0];
				$s2 = (int)$search[1];
				$_recoded = (int)$answer >= $s1 && (int)$answer <= $s2 ? $value : $answer;
				break;
        }
        
		return $_recoded;
    }


    // Heatmap - pretvori polje s tockami obmocja v ustrezno obliko
	private function convertPolyString($polypoints){

		$poly = array();
		$polyObjectArray = array();
		$j = 0;
		$poly = explode(',',$polypoints);

		for($i = 0; $i<sizeof($poly); $i++){
             
            if($i == 0 || $i%2 == 0){
				$tmpX = (int)$poly[$i];
            }
            else{
                $tmpY = (int)$poly[$i];
                
				$polyObjectArray[$j]["x"] = $tmpX;
                $polyObjectArray[$j]["y"] = $tmpY;
                
				$j++;
			}
        }
        
		return $polyObjectArray;
    }
    
    // Heatmap - preveri, ali je posamezna tocka znotraj trenutnega obmocja
 	private function insidePoly($poly, $pointx, $pointy){

        $inside = false;
        		
  		for ($i = 0, $j = sizeof($poly) - 1; $i < sizeof($poly); $j = $i++) {

            if( (($poly[$i]["y"] > $pointy) != ($poly[$j]["y"] > $pointy)) && ($pointx < ($poly[$j]["x"]-$poly[$i]["x"]) * ($pointy-$poly[$i]["y"]) / ($poly[$j]["y"]-$poly[$i]["y"]) + $poly[$i]["x"]) )
                $inside = !$inside;		
        }
        
		return $inside;
    }
    
    // Heatmap - priprava spremenljivk za koordinate točk
	private function preparePointCoords($answerKoo, $returnPointx, $returnPointy){

		$coordsWithComma = explode('<br>',$answerKoo);
		$pointx = array();
		$pointy = array();
		
		for($i = 1; $i<sizeof($coordsWithComma); $i++){

            // Vecdimenzionalno polje
			$coords[$i-1] = explode(',',$coordsWithComma[$i]);	
		}

		// Loop-anje skozi vecdimenzionalno polje
		$i = 0;
		$j = 0;
		foreach ($coords as $coord) {
            
            foreach ($coord as $coo) {			
                
                if($i == 0 || $i%2 == 0){
					$tmpX = (int)$coo;
                }
                else{
					$tmpY = (int)$coo;
					$pointx[$j]["x"] = $tmpX;
					$pointy[$j]["y"] = $tmpY;
					$j++;
                }
                
				$i++;
			}
		}
		
		if ($returnPointx == 1){
			return $pointx;
        }
        
		if ($returnPointy == 1){
			return $pointy;
		}
	}


    // Zapisemo error in oznacimo da imamo error
	private function trigerError($what, $error) {

		$this->noErrors = false;
		$this->errors[][$what] = $error;
		
		return;
	}

    // Vrnemo error
	private function getError() {
		return array('error'=>$this->errors);
    }

    // Shranimo checkpoint za merjenje performanca - zaenkrat onemogoceno (samo za testiranje)
    private function setLogCheckpoint($checkpoint_name){
        global $admin_type;

        if(true || $admin_type != 0)
            return;

        $this->log[$checkpoint_name]['time'] = microtime(true);
        $this->log[$checkpoint_name]['memory'] = memory_get_usage();
        $this->log[$checkpoint_name]['memory_peak'] = memory_get_peak_usage();        
    }

    // Zapisemo checkpointe v logfile - zaenkrat onemogoceno (samo za testiranje)
    private function saveLogCheckpoints(){

        if(true || $admin_type != 0)
            return;

        $SL = new SurveyLog();

        $cnt = 0;
        foreach($this->log as $checkpoint => $data){

            $time = ($cnt == 0) ? 'Start: '.$data['time'] : 'Elapsed: '.($data['time'] - $this->log['start']['time']);
            $memory = 'Memory: '.((int)$data['memory'] / 1024 / 1000).' MB';
            $memory_peak = 'Memory peak: '.((int)$data['memory_peak'] / 1024 / 1000).' MB';

            $message = ' TESTING data collection for survey '.$this->sid.' - checkpoint '.$checkpoint.' - '.$time.', '.$memory.', '.$memory_peak;

            $SL->addMessage(SurveyLog::DEBUG, $message);

            $cnt++;
        }

        $SL->write();	
    }
}
?>