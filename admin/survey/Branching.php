<?php

class Branching {

    var $anketa;                // trenutna anketa
    var $grupa;                 // trenutna grupa
    var $spremenljivka;         // trenutna spremenljivka
    //var $SurveyAdmin = null;    // globalna spremenljivka za SurveyAdmin // SurveyAdmin se nikjer vec ne klice iz Branchinga

    //var $sidebar;               // ali prikazemo sidebar: 0-ne, 1-vprasanja, 2-library	// sidebara nimamo vec
    //var $collapsed_content;     // ali prikazujemo vseibno IFa (ce ne se poklice z ajaxom)	// to se bo pa mogoce se uporabil, pri skrivanju ifov, zaenkrat se pa ne

    var $skin = 0;

    // teh nastavitev se ne uporablja vec
    // tele nastavitve so tudi v BranchingAjax in jih je treba tudi tam popravit!

    //var $maxIfCount = 0;		// koliko ifov je meja za prikaz. Če je 0 prikeže vse
    //var $autoRecount = 0;		// ce je vec kot 50 spremenljivk nimamo avtomatskega prestevilcevanja

    //var $new = true;			// spremenljivka za nov nacin prikaza ifov (s checkboxi), ker bo zihr treba se nazaj dajat... (v Branching.php in branching.js)
    //var $full_screen_edit = false;	// Ali editiramo v full screen nacinu

    var $expanded = false;		// ali prikazujemo anketo razsirjeno (prikazan predogled vprasanja) ali skrceno (samo 1 vrstica za vprasanje)

    var $db_table = '';

    var $lang_id = null;
    var $locked = false;

    var $branching = 0;
    var $displayKomentarji = true;
	
	var $prevajanje = false;
	
	var $imageadded = array();
	//$imageadded[$spremenljivka] = false;

    /**
    * @desc konstruktor
    */
    function __construct ($anketa=0) {
        global $surveySkin;
		global $site_path;
		global $global_user_id;

        // Preverimo vse opcije, da dobimo ID ankete
        if (is_numeric($anketa) && $anketa > 0) {

            $this->anketa = $anketa;

        }elseif (!empty($_POST['anketa']) && is_numeric($_POST['anketa'])){

            $this->anketa = $_POST['anketa'];

        }elseif ( !empty($_GET['anketa']) && is_numeric($_GET['anketa'])) {

            $this->anketa = $_GET['anketa'];

        }

        // Vrnemo in zapišemo v log, kdaj je anketa=0
        if(empty($this->anketa) || $this->anketa <= 0) {
            if($this->anketa == 0)
                return 'Missing ID ankete v branchingu!';
        }

		// spremeni nastavitve pogleda urejanja
		if (isset($_GET['change_mode']) && $_GET['change_mode'] == 1) {
			$ba = new BranchingAjax($this->anketa);
			$ba->ajax_change_mode();
		}

		# clear E_NOTICE
		if (!isset($_POST['spr']))		{ $_POST['spr'] = null; }
		if (!isset($_POST['if']))		{ $_POST['if'] = null; }
		if (!isset($_POST['endif']))	{ $_POST['endif'] = null; }
		if (!isset($_POST['parent_if']))	{ $_POST['parent_if'] = null; }
		if (!isset($_GET['parent_if']))	{ $_GET['parent_if'] = null; }
		if (!isset($_POST['info']))	{ $_POST['info'] = null; }

        if (isset($surveySkin))
            $this->skin = $surveySkin;

        SurveyInfo::getInstance()->SurveyInit($this->anketa);

		$this->db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();

		UserSetting :: getInstance()->Init($global_user_id);

        // v novem nacinu se vsem anketam nastavi branching na 1 (popravi se pri starih - brez ifov, da dobiji srv_branching tabelo)
        $row = SurveyInfo::getInstance()->getSurveyRow();
        if ($row['branching'] == 0) {
            $this->init_branching();
        }
        if ($row['expanded'] == 1)
        	$this->expanded = true;

        if ($row['locked'] == 1)
        	$this->locked = 1;
    }

    /**
    * @desc inicializacija branchinga (samo prvic, na zacetku), prepise vrstni red iz normalenga urejanja
    */
    function init_branching () {

        // nastavimo na branching, da ne sinhroniza
        sisplet_query("UPDATE srv_anketa SET branching='1' WHERE id = '$this->anketa'");

		// vsilimo refresh podatkov
		SurveyInfo :: getInstance()->resetSurveyData();

        sisplet_query("DELETE FROM srv_branching WHERE ank_id='$this->anketa'");

        $sql = sisplet_query("SELECT s.id, s.gru_id, s.vrstni_red
                            FROM srv_grupa g, srv_spremenljivka s
                            WHERE g.ank_id='$this->anketa' AND g.id=s.gru_id AND s.id
                            ORDER BY g.vrstni_red ASC, s.vrstni_red ASC
                            ");
        $vrstni_red = 1;
        while ($row = mysqli_fetch_array($sql)) {

            $sql1 = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id = '$row[gru_id]' AND vrstni_red = ('$row[vrstni_red]'+1)");
            if (mysqli_num_rows($sql1) == 0 && $this->find_last_spr() != $row['id'])
                $pb = 1;
            else
                $pb = 0;

            sisplet_query("INSERT INTO srv_branching (ank_id, parent, element_spr, element_if, vrstni_red, pagebreak) VALUES ('$this->anketa', '0', '$row[id]', '0', '$vrstni_red', '$pb')");

            $vrstni_red ++;
        }

    }

    function display_new () {
		global $lang;

		$row = SurveyInfo::getInstance()->getSurveyRow();
		#$this->survey_type = SurveyAdmin::getSurvey_type($this->anketa);
		$this->survey_type = SurveyInfo::getInstance()->getSurveyColumn("survey_type");

		// Glasovanje
		if($this->survey_type == 0){

			$gl = new Glasovanje($this->anketa);

			//div z nastavitvami za glasovanje
			echo '  <div id="glas_settings">';
			//$this->display_glasovanje_settings();
			$gl->display_glasovanje_settings();
			echo '  </div> <!-- /glas_settings -->';


			echo '<div id="placeholder">';

			echo '<div id="branching" class="branching_new expanded branching_glasovanje">';

			//$this->branching_struktura();
			$gl = new Glasovanje($this->anketa);
			$gl->vprasanja();

			echo '</div>';	// #branching

			//$this->vprasanje_float_editing();
			echo '<div id="vprasanje_float_editing" class="float_glasovanje"></div>';

			echo '</div>';	// #placeholder

			$this->toolbox();
		}

		// Navadna anketa ali forma
		else{
			echo '<div id="placeholder">';

			echo '<div id="branching" class="branching_new'.($this->expanded?' expanded':' collapsed').($this->survey_type==1?' branching_forma':'').'">';
			
			Common::Init($this->anketa);
			echo Common::checkStruktura();
			$this->branching_struktura();

			echo '</div>';	// #branching

			$this->vprasanje_float_editing();

			echo '</div>';	// #placeholder

			$this->toolbox();
		}

		// forma in glasovanje - hitre nastavitve na desni - ce imamo odprto knjiznico ne prikazemo zaradi prekrivanja
		if ( ($this->survey_type == 1 || $this->survey_type == 0) && ($row['toolbox'] < 3) ) {
			echo '<div id="quick_settings_holder" '.($this->survey_type==0 ? ' class="glas_quick_settings"':'').'>';
			echo '<div id="quick_settings" '.($this->survey_type==0 ? ' class="glas_quick_settings"':'').'>';
			$this->toolbox_settings();
			echo '</div>';
			echo '</div>';
		}

		if ($row['popup'] == 0) {	// default je true
			?><script> popup = false; </script><?php
		}
		if ($row['locked'] == 1) {	// default je true
			?><script> locked = true; </script><?php
		}

		?><script> var vprasanje_tracking = <?=$row['vprasanje_tracking']?>; </script><?php

		/*echo '<script>';
		echo 'alert(document.getElementsByTagName("*").length);';
		echo '</script>';*/

    }


    function vprasanje_float_editing () {

		echo '<div id="vprasanje_float_editing"></div>';

    }

    /**
    * prikaze zgornji toolbox z nastavitvami
    *
    */
    /*function toolbox_nastavitve () {

		$row = SurveyInfo::getInstance()->getSurveyRow();

		echo '<div id="toolbox_nastavitve"'.($row['toolbox']>=3?' class="library"':'').'>';
		$this->display_toolbox_nastavitve();
		echo '</div>';
    }*/

    /**
    * prikaze zgornji toolbox z nastavitvami
    *
    */
    function display_toolbox_nastavitve() {
		global $lang;
		global $admin_type;
		global $site_url;
		global $global_user_id;

		$row = SurveyInfo::getInstance()->getSurveyRow();
    	# preverimo ali imamo ife. Če so, izpisujemo vse ikonce
		$sql_select = "SELECT count(*) AS if_count FROM srv_branching WHERE element_if > 0 AND ank_id = '".$this->anketa."'";
		$sql_query = sisplet_query($sql_select);
		$row_query = mysqli_fetch_array($sql_query);
		$has_if = (int)$row_query['if_count'] > 0 ? true : false;

		# ali prikazujemo vse ikonice ali samo "simpl" ikonice
		$sql_select_fv = "SELECT count(*) AS full_view FROM srv_user_setting_for_survey WHERE sid='".$this->anketa."' AND uid='".$global_user_id."' AND what='display_full_toolbox' AND value='1'";
		$sql_query_fv = sisplet_query($sql_select_fv);
		$row_query_fv = mysqli_fetch_array($sql_query_fv);
		$full_view = (int)$row_query_fv['full_view'] == 1 ? true : false;
		$full_view = true;

		echo '<div class="floatLeft">';
		# skrčen / razširjen
		if ($this->expanded) {
			echo '<span title="'.$lang['srv_expanded_1'].'"><a href="index.php?anketa='.$this->anketa.'&a=branching&change_mode=1&what=expanded&value=0"><span class="faicon expanded"></span>'.$lang['srv_expanded_0_short'].'</a></span> ';
			$sqle = sisplet_query("SELECT count(*) AS c FROM srv_branching WHERE ank_id='$this->anketa'");
			$rowe = mysqli_fetch_array($sqle);
			if ($rowe['c'] > 15 && !isset($_COOKIE['expanded']))
				echo '<span class="expanded-tooltip top">'.$lang['srv_expaned_text'].' <a href="#" onclick="$(this).closest(\'.expanded-tooltip\').hide(); var d = new Date(); d.setDate(d.getDate()+30); document.cookie=\'expanded=1;expires=\'+d.toUTCString(); return false;" class="close">'.$lang['srv_zapri'].'</a><span class="arrow"></span></span>';
		} else {
			echo '<span title="'.$lang['srv_expanded_0'].'"><a href="index.php?anketa='.$this->anketa.'&a=branching&change_mode=1&what=expanded&value=1"><span class="faicon compress"></span>'.$lang['srv_expanded_0_short'].'</a></span> ';
		}
		echo Help::display('srv_branching_expanded');

		echo '</div>';


		echo '<div class="floatRight">';

		# find & replace
		if ($row['locked'] == 0) {
			echo '<a href="#" onclick="find_replace(); return false;" title="'.$lang['srv_find_replace'].'" ><span class="faicon replace"></span></a>';
		}

		if ($row['locked'] == 0 && $full_view == true) {
			echo '<a href="#" onclick="javascript:pagebreak_all(); return false;" title="'.$lang['srv_pagebreak_all'].'"><span class="faicon paragraph"></span></a> '."\n";
		}

		# Hrošč je viden samo če imamo ife in razširjen pogled
		if ($row['locked'] == 0 && $full_view == true) { // if ($row['flat'] == 0)
			echo '<a href="#" onclick="check_pogoji(); return false;" title="'.$lang['srv_check_pogoji'].'"><span class="faicon bug"></span></a> '."\n";
		}

		if ($row['locked'] == 0 && $full_view == true) {
			echo '<a href="#" onclick="prestevilci(); return false;"><span class="faicon hashtag" title="'.$lang['srv_grupe_recount_branching'].'"></span></a>' . Help :: display('srv_grupe_recount_branching')  ."\n";
		}

		if ($has_if == true && $full_view == true || ($has_if == true && $this->expanded == false)) {
			echo '<a href="#" onClick="expandCollapseAllPlusMinus(\'expand\'); return false;"><span class="faicon plus_square" title="'.$lang['srv_expand'].'"></span></a>'."\n";
			echo '<a href="#" onClick="expandCollapseAllPlusMinus(\'collapse\'); return false;"><span class="faicon minus_square" title="'.$lang['srv_collapse'].'"></span></a>'."\n";
		}

		echo '</div>';

		// prikaz blokov kot zavihke
		echo '<div class="blockSwitch">';
		$sql = sisplet_query("SELECT i.* FROM srv_if i, srv_branching b WHERE i.tab='1' AND i.tip='1' AND i.id=b.element_if AND b.ank_id='$this->anketa' ORDER BY b.parent, b.vrstni_red");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		$i = 0;
		while ($row = mysqli_fetch_array($sql)) {
			if ($i++ != 0) echo ' | ';
			$label = ($row['label'] == ''?$lang['srv_blok'].' ('.$row['number'].')':$row['label']);
			echo '<a href="index.php?anketa='.$this->anketa.'&parent_if='.$row['id'].'">'.$label.'</a> ';
		}
		echo '</div>';

		echo '<br class="clr"/>';
    }

    /**
    * zamenja full_view = 1/0 za toolbox
    *
    */
    function toogle_toolbox_nastavitve() {
    	global $global_user_id;

    	# ali prikazujemo vse ikonice ali samo "simpl" ikonice

    	$sql_update_fv = "INSERT INTO srv_user_setting_for_survey (sid,uid,what,value) VALUES ('$this->anketa', '$global_user_id', 'display_full_toolbox', '1') ON DUPLICATE KEY UPDATE value = !value";
    	$sql_query_fv = sisplet_query($sql_update_fv);
    	$this->display_toolbox_nastavitve();
    }

    /**
    * prikaze izbrani toolbox
    *
    */
    function toolbox () {

		$row = SurveyInfo::getInstance()->getSurveyRow();

		if($this->survey_type != 0)
			$this->toolbox_basic2();

		if ($row['toolbox'] >= 3)
			$this->toolbox_library();

		?><script>
		$(function () {
			init_toolbox();
		});
		</script><?php

        // Mobile add question
        MobileSurveyAdmin::displayAddQuestion($this->anketa);
    }

    /**
    * novi toolbox s tipi vprašanj
    */
    function toolbox_basic2 () {
		global $lang;
		global $admin_type;
		global $global_user_id;

		$row = SurveyInfo::getInstance()->getSurveyRow();
		$this->survey_type = SurveyInfo::getInstance()->getSurveyColumn("survey_type");
		$hierarhija = SurveyInfo::getInstance()->checkSurveyModule('hierarhija');

		// Toolbox napredne nastavitve (prestevilci, dodaj prelome, debug...)
		if($this->survey_type > 1 && !SurveyInfo::getInstance()->checkSurveyModule('hierarhija') ){
		
			echo '<div id="toolbox_advanced_settings">';

			echo '<span class="faicon icon-blue wheel_32"></span>';

			$this->toolbox_add_advanced_settings();

			echo '</div>';
		}


		echo '<div id="toolbox_basic"'.($this->survey_type==1?' class="forma"':'').($hierarhija ? ' class="toolbox-hierarhija"' : '').'>';

		if ($row['locked'] == 1) {

			// Ce ima uporabnik prepreceno moznost odklepanja ankete
			$d = new Dostop();
			if(($hierarhija && SurveyInfo::getSurveyModules('hierarhija') == 2) || $d->checkDostopSub('lock') && ($admin_type != 0 && $admin_type != 1)){
				echo '<div id="locked_toolbar">';
				echo '<span class="sprites lock_big"></span>';
				echo '</div>';
			}
			else{
				//echo '<a href="#" title="'.$lang['srv_anketa_locked_1'].'" onclick="javascript:anketa_lock(\''.$this->anketa.'\', \'0\');"><p style="text-align:center" ><span class="sprites lock_close_white"></span></p></a>';
				echo '<div id="locked_toolbar">';
				echo '<span class="sprites lock_big pointer" onclick="javascript:anketa_lock(\''.$this->anketa.'\', \'0\', \''.$row['mobile_created'].'\');"></span>';
				echo '</div>';
			}

			if(!$hierarhija) {
				echo '<p class="new_spr" tip="1"><span class="faicon icon-white radio_32"></span></p>';
				echo '<p class="new_spr" tip="2"><span class="faicon icon-white check_32"></span></p>';
			}

			echo '<p class="new_spr" tip="6"><span class="faicon icon-white matrix_32"></span></p>';
			echo '<p class="new_spr" tip="21"><span class="faicon icon-white abc_32"></span></p>';	

			if(!$hierarhija)
				echo '<p class="new_spr" tip="7"><span class="faicon icon-white number_32"></span></p>';

			echo '<p class="new_spr" tip="5"><span class="faicon icon-white nagovor"></span></p>';
			
			if(!$hierarhija) {
				echo '<div class="new_adv"><span class="faicon icon-white plus_32"></span>';
				$this->toolbox_add_advanced();
				echo '</div>';
			}

			// normalna anketa
			if ($this->survey_type != 1){

                // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
                $userAccess = UserAccess::getInstance($global_user_id);
                
				echo '<p class="new_if '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" tip="9999"><span class="faicon icon-white if_32"></span></p>';
				echo '<p class="new_block '.(!$userAccess->checkUserAccess($what='block') ? 'user_access_locked' : '').'" tip="9998"><span class="faicon icon-white block_32"></span></p>';
			}
		}
		else {

			if(!$hierarhija) {
				echo '<p class="new_spr" tip="1"><span class="faicon icon-white radio_32"></span></p>';
				echo '<p class="new_spr" tip="2"><span class="faicon icon-white check_32"></span></p>';
			}

			echo '<p class="new_spr" tip="6"><span class="faicon icon-white matrix_32"></span></p>';
			echo '<p class="new_spr" tip="21"><span class="faicon icon-white abc_32"></span></p>';
			
			if(!$hierarhija)
				echo '<p class="new_spr" tip="7"><span class="faicon icon-white number_32"></span></p>';

			echo '<p class="new_spr" tip="5"><span class="faicon icon-white nagovor"></span></p>';
			
			if(!$hierarhija) {
				echo '<div class="new_adv"><span class="faicon icon-white plus_32"></span>';
				$this->toolbox_add_advanced();
				echo '</div>';
			}

			// normalna anketa
			if ($this->survey_type != 1){

                // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
                $userAccess = UserAccess::getInstance($global_user_id);
                
				echo '<p class="new_if '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" tip="9999"><span class="faicon icon-white if_32"></span></p>';
				echo '<p class="new_block '.(!$userAccess->checkUserAccess($what='block') ? 'user_access_locked' : '').'" tip="9998"><span class="faicon icon-white block_32"></span></p>';
			}
		}

		echo '</div>';
    }

    /**
    * prikaze popup z vsemi tipi vprasanj
    *
    */
    function toolbox_add_advanced() {
		global $lang;
		global $site_url;
		global $admin_type;
		global $global_user_id;

		$spr = $_POST['spr'];
		$if = $_POST['if'];
		$endif = $_POST['endif'];

        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
        $userAccess = UserAccess::getInstance($global_user_id);

		echo '<div id="toolbox_add_advanced">';

		echo '<p class="toolbox_add_title">'.$lang['srv_add_question_type'].' '.Help::display('srv_toolbox_add_advanced').'</p>';
		echo '<p class="new_question">'.$lang['srv_new_question_text'].'</p>';

		echo '<a style="position:absolute; right:10px; top:10px" href="#" title="'.$lang['srv_zapri'].'" onclick="$(\'#toolbox_add_advanced\').hide(); return false;">';
		echo '<span class="faicon close"></span>';
		echo '</a>';

		echo '<div class="holder">';

		echo '<p class="naslov">'.$lang['srv_sklop_osnovna2'].'</p>';
		echo '<div class="clr"></div>';

		echo '<p class="new_spr adv" tip="1"><span class="sprites radio3"></span> '.$lang['srv_vprasanje_tip_1'].'</p>';
		echo '<p class="new_spr adv" tip="3"><span class="sprites osnovna_vprasanja"></span> '.$lang['srv_vprasanje_tip_1'].' - '.$lang['srv_dropdown'].'</p>';
		echo '<p class="new_spr adv" tip="2"><span class="sprites checkbox3"></span> '.$lang['srv_vprasanje_tip_2'].'</p>';

		echo '<div class="clr"></div>';
		echo '<p class="naslov">'.$lang['srv_vprasanje_tables'].'</p>';
		echo '<div class="clr"></div>';

		echo '<p class="new_spr podtip adv" tip="6" podtip="0"><span class="sprites table"></span> '.$lang['srv_classic'].'</p>';
		echo '<p class="new_spr podtip adv" tip="6" podtip="1"><span class="sprites table"></span> '.$lang['srv_diferencial2'].'</p>';
		echo '<p class="new_spr podtip adv" tip="6" podtip="2"><span class="sprites table"></span> '.$lang['srv_table_dropdown'].'</p>';
		echo '<p class="new_spr podtip adv" tip="6" podtip="3"><span class="sprites table"></span> '.$lang['srv_double_grid'].'</p>';

		echo '<div class="clr"></div>';
		echo '<p class="naslov">'.$lang['srv_sklop_posebna'].'</p>';
		echo '<div class="clr"></div>';

		echo '<p class="new_spr adv" tip="5"><span class="faicon preview icon-blue"></span> '.$lang['srv_vprasanje_tip_5'].'</p>';

        echo '<p class="new_spr adv '.(!$userAccess->checkUserAccess($what='question_type_location') ? 'user_access_locked' : '').'" tip="26"><span class="faicon preview icon-blue"></span> '.$lang['srv_vprasanje_tip_26'].'</p>';
		
		echo '<p class="new_spr adv '.(!$userAccess->checkUserAccess($what='question_type_heatmap') ? 'user_access_locked' : '').'" tip="27"><span class="faicon preview icon-blue"></span> '.$lang['srv_vprasanje_heatmap'].'</p>';
		
		echo '<p class="new_spr adv" tip="8"><span class="faicon preview icon-blue"></span> '.$lang['srv_vprasanje_tip_8'].'</p>';
		echo '<p class="new_spr adv '.(!$userAccess->checkUserAccess($what='question_type_ranking') ? 'user_access_locked' : '').'" tip="17"><span class="faicon preview icon-blue"></span> '.$lang['srv_vprasanje_tip_17'].'</p>';
		echo '<p class="new_spr adv '.(!$userAccess->checkUserAccess($what='question_type_sum') ? 'user_access_locked' : '').'" tip="18"><span class="faicon preview icon-blue"></span> '.$lang['srv_vprasanje_tip_18'].'</p>';

		echo '</div>';
		echo '<div class="holder">';

		echo '<p class="naslov">'.$lang['srv_sklop_osnovna_vnos2'].'</p>';
		echo '<div class="clr"></div>';

		echo '<p class="new_spr adv" tip="7"><span class="sprites number"></span> '.$lang['srv_vprasanje_tip_7'].'</p>';
		echo '<p class="new_spr adv" tip="21"><span class="sprites text"></span> '.$lang['srv_vprasanje_tip_21'].'</p>';
		echo '<p class="new_spr podtip adv" tip="7" podtip="2"><span class="sprites number"></span> '.$lang['srv_number_insert_1'].'</p>';

		echo '<div class="clr"></div>';
		echo '<p class="naslov">'.$lang['srv_sklop_tabele_ostale'].'</p>';
		echo '<div class="clr"></div>';

		echo '<p class="new_spr adv" tip="16"><span class="sprites table"></span> '.$lang['srv_vprasanje_tip_16'].'</p>';
		echo '<p class="new_spr adv" tip="20"><span class="sprites table"></span> '.$lang['srv_vprasanje_tip_20'].'</p>';
		echo '<p class="new_spr adv" tip="19"><span class="sprites table"></span> '.$lang['srv_vprasanje_tip_19'].'</p>';
		echo '<p class="new_spr adv '.(!$userAccess->checkUserAccess($what='question_type_multitable') ? 'user_access_locked' : '').'" tip="24"><span class="sprites table"></span> '.$lang['srv_survey_table_multiple'].'</p>';

		echo '<div class="clr"></div>';
		echo '<p class="naslov">'.$lang['srv_standardni_vnosi'].'</p>';
		echo '<div class="clr"></div>';

		echo '<p class="new_spr podtip adv" tip="21" podtip="2"><span class="faicon preview icon-blue"></span> '.$lang['email'].'</p>';
		echo '<p class="new_spr podtip adv" tip="21" podtip="3"><span class="faicon preview icon-blue"></span> '.$lang['url'].'</p>';
		echo '<p class="new_spr podtip adv" tip="21" podtip="4"><span class="faicon preview icon-blue"></span> '.$lang['srv_tip_standard_993'].'</p>';
        echo '<p class="new_spr podtip adv" tip="21" podtip="7"><span class="faicon preview icon-blue"></span> '.$lang['srv_vprasanje_tip_long_21_7'].'</p>';
		echo '<p class="new_spr podtip adv" tip="21" podtip="1"><span class="faicon preview icon-blue"></span> '.$lang['srv_captcha_edit'].'</p>';
		echo '<p class="new_spr podtip adv" tip="1" podtip="10"><span class="faicon preview icon-blue"></span> '.$lang['srv_gdpr'].'</p>';

		echo '</div>';

		echo '<div class="holder">';

		if (($lang['id'] == '1' || $lang['id'] == '2') && ($site_url == 'https://www.1ka.si/' || strpos($site_url, 'localhost') !== false ) ) {

			echo '<p class="naslov">'.$lang['srv_demografija'].'</p>';
			echo '<div class="clr"></div>';

			if ($lang['id'] == '1') {
				echo '<p class="new_spr podtip adv" tip="23" podtip="'.Demografija::getInstance()->getSpremenljivkaID('XSPOL').'"><span class="faicon preview icon-blue"></span> '.$lang['srv_demografija_spol'].'</p>';
				echo '<p class="new_spr podtip adv" tip="23" podtip="'.Demografija::getInstance()->getSpremenljivkaID('XSTAR2a4').'"><span class="faicon preview icon-blue"></span> '.$lang['srv_demografija_starost'].'</p>';
				echo '<p class="new_spr podtip adv" tip="23" podtip="'.Demografija::getInstance()->getSpremenljivkaID('XZST1surs4').'"><span class="faicon preview icon-blue"></span> '.$lang['srv_demografija_zakonski_stan'].'</p>';
				echo '<p class="new_spr podtip adv" tip="23" podtip="'.Demografija::getInstance()->getSpremenljivkaID('XDS2a4').'"><span class="faicon preview icon-blue"></span> '.$lang['srv_demografija_status'].'</p>';
				echo '<p class="new_spr podtip adv" tip="23" podtip="'.Demografija::getInstance()->getSpremenljivkaID('XIZ1a2').'"><span class="faicon preview icon-blue"></span> '.$lang['srv_demografija_izobrazba'].'</p>';
				echo '<p class="new_spr podtip adv" tip="23" podtip="'.Demografija::getInstance()->getSpremenljivkaID('XLOKACREGk').'"><span class="faicon preview icon-blue"></span> '.$lang['srv_demografija_lokacija'].'</p>';
			}

			if ($lang['id'] == '2') {
				echo '<p class="new_spr podtip adv" tip="23" podtip="'.Demografija::getInstance()->getSpremenljivkaID('XSEX').'"><span class="faicon preview icon-blue"></span> '.$lang['srv_demografija_spol'].'</p>';
				echo '<p class="new_spr podtip adv" tip="23" podtip="'.Demografija::getInstance()->getSpremenljivkaID('XAGE').'"><span class="faicon preview icon-blue"></span> '.$lang['srv_demografija_starost'].'</p>';
				echo '<p class="new_spr podtip adv" tip="23" podtip="'.Demografija::getInstance()->getSpremenljivkaID('XMRSTS').'"><span class="faicon preview icon-blue"></span> '.$lang['srv_demografija_zakonski_stan'].'</p>';
				echo '<p class="new_spr podtip adv" tip="23" podtip="'.Demografija::getInstance()->getSpremenljivkaID('XSTS').'"><span class="faicon preview icon-blue"></span> '.$lang['srv_demografija_status'].'</p>';
				echo '<p class="new_spr podtip adv" tip="23" podtip="'.Demografija::getInstance()->getSpremenljivkaID('XEDU').'"><span class="faicon preview icon-blue"></span> '.$lang['srv_demografija_izobrazba'].'</p>';
				echo '<p class="new_spr podtip adv" tip="23" podtip="'.Demografija::getInstance()->getSpremenljivkaID('XLOC').'"><span class="faicon preview icon-blue"></span> '.$lang['srv_demografija_lokacija'].'</p>';
			}
		}

		echo '<p class="new_spr_spacer"></p>';
		echo '<p class="new_spr_spacer" style="height: 22px;"></p>';

		echo '<div class="clr"></div>';
		echo '<p class="naslov">'.$lang['srv_advanced_features'].'</p>';
		echo '<div class="clr"></div>';

        // Kalkulacija
		echo '<p class="new_spr adv '.(!$userAccess->checkUserAccess($what='question_type_calculation') ? 'user_access_locked' : '').'" tip="22"><span class="faicon preview icon-blue"></span> '.$lang['srv_vprasanje_tip_22'].'</p>';
		
		// Kvota
		echo '<p class="new_spr adv '.(!$userAccess->checkUserAccess($what='question_type_quota') ? 'user_access_locked' : '').'" tip="25"><span class="faicon preview icon-blue"></span> '.$lang['srv_vprasanje_tip_25'].'</p>';
		
		// Loop
		echo '<p class="new_loop '.(!$userAccess->checkUserAccess($what='loop') ? 'user_access_locked' : '').'" tip="9997"><span class="faicon preview icon-blue"></span> '.$lang['srv_zanka'].'</p>';
		
		// Signature
		echo '<p class="new_spr podtip adv '.(!$userAccess->checkUserAccess($what='question_type_signature') ? 'user_access_locked' : '').'" tip="21" podtip="6"><span class="faicon preview icon-blue"></span> '.$lang['srv_signature_edit'].'</p>';

		// Chat (nagovor z gumbom za vklop chata) - ce je vklopljen modul chat
		if (SurveyInfo::getInstance()->checkSurveyModule('chat')){
			echo '<p class="new_spr podtip adv" tip="5" podtip="2"><span class="faicon preview icon-blue"></span> '.$lang['srv_vprasanje_tip_5_2'].'</p>';
		}
	
		// Socialna omrezja
		if (SurveyInfo::getInstance()->checkSurveyModule('social_network')){
			echo '<p class="new_spr adv" tip="9"><span class="faicon preview icon-blue"></span> '.$lang['srv_vprasanje_tip_9'].'</p>';
        }
        elseif(!$userAccess->checkUserAccess($what='question_type_signature')){
			echo '<p class="new_sn adv" tip="9" style="float:left; width:150px; margin:1px 0 1px 1px; padding:4px 7px;"><a href="index.php?anketa='.$this->anketa.'&a=social_network" class="user_access_locked"><span class="faicon preview icon-blue"></span> '.$lang['srv_vprasanje_tip_9'].'</A></p>';
        }
		else{
			echo '<p style="float:left; width:150px; margin:1px 0 1px 1px; padding:4px 7px;"><a href="index.php?anketa='.$this->anketa.'&a=social_network" '.(!$userAccess->checkUserAccess($what='question_type_signature') ? 'class="user_access_locked"' : '').'"><span class="faicon preview icon-blue"></span> '.$lang['srv_vprasanje_tip_9'].'</A></p>';
		}	

		echo '</div>';

		//echo '<a style="position:absolute; right:10px; bottom:10px" href="#" onclick=" $(\'#toolbox_add_advanced\').addClass(\'dragging\'); setTimeout(function() { $(\'#toolbox_add_advanced\').removeClass(\'dragging\'); }, 500); return false;">'.$lang['srv_zapri'].'</a>';

		echo '</div>';
    }

	/**
    * prikaze popup z vsemi tipi vprasanj
    *
    */
    function toolbox_add_advanced_settings() {
		global $lang;
		global $admin_type;
		global $site_url;
		global $global_user_id;

		echo '<div id="toolbox_advanced_settings_holder">';

		echo '<span class="advanced_settings_title">'.$lang['srv_advanced_settings_title'].'</span>';

		$row = SurveyInfo::getInstance()->getSurveyRow();

    	# preverimo ali imamo ife. Če so, izpisujemo vse ikonce
		$sql_select = "SELECT count(*) AS if_count FROM srv_branching WHERE element_if > 0 AND ank_id = '".$this->anketa."'";
		$sql_query = sisplet_query($sql_select);
		$row_query = mysqli_fetch_array($sql_query);
		$has_if = (int)$row_query['if_count'] > 0 ? true : false;

		# ali prikazujemo vse ikonice ali samo "simpl" ikonice
		$sql_select_fv = "SELECT count(*) AS full_view FROM srv_user_setting_for_survey WHERE sid='".$this->anketa."' AND uid='".$global_user_id."' AND what='display_full_toolbox' AND value='1'";
		$sql_query_fv = sisplet_query($sql_select_fv);
		$row_query_fv = mysqli_fetch_array($sql_query_fv);
		$full_view = (int)$row_query_fv['full_view'] == 1 ? true : false;
		$full_view = true;

		// Razsiri / skrci
		if($this->survey_type > 1){
			if($this->expanded) {
				echo '<p>';
				echo '<a href="index.php?anketa='.$this->anketa.'&a=branching&change_mode=1&what=expanded&value=0" title="'.$lang['srv_expanded_0'].'">';
				echo '<span class="advanced_setting"><span class="faicon compress"></span></span>';
				echo $lang['srv_expand_0'];
				echo '</a>';
				echo '</p>';
			}
			else {
				echo '<p>';
				echo '<a href="index.php?anketa='.$this->anketa.'&a=branching&change_mode=1&what=expanded&value=1" title="'.$lang['srv_expanded_1'].'">';
				echo '<span class="advanced_setting"><span class="faicon expand"></span></span>';
				echo $lang['srv_expand_1'];
				echo '</a>';
				echo '</p>';
			}
		}

		if ($has_if == true/* && $full_view == true || ($has_if == true && $this->expanded == false)*/) {
			echo '<p>';
			echo '<a href="#" onClick="expandCollapseAllPlusMinus(\'expand\'); return false;"><span class="advanced_setting"><span class="faicon plus_square" title="'.$lang['srv_expand'].'"></span></span>'.$lang['srv_expand'].'</a>'."\n";
			echo '<a href="#" onClick="expandCollapseAllPlusMinus(\'collapse\'); return false;"><span class="advanced_setting" style="margin-left: 15px;"><span class="faicon minus_square" title="'.$lang['srv_collapse'].'"></span></span>'.$lang['srv_collapse'].'</a>'."\n";
			echo '</p>';
		}

		# find & replace
		if ($row['locked'] == 0) {
			echo '<p>';
			echo '<a href="#" onclick="find_replace(); return false;" title="'.$lang['srv_find_replace_words'].'" ><span class="advanced_setting"><span class="faicon replace"></span></span>'.$lang['srv_find_replace_words'].'</a>';
			echo '</p>';
		}

		if ($row['locked'] == 0 && $full_view == true) {
			echo '<p>';
			echo '<a href="#" onclick="javascript:pagebreak_all(); return false;" title="'.$lang['srv_pagebreak_all'].'"><span class="advanced_setting"><span class="faicon paragraph"></span></span>'.$lang['srv_pagebreak_all'].'</a> '."\n";
			echo '</p>';
		}

		# Hrošč je viden samo če imamo ife in razširjen pogled
		if ($row['locked'] == 0 && $full_view == true) { // if ($row['flat'] == 0)
			echo '<p>';
			echo '<a href="#" onclick="javascript:check_pogoji(); return false;" title="'.$lang['srv_check_pogoji'].'"><span class="advanced_setting"><span class="faicon bug"></span></span>'.$lang['srv_check_pogoji'].'</a> '."\n";
			echo '</p>';
		}

		if ($row['locked'] == 0 && $full_view == true) {
			echo '<p>';
			echo '<a href="#" onclick="prestevilci(); return false;"><span class="advanced_setting"><span class="faicon hashtag" title="'.$lang['srv_grupe_recount_branching'].'"></span></span>'.$lang['srv_grupe_recount_branching'].'</a>' . Help :: display('srv_grupe_recount_branching')  ."\n";
			echo '</p>';
		}

		// Knjiznica na desni
		if ($row['locked'] == 0) {
			if($row['toolbox'] >= 3){
				echo '<p>';
				echo '<a href="#" onclick="change_mode(\'toolboxback\', \'1\'); return false;" title="'.$lang['srv_library_hide'].'" ><span class="advanced_setting"><span class="faicon library smaller"></span></span>'.$lang['srv_library_hide'].'</a>';
				echo '</p>';
			}
			else{
				echo '<p>';
				echo '<a href="#" onclick="change_mode(\'toolbox\', \'3\'); return false;" title="'.$lang['srv_library_show'].'" ><span class="advanced_setting"><span class="faicon library smaller"></span></span>'.$lang['srv_library_show'].'</a>';
				echo '</p>';
			}
		}
		
		echo '</div>';

		// prikaz blokov kot zavihke
		/*echo '<div class="blockSwitch">';
		$sql = sisplet_query("SELECT i.* FROM srv_if i, srv_branching b WHERE i.tab='1' AND i.tip='1' AND i.id=b.element_if AND b.ank_id='$this->anketa' ORDER BY b.parent, b.vrstni_red");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		$i = 0;
		while ($row = mysqli_fetch_array($sql)) {
			if ($i++ != 0) echo ' | ';
			$label = ($row['label'] == ''?$lang['srv_blok'].' ('.$row['number'].')':$row['label']);
			echo '<p>';
			echo '<a href="index.php?anketa='.$this->anketa.'&parent_if='.$row['id'].'">'.$label.'</a> ';
			echo '</p>';
		}
		echo '</div>';*/
    }

	/**
	* prikaz knjiznice v toolboxu na levi strani
	*
	*/
	function toolbox_library () {
		global $lang;

		$row = SurveyInfo::getInstance()->getSurveyRow();

		echo '<div id="toolbox_library" class="library">';

		if ($row['locked'] == 1) {

			echo '<p>';
			echo '<span class="sprites lock_close"></span> '.$lang['srv_anketa_locked_1'];
			echo '</p>';

		} else {

			echo '<div id="library_holder">';
			$l = new Library();
			$l->display();
			echo '</div>';
		}

		echo '</div>';
	}

	/**
    * hitre nastavitve - na dnu pri formi
    *
    */
    function toolbox_settings ($status1='none', $status2='none') {
		global $lang;
		global $admin_type;

		$row = Cache::srv_spremenljivka($row2['spr_id']);
		$rowA = SurveyInfo::getInstance()->getSurveyRow();

		SurveySetting::getInstance()->Init($this->anketa);
		$alertDiv = SurveySetting::getInstance()->getSurveyMiscSetting('srvtoolbox_form_alert');
		$emailDiv = SurveySetting::getInstance()->getSurveyMiscSetting('srvtoolbox_form_email');


		//OBVESCANJE
		$sqlAlert = sisplet_query("SELECT * FROM srv_alert WHERE ank_id = '$this->anketa'");
		if (!$sqlAlert)
			echo mysqli_error($GLOBALS['connect_db']);
		$rowAlert = mysqli_fetch_array($sqlAlert);

		$rowAlert['finish_other'] == 1 || ($rowAlert['finish_other_emails'] && $rowAlert['finish_other'] != 0) ? $checked = true : $checked = false;

		echo '<p style="margin: 6px 0;">';
		$alertDiv == 1 ? $obvescanje = 0 : $obvescanje = 1;
		echo '<a href="#" onClick="change_form_quicksettings(\'form_settings_obvescanje\');">';

		$img = ($status1 == 'none') ? ' class="faicon icon-blue plus"' : ' class="faicon icon-blue minus"';
		echo '<span '.$img.' id="obvescanje_switch" style="cursor:pointer;"></span> ';

		echo '<b>' . $lang['srv_alert_link_form'] . '</b></a>';
		echo '</p>';

		//echo '<span class="nastavitveSpan4" style="width: 100%;"><label>' . $lang['srv_alert_prejemnik'] . '</label></span><br />';
		$sas = new SurveyAdminSettings();

		echo '<div id="form_settings_obvescanje" class="form_bottom_settings" style="display: '.$status1.';">';

		// avtor ankete oz osebe z dostopom
		echo '<p class="whole"><input type="checkbox" name="alert_finish_author" id="alert_finish_author" value="1" onChange="quick_settings(\'' . $row2['spr_id'] . '\', this, \'finish_author\'); return false;"' . ($rowAlert['finish_author'] == 1 ? ' checked' : '') . '>';
		echo '<span id="label_alert_finish_author">';
		$sas->display_alert_label('finish_author',($rowAlert['finish_author'] == 1), true);
		echo '</span></p>';

		// posebej navedeni maili
		echo '<p class="whole"><input type="checkbox" name="alert_finish_other"  id="alert_finish_other"  value="1"' . ($checked ? ' checked' : '') . ' onchange="toggleStatusAlertOtherCheckbox(\'finish_other\'); quick_settings(\'' . $row2['spr_id'] . '\', this, \'finish_other\'); return false;"><label for="alert_finish_other">' . $lang['email_prejemniki'] . ($checked ? $lang['email_one_per_line'] : '' ) . '</label></p>';

		echo '<p id="alert_holder_finish_other_emails" '.($rowAlert['finish_other'] == 0 ? 'class="hidden"' : '' ).'>';
		echo '<label for="alert_finish_other_emails">' . $lang['email'] . ':</label>';
		echo '<textarea name="alert_finish_other_emails" id="alert_finish_other_emails" style="height:100px; width:60%; margin-left: 10px;" onBlur="quick_settings(\'' . $row2['spr_id'] . '\', this.value, \'finish_other_emails\');">' . $rowAlert['finish_other_emails'] . '</textarea>';
		echo '</p>';

		//respondent iz cms
		/*echo '<p><input type="checkbox" name="alert_finish_respondent_cms" id="alert_finish_respondent_cms" value="1" onChange="quick_settings(\'' . $row2['spr_id'] . '\', this, \'finish_respondent_cms\'); return false;" ' . ($rowAlert['finish_respondent_cms'] == 1 ? ' checked' : '') . '>';
		echo '<span id="label_alert_finish_respondent_cms">';
		$sas->display_alert_label('finish_respondent_cms',($rowAlert['finish_respondent_cms'] == 1), true);
		echo '</span></p>';*/

		//respondent
		echo '<p class="whole"><input type="checkbox"  class="enka-admin-custom" name="alert_finish_respondent" id="alert_finish_respondent" value="1" onChange="quick_settings(\'' . $row2['spr_id'] . '\', this, \'finish_respondent\'); return false;" ' . ($rowAlert['finish_respondent'] == 1 ? ' checked' : '') . '>';
        echo '<span class="enka-checkbox-radio "></span>';
		echo '<span id="label_alert_finish_respondent">';
		$sas->display_alert_label('finish_respondent',($rowAlert['finish_respondent'] == 1), true);
		echo '</span></p>';

		echo '<a style=" margin: 5px 0 5px 10px;" href="index.php?anketa=' . $this->anketa . '&a=alert" ><span class="strong">'.$lang['srv_detail_settings'].'</span></a>';
		echo '</div>';

		echo '<div id="clr" class="clr" ></div>';
    }

    /**
    * izrise linke za dodajanje demografskih vprasanj pri novi prazni anketi
    *
    */
    function demografija () {
		global $lang;

		echo '<div id="demografija">';
		echo '<form id="demografija-new">';

		echo '<h3>'.$lang['srv_head_demografska_vprasanja'].'</h3>';

		echo '<div class="left">';

		if ($lang['id'] == '1') {
			echo '<p><input type="checkbox" class="enka-admin-custom" name="demografija[]" onchange="demografija_new(\'XSPOL\');" '.($this->check_demografija_exists('XSPOL')?'checked':'').' value="XSPOL" id="XSPOL"><span class="enka-checkbox-radio"></span><label for="XSPOL">'.$lang['srv_demografija_spol'].'</label></p>';
			echo '<p><input type="checkbox" class="enka-admin-custom" name="demografija[]" onchange="demografija_new(\'XSTAR2a4\');" '.($this->check_demografija_exists('XSTAR2a4')?'checked':'').' value="XSTAR2a4" id="XSTAR2a4"><span class="enka-checkbox-radio"></span><label for="XSTAR2a4">'.$lang['srv_demografija_starost'].'</label></p>';
			echo '<p><input type="checkbox" class="enka-admin-custom" name="demografija[]" onchange="demografija_new(\'XZST1surs4\');" '.($this->check_demografija_exists('XZST1surs4')?'checked':'').' value="XZST1surs4" id="XZST1surs4"><span class="enka-checkbox-radio"></span><label for="XZST1surs4">'.$lang['srv_demografija_zakonski_stan'].'</label></p>';

			echo '</div><div class="left">';

			echo '<p><input type="checkbox" class="enka-admin-custom" name="demografija[]" onchange="demografija_new(\'XDS2a4\');" '.($this->check_demografija_exists('XDS2a4')?'checked':'').' value="XDS2a4" id="XDS2a4"><span class="enka-checkbox-radio"></span><label for="XDS2a4">'.$lang['srv_demografija_status'].'</label></p>';
			echo '<p><input type="checkbox" class="enka-admin-custom" name="demografija[]" onchange="demografija_new(\'XIZ1a2\');" '.($this->check_demografija_exists('XIZ1a2')?'checked':'').' value="XIZ1a2" id="XIZ1a2"><span class="enka-checkbox-radio"></span><label for="XIZ1a2">'.$lang['srv_demografija_izobrazba'].'</label></p>';
			echo '<p><input type="checkbox" class="enka-admin-custom" name="demografija[]" onchange="demografija_new(\'XLOKACREGk\');" '.($this->check_demografija_exists('XLOKACREGk')?'checked':'').' value="XLOKACREGk" id="XLOKACREGk"><span class="enka-checkbox-radio"></span><label for="XLOKACREGk">'.$lang['srv_demografija_lokacija'].'</label></p>';
			//echo '<p><input type="checkbox" name="demografija[]" value="'.Demografija::getInstance()->getSpremenljivkaID('XPODJPRIH').'" id="XPODJPRIH"> <label for="XPODJPRIH">'.$lang['srv_demografija_podjetja'].'</label></p>';
		}

		if ($lang['id'] == '2') {
			echo '<p><input type="checkbox" class="enka-admin-custom" name="demografija[]" onchange="demografija_new(\'XSEX\');" '.($this->check_demografija_exists('XSEX')?'checked':'').' value="XSEX" id="XSEX"><span class="enka-checkbox-radio"></span><label for="XSEX">'.$lang['srv_demografija_spol'].'</label></p>';
			echo '<p><input type="checkbox" class="enka-admin-custom" name="demografija[]" onchange="demografija_new(\'XAGE\');" '.($this->check_demografija_exists('XAGE')?'checked':'').' value="XAGE" id="XAGE"><span class="enka-checkbox-radio"></span><label for="XAGE">'.$lang['srv_demografija_starost'].'</label></p>';
			echo '<p><input type="checkbox" class="enka-admin-custom" name="demografija[]" onchange="demografija_new(\'XMRSTS\');" '.($this->check_demografija_exists('XMRSTS')?'checked':'').' value="XMRSTS" id="XMRSTS"><span class="enka-checkbox-radio"></span><label for="XMRSTS">'.$lang['srv_demografija_zakonski_stan'].'</label></p>';

			echo '</div><div class="left">';

			echo '<p><input type="checkbox" class="enka-admin-custom" name="demografija[]" onchange="demografija_new(\'XSTS\');" '.($this->check_demografija_exists('XSTS')?'checked':'').' value="XSTS" id="XSTS"><span class="enka-checkbox-radio"></span><label for="XSTS">'.$lang['srv_demografija_status'].'</label></p>';
			echo '<p><input type="checkbox" class="enka-admin-custom" name="demografija[]" onchange="demografija_new(\'XEDU\');" '.($this->check_demografija_exists('XEDU')?'checked':'').' value="XEDU" id="XEDU"><span class="enka-checkbox-radio"></span><label for="XEDU">'.$lang['srv_demografija_izobrazba'].'</label></p>';
			echo '<p><input type="checkbox" class="enka-admin-custom" name="demografija[]" onchange="demografija_new(\'XLOC\');" '.($this->check_demografija_exists('XLOC')?'checked':'').' value="XLOC" id="XLOC"><span class="enka-checkbox-radio"></span><label for="XLOC">'.$lang['srv_demografija_lokacija'].'</label></p>';			//echo '<p><input type="checkbox" name="demografija[]" value="'.Demografija::getInstance()->getSpremenljivkaID('XPODJPRIH').'" id="XPODJPRIH"> <label for="XPODJPRIH">'.$lang['srv_demografija_podjetja'].'</label></p>';
		}

		echo '</div>';
		echo '</form>';

		echo '</div>';

    }

    /**
    * Vrne ID bloka z demografijo
    *
    */
    function get_demografija_id () {
		global $lang;

		$sql = sisplet_query("SELECT i.* FROM srv_branching b, srv_if i WHERE b.ank_id = '$this->anketa' AND b.parent='0' AND i.id=b.element_if AND i.tip='1'");
		if (mysqli_num_rows($sql) == 1) {
			$row = mysqli_fetch_array($sql);
			if ($row['label'] == $lang['srv_demografija']) {
				return $row['id'];
			}
		}

		return 0;

    }

    /**
    * preveri, ce so v bloku z demografijo samo demografska vprasanja
    *
    */
    function check_only_demografija($id) {

		$sql = sisplet_query("SELECT element_spr FROM srv_branching WHERE parent = '$id'");
		while ($row = mysqli_fetch_array($sql)) {

			if ($row['element_spr'] > 0) {

				$sql1 = sisplet_query("SELECT sistem FROM srv_spremenljivka WHERE id = '$row[element_spr]'");
				$row1 = mysqli_fetch_array($sql1);
				if ( ! $row1['sistem']==1 )	// bomo rekl, da kr ce ni sistemska
					return false;

			} else {
				return false;
			}

		}

		return true;

    }

    /**
    * preveri, ce je izbrana variabla ze dodana kot demografija
    *
    * @param mixed $variable
    */
    function check_demografija_exists($variable) {

		$if_id = $this->get_demografija_id();

		$sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_branching b, srv_spremenljivka s WHERE b.ank_id='$this->anketa' AND b.element_spr=s.id AND b.parent='$if_id' AND s.variable='$variable'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		$row = mysqli_fetch_array($sql);

		if ($row['count'] > 0)
			return true;

		return false;
    }


    /**
    * nova branching struktura
    *
    */
    function branching_struktura () {
		global $lang;

		// naenkrat preberemo vse spremenljivke, da ne delamo queryja vsakic posebej
		Cache::cache_all_srv_spremenljivka($this->anketa, true);
		// enako za srv_branching
		Cache::cache_all_srv_branching($this->anketa, true);
		// cachiramo tudi srv_if
		Cache::cache_all_srv_if($this->anketa);
		// cache vseh spremenljivk
		//$this->find_all_spremenljivka();

		#$this->survey_type = SurveyAdmin::getSurvey_type($this->anketa);
		$this->survey_type = SurveyInfo::getInstance()->getSurveyColumn("survey_type");

		//$this->toolbox_nastavitve();
		
		
		// prikaz blokov kot zavihke	
		$sqlB = sisplet_query("SELECT i.* FROM srv_if i, srv_branching b WHERE i.tab='1' AND i.tip='1' AND i.id=b.element_if AND b.ank_id='$this->anketa' ORDER BY b.parent, b.vrstni_red");
		if (!$sqlB) echo mysqli_error($GLOBALS['connect_db']);
		
		if(mysqli_num_rows($sqlB) > 0){
			
			echo '<div class="blockSwitch">';
			
			// Zavihek VSI
			echo '<p>';
			echo '<span '.(!isset($_GET['parent_if']) ? ' class="bold"' : '').' style="text-transform:uppercase;"><a href="index.php?anketa='.$this->anketa.'">'.$lang['srv_vsi'].'</a></span> ';	
			echo '</p>';
		
			// Ostali zavihki
			while ($rowB = mysqli_fetch_array($sqlB)) {
				echo ' | ';
				$label = ($rowB['label'] == '' ? $lang['srv_blok'].' ('.$rowB['number'].')' : $rowB['label']);
				echo '<p>';
				echo '<span '.(isset($_GET['parent_if']) && $_GET['parent_if'] == $rowB['id'] ? ' class="bold"' : '').'><a href="index.php?anketa='.$this->anketa.'&parent_if='.$rowB['id'].'">'.$label.'</a></span> ';
				echo '</p>';
			}
			
			echo '</div>';
		}		

		
		echo '<ul class="first '.($this->locked?'locked':'').'">';

		$parent = 0;
        if ($_GET['parent_if'] != 0) $parent = (int)$_GET['parent_if'];

        // navaden prikaz
        if ($parent == 0) {

			// uvod - pri formi ga ni
			if ($this->survey_type != 1) {

				// napis uvod na začetku
				echo '<li id="droppable_0-0" class="nodrop">';
				echo '<span class="pb_on permanent"><span>'.$lang['srv_intro_page'].'</span></span>';
				echo '</li>';

				echo '<li id="droppable_0-0" class="nodrop">';
				echo '<span class="pb_off"></span>';
				echo '</li>';

				// Ce imamo slucajno GDPR preduvod
				if(GDPR::isGDPRSurvey($this->anketa)){
					
					$gdpr_settings = GDPR::getSurveySettings($this->anketa);

					if($gdpr_settings['1ka_template'] == '1'){				
						echo '<li class="spr">';
						if ($this->expanded)
							$this->gdpr_introduction();
						else
							echo $lang['srv_gdpr_survey_gdpr_1ka_template_title'];
						echo '</li>';
						
						echo '<li id="droppable_0-0" class="nodrop">';
						echo '<span class="pb_off"></span>';
						echo '</li>';
					}
				}

				echo '<li id="-1" class="spr">';
				if ($this->expanded)
					$this->introduction_conclusion(-1);
				else
					echo ''.$lang['srv_intro_label'].'';
				echo '</li>';
			}


            $sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_branching WHERE ank_id = '$this->anketa' AND parent = '0'");
			$row = mysqli_fetch_array($sql);
			$first = $row['count'];
            
			// prazen div - ko se ni nobenega vprasanja
			if ($first <= 0) {

				echo '<li id="droppable_0-0" class="drop empty_vrivanje" spr="0" if="0" endif="0">';
        
                echo '  <div class="empty_vrivanje_title">'.$lang['srv_new_survey_success2'].'</div>';
                
                echo '  <div class="empty_vrivanje_subtitle">';
                printf ($lang['srv_new_survey_success3'], 'index.php?anketa='.$this->anketa.'&a=branching&change_mode=1&what=toolbox&value=3');
                echo '  </div>';

                MobileSurveyAdmin::displayNoQuestions($this->anketa);

				echo '</li>';
			}


			if ($first > 0) {

				if ($this->survey_type != 1) {
					echo '<li id="droppable_0-0-2" class="nodrop" spr="0" if="0" endif="0">';
					echo '<span class="pb_off"></span>';
					echo '</li>';

					$first = $this->find_first_spr();
					echo '<li id="droppable_0-0" class="nodrop" spr="0" if="0" endif="0">';

					// Zaenkrat imamo vedno isti text za strani
					if ($first > 0){
						/*$gr = $this->getGrupa4Spremenljivka($first);*/
						$gr = $this->getGrupa4Spremenljivka($first);
						$naslov = $lang['srv_stran'].' '.$gr['vrstni_red'];
					}
					else
						$naslov = $lang['srv_stran'].' 1';

					echo '<span class="pb_on permanent"><span>'.$naslov.'</span></span>';
					echo '</li>';
				}

				echo '<li id="droppable_0-0-0" class="drop" spr="0" if="0" endif="0">';
				echo '<span class="pb_off"></span>';
				echo '</li>';

			}

	        foreach (Cache::srv_branching_parent($this->anketa, $parent) AS $k => $rowQ) {
	            $this->display_element($rowQ['element_spr'], $rowQ['element_if']);
	        }

			// zakljucek - pri formi ga ni
			if ($this->survey_type != 1) {

				// napis zakljucek prikazemo tukaj in ne za zadnjo spremenljivko da je lepse
				echo '<li id="droppable_'.'0'.'-'.'0'.'-1" class="nodrop" spr="'.'0'.'" if="0" endif="0">';
				echo '<span class="pb_on permanent"><span>'.$lang['srv_end_page'].'</span></span>';
				echo '</li>';

				echo '<li id="droppable_'.'0'.'-'.'0'.'-0" class="nodrop" spr="'.'0'.'" if="0" endif="0">';
				echo '<span class="pb_off"></span>';
				echo '</li>';

				echo '<li id="-2" class="spr">';
				if ($this->expanded)
					$this->introduction_conclusion(-2);
				else
					echo ''.$lang['srv_end_label'].'';
				echo '</li>';
			}

		
        } 
        // prikaz samo bloka - zavihek
        else {
			$this->display_if($parent);
		}

		echo '</ul>';


		// Pri formi na dnu izpisemo dodaten text
		$this->showVprasalnikBottom();

        
		if (isset($_GET['spr_id']) && $_GET['spr_id'] > 0) {
			?>
			<script>
				$(function() {
					vprasanje_fullscreen(<?=(int)$_GET['spr_id']?>);
				});
			</script>
			<?
		}

		// ZZa redirekte z preverjanjem podvojenosti imen variabel (pride iz zavihka Testiranje)
		if (isset($_GET['checkDuplicate']) && $_GET['checkDuplicate'] = '1') {
			?>
			<script>
				$(function() {
					check_pogoji();
				});
			</script>
			<?
		}
	}

    function display_element ($element_spr, $element_if) {

        // Eden ne sme biti enak 0!
        if($element_spr == 0 && $element_if == 0)
            return;

		if ($element_spr > 0)
			$this->display_spremenljivka($element_spr);
		else
            $this->display_if($element_if);
    }

    function display_spremenljivka ($spremenljivka) {
		global $lang;

		$row = SurveyInfo::getInstance()->getSurveyRow();
		#$this->survey_type = SurveyAdmin::getSurvey_type($this->anketa);
		$this->survey_type = SurveyInfo::getInstance()->getSurveyColumn("survey_type");

        if ($row['flat'] == 0)
			$zamik = ( $this->level($spremenljivka,0) > 0 ? ' style="padding-left:'.(10+$this->level($spremenljivka,0)*20).'px"' : '' );
        else
        	$zamik = '';

		$row = Cache::srv_spremenljivka($spremenljivka);
		$row1 = Cache::srv_branching($spremenljivka, 0);

		echo '<li id="branching_'.$spremenljivka.'" class="spr'.' '.($row['tip']==22?' calculation':'').' '.($row['tip']==25?' quota':'').'" '.$zamik.' tip="'.$row['tip'].'" signature="'.$row['signature'].'">';
		if ($this->expanded) {
			$this->vprasanje($spremenljivka);
		} else {
			$this->spremenljivka_name($row['id'], $row['naslov'], $row['variable'], $row['visible'], $row['sistem']);
		}
		echo '</li>';

		//echo '<li id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'" class="drop" spr="'.$row['id'].'" if="0" endif="0">';

        if ($this->pagebreak($spremenljivka)) {
            $gr = $this->getGrupa4Spremenljivka($this->find_next_spr($spremenljivka));
            if ($gr['id'] > 0) {
            	echo '<li id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'-2" class="drop" spr="'.$row['id'].'" if="0" endif="0" drop="2">';
            	echo '<span class="pb_off"></span>';
            	echo '</li>';

            	// ++ zadnji pagebreak prikazan izven ifa //
            	// ce je spremenljivka zadnja v IFu, ne prikazemo PB, ker ga bomo za ENDIFom
            	$rows = Cache::srv_branching($spremenljivka, 0);
            	if ( $this->find_last_in_if($rows['parent']) != $spremenljivka ) {
            		echo '<li id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'-1" class="nodrop" spr="'.$row['id'].'" if="0" endif="0">';
					//echo '<span class="pb_on" title="'.$lang['srv_rem_pagebreak'].'"><span>'.$gr['naslov'].'</span></span>';
					echo '<span class="pb_on" title="'.$lang['srv_rem_pagebreak'].'"><span>'.$lang['srv_stran'].' '.$gr['vrstni_red'].'</span></span>';
					echo '</li>';
					echo '<li id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'-0" class="drop" spr="'.$row['id'].'" if="0" endif="0">';
					echo '<span class="pb_off"></span>';
					echo '</li>';
				}
				// -- zadnji pagebreak prikazan izven ifa //
			} elseif($this->survey_type != 1) {
				echo '<li id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'-2" class="drop" spr="'.$row['id'].'" if="0" endif="0" drop="2">';
	            echo '<span class="pb_off"></span>';
	            echo '</li>';

				/*echo '<li id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'-1" class="nodrop" spr="'.$row['id'].'" if="0" endif="0">';
				echo '<span class="pb_on">'.$lang['srv_end_label'].'</span>';
				echo '</li>';

         		echo '<li id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'-0" class="nodrop" spr="'.$row['id'].'" if="0" endif="0">';
				echo '<span class="pb_off"></span>';
				echo '</li>';*/
			}

        } elseif ($spremenljivka == $this->find_last_spr() && $this->survey_type != 1) {	// zadnja spremenljivka - zakljucek
			echo '<li id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'-2" class="drop" spr="'.$row['id'].'" if="0" endif="0" drop="2">';
            echo '<span class="pb_off"></span>';
            echo '</li>';

			/*echo '<li id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'-1" class="nodrop" spr="'.$row['id'].'" if="0" endif="0">';
			echo '<span class="pb_on">'.$lang['srv_end_label'].'</span>';
			echo '</li>';

         	echo '<li id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'-0" class="nodrop" spr="'.$row['id'].'" if="0" endif="0">';
			echo '<span class="pb_off"></span>';
			echo '</li>';*/

		} elseif ($this->survey_type != 1) { //pri formi ne dovolimo dodajanja page-breakov

			// tuki pustimo, da se PB lahko dodaja za spremenljivko in za ifom (pol se prikaze za ifom)
			// -- v zadnji spremenljivki za ifom ne prikazemo dodajanja
			$rows = Cache::srv_branching($spremenljivka, 0);
            if ( $this->find_last_in_if($rows['parent']) != $spremenljivka ) {
				echo '<li id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'" class="drop" spr="'.$row['id'].'" if="0" endif="0">';
				echo '<span class="pb_new" title="'.$lang['srv_add_pagebreak'].'"></span>';
				echo '</li>';
			} else { // zadnji spremenljivki v ifu izpisemo PB izven ifa (da je bolj pregledno in lepse)
				echo '<li id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'" class="drop" spr="'.$row['id'].'" if="0" endif="0">';
				echo '<span class="pb_off"></span>';
				echo '</li>';
			}
        } elseif ($this->survey_type == 1) { // forma
			echo '<li id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'" class="drop" spr="'.$row['id'].'" if="0" endif="0">';
			echo '</li>';
		}

		//echo '</li>';

    }

    function display_if_label($if) {
		global $lang;

		$rowb = Cache::srv_if($if);

		echo '<div class="if_content">';
		echo '<span class="conditions_display">';

		if ($rowb['tip'] == 0) {
	        $this->conditions_display($if);
		} elseif ($rowb['tip'] == 1) {
           echo '<strong class="clr_bl">BLOCK</strong> <span class="colorblock">('.$rowb['number'].')</span>'.($rowb['enabled']==2?' FALSE ':'').($rowb['label']!=''?' <span class="if_comment">( '.$rowb['label'].' )</span>':'').'';
	    } elseif ($rowb['tip'] == 2) {
			$this->loop_display($if);
	    }

	    echo '</span>';
	    echo '</div>';

    }

    function display_if ($if) {
		global $lang;

		$row = SurveyInfo::getInstance()->getSurveyRow();
        $rowb = Cache::srv_if($if);

        if ($row['flat'] == 0)
			$zamik = ( $this->level(0,$if) > 0 ? ' style="padding-left:'.(10+$this->level(0,$if)*20).'px"' : '' );
        else
        	$zamik = '';


        echo '<li id="branching_if'.$if.'" class="'.($rowb['tip']==0?'if':($rowb['tip']==1?'block':'loop')).'"'.$zamik.'>';

        // plusminus
        if ($row['flat'] == 0)
            echo '<a class="pm '.($rowb['collapsed']==1 && $row['flat']==0?'plus':'minus').'"></a>';

        $this->display_if_label($if);

        echo '</li>';
            

		echo '<ul id="if_'.$if.'"'.($rowb['collapsed']==1 && $row['flat']==0?' style="display:none"':'').'>';
		$this->display_if_content($if);
		echo '</ul>';


		$row1 = Cache::srv_branching(0, $if);

		// ++ zadnji pagebreak prikazan izven ifa //
		$spr = $this->find_last_in_if($if);
		if($spr > 0)
            $rows = Cache::srv_branching($spr, 0);
            
		$rowi = cache::srv_branching(0, $if);

		// preverimo, da ni na zadnjem mestu ifa se en if (ker potem se 2x izpise PB)
		$sqle = sisplet_query("SELECT ank_id, parent, element_spr, element_if FROM srv_branching WHERE parent='{$rowi['parent']}' AND vrstni_red>'{$rowi['vrstni_red']}' AND ank_id='$this->anketa'");


		if ($rows['pagebreak'] == 1 AND (mysqli_num_rows($sqle)>0 || $rowi['parent']==0) ) {

			$gr = $this->getGrupa4Spremenljivka($this->find_next_spr($spr));

			if ($gr['id'] > 0) {

				echo '<li './*id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'-2"*/' class="nodrop" spr="'.$spr.'" if="0" endif="0">';
				echo '<span class="pb_off"></span>';
				echo '</li>';
				echo '<li './*id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'-1"*/' class="nodrop" spr="'.$spr.'" if="0" endif="0">';
				echo '<span class="pb_on" title="'.$lang['srv_rem_pagebreak'].'">'.$lang['srv_stran'].' '.$gr['vrstni_red'].'</span>';
				echo '</li>';
			}

		}

		// zadnji spremenljivki v ifu, tudi dodamo PB izven pagebreaka (da se doda, tam kjer se potem prikaže)
		if ($rows['pagebreak'] == 0 AND (mysqli_num_rows($sqle)>0 || $rowi['parent']==0) ) {
			echo '<li id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'" class="drop" spr="0" if="'.$if.'" endif="1" spr_pb="'.$spr.'">';
			echo '<span class="pb_new" title="'.$lang['srv_add_pagebreak'].'"></span>';
	        echo '</li>';
		} else {
			echo '<li id="droppable_'.$row1['parent'].'-'.$row1['vrstni_red'].'" class="drop" spr="0" if="'.$if.'" endif="1">';
	        echo '</li>';
		}

		// -- zadnji pagebreak prikazan izven ifa //
    }

    function display_if_content ($if) {
		global $lang;

		$row = SurveyInfo::getInstance()->getSurveyRow();

        $rowb = Cache::srv_if($if);

        if ($row['flat'] == 0) {
            $zamik = ($this->level(0, $if) > 0 ? ' style="padding-left:' . (10 + $this->level(0, $if) * 20) . 'px"' : '');
            $zaklepaj = ($this->level(0, $if) > 0 ? 'margin-left:-' . (10 + $this->level(0, $if) * 20) . 'px;width:' . (15 + $this->level(0, $if) * 20) . 'px;' : '');
        }
		else {
            $zamik = '';
            $zaklepaj = '';
        }

	    echo '<li id="droppable_'.$if.'-0" class="drop" spr="0" if="'.$if.'" endif="0">';
        echo '</li>';

        foreach (Cache::srv_branching_parent($this->anketa, $if) AS $k => $row1) {
            $this->display_element($row1['element_spr'], $row1['element_if']);
        }

		echo '<li id="branching_endif'.$if.'" class="'.($rowb['tip']==0?'endif':($rowb['tip']==1?'endblock':'endloop')).'"'.$zamik.'>';
	    echo '<span class="'.($rowb['tip']==0?' clr_if':($rowb['tip']==1?' clr_bl':' clr_lp')).'"><strong>'.($rowb['tip']==0?'ENDIF':($rowb['tip']==1?'ENDBLOCK':'ENDLOOP')).'</strong></span> <span class="'.($rowb['tip']==0?'colorif':($rowb['tip']==1?'colorblock':'colorloop')).'">('.$rowb['number'].')</span>'."\n\r";

		// Dodajanje komentarjev na if/blok
		if ($this->displayKomentarji !== false) {
			$this->if_komentarji($if, $rowb['tip']);
		}

        echo '</li>';
    }

   	/**
   	* prikaze spremenljivko pri skrcenem nacinu
   	*
   	* @param mixed $spremenljivka
   	* @param mixed $naslov
   	* @param mixed $variable
   	* @param mixed $visible
   	* @param mixed $sistem
   	*/
    function spremenljivka_name ($spremenljivka, $naslov=null, $variable=null, $visible=1, $sistem=0) {
		global $lang;

		$row = Cache::srv_spremenljivka($spremenljivka);

		$tip = $row['tip'];
        $naslov = $row['naslov'];
        $variable = $row['variable'];
        $visible = $row['visible'];
        $sistem = $row['sistem'];
    	$dostop = $row['dostop'];

		// Barva vprašanja je privzeto modra, če pa je sistemsko ali skrito pa je rdeča
		$spanred = ($visible == 0 || $sistem == 1 || $dostop != 4 ) ? ' <span class="red">' : '';

		// Kvota
		if($tip == 25){
			$SQ = new SurveyQuotas($this->anketa);
			echo '<span class="quotavariable">('.$variable.')</span> '.$SQ->quota_display(-$spremenljivka).' <span class="spr_comment">( '.$lang['srv_vprasanje_tip_long_'.$row['tip']].' )</span>';
		}
		// Kalkulacija
		else if($tip == 22){
			echo '<span class="calculationvariable">('.$variable.')</span> '.$this->calculations_display(-$spremenljivka).' <span class="spr_comment">( '.$lang['srv_vprasanje_tip_long_'.$row['tip']].' )</span>';
		}
		// Navadne spremenljivke
		else{	
			echo '<span class="colorvariable">('.$variable.')</span> '.$spanred.skrajsaj(strip_tags($naslov), 80).($spanred!=''?'</span>':'').' <span class="spr_comment">( '.$lang['srv_vprasanje_tip_long_'.$row['tip']].' )</span>';	
		} 
    }

    private $Survey = null;

    /**
	* @desc prikaze vprasanje pri razsirjenem nacinu
	* dodal nov argument, zaradi pravilnega prikazovanja prevoda sliderjev
	*/
	function vprasanje($spremenljivka, $prevajanje = false) {	
		global $lang;
		global $lang1;
		global $site_path;
		global $admin_type;
		global $global_user_id;
		global $site_url;

		$row = Cache::srv_spremenljivka($spremenljivka);
		
		$this->prevajanje = $prevajanje;
		
		if ( $this->lang_id != null ) {
			include_once('../../main/survey/app/global_function.php');
			if (empty($this->Survey->get))
				$this->Survey = new \App\Controllers\SurveyController(true);

			save('lang_id', $this->lang_id);

			$rowl = \App\Controllers\LanguageController::srv_language_spremenljivka($spremenljivka);
			if (strip_tags($rowl['naslov']) != '') $row['naslov'] = $rowl['naslov'];
			if (strip_tags($rowl['info']) != '') $row['info'] = $rowl['info'];
			if ($rowl['vsota'] != '') $row['vsota'] = $rowl['vsota'];
		}

		$this->survey_type = SurveyInfo::getInstance()->getSurveyColumn("survey_type");

		// Ce je vprasanje ali anketa zaklenjena (vprasanje ni nikoli zaklenjeno za admine, managerje in avtorja ankete)
		$author = SurveyInfo::getInstance()->getSurveyColumn("insert_uid");
		$question_locked = ($row['locked'] == 1 && $admin_type != 0 && $admin_type != 1 && $global_user_id != $author) ? true : false;
		$locked = ($this->locked || $question_locked) ? true : false;

		// v atribut
		echo '<div id="spremenljivka_content_' . $spremenljivka . '" class="spremenljivka_content spr_normalmode '.($row['orientation']==0?'orientation_ob':'').($row['orientation']==2?'orientation_pod':'').' '.($question_locked?' question_locked':'').'" skala="'.$row['skala'].'" signature="'.$row['signature'].'" spr_id="'.$spremenljivka.'" spr_orientation="'.$row['orientation'].'" spr_enota="'.$row['enota'].'" tip="'.$row['tip'].'">';

		// nalozimo parametre spremenljivke
		$spremenljivkaParams = new enkaParameters($row['params']);

		// Ce prikazujemo urejanje variable
		$show_variable_inline = $spremenljivkaParams->get('grid_var') == '1' ? ' style="display:block;"' : ' style="display:none;"';
		$show_variable_row = $spremenljivkaParams->get('grid_var') == '1' ? ' style="display:auto;"' : ' style="display:none;"';		

		if ($this->branching == 0 )
			$movable = ' movable';
		else
			$movable = '';

		// <-- Zgornja vrstica pri editiranju vprasanj ---
		echo '<div class="spremenljivka_settings' . $movable .'" title="'.$lang['edit3'].' / '.$lang['srv_movespremenljivko'].'">';
		echo '  <div class="variable_name">' . $row['variable'] . ($row['label']!=''?' - <i>'.$row['label'].'</i>':'') .'</div>';


		echo '<div id="spr_settings" class="spr_settings" >';
		$string = '';
		// statusi: reminder, timer, in še kaj
		if ($row['sistem'] == 1)
			$string = $string . $lang['srv_system_text'] . '&nbsp;|&nbsp;';

		if ($row['visible'] == 0)
			$string .= $lang['srv_hidden_text'] . '&nbsp;|&nbsp;';

		if ($row['dostop'] != 4) {
			$string = $string . $lang['srv_visible_dostop'].' ';
			switch ($row['dostop']) {
				case 3: $string .= strtolower($lang['see_registered']);
				break;
				case 2: $string .= strtolower($lang['see_member']);
				break;
				case 1: $string .= strtolower($lang['see_manager']);
				break;
				case 0; $string .= strtolower($lang['see_admin']);
				break;
			}
			$string .= '&nbsp;|&nbsp;';
		}

		if ($row['reminder'] > 0) {
			if ($row['reminder'] == 1) {
				//				echo '<img src="img_'.$this->skin.'/reminder_soft.png" alt="'.$lang['srv_reminder_soft'].'" />';
				$string = $string . $lang['srv_reminder_soft'];
			} else {
				//				echo '<img src="img_'.$this->skin.'/reminder_hard.png" alt="'.$lang['srv_reminder_hard'].'" />';
				$string = $string . $lang['srv_reminder_hard'];
			}
			$string = $string . '&nbsp;|&nbsp;';
		}

		$sqlv = sisplet_query("SELECT spr_id, if_id FROM srv_validation WHERE spr_id = '$spremenljivka'");
		if (mysqli_num_rows($sqlv) > 0) {
			$string = $string . $lang['srv_validation'];
			$string = $string . '&nbsp;|&nbsp;';
		}

		if ($row['timer'] > 0) {
			$string = $string . $lang['srv_timer_on_time'];
			$string = $string . (substr(bcdiv($row['timer'], 60), 0, 4)) . '' . $lang['srv_minutes'] . ' ';
			$string = $string . (bcmod($row['timer'], 60)) . '' . $lang['srv_seconds'] . '';
			$string = $string . '&nbsp;|&nbsp;';
		}
		//izrišemo še ostale statuse: statistika, orientacija, sortiranje
		if ($row['tip'] <= 3 && $row['stat'] && $this->survey_type != 0) {
			$string = $string . $lang['srv_stat_on'] . '&nbsp;|&nbsp;';
		}


        // Status orentacije - navadna vprasanja
        if((in_array($row['tip'], array('1', '2', '21', '7', '8')) && $row['orientation'] != '1') || ($row['tip'] == '3' && $row['orientation'] == '1')){
            $string .= $this->getVprasanjeOrientationString($row['tip'], $row['orientation']) . '&nbsp;|&nbsp;';
        }
        // Status orentacije - tabele
        elseif(in_array($row['tip'], array('6', '16')) && $row['enota'] != '0'){
            $string .= $this->getVprasanjeOrientationString($row['tip'], $row['enota']) . '&nbsp;|&nbsp;';
        }


		if ($row['tip'] == 1 && $row['hidden_default'] == 1) {
			$string = $string . $lang['srv_potrditev'] . '&nbsp;|&nbsp;';
		}

		if ($row['random']) {
			$arrayRandomText = array (
			0 => $lang['srv_random_off'],
			1 => $lang['srv_random_on'],
			2 => $lang['srv_sort_asc'],
			3 => $lang['srv_sort_desc']
			);

			$string = $string . $arrayRandomText[$row['random']] . '&nbsp;|&nbsp;';
		}
		//skriti checkboxi
		if ($row['checkboxhide'] != 0)
			$string = $string . $lang['srv_checkboxhide_disabled'] . '&nbsp;|&nbsp;';

		// Kljucavnica ce je vprasanje zaklenjeno
		if($question_locked){
			echo '<div class="lock_holder"><span class="sprites lock_close"></sprites></div>';
		}
		// Ce je zaklenjeno ampak ga lahko ureja ker je admin ali avtor
		elseif($row['locked']){
			$string = $string . $lang['srv_locked_text'] . '&nbsp;|&nbsp;';
		}

		// Ce je onemogoceno vprasanje
		$disabled_vprasanje = $spremenljivkaParams->get('disabled_vprasanje') ? $spremenljivkaParams->get('disabled_vprasanje') : 0;
		if($disabled_vprasanje){
			$string = $string . $lang['srv_disabled_text'] . '&nbsp;|&nbsp;';
		}

		//zbrisemo zadnji "|" iz niza
		$string = substr($string, 0, -7);
		$string = '<span class="red">' . $string . '</span>';
		echo $string;

		echo '</div>';

		echo '</div>'; // - spremenljivka_settings
		// --- Zgornja vrstica pri editiranju vprasanj -->

		// pri multigridu ne pustimo spremembe orientacije
		if ( ($row['orientation'] == 0 || $row['orientation'] == 2) && $row['tip'] != '6') {
			$cssFloat = ' floatLeft';
			$divClear = '';
			if ($row['orientation'] == 2) {
				# pri vodoravni orientaciji z prelomom vrstice
				$line_break = "<br/>";
			}
		} else {
			$cssFloat = '';
			$divClear = '<div id="clr" class="clr" ></div>';
		}

		// kalkulacija
		if ($row['tip'] == 22) {
			$row['naslov'] = ''.$this->calculations_display(-$spremenljivka).' <span class="spr_comment">( '.$lang['srv_vprasanje_tip_long_'.$row['tip']].' )</span>';
		}
		// Kvota
		else if($row['tip'] == 25){
			$SQ = new SurveyQuotas($this->anketa);
			$row['naslov'] = ''.$SQ->quota_display(-$spremenljivka).' <span class="spr_comment">( '.$lang['srv_vprasanje_tip_long_'.$row['tip']].' )</span>';
        }
        
        // Inline ifi so disablani ce nimamo ustreznega paketa
        $userAccess = UserAccess::getInstance($global_user_id);

		echo '<div id="spremenljivka_contentdiv' . $spremenljivka . '" class="content_div_normalmode">';

		if ( in_array($row['tip'], array(1,2,6,16,19,20)) ) {
			if ($row['enota'] != 10 && $row['orientation'] != 10){
				echo '<div class="add-variable tip_'.$row['tip'].'"><a href="#" onclick="vprasanje_fullscreen(\''.$spremenljivka.'\'); return false;" title="'.$lang['srv_novavrednost'].'"><span class="faicon add small"></span> '.$lang['srv_novavrednost'].'</a></div>';
				
                MobileSurveyAdmin::displayAddQuestionCategory($this->anketa, $spremenljivka, $row['tip']);
			}
		}

		// kalkulacija
		if ($row['tip'] == 22) {
			echo '<div class="naslov '.($row['orientation']==0?'floatLeft':'').' calculation">';
			echo $row['naslov'];
			echo '</div>';
		}
		// Kvota
		elseif($row['tip'] == 25){
			echo '<div class="naslov '.($row['orientation']==0?'floatLeft':'').' quota">';
			echo $row['naslov'];
			echo '</div>';
		}
        // GDPR vprasanje in prevajanje - prevedemo v anglescino
        elseif($prevajanje && $row['variable'] == 'gdpr'){

            // nastavimo na jezik za respondentov vmesnik
            $language_id_bck = $lang['id'];
            $file = ($this->lang_id == '1') ? '../../lang/1.php' : '../../lang/2.php';
            @include($file);

            $gdpr_naslov = GDPR::getSurveyIntro($this->anketa);

            echo '<div class="naslov '.($row['orientation']==0?'':'').' naslov_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" spr_id="'.$row['id'].'" '.(strpos($row['naslov'], $lang['srv_new_vprasanje'])!==false || strpos($row['naslov'], $lang1['srv_new_vprasanje'])!==false || $this->lang_id!=null ? ' default="1"':'').'>';
			echo $gdpr_naslov;
			echo '</div>';

            // nastavimo nazaj na admin jezik
            $file = '../../lang/'.$language_id_bck.'.php';
            @include($file);
        }
		else{
			echo '<div class="naslov '.($row['orientation']==0?'':'').' naslov_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" spr_id="'.$row['id'].'" '.(strpos($row['naslov'], $lang['srv_new_vprasanje'])!==false || strpos($row['naslov'], $lang1['srv_new_vprasanje'])!==false || $this->lang_id!=null ? ' default="1"':'').'>';
			echo $row['naslov'];
			echo '</div>';
		}
		
		if ($row['info'] != '')
			echo '<div class="spremenljivka_info info_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" spr_id="'.$row['id'].'" '.($row['info']==$lang['note'] || $row['info']==$lang1['note'] || $this->lang_id!=null ? ' default="1"':'').'>' . $row['info'] . '</div>';

		echo '<span class="faicon edit-vprasanje icon-as_link display_editor" onclick="inline_load_editor(this); return false;"></span>';

		if ($_POST['info'] == $lang['note']) { //ce v opombi je default besedilo ("Opomba")
				?><script> $('#spremenljivka_content_<?=$spremenljivka?> div.spremenljivka_info.info_inline').focus(); </script><? 
		}
		
		if ((($_POST['info'] != $lang['note']) || ($_POST['info'] != '')) && !$prevajanje){	//ce v opombi ni default besedila ("Opomba") ali opomba ni prazna - in ce ne gre za prevajanje (drugace ne prikaze prevedene opombe)
		
			if($row['tip'] == 2 && $row['orientation'] == 6){//ce je kategorije - vec odgovorov in postavitev je selectbox, spremeni opombo
				$s = sisplet_query("SELECT info FROM srv_spremenljivka WHERE id = '$spremenljivka'");
				$r = mysqli_fetch_array($s);
				
				if($r['info'] == $lang['srv_info_checkbox']){
					echo'
						<script>
							$("#spremenljivka_content_'.$spremenljivka.' div.spremenljivka_info.info_inline").html("'.$lang['srv_info_selectbox'].'");
						</script>
					';
				}
				else{
					echo'
						<script>
							$("#spremenljivka_content_'.$spremenljivka.' div.spremenljivka_info.info_inline").html("'.$r['info'].'");
						</script>
					';
				}
				?><script> $('#spremenljivka_content_<?=$spremenljivka?> div.spremenljivka_info.info_inline').blur(); </script><?
			}
			elseif(($row['tip'] == 2 && $row['orientation'] != 6) && ($row['tip'] == 2 && $row['orientation'] != 8)){//ce je kategorije - vec odgovorov in postavitev ni selectbox in tabela da/ne, pusti default opombo			
				$s = sisplet_query("SELECT info FROM srv_spremenljivka WHERE id = '$spremenljivka'");
				$r = mysqli_fetch_array($s);
				
				if($r['info'] == $lang['srv_info_selectbox']){				
					echo'
						<script>
							$("#spremenljivka_content_'.$spremenljivka.' div.spremenljivka_info.info_inline").html("'.$lang['srv_info_checkbox'].'");
						</script>
					';
				}
				else{
					echo'
						<script>
							$("#spremenljivka_content_'.$spremenljivka.' div.spremenljivka_info.info_inline").html("'.$r['info'].'");
						</script>
					';	
				}
				?><script> $('#spremenljivka_content_<?=$spremenljivka?> div.spremenljivka_info.info_inline').blur(); </script><?
			}
			
			if($row['tip'] == 16 && $row['enota'] == 6){//ce je tabela - vec odgovorov in postavitev je selectbox, spremeni opombo
				$s = sisplet_query("SELECT info FROM srv_spremenljivka WHERE id = '$spremenljivka'");
				$r = mysqli_fetch_array($s);
				
				if($r['info'] == $lang['srv_info_checkbox']){
					echo'
						<script>
							$("#spremenljivka_content_'.$spremenljivka.' div.spremenljivka_info.info_inline").html("'.$lang['srv_info_selectbox'].'");
						</script>
					';
				}
				else{
					echo'
						<script>
							$("#spremenljivka_content_'.$spremenljivka.' div.spremenljivka_info.info_inline").html("'.$r['info'].'");
						</script>
					';
				}
				?><script> $('#spremenljivka_content_<?=$spremenljivka?> div.spremenljivka_info.info_inline').blur(); </script><?
			}
			elseif($row['tip'] == 16 && $row['enota'] != 6){//ce je tabela - vec odgovorov in postavitev ni selectbox, pusti default opombo			
				$s = sisplet_query("SELECT info FROM srv_spremenljivka WHERE id = '$spremenljivka'");
				$r = mysqli_fetch_array($s);
				
				if($r['info'] == $lang['srv_info_selectbox']){				
					echo'
						<script>
							$("#spremenljivka_content_'.$spremenljivka.' div.spremenljivka_info.info_inline").html("'.$lang['srv_info_checkbox'].'");
						</script>
					';
				}
				else{
					echo'
						<script>
							$("#spremenljivka_content_'.$spremenljivka.' div.spremenljivka_info.info_inline").html("'.$r['info'].'");
						</script>
					';	
				}
				?><script> $('#spremenljivka_content_<?=$spremenljivka?> div.spremenljivka_info.info_inline').blur(); </script><?
			}

		}	

		echo '<div id="variable_holder" class="variable_holder '.($this->lang_id==null?'allow_new':'').'"><!-- variable holder -->';

		// radio, checkbox, select
		if ($row['tip'] <= 3) {

            if ($row['tip'] == 3 || $row['orientation'] == 6) 
                echo '<div class="edit_mode '.($this->lang_id==null?'allow_new':'').'">';

			$orderby = "ORDER BY vrstni_red" ;

			$sql1 = sisplet_query("SELECT id, naslov, variable, other, if_id, hidden FROM srv_vrednost WHERE spr_id='$row[id]' AND vrstni_red>0 $orderby");


			$spremenljivkaParams = new enkaParameters($row['params']);
			$stolpci = ($spremenljivkaParams->get('stolpci') ? $spremenljivkaParams->get('stolpci') : 1);

			//if($row['tip'] == 1 && $row['orientation'] == 6){
			if(($row['tip'] == 1 && $row['orientation'] == 6) || ($row['tip'] == 2 && $row['orientation'] == 6)){			
				?>
				<script>
					$(document).ready(function(){
							$("#spremenljivka_contentdiv<?=$spremenljivka?>").mouseleave(function(){//ko z misko zapustimo obmocje editiranja vprasanja
								selectbox_dynamic_size(<?=$spremenljivka?>, '<?=$lang['srv_select_box_vse']?>'); //poklici funkcijo za dinamicno urejanje stevila vidnih odgovorov v seznamu							
							});
					});					
				</script>
				<?
			}			
			if(($row['tip'] == 1 && $row['orientation'] != 6) || ($row['tip'] == 2 && $row['orientation'] != 6)){			
				?>
				<script>
					$(document).ready(function(){
							$("#spremenljivka_contentdiv<?=$spremenljivka?>").mouseleave(function(){//ko z misko zapustimo obmocje editiranja vprasanja
								selectbox_dynamic_size_other(<?=$spremenljivka?>, '<?=$lang['srv_checkbox_max_limit']?>', '<?=$lang['srv_checkbox_min_limit']?>'); //poklici funkcijo za dinamicno urejanje stevila vidnih odgovorov v seznamu, ko postavitev ni seznam						
							});
					});					
				</script>
				<?
			}
			
			//ureditev dinamicnega urejanja omejitve minimalnega in maksimalnega stevila izbranih checkbox-ov
			if(($row['tip'] == 2)){			
				?>
				<script>
					$(document).ready(function(){
							$("#spremenljivka_contentdiv<?=$spremenljivka?>").mouseleave(function(){//ko z misko zapustimo obmocje editiranja vprasanja
								checkbox_limit_dropdown_size(<?=$spremenljivka?>, '<?=$lang['no']?>'); //poklici funkcijo za dinamicno urejanje omejitve minimalnega in maksimalnega stevila izbranih checkbox-ov						
								
							});
					});					
				</script>
				<?
			}			
			//ureditev dinamicnega urejanja omejitve minimalnega in maksimalnega stevila izbranih checkbox-ov - konec
			
			if (($row['tip'] == 1 && $row['orientation'] == 8) || ($row['tip'] == 2 && $row['orientation'] == 8)){	//drag-drop
			
				$sql1 = sisplet_query("SELECT id, naslov, hidden, other, if_id FROM srv_vrednost WHERE spr_id = '$spremenljivka' AND vrstni_red>0 ORDER BY vrstni_red");

				//izracun visine
				$num = mysqli_num_rows($sql1);
				$size = $num * 50;

				//zaslon razdelimo na dva dela - izris leve strani
				echo '<div id="half" class="dropzone '.($this->lang_id==null?'allow_new':'').'" style="width: 50%; min-height:' . $size . 'px; float: left; border-right: 1px solid black;">';

				while ($row1 = mysqli_fetch_array($sql1)) {
					
					
					if ($this->lang_id != null) {
						save('lang_id', $this->lang_id);
						$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
						if ($naslov != '') $row1['naslov'] = $naslov;
					}

					//preverimo dolzino niza -> max == 20
					$length = strlen($row1['naslov']);
					?>
					<script>
						$(document).ready(function(){
							

							DraggableAdmin(<?=$row1['id']?>);
							$("#vre_id_<?=$row1['id']?>")
								.mousemove(function(){ //ko se miska premakne
									DraggableAdmin(<?=$row1['id']?>);
							})
                        });
					</script>
					<?
					//******************
					// Slika kot odgovor
						$quickImage = ($spremenljivkaParams->get('quickImage') ? $spremenljivkaParams->get('quickImage') : 0);
						if($quickImage == -1){	//onesposobil za enkrat
														
							if ($length > 30) $class = 'ranking_long'; $class = 'ranking';
							//echo '<div class="variabla" id="variabla_'.$row1['id'].'">';
							echo '<div class="variabla" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'">';
							// Ikona za upload slike
							echo ' <span class="sprites image_upload pointer" onclick="vrednost_insert_image(\''.$row1['id'].'\', false); return false;" title="'.$lang['upload_img2'].'"></span>';
							
							echo '<span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span>';
							echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
							echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
							echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
							echo ' <span class="faicon edit2 inline inline_edit"></span>';
							echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline '.$class.'" style="float:none" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>'.$row1['naslov'].'</div>';
							//koda za notranji IF
							if ($row1['if_id'] > 0) {
								echo ' <span class="red">*</span>';

								echo ' <span style="font-size:9px; cursor:pointer" id="if_notranji_'.$row1['id'].'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">';
								$this->conditions_display($row1['if_id']);
								echo '</span>';

								if ($this->condition_check($row1['if_id']) != 0)
									echo ' <span class="faicon warning icon-orange"></span>';
							}
						}
						else{
							if ($length > 30) $class = 'ranking_long'; $class = 'ranking';
							//echo '<div class="variabla" id="variabla_'.$row1['id'].'">';
							echo '<div class="variabla" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'">';
							echo '<span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span>';
							echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
							echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
							echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
							echo ' <span class="faicon edit2 inline inline_edit"></span>';
							echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline '.$class.'" style="float:none" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>'.$row1['naslov'].'</div>';
							//koda za notranji IF
							if ($row1['if_id'] > 0) {
								echo ' <span class="red">*</span>';

								echo ' <span style="font-size:9px; cursor:pointer" id="if_notranji_'.$row1['id'].'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">';
								$this->conditions_display($row1['if_id']);
								echo '</span>';

								if ($this->condition_check($row1['if_id']) != 0)
									echo ' <span class="faicon warning icon-orange"></span>';
							}						
						}
					
					
					//******************// Slika kot odgovor - konec
					

					echo '</div>';

				}
				echo '</div>';

				// izris desne strani
				echo '<div id="half2" class="dropzone" style="width: 49%; min-height:' . $size . 'px; float: right;">';

				echo '<div class="dragdrop_frame"></div>';

				echo '</div>';

				echo '<div class="clr"></div>';
					
			}
			
			//Image hot spot @ radio ********************************************************************************************************
			if( ($row['tip'] == 1) && $row['orientation'] == 10){	//image hot spot
				$this->vprasanje_hotspot($row['id'], $row['tip'], $row['orientation']);
			}
			
			//Image hot spot @ checkbox ********************************************************************************************************
			if( ($row['tip'] == 2) && $row['orientation'] == 10){	//image hot spot
				$this->vprasanje_hotspot($row['id'], $row['tip'], $row['orientation']);
			}


			$stolpec = 1;
			$i = 0;
			while ($row1 = mysqli_fetch_array($sql1)) {

				if ($this->lang_id != null) {
					save('lang_id', $this->lang_id);
					$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
					if ($naslov != '') $row1['naslov'] = $naslov;

                    // Prevajanje in gdpr
                    if($prevajanje && $row['variable'] == 'gdpr'){

                        $gdpr_answer = '';

                        if($row1['variable'] == '2')
                            $gdpr_answer = 'yes';

                        if($row1['variable'] == '1')
                            $gdpr_answer = 'no';

                        // Prevedemo gdpr odgovore
                        if($gdpr_answer != ''){
                            $row1['naslov'] = $lang['srv_gdpr_intro_'.$gdpr_answer];
                        }             
                    }
				}

				// Ce je variabla ne vem in imamo vklopljen prikaz ob opozorilu -> rdec
				$missing_warning = '';
				if(($row1['other'] == '-97' && $row['alert_show_97'] > 0)
					|| ($row1['other'] == '-98' && $row['alert_show_98'] > 0)
					|| ($row1['other'] == '-99' && $row['alert_show_99'] > 0)){
					$missing_warning = ' red';
				}

				if ($row['tip'] == 1 && $row['orientation'] != 8 && $row['orientation'] != 10) {
					//echo '        <div class="variabla' . $cssFloat . '" id="variabla_'.$row1['id'].'"><span class="sprites move_updown_orange inline inline_move" title="'.$lang['srv_move'].'"></span> <input type="radio" name="foo_' . $row['id'] . '" id="foo_' . $row1['id'] . '" value="" /> <label for="foo_' . $row1['id'] . '">' . $row1['naslov'] . '</label>';
					if($row['orientation'] == 6){
						echo '        <div class="variabla' . $cssFloat . '" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'"><span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span> <div class="variable_inline variable_inline_'.$row['id'].'" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" vre_id='.$row1['id'].' tabindex="1">'.$row1['variable'].'</div><div id="vre_id_' . $row1['id'] . '" vre_id='.$row1['id'].' contenteditable="'.(!$locked?'true':'false').'" class="vrednost_inline" tabindex="1" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'] . '</div>';
					}
					elseif($row['orientation'] == 7){
						//echo '        <div class="variabla' . $cssFloat . '" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'"><span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span><div class="variable_inline variable_inline_'.$row['id'].'" contenteditable="'.(!$locked?'true':'false').'" vre_id='.$row1['id'].' tabindex="1">'.$row1['variable'].'</div><div id="vre_id_' . $row1['id'] . '" vre_id='.$row1['id'].' contenteditable="'.(!$locked?'true':'false').'" class="vrednost_inline '.$missing_warning.'" tabindex="1" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false?' default="1"':'').'>' . $row1['naslov'] . '</div> <input type="radio" name="foo_' . $row['id'] . '" id="foo_' . $row1['id'] . '" value="" onclick="return false" />';
						echo '        <div class="variabla' . $cssFloat . '" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'"><span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span><div class="variable_inline variable_inline_'.$row['id'].'" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" vre_id='.$row1['id'].' tabindex="1">'.$row1['variable'].'</div><div id="vre_id_' . $row1['id'] . '" vre_id='.$row1['id'].' contenteditable="'.(!$locked?'true':'false').'" class="vrednost_inline '.$missing_warning.'" tabindex="1" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'] . '</div>';

						if ($row1['other'] == 1){
							$otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
							$otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

							if ($otherHeight > 1)
								echo '<textarea name="" rows="'.$otherHeight.'" '.($otherWidth != -1 ? 'style="width:'.$otherWidth.'%;"' : '').' disabled="disabled"></textarea>';
							else
								echo '<input type="text" name="" value="" '.($otherWidth != -1 ? 'style="width:'.$otherWidth.'%;"' : '').' disabled="disabled" />';
					    }

						if ($row1['if_id'] > 0) {

							echo ' <span class="red">*</span>';

							echo ' <span style="font-size:9px; cursor:pointer" id="if_notranji_'.$row1['id'].'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">';
							$this->conditions_display($row1['if_id']);
							echo '</span>';

							if ($this->condition_check($row1['if_id']) != 0)
								echo ' <span class="faicon warning icon-orange"></span>';
						}

						echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
                        echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
						if($row['tip'] == 1)
							echo ' <span class="faicon odg_if_follow inline inline_if_follow '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="follow_up_condition(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_follow_up'].'"></span>';
						echo ' <span class="faicon odg_if_not inline inline_if_no '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
						echo ' <span class="faicon edit2 inline inline_edit"></span>';


						echo '<input type="radio" class="enka-admin-custom" name="foo_' . $row['id'] . '" id="foo_' . $row1['id'] . '" value="" onclick="return false" />';
                        echo '<span class="enka-checkbox-radio"></span>';
						echo'</div>';
					}
                    elseif($row['orientation'] == 9){
                        echo '<div class="variabla custom_radio ' . $cssFloat . '" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'">
                                    <label>
                                        <input type="radio" name="foo_' . $row['id'] . '" id="foo_' . $row1['id'] . '" value="" onclick="return false" />                                        
                                        <span class="enka-custom-radio '.($spremenljivkaParams->get('customRadio') ? $spremenljivkaParams->get('customRadio') : '').'"></span>
                                        <div class="custom_radio_answer">('.$row1['naslov'].')</div>
                                    </label>
                              </div>';

                    }
                    elseif( $row['orientation'] == 11){

                        $stVsehEnot = mysqli_num_rows($sql1);
                        //
                        echo '<div class="variabla custom_radio visual-radio-scale ' . $cssFloat . '" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'">
                                    <label>
                                        <input type="radio" name="foo_' . $row['id'] . '" id="foo_' . $row1['id'] . '" value="" onclick="return false" />
                                        <span class="enka-vizualna-skala siv-'.$stVsehEnot.$row1['naslov'].'"></span>
                                        <div class="custom_radio_answer">('.$row1['naslov'].')</div>
                                    </label>
                              </div>';
                    }
					else{		
						// Slika kot odgovor
						$quickImage = ($spremenljivkaParams->get('quickImage') ? $spremenljivkaParams->get('quickImage') : 0);
						if($quickImage == 1 && $row['orientation'] == 1){
							echo '        <div class="variabla' . $cssFloat . '" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'">';
							echo '          <span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span> <input type="radio" name="foo_' . $row['id'] . '" id="foo_' . $row1['id'] . '" class="enka-admin-custom" value="" onclick="return false" /><span class="enka-checkbox-radio"></span>';
							echo '			<div class="variable_inline variable_inline_'.$row['id'].'" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" vre_id='.$row1['id'].' tabindex="1">'.$row1['variable'].'</div>';
							
							// Ikona za upload slike
							echo ' <span class="sprites image_upload pointer" onclick="vrednost_insert_image(\''.$row1['id'].'\', false); return false;" title="'.$lang['upload_img2'].'"></span>';

							// Slika oz. text (brez moznosti editiranja) - samo če je vnesen kaksen text ali slika
							echo '          <div id="vre_id_' . $row1['id'] . '" vre_id='.$row1['id'].' contenteditable="'.(!$locked?'true':'false').'" class="vrednost_inline '.$missing_warning.'" tabindex="1" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'] . '</div>';
						}
						else{	
							echo '        <div class="variabla' . $cssFloat . '" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'">';
							echo '			<span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span> <input type="radio" class="enka-admin-custom enka-inline" name="foo_' . $row['id'] . '" id="foo_' . $row1['id'] . '" value="" onclick="return false" /><span class="enka-checkbox-radio"></span>';
							echo '			<div class="variable_inline variable_inline_'.$row['id'].'" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" vre_id='.$row1['id'].' tabindex="1">'.$row1['variable'].'</div>';
							
							echo '				<div id="vre_id_' . $row1['id'] . '" vre_id='.$row1['id'].' contenteditable="'.(!$locked?'true':'false').'" class="vrednost_inline '.$missing_warning.'" tabindex="1" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'] . '</div>';
						}
					}
				}
				elseif ($row['tip'] == 2 && $row['orientation'] != 10) {
					if($row['orientation'] == 6){
						echo '        <div class="variabla' . $cssFloat . '" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'"><span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span> <div class="variable_inline variable_inline_'.$row['id'].'" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" vre_id='.$row1['id'].' tabindex="1">'.$row1['variable'].'</div><div id="vre_id_' . $row1['id'] . '" vre_id='.$row1['id'].' contenteditable="'.(!$locked?'true':'false').'" class="vrednost_inline" tabindex="1" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'] . '</div>';
					}
					elseif($row['orientation'] == 7){
						//echo '        <div class="variabla' . $cssFloat . '" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'"><span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span> <div class="variable_inline variable_inline_'.$row['id'].'" contenteditable="'.(!$locked?'true':'false').'" vre_id='.$row1['id'].' tabindex="1">'.$row1['variable'].'</div><div id="vre_id_' . $row1['id'] . '" vre_id="'.$row1['id'].'" contenteditable="'.(!$locked?'true':'false').'" class="vrednost_inline '.$missing_warning.'" tabindex="1" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false?' default="1"':'').'>' . $row1['naslov'] . '</div><input type="checkbox" name="foo_' . $row['id'] . '" id="foo_' . $row1['id'] . '" value="" ' . (($row['checkboxhide'] == 1) ? 'class="hidden" ' : '') . ' onclick="return false" />';
							echo '        <div class="variabla' . $cssFloat . '" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'"><span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span> <div class="variable_inline variable_inline_'.$row['id'].'" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" vre_id='.$row1['id'].' tabindex="1">'.$row1['variable'].'</div><div id="vre_id_' . $row1['id'] . '" vre_id="'.$row1['id'].'" contenteditable="'.(!$locked?'true':'false').'" class="vrednost_inline '.$missing_warning.'" tabindex="1" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'] . '</div>';

						if ($row1['other'] == 1){
							$otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
							$otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

							if ($otherHeight > 1)
								echo '<textarea name="" rows="'.$otherHeight.'" '.($otherWidth != -1 ? 'style="width:'.$otherWidth.'%;"' : '').' disabled="disabled"></textarea>';
							else
								echo '<input type="text" name="" value="" '.($otherWidth != -1 ? 'style="width:'.$otherWidth.'%;"' : '').' disabled="disabled" />';
						}

						if ($row1['if_id'] > 0) {

							echo ' <span class="red">*</span>';

							echo ' <span style="font-size:9px; cursor:pointer" id="if_notranji_'.$row1['id'].'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">';
							$this->conditions_display($row1['if_id']);
							echo '</span>';

							if ($this->condition_check($row1['if_id']) != 0)
								echo ' <span class="faicon warning icon-orange"></span>';
						}

						echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
                        echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
						if($row['tip'] == 1)
							echo ' <span class="faicon odg_if_follow inline inline_if_follow '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="follow_up_condition(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_follow_up'].'"></span>';
						echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
						echo ' <span class="faicon edit2 inline inline_edit"></span>';


						echo'<input type="checkbox" name="foo_' . $row['id'] . '" id="foo_' . $row1['id'] . '" value="" class="enka-admin-custom ' . (($row['checkboxhide'] == 1) ? 'hidden' : '') . '" onclick="return false" />';
                        echo '<span class="enka-checkbox-radio"></span>';
						echo'</div>';
					}
					else{
						// Slika kot odgovor
						$quickImage = ($spremenljivkaParams->get('quickImage') ? $spremenljivkaParams->get('quickImage') : 0);
						if($quickImage == 1 && $row['orientation'] == 1){
							echo '        <div class="variabla' . $cssFloat . '" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'">';
							echo '          <span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span> <input type="radio" name="foo_' . $row['id'] . '" id="foo_' . $row1['id'] . '" value="" onclick="return false" />';
							echo '          <div class="variable_inline variable_inline_'.$row['id'].'" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" vre_id='.$row1['id'].' tabindex="1">'.$row1['variable'].'</div>';
							
							// Ikona za upload slike
							echo ' <span class="sprites image_upload pointer" onclick="vrednost_insert_image(\''.$row1['id'].'\', false); return false;" title="'.$lang['upload_img2'].'"></span>';

							// Slika oz. text (brez moznosti editiranja) - samo če je vnesen kaksen text ali slika
							echo '          <div id="vre_id_' . $row1['id'] . '" vre_id='.$row1['id'].' contenteditable="'.(!$locked?'true':'false').'" class="vrednost_inline '.$missing_warning.'" tabindex="1" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'] . '</div>';
						}
						else{
							echo '        <div class="variabla' . $cssFloat . '" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'"><span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span> <input type="checkbox" name="foo_' . $row['id'] . '" id="foo_' . $row1['id'] . '" value="" class="enka-admin-custom enka-inline' . (($row['checkboxhide'] == 1) ? 'hidden' : '') . '" onclick="return false" /><span class="enka-checkbox-radio"></span><div class="variable_inline variable_inline_'.$row['id'].'" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" vre_id='.$row1['id'].' tabindex="1">'.$row1['variable'].'</div><div id="vre_id_' . $row1['id'] . '" vre_id="'.$row1['id'].'" contenteditable="'.(!$locked?'true':'false').'" class="vrednost_inline '.$missing_warning.'" tabindex="1" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'] . '</div>';
						}
					}
				}
				elseif ($row['tip'] == 3) {
					//echo '        <option value="">' . $row1['naslov'] . '</option>';
					echo '        <div class="variabla' . $cssFloat . '" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'"><span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span> <div class="variable_inline variable_inline_'.$row['id'].'" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" vre_id='.$row1['id'].' tabindex="1">'.$row1['variable'].'</div><div id="vre_id_' . $row1['id'] . '" vre_id='.$row1['id'].' contenteditable="'.(!$locked?'true':'false').'" class="vrednost_inline" tabindex="1" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'] . '</div>';
				}


				if (!in_array($row['orientation'], [7, 9, 10, 11])) {
					if ($row1['other'] == 1){
						$otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
						$otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

						if ($otherHeight > 1)
							echo '<textarea name="" rows="'.$otherHeight.'" '.($otherWidth != -1 ? 'style="width:'.$otherWidth.'%;"' : '').' disabled="disabled"></textarea>';
						else
							echo '<input type="text" name="" value="" '.($otherWidth != -1 ? 'style="width:'.$otherWidth.'%;"' : '').' disabled="disabled" />';
					}

					if ($row1['if_id'] > 0) {

						echo ' <span class="red">*</span>';

						echo ' <span style="font-size:9px; cursor:pointer" id="if_notranji_'.$row1['id'].'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">';
						$this->conditions_display($row1['if_id']);
						echo '</span>';

						if ($this->condition_check($row1['if_id']) != 0)
							echo ' <span class="faicon warning icon-orange"></span>';
					}
					echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
					
					// Kljukica za kviz
					if(SurveyInfo::getInstance()->checkSurveyModule('quiz')){
						$sqlQ = sisplet_query("SELECT * FROM srv_quiz_vrednost WHERE spr_id='".$row['id']."' AND vre_id='".$row1['id']."'");
						echo ' <span class="faicon correct inline '.(mysqli_num_rows($sqlQ) > 0 ? ' show-correct' : '').'" spr_id="'.$row['id'].'" vre_id="'.$row1['id'].'" title="'.$lang['srv_vrednost_correct'].'"></span>';
					}
					
					echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
                   
				   if($row['tip'] == 1 || $row['tip'] == 2 || $row['tip'] == 3)
                        echo ' <span class="faicon odg_if_follow inline inline_if_follow '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="follow_up_condition(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_follow_up'].'"></span>';
                    
					echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
					echo ' <span class="faicon edit2 inline inline_edit"></span>';

					echo '</div>';

				}

				$i++;
			}		

			if ($row['tip'] == 3 || $row['orientation'] == 6) {//ce je tip vprasanja roleta ali je orientacija "Izbrite iz seznama"
				echo '</div><div class="preview_mode">';
				echo '      <div class="variabla' . $cssFloat . '"><select name="foo_' . $row['id'] . '"' . ' size="'.($row['orientation']=='6'?(mysqli_num_rows($sql1)+1):'1').'">';
				//echo '        <option value=""></option>';
				if ($row['orientation'] == 6){
					$prvaVrstica = ($spremenljivkaParams->get('prvaVrstica') ? $spremenljivkaParams->get('prvaVrstica') : 1);
					switch ($prvaVrstica) {
						case "1":
							
							break;
						case "2":
							echo '        <option value=""></option>';
											break;
						case "3":
							echo '<option value="">'.$lang['srv_dropdown_select'].'...</option>';
							break;
					}
				}
				elseif ($row['tip'] == 3){
					$prvaVrstica_roleta = ($spremenljivkaParams->get('prvaVrstica_roleta') ? $spremenljivkaParams->get('prvaVrstica_roleta') : 1);
					switch ($prvaVrstica_roleta) {
						case "1":
							echo '        <option value=""></option>';
							break;
						case "2":
					
											break;
						case "3":
							echo '<option value="">'.$lang['srv_dropdown_select'].'...</option>';
							break;
					}
				}
				mysqli_data_seek($sql1, 0);
				while ($row1 = mysqli_fetch_array($sql1)) {
					echo '        <option value="">' . $row1['naslov'] . '</option>';
				}
				echo '      </select>  <a href="#" onclick="$(this).closest(\'div.spremenljivka_content\').find(\'div.spremenljivka_settings\').click(); return false;">'.$lang['edit3'].'</a></div>';
				echo '</div>';
			}
		}
		// multigrid, multicheckbox, multitext, multinumber prikaz 
		elseif ($row['tip'] == 6 || $row['tip'] == 16 || $row['tip'] == 19 || $row['tip'] == 20 || $row['tip'] == 24) {

			$spremenljivkaParams = new enkaParameters($row['params']);
			$gridWidth = (($spremenljivkaParams->get('gridWidth') > 0) ? $spremenljivkaParams->get('gridWidth') : 30);
			$css = ' style = "width: '.$gridWidth.'%;" ';
			
			// izracuni za sirino celic
			$size = $row['grids'];

			# če imamo missinge size povečamo za 1 + številomissingov
			$sql_grid_mv = sisplet_query("SELECT id, spr_id FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0");
			$missing_count  = mysqli_num_rows($sql_grid_mv);
			if ($missing_count > 0) {
				$size += $missing_count + 1;
			}

			if($row['tip'] == 6 && $row['enota'] == 1){
				$size += 2;
			}
			if(($row['tip'] == 6 || $row['tip'] == 16) && $row['enota'] == 3){
				$size *= 2;
			}

			$size +=1;

			//ce imamo nastavljno sirino prvega grida ostalih ne nastavljamo
			if($gridWidth == 30)
				$cellsize = round(70/$size);
			else
				$cellsize = 'auto';

			$spacesize = round(70 / $size / 4);

			$taWidth = ($spremenljivkaParams->get('taWidth') ? $spremenljivkaParams->get('taWidth') : -1);
			$taHeight = ($spremenljivkaParams->get('taHeight') ? $spremenljivkaParams->get('taHeight') : 1);
			//default sirina
			if($taWidth == -1)
				//$taWidth = 10;
				$taWidth = round(50 / $size);

			
			
			$sizebox = '$("#grids_count option:selected").val()';
			$display = ($row['tip'] == 6 && $row['enota'] == 8) ? ' style="display:none;"' : '';
			$grid_plus_minus = '<div class="grid-plus-minus"><a href="#" onclick="change_selectbox_size_1(\'' . $row['id'] . '\', \'' . $lang['srv_select_box_vse'] . '\'); grid_plus_minus(\'1\'); return false;" title="'.$lang['srv_grid_add'].'"><span class="faicon add small '.$spremenljivka.'" '.$display.'></span></a> <a href="#" onclick="grid_plus_minus(\'0\'); return false;" title="'.$lang['srv_grid_remove'].'"><span class="faicon delete_circle"></span></a></div>';
			
			//izrisemo multigride z dropdowni in select box
			if($row['tip'] == 6 && ($row['enota'] == 2 || $row['enota'] == 6)) {

				echo '<table class="grid_header_table' . ' tabela_roleta">';

				echo '        <thead class="edit_mode">';

				// urejanje vrednosti
				echo '        <tr id="grid_variable_'.$row['id'].'" '.$show_variable_row.'>';
				echo '          <td></td>';
				echo '          <td></td>';

				$bg = 1;

				$sql2 = sisplet_query("SELECT id, naslov, vrstni_red, variable FROM srv_grid WHERE spr_id='$row[id]' AND vrstni_red>0 ORDER BY vrstni_red");
				$row2 = mysqli_fetch_array($sql2);

				for ($i = 1; $i <= $row['grids']; $i++) {
					if ($this->lang_id != null) {
						$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row2['id']);
						if ($naslov != '') $row2['naslov'] = $naslov;
					}
					if ($row2['vrstni_red'] == $i) {
						echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_variable_inline" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'">' . $row2['variable'] . '</div></td>';
						$row2 = mysqli_fetch_array($sql2);
					} else {
						echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"></td>';
					}
				}
				echo '</tr>';

				echo '        <tr>';
				echo '          <td>'.$grid_plus_minus.'</td>';
				//echo '          <td style="width:' . $spacesize . '%"></td>';
				echo '          <td></td>';

				$bg = 1;

				$sql2 = sisplet_query("SELECT id, naslov, vrstni_red FROM srv_grid WHERE spr_id='$row[id]' AND other=0 ORDER BY vrstni_red");
				$row2 = mysqli_fetch_array($sql2);		
				
				for ($i = 1; $i <= $row['grids']; $i++) {
					if ($row2['vrstni_red'] == $i) {
					
						if ($this->lang_id != null) {
							$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row2['id']);
							if ($naslov != '') $row2['naslov'] = $naslov;
						}
				
						echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'" '.(strpos($row2['naslov'], $lang['srv_new_grid'])!==false || strpos($row2['naslov'], $lang1['srv_new_grid'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row2['naslov'] . '</div></td>';
						
						$row2 = mysqli_fetch_array($sql2);
					} 
					else {
						echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"></td>';
					}
				}

				#kateri missingi so nastavljeni
				$sql_grid_mv = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0");
				if (mysqli_num_rows($sql_grid_mv) > 0 ) {
					echo '<td class=""></td>';
					while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
						
						if ($this->lang_id != null) {
							$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row_grid_mv['id']);
							if ($naslov != '') $row_grid_mv['naslov'] = $naslov;
						}
					
						echo '<td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row_grid_mv['id'].'"><div class="grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row_grid_mv['id'].'" '.(strpos($row_grid_mv['naslov'], $lang['srv_new_grid'])!==false || strpos($row_grid_mv['naslov'], $lang1['srv_new_grid'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row_grid_mv['naslov'] . '</div></td>';
					}
				}
				echo '        </tr>';

				echo '</thead>';

				echo '<tbody class="'.($this->lang_id==null?'allow_new':'').'">';

				$bg = 1;


                $sql1 = sisplet_query("SELECT id, naslov, other, hidden, if_id FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
				while ($row1 = mysqli_fetch_array($sql1)) {

					if ($this->lang_id != null) {
						save('lang_id', $this->lang_id);
						$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
						if ($naslov != '') $row1['naslov'] = $naslov;
					}

					echo '<tr class="variabla" id="variabla_'.$row1['id'].'">';
					echo '<td class="grid_question ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" '.($gridWidth == -1 ? '' : $css ).' id="f_'.$row1['id'].'">';
					echo '<span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span>';
					echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
                    echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
                    echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
					echo ' <span class="faicon edit2 inline inline_edit"></span>';

					echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'].'</div>';

					if ($row1['if_id'] > 0) {
						echo ' <span class="red" style="cursor:pointer" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">*</span>';
						if ($this->condition_check($row1['if_id']) != 0)
							echo ' <span class="faicon warning icon-orange"></span>';
					}

					if ($row1['other'] == 1){
						$otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
						$otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

						if ($otherHeight > 1)
							echo '<textarea name="" rows="'.$otherHeight.'" style="max-width:50%; '.($otherWidth != -1 ? ' width:'.$otherWidth.'%;' : '').'" disabled="disabled"></textarea>';
						else
							echo '<input type="text" name="" value="" style="max-width:50%; '.($otherWidth != -1 ? ' width:'.$otherWidth.'%;' : '').'" disabled="disabled" />';
					}

					echo '</td>';

					$sql2 = sisplet_query("SELECT id, naslov, vrstni_red FROM srv_grid WHERE spr_id='$row[id]' AND other=0 ORDER BY vrstni_red");
					$row2 = mysqli_fetch_array($sql2);

					echo '<td class="preview_mode">';

					if ($row['enota'] == 6){
						echo '<select style="width: 100px;" multiple="">';
					}else{
						echo '<select style="width: 100px;">';
					}

					if($row['enota'] == 6){
						//echo '<option></option>';
						$prvaVrstica = ($spremenljivkaParams->get('prvaVrstica') ? $spremenljivkaParams->get('prvaVrstica') : 1);
						switch ($prvaVrstica) {
							case "1":

								break;
							case "2":
						echo '<option></option>';
								break;
							case "3":
								echo '<option>'.$lang['srv_dropdown_select'].'...</option>';
								break;
						}
					}
					elseif($row['enota'] == 2){
						//echo '<option></option>';
						$prvaVrstica_roleta = ($spremenljivkaParams->get('prvaVrstica_roleta') ? $spremenljivkaParams->get('prvaVrstica_roleta') : 1);
						switch ($prvaVrstica_roleta) {
							case "1":
								echo '<option></option>';
								break;
							case "2":
						
								break;
							case "3":
								echo '<option>'.$lang['srv_dropdown_select'].'...</option>';
								break;
						}
					}
					
					


					for ($i = 1; $i <= $row['grids']; $i++) {
						if ($row2['vrstni_red'] == $i) {
							if ($this->lang_id != null) {
								$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row2['id']);
								if ($naslov != '') $row2['naslov'] = $naslov;
							}
						
							echo '<option>' . $row2['naslov'] . '</option>';
							$row2 = mysqli_fetch_array($sql2);
						}
					}

					#kateri missingi so nastavljeni
					$sql_grid_mv = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0 ORDER BY vrstni_red");
					while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
						if ($this->lang_id != null) {
							$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row_grid_mv['id']);
							if ($naslov != '') $row_grid_mv['naslov'] = $naslov;
						}
						echo '<option>' . $row_grid_mv['naslov'] . '</option>';
					}
					echo '</select>';
					echo ' <a href="#" onclick="$(this).closest(\'div.spremenljivka_content\').find(\'div.spremenljivka_settings\').click(); return false;">'.$lang['edit3'].'</a>';
					echo '</td>';


					echo '</tr>';
					$bg++;
				}


				echo '</tbody>';
				echo '</table>';
			}

			//izrisemo multigride s select box namesto checkbox
			elseif($row['tip'] == 16 && $row['enota'] == 6) {

				echo '<table class="grid_header_table' . ' tabela_roleta">';

				echo '        <thead class="edit_mode">';

				// urejanje vrednosti
				echo '        <tr id="grid_variable_'.$row['id'].'" '.$show_variable_row.'>';
				echo '          <td></td>';
				echo '          <td></td>';

				$bg = 1;

				$sql2 = sisplet_query("SELECT id, naslov, vrstni_red, variable FROM srv_grid WHERE spr_id='$row[id]' AND vrstni_red>0 ORDER BY vrstni_red");
				$row2 = mysqli_fetch_array($sql2);

				for ($i = 1; $i <= $row['grids']; $i++) {
					if ($row2['vrstni_red'] == $i) {
						echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_variable_inline" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'">' . $row2['variable'] . '</div></td>';
						$row2 = mysqli_fetch_array($sql2);
					} else {
						echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"></td>';
					}
				}
				echo '</tr>';

				echo '        <tr>';
				echo '          <td>'.$grid_plus_minus.'</td>';
				//echo '          <td style="width:' . $spacesize . '%"></td>';
				echo '          <td></td>';

				$bg = 1;

				$sql2 = sisplet_query("SELECT id, naslov, vrstni_red FROM srv_grid WHERE spr_id='$row[id]' AND other=0 ORDER BY vrstni_red");
				$row2 = mysqli_fetch_array($sql2);

				for ($i = 1; $i <= $row['grids']; $i++) {
					if ($this->lang_id != null) {
						$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row2['id']);
						if ($naslov != '') $row2['naslov'] = $naslov;
					}
					if ($row2['vrstni_red'] == $i) {
						echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'" '.(strpos($row2['naslov'], $lang['srv_new_grid'])!==false || strpos($row2['naslov'], $lang1['srv_new_grid'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row2['naslov'] . '</div></td>';
						$row2 = mysqli_fetch_array($sql2);
					} else {
						echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"></td>';
					}
				}

				#kateri missingi so nastavljeni
				$sql_grid_mv = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0");
				if (mysqli_num_rows($sql_grid_mv) > 0 ) {
					echo '<td class=""></td>';
					while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
						
						if ($this->lang_id != null) {
							$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row_grid_mv['id']);
							if ($naslov != '') $row_grid_mv['naslov'] = $naslov;
						}
					
						echo '<td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row_grid_mv['id'].'"><div class="grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row_grid_mv['id'].'" '.(strpos($row_grid_mv['naslov'], $lang['srv_new_grid'])!==false || strpos($row_grid_mv['naslov'], $lang1['srv_new_grid'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row_grid_mv['naslov'] . '</div></td>';
					}
				}
				echo '        </tr>';

				echo '</thead>';

				echo '<tbody class="'.($this->lang_id==null?'allow_new':'').'">';

				$bg = 1;


				$sql1 = sisplet_query("SELECT id, naslov, hidden, other, if_id FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
				while ($row1 = mysqli_fetch_array($sql1)) {

					if ($this->lang_id != null) {
						save('lang_id', $this->lang_id);
						$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
						if ($naslov != '') $row1['naslov'] = $naslov;
					}

					echo '<tr class="variabla" id="variabla_'.$row1['id'].'">';
					echo '<td class="grid_question ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" '.($gridWidth == -1 ? '' : $css ).' id="f_'.$row1['id'].'">';
					echo '<span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span>';
					echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
                    echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
                    echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
					echo ' <span class="faicon edit2 inline inline_edit"></span>';

					echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'].'</div>';

					if ($row1['if_id'] > 0) {
						echo ' <span class="red" style="cursor:pointer" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">*</span>';
						if ($this->condition_check($row1['if_id']) != 0)
							echo ' <span class="faicon warning icon-orange"></span>';
					}

					if ($row1['other'] == 1){
						$otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
						$otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

						if ($otherHeight > 1)
							echo '<textarea name="" rows="'.$otherHeight.'" style="max-width:50%; '.($otherWidth != -1 ? ' width:'.$otherWidth.'%;' : '').'" disabled="disabled"></textarea>';
						else
							echo '<input type="text" name="" value="" style="max-width:50%; '.($otherWidth != -1 ? ' width:'.$otherWidth.'%;' : '').'" disabled="disabled" />';
					}

					echo '</td>';

					$sql2 = sisplet_query("SELECT naslov, vrstni_red FROM srv_grid WHERE spr_id='$row[id]' AND other=0 ORDER BY vrstni_red");
					$row2 = mysqli_fetch_array($sql2);

					echo '<td class="preview_mode">';

					if ($row['enota'] == 6){
						echo '<select style="width: 100px;" multiple="">';
					}else{
						echo '<select style="width: 100px;">';
					}

					//echo '<option></option>';
					$prvaVrstica = ($spremenljivkaParams->get('prvaVrstica') ? $spremenljivkaParams->get('prvaVrstica') : 1);
					switch ($prvaVrstica) {
						case "1":

							break;
						case "2":
					echo '<option></option>';
							break;
						case "3":
							echo '<option>'.$lang['srv_dropdown_select'].'...</option>';
							break;
					}

					for ($i = 1; $i <= $row['grids']; $i++) {
						if ($row2['vrstni_red'] == $i) {
							echo '<option>' . $row2['naslov'] . '</option>';
							$row2 = mysqli_fetch_array($sql2);
						}
					}

					#kateri missingi so nastavljeni
					$sql_grid_mv = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0 ORDER BY vrstni_red");
					while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
						if ($this->lang_id != null) {
							$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row_grid_mv['id']);
							if ($naslov != '') $row_grid_mv['naslov'] = $naslov;
						}
						echo '<option>' . $row_grid_mv['naslov'] . '</option>';
					}
					echo '</select>';
					echo ' <a href="#" onclick="$(this).closest(\'div.spremenljivka_content\').find(\'div.spremenljivka_settings\').click(); return false;">'.$lang['edit3'].'</a>';
					echo '</td>';


					echo '</tr>';
					$bg++;
				}


				echo '</tbody>';
				echo '</table>';
			}

			//izrisemo double multigride
			elseif(($row['tip'] == 6 || $row['tip'] == 16) && $row['enota'] == 3){

				$colspan = $row['grids'];
				$sql_grid_mv = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0 AND part='1'");
				if (mysqli_num_rows($sql_grid_mv) > 0 ) {
					$colspan += mysqli_num_rows($sql_grid_mv) + 1;
				}

				echo '<table class="grid_header_table' . '" cellspacing="0">';

				echo '  <thead>';

				echo '  <tr>';
				echo '      <td></td>';
				echo '      <td style="width:' . $spacesize . '%"></td>';

                // Prevod podnaslovov
                if ($this->lang_id != null) {

                    $podnaslov1 = \App\Controllers\LanguageController::srv_language_grid_podnaslov($row['id'], 1);
                    $podnaslov2 = \App\Controllers\LanguageController::srv_language_grid_podnaslov($row['id'], 2);

                    if ($podnaslov1 != '') {				
                        $row['grid_subtitle1'] = $podnaslov1;
                    }
                    if ($podnaslov2 != '') {				
                        $row['grid_subtitle2'] = $podnaslov2;
                    }
                }
                
				// Urejanje podnaslova 1. grida
				echo '		<td class="grid_header" colspan="'.$colspan.'">';
				echo '<div class="grid_subtitle_inline" grid_id=1 contenteditable="'.(!$locked?'true':'false').'" grid_subtitle="grid_subtitle1">' . $row['grid_subtitle1'] . '</div>';
				echo '</td>';

				echo '		<td style="width:0px;"></td>';

				// Urejanje podnaslova 2. grida
				echo '		<td class="grid_header" colspan="'.$colspan.'">';
				echo '<div class="grid_subtitle_inline" grid_id=2 contenteditable="'.(!$locked?'true':'false').'" grid_subtitle="grid_subtitle2">' . $row['grid_subtitle2'] . '</div>';
				echo '</td>';

				echo '<td style="width:' . $spacesize*3 . '%"></td>';
				
				echo '	</tr>';


				// urejanje vrednosti
				echo '        <tr id="grid_variable_'.$row['id'].'" '.$show_variable_row.'>';
				echo '          <td></td>';
				//echo '          <td style="width:' . $spacesize . '%"></td>';
				echo '          <td></td>';

				$bg = 1;

				$sql2 = sisplet_query("SELECT id, naslov, vrstni_red, variable FROM srv_grid WHERE spr_id='$row[id]' AND vrstni_red>0 ORDER BY vrstni_red");
				$row2 = mysqli_fetch_array($sql2);

				for ($i = 1; $i <= $row['grids']; $i++) {
					if ($this->lang_id != null) {
						$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row2['id']);
						if ($naslov != '') $row2['naslov'] = $naslov;
					}
					if ($row2['vrstni_red'] == $i) {
						echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_variable_inline" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'">' . $row2['variable'] . '</div></td>';
						$row2 = mysqli_fetch_array($sql2);
					} else {
						echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"></td>';
					}
				}
				
				echo '<td style="width:' . $spacesize*3 . '%"></td>';
				
				echo '</tr>';

				echo '        <tr>';
				echo '          <td>'.$grid_plus_minus.'</td>';
				echo '          <td style="width:' . $spacesize . '%"></td>';

				$bg = 1;

				//PRVI DEL GRIDA
				$sql2 = sisplet_query("SELECT id, naslov, vrstni_red FROM srv_grid WHERE spr_id='$row[id]' AND other=0 AND part='1' ORDER BY vrstni_red");
				$row2 = mysqli_fetch_array($sql2);
				for ($i = 1; $i <= $row['grids']; $i++) {
					if ($this->lang_id != null) {
						$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row2['id']);
						if ($naslov != '') $row2['naslov'] = $naslov;
					}
					if ($row2['vrstni_red'] == $i) {
						echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'" '.(strpos($row2['naslov'], $lang['srv_new_grid'])!==false || strpos($row2['naslov'], $lang1['srv_new_grid'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row2['naslov'] . '</div></td>';
						$row2 = mysqli_fetch_array($sql2);
					} else {
						echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"></td>';
					}
				}

				#kateri missingi so nastavljeni
				$sql_grid_mv = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0 AND part='1'");
				if (mysqli_num_rows($sql_grid_mv) > 0 ) {
					echo '<td class=""></td>';
					while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
						if ($this->lang_id != null) {
							$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row_grid_mv['id']);
							if ($naslov != '') $row_grid_mv['naslov'] = $naslov;
						}
						
						echo '<td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row_grid_mv['id'].'"><div class="grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row_grid_mv['id'].'" '.(strpos($row_grid_mv['naslov'], $lang['srv_new_grid'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $row_grid_mv['naslov'] . '</div></td>';
					}
				}

				echo '<td style="width:0px; border-left: 1px black solid;"></td>';

				//DRUGI DEL GRIDA
				//$sql2 = sisplet_query("SELECT naslov, vrstni_red FROM srv_grid WHERE spr_id='$row[id]' AND other=0 AND part='2' ORDER BY vrstni_red");
				$sql2String = "SELECT id, naslov, vrstni_red FROM srv_grid WHERE spr_id='$row[id]' AND other=0 AND part='2' ORDER BY vrstni_red";
				$sql2 = sisplet_query($sql2String);
				//$sql2 = sisplet_query("SELECT naslov, vrstni_red FROM srv_grid WHERE spr_id='$row[id]' AND other=0 AND part='1' ORDER BY vrstni_red");
				$row2 = mysqli_fetch_array($sql2);
				for ($i = 1; $i <= $row['grids']; $i++) {
					if ($this->lang_id != null) {						
						//$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row2['idrow2['id']);
						$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $i);
						if ($naslov != '') $row2['naslov'] = $naslov;
					}
					if ($row2['vrstni_red'] == $i+$row['grids']+mysqli_num_rows($sql_grid_mv)) {
						//echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . ' double" grd="g_'.$i.'" onclick="javascript:$(\'#branching_'.$spremenljivka.' div[grd_id='.$i.']\').focus();">' . $row2['naslov'] . '</td>';
						if($this->prevajanje == true){
							$oznaka = 'vprlang';
						}elseif($this->prevajanje == false){
							$oznaka = 'branching';
						}
						
						//echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . ' double" grd="g_'.$i.'" onclick="javascript:$(\'#'.$oznaka.'_'.$spremenljivka.' div[grd_id='.$i.']\').focus();">' . $row2['naslov'] . '</td>';
						echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . ' double" grd="g_'.$i.'" onclick="javascript:$(\'#'.$oznaka.'_'.$spremenljivka.' div[grd_id='.$i.']\').focus();">' . $row2['naslov'] . '</td>';
						$row2 = mysqli_fetch_array($sql2);
					} else {
						echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"></td>';
					}
				}

				#kateri missingi so nastavljeni
				$sql_grid_mv = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0 AND part='2'");
				if (mysqli_num_rows($sql_grid_mv) > 0 ) {
					echo '<td class=""></td>';
					while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
						if ($this->lang_id != null) {
							$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row_grid_mv['id']);
							if ($naslov != '') $row_grid_mv['naslov'] = $naslov;
						}
					
						echo '<td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . ' double" grd="g_'.($row_grid_mv['id']-$row['grids']-mysqli_num_rows($sql_grid_mv)).'" onclick="javascript:$(\'#branching_'.$spremenljivka.' div[grd_id='.$row_grid_mv['id'].']\').focus();">' . $row_grid_mv['naslov'] . '</td>';
					}
				}

				echo '        </tr>';

				echo '        </thead>';
				echo '        <tbody class="'.($this->lang_id==null?'allow_new':'').'">';

				$bg++;

				//$orderby = Survey::generate_order_by_field($spremenljivka);

				//$sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
				$sql1 = sisplet_query("SELECT id, naslov, hidden, if_id, other FROM srv_vrednost WHERE spr_id='$row[id]'  ORDER BY vrstni_red");
				while ($row1 = mysqli_fetch_array($sql1)) {

					if ($this->lang_id != null) {
						save('lang_id', $this->lang_id);
						$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
						if ($naslov != '') $row1['naslov'] = $naslov;
					}

					echo '        <tr class="variabla" id="variabla_'.$row1['id'].'">';
					echo '          <td class="grid_question ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" '.($gridWidth == -1 ? '' : $css ).' id="f_'.$row1['id'].'">';
					echo '<span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span>';
                    echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
                    echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
                    echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
					echo ' <span class="faicon edit2 inline inline_edit"></span>';

					echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'].'</div>';

					if ($row1['if_id'] > 0) {
						echo ' <span class="red" style="cursor:pointer" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">*</span>';
						if ($this->condition_check($row1['if_id']) != 0)
							echo ' <span class="faicon warning icon-orange"></span>';
					}

					if ($row1['other'] == 1){
						$otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
						$otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

						if ($otherHeight > 1)
							echo '<textarea name="" rows="'.$otherHeight.'" style="max-width:50%; '.($otherWidth != -1 ? ' width:'.$otherWidth.'%;' : '').'" disabled="disabled"></textarea>';
						else
							echo '<input type="text" name="" value="" style="max-width:50%; '.($otherWidth != -1 ? ' width:'.$otherWidth.'%;' : '').'" disabled="disabled" />';

					}

					echo '</td>';
					echo '<td style="width:' . $spacesize . '%"></td>';

					//PRVI DEL GRIDA
					//razlicni vnosi glede na tip multigrida
					for ($i = 1; $i <= $row['grids']; $i++) {

						if($row['tip'] == 6)
							echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';

						elseif($row['tip'] == 16)
							echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="checkbox" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
					}
					#kateri missingi so nastavljeni
					$sql_grid_mv = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0 AND part='1'");
					if (mysqli_num_rows($sql_grid_mv) > 0 ) {
						echo '<td style="width:' . $spacesize . '%"></td>';
						while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
							if($row['tip'] == 6) {
								echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom"  name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
							} elseif($row['tip'] == 16) {
								echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="checkbox" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
							} else {
								echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';

							}

						}
					}

					echo '<td style="width:1%; border-left: 1px black solid;"></td>';

					//DRUGI DEL GRIDA
					//razlicni vnosi glede na tip multigrida
					for ($i = 1; $i <= $row['grids']; $i++) {

						if($row['tip'] == 6)
							echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';

						elseif($row['tip'] == 16)
							echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="checkbox" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
					}
					#kateri missingi so nastavljeni
					$sql_grid_mv = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0 AND part='2'");
					if (mysqli_num_rows($sql_grid_mv) > 0 ) {
						echo '<td style="width:' . $spacesize . '%"></td>';
						while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
							if($row['tip'] == 6) {
								echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
							} elseif($row['tip'] == 16) {
								echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="checkbox" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
							} else {
								echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
							}

						}
					}

					// Zadnja bela celica kjer se nahajajo ikone za mouseover
					echo '<td class="white" style="min-width:80px;"></td>';
					
					echo '        </tr>';

					$bg++;
				}

				echo '      </tbody>';
				echo '      </table>';


			// multiple gridi
			} elseif ($row['tip'] == 24) {

				$this->vprasanje_grid_multiple($row['id']);
			}

			//one against another
			elseif($row['tip'] == 6 && $row['enota'] == 4){

                echo '      <table class="grid_header_table '.($this->lang_id==null?'allow_new':'').'">';

				// urejanje vrednosti
				echo '        <tr id="grid_variable_'.$row['id'].'" '.$show_variable_row.'>';
				echo '          <td></td>';

				$bg = 1;

				$sql2 = sisplet_query("SELECT id, variable FROM srv_grid WHERE spr_id='$row[id]' AND other=0 ORDER BY vrstni_red");
				$row2 = mysqli_fetch_array($sql2);

				echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_variable_inline" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'">' . $row2['variable'] . '</div></td>';
				echo '          <td></td>';
				$row2 = mysqli_fetch_array($sql2);
				echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_variable_inline" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'">' . $row2['variable'] . '</div></td>';
				echo '</tr>';
				
				
				echo '<tbody class="'.($this->lang_id==null?'allow_new':'').'">';

				$bg++;


                $sql1 = sisplet_query("SELECT id, naslov, naslov2, if_id, other FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
				while ($row1 = mysqli_fetch_array($sql1)) {
					
					if ($this->lang_id != null) {
						save('lang_id', $this->lang_id);
						
						$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
						if ($naslov != '') $row1['naslov'] = $naslov;
						
						$naslov2 = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id'], true);
						if ($naslov2 != '') $row1['naslov2'] = $naslov2;
					}				
					
					echo '        <tr class="variabla" id="variabla_'.$row1['id'].'">';
					echo '          <td class="grid_question ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" '.($gridWidth == -1 ? '' : $css ).' id="'.$row1['id'].'">';
					echo '<span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span>';

					echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';

					//levi stolpec možnosti
					echo '<div style="text-align:right;" id="vre_id_'.$row1['id'].'" class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'].'</div>';

					if ($row1['if_id'] > 0) {
						echo ' <span class="red" style="cursor:pointer" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">*</span>';
						if ($this->condition_check($row1['if_id']) != 0)
							echo ' <span class="faicon warning icon-orange"></span>';
					}

					if ($row1['other'] == 1){
						$otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
						$otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

						if ($otherHeight > 1)
							echo '<textarea name="" rows="'.$otherHeight.'" style="max-width:50%; '.($otherWidth != -1 ? ' width:'.$otherWidth.'%;' : '').'" disabled="disabled"></textarea>';
						else
							echo '<input type="text" name="" value="" style="max-width:50%; '.($otherWidth != -1 ? ' width:'.$otherWidth.'%;' : '').'" disabled="disabled" />';
					}

					echo '</td>';


					//radio buttons in "ali"
                    echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';

					echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '">'.$lang['srv_tip_sample_t6_4_vmes'].'</td>';

					echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';

					//<span>ali</span><input type="radio" name="foo_' . $row1['id'] . '" value="" />

					#kateri missingi so nastavljeni
					$sql_grid_mv = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0");
					if (mysqli_num_rows($sql_grid_mv) > 0 ) {
						//echo '<td style="width:' . $spacesize . '%"></td>';
						echo '<td></td>';
						while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {

							if($row['tip'] == 6) {
								echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
							} else {
								echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_missing_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
							}

						}
					}


					// desni stolpec možnosti, predelani bivši diferencial
					echo '          <td style="text-align:left;" class="grid_question ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" id="f_'.$row1['id'].'_2"><div class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'_2" '.(strpos($row1['naslov2'], $lang['srv_new_vrednost'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $row1['naslov2'] . '</div></td>';

					// Zadnja bela celica kjer se nahajajo ikone za mouseover
					echo '<td class="white" style="min-width:20px;"></td>';

					echo '        </tr>';

					$bg++;
				}

				echo '      </tbody>';
				echo '      </table>';
			}

			//MaxDiff
			elseif($row['tip'] == 6 && $row['enota'] == 5){
				//echo "MaxDiff";
				echo '      <table class="grid_header_table '.($this->lang_id==null?'allow_new':'').'">';
				echo '        <thead>';	//začetek glave oz. naslovne vrstice tabele

				// urejanje vrednosti
				echo '        <tr id="grid_variable_'.$row['id'].'" '.$show_variable_row.'>';

				$bg = 1;

				$sql2 = sisplet_query("SELECT id, variable FROM srv_grid WHERE spr_id='$row[id]' AND other=0 ORDER BY vrstni_red");
				$row2 = mysqli_fetch_array($sql2);

				echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_variable_inline" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'">' . $row2['variable'] . '</div></td>';
				$row2 = mysqli_fetch_array($sql2);
				echo '          <td></td>';
				echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_variable_inline" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'">' . $row2['variable'] . '</div></td>';
				echo '</tr>';

				echo '        <tr>';

				$bg = 1;

				$sql2 = sisplet_query("SELECT id, naslov, vrstni_red FROM srv_grid WHERE spr_id='$row[id]' AND other=0 ORDER BY vrstni_red");
				$row2 = mysqli_fetch_array($sql2);

				//risanje naslovov stolpcev				
				for ($i = 1; $i <= $row['grids']; $i++) {

					if ($this->lang_id != null) {
						$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row2['id']);
						if ($naslov != '') $row2['naslov'] = $naslov;
					}
					
					if ($row2['vrstni_red'] == $i) {
						//echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'" '.(strpos($row2['naslov'], $lang['srv_new_grid'])!==false || strpos($row2['naslov'], $lang1['srv_new_grid'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row2['naslov'] . '</div></td>';
						$label_text = ($row2['id'] % 2 == 0 ? $lang['srv_maxdiff_label1'] : $lang['srv_maxdiff_label2']);
												
						//ce je default besedilo "Vpišite besedilo" spremeni labelo v "Najmanj pomemben" in "Najbolj pomemben", drugace pokazi v labelah, kar je v bazi
						echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'"'.(strpos($row2['naslov'], $lang['srv_new_grid'])!==false || strpos($row2['naslov'], $lang1['srv_new_grid'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . ($row2['naslov'] == $lang['srv_new_grid'] ? $label_text : $row2['naslov']) . '</div></td>';
						
						$row2 = mysqli_fetch_array($sql2);
						echo '<td></td>';
					} else {
						echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"></td>';
					}
				}

				#kateri missingi so nastavljeni
				$sql_grid_mv = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0");
				if (mysqli_num_rows($sql_grid_mv) > 0 ) {
					//echo '<td class=""></td>';
					while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
						if ($this->lang_id != null) {
							$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row_grid_mv['id']);
							if ($naslov != '') $row_grid_mv['naslov'] = $naslov;
						}
						
						echo '<td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row_grid_mv['id'].'"><div class="grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row_grid_mv['id'].'" '.(strpos($row_grid_mv['naslov'], $lang['srv_new_grid'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $row_grid_mv['naslov'] . '</div></td>';
					}
				}
				echo '        </tr>';

				echo '</thead>';	//konec glave oz. naslovne vrstice tabele

				echo '<tbody class="'.($this->lang_id==null?'allow_new':'').'">';	//zacetek telesa tabele

				$bg++;

				$sql1 = sisplet_query("SELECT id, naslov, hidden, if_id, other FROM srv_vrednost WHERE spr_id='$row[id]'  ORDER BY vrstni_red");
				while ($row1 = mysqli_fetch_array($sql1)) {

					if ($this->lang_id != null) {
						save('lang_id', $this->lang_id);
						$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
						if ($naslov != '') $row1['naslov'] = $naslov;
					}

					echo '        <tr class="variabla" id="variabla_'.$row1['id'].'">';	//začetek vrstice


			        // levi del radio button
					 //echo ' <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" name="foo_' . $row1['id'] . '" value=""  /></td>';
					 echo ' <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value=""  data-col="1"/><span class="enka-checkbox-radio"></span></td>';

			        //sredinski del z besedilom
					echo '          <td style="text-align:center;" class="grid_question ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" '.($gridWidth == -1 ? '' : $css ).' id="'.$row1['id'].'">';
					echo '<span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span>';
                    echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
                    echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
                    echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
					echo ' <span class="faicon edit2 inline inline_edit"></span>';


					echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'].'</div>';

					if ($row1['if_id'] > 0) {
						echo ' <span class="red" style="cursor:pointer" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">*</span>';
						if ($this->condition_check($row1['if_id']) != 0)
							echo ' <span class="faicon warning icon-orange"></span>';
					}

					if ($row1['other'] == 1){
						$otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
						$otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

						if ($otherHeight > 1)
							echo '<textarea name="" rows="'.$otherHeight.'" style="max-width:50%; '.($otherWidth != -1 ? ' width:'.$otherWidth.'%;' : '').'" disabled="disabled"></textarea>';
						else
							echo '<input type="text" name="" value="" style="max-width:50%; '.($otherWidth != -1 ? ' width:'.$otherWidth.'%;' : '').'" disabled="disabled" />';
					}

					echo '</td>';


			        // desni del radio button
					echo ' <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" data-col="2"/><span class="enka-checkbox-radio"></span></td>';

					//urejanje navpicnega dela grupiranja radio button - vodoravni je urejen po defaultu s pomočjo atributa name
					echo'
						<script>

						$(document).ready(
							function(){
								var col, elem, ime;
								ime = "foo_' . $row1['id'] . '";

									$("input[name=" + ime + "]").click(function() {
										elem = $(this);
										col = elem.data("col");
										$("input[data-col=" + col + "]").prop("checked", false);
										elem.prop("checked", true);
									});

							}
						);
						</script>
					';

					#kateri missingi so nastavljeni
					$sql_grid_mv = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0");
					if (mysqli_num_rows($sql_grid_mv) > 0 ) {
						//echo '<td style="width:' . $spacesize . '%"></td>';
						echo '<td></td>';
						while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
							if($row['tip'] == 6) {
								echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
							} else {
								echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_missing_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
							}

						}
					}

					// diferencial

					echo '        </tr>';	//konec vrstice

					$bg++;
				}

				echo '      </tbody>';
				echo '      </table>';
 			}
			
			//Drag and drop grids********************************************************************************************************
			//elseif($row['tip'] == 6 && $row['enota'] == 9){	//Drag and drop grids
			elseif( ($row['tip'] == 6 || $row['tip'] == 16) && $row['enota'] == 9){	//Drag and drop grids

				$sql1 = sisplet_query("SELECT id, naslov, hidden, other, if_id FROM srv_vrednost WHERE spr_id = '$spremenljivka' AND vrstni_red>0 ORDER BY vrstni_red");

				//izracun visine
				$num = mysqli_num_rows($sql1);
				$size = $num * 50;

				//zaslon razdelimo na dva dela - izris leve strani
				echo '<div id="half" class="dropzone '.($this->lang_id==null?'allow_new':'').'" style="width: 50%; min-height:' . $size . 'px; float: left; border-right: 1px solid black;">';
				
				while ($row1 = mysqli_fetch_array($sql1)) {
					
					if ($this->lang_id != null) {
						save('lang_id', $this->lang_id);
						$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
						if ($naslov != '') $row1['naslov'] = $naslov;
					}

					//preverimo dolzino niza -> max == 20
					$length = strlen($row1['naslov']);
					?>
					<script>
						$(document).ready(function(){
							
							DraggableAdmin(<?=$row1['id']?>);
							$("#vre_id_<?=$row1['id']?>")
								.mousemove(function(){ //ko se miska premakne
									DraggableAdmin(<?=$row1['id']?>);
							})
						});
					</script>
					<?

					
					if ($length > 30) $class = 'ranking_long'; $class = 'ranking';
					//echo '<div class="variabla" id="variabla_'.$row1['id'].'">';
					echo '<div class="variabla" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'">';
					echo '<span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span>';
					echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
					echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
					echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
					echo ' <span class="faicon edit2 inline inline_edit"></span>';

					echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline '.$class.'" style="float:none" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>'.$row1['naslov'].'</div>';
					//koda za notranji IF
					if ($row1['if_id'] > 0) {
						echo ' <span class="red">*</span>';

						echo ' <span style="font-size:9px; cursor:pointer" id="if_notranji_'.$row1['id'].'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">';
						$this->conditions_display($row1['if_id']);
						echo '</span>';

						if ($this->condition_check($row1['if_id']) != 0)
							echo ' <span class="faicon warning icon-orange"></span>';
					}
					echo '</div>';

				}
				echo '</div>';
				
				//izris desne strani**************************************************************************************
				
				//***********za skatlasto obliko
				$display_drag_and_drop_new_look = ($spremenljivkaParams->get('display_drag_and_drop_new_look') ? $spremenljivkaParams->get('display_drag_and_drop_new_look') : 0); //za checkbox
				//***********za skatlasto obliko - konec

				echo '<div id="half2" class="dropzone" style="width: 49%; min-height:' . $size . 'px; float: right;">';
				
				$sql2 = sisplet_query("SELECT id, naslov, variable, vrstni_red FROM srv_grid WHERE spr_id='$spremenljivka' AND other=0 ORDER BY vrstni_red");
				$row2 = mysqli_fetch_array($sql2);
				
				echo	'<ul>';		
					for ($i = 1; $i <= $row['grids']; $i++) {
						if ($row2['vrstni_red'] == $i) {
							
							
							if ($this->lang_id != null) {
								$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row2['id']);
								if ($naslov != '') $row2['naslov'] = $naslov;
							}
						
							echo		'<li class="grid_variable_'.$row['id'].'" '.$show_variable_inline.'>
											<div class="grid_variable_inline" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'">' . $row2['variable'] . '</div>
										</li>'."\n";	//izpis "oznake" okvirja
							if($display_drag_and_drop_new_look == 0){			
								echo		'<li>
												<div class="grid_inline_droppable_title grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'" '.(strpos($row2['naslov'], $lang['srv_new_grid'])!==false || strpos($row2['naslov'], $lang1['srv_new_grid'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row2['naslov'] . '</div>
											</li>'."\n";	//izpis "naslova" okvirja
								echo		'<li>
												<div class="dragdrop_frame_grid"></div>
											</li>'."\n";	//izpis okvirja
							}else if($display_drag_and_drop_new_look == 1){
								echo	'<li>
											<div class="dragdrop_frame_grid_box"></div>
										</li>'."\n";	//izpis okvirja
								echo	'<li>
											<div class="grid_inline_droppable_title_box grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'" '.(strpos($row2['naslov'], $lang['srv_new_grid'])!==false || strpos($row2['naslov'], $lang1['srv_new_grid'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row2['naslov'] . '</div>
										</li>'."\n";	//izpis "naslova" okvirja
							}
										
							$row2 = mysqli_fetch_array($sql2);
						}
					}
					//***************************** missing-i
					#kateri missingi so nastavljeni
					$sql_grid_mv = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0");
					if (mysqli_num_rows($sql_grid_mv) > 0 ) {
						while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
							if ($this->lang_id != null) {
								$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row_grid_mv['id']);
								if ($naslov != '') $row_grid_mv['naslov'] = $naslov;
							}
							if($display_drag_and_drop_new_look == 0){
								echo		'<li>
												<div class="grid_inline_droppable_title grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row_grid_mv['id'].'" '.(strpos($row_grid_mv['naslov'], $lang['srv_new_grid'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $row_grid_mv['naslov'] . '</div>
											</li>'."\n";	//izpis "naslova" okvirja za missing
								echo		'<li>
												<div class="dragdrop_frame_grid"></div>
											</li>'."\n";	//izpis okvirja za missing
							}else if($display_drag_and_drop_new_look == 1){
								echo		'<li>
												<div class="dragdrop_frame_grid_box"></div>
											</li>'."\n";	//izpis okvirja za missing
								echo		'<li>
												<div class="grid_inline_droppable_title_box grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row_grid_mv['id'].'" '.(strpos($row_grid_mv['naslov'], $lang['srv_new_grid'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $row_grid_mv['naslov'] . '</div>
											</li>'."\n";	//izpis "naslova" okvirja za missing
							}
						}

					}
					//********************************** konec missing-i					
					
				echo	'</ul>';

				
				echo		'</div>'; //half2_$spremenljivka

				echo '<div class="clr"></div>';

			//***********************Drag and drop grid konec*************************************************************
			
			}
			//Image hot spot********************************************************************************************************
			elseif( ($row['tip'] == 6) && $row['enota'] == 10){	//image hot spot
				$this->vprasanje_hotspot($row['id'], $row['tip'], $row['orientation']);
			}
			// navadni gridi
			else{

				// Ce imamo vklopljeno omejitev pri multinumber
				if ($row['tip'] == 20) {
					if($row['num_useMin'] == 1 && $row['num_useMax'] == 1 && $row['vsota_min'] == $row['vsota_limit'])
						$limit = '('.$row['vsota_min'].')';
					elseif($row['num_useMin'] == 1 && $row['num_useMax'] == 1)
						$limit = '(min '.$row['vsota_min'].', max '.$row['vsota_limit'].')';
					elseif($row['num_useMin'] == 1)
						$limit = '(min '.$row['vsota_min'].')';
					elseif($row['num_useMax'] == 1)
						$limit = '(max '.$row['vsota_limit'].')';
					else
						$limit = '';

					if ($row['vsota_show'] == 1 && $limit != '') {
						echo '<span style="color: red;">'.$limit.'</span>';
					}
				}
				
				//************************ za izris traku
				$diferencial_trak = ($spremenljivkaParams->get('diferencial_trak') ? $spremenljivkaParams->get('diferencial_trak') : 0); //za checkbox
				$trak_num_of_titles = ($spremenljivkaParams->get('trak_num_of_titles') ? $spremenljivkaParams->get('trak_num_of_titles') : 0);
				
				//if($diferencial_trak == 1 && ($row['enota'] == 1 || $row['enota'] == 0)){	//ce je trak vklopljen in je diferencial ali klasicna tabela
				if($row['tip'] == 6 && $diferencial_trak == 1 && ($row['enota'] == 1 || $row['enota'] == 0)){	//ce je trak vklopljen in je diferencial ali klasicna tabela
					$trak_class = 'trak_class';
					$trak_class_input = 'trak_class_input';
					if($trak_num_of_titles != 0){
						$display_trak_num_of_titles = 'style="display:none;"';
						$trak_nadnaslov_table_td_width = 100 / $trak_num_of_titles;	//spremenljivka za razporeditev sirine nadnaslovov @ traku
					}
					$display_trak_num_of_titles = '';			
				}else{
					$trak_class = '';
					$trak_class_input = '';
					$display_trak_num_of_titles = 'style="display:none;"';
				}

				
				for($i = 1; $i <= $trak_num_of_titles; $i++){
					$trak_nadnaslov[$i] = ($spremenljivkaParams->get('trak_nadnaslov_'.$i.'') ? $spremenljivkaParams->get('trak_nadnaslov_'.$i.'') : $lang['srv_new_text']);
					if ($this->lang_id != null) {
						$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $i);
						if ($naslov != '') $trak_nadnaslov[$i] = $naslov;
					}					
				}

				//********************** za izris traku - konec
				

				if ($row['tip'] == 20) {	//ce je tabela s stevili
					if ($row['ranking_k'] == 1){	//ce so stevila v obliki drsnikov
						$slider_handle = ($spremenljivkaParams->get('slider_handle') ? $spremenljivkaParams->get('slider_handle') : 0);				
						$slider_window_number = ($spremenljivkaParams->get('slider_window_number') ? $spremenljivkaParams->get('slider_window_number') : 0);
						$slider_nakazi_odgovore = ($spremenljivkaParams->get('slider_nakazi_odgovore') ? $spremenljivkaParams->get('slider_nakazi_odgovore') : 0); //za checkbox
						$slider_MinMaxNumLabelNew = ($spremenljivkaParams->get('slider_MinMaxNumLabelNew') ? $spremenljivkaParams->get('slider_MinMaxNumLabelNew') : 0);
						$slider_MinMaxLabel = ($spremenljivkaParams->get('slider_MinMaxLabel') ? $spremenljivkaParams->get('slider_MinMaxLabel') : 0);				
						$slider_VmesneNumLabel = ($spremenljivkaParams->get('slider_VmesneNumLabel') ? $spremenljivkaParams->get('slider_VmesneNumLabel') : 0);
						$slider_VmesneDescrLabel = ($spremenljivkaParams->get('slider_VmesneDescrLabel') ? $spremenljivkaParams->get('slider_VmesneDescrLabel') : 0);				
						$slider_VmesneCrtice = ($spremenljivkaParams->get('slider_VmesneCrtice') ? $spremenljivkaParams->get('slider_VmesneCrtice') : 0);				
						$slider_handle_step = ($spremenljivkaParams->get('slider_handle_step') ? $spremenljivkaParams->get('slider_handle_step') : 5);
						$slider_MinLabel= ($spremenljivkaParams->get('slider_MinLabel') ? $spremenljivkaParams->get('slider_MinLabel') : "Minimum");
						$slider_MaxLabel= ($spremenljivkaParams->get('slider_MaxLabel') ? $spremenljivkaParams->get('slider_MaxLabel') : "Maximum");
						$slider_MinNumLabel = ($spremenljivkaParams->get('slider_MinNumLabel') ? $spremenljivkaParams->get('slider_MinNumLabel') : 0);
						$slider_MaxNumLabel = ($spremenljivkaParams->get('slider_MaxNumLabel') ? $spremenljivkaParams->get('slider_MaxNumLabel') : 100);
						$slider_MinNumLabelTemp = ($spremenljivkaParams->get('slider_MinNumLabelTemp') ? $spremenljivkaParams->get('slider_MinNumLabelTemp') : 0);
						$slider_MaxNumLabelTemp = ($spremenljivkaParams->get('slider_MaxNumLabelTemp') ? $spremenljivkaParams->get('slider_MaxNumLabelTemp') : 100);
						$MinLabel = ($spremenljivkaParams->get('MinLabel') ? $spremenljivkaParams->get('MinLabel') : $lang['srv_new_text']);
						$MaxLabel = ($spremenljivkaParams->get('MaxLabel') ? $spremenljivkaParams->get('MaxLabel') : $lang['srv_new_text']);
						
						$slider_DescriptiveLabel_defaults = ($spremenljivkaParams->get('slider_DescriptiveLabel_defaults') ? $spremenljivkaParams->get('slider_DescriptiveLabel_defaults') : 0);
						$slider_DescriptiveLabel_defaults_naslov1 = ($spremenljivkaParams->get('slider_DescriptiveLabel_defaults_naslov1') ? $spremenljivkaParams->get('slider_DescriptiveLabel_defaults_naslov1') : 0);
						
						if($prevajanje == true){
							$sqlString = "SELECT label, label_id FROM srv_language_slider WHERE ank_id='$this->anketa' AND spr_id='$spremenljivka' AND lang_id='$this->lang_id' ORDER BY label_id";
							$sqlSlider = sisplet_query($sqlString);
							//$custom = "1; 2; 3; 4";
							while ($rowPrevajanje = mysqli_fetch_array($sqlSlider)) {
								if($rowPrevajanje['label_id'] == 1){
									$MinLabel = $rowPrevajanje['label'];
								}elseif($rowPrevajanje['label_id'] == 2){
									$MaxLabel = $rowPrevajanje['label'];
								}elseif($rowPrevajanje['label_id'] == 0){
									$custom = $rowPrevajanje['label'];									
								}
							}
							$prevod = "prevajanje";
							
							if($slider_DescriptiveLabel_defaults && $custom==''){	//ce so prednalozene opisne labele drsnika in nimamo se prevoda
								$custom_ar = explode(';', $slider_DescriptiveLabel_defaults_naslov1);
							}else{	//ce so custom opisne labele drsnika
								$custom_ar = explode('; ', $custom);
							}
						}else if ($prevajanje == false){
							$prevod = "";
						}
						
						$slider_CalculatedNumofDescrLabels = $slider_MaxNumLabel - $slider_MinNumLabel;
						if($slider_CalculatedNumofDescrLabels>11){
							$slider_CalculatedNumofDescrLabels = 11;
						}
						$slider_NumofDescrLabels = ($spremenljivkaParams->get('slider_NumofDescrLabels') ? $spremenljivkaParams->get('slider_NumofDescrLabels') : $slider_CalculatedNumofDescrLabels);
						
						if($slider_VmesneDescrLabel){	//ce se ureja opisne labele drsnika
							$slider_NumofColspans = $slider_NumofDescrLabels + 1;
							$sliderTableStyle = 'style="table-layout: fixed; width: 100%"';
							$sliderTableColspan = "colspan=".$slider_NumofColspans." ";
						}else{
							$slider_NumofColspans = $slider_NumofDescrLabels;
						}
						
					}
				}else{
					$sliderTableStyle = "";
					$sliderTableColspan = "";
				}
				
				echo '      <table '.$sliderTableStyle.' class="grid_header_table '.($this->lang_id==null?'allow_new':'').'">';
				echo '        <thead>';
				

				//vrstica z nadnaslovi ###################################################################################													
				echo '        <thead>';
				

				//vrstica z nadnaslovi
				echo '<tr '.$display_trak_num_of_titles.' class="display_trak_num_of_titles_'.$row['id'].'">';



				echo '          <td></td>';
				echo '          <td></td>';


				for ($j = 1; $j <= $row['grids']; $j++) {
					
					//echo '<td>'.$j.'</td>';
					if($j == 1){
						$nadnaslov_floating = 'left';
					}
					else if($j == $row['grids']){
						$nadnaslov_floating = 'right';
					}
					else{
						$nadnaslov_floating = 'none';
					}
					
					echo '<td class="trak_inline_nadnaslov" grd="g_'.$j.'" ><div grid="'.$j.'" id="trak_nadnaslov_'.$j.'_'.$spremenljivka.'" name="trak_nadnaslov_'.$j.'" class="trak_inline_nadnaslov" style="float:'.$nadnaslov_floating.'; display:inline" contenteditable="'.(!$locked?'true':'false').'" '.(strpos($trak_nadnaslov[$j], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $trak_nadnaslov[$j] . '</div></td>';
				}
				echo '</tr>';	

				//vrstica z nadnaslovi - konec ###################################################################################

				// urejanje vrednosti
				echo '        <tr id="grid_variable_'.$row['id'].'" '.$show_variable_row.'>';
				echo '          <td></td>';
				echo '          <td></td>';

				$bg = 1;

				$sql2 = sisplet_query("SELECT id, variable, vrstni_red FROM srv_grid WHERE spr_id='$row[id]' AND other=0 ORDER BY vrstni_red");
				$row2 = mysqli_fetch_array($sql2);

				for ($i = 1; $i <= $row['grids']; $i++) {
					if ($row2['vrstni_red'] == $i) {
						echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_variable_inline" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'">' . $row2['variable'] . '</div></td>';
						$row2 = mysqli_fetch_array($sql2);
					} else {
						echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"></td>';
					}
				}
				echo '</tr>';

				//echo '        <tr>';
				echo '        <tr class="grid_naslovi_'.$row['id'].'">';
	 			if ($row['ranking_k'] != 1){	//ce ni slider
					echo '          <td>'.$grid_plus_minus.'</td>';
				}else{
					echo '          <td></td>';
				}
				echo '          <td></td>';

				$bg = 1;

				$sql2 = sisplet_query("SELECT id, naslov, vrstni_red FROM srv_grid WHERE spr_id='$row[id]' AND other=0 ORDER BY vrstni_red");
				$row2 = mysqli_fetch_array($sql2);

				for ($i = 1; $i <= $row['grids']; $i++) {
					if ($this->lang_id != null) {
						$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row2['id']);
						if ($naslov != '') $row2['naslov'] = $naslov;
					}
					if ($row2['vrstni_red'] == $i) {
						echo '          <td '.$sliderTableColspan.' class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_inline '.$trak_class_input.'" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'" '.(strpos($row2['naslov'], $lang['srv_new_grid'])!==false || strpos($row2['naslov'], $lang1['srv_new_grid'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row2['naslov'] . '</div></td>';
						$row2 = mysqli_fetch_array($sql2);
					} else {
						echo '          <td '.$sliderTableColspan.' class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"></td>';
					}
				}
				
				//************* za ureditev prilagajanja label stolpcev
				$custom_column_label_option = ($spremenljivkaParams->get('custom_column_label_option') ? $spremenljivkaParams->get('custom_column_label_option') : 1);
				echo '
					<script>
						change_custom_column_label_option(\'' . $row['grids'] . '\', \'' . $row['id'] . '\', \'' . $custom_column_label_option . '\');
					</script>				
				';				
				//************* za ureditev prilagajanja label stolpcev - konec
				//*********** trak - nadnaslovi
				if($row['tip'] == 6 && $diferencial_trak == 1 && ($row['enota'] == 1 || $row['enota'] == 0)){	//ce je trak vklopljen in je diferencial ali klasicna tabela
					//$trak_num_of_titles
					
					?>
							<script>
								$(document).ready(function(){
									trak_edit_num_titles(<?=$row['grids']?>, <?=$spremenljivka?>, <?=$trak_num_of_titles?>, <?=json_encode($trak_nadnaslov)?>);
								});
							</script>
					<?
				}
				//*********** trak - nadnaslovi - konec
								


				#kateri missingi so nastavljeni
				$sql_grid_mv = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0");
				if (mysqli_num_rows($sql_grid_mv) > 0 ) {
					echo '<td class=""></td>';
					while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
						if ($this->lang_id != null) {
							$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row_grid_mv['id']);
							if ($naslov != '') $row_grid_mv['naslov'] = $naslov;
						}
						
						//echo '<td class="grid_header11 ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row_grid_mv['id'].'"><div class="grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row_grid_mv['id'].'" '.(strpos($row_grid_mv['naslov'], $lang['srv_new_grid'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $row_grid_mv['naslov'] . '</div></td>';
						echo '<td class="grid_header11 ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row_grid_mv['id'].'"><div class="grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row_grid_mv['id'].'" '.(strpos($row_grid_mv['naslov'], $lang['srv_new_grid'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $row_grid_mv['naslov'] . '</div></td>';
					}

				}
					
				// diferencial
				if ($row['enota'] == 1 && $row['tip'] == 6) {
					echo '          <td></td>';
					echo '          <td></td>';
					echo '<td style="width:' . $spacesize*3 . '%"></td>';
				}
				else{
					if ($row['ranking_k'] != 1){	//ce ni slider							
						echo '<td style="width:' . $spacesize*3 . '%"></td>';
					}
				}
                
				echo '        </tr>';

				echo '</thead>';
				if ($row['ranking_k'] == 1) {	//ce je slider
					//min max labele nad drsnikom ##################################################################
					$displayMinMaxLabel = ($slider_MinMaxLabel == 0) ? ' style="display:none;"' : '';
					echo '<td colspan="2" '.$displayMinMaxLabel.'>'.$lang['slider_admin_minmax_label_desc'].'</td>';					
					echo '<td '.$displayMinMaxLabel.' colspan="'.($slider_NumofColspans).'" style="width:100%" >';
					
					echo '<table '.$displayMinMaxLabel.' style="width:100%">';
					echo '<tr>';								
					//echo '<td><div id="MinLabel_'.$spremenljivka.'" name="MinLabel" class="label_inline" style="float:none; padding: 2px 15px; display:inline" contenteditable="'.(!$locked?'true':'false').'" '.(strpos($MinLabel, $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $MinLabel . '</div></td>';								
					echo '<td align="left"><div id="MinLabel_'.$spremenljivka.'" name="MinLabel" class="label_inline" style="float:none; padding: 2px 15px; display:inline-block; word-break:break-all; width:60%" contenteditable="'.(!$locked?'true':'false').'" '.(strpos($MinLabel, $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $MinLabel . '</div></td>';								
					//echo '<td align="right"><div id="MaxLabel_'.$spremenljivka.'" name="MaxLabel" class="label_inline" style="float:none; padding: 2px 15px; display:inline" contenteditable="'.(!$locked?'true':'false').'" '.(strpos($MaxLabel, $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $MaxLabel . '</div></td>';
					echo '<td align="right"><div id="MaxLabel_'.$spremenljivka.'" name="MaxLabel" class="label_inline" style="float:none; padding: 2px 15px; display:inline-block; word-break:break-all; width:60%" contenteditable="'.(!$locked?'true':'false').'" '.(strpos($MaxLabel, $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $MaxLabel . '</div></td>';
					echo '</tr>';
					echo '</table>';
					
					//min max labele nad drsnikom - konec ##################################################################
				}
				//echo '<tbody class="'.($this->lang_id==null?'allow_new':'').'">';
				echo '<tbody id="slider_grid_'.$row['id'].'" class="'.($this->lang_id==null?'allow_new':'').'">';

				$bg++;
				
				$varIndex = 0;	//belezi, katera je trenuta vrstica podvprasanja  

				//parameter, ki belezi, ali se je izris droppables izvedel
				$izris_droppable_grid = ($spremenljivkaParams->get('izris_droppable_grid') ? $spremenljivkaParams->get('izris_droppable_grid') : 0);
			

				$sql1 = sisplet_query("SELECT id, naslov, naslov2, hidden, other, if_id FROM srv_vrednost WHERE spr_id='$row[id]'  ORDER BY vrstni_red");
				while ($row1 = mysqli_fetch_array($sql1)) {

					if ($this->lang_id != null) {
						save('lang_id', $this->lang_id);
						
						$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
						if ($naslov != '') $row1['naslov'] = $naslov;
						
						$naslov2 = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id'], true);
						if ($naslov2 != '') $row1['naslov2'] = $naslov2;
					}
					
					if ($row['ranking_k'] == 1) {	//ce je slider
						$style = 'style="height:150px;"';
					}else{
						$style = '';
					}
					
					echo '        <tr class="variabla" '.$style.'  id="variabla_'.$row1['id'].'">';
					echo '          <td class="grid_question ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" '.($gridWidth == -1 ? '' : $css ).' id="'.$row1['id'].'">';
					echo '<span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span>';
					echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
                    echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
                    echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
					echo ' <span class="faicon edit2 inline inline_edit"></span>';
					
					if($row['enota'] != 9){					

						echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'].'</div>';
					}
					
					if ($row1['if_id'] > 0) {
						echo ' <span class="red" style="cursor:pointer" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">*</span>';
						if ($this->condition_check($row1['if_id']) != 0)
							echo ' <span class="faicon warning icon-orange"></span>';
					}

					if ($row1['other'] == 1){
						$otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
						$otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

						if ($otherHeight > 1)
							echo '<textarea name="" rows="'.$otherHeight.'" style="max-width:50%; '.($otherWidth != -1 ? ' width:'.$otherWidth.'%;' : '').'" disabled="disabled"></textarea>';
						else
							echo '<input type="text" name="" value="" style="max-width:50%; '.($otherWidth != -1 ? ' width:'.$otherWidth.'%;' : '').'" disabled="disabled" />';
					}

					echo '</td>';

					//echo '<td style="width:' . $spacesize . '%"></td>';
					echo '<td></td>';
					
					

					//razlicni vnosi glede na tip multigrida
					for ($i = 1; $i <= $row['grids']; $i++) {

						if($row['tip'] == 6) {
							if($row['enota'] != 9){	//ce ni postavitev drag and drop, pokazi radio buttone
								
								$sqlTrak = sisplet_query("SELECT spr_id, variable, vrstni_red FROM srv_grid WHERE spr_id='$row[id]' AND other=0 AND id='$i'");
								$rowTrak = mysqli_fetch_array($sqlTrak);							
							
								echo '<td onClick="trak_change_bg(this, '.$diferencial_trak.', '.$rowTrak['spr_id'].');" id="trak_tbl_' . $row1['id'] . '_'.$rowTrak['vrstni_red'].'" style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . ' '.$trak_class.'">
								        <input type="radio" class="enka-admin-custom '.$trak_class_input.'" name="foo_' . $row1['id'] . '" id="foo_' . $row1['id'] . '_'.$rowTrak['vrstni_red'].'" vre_id = '.$row1['id'].'  value="" />';
								if($row['enota'] == 11){
                                    echo '<span class="enka-vizualna-skala siv-'.$row['grids']. $i.'"></span>';
                                }elseif($row['enota'] == 12){
                                    echo '<span class="enka-custom-radio '.(!empty($spremenljivkaParams->get('customRadio')) ? $spremenljivkaParams->get('customRadio') : 'star').'"></span>';
                                }else {
                                    echo '<span class="enka-checkbox-radio"></span>';
                                }
								
								if($diferencial_trak == 1 && ($row['enota'] == 1 || $row['enota'] == 0)){	//ce je trak vklopljen in je diferencial ali klasicna tabela, dodaj se label z ustreznimi stevilkami za trak
									echo '<label class="radio-button-label"><span class="radio-trak-label" data-position="'.($i - 1).'">'.$rowTrak['variable'].'</span></label>';
								}
								
								echo '</td>';
							}							

						} elseif($row['tip'] == 16) {
							echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="checkbox" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';

						} elseif ($row['tip'] == 19) {
							echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><textarea style="width:'.$taWidth.'em; height:'.($taHeight*12).'px" name="foo_' . $row1['id'] . '"></textarea></td>';

						} elseif ($row['tip'] == 20) {

							//echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="text" style="width:'.$taWidth.'em;" name="foo_' . $row1['id'] . '" value="" />';

							if ($row['ranking_k'] == 1) {	//ce je slider

 								echo '          <td colspan="'.($slider_NumofColspans).'" style="width:100%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="text" style="width:'.$taWidth.'em;" name="foo_' . $row1['id'] . '" value="" />';
								
								echo '<div style="width:100%">';


								$default_value = round( ($slider_MaxNumLabel-$slider_MinNumLabel) / 2) + $slider_MinNumLabel;

								
								echo '<div class="sliderText" id="sliderTextbranching_'.$spremenljivka.'_'.$row1['id'].'">'.$default_value.'</div>';

								echo '<div id="sliderbranching_'.$prevod.$spremenljivka.'_'.$row1['id'].'" class="slider"></div>';							
								
								echo '</div>';
								
								
 								//za custom opisne labele
								//moznosti urejanja opisnih label drsnika
								if($slider_VmesneDescrLabel){									
									for($i=1; $i<=$slider_NumofDescrLabels; $i++){						
										if($prevajanje == false){
										$slider_CustomDescriptiveLabelsTmp = ($spremenljivkaParams->get('slider_Labela_opisna_'.$i) ? $spremenljivkaParams->get('slider_Labela_opisna_'.$i) : '');
										}else if ($prevajanje == true){									
											$slider_CustomDescriptiveLabelsTmp = $custom_ar[$i-1];
										}
										
										$slider_CustomDescriptiveLabelsTmp = preg_replace("/\s|&nbsp;/",' ',$slider_CustomDescriptiveLabelsTmp);  //za odstranitev morebitnih presledkov, ki lahko delajo tezave pri polju za drsnik										
										if($i == 1){
											$slider_CustomDescriptiveLabels = $slider_CustomDescriptiveLabelsTmp;
										}else{
											$slider_CustomDescriptiveLabels .= "; ".$slider_CustomDescriptiveLabelsTmp;
										}									
									}
								}
								//za custom opisne labele - konec	 

								if($prevajanje == false){
									?>
									<script>
										$(function() {
											slider_edit_grid_init(<?=$spremenljivka?>, <?=$row1['id']?>, <?=$slider_MinNumLabel?>, <?=$slider_MaxNumLabel?>, <?=$default_value?>, <?=$slider_VmesneNumLabel?>, <?=$slider_VmesneCrtice?>, <?=$slider_MinMaxNumLabelNew?>, <?=$slider_handle?>, <?=$slider_handle_step?>, <?=$slider_window_number?>, '<?=$slider_DescriptiveLabel_defaults_naslov1?>', <?=$slider_DescriptiveLabel_defaults?>, <?=$slider_nakazi_odgovore?>, <?=$slider_MinNumLabelTemp?>, <?=$slider_MaxNumLabelTemp?>, <?=$slider_VmesneDescrLabel?>, '<?=$slider_CustomDescriptiveLabels?>');
											});
									</script>
									<?
								}
								else if ($prevajanje == true){
									?>
									<script>
										$(function() {
											slider_edit_grid_init_prevajanje(<?=$spremenljivka?>, <?=$row1['id']?>, <?=$slider_MinNumLabel?>, <?=$slider_MaxNumLabel?>, <?=$default_value?>, <?=$slider_VmesneNumLabel?>, <?=$slider_VmesneCrtice?>, <?=$slider_MinMaxNumLabelNew?>, <?=$slider_handle?>, <?=$slider_handle_step?>, <?=$slider_window_number?>, '<?=$slider_DescriptiveLabel_defaults_naslov1?>', <?=$slider_DescriptiveLabel_defaults?>, <?=$slider_nakazi_odgovore?>, <?=$slider_MinNumLabelTemp?>, <?=$slider_MaxNumLabelTemp?>, <?=$slider_VmesneDescrLabel?>, '<?=$slider_CustomDescriptiveLabels?>');
											});
									</script>
									<?									
								}
								//Zadnja bela celica kjer se nahajajo ikone za mouseover, ko je slider
								if($slider_VmesneDescrLabel){
									$mouseOverStyle = '';
								}else{
									$mouseOverStyle = 'style="min-width:80px;"';
								}
								echo '<td class="white" '.$mouseOverStyle.'></td>';
								//echo '        </tr>';
								//Zadnja bela celica kjer se nahajajo ikone za mouseover, ko je slider - konec
								
							}else{
								echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="text" style="width:'.$taWidth.'em;" name="foo_' . $row1['id'] . '" value="" />';
							}

							echo '</td>';
						}
					}
					
					$izris_droppable_grid = 1;	//izris ene vrstice droppables se je izvedel
                    
					#kateri missingi so nastavljeni
					$sql_grid_mv = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0");
					if (mysqli_num_rows($sql_grid_mv) > 0 ) {
						//echo '<td style="width:' . $spacesize . '%"></td>';
						echo '<td></td>';
						while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
							if($row['tip'] == 6) {
								echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
							} elseif($row['tip'] == 16) {
								echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="checkbox" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
							} else {
								echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_missing_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
							}

						}
					}

					// diferencial
					if ($row['enota'] == 1 && $row['tip'] == 6) {
						echo '          <td></td>';
						echo '          <td style="text-align:left;" class="grid_question ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" id="f_'.$row1['id'].'_2"><div class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'_2" '.(strpos($row1['naslov2'], $lang['srv_new_vrednost'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $row1['naslov2'] . '</div></td>';
					}

					if ($row['ranking_k'] != 1) {	//ce ni slider
						// Zadnja bela celica kjer se nahajajo ikone za mouseover
						echo '<td class="white" style="min-width:80px;"></td>';
						
						echo '        </tr>';					
					}
					
					$bg++;
					
					$varIndex++;
					if($diferencial_trak == 1 && ($row['enota'] == 1 || $row['enota'] == 0) ){	// ce je diferencial ali klasicna tabela s trakom, dodaj se prazno vrstico med razlicnimi odgovori
						echo '<tr><td></td></tr>' . "\n";
					}
				}

				echo '      </tbody>';
				echo '      </table>';
				
				//ce je drsnik in moramo urediti opisne labele #########################################################			
				if ($row['ranking_k'] == 1 && $slider_VmesneDescrLabel){	//ce je slider in se ureja opisne labele
					
					echo '<div style="padding: 50px;"></div>'; //za urediti prostor med zadnjimi opisnimi labelami in njihovim urejanjem
					
					if($slider_DescriptiveLabel_defaults&&$prevajanje==false){
						$tabelaOpisneStyle = 'display:none;';
					}else{
						$tabelaOpisneStyle = '';
					}

					echo '<table style="width: 100%; '.$tabelaOpisneStyle.'" id="inline_opisne_labele_'.$row['id'].$prevod.'" >';
					echo '<thead><tr><td align="center" colspan="'.($slider_NumofDescrLabels).'">'.$lang['slider_custom_labels_msg'].'</td></tr></thead>';
					
					//moznosti urejanja opisnih label drsnika
					echo '<tbody id="edit_opisne_labele_'.$row['id'].$prevod.'" style="display: none">';
					echo '<tr>';					
					echo '<td><button id="update_opisne_labele'.$prevod.'" type="button" onclick=" updateSliderOpisneLabele('.$row['id'].', '.$slider_NumofDescrLabels.', \''.$prevod.'\', \'grid\')">'.$lang['slider_custom_labels_update'].'</button></td>';	//gumb za posodobitev custom opisnih label
					echo '</tr>';
					echo '<tr>';
					$sirinaStolpcev = 100/$slider_NumofDescrLabels;					
					for($j = 1; $j <= $slider_NumofDescrLabels; $j++){	//ostali stolpci									
						if($prevajanje == false){
							$slider_Labela_opisna[$j] = ($spremenljivkaParams->get('slider_Labela_opisna_'.$j.'') ? $spremenljivkaParams->get('slider_Labela_opisna_'.$j.'') : $lang['srv_new_text']);										
						}else if ($prevajanje == true){
							$slider_Labela_opisna[$j] = $custom_ar[$j-1];
						}									
						
						echo '<td align="center" style="width:'.$sirinaStolpcev.'%;" ><div class="inline_opisne_labele'.$prevod.'" id="slider_Labela_opisna_'.$j.'_'.$spremenljivka.$prevod.'" name="slider_Labela_opisna_'.$j.$prevod.'" labelaVreId='.$row1['id'].' style="float:none; display:inline" contenteditable="'.(!$locked?'true':'false').'" '.(strpos($slider_Labela_opisna[$j], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $slider_Labela_opisna[$j] . '</div></td>';
					}					
					echo '</tr>';
					echo '</tbody>';
					//moznosti urejanja ureditev opisnih label drsnika - konec
					
					//moznosti brez urejanja opisnih label drsnika
					echo '<tbody id="preview_opisne_labele_'.$row['id'].$prevod.'">';
					echo '<tr>';					
					echo '<td><button id="edit_opisne_labele_button_'.$prevod.$row['id'].'" type="button" onclick="switchSliderOpisneLabeleEditMode('.$row['id'].', \''.$prevod.'\'); $(this).closest(\'div.spremenljivka_content\').find(\'div.spremenljivka_settings\').click(); return false;">'.$lang['edit3'].'</button></td>';	//gumb za vklop posodabljanja custom opisnih label
					echo '</tr>';
					echo '<tr>';
					$sirinaStolpcev = 100/$slider_NumofDescrLabels;
					for($j = 1; $j <= $slider_NumofDescrLabels; $j++){	//ostali stolpci
						if($prevajanje == false){
							$slider_Labela_opisna[$j] = ($spremenljivkaParams->get('slider_Labela_opisna_'.$j.'') ? $spremenljivkaParams->get('slider_Labela_opisna_'.$j.'') : $lang['srv_new_text']);										
						}else if ($prevajanje == true){
							$slider_Labela_opisna[$j] = $custom_ar[$j-1];
						}
						
						echo '<td align="center" style="width:'.$sirinaStolpcev.'%;" ><div class="inline_opisne_labele" id="slider_Labela_opisna_'.$j.$prevod.'_'.$spremenljivka.'" name="slider_Labela_opisna_'.$j.$prevod.'" labelaVreId='.$row1['id'].' style="float:none; display:inline"  '.(strpos($slider_Labela_opisna[$j], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $slider_Labela_opisna[$j] . '</div></td>';
					}					
					echo '</tr>';
					echo '</tbody>';
					//moznosti brez urejanja ureditev opisnih label drsnika - konec
					
					//js koda za ustrezno skrivanje in prikazovanje delov za urejanje custom opisnih label
					?>
					<script>
						$(document).ready(function(){
							var edit_mode = $("#vprasanje_edit form").attr("name");							
							if (edit_mode == 'vprasanje_edit') {	//ce je odprto okno z nastavitvami
								$('#preview_opisne_labele_'+<?=$row['id']?>).css('display', 'none');
								$('#edit_opisne_labele_'+<?=$row['id']?>).css('display', 'block');
							} else {	//drugace
								$('#preview_opisne_labele_'+<?=$row['id']?>).css('display', 'block');
								$('#edit_opisne_labele_'+<?=$row['id']?>).css('display', 'none');
							}

							

						});					
					</script>
					<?
					//js koda za ustrezno skiranje in prikazovanje delov za urejanje custom opisnih label - konec
					
					echo ' </table>';
				}				
				//ce je drsnik in moramo urediti opisne labele - konec ##################################################	
			}

		// textbox -- not any more
		} elseif ($row['tip'] == 4) {

			$taSize = ($spremenljivkaParams->get('taSize') ? $spremenljivkaParams->get('taSize') : 1);
			$taWidth = ($spremenljivkaParams->get('taWidth') ? $spremenljivkaParams->get('taWidth') : -1);
			//default sirina
			if($taWidth == -1)
				$taWidth = 30;

			if ($taSize > 1)
				echo '<textarea name="foo_' . $row['id'] . '" rows="' . $taSize . '" style="width: '.$taWidth.'em;" disabled="disabled"></textarea>';
			else
				echo '<input type="text" style="width: '.$taWidth.'em;" disabled="disabled"/>';

			// dodamo checkboxe za druga polja
			$sql1 = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='$row[id]' AND other>0");
			while ($row1 = mysqli_fetch_array($sql1)) {
				echo '<div class="variabla' . $cssFloat . '">';
				echo '<input type="checkbox" name="foo_' . $row['id'] . '" id="foo_' . $row1['id'] . '" value="" class="enka-admin-custom ' . (($row['checkboxhide'] == 1) ? 'en' : '') . '"/>';
                echo '<span class="enka-checkbox-radio"></span>';
				echo '<label for="foo_' . $row1['id'] . '">' . $row1['naslov'] . '</label>';
				echo '</div>';
			}
		}

		// textbox*
		elseif ($row['tip'] == 21) {
                    
                        if($row['num_useMin'] == 1 && $row['num_useMax'] == 1 && $row['vsota_min'] == $row['vsota_limit'])
				$limit = '('.$lang['srv_text_length_char_num'].$row['vsota_min'].')';
			elseif($row['num_useMin'] == 1 && $row['num_useMax'] == 1)
				$limit = '('.$lang['srv_text_length_char_num'].'min '.$row['vsota_min'].', max '.$row['vsota_limit'].')';
			elseif($row['num_useMin'] == 1)
				$limit = '('.$lang['srv_text_length_char_num'].'min '.$row['vsota_min'].')';
			elseif($row['num_useMax'] == 1)
				$limit = '('.$lang['srv_text_length_char_num'].'max '.$row['vsota_limit'].')';
			else
				$limit = '';

			$taSize = ($spremenljivkaParams->get('taSize') ? $spremenljivkaParams->get('taSize') : 1);
			$taWidth = ($spremenljivkaParams->get('taWidth') ? $spremenljivkaParams->get('taWidth') : -1);
			//default sirina
			if($taWidth == -1)
				$taWidth = 30;

			echo '<table class="text_vrednost" style="width: 100%; text-align: left">';
			if($row['orientation'] != 3)
				echo '<tr>';

			$_others = array();
			$sql1 = sisplet_query("SELECT id, naslov, variable, size, other, hidden, naslov2 FROM srv_vrednost WHERE spr_id='$row[id]' AND vrstni_red > 0 ORDER BY vrstni_red");
			while ($row1 = mysqli_fetch_array($sql1)) {
				
				if ((int)$row1['other'] == 0) {

					// sirina celice td
					$cell = $row['text_kosov'] == 1 ? 100 : $row1['size'] ;
					// sirina vnosnega polja
					$input = $taWidth;

                    if ($this->lang_id != null) {
                        save('lang_id', $this->lang_id);
                        $naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
                        if ($naslov != '') $row1['naslov'] = $naslov;
                    }

					if($row['orientation'] == 3)
						echo '<tr>';
                        
					echo '<td class="grid_question" style="width: '.$cell.'%; text-align:left" id="f_'.$row1['id'].'">';

					if($row['text_orientation'] == 1 || $row['text_orientation'] == 3){
						echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline" style="float:none; display:inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>'.$row1['naslov'].'</div>';
						if ($row['text_orientation'] == 3)
							echo '<br />';
					}
					if ($taSize > 1)
						echo '<textarea id="txt_f_' .$row1['id'] .'" vre_id="'.$row1['id'].'_2" name="foo_' . $row['id'] . '" rows="' . $taSize . '" style="width: ' . $input . '%; margin-left:15px" ' .($locked?'disabled="disabled" ':' class="textfield_editable"  contenteditable="true" ETF="true"') .'>' .$row1['naslov2'] .'</textarea>';
					else
						echo '<input id="txt_f_' .$row1['id'] .'" vre_id="'.$row1['id'].'_2" type="text" style="width: ' . $input . '%; margin-left:15px" ' .($locked?'disabled="disabled" ':' class="textfield_editable" contenteditable="true" ETF="true"') .' value="' .$row1['naslov2'] .'" />';
                                        
					if($row['text_orientation'] == 2){
						echo '<br /><div id="vre_id_'.$row1['id'].'" class="vrednost_inline" style="float:none; display:inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>'.$row1['naslov'].'</div>';
					}
					echo '</td>';
					if($row['orientation'] == 3)
						echo '</tr>';
				}
				else {
					# imamo opcijo drugo prikažemo kot checkbox
					$_others[] = $row1;
				}
			}
			if($row['orientation'] != 3)
				echo '</tr>';
			echo '</table>';
                        
                        if ($row['vsota_show'] == 1 && $limit != '')
                                echo '<span id="variabla_limit_'.$spremenljivka.'" class="variabla_limit '.$cssFloat.'" style="padding: 0 15px 0 10px;">'.$limit.'</span>';

			if (count($_others)> 0 ) {
				foreach ($_others AS $oKey => $_other) {

				if ($this->lang_id != null) {
					save('lang_id', $this->lang_id);
					$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($_other['id']);
					if ($naslov != '') $_other['naslov'] = $naslov;
				}
				
				// Ce je variabla ne vem in imamo vklopljen prikaz ob opozorilu -> rdec
				$missing_warning = '';
				if(($_other['variable'] == '-97' && $row['alert_show_97'] > 0)
					|| ($_other['variable'] == '-98' && $row['alert_show_98'] > 0)
					|| ($_other['variable'] == '-99' && $row['alert_show_99'] > 0)){
					$missing_warning = ' red';
				}

				echo '<div class="variabla' . $cssFloat . '" id="variabla_'.$_other['id'].'" other="'.$_other['other'].'">';
				echo ' <input type="checkbox" name="foo_' . $_other['id'] . '" id="foo_' . $_other['id'] . '" value="" class="' . (($_other['checkboxhide'] == 1) ? 'hidden' : '') . '" onClick="return false;"/>';
				echo '<div id="vre_id_'.$_other['id'].'" class="vrednost_inline '.$missing_warning.'" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$_other['id'].'">' . $_other['naslov'] . '</div>';
                echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
                echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
                echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
                echo ' <span class="faicon edit2 inline inline_edit"></span>';

				echo '</div>';

				}
			}
		}

		// number
		elseif ($row['tip'] == 7) {	

			if($row['num_useMin'] == 1 && $row['num_useMax'] == 1 && $row['vsota_min'] == $row['vsota_limit'])
				$limit = '('.$row['vsota_min'].')';
			elseif($row['num_useMin'] == 1 && $row['num_useMax'] == 1)
				$limit = '(min '.$row['vsota_min'].', max '.$row['vsota_limit'].')';
			elseif($row['num_useMin'] == 1)
				$limit = '(min '.$row['vsota_min'].')';
			elseif($row['num_useMax'] == 1)
				$limit = '(max '.$row['vsota_limit'].')';
			else
				$limit = '';

			if($row['size'] == 2){
				if($row['num_useMin2'] == 1 && $row['num_useMax2'] == 1 && $row['num_min2'] == $row['num_max2'])
					$limit2 = '('.$row['num_min2'].')';
				elseif($row['num_useMin2'] == 1 && $row['num_useMax2'] == 1)
					$limit2 = '(min '.$row['num_min2'].', max '.$row['num_max2'].')';
				elseif($row['num_useMin2'] == 1)
					$limit2 = '(min '.$row['num_min2'].')';
				elseif($row['num_useMax2'] == 1)
					$limit2 = '(max '.$row['num_max2'].')';
				else
					$limit2 = '';
			}

			$taWidth = ($spremenljivkaParams->get('taWidth') ? $spremenljivkaParams->get('taWidth') : -1);
			//default sirina
			if($taWidth == -1)
				$taWidth = 10;

			$cssFloat = ' floatLeft';


			$sql1 = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='$row[id]' AND other = 0 ");
			$row1 = mysqli_fetch_array($sql1);
			$sqlOther = sisplet_query("SELECT id, naslov, other FROM srv_vrednost WHERE spr_id='$row[id]' AND vrstni_red>0 AND other != 0");
			$num_other = mysqli_num_rows($sqlOther);
			$num_all = $num_other+$row['size'];

			$cell_width = 'width:'.(80/$num_all).'% ';

			if ( $row['ranking_k'] == '0' ) {
			
				echo '<div class="variabla' . $cssFloat . '" style="'.$cell_width.'clear:none" id="variabla_'.$row1['id'].'">';

				if ($this->lang_id != null) {
					save('lang_id', $this->lang_id);
					$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
					if ($naslov != '') $row1['naslov'] = $naslov;
				}
				
				if($row['enota'] == 1)
					echo '      <div id="vre_id_'.$row1['id'].'" class="vrednost_inline" style="float:none; display:inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $row1['naslov'] . '</div>
								<input type="text" name="foo_' . $row['id'] . '" style="width: '.$taWidth.'em; float:none" id="foo_'.$row1['id'].'">';
				elseif($row['enota'] == 2)
					echo '      <input type="text" name="foo_' . $row['id'] . '" style="width: '.$taWidth.'em; float:none" id="foo_'.$row1['id'].'" > <div id="vre_id_'.$row1['id'].'" class="vrednost_inline" style="float:none; display:inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_text'])!==false || $this->lang_id!=null?' default="1"':'').'>' . $row1['naslov'].'</div>';
				else
					echo '      <input type="text" name="foo_' . $row['id'] . '" style="width: '.$taWidth.'em; float:none" id="foo_'.$row1['id'].'" >';
				echo '</div>';

				// Omejitev vnosa
				if ($row['orientacija'] == 1) {
					echo '<div class="clr">ccc</div>';
					$cssFloat = '';
				}else {
					$cssFloat = ' floatLeft';
				}
				if ($row['vsota_show'] == 1 && $limit != '') {
					echo '<span id="variabla_limit_'.$spremenljivka.'" class="variabla_limit '.$cssFloat.'" style="padding: 0 15px 0 10px;">'.$limit.'</span>';
				} elseif($row['size'] != 2 && $limit == '') {
					echo '<span id="variabla_limit_'.$spremenljivka.'" class="variabla_limit '.$cssFloat.' editingOnly" style="padding-left: 10px;">'.$lang['srv_number_text'].'</span>';
				}

				$cssFloat = ' floatLeft';

				if ($row['size'] == 2) {
				
					$row1 = mysqli_fetch_array($sql1);
					
					if ($this->lang_id != null) {
						save('lang_id', $this->lang_id);
						$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
						if ($naslov != '') $row1['naslov'] = $naslov;
					}
					
					echo '<div class="variabla' . $cssFloat . '" style="width:'.$cell_width.'% !important; clear:none" id="variabla_'.$row1['id'].'">';
					if($row['enota'] == 1){
						if($taWidth > 40)
							echo '<br />';
						echo '      <div id="vre_id_'.$row1['id'].'" class="vrednost_inline" style="float:none; display:inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $row1['naslov'] . '</div>
									<input type="text" name="foo_' . $row['id'] . '" style="width: '.$taWidth.'em; float:none" id="foo_'.$row1['id'].'">';
					}
					elseif($row['enota'] == 2)
						echo '      <input type="text" name="foo_' . $row['id'] . '" style="width: '.$taWidth.'em; float:none" id="foo_'.$row1['id'].'"> <div id="vre_id_'.$row1['id'].'" class="vrednost_inline" style="float:none; display:inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $row1['naslov'].'</div>';
					else
						echo '      <input type="text" name="foo_' . $row['id'] . '" style="width: '.$taWidth.'em; float:none" id="foo_'.$row1['id'].'">';
					echo '</div>';

					//Omejitev vnosa
					if ($row['orientacija'] == 1) {
						echo '<div class="clr">ccc</div>';
						$cssFloat = '';
					}else {
						$cssFloat = ' floatLeft';
					}
					if ($row['vsota_show'] == 1 && $limit2 != '') {
						echo '<span id="variabla_limit_'.$spremenljivka.'" class="variabla_limit '.$cssFloat.'" style="padding-left: 10px;">'.$limit2.'</span>';
					} elseif($limit == '' && $limit2 == '') {
						echo '<span id="variabla_limit_'.$spremenljivka.'" class="variabla_limit '.$cssFloat.' editingOnly" style="padding-left: 10px;">'.$lang['srv_number_text'].'</span>';
					}
				}

			}//ranking_k == 0
			
			if ( $row['ranking_k'] == '1' ) {
			
				echo '<div id="variabla_'.$row1['id'].'">';
				
				$slider_handle = ($spremenljivkaParams->get('slider_handle') ? $spremenljivkaParams->get('slider_handle') : 0);				
				$slider_window_number = ($spremenljivkaParams->get('slider_window_number') ? $spremenljivkaParams->get('slider_window_number') : 0);
				$slider_nakazi_odgovore = ($spremenljivkaParams->get('slider_nakazi_odgovore') ? $spremenljivkaParams->get('slider_nakazi_odgovore') : 0); //za checkbox
				$slider_MinMaxNumLabelNew = ($spremenljivkaParams->get('slider_MinMaxNumLabelNew') ? $spremenljivkaParams->get('slider_MinMaxNumLabelNew') : 0);
				$slider_MinMaxLabel = ($spremenljivkaParams->get('slider_MinMaxLabel') ? $spremenljivkaParams->get('slider_MinMaxLabel') : 0);				
				$slider_VmesneNumLabel = ($spremenljivkaParams->get('slider_VmesneNumLabel') ? $spremenljivkaParams->get('slider_VmesneNumLabel') : 0);
				$slider_VmesneDescrLabel = ($spremenljivkaParams->get('slider_VmesneDescrLabel') ? $spremenljivkaParams->get('slider_VmesneDescrLabel') : 0);				
				$slider_VmesneCrtice = ($spremenljivkaParams->get('slider_VmesneCrtice') ? $spremenljivkaParams->get('slider_VmesneCrtice') : 0);				
				$slider_handle_step = ($spremenljivkaParams->get('slider_handle_step') ? $spremenljivkaParams->get('slider_handle_step') : 5);
				$slider_MinLabel= ($spremenljivkaParams->get('slider_MinLabel') ? $spremenljivkaParams->get('slider_MinLabel') : "Minimum");
				$slider_MaxLabel= ($spremenljivkaParams->get('slider_MaxLabel') ? $spremenljivkaParams->get('slider_MaxLabel') : "Maximum");
				$slider_MinNumLabel = ($spremenljivkaParams->get('slider_MinNumLabel') ? $spremenljivkaParams->get('slider_MinNumLabel') : 0);
				$slider_MaxNumLabel = ($spremenljivkaParams->get('slider_MaxNumLabel') ? $spremenljivkaParams->get('slider_MaxNumLabel') : 100);
				$slider_MinNumLabelTemp = ($spremenljivkaParams->get('slider_MinNumLabelTemp') ? $spremenljivkaParams->get('slider_MinNumLabelTemp') : 0);
				$slider_MaxNumLabelTemp = ($spremenljivkaParams->get('slider_MaxNumLabelTemp') ? $spremenljivkaParams->get('slider_MaxNumLabelTemp') : 100);
				
				$MinLabel = ($spremenljivkaParams->get('MinLabel') ? $spremenljivkaParams->get('MinLabel') : $lang['srv_new_text']);
				$MaxLabel = ($spremenljivkaParams->get('MaxLabel') ? $spremenljivkaParams->get('MaxLabel') : $lang['srv_new_text']);
				
				if ($prevajanje == true){
					$sqlString = "SELECT label, label_id FROM srv_language_slider WHERE ank_id='$this->anketa' AND spr_id='$spremenljivka' AND lang_id='$this->lang_id' ORDER BY label_id";
					$sqlSlider = sisplet_query($sqlString);
					
					while ($rowPrevajanje = mysqli_fetch_array($sqlSlider)) {
						if($rowPrevajanje['label_id'] == 1){
							$MinLabel = $rowPrevajanje['label'];
						}elseif($rowPrevajanje['label_id'] == 2){
							$MaxLabel = $rowPrevajanje['label'];
						}elseif($rowPrevajanje['label_id'] == 0){
							$custom = $rowPrevajanje['label'];
						}
					}
				}				
				
				$slider_NumofDescrLabels = ($spremenljivkaParams->get('slider_NumofDescrLabels') ? $spremenljivkaParams->get('slider_NumofDescrLabels') : 5);
				$slider_DescriptiveLabel_defaults = ($spremenljivkaParams->get('slider_DescriptiveLabel_defaults') ? $spremenljivkaParams->get('slider_DescriptiveLabel_defaults') : 0);
				$slider_DescriptiveLabel_defaults_naslov1 = ($spremenljivkaParams->get('slider_DescriptiveLabel_defaults_naslov1') ? $spremenljivkaParams->get('slider_DescriptiveLabel_defaults_naslov1') : 0);
				$displayMinMaxLabel = ($slider_MinMaxLabel == 0) ? ' style="display:none;"' : '';
				$slider_labele_podrocij = ($spremenljivkaParams->get('slider_labele_podrocij') ? $spremenljivkaParams->get('slider_labele_podrocij') : 0); //za checkbox
				$display_labele_podrocij = ($slider_labele_podrocij == 0) ? ' style="display:none;"' : '';
				$slider_StevLabelPodrocij = ($spremenljivkaParams->get('slider_StevLabelPodrocij') ? $spremenljivkaParams->get('slider_StevLabelPodrocij') : 3);
				
				$slider_table_td_width = 100 / $slider_StevLabelPodrocij;	//spremenljivka za razporeditev sirine sliderja po podrocjih
				
				for($i = 1; $i <= $slider_StevLabelPodrocij; $i++){
					$slider_Labela_podrocja[$i] = ($spremenljivkaParams->get('slider_Labela_podrocja_'.$i.'') ? $spremenljivkaParams->get('slider_Labela_podrocja_'.$i.'') : $lang['srv_new_text']);
				}
				
				echo '<div style="width:100%">';

				$default_value = round( ($slider_MaxNumLabel-$slider_MinNumLabel) / 2) + $slider_MinNumLabel;
				
				//tabela za labeli nad min in max
				echo '<table '.$displayMinMaxLabel.' style="width:85%">';
                echo '<tr>';
                
				echo '<td><div id="MinLabel_'.$spremenljivka.'" name="MinLabel" class="label_inline" style="float:none; display:inline" contenteditable="'.(!$locked?'true':'false').'" '.(strpos($MinLabel, $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $MinLabel . '</div></td>';

				echo '<td align="right"><div id="MaxLabel_'.$spremenljivka.'" name="MaxLabel" class="label_inline" style="float:none; display:inline" contenteditable="'.(!$locked?'true':'false').'" '.(strpos($MaxLabel, $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $MaxLabel . '</div></td>';

				echo '</tr>';
				echo '</table>';
				//tabela za labeli nad min in max - konec
				
				echo '<div class="sliderText" id="sliderTextbranching_'.$spremenljivka.'">'.$default_value.'</div>';			
				if($prevajanje == false){
					//echo '<div id="sliderbranching_'.$spremenljivka.'" class="slider"></div>';
					$prevod = "";
				}
				else if ($prevajanje == true){
					$prevod = "prevajanje";					
					if($slider_DescriptiveLabel_defaults && $custom==''){	//ce so prednalozene opisne labele drsnika in nimamo se prevoda
						$custom_ar = explode(';', $slider_DescriptiveLabel_defaults_naslov1);
					}else{	//ce so custom opisne labele drsnika
						$custom_ar = explode('; ', $custom);
					}
				}
				echo '<div id="sliderbranching_'.$prevod.$spremenljivka.'" class="slider"></div>';
				echo '</div>';
				
 				//za custom opisne labele
				//moznosti urejanja opisnih label drsnika
				if($slider_VmesneDescrLabel){			
					for($i=1; $i<=$slider_NumofDescrLabels; $i++){						
						if($prevajanje == false){
							$slider_CustomDescriptiveLabelsTmp = ($spremenljivkaParams->get('slider_Labela_opisna_'.$i) ? $spremenljivkaParams->get('slider_Labela_opisna_'.$i) : '');
						}else if ($prevajanje == true){
							$slider_CustomDescriptiveLabelsTmp = $custom_ar[$i-1];
						}					
						
						$slider_CustomDescriptiveLabelsTmp = preg_replace("/\s|&nbsp;/",' ',$slider_CustomDescriptiveLabelsTmp);  //za odstranitev morebitnih presledkov, ki lahko delajo tezave pri polju za drsnik										
						if($i == 1){
							$slider_CustomDescriptiveLabels = $slider_CustomDescriptiveLabelsTmp;
						}else{
							$slider_CustomDescriptiveLabels .= "; ".$slider_CustomDescriptiveLabelsTmp;
						}									
					}
				}
				//za custom opisne labele - konec

				//echo $slider_DescriptiveLabel_defaults_naslov1;
				if ($prevajanje == false){
					?>
					<script>
						$(function() {
							slider_edit_init(<?=$spremenljivka?>, <?=$slider_MinNumLabel?>, <?=$slider_MaxNumLabel?>, <?=$default_value?>, <?=$slider_handle?>, <?=$slider_handle_step?>, <?=$slider_VmesneNumLabel?>, <?=$slider_VmesneCrtice?>, <?=$slider_MinMaxNumLabelNew?>, <?=$slider_window_number?>, '<?=$slider_DescriptiveLabel_defaults_naslov1?>', <?=$slider_DescriptiveLabel_defaults?>, <?=$slider_nakazi_odgovore?>, <?=$slider_MinNumLabelTemp?>, <?=$slider_MaxNumLabelTemp?>, <?=$slider_VmesneDescrLabel?>, '<?=$slider_CustomDescriptiveLabels?>');
						});
					</script>
					<?
				}
				else if ($prevajanje == true){
					?>
					<script>
						$(function() {
							slider_edit_init_prevajanje(<?=$spremenljivka?>, <?=$slider_MinNumLabel?>, <?=$slider_MaxNumLabel?>, <?=$default_value?>, <?=$slider_handle?>, <?=$slider_handle_step?>, <?=$slider_VmesneNumLabel?>, <?=$slider_VmesneCrtice?>, <?=$slider_MinMaxNumLabelNew?>, <?=$slider_window_number?>, '<?=$slider_DescriptiveLabel_defaults_naslov1?>', <?=$slider_DescriptiveLabel_defaults?>, <?=$slider_nakazi_odgovore?>, <?=$slider_MinNumLabelTemp?>, <?=$slider_MaxNumLabelTemp?>, <?=$slider_VmesneDescrLabel?>, '<?=$slider_CustomDescriptiveLabels?>');
						});
					</script>
					<?					
				}
				echo '<br />';
				echo '<br />';
				echo '<br />';
				
				//tabela za labele podrocij in podrocja
                echo '<table '.$display_labele_podrocij.' style="width:85%">';
                
                //vrstica z graficnim prikazom podrocja
                echo '<tr>';					
                for($i = 1; $i <= $slider_StevLabelPodrocij; $i++){
                    echo '<td width="'.$slider_table_td_width.'%" class="label_podrocje_prikaz"><div ></div></td>';
                }
                echo '</tr>';
                
                //vrstica z labelami podrocij
                echo '<tr>';
                for($j = 1; $j <= $slider_StevLabelPodrocij; $j++){									
					echo '<td class="inline_labele_podrocij"><div id="slider_Labela_podrocja_'.$j.'_'.$spremenljivka.'" name="slider_Labela_podrocja_'.$j.'" class="inline_labele_podrocij" style="float:none; display:inline" contenteditable="'.(!$locked?'true':'false').'" '.(strpos($slider_Labela_podrocja[$j], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $slider_Labela_podrocja[$j] . '</div></td>';
                }
                echo '</tr>';	

                echo '</table>';
                
				//tabela za labele podrocij in podrocja
				echo '</div>';
				
				//ce je drsnik in moramo urediti opisne labele #########################################################				
				if ($slider_VmesneDescrLabel){	//ce se ureja custom opisne labele				
					
					echo '<div style="padding: 50px;"></div>'; //za urediti prostor med zadnjimi opisnimi labelami in njihovim urejanjem
										
					if($slider_DescriptiveLabel_defaults&&$prevajanje==false){
						$tabelaOpisneStyle = 'display:none;';
					}else{
						$tabelaOpisneStyle = '';
					}
					
					echo '<table style="width: 100%; '.$tabelaOpisneStyle.'" id="inline_opisne_labele_'.$row['id'].$prevod.'">';
					echo '<thead><tr><td align="center" colspan="'.($slider_NumofDescrLabels).'">'.$lang['slider_custom_labels_msg'].'</td></tr></thead>';
				
					//moznosti urejanja opisnih label drsnika
					echo '<tbody id="edit_opisne_labele_'.$row['id'].$prevod.'" style="display: none">';
					//echo '<tbody id="edit_opisne_labele_'.$row['id'].'" >';
					echo '<tr>';
					echo '<td><button id="update_opisne_labele'.$prevod.'" type="button" onclick=" updateSliderOpisneLabele('.$row['id'].', '.$slider_NumofDescrLabels.', \''.$prevod.'\', \'\')">'.$lang['slider_custom_labels_update'].'</button></td>';	//gumb za posodobitev custom opisnih label
					echo '</tr>';
					echo '<tr>';
					$sirinaStolpcev = 100/$slider_NumofDescrLabels;
					for($j = 1; $j <= $slider_NumofDescrLabels; $j++){	//ostali stolpci									
						if($prevajanje == false){
							$slider_Labela_opisna[$j] = ($spremenljivkaParams->get('slider_Labela_opisna_'.$j.'') ? $spremenljivkaParams->get('slider_Labela_opisna_'.$j.'') : $lang['srv_new_text']);										
						}else if ($prevajanje == true){
							$slider_Labela_opisna[$j] = $custom_ar[$j-1];
						}
						
						echo '<td align="center" style="width:'.$sirinaStolpcev.'%;" ><div class="inline_opisne_labele'.$prevod.'" id="slider_Labela_opisna_'.$j.'_'.$spremenljivka.$prevod.'" name="slider_Labela_opisna_'.$j.$prevod.'" labelaVreId='.$row1['id'].' style="float:none; display:inline" contenteditable="'.(!$locked?'true':'false').'" '.(strpos($slider_Labela_opisna[$j], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $slider_Labela_opisna[$j] . '</div></td>';
					}					
					echo '</tr>';
					echo '</tbody>';
					//moznosti urejanja ureditev opisnih label drsnika - konec
					
					//moznosti brez urejanja opisnih label drsnika
					echo '<tbody id="preview_opisne_labele_'.$row['id'].$prevod.'">';
					echo '<tr>';
					echo '<td><button id="edit_opisne_labele_button_'.$prevod.$row['id'].'" type="button" onclick="switchSliderOpisneLabeleEditMode('.$row['id'].', \''.$prevod.'\'); $(this).closest(\'div.spremenljivka_content\').find(\'div.spremenljivka_settings\').click(); return false;">'.$lang['edit3'].'</button></td>';	//gumb za vklop posodabljanja custom opisnih label
					echo '</tr>';
					echo '<tr>';
					$sirinaStolpcev = 100/$slider_NumofDescrLabels;
					for($j = 1; $j <= $slider_NumofDescrLabels; $j++){	//ostali stolpci
						if($prevajanje == false){
							$slider_Labela_opisna[$j] = ($spremenljivkaParams->get('slider_Labela_opisna_'.$j.'') ? $spremenljivkaParams->get('slider_Labela_opisna_'.$j.'') : $lang['srv_new_text']);										
						}else if ($prevajanje == true){
							$slider_Labela_opisna[$j] = $custom_ar[$j-1];
						}
						
						echo '<td align="center" style="width:'.$sirinaStolpcev.'%;" ><div class="inline_opisne_labele" id="slider_Labela_opisna_'.$j.$prevod.'_'.$spremenljivka.'" name="slider_Labela_opisna_'.$j.$prevod.'" labelaVreId='.$row1['id'].' style="float:none; display:inline"  '.(strpos($slider_Labela_opisna[$j], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $slider_Labela_opisna[$j] . '</div></td>';
					}					
					echo '</tr>';
					echo '</tbody>';
					//moznosti brez urejanja ureditev opisnih label drsnika - konec
					
					//js koda za ustrezno skrivanje in prikazovanje delov za urejanje custom opisnih label
					?>
					<script>
						$(document).ready(function(){
							var spr_id_nastavitev = $("#vprasanje_edit form input[name='spremenljivka'] ").val()
							if (spr_id_nastavitev == spr_id) {	//ce je odprto okno z nastavitvami
								$('#preview_opisne_labele_'+<?=$row['id']?>).css('display', 'none');
								$('#edit_opisne_labele_'+<?=$row['id']?>).css('display', 'block');
							} else {	//drugace
								$('#preview_opisne_labele_'+<?=$row['id']?>).css('display', 'block');
								$('#edit_opisne_labele_'+<?=$row['id']?>).css('display', 'none');
							}
						});					
					</script>
					<?
					//js koda za ustrezno skrivanje in prikazovanje delov za urejanje custom opisnih label - konec
					
					echo '      </table>';
				}				
				//ce je drsnik in moramo urediti opisne labele - konec ##################################################				
			}


			// dodamo checkboxe za druga polja
			while ($row1 = mysqli_fetch_array($sqlOther)) {

				if ($this->lang_id != null) {
					save('lang_id', $this->lang_id);
					$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
					if ($naslov != '') $row1['naslov'] = $naslov;
				}
			
				// Ce je variabla ne vem in imamo vklopljen prikaz ob opozorilu -> rdec
				$missing_warning = '';
				if(($row1['other'] == '-97' && $row['alert_show_97'] > 0)
					|| ($row1['other'] == '-98' && $row['alert_show_98'] > 0)
					|| ($row1['other'] == '-99' && $row['alert_show_99'] > 0)){
					$missing_warning = ' red';
				}

				echo '<div class="variabla' . $cssFloat . '" style="'.$cell_width.'" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'">';
				echo '<input type="checkbox" name="foo_' . $row['id'] . '" id="foo_' . $row1['id'] . '" value=""  class="' . (($row['checkboxhide'] == 1) ? 'hidden' : '') . '" onClick="return false;"/>';
				echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline '.$missing_warning.'" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'">' . $row1['naslov'] . '</div>';

				echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
				echo ' <span class="faicon edit2 inline inline_edit"></span>';
				echo '</div>';
			}
		}

		// label
		elseif ($row['tip'] == 5) {

		}

		// 8_datum
		elseif ($row['tip'] == 8) {
			
			#XXXXXXXXX MV
			echo '<div class="variabla' . $cssFloat . '">';
			echo '      <input type="text" name="foo1_' . $row['id'] . '" id="foo1_' . $row['id'] . '" value="' . date("d.m.Y") . '" disabled="disabled" />';
			echo '		<span class="faicon calendar_icon icon-blue" id="foo1_img_' . $row['id'] . '" style="vertical-align:-7px; margin-left: 7px;"></span>';
			echo '</div>';
			
			// dodamo checkboxe za druga polja
			$sql1 = sisplet_query("SELECT id, naslov, other FROM srv_vrednost WHERE spr_id='$row[id]' AND vrstni_red>0 AND other != 0");
			while ($row1 = mysqli_fetch_array($sql1)) {

				if ($this->lang_id != null) {
					save('lang_id', $this->lang_id);
					$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
					if ($naslov != '') $row1['naslov'] = $naslov;
				}
			
				// Ce je variabla ne vem in imamo vklopljen prikaz ob opozorilu -> rdec
				$missing_warning = '';
				if(($row1['other'] == '-97' && $row['alert_show_97'] > 0)
					|| ($row1['other'] == '-98' && $row['alert_show_98'] > 0)
					|| ($row1['other'] == '-99' && $row['alert_show_99'] > 0)){
					$missing_warning = ' red';
				}

				echo '<div class="variabla' . $cssFloat . '" id="variabla_'.$row1['id'].'" other="'.$row1['other'].'">';
				echo '<input type="checkbox" name="foo_' . $row['id'] . '" id="foo_' . $row1['id'] . '" value=""  class="' . (($row['checkboxhide'] == 1) ? 'hidden' : '') . '" onClick="return false;"/>';
				echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline '.$missing_warning.'" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'">' . $row1['naslov'] . '</div>';
				echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
                echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
                echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
				echo ' <span class="faicon edit2 inline inline_edit"></span>';
				echo '</div>';
			}

		}

		// ranking
		elseif ($row['tip'] == 17) {

			$sql1 = sisplet_query("SELECT id, naslov, hidden, if_id FROM srv_vrednost WHERE spr_id = '$spremenljivka' AND vrstni_red>0 ORDER BY vrstni_red");

			// izracun visine
			$num = mysqli_num_rows($sql1);
			$size = $num * 50;

			// n=k
			if ($row['design'] == 2) {
				echo '<div id="half_' . $row['podpora'] . '" class="dropzone '.($this->lang_id==null?'allow_new':'').'" style="min-height:' . $size . 'px;">';

				while ($row1 = mysqli_fetch_array($sql1)) {
					
					if ($this->lang_id != null) {
						save('lang_id', $this->lang_id);
						$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
						if ($naslov != '') $row1['naslov'] = $naslov;
					}
					
					//preverimo dolzino niza -> max == 20
					$length = strlen($row1['naslov']);
					?>
					<script>
						$(document).ready(function(){
							UrediOkvir(<?=$row1['id']?>);	//funkcija v customizeImageView.js
						});
					</script>
					<?

					if ($length > 30) $class = 'ranking_long'; $class = 'ranking';
					
					echo '<div class="variabla" id="variabla_'.$row1['id'].'">';
					echo '<span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span>';
					echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
                    echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
                    echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
					echo ' <span class="faicon edit2 inline inline_edit"></span>';

					echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline '.$class.'" style="float:none" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>'.$row1['naslov'].'</div>';
					
					//koda za notranji IF
					if ($row1['if_id'] > 0) {
						
						echo '<div style="text-align: center;">';
						echo ' <span class="red">*</span>';

						echo ' <span style="font-size:9px; cursor:pointer" id="if_notranji_'.$row1['id'].'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">';
						$this->conditions_display($row1['if_id']);
						echo '</span>';

						if ($this->condition_check($row1['if_id']) != 0)
							echo ' <span class="faicon warning icon-orange"></span>';
					
						echo '</div>';
					}

					echo '</div>';
				}
				echo '</div>';
			}

			//n>k
			elseif ($row['design'] == 0) {

				//zaslon razdelimo na dva dela - izris leve strani
				echo '<div id="half" class="dropzone '.($this->lang_id==null?'allow_new':'').'" style="width: 50%; min-height:' . $size . 'px; float: left; border-right: 1px solid black;">';

				while ($row1 = mysqli_fetch_array($sql1)) {

					if ($this->lang_id != null) {
						save('lang_id', $this->lang_id);
						$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
						if ($naslov != '') $row1['naslov'] = $naslov;
					}
				
					//preverimo dolzino niza -> max == 20
					$length = strlen($row1['naslov']);
					?>
					<script>
						$(document).ready(function(){							
							UrediOkvir(<?=$row1['id']?>);	//funkcija v customizeImageView.js							
						});
					</script>
					<?

					if ($length > 30) $class = 'ranking_long'; $class = 'ranking';
					
					echo '<div class="variabla" id="variabla_'.$row1['id'].'">';
					echo '<span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span>';
					echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
                    echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
                    echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
					echo ' <span class="faicon edit2 inline inline_edit"></span>';

					echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline '.$class.'" style="float:none" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>'.$row1['naslov'].'</div>';
					
					//koda za notranji IF
					if ($row1['if_id'] > 0) {
						echo '<div style="text-align: center;">';
						echo ' <span class="red">*</span>';

						echo ' <span style="font-size:9px; cursor:pointer" id="if_notranji_'.$row1['id'].'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">';
						$this->conditions_display($row1['if_id']);
						echo '</span>';

						if ($this->condition_check($row1['if_id']) != 0){
							echo ' <span class="faicon warning icon-orange"></span>';
						}
						
						echo '</div>';
					}

					echo '</div>';
				}
				echo '</div>';

				// izris desne strani
				echo '<div id="half2" class="dropzone" style="width: 49%; min-height:' . $size . 'px; float: right;">';

				if($row['ranking_k'] == 0)
					$max = mysqli_num_rows($sql1);
				else
					$max = $row['ranking_k'];

				for($i=1; $i<=$max; $i++){
					echo '<div class="ranking_frame">'.$i.'</div>';
				}

				echo '</div>';

				echo '<div class="clr"></div>';
			}

			// cifre - dropdown
			elseif ($row['design'] == 1) {

                $max = mysqli_num_rows($sql1);

				while ($row1 = mysqli_fetch_array($sql1)) {
					
					if ($this->lang_id != null) {
						save('lang_id', $this->lang_id);
						$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
						if ($naslov != '') $row1['naslov'] = $naslov;
					}
					
					echo '<div class="variabla '.$cssFloat.'" id="variabla_'.$row1['id'].'">';
					echo '<span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span>';
					echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
                    echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
                    echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
					echo ' <span class="faicon edit2 inline inline_edit"></span>';

					//echo '<input type="textfield" size="2"> ';
                    echo '<select style="width:50px; margin-top:0; float:left;">';
                    echo '  <option></option>';
                    for($i=1; $i<=$max; $i++){
                        echo '  <option>'.$i.'</option>';
                    }
                    echo '</select>';
                    
					echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline '.$class.'" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>'.$row1['naslov'].'</div>';
					
					//koda za notranji IF
					if ($row1['if_id'] > 0) {
						
						echo ' <span class="red">*</span>';

						echo ' <span style="font-size:9px; cursor:pointer" id="if_notranji_'.$row1['id'].'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">';
						$this->conditions_display($row1['if_id']);
						echo '</span>';

						if ($this->condition_check($row1['if_id']) != 0)
							echo ' <span class="faicon warning icon-orange"></span>';
					}

					echo '</div>';
				}
			}
			
			//image hotspot za razvrscanje
			elseif ($row['design'] == 3) {
				
				//izris hotspot nastavitev za dodajanje slike
				$this->vprasanje_hotspot($row['id'], $row['tip'], $row['design']);
			}
		}

		// vsota
		elseif ($row['tip'] == 18) {

			$spremenljivkaParams = new enkaParameters($row['params']);
			$gridWidth = (($spremenljivkaParams->get('gridWidth') > 0) ? $spremenljivkaParams->get('gridWidth') : 30);

			echo '<div class="'.($this->lang_id==null?'allow_new':'').'">';
			
			$sql1 = sisplet_query("SELECT id, naslov, hidden, if_id FROM srv_vrednost WHERE spr_id='$row[id]' AND vrstni_red > '0' ORDER BY vrstni_red ASC");
			while($row1 = mysqli_fetch_array($sql1)){
				
				if ($this->lang_id != null) {
					save('lang_id', $this->lang_id);
					$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
					if ($naslov != '') $row1['naslov'] = $naslov;
				}				

				echo '<div class="variabla variabla_vsota" id="variabla_'.$row1['id'].'" style="width:100%; ">';
				echo '<span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span>';
                echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
                echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
                echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
				echo ' <span class="faicon edit2 inline inline_edit"></span>';

				echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline vrednost_inline_vsota '.$class.'" style="width:'.($gridWidth*7).'px" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>'.$row1['naslov'].'</div>';

				echo ' <input type="text" name="foo_' . $row['id'] . '" maxlength="8" size="5">';
				if ($row1['if_id'] > 0) {
					echo ' <span class="red" style="cursor:pointer" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">*</span>';
					if ($this->condition_check($row1['if_id']) != 0)
						echo ' <span class="faicon warning icon-orange"></span>';
				}

				echo '</div>';

			}
			echo '</div>';

			$row1 = Cache::srv_spremenljivka($row['id']);

			if($row['vsota_limit'] != 0 && $row['vsota_limit'] == $row['vsota_min'])
				$limit = '('.$row['vsota_min'].')';
			elseif($row['vsota_limit'] != 0 && $row['vsota_min'] != 0)
				$limit = '(min '.$row['vsota_min'].', max '.$row['vsota_limit'].')';
			elseif($row['vsota_limit'] != 0)
				$limit = '(max '.$row['vsota_limit'].')';
			elseif($row['vsota_min'] != 0)
				$limit = '(min '.$row['vsota_min'].')';

			$vsota = ($row1['vsota'] != '') ? $row1['vsota'] : $lang['srv_vsota_text'];


            echo '<table class="variabla_vsota"><tr>';

            echo '  <td class="text">';
            echo '      <div class="variabla_vsota_sum">';
			echo '          <div style="width:'.($gridWidth*7).'px;" id="vsota_'.$row['id'].'" name="vsota" class="variabla_vsota_inline vrednost_inline_vsota" style="display:inline" contenteditable="'.(!$locked?'true':'false').'" '.(strpos($vsota, $lang['srv_vsota_text'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $vsota . '</div>';
			echo '      </div>';
			echo '  </td>';
            
            echo '  <td class="input">';
            echo '      <input type="text" name="foo_' . $row['id'] . '" maxlength="8" size="5" >';
            if ($row['vsota_show'] == 1)
				echo ' 		<label style="color: red; padding-left: 5px;">'.$limit.'</label>';
            echo '  </td>';

            echo '</tr></table>';
		}

		// SN - imena
		elseif ($row['tip'] == 9) {

		}

		// SN - social
		elseif ($row['tip'] == 10) {

		}

		// SN - podvprasanje
		elseif ($row['tip'] == 11) {

		}

		// SN - number
		elseif ($row['tip'] == 12) {

			$sql1 = sisplet_query("SELECT naslov FROM srv_vrednost WHERE spr_id='$row[id]'");

			for ($t = 0; $t < $row['size']; $t++) {
				$row1 = mysqli_fetch_array($sql1);

				if ($row['enota'] > 0) {
					echo '      <p>' . $row1['naslov'] . ' <input type="text" name="foo_' . $row['id'] . '" maxlength="8"></p>';
				} else
					echo '      <p><input type="text" name="foo_' . $row['id'] . '" maxlength="8"></p>';
			}
		}

		// SN - povezave
		elseif ($row['tip'] == 13) {

		}

		// AW - podvprasanje
		elseif ($row['tip'] == 14) {

			if ($row['random'] == 1)
				$orderby = 'RAND()';
			else
				$orderby = 'vrstni_red';

			$sql1 = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY $orderby");
			while ($row1 = mysqli_fetch_array($sql1)) {
				echo '        <p' . '><input type="radio" class="enka-admin-custom" name="foo_' . $row['id'] . '" id="foo_' . $row1['id'] . '" value="" "/><span class="enka-checkbox-radio"></span> <label for="foo_' . $row1['id'] . '">' . $row1['naslov'] . '</label></p>';
			}

			if ($row['textfield'] == 1) {
				echo '        <p' . '><input type="radio" class="enka-admin-custom" name="foo_' . $row['id'] . '" id="foo_-3" value="" "/><span class="enka-checkbox-radio"></span> <label for="foo_-3">' . $row['textfield_label'] . '</label> <input type="text" name="" value="" /></p>';
			}
		}

		// AW - number
		elseif ($row['tip'] == 15) {

		}
		
		// 26 - Lokacija - maps
		elseif ($row['tip'] == 26) {
                    
                    //podtip 2 - multilokacija; 1 - moja lokacija; 3 - choose lokacija
                    
                    $default_centerInMap = "Slovenija";
                    
                    //pridobi parametre za centriranje mape in jo nastavi za kasnejso uporabo v js
                    $fokus_koordinate = $spremenljivkaParams->get('fokus_koordinate'); //dobi fokus koordinat mape
                    if(!isset(json_decode($fokus_koordinate)->center->lat))
                        $fokus_koordinate = false;
                    
                    $fokus = $spremenljivkaParams->get('fokus_mape'); //dobi fokus mape
                    $podvprasanje_naslov = $spremenljivkaParams->get('naslov_podvprasanja_map');//dobi naslov podvprasanja

                    if($fokus || $fokus_koordinate)
                        $centerInMap = $fokus;
                    else
                        $centerInMap = $default_centerInMap;
                    
                    $map_data = array();
                    $map_data_info_shapes = array();
                    
                    //ce je podtip choose location
                    if($row['enota'] == 3){
                        //ce so podatki ze v bazi
                        $sql1 = sisplet_query("SELECT vm.vre_id as id, v.naslov, vm.lat, vm.lng, vm.address FROM srv_vrednost AS v 
                            LEFT JOIN srv_vrednost_map AS vm ON v.id = vm.vre_id
                            WHERE v.spr_id='$spremenljivka'", 'array');
            
                        //je vec vrednosti
                        if(!isset($sql1['lat']))
                            $map_data = $sql1;
                        //je ena vrednost
                        else
                            $map_data[] = $sql1;
                        
                        echo '<span id="variabla_no_value_'.$spremenljivka.'" '. (($cssFloat != '') ? 'class="'.$cssFloat.'"' : '') .' style="width:auto !important; color: red; padding-left: 10px; display:'. ((count($map_data) > 0) ? 'none' : 'inline-block') .';">'.$lang['srv_branching_no_value_map'].'</span>';
                        
                        //get info shapes
                        $sql2 = sisplet_query("SELECT lat, lng, address, overlay_id FROM srv_vrednost_map 
                            WHERE spr_id='$spremenljivka' AND overlay_type='polyline' ORDER BY overlay_id, vrstni_red", 'array');
                        
                        //create json data for info shapes
                        $st_linij=0;
                        $last_id=0;
                        foreach ($sql2 as $line_row) {
                            if($line_row['overlay_id'] != $last_id){
                                $st_linij++;
                                $last_id = $line_row['overlay_id'];
                                $map_data_info_shapes[$st_linij-1]['overlay_id']=$line_row['overlay_id'];
                                $map_data_info_shapes[$st_linij-1]['address']=$line_row['address'];
                                $map_data_info_shapes[$st_linij-1]['path']= array();
                            }
                            
                            $path = array();
                            $path['lat']=floatval($line_row['lat']);
                            $path['lng']=floatval($line_row['lng']);
                            
                            array_push($map_data_info_shapes[$st_linij-1]['path'], $path);
                        }
                    }
                    
                    //izrisi search box za v mapo
                    echo '<input id="pac-input_'.$spremenljivka.'" class="pac-input" type="text" style="display:none" onkeypress="return event.keyCode != 13;">';

                    echo '<div id="br_map_'.$spremenljivka.'" style="width:100%;height:300px;margin:0px 30px 0px 0px;border-style: solid;border-width: 1px;border-color: #b4b3b3;"></div>';
                    ?>
                    <script type="text/javascript">
                        //naredi padding variable_holder na desni in levi strani (default je samo na levi)
                        document.getElementById('br_map_<?php echo $spremenljivka; ?>').parentElement.style.padding = "0px 30px";
                        
                        //preveri, ce je google API ze includan (ce se je vedno icludal, je prislo do errorjev)
                        if((typeof google === 'object' && typeof google.maps === 'object')){
                            MapsBranching();
                        }
                        else{
                            //main/app/contollers/js/Maps/Declaration.js
                            mapsAPIseNi (MapsBranching);
                        }

                        //nastavi mapo
                        function MapsBranching(){
                            //mapType = tip zemljevida, ki bo prikazan. Recimo za satelitsko sliko google.maps.MapTypeId.SATELLITE (možno še .ROADMAP)
                            var mapType = google.maps.MapTypeId.ROADMAP;
                            //centerInMap = string naslova, kaj bo zajel zemljevid. Rec. Slovenija / ali Ljubljana
                            var centerInMap = '<?php echo $centerInMap; ?>';

                            //ali je anketa locked
                            var locked = <?php echo json_encode($locked); ?>;
                            
                            //pridobi parametre za centriranje mape in jo nastavi za kasnejso uporabo
                            var centerInMapKoordinate = <?php echo json_encode($fokus_koordinate)?>;
                            if(centerInMapKoordinate)
                                centerInMapKoordinate = JSON.parse(centerInMapKoordinate);

                            //Deklaracija potrebnih stvari za delovanje in upravljanje google maps JS API
                            var mapOptions = {
                                    zoomControl: false,
                                    streetViewControl: false,
                                    disableDoubleClickZoom: true,
                                    scrollwheel: false,
                                    navigationControl: false,
                                    mapTypeControl: false,
                                    scaleControl: false,
                                    draggable: false,
                                    mapTypeId: mapType
                            };
                            
                            //ce je v bazi naslov enak vpisanemu v nastavitvah, nastavi po parametrih
                            if(centerInMapKoordinate.fokus === centerInMap || centerInMap === ''){
                                mapOptions.center = {lat:  parseFloat(centerInMapKoordinate.center.lat), 
                                    lng:  parseFloat(centerInMapKoordinate.center.lng)};
                                mapOptions.zoom = parseInt(centerInMapKoordinate.zoom);
                            }   
                            //ce ni parametrov v bazi ali pa je nanovo kreirana spremenljivka, nastavi na Slovenijo
                            else if(!centerInMapKoordinate && centerInMap === '<?php echo $default_centerInMap; ?>'){
                                mapOptions.center = {lat: 46.151241, lng: 14.995463};
                                mapOptions.zoom = 7;
                            }

                            //deklaracija zemljevida
                            var mapdiv = document.getElementById("br_map_<?php echo $spremenljivka; ?>");
                            var map = new google.maps.Map(mapdiv, mapOptions);
                            //to se kasneje uporabi za pridobitev mape z id-em spremenljivke
                            mapdiv.gMap = map;
                            //deklaracija mej/okvira prikaza na zemljevidu
                            bounds['<?php echo $spremenljivka; ?>'] = new google.maps.LatLngBounds();
                            //deklaracija geocoderja (API)
                            if(!geocoder)
                                geocoder = new google.maps.Geocoder();

                            //nastavitve za nastavljanje fokusa na mapi
                            var centerControlDiv = document.createElement('div');
                            centerControl(centerControlDiv, map, '<?php echo $spremenljivka; ?>');
                            centerControlDiv.index = 1;
                            map.controls[google.maps.ControlPosition.TOP_CENTER].push(centerControlDiv);
                            //skrij, ce je anketa locked
                            if(locked)
                                centerControlDiv.style.display =  'none';
                            
                            
                            //nastavitve, ce je chooselocation
                            if(<?php echo $row['enota']; ?> === 3){
                                //onemogoci editiranje, ce je locked
                                if(!locked){
                                    centerControlDiv.style.display =  'none';
                                    setMapMovable(map);
                                    //izrisi search box za v mapo
                                    searchBox('<?php echo $spremenljivka; ?>', doAfterPlaceFromSearchBox);
                                    drawMarkers('<?php echo $spremenljivka; ?>');
                                }
                                
                                //naslov podvprasanja v infowindow
                                podvprasanje_naslov['<?php echo $spremenljivka; ?>'] = '<?php echo $podvprasanje_naslov; ?>';
                                
                                allMarkers['<?php echo $spremenljivka; ?>'] = [];
                                
                                if(!infowindow)
                                    infowindow = new google.maps.InfoWindow();
                                
                                //ze ta spremenljivka vsebuje vrednosti oz. markerje?
                                var map_data = JSON.parse('<?php echo addslashes(json_encode($map_data)); ?>');
                                if (map_data.length > 0){
                                    map_data_fill_vnaprej_mrkerji('<?php echo $spremenljivka; ?>', map_data, locked);
                                    st_markerjev['<?php echo $spremenljivka; ?>'] = map_data.length;   
                                }
                                else
                                    st_markerjev['<?php echo $spremenljivka; ?>'] = 0;
                                
                                var map_data_info_shapes = JSON.parse('<?php echo addslashes(json_encode($map_data_info_shapes)); ?>');
                                var last_shape_id = 0;
                                if (map_data_info_shapes.length > 0)
                                    last_shape_id = map_data_fill_vnaprej_shapes('<?php echo $spremenljivka; ?>', map_data_info_shapes, locked);
                                
                                //set global variable st_shapes for this variable 
                                st_shapes['<?php echo $spremenljivka; ?>'] = {count: map_data_info_shapes.length, last_id: last_shape_id};
                            }
                            
                            /**
                             * Do when place is found from searchbox
                             * @param {type} data - object array with position (coordinates) and address
                             * @returns {undefined}
                             */
                            function doAfterPlaceFromSearchBox(pos, address){
                                shraniMarker('<?php echo $spremenljivka; ?>', address, '', 
                                ustvari_basic_marker('<?php echo $spremenljivka; ?>', pos, address));
                            }
                     
                            /**
                             * geokodira fokus mape - v nastavitvah naslov - in shrane parametre v bazo
                             * @returns {undefined}
                             */
                            function geocoderMap(place){
                                if(place){
                                    map.setCenter(place.geometry.location);
                                    map.fitBounds(place.geometry.viewport);

                                    //kreiraj json za kasnejsi fokus mape - da se ne porabljajo kvote za geocoding
                                    /*var fokusJSON = {koordinate:{center:{lat:null, lng:null}, zoom:null, fokus:centerInMap}, 
                                        spr_id:'<?php /*echo $spremenljivka;*/ ?>'};
                                    fokusJSON.koordinate.center.lat = map.getCenter().lat();
                                    fokusJSON.koordinate.center.lng = map.getCenter().lng();
                                    fokusJSON.koordinate.zoom = map.getZoom();

                                    //shrani parametre v bazo - BranchingAjax.php -> ajax_fokus_koordiante_map()
                                    $.post('ajax.php?t=branching&a=fokus_koordiante_map', fokusJSON);*/

                                    set_fokus_koordiante_map('<?php echo $spremenljivka; ?>', map.getCenter().lat(), 
                                        map.getCenter().lng(), map.getZoom(), centerInMap);

                                    //povecaj zoom za 1, ker google naredi prevec oddaljeno
                                    //pri vecji povrsini na mapi (npr Slovenija), ne dela ok
                                    //map.setZoom(map.getZoom()+1);
                                }
                            }
                            
                            //ce je false (ni parametrov) ali pa se parameter razlikuje od polja fokus v nastavitvah
                            if(!centerInMapKoordinate || (centerInMapKoordinate.fokus !== centerInMap) && centerInMap !== ''){
                                //izvedi geocoding in shrani parametre
                                geocoderFromAddress(centerInMap, geocoderMap);
                            }
                        }
                    </script>
                    <?php
		}			
		//Heatmap********************************************************************************************************
		elseif( ($row['tip'] == 27)){	//Heatmap
				$this->vprasanje_heatmap($row['id'], $row['tip']);
		}
	
		
		
		//echo '<div id="clr" class="clr"></div>';
		echo '</div>';

		if ($row['orientation']==0 || $row['orientation']==2 || $row['tip'] == 17) echo '<div class="clr"></div>';	// kjer so vsi divi floatani, da se raztegne okvir vprasanja

		echo '</div><!-- end:variable_holder -->';

		//echo '      </div>';
		if ($this->displayKomentarji !== false) {
			$this->vprasanje_komentarji($spremenljivka);
		}

		echo '      </div> <!-- spremenljivka_content_' . $spremenljivka . ' -->';
	}

    function vprasanje_grid_multiple ($spremenljivka) {
		global $lang;

		$row = Cache::srv_spremenljivka($spremenljivka);

		$spremenljivkaParams = new enkaParameters($row['params']);

		// Ce je vprasanje ali anketa zaklenjena
        $locked = ($this->locked) ? true : false;
        
        // Inline ifi so disablani ce nimamo ustreznega paketa
        $userAccess = UserAccess::getInstance($global_user_id);

		echo '      <table class="grid_header_table '.($this->lang_id==null?'allow_new':'').'">';
		echo '        <thead>';

		// podnaslovi gridov
		if ($row['grid_subtitle1'] == '1') {
			echo '        <tr>';
			echo '          <td></td>';
			//echo '          <td style="width:' . $spacesize . '%"></td>';
			echo '          <td></td>';

			$sql2 = sisplet_query("SELECT s.id, s.naslov, s.grids FROM srv_spremenljivka s, srv_grid_multiple m WHERE s.id = m.spr_id AND parent = '".$row['id']."' ORDER BY m.vrstni_red");
			while ($row2 = mysqli_fetch_array($sql2)) {
				
				
 				if ($this->lang_id != null) {
					save('lang_id', $this->lang_id);					
					$rowl = \App\Controllers\LanguageController::srv_language_spremenljivka($row2['id']);					
					if (strip_tags($rowl['naslov']) != '') $row2['naslov'] = $rowl['naslov'];
				}				
				

				echo '          <td colspan="'.$row2['grids'].'" class="grid_header" grd="g_'.$row2['id'].'"><div class="naslov_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" spr_id="'.$row2['id'].'" '.(strpos($row2['naslov'], $lang['srv_new_vprasanje'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $row2['naslov'] . '</div></td>';

			}

			echo '        </tr>';
		}

		// urejanje vrednosti
		echo '        <tr id="grid_variable_'.$row['id'].'" '.$show_variable_row.'>';
		echo '          <td></td>';
		//echo '          <td style="width:' . $spacesize . '%"></td>';
		echo '          <td></td>';

		$bg = 1;

		$sql2 = sisplet_query("SELECT id, variable, vrstni_red FROM srv_grid WHERE spr_id='$row[id]' AND other=0 ORDER BY vrstni_red");
		$row2 = mysqli_fetch_array($sql2);

		for ($i = 1; $i <= $row['grids']; $i++) {
			if ($row2['vrstni_red'] == $i) {
				echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_variable_inline" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'">' . $row2['variable'] . '</div></td>';
				$row2 = mysqli_fetch_array($sql2);
			} else {
				echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"></td>';
			}
		}
		echo '</tr>';

		$grid_plus_minus = '<div class="grid-plus-minus"><a href="#" onclick="grid_multiple_add(\''.$row['id'].'\'); return false;" title="'.$lang['srv_gridmultiple_add'].'">'.$lang['add'].' <span class="faicon add icon-blue"></span></a></div>';
		
		echo '        <tr>';
		echo '          <td></td>';
		//echo '          <td style="width:' . $spacesize . '%"></td>';
		echo '          <td></td>';

		$bg = 1;

		//$sql2 = sisplet_query("SELECT g.* FROM srv_grid g, srv_grid_multiple m WHERE g.spr_id = m.spr_id AND parent = '".$row['id']."' ORDER BY m.vrstni_red, g.vrstni_red");

		$sqlM = sisplet_query("SELECT spr_id FROM srv_grid_multiple WHERE parent='$spremenljivka' ORDER BY vrstni_red");
		if(mysqli_num_rows($sqlM) > 0){
			$multiple = array();
			while ($rowM = mysqli_fetch_array($sqlM)) {
				$multiple[] = $rowM['spr_id'];
			}
			$sql2 = sisplet_query("SELECT g.*, s.tip, s.enota, s.dostop FROM srv_grid g, srv_grid_multiple m, srv_spremenljivka s WHERE s.id=g.spr_id AND g.spr_id=m.spr_id AND m.spr_id IN (".implode(',', $multiple).") ORDER BY m.vrstni_red, g.vrstni_red");
			$row2 = mysqli_fetch_array($sql2);

			for ($i = 1; $i <= mysqli_num_rows($sql2); $i++) {
				if ($this->lang_id != null) {
					$naslov = \App\Controllers\LanguageController::srv_language_grid($row2['spr_id'], $row2['id']);
					if ($naslov != '') $row2['naslov'] = $naslov;
				}

				if (true) {
					echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'" spr_id="'.$row2['spr_id'].'" '.(strpos($row2['naslov'], $lang['srv_new_grid'])!==false || strpos($row2['naslov'], $lang1['srv_new_grid'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row2['naslov'] . '</div></td>';
					$row2 = mysqli_fetch_array($sql2);
				} else {
					echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"></td>';
				}
			}
		}

		#kateri missingi so nastavljeni
		$sql_grid_mv = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0");
		if (mysqli_num_rows($sql_grid_mv) > 0 ) {
			echo '<td class=""></td>';
			while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
				echo '<td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row_grid_mv['id'].'"><div class="grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row_grid_mv['id'].'" '.(strpos($row_grid_mv['naslov'], $lang['srv_new_grid'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $row_grid_mv['naslov'] . '</div></td>';

			}
		}
		echo '        </tr>';


		// linki za urejanje pod-spremenljivk tabele
		echo '        <tr class="sub-table">';
		echo '          <td>'.$grid_plus_minus.'</td>';
		//echo '          <td style="width:' . $spacesize . '%"></td>';
		echo '          <td></td>';

		$bg = 1;
		$col = 1;
		$tip_prev = 0;
		$id_prev = 0;

		$sql2 = sisplet_query("SELECT s.grids, s.id, s.tip FROM srv_grid_multiple m, srv_spremenljivka s WHERE s.id = m.spr_id AND m.parent = '".$row['id']."' ORDER BY m.vrstni_red");
		while ($row2 = mysqli_fetch_array($sql2)) {

			if ($id_prev == 0) $id_prev = $row2['id'];

			if ($tip_prev != $row2['tip']) $col++;
				$tip_prev = $row2['tip'];

			echo '          <td class="grid_header ' .($id_prev!=$row2['id']?'col_border ':'').($col%2==0?'col_dark ':'') . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" colspan="'.$row2['grids'].'"><a href="#" onclick="grid_multiple_edit(\''.$row['id'].'\', \''.$row2['id'].'\'); return false;">'.$lang['edit3'].'</a></td>';

			$id_prev = $row2['id'];
		}
		echo '        </tr>';

		echo '</thead>';
		echo '<tbody class="'.($this->lang_id==null?'allow_new':'').'">';

		$bg++;

		$sql1 = sisplet_query("SELECT id, naslov, naslov2, hidden, other, if_id FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
		while ($row1 = mysqli_fetch_array($sql1)) {

			if ($this->lang_id != null) {
				save('lang_id', $this->lang_id);
				$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
				if ($naslov != '') $row1['naslov'] = $naslov;
			}

			echo '        <tr class="variabla" id="variabla_'.$row1['id'].'">';
			echo '          <td class="grid_question ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" '.($gridWidth == -1 ? '' : $css ).' id="'.$row1['id'].'">';
			echo '<span class="faicon move_updown inline inline_move" title="'.$lang['srv_move'].'"></span>';
			echo ' <span class="faicon delete small inline inline_delete" title="'.$lang['srv_brisivrednost'].'"></span>';
            echo ' <span class="faicon odg_hidden inline inline_hidden '. (($row1['hidden'] == 1) ? 'show-hidden' : '').(($row1['hidden'] == 2) ? 'show-disable' : '') .'" odg_vre="'.$row1['hidden'].'" odg_id="'.$row1['id'].'" title="'.$lang['srv_hide-disable_answer-'.$row1['hidden']].'"></span>';
            echo ' <span class="faicon odg_if_not inline inline_if_not '.(!$userAccess->checkUserAccess($what='if') ? 'user_access_locked' : '').'" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'"></span>';
			echo ' <span class="faicon edit2 inline inline_edit"></span>';

			echo '<div class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'].'</div>';

			if ($row1['if_id'] > 0) {
				echo ' <span class="red" style="cursor:pointer" onclick="vrednost_condition_editing(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_podif_edit'].'">*</span>';
				if ($this->condition_check($row1['if_id']) != 0)
					echo ' <span class="faicon warning icon-orange"></span>';
			}

			if ($row1['other'] == 1){
				$otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
				$otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);

				if ($otherHeight > 1)
					echo '<textarea name="" rows="'.$otherHeight.'" style="max-width:50%; '.($otherWidth != -1 ? ' width:'.$otherWidth.'%;' : '').'" disabled="disabled"></textarea>';
				else
					echo '<input type="text" name="" value="" style="max-width:50%; '.($otherWidth != -1 ? ' width:'.$otherWidth.'%;' : '').'" disabled="disabled" />';
			}


			echo '</td>';

			//echo '<td style="width:' . $spacesize . '%"></td>';
			echo '<td></td>';

			//razlicni vnosi glede na tip multigrida
			//for ($i = 1; $i <= $row['grids']; $i++) {
			$sql2 = sisplet_query("SELECT s.id, s.tip, s.enota FROM srv_grid g, srv_grid_multiple m, srv_spremenljivka s WHERE s.id=g.spr_id AND g.spr_id = m.spr_id AND parent = '".$row['id']."' ORDER BY m.vrstni_red, g.vrstni_red");
			if (mysqli_num_rows($sql2) > 0)
				$cellsize = 80/mysqli_num_rows($sql2);
			else
				$cellsize = 0;

			$col = 1;
			$tip_prev = 0;
			$id_prev = 0;

			while ($row2 = mysqli_fetch_array($sql2)) {

				if ($id_prev == 0) $id_prev = $row2['id'];

				if ($tip_prev != $row2['tip']) $col++;
				$tip_prev = $row2['tip'];

				if($row2['tip'] == 6) {
					//echo '          <td style="width:' . $cellsize . '%" class="' .($id_prev!=$row2['id']?'col_border ':'').($col%2==0?'col_dark ':'') . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '">'.($row2['enota']!=2?'<input type="radio" name="foo_' . $row1['id'] . '" value="">':'').'</td>';
					echo '          <td style="width:' . $cellsize . '%" class="' .($id_prev!=$row2['id']?'col_border ':'').($col%2==0?'col_dark ':'') . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '">'.(($row2['enota']!=2 && $row2['enota']!=6)?'<input type="radio" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value=""><span class="enka-checkbox-radio"></span>':'').'</td>';

				} elseif($row2['tip'] == 16) {
					//echo '          <td style="width:' . $cellsize . '%" class="' .($id_prev!=$row2['id']?'col_border ':'').($col%2==0?'col_dark ':'') . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="checkbox" name="foo_' . $row1['id'] . '" value=""></td>';
					echo '          <td style="width:' . $cellsize . '%" class="' .($id_prev!=$row2['id']?'col_border ':'').($col%2==0?'col_dark ':'') . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '">'.(($row2['enota']!=2 && $row2['enota']!=6)?'<input type="checkbox" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value=""><span class="enka-checkbox-radio"></span>':'').'</td>';

				} elseif ($row2['tip'] == 19) {
					echo '          <td style="width:' . $cellsize . '%" class="' .($id_prev!=$row2['id']?'col_border ':'').($col%2==0?'col_dark ':'') . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><textarea style="width:3em; height:12px" name="foo_' . $row1['id'] . '"></textarea></td>';

				} elseif ($row2['tip'] == 20) {

					echo '          <td style="width:' . $cellsize . '%" class="' .($id_prev!=$row2['id']?'col_border ':'').($col%2==0?'col_dark ':'') . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="text" style="width:'.$taWidth.'em;" name="foo_' . $row1['id'] . '" value="">';

					if ($row['ranking_k'] == 1) {
						echo '<div style="width:100%">';

						$default_value = round(($row['vsota_limit']-$row['vsota_min']) / 2) + $row['vsota_min'];
						echo '<div class="sliderText" id="sliderTextbranching_'.$spremenljivka.'_'.$row1['id'].'">'.$default_value.'</div>';

						echo '<div style="display:inline-block;">'.$row['vsota_min'].'</div>';
						echo '<div id="sliderbranching_'.$spremenljivka.'_'.$row1['id'].'" class="slider"></div>';
						echo '<div style="display:inline-block;">'.$row['vsota_limit'].'</div>';

						echo '</div>';

						//<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/base/jquery-ui.css" type="text/css" media="all" />
						?>
						<script>
							$(function() {
								slider_edit_grid_init(<?=$spremenljivka?>, <?=$row1['id']?>, <?=$row['vsota_min']?>, <?=$row['vsota_limit']?>, <?=$default_value?>);
							});
						</script>
						<?
					}

					echo '</td>';
				}

				$id_prev = $row2['id'];
			}

			#kateri missingi so nastavljeni
			$sql_grid_mv = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0");
			if (mysqli_num_rows($sql_grid_mv) > 0 ) {
				//echo '<td style="width:' . $spacesize . '%"></td>';
				echo '<td></td>';
				while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
					if($row['tip'] == 6) {
						echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom"  name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
					} elseif($row['tip'] == 16) {
						echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="checkbox" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
					} else {
						echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="radio" class="enka-admin-custom" name="foo_' . $row1['id'] . '" value="" /><span class="enka-checkbox-radio"></span></td>';
//								echo '          <td style="width:' . $cellsize . '%" class="' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"><input type="text" style="width: 90%;" name="foo_' . $row1['id'] . '" value="" /></td>';
					}

				}
			}

			// diferencial
			if ($row['enota'] == 1 && $row['tip'] == 6) {
				//echo '          <td style="width:' . $spacesize . '%"></td>';
				echo '          <td></td>';
				echo '          <td style="text-align:left;" class="grid_question ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" id="f_'.$row1['id'].'_2"><div class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'_2" '.(strpos($row1['naslov2'], $lang['srv_new_vrednost'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $row1['naslov2'] . '</div></td>';
			}
			echo '        </tr>';

			$bg++;
		}

		echo '      </tbody>';
		echo '      </table>';

    }


    /**
	* komentarji na vprasanje - vrstica se izpise na dnu prikaza vprasanja
	*
	* @param mixed $spremenljivka
	*/
	function vprasanje_komentarji ($spremenljivka) {
		global $lang;
		global $admin_type;
		global $global_user_id;

		SurveySetting::getInstance()->Init($this->anketa);
		$question_comment = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment');
		$question_resp_comment = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment');
		$question_resp_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_viewadminonly');

		$question_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewadminonly');
		$question_comment_viewauthor = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewauthor');

		if (!($admin_type <= $question_comment && $question_comment!='') && !($question_resp_comment==1)) return;
		if ($_GET['a'] == 'komentarji') return; // v komentarjih tut ne izpisujemo te vrstice

		echo '<div id="surveycommentcontainer_'.$spremenljivka.'" class="printHide comment_container_inline">';

		// komentarji na vprasanje
		if ($admin_type <= $question_comment && $question_comment != '') {

			// Dodaj komentar
			echo '   <span class="floatRight spaceLeft"><a href="#" id="surveycomment_'.$spremenljivka.'_0" class="surveycomment" type="1" subtype="q_admin_add" spremenljivka="'.$spremenljivka.'" view="0" onclick="return false;" title="'.$lang['srv_question_comment'].'"> ';
			//echo '<img src="img_' . $this->skin . '/comment.png" alt="'.$lang['srv_question_comment'].'" title="'.$lang['srv_question_comment'].'" > ';
			echo '<span class="faicon inline_comment"></span> ';
			echo $lang['srv_add_comment'];
			echo '</a></span>';
			echo '   <script>  $(function() {  load_comment(\'#surveycomment_'.$spremenljivka.'_0\');  });  </script>';

			$row = Cache::srv_spremenljivka($spremenljivka);
			if ($row['thread'] == 0) {
				$row['count'] = 0;
			} else {
				if ($admin_type <= $question_comment_viewadminonly) {
					$sql = sisplet_query("SELECT COUNT(*) AS count FROM post WHERE tid='$row[thread]'");
				} elseif ($question_comment_viewauthor==1) {
					$sql = sisplet_query("SELECT COUNT(*) AS count FROM post WHERE tid='$row[thread]' AND uid='$global_user_id'");
				} else {
					$sql = sisplet_query("SELECT * FROM post WHERE 1 = 0");
				}
				$row = mysqli_fetch_array($sql);
				$row['count']--;//1. je default comment
			}

			// Poglej komentarje
			if ($admin_type <= $question_comment_viewadminonly || $question_comment_viewauthor==1) {
				echo '&nbsp;&nbsp;&nbsp;<span class="floatRight spaceRight" id="comment_add_'.$spremenljivka.'"'.($row['count']==0?' style="visibility:hidden"':'').'><a href="#" id="surveycomment_'.$spremenljivka.'_1" class="surveycomment" type="1" subtype="q_admin_all" spremenljivka="'.$spremenljivka.'" view="1" onclick="return false;" title="'.$lang['srv_question_comments'].'"> ';
				//echo '<img src="img_' . $this->skin . '/comments.png" alt="'.$lang['srv_question_comments'].'" title="'.$lang['srv_question_comments'].'" > ';
				echo '<span class="faicon inline_double_comment"></span> ';
				echo $lang['srv_view_comment'].''.($row['count']>0?' ('.$row['count'].')':'');
				echo '</a></span>';
				echo '   <script>  $(function() {  load_comment(\'#surveycomment_'.$spremenljivka.'_1\');  });  </script>';
			}
		}

		// komentarji respondentov
		if (($question_resp_comment==1) AND ($admin_type <= $question_resp_comment_viewadminonly)) {

			echo '&nbsp;<span class="spaceRight floatRight" style="padding-right:10px">';
			$sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_data_text".$this->db_table." WHERE spr_id='0' AND vre_id='$spremenljivka'");
			$row = mysqli_fetch_array($sql);

			if ($row['count'] > 0) {
				echo '<a href="#" id="surveycomment_'.$spremenljivka.'_2" class="surveycomment" type="2" subtype="q_resp_all" spremenljivka="'.$spremenljivka.'" onclick="return false;">';
				echo '<span class="faicon inline_comment icon-orange"></span> ';
				echo $lang['srv_repondent_comment'].' ('.$row['count'].')';
				echo '</a>';
				echo '   <script>  $(function() {  load_comment(\'#surveycomment_'.$spremenljivka.'_2\');  });  </script>';
			}
			echo '</span>';
		}

		echo '</div>';
	}
	
	 /**
	* komentarji na if oz. blok - vrstica se izpise na dnu prikaza ifa oz. bloka
	*
	* @param mixed $if_id (id ifa ali bloka)
	* @param mixed $block (ali gre za if ali blok)
	*/
	function if_komentarji ($if_id, $block=0) {
		global $lang;
		global $admin_type;
		global $global_user_id;

		SurveySetting::getInstance()->Init($this->anketa);
		$question_comment = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment');

		$question_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewadminonly');
		$question_comment_viewauthor = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewauthor');

		if (!($admin_type <= $question_comment && $question_comment!='')) return;
		if ($_GET['a'] == 'komentarji') return; // v komentarjih tut ne izpisujemo te vrstice

		// komentarji na if ali blok
		if ($admin_type <= $question_comment && $question_comment != '') {

			// Dodaj komentar
			echo '   <span class="floatRight spaceLeft"><a href="#" id="surveycomment_'.$if_id.'_0" class="surveycomment" type="'.($block==1 ? '6' : '5').'" subtype="'.($block==1 ? 'blok_admin_add' : 'if_admin_add').'" spremenljivka="'.$if_id.'" view="0" onclick="return false;" title="'.$lang['srv_question_comment'].'"> ';
			//echo '<img src="img_' . $this->skin . '/comment.png" alt="'.$lang['srv_question_comment'].'" title="'.$lang['srv_question_comment'].'" > ';
			echo '<span class="faicon inline_comment"></span> ';
			echo $lang['srv_add_comment'];
			echo '</a></span>';
			echo '   <script>  $(function() {  load_comment(\'#surveycomment_'.$if_id.'_0\');  });  </script>';

			$row = Cache::srv_if($if_id);
			if ($row['thread'] == 0) {
				$row['count'] = 0;
			} else {
				if ($admin_type <= $question_comment_viewadminonly) {
					$sql = sisplet_query("SELECT COUNT(*) AS count FROM post WHERE tid='$row[thread]'");
				} elseif ($question_comment_viewauthor==1) {
					$sql = sisplet_query("SELECT COUNT(*) AS count FROM post WHERE tid='$row[thread]' AND uid='$global_user_id'");
				} else {
					$sql = sisplet_query("SELECT * FROM post WHERE 1 = 0");
				}
				$row = mysqli_fetch_array($sql);
				$row['count']--;//1. je default comment
			}

			// Poglej komentarje
			if ($admin_type <= $question_comment_viewadminonly || $question_comment_viewauthor==1) {
				echo '&nbsp;&nbsp;&nbsp;<span class="floatRight spaceRight" id="comment_add_'.$if_id.'"'.($row['count']==0?' style="visibility:hidden"':'').'><a href="#" id="surveycomment_'.$if_id.'_1" class="surveycomment" type="'.($block==1 ? '6' : '5').'" subtype="'.($block==1 ? 'blok_admin_all' : 'if_admin_all').'" spremenljivka="'.$if_id.'" view="1" onclick="return false;" title="'.$lang['srv_question_comments'].'"> ';
				//echo '<img src="img_' . $this->skin . '/comments.png" alt="'.$lang['srv_question_comments'].'" title="'.$lang['srv_question_comments'].'" > ';
				echo '<span class="faicon inline_double_comment"></span> ';
				echo $lang['srv_view_comment'].''.($row['count']>0?' ('.$row['count'].')':'');
				echo '</a></span>';
				echo '   <script>  $(function() {  load_comment(\'#surveycomment_'.$if_id.'_1\');  });  </script>';
			}
		}
	}

    /**
    * @desc prikaze uvod ali zakljucek (pri razsirjenem nacinu)
    */
    function introduction_conclusion ($id, $editmode = 0) {
        global $lang;
        global $site_path, $site_url;

		SurveyInfo :: getInstance()->resetSurveyData();
		$row = SurveyInfo::getInstance()->getSurveyRow();

		if ( $this->lang_id != null ) {
			$l = $lang;	// survey nam povozi lang..

			include_once('../../main/survey/app/global_function.php');
			if (empty($this->Survey->get))
				$this->Survey = new \App\Controllers\SurveyController(true);

			$lang = $l;
			save('lang_id', $this->lang_id);
			$rowl = \App\Controllers\LanguageController::srv_language_spremenljivka($id);
			if ($id == -1) {
				if (strip_tags($rowl['naslov']) != '') $row['introduction'] = $rowl['naslov'];
			} else {
				if (strip_tags($rowl['naslov']) != '') $row['conclusion'] = $rowl['naslov'];
			}
		}

		//uvod
        if ($id == -1) {
            if ($row['introduction'] == '') {
	            SurveyInfo::getInstance()->SurveyInit($this->anketa);
				$lang_admin = SurveyInfo::getInstance()->getSurveyColumn('lang_admin');
				$lang_resp  = SurveyInfo::getInstance()->getSurveyColumn('lang_resp');

				// nastavimo na jezik za respondentov vmesnik
				if ($this->lang_id == null) {
					if ($lang_resp > 0) {
						$file = '../../lang/'.$lang_resp.'.php';
						@include($file);
					}
				}

				$text = '<p>'.$lang['srv_intro'].'</p>';

				// nastavimo nazaj na admin jezik
				if ($this->lang_id == null) {
					if ($lang_admin > 0) {
						$file = '../../lang/'.$lang_admin.'.php';
						@include($file);
					}
				}

            } else {
                $text = $row['introduction'];
            }
            $show = $row['show_intro'];
            $opomba = $row['intro_opomba'];
            $selectall = $lang['srv_intro'];
        }
		//statistika
		elseif($id == -3) {
			$gl = new Glasovanje($this->anketa);
            $gl->edit_statistika($editmode);
			return 0;
        }
		//zakljucek
		else{
			if ($row['conclusion'] == '') {
				SurveyInfo::getInstance()->SurveyInit($this->anketa);
				$lang_admin = SurveyInfo::getInstance()->getSurveyColumn('lang_admin');
				$lang_resp  = SurveyInfo::getInstance()->getSurveyColumn('lang_resp');

				// nastavimo na jezik za respondentov vmesnik
				if ($this->lang_id == null) {
					if ($lang_resp > 0) {
						$file = '../../lang/'.$lang_resp.'.php';
						@include($file);
					}
				}

				$text = '<p>'.$lang['srv_end'].'</p>';

				// nastavimo nazaj na admin jezik
				if ($this->lang_id == null) {
					if ($lang_admin > 0) {
						$file = '../../lang/'.$lang_admin.'.php';
						@include($file);
					}
				}
            } else {
                $text = $row['conclusion'];
            }
            $show = $row['show_concl'];
            $opomba = $row['concl_opomba'];
            $selectall = $lang['srv_end'];
		}

        echo '      <div id="spremenljivka_content_'.$id.'" class="spremenljivka_content'.($editmode==1?' active':'').' '.($show!=1?' spremenljivka_hidden':'').'" spr_id="'.$id.'" '.($editmode==0?'onclick="editmode_introconcl(\''.$id.'\');"':'').'>'."\n\r";
        if ($editmode == 0) {
			// <-- Zgornja vrstica pri editiranju vprasanj ---
			echo '<div class="spremenljivka_settings spremenljivka_settings movable" title="'.$lang['edit3'].'">';
			echo '<div style="float:left;width:auto;">';
			// variabla
			echo '<div class="variable_name" id="div_variable_'.$id.'">';
	        echo ($id == -1) ? $lang['srv_intro_label'] : $lang['srv_end_label'];
			echo '</div>'."\n\r";
			echo '</div>';

			// prikažemo nastavitve vprasanja
			$fullscreen = ( isset($_POST['fullscreen']) && $_POST['fullscreen'] != 'undefined') ? (int)$_POST['fullscreen'] : false;
	        echo '<div id="spr_settings_intro_concl" >'."\n\r";

	        if ($id == -1) {
	            $show = $row['show_intro'];
	        } else {
	            $show = $row['show_concl'];
	        }

			if ($show != 1) {
				echo '<div class="intro_concl red">' . $lang['srv_visible_off'] .'</div>';
			}

			echo '</div>';

			echo '<div class="clr"></div>';
			echo '</div>';
			// --- Zgornja vrstica pri editiranju vprasanj -->

			// <-- Editor teksta vprasanja ---
			echo '<div class="spremenljivka_tekst_form">';
	        echo '<div class="naslov naslov_inline" contenteditable="'.(!$this->locked?'true':'false').'" spr_id="'.$id.'" tabindex="1" '.(strpos($text, $selectall)!==false?' default="1"':'').'>'.$text.'</div>';
			echo '<div class="clr"></div>';

			echo '<span class="faicon edit-vprasanje icon-as_link display_editor" onclick="inline_load_editor(this); return false;"></span>';

			// opomba
			if ($opomba != '' && $this->lang_id == null) {
				echo '<table style="margin-top:5px; width:100%"><tr>';
				echo '<td style="width:120px;">'.$lang['note'].' ('.$lang['srv_internal'].'):</td>';
				echo '<td >';
				echo '<span>'.$opomba.'</span>';
				echo '</td>';
				echo '</tr></table>';
			}
			echo '</div>';
			// --- Editor teksta vprasanja -->

			echo '<div class="clr"></div>';

        } else { // urejanje uvoda,zakljucka

			// <-- Zgornja vrstica pri editiranju vprasanj ---
			echo '<div class="spremenljivka_settings spremenljivka_settings_active">';
			echo '<div style="float:left;width:auto;">';
			// variabla
			echo '<div class="variable_name" id="div_variable_'.$id.'">';
	        echo ($id == -1) ? $lang['srv_intro_label'] : $lang['srv_end_label'];
			echo '</div>'."\n\r";
			echo '</div>';

			// prikažemo nastavitve vprasanja
			$fullscreen = ( isset($_POST['fullscreen']) && $_POST['fullscreen'] != 'undefined') ? (int)$_POST['fullscreen'] : false;
	        echo '<div id="spr_settings_intro_concl" >'."\n\r";
	        echo ' <span id="visible_introconcl_'.$id.'" class="extra_opt">';
			//$this->introconcl_visible($id);
	        echo ' </span>'."\n\r";
			echo '</div>';

			if (!$fullscreen) {
			// right spremenljivka icon menu
				echo '      <div class="editmenu" onClick="return false;">'."\n\r";
				echo '        <span><a href="#" title="'.$lang['srv_preglejspremenljivko'].'" onclick="'.($editmode==0?'edit':'normal').'mode_introconcl(\''.$id.'\',\''.$editmode.'\'); return false;"><img src="img_'.$this->skin.'/palete_green.png" alt="'.$lang['srv_preglejspremenljivko'].'" /></a></span>'."\n\r";
				echo '        <span><a href="#" title="'.$lang['srv_editirajspremenljivko_fs'].'" onclick="intro_concl_fullscreeen(\''.$id.'\', \'2\');  return false;"><img src="icons/icons/arrow_out.png" alt="'.$lang['srv_editirajspremenljivko_fs'].'" /></a></span>'."\n\r";
				echo '        <span><a href="#" title="'.$lang['srv_predogled_spremenljivka'].'" onclick="intro_concl_preview(\''.$id.'\'); return false;"><img src="img_'.$this->skin.'/preview_green.png" alt="'.$lang['srv_predogled_spremenljivka'].'" /></a></span>'."\n\r";
				echo '      </div> <!-- /editmenu -->'."\n\r";
			}
			echo '<div class="clr"></div>';
			echo '</div>';
			// --- Zgornja vrstica pri editiranju vprasanj -->

	        echo '      <form name="editintro_'.substr($id, 1, 1).'" action="" method="post">'."\n\r";
			// <-- Editor teksta vprasanja ---

			echo '<div class="spremenljivka_tekst_form">';

			echo '<div id="editor_display_' . $id. '" class="editor_display" >';
			echo '<div class="faicon edit-vprasanje icon-as_link pointer lightRed" onmouseover="editor_display(\'' . $id . '\'); $(this).parent().hide();" style="width:auto;" title="'.$lang['srv_editor'].'">';
			//echo '<img src="img_' . $this->skin . '/settings.png" />';
			echo '<span class="faicon edit-vprasanje icon-as_link"></span>';
			echo'</div>';
			echo '</div>';
			echo '<textarea name="naslov_' . $id . '" class="texteditor naslov" id="naslov_' . $id . '" >' . $text . '</textarea>';
			echo '<div class="clr"></div>';

			// opomba
			echo '<table style="margin-top:5px; width:100%"><tr>';
			echo '<td style="width:120px;">'.$lang['note'].' ('.$lang['srv_internal'].'):</td>';
			echo '<td >';
			echo '<textarea name="opomba" id="opomba_'.$id.'" class="texteditor info" >'.$opomba.'</textarea>';
			echo '</td>';
			echo '</tr></table>';

			echo '<script type="text/javascript">'; // shranimo ko zapustmo input polje
			echo '$(document).ready(function() {' .
			'  $("#naslov_' . $id . '").bind("blur", {}, function(e) {' .
			'    editor_save(\''.$id.'\'); return false;  ' .
			'  });' .
			'  $("#opomba_'.$id.'").bind("blur", {}, function(e) {' .
			'    editor_save(\''.$id.'\'); return false;  ' .
			'  });' .
			'});';
			echo '</script>';

			echo '</div>';

			echo '</form>';
			// --- Editor teksta vprasanja -->

			if ($id == -2) {
                $text = $row['text'];
                if ($row['url'] != '')
                    $url = $row['url'];
                else
                    $url = $site_url;

				echo '<div class="spremenljivka_tip_content">';

                echo '        <p>'.$lang['srv_concl_link'].'<input type="checkbox" class="enka-admin-custom" name="concl_link" value="1" '.($row['concl_link']==1?'':' checked').' onchange="javascript:concl_settings();" autocomplete="off"><span class="enka-checkbox-radio"></span></p>';

                echo '        <form name="conclusion" method="post" action="">';
                //echo '        <p><label for="text">'.$lang['srv_text'].':</label> <input type="text" name="text" id="text_concl_sett" value="'.$text.'" style="width:200px" autocomplete="off"><br />'."\n\r";
                echo '        <label for="url">'.$lang['srv_url'].':</label> <input type="text" name="url" id="url_concl_sett" value="'.$url.'" style="width:200px" autocomplete="off"></p>'."\n\r";
                echo '        </form>';

				echo '        <p>'.$lang['srv_concl_back_button_show'].'<input type="checkbox" class="enka-admin-custom" name="concl_back_button" value="1" '.($row['concl_back_button']==1 ? ' checked' : '').' onchange="javascript:concl_settings();" autocomplete="off"><span class="enka-checkbox-radio"></span></p>';

				echo '<script type="text/javascript">'; // shranimo ko zapustmo input polje
				echo '$(document).ready(function() {' .
				'  $("#text_concl_sett").bind("blur", {}, function(e) {' .
				'    concl_settings(); return false;  ' .
				'  });' .
				'  $("#url_concl_sett").bind("blur", {}, function(e) {' .
				'    concl_settings(); return false;  ' .
				'  });' .
				'});';
				echo '</script>';
				echo '</div>';
            }

			//pri formi in glasovanju gumb potrdi refresha stran
			if($row['survey_type'] == 1 || $row['survey_type'] == 0){
				echo '<div class="save_button">';
				echo '  <span class="floatLeft spaceRight"><div class="buttonwrapper" id="save_button_'.$id.'" ><a class="ovalbutton ovalbutton_orange" href="#"><span>';
				echo $lang['srv_potrdi'].'</span></a></div></span>';
				echo '</div>';
				echo '<div class="clr"></div>';
			}
			else{
				echo '<div class="save_button">';
				echo '  <span class="floatLeft spaceRight"><div class="buttonwrapper" id="save_button_'.$id.'" ><a class="ovalbutton ovalbutton_orange" href="#" onclick="normalmode_introconcl(\''.$id.'\',\''.$editmode.'\',\''.$fullscreen.'\'); return false;"><span>';
				echo $lang['srv_potrdi'].'</span></a></div></span>';
				echo '</div>';
				echo '<div class="clr"></div>';
			}
        }
        echo '      </div> <!-- /spremenljivka_content_'.$id.' -->'."\n\r";
    }
	
	/**
    * @desc prikaze uvod ali zakljucek (pri razsirjenem nacinu)
    */
    function gdpr_introduction () {
        global $lang;
        global $site_path, $site_url;

		$text = GDPR::getSurveyIntro($this->anketa);
		$text = str_replace('h3', 'b', $text);
			
        echo '<div class="spremenljivka_content">'."\n\r";


		// <-- Zgornja vrstica pri editiranju vprasanj ---
		echo '<div class="spremenljivka_settings">';
		echo '<div style="float:left;width:auto;">';
		// variabla
		echo '<div class="variable_name">';
        echo $lang['srv_gdpr_survey_gdpr_1ka_template_title'];
		echo '</div>'."\n\r";
		echo '</div>';

		echo '<div class="clr"></div>';
				
		echo '</div>';
		// --- Zgornja vrstica pri editiranju vprasanj -->


		// <-- Editor teksta vprasanja ---
		echo '<div class="spremenljivka_tekst_form">';
        echo '<div class="naslov naslov_inline">'.$text.'</div>';
		echo '<div class="clr"></div>';

		echo '</div>';
		// --- Editor teksta vprasanja -->


		echo '<div class="clr"></div>';


		// Da/ne variable
		echo '<div id="variable_holder" class="variable_holder">';

		echo '<div class="variabla">';
		echo '	<span class="faicon move_updown inline inline_move"></span>';
		echo '	<input class="enka-admin-custom enka-inline" value="" type="radio">';
		echo '	<span class="enka-checkbox-radio"></span>';
		echo '	<div class="vrednost_inline" style="padding-top:4px;">'.$lang['srv_gdpr_intro_no'].'</div>';
		echo '</div>';
		
		echo '<div class="variabla">';
		echo '	<span class="faicon move_updown inline inline_move"></span>';
		echo '	<input class="enka-admin-custom enka-inline" value="" type="radio">';
		echo '	<span class="enka-checkbox-radio"></span>';
		echo '	<div class="vrednost_inline" style="padding-top:4px;">'.$lang['srv_gdpr_intro_yes'].'</div>';
		echo '</div>';

		echo '</div>';
		
		echo '<br />';
    
	
        echo '      </div>'."\n\r";
    }
	

    /**
    * vrne parente elementa
    *
    * @param mixed $spr
    * @param mixed $if
    */
    var $get_parents = array();
    function get_parents ($spr=0, $if=0) {

		if (isset($this->get_parents[$spr][$if]))
			return $this->get_parents[$spr][$if];

		$sql = sisplet_query("SELECT parent FROM srv_branching WHERE element_spr='$spr' AND element_if='$if'");
		$row = mysqli_fetch_array($sql);

		if ($row['parent'] == 0) {
			$this->get_parents[$spr][$if] = '';

		} else {
			$this->get_parents[$spr][$if] = $this->get_parents(0, $row['parent']);			// rekurzija

			if ($this->get_parents[$spr][$if] != '') $this->get_parents[$spr][$if] .= ' ';	// presledek

			$this->get_parents[$spr][$if] .= 'p_'.$row['parent'];							// trenutni element
		}

		return $this->get_parents[$spr][$if];
    }

    /**
    * @desc prikaze link za page breake
    */
    function pagebreak_display ($spremenljivka) {
        global $lang;

        if ($this->pagebreak($spremenljivka))
            echo '<a class="pb on" title="'.$lang['srv_pagebreak_on'].'"></a>';
        else
            echo '<a class="pb" title="'.$lang['srv_pagebreak_off'].'"></a>';
    }


    /**
    * @desc prikaze pogoje v IFu
    */
    function conditions_display($if, $long_alert=0, $notranji_pogoj=0) {
		global $lang;

    	$row_if = Cache::srv_if($if);

        if ($row_if['tip'] != 0) return;
		
        // če gre za notranji pogoj prikažemo zvezdico
        echo '<strong class="clr_if">'.(($notranji_pogoj == 1)?'<span style="color:red;">*</span>' : '').'IF</strong>';

        if (true || $long_alert == 0) {
            echo ' <span class="colorif">('.$row_if['number'].')</span> ';
        }

		if ($long_alert == 0) {
            
            if ($row_if['enabled'] == 1) {
				echo 'TRUE';
				echo ' <span class="if_comment">( ';
            } 
            elseif ($row_if['enabled'] == 2) {
				echo 'FALSE';
				echo ' <span class="if_comment">( ';
			}
		}

        $sql = Cache::srv_condition($if);
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        $spr_id=0;
        $bracket = 0;
        $c = 0;
        while ($row = mysqli_fetch_array($sql)) {

        	if ($row['spr_id'] != 0)	$spr_id = $row['spr_id'];

        	echo '<span class="conjunction">';
            if ($c++ != 0)
                if ($row['conjunction'] == 0)
                    echo ' AND ';
                else
                    echo ' OR ';

            if ($row['negation'] == 1)
                echo ' NOT ';
			echo '</span>';

            for ($i=1; $i<=$row['left_bracket']; $i++)
                if ($long_alert == 1)
                    echo ' <span class="bracket'.(($bracket++)%12).'">(</span> ';
                else
                    echo ' ( ';

            // obicajne spremenljivke
            if ($row['spr_id'] > 0) {

				$row2 = Cache::srv_spremenljivka($row['spr_id']);

                // obicne spremenljivke
                if ($row['vre_id'] == 0) {
                    $row1 = Cache::srv_spremenljivka($row['spr_id']);
                // multigrid
                } elseif ($row['vre_id'] > 0) {
                    $sql1 = sisplet_query("SELECT variable FROM srv_vrednost WHERE id = '$row[vre_id]'");
                    if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
                    $row1 = mysqli_fetch_array($sql1);
                } else
                    $row1 = null;

                if (true || $long_alert) echo '<strong>';
                echo $row1['variable'];
                if (true || $long_alert) echo '</strong>';

                // radio, checkbox, dropdown in multigrid
                if (($row2['tip'] <= 3 || $row2['tip'] == 6 || $row2['tip'] == 16) && ($row['spr_id'] || $row['vre_id'])) {

                    if ($row['operator'] == 0)
                        echo ' = ';
                    else
                        echo ' &ne; ';

                    echo '[';

                    // obicne spremenljivke
                    if ($row['vre_id'] == 0) {
                        $sql2 = sisplet_query("SELECT * FROM srv_condition_vre c, srv_vrednost v WHERE cond_id='$row[id]' AND c.vre_id=v.id ORDER BY v.vrstni_red");
                        if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);

                        $j = 0;
                        while ($row2 = mysqli_fetch_array($sql2)) {
                            if ($j++ != 0) echo ', ';

							if($row2['vre_id'] == '-1')
								echo '-1';
							else
								echo $row2['variable'];
                        }
                    // multigrid
                    } elseif ($row['vre_id'] > 0) {

						$j = 0;

						// Preverimo pogoj -1
						$sqlX = sisplet_query("SELECT * FROM srv_condition_grid WHERE cond_id='$row[id]' AND grd_id='-1'");
						if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);
						if(mysqli_num_rows($sqlX) > 0){
							echo '-1';
							$j++;
						}

						$sql2 = sisplet_query("SELECT g.* FROM srv_condition_grid c, srv_grid g WHERE c.cond_id='$row[id]' AND c.grd_id=g.id AND g.spr_id='$row[spr_id]' ORDER BY g.part, g.vrstni_red");
                        if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);
                        while ($row2 = mysqli_fetch_array($sql2)) {
                            if ($j++ != 0) echo ', ';

							echo $row2['variable'];
                        }
                    }

                    echo ']';

                // razvrscanje
				} elseif ($row2['tip'] == 17) {

					if ($row['operator'] == 0)
                        echo ' = ';
                    else
                        echo ' &ne; ';

                    echo '[';

                    $sql2 = sisplet_query("SELECT * FROM srv_condition_grid c WHERE c.cond_id='$row[id]'");

                    $j = 0;
                    while ($row2 = mysqli_fetch_array($sql2)) {
                        if ($j++ != 0) echo ', ';
                        echo $row2['grd_id'];
                    }

                    echo ']';

                // textbox in number mata drugacne pogoje in opcije
                } elseif ( in_array($row2['tip'], array(4, 21, 7, 8, 22, 19, 20, 18)) ) {

                	if ($row2['tip'] == 19 || $row2['tip'] == 20) {
						echo '['.$row['grd_id'].']';
                	} elseif ($row2['tip'] == 7) {
						echo '['.($row['grd_id']+1).']';
                	} elseif ($row2['tip'] == 8) {
						echo '['.($row['grd_id']+1).']';
                	}

                    if ($row['operator'] == 0)
                        echo ' = ';
                    elseif ($row['operator'] == 1)
                        echo ' &ne; '; // echo ' != ';
                    elseif ($row['operator'] == 2)
                        echo ' < ';
                    elseif ($row['operator'] == 3)
                        echo ' <= ';
                    elseif ($row['operator'] == 4)
                        echo ' > ';
                    elseif ($row['operator'] == 5)
                        echo ' >= ';
					elseif ($row['operator'] == 6)
						echo ' length = ';
					elseif ($row['operator'] == 7)
						echo ' length < ';
					elseif ($row['operator'] == 8)
                        echo ' length > ';

					#vrednost pogoja
					echo '\''.$row['text'].'\'';
                }

            // recnum
            } elseif ($row['spr_id'] == -1) {

                //echo 'mod(recnum, '.$row['modul'].') = '.$row['ostanek'];
                echo ''.$lang['group'].' ('.$row['modul'].') = '.($row['ostanek'] + 1);

            // calculation
            } elseif ($row['spr_id'] == -2) {

                echo ' ( '.$this->calculations_display($row['id']).' ) ';

                if ($row['operator'] == 0)
                    echo ' = ';
                elseif ($row['operator'] == 1)
                    echo ' &ne; '; //echo ' != ';
                elseif ($row['operator'] == 2)
                    echo ' < ';
                elseif ($row['operator'] == 3)
                    echo ' <= ';
                elseif ($row['operator'] == 4)
                    echo ' > ';
                elseif ($row['operator'] == 5)
                    echo ' >= ';

                echo ''.$row['text'].'';

			// kvote
            } elseif ($row['spr_id'] == -3) {
				
				$SQ = new SurveyQuotas($this->anketa);
                echo ' ( '.$SQ->quota_display($row['id']).' ) ';

                if ($row['operator'] == 0)
                    echo ' = ';
                elseif ($row['operator'] == 1)
                    echo ' &ne; '; //echo ' != ';
                elseif ($row['operator'] == 2)
                    echo ' < ';
                elseif ($row['operator'] == 3)
                    echo ' <= ';
                elseif ($row['operator'] == 4)
                    echo ' > ';
                elseif ($row['operator'] == 5)
                    echo ' >= ';

                echo ''.$row['text'].'';
				
            // naprava
            } elseif ($row['spr_id'] == -4) {

				if(in_array($row['text'], array('0','1','2','3')))
					echo ''.$lang['srv_device'].' = '.$lang['srv_para_graph_device'.$row['text']];
				else
					echo ''.$lang['srv_device'].' = '.$lang['srv_device_type_select'];
            }

            for ($i=1; $i<=$row['right_bracket']; $i++)
                if ($long_alert == 1)
                    echo ' <span class="bracket'.((--$bracket)%12).'">)</span> ';
                else
                    echo ' ) ';

        }
		//echo '</span>';

        if ($row_if['label'] != '') {
	        echo ' <span class="if_comment">(';
	        echo ' '.$row_if['label'].' ';
	        echo ')</span> ';

        }

        $condition_check = $this->condition_check($if);

        if ($long_alert) {
        	if ($c == 1 && $spr_id == 0)
        		if ($this->count_spr_in_if($if) > 1) {
        			echo '<em>'.$lang['srv_edit_condition_question'].'</em>';
				} else {
					$spr = $this->find_first_in_if($if);
					$r = Cache::srv_spremenljivka($spr);
					if (!$spr > 0) $r['variable'] = '';
					echo '<em>'.sprintf($lang['srv_edit_condition_question1'], '<span class="variable">'.$r['variable'].'</span>').'</em>';
				}
				//if ($condition_check >= 1 && $condition_check <= 5 ) {
		        	echo '<span class="error_display">';
		            if ($condition_check == 1)
		                echo '<span class="faicon warning icon-orange"></span> <span class="red">'.$lang['srv_error_oklepaji'].'</span>';
		            if ($condition_check == 2) {
		            	if (mysqli_num_rows($sql) > 1)	// ko nardimo nov if, ne prikazemo takoj errorja
		                	echo '<span class="faicon warning icon-orange"></span> <span style="color:red">'.$lang['srv_error_spremenljivka'].'</span>';
					} elseif ($condition_check == 3)
		                echo '<span class="faicon warning icon-orange"></span> <span class="red">'.$lang['srv_error_vrednost'].'</span>';
		            if ($condition_check == 4)
		                echo '<span class="faicon warning icon-orange"></span> <span class="red">'.$lang['srv_error_numericno'].'</span>';
		            if ($condition_check == 5)
		            	echo '<span class="faicon warning icon-orange"></span> <span class="red">'.$lang['srv_error_calculation'].'</span>';
		            if ($condition_check == 6)
		            	echo '<span class="faicon warning icon-orange"></span> <span class="red">'.$lang['srv_error_date'].'</span>';
		        	echo '</span>';
				//}

        } else {
            if ($condition_check != 0)
                echo ' <span class="faicon warning icon-orange"></span> <span class="red">'.$lang['srv_if_error'].'</span>';

        }

        if ($long_alert == 0) {
	        if ($row_if['enabled'] == 1) {
				echo ' )</span>';	// span class="if_comment"
			} elseif ($row_if['enabled'] == 2) {
				echo ' )</span>';	// span class="if_comment"
			}
		}


    }

    function loop_display ($if) {
		global $lang;

		if ($if == 0) return;
		$rowb = Cache::srv_if($if);
        //začetek oklepaja za ZANKO

		echo '<strong class="clr_lp">LOOP</strong> <span class="colorloop">('.$rowb['number'].')</span>';

		$sql = sisplet_query("SELECT l.spr_id, s.variable FROM srv_loop l, srv_spremenljivka s WHERE l.if_id='$if' AND l.spr_id=s.id");
		$row = mysqli_fetch_array($sql);

		if ($row['spr_id'] == 0) return;

		$spr = Cache::srv_spremenljivka($row['spr_id']);

		echo ' '.$row['variable'].' for [';

		if ($spr['tip'] == 7) {

			echo 'value';

		} else {

			$i = 0;
			$sql1 = sisplet_query("SELECT v.variable FROM srv_loop_vre lv, srv_vrednost v WHERE lv.if_id='$if' AND lv.vre_id=v.id ORDER BY v.vrstni_red ASC");
			while ($row1 = mysqli_fetch_array($sql1)) {

				if ($i++ != 0) echo ', ';
				echo strip_tags($row1['variable']);
			}

		}

		echo ']';

		echo ($rowb['label']!=''?' <span class="if_comment">( '.$rowb['label'].' )</span>':'').'';

		if ( $this->find_parent_loop(0, $if) > 0 ) {
			echo '<span class="error_display" style="display:block; background-color:white; min-height:15px">';
			echo ' <span class="faicon warning icon-orange" title="'.$lang['srv_loop_no_nesting'].'"></span> ';
			echo '</span>';
		}
    }

    function blocks_display ($if) {
		global $lang;

        if ($if == 0) return;
        
        $rowb = Cache::srv_if($if);
        
        if ($rowb['tip'] != 1) return;

		echo '<strong class="clr_lp">BLOK</strong> <span>('.$rowb['number'].')</span>';

        if($rowb['label'] != '')
            echo ' <span>'.$rowb['label'].'</span>';
    }

    /**
    * preveri za celo anketo, če so vsi pogoji OK
    *
    */
    //public $check_pogoji_id;
    function check_pogoji () {

		// najprej gremo cez vse ife
		$sql = sisplet_query("SELECT element_if FROM srv_branching WHERE ank_id = '$this->anketa' AND element_if > 0 ORDER BY vrstni_red");
		while ($row = mysqli_fetch_array($sql)) {
			$condition_check = $this->condition_check($row['element_if']);
			if ($condition_check != 0) {
				//$this->check_pogoji_id = $row['element_if'];
				//return $condition_check;
				return array('type' => 'if', 'code' => $condition_check, 'id' => $row['element_if']);
			}
		}

		// potem moramo it se cez vse podife na vrednostih spremenljivk
		$sql = sisplet_query("SELECT v.if_id, s.id FROM srv_vrednost v, srv_spremenljivka s, srv_grupa g WHERE v.spr_id=s.id AND s.gru_id=g.id AND g.ank_id='$this->anketa' AND v.if_id > '0' ORDER BY g.vrstni_red, s.vrstni_red, v.vrstni_red");
		while ($row = mysqli_fetch_array($sql)) {
			$condition_check = $this->condition_check($row['if_id']);
			if ($condition_check != 0) {
				//$this->check_pogoji_id = $row['id'];
				//return $condition_check + 10;
				return array('type' => 'podif', 'code' => $condition_check, 'id' => $row['id']);
			}
		}

		// in pa mogoce se cez spremenljivke tipa kalkulacija ?
		// TODO maybe

		return true;
    }

    /**
    * preveri celo anketo, ce so loopi pravilno postavljeni
    * gleda pa to, da loopi niso vgnezdeni en znotraj drugega
    * za PB ne rabimo preverjat, ker se delajo ze sproti v check_loop()
    *
    */
    function check_loops () {

		// vseeno se enkrat popravimo pagebreake...
		$this->check_loop();

		$sql = sisplet_query("SELECT b.element_spr, b.element_if FROM srv_branching b, srv_if i WHERE b.ank_id='$this->anketa' AND b.element_if > '0' AND b.element_if=i.id AND i.tip='2' ORDER BY b.vrstni_red ASC");
		while ($row = mysqli_fetch_array($sql)) {

			// trenutni loop ima nekega parenta ki je tudi loop
			if ( $this->find_parent_loop($row['element_spr'], $row['element_if']) > 0 ) {

				return array('type' => 'loop', 'code' => '6', 'id' => $row['element_if']);

			}

		}

		return true;
    }

    /**
    * preveri za celo anketo, če so vse validacije OK
    *
    */
    function check_validation () {

		// najprej gremo cez vse ife
		$sql = sisplet_query("SELECT if_id, spr_id FROM srv_validation v, srv_branching b WHERE v.spr_id=b.element_spr AND b.ank_id='$this->anketa' AND b.element_spr > 0 ORDER BY b.vrstni_red");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		while ($row = mysqli_fetch_array($sql)) {
			$condition_check = $this->condition_check($row['if_id']);
			if ($condition_check != 0) {
				//$this->check_pogoji_id = $row['element_if'];
				//return $condition_check;
				return array('type' => 'validation', 'code' => $condition_check, 'id' => $row['spr_id']);
			}
		}

		return true;
    }

    /**
    * preveri, da se imena spremenljivk (vprašanj) v anketi in imena variabel znotraj vprasanja ne ponavljajo
    *
    */
    function check_variable () {

		$spremenljivke = array();
		$vrednosti = array();
		$spr_errors = array();
		$var_errors = array();

		// Napolnimo array z vprasanji
		$sql = sisplet_query("SELECT s.id, s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa'");
		while ($row = mysqli_fetch_array($sql)) {
			$spremenljivke[$row['id']] = $row['variable'];
			
			// Napolnimo array z variablami znotraj vprasanj
			$sqlV = sisplet_query("SELECT id, variable FROM srv_vrednost WHERE spr_id='".$row['id']."'");
			while ($rowV = mysqli_fetch_array($sqlV)) {
				$vrednosti[$row['id']][$rowV['id']] = $rowV['variable'];
			}
		}

		$vars = array();
		
		// Preverimo in izpišemo napake za imena vprasanj (spremenljivk)
		if ( count($spremenljivke) != count(array_unique($spremenljivke)) ) {
			$sql = sisplet_query("SELECT variable FROM srv_spremenljivka s, srv_grupa g WHERE gru_id=g.id and g.ank_id='$this->anketa' GROUP BY variable HAVING COUNT(variable) > 1");
			
			while ($row = mysqli_fetch_array($sql)) {
				$vars[] = $row['variable'];
			}
			
			$spr_errors['type'] = 'question_variable';
			$spr_errors['code'] = '7';
			$spr_errors['vars'] = $vars;
		}

		// Preverimo in izpišemo napake se za imena variabel znotraj vprasanj - ce je vklopljen modul kviz, dovolimo iste vrednosti
		if(!SurveyInfo::getInstance()->checkSurveyModule('quiz')){
			foreach($vrednosti as $spr_id => $vrednost){
				
				if ( count($vrednost) != count(array_unique($vrednost)) ) {
					$sql = sisplet_query("SELECT variable FROM srv_vrednost WHERE spr_id='".$spr_id."' GROUP BY variable HAVING COUNT(variable) > 1");

					while ($row = mysqli_fetch_array($sql)) {
						$vars[] = $spremenljivke[$spr_id] . ' - ' . $row['variable'];
					}
					
					$var_errors['type'] = 'variable';
					$var_errors['code'] = '7';
					$var_errors['vars'] = $vars;
				}
			}
		}
		
		/*if(count($spr_errors))
			return $spr_errors;*/
			
		if(count($var_errors))
			return $var_errors;

		return true;
    }
	

    /**
    * @desc preveri ali je IF pravilno nastavljen
    * error code:	0 - ok
    * 				1 - oklepaji so narobe
    * 				2 - spremenljivka ni postavljena
    * 				3 - spremenljivka nima vrednosti nastavljene
    * 				4 - number vrednost ni nastavljena
    * 				5 - kalkulacija ni v redu postavljena
    * 				6 - datum ni v pravilni obliki
    */
    function condition_check ($if) {

        $row = Cache::srv_if($if);
		
        if ($row['tip'] == 1 || $row['tip'] == 2) return 0;			// ce je blok je vse ok, ker ne more biti narobe nastavljen

        if ( !$this->condition_check_bracket($if))
        	return 1;

        if (!$this->condition_check_spremenljivka($if))
        	return 2;

        if (!$this->condition_check_spremenljivka_vre($if))
        	return 3;

        if (!$this->condition_check_number($if))
    		return 4;

    	if (!$this->condition_check_calculation($if))
    		return 5;

    	if (!$this->condition_check_date($if))
    		return 6;

		if (!$this->condition_check_device($if))
			return 7;

        return 0;

    }

    /**
    * za podani IF preveri, ce ima pravilno nastavljene vse kalkulacije
    *
    * @param int $if
    */
    function condition_check_calculation ($if) {

		$sql1 = Cache::srv_condition($if);
		while($row1 = mysqli_fetch_array($sql1)) {
			if ($row1['spr_id'] == '-2') {
				if ( $this->calculation_check($row1['id']) != 0 )
					return false;
			}
		}
		return true;
    }

    /**
    * @desc preveri ali so oklepaji pravilno postavljeni (presteje predoklepaje in zaklepaje)
    */
    function condition_check_bracket ($if) {

        //$sql = sisplet_query("SELECT * FROM srv_condition WHERE if_id='$if' ORDER BY vrstni_red");
        $sql = Cache::srv_condition($if);
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        $bracket = 0;
        while ($row = mysqli_fetch_array($sql)) {
            $bracket = $bracket + $row['left_bracket'] - $row['right_bracket'];
            if ($bracket < 0)
                return false;
        }

        if ($bracket == 0)
            return true;
        else
            return false;
    }

    /**
    * @desc preveri ali imajo vsi pogoji izbrane spremenljivke
    */
    function condition_check_spremenljivka ($if) {

        //$sql = sisplet_query("SELECT * FROM srv_condition WHERE if_id='$if'");
        $sql = Cache::srv_condition($if);
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        while ($row = mysqli_fetch_array($sql)) {
            if (!($row['spr_id'] > 0 || $row['spr_id'] == -1 || $row['spr_id'] == -2 || $row['spr_id'] == -3 || $row['spr_id'] == -4))
                return false;
        }

        return true;
    }

    /**
    * @desc preveri ali imajo vse spremenljivke izbrane vrednosti
    */
    function condition_check_spremenljivka_vre ($if) {

        $sql = Cache::srv_condition($if);
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        while ($row = mysqli_fetch_array($sql)) {
            if ($row['spr_id'] > 0) {

            	$row1 = Cache::srv_spremenljivka($row['spr_id']);

                if ($row['vre_id'] == 0) {

                    if ($row1['tip'] <= 3) {

                        $sql2 = sisplet_query("SELECT * FROM srv_condition_vre WHERE cond_id='$row[id]'");
                        if (mysqli_num_rows($sql2) == 0)
                            return false;

                    }

                } elseif ($row['vre_id'] > 0) {
                	// tabela radio, tabela checkbox
                	if ($row1['tip'] == 6 || $row1['tip'] == 16 || $row1['tip'] == 17) {
	                    $sql2 = sisplet_query("SELECT * FROM srv_condition_grid WHERE cond_id='$row[id]'");
	                    if (mysqli_num_rows($sql2) == 0)
	                        return false;

					// textbox
					} else {
						// ok... besedilo ima samo polje za vnos, ki pa je lahko prazno
					}

                }
            }
        }

        return true;
    }
	
    /**
    * @desc preveri ali imajo datumi pravilno obliko
    */
    function condition_check_date ($if) {

        $sql = Cache::srv_condition($if);
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        while ($row = mysqli_fetch_array($sql))
        {
            if ($row['spr_id'] > 0)
            {
            	$row1 = Cache::srv_spremenljivka($row['spr_id']);
            	if ($row1['tip'] == 8) {
            		if (empty($row['text']) || trim($row['text']) == '')
            		{
            			return false;
            		}
            		else if ((int)$row['text'] <= 0)
            		{
            			return false;
            		}
            		else
            		{
            			#preverimo obliko datuma
            			$fields = explode('.',$row['text']);
            			if (!is_numeric($fields[0]) || (int)$fields[0] == 0
            				|| !is_numeric($fields[1]) || (int)$fields[1] == 0
            				|| !is_numeric($fields[2]) || (int)$fields[2] == 0)
            			{
            				return false;
            			}
            			return checkdate((int)$fields[1],(int)$fields[0],(int)$fields[2]);
					}

                }
            }
        }

        return true;
    }
	
	 /**
    * @desc preveri ali je naprava ustrezno nastavljena
    */
    function condition_check_device ($if) {

        $sql = Cache::srv_condition($if);
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        while ($row = mysqli_fetch_array($sql)) {
            if ($row['spr_id'] == -4 && !in_array($row['text'], array('0','1','2','3'))){
				return false;
			}             
        }

        return true;
    }

    /**
    * @desc preveri ali imajo number polja z numeri�nim operatorjem vpisano �tevilko
    */
    function condition_check_number ($if) {

        // number
        $sql = sisplet_query("SELECT c.* FROM srv_condition c, srv_spremenljivka s WHERE s.id=c.spr_id AND s.tip IN (7, 18, 20)  AND c.if_id='$if'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        while ($row = mysqli_fetch_array($sql)) {

            if ( ! (is_numeric($row['text']) || $row['text'] == '') )
                return false;
        }

        // calculation
        $sql = Cache::srv_condition($if);
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        while ($row = mysqli_fetch_array($sql)) {
        	if ($row['spr_id'] == -2) {
	            if (!is_numeric($row['text']))
	                return false;
			}
        }

        return true;

    }

    /**
    * @desc preveri ali so oklepaji pravilno postavljeni v kalkulacijah (presteje predoklepaje in zaklepaje)
    * in pa ce je izbrana spremenljivka
    */
    function calculation_check ($condition) {

        $sql = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id='$condition' ORDER BY vrstni_red");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

		if (mysqli_num_rows($sql) == 0) return 1;	// ni sploh se izbrana spremenljivka (ker ni calculation sploh se inicializiran z default vrstico)

        $bracket = 0;
        while ($row = mysqli_fetch_array($sql)) {
        	if ($row['spr_id'] == 0)	// ce ni izbrana spremenljivka v eni od vrstic
        		return 2;

            $bracket = $bracket + $row['left_bracket'] - $row['right_bracket'];
            if ($bracket < 0)
                return 3;		// oklepaj
        }

        if ($bracket == 0)
            return 0;			// vse ok
        else
            return 3;			// zaklepaj
    }

    /**
    * @desc izpise form za urejanje pogojev
    * @param int ID ifa, ki mu urejamo pogoje
    * @param int Pri urejanju podifov (na multigridih, radio,..) podamo tudi ID vrednosti na katero se if navezuje
    */
    function condition_editing ($if, $vrednost=0, $edit_fill_value=0) {
        global $lang;

        // Pogoj na vrednost
        if($vrednost > 0){
            echo '<h2>'.$lang['urejanje_pogoj_popup_vrednost'].'</h2>';
            echo '<div class="popup_close"><a href="#" onClick="vrednost_condition_editing_close(\''.$vrednost.'\', \''.$if.'\'); return false;"">✕</a></div>';

            echo '<span class="bold">'.$lang['urejanje_podif_alert'].'</span>';
            
            echo '<br /><br />';
			
			$sql = sisplet_query("SELECT naslov FROM srv_vrednost WHERE id='$vrednost'");
			$row = mysqli_fetch_assoc($sql);
			echo '<div class="condition_editing_vrednost_title">'.$row['naslov'].'</div>';
        }
        // Pogoj pri npr. obvescanju
        elseif($vrednost == -3){
            echo '<h2>'.$lang['urejanje_pogoj_popup'].'</h2>';
            echo '<div class="popup_close"><a href="#" onClick="alert_if_close(\''.$vrednost.'\', \''.$if.'\'); return false;">✕</a></div>';
        }
        // Validacija
        elseif($vrednost == -4){
            echo '<h2>'.$lang['urejanje_pogoj_popup_validacija'].'</h2>';
            echo '<div class="popup_close"><a href="#" onClick="validation_if_close(\''.$this->spremenljivka.'\', \''.$if.'\'); return false;">✕</a></div>';
        }

		$row = Cache::srv_if($if);

		// osnovni okvir vsebine, ce prebije visino dobi scroolbar
        echo ' <div id="div_condition_editing_container">';
		echo '    <div id="div_condition_editing_inner"> ';

		$this->condition_editing_inner($if, $vrednost, $edit_fill_value);

        echo '    </div><!-- id="div_condition_editing_inner" -->';
		echo '  </div><!-- id="div_condition_editing_container" -->';

		echo '  <div class="clr"></div>';
		//echo '  </div><!-- id="div_condition_editing_close" -->';
		echo '  <div class="clr"></div>';


        // gumbi na desni v novem oknu
		if ($vrednost==0) {

			// floating box
			echo '<div id="div_condition_editing_float">';

			if ($row['tip'] == 0)
				echo '<h2>'.$lang['oblikovanje_if'].'</h2>';
			elseif ($row['tip'] == 1)
				echo '<h2>'.$lang['oblikovanje_blok'].'</h2>';
			elseif ($row['tip'] == 2)
				echo '<h2>'.$lang['srv_loop_urejanje'].'</h2>';

			if ($row['tip'] == 0)
				echo '<p class="heading">'.$lang['srv_edit_condition_question'].'</p>';
			elseif ($row['tip'] == 1)
				echo '<p class="heading">'.$lang['srv_block_desc'].'</p>';

	        if ($vrednost != -1 && $vrednost != -2) {
			    echo '<p><span class="title">';
			    echo ($row['tip']==0?$lang['srv_if_label']:($row['tip']==1?$lang['srv_block_label']:$lang['srv_loop_label'])).':</span>';
			    echo '<span class="content"><input type="text" name="label_'.$if.'" id="label_'.$if.'" value="'.$row['label'].'" ></span>';
			    echo '</p>';

        		echo '<script type="text/javascript">'; // shranimo ko zapustmo input polje
				echo '$(document).ready(function() {' .
				'  $("input#label_'.$if.'").bind("keyup", {}, function(e) {' .
				'    edit_label(\''.$if.'\'); return false;  ' .
				'  });' .
				'});';
				echo '</script>';

			}

			if ($row['tip'] == 0 || $row['tip'] == 1) {
				
				echo '<fieldset>';
				
				echo '<p><span class="title">'.($row['tip'] == 1 ? $lang['srv_block_enabled'] : $lang['srv_if_enabled']).':</span>';

				echo'<span class="displayBlock" style="padding:5px 0 0 5px;">';
				echo '<label for="if_edit_0"><input type="radio" class="enka-admin-custom" value="0" name="if_edit" id="if_edit_0" '.($row['enabled']==0?' checked="checked"':'').' onClick="if_edit_enabled(\''.$row['id'].'\', $(this).val())" /><span class="enka-checkbox-radio"></span>'.$lang['srv_if_enabled_'.$row['tip']].'</label>';
				if ($row['tip'] == 0)
					echo '<span class="spaceLeft"><label for="if_edit_1"><input type="radio" value="1" name="if_edit" id="if_edit_1" class="enka-admin-custom" '.($row['enabled']==1?' checked="checked"':'').' onClick="if_edit_enabled(\''.$row['id'].'\', $(this).val())" /><span class="enka-checkbox-radio"></span>'.$lang['srv_if_enabled_1'].'</label></span>';
				echo '<span class="spaceLeft"><label for="if_edit_2"><input type="radio" value="2" name="if_edit" id="if_edit_2" class="enka-admin-custom" '.($row['enabled']==2?' checked="checked"':'').' onClick="if_edit_enabled(\''.$row['id'].'\', $(this).val())" /><span class="enka-checkbox-radio"></span>'.$lang['srv_if_enabled_2'].'</label></span>';
				echo '</span>';

				echo '</p>';	

				echo '</fieldset>';
			}
			
			// Posebna nastavitev vrednosti statusa panelista ce je vklopljen modul "panel"
			if ($row['tip'] == 0 && SurveyInfo::getInstance()->checkSurveyModule('panel')) {
				
				echo '<fieldset>';
				echo '<p><span class="title">'.$lang['srv_panel_if'].':</span>';

				$sp = new SurveyPanel($this->anketa);
				$panel_if = $sp->getPanelIf($if);
				
				echo'<span class="displayBlock" style="padding:5px 0 0 5px;">';
				echo '<input type="text" name="panel_status_'.$if.'" id="panel_status_'.$if.'" value="'.$panel_if.'" ></span>';
			    echo '</span>';
				
        		echo '<script type="text/javascript">'; // shranimo ko zapustmo input polje
				echo '$(document).ready(function() {' .
				'  $("input#panel_status_'.$if.'").bind("keyup", {}, function(e) {' .
				'    edit_panel_status(\''.$if.'\'); return false;  ' .
				'  });' .
				'});';
				echo '</script>';

				echo '</p>';	
				echo '</fieldset>';
			}

			if ($row['tip'] == 1) {
			
				echo '<fieldset>';
			
				// Blok ki prikaze nakljucno razvrscena vprasanja (in samo doloceno stevilo)
				echo '<p>';
				echo '	<span class="title">'.$lang['srv_block_random'].': </span>'.Help::display('srv_block_random');
				echo '	<span class="displayBlock" style="padding:5px 0 0 5px;">';
				echo '		<label for="if_random_-1"><input type="radio" value="-1" class="enka-admin-custom small-padding" name="if_random" id="if_random_-1" '.($row['random']==-1?' checked="checked"':'').' onClick="if_blok_random(\''.$row['id'].'\', $(this).val());" /><span class="enka-checkbox-radio"></span>'.$lang['no1'].'</label>';
				echo '		<span class="spaceLeft"><label for="if_random_-2"><input type="radio" value="-2" class="enka-admin-custom small-padding" name="if_random" id="if_random_-2" '.($row['random']==-2?' checked="checked"':'').' onClick="if_blok_random(\''.$row['id'].'\', $(this).val())" /><span class="enka-checkbox-radio"></span>'.$lang['srv_block_random_blocks'].'</label></span>';
				echo '		<span class="spaceLeft"><label for="if_random_0"><input type="radio" value="0" class="enka-admin-custom small-padding" name="if_random" id="if_random_0" '.($row['random']>=0?' checked="checked"':'').' onClick="if_blok_random(\''.$row['id'].'\', $(this).val())" /><span class="enka-checkbox-radio"></span>'.$lang['srv_block_random_questions'].'</label></span>';
				echo '	</span>';
				echo '</p>';
				
				// Stevilo vprasanj ki jih nakljucno izberemo (ce je zgornja "da")
				echo '<p id="if_blok_random_cnt" '.(($row['random'] >= 0) ? '' : ' style="display:none;"').'><span class="title">'.$lang['srv_block_random_cnt'].':</span><span class="content">';
				echo '<select onchange="if_blok_random_cnt(\''.$row['id'].'\', $(this).val());">';
				echo '	<option value="0" '.($row['random']==0 ? 'selected' : '').'>'.$lang['srv_block_random_all'].'</option>';
				$sqlB = sisplet_query("SELECT count(*) AS cnt_spr FROM srv_branching 
										WHERE ank_id='".$this->anketa."' AND parent='".$row['id']."' AND element_spr>'0'
										ORDER BY vrstni_red");
				$rowB = mysqli_fetch_array($sqlB);
				if ($rowB['cnt_spr'] > 0){
					for($i=1; $i<$rowB['cnt_spr']; $i++){
						echo '	<option value="'.$i.'" '.($row['random']==$i ? 'selected' : '').'>'.$i.'</option>';
					}
				}
				echo '</select>';			
				
				// Blok ki vsebuje horizontalno urejena vprasanja
				echo '<p><span class="title">'.$lang['srv_orientacija_vprasanja'].':</span><span class="content">';
				echo '<select name="if_blok_horizontal" onchange="if_blok_horizontal(\''.$row['id'].'\', $(this).val());">';
				echo '	<option value="0" '.($row['horizontal']==0?'selected':'').'>'.$lang['srv_orientacija_classic'].'</option>';
				echo '	<option value="2" '.($row['horizontal']==2?'selected':'').'>'.$lang['srv_orientacija_expand'].'</option>';
				echo '	<option value="1" '.($row['horizontal']==1?'selected':'').'>'.$lang['srv_orientacija_horizontalna_3'].'</option>';
				echo '</select>';
				echo '</span></p>';
							
				// Blok kot zavihek
				echo '<p><span class="title">'.$lang['srv_block_tab'].':</span><span class="content">';
				echo '<select name="if_blok_tab" onchange="if_blok_tab(\''.$row['id'].'\', $(this).val());">';
				echo '	<option value="0" '.($row['tab']==0?'selected':'').'>'.$lang['no'].'</option>';
				echo '	<option value="1" '.($row['tab']==1?'selected':'').'>'.$lang['yes'].'</option>';
				echo '</select>';
				echo '</span></p>';
								
				// Opozorilo, da mora biti blok na svoji strani (ce je vklopljena katera od zgornjih nastavitev)
				echo '<p id="blok_pb_warning" '.($row['tab']==1 || $row['horizontal']==1 || $row['random']>=0 ? '' : ' style="display:none;"').'><span class="red bold">'.$lang['srv_block_pbWarning'].'</span></p>';
			
				echo '</fieldset>';
			}
			

			echo '<span id="condition_editing_bottom_placeholder"></span>';
				
			echo '<span class="buttonwrapper spaceRight floatLeft" id="if_remove_all" style="margin-bottom:10px">';
            echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="if_remove(\''.$if.'\', \'1\', \'0\'); return false;" title="'.($row['tip'] == 0 ? $lang['srv_if_rem_all'] : ($row['tip']==1?$lang['srv_block_rem_all']:$lang['srv_loop_rem_all']) ).'"><span>'.($row['tip'] == 0 ? $lang['srv_if_rem_all'] : ($row['tip']==1?$lang['srv_block_rem_all']:$lang['srv_loop_rem_all']) ).'</span></a>'."\n\r";
			echo '</span>';

			echo '<div class="clr"></div>';

            echo '<span class="buttonwrapper spaceRight floatLeft">';
            echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="condition_editing_close(\''.$if.'\'); return false;">'.$lang['srv_zapri'].'</a>';
            echo '</span>';

            echo '<span class="buttonwrapper spaceRight floatLeft">';
            echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="if_remove(\''.$if.'\'); return false;" title="'.($row['tip'] == 0 ? $lang['srv_if_rem'] : ($row['tip']==1?$lang['srv_block_rem']:$lang['srv_loop_rem']) ).'"><span>'.($row['tip'] == 0 ? $lang['srv_if_rem'] : ($row['tip']==1?$lang['srv_block_rem']:$lang['srv_loop_rem']) ).'</span></a>'."\n\r";
			echo '</span>';

            echo '</div>';	// -- div_condition_editing_float

        } 
        // filter v DisplayData
        elseif ($vrednost == -1) {    
            // tega niti ni vec...
        } 
         // profili filtrov
        elseif ($vrednost == -2) {   

        } 
        // klasicen popup v #div_condition_editing, uporablja se npr pri ifih za obvescanje
        elseif ($vrednost == -3) {	
			echo '<span class="buttonwrapper floatRight">';
            echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="alert_if_close(\''.$vrednost.'\', \''.$if.'\'); return false;"><span>'.$lang['srv_potrdi'].'</span></a>';
        	echo '</span>';
        	echo '<span class="buttonwrapper spaceRight floatRight">';
            echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="alert_if_close(\''.$vrednost.'\', \''.$if.'\'); return false;"><span>'.$lang['srv_zapri'].'</span></a>';
        	echo '</span>';
        	echo '<span class="buttonwrapper spaceRight floatRight">';
            echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="alert_if_remove(\''.$if.'\'); return false;" title="'.$lang['srv_if_rem'].'"><span>'.$lang['srv_if_rem'].'</span></a>'."\n\r";
			echo '</span>';

        } 
        // validacija
        elseif ($vrednost == -4) {	
			echo '<span class="buttonwrapper floatRight">';
            echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="validation_if_close(\''.$this->spremenljivka.'\', \''.$if.'\'); return false;"><span>'.$lang['srv_potrdi'].'</span></a>';
        	echo '</span>';
        	echo '<span class="buttonwrapper spaceRight floatRight">';
            echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="validation_if_close(\''.$this->spremenljivka.'\', \''.$if.'\'); return false;"><span>'.$lang['srv_zapri'].'</span></a>';
        	echo '</span>';
        	echo '<span class="buttonwrapper spaceRight floatRight">';
            echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="validation_if_remove(\''.$this->spremenljivka.'\', \''.$if.'\'); return false;" title="'.$lang['srv_if_rem'].'"><span>'.$lang['srv_if_rem'].'</span></a>'."\n\r";
			echo '</span>';

        } 
        // filter na vrednosti
        else {						
        	echo '<span class="buttonwrapper floatRight">';
            echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="vrednost_condition_editing_close_save(\''.$vrednost.'\', \''.$if.'\'); return false;"><span>'.$lang['srv_potrdi'].'</span></a>';
        	echo '</span>';
        	echo '<span class="buttonwrapper spaceRight floatRight">';
            echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_condition_editing_close(\''.$vrednost.'\', \''.$if.'\'); return false;"><span>'.$lang['srv_zapri'].'</span></a>';
        	echo '</span>';
        	echo '<span class="buttonwrapper spaceRight floatRight">';
            echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_if_remove(\''.$if.'\', \''.$vrednost.'\'); return false;" title="'.$lang['srv_if_rem'].'"><span>'.$lang['srv_if_rem'].'</span></a>'."\n\r";
			echo '</span>';
        }

		echo '<div class="clr"></div>';

		?><script>
		$('#div_condition_editing_inner').sortable({items: 'form', handle: 'img.move', stop: function () {
			condition_sort(<?=$if?>);
		} });

		</script><?
    }

    function condition_editing_inner ($if, $vrednost=0, $edit_fill_value=0) {
		global $lang;

        $row = Cache::srv_if($if);

		// if
        if ($row['tip'] == 0) {

        	echo '<div class="condition_editing_preview">';

        	//echo '<div class="condition_editing_naslov">'.$lang['sintaksa_if'].'</div>';

        	echo '<div class="condition_editing_naslov_holder">';
        	echo '<div id="div_condition_editing_conditions">';
            //zacetni oklepaj za if
//            echo '<span class="zacetni-oklepaj" id="zacetni_oklepaj_'.$if.'" style="display:none;"></span>';
            $this->conditions_display($if, 1, 1);
            echo '</div>';
            echo '</div>';
         	echo '</div><!-- condition_editing_preview -->';


         	echo '<div class="condition_editing_body">';

            $sql1 = Cache::srv_condition($if);
            if (!$sql1) { echo mysqli_error($GLOBALS['connect_db']); die('1'); }

            while ($row1 = mysqli_fetch_array($sql1)) {
                $this->condition_edit($row1['id'], $vrednost, $edit_fill_value);
                $spr_id = $row1['spr_id'];
            }

            echo '</div><!-- class="condition_editing_body"-->';

            
			if ( ! ( mysqli_num_rows($sql1)==1 && $spr_id==0 ) ) {
	            echo '<div id="div_condition_editing_operators" style="padding-left:1%">'.$lang['srv_add_cond'].' '.Help::display('srv_if_operator').':

	            <a href="#" onclick="condition_add(\''.$if.'\', \'0\', \'0\', \''.$vrednost.'\'); return false;"><strong>&nbsp;AND&nbsp;</strong></a>,
	            <a href="#" onclick="condition_add(\''.$if.'\', \'0\', \'1\', \''.$vrednost.'\'); return false;"><strong>&nbsp;AND NOT&nbsp;</strong></a>,
	            <a href="#" onclick="condition_add(\''.$if.'\', \'1\', \'0\', \''.$vrednost.'\'); return false;"><strong>&nbsp;OR&nbsp;</strong></a>,
	            <a href="#" onclick="condition_add(\''.$if.'\', \'1\', \'1\', \''.$vrednost.'\'); return false;"><strong>&nbsp;OR NOT&nbsp;</strong></a></div>';
            }	
        } 
        // blok
        elseif ($row['tip'] == 1) {

            //ko imamo BLOCK prikažemo začetek oklepaja
            echo '<strong class="clr_bl">BLOCK</strong> <span class="colorblock">('.$row['number'].')</span>'.($row['enabled']==2?' FALSE ':'').($row['label']!=''?' <span class="if_comment">( '.$row['label'].' )</span>':'').'';

       
        } 
        // zanka
        elseif ($row['tip'] == 2) {
            //začetni oklepaj za zanko

            $this->loop_display($if);

			echo '<span class="error_display"></span>';
			echo '<div class="condition_editing_body">';

            echo '<p style="padding:10px">'.$lang['srv_loop_desc'].' '.Help::display('DataPiping').'</p>';

            if ( $this->find_parent_loop(0, $if) > 0 ) {
				echo '<p class="red"><span class="faicon warning icon-orange" title="'.$lang['srv_loop_no_nesting'].'"></span> '.$lang['srv_loop_no_nesting'].'</p>';
			} else {
				echo '<p style="padding:10px">'.$lang['srv_loop_no_nesting'].'</p>';
			}

			$this->loop_edit($if);

			echo '</div><!-- class="condition_editing_body"-->';

			if (true) {
				$sql = sisplet_query("SELECT count(*) AS count FROM srv_user WHERE ank_id='$this->anketa' AND deleted='0'");
				$row = mysqli_fetch_array($sql);
				if ($row['count'] > 10) {
					?><script>
						if ( ! confirm('<?=$lang['srv_loop_edit_alert']?>') ) {
							condition_editing_close('<?=$if?>');
						}
					</script><?
				}
			}

        }

    }

    /**
    * @desc izpise form za urejanje pogoja
    * @param id conditiona
    * @param vrednost, na katero se nanasa if v podifih
    */
    private $dropdown_query = null;
    function condition_edit ($condition, $vrednost=0, $edit_fill_value=0) {
        global $lang;
		global $admin_type;

        $sql = sisplet_query("SELECT * FROM srv_condition WHERE id = '$condition'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        if ($vrednost == 0) {   // ce imamo obicn if v branchingu ali v DisplayData
            $vrstni_red = $this->vrstni_red($this->find_before_if($row['if_id']));
        } elseif ($vrednost == -1 or $vrednost == -2 or $vrednost == -3) {  // filter v DisplayData.php (prikazemo vse spremenljivke)
            $vrstni_red = PHP_INT_MAX;
		} elseif ($vrednost == -4) {	// validacija
			if (!$this->spremenljivka > 0) {
				$sqlv = sisplet_query("SELECT spr_id FROM srv_validation WHERE if_id = '$row[if_id]'");
				$rowv = mysqli_fetch_array($sqlv);
				$this->spremenljivka = $rowv['spr_id'];
			}
			$vrstni_red = $this->vrstni_red($this->spremenljivka);
        } else {      // ce imamo podif na vrednosti
            $sqlv = sisplet_query("SELECT spr_id FROM srv_vrednost WHERE if_id='$row[if_id]'");
            $rowv = mysqli_fetch_array($sqlv);
            $vrstni_red = $this->vrstni_red($rowv['spr_id']);
        }

        $sql_count = sisplet_query("SELECT COUNT(*) AS count FROM srv_condition WHERE if_id='$row[if_id]'");
        if (!$sql_count) die();
        $row_count = mysqli_fetch_array($sql_count);

        if ($row['vrstni_red'] == 1 && $row_count['count'] > 1) {
			echo '<table class="tbl_condition_editing">';
			echo '<tr>';
			echo '<th style="text-align:center; width:50px">'.$lang['srv_oklepaji'].'</th>'; // gumbi oklepaji
			echo '<th  style="width:70px">&nbsp;</th>'; // logicni operatorji
			echo '<th style="width:50px">&nbsp;</th>'; // oklepaji
			echo '<th >&nbsp;</th>';
			echo '<th style="text-align:center; width:50px;">'.$lang['srv_zaklepaji'].'</th>'; // gumbi zaklepaji
			echo '<th style="text-align:center; width:60px">'.$lang['edit2'].'</th>'; // move
			echo '</tr>';
        	echo '</table>';

        } elseif ($row['vrstni_red'] == 1) {
			echo '<table class="tbl_condition_editing"><tr><th>&nbsp;</th></tr></table>';
        }

        // form
        echo '<form name="condition_'.$condition.'" action="" method="post" onsubmit="condition_edit(\''.$condition.'\'); return false;" id="condition_'.$condition.'">'."\n\r";

        echo '<table class="tbl_condition_editing" style="margin-bottom:10px; background-color:white" >';
        echo '<tr>';

        // left_bracket
		if ($row_count['count'] != 1 || $row['left_bracket']>0 || $row['right_bracket']>0) {
            echo '<td class="tbl_ce_lol white" style="width:50px; text-align:center;" >';
			echo '<a href="#" onclick="javascript:bracket_edit_new(\''.$condition.'\', \''.$vrednost.'\', \'left\', \'plus\' ); return false;" title="'.$lang['srv_oklepaj_add'].'"><span class="faicon add small"></span></a>';
			if ($row['left_bracket'] > 0)
				echo '<a href="#" onclick="javascript:bracket_edit_new(\''.$condition.'\', \''.$vrednost.'\', \'left\', \'minus\'); return false;" title="'.$lang['srv_oklepaj_rem'].'"><span class="faicon delete_circle"></span></a>';
			else
				echo '<span class="faicon delete_circle icon-grey_normal"></span>';
		} else {
            echo '<td class="tbl_ce_lol white" style="width:50px; text-align:center;" >';
		}
		echo '</td>';


        // conjunction
        echo '<td class="tbl_ce_tb white" style="width:80px; text-align:center">';

        $operator = $row['conjunction'].'_'.$row['negation'];

        echo '<input type="hidden" name="conjunction_'.$condition.'" id="conjunction_'.$condition.'" value="'.$row['conjunction'].'_'.$row['negation'].'" />';

        // Prikazujemo samo pri prvem in ce je ze izbran not
        if ($row['vrstni_red'] == 1) {

            if($row['negation'] == 1)
			    echo '<span style="font-weight:bold"><a href="#" onclick="conjunction_edit(\''.$condition.'\', \'0\', \'0\'); return false;">&nbsp;&nbsp;not&nbsp;&nbsp;</a></span>';
        } 
        else {

            echo '<select name="conjunction_dropdown_'.$condition.'" id="conjunction_dropdown_'.$condition.'" onChange="conjunction_dropdown_edit(\''.$condition.'\'); return false;">';
            echo '  <option value="0_0" '.($operator == '0_0' ? 'selected="selected"' : '').'>AND</option>';
            echo '  <option value="1_0" '.($operator == '1_0' ? 'selected="selected"' : '').'>OR</option>';
            echo '  <option value="0_1" '.($operator == '0_1' ? 'selected="selected"' : '').'>AND NOT</option>';
            echo '  <option value="1_1" '.($operator == '1_1' ? 'selected="selected"' : '').'>OR NOT</option>';
            echo '</select>';
        }
        
        echo '</td>';

	    // display bracket
		echo '<td class="tbl_ce_tb white" style="width:50px; text-align:center" nowrap>';
		for ($i=1; $i<=$row['left_bracket']; $i++) echo ' ( ';
		echo '</td>';

		// spremenljivka
        echo '<td class="tbl_ce_tb white" style="width:150px">'.($row['spr_id']=='0'?'<span class="red">'.$lang['srv_select_spr'].'!</span>':'').'<br />';
        echo '<select class="spremenljivka_select spaceRight" name="spremenljivka_'.$condition.'" id="spremenljivka_'.$condition.'" size="1" style="width:150px" onchange="javascript:fill_value(\''.$condition.'\', \''.$vrednost.'\');">'."\n\r";

        echo '<option value="0"></option>';
        echo '<option value="-1"'.($row['spr_id']==-1?' selected="selected"':'').' style="color:blue">&nbsp;&nbsp;&nbsp; '.$lang['srv_random_groups'].'</option>';
        echo '<option value="-2"'.($row['spr_id']==-2?' selected="selected"':'').' style="color:blue">&nbsp;&nbsp;&nbsp; '.$lang['srv_calc'].'</option>';
		// Kvota
		//if($admin_type == 0)
		echo '<option value="-3"'.($row['spr_id']==-3?' selected="selected"':'').' style="color:blue">&nbsp;&nbsp;&nbsp; '.$lang['srv_quota'].'</option>';
		echo '<option value="-4"'.($row['spr_id']==-4?' selected="selected"':'').' style="color:blue">&nbsp;&nbsp;&nbsp; '.$lang['srv_device'].'</option>';

        // query za izrisat dropdown kesiramo, ker je isti pri vsakem conditionu
        if ($this->dropdown_query == null) {
	        $sql1 = sisplet_query("SELECT s.*, g.naslov AS grupa_naslov
	                            FROM srv_spremenljivka s, srv_grupa g
	                            WHERE g.ank_id='$this->anketa' AND s.gru_id=g.id AND s.tip IN (1,2,3, 6, 16, 4,7,8, 21, 22,25, 19, 20, 18, 17, 24)
	                            ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");

	        if(!$sql1) echo mysqli_error($GLOBALS['connect_db']);
			$this->dropdown_query = $sql1;
		} else {
			mysqli_data_seek($this->dropdown_query, 0);
			$sql1 = $this->dropdown_query;
		}
		$prev_grupa = 0;

        while ($row1 = mysqli_fetch_array($sql1)) {

            if ($this->vrstni_red($row1['id']) <= $vrstni_red) {

            	if ($row1['gru_id'] != $prev_grupa) {
					echo '<option value="0" disabled style="font-style: italic;">'.$row1['grupa_naslov'].'</option>';
					$prev_grupa = $row1['gru_id'];
            	}

                // tabela radio, tabela checkbox
                if ( in_array($row1['tip'], array(6, 16, 17)) ) {

                	echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.strip_tags($row1['naslov']).'</option>';

                    $sql2 = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE spr_id='$row1[id]' ORDER BY vrstni_red ASC");
                    while ($row2 = mysqli_fetch_array($sql2)) {

                        if ($row2['id'] == $row['vre_id'])
                            $selected = ' selected="selected"';
                        else
                            $selected = '';

                        echo '<option value="vre_'.$row2['id'].'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.$row2['variable'].') '.strip_tags($row2['naslov']).'</option>'."\n\r";

                    }

				// tabela textbox, tabela stevilo
				} elseif ( in_array($row1['tip'], array(19, 20)) ) {

                	echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.strip_tags($row1['naslov']).'</option>';

                	$sql3 = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id='$row1[id]' AND other='0' ORDER BY vrstni_red ASC");

                    $sql2 = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE spr_id='$row1[id]' ORDER BY vrstni_red ASC");
                    while ($row2 = mysqli_fetch_array($sql2)) {

                        echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.$row2['variable'].') '.strip_tags($row2['naslov']).'</option>'."\n\r";

                        mysqli_data_seek($sql3, 0);
	                    while ($row3 = mysqli_fetch_array($sql3)) {

	                        if ($row2['id'] == $row['vre_id'] && $row3['id'] == $row['grd_id'])
	                            $selected = ' selected="selected"';
	                        else
	                            $selected = '';

	                        echo '<option value="grd_'.$row2['id'].'_'.$row3['id'].'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.$row3['id'].') '.strip_tags($row3['naslov']).'</option>'."\n\r";
						}
                    }

				// textbox
				} elseif ( $row1['tip'] == 21 ) {

					echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.strip_tags($row1['naslov']).'</option>'."\n\r";

					$i=1;
					$sql2 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$row1[id]' ORDER BY vrstni_red ASC");
                    while ($row2 = mysqli_fetch_array($sql2)) {

                        if ($row2['id'] == $row['vre_id'])
                            $selected = ' selected="selected"';
                        else
                            $selected = '';

                        echo '<option value="vre_'.$row2['id'].'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.$i.') '.$i.'. '.$lang['srv_field'].'</option>'."\n\r";
						$i++;
                    }

				// number
				} elseif ($row1['tip'] == 7) {

					echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.strip_tags($row1['naslov']).'</option>'."\n\r";

					// number ima lahko dva polja
					for ($i=0; $i<$row1['size']; $i++) {

						if ($row1['id'] == $row['spr_id'] && $i == $row['grd_id'])
	                        $selected = ' selected="selected"';
	                    else
	                        $selected = '';

	                    echo '<option value="num_'.$row1['id'].'_'.$i.'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.($i+1).') '.($i+1).'. '.$lang['srv_field'].'</option>'."\n\r";
					}

					// datum
				} elseif ($row1['tip'] == 8) {
					echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.strip_tags($row1['naslov']).'</option>'."\n\r";

					// datum
					$i=0;
					if ($row1['id'] == $row['spr_id'] && $i == $row['grd_id'])
                        $selected = ' selected="selected"';
                    else
                        $selected = '';

	                    echo '<option value="num_'.$row1['id'].'_'.$i.'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.($i+1).') '.($i+1).'. '.$lang['srv_field'].'</option>'."\n\r";

				// vsota -- uporabimo opcijo vre_ ker se shranjuje isto kot multigrid, textbox - ma srv_vrednost
				} elseif ($row1['tip'] == 18) {

					echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.strip_tags($row1['naslov']).'</option>'."\n\r";

					$i=1;
					$sql2 = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE spr_id='$row1[id]' ORDER BY vrstni_red ASC");
                    while ($row2 = mysqli_fetch_array($sql2)) {

                        if ($row2['id'] == $row['vre_id'])
                            $selected = ' selected="selected"';
                        else
                            $selected = '';

                        echo '<option value="vre_'.$row2['id'].'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.$row2['variable'].') '.strip_tags($row2['naslov']).'</option>'."\n\r";
						
						$i++;
                    }
					
				// kombinirana tabela - izvedemo query na vseh notranjih tabelah
				} elseif ($row1['tip'] == 24) {

					$sqlMT =  sisplet_query("SELECT s.id, s.tip, s.variable, s.naslov
	                            FROM srv_spremenljivka s, srv_grid_multiple mt
	                            WHERE mt.ank_id='$this->anketa' AND mt.parent='".$row1['id']."' AND mt.spr_id=s.id
	                            ORDER BY mt.vrstni_red ASC");
					if(!$sqlMT) echo mysqli_error($GLOBALS['connect_db']);
				
					while ($rowMT = mysqli_fetch_array($sqlMT)) {
						
						// Notranja tabela radio ali tabela checkboxov
						if(in_array($rowMT['tip'], array(6, 16))){
							echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].' - '.$rowMT['variable'].') '.strip_tags($rowMT['naslov']).'</option>';

							$sql2 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$rowMT[id]' ORDER BY vrstni_red ASC");
							while ($row2 = mysqli_fetch_array($sql2)) {

								if ($row2['id'] == $row['vre_id'])
									$selected = ' selected="selected"';
								else
									$selected = '';

								echo '<option value="vre_'.$row2['id'].'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.$row2['variable'].') '.strip_tags($row2['naslov']).'</option>'."\n\r";
							}
						}
						// Notranja tabela text ali number
						elseif(in_array($rowMT['tip'], array(19, 20))){
							echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].' - '.$rowMT['variable'].') '.strip_tags($rowMT['naslov']).'</option>';

							$sql3 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$rowMT[id]' AND other='0' ORDER BY vrstni_red ASC");

							$sql2 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$rowMT[id]' ORDER BY vrstni_red ASC");
							while ($row2 = mysqli_fetch_array($sql2)) {

								echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.$row2['variable'].') '.strip_tags($row2['naslov']).'</option>'."\n\r";

								mysqli_data_seek($sql3, 0);
								while ($row3 = mysqli_fetch_array($sql3)) {

									if ($row2['id'] == $row['vre_id'] && $row3['id'] == $row['grd_id'])
										$selected = ' selected="selected"';
									else
										$selected = '';

									echo '<option value="grd_'.$row2['id'].'_'.$row3['id'].'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.$row3['id'].') '.strip_tags($row3['naslov']).'</option>'."\n\r";
								}
							}
						}
					}

                // vsi ostali (razen label)
                } else {
                    if ($row1['id'] == $row['spr_id'])
                        $selected = ' selected="selected"';
                    else
                        $selected = '';

                    echo '<option value="'.$row1['id'].'"'.$selected.'>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.strip_tags($row1['naslov']).'</option>'."\n\r";
                }
            }
        }

        echo '</select><br />&nbsp;'."\n\r";
        echo '</td>';


        // span, ki se updata ob spremembi spremenljivke
        echo '<td class="tbl_ce_tb white">';

        echo '<span id="fill_vrednost_'.$condition.'">'."\n\r";

        if ($edit_fill_value == $condition)
        	$edit_value = true;
        else
        	$edit_value = false;

        // to je zato, da pri kalkulaciji prenesemo naprej nastavitev, a smo v navadnih ifih, profilih ifov ipd, zaradi tistih gumbov spodaj
        if ($vrednost < 0 && $row['vre_id'] <= 0 || ($row['spr_id'] == -2 || $row['spr_id'] == -3))
        	$row['vre_id'] = $vrednost;

        $this->fill_value($condition, $row['spr_id'], $row['vre_id'], $row['grd_id'], $edit_value);

        echo '</span>'."\n\r";
        echo '</td>';

        // display right bracket
		echo '<td class="tbl_ce_tb white" style="width:50px; text-align:center" nowrap>';
		if ($row_count['count'] != 1 || $row['right_bracket']>0 || $row['left_bracket']>0) {
			for ($i=1; $i<=$row['right_bracket']; $i++) echo ' ) ';
		}
		echo '</td>';

        // right_bracket buttons
		if ($row_count['count'] != 1 || $row['right_bracket']>0 || $row['left_bracket']>0) {
            echo '<td class="tbl_ce_tb white" style="width:50px; text-align:center" nowrap>';
			
			if ($row['right_bracket'] > 0)
				echo '<a href="#" onclick="javascript:bracket_edit_new(\''.$condition.'\', \''.$vrednost.'\', \'right\', \'minus\'); return false;" title="'.$lang['srv_zaklepaj_rem'].'"><span class="faicon delete_circle"></span></a>';
			else
				echo '<span class="faicon delete_circle icon-grey_normal"></span>';

			echo '<a href="#" onclick="javascript:bracket_edit_new(\''.$condition.'\', \''.$vrednost.'\', \'right\', \'plus\' ); return false;" title="'.$lang['srv_zaklepaj_add'].'"><span class="faicon add small"></span></a>';
		} 
        else {
            echo '<td class="tbl_ce_tb white" style="width:50px; text-align:center" nowrap>';
		}
		echo '</td>';


		// move
        echo '<td class="tbl_ce_tb white" style="text-align:right; width:30px">';
        if ($row_count['count'] != 1 )
        	echo '<img src="img_0/move_updown.png" class="move" title="'.$lang['srv_move'].'" />';
        echo '</td>';

        // remove
        echo '<td class="tbl_ce_lor white" style="text-align:left; width:30px">';
        if ($row_count['count'] != 1 )
            echo '<a href="#" onclick="condition_remove(\''.$row['if_id'].'\', \''.$condition.'\', \''.$vrednost.'\'); return false;" title="'.$lang['srv_if_rem'].'"><span class="faicon delete icon-grey_dark_link"></span></a>'."\n";
        echo '</td>';


        echo '</tr>';
        echo '</table>';

        echo '</form>'."\n\r";
    }

    /**
    * @desc napolni select z vrednostmi izbrane spremenljivke -- $vrednost je pa za multigrid
    */
    function fill_value($condition, $spremenljivka, $vrednost, $grid=0, $edit_value = false) {
        global $lang;

        $sql = sisplet_query("SELECT * FROM srv_condition WHERE id='$condition'");
        $row = mysqli_fetch_array($sql);

        $row1 = Cache::srv_spremenljivka($spremenljivka);

        // navadne spremenljivke (vkljucno z multigrid)
        if ($spremenljivka > 0) {

            // zato, da z JS vemo kaj poslat po AJAXu, ID vrednosti ali text
            echo '<input type="hidden" name="tip_'.$condition.'" id="tip_'.$condition.'" value="'.$row1['tip'].'" >';

            // operator
            echo '<select name="operator_'.$condition.'" id="operator_'.$condition.'" onchange="javascript:condition_edit(\''.$condition.'\');" style="width:45px">'."\n\r";
            echo '  <option value="0"'.($row['operator']==0?' selected="selected"':'').'>=</option>'."\n\r";
			echo '  <option value="1"'.($row['operator']==1?' selected="selected"':'').'>&ne;</option>'."\n\r";
  //          echo '  <option value="1"'.($row['operator']==1?' selected':'').'>≠</option>'."\n\r";

            if ($row1['tip'] == 7 || $row1['tip'] == 8 || $row1['tip'] == 22 || $row1['tip'] == 25 || $row1['tip'] == 20 || $row1['tip'] == 18) {
            // number in compute in tabela number ma dodatne operatorje
                echo '  <option value="2"'.($row['operator']==2?' selected="selected"':'').'><</option>'."\n\r";
                echo '  <option value="3"'.($row['operator']==3?' selected="selected"':'').'><=</option>'."\n\r";
                echo '  <option value="4"'.($row['operator']==4?' selected="selected"':'').'>></option>'."\n\r";
                echo '  <option value="5"'.($row['operator']==5?' selected="selected"':'').'>>=</option>'."\n\r";
            }

            if ($row1['tip'] == 21) {
				echo '  <option value="6"'.($row['operator']==6?' selected':'').'>length =</option>'."\n\r";
				echo '  <option value="7"'.($row['operator']==7?' selected':'').'>length <</option>'."\n\r";
				echo '  <option value="8"'.($row['operator']==8?' selected':'').'>length ></option>'."\n\r";
            }

            echo '</select> '."\n\r";

            echo '</td><td class="tbl_ce_tb white" style="width:35%">';

            // number in textbox, vsota imajo textovni input (ter compute in kvota)
            if ($row1['tip'] == 4 || $row1['tip'] == 21 || $row1['tip'] == 7 || $row1['tip'] == 22 || $row1['tip'] == 25 || $row1['tip'] == 18)
            {
                echo '<input type="text" name="text_'.$condition.'" id="text_'.$condition.'" value="'.$row['text'].'" style="width:140px" >';

				echo '<script type="text/javascript">'; // shranimo ko zapustmo input polje
				echo '$(document).ready(function() {' .
				'  $("input#text_'.$condition.'").bind("click", {}, function(e) { cond_focus_field = this; });' .
				'  $("input#text_'.$condition.'").bind("keyup", {}, function(e) {' .
				'    condition_edit(\''.$condition.'\'); return false;  ' .
				'  });' .
				'});';
				echo '</script>';

			// 	select input za datum
            } else if ( $row1['tip'] == 8){

            	echo '<input type="text" name="text_'.$condition.'" id="text_'.$condition.'" value="'.$row['text'].'" style="width:140px" >';

            	echo '<script type="text/javascript">'; // shranimo ko zapustmo input polje
            	echo '$(document).ready(function() {' .
            			'  $( "#text_'.$condition.'" ).datepicker({
							showOtherMonths: true,
							selectOtherMonths: true,
							changeMonth: true,
							changeYear: true,
							dateFormat: "dd.mm.yy",
							showAnim: "slideDown",
							showOn: "button",
							buttonText: "",
							onSelect: function(selected,evnt) {
								condition_edit(\''.$condition.'\'); return false;
            				}
            				});' .


							'  $("input#text_'.$condition.'").bind("keyup", {}, function(e) {' .
							'    condition_edit(\''.$condition.'\'); return false;  ' .
							'  });' .
            			'});';
            	echo '</script>';

            // select input za spremenljivke
            } else {
                // obicajna spremenljivka
                if ($vrednost <= 0) {

                	echo '<span id="edit_fill_value_'.$condition.'"'.($edit_value?'':' style="display: none"').'>';

                	$sql1 = sisplet_query("SELECT COUNT(*) AS count FROM srv_condition_vre WHERE cond_id='$condition'");
                	$row1 = mysqli_fetch_array($sql1);
                	if ($row1['count'] == 0) echo '<span class="red">';
                	echo $lang['srv_note_vrednost'].':<br />';
                	if ($row1['count'] == 0) echo '</span>';

                    $sql = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE spr_id='$spremenljivka' ORDER BY vrstni_red ASC");
                    while ($row = mysqli_fetch_array($sql)) {

                        $sql1 = sisplet_query("SELECT * FROM srv_condition_vre WHERE cond_id='$condition' AND vre_id='$row[id]'");
                        if (mysqli_num_rows($sql1) > 0)
                        	$selected = ' checked="checked"';
						else
                            $selected = '';

                        echo '<label for="vrednost_'.$condition.'_'.$row['id'].'" style="height:1em; overflow:hidden;" nowrap><input type="checkbox" name="vrednost_'.$condition.'" id="vrednost_'.$condition.'_'.$row['id'].'" value="'.$row['id'].'" class="enka-admin-custom" '.$selected.' onclick="condition_edit(\''.$condition.'\');" /><span class="enka-checkbox-radio"></span> ('.$row['variable'].') '.strip_tags($row['naslov']).'</label><br />'."\n\r";
						if ($selected != '')
                        	$preview .= /*($preview!=''?', ':'').*/'('.$row['variable'].') '.strip_tags($row['naslov']).'<br>';
                    }


					// Ce je bilo vprasanje na prejsnji strani imamo tudi pogoj -1 (neodgovor)
					$sqlC = sisplet_query("SELECT if_id FROM srv_condition WHERE id='$condition'");
		            $rowC = mysqli_fetch_array($sqlC);
					$current_grupa = $this->getGrupa4If($rowC['if_id']);
					$selected_grupa = $this->getGrupa4Spremenljivka($spremenljivka);
					if($selected_grupa['vrstni_red'] != $current_grupa['vrstni_red']){

						$sql1 = sisplet_query("SELECT * FROM srv_condition_vre WHERE cond_id='$condition' AND vre_id='-1'");
						if (mysqli_num_rows($sql1) > 0)
							$selected = ' checked="checked"';
						else
							$selected = '';
						echo '<input type="checkbox" class="enka-admin-custom" name="vrednost_'.$condition.'" id="vrednost_'.$condition.'_-1" value="-1"'.$selected.' onclick="condition_edit(\''.$condition.'\');" /><span class="enka-checkbox-radio"></span><label for="vrednost_'.$condition.'_-1" style="height:1em; overflow:hidden;" nowrap> (-1) '.$lang['srv_mv_Ni odgovoril'].'</label><br />'."\n\r";
						if ($selected != '')
							$preview .= '(-1) '.$lang['srv_mv_Ni odgovoril'].'<br>';
					}

                    echo '</span>';
					echo '<a href="#" onclick="edit_fill_value(\''.$condition.'\'); return false;" id="preview_fill_value_'.$condition.'"'.($edit_value?' style="display: none"':'').' title="'.$lang['srv_note_vrednost'].'!">'.($preview!=''?$preview:$lang['srv_error_vrednost']).'</a>';


                // multigrid (tuki mamo poleg spremenljivke vrednost, ki oznacuje element)
				} elseif ($vrednost > 0) {

					// tabela radio, tabela checkbox
					if ($grid == 0) {

	                	echo '<span id="edit_fill_value_'.$condition.'"'.($edit_value?'':' style="display: none"').'>';

	                	$sql1c = sisplet_query("SELECT COUNT(*) AS count FROM srv_condition_grid WHERE cond_id='$condition'");
		                $row1c = mysqli_fetch_array($sql1c);
                		if ($row1c['count'] == 0) echo '<span class="red">';
                		echo $lang['srv_note_vrednost'].':<br />';
                		if ($row1c['count'] == 0) echo '</span>';

	                	if ($row1['tip'] == 6 || $row1['tip'] == 16) {

		                    $sql = sisplet_query("SELECT s.id FROM srv_vrednost v, srv_spremenljivka s WHERE v.id='$vrednost' AND v.spr_id=s.id");
		                    $row = mysqli_fetch_array($sql);

		                    $sql3 = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='$row[id]' ORDER BY vrstni_red");
		                    while ($row3 = mysqli_fetch_array($sql3)) {
		                        $i = $row3['id'];

		                        $sql1 = sisplet_query("SELECT * FROM srv_condition_grid WHERE cond_id='$condition' AND grd_id='$i'");
		                        if (mysqli_num_rows($sql1) > 0)
                        			$selected = ' checked="checked"';
		                        else
		                            $selected = '';

		                        $sql2 = sisplet_query("SELECT naslov, variable FROM srv_grid WHERE id='$i' AND spr_id='$row[id]'");
		                        $row2 = mysqli_fetch_array($sql2);

		                        echo '<label for="vrednost_'.$condition.'_'.$i.'" style="height:1em; overflow:hidden;" nowrap><input type="checkbox" class="enka-admin-custom" name="vrednost_'.$condition.'" id="vrednost_'.$condition.'_'.$i.'" value="'.$i.'"'.$selected.' onclick="condition_edit(\''.$condition.'\');" /><span class="enka-checkbox-radio"></span> ('.$row2['variable'].') '.strip_tags($row2['naslov']).'</label><br />'."\n\r";
								if ($selected != '')
									$preview .= /*($preview!=''?', ':'').*/'('.$row2['variable'].') '.strip_tags($row2['naslov']).'<br>';
		                    }

							// Ce je bilo vprasanje na prejsnji strani imamo tudi pogoj -1 (neodgovor)
							$sqlC = sisplet_query("SELECT if_id FROM srv_condition WHERE id='$condition'");
							$rowC = mysqli_fetch_array($sqlC);
							$current_grupa = $this->getGrupa4If($rowC['if_id']);
							$selected_grupa = $this->getGrupa4Spremenljivka($spremenljivka);
							if($selected_grupa['vrstni_red'] != $current_grupa['vrstni_red']){
								$sql1 = sisplet_query("SELECT * FROM srv_condition_grid WHERE cond_id='$condition' AND grd_id='-1'");
								if (mysqli_num_rows($sql1) > 0)
									$selected = ' checked="checked"';
								else
									$selected = '';
								echo '<input type="checkbox" class="enka-admin-custom" name="vrednost_'.$condition.'" id="vrednost_'.$condition.'_-1" value="-1"'.$selected.' onclick="condition_edit(\''.$condition.'\');" /><span class="enka-checkbox-radio"></span><label for="vrednost_'.$condition.'_-1" style="height:1em; overflow:hidden;" nowrap> (-1) '.$lang['srv_mv_Ni odgovoril'].'</label><br />'."\n\r";
								if ($selected != '')
									$preview .= '(-1) '.$lang['srv_mv_Ni odgovoril'].'<br>';
							}

		                // ranking
						} elseif ($row1['tip'] == 17) {

							$sql = sisplet_query("SELECT s.id FROM srv_vrednost v, srv_spremenljivka s WHERE v.id='$vrednost' AND v.spr_id=s.id");
		                    $row = mysqli_fetch_array($sql);

							$sql3 = sisplet_query("SELECT COUNT(*) AS count FROM srv_vrednost WHERE spr_id='$row[id]'");
		                    $row3 = mysqli_fetch_array($sql3);
		                    for ($i=1; $i<=$row3['count']; $i++) {

		                        $sql1 = sisplet_query("SELECT * FROM srv_condition_grid WHERE cond_id='$condition' AND grd_id='$i'");
		                        if (mysqli_num_rows($sql1) > 0)
                        			$selected = ' checked="checked"';
		                        else
		                            $selected = '';

		                        echo '<input type="checkbox" class="enka-admin-custom" name="vrednost_'.$condition.'" id="vrednost_'.$condition.'_'.$i.'" value="'.$i.'"'.$selected.' onclick="condition_edit(\''.$condition.'\');" /><span class="enka-checkbox-radio"></span><label for="vrednost_'.$condition.'_'.$i.'" style="height:1em; overflow:hidden;" nowrap> ('.$i.') '.$i.'. '.$lang['srv_position'].'</label><br />'."\n\r";
								if ($selected != '')
									$preview .= /*($preview!=''?', ':'').*/'('.$i.') '.$i.'. '.$lang['srv_position'].'<br>';

		                    }

						}

	                	echo '</span>';
						echo '<a href="#" onclick="edit_fill_value(\''.$condition.'\'); return false;" id="preview_fill_value_'.$condition.'"'.($edit_value?' style="display: none"':'').' title="'.$lang['srv_note_vrednost'].'!">'.($preview!=''?$preview:$lang['srv_error_vrednost']).'</a>';


	                // tabela text, tabela stevilo
					} else {

						echo '<input type="text" name="text_'.$condition.'" id="text_'.$condition.'" value="'.$row['text'].'" style="width:140px" >';

						echo '<script type="text/javascript">'; // shranimo ko zapustmo input polje
						echo '$(document).ready(function() {' .
						'  $("input#text_'.$condition.'").bind("click", {}, function(e) { cond_focus_field = this; });' .
						'  $("input#text_'.$condition.'").bind("keyup", {}, function(e) {' .
						'    condition_edit(\''.$condition.'\'); return false;  ' .
						'  });' .
						'});';
						echo '</script>';
					}
                }
            }

        // mod recnum
        } elseif ($spremenljivka == -1) {

            // zato, da z JS vemo kaj poslat po AJAXu, ID vrednosti ali text
            echo '<input type="hidden" name="tip_'.$condition.'" id="tip_'.$condition.'" value="-1" />'."\n\r";

            echo $lang['srv_groups'].': <select name="modul_'.$condition.'" id="modul_'.$condition.'" style="width:40px" onchange="javascript:fill_ostanek(\''.$condition.'\');">'."\n\r";

            for ($i=2; $i<=64; $i++)
                echo '<option value="'.$i.'"'.($row['modul']==$i?' selected="selected"':'').'>'.$i.'</option>';

            echo '</select>'."\n\r";

            echo '<span id="'.$condition.'_ostanek">';
            $this->fill_ostanek($condition);
            echo '</span>'."\n\r";

        // calculation
        } elseif ($spremenljivka == -2) {

            // zato, da z JS vemo kaj poslat po AJAXu, ID vrednosti ali text
            echo '<input type="hidden" name="tip_'.$condition.'" id="tip_'.$condition.'" value="-2" />'."\n\r";

            $text = $this->calculations_display($condition);
            if ($text == '<span class="calculations_display"></span>') $text = $lang['srv_editcalculation'];

            echo ' <a href="#" onclick="calculation_editing(\''.$condition.'\', \'0\', \''.$vrednost.'\'); $(\'#calculation\').css({\'position\': \'absolute\'}); return false;">'.$text.'</a> ';

            // operator
            echo '<select name="operator_'.$condition.'" id="operator_'.$condition.'" onchange="javascript:condition_edit(\''.$condition.'\');" style="width:45px">'."\n\r";
            echo '  <option value="0"'.($row['operator']==0?' selected':'').'>=</option>'."\n\r";
            echo '  <option value="1"'.($row['operator']==1?' selected':'').'>&ne;</option>'."\n\r";
//            echo '  <option value="1"'.($row['operator']==1?' selected':'').'>≠</option>'."\n\r";
            echo '  <option value="2"'.($row['operator']==2?' selected':'').'><</option>'."\n\r";
            echo '  <option value="3"'.($row['operator']==3?' selected':'').'><=</option>'."\n\r";
            echo '  <option value="4"'.($row['operator']==4?' selected':'').'>></option>'."\n\r";
            echo '  <option value="5"'.($row['operator']==5?' selected':'').'>>=</option>'."\n\r";
            echo '</select> '."\n\r";

            echo '<input type="text" name="text_'.$condition.'" id="text_'.$condition.'" value="'.$row['text'].'" style="width:40px;" />';

			echo '<script type="text/javascript">'; // shranimo ko zapustmo input polje
			echo '$(document).ready(function() {' .
			'  $("input#text_'.$condition.'").bind("keyup", {}, function(e) {' .
			'    condition_edit(\''.$condition.'\'); return false;  ' .
			'  });' .
			'});';
			echo '</script>';
			
		// kvote
        } elseif ($spremenljivka == -3) {

            // zato, da z JS vemo kaj poslat po AJAXu, ID vrednosti ali text
            echo '<input type="hidden" name="tip_'.$condition.'" id="tip_'.$condition.'" value="-3" />'."\n\r";

			$SQ = new SurveyQuotas($this->anketa);
            $text = $SQ->quota_display($condition);
            if ($text == '<span class="quota_display"></span>') $text = $lang['srv_edit_quota'];

            echo ' <a href="#" onclick="quota_editing(\''.$condition.'\', \'0\', \''.$vrednost.'\');  $(\'#quota\').css({\'position\': \'absolute\'}); return false;">'.$text.'</a> ';

            // operator
            echo '<select name="operator_'.$condition.'" id="operator_'.$condition.'" onchange="javascript:condition_edit(\''.$condition.'\');" style="width:45px">'."\n\r";
            echo '  <option value="0"'.($row['operator']==0?' selected':'').'>=</option>'."\n\r";
            echo '  <option value="1"'.($row['operator']==1?' selected':'').'>&ne;</option>'."\n\r";
//            echo '  <option value="1"'.($row['operator']==1?' selected':'').'>≠</option>'."\n\r";
            echo '  <option value="2"'.($row['operator']==2?' selected':'').'><</option>'."\n\r";
            echo '  <option value="3"'.($row['operator']==3?' selected':'').'><=</option>'."\n\r";
            echo '  <option value="4"'.($row['operator']==4?' selected':'').'>></option>'."\n\r";
            echo '  <option value="5"'.($row['operator']==5?' selected':'').'>>=</option>'."\n\r";
            echo '</select> '."\n\r";

            echo '<input type="text" name="text_'.$condition.'" id="text_'.$condition.'" value="'.$row['text'].'" style="width:40px;" />';

			echo '<script type="text/javascript">'; // shranimo ko zapustmo input polje
			echo '$(document).ready(function() {' .
			'  $("input#text_'.$condition.'").bind("keyup", {}, function(e) {' .
			'    condition_edit(\''.$condition.'\'); return false;  ' .
			'  });' .
			'});';
			echo '</script>';
			
		// Naprava	
        } elseif ($spremenljivka == -4) {

            // zato, da z JS vemo kaj poslat po AJAXu, ID vrednosti ali text
            echo '<input type="hidden" name="tip_'.$condition.'" id="tip_'.$condition.'" value="-4" />'."\n\r";

            echo $lang['srv_device_type'].': <select name="text_'.$condition.'" id="text_'.$condition.'" style="width:100px" onchange="javascript:condition_edit(\''.$condition.'\');">'."\n\r";
				echo '<option value="-1"'.($row['text']=='' || $row['text']=='-1'?' selected="selected"':'').'>'.$lang['srv_device_type_select'].'</option>';
				echo '<option value="0"'.($row['text']=='0'?' selected="selected"':'').'>'.$lang['srv_para_graph_device0'].'</option>';
				echo '<option value="1"'.($row['text']=='1'?' selected="selected"':'').'>'.$lang['srv_para_graph_device1'].'</option>';
				echo '<option value="2"'.($row['text']=='2'?' selected="selected"':'').'>'.$lang['srv_para_graph_device2'].'</option>';
				echo '<option value="3"'.($row['text']=='3'?' selected="selected"':'').'>'.$lang['srv_para_graph_device3'].'</option>';
            echo '</select>'."\n\r";
        }
    }

    /**
    * @desc izpise dropdown za ostanek
    */
    function fill_ostanek ($condition) {
        global $lang;

        $sql = sisplet_query("SELECT * FROM srv_condition WHERE id='$condition'");
        $row = mysqli_fetch_array($sql);

        echo $lang['srv_group'].': <select name="ostanek_'.$condition.'" id="ostanek_'.$condition.'" style="width:40px" onchange="javascript:condition_edit(\''.$condition.'\');">'."\n\r";

        for ($i=0; $i<$row['modul']; $i++)
            echo '<option value="'.$i.'"'.($row['ostanek']==$i?' selected="selected"':'').'>'.($i+1).'</option>';

        echo '</select>'."\n\r";

    }

    /**
    * prikaze urejanje loopa
    *
    * @param int $if
    */
    function loop_edit ($if) {
		global $lang;

		$advanced = false;

		$sqll = sisplet_query("SELECT * FROM srv_loop_vre WHERE if_id='$if' AND tip!='0'");
		if (mysqli_num_rows($sqll) > 0)
			$advanced = true;
		
		if ($_POST['advanced'] == 1)
			$advanced = true;

		$sql = sisplet_query("SELECT * FROM srv_loop WHERE if_id='$if'");
		$row = mysqli_fetch_array($sql);
		$spremenljivka = $row['spr_id'];
		$max = $row['max'];

		$spr = Cache::srv_spremenljivka($spremenljivka);
		$tip = $spr['tip'];

		$vrstni_red = $this->vrstni_red($this->find_before_if($if));
		$prev_grupa = 0;

		
		echo '<table style="width:100%"><tr>';

		echo '<td style="width:40%; text-align:center">';

		echo $lang['srv_loop_for'].' <select name="spremenljivka_'.$if.'" id="spremenljivka_'.$if.'" size="1" style="width:150px" onchange="javascript:fill_value_loop(\''.$if.'\');">';
		echo '<option value="0"></option>';

		$sql1 = sisplet_query("SELECT s.id, s.naslov, s.variable, s.gru_id, g.naslov AS grupa_naslov
	                            FROM srv_spremenljivka s, srv_grupa g
	                            WHERE g.ank_id='$this->anketa' AND s.gru_id=g.id AND s.tip IN (2,7,9,17)
	                            ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");
		while ($row1 = mysqli_fetch_array($sql1)) {

            if ($this->vrstni_red($row1['id']) <= $vrstni_red) {

            	if ($row1['gru_id'] != $prev_grupa) {
					echo '<option value="0" disabled style="font-style: italic;">'.$row1['grupa_naslov'].'</option>';
					$prev_grupa = $row1['gru_id'];
            	}

                if ($row1['id'] == $spremenljivka)
                    $selected = ' selected="selected"';
                else
                    $selected = '';

                echo '<option value="'.$row1['id'].'"'.$selected.'>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.$row1['naslov'].'</option>'."\n\r";
			}
		}
		echo '</select> ';

		if ($spremenljivka > 0 && ($tip==2 || $tip==3))
			echo $lang['srv_loop_for_when'].(!$advanced?' '.$lang['srv_loop_when_checked']:'');

		echo '</td><td>';

		if ($spremenljivka > 0 && ($tip==2 ||$tip==3)) {	// spremenljivka je izbrana in ne gre za sn-imena

			if ($advanced) {
				echo '<table style="width:100%"><tr><td style="width:auto"></td><th style="width:25%">'.$lang['srv_loop_checked'].'</th><th style="width:25%">'.$lang['srv_loop_notchecked'].'</th><th style="width:25%">'.$lang['srv_loop_always'].'</th><th style="width:25%">'.$lang['srv_loop_never'].'</th></tr>';
			}

			$sql2 = sisplet_query("SELECT id, naslov, variable, vrstni_red FROM srv_vrednost WHERE spr_id='$spremenljivka' ORDER BY vrstni_red ASC");
			while ($row2 = mysqli_fetch_array($sql2)) {

				$sql3 = sisplet_query("SELECT * FROM srv_loop_vre WHERE if_id='$if' AND vre_id='$row2[id]'");
				$row3 = mysqli_fetch_array($sql3);

				if ($advanced) {

					if (mysqli_num_rows($sql3) == 0) $row3['tip'] = 3;

					echo '<tr id="vrednost_'.$if.'">';
					echo '<td nowrap>('.$row2['variable'].') '.strip_tags($row2['naslov']).'</td>';
					echo '<td style="text-align:center"><label for="'.$row2['vrstni_red'].'-0"><input type="radio" class="enka-admin-custom" name="vrednost_'.$if.'_'.$row2['id'].'" id="'.$row2['vrstni_red'].'-0" value="0" '.($row3['tip']==0?'checked':'').' onclick="loop_edit_advanced(\''.$if.'\');" /><span class="enka-checkbox-radio"></span></label></td>';
					echo '<td style="text-align:center"><label for="'.$row2['vrstni_red'].'-1"><input type="radio" class="enka-admin-custom" name="vrednost_'.$if.'_'.$row2['id'].'" id="'.$row2['vrstni_red'].'-1" value="1" '.($row3['tip']==1?'checked':'').' onclick="loop_edit_advanced(\''.$if.'\');" /><span class="enka-checkbox-radio"></span></label></td>';
					echo '<td style="text-align:center"><label for="'.$row2['vrstni_red'].'-2"><input type="radio" class="enka-admin-custom" name="vrednost_'.$if.'_'.$row2['id'].'" id="'.$row2['vrstni_red'].'-2" value="2" '.($row3['tip']==2?'checked':'').' onclick="loop_edit_advanced(\''.$if.'\');" /><span class="enka-checkbox-radio"></span></label></td>';
					echo '<td style="text-align:center"><label for="'.$row2['vrstni_red'].'-3"><input type="radio" class="enka-admin-custom" name="vrednost_'.$if.'_'.$row2['id'].'" id="'.$row2['vrstni_red'].'-3" value="3" '.($row3['tip']==3?'checked':'').' onclick="loop_edit_advanced(\''.$if.'\');" /><span class="enka-checkbox-radio"></span></label></td>';
					echo '</tr>';

				} else {
					if (mysqli_num_rows($sql3) > 0)	$selected = ' checked'; else $selected = '';

					echo '<input type="checkbox" class="enka-admin-custom" name="vrednost_'.$if.'" id="vrednost_'.$if.'_'.$row2['id'].'" value="'.$row2['id'].'" '.$selected.' onclick="loop_edit(\''.$if.'\');" />';
                    echo '<span class="enka-checkbox-radio"></span>';
					echo'<label for="vrednost_'.$if.'_'.$row2['id'].'"> ('.$row2['variable'].') '.strip_tags($row2['naslov']).'</label><br />';
				}
			}

			if ($advanced)
				echo '</table>';

		} elseif ($tip == 7) {	// number nima dodatnih nastavitev
		} elseif ($tip == 8) {	// datum nima dodatnih nastavitev
		} elseif ($tip == 9) {	//SN-imena - uporabimo vedno vseh 20 vrednosti
		}

		echo '</td>';

		if (!$advanced && $spremenljivka>0  && ($tip==2 || $tip==3)) {
			echo '<td><a href="#" onclick="$(\'#branching_if'.$if.'\').load(
			\'ajax.php?t=branching&a=condition_editing\', {
				\'if\' : '.$if.',
				anketa : '.$this->anketa.',
				advanced: 1
			}); return false;">'.$lang['srv_advanced_options'].'</a></td>';
		}

		echo '</tr></table>';

		if ($spremenljivka > 0) {
			$sql2 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$spremenljivka'");
			$count = mysqli_num_rows($sql2);

			if ($tip == 7) {
				$count = 20;
				if ($max == 0) $max = 20;
			}

			echo '<p style="margin-left:10px; margin-top:40px">'.$lang['srv_loop_max'].': <select name="max" onchange="loop_edit_max('.$if.', this.value);">';
			if ($tip != 7)
				echo '<option value="0"'.(0==$max?' selected':'').'>'.$lang['srv_all'].'</option>';
			for ($i=1; $i<=$count; $i++)
				echo '<option value="'.$i.'"'.($i==$max?' selected':'').'>'.$i.'</option>';
			echo '</select></p>';
		}
    }

    function calculations_display($condition, $long_alert=0) {
        global $lang;

        $echo = '';
        $echo .= '<span class="calculations_display">';

        if ($condition < 0) {
	        $rowC = Cache::srv_spremenljivka($condition < 0 ? -$condition : $condition);
	        $echo .= $rowC['variable'].' = ';
		}

        $sql = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id = '$condition' ORDER BY vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        $bracket = 0;
        $i = 0;
        while ($row = mysqli_fetch_array($sql)) {


            if ($i++ != 0)
                if ($row['operator'] == 0)
                    $echo .= ' <span class="conjunction">+</span> ';
                elseif ($row['operator'] == 1)
                    $echo .= ' <span class="conjunction">-</span> ';
                elseif ($row['operator'] == 2)
                    $echo .= ' <span class="conjunction">*</span> ';
                elseif ($row['operator'] == 3)
                    $echo .= ' <span class="conjunction">/</span> ';

            for ($i=1; $i<=$row['left_bracket']; $i++)
                if ($long_alert == 1)
                    $echo .= ' <span class="bracket'.(($bracket++)%12).'">(</span> ';
                else
                    $echo .= ' ( ';

            // spremenljivke
            if ($row['spr_id'] > 0) {

                // obicne spremenljivke
                if ($row['vre_id'] == 0) {
                    $row1 = Cache::srv_spremenljivka($row['spr_id']);
                    if ($row1['tip'] != 7) {
                    	$variable = $row1['variable'];
					// number
					} else {
						$variable = $row1['variable'].'['.($row['grd_id']+1).']';
					}
                // multigrid
                } elseif ($row['grd_id'] == 0) {
                    $sql1 = sisplet_query("SELECT variable FROM srv_vrednost WHERE id = '$row[vre_id]'");
                    if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
                    $row1 = mysqli_fetch_array($sql1);
					
                    $variable = $row1['variable'];
                // multichecckbox, multinumber
                } elseif ($row['grd_id'] > 0) {
					$sql1 = sisplet_query("SELECT variable FROM srv_vrednost WHERE id = '$row[vre_id]'");
                    if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
                    $row1 = mysqli_fetch_array($sql1);
					
                    $sql1g = sisplet_query("SELECT * FROM srv_grid WHERE id = '$row[grd_id]'");
                    if (!$sql1g) echo mysqli_error($GLOBALS['connect_db']);
                    $row1g = mysqli_fetch_array($sql1g);
					
                    $variable = $row1['variable'].'['.$row1g['variable'].']';
                }

                if ($long_alert) $echo .= '<strong>';
                $echo .= $variable;
				if ($long_alert) $echo .= '</strong>';

            // konstante
            } elseif ($row['spr_id'] == -1) {

                $echo .= $row['number'];

            // recnum
            } elseif ($row['spr_id'] == -2) {

                $echo .= 'Recnum';

            }

            for ($i=1; $i<=$row['right_bracket']; $i++)
                if ($long_alert == 1)
                    $echo .= ' <span class="bracket'.((--$bracket)%12).'">)</span> ';
                else
                    $echo .= ' ) ';

        }

        $echo .= '</span>';

        if ($long_alert) {
            $calculation_check = $this->calculation_check($condition);

            if ( $calculation_check != 0) {

                if ($calculation_check == 1)
                	$echo .= '<br /><span class="faicon warning icon-orange"></span> <span class="red">'.$lang['srv_error_spremenljivka'].'</span>';
                elseif ($calculation_check == 2)
                	$echo .= '<br /><span class="faicon warning icon-orange"></span> <span class="red">'.$lang['srv_error_spremenljivka'].'</span>';
                elseif ($calculation_check == 3)
                	$echo .= '<br /><span class="faicon warning icon-orange"></span> <span class="red">'.$lang['srv_error_oklepaji'].'</span>';

			}
        }

        return $echo;
    }

    /**
    * @desc izpise urejanje kalkulacije
    */
    function calculation_editing ($condition, $vrednost=0) {
        global $lang;

        echo '<div class="popup_close"><a href="#" onClick="calculation_editing_close(\''.$condition.'\', \''.$vrednost.'\'); return false;">✕</a></div>';

        echo '<div id="calculation_editing_inner">';
        $this->calculation_editing_inner($condition, $vrednost);
        echo '</div>';

        echo '<div id="bottom_space">';

		$row = Cache::srv_spremenljivka(-$condition);
        if ($condition < 0) {		
			echo '<p style="float:left; padding:0; margin:0; margin-left:20px">'.$lang['srv_variable'].': <input type="text" id="variable_'.(-$condition).'" value="'.$row['variable'].'" onkeyup="calculation_edit_variable(\''.-$condition.'\');" style="width:60px" /></p>';
        }
		
		// Nastavitev števila decimalk
		echo '<p style="clear:both;float:left; padding:0; margin:10px 0 0 20px;">'.$lang['srv_results_num_digits'].':';
		echo ' <select id="decimalna_'.(-$condition).'" onChange="calculation_edit_decimalna(\''.-$condition.'\');">';
		for($i=0; $i<=10; $i++){
			echo '	<option value="'.$i.'" '.($row['decimalna'] == $i ? ' selected="selected"' : '').'>'.$i.'</option>';
		}
		echo '</select>';
		echo '</p>';
		
		// Nastavitev kako se obravnava missing v kalkulaciji (kot 0 ali za celo kalkulacijo kot -88)
		$newParams = new enkaParameters($row['params']);
		$calcMissing = $newParams->get('calcMissing', '0');
		echo '<p style="float:left; padding:0; margin:10px 0 0 20px;">';
		echo '<input type="checkbox" class="enka-admin-custom" value="1" '.($calcMissing == 1 ? ' checked="checked"' : '').' id="calcMissing_'.(-$condition).'" onChange="calculation_edit_missing(\''.-$condition.'\');"> ';
        echo '<span class="enka-checkbox-radio"></span>';
		echo '<label for="calcMissing_'.(-$condition).'">'.$lang['srv_editcalculation_missing'].'</label> '.Help::display('srv_calculation_missing');
		echo '</p>';

        echo '<div id="condition_editing_close">';

        // kalkulacija kot spremenljivka (lahko jo zbrisemo)
        if ($condition < 0) {
	        echo '<span class="buttonwrapper spaceRight floatLeft">';
	        echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="brisi_spremenljivko(\''.(-$condition).'\'); return false;"><span>'.$lang['srv_anketadelete_txt'].'</span></a>';
	        echo '</span>';
		}

		echo '<span class="buttonwrapper spaceRight floatLeft">';
        echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="calculation_editing_close(\''.$condition.'\', \''.$vrednost.'\'); return false;"><span>'.$lang['srv_zapri'].'</span></a>';
        echo '</span>';

        echo '<span class="buttonwrapper floatLeft">';
        echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="calculation_editing_close(\''.$condition.'\', \''.$vrednost.'\'); return false;"><span>'.$lang['srv_potrdi'].'</span></a>';
        echo '</span>';

        echo '</div>';
        echo '</div>';

        echo '<div id="arrows_more_calculation" onclick=" $(\'#calculation_editing_inner\').animate({ scrollTop: $(\'#calculation_editing_inner\').attr(\'scrollHeight\') }, 2000); "><img src="img_0/bullet_arrow_down.png" /> '.$lang['srv_more'].'</div>';

        ?><script>
		$('#calculation_editing_inner').sortable({items: 'form', handle: 'img.move', stop: function () {
			calculation_sort(<?=$condition?>);
		} });

		$('#calculation_editing_inner').scroll(function() {
			if ( isScrolledIntoView('#calculation_editing_operators', '#calculation_editing_inner') )
				$('#arrows_more_calculation').fadeOut(1000);
			else
				$('#arrows_more_calculation').fadeIn(1000);
		});
		$('#calculation_editing_inner').resize(function() {
			$('#calculation_editing_inner').scroll();
		});
		$('#calculation_editing_inner').scroll();
		</script><?
    }

    function calculation_editing_inner ($condition, $vrednost=0) {
		global $lang;

        echo '<div class="calculation_editing_preview">';

        echo '<h2>'.$lang['srv_calc'].'</h2>';
        echo '<div id="calculation_editing_calculations">';
        echo $this->calculations_display($condition, 1);
        echo '</div>';
		echo '</div>';

        echo '<div class="calculation_editing_title">';
        echo '<h2>'.$lang['srv_editcalculation'].'</h2>';
        echo '</div>';
        

        echo '<div class="calculation_editing_body">';
        $sql = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id = '$condition' ORDER BY vrstni_red");
        if (mysqli_num_rows($sql) == 0) {
            sisplet_query("INSERT INTO srv_calculation (id, cnd_id, vrstni_red) VALUES ('', '$condition', '1')");
            $sql = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id = '$condition' ORDER BY vrstni_red");
        }
        while ($row = mysqli_fetch_array($sql)) {
            $this->calculation_edit($row['id'], $vrednost);
        }
        echo '</div>';


        echo '<p id="calculation_editing_operators" style="margin-left:62px; height:50px;">'.$lang['srv_add_cond'].':
                <a href="#" onclick="calculation_add(\''.$condition.'\', \'0\', \''.$vrednost.'\'); return false;"><strong style="font-size:18px">&nbsp;+&nbsp;</strong></a>,
                <a href="#" onclick="calculation_add(\''.$condition.'\', \'1\', \''.$vrednost.'\'); return false;"><strong style="font-size:18px">&nbsp;-&nbsp;</strong></a>,
                <a href="#" onclick="calculation_add(\''.$condition.'\', \'2\', \''.$vrednost.'\'); return false;"><strong style="font-size:18px">&nbsp;*&nbsp;</strong></a>,
                <a href="#" onclick="calculation_add(\''.$condition.'\', \'3\', \''.$vrednost.'\'); return false;"><strong style="font-size:18px">&nbsp;/&nbsp;</strong></a>
            </p><br><br>';
    }

    /**
    * @desc vrstica v urejanju kalkulacij
    */
    function calculation_edit ($calculation, $vrednost=0) {
        global $lang;

        $sql = sisplet_query("SELECT * FROM srv_calculation WHERE id = '$calculation'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        $sql1 = sisplet_query("SELECT * FROM srv_condition WHERE id = '{$row['cnd_id']}'");
        $row1 = mysqli_fetch_array($sql1);

        if ($row['cnd_id'] > 0) {	// kalkulacija znotraj pogoja
        	$vrstni_red = $this->vrstni_red($this->find_before_if($row1['if_id']));
		} else {		// kalkulacija kot spremenljivka
			$vrstni_red = $this->vrstni_red(-$row['cnd_id']) - 1;	// -1 je da ne prikaze se trenutne kalkulacije
		}

        $sql_count = sisplet_query("SELECT COUNT(*) AS count FROM srv_calculation WHERE cnd_id='$row[cnd_id]'");
        if (!$sql_count) die();
        $row_count = mysqli_fetch_array($sql_count);

        if ($row['vrstni_red'] == 1 && $row_count['count'] > 1) {
			echo '<table class="tbl_condition_editing">';
			echo '<tr>';
			echo '<th style="text-align:center; width:50px">'.$lang['srv_oklepaji'].'</th>'; // gumbi oklepaji
			echo '<th  style="width:70px">&nbsp;</th>'; // logicni operatorji
			echo '<th style="width:50px">&nbsp;</th>'; // oklepaji
			echo '<th >&nbsp;</th>';
			echo '<th style="text-align:center; width:50px;">'.$lang['srv_zaklepaji'].'</th>'; // gumbi zaklepaji
			echo '<th style="text-align:center; width:60px">'.$lang['edit2'].'</th>'; // move
			echo '</tr>';
        	echo '</table>';

        } elseif ($row['vrstni_red'] == 1) {
			echo '<table class="tbl_condition_editing"><tr><th>&nbsp;</th></tr></table>';
        }

        // form
        echo '<form name="calculation_'.$calculation.'" id="calculation_'.$calculation.'" action="" method="post" onsubmit="calculation_edit(\''.$calculation.'\'); return false;">'."\n\r";

        echo '<table class="tbl_condition_editing" style="margin-bottom:10px; padding-bottom:10px; background-color:white">';
        echo '<tr>';


        // left_bracket
		if ($row_count['count'] != 1 || $row['left_bracket']>0 || $row['right_bracket']>0) {
            
            echo '<td class="tbl_ce_lol white" style="width:50px; text-align:center;" >';
			echo '<a href="#" onclick="javascript:calculation_bracket_edit_new(\''.$calculation.'\', \''.$vrednost.'\', \'left\', \'plus\' ); return false;" title="'.$lang['srv_oklepaj_add'].'"><span class="faicon add small"></span></a>';
            
            if ($row['left_bracket'] > 0)
				echo '<a href="#" onclick="javascript:calculation_bracket_edit_new(\''.$calculation.'\', \''.$vrednost.'\', \'left\', \'minus\'); return false;" title="'.$lang['srv_oklepaj_rem'].'"><span class="faicon delete_circle"></span></a>';
			else
				echo '<span class="faicon delete_circle icon-grey_normal"></span>';
        } 
        else {
            echo '<td class="tbl_ce_lol white" style="width:50px; text-align:center;" >';
		}
		echo '</td>';

        // operator
        echo '<td class="tbl_ce_tb white" style="width:77px; text-align:center">';

        if ($row['vrstni_red'] == 1) {
            // nimamo nic..
        } else {

            if ($row['operator']==0)
				echo '<a href="#" onclick="calculation_operator_edit(\''.$calculation.'\', \'1\'); return false;" style="font-weight:bold; font-size:18px" title="'.$lang['srv_edit_condition_conjunction'].'">&nbsp;+&nbsp;</a>';
			if ($row['operator']==1)
				echo '<a href="#" onclick="calculation_operator_edit(\''.$calculation.'\', \'2\'); return false;" style="font-weight:bold; font-size:18px" title="'.$lang['srv_edit_condition_conjunction'].'">&nbsp;-&nbsp;</a>';
			if ($row['operator']==2)
				echo '<a href="#" onclick="calculation_operator_edit(\''.$calculation.'\', \'3\'); return false;" style="font-weight:bold; font-size:18px" title="'.$lang['srv_edit_condition_conjunction'].'">&nbsp;*&nbsp;</a>';
			if ($row['operator']==3)
				echo '<a href="#" onclick="calculation_operator_edit(\''.$calculation.'\', \'0\'); return false;" style="font-weight:bold; font-size:18px" title="'.$lang['srv_edit_condition_conjunction'].'">&nbsp;/&nbsp;</a>';
        }

        echo '</td>';


        // left_bracket
        echo '<td class="tbl_ce_tb white" style="width:40px; text-align:center">';
        for ($i=$row['left_bracket']; $i>0; $i--) {
            echo ' ( ';        }
        echo '</td>';

        // spremenljivka
        echo '<td class="tbl_ce_tb white" style="width:auto">';
        if ($row['spr_id']==0) echo '<span class="red">'.$lang['srv_select_spr'].'!</span>';
        echo '<br />';
        echo '<select name="calculation_spremenljivka_'.$calculation.'" id="calculation_spremenljivka_'.$calculation.'" size="1" style="width:'.($row['spr_id']==-1?'100':'150').'px" onchange="javascript:calculation_edit(\''.$calculation.'\', \''.$vrednost.'\');">'."\n\r";

        echo '<option value="0"></option>';
        echo '<option value="-1"'. ($row['spr_id']==-1 ?' selected="selected"':'').' style="color: blue">&nbsp;&nbsp;&nbsp; '.$lang['srv_number'].'</option>';
        echo '<option value="-2"'. ($row['spr_id']==-2 ?' selected="selected"':'').' style="color: blue">&nbsp;&nbsp;&nbsp; '.$lang['srv_recnum2'].'</option>';

        $sql1 = sisplet_query("SELECT s.id, s.gru_id, s.naslov, s.variable, s.tip, s.enota, s.size, g.naslov AS grupa_naslov
                            FROM srv_spremenljivka s, srv_grupa g
                            WHERE g.ank_id='$this->anketa' AND s.gru_id=g.id AND s.tip IN (1, 2, 3, 6, 7, 22, 16, 20, 17, 18)
                            ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");

        if(!$sql1) echo mysqli_error($GLOBALS['connect_db']);

        $prev_grupa = 0;

        while ($row1 = mysqli_fetch_array($sql1)) {

            if ($this->vrstni_red($row1['id']) <= $vrstni_red) {

            	if ($row1['gru_id'] != $prev_grupa) {
					echo '<option value="0" disabled style="font-style: italic;">'.$row1['grupa_naslov'].'</option>';
					$prev_grupa = $row1['gru_id'];
            	}


				// checkbox , multigrid, razvrscanje, vsota
                if ($row1['tip'] == 2 || $row1['tip'] == 6 || $row1['tip'] == 17 || $row1['tip'] == 18) {

                	echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.$row1['naslov'].'</option>';

                    $sql2 = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE spr_id='$row1[id]' ORDER BY vrstni_red ASC");
                    while ($row2 = mysqli_fetch_array($sql2)) {

                        if ($row2['id'] == $row['vre_id'] && $row['grd_id'] == 0)
                            $selected = ' selected="selected"';
                        else
                            $selected = '';

                        echo '<option value="vre_'.$row2['id'].'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.$row2['variable'].') '.$row2['naslov'].'</option>'."\n\r";

                    }

                    // multigrid dvojna tabela
                    if ($row1['tip'] == 6 && $row1['enota'] == 3) {
						$sql2 = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE spr_id='$row1[id]' ORDER BY vrstni_red ASC");
	                    while ($row2 = mysqli_fetch_array($sql2)) {

	                        if ($row2['id'] == $row['vre_id'] && $row['grd_id'] == 1)
	                            $selected = ' selected="selected"';
	                        else
	                            $selected = '';

	                        echo '<option value="vre_'.$row2['id'].'_1"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.$row2['variable'].') '.$row2['naslov'].'</option>'."\n\r";

	                    }
                    }

                // number
				} elseif ($row1['tip'] == 7) {

					echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.$row1['naslov'].'</option>'."\n\r";

					// number ima lahko dva polja
					for ($i=0; $i<$row1['size']; $i++) {

						if ($row1['id'] == $row['spr_id'] && $i == $row['grd_id'])
	                        $selected = ' selected="selected"';
	                    else
	                        $selected = '';

	                    echo '<option value="num_'.$row1['id'].'_'.$i.'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.($i+1).') '.($i+1).'. '.$lang['srv_field'].'</option>'."\n\r";
					}

				// multichecbox, multinumber
				} elseif ($row1['tip'] == 16 || $row1['tip'] == 20) {

					echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.$row1['naslov'].'</option>';

                    $sql2 = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE spr_id='$row1[id]' ORDER BY vrstni_red ASC");
                    while ($row2 = mysqli_fetch_array($sql2)) {

                        echo '<option value="0" disabled>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; - ('.$row2['variable'].') '.$row2['naslov'].'</option>'."\n\r";

                        $sql3 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row1[id]' ORDER BY vrstni_red ASC");
                        while ($row3 = mysqli_fetch_array($sql3)) {

							if ($row1['id']==$row['spr_id'] && $row2['id']==$row['vre_id'] && $row3['id']==$row['grd_id'])
		                        $selected = ' selected="selected"';
		                    else
		                        $selected = '';

		                    echo '<option value="mlti_'.$row1['id'].'_'.$row2['id'].'_'.$row3['id'].'"'.$selected.'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ('.$row3['variable'].') '.$row3['naslov'].'</option>'."\n\r";


                        }

                    }

                // vsi ostali (numericni)
                } else {
                    if ($row1['id'] == $row['spr_id'])
                        $selected = ' selected="selected"';
                    else
                        $selected = '';

                    echo '<option value="'.$row1['id'].'"'.$selected.'>&nbsp;&nbsp;&nbsp; ('.$row1['variable'].') '.$row1['naslov'].'</option>'."\n\r";
                }
            }
        }

        echo '</select>';

        // number vnos
        if ($row['spr_id'] == -1) {
            //if ($row['number'] == 0) $row['number'] = '';
            echo ' <input type="text" name="number" id="calculation_number_'.$calculation.'" value="'.$row['number'].'" style="width:40px" >';

            echo '<script type="text/javascript">'; // shranimo ko zapustmo input polje
				echo '$(document).ready(function() {' .
				'  $("input#calculation_number_'.$calculation.'").bind("blur", {}, function(e) {' .
				'    calculation_edit(\''.$calculation.'\', \''.$vrednost.'\'); return false;  ' .
				'  });' .
				'});';
			echo '</script>';

        }

        echo '<br />&nbsp;'."\n\r";
        echo '</td>';


        // right_bracket
        echo '<td class="tbl_ce_tb white" style="width:40px; text-align:center">';
        for ($i=$row['right_bracket']; $i>0; $i--) {
            echo ' ) ';
        }
        echo '</td>';


        // right_bracket buttons
		if ($row_count['count'] != 1 || $row['right_bracket']>0 || $row['left_bracket']>0) {
            echo '<td class="tbl_ce_lor" style="width:50px; text-align:center" nowrap>';
			if ($row['right_bracket'] > 0)
				echo '<a href="#" onclick="javascript:calculation_bracket_edit_new(\''.$calculation.'\', \''.$vrednost.'\', \'right\', \'minus\'); return false;" title="'.$lang['srv_zaklepaj_rem'].'"><span class="faicon delete_circle"></span></a>';
			else
				echo '<span class="faicon delete_circle icon-grey_normal"></span>';

			echo '<a href="#" onclick="javascript:calculation_bracket_edit_new(\''.$calculation.'\', \''.$vrednost.'\', \'right\', \'plus\' ); return false;" title="'.$lang['srv_zaklepaj_add'].'"><span class="faicon add small"></span></a>';
		} else {
            echo '<td class="tbl_ce_lor white" style="width:50px; text-align:center" nowrap>';
//			echo '<span class="sprites delete_blue_light"></span>';
//			echo '<span class="sprites add_blue_light"></span>';
		}
		echo '</td>';

		// move
        echo '<td class="tbl_ce_bck_blue white" style="text-align:right; width:30px">';
        if ($row_count['count'] != 1 )
        	echo '<img src="img_0/move_updown.png" class="move" title="'.$lang['srv_move'].'" />';
        echo '</td>';

        // remove
        echo '<td class="tbl_ce_bck_blue white" style="text-align:left; width:30px">';
        $sql3 = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id='$row[cnd_id]'");
        if (mysqli_num_rows($sql3) != 1 )
            echo ' <a href="#" onclick="calculation_remove(\''.$row['cnd_id'].'\', \''.$calculation.'\', \''.$vrednost.'\'); return false;" title="'.$lang['srv_if_rem'].'"><span class="faicon delete icon-grey_dark_link delte-if-block"></span></a>'."\n\r";
        echo '</td>';


        echo '</tr>';
        echo '</table>';

        echo '</form>'."\n\r";

    }

    /**
	* @desc generira ime variable za novo spremenljivko
	*/
	function generate_variable ($preset = null) {

		$variable_array = array();

		
		// Dodaten loop po spremenljivkah, ki so znotraj kombinirane tabele (gru_id == -2)
		$sql = sisplet_query("SELECT s.id AS id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND s.tip='24' ORDER BY g.vrstni_red, s.vrstni_red");
		if(mysqli_num_rows($sql)){
			while ($row = mysqli_fetch_array($sql)) {
				$sqlM = sisplet_query("SELECT s.id, s.variable, s.variable_custom FROM srv_spremenljivka s, srv_grid_multiple m
										WHERE m.parent='".$row['id']."' AND m.ank_id='$this->anketa' AND s.id=m.spr_id
										ORDER BY m.vrstni_red");
				while ($rowM = mysqli_fetch_array($sqlM)) {
					$variable_array[$rowM['id']] = $rowM['variable'];
				}
			}
		}


		$sql = sisplet_query("SELECT s.id, variable, variable_custom FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND s.tip!='9' ORDER BY g.vrstni_red, s.vrstni_red");
		while ($row = mysqli_fetch_array($sql)) {
			$variable_array[$row['id']] = $row['variable'];
		}
		$i = 1;
		do {
			if ($preset === null) $preset = 'Q';
			$variable = $preset . $i++;

			$variable_ok = true;
			foreach ($variable_array AS $spr => $var) {
				if ($spr != $row['id'] && $var == $variable) {
					$variable_ok = false;
				}
			}
		} while ( ! $variable_ok );


		return $variable;
	}

	/**
	* @desc preveri ime variable za novo spremenljivko in doda številko če je potrebno
	*/
	function append_variable ($variable) {

		$variable_array = array();

		$sql = sisplet_query("SELECT s.id, variable, variable_custom FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND s.tip!='9' ORDER BY g.vrstni_red, s.vrstni_red");

		while ($row = mysqli_fetch_array($sql)) {
			$variable_array[$row['id']] = $row['variable'];
		}

		if ( ! in_array($variable, $variable_array) )
			return $variable;

		$i = 1;
		do {

			$variable_new = $variable . $i++;
			if ( ! in_array($variable_new, $variable_array) )
				return $variable_new;

		} while ( true );

	}

    /**
	* @desc vpise novo spremenljivko v bazo (lahko je skopirana)
	*
	* kopiranje je urejeno, da deluje z bazo ver 10.11.24, za nadaljne spremembe je potrebno dodati samo nove tabele, ker se celotna vsebina obstojecih tabel kopira avtomatsko cela
	*/
	function nova_spremenljivka($grupa, $grupa_vrstni_red, $vrstni_red, $kuki = 0) {
		global $lang;

		if ($kuki == 0)
			$kuki = $_COOKIE['srv_clipboard_' . $this->anketa];

		$variable = $this->generate_variable();
		$variable_custom = 0;

		// skopirali bomo spremenljivko
		if ($kuki > 0) {

			$spr = $kuki;

			$row = Cache::srv_spremenljivka($spr);
			if ($row['id'] != $spr) return;

			// zakaj je bilo to ??
			//$variable = $row['variable'] . '_' . $variable;

			// pri sistemskih se variable ohrani, ker jih nekje identificiramo po variable-i
			// in pri kopiranju iz in v knjiznico tudi
			if ($row['sistem'] == 1) {
				$variable = $row['variable'];
				$variable_custom = $row['variable_custom'];
				if ($grupa > 0) {
					$variable = $this->append_variable($variable);
				}
			}

			// kalkulacija ima svoj custom tip
			if ($row['tip'] == 22) {
				$sql1 = sisplet_query("SELECT s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND s.tip='22'");
				if (!$sql1) echo 'err453'.mysqli_error($GLOBALS['connect_db']);
				$c = 0;
				while ($row1 = mysqli_fetch_array($sql1)) {
					$row1['variable'] = (int)str_replace('C', '', $row1['variable']);
					if ($row1['variable'] > $c)
						$c = $row1['variable'];
				}
				$c++;
				$variable = 'C'.$c;
			}
			// name generator
			if ($row['tip'] == 9) {
				$sql1 = sisplet_query("SELECT s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND s.tip='9'");
				if (!$sql1) echo 'err453'.mysqli_error($GLOBALS['connect_db']);
				$c = 0;
				while ($row1 = mysqli_fetch_array($sql1)) {
					$row1['variable'] = (int)str_replace('G', '', $row1['variable']);
					if ($row1['variable'] > $c)
						$c = $row1['variable'];
				}
				$c++;
				$variable = 'G'.$c;
			}

			global $connect_db;

			SurveyCopy::setSrcSurvey($this->anketa);
			SurveyCopy::setSrcConectDb($connect_db);
			SurveyCopy::setDestSite(0);

			// spremenljivka
			$qry_src_spremenljivka = sisplet_query("SELECT * FROM srv_spremenljivka WHERE id = '$row[id]'");
			$pre_set = array(	'id' => "NULL",
								'gru_id' => "'$grupa'",
								'naslov' => "'".mysqli_real_escape_string($GLOBALS['connect_db'], $row['naslov'])."'",
								'variable' => "'".mysqli_real_escape_string($GLOBALS['connect_db'], $variable)."'",
								'variable_custom' => "'$variable_custom'",
								'thread' => "'0'",
								'edit_graf' => "'0'",				// naknadno popravimo editiranje grafov - po kopiranju je onemogoceno
							);
			$spr_array = SurveyCopy::preformCopyTable('srv_spremenljivka', 'id', SurveyCopy::sql2array($qry_src_spremenljivka), $pre_set);
			$spremenljivka = $spr_array[$row['id']];	// vrne v obliki arraya [star_id] => [nov_id]

			// if - podifi na vrednostih
			$new_if_ids = array ();
			$qry_src_vre_if = sisplet_query("SELECT * FROM srv_vrednost v WHERE v.if_id>0 AND v.spr_id='$spr'");
			while ($row_vre_if = mysqli_fetch_array($qry_src_vre_if)) {
				// IF-i, tabela srv_if
				$qry_src_if_ = sisplet_query("SELECT * FROM srv_if WHERE id = '".$row_vre_if['if_id']."'");
				//$qry_src_if = self::arrayfilter($arr_src['srv_if'], 'id', $row['id']);
				$pre_set = array('id' => "NULL");
				$tmp_if_ids = SurveyCopy::preformCopyTable('srv_if', 'id', SurveyCopy::sql2array($qry_src_if_), $pre_set);

				$new_if_ids += $tmp_if_ids;
			}

			// vrednost
			$qry_src_vrednost = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$spr' ORDER BY vrstni_red");
			$pre_set = array(	'id' => "NULL",
								'spr_id' => "'$spremenljivka'",
								'if_id' => array('field'=>'if_id', 'from'=>$new_if_ids)
							);
			$tmp_vrednosti_ids = SurveyCopy::preformCopyTable('srv_vrednost', 'id', SurveyCopy::sql2array($qry_src_vrednost), $pre_set);
                        
                        
                        // maps - choose
                        if ($row['tip'] == 26 && $row['enota'] == 3){
                            $new_vrednosti_ids = array();
                            // shranimo stare in nove id-je spremenljivk
                            if ( count($tmp_vrednosti_ids) > 0 )
                                foreach ($tmp_vrednosti_ids as $key => $value)
                                        $new_vrednosti_ids[$key] = $value;
                                                        
                            $qry_src_vrednost_map = sisplet_query("SELECT * FROM srv_vrednost_map WHERE spr_id='$spr' ORDER BY vrstni_red");
                            $pre_set = array('id' => "NULL",
                                    'spr_id' => "'$spremenljivka'",
                                    'vre_id' => array('field'=>'vre_id', 'from'=>$new_vrednosti_ids));
                            SurveyCopy::preformCopyTable('srv_vrednost_map', 'id', SurveyCopy::sql2array($qry_src_vrednost_map), $pre_set);
                        }

			// grid
			$qry_src_grid = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$spr' ORDER BY id");
			$pre_set = array(	'spr_id' => "'$spremenljivka'",
							);
			SurveyCopy::preformCopyTable('srv_grid', null, SurveyCopy::sql2array($qry_src_grid), $pre_set);


			// srv_grid_multiple
			$new_sub = array();
			$sqlm = sisplet_query("SELECT * FROM srv_grid_multiple WHERE parent = '$spr'");
			while ($rowm = mysqli_fetch_assoc($sqlm)) {
				$new_sub[$rowm['spr_id']] = $this->nova_spremenljivka(-2, 0, 0, $rowm['spr_id']);
			}

            $qry_src_grid_multiple = sisplet_query("SELECT * FROM srv_grid_multiple WHERE parent='$spr' ORDER BY vrstni_red");
        
            // Ce dodajamo kombinirano tabelo v knjiznico nastavimo ank_id v srv_grid_multiple na -1
            $multiple_ank_id = ($grupa == -1) ? '-1' : $this->anketa;

			$pre_set = array(	
                'ank_id' => $multiple_ank_id,
                'parent' => "'$spremenljivka'",
				'spr_id' => array('field'=>'spr_id','from'=>$new_sub),
			);
			SurveyCopy::preformCopyTable('srv_grid_multiple', null, SurveyCopy::sql2array($qry_src_grid_multiple), $pre_set);


			// srv_condition
			$qry_src_condition = sisplet_query("SELECT * FROM srv_condition WHERE if_id IN (".SurveyCopy::prepareSubquery(sisplet_query("SELECT v.if_id FROM srv_vrednost v WHERE v.spr_id='$spr' AND v.if_id>0")).")");
			if (!$qry_src_condition) echo mysqli_error($GLOBALS['connect_db']);
			$pre_set = array('id' => "NULL",
							'if_id' => array('field'=>'if_id', 'from'=>$new_if_ids)/*,
							'spr_id' => array('field'=>'spr_id', 'from'=>$new_spremenljivke_ids),
							'vre_id' => array('field'=>'vre_id', 'from'=>$new_vrednosti_ids)*/);
			$condition = SurveyCopy::preformCopyTable('srv_condition', 'id', SurveyCopy::sql2array($qry_src_condition), $pre_set);

			// condtition grid, tabela srv_condition_grid
			if (count($condition) > 0) {
				foreach ($condition AS $orig => $bckp) {
					// condtition grid, tabela srv_condition_grid
					$qry_src_condition_grid = sisplet_query("SELECT * FROM srv_condition_grid WHERE cond_id = '".$orig."'");
					//$src_srv_condition_grid = SurveyCopy::arrayfilter($arr_src['srv_condition_grid'], 'cond_id', $orig);
					$pre_set = array('id'=>"NULL",
									'cond_id' => "'".$bckp."'");
					$new_condition_grid_ids = SurveyCopy :: preformCopyTable('srv_condition_grid', 'id', SurveyCopy::sql2array($qry_src_condition_grid), $pre_set);
				}
			}

			// condtition vrednost, tabela srv_condition_vre
			if (count($condition) > 0) {
				foreach ($condition AS $orig => $bckp) {
					$qry_src_condition_vre = sisplet_query("SELECT * FROM srv_condition_vre WHERE cond_id = '$orig'");
					//$src_srv_condition_vre = SurveyCopy::arrayfilter($arr_src['srv_condition_vre'], 'cond_id', $orig);
					$pre_set = array('cond_id' => $bckp/*,
									'vre_id' => array('field'=>'vre_id', 'from'=>$new_vrednosti_ids)*/);
					SurveyCopy::preformCopyTable('srv_condition_vre', null, SurveyCopy::sql2array($qry_src_condition_vre), $pre_set);
				}
			}

			// kopiranje kalkulacije
			$sql2 = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id = '-$spr'");
            if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);
            while ($row2 = mysqli_fetch_array($sql2)) {
                $qry_src_calculation = sisplet_query("SELECT * FROM srv_calculation WHERE id = '$row2[id]'");
                $pre_set = array(	'id' =>	"NULL",
                					'cnd_id' => "'-$spremenljivka'",
                					'spr_id' => "'".$this->if_copy_spremenljivka($row2['spr_id'])."'",
                					'vre_id' =>	"'".$this->if_copy_vrednost($row2['vre_id'])."'"
                				);
            	SurveyCopy::preformCopyTable('srv_calculation', null, SurveyCopy::sql2array($qry_src_calculation), $pre_set);

            }

            // kopiranje kalkulacije v podifu
			$sql2 = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id IN (".SurveyCopy::prepareSubquery(sisplet_query("SELECT c.id FROM srv_vrednost v, srv_condition c WHERE c.if_id=v.if_id AND v.if_id >0 AND v.spr_id='$spr'")).")");
            if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);
            while ($row2 = mysqli_fetch_array($sql2)) {
                $qry_src_calculation = sisplet_query("SELECT * FROM srv_calculation WHERE id = '$row2[id]'");
                $pre_set = array(	'id' =>	"NULL",
                					'cnd_id' => array('field'=>'cnd_id', 'from'=>$condition)/*,
                					'spr_id' => "'".$this->if_copy_spremenljivka($row2['spr_id'])."'",
                					'vre_id' =>	"'".$this->if_copy_vrednost($row2['vre_id'])."'"*/
                				);
            	SurveyCopy::preformCopyTable('srv_calculation', null, SurveyCopy::sql2array($qry_src_calculation), $pre_set);

            }

		// nova (prazna) spremenljivka
		} else {

			// pri glasovanju ustvarimo samo prvic novo spr. z default 2 variablami
			if(isset($_POST['survey_type']) && $_POST['survey_type'] == 0)
				$size = 2;
			else
				$size = 3;

			if($type < 990)
				$sql = sisplet_query("INSERT INTO srv_spremenljivka (id, gru_id, naslov, variable, size, tip, vrstni_red) VALUES ('', '$grupa', '<p>$lang[srv_new_vprasanje]</p>', '$variable', '$size', '$type', '$vrstni_red')");
			//standardna vprasanja -> email, url, file upload, ime priimek
			else{
				if($type == 991) //email
					$sql = sisplet_query("INSERT INTO srv_spremenljivka (id, gru_id, naslov, variable, size, tip, vrstni_red) VALUES ('', '$grupa', '<p>Vnesite vaš email naslov</p>', 'email', '1', '4', '$vrstni_red')");
				elseif($type == 992) //url
					$sql = sisplet_query("INSERT INTO srv_spremenljivka (id, gru_id, naslov, variable, size, tip, vrstni_red) VALUES ('', '$grupa', '<p>Vnesite URL</p>', 'url', '1', '4', '$vrstni_red')");
				elseif($type == 993) //file upload
					$sql = sisplet_query("INSERT INTO srv_spremenljivka (id, gru_id, naslov, variable, size, tip, vrstni_red) VALUES ('', '$grupa', '<p>Naložite datoteko</p>', 'upload', '1', '4', '$vrstni_red')");
				elseif($type == 994) //ime priimek
					$sql = sisplet_query("INSERT INTO srv_spremenljivka (id, gru_id, naslov, variable, size, tip, vrstni_red, text_kosov, text_orientation) VALUES ('', '$grupa', '<p>Vnesite ime in priimek</p>', 'name', '1', '21', '$vrstni_red', '2', '2')");
			}

			if (!$sql)
				echo mysqli_error($GLOBALS['connect_db']);
			
			$spremenljivka = mysqli_insert_id($GLOBALS['connect_db']);
		}


		// vnesemo -4 tag v podatke, ki oznacuje novo spremenljivko
		$sql = sisplet_query("SELECT id FROM srv_user WHERE ank_id = '$this->anketa'");
		$query_values = "";
		while ($row = mysqli_fetch_array($sql)) {
			if($query_values != "") $query_values .= ", ";
			$query_values .= " ('$spremenljivka', '-4', '$row[id]') ";
		}
		if (mysqli_num_rows($sql) > 0)
			sisplet_query("INSERT INTO srv_data_vrednost".$this->db_table." (spr_id, vre_id, usr_id) VALUES $query_values");


        // Preverimo ce smo presegli limit za stevilo vprasanj
        $check = new SurveyCheck($this->anketa);
        $check->checkLimitSpremenljivke();

        
		return $spremenljivka;
	}



    /**
    * @desc ustvari nov IF (lahko je skopiran)
    */
    function if_new ($endif, $parent, $if_id, $vrstni_red, $spremenljivka, $if, $copy=0, $no_content=0, $include_element=true) {

    	if ($copy > 0)
    		$cookie = $copy;
    	else
        	$cookie = substr($_COOKIE['srv_clipboard_'.$this->anketa], 3);

		// Dodaten pogoj da nikoli ne vstavimo v srv_branching elementa ki ima element_spr=0 in element_if=0 (potem lahko pride do neskoncnega loopa kjer se dodajajo grupe v anketo)
		if ($if_id == 0) die('copy error2');	
			
        // skopiran if
        if ($cookie > 0 && $this->if_copy_check($parent, $cookie)) {

            sisplet_query("UPDATE srv_branching SET vrstni_red=vrstni_red+1 WHERE parent='$parent' AND vrstni_red>='$vrstni_red' AND ank_id='$this->anketa'");

            sisplet_query("INSERT INTO srv_branching (ank_id, parent, element_spr, element_if, vrstni_red) VALUES ('$this->anketa', '$parent', '0', '$if_id', '$vrstni_red')");

            $this->if_copy($if_id, $cookie, false, $no_content);

            // ob pastanju zbrisemo clipboard
            setcookie('srv_clipboard_'.$this->anketa, '', time()-3600);

        // navaden nov if (pri ENDIFu je nov if prazen, zato ne dodamo nicesar)
        } else {

            $s = sisplet_query("INSERT INTO srv_condition (id, if_id, vrstni_red) VALUES ('', '$if_id', '1')");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);

			if (!$include_element) {	// ce vstavimo if, moramo vrstne rede ostalih povecati (sicer pa ne, ker 1 element postavimo v IF)
				sisplet_query("UPDATE srv_branching SET vrstni_red=vrstni_red+1 WHERE parent='$parent' AND vrstni_red >= '$vrstni_red'  AND ank_id='$this->anketa'");
			}

            sisplet_query("INSERT INTO srv_branching (ank_id, parent, element_spr, element_if, vrstni_red) VALUES ('$this->anketa', '$parent', '0', '$if_id', '$vrstni_red')");

            if ( $include_element && ($spremenljivka > 0 || $if > 0) ) {
                // v if dodamo se spremenljivko oz if (karkoli je bilo pod crto)
                sisplet_query("UPDATE srv_branching SET parent='$if_id', vrstni_red='1' WHERE element_spr='$spremenljivka' AND element_if='$if' AND ank_id='$this->anketa'");
            }
        }
        $this->repare_branching($parent);
    }

    /**
    * @desc preverimo da ne kopiramo v isti IF (ker se rekurzivno kopira naprej zarad algoritma)
    * @param parent od elementa kamor kopiramo
    * @param if iz katerega bomo kopiral (v kukiju)
    */
    function if_copy_check($parent, $kuki) {

        if ($parent == $kuki)
            return false;

        if ($parent == 0)
            return true;

        $sql = sisplet_query("SELECT parent FROM srv_branching WHERE element_if='$parent' AND ank_id='$this->anketa'");
        $row = mysqli_fetch_array($sql);

        if ($row['parent'] > 0)
            if (!$this->if_copy_check($row['parent'], $kuki))
                return false;

        return true;
    }


    /**
    * @desc skopira vsebino enega ifa v drugega
    *
    * kopiranje je urejeno, da deluje z bazo ver 10.11.24, za nadaljne spremembe je potrebno dodati samo nove tabele, ker se celotna vsebina obstojecih tabel kopira avtomatsko cela
    *
    * @param if v katerega bomo kopiral
    * @param if iz katerega bomo kopiral
    * @param library pove, ce kopiramo v knjiznico, da ima ank_id -1
    */
    function if_copy($if, $if_copied, $library=false, $no_content=0) {

        // ce kopiramo v knjiznico, tukaj nardimo nov if
        if ($if == 0) {
            $sql = sisplet_query("INSERT INTO srv_if (id) VALUES ('')");
            if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
            $if = mysqli_insert_id($GLOBALS['connect_db']);
        }

        // popravimo if, ki je bil skreiran ze v funkciji ajax
        $sql = sisplet_query("SELECT * FROM srv_if WHERE id = '$if_copied'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        $s = sisplet_query("SHOW columns FROM srv_if");
        $update = '';
        while ($r = mysqli_fetch_array($s)) {
			if ($r['Field'] != 'id' && $r['Field'] != 'folder') {
				if ($update != '') $update .= ',';
				$update .= $r['Field']." = '".$row[$r['Field']]."' ";
			}
        }
        sisplet_query("UPDATE srv_if SET $update WHERE id = '$if'");


        global $connect_db;

		SurveyCopy::setSrcSurvey($this->anketa);
		SurveyCopy::setSrcConectDb($connect_db);
		SurveyCopy::setDestSite(0);


        // skopiramo pogoje
        $sql = Cache::srv_condition($if_copied);
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        while ($row = mysqli_fetch_array($sql)) {

            $qry_src_condition = sisplet_query("SELECT * FROM srv_condition WHERE id = '$row[id]'");
            $pre_set = array(	'id' => "NULL",
            					'if_id' => "'$if'",
            					'spr_id' => "'".$this->if_copy_spremenljivka($row['spr_id'])."'"
            				);
            $cond_array = SurveyCopy::preformCopyTable('srv_condition', 'id', SurveyCopy::sql2array($qry_src_condition), $pre_set);
            $cond_id = $cond_array[$row['id']];

            $vre_id = array();
            $sql2 = sisplet_query("SELECT * FROM srv_condition_vre WHERE cond_id = '$row[id]'");
            while ($row2 = mysqli_fetch_array($sql2)) {
				$vre_id[$row2['vre_id']] = $this->if_copy_vrednost($row2['vre_id']);
            }
            if (mysqli_num_rows($sql2) > 0) mysqli_data_seek($sql2, 0);
            $pre_set = array(	'cond_id' => "'$cond_id'",
            					'vre_id' => array('field'=>'vre_id', 'from'=>$vre_id)
            				);
            SurveyCopy::preformCopyTable('srv_condition_vre', null, SurveyCopy::sql2array($sql2), $pre_set);

            $sql2 = sisplet_query("SELECT * FROM srv_condition_grid WHERE cond_id = '$row[id]'");
            $pre_set = array(	'cond_id' => "'$cond_id'",
            				);
            SurveyCopy::preformCopyTable('srv_condition_grid', null, SurveyCopy::sql2array($sql2), $pre_set);

            $sql2 = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id = '$row[id]'");
            while ($row2 = mysqli_fetch_array($sql2)) {
				$vre_id[$row2['vre_id']] = $this->if_copy_vrednost($row2['vre_id']);
            }
            if (mysqli_num_rows($sql2) > 0) mysqli_data_seek($sql2, 0);
            $pre_set = array(	'id' => "NULL",
            					'cnd_id' => "'$cond_id'",
            					'spr_id' => array('field'=>'spr_id', 'from'=>$this->if_copy_spremenljivke),
            					'vre_id' => array('field'=>'vre_id', 'from'=>$vre_id)
            				);
            SurveyCopy::preformCopyTable('srv_calculation', null, SurveyCopy::sql2array($sql2), $pre_set);
        }

        // zanka
        // srv_loop
		$qry_src_loop = sisplet_query("SELECT * FROM srv_loop WHERE if_id = '$if_copied'");
		$pre_set = array(	'if_id' => "'$if'",
							'spr_id' => array('field'=>'spr_id','from'=>$this->if_copy_spremenljivke)
						);
		SurveyCopy::preformCopyTable('srv_loop', null, SurveyCopy::sql2array($qry_src_loop), $pre_set);

		// srv_loop_vre
		$qry_src_loop_vre = sisplet_query("SELECT * FROM srv_loop_vre WHERE if_id = '$if_copied'");
		while ($row2 = mysqli_fetch_array($qry_src_loop_vre)) {
			$vre_id[$row2['vre_id']] = $this->if_copy_vrednost($row2['vre_id']);
        }
        if (mysqli_num_rows($qry_src_loop_vre) > 0) mysqli_data_seek($qry_src_loop_vre, 0);
		$pre_set = array(	'if_id' => "'$if'",
							'vre_id' => array('field'=>'vre_id','from'=>$vre_id)
						);
		SurveyCopy::preformCopyTable('srv_loop_vre', null, SurveyCopy::sql2array($qry_src_loop_vre), $pre_set);

		// srv_loop_data
		$qry_src_loop_data = sisplet_query("SELECT * FROM srv_loop_data WHERE if_id = '$if_copied'");
		while ($row2 = mysqli_fetch_array($qry_src_loop_data)) {
			$vre_id[$row2['vre_id']] = $this->if_copy_vrednost($row2['vre_id']);
        }
        if (mysqli_num_rows($qry_src_loop_data) > 0) mysqli_data_seek($qry_src_loop_data, 0);
		$pre_set = array(	'id' => "NULL",
							'if_id' => "'$if'",
							'vre_id' => array('field'=>'vre_id','from'=>$vre_id)
						);
		SurveyCopy::preformCopyTable('srv_loop_data', 'id', SurveyCopy::sql2array($qry_src_loop_data), $pre_set);

        // ali kopiramo tudi vsebino ifa
        if ($no_content != 1) {

	        // gremo cez njegove childe in jih kopiramo
	        $sql = sisplet_query("SELECT * FROM srv_branching WHERE parent = '$if_copied' ORDER BY vrstni_red");
	        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
	        $values = '';
	        while ($row = mysqli_fetch_array($sql)) {

                if ($library)   // ce kopiramo v library
	                $anketa = -1;
	            elseif ($row['ank_id'] == -1)   // ce kopiramo iz librarija v anketo
	                $anketa = $this->anketa;
	            else
                    $anketa = $row['ank_id'];
                    
	            // element je spremenljivka -- kreiramo novo spremenljivko
	            if ($row['element_spr'] > 0) {

                    // Dobimo id strani
                    $sqlGrupa = sisplet_query("SELECT g.id, g.vrstni_red FROM srv_grupa g, srv_spremenljivka s WHERE s.id='".$row['element_if']."' AND s.gru_id=g.id");
	                if (!$sqlGrupa) echo mysqli_error($GLOBALS['connect_db']);
                    $rowGrupa = mysqli_fetch_array($sqlGrupa);

	                //$element_spr = $this->nova_spremenljivka($anketa, 0, $row['vrstni_red'], $row['element_spr']);
	                $element_spr = $this->nova_spremenljivka($rowGrupa['id'], $rowGrupa['vrstni_red'], $row['vrstni_red'], $row['element_spr']);
	                $element_if = 0;

	                $this->if_copy_spremenljivke[$row['element_spr']] = $element_spr;

	            // element je if --
	            } elseif ($row['element_if'] > 0) {

                    $sql1 = sisplet_query("SELECT * FROM srv_if WHERE id = '".$row['element_if']."'");
	                if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
                    $row1 = mysqli_fetch_array($sql1);
                    
	                $sql2 = sisplet_query("INSERT INTO srv_if (id, label) VALUES ('', '".$row1['label']."')");
	                $element_spr = 0;
                    $element_if = mysqli_insert_id($GLOBALS['connect_db']);
                    
	                // skopiramo rekurzivno se podif
	                $this->if_copy($element_if, $row['element_if'], $library);
	            }

	            $sql3 = sisplet_query("SELECT * FROM srv_branching WHERE ank_id='".$row['ank_id']."' AND parent='".$row['parent']."' AND element_spr='".$row['element_spr']."' AND element_if='".$row['element_if']."'");
	            $pre_set = array(	'ank_id' => "'$anketa'",
	            					'parent' => "'$if'",
	            					'element_spr' => "'$element_spr'",
	            					'element_if' => "'$element_if'"
	            				);
	        	SurveyCopy::preformCopyTable('srv_branching', null, SurveyCopy::sql2array($sql3), $pre_set);
	        }

		}

        return $if; // to je ko kopiramo v library, da dobimo ID ifa
    }

    private $if_copy_spremenljivke = array(); // povezave originalnih spremenljivk s skopiranimi (da lahko spremenimo pogoje)

    /**
    * @desc za podano spremenljivko vrne skopirano spremenljivko, ce smo jo skopiral. ce ne, vrne original
    */
    function if_copy_spremenljivka ($spr) {

        if ($spr < 0)
            return $spr;

        if ($this->if_copy_spremenljivke[$spr] > 0)
            return $this->if_copy_spremenljivke[$spr];

        $sql = sisplet_query("SELECT g.ank_id FROM srv_spremenljivka s, srv_grupa g WHERE s.id = '$spr' AND s.gru_id=g.id");
        $row = mysqli_fetch_array($sql);
        if ($row['ank_id'] == $this->anketa)
            return $spr;

        return 0;
    }

    /**
    * @desc za podano vrednost v (podani) spremenljivki vrne skopirano vrednost (skopirane spremenljivke)
    * ce ni bila skopirana v tej rundi vrne original (to pomeni, da se if nanasa na spremenljivko izven bloka ki se kopira)
    */
    function if_copy_vrednost ($vre) {

        $sql = sisplet_query("SELECT spr_id, vrstni_red FROM srv_vrednost WHERE id = '$vre'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        $spr_copy = $this->if_copy_spremenljivke[$row['spr_id']];
        if ($spr_copy > 0) {

            $vrstni_red = $row['vrstni_red'];

            $sql = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$spr_copy' AND vrstni_red = '$vrstni_red'");
            if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
            $row = mysqli_fetch_array($sql);

            return $row['id'];

        } else {

            $sql = sisplet_query("SELECT g.ank_id FROM srv_vrednost v, srv_spremenljivka s, srv_grupa g WHERE v.id='$vre' AND v.spr_id=s.id AND s.gru_id=g.id");
            $row = mysqli_fetch_array($sql);

            if ($row['ank_id'] == $this->anketa)
                return $vre;
            else
                return 0;

        }
    }

    /**
    * @desc preveri ali lahko droppamo spremenljivko na to mesto (da se ne unicijo pogoji)
    * @param spremenljivka ki jo droppamo
    * @param parent kamor smo droppal spremenljivko
    * @param vrstni_red znotraj parenta kamor smo droppal
    * Pogledamo kam bo padla spremenljivka in potem za vse pogoje pred novo pozicijo spremenljivke
    * preverimo, da nimajo v condition-u te spremenljivke
    */
    function check_dropped_spremenljivka ($spremenljivka, $parent, $vrstni_red) {

        // vrstni red kam bo prisla spremenljivka
        $vrstni_red = $this->check_vrstni_red($parent, $vrstni_red);

        $sql = sisplet_query("SELECT i.id, i.tip FROM srv_branching b, srv_if i WHERE i.id=b.element_if AND b.element_if > '0' AND b.ank_id='$this->anketa' ORDER BY b.vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        while ($row = mysqli_fetch_array($sql)) {

            // ce je pogoj pred novo pozicijo spremenljivke
            if ($this->vrstni_red_branching($row['id']) <= $vrstni_red)
                if ($row['tip'] == 0) {			// if
                	if (!$this->check_if($row['id'], $spremenljivka))
                    	return false;
				} elseif ($row['tip'] == 2) {	// zanka
					if (!$this->check_loop_spremenljivka($row['id'], $spremenljivka))
						return false;
				}

        }

        return true;
    }

    /**
    * @desc preveri ali lahko droppamo if na to mesto (da se ne unicijo pogoji)
    * Najprej poiscemo novo pozicijo IFa in potem za vse spremenljivke za novo pozicijo pogoja (in pred staro)
    * preverimo da ni spremenljivka v katerem od pogojev IFa (in tudi podifov tega IFa)
    * Potem pa se za vse IFe pred droppanim IFom preverimo, da nimajo v pogoju kaksne spremenljivke
    * iz droppanga IFa
    */
    function check_dropped_if ($if, $parent, $vrstni_red) {

        // vrstni red kam bo prsu if
        $vrstni_red = $this->check_vrstni_red($parent, $vrstni_red);

        // spremenljivke za IFom
        foreach ($this->get_subifs($if) AS $ifs) {
            //echo $ifs.': ';

            $sql = sisplet_query("SELECT tip FROM srv_if WHERE id = '$ifs'");
    		$row = mysqli_fetch_array($sql);
    		$tip = $row['tip'];

            $sql = sisplet_query("SELECT element_spr FROM srv_branching WHERE element_spr > '0' AND ank_id='$this->anketa' ORDER BY vrstni_red ASC");
            if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
            while ($row = mysqli_fetch_array($sql)) {

                // ce je spremenljivka za novo pozicijo ifa (in pred staro)
                if ($this->vrstni_red_branching(0, $row['element_spr']) > $vrstni_red &&
                    $this->vrstni_red_branching(0, $row['element_spr']) < $this->vrstni_red_branching($if))
                    if ($tip == 0) {			// if
	                    if (!$this->check_if($ifs, $row['element_spr']))
	                        return false;
					} elseif ($tip == 2) {		// zanka
						if (!$this->check_loop_spremenljivka($ifs, $row['element_spr']))
	                        return false;
					}

            }
        }


        // IFi pred droppanim IFom
        $sql = sisplet_query("SELECT element_if FROM srv_branching WHERE element_if > '0' AND ank_id='$this->anketa' ORDER BY vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        while ($row = mysqli_fetch_array($sql)) {

            // gledamo samo za tiste IFe, ki niso vgnezdeni v droppanem IFu
            if (!in_array($row['element_if'], $this->get_subifs($if)))
                // ce je pogoj pred novo pozicijo droppanga ifa
                if ($this->vrstni_red_branching($row['element_if']) <= $vrstni_red)
                    foreach ($this->get_subspr($if) AS $spr)
                        if (!$this->check_if($row['element_if'], $spr))
                            return false;

        }

        return true;
    }

    /**
    * @desc poisce vrstni red kamor bo paddel dropped element
    */
    function check_vrstni_red ($parent, $vrstni_red) {

        // spustil smo na prvi element v IFu (ki ima vrstni red 0)
        if ($vrstni_red == 0) {
            return $this->vrstni_red_branching($parent);

        } else {

            $sql = sisplet_query("SELECT element_spr, element_if FROM srv_branching WHERE parent='$parent' AND ank_id='$this->anketa' AND vrstni_red='$vrstni_red'");
            if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
            $row = mysqli_fetch_array($sql);

            // spustil smo pod spremenljivko
            if ($row['element_spr'] > 0) {
                return $this->vrstni_red_branching(0, $row['element_spr']);

            // spustil smo pod ENDIF, zato poiscemo zadnji element v ifu (ker je isti globalni vrstni red -- endif nima vpliva)
            // oz. ce je ta IF prazen poiscemo kar vrstni red tega IFa
            } else {
                return $this->find_last_elm_in_if($row['element_if']);
            }
        }
    }

    /**
    * @desc preveri, da ni spremenljivka v pogoju IFa. Vrne true, ce spremenljivke ni med pogoji
    */
    function check_if ($if, $spremenljivka) {

		$sql = Cache::srv_condition($if);	
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        while ($row = mysqli_fetch_array($sql)) {
            if ($row['spr_id'] == $spremenljivka)
                return false;
        }
		
        return true;
    }

    /**
    * preveri, da spremenljivka ni nastavljena, da se loopa po njej v zanki
    *
    * @param mixed $if
    * @param mixed $spremenljivka
    */
    function check_loop_spremenljivka ($if, $spremenljivka) {
		
		$sql = sisplet_query("SELECT if_id, spr_id FROM srv_loop WHERE if_id = '$if'");
		$row = mysqli_fetch_array($sql);
		if ($row['spr_id'] == $spremenljivka) return false;
		
		return true;
    }

    /**
    * @desc vrne vse podife podanega ifa (vkljucno s podanim ifom)
    */
    function get_subifs ($if) {

        $array = array();
        array_push($array, $if);

        $sql = sisplet_query("SELECT element_if FROM srv_branching WHERE parent = '$if' AND ank_id='$this->anketa' ORDER BY vrstni_red");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        while ($row = mysqli_fetch_array($sql)) {

            if ($row['element_if'] > 0) {
                foreach ($this->get_subifs($row['element_if']) AS $key)
                    array_push($array, $key);
            }
        }

        return $array;
    }

    /**
    * @desc vrne array vseh spremenljivk vgnezdenih v podanem ifu
    */
    function get_subspr ($if) {

        $array = array();

        $sql = sisplet_query("SELECT element_spr, element_if FROM srv_branching WHERE parent = '$if' AND ank_id='$this->anketa' ORDER BY vrstni_red");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        while ($row = mysqli_fetch_array($sql)) {

            if ($row['element_spr'] > 0)
                array_push($array, $row['element_spr']);
            else
                foreach ($this->get_subspr($row['element_if']) AS $key)
                    array_push($array, $key);
        }

        return $array;
    }

    /**
    * @desc popravi vrstne rede v obicajnem urejanju (srv_spremenljivka) ob premikanju v branchingu (srv_branching)
    */
    var $spremenljivka_grupa = null;			// cache trenutnih vrednosti, da izvedemo query, samo pri tistih, kjer je razlika
    var $repare_vrstni_red_values = '';		// kesiramo update query, da se tudi pri rekurzivnih klicih te funkcije, izvede samo 1x
    function repare_vrstni_red ($parent=0, $spr_vr=1, $gru_vr=1) {

		if ($parent == 0) sisplet_query("BEGIN");

		// preberemo cel branching, ker dostopamo iz $this->pagebreak();
		if ($parent == 0)
			Cache::cache_all_srv_branching($this->anketa);

		if ($this->spremenljivka_grupa == null) {
			$sql = sisplet_query("SELECT s.id, s.gru_id, s.vrstni_red FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa'");
			while ($row = mysqli_fetch_array($sql)) {
				$a = array('vrstni_red' => $row['vrstni_red'], 'gru_id' => $row['gru_id']);
				$this->spremenljivka_grupa[$row['id']] = $a;
			}
		}

        // gremo cez vse v srv_branching
        $sql = sisplet_query("SELECT element_spr, element_if FROM srv_branching WHERE ank_id='$this->anketa' AND parent='$parent' ORDER BY vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        $values = "";
        while ($row = mysqli_fetch_array($sql)) {
            if ($row['element_spr'] > 0) {

                $gru_id = $this->find_grupa($gru_vr);

				if ($this->spremenljivka_grupa[$row['element_spr']]['vrstni_red'] != $spr_vr OR $this->spremenljivka_grupa[$row['element_spr']]['gru_id'] != $gru_id) {
                	if ( $gru_id > 0 ) {
						sisplet_query("UPDATE srv_spremenljivka SET vrstni_red = '$spr_vr', gru_id='$gru_id' WHERE id = '$row[element_spr]'");
					
						if ($values != "")	$values .= ", ";
						$values .= "('$row[element_spr]', '$gru_id', '$spr_vr')";
					}
				}

                // ce je za njim pagebreak, poiscemo novo grupo
                if ($this->pagebreak($row['element_spr'])) {
                    $gru_vr++;
                    $spr_vr = 1;
                // naslednja spremenljivka je na isti strani
                } else {
                    $spr_vr++;
                }

            } else if ($row['element_if'] > 0){

                // rekurzivni klic iste funckije, ki gre cez ife
                $arr = $this->repare_vrstni_red($row['element_if'], $spr_vr, $gru_vr);

                $spr_vr = $arr[0];
                $gru_vr = $arr[1];
            }
        }

        if ($this->repare_vrstni_red_values != "" && $values != "") $this->repare_vrstni_red_values .= ",";
        $this->repare_vrstni_red_values .= $values;

        if ($this->repare_vrstni_red_values != "" && $parent == 0) {
			//$s = sisplet_query("INSERT INTO srv_spremenljivka (id, gru_id, vrstni_red) VALUES ".$this->repare_vrstni_red_values." ON DUPLICATE KEY UPDATE gru_id=VALUES(gru_id), vrstni_red=VALUES(vrstni_red)");
			//if (!$s) echo 'e007'.mysqli_error($GLOBALS['connect_db']);
		}

		if ($parent == 0) sisplet_query("COMMIT");

        $a = Array($spr_vr, $gru_vr);
        return $a;
    }

    /**
    * @desc poisce grupo s podanim vrstnim redom
    */
    var $find_grupa = array();
    function find_grupa ($gru_vr) {
        global $lang;

        if (isset($this->find_grupa[$gru_vr])) {
			return $this->find_grupa[$gru_vr];
        }

        $sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$this->anketa' AND vrstni_red = '$gru_vr'");
        $row = mysqli_fetch_array($sql);

        if ($row['id'] > 0) {
        	$this->find_grupa[$gru_vr] = $row['id'];
        	return $this->find_grupa[$gru_vr];
		}

        $sql = sisplet_query("INSERT INTO srv_grupa (ank_id, naslov, vrstni_red) VALUES ('$this->anketa', '$lang[srv_stran] $gru_vr', '$gru_vr')");
        $this->find_grupa[$gru_vr] = mysqli_insert_id($GLOBALS['connect_db']);

		// Ce dodamo 4. stran vklopimo progress indicator (pri 3 straneh ali manj je po default izklopljen)
		$sql2 = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$this->anketa'");
		$vrstni_red = mysqli_num_rows($sql2);
		if($vrstni_red == 4){
			$sqlP = sisplet_query("UPDATE srv_anketa SET progressbar='1' WHERE id='$this->anketa'");
		}

        return $this->find_grupa[$gru_vr];
    }

    /**
    * @desc odstrani prazne grupe (strani) na koncu ankete
    */
    function trim_grupe () {

		// pogledamo, ce je zadnja grupa prazna in nato rekurzivno klicemo funkcijo dokler so prazne
        $sqlG = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$this->anketa' ORDER BY vrstni_red DESC");

		// Ce imamo samo se 1 grupo je ne smemo pobrisati - izstopni pogoj
		if(mysqli_num_rows($sqlG) > 1){
			$rowG = mysqli_fetch_array($sqlG);

			$sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id = '$rowG[id]'");
			if (mysqli_num_rows($sql) == 0) {
				sisplet_query("DELETE FROM srv_grupa WHERE id = '$rowG[id]'");
				$this->trim_grupe();
			}
		}
    }

    /**
    * zgenerira strukturo za hitrejse delovanje find_next_spr() in find_prev_spr()
    * TODO - ni v uporabi...
    */
    private $find_all_spr = null;
    function find_all_spremenljivka () {

		$sql = sisplet_query("SELECT s.id AS id, g.naslov AS grupa_naslov FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' ORDER BY g.vrstni_red, s.vrstni_red");
		while ($row = mysqli_fetch_assoc($sql)) {
			$this->find_all_spr[$row['id']] = $row;
		}
	}

    /**
    * @desc poisce naslednjo spremenljivko za podano spremenljivko
    */
    function find_next_spr ($spremenljivka) {

        $sql = sisplet_query("SELECT vrstni_red, gru_id FROM srv_spremenljivka WHERE id = '$spremenljivka'");
        $row = mysqli_fetch_array($sql);
        $vrstni_red = $row['vrstni_red'];

        // naslednij je na isti strani
        $next = $vrstni_red + 1;
        $sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE vrstni_red='$next' AND gru_id='$row[gru_id]'");
        if (mysqli_num_rows($sql) > 0) {
            $row = mysqli_fetch_array($sql);
            return $row['id'];
        }

        // naslednik je na naslednji strani
        $sql = sisplet_query("SELECT g.vrstni_red FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND s.id='$spremenljivka'");
        $row = mysqli_fetch_array($sql);
        $grupa_red = $row['vrstni_red'];
        $next = $grupa_red + 1;
        $sql = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND g.vrstni_red = '$next' ORDER BY s.vrstni_red ASC LIMIT 1");
        $row = mysqli_fetch_array($sql);
        return $row['id'];

    }

    /**
    * @desc poisce predhodnjo spremenljivko za podano spremenljivko
    */
    var $find_prev_spr = array();
    function find_prev_spr ($spremenljivka) {

        if (array_key_exists($spremenljivka, $this->find_prev_spr)) {
            return $this->find_prev_spr[$spremenljivka];
        }

        $sql = sisplet_query("SELECT vrstni_red, gru_id FROM srv_spremenljivka WHERE id = '$spremenljivka'");
        $row = mysqli_fetch_array($sql);
        $vrstni_red = $row['vrstni_red'];

        // predhodnik je na isti strani
        $prev = $vrstni_red - 1;
        $sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE vrstni_red='$prev' AND gru_id='$row[gru_id]'");
        if (mysqli_num_rows($sql) > 0) {
            $row = mysqli_fetch_array($sql);

            $this->find_prev_spr[$spremenljivka] = $row['id'];
            return $row['id'];
        }

        // predhodnik je na prejsnji strani
        $sql = sisplet_query("SELECT g.vrstni_red FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND s.id='$spremenljivka'");
        $row = mysqli_fetch_array($sql);
        $grupa_red = $row['vrstni_red'];
        $prev = $grupa_red - 1;
        $sql = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND g.vrstni_red = '$prev' ORDER BY s.vrstni_red DESC LIMIT 1");
        $row = mysqli_fetch_array($sql);

        $this->find_prev_spr[$spremenljivka] = $row['id'];
        return $row['id'];

    }

	var $prev_srv_branching = 0; // zacasna spremenljivka
	/**
	* poisce predhodnjo spremenljivko v srv_branching tabeli
	*
	* @param mixed $spremenljivka
	*/
	function find_prev_spr_branching ($spremenljivka, $parent=0) {

		$sql = sisplet_query("SELECT element_spr, element_if FROM srv_branching WHERE ank_id='$this->anketa' AND parent='$parent' ORDER BY vrstni_red ASC");
        while ($row = mysqli_fetch_array($sql)) {

			if ($row['element_spr'] > 0) {
				if ($row['element_spr'] == $spremenljivka) return $this->prev_srv_branching;
				$this->prev_srv_branching = $row['element_spr'];

			} else {
				$r = $this->find_prev_spr_branching($spremenljivka, $row['element_if']);
				if ($r > 0) return $r;
			}
        }

        return 0;
	}

    /**
    * @desc Poisce zadnjo spremenljivko v anketi
    */
    var $find_last_spr = 0;
    function find_last_spr () {

        if ($this->find_last_spr > 0)
            return $this->find_last_spr;

        $sql = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' ORDER BY g.vrstni_red DESC, s.vrstni_red DESC LIMIT 1");
        $row = mysqli_fetch_array($sql);

        $this->find_last_spr = $row['id'];
        return $row['id'];

    }

    /**
    * poisce zadnjo spremenljivko v tabeli srv_branching
    *
    */
    function find_last_spr_branching ($parent = 0) {

        $sql = sisplet_query("SELECT element_spr, element_if FROM srv_branching WHERE ank_id='$this->anketa' AND parent='$parent' ORDER BY vrstni_red DESC LIMIT 1");
        $row = mysqli_fetch_array($sql);

        if ($row['element_spr'] > 0) return $row['element_spr'];

        if ( mysqli_num_rows($sql) > 0 )
        	return $this->find_last_spr_branching($row['element_if']);

        return 0;
    }

    /**
    * vrne array grupe za podano spremenljivko
    *
    * @param mixed $spremenljivka
    * @return array
    */
    function getGrupa4Spremenljivka($spremenljivka) {
		
		$sql = sisplet_query("SELECT g.* FROM srv_grupa AS g WHERE g.id = (SELECT s.gru_id FROM srv_spremenljivka as s WHERE s.id = '$spremenljivka')");
		$row = mysqli_fetch_assoc($sql);
		
		return $row;
	}

	/**
    * vrne array grupe za podan if
    *
    * @param mixed $if_id
    * @return array
    */
    function getGrupa4If($if_id) {

		$sqlBr = sisplet_query("SELECT vrstni_red FROM srv_branching WHERE ank_id='$this->anketa' AND element_if='$if_id'");
		$rowBr = mysqli_fetch_assoc($sqlBr);
		$vrstni_red_if = $rowBr['vrstni_red'];

		if($vrstni_red_if == 1){
			$sql = sisplet_query("SELECT * FROM srv_grupa WHERE ank_id='$this->anketa' AND vrstni_red='1'");
			$row = mysqli_fetch_assoc($sql);
		}
		else{
			$sqlB = sisplet_query("SELECT element_spr, pagebreak FROM srv_branching WHERE ank_id='$this->anketa' AND vrstni_red<'$vrstni_red_if' AND element_spr>'0' ORDER BY vrstni_red DESC");
			$rowB = mysqli_fetch_assoc($sqlB);

			if($rowB['pagebreak'] == 0){
				$row = $this->getGrupa4Spremenljivka($rowB['element_spr']);
			}
			else{
				$prev_grupa = $this->getGrupa4Spremenljivka($rowB['element_spr']);
				$row['id'] = $this->find_grupa($prev_grupa['vrstni_red']+1);
				$row['vrstni_red'] = $prev_grupa['vrstni_red']+1;
			}
		}

		return $row;
	}

    /**
    * @desc Poisce prvo spremenljivko v anketi
    */
    var $find_first_spr = 0;
    function find_first_spr () {

        if ($this->find_first_spr > 0)
            return $this->find_first_spr;

        $sql = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' ORDER BY g.vrstni_red ASC, s.vrstni_red ASC LIMIT 1");
        $row = mysqli_fetch_array($sql);

        $this->find_first_spr = $row['id'];
        return $row['id'];

    }

        /**
    * @desc vrne (globalni) vrstni red spremenljivke, uposteva tudi stran
    */
    var $vrstni_red = array();
    function vrstni_red($spremenljivka) {

    	if ($spremenljivka == 0) return 0;
        //$this->repareAnketa();

        if (array_key_exists($spremenljivka, $this->vrstni_red)) {
			return $this->vrstni_red[$spremenljivka];
        }

        $sql = sisplet_query("SELECT id, gru_id, vrstni_red FROM srv_spremenljivka WHERE id = '$spremenljivka'");
        $row = mysqli_fetch_array($sql);
        $vrstni_red = $row['vrstni_red'];

        $sqlg = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id = '$row[gru_id]'");
        $rowg = mysqli_fetch_array($sqlg);

        $sql1 = sisplet_query("SELECT id FROM srv_grupa WHERE vrstni_red < '$rowg[vrstni_red]' AND ank_id='$this->anketa'");
        while ($row1 = mysqli_fetch_array($sql1)) {

            $vrstni_red += $this->prestej_grupo($row1['id']);
        }

        $this->vrstni_red[$spremenljivka] = $vrstni_red;
        return $this->vrstni_red[$spremenljivka];
    }

    /**
    * @desc presteje spremenljivke v grupi
    */
    var $prestej_grupo = array();
    function prestej_grupo ($grupa) {

        if (array_key_exists($grupa, $this->prestej_grupo)) {
            return $this->prestej_grupo[$grupa];
        }

        $sql2 = sisplet_query("SELECT COUNT(*) AS count FROM srv_spremenljivka WHERE gru_id = '$grupa'");
        $row2 = mysqli_fetch_array($sql2);

        $this->prestej_grupo[$grupa] = $row2['count'];
        return $row2['count'];
    }

    /**
    * poisce naslednji element (spremenljivko, if, blok) in vrne njegov row (parent, vrstni_red, element_spr, element_if)
    * v primeru ENDIFA, vrne prazno
    *
    * @param mixed $spr
    * @param mixed $if
    */
    var $find_next_element = array();
    function find_next_element($parent, $vrstni_red) {

		if (isset($this->find_next_element[$spr][$if]))
			return $this->find_next_element[$spr][$if];

		$sql = sisplet_query("SELECT parent, vrstni_red, element_spr, element_if FROM srv_branching WHERE parent='$parent' AND vrstni_red>'$vrstni_red' AND ank_id='$this->anketa' ORDER BY vrstni_red ASC LIMIT 1");
		if (mysqli_num_rows($sql) > 0) {
			$row = mysqli_fetch_array($sql);
			$this->find_next_element[$spr][$if] = $row;
		} else {
			$this->find_next_element[$spr][$if] = null;
		}

		return $this->find_next_element[$spr][$if];
	}

    /**
    * @desc poklice funkcijo za poiskat spremenljivko pred podanim ifom
    */
    function find_before_if ($if) {
        $a = $this->find_before_if_fun($if);
        return $a[1];
    }

    /**
    * @desc poisce spremenljivko pred podanim ifom
    */
    function find_before_if_fun ($if, $parent=0, $prev=0) {

        $sql = sisplet_query("SELECT element_spr, element_if FROM srv_branching WHERE ank_id='$this->anketa' AND parent='$parent' ORDER BY vrstni_red");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        while ($row = mysqli_fetch_array($sql)) {

            if ($row['element_if'] > 0) {

                if ($row['element_if'] == $if) {
                    $a[0] = 1;
                    $a[1] = $prev;
                    return $a;
                }

                $a = $this->find_before_if_fun($if, $row['element_if'], $prev);
                if ($a[0] == 1) {
                    return $a;
                }
                $prev = $a[1];

            } else {

                $prev = $row['element_spr'];
            }
        }

        $a[0] = 0;
        $a[1] = $prev;
        return $a;
    }

    /**
    * @desc vrne vrstni red zadnjega elementa v ifu (spremenljivka ali if -- ce je prazen, drugace je zadnja v vsakem primeru spremenljivka)
    */
    function find_last_elm_in_if ($if) {

        $sql1 = sisplet_query("SELECT element_spr, element_if FROM srv_branching WHERE parent='$if' AND ank_id='$this->anketa' ORDER BY vrstni_red DESC LIMIT 1");

        // if ni prazen, gremo dalje
        if (mysqli_num_rows($sql1) > 0) {

            $row1 = mysqli_fetch_array($sql1);

            // zadnja je spremenljivka -- vrnemo jo
            if ($row1['element_spr'] > 0) {			
                return $this->vrstni_red_branching(0, $row1['element_spr']);

            // na zadnjem mestu je if -- gremo rekurzivno naprej
            } else {
                return $this->find_last_elm_in_if($row1['element_if']);
            }

        // if je prazen, vrnemo kar vrstni red ifa
        } else {
            return $this->vrstni_red_branching($if);
        }

    }

    /**
    * @desc poisce zadnjo spremenljivko v IFu
    */
    function find_last_in_if ($if) {

        if ($if == null) return null;

        if (!$this->find_spr_in_if($if)) return null;

        $sql = sisplet_query("SELECT element_spr, element_if FROM srv_branching WHERE parent='$if' AND ank_id='$this->anketa' ORDER BY vrstni_red DESC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        while ($row = mysqli_fetch_array($sql)) {

            if ($row['element_spr'] > 0)
                return $row['element_spr'];
            else {
                 $r = $this->find_last_in_if($row['element_if']);
                 if ($r != null)
                    return $r;
            }
        }
    }

    /**
    * @desc poisce prvo spremenljivko v IFu
    */
    function find_first_in_if ($if) {

        if ($if == null) return null;

        if (!$this->find_spr_in_if($if)) return null;

        $sql = sisplet_query("SELECT element_spr, element_if FROM srv_branching WHERE parent='$if' AND ank_id='$this->anketa' ORDER BY vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        while ($row = mysqli_fetch_array($sql)) {

            if ($row['element_spr'] > 0)
                return $row['element_spr'];
            else {
                 $r = $this->find_first_in_if($row['element_if']);
                 if ($r != null)
                    return $r;
            }
        }
    }

    /**
    * @desc preveri ali so kaksne spremenljivke v IFu
    */
    function find_spr_in_if ($if) {

        $sql = sisplet_query("SELECT element_spr, element_if FROM srv_branching WHERE parent='$if' AND ank_id='$this->anketa'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        while ($row = mysqli_fetch_array($sql)) {
            if ($row['element_spr'] > 0)
                return true;
            elseif ($row['element_if'] > 0)
                if ($this->find_spr_in_if($row['element_if']))
                    return true;
        }

        return false;
    }

    /**
    * @desc presteje koliko spremenljiv je v IFu
    */
    function count_spr_in_if ($if) {
		$count = 0;

        $sql = sisplet_query("SELECT element_spr, element_if FROM srv_branching WHERE parent='$if' AND ank_id='$this->anketa'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        while ($row = mysqli_fetch_array($sql)) {
            if ($row['element_spr'] > 0)
                $count++;
            elseif ($row['element_if'] > 0)
            	$count += $this->count_spr_in_if($row['element_if']);

        }

        return $count;
    }

    private $vrstni_red_branching = null;
    /**
    * vrne vrstni red elementa v branching strukturi (uposteva tudi ife itd..)
    *
    * @param mixed $if
    * @param mixed $spremenljivka
    */
    function vrstni_red_branching ($if, $spremenljivka=0) {

		if ($this->vrstni_red_branching === null) {

			Cache::cache_all_srv_branching($this->anketa);
			$vrstni_red = 1;
			$this->vrstni_red_branching_fun(0, $vrstni_red);
		}

		return $this->vrstni_red_branching[$if][$spremenljivka];
    }

    /**
    * zgenerira array z vrstnimi redi elementov (rekurzivno)
    *
    * @param mixed $parent
    * @param mixed $vrstni_red
    */
    function vrstni_red_branching_fun ($parent, &$vrstni_red) {

		foreach (Cache::srv_branching_parent($this->anketa, $parent) AS $k => $row) {

			$this->vrstni_red_branching[$row['element_if']][$row['element_spr']] = $vrstni_red;
            $vrstni_red++;

            if ($row['element_if'] > 0) {
                $this->vrstni_red_branching_fun($row['element_if'], $vrstni_red);
            }
		}
    }

    /**
    * @desc poklice repareAnketa, ki popravi celotno anketo
    */
    function repareAnketa () {

    	Common::repareAnketa($this->anketa);
    }

    /**
    * pri repare branching bomo šli zdej vedno čez celo strukturo, ker drugace zgleda da nekaj ni ok
    *
    * @param mixed $parent
    */
    function repare_branching ($parent=0) {

		$this->repare_branching_do();
    }

    /**
    * @desc rekurzivno popravi vrstne rede v branchingu
    */
    function repare_branching_do ($parent=0) {

        $vrstni_red = 1;

        $sql = sisplet_query("SELECT element_spr, element_if FROM srv_branching WHERE parent='$parent' AND ank_id='$this->anketa' ORDER BY vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        while ($row = mysqli_fetch_array($sql)) {
        	
			if ($row['vrstni_red'] != $vrstni_red) {
            	sisplet_query("UPDATE srv_branching SET vrstni_red='$vrstni_red' WHERE element_spr='$row[element_spr]' AND element_if='$row[element_if]' AND ank_id='$this->anketa'");
			}
			
            $vrstni_red++;

            if ($row['element_if'] > 0)
            	$this->repare_branching_do($row['element_if']);
        }
    }

    /**
    * @desc preveri ali je spremenljivka zadnja na strani (pagebreak)
    */
    var $pagebreak = array();
    function pagebreak ($spremenljivka) {

        if (array_key_exists($spremenljivka, $this->pagebreak)) {
            return $this->pagebreak[$spremenljivka];
        }

		$row = Cache::srv_branching($spremenljivka, 0);

        if ($row['pagebreak'] == 1) {
            $this->pagebreak[$spremenljivka] = true;
            return true;
        } else {
            $this->pagebreak[$spremenljivka] = false;
            return false;
        }
    }

    /**
    * @desc vrne nivo (level) spremenljivke v strukturi branchinga
    */
    var $level = array();
    function level ($element_spr, $element_if=0) {

        if (isset($this->level[$element_spr][$element_if])) {
            return $this->level[$element_spr][$element_if];
        }

		$row = Cache::srv_branching($element_spr, $element_if);

        if ($row['parent'] == 0) {

            $this->level[$element_spr][$element_if] = 0;
            return 0;

        } else {

            $value = $this->level(0, $row['parent']) + 1;
            $this->level[$element_spr][$element_if] = $value;

            return $value;
        }
    }

    /**
    * @desc popravi vrstne rede v conditionu
    */
    function repare_condition ($if) {

        $sql = Cache::srv_condition($if);
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        $vrstni_red=1;

        while ($row = mysqli_fetch_array($sql)) {
        	if ($row['vrstni_red'] != $vrstni_red)
            	sisplet_query("UPDATE srv_condition SET vrstni_red='$vrstni_red' WHERE id = '$row[id]'");
            $vrstni_red++;
        }
    }

    /**
    * @desc popravi vrstne rede v calculationu
    */
    function repare_calculation ($condition) {

        $sql = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id='$condition' ORDER BY vrstni_red ASC");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);

        $vrstni_red=1;

        while ($row = mysqli_fetch_array($sql)) {
        	if ($row['vrstni_red'] != $vrstni_red)
            	$s = sisplet_query("UPDATE srv_calculation SET vrstni_red='$vrstni_red' WHERE id = '$row[id]'");
            $vrstni_red++;
        }
    }

    /**
    * @desc
    */
    function dropped_alert($text = null) {
        global $lang;

        if ($text === null)
        	$text = $lang['srv_dropped_alert'];

        echo '<div id="dropped_alert" style="display:none">';

        echo '  <h2>'.$lang['srv_warning'].'</h2>';
        echo '  <div class="popup_close"><a href="#" onClick="$(\'#dropped_alert\').hide(); $(\'#fade\').fadeOut(); return false;">✕</a></div>';

        echo '  <p>'.$text.'</p>';

        echo '</div>';

        echo '    <script type="text/javascript">                                                                           '."\n\r";
        echo '      $("#dropped_alert").fadeIn("fast").animate({opacity: 1.0}, 3000).fadeOut("slow");                                                                    '."\n\r";
        echo '    </script>                                                                                                 '."\n\r";
    }

	function showVprasalnikBottom() {

        echo '    <div id="bottom_icons_holder" >' . "\n\r";
		$this->showVprasalnikBottomContent();
		echo '    </div> <!-- /bottom_icons_holder -->' . "\n\r";
	}

	/**
	*
	* @param mixed $color
	*/
	function showVprasalnikBottomContent($color = 'orange') {
		global $lang, $site_url;

		$sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_branching WHERE ank_id='$this->anketa'");
		$row = mysqli_fetch_array($sql);

		if ($row['count'] > 0) {

			$d = new Dostop();

			echo '<div class="forma_bottom">';


			// Vprasalnik se shranjuje avt.
			echo '<div class="forma_bottom_inner changes">';
			echo '<table><tr><td><span class="faicon bottom_saving icon-blue" style="float:left; display:inline;"></span></td>';
			echo '<td>'.$lang['srv_vprasalnik_autosave'].'</td></tr></table>';
			echo '</div>';

			echo '<div class="forma_bottom_inner links">';

			// Preview
			echo '<a href="' . SurveyInfo::getSurveyLink() . '?preview=on" title="'.$lang['srv_poglejanketo'].'" target="_blank">';
			echo '<span class="faicon bottom_preview"></span> ';
			echo $lang['srv_poglejanketo2'];
			echo '</a>';

			// Testiranje
			if($this->survey_type != 0 && $this->survey_type != 1){
				if ($d->checkDostopSub('test')){
					echo '<a href="index.php?anketa='.$this->anketa.'&a=testiranje" title="'.$lang['srv_testiranje'].'">';
					echo '<span class="faicon bottom_test"></span> ';
					echo $lang['srv_testiranje'];
					echo '</a>';
				}
			}

			// Objava
			if ($d->checkDostopSub('publish')){
				echo '<a href="index.php?anketa=' . $this->anketa . '&amp;a=vabila" '.$lang['srv_vabila'].' title="'.$lang['srv_vabila'].'">';
				echo '<span class="faicon bottom_publish"></span> ';
				echo $lang['srv_vabila'];
				echo '</a>';
			}

			echo '</div>';


			echo '</div>';
		}
	}

	/**
	* @desc Če je anketa aktivna, preverimo da ni slučajno potekel čas aktivnosti,
	* če je, jo deaktiviramo
	*/
	function checkSurveyActive($anketa = null) {
		// pretecena anketa, kontroliramo datum na: starts in expire
		sisplet_query("UPDATE srv_anketa SET active = '0' WHERE id='" . ($anketa ? $anketa : $this->anketa) . "' AND active = '1' AND expire < CURDATE()");

		SurveyInfo :: getInstance()->SurveyInit($this->anketa);
		// vsilimo refresh podatkov
		SurveyInfo :: getInstance()->resetSurveyData();

		$sqls = sisplet_query("SELECT active FROM srv_anketa WHERE id='" . ($anketa ? $anketa : $this->anketa) . "'");
		$rows = mysqli_fetch_assoc($sqls);
		return $rows['active'];
	}

	/**
	* vrne array vseh spremenljivk, ki se pojavljajo v loopu
	*
	* @param mixed $ank_id
	*/
	function spremenljivke_in_loop ($anketa = null) {

		if ($anketa == null) {
			$ank_id = $this->anketa;
		} else {
			$ank_id = $anketa;
		}
		$a = array();

		$sql = sisplet_query("SELECT b.element_if FROM srv_branching b, srv_if i WHERE b.ank_id='$ank_id' AND b.element_if=i.id AND i.tip='2'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		while ($row = mysqli_fetch_array($sql)) {

			$a[$row['element_if']] = $this->get_subspr($row['element_if']);
		}

		return $a;
	}

	/**
	* preveri, ce obstajajo loopi in ce so PB na loopih pravilno postavljeni ter jih popravi
	* - pagebreak mora biti pred in za loopom, ter nobenega pb v loopu -- se pravi: loop je na svoji strani
	*/
	function check_loop () {

		$change = false;

		$a = $this->spremenljivke_in_loop();

		// loop cez vse loope :)
		foreach ($a AS $loop => $spremenljivke) {

			// spremenljivka pred loopom - mora imet pb
			$pred = $this->find_before_if($loop);
			$row = Cache::srv_branching($pred, 0);
			if ($row['pagebreak'] == 0) {
				sisplet_query("UPDATE srv_branching SET pagebreak = '1' WHERE element_spr = '$pred' AND ank_id='".$this->anketa."'");
				$change = true;
			}

			// zadnja spremenljivka v loopu
			$za = $this->find_last_in_if($loop);

			// loop cez spremenljivke v loopu
			foreach ($spremenljivke AS $spr) {

				if ($spr != $za) {
					// spremenljivke v loopu nimajo pb (razen zadnje)
					$row = Cache::srv_branching($spr, 0);
					if ($row['pagebreak'] == 1) {
						sisplet_query("UPDATE srv_branching SET pagebreak = '0' WHERE element_spr = '$spr'  AND ank_id='".$this->anketa."'");
						$change = true;
					}
				}

			}

			// zadnja spremenljivka v loopu - mora imet pb
			$row = Cache::srv_branching($za, 0);
			if ($row['pagebreak'] == 0) {
				sisplet_query("UPDATE srv_branching SET pagebreak = '1' WHERE element_spr = '$za'  AND ank_id='".$this->anketa."'");
				$change = true;
			}

		}

		if ($change) {
			$this->repare_vrstni_red();
        	$this->trim_grupe();
        	$this->pagebreak = array();
        	Cache::clear_branching_cache();
		}

	}

	/**
	* preveri, ce je podani element loop, ali pa ima parenta, ki je loop
	*
	*/
	function find_loop_parent ($parent) {

		if ($parent == 0) return 0;

		$sql = sisplet_query("SELECT id FROM srv_if WHERE id = '$parent' AND tip = '2'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0)
			return $parent;

		$sql = sisplet_query("SELECT parent FROM srv_branching WHERE element_spr = '0' AND element_if = '$parent' AND ank_id='$this->anketa'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		$row = mysqli_fetch_array($sql);
		return $this->find_loop_parent($row['parent']);

	}

	/**
	* preveri, ce je podani element loop, ali pa ima childa, ki je loop
	*
	*/
	function find_loop_child ($if) {

		$sql = sisplet_query("SELECT id FROM srv_if WHERE id = '$if' AND tip = '2'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0)
			return $if;

		$sql = sisplet_query("SELECT element_if FROM srv_branching WHERE parent = '$if' AND element_if > '0' AND ank_id='$this->anketa'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		while ($row = mysqli_fetch_array($sql)) {
			return $this->find_loop_child($row['element_if']);
		}

		return 0;
	}

	/**
	* poisce, ce ima podani element parenta, ki je loop
	*
	*/
	function find_parent_loop ($element_spr, $element_if=0) {

		$sql = sisplet_query("SELECT parent FROM srv_branching WHERE element_spr = '$element_spr' AND element_if = '$element_if' AND ank_id='$this->anketa'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		$row = mysqli_fetch_array($sql);

		if ($row['parent'] == 0) return 0;

		$sql = sisplet_query("SELECT id FROM srv_if WHERE id = '$row[parent]' AND tip = '2'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0)
			return $row['parent'];
		else
			return $this->find_parent_loop(0, $row['parent']);

	}

	function displayKomentarji($displayKomentarji = true) {
		$this->displayKomentarji = $displayKomentarji;
	}

	//funkcija za izris hotspot
	//radio grid
	function vprasanje_hotspot($spremenljivka, $tip, $enota_orientation){
		global $lang;

		$row = Cache::srv_spremenljivka($spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		$hotspot_image = ($spremenljivkaParams->get('hotspot_image') ? $spremenljivkaParams->get('hotspot_image') : "");
		
		//zaslon razdelimo na dva dela - izris leve strani***************************************
		//echo '<div id="half_hot_spot_1" style="width: 50%; class="grid_header_table '.($this->lang_id==null?'allow_new':'').'">';
		echo '<div id="half_hot_spot_1" class="hotspot" style="width: 40%; float: left;">';

						
		//$sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
		$sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$row[id]' AND other = 0 ORDER BY vrstni_red");
		$sql1_missing = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$row[id]' AND other != 0 ORDER BY vrstni_red");

		$sqlR = sisplet_query("SELECT * FROM srv_hotspot_regions WHERE spr_id='$row[id]' ");
	
		if($_GET['a'] == 'prevajanje'){ //ce se izvaja prevajanje
			if (mysqli_num_rows($sqlR) != 0){	//ce so obmocja

			//pokazi shranjena obmocja
				while ($rowR = mysqli_fetch_array($sqlR)) {
					
					//ureditev izbire imena obmocja glede na jezik ankete
					if ($this->lang_id != null) {
						save('lang_id', $this->lang_id);
						$naslovObmocja = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($rowR['vre_id']);
						if ($naslovObmocja == ''){
							$naslovObmocja = $rowR['region_name'];
						}
						//echo "naslovObmocja: ".$naslovObmocja;
					}
					
					echo '<div id="hotspot_region_'.$rowR['region_index'].'" class="hotspot_region"><div id="hotspot_region_name_'.$rowR['region_index'].'" vre_id="'.$rowR['vre_id'].'" region_index = "'.$rowR['region_index'].'" class="hotspot_vrednost_inline" contenteditable="true">'.$naslovObmocja.'</div><br /></div>';
				}
			}
		}
		else{		//ce ni prevajanje pokazi:
			//GUMB za nalaganje in urejanje slike
			if ( ($hotspot_image == '') || substr($hotspot_image, 0, 4) != '<img'){	//ce ni slike
				$hotspot_image_button_text = $lang['srv_hot_spot_load_image'];	//pokazi tekst za upload slike
			//}else{	//
			}else if(substr($hotspot_image, 0, 4) == '<img'){
				$hotspot_image_button_text = $lang['srv_hot_spot_edit_image'];	//drugace pokazi tekst za urejanje slike
			}					
			//echo '<p><span class="title" ><button id="hot_spot_regions_add_image_'.$row['id'].'" type="button" onclick=" hotspot_edit('.$row['id'].')">'.$hotspot_image_button_text.'</button></span></p>';
			echo '<p><span class="sprites image_upload pointer" onclick=" hotspot_edit('.$row['id'].')"></span></p>';
			
			//Slika
			echo '<div id="hotspot_image_'.$row['id'].'" class="vrednost_inline_hotspot"  contenteditable="false" spr_id="'.$row['id'].'">'.$hotspot_image.'</div>';
		}
	
		
		//skrita varianta od koder poberem height in width za urejanje obmocij
		//izbira ustreznega radio ali checkbox za prikazovanje ob missingu
		if ($tip == 1){
			$input = "radio";
		}
		else if ($tip == 2){
			$input = "checkbox";
		}
		
		echo '<div id="hotspot_image_'.$row['id'].'_hidden" style="display: none;" class="vrednost_inline_hotspot" contenteditable="false" spr_id="'.$row['id'].'">'.$hotspot_image.'</div>';

		echo '<div id="hotspot_regions_hidden_menu_'.$row['id'].'">';

        if (mysqli_num_rows($sql1) == 0){
			echo '        <div class="variabla">';
			echo '</div>';
		}
		else{
			while ($row1 = mysqli_fetch_array($sql1)) {
				
				echo '        <div style="display:none;" other="'.$row1['other'].'" class="variabla" id="variabla_'.$row1['id'].'">';
				echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'].'</div>';
				echo '</div>';
			}
			while ($row1_missing = mysqli_fetch_array($sql1_missing)) {
				
				echo '<div  class="variabla" other="'.$row1_missing['other'].'" id="variabla_'.$row1_missing['id'].'"><input disabled type="'.$input.'">';
				echo '<div id="vre_id_'.$row1_missing['id'].'" class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1_missing['id'].'" '.(strpos($row1_missing['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1_missing['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1_missing['naslov'].'</div>';
				echo '</div>';
			}
		}
				
		echo '</div>';

				
		?>
		<script>
			$(document).ready(function(){					
				//resize slike ob razlicnih dogodkih
				hotspot_image_resize(<?=$row['id']?>);
				//show_hot_spot_settings (<?=$row['id']?>, <?=$row['enota']?>, <?=$row['tip']?>);
				//hotspot_image_button_update(<?=$row['id']?>, '<?=$lang['srv_hot_spot_load_image']?>', '<?=$lang['srv_hot_spot_edit_image']?>');
				
				$("#hotspot_image_<?=$row['id']?>")
					.mouseup(function(){//ko prst dvignemo iz miskine tipke
						hotspot_image_resize(<?=$row['id']?>); //
						//show_hot_spot_settings (<?=$row['id']?>, <?=$row['enota']?>, <?=$row['tip']?>);
				})						
					.mouseover(function(){//ko z misko gremo mimo
						hotspot_image_resize(<?=$row['id']?>); //
						//show_hot_spot_settings (<?=$row['id']?>, <?=$row['enota']?>, <?=$row['tip']?>);
				})
					.mouseout(function(){//ko z misko gremo ven
						hotspot_image_resize(<?=$row['id']?>); //
						//show_hot_spot_settings (<?=$row['id']?>, <?=$row['enota']?>, <?=$row['tip']?>);
				})
				
			});					
		</script>
		<?
			
		echo '      </div>';
		//************************* Izris leve strani - konec
		
		//************** Izris desne strani za grid
        if($tip == 6){	//ce je radio grid izrisi desno stran			
            
			echo '<div id="half_hot_spot_2" style="width: 60%; float: right;">';
			echo '      <table class="grid_header_table '.($this->lang_id==null?'allow_new':'').'">';
			echo '        <thead>';

			// urejanje vrednosti
			echo '        <tr id="grid_variable_'.$row['id'].'" '.$show_variable_row.'>';
			echo '          <td></td>';

			$bg = 1;

			$sql2 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row[id]' AND other=0 ORDER BY vrstni_red");
			$row2 = mysqli_fetch_array($sql2);

			for ($i = 1; $i <= $row['grids']; $i++) {
				if ($row2['vrstni_red'] == $i) {
					echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_variable_inline" '.$show_variable_inline.' contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'">' . $row2['variable'] . '</div></td>';
					$row2 = mysqli_fetch_array($sql2);
				} else {
					echo '          <td class=" ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"></td>';
				}
			}
			echo '</tr>';

			echo '        <tr>';
			echo '          <td>'.$grid_plus_minus.'</td>';

			$bg = 1;

			$sql2 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row[id]' AND other=0 ORDER BY vrstni_red");
			$row2 = mysqli_fetch_array($sql2);

			for ($i = 1; $i <= $row['grids']; $i++) {
				if ($this->lang_id != null) {
					$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row2['id']);
					if ($naslov != '') $row2['naslov'] = $naslov;
				}
				if ($row2['vrstni_red'] == $i) {
					echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row2['id'].'"><div class="grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row2['id'].'" '.(strpos($row2['naslov'], $lang['srv_new_grid'])!==false || strpos($row2['naslov'], $lang1['srv_new_grid'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row2['naslov'] . '</div></td>';
					$row2 = mysqli_fetch_array($sql2);
				} else {
					echo '          <td class="grid_header ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '"></td>';
				}
			}

			#kateri missingi so nastavljeni
			$sql_grid_mv = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='".$spremenljivka."' AND other != 0");
			if (mysqli_num_rows($sql_grid_mv) > 0 ) {
				echo '<td class=""></td>';
				while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
					if ($this->lang_id != null) {
						$naslov = \App\Controllers\LanguageController::srv_language_grid($row['id'], $row_grid_mv['id']);
						if ($naslov != '') $row_grid_mv['naslov'] = $naslov;
					}
					
					echo '<td class="grid_header11 ' . ($bg % 2 == 0 ? 'grid_light' : 'grid_dark') . '" grd="g_'.$row_grid_mv['id'].'"><div class="grid_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" grd_id="'.$row_grid_mv['id'].'" '.(strpos($row_grid_mv['naslov'], $lang['srv_new_grid'])!==false || $this->lang_id!=null ?' default="1"':'').'>' . $row_grid_mv['naslov'] . '</div></td>';

				}

			}
			echo '<td style="width:' . $spacesize*3 . '%"></td>';
			echo '        </tr>';

			echo '</thead>';
			echo '<tbody class="'.($this->lang_id==null?'allow_new':'').'">';

			$bg++;


			//$sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
			$sql1 = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='$row[id]'  ORDER BY vrstni_red");
			while ($row1 = mysqli_fetch_array($sql1)) {

				if ($this->lang_id != null) {
					save('lang_id', $this->lang_id);
					$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($row1['id']);
					if ($naslov != '') $row1['naslov'] = $naslov;
				}

				$bg++;
			}

			echo '      </tbody>';
			
			echo '      </table>';
			echo '      </div>';
			
		}
		//************** Izris desne strani za grid - konec
		
		
				//************** Izris desne strani za image hotspot razvrscanje
		if($tip == 17 || $row['design'] == 3){	//ce je razvrscanje z image hotspot izrisi desno stran
			echo '<div id="half_hot_spot_2" style="width: 60%; float: right;">';
			echo '      <table class="grid_header_table '.($this->lang_id==null?'allow_new':'').'">';
			echo '        <thead>';

			// urejanje vrednosti
			echo '        <tr id="grid_variable_'.$row['id'].'" '.$show_variable_row.'>';
			echo '          <td></td>';

			$bg = 1;

			$sql2 = sisplet_query("SELECT * FROM srv_hotspot_regions WHERE spr_id='$row[id]' ORDER BY vrstni_red");
			$row2 = mysqli_fetch_array($sql2);
			
			for($indeks=1;$indeks <= mysqli_num_rows($sql2);$indeks++){
				echo '<td grd="g_'.$row2['id'].'"><div class="grid_inline" contenteditable="false" tabindex="1" grd_id="'.$row2['id'].'">' . $row2['vrstni_red'] . '</div></td>';
				$row2 = mysqli_fetch_array($sql2);
			}
			echo '</tr>';

			echo '        <tr>';
			echo '          <td>'.$grid_plus_minus.'</td>';

			$bg = 1;
        
			echo '<td style="width:' . $spacesize*3 . '%"></td>';
			echo '        </tr>';

			echo '</thead>';
			
			echo '      </table>';
			echo '      </div>';
			
		}
		//************** Izris desne strani za image hotspot razvrscanje - konec
		
		
		//***********************Image hot spot konec*************************************************************
	}
	
	//funkcija za izris heatmap
	//radio grid
	function vprasanje_heatmap($spremenljivka, $tip){
		global $lang;

		$row = Cache::srv_spremenljivka($spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		$hotspot_image = ($spremenljivkaParams->get('hotspot_image') ? $spremenljivkaParams->get('hotspot_image') : "");
		$spremenljivkaParams->set('multi_input_type', 'marker');
		
		echo '<div id="half_hot_spot_1" class="hotspot" style="width: 40%; float: left;">';

			$sql1 = sisplet_query("SELECT id, naslov, other FROM srv_vrednost WHERE spr_id='$row[id]' AND other = 0 ORDER BY vrstni_red");
			$sql1_missing = sisplet_query("SELECT id, naslov, other FROM srv_vrednost WHERE spr_id='$row[id]' AND other != 0 ORDER BY vrstni_red");
		
			//GUMB za nalaganje in urejanje slike
			if ( ($hotspot_image == '') || substr($hotspot_image, 0, 4) != '<img'){	//ce ni slike
				$hotspot_image_button_text = $lang['srv_hot_spot_load_image'];	//pokazi tekst za upload slike
            }
            else if(substr($hotspot_image, 0, 4) == '<img'){
				$hotspot_image_button_text = $lang['srv_hot_spot_edit_image'];	//drugace pokazi tekst za urejanje slike
			}					

            echo '<p><span class="sprites image_upload pointer" onclick=" hotspot_edit('.$row['id'].')"></span></p>';
			
			echo '<div id="hotspot_image_'.$row['id'].'" class="vrednost_inline_hotspot"  contenteditable="false" spr_id="'.$row['id'].'">'.$hotspot_image.'</div>';
			
			
			//skrita varianta od koder poberem height in width za urejanje obmocij
			//izbira ustreznega radio ali checkbox za prikazovanje ob missingu
			$input = "checkbox";
			
			echo '<div id="hotspot_image_'.$row['id'].'_hidden" style="display: none;" class="vrednost_inline_hotspot" contenteditable="false" spr_id="'.$row['id'].'">'.$hotspot_image.'</div>';
	
            echo '<div id="hotspot_regions_hidden_menu_'.$row['id'].'">';
            
            if (mysqli_num_rows($sql1) == 0){
                echo '<div class="variabla">';
                echo '</div>';
            }
            else{
                while ($row1 = mysqli_fetch_array($sql1)) {
                    
                    echo '<div style="display:none;" other="'.$row1['other'].'" class="variabla" id="variabla_'.$row1['id'].'">';
                        echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'].'</div>';
                    echo '</div>';
                }
                while ($row1_missing = mysqli_fetch_array($sql1_missing)) {								
                    echo '<div  class="variabla" other="'.$row1_missing['other'].'" id="variabla_'.$row1_missing['id'].'"><input disabled type="'.$input.'">';
                        echo '<div id="vre_id_'.$row1_missing['id'].'" class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1_missing['id'].'" '.(strpos($row1_missing['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1_missing['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1_missing['naslov'].'</div>';
                    echo '</div>';
                }
            }					
			echo '</div>';
								
			?>
			<script>
				$(document).ready(function(){							
					//resize slike ob razlicnih dogodkih
					hotspot_image_resize(<?=$row['id']?>);
					//show_hot_spot_settings (<?=$row['id']?>, <?=$row['enota']?>, <?=$row['tip']?>);
					//hotspot_image_button_update(<?=$row['id']?>, '<?=$lang['srv_hot_spot_load_image']?>', '<?=$lang['srv_hot_spot_edit_image']?>');
					
					$("#hotspot_image_<?=$row['id']?>")
						.mouseup(function(){//ko prst dvignemo iz miskine tipke
							hotspot_image_resize(<?=$row['id']?>); //
							//show_hot_spot_settings (<?=$row['id']?>, <?=$row['enota']?>, <?=$row['tip']?>);
					})						
						.mouseover(function(){//ko z misko gremo mimo
							hotspot_image_resize(<?=$row['id']?>); //
							//show_hot_spot_settings (<?=$row['id']?>, <?=$row['enota']?>, <?=$row['tip']?>);
					})
						.mouseout(function(){//ko z misko gremo ven
							hotspot_image_resize(<?=$row['id']?>); //
							//show_hot_spot_settings (<?=$row['id']?>, <?=$row['enota']?>, <?=$row['tip']?>);
					})
					
				});					
			</script>
			<?
		echo '      </div>';
		//***********************Heatmap konec*************************************************************
	}
    
    
    // Vrnemo string orientacije za vprasanje
    private function getVprasanjeOrientationString($type, $orientation){
        global $lang;

        // Prva stevilka je tip vprasanja, druga tip orientacije
        switch($type.'-'.$orientation){

            case '1-0':
            case '2-0':
            case '21-0':
            case '7-0':
            case '8-0':
                $orientation_string = $lang['srv_orientacija_horizontalna'];
            break;
            
            case '1-2':
            case '2-2':
            case '21-2':
                $orientation_string = $lang['srv_orientacija_horizontalna_2'];
            break;

            case '21-3':
                $orientation_string = $lang['srv_orientacija_vertikalna'];
            break;

            case '1-4':
            case '3-1':
            case '6-2':
                $orientation_string = $lang['srv_dropdown'];
            break;

            case '1-6':
            case '2-6':
                $orientation_string = $lang['srv_select-box_radio'];
            break;

            case '1-7':
            case '2-7':
                $orientation_string = $lang['srv_orientacija_vertikalna_2'];
            break;

            case '1-8':
            case '2-8':
                $orientation_string = $lang['srv_drag_drop'];
            break;

            case '1-9':
            case '6-12':
                $orientation_string = $lang['srv_custom-picture_radio'];
            break;

            case '1-10':
            case '2-10':
            case '6-10':
                $orientation_string = $lang['srv_hot_spot'];
            break;

            case '1-11':
            case '6-11':
                $orientation_string = $lang['srv_visual_analog_scale'];
            break;


            case '6-1':
                $orientation_string = $lang['srv_diferencial2'];
            break;

            case '6-3':
            case '16-3':
                $orientation_string = $lang['srv_double_grid'];
            break;

            case '6-4':
                $orientation_string = $lang['srv_one_against_another'];
            break;

            case '6-5':
                $orientation_string = $lang['srv_max_diff'];
            break;

            case '6-6':
            case '16-6':
                $orientation_string = $lang['srv_select-box_radio'];
            break;

            case '6-8':
                $orientation_string = $lang['srv_orientacija_tabela_da_ne'];
            break;

            case '6-9':
            case '16-9':
                $orientation_string = $lang['srv_drag_drop'];
            break;

            default:
                $orientation_string = '';
            break;
        }

        return $orientation_string;
    }
}

?>
