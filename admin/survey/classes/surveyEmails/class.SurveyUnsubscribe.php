<?php
/**
 * skrbi za odjavo posameznega uporabnik od prejemanja obvestil ankete
 *
 */
class SurveyUnsubscribe {
	private static $sid = null;
	private static $unsubscribed = null;


	function __construct($sid) {
		self::$sid = $sid;
		SurveyInfo::SurveyInit(self::$sid);	
	}

	static function isUnsubscribedEmail($email) {
		
		# če še nismo zakeširamo djavljene emaile za anketo
		if (self::$unsubscribed == null && !is_array(self::$unsubscribed)) {
			self::getUnsubscribedEmails();
		}
		if (is_array(self::$unsubscribed) && count(self::$unsubscribed) > 0 && $email != null && trim($email) != '') {
			return isset(self::$unsubscribed[$email]);
		}
		
		return false;
	}

	static function getUnsubscribedEmails() {
		# polovimo vse odjavljene e-maile in jih shranimo v array
		# preverimo ali je uporabnik že odjavljen
		$emails = array();
		$u1s = "SELECT email FROM srv_survey_unsubscribe WHERE ank_id ='".self::$sid."'";
		$u1q = sisplet_query($u1s);
		while ($u1r = mysqli_fetch_assoc($u1q)) {
			if (trim($u1r['email']) != '') {
				$emails[$u1r['email']] = $u1r['email'];
			}
		}
		# preverimo tabelo srv_invitations_recipients
		$u2s = "SELECT email FROM srv_invitations_recipients WHERE ank_id ='".self::$sid."' AND unsubscribed = '1'";
		if (count($emails) > 0 ) {
			$emails_implode = implode('\', \'', $emails);
			$u2s .= " AND email NOT IN ('".$emails_implode."')";
			//$u2s.=" AND email NOT IN ('".implode('\',\'',$emails)."')";
		}
		$u2q = sisplet_query($u2s);
		while ($u2r = mysqli_fetch_assoc($u2q)) {
			if (trim($u2r['email']) != '') {
				$emails[$u2r['email']] = $u2r['email'];
			}
		}
		
		# polovimo še vse iz srv_user in users
		$u3s = "SELECT user_id, email FROM srv_user WHERE ank_id ='".self::$sid."' AND unsubscribed = '1'";
		$u3q = sisplet_query($u3s);
		$cms_id = array();
		while ($u3r = mysqli_fetch_assoc($u3q)) {
			if (trim($u3r['email']) != '') {
				$emails[$u3r['email']] = $u3r['email'];
			} else {
				#poiščemo še email v tabeli users (če gre za userja iz cms
				if ((int)$u3r['user_id'] > 0) {
					$cms_id[] =  $u3r['user_id'];
				}
			}
		}
		
		if (count($cms_id) > 0) {
			$u4s = "SELECT email FROM users WHERE id IN ('".implode("','",$cms_id)."')";
			$u4q = sisplet_query($u4s);
			while ($u4r = mysqli_fetch_assoc($u4q)) {
				if (trim($u4r['email']) != '') {
					$emails[$u4r['email']] = $u4r['email'];
				}
			}
		}
		
		self::$unsubscribed = $emails;
	}

	function generateCodeForEmail($email) {

		#preverimo ali email že obstaja za to anketo
		$used_codes = array();
		$sql_string = "SELECT code FROM srv_survey_unsubscribe_codes WHERE ank_id = '".self::$sid."' AND email='".$email."'";
		$sql_query = sisplet_query($sql_string);
		if (mysqli_num_rows($sql_query) > 0) {
			$sql_row = mysqli_fetch_assoc($sql_query);
			return $sql_row['code'];
		}


		#polovimo katere kode smo že uporabili za to anketo
		$used_codes = array();
		$sql_string = "SELECT code FROM srv_survey_unsubscribe_codes WHERE ank_id = '".self::$sid."'";
		$sql_query = sisplet_query($sql_string);
		while ($sql_row = mysqli_fetch_assoc($sql_query)) {
			$used_codes[$sql_row['code']] = $sql_row['code'];
		}
		# zgeneriramo kodo za upoirabika
		# Izberemo random hash, ki se ni v bazi
		do {
			list($code,$cookie) = self::generateCode();
		} while (in_array($cookie,$used_codes) && !is_numeric($cookie));

		#vstavimo v tabelo srv_survey_unsubscribe_codes
		$sql_insert = "INSERT INTO srv_survey_unsubscribe_codes (ank_id, email, code) VALUES ( '".self::$sid."', '".$email."', '".$cookie."') ";
		$sqlQuery = sisplet_query($sql_insert);
		
		return $cookie;
	}

	function generateCode() {
		$cookie = md5(mt_rand(1, mt_getrandmax()) . '@' . $_SERVER['REMOTE_ADDR']);

		return array(substr($cookie,0,6), $cookie);
	}

	function doUnsubscribe() {
		global $lang;
		
		$anketa = self::$sid;
		
		$db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
		
		$lang_id = (int)$_GET['language'];
		if ($lang_id != null) $_lang = '_'.$lang_id; else $_lang = '';
		SurveySetting::getInstance()->init($anketa);
		$user_bye_textA = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_user_bye_textA'.$_lang);
		if ($user_bye_textA == '') $user_bye_textA = $lang['user_bye_textA'];
		
		if (isset($_GET['uc']) && trim($_GET['uc']) != '' && isset($_GET['em']) && trim($_GET['em']) != '') {
			# imamo userja iz cms, email smo poslali kodirano
			#dodamo ga v tabelo: srv_survey_unsubscribe
			$uc = trim($_GET['uc']);
			$em = base64_decode($_GET['em']);
			# preverimo obstoj in pravilnost emaila in kode v bazi
				
			$s = "SELECT * FROM srv_survey_unsubscribe_codes WHERE ank_id='".self::$sid."' AND email='".$em."' AND code='".$uc."'";
			$q = sisplet_query($s);
			if (mysqli_num_rows($q) > 0) {
				# zapis je v bazi uporabnika lahko odjavimo
				$si = "INSERT INTO srv_survey_unsubscribe (ank_id, email, unsubscribe_time) VALUES ('".self::$sid."','".$em."',now())";
				$qi = $s = sisplet_query($si);
				
				echo $user_bye_textA;
			} 
			else {
				# zapisa ni v bazi obvestimo uporabnika o napačni kodi
				echo 'Koda je napačna! Ne moremo vas odjaviti od prejemanja obvestil!';
			}
			
			exit();
		} 
		else if ( isset($_GET['email']) && trim($_GET['email']) != '' &&
				    isset($_GET['uid']) && (int)trim($_GET['uid']) > 0) {
			
			$uid = (int)trim($_GET['uid']);
			$email = trim($_GET['email']);
			#poiščemo userja
			#poiščemo id spremenljivke z emailom
			$ssp = "SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$anketa' AND variable = 'email' and sistem ='1'";
			$qsp = sisplet_query($ssp);
			$rsp = mysqli_fetch_assoc($qsp);
			$spid = $rsp['id'];

			#poiščemo email uporabnika
			if ((int)$spid > 0 && (int) $uid > 0) {
				$se = "SELECT count(*) from srv_data_text".$db_table." WHERE spr_id = '$spid' AND usr_id = '$uid' AND text ='$email'";
				$qe = sisplet_query($se);
				list($count) = mysqli_fetch_row($qe);
			}
			
			# če ustreza email in uid, ga odjavimo
			if ((int)$count > 0) {
				if ($email != null && trim($email) != '') {
					$si = "INSERT INTO srv_survey_unsubscribe (ank_id, email, unsubscribe_time) VALUES ('$anketa','$email',now())";
					$qi = $s = sisplet_query($si);
				}
				$s = sisplet_query("UPDATE srv_user SET unsubscribed='1' WHERE id='$uid' AND ank_id='$anketa'");
				if ($s) {
					echo $user_bye_textA;
				} else {
					//echo mysqli_error($GLOBALS['connect_db']);
					echo 'error';
				}
			} else {
				echo 'V bazi ni podaanega emaila.';
			}
				
		} 
		else {
			$code = strtolower( $_GET['code'] );
			$msgOutputed = false;
			if (trim($code) != '' && trim($anketa) != '' && (int)$anketa > 0) {
				# id uporabnika v tabeli srv_user
				$su = "SELECT id FROM srv_user WHERE pass='$code' AND ank_id='$anketa'";
				$qu = sisplet_query($su);
				$ru = mysqli_fetch_assoc($qu);
				$uid = $ru['id'];

				#poiščemo id spremenljivke z emailom
				$ssp = "SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$anketa' AND variable = 'email' and sistem ='1'";
				$qsp = sisplet_query($ssp);
				$rsp = mysqli_fetch_assoc($qsp);
				$spid = $rsp['id'];

				#poiščemo email uporabnika
				if ((int)$spid > 0 && (int) $uid > 0) {
					$se = "SELECT text as email from srv_data_text".$db_table." WHERE spr_id = '$spid' AND usr_id = '$uid'";
					$qe = sisplet_query($se);
					$re = mysqli_fetch_assoc($qe);
					$email = $re['email'];
				}
	
				if ($email != null && trim($email) != '') {
					$si = "INSERT INTO srv_survey_unsubscribe (ank_id, email, unsubscribe_time) VALUES ('$anketa','$email',now())";
					$qi = $s = sisplet_query($si);
				}

				# preverimo ali obstaja koda za nov način pošiljanja sporočil
				$sqlString = "SELECT id, email FROM srv_invitations_recipients WHERE ank_id='$anketa' AND password ='$code' AND unsubscribed='0'";
				$sql_query = sisplet_query($sqlString);
				if (mysqli_num_rows($sql_query) > 0 ) {
					$row = mysqli_fetch_assoc($sql_query);
					if (trim($row['email']) != '') {
						// KAJ TO DELA TUKAJ??
						//$sqlG = sisplet_query("INSERT INTO srv_glasovanje (ank_id, spr_id) VALUES ('$anketa', '$spr_id')");
						$si = "INSERT  INTO srv_survey_unsubscribe (ank_id, email, unsubscribe_time) VALUES ('$anketa','$row[email]',now())";
						$qi = $s = sisplet_query($si);
					}
					$s = sisplet_query("UPDATE srv_invitations_recipients SET unsubscribed='1', date_unsubscribed=NOW() WHERE password='$code' AND ank_id='$anketa'");
					sisplet_query("COMMIT");
					if ($s) {
						echo $user_bye_textA;
						$msgOutputed = true;
					} else {
						//echo mysqli_error($GLOBALS['connect_db']);
						echo 'error1';
					}
	
				} else {
					# preverimo ali je že predhodno odjavljen
					$sqlString = "SELECT id FROM srv_invitations_recipients WHERE ank_id='$anketa' AND password ='$code' AND unsubscribed='1'";
					$sql_query = sisplet_query($sqlString);
					if (mysqli_num_rows($sql_query) > 0 ) {
						echo $lang['user_bye_textC'];
						$msgOutputed = true;
					} else {
						#userja z kodo ni v bazi 
					}
				}

				$s = sisplet_query("UPDATE srv_user SET unsubscribed='1' WHERE (pass='$code' OR SUBSTRING(cookie,1,6) ='$code') AND ank_id='$anketa'");
				if ($s) {
					#tekst je bil poslan že zgoraj!
					if ($msgOutputed == false) {
						echo $user_bye_textA;
					}	
				} else {
					//echo mysqli_error($GLOBALS['connect_db']);
					echo 'error';
				}
			}
		}
	}
}