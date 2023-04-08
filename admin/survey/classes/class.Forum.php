<?php

class Forum {


	var $currentForum;		// Trenutni forum kjer se nahaja user
	var $currentThread;		// Trenutna tema kjer se nahaja user
	var $currentPost;		// Trenutni post kjer se nahaja user

	var $currentForumNice;		// NiceLink trenutnega foruma
	
	var $displayPosts;		// 0 zaporedno, 1 nitno, 2 stavcno
	var $displayColumn;		// 3, 2, 1 - stevilo stolpcev
	var $displayIcons;		// 1 prikaze, 0 ne prikaze
	
	var $sortOrder;			// ASC, DESC - za sortiranje tem
	var $sortPostOrder;	// ASC, DESC - za sorstiranje sporocil
	var $sortLimit;			// no, 1d, 1w, 2w, 1m, 3m, 6m, 1y - omejitev izpisa tem
	
	// Spremenljivki za komentarje novic, rubrik, baze..
	var $tableID;			// ID stvari, ki se jo komentira v tabeli new
	var $columnID;			// ID zapisa v tej tabeli, na katerega komentiramo
	
	// Stevilo tem in postov na eni strani
	var $threadBreak;
	var $postBreak;

	var $uid;
	

	function __construct ($f=0, $t=0, $p=0) {
		$this->currentForum = $f;
		$this->currentThread = $t;
		$this->currentPost = $p;

		$this->uid = $this->uid();

		$r = sisplet_query ("SELECT NiceLink FROM forum WHERE id='" .$this->currentForum ."'");
		$rr = mysqli_fetch_row ($r);
		$this->currentForumNice = $rr[0];

		if (isset($_COOKIE['DP'])) {
			$this->displayPosts = $_COOKIE['DP'];
		} else {
			$this->displayPosts = 0;	// default izpisujemo zaporedno
		}
		if (isset($_COOKIE['DC'])) {
			$this->displayColumn = $_COOKIE['DC'];	// po novem pa se default vrednost nastavi v adminu (in se shranjena v bazi)
		} else {
			$this->displayColumn = null;
		}
		if (isset($_COOKIE['DI'])) {
			$this->displayIcons = $_COOKIE['DI'];
		} else {
			$this->displayIcons = 1;	// default prikazujemo ikonce
		}
		if (isset($_COOKIE['SO'])) {
			if ($_COOKIE['SO'] == 1) {
				$this->sortOrder = 'ASC';
			} else {
				$this->sortOrder = 'DESC';
			}
		} else {
			$this->sortOrder = 'DESC';
		}
		if (isset($_COOKIE['SPO'])) {
			if ($_COOKIE['SPO'] == 1) {
				$this->sortPostOrder = 'DESC';
			} else {
				$this->sortPostOrder = 'ASC';
			}
		} else {
			$this->sortPostOrder = 'ASC';
		}
		if (isset($_COOKIE['SL'])) {
			$this->sortLimit = $_COOKIE['SL'];
		} else {
			$this->sortLimit = 'no';
		}
		
		$this->tableName = '';
		$this->tableId = 0;
		
	}
	

	// Nastavi trenutni forum
	function setForum($f) {
		$this->currentForum = $f;
	}
	
	// Nastavi trenutno temo
	function setThread($t) {
		$this->currentThread = $t;
	}
	
	// Nastavi trenutno sporocilo
	function setPost($p) {
		$this->currentPost = $p;
	}
	
	// Nastavi tableName
	function setTableID ($t) {
		$this->tableID = $t;
	}
	
	// Nastavi tableId
	function setColumnID ($i) {
		$this->columnID = $i;
	}
	
			
	// Vnese post v bazo (in postori vse ostale potrebne stvari ob dodajanju novega sporocila :) )
	function addPost ($avtor, $naslov, $vsebina, $new=0, $id=0, $timeDelay=0, $mail_alert=true) {
		global $site_url;
		global $skin_name;
		global $lang;
		global $admin_type;
		global $pass_salt;
		global $global_user_id;
		global $cookie_domain;

		// preden naredimo karkoli, odstranimo javascript iz vsebine in naslova:
		// Enako za meta redirect
		$vsebina = preg_replace ('/\<script(.*?)\/script>/i', "", $vsebina);
		$naslov = preg_replace ('/\<script(.*?)\/script>/i', "", $naslov);
		$vsebina = preg_replace ('/\<meta(.*?)\>/i', "", $vsebina);
		$naslov = preg_replace ('/\<meta(.*?)\>/i', "", $naslov);

		// praznih sporocil ne jemljemo.	
		if (strlen ($vsebina)>0) {

			$IP = $_SERVER['REMOTE_ADDR'];
			$parent = 0;

			if ($this->currentPost > 0) {
				$parent = $this->currentPost;
			} elseif ($this->currentThread > 0) {
				$parent = $this->currentThread;
			}

			if ($global_user_id > 0) {
				$uid = $global_user_id;
				$resu = sisplet_query ("SELECT name FROM users WHERE id='" .$uid ."'");
				$ru = mysqli_fetch_row ($resu);
				$user = $ru[0];
			} else {
				$uid = 0;
				$user = $avtor;
			}

			if ($admin_type == -1 || $global_user_id==0) {
				if (isset($_POST['SessID']) && isset($_POST['prepis'])) {
					// Prepis kode
					$handle = $_POST['SessID'];
					$resultCD = sisplet_query ("SELECT code FROM registers WHERE handle='$handle'");
					$sqlCD = mysqli_fetch_row($resultCD);
	
					if (strtolower ($_POST['prepis']) != strtolower ($sqlCD[0]) || mysqli_num_rows($resultCD)==0)
					die($lang['nu_regp_pict'] .'<br><br><a href="' .$site_url .'">' .$lang['home'] .'</a>');
	
					$vsebina = nl2br($vsebina);	// neprijavljeni userji nimajo editorja in se ne nardijo <br>
				} else 
				die($lang['nu_regp_pict'] .'<br><br><a href="' .$site_url .'">' .$lang['home'] .'</a>');
			}

			if ($new != 0 && $id != 0) {

				if ($new > 10) {		// baza
					$t = $this->getTable($new);
					$_id = 'id';

					$sql1 = sisplet_query("SELECT naslov FROM $t WHERE $_id = '$id'");
					$row1 = mysqli_fetch_row($sql1);
					$vsebina = $lang['news_comment_txt'].' <a href="'.$site_url.'index.php?fl=2&amp;lact=1&amp;bid='.$id.'">'.$row1[0].'</a>';
				} elseif ($new == '-1') {	// navigacija
					$vsebina = $lang['news_comment_txt'].' <a href="'.$site_url.'index.php?fl=1&amp;nt=9&amp;sid='.$id.'">'.$naslov.'</a>';
				} else {			// novice
					$t = $this->getTable($new);
					$_id = 'sid';

					$sql1 = sisplet_query("SELECT naslov, vsebina FROM $t WHERE $_id = '$id'");
					$row1 = mysqli_fetch_row($sql1);

					$f = 'index.php?fl=1&amp;nt='.$new;
					$vsebina = $lang['news_comment_txt'].' <a href="'.$site_url.''.$f.'&amp;sid='.$id.'">'.$row1[0].'</a>:<br /><br />'.skrajsaj(trim(strip_tags($row1[1])), 200);
				}
			}

			// obvescanje na mail - nov (neprijavljen) user se hoce narocit
			// Sem premaknil gor, da spremenimo ime avtorja preden dodamo post! --may
			if (isset($_POST['alertmail'])) {

				if ($_POST['alertmail'] != '') {
					$mail = $_POST['alertmail'];
					$sqla = sisplet_query("SELECT id FROM users WHERE email = '$mail'");

					if (mysqli_num_rows($sqla) > 0) {
						$rowa = mysqli_fetch_row($sqla);
						$narocnikID = $rowa[0];
					} else {

						// Preveri ali je vzdevek ze zaseden- ce je, mu dodaj neko stevilko da bo unique
						$a2 = $avtor;

						$result = sisplet_query ("SELECT * FROM users WHERE name='$a2' AND surname=''");
						while (mysqli_num_rows ($result) > 0) {
							$a2 = $avtor .rand(0, 32767);
							$result = sisplet_query ("SELECT * FROM users WHERE name='$a2' AND surname=''");
						}

						$avtor = $a2;
						$g = base64_encode((hash('SHA256', '' .$pass_salt)));

						$sqln = sisplet_query("INSERT INTO users (email, name, when_reg, came_from, pass) VALUES ('" .$_POST['alertmail'] ."', '$avtor', NOW(), '2', '$g')");
						$narocnikID = mysqli_insert_id($GLOBALS['connect_db']);

						// Ker je noviregistriran mu dajmo se UID.
						$uid = $narocnikID;
						$user = "";
					}

					setcookie("uid", base64_encode ($mail), time()+3600*24*365, "/", $cookie_domain);
					setcookie("secret", base64_encode((hash('SHA256', '' .$pass_salt))), time()+3600*24*365, "/", $cookie_domain);

					$sqlaa = sisplet_query("INSERT INTO obvescanje_tema (uid, tid) VALUES ('" .$narocnikID ."', '" .$this->currentThread ."')");
					$sqlaa = sisplet_query("UPDATE post SET uid='" .$narocnikID ."', user='' WHERE id='" .$this->currentPost ."'");
				}
			}

			$admin = $_POST['admin'];
			if (isset ($_POST['admin_override']) && $_POST['admin_override'] == "1") $admin = 0;

			if (!isset ($_POST['admin'])) $admin = 3;
			if (isset ($_POST['sporocilo']) && !($new != 0 && $id != 0)) $admin = $_POST['sporocilo'];

			// preveri ce moras nastaviti dispauth
			$la = sisplet_query ("SELECT lockedauth FROM forum WHERE id='" .$this->currentForum ."' AND lockedauth=1");
			if (!($new != 0 && $id != 0) && (mysqli_num_rows ($la)>0 || (isset ($_POST['dispauth']) && $_POST['dispauth']=="1"))) {$dispauth=1; $dispthread=1;}

			else {$dispauth=0; $dispthread=0;}

			$vsebina = str_replace ("'", "`", $vsebina);
			$sql = sisplet_query("INSERT INTO post (fid, tid, parent, naslov, vsebina, uid, user, time, admin, IP, dispauth, dispthread) VALUES ('".$this->currentForum."', '".$this->currentThread."', '$parent', '$naslov', '$vsebina', '$uid', '$user', NOW() - INTERVAL $timeDelay SECOND, '$admin', '$IP', '$dispauth', '$dispthread')");
			if (!$sql) $error = mysqli_error($GLOBALS['connect_db']);
			$ittdd = mysqli_insert_id($GLOBALS['connect_db']);			// tale ID je pomemben na koncu, ker se ga returna na koncu funkcije !

			// Ce je to nova tema, potem naredi link.
			if ($parent == 0) {
				$fnl = sisplet_query ("SELECT NiceLink FROM forum WHERE id='" .$this->currentForum ."'");
				$fnlr = mysqli_fetch_row ($fnl);
				$flink = preg_replace ("/(.*?[^\/])\/\/(.*?[^\/])\/(.*?[^\/])\/(.*?[^\/])\/(.*)/i", "$5", $rnlr[0]);

				sisplet_query ("UPDATE post SET NiceLink = '" .$site_url .'thread/' .$this->currentForum .'/' .$ittdd .'/' .$flink .'/' .$naslov .'/' ."' WHERE id='" .$ittdd ."'");
			}


			// dodaj v index...
			$this->setPost($ittdd);

			$id = mysqli_insert_id($GLOBALS['connect_db']);
            
			if ($this->currentThread == 0) {	
				$u = sisplet_query("UPDATE post SET tid='$ittdd' WHERE id='$ittdd'");
				$this->setThread($ittdd);
			}

			$sql = sisplet_query("UPDATE post SET time2=NOW() WHERE id='".$this->currentThread."'");

			
			// obvescanje na mail
			if (isset($_POST['alert'])) {
				$sqlaa = sisplet_query("SELECT * FROM obvescanje_tema WHERE uid='" .$global_user_id ."' AND tid='" .$this->currentThread ."'");
				if (mysqli_num_rows($sqlaa) == 0) {
					$sqla = sisplet_query("INSERT INTO obvescanje_tema (uid, tid) VALUES ('" .$global_user_id ."', '" .$this->currentThread ."')");
				}
			} else {
				$sqlaa = sisplet_query("DELETE FROM obvescanje_tema WHERE uid='" .$global_user_id ."' AND tid='" .$this->currentThread ."'");
			}

			// hendlanje skupin - GROUP
			if (isset($_POST['group']) && $_POST['group']!='') {

				$group = $_POST['group'];
				$mails = explode("\n", $group);

				foreach ($mails as $key => $mail) {
					$mail = trim($mail);
					$sqla = sisplet_query("SELECT id FROM users WHERE email = '$mail'");

					if (mysqli_num_rows($sqla) > 0) {
						$rowa = mysqli_fetch_row($sqla);
						$narocnikID = $rowa[0];
					} else {
						$g = base64_encode((hash('SHA256', '' .$pass_salt)));
						$sqln = sisplet_query("INSERT INTO users (email, name, when_reg, camefrom, pass) VALUES ('$mail', '$mail', NOW(), '2', '$g')");
						$narocnikID = mysqli_insert_id($GLOBALS['connect_db']);
					}

					$sqlaa = sisplet_query("INSERT INTO obvescanje_tema (uid, tid) VALUES ('$narocnikID', '" .$this->currentThread ."')");
					$sqlaa = sisplet_query("INSERT INTO forum_group (uid, tid) VALUES ('$narocnikID', '" .$this->currentThread ."')");
				}
				$sqlaa = sisplet_query("INSERT INTO forum_group (uid, tid) VALUES ('" .$global_user_id ."', '" .$this->currentThread ."')");
			}

			if (isset($_GET['table'])) {
				$this->setTableID($_GET['table']);
				if (isset($_GET['column'])) {
					$this->setColumnID($_GET['column']);
				}

				$t = $this->getTable($this->tableID);
				$sqlc = sisplet_query("UPDATE $t SET thread='".$this->currentThread."' WHERE ".($this->tableID<=10?'s':'')."id='".$this->columnID."' AND thread='0'");
			}
			if (!isset($_GET['table']) && isset($_GET['column'])) {
				$sqlc = sisplet_query("UPDATE menu SET thread='" .$this->currentThread ."' WHERE id='" .$_GET['column'] ."'");
			}
			$user_id = $global_user_id;
			
			if ($mail_alert)
				include('alert.php');
		}
        
        return $ittdd;
	}

	
	// Vrne tabelo glede na id v tabeli new (baze imajo id v tabeli new svoj_ID+10)
	function getTable($new) {
		switch ($new) {
			case 9:	$t = 'novice'; break;
			case 3:	$t = 'aktualno'; break;
			case 4:	$t = 'faq'; break;
			case 10: $t = 'mailnovice'; break;
			case 2:	$t = 'vodic'; break;
			case 5:	$t = 'rubrika1'; break;
			case 6:	$t = 'rubrika2'; break;
			case 7:	$t = 'rubrika3'; break;
			case 8:	$t = 'rubrika4'; break;
		}
		if ($new > 10) {
			$t = 'data_baze';
		}
		return $t;
	}

	// Vrne ID trenutnega uporabnika (ce ni prijavljen vrne 0)
	function uid () {
		global $mysql_database_name;
		global $global_user_id;
		global $admin_type;
		global $lang;

		if (isset ($_GET['em'])) {		// email iz alerta
			$result = sisplet_query ("SELECT id FROM users WHERE email='" .$_GET['em'] ."'");
			$r = mysqli_fetch_row ($result);
			return $r[0];
 		}
		else	{
			return $global_user_id;
		}
	}

	// Vrne userja
	function user ($uid, $link=0, $user='') {
		global $lang;
		global $site_url;
		global $skin_name;

		if ($uid > 0) {
			$sql = sisplet_query("SELECT email, name, show_email FROM users WHERE id='$uid'");
			$row = mysqli_fetch_row($sql);
			$return = '';

			if ($link == 1) $return .= '<a href="'.$site_url.'forums/?lact=2&amp;uid='.$uid.'">';
			if ($row[1] != '') {
				$return .= $row[1];
			} elseif ($row[2] == 2) {
				$return .= $row[0];
			} else {
				$return .= $lang['user2'];
			}
			if ($link == 1) $return .= '</a>';
			return $return;
		} elseif ($user != '') {
			return $user;
		} else {
			return $lang['guest'];
		}
	}
	
	
	function inicialke ($ime) {

		$out = '';

		$ime = strtoupper($ime);
		$b = explode(' ', $ime);
		foreach ($b AS $beseda) {
			$out .= $beseda[0];
		}

		return $out;
	}	
	
	// Polepsa izpis datuma in ure
	function datetime($time) {
		global $admin_type;
		
		$sql = sisplet_query("SELECT value FROM misc WHERE what='ForumHourDisplay'");
		$row = mysqli_fetch_row($sql);

		// Funkcija se klice zelooooo pogosto, zato sem vrgel ven substr in sestavljam rocno, je hitreje.
		if ($row[0] == 0 || $admin_type==0)
			return $time[8] .$time[9] ."." .$time[5] .$time[6] ."." .$time[0] .$time[1] .$time[2] .$time[3] ." " .$time[11] .$time[12] .":" .$time[14] .$time[15];
		else 
			return $time[8] .$time[9] ."." .$time[5] .$time[6] ."." .$time[0] .$time[1] .$time[2] .$time[3];
	}
	
	// Polepsa izpis datuma in ure
	function datetime1($time) {
		global $admin_type;
		
		$sql = sisplet_query("SELECT value FROM misc WHERE what='ForumHourDisplay'");
		$row = mysqli_fetch_row($sql);
		if ($row[0] == 0 || $admin_type==0)
			return $time[8] .$time[9] ."." .$time[5] .$time[6]  ." " .$time[11] .$time[12] .":" .$time[14] .$time[15];
		else
			return $time[8] .$time[9] ."." .$time[5] .$time[6];
	}
	
	// Polepsa izpis datuma (brez leta
	function date1($time) {
		return  $time[8] .$time[9] ."." .$time[5] .$time[6];
	}
	
}



?>
