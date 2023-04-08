<?php
/**
 * Created on 11.12.2009
 *
 * @author: Gorazd Veselič
 */

session_start();

global $site_path;


class SurveyRespondents {

	static private $instance;

	static private $surveyId = null;
	static private $uId = null;

	static private $messages = null;
	static private $errors = null;

	static private $currentProfileId = null; // trenutno izbrana lista
	static private $profiles = array();		// seznam vseh list uporannika

	static private $systemVariables = null;
	/* naredimo v konstruktu
		static private $systemVariables = array(
									'email' => array('naslov'=>$lang['srv_system_variables_email']),
									'ime' => array('naslov'=>$lang['srv_system_variables_name']),
									'priimek' => array('naslov'=>$lang['srv_system_variables_surname']),
									'telefon' => array('naslov'=>$lang['srv_system_variables_phone']),
									'naziv' => array('naslov'=>$lang['srv_system_variables_title']));
	*/

	protected function __construct() {
		global $lang;
		self::$systemVariables = array(
									'email' => array('naslov'=>$lang['srv_system_variables_email']),
									'pass' => array('naslov'=>$lang['password']),
									'ime' => array('naslov'=>$lang['srv_system_variables_name']),
									'priimek' => array('naslov'=>$lang['srv_system_variables_surname']),
									'telefon' => array('naslov'=>$lang['srv_system_variables_phone']),
									'naziv' => array('naslov'=>$lang['srv_system_variables_title']),
									'drugo' => array('naslov'=>$lang['srv_system_variables_custom']),
									'odnos' => array('naslov'=>$lang['srv_system_variables_relation']));
	}

	final private function __clone() {}

	/** Poskrbimo za samo eno instanco razreda
	 *
	 */
	static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new SurveyRespondents();
		}
		return self::$instance;
	}

	/** napolnimo podatke
	 *
	 */
	static function Init($_surveyId)
	{
		global $global_user_id;
		 
		if ($_surveyId && $global_user_id)
		{
			self::$surveyId = $_surveyId;
			self::$uId = $global_user_id;
			// inicializiramo datoteko z nastavitvami
			SurveyUserSetting :: getInstance()->Init(self::$surveyId, self::$uId);			
			// polovimo uporabnikovo privzeto listo
			self::getProfileId();
			self::getProfiles();
			self::getSistemVariables();
		} else {
			die("Mandatory data missing in SurveyRespondents class!");
		}
	}

	static function getSurveyId()				{ return self::$surveyId; }
	static function getGlobalUserId()			{ return self::$uId; }
	static function getCurentProfileId()			{ return self::$currentProfileId; }
	
	static function getProfileId() {
		// preverimo ali obstaja nastavitev
		// poiščemo kateri profil variabel imamo
		$drl = SurveyUserSetting :: getInstance()->getSettings('default_respondent_profile');


		if ($drl == null || $drl < 1) {
			$drl = self::checkDefaultProfile();
			if ($drl > 0)
				SurveyUserSetting :: getInstance()->saveSettings('default_respondent_profile', $drl);
				
		}
		self::$currentProfileId = $drl;
		return self::$currentProfileId;
	}
	
	static function checkDefaultProfile() {
		global $lang;
		
		# če smo v telefonski anketi skreiramo začasen prazen profil,
		if ($_GET['a'] == A_PHONE || $_POST['profile_from'] == A_PHONE) {
			
			// najprej preverimo ali  obstaja seja začasin profil
			if ( isset($_SESSION['respondent_profile']) ) {
				# preverimo ali obstaja variabla telefon, potem je bil provil pravilno skreiran
				if (strpos($_SESSION['respondent_profile']['variables'],'telefon')) {
					// vrnemo id iz seje
					return $_SESSION['respondent_profile']['id'];
				}				
			} 
			# če profil ne obstaja skreiramo nov zacasni prodil v obliki seje

			$_SESSION['respondent_profile'] = array(	'id'	=> 0, 
														'uid'	=> self::getGlobalUserId(), 
														'variables' => 'telefon',
														'lines' => array());
			if (!isset($_SESSION['respondent_profile']['name'])) {
				$_SESSION['respondent_profile']['name']	= $lang['srv_temp_profile_author'];														
			}
			return 0;	# vrnemo id = 0, pravkar skreiranega profila
		}
		# če pa smo v e-mail anketi pa skreiramo prazen e-mail profil
		if ($_GET['a'] == A_EMAIL || $_POST['profile_from'] == A_EMAIL) {

			// najprej preverimo ali  obstaja seja začasin profil
			if ( isset($_SESSION['respondent_profile']) ) {
				# preverimo ali obstaja variabla email, potem je bil provil pravilno skreiran
				if (strpos($_SESSION['respondent_profile']['variables'],'email')) {
					// vrnemo id iz seje
					return $_SESSION['respondent_profile']['id'];
				}				
			} 
			# če profil ne obstaja skreiramo nov zacasni prodil v obliki seje
			# in dodamo listo z avtorjevim e-mailom
			$qryEmail = sisplet_query("SELECT email FROM users WHERE id = '".self::getGlobalUserId()."'");
			$rowEmail = mysqli_fetch_assoc($qryEmail);

			$_SESSION['respondent_profile'] = array(	'id'	=> 0, 
														'uid'	=> self::getGlobalUserId(), 
														'variables' => 'email',
														'lines' => array($rowEmail['email']));
			if (!isset($_SESSION['respondent_profile']['name'])) {
				$_SESSION['respondent_profile']['name']	= $lang['srv_temp_profile_author'];														
			}
			return 0;	# vrnemo id = 0, pravkar skreiranega profila
		}
		
		# če smo tukaj je napaka !
		return 0;
	}

	/** Pridobimo seznam vseh list uporabnika
	 *  v obliki arraya
	 */
	static function getProfiles() {

		self::$profiles = array();
		// dodamo profil iz seje , če obstaja
		if ( isset($_SESSION['respondent_profile']) ) {
			self::$profiles[$_SESSION['respondent_profile']['id']] = $_SESSION['respondent_profile'];
		}
		
		// dodamo še ostale profile iz baze
		$stringSelect = "SELECT * FROM  srv_respondent_profiles WHERE uid = '".self::getGlobalUserId()."' ORDER BY id";
		$querySelect = sisplet_query($stringSelect);

		while ( $rowSelect = mysqli_fetch_assoc($querySelect) ) {
			self::$profiles[$rowSelect['id']] = $rowSelect;
		}
		return self::$profiles;
	}
	
	static function getProfileData($pid = null) {
		$result = array();
		if($pid == null) {
			$pid = self::$currentProfileId;
		}
		if (isset($_SESSION['respondent_profile']['id']) && $pid == $_SESSION['respondent_profile']['id']) {
			$result[$_SESSION['respondent_profile']['id']] = array('pid'=> $_SESSION['respondent_profile']['id'], 'lines'=>$_SESSION['respondent_profile']['lines']);
			return $result;
		} 

		// cene dodamo podatke iz baze
				
		$selectString = "SELECT * FROM srv_respondents WHERE pid = '".$pid."'";
		$querySelect = sisplet_query($selectString);
		$_lines = array();
		while ( $rowSelect = mysqli_fetch_assoc($querySelect) ){
			 $_lines[] =  $rowSelect['line']; 	
		}
		$result[$pid] = array('pid'=> $pid, 'lines'=>$_lines);

		return $result;
	}	
	
	static function getSistemVariables() {
		// osnovnm sistemskim variablam dodamo tiste iz ankete
		$sqlSistemske = sisplet_query("SELECT s.id, s.naslov, s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.gru_id=g.id AND g.ank_id='".self::getSurveyId()."' ORDER BY g.vrstni_red, s.vrstni_red");
		if (mysqli_num_rows($sqlSistemske) > 0) {
			while ($rowSistemske = mysqli_fetch_assoc($sqlSistemske)) {
				if (!isset(self::$systemVariables[$rowSistemske['variable']])) {
					self::$systemVariables[$rowSistemske['variable']] = array('naslov'=>$rowSistemske['naslov']);
				}				
			}

		}
		return self::$systemVariables;
	}

	static function displayProfiles() {
		global $admin_type;
		global $lang;
		
		?>			
		<div class="respondent_profile_holder">
			<div id="respondent_profiles" class="select">
			<input type="hidden" id="profile_from"  value="<?php 
			
			echo isset($_POST['profile_from']) ? $_POST['profile_from'] : $_GET['a']; ?>" />
			<?php
			foreach ( self::$profiles as $key => $profile ) {
				echo '<div class="option' . ($profile['id'] == self::$currentProfileId ? ' active' : '') . '" value="' . $profile['id'] . '">' . $profile['name'] . '</div>';       
			}
			?>
			</div>
			<div class="clr"></div>
			<div class="respondent_profiles_links link_no_decoration">
			    <a href="#" onclick="showDeleteRespondentProfile(); return false;"><?=$lang['srv_delete_profile'];?></a><br/>
				<?php
				//zacasnega profila ne moremo preimenovat
				if (self::$currentProfileId != 0)
					echo '    <a href="#" onclick="showRenameRespondentProfile(); return false;">'.$lang['srv_rename_profile'].'</a>'."\n";
				?>
		  	</div>
		</div>
		<div id="respondent_profile_values" ><?=self::displayProfileData(self::$currentProfileId);?></div>
		<div id="respondent_profile_right" >
		<?php
		if (!($admin_type <= 1)) {
		?>
			<?=$lang['srv_user_base_user_note20']?></p><?
		}
		?>
		  <div id="respondent_profile_notes" ><?=$lang['srv_mailing_add_help'];?></div>
		  <div id="respondent_profile_upload" >
			<form id="resp_uploader" name="resp_uploader" method="post" enctype="multipart/form-data" action="index.php?anketa=<?=self::getSurveyId();?>&a=email&m=uploadRespondents">
				<input type="hidden" name="posted" value="1" /><?=$lang['srv_mailing_upload_list'];?>:<input type="file" name="ul" id="ul" size="42"onChange="submit();" />
			</form>
		  </div>
		</div>
		<div class="clr"></div>
		<script type="text/javascript">
		$(document).ready(function() {
		  $("#respondent_profiles .option").click(function() {
		  $("#respondent_profiles .option").each(function () {
		    $(this).removeClass("active"); });
		    $(this).addClass("active");
		    change_respondent_profile($(this).attr('value'));
			});
		});
		</script>
		<!-- dialog za skrani kot nov -->
		<div id="respondent_new_dialog"><?=$lang['srv_respondent_profile_name']?>: 
			<input id="newProfileName" name="newProfileName" type="text" value="" size="45"  />
			<input id="newProfileId" type="hidden" />
			<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$('#respondent_new_dialog, #fade').hide();  return false;"><span><?=$lang['srv_close_profile'];?></span></a></span></span>
			<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="respondent_saveNewProfile(); return false;"><span><?=$lang['srv_save_profile_yes'];?></span></a></span></span>			
		</div>

		<!-- dialog za preimenuj -->
		<div id="respondent_rename_dialog"><?=$lang['srv_respondent_profile_name'];?>: 
		<input id="renameProfileName" name="renameProfileName" type="text" value="<?=self::$profiles[self::$currentProfileId]['name'];?>" size="45"  />
		<input id="renameProfileId" type="hidden" />
		<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$('#respondent_rename_dialog, #fade').hide();  return false;"><span><?=$lang['srv_close_profile'];?></span></a></span></span>
		<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="respondent_renameProfile(); return false;"><span><?=$lang['srv_rename_profile_yes'];?></span></a></span></span>			
		</div>
		
		<!-- div za brisanje -->
		<div id="respondent_delete_dialog"><?=$lang['srv_missing_profile_delete_confirm'];?>: <b><?=self::$profiles[self::$currentProfileId]['name']?></b>?
		<input id="deleteProfileId" type="hidden" value="<?=self::$currentProfileId?>"  />
		<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$('#respondent_delete_dialog, #fade').hide();  return false;"><span><?=$lang['srv_close_profile'];?></span></a></span></span>
		<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="respondent_deleteProfile(); return false;"><span><?=$lang['srv_delete_profile_yes'];?></span></a></span></span>			
		</div>
		<?php
		
				
	}
	static function displayProfileData($pid) {
		
		global $lang;

		$pd = self::getProfileData($pid);
		
		$data = implode("\n",$pd[$pid]['lines']);

		echo '<p> <span class="labelSpanWide" >'.$lang['srv_respondents_variables'].':</span>';
		echo '<div id="respondent_profile_variables_box">';
		foreach (self::$systemVariables as $sysKey =>$sysVar) {
			echo  '<input type="checkbox" value="'.$sysKey.'" name="resp_check"',((strpos(self::$profiles[$pid]['variables'],$sysKey) === false) ? '' : ' checked="checked"'),
					' onChange="respondent_change_variable(\''.$sysKey.'\');" />',$sysVar['naslov'];


		} 
		echo '</div>';
		echo '<div class="clr"></div>';
		echo '<span class="labelSpanWide" >&nbsp;</span>';
		echo '<input type="text" id="respondent_profile_variables" value="' . self::$profiles[$pid]['variables'] . '" /></p>';
		echo '<p> <span class="labelSpanWide" >'.$lang['srv_respondents_respondents'].':</span>';
		echo '<textarea id="respondent_profile_value_text" >'.$data.'</textarea>';
		echo '<div class="">';

		echo '  <span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="respondent_run(\''.$pid.'\'); return false;"><span>'.$lang['srv_respondents_add'].'</span></a></div></span>';
		echo '  <span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="respondent_save(\''.$pid.'\'); return false;"><span>'.$lang['srv_respondents_save'].'</span></a></div></span>';
		echo '  <span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="respondent_save_new(\''.$pid.'\'); return false;"><span>'.$lang['srv_respondents_save_as_new'].'</span></a></div></span>';

		echo '	<div class="clr"></div>';

		echo '</div>';
	}
	

	static function CheckUploadedFile() {
		global $lang;
		$error = array();

		$fileName = $_FILES['ul']['name'];
        $tmpName  = $_FILES['ul']['tmp_name'];
        $fileSize = $_FILES['ul']['size'];
        $fileType = $_FILES['ul']['type'];


		$okFileType = ( $fileType == 'text/plain' || $fileType == 'application/vnd.ms-excel' );
		$okFileEnd = (pathinfo($fileName, PATHINFO_EXTENSION) != 'txt' || pathinfo($fileName, PATHINFO_EXTENSION) != 'csv');
		// preverimo tip: 
		if ( $okFileType = false ) {
				$error[] = $lang['srv_respondents_invalid_file_type'];	
		}
		// prevermio še končnico (.txt)
		else if ($okFileEnd = false)
			$error[] = $lang['srv_respondents_invalid_file_type'];
			
		// preverimo velikost
		if ( $fileSize == 0 )
			$error[] = $lang['srv_respondents_invalid_file_size'];
		
		// ce je do tu vse ok pregledamo vsebino
        $respondents = array();
        $fh = @fopen($tmpName, "rb");
        
        if ($fh) {
			//$data = fread($fh, 4096); // zakaj je ta limit?
			$data = fread($fh, filesize($tmpName));
	        fclose($fh);
	        
			// počistimo prazne vrstice in kakšno zlonamerno kodo
			$data = strip_tags(str_replace(array("\n\r","\n"),array("\n","\n"),$data));
			$respondents = explode("\n",$data);
			$respondents = array_values(array_filter($respondents));
			
		} else {
			$error[] = 'file read';
		}

		// ce ni napak in ce imamo zapise kreiramo nov zacasni profil v seji
		if (count($error) == 0 && count($respondents) > 0) {

			$_SESSION['respondent_profile'] = array(	'id'	=> 0, 
														'uid'	=> self::getGlobalUserId(),
														'variables' => 'email',
														'lines' => $respondents);
			$_SESSION['respondent_profile']['name'] = $lang['srv_respondents_temp_profile'];
			// nastavimo profil
			self::$currentProfileId = 0;
			
		} else {
			echo $lang['error'].'<br/>';
			foreach ( $error as $value ) {
       			echo $value,"<br/>";
			}
		}
		// prikazemo vrednosti
		self::getProfiles();
		self::displayProfiles();
	}

	static function checkSystemVariables($variables, $setUserbase=true) {
		$user_base = 0;
		global $site_path;

		$sqlb = sisplet_query("SELECT branching, user_base FROM srv_anketa WHERE id = '".self::getSurveyId()."'");
		$rowb = mysqli_fetch_array($sqlb);

		$ba = new BranchingAjax(self::getSurveyId());
		
		if (count($variables) > 0) {
			// zakaj je bi ta reverse???
			//$variables = array_reverse($variables,true);
			foreach ($variables as $variable) {

				$sqlVariable = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.variable='".$variable."' AND s.gru_id=g.id AND g.ank_id='".self::getSurveyId()."'");
				if (mysqli_num_rows($sqlVariable) == 0 && $variable!='pass') { // če varabla še ne obstaja jo kreiramo
																			  // za polje pass - Geslo ne kreiramo sistemske variable
					
					if ($variable != 'language') $user_base = 1;
					
					// dodamo novo spremenljivko na konec, tip je 21
					ob_start();
                    $ba->ajax_spremenljivka_new(0, 0, 1, 0, 21);
                    $spr_id = $ba->spremenljivka;
                    ob_clean();
                    
					$s = sisplet_query("UPDATE srv_spremenljivka SET variable='".$variable."', variable_custom='1', naslov='".((isset($allowed[$variable]['naslov']) ? $allowed[$variable]['naslov'] : $variable))."', sistem='1', visible='0' WHERE id='$spr_id'");
					if (!$s) echo 'err435'.mysqli_error($GLOBALS['connect_db']);
					
				}
			}
		}
		// če je potreben updejt (ampak najbrž je itak na 1 zaradi e-mail)
		if ($setUserbase == true && $user_base > 0 && $user_base != $rowb['user_base']) {
			$sql = sisplet_query("UPDATE srv_anketa SET user_base='$user_base' WHERE id='" . self::getSurveyId() . "'");
			SurveyInfo :: getInstance()->resetSurveyData();
		}
			
	}
	static function displayAddedUsers() {
		global $site_path, $site_url, $lang;
		
		SurveyInfo::getInstance()->SurveyInit(self::getSurveyId());
		$db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
		
		echo '<span>'.$lang['srv_respondents_added_respondents'].':</span>';
		echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="' . $site_url . 'admin/survey/index.php?anketa='.self::getSurveyId().'&a=email&m=usermailing_setting" ><span>'.$lang['forward'].'</span></a></span></span>';	 	
		echo '<br class="clr"/>';
		echo '<br/>';
		
		$temp_user_data = array();
		$data = array();
		$header = array();
		
		// poiščemo sistemske spremenljivke ki so navedene za spremenlivko
		$sprList = "";
		$prefix="";
		
		$sql = sisplet_query("SELECT s.id, s.variable, s.naslov FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.gru_id=g.id AND g.ank_id='".self::getSurveyId()."' ORDER BY g.vrstni_red, s.vrstni_red ASC");
		while($row = mysqli_fetch_assoc($sql)) {
			$sprList .= $prefix.$row['id'];
			$prefix=",";
			$header[$row['id']] = $row['naslov'];
		}
		
		// dodamo sistemske podatke
		$header_sys = array('pass'=>$lang['srv_respondents_cookie'], 
							'status'=>$lang['srv_respondents_status'],
							'datetime'=>$lang['srv_respondents_datetime'], 
							'admin_id'=>$lang['srv_respondents_admin']);

		$str_qry_users = "SELECT u.id AS usr_id, u.cookie, u.pass, u.unsubscribed, IF(u.last_status = '-1','null',u.last_status) as status FROM srv_user as u WHERE u.ank_id = '".self::getSurveyId()."' AND u.preview = '0' ";

		$qry_users = sisplet_query($str_qry_users);
		while ( $row_users = mysqli_fetch_assoc($qry_users)) {
			// polovimo podatke sistemskih spremenljivk od userja
			if ($sprList != "") {
			    $sqlSprData = sisplet_query("SELECT spr_id,text FROM srv_data_text".$db_table." WHERE usr_id='".$row_users['usr_id']."' AND spr_id IN (".$sprList.")");

				while ($rowSprData = mysqli_fetch_assoc($sqlSprData)) {
					$data[$row_users['usr_id']][$rowSprData['spr_id']] = $rowSprData['text']; 
				}
			}
			// uporabniku dodamo podatke o kookiju in statusu
			$data[$row_users['usr_id']]['pass'] = $row_users['pass'];
			$data[$row_users['usr_id']]['status'] = $row_users['status'] . ' - ' .$lang['srv_userstatus_'.$row_users['status']];

			if ($row_users['unsubscribed'] == 1) $data[$row_users['usr_id']]['status'] .= ', '.$lang['srv_unsubscribed'];
			
			// polovimo podatke iz userbase
//			$str_ub = "SELECT ub.*, usr.name, usr.surname, usr.email FROM srv_userbase AS ub LEFT JOIN (SELECT usr.* FROM users as usr) as usr ON usr.id = ub.admin_id WHERE ub.usr_id = '".$row_users['usr_id']."' ORDER BY ub.tip ASC LIMIT 1";
			$str_ub = "SELECT ub.* FROM srv_userbase AS ub WHERE ub.usr_id = '".$row_users['usr_id']."' ORDER BY ub.tip ASC LIMIT 1";
			$qry_ub = sisplet_query($str_ub);
			$row_ub = mysqli_fetch_assoc($qry_ub);

			$data[$row_users['usr_id']]['datetime'] = $row_ub['datetime'];
			
			// user insert data
			if (!isset($temp_user_data[$row_ub['admin_id']])) {
				$str_user = "SELECT usr.name, usr.surname, usr.email FROM users as usr WHERE usr.id = '".$row_ub['admin_id']."'";
				$qry_user = sisplet_query($str_user);
				$row_user = mysqli_fetch_assoc($qry_user);
				$data[$row_users['usr_id']]['admin_id'] = $row_user['name']." ".$row_user['surname'];
				$temp_user_data[$row_ub['admin_id']] = $row_user['name']." ".$row_user['surname'];
			} else
				$data[$row_users['usr_id']]['admin_id'] = $temp_user_data[$row_ub['admin_id']];

		}
		echo '<table id="respondentTable" class="anl_tbl anl_bt anl_bl anl_br anl_bb" style="padding:0px; margin:0px;  border-collapse: collapse;">' . "\n";
		// naslovna vrstica
		echo '<tr>';
		echo '<th class="anl_br anl_al anl_bck anl_bb anl_w50">'.$lang['srv_anketadelete_txt'].'</th>';
		echo '<th class="anl_br anl_al anl_bck anl_bb anl_w50">'.$lang['srv_unsubscribe_1'].'</th>';
		foreach ($header as $header_key => $headerTitle) {
			echo '<th class="anl_br anl_al anl_bb anl_bck">'. $headerTitle .'</th>';	
		}
		foreach ($header_sys as $header_key => $headerTitle) {
			echo '<th class="anl_br anl_al anl_bb anl_bck">'. $headerTitle .'</th>';	
		}
		
		echo '</tr>'."\n";				
		foreach ( $data as $user_id => $user_data ) {

			echo '<tr id="respondent_id_'.$user_id.'">';
				echo '<td class="anl_br anl_ac"><a href="#" onclick="respondent_data_delete(\''.$user_id.'\', \''.$lang['srv_ask_delete'].'\'); return false;"><img src="img_0/delete_red.png" alt="' . $lang['srv_delete_respondent'] . '" title="' . $lang['srv_delete_respondent'] . '" /></a></td>';
				echo '<td class="anl_br anl_ac"><a href="#" onclick="$.post(\'unsubscribe.php?anketa='.self::getSurveyId().'&code='.$user_data['pass'].'\', {}, function() { window.location.reload(); }); return false;"><img src="img_0/email_delete.png" alt="' . $lang['srv_unsubscribe_2'] . '" title="' . $lang['srv_unsubscribe_2'] . '" /></a></td>';
				// držimo se vrstnega reda header polij
				foreach ($header as $header_key => $headerTitle) {
					echo '<td class="anl_br anl_ac">'.($data[$user_id][$header_key] != null && $data[$user_id][$header_key] != "" ? $data[$user_id][$header_key] : '&nbsp;').'</td>';
				}
				foreach ($header_sys as $header_key => $headerTitle) {
					echo '<td class="anl_br anl_ac">'.($data[$user_id][$header_key] != null && $data[$user_id][$header_key] != "" ? $data[$user_id][$header_key] : '&nbsp;').'</td>';
				}
			echo '</tr>'."\n";
		}
		echo '</table>'. "\n";
		
	}
	
	static function Ajax($action) {
		switch ( $action ) {
			case 'save_new_respondent_profile':
				// shranimo podatek
				self::ajaxSaveNewProfile();		
				// osvezimo
				self::getProfileId();
				self::getProfiles();
				self::displayProfiles();
			break;
			
			case 'change_respondent_profile':
				// osvezimo
				$pid = (isset($_POST['pid']) ? $_POST['pid'] : SurveyUserSetting :: getInstance()->getSettings('default_respondent_profile') );
//				if ($pid > 0) // če ni seja, shranimo kot privzeti
					SurveyUserSetting :: getInstance()->saveSettings('default_respondent_profile', $pid);
				self::$currentProfileId = $pid;				
				self::getProfileId();
				self::getProfiles();
				self::displayProfiles();
//				self::displayProfileData($pid);
			break;
			case 'save_respondent_profile':
				// shranimo podatek
				self::ajaxSaveProfile();		
				self::getProfileId();
				self::getProfiles();
				self::displayProfiles();
			break;
			case 'run_respondent_profile':
				// shranimo podatek
				self::ajaxRunProfile();
				// osvežimo
				# odvisno ali smo v telefonu ali e-mail vabilih
				if ($_POST['profile_from'] == A_PHONE) {
					# če smo telefon
					echo 'index.php?anketa=',self::getSurveyId(),'&a='.A_PHONE.'&m=start';
				} else {
					# če smo e-mail
					echo 'index.php?anketa=',self::getSurveyId(),'&a=email&m=respondenti_view';
				}
			break;
			case 'rename_respondent_profile':
				// shranimo podatek
				self::ajaxRenameProfile();	
				// osvežimo	
				self::getProfileId();
				self::getProfiles();
				self::displayProfiles();
			break;
			case 'delete_respondent_profile':
				// pobrišemo
				self::ajaxDeleteProfile();
				// osvežimo		
				self::getProfileId();
				self::getProfiles();
				self::displayProfiles();
			break;


			default:
				self::displayProfiles();					
			break;
		}
	}
	
	static function ajaxSaveNewProfile() {
		global $lang;
		if ( isset($_POST['name']) && isset($_POST['pid']) ) {
	
			if ( !isset($_POST['variables']) )
				self::$messages[] = 'Manjka podatek spremenljivke!';
			if ( !isset($_POST['data']) )
				self::$messages[] = 'Polje respondenti je prazno!';

			// shranimo vrednosti
			$new_name = $_POST['name'];
			$variables = $_POST['variables'];
			$stringInsert = "INSERT INTO srv_respondent_profiles (uid, name, variables) VALUES ('".self::getGlobalUserId()."', '".$new_name."', '".$variables."')";
			$queryInsert = sisplet_query($stringInsert) or die(mysqli_error($GLOBALS['connect_db']));
			$newId = mysqli_insert_id($GLOBALS['connect_db']);
			
			if ($newId > 0) {
				SurveyUserSetting :: getInstance()->saveSettings('default_respondent_profile', $newId);
				self::$currentProfileId = $newId;

				// dodamo še podatke (najprej pobrišemo stare če obstajajo)
				$lines = explode("\n",$_POST['data']);
				if (count($lines)) {
					foreach ( $lines as $line ) {
       					$stringInsert = "INSERT INTO srv_respondents (pid, line) VALUES ('".$newId."', '".$line."')";
       					$queryInsert = sisplet_query($stringInsert) or die(mysqli_error($GLOBALS['connect_db']));
					}
				}
			} else {
				self::$errors[] = $lang['srv_respondents_error_create'];
			}
		} else { 
			self::$errors[] = $lang['srv_respondents_error_data']; 
		}
		// posodobimo podatke
 	}
 	
 	static function ajaxSaveProfile() {

		if (isset($_POST['pid'])) {
			$pid = $_POST['pid'];
			if ($pid == 0) { // imamo profil iz seje
				//popravimo podatke v seji			

				$lines = explode("\n",$_POST['data']);
				$_SESSION['respondent_profile']['lines'] = $lines;

				$_SESSION['respondent_profile']['variables'] = $_POST['variables'];
				
			} else {
				// popravimo podatek za variables v bazi 
				$stringUpdate = "UPDATE srv_respondent_profiles SET variables = '".$_POST['variables']."' WHERE id = '".$pid."'";
				sisplet_query($stringUpdate);
	
				// pobrisemo stare zapise (podatke)
				$stringDelete = "DELETE FROM srv_respondents WHERE pid = '".$pid."'";
				sisplet_query($stringDelete);
				// dodamo še podatke 
				$lines = explode("\n",$_POST['data']);
				if (count($lines)) {
					foreach ( $lines as $line ) {
						$stringInsert = "INSERT INTO srv_respondents (pid, line) VALUES ('".$pid."', '".$line."')";
						$queryInsert = sisplet_query($stringInsert) or die(mysqli_error($GLOBALS['connect_db']));
					}
				}			
			}
		}
 	}
 	
 	static function ajaxRunProfile() {
 		global $admin_type;
 		 	
		SurveyInfo::getInstance()->SurveyInit(self::getSurveyId());
		$db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
			
		// preverimo potrebne sistemske variable
        // tu je lahko vejica
		$variables = explode(",",$_POST['variables']);
		self::checkSystemVariables($variables);

		// poskrbeti moramo za pravilni vrstni red shranjevanja (vrstni red in id-je spremenljivk)
		$cnt=0;
		$spremenlivke = array(); // array kam po vrstnem redu shranimo id-je spremenlivk
		$vrednosti = array(); // poleg IDja spremenljivk potrebujemo tudi ID vrednosti (ker za tip 21 imamo lahko vec text fieldov)
		foreach ($variables as $variabla) {
			// za vsako variablo dobimo id spremenlivke
			if ($variabla != 'pass') {
				$sqlSpremenlivka = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.sistem='1' AND s.variable = '".$variabla."' AND s.gru_id=g.id AND g.ank_id='".self::$surveyId."' ORDER BY g.vrstni_red, s.vrstni_red");
				$rowSpremenlivka = mysqli_fetch_assoc($sqlSpremenlivka);
				$spremenlivke[$cnt] = $rowSpremenlivka['id'];
				$sqlVrednost = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$rowSpremenlivka[id]' ORDER BY vrstni_red ASC LIMIT 1");
				$rowVrednost = mysqli_fetch_array($sqlVrednost);
				$vrednosti[$cnt] = $rowVrednost['id'];
			} else {
				$spremenlivke[$cnt] = -1;
				$vrednosti[$cnt] = -1;
			}
			$cnt++;
		}
                $delimiter = $_POST['recipientsDelimiter'];
                
		$users = mysql_real_unescape_string($_POST['data']);
		// najprej razdelimo vrstice
		$vrstice = explode("\n", $users);
		
		// navadni userji lahko dodajo najvec 20 respondentov
		if ($admin_type <= 1) {
			$count = null;
		} else {
			$sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_user WHERE ank_id='".self::$surveyId."' AND preview='0'");
			$row = mysqli_fetch_array($sql);
			$count = $row['count'];
		}
		
		// shranjujemo v session za progress bar
		session_start();
		$_SESSION['progressBar'][self::$surveyId]['status'] = 'ok';
		$_SESSION['progressBar'][self::$surveyId]['total'] = (int)count($vrstice);
		$_SESSION['progressBar'][self::$surveyId]['current'] = (int)0;
		session_commit();
	
		$progressCounter = 0;
			
		foreach ($vrstice AS $vrstica) {
			
			if ($count === null || $count < 20) {
				if ($vrstica != '') {
					
					// izberemo random hash, ki se ni v bazi
					do {
						$rand = md5(mt_rand(1, mt_getrandmax()) . '@' . $_SERVER['REMOTE_ADDR']);
						$sql = sisplet_query("SELECT id FROM srv_user WHERE SUBSTRING(cookie, 1, 6) = SUBSTRING('".$rand."', 1, 6)");
					} while (mysqli_num_rows($sql) > 0);

					sisplet_query("INSERT INTO srv_user (ank_id, cookie, pass, last_status, time_insert) VALUES ('".self::$surveyId."', '".$rand."', '".substr($rand, 0, 6)."', '0', NOW())");
					$usr_id = mysqli_insert_id($GLOBALS['connect_db']);

					sisplet_query("INSERT INTO srv_userbase (usr_id, tip, datetime, admin_id) VALUES ('".$usr_id."', '0', NOW(), '" . self::$uId . "')");
					sisplet_query("INSERT INTO srv_userstatus (usr_id, tip, status, datetime) VALUES ('".$usr_id."', '0', '0', NOW())");

					// vrstico razbijemo v data, v $i pa mamo stevec in dodajamo po vrsti v while zanki
                                        // pa smo spet pri vejicah ipd.
                                        
					$data = explode($delimiter, $vrstica);

					$i = 0;
					if (count($data) > 0) {
						foreach ( $data as $value ) {
							$value = trim($value);
							if (isset($spremenlivke[$i])) {
								if ($spremenlivke[$i] > 0) {	// sistemska
									if (!isset($value) || $value == "")
										$value = -1;
										sisplet_query("INSERT INTO srv_data_text".$db_table." (spr_id, vre_id, text, usr_id) VALUES ('".$spremenlivke[$i]."', '".$vrednosti[$i]."', '" . trim($value) . "', '".$usr_id."')");							
								} elseif ($spremenlivke[$i] == -1) {	// pass - Geslo
									sisplet_query("UPDATE srv_user SET pass='".strtolower($value)."' WHERE id='$usr_id'");
								}
							}
							$i++;
						}
					}
					
					session_start();
					$_SESSION['progressBar'][self::$surveyId]['current'] = (int)++$progressCounter;
					session_commit();
					
				}
			}
			if ($count !== null) $count++;
		}
		
		session_start();
		$_SESSION['progressBar'][self::$surveyId]['status'] = 'end';
		session_commit();
		
 	}

	static function ajaxRenameProfile() {
		
		$pid = $_POST['pid'];
		$name = $_POST['name'];

		if (isset($pid) && $pid > 0 && isset($name) && $name != "") {
			// popravimo podatek za variables 
			$stringUpdate = "UPDATE srv_respondent_profiles SET name = '".$name."' WHERE id = '".$pid."'";
			sisplet_query($stringUpdate);
			
		} else {
			global $lang;
			die($lang['srv_respondents_error_data']);
		}
	} 	
 	static function ajaxDeleteProfile() {
		$pid = $_POST['pid'];
		if ($pid == 0) {
			// brišemo session (to lahko samo če imamo še druge profile)
			unset( $_SESSION['respondent_profile'] );
		} else if (isset($pid) && $pid > 0) {
	 		// pobrišemo vrednosti 
			$stringDelete = "DELETE FROM srv_respondents WHERE pid = '".$pid."'";
			sisplet_query($stringDelete);	 		

	 		// pobrišemo profil		
			$stringDelete = "DELETE FROM srv_respondent_profiles WHERE id = '".$pid."'";
			sisplet_query($stringDelete);
			
			// nastavimo nov privzet profil
			$stringSelect = "SELECT id FROM  srv_respondent_profiles WHERE uid = '".self::getGlobalUserId()."' ORDER BY id LIMIT 1";
			$querySelect = sisplet_query($stringSelect);
			$rowSelect = mysqli_fetch_assoc($querySelect);
			if ($rowSelect['id'])
				SurveyUserSetting :: getInstance()->saveSettings('default_respondent_profile', $rowSelect['id']);
			else
				SurveyUserSetting :: getInstance()->saveSettings('default_respondent_profile', 0);
		}
 	}	
}
?>
