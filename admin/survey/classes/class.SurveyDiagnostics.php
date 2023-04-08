<?php
/** Class ki skrbi za diagnostiko ankete
 *  September 2011
 * 
 * 
 * Enter description here ...
 * @author Gorazd_Veselic
 *
 */
if(session_id() == '') {session_start();}

define("SPR_ON_PAGE_LIMIT", 8);							# priporočeno število spremenljivk na stran
define("SPR_IN_BLOCK_LIMIT", 15);							# priporočeno število spremenljivk na blok
define("SUB_Q_IN_GRID_LIMIT", 8);							# priporočeno število podvprašanj na grid
define("SPR_UNAPROPRIATE_START_LIMIT", 30);				# koliko % spremenljivk preverjamo na pravilen začetek 
define("SPR_REMINDER_ON_MGRID_LIMIT", 10);				# koliko % spremenljivk preverjamo na pravilen začetek 
define("TIME_SOFT_LIMIT", 120);							# čas izpolnjevanja 2 minuti priporočilo 
define("TIME_HARD_LIMIT", 900);							# čas izpolnjevanja 15 minut opozorilo 

 
	
define("DIAG_SPR_ON_PAGE", "DIAG_SPR_ON_PAGE");				# Zaznali smo preveč spremenljivk na stran
define("DIAG_SPR_IN_BLOCK", "DIAG_SPR_IN_BLOCK");				# Zaznali smo preveč spremenljivk na blok
define("DIAG_SUB_Q_IN_GRID", "DIAG_SUB_Q_IN_GRID");			# Zaznali smo preveč podvprašanj v gridu
define("DIAG_REMINDER_ON_IF", "DIAG_REMINDER_ON_IF");			# Manjka reminder na spremenljvko na katero se sklicuje if
define("DIAG_REMINDER_ON_MGRID", "DIAG_REMINDER_ON_MGRID");	# Reminder na spremenljvko z veliko podvprašanji
define("DIAG_UNAPROPRIATE_START", "DIAG_UNAPROPRIATE_START");	# ali imamo na začetku ankete neprimerna vprašanja
define("DIAG_INVALID_CONDITIONS", "DIAG_INVALID_CONDITIONS");	# Ali so napake v ifih
define("DIAG_INVALID_VARIABLENAMES", "DIAG_INVALID_VARIABLENAMES");	# Ali so varable podvojene
define("DIAG_INVALID_VALIDATIONS", "DIAG_INVALID_VALIDATIONS");	# Ali so napacne validacije na spremenljivkah
define("DIAG_TIME_SOFT_LIMIT", "DIAG_TIME_SOFT_LIMIT");		# predolga anketa 1
define("DIAG_TIME_HARD_LIMIT", "DIAG_TIME_HARD_LIMIT");		# predolga anketa 2

class SurveyDiagnostics
{
	private $sid;
	private $surveyInfo;

	private $struktura = array();				# drevesna struktura vprašalnika
	private $spremenljivke = array();			# array z spremenljivkami
	
	private $struktura_spremenljivk = array();	# spremenljivke po vrsti
	private $pages = array();					# vprašanja po straneh
	private $blocks = array(); 					# vprašanja po blokih
	private $conditions = array(); 				# ifi - pogoji
	
	private $cnt_spremenljivka = 0;				# število spremenljivk
	private $cnt_hidden = 0;					# število skritih spremenljivk
	private $cnt_pages = 1;						# število strani
	private $cnt_blocks = 0;					# število blokov
	private $cnt_conditions = 0;				# število ifov

	private $time = array();					# predviden čas array('sekund', 'formatiran => min:sekund')
	private $comments = array();				# komentarji array('vsi', 'nerazrešeni')
		
	private $diagnostic_note = array ();
	
	
	function __construct($sid) {
		$this->sid = $sid;
		
		SurveyInfo::SurveyInit($this->sid);
		$this->surveyInfo = SurveyInfo::getSurveyRow(); 
		
		# polovimo vsa vprašanja
		$this->init();
	}
	
	function init() {
		
		# naenkrat preberemo vse spremenljivke, da ne delamo queryja vsakic posebej
		$this->spremenljivke = Cache::cache_all_srv_spremenljivka($this->sid, true);
		# enako za srv_branching
		Cache::cache_all_srv_branching($this->sid, true);
		# cachiramo tudi srv_if
		Cache::cache_all_srv_if($this->sid);

		$this->createStructure();
		$this->countHidden();
	}
	
	function getDiagnostic() {
		return $this->diagnostic_note;
	}
	
	function ajax() {
		if (isset($_REQUEST['a']) && trim($_REQUEST['a']) != '') {
			$this->action($_REQUEST['a']);
		} else {
			echo 'Ajax error!';
			return 'Ajax error!';
		}
	}
	
	function action($action) {
		switch ($action) {
			default:
				$this->showDiagnostics();
			break;
		}
	}
	
	function doDiagnostics() {
		$this->testUnapropriateStart();
		$this->testHardRremindersAndSubQ();
		$this->testTooManyQuestions();
		$this->testTime();
		$this->testComments();
		$this->testConditions();
		
	}
	
	function displayDiagnostic() {
		global $lang;
		global $site_url;
		
		echo '<div id="srv_diagnostic">';
		
		# damo obvestilo o diagnostiki
		#print_r($this->diagnostic_note);
		
		# opozorila:
		# DIAG_SPR_ON_PAGE
		# DIAG_SPR_IN_BLOCK
		# DIAG_SUB_Q_IN_GRID
		# DIAG_UNAPROPRIATE_START
		$_opozorila = false;
		if (in_array(DIAG_SPR_ON_PAGE, $this->diagnostic_note) 
			|| in_array(DIAG_SPR_IN_BLOCK, $this->diagnostic_note)
			|| in_array(DIAG_SUB_Q_IN_GRID, $this->diagnostic_note)
			|| in_array(DIAG_UNAPROPRIATE_START, $this->diagnostic_note)
			|| in_array(DIAG_TIME_HARD_LIMIT, $this->diagnostic_note)) {
				$_opozorila = true;	
			}
		
		# priporočila:
		# DIAG_REMINDER_ON_IF
		# DIAG_REMINDER_ON_MGRID
		$_pripombe = false;
		if (in_array(DIAG_REMINDER_ON_IF, $this->diagnostic_note) 
			|| in_array(DIAG_REMINDER_ON_MGRID, $this->diagnostic_note)
			|| in_array(DIAG_TIME_SOFT_LIMIT, $this->diagnostic_note)) {
				$_pripombe = true;	
			}
		
			
		echo '<span class="srv_diagnostic_note">';
		echo $this->printNote();
		#
		echo '</span>';
		
		
		SurveySetting::getInstance()->Init($this->anketa);
		$preview_disableif = SurveySetting::getInstance()->getSurveyMiscSetting('preview_disableif');
		$preview_disablealert = SurveySetting::getInstance()->getSurveyMiscSetting('preview_disablealert');
		$preview_displayifs = SurveySetting::getInstance()->getSurveyMiscSetting('preview_displayifs');
		$preview_displayvariables = SurveySetting::getInstance()->getSurveyMiscSetting('preview_displayvariables');
		$preview_hidecomment = SurveySetting::getInstance()->getSurveyMiscSetting('preview_hidecomment');
		$preview_options = ''.($preview_disableif==1?'&disableif=1':'').($preview_disablealert==1?'&disablealert=1':'').($preview_displayifs==1?'&displayifs=1':'').($preview_displayvariables==1?'&displayvariables=1':'').($preview_hidecomment==1?'&hidecomment=1':'').'';
		
		echo '<br><div style="display:inline-block; margin: 0 0 10px 20px">';
		
		echo '<span class="tooltip">';
		echo '<a href="'.SurveyInfo::getSurveyLink().'&preview=on&testdata=on'.$preview_options.'" target="_blank" style="font-size:15px"><span class="faicon edit_square"></span> '.$lang['srv_survey_testdata'].'</a>';
		echo ' ('.SurveyInfo::getSurveyLink().'&preview=on&testdata=on'.$preview_options.') ';
		echo '<span class="expanded-tooltip bottom light" style="left: -40px;">';
		echo '<b>' . $lang['srv_survey_testdata2'] . ':</b> '.$lang['srv_testdata_text'].'';
		echo '<p>'.$lang['srv_preview_testdata_longtext'].'</p>';
		echo '<span class="arrow"></span>';
		echo '</span>';	// expanded-tooltip bottom
		echo '</span>'; // tooltip
			
		echo ' - <a href="#" id="popup-open" onclick="javascript:testiranje_preview_settings(); return false;" title="'.$lang['settings'].'"><span class="sprites settings"></span> '.$lang['srv_uredniske_nastavitve'].'</a>';
		echo '</div>';
		
		if ( isset($_GET['popup']) && $_GET['popup'] == 'open' ) {
			?><script> $(function() { $('#popup-open').click(); }); </script><?php
		}
		
		echo '<br/>';
		# predviden čas ankete
		list($total,$skupni_cas) = $this->time;
        
        if($lang['id'] == '1')
		    $link = '<a href="https://www.1ka.si/d/sl/spletne-ankete/osnovna-priporocila?from1ka=1" target="_blank">%s</a>';
        else
            $link = '<a href="https://www.1ka.si/d/en/web-surveys/basic-recommendations?from1ka=1" target="_blank">%s</a>';
		
		list($commentsAll,$commentsUnresolved,$commentsQuestionAll,$commentsQuestionUnresolved,$commentsUser,$commentsUserFinished,$commentsUserSurveyAll,$commentsUserSurveyUnresolved) = $this->comments;

		if ($total < 120) { # 2min
			$time = $lang['srv_diagnostic_time_1'];
		} else if ($total < 300) { # 5min
			$time = $lang['srv_diagnostic_time_2'];
		} else if ($total < 900) { # 15 min
			$time = $lang['srv_diagnostic_time_3'];
		} else if ($total < 1800) { # 30 min
			$time = $lang['srv_diagnostic_time_4'];
		}	else { #> 30 min
			$time = $lang['srv_diagnostic_time_5'];
		}
		
		# Kompleksnost
		if ((int)($this->cnt_blocks + $this->cnt_conditions) == 0) {
			$kompleksnost = $lang['srv_diagnostic_complexity_1'];
		} else if ((int)($this->cnt_blocks + $this->cnt_conditions) == 1) {
			$kompleksnost = $lang['srv_diagnostic_complexity_2'];
		} else if ((int)($this->cnt_blocks + $this->cnt_conditions) < 10) {
			$kompleksnost = $lang['srv_diagnostic_complexity_3'];
		} else if ((int)($this->cnt_blocks + $this->cnt_conditions) < 50) {
			$kompleksnost = $lang['srv_diagnostic_complexity_4'];
		} else {
			$kompleksnost = $lang['srv_diagnostic_complexity_5'];
		}
		 
		#OPOZORILA
		echo '<div  class="floatLeft">';
		echo '<div id="srv_diagnostic_results" >';
		echo '<table class="srv_diagnostic_results">';
		echo '<tr>';
		echo '<th>'.$lang['srv_diagnostika_table_title1'].'</th>';
		echo '<th>'.$lang['srv_diagnostika_table_title'].'</th>';
		echo '</tr>';
		#Preveč ali premalo opomnikov (reminder). 
		
		#Napake - v IFih, Zankah
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_5_element_1'].'</td>';
		echo '<td>';

		if (in_array(DIAG_INVALID_CONDITIONS,$this->diagnostic_note)) {
            echo '<span class="red">';
            if($lang['id'] == '1')
			    echo '<a href="https://www.1ka.si/d/sl/pomoc/pogosta-vprasanja/kako-odkriti-logicne-tehnicne-napake-vprasalniku?from1ka=1" target="_blank">'.$lang['srv_diagnostic_neustreza1'].'</a>';
            else
                echo '<a href="https://www.1ka.si/d/en/help/faq/how-do-i-detect-logical-and-technical-errors-the-questionnaire?from1ka=1" target="_blank">'.$lang['srv_diagnostic_neustreza1'].'</a>';
            echo '</span>';
        }	
        else {
            echo '<span class="green">';
            if($lang['id'] == '1')
			    echo '<a href="https://www.1ka.si/d/sl/pomoc/pogosta-vprasanja/kako-odkriti-logicne-tehnicne-napake-vprasalniku?from1ka=1" target="_blank">'.$lang['srv_diagnostic_ustreza'].'</a>';
            else
                echo '<a href="https://www.1ka.si/d/en/help/faq/how-do-i-detect-logical-and-technical-errors-the-questionnaire?from1ka=1" target="_blank">'.$lang['srv_diagnostic_ustreza'].'</a>';
			echo '</span>';
		}
		echo '</td>';
		echo '</tr>';
		
		#Napake - v validacijah
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_5_element_3'].'</td>';
		echo '<td>';

		if (in_array(DIAG_INVALID_VALIDATIONS, $this->diagnostic_note)) {
            echo '<span class="red">';
            if($lang['id'] == '1')
			    echo '<a href="https://www.1ka.si/d/sl/pomoc/pogosta-vprasanja/kako-odkriti-logicne-tehnicne-napake-vprasalniku?from1ka=1" target="_blank">'.$lang['srv_diagnostic_neustreza1'].'</a>';
            else
                echo '<a href="https://www.1ka.si/d/en/help/faq/how-do-i-detect-logical-and-technical-errors-the-questionnaire?from1ka=1" target="_blank">'.$lang['srv_diagnostic_neustreza1'].'</a>';
			echo '</span>';
        }	
        else {
            echo '<span class="green">';
            if($lang['id'] == '1')
			    echo '<a href="https://www.1ka.si/d/sl/pomoc/pogosta-vprasanja/kako-odkriti-logicne-tehnicne-napake-vprasalniku?from1ka=1" target="_blank">'.$lang['srv_diagnostic_ustreza'].'</a>';
            else
                echo '<a href="https://www.1ka.si/d/en/help/faq/how-do-i-detect-logical-and-technical-errors-the-questionnaire?from1ka=1" target="_blank">'.$lang['srv_diagnostic_ustreza'].'</a>';
			echo '</span>';
		}
		echo '</td>';
		echo '</tr>';
		
		#napake - Podovojenost imen varianel
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_5_element_2'].'</td>';
		echo '<td>';
		if (in_array(DIAG_INVALID_VARIABLENAMES,$this->diagnostic_note)) {
            echo '<span class="red">';
            if($lang['id'] == '1')
			    echo '<a href="https://www.1ka.si/d/sl/pomoc/pogosta-vprasanja/kako-odkriti-logicne-tehnicne-napake-vprasalniku?from1ka=1" target="_blank">'.$lang['srv_diagnostic_neustreza1'].'</a>';
            else
                echo '<a href="https://www.1ka.si/d/en/help/faq/how-do-i-detect-logical-and-technical-errors-the-questionnaire?from1ka=1" target="_blank">'.$lang['srv_diagnostic_neustreza1'].'</a>';
			echo '</span>';
			echo '<span>&nbsp;';
			echo '<a href="'.$site_url . 'admin/survey/index.php?anketa='.$this->sid.'&checkDuplicate=1" title="'.$lang['srv_check_pogoji'].'"><span class="faicon bug"></span></a> '."\n";
			echo '</span>';
        }	
        else {
            echo '<span class="green">';
            if($lang['id'] == '1')
			    echo '<a href="https://www.1ka.si/d/sl/pomoc/pogosta-vprasanja/kako-odkriti-logicne-tehnicne-napake-vprasalniku?from1ka=1" target="_blank">'.$lang['srv_diagnostic_ustreza'].'</a>';
            else
                echo '<a href="https://www.1ka.si/d/en/help/faq/how-do-i-detect-logical-and-technical-errors-the-questionnaire?from1ka=1" target="_blank">'.$lang['srv_diagnostic_ustreza'].'</a>';
			echo '</span>';
		}
		echo '</td>';
		echo '</tr>';
		
		echo '</table>';
		echo '</div>';
		
		echo '<br/><br/>';

		echo '<div id="srv_diagnostic_results" >';
		echo '<table class="srv_diagnostic_results">';
#		echo '<COLGROUP><COL width="50%"><COL><COL><COLGROUP>';
		echo '<tr>';
		echo '<th>'.$lang['srv_diagnostika_table_title2'].'</th>';
		echo '<th>'.$lang['srv_diagnostika_table_title'].'</th>';
		echo '</tr>';
	
		# Preveč podvprašanj na eni strani.
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_1_element_1'].'</td>';
        echo '<td>';
        
        if($lang['id'] == '1')
		    $link = '<a href="https://www.1ka.si/d/sl/spletne-ankete/osnovna-priporocila/prevec-podvprasanj-bloku-prevec-vprasanj-na-eni-strani?from1ka=1" target="_blank">%s</a>';
        else    
            $link = '<a href="https://www.1ka.si/d/en/web-surveys/basic-recommendations/too-many-subquestions-block-and-too-many-questions-on-one-page?from1ka=1" target="_blank">%s</a>';
        
        if (in_array(DIAG_SPR_ON_PAGE,$this->diagnostic_note)) {
			echo '<span class="red">';
			printf($link,$lang['srv_diagnostic_neustreza2']);
			echo '</span>';
        }	
        else {
			echo '<span class="green">';
			printf($link,$lang['srv_diagnostic_ustreza']);
			echo '</span>';
		}
		echo '</td>';
		echo '</tr>';
		
		# Preveč vprašanj v bloku.
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_1_element_2'].'</td>';
        echo '<td>';
        
        if($lang['id'] == '1')
		    $link = '<a href="https://www.1ka.si/d/sl/spletne-ankete/osnovna-priporocila/premajhna-strukturiranost-vprasalnika?from1ka=1" target="_blank">%s</a>';
        else
            $link = '<a href="https://www.1ka.si/d/en/web-surveys/basic-recommendations/insufficiently-structured-questionnaire?from1ka=1" target="_blank">%s</a>';
        
        if (in_array(DIAG_SPR_IN_BLOCK,$this->diagnostic_note)) {
			echo '<span class="red">';
			printf($link,$lang['srv_diagnostic_neustreza2']);
			echo '</span>';
		}	else {
			echo '<span class="green">';
			printf($link,$lang['srv_diagnostic_ustreza']);
			echo '</span>';
		}
		echo '</td>';
		echo '</tr>';

		# Preveč podpvprašanj v multigridu
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_1_element_3'].'</td>';
        echo '<td>';

        if($lang['id'] == '1')
            $link = '<a href="https://www.1ka.si/d/sl/spletne-ankete/osnovna-priporocila/prevec-podvprasanj-bloku-prevec-vprasanj-na-eni-strani?from1ka=1" target="_blank">%s</a>';
        else
            $link = '<a href="https://www.1ka.si/d/en/web-surveys/basic-recommendations/too-many-subquestions-block-and-too-many-questions-on-one-page?from1ka=1" target="_blank">%s</a>';

		if (in_array(DIAG_SUB_Q_IN_GRID,$this->diagnostic_note)) {
			echo '<span class="red">';
			printf($link,$lang['srv_diagnostic_neustreza2']);
			echo '</span>';
		}	else {
			echo '<span class="green">';
			printf($link,$lang['srv_diagnostic_ustreza']);
			echo '</span>';
		}
		echo '</td>';
		echo '</tr>';
		
		#Začetek ankete z neprimernimi vprašanji.
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_2_element_1'].'</td>';
        echo '<td>';
        
        if($lang['id'] == '1')
		    $link = '<a href="https://www.1ka.si/d/sl/spletne-ankete/osnovna-priporocila/zacetek-ankete-z-neprimernimi-vprasanji?from1ka=1" target="_blank">%s</a>';
        else
            $link = '<a href="https://www.1ka.si/d/en/web-surveys/basic-recommendations/beginning-the-survey-with-inappropriate-questions?from1ka=1" target="_blank">%s</a>';
        
        if (in_array(DIAG_UNAPROPRIATE_START,$this->diagnostic_note)) {
			echo '<span class="red">';
			printf($link,$lang['srv_diagnostic_neustreza2']);
			echo '</span>';
		}	else {
			echo '<span class="green">';
			printf($link,$lang['srv_diagnostic_ustreza']);
			echo '</span>';
		}
		echo '</td>';
		echo '</tr>';
		# predolga anketa - opozorilo
		if (in_array(DIAG_TIME_HARD_LIMIT,$this->diagnostic_note)) {
			echo '<tr>';
			echo '<td>'.$lang['srv_diagnostic_2_element_2'].'</td>';
			echo '<td>';
            echo '<span class="red">';
            
            if($lang['id'] == '1')
			    echo '<a href="https://www.1ka.si/d/sl/spletne-ankete/osnovna-priporocila/kako-dolga-naj-bo-moja-anketa?from1ka=1" target="_blank">';
            else
                echo '<a href="https://www.1ka.si/d/en/web-surveys/basic-recommendations/how-long-should-my-survey-be?from1ka=1" target="_blank">';
            
            echo $lang['srv_diagnostic_neustreza'];
			echo '</a>';
			echo '</span>';
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
		echo '</div>';
		
		# PRIPOROČILA
		echo '<br/><br/>';
		echo '<div id="srv_diagnostic_results" >';
		echo '<table class="srv_diagnostic_results">';
		echo '<tr>';
		echo '<th>'.$lang['srv_diagnostika_table_title3'].'</th>';
		echo '<th>'.$lang['srv_diagnostika_table_title'].'</th>';
		echo '</tr>';
		#Preveč ali premalo opomnikov (reminder). 
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_3_element_1'].'</td>';
        echo '<td>';

        if($lang['id'] == '1')
		    $link = '<a href="https://www.1ka.si/d/sl/spletne-ankete/osnovna-priporocila/prevec-ali-premalo-opomnikov?from1ka=1" target="_blank">%s</a>';
        else
            $link = '<a href="https://www.1ka.si/d/en/web-surveys/basic-recommendations/too-many-or-too-few-reminders?from1ka=1" target="_blank">%s</a>';
        
            if (in_array(DIAG_REMINDER_ON_IF,$this->diagnostic_note)) {
			echo '<span class="red">';
			printf($link,$lang['srv_diagnostic_neustreza']);
			echo '</span>';
		}	else {
			echo '<span class="green">';
			printf($link,$lang['srv_diagnostic_ustreza']);
			echo '</span>';
		}
		echo '</td>';
		echo '</tr>';
		
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_3_element_2'].'</td>';
        echo '<td>';

        if($lang['id'] == '1')
		    $link = '<a href="https://www.1ka.si/d/sl/spletne-ankete/osnovna-priporocila/prevec-ali-premalo-opomnikov?from1ka=1" target="_blank">%s</a>';
        else
            $link = '<a href="https://www.1ka.si/d/en/web-surveys/basic-recommendations/too-many-or-too-few-reminders?from1ka=1" target="_blank">%s</a>';
        
            if (in_array(DIAG_REMINDER_ON_MGRID,$this->diagnostic_note)) {
			echo '<span class="red">';
			printf($link,$lang['srv_diagnostic_neustreza']);
			echo '</span>';
		}	else {
			echo '<span class="green">';
			printf($link,$lang['srv_diagnostic_ustreza']);
			echo '</span>';
		}
		echo '</td>';
		echo '</tr>';
		# predolga naketa - priporočilo
		if (in_array(DIAG_TIME_SOFT_LIMIT,$this->diagnostic_note)) {
			echo '<tr>';
			echo '<td>'.$lang['srv_diagnostic_2_element_2'].'</td>';
			echo '<td>';
            echo '<span class="red">';
            
            if($lang['id'] == '1')
			    echo '<a href="https://www.1ka.si/d/sl/spletne-ankete/osnovna-priporocila/kako-dolga-naj-bo-moja-anketa?from1ka=1" target="_blank">';
            else
                echo '<a href="https://www.1ka.si/d/en/web-surveys/basic-recommendations/how-long-should-my-survey-be?from1ka=1" target="_blank">';
            
                echo $lang['srv_diagnostic_neustreza'];
			echo '</a>';
			echo '</span>';
			echo '</td>';
			echo '</tr>';
		}
		echo '</table>';
		echo '</div>';
		echo '</div>';
		
		
		# Trajanje - linki
		echo '<div id="srv_diagnostic_results_right">';
		echo '<table class="srv_diagnostic_results">';
		echo '<tr>';
		echo '<th>'.$lang['srv_info_duration'].'</th>';
		echo '<th></th>';
		echo '<th></th>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>'.$lang['srv_testiranje_predvidenicas'].'</td>';
		echo '<td>'.$skupni_cas.'</td>';
		echo '<td><a href="index.php?anketa=' . $this->sid . '&amp;a='.A_TESTIRANJE.'&amp;m=predvidenicas" title="'.$lang['srv_testiranje_predvidenicas'].'">'.$lang['details'].'</a></td>';
		echo '</tr>';
		$sas = new SurveyAdminSettings();
		$dejanski_cas = ($sas->testiranje_cas(1) == null) ? '-' : $sas->testiranje_cas(1);
		echo '<tr>';
		echo '<td>'.$lang['srv_testiranje_cas'].'</td>';
		echo '<td>'.$dejanski_cas.'</td>';
		echo '<td><a href="index.php?anketa=' . $this->sid . '&amp;a='.A_TESTIRANJE.'&amp;m='.M_TESTIRANJE_CAS.'" title="'.$lang['srv_testiranje_cas'].'">'.$lang['details'].'</a></td>';
		echo '</tr>';
		echo '</table>';
		echo '</div>';
		echo '<br /><br />';
		
		
		echo '<div id="srv_diagnostic_results_right">';
		echo '<table class="srv_diagnostic_results">';
		echo '<tr>';
		echo '<th>'.$lang['srv_diagnostika_table_title4'].'</th>';
		echo '<th colspan="2">'.$lang['srv_diagnostika_table_title'].'</th>';
		echo '</tr>';
		/*echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_1_element_0'].'</td>';
		echo '<td colspan="2">'.$skupni_cas.'</td>';
		echo '</tr>';*/
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_1_element_5'].'</td>';

        echo '<td colspan="2">';   

        if($lang['id'] == '1')
		    echo '<a href="https://www.1ka.si/d/sl/spletne-ankete/osnovna-priporocila/kako-dolga-naj-bo-moja-anketa?from1ka=1" target="_blank">';
        else
            echo '<a href="https://www.1ka.si/d/en/web-surveys/basic-recommendations/how-long-should-my-survey-be?from1ka=1" target="_blank">';

		echo $time;
		echo '</a>';
#		echo Help::display('srv_diag_time').'</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_4_element_9'].'</td>';
		echo '<td colspan="2">'.(int)SurveyInfo::getSurveyGroupCount().'</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_4_element_5'].'</td>';
		echo '<td colspan="2">'.(int)$this->cnt_spremenljivka.'</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_4_element_5a'].'</td>';
		echo '<td colspan="2">'.(int)$this->cnt_hidden.'</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_4_element_8'].'</td>';
		echo '<td colspan="2">'.(int)SurveyInfo::getSurveyVariableCount().'</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_4_element_2'].'</td>';
		echo '<td colspan="2">'.(int)$this->cnt_conditions.'</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_4_element_3'].'</td>';
		echo '<td colspan="2">'.(int)$this->cnt_blocks.'</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_4_element_4'].'</td>';
		echo '<td colspan="2">'.(int)$this->globina.'</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>'.$lang['srv_diagnostic_1_element_4'].'</td>';
		echo '<td colspan="2">';
        
        if($lang['id'] == '1')
            echo '<a href="https://www.1ka.si/d/sl/spletne-ankete/osnovna-priporocila/kaj-pomeni-kompleksnost-ankete?from1ka=1" target="_blank">';
        else
            echo '<a href="https://www.1ka.si/d/en/web-surveys/basic-recommendations/what-does-survey-complexity-mean?from1ka=1" target="_blank">';
        
            echo $kompleksnost;
		echo '</a>';
		#echo Help::display('srv_diag_complexity').'</td>';
		echo '</tr>';
		
		echo '</table>';
		echo '</div>';
		echo '<br /><br />';
		
		
		# nerazrešeni komentarji uporabnikov $commentsUser,$commentsUserFinished		
		$commentsUserUnresolved = $commentsUser - $commentsUserFinished;
		if ((	(int)$commentsAll 
				+(int)$commentsUnresolved
				+(int)$commentsQuestionAll
				+(int)$commentsQuestionUnresolved
				+(int)$commentsUser
				+(int)$commentsUserFinished
				) > 0 ) { 
			echo '<div id="srv_diagnostic_results_right">';
			echo '<table class="srv_diagnostic_results">';
			echo '<tr>';
			echo '<th>'.$lang['srv_diagnostic_4_element_0'].'</th>';
			echo '<th>'.$lang['srv_diagnostic_unresolved'].'</th>';
			echo '<th>'.$lang['srv_diagnostic_all'].'</th>';
			echo '</tr>';
			echo '<tr>';
			echo '<td>'.$lang['srv_diagnostic_4_element_1'].'</td>';
			echo '<td>'.(int)$commentsUnresolved.'</td>';
			echo '<td>'.(int)$commentsAll.'</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td>'.$lang['srv_diagnostic_4_element_1a'].'</td>';
			echo '<td>'.(int)$commentsUserSurveyUnresolved.'</td>';
			echo '<td>'.(int)$commentsUserSurveyAll.'</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td>'.$lang['srv_diagnostic_4_element_6'].'</td>';
			echo '<td>'.(int)$commentsQuestionUnresolved.'</td>';
			echo '<td>'.(int)$commentsQuestionAll.'</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td>'.$lang['srv_diagnostic_4_element_7'].'</td>';
			echo '<td>'.(int)$commentsUserUnresolved.'</td>';
			echo '<td>'.(int)$commentsUser.'</td>';
			echo '</tr>';
			echo '</table>';
			echo '</div>';
		}
		

		echo '<br /><br />';
		echo '</div>'; # id="srv_diagnostic"
	}
	
	/* Začetek ankete z neprimernimi vprašanji.
	 * Ni najbolje začeti z demografijo (razen v primeru specifičnih anket ali ko te podatke potrebujemo zaradi vejitev). 
	 * Še slabše je začeti z občutljivim ali težkimi vprašanji. 
	 * Prvo vprašanje mora namreč biti (a) enostavno, (b) prijazno in (c) zanimivo. 
	 * Demografija (spol, starost, izobrazba ipd.) je običajno na koncu. 
	 * Pri tem je treba posebej dobro razmisliti ali vsa ta vprašanja res potrebujemo. 
	 * Pogosto sprašujemo npr. po regiji, čeprav  je regija za naš problem irelevantna in na tej osnovi kasneje ne izvedemo nobene analize. 
	 * Nepotrebno je tudi preveč podrobno spraševanje (npr. po starosti), če vemo, da nas v pogledu starosti zanima 
	 * kvečjemu analiza treh večjih starostnih skupin (npr. do 30, 30-50, 50+) - v takem primeru zato tako tudi vprašamo. 
	 * S tem se izognemo dodatnemu rekodiranju, hkrati pa seveda respondentom olajšamo odgovarjanje.
	 */
	function testUnapropriateStart() {
		global $lang;
		#Preverimo ali se v prvh 30% vprašanj pojavi beseda spol, starost, izobrazba	
		

		$cnt = 0;
		$_30_percent = $this->cnt_spremenljivka / (100 / SPR_UNAPROPRIATE_START_LIMIT);
		
		$_bad_words = $lang['srv_diagnostic_bad_start_words'];# = array('spol', 'starost', 'rojstva', 'izobrazba', 'šola');

		foreach ( $this->struktura_spremenljivk AS $key => $spr_id ) {
			if ( !in_array(DIAG_UNAPROPRIATE_START,$this->diagnostic_note) 
				&& $cnt < $_30_percent 
				&& $cnt < 5) {
				# preverimo ali se kje pojavijo demografska vprašanja
				$naslov = trim($this->spremenljivke[$spr_id]['naslov']);
				foreach ($_bad_words AS $bad_word) {
					if( !in_array(DIAG_UNAPROPRIATE_START,$this->diagnostic_note) 
						&& stristr($naslov, $bad_word) !== FALSE ) {
							$this->diagnostic_note[] = DIAG_UNAPROPRIATE_START;
					}						
				}	
				 
			}
			$cnt++;
		}
	}
	
	/* Za pomembna vprašanja, npr. vprašanja na osnovi katerih temeljijo vejitve ali vprašanja, 
	 * ki so bistvena za našo analizo, je smiselno nastaviti opozorilo, ki respondenta opozori na pomembnost odgovora. 
	 * Le v zelo nujnih primerih pa respondentu tudi preprečimo nadaljevanje, če ne odgovori. 
	 * Druga skrajnost so trdi opomniki za čisto vsako vprašanje, ki pa lahko odvrnejo respondenta od celotne ankete.
	 */
	# Hkrati potestiramo še za preveč podvprašanj v gridu
	/*Znano je, da respondenti zapuščajo anketo predvsem pri blokih vprašanj, 
	 * kjer se npr. zahteva strinjanje z več trditvami. V enem bloku naj bo zato 
	 * praviloma do največ 8-10 vprašanj, nakar se naredi nov blok z novim (krajšim) 
	 * nagovorom.
	 */ 
	
	function testHardRremindersAndSubQ() {
		$_spr_reminders_checked = array();
		# najprej preverimo ali imamo opomnike na vprašanja na katere se sklicujejo if-i
		if ($this->cnt_conditions > 0) {
			# zloopamo skozi pogoje
			foreach ($this->conditions AS $iid => $condition) {

				# zloopamo skozi posamezen pogoj
				if ( !in_array(DIAG_REMINDER_ON_IF,$this->diagnostic_note)
					&& $condition > 0) { 
					$sql_condition_spr = Cache::srv_condition($condition);
					while($row_condition_spr = mysqli_fetch_array($sql_condition_spr)) {
						# za vako posamezno spremenljivko preverimo ali imamo kakšen reminder
						# vsako spremenljivko preverimo samo 1x :)
						if ( !in_array(DIAG_REMINDER_ON_IF,$this->diagnostic_note)
							&& !isset($_spr_reminders[$row_condition_spr['spr_id']])) {
							if ((int)$this->spremenljivke[$row_condition_spr['spr_id']]['reminder'] == 0) {
								$this->diagnostic_note[] = DIAG_REMINDER_ON_IF;
							}
							
							# zakeširamo spremenljivko, da ne gledamo večkrat
							$_spr_reminders[$row_condition_spr['spr_id']] = true;
						}						
					}
				}
			}
		}
		
		# preverimo še da nimamo remindejrev na vprašanja z ogromno odgovori. (multigrid > 10)
		if ($this->cnt_spremenljivka > 0) {
			foreach ($this->spremenljivke as $skey => $spremenljivka) {
				# checkbox
				if ( $spremenljivka['tip'] == 6 	# m-radio
					|| $spremenljivka['tip'] == 16	# m-check
					|| $spremenljivka['tip'] == 19	# m-text
					|| $spremenljivka['tip'] == 20	# m-number
						) {
					# Preštejemo variable
					$sql_string = "SELECT count(*) AS cnt FROM srv_vrednost WHERE spr_id = '".$spremenljivka['id']."'";
					$sql_query = sisplet_query($sql_string);
					$sql_row = mysqli_fetch_assoc($sql_query);
					if ($spremenljivka['reminder'] == 2 && $sql_row['cnt'] >= SPR_REMINDER_ON_MGRID_LIMIT) {
						# če še ni nastavljeno
						if (!in_array(DIAG_REMINDER_ON_MGRID,$this->diagnostic_note)) {
							$this->diagnostic_note[] = DIAG_REMINDER_ON_MGRID;
						}
					}
					if ($sql_row['cnt'] > SUB_Q_IN_GRID_LIMIT) {
						$this->diagnostic_note[] = DIAG_SUB_Q_IN_GRID;
					}
				}
			}
		}
	}
	
	
	/* Večinoma - razen redkih primerov - je tudi smiselno, da je na eni spletni 
	 * strani v grobem toliko vprašanj, kolikor jih je možno videti na običajnem ekranu pri običajni 
	 * ločljivosti. V tem smislu tudi sicer dajemo na eno stran toliko vprašanj, kot jih gre na ekran, 
	 * vključno s prvo stranjo, kjer poskrbimo, da je gumb "NAPREJ" viden brez uporabe drsnika. 
	 * Le izjemoma in z dobrimi razlogi se odločimo, da dajemo na eno stran več al celo vsa vprašanja, 
	 * kar seveda zahteva od uporabnika da se z miško premika po strani. Podobno velja tudi za tudi vprašanja, 
	 * ki se prikažejo šele ob pogoju -  običajno jh postavimo na novo stran, le v primeru manjših podvprašanj 
	 * sprožimo prikaz na isti strani.
	 */
	function testTooManyQuestions() {
		# preštejemo vprašanja na posamezni strani
		foreach ($this->pages AS $pid => $page) {
			if (count($page) > SPR_ON_PAGE_LIMIT) {
				$this->diagnostic_note[] = DIAG_SPR_ON_PAGE;
			}
		}
		
		# preštejemo vprašanja v posamezem bloku
		foreach ($this->blocks AS $bid => $block) {
			if (count($block) > SPR_IN_BLOCK_LIMIT) {
				$this->diagnostic_note[] = DIAG_SPR_IN_BLOCK;
			}
		}
	}
	
	function testTime() {
		$sas = new SurveyAdminSettings();
		$total = $sas->testiranje_predvidenicas(2);
		$this->time = array( $total,
							 (bcdiv($total, 60, 0)>0?bcdiv($total, 60, 0).'min ':'').''.round(bcmod($total, 60), 0).'s'
							);
		
		if ($total >= TIME_SOFT_LIMIT) {
			if ($total <= TIME_HARD_LIMIT) {
				$this->diagnostic_note[] = DIAG_TIME_SOFT_LIMIT;
			} else {
				$this->diagnostic_note[] = DIAG_TIME_HARD_LIMIT;
			}
		}
	}
		
	function testComments() {
		$spr_id=array();
		$threads=array();
		if ( is_array($this->spremenljivke) && count($this->spremenljivke) > 0 ) {
			foreach ($this->spremenljivke as $id=>$value) {
				$spr_id[] = $id;
				if ((int)$value['thread'] > 0) {
					$threads[] = $value['thread'];
				}
			}
		}
		if (count($spr_id) > 0) {
			
			$db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
			
			#preštejemo komentarje uporabnikov na vprašanja
			# srv_data_text where spr_id = 0 AND vre_id IN (id-ji spremenljivk)
			$strqr = "SELECT count(*) FROM srv_data_text".$db_table." WHERE spr_id=0 AND vre_id IN (".implode(',',$spr_id).")";
			$sqlqr = sisplet_query($strqr);
			list($rowqr) = mysqli_fetch_row($sqlqr);

			#končani komentarji respondentov
			#text2 = 2 => končan
			#text2 = 3 => nerelevantno
			$strqrf = "SELECT count(*) FROM srv_data_text".$db_table." WHERE spr_id=0 AND vre_id IN (".implode(',',$spr_id).") AND text2 IN (2,3)";
			$sqlqrf = sisplet_query($strqrf);
			list($rowqrf) = mysqli_fetch_row($sqlqrf);

			# vsi komentarji na anketo
			$strta = "SELECT count(*) FROM post WHERE tid='".$this->surveyInfo['thread']."' AND parent > 0"; 
			$sqlta = sisplet_query($strta);
			list($rowta) = mysqli_fetch_row($sqlta);
			
			# nerešeni komentarji: only_unresolved =>   ocena <= 1 
			$strtu = "SELECT count(*) FROM post WHERE tid='".$this->surveyInfo['thread']."' AND parent > 0 AND ocena <= 1 "; 
			$sqltu = sisplet_query($strtu);
			list($rowtu) = mysqli_fetch_row($sqltu);
			
			# vsi komentarji na anketo respondentov
			$strtar = "SELECT count(*) FROM srv_comment_resp WHERE ank_id='".$this->sid."'"; 
			$sqltar = sisplet_query($strtar);
			list($rowtar) = mysqli_fetch_row($sqltar);
			
			# nerešeni komentarji respondentov na anketo: only_unresolved =>   ocena <= 1 
			$strtur = "SELECT count(*) FROM srv_comment_resp WHERE ank_id='".$this->sid."' AND ocena <= 1 "; 
			$sqltur = sisplet_query($strtur);
			list($rowtur) = mysqli_fetch_row($sqltur);
			
			$rowtqa = 0;
			$rowtqu = 0;
			# preštejemo 
			if (count($threads) > 0) {
				# vsi komentarji na anketo
				$strta = "SELECT count(*) FROM post WHERE tid IN (".implode(',',$threads).") AND parent > 0"; 
				$sqlta = sisplet_query($strta);
				list($rowtqa) = mysqli_fetch_row($sqlta);
				
				# nerešeni komentarji: only_unresolved =>   ocena <= 1 
				$strtu = "SELECT count(*) FROM post WHERE tid IN (".implode(',',$threads).") AND parent > 0 AND ocena <= 1 "; 
				$sqltu = sisplet_query($strtu);
				list($rowtqu) = mysqli_fetch_row($sqltu);
				
			}			
		}
		$this->comments = array($rowta,$rowtu,$rowtqa,$rowtqu,$rowqr,$rowqrf,$rowtar,$rowtur);
		return $this->comments;
	}
	
	function testConditions() {
		global $lang;
		$code = true;
		
		$b = new Branching($this->sid);
		$code1 = $b->check_pogoji();
		
		// ce je vse ok, preverimo se loope
		$code2 = $b->check_loops();
		
		if (($code1===true && $code2 === true ) !== true) {
			$this->diagnostic_note[] = DIAG_INVALID_CONDITIONS;
		}
		
		$code4 = $b->check_validation();
		if ($code4 !== true) {
			$this->diagnostic_note[] = DIAG_INVALID_VALIDATIONS;
		}
		
		// preverimo podvojensot imen variabel
		$code3 = $b->check_variable();
		if ( $code3 !== true ) {
			$this->diagnostic_note[] = DIAG_INVALID_VARIABLENAMES;
		}		
	}
	
	
	/** Skreiramo "postavitev" ankete
	 * 
	 */
	function createStructure() {
		$parent = '0';
		$this->struktura = $this->getInlineBranching($parent);
		
		$this->spremenljivk = count($this->struktura_spremenljivk);

		$this->globina = ArrayDepth($this->struktura)-1;

	}

	function countHidden() {
		if (count($this->spremenljivke) > 0) {
			foreach ($this->spremenljivke as $spremenljivka) {
				if ((int)$spremenljivka['visible'] == 0) {
					$this->cnt_hidden++;
				}
			}
		}
	}
	
	function getInlineBranching($parent) {

		$_elements_in_block = Cache::srv_branching_parent($this->sid, $parent);
		$result = array();
		foreach ($_elements_in_block AS $key => $_element) {
			if ($_element['element_if'] > 0) {
				# če je if in je tip 1 imamo nov blok
				$_if = Cache::srv_if($_element['element_if']);
				if ($_if['tip'] == 1) {
					# če je tip = 1 imamo blok
					$this->cnt_blocks++;

				} else {
					# preštejemo navane ife
					$this->conditions[$this->cnt_conditions] = $_element['element_if']; 
					$this->cnt_conditions++;

				}
				
				array_push($result, $this->getInlineBranching($_element['element_if']));
			} else {
				array_push($result, $_element['element_spr']);
				
				# dodamo vrstni red spremenljivk
				$this->struktura_spremenljivk[$this->cnt_spremenljivka] = $_element['element_spr'];
				$this->cnt_spremenljivka++;
				
				# ločimo spremenljivke po straneh
				$this->pages[$this->cnt_pages][] = $_element['element_spr'];
				if ($_element['pagebreak'] == 1) {
					$this->cnt_pages++;
				}

				# ločimo spremenljivke po blokih, nov blok je označen kot if - z posebnim tipom
				$this->blocks[$this->cnt_blocks][] = $_element['element_spr'];
			}
			
		}
		return $result;
	}
	
	function printNote($show_link = false) {
		global $lang;

		# napake
		$_napake = (int)in_array(DIAG_INVALID_CONDITIONS, $this->diagnostic_note);
		$_napake += (int)in_array(DIAG_INVALID_VARIABLENAMES, $this->diagnostic_note);
		
		# priporočila:
		# DIAG_SPR_ON_PAGE
		# DIAG_SPR_IN_BLOCK
		# DIAG_SUB_Q_IN_GRID
		# DIAG_UNAPROPRIATE_START
		$_opozorila = (int)in_array(DIAG_SPR_ON_PAGE, $this->diagnostic_note) 
						+(int)in_array(DIAG_SPR_IN_BLOCK, $this->diagnostic_note)
						+(int)in_array(DIAG_SUB_Q_IN_GRID, $this->diagnostic_note)
						+(int)in_array(DIAG_UNAPROPRIATE_START, $this->diagnostic_note)
						+(int)in_array(DIAG_TIME_HARD_LIMIT, $this->diagnostic_note);
		
		# opozorila:
		# DIAG_REMINDER_ON_IF
		# DIAG_REMINDER_ON_MGRID
		$_priporocila = (int)in_array(DIAG_REMINDER_ON_IF, $this->diagnostic_note) 
					+(int)in_array(DIAG_REMINDER_ON_MGRID, $this->diagnostic_note)
					+(int)in_array(DIAG_TIME_SOFT_LIMIT, $this->diagnostic_note);
					
		#print_r($this->diagnostic_note);
		#echo $lang['srv_diagnostic_note'].' '.$lang['srv_diagnostic_note_1'].' ';
		echo $lang['srv_diagnostic_note_1'].' ';
		
		//$_comments = ($this->comments[1] + $this->comments[3] + $this->comments[4] - $this->comments[5]);
		$_comments_survey = ($this->comments[1] + $this->comments[7]);
		$_comments_question = ($this->comments[3] + $this->comments[4] - $this->comments[5]);
		
		if((int)$_napake > 0) echo '<a href="index.php?anketa='.$this->sid.'&a=testiranje">';
		echo $this->string_format((int)$_napake,'srv_cnt_napake');
		if((int)$_napake > 0) echo '</a>';
		
		echo ', ';
		
		if((int)$_opozorila > 0) echo '<a href="index.php?anketa='.$this->sid.'&a=testiranje">';
		echo $this->string_format((int)$_opozorila,'srv_cnt_opozorila');
		if((int)$_opozorila > 0) echo '</a>';
		
		echo ', ';
		
		if((int)$_priporocila > 0) echo '<a href="index.php?anketa='.$this->sid.'&a=testiranje">';
		echo $this->string_format((int)$_priporocila,'srv_cnt_priporocila');
		if((int)$_priporocila > 0) echo '</a>';
		
		/*echo '</a> '.$lang['srv_and'].' <a href="index.php?anketa='.$this->sid.'&a=komentarji">';
		echo $this->string_format((int)$_comments, 'srv_cnt_komentarji');
		echo '</a>.';*/
		
		echo ', ';
		
		if((int)$_comments_survey > 0) echo '<a href="index.php?anketa='.$this->sid.'&a=komentarji_anketa">';
		echo $this->string_format((int)$_comments_survey, 'srv_cnt_komentarji_survey');
		if((int)$_comments_survey > 0) echo '</a>';
		
		echo ' '.$lang['srv_and'].' ';
		
		if((int)$_comments_question > 0) echo ' <a href="index.php?anketa='.$this->sid.'&a=komentarji">';
		echo $this->string_format((int)$_comments_question, 'srv_cnt_komentarji_question');
		if((int)$_comments_question > 0) echo '</a>';
		
		echo '.';
	}
	
	function string_format($cnt,$lang_root) {
		global $lang;
		
		$txt = '';
		if ($cnt > 0) $txt .= '<span class="red">';
		
		if (isset($lang[$lang_root.'_'.$cnt])) {
			$txt .= $cnt.' '.$lang[$lang_root.'_'.$cnt];
		} else {
			$txt .= $cnt.' '.$lang[$lang_root.'_more'];
		}
		
		if ($cnt > 0) $txt .= '</span>';
		
		return $txt;
	}
}

function ArrayDepth($Array,$DepthCount=-1) {
// Find maximum depth of an array
// Usage: int ArrayDepth( array $array )
// returns integer with max depth
// if Array is a string or an empty array it will return 0
  $DepthArray=array(0);
  $DepthCount++;
  
  if (is_array($Array))
    foreach ($Array as $Key => $Value) {
      $DepthArray[]=ArrayDepth($Value,$DepthCount);
    }
  else
    return $DepthCount;
  return max($DepthCount,max($DepthArray));
}