<?php

/**
* 
* Prevajanje ankete za vecjezikovno podporo
* 
*/

class Prevajanje {
	
	var $anketa;                // trenutna anketa
	
	var $db_table = '';
	
	var $lang_admin;				// lang admin
	var $lang_resp;					// lang resp
	
	var $lang_id = 0;				// id languaga, ki ga trenutno prevajamo
	
	var $Survey = null;
	var $Branching = null;
	
	var $user_dostop_edit = null;		// aktivni uporabniki, pasivni lahko prevajajo samo dodeljene jezike
	
	/**
	* konstruktor
	* 
	* @param mixed $anketa
	* @return Vprasanje
	*/
	function __construct ($anketa = 0) {
		global $lang;				// lang admin
		global $lang1;				// lang resp
		global $lang2;				// lang, ki ga prevajamo
		global $global_user_id;
		global $admin_type;
		
		if (isset ($_GET['anketa']))
			$this->anketa = $_GET['anketa'];
		elseif (isset ($_POST['anketa'])) 
			$this->anketa = $_POST['anketa'];
		if ($anketa != 0) 
			$this->anketa = $anketa;
		
		SurveyInfo::getInstance()->SurveyInit($this->anketa);
		$rowa = SurveyInfo::getInstance()->getSurveyRow();
				
		SurveySetting::getInstance()->Init($this->anketa);
		
		$this->db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
		
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		$this->lang_admin = $row['lang_admin'];
		$this->lang_resp = $row['lang_resp'];
		
		if (isset($_REQUEST['lang_id']))
			$this->lang_id = $_REQUEST['lang_id'];
		
		$this->include_lang($this->lang_resp);
		$lang1 = $lang;
			
		if ($this->lang_id > 0) {
			$this->include_second_lang();
			$lang2 = $lang;
		}
		
		$this->include_base_lang();	
	}
	
	function dostop () {
		global $admin_type;
		global $global_user_id;
		
		SurveyInfo::getInstance()->SurveyInit($this->anketa);
		$rowa = SurveyInfo::getInstance()->getSurveyRow();
		
		$manage = '';
		if ($admin_type <= 1) $manage = " OR uid IN (SELECT user FROM srv_dostop_manage WHERE manager = '$global_user_id')";
		
		$sql = sisplet_query("SELECT ank_id, uid FROM srv_dostop WHERE ank_id = '$this->anketa' AND dostop LIKE '%edit%' AND (uid='$global_user_id' $manage )");
		if (mysqli_num_rows($sql) > 0)
			$this->user_dostop_edit = 1;
		else
			$this->user_dostop_edit = 0;
			
		
		if ($this->user_dostop_edit == 1 || $admin_type <= $rowa['dostop']) {
			// vse ok
			$this->user_dostop_edit = 1;		// ce je admin in ima dostop, potem je aktiven (ni pa vpisan v srv_dostop)
		} else {
			if (count($this->get_all_translation_langs())==0)	// ce pasivni uporabnik nima dodeljenega nobenega jezika
				die('No access language');
			$this->user_dostop_edit = 0;		// to niti ni treba, ampak zarad lepsga, ce je ze zgoraj :)
		}	
	}
	
	/**
	* starting point za prevajanje
	* 
	*/
	function prevajaj () {
		global $lang;	// admin-default lang
		global $lang1;	// respondent lang
		global $lang2;	// lang ki ga prevajamo
		
		$this->dostop();
		
		$rowa = SurveyInfo::getInstance()->getSurveyRow();
		
		if ($rowa['multilang'] == 1) {
			
			// default je pač prvi jezik v seznamu...
			if ($this->lang_id==0) {
				$lang_array = $this->get_all_translation_langs();
				if (count($lang_array) > 0) {
					list($this->lang_id) = each($lang_array);
					
					// redirectamo, da je nastavljen tudi v urlju
					header("Location: index.php?anketa=".$this->anketa."&a=prevajanje&lang_id=".$this->lang_id);
					
					$this->include_lang($this->lang_resp);
					$lang1 = $lang;
					
					$this->include_second_lang();
					$lang2 = $lang;
					
					$this->include_base_lang();
				}
			}
		}
		
		
		$this->top_settings();
		
		$sql = sisplet_query("SELECT * FROM srv_language WHERE ank_id = '$this->anketa'");
		if (mysqli_num_rows($sql) == 0) {
		
			//$this->dodaj_jezik(1);
		
		} else {
			
			if ($rowa['multilang'] == 1) {
				
				$this->urejanje();
				
				?><script> var srv_meta_lang_id = <?=$this->lang_id?>; </script><?
			}
		}
	}
	
	/**
	* Zgornje nastavitve in opcije pri prevajanju.
	* 
	*/
	function top_settings () {
		global $lang;
		global $site_url;
		global $site_path;
		global $admin_type;
		global $global_user_id;
		
		SurveySetting::getInstance()->Init($this->anketa);
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		$lang_admin = $row['lang_admin'];
		$lang_resp = $row['lang_resp'];
		$lang_array = array();
		// Preberemo razpoložljive jezikovne datoteke
		if ($dir = opendir($site_path . 'lang')) {
			while (($file = readdir($dir)) !== false) {
				if ($file != '.' AND $file != '..') {
					if (is_numeric(substr($file, 0, strpos($file, '.')))) {
						$i = substr($file, 0, strpos($file, '.'));
						if ($i > 0) {
							$file = '../../lang/'.$i.'.php';
							@include($file);
							$lang_array[$i] = $lang['language'];
						}
					}
				}
			}
		}
		
		// nastavimo jezik nazaj
		if ($lang_admin > 0) {
			$file = '../../lang/'.$lang_admin.'.php';
			@include($file);
		}
		
		
		/**
		* Leva stran nastavitev 
		*/
		
		echo '<fieldset class="wide prevajanje" style="position:relative">';
		
		if ($this->user_dostop_edit == 1) {
			
			echo '<legend>' . $lang['srv_language_settings'] . '</legend>';
			echo '<form autocomplete="off" method="post" action="ajax.php?a=editanketasettings" name="settingsanketa_'.$row['id'].'">';
			
			echo '	<input type="hidden" value="'.$row['id'].'" name="anketa">
					<input type="hidden" value="" name="grupa">
					<input type="hidden" value="prevajanje" name="location">
					<input type="hidden" value="1" name="submited">';
			
			
			asort($lang_array);
			
			echo '<p><span class="nastavitveSpan1">'.$lang['srv_language_respons_1'].': </span><select name="lang_resp" onchange="document.settingsanketa_' . $row['id'] . '.submit(); return false;">';
			foreach ($lang_array AS $key => $val) {
				echo '<option value="'.$key.'" '.($key==$lang_resp?' selected':'').'>'.$val.'</option>'; 
			}
			echo '</select> <a href="index.php?anketa='.$this->anketa.'&a=jezik">'.$lang['srv_extra_translations'].'</a></p>' . "\n\r";
			
			$resp_change_lang = SurveySetting::getInstance()->getSurveyMiscSetting('resp_change_lang');		
			
			if ($this->lang_id != 0) {
				
				echo '<p><span class="nastavitveSpan1"><label for="">'.$lang['srv_resp_change_lang'].':</label> </span>
					<label for="resp_change_lang_0"><input type="radio" name="resp_change_lang" id="resp_change_lang_0" value="0" '.($resp_change_lang==0?' checked':'').' onchange="document.settingsanketa_' . $row['id'] . '.submit(); return false;" />'.$lang['no'].'</label> 
					<label for="resp_change_lang_1"><input type="radio" name="resp_change_lang" id="resp_change_lang_1" value="1" '.($resp_change_lang==1?' checked':'').' onchange="document.settingsanketa_' . $row['id'] . '.submit(); return false;" />'.$lang['yes'].'</label></p>';
					
				if($resp_change_lang==1){
					$resp_change_lang_type = SurveySetting::getInstance()->getSurveyMiscSetting('resp_change_lang_type');		
					
					echo '<p>';
					
					echo '<span class="nastavitveSpan1"><label for="">'.$lang['srv_resp_change_lang_type'].':</label> </span>
					<label for="resp_change_lang_type_0"><input type="radio" name="resp_change_lang_type" id="resp_change_lang_type_0" value="0" '.($resp_change_lang_type==0?' checked':'').' onchange="document.settingsanketa_' . $row['id'] . '.submit(); return false;" />'.$lang['srv_resp_change_lang_type_0'].'</label>
					<label for="resp_change_lang_type_1"><input type="radio" name="resp_change_lang_type" id="resp_change_lang_type_1" value="1" '.($resp_change_lang_type==1?' checked':'').' onchange="document.settingsanketa_' . $row['id'] . '.submit(); return false;" />'.$lang['srv_resp_change_lang_type_1'].'</label>';
					
					echo '</p>';
				}		
			} 
			else {
				echo '<a href="index.php?anketa='.$this->anketa.'" style="position:absolute; top:0px; right:10px">'.$lang['srv_back_edit'].'</a>';
			}
			
			// Jezik administrativnega vmesnika
			echo '<div style="position:absolute; right:20px; bottom:10px;"><span class="bold">';
			//printf ($lang['srv_language_admin_survey2'], 'index.php?anketa='.$this->anketa.'&a=nastavitve');
			echo '<a href="index.php?anketa='.$this->anketa.'&a=nastavitve">'.$lang['srv_language_admin_survey'].'</a>';
			echo '</span></div>';
			
			echo '</form>';
		}
		
		echo '</fieldset>';
		
		
		/**
		* 	Desna stran
		*/		
		echo '<fieldset class="wide prevajanje" style="position:relative; float:right;"><legend>'.$lang['srv_multilang'].'</legend>';

		if ($this->lang_id != 0) {
			$lang_array = $this->get_all_translation_langs();
			
			foreach ($lang_array AS $key => $l) {
				echo '<p>';
				
				echo '<a href="index.php?anketa='.$this->anketa.'&a=prevajanje&lang_id='.$key.'" style="font-size: 1.2em; display:inline-block; min-width: 150px; '.($this->lang_id==$key?' font-weight:bold':'').'">'.$l.'</a> ';
				
				echo '<span class="prevajanje_settings '.($key!=$this->lang_id?'transparent':'').'">';
				
				echo '<a href="'.SurveyInfo::getSurveyLink().'?language='.$key.'&preview=on" target="_blank" title="'.$lang['srv_poglejanketo'].': '.$lang['srv_preview_text'].'"><span class="faicon preview large"></span> '.$lang['srv_poglejanketo2'].'</a>&nbsp;&nbsp;-&nbsp;&nbsp;';
				echo '<a href="'.SurveyInfo::getSurveyLink().'?language='.$key.'&preview=on&testdata=on" target="_blank" title="'.$lang['srv_survey_testdata2'].': '.$lang['srv_testdata_text'].'"><span class="faicon edit_square large"></span> '.$lang['srv_test'].'</a>&nbsp;&nbsp;-&nbsp;&nbsp;';
				echo '<a href="'.SurveyInfo::getSurveyLink().'?language='.$key.'" target="_blank" title="'.$lang['url'].': '.SurveyInfo::getSurveyLink().'?language='.$key.'"><span class="faicon data_link_small large"></span> '.$lang['url'].'</a>&nbsp;&nbsp;-&nbsp;&nbsp;';

				// Pdf in rtf izvoz vprasalnika v tujem jeziku
				echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=vprasalnik_pdf&anketa='.$this->anketa.'&type=1&language='.$key).'" target="_blank" title="'.$lang['PDF_Izpis'].'"><span class="faicon pdf large"></span> '.$lang['srv_export_hover_pdf'].'</a>&nbsp;&nbsp;-&nbsp;&nbsp;';
				echo '<a href="'.makeEncodedIzvozUrlString('izvoz.php?a=vprasalnik_rtf&anketa='.$this->anketa.'&type=1&language='.$key).'" target="_blank" title="'.$lang['RTF_Izpis'].'"><span class="faicon rtf large"></span> '.$lang['srv_export_hover_rtf'].'</a>';		
								
				$d = new Dostop();
				if ($d->checkDostopSub('edit')) {
					
					echo '<span class="prevajanje_settings_sub">';
					
					echo '<a href="index.php?anketa='.$this->anketa.'&a=jezik&lang_id='.$key.'"><span class="faicon compress"></span> '.$lang['srv_extra_translations'].'</a>';
				
					$title = '';
					$sqlu = sisplet_query("SELECT uid FROM srv_dostop_language WHERE ank_id='$this->anketa' AND lang_id='$key'");
					while ($rowu = mysqli_fetch_array($sqlu)) {
						$sql1 = sisplet_query("SELECT name, surname, email FROM users WHERE id = '$rowu[uid]'");
						$row1 = mysqli_fetch_array($sql1);
						if ($title != '') $title .= ', ';
						$title .= $row1['name'].' '.$row1['surname'].' ('.$row1['email'].')';
					}
					
					echo '&nbsp;&nbsp;-&nbsp;&nbsp;<a href="index.php?anketa='.$this->anketa.'&a=dostop" title="'.$lang['srv_passive_multilang_1'].': '.$title.'"><span class="faicon users_small"></span> '.$lang['srv_users'].'</a>';
					
					if ($this->user_dostop_edit == 1)
						echo '&nbsp;&nbsp;-&nbsp;&nbsp;<a href="ajax.php?anketa='.$this->anketa.'&t=prevajanje&a=brisi_jezik&lang_id='.$key.'" onclick="if (!confirm(\''.$lang['srv_lang_rem_confirm'].'\')) return false"><span class="faicon delete_circle icon-orange"></span> '.$lang['srv_lang_rem'].'</a></span>';
				
					echo '</span>';
				}
				
				echo '</span>';
					
				echo '</p>';	
			}
		}	
		
		// Dodajanje novega jezika
		$this->dodaj_jezik();
			
		echo '</fieldset>';
			
	
		echo '<div class="clr"></div><p></p>';	
	}
	
	/**
	* opcija za dodajanje novega jezika -- ima svoj <form> zato ne more biti znotraj drugega forma
	* 
	*/
	function dodaj_jezik ($first_page=0) {
		global $lang;
		global $lang1;
		
		if ($this->user_dostop_edit == 1) {
			// ok
		} else return;
		
		$row = SurveyInfo::getInstance()->getSurveyRow();

		$lang_array = $this->get_all_available_langs();
		
		asort($lang_array);
		
		//echo '<form action="ajax.php?anketa='.$this->anketa.'&t=prevajanje&a=dodaj_jezik" method="post" name="dodaj_jezik" style="position:absolute; right:10px; top:0">';
		echo '<form action="ajax.php?anketa='.$this->anketa.'&t=prevajanje&a=dodaj_jezik" method="post" name="dodaj_jezik" >';
		echo '<p><span class="bold">'.$lang['srv_prevajanje_dodaj'].': </span> ';
		echo '<select name="lang_id">';
		echo '<option value="0"></option>';
		foreach ($lang_array AS $key => $l) {
			echo '<option value="'.$key.'">'.$l.'</option>';
		}
		echo '</select> ';
		
		echo '<input type="submit" value="'.$lang['srv_add_new_language'].'" />';
		
		echo '</p>';
		echo '</form>';
	}
	
	/**
	* spremeni jezik, ki ga trenutno prevajamo
	* 
	*/
	function spremeni_jezik () {
		global $lang;
		global $site_url;
		
		//echo '<select name="lang_id" onchange="window.location=\'index.php?anketa='.$this->anketa.'&a=prevajanje'.($this->user_aktiven==0?'2':'').'&lang_id=\'+this.value">';
		
	}
	
	/**
	* prikaz seznama za prevajanje
	* 
	*/
	function urejanje () {
		global $lang;
		global $lang1;
		
		/*echo '<div style="float:right">';
		$this->dodaj_jezik();
		echo '</div>';*/
		
		echo '<fieldset style="margin-top:40px; min-width:1100px;" class="locked">';
		
		/**
		* Leva stran
		*/
		echo '<div class="jezik_left noborder" style="opacity:1; margin-bottom:30px;">';
		echo '<span class="red" style="font-weight:bold; font-size:1.2em;">'.$lang['srv_base_lang'].': <span style="font-size:1em">'.$lang1['language'];
		echo '</span></span>';
		$d = new Dostop();
		if ($d->checkDostopSub('edit'))
			echo ' - '.$lang['srv_edit_in_edit'].' <a href="index.php?anketa='.$this->anketa.'">'.$lang['srv_urejanje'].'</a>';
		echo '</div>';
		
		/**
		* Desna stran
		*/
		echo '<div class="jezik_right noborder">';
		
		echo '<span class="red" style="font-weight:bold; font-size:1.2em;">'.$lang['srv_trans_lang'].': ';
		
		$lang_array = $this->get_all_translation_langs();
		echo '<span style="font-size:1em">'.$lang_array[$this->lang_id].'</span>';
		
		echo '</span>';
		
		echo '</div>';
		
		
		// Prevajanje imena ankete
		$this->urejanje_texti();
		
		// Prevajanje vprasanj
		$this->urejanje_vprasanja();
		
		echo '</fieldset>';
		
		echo '<div class="clr"></div>';
		
		?><script>
			prevajanje_bind_click();
		</script><?
	}
	
	/**
	* urejanje naslova ankete
	* 
	*/
	function urejanje_texti () {
		global $lang;
		
		echo '<div class="clr"></div>';
		echo '<div class="jezik_left noborder"><h2 class="jezik_page">'.$lang['srv_novaanketa_kratkoime'].'</h2></div>';
		echo '<div class="jezik_right noborder"><h2 class="jezik_page">'.$lang['srv_novaanketa_kratkoime'].'</h2></div>';
		
		echo '<strong>';
		$this->extra_translation('srv_novaanketa_kratkoime');
		echo '</strong>';
		
		$this->extra_translation('srv_anketa_opomba');	
	}
	
	/**
	* funkcija, ki prikaze polja za nastavitev ekstra prevodov
	* 
	*/
	function extra_translation ($text) {
		global $lang;
		global $lang1;
		global $lang2;
		
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		if ($text == 'srv_novaanketa_kratkoime') {	// akronim pri default jeziku je pac v srv_anketa
			$value = (($row['akronim'] == null || $row['akronim'] == '') ? $row['naslov'] : $row['akronim'] );
		} elseif ($text == 'srv_question_comment_text') {	// "Vaš komentar k vprašanju" ni v lang, ampak je v settingsih
			$value = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_text');
		} elseif ($text == 'srv_anketa_opomba') {
			$value = $row['intro_opomba'];
		} else {
			$value = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_'.$text);
			if ($value == '') $value = $lang1[$text];
		}
		
		echo '<div class="jezik_left">';
		echo '<p style="margin-left:10px">'.$value.'</p>';
		echo '</div>';
		 
		echo '<div class="jezik_right srvlang" id="srvlang_'.$text.'">';
        $this->edit_extra_translation($text, $value);
		echo '</div>';
		
	}
	
	function edit_extra_translation ($text, $def='') {
		global $lang;
		global $lang2;
		
		$row = SurveyInfo::getInstance()->getSurveyRow();
					
		$value1 = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_'.$text.'_'.$this->lang_id);
		
		if ($value1 == '' && $text != 'srv_anketa_opomba') $value1 = $def;
		
		echo '<span style="margin-left: 10px; margin:10px; display:block; width:120px; float:left;">';
		if ($text == 'srv_novaanketa_kratkoime')
			echo $lang['srv_naslov'].': ';
		if ($text == 'srv_anketa_opomba')
			echo $lang['note'].' ('.$lang['srv_internal'].'): ';
		echo '</span>';
		
		echo '<p style="margin-left:10px; min-height:15px; min-width:200px; float:left; outline:1px dashed gray; cursor: text" class="editable" id="srvlang_'.$text.'_'.$this->lang_id.'" contenteditable="true" onblur="extra_translation_save(\''.$text.'\');">'.$value1.'</p>';
	}
	
	/**
	* urejanje/prevajanje vprasanj
	* 
	*/
	function urejanje_vprasanja () {
		global $lang;
		
		Cache::cache_all_srv_spremenljivka($this->anketa);
		
		echo '<div class="clr"></div>';
		echo '<div class="jezik_left noborder"><h2 class=" jezik_page">'.$lang['srv_intro_label'].'</h2></div>';
		echo '<div class="jezik_right noborder"><h2 class=" jezik_page">'.$lang['srv_intro_label'].'</h2></div>';
		
		$this->urejanje_vprasanje(-1);
		
		$gru_id = 0;
		
		$sql = sisplet_query("SELECT s.id, g.naslov, g.id AS gru_id, g.vrstni_red FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND s.visible='1' ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");
		while ($row = mysqli_fetch_array($sql)) {
			
			if ($gru_id != $row['gru_id']) {
				echo '<div class="clr"></div>';
				echo '<div class="jezik_left noborder"><h2 class=" jezik_page">'.$lang['srv_stran'].' '.$row['vrstni_red'].'</h2></div>';
				echo '<div class="jezik_right noborder"><h2 class=" jezik_page">'.$lang['srv_stran'].' '.$row['vrstni_red'].'</h2></div>';
				
				$gru_id = $row['gru_id'];
			}
			
			$this->urejanje_vprasanje($row['id']);
		}
		
		echo '<div class="clr"></div>';
		echo '<div class="jezik_left noborder"><h2 class=" jezik_page">'.$lang['srv_end_label'].'</h2></div>';
		echo '<div class="jezik_right noborder"><h2 class=" jezik_page">'.$lang['srv_end_label'].'</h2></div>';
		
		$this->urejanje_vprasanje(-2);
	}
	
	/**
	* prikaze urejanje vprasanja - na levi original in na desni za prevod
	* 
	* @param mixed $spremenljivka
	*/
	function urejanje_vprasanje ($spremenljivka) {
		global $lang;
		global $lang1;
		global $lang2;

		//******************************************
		$row = Cache::srv_spremenljivka($spremenljivka);
		//******************************************

        // Shranimo originalen jezik vmesnika
        $lang_bck = $lang;


        include_once('../../main/survey/app/global_function.php');
        
		if (empty($this->Survey->get)){
			$this->Survey = new \App\Controllers\SurveyController(true);
			save('forceShowSpremenljivka', true);
		}
		if ($this->Branching == null) {
			$this->Branching = new Branching($this->anketa);
		}
        

        // LEVA STRAN PREVAJANJA
		echo '<div class="jezik_left">';
   
		$lang = $lang1;
		save('lang_id', null);	// null je default aka resp
		$this->Branching->lang_id = null;	// null je default aka resp
		$this->Branching->locked = true;
		
		if ($spremenljivka == -1)
			$this->Branching->introduction_conclusion(-1);
		elseif ($spremenljivka == -2)
			$this->Branching->introduction_conclusion(-2);
		else
			$this->Branching->vprasanje($spremenljivka);
		
		echo '</div>';
			
        
        // DESNA STRAN PREVAJANJA
		echo '<div class="jezik_right vprlang" id="vprlang_'.$spremenljivka.'">';
		
		$lang = $lang2;	// spremenimo language na language, ki ga prevajamo
		save('lang_id', $this->lang_id);
		$this->Branching->lang_id = $this->lang_id;
		$this->Branching->locked = false;
		
		if ($spremenljivka == -1)
			$this->Branching->introduction_conclusion(-1);
		elseif ($spremenljivka == -2)
			$this->Branching->introduction_conclusion(-2);
		else
			$this->Branching->vprasanje($spremenljivka, true);	//poklici izris vprasanja za prevajanje (za enkrat je drugi argument pomemben le za slider)
		
        echo '</div>';		
    

        // Nastavimo nazaj originalen jezik vmesnika
        $lang = $lang_bck;
	}
	
	/**
	* prikaze formo za vpis prevoda
	* 
	* @param mixed $spremenljivka
	*/
	function vprasanje_prevod ($spremenljivka) {
		global $lang;
		global $lang2;
		
		echo '<form name="vprasanje_prevod_'.$spremenljivka.'" id="vprasanje_prevod_'.$spremenljivka.'" method="post" onsubmit="vprasanje_prevod_save(\''.$spremenljivka.'\'); return false;">';
		echo '<input type="hidden" name="anketa" value="'.$this->anketa.'" />';
		echo '<input type="hidden" name="lang_id" value="'.$this->lang_id.'" />';
		echo '<input type="hidden" name="spremenljivka" value="'.$spremenljivka.'" />';
		
		if ($spremenljivka < 0)
			$row['tip'] = -1;
		else
			$row = Cache::srv_spremenljivka($spremenljivka);
			
		// spremenljivka - to majo vse spremenljivka
		$this->vprasanje_spremenljivka($spremenljivka);
		
		
		// grid - vse tabele
		if (  in_array( $row['tip'], array(6, 16, 19, 20) )  ) {
			
			$this->vprasanje_grid($spremenljivka);
			
		}
		
		// vrednost - vsa vprasanja, ki uporabljajo srv_vrednost
		if (  in_array( $row['tip'], array(1, 2, 21, 7, 17, 18, 6, 16, 19, 20) )  ) {
			
			$this->vprasanje_vrednost($spremenljivka);
			
		}
		
		// vsota - ma se svoje polje
		if (  $row['tip'] == 18  ) {
			
			$this->vprasanje_vsota($spremenljivka);
			
		}
		
		echo '<p><input type="submit" value="'.$lang['srv_potrdi'].'" /></p>';
		
		echo '</form>';
		
	}
	
	/**
	* prevod spremenljivka - naslov in info
	* 
	* @param mixed $spremenljivka
	*/
	function vprasanje_spremenljivka ($spremenljivka) {
		global $lang;
		global $lang2;
		
		$row = Cache::srv_spremenljivka($spremenljivka);
		
		$rowa = SurveyInfo::getInstance()->getSurveyRow();
		
		$sql1 = sisplet_query("SELECT naslov, info FROM srv_language_spremenljivka WHERE ank_id='$this->anketa' AND spr_id='$spremenljivka' AND lang_id='$this->lang_id'");
		$row1 = mysqli_fetch_array($sql1);
		
		if (strtolower(substr($row1['naslov'], 0, 3)) == '<p>' && strtolower(substr($row1['naslov'], -4)) == '</p>' && strrpos($row1['naslov'], '<p>') == 0) {
			$row1['naslov'] = substr($row1['naslov'], 3);
			$row1['naslov'] = substr($row1['naslov'], 0, -4);
		}
		
		if ($spremenljivka == -1) {		// uvod
			
			echo '<p>'.$lang2['srv_intro'].'<br /><textarea name="naslov">'.$row1['naslov'].'</textarea></p>';
			
		} elseif ($spremenljivka == -2) {	// zakljucek
			
			echo '<p>'.$lang2['srv_end'].'<br /><textarea name="naslov">'.$row1['naslov'].'</textarea></p>';
			
		} else {	// obicna spremenljivka
			
			echo '<p>'.$row['naslov'].'<br /><textarea name="naslov" style="width:99%">'.$row1['naslov'].'</textarea></p>';
			echo '<p>'.$row['info'].'<br /><textarea name="info" style="height:12px; width:99%">'.$row1['info'].'</textarea></p>';
		}	
	}
	
	/**
	* prevod vrednosti
	* 
	* @param mixed $spremenljivka
	*/
	function vprasanje_vrednost ($spremenljivka) {
		global $lang;
		global $lang2;
		
		$row = Cache::srv_spremenljivka($spremenljivka);
		
		$sql1 = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id = '$spremenljivka' ORDER BY vrstni_red");
		while ($row1 = mysqli_fetch_array($sql1)) {
			
			$sql2 = sisplet_query("SELECT naslov FROM srv_language_vrednost WHERE ank_id='$this->anketa' AND vre_id='$row1[id]' AND lang_id='$this->lang_id'");
			$row2 = mysqli_fetch_array($sql2);
			
			echo '<p>'.$row1['naslov'].'<br /><textarea name="naslov_vrednost_'.$row1['id'].'" style="height:12px">'.$row2['naslov'].'</textarea></p>'; 		
		}
	}
	
	/** 
	* prevod za gride
	* 
	* @param mixed $spremenljivka
	*/
	function vprasanje_grid ($spremenljivka) {
		global $lang;
		global $lang2;
		
		$row = Cache::srv_spremenljivka($spremenljivka);
		
		echo '<table style="padding-left:20px"><tr>';
		
		$sql1 = sisplet_query("SELECT id, naslov FROM srv_grid WHERE spr_id = '$spremenljivka' ORDER BY vrstni_red");
		while ($row1 = mysqli_fetch_array($sql1)) {
			
			$sql2 = sisplet_query("SELECT naslov FROM srv_language_grid WHERE ank_id='$this->anketa' AND spr_id='$spremenljivka' AND grd_id='$row1[id]' AND lang_id='$this->lang_id'");
			$row2 = mysqli_fetch_array($sql2);
			
			echo '<td style="width:auto; padding:3px">'.$row1['naslov'].'<br /><textarea name="naslov_grid_'.$row1['id'].'" style="height:12px; width:100%">'.$row2['naslov'].'</textarea></td>'; 
			
		}
		
		echo '</tr></table>';
	}
	
	/**
	* prevod za polje vsota
	* 
	* @param mixed $spremenljivka
	*/
	function vprasanje_vsota ($spremenljivka) {
		global $lang;
		global $lang2;
		
		$row = Cache::srv_spremenljivka($spremenljivka);
		
		$sql1 = sisplet_query("SELECT vsota FROM srv_language_spremenljivka WHERE ank_id='$this->anketa' AND spr_id='$spremenljivka' AND lang_id='$this->lang_id'");
		$row1 = mysqli_fetch_array($sql1);
		
		echo '<p>'.$row['vsota'].'<br /><textarea name="vsota" style="height:12px">'.$row1['vsota'].'</textarea></p>'; 
    }
	
	/**
	* vrne vse jezikovne datoteke, ki so nam se na voljo
	* 
	*/
	function get_all_available_langs () {
		global $lang;
		global $site_path;
		
		$row = SurveyInfo::getInstance()->getSurveyRow();
        
        // Dobimo vse jezike za katere obstaja jezikovna datoteka
        include_once($site_path.'lang/jeziki.php');
		$lang_array = $lang_all_global['ime'];
		
		// pobrisemo jezike, ki so ze dodani, tudi default jezik
		$sql1 = sisplet_query("SELECT lang_id FROM srv_language WHERE ank_id = '$this->anketa'");
		while ($row1 = mysqli_fetch_array($sql1)) {
			unset($lang_array[$row1['lang_id']]);	
		}
		unset($lang_array[$row['lang_resp']]);
		
		return $lang_array;
	}
	
	/**
	* vrne vse jezike, ki so ze dodani
	* 
	*/
	function get_all_translation_langs () {
		global $lang;
		global $global_user_id;
		global $admin_type;
		
		$lang_array = array();
		
		if ($this->user_dostop_edit == 1)
			$sql = sisplet_query("SELECT lang_id, language FROM srv_language WHERE ank_id = '$this->anketa'");
		else
			$sql = sisplet_query("SELECT * FROM srv_language l, srv_dostop_language d WHERE l.ank_id='$this->anketa' AND d.ank_id=l.ank_id AND d.lang_id=l.lang_id AND d.uid='$global_user_id'");
			
		while ($row = mysqli_fetch_array($sql)) {
			$lang_array[$row['lang_id']] = $row['language'];
		}
		return $lang_array;
	}
	
	/**
	* nastavi jezik nazaj na osnovnega takrat, ko kaj delamo z includi ostalih jezikov
	* 
	*/
	function include_base_lang() {
		global $lang;
		
		$this->include_lang($this->lang_admin);
	}
	
	/**
	* includa jezik, ki ga prevajamo
	* 
	*/
	function include_second_lang () {
		global $lang;
		
		$this->include_lang($this->lang_id);
	}
	
	/**
	* includa podan jezik
	* 
	* @param mixed $id
	*/
	function include_lang ($id) {
		global $lang;
		
		if ($id > 0) {
			$file = '../../lang/'.$id.'.php';
			@include($file);
		}
	}
	
	/**
	* pohendla razne nastavitve, ki se postnejo
	* ne gre nujno vse prek ajaxa, ene so tud navadne post
	* 
	*/
	function ajax() {
		global $lang;
		
		if ($_GET['a'] == 'dodaj_jezik') {
			$this->ajax_dodaj_jezik();
		
		} elseif ($_GET['a'] == 'brisi_jezik') {
			$this->ajax_brisi_jezik();
			
		} elseif ($_GET['a'] == 'extra_translation') {
			$this->ajax_extra_translation();
			
		} elseif ($_GET['a'] == 'extra_translation_save') {
			$this->ajax_extra_translation_save();
			
		} elseif ($_GET['a'] == 'vprasanje_prevod') {
			$this->ajax_vprasanje_prevod();
			
		} elseif ($_GET['a'] == 'vprasanje_prevod_save') {
			$this->ajax_vprasanje_prevod_save();
			
		}
	}
	
	function ajax_dodaj_jezik () {
		global $lang;
				
		$lang_id = $_POST['lang_id'];
		if (!$lang_id > 0) { header("Location: index.php?anketa=".$this->anketa."&a=prevajanje"); return; }
		
		// "Jezik" v originalnem jeziku (preden prklopimo na novega z include)
		$lang_string = $lang['lang'];
		
		// Originalni jezik za respondente
		$this->include_lang($this->lang_resp);
		$base_lang_resp = $lang['language'];		
		
		$this->include_lang($lang_id);
		$added_lang = $lang['language'];
		$s = sisplet_query("UPDATE srv_anketa SET multilang='1' WHERE id='$this->anketa'");
		$sql = sisplet_query("INSERT INTO srv_language (ank_id, lang_id, language) VALUES ('$this->anketa', '$lang_id', '$lang[language]')");
		
		
		// Po novem dodamo tudi spremenljivko za jezik (najlazje za upostevanje if-ov in validacij)
		// Na zacetku moramo ustvarit najprej vprasanje (ce ga se nimamo)
		$sqlS = sisplet_query("SELECT s.id AS spr_id FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='$this->anketa' AND s.gru_id=g.id AND s.skupine='3'");
		if(mysqli_num_rows($sqlS) == 0){
			
			$sqlG = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$this->anketa' AND vrstni_red='1'");
			$rowG = mysqli_fetch_array($sqlG);
			$gru_id = $rowG['id'];
		
			$b = new Branching($this->anketa);
			$spr_id = $b->nova_spremenljivka($grupa=$gru_id, $grupa_vrstni_red=1, $vrstni_red=0);
			
			$sql = sisplet_query("UPDATE srv_spremenljivka SET naslov='".$lang_string."', variable='language', variable_custom='1', skupine='3', sistem='1', visible='0', size='0' WHERE id='$spr_id'");
			
			Vprasanje::change_tip($spr_id, 1);
			
			// Dodamo se variablo za originalni jezik			
			$v = new Vprasanje($this->anketa);
			$v->spremenljivka = $spr_id;
			$vre_id = $v->vrednost_new($base_lang_resp);
		}
		else{
			$rowS = mysqli_fetch_array($sqlS);
			$spr_id = $rowS['spr_id'];
		}
		
		// Dodamo se variablo za dodajani jezik
		$v = new Vprasanje($this->anketa);
		$v->spremenljivka = $spr_id;
		$vre_id = $v->vrednost_new($added_lang);
		
		// Prestevilcimo in popravimo vrstni red
		Common::repareVrednost($spr_id);
		Common::prestevilci($spr_id);
		
		header("Location: index.php?anketa=".$this->anketa."&a=prevajanje&lang_id=".$lang_id);
	
		$this->include_base_lang();
	}
	
	function ajax_brisi_jezik () {
		global $lang;
		
		$lang_id = $_REQUEST['lang_id'];
		
		sisplet_query("DELETE FROM srv_language WHERE ank_id='$this->anketa' AND lang_id='$lang_id'");
		
		// Pobrisemo variablo iz vprasanja
		$sqlS = sisplet_query("SELECT s.id AS spr_id FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='$this->anketa' AND s.gru_id=g.id AND s.skupine='3'");
		$spr_id = 0;
		if(mysqli_num_rows($sqlS) > 0){
		
			$rowS = mysqli_fetch_array($sqlS);
			$spr_id = $rowS['spr_id'];
		
			$this->include_lang($lang_id);
			$naslov = $lang['language'];
			$this->include_base_lang();
			
			$sqlV = sisplet_query("DELETE FROM srv_vrednost WHERE naslov='".$naslov."' AND spr_id='$spr_id'");
		}
		
		// Ce nimamo vec nobenega dodanega jezika
		$sql = sisplet_query("SELECT * FROM srv_language WHERE ank_id='$this->anketa'");
		if (mysqli_num_rows($sql) == 0){
			sisplet_query("UPDATE srv_anketa SET multilang='0' WHERE id='$this->anketa'");
			
			// Pobrisemo vse vrednosti (za vsak slucaj ce kaksna ostane) in potem se spremenljivko
			if($spr_id > 0){
				$sqlVDelete = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spr_id'");
				$sqlSDelete = sisplet_query("DELETE FROM srv_spremenljivka WHERE id='$spr_id'");
			}
		}
		// Drugace prestevilcimo in popravimo vrstni red
		elseif($spr_id > 0){
			Common::repareVrednost($spr_id);
			Common::prestevilci($spr_id);
		}
		
		header("Location: index.php?anketa=".$this->anketa."&a=prevajanje");
	}
	
	function ajax_extra_translation () {
		
		$text = $_POST['text'];
		
		$this->edit_extra_translation($text);		
	}
	
	function ajax_extra_translation_save () {
		global $lang2;
		
		$value = $_POST['value'];
		$text = $_POST['text'];
		
		if ($value != '') {		
			$value = strip_tags($value);
			SurveySetting::getInstance()->setSurveyMiscSetting('srvlang_'.$text.'_'.$this->lang_id, $value);
			
			echo '<p>'.$value.'</p>';
		} 
		else {
			SurveySetting::getInstance()->removeSurveyMiscSetting('srvlang_'.$text.'_'.$this->lang_id);
			
			echo '<p>'.$lang2[$text].'</p>';
		}		
	}
	
	function ajax_vprasanje_prevod () {
		
		$spremenljivka = $_POST['spremenljivka'];
		
		$this->vprasanje_prevod($spremenljivka);		
	}
	
	function ajax_vprasanje_prevod_save () {
		global $lang;
		global $lang2;
		
		$spremenljivka = $_POST['spremenljivka'];
		
		$naslov = $_POST['naslov'];
		$info = $_POST['info'];
		$vsota = $_POST['vsota'];
		
		if (strtolower(substr($naslov, 0, 3)) != '<p>' && strtolower(substr($naslov, -4)) != '</p>' && strrpos($naslov, '<p>') === false) {
			$naslov = '<p>' . str_replace("\n", "</p>\n<p>", $naslov) . '</p>';
		}
		
		// spremenljivka
		if ($naslov!='' || $info!='' || $vsota!='')
			sisplet_query("REPLACE INTO srv_language_spremenljivka (ank_id, spr_id, lang_id, naslov, info, vsota) VALUES ('$this->anketa', '$spremenljivka', '$this->lang_id', '$naslov', '$info', '$vsota')");
		else
			sisplet_query("DELETE FROM srv_language_spremenljivka WHERE ank_id='$this->anketa' AND spr_id='$spremenljivka' AND lang_id='$this->lang_id'");
		
		// vrednost
		$sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id = '$spremenljivka'");
		while ($row1 = mysqli_fetch_array($sql1)) {
			$naslov = $_POST['naslov_vrednost_'.$row1['id']];
			if ($naslov != '')
				$s = sisplet_query("REPLACE INTO srv_language_vrednost (ank_id, vre_id, lang_id, naslov) VALUES ('$this->anketa', '$row1[id]', '$this->lang_id', '$naslov')");
			else
				$s = sisplet_query("DELETE FROM srv_language_vrednost WHERE ank_id='$this->anketa' AND vre_id='$row1[id]' AND lang_id='$this->lang_id'");
		}
		
		
		// grid
		$sql1 = sisplet_query("SELECT * FROM srv_grid WHERE spr_id = '$spremenljivka'");
		while ($row1 = mysqli_fetch_array($sql1)) {
			$naslov = $_POST['naslov_grid_'.$row1['id']];
			if ($naslov != '')
				$s = sisplet_query("REPLACE INTO srv_language_grid (ank_id, spr_id, grd_id, lang_id, naslov) VALUES ('$this->anketa', '$spremenljivka', '$row1[id]', '$this->lang_id', '$naslov')");
			else
				$s = sisplet_query("DELETE FROM srv_language_grid WHERE ank_id='$this->anketa' AND spr_id='$spremenljivka' AND grd_id='$row1[id]' AND lang_id='$this->lang_id'");
		}
		
		echo '<a href="" onclick="_vprasanje_prevod_preview=1; preview_spremenljivka(\''.$spremenljivka.'\', \''.$this->lang_id.'\'); return false;" style="float:right; margin:5px"><span class="faicon edit_square"></span></a>';

		include_once('../../main/survey/app/global_function.php');
		$this->Survey = new \App\Controllers\SurveyController(true);
		save('forceShowSpremenljivka', true);
		save('lang_id', $this->lang_id);

		$lang = $lang2;
		if ($spremenljivka == -1)
			\App\Controllers\BodyController::getInstance()->displayIntroduction();
		elseif ($spremenljivka == -2)
			\App\Controllers\BodyController::getInstance()->displayKonec();
		else
			\App\Controllers\Vprasanja\VprasanjaController::getInstance()->displaySpremenljivka($spremenljivka);
		
	}
	
}

?>