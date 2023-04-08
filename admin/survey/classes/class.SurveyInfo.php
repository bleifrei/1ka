<?php
/**
 * Created on 6.4.2009
 *
 * @author: GOrazd Vesleič
 */
class SurveyInfo
{
	static private $instance;
	static private $inited;

	static private $surveyId = null;
	
	static private $cntUsers = null;
	static private $cntAnswers = null;
	static private $cntApropriateAnswers = null; // ustrezni odgovori (status = 4,5)
	static private $cntValidSurveys = null; // hrani stevilo ustrezno izpolnjenih vprasalnikov (status = 6)
	static private $cntPartiallyValidSurveys = null; // hrani stevilo delno izpolnjenih vprasalnikov (status = 5)
	static private $cntInvalidSurveys = null; // hrani stevilo neustrezno izpolnjenih vprasalnikov (status = 3 ali 4)
	static private $cntGroups = null;
	static private $cntQuestions = null;
	static private $cntVarables = null;

	static private $access_users = null;

	static private $rowSurveyInit = null;
	static private $rowUserInsertInfo = null;
	static private $rowUserEditInfo = null;
	static private $rowVsiUpo = null;
	static private $rowFirstLast = null;
	
	static private $surveyModules = null;

	static public $dateTimeSeperator = ', ';
	
	static private $enkaVersion = null;

	protected function __construct() {}

	final private function __clone() {}

	/** Poskrbimo za samo eno instanco razreda
	 *
	 */
	static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new SurveyInfo();
		}
		return self::$instance;
	}

	/**
	* ob inicializaciji samo nastavimo survey ID, podatke bomo polovili, ko jih bomo rabili
	* 
	* @param mixed $_surveyId
	*/
	static function SurveyInit($_surveyId)
	{
		self::$surveyId = $_surveyId;
		
		self::$cntUsers = null;
		self::$cntAnswers = null;
		self::$cntApropriateAnswers = null;
		self::$cntValidSurveys = null;
		self::$cntPartiallyValidSurveys = null;
		self::$cntInvalidSurveys = null;
		self::$cntGroups = null;
		self::$cntQuestions = null;
		self::$cntVarables = null;
		self::$access_users = null;
		self::$rowSurveyInit = null;
		self::$rowUserInsertInfo = null;
		self::$rowUserEditInfo = null;
		self::$rowVsiUpo = null;
		self::$rowFirstLast = null;
		self::$dateTimeSeperator = ', ';
		self::$surveyModules = null;
		
		self::$enkaVersion = null;
		
		self::$inited = true;
		
		return true;
	}
	
	/**
	* Vsilimo ponovno branje podatkov iz baze. (Uporabljamo po updejtih srv_anketa) 
	*/
	static function resetSurveyData() {
		self::$rowSurveyInit = null;
	}
	
	/**
	* vrne stolpec ($key) iz tabele srv_anketa za inicializirano anketo
	* 
	* @param mixed $key stolpec v tabeli srv_anketa
	* @return vrednost v stolpcu $key
	*/
	static function getSurveyColumn ($key) {
		$row = self::getSurveyRow();
		return $row[$key];
	}
	
	static function getSurveyRow () {
		if (!self::$rowSurveyInit) {
			$querySurveyInit = sisplet_query("SELECT * FROM srv_anketa WHERE id = '".self::$surveyId."'");
			self::$rowSurveyInit = mysqli_fetch_assoc($querySurveyInit);
		}
			
		return self::$rowSurveyInit;
	}
	
	static function getUserInsertInfo ($key) {
		if (!self::$rowUserInsertInfo) {
			$sqlUserInsertInfo = sisplet_query("SELECT name, surname, id, email FROM users WHERE id='".self::getSurveyInsertUid()."'");
		    self::$rowUserInsertInfo = mysqli_fetch_assoc($sqlUserInsertInfo);
		}
		
		return self::$rowUserInsertInfo[$key];
	}
	
	static function getUserEditInfo ($key) {
		if (!self::$rowUserEditInfo) {
			$sqlUserEditInfo = sisplet_query("SELECT name, surname, id, email FROM users WHERE id='".self::getSurveyEditUid()."'");
	    	self::$rowUserEditInfo = mysqli_fetch_assoc($sqlUserEditInfo);
		}
		
		return self::$rowUserEditInfo[$key];
	}
	
	static function getEnkaVersion($key) {
		if (!self::$enkaVersion) {
			$sqlEnkaVersion = sisplet_query("SELECT value FROM misc WHERE what='version' ");
	    	self::$enkaVersion = mysqli_fetch_assoc($sqlEnkaVersion);
		}
		
		return self::$enkaVersion[$key];
	}
	
	
/*	
	static function getVsiUpo () {
		if (!self::$rowVsiUpo) {
			$sqlVsiUpo = sisplet_query("SELECT id AS usr_id FROM srv_user WHERE ank_id = '".self::$surveyId."' AND preview = '0'");
			self::$rowVsiUpo = mysqli_fetch_assoc($sqlVsiUpo);
		}
		return self::$rowVsiUpo;
	}
	*/
	static function getFirstLast ($key) {
		if (!self::$rowFirstLast) {
			$sqlFirstLast = sisplet_query("SELECT min( time_insert ) as frst, max( time_insert ) as lst FROM srv_user WHERE ank_id = '".self::$surveyId."' AND preview ='0' AND deleted='0'");
			self::$rowFirstLast = mysqli_fetch_assoc($sqlFirstLast);
		}
		return self::$rowFirstLast[$key];
	}
	
	// te funkcije ohranimo, da se obdrzi kompatibilnost za nazaj. Za naprej se lahko uporabi kar direktno getSurveyColumn (v primerih kjer se lahko)
	static function getSurveyId()			{ return self::$surveyId; }
	static function getSurveyHash()			{ return self::getSurveyColumn('hash'); }
	static function getSurveyTitle()		{ return strip_tags(self::getSurveyColumn('naslov')); }
	static function getSurveyAkronim()		{ return strip_tags(self::getSurveyColumn('akronim')); }
	static function getSurveyActive()		{ return self::getSurveyColumn('active'); }
	static function getSurveyFolder()		{ return self::getSurveyColumn('folder'); }
	static function getSurveyInfo()			{ return self::getSurveyColumn('intro_opomba'); }
	static function getSurveyType()			{ return self::getSurveyColumn('survey_type'); }

	static function getSurveyShowIntro()	{ return self::getSurveyColumn('show_intro'); }
	static function getSurveyShowConcl()	{ return self::getSurveyColumn('show_concl'); }
	static function getSurveyIntro()		{ return strip_tags(self::getSurveyColumn('introduction')); }
	static function getSurveyConcl()		{ return strip_tags(self::getSurveyColumn('conclusion')); }
	static function getSurveyCountType()	{ return self::getSurveyColumn('countType'); }
	static function getSurveyInsertUid()	{ return self::getSurveyColumn('insert_uid'); }
	static function getSurveyInsertName()	{
		if (trim(self::getUserInsertInfo('name')) || trim(self::getUserInsertInfo('surname'))) {
			return self::getUserInsertInfo('name').(trim(self::getUserInsertInfo('name')) || trim(self::getUserInsertInfo('surname')) ? ' ' : '').self::getUserInsertInfo('surname'); 
		} else {
			global $lang;
			return $lang['srv_anonymous'];
		}
	}

	static function getSurveyInsertNameShort(){ 
		return substr(self::getUserInsertInfo('name'),0,1).substr(self::getUserInsertInfo('surname'),0,1); 
	}
	static function getSurveyInsertEmail()	{ 
		if (trim(self::getUserInsertInfo('email'))) {
			return self::getUserInsertInfo('email');
		} else {
			global $lang;
			return $lang['srv_anonymous'];
		}
	}
	static function getSurveyInsertDate() 	{ $insertDateTime = explode(" ", self::getSurveyColumn('insert_time')); $tmpDate = explode('-',$insertDateTime[0]); return $tmpDate[2].".".$tmpDate[1].".".$tmpDate[0]; }
	static function getSurveyInsertTime()	{ $insertDateTime = explode(" ", self::getSurveyColumn('insert_time')); return $insertDateTime[1]; }
	static function getSurveyEditUid()		{ return self::getSurveyColumn('edit_uid'); }
	static function getSurveyEditName()		{
		if (trim(self::getUserEditInfo('name')) || trim(self::getUserEditInfo('surname'))) { 
			return self::getUserEditInfo('name').(trim(self::getUserEditInfo('name')) || trim(self::getUserEditInfo('surname')) ? ' ' : '').self::getUserEditInfo('surname');
		} else {
			global $lang;
			return $lang['srv_anonymous'];
		}
	}
	static function getSurveyEditNameShort(){ 
		return substr(self::getUserEditInfo('name'),0,1).substr(self::getUserEditInfo('surname'),0,1);
	}
	static function getSurveyEditEmail()	{ 
		if (trim(self::getUserEditInfo('email'))) {
			return self::getUserEditInfo('email');
		} else {
			global $lang;
			return $lang['srv_anonymous'];
		}
	}
	static function getSurveyEditDate()		{ $editDateTime = explode(" ", self::getSurveyColumn('edit_time')); $tmpDate = explode('-',$editDateTime[0]); return $tmpDate[2].".".$tmpDate[1].".".$tmpDate[0]; }
	static function getSurveyEditTime()		{ $editDateTime = explode(" ", self::getSurveyColumn('edit_time')); return $edit_time = $editDateTime[1]; }
	static function getSurveyStartsDate()	{ $tmpDate = explode('-',self::getSurveyColumn('starts')); return $tmpDate[2].".".$tmpDate[1].".".$tmpDate[0]; }
	static function getSurveyExpireDate()	{ $tmpDate = explode('-',self::getSurveyColumn('expire')); return $tmpDate[2].".".$tmpDate[1].".".$tmpDate[0]; }
	
	static function getSurveyFirstEntryDate() { 
		if (self::getFirstLast('frst') == '0000-00-00 00:00:00' || self::getFirstLast('frst') == null)
			return null;
		$firstDateTime = explode(" ", self::getFirstLast('frst')); $tmpDate = explode('-',$firstDateTime[0]); return $tmpDate[2].".".$tmpDate[1].".".$tmpDate[0]; 
	}
	static function getSurveyLastEntryDate() { 
		if (self::getFirstLast('lst') == '0000-00-00 00:00:00' || self::getFirstLast('lst') == null)
			return null;
		$lastDateTime = explode(" ", self::getFirstLast('lst')); $tmpDate = explode('-',$lastDateTime[0]); return $tmpDate[2].".".$tmpDate[1].".".$tmpDate[0]; 
	}

	static function getSurveyFirstEntryTime() { $firstDateTime = explode(" ", self::getFirstLast('frst')); $tmpDate = explode(':',$firstDateTime[1]); return $tmpDate[0].":".$tmpDate[1].":".$tmpDate[2]; }
	static function getSurveyLastEntryTime() { $lastDateTime = explode(" ", self::getFirstLast('lst')); $tmpDate = explode(':',$lastDateTime[1]); return $tmpDate[0].":".$tmpDate[1].":".$tmpDate[2]; }

	static function getSurveyGroupCount()	{ 
		if (!self::$cntGroups) {
			$sqlg = sisplet_query("SELECT count(*) FROM srv_grupa WHERE ank_id='".self::$surveyId."'");
    		$rowg = mysqli_fetch_row($sqlg);
			self::$cntGroups = $rowg[0];
		}
		return self::$cntGroups; 
	}
	/*
	static function getSurveyUsersId()		{ return self::getVsiUpo(); }
	static function getSurveyUsersCount()	{ return count(self::getVsiUpo()); }
	*/		
	static function getSurveyAnswersCount()	{ 
		
		if (!self::$cntAnswers) {
			# ročno preštrejemo odgovore
			$str_qry_all_users = "SELECT count(u.id) AS user_count FROM srv_user AS u " . "WHERE u.ank_id = '".self::getSurveyId()."' AND u.preview = '0' AND u.deleted='0'";
			$qry_all_users = sisplet_query($str_qry_all_users);
			$row_all_users = mysqli_fetch_assoc($qry_all_users);
			self::$cntAnswers = $row_all_users['user_count']; 
		}
		
		return self::$cntAnswers; 
	}
		
	static function getSurveyApropriateAnswersCount()	{
		//@Uroš je 31.8.2017 dodal v funkcijo refreshData() v classSurveyList.php izkljucevanje lurkerjev v stolpcu approp 
                //v DB tabeli srv_survey_list. Prej so bili v approp vkljuceni vsi userji s parametri "preview = '0'  AND deleted='0' AND last_status IN (5,6)"
                //zaenkrat se se naj uporablja ta funkcija, ker bi drugace prislo do razhajanja v stevilu ustreznih enot, ker se 
                //podatki za stare ankete ne bi posodobili (bi bili se vedno vkljuceni lurkerji)
                //enkrat v prihodnosti, bi se lahko klicalo samo SELECT approp FROM srv_survey_list WHERE id='self::getSurveyId()'
                //tocen datum, kdaj se bo yadeva updatala na produkcijo, glej datum prvega updata po tej spremembi
            
		if (!self::$cntApropriateAnswers) {
			# ročno preštrejemo veljavne odgovore
			$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . self::getSurveyId() . "' AND preview = '0'  AND deleted='0' AND last_status IN (5,6) AND lurker = 0"; // samo veljavne statuse
			$qry_AppAnsw = sisplet_query($sqlStringAll);
			$row_AppAnsw = mysqli_fetch_assoc($qry_AppAnsw);
			self::$cntApropriateAnswers = $row_AppAnsw['cnt']; 
		}
		
		return self::$cntApropriateAnswers; 
	}
	
	
	static function getValidSurveysCount()	{
            
		if (!self::$cntValidSurveys) {
			# rocno prestejemo veljavno (ali delno) veljavne vprasalnike
			$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id='" . self::getSurveyId() . "' AND last_status = '6' AND lurker = '0' AND deleted='0' AND preview = '0' AND testdata='0'"; // samo Completed status ali Partially completed
			$qry_ValidSurveys = sisplet_query($sqlStringAll);
			$row_ValidSurveys = mysqli_fetch_assoc($qry_ValidSurveys);
			self::$cntValidSurveys = $row_ValidSurveys['cnt']; 
		}
		
		return self::$cntValidSurveys; 
	}
	
	static function getPartiallyValidSurveysCount()	{
            
		if (!self::$cntPartiallyValidSurveys) {
			# rocno prestejemo veljavno (ali delno) veljavne vprasalnike
			$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id='" . self::getSurveyId() . "' AND last_status = '5' AND lurker = '0' AND deleted='0' AND preview = '0' AND testdata='0'"; // samo Completed status ali Partially completed
			$qry_PartiallyValidSurveys = sisplet_query($sqlStringAll);
			$row_PartiallyValidSurveys = mysqli_fetch_assoc($qry_PartiallyValidSurveys);
			self::$cntPartiallyValidSurveys = $row_PartiallyValidSurveys['cnt']; 
		}
		
		return self::$cntPartiallyValidSurveys; 
	}
	
	static function getInvalidSurveysCount()	{
            
		if (!self::$cntInvalidSurveys) {
			# rocno prestejemo neveljavne vprasalnike
			//$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id='" . self::getSurveyId() . "' AND last_status IN (0,1,2,3,4,5l,6l) AND deleted='0' AND preview = '0' AND testdata='0'"; // samo invalid
			$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id='" . self::getSurveyId() . "' AND last_status IN (0,1,2,3,4,'5l', '6l') AND lurker = '1' AND deleted='0' AND preview = '0' AND testdata='0'"; // samo invalid
			$qry_InvalidSurveys = sisplet_query($sqlStringAll);
			$row_InvalidSurveys = mysqli_fetch_assoc($qry_InvalidSurveys);
			self::$cntInvalidSurveys = $row_InvalidSurveys['cnt']; 
		}
		
		return self::$cntInvalidSurveys; 
	}
	
	static function getSurveyQuestionCount(){ 
		
		if (!self::$cntQuestions) {
			# zato ročno preštrejemo vprašanja
			$str_qry_Questions = "SELECT s.id FROM srv_grupa g, srv_spremenljivka s WHERE g.ank_id='".self::getSurveyId()."' "." AND g.id=s.gru_id  ";
			$qry_Questions =  sisplet_query($str_qry_Questions);
			self::$cntQuestions = mysqli_num_rows($qry_Questions);	
		}
		
		return self::$cntQuestions; 
	}
	
	static function getSurveyVariableCount(){ 
	
		if (!self::$cntVarables) {
			#ročno preštrejemo variable
			$str_qry_variables = "SELECT s.id, s.tip, s.size, s.enota FROM srv_grupa g, srv_spremenljivka s WHERE g.ank_id='".self::getSurveyId()."' "." AND g.id=s.gru_id  ";
			$qry_variables =  sisplet_query($str_qry_variables);
			self::$cntVarables = self::doVariablesCount($qry_variables);	
		}
		
		return self::$cntVarables; 
	}
	
	static function getDateTimeSeperator(){
		return self::$dateTimeSeperator;
	} 
	
	// preverimio še kdo ima dostop
	static function getSurveyAccessUsers() { 
	
		if (!self::$access_users) {
			self::$access_users= array();
			$sqlDostop = sisplet_query("SELECT u.id, u.name, u.surname, u.email, dostop.alert_complete FROM users as u "
			." RIGHT JOIN (SELECT sd.uid, sd.alert_complete FROM srv_dostop as sd WHERE sd.ank_id='".self::$surveyId."') AS dostop ON u.id = dostop.uid WHERE u.id != '".self::getSurveyInsertUid()."'");
			while ($rowDostop = mysqli_fetch_assoc($sqlDostop)) {
				self::$access_users[] = $rowDostop;
			}
		}
		
		return self::$access_users; 
	}

	/** Polovimo vse datume aktivnosti ankete
	 * 
	 * @return unknown_type
	 */
	static function getSurveyActivity() {
		$activity = array();
		if ( self::getSurveyColumn('active') == 1 ) {
			$_starts = self::getSurveyColumn('starts');
			$_expire = self::getSurveyColumn('expire');
			
			// izberemo samo unikatne zapise ki niso enaki trenutni aktivnosti
			$str = "SELECT DISTINCT starts, expire FROM srv_activity WHERE not(starts = '".$_starts."' AND expire = '".$_expire."') AND sid = '".self::getSurveyId()."'";
			$qry = sisplet_query($str);
			while ($row = mysqli_fetch_assoc($qry)) {
				$activity[] = array('starts'=>$row['starts'],'expire'=>$row['expire']);
			}
			// dodamo trenutno nastavljeno aktivnost
			$activity[] = array('starts'=>$_starts,'expire'=>$_expire);
			
		} else {
			// anketa ni aktivna, preberemo samo iz tabele aktivnosti
			$str = "SELECT DISTINCT starts, expire FROM srv_activity WHERE sid = '".self::getSurveyId()."'";
			$qry = sisplet_query($str);
			while ($row = mysqli_fetch_assoc($qry)) {
				$activity[] = array('starts'=>$row['starts'],'expire'=>$row['expire']);
			}
			
		}
		return $activity;
	}
	
	
	static function DisplayInfoBox($showIcons = false) {
		global $lang;
		global $site_url;
		global $global_user_id;
		
		// Ce smo v seznamu anket upostevamo splosni jezik in ne jezik ankete
		if(isset($_GET['a']) && $_GET['a'] == 'surveyList_display_info'){
			
			$lang_orig = $lang['id'];
			
			$sql = sisplet_query("SELECT lang FROM users WHERE id = '$global_user_id'");
			$row = mysqli_fetch_array($sql);
			$lang_admin = $row['lang'];
						
			// Naložimo jezikovno datoteko
			if($lang_admin != $lang_orig){
				$file = '../../lang/'.$lang_admin.'.php';
				include($file);
			}
                        
            echo '<div class="popup_close">';
            echo '  <a style="position:absolute; right:10px; top:10px" href="#" title="'.$lang['srv_zapri'].'" onclick="$(\'#survey_list_info\').hide(); return false;">✕</a>';
            echo '</div>';
		}
		
		echo '<span class="survey_info_title">'.$lang['srv_status_osnovni'].'</span><br /><br />';
		
		// spremenljivk
		echo '<span class="space">' . $lang['srv_info_name'] . ':</span>' . self::getSurveyTitle() . '<br/>';
		echo '<span class="space">' . $lang['srv_info_type'] . ':</span>' . $lang['srv_vrsta_survey_type_'.self::getSurveyType()] . '<br/>';
		echo '<span class="space">' . $lang['srv_info_questions']. ':</span>' .self::getSurveyQuestionCount().'<br/>';
		echo '<span class="space">' . $lang['srv_info_variables']. ':</span>' .self::getSurveyVariableCount().'<br/>';
		echo '<span class="space">' . $lang['srv_info_answers']. ':</span>' .self::getSurveyAnswersCount().'<br/>';
		echo '<span class="space">' . $lang['srv_info_answers_valid']. ':</span>' .self::getSurveyApropriateAnswersCount().'<br/>';
		
		# jezik izpolnjevanja
		echo '<span class="space">' . $lang['srv_info_language']. ':</span>'.self::getRespondentLanguage().'<br/>';
		echo '<span class="space">' . ($showIcons ? '<span class="sprites user" title="'.$lang['srv_info_author'].'"></span> ' : '' )
					  . $lang['srv_info_author']. ':</span>' .self::getSurveyInsertEmail();
		if (self::getSurveyInsertDate() && self::getSurveyInsertDate() != "00.00.0000")
			echo self::$dateTimeSeperator . self::getSurveyInsertDate();
		if (self::getSurveyInsertTime() && self::getSurveyInsertTime() != "00:00:00")
			echo self::$dateTimeSeperator . self::getSurveyInsertTime();
		echo '<br/>';
		
		echo '<span class="space">' . ($showIcons ? '<span class="sprites user_edit" title="'.$lang['srv_info_modify'].'"></span> ' : '' )
					  . $lang['srv_info_modify']. ':</span>' .self::getSurveyEditEmail();
		if (self::getSurveyEditDate() && self::getSurveyEditDate() != "00.00.0000")
			echo self::$dateTimeSeperator . self::getSurveyEditDate() ;
		if (self::getSurveyEditTime() && self::getSurveyEditTime() != "00:00:00")
			echo self::$dateTimeSeperator . self::getSurveyEditTime();
		echo '<br/>';
		
		$dostop = self::getSurveyAccessUsers();
		// dostop, Kdo razen avtorja ima dostop
		if ($dostop) {
			$cnt=0;
			foreach ( $dostop as $user) {
				if ($cnt==0) {
					echo '<span class="space">';
					echo ($showIcons ? '<span class="sprites user_add" title="'.$lang['srv_info_access'].'"></span> ' : '');
					echo $lang['srv_info_access']. ': </span>'.$user['name']." (".$user['email'].")<br/>";
				}
       				
       			else
					echo '<span class="infoData">'.$user['name']." (".$user['email'].')</span><br/>';
       			$cnt++;
			}
		}

		// aktivnost
		echo '<span class="space">';
		echo ($showIcons ? '<span class="sprites clock_play" title="'.$lang['srv_info_activity'].'"></span> ' : '');
		echo $lang['srv_info_activity']. ': </span>';
		$activity = self:: getSurveyActivity();
		if ( count($activity) > 0 ) {
			$prefix = '';
			foreach ($activity as $active) {
				$_starts = explode('-',$active['starts']);
				$_expire = explode('-',$active['expire']);
				
				echo $prefix.$_starts[2].'.'.$_starts[1].'.'.$_starts[0].'-'.$_expire[2].'.'.$_expire[1].'.'.$_expire[0];
				$prefix = '; ';
			}
		} 
        else {
			echo $lang['srv_anketa_noactive2'].'!';
		}
		echo "<br />";

		// prvi / zadnj vnos
		if (trim(self::getSurveyFirstEntryDate()) != '') {
			echo '<span class="space">';
			echo ($showIcons ? '<span class="sprites date_previous" title="'.$lang['srv_info_first_entry'].'"></span> ' : '');
			echo $lang['srv_info_first_entry']. ': </span>' . self::getSurveyFirstEntryDate() . self::$dateTimeSeperator .self::getSurveyFirstEntryTime() . '<br/>';
		}
		if (trim(self::getSurveyLastEntryDate()) != '') {
			echo '<span class="space">';
			echo ($showIcons ? '<span class="sprites date_next" title="'.$lang['srv_info_last_entry'].'"></span> ' : '');
			echo $lang['srv_info_last_entry']. ': </span>' . self::getSurveyLastEntryDate() . self::$dateTimeSeperator . self::getSurveyLastEntryTime() . '<br/>';
		}
		
		echo '<br class="clr"/>';
		
		$link = $site_url.'admin/survey/index.php?anketa=' . self::$surveyId.'&a=reporti';
		echo '<span style="text-transform:capitalize; font-weight:600;"><a class="link" href="'.$link.'">'.$lang['srv_more'].' >></a></span>';
		
		// Ce smo v seznamu anket upostevamo splosni jezik in ne jezik ankete
		if(isset($_GET['a']) && $_GET['a'] == 'surveyList_display_info'){

			// Naložimo originalno jezikovno datoteko
			if($lang_admin != $lang_orig){
				$file = '../../lang/'.$lang_orig.'.php';
				include($file);
			}
		}
	}
	
	static function doVariablesCount($query){ 
        
		// zloopamo skozi vprašanja in za vsako vprašan je preberemo št variabel
		$cnt= 0;

	    while ($rowVprasanje = mysqli_fetch_assoc($query)) {
	    	
	    	$spr_id= $rowVprasanje['id'];

            // v odvisnosti od tipa vprašanja pohandlamo podatke
	    	switch ( $rowVprasanje['tip'] ) {

	    		case 1: // radio
	    		case 3: // dropdown
					$cnt++; // za sam header
					$sqlSrvVred = self::select_sql_vrednost($rowVprasanje['id']);
					while ( $rowSrvVred = mysqli_fetch_assoc($sqlSrvVred) ) {
						if ($rowSrvVred['other'] == 1) 
						{
							$cnt++; // za text polje
						}
					}
	    		break;

	    		case 2: // checkbox
	    		case 6: // multigrid
				case 18: // vsota
				case 17: // ranking
				case 21: // text*
					$sqlSrvVred = self::select_sql_vrednost($rowVprasanje['id']);
					while ( $rowSrvVred = mysqli_fetch_assoc($sqlSrvVred) ) {
						$cnt++; // za vsako variablo
						if ($rowSrvVred['other'] == 1) {
							$cnt++; // za text polje (polj edrugo še ima tekstovni vnos
						}
					}
				break;

				case 4: // text
				case 8: // datum
				case 7: // number
				case 22:// compute
				case 25:// kvota
					$cnt++;
					if ( $rowVprasanje['tip'] == 7 && $rowVprasanje['size'] != 1 ) {
						$cnt+=2;
					}
				break;

				case 16: // multicheckbox
				case 19: // multitext
				case 20: // multinumber
					$cnt++; // za sam header
					$sqlSrvVred = self::select_sql_vrednost($spr_id);
					//gridi s katerimi sestavljamo header (vrstica_grid)
					while ( $rowSrvVred = mysqli_fetch_assoc($sqlSrvVred) ) {
						//loop cez gride znotraj ene vrstice - ponovimo za vsako vrstico
						$sqlSrvGrid = self::select_sql_grid($spr_id);
						while ( $colSrvVred = mysqli_fetch_assoc($sqlSrvGrid) ) {
							$cnt++;
						}
						if ($rowSrvVred['other'] == 1) {
							$cnt++;
						}
					}
				break;

                // lokacija
                case 26: 
                    if($rowVprasanje['enota'] == 3){
                        $sqlSrvVred = self::select_sql_vrednost($rowVprasanje['id']);
                        while ( $rowSrvVred = mysqli_fetch_assoc($sqlSrvVred) ) {
                                $cnt++; // za vsako variablo
                        }
                    }
                    else
                        $cnt++; // moja lokacija in multilokacija je 1
				break;

                // heatmap
				case 27: 
					$cnt++; // za koordinate
					$sqlSrvVred = self::select_sql_vrednost($rowVprasanje['id']);
					while ( $rowSrvVred = mysqli_fetch_assoc($sqlSrvVred) ) {	//za morebitna obmocja
						$cnt++; // za vsako variablo
						if ($rowSrvVred['other'] == 1) {
							$cnt++; // za text polje (polj edrugo še ima tekstovni vnos
						}
					}
				break;
	    	} // end switch

	    } // end while
	    return $cnt;
	}
	
	// pointerji do vrednosti zaradi hitrosti
    /**
    * @desc vrne sisplet_query za podan spr_id. v bistvu kesramo queryje, ker jih uporabljamo (zlo pogosto) v izpisu
    */
	//static private $otherCondition = ' AND other NOT IN (99,98,97,96)';
	static private $otherCondition = '';
    private static $_select_sql_vrednost = array();
    static function select_sql_vrednost ($spr_id) {
        if ( isset(self::$_select_sql_vrednost[$spr_id]) ) {
            // resetiramo pointer in vrnemo ke�iran query
            if (mysqli_num_rows(self::$_select_sql_vrednost[$spr_id]))
            	mysqli_data_seek(self::$_select_sql_vrednost[$spr_id], 0);
            return self::$_select_sql_vrednost[$spr_id];
        }
		else {
			self::$_select_sql_vrednost[$spr_id] = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='".$spr_id."' AND vrstni_red > 0".self::$otherCondition." ORDER BY vrstni_red ASC");
			return self::$_select_sql_vrednost[$spr_id];
		}
    }
    /**
    * @desc vrne sisplet_query za podan spr_id. v bistvu kesramo queryje, ker jih uporabljamo (zlo pogosto) v izpisu
    */
    private static $_select_sql_grid = array();
    static function select_sql_grid ($spr_id) {
        if ( isset(self::$_select_sql_grid[$spr_id]) ) {
            // resetiramo pointer in vrnemo ke�iran query
            if (mysqli_num_rows(self::$_select_sql_grid[$spr_id]))
            	mysqli_data_seek(self::$_select_sql_grid[$spr_id], 0);
            return self::$_select_sql_grid[$spr_id];
        }
		else {
			self::$_select_sql_grid[$spr_id] = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='".$spr_id."' ORDER BY vrstni_red");
			return self::$_select_sql_grid[$spr_id];
		}
    }
    
    /**
	* vrne URL naslov ankete (lahko je lep :) )
	* 
	*/
	static private $surveyLink = array();
	static function getSurveyLink ($anketa = false, $uporabnost = true) {
		global $site_url;
		
		if ($anketa == false)
			$anketa = self::$surveyId;

        $anketa_string = self::getSurveyHash();
				
		if ( ! isset( self::$surveyLink[$anketa_string] ) ) {
			
			$sqll = sisplet_query("SELECT link FROM srv_nice_links WHERE ank_id = '".$anketa."' ORDER BY id ASC LIMIT 1");
			if (mysqli_num_rows($sqll) > 0) {
				$rowl = mysqli_fetch_array($sqll);
				
				$link = $site_url . $rowl['link'] ;			
			} 
			else {		
				if (self::checkSurveyModule('uporabnost') && $uporabnost == true) // na redirectih pa v form action ne sme it na uporabnost (ker se odpira znotraj frama)
					$link = $site_url.'main/survey/uporabnost.php?anketa=' . $anketa_string;
				else
					$link = $site_url.'a/' . $anketa_string ;	
			}
			
			self::$surveyLink[$anketa_string] = $link;		
		}
			
		return self::$surveyLink[$anketa_string];		
	}
	
	static function getRespondentLanguage() {
		global $lang;
		$lang_resp = SurveyInfo::getSurveyColumn('lang_resp');
		if ($lang['id'] <> $lang_resp && $lang_resp > 0) {
			$lang_old= $lang;
			
			$file = dirname(__FILE__).'/../../../lang/'.$lang_resp.'.php';
			if (file_exists($file)) {
				include($file);
				$_lang_name = $lang['language'];
				$lang = $lang_old;
			}
		} else {
			$_lang_name = $lang['language'];
		}
		
		$p = new Prevajanje(self::$surveyId);
		$arr = $p->get_all_translation_langs();
		$dodatni = '';
		foreach ($arr AS $l) {
			if ($dodatni != '') $dodatni .= ', ';
			$dodatni .= $l;
		}
		if ($dodatni != '') $_lang_name .= ' ('.$dodatni.')';
		
		return $_lang_name;
	}

	/** 
	 * Polovimo vse module, ki so vklopljeni na anketi
	 */
	public static function getSurveyModules($ime = null) {
        global $global_user_id;

		if (!self::$surveyModules) {
            
            $modules = array();
            
            $userAccess = UserAccess::getInstance($global_user_id);

			$sql = sisplet_query("SELECT modul, vrednost FROM srv_anketa_module WHERE ank_id='".self::getSurveyId()."'");
			while($row = mysqli_fetch_array($sql)){
            
                $module_availible = true;

                // Preverimo, ce je modul omogocen v placljivem paketu
                switch($row['modul']){

                    case 'uporabnost':
                        if(!$userAccess->checkUserAccess($what='uporabnost'))
                            $module_availible = false;
                    break;

                    case 'quiz':
                        if(!$userAccess->checkUserAccess($what='kviz'))
                            $module_availible = false;
                    break;

                    case 'voting':
                        if(!$userAccess->checkUserAccess($what='voting'))
                            $module_availible = false;
                    break;

                    case 'social_network':
                        if(!$userAccess->checkUserAccess($what='social_network'))
                            $module_availible = false;
                    break;

                    case 'slideshow':
                        if(!$userAccess->checkUserAccess($what='slideshow'))
                            $module_availible = false;
                    break;

                    case 'phone':
                        if(!$userAccess->checkUserAccess($what='telephone'))
                            $module_availible = false;
                    break;

                    case 'chat':
                        if(!$userAccess->checkUserAccess($what='chat'))
                            $module_availible = false;
                    break;

                    case 'panel':
                        if(!$userAccess->checkUserAccess($what='panel'))
                            $module_availible = false;
                    break;
                }

                // Ce je modul na voljo v paketu, ga dodamo v array
                if($module_availible)
                    $modules[$row['modul']] = $row['vrednost'];
            }

			self::$surveyModules = $modules;
		}
		
		if(!is_null($ime) && isset(self::$surveyModules[$ime]))
			return self::$surveyModules[$ime];
		elseif(!is_null($ime) && sizeof(self::$surveyModules) == 0)
			return 0;
		else
			return self::$surveyModules;
	}
	
	/** 
	 * Preverimo, ce je specificen modul vklopljen na anketi
	 */
	public static function checkSurveyModule($module, $anketa_id = null) {
        global $global_user_id;

        if(is_null($anketa_id))
            $anketa_id = self::getSurveyId();

		if($anketa_id > 0 && $module != ''){

            // Preverimo, ce je modul omogocen v placljivem paketu
            $userAccess = UserAccess::getInstance($global_user_id);
            switch($module){

                case 'uporabnost':
                    if(!$userAccess->checkUserAccess($what='uporabnost'))
                        return false;
                break;

                case 'quiz':
                    if(!$userAccess->checkUserAccess($what='kviz'))
                        return false;
                break;

                case 'voting':
                    if(!$userAccess->checkUserAccess($what='voting'))
                        return false;
                break;

                case 'social_network':
                    if(!$userAccess->checkUserAccess($what='social_network'))
                        return false;
                break;

                case 'slideshow':
                    if(!$userAccess->checkUserAccess($what='slideshow'))
                        return false;
                break;

                case 'phone':
                    if(!$userAccess->checkUserAccess($what='telephone'))
                        return false;
                break;

                case 'chat':
                    if(!$userAccess->checkUserAccess($what='chat'))
                        return false;
                break;

                case 'panel':
                    if(!$userAccess->checkUserAccess($what='panel'))
                        return false;
                break;
            }
			
			if (!self::$surveyModules) {
				$sql = sisplet_query("SELECT EXISTS (SELECT 1 FROM srv_anketa_module WHERE ank_id='".$anketa_id."' AND modul='".$module."')");
				$row = mysqli_fetch_array($sql);
				
				if($row[0] > 0)
					return true;
				else
					return false;
			}
			else{
				if(self::$surveyModules[$module] > 0)
					return true;
				else
					return false;
			}	
		}
		else
			return false;
	}


	// Vrnemo pripeto ime tabele s podatki ce gre za arhivsko ali aktivno anketo (_active, archive1, archive2...)
	public static function getSurveyArchiveDBString() {
        
        $db_table = self::getSurveyColumn('db_table');

        switch($db_table){

            // Arhivska 1
            case '0':
                $db_table_string = '_archive1';
                break;

            // Arhivska 2
            case '2':
                $db_table_string = '_archive2';
                break;
            
            // Aktivna anketa
            case '1':
            default:
                $db_table_string = '_active';
                break;
        }

        return $db_table_string;
	}
}
?>