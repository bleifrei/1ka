<?php

/**
* KONSTANTE (skopirano iz SurveyAdmin...)
*
*/

// STARO
define("A_REPORTI", "reporti");

// tipi uporabnikov, (za kontrolo prikaza posameznih elementov) za preverjanje kličemo funkcijo user_role_cehck
define("U_ROLE_ADMIN", 0);
define("U_ROLE_MANAGER", 1);
define("U_ROLE_CLAN", 2);
define("U_ROLE_NAROCNIK", 3);

define("EXPORT_FOLDER", "admin/survey/SurveyData");

global $site_path;

class SurveyAdminAjax {
	
	var $anketa; // trenutna anketa
	var $grupa; // trenutna grupa
	var $spremenljivka; // trenutna spremenljivka
	var $branching = 0; // pove, ce smo v branchingu
	var $stran;
	var $podstran;
	var $skin = 0;
	var $survey_type; // privzet tip je anketa na vecih straneh

	var $displayLinkIcons = false; // zaradi nenehnih sprememb je trenutno na false, se kasneje lahko doda v nastavitve
	var $displayLinkText = true; // zaradi nenehnih sprememb je trenutno na true, se kasneje lahko doda v nastavitve
	var $setting = null;

	var $db_table = '';

	var $icons_always_on = false;	# ali ima uporabnik nastavljeno da so ikone vedno vidne
	var $full_screen_edit = false;	# ali ima uporabnik nastavljeno da ureja vprašanja v fullscreen načinu

	var $SurveyAdmin = null;
	
	/**
	* @desc konstruktor
	*/
	function __construct($action = 0, $anketa = 0) {
		global $surveySkin, $site_url, $global_user_id;
		global $lang;
		
		$this->SurveyAdmin = new SurveyAdmin($action);
		
		if (isset ($surveySkin))
			$this->skin = $surveySkin;
		else
			$this->skin = 0;

		// polovimo anketa ID
		if (isset ($_GET['anketa']))
			$this->anketa = $_GET['anketa'];
		elseif (isset ($_POST['anketa'])) 
			$this->anketa = $_POST['anketa'];
		elseif ($anketa != 0) 
			$this->anketa = $anketa;

		UserSetting :: getInstance()->Init($global_user_id);
		$this->icons_always_on = UserSetting :: getInstance()->getUserSetting('icons_always_on');
		$this->full_screen_edit = UserSetting :: getInstance()->getUserSetting('full_screen_edit');

		SurveyInfo::getInstance()->SurveyInit($this->anketa);

		if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
			$this->db_table = '_active';

		$this->survey_type = $this->SurveyAdmin->getSurvey_type($this->anketa);

		if ($_GET['a'] == 'branching' || $this->survey_type > 1)
			$this->branching = 1;

		if ($this->anketa > 0) {
			// preverimo ali anketa sploh obstaja
			if (!$this->SurveyAdmin->checkAnketaExist()) {
				header('location: ' . $site_url . 'admin/survey/index.php');
			} else

				// preverimo userjev dostop		// posebej je dovoljen dostop za pasiven do analize in reportov
				if ($this->SurveyAdmin->checkDostop() || $this->SurveyAdmin->checkDostopAktiven() || $_GET['a']==A_ANALYSIS || $_GET['a']=='analiza' || $_GET['a']=='analizaReloadData' || $_GET['t']==A_ANALYSIS || $_GET['a']==A_REPORTI ) {
					
					// ok
					
				} else {
					header('location: ' . $site_url . 'admin/main/login.php?l=' . base64_encode($_SERVER['REQUEST_URI']));
					die();		// pri ajax klicih ne sme naprej, da ne more pisat v bazo
				}
				
		}

		if ($action == 0) {
			if (isset ($_GET['anketa'])) {

				SurveyInfo :: getInstance()->SurveyInit($this->anketa);

				if (isset ($_GET['grupa'])) {
					$this->grupa = $_GET['grupa'];

				} elseif (!isset ($_GET['a'])) {
					
					$sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$this->anketa' ORDER BY vrstni_red LIMIT 1");
					$row = mysqli_fetch_array($sql);
					$this->grupa = $row['id'];
					
					if ($this->survey_type == 2)
						header('Location: index.php?anketa=' . $this->anketa . '&grupa=' . $this->grupa . '');
					
				}
				/*
				// meta podatki, ki jih beremo z JS
				echo '<form name="meta" action="" style="display:none">';
				echo '<input type="hidden" name="anketa" id="srv_meta_anketa_id" value="' . $this->anketa . '" />';
				echo '<input type="hidden" name="grupa"  id="srv_meta_grupa"  value="' . $this->grupa . '" />';
				echo '<input type="hidden" name="branching" id="srv_meta_branching" value="' . $this->branching . '" />';
				echo '<input type="hidden" name="podstran" id="srv_meta_podstran" value="' . $_GET['m'] . '" />';
				echo '<input type="hidden" name="akcija" id="srv_meta_akcija" value="' . $_GET['a'] . '" />';
				echo '<input type="hidden" name="full_screen_edit" id="srv_meta_full_screen_edit" value="' . ($this->full_screen_edit == 1 ? 1 : 0) . '" />';
				echo '</form>';
				*/
			}

			// tole je, ce se inicializira v branhingu z $action=-1 (pa mogoce/najbrz se kje), da se ne prikazujejo 2x te meta podatki in redirecta...
		} else {
			if ($this->anketa == 0)	die();
		}

		$this->stran = $_GET['a'];

	}
	
	/**
	* @desc pohendla ajax zahteve
	*/
	function ajax() {
		global $lang;
		global $site_path;
		global $site_url;
		global $global_user_id;

		if (isset ($_POST['spremenljivka']))
			$spremenljivka = $_POST['spremenljivka'];
		if (isset ($_POST['tip']))
			$tip = $_POST['tip'];
		if (isset ($_POST['survey_type']))
			$survey_type = $_POST['survey_type'];
		if (isset ($_POST['anketa'])) {
			$anketa = $_POST['anketa'];
			$this->anketa = $_POST['anketa'];
		}
		if (isset ($_POST['naslov']))
			$naslov = $_POST['naslov'];
		if (isset ($_POST['naslov2']))
			$naslov2 = $_POST['naslov2'];
		if (isset ($_POST['grupa']))
			$grupa = $_POST['grupa'];
		if (isset ($_POST['vrednost']))
			$vrednost = $_POST['vrednost'];
		if (isset ($_POST['serialize']))
			$serialize = $_POST['serialize'];
		if (isset ($_POST['thisgrupa']))
			$thisgrupa = $_POST['thisgrupa'];
		if (isset ($_POST['intro']))
			$intro = $_POST['intro'];
		if (isset ($_POST['concl']))
			$concl = $_POST['concl'];
		if (isset ($_POST['size']))
			$size = $_POST['size'];
		if (isset ($_POST['skala']))
			$skala = $_POST['skala'];
		if (isset ($_POST['undecided']))
			$undecided = $_POST['undecided'];
		if (isset ($_POST['grid']))
			$grid = $_POST['grid'];
		if (isset ($_POST['text']))
			$text = $_POST['text'];
		if (isset ($_POST['url']))
			$url = $_POST['url'];
		if (isset ($_POST['cookie']))
			$cookie = $_POST['cookie'];
		if (isset ($_POST['cookie_return']))
			$cookie_return = $_POST['cookie_return'];
		if (isset ($_POST['dostop']))
			$dostop = $_POST['dostop'];
		if (isset ($_POST['uid']))
			$uid = $_POST['uid'];
		if (isset ($_POST['variable']))
			$variable = $_POST['variable'];
		if (isset ($_POST['user_from_cms']))
			$user_from_cms = $_POST['user_from_cms'];
		if (isset ($_POST['skin']))
			$skin = $_POST['skin'];
		if (isset ($_POST['odgovarja']))
			$odgovarja = $_POST['odgovarja'];
		if (isset ($_POST['dostop_edit']))
			$dostop_edit = $_POST['dostop_edit'];
		if (isset ($_POST['branching']))
			$this->branching = $_POST['branching'];
		if (isset ($_POST['label']))
			$label = $_POST['label'];
		if (isset ($_POST['cela']))
			$cela = $_POST['cela'];
		if (isset ($_POST['decimalna']))
			$decimalna = $_POST['decimalna'];
		if (isset ($_POST['enota']))
			$enota = $_POST['enota'];
		// posiljanje mailov ob obvescanju
		if (isset ($_POST['alert_finish_respondent']))
			$alert_finish_respondent = $_POST['alert_finish_respondent'];
		if (isset ($_POST['alert_finish_respondent_cms']))
			$alert_finish_respondent_cms = $_POST['alert_finish_respondent_cms'];
		if (isset ($_POST['alert_finish_author']))
			$alert_finish_author = $_POST['alert_finish_author'];
		if (isset ($_POST['alert_finish_author_uid']))
			$alert_finish_author_uid = $_POST['alert_finish_author_uid'];
		if (isset ($_POST['alert_finish_other']))
			$alert_finish_other = $_POST['alert_finish_other'];
		if (isset ($_POST['alert_finish_other_emails']))
			$alert_finish_other_emails = $_POST['alert_finish_other_emails'];
		if (isset ($_POST['alert_finish_subject']))
			$alert_finish_subject = $_POST['alert_finish_subject'];
		if (isset ($_POST['alert_finish_text']))
			$alert_finish_text = $_POST['alert_finish_text'];
		if (isset ($_POST['alert_expire_days']))
			$alert_expire_days = $_POST['alert_expire_days'];
		if (isset ($_POST['alert_expire_author']))
			$alert_expire_author = $_POST['alert_expire_author'];
		if (isset ($_POST['alert_expire_author_uid']))
			$alert_expire_author_uid = $_POST['alert_expire_author_uid'];
		if (isset ($_POST['alert_expire_other']))
			$alert_expire_other = $_POST['alert_expire_other'];
		if (isset ($_POST['alert_expire_other_emails']))
			$alert_expire_other_emails = $_POST['alert_expire_other_emails'];
		if (isset ($_POST['alert_expire_subject']))
			$alert_expire_subject = $_POST['alert_expire_subject'];
		if (isset ($_POST['alert_expire_text']))
			$alert_expire_text = $_POST['alert_expire_text'];
		if (isset ($_POST['alert_delete_author']))
			$alert_delete_author = $_POST['alert_delete_author'];
		if (isset ($_POST['alert_delete_other']))
			$alert_delete_other = $_POST['alert_delete_other'];
		if (isset ($_POST['alert_delete_author_uid']))
			$alert_delete_author_uid = $_POST['alert_delete_author_uid'];
		if (isset ($_POST['alert_delete_other_emails']))
			$alert_delete_other_emails = $_POST['alert_delete_other_emails'];
		if (isset ($_POST['alert_delete_subject']))
			$alert_delete_subject = $_POST['alert_delete_subject'];
		if (isset ($_POST['alert_delete_text']))
			$alert_delete_text = $_POST['alert_delete_text'];
		if (isset ($_POST['alert_active_author']))
			$alert_active_author = $_POST['alert_active_author'];
		if (isset ($_POST['alert_active_author_uid']))
			$alert_active_author_uid = $_POST['alert_active_author_uid'];
		if (isset ($_POST['alert_active_other']))
			$alert_active_other = $_POST['alert_active_other'];
		if (isset ($_POST['alert_active_other_emails']))
			$alert_active_other_emails = $_POST['alert_active_other_emails'];
		if (isset ($_POST['alert_active_subject0']))
			$alert_active_subject0 = $_POST['alert_active_subject0'];
		if (isset ($_POST['alert_active_text0']))
			$alert_active_text0 = $_POST['alert_active_text0'];
		if (isset ($_POST['alert_active_subject1']))
			$alert_active_subject1 = $_POST['alert_active_subject1'];
		if (isset ($_POST['alert_active_text1']))
			$alert_active_text1 = $_POST['alert_active_text1'];
		// posiljanje mailov ob obvescanju
		if (isset ($_POST['user_base']))
			$user_base = $_POST['user_base'];
		if (isset ($_POST['progressbar']))
			$progressbar = $_POST['progressbar'];
		if (isset ($_POST['spr_id']))
			$spr_id = $_POST['spr_id'];
		if (isset ($_POST['vre_id']))
			$vre_id = $_POST['vre_id'];
		if (isset ($_POST['usr_id']))
			$usr_id = $_POST['usr_id'];
		if (isset ($_POST['value']))
			$value = $_POST['value'];
		if (isset ($_POST['textfield']))
			$textfield = $_POST['textfield'];
		if (isset ($_POST['grd_id']))
			$grd_id = $_POST['grd_id'];
		if (isset ($_POST['timer']))
			$timer = $_POST['timer'];
		if (isset ($_POST['intro_opomba']))
			$intro_opomba = $_POST['intro_opomba'];
		if (isset ($_POST['akronim']))
			$akronim = $_POST['akronim'];
		if (isset ($_POST['paramName']))
			$paramName = $_POST['paramName'];
		if (isset ($_POST['paramValue']))
			$paramValue = $_POST['paramValue'];
		if (isset ($_POST['antonucci']))
			$antonucci = $_POST['antonucci'];
		if (isset ($_POST['podpora']))
			$podpora = $_POST['podpora'];
		if (isset ($_POST['design']))
			$design = $_POST['design'];
		if (isset ($_POST['subject']))
			$subject = $_POST['subject'];
		if (isset ($_POST['grids']))
			$grids = $_POST['grids'];
		if (isset ($_POST['other']))
			$other = $_POST['other'];
		if (isset ($_POST['expire']))
			$expire = $_POST['expire'];
		if (isset ($_POST['starts']))
			$starts = $_POST['starts'];
		if (isset ($_POST['info']))
			$info = $_POST['info'];
		if (isset ($_POST['what']))
			$what = $_POST['what'];
		if (isset ($_POST['state']))
			$state = $_POST['state'];
		if (isset ($_POST['return_finished']))
            $return_finished = $_POST['return_finished'];
        if (isset ($_POST['subsequent_answers']))
			$subsequent_answers = $_POST['subsequent_answers'];
		if (isset ($_POST['cookie_continue']))
			$cookie_continue = $_POST['cookie_continue'];
		if (isset ($_POST['block_ip']))
			$block_ip = $_POST['block_ip'];
		if (isset ($_POST['child']))
			$child = $_POST['child'];
		if (isset ($_POST['reminder']))
			$reminder = $_POST['reminder'];
		if (isset ($_POST['min']))
			$min = $_POST['min'];
		if (isset ($_POST['results']))
			$results = $_POST['results'];
		if (isset ($_POST['vote_limit']))
			$vote_limit = $_POST['vote_limit'];
		if (isset ($_POST['vote_count']))
			$vote_count = $_POST['vote_count'];
		if (isset ($_POST['orientation']))
			$orientation = $_POST['orientation'];
		if (isset ($_POST['pid']))
			$pid = $_POST['pid'];
			
		if (strpos($_SERVER['HTTP_REFERER'], 'parent_if') !== false) {
			$_GET['parent_if'] = substr( $_SERVER['HTTP_REFERER'], strpos($_SERVER['HTTP_REFERER'], 'parent_if')+10 );
		}
		
		SurveyInfo :: getInstance()->SurveyInit($anketa);
		// vsilimo refresh podatkov
		SurveyInfo :: getInstance()->resetSurveyData();

		$this->survey_type = SurveyInfo :: getInstance()->getSurveyColumn('survey_type');

		Setting :: getInstance()->Init($global_user_id);


		// hendlanje AJAX zahtev (po novem so ene tudi obicne, ne-ajax)

		if ($_GET['a'] == 'edit_anketa') {
			Common::updateEditStamp();
			if ($naslov != '' && $naslov != 'undefined') {
				sisplet_query("UPDATE srv_anketa SET naslov='$naslov' WHERE id='$anketa'");
				// vsilimo refresh podatkov
				SurveyInfo :: getInstance()->resetSurveyData();

			}
			$sql = sisplet_query("SELECT naslov FROM srv_anketa WHERE id='$anketa'");
			if (!$sql)
				echo mysqli_error($GLOBALS['connect_db']);
			$row = mysqli_fetch_array($sql);
			//		    echo '    <a href="#" onclick="anketa_title_edit(\''.$this->anketa.'\',\'1\'); return false;" title="'.$lang['srv_anketa_title_edit'].'"><img src="img_'.$this->skin.'/pencil.png" alt="" /></a>';
			echo '    <a href="index.php?anketa=' . $this->anketa . '">' . $row['naslov'] . '</a>';

			//			echo $naslov;
		}
		        
        elseif ($_GET['a'] == "anketaadddevice") {
            // PDO bom moral dat da bo varno...ko bo čas... torej nikoli :)
            
            $name = str_replace ("'", "", $_POST['tablet_name']);
            $secret = str_replace ("'", "", $_POST['tablet_secret']);
            $terminal_srv_id = intval($_POST['terminal_srv_id']);
            $local_srv_id = intval($_POST['sid']);
            
            if (is_numeric ($terminal_srv_id) && is_numeric ($local_srv_id) && $terminal_srv_id >0 && $local_srv_id > 0) {
                $sql = sisplet_query("INSERT INTO srv_fieldwork (terminal_id, sid_terminal, sid_server, secret) VALUES ('" .$name ."', '" .$terminal_srv_id ."', '" .$local_srv_id ."', '" .$secret ."')");
            }
            
            header ('location: index.php?anketa=' .$local_srv_id .'&a=fieldwork');
        }
        elseif ($_GET['a'] == "anketadeldevice") {
            // PDO bom moral dat da bo varno...ko bo čas... torej nikoli :)

            $dev_id = intval($_GET['dev']);
            
            if (is_numeric ($dev_id) && $dev_id >0) {
                $sql = sisplet_query("DELETE FROM srv_fieldwork WHERE id='" .$dev_id ."'");
            }
            header ('location: index.php?anketa=' .$_GET['srv'] .'&a=fieldwork');

        }
                
		elseif ($_GET['a'] == 'edit_anketa_note') {
			if ($anketa && isset ($_POST['note']) && $_POST['note'] != '' && $_POST['note'] != 'undefined') {
				Common::updateEditStamp();
				$sql = sisplet_query("UPDATE srv_anketa SET intro_opomba='" . $_POST['note'] . "' WHERE id='$anketa'");
				// vsilimo refresh podatkov
				SurveyInfo :: getInstance()->resetSurveyData();

			}
		}
		elseif ($_GET['a'] == 'edit_anketa_akronim') {
			if ($anketa && isset ($_POST['akronim']) && $_POST['akronim'] != '' && $_POST['akronim'] != 'undefined') {
				Common::updateEditStamp();
				$sql = sisplet_query("UPDATE srv_anketa SET akronim='" . $_POST['akronim'] . "' WHERE id='$anketa'");
				// vsilimo refresh podatkov
				SurveyInfo :: getInstance()->resetSurveyData();
			}
		}
		elseif ($_GET['a'] == 'quick_title_edit') {
			$row = SurveyInfo::getInstance()->getSurveyRow();
			$naslov = $row['naslov'];
			$akronim = $row['akronim'];
	
            echo '<div id="quick_title_edit" class="divPopUp">';
            
            echo '<div class="popup_close"><a href="#" onClick="quick_title_edit_cancel(); return false;">✕</a></div>';
			
			echo '<h2>'.$lang['srv_ime'].'</h2>';
			
			echo '<div class="quick_title_edit_label taLeft floatLeft">'.$lang['srv_novaanketa_polnoime'].':</div>';
			echo '<div class="floatLeft" >';
			echo '<input type="text" id="novaanketa_naslov_1" name="novaanketa_naslov_1" value="'.$naslov.'" class="full" maxlength="'.ANKETA_NASLOV_MAXLENGTH.'"  onfocus="if(this.value==\''.$lang['srv_naslov'].'\') {this.value=\'\';}" />';
			echo '<span id="novaanketa_naslov_1_chars" class="spaceLeft">'.mb_strlen($naslov, 'UTF-8').'/'.ANKETA_NASLOV_MAXLENGTH.'</span>';
			echo '<br class="clr"/><i class="gray small">'.$lang['srv_interno_ime'].'</i>';
			echo '</div>';
			
			echo '<br class="clr"/><br class="clr"/>';
			
			echo '<div class="quick_title_edit_label taLeft floatLeft">'.$lang['srv_novaanketa_kratkoime'].':</div>';
			echo '<div class="floatLeft">';
			$name_changed = ($naslov != $akronim) ? '1' : '0';
			echo '<input type="text" id="novaanketa_akronim_1" name="novaanketa_akronim_1" value="'.$akronim.'" class="full" maxlength="'.ANKETA_AKRONIM_MAXLENGTH.'"  onfocus="$(this).attr(\'changed\',\'1\'); if(this.value==\''.$lang['srv_naslov'].'\') {this.value=\'\';}" changed="'.$name_changed.'" />';
			echo '<span id="novaanketa_akronim_1_chars" class="spaceLeft">'.mb_strlen($akronim, 'UTF-8').'/'.ANKETA_AKRONIM_MAXLENGTH.'</span>';
			echo '<br class="clr"/><i class="gray small">'.$lang['srv_objavljeno_ime'].'</i>';
			echo '</div>';
			
			echo '<br class="clr"/><br class="clr"/>';
			
			echo '<div class="quick_title_edit_label taLeft floatLeft">'.$lang['srv_list_no_data_create_note'].':</div>';
			echo '<div class="floatLeft">';
			echo '<textarea id="novaanketa_opis_1" name="novaanketa_opis_1" class="full" rows="3">'.$row['intro_opomba'].'</textarea>';
			echo '</div>';
			
			echo '<script>';
			echo "$('#novaanketa_naslov_1').keyup(function(){
			        var max = parseInt($(this).attr('maxlength'));
			        if($(this).val().length > max){
			            $(this).val($(this).val().substr(0, $(this).attr('maxlength')));
			        }

					$('#'+$(this).attr('id')+'_chars').html($(this).val().length + ' / '+max);
					check_akronim();

			    });
				$('#novaanketa_akronim_1').keyup(function(){
			        var max = parseInt($(this).attr('maxlength'));
			        if($(this).val().length > max){
			            $(this).val($(this).val().substr(0, $(this).attr('maxlength')));
			        }
					$('#'+$(this).attr('id')+'_chars').html($(this).val().length + ' / '+max);

			    });
			    function check_akronim() {
				    if ( $('#novaanketa_akronim_1').attr('changed') == '0') {
				    	$('#novaanketa_akronim_1').val($('#novaanketa_naslov_1').val());
				    }

				    var max = $('#novaanketa_akronim_1').attr('maxlength');
				    var leng = $('#novaanketa_akronim_1').val().length;

				    $('#novaanketa_akronim_1_chars').html(leng + ' / '+max);
				 }
			    ";
			echo '</script>';
			
			
	        //echo '<span class="floatleft spaceLeft" title="'.$lang['srv_settings_quick'].'"><a class="" href="#" onclick="quick_title_edit_save(\'true\'); return false;"><span>'.$lang['srv_settings_quick'].'</span></a></span>';
           
            echo '<div class="buttons_holder">';
			echo '<span class="buttonwrapper floatRight" title="'.$lang['srv_save_profile_yes'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="quick_title_edit_save(); return false;"><span>'.$lang['srv_save_profile_yes'].'</span></a></span>';
	        echo '<span class="buttonwrapper floatRight spaceRight" title="'.$lang['srv_cancel'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="quick_title_edit_cancel(); return false;"><span>'.$lang['srv_cancel'].'</span></a></span>';
            echo '</div>';
            
			echo '</div>';
		} 
		elseif ($_GET['a'] == 'quick_title_edit_save') {
			
			$update=array();
			
			if (isset($_POST['naslov']) && trim($_POST['naslov']) != '') {
				$update[] = " naslov='".trim($_POST['naslov'])."'";
			}
			if (isset($_POST['akronim']) && trim($_POST['akronim']) != '') {
				$update[] = " akronim='".trim($_POST['akronim'])."'";
			}
			if (isset($_POST['intro_opomba']) /*&& trim($_POST['intro_opomba']) != ''*/) {
				$update[] = " intro_opomba='".trim($_POST['intro_opomba'])."'";
			}
			if (count($update) > 0 ) {
				sisplet_query("UPDATE srv_anketa SET ".implode(',',$update)." WHERE id='".$this->anketa."'");
				
				SurveyInfo :: getInstance()->resetSurveyData();
				
				if (isset($_POST['quick_settings']) && $_POST['quick_settings'] == 'true') {
					echo $site_url . 'admin/survey/index.php?anketa=' . $_POST['anketa'].'&a='.A_QUICK_SETTINGS;
					return;
				}
				
				if (isset($_GET['ajaxa']) && trim($_GET['ajaxa']) != '') {
					echo $site_url . 'admin/survey/index.php?anketa=' . $_POST['anketa'].'&a='.$_GET['ajaxa'];
					return;
				} else {
					echo $site_url . 'admin/survey/index.php?anketa=' . $_POST['anketa'];
					return;
				}
			}
			if (isset($_POST['quick_settings']) && $_POST['quick_settings'] == 'true') {
				echo $site_url . 'admin/survey/index.php?anketa=' . $_POST['anketa'].'&a='.A_QUICK_SETTINGS;
				return;
			}
			
			echo $site_url . 'admin/survey/index.php?anketa=' . $_POST['anketa'];
			return;
		} 
		elseif ($_GET['a'] == 'editanketaintro') {
			Common::updateEditStamp();

			$show_intro = $_POST['show_intro'];
			$show_concl = $_POST['show_concl'];
			$concl_link = $_POST['concl_link'];
			$intro_opomba = $_POST['intro_opomba'];
			$concl_opomba = $_POST['concl_opomba'];
			if ($_POST['concl_link'] == 1)
				$concl_link = 0;
			else
				$concl_link = 1;

			$sql = sisplet_query("UPDATE srv_anketa SET
			introduction='$intro', conclusion='$concl', text='$text', url='$url' ,
			show_intro = '$show_intro', show_concl='$show_concl', concl_link='$concl_link',
			intro_opomba = '$intro_opomba', concl_opomba = '$concl_opomba'
			WHERE id='$anketa'");
			// vsilimo refresh podatkov
			SurveyInfo :: getInstance()->resetSurveyData();

			header('Location: index.php?anketa=' . $anketa . '');

		}
		elseif ($_GET['a'] == 'settings_anketa') {

			$this->anketa = $anketa;
			$this->grupa = $grupa;

			$this->SurveyAdmin->anketa_nastavitve();

		}
		elseif ($_GET['a'] == 'editanketasettings') {
			Common::updateEditStamp();

			#sistemske nastavitve
			if ($_GET['m'] == 'system') {
				if (isset ($_POST['SurveyDostop'])) {
					$val = $_POST['SurveyDostop'];
					if ($val >= 0) {
						$sql = sisplet_query("UPDATE misc SET value='$val' WHERE what = 'SurveyDostop'");
						if (!$sql)
							echo mysqli_error($GLOBALS['connect_db']);
					}
				}
				if (isset ($_POST['SurveyCookie'])) {
					$val = $_POST['SurveyCookie'];
					$sql = sisplet_query("UPDATE misc SET value='$val' WHERE what = 'SurveyCookie'");
					if (!$sql)
						echo mysqli_error($GLOBALS['connect_db']);
				}
				if (isset ($_POST['SurveyExport'])) {
					$val = $_POST['SurveyExport'];
					$sql = sisplet_query("UPDATE misc SET value='$val' WHERE what = 'SurveyExport'");
					if (!$sql)
						echo mysqli_error($GLOBALS['connect_db']);
				}
				if (isset ($_POST['SurveyForum'])) {
					$val = $_POST['SurveyForum'];
					$sql = sisplet_query("UPDATE misc SET value='$val' WHERE what = 'SurveyForum'");
					if (!$sql)
						echo mysqli_error($GLOBALS['connect_db']);
				}
            } 
            elseif ($_GET['m'] == 'global_user_settings') {
				if (isset ($_POST['language'])) {
					$lang = $_POST['language'];
		
					sisplet_query("UPDATE users SET lang = '$lang' WHERE id = '$global_user_id'");
				}

				$poslane_spremenljivke = [
                    'advancedMySurveys',
                    'oneclickCreateMySurveys',
                    'lockSurvey',
                    'autoActiveSurvey',
                    'activeComments',
                    'showIntro',
                    'showConcl',
                    'showSurveyTitle',
                    'showSAicon',
                    'showLanguageShortcut'
                ];

				foreach($poslane_spremenljivke as $post_variable) {
                    if (isset ($_POST[$post_variable])) {
                        $val = $_POST[$post_variable];

                        UserSetting::getInstance()->setUserSetting($post_variable, $val);
                        UserSetting::getInstance()->saveUserSetting();
                    }
                }

			} elseif ($_GET['m'] == 'global_user_myProfile') {


				// preveri prejsnje podatke
				$sqlU = sisplet_query ("SELECT name, surname, email, pass FROM users WHERE id='".$global_user_id."'");
				$rowU = mysqli_fetch_assoc($sqlU);
				
				$name_before = $rowU['name'];
				$surname_before = $rowU['surname'];
				$email_before = $rowU['email'];
				$password_before = $rowU['pass'];
				
				// Spremenimo ime
				if (isset($_POST['ime']) && $_POST['ime'] != '' && $_POST['ime'] != $name_before) {
					
					$checkIme = sisplet_query ("SELECT * FROM users WHERE name='".$_POST['ime']."' AND surname='" .$_POST['priimek']."' AND id!='".$global_user_id."'");
					if (mysqli_num_rows ($checkIme) == 0){
						
						$ime = $_POST['ime'];
						//$ime = strtolower($_POST['ime']);
						$ime = CleanXSS($ime);
						
						$result = sisplet_query ("UPDATE users SET name='$ime' WHERE id='".$global_user_id."'");	
					}
				}
				
				// Spremenimo priimek
				if (isset($_POST['priimek']) && $_POST['priimek'] != '' && $_POST['priimek'] != $surname_before) {
					
					$checkIme = sisplet_query ("SELECT * FROM users WHERE name='".$_POST['ime']."' AND surname='" .$_POST['priimek']."' AND id!='".$global_user_id."'");
					if (mysqli_num_rows ($checkIme) == 0){
						
						$priimek = $_POST['priimek'];
						//$priimek = strtolower($_POST['priimek']);
						$priimek = CleanXSS($priimek);
						
						$result = sisplet_query ("UPDATE users SET surname='$priimek' WHERE id='".$global_user_id."'");	
					}
				}

				// Obveščanje posodobi
                if(isset($_POST['gdpr_agree'])){
                    sisplet_query ("UPDATE users SET gdpr_agree='".$_POST['gdpr_agree']."' WHERE id='".$global_user_id."'");
                }

                if(!empty($_POST['google-2fa']) && !empty($_POST['google-2fa-secret'])){

                    User::getInstance($global_user_id)->setOption('google-2fa-secret', $_POST['google-2fa-secret']);
                    User::getInstance($global_user_id)->setOption('google-2fa-validation', 'NOT');

                }elseif(!empty($_POST['google_2fa_koda_validate']) && empty($_POST['google-2fa-secret'])){
                    $google2fa = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();
                    $secret = User::option($global_user_id, 'google-2fa-secret');

                    if ($google2fa->checkCode($secret, $_POST['google_2fa_koda_validate'])) {
                        sisplet_query ("UPDATE user_options SET option_value=NOW() WHERE option_name='google-2fa-validation'");
                        echo 'success';
                        return true;
                    }

                }

                if(empty($_POST['google-2fa']) && !empty($_POST['google_2fa_akcija']) && $_POST['google_2fa_akcija'] == 'deactivate' && !empty($_POST['google_2fa_deaktiviraj'])){
                    $user_2fa = User::option($global_user_id, 'google-2fa-secret');
                    $user_2fa_validate = User::option($global_user_id, 'google_2fa_koda_validate');

                    $google2fa = new \Sonata\GoogleAuthenticator\GoogleAuthenticator();

                    if($google2fa->checkCode($user_2fa, $_POST['google_2fa_deaktiviraj']) && $user_2fa_validate != 'NOT'){
                        sisplet_query("DELETE FROM user_options WHERE user_id='".$global_user_id."' AND option_name IN ('google-2fa-secret', 'google-2fa-validation')");

                        echo 'success';
                        return true;
                    }else{
                        echo 'error';
                        return true;
                    }
                }

                //Uporabnik si ni kode shranil
                if(empty($_POST['google-2fa']) && !empty($_POST['google_2fa_akcija']) && $_POST['google_2fa_akcija'] == 'reset'){

                    if(User::option($global_user_id, 'google-2fa-validation') == 'NOT') {
                        sisplet_query("DELETE FROM user_options WHERE user_id='".$global_user_id."' AND option_name IN ('google-2fa-secret', 'google-2fa-validation')");
                        echo 'success';
                        return true;
                    }

                }

				// Spremenimo geslo
				if (isset($_POST['geslo']) && $_POST['geslo'] != '' && $_POST['geslo'] != $password_before) {
					global $pass_salt;
					global  $cookie_domain;
					
					$geslo = $_POST['geslo'];
					$geslo = CleanXSS ($geslo);
								
					if (isset($_POST['geslo'])
							&& $_POST['geslo'] != "PRIMERZELODOLGEGAGESLA"
							&& $password_before != base64_encode((hash('SHA256', $geslo.$pass_salt)))
							&& $_POST['geslo'] == $_POST['geslo2']){


						$result = sisplet_query ("UPDATE users SET pass='".base64_encode((hash(SHA256, $geslo.$pass_salt)))."' WHERE id='".$global_user_id."'");
                        setcookie('uid', '', time() - 3600, '/', $cookie_domain);
                        setcookie('secret', '', time() - 3600, '/', $cookie_domain);
                        setcookie('unam', '', time() - 3600, '/', $cookie_domain);

                        global $site_frontend;
                        if($site_frontend == 'drupal') {
                            setcookie('spremembaGesla', '1', time() + 3600, '/',
                              $cookie_domain);
                        }
					}
				}

				// Alternativni emaili
				if (isset($_POST['alternative_email']) && validEmail($_POST['alternative_email'])) {

				    global $pass_salt;
				    $email = $_POST['alternative_email'];

                    // naredi link za aktivacijo
                    $code = base64_encode((hash('SHA256', time() .$pass_salt . $email. $rowU['name'])));

                    // Vstavimo novega userja v users_to_be kjer caka na aktivacijo
                    $insert_id = sisplet_query ("INSERT INTO users_to_be 
										(type, email, name, user_id, timecode, code, lang) 
										VALUES 
										('3', '".$email."', '".$rowU['name']."', '".$global_user_id."', '".time()."', '$code', '" .$lang['id']. "')", "id");

                    $poslji_email = [];

                    global $app_settings;
                    $PageName = $app_settings['app_name'];

                    // Pošljemo email na alternativni email in nato še na primarni email samo obvestilo o dodanem emailu
                    $poslji_email['novi'] = [
                            'email' => $email,
                            'naslov' => str_replace ("#PAGENAME#", $PageName, $lang['add_alternative_email_subject'])
                    ];
                    $poslji_email['primarni'] = [
                          'email' =>  $email_before,
                          'naslov' => str_replace ("#PAGENAME#", $PageName, $lang['add_alternative_primary_email_subject'])
                      ];

                    $uporabnik = sisplet_query("SELECT name, surname FROM users WHERE id='".$global_user_id."'", "obj");

                    // Sporočilo, ki ga posredujemo na nov email za aktivacijo
                    $alVsebina = str_replace ("#PRIMARNIEMAIL#", $email_before, $lang['add_alternative_email']);
                    $alVsebina = str_replace ("#ALTERNATIVNIEMAIL#", $email, $alVsebina);
                    $alVsebina = str_replace ("#NAME#", $uporabnik->name .' ' .$uporabnik->surname, $alVsebina);
                    $alVsebina = str_replace ("#PAGENAME#", $PageName, $alVsebina);
                    $alVsebina = str_replace ("#CODESTART#", '<a href="' .$site_url .'frontend/api/api.php?action=activate_second_email&amp;enc='.base64_encode('code=' .$code .'&id=' .$insert_id).'">', $alVsebina);
                    $alVsebina = str_replace ("#CODEEND#", '</a>', $alVsebina);


                    // Pošljemo še email na primarni email
                    $prVsebina= str_replace ("#ALTERNATIVNIEMAIL#", $email,  $lang['add_alternative_primary_email']);
                    $prVsebina= str_replace ("#NAME#", $uporabnik->name .' ' .$uporabnik->surname, $prVsebina);
                    $prVsebina= str_replace ("#PAGENAME#", $PageName, $prVsebina);

                    // Podpis
                    $signature = Common::getEmailSignature();
                    $poslji_email['novi']['vsebina'] = $alVsebina . $signature;
                    $poslji_email['primarni']['vsebina'] =  $prVsebina. $signature;


                    foreach($poslji_email as $poslji) {
                        try {
                            $MA = new MailAdapter();
                            $MA->addRecipients($poslji['email']);
                            $MA->sendMail(stripslashes($poslji['vsebina']), $poslji['naslov']);
                        } catch (Exception $e) {
                            error_log("Email pri dodajanju emaila ni bil poslan: $e");
                        }
                    }

					echo 'success';
					return true;
				}

				if (isset($_POST['active_email']) && $_POST['active_email'] != 'new') {

				        $emails = User::getInstance()->allEmails('without master');

                        foreach($emails as $email){
                            $active = 0;
                            if($email->id == $_POST['active_email'] )
                                $active = 1;

	                        sisplet_query("UPDATE user_emails SET active='".$active."' WHERE id='".$email->id."'");
                        }
				}


				if (isset($_POST['izbrisiAlternativniEmail']) && $_POST['izbrisiAlternativniEmail'] == 1 && !empty($_POST['alternativniEmailId'])) {
					sisplet_query("DELETE FROM user_emails WHERE user_id='".$global_user_id."' AND id='".$_POST['alternativniEmailId']."'");
				}

				// Izbriše račun - v bazi posatvimo na 0 in spremenimo email, da je bil odjavljen
                if (isset($_POST['izbrisiRacun']) && $_POST['izbrisiRacun'] == 1) {
				    global $cookie_domain;

	                $result = sisplet_query ("UPDATE users SET status=0, email=CONCAT('UNSU8MD-', UNIX_TIMESTAMP(), email) WHERE id='".$global_user_id."'");

	                setcookie ('uid', '', time()-3600, '/', $cookie_domain);
	                setcookie ('secret', '', time()-3600, '/', $cookie_domain);
	                setcookie ('unam', '', time()-3600, '/', $cookie_domain);

	                echo 'izbrisan';
	                return null;
				}

			} elseif ($_GET['m'] == A_MAILING || $_GET['m'] == 'inv_server' || $_GET['m'] == 'email_server') {	// smtp mailing
				if ($_POST['submited'] == 1){
					
					if ((int)$_POST['anketa'] > 0){
						$this->anketa = $_POST['anketa'];
					}
					
					$MA = new MailAdapter($this->anketa, $type='alert');
					
					$settings = $MA->getSettingsFromRequest($_REQUEST);
					$mode = $_REQUEST['SMTPMailMode'];
					
					$MA->setSettings($mode, $settings);
				}
			} elseif ($_GET['m'] == 'predvidenicasi') {	// predvideni casi
				foreach($_POST AS $key => $val) {
					if (substr($key, 0, 7) == 'timing_') {
						GlobalMisc::setMisc($key, $val);
					}
				}
			} else { // globalne nastavitve

				if (isset ($_POST['phone']) || isset ($_POST['email'])) {
					$phone = $_POST['phone'];
					$email = $_POST['email'];
					// nastavimo respondente iz baze, kreiramo novo sistemsko spremenljivko
					if ($phone == 1 or $email == 1) {
						$this->SurveyAdmin->createUserbaseSystemVariable($phone, $email);
						$user_base = 1;
					} else {
						$this->SurveyAdmin->createUserbaseSystemVariable($phone, $email);
						$user_base = 0;
						$_POST['user_base'] = 0;
					}
				}
				SurveySetting::getInstance()->Init($this->anketa);

				if (isset($_POST['resp_change_lang'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('resp_change_lang', $_POST['resp_change_lang']);
				}
				
				if (isset($_POST['resp_change_lang_type'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('resp_change_lang_type', $_POST['resp_change_lang_type']);
				}
				
				if (isset($_POST['display_backlink'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('display_backlink', $_POST['display_backlink']);
				}
				
				if (isset($_POST['mobile_friendly'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('mobile_friendly', $_POST['mobile_friendly']);
				}
				
				if (isset($_POST['hide_mobile_img'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('hide_mobile_img', $_POST['hide_mobile_img']);
				}
				
				if (isset($_POST['mobile_tables'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('mobile_tables', $_POST['mobile_tables']);
				}
				
				if (isset($_POST['export_font_size'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('export_font_size', $_POST['export_font_size']);
				}
				if (isset($_POST['export_numbering'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('export_numbering', $_POST['export_numbering']);
				}
				if (isset($_POST['export_show_if'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('export_show_if', $_POST['export_show_if']);
				}
				if (isset($_POST['export_show_intro'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('export_show_intro', $_POST['export_show_intro']);
				}
				
				if (isset($_POST['export_data_type'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('export_data_type', $_POST['export_data_type']);
				}
				if (isset($_POST['export_data_font_size'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('export_data_font_size', $_POST['export_data_font_size']);
				}
				if (isset($_POST['export_data_numbering'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('export_data_numbering', $_POST['export_data_numbering']);
				}
				if (isset($_POST['export_data_show_if'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('export_data_show_if', $_POST['export_data_show_if']);
				}			
				if (isset($_POST['export_data_show_recnum'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('export_data_show_recnum', $_POST['export_data_show_recnum']);
				}
				if (isset($_POST['export_data_PB'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('export_data_PB', $_POST['export_data_PB']);
				}
				if (isset($_POST['export_data_skip_empty'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('export_data_skip_empty', $_POST['export_data_skip_empty']);
				}
				if (isset($_POST['export_data_skip_empty_sub'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('export_data_skip_empty_sub', $_POST['export_data_skip_empty_sub']);
				}
				if (isset($_POST['export_data_landscape'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('export_data_landscape', $_POST['export_data_landscape']);
				}		
				
				if (isset($_POST['privacy'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('survey_privacy', $_POST['privacy']);
				}

				if (isset($_POST['survey_hint'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('survey_hint', $_POST['survey_hint']);
				}
				
				if (isset($_POST['survey_hide_title'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('survey_hide_title', $_POST['survey_hide_title']);
				}
				
				if (isset($_POST['survey_track_reminders'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('survey_track_reminders', $_POST['survey_track_reminders']);
				}
				
				if (isset($_POST['enumerate'])) {
					SurveySetting::getInstance()->setSurveyMiscSetting('enumerate', $_POST['enumerate']);
				}
				
				if (isset($_POST['anketa_folder'])) {				
				
					if($_POST['anketa_folder'] == '0'){
						$sql = sisplet_query("DELETE FROM srv_mysurvey_anketa WHERE ank_id='".$anketa."' AND usr_id='".$global_user_id."'");
					}
					else{
						// Razpremo folder v akterega uvrscamo anketo
						$sql = sisplet_query("UPDATE srv_mysurvey_folder SET open='1' WHERE id='".$_POST['anketa_folder']."' AND usr_id='".$global_user_id."'");

						// Vstavimo anketo
						$sql = sisplet_query("INSERT INTO srv_mysurvey_anketa (ank_id, usr_id, folder) VALUES ('".$anketa."', '".$global_user_id."', '".$_POST['anketa_folder']."') ON DUPLICATE KEY UPDATE folder='".$_POST['anketa_folder']."'");
					}
				}
				
				// shranjujemo dodatne prevode besedil...
				if (isset($_POST['extra_translations'])) {

				    // Preverimo, če dobimo podatek za izbris vseh prevodov
				    $post = $_POST;
				    if(!empty($_POST['remove_lang'])){
				       $post = [];
                       parse_str($_POST['data'], $post);
                    }

                        foreach ($post AS $key => $val) {
                            if (substr($key, 0, 8) == 'srvlang_') {
                                if ($val != '' && empty($_POST['remove_lang'])) {
                                    // očistimo HTML  tage, če gre za gumbe
                                    if (in_array(substr($key, 8), [
                                        'srv_nextpage',
                                        'srv_nextpage_uvod',
                                        'srv_prevpage',
                                        'srv_lastpage',
                                        'srv_forma_send',
                                        'srv_konec'
                                    ])) {
                                        $val = strip_tags($val);
                                    }

                                    // Počistimo besedilo preden shranimo v bazo, saj je bila težava za tuje jezike
                                    $purifier = New Purifier();
                                    $val = $purifier->purify_DB($val);

                                    SurveySetting::getInstance()->setSurveyMiscSetting($key, $val);
                                } else {
                                    SurveySetting::removeSurveyMiscSetting($key);
                                }
                                // pri osnovnem jeziku vnesemo 2x - enkrat brez pripone ID jezika, enkrat s pripono (ker se nekje uporablja eno, nekje drugo...)
                                if (! is_numeric(substr($key, strrpos($key, '_') + 1))) {
                                    if ($val != '') {
                                        SurveySetting::getInstance()->setSurveyMiscSetting($key.'_'.SurveyInfo::getInstance()->getSurveyColumn('lang_resp'), $val);
                                    } else {
                                        SurveySetting::removeSurveyMiscSetting($key.'_'.SurveyInfo::getInstance()->getSurveyColumn('lang_resp'));
                                    }
                                }
                            }
                         }
                }

				// Ce imamo vec jezikov popravimo vrednost v sistemskem vprasanju "language"
				if(isset($_POST['lang_resp'])){
					
					// Popravljamo samo ce imamo vec jezikov
					$sqlL = sisplet_query("SELECT id FROM srv_language WHERE ank_id='$this->anketa'");
					if (mysqli_num_rows($sqlL) > 0){
						$new_resp_lang_id = $_POST['lang_resp'];		
						$old_resp_lang_id = SurveyInfo::getInstance()->getSurveyColumn('lang_resp');

						// Dobimo id vprasanja
						$sqlS = sisplet_query("SELECT s.id AS spr_id FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='$this->anketa' AND s.gru_id=g.id AND s.skupine='3'");
						$spr_id = 0;
						if(mysqli_num_rows($sqlS) > 0){
						
							$rowS = mysqli_fetch_array($sqlS);
							$spr_id = $rowS['spr_id'];
							
							if($spr_id > 0){
								
								$p = new Prevajanje($this->anketa);
								$bck_lang_id = $lang['id'];

								// Pobrisemo staro vrednost default jezika za respondente
								$p->include_lang($old_resp_lang_id);
								// Noce prjet zaradi čšž-jev tko da je to se najlazje:)
								if (strcmp($lang['language'], 'Sloven&#353;&#269;ina') == 0)
									$sqlV = sisplet_query("DELETE FROM srv_vrednost WHERE naslov='Slovenščina' AND spr_id='$spr_id'");
								else
									$sqlV = sisplet_query("DELETE FROM srv_vrednost WHERE naslov='".$lang['language']."' AND spr_id='$spr_id'");
								
								// Dodamo novo vrednost v vprasanje "language"
								$v = new Vprasanje($this->anketa);
								$v->spremenljivka = $spr_id;		
								$p->include_lang($new_resp_lang_id);
								$vre_id = $v->vrednost_new($lang['language']);
								
								// Preklopimo nazaj na originalen jezik
								$p->include_lang($bck_lang_id);
								
								// Prestevilcimo in popravimo vrstni red
								Common::repareVrednost($spr_id);
								Common::prestevilci($spr_id);
							}
						}
					}
				}
				
				// Zaradi zavihkov sproti preverjamo katere variable lahko shranimo če so bile podane preko $_POST
				$allVariableToSave = array (
				'cookie',
				'cookie_return',
				'return_finished',
				'subsequent_answers',
				'cookie_continue',
				'user_from_cms',
				'user_base',
				'phone',
				'email',
				'social_network',
				'quiz',
				'uporabnost',
				'usercode_skip',
				'usercode_required',
				'usercode_text',
				'block_ip',
				'starts',
				'expire',
				'dostop',
				'odgovarja',
				'vote_limit',
				'vote_count',
				'form_open',
				'lang_admin',
				'lang_resp',
				'multilang',
				'slideshow',
				'mass_insert',
				'show_email',
				'show_concl',
				'concl_link',				
				'url',
				'conclusion',
				'concl_end_button',
				'concl_back_button',
				'vprasanje_tracking',
				'continue_later',
				'js_tracking',
				'defValidProfile',
				'showItime',
				'showLineNumber',
				'parapodatki'
				);
				
				
				// ce mamo radio: user_from_cms potem mamo tudi checkbox user_from_cms_email
				if(isset($_POST['user_from_cms'])) {
					$allVariableToSave[] = 'user_from_cms_email';
					if (!isset($_POST['user_from_cms_email']))
						$_POST['user_from_cms_email'] = 0;
					if ($_POST['user_from_cms']==2 && !isset($_POST['cookie']))
						$_POST['cookie'] = -1;
				}

				$setString = "";
				$prefix = "";
				foreach ($allVariableToSave as $value) {

					if (isset ($_POST[$value]) ) {
						$setString .= $prefix . $value . " = '" . $_POST[$value] . "'";	// tale se ze zanasa na mysqli_real_escape_string($GLOBALS['connect_db'], _string() v function.php
						$prefix = ", ";
					}
				}
				
				# če je anketa označena kot trajna, jo hkrati aktiviramo če še ni
				if (isset($_POST['trajna_anketa']) && $_POST['trajna_anketa'] == 'on') {
					$setString .= $prefix . "active = '1'";
					$prefix = ", ";
				}

				if ($setString != "") {
					$sql = sisplet_query("UPDATE srv_anketa SET " . $setString . " WHERE id='$anketa'") or die(mysqli_error($GLOBALS['connect_db']));
					// vsilimo refresh podatkov
					SurveyInfo :: getInstance()->resetSurveyData();
				}
				
				if (isset($_POST['progressbar'])) {
					$sql = sisplet_query("UPDATE srv_anketa SET progressbar='$_POST[progressbar]' WHERE id='$anketa'");
				}			
	
				if ($_POST['quiz'] == 1) {		// za kviz je anketa vedno v pogoji in bloki načinu
					sisplet_query("UPDATE srv_anketa SET survey_type='3' WHERE id = '$anketa'");
					ob_start();
					$ba = new BranchingAjax($this->anketa);
					$ba->ajax_dodaj_blok_interpretacije();
					ob_get_clean();
				}
				
				// nastavitve za knjiznico
				if (isset($_POST['javne_ankete'])) {
					if ($_POST['javne_ankete'] == 1) {
						$sqlk = sisplet_query("SELECT * FROM srv_library_anketa WHERE ank_id='$this->anketa' AND uid='0'");
						if (mysqli_num_rows($sqlk) == 0) {
							$sql1 = sisplet_query("SELECT id FROM srv_library_folder WHERE uid='0' AND tip='1' AND parent='0' AND lang='$lang[id]'");
				            $row1 = mysqli_fetch_array($sql1);
				            sisplet_query("INSERT INTO srv_library_anketa (ank_id, uid, folder) VALUES ('$this->anketa', '0', '$row1[id]')");
						}
					} else {
						sisplet_query("DELETE FROM srv_library_anketa WHERE ank_id='$this->anketa' AND uid='0'");
					}
				}
				if (isset($_REQUEST['moje_ankete'])) {
					if ($_REQUEST['moje_ankete'] == 1) {
						$sqlk = sisplet_query("SELECT * FROM srv_library_anketa WHERE ank_id='$this->anketa' AND uid='$global_user_id'");
						if (mysqli_num_rows($sqlk) == 0) {
							$sql1 = sisplet_query("SELECT id FROM srv_library_folder WHERE uid='$global_user_id' AND tip='1' AND parent='0'");
				            $row1 = mysqli_fetch_array($sql1);
				            sisplet_query("INSERT INTO srv_library_anketa (ank_id, uid, folder) VALUES ('$this->anketa', '$global_user_id', '$row1[id]')");
						}
					} else {
						sisplet_query("DELETE FROM srv_library_anketa WHERE ank_id='$this->anketa' AND uid='$global_user_id'");
					}
				}

				if ($_POST['multilang'] == 1) {
					$this->SurveyAdmin->createUserbaseSystemVariable(0, 0, 1);
				}				
				
				SurveySetting::getInstance()->Init($anketa);
				
				$surveysetting = array(
					'survey_comment',
					'survey_comment_showalways',
					'question_comment',
					'survey_comment_viewadminonly',
					'survey_comment_viewauthor',
					'question_comment_viewadminonly',
					'question_comment_viewauthor',
					'question_resp_comment_viewadminonly',					
					'question_resp_comment_inicialke',					
					'question_resp_comment_inicialke_alert',
					'question_resp_comment',		
					'survey_comment_resp',
					'survey_comment_showalways_resp',
					'survey_comment_viewadminonly_resp',
					'survey_comment_viewauthor_resp',					
					'question_comment_text',					
					'question_note_write',					
					'question_note_view',					
					'question_resp_comment_show_open',					
					'sortpostorder',					
					'addfieldposition',					
					'commentmarks',					
					'commentmarks_who',	
					'comment_history',						
					'survey_ip',
					'survey_show_ip',					
					'survey_browser',					
					'survey_js',					
					'survey_referal',					
					'survey_date',					
					'preview_disableif',					
					'preview_disablealert',					
					'preview_displayifs',					
					'preview_displayvariables',					
					'preview_hidecomment',		
					'preview_hide_survey_comment',
					'preview_survey_comment_showalways',
					'preview_disable_test_insert',
				);
				
				foreach ($surveysetting AS $key) {
					if ( isset($_POST[$key]) )
						SurveySetting::getInstance()->setSurveyMiscSetting($key, $_POST[$key]);
				}
								
				// shranjujemo skrivanje metapodatkov
				if (isset($_POST['hide_metadata'])) {
					foreach ($_POST AS $key => $val) {
						if (substr($key, 0, 14) == 'hide_metadata_') {
							if ($val == 1)
								SurveySetting::getInstance()->setSurveyMiscSetting($key, '1');
							else
								SurveySetting::removeSurveyMiscSetting($key);
						}
					}
				}

				if ($dostop_edit == 1 && isset ($global_user_id) && $global_user_id > 0) {
					global $admin_type; 
					
					$uid = $_POST['uid'];
					
					// ne pustimo da ni izbran noben user, ker potem nihče več nima dostopa do ankete zato dodamo kontrolo na global_user_id
					// prav tako ne smemo onemogočiti dostopa avtorju
					$avtorSql = sisplet_query("SELECT insert_uid FROM srv_anketa WHERE id='" . $this->anketa . "'");
					$avtorRow = mysqli_fetch_assoc($avtorSql);
					
					// da ne more zbrisat avtorja (razen če je test@1ka.si)
					$avtorPogoj = (isset ($avtorRow['insert_uid']) && $avtorRow['insert_uid'] > 0) ? " AND (uid != ".$avtorRow['insert_uid']." )" : "";
					// da ne more zbrisat sam sebe
					$avtorID = " AND uid != '" . $global_user_id . "'";
					if ($admin_type == 0) { 
						$avtorPogoj = ''; 
						$avtorID = '';
					}
					$uid_canedits = " AND uid NOT IN (".implode(',', $uid).") ";
					$sql = sisplet_query("DELETE FROM srv_dostop WHERE ank_id='$anketa' ". $avtorID . $avtorPogoj . $uid_canedits);	
					if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
					
					if (isset ($uid) && $uid != null && is_array($uid))
						foreach ($uid AS $val) {
							//if ($val != $global_user_id)
								$sql = sisplet_query("INSERT INTO srv_dostop (ank_id, uid, aktiven) VALUES ('$anketa', '$val', '1')");
					}
					
					// pasivnih ni vec.....................
					if (isset($_POST['uid_passive']) && $_POST['uid_passive'] != null && is_array($_POST['uid_passive']))
						foreach ($_POST['uid_passive'] AS $val) {
							//if ($val != $global_user_id)
								$sql = sisplet_query("INSERT INTO srv_dostop (ank_id, uid, aktiven) VALUES ('$anketa', '$val', '0')");
					}
					
					if (isset($_POST['dostop_language'])) {
						sisplet_query("DELETE FROM srv_dostop_language WHERE ank_id = '$anketa'");
						foreach ($_POST['dostop_language'] AS $val) {
							$val = explode('-', $val);
							$uid = $val[0];
							$lang_id = $val[1];
							sisplet_query("INSERT INTO srv_dostop_language (ank_id, uid, lang_id) VALUES ('$anketa', '$uid', '$lang_id')");
						}
					}		
				}
				
				if ($_POST['comment_send'] != '') {
					
					// nastavitev, da se okno s komentarji prvic prikaze odprto
					if ($_POST['srv_c_alert'] == '1')
						$ocena = 5;
					else $ocena = 0;
					
					$ba = new BranchingAjax($this->anketa);
					$ba->ajax_comment_manage(0, 0, $_POST['comment_send'], $ocena);
					
					// posiljanje komentarjev na maile
					if ($_POST['srv_c_to_mail'] == '1') {
						
						$sr = SurveyInfo::getSurveyRow();
						
						foreach ($_POST['mails'] AS $email) {
							
							//$email = $rowa['email'];
							$content = $_POST['comment_send'].' <br /><br /><a href="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'">'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'</a>';
							$subject = $lang['srv_admin_s_comments'].': '.$sr['naslov'];

							try
							{
								$MA = new MailAdapter($this->anketa, $type='alert');
								$MA->addRecipients($email);
								$resultX = $MA->sendMail(stripslashes($content), $subject);
							}
							catch (Exception $e)
							{
							}
							
							if ($resultX) {
								$status = 1; // poslalo ok
							} else {
								$status = 2; // ni poslalo
							}
							
						}
					}
				}

				// Dodajanje novih uproabnikov (emailov)
				if (isset($_POST['addusers']) && $_POST['addusers'] != '') {
					global $pass_salt, $global_user_id, $site_path, $admin_type;
					
					$_POST['addusers'] = mysql_real_unescape_string($_POST['addusers']);
					$users = explode(NEW_LINE, $_POST['addusers']);
					$sqlu = sisplet_query("SELECT email, name FROM users WHERE id = '".$global_user_id."'");
					$rowu = mysqli_fetch_array($sqlu);
					
					$MailReply = $rowu['email'];
					$nameAuthor = $rowu['name'];
					
					$aktiven = $_POST['aktiven'];
					
					// Loop cez vse vnesesne userje, ki jim dajemo dostop
					foreach ($users AS $user) {
						
						$user = explode(',', $user);
						$email = trim($user[0]);
						$name = trim($user[1])!='' ? trim($user[1]) : $email;
						$surnname = trim($user[2])!='' ? trim($user[2]) : $email;

						// Ce gre za veljaven email dodamo userja
						if ($email != '' && validEmail($email)) {
						
							$id = 0;
							$sqlu = sisplet_query("SELECT id FROM users WHERE email='$email'");
	
							// Ce user, ki ga dodajamo, se ne obstaja, ga ustvarimo - PO NOVEM SAMO CE SMO ADMIN ALI MANAGER
							if (mysqli_num_rows($sqlu) == 0 && ($admin_type == 0 || $admin_type == 1)) {
								$s = sisplet_query("INSERT INTO users (name, surname, email, pass, type, when_reg, came_from) VALUES ('$name', '$surnname', '$email', '" .base64_encode((hash(SHA256, '' .$pass_salt))) ."', '3', DATE_FORMAT(NOW(), '%Y-%m-%d'), '1')");
								$id = mysqli_insert_id($GLOBALS['connect_db']);
							} 
							// Drugace pridobimo podatke o userju iz baze
							else {
								$rowu = mysqli_fetch_array($sqlu);
								$id = $rowu['id'];
							}
							
							// Ce je bil ustvarjen oz ga imamo ze v bazi, mu damo dostop in posljemo mail
							if($id > 0){
                                $s = sisplet_query("INSERT INTO srv_dostop (ank_id, uid, aktiven) VALUES ('$anketa', '$id', '$aktiven')");
                                if ( !$s ) echo mysqli_error($GLOBALS['connect_db']);

							    // V kolikor gre za hierarhijo, potem še enkrat preverimo v bazi in dodelimo dostop tudi do hierarhije status 2 - naknadno dodan administrator
							    if(SurveyInfo::checkSurveyModule('hierarhija', $anketa))
                                    sisplet_query("INSERT INTO srv_hierarhija_users (user_id, anketa_id, type) VALUES ('".$id."', '".$anketa."', 2)");
			
								$naslov = SurveyInfo::getInstance()->getSurveyColumn('naslov');

								$subject = $lang['srv_dostopmail_1'].' '.$naslov.'.';
                                
								$content = $lang['srv_dostopmail_2'].' <span style="color:red;">'.$nameAuthor.'</span> (<a style="color:#1e88e5 !important; text-decoration:none !important;" href="mailto:'.$MailReply.'">'.$MailReply.'</a>) '.$lang['srv_dostopmail_3'].' <a style="color:#1e88e5 !important; text-decoration:none !important;" href="'.$site_url.'admin/survey/index.php?anketa='.$anketa.'"><span style="font-weight:bold;">'.$naslov.'.</span></a><br /><br />
                                '.$lang['srv_dostopmail_4'].' <a style="color:#1e88e5 !important; text-decoration:none !important;" href="'.$site_url.'">'.$site_url.'</a> '.$lang['srv_dostopmail_5'].' (<a style="color:#1e88e5 !important; text-decoration:none !important;" href="mailto:'.$email.'">'.$email.'</a>).';
                                
                                // Ce email se ni registriran, dodamo dodatno obvestilo
                                if(mysqli_num_rows($sqlu) == 0 && ($admin_type == 0 || $admin_type == 1)){
                                    $content .= '<br /><br />'.$lang['srv_dostopmail_7'];
                                    $content .= ' <a style="color:#1e88e5 !important; text-decoration:none !important;" href="'.$site_url.'/admin/survey/index.php?a=nastavitve&m=global_user_myProfile">'.$lang['edit_data'].'</a> ';
                                    $content .= $lang['srv_dostopmail_72'];
                                }
                                
                                // Sporočilo urednika (opcijsko)
                                if(isset($_POST['addusers_note']) && $_POST['addusers_note'] != ''){

                                    $_POST['addusers_note'] = mysql_real_unescape_string($_POST['addusers_note']);

                                    $content .= '<br /><br /><span style="font-weight:bold;">'.$lang['srv_dostopmail_note'].'</span><br /><br />';
                                    $content .= '<span style="color:red;">'.$_POST['addusers_note'].'</span>';
                                }

                                // Podpis
                                $signature = Common::getEmailSignature();
                                $content .= $signature;

								try{
									$MA = new MailAdapter($this->anketa, $type='account');
									$MA->addRecipients($email);
									$resultX = $MA->sendMail(stripslashes($content), $subject);
								}
								catch (Exception $e)
								{
								}
								
								if ($resultX) {
									$status = 1; // poslalo ok
								} else {
									$status = 2; // ni poslalo
								}
							}
						}
					}
				}
			}
			
			# nastavimo še stvari za slideshow
			if (isset($_POST['slideshow'])) {
				if ((int)$_POST['slideshow'] == 1) {
					# spremenimo skin v slideshow
					$ss = new SurveySlideshow($this->anketa);
					$ss -> setSlideshowSkin();
				}
			}
			
			$urlprefix = "?";
			if (isset ($anketa) && $anketa != null && $anketa != "") {
				$anketaurl = $urlprefix . 'anketa=' . $anketa;
				$urlprefix = "&";
			}
			if ($_REQUEST['location'] == 'jezik' && $_REQUEST['multilang'] == '1') {
				$locationurl = $urlprefix . 'a=prevajanje';
				$urlprefix = "&";
			} else if ($_REQUEST['uporabnost'] == 1) {
				$locationurl = $urlprefix . 'a=uporabnost';
				$urlprefix = "&";
			} else if ($_REQUEST['user_from_cms'] == 2 && $_REQUEST['location'] != 'piskot') {
				$locationurl = $urlprefix . 'a=vnos';
				$urlprefix = "&";
			} else if ($_REQUEST['quiz'] == 1) {
				$locationurl = $urlprefix . 'a=kviz';
				$urlprefix = "&";
			} else if ($_REQUEST['phone'] == 1) {
				$locationurl = $urlprefix . 'a='.A_TELEPHONE;
				$urlprefix = "&";
			} else if ($_REQUEST['email'] == 1) {
				#$locationurl = $urlprefix . 'a=invitations';
				$locationurl = $urlprefix . 'a='.A_VABILA;
				$urlprefix = "&";
			} else if ($_REQUEST['social_network'] == 1) {
				$locationurl = $urlprefix . 'a=social_network';
				$urlprefix = "&";
			} else if ($_REQUEST['m'] == 'vabila ') {
				// izpisemo vsebino nastavitev za vabila
				$locationurl = $urlprefix . 'a='.A_VABILA;
				$urlprefix = "&";
			} else if ($_REQUEST['slideshow'] == 1) {
				$locationurl = $urlprefix.'a=slideshow';
				$urlprefix = "&";
			} else if ($_REQUEST['m'] == 'system') {
				$locationurl = $urlprefix.'a=nastavitve&m=system';
				$urlprefix = "&";
			} else if ($_REQUEST['m'] == 'global_user_settings') {
				$locationurl = $urlprefix.'a=nastavitve&m=global_user_settings';
				$urlprefix = "&";
			} else if ($_REQUEST['m'] == 'global_user_myProfile') {
				$locationurl = $urlprefix.'a=nastavitve&m=global_user_myProfile';
				$urlprefix = "&";
			} else if ($_REQUEST['m'] == 'predvidenicasi') {
				$locationurl = $urlprefix.'a=nastavitve&m=predvidenicasi';
				$urlprefix = "&";
			} else if ($_REQUEST['m'] == 'vabila_settings') {
				$locationurl = $urlprefix.'a='.A_VABILA;
				$urlprefix = "&";
			} else if ($_REQUEST['m'] == 'inv_server') {
				$locationurl = $urlprefix.'a=invitations&m=inv_settings';
				$urlprefix = "&";
			} else if ($_REQUEST['m'] == 'email_server') {
				$locationurl = $urlprefix.'a=alert&m=email_server';
				$urlprefix = "&";
			} else if ($_POST['location'] == 'handleUserCodeSetting') {
				$locationurl = $urlprefix.'&a='.A_VABILA;
				$urlprefix = "&";
			} else {
				$location = (isset ($_POST['location']) && $_POST['location'] != null && $_POST['location'] != "") ? $location = $_POST['location'] : 'nastavitve';
				$locationurl = $urlprefix . 'a=' . $location;
				$urlprefix = "&";
			}
			if (isset($_REQUEST['submited']) && $_REQUEST['submited'] == 1) {
				$locationurl .= $urlprefix.'s=1'.($_GET['show_back'] ? '&show_back=true' : '');	
			}
			if ( isset($_REQUEST['lang_id']) ) {
				$locationurl .= $urlprefix.'lang_id='.$_REQUEST['lang_id'];
			}
			header('Location: ' . $site_url . 'admin/survey/index.php' . $anketaurl . $locationurl);
		}
		elseif ($_GET['a'] == 'enableEmailInvitation') {
			
			// Vklop vabil z individualizirano kodo (posta, sms)
			if(isset($_POST['what']) && $_POST['what'] == '2'){
				sisplet_query("UPDATE srv_anketa SET user_base='1', usercode_required='1', show_email='0' WHERE id='$anketa'");
				SurveySession::sessionStart($anketa);
				SurveySession::set('inv_noEmailing', 1);
			}
			// Vklop vabil brez individualizirano kode (samo posiljanje mailov)
			elseif(isset($_POST['what']) && $_POST['what'] == '3'){				
				sisplet_query("UPDATE srv_anketa SET user_base='1', individual_invitation='0', usercode_skip='1', show_email='0' WHERE id='$anketa'");
			}
			// Vklop vabil za rocno posiljanje
			elseif(isset($_POST['what']) && $_POST['what'] == '4'){				
				sisplet_query("UPDATE srv_anketa SET user_base='1', usercode_required='1', show_email='0' WHERE id='$anketa'");
			}
			// Vklop klasicnih email vabil
			else{
				sisplet_query("UPDATE srv_anketa SET user_base='1', show_email='0' WHERE id='$anketa'");
			}

			sisplet_query("INSERT INTO srv_anketa_module (ank_id, modul) VALUES ('".$anketa."', 'email')");

			sisplet_query('COMMIT');
			
			echo $site_url . 'admin/survey/index.php?anketa=' . $anketa.'&a=invitations&s=1';
			exit();
		} 
		elseif ($_GET['a'] == 'editanketatema') {
			Common::updateEditStamp();

			$sql = sisplet_query("UPDATE srv_anketa SET skin='$skin', progressbar='$progressbar' WHERE id='$anketa'");
			// vsilimo refresh podatkov
			SurveyInfo :: getInstance()->resetSurveyData();

			header('Location: index.php?anketa=' . $anketa . '&a=tema&s=1');

		}
		elseif ($_GET['a'] == 'editanketaalert') {
			Common::updateEditStamp();
			if ($_POST['m'] == 'complete') {
				if ($alert_finish_respondent != 1)
					$alert_finish_respondent = 0;
				if ($alert_finish_respondent_cms != 1)
					$alert_finish_respondent_cms = 0;
				if ($alert_finish_author != 1)
					$alert_finish_author = 0;
				if ($alert_finish_other != 1 || !$alert_finish_other_emails)
					$alert_finish_other = 0; // če ni emailov, damo alert_more na 0

				// shranimo dodatne emaile
				$mySqlInsert = sisplet_query("INSERT INTO srv_alert (ank_id, finish_respondent, finish_respondent_cms, finish_author, finish_other, finish_other_emails, finish_subject, finish_text, reply_to) VALUES " .
				"('".$this->anketa."', '$alert_finish_respondent', '$alert_finish_respondent_cms', '$alert_finish_author', '$alert_finish_other', '$alert_finish_other_emails', '$alert_finish_subject', '$alert_finish_text', '$_POST[reply_to]') " .
				"ON DUPLICATE KEY UPDATE finish_respondent = '$alert_finish_respondent', finish_respondent_cms = '$alert_finish_respondent_cms', finish_author = '$alert_finish_author', finish_other = '$alert_finish_other', finish_other_emails='$alert_finish_other_emails', finish_subject='$alert_finish_subject', finish_text='$alert_finish_text', reply_to='$_POST[reply_to]'");
				if (!$mySqlInsert)
					echo mysqli_error($GLOBALS['connect_db']);

				// ponastavimo alert_admin
				// najprej vse stare zapise postavimo na 0 nato pa setiramo na 1 kjer je potrebno
				$mysqlUpdate = sisplet_query("UPDATE srv_dostop SET alert_complete='0' WHERE ank_id = '$this->anketa'");
				if (!$mysqlUpdate)
					echo mysqli_error($GLOBALS['connect_db']);

				if ($alert_finish_author && $alert_finish_author_uid) {
					foreach ($alert_finish_author_uid as $authorId) {
						$sqlInsertUpdate = sisplet_query("INSERT INTO srv_dostop (ank_id, uid, alert_complete) VALUES ('$this->anketa', '$authorId', 1) ON DUPLICATE KEY UPDATE alert_complete=1");
						if (!$sqlInsertUpdate)
							echo mysqli_error($GLOBALS['connect_db']);
					}
				}

			} else if ($_POST['m'] == 'expired') {
					if ($alert_expire_author != 1)
						$alert_expire_author = 0;
					if ($alert_expire_other != 1 || !$alert_expire_other_emails)
					$alert_expire_other = 0; // če ni emailov, damo alert_more na 0

				// izračunamo datum kdaj moramo obvestiti uporabnike
				$dayDif = is_numeric($alert_expire_days) ? $alert_expire_days : 0;

				$mySqlInsert = sisplet_query("INSERT INTO srv_alert (ank_id, expire_days, expire_author, expire_other, expire_other_emails, expire_subject, expire_text, reply_to) VALUES " .
				"('".$this->anketa."', '$dayDif', '$alert_expire_author', '$alert_expire_other', '$alert_expire_other_emails', '$alert_expire_subject', '$alert_expire_text', '$_POST[reply_to]') " .
				"ON DUPLICATE KEY UPDATE expire_days = '$dayDif', expire_author = '$alert_expire_author', expire_other = '$alert_expire_other', expire_other_emails='$alert_expire_other_emails', expire_subject='$alert_expire_subject', expire_text='$alert_expire_text', reply_to='$_POST[reply_to]'");
				if (!$mySqlInsert)
					echo mysqli_error($GLOBALS['connect_db']);

				// ponastavimo alert_admin
				// najprej vse stare zapise postavimo na 0 nato pa setiramo na 1 kjer je potrebno
				$mysqlUpdate = sisplet_query("UPDATE srv_dostop SET alert_expire='0' WHERE ank_id = '$anketa'");
				if (!$mysqlUpdate)
					echo mysqli_error($GLOBALS['connect_db']);
				if ($alert_expire_author && $alert_expire_author_uid) {
					foreach ($alert_expire_author_uid as $authorId) {
						$sqlInsertUpdate = sisplet_query("INSERT INTO srv_dostop (ank_id, uid, alert_expire) VALUES ('$this->anketa', '$authorId', 1) ON DUPLICATE KEY UPDATE alert_expire=1");
						if (!$sqlInsertUpdate)
							echo mysqli_error($GLOBALS['connect_db']);
					}
				}

				SurveyAlert::getInstance()->Init($anketa, $global_user_id);
				SurveyAlert::getInstance()->prepareSendExpireAlerts();

			} elseif ($_POST['m'] == 'active') {

				if ($alert_active_author != 1)
					$alert_active_author = 0;
				if ($alert_active_other != 1 || !$alert_active_other_emails)
					$alert_active_other = 0; // če ni emailov, damo alert_more na 0

				$mySqlInsert = sisplet_query("INSERT INTO srv_alert (ank_id, active_author, active_other, active_other_emails, active_subject0, active_text0, active_subject1, active_text1, reply_to) VALUES " .
				"('".$this->anketa."', '$alert_active_author', '$alert_active_other', '$alert_active_other_emails', '$alert_active_subject0', '$alert_active_text0', '$alert_active_subject1', '$alert_active_text1', '$_POST[reply_to]') " .
				"ON DUPLICATE KEY UPDATE active_author = '$alert_active_author', active_other = '$alert_active_other', active_other_emails='$alert_active_other_emails', active_subject0='$alert_active_subject0', active_text0='$alert_active_text0', active_subject1='$alert_active_subject1', active_text1='$alert_active_text1', reply_to='$_POST[reply_to]'");

				if (!$mySqlInsert)
					echo mysqli_error($GLOBALS['connect_db']);

				// ponastavimo alert_admin
				// najprej vse stare zapise postavimo na 0 nato pa setiramo na 1 kjer je potrebno
				$mysqlUpdate = sisplet_query("UPDATE srv_dostop SET alert_active='0' WHERE ank_id = '$anketa'");
				if (!$mysqlUpdate)
					echo mysqli_error($GLOBALS['connect_db']);
				if ($alert_active_author && $alert_active_author_uid) {
					foreach ($alert_active_author_uid as $authorId) {
						$sqlInsertUpdate = sisplet_query("INSERT INTO srv_dostop (ank_id, uid, alert_active) VALUES ('$this->anketa', '$authorId', 1) ON DUPLICATE KEY UPDATE alert_active=1");
						if (!$sqlInsertUpdate)
							echo mysqli_error($GLOBALS['connect_db']);
					}
				}
			} else if ($_POST['m'] == 'delete') {

					if ($alert_delete_author != 1)
						$alert_delete_author = 0;
					if ($alert_delete_other != 1 || !$alert_delete_other_emails)
					$alert_delete_other = 0; // če ni emailov, damo alert_more na 0

				$mySqlInsert = sisplet_query("INSERT INTO srv_alert (ank_id, delete_author, delete_other, delete_other_emails, delete_subject, delete_text, reply_to) VALUES " .
				"('".$this->anketa."', '$alert_delete_author', '$alert_delete_other', '$alert_delete_other_emails', '$alert_delete_subject', '$alert_delete_text', '$_POST[reply_to]') " .
				"ON DUPLICATE KEY UPDATE delete_author = '$alert_delete_author', delete_other = '$alert_delete_other', delete_other_emails='$alert_delete_other_emails', delete_subject='$alert_delete_subject', delete_text='$alert_delete_text', reply_to='$_POST[reply_to]'");
				if (!$mySqlInsert)
					echo mysqli_error($GLOBALS['connect_db']);

				// ponastavimo alert_admin
				// najprej vse stare zapise postavimo na 0 nato pa setiramo na 1 kjer je potrebno
				$mysqlUpdate = sisplet_query("UPDATE srv_dostop SET alert_delete='0' WHERE ank_id = '$anketa'");
				if (!$mysqlUpdate)
					echo mysqli_error($GLOBALS['connect_db']);
				if ($alert_delete_author && $alert_delete_author_uid) {
					foreach ($alert_delete_author_uid as $authorId) {
						$sqlInsertUpdate = sisplet_query("INSERT INTO srv_dostop (ank_id, uid, alert_delete) VALUES ('$this->anketa', '$authorId', 1) ON DUPLICATE KEY UPDATE alert_delete=1");
						if (!$sqlInsertUpdate)
							echo mysqli_error($GLOBALS['connect_db']);
					}
				}


			}
			header('Location: index.php?anketa=' . $anketa . '&a=alert&m='.$_POST['m'].(isset($_REQUEST['submited']) && $_REQUEST['submited'] == 1 ? '&s=1' : ''));
		} elseif ($_GET['a'] == 'alert_edit_if') {
			Common::updateEditStamp();
		
			$uid = $_POST['uid'];
			$type = $_POST['type'];
		
			if ($type == 1) {	// avtor oz. kdor ma dostop

		        $sql = sisplet_query("SELECT alert_complete_if FROM srv_dostop WHERE uid = '$uid' AND ank_id='$this->anketa'");
		        $row = mysqli_fetch_array($sql);
		        
		        if ($row['alert_complete_if'] > 0) {
		            $if = $row['alert_complete_if'];
		        } else {
		            sisplet_query("INSERT INTO srv_if (id) VALUES ('')");
		            $if = mysqli_insert_id($GLOBALS['connect_db']);
		            $s = sisplet_query("INSERT INTO srv_condition (id, if_id, vrstni_red) VALUES ('', '$if', '1')");
		            $s = sisplet_query("UPDATE srv_dostop SET alert_complete_if='$if' WHERE uid = '$uid' AND ank_id='$this->anketa'");
		        }
		        
			} elseif ($type == 2) {	// respondent
				
				$sql = sisplet_query("SELECT finish_respondent_if FROM srv_alert WHERE ank_id='$this->anketa'");
		        $row = mysqli_fetch_array($sql);
		        
		        if ($row['finish_respondent_if'] > 0) {
		            $if = $row['finish_respondent_if'];
		        } else {
		            sisplet_query("INSERT INTO srv_if (id) VALUES ('')");
		            $if = mysqli_insert_id($GLOBALS['connect_db']);
		            $s = sisplet_query("INSERT INTO srv_condition (id, if_id, vrstni_red) VALUES ('', '$if', '1')");
		            $s = sisplet_query("UPDATE srv_alert SET finish_respondent_if='$if' WHERE ank_id='$this->anketa'");
		        }
				
			} elseif ($type == 3) {	// respondent iz cmsja
				
				$sql = sisplet_query("SELECT finish_respondent_cms_if FROM srv_alert WHERE ank_id='$this->anketa'");
		        $row = mysqli_fetch_array($sql);
		        
		        if ($row['finish_respondent_cms_if'] > 0) {
		            $if = $row['finish_respondent_cms_if'];
		        } else {
		            sisplet_query("INSERT INTO srv_if (id) VALUES ('')");
		            $if = mysqli_insert_id($GLOBALS['connect_db']);
		            $s = sisplet_query("INSERT INTO srv_condition (id, if_id, vrstni_red) VALUES ('', '$if', '1')");
		            $s = sisplet_query("UPDATE srv_alert SET finish_respondent_cms_if='$if' WHERE ank_id='$this->anketa'");
		        }
				
			} elseif ($type == 4) {	// ostali (vneseni rocno)
				
				$sql = sisplet_query("SELECT finish_other_if FROM srv_alert WHERE ank_id='$this->anketa'");
		        $row = mysqli_fetch_array($sql);
		        
		        if ($row['finish_other_if'] > 0) {
		            $if = $row['finish_other_if'];
		        } else {
		            sisplet_query("INSERT INTO srv_if (id) VALUES ('')");
		            $if = mysqli_insert_id($GLOBALS['connect_db']);
		            $s = sisplet_query("INSERT INTO srv_condition (id, if_id, vrstni_red) VALUES ('', '$if', '1')");
		            $s = sisplet_query("UPDATE srv_alert SET finish_other_if='$if' WHERE ank_id='$this->anketa'");
		        }
				
			}
			
			if ( ! $if > 0 ) return;
			
	        $b = new Branching($this->anketa);
	        $b->condition_editing($if, -3);
			
		} elseif ($_GET['a'] == 'anketa' || $_GET['a'] == 'nova-anketa-in-hierarhija' || $_GET['a'] == 'anketa_from_text') {
			Common::updateEditStamp();
			
			if (trim($_POST['survey_type']) == '') {
				$_POST['survey_type'] = 2;
			}
			$anketa = $this->SurveyAdmin->nova_anketa($naslov, $intro_opomba, $akronim, $_POST['survey_type'], $skin);
			
			// Ce imamo pri ustvarjanju doloceno tudi mapo, anketo vstavimo v njo
			if(isset($_POST['folder']) && $_POST['folder'] > 0){
				
				// Razpremo folder v akterega uvrscamo anketo
				$sql = sisplet_query("UPDATE srv_mysurvey_folder SET open='1' WHERE id='".$_POST['folder']."' AND usr_id='".$global_user_id."'");

				// Vstavimo anketo
				$sql = sisplet_query("INSERT INTO srv_mysurvey_anketa (ank_id, usr_id, folder) VALUES ('".$anketa."', '".$global_user_id."', '".$_POST['folder']."')");
			}

			// Če ob ustvarjanju ankete vključimo še hierarhijos
			if(!empty($_POST['vkljuciHierarhijo'])){
                sisplet_query("INSERT INTO srv_anketa_module (ank_id, modul) VALUES ('".$anketa."', 'hierarhija')");

                (new \Hierarhija\Hierarhija($anketa))->DolociPraviceUporabniku();
            }

			// Ce ustvarjamo anketo preko uvoza iz besedila
			if(isset($_POST['from_text']) && $_GET['a'] == 'anketa_from_text'){

				$from_text = $_POST['from_text'];				
				$text_array = Common::anketaArrayFromText($from_text);
				
				$spr_id = 0;
				
				// Loop po vseh vprasanjih, ki jih uvazamo
				foreach($text_array as $vprasanje){
					
					$ba = new BranchingAjax($anketa);
					
					// Imamo samo naslov vprasanja - text tip (21)
					if(count($vprasanje) == 1){
						$b = new Branching($anketa);
				        $spr_id = $ba->spremenljivka_new(0, 0, 1);
									
						Vprasanje::change_tip($spr_id, $tip='21');			
						$sql = sisplet_query("UPDATE srv_spremenljivka SET naslov='".$vprasanje['title']."' WHERE id='".$spr_id."'");
					}
					// Imamo variable - radio tip (1)
					else{
						$b = new Branching($anketa);
				        $spr_id = $ba->spremenljivka_new(0, 0, 1);

						Vprasanje::change_tip($spr_id, $tip='1');
						$sql = sisplet_query("UPDATE srv_spremenljivka SET naslov='".$vprasanje['title']."' WHERE id='".$spr_id."'");				
						$sql = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spr_id'");
						
						unset($vprasanje['title']);
						
						// Loop po variablah
						foreach($vprasanje as $key => $var_title){
						
							$v = new Vprasanje();
				            $v->spremenljivka = $spr_id;
				            $vrednost = $v->vrednost_new($var_title);
							
							Common::prestevilci($spr_id);
						}
					}
				}
			}
			
			flush();
			
			echo 'index.php?anketa=' . $anketa;
			
		} elseif ($_GET['a'] == 'anketa_active') {
			Common::updateEditStamp();

			$row = SurveyInfo::getInstance()->getSurveyRow();

			if ($row['active'] == 0) {
				$active = 1;
				$backup = 0;
				
				# preverimo ali ima uporabnik izklopljeno zaklepanje
				# polovimo nastavitve uporabnika
				global $global_user_id;
				UserSetting::getInstance()->Init($global_user_id);
				# ali zaklepamo anketo ob aktivaciji
				$lockSurvey = UserSetting::getInstance()->getUserSetting('lockSurvey');
				
				$locked = "'".(int)$lockSurvey."'";
				
				// ponastavimo datume
				if ($_POST['starts']) {
					$starts = ", starts='" . $_POST['starts'] . "' ";
					$activity_starts = "'".$_POST['starts']."'";
				} else {
					$starts = ", starts=NOW() ";
					$activity_starts = 'NOW()';
				}
				if ($_POST['expire']) {
					# če je datum expire od trajne ankete '2099-01-01' in anketo deaktiviramo moramo spremeniti datum expire da ni več videti kot trajna
					if ($_POST['expire'] == PERMANENT_DATE) {
						$dateToday = date("Y-m-d"); // danes
						$_POST['expire'] = $dateToday;
					}
					$expire = ", expire='" . $_POST['expire'] . "' ";
					$activity_expire = "'".$_POST['expire']."'";
				} else {
					$expire = ", expire=NOW() + INTERVAL 30 DAY ";
					$activity_expire = 'NOW() + INTERVAL 30 DAY';
				}
			} else {
				$active = 0;
				$backup = $row['backup'];
				$locked = "locked";
                                
                if(Common::checkModule('maza') && SurveyInfo::getSurveyModules('maza')){
                    $maza = new MAZA($this->anketa);
                    $maza ->maza_off();
                }
			}

			$sql = sisplet_query("UPDATE srv_anketa SET active='$active', backup='$backup', locked=$locked $starts $expire WHERE id = '$anketa'");

			# dodamo zapis v srv_activity
			if ($active == 1) {
				$activity_insert_string = "INSERT INTO srv_activity (sid, starts, expire, uid) VALUES('".$anketa."', $activity_starts, $activity_expire, '".$global_user_id."' );";
				$sql_insert = sisplet_query($activity_insert_string);
			}

			# vsilimo refresh podatkov
			SurveyInfo :: getInstance()->resetSurveyData();

			# posljemo mail ob spremembi aktivnosti ankete
			SurveyAlert::getInstance()->Init($anketa, $global_user_id);
			SurveyAlert::getInstance()->sendMailActive();

			# popravimo tudi alerte za pošiljanje ob poteku ankete
			SurveyAlert::getInstance()->setDefaultAlertBeforeExpire();

			
			$this->anketa = $anketa;
			if ($_POST['folders'] && $_POST['folders'] == 'true') { // če smo na folderjih zlistamo folderje
				# osvezimo samo ikonico in ne celotnih map
				$row = SurveyInfo::getInstance()->getSurveyRow();
				echo '<a href="#" onclick="anketa_active(\''.$this->anketa.'\',\''.(int)$row['active'].'\',\'true\'); return false;">' .
				'<img src="'.$site_url.'admin/survey/icons/icons/star_'.((int)$row['active']==1?'on':'off').'.png" alt="'.(int)$row['active'].'" title="'.((int)$row['active']==1?$lang['srv_anketa_active']:$lang['srv_anketa_noactive']).'" />'.
				'</a> ';
            } 
            else { // čene izpišemo zgornjo vrstico ankete in nardimo link
				$this->SurveyAdmin->displayAktivnost();
			}
		}
		elseif ($_GET['a'] == 'anketa_vabila_sending') {
			$_GET['a'] = 'email';
			$_GET['m'] = 'usermailing';
			$sas = new SurveyAdminSettings();
			$sas->usermailing();
		}
		elseif ($_GET['a'] == 'anketa_delete') {
			Common::updateEditStamp();

			$rowa = SurveyInfo::getInstance()->getSurveyRow();

                        //notify all maza app users who participate in this survey that this survey has ended
                        if(SurveyInfo::getSurveyModules('maza') && $rowa['active'] = 1){
                            $maza = new MAZA($this->anketa);
                            $maza ->maza_off();
                        }

			$this->SurveyAdmin->anketa_delete($anketa);

			# če postamo iz survey_lista (prva stran) preverimo koliko anket je ostalo, če je bila zadnja osvežimo celotno stran 
			if (isset($_POST['inList'] ) && $_POST['inList'] == 'true' ) {
				# preštejemo število anket.
				global $admin_type, $global_user_id;
				//SELECT count(sa.id) AS cnt FROM srv_anketa sa WHERE sa.backup='0' AND sa.id > 0 AND (sa.dostop >= '2' OR sa.id IN (SELECT ank_id FROM srv_dostop WHERE uid='90'))
				$stringSurveyList = "SELECT count(sa.id) AS cnt FROM srv_anketa sa WHERE sa.backup='0' AND sa.id > 0 AND (sa.dostop >= '".$admin_type."' OR sa.id IN (SELECT ank_id FROM srv_dostop WHERE uid='".$global_user_id."'))";
				$sqlSurveyList = sisplet_query($stringSurveyList);
				$rowSurveyList = mysqli_fetch_assoc($sqlSurveyList);
				# v ajax post vrnemo število anket
				echo  $rowSurveyList['cnt'];
				return;
			}   
			if ($rowa['backup'] > 0)
				echo 'index.php?anketa=' . $rowa['backup'] . '&a=arhivi';
			else
				echo 'index.php';

		}
		elseif ($_GET['a'] == 'nova_grupa') {
			Common::updateEditStamp();

			if($anketa > 0){
				$sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$anketa'");
				$nums = mysqli_num_rows($sql);
				$vrstni_red = $nums + 1;

				$sql = sisplet_query("INSERT INTO srv_grupa (id, ank_id, naslov, vrstni_red) VALUES ('', '$anketa', '$lang[srv_stran] $vrstni_red', '$vrstni_red')");
				$insert_id = mysqli_insert_id($GLOBALS['connect_db']);

				// Ce dodamo 4. stran vklopimo progress indicator (pri 3 straneh ali manj je po default izklopljen)
				if($vrstni_red == 4){
					$sqlP = sisplet_query("UPDATE srv_anketa SET progressbar='1' WHERE id='$anketa'");
				}
				
				echo 'index.php?anketa=' . $anketa . '&grupa=' . $insert_id . '&novagrupa=true';
			}
			
		}
		elseif ($_GET['a'] == 'edit_grupa') {
			Common::updateEditStamp();

			$sql = sisplet_query("UPDATE srv_grupa SET naslov = '$naslov' WHERE id='$grupa'");

		}
		elseif ($_GET['a'] == 'save_edit_grupa') {
			Common::updateEditStamp();

			$sql = sisplet_query("UPDATE srv_grupa SET naslov = '$naslov' WHERE id='$grupa'");
			echo '<span id="grupaName">' . $naslov . "</span>";
			$this->SurveyAdmin->showEditPageDiv($grupa, false);

		}
		elseif ($_GET['a'] == 'save_edit_uporabnost_link') {
			Common::updateEditStamp();

			SurveySetting::getInstance()->Init($this->anketa);
			SurveySetting::getInstance()->setSurveyMiscSetting('uporabnost_link_'.$_POST['grupa'], $_POST['link']);
			$sql = sisplet_query("SELECT naslov FROM srv_grupa WHERE id  = '$grupa'");
			$row = mysqli_fetch_array($sql);
			echo '<span id="grupaName">' . $row['naslov'] . "</span>";
			$this->SurveyAdmin->showEditPageDiv($grupa, false);

		}
		elseif ($_GET['a'] == 'brisi_grupo') {
			Common::updateEditStamp();

			if($grupa > 0 && $anketa > 0){
				
				$sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id='$grupa'");
				while ($row = mysqli_fetch_array($sql)) {
					$sql1 = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$row[id]'");
				}
				$sql = sisplet_query("DELETE FROM srv_spremenljivka WHERE gru_id='$grupa'");

				$sqlOldGrupa = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id='$grupa'");
				$rowOldGrupa = mysqli_fetch_assoc($sqlOldGrupa);
				$sql = sisplet_query("DELETE FROM srv_grupa WHERE id = '$grupa'");

				// popravimo vrstni red grup
				$sqlUpdateVrestniRed = sisplet_query("UPDATE srv_grupa SET vrstni_red = vrstni_red-1 WHERE id = '$grupa' AND vrstni_red > '".$rowOldGrupa['vrstni_red']."'");

				// preverimo ce imamo kaksno grupo
				$sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$anketa'");
				$nums = mysqli_num_rows($sql);
				if ($nums == 0) {
					// dodamo eno grupo
					$vrstni_red = 1;
					$sql = sisplet_query("INSERT INTO srv_grupa (id, ank_id, naslov, vrstni_red) VALUES ('', '$anketa', '$lang[srv_stran] $vrstni_red', '$vrstni_red')");
					$insert_id = mysqli_insert_id($GLOBALS['connect_db']);

					echo $site_url . 'admin/survey/index.php?anketa=' . $anketa;
					die();
				}
				$this->SurveyAdmin->repareGrupa($anketa);

				if ($thisgrupa != $grupa) {
					$redirect = '&grupa=' . $thisgrupa;
				} else
					$redirect = '';

				echo $site_url . 'admin/survey/index.php?anketa=' . $anketa . '&grupa=' . $redirect;
				die();
			}
		}
		elseif ($_GET['a'] == 'nova_spremenljivka') {
			Common::updateEditStamp();

			$rowb = SurveyInfo::getInstance()->getSurveyRow();

			$this->grupa = $grupa;

			if ($rowb['branching'] == 0) { // obicno dodajanje spremenljivke

				if ($this->grupa > 0) {

					$sql = sisplet_query("SELECT ank_id, vrstni_red FROM srv_grupa WHERE id = '$grupa'");
					$row = mysqli_fetch_array($sql);
					$this->anketa = $row['ank_id'];

					if ($spremenljivka > 0) {
						$row3 = Cache::srv_spremenljivka($spremenljivka);
						$vrstni_red = $row3['vrstni_red'];

						$sql3 = sisplet_query("UPDATE srv_spremenljivka SET vrstni_red = vrstni_red+1 WHERE gru_id = '$grupa' AND vrstni_red >= '$vrstni_red'");
					} else {
						$sql3 = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id='$grupa'");
						$nums = mysqli_num_rows($sql3);
						$vrstni_red = $nums +1;
					}
					$spr_id = $this->SurveyAdmin->nova_spremenljivka($grupa, $row['vrstni_red'], $vrstni_red);
				}

			} else { // ce mamo branching, je treba dodati tudi v srv_branching

				//include_once ('Branching.php');
				$Branching = new Branching($this->anketa);

				$Branching->spremenljivka_new($spremenljivka);

				$Branching->repare_vrstni_red();

			}

			$this->SurveyAdmin->prestevilci();
			$this->SurveyAdmin->vprasanja();

		}
		elseif ($_GET['a'] == 'nova_spremenljivka_vrivanje') {
			Common::updateEditStamp();

			$last = substr($spremenljivka, strlen($spremenljivka) - 5, strlen($spremenljivka));

			//ugotovimo id grupe kjer se je zgodil drop
			if ($last == "_last") {
				//dodajanje na konec (popravimo spremenljivko)
				$spremenljivka = substr($spremenljivka, 0, strlen($spremenljivka) - 5);

				// ni vprasanj, ugotovimo id prve grupe
				$sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$this->anketa' ORDER BY vrstni_red LIMIT 1");
				$row = mysqli_fetch_array($sql);
				$grupa = $row['id'];
			} else {
				$row = Cache::srv_spremenljivka($spremenljivka);
				$grupa = $row['gru_id'];
			}

			$this->grupa = $grupa;

			//ugotovimo vrstni red grupe
			$sql = sisplet_query("SELECT ank_id, vrstni_red FROM srv_grupa WHERE id = '$grupa'");
			$row = mysqli_fetch_array($sql);
			$this->anketa = $row['ank_id'];

			//ugotovimo vrstni red vprasanja kamor ga vstavljamo
			$row3 = Cache::srv_spremenljivka($spremenljivka);
			$vrstni_red = $row3['vrstni_red'];

			if ($last == "_last") {
				$vrstni_red++;
			}
			//popravimo vrstni red vprasanj za vstavljenim
			$sql3 = sisplet_query("UPDATE srv_spremenljivka SET vrstni_red = vrstni_red+1 WHERE gru_id = '$grupa' AND vrstni_red >= '$vrstni_red'");

			//ustvarimo novo vprasanje na pravem mestu
			$this->SurveyAdmin->nova_spremenljivka($grupa, $row['vrstni_red'], $vrstni_red);

			//nastavimo tudi tip vprasanja ki smo ga draggali
			//substring je st. vprasanja (tip)
			$type = substr($child, 10);

			//textbox vprasanje - ena vrstica
			if ($type == "401") {
				$type = 4;
			}
			//textbox vprasanje - 5 vrstic
			elseif ($type == "405") {
				$sql = sisplet_query("SELECT params FROM srv_spremenljivka WHERE gru_id='$grupa' AND vrstni_red='$vrstni_red'");
				$row = mysqli_fetch_array($sql);
				Common::updateEditStamp();
				
				// v polje params spremenljivke shranimo spremembo parametra
				$newParams = new enkaParameters($row['params']);
				$newParams->set("taSize", 5);

				$s = sisplet_query("UPDATE srv_spremenljivka SET params='" . $newParams->getString() . "' WHERE gru_id='$grupa' AND vrstni_red='$vrstni_red'");

				$type = 4;
			}

			$sql5 = sisplet_query("UPDATE srv_spremenljivka SET tip='$type' WHERE gru_id='$grupa' AND vrstni_red='$vrstni_red'");
			//popravimo size na 1 za število
			if ($type == 7 || $type == 21)
				$sql5 = sisplet_query("UPDATE srv_spremenljivka SET size='1' WHERE gru_id='$grupa' AND vrstni_red='$vrstni_red'");

			$this->SurveyAdmin->prestevilci();
			$this->SurveyAdmin->vprasanja();

		}
		elseif ($_GET['a'] == 'nova_spremenljivka_in_grupa') {
			Common::updateEditStamp();

			$rowb = SurveyInfo::getInstance()->getSurveyRow();

			if ($_POST['grupa'] == 'all') {
				// ugotovimo id grupa od spremenljivke
				$row = Cache::srv_spremenljivka($spremenljivka);
				$this->grupa = $row['gru_id'];
				$grupa = $row['gru_id'];

			} else {
				$this->grupa = $_POST['grupa'];
				$grupa = $_POST['grupa'];
			}

			if ($rowb['branching'] == 0) { // obicno dodajanje spremenljivke

				if ($this->grupa > 0) {
					$sql = sisplet_query("SELECT ank_id, vrstni_red FROM srv_grupa WHERE id = '$grupa'");
					$row = mysqli_fetch_array($sql);
					$this->anketa = $row['ank_id'];

					if ($spremenljivka > 0) {
						$row3 = Cache::srv_spremenljivka($spremenljivka);
						$vrstni_red = $row3['vrstni_red'];

						$sql3 = sisplet_query("UPDATE srv_spremenljivka SET vrstni_red = vrstni_red+1 WHERE gru_id = '$grupa' AND vrstni_red >= '$vrstni_red'");
					} else {
						$sql3 = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id='$grupa'");
						$nums = mysqli_num_rows($sql3);
						$vrstni_red = $nums +1;
					}
				}

				//ustvarimo novo vprasanje na pravem mestu
				$spr_id = $this->SurveyAdmin->nova_spremenljivka($grupa, $row['vrstni_red'], $vrstni_red);

			} else { // ce mamo branching, je treba dodati tudi v srv_branching

				//include_once ('Branching.php');
				$Branching = new Branching($this->anketa);

				$Branching->spremenljivka_new($spremenljivka);

				$Branching->repare_vrstni_red();

			}

			$this->SurveyAdmin->prestevilci();
			if (isset($_POST['full_screen']) && $_POST['full_screen'] == 'true') { // v fullscreenu vrnemo samo id nove spremenljivke
				echo $spr_id;
			} else { // v normalnem načinu vrenmo html editmode vprašanja
				if ($rowb['branching'] == 0)
					$movable = ' movable';
				else
					$movable = '';

				// prikažemo vprašanje v edit načinu
				echo '    <div id="spremenljivka_' . $spr_id . '" class="spremenljivka' . $movable . '">';
				$this->SurveyAdmin->vprasanje_edit($spr_id);
				echo '    </div> <!-- /spremenljivka_' . $spr_id . ' -->';
			}
		}
		elseif ($_GET['a'] == 'refresh_grupe') {
			$this->SurveyAdmin->grupe();
		}
		elseif ($_GET['a'] == 'refresh_right_panel') {
			global $site_url;
			echo $site_url . 'admin/survey/index.php?anketa=' . $anketa . '&grupa='.$grupa;
		}
		elseif ($_GET['a'] == 'brisi_spremenljivko') {
			$this->ajax_brisi_spremenljivko();

		}
		elseif ($_GET['a'] == 'nova_vrednost') {
			Common::updateEditStamp();

			$sql = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$spremenljivka' AND vrstni_red>0");
			$row = mysqli_fetch_array($sql);
			$nums = mysqli_num_rows($sql);
			$vrstni_red = $nums +1;

			$row1 = Cache::srv_spremenljivka($spremenljivka);

			$variable = $vrstni_red;	// tole se itak popravi v prestevilci()

			// če smo postali polje undecided, rejected inappropriate
			$_otherStatus = array (
			99,
			98,
			97
			);
			$_otherStatusFields = array (
			99 => 'undecided',
			98 => 'rejected',
			97 => 'inappropriate'
			);
			$_otherStatusDefaults = array (
			99 => 'Ne vem',
			98 => 'Zavrnil',
			97 => 'Neustrezno'
			);

			if (!in_array($other, $_otherStatus)) { // nismo kreirali polja 99,98,97
				if ($other == 1)
					$naslov = $lang['srv_other'] . ':';
				else
					$naslov = '';
				// vrednost dodamo v tabelo
				$sql = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red, other) " .
				"VALUES ('', '$spremenljivka', '$naslov', '$variable', '$vrstni_red', '$other')");
			} else {
				// polja 99,98,97 damo v tabelo srv_vrednost smo za spremenljivke tipa: 1,2,3,4,7,8
				if (in_array($row1['tip'], array (
				1,
				2,
				3,
				4,
				7,
				8
				))) { // za tipe vprašanj 1,2,3 dodamo variablo v tabelo srv_vrednost
					// po novem so neopredeljene vrednosti negativne : -99,-98,-97
					$_otherVariables = array ( 99=>'-99',98=>'-98',97=>'-97');

					$variable = $_otherVariables[$other];
					$naslov = $_otherStatusDefaults[$other];
					$sql = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red, other) " .
					"VALUES ('', '$spremenljivka', '$naslov', '$variable', '$vrstni_red', '$other')");
				}

				// spremenimo nastavitev v srv_spremenljvka
				if ($row1[$_otherStatusFields[$other]] == 0) {
					$_updateState = $_otherStatusFields[$other] . "='1'";
				} else {
					$_updateState = $_otherStatusFields[$other] . "='0'";
					// pobrišemo spremenljivkko če je nastavljena
					$sqlDelete = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id = '" . $spremenljivka . "' AND other = '" . $other . "'");
				}

				// nardimo updejt posameznega polja ( undecided, rejected inappropriate) v tabeli srv_spremenljivka
				$sql = sisplet_query("UPDATE srv_spremenljivka SET $_updateState WHERE id='$spremenljivka'");
				// enako mormo updejtat kadar pobrišemo vrednost preko gumba -
			}

			// dodamo vrednosti -4 za novo variablo k že vpisanim odgovorom
			// multigridu dodamo vrednost -4
			if ($row1['tip'] == 6) {
				$sql = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$spremenljivka'");
				$sql1 = sisplet_query("SELECT id FROM srv_user WHERE ank_id='$anketa'");
				while ($row1 = mysqli_fetch_assoc($sql1)) {
					mysqli_data_seek($sql, 0);
					while ($row = mysqli_fetch_assoc($sql)) {
						$s = sisplet_query("INSERT INTO srv_data_grid".$this->db_table." (spr_id, vre_id, usr_id, grd_id) VALUES ('$spremenljivka', '$row[id]', '$row1[id]', '-4')");
					}
				}
			}
			if ($row1['tip'] == 16) { // multicheckbox
				$sql = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$spremenljivka'");
				$sql1 = sisplet_query("SELECT id FROM srv_user WHERE ank_id='$anketa'");
				while ($row1 = mysqli_fetch_assoc($sql1)) {
					mysqli_data_seek($sql, 0);
					while ($row = mysqli_fetch_assoc($sql)) {
						$s = sisplet_query("INSERT INTO srv_data_grid".$this->db_table." (spr_id, vre_id, usr_id, grd_id) VALUES ('$spremenljivka', '$row[id]', '$row1[id]', '-4')");
					}
				}
			}

			if ($row1['tip'] == 17) {
				$sql = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$spremenljivka'");
				$sql1 = sisplet_query("SELECT id FROM srv_user WHERE ank_id='$anketa'");
				while ($row1 = mysqli_fetch_assoc($sql1)) {
					mysqli_data_seek($sql, 0);
					while ($row = mysqli_fetch_assoc($sql)) {
						$s = sisplet_query("INSERT INTO srv_data_rating (spr_id, vre_id, usr_id, vrstni_red) VALUES ('$spremenljivka', '$row[id]', '$row1[id]', '-4')");
					}
				}
			}

			$this->SurveyAdmin->prestevilci($spremenljivka);
			$this->SurveyAdmin->vprasanje_edit($spremenljivka);

		}
		elseif ($_GET['a'] == 'edit_vrednost') {
			Common::updateEditStamp();
			$sql = sisplet_query("SELECT variable FROM srv_vrednost WHERE id ='$vrednost'");
			$row = mysqli_fetch_array($sql);
			if ($row['variable'] != $variable)
				$variable_custom = ", variable_custom='1' ";
			else
				$variable_custom = '';

			$sql = sisplet_query("UPDATE srv_vrednost SET naslov = '$naslov', naslov2 ='$naslov2', variable='$variable' $variable_custom WHERE id='$vrednost'");

		}
		elseif ($_GET['a'] == 'edit_vrednost_size') {
			Common::updateEditStamp();

			$sql = sisplet_query("UPDATE srv_vrednost SET size = '$size' WHERE id = '$vrednost'");

			$this->SurveyAdmin->vprasanje_edit($spremenljivka);
		}
		elseif ($_GET['a'] == 'edit_vsota') {
			Common::updateEditStamp();

			$sql = sisplet_query("UPDATE srv_spremenljivka SET vsota = '$vrednost' WHERE id='$spremenljivka'");
		}
		elseif ($_GET['a'] == 'edit_limit') {
			Common::updateEditStamp();

			if($vrednost == "")
				$vrednost = 0;
			if($min == "")
				$min = 0;

			$sql = sisplet_query("UPDATE srv_spremenljivka SET vsota_limit = '$vrednost', vsota_min = '$min' WHERE id='$spremenljivka'");
		}
		elseif ($_GET['a'] == 'edit_vsota_omejitve') {
			Common::updateEditStamp();
			
			$sql = sisplet_query("UPDATE srv_spremenljivka SET vsota_limittype = '$tip' WHERE id='$spremenljivka'");

			$this->SurveyAdmin->display_vsota_omejitve($spremenljivka, $tip);
		}
		elseif ($_GET['a'] == 'edit_spremenljivka_vsota_reminder') {
			Common::updateEditStamp();

			$sql = sisplet_query("UPDATE srv_spremenljivka SET vsota_reminder = '$reminder' WHERE id='$spremenljivka'");
		}
		elseif ($_GET['a'] == 'editor_vrednost') {

			$sql = sisplet_query("SELECT naslov, naslov2, variable, spr_id FROM srv_vrednost WHERE id = '$vrednost'");
			$row = mysqli_fetch_array($sql);

			echo '<p><b>' . $lang['srv_editirajvrednost'] . '</b></p>';
			echo '<form action="">';
			echo '<textarea name="naslov" id="naslovvrednost_' . $vrednost . '">' . $row['naslov'] . '</textarea>';
			echo '<input type="hidden" name="naslov2" id="naslov2_' . $vrednost . '" value="' . $row['naslov2'] . '">';
			echo '<input type="hidden" name="variable" id="variable_' . $vrednost . '" value="' . $row['variable'] . '">';
			echo '<input type="submit" value="' . $lang['srv_zapri'] . '"
			onclick="javascript:editor_vrednost_close(\'' . $vrednost . '\', \'' . $row['spr_id'] . '\'); return false;">';
			echo '</form>';
		}
		elseif ($_GET['a'] == 'editor_note') {
			$row = Cache::srv_spremenljivka($spremenljivka);

			echo '<p><b>' . $lang['srv_editirajopombo'] . '</b></p>';
			
			echo '<form action="">';
			echo '<textarea name="naslov" id="naslovvnote_' . $spremenljivka . '">' . $row['info'] . '</textarea>';
			echo '<input type="submit" value="' . $lang['srv_zapri'] . '"
					onclick="javascript:editor_note_close(\'' . $spremenljivka . '\'); return false;">';
			echo '</form>';
		}
		elseif ($_GET['a'] == 'editor_note_save') {

			$info = $_POST['content'];
			$sqlUpdate = sisplet_query("UPDATE srv_spremenljivka SET info ='" . $info . "' WHERE id = '" . $spremenljivka . "'");
		}
		elseif ($_GET['a'] == 'brisi_vrednost') {
			Common::updateEditStamp();

			$sql = sisplet_query("SELECT spr_id, other FROM srv_vrednost WHERE id = '$vrednost'");
			$row = mysqli_fetch_array($sql);
			$spremenljivka = $row['spr_id'];

			$sql = sisplet_query("DELETE FROM srv_vrednost WHERE id='$vrednost'");

			// če je other 99,98,97 moramo nastavit ustrezno polje še v srv_spremenljivka na 0
			$_otherStatus = array (
			99,
			98,
			97
			);
			$_otherStatusFields = array (
			99 => 'undecided',
			98 => 'rejected',
			97 => 'inappropriate'
			);
			if (in_array($row['other'], $_otherStatus))
				$sqlUpdate = sisplet_query("UPDATE srv_spremenljivka SET " . $_otherStatusFields[$row['other']] . "=0 WHERE id = '" . $spremenljivka . "'");

			$this->SurveyAdmin->repareVrednost($spremenljivka);

			$this->SurveyAdmin->prestevilci($spremenljivka);
			$this->SurveyAdmin->vprasanje_edit($spremenljivka);

		}
		elseif ($_GET['a'] == 'edit_gridvrednost') {
			Common::updateEditStamp();

			$sql = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$spremenljivka' AND vrstni_red='$grid'");
			$row = mysqli_fetch_array($sql);

			if ($row != FALSE) {

				//poiscemo id grida, ki ga zelimo popraviti
				$id = $row['id'];

				$sql = sisplet_query("UPDATE srv_grid SET naslov = '$naslov' WHERE id='$id' AND spr_id='$spremenljivka'");

				//za popravljanje ze obstojecih vprasanj - nastavinmo variablo na isto kot je pozicija grida
				if ($row['variable'] == '') {
					$sql = sisplet_query("UPDATE srv_grid SET variable = '$grid' WHERE id='$id' AND spr_id='$spremenljivka'");
				}

				//za popravljanje ze obstojecih vprasanj - nastavinmo vrstni red na isto kot je pozicija grida
				if ($row['vrstni_red'] == 0) {
					$sql = sisplet_query("UPDATE srv_grid SET vrstni_red = '$grid' WHERE id='$id' AND spr_id='$spremenljivka'");
				}

			} else {
				$sql = sisplet_query("SELECT MAX(id) FROM srv_grid WHERE spr_id='$spremenljivka' ");
				$row = mysqli_fetch_array($sql);

				//nastavimo id na najvecji v vprasanju
				$id = $row['MAX(id)'] + 1;

				$sql1 = sisplet_query("INSERT INTO srv_grid (id, spr_id, naslov, vrstni_red, variable) VALUES ('$id', '$spremenljivka', '$naslov', '$grid', '$grid')");
			}
		}

		//editiranje variabel gridov
		elseif ($_GET['a'] == 'edit_grids') {
			Common::updateEditStamp();

			$row = Cache::srv_spremenljivka($spremenljivka);
			
			if ($row['grids_edit'] == 1)
				$sql1 = sisplet_query("UPDATE srv_spremenljivka SET grids_edit='0' WHERE id='$spremenljivka'");
			else
				$sql1 = sisplet_query("UPDATE srv_spremenljivka SET grids_edit='1' WHERE id='$spremenljivka'");

			$this->SurveyAdmin->vprasanje_edit($spremenljivka);
		}

		elseif ($_GET['a'] == 'edit_gridID') {
			Common::updateEditStamp();

			$sql = sisplet_query("UPDATE srv_grid SET variable = '$grid' WHERE id='$grd_id' AND spr_id='$spremenljivka'");
		}

		elseif ($_GET['a'] == 'edit_grid_number') {
			Common::updateEditStamp();

			$sql = sisplet_query("UPDATE srv_spremenljivka SET grids = '$grids' WHERE id='$spremenljivka'");

			//dodamo manjkajoce gride v bazo
			$this->SurveyAdmin->addMissingGrids($spremenljivka);
			$this->SurveyAdmin->vprasanje_edit($spremenljivka);

		}

		elseif ($_GET['a'] == 'edit_spremenljivka') {
			Common::updateEditStamp();

			if (strtolower(substr($naslov, 0, 3)) != '<p>' && strtolower(substr($naslov, -4)) != '</p>' && strrpos($naslov, '<p>') === false) {
				//$naslov = '<p>'.nl2br($naslov).'</p>';
				$naslov = '<p>' . str_replace(NEW_LINE, "</p>\n<p>", $naslov) . '</p>';
			}

			// Počistimo opombo
			$info = trim(strip_tags($info));
			
			$sql = sisplet_query("UPDATE srv_spremenljivka SET naslov = '$naslov', info='$info' WHERE id='$spremenljivka'");

			if ($_REQUEST['normalmode'] == 1)
				$this->SurveyAdmin->vprasanje($spremenljivka);

		}
		elseif ($_GET['a'] == 'edit_spremenljivka_label') {
			Common::updateEditStamp();

			$sql = sisplet_query("UPDATE srv_spremenljivka SET naslov = '$naslov' WHERE id='$spremenljivka'");
			$this->SurveyAdmin->vprasanje($spremenljivka);

			$row = Cache::srv_spremenljivka($spremenljivka);
			
			if ($row['variable'] != $variable) { 
				$variable_custom = ", variable_custom='1' "; 
			} else { 
				$variable_custom = ''; 
			}
		
		}
		elseif ($_GET['a'] == 'edit_spremenljivka_variable') {
			Common::updateEditStamp();

			$row = Cache::srv_spremenljivka($spremenljivka);
			
			if ($row['variable'] != $variable)
				$variable_custom = ", variable_custom='1' ";
			else
				$variable_custom = '';

			$sql = sisplet_query("UPDATE srv_spremenljivka SET variable='$variable' $variable_custom WHERE id='$spremenljivka'");

			$this->SurveyAdmin->check_spremenljivka_variable($spremenljivka, $variable);

		}
		elseif ($_GET['a'] == 'edit_spremenljivka_skala') {
			Common::updateEditStamp();
			
			# popravimo skalo spremenljivke
			# skala - 0 Ordinalna
			# skala - 1 Nominalna
			if ( isset($skala)) {
				$sql = sisplet_query("UPDATE srv_spremenljivka SET skala='".$skala."' WHERE id='$spremenljivka'");
			}
		}
		elseif ($_GET['a'] == 'edit_spremenljivka_tip') {
			Common::updateEditStamp();

			$row5 = Cache::srv_spremenljivka($spremenljivka);
			
			// pri tipu besedilo* nastavimo privzeto velikost na 1
			if ($tip == 21 && ($size == "" || $size== "undefined")) {
				$sql = sisplet_query("UPDATE srv_spremenljivka SET size='1' WHERE id='$spremenljivka'");
			}		
			else if ($row5['size'] < 3) { //popravimo velikost v primeru prehoda iz antonucci/number vprasanja
					$sql = sisplet_query("UPDATE srv_spremenljivka SET size='3' WHERE id='$spremenljivka'");
				}
				// kalkulacija ima vedno size 1
				if ($tip == 22) {
				$sql = sisplet_query("UPDATE srv_spremenljivka SET size='1' WHERE id='$spremenljivka'");
			}

			if ($size > 0)
				$_size = ", size='$size'";
			else
				$_size = "";

			// če smo dobili postali polje undecided, rejected inappropriate
			$_otherStatus = array (
			99,
			98,
			97
			);
			$_otherStatusFields = array (
			99 => 'undecided',
			98 => 'rejected',
			97 => 'inappropriate'
			);

			if ($undecided && in_array($undecided, $_otherStatus)) {
				$_updateState = "";
				// updejtamo sistemske variable če jih dobimo z ajaxa
				if ($row5[$_otherStatusFields[$undecided]] == 0)
					$_updateState .= ", " . $_otherStatusFields[$undecided] . "='1'";
				else
					$_updateState .= ", " . $_otherStatusFields[$undecided] . "='0'";
			}
			// nardimo updejt posameznega polja ( undecided, rejected inappropriate) v tabeli srv_spremenljivka
			$sql = sisplet_query("UPDATE srv_spremenljivka SET tip = '$tip' $_size $_updateState WHERE id='$spremenljivka'");

			// popravimo variable za m.grid in m.checkbox
			if ($tip == 6 || $tip == 16 || $tip == 19 || $tip == 20) {
				$this->SurveyAdmin->addMissingGrids($spremenljivka);
			}
			$row = Cache::srv_spremenljivka($spremenljivka);
			
			if (($row['tip'] <= 3 || $row['tip'] == 9 || $row['tip'] == 12 || $row['tip'] == 15 || $row['tip'] == 17 || $row['tip'] == 18) && ($size > 0 || $row5['tip'] != $tip)) {

				$sqlp = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id='$row[gru_id]'");
				$rowp = mysqli_fetch_array($sqlp);

				//if ($row['tip'] == 2 || $row['tip'] == 18) {
				if ($row['tip'] == 2 || $row['tip'] == 18 || $row['tip'] == 27) {
					sisplet_query("UPDATE srv_vrednost SET variable=CONCAT('$row5[variable]', CHAR(vrstni_red+96)) WHERE spr_id='$spremenljivka' AND other <= 1");
				} else {
					sisplet_query("UPDATE srv_vrednost SET variable=vrstni_red WHERE spr_id='$spremenljivka' AND other <= 1");
				}


				$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$spremenljivka' AND other <=1");
				$rows = mysqli_num_rows($sql1);
				if ($rows < $row['size']) {
					for ($i = 1; $i <= $row['size'] - $rows; $i++) {
						if ($row['tip'] == 2 || $row['tip'] == 18)
							$variable = $row5['variable'] . chr($i + $rows +96);
						else
							$variable = $i + $rows;
						$sql2 = sisplet_query("INSERT INTO srv_vrednost (spr_id, variable, vrstni_red) VALUES ('$spremenljivka', '$variable', '" . ($i + $rows) . "')");
					}

					$this->SurveyAdmin->repareVrednost($spremenljivka);
				}
				elseif ($rows > $row['size']) {

					$sql = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spremenljivka' AND naslov='' AND vrstni_red > '$row[size]' AND other <= 1");

					$this->SurveyAdmin->repareVrednost($spremenljivka);
				}

			} 
			else if (($row['tip'] == 6 || $row['tip'] == 16 || $row['tip'] == 19 || $row['tip'] == 20 || $row['tip'] == 18) && $row5['tip'] != $tip) {
				sisplet_query("UPDATE srv_vrednost SET variable=CONCAT('$row5[variable]', CHAR(vrstni_red+96)) WHERE spr_id='$spremenljivka' AND variable_custom='0' AND other <= 1");

				/* Pri enovnosnih poljih pobri"semo nepotrebne spremenljivke v bazi
				* to so:
				* 	-text		-> tip = 4
				* 	-label 		-> tip = 5
				* 	-number		-> tip = 7
				* 	-datum		-> tip = 8
				* */
			}
			else if ($row['tip'] == 7) { // number
				// preverimo če sploh in koliko variable rabimo
				$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$spremenljivka' AND other <=1");
				$rows = mysqli_num_rows($sql1);

				if ($rows < $row['size']) {
					for ($i = 1; $i <= $row['size'] - $rows; $i++) {
						if ($row['tip'] == 2)
							$variable = $row5['variable'] . chr($i + $rows +96);
						else
							$variable = $i + $rows;
						$sql2 = sisplet_query("INSERT INTO srv_vrednost (spr_id, variable, vrstni_red) VALUES ('$spremenljivka', '$variable', '" . ($i + $rows) . "')");
					}
				}
			}
			else if ($row['tip'] == 21) { // besedilo*
				// pobrisemo odvecne variable pri preklopu na besedilo*
				$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$spremenljivka'");
				$rows = mysqli_num_rows($sql1);

				if ($rows > $row['text_kosov'])
					$sql2 = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spremenljivka' AND vrstni_red > '$row[text_kosov]'");	
			} 
			else if (($row['tip'] == 4 || $row['tip'] == 5 || $row['tip'] == 8) && $row5['tip'] != $tip) {
				//					$sql = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spremenljivka' AND naslov='' AND vrstni_red > '1'");
				//				$sql = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spremenljivka' AND other <= 1");
				$this->SurveyAdmin->repareVrednost($spremenljivka);
			}

			if (($row['tip'] == 7 || $row['tip'] == 12 || $row['tip'] == 15) && $row['size'] > 2) {
				$sql = sisplet_query("UPDATE srv_spremenljivka SET size=1 WHERE id='$spremenljivka'");
				//				$sql = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spremenljivka' AND naslov='' AND vrstni_red > '1'");
				$this->SurveyAdmin->repareVrednost($spremenljivka);
			}

			if ($row['tip'] == 9) {
				$sql = sisplet_query("UPDATE srv_spremenljivka SET antonucci=1, size=0 WHERE id='$spremenljivka'");
			}

			if ($row['tip'] == 10 || $row['tip'] == 11 || $row['tip'] == 12 || $row['tip'] == 13 || $row['tip'] == 14 || $row['tip'] == 15) {
				$sql = sisplet_query("UPDATE srv_spremenljivka SET podpora=1 WHERE id='$spremenljivka'");
			}

			//dodajanje page-breakov pri SN-imena, SN-social in SN-povezave
			if ($row['tip'] == 9 || $row['tip'] == 10 || $row['tip'] == 13) {
				$sql = sisplet_query("UPDATE srv_branching SET pagebreak=1 WHERE element_spr='$spremenljivka' AND ank_id='".$this->anketa."'");

				//$sql = sisplet_query("INSERT INTO srv_branching (ank_id, element_spr, vrstni_red, pagebreak) VALUES ('$this->anketa', '$spremenljivka', 0 ,'1') ON DUPLICATE KEY UPDATE pagebreak=1");

				$sqlX = sisplet_query("SELECT g.ank_id FROM srv_grupa g, srv_spremenljivka s WHERE s.id='$spremenljivka' AND (g.id=s.gru_id) ");
				$rowX = mysqli_fetch_array($sqlX);

				$anketa = $rowX['ank_id'];

				//include_once ('Branching.php');
				$b = new Branching($anketa);

				$b->repare_vrstni_red();
				$b->trim_grupe();

				if ($this->branching == 0) {
					$sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$anketa'");

					$nums = mysqli_num_rows($sql);
					$vrstni_red = $nums + 1;

					$sql = sisplet_query("INSERT INTO srv_grupa (id, ank_id, naslov, vrstni_red) VALUES ('', '$anketa', '$lang[srv_stran] $vrstni_red', '$vrstni_red')");
					$insert_id = mysqli_insert_id($GLOBALS['connect_db']);
					
					// Ce dodamo 4. stran vklopimo progress indicator (pri 3 straneh ali manj je po default izklopljen)anketa
					if($vrstni_red == 4){
						$sqlP = sisplet_query("UPDATE srv_anketa SET progressbar='1' WHERE id='$anketa'");
					}
				}
			}

			// dodamo -4 multigridu in multicheckboxu
			if ( $tip == 2 ||  $tip == 6 || $tip == 16) {
				$sql = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$spremenljivka'");
				$sql1 = sisplet_query("SELECT id FROM srv_user WHERE ank_id='$anketa'");
				while ($row1 = mysqli_fetch_array($sql1)) {
					if ($tip == 2) // checkboxu damo -4 samo en zapis -4 za vse vrednosti
						$s = sisplet_query("INSERT INTO srv_data_vrednost".$this->db_table." (spr_id, vre_id, usr_id) VALUES ('$spremenljivka', '-4', '$row1[id]')");

					mysqli_data_seek($sql, 0);
					while ($row = mysqli_fetch_array($sql)) {

						if ($tip == 6)
							$s = sisplet_query("INSERT INTO srv_data_grid".$this->db_table." (spr_id, vre_id, usr_id, grd_id) VALUES ('$spremenljivka', '$row[id]', '$row1[id]', '-4')");
						if ($tip == 16)
							$s = sisplet_query("INSERT INTO srv_data_checkgrid".$this->db_table." (spr_id, vre_id, usr_id, grd_id) VALUES ('$spremenljivka', '$row[id]', '$row1[id]', '-4')");
					}
				}
			}

			$this->SurveyAdmin->vprasanje_edit($spremenljivka);
		}

		elseif ($_GET['a'] == 'edit_spremenljivka_textboxes') {

			Common::updateEditStamp();
			$s = sisplet_query("UPDATE srv_spremenljivka SET text_kosov='$size' WHERE id='$spremenljivka'");

			$rowV = Cache::srv_spremenljivka($spremenljivka);
			
			$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$spremenljivka' AND other <=1");
			$rows = mysqli_num_rows($sql1);
			if ($rows < $size) {
				for ($i = 1; $i <= $size - $rows; $i++) {
					$variable = $rowV['variable'].chr(96 + $i + $rows);
					$sql2 = sisplet_query("INSERT INTO srv_vrednost (spr_id, naslov, variable, vrstni_red) VALUES ('$spremenljivka', '', '$variable', '" . ($i + $rows) . "')");
				}

				$this->SurveyAdmin->repareVrednost($spremenljivka);
			} else if ($rows > $size) {
					# pobrisemo odvecna polja
					$sql = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spremenljivka' AND vrstni_red > '$size'");				
				}

				$this->SurveyAdmin->vprasanje_edit($spremenljivka);
		}

		elseif ($_GET['a'] == 'edit_spremenljivka_text_orientation') {
			Common::updateEditStamp();
			$s = sisplet_query("UPDATE srv_spremenljivka SET text_orientation='$orientation' WHERE id='$spremenljivka'");

			$this->SurveyAdmin->vprasanje_edit($spremenljivka);
		}

		elseif ($_GET['a'] == 'edit_spremenljivka_antonucci') {
			Common::updateEditStamp();
			$s = sisplet_query("UPDATE srv_spremenljivka SET antonucci='$antonucci' WHERE id='$spremenljivka'");
		}

		elseif ($_GET['a'] == 'edit_spremenljivka_design') {
			Common::updateEditStamp();
			$s = sisplet_query("UPDATE srv_spremenljivka SET design='$design', cela=2, decimalna=0 WHERE id='$spremenljivka'");
			$this->SurveyAdmin->vprasanje_edit($spremenljivka);
		}

		elseif ($_GET['a'] == 'edit_spremenljivka_ranking_k') {
			Common::updateEditStamp();
			$s = sisplet_query("UPDATE srv_spremenljivka SET ranking_k='$size' WHERE id='$spremenljivka'");
		}

		elseif ($_GET['a'] == 'edit_spremenljivka_podpora') {
			Common::updateEditStamp();
			$s = sisplet_query("UPDATE srv_spremenljivka SET podpora='$podpora' WHERE id='$spremenljivka'");
		}

		elseif ($_GET['a'] == 'edit_spremenljivka_number') {
			Common::updateEditStamp();
			$s = sisplet_query("UPDATE srv_spremenljivka SET cela='$cela', decimalna='$decimalna', enota='$enota' WHERE id='$spremenljivka'");
			$this->SurveyAdmin->vprasanje_edit($spremenljivka);
		}
		elseif ($_GET['a'] == 'edit_spremenljivka_param') {
			$row = Cache::srv_spremenljivka($spremenljivka);
			Common::updateEditStamp();
			
			// v polje params spremenljivke shranimo spremembo parametra
			$newParams = new enkaParameters($row['params']);
			$newParams->set($paramName, $paramValue);

			$s = sisplet_query("UPDATE srv_spremenljivka SET params='" . $newParams->getString() . "' WHERE id='$spremenljivka'");

			$this->SurveyAdmin->repareVrednost($spremenljivka);
			$this->SurveyAdmin->vprasanje_edit($spremenljivka);

		}
		elseif ($_GET['a'] == 'spremenljivka_random') {
			Common::updateEditStamp();
			/** random polje:
			* 	0 = sort po vrstnem redu
			*  1 = sort random
			* 	2 = sort po abecedi naraščajoče
			* 	3 = sort po abecedi padajoče
			*/

			$row = Cache::srv_spremenljivka($spremenljivka);
			
			if ($row['random'] == 3) {
				$random = 0;
			} else {
				$random = $row['random'] + 1;
			}
			$other = ($random) ? " AND other='0'" : "";

			$sql = sisplet_query("UPDATE srv_spremenljivka SET random = '$random' WHERE id='$spremenljivka'");
			$sql1 = sisplet_query("UPDATE srv_vrednost SET random = '$random' WHERE spr_id ='$spremenljivka' $other");
			$this->SurveyAdmin->vprasanje_edit($spremenljivka);

		}
		elseif ($_GET['a'] == 'random_vrednost') {
			Common::updateEditStamp();
			/** random polje:
			* 	0 = sort po vrstnem redu
			*  1 = sort random
			* 	2 = sort po abecedi naraščajoče
			* 	3 = sort po abecedi padajoče
			*/

			$sql = sisplet_query("SELECT random FROM srv_vrednost WHERE id = '$vrednost'");
			$row = mysqli_fetch_array($sql);

			if ($row['random'] == 3) {
				$random = 0;
			} else {
				$random = $row['random'] + 1;
			}

			sisplet_query("UPDATE srv_vrednost SET random = '$random' WHERE id = '$vrednost'");
			$this->SurveyAdmin->random_vrednost($vrednost);

		}
		elseif ($_GET['a'] == 'spremenljivka_stat') {
			Common::updateEditStamp();

			$row = Cache::srv_spremenljivka($spremenljivka);
			
			if ($row['stat'] == 1) {
				$stat = 0;
			} else {
				$stat = 1;
			}

			$sql = sisplet_query("UPDATE srv_spremenljivka SET stat = '$stat' WHERE id='$spremenljivka'");

			$this->SurveyAdmin->spremenljivka_stat($spremenljivka);
		}
		elseif ($_GET['a'] == 'spremenljivka_orientation') {
			Common::updateEditStamp();

			$row = Cache::srv_spremenljivka($spremenljivka);
			
			if ($row['orientation'] == 1) {
				$orientation = 0;
			} else if ($row['orientation'] == 0) {
					$orientation = 2;
				} else if ($row['orientation'] == 2) {
						$orientation = 1;
					}
					$sql = sisplet_query("UPDATE srv_spremenljivka SET orientation = '$orientation' WHERE id='$spremenljivka'");

			$this->SurveyAdmin->spremenljivka_orientation($spremenljivka);
		}
		elseif ($_GET['a'] == 'spremenljivka_checkbox_hide') {
			Common::updateEditStamp();

			$row = Cache::srv_spremenljivka($spremenljivka);
			
			if ($row['checkboxhide'] == 1) {
				$checkboxhide = 0;
			} else {
				$checkboxhide = 1;
			}

			$sql = sisplet_query("UPDATE srv_spremenljivka SET checkboxhide = '$checkboxhide' WHERE id='$spremenljivka'");

			$this->SurveyAdmin->spremenljivka_checkbox_hide($spremenljivka);

		}
		elseif ($_GET['a'] == 'spremenljivka_reminder') {
			Common::updateEditStamp();

			$row = Cache::srv_spremenljivka($spremenljivka);
			
			if ($row['reminder'] == 0) {
				$reminder = 1;
			}
			elseif ($row['reminder'] == 1) {
				$reminder = 2;
			} else {
				$reminder = 0;
			}

			$sql = sisplet_query("UPDATE srv_spremenljivka SET reminder = '$reminder' WHERE id='$spremenljivka'");

			$this->SurveyAdmin->spremenljivka_reminder($spremenljivka);

		}
		elseif ($_GET['a'] == 'spremenljivka_sistem') {
			Common::updateEditStamp();

			$row = Cache::srv_spremenljivka($spremenljivka);
			
			if ($row['sistem'] == 1) {
				$sistem = 0;
			} else {
				$sistem = 1;
			}

			$sql = sisplet_query("UPDATE srv_spremenljivka SET sistem = '$sistem' WHERE id='$spremenljivka'");

			$this->SurveyAdmin->spremenljivka_sistem($spremenljivka);

		}
		elseif ($_GET['a'] == 'spremenljivka_visible') {
			Common::updateEditStamp();

			$row = Cache::srv_spremenljivka($spremenljivka);
			
			if ($row['visible'] == 1) {
				$visible = 0;
			} else {
				$visible = 1;
			}

			$sql = sisplet_query("UPDATE srv_spremenljivka SET visible = '$visible' WHERE id='$spremenljivka'");

			$this->SurveyAdmin->spremenljivka_visible($spremenljivka);

		}
		elseif ($_GET['a'] == 'spremenljivka_textfield') {
			Common::updateEditStamp();

			$row = Cache::srv_spremenljivka($spremenljivka);
			
			if ($row['textfield'] == 1) {
				$textfield = 0;
				$label = '';
			} else {
				$textfield = 1;
				$label = $lang['srv_other'] . ':';
			}

			$sql = sisplet_query("UPDATE srv_spremenljivka SET textfield = '$textfield', textfield_label='$label' WHERE id='$spremenljivka'");

			$this->SurveyAdmin->vprasanje_edit($spremenljivka);

		}
		elseif ($_GET['a'] == 'spremenljivka_timer') {
			Common::updateEditStamp();

			$sql = sisplet_query("UPDATE srv_spremenljivka SET timer = '$timer' WHERE id='$spremenljivka'");

			$this->SurveyAdmin->spremenljivka_timer($spremenljivka);

		}
		elseif ($_GET['a'] == 'edit_textfield') {
			Common::updateEditStamp();

			sisplet_query("UPDATE srv_vrednost SET naslov = '$label' WHERE id='$vrednost'");

		}
		elseif ($_GET['a'] == 'vrstnired_vrednost') {
			Common::updateEditStamp();

			$exploded = explode('&', $serialize);

			$i = 1;
			foreach ($exploded AS $key) {
				$key = str_replace('spremenljivka_', '', $key);
				$explode = explode('[]=', $key);
				$sql = sisplet_query("UPDATE srv_vrednost SET vrstni_red = '$i' WHERE id = '$explode[1]'");
				$i++;
			}

			$this->SurveyAdmin->prestevilci();	// TODO, tukaj bi moral biti id spremenljivke

		}
		elseif ($_GET['a'] == 'vrstnired_vprasanje') {
			Common::updateEditStamp();

			$exploded = explode('&', $serialize);

			if ($_POST['grupa'] != 'all') {
				// sortiramo samo v okviru ene strani
				$i = 1;
				foreach ($exploded AS $key) {
					$key = str_replace('vprasanja', '', $key);
					$explode = explode('[]=', $key);
					$sql = sisplet_query("UPDATE srv_spremenljivka SET vrstni_red = '$i' WHERE id = '$explode[1]'");
					$i++;
				}
			} else {


				// da ne updejtamo vseh spremenljivk, popravljamo podatke samo pri "prizadetih" grupah
				$moved_spr = str_replace('spremenljivka_', '', $_POST['moved']);

				// id stare gurpe
				$strGr = "select gru_id FROM srv_spremenljivka where id = '".$moved_spr."'";
				$sqlGr = sisplet_query($strGr);
				$rowGr = mysqli_fetch_assoc($sqlGr);
				$oldPageId = $rowGr['gru_id'];

				// id nove grupe
				$newPageId = str_replace('fieldset_page_', '', $_POST['topage']);

				$grNew = array();
				$grOld = array();
				// vse psremenljvke ki so v novi gupi
				$strGrNew = "select id FROM srv_spremenljivka where gru_id = '".$newPageId."'";
				$sqlGrNew = sisplet_query($strGrNew);
				while ($rowGrNew = mysqli_fetch_assoc($sqlGrNew)) {
					$grNew[$rowGrNew['id']] = $newPageId;
				}
				// vse spremenljivke ki so v stari grupi
				$strGrOld = "select id FROM srv_spremenljivka where gru_id = '".$oldPageId."'";
				$sqlGrOld = sisplet_query($strGrOld);
				while ($rowGrOld = mysqli_fetch_assoc($sqlGrOld)) {
					$grOld[$rowGrOld['id']] = $oldPageId;
				}
				$serialized = array();
				// zloopamo skozi prejeta vprašanja in popravimo grupe
				$grupa_test = null;
				$vrstni_red = 1;
				foreach ($exploded AS $key) {
					$key = str_replace('spremenljivka[]=', '', $key);
					// ce je $key spremenljivka ki jo premikamo ji dodelimo novo grupo
					if ($moved_spr == $key) {
						$grupa = $newPageId;
					} else {
						$grupa = isset($grNew[$key])
						? $grNew[$key]
						: (isset($grOld[$key])
						? $grOld[$key]
						: null);
					}
					if ($grupa_test != $grupa) {
						// resetriamo couner
						$vrstni_red = 1;
						$grupa_test = $grupa;
					}
					if ($grupa != null) {
						$serialized[$key] = array('id'=>$key, 'grupa'=>$grupa, 'vrstni_red'=>$vrstni_red);
						$vrstni_red ++;

					}
				}
				// updejtamo serializirane podatke
				foreach ( $serialized as $key => $value ) {
					$sql = sisplet_query("UPDATE srv_spremenljivka SET gru_id='".$value['grupa']."', vrstni_red = '".$value['vrstni_red']."' WHERE id = '".$value['id']."'");
				}
			}
			$this->SurveyAdmin->prestevilci();

		}
		elseif ($_GET['a'] == 'vrstnired_vprasanje_forma') {
			Common::updateEditStamp();

			$exploded = explode('&', $serialize);

			$i = 1;
			foreach ($exploded AS $key) {
				$key = str_replace('vprasanja', '', $key);
				$explode = explode('[]=', $key);
				$sql = sisplet_query("UPDATE srv_spremenljivka SET vrstni_red = '$i' WHERE id = '$explode[1]'");
				$i++;
			}

			$this->SurveyAdmin->prestevilci();
			$this->SurveyAdmin->vprasanja();

		}
		elseif ($_GET['a'] == 'vrstnired_grupa') {
			Common::updateEditStamp();

			$exploded = explode('&', $serialize);

			$i = 1;
			foreach ($exploded AS $key) {
				$key = str_replace('grupe', '', $key);
				$explode = explode('[]=', $key);
				$sql = sisplet_query("UPDATE srv_grupa SET vrstni_red = '$i' WHERE id = '$explode[1]'");
				$i++;
			}

			$this->SurveyAdmin->prestevilci();

		}
		elseif ($_GET['a'] == 'premakni_vprasanje') {
			Common::updateEditStamp();

			$row = Cache::srv_spremenljivka($spremenljivka);
			$old_grupa = $row['gru_id'];

			$sql = sisplet_query("SELECT MAX(vrstni_red) AS max FROM srv_spremenljivka WHERE gru_id='$grupa'");
			$row = mysqli_fetch_array($sql);
			$vrstni_red = $row['max'] + 1;

			$sql = sisplet_query("UPDATE srv_spremenljivka SET gru_id='$grupa', vrstni_red='$vrstni_red' WHERE id='$spremenljivka'");

			$this->SurveyAdmin->repareSpremenljivka($old_grupa);
			$this->SurveyAdmin->repareSpremenljivka($grupa);

			$this->SurveyAdmin->prestevilci();

		}
		elseif ($_GET['a'] == 'intro_concl_fullscreeen') {

			//include_once ('Branching.php');
			$Branching = new Branching($this->anketa);

			echo '    <div id="spremenljivka_' . $_POST['introconcl'] . '" class="spremenljivka" style="margin-top:15px">';
			$Branching->introduction_conclusion($_POST['introconcl'], 1);
			echo '    </div> <!-- /spremenljivka_' . $_POST['introconcl'] . ' -->';

		}
		elseif ($_GET['a'] == 'editmode_spremenljivka') {
			$this->grupa = $grupa;
			$this->SurveyAdmin->vprasanje_edit($spremenljivka);
		}
		elseif ($_GET['a'] == 'editmode_spremenljivka_single') {
			$Branching = new Branching($this->anketa);
			$Branching->display();
		}

		elseif ($_GET['a'] == 'normalmode_spremenljivka') {

			$this->SurveyAdmin->vprasanje($spremenljivka);

		}
		elseif ($_GET['a'] == 'editmode_grupa') {

			if ($_POST['pages'] == "1") {
				$this->SurveyAdmin->showEditPageDiv($grupa, true);
			} else {
				$this->SurveyAdmin->grupa_edit($grupa);
			}

		}
		elseif ($_GET['a'] == 'branch_editmode_grupa') {
			$this->SurveyAdmin->branch_grupa_edit($grupa, $spremenljivka);
		}
		elseif ($_GET['a'] == 'branch_normalmode_grupa') {
			$this->SurveyAdmin->branch_normalmode_grupa($grupa, $spremenljivka);
		}
		elseif ($_GET['a'] == 'normalmode_grupa') {

			$sql = sisplet_query("SELECT ank_id FROM srv_grupa WHERE id = '$grupa'");
			$row = mysqli_fetch_array($sql);
			$this->anketa = $row['ank_id'];

			$this->SurveyAdmin->grupa($grupa);

		}
		elseif ($_GET['a'] == 'copy_spremenljivka') {

			$this->SurveyAdmin->clipboard_display($spremenljivka);

		}
		elseif ($_GET['a'] == 'copy_remove') {

			$this->SurveyAdmin->clipboard_display(-1);

		}
		elseif ($_GET['a'] == 'edit_data_vrednost_ch') {
			Common::updateEditStamp();

			sisplet_query("DELETE FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$spr_id' AND vre_id='$vre_id' AND usr_id='$usr_id'");

			if ($value == 1) {
				$s = sisplet_query("INSERT INTO srv_data_vrednost".$this->db_table." (spr_id, vre_id, usr_id) VALUES ('$spr_id', '$vre_id', '$usr_id')");
			}

		}
		elseif ($_GET['a'] == 'edit_data_vrednost') {
			Common::updateEditStamp();

			sisplet_query("DELETE FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$spr_id' AND usr_id='$usr_id'");

			if ($vre_id != 0) {
				sisplet_query("INSERT INTO srv_data_vrednost".$this->db_table." (spr_id, vre_id, usr_id) VALUES ('$spr_id', '$vre_id', '$usr_id')");
			}

		}
		elseif ($_GET['a'] == 'edit_data_grid') {
			Common::updateEditStamp();

			sisplet_query("UPDATE srv_data_grid".$this->db_table." SET grd_id='$grd_id' WHERE spr_id='$spr_id' AND vre_id='$vre_id' AND usr_id='$usr_id'");

		}
		elseif ($_GET['a'] == 'edit_data_text') {
			Common::updateEditStamp();

			sisplet_query("DELETE FROM srv_data_text".$this->db_table." WHERE spr_id='$spr_id' AND vre_id='$vre_id' AND usr_id='$usr_id'");

			if ($value != '') {
				sisplet_query("INSERT INTO srv_data_text".$this->db_table." (spr_id, vre_id, usr_id, text) VALUES ('$spr_id', '$vre_id', '$usr_id', '$value')");

				/*if ($textfield == 1)
				sisplet_query("DELETE FROM srv_data_vrednost WHERE spr_id='$spr_id' AND usr_id='$usr_id'");*/
			}

		}
		elseif ($_GET['a'] == 'edit_data_delete') {
			Common::updateEditStamp();

			sisplet_query("DELETE FROM srv_user WHERE id = '$usr_id'");
			/* Ker imamo FK bi moralo avtomatsko pobrisati vse ostale vnose ( upam da res :) )
				sisplet_query("DELETE FROM srv_data_grid".$this->db_table." WHERE usr_id = '$usr_id'");
				sisplet_query("DELETE FROM srv_data_text".$this->db_table." WHERE usr_id = '$usr_id'");
				sisplet_query("DELETE FROM srv_data_vrednost".$this->db_table." WHERE usr_id = '$usr_id'");
				sisplet_query("DELETE FROM srv_data_checkgrid".$this->db_table." WHERE usr_id = '$usr_id'");
				sisplet_query("DELETE FROM srv_data_imena WHERE usr_id = '$usr_id'");
				sisplet_query("DELETE FROM srv_data_number WHERE usr_id = '$usr_id'");
				sisplet_query("DELETE FROM srv_data_rating WHERE usr_id = '$usr_id'");
				sisplet_query("DELETE FROM srv_data_textgrid".$this->db_table." WHERE usr_id = '$usr_id'");
				sisplet_query("DELETE FROM srv_user_grupa_active WHERE usr_id = '$usr_id'");
				sisplet_query("DELETE FROM srv_user_grupa WHERE usr_id = '$usr_id'");
				*/
		}
		elseif ($_GET['a'] == 'delete_all') {
			Common::updateEditStamp();

			$sql = sisplet_query("DELETE FROM srv_user WHERE ank_id = '$this->anketa'");
			//$sql = sisplet_query("SELECT * FROM srv_user WHERE ank_id = '$this->anketa'");
			//while ($row = mysqli_fetch_array($sql)) {

				//sisplet_query("DELETE FROM srv_user WHERE id = '$row[id]'");
				/* Ker imamo FK bi moralo avtomatsko pobrisati vse ostale vnose ( upam da res :) )
				sisplet_query("DELETE FROM srv_data_grid".$this->db_table." WHERE usr_id = '$row[id]'");
				sisplet_query("DELETE FROM srv_data_text".$this->db_table." WHERE usr_id = '$row[id]'");
				sisplet_query("DELETE FROM srv_data_vrednost".$this->db_table." WHERE usr_id = '$row[id]'");
				sisplet_query("DELETE FROM srv_data_checkgrid".$this->db_table." WHERE usr_id = '$row[id]'");
				sisplet_query("DELETE FROM srv_data_imena WHERE usr_id = '$row[id]'");
				sisplet_query("DELETE FROM srv_data_number WHERE usr_id = '$row[id]'");
				sisplet_query("DELETE FROM srv_data_rating WHERE usr_id = '$row[id]'");
				sisplet_query("DELETE FROM srv_data_textgrid".$this->db_table." WHERE usr_id = '$row[id]'");
				sisplet_query("DELETE FROM srv_user_grupa_active WHERE usr_id = '$row[id]'");
				sisplet_query("DELETE FROM srv_user_grupa WHERE usr_id = '$row[id]'");
				*/
			//}
			
			# pobrišemo še DATA datoteke in HTML -dashboard če obstajajo
			global $site_path;
			$folder = $site_path . EXPORT_FOLDER.'/';
			#pobrišemo header datoteko
			if (file_exists($folder.'export_header_'.$this->anketa.'_*.dat')) {
				unlink($folder.'export_header_'.$this->anketa.'_*.dat');
			}
			# pobrišemo data datoteko
			if (file_exists($folder.'export_data_'.$this->anketa.'_*.dat')) {
				unlink($folder.'export_data_'.$this->anketa.'_*.dat');
			}
			# pobrišemo dashboard
			if (file_exists($folder.'export_dashboard_'.$this->anketa.'_*.html')) {
				unlink($folder.'export_dashboard_'.$this->anketa.'_*.html');
			}
			
			echo 'index.php?anketa=' . $this->anketa . '&a='.A_COLLECT_DATA;
		}
		elseif ($_GET['a'] == A_REPORTI) { // ajax funkcije za analizo

			switch ($_GET['m']) {
				case M_ANALYSIS_STATISTICS :
					$options = array ();
					$options['startDate'] = (isset ($_POST['startDate'])) ? $_POST['startDate'] : null;
					$options['endDate'] = (isset ($_POST['endDate'])) ? $_POST['endDate'] : null;
					$options['type'] = (isset ($_POST['type'])) ? $_POST['type'] : null;
					//include_once ('Analiza.php');
					$analiza = new Analiza($this->anketa, $_GET['m']);
					$analiza->displayStats($options);
					break;
			}
		}
		elseif ($_GET['a'] == 'analizaDisplayData') { // ajax funkcije za analizo
			$podstran = $_POST['podstran'];
			SurveyAnalysis::Init($this->anketa);
			SurveyAnalysis::Display();

		} elseif ($_GET['a'] == 'anketa_active_refresh') {
			SurveyInfo :: getInstance()->SurveyInit($anketa);
			// vsilimo refresh podatkov
			SurveyInfo :: getInstance()->resetSurveyData();
			$row = SurveyInfo::getInstance()->getSurveyRow();

			# updejtjmo pošiljanje alertov
			SurveyAlert::getInstance()->Init($anketa, $global_user_id);
			SurveyAlert::getInstance()->prepareSendExpireAlerts();
			
			if ($_POST['folders'] && $_POST['folders'] == 'true') { // če smo na folderjih zlistamo folderje
				// osvezimo samo ikonico in ne celotnih map
				
				echo '<a href="#" onclick="anketa_active(\''.$this->anketa.'\',\''.(int)$row['active'].'\',\'true\'); return false;">' .
				'<img src="'.$site_url.'admin/survey/icons/icons/star_'.((int)$row['active']==1?'on':'off').'.png" alt="'.(int)$row['active'].'" title="'.((int)$row['active']==1?$lang['srv_anketa_active']:$lang['srv_anketa_noactive']).'" />'.
				'</a> ';
			} else {	
				$this->SurveyAdmin->displayAktivnost();
			}
			
		} elseif ($_GET['a'] == 'anketa_show_activation') {
			global $global_user_id;
			
			$folders = $_POST['folders'];
			
			# za koliko časa aktiviramo 
			$mth = 3;

			$starts = date("d.m.Y"); // danes
			$startsDB = date("Y-m-d"); // danes
			$cd = strtotime($starts);
			$expire = date('d.m.Y', mktime(0, 0, 0, date('m', $cd) + $mth, date('d', $cd), date('Y', $cd)));
			$expireDB = date('Y-m-d', mktime(0, 0, 0, date('m', $cd) + $mth, date('d', $cd), date('Y', $cd)));

			# preverimo ali ima uporabnik izklopljeno zaklepanje
			# polovimo nastavitve uporabnika
			
			UserSetting::getInstance()->Init($global_user_id);
			# ali zaklepamo anketo ob aktivaciji
			$lockSurvey = UserSetting::getInstance()->getUserSetting('lockSurvey');

			# aktiviramo anketo
			#avtomatsko aktiviramo anketo za 1 mesec in o tem obvestimo uporabnika.		
			$updateString = "UPDATE srv_anketa SET active='1', locked='".(int)$lockSurvey."', backup='0', starts='".$startsDB."', expire='".$expireDB."' WHERE id='$anketa'";
			$sql = sisplet_query($updateString) or die(mysqli_error($GLOBALS['connect_db']));

		    // Zapišemo vsako aktivacijo ankete po dnevih
            $activity_insert_string = "INSERT INTO srv_activity (sid, starts, expire, uid) VALUES('" . $anketa . "', '" . $startsDB . "', '" . $expireDB . "', '" . $global_user_id . "' )";
			$sql_insert = sisplet_query($activity_insert_string);
			
			# popravimo timestamp za regeneracijo dashboarda
			Common::getInstance()->Init($anketa);
	    	Common::getInstance()->updateEditStamp();
			
			# vsilimo refresh podatkov
			SurveyInfo :: getInstance()->resetSurveyData();
			$row = SurveyInfo::getInstance()->getSurveyRow();		
			
			# posljemo mail ob spremembi aktivnosti ankete
			SurveyAlert::getInstance()->Init($anketa, $global_user_id);
			SurveyAlert::getInstance()->sendMailActive();
			
			$gdpr = new GDPR();
			
			# Aktivacijski pop up za hierarhijo
			if(SurveyInfo::getInstance()->checkSurveyModule('hierarhija')){
				// Anketo zaklenemo
				sisplet_query("UPDATE srv_anketa SET locked='1' WHERE id = '$anketa'");

				// Popup
                echo '<div id="anketa_activate_note" class="divPopUp">';

                echo '<div class="popup_close"><a href="#" onClick="anketa_activate_save(\''.$this->anketa.'\',\''.$folders.'\'); return false;">✕</a></div>';
                
                echo '<h2>' . $lang['srv_hierarchy_activation_header'] . '.</h2>';

                echo '<div>' . $lang['srv_activation_expire'] . $expire . '</div>';
                echo '<div id="div_anketa_activate_more"><br/>';
                printf($lang['srv_activation_setting'], $anketa);
                echo '</div>';
        
                // Seznam uporabnikov na katere je bil poslan email
                // Dobimo samo uporabnike na zadnjem nivoju in to obvestilo prikažemo samo prvič ko se aktivira anketa
                if(SurveyInfo::getSurveyModules('hierarhija') == 1) {
                    $users_upravicen_do_evalvacije = (new \Hierarhija\Model\HierarhijaOnlyQuery())->queryStrukturaUsers($anketa, ' AND hs.level=(SELECT MAX(level) FROM srv_hierarhija_struktura WHERE anketa_id=' . $anketa . ') GROUP BY users.id');
                    echo '<div class="hierarhija-aktivacija-seznam-uporabnikov">';
                    echo '<div class="oranzna">';

                    echo '<h3>' . Hierarhija\HierarhijaHelper::textGledeNaOpcije($anketa, 'srv_hierarchy_email_code') . '</h3>';

                    if(\Hierarhija\Model\HierarhijaQuery::getOptions($anketa, 'onemogoci_dostop_uciteljem') == 1)
                        echo '<h3>' . $lang['srv_hierarchy_teacher_can_not_access']. '</h3>';

                    echo '</div>';
                    echo '<ul>';
                    while ($uporabnik = $users_upravicen_do_evalvacije->fetch_object()) {
                        echo '<li>' . $uporabnik->email . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
			}
			else {
                // Brez timerja
                //echo '<script>stopActivationTimer();</script>';

				# Aktivacijski pop up za vse ostale ankete
                echo '<div id="anketa_activate_note" class="divPopUp">';
                
                echo '<div class="popup_close"><a href="#" onClick="anketa_activate_save(\''.$this->anketa.'\',\''.$folders.'\'); return false;">✕</a></div>';

				echo '<h2>' . $lang['srv_activation_header'] . '.</h2>';
				
				// GDPR opozorilo ce ni potencialno GDPR
				echo '<p class="bold">';
				printf($lang['srv_activation_text_nongdpr'], $anketa);
                echo '</p>';
                echo '<p class="bold">';
				echo $lang['srv_activation_text_nongdpr2'];
				echo '</p>';

				echo '<div>' . $lang['srv_activation_expire'] .'<b>'.$expire.'</b></div>';
				echo '<div id="div_anketa_activate_more"><br/>';
				printf($lang['srv_activation_setting'], $anketa);
				echo '</div>';

				echo '<div id="div_lock_survey">';
				$sas = new SurveyAdminSettings();
				$sas->showLockSurvey();
				echo '</div>';
			}
			
			# skrit div za dodatne nastavitve
			echo '<div id="anketa_activate_settings" class="displayNone">';
			echo '<br/>';
			echo '<fieldset><legend>'.$lang['srv_activate_duration'].'</legend>';
			echo '<p><input id="radioAuto" type="radio" name="radioTrajanje" value="0" checked="checked" >';
			echo '<label for="radioAuto">'.$lang['srv_activate_duration_auto'].'<span id="startsAuto">' . $starts . '</span>'.$lang['srv_activate_duration_auto_to'].'<span id="expireAuto">' . $expire . '</span></label><br/>';
			echo '</p><p><input id="radioManual" type="radio" name="radioTrajanje" value="1" >';
			echo '<label for="radioManual">' . $lang['srv_activate_duration_manual'];
			echo '<input id="startsManual" type="text" name="startsManual" value="' . $starts . '" /> <span id="starts_img_manual" class="sprites calendar_ico"></span>';
			echo $lang['srv_activate_duration_manual_to'];
			echo '<input id="expireManual" type="text" name="expireManual" value="' . $expire . '" /> <span id="expire_img_manual" class="sprites calendar_ico"></span><br/>';
			echo '</label></p>';
			echo '</fieldset>';
			
			echo '<fieldset><legend>'.$lang['srv_vote_limit'].'</legend>';
			echo '<p><label>' . $lang['srv_vote_limit'] . ':</label>';
			echo '<input type="radio" name="vote_count_limit" value="0" id="vote_count_limit_0"' . ($row['vote_limit'] == 0 ? ' checked="checked"' : '') . ' onClick="voteCountToggle(0)" /><label for="vote_count_limit_0">' . $lang['no1'] . '</label>';
			echo '<input type="radio" name="vote_count_limit" value="1" id="vote_count_limit_1"' . ($row['vote_limit'] == 1 ? ' checked="checked"' : '') . ' onClick="voteCountToggle(1)" /><label for="vote_count_limit_1">' . $lang['yes'] . '</label>';
			echo '<input type="radio" name="vote_count_limit" value="2" id="vote_count_limit_2"' . ($row['vote_limit'] == 2 ? ' checked="checked"' : '') . ' onClick="voteCountToggle(2)" /><label for="vote_count_limit_2">' . $lang['srv_data_only_valid'] . ' (status 5, 6)</label>';
			echo '</p><p id="voteCountToggle1"' . ( $row['vote_limit'] == 0 ? ' class="displayNone"' : '' ).'>';
			echo '<label for="anketa' . $row['id'] . '" >'.$lang['srv_vote_count'].':</label>';
			echo '<input type="text" id="vote_count_val" name="vote_count_val" value="' . $row['vote_count'] . '" style="width:50px; margin-left: 5px;" maxlength="7" />';
			echo '</p>';
			echo '</fieldset>';
			
			echo '
			<script type="text/javascript">
                Calendar.setup({
                    inputField  : "startsManual",
                    ifFormat    : "%d.%m.%Y",
                    button      : "starts_img_manual",
                    singleClick : true,
                    onUpdate    : updateManual
                });
                Calendar.setup({
                    inputField  : "expireManual",
                    ifFormat    : "%d.%m.%Y",
                    button      : "expire_img_manual",
                    singleClick : true,
                    onUpdate    : updateManual	
                });
			</script>';	
			
			echo '</div>';
            
            // Timer counter
			echo '<div id="divAvtoClose" active="1" >'.$lang['srv_activate_duration_autostart'].': <span>10</span> s.</div>';
            
            // Gumb zapri
            echo '<div class="buttonwrapper buttons_holder"><a class="ovalbutton ovalbutton_orange" href="#" onclick="anketa_activate_save(\''.$this->anketa.'\',\''.$folders.'\'); return false;"><span>' . $lang['srv_zapri'] . '</span></a></div>';
			
			echo '</div>';
			
		} elseif ($_GET['a'] == 'anketa_save_activation') {
			
			Common::updateEditStamp();
			# po potrebi shranimo dodatne nastavitve

			$dbStarts = DateTime::createFromFormat('d.m.Y',$_POST['durationStarts']);
			$dbStarts =  $dbStarts->format('Y-m-d');
			$dbExpire = DateTime::createFromFormat('d.m.Y', $_POST['durationExpire']);
			$dbExpire =  $dbExpire->format('Y-m-d');

			$updateString = "UPDATE srv_anketa SET";  
			if ((int)$_POST['durationType'] == 1) {
				$updateString .= " starts = '".$dbStarts."', expire='".$dbExpire."'";
				$prefix = ',';
			}
			
			if ((int)$_POST['voteCountLimitType'] == 1) {
				$updateString .= $prefix." vote_limit = '1', vote_count='".(int)$_POST['voteCountValue']."'";
				$prefix = ',';
			} elseif ((int)$_POST['voteCountLimitType'] == 2) {
				$updateString .= $prefix." vote_limit = '2', vote_count='".(int)$_POST['voteCountValue']."'";
				$prefix = ',';
			} else {
				$updateString .= $prefix." vote_limit = '0'";
				$prefix = ',';
			}
			
			$updateString .=  " WHERE id='$anketa'";
			$sql = sisplet_query($updateString);
			#updejtamo srv_alert
			global $global_user_id;
			SurveyAlert::getInstance()->Init($this->anketa, $global_user_id);
			SurveyAlert::getInstance()->prepareSendExpireAlerts();
			# vsilimo refresh podatkov
			SurveyInfo :: getInstance()->resetSurveyData();	
			 
		} elseif ($_GET['a'] == 'anketa_getDates') {
			// prikažemo vmesnik za izbiro datuma
			// preberemo datume aktivnosti
			//$sqlDates = sisplet_query("SELECT starts, expire FROM srv_anketa WHERE id='" . $this->anketa . "'");
			//$rowDates = mysqli_fetch_assoc($sqlDates);
			$rowDates = SurveyInfo::getInstance()->getSurveyRow();

			$dateToday = date("Y-m-d"); // danes
			$cd = strtotime($dateToday);
			$mth = 1;
			$dateMonth = date('Y-m-d', mktime(0, 0, 0, date('m', $cd) + $mth, date('d', $cd), date('Y', $cd)));

			// datumi niso nastavljeni predlagamo avtomtsko.
			$dateAllSet = ($rowDates['starts'] == '0000-00-00' && $rowDates['expire'] == '0000-00-00');
			// novo: če uporabnik v 3 sekundah ne klikne na alert, izberemo "avtomatsko" 1 mesec
			$dateAllSet = 1;
			$rowDates['starts'] = (($rowDates['starts'] == '0000-00-00') ? $dateToday : $rowDates['starts']);
			$rowDates['expire'] = (($rowDates['expire'] == '0000-00-00') ? $dateMonth : $rowDates['expire']);

			echo '<fieldset><legend>'.$lang['srv_activate_duration'].'</legend>';
			echo '<input id="radioAuto" type="radio" name="radioTrajanje" value="0" ' . (($dateAllSet) ? ' checked="checked" ' : '') . '/>';
			echo $lang['srv_activate_duration_auto'].'<span id="startsAuto">' . $dateToday . '</span>'.$lang['srv_activate_duration_auto_to'].'<span id="expireAuto">' . $dateMonth . '</span> <br/>';
			echo '<input id="radioManual" type="radio" name="radioTrajanje" value="1" ' . (($dateAllSet) ? '' : ' checked="checked" ') . '/>';
			echo $lang['srv_activate_duration_manual'];
			echo '<input id="startsManual" type="text" name="startsManual" value="' . $rowDates['starts'] . '" />
			<span id="starts_img_manual" class="sprites calendar_ico"></span>';
			echo $lang['srv_activate_duration_manual_to'];
			echo '<input id="expireManual" type="text" name="expireManual" value="' . $rowDates['expire'] . '" /> <span id="expire_img_manual" class="sprites calendar_ico"></span><br/>';
			echo '<div id="divAvtoClose" >'.$lang['srv_activate_duration_autostart'].'<span id="spanAvtoClose">3</span> s</div>';
			echo '<div id="trajanjeLink" class="trajanjeLinkOff"><span class="sprites trajanje_star"></span> '.$lang['srv_activate_duration_button'].'</div>';
			echo '
			<script type="text/javascript">
			Calendar.setup({
			inputField  : "startsManual",
			ifFormat    : "%Y-%m-%d",
			button      : "starts_img_manual",
			singleClick : true,
			onUpdate    : updateManual
			});
			Calendar.setup({
			inputField  : "expireManual",
			ifFormat    : "%Y-%m-%d",
			button      : "expire_img_manual",
			singleClick : true,
			onUpdate    : updateManual	});' .

			'$("#trajanjeLink").click(function(){ anketa_setActive(\'' . $this->anketa . '\',\'' . $_POST['folders'] . '\') });
			$("#trajanjeLink").mouseenter(function(){$(this).addClass(\'trajanjeLinkOn\');});
			$("#trajanjeLink").mouseleave(function(){$(this).removeClass(\'trajanjeLinkOn\');});
			closeTimeout = setTimeout(function() {autoCloseActivationDiv(\'' . $this->anketa . '\',\'' . $_POST['folders'] . '\');}, 1000);
			$("#surveyTrajanje").bind("click", function() {$("#divAvtoClose").hide();});
			</script>
			';
		}
		elseif ($_GET['a'] == 'save_global') {
			if (isset ($anketa) || isset ($what) || isset ($state)) {
				Common::updateEditStamp();
				$updateString = "UPDATE srv_anketa SET $what='" . $state . "' WHERE id='" . $anketa . "'";
				$sql = sisplet_query($updateString);
				// vsilimo refresh podatkov
				SurveyInfo :: getInstance()->resetSurveyData();

			}
		}
		elseif ($_GET['a'] == 'sysFilterEditMode') {
			global $global_user_id;
			$mode = (isset ($_POST['mode'])) ? $_POST['mode'] : 'normal';
			Setting :: getInstance()->Init($global_user_id);
			Setting :: getInstance()->DisplaySystemFilters($mode);
		}
		elseif ($_GET['a'] == 'sysFilterAdd') {
			global $global_user_id;
			global $admin_type;

			if (isset ($_POST['filter']) && isset ($_POST['text']) && isset ($_POST['fid']) && $admin_type == 0) {
				Setting :: getInstance()->Init($global_user_id);
				Setting :: getInstance()->AddS($_POST['filter'], $_POST['text'], $_POST['fid']);
				Setting :: getInstance()->DisplaySystemFilters('edit');
			}
		}
		elseif ($_GET['a'] == 'sysFilterDelete') {
			global $global_user_id;
			global $admin_type;
			if (isset ($_POST['id']) && $admin_type == 0) {
				Setting :: getInstance()->Init($global_user_id);
				Setting :: getInstance()->DeleteSystemFilters($_POST['id']);
				Setting :: getInstance()->DisplaySystemFilters('edit');
			}
		}
		elseif ($_GET['a'] == 'sysFilterSave') {
			global $global_user_id;
			global $admin_type;
			if (isset ($_POST['id']) && isset ($_POST['filter']) && isset ($_POST['text']) && $admin_type == 0) {
				Setting :: getInstance()->Init($global_user_id);
				Setting :: getInstance()->SaveSystemFilters($_POST['id'], $_POST['filter'], $_POST['text']);
				Setting :: getInstance()->DisplaySystemFilters('edit');
			}
		}
		elseif ($_GET['a'] == 'save_reportSetting') {
			global $lang, $global_user_id;

			SurveyUserSetting :: getInstance()->Init($_POST['anketa'], $_POST['uid']);
			SurveyUserSetting :: getInstance()->setShowPdfIf($_POST['state']);

		}
		elseif ($_GET['a'] == 'saveSrvMisc') {
			global $global_user_id;
			if (isset ($_POST['what']) && isset ($_POST['value'])) {
				Setting :: getInstance()->Init($global_user_id);
				Setting :: getInstance()->setSysMiscSetting($_POST['what'], $_POST['value']);
			}
		}
		elseif ($_GET['a'] == 'saveSrvSurveyMisc') { // shranimonastavitev posamezne ankete
			if (isset ($_POST['anketa']) && $_POST['anketa'] != '') {
				SurveySetting :: getInstance()->Init($_POST['anketa']);
				if (isset ($_POST['what']) && isset ($_POST['value'])) {
					SurveySetting :: getInstance()->setSurveyMiscSetting($_POST['what'], $_POST['value']);
				}
			}
		}
		elseif ($_GET['a'] == 'save_userSetting') { // shranimo nastavitev posameznega uporabnika
			if (isset ($_POST['uid']) && $_POST['uid'] != '') {
				UserSetting :: getInstance()->Init($_POST['uid']);
				if (isset ($_POST['what']) && isset ($_POST['state'])) {
					UserSetting :: getInstance()->setUserSetting($_POST['what'], $_POST['state']);
				}
			}
		}
		elseif ($_GET['a'] == 'vnosiReloadData') {
			global $global_user_id;
			#$dsd = new DisplaySurveyData($this->anketa);
			#$dsd->displayData();
			SurveyDataDisplay::Init($this->anketa);
			SurveyDataDisplay::displayVnosiHTML();
		}
		elseif ($_GET['a'] == 'vnosiReloadLeftFilter') {
			global $global_user_id;
			SurveyDataDisplay::Init($this->anketa);
			SurveyDataDisplay::displayLeftFilters();
		}
		elseif ($_GET['a'] == 'analiza_loadFilterProfile') {
			global $global_user_id;
			$analiza = new Analiza($this->anketa, $_POST['podstran']);
			$analiza->showFilterProfiles();
		}
		elseif ($_GET['a'] == 'analiza_show_chart_color') {
			
			SurveyAnalysis::Init($this->anketa);
			SurveyAnalysis::showChartColorProfiles();
		}
		elseif ($_GET['a'] == 'analiza_loadMissingProfile') {
			global $global_user_id;
			$analiza = new Analiza($this->anketa, $_POST['podstran']);
			$analiza->showMissingValues();
		}
		elseif ($_GET['a'] == 'analizaMissingProfileDropdownReloadData') {
			global $global_user_id;
			$analiza = new Analiza($this->anketa, $_POST['podstran']);
			$analiza->analizaMissingProfileDropdownReloadData();
		}
		elseif ($_GET['a'] == 'analiza_changeViewFilterProfile') {
			global $global_user_id;
			//            SurveyUserSetting :: getInstance()->Init($this->anketa, $global_user_id);
			//            SurveyUserSetting :: getInstance()->saveSettings('default_missing_profile', $_POST['profileId']);
			// če ni več seja pobirsemo session za to nastavitev

			if ( $_POST['profileId'] != 0 )
				$_SESSION['missing_profile_from_session'] = false;

			$analiza = new Analiza($this->anketa, $_POST['podstran']);
			$analiza->showFilterProfiles($_POST['profileId']);
		}
		elseif ($_GET['a'] == 'analiza_changeViewMissingProfile') {
			global $global_user_id;
			//			SurveyUserSetting :: getInstance()->Init($this->anketa, $global_user_id);
			//			SurveyUserSetting :: getInstance()->saveSettings('default_missing_profile', $_POST['profileId']);
			// če ni več seja pobirsemo session za to nastavitev

			if ( $_POST['profileId'] != 0 )
				$_SESSION['missing_profile_from_session'] = false;

			$analiza = new Analiza($this->anketa, $_POST['podstran']);
			$analiza->showMissingValues($_POST['profileId']);
		}
		elseif ($_GET['a'] == 'changeMissingProfileDropdown') {
			global $global_user_id;

			SurveyUserSetting :: getInstance()->Init($this->anketa, $global_user_id);
			SurveyUserSetting :: getInstance()->saveSettings('default_missing_profile', $_POST['profileId']);

			// če ni več seja pobirsemo session za to nastavitev
			if ( $_POST['profileId'] != 0 )
				unset($_SESSION['missing_profile_from_session']);
			else {
				$_SESSION['missing_profile_from_session'] = true;
			}

		}
		elseif ($_GET['a'] == 'changeFilterProfileDropdown') {
			global $global_user_id;

			$pid = $_POST['profileId'];
			SurveyFilterProfiles :: getInstance()->Init($this->anketa, $global_user_id);
			SurveyFilterProfiles :: getInstance()->setCurrentProfile($pid);
		}
		elseif ($_GET['a'] == 'analiza_createFilterProfile') {
			global $global_user_id;

			SurveyFilterProfiles :: getInstance()->Init($this->anketa, $global_user_id);
			$new_id = SurveyFilterProfiles :: getInstance()->newProfile($_POST['profileName']);

			SurveyFilterProfiles :: getInstance()->setCurrentProfile($new_id);
		}
		elseif ($_GET['a'] == 'analiza_runFilterProfile') {
			global $global_user_id;

			// shranimo.
			$pid = $_POST['profileId'];
			SurveyFilterProfiles :: getInstance()->Init($this->anketa, $global_user_id);
			SurveyFilterProfiles :: getInstance()->setCurrentProfile($pid);
		}
		elseif ($_GET['a'] == 'analiza_deleteFilterProfile') {
			global $global_user_id;

			SurveyFilterProfiles :: getInstance()->Init($this->anketa, $global_user_id);
			$newId = SurveyFilterProfiles :: getInstance()->deleteProfile($_POST['profileId']);

			SurveyFilterProfiles :: getInstance()->setCurrentProfile($newId);
		}
		elseif ($_GET['a'] == 'analiza_renameFilterProfile') {
			global $global_user_id;
			SurveyFilterProfiles :: getInstance()->Init($this->anketa, $global_user_id);
			$updated = SurveyFilterProfiles :: getInstance()->renameProfile($_POST['profileId'],$_POST['newProfileName']);
		}
		elseif ($_GET['a'] == 'analizaFilterProfileDropdownReloadData') {
			global $global_user_id;

			$analiza = new Analiza($this->anketa, $_POST['podstran']);
			$analiza->analizaFilterProfileDropdownReloadData();
		}


		elseif ($_GET['a'] == 'filter_editing') {
			Common::updateEditStamp();
			
			if (isset ($_COOKIE['filter_' . $this->anketa])) {
				$if_id = $_COOKIE['filter_' . $this->anketa];
			} else {
				sisplet_query("INSERT INTO srv_if (id) VALUES ('')");
				$if_id = mysqli_insert_id($GLOBALS['connect_db']);
				sisplet_query("INSERT INTO srv_condition (id, if_id, vrstni_red) VALUES ('', '$if_id', '1')");
				setcookie('filter_' . $this->anketa, $if_id);
			}

			//include_once ('Branching.php');
			$Branching = new Branching($this->anketa);
			$Branching->condition_editing($if_id, -1);

		}
		elseif ($_GET['a'] == 'filter_remove') {
			Common::updateEditStamp();
			
			//include_once ('Branching.php');
			$Branching = new Branching($this->anketa);
			ob_start();
			$Branching->ajax_if_remove($_COOKIE['filter_' . $this->anketa]);
			ob_get_clean();
			setcookie('filter_' . $this->anketa, '', time() - 3600);
			echo 'index.php?anketa=' . $this->anketa . '&a='.A_COLLECT_DATA;

		}
		elseif ($_GET['a'] == 'filter_close') {

			echo 'index.php?anketa=' . $this->anketa . '&a='.A_COLLECT_DATA;
		}
		elseif ($_GET['a'] == 'handleUserCodeSetting') {
			Common::updateEditStamp();
			
			if (isset ($_POST['phone']) && isset ($_POST['email']) && isset ($this->anketa)) {
				
				//$sql = sisplet_query("UPDATE srv_anketa SET phone = '" . $_POST['phone'] . "', email = '" . $_POST['email'] . "' , social_network = '" . $_POST['social_network'] . "' WHERE id = '" . $this->anketa . "'");
				
				if($_POST['phone'] == '1')
					$sql = sisplet_query("INSERT INTO srv_anketa_module (ank_id, modul) VALUES ('".$this->anketa."', 'phone')");
				else
					$sql = sisplet_query("DELETE FROM srv_anketa_module WHERE ank_id='".$this->anketa."' AND modul='phone'");
					
				if($_POST['email'] == '1')
					$sql = sisplet_query("INSERT INTO srv_anketa_module (ank_id, modul) VALUES ('".$this->anketa."', 'email')");
				else
					$sql = sisplet_query("DELETE FROM srv_anketa_module WHERE ank_id='".$this->anketa."' AND modul='email'");
				
				// vsilimo refresh podatkov
				SurveyInfo :: getInstance()->resetSurveyData();

				$this->SurveyAdmin->createUserbaseSystemVariable($_POST['phone'], $_POST['email']);
			}
			elseif (isset ($_POST['usercode_skip']) && isset ($this->anketa)) {
				$sql = sisplet_query("UPDATE srv_anketa SET usercode_skip = '" . $_POST['usercode_skip'] . "', cookie='-1' WHERE id = '" . $this->anketa . "'");
				// vsilimo refresh podatkov
				SurveyInfo :: getInstance()->resetSurveyData();

			}
			elseif (isset ($_POST['usercode_required']) && isset ($this->anketa)) {
				$sql = sisplet_query("UPDATE srv_anketa SET usercode_required = '" . $_POST['usercode_required'] . "' WHERE id = '" . $this->anketa . "'");
				// vsilimo refresh podatkov
				SurveyInfo :: getInstance()->resetSurveyData();
			} else
				echo 'Napaka!';
				
			$sas = new SurveyAdminSettings();
			if ($_POST['all'] == 1)
				$sas->respondenti_iz_baze();
			else
				$sas->showUserCodeSettings($row);
		}
		elseif ($_GET['a'] == 'anketaActiveEmail') {
			Common::updateEditStamp();

			// če je email 0, aktiviramo email in hkrati user_base
			if (SurveyInfo::getInstance()->checkSurveyModule('email')) {
				$strUpdate = "UPDATE srv_anketa SET user_base = 1 WHERE id = '" . $this->anketa . "'";
				$sqlUpdate = sisplet_query($strUpdate);

				$sql = sisplet_query("INSERT INTO srv_anketa_module (ank_id, modul) VALUES ('".$this->anketa."', 'email')");				
				
				// vsilimo refresh podatkov
				SurveyInfo :: getInstance()->resetSurveyData();

				$_email = 1;
			} else {
				// če je email 1 deaktiviramo email in hkrati preverimo še telefon in ponastavimo user_base
				$strUserBase = "";
				if (!SurveyInfo::getInstance()->checkSurveyModule('phone'))
					$strUserBase = " user_base = 0,";
				$strUpdate = "UPDATE srv_anketa SET" . $strUserBase . " WHERE id = '" . $this->anketa . "'";
				$sqlUpdate = sisplet_query($strUpdate);
				
				$sql = sisplet_query("DELETE FROM srv_anketa_module WHERE ank_id='".$this->anketa."' AND modul='email'");
				
				// vsilimo refresh podatkov
				SurveyInfo :: getInstance()->resetSurveyData();

				$_email = 0;
			}
			
			$this->SurveyAdmin->createUserbaseSystemVariable((int)SurveyInfo::getInstance()->checkSurveyModule('phone'), $_email);
		}
		elseif ($_GET['a'] == 'editRespondentVrednost') {
			Common::updateEditStamp();
			if (isset ($_POST['spr_id']) && $_POST['spr_id'] != "" && isset ($_POST['usr_id']) && $_POST['usr_id'] != "") {
				$strUpdate = "INSERT INTO srv_data_text".$this->db_table." (spr_id, usr_id, text) VALUES ('" . $_POST['spr_id'] . "', '" . $_POST['usr_id'] . "', '" . $_POST['val'] . "') " .
				"ON DUPLICATE KEY UPDATE text = '" . $_POST['val'] . "'";
				$sqlUpdate = sisplet_query($strUpdate);
			}
		}
		elseif ($_GET['a'] == 'edit_email_invitations') {
			
			if ($_POST['id'] == '0') {
				$email_template_name = "Moja predloga";
				$email_template_subject = $_POST['email_subject'];
				$email_template_text = $_POST['email_text'];
			} else {
				// iz baze poberemo vrednosti
				$sql = sisplet_query("SELECT * FROM srv_userbase_invitations WHERE id = '" . $_POST['id'] . "'");
				$row = mysqli_fetch_assoc($sql);
				$email_template_name = $row['name'];
				$email_template_subject = $row['subject'];
				$email_template_text = $row['text'];
			}

			echo '<div style="margin-bottom:10px;">';
			echo '<input id="template_id" type="hidden" value="' . $_POST['id'] . '">';
			echo '<p> <span class="labelSpan" >Ime:</span>';
			echo '<input id="template_name_' . $_POST['id'] . '" type="text" value="' . $email_template_name . '"></p>';
			echo '<p> <span class="labelSpan" >Zadeva:</span>';
			echo '<input id="template_subject_' . $_POST['id'] . '" type="text" value="' . $email_template_subject . '"></p>';
			echo '<p> <span class="labelSpan" >' . $lang['text'] . ':</span><br/>';
			echo '<textarea name="template_text_' . $_POST['id'] . '" id="template_text_' . $_POST['id'] . '">' . $email_template_text . '</textarea></p>';
			echo '  <span class="floatRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_red" href="#" onclick="email_invitations_close(\'close\'); return false;"><span><img src="icons/icons/bin_closed.png" alt="" vartical-align="middle" />' . $lang['srv_zapri'] . '</span></a></div></span>';
			if ($_POST['id'] != '0') {
				echo '  <span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_green" href="#" onclick="email_invitations_close(\'save\'); return false;"><span><img src="icons/icons/cog_save.png" alt="" vartical-align="middle" />shrani predlogo</span></a></div></span>';
			}
			echo '  <span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="email_invitations_close(\'new\'); return false;"><span><img id="email_save" src="icons/icons/cog_add.png" alt="" vartical-align="middle" />nova predloga</span></a></div></span>';
			echo '	<div class="clr"></div>';
			echo '</div>';
			echo '<script type="text/javascript">';
			//			echo '$(document).ready(function() {' .
			echo ' create_editor(\'template_text_' . $_POST['id'] . '\');';
			//			' });';
			echo '</script>';

		}
		elseif ($_GET['a'] == 'edit_email_invitations_save') {
			Common::updateEditStamp();
			if ($_POST['template_name'] != "") {
				if ($_POST['what'] == 'save') {
					// shranimo obstoječo
					$sql_update = sisplet_query("UPDATE srv_userbase_invitations SET name = '" . $_POST['template_name'] . "', subject = '" . $_POST['template_subject'] . "', text = '" . $_POST['template_text'] . "' WHERE id = '" . $_POST['id'] . "'");
					$this->SurveyAdmin->show_email_invitation_templates($_POST['id']);

				}
				elseif ($_POST['what'] == 'new') {
					// shranimo kot novo
					$sql_insert = sisplet_query("INSERT INTO srv_userbase_invitations (name, subject, text) VALUES('" . $_POST['template_name'] . "', '" . $_POST['template_subject'] . "', '" . $_POST['template_text'] . "');");
					$id = mysqli_insert_id($GLOBALS['connect_db']);
					$errorMsg = null;
					if (mysqli_affected_rows($GLOBALS['connect_db']) < 1) {
						// predloga s tem imenom že obstaja
						$errorMsg = "Predloga s tem imenom že obstaja!";
						//poiščemo id te predlogo
						$sql = sisplet_query("SELECT id FROM srv_userbase_invitations WHERE name = '" . $_POST['template_name'] . "'");
						$row = mysqli_fetch_assoc($sql);
						$id = $row['id'];
					}
					if ($id < 1)
						$id = 1;
					$this->SurveyAdmin->show_email_invitation_templates($id, $errorMsg);
				}
			} else {
				$errorMsg = "Manjka ime predloge!";
				$this->SurveyAdmin->show_email_invitation_templates($id, $errorMsg);
			}
		}
		elseif ($_GET['a'] == 'change_email_invitations_template') {
			$this->SurveyAdmin->show_email_invitation_values($_POST['id']);
		}
		elseif ($_GET['a'] == 'email_invitation_delete_template') {
			Common::updateEditStamp();
			$id = 1;
			if ($_POST['id'] > 1) {
				$errorMsg = "Predloga je bila izbrisana!";
				$sql_delete = sisplet_query("DELETE FROM srv_userbase_invitations WHERE id = '" . $_POST['id'] . "'");
				if (mysqli_affected_rows($GLOBALS['connect_db']) < 1) {
					$errorMsg = "Napaka! Predloge ni bilo mogoče izbrisati!";
					$id = $_POST['id'];
				}
			}
			elseif ($_POST['id'] == 1) {
				$errorMsg = "Privzete predloge ni mogoče izbrisati!";
			} else {
				$errorMsg = "Manjka id. Predloge ni mogoče izbrisati!";
			}
			$this->SurveyAdmin->show_email_invitation_templates($id, $errorMsg);
		}
		elseif ($_GET['a'] == 'show_insert_email_respondents') {
			// če je id > 0 vstavljamo iz lste
			$users = "";
			if ($_POST['id'] > 0) {
				// preberemo respondente
				$_users = array ();
				$sqlRespondenti = sisplet_query("SELECT line FROM srv_userbase_respondents WHERE list_id = '" . $_POST['id'] . "'");
				while ($row_respondenti = mysqli_fetch_assoc($sqlRespondenti)) {
					$_users[] = $row_respondenti['line'];
				}
				$users = implode(NEW_LINE, $_users);
			}
			echo '<div id="insert_email_respondents">';
			echo '<fieldset><legend>' . $lang['srv_massinsert'] . '</legend>';
			echo '<p><strong>';
			$sql = sisplet_query("SELECT s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.gru_id=g.id AND g.ank_id='$this->anketa' ORDER BY g.vrstni_red, s.vrstni_red");
			if (!$sql)
				echo mysqli_error($GLOBALS['connect_db']);
			$i = 0;
			while ($row = mysqli_fetch_array($sql)) {
				if ($i++ != 0)
					echo ',';
				echo '' . strip_tags($row['variable']) . '';
			}
			echo '</strong></p>';
			echo '<form id="frm_email_respondents" action="index.php?anketa=' . $this->anketa . '&a=email" method="post">';
			echo '<div style="float:left; width:390px;"><textarea name="userinsert" style="width:380px; height:300px">' . $users . '</textarea></div>';
			echo '<div style="float:right; width:410px; border:1px solid #e2e2e2; background-color: #f2f2f2; padding:5px;">' . $lang['srv_masstxt'] . '</div>';
			echo '<div class="clr"></div>';
			echo '</form>';
			echo '</fieldset><br/>';
			echo '<div class="floatRight spaceLeft"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_red" href="#" onclick="close_insert_email_respondents(); return false;"><span><img src="icons/icons/accept.png" alt="" vartical-align="middle" />' . $lang['close'] . '</span></a></div></div>';
			echo '<div class="floatRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_green" href="#" onclick="$(\'#frm_email_respondents\').submit(); return false;"><span><img src="icons/icons/accept.png" alt="" vartical-align="middle" />' . $lang['add'] . '</span></a></div></div>';
			echo '<div class="clr"></div>';
			echo '</div>';
		}
		elseif ($_GET['a'] == 'show_edit_email_respondents') {
			echo '<div id="insert_email_respondents">';
			echo '<fieldset><legend>' . $lang['srv_massinsert'] . '</legend>';

			$_sistemske = array ();
			$labels = "";
			$users = "";

			$listName = "Moji respondenti";
			// če je id=0 urejamo respondente iz trenutne ankete, če je id > 0 pa urejamo respondente že shranjene liste
			if ($_POST['id'] == 0) {
				//poiščemo sistemske spremenljivke
				$labelsPrefix = "";
				$sqlSistemske = sisplet_query("SELECT s.id, s.naslov FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.gru_id=g.id AND g.ank_id='$this->anketa' ORDER BY g.vrstni_red, s.vrstni_red");
				while ($rowSistemske = mysqli_fetch_assoc($sqlSistemske)) {
					$_sistemske[] = $rowSistemske['id'];
					$labels .= $labelsPrefix . strip_tags($rowSistemske['naslov']);
					$labelsPrefix = ",";
				}
				//	zloopamo po userjih ankete
				$str_qry_users = "SELECT u.id AS usr_id FROM srv_user AS u WHERE u.ank_id = '" . $this->anketa . "' ";
				$qry_users = sisplet_query($str_qry_users) or die(mysqli_error($GLOBALS['connect_db']));

				while ($row_users = mysqli_fetch_assoc($qry_users)) {
					$tmpUsers = "";
					$usersPrefix = "";
					foreach ($_sistemske as $sistemska) {
						$textSql = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id = '" . $sistemska . "' AND usr_id = '" . $row_users['usr_id'] . "'");
						$textRow = mysqli_fetch_assoc($textSql);
						$tmpUsers .= $usersPrefix . strip_tags($textRow['text']);
						$usersPrefix = ",";
					}
					$_users[] = $tmpUsers;
				}

			}
			elseif ($_POST['id'] > 0) {
				// preberemo stare vrednosti
				// preberemo ime liste in sistemske spremenljivke
				$sqlLista = sisplet_query("SELECT * FROM srv_userbase_respondents_lists WHERE id = '" . $_POST['id'] . "'");
				$rowLista = mysqli_fetch_assoc($sqlLista);
				$labels = $rowLista['variables'];
				$listName = $rowLista['name'];
				// preberemo respondente
				$_users = array ();
				$sqlRespondenti = sisplet_query("SELECT line FROM srv_userbase_respondents WHERE list_id = '" . $_POST['id'] . "'");
				while ($row_respondenti = mysqli_fetch_assoc($sqlRespondenti)) {
					$_users[] = $row_respondenti['line'];
				}
			}

			echo '<input type="hidden" name="list_id" id="list_id" value="' . $_POST['id'] . '">';
			echo '<input type="hidden" name="list_variables_' . $_POST['id'] . '" id="list_variables_' . $_POST['id'] . '" value="' . $labels . '">';
			echo '<p>Ime liste: <input name="list_name_' . $_POST['id'] . '" id="list_name_' . $_POST['id'] . '" value="' . $listName . '">';
			echo '<p>Sistemske spremenljivke: <strong>' . $labels . '</strong></p>';

			echo '<div style="float:left; width:410px;"><textarea name="list_text_' . $_POST['id'] . '" id="list_text_' . $_POST['id'] . '" style="width:400px; height:300px">';
			if ($_users)
				echo implode(NEW_LINE, $_users);
			echo '</textarea></div>';
			echo '<div style="float:right; width:410px; border:1px solid #e2e2e2; background-color: #f2f2f2; padding:5px;">' . $lang['srv_masstxt'] . '</div>';
			echo '<div class="clr"></div>';

			echo '</fieldset><br/>';
			echo '<div class="floatRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_red" href="#" onclick="close_edit_email_respondents(\'close\'); return false;"><span><img src="icons/icons/bin_closed.png" alt="" vartical-align="middle" />' . $lang['close'] . '</span></a></div></div>';
			if ($_POST['id'] != '0') {
				echo '<div class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_green" href="#" onclick="close_edit_email_respondents(\'save\'); return false;"><span><img src="icons/icons/book_save.png" alt="" vartical-align="middle" />Shrani listo</span></a></div></div>';
			}
			echo '<div class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="close_edit_email_respondents(\'new\'); return false;"><span><img src="icons/icons/book_add.png" alt="" vartical-align="middle" />Nova lista</span></a></div></div>';
			echo '<div class="clr"></div>';
			echo '</div>';

		}
		elseif ($_GET['a'] == 'edit_respondents_list_save') {
			Common::updateEditStamp();
			$id = $_POST['list_id'];
			if ($_POST['list_name'] != "") {
				if ($_POST['what'] == 'save') {
					// shranimo obstoječo
					$sql_update = sisplet_query("UPDATE srv_userbase_respondents_lists SET name = '" . $_POST['list_name'] . "', variables = '" . $_POST['list_variables'] . "' WHERE id = '" . $id . "'");
					// pobrišemo stare zapise in dodamo nove
					$sql_delete = sisplet_query("DELETE FROM srv_userbase_respondents WHERE list_id = '" . $id . "'");
					// zapišemo nove respondente
					$vrstice = explode(NEW_LINE, $_POST['list_text']);
					foreach ($vrstice AS $vrstica) {
						$sql_insert = sisplet_query("INSERT INTO srv_userbase_respondents (list_id, line) VALUES('" . $id . "', '" . $vrstica . "');");
					}
					$errorMsg = "Lista je bila shranjena!";
				}
				elseif ($_POST['what'] == 'new') {
					// shranimo kot novo listo
					$sql_insert = sisplet_query("INSERT INTO srv_userbase_respondents_lists (name, variables) VALUES('" . $_POST['list_name'] . "', '" . $_POST['list_variables'] . "');");
					$iid = mysqli_insert_id($GLOBALS['connect_db']);

					$errorMsg = null;
					if (mysqli_affected_rows($GLOBALS['connect_db']) < 1) {
						// predloga s tem imenom že obstaja
						$errorMsg = "Lista s tem imenom že obstaja!";
						//poiščemo id te predlogo
						$sql = sisplet_query("SELECT id FROM srv_userbase_respondents_lists WHERE name = '" . $_POST['list_name'] . "'");
						$row = mysqli_fetch_assoc($sql);
						$id = $row['id'];
					}
					elseif ($iid > 0) {
						// vstavimo še respondente
						// najprej razdelimo vrstice
						$vrstice = explode(NEW_LINE, $_POST['list_text']);
						foreach ($vrstice AS $vrstica) {
							$sql_insert = sisplet_query("INSERT INTO srv_userbase_respondents (list_id, line) VALUES('" . $iid . "', '" . $vrstica . "');");
						}
						$errorMsg = "Lista je bila kreirana!";
						$id = $iid;
					}
				}
			} else {
				$errorMsg = "Manjka ime liste!";
			}
			if ($id < 1)
				$id = 1;
			$this->SurveyAdmin->show_userbase_respondents_lists($id, $errorMsg);
		}
		elseif ($_GET['a'] == 'change_respondent_list') {
			$this->SurveyAdmin->show_userbase_list_respondents($_POST['id']);
		}
		elseif ($_GET['a'] == 'delete_respondent_list') {
			Common::updateEditStamp();
			if ($_POST['id'] != "") {
				$errorMsg = "Lista je bila izbrisana!";
				$sql_delete1 = sisplet_query("DELETE FROM srv_userbase_respondents WHERE list_id = '" . $_POST['id'] . "'");
				$aff1 = mysqli_affected_rows($GLOBALS['connect_db']);
				$sql_delete2 = sisplet_query("DELETE FROM srv_userbase_respondents_lists WHERE id = '" . $_POST['id'] . "'");
				$aff2 = mysqli_affected_rows($GLOBALS['connect_db']);
				if ($aff2 < 1) {
					$errorMsg = "Napaka! Predloge ni bilo mogoče izbrisati!";
				}

			}
			$this->SurveyAdmin->show_userbase_respondents_lists(null, $errorMsg);
		}
		elseif ($_GET['a'] == 'change_mailto_radio') {
			$this->SurveyAdmin->show_mailto_users($_POST['mailto_radio'], $_POST['mailto_status']);
		}
		elseif ($_GET['a'] == 'preview_mailto_email') {

			$this->SurveyAdmin->preview_mailto_email($_POST['mailto_radio'], $_POST['mailto_status']);
		}
		elseif ($_GET['a'] == 'show_surveyListSettings') {
			$SL = new SurveyList();
			UserSetting::getInstance()->Init();
			if ($_POST['sortby'] != "" ) {
				UserSetting::getInstance()->setUserSetting('survey_list_order_by', $_POST['sortby']. ",".$_POST['sorttype']);
			}
			echo '<div id="survey_list_settings">';
			$SL -> displaySettings();
			echo '</div>';
			
		}
		elseif ($_GET['a'] == 'show_surveyListQickInfo') {
			$SL = new SurveyList();
			$SL -> displayListQickInfo();
		}
		elseif ($_GET['a'] == 'save_surveyListSettings') {
			// setiramo nastavitve v UserSetting
			UserSetting::getInstance()->Init();
			UserSetting::getInstance()->setUserSetting('survey_list_rows_per_page', $_POST['rows_per_page']);
			UserSetting::getInstance()->setUserSetting('survey_list_order', $_POST['vrstniRed']);
			UserSetting::getInstance()->setUserSetting('survey_list_visible', $_POST['data']);
			if ($_POST['sortby'] != "" )
				UserSetting::getInstance()->setUserSetting('survey_list_order_by', $_POST['sortby']. ",".$_POST['sorttype']);

			// shranimo nastavitve
			UserSetting::getInstance()->saveUserSetting();
			
			$SL = new SurveyList();
			$SL->getSurveys();
		}
		elseif ($_GET['a'] == 'surveyListFilter') {
			// setiramo nastavitve v UserSetting
			$SL = new SurveyList();
			$SL -> setFilter();
			$SL->getSurveys();
		}
		elseif ($_GET['a'] == 'default_surveyListSettings') {
			// setiramo nastavitve v UserSetting
			$SL = new SurveyList();
			
			UserSetting::getInstance()->Init();
			UserSetting::getInstance()->setUserSetting('survey_list_rows_per_page', $SL->getDef_Rows_per_page());
			UserSetting::getInstance()->setUserSetting('survey_list_order', '');
			UserSetting::getInstance()->setUserSetting('survey_list_visible', '');
			UserSetting::getInstance()->setUserSetting('survey_list_order_by', '');
			// shranimo nastavitve
			UserSetting::getInstance()->saveUserSetting();


			$SL->saveCssSettings('');
			echo '<div id="survey_list_settings">';
			$SL->displaySettings();

			echo '</div>';
		}
		elseif ($_GET['a'] == 'save_surveyListCssSettings') {
			UserSetting::getInstance()->Init();
			if ($_POST['sortby'] != "" )
				UserSetting::getInstance()->setUserSetting('survey_list_order_by', $_POST['sortby']. ",".$_POST['sorttype']);

			$SL = new SurveyList();
			$SL->saveCssSettings($_POST['data']);
			$SL->getSurveys();
		}
		elseif ($_GET['a'] == 'surveyList_goTo') {
			UserSetting::getInstance()->Init();
			if ($_POST['sortby'] != "" )
				UserSetting::getInstance()->setUserSetting('survey_list_order_by', $_POST['sortby']. ",".$_POST['sorttype']);
			UserSetting::getInstance()->saveUserSetting();
			
			$SL = new SurveyList();
			$SL->getSurveys();
			
		}
		elseif ($_GET['a'] == 'surveyList_folders') {			
			$val = $_POST['show_folders'];
			
			UserSetting::getInstance()->Init();		
			UserSetting::getInstance()->setUserSetting('survey_list_folders', $val);
			UserSetting::getInstance()->saveUserSetting();
			
			$SL = new SurveyList();
			$SL->getSurveys();
			
		}
		elseif ($_GET['a'] == 'surveyList_user') {

			$SL = new SurveyList();
			$SL -> setUserId();
			$SL->getSurveys();
			
		}
		elseif ($_GET['a'] == 'surveyList_language') {

			$SL = new SurveyList();
			$SL -> setUserLanguage();
			$SL->getSurveys();
			
        }
        elseif ($_GET['a'] == 'surveyList_gdpr') {

			$SL = new SurveyList();
			$SL -> setUserGDPR();
			$SL->getSurveys();
			
		}
		elseif ($_GET['a'] == 'surveyList_library') {

			$SL = new SurveyList();
			$SL -> setUserLibrary();
			$SL->getSurveys();
			
		}
		elseif ($_GET['a'] == 'survey_chaneg_type') {
			global $site_url;
			$errorMsg = null;
			// preverimo ali moramo spremeniti vrsto ankete, ali samo izpisemo opozorila
			if ($_POST['change_type_submit'] == 'true') {
				if ($_POST['new_type'] == 0) {
					// za preklop na glasovanje zaenkrat nimamo omejitev
				}
				else if ($_POST['new_type'] == 1) {
						// za preklop na formo zaenkrat nimamo omejitev
					}
					else if ($_POST['new_type'] == 2) { // ce zelimo tip 2 (navadna anketa) preverimo da nimamo pogojev (if-ov)
							//					$sql = sisplet_query("SELECT * FROM srv_branching WHERE ank_id='$this->anketa' AND element_if > 0");
							//                	if (mysqli_num_rows($sql) > 0) {
							//						$errorMsg = $lang['srv_vrsta_survey_with_pages_error'];
							//					}
						}
						elseif ($_POST['new_type'] == 3) {
							// za preklop na anketo s pogoje zaenkrat nimamo omejitev
						} else {
							$errorMsg = $lang['srv_vrsta_survey_error_not_suported'];
				}

				if ($errorMsg == null) {
					Common::updateEditStamp();
					sisplet_query("UPDATE srv_anketa SET survey_type = '" . $_POST['new_type'] . "' WHERE id = '$this->anketa'");

					switch ($_POST['new_type']) {
						case 0 : // glasovanja
							break;
						case 1 : // forma - izklopimo uvod in zakljucek
							sisplet_query("UPDATE srv_anketa SET show_intro = '0', show_concl = '0' WHERE id = '$this->anketa'");
							break;
						case 2 : // Anketa na vec straneh
							break;
						case 3 : // anketa s pogoji
							break;
					}
					// vsilimo refresh podatkov
					SurveyInfo :: getInstance()->resetSurveyData();

					//					sisplet_query("UPDATE srv_anketa SET branching='1' WHERE id = '$this->anketa'");
					$location = "";
					if ($_POST['new_type'] == 3)
						$location = "&a=branching";
					echo '<script type="text/javascript">';
					echo '$(document).ready(function() {' .
					'$("#fullscreen").fadeOut("slow");' .
					' vnos_redirect(\'' . $site_url . 'admin/survey/index.php?anketa=' . $this->anketa . $location . '\');' .
					' });';
					echo '</script>';
					die();
				}
			}

			echo '<div id="change_survey_type">';
			echo '<div id="change_survey_type_note">';
			echo $lang['srv_vrsta_survey_note_' . $_POST['new_type'].'_1'];
			echo $lang['srv_vrsta_survey_note_' . $_POST['new_type'].'_2'];
			switch ( $_POST['new_type'] ) {
				case 1:
					echo Help :: display('srv_vrsta_survey_with_form');
					break;
				case 2:
					echo Help :: display('srv_vrsta_survey_with_pages');
					break;
				case 3:
					echo Help :: display('srv_type_with_conditions');
					break;
			}

			if ($errorMsg != null) {
				echo '<div id="div_error" class="red floatLeft"><img src="icons/icons/error.png" alt="" vartical-align="middle" />' . $errorMsg . '</div>';
			}
			echo '</div>';
			echo '<div class="clr"></div>';
			echo '<div class="">';

			$buttonTitle = array(	0 => $lang['srv_vrsta_survey_with_pool'],
			1 => $lang['srv_vrsta_survey_with_form'],
			2 => $lang['srv_vrsta_survey_with_pages'],
			3 => $lang['srv_vrsta_survey_with_conditions'],
			4 => $lang['srv_vrsta_survey_with_usability']);

			echo '<span class="floatRight spaceRight" ><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="survey_chaneg_type(\'' . $_POST['new_type'] . '\',\'true\'); return false;" title="' . $buttonTitle[$_POST['new_type']] . '"><span>' . $buttonTitle[$_POST['new_type']] . '</span></a></div></span>';
			echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="survey_chaneg_type_cancle(); return false;"><span>' . $lang['cancel'] . '</span></a></div></span>';

			echo '<div class="clr"></div>';
			echo '</div>';

			echo '</div>';
		}
		elseif ($_GET['a'] == 'preview_spremenljivka') {
			global $site_path;

			SurveyInfo :: getInstance()->SurveyInit($anketa);

			$offset = 0;
			$zaporedna = 0;
			$count_type = SurveyInfo :: getInstance()->getSurveyCountType();

			if ($count_type) {

				// Preštejemo koliko vprašanj je bilo do sedaj
				$sqlg = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id = (SELECT gru_id FROM srv_spremenljivka WHERE id = '" . $_POST['spremenljivka'] . "')");
				$rowg = mysqli_fetch_assoc($sqlg);
				$vrstni_red = $rowg['vrstni_red'];

				$sqlCountPast = sisplet_query("SELECT count(*) as cnt FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='" . $_POST['anketa'] . "' AND s.gru_id=g.id AND g.vrstni_red < '$vrstni_red' ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");
				$rowCount = mysqli_fetch_assoc($sqlCountPast);
				$offset = $rowCount['cnt'];

				// poiscemo vprasanja / spremenljivke
				$sql = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id=(SELECT gru_id FROM srv_spremenljivka WHERE id = '" . $_POST['spremenljivka'] . "') AND visible='1' ORDER BY vrstni_red ASC");
				while ($row = mysqli_fetch_array($sql)) {
					if ($row['id'] == $_POST['spremenljivka']) {
						$zaporedna++;
						break;
					}
				}
			}

            echo '<div id="preview_spremenljivka">';
            
            echo '<div class="popup_close"><a href="#" onClick="preview_spremenljivka_cancle(); return false;">✕</a></div>';

			include_once('../../main/survey/app/global_function.php');
			new \App\Controllers\SurveyController(true);

			if (isset($_POST['lang_id'])) {
				save('lang_id', (int)$_POST['lang_id']);
			}
			echo '  <div  id="spremenljivka_preview">';
			if ( $_POST['spremenljivka'] == -1 ) {
				\App\Controllers\BodyController::getInstance()->displayIntroduction();
			}
			elseif ( $_POST['spremenljivka'] == -2 ) {
				\App\Controllers\BodyController::getInstance()->displayKonec();
			}
			elseif ( $_POST['spremenljivka'] == -3 ) {
				\App\Controllers\StatisticController::displayStatistika();
			}
			else {
				save('forceShowSpremenljivka', true);
				save('question_preview', true);
				\App\Controllers\Vprasanja\VprasanjaController::getInstance()->displaySpremenljivka($_POST['spremenljivka'], $offset, $zaporedna);
			}
            echo '  </div>';
            
			echo '<div class="buttons_holder">';
			echo '<span class="floatRight">';
			echo ' <div class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_orange" href="#" onclick="preview_spremenljivka_cancle(); return false;"><span>' . $lang['srv_zapri'] . '</span></a></div> ';
			echo ' <div class="buttonwrapper spaceRight floatRight"><a class="ovalbutton ovalbutton_gray" href="#" onclick="window.open(\''.$site_url.'admin/survey/ajax.php?t=branching&a=spremenljivka_preview_print&anketa='.$this->anketa.'&spremenljivka='.$_POST['spremenljivka'].'\', \'print\', \'scrollbars=1\');  return false;"><span><img src="img_0/printer.png" /> ' . $lang['hour_print2'] . '</span></a></div> ';
			echo '</span>';
			echo '</div>';
			
			echo '</div>';
		}
		else if ($_GET['a'] == 'preview_page') {
			echo '<div id="preview_page">';
			echo '  <div  id="page_preview">';
			echo 'TODO';
			echo '  </div>';
			echo '	<div class="clr"></div>';
			echo '	<div class="">';
			echo '  <span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_red" href="#" onclick="preview_page_cancle(); return false;"><span>' . $lang['back'] . '</span></a></div></span>';
			echo '	<div class="clr"></div>';
			echo '	</div>';
			echo '</div>';
		}
		/*else if ($_GET['a'] == 'rename_variable') {
			if ($_POST['show'] == 'true') {
				echo '<input type="text" name="variable" id="variable_' . $spremenljivka . '" value="' . $_POST['value'] . '" class="variableinput variableinput_noborder" onkeyup="edit_spremenljivka_variable(\'' . $spremenljivka . '\');" ' . ' />';
				echo '<script type="text/javascript">';
				echo '$(document).ready(function() {';
				echo '  $("#variable_' . $spremenljivka . '").keypress(function(e){if(e.which==13)  rename_variable(\'' . $spremenljivka . '\', $(this).val()' . ', \'' . $_POST['variable_custom'] . '\', \'false\'); });';
				echo '});';
				echo '</script>';

			} else {
				echo $_POST['value'];
				echo '<a href="#" onclick="rename_variable(\'' . $spremenljivka . '\', \'' . $_POST['value'] . '\', \'' . $_POST['variable_custom'] . '\', \'true\'); return false;" title="' . $lang['edit3'] . '"><img id="edit_variable_' . $spremenljivka . '" src="img_' . $this->skin . '/pencil.png" /></a>';

			}
		}*/
			
		else if ($_GET['a'] == 'form_settings') {
			Common::updateEditStamp();
			
			if($what == 'finish_author' || $what == 'finish_respondent_cms' || $what == 'finish_other' | $what == 'finish_other_emails') {
				sisplet_query("INSERT INTO srv_alert (ank_id, $what) VALUES ('$this->anketa', '$results')
				ON DUPLICATE KEY UPDATE $what = '$results' ");
			}
			else{
				sisplet_query("UPDATE srv_anketa SET $what = '$results' WHERE id = '$this->anketa'");
			}

			// vsilimo refresh podatkov
			SurveyInfo :: getInstance()->resetSurveyData();

			$this->SurveyAdmin->display_form_simple(1);
		}
		else if ($_GET['a'] == 'form_extra') {

			//prikazemo na desni intro
			if($what == 'show_intro'){

				//include_once ('Branching.php');
				$Branching = new Branching($this->anketa);

				echo '    <div id="spremenljivka_-1" class="spremenljivka" style="margin-top:15px">';
				$Branching->introduction_conclusion(-1, 1);
				echo '    </div> <!-- /spremenljivka_-1 -->';
			}
			//prikazemo na desni zakljucek
			else{
				//include_once ('Branching.php');
				$Branching = new Branching($this->anketa);

				echo '    <div id="spremenljivka_-2" class="spremenljivka" style="margin-top:15px">';
				$Branching->introduction_conclusion(-2, 1);
				echo '    </div> <!-- /spremenljivka_-2 -->';
			}
		}
		else if ($_GET['a'] == 'insert_grupa_before') {
			Common::updateEditStamp();
			
			// ugotovimo vrstni red trenutne grupe
			$string_select_vrstni_red_grupe = "SELECT vrstni_red FROM srv_grupa WHERE ank_id = '" . $anketa . "' AND id='" . $_POST['grupa'] . "' ORDER BY vrstni_red DESC";
			$sql_select_vrstni_red_grupe = sisplet_query($string_select_vrstni_red_grupe);
			$row_select_vrstni_red_grupe = mysqli_fetch_assoc($sql_select_vrstni_red_grupe);

			// dodelimmo ime nove grupe
			$string_select_max_grupe = "SELECT max(vrstni_red)+1 as nova_grupa_id FROM srv_grupa WHERE ank_id = '" . $anketa . "'";
			$sql_select_max_grupe = sisplet_query($string_select_max_grupe);
			$row_select_max_grupe = mysqli_fetch_assoc($sql_select_max_grupe);
			$nova_grupa_name = $lang['srv_stran'] . " " . $row_select_max_grupe['nova_grupa_id'];

			// premaknemo vrstni red grupam ki so nižje kot trenutna
			$string_update_vrstni_red = "UPDATE srv_grupa SET vrstni_red = vrstni_red + 1 WHERE ank_id = '" . $anketa . "' AND vrstni_red >= '" . $row_select_vrstni_red_grupe['vrstni_red'] . "'";
			$sql_update_vrstni_red = sisplet_query($string_update_vrstni_red);

			// vstavimo novo grupo
			$string_insert = "INSERT INTO srv_grupa (id, ank_id, naslov, vrstni_red) VALUES ('', '$anketa', '$nova_grupa_name', '$row_select_vrstni_red_grupe[vrstni_red]')";
			$sql_insert = sisplet_query($string_insert);
			$insert_id = mysqli_insert_id($GLOBALS['connect_db']);
			
			// Ce dodamo 4. stran vklopimo progress indicator (pri 3 straneh ali manj je po default izklopljen)
			$sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$anketa'");
			$vrstni_red = mysqli_num_rows($sql);
			if($vrstni_red == 4){
				$sqlP = sisplet_query("UPDATE srv_anketa SET progressbar='1' WHERE id='$anketa'");
			}
			
			$redirect = '&grupa=' . $insert_id;
			echo 'index.php?anketa=' . $anketa . $redirect;
		}
		else if ($_GET['a'] == 'grupa_recount') {
			Common::updateEditStamp();
			
			// preberemo vse gupe sortirane po vrstnem redu
			$string_select_grupe = "SELECT id FROM srv_grupa WHERE ank_id = '" . $anketa . "' ORDER BY vrstni_red";
			$sql_select_grupe = sisplet_query($string_select_grupe);
			$i = 1;
			while ($row_select_grupe = mysqli_fetch_assoc($sql_select_grupe)) {
				$grupa_name = $lang[srv_stran].' '. $i++;

				sisplet_query("UPDATE srv_grupa SET naslov = '".$grupa_name."' WHERE id = '$row_select_grupe[id]'");
			}

			$redirect = '&grupa=' . $grupa;
			echo 'index.php?anketa=' . $anketa . $redirect;
		} else if ($_GET['a'] == 'outputLanguageNote') {
			global $lang;
			
			header('Content-Type: text/html; charset=UTF-8');
			echo $lang[$_POST['note']];
			
		} else if ($_GET['a'] == 'redirect') {
			echo $site_url . 'admin/survey/index.php?anketa=' . $_POST['anketa'] . '&grupa=' . $_POST['grupa'];
			die();
		} else if ($_GET['a'] == 'change_alert_respondent') {
			Common::updateEditStamp();
			$is_checked = ( $_POST['checked'] && $_POST['checked'] == "true" ? true : false);
			$sas = new SurveyAdminSettings();
			$sas->display_alert_label($_POST['what'],$is_checked);
		} else if ($_GET['a'] == 'alert_add_necessary_sysvar') {
			Common::updateEditStamp();
			$this->SurveyAdmin->alert_add_necessary_sysvar();
			$is_checked = ( $_POST['checked'] && $_POST['checked'] == "true" ? true : false);
			$sas = new SurveyAdminSettings();
			$sas->display_alert_label($_POST['what'],$is_checked);
		} else if ($_GET['a'] == 'alert_change_user_from_cms') {
			Common::updateEditStamp();
			$this->SurveyAdmin->alert_change_user_from_cms();
		} else if ($_GET['a'] == 'survey_respondents') {
			Common::updateEditStamp();
			SurveyRespondents :: getInstance()->Init($anketa);
			SurveyRespondents :: getInstance()->Ajax($_GET['b']);
		} elseif ($_GET['a'] == 'comment_manage') {
			$this->SurveyAdmin->comment_manage();
		} elseif ($_GET['a'] == 'recalc_alert_expire') {
			$days = (isset($_POST['days']) && is_numeric($_POST['days']) ? $_POST['days'] : 0);

			// izračunamo nov datum poteka ankete s pomočjo sql-a
			$sql_newDate = sisplet_query("SELECT DATE_SUB(expire,INTERVAL $days DAY) as newdate FROM srv_anketa WHERE id = '$anketa'");
			$row_newDate = mysqli_fetch_assoc($sql_newDate);
			echo $row_newDate['newdate'];

		} elseif ($_GET['a'] == 'displayInfoBox') {
			$this->SurveyAdmin->displayInfoBox();
		} elseif ($_GET['a'] == 'anketa_aktivacija_note') {
			Common::updateEditStamp();
			$this->SurveyAdmin->anketa_aktivacija_note();
		} elseif ($_GET['a'] == 'anketa_aktivacija_mailto_preview') {
			Common::updateEditStamp();
			SurveyInfo::getInstance()->SurveyInit($this->anketa);
			$row = SurveyInfo::getInstance()->getSurveyRow();

			$sas = new SurveyAdminSettings();
			$sas->displayBtnMailtoPreview($row);
				
		} elseif ($_GET['a'] == 'statisticDateRefresh') {
			# refresha datumski pregled
			$ss=new SurveyStatistic();
			$ss->Init($this->anketa);
			$ss->PrepareDateView();
			$ss->DisplayDateView();
		} elseif ($_GET['a'] == 'statisticTimelineDropdownRefresh') {
			# refresha datumski pregled
			$ss=new SurveyStatistic();
			$ss->Init($this->anketa);
			$ss->DisplayTimelineDropdowns();
			
		} elseif ($_GET['a'] == 'statisticReloadInvitationFilter') {
			#refresha pregled po statusih
			$ss = new SurveyStatistic();
			$ss -> Init($this->anketa);
			$ss -> changeInvitationFilter();
		} elseif ($_GET['a'] == 'statisticInfoRefresh') {
			#refresha pregled po statusih
			$ss = new SurveyStatistic();
			$ss -> Init($this->anketa);
			$ss -> prepareStatusView();
			$ss -> DisplayInfoView();
		} elseif ($_GET['a'] == 'statisticAnswerStateRefresh') {
			#refresha pregled po statusih
			$ss = new SurveyStatistic();
			$ss -> Init($this->anketa);
			$ss -> prepareStatusView();
			$ss -> DisplayAnswerStateView();
		} elseif ($_GET['a'] == 'statisticPageStateRefresh') {
			#refresha pregled po statusih
			$ss = new SurveyStatistic();
			$ss -> Init($this->anketa);
			$ss -> prepareStatusView();
			$ss -> DisplayPagesStateView();
		} elseif ($_GET['a'] == 'statisticStatusRefresh') {
			#refresha pregled po statusih
			$ss = new SurveyStatistic();
			$ss -> Init($this->anketa);
			$ss -> prepareStatusView();
			$ss -> DisplayStatusView();
		} elseif ($_GET['a'] == 'statisticReferalRefresh') {
			#refresha pregled po referalih
			$ss = new SurveyStatistic();
			$ss -> Init($this->anketa);
			$ss -> prepareStatusView();
			$ss -> DisplayReferalsView();
		} elseif ($_GET['a'] == 'survey_statistic_referal') {
			$ss = new SurveyStatistic();
			$ss -> Init($this->anketa);
			$ss -> DisplayReferalsList();
		} elseif ($_GET['a'] == 'survey_statistic_ip_list') {
			$ss = new SurveyStatistic();
			$ss -> Init($this->anketa);
			$ss -> DisplayIPList();
		} elseif ($_GET['a'] == 'survey_statistic_status') {
			$ss = new SurveyStatistic();
			$ss -> Init($this->anketa);
			$ss -> DisplayUserByStatus();
		} elseif ($_GET['a'] == 'statisticAnswerStateRefresh') {
			$ss = new SurveyStatistic();
			$ss -> Init($this->anketa);
			$ss -> DisplayAnswerStateView();
		}elseif ($_GET['a'] == 'editsAnalysisContinuousEditing') {
			# refresha neprekinjeno urejanje
			$sea=new SurveyEditsAnalysis($this->anketa);
			$sea->ajax_drawContinuEditsTable();
		}elseif ($_GET['a'] == 'show_bottom_icons') {
			$this->SurveyAdmin->showVprasalnikBottom('gray');
		} elseif ($_GET['a'] == 'save_user_settings') {
			Common::updateEditStamp();
			UserSetting::getInstance()->Init();
			if (isset($_POST['icons_always_on']))
				UserSetting::getInstance()->setUserSetting('icons_always_on', $_POST['icons_always_on']);
			if (isset($_POST['full_screen_edit'])) {
			UserSetting::getInstance()->setUserSetting('full_screen_edit', $_POST['full_screen_edit']);
			}
			UserSetting::getInstance()->saveUserSetting();
		} elseif ($_GET['a'] == 'display_success_save') {
			$this->SurveyAdmin->displaySuccessSave();
		} elseif ($_GET['a'] == 'vnosi_show_status_casi') {
			SurveyStatusCasi :: Init($anketa);
			if (isset($pid) && $pid > 0) {
				SurveyStatusCasi :: setCurentProfileId($pid);
			}
			SurveyStatusCasi :: DisplayProfile($pid);
		} elseif ($_GET['a'] == 'vnosi_show_rezanje_casi') {
			$sas = new SurveyAdminSettings();
			$sas->show_rezanje_casi();
		} elseif ($_GET['a'] == 'vnosi_save_status_casi') {
			Common::updateEditStamp();
			SurveyStatusCasi :: Init($anketa);
			$new_id = SurveyStatusCasi :: saveNewProfile($pid,$_POST['name'],$_POST['status']);
			SurveyStatusCasi :: setDefaultProfileId($new_id);
			echo $new_id;
		} elseif ($_GET['a'] == 'vnosi_change_status_casi') {
			Common::updateEditStamp();
			SurveyStatusCasi :: Init($anketa);
			SurveyStatusCasi :: setDefaultProfileId($pid);
		} elseif ($_GET['a'] == 'vnosi_run_status_casi') {
			Common::updateEditStamp();
			SurveyStatusCasi :: Init($anketa);
			SurveyStatusCasi :: saveProfile($pid,$_POST['status']);
			SurveyStatusCasi :: setDefaultProfileId($pid);
		} elseif ($_GET['a'] == 'vnosi_run_rezanje_casi') {
			Common::updateEditStamp();
			$sas = new SurveyAdminSettings();
			$sas->save_rezanje_casi();
		} elseif ($_GET['a'] == 'vnosi_delete_status_casi') {
			Common::updateEditStamp();
			SurveyStatusCasi :: Init($anketa);
			SurveyStatusCasi :: Delete($pid);
			SurveyStatusCasi :: setDefaultProfileId(1);
		} elseif ($_GET['a'] == 'submitArchiveAnaliza') {
			SurveyAnalysisArchive :: Init($this->anketa);
			SurveyAnalysisArchive :: createArchiveFromAnaliza();
			
		} elseif ($_GET['a'] == 'doArchiveAnaliza') {
			SurveyAnalysisArchive :: Init($this->anketa);
			SurveyAnalysisArchive :: DisplayDoArchive();
		} elseif ($_GET['a'] == 'emailArchiveAnaliza') {
			SurveyAnalysisArchive :: Init($this->anketa);
			SurveyAnalysisArchive :: EmailArchive($_POST['aid']);
			
		} elseif ($_GET['a'] == 'sendEmailArchiveAnaliza') {
			SurveyAnalysisArchive :: Init($this->anketa);
			SurveyAnalysisArchive :: SendEmailArchive($_POST['aid'], $_POST['subject'], $_POST['text'], mysql_real_unescape_string($_POST['emails']));
		} elseif ($_GET['a'] == 'editArchiveAnaliza') {
			SurveyAnalysisArchive :: Init($this->anketa);
			SurveyAnalysisArchive :: EditArchive($_POST['aid']);
		} elseif ($_GET['a'] == 'saveArchiveAnaliza') {
			$aid = (isset($_POST['aid']) && trim($_POST['aid'])) ? $_POST['aid'] : 0;
			$name = (isset($_POST['name']) && trim($_POST['name'])) ? $_POST['name'] : null;
			$note = (isset($_POST['note']) && trim($_POST['note'])) ? $_POST['note'] : null;
			$access = (isset($_POST['access']) && trim($_POST['access'])) ? $_POST['access'] : 0;
                        $access_password = (isset($_POST['access_password']) && trim($_POST['access_password'])) ? $_POST['access_password'] : null;
			$duration = (isset($_POST['duration']) && trim($_POST['duration'])) ? trim($_POST['duration']) : null;
			
			SurveyAnalysisArchive :: Init($this->anketa);
			SurveyAnalysisArchive :: SaveArchive($aid,$name,$note,$access,$duration,$access_password);

		} elseif ($_GET['a'] == 'refreshArchiveAnaliza') {
			SurveyAnalysisArchive :: Init($this->anketa);
			SurveyAnalysisArchive :: ListArchive();
		} elseif ($_GET['a'] == 'askDeleteArchiveAnaliza') {
			$aid = (isset($_POST['aid']) && trim($_POST['aid'])) ? $_POST['aid'] : 0;
			SurveyAnalysisArchive :: Init($this->anketa);
			SurveyAnalysisArchive :: AskDeleteArchive($aid);
		} elseif ($_GET['a'] == 'doDeleteArchiveAnaliza') {
			$aid = (isset($_POST['aid']) && trim($_POST['aid'])) ? $_POST['aid'] : 0;
			SurveyAnalysisArchive :: Init($this->anketa);
			echo SurveyAnalysisArchive :: DoDeleteArchive($aid);
		} elseif ($_GET['a'] == 'createArchiveBeforeEmail') {
				SurveyAnalysisArchive :: Init($this->anketa);
				SurveyAnalysisArchive :: createArchiveBeforeEmail();
		} elseif ($_GET['a'] == 'createArchiveCrosstabBeforeEmail') {
			SurveyAnalysisArchive :: Init($this->anketa);
			SurveyAnalysisArchive :: archiveCrosstabBeforeEmail();
		} elseif ($_GET['a'] == 'toggle_komentarji') {
			Common::updateEditStamp();
			
			SurveySetting::getInstance()->Init($this->anketa);
			if ($_GET['survey_comment'] == '1') {

				SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment', '3');
				SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment_viewadminonly', '3');
				SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment_showalways', '0');

				SurveySetting::getInstance()->setSurveyMiscSetting('question_note_view', '3');
				SurveySetting::getInstance()->setSurveyMiscSetting('question_note_write', '0');
				
				SurveySetting::getInstance()->setSurveyMiscSetting('question_comment', '3');
				SurveySetting::getInstance()->setSurveyMiscSetting('question_comment_viewadminonly', '3');

			} else {
				
				SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment', '');
				SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment_viewadminonly', '');
				SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment_showalways', '');

				SurveySetting::getInstance()->setSurveyMiscSetting('question_note_view', '');
				SurveySetting::getInstance()->setSurveyMiscSetting('question_note_write', '');
				
				SurveySetting::getInstance()->setSurveyMiscSetting('question_comment', '');
				SurveySetting::getInstance()->setSurveyMiscSetting('question_comment_viewadminonly', '');				
			}
			
			header("Location: $_SERVER[HTTP_REFERER]&show_survey_comment=1");
			
		} elseif ($_GET['a'] == 'refreshCollectData') {
		
			SurveyDataDisplay::Init($this->anketa);
			SurveyDataDisplay::displayFilters();
			SurveyDataDisplay::displayVnosiHTML();
			
		} elseif ($_GET['a'] == 'reloadData') {
		
			echo '<div id="analiza_data">';
			Timer::StartTimer($lang['srv_collectData']);
			SurveyDataDisplay::Init($this->anketa);
			SurveyDataDisplay::displayFilters();
			SurveyDataDisplay::displayVnosiHTML();
			echo '</div>'; // div_analiza_data
			
			Timer::GetTimer($lang['srv_collectData']);
		
		} elseif ($_GET['a'] == 'dataDeleteRow') {
		
			if ((int)$usr_id > 0) {
				
				// Preverimo ce gre za prvo popravljanje podatkov in avtomatskoustvarimo arhiv podatkov ce je potrebno
				$sas = new SurveyAdminSettings();
				$sas->checkFirstDataChange();
							
				sisplet_query("UPDATE srv_user SET deleted = '1', time_edit = NOW() WHERE id = '$usr_id'");
				sisplet_query('COMMIT');
				
				echo '0';
			} 
			else {
				echo 'Error: Invalid user ID!';
			}
			
		} elseif ($_GET['a'] == 'dataDeleteMultipleRow') {	// V DELU...
		
			// ulovimo json objekt z userji za brisanje	
			if ( is_array($_POST['users']) && count($_POST['users']) > 0 ) {
				
				// Preverimo ce gre za prvo popravljanje podatkov in avtomatskoustvarimo arhiv podatkov ce je potrebno
				$sas = new SurveyAdminSettings();
				$sas->checkFirstDataChange();
					
				$users = implode(',',$_POST['users']);
				
				sisplet_query("UPDATE srv_user SET deleted = '1', time_edit = NOW() WHERE id IN (".$users.")");
				sisplet_query('COMMIT');
			}
	
		} elseif ($_GET['a'] == 'dataCopyRow') {
		
			if ((int)$usr_id > 0) {
			
				// Preverimo ce gre za prvo popravljanje podatkov in avtomatskoustvarimo arhiv podatkov ce je potrebno
				$sas = new SurveyAdminSettings();
				$sas->checkFirstDataChange();

				global $connect_db;
				SurveyCopy::setSrcSurvey($this->anketa);
				SurveyCopy::setSrcConectDb($connect_db);
				SurveyCopy::setDestSite(0);
				$new_usr_id = SurveyCopy::copyRespondent($usr_id);
								
				echo $new_usr_id;
			} 
			else {
				echo 'Error: Invalid user ID!';
			}
			
		} elseif ($_GET['a'] == 'check_survey_permanent') {
			$row = SurveyInfo::getInstance()->getSurveyRow();
			if ($row['expire'] == PERMANENT_DATE ) {
				echo  'true';
			} else {
				echo 'false';
			}
		} elseif ($_GET['a'] == 'refresh_nastavitve_trajanje') {
			$_GET['a'] = 'trajanje';
			$sas = new SurveyAdminSettings();
			$sas->DisplayNastavitveTrajanje();
			$sas->DisplayNastavitveMaxGlasov();
		} elseif ($_GET['a'] == 'show_collect_data_setting') {
			$sas = new SurveyAdminSettings();
			$sas->Show_collect_data_setting();
		} elseif ($_GET['a'] == 'enable_addvance') {
			Common::updateEditStamp();
			
			if (isset ($_POST['what']) && ($_POST['what'] == 'email' || $_POST['what'] == 'phone')) {
				$_phone = (int) (SurveyInfo::getInstance()->checkSurveyModule('phone') || $_POST['what'] == 'phone');
				$_email = (int) (SurveyInfo::getInstance()->checkSurveyModule('email') || $_POST['what'] == 'email');
				// nastavimo respondente iz baze, kreiramo novo sistemsko spremenljivko
				$this->SurveyAdmin->createUserbaseSystemVariable($_phone, $_email);
				if ($_POST['what'] == 'phone') {
					# redirektamo na telefon
					
					#echo 'index.php?anketa=' . $this->anketa . '&a=telefon'; 
					echo 'index.php?anketa=' . $this->anketa . '&a=telephone'; 
				}
				if ($_POST['what'] == 'email') {
					# redirektamo na email
					#echo 'index.php?anketa=' . $this->anketa . '&a=email&m=emailnastavitve';
					echo 'index.php?anketa=' . $this->anketa . '&a=invitations&m=add_recipients_view';
				}
			}
			
		} elseif ($_GET['a'] == 'save_editcss') {
			Common::updateEditStamp();
			
			$css_content = mysql_real_unescape_string($_POST['css_content']);
			
			SurveyInfo::getInstance()->SurveyInit($this->anketa);
			SurveyInfo::getInstance()->resetSurveyData();
			$row = SurveyInfo::getInstance()->getSurveyRow();
			
			$profile = $_POST['profile'];
			$mobile = $_POST['mobile'];
			
			$sqlp = sisplet_query("SELECT usr_id FROM srv_theme_profiles".($mobile=='1' ? '_mobile' : '')." WHERE id = '$profile'");
			$rowp = mysqli_fetch_array($sqlp);
			
			$skin_name = $rowp['usr_id'].'_'.$_POST['skin_name'];
			//$old_name = $global_user_id.'_'.$_POST['old_name'];
			
			//unlink('../../main/survey/skins/'.$old_name.'.css');
			
			$f = fopen('../../main/survey/skins/'.$skin_name.'.css', 'w');
			fwrite($f, $css_content);
			fclose($f);
			
			ob_clean();
			echo $skin_name;
		
		} elseif ($_GET['a'] == 'testiranje') {
			Common::updateEditStamp();
			
			$sas = new SurveyAdminSettings();
			$sas->tabTestiranje();
			
		} elseif ($_GET['a'] == 'nice_url') {
			$this->ajax_nice_url();
			
		} elseif ($_GET['a'] == 'nice_url_remove') {
			$this->ajax_nice_url_remove();
			
		} elseif ($_GET['a'] == 'comment_ocena') {
			$type = $_POST['type'];
			
			if ($type == 'question_comment') {
				$ocena = $_POST['ocena'];
				$id = $_POST['id'];
				
				$s = sisplet_query("UPDATE post SET ocena = '$ocena' WHERE id = '$id'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			
			} elseif ($type == 'respondent_comment') {
				
				$text2 = $_POST['text2'];
				$id = $_POST['id'];
				
				$s = sisplet_query("UPDATE srv_data_text".$this->db_table." SET text2 = '$text2' WHERE id = '$id'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);				
			} elseif ($type == 'respondent_survey_comment') {
				
				$ocena = $_POST['ocena'];
				$id = $_POST['id'];
				
				$s = sisplet_query("UPDATE srv_comment_resp SET ocena = '$ocena' WHERE id = '$id'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
		
		} elseif ($_GET['a'] == 'comment_on_comment') {
			$id = $_POST['id'];
			$vsebina = $_POST['vsebina'];
			
			if ($vsebina == '') return;
			
			$sql = sisplet_query("SELECT vsebina FROM post WHERE id='$id'");
			$row = mysqli_fetch_array($sql);
			
			$f = new Forum();
			$vsebina = $row['vsebina'].'<blockquote style="margin-left:20px"><b>'.$f->user($global_user_id).'</b> ('.$f->datetime1(date('Y-m-d h:i:s')).'):<br />'.$vsebina.'</blockquote>';
			
			sisplet_query("UPDATE post SET vsebina='$vsebina' WHERE id='$id'");
			
		} elseif ($_GET['a'] == 'archivePopup') {
			echo '<div class="survey_archive_popup">';
			$sas = new SurveyAdminSettings();
			$sas->backup_create_popup();
			echo '</div>';
		
		} elseif ($_GET['a'] == 'backup_data') {
			global $connect_db;
			
			$data = false;
			if ($_GET['data'] == 'true')
				$data = true;
			
			SurveyCopy::setSrcSurvey($this->anketa);
			SurveyCopy::setSrcConectDb($connect_db);
			SurveyCopy::saveArrayFile($data);
			
		} elseif ($_GET['a'] == 'backup_restore') {
			global $connect_db;
			
			$filename = $_GET['filename'];
			
			SurveyCopy::setSrcSurvey($this->anketa);
			SurveyCopy::setSrcConectDb($connect_db);
			SurveyCopy::setDestSite(0);
			$id = SurveyCopy::restoreArrayFile($filename);
			
			if ($id > 0)
				header("Location: index.php?anketa=$id");
			
		} elseif ($_GET['a'] == 'archive_download') {
			global $connect_db;
			
			$data = false;
			if ($_GET['data'] == 'true')
				$data = true;
			
			SurveyCopy::setSrcSurvey($this->anketa);
			SurveyCopy::setSrcConectDb($connect_db);
			SurveyCopy::setDestSite(0);
			
			SurveyCopy::downloadArrayFile($data);
			
		} elseif ($_GET['a'] == 'archive_restore') {
		
			set_time_limit(1800);
		
			$has_data = isset($_POST['has_data']) ? $_POST['has_data'] : 0;
		
			// Restore samo ankete
			if (!empty($_FILES['restore']['tmp_name']) && $has_data != 1) {
				$contents = file_get_contents($_FILES['restore']['tmp_name']);
				//$array = unserialize($contents);
				$array = unserialize(base64_decode($contents));
			}
			// Restore ankete s podatki
			elseif (!empty($_FILES['restore_data']['tmp_name']) && $has_data == 1) {
				$contents = file_get_contents($_FILES['restore_data']['tmp_name']);
				//$array = unserialize($contents);
				$array = unserialize(base64_decode($contents));
			}
			
			if ( is_array($array) ) {
				global $connect_db;
				
				SurveyCopy::setSrcSurvey(-1);
				SurveyCopy::setSrcConectDb($connect_db);
				SurveyCopy::setDestSite(0);
				
				SurveyCopy::setSourceArray($array);
								
				if ( SurveyCopy::getErrors() == '' ){
					
					// Dobimo id kopije ankete in preusmerimo na urejanje
					$new_survey_id = SurveyCopy::doCopy();
					
					echo '<meta http-equiv="refresh" content="0;url='.$site_url.'admin/survey/index.php?anketa='.$new_survey_id.'">';
					//echo '<meta http-equiv="refresh" content="0;url='.$site_url.'admin/survey/">';
				}
				else{
					//print_r( SurveyCopy::getErrors() );
					
					// Ce je prislo do napake preusmerimo nazaj na isto stran
					echo '<meta http-equiv="refresh" content="0;url='.$site_url.'admin/survey/index.php?a=ustvari_anketo&b=archive&error=2">';
				}
			} else {
			
				$error = ($has_data == 1) ? $_FILES['restore_data']['error'] : $_FILES['restore']['error'];
				
				switch ($error) {
					case UPLOAD_ERR_OK:
						break;
					case UPLOAD_ERR_INI_SIZE:
						$response = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
						break;
					case UPLOAD_ERR_FORM_SIZE:
						$response = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
						break;
					case UPLOAD_ERR_PARTIAL:
						$response = 'The uploaded file was only partially uploaded.';
						break;
					case UPLOAD_ERR_NO_FILE:
						$response = 'No file was uploaded.';
						break;
					case UPLOAD_ERR_NO_TMP_DIR:
						$response = 'Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.';
						break;
					case UPLOAD_ERR_CANT_WRITE:
						$response = 'Failed to write file to disk. Introduced in PHP 5.1.0.';
						break;
					case UPLOAD_ERR_EXTENSION:
						$response = 'File upload stopped by extension. Introduced in PHP 5.2.0.';
						break;
					default:
						$response = 'Unknown error';
						$response = 'Wrong file format';
						break;
				}
				//echo $response;
				
				// Ce je prislo do napake preusmerimo nazaj na isto stran
				echo '<meta http-equiv="refresh" content="0;url='.$site_url.'admin/survey/index.php?a=ustvari_anketo&b=archive&error=1">';
			}
		} elseif ($_GET['a'] == 'add_to_library') {
			global $global_user_id;
			
			$where = $_POST['where'];
				
			if ($where == 'lib') {
				$sqlk = sisplet_query("SELECT * FROM srv_library_anketa WHERE ank_id='$this->anketa' AND uid='0'");
				if (mysqli_num_rows($sqlk) == 0) {
					$sql1 = sisplet_query("SELECT id FROM srv_library_folder WHERE uid='0' AND tip='1' AND parent='0' AND lang='$lang[id]'");
		            $row1 = mysqli_fetch_array($sql1);
		            sisplet_query("INSERT INTO srv_library_anketa (ank_id, uid, folder) VALUES ('$this->anketa', '0', '$row1[id]')");
				}
			}
			
			if ($where == 'mylib') {
				$sqlk = sisplet_query("SELECT * FROM srv_library_anketa WHERE ank_id='$this->anketa' AND uid='$global_user_id'");
				if (mysqli_num_rows($sqlk) == 0) {
					$sql1 = sisplet_query("SELECT id FROM srv_library_folder WHERE uid='$global_user_id' AND tip='1' AND parent='0'");
		            $row1 = mysqli_fetch_array($sql1);
		            sisplet_query("INSERT INTO srv_library_anketa (ank_id, uid, folder) VALUES ('$this->anketa', '$global_user_id', '$row1[id]')");
				}
			}
		} elseif ($_GET['a'] == 'new_anketa') {
			global $global_user_id, $site_url;
			
			$naslov = trim($_POST['naslov']);
			
			echo '<div id="new_anketa_div">';			
			$newSurvey = new NewSurvey();
			$newSurvey->displayNewSurveyPage();			
			echo '</div>';
		
		} elseif ($_GET['a'] == 'srv_password') {
			
			$password = $_POST['password'];
			if ($password != '') {
				$s = sisplet_query("REPLACE INTO srv_password (ank_id, password) VALUES ('$this->anketa', '$password')");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
		
		} elseif ($_GET['a'] == 'srv_password_del') {
			
			$password = $_POST['password'];
			if ($password != '') {
				$s = sisplet_query("DELETE FROM srv_password WHERE ank_id='$this->anketa' AND password = '$password'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
		} elseif ($_GET['a'] == 'getDataStatusTitles') {
			global $lang;
			$return = array();
			$return['status6'] = $lang['srv_userstatus_6'];
			$return['status5'] = $lang['srv_userstatus_5'];
			$return['status4'] = $lang['srv_userstatus_4'];
			$return['status3'] = $lang['srv_userstatus_3'];
			$return['status2'] = $lang['srv_userstatus_2'];
			$return['status1'] = $lang['srv_userstatus_1'];
			$return['status0'] = $lang['srv_userstatus_0'];

			echo json_encode($return);
			
		} elseif ($_GET['a'] == 'getDataLurkerTitles') {
			global $lang;

			$return = array();
			$return['status1'] = $lang['yes'];
			$return['status0'] = $lang['no'];

			echo json_encode($return);
			
		} elseif ($_GET['a'] == 'getDataEmailTitles') {
			global $lang;

			$return = array();
			$return['email0'] = $lang['srv_data_emailstatus_0'];
			$return['email1'] = $lang['srv_data_emailstatus_1'];
			$return['email2'] = $lang['srv_data_emailstatus_2'];

			echo json_encode($return);
			
		} elseif ($_GET['a'] == 'alert_custom') {
			$this->ajax_alert_custom();
			
		} elseif ($_GET['a'] == 'alert_custom_save') {
			$this->ajax_alert_custom_save();

		} elseif ($_GET['a'] == 'exportChangeCheckbox') {
				
			if(isset($_POST['name']) && $_POST['name'] != '') {
				
				session_start();

				// Resetiramo vse nastavitve
				$_SESSION['exportHiddenSystem'] = false;
				$_SESSION['exportFullMeta'] = false;
				$_SESSION['exportOnlyData'] = false;

				// Nastavimo ustrezno nastavitev
				$_SESSION[$_POST['name']] = true;
			}
		} elseif ($_GET['a'] == 'exportChangeRadio') {
			if(isset($_POST['name']) && $_POST['name'] != '') {
				session_start();
				
				$_SESSION[$_POST['name']] = $_POST['value'];
			}
		} elseif ($_GET['a'] == 'setExpirePermanent') {
			
			$rowDates['starts'] = SurveyInfo::getInstance()->getSurveyColumn('starts');
			$rowDates['expire'] = SurveyInfo::getInstance()->getSurveyColumn('expire');
			
			# če slučajno nimamo nastavljenih datumov
			$dateToday = date("Y-m-d"); // danes
			$rowDates['starts'] = (($rowDates['starts'] == '0000-00-00') ? $dateToday : $rowDates['starts']);
			
			if (isset($_POST['makePermanent']) && $_POST['makePermanent'] == 'true') {
				# anketo naredimo za trajno
				$rowDates['expire'] = PERMANENT_DATE;
				$permanent = '1';
			} else {
				# anketo ni več trajna
				#določimo nov datum poteka
				$cd = strtotime($dateToday);
				# za koliko časa aktiviramo
				$mth = 3;
				$dateMonth = date('Y-m-d', mktime(0, 0, 0, date('m', $cd) + $mth, date('d', $cd), date('Y', $cd)));
				$rowDates['expire'] = (($rowDates['expire'] == '0000-00-00' || $rowDates['expire'] == PERMANENT_DATE) ? $dateMonth : $rowDates['expire']);
				$permanent = '0';
			}
			
			#nastavimo aktivnost
			$sql_update_string = "UPDATE srv_anketa SET active='1', starts='".$rowDates['starts']."', expire='".$rowDates['expire']."'  WHERE id = '$anketa'";
			$sql_update = sisplet_query($sql_update_string);
			if (!$sql_update) {
				$msg = $sql_update_string. ' : '. mysqli_error($GLOBALS['connect_db']);
			}
			
			# dodamo zapis v srv_activity
			$activity_insert_string = "INSERT IGNORE INTO srv_activity (sid, starts, expire, uid) VALUES('".$anketa."', '".$rowDates['starts']."', '".$rowDates['expire']."', '".$global_user_id."')";
			$msg.=$activity_insert_string;
			$sql_insert = sisplet_query($activity_insert_string);
			if (!$sql_insert) {
				$msg .= $activity_insert_string. ' : '. mysqli_error($GLOBALS['connect_db']);
			}		
			sisplet_query('COMMIT');

			$_expire = explode('-',$rowDates['expire']);
			$expire =  $_expire[2].'.'.$_expire[1].'.'.$_expire[0];
				
			echo json_encode(array('expire'=>$expire, 'permanent' => $permanent, 'msg'=>$msg));
			return;
		} elseif ($_GET['a'] == 'doCMSUserFilterCheckbox') {
			session_start();
			$_SESSION['sid_'.$anketa]['doCMSUserFilter'] = $_POST['checked'] == 'true' ? true : false;
			session_commit();
		
		} elseif ($_GET['a'] == 'anketa_restore') {
			$this->ajax_anketa_restore();
			
		} elseif ($_GET['a'] == 'data_restore') {
			$this->ajax_data_restore();
			
		} elseif ($_GET['a'] == 'deleteSurveyDataFile') {
			$this->ajax_deleteSurveyDataFile();
			
		} elseif ($_GET['a'] == 'analisysIncludeTestData') {
			session_start();
			$_SESSION['testData'][$this->anketa]['includeTestData'] = $_POST['includeTestData'];
			session_commit();
		
		} elseif ($_GET['a'] == 'reminder_all') {
			$this->ajax_reminder_all();
				
		} elseif ($_GET['a'] == 'get_variable_labels') {
			$this->ajax_get_variable_labels();
			
		} elseif ($_GET['a'] == 'remove_logo') {
			$this->ajax_remove_logo();
			
		} elseif ($_GET['a'] == 'makeEncodedIzvozUrlString') {
			echo ''.makeEncodedIzvozUrlString($_POST['string']);
			
		} elseif ($_GET['a'] == 'dostop_admin') {
			$this->ajax_dostop_admin();
			
		} elseif ($_GET['a'] == 'testiranje_preview_settings') {
			$this->ajax_testiranje_preview_settings();
			
		} elseif ($_GET['a'] == 'testiranje_preview_settings_save') {
			$this->ajax_testiranje_preview_settings_save();
			
		} elseif ($_GET['a'] == 'comments_onoff') {
			$this->ajax_comments_onoff();
		
		} elseif ($_GET['a'] == 'runLanguageTechnology') {
			header('Content-Type: application/json; charset=UTF-8');
			$parsedData= array();			
			try {
				$settings = array();
				foreach(array('lt_language', 'lt_min_FWD', 'lt_min_nNoM', 'lt_min_vNoM', 'lt_special_setting') AS $_key) {
                if (isset ($_POST['settings'][$_key]))
                    $settings[$_key] = $_POST['settings'][$_key];
                }

				$slt = new SurveyLanguageTechnology($this->anketa);
				$settings = $slt->setup($settings);
				$parsedData = $slt->parseSpremenljivka($spremenljivka);
				$settings['lt_spremenljivka'] = $spremenljivka;
				
				$parsedData['setting'] = $settings;
				$parsedData['error'] = array('hasError'=> false, 'msg' => '');

			} catch (Exception $e) {
				$parsedData['error'] = array('hasError'=> true, 'msg' => 'Prišlo je do napake');
			}
			if (isset($parsedData['language'])) {
				//unset($parsedData['language']);
			}	
			echo json_encode($parsedData);
			exit();
        } elseif ($_GET['a'] == 'runLanguageTechnologyWord') {
            header('Content-Type: application/json; charset=UTF-8');
            $parsedData= array();
            try {
                $settings = array();
                if (isset ($_POST['lt_language']))
                    $settings['lt_language'] = $_POST['lt_language'];
                if (isset ($_POST['lt_min_FWD']))
                    $settings['lt_min_FWD'] = $_POST['lt_min_FWD'];
                if (isset ($_POST['lt_min_nNoM']))
                    $settings['lt_min_nNoM'] = $_POST['lt_min_nNoM'];
                if (isset ($_POST['lt_min_vNoM']))
                    $settings['lt_min_vNoM'] = $_POST['lt_min_vNoM'];
            
                $word = $_REQUEST['lt_word'];
                $wordType = $_REQUEST['lt_tag'];
                $slt = new SurveyLanguageTechnology($this->anketa);
                $settings = $slt->setup($settings);
                $parsedData = $slt->parseWord($word, $wordType);
                $settings['lt_spremenljivka'] = $spremenljivka;
            
                $parsedData['setting'] = $settings;
                $parsedData['error'] = array('hasError'=> false, 'msg' => '');
            
            } catch (Exception $e) {
                $parsedData['error'] = array('hasError'=> true, 'msg' => 'Prišlo je do napake');
            
            }
            if (isset($parsedData['language'])) {
                //unset($parsedData['language']);
            }
            echo json_encode($parsedData);
            exit();
        } elseif ($_GET['a'] == 'runLanguageTechnologyHypoHypernym') {
            header('Content-Type: application/json; charset=UTF-8');
            $parsedData= array();
            try {
                $synsets = $_REQUEST['synsets'];
               
                $slt = new SurveyLanguageTechnology($this->anketa);
                $settings = array();

                if (isset ($_POST['settings']['lt_language']))
                    $settings['lt_language'] = $_POST['settings']['lt_language'];
                $settings = $slt->setup($settings);

                $parsedData = $slt->getHypoHypernym($synsets);
                
                $parsedData['setting']['lt_spremenljivka'] = $spremenljivka;
                $parsedData['setting']['synsets'] = $synsets;
            
                $parsedData['error'] = array('hasError'=> false, 'msg' => '');
            
            } catch (Exception $e) {
                $parsedData['error'] = array('hasError'=> true, 'msg' => 'Prišlo je do napake');
            
            }
            if (isset($parsedData['language'])) {
                //unset($parsedData['language']);
            }
            echo json_encode($parsedData);
            exit();
		} elseif ($_GET['a'] == 'exportLanguageTechnology') {
			header('Content-Type: application/json; charset=UTF-8');
			
			$result = array();
			try {
				$slt = new SurveyLanguageTechnology($this->anketa);
				$url = $slt->exportLanguageTechnology($_REQUEST['lt_data'], $_REQUEST['language']);
				$result['error'] = false; 
				$result['filename'] = $url; 
				$result['url'] = makeEncodedIzvozUrlString('izvoz.php?a=lt_excel&file=' . $url);; 
			} catch (Exception $e) {
				$result['error'] = true;
				$result['filename'] = '';
				$result['url'] = '';
				$result['msg'] = $e->getMessage();
			}
			echo json_encode($result);
			exit();
		} else { // genericna resitev za vse nadaljne
			
			$ajax = 'ajax_' . $_GET['a'];
			
			if ( method_exists('SurveyAdminAjax', $ajax) )
				$this->$ajax();
			else
				echo 'method: "'.$ajax.'" does not exist';
		}
		
	}
	// Ajax :end
	
	function ajax_brisi_spremenljivko () {
		global $lang;
		
		Common::updateEditStamp();
		
		$spremenljivka = $_POST['spremenljivka'];
		$anketa = $_POST['anketa'];
		$grupa = $_POST['grupa'];
		
		$this->grupa = $grupa;
		$this->anketa = $anketa;

		$confirmed = $_POST['confirmed'];
		$return = array();
		
		// preverimo, da ni spremenljivka v kaksnem pogoju, preden jo zbrisemo	
		if (!$this->SurveyAdmin->check_spremenljivka_delete($spremenljivka)) {
            $return['error'] = 1;
            
            $return['output'] = '<div class="popup_close"><a href="#" onClick="$(\'#dropped_alert\').hide(); $(\'#fade\').fadeOut(); return false;">✕</a></div>';
            $return['output'] .= '<h2>'.$lang['srv_warning'].'</h2>';

            $return['output'] .= '<p>'.$lang['spremenljivka_delete_in_if'].'</p>';

            $return['output'] .= '<span class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#dropped_alert\').hide(); $(\'#fade\').fadeOut(); return false;"><span>'.$lang['srv_analiza_arhiviraj_cancle'].'</span></a></span>';
            
            echo json_encode($return);
            
			return;
		}
		
		// preverimo, ce obstajajo ze podatki za spremenljivko - v tem primeru damo dodaten error
		if ($confirmed != '1') {

			$sql = sisplet_query("SELECT count(*) AS count FROM srv_user WHERE ank_id='$this->anketa' AND deleted='0' AND preview='0'");
            $row = mysqli_fetch_array($sql);
            
			if ($row['count'] > 0) {

                $return['error'] = 2;
                
                $return['output'] = '<div class="popup_close"><a href="#" onClick="$(\'#dropped_alert\').hide(); $(\'#fade\').fadeOut(); return false;">✕</a></div>';
                $return['output'] .= '<h2>'.$lang['srv_warning'].'</h2>';

				$return['output'] .= '<p>'.$lang['spremenljivka_delete_data'].'</p>';
                $return['output'] .= '<p>'.$lang['srv_brisispremenljivkoconfirm_data'].'</p><br />';
                
                $return['output'] .= '<span class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_orange" href="#" onclick="brisi_spremenljivko(\''.$spremenljivka.'\', \'\', \'1\'); return false;"><span>'.$lang['srv_brisispremenljivko'].'</span></a></span>';
                $return['output'] .= '<span class="buttonwrapper floatRight spaceRight"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#dropped_alert\').hide(); $(\'#fade\').fadeOut(); return false;"><span>'.$lang['srv_analiza_arhiviraj_cancle'].'</span></a></span>';
				//$return['output'] .= '<p><a href="#" onclick="brisi_spremenljivko(\''.$spremenljivka.'\', \'\', \'1\'); return false;">'.$lang['srv_brisispremenljivko'].'</a> <a href="#" onclick="$(\'#dropped_alert\').hide(); $(\'#fade\').fadeOut(); return false;">'.$lang['srv_analiza_arhiviraj_cancle'].'</a></p>';
                
                echo json_encode($return);
                
                return;
			}

			//Preverimo, če je vklopljena hierarhija, ker potem tudi ne moremo bristati vloge
			if(SurveyInfo::getInstance()->checkSurveyModule('hierarhija') && Cache::get_spremenljivka($spremenljivka, 'variable') == 'vloga'){
				$return['error'] = 1;
				$return['output'] = '<p>'.$lang['srv_hierarchy_delete_vloga'].'</p>';
                
                echo json_encode($return);
                
                return;
			}
		}

		$b = new Branching($this->anketa);
		
		// ce je za spremenljivko PB, ga prestavimo na prejsnjo spremenljivko
		$rowg = Cache::srv_branching($spremenljivka, 0);
		if ($rowg['pagebreak'] == 1) {
			$s = sisplet_query("UPDATE srv_branching SET pagebreak='1' WHERE element_spr='{$b->find_prev_spr($spremenljivka)}' AND ank_id='$this->anketa'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		}
		$this->SurveyAdmin->brisi_spremenljivko($spremenljivka);

		if ($_COOKIE['srv_clipboard_' . $this->anketa] == $spremenljivka)
			$this->SurveyAdmin->clipboard_display(-1);

		$this->SurveyAdmin->prestevilci(0);

		$b->check_loop();	
		ob_start();	
		$b->branching_struktura();
		
		$return['error'] = 0;
		$return['output'] = ob_get_clean().$echo;
		
		echo json_encode($return);
	}
	
	function ajax_nice_url () {
		global $site_path;
		
		Common::updateEditStamp();
		
		$add = false;
		
		$anketa = $_POST['anketa'];
		$nice_url = $_POST['nice_url'];
		
		$nice_url = preg_replace("#[^A-Za-z0-9-]#", "", $nice_url);
		
		$f = @fopen($site_path.'.htaccess', 'rb');
		if ($f !== false)  {
			$add = true;
			while (!feof($f)) {
				$r = fgets($f);
				if (strpos($r, "^".$nice_url.'\b') !== false) {		// preverimo, da ni tak redirect ze dodan
					$add = false;
				}
			}
			fclose($f);
		}
		
		// Ne pustimo manj kot 3 znake
		if (strlen($nice_url) < 3) $add = false;
		
		// Ne pustimo vec kot 20 znakov
		if (strlen($nice_url) > 20) $add = false;
		
		sisplet_query("BEGIN");	// damo v transakcijo, da se ne more kdo med tedva querija ustulit
		
		$sql = sisplet_query("SELECT id FROM srv_nice_links WHERE link = '$nice_url'");
		if (mysqli_num_rows($sql) > 0) $add = false;
		
		if (SurveyInfo::getInstance()->checkSurveyModule('uporabnost'))
			$link = 'main/survey/uporabnost.php?anketa=' . $anketa ;
		else
			$link = 'main/survey/index.php?anketa=' . $anketa ;
		

		// Dodamo nice url
		if ($add) {
			
			// Dodamo nice url za anketo
			$f = @fopen($site_path.'.htaccess', 'a');
			if ($f !== false) {
				fwrite($f, "\nRewriteRule ^".$nice_url.'\b(.*)			'.$link."&foo=\$1&%{QUERY_STRING}");
				
				// Dodamo nice url v bazo
				$s = sisplet_query("INSERT INTO srv_nice_links (id,ank_id,link) VALUES ('','$this->anketa','$nice_url')");
				
				// Dobimo id nice url-ja ce ga slucajno rabimo za skupine
				$nice_url_id = mysqli_insert_id($GLOBALS['connect_db']);

				// Preverimo ce obstajajo skupine in se njim nastavimo nice url
				$ss = new SurveySkupine($this->anketa);
				$skupine_spr_id = $ss->hasSkupine();

				// Ce imamo skupine jim dodamo nice url
				if($skupine_spr_id != 0){

					$sqlS = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$skupine_spr_id'");
					if(mysqli_num_rows($sqlS) > 0){

						// Loop cez obstojece skupine
						while($rowS = mysqli_fetch_array($sqlS)){

							$nice_url_skupina = $nice_url.'_'.$rowS['vrstni_red'];
							$link_skupina = $link.'&skupina='.$rowS['id'];
									
							// Skupini dodamo nice url zapis v htaccess
							fwrite($f, "\nRewriteRule ^".$nice_url_skupina.'\b(.*)			'.$link_skupina."&foo=\$1&%{QUERY_STRING}");
									
							// Skupini zapisemo nice url se v bazo
							$sqlSI = sisplet_query("INSERT INTO srv_nice_links_skupine 
													(ank_id, nice_link_id, vre_id, link) 
													VALUES 
													('".$this->anketa."', '".$nice_url_id."', '".$rowS['id']."', '".$nice_url_skupina."')
												");
						}
					}
				}

				fclose($f);
			}
		}
		
		sisplet_query("COMMIT");
		
		echo 'index.php?anketa='.$anketa.'&a=vabila&m=settings'.(!$add?'&error='.$nice_url:'');
	}
	
	function ajax_nice_url_remove () {
		global $site_path;
		
		Common::updateEditStamp();
		
		$anketa = $_GET['anketa'];
		$nice_url_id = $_GET['nice_url'];
		
		$sql = sisplet_query("SELECT id, link FROM srv_nice_links WHERE id = '$nice_url_id'");
		$row = mysqli_fetch_array($sql);
		
		$nice_url = $row['link'];
		
		$f = fopen($site_path.'.htaccess', 'rb');
		if ($f !== false) {
			$output = array();
			while (!feof($f)) {
				$r = fgets($f);
				if (strpos($r, "^".$nice_url.'\b(.*)	') !== false && strpos($r, "?anketa=".$anketa."") !== false) {
					// kao pobrisemo vrstico
				} else {
					$output[] = $r;
				}
			}
			fclose($f);
		}
		
		if (count($output) > 0) {
			
			$f = fopen($site_path.'.htaccess', 'w');
			if ($f !== false) {
				foreach ($output AS $line) {
					fwrite($f, $line);
				}
				fclose($f);
				
				$sql = sisplet_query("DELETE FROM srv_nice_links WHERE id = '$nice_url_id'");
				
				// Preverimo ce imamo skupine s tem urljem in jih pobrisemo
				$sqlS = sisplet_query("SELECT * FROM srv_nice_links_skupine WHERE ank_id='$anketa' AND nice_link_id='$nice_url_id'");
				if(mysqli_num_rows($sqlS) > 0){
										
					$f = fopen($site_path.'.htaccess', 'rb');
					if ($f !== false) {
						$outputS = array();
						while (!feof($f)) {
						
							$r = fgets($f);
							
							// Loop cez vse skupine
							$delete = false;
							$sqlS = sisplet_query("SELECT * FROM srv_nice_links_skupine WHERE ank_id='$anketa' AND nice_link_id='$nice_url_id'");
							while($rowS = mysqli_fetch_array($sqlS)){
								
								if (strpos($r, "^".$rowS['link'].'\b(.*)	') !== false && strpos($r, "?anketa=".$anketa."&skupina=".$rowS['vre_id']."") !== false) {
									// pobrisemo vrstico in vnos v bazi
									$sqlD = sisplet_query("DELETE FROM srv_nice_links_skupine WHERE ank_id='$anketa' AND nice_link_id='$row[id]' AND vre_id='$rowS[vre_id]'");
									$delete = true;
								} 
							}
							if($delete == false){
								$outputS[] = $r;
							}
						}
						fclose($f);
					}
					
					if (count($outputS) > 0) {
						
						$f = fopen($site_path.'.htaccess', 'w');
						if ($f !== false) {
							foreach ($outputS AS $line) {
								fwrite($f, $line);
							}
							fclose($f);
						}
					}						
				}
			}
		}
	
		
		header('Location: index.php?anketa='.$anketa.'&a=vabila&m=settings');		
	}
	
	function ajax_alert_custom() {
		global $lang;
		global $global_user_id;
		global $app_settings;
		
		$anketa = $this->anketa;
		$type = $_POST['type'];
		$uid = (int)$_POST['uid'];
		
		// najprej preberemo splosne nastavitve alertov
		$sqlAlert = sisplet_query("SELECT * FROM srv_alert WHERE ank_id = '$anketa'");
		if (!$sqlAlert)
		    echo mysqli_error($GLOBALS['connect_db']);
        
        if (mysqli_num_rows($sqlAlert) > 0) {
			$rowAlert = mysqli_fetch_array($sqlAlert);
        } 
        else {
			SurveyAlert::getInstance()->Init($anketa, $global_user_id);
			$rowAlert = SurveyAlert::setDefaultAlertBeforeExpire();
		}
		
		$subject = ($rowAlert['finish_subject'] ? $rowAlert['finish_subject'] : $lang['srv_alert_finish_subject']);
		
        // Custom podpis
        $signature = Common::getEmailSignature();
		$text = ($rowAlert['finish_text'] != ''? $rowAlert['finish_text'] : nl2br($lang['srv_alert_finish_text'].$signature) );


		// z nastavitvami za trenutnega povozimo splošne nastavitve
		$sql = sisplet_query("SELECT subject, text FROM srv_alert_custom WHERE ank_id='$this->anketa' AND type='$type' AND uid='$uid'");
		if (mysqli_num_rows($sql) > 0) {
			$row = mysqli_fetch_array($sql);
			
			$subject = $row['subject'];
			$text = $row['text'];
		}
		
		echo '<form action="ajax.php?a=alert_custom_save" name="alert_custom" method="post">';
		echo '<input type="hidden" name="anketa" value="'.$this->anketa.'">';
		echo '<input type="hidden" name="type" value="'.$type.'">';
		echo '<input type="hidden" name="uid" value="'.$uid.'">';
		
		echo '<p><label for="subject">' . $lang['subject'] . ': </label><input type="text" id="subject" name="subject" value="' . $subject . '" size="90"/></p>';

		
		// prikaze editor za ne-spremenljivko (za karkoli druzga pac)
		echo '    <p><label for="text">' . $lang['text'] . ':</label>';
		echo '    <textarea name="text" id="text" rows="3" >' . $text . '</textarea>';
		echo '    </p>';
		echo '</div>';
		
		echo '<div class="buttonwrapper floatRight spaceRight"><a class="ovalbutton ovalbutton_orange" onclick="$(\'form[name=alert_custom]\').submit(); return false;" href="#"><span>'.$lang['save'].'</span></a></div>';
		echo '<div class="buttonwrapper floatRight spaceRight"><a class="ovalbutton ovalbutton_gray" onclick="remove_editor(\'text\'); $(\'#fade\').fadeOut(\'slow\'); $(\'#vrednost_edit\').hide().html(\'\'); return false;" href="#"><span>'.$lang['srv_analiza_arhiviraj_cancle'].'</span></a></div>';
		
		echo '</form>';
		
	}
	
	function ajax_alert_custom_save () {
		
		$uid = $_POST['uid'];
		$type = $_POST['type'];
		
		$subject = $_POST['subject'];
		$text = $_POST['text'];
		
		$sql = sisplet_query("REPLACE INTO srv_alert_custom (ank_id, type, uid, subject, text) VALUES ('$this->anketa', '$type', '$uid', '$subject', '$text')");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
				
		header("Location: index.php?anketa={$this->anketa}&a=alert");
		
	}	
	
	function ajax_anketa_restore() {
		
		$id = (int)$_POST['id'];
		
		sisplet_query("UPDATE srv_anketa SET active='0' WHERE active='-1' AND id='$id'");
		
	}
	
	function ajax_data_restore() {
		
		$id = (int)$_POST['id'];
		
		sisplet_query("UPDATE srv_user SET deleted='0', time_edit=NOW() WHERE deleted='1' AND ank_id='$id'");
		
		header("Location: index.php?anketa=".$id);
		
	}

	function ajax_deleteSurveyDataFile() {
		global $lang, $admin_type, $site_path;
		$id = (int)$_POST['anketa'];
		$result = $lang['srv_deleteSurveyDataFile_error_note'];
		if ( $id > 0) {
			#pobrišemo header datoteke
			if (file_exists($site_path.'admin/survey/SurveyData/'.'export_header_'.$id.'.dat')) {
				unlink($site_path.'admin/survey/SurveyData/'.'export_header_'.$id.'.dat');
			}
			
			#pobrišemo data datoteko
			if (file_exists($site_path.'admin/survey/SurveyData/'.'export_data_'.$id.'.dat')) {
				unlink($site_path.'admin/survey/SurveyData/'.'export_data_'.$id.'.dat');
			}
			
			#pobrišemo tmp
			if (file_exists($site_path.'admin/survey/SurveyData/'.'export_data_'.$id.'.tmp')) {
				unlink($site_path.'admin/survey/SurveyData/'.'export_data_'.$id.'.tmp');
			}
			
			# odstranimo morebitne SN datoteke - header
			$files = glob($site_path.'admin/survey/SurveyData/'.'export_sn_header_'.$id.'_*.dat');
			if(count($files ) > 0) {
				foreach ($files AS $file) {
					unlink($file);
				}
			}			
			# odstranimo morebitne SN datoteke - data
			$files = glob($site_path.'admin/survey/SurveyData/'.'export_sn_data_'.$id.'_*.dat');
			if(count($files ) > 0) {
				foreach ($files AS $file) {
					unlink($file);
				}
			}
			$result = $lang['srv_deleteSurveyDataFile_success_note'];
		}
		return $result;
	}
	
	function ajax_reminder_all () {
		
		$what = $_GET['what'];
	
		if ( ! $this->anketa > 0) return;
		
		if ($what == 'soft') {
			
			$s = sisplet_query("UPDATE srv_spremenljivka SET reminder='1' WHERE tip !='5' AND id IN (SELECT element_spr FROM srv_branching WHERE ank_id='$this->anketa' AND element_spr > 0)");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);			
		
		} else if ($what == 'hard') {
			
			$s = sisplet_query("UPDATE srv_spremenljivka SET reminder='2' WHERE tip !='5' AND id IN (SELECT element_spr FROM srv_branching WHERE ank_id='$this->anketa' AND element_spr > 0)");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			
		} else if ($what == 'no') {
			
			$s = sisplet_query("UPDATE srv_spremenljivka SET reminder='0' WHERE id IN (SELECT element_spr FROM srv_branching WHERE ank_id='$this->anketa' AND element_spr > 0)");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			
		}
		
		header("Location: index.php?anketa=".$this->anketa);
		
	}
	
	function ajax_get_variable_labels () {
		global $lang;
		
		$spr = array_unique( $_POST['spr'] );
		
		$response = array();
		
		foreach ($spr AS $spr_id) {
			
			$s = sisplet_query("SELECT tip FROM srv_spremenljivka WHERE id = '$spr_id'");
			$r = mysqli_fetch_array($s);
			
			if ( in_array($r['tip'], array(1, 3)) ) {
			
				$output = array();
				
				$output['spr'] = $spr_id;
				$output['tip'] = $r['tip'];
				
				$output['values'] = array();
				
				$sql = sisplet_query("SELECT naslov, variable FROM srv_vrednost WHERE spr_id='$spr_id' ORDER BY vrstni_red ASC");
				while ($row = mysqli_fetch_array($sql)) {					
					$output['values'][$row['variable']] = strip_tags( $row['naslov'] );
				}
				
				$output['values']['-1'] = $lang['srv_bottom_data_legend_note_li1a'];
				$output['values']['-2'] = $lang['srv_bottom_data_legend_note_li2a'];
				$output['values']['-3'] = $lang['srv_bottom_data_legend_note_li3a'];
				$output['values']['-4'] = $lang['srv_bottom_data_legend_note_li4a'];
				$output['values']['-5'] = $lang['srv_bottom_data_legend_note_li5a'];
				
				$response[] = $output;
				
			} elseif ( in_array($r['tip'], array(6, 16)) ) {
				
				$output = array();
				
				$output['spr'] = $spr_id;
				$output['tip'] = $r['tip'];
				
				$output['values'] = array();
				
				$sql = sisplet_query("SELECT naslov, variable FROM srv_grid WHERE spr_id='$spr_id' ORDER BY vrstni_red ASC");
				while ($row = mysqli_fetch_array($sql)) {
					$output['values'][$row['variable']] = strip_tags( $row['naslov'] );
				}
				
				$output['values']['-1'] = $lang['srv_bottom_data_legend_note_li1a'];
				$output['values']['-2'] = $lang['srv_bottom_data_legend_note_li2a'];
				$output['values']['-3'] = $lang['srv_bottom_data_legend_note_li3a'];
				$output['values']['-4'] = $lang['srv_bottom_data_legend_note_li4a'];
				$output['values']['-5'] = $lang['srv_bottom_data_legend_note_li5a'];
				
				$response[] = $output;
			}
			
		}
		
		echo json_encode($response);
		
	}
	
	function ajax_remove_logo () {
		
		$profile = $_POST['profile'];
		
		$sql = sisplet_query("SELECT logo FROM srv_theme_profiles WHERE id = '$profile'");
		$row = mysqli_fetch_array($sql);
		
		if ($row['logo'] != '')
			unlink( dirname(__FILE__).'/../../main/survey/uploads/'.$row['logo'] );
		
		$s = sisplet_query("UPDATE srv_anketa SET logo = '' WHERE id = '$this->anketa'");
		$s = sisplet_query("UPDATE srv_theme_profiles SET logo = '' WHERE id = '$profile'");
		
		
		
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
	}
	
	function ajax_dostop_active_show_all(){

		$show_all = $_POST['show_all'];
		
		$sas = new SurveyAdminSettings();
		$sas->display_dostop_users($show_all);
	}
	
	/**
	* omogoci administratorjem dostop do ankete (za omejeno obdobje)
	* 
	*/
	function ajax_dostop_admin () {
		global $lang;
		
		if ($_POST['remove'] == 1) {
			$s = sisplet_query("UPDATE srv_anketa SET dostop_admin = NOW() - INTERVAL 1 WEEK WHERE id = '$this->anketa'");
		} else {
			$s = sisplet_query("UPDATE srv_anketa SET dostop_admin = NOW() + INTERVAL 1 WEEK WHERE id = '$this->anketa'");
		}
		
		SurveyInfo::getInstance()->resetSurveyData();
		
		$this->SurveyAdmin->request_help_content();
	}

	
	function ajax_testiranje_preview_settings () {
		global $lang;
		global $global_user_id;
		
        SurveySetting::getInstance()->Init($this->anketa);
        
        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
        $userAccess = UserAccess::getInstance($global_user_id);

		$preview_disableif = SurveySetting::getInstance()->getSurveyMiscSetting('preview_disableif');
		$preview_disablealert = SurveySetting::getInstance()->getSurveyMiscSetting('preview_disablealert');
		$preview_displayifs = SurveySetting::getInstance()->getSurveyMiscSetting('preview_displayifs');
		$preview_displayvariables = SurveySetting::getInstance()->getSurveyMiscSetting('preview_displayvariables');
		$preview_hidecomment = SurveySetting::getInstance()->getSurveyMiscSetting('preview_hidecomment');
		
		$question_resp_comment = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment');
		$question_resp_comment_show_open = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_show_open');
		
		$question_resp_comment_inicialke = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_inicialke');
		$question_resp_comment_inicialke_alert = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_inicialke_alert');
		
        echo '<h2>'.$lang['srv_test_sett_txt'].'</h2>';
        
        echo '<div class="popup_close"><a href="#" onClick="$(\'#vrednost_edit\').html(\'\').hide(); $(\'#fade\').fadeOut(); return false;">✕</a></div>';
		
		echo '<form name="testiranje_preview_settings" action="#" method="get">';
		echo '<input type="hidden" name="anketa" value="'.$this->anketa.'">';
        
        // Nastavitve komentarjev - preverimo ce so na voljo v paketu
        if($userAccess->checkUserAccess($what='question_type_location')){
            echo '<div style="float: right; width: 260px">';
            
            echo '<p style="margin-top:0">'.$lang['srv_testiranje_komentarji'].': ';
            echo '<input type="radio" name="question_resp_comment" value="0" id="question_resp_comment_0" ' . ($question_resp_comment == 0 ? ' checked' : '') . ' onclick="testiranje_settings();"><label for="question_resp_comment_0">' . $lang['no'] . '</label> ';
            echo '<input type="radio" name="question_resp_comment" value="1" id="question_resp_comment_1" ' . ($question_resp_comment == 1 ? ' checked' : '') . ' onclick="testiranje_settings();"><label for="question_resp_comment_1">' . $lang['yes'] . '</label> ';
            echo '</p>';
            echo '<p class="question_resp_comment">'.$lang['srv_comments_show_open'].': <br />';
            echo '<input type="radio" name="question_resp_comment_show_open" value="" id="question_resp_comment_show_open_0" ' . ($question_resp_comment_show_open == 0 ? ' checked' : '') . '/><label for="question_resp_comment_show_open_0">' . $lang['forma_settings_open'] . '</label> ';
            echo '<input type="radio" name="question_resp_comment_show_open" value="1" id="question_resp_comment_show_open_1" ' . ($question_resp_comment_show_open == 1 ? ' checked' : '') . '/><label for="question_resp_comment_show_open_1">' . $lang['forma_settings_closed'] . '</label> ';
            echo '</p><p class="question_resp_comment">';
            echo '' . $lang['srv_q_inicialke'] . ': <br />';
            echo '<input type="radio" name="question_resp_comment_inicialke" value="0" id="question_resp_comment_inicialke_0" ' . ($question_resp_comment_inicialke == 0 ? ' checked' : '') . ' onclick="testiranje_settings();"><label for="question_resp_comment_inicialke_0">' . $lang['no'] . '</label> ';
            echo '<input type="radio" name="question_resp_comment_inicialke" value="1" id="question_resp_comment_inicialke_1" ' . ($question_resp_comment_inicialke == 1 ? ' checked' : '') . ' onclick="testiranje_settings();"><label for="question_resp_comment_inicialke_1">' . $lang['yes'] . '</label> ';
            echo '</p>';
            echo '<p class="question_resp_comment question_resp_comment_inicialke">' . $lang['srv_q_inicialke_alert'] . ': <br />';
            echo '<input type="radio" name="question_resp_comment_inicialke_alert" value="0" id="question_resp_comment_inicialke_alert_0" ' . ($question_resp_comment_inicialke_alert == 0 ? ' checked' : '') . '/><label for="question_resp_comment_inicialke_alert_0">' . $lang['srv_reminder_off2'] . '</label><br> ';
            echo '<input type="radio" name="question_resp_comment_inicialke_alert" value="1" id="question_resp_comment_inicialke_alert_1" ' . ($question_resp_comment_inicialke_alert == 1 ? ' checked' : '') . '/><label for="question_resp_comment_inicialke_alert_1">' . $lang['srv_reminder_soft2'] . '</label><br> ';
            echo '<input type="radio" name="question_resp_comment_inicialke_alert" value="2" id="question_resp_comment_inicialke_alert_2" ' . ($question_resp_comment_inicialke_alert == 2 ? ' checked' : '') . '/><label for="question_resp_comment_inicialke_alert_2">' . $lang['srv_reminder_hard2'] . '</label> ';
            echo  '</p>';
            echo '<p><a href="index.php?anketa='.$this->anketa.'&a=urejanje&advanced_expanded=1">('.$lang['srv_details_settings'].')</a></p>';
            
            echo '</div>';          
            
            ?><script>
                testiranje_settings();
            </script><?		    
        }
		
		echo '<h3>'.$lang['srv_preview_defaults'].'</h3>';
		
		echo '<p><label for="disableif"><input type="checkbox" value="1" '.($preview_disableif==1?' checked':'').' name="disableif" id="disableif">';
        echo ' '.$lang['srv_disableif'].'</label></p>';
        
		echo '<p><label for="disablealert"><input type="checkbox" value="1" '.($preview_disablealert==1?' checked':'').' name="disablealert" id="disablealert">';
        echo ' '.$lang['srv_disablealert'].'</label></p>';
        
		echo '<p><label for="displayifs"><input type="checkbox" value="1" '.($preview_displayifs==1?' checked':'').' name="displayifs" id="displayifs">';
        echo ' '.$lang['srv_displayifs'].'</label></p>';
        
		echo '<p><label for="displayvariables"><input type="checkbox" value="1" '.($preview_displayvariables==1?' checked':'').' name="displayvariables" id="displayvariables">';
        echo ' '.$lang['srv_displayvariables'].'</label></p>';	
            
        // Nastavitve komentarjev - preverimo ce so na voljo v paketu
        if($userAccess->checkUserAccess($what='question_type_location')){
		    echo '<p><label for="hidecomment"><input type="checkbox" value="1" '.($preview_hidecomment==1?' checked':'').' name="hidecomment" id="hidecomment">';
            echo ' '.$lang['srv_preview_comments2'].'</label></p>';
        }
		
		echo '<div style="clear:both;"></div>';

		echo '<div class="buttonwrapper floatRight">
                <a class="ovalbutton ovalbutton_orange btn_savesettings" onclick="testiranje_preview_settings_save(); return false;" href="#">
                <span>'.$lang['edit1337'].'</span>
                </a>
                </div>
                
                <div class="buttonwrapper spaceRight floatRight">
                <a class="ovalbutton ovalbutton_gray btn_savesettings" onclick="$(\'#vrednost_edit\').html(\'\').hide(); $(\'#fade\').fadeOut(); return false;" href="#" style="margin-left:10px">
                <span>'.$lang['srv_cancel'].'</span>
                </a>
                </div>';		
		
		echo '</form>';
	}
	
	function ajax_testiranje_preview_settings_save () {
		
		SurveySetting::getInstance()->Init($this->anketa);
		SurveySetting::getInstance()->setSurveyMiscSetting('preview_disableif', $_POST['disableif'].'');
		SurveySetting::getInstance()->setSurveyMiscSetting('preview_disablealert', $_POST['disablealert'].'');
		SurveySetting::getInstance()->setSurveyMiscSetting('preview_displayifs', $_POST['displayifs'].'');
		SurveySetting::getInstance()->setSurveyMiscSetting('preview_displayvariables', $_POST['displayvariables'].'');
		SurveySetting::getInstance()->setSurveyMiscSetting('preview_hidecomment', $_POST['hidecomment'].'');
	
		SurveySetting::getInstance()->setSurveyMiscSetting('question_resp_comment', $_POST['question_resp_comment'].'');
		SurveySetting::getInstance()->setSurveyMiscSetting('question_resp_comment_show_open', $_POST['question_resp_comment_show_open'].'');
		SurveySetting::getInstance()->setSurveyMiscSetting('question_resp_comment_inicialke', $_POST['question_resp_comment_inicialke'].'');
		SurveySetting::getInstance()->setSurveyMiscSetting('question_resp_comment_inicialke_alert', $_POST['question_resp_comment_inicialke_alert'].'');
		
	}

	// vklopi izklopi _vse_ komentarje
	function ajax_comments_onoff () {
		
		if ($_GET['do'] == 'on') {
			
			SurveySetting::getInstance()->Init($this->anketa);
			
			SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment', 3);
			SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment_viewadminonly', 3);
			
			SurveySetting::getInstance()->setSurveyMiscSetting('question_note_view', 3);
			SurveySetting::getInstance()->setSurveyMiscSetting('question_note_write', 0);
			
			SurveySetting::getInstance()->setSurveyMiscSetting('question_comment', 3);
			SurveySetting::getInstance()->setSurveyMiscSetting('question_comment_viewadminonly', 3);
			
			SurveySetting::getInstance()->setSurveyMiscSetting('question_resp_comment', 1);
			SurveySetting::getInstance()->setSurveyMiscSetting('question_resp_comment_viewadminonly', 3);
			
			SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment_resp', 4);
			SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment_viewadminonly_resp', 4);
			
			header("Location: index.php?anketa=".$this->anketa."&a=urejanje&show=on_alert");
			
		} elseif ($_GET['do'] == 'off') {
			
			SurveySetting::getInstance()->Init($this->anketa);
			
			SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment', '');
			SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment_viewadminonly', '');
			
			SurveySetting::getInstance()->setSurveyMiscSetting('question_note_view', '');
			SurveySetting::getInstance()->setSurveyMiscSetting('question_note_write', '');
			
			SurveySetting::getInstance()->setSurveyMiscSetting('question_comment', '');
			SurveySetting::getInstance()->setSurveyMiscSetting('question_comment_viewadminonly', '');
			
			SurveySetting::getInstance()->setSurveyMiscSetting('question_resp_comment', '');
			SurveySetting::getInstance()->setSurveyMiscSetting('question_resp_comment_viewadminonly', '');
			
			SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment_resp', '');
			SurveySetting::getInstance()->setSurveyMiscSetting('survey_comment_viewadminonly_resp', 4);
			
			header("Location: index.php?anketa=".$this->anketa."&a=urejanje");
		}
		
	}

	// Vklopi/izklopi napredni modul (sn, kviz, slideshow...)
	function ajax_toggle_advanced_module(){
		global $lang, $site_url, $global_user_id;
		
		if (isset ($_POST['value']))
			$value = $_POST['value'];
		if (isset ($_POST['what']))
			$what = $_POST['what'];
		

		// Updatamo bazo
		if($value == '1')
			$sql = sisplet_query("INSERT INTO srv_anketa_module (ank_id, modul) VALUES ('".$this->anketa."', '".$what."')");
		else
			$sql = sisplet_query("DELETE FROM srv_anketa_module WHERE ank_id='".$this->anketa."' AND modul='".$what."'");
		if (!$sql)
			echo mysqli_error($GLOBALS['connect_db']);
			
		
		if ($what == 'uporabnost' && $value == '1'){
			$sas = new SurveyAdminSettings();
			$sas->uporabnost();
		}
		elseif ($what == 'hierarhija' && $value == '1'){
			\Hierarhija\Hierarhija::hierarhijaInit($this->anketa);

			// Če je anketa že aktivirana jo ponovno izključimo
			if(SurveyInfo::getInstance()->getSurveyColumn('active') == 1)
				sisplet_query("UPDATE srv_anketa SET active = '0' WHERE id='".$this->anketa."'");
		}
		elseif ($what == 'quiz' && $value == '1'){
			// kviz
			$sq = new SurveyQuiz($this->anketa);
			$sq->displaySettings();
		} 
		elseif ($what == 'advanced_paradata' && $value == '1'){
			// kviz
			$sap = new SurveyAdvancedParadata($this->anketa);
			$sap->displaySettings();
		} 
		elseif ($what == 'slideshow' && $value == '1'){
			$ss = new SurveySlideshow($this->anketa);
			$ss->ShowSlideshowSetings();
		} 
		elseif ($what == 'user_from_cms' && $value == '2') {			
			// Updatamo se mass insert
			$sql = sisplet_query("UPDATE srv_anketa SET mass_insert='1', user_from_cms='2' WHERE id='".$this->anketa."'");
			if (!$sql)
				echo mysqli_error($GLOBALS['connect_db']);
		
			$sas = new SurveyAdminSettings();
			$sas->vnos();
		} 
		elseif ($what == 'user_from_cms' && $value == '0') {	
			// Updatamo se mass insert
			$sql = sisplet_query("UPDATE srv_anketa SET mass_insert='0', user_from_cms='0' WHERE id='".$this->anketa."'");
			if (!$sql)
				echo mysqli_error($GLOBALS['connect_db']);
		} 
		elseif ($what == 'phone' && $value == '1'){
			$ST = new SurveyTelephone($this->anketa);
			$ST->action($_GET['m']);
			
			$sql = sisplet_query("UPDATE srv_anketa SET user_base='1' WHERE id='".$this->anketa."'");
			if (!$sql)
				echo mysqli_error($GLOBALS['connect_db']);
		}
		elseif ($what == 'phone' && $value == '0'){
			$sql = sisplet_query("UPDATE srv_anketa SET user_base='0' WHERE id='".$this->anketa."'");
			if (!$sql)
				echo mysqli_error($GLOBALS['connect_db']);
		}
		elseif ($what == 'social_network' && $value == '1'){
			// urejanje respondentov
			$sas = new SurveyAdminSettings();
			$sas->SN_Settings();
		}
		elseif ($what == '360_stopinj' && $value == '1'){
			// analiza 360 stopinj
			$s360 = new Survey360($this->anketa);
			$s360->displaySettings();
		}
		elseif ($what == '360_stopinj_1ka' && $value == '1'){
			// analiza 360 stopinj 1ka
			$s360 = new Survey3601ka($this->anketa);
			$s360->displaySettings();
		}
		elseif ($what == 'chat' && $value == '1'){
			// chat
			$sc = new SurveyChat($this->anketa);
			$sc->displaySettings();
		}
		elseif ($what == 'panel' && $value == '1'){
			// panel
			$sp = new SurveyPanel($this->anketa);
			$sp->activatePanel();
			$sp->displaySettings();
		}
	}
	
	// Zgenerira key za api
	function ajax_generate_API_key(){
		global $lang, $site_url, $global_user_id;
		
		$identifier = '';
		$private_key = '';
		
		// Zgeneriramo identifier (identifikator)
		$identifier = bin2hex(openssl_random_pseudo_bytes(8));
		
		// Zgeneriramo privatni ključ - po novem uporabimo openssl in ne mcrypt
		//$private_key = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
		$private_key = bin2hex(openssl_random_pseudo_bytes(32, $strong=true));
		
		// Updatamo bazo
		$sql = sisplet_query("INSERT INTO srv_api_auth (usr_id, identifier, private_key) VALUES ('".$global_user_id."', '".$identifier."', '".$private_key."')
								ON DUPLICATE KEY UPDATE identifier='".$identifier."', private_key='".$private_key."'");
		if (!$sql)
			echo mysqli_error($GLOBALS['connect_db']);
		
		echo '<div class="title">'.$lang['srv_api_auth_title'].'</div>';
		
		echo '<br />';
		
		echo 'ID: ';	
		echo '<br /><span class="bold">'.$identifier.'</span>';
		
		echo '<br /><br />';
		
		echo 'PRIVATNI KLJUČ: ';
		echo '<br /><span class="bold">'.$private_key.'</span>';
		
		echo '<br /><br />';
		
		// Gumb za zapiranje
		echo '<div class="floatRight spaceRight">';
		echo '<div class="buttonwrapper" title="'.$lang['srv_zapri'].'">';
		echo '<a class="ovalbutton ovalbutton" onclick="close_API_window(); return false;" href="#">';
		echo '<span>'.$lang['srv_zapri'].'</span>';
		echo '</a>';
		echo '</div>';
		echo '</div>';
	}
	
	// Prikaze uvoz iz besedila znotraj ankete (popup)
	function ajax_show_import_from_text(){
		global $lang, $site_url, $global_user_id;
				
		// uvoz iz besedila
        echo '<div class="fieldset anketa_from_text">';	
        
        echo '<div class="popup_close"><a href="#" onClick="popupImportAnketaFromText_close();">✕</a></div>';

		// Naslov
		echo '<h2>' . $lang['srv_newSurvey_survey_from_text_title'] . ' '.Help::display('srv_create_survey_from_text').'</h2>';
		echo '<span>' . $lang['srv_newSurvey_survey_from_text_text'] . '</span>';
		
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

		echo '	<span class="floatRight spaceRight buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="importAnketaFromText();" title="'.$lang['srv_newSurvey_survey_from_text'].'">';
		echo '		<span>'.$lang['next1'].'</span>';
		echo '	</a></span>';
		
		echo '	<span class="floatRight spaceRight buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="popupImportAnketaFromText_close();" title="'.$lang['srv_cancel'].'">';
		echo '		<span>'.$lang['srv_cancel'].'</span>';
		echo '	</a></span>';
			
        echo '</div>';
	}
	
	// Uvoz iz besedila znotraj ankete (popup)
	function ajax_import_from_text(){
		global $site_url;
		
		// Ce ustvarjamo anketo preko uvoza iz besedila
		if(isset($_POST['from_text'])){

			$from_text = $_POST['from_text'];				
			$text_array = Common::anketaArrayFromText($from_text);
			
			$spr_id = 0;
			
			// Loop po vseh vprasanjih, ki jih uvazamo
			foreach($text_array as $vprasanje){
				
				$ba = new BranchingAjax($this->anketa);
				
				// Imamo samo naslov vprasanja - text tip (21)
				if(count($vprasanje) == 1){
					$b = new Branching($this->anketa);
					$spr_id = $ba->spremenljivka_new(0, 0, 1);
								
					Vprasanje::change_tip($spr_id, $tip='21');			
					$sql = sisplet_query("UPDATE srv_spremenljivka SET naslov='".$vprasanje['title']."' WHERE id='".$spr_id."'");
				}
				// Imamo variable - radio tip (1)
				else{
					$b = new Branching($this->anketa);
					$spr_id = $ba->spremenljivka_new(0, 0, 1);

					Vprasanje::change_tip($spr_id, $tip='1');
					$sql = sisplet_query("UPDATE srv_spremenljivka SET naslov='".$vprasanje['title']."' WHERE id='".$spr_id."'");				
					$sql = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spr_id'");
					
					unset($vprasanje['title']);
					
					// Loop po variablah
					foreach($vprasanje as $key => $var_title){
					
						$v = new Vprasanje();
						$v->spremenljivka = $spr_id;
						$vrednost = $v->vrednost_new($var_title);
						
						Common::prestevilci($spr_id);
					}
				}
			}
		}
		
		flush();
		
		echo $site_url.'admin/survey/index.php?anketa='.$this->anketa;
	}


    // Display consulting popup
    function ajax_consulting_popup_open () {
        global $lang;

        echo '<div class="popup_close"><a href="#" onClick="quick_title_edit_cancel(); return false;">✕</a></div>';
			
        echo '<h2>'.$lang['srv_svetovanje'].'</h2>';

        
        echo '<div class="popup_content consulting">';

        echo $lang['srv_svetovanje_text'].': '; 
        echo '<br /><br />';   
        
        echo '  <div class="row">';

        echo '  <div class="col">';
        echo '      <a href="https://www.go-tel.si/instrukcije/1KA" target="_blank"><span class="faicon cog_large"></span><span>'.$lang['srv_svetovanje_uporaba'].'</span></a>';
        echo '  </div>';

        echo '  <div class="col">';
        echo '      <a href="https://www.go-tel.si/instrukcije/statistika" target="_blank"><span class="faicon chart_large"></span><span>'.$lang['srv_svetovanje_statistika'].'</span></a>';
        echo '  </div>';

        echo '  <div class="col">';
        echo '      <a href="https://www.1ka.si/d/sl/cenik/ostale-storitve" target="_blank"><span class="faicon reload_large"></span><span>'.$lang['srv_svetovanje_metodologija'].'</span></a>';
        echo '  </div>';

        echo '</div>';

        echo '</div>';

        echo '<div class="buttons_holder">';
        echo '<span class="buttonwrapper floatRight" title="'.$lang['srv_zapri'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="smtpAAIPopupClose(); return false;"><span>'.$lang['srv_zapri'].'</span></a></span>';
        echo '</div>';
    }
}

?>