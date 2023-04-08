<?php

/**
 *
 * Class, ki naj bi imel stvari glede dostopa uporabnikov do anket
 * Ampak dejansko je se ogromno stvari, ki grejo mimo classa in direktno na bazo
 *
 */

class Dostop {

	var $anketa; // trenutna anketa

	function __construct($anketa = NULL)
	{

		// polovimo anketa ID
		if (isset ($_GET['anketa'])) {
			$this->anketa = $_GET['anketa'];
		} elseif (isset ($_POST['anketa'])) {
			$this->anketa = $_POST['anketa'];
		} elseif ($anketa != 0) {
			$this->anketa = $anketa;
		}
	}

	/**
	 * preveri dostop do ankete
	 *
	 * @param mixed $anketa
	 */
	function checkDostop($anketa = 0)
	{
		global $admin_type;
		global $global_user_id;

		if ($anketa == 0) {
			$anketa = $this->anketa;
		}
		$uid = $global_user_id;

		SurveyInfo::getInstance()->SurveyInit($anketa);
		$rowa = SurveyInfo::getInstance()->getSurveyRow();

		// meta admin vidi kao spet vse
		if (self::isMetaAdmin()) {
			return TRUE;
		}

		// za demonstracijsko je posebno preverjanje
		if ($rowa['invisible'] == 1) {
			return TRUE;
		}

		// posebej dostop za vsazga userja posebej
		$sql = sisplet_query("SELECT ank_id, uid FROM srv_dostop WHERE ank_id = '$anketa' AND uid='$uid'");
		if (mysqli_num_rows($sql) > 0) {
			return TRUE;
		}

		// dodatno imamo se ce je manager ali admin, potem vidi ankete podrejenih userjev
		if ($admin_type == 1 || $admin_type == 0) {
			$sql = sisplet_query("SELECT COUNT(*) FROM srv_dostop WHERE ank_id='$anketa' AND uid IN (SELECT user FROM srv_dostop_manage WHERE manager='$uid')");
			$row = mysqli_fetch_array($sql);
			if ($row[0] > 0) {
				return TRUE;
			}
		}

		// ce imajo administratorji poseben dostop do ankete za help
		if ($admin_type == 0 && strtotime($rowa['dostop_admin']) >= strtotime(date("Y-m-d"))) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Meta admin, ki vidi vse ankete
	 */
	static function isMetaAdmin(){		
        global $global_user_id;
		global $admin_type;

        // Ce ni admin ni nikoli metaadmin
        if($admin_type != '0'){
            return FALSE;
        }

        $meta_admin_ids = AppSettings::getInstance()->getSetting('meta_admin_ids');

        // Ce imamo nastavljene id-je za metaadmine v settings_optional
        if(isset($meta_admin_ids) && !empty($meta_admin_ids)){

            if (in_array($global_user_id, $meta_admin_ids)) {
				return TRUE;
			}
        }
		// Gorenje ima svoje metaadmine
		elseif(Common::checkModule('gorenje')){
			global $meta_admin_emails;

			$sql = sisplet_query("SELECT email FROM users WHERE id = '$global_user_id'");
			$row = mysqli_fetch_array($sql);

			if(in_array($row['email'], $meta_admin_emails)){
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * preveri, ce ima uporabnik aktiven dostop do ankete
	 * zdaj ko nimamo vec aktivnih in pasivnih uporabnikov, gledamo ali ima edit
	 * dostop do ankete
	 *
	 * ta funkcija je v bistvu deprecated, naj se raje uporablja
	 * checkDostopSub('edit'...); ostaja za zdruzljivost za nazaj
	 *
	 * @param mixed $anketa
	 */
	function checkDostopAktiven($anketa = 0)
	{
		global $admin_type;
		global $global_user_id;

		return $this->checkDostopSub('edit', $anketa);
	}

	/**
	 * preveri tocen dostop do podstoritev
	 *
	 * @param mixed $anketa
	 */
	function checkDostopSub($type, $anketa = 0)
	{
		global $admin_type;
		global $global_user_id;

		if ($anketa == 0) {
			$anketa = $this->anketa;
		}
		$uid = $global_user_id;

		SurveyInfo::getInstance()->SurveyInit($anketa);
		$rowa = SurveyInfo::getInstance()->getSurveyRow();

		// meta admin vidi kao spet vse
		if (self::isMetaAdmin()) {
			return TRUE;
		}

		// za demonstracijsko je posebno preverjanje
		if ($rowa['invisible'] == 1) {
			return TRUE;
		}

		$sql = sisplet_query("SELECT dostop FROM srv_dostop WHERE ank_id = '$anketa' AND uid='$uid'");
		if (mysqli_num_rows($sql) > 0) {
			$row = mysqli_fetch_array($sql);

			$dostop = explode(',', $row['dostop']);
			if (in_array($type, $dostop)) {
				return TRUE;
			}

			// managerji in admini majo vedno lepe linke in maile
			if ($admin_type <= 1 && in_array($type, ['link', 'mail'])) {
				return TRUE;
			}
		}

		// administratorji in managerji imajo do max kar imajo njegovi podrejeni userji
		if ($admin_type <= 1) {
			$sql = sisplet_query("SELECT dostop FROM srv_dostop WHERE ank_id='$anketa' AND uid IN (SELECT user FROM srv_dostop_manage WHERE manager='$uid')");
			while ($row = mysqli_fetch_array($sql)) {
				$dostop = explode(',', $row['dostop']);

				if (in_array($type, $dostop)) {
					return TRUE;
				}

				// managerji in admini majo vedno lepe linke in maile
				if ($admin_type <= 1 && in_array($type, ['link', 'mail'])) {
					return TRUE;
				}
			}
		}

		// ce imajo administratorji poseben dostop do ankete za help
		if ($admin_type == 0 && strtotime($rowa['dostop_admin']) >= strtotime(date("Y-m-d"))) {
			return TRUE;
		}

		return FALSE;
	}

	function ajax(){

		if ($_GET['a'] == 'manager_add_user') {
			$this->ajax_manager_add_user();
		} 
        elseif($_GET['a'] == 'add_new_user'){
            $this->ajax_add_new_user();
        } 
        elseif ($_GET['a'] == 'anketa_user_dostop') {
			$this->ajax_anketa_user_dostop();
		} 
        elseif ($_GET['a'] == 'anketa_user_dostop_save') {
			$this->ajax_anketa_user_dostop_save();
		} 
        elseif ($_GET['a'] == 'edit_user') {
			$this->ajax_edit_user();
		} 
        elseif ($_GET['a'] == 'edit_user_save') {
			$this->ajax_edit_user_save();
		} 
        elseif ($_GET['a'] == 'admin_add_user') {
             $this->ajax_admin_add_user();
        } 
        elseif ($_GET['a'] == 'admin_add_user_popup') {
            $this->ajax_admin_add_user_popup();
        } 
        elseif($_GET['a'] == 'find_user'){
            $this->ajax_find_user();
		} 
        elseif ($_GET['a'] == 'edit_remove_user') {
			$this->ajax_edit_remove_user();
		} 
        elseif ($_GET['a'] == 'edit_remove_user_manager') {
			$this->ajax_edit_remove_user_manager();
		} 
        elseif ($_GET['a'] == 'edit_remove_user_admin') {
			$this->ajax_edit_remove_user_admin();
		} 
        elseif ($_GET['a'] == 'all_users_list') {
			
            if ($_GET['m'] == 'delete') {
				$this->ajax_all_users_list_delete();
            }
            
            if ($_GET['m'] == 'ban') {
				$this->ajax_all_users_list_ban();
            }
            else {
				$this->ajax_all_users_list();
			}
		} 
        elseif($_GET['a'] == 'my_users_list'){
			$this->ajax_all_users_list_my();
		} 
        elseif ($_GET['a'] == 'delete_users_list') {
			$this->ajax_delete_users_list();
		} 
        elseif ($_GET['a'] == 'unsigned_users_list') {
			$this->ajax_unsigned_users_list();
		} 
        elseif ($_GET['a'] == 'unconfirmed_mail_user_list') {
			
            if ($_GET['m'] == 'delete') {
				$this->ajax_unconfirmed_mail_user_list_delet_user();
            } 
            elseif ($_GET['m'] == 'accept') {
				$this->ajax_confirm_user_email();
            } 
            else {
				$this->ajax_unconfirmed_mail_user_list();
			}
		}
        elseif ($_GET['a'] == 'dodeljeni_uporabniki_display') {
			$this->ajax_dodeljeni_uporabniki_display();
		}
	}

    /**
     * Dodamo novega uporabnika v 1KA sistem
     */
	public function ajax_add_new_user()
    {
        global $pass_salt, $site_url, $site_domain, $lang;

        $email = $_POST['email'];
        $name = $_POST['name'];
        $surnname = $_POST['surname'];
        $password = $_POST['password'];
        $password2 = $_POST['password2'];
        $jezik = $_POST['jezik'];

        include root_dir('lang/'.$jezik.'.php');


        if ($email != '') {

            $sqlu = sisplet_query("SELECT id FROM users WHERE email='$email'");
            if (mysqli_num_rows($sqlu) == 0) {

                if ($password == '' || $password == $password2) {

                    $s = sisplet_query("INSERT INTO users (name, surname, email, pass, type, when_reg, came_from, lang) VALUES ('$name', '$surnname', '$email', '" . base64_encode((hash(SHA256, $password . $pass_salt))) . "', '3', DATE_FORMAT(NOW(), '%Y-%m-%d'), '1', $jezik)");
                    $id = mysqli_insert_id($GLOBALS['connect_db']);

                } else {
                    $error = 'pass';
                }

            } else {
                // ne more si dodati že obstoječega uporabnika, ker potem bi si lahko kar kogarkoli dodal in bi videl njegove ankete
                $id = 0;
                $error = 'email';
            }

            if ($id > 0) {

                $UserContent = $lang['add_new_user_content'];

                // Podpis
                $signature = Common::getEmailSignature();
                $UserContent .= $signature;

                $UserContent .= $lang['register_add_user_content_edit'];

                $PageName = AppSettings::getInstance()->getSetting('app_settings-app_name');

                $change = '<a href="'.$site_url.'admin/survey/index.php?a=nastavitve&m=global_user_myProfile">';
                $out = '<a href="'.$this->page_urls['page_unregister'].'?email='.$email.'">';

                // Ce gre slucajno za virtualko
                $Subject = (isVirtual()) ? $lang['register_user_subject_virtual'] : $lang['register_user_subject'];

                $UserContent = str_replace("SFNAME", $name, $UserContent);
                $UserContent = str_replace("SFMAIL", $email, $UserContent);
                $UserContent = str_replace("SFWITH", $email, $UserContent);
                $UserContent = str_replace("SFPAGENAME", $PageName, $UserContent);
                $UserContent = str_replace("SFCHANGE", $change, $UserContent);
                $UserContent = str_replace("SFOUT", $out, $UserContent);
                $UserContent = str_replace("SFEND", '</a>', $UserContent);

                $Subject = str_replace("SFPAGENAME", $PageName, $Subject);
                
                // Ce gre slucajno za virtualko
                if(isVirtual())
                    $Subject = str_replace("SFVIRTUALNAME", $site_domain, $Subject);

                if ($password2 == "") {
                    $UserContent = str_replace("SFPASS", "( ".$lang['without']." ) ", $UserContent);
                } 
                else {
                    $UserContent = str_replace("SFPASS", $password2 ." (".$lang['register_add_user_password'].")", $UserContent);
                }

                if ($name == "") {
                    $UserContent = str_replace("SFNAME", $lang['mr_or_mrs'], $UserContent);
                } 
                else {
                    $UserContent = str_replace("SFNAME", $name, $UserContent);
                }

                $ZaMail = '<!DOCTYPE HTML PUBLIC"-//W3C//DTD HTML 4.0 Transitional//EN">'.'<html><head><title>'.$Subject.'</title><meta content="text/html; charset=utf-8" http-equiv=Content-type></head><body>';

                $ZaMail .= $UserContent;


                if(isDebug()){
                    echo $ZaMail;
                    die();
                }

                // Posljemo mail vsakemu uporabniku posebej
                try {
                    $MA = new MailAdapter(null, 'account');
                    $MA->addRecipients($email);
                    $resultX = $MA->sendMail(stripslashes($ZaMail), $Subject);
                } 
                catch (Exception $e) {
                }

                if ($resultX) {
                    $status = 1; // poslalo ok
                } 
                else {
                    $status = 2; // ni poslalo
                }
            }

        } else {
            $error = 'email';
        }

        header("Location: index.php?a=diagnostics&t=uporabniki&m=all&add=new&error=" . ($error !== FALSE ? $error : ''));
    }

	/**
	 * Manager: dodajanje svojih novih uporabnikov
	 *
	 */
	function ajax_manager_add_user()
	{
		global $pass_salt;
		global $lang;
		global $global_user_id, $site_path, $site_domain;
		global $admin_type;

		$error = FALSE;

		$sqlu = sisplet_query("SELECT email, type FROM users WHERE id = '" . $global_user_id . "'");
		list($MailReply) = mysqli_fetch_row($sqlu);

		$aktiven = $_POST['aktiven'];

		$email = $_POST['email'];
		$name = $_POST['name'];
		$surnname = $_POST['surname'];
		$password = $_POST['password'];
		$password2 = $_POST['password2'];

		if ($email != '') {

			$sqlu = sisplet_query("SELECT id FROM users WHERE email='$email'");
			if (mysqli_num_rows($sqlu) == 0) {

				if ($password == '' || $password == $password2) {

					$s = sisplet_query("INSERT INTO users (name, surname, email, pass, type, when_reg, came_from) VALUES ('$name', '$surnname', '$email', '" . base64_encode((hash(SHA256, $password . $pass_salt))) . "', '3', DATE_FORMAT(NOW(), '%Y-%m-%d'), '1')");
					$id = mysqli_insert_id($GLOBALS['connect_db']);

				} else {
					$error = 'pass';
				}

			} else {
				// ne more si dodati že obstoječega uporabnika, ker potem bi si lahko kar kogarkoli dodal in bi videl njegove ankete
				$id = 0;
				$error = 'email';
			}

			if ($id > 0) {

				$s = sisplet_query("INSERT INTO srv_dostop_manage (manager, user) VALUES ('$global_user_id', '$id')");
				if (!$s) {
					echo mysqli_error($GLOBALS['connect_db']);
				}

                $subject = sprintf($lang['srv_dodanmail_1'], $site_domain);

                $content = sprintf($lang['srv_dodanmail_2'], $MailReply, $site_domain).'<br /><br />';
                $content .= $lang['srv_dodanmail_3'];
                $content .= '<ul>';
                $content .= '<li>'.$lang['srv_dodanmail_3_email'].' <b>'.$email.'</b></li>';
                $content .= '<li>'.$lang['srv_dodanmail_3_pass_1'].' <b>'.$password.'</b> ('.$lang['srv_dodanmail_3_pass_2'].')</li>';
                $content .= '</ul>';

                // Podpis
                $signature = Common::getEmailSignature();
                $content .= $signature;

                
				// Posljemo mail vsakemu uporabniku posebej
				try {
					$MA = new MailAdapter($this->anketa, $type='account');
					$MA->addRecipients($email);
					$MA->addRecipients($MailReply);
					$resultX = $MA->sendMail(stripslashes($content), $subject);
				} catch (Exception $e) {
				}


				if ($resultX) {
					$status = 1; // poslalo ok
				} else {
					$status = 2; // ni poslalo
				}
			}

		} else {
			$error = 'email';
		}

		header("Location: index.php?a=diagnostics&t=uporabniki&m=my&error=" . ($error !== FALSE ? $error : ''));
	}

	/**
	 * Urejanje natančnega dostopa uporabnikov v nastavitvah ankete
	 *
	 */
	function ajax_anketa_user_dostop()
	{
		global $admin_type;
		global $lang;

		$uid = $_POST['uid'];

		$s = sisplet_query("SELECT name, surname, email, type FROM users WHERE id='$uid'");
		$r = mysqli_fetch_array($s);

		$sqla = sisplet_query("SELECT naslov FROM srv_anketa WHERE id = '$this->anketa'");
		$rowa = mysqli_fetch_array($sqla);

        echo '<h2>'.$lang['srv_anketa'].' '.$rowa['naslov'].'</h2>';

        echo '<div class="popup_close"><a href="#" onClick="anketa_user_dostop_close(); return false;">✕</a></div>';

		echo '<h3><span class="bold">';

        if ($r['type'] == 2 || $r['type'] == 3) {
			echo $lang['admin_narocnik'];
        } 
        elseif ($r['type'] == 1) {
			echo $lang['manager'];
        } 
        elseif ($r['type'] == 0) {
			echo $lang['administrator'];
		}

		$r['email'] = iconv("iso-8859-2", "utf-8", $r['email']);

		echo ': ' . $r['name'] . ' ' . $r['surname'] . ' (' . $r['email'] . ')</span></h3>';

		if ($r['type'] >= 2) {

			$sql = sisplet_query("SELECT dostop FROM srv_dostop WHERE ank_id='$this->anketa' AND uid='$uid'");
			$row = mysqli_fetch_array($sql);
			$dostop = explode(',', $row['dostop']);
			echo '<form id="dostop">';
			echo '<input type="hidden" name="uid" value="' . $uid . '">';

			echo '<input type="hidden" name="aktiven" value="1" id="aktiven_1">';

			echo '<p><input type="checkbox" name="dostop[dashboard]" value="dashboard" id="dashboard" ' . (in_array('dashboard', $dostop) ? 'checked' : '') . ' ' . (in_array('phone', $dostop) ? ' disabled="disabled"' : '') . '> <label for="dashboard">' . $lang['srv_dostop_dashboard'] . '</label></p>';
			echo '<p><input type="checkbox" name="dostop[edit]" value="edit" id="edit" ' . (in_array('edit', $dostop) ? 'checked' : '') . ' ' . (in_array('phone', $dostop) ? ' disabled="disabled"' : '') . ' onchange="dostop_language(this);"> <label for="edit">' . $lang['srv_dostop_edit'] . '</label></p>';
			echo '<p><input type="checkbox" name="dostop[test]" value="test" id="test" ' . (in_array('test', $dostop) ? 'checked' : '') . ' ' . (in_array('phone', $dostop) ? ' disabled="disabled"' : '') . ' onchange="dostop_language(this);"> <label for="test">' . $lang['srv_dostop_test'] . '</label></p>';
			echo '<p><input type="checkbox" name="dostop[publish]" value="publish" id="publish" ' . (in_array('publish', $dostop) ? 'checked' : '') . ' ' . (in_array('phone', $dostop) ? ' disabled="disabled"' : '') . ' onchange="dostop_language(this);"> <label for="publish">' . $lang['srv_dostop_publish'] . '</label></p>';
			echo '<p><input type="checkbox" name="dostop[data]" value="data" id="data" ' . (in_array('data', $dostop) ? 'checked' : '') . ' ' . (in_array('phone', $dostop) ? ' disabled="disabled"' : '') . '> <label for="data">' . $lang['srv_dostop_data'] . '</label></p>';
			echo '<p><input type="checkbox" name="dostop[analyse]" value="analyse" id="analyse" ' . (in_array('analyse', $dostop) ? 'checked' : '') . ' ' . (in_array('phone', $dostop) ? ' disabled="disabled"' : '') . '> <label for="analyse">' . $lang['srv_dostop_analyse'] . '</label></p>';

			echo '<p><input type="checkbox" name="dostop[export]" value="export" id="export" ' . (in_array('export', $dostop) ? 'checked' : '') . ' ' . (in_array('phone', $dostop) ? ' disabled="disabled"' : '') . '> <label for="export">' . $lang['srv_dostop_export'] . '</label></p>';

			// Nastavitev, da ne more odklenit ankete
			echo '<p><input type="checkbox" name="dostop[lock]" value="lock" id="lock" ' . (in_array('lock', $dostop) ? 'checked' : '') . ' ' . (in_array('phone', $dostop) ? ' disabled="disabled"' : '') . '> <label for="lock">' . $lang['srv_dostop_lock'] . '</label></p>';

			// Je anketar - ne more poceti nicesar razen izvajati telefonsko anketo (ob kliku se ostale avtomatsko ugasnejo in disablajo)
			echo '<p><input type="checkbox" name="dostop[phone]" value="phone" id="phone" ' . (in_array('phone', $dostop) ? 'checked' : '') . ' onchange="dostop_anketar(this);"> <label for="phone">' . $lang['srv_dostop_phone'] . '</label></p>';

			// Če gre za Hierarhijo
			if (SurveyInfo::checkSurveyModule('hierarhija', $this->anketa)) {
				$tip = sisplet_query("SELECT type FROM srv_hierarhija_users WHERE user_id='" . $uid . "' AND anketa_id='" . $this->anketa . "'", "obj");

				if (!empty($tip) && !empty($tip->type)) {
					echo '<p><label>Uporabnik hierarhije s pravicami: </label>';
					echo '<select name="hierarchy_type" id="hierarchy-type-change" onchange="hierarhijaPravice()">
                            <option value="10" ' . ($tip->type == 10 ? 'selected' : NULL) . '> Učitelj </option>
                            <option value="2" ' . ($tip->type == 2 ? 'selected' : NULL) . '> Administrator </option>
                        </select ></p>';
				}
			}


			echo '<div style="position: absolute; right: 10px; top: 50px; width: 200px;">';

			// Ce je katerikoli od treh checkboxou ugasnjen imamo enablano editiranje samo posameznega jezik
			$enable_lang = (!in_array('edit', $dostop) || !in_array('test', $dostop) || !in_array('publish', $dostop)) ? TRUE : FALSE;

			$sqll = sisplet_query("SELECT * FROM srv_language WHERE ank_id = '$this->anketa'");
			if (mysqli_num_rows($sqll) > 0) {
				echo '<p><b>' . $lang['srv_passive_multilang'] . '</b></p>';

				echo '<input type="hidden" name="dostop_language_edit" id="dostop_language_edit" value="' . ($enable_lang ? '1' : '0') . '">';
			}
			while ($rowl = mysqli_fetch_array($sqll)) {

				$sqldl = sisplet_query("SELECT * FROM srv_dostop_language WHERE ank_id = '$this->anketa' AND uid = '$uid' AND lang_id='$rowl[lang_id]'");
				if (!$sqldl) {
					echo mysqli_error($GLOBALS['connect_db']);
				}
				if (mysqli_num_rows($sqldl) > 0) {
					$checked = ' checked';
				} else {
					$checked = '';
				}

				echo '<label><input class="dostop_language" type="checkbox" name="dostop_language[]" value="' . $uid . '-' . $rowl['lang_id'] . '" ' . $checked . ' ' . ($enable_lang ? '' : ' disabled="disabled"') . ' > ' . $rowl['language'] . '</label> <br>';
			}

			echo '</div>';

			echo '</form>';

			echo '<div class="buttonwrapper floatLeft spaceRight"><a class="ovalbutton ovalbutton_orange" onclick="anketa_user_dostop_save(\'' . $this->anketa . '\'); return false;" href="#"><span>' . $lang['edit1337'] . '</span></a></div>';
        } 
        // Manager - brez moznosti uporabe 1ka streznika
		elseif ($r['type'] == 1) {

			$sql = sisplet_query("SELECT dostop FROM srv_dostop WHERE ank_id='$this->anketa' AND uid='$uid'");
			$row = mysqli_fetch_array($sql);
			$dostop = explode(',', $row['dostop']);

			// Admin lahko managerju spreminja samo posiljanje vabil preko 1ka streznika
			if ($admin_type == 0) {

				echo '<form id="dostop">';
				echo '<input type="hidden" name="uid" value="' . $uid . '">';
				echo '<input type="hidden" name="aktiven" value="1" id="aktiven_1">';

				if (in_array('dashboard', $dostop)) {
					echo '<input type="hidden" name="dostop[dashboard]" value="dashboard" id="dashboard">';
				}
				if (in_array('edit', $dostop)) {
					echo '<input type="hidden" name="dostop[edit]" value="edit" id="edit">';
				}
				if (in_array('test', $dostop)) {
					echo '<input type="hidden" name="dostop[test]" value="test" id="test">';
				}
				if (in_array('publish', $dostop)) {
					echo '<input type="hidden" name="dostop[publish]" value="publish" id="publish">';
				}
				if (in_array('data', $dostop)) {
					echo '<input type="hidden" name="dostop[data]" value="data" id="data">';
				}
				if (in_array('analyse', $dostop)) {
					echo '<input type="hidden" name="dostop[analyse]" value="analyse" id="analyse">';
				}
				if (in_array('export', $dostop)) {
					echo '<input type="hidden" name="dostop[export]" value="export" id="export">';
				}
				if (in_array('lock', $dostop)) {
					echo '<input type="hidden" name="dostop[lock]" value="lock" id="lock">';
				}
				if (in_array('phone', $dostop)) {
					echo '<input type="hidden" name="dostop[phone]" value="phone" id="phone">';
				}

				echo '</form>';

				echo '<p>(' . $lang['srv_dostop_edit'] . ', ' . $lang['srv_dostop_data'] . ', ' . $lang['srv_dostop_export'] . ')</p>';

				echo '<div class="buttonwrapper floatLeft spaceRight"><a class="ovalbutton ovalbutton_orange" onclick="anketa_user_dostop_save(\'' . $this->anketa . '\'); return false;" href="#"><span>' . $lang['edit1337'] . '</span></a></div>';
            } 
            // Ostali ne morejo managerju nicesar spreminjati
			else {
				echo '<p>(' . $lang['srv_dostop_edit'] . ', ' . $lang['srv_dostop_data'] . ', ' . $lang['srv_dostop_export'] . ' )</p>';
			}
        } 
        // Admin
		else {
			echo '<p>(' . $lang['srv_dostop_edit'] . ', ' . $lang['srv_dostop_data'] . ', ' . $lang['srv_dostop_export'] . ')</p>';
		}

		echo '<div class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_gray" onclick="anketa_user_dostop_close(); return false;" href="#"><span>' . $lang['srv_zapri'] . '</span></a></div>';
	}

	function ajax_anketa_user_dostop_save()	{

		$uid = $_POST['uid'];
		$aktiven = $_POST['aktiven'];

		$dostop = implode(',', $_POST['dostop']);

		$sql = sisplet_query("UPDATE srv_dostop SET aktiven='$aktiven', dostop='$dostop' WHERE uid = '$uid' AND ank_id='$this->anketa'");

		if (isset($_POST['dostop_language_edit']) && $_POST['dostop_language_edit'] == '1') {
			sisplet_query("DELETE FROM srv_dostop_language WHERE ank_id = '$this->anketa' AND uid='$uid'");
			foreach ($_POST['dostop_language'] AS $val) {
				$val = explode('-', $val);
				$uid = $val[0];
				$lang_id = $val[1];
				sisplet_query("INSERT INTO srv_dostop_language (ank_id, uid, lang_id) VALUES ('$this->anketa', '$uid', '$lang_id')");
			}
		}

		if (isset($_POST['hierarchy_type']) && SurveyInfo::checkSurveyModule('hierarhija', $this->anketa)) {
			$tip = (!empty($_POST['hierarchy_type']) ? $_POST['hierarchy_type'] : NULL);

			$result = sisplet_query("SELECT id FROM srv_hierarhija_users WHERE user_id='" . $uid . "' AND anketa_id='" . $this->anketa . "'", "obj");

			if (!empty($result) && !empty($result->id) && !is_null($tip)) {
				sisplet_query("UPDATE srv_hierarhija_users SET type='" . $tip . "' WHERE id='" . $result->id . "'");
			}

		}
	}

	function ajax_edit_user(){
		global $lang;
		global $global_user_id;
		global $admin_type;

		$uid = $_POST['uid'];

        echo '<div class="edit_user_content">';

        // NASTAVITVE UPORABNIKA
        echo '<div class="user_settings">';

        $sql = sisplet_query("SELECT name, surname, email, type, status, gdpr_agree FROM users WHERE id ='" . $uid . "'");
        $row = mysqli_fetch_array($sql);
        
		echo '<form class="manager_add_user" name="manager_edit_user" action="ajax.php?t=dostop&a=edit_user_save&uid=' . $uid . '" method="post">';

		echo '<h2><strong>' . $lang['edit_user'] . '</strong></h2>';

        // Segment tip uporabnika
        echo '<div class="segment user_type">';

        $row['email'] = iconv("iso-8859-2", "utf-8", $row['email']);

		// Emaila ne more vec editirat, ker je prevec problemov (izgubi ankete...)
		echo '<input type="hidden" id="email" name="email" value="' . $row['email'] . '" />';
        echo '<p><label for="email">' . $lang['user2'] . ':</label>'.$row['name'].' '.$row['surname'].' ('.$row['email'].')</p>';

		// Admin lahko spreminja tip vseh userjev
		if ($admin_type == 0) {
			echo '<p><label for="type">' . $lang['admin_type'] . '</label><select id="type" name="type">';
			echo '<option value="0" ' . ($row['type'] == '0' ? 'selected' : '') . '>' . $lang['admin_admin'] . '</option>';
			echo '<option value="1" ' . ($row['type'] == '1' ? 'selected' : '') . '>' . $lang['admin_manager'] . '</option>';
			echo '<option value="3" ' . ($row['type'] == '3' ? 'selected' : '') . '>' . $lang['admin_narocnik'] . '</option>';
			echo '</select></p>';
        } 
        else {
			echo '<input type="hidden" id="type" name="type" value="' . $row['type'] . '" />';
		}

		echo '<p><label for="status">' . $lang['status'] . '</label><select name="status" id="status"><option value="1" ' . ($row['status'] == 1 ? 'selected' : '') . '>' . $lang['srv_user_notbanned'] . '</option><option value="0" ' . ($row['status'] == 0 ? 'selected' : '') . '>' . $lang['srv_user_banned'] . '</option></select></p>';
        
        echo '</div>';

        // Segment osnovni podatki
        echo '<div class="segment user_info">';

		echo '<p><label for="name">' . $lang['name'] . ':</label><input type="text" id="name" name="name" value="' . (!empty($row['name']) ? $row['name'] : '') . '" autocomplete="off" size="50"></p>';
		echo '<p><label for="surname">' . $lang['surname'] . ':</label><input type="text" id="surname" name="surname" value="' . (!empty($row['surname']) ? $row['surname'] : '') . '" size="50" readonly onfocus="this.removeAttribute(\'readonly\');"></p>';
		echo '<p><label for="password">' . $lang['password'] . ':</label><input type="password" id="password" name="password" readonly onfocus="this.removeAttribute(\'readonly\');"></p>';
		echo '<p><label for="password2">' . $lang['cms_register_user_repeat_password'] . ':</label><input type="password" id="password2" name="password2" readonly onfocus="this.removeAttribute(\'readonly\');"></p>';
        echo '<p><label for="subscription">'.$lang['srv_subscribe'].':</label> 
               <input type="radio" id="subscriptionDa" name="gdpr_agree" value="1" '.($row['gdpr_agree'] == 1 ? 'checked="checked"' : '').'><label for="subscriptionDa" style="width: auto;">'.$lang['yes'].'</label>'.
                ' <input type="radio" id="subscriptionNe" class="spaceLeft" name="gdpr_agree" value="0" '.($row['gdpr_agree'] == 0 ? 'checked="checked"' : '').'><label for="subscriptionNe" style="width: auto;">'.$lang['no'].'</label>'.
            '</p>';

        $user_2fa_validate = User::option($uid, 'google-2fa-validation');
        if($admin_type == 0 && !empty($user_2fa_validate) && $user_2fa_validate != 'NOT') {
            echo '<p><label for="google_2fa">'.$lang['google_2fa'].':</label>
                    <label for="google-2fa-da" style="width: auto;">
                      <input type="radio" id="google-2fa-da" name="google-2fa" value="1" disabled="disabled" '.((!empty($user_2fa_validate) && $user_2fa_validate != 'NOT') ? 'checked="checked"' : '').'>'.$lang['srv_mass_input_1'].'
                     </label>
                     <label for="google-2fa-ne" style="width: auto;">
                      <input type="radio" id="google-2fa-ne" name="google-2fa" value="izbrisi">'.$lang['srv_mass_input_0'].
                    '</label></p>';
        }


        // Admin lahko ureja katere uporabnike (mail domene) bo manager lahko dodajal pod svoj pregled
		if ($admin_type == 0 && $row['type'] == '1') {
			UserSetting:: getInstance()->Init($uid);
			$emails = UserSetting:: getInstance()->getUserSetting('manage_domain');

			echo '<p><label for="manage_domain" style="width:200px;">Manager domene (npr. 1ka.si):</label><input type="text" id="manage_domain" name="manage_domain" value="' . $emails . '" autocomplete="off" size="30"></p>';
		}

        // Na virtualkah manager ne sme odstraniti uporabnika iz pregleda (zaradi omejitve)
        if($admin_type != '1' || !isVirtual()){
            $sqlu = sisplet_query("SELECT * FROM srv_dostop_manage WHERE manager='$global_user_id' AND user='$uid'");

            if (mysqli_num_rows($sqlu) > 0) {
                echo '<br /><p><a href="ajax.php?t=dostop&a=edit_remove_user&uid=' . $uid . '" onclick="if ( confirm(\'?\')) {  } else {return false;}">' . $lang['srv_manager_rem_user2'] . '</a></p>';
            }
        }

        echo '</div>';
        
        // Segment paket
        if(AppSettings::getInstance()->getSetting('app_settings-commercial_packages') === true){
            echo '<div class="segment user_package">';

            $userAccess = UserAccess::getInstance($uid);
            $userAccess_data = $userAccess->getAccess();

            $active_package = (isset($userAccess_data['package_id'])) ? $userAccess_data['package_id'] : '1';
            $time_expire = (isset($userAccess_data['time_expire'])) ? date('d.m.Y', strtotime($userAccess_data['time_expire'])) : '';

            // Paket
            echo '<p>';
            echo '  <label for="package">' . $lang['srv_access_package'] . ':</label>';
            echo '  <select name="package" id="package">';
            foreach($userAccess->getPackages() as $package_id => $package){
                echo '      <option value="'.$package_id.'" '.($package_id == $active_package ? 'selected="selected"' : '').'>'.$package['name'].'</option>';
            }
            echo '  </select>';
            echo '</p>';

            // Trajanje paketa
            echo '<p>';
            echo '  <label for="package_expire">' . ucfirst($lang['srv_access_package_valid']) . ':</label>';
            echo '  <input type="text" name="package_expire" id="package_expire" value="'.$time_expire.'" style="width:80px;">';
            echo '</p>';

            echo '
                <script type="text/javascript">
                    $(document).ready(function () {
                        datepicker("#package_expire");
                    });
                </script>';	

            echo '</div>';
        }

        echo '</form>';
        echo '</div>';
        

        // SEZNAM ANKET
		echo '<div class="survey_list">';
		echo '  <h3><strong>' . $lang['srv_ankete'] . '</strong></h3>';

		echo '  <ul>';
		$sql = sisplet_query("SELECT srv_anketa.id, srv_anketa.naslov FROM srv_dostop, srv_anketa WHERE srv_dostop.uid='". $uid ."' AND srv_dostop.ank_id=srv_anketa.id ORDER BY srv_anketa.edit_time DESC");
		while ($row = mysqli_fetch_array($sql)) {
			echo '      <li><a href="#" onclick="anketa_user_dostop(\'' . $uid . '\', \'' . $row['id'] . '\'); return false;">' . $row['naslov'] . '</a></li>';
		}

		echo '  </ul>';
        echo '</div>';
        
        echo '</div>';

        
        // GUMBI NA DNU
		echo '<p>';
        echo '  <div class="buttonwrapper floatLeft"><a class="ovalbutton ovalbutton_gray" href="#" onclick="edit_user_close();"><span>'.$lang['srv_zapri'].'</span></a></div>';
        echo '  <div class="buttonwrapper floatLeft spaceLeft"><a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="document.manager_edit_user.submit();"><span>'.$lang['edit1337'].'</span></a></div>';
        echo '</p>';
	}

	function ajax_edit_user_save(){
		global $pass_salt;
		global $admin_type;

		$uid = $_GET['uid'];

		$_POST['email'] = iconv("utf-8", "iso-8859-2", $_POST['email']);

		if ($_POST['email'] != '') {

			if ($_POST['password'] != '' && $_POST['password'] == $_POST['password2']) {
				$password = ", pass = '" . base64_encode((hash('SHA256', $_POST['password'] . $pass_salt))) . "' ";
			} else {
				$password = "";
			}

			$s = sisplet_query("UPDATE users SET type='$_POST[type]', status='$_POST[status]', email='$_POST[email]', name='$_POST[name]', surname='$_POST[surname]' $password WHERE id = '$uid'");
			if (!$s) {
				echo mysqli_error($GLOBALS['connect_db']);
			}
		}

		if(isset($_POST['gdpr_agree'])){
		        sisplet_query("UPDATE users SET gdpr_agree='".$_POST['gdpr_agree']."' WHERE id = '$uid'");
        }

        if(isset($_POST['google-2fa']) && $_POST['google-2fa'] == 'izbrisi'){
            sisplet_query("DELETE FROM user_options WHERE user_id='".$uid."' AND option_name IN ('google-2fa-secret', 'google-2fa-validation')");
        }

		if (isset($_POST['manage_domain'])) {

			UserSetting::getInstance()->Init($uid);
			UserSetting::getInstance()
			           ->setUserSetting('manage_domain', $_POST['manage_domain']);
			UserSetting::getInstance()->saveUserSetting();
        }

        // Update or insert user package
        if (isset($_POST['package']) && isset($_POST['package_expire']) && $_POST['package_expire'] != '') {

			$package_id = $_POST['package'];
            $package_expire = $_POST['package_expire'];
            $package_expire_sql = date('Y-m-d H:i:s', strtotime($package_expire));
            
            $sqlPackageTime = sisplet_query("SELECT time_activate FROM user_access WHERE usr_id='".$uid."'");
            if(mysqli_num_rows($sqlPackageTime) > 0){
                $rowPackageTime = mysqli_fetch_array($sqlPackageTime);
                $time_activate = date('Y-m-d H:i:s', strtotime($rowPackageTime['time_activate']));

                $sqlPackageDelete = sisplet_query("DELETE FROM user_access WHERE usr_id='".$uid."'");

                $sqlPackage = sisplet_query("INSERT INTO user_access
                                                (usr_id, time_activate, time_expire, package_id)
                                                VALUES
                                                ('".$uid."', '".$time_activate."', '".$package_expire_sql."', '".$package_id."')
                                            ");
            }
            else{
                $sqlPackage = sisplet_query("INSERT INTO user_access
                                                (usr_id, time_activate, time_expire, package_id)
                                                VALUES
                                                ('".$uid."', NOW(), '".$package_expire_sql."', '".$package_id."')
                                            ");
            }

            
            if (!$sqlPackage)
                echo mysqli_error($GLOBALS['connect_db']);
		}

        if($admin_type == 0) {
            
            // Ce smo odprli okno v narocilih
            if(strpos($_SERVER['HTTP_REFERER'], 'a=narocila') !== false)
                header("Location: index.php?a=narocila");
            else
                header("Location: index.php?a=diagnostics&t=uporabniki&m=all");
        }
        else{
            header("Location: index.php?a=diagnostics&t=uporabniki&m=my");
        }
	}

	/**
	 * Admin: dodajanje obstojecih uporabnikov
	 *
	 */
	function ajax_admin_add_user()
	{
		global $pass_salt;
		global $lang;
		global $global_user_id, $site_path;
		global $admin_type;

		if ($admin_type != 0 && $admin_type != 1) {
			return;
		}

		$error = FALSE;

		$sqlu = sisplet_query("SELECT email FROM users WHERE id = '" . $global_user_id . "'");
		$rowu = mysqli_fetch_array($sqlu);

		$mail_admin = $rowu['email'];

		$uid = (!empty($_POST['uid']) ? $_POST['uid'] : null);
		$uemail = (!empty($_POST['uemail']) ? $_POST['uemail'] : null);

		$sqlu = sisplet_query("SELECT email, type FROM users WHERE id='$uid'");
		if (mysqli_num_rows($sqlu) > 0) {
			$rowu = mysqli_fetch_array($sqlu);
			$mail_user = $rowu['email'];
			$type_user = $rowu['type'];
			$id = $uid;
		}

		// Za managerje pošljemo samo email
		if(empty($id)) {
	        $sqlu = sisplet_query("SELECT id, email, type FROM users WHERE email='".$uemail."'");
	        if (mysqli_num_rows($sqlu) > 0) {
	            $rowu = mysqli_fetch_array($sqlu);
	            $mail_user = $rowu['email'];
	            $type_user = $rowu['type'];
	            $id = $rowu['id'];
	        }
	    }

		if ($id > 0 && $type_user >= $admin_type) {

			$s = sisplet_query("INSERT INTO srv_dostop_manage (manager, user) VALUES ('$global_user_id', '$id')");
			if (!$s) {
				echo mysqli_error($GLOBALS['connect_db']);
			}

			global $site_url;

			$subject = $lang['srv_dodanmail_m_1'] . '';
			$content = sprintf($lang['srv_dodanmail_m_2'], $mail_admin, $site_url, $mail_user) . '<br /><br />' . sprintf($lang['srv_dodanmail_m_3']);

            // Podpis
            $signature = Common::getEmailSignature();
            $content .= $signature;

			try {
				$MA = new MailAdapter($this->anketa, $type='account');
				$MA->addRecipients($mail_user);
				$resultX = $MA->sendMail(stripslashes($content), $subject);
			} catch (Exception $e) {
			}

			if ($resultX) {
				$status = 1; // poslalo ok
			} else {
				$status = 2; // ni poslalo
			}
		}

		header("Location: index.php?a=diagnostics&t=uporabniki".($error !== FALSE ? '&error='.$error : ''));
	}

    /**
	 * Admin: dodajanje obstojecih uporabnikov drugemu uporabniku v popupu
	 *
	 */
	function ajax_admin_add_user_popup(){
		global $lang;
		global $admin_type;

		if ($admin_type != 0)
			return;

        $manager = (isset($_POST['manager'])) ? $_POST['manager'] : '0';
        $user = (isset($_POST['user'])) ? $_POST['user'] : '0';

        if($manager == '' || $manager == '0' || $user == '' || $user == '0')
            return;
        
        $sql = sisplet_query("INSERT INTO srv_dostop_manage (manager, user) VALUES ('".$manager."', '".$user."')");
        if (!$sql)
            echo mysqli_error($GLOBALS['connect_db']);
        
        $this->ajax_dodeljeni_uporabniki_display();
	}

    /**
     * Poiščemo uporabnika, ki je v bazi
     */
	function  ajax_find_user(){
	    global $admin_type, $global_user_id;

        $json['results'] = [];

	    if($admin_type == 0){

            $sqls = sisplet_query("SELECT id, name, surname, email FROM users WHERE id NOT IN (SELECT user FROM srv_dostop_manage WHERE manager='".$global_user_id."') AND  email NOT LIKE ('D3LMD-%') AND email NOT LIKE ('UNSU8MD-%') AND email LIKE '%".$_GET['term']."%' ORDER BY email", "obj");

            if(!empty($sqls->email)){
                $json['results'][] = [
                    'id' => $sqls->id,
                    'text' => $sqls->email.' - '.$sqls->name.' '.$sqls->surname
                ];
            }
            else{
                foreach ($sqls as $user) {
                    $json['results'][] = [
                        'id' => $user->id,
                        'text' => $user->email.' - '.$user->name.' '.$user->surname
                    ];
                }
            }

            echo json_encode($json);
        }
        elseif ($admin_type == 1){
	        $email = trim($_POST['uemail']);
            $user = sisplet_query("SELECT id, name, surname, email FROM users WHERE id NOT IN (SELECT user FROM srv_dostop_manage WHERE manager='".$global_user_id."') AND  email NOT LIKE ('D3LMD-%') AND email NOT LIKE ('UNSU8MD-%') AND email='".$email."'");

            if(mysqli_num_rows($user) > 0) {
                echo 'success';
            }
            else{
                echo 'error';
            }
        }
    }

	// Če je administrator

	/**
	 * odstrani uporabnika iz nadzora
	 *
	 */
	function ajax_edit_remove_user(){
		global $global_user_id;
		global $site_url;

		$uid = (int) $_GET['uid'];

		$sql = sisplet_query("DELETE FROM srv_dostop_manage WHERE user='".$uid."' AND manager='".$global_user_id."'");

		header("Location:  " . $site_url . "admin/survey/index.php?a=diagnostics&t=uporabniki&m=my");
	}

	/**
	 * odstrani uporabnika iz managerjevega nadzora
	 *
	 */
	function ajax_edit_remove_user_manager(){
		global $global_user_id;
		global $site_url;

		$uid = (int) $_GET['uid'];

		$sql = sisplet_query("DELETE FROM srv_dostop_manage WHERE user='$global_user_id' AND manager='$uid'");

		header("Location:  " . $site_url . "admin/survey/index.php?a=diagnostics&t=uporabniki");
	}

    /**
	 * admin odstrani uporabnika iz nadzora drugemu uporabniku (managerju ali adminu)
	 *
	 */
	function ajax_edit_remove_user_admin(){
		global $admin_type;

        if($admin_type != '0')
            return;

		$manager = (isset($_POST['manager'])) ? $_POST['manager'] : '0';
		$user = (isset($_POST['user'])) ? $_POST['user'] : '0';

        if($manager == '' || $manager == '0' || $user == '' || $user == '0')
            return;

		$sql = sisplet_query("DELETE FROM srv_dostop_manage WHERE user='$user' AND manager='$manager'");

        $this->ajax_dodeljeni_uporabniki_display();
	}

	/**
	 * Seznam vseh uporabnikov znotrja 1ke
	 */
	function ajax_all_users_list(){
		global $admin_languages;
		global $global_user_id;
		global $lang;
        global $admin_type;
        
		$seznam = [];

		$iskanjeSql = "";
		if(!empty($_POST['search']['value'])){
		    $iskaniNiz = $_POST['search']['value'];
		    $iskanjeSql = "  AND (u.name LIKE '%".$iskaniNiz."%' OR u.surname LIKE '%".$iskaniNiz."%' OR u.email LIKE '%".$iskaniNiz."%' OR d1.dostop_survey_count  LIKE '%".$iskaniNiz."%' OR d2.dostop_survey_archive LIKE '%".$iskaniNiz."%')";
        }

		// Pridobimo vse uporabnike
		$sql = "SELECT u.id as id, u.type as type, u.status, u.email as email, u.name as name, u.surname as surname, u.lang as lang, u.eduroam as aai, date_format(u.when_reg, '%d.%m.%Y') as registriran, u.gdpr_agree as gdpr_agree, dm.st_dodeljenih_uporabnikov as st_dodeljenih_uporabnikov, dm2.st_managerjev as st_managerjev, d1.dostop_survey_count as st_anket, d2.dostop_survey_archive as st_arhivskih, date_format(u.last_login, '%d.%m.%Y') as last_login, ue.email as second_email FROM users AS u ".
                " LEFT OUTER JOIN (SELECT srv_dostop.ank_id, srv_dostop.uid, count(*) AS dostop_survey_count FROM srv_dostop, srv_anketa WHERE srv_anketa.id=srv_dostop.ank_id AND srv_anketa.backup='0' GROUP BY srv_dostop.uid ) AS d1 ON d1.uid = u.id ".
                " LEFT OUTER JOIN (SELECT srv_dostop.ank_id, srv_dostop.uid, count(*) AS dostop_survey_archive FROM srv_dostop, srv_anketa WHERE srv_anketa.id=srv_dostop.ank_id AND srv_anketa.backup>'0' GROUP BY srv_dostop.uid ) AS d2 ON d2.uid = u.id ".
                " LEFT OUTER JOIN (SELECT srv_dostop_manage.manager, count(*) AS st_dodeljenih_uporabnikov FROM srv_dostop_manage GROUP BY srv_dostop_manage.manager) AS dm ON dm.manager = u.id ".
                " LEFT OUTER JOIN (SELECT srv_dostop_manage.user, count(*) AS st_managerjev FROM srv_dostop_manage GROUP BY srv_dostop_manage.user) AS dm2 ON dm2.user = u.id ".
                " LEFT OUTER JOIN (SELECT user_emails.email, user_emails.user_id FROM user_emails WHERE active=1)  AS ue ON ue.user_id = u.id".
                " WHERE u.email NOT LIKE ('D3LMD-%') AND u.email NOT LIKE ('UNSU8MD-%') ".$iskanjeSql;

        // Filtri, ki jih datatables pošilja in po katerih filtriramo
        if($_POST['order'][0]['column'] < 12) {
            $orderPolje = [
                "u.name ".$_POST['order'][0]['dir'].", u.surname",
                "u.email",
                "u.type",
                "u.lang",
                "u.eduroam", //AAI
                "d1.dostop_survey_count", //st_anket
                "d2.dostop_survey_archive", //st_arhivskih
                "dm.st_dodeljenih_uporabnikov", //st_dodeljenih_uporabnikov
                "dm2.st_managerjev", //st_managerjev
                "u.gdpr_agree",
                "u.when_reg",
                "u.last_login"
            ];

            if($_POST['order'][0]['column'] == 9){

                $vrednost='u.gdpr_agree desc';
                if($_POST['order'][0]['dir'] == 'asc'){
                    $vrednost= ' FIELD (u.gdpr_agree, 0, \'-1\', 1)';
                }

                $sql .= " ORDER BY ".$vrednost;
            }
            else {
                $sql .= " ORDER BY ".$orderPolje[$_POST['order'][0]['column']]." ".$_POST['order'][0]['dir'];
            }
        }

        if($_POST['length'] != '-1') {
            $sql .= " LIMIT ".$_POST['start'].", ".$_POST['length'];
        }

        $resultQuery = sisplet_query($sql);
        $resultU = lazyLoadSqlObj($resultQuery);

        // Seznam uporabnikov vrne za administratorje vse za ostale pa samo tiste, ki smo jih dodali k uporabniku.
        if (!empty($resultU)) {

            if (!empty($resultU->name)) {
                $vsi[] = $resultU;
            } 
            else {
                $vsi = $resultU;
            }

            foreach ($vsi as $uporabnik) {
                $seznam[] = [
                    iconv(mb_detect_encoding( $uporabnik->name, mb_detect_order(), true), "UTF-8", $uporabnik->name) .' '.iconv(mb_detect_encoding( $uporabnik->surname, mb_detect_order(), true), "UTF-8", $uporabnik->surname),
                    (!empty($uporabnik->second_email) ? iconv(mb_detect_encoding(  $uporabnik->second_email, mb_detect_order(), true), "UTF-8", $uporabnik->second_email) : iconv(mb_detect_encoding(  $uporabnik->email, mb_detect_order(), true), "UTF-8", $uporabnik->email)),
                    $this->userTypeToText($uporabnik->type),
                    $admin_languages[$uporabnik->lang],
                    (!empty($uporabnik->aai) ? $this->vrniDaNe($uporabnik->aai) : $lang['no1']),
                    (!empty($uporabnik->st_anket) ? $uporabnik->st_anket : 0),
                    (!empty($uporabnik->st_arhivskih) ? $uporabnik->st_arhivskih : 0),
                    '<a href="#" onclick="dodeljeni_uporabniki_display(\''.$uporabnik->id.'\'); return false;" title="'.$lang['srv_manager_manager'].'">'.(!empty($uporabnik->st_dodeljenih_uporabnikov) ? $uporabnik->st_dodeljenih_uporabnikov : 0).'</a>',
                    (!empty($uporabnik->st_managerjev) ? $uporabnik->st_managerjev : 0),
                    $lang["users_gdpr".$uporabnik->gdpr_agree],
                    $uporabnik->registriran,
                    $uporabnik->last_login,
                    '<a href="#" onclick="edit_user(\''.$uporabnik->id.'\'); return false;" title="'.$lang['srv_info_modify'].'"><i class="fa fa-pencil-alt link-sv-moder"></i></a>'.
                    ' | <a href="#" onclick="vsiUporabnikiAkcija(\''.$uporabnik->id.'\', \'ban\'); return false;" title="'.$lang[($uporabnik->status == 0 ? 'srv_user_banned' : 'srv_user_notbanned')].'"><i class="fa fa-ban '.($uporabnik->status == 0 ? 'link-rdec' : 'link-sv-moder').'"></i></a>'.
                    ' | <a href="#" onclick="vsiUporabnikiAkcija(\''.$uporabnik->id.'\', \'delete\'); return false;" title="'.$lang['srv_multicrosstabs_tables_delete_short'].'"><i class="fa fa-times link-sv-moder"></i></a>'
                ];
            }
        }

        $sql_recordsTotal = sisplet_query("SELECT count(id) as stVseh FROM users WHERE email NOT LIKE ('D3LMD-%') AND email NOT LIKE ('UNSU8MD-%')", "obj");
        
        // Število vseh zadetkov, ki jih imamo v bazi
        $recordsTotal = 0;
        if(!empty($sql_recordsTotal)) {
            $recordsTotal = $sql_recordsTotal->stVseh;
        } 

        // Število filtriranih zadetkov
        $recordFiltered = $recordsTotal;
        if(!empty($_POST['search']['value']))
            $recordFiltered = sizeof($vsi);

		echo json_encode([
                       "draw" => (!empty($_POST['draw']) ? $_POST['draw'] : 1),
			                 "recordsTotal" => $recordsTotal,
			                 "recordsFiltered" => $recordFiltered,
			                 "data" => $seznam   // polje z vsebino
		                 ]);
    }
    
    /**
	 * Seznam dodeljenih uporabnikov (manager in admin)
	 */
	function ajax_all_users_list_my(){
		global $admin_languages;
		global $global_user_id;
		global $lang;
        global $admin_type;
        
		$seznam = [];

		$iskanjeSql = "";
		if(!empty($_POST['search']['value'])){
		    $iskaniNiz = $_POST['search']['value'];
		    $iskanjeSql = "  AND (u.name LIKE '%".$iskaniNiz."%' OR u.surname LIKE '%".$iskaniNiz."%' OR u.email LIKE '%".$iskaniNiz."%' OR d1.dostop_survey_count  LIKE '%".$iskaniNiz."%' OR d2.dostop_survey_archive LIKE '%".$iskaniNiz."%')";
        }

		// Pridobimo vse uporabnike
		$sql = "SELECT u.id as id, u.type as type, u.status, u.email as email, u.name as name, u.surname as surname, u.lang as lang, u.eduroam as aai, date_format(u.when_reg, '%d.%m.%Y') as registriran, u.gdpr_agree as gdpr_agree, d1.dostop_survey_count as st_anket, d2.dostop_survey_archive as st_arhivskih, date_format(u.last_login, '%d.%m.%Y') as last_login, ue.email as second_email FROM users AS u ".
                " LEFT OUTER JOIN ( SELECT srv_dostop.ank_id, srv_dostop.uid, count(*) AS dostop_survey_count FROM srv_dostop, srv_anketa WHERE srv_anketa.id=srv_dostop.ank_id AND srv_anketa.backup='0' GROUP BY srv_dostop.uid ) AS d1 ON d1.uid = u.id ".
                " LEFT OUTER JOIN ( SELECT srv_dostop.ank_id, srv_dostop.uid, count(*) AS dostop_survey_archive FROM srv_dostop, srv_anketa WHERE srv_anketa.id=srv_dostop.ank_id AND srv_anketa.backup>'0' GROUP BY srv_dostop.uid ) AS d2 ON d2.uid = u.id ".
                " LEFT OUTER JOIN  (SELECT user_emails.email, user_emails.user_id FROM user_emails WHERE active=1)  AS ue ON ue.user_id = u.id".
                " WHERE u.email NOT LIKE ('D3LMD-%') AND u.email NOT LIKE ('UNSU8MD-%') ".$iskanjeSql;

		// Filter samo po lastnih uporabnikih
        $isciPoDomeni = '';

        // Med lastne uporabnike prikažemo tudi, tiste ki so bili registrirani z isto domeno
        /*UserSetting :: getInstance()->Init($global_user_id);
        $domena = UserSetting :: getInstance()->getUserSetting('manage_domain');
        if(!empty($domena)){
            $isciPoDomeni = " OR u.email LIKE '%".$domena."'";
        }*/

        $sql .= " AND (u.id IN (SELECT user FROM srv_dostop_manage WHERE manager='".$global_user_id."') ".$isciPoDomeni.")";


        // Filtri, ki jih datatables pošilja in po katerih filtriramo
        if($_POST['order'][0]['column'] < 10) {
            $orderPolje = [
                "u.name ".$_POST['order'][0]['dir'].", u.surname",
                "u.email",
                "u.type",
                "u.lang",
                "u.eduroam", //AAI
                "d1.dostop_survey_count", //st_anket
                "d2.dostop_survey_archive", //st_arhivskih
                "u.gdpr_agree",
                "u.when_reg",
                "u.last_login"
            ];

            if($_POST['order'][0]['column'] == 7){

                $vrednost='u.gdpr_agree desc';
                if($_POST['order'][0]['dir'] == 'asc'){
                    $vrednost= ' FIELD (u.gdpr_agree, 0, \'-1\', 1)';
                }

                $sql .= " ORDER BY ".$vrednost;
            }
            else {
                $sql .= " ORDER BY ".$orderPolje[$_POST['order'][0]['column']]." ".$_POST['order'][0]['dir'];
            }
        }

        if($_POST['length'] != '-1') {
            $sql .= " LIMIT ".$_POST['start'].", ".$_POST['length'];
        }

        $resultQuery = sisplet_query($sql);
        $resultU = lazyLoadSqlObj($resultQuery);

        // Seznam uporabnikov vrne za administratorje vse za ostale pa samo tiste, ki smo jih dodali k uporabniku.
        if (!empty($resultU) && ($this->jeAdministrator() || !$this->jeAdministrator())) {

            if (!empty($resultU->name)) {
                $vsi[] = $resultU;
            } 
            else {
                $vsi = $resultU;
            }

            foreach ($vsi as $uporabnik) {
                $seznam[] = [
                    iconv(mb_detect_encoding( $uporabnik->name, mb_detect_order(), true), "UTF-8", $uporabnik->name) .' '.iconv(mb_detect_encoding( $uporabnik->surname, mb_detect_order(), true), "UTF-8", $uporabnik->surname),
                    (!empty($uporabnik->second_email) ? iconv(mb_detect_encoding(  $uporabnik->second_email, mb_detect_order(), true), "UTF-8", $uporabnik->second_email) : iconv(mb_detect_encoding(  $uporabnik->email, mb_detect_order(), true), "UTF-8", $uporabnik->email)),
                    $this->userTypeToText($uporabnik->type),
                    $admin_languages[$uporabnik->lang],
                    (!empty($uporabnik->aai) ? $this->vrniDaNe($uporabnik->aai) : $lang['no1']),
                    (!empty($uporabnik->st_anket) ? $uporabnik->st_anket : 0),
                    (!empty($uporabnik->st_arhivskih) ? $uporabnik->st_arhivskih : 0),
                    $lang["users_gdpr".$uporabnik->gdpr_agree],
                    $uporabnik->registriran,
                    $uporabnik->last_login,
                    '<a href="#" onclick="edit_user(\''.$uporabnik->id.'\'); return false;" title="'.$lang['srv_info_modify'].'"><i class="fa fa-pencil-alt link-sv-moder"></i></a>'.
                    ' | <a href="#" onclick="vsiUporabnikiAkcija(\''.$uporabnik->id.'\', \'ban\'); return false;" title="'.$lang[($uporabnik->status == 0 ? 'srv_user_banned' : 'srv_user_notbanned')].'"><i class="fa fa-ban '.($uporabnik->status == 0 ? 'link-rdec' : 'link-sv-moder').'"></i></a>'.
                    ' | <a href="#" onclick="vsiUporabnikiAkcija(\''.$uporabnik->id.'\', \'delete\'); return false;" title="'.$lang['srv_multicrosstabs_tables_delete_short'].'"><i class="fa fa-times link-sv-moder"></i></a>'
                ];

            }
        }
        
        // Število vseh zadetkov, ki jih imamo v bazi
        $recordsTotal = 0;
        $recordsTotal = sizeof($vsi);

        // Število filtriranih zadetkov
        $recordFiltered = $recordsTotal;
        if(!empty($_POST['search']['value']))
            $recordFiltered = sizeof($vsi);

		echo json_encode([
                       "draw" => (!empty($_POST['draw']) ? $_POST['draw'] : 1),
			                 "recordsTotal" => $recordsTotal,
			                 "recordsFiltered" => $recordFiltered,
			                 "data" => $seznam   // polje z vsebino
		                 ]);
	}

	/**
	 * Izbrišemo uporabnika, še vendo pa hranimo njegove ankete
	 */
	function  ajax_all_users_list_delete(){
			$uid = (!empty($_POST['uid']) ? $_POST['uid'] : null);

			if($this->sebeNeMoreIzbrisati($uid)){
				return false;
			}

			$result = sisplet_query ("UPDATE users SET status=0, email=CONCAT('D3LMD-', UNIX_TIMESTAMP(), email) WHERE id='".$uid."'");
	}

	function ajax_all_users_list_ban(){
		$uid = (!empty($_POST['uid']) ? $_POST['uid'] : null);

		if($this->sebeNeMoreIzbrisati($uid)){
			return false;
		}

		$user = sisplet_query ("SELECT id, status FROM users WHERE id='".$uid."'", 'obj');

		$status = 0;
		if($user->status == 0)
			$status = 1;

		sisplet_query ("UPDATE users SET status='".$status."' WHERE id='".$uid."'");
	}

	private function sebeNeMoreIzbrisati($id){
		global $global_user_id;

		if($global_user_id == $id)
			return TRUE;

		return false;
	}


	private function jeAdministrator()
	{
		global $admin_type;

		if ($admin_type == 0) {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Pridobimo vrste uporabnika v besedilni obliki
	 *
	 * @param $db_type
	 *
	 * @return mixed
	 */
	function userTypeToText($db_type)
	{
		global $lang;

		$type = $lang['admin_narocnik'];

		switch ($db_type) {
			case 0:
				$type = $lang['admin_admin'];
				break;
			case 1:
				$type = $lang['admin_manager'];
				break;
			case 2:
			case 3:
				$type = $lang['admin_narocnik'];
				break;
		}

		return $type;
	}

	/**
	 * Seznam vseh izbrisanih uporabnikov, ki jih pridobi datatables
	 */
	function ajax_delete_users_list()
	{
		global $admin_languages;
		$seznam = [];

		$resultQuery = sisplet_query("SELECT name, surname, SUBSTRING(REPLACE (email, 'D3LMD-', ''),11) as email, type, DATE_FORMAT(when_reg, '%d.%m.%Y') as registriran, lang FROM users WHERE  email LIKE ('D3LMD-%')");
        $resultU = lazyLoadSqlObj($resultQuery);

		if (!empty($resultU) && $this->jeAdministrator()) {

			if (!empty($resultU->name)) {
				$izbrisani[] = $resultU;
			} else {
				$izbrisani = $resultU;
			}

			foreach ($izbrisani as $uporabnik) {
				$seznam[] = [
					iconv(mb_detect_encoding( $uporabnik->name, mb_detect_order(), true), "UTF-8", $uporabnik->name) .' '.iconv(mb_detect_encoding( $uporabnik->surname, mb_detect_order(), true), "UTF-8", $uporabnik->surname),
					iconv(mb_detect_encoding(  $uporabnik->email, mb_detect_order(), true), "UTF-8", $uporabnik->email),
					$this->userTypeToText($uporabnik->type),
					$admin_languages[$uporabnik->lang],
					$uporabnik->registriran,
				];
			}
		}

		echo json_encode([
			                 "data" => $seznam   // polje z vsebino
		                 ]);

	}

	public function ajax_unsigned_users_list()
	{
		global $admin_languages;
		$seznam = [];

		$odjavljeniQuery = sisplet_query("SELECT name, surname, SUBSTRING(REPLACE (email, 'UNSU8MD-', ''),11) as email, type, DATE_FORMAT(when_reg, '%d.%m.%Y') as registriran, status, lang FROM users WHERE  email LIKE ('UNSU8MD-%')");
        $odjavljeni_db = lazyLoadSqlObj($odjavljeniQuery);

		if (!empty($odjavljeni_db) && $this->jeAdministrator()) {

			if (!empty($odjavljeni_db->name)) {
				$odjavljeni[] = $odjavljeni_db;
			} else {
				$odjavljeni = $odjavljeni_db;
			}

			foreach ($odjavljeni as $uporabnik) {
				$seznam[] = [
					iconv(mb_detect_encoding( $uporabnik->name, mb_detect_order(), true), "UTF-8", $uporabnik->name) .' '.iconv(mb_detect_encoding( $uporabnik->surname, mb_detect_order(), true), "UTF-8", $uporabnik->surname),
					iconv(mb_detect_encoding(  $uporabnik->email, mb_detect_order(), true), "UTF-8", $uporabnik->email),
					$this->userTypeToText($uporabnik->type),
					$admin_languages[$uporabnik->lang],
					$uporabnik->registriran,
				];
			}
		}

		echo json_encode([
			                 "data" => $seznam   // polje z vsebino
		                 ]);
	}

	public function ajax_unconfirmed_mail_user_list_delet_user()
	{
		$uid = (!empty($_POST['uid']) ? $_POST['uid'] : NULL);

		if (empty($uid)) {
			return NULL;
		}

		sisplet_query("DELETE FROM users_to_be WHERE id='" . $uid . "'");

		echo 'ok';
	}

	public function ajax_confirm_user_email(){
		global $pass_salt;
		global $lang;

		$uid = (!empty($_POST['uid']) ? $_POST['uid'] : NULL);

		if (empty($uid)) {
			return NULL;
		}

		// kopirano iz user_to_be v users
		$result = sisplet_query("SELECT type, email, name, surname, pass, status, gdpr_agree, when_reg, came_from, lang FROM users_to_be WHERE id='" . $uid . "'");

		if (mysqli_num_rows($result) > 0) {
			$r = mysqli_fetch_assoc($result);
			$g = base64_encode((hash('SHA256', base64_decode($r['pass']) . $pass_salt)));

			sisplet_query("INSERT INTO users (type, email, name, surname, pass, status, gdpr_agree, when_reg, came_from, lang, manuallyApproved) 
                      VALUES ('" . $r['type'] . "', '" . $r['email'] . "', '" . $r['name'] . "', '" . $r['surname'] . "', '" . $g . "','" . $r['status'] . "', '" . $r['gdpr_agree'] . "','" . $r['when_reg'] . "', '" . $r['came_from'] . "', '" . $r['lang'] . "',  'Y')");
			sisplet_query("DELETE FROM users_to_be WHERE id='" . $uid . "' OR email='" . $r['email'] . "'");
           

            // Uporabniku posljemo email da je bil njegov racun aktiviran
            $Content = $lang['confirmed_user_mail'];
        
            // Podpis
            $signature = Common::getEmailSignature();
            $Content .= $signature;

            // Ce gre slucajno za virutalko
            $Subject = $lang['confirmed_user_mail_subject'];	
            
            $PageName = AppSettings::getInstance()->getSetting('app_settings-app_name');
            $ZaMail = '<!DOCTYPE HTML PUBLIC"-//W3C//DTD HTML 4.0 Transitional//EN">'.'<html><head>  <title>'.$Subject.'</title><meta content="text/html; charset=utf-8" http-equiv=Content-type></head><body>';

            // Besedilo v lang dilu je potrebno popravit, ker nimamo vec cel kup parametrov
            $Content = str_replace("SFNAME", $r['name'].' '.$r['surname'], $Content);
            $Content = str_replace("SFPAGENAME", $PageName, $Content);

            $Subject = str_replace("SFPAGENAME", $PageName, $Subject);

            $ZaMail .= $Content;
            $ZaMail .= "</body></html>";

            // Za testiranje brez posiljanja maila
            if(isDebug()) {
                echo $ZaMail; 
                die();
            }

            // Posljemo mail, da je bil racun aktiviran
            try{
                $MA = new MailAdapter(null, 'account');  
                $MA->addRecipients($r['email']);
                $result = $MA->sendMail($ZaMail, $Subject);
            }
            catch (Exception $e){
                echo $e;
            }

            echo 'ok';
        } 
        else {
			echo 'non';
		}
	}

	public function ajax_unconfirmed_mail_user_list()
	{
		global $admin_languages;
		global $lang;
		$seznam = [];

		$resultQuery = sisplet_query("SELECT id, name, surname, email, type, DATE_FORMAT(when_reg, '%d.%m.%Y') as registriran, lang  FROM users_to_be");
        $resultU = lazyLoadSqlObj($resultQuery);

		if (!empty($resultU)) {

			// V kolikor imamo samo eno vrstico vpisano, potem objekt spremenimo v multiarray
			if (!empty($resultU->name)) {
				$nepotrjeni[] = $resultU;
			} else {
				$nepotrjeni = $resultU;
			}

			$seznam = [];
			foreach ($nepotrjeni as $uporabnik) {
				$seznam[] = [
					iconv(mb_detect_encoding( $uporabnik->name, mb_detect_order(), true), "UTF-8", $uporabnik->name) .' '.iconv(mb_detect_encoding( $uporabnik->surname, mb_detect_order(), true), "UTF-8", $uporabnik->surname),
					iconv(mb_detect_encoding(  $uporabnik->email, mb_detect_order(), true), "UTF-8", $uporabnik->email),
					$this->userTypeToText($uporabnik->type),
					$admin_languages[$uporabnik->lang],
					$uporabnik->registriran,
					'<a href="#" onclick="potrdiNepotrjenegaUporabnika(' . $uporabnik->id . ')" title="' . $lang['confirm_user_in_db'] . '"><i class="fa fa-check link-sv-moder"></i> <span class="no-print"> | </span>' .
					'<a href="#" onclick="izbrisiNepotrjenegaUporabnika(' . $uporabnik->id . ')" title="'.$lang['delete_user_in_db'].'"><i class="fa fa-times link-sv-moder"></a>',
				];
			}
		}

		echo json_encode([
			                 "data" => $seznam   // polje z vsebino
		                 ]);
	}

    // Popup z dodeljenimi uporabniki
    private function ajax_dodeljeni_uporabniki_display(){
        global $lang;

        $manager = (isset($_POST['manager'])) ? $_POST['manager'] : '0';

        if($manager == '' || $manager == '0'){
            return;
        }


        echo '<div class="popup_close"><a href="#" onClick="dodeljeni_uporabniki_close(); return false;">✕</a></div>';
			
        echo '<h2>'.$lang['srv_manager_count'].'</h2>';

        
        echo '<div class="popup_content dodeljeni_uporabniki">';

         // Seznam dodeljenih uporabnikov
        $sqlUsers = sisplet_query("SELECT u.id, u.name, u.surname, u.email, u.status
                                    FROM users u, srv_dostop_manage m
                                    WHERE u.id=m.user AND m.manager='".$manager."'
                                ");
        if(mysqli_num_rows($sqlUsers) > 0){

            echo '<ul>';

            while($rowUsers = mysqli_fetch_array($sqlUsers)){
                echo '<li>';
                
                echo '  <span>';
                echo $rowUsers['name'].' '.$rowUsers['surname'].' ('.$rowUsers['email'].')';
                if($rowUsers['status'] == '0')
                    echo ' - <span class="red italic">NEAKTIVEN</span>';
                echo '  </span>';
                
                echo '  <span><a onClick="dodeljeni_uporabniki_remove(\''.$manager.'\', \''.$rowUsers['id'].'\');">'.$lang['hour_remove'].'</a></span>';
                
                echo '</li>';
            }

            echo '</ul>';
        }

        // Dodajanje novega uporabnika
        echo '<h4>'.$lang['srv_manager_add_admin'].'</h4>';
        echo '<div class="add_user">';
        
        echo '<form class="manager_add_user" name="admin_add_dostop" action="ajax.php?t=dostop&a=admin_add_user" method="post">';

        echo '<h3><b>'.$lang['srv_manager_add_user_popup'].'</b></h3>';
        echo '<p><select name="add_user_id" id="add_user_id" class="js-obstojeci-uporabniki-admin-ajax" style="width: 300px;"></select></p>';
        
        echo '<p><div class="buttonwrapper floatLeft">';
        echo '  <a class="ovalbutton ovalbutton_orange" href="#" onClick="dodeljeni_uporabniki_add(\''.$manager.'\', \''.$rowUsers['id'].'\');">'.$lang['add'].'</a>';
        echo '</div></p><br><br>';

        echo '</form>';

        echo '<script>$(\'.js-obstojeci-uporabniki-admin-ajax\').select2({
                    minimumInputLength: 3,
                    ajax: {
                        url: \'ajax.php?t=dostop&a=find_user\',
                        dataType: \'json\'
                    }
                });</script>';

        echo '</div>';

        echo '</div>';


        echo '<div class="buttons_holder">';
        echo '<span class="buttonwrapper floatRight" title="'.$lang['srv_zapri'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="dodeljeni_uporabniki_close(); return false;"><span>'.$lang['srv_zapri'].'</span></a></span>';
        echo '</div>';
    }

	function isAnketar()
	{
		global $admin_type;
		# preverimo ali je anketar
		return ($this->checkDostopSub('phone') && $admin_type > 1);

	}

	/**
	 * vrne seznam vseh uporabnikov z dostopom do ankete
	 *
	 */
	function getDostop()
	{

		$dostop = [];

		$dostop[0] = $this->getAdminsDostop();
		$dostop[1] = $this->getManagersDostop();
		$dostop[2] = $this->getUsersDostop();

		return $dostop;
	}

	/**
	 * preveri ali imajo do ankete dostop administratorji
	 * ta funkcija ni!!! primerna za preverjat, ce prikazemo anketo
	 * administratorju, ker se mora poleg tega preverjati se, ce je uporabnik
	 * admin pa to
	 *
	 */
	function getAdminsDostop()
	{

		SurveyInfo::getInstance()->SurveyInit($this->anketa);
		$rowa = SurveyInfo::getInstance()->getSurveyRow();

		if (strtotime($rowa['dostop_admin']) >= strtotime(date("Y-m-d"))) {
			return $rowa['dostop_admin'];
		}

		return FALSE;
	}

	/**
	 * kdo ima managerski dostop (od managerjev in administratorjev)
	 *
	 */
	function getManagersDostop()
	{

		$sql = sisplet_query("SELECT u.* FROM users u, srv_dostop_manage m WHERE u.id=m.manager AND m.user IN (SELECT uid FROM srv_dostop WHERE ank_id='$this->anketa') ");
		while ($row = mysqli_fetch_array($sql)) {
			$dostop[] = $row;
		}

		return $dostop;
	}

	/**
	 * kdo od uporabnikov ima dostop
	 *
	 */
	function getUsersDostop()
	{

		$sql = sisplet_query("SELECT u.* FROM srv_dostop d, users u WHERE u.id=d.uid AND d.ank_id = '$this->anketa'");
		while ($row = mysqli_fetch_array($sql)) {
			$dostop[] = $row;
		}

		return $dostop;
	}

	/**
	 * Vrenemo besedni izraz za 1/0 iz podatkovne baze
	 *
	 * @param int $val
	 *
	 * @return mixed
	 */
	private function vrniDaNe($val = 0)
	{
		global $lang;

		if ($val == 1) {
			return $lang['yes'];
		}

		return $lang['no1'];
	}

}

?>