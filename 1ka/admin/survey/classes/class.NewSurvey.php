<?php

/*
 *  Prikaz vmesnika za ustvarjanje nove ankete 
 * 
 *	(ce uporabnik klikne "nova anketa" ali ce se nima nobene ankete)
 *
 */

use Hierarhija\HierarhijaHelper;

class NewSurvey{

	private $subpage;				// Podstran na kateri se nahajamo (nova anketa, iz predloge, uvoz)
	
	private $template_category;		// Podstran kategorij predlog na kateri se nahajamo (vse predloge, organizacija dogodka...)
	private $templates;				// Array vseh templatov

	function __construct(){
		global $site_url;

		$this->subpage = (isset($_GET['b'])) ? $_GET['b'] : 'new';
		$this->template_category = (isset($_GET['c'])) ? $_GET['c'] : '0';
	}
	
	
	// Izris strani za ustvarjanje ankete
	public function displayNewSurveyPage(){
		global $lang;
        global $site_url;
        global $site_path;
				
		// Leva stran - meni
		echo '<div id="left_menu">';
		$this->displayLeftMenu();
		echo '</div>';
			
		// Desna stran - vsebina
		echo '<div id="right_content">';
		$this->displayRightContent();
		echo '</div>';
	}
	
	// Izris levega menija
	private function displayLeftMenu(){
		global $lang;
        global $site_url;
        global $site_path;
        global $virtual_domain;
        global $debug;
        global $admin_type;
		
		echo '<div class="title">'.$lang['srv_newSurvey_title'].':</div>';
		
		// Nova anketa
		echo '<a href="'.$site_url.'admin/survey/index.php?a=ustvari_anketo&b=new" title="'.$lang['srv_newSurvey_survey_new2'].'"><span class="item '.($this->subpage == 'new' ? ' active' : '').'" onClick="">'.$lang['srv_newSurvey_survey_new'].'</span></a>';
		
		// Anketa iz predloga (knjiznice) - samo na www.1ka.si, testu in arnesu
		if((strpos($site_url, 'www.1ka.si') !== false && !$virtual_domain) 
				|| strpos($site_url, 'test.1ka.si')
				|| strpos($site_url, '1ka.arnes.si')
				|| $debug == '1'){
			
			echo '<a href="'.$site_url.'admin/survey/index.php?a=ustvari_anketo&b=template" title="'.$lang['srv_newSurvey_survey_template2'].'"><span class="item '.($this->subpage == 'template' ? ' active' : '').'">'.$lang['srv_newSurvey_survey_template'].'</span></a>';
			
			if($this->subpage == 'template'){
				echo '<ul>';		
				for($i=0; $i<=10; $i++){
					echo '<li><a href="'.$site_url.'admin/survey/index.php?a=ustvari_anketo&b=template&c='.$i.'" title="'.$lang['srv_newSurvey_survey_template_cat'.$i].'"><span class="subitem '.($this->subpage == 'template' && $this->template_category == $i ? ' active' : '').'">'.$lang['srv_newSurvey_survey_template_cat'.$i].'</span></a></li>';
				}
				echo '</ul>';
			}
		}
		
		// Kopiraj mojo anketo
		echo '<a href="'.$site_url.'admin/survey/index.php?a=ustvari_anketo&b=copy" title="'.$lang['srv_newSurvey_survey_copy'].'"><span class="item '.($this->subpage == 'copy' ? ' active' : '').'">'.$lang['srv_newSurvey_survey_copy'].'</span></a>';
		
		// Uvoz ankete
		echo '<a href="'.$site_url.'admin/survey/index.php?a=ustvari_anketo&b=archive" title="'.$lang['srv_newSurvey_survey_archive2'].'"><span class="item '.($this->subpage == 'archive' ? ' active' : '').'">'.$lang['srv_newSurvey_survey_archive'].'</span></a>';

		// Anketa iz besedila
		echo '<a href="'.$site_url.'admin/survey/index.php?a=ustvari_anketo&b=from_text" title="'.$lang['srv_newSurvey_survey_from_text'].'"><span class="item '.($this->subpage == 'from_text' ? ' active' : '').'">'.$lang['srv_newSurvey_survey_from_text'].'</span></a>';

		if(HierarhijaHelper::aliImaDostopDoIzdelovanjaHierarhije()) {
            echo '<a href="'.$site_url.'admin/survey/index.php?a=ustvari_anketo&b=hierarhija" title="'.$lang['srv_hierarchy'].'"><span class="item hierarhija-button '.($this->subpage == 'hierarhija' ? ' active' : '').'">'.$lang['srv_hierarchy'].'</span></a>';
        }
	}
	
	// Izris desne vsebine
	private function displayRightContent(){
        global $global_user_id;

        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
        $userAccess = UserAccess::getInstance($global_user_id);
        if(!$userAccess->checkUserAccess()){
            $userAccess->displayNoAccess();
            return;
        }

		// Anketa iz predloge
		if($this->subpage == 'template')
			$this->displayRightContentTemplates();
		// Kopiranje iz moje ankete
		elseif($this->subpage == 'copy')
			$this->displayRightContentCopy();
		// Anketa iz arhiva (uvoz)
		elseif($this->subpage == 'archive')
			$this->displayRightContentArchive();
		// Hierarhija
        elseif($this->subpage == 'hierarhija')
            $this->displayRightContentHierarhija();
		// Iz besedila
        elseif($this->subpage == 'from_text')
            $this->displayRightContentFromText();
		// Nova anketa
		else
			$this->displayRightContentNew();
	}
	
	
	// Vsebina za ustvarjanje nove ankete
	private function displayRightContentNew(){
		global $lang;
        global $site_url;
        global $site_path;
		global $global_user_id;
		
		
		// 1. sklop - ime ankete
		echo '<div class="fieldset">';	

		// Naslov
		echo '<div class="title">' . $lang['srv_noSurvey_settings'] . '</div>';

		echo '<div class="survey_title_text_holder">';
		
        // Interno ime
        echo '<div class="setting survey_title_text">';
        echo '<span class="label" style="float:left; width:180px;">' . $lang['srv_list_no_data_create'] . ':</span>';
        echo '<input type="text" id="novaanketa_naslov_1" name="novaanketa_naslov_1" placeholder="' . $lang['srv_novaanketa_polnoime'] . '" value="' . $lang['srv_novaanketa_polnoime'] . '" size="40" maxlength="' . ANKETA_NASLOV_MAXLENGTH . '"  onfocus="if(this.value==\'' . $lang['srv_novaanketa_polnoime'] . '\') {this.value=\'\';}" />';
        echo '<span id="novaanketa_naslov_1_chars">0 / ' . ANKETA_NASLOV_MAXLENGTH . '</span>';
        echo '</div>';

        // Objavljeno ime
        echo '<div class="setting survey_title_text">';
        echo '<span class="label" style="float:left; width:180px;">' . $lang['srv_novaanketa_kratkoime'] . ':</span>';
        echo '<input type="text" id="novaanketa_akronim_1" name="novaanketa_akronim_1" value="' . $lang['srv_novaanketa_ime_respondenti'] . '" placeholder="' . $lang['srv_novaanketa_ime_respondenti'] . '" size="40" maxlength="' . ANKETA_AKRONIM_MAXLENGTH . '"  onfocus="$(this).attr(\'changed\',\'1\'); if(this.value==\'' . $lang['srv_novaanketa_ime_respondenti'] . '\') {this.value=\'\';}" changed="0" />';
        echo '<span id="novaanketa_akronim_1_chars">0 / ' . ANKETA_AKRONIM_MAXLENGTH . '</span>';
        echo '</div>';
		
		// Ce ima uporabnik mape, lahko izbere v katero mapo se anketa uvrsti
		UserSetting::getInstance()->Init($global_user_id);
		$show_folders = UserSetting::getInstance()->getUserSetting('survey_list_folders');
		$sql = sisplet_query("SELECT * FROM srv_mysurvey_folder WHERE usr_id='$global_user_id' ORDER BY naslov ASC");
		if($show_folders == 1 && mysqli_num_rows($sql) > 0){
			echo '<div class="setting survey_title_text">';
			echo '<span class="label" style="float:left; width:180px;">' . $lang['srv_newSurvey_survey_new_folder'] . ':</span>';
			
			echo '<select name="novaanketa_folder" id="novaanketa_folder">';
			echo '<option value="0">'.$lang['srv_newSurvey_survey_new_folder_def'].'</option>';
			while($row = mysqli_fetch_array($sql)){
				echo '<option value="'.$row['id'].'">'.$row['naslov'].'</option>';
			}
			echo '</select>';
			
			echo '</div>';
		}
		
		echo '</div>';
		echo '</div>';
		

        // 2. sklop - tip ankete (navadna, forma, glasovanje)
        echo '<div class="fieldset">';

        echo '<div class="title">' . $lang['srv_noSurvey_type'] . '</div>';

		echo '<div class="survey_type_holder">';
		
        // Navadna anketa
		echo '<label for="newAnketaBlank_2"><div class="setting survey_type active">';
        echo '<input type="radio" name="newAnketaBlank" id="newAnketaBlank_2" value="2" checked="checked" onClick="$(\'.survey_type\').removeClass(\'active\'); $(this).parent().parent().find(\'.survey_type\').addClass(\'active\');" />';
        echo '<span class="radioSetting_type">';
		echo '	<div class="survey_type_title">' . $lang['srv_vrsta_survey_type_2']  . Help::display('srv_create_survey'). '</div>';
		echo '	<div class="survey_type_text">' . $lang['srv_noSurvey_type_2'] . '</div>';
		echo '</span>';
        echo '</div></label>';

        // Forma
		echo '<label for="newAnketaBlank_1"><div class="setting survey_type">';
        echo '<input type="radio" name="newAnketaBlank" id="newAnketaBlank_1" value="1" onClick="$(\'.survey_type\').removeClass(\'active\'); $(this).parent().parent().find(\'.survey_type\').addClass(\'active\');" />';
		echo '<span class="radioSetting_type">';
		echo '	<div class="survey_type_title">' . $lang['srv_vrsta_survey_type_1']  . Help::display('srv_create_form'). '</div>';
		echo '	<div class="survey_type_text">' . $lang['srv_noSurvey_type_1'] . '</div>';
		echo '</span>';
		echo '</div></label>';

        // Glasovanje
		echo '<label for="newAnketaBlank_0"><div class="setting survey_type">';
        echo '<input type="radio" name="newAnketaBlank" id="newAnketaBlank_0" value="0" onClick="$(\'.survey_type\').removeClass(\'active\'); $(this).parent().parent().find(\'.survey_type\').addClass(\'active\');" />';
		echo '<span class="radioSetting_type">';
		echo '	<div class="survey_type_title">' . $lang['srv_vrsta_survey_type_0']  . Help::display('srv_create_poll'). '</div>';
		echo '	<div class="survey_type_text">' . $lang['srv_noSurvey_type_0'] . '</div>';
		echo '</span>';
		echo '</div></label>';

        echo '</div>';
        echo '</div>';


        // 3. sklop - skin ankete
        echo '<div class="fieldset noSurvey_skin">';

        echo '<span class="title">' . $lang['srv_noSurvey_skin'] . ' ' . Help::display('srv_choose_skin') . '</span>';

		// Gorenje ima svoj default skin
		if(Common::checkModule('gorenje'))
			echo '<input type="hidden" name="skin" id="noSurvey_skin_id" value="GorenjeGroup" /><br /><br />';
		else
			echo '<input type="hidden" name="skin" id="noSurvey_skin_id" value="1kaBlue" /><br /><br />';

		// Puscica levo
        echo '<a href="#" onClick="scroll_noSurvey_skin(\'left\'); return false;"><span id="skin_arrow_left" class="faicon arrow_verylarge2_l"></span></a>';

        echo '<div id="skins_holder"><div id="noSurvey_skins">';

		// Gorenje ima samo 1 skin
		if(Common::checkModule('gorenje')){
			echo '<div class="skin selected" id="skin_GorenjeGroup">';

			echo 'GorenjeGroup (' . $lang['default'] . ')';

			echo '<div class="preview">';
			echo '<img src="' . $site_url . 'public/img/skins_previews/Gorenje.png">';
			echo '</div>';

			echo '</div>';
		}
		else{
			// Loop cez vse skine (zaenkrat samo sistemske)
			$st = new SurveyTheme();
			$skins = $st->getGroups();
			foreach ($skins['0']['skins'] as $key => $skin) {

				$simple_name = preg_replace("/\.css$/", '', $skin);

				// Default skin po novem izkljucimo
				if ($simple_name != 'Default') {
					echo '<div class="skin ' . ($simple_name == '1kaBlue' ? ' selected' : '') . '" id="skin_' . $simple_name . '" onClick="change_noSurvey_skin(\'' . $simple_name . '\');">';

					echo '<div class="preview">';
					echo '<img src="' . $site_url . 'public/img/skins_previews/' . urlencode($simple_name) . '.png">';
					echo '</div>';
					
					echo $simple_name . ($simple_name == '1kaBlue' ? ' (' . $lang['default'] . ')' : '');

					// Vprasajcki
					if ($simple_name == 'Embed' || $simple_name == 'Embed2' || $simple_name == 'Fdv' || $simple_name == 'Uni' || $simple_name == 'Slideshow')
						echo ' ' . Help:: display('srv_skins_' . $simple_name);

					echo '</div>';
				}
			}
		}
		
        echo '</div></div>';

		// Puscica desno
        echo '<a href="#" onClick="scroll_noSurvey_skin(\'right\'); return false;"><span id="skin_arrow_right" class="faicon arrow_verylarge2_r"></span></a>';

        echo '</div>';


        // Gumba naprej in preklici
        echo '<div class="noSurvey_buttons">';

        echo '<a href="' . $site_url . 'admin/survey/index.php" title="' . $lang['srv_cancel'] . '"><span id="noSurvey_cancel">' . $lang['srv_cancel'] . '</span></a>';
        echo '<a href="#" onclick="newAnketaBlank();" title="' . $lang['srv_create_survey'] . '"><span id="noSurvey_create">' . $lang['next1'] . '</span></a>';

        echo '</div>';
	}
	
	
	// Vsebina za ustvarjanje ankete iz predloge
	private function displayRightContentTemplates(){
		global $lang;
        global $site_url;
        global $site_path;
		global $global_user_id;	
		
		$this->setTemplates();
				
		
		// 1. sklop - ime ankete
		echo '<div class="fieldset">';	

		// Naslov
		echo '<div class="title">' . $lang['srv_noSurvey_settings'] . '</div>';

		echo '<div class="survey_title_text_holder">';
		
        // Interno ime
        echo '<div class="setting survey_title_text">';
        echo '<span class="label" style="float:left; width:180px;">' . $lang['srv_list_no_data_create'] . ':</span>';
        echo '<input type="text" id="novaanketa_naslov_1" name="novaanketa_naslov_1" placeholder="' . $lang['srv_novaanketa_polnoime'] . '" value="' . $lang['srv_novaanketa_polnoime'] . '" size="40" maxlength="' . ANKETA_NASLOV_MAXLENGTH . '"  onfocus="if(this.value==\'' . $lang['srv_novaanketa_polnoime'] . '\') {this.value=\'\';}" />';
        echo '<span id="novaanketa_naslov_1_chars">0 / ' . ANKETA_NASLOV_MAXLENGTH . '</span>';
        echo '</div>';

        // Objavljeno ime
        echo '<div class="setting survey_title_text">';
        echo '<span class="label" style="float:left; width:180px;">' . $lang['srv_novaanketa_kratkoime'] . ':</span>';
        echo '<input type="text" id="novaanketa_akronim_1" name="novaanketa_akronim_1" value="' . $lang['srv_novaanketa_ime_respondenti'] . '" placeholder="' . $lang['srv_novaanketa_ime_respondenti'] . '" size="40" maxlength="' . ANKETA_AKRONIM_MAXLENGTH . '"  onfocus="$(this).attr(\'changed\',\'1\'); if(this.value==\'' . $lang['srv_novaanketa_ime_respondenti'] . '\') {this.value=\'\';}" changed="0" />';
        echo '<span id="novaanketa_akronim_1_chars">0 / ' . ANKETA_AKRONIM_MAXLENGTH . '</span>';
        echo '</div>';
		
		// Ce ima uporabnik mape, lahko izbere v katero mapo se anketa uvrsti
		UserSetting::getInstance()->Init($global_user_id);
		$show_folders = UserSetting::getInstance()->getUserSetting('survey_list_folders');
		$sql = sisplet_query("SELECT * FROM srv_mysurvey_folder WHERE usr_id='$global_user_id' ORDER BY naslov ASC");
		if($show_folders == 1 && mysqli_num_rows($sql) > 0){
			echo '<div class="setting survey_title_text">';
			echo '<span class="label" style="float:left; width:180px;">' . $lang['srv_newSurvey_survey_new_folder'] . ':</span>';
			
			echo '<select name="novaanketa_folder" id="novaanketa_folder">';
			echo '<option value="0">'.$lang['srv_newSurvey_survey_new_folder_def'].'</option>';
			while($row = mysqli_fetch_array($sql)){
				echo '<option value="'.$row['id'].'">'.$row['naslov'].'</option>';
			}
			echo '</select>';
			
			echo '</div>';
		}
		
		echo '</div>';
		echo '</div>';
		
		
		// 2. sklop - izbira predloge
        echo '<div class="fieldset noSurvey_template">';

        echo '<div class="title">' . $lang['srv_newSurvey_survey_template_cat'.$this->template_category] . '</div>';
		
		echo '<input type="hidden" name="noSurvey_template_id" id="noSurvey_template_id" value="">';
			
		// Prikaz predlog
		foreach($this->templates as $template_id => $template_name){				
			$this->displayRightContentTemplate($template_id);
		}
		
        echo '</div>';

		
		// Gumba naprej in preklici
        echo '<div class="noSurvey_buttons">';

        echo '<a href="' . $site_url . 'admin/survey/index.php" title="' . $lang['srv_cancel'] . '"><span id="noSurvey_cancel">' . $lang['srv_cancel'] . '</span></a>';
        echo '<a href="#" onclick="newAnketaTemplate();" title="' . $lang['srv_create_survey'] . '"><span id="noSurvey_create">' . $lang['next1'] . '</span></a>';

        echo '</div>';
	}
	
	// Pripravimo podatke o vseh predlogah na trenutni strani
	private function setTemplates(){
		global $lang;

		// Pridobimo seznam templatov anket za izbrano kategorijo in jezik
		$lang_str = ($lang['id'] == '1') ? '_slo' : '_eng';
		$cat_str = ($this->template_category == '0') ? '' : ' AND kategorija=\''.$this->template_category.'\'';

		$sql = sisplet_query("SELECT kategorija, ank_id".$lang_str." AS ank_id, naslov".$lang_str." AS naslov, desc".$lang_str." AS opis
								FROM srv_anketa_template
								WHERE ank_id".$lang_str.">0 ".$cat_str."");							
		while($row = mysqli_fetch_array($sql)){
				
			SurveyInfo::getInstance()->SurveyInit($row['ank_id']);
			$survey_type = SurveyInfo::getInstance()->getSurveyColumn('survey_type');

			if($survey_type === '0')
				$survey_type_str = $lang['srv_vrsta_survey_type_0'];
			elseif($survey_type === '1')
				$survey_type_str = $lang['srv_vrsta_survey_type_1'];
			else
				$survey_type_str = $lang['srv_vrsta_survey_type_2'];
			
			$this->templates[$row['ank_id']] = array(
				'naslov' 	=> $row['naslov'],
				'tip' 		=> $survey_type_str,
				'opis' 		=> $row['opis']
			);
		}
	}
	
	// Prikazemo posamezen element predloge
	private function displayRightContentTemplate($template_id){
		global $lang;
		global $site_url;
		
		$template_data = $this->templates[$template_id];
		
		echo '<div class="template" id="template_'.$template_id.'" onClick="newAnketaTemplate_change(\''.$template_id.'\');">';

		// Naslov
		echo '<input type="hidden" name="template_title_'.$template_id.'" id="template_title_'.$template_id.'" value="'.$template_data['naslov'].'">';
		

		// Vsebina
		echo '<div class="template_content">';
		echo '<div class="template_content_white">';
		echo $lang['srv_newSurvey_survey_template_type'].': <span class="bold">'.$template_data['tip'].'</span>';
		echo '<br /><br /><br />';
		if($template_data['opis'] != '')
			echo (strlen($template_data['opis']) > 155) ? substr($template_data['opis'], 0, 152).'...' : $template_data['opis'];
		else
			echo $template_data['naslov'];

		echo '</div>';
		
		echo '<span class="template_title">'.$template_data['naslov'].'</span>';
		
		echo '</div>';

		// Predogled
		echo '<a href="'.$site_url.'a/'.$template_id.'?preview=on&no_preview=1&size=full" target="_blank" class="template_preview"><span class="faicon preview"></span> '.$lang['srv_newSurvey_survey_template_preview'].'</a>';

		echo '</div>';
	}
	
	
	// Vsebina za ustvarjanje ankete iz predloge
	private function displayRightContentCopy(){
		global $lang;
        global $site_url;
        global $site_path;
        global $global_user_id;
		
		 // 1. sklop - tip ankete (navadna, forma, glasovanje)
        echo '<div class="fieldset">';

        echo '<div class="title">'.$lang['srv_newSurvey_survey_copy_title'].'</div>';
		
		//echo '<input placeholder="'.$lang['srv_newSurvey_survey_copy_text'].'" list="my_surveys" data-list-focus="true" style="width:300px;">';
		//echo '<datalist id="my_surveys">';
		echo '<select name="my_surveys" id="my_surveys">';
		// Pridobimo seznam obstoječih anket
		$sql = sisplet_query("SELECT id, naslov, akronim FROM srv_anketa WHERE insert_uid='".$global_user_id."' ORDER BY naslov ASC");
		while($row = mysqli_fetch_array($sql)){
			echo '	<option value="'.$row['id'].'">'.$row['naslov'].'</option>';
		}
		//echo '</datalist>';
		echo '</select>';

        echo '</div>';

		
		// Gumba naprej in preklici
        echo '<div class="noSurvey_buttons">';

        echo '<a href="' . $site_url . 'admin/survey/index.php" title="' . $lang['srv_cancel'] . '"><span id="noSurvey_cancel">' . $lang['srv_cancel'] . '</span></a>';
        echo '<a href="#" onclick="newAnketaCopy();" title="' . $lang['srv_create_survey'] . '"><span id="noSurvey_create">' . $lang['next1'] . '</span></a>';

        echo '</div>';
	}
	
	// Vsebina za uvažanje ankete iz arhiva
	private function displayRightContentArchive(){
		global $lang;
        global $site_url;
        global $site_path;
		
		// 1. sklop - ime ankete
		echo '<div class="fieldset">';	

		// Naslov
		echo '<div class="title">' . $lang['srv_newSurvey_survey_archive_title'] . '</div>';

		echo $lang['srv_newSurvey_survey_archive_text2'];
		
		echo '<div class="setting archive">';		 
		echo '<form id="restore" action="ajax.php?a=archive_restore" method="post" name="restorefrm" enctype="multipart/form-data" >';
		
		echo '<input type="hidden" name="has_data" value="0" />';
		//echo '<input type="file" name="restore" onchange="document.restorefrm.submit();" />';
		echo '<input type="file" name="restore" class="pointer" />';

		echo $lang['srv_arhiv_datoteka_save_txt2'].'.';
		
		echo '<br /><br />'.$lang['srv_newSurvey_survey_archive_text'].'.';
			  
		echo '</form>';
		echo '</div>';
		
		// Izpis napake pri uvozu
		if(isset($_GET['error'])){
			if($_GET['error'] == '2')
				echo '<p><span class="red">'.$lang['srv_newSurvey_survey_archive_error2'].'</span></p>';
			else
				echo '<p><span class="red">'.$lang['srv_newSurvey_survey_archive_error1'].'</span></p>';
		}
		
		echo '</div>';
		
		
		// Gumba naprej in preklici
        echo '<div class="noSurvey_buttons">';

        echo '<a href="' . $site_url . 'admin/survey/index.php" title="' . $lang['srv_cancel'] . '"><span id="noSurvey_cancel">' . $lang['srv_cancel'] . '</span></a>';
        echo '<a href="#" onclick="document.restorefrm.submit();" title="' . $lang['srv_create_survey'] . '"><span id="noSurvey_create">' . $lang['next1'] . '</span></a>';

        echo '</div>';
	}


    /**
     * Modul za ustvarjanje hierarhije
     */
    private function displayRightContentHierarhija(){
        global $lang;
        global $site_url;
        global $site_path;

        // V kolikor nima pravic za ustvarjanje hierarhije je blank page
        if(!HierarhijaHelper::aliImaDostopDoIzdelovanjaHierarhije()){
            return false;
        }

        // 1. sklop - ime ankete
        echo '<div class="fieldset">';

        // Naslov
        echo '<div class="title">' . $lang['srv_hierarchy'] . '</div>';

        // Glavno okno za prikaz uvoda in izbire ankete
        echo '<div class="setting archive" id="hierarhija-opcije-vklopa">';

            if(!empty($_GET['c']) && $_GET['c'] == 'izbira'){
                global $hierarhija_default_id;

                echo '<h4>'.$lang['srv_hierarchy_intro_select_title'].':</h4>';
                echo '<div class="izbira">';
                    echo '<label class="strong block" onclick="pridobiKnjiznicoZaHierarhijo(\'nova\')"><input type="radio" id="obstojeca-anketa" name="izberi-anketo" value="nova"/><span class="enka-checkbox-radio"></span>'.$lang['srv_hierarchy_intro_option_new'].'</label>';

                    echo '<div class="ime-ankete" style="padding:10px;display: none;">';
                        // Interno ime
                        echo '<div class="setting">';
                        echo '<span class="label" style="float:left; width:180px;">' . $lang['srv_list_no_data_create'] . ':</span>';
                        echo '<input type="text" id="novaanketa_naslov_1" name="novaanketa_naslov_1" placeholder="' . $lang['srv_novaanketa_polnoime'] . '" value="' . $lang['srv_novaanketa_polnoime'] . '" size="40" maxlength="' . ANKETA_NASLOV_MAXLENGTH . '"  onfocus="if(this.value==\'' . $lang['srv_novaanketa_polnoime'] . '\') {this.value=\'\';}" />';
                        echo '<span id="novaanketa_naslov_1_chars">0 / ' . ANKETA_NASLOV_MAXLENGTH . '</span>';
                        echo '</div>';

                        // Objavljeno ime
                        echo '<div class="setting">';
                        echo '<span class="label" style="float:left; width:180px;">' . $lang['srv_novaanketa_kratkoime'] . ':</span>';
                        echo '<input type="text" id="novaanketa_akronim_1" name="novaanketa_akronim_1" value="' . $lang['srv_novaanketa_ime_respondenti'] . '" placeholder="' . $lang['srv_novaanketa_ime_respondenti'] . '" size="40" maxlength="' . ANKETA_AKRONIM_MAXLENGTH . '"  onfocus="$(this).attr(\'changed\',\'1\'); if(this.value==\'' . $lang['srv_novaanketa_ime_respondenti'] . '\') {this.value=\'\';}" changed="0" />';
                        echo '<span id="novaanketa_akronim_1_chars">0 / ' . ANKETA_AKRONIM_MAXLENGTH . '</span>';
                        echo '</div>';
                    echo '</div>';

                    echo '<label class="strong block"><input type="radio" id="prevzeta-anketa" name="izberi-anketo" onclick="pridobiKnjiznicoZaHierarhijo(\'privzeta\')" value="prevzeta" /><span class="enka-checkbox-radio"></span>'.$lang['srv_hierarchy_intro_option_default'].'
                            <a href="/main/survey/index.php?anketa='.$hierarhija_default_id.'&amp;preview=on" target="_blank" title="Predogled ankete">
                                <span class="faicon preview"></span>
                            </a>                          
                          </label>';

                    echo '<label class="strong block"><input type="radio" name="izberi-anketo" value="knjiznica" onclick="pridobiKnjiznicoZaHierarhijo(\'vse\')"/><span class="enka-checkbox-radio"></span>'.$lang['srv_hierarchy_intro_option_library'].'</label>';
                echo '</div>';
                echo '<div id="hierarhija-knjiznica">';
                echo '</div>';
            }else{
                echo $lang['srv_hierarchy_description'];
            }

        echo '</div>';
        echo '</div>';

        // Gumbi
        echo '<div class="noSurvey_buttons">';
        if(!empty($_GET['c']) && $_GET['c'] == 'izbira'){

            echo '<a href="' . $site_url . 'admin/survey/index.php?a=ustvari_anketo&b=hierarhija" title="' . $lang['back'] . '"><span id="noSurvey_cancel">' . $lang['back'] . '</span></a>';
            echo '<a href="#" onclick="potrdiIzbiroAnkete();" title="' . $lang['srv_potrdi'] . '"><span id="noSurvey_create">' . $lang['srv_potrdi'] . '</span></a>';

        }else {
            echo '<a href="'.$site_url.'admin/survey/index.php" title="'.$lang['srv_cancel'].'"><span id="noSurvey_cancel">'.$lang['srv_cancel'].'</span></a>';
            echo '<a href="'.$site_url.'admin/survey/index.php?a=ustvari_anketo&b=hierarhija&c=izbira"  title="'.$lang['srv_next1'].'"><span id="noSurvey_create">'.$lang['next1'].'</span></a>';
        }

        echo '</div>';

    }

	
	// Uvoz ankete iz besedila
	public function displayRightContentFromText(){
		global $lang;
        global $site_url;
        global $site_path;
		global $global_user_id;
		
		
		// 1. sklop - ime ankete
		echo '<div class="fieldset">';	

		// Naslov
		echo '<div class="title">' . $lang['srv_noSurvey_settings'] . '</div>';

		echo '<div class="survey_title_text_holder">';

        // Interno ime
        echo '<div class="setting survey_title_text">';
        echo '<span class="label" style="float:left; width:180px;">' . $lang['srv_list_no_data_create'] . ':</span>';
        echo '<input type="text" id="novaanketa_naslov_1" name="novaanketa_naslov_1" placeholder="' . $lang['srv_novaanketa_polnoime'] . '" value="' . $lang['srv_novaanketa_polnoime'] . '" size="40" maxlength="' . ANKETA_NASLOV_MAXLENGTH . '"  onfocus="if(this.value==\'' . $lang['srv_novaanketa_polnoime'] . '\') {this.value=\'\';}" />';
        echo '<span id="novaanketa_naslov_1_chars">0 / ' . ANKETA_NASLOV_MAXLENGTH . '</span>';
        echo '</div>';

        // Objavljeno ime
        echo '<div class="setting survey_title_text">';
        echo '<span class="label" style="float:left; width:180px;">' . $lang['srv_novaanketa_kratkoime'] . ':</span>';
        echo '<input type="text" id="novaanketa_akronim_1" name="novaanketa_akronim_1" value="' . $lang['srv_novaanketa_ime_respondenti'] . '" placeholder="' . $lang['srv_novaanketa_ime_respondenti'] . '" size="40" maxlength="' . ANKETA_AKRONIM_MAXLENGTH . '"  onfocus="$(this).attr(\'changed\',\'1\'); if(this.value==\'' . $lang['srv_novaanketa_ime_respondenti'] . '\') {this.value=\'\';}" changed="0" />';
        echo '<span id="novaanketa_akronim_1_chars">0 / ' . ANKETA_AKRONIM_MAXLENGTH . '</span>';
        echo '</div>';
		
		// Ce ima uporabnik mape, lahko izbere v katero mapo se anketa uvrsti
		UserSetting::getInstance()->Init($global_user_id);
		$show_folders = UserSetting::getInstance()->getUserSetting('survey_list_folders');
		$sql = sisplet_query("SELECT * FROM srv_mysurvey_folder WHERE usr_id='$global_user_id' ORDER BY naslov ASC");
		if($show_folders == 1 && mysqli_num_rows($sql) > 0){
			echo '<div class="setting survey_title_text">';
			echo '<span class="label" style="float:left; width:180px;">' . $lang['srv_newSurvey_survey_new_folder'] . ':</span>';
			
			echo '<select name="novaanketa_folder" id="novaanketa_folder">';
			echo '<option value="0">'.$lang['srv_newSurvey_survey_new_folder_def'].'</option>';
			while($row = mysqli_fetch_array($sql)){
				echo '<option value="'.$row['id'].'">'.$row['naslov'].'</option>';
			}
			echo '</select>';
			
			echo '</div>';
		}
		
		echo '</div>';
		echo '</div>';
		
		
		// 2. sklop - uvoz iz besedila
		echo '<div class="fieldset anketa_from_text">';	

		// Naslov
		echo '<div class="title">' . $lang['srv_newSurvey_survey_from_text_title'] . ' '.Help::display('srv_create_survey_from_text').'</div>';
		echo '<div class="from_text_instructions">' . $lang['srv_newSurvey_survey_from_text_text'] . '</div>';
		
		// Input okno za text
		echo '<div id="input_field_holder"><div id="input_field">';
		//echo '<textarea placeholder="'.$lang['srv_newSurvey_survey_from_text_example'].'" onKeyUp="$(\'#preview_field\').html($(\'textarea\').val());"></textarea>';
		echo '<textarea id="anketa_from_text_textarea" placeholder="'.$lang['srv_newSurvey_survey_from_text_example'].'" onKeyUp="anketaFromText_preview();"></textarea>';
		echo '</div></div>';
		
		// Preview okno
		echo '<div id="preview_field_holder"><div id="preview_field">';
		echo '<span class="italic">'.$lang['srv_poglejanketo2'].'</span>';
		echo '</div></div>';	
		
		echo '</div>';		


        // Gumba naprej in preklici
        echo '<div class="noSurvey_buttons">';

        echo '<a href="' . $site_url . 'admin/survey/index.php" title="' . $lang['srv_cancel'] . '"><span id="noSurvey_cancel">' . $lang['srv_cancel'] . '</span></a>';
        echo '<a href="#" onclick="newAnketaFromText();" title="' . $lang['srv_create_survey'] . '"><span id="noSurvey_create">' . $lang['next1'] . '</span></a>';

        echo '</div>';
	}
	
	
	/**
     * Uporabnik se je ravnokar registriral in se nima nobenih anket
     *
     */
    function displayNoSurveySequence(){
        global $lang;
        global $site_url;
        global $site_path;

        echo '<div class="noSurvey_sequence">';

		// Video
        if ($_GET['b'] == 'video') {

            echo '<div class="main_holder video">';

            echo '<span class="title">' . $lang['noSurvey_sequence_title'] . '</span>';

            echo '<p style="text-align:center;"><iframe width="700" height="500" style="border:1px #8a9fbf solid;" src="//www.youtube.com/embed/1OeaQErrPrc" frameborder="0" allowfullscreen></iframe></p>';
            
            echo '</div>';
        } 
        // Spisek funkcionalnosti
        elseif ($_GET['b'] == 'features') {

            echo '<div class="main_holder features">';

            echo '<span class="title">' . $lang['noSurvey_sequence_title'] . '</span>';

            echo $lang['noSurvey_sequence_features'];

            echo '<h2>' . $lang['noSurvey_sequence_features_h1'] . '</h2>';
            echo '<ul class="features">';
            echo '<li>' . $lang['noSurvey_sequence_features_li1'] . '</li>';
            echo '<li>' . $lang['noSurvey_sequence_features_li2'] . '</li>';
            echo '</ul>';

            echo '<h2>' . $lang['noSurvey_sequence_features_h2'] . '</h2>';
            echo '<ul class="features">';
            echo '<li>' . $lang['noSurvey_sequence_features_li3'] . '</li>';
            echo '<li>' . $lang['noSurvey_sequence_features_li4'] . '</li>';
            echo '<li>' . $lang['noSurvey_sequence_features_li5'] . '</li>';
            echo '<li>' . $lang['noSurvey_sequence_features_li6'] . '</li>';
            echo '<li>' . $lang['noSurvey_sequence_features_li7'] . '</li>';
            echo '<li>' . $lang['noSurvey_sequence_features_li8'] . '</li>';
            echo '<li>' . $lang['noSurvey_sequence_features_li9'] . '</li>';
            echo '</ul>';

            echo '<h2>' . $lang['noSurvey_sequence_features_h3'] . '</h2>';
            echo '<ul class="features">';
            echo '<li>' . $lang['noSurvey_sequence_features_li10'] . '</li>';
            echo '<li>' . $lang['noSurvey_sequence_features_li11'] . '</li>';
            echo '<li>' . $lang['noSurvey_sequence_features_li12'] . '</li>';
            echo '<li>' . $lang['noSurvey_sequence_features_li13'] . '</li>';
            echo '<li>' . $lang['noSurvey_sequence_features_li14'] . '</li>';
            echo '<li>' . $lang['noSurvey_sequence_features_li15'] . '</li>';
            echo '<li>' . $lang['noSurvey_sequence_features_li16'] . '</li>';
            echo '<li>' . $lang['noSurvey_sequence_features_li17'] . '</li>';
            echo '</ul>';

            echo '</div>';
        } 
        // Prva stran
        else {

            echo '<div class="main_holder main">';
            
            // Leva stran
            echo '<div class="left_holder">';
            echo '<span class="title">' . $lang['noSurvey_sequence_title'] . '</span>';
            echo $lang['noSurvey_sequence_main'];
            echo '</div>';

            // Desna stran
            echo '<div class="right_holder">';
            echo '<img src="/public/img/images/first_survey.jpg">';
            echo '</div>';

            echo '</div>';
        }


        // Gumbi na dnu
        echo '<div class="buttons_holder">';

        // Gumb ustvari anketo
        echo '<a href="'.$site_url.'/admin/survey/index.php?b=new_survey">';
        echo '  <div class="button">'.$lang['noSurvey_sequence_button_create'].'</div>';
        echo '</a>';
        
        // Gumb videovodic
        if($_GET['b'] != 'video'){
            echo '<a href="'.$site_url.'/admin/survey/index.php?b=video">';
            echo '  <div class="button button_gray">'.$lang['noSurvey_sequence_button_video'].'</div>';
            echo '</a>';
        }

        // Gumb ogled funkcionalnosti
        if($_GET['b'] != 'features'){
            echo '<a href="'.$site_url.'/admin/survey/index.php?b=features">';
            echo '  <div class="button button_gray">'.$lang['noSurvey_sequence_button_advanced'].'</div>';
            echo '</a>';
        }

        // Gumb ogled cenika
        if($_GET['b'] == 'features'){
            $cenik_link = ($lang['id'] == '1') ? 'https://www.1ka.si/d/sl/cenik' : 'https://www.1ka.si/d/en/services/';

            echo '<a href="'.$cenik_link.'" target="_blank">';
            echo '  <div class="button button_gray">'.$lang['noSurvey_sequence_button_cenik'].'</div>';
            echo '</a>';
        }

        echo '</div>';


        echo '</div>';
    }


	public function ajax(){
		global $lang;
		
		if ($_GET['a'] == 'from_text_preview') {
			
			$text = (isset($_POST['text'])) ? $_POST['text'] : '';
			$text = str_replace('\n', '<br />', strip_tags($text));

			// Ce imamo prazno
			if($text == ''){
				$text = '<span class="italic">'.$lang['srv_poglejanketo2'].'</span>';
			}
			else{
				// Pobrisemo vmesne odvecne presledke
				$text = preg_replace(
					'/(<br \/>){3,}/', 
	        		'<br /><br />',
					$text
				);
	
				// Wrapamo naslove (prazna vrstica spredaj)
				$text = preg_replace(
					'/<br \/><br \/>([^<>]+)/', 
	        		'<br /><br /><span class="title">$1</span>',
					$text
				);		
				// Wrapamo variable (vsaka v novi vrstici)
				$text = preg_replace(
					'/<br \/>([^<>]+)/', 
	        		'<br /><span class="variable"><input type="radio" /><span class="enka-checkbox-radio"></span> $1</span>',
					$text
				);				
				// Dodamo se textbox ce je samo vprasanje
				$text = preg_replace(
					'/(<span class="title">[^<>]+<\/span>)<br \/><br \/>/', 
	        		'$1<br /><input type="text" /><br /><br />',
					$text
				);
				
				// Wrapamo se prvo vrstico kot naslov
				$text = preg_replace(
					'/([^<>]+)<br \/>/', 
					'<span class="title">$1</span><br />',
					$text, 1
				);
				// Dodamo se textbox na zadnjo vrstico ce je potrebno
				$text = preg_replace(
					'/(<span class="title">[^<>]+<\/span>)\Z/', 
					'$1<br /><input type="text" />',
					$text
				);
				// Dodamo se textbox na prvo vrstico ce je potrebno
				$text = preg_replace(
					'/(<span class="title">[^<>]+<\/span>)<br \/><br \/>/', 
					'$1<br /><input type="text" /><br /><br />',
					$text, 1
				);				
			}

			echo $text.'<br /><br />';
		}
	}
}