<?php 

/**
 *
 * Created on 17.12.2019
 * 
 * @author: Peter Hrvatin
 * 
 * Class za upravljanje z datotekami za podatke (preveri status datoteke, zaklepanje datoteke, klice kreiranje, updatanje...)
 *
*/

class SurveyDataFile {
	
    private static $instance;
    
    private $anketa;
    private $checked;
    
    private $header_file_name;                      // Ime header datoteke
    private $data_file_name;                        // Ime datoteke s podatki
    private $head_file_time = 0;                    // Cas zadnjega kreiranja header datoteke
    private $data_file_time = 0;                    // Cas zadnjega kreiranja datoteke s podatki

    private $last_update = 0;                       // Cas zadnjega kreiranja datoteke
    private $collect_all_status = 1;                // Ali zbiramo vse ali samo ustrezne
    private $file_status = FILE_STATUS_NO_DATA;     // Status datoteke
    private $has_test_data = false;                 // Ali vnosi vsebujejo testne podatke
    private $last_response_time = null;             // Ali vnosi vsebujejo testne podatke
    private $all_user_cnt = null;                   // Stevilo vseh responsov
    
    private $HEADER = NULL;
    

    public static function get_instance($force_new=false) {

    	if(!is_object(self::$instance) || $force_new) {
			self::$instance = new SurveyDataFile();
        }
        
		return self::$instance;
	}
    
	public function init($anketa) {
        global $site_path;

        // Nastavimo id ankete
        $this->anketa = $anketa;

        // Nastavimo folder kjer se nahajajo datoteke
        $folder = $site_path . EXPORT_FOLDER.'/';

        SurveySetting::getInstance()->Init($this->anketa);
        
		// Nastavimo imena datotek
		$this->header_file_name = $folder . 'export_header_'.$this->anketa.'.dat';
		$this->data_file_name = $folder . 'export_data_'.$this->anketa.'.dat';
        
        // Vedno ob inicializaciji preverimo status datoteke
        if($this->checked == false)
    		$this->checkFile();
    }
    

    /**** GETTERJI ****/

    // Vrnemo status datoteke s podatki
    public function getStatus() {
    	return $this->file_status;
    }

    // Vrnemo cas zadnjega updata datoteke
    public function getFileUpdated() {
		return $this->last_update;
    }
    
    // Vrnemo ime header datoteke
    public function getHeaderFileName() {
    	return $this->header_file_name;
    }

    // Vrnemo ime datoteke s podatki
    public function getDataFileName() {
    	return $this->data_file_name;
    }

    // Vrnemo cas kreiranja datoteke s podatki
    public function getDataFileTime() {
    	return $this->data_file_time;
    }

    // Vrnemo status kreiranja (ce pridobivamo vse ali samo ustrezne)
    public function getCollectAllStatus() {
    	return $this->collect_all_status;
    }

    // Vrnemo status kreiranja (ce pridobivamo vse ali samo ustrezne)
    public function getHasTestData() {
    	return $this->has_test_data;
    }

    // Vrnemo cas zadnjega responsa
    public function getLastResponseTime() {
    	return $this->last_response_time;
    }

    // Vrnemo header datoteko 
	public function getHeader() {
        
        if (!isset($this->HEADER) || $this->HEADER == null) {
			if ($this->header_file_name != null && file_exists($this->header_file_name)) {
				$this->HEADER = unserialize(file_get_contents($this->header_file_name));
			}
        }
        
		return $this->HEADER;
    }
    
	public function getHeaderVariable($variable) {

        $this->getHeader();
        
		return isset($this->HEADER[$variable]) ? $this->HEADER[$variable] : null;
	}
	
	public function getSurveyVariables($filterTip = NULL) {

        $result = array();
        
        $this->getHeader();
        
		if (is_countable($this->HEADER) && count($this->HEADER) > 0) {

			foreach ($this->HEADER AS $_vkey => $variable) {

				# dodamo samo tiste variable, ki imajo numerični tip (navadne spremenljvke)
				if (is_numeric($variable['tip'] )
					# ne dodamo sistemskih email, telefon, ime, priimek, naziv
					&& !( (int)$variable['hide_system'] == 1 
					&& in_array($variable['variable'],unserialize (SYSTEM_VARIABLES)) )# unserialize (SYSTEM_VARIABLES) -> definition.php = array('email','telefon','ime','priimek','naziv','drugo')
				) {
					# če filter ni setiran dodamo vse variable
					if ( $filterTip == NULL ) {
						$result[$_vkey] = $variable['sequences'];
                    } 
                    else if ( is_array($filterTip) && in_array($variable['tip'],$filterTip) ) {
						$result[$_vkey] = $variable['sequences'];
                    } 
                    else if ( is_string($filterTip) && (string)$variable['tip'] == (string)$filterTip ) {
						$result[$_vkey] = $variable['sequences'];
                    }
                    else if ( is_numeric($filterTip) && (int)$variable['tip'] == (int)$filterTip ) {
						$result[$_vkey] = $variable['sequences'];
					}
				}
			}
        }
        
		return $result;
	}
	
	public function getVariableName($variable) {

        $result = '';
        $this->getHeader();
        
		if (isset($this->HEADER[$variable])) {
			$result = '(' . $this->HEADER[$variable]['variable'] .') - '.$this->HEADER[$variable]['naslov'];
        }
        
		return $result;
    }

    /**** GETTERJI - KONEC ****/
    

    
    // Preverimo status datotek s podatki
	public function checkFile () {
		
		// Najprej nastavimo status ni podatkov
		$this->file_status = FILE_STATUS_NO_DATA;
		
		// Preverimo ce ima anketa kaj responsov - če je respondentov več kot ONLY_VALID_LIMIT (5000) lovimo samo ustrezne
		$sql_cnt_user = sisplet_query("SELECT count(*) FROM srv_user AS u WHERE u.ank_id = '".$this->anketa."' AND u.preview='0' AND u.deleted='0'");
		list($this->all_user_cnt) = mysqli_fetch_row($sql_cnt_user);
        
        // Imamo response
		if((int)$this->all_user_cnt > 0) {

            // Nastavimo zapise v bazi za datoteko
            $sql = sisplet_query("SELECT UNIX_TIMESTAMP(head_file_time), UNIX_TIMESTAMP(data_file_time), DATE_FORMAT(last_update,'%d.%m.%Y %H:%i:%s'), collect_all_status
                                    FROM srv_data_files 
                                    WHERE sid='".$this->anketa."'");
            if(mysqli_num_rows($sql) > 0) {
                list($this->head_file_time, $this->data_file_time, $this->last_update, $this->collect_all_status) = mysqli_fetch_row($sql);
            }

            // Ce je collect_all_status = 2 je admin nastavil da se lovijo vsi statusi, popravimo tudi čas, da se datoteka zgenerira na novo
            if ((int)$this->all_user_cnt > ONLY_VALID_LIMIT && (int)$this->collect_all_status == 1) {
                
                $this->collect_all_status = 0;
 
                $updated = sisplet_query("INSERT INTO srv_data_files (sid, collect_all_status, data_file_time) 
                                            VALUES ('".$this->anketa."','".(int)$this->collect_all_status."', '0000-00-00') 
                                            ON DUPLICATE KEY UPDATE collect_all_status = '".(int)$this->collect_all_status."'");
            }   

            // Nastavimo, ce imamo testne vnose
            $_qry_cnt_testdata = sisplet_query("SELECT count(*) 
                                                    FROM srv_user AS u 
                                                    WHERE u.ank_id = '".$this->anketa."' AND preview='0' AND (u.testdata = '1' OR u.testdata = '2') AND u.deleted = '0'");
            list($this->has_test_data) = mysqli_fetch_row($_qry_cnt_testdata);


            // Ce ne belezimo parapodatka o datumu responsa, preverimo zadnji timestamp resevanja ankete
            if(SurveySetting::getInstance()->getSurveyMiscSetting('survey_date') == 1) {
 
                $sql_last_response_time = sisplet_query("SELECT UNIX_TIMESTAMP(last_response_time) AS last_response_time FROM srv_anketa WHERE id='".$this->anketa."'");
                list($last_response_time) = mysqli_fetch_row($sql_last_response_time);

                if($this->data_file_time < $last_response_time){
                    $this->clearFiles();
                    $this->file_status = FILE_STATUS_NO_FILE;
                }
            }

            // Preverimo ce imamo usability stolpec v header datoteki ali ce imamo na novo testne podatke - potem pobrisemo vse datoteke, ker moramo vse generirati na novo
            if($this->checkUsability() || $this->checkTestData()){
                $this->clearFiles();
                    
                $this->file_status = FILE_STATUS_NO_FILE;
            }
            else{

                // Datoteka obstaja
                if(file_exists($this->getHeaderFileName()) && file_exists($this->data_file_name)) {
                    // Preverimo ce je datoteka up2date
                    $this->file_status = $this->isFileUp2Date();
                } 
                // Ni datoteke
                else {
                    $this->file_status = FILE_STATUS_NO_FILE;
                }
            }
        } 
        
        // Konmcali smo preverjanje statusa datotek
        $this->checked = true;

		return $this->file_status;
	}

    // Preverimo ce je datoteka s podatki up to date
	private function isFileUp2Date() {
		        
		// Cas zadnjega editiranja ankete (vse razen active=-1 - pobrisana)
		$sql_survey = sisplet_query("SELECT active, UNIX_TIMESTAMP(edit_time) AS srv_edit_time FROM srv_anketa WHERE id='".$this->anketa."'");
		list($anketa_active, $anketa_edit_time) = mysqli_fetch_row($sql_survey);
        
        // Ce je anketa slucajno pobrisana nastavimo status na pobrisano
        if($anketa_active != '0' && $anketa_active != '1'){
            return FILE_STATUS_SRV_DELETED;
        }

		// Ce se cas zadnjega urejanja ankete ne ujema s tistim v bazi, moram vse kreirati na novo
		if((int)$anketa_edit_time != (int)$this->head_file_time || (int)$anketa_edit_time <= 0) {
            $this->clearFiles();

            return FILE_STATUS_NO_FILE;
        }

        // Polovimo datum zadnjega respondenta
        $this->setLastResponseTime();

        // Preberemo max time iz datoteke s podatki in uporabimo manjšega
        if(IS_WINDOWS) {
            $command = 'awk -F"|" "BEGIN {max = 0} {if ($6 > max && $6 > 0) max=$6 } END {print max}" '.$this->data_file_name;
        } 
        else {
            $command = 'awk -F\'|\' \'BEGIN {max = 0} {if ($6 > max && $6 > 0) max=$6 } END {print max}\' '.$this->data_file_name;
        }
        $response_time_from_file = shell_exec($command);
        
        if((int)$response_time_from_file > 0) {            
            $data_file_time = min((int)$this->data_file_time, (int)$response_time_from_file);
        }
        else{
            $data_file_time = $this->data_file_time;
        }
        
        // Ce se cas dejanskega zadnjega responsa in cas zadnjega responsa v datoteki ne ujemata je datoteka zastarela
        if((int)$this->last_response_time != (int)$data_file_time || (int)$this->last_response_time <= 0) {
            return FILE_STATUS_OLD;
        } 


		return FILE_STATUS_OK;
    }

    // Preverimo ce imamo vklopljen filter na uporabnost in ce imamo v headerju zapisan stolpec za usability (drugace moramo generirati datoteko na novo)
	private function checkUsability(){
		
		// Preverimo ce imamo vklopljen filter na usability
		if(SurveyStatusProfiles::usabilitySettings()){
			
			// Preverimo ce imamo usability stolpec v header datoteki
			$usability = $this->getHeaderVariable('usability');
			if($usability == null){
				return true;
			}
        }
        
        return false;
	}

    // Preverimo ce imamo testne podatke na novo -> potem moramo generirati datoteko na novo
	private function checkTestData(){
			
		// Preverimo ce prej nismo imeli testnih podatkov
		$settings = $this->getHeaderVariable('_settings');
		if($settings['hasTestData'] != 1 && $this->has_test_data > 0){
			return true;
        }
        
        return false;
	}
    
    // Vrnemo text z info o datoteki
    public function getDataFileInfo() {
		global $admin_type, $lang;
		
		$result = null;
		
		if ($admin_type == '0') {

            // Delamo inkremental
            $inkremental_user_limit = '';
            if ($this->file_status == FILE_STATUS_OLD) {		
				$inkremental_user_limit = " AND u.time_edit > FROM_UNIXTIME('".(int)$this->data_file_time."') ";
            }

            // Zbiramo samo ustrezne
            $is_valid_user_limit = '';
            if ((int)$this->collect_all_status == 0) {
                $is_valid_user_limit = " AND u.last_status IN (5,6) ";
            }

			$qry_usr_cnt = sisplet_query("SELECT count(u.id) 
                                            FROM srv_user AS u 
                                            WHERE u.ank_id = '".$this->anketa."' AND u.preview='0' AND u.deleted='0' " . $is_valid_user_limit . $inkremental_user_limit);
			list($user_cnt) = mysqli_fetch_row($qry_usr_cnt);
			
			// Preverimo ce imamo izklopljeno belezenje parapodatka datum - potem moramo vedno generirati datoteko
			$no_paradata_date = SurveySetting::getInstance()->getSurveyMiscSetting('survey_date');
			
            $result = '&nbsp;&nbsp;<span class="leftSpace">';
            
			if ($this->file_status == FILE_STATUS_NO_DATA) {
				$result .=  $lang['srv_dashboard_no_data'];
			}
			else if ($this->file_status == FILE_STATUS_NO_FILE) {
				$result .=  $lang['srv_dashboard_no_file'].$lang['srv_dashboard_new_units'].(int)$user_cnt;
			}
			else if ($this->file_status == FILE_STATUS_OLD && $no_paradata_date == 0) {
				$result .=  $lang['srv_dashboard_not_up_to_date'].'('.$this->last_update.')';
				$result .= $lang['srv_dashboard_new_units'].(int) $user_cnt;
			}
			else if ($this->file_status == FILE_STATUS_OLD && $no_paradata_date == 1) {
				$result .=  $lang['srv_dashboard_up_to_date'].' ('.$this->last_update.')';
			}
			else if ($this->file_status == FILE_STATUS_OK) {
				$result .=  $lang['srv_dashboard_up_to_date'].' ('.$this->last_update.')';
            }
            
            $result .= '</span>';
        }
        
		return $result;
    }
    
    // Preverimo v bazi in nastavimo cas zadnjega respondenta
    public function setLastResponseTime() {
		global $admin_type, $lang;
        
        // Zbiramo samo ustrezne
        $is_valid_user_limit = '';
        if ((int)$this->collect_all_status == 0) {
            $is_valid_user_limit = " AND u.last_status IN (5,6) ";
        }

        // Polovimo datum zadnjega respondenta
        $sql_usr_time = sisplet_query("SELECT max(UNIX_TIMESTAMP(GREATEST(u.time_insert,u.time_edit))) 
                                        FROM srv_user AS u 
                                        WHERE u.ank_id='".$this->anketa."' AND u.preview='0' AND u.deleted='0' ".$is_valid_user_limit."");
        list($this->last_response_time) = mysqli_fetch_row($sql_usr_time);

        // Ce ne belezimo parapodatka datum responsa moramo vedno generirati na novo - nastavimo, da je vedno zadnji response zdaj
        $no_paradata_date = SurveySetting::getInstance()->getSurveyMiscSetting('survey_date');
        if($no_paradata_date == 1)
            $this->last_response_time = time();

        return $this->last_response_time;
	}


    // Pripravimo datoteke s podatki
    public function prepareFiles($show_loading=false) {
        global $lang;
        
        // File status ok, no data ali deleted survey - ne rabimo generirati ničesar
        if(in_array($this->file_status, array(FILE_STATUS_OK, FILE_STATUS_NO_DATA, FILE_STATUS_SRV_DELETED))){
            return $this->file_status;
        }
        
        // Ce imamo to vklopljeno in imamo vec kot 300 responsov prikazemo popup za loading in z ajaxem poklicemo generiranje
        if($show_loading && $this->all_user_cnt > 300 && !isDebug()){

            // Popup div z loading obvestilom
            echo '<div id="collect_data_popup" class="divPopUp"><span class="faicon spinner fa-spin spaceRight"></span> '.$lang['srv_collectdata_in_progress'].' ('.$this->all_user_cnt.'). '.$lang['srv_collectdata_in_progress2'].'</div>';

            echo '<script>
                collectDataAjax();  
            </script>';

            return $this->file_status;
        }
        

        // Najprej poskusimo zakleniti anekto za kreiranje
        $lock = new ExclusiveLock( "survey_lock_".$this->anketa);
        $locked_success = $lock->lock();

        // Ce je nismo ustrezno zaklenili ne moremo poklicati generiranje datotek
        if(!$locked_success){
            return $this->file_status;
        }


        // Ce smo jo ustrezno zaklenili lahko poklicemo generiranje datotek
        $collectData = new SurveyDataCollect($this->anketa);

        // File status old - inkrementalno dodamo nove response
        if($this->file_status == FILE_STATUS_OLD){
            $sdc = new SurveyDataCollect($this->anketa);
            $createdNewFile = (int)$sdc->updateFilesIncremental();  
        }
        // Drugace generiramo vse na novo
        else{
            $sdc = new SurveyDataCollect($this->anketa);
            $createdNewFile = (int)$sdc->createFiles();  
        }

        // Na koncu se enkrat preverimo in nastavimo stanje datotek
        $this->checkFile();

        // Anketo odklenemo
        $lock->unlock();

        // Vrnemo zadnji status datotek
        return $this->file_status;
    }
    
    // Popolnoma pobrisemo datoteke s podatki in ponastavimo vrednosti generiranja v bazi
    public function clearFiles() {

        // Najprej pobrisemo vse datoteke
        $this->deleteFile();
        
        // Pobrisemo vrstico za datoteke v bazi
        $sql = sisplet_query("DELETE FROM srv_data_files WHERE sid='".$this->anketa."'");
        
        // Pobrisemo lock ce ga imamo na datoteki
        $key = 'survey_lock_'.$this->anketa;
        $sqlUnlock = sisplet_query("DELETE FROM srv_lock WHERE lock_key='".$key."'");
        
        // Na koncu ponastavimo statuse
        $this->file_status = FILE_STATUS_NO_FILE;
        $this->last_update = 0;
    }

    // Pobrisemo datoteke s podatki
    public function deleteFile($filename=null) {
        global $site_path;

        // Nastavimo folder kjer se nahajajo datoteke
        $folder = $site_path . EXPORT_FOLDER.'/';


        // Brišemo specifično datoteko
        if ($filename !== null && trim($filename) != '') {
			
			if (file_exists($filename)) {
				unlink($filename);
			}
        } 
        // Brisemo vse datoteke za doloceno anketo
        else {
   
            // Pobrišemo header datoteke
			if (file_exists($folder . 'export_header_'.$this->anketa.'.dat')) {
				unlink($folder . 'export_header_'.$this->anketa.'.dat');
            }
            // Pobrišemo data datoteko
			if (file_exists($folder . 'export_data_'.$this->anketa.'.dat')) {
				unlink($folder . 'export_data_'.$this->anketa.'.dat');
			}
			
			// Pobrišemo tmp file
			if (file_exists($folder . 'export_data_'.$this->anketa.'.tmp')) {
				unlink($folder . 'export_data_'.$this->anketa.'.tmp');
			}
			if (file_exists($folder . 'export_data_'.$this->anketa.'.dat_data1.tmp')) {
				unlink($folder . 'export_data_'.$this->anketa.'.dat_data1.tmp');
			}
			if (file_exists($folder . 'export_data_'.$this->anketa.'.dat_data1_1.tmp')) {
				unlink($folder . 'export_data_'.$this->anketa.'.dat_data1_1.tmp');
			}
			
			// Pobrisemo morebitne SN datoteke - header
			$files = glob($folder.'export_sn_header_'.$this->anketa.'_*.dat');
			if(count($files ) > 0) {
				foreach ($files AS $file) {
					unlink($file);
				}
			}
			// Pobrisemo morebitne SN datoteke - data
			$files = glob($folder.'export_sn_data_'.$this->anketa.'_*.dat');
			if(count($files ) > 0) {
				foreach ($files AS $file) {
					unlink($file);
				}
			}
		}
    }    


    // Ajax klici
    public function ajax() {
        
        // Poklicemo generiranje datoteke s podatki
		if($_GET['a'] == 'prepareFiles') {

            $file_status = $this->prepareFiles($show_loading = false);

            echo $file_status;
		}
	}
}

