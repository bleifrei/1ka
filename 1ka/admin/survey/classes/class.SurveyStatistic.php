<?php
/*
 * Created on 28.01.2010
 *
 */
if(session_id() == '') {session_start();}

define('TYPE_ALL', 'all');						# čisto vsi statusi
define('TYPE_APPROPRIATE', 'appropriate');		# ustrezni statusi 6,5 brez lurkerjev
define('TYPE_STATUS_6', 'status_6');			# ustrezni statusi 6 brez lurkerjev
define('TYPE_STATUS_5', 'status_5');			# ustrezni statusi 5 brez lurkerjev
define('TYPE_INAPPROPRIATE', 'inappropriate');	# neustrezni statusi 6l,5l,4,3,2,1,0,-1
define('TYPE_STATUS_6l', 'status_6l');			# neustrezni statusi 6l
define('TYPE_STATUS_5l', 'status_5l');			# neustrezni statusi 5l
define('TYPE_STATUS_4', 'status_4');			# neustrezni statusi 4
define('TYPE_STATUS_3', 'status_3');			# neustrezni statusi 3
define('TYPE_STATUS_2', 'status_2');			# neustrezni statusi 2
define('TYPE_STATUS_1', 'status_1');			# neustrezni statusi 1
define('TYPE_STATUS_0', 'status_0');			# neustrezni statusi 0
# končal anketo => 6
define('TYPE_STATUS_KUMULATIVE_6', 'status_kum_6'); # kumulativni statusi 6 brez lurkerjev
# začel izpolnjevat => 5 = 6 + 5 (brez L)
define('TYPE_STATUS_KUMULATIVE_5', 'status_kum_5'); # kumulativni statusi 6 in 5 brez lurkerjev
define('TYPE_STATUS_KUMULATIVE_5ll', 'status_kum_5ll'); # kumulativni statusi 5ll
define('TYPE_STATUS_KUMULATIVE_4ll', 'status_kum_4ll'); # kumulativni statusi 4ll
define('TYPE_STATUS_KUMULATIVE_3ll', 'status_kum_3ll'); # kumulativni statusi 3ll
		
define('TYPE_STATUS_NULL', 'status_null');		# neustrezni statusi NULL <=> -1
define('TYPE_PAGES', 'pages');
define('TYPE_ANALYSIS', 'analysis');
define('DATE_FORMAT', 'Y-m-d');
define('DATE_FORMAT_SHORT', 'j.n.y');
define('TIME_FORMAT_SHORT', 'G:i');

define('PERIOD_MONTH_PERIOD', 'month_period');		# meseci v obdobju
define('PERIOD_WEEK_PERIOD', 'week_period');		# tedni v obdobju
define('PERIOD_DAY_PERIOD', 'day_period');			# dnevi v mesecu v obdobju(izpis po datumih - dnevi)
define('PERIOD_HOUR_PERIOD', 'hour_period');		# ure v obdobju ( 0 - 23 ) za vsak dan posebej
define('PERIOD_MONTH_YEAR', 'month_year');			# meseci v letu v obdobju (1-12)
define('PERIOD_WEEK_YEAR', 'week_year');			# tedni v letu v obdobju (1-52)
define('PERIOD_DAY_YEAR', 'day_year');				# dnevi v letu v obdobju (1-366)
define('PERIOD_DAY_MONTH', 'day_month');			# dnevi v mesecu v obdobju (1-31)
define('PERIOD_DAY_WEEK', 'day_week');				# dnevi v tednu ( 1 - 7 ) (pon-ned)
define('PERIOD_HOUR_DAY', 'hour_day');				# ure v dnevu ( 0 - 23 )

define('GRAPH_REDUCE', '1.22');						# količnik za koliko zmanjšamo širino grafa da ne prebije 

define('REFRESH_USER_COUNT', 1000);					# Koliko userejv je meja da delamo refresh na 15 minut
define('REFRESH_USER_TIME', 15);					# Na koliko minut delamo update za veliko bazo


class SurveyStatistic {
	public $inited=false; 							// inicializacija
	public $surveyId = null; 						// id ankete

	public $doCache = false; 						// ali keširamo dashboard

	public $startDate = null;						// datum zacetka statistik
	public $endDate = null;							// datum konca statistik
	public $type = TYPE_STATUS_KUMULATIVE_3ll;
	public $period = PERIOD_DAY_PERIOD;
	public $periods = array (PERIOD_MONTH_PERIOD,PERIOD_WEEK_PERIOD,PERIOD_DAY_PERIOD,PERIOD_HOUR_PERIOD,PERIOD_MONTH_YEAR,PERIOD_WEEK_YEAR,PERIOD_DAY_YEAR,PERIOD_DAY_MONTH,PERIOD_DAY_WEEK,PERIOD_HOUR_DAY); // možni tipi statistike
	public $hideNullValues_dates = true;			// ali prikazujemo nicelne vrednosti pri statistiki referalov
	public $hideNullValues_status = true;			// ali skrivamo nicelne vrednosti pri statisti statusov
	public $filter_email_status = 0;					// vsi userji
	
	public $timelineDropDownType = 1;						// 0 - končni statusi, 1 - kumulativni statusi
	
	public $isDefaultFilters = false;				// ali imampo privzete filtre 
		
	public $range = null;				
	public $arrayRange = null;						// za datumsko porazdelitev koikov
	public $allRecordCount = null;					// koliko je vseh zapisov v bazi (neglede na datumsko obdobje)
	public $maxValue = 0;
	
	public $isInterval = false;						// ali je uporabnik izbral datum iz intervala
	public $stat_interval = null;					// obdobje intervala

	
	/*
	"srv_userstatus_0"          =>	 "Email &#154;e ni bil poslan",
    "srv_userstatus_1"          =>	 "Email poslan",
    "srv_userstatus_2"          =>	 "Napaka pri po&#154;iljanju emaila",
    "srv_userstatus_3"          =>	 "Klik na anketo",
    "srv_userstatus_4"          =>	 "Klik na prvo stran",
    "srv_userstatus_5"          =>	 "Za&#269;el izpolnjevati",
    "srv_userstatus_6"          =>	 "Kon&#269;al anketo",
	*/
	public $userByStatus = array('valid'=>array(6=>0,5=>0),
							     'nonvalid'=>array('6l'=>0,'5l'=>0,4=>0,3=>0,-1=>0),
								 'invitation'=>array(2=>0,1=>0,0=>0));		// za porazdelitev userjev po statusu
	public $testDataCount = 0;
	public $appropriateStatus = array(6,5); 			// Statusi anket katere štejemo kot ustrezne
	public $unAppropriateStatus = array('6l','5l',4,3,-1); 	// Statusi anket katere štejemo kot neustrezne, 5l, 6l - lurkerji
	public $invitationStatus = array(2,1,0); 	// Statusi anket katere štejemo kot neustrezne, 5l, 6l - lurkerji

	public $emailInvitation = 0;						# vsi respondenti (email in normalni)
	public $cntUserByStatus = array('valid'=>0, 'nonvalid'=>0, 'invitation'=>0);					// Štejemo userej po statusu
	public $emailStatus = array(0,1,2);					// Statusi povezani z emaili, se upoštevajo v statistiki neglede na datu (timeinsert)

	public $cntValidRedirections = 0;					// Stejemo vlejavne referale (ki vsebujejo tekst)
	public $cntNonValidRedirections = 0;				// Stejemo neveljavne referale (ki ne vsebujejo teksta)
	public $userRedirections = array(3=>0,4=>0,5=>0,6=>0,'valid' => array('email'=>0),'email'=>0,'direct'=>0, 'cntAll'=>0);					// za porazdelitev redirekcij na anketo
	public $maxRedirection = 0;							// koliko je maksimalno število klikov (za primerno širino diva)
	public $maxCharRedirection = 0;						// max stevilo znakovv v "host" (za lepsi izpis redirekcij)

	public $realUsersByStatus_all = 0;					// skupaj frekvenc
	public $realUsersByStatus = array();				// frekvenca po posameznem statusu (uposeteva da ce je kdo koncal anketo jo je tudi zacel)
	public $respondentLangArray = array();				# grupiranje po jezikih 

	public $realUsersByStatus_base = '3ll';				# kateri status nam predstavlja osnovo (100%) v oknu stopnje odgovorov		
	
	public $pageUsersByStatus_base = '3ll';				# kateri status nam predstavlja osnovo (100%) v oknu potek po straneh
	
	public $ip_list = array();

	public $db_table = "";

	public $fileTimestamp = null;						# timestamp ki ga dodelimo datoteki če shranimo dashboard v fajl

	public $comments = array();							# za komentarej ankete

	public $cnt_all = 0;
	public $cnt_email = 0;
	
	
	/** Inicializacija
	 *
	 */
	function Init($sid, $forceDefaultFilter = false) {
		global $global_user_id;
		# nastavimo sid
		$this->surveyId = $sid;
		$this->setSurveyId($sid);
		
		Common::deletePreviewData($sid);
		
		# inicializiramo časovne porfile
		SurveyTimeProfiles :: Init($this->getSurveyId(), $global_user_id);
		
		# poiščemo aktivno anketo
		SurveyInfo :: getInstance()->SurveyInit($this->getSurveyId());
		if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
			$this->db_table = '_active';
		
		# nastavimo spremenljivko ali imamo vse default vrednosti
		$this->isDefaultFilters = true; 
		
		# resetiramo timestamp
		$this->fileTimestamp = null; 

		# osvezimo datume zacetka in konca iz profila
		$this->RefreshDates($forceDefaultFilter);		
		
		
		# če se število respondentov z emaili in brez razlikuje ponudimo filter
		$str1 = "SELECT count(*) FROM srv_user WHERE ank_id = '".$this->getSurveyId()."' AND preview = '0' AND deleted='0'  AND (time_insert BETWEEN '".$this->startDate."' AND '".$this->endDate."' + INTERVAL 1 DAY)";
		list($cnt_all) = mysqli_fetch_row(sisplet_query($str1));
		$str2 = "SELECT count(*) FROM srv_user WHERE ank_id = '".$this->getSurveyId()."' AND preview = '0' AND deleted='0' AND inv_res_id is NOT NULL  AND (time_insert BETWEEN '".$this->startDate."' AND '".$this->endDate."' + INTERVAL 1 DAY)";
		list($cnt_email) = mysqli_fetch_row(sisplet_query($str2));
		$this->cnt_all = (int)$cnt_all;
		$this->cnt_email = (int)$cnt_email;
		
		
		# ali gledamo vse/samo emaile/brez emailov
		if ( isset($_REQUEST['filter_email_status']) && $_REQUEST['filter_email_status'] != "") {
			$this->emailInvitation = (int)$_REQUEST['filter_email_status'];
		} else if ( $this->cnt_all == $this->cnt_email && $this->cnt_all > 0 ) {
			$this->emailInvitation = 1;
		}
		# tip statistike, strani, analize, uporabniki
		#privzet tip:
		$this->type = TYPE_STATUS_KUMULATIVE_3ll;
		$this->isDefaultFilters = true;
		if ( isset($_REQUEST['type']) && $_REQUEST['type'] != "") {
			$this->type = $_REQUEST['type'];
			$this->isDefaultFilters = false;
		}
		
		# Ali skrivamo vrednosti ki so 0 za datumski prikaz
		if ( isset($_REQUEST['hideNullValues_dates']) && $_REQUEST['hideNullValues_dates'] != "") {
			$this->hideNullValues_dates = ($_REQUEST['hideNullValues_dates'] == 'true');
				$this->isDefaultFilters = false;
		}
		
		# Ali skrivamo vrednosti ki so 0 pri statusih
		if ( isset($_REQUEST['hideNullValues_status']) && $_REQUEST['hideNullValues_status'] != "") {
			$this->hideNullValues_status = ($_REQUEST['hideNullValues_status'] == 'true');
			$this->isDefaultFilters = false;
		}

		# Kateri dropdown prikažemo. ali z končnimi statusi, ali pa z kumulativnmi statusi
		if ( isset($_REQUEST['timelineDropDownType']) && $_REQUEST['timelineDropDownType'] == 'false') {
			$this->timelineDropDownType = 0;
			$this->isDefaultFilters = false;
			# če nimamo pravega tipa
		} else {
			if (!in_array($this->type, array(TYPE_STATUS_KUMULATIVE_3ll, TYPE_STATUS_KUMULATIVE_4ll, TYPE_STATUS_KUMULATIVE_5ll, TYPE_STATUS_KUMULATIVE_5, TYPE_STATUS_KUMULATIVE_6))) {
				$this->type = TYPE_STATUS_KUMULATIVE_3ll; 
			}
			
		}
		
		
		# Time filter
		if ( (int)SurveyTimeProfiles ::getCurentProfileId() !== (int)SurveyTimeProfiles ::getSystemDefaultProfile())
		{
			$this->isDefaultFilters = false;
		}
		
		
		# obdobje prikaza
		if ( isset($_REQUEST['period']) && $_REQUEST['period'] != "") {
			$this->period =  $_REQUEST['period'];
			$this->isDefaultFilters = false;
		}
		# osnova za stopnje odgovorov (dropdown)
		# če smo preklopli na email vabila in če imamo samo vabila je osnova email
		if ($this->emailInvitation === 1 && $_REQUEST['inviation_dropdown'] === 'true') {
			$this->realUsersByStatus_base = 'email';
		} else if ( isset($_REQUEST['userStatusBase']) && $_REQUEST['userStatusBase'] != "") {
			if ($this->emailInvitation !== 1 && $_REQUEST['userStatusBase'] == 'email') {
				$_REQUEST['userStatusBase'] = '3ll';
			}
			$this->realUsersByStatus_base = $_REQUEST['userStatusBase'];
			$this->isDefaultFilters = false;
		}
		
		// Osnova za odgovore po straneh
		if (isset($_REQUEST['pageUserStatusBase']) && $_REQUEST['pageUserStatusBase'] != "") {
			if ($this->emailInvitation !== 1 && $_REQUEST['pageUserStatusBase'] == 'email') {
				$_REQUEST['pageUserStatusBase'] = '3ll';
			}
			$this->pageUsersByStatus_base = $_REQUEST['pageUserStatusBase'];
			$this->isDefaultFilters = false;
		}
		
		if ((int)$sid > 0) {
			$this->inited = true;
		}
	}
	
	function ajax() {
		global $lang;
	}

	// GETTERS & SETTERS
	function getSurveyId()					{ return $this->surveyId; }
	function setSurveyId($sid)				{ $this->surveyId = $sid; }
	function getStartDate()					{ return $this->startDate; }
	function setStartDate($sd)				{ $this->startDate = $sd; }
	function getEndDate()					{ return $this->endDate; }
	function setEndDate($ed)				{ $this->endDate = $ed; }
	function getUserByStatus()				{ return $this->userByStatus; }
	function getCntUserByStatus()			{ return $this->cntUserByStatus; }
        function setPeriod($period)                            { $this->period = $period; }
        function getArrayRange()			{ return $this->arrayRange; }//dobi range po datumih
        function getUserRedirections()			{ return $this->userRedirections; }//dobi preusmeritve


	function RefreshDates($forceDefaultFilter = false) { 		
		# iz profila preberemo začetni datum in končni datum
		$dates = SurveyTimeProfiles :: GetDates($forceDefaultFilter);
		$this->startDate = $dates['start_date'];
		$this->endDate = $dates['end_date'];
		if ($dates['is_default_dates'] == false) {
			$this->isDefaultFilters = false;
		}
	}
	
	/** Pripravi osnovne podatke ce niso podani drugace
	 * 
	 */
	function PrepareDateView() {
		switch ($this->type) {
			case TYPE_ALL :
			case TYPE_APPROPRIATE :
			case TYPE_STATUS_6 :
			case TYPE_STATUS_5 :
			case TYPE_INAPPROPRIATE :
			case TYPE_STATUS_6l :
			case TYPE_STATUS_5l :
			case TYPE_STATUS_4 :	
			case TYPE_STATUS_3 :
			case TYPE_STATUS_2 :
			case TYPE_STATUS_1 :
			case TYPE_STATUS_0 :
			case TYPE_STATUS_NULL :
				#še kumulativni statusi
			case TYPE_STATUS_KUMULATIVE_6:
			case TYPE_STATUS_KUMULATIVE_5:
			case TYPE_STATUS_KUMULATIVE_5ll:
			case TYPE_STATUS_KUMULATIVE_4ll:
			case TYPE_STATUS_KUMULATIVE_3ll:
				$time_edit = 'srv_user.time_insert';
			break;
			case TYPE_PAGES :
				$time_edit = 'grupa.time_edit';
			break;
			case TYPE_ANALYSIS :
				$time_edit = 'srv_tracking'.$this->db_table.'.datetime';
			break;
		}

		switch ($this->period) {
			case PERIOD_MONTH_PERIOD :
				// vzamemo prvi dan v izbranem mesecu
				$intervalFormat = "DATE_FORMAT($time_edit,'%Y-%m') AS formatdate ";
				$groupBy = "GROUP BY formatdate ";
				if (!$this->hideNullValues_dates) // da ne delamo po neporebnem
					$this->arrayRange = $this->createDateRangeArray($this->startDate, $this->endDate, "Y-m", 'm');
				break;
			case PERIOD_WEEK_PERIOD :
				// vzamemo prvi dan v izbranem mesecu
				$intervalFormat = "YEARWEEK($time_edit) AS formatdate ";
				$groupBy = "GROUP BY formatdate ";
				if (!$this->hideNullValues_dates) // da ne delamo po neporebnem
					$this->arrayRange = $this->createDateRangeArray($this->startDate, $this->endDate, "YW", 'W');
				break;
		
			case PERIOD_MONTH_YEAR :
				$intervalFormat = "MONTH($time_edit) AS formatdate ";
				$groupBy = "GROUP BY formatdate ";
				if (!$this->hideNullValues_dates) // da ne delamo po neporebnem
					$this->arrayRange = (($this->startDate <= $this->endDate) ? array_fill(1, 12, 0) : array ());
				break;
			case PERIOD_WEEK_YEAR :
				$intervalFormat = "WEEKOFYEAR($time_edit) AS formatdate ";
				$groupBy = "GROUP BY formatdate ";
				if (!$this->hideNullValues_dates) // da ne delamo po neporebnem
					$this->arrayRange = (($this->startDate <= $this->endDate) ? array_fill(1, 52, 0) : array ());
				break;
			case PERIOD_DAY_YEAR :
				$intervalFormat = "DAYOFYEAR($time_edit) AS formatdate ";
				$groupBy = "GROUP BY formatdate ";
				if (!$this->hideNullValues_dates) // da ne delamo po neporebnem
					$this->arrayRange = (($this->startDate <= $this->endDate) ? array_fill(1, 366, 0) : array ());
				break;
			case PERIOD_DAY_MONTH :
				$intervalFormat = "DAYOFMONTH($time_edit) AS formatdate ";
				$groupBy = "GROUP BY formatdate ";
				if (!$this->hideNullValues_dates) // da ne delamo po neporebnem
					$this->arrayRange = (($this->startDate <= $this->endDate) ? array_fill(1, 31, 0) : array ());
				break;
		
			case PERIOD_DAY_PERIOD :
				$intervalFormat = "DATE_FORMAT($time_edit,'%Y-%m-%d') AS formatdate ";
				$groupBy = "GROUP BY formatdate ";
				if (!$this->hideNullValues_dates) // da ne delamo po neporebnem
					$this->arrayRange = $this->createDateRangeArray($this->startDate, $this->endDate, DATE_FORMAT);
				break;
			case PERIOD_DAY_WEEK :
				$intervalFormat = "WEEKDAY($time_edit) AS formatdate ";
				$groupBy = "GROUP BY formatdate ";
				if (!$this->hideNullValues_dates) // da ne delamo po neporebnem
					$this->arrayRange = (($this->startDate <= $this->endDate) ? array_fill(0, 7, 0) : array ());
				break;
			case PERIOD_HOUR_DAY :
				$intervalFormat = "DATE_FORMAT($time_edit,'%H') AS formatdate ";
				$groupBy = "GROUP BY formatdate ";
				if (!$this->hideNullValues_dates) // da ne delamo po neporebnem
					$this->arrayRange = (($this->startDate <= $this->endDate) ? $this->createDateRangeArray(null,null,null,'H') : array ());
				break;
			case PERIOD_HOUR_PERIOD :
				$intervalFormat = "DATE_FORMAT($time_edit,'%Y-%m-%d %H') AS formatdate ";
				$groupBy = "GROUP BY formatdate ";
				if (!$this->hideNullValues_dates) {// da ne delamo po neporebnem
					// pripravimo data array
					//$this->arrayRange = $this->createPeriodDateRangeArray(PERIOD_HOUR_PERIOD, $this->startDate, $this->endDate);
					$this->arrayRange = $this->createDateRangeArray($this->startDate, $this->endDate, "Y-m-d H", 'h');
				}
				break;
        }
        
		$email_filter_string = null;
		if ($this->emailInvitation > 0) {
			if ($this->emailInvitation == 1) {
				$email_filter_string = ' AND inv_res_id is not NULL ';
			} else if($this->emailInvitation == 2) {
				$email_filter_string = ' AND inv_res_id is NULL ';
			} 
        }
        
        // Tega ne rabimo? Drugace je pri kopirani arivski anketi vse prazno
        //$intervalLimit = "AND $time_edit BETWEEN '".$this->startDate."' AND '".$this->endDate."' + INTERVAL 1 DAY ";
        $intervalLimit = "";

		switch ($this->type) {
			# VSI
			case TYPE_ALL :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' " . $email_filter_string . $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' ".$email_filter_string; // vsi statusi
			break;
			
			# USTREZNI BREZ LURKERJEV
			case TYPE_APPROPRIATE : # 6,5 brez lurkerjev
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' " . " AND last_status IN (6,5) AND lurker = '0' ".$email_filter_string . $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status IN (6,5) AND lurker = '0' ".$email_filter_string; // samo veljavne statuse
			break;
			
			# USTREZNI 6 BREZ LURKERJEV
			case TYPE_STATUS_6 :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '6' AND lurker = '0' ".$email_filter_string . $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '6' AND lurker = '0' ".$email_filter_string; 
			break;
			
			# USTREZNI 5 BREZ LURKERJEV
			case TYPE_STATUS_5 :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '5' AND lurker = '0' ".$email_filter_string. $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '5' AND lurker = '0' ".$email_filter_string; 
			break;

			# NEUSTREZNI 
			case TYPE_INAPPROPRIATE :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND ( last_status NOT IN (6,5) OR lurker = '1')  ".$email_filter_string. $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status NOT IN (6,5) OR lurker = '1' ".$email_filter_string; 
			break;

			# NEUSTREZNI 6L lurkerji 
			case TYPE_STATUS_6l :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '6' AND lurker = '1' ".$email_filter_string. $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '6' AND lurker = '1' ".$email_filter_string; 
			break;

			# NEUSTREZNI 5L lurkerji 
			case TYPE_STATUS_5l :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '5' AND lurker = '1' ".$email_filter_string. $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '6' AND lurker = '1' ".$email_filter_string; 
			break;

			# NEUSTREZNI 4
			case TYPE_STATUS_4 :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '4' ".$email_filter_string. $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '4' ".$email_filter_string; 
			break;
			
			# NEUSTREZNI 3
			case TYPE_STATUS_3 :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '3' ".$email_filter_string. $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '3'".$email_filter_string; 
			break;
			
			# NEUSTREZNI 2
			case TYPE_STATUS_2 :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '2' ".$email_filter_string. $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '2'".$email_filter_string; 
			break;
			
			# NEUSTREZNI 1
			case TYPE_STATUS_1 :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '1' ".$email_filter_string. $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '1'".$email_filter_string; 
			break;
			
			# NEUSTREZNI 0
			case TYPE_STATUS_0 :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '0' ".$email_filter_string. $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '0'".$email_filter_string; 
			break;
			
			# NEUSTREZNI NULL
			case TYPE_STATUS_NULL :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '-1' ".$email_filter_string. $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '-1'.$email_filter_string"; 
			break;

			# kumuativni statusi
			# KUMULATIVNI 6 BREZ LURKERJEV
			case TYPE_STATUS_KUMULATIVE_6 :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '6' AND lurker = '0' ".$email_filter_string. $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '6' AND lurker = '0' ".$email_filter_string; 
			break;
			# KUMULATIVNI 5 = 6 in 5 BREZ LURKERJEV
			case TYPE_STATUS_KUMULATIVE_5 :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status IN ('6','5') AND lurker = '0' ".$email_filter_string. $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '6' AND lurker = '0' ".$email_filter_string; 
			break;

			# KUMULATIVNI 5LL = 6 in 5 z lurkerji
			case TYPE_STATUS_KUMULATIVE_5ll :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status IN ('6','5') ".$email_filter_string. $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '6' AND lurker = '0' ".$email_filter_string; 
			break;

			# KUMULATIVNI 4LL = 6 in 5 z lurkerji in 4
			case TYPE_STATUS_KUMULATIVE_4ll :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status IN ('6','5','4') ".$email_filter_string. $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '6' AND lurker = '0' ".$email_filter_string; 
			break;

			# KUMULATIVNI 3LL = 6 in 5 z lurkerji in 4 in 3
			case TYPE_STATUS_KUMULATIVE_3ll :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status IN ('6','5','4','3') ".$email_filter_string. $intervalLimit . $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user WHERE ank_id = '" . $this->getSurveyId() . "' AND preview = '0' AND deleted='0' AND last_status = '6' AND lurker = '0' ".$email_filter_string; 
			break;
			
			case TYPE_PAGES :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_user_grupa".$this->db_table." AS grupa, srv_grupa WHERE" .
						" grupa.gru_id = srv_grupa.id AND srv_grupa.ank_id = '".$this->getSurveyId()."'" . $intervalLimit . " ". $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_user_grupa".$this->db_table." AS grupa, srv_grupa WHERE" .
						" grupa.gru_id = srv_grupa.id AND srv_grupa.ank_id = '".$this->getSurveyId()."'";

			break;
			case TYPE_ANALYSIS :
				$sqlString = "SELECT " . $intervalFormat . ", count(*) as cnt FROM srv_tracking".$this->db_table." WHERE" .
						" `get` LIKE '%analiza%' AND srv_tracking".$this->db_table.".ank_id = '".$this->getSurveyId()."'" . $intervalLimit . " ". $groupBy;
				$sqlStringAll = "SELECT count(*) as cnt FROM srv_tracking".$this->db_table." WHERE" .
						" `get` LIKE '%analiza%' AND srv_tracking".$this->db_table.".ank_id = '".$this->getSurveyId()."'";
			break;
		}
		// napolnomo array z damumi med intervali
		$sql = sisplet_query($sqlString) ;
		while ($row = mysqli_fetch_assoc($sql)) {
			$this->range[$row['formatdate']] = $row['cnt'];
		}
		
		// preštejemo vse neodvisno od datuma
		$sqlAll = sisplet_query($sqlStringAll) ;
		$rowAll = mysqli_fetch_assoc($sqlAll);
		$this->allRecordCount = $rowAll['cnt'];

		if ($this->hideNullValues_dates)
			$this->arrayRange = array(); // naredimo prazen array in nato dodamo samo vrednosti > 0

		// kjer imamo zapise popravimo pre"stete vrednosti
		if ($this->range)
		foreach ($this->range as $key => $value) {


			$this->arrayRange[$key] = $value;
			$this->maxValue = max($this->maxValue, $value);
		}
		
	}

	/** Funkcija pripravi podatke za vse panele razen za panel DIsplayData
	 * 
	 */
	function  prepareStatusView() {

        $email_filter_string = null;    		
		if ($this->emailInvitation > 0) {
			if ($this->emailInvitation == 1) {
				$email_filter_string = ' AND inv_res_id is not NULL ';
            } 
            else if($this->emailInvitation == 2) {
				$email_filter_string = ' AND inv_res_id is NULL ';
			}
		}

        // Tukaj ne vem zakaj filtriramo po datumu? Itak rabimo vse
		$qry = sisplet_query("SELECT id, last_status, lurker, testdata, inv_res_id, referer, language 
                                FROM srv_user 
                                WHERE ank_id = '".$this->getSurveyId()."' AND preview='0' AND deleted='0'"
                                    .$email_filter_string
                            );
		if (mysqli_num_rows($qry) > 0) {

			$user_id_to_check_link = array(); # id-ji uporabnikov pri katerih imamo direkten klik. naknadno ugotavljamo ali je slučajno e-mail vabilo
		 	
			while ($row = mysqli_fetch_assoc($qry)) {
				
				if ($this->emailInvitation == 1 && $row['inv_res_id'] != null){
					$this->userByStatus['valid']['email'] += 1;
                }	
                
				if ((int)$row['testdata'] > 0) {
					$this->testDataCount++;
                }

				// dodamo statuse
				if (in_array($row['last_status'], $this->appropriateStatus)){
                    
                    # če ni lurker je ok
					if ($row['lurker'] == 0){
						$this->userByStatus['valid'][$row['last_status']] += 1;
			 			$this->cntUserByStatus['valid'] += 1;
					}
					else{
						# če je lurker ga dodamo k neveljavnim
						$this->userByStatus['nonvalid'][$row['last_status'].'l'] += 1;
			 			$this->cntUserByStatus['nonvalid'] += 1;	
					}
                } 
				# neveljavne enote
				else if (in_array($row['last_status'], $this->unAppropriateStatus)){
					$this->userByStatus['nonvalid'][$row['last_status']] += 1;
			 		$this->cntUserByStatus['nonvalid'] += 1;	
				}
				# emaili
				else if (in_array($row['last_status'], $this->invitationStatus)){
					$this->userByStatus['invitation'][$row['last_status']] += 1;
			 		$this->cntUserByStatus['invitation'] += 1;	
				}

				#polovimo redirekte
				if (in_array((int)$row['last_status'], $this->invitationStatus)){ 	
					# email vabila ... ne lovimo redirektov
					# podatek o referalu je prazen lahko da email ni bil poslan, ali pa gre za direkten link
					#$this->cntNonValidRedirections += 1;
					#$this->userRedirections[(int)$row['last_status']] += 1;
				}
				else {
					# če so vabila
					if ($row['inv_res_id'] != null ){
						$this->cntValidRedirections += 1;
						$this->userRedirections["valid"]['email'] += 1;
                                                //$this->userRedirections["cntAll"] += 1;
						$this->maxRedirection = max($this->maxRedirection , $this->userRedirections["valid"]['email']);
					} 
					# če imamo referal
					else if ($row['referer'] != "" && $row['referer'] != "0"){
						$parsed = parse_url($row['referer']);
						$this->cntValidRedirections += 1;
						$this->userRedirections["valid"][$parsed['host']] += 1;
                                                //$this->userRedirections["cntAll"] += 1;
						$this->maxCharRedirection = max($this->maxCharRedirection , strlen ($parsed['host']) );
						$this->maxRedirection = max($this->maxRedirection , $this->userRedirections["valid"][$parsed['host']] );
					} 
					# če ne je najbrž direkten link
					else{
						# shranimo id_userjev za katere nato ugotavljamo ali je link res direkten ali obstaja kaksen zapis da je slo preko e-maila
						$user_id_to_check_link[] = $row['id'];
						$_tmp_direct +=1;
						
						$this->cntNonValidRedirections += 1;
					}
				}
				
				#polovimo jezike
				if (isset($this->respondentLangArray[$row['language']])){
					$this->respondentLangArray[$row['language']] ++;
				}
				else{
					$this->respondentLangArray[$row['language']] = 1;
				}
		 	}
		 	
		}
		
		# od direktnega klika odštejemo e-mail vabila
	 	if (count($user_id_to_check_link)> 0) {

	 		$qryEmail = sisplet_query("SELECT COUNT(*) as cnt FROM srv_userstatus  WHERE usr_id IN (".implode(',', $user_id_to_check_link).") AND status IN (".implode(',', $this->emailStatus).")");
             $rwsEmail = mysqli_fetch_assoc($qryEmail);
             
	 		$this->userRedirections["email"] = (int)$rwsEmail['cnt'];
            
            $directCnt = (int)$_tmp_direct - (int)$rwsEmail['cnt'];
             
            $this->userRedirections["direct"] = $directCnt;     
            //$this->userRedirections["cntAll"] += $directCnt;
	 	}

		// prestejemo max stevilo klikov za lepsi izris tabele
		$this->maxRedirection = max($this->maxRedirection , $this->userRedirections["2"], $this->userRedirections["1"], $this->userRedirections["0"],$this->userRedirections["direct"], $this->userRedirections['email']);

		# izracunamo realne frekvence po statusih
	 	# Klik na anketo - vsak ki je končal anketo (itd...) je "najbrž" tudi kliknil na anketo..
	 	$this->realUsersByStatus_all = $this->userByStatus['valid'][6] 
	 								+ $this->userByStatus['valid'][5] 
	 								+ $this->userByStatus['nonvalid']['5l'] 
	 								+ $this->userByStatus['nonvalid']['6l'] 
	 								+ $this->userByStatus['nonvalid'][4] 
	 								+ $this->userByStatus['nonvalid'][3] 
	 								+ $this->userByStatus['nonvalid'][-1];
	 	// Klik na prvo stran - vsak ki je končal anketo (itd...) je "najbrž" tudi kliknil na anketo..
		# končal anketo => 6
	 	$this->realUsersByStatus[6] = array('cnt'=>$this->userByStatus['valid'][6], 'percent'=>0);
		# začel izpolnjevat => 5 = 6 + 5
		$this->realUsersByStatus[5] = array('cnt'=>$this->userByStatus['valid'][5]+$this->realUsersByStatus[6]['cnt'], 'percent'=>0); 
	 	# Koliko ljudi je dejansko končalo anketo ne glede na to ali so lurkerji 6 + 6l 
	 	$this->realUsersByStatus['6ll'] = array('cnt'=>$this->userByStatus['nonvalid']['6l']+$this->realUsersByStatus[6]['cnt'], 'percent'=>0);
	 	# delno izpolnjena 4ll => 6 + 5 + 6l + 5l 
	 	$this->realUsersByStatus['5ll'] = array('cnt'=>$this->userByStatus['nonvalid']['6l']+$this->userByStatus['nonvalid']['5l']+$this->realUsersByStatus[5]['cnt'], 'percent'=>0);
		# klik na prvo stran => 4 = 6 + 6l + 5l + 5 + 4
		$this->realUsersByStatus['4ll'] = array('cnt'=>$this->userByStatus['nonvalid'][4]+$this->realUsersByStatus['5ll']['cnt'], 'percent'=>0);
		# klik na anketo => 3 = 6 + 6l + 5l + 5 + 4 + 3 
		$this->realUsersByStatus['3ll'] = array('cnt'=>$this->userByStatus['nonvalid'][3]+$this->userByStatus['nonvalid'][-1]+$this->realUsersByStatus['4ll']['cnt'], 'percent'=>0);
		//if ($this->emailInvitation == 1) 
		{
			$this->realUsersByStatus['email'] 
			= array('cnt'=>(isset($this->userByStatus['valid']['email'])?$this->userByStatus['valid']['email']:0), 'percent'=>0);
		}
		// izracunamo se procente
		# v odvisnosti od osnove (dropdown) izberemo kaj nam predstavlja 100%
		$status_100_percent = ($this->realUsersByStatus_base == '3ll') 
			? $this->realUsersByStatus_all 
			: $this->realUsersByStatus[$this->realUsersByStatus_base]['cnt'];

		foreach ($this->realUsersByStatus as $key => $value) {
			$this->realUsersByStatus[$key]['percent'] = ($status_100_percent > 0) ? $value['cnt'] / $status_100_percent : 0;
		}	 	 

		# komentarji
		$SD = new SurveyDiagnostics($this->surveyId);
		$this->comments = $SD->testComments();
	}

	
	/** Funkcija klice funkcije za prikaz statistike 
	 * 	DisplayInfoView - prikaze panelo z osnovnimi informacijami
	 *  DisplayStatusView - prikaze panelo z kliki po statusih 
	 *  DisplayReferalsView - prikaze panelo z redirekcijami in referali
	 *  DisplayDataView - prikate panelo z kliki po datumih
	 */
	function Display()  {
		global $lang, $site_url;

		$dashboardHtml = null;
				
		// Preverjamo ce imamo slucajno izklopljeno shranjevanje datumov odgovorov (potem ni vecine statusov)
		$paradata_date = SurveySetting::getInstance()->getSurveyMiscSetting('survey_date');
        
        
		$dashboardCacheFile = $this->CheckDashbordChacheFile();
        
        
        // Preverimo stanje datoteke s podatki
        $SDF = SurveyDataFile::get_instance();
        $SDF->init($this->surveyId);           


		# iz chace preberemo samo za osnovni profil
		if ($this->doCache && $dashboardCacheFile !== null && $this->isDefaultFilters == true ) {

			#čas updejta iz dashboarda
			$_sql_string = "SELECT DATE_FORMAT(dashboard_update_time,'%d.%m.%Y %H:%i:%s') FROM srv_data_files WHERE sid = '".$this->surveyId."'";
			$_sql_qry = sisplet_query($_sql_string);
			list($dashboard_update_time) = mysqli_fetch_row($_sql_qry);
			echo  '<span id="srv_dashboard_updated">'.$lang['srv_dashboard_updated'].$dashboard_update_time.'</span>';
			
			#preberemo podatke o datoteki
			echo $SDF->getDataFileInfo();
			
			# preberemo cache file in ga zehamo
			echo $this->ReadCacheFile();
		} 
		else {
			
			$dashboard_update_time = date("d.m.Y, H:i:s");
			echo  '<span id="srv_dashboard_updated">'.$lang['srv_dashboard_updated'].$dashboard_update_time.'</span>';
			
			#preberemo podatke o datoteki
			echo $SDF->getDataFileInfo();
			
			// Ce ne zbiramo parapodatkov casov resevanja izpisemo opozorilo
			if($paradata_date == 1)
				echo  '<br /><br /><span>'.$lang['srv_dashboard_paradata_date_warning'].'</span>';
			
			// Prikazemo filter na datum ce je vklopljen
			if (SurveyTimeProfiles::getCurentProfileId() != STP_DEFAULT_PROFILE){
				echo '<br class="clr" /><br class="clr" />';
				echo '<div id="displayFilterNotes">';
				SurveyTimeProfiles :: printIsDefaultProfile();
				echo '</div>';
			}
			
			# Cache file ne obstaja. Če imamo privzete nastavitve vseh filtrov, shranimo prikazan html v datoteko
			# spodnje ehote shranimo v spremenljivko ki jo popotrebi keširanja shranimo v datoteko.
			
			ob_start();
			$email_filter_string = null;
			if ($this->emailInvitation > 0) {
				if ($this->emailInvitation == 1) {
					$email_filter_string = ' AND inv_res_id is not NULL ';
				} else if($this->emailInvitation == 2) {
					$email_filter_string = ' AND inv_res_id is NULL ';
				}
			}
				
			// preverimo ali ima anketa kakšne vnose
			$str_qry_all_users = "SELECT count(u.id) AS user_count FROM srv_user AS u " . "WHERE u.ank_id = '".$this->getSurveyId()."' AND preview = '0' AND deleted='0' ".$email_filter_string; 
			$qry_all_users = sisplet_query($str_qry_all_users);
			$row_all_users = mysqli_fetch_assoc($qry_all_users);
			$allUserCount = $row_all_users['user_count']; 
			
			// nimamo še vnosov
			if ($allUserCount == 0 || $paradata_date == 1) {
			
				// zgornji boxi
				echo '<table class="dashboard dashboard_single">';
				echo '<tr>';
				echo '<td>';
				echo '<div class="dashboard_cell" name="div_statistic_info" id="div_statistic_info" >'."\n";
				$this -> DisplayInfoView();
				echo '</div>';
				echo '</td>';
				echo '</tr>';
				echo '</table>';
			} 
			// imamo vnose, prikažemo statistiko
			else {

				$this->PrepareDateView();
				$this->PrepareStatusView();

				echo '<table class="dashboard">';
				echo '<tr>';
				
				// zgornji boxi
				echo '<td>';
				echo '<div class="dashboard_cell" name="div_statistic_info" id="div_statistic_info" >'."\n";
				$this -> DisplayInfoView();
				echo '</div>';
				echo '</td>';
					
				echo '<td>';
				echo '<div class="dashboard_cell" name="div_statistic_status" id="div_statistic_status" >'."\n";
				$this -> DisplayStatusView();
				echo '</div>';
				echo '</td>';
				
				echo '<td>';
				echo '<div class="dashboard_cell" name="div_statistic_answer_state" id="div_statistic_answer_state" >'."\n";
				$this -> DisplayAnswerStateView();
				echo '</div>';
				echo '</td>';
				echo '</tr>';
			
				// spodnji boxi
				echo '<tr>';
				echo '<td>';
				echo '<div class="dashboard_cell"  id="div_statistic_referals">';
				$this -> DisplayReferalsView();
				echo '</div>';
				echo '</td>';
				
				echo '<td>';
				echo '<div class="dashboard_cell" id="div_statistic_visit">';
				echo '<span class="dashboard_title">'.$lang['srv_statistic_timeline_title'].'</span>'.Help :: display('srv_statistic_timeline_title');
				$this -> DisplayFilters();
				echo '<br/>';
				echo '<div name="div_statistic_visit_data" id="div_statistic_visit_data" >'."\n";
				$this -> DisplayDateView();
				echo '</div>';
				echo '</div>';
				echo '</td>';
				
				echo '<td>';
				echo '<div class="dashboard_cell" id="div_statistic_pages_state">';
				$this -> DisplayPagesStateView();
				echo '</div>';
				echo '</td>';

				echo '</tr>';
				echo '</table>';
			}
						
			# HTML zapišemo v spremenljivko
			
			$dashboardHtml = ob_get_clean();
			# če imamo default filtre zapišemo v datoteko; Prav tako mora bit izbran osnovni profil intervala z id = 0
			if ($dashboardHtml != null && $this->isDefaultFilters == true) {
				# poiščemo čase

				# poiščemo datume sprememb v anketi
				$str_qry_surveys = "SELECT id, UNIX_TIMESTAMP(GREATEST(insert_time,edit_time)) as time FROM srv_anketa WHERE id = '".$this->surveyId."'";
				$qry_surveys = sisplet_query($str_qry_surveys);
				list($id,$time1) = mysqli_fetch_row($qry_surveys);

				# poiščemo datume sprememb v userju
				$str_qry_users = "SELECT ank_id, UNIX_TIMESTAMP(GREATEST(max(time_insert), max(time_edit))) as time FROM srv_user WHERE ank_id = '".$this->surveyId."' AND preview = '0' AND deleted='0' GROUP BY ank_id";
				$qry_users = sisplet_query($str_qry_users);
				list($id,$time2) = mysqli_fetch_row($qry_users);
				
				$timestamp = (int)$time1 + (int)$time2;

				$this->WriteToCacheFile($dashboardHtml,$timestamp);
			}
			# izpišemo HTML v browser
			echo $dashboardHtml;
		}
		
		echo '<div class="clr"></div>';
		echo '<span >';
		echo '</span>';
		
		$dashboardHtml = null;
	}

	/** Funkcija prikaze osnovnih informacij
	 * 
	 */
	function DisplayInfoView() {
		global $lang;
		global $site_url;

		echo '<div class="dashboard_title">'.$lang['srv_statistic_info_title'].Help :: display('srv_statistic_info_title').'</div><br/>';
		//SurveyInfo::getInstance()->DisplayInfoBox(false);
		/* zaradi nenehnih sprememb (Vasja) se ne da naredit univerzalno, */
		
		
		echo '<table style="width:100%;">';
		echo '<COLGROUP><COL width="90px"><COL width="100px"><COL width="90px"><COL width="100px"></COLGROUP>';
		
		# ime ankete
		echo '<tr><td>';
		echo $lang['srv_info_name'].':';
		echo '</td><td colspan="3">';
		echo htmlentities(SurveyInfo::getSurveyTitle(), ENT_QUOTES, "UTF-8");
		echo '</td>';
		echo '</tr>';
		if (SurveyInfo::getSurveyInfo()) {
			#opomba
			// prikažemo 30 znakov na mouseover pa kompletno
			echo '<tr><td>';
			echo $lang['srv_info_note'].':';
			echo '</td><td colspan="3" title="' . SurveyInfo::getSurveyInfo() . '">';
			echo htmlentities($this->limitString(SurveyInfo::getSurveyInfo(),30), ENT_QUOTES, "UTF-8");
			echo '</td></tr>';
		}
					
		
		# katere napredne možnosti so vklopljene
		$row = SurveyInfo::getSurveyRow();
		$modules = SurveyInfo::getSurveyModules();
		$enabled_advanced = null;
		$prefix = '';
		if (isset($modules['uporabnost'])) {
			$enabled_advanced .= $prefix . $lang['srv_vrsta_survey_type_4'];
			$prefix = ', '; 	
		}
		if ($row['user_from_cms'] == 1) {
			$enabled_advanced .= $prefix . $lang['srv_vrsta_survey_type_5'];
			$prefix = ', ';	
		}
		if (isset($modules['quiz'])) {
			$enabled_advanced .= $prefix . $lang['srv_vrsta_survey_type_6'];
			$prefix = ', ';
		}
		if (isset($modules['phone'])) {
			$enabled_advanced .= $prefix . $lang['srv_vrsta_survey_type_7'];
			$prefix = ', ';
		}
		if (isset($modules['social_network'])) {
			$enabled_advanced .= $prefix . $lang['srv_vrsta_survey_type_8'];
			$prefix = ', ';
		}
		
		# tip ankete
		echo '<tr><td>';
		echo $lang['srv_info_type'].':';
		echo '</td><td colspan="3">';
		echo $lang['srv_vrsta_survey_type_'.(SurveyInfo::getSurveyType()>2 ? 2 : SurveyInfo::getSurveyType())] . ($enabled_advanced != null ? ' ('.$enabled_advanced.')' : '' );
		echo '</td></tr>';
		# vprašanj, variabel
		echo '<tr><td>';
		echo $lang['srv_info_questions']. ':';
		echo '</td><td>';
		echo SurveyInfo::getSurveyQuestionCount();
		echo '</td><td>';
		echo $lang['srv_info_variables']. ':';
		echo '</td><td>';
		echo SurveyInfo::getSurveyVariableCount();
		echo '</td></tr>';
		# stevilo strani
		echo '<tr><td>';
		echo $lang['srv_info_pages']. ':';
		echo '</td><td>';
		echo SurveyInfo::getSurveyGroupCount();
		echo '</td></tr>';
		# uporabnikov, odgovorov
		echo '<tr><td>';
		echo $lang['srv_analiza_stUporabnikov']. ':';
		echo '</td><td>';
		echo SurveyInfo::getSurveyAnswersCount();
		echo '</td><td>';
		echo $lang['srv_info_answers_valid']. ':';
		echo '</td><td>';
		echo SurveyInfo::getSurveyApropriateAnswersCount();
		echo '</td></tr>';
		# jezik izpolnjevanja
		echo '<tr><td>';
		echo $lang['srv_info_language']. ':';
		echo '</td><td colspan="3" >';
		echo SurveyInfo::getRespondentLanguage();
		echo '</td></tr>';
		# autor
		echo '<tr><td>';
		echo $lang['srv_info_creator']. ':';
		echo '</td><td colspan="3" title="'.SurveyInfo::getSurveyInsertEmail().'">';
		echo SurveyInfo::getSurveyInsertName();
			if (SurveyInfo::getSurveyInsertDate() && SurveyInfo::getSurveyInsertDate() != "00.00.0000")
				echo SurveyInfo::getDateTimeSeperator() . $this->dateFormat(SurveyInfo::getSurveyInsertDate(),DATE_FORMAT_SHORT);
			if (SurveyInfo::getSurveyInsertTime() && SurveyInfo::getSurveyInsertTime() != "00:00:00")
				echo SurveyInfo::getDateTimeSeperator() . $this->dateFormat(SurveyInfo::getSurveyInsertTime(),TIME_FORMAT_SHORT);
		echo '</td></tr>';
		# spreminjal
		echo '<tr><td>';
		echo $lang['srv_info_modify']. ':';
		echo '</td><td colspan="3" title="'.SurveyInfo::getSurveyEditEmail().'">';
		echo SurveyInfo::getSurveyEditName();
			if (SurveyInfo::getSurveyEditDate() && SurveyInfo::getSurveyEditDate() != "00.00.0000")
				echo SurveyInfo::getDateTimeSeperator() . $this->dateFormat(SurveyInfo::getSurveyEditDate(),DATE_FORMAT_SHORT);
			if (SurveyInfo::getSurveyEditTime() && SurveyInfo::getSurveyEditTime() != "00:00:00")
				echo SurveyInfo::getDateTimeSeperator() . $this->dateFormat(SurveyInfo::getSurveyEditTime(),TIME_FORMAT_SHORT);
		echo '</td></tr>';

		#dostop, Kdo razen avtorja ima dostop
		$dostop = SurveyInfo::getSurveyAccessUsers();
		if ($dostop) {
			echo '<tr><td>';
			echo $lang['srv_info_access']. ':';
			echo '</td><td colspan="3">';
			$prefix='';
			foreach ( $dostop as $user) {
					//echo $prefix.'<span style="width:auto;" title="'.$user['email'].'">'.iconv("iso-8859-2", "utf-8",$user['name']).'</span>';
					echo $prefix.'<span style="width:auto;" title="'.$user['email'].'">'.$user['name'].'</span>';
					$prefix='; ';
			}
			echo '</td></tr>';

		}
		# aktivnost
		$activity = SurveyInfo:: getSurveyActivity();
		$_last_active = end($activity);
		echo '<tr><td>';
		echo $lang['srv_displaydata_status']. ':';
		echo '</td><td colspan="3">';
		if (SurveyInfo::getSurveyColumn('active') == 1) {
			echo '<span  style="width:auto; color: green;">'.$lang['srv_anketa_active2'].'</span>';	
		} else {
			# preverimo ali je bila anketa že aktivirana
			if (!isset($_last_active['starts'])) {
				# anketa še sploh ni bila aktivirana
				echo '<span style="width:auto; color:orange;">'.$lang['srv_survey_non_active_notActivated'].'</span>';
			} else {
				# anketa je že bila aktivirna ampak je sedaj neaktivna
				echo '<span style="width:auto; color:orange;">'.$lang['srv_survey_non_active'].'</span>';	
			}
		}
		
		echo '</td></tr>';
		# trajanje: datumi aktivnosti
		if ( count($activity) > 0 ) {
			echo '<tr><td>';
			echo $lang['srv_info_activity']. ':';
			echo '</td><td colspan="3">';
			$prefix = '';
			foreach ($activity as $active) {
				$_starts = explode('-',$active['starts']);
				$_expire = explode('-',$active['expire']);
				
				echo $prefix.$_starts[2].'.'.$_starts[1].'.'.$_starts[0].'-'.$_expire[2].'.'.$_expire[1].'.'.$_expire[0];
				
				# echo $prefix . $this->dateFormat($active['starts'],DATE_FORMAT_SHORT).'-'.$this->dateFormat($active['expire'],DATE_FORMAT_SHORT);
				$prefix = '; ';
			}
			echo '</td></tr>';
		}

		
		# predviceni cas trajanja enkete
		$sas = new SurveyAdminSettings();
		$skupni_cas = $sas->testiranje_cas(1);
		$skupni_predvideni_cas = $sas->testiranje_predvidenicas(1);
		
		$d = new Dostop();
		
		echo '<tr><td>';
		echo $lang['srv_info_duration']. ':';
		echo '</td><td colspan="3">';
		echo ($skupni_cas!=''?'<a href="index.php?anketa='.$this->surveyId.'&a='.A_REPORTI . '&m='.M_TESTIRANJE_CAS.'">':'').$skupni_cas.($skupni_cas!=''?'</a>, ':'');
		
		echo ''.$lang['srv_predvideno'].': ';
		if ($d->checkDostopSub('test'))
			echo '<a href="index.php?anketa='.$this->surveyId.'&a=testiranje&m=predvidenicas">';
		echo $skupni_predvideni_cas;
			if ($d->checkDostopSub('test'))
		echo '</a>';
		
		echo '</td></tr>';
		
		
		//VNOSI - pvi / zadnji vnos
		$prvi_vnos_date = SurveyInfo::getSurveyFirstEntryDate();
		$prvi_vnos_time = SurveyInfo::getSurveyFirstEntryTime();
		$zadnji_vnos_date = SurveyInfo::getSurveyLastEntryDate();
		$zadnji_vnos_time = SurveyInfo::getSurveyLastEntryTime();
		if ($prvi_vnos_date != null) {
			echo '<tr><td>';
			echo $lang['srv_info_first_entry']. ':';
			echo '</td><td '.( $zadnji_vnos_date == null ? 'colspan="3"' : '').'>';

			echo $this->dateFormat($prvi_vnos_date,DATE_FORMAT_SHORT);
			echo $prvi_vnos_time != null ? (SurveyInfo::$dateTimeSeperator .$this->dateFormat($prvi_vnos_time,TIME_FORMAT_SHORT)) : ''; 
			echo '</td>';
		}
		if ($zadnji_vnos_date != null) {
			echo '<td>';
			echo $lang['srv_info_last_entry']. ':';
			echo '</td><td '.( $prvi_vnos_date == null ? 'colspan="3"' : '').'>';
			echo $this->dateFormat($zadnji_vnos_date,DATE_FORMAT_SHORT);
			echo $zadnji_vnos_time != null ? (SurveyInfo::$dateTimeSeperator .$this->dateFormat($zadnji_vnos_time,TIME_FORMAT_SHORT)) : '';
			echo'</td></tr>';
		}
/*
		#linki - urejanje
		echo '<tr><td>';
		echo $lang['srv_stat_edit'] . ':';
		echo '</td><td colspan="3">';
		echo '<a href="'.$site_url.'admin/survey/index.php?anketa='.$this->getSurveyId().'">' . $lang['srv_stat_edit_survey'] . '</a>,';
		echo ' <a href="index.php?anketa='.$this->getSurveyId().'&a=komentarji">'.$lang['comments'].'</a>';
		echo '</td></tr>';

		#linki - povezave
		
	*/			

	
		list($commentsAll,$commentsUnresolved,$commentsQuestionAll,$commentsQuestionUnresolved,$commentsUser,$commentsUserFinished,$commentsUserSurveyAll,$commentsUserSurveyUnresolved) = $this->comments;
		
		$commentsUserUnresolved = $commentsUser - $commentsUserFinished;
		if ((	(int)$commentsAll
				+(int)$commentsUnresolved
				+(int)$commentsQuestionAll
				+(int)$commentsQuestionUnresolved
				+(int)$commentsUser
				+(int)$commentsUserFinished
				) > 0 ) {
			echo '<tr><td>';
			echo $lang['srv_diagnostic_4_element_0'] . ':';
			echo '</td><td colspan="3">';
			echo $lang['srv_diagnostic_4_element_1'];
			echo ':&nbsp;';
			echo (int)$commentsUnresolved. ' / '.(int)$commentsAll;
			echo '</td></tr>';
			echo '<tr><td>';
			echo '&nbsp;';
			echo '</td><td colspan="3">';
			echo $lang['srv_diagnostic_4_element_1a'];
			echo ':&nbsp;';
			echo (int)$commentsUserSurveyUnresolved. ' / '.(int)$commentsUserSurveyAll;
			echo '</td></tr>';
			echo '<tr><td>';
			echo '&nbsp;';
			echo '</td><td colspan="3">';
			echo $lang['srv_diagnostic_4_element_6'];
			echo ':&nbsp;';
			echo (int)$commentsQuestionUnresolved. ' / '.(int)$commentsQuestionAll;
			echo '</td></tr>';
			echo '<tr><td>';
			echo '&nbsp;';
			echo '</td><td colspan="3">';
			echo $lang['srv_diagnostic_4_element_7'];
			echo ':&nbsp;';
			echo (int)$commentsUserUnresolved. ' / '.(int)$commentsUser;
			echo '</td></tr>';
		}
		
		echo '</table>';
	}

	/** Funkcija prikaže statuse odgovorov
	 * 
	 */
	 function DisplayAnswerStateView() {
	 	global $lang;
	 	
	 	if ($this->emailInvitation == 1) {
	 		$order = array('email','3ll','4ll','5ll',5,6);
	 	} else {
	 		$order = array('3ll','4ll','5ll',5,6);
	 	}
		if(true) {
			$this->realUsersByStatus[0]['cnt'] = $this->cntUserByStatus['invitation'];
		}
	 	echo '<div class="floatLeft"><span class="dashboard_title">'.$lang['srv_statistic_answer_state_title'].'</span>'.Help :: display('srv_statistic_answer_state_title');
		echo '</div>';
		echo '<div class="floatRight">'.$lang['srv_statistic_answer_state_base'].': ';
		echo '<select id="userStatusBase" onchange="changeUserStatusBase()">';
		foreach ($order as $key) {
			echo '<option '.($this->realUsersByStatus_base.'' == $key.'' ? ' selected="selected"' : '').' value="'.$key.'" >'.$lang['srv_userstatus_'.$key].'</option>';
		}
		echo '</select>';

	 	echo '</div>';
	 	echo '<br class="clr"/>';
		echo '<br />';
	 	
	 	echo '<table id="tbl_answ_state">';
		echo '<tr class="anl_dash_bb "><th><strong>'.$lang['srv_statistic_answer_state_status'].'</strong></th><td><strong>'.$lang['srv_statistic_answer_state_frequency'].'</strong></td><td><strong>'.$lang['srv_statistic_answer_state_percent'].'</strong></td></tr>';
	 	foreach ($order as $key) {
	 		if ($this->realUsersByStatus_base == $key) {
	 			$base_found = true;
	 		}
	 		echo '<tr><th>'.$lang['srv_userstatus_'.$key].'</th>';
	 		#frekvenca
	 		echo '<td>'.($this->realUsersByStatus[$key]['cnt'] > 0 ? $this->realUsersByStatus[$key]['cnt'] : '0').'</td>';
			#procenti
			;
	 		echo '<td>';
	 		#echo ( (float)$this->realUsersByStatus[$key]['percent'] > 1.0) 
	 		echo ( $base_found == false) 
	 			? '--'
	 			: $this->formatNumber($this->realUsersByStatus[$key]['percent']*100,NUM_DIGIT_PERCENT,'%');
	 		echo '</td>';
	 		echo '</tr>';
	 	}
	 	echo '</table>';
		
	
		echo '<br />';
		
		
		// Uporabnost respondentov
		$sur = new SurveyUsableResp($this->surveyId, $generateDatafile=false);
		if(!$sur->hasDataFile())
			echo '<br />';
//			echo $lang['srv_dashboard_no_file'].'<br /><br />';
		else{
			$usability = $sur->calculateData();

			echo '<table id="tbl_answ_usability">';
			echo '<tr class="anl_dash_bb "><th><strong>'.$lang['srv_statistic_answer_state_usability'].' ('.$sur->bottom_usable_limit.'%/'.$sur->top_usable_limit.'%)</strong></th><td><strong>'./*$lang['srv_statistic_answer_state_frequency'].*/'</strong></td><td><strong>'./*$lang['srv_statistic_answer_state_percent'].*/'</strong></td></tr>';

			echo '<tr><th>'.$lang['srv_usableResp_usable_unit'].'</th>';
			echo '<td>'.$usability['usable'].'</td>';
			if($usability['all'] > 0)
				echo '<td>'.$this->formatNumber($usability['usable']/$usability['all']*100, NUM_DIGIT_PERCENT, '%').'</td>';
			else
				echo '<td>'.$this->formatNumber(0, NUM_DIGIT_PERCENT, '%').'</td>';
			echo '</tr>';
			
			echo '<tr><th>'.$lang['srv_usableResp_partusable_unit'].'</th>';
			echo '<td>'.$usability['partusable'].'</td>';
			if($usability['all'] > 0)
				echo '<td>'.$this->formatNumber($usability['partusable']/$usability['all']*100, NUM_DIGIT_PERCENT, '%').'</td>';
			else
				echo '<td>'.$this->formatNumber(0, NUM_DIGIT_PERCENT, '%').'</td>';
			echo '</tr>';
			
			echo '<tr><th>'.$lang['srv_usableResp_unusable_unit'].'</th>';
			echo '<td>'.$usability['unusable'].'</td>';
			if($usability['all'] > 0)
				echo '<td>'.$this->formatNumber($usability['unusable']/$usability['all']*100, NUM_DIGIT_PERCENT, '%').'</td>';
			else
				echo '<td>'.$this->formatNumber(0, NUM_DIGIT_PERCENT, '%').'</td>';
			echo '</tr>';
				
			echo '</table>';
			
			echo '<br />';
		}
		
		// Breakoffi
		$status3 = (isset($this->userByStatus['nonvalid']['3'])) ? $this->userByStatus['nonvalid']['3'] : 0;
		$status4 = (isset($this->userByStatus['nonvalid']['4'])) ? $this->userByStatus['nonvalid']['4'] : 0;
		$status5 = (isset($this->userByStatus['valid']['5'])) ? $this->userByStatus['valid']['5'] : 0;
		$status5l = (isset($this->userByStatus['nonvalid']['5l'])) ? $this->userByStatus['nonvalid']['5l'] : 0;
		$status6 = (isset($this->userByStatus['valid']['6'])) ? $this->userByStatus['valid']['6'] : 0;
		$all = (isset($this->realUsersByStatus['3ll']['cnt'])) ? $this->realUsersByStatus['3ll']['cnt'] : 0;
		if($all > 0){
			echo '<table id="tbl_answ_breakoff">';
			echo '<tr class="anl_dash_bb "><th><strong>'.$lang['srv_statistic_answer_state_breakoff'].'</strong></th><td><strong>'./*$lang['srv_statistic_answer_state_frequency'].*/'</strong></td><td><strong>'./*$lang['srv_statistic_answer_state_percent'].*/'</strong></td></tr>';	
			
			$introBreakoff = $status3 + $status4;
			$introBreakoffPercent = $introBreakoff / $all;
			echo '<tr><th>'.$lang['srv_statistic_answer_state_breakoff_1'].'</th>';
			echo '<td>'.$introBreakoff.'</td>';
			echo '<td>'.$this->formatNumber($introBreakoffPercent*100, NUM_DIGIT_PERCENT, '%').'</td>';
			echo '</tr>';
			
			$qBreakoff = $status5 + $status5l;
			$qBreakoffPercent = $qBreakoff / $all;
			if(($all - $status3 - $status4) > 0)
				$qBreakoffNeto = $qBreakoff / ($all - $status3 - $status4);
			echo '<tr><th>'.$lang['srv_statistic_answer_state_breakoff_2'].'</th>';
			echo '<td>'.$qBreakoff.'</td>';
			echo '<td>'.$this->formatNumber($qBreakoffPercent*100, NUM_DIGIT_PERCENT, '%').' (neto '.$this->formatNumber($qBreakoffNeto*100, NUM_DIGIT_PERCENT, '%').')</td>';
			echo '</tr>';
			
			$totalBreakoff = $status3 + $status4 + $status5 + $status5l;
			$totalBreakoffPercent = $totalBreakoff / $all;
			echo '<tr><th>'.$lang['srv_statistic_answer_state_breakoff_3'].'</th>';
			echo '<td>'.$totalBreakoff.'</td>';
			echo '<td>'.$this->formatNumber($totalBreakoffPercent*100, NUM_DIGIT_PERCENT, '%').'</td>';
			echo '</tr>';
				
			echo '</table>';
		}
                $this->JsonAnswerStateView();
	 }
         
         /** Funkcija kreira json statuse odgovorov (za API)
	 * 
	 */
	 function JsonAnswerStateView() {
	 	global $lang;
	 	
	 	if ($this->emailInvitation == 1) {
	 		$order = array('email','3ll','4ll','5ll',5,6);
	 	} else {
	 		$order = array('3ll','4ll','5ll',5,6);
	 	}
		if(true) {
			$this->realUsersByStatus[0]['cnt'] = $this->cntUserByStatus['invitation'];
		}
                
	 	$json_array = array();
                
	 	foreach ($order as $key) {
	 		if ($this->realUsersByStatus_base == $key) {
	 			$base_found = true;
	 		}
                        $json_array['status'][$key] = ['freq' => $this->realUsersByStatus[$key]['cnt'] > 0 ? $this->realUsersByStatus[$key]['cnt'] : '0',
                            'state' => ( $base_found == false) ? '--'
	 			: $this->formatNumber($this->realUsersByStatus[$key]['percent']*100,NUM_DIGIT_PERCENT,'%')];
	 	}		
		
		// Uporabnost respondentov
		$sur = new SurveyUsableResp($this->surveyId, $generateDatafile=false);
		if($sur->hasDataFile()){
			$usability = $sur->calculateData();

                        $json_array['usability']['unit'] = '('.$sur->bottom_usable_limit.'%/'.$sur->top_usable_limit.'%)';

                        if(isset($usability['usable']))
                            $json_array['usability']['usable'] = ['freq' => $usability['usable'], 
                                'state' => $this->formatNumber($usability['usable']/$usability['all']*100, NUM_DIGIT_PERCENT, '%')];
                        else
                            $json_array['usability']['usable'] = ['freq' => 0, 'state' => '0%'];

                        if(isset($usability['partusable']))
                            $json_array['usability']['partusable'] = ['freq' => $usability['partusable'], 
                                'state' => $this->formatNumber($usability['partusable']/$usability['all']*100, NUM_DIGIT_PERCENT, '%')];
                        else
                            $json_array['usability']['partusable'] = ['freq' => 0, 'state' => '0%'];
                        
                        if(isset($usability['unusable']))
                            $json_array['usability']['unusable'] = ['freq' => $usability['unusable'],                    
                                'state' => $this->formatNumber($usability['unusable']/$usability['all']*100, NUM_DIGIT_PERCENT, '%')];
                        else
                            $json_array['usability']['unusable'] = ['freq' => 0, 'state' => '0%'];
		}
                else
                    $json_array['usability'] = array();
		
		// Breakoffi
		$status3 = (isset($this->userByStatus['nonvalid']['3'])) ? $this->userByStatus['nonvalid']['3'] : 0;
		$status4 = (isset($this->userByStatus['nonvalid']['4'])) ? $this->userByStatus['nonvalid']['4'] : 0;
		$status5 = (isset($this->userByStatus['valid']['5'])) ? $this->userByStatus['valid']['5'] : 0;
		$status5l = (isset($this->userByStatus['nonvalid']['5l'])) ? $this->userByStatus['nonvalid']['5l'] : 0;
		$status6 = (isset($this->userByStatus['valid']['6'])) ? $this->userByStatus['valid']['6'] : 0;
		$all = (isset($this->realUsersByStatus['3ll']['cnt'])) ? $this->realUsersByStatus['3ll']['cnt'] : 0;
		if($all > 0){
			$introBreakoff = $status3 + $status4;
			$introBreakoffPercent = $introBreakoff / $all;
                        
                        $json_array['breakoffs']['intro'] = ['freq' => $introBreakoff,                    
                            'state' => $this->formatNumber($introBreakoffPercent*100, NUM_DIGIT_PERCENT, '%')];
                                               
                        $qBreakoff = $status5 + $status5l;
			$qBreakoffPercent = $qBreakoff / $all;
			if(($all - $status3 - $status4) > 0)
				$qBreakoffNeto = $qBreakoff / ($all - $status3 - $status4);
                        
			$json_array['breakoffs']['questionnaire'] = ['freq' => $qBreakoff,                    
                            'state' => $this->formatNumber($qBreakoffPercent*100, NUM_DIGIT_PERCENT, '%').
                            ' (neto '.$this->formatNumber($qBreakoffNeto*100, NUM_DIGIT_PERCENT, '%').')'];
                        
                        $totalBreakoff = $status3 + $status4 + $status5 + $status5l;
			$totalBreakoffPercent = $totalBreakoff / $all;
                        
                        $json_array['breakoffs']['total'] = ['freq' => $totalBreakoff,                    
                            'state' => $this->formatNumber($totalBreakoffPercent*100, NUM_DIGIT_PERCENT, '%')];
		}

                return $json_array;
	 }
	 
	 /** Funkcija prikaže statuse
	 *  KONČNI STATUSI
	 */
	 function DisplayStatusView() {
	 	global $lang;
	 	
		echo '<span class="floatLeft dashboard_title">'.$lang['srv_statistic_status_title'].Help :: display('srv_statistic_status_title');
		echo '</span>';

		echo '<span class="floatRight">';
		echo $lang['srv_statistic_hide_null'];
		echo '<input id="hideNullValues_status" name="hideNullValues_status" type="checkbox" onchange="statisticStatusRefresh(); return false;"'.($this->hideNullValues_status ? ' checked="checked"' : '').' autocomplete="off">';
		echo '</span>';
		
		echo '<br class="clr"/><br/>';

		$cntValid = 0; // da vemo ali izpisemo skupne
		$cntNonValid = 0; // da vemo ali izpisemo skupne
		$cntInvitation = 0; // da vemo ali izpisemo skupne
		foreach ($this->appropriateStatus as $status) {
			if (!($this->hideNullValues_status && $this->userByStatus['valid'][$status] == 0)) {// da ne delamo po neporebnem
		 		echo '<span class="dashboard_status_span">' . $lang['srv_userstatus_'.$status] . ' ('.$status.') :</span>' . $this->userByStatus['valid'][$status].'<br/>';
				$cntValid++;
			}
		}
		// vsota vlejavnih
		if ($cntValid > 0 || !$this->hideNullValues_status) {
			echo '<div class="anl_dash_bt full strong"><span class="dashboard_status_span">'.$lang['srv_statistic_redirection_sum_valid'].'</span>'.($this->cntUserByStatus['valid']).'<br/></div><br/>';	
		} 

		// izpišemo še neveljavne
		foreach ($this->unAppropriateStatus as $status) {
			if (!($this->hideNullValues_status && $this->userByStatus['nonvalid'][$status] == 0)) {// da ne delamo po neporebnem
		 		echo '<span class="dashboard_status_span">' . $lang['srv_userstatus_'.$status] . ' ('.$status.') :</span>' . $this->userByStatus['nonvalid'][$status] 
				. ( ( $index <= 2 && $this->userByStatus['valid'][$status] > 0 )
					? '&nbsp;<a href="#" onclick="survey_statistic_status(\''.$status.'\')">'.$lang['srv_statistic_detail'].'</a>'
					: '') 
				. '<br/>';
				$cntNonValid++;
			}
		}
		// se status null (neznan status)
		/*if (!($this->hideNullValues_status && $this->userByStatus['nonvalid'][-1] == 0)) {// da ne delamo po neporebnem
			echo '<span class="dashboard_status_span">' . $lang['srv_userstatus_null'] . ' (null) :</span>' . (isset($this->userByStatus['nonvalid'][-1]) ? $this->userByStatus['nonvalid'][-1] : '0') . '<br/>';
			$cntNonValid++;
		}*/
		
		// vsota nevlejavnih 
		if ($cntNonValid > 0 || !$this->hideNullValues_status) {
			echo '<div class="anl_dash_bt full strong"><span class="dashboard_status_span">'.$lang['srv_statistic_redirection_sum_nonvalid'].'</span>'.($this->cntUserByStatus['nonvalid']).'<br></div><br/>';	
		}
		
		// Klikov na povezavo
		SurveySetting::getInstance()->setSID($this->surveyId);
		$view_count = SurveySetting::getInstance()->getSurveyMiscSetting('view_count'); 
		if ($view_count == "") $view_count = 0;	
		if ($view_count > 0 || !$this->hideNullValues_status)
			echo '<div class="full strong"><span class="dashboard_status_span">'.$lang['srv_statistic_redirection_sum_view'].'</span>'.($view_count).'<br /><br /></div>';		
		
		// Vsota anketiranih	
		echo '<div class="anl_dash_bt full strong "><span class="dashboard_status_span">'.$lang['srv_statistic_redirection_sum_surveyed'].'</span>'.($this->cntUserByStatus['valid']+$this->cntUserByStatus['nonvalid']).'<br></div>';		
		// Testni
		if ((int)$this->testDataCount > 0) {
			echo '<div class="full"><span class="dashboard_status_span">('.$lang['srv_statistic_redirection_test'].')</span>'.((int)$this->testDataCount).'<br></div>';
		}
		
		echo '<br class="clr"/>';

		
		# preštejemo še neposlana vabila
		$str = "SELECT count(*) FROM srv_invitations_recipients WHERE ank_id='".$this->getSurveyId()."' AND sent='0' AND deleted='0'";

		$qry = sisplet_query($str);
		list($cntUnsent) = mysqli_fetch_row($qry);
		$this->userByStatus['invitation'][0] = (int)$cntUnsent; 

		# še email vabila
		// ker izpade čudno, statusov email neposlan
        if (count(array_filter($this->userByStatus['invitation'])) > 0 || !$this->hideNullValues_status){
		
			echo '<span class="floatLeft strong">'.$lang['srv_statistic_nonsurveyed_title'];
			echo '</span>';
			echo '<br class="clr"/>';
				
			foreach ($this->invitationStatus as $status) 
			{
				if (!($this->hideNullValues_status && $this->userByStatus['invitation'][$status] == 0)) {// da ne delamo po neporebnem
					echo '<span class="dashboard_status_span">' . $lang['srv_statistic_email_status_'.$status] . ' ('.$status.') :</span>' . $this->userByStatus['invitation'][$status]
					#. ( ( $status <= 2 && $this->userByStatus['invitation'][$status] > 0 )
					#		? '&nbsp;<a href="#" onclick="survey_statistic_status(\''.$status.'\')">'.$lang['srv_statistic_detail'].'</a>'
					#		: '')
							. '<br/>';
					$cntInvitation++;
				}
			}
		}
	
		// vsota emaili
		if ($cntInvitation > 0 || !$this->hideNullValues_status) {
			echo '<div class="anl_dash_bt full strong"><span class="dashboard_status_span">'.$lang['srv_statistic_sum2'].'</span>'.($this->cntUserByStatus['invitation']).'<br/></div><br/>';
		}
		
		
		// Vsota vseh	
		echo '<div class="anl_dash_bt full strong"><span class="dashboard_status_span">'.$lang['srv_statistic_sum_all'].'</span>'.($this->cntUserByStatus['valid']+$this->cntUserByStatus['nonvalid']+$this->cntUserByStatus['invitation']).'<br></div>';		
	 }
	 
	/** Funkcija za prikaz referalov
	 * #KLIK NA ANKETO#
	 * #PREUSMERITVE#
	 */
	 function DisplayReferalsView() {
	 	global $lang;
	 	global $admin_type;
		
		echo '<div><span class="dashboard_title">'.$lang['srv_statistic_redirection_title'].'</span>'.Help :: display('srv_statistic_redirection_title').'</div>';
		
		// izrisemo graf
		if ( ( $this->cntValidRedirections + $this->cntNonValidRedirections ) > 0) {
			$maxValue = $this->maxRedirection * GRAPH_REDUCE;
			$value_sum = 0;
			
			echo '<table class="survey_referals_tbl">'."\n";
			echo '<tr class="anl_dash_bb">'."\n";
			echo '<th  style="width:'.($this->maxCharRedirection+2).'pt;"><strong>' . $lang['srv_statistic_redirection_site'] . '</strong></th>'."\n";
			echo '<td class="anl_ar"><strong>'.$lang['srv_statistic_redirection_click'].'</strong></td>'."\n";
			echo '</tr>'."\n";
			
			if (count($this->userRedirections["valid"])) {	
				foreach ($this->userRedirections["valid"] as $key => $value) {
					if ($key == 'email')
					{
						echo '<tr>'."\n";
						echo '<th style="width:'.($this->maxCharRedirection+2).'pt;">' . $lang['srv_statistic_redirection_email'] . '</th>'."\n";
						$width = ($maxValue && $value) ? (round($value / $maxValue * 100, 0)) : "0";
						echo '<td><div class="graph_db" style="text-align:right; float:left; width:'.$width.'%">&nbsp;</div><span style="display:block; margin:auto; margin-left:5px; width:20px; float:left">'.$value.'</span></td>'."\n";
						echo '</tr>'."\n";
					}
					else 
					{
						echo '<tr>'."\n";
						echo '<th style="width:'.($this->maxCharRedirection+2).'pt;">' . $key . '</th>'."\n";
						$width = ($maxValue && $value) ? (round($value / $maxValue * 100, 0)) : "0";
						echo '<td><div class="graph_db" style="text-align:right; float:left; width:'.$width.'%">&nbsp;</div><span style="display:block; margin:auto; margin-left:5px; width:20px; float:left">'.$value.'</span></td>'."\n";
						echo '</tr>'."\n";
					}
					$value_sum += $value;
				}
			}
			// dodamo še direktni link
			if ($this->userRedirections["direct"] > 0) {
				$value = $this->userRedirections["direct"];
				echo '<tr>'."\n";
				echo '<th style="width:'.($this->maxCharRedirection+2).'pt;">' . $lang['srv_statistic_redirection_direct'] . '</th>'."\n";
				$width = ($maxValue && $value) ? (round($value / $maxValue * 100, 0)) : "0";
				echo '<td><div class="graph_db" style="text-align:right; float:left; width:'.$width.'%">&nbsp;</div><span style="display:block; margin:auto; margin-left:5px; width:20px; float:left">'.$value.'</span></td>'."\n";
				echo '</tr>'."\n";
				$value_sum += $value;
			}			
			// dodamo še email klik
                        // @Uros to je identicno, kot zgoraj v foreach, na koncu se gleda rezultat iz valid...se to sploh rabi?
			if ($this->userRedirections["email"] > 0) {
				$value = $this->userRedirections["email"];
				echo '<tr>'."\n";
				echo '<th style="width:'.($this->maxCharRedirection+2).'pt;">' . $lang['srv_statistic_redirection_email'] . '</th>'."\n";
				$width = ($maxValue && $value) ? (round($value / $maxValue * 100, 0)) : "0";
				echo '<td><div class="graph_db" style="text-align:right; float:left; width:'.$width.'%">&nbsp;</div><span style="display:block; margin:auto; margin-left:5px; width:20px; float:left">'.$value.'</span></td>'."\n";
				echo '</tr>'."\n";
				$value_sum += $value;
			}			
			// dodamo sumo
			echo '<tr class="anl_dash_bt strong">'."\n";
			echo '<th  style="width:'.($this->maxCharRedirection+2).'pt;"><strong>' . $lang['srv_statistic_redirection_sum_clicked'] . '</strong></th>'."\n";
			echo '<td style="text-align:left">'.$value_sum.'</td>'."\n";
			echo '</tr>'."\n";
			echo '<tr class="">'."\n";
			echo '<th colspan="2">&nbsp;</th>'."\n";
			echo '</tr>'."\n";
			
			/*
			// dodamo se neveljavne
			$value_sum_nonvalid = 0;
			for ($key = 2; $key >= 0; $key--) {
				$value = $this->userRedirections["$key"];
				if ($value > 0) {
					echo '<tr>'."\n";
					echo '<th style="width:'.($this->maxCharRedirection+2).'pt;">' . $lang['srv_statistic_redirection_email_'.$key] . '</th>'."\n";
					$width = ($maxValue && $value) ? (round($value / $maxValue * 100, 0)) : "0";
					echo '<td><div class="graph_ly" style="text-align:right; float:left; width:'.$width.'%">&nbsp;</div><span style="display:block; margin:auto; margin-left:5px; width:20px; float:left">'.$value.'</span></td>'."\n";
					echo '</tr>'."\n";
					$value_sum_nonvalid += $value;
				}
			}
			// dodamo sumo
			if ($value_sum_nonvalid > 0 ) {
				echo '<tr class="anl_dash_bt strong">'."\n";
				echo '<th style="width:'.($this->maxCharRedirection+2).'pt;"><strong>' . $lang['srv_statistic_redirection_sum_nonvalid'] . '</strong></th>'."\n";
				echo '<td style="text-align:left">'.$value_sum_nonvalid.'</td>'."\n";
				echo '</tr>'."\n";
				echo '<tr class="">'."\n";
				echo '<th colspan="2">&nbsp;</th>'."\n";
				echo '</tr>'."\n";
			}
			*/
			if (!($value_sum_nonvalid == 0 || $value_sum == 0 )) {
				echo '<tr class="anl_dash_bt strong">'."\n";
				echo '<th  style="width:'.($this->maxCharRedirection+2).'pt;"><strong>' . $lang['srv_statistic_redirection_sum'] . '</strong></th>'."\n";
				echo '<td style="text-align:left">'.($value_sum+$value_sum_nonvalid).'</td>'."\n";
				echo '</tr>'."\n";
			}
			echo '</table>'."\n";
			
			echo '<div id="referal_detail"><span class="dashboard_title">'.$lang['srv_statistic_details'].'</span></div>';
			if ($this->cntValidRedirections > 0) {
				echo '<div class="spaceLeft"><a href="#" onclick="survey_statistic_referal(this); return false;" value="0" title="'.$lang['srv_statistic_detail_referal'].'">'.$lang['srv_statistic_detail_referal'].'</a></div>';
			} else {
				echo '<div class="spaceLeft">'.$lang['srv_statistic_show_no_referals'].'</div>';
			}
			echo '<div class="spaceLeft"><a href="#" onclick="ip_list_podrobno(this); return false;" value="0" title="'.$lang['srv_statistic_detail_IP'].'">'.$lang['srv_statistic_detail_IP'].'</a></div>';
		}
/*		
		#echo '<div><p><strong>'.$lang['srv_count_ip_list'].': '.count($this->ip_list).'</strong></p></div>';
		echo '<div><p><strong>'.$lang['srv_detail_ip_list'].': </strong></p></div>';
		echo '<p>&nbsp;<a href="#" onclick="ip_list_podrobno(this); return false;" value="0">'.$lang['srv_statistic_detail'].'</a></p>';
*/
		# skrita div aza podrobnosti
		echo '<div id="survey_referals" class="displayNone"></div>';
		echo '<div id="ip_list_podrobno" class="displayNone"></div>';
	}

	/** Funkcija za prikaz klikov po straneh
	 * 
	 */
	 function DisplayPagesStateView() {
	 	global $lang;
	 	
	 	# ali lovimo samo strani ki niso bile preskočene
	 	$grupa_jump = "AND ug.preskocena = 0 ";
	 	
	 	echo '<span class="dashboard_title">'.$lang['srv_statistic_pages_state_title'].'</span>'.Help :: display('srv_statistic_pages_state_title');
			
		// Filter po osnovi
		if ($this->emailInvitation == 1) {
	 		$order = array('email','3ll','4ll','5ll',5,6);
	 	} else {
	 		$order = array('3ll','4ll','5ll',5,6);
	 	}
		echo '<div class="floatRight">'.$lang['srv_statistic_answer_state_base'].': ';
		echo '<select id="pageUserStatusBase" onchange="changePageUserStatusBase()">';
		foreach ($order as $key) {
			echo '<option '.($this->pageUsersByStatus_base.'' == $key.'' ? ' selected="selected"' : '').' value="'.$key.'" >'.$lang['srv_userstatus_'.$key].'</option>';
		}
		echo '</select>';
		echo '</div>';
		
		$status_filter_string = '';
		switch($this->pageUsersByStatus_base){
			case 'email':
				//$status_filter_string = "AND u.last_status IN ('6','5')";
				break;
				
			case '3ll':
				$status_filter_string = "AND u.last_status IN ('3','4','6','5')";
				break;
				
			case '4ll':
				$status_filter_string = "AND u.last_status IN ('4', '6','5')";
				break;
				
			case '5ll':
				$status_filter_string = "AND u.last_status IN ('6','5')";
				break;
				
			case '5':
				$status_filter_string = "AND u.last_status IN ('6','5') AND u.lurker='0'";
				break;
				
			case '6':
				$status_filter_string = "AND u.last_status='6' AND u.lurker='0'";
				break;
		}
			
		echo '<br class="clr">';		
		
	 	$pages=array();		
	 	$maxValue = 0;
	 	if ($this->emailInvitation > 0) {
	 		if ($this->emailInvitation == 1) {
	 			$email_filter_string = ' AND inv_res_id is not NULL ';
	 		} else if($this->emailInvitation == 2) {
	 			$email_filter_string = ' AND inv_res_id is NULL ';
	 		}
	 	}
	 	 
	 	# polovimo imena strani in preštejemo klike
	 	$sql= "SELECT g.id, g.naslov, g.vrstni_red, COUNT( ug.usr_id ) cnt FROM srv_grupa g LEFT JOIN srv_user_grupa".$this->db_table." ug ON g.id = ug.gru_id"
	 			." JOIN srv_user u ON u.id=ug.usr_id"
	 			." WHERE ug.time_edit BETWEEN '".$this->startDate."' AND '".$this->endDate."' + INTERVAL 1 DAY ".$grupa_jump
				." AND g.ank_id = '".$this->getSurveyId()."' AND u.preview='0' AND u.deleted='0' ".$email_filter_string." ".$status_filter_string." GROUP BY g.id ORDER BY g.vrstni_red";
		
		$qry = sisplet_query($sql);
		if (!$qry) echo mysqli_error($GLOBALS['connect_db']);
	 	while ($row = mysqli_fetch_assoc($qry)) {
	 		$pages[$row['id']] = array('naslov'=>$row['naslov'],'vrstni_red'=>$row['vrstni_red'],'cnt'=>$row['cnt']);
	 		$maxValue = max($maxValue, $row['cnt']);
	 	}

	 	$maxValue = max($maxValue, $this->realUsersByStatus['3ll']['cnt']);
	 	$maxValue = $maxValue * GRAPH_REDUCE;
		echo '<table class="survey_referals_tbl">'."\n";
		echo '<tr class="anl_dash_bb">'."\n";
		echo '<th  style="width:10pt;"><strong>' . $lang['srv_statistic_answer_state_status'] . '</strong></th>'."\n";
		echo '<td class="anl_ar"><strong>'.$lang['srv_statistic_redirection_click'].'</strong></td>'."\n";
		echo '</tr>'."\n";

		# status 3 - "Klik na anketo"
		$value = $this->realUsersByStatus['3ll']['cnt'];
		echo '<tr>'."\n";
		echo '<th style="width: 10pt;">' .  $lang['srv_userstatus_3'] . '</th>'."\n";
		$width = ($maxValue && $value) ? (round($value / $maxValue * 100, 0)) : "0";
		echo '<td><div class="graph_db" style="text-align:right; float:left; width:'.$width.'%">&nbsp;</div><span style="display:block; margin:auto; margin-left:5px; width:20px; float:left">'.$value.'</span></td>'."\n";
		echo '</tr>'."\n";
		
		# status 4 - "Klik na prvo stran"
		$value = $this->realUsersByStatus['4ll']['cnt'];
		echo '<tr>'."\n";
		echo '<th style="width: 10pt;">' .  $lang['srv_userstatus_4'] . '</th>'."\n";
		$width = ($maxValue && $value) ? (round($value / $maxValue * 100, 0)) : "0";
		echo '<td><div class="graph_db" style="text-align:right; float:left; width:'.$width.'%">&nbsp;</div><span style="display:block; margin:auto; margin-left:5px; width:20px; float:left">'.$value.'</span></td>'."\n";
		echo '</tr>'."\n";
		
		# status 5 - "Za&#269;el izpolnjevati",
		$value = $this->realUsersByStatus[5]['cnt'];
		echo '<tr>'."\n";
		echo '<th style="width: 10pt;">' .  $lang['srv_userstatus_5'] . '</th>'."\n";
		$width = ($maxValue && $value) ? (round($value / $maxValue * 100, 0)) : "0";
		echo '<td><div class="graph_db" style="text-align:right; float:left; width:'.$width.'%">&nbsp;</div><span style="display:block; margin:auto; margin-left:5px; width:20px; float:left">'.$value.'</span></td>'."\n";
		echo '</tr>'."\n";
		
		#strani
		echo '<tr class="anl_dash_bb">'."\n";
		echo '<th  style="width:10pt;">' . $lang[''] . '</th>'."\n";
		echo '<td style="text-align:center">'.$lang['	'].'</td>'."\n";
		echo '</tr>'."\n";
	
		foreach ($pages as $key => $page) {
			$value = $page['cnt'];
			echo '<tr>'."\n";
			//echo '<th style="width: 10pt;">' .  $page['naslov'] . '</th>'."\n";
			echo '<th style="width: 10pt;">' . $lang['srv_stran'].' '.$page['vrstni_red']. '</th>'."\n";
			$width = ($maxValue && $value) ? (round($value / $maxValue * 100, 0)) : "0";
			echo '<td><div class="graph_db" style="text-align:right; float:left; width:'.$width.'%">&nbsp;</div><span style="display:block; margin:auto; margin-left:5px; width:20px; float:left">'.$value.'</span></td>'."\n";
			echo '</tr>'."\n";
		}
		
		#strani
		echo '<tr class="anl_dash_bb">'."\n";
		echo '<th  style="width:10pt;">' . $lang[''] . '</th>'."\n";
		echo '<td style="text-align:center">'.$lang['	'].'</td>'."\n";
		echo '</tr>'."\n";
		
		# status 6 - "Koncal",
		$value6 = $this->realUsersByStatus[6]['cnt'];
		
		echo '<tr>'."\n";
		echo '<th style="width: 10pt;">' .  $lang['srv_userstatus_6'] . '</th>'."\n";
		$width = ($maxValue && $value6) ? (round($value6/ $maxValue * 100, 0)) : "0";
		echo '<td><div class="graph_db" style="text-align:right; float:left; width:'.$width.'%">&nbsp;</div><span style="display:block; margin:auto; margin-left:5px; width:20px; float:left">'.$value6.'</span></td>'."\n";
		echo '</tr>'."\n";
		
		#če imamo lurkerje 6l dodamo skupaj konačal anketo (to je 6 + 6l) in nato še koliko jih je samo s statusom 6 (končal anketo)
		# status 6l - "Koncal - lurker", izpišemo samo če obstajajo 6l
		$lurkerjev = $this->realUsersByStatus['6ll']['cnt'] - $value6;
		if ($lurkerjev > 0) {
			$valueall = $this->realUsersByStatus['6ll']['cnt'] ;
			
			# končal s tem da je lurker (6l)
			echo '<tr>'."\n";
			echo '<th style="width: 10pt;">' .  $lang['srv_userstatus_6l'].'' . '</th>'."\n";
			$width = ($maxValue && $lurkerjev) ? (round($lurkerjev / $maxValue * 100, 0)) : "0";
			echo '<td><div class="graph_db" style="text-align:right; float:left; width:'.$width.'%">&nbsp;</div><span style="display:block; margin:auto; margin-left:5px; width:20px; float:left">'.$lurkerjev.'</span></td>'."\n";
			echo '</tr>'."\n";
			
			#črta
			echo '<tr class="anl_dash_bb">'."\n";
			echo '<th  style="width:10pt;">' . $lang[''] . '</th>'."\n";
			echo '<td style="text-align:center">'.$lang['	'].'</td>'."\n";
			echo '</tr>'."\n";
			
			# končal ne glede na to ali je lurker	
			echo '<tr>'."\n";
			echo '<th style="width: 10pt;">' .  $lang['srv_userstatus_all'] . '</th>'."\n";
			$width = ($maxValue && $valueall) ? (round($valueall / $maxValue * 100, 0)) : "0";
			echo '<td><div class="graph_db" style="text-align:right; float:left; width:'.$width.'%">&nbsp;</div><span style="display:block; margin:auto; margin-left:5px; width:20px; float:left">'.$valueall.'</span></td>'."\n";
			echo '</tr>'."\n";
			
		}
		
 		echo '</table>'."\n";
	}

	/** Funkcija za prikaz seznam referalov
	 * 
	 */
	function DisplayReferalsList() {
	 	global $lang;
		
	 	if ($this->emailInvitation > 0) {
	 		if ($this->emailInvitation == 1) {
	 			$email_filter_string = ' AND inv_res_id is not NULL ';
	 		} else if($this->emailInvitation == 2) {
	 			$email_filter_string = ' AND inv_res_id is NULL ';
	 		}
	 	}
	 	
		echo '<br/><div class="dashboard_title">'.$lang['srv_statistic_referals_list'].Help :: display('srv_statistic_referals_list').'</div>';
		
		// še podatke o uporabniku
		$sql_userInfo = sisplet_query("SELECT referer, COUNT(*) as cnt FROM srv_user WHERE ank_id = '".$this->getSurveyId()."' AND preview = '0' AND deleted='0' AND time_insert BETWEEN '".$this->startDate."' AND '".$this->endDate."' + INTERVAL 1 ".$email_filter_string ." DAY GROUP BY referer");
		if (mysqli_num_rows($sql_userInfo) > 0) {
			
			echo '<table>';
			$cnt=0;		
			while ( $row_userInfo = mysqli_fetch_assoc($sql_userInfo) ) {
				if ($row_userInfo['referer'] != "") {
					$css_top = $cnt ? ' anl_dash_bt' : ''; 
					echo '<tr><td class="anl_dash_br anl_w15'.$css_top.'">'.$row_userInfo['cnt']. '</td><td class="'.$css_top.'">' .$row_userInfo['referer'].'</td></tr>';
					$cnt++;
				}		
			}
			echo '</table>';
		} else {
			echo $lang['srv_statistic_show_no_referals'];
		}
	}
	 
	/** Funkcija za prikaz seznam IP-jev
	 * 
	 */
	function DisplayIPList() {
	 	global $lang;

	 	if ($this->emailInvitation > 0) {
	 		if ($this->emailInvitation == 1) {
	 			$email_filter_string = ' AND inv_res_id is not NULL ';
	 		} else if($this->emailInvitation == 2) {
	 			$email_filter_string = ' AND inv_res_id is NULL ';
	 		}
	 	}
	 	 
		# IP-je lovimo preko ajaxa
		$string_sql = "SELECT COUNT(id) AS count, ip FROM srv_user WHERE ank_id='".$this->getSurveyId()."' AND preview = '0' AND deleted='0' AND (time_insert BETWEEN '".$this->startDate."' AND '".$this->endDate."' + INTERVAL 1 DAY) ".$email_filter_string." GROUP BY ip ORDER BY count DESC, ip ASC";
		$sql = sisplet_query($string_sql);
		
		echo '<br/><div class="dashboard_title">'.$lang['srv_statistic_IP_list'].Help :: display('srv_statistic_IP_list').'</div>';
		if (mysqli_num_rows($sql) > 0) {
			#echo '<div class="dashboard_title">'.$lang[''].'</div>';
			echo '<table>';
			
			while ($row = mysqli_fetch_array($sql)) {
				echo '<tr><td class="anl_dash_br">'.$row['ip'].'</td><td>'.$row['count'].'</td></tr>';
			}
			echo '</table>';
		}
	}
	
	/** Prikaze userje po posameznem statusu
	 * 
	 */
	 function DisplayUserByStatus() {
	 	global $lang;
	 	$status = $_POST['status'];

	 	if ($this->emailInvitation > 0) {
	 		if ($this->emailInvitation == 1) {
	 			$email_filter_string = ' AND inv_res_id is not NULL ';
	 		} else if($this->emailInvitation == 2) {
	 			$email_filter_string = ' AND inv_res_id is NULL ';
	 		}
	 	}
	 	
	 	echo '<div id="div_statistic_float_div">';
		echo '<span>'.$lang['srv_statistic_show_emails'].'</span><br/>';		
		// polovimo e-maile ce obstajajo
		$sqlUser = sisplet_query("SELECT d.text FROM srv_data_text".$this->db_table." d, srv_spremenljivka s , srv_grupa g WHERE d.spr_id=s.id AND d.usr_id IN ( SELECT id FROM srv_user WHERE ank_id = '".$this->getSurveyId()."' AND preview = '0' AND deleted='0' AND last_status = '".$status."' ) AND s.variable = 'email' AND g.ank_id='" . $this->getSurveyId(). "' AND s.gru_id=g.id ".$email_filter_string);
		$cnt = 0;

		while($rowUser = mysqli_fetch_assoc($sqlUser)) {
			if (isset($rowUser['text']) && $rowUser['text'] != "") {
				echo '<div class="list">'.$rowUser['text'].'</div>';
				$cnt++;
			}
		}
		if (!$cnt){
			echo $lang['srv_statistic_show_no_emails'];
		}	


		echo '<br/><span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="close_statistic_float_div(); return false;"><span>';
		echo $lang['srv_zapri'];
		echo '</span></a></div></span>';
		echo '<div class="clr"></div>';
	 	echo '</div>';
	 }

 	/** Funkcija prikaze filter
 	 * 
 	 */
	function DisplayFilters () {
		global $lang;

		// Kumulativa
		echo '<span class="floatRight" style="display:inline-block; vertical-align: middle;">';
		echo $lang['srv_statistic_hide_null'];
		echo '<input id="hideNullValues_dates" name="hideNullValues_dates" type="checkbox" onclick="statisticFilterDateRefresh();"'.($this->hideNullValues_dates ? ' checked="checked"' : '').' autocomplete="off">';
		echo '</span>';
		
		// Skrij 0
		echo '<span class="floatRight" style="display:inline-block; margin-right:10px; vertical-align: middle;">';
		echo '<label for="timelineDropDownType" autocomplete="off">'.$lang['srv_statistic_kumulativa'].': </label>';
		echo '<input type="checkbox" id="timelineDropDownType" name="timelineDropDownType" value="0" '.($this->timelineDropDownType == 1 ? ' checked="checked"' : '').' autocomplete="off" style="margin:0px!important;" onclick="statisticDropdownChange();" >';
		echo '</span>';

		echo '<br class="clr"/>';		

		// Osnova
		echo '<span id="span_timelineDropDownType" class="floatLeft">';
		$this->DisplayTimelineDropdowns();
		echo '</span>';
		
		// Oblika
		echo '<span class="floatRight">';
		echo '<label>'.$lang['srv_statistic_period'].'</label>:'."\n";
		echo '<select id="period" name="period" size="1" onchange="statisticFilterDateRefresh();" autocomplete="off" >'."\n";
		foreach ( $this->periods as $key => $_period) {
			echo '<option value="' . $_period . '" ' . ( $_period == $this->period ? ' selected="selected" ' : '') . '>'.$lang['srv_statistic_period_' . $_period ].'</option>'."\n";       
		}
		echo '</select>'."\n";
		echo '</span>';
	}

	function DisplayTimelineDropdowns() {
		global $lang;
		
		echo $lang['srv_statistic_answer_state_base'].': ';
		
		if ($this->timelineDropDownType == 0) {
			echo '<select name="type" id="type" onchange="statisticFilterDateRefresh();" autocomplete="off">'."\n";
			
			echo '<option value="'.TYPE_ALL.'"' . ($this -> type == TYPE_ALL ? ' selected' : '') . ' class="opt_bold">'.$lang['srv_userstatus_total'].'</option>';
			echo '<option value="'.TYPE_APPROPRIATE.'"' . ($this -> type == TYPE_APPROPRIATE ? ' selected' : '') . ' class="opt_bold">'.$lang['srv_userstatus_appropriate'].'</option>';
			echo '<option value="'.TYPE_STATUS_6.'"' . ($this -> type == TYPE_STATUS_6 ? ' selected' : '') . '>&nbsp;&nbsp;'.$lang['srv_userstatus_6'].' (6)</option>'."\n";
			echo '<option value="'.TYPE_STATUS_5.'"' . ($this -> type == TYPE_STATUS_5 ? ' selected' : '') . '>&nbsp;&nbsp;'.$lang['srv_userstatus_5'].' (5)</option>'."\n";
			echo '<option value="'.TYPE_INAPPROPRIATE.'"' . ($this -> type == TYPE_INAPPROPRIATE ? ' selected' : '') . 'class="opt_bold">'.$lang['srv_userstatus_inappropriate'].'</option>';		
			echo '<option value="'.TYPE_STATUS_6l.'"' . ($this -> type == TYPE_STATUS_6l ? ' selected' : '') . '>&nbsp;&nbsp;'.$lang['srv_userstatus_6l'].' (6l)</option>'."\n";			
			echo '<option value="'.TYPE_STATUS_5l.'"' . ($this -> type == TYPE_STATUS_5l ? ' selected' : '') . '>&nbsp;&nbsp;'.$lang['srv_userstatus_5l'].' (5l)</option>'."\n";			
			echo '<option value="'.TYPE_STATUS_4.'"' . ($this -> type == TYPE_STATUS_4 ? ' selected' : '') . '>&nbsp;&nbsp;'.$lang['srv_userstatus_4'].' (4)</option>'."\n";			
			echo '<option value="'.TYPE_STATUS_3.'"' . ($this -> type == TYPE_STATUS_3 ? ' selected' : '') . '>&nbsp;&nbsp;'.$lang['srv_userstatus_3'].' (3)</option>'."\n";			
			echo '<option value="'.TYPE_STATUS_2.'"' . ($this -> type == TYPE_STATUS_2 ? ' selected' : '') . '>&nbsp;&nbsp;'.$lang['srv_userstatus_2'].' (2)</option>'."\n";			
			echo '<option value="'.TYPE_STATUS_1.'"' . ($this -> type == TYPE_STATUS_1 ? ' selected' : '') . '>&nbsp;&nbsp;'.$lang['srv_userstatus_1'].' (1)</option>'."\n";			
			echo '<option value="'.TYPE_STATUS_0.'"' . ($this -> type == TYPE_STATUS_0 ? ' selected' : '') . '>&nbsp;&nbsp;'.$lang['srv_userstatus_0'].' (0)</option>'."\n";			
			echo '<option value="'.TYPE_STATUS_NULL.'"' . ($this -> type == TYPE_STATUS_NULL ? ' selected' : '') . '>&nbsp;&nbsp;'.$lang['srv_userstatus_null'].' (-1)</option>'."\n";			
			echo '<option value="'.TYPE_PAGES.'"' .    ($this -> type == TYPE_PAGES ? ' selected' : '') . ' class="opt_bold">'.$lang['srv_diagnostics_strani'].'</option>'."\n";
			echo '<option value="'.TYPE_ANALYSIS.'"' . ($this -> type == TYPE_ANALYSIS ? ' selected' : '') . ' class="opt_bold">'.$lang['srv_diagnostics_analiza'].'</option>'."\n";
			
			echo '</select>'."\n";
		} 
		else {
			$order = array(
				'3ll'=>TYPE_STATUS_KUMULATIVE_3ll,
				'4ll'=>TYPE_STATUS_KUMULATIVE_4ll,
				'5ll'=>TYPE_STATUS_KUMULATIVE_5ll,
				5=>TYPE_STATUS_KUMULATIVE_5,
				6=>TYPE_STATUS_KUMULATIVE_6,);
			echo '<select name="type" id="type" onchange="statisticFilterDateRefresh();" autocomplete="off">'."\n";
			foreach ($order as $key => $value) {
				echo '<option value="'.$value.'" '.($this -> type .'' == $value.'' ? ' selected="selected"' : '').' value="'.$value.'" >'.$lang['srv_userstatus_'.$key].'</option>';
			}
			echo '</select>';
		}
	}
	
	/** Funkcija prikaze statistike
	 * 
	 */
	function DisplayDateView() {
        global $lang;
        
		$this->maxValue *= GRAPH_REDUCE;
        $cnt=0;
        
        echo '<table class="survey_referals_tbl">'."\n";
        
		if ($this->arrayRange) {
            
            foreach ($this->arrayRange as $key => $value) {
				$label = $this->formatStatsString($key, $this->period);
				
				echo '<tr>'."\n";
				echo '<td style="width:90px;">' . $label . '</td>'."\n";
				$width = ($this->maxValue && $value) ? (round($value / $this->maxValue * 100, 0)) : "0";
				echo '<td style=""><div class="graph_db" style="text-align:right; float:left; width:'.$width.'%">&nbsp;</div><span style="display:block; margin:auto; margin-left:5px; width:20px; float:left">'.$value.'</span></td>'."\n";
				echo '</tr>'."\n";
                
                $cnt+=$value;
            }
            
			// dodamo sumo
			echo '<tr >'."\n";
			echo '<th class="anl_dash_bt strong" style="width:'.($this->maxCharRedirection+2).'pt;"><strong>' . $lang['srv_statistic_redirection_sum'] . '</strong></th>'."\n";
			echo '<td class="anl_dash_bt strong" style="text-align:left">'.$cnt.' </td>'."\n";
			echo '</tr>'."\n";
			
        } 
        else {
			echo '<td style="width:70%"><div style="background-color:#EFF2F7; border-left:1px solid #B9C5D9;">'.$lang['srv_no_data'].'</div></td>'."\n";			
        }
        
		echo '</table>'."\n";
	}
	 
	// pomozne funkcije
	/**
	 * @param $strDateFrom
	 * @param $strDateTo
	 * @param $format
	 * @param $add 
	 *			[D] - doda en dan (24 ur); 
	 *			[m] - doda en mesec (št dni * 24 ur); 
	 *			[h] - doda 1 uro;
	 * @return unknown_type
	 */
	function createDateRangeArray ($strDateFrom, $strDateTo, $format = DATE_FORMAT, $add = 'D') {
		// takes two dates formatted as YYYY-MM-DD and creates an
		// inclusive array of the dates between the from and to dates.
	
		// could test validity of dates here but I'm already doing
		// that in the main script
	
		$aryRange = array ();
		if ($add == 'H') { // urni interval za dnevno obdobje
			for ($i = 0; $i <=23; $i++){
				$aryRange[($i <= 9) ? '0'.$i : $i] = 0; // vrednosti nastavimo na 0
			}
			return $aryRange;
		}
		if ($add == 'h') { // urni interval za celotno obdobje
			$iDateFrom = strtotime($strDateFrom.' -1 hour');
			$iDateTo = strtotime($strDateTo.' + 23 hour');
			while ($iDateFrom < $iDateTo) {
				$iDateFrom += 3600; // add 1 hour
				$aryRange[date($format, $iDateFrom)] = 0; // vrednosti nastavimo na 0
			}			
			return $aryRange;
		}
		
		
		$iDateFrom = mktime(1, 0, 0, substr($strDateFrom, 5, 2), substr($strDateFrom, 8, 2), substr($strDateFrom, 0, 4));
		$iDateTo = mktime(1, 0, 0, substr($strDateTo, 5, 2), substr($strDateTo, 8, 2), substr($strDateTo, 0, 4));

		if ($iDateTo >= $iDateFrom) {
			//NAMESTO:    array_push($aryRange,date($format,$iDateFrom)); // first entry
			$aryRange[date($format, $iDateFrom)] = 0; // vrednosti nastavimo na 0
			while ($iDateFrom <= $iDateTo) {

				if ($add == 'm') {
					// ugotovimo koliko dni je v trenutnem mesecu
					$daysInMonth = date("d", strtotime('-1 second', strtotime('+1 month', strtotime(date("m", $iDateFrom) . '/01/' . date("Y", $iDateFrom) . ' 00:00:00'))));
					$iDateFrom += 86400 * ($daysInMonth); // add 24 hours * dayInMonth
				} else { // ($add == 'H')
					$iDateFrom += 86400; // add 24 hours
				}
				if ($iDateFrom <= $iDateTo)
					$aryRange[date($format, $iDateFrom)] = 0; // vrednosti nastavimo na 0
				//NAMESTO:  array_push($aryRange,date($format,$iDateFrom));
			}
		}
		return $aryRange;
	}
	
	function formatStatsString ($txt, $type) {
		global $months, $months_shortx, $weekdays, $week_short,$months;
	
		switch ($type) {
			case PERIOD_MONTH_PERIOD :
				$result = $months[round(substr($txt, 5, 7), 0)] . ", " . substr($txt, 0, 4);
				break;
			case PERIOD_WEEK_PERIOD :
				$result = substr($txt, 4, 7) . ", " . substr($txt, 0, 4);
				break;
			case PERIOD_MONTH_YEAR :
				$result = $months[$txt];
				break;
			case PERIOD_WEEK_YEAR :
				$result = $txt;
				break;
			case PERIOD_DAY_YEAR :
				$result = $txt;
				break;
			case PERIOD_DAY_MONTH :
				$result = $txt;
				break;
			case PERIOD_DAY_PERIOD :
				$result = $txt;
				break;
			case PERIOD_DAY_WEEK :
				$weekdays['0'] = $weekdays['7'];
				$nedelja = $weekdays['7'];
				ksort($weekdays);
				$weekdays = array_unique($weekdays);
				$result = $weekdays[round($txt, 0)];
				break;
			case PERIOD_HOUR_DAY :
				$result = $txt.':00 - '.($txt+1).':00';
				break;
			case PERIOD_HOUR_PERIOD :
						
				$result =  date('Y-m-d H:00',strtotime($txt.':00')).' - '.date('H:00',strtotime($txt.':00 + 1 hour'));
				break;
				
		}
		return $result;
	}
	function dateFormat($input, $format) {
		if ($input != '..') {		
			return date($format,strtotime($input));
		} else {
			return '';
		}
	}
	
	/** Lepo oblikuje number string
	 * 
	 * @param float $value
	 * @param int $digit
	 * @param string $sufix
	 * @return string
	 */
	function formatNumber ($value, $digit = 0, $sufix = "") {
		if ($value <> 0 && $value != null)
			$result = round($value, $digit);
		else
			$result = "0";
		$result = number_format($result, $digit, '.', ',') . $sufix;
	
		return $result;
	}
	
	/** 
	 * @desc Odreze niz na zadnjem presledkom pred omejitvijo (limit)
	 * 
	 * @param $input
	 * @param $limit
	 * 
	 * @return $string
	 */
	function limitString($input, $limit = 100) {
	    // Return early if the string is already shorter than the limit
	    if(strlen($input) < $limit) {return $input;}
	
	    $regex = "/(.{1,$limit})\b/";
	    preg_match($regex, $input, $matches);
	    return $matches[1].'...';
	}

	/**
	 * perveri ali imamo zakeširan fajl (HTML) če obstaja, ga uporabimo za prikaz dashboarda.
	 */
	function CheckDashbordChacheFile() {
		global $site_path;
		# nastavimo folder
		$folder = $site_path . EXPORT_FOLDER.'/';
		
		# preverimo timestampe
		$str_qry_surveys = "SELECT id, UNIX_TIMESTAMP(GREATEST(insert_time,edit_time)) as time FROM srv_anketa WHERE id ='".$this->surveyId."'";
		$qry_surveys = sisplet_query($str_qry_surveys);
		list($id,$time_survey) = mysqli_fetch_row($qry_surveys);
		
		$str_qry_users = "SELECT ank_id, UNIX_TIMESTAMP(GREATEST(max(time_insert), max(time_edit))) as time FROM srv_user WHERE ank_id > 0 AND ank_id ='".$this->surveyId."' AND preview = '0' AND deleted='0' GROUP BY ank_id";
		$qry_users = sisplet_query($str_qry_users);
		list($id,$time_user) = mysqli_fetch_row($qry_users);
		
		$dashboard_timestamp = (int)$time_survey + (int)$time_user;
		
		$fileToUse = $folder . 'export_dashboard_'.$this->surveyId.'.html';
		# če cache fajla še nimamo kreiramo novega
		if (!file_exists($fileToUse)) {
			# cache fajl ne obstaja
			return null;
		} else {
			# cache fajl obstaja
			#preverimo datum dashboard fajla
			$_sql_string = "SELECT dashboard_file_time FROM srv_data_files WHERE sid = '".$this->surveyId."'";
			$_sql_qry = sisplet_query($_sql_string);
			list($dashboard_file_time) = mysqli_fetch_row($_sql_qry);
			
			# preverimo ali je file up to date
			if ($dashboard_timestamp == $dashboard_file_time) {
				#dashboard file je up to date
				return $fileToUse;
			} else {
				# dashboard fajl ni up to date
				
				#preštejemo vse userje da lahko za vlke ankete kreiramo cache samo na 15 minut.
				$str_count = "SELECT count(id) FROM srv_user where ank_id ='".$this->surveyId."' AND deleted='0'"; // zakaj tukaj ni preview???
				$qry_count = sisplet_query($str_count);
				list($user_count) = mysqli_fetch_row($qry_count);
				
				# če imamo dosti uporabnikov refreshamo cache fajl na 15 minut.
				if ($user_count > REFRESH_USER_COUNT ) {
					# preverimo ali je potrebno dashboard fajl posodobit, ali uporabimo starega
					# cache fajl ni up to date, preverimo ali moramo kreirati novega ali lahko uporabimo starega (update time < 15 min)
					# poiščemo zadnji cache file za to anketo
					if (file_exists($fileToUse)) {
						$ctime = filectime($lastFiles);
						$diff = time() - $ctime;
						if ($diff > (REFRESH_USER_TIME * 60)) {
							# pobrišemo star fajl
							unlink($fileToUse);
							# updejt je bil pred več kot 15 min. delamo nov fajl
							return null;
						} else {
							return $fileToUse;
						}
					}
				}
			}
		}
		
		unlink($fileToUse);
		return null;
	}

	/** Zapišemo html ($data) v html fajl za keširanje
	 * 
	 * @param HTML $data
	 */
	function WriteToCacheFile($data,$timestamp) {
		global $site_path;
		if ($this->surveyId !== null && $data !== null && $data !== '') {
			# če imamo default filtre zapišemo v datoteko; Prav tako mora bit izbran osnovni profil intervala z id = 0
			if ( $this->isDefaultFilters  == true) {
				# nastavimo folder
				$folder = $site_path . EXPORT_FOLDER.'/';
				$fileToUse = $folder . 'export_dashboard_'.$this->surveyId.'.html';
				# pobrišemo morebitne predhodne header datoteke ankete
				if (file_exists($fileToUse)) {
					unlink($fileToUse);
				}
				
				$df = fopen($fileToUse, 'w') or die("can't open file");
				fwrite($df, $data);
				fclose($df);
				
				$str_qry_exist_files_update = "INSERT INTO srv_data_files (sid, dashboard_file_time, dashboard_update_time) VALUES ('".$this->surveyId."','".$timestamp."',NOW()) ON DUPLICATE KEY UPDATE dashboard_file_time = '".$timestamp."', dashboard_update_time = NOW()";
				$updated = sisplet_query($str_qry_exist_files_update);
			}
		}
	}
	
	/** prepebremo zakeširan html fajl 
	 * 
	 * @param unknown_type $data
	 */
	function ReadCacheFile() {
		global $site_path;
		if ($this->surveyId !== null) { 
			# nastavimo folder
			$folder = $site_path . EXPORT_FOLDER.'/';
			$dashboardFile = null;
			# prebermo fajl:
			foreach (glob($folder . 'export_dashboard_'.$this->surveyId.'.html') as $filesToUse) {
				if ( $dashboardFile == null) {
					$dashboardFile  = $filesToUse;
				}
			}

			$df = fopen($dashboardFile, 'r') or die("can't open file");
			
			$data = fread($df, filesize($dashboardFile));

			fclose($df);
			return $data; 
		}
		return null;
	}
	
	
	/** Funkcija klice funkcije za prikaz statistike 
	 * 	DisplayInfoView - prikaze panelo z osnovnimi informacijami
	 *  DisplayStatusView - prikaze panelo z kliki po statusih 
	 *  DisplayReferalsView - prikaze panelo z redirekcijami in referali
	 *  DisplayDataView - prikate panelo z kliki po datumih
	 */
	function WriteDashboardToFile($sid,$timestamp)  {
		
		global $lang, $site_url;
		if ((int)$sid > 0) {
			
			# spodnje ehote shranimo v spremenljivko ki jo popotrebi keširanja shranimo v datoteko.
	
			// preverimo ali ima anketa kakšne vnose
			$str_qry_all_users = "SELECT count(u.id) AS user_count FROM srv_user AS u " . "WHERE u.ank_id = '".$this->getSurveyId()."' AND preview = '0' AND deleted='0' "; 
			$qry_all_users = sisplet_query($str_qry_all_users);
			$row_all_users = mysqli_fetch_assoc($qry_all_users);
			$allUserCount = $row_all_users['user_count']; 
			
			ob_start();	
	
			#echo  '<div class="floatLeft" id="updateTime">'.$lang['srv_dashboard_updated'].date("d.m.Y, H:i").'</div>';
			
			if ($allUserCount == 0) { // nimamo še vnosov
					
					// zgornji boxi
					echo '<table class="dashboard dashboard_single">';
					echo '<tr>';
					echo '<td>';
					echo '<div class="dashboard_cell" name="div_statistic_info" id="div_statistic_info" >'."\n";
					$this -> DisplayInfoView();
					echo '</div>';
					echo '</td>';
					echo '</tr>';
					echo '</table>';
					
					// 1) če ankete še nima nobenega klika, naj se pri poročilu ne prikaže nobena od šestih analiza, 
					//    ampak naj pise: Anketa nima še nobenega vnosa«  zreaven naj bo  v SIVEM zavihka UREJANJE
					// 2) Če nima anketa nobenega klika in  ni niti aktivirana, pa napisite: 
					//    Anketa še ni aktivvirajna. Zraven naj bo sta siva zavihkja Urejanje vprašalnika in Objave&vabila
	
					
				} else {
					
					// imamo vnose, prikažemo statistiko
					$this->PrepareDateView();
					$this->PrepareStatusView();
	
					echo '<table class="dashboard">';
					echo '<tr>';
					
					// zgornji boxi
					echo '<td>';
					echo '<div class="dashboard_cell" name="div_statistic_info" id="div_statistic_info" >'."\n";
					$this -> DisplayInfoView();
					echo '</div>';
					echo '</td>';
						
					echo '<td>';
					echo '<div class="dashboard_cell" name="div_statistic_status" id="div_statistic_status" >'."\n";
					$this -> DisplayStatusView();
					echo '</div>';
					echo '</td>';
					
					echo '<td>';
					echo '<div class="dashboard_cell" name="div_statistic_answer_state" id="div_statistic_answer_state" >'."\n";
					$this -> DisplayAnswerStateView();
					echo '</div>';
					echo '</td>';
					echo '</tr>';
				
					// spodnji boxi
					echo '<tr>';
					echo '<td>';
					echo '<div class="dashboard_cell"  id="div_statistic_referals">';
					$this -> DisplayReferalsView();
					echo '</div>';
					echo '</td>';
					
					echo '<td>';
					echo '<div class="dashboard_cell" id="div_statistic_visit">';
					echo '<span class="dashboard_title">'.$lang['srv_statistic_timeline_title'].'</span>'.Help :: display('srv_statistic_timeline_title'); 
					echo '<form name="frm_statistic_filter" id="frm_statistic_filter" autocomplete="off">'."\n";
	//				echo '<div name="div_statistic_filter" id="div_statistic_filter" >'."\n";
					$this -> DisplayFilters();
	//				echo '</div>';
					echo '</form>';
					echo '<br/>';
					
					echo '<div name="div_statistic_visit_data" id="div_statistic_visit_data" >'."\n";
					$this -> DisplayDateView();
					echo '</div>';
					echo '</div>';
					echo '</td>';
					
					echo '<td>';
					echo '<div class="dashboard_cell" id="div_statistic_pages_state">';
					$this -> DisplayPagesStateView();
					echo '</div>';
					echo '</td>';
	
					echo '</tr>';
					echo '</table>';
				}
	
			# HTML zapišemo v spremenljivko
			$dashboardHtml = ob_get_clean();
			$this->WriteToCacheFile($dashboardHtml,$timestamp);
		}
	}
	 
	function changeInvitationFilter() {
		$this->emailInvitationFilter($_POST['filter_email_status']);
	}
	
	function emailInvitationFilter($dashboardInvitationType) {
		global $lang;
		
		if ($dashboardInvitationType == 1) {
			echo '<div id="dashboardEmailFilter">';
			echo $lang[''].'Podatki so filtrirani: ';
			echo $lang['srv_statistic_email_invitation_only_email'];
			echo '&nbsp;&nbsp;<span class="as_link space_left" onclick="$(\'#filter_email_status\').val(\'0\').trigger(\'change\');">Odstrani</span>';
			echo '</div>';
			
		}
		if ($dashboardInvitationType == 2) {
			echo '<div id="dashboardEmailFilter">';
			echo $lang[''].'Podatki so filtrirani: ';
			echo $lang['srv_statistic_email_invitation_no_email'];
			echo '&nbsp;&nbsp;<span class="as_link space_left" onclick="$(\'#filter_email_status\').val(\'0\').trigger(\'change\');">Odstrani</span>';
			echo '</div>';
		}
	}
	
	function DisplayAaporCalculations() {
        global $lang;

		$this->PrepareDateView();
		$this->PrepareStatusView();
		
		$sa = new SurveyAapor($this->cntUserByStatus,$this->userByStatus,$this->surveyId);
	}
	
	function DisplayAaporPriblizek(){
		$this->PrepareDateView();
		$this->PrepareStatusView();
		
		$sa = new SurveyAapor($this->cntUserByStatus,$this->userByStatus,$this->surveyId);
		$sa->prikaziPriblizek();
	}
	
	function DisplayAaporFullCalculation(){
		$sa = new SurveyAapor($this->cntUserByStatus,$this->userByStatus,$this->surveyId);
		$sa->calculationForFullAapor();
	}
	
	function DisplayLangStatistic() {
		global $lang;
		
		$this->PrepareDateView();
		$this->PrepareStatusView();

		
		echo '<table class="dashboard dashboard_single">';
		echo '<tr>';
		
		// zgornji boxi
		echo '<td>';
		echo '<div class="dashboard_cell" name="div_statistic_info" id="div_statistic_info" >'."\n";
		
		{
			$lang_array = array();
			$lang_array[$lang['id']] = $lang['language'];
		
			$sqll = sisplet_query("SELECT lang_id, language FROM srv_language WHERE ank_id='$this->surveyId' ORDER BY language");
			while ($rowl = mysqli_fetch_array($sqll)) {
				$lang_array[$rowl['lang_id']] = $rowl['language'];
			}
		
			echo '<span class="dashboard_title">'.$lang['srv_statistic_lang_title'].'</span>';
			echo '<br/>';
			echo '<table id="tbl_answ_state">';
			echo '<tr class="anl_dash_bb "><th><strong>'.$lang['srv_statistic_lang'].'</strong></th><td><strong>'.$lang['srv_statistic_answer_state_frequency'].'</strong></td><td><strong>'.$lang['srv_statistic_answer_state_percent'].'</strong></td></tr>';
			foreach ($this->respondentLangArray as $key => $cnt) {
				$allCnt+=$cnt;
				echo '<tr><th>'.$lang_array[$key].'</th>';
				#frekvenca
				echo '<td>'.(int)$cnt.'</td>';
				#procenti
				echo '<td>';
				$percent = ($this->realUsersByStatus['3ll']['cnt'] > 0)
				? $cnt / $this->realUsersByStatus['3ll']['cnt'] * 100
				: 0;
				echo $this->formatNumber((int)$percent,NUM_DIGIT_PERCENT,'%');
				echo '</td>';
				echo '</tr>';
			}
			echo '<tr class="anl_dash_bt "><th><strong>'.$lang['srv_statistic_sum'].'</strong></th>';
			#frekvenca
			echo '<td><strong>'.(int)$allCnt.'</strong></td>';
			#procenti
			echo '<td>';
			$percent = 100;
			echo $this->formatNumber((int)$percent,NUM_DIGIT_PERCENT,'%');
			echo '</td>';
			echo '</tr>';
			echo '</table>';
		}
		
		echo '</div>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		
	}
}
?>