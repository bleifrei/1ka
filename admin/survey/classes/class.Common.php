<?php

/**
 * pogoste funkcije iz SurveyAdmin
 * da se klice raje tale mali file, namesto tisti gromozanski
 */
class Common {

	static private $instance;

	static private $anketa;

	static private $db_table = '';

	static private $time_start = null;	// pri stetju casa izvajanja, sem shranimo zacetek izvajanja

	static private $updateEditStamp = false;	// ali ob koncu posodobimo time stamp ankete

	// konstrutor
	protected function __construct() {}
	// kloniranje
	final private function __clone() {}

	/**
	 * Poskrbimo za samo eno instanco
	 */
	static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new Common();
		}
		return self::$instance;
	}

	/**
	 * Inicializacija
	 *
	 * @param int $anketa
	 */
	static function Init( $anketa = null )
	{
		if ($anketa) {
			self::$anketa = $anketa;
			SurveyInfo::getInstance()->SurveyInit(self::$anketa);
			if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
				self::$db_table = '_active';

		}
	}

	/**
	 * @desc prestevilci variable vseh vprasanj v anketi
	 */
	static function prestevilci ($spremenljivka = 0, $all = false, $force = false) {

		// Preverimo ce imamo izklopljeno atomatsko prestevilcevanje
        SurveySetting::getInstance()->Init(self::$anketa);
        
		$enumerate = SurveySetting::getInstance()->getSurveyMiscSetting('enumerate'); if ($enumerate == '') $enumerate = 1;
		if($enumerate == 0 && $all != true)
			return;

		// prestevilcimo spremenljivke (vse v anketi)
		if ($spremenljivka == 0) {
			self::prestevilci_if();

			$i = 1;

			$sql = sisplet_query("SELECT s.id, variable, variable_custom, s.tip AS tip FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='".self::$anketa."' AND s.tip!='9' ORDER BY g.vrstni_red, s.vrstni_red");

			// ce je vec kot 50 spremenljivk nimamo avtomatskega prestevilcevanja
			if ( ( mysqli_num_rows($sql) <= 50 or $all == true ) && mysqli_num_rows($sql)>0 ) {

				$values = "";
				$val_array = array();

				$variable_array = array();

				$j=1;
				while ($row = mysqli_fetch_array($sql)) {
					$variable_array[$row['id']]['id'] = $row['id'];
					$variable_array[$row['id']]['variable'] = $row['variable'];
					$variable_array[$row['id']]['variable_custom'] = $row['variable_custom'];
					$variable_array[$row['id']]['vrstni_red'] = $j;

					// Dodaten loop po spremenljivkah, ki so znotraj kombinirane tabele (gru_id == -2)
					if($row['tip'] == 24){

						$sqlM2 = sisplet_query("SELECT s.id, s.variable, s.variable_custom FROM srv_spremenljivka s, srv_grid_multiple m 
												WHERE m.parent='".$row['id']."' AND m.ank_id='".self::$anketa."' AND s.id=m.spr_id
												ORDER BY m.vrstni_red");
						while ($rowM2 = mysqli_fetch_array($sqlM2)) {

							$j++;

							$variable_array[$rowM2['id']]['id'] = $rowM2['id'];
							$variable_array[$rowM2['id']]['variable'] = $rowM2['variable'];
							$variable_array[$rowM2['id']]['variable_custom'] = $rowM2['variable_custom'];
							$variable_array[$rowM2['id']]['vrstni_red'] = $j;
						}
					}

					$j++;
				}

				$variable_array2 = $variable_array;

				// Loop cez vsa vprasanja v anketi po vrstnem redu
				foreach($variable_array2 as $row) {

					// Ce vprasanje nima custom variable jo lahko prestevilcimo
					if ($row['variable_custom'] == 0) {

						do {
							// Nastavimo variablo na stevilko ki pripada njenemu vrstnemu redu
							$variable = 'Q' . $i;
							$variable_ok = true;
							$i++;

							// Loop cez vsa ostala vprasanja, kjer preverimo ce obstaja se kaksna z istim imenom (ki je custom ali pa ima manjsi vrstni red)
							foreach ($variable_array AS $spr => $var) {
								if ($spr != $row['id'] && $var['variable'] == $variable && ($var['variable_custom'] == 1 || $i > $var['vrstni_red'])) {
									$variable_ok = false;
								}
							}
						} while ( ! $variable_ok );

						// Nasli smo ustrezno poimenovanje in ga updatamo
						if ($row['variable'] != $variable) {
							$variable_array[$row['id']]['variable'] = $variable;
							sisplet_query("UPDATE srv_spremenljivka SET variable = '$variable' WHERE id = '$row[id]'");
							if ($values != "") $values .= ", ";
							$values .= "('$row[id]', '$variable')";
						}
					}

					// po vrsti shranjujemo vprasanja v array, ker moramo na koncu se prestevilciti njihove notranje variable
					array_push($val_array, $row['id']);
				}

				// prestevilcimo se variable znotraj posameznih vprasanja - prestevilci moramo poklicati sele za zgornjim INSERTom!
				foreach ($val_array AS $key => $val) {
					self::prestevilci($val);
				}
			}
		}
		// prestevilcimo variable v spremenljivki
		else {

			// Nastavitev da ostevilcujemo v obratnem vrstnem redu (samo radio tip)
			$rowSpr = Cache::srv_spremenljivka($spremenljivka);	
			$spremenljivkaParams = new enkaParameters($rowSpr['params']);
			$reverse_var = ($spremenljivkaParams->get('reverse_var') ? $spremenljivkaParams->get('reverse_var') : 0);

			//sisplet_query("BEGIN");
			$j = 1;
			// Ce je katerakoli variabla custom, potem ne prestevilcimo vec
			$sql = sisplet_query("SELECT v.id AS id, s.tip AS tip, s.variable AS variable, v.other as other, v.variable AS variable_vre
			                    FROM srv_vrednost v, srv_spremenljivka s
			                    WHERE
			                    v.spr_id = s.id AND
			                    v.spr_id = '$spremenljivka' AND
			                    v.variable_custom = '0' AND
			                    v.vrstni_red > '0' AND
								NOT EXISTS (SELECT v2.* from srv_vrednost v2 WHERE v2.spr_id=v.spr_id AND v2.vrstni_red>'0' AND v2.variable_custom='1')
			                    ORDER BY v.vrstni_red ".($reverse_var == 1 ? ' DESC' : ' ASC')."
			                    ");

			$values = "";
			$tip = 0;
			while ($row = mysqli_fetch_array($sql)) {
				if ($row['other'] == 0 || $row['other'] == 1) { # missing value ne preštevilčimo 
					//do {
					// Popravimo imena spremenljivk pri tabelah (zdruzimo ime variable + abeceda)
					//if ($row['tip'] == 2 || $row['tip'] == 6 || $row['tip'] == 7 || $row['tip'] == 16 || $row['tip'] == 17 || $row['tip'] == 18  || $row['tip'] == 19 || $row['tip'] == 20 || $row['tip'] == 21 || $row['tip'] == 24) {
					if ($row['tip'] == 2 || $row['tip'] == 6 || $row['tip'] == 7 || $row['tip'] == 16 || $row['tip'] == 17 || $row['tip'] == 18  || $row['tip'] == 19 || $row['tip'] == 20 || $row['tip'] == 21 || $row['tip'] == 24 || $row['tip'] == 26 || $row['tip'] == 27) {
						if (mysqli_num_rows($sql) <= 26) {
							$variable_vre = $row['variable'] . chr($j +96);
						} else {
							$jjj = bcdiv($j-1, 26) + 1;
							$jj = bcmod($j-1, 26) + 1; if ($jj == 0) { $jj=26; $jjj--; };
							$variable_vre = $row['variable'] . chr($jjj +96) . chr($jj +96);
						}
					} else {
						$variable_vre = $j;
					}
					//$sqlCheckVariable = sisplet_query("SELECT id FROM srv_vrednost WHERE variable='$variable_vre' AND spr_id = '$spremenljivka' AND id <> '$row[id]'");
					$j++;
					//} while (mysqli_num_rows($sqlCheckVariable) > 0);

					if ($row['variable_vre'] != $variable_vre) {
						sisplet_query("UPDATE srv_vrednost SET variable='$variable_vre' WHERE id = '$row[id]'");
						if ($values != "") $values .= ", ";
						$values .= "('$row[id]', '$variable_vre')";
					}
				}
			}

			//sisplet_query("COMMIT");
		}
	}

	/**
	 * @desc prestevilci ife
	 */
	static $prestevilci_if = '';
	static function prestevilci_if($parent = 0, & $number = 1) {

		if ($parent == 0)
			self::$prestevilci_if = '';

		$sql = sisplet_query("SELECT element_if FROM srv_branching WHERE ank_id='".self::$anketa."' AND parent='$parent' AND element_if>'0' ORDER BY vrstni_red");
		while ($row = mysqli_fetch_array($sql)) {

			if (self::$prestevilci_if != "") self::$prestevilci_if .= ", ";
			self::$prestevilci_if .= "('$row[element_if]', '$number')";

			$number++;
			self::prestevilci_if($row['element_if'], $number);
		}

		if ($parent == 0 && self::$prestevilci_if != "") {
			$s = sisplet_query("INSERT INTO srv_if (id, number) VALUES ".self::$prestevilci_if." ON DUPLICATE KEY UPDATE number = VALUES (number)");
			if (!$s) echo 'e345'.mysqli_error($GLOBALS['connect_db']);
		}
	}


	/**
	 * pobrise podatke, ki se izpolnijo v preview modu ankete
	 *
	 * @param boolean vedno brišemo samo iz trenutne ankete zaradi brisanja iz podatkovne datoteke
	 */
	static function deletePreviewData ($sid = null) {

		if ($sid == null || (int)$sid == 0)
			return;
		else
			$sql = sisplet_query("SELECT id FROM srv_user WHERE preview='1' AND time_edit < NOW() - INTERVAL 3 HOUR AND ank_id = '".$sid."'");

		# polovimo vrsto tabel za to anketo
		$strDbTable = "SELECT db_table FROM srv_anketa WHERE id = $sid";
		$qryDbTable = sisplet_query($strDbTable);
		list($db_table) = mysqli_fetch_row($qryDbTable);

		$list = '';
		// se je dogajalo da je 0 ... pa se je pojavljal mysql_fetch error
		if (mysqli_num_rows($sql) > 0) {
			$prefix = '';
			while ($row = mysqli_fetch_array($sql)) {
				$list = $list.$prefix.$row['id'];
				$prefix = ', ';
			}
		}
		if ($list != '') {
			sisplet_query("BEGIN");
			// tabela z respondenti
			$deleted = sisplet_query("DELETE FROM srv_user WHERE preview='1' AND id IN ($list) AND ank_id = '$sid'");

			// tabele s podatki
			sisplet_query("DELETE FROM srv_data_checkgrid".$db_table." WHERE usr_id IN ($list)");
			sisplet_query("DELETE FROM srv_data_glasovanje WHERE usr_id IN ($list)");
			sisplet_query("DELETE FROM srv_data_grid".$db_table." WHERE usr_id IN ($list)");
			sisplet_query("DELETE FROM srv_data_imena WHERE usr_id IN ($list)");
			sisplet_query("DELETE FROM srv_data_number WHERE usr_id IN ($list)");
			sisplet_query("DELETE FROM srv_data_rating WHERE usr_id IN ($list)");
			sisplet_query("DELETE FROM srv_data_text".$db_table." WHERE usr_id IN ($list)");
			sisplet_query("DELETE FROM srv_data_textgrid".$db_table." WHERE usr_id IN ($list)");
			sisplet_query("DELETE FROM srv_data_vrednost".$db_table." WHERE usr_id IN ($list)");
			sisplet_query("DELETE FROM srv_userstatus WHERE usr_id IN ($list)");
			sisplet_query("DELETE FROM srv_user_grupa".$db_table." WHERE usr_id IN ($list)");

			sisplet_query("COMMIT");
		}
	}

	/**
	 * ob zacetku izvajanja shranimo cas izvajanja
	 *
	 */
	static function start () {
		self::$time_start = microtime(true);
	}

	/**
	 * ob koncu izvajanja izracunamo cas izvajanja in pozenemo updateEditStampSave(), ce je treba
	 *
	 */
	static function stop () {

		// racunanje casa izvedemo v updateTracking(), ki se poklice iz updateEditStampSave()	
		if ( self::$updateEditStamp == 1 ) {

			self::updateEditStampSave();
		}
	}

	/**
	 * oznacimo, da na koncu izvajanja popravimo timestamp ankete
	 *
	 */
	static function updateEditStamp () {
		self::$updateEditStamp = true;
	}

	/**
	 * @desc popravimo cas in userja popravka
	 */
	static function updateEditStampSave() {
		global $admin_type;
		global $global_user_id;

		if (isset ($_REQUEST['spremenljivka']))
			$spremenljivka = $_REQUEST['spremenljivka'];
		if (isset ($_REQUEST['anketa']))
			$anketa = $_REQUEST['anketa'];
		if (isset ($_REQUEST['grupa']))
			$grupa = $_REQUEST['grupa'];

		if (!$anketa > 0) {
			if ($grupa > 0) {
				$sql = sisplet_query("SELECT ank_id FROM srv_grupa WHERE id='$grupa'");
				$row = mysqli_fetch_array($sql);
				$anketa = $row['ank_id'];
			}
			elseif ($spremenljivka > 0) {
				$sql = sisplet_query("SELECT gru_id FROM srv_spremenljivka WHERE id='$spremenljivka'");
				$row = mysqli_fetch_array($sql);
				$grupa = $row['gru_id'];
				$sql = sisplet_query("SELECT ank_id FROM srv_grupa WHERE id='$grupa'");
				$row = mysqli_fetch_array($sql);
				$anketa = $row['ank_id'];
			}
			//Uros dodal, za API
			elseif(isset(self::$anketa) && self::$anketa > 0)
				$anketa = self::$anketa;
		}

		if ($anketa > 0) {
			$update = true;
			if ($admin_type == 0) {
				$sql = sisplet_query("SELECT * FROM srv_dostop WHERE ank_id='$anketa' AND uid='$global_user_id'");
				if (mysqli_num_rows($sql) == 0)
					$update = false;
			}

			if ($update) {
				$sql = sisplet_query("UPDATE srv_anketa SET edit_uid = '$global_user_id', edit_time=NOW() WHERE id='$anketa'");

				# popravimo še polje za osvežitev podatkov seznama anket uporablja class: class.SurveyList.php
				$updateString = "INSERT INTO srv_survey_list (id, updated) "
					." VALUES ('$anketa', '1') ON DUPLICATE KEY UPDATE updated='1'";
				$s = sisplet_query($updateString);
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);

				// vsilimo refresh podatkov
				SurveyInfo :: getInstance()->resetSurveyData();
			}

			# vsilimo refresh header datoteke in datoteke s podatki
			$update_header = "UPDATE srv_data_files SET head_file_time='0000-00-00', data_file_time='0000-00-00' WHERE sid='$anketa'";
			sisplet_query($update_header);
			sisplet_query("COMMIT");
		}
	}


	/**
	 * @desc Vrne ID trenutnega uporabnika (ce ni prijavljen vrne 0)
	 */
	static function uid() {
		global $global_user_id;

		return $global_user_id;
	}

	/**
	 * @desc popravi celotno anketo
	 */
	static function repareAnketa($anketa = 0) {
		if ($anketa == 0)
			$anketa = self::$anketa;

		if($anketa > 0){
			self::repareGrupa($anketa);

			$sql = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='".$anketa."'");
			while ($row = mysqli_fetch_array($sql)) {
				self::repareSpremenljivka($row['id']);

				$sql1 = sisplet_query("SELECT id FROM srv_spremenljivka WHERE gru_id='$row[id]'");
				while ($row1 = mysqli_fetch_array($sql1)) {
					self::repareVrednost($row1['id']);
				}
			}
		}
	}

	/**
	 * @desc popravi vrstni red v tabeli srv_grupa
	 */
	static function repareGrupa($anketa) {

		if($anketa > 0){
			sisplet_query("BEGIN");

			$sql = sisplet_query("SELECT id, vrstni_red FROM srv_grupa WHERE ank_id='$anketa' ORDER BY vrstni_red");
			$i = 1;

			$values = "";

			while ($row = mysqli_fetch_array($sql)) {
				if ($row['vrstni_red'] != $i) {
					$sql1 = sisplet_query("UPDATE srv_grupa SET vrstni_red='$i' WHERE id = '$row[id]'");
					if ($values != "") $values .= ", ";
					$values .= "('$row[id]', '$i')";
				}
				$i++;
			}

			//if ($values != "") sisplet_query("INSERT INTO srv_grupa (id, vrstni_red) VALUES $values ON DUPLICATE KEY UPDATE vrstni_red=VALUES(vrstni_red)");
			sisplet_query("COMMIT");
		}
	}

	/**
	 * @desc popravi vrstni red v tabeli srv_spremenljivka
	 */
	static function repareSpremenljivka($grupa) {

		if($grupa > 0){
			sisplet_query("BEGIN");
			$sql = sisplet_query("SELECT id, vrstni_red FROM srv_spremenljivka WHERE gru_id='$grupa' ORDER BY vrstni_red");
			$i = 1;

			$values = "";

			while ($row = mysqli_fetch_array($sql)) {
				if ($row['vrstni_red'] != $i) {
					$sql1 = sisplet_query("UPDATE srv_spremenljivka SET vrstni_red='$i' WHERE id = '$row[id]'");
					if ($values != "") $values .= ", ";
					$values .= "('$row[id]', '$i')";
				}
				$i++;
			}

			//if ($values != "") sisplet_query("INSERT INTO srv_spremenljivka (id, vrstni_red) VALUES $values ON DUPLICATE KEY UPDATE vrstni_red=VALUES(vrstni_red)");
			sisplet_query("COMMIT");
		}
	}

	/**
	 * @desc popravi vrstni red v tabeli srv_vrednost
	 */
	static function repareVrednost($spremenljivka) {

		if($spremenljivka > 0){
			sisplet_query("BEGIN");

			$sql = sisplet_query("SELECT id, vrstni_red FROM srv_vrednost WHERE spr_id='$spremenljivka' AND vrstni_red>0 AND other <= 1 ORDER BY vrstni_red ASC");
			$i = 1;
			while ($row = mysqli_fetch_array($sql)) {
				if ($row['vrstni_red'] != $i) {
					$sql1 = sisplet_query("UPDATE srv_vrednost SET vrstni_red='$i' WHERE id = '$row[id]'");
				}
				$i++;
			}

			//tole je za opcijo drugo (kjer gre vrstni_red v minus)
			$sql = sisplet_query("SELECT id, vrstni_red FROM srv_vrednost WHERE spr_id='$spremenljivka' AND vrstni_red<0 AND other <= 1 ORDER BY vrstni_red DESC");
			$i = -1;
			while ($row = mysqli_fetch_array($sql)) {
				if ($row['vrstni_red'] != $i)
					$sql1 = sisplet_query("UPDATE srv_vrednost SET vrstni_red='$i' WHERE id = '$row[id]'");
				$i--;
			}

			sisplet_query("COMMIT");
		}
	}

	/**
	 * shrani trenutno stanje ankete in obvesti administratorja, da nekaj ni ok z anketo
	 *
	 * Vcasih se je sesuval srv_branching zaradi nekih bugov, ki so popravljeni. Zdaj se to zgodi samo v primerih, ce se kopira staro pokvarjeno anketo.
	 * Ce se pokvari srv_branching je ponavadi dovolj, da se v anketi premakne kaksno vprasanje (lahko na isto mesto), da se spet izvede funkcija, ki pocisti bazo
	 *
	 * TOLE UGASNEMO KER VČASIH KAR LETIJO MAILI:)
	 */
	static function checkStruktura () {
		global $site_path;
		global $site_url;

		return;

		if ( ! self::$anketa > 0 ) self::$anketa = (int)$_REQUEST['anketa'];
		if ( ! self::$anketa > 0 ) return;

		$check = null;
		if ( self::$anketa < 8730 ) {
			return;
			$check = self::checkBranchingOld();
		} else {
			$check = self::checkBranching();
		}

		if ( ! $check ) {

			$user = self::uid();

			$get = '';
			foreach ($_GET AS $key => $val) {
				if ($get != '')
					$get .= ', ';
				$get .= $key . ': "' . $val . '"';
			}

			$post = '';
			foreach ($_POST AS $key => $val) {
				if ($post != '')
					$post .= ', ';
				$post .= $key . ': "' . $val . '"';
			}

			$branching = 'ank_id'. " \t - \t ".'parent'." \t - \t ".'vrstni_red'." \t - \t ".'element_spr'." \t - \t ".'element_if'." \t - \t ".'pagebreak'.'<br>';
			$sql = sisplet_query("SELECT * FROM srv_branching WHERE ank_id = '".self::$anketa."' ORDER BY parent, vrstni_red");
			while ($row = mysqli_fetch_array($sql)) {
				$branching .= $row['ank_id']. " \t - \t ".$row['parent']." \t - \t ".$row['vrstni_red']." \t - \t ".$row['element_spr']." \t - \t ".$row['element_if']." \t - \t ".$row['pagebreak'].'<br>';
			}

			$content = 	" ce se pokvari srv_branching je ponavadi dovolj, da se v anketi premakne kaksno vprasanje (lahko na isto mesto), da se spet izvede funkcija, ki pocisti bazo<br><br>".
				" anketa id:<br> 	" . self::$anketa . "<br><br>".
				" <a href=\"".$site_url."admin/survey/index.php?anketa=".self::$anketa."\">".$site_url."admin/survey/index.php?anketa=".self::$anketa."</a><br><br>".
				" site:<br> 		" . $site_path ."<br><br>".
				" get:<br> 			" . $get ."<br><br>".
				" post:<br> 		" . $post ."<br><br>".
				" srv_branching:<br><pre>" . $branching ."</pre><br><br>".
				" user:<br> 		" . $user ."<br><br>"
			;

			try
			{
				$MA = new MailAdapter(self::$anketa, $type='admin');
				//$MA->addRecipients('mitja@sisplet.org');
				$MA->addRecipients('peter.hrvatin@siol.net');
				$resultX = $MA->sendMail($content, 'Sesut srv_branching '.self::$anketa);
			}
			catch (Exception $e)
			{
			}
		}

	}

	/**
	 * rekurzivno preverja, ce je z srv_branchingom vse ok
	 * v nulo, vrstni_red mora bit 1, 2, 3, ...
	 *
	 * @param mixed $parent
	 */
	static function checkBranching ($parent = 0) {

		if ( ! self::$anketa > 0 ) return false;

		$sql = sisplet_query("SELECT parent, vrstni_red, element_if FROM srv_branching WHERE ank_id = '".self::$anketa."' AND parent='$parent' ORDER BY parent, vrstni_red");
		$parent = null;
		$vrstni_red = null;
		while ($row = mysqli_fetch_array($sql)) {

			if ($parent == null || $parent != $row['parent']) {
				$parent = $row['parent'];
				//$vrstni_red = 0;
				$vrstni_red = 1;
			} else {
				$vrstni_red++;
			}

			if ( $row['vrstni_red'] == $vrstni_red ) {

				//ok
			} else {
				// not ok
				return false;
			}

			if ($row['element_if'] > 0) {
				if ( ! self::checkBranching($row['element_if']) )
					return false;
			}
		}

		return true;
	}

	/**
	 * rekurzivno preverja, ce je z srv_branchingom vse ok
	 * za starejse ankete, vazno je da se vrstni_red ne ponavlja, ok je npr: 123, 124, 125, 160, 162
	 *
	 * @param mixed $parent
	 */
	static function checkBranchingOld ($parent = 0) {

		if ( ! self::$anketa > 0 ) return false;

		$sql = sisplet_query("SELECT parent, vrstni_red, element_if FROM srv_branching WHERE ank_id = '".self::$anketa."' AND parent='$parent' ORDER BY parent, vrstni_red");
		$parent = null;
		$vrstni_red = null;
		while ($row = mysqli_fetch_array($sql)) {

			if ($parent == null || $parent != $row['parent']) {
				$parent = $row['parent'];
				$vrstni_red = 0;
				//$vrstni_red = 1;
			} else {
				//$vrstni_red++;
			}

			//echo $row['parent'].' '.$row['vrstni_red'].' ( '.$vrstni_red.' )<br>';

			if ( $row['vrstni_red'] > $vrstni_red ) {

				//ok
			} else {
				// not ok
				return false;
			}

			if ($row['element_if'] > 0) {
				if ( ! self::checkBranchingOld($row['element_if']) )
					return false;
			}

			$vrstni_red = $row['vrstni_red'];
		}

		return true;
	}

	/**
	 * rekurzivno se sprehodimo cez vse elemente v anketi in vrnemo seznam v pravem vrstnem redu
	 *
	 * @param mixed $parent
	 */
	static $branching_array = array();
	static function getBranchingOrder ($parent = 0) {

		if (! self::$anketa > 0) return false;

		$sql = sisplet_query("SELECT element_spr, element_if, parent FROM srv_branching WHERE ank_id = '".self::$anketa."' AND parent='$parent' ORDER BY parent, vrstni_red");
		while ($row = mysqli_fetch_array($sql)) {

			self::$branching_array[] = array('spr_id'=>$row['element_spr'], 'if_id'=>$row['element_if'], 'parent'=>$row['parent']);

			if ($row['element_if'] > 0)
				self::getBranchingOrder($row['element_if']);
		}

		if($parent == 0)
			return self::$branching_array;
		else
			return false;
	}


	/** Naslov za odgovor email sporočil.
	 * Privzeto je avtor ankete
	 * Če ne uporabimo email uporabnika
	 * v nasprotnem primeru damo info@1ka.si ali info@safe.si (odvisno od domene)
	 *
	 * @return Ambigous <string, multitype:>
	 */
	function getReplyToEmail() {
		global $global_user_id, $site_domain;

		# naslov za odgovor je avtor ankete
		$sql = sisplet_query("SELECT email FROM users WHERE id = (select insert_uid from srv_anketa where id='".self::$anketa."')");
		list($MailReplyTo) = mysqli_fetch_row($sql);
		# preverimo veljavnost e-maila
		if ($MailReplyTo == null || !$this->validEmail($MailReplyTo))
		{
			$sql = sisplet_query("SELECT email FROM users WHERE id = '$global_user_id'");
			list($MailReplyTo) = mysqli_fetch_row($sql);
		}

		if ($MailReplyTo == null || trim($MailReplyTo) == '' || !$this->validEmail($MailReplyTo) )
		{
			if (strpos($site_domain, "safe.si") === false)
			{
				$MailReplyTo = '1ka@1ka.si';
			}
			else
			{
				$MailReplyTo = '1ka@safe.si';
			}
		}

		return $MailReplyTo;
	}

	/** Naslov pošiljatelja email sporočil.
	 * odvisno od strani če smo na safe.si uporabimo info@safe.si
	 * če ne pa preberemo nastavitev iz baze
	 * oziroma uporabimo info@1ka.si
	 * po novem se vabila in obvescanje posilja iz raziskave@1ka.si (namesto info@1ka.si)
	 *
	 * @return Ambigous <string, multitype:>
	 */
	function getFromEmail() {
		global $global_user_id, $site_domain;
		$MailFrom = 'info@1ka.si';

		# nastavimo pošiljatelja
		# za SAFE.SI naredimo hardcoded da se pošilja kao iz SAFE.SI
		if (strpos($site_domain, "safe.si") === false)
		{
			#nismo iz SAFE.SI
			$r = sisplet_query("SELECT value FROM misc WHERE what='AlertFrom'");
			list ($MailFrom) = mysqli_fetch_row($r);
		}
		else
		{
			# smo na SAFE.SI
			$MailFrom = 'info@safe.si';
		}

		# Če je slučanjo napaka nastavimo info@1ka.si
		if ($MailFrom == null || trim($MailFrom) == '' || !$this->validEmail($MailFrom))
		{
			$MailFrom = 'info@1ka.si';
		}
		return $MailFrom;
	}



	/**
	Validate an email address.
	Provide email address (raw input)
	Returns true if the email address has the email
	address format and the domain exists.
	 */
	function validEmail($email = null) {

		$isValid = true;
		$atIndex = strrpos($email, "@");

		if (is_bool($atIndex) && !$atIndex) {
			$isValid = false;
		}
		else {
			$domain = substr($email, $atIndex+1);
			$local = substr($email, 0, $atIndex);
			$localLen = strlen($local);
			$domainLen = strlen($domain);
			$domain_parts = explode('.',$domain);

			if ($localLen < 1 || $localLen > 64) {
				// local part length exceeded
				$isValid = false;
			} else if ($domainLen < 1 || $domainLen > 255) {
				// domain part length exceeded
				$isValid = false;
			} else if ($local[0] == '.' || $local[$localLen-1] == '.') {
				// local part starts or ends with '.'
				$isValid = false;
			} else if ($domain[0] == '.' || $domain[$domainLen-1] == '.') {
				// domain part starts or ends with '.'
				$isValid = false;
			} else if (preg_match('/\\.\\./', $local)) {
				// local part has two consecutive dots
				$isValid = false;
			} else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
				// character not valid in domain part
				$isValid = false;
			} else if (preg_match('/\\.\\./', $domain)) {
				// domain part has two consecutive dots
				$isValid = false;
			} else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
				// character not valid in local part unless
				// local part is quoted
				if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
					$isValid = false;
				}
			} else if ( strlen($domain_parts[0]) < 1) {
				// num chars in
				$isValid = false;
			} else if ( strlen($domain_parts[1]) < 1) {
				$isValid = false;
			}

			/*if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A"))) {
				// domain not found in DNS
				$isValid = false;
			}*/
		}

		return $isValid;
	}

	static function formatNumberSimple ($value, $digit = 0, $sufix = "") {
		if ($value <> 0 && $value != null)
			$result = round($value, $digit);
		else
			$result = "0";

		# polovimo decimalna mesta in vejice za tisočice

		$decimal_point = SurveyDataSettingProfiles :: getSetting('decimal_point');
		$thousands = SurveyDataSettingProfiles :: getSetting('thousands');

		$result = number_format($result, $digit, $decimal_point, $thousands) . $sufix;

		return $result;
	}

	static function formatNumber ($value, $digit = 0, $form=null,$sufix = "") {
		# Kako izpisujemo decimalke in tisočice
		$default_seperators = array(	0=>array('decimal_point'=>'.', 'thousands'=>','),
			1=>array('decimal_point'=>',', 'thousands'=>'.'));

		if (is_array($form) && isset($form['decimal_point'])&& isset($form['thousands'])) {
			$decimal_point = $form['decimal_point'];
			$thousands = $form['thousands'];
		} else {
			$decimal_point = $default_seperators['decimal_point'];
			$thousands = $default_seperators['thousands'];
		}

		if ($value <> 0 && $value != null)
			$result = round($value, $digit);
		else
			$result = "0";

		$result = number_format($result, $digit, $decimal_point, $thousands).$sufix;

		return $result;
	}


	/**
	 * @desc V podanem stringu poisce spremenljivke in jih spajpa z vrednostmi
	 */
	private $dataPiping_query = null;   // kesiramo query, ker ga bomo ponavljali za vsako spremenljivko
	public function dataPiping ($text, $usr_id, $loop_id=null, $lang_id=null) {

		if (stripos($text, "#") !== false) {
			if ($this->dataPiping_query == null)
				$this->dataPiping_query = sisplet_query("SELECT s.* FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='".self::$anketa."' AND s.gru_id=g.id AND s.tip!='5' AND (s.tip < '10' OR s.tip = '17' OR s.tip = '22' OR s.tip = '25' OR s.tip='21')");
			if (mysqli_num_rows($this->dataPiping_query))
				mysqli_data_seek($this->dataPiping_query, 0);

			// pri loopih izpisemo samo vrednost spremenljivke za trenuten loop
			if ($loop_id != null) {
				$sql = sisplet_query("SELECT * FROM srv_loop_data WHERE id = '$loop_id'");
				$row = mysqli_fetch_array($sql);
				$loop_vre_id = $row['vre_id'];
				$sql = sisplet_query("SELECT * FROM srv_loop WHERE if_id = '$row[if_id]'");
				$row = mysqli_fetch_array($sql);
				$loop_spr_id = $row['spr_id'];
			} else $loop_spr_id = 0;

			while ($row1 = mysqli_fetch_array($this->dataPiping_query)) {

				if ($row1['tip'] <= 3) {    // radio, checkbox, select

					if (stripos($text, '#'.$row1['variable'].'#') !== false) {
						$pipe = '';
						if ($loop_spr_id == 0) {	// obicen piping
							$sql2 = sisplet_query("SELECT v.id, v.naslov, v.other FROM srv_data_vrednost".self::$db_table." d, srv_vrednost v WHERE d.vre_id=v.id AND d.spr_id='$row1[id]' AND v.spr_id='$row1[id]' AND d.usr_id='$usr_id'");

							while ($row2 = mysqli_fetch_assoc($sql2)) {

								// Piping za multilang anketo
								if($lang_id != null){
									$translate = self::translate_vrednost($lang_id, $row2['id']);
									$row2['naslov'] = $translate ? $translate : $row2['naslov'];
								}

								if ($pipe != '') $pipe .= ', ';

								if ($row2['other'] == 0) {
									$pipe .= $row2['naslov'];
								}
								elseif ($row2['other'] == 1) {
									$sql3 = sisplet_query("SELECT text FROM srv_data_text".self::$db_table." WHERE spr_id='$row1[id]' AND vre_id='$row2[id]' AND usr_id='$usr_id'");
									$row3 = mysqli_fetch_array($sql3);
									$pipe .= $row3['text'];
								}
							}
						}
						// piping v loopu
						else {
							$sql2 = sisplet_query("SELECT v.id, v.naslov, v.other FROM srv_vrednost v WHERE id = '$loop_vre_id'");
							while ($row2 = mysqli_fetch_array($sql2)) {
								// Piping za multilang anketo
								if($lang_id != null){
									$translate = self::translate_vrednost($lang_id, $row2['id']);
									$row2['naslov'] = $translate ? $translate : $row2['naslov'];
								}

								if ($pipe != '') $pipe .= ', ';

								if ($row2['other'] == 0) {
									$pipe .= $row2['naslov'];
								}
								elseif ($row2['other'] == 1) {
									$sql3 = sisplet_query("SELECT text FROM srv_data_text".self::$db_table." WHERE spr_id='$row1[id]' AND vre_id='$row2[id]' AND usr_id='$usr_id'");
									$row3 = mysqli_fetch_array($sql3);
									$pipe .= $row3['text'];
								}
							}
						}

						$text = str_ireplace('#'.$row1['variable'].'#', '<span class="data-piping-'.$row1['id'].'">'.$pipe.'</span>', $text);
					}
				}
				elseif ($row1['tip'] == 9) {    // SN - imena

					if (stripos($text, '#'.$row1['variable'].'#') !== false) {
						$pipe = '';

						// piping v loopu
						$sql2 = sisplet_query("SELECT v.id FROM srv_vrednost v WHERE id = '$loop_vre_id'");
						while ($row2 = mysqli_fetch_array($sql2)) {
							if ($pipe != '') $pipe .= ', ';

							$sql3 = sisplet_query("SELECT text FROM srv_data_text".self::$db_table." WHERE spr_id='$row1[id]' AND vre_id='$row2[id]' AND usr_id='$usr_id'");
							$row3 = mysqli_fetch_array($sql3);
							$pipe .= $row3['text'];
						}

						$text = str_ireplace('#'.$row1['variable'].'#', '<span class="data-piping-'.$row1['id'].'">'.$pipe.'</span>', $text);
					}

				}
				elseif ($row1['tip'] == 4 or $row1['tip'] == 7 or $row1['tip'] == 8 or $row1['tip'] == 22 or $row1['tip'] == 25 or $row1['tip'] == 21) {    // textbox, number, datum

					// piping v loopu za number
					if ($row1['tip'] == 7 && $loop_spr_id != 0) {

						$sql3 = sisplet_query("SELECT COUNT(*)+1 AS c FROM srv_loop_data WHERE if_id='$row[if_id]' AND id<'$loop_id'");
						$row3 = mysqli_fetch_array($sql3);
						$text = str_ireplace('#'.$row1['variable'].'#', '<span class="data-piping-'.$row1['id'].'">'.$row3['c'].'</span>', $text);
					}
					else {
						if (stripos($text, '#'.$row1['variable'].'#') !== false) {
							$sql2 = sisplet_query("SELECT text FROM srv_data_text".self::$db_table." WHERE spr_id='$row1[id]' AND usr_id='$usr_id'");
							if (!$sql2) echo mysqli_error($GLOBALS['connect_db']);

							$row2 = mysqli_fetch_array($sql2);

							$text = str_ireplace('#'.$row1['variable'].'#', '<span class="data-piping-'.$row1['id'].'">'.nl2br($row2['text']).'</span>', $text);
						}
					}

				}
				elseif ($row1['tip'] == 6) {  // multigrid

					$sqlm = sisplet_query("SELECT id, variable FROM srv_vrednost WHERE spr_id = '$row1[id]'");
					while ($rowm = mysqli_fetch_array($sqlm)) {
						if (stripos($text, '#'.$rowm['variable'].'#') !== false) {

							$pipe = '';

							$sql2 = sisplet_query("SELECT g.id, g.naslov FROM srv_data_grid".self::$db_table." d, srv_grid g WHERE d.grd_id=g.id AND d.spr_id=g.spr_id AND d.spr_id='$row1[id]' AND d.vre_id='$rowm[id]' AND d.usr_id='$usr_id'");
							while ($row2 = mysqli_fetch_array($sql2)) {

								// Piping za multilang anketo
								if($lang_id != null){
									$translate = self::translate_grid($lang_id, $row1['id'], $row2['id']);
									$row2['naslov'] = $translate ? $translate : $row2['naslov'];
								}

								if ($pipe != '') $pipe .= ', ';

								$pipe .= $row2['naslov'];
							}

							$text = str_ireplace('#'.$rowm['variable'].'#', '<span class="data-piping-'.$row1['id'].'">'.$pipe.'</span>', $text);
						}
					}
				}
				elseif ($row1['tip'] == 17) {    // Ranking

					if (stripos($text, '#'.$row1['variable'].'#') !== false) {
						$pipe = '';

						// piping v loopu
						$sql2 = sisplet_query("SELECT v.id, v.naslov FROM srv_vrednost v WHERE id = '$loop_vre_id'");
						while ($row2 = mysqli_fetch_array($sql2)) {						
							if ($pipe != '') $pipe .= ', ';

							$pipe .= $row2['naslov'];
						}

						$text = str_ireplace('#'.$row1['variable'].'#', '<span class="data-piping-'.$row1['id'].'">'.$pipe.'</span>', $text);
					}

				}
			}
		}

		return $text;
	}

	/**
	 * prevod za srv_spremenljivka
	 */
	static function translate_spremenljivka($lang_id, $spremenljivka) {

		if ($lang_id != null) {
			$sqll = sisplet_query("SELECT * FROM srv_language_spremenljivka WHERE ank_id='".self::$anketa."' AND spr_id='".$spremenljivka."' AND lang_id='".$lang_id."'");
			$rowl = mysqli_fetch_array($sqll);

			return $rowl;
		}

		return false;
	}

	/**
	 * prevod za srv_vrednost
	 */
	static function translate_vrednost($lang_id, $vrednost) {

		if ($lang_id != null) {

			$sqll = sisplet_query("SELECT naslov FROM srv_language_vrednost WHERE ank_id='".self::$anketa."' AND vre_id='".$vrednost."' AND lang_id='".$lang_id."'");
			$rowl = mysqli_fetch_array($sqll);

			if ($rowl['naslov'] != '')
				return $rowl['naslov'];
		}

		return false;
	}

	/**
	 * prevod za srv_grid
	 */
	static function translate_grid($lang_id, $spremenljivka, $grid) {

		if ($lang_id != null) {

			$sqll = sisplet_query("SELECT naslov FROM srv_language_grid WHERE ank_id='".self::$anketa."' AND spr_id=".$spremenljivka." AND grd_id='".$grid."' AND lang_id='".$lang_id."'");
			$rowl = mysqli_fetch_array($sqll);

			if ($rowl['naslov'] != '')
				return $rowl['naslov'];
		}

		return false;
	}


	static function isUserAnketar($anketa,$user) {

		$str = " (SELECT count(*)FROM srv_dostop AS sd WHERE sd.ank_id='$anketa' AND sd.aktiven = '1'"
			." AND uid = '$user' "
			." AND FIND_IN_SET('phone',sd.dostop )>0 AND FIND_IN_SET('edit',sd.dostop ) = 0)";
		$qry = sisplet_query($str);
		list($cnt) = mysqli_fetch_row($qry);

		return ((int)$cnt > 0) ? true : false;;
	}

	static function getSpremenljivkaSkala($spr_id) {
		# skala se lahko nastavlja samo za:
		# ($row['tip'] == 1 || $row['tip'] == 2 || $row['tip'] == 3 || $row['tip'] == 6)


		// uporabniško nastavljena
		# skala - 1 Nominalna
		# skala - 0 Ordinalna - računamo povprečja

		# v bazi je privzeto -1

		$row = Cache::srv_spremenljivka($spr_id);
		$value = $row['skala'];
		//pri checkboxu je default nominalna
		if($row['skala'] == -1 && $row['tip'] == 2) {
			$value = (int)1;

			# če uporabnik še ni spremninjal
		} elseif($row['skala'] == -1) {
			# če je radio
			if ($row['tip'] == 1 ) {
				# če imamo opcij 4 ali več je ordinalna
				$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$spr_id' ORDER BY vrstni_red ASC");
				if (mysqli_num_rows($sql1) > 3) {
					$value = (int)0; # Ordinalna - računamo povprečja
				}
			}
			# če je multiradio
			if ($row['tip'] == 6 ) {
				# če imamo opcij 4 ali več je ordinalna
				$sql_grid = sisplet_query("SELECT id FROM srv_grid WHERE spr_id='".$spr_id."'");
				if (mysqli_num_rows($sql_grid) > 3) {
					$value = (int)0; # Ordinalna - računamo povprečja
				}
			}
		} else {
			# drugače pustimo uporabniško nastavljeno (ali default (-1))
			$value = (int)$row['skala'];
		}

		return $value;
	}

	// ime pove vse :-)
	static function RemoveNiceUrl($ank_id) {
		global $site_path;

		$sql = sisplet_query("SELECT link FROM srv_nice_links WHERE ank_id = '$ank_id'");
		$row = mysqli_fetch_array($sql);

		$nice_url = $row['link'];

		$f = fopen($site_path.'.htaccess', 'rb');

		if ($f !== false) {
			$output = array();

			while (!feof($f)) {
				$r = fgets($f);

				if (strpos($r, "^".$nice_url.'\b(.*)	') !== false && strpos($r, "?anketa=".$ank_id."") !== false) {
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

				$sql = sisplet_query("DELETE FROM srv_nice_links WHERE ank_id = '$ank_id'");

				// Preverimo ce imamo skupine s tem urljem in jih pobrisemo
				$sqlS = sisplet_query("SELECT * FROM srv_nice_links_skupine WHERE ank_id='$ank_id'");
				if(mysqli_num_rows($sqlS) > 0){

					$f = fopen($site_path.'.htaccess', 'rb');

					if ($f !== false) {
						$outputS = array();

						while (!feof($f)) {
							$r = fgets($f);

							// Loop cez vse skupine
							$delete = false;
							$sqlS = sisplet_query("SELECT * FROM srv_nice_links_skupine WHERE ank_id='$ank_id'");

							while($rowS = mysqli_fetch_array($sqlS)){

								if (strpos($r, "^".$rowS['link'].'\b(.*)	') !== false && strpos($r, "?anketa=".$ank_id."&skupina=".$rowS['vre_id']."") !== false) {

									// pobrisemo vrstico in vnos v bazi
									$sqlD = sisplet_query("DELETE FROM srv_nice_links_skupine WHERE ank_id='$ank_id' AND nice_link_id='$row[id]' AND vre_id='$rowS[vre_id]'");
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
	}

	/**
	 * preveri ce je dolocen modul vklopljen na tej instalaciji 1ke (npr. hirearhija, evalvacija, 360...)
	 */
	static function checkModule($module) {

		$sql = sisplet_query("SELECT active FROM srv_module WHERE module_name='$module'");
		$row = mysqli_fetch_array($sql);

		return ($row['active'] == '1') ? true : false;
	}


	// Vrne help url glede na ustrezno podstran
	public static function getHelpUrl($subdomain, $podstran){

		// Default help url
		$help_url = 'http://www.1ka.si/d/sl/pomoc';

		// Angleški vmesnik - usmerimo na english.1ka.si help
		if($subdomain == 'english'){

			// Nastavitve in podzavihki
			if ($_GET['anketa'] == '' && ($_GET['a'] == 'nastavitve' # in podzavihki
				|| $_GET['a'] == 'osn_pod'
				|| $_GET['a'] == 'trajanje'
				|| $_GET['a'] == 'dostop'
				|| $_GET['a'] == 'urejanje') ) {
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/my-surveys/settings';
			}
			
			//Mobilne nastavitve
			elseif($_GET['a']== 'mobile_settings'){
				$help_url = 'https://www.1ka.si/d/en/help/manuals/mobile-survey-adjustments';
			}
			
			//Standardne besede
			elseif($_GET['a']== 'jezik'){
				$help_url = 'https://www.1ka.si/d/en/help/faq/can-standard-words-be-altered';
			}
			
			//Dostop uredniki
			elseif($_GET['a']== 'dostop'){
				$help_url = 'https://www.1ka.si/d/en/help/faq/how-can-i-add-another-survey-administrator';
			}
			
			//Dostop respondenti
			elseif($_GET['a']== 'piskot'){
				$help_url = 'https://www.1ka.si/d/en/help/manuals/settings-for-respondent-access-cookies-and-passwords';
			}
			
			// Obvescanje
			else if ($_GET['a'] == 'alert') {
				if($_GET['m']=='email_server'){
					$help_url = 'https://www.1ka.si/d/en/help/manuals/sending-emails-via-an-arbitrary-server-eg-gmail ';
				}
				else{
					$help_url = 'https://www.1ka.si/d/en/help/manuals/notifications';
				}
			}
			
			//Aktivnost/kvote
			elseif($_GET['a']== 'trajanje'){
				$help_url = 'https://www.1ka.si/d/en/help/manuals/survey-duration-based-on-date-or-the-number-of-responses';
			}

			//Skupine
			elseif($_GET['a']== 'skupine'){
				$help_url = 'https://www.1ka.si/d/en/help/manuals/creating-respondent-groups';
			}
			
			//Komentarji
			elseif($_GET['a']== 'urejanje'){
				$help_url = 'https://www.1ka.si/d/en/help/manuals/comments';
			}

			//Prikaz podatkov
			elseif($_GET['a']== A_PRIKAZ){	//|| $_GET['a'] == A_PRIKAZ
				$help_url = 'https://www.1ka.si/d/en/help/manuals/data-display-settings';
			}

			//Parapodatki
			elseif($_GET['a']== 'metadata'){	//|| $_GET['a'] == 'metadata'
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/edit/settings';
			}

			//Manjkajoce vrednosti
			elseif($_GET['a']== 'missing'){	//|| $_GET['a'] == 'missing'
				$help_url = 'https://www.1ka.si/d/en/help/manuals/status-of-units-relevance-validity-and-missing-values';
			}
			
			//PDF/RFT Izvozi
			elseif($_GET['a']== 'export_settings'){
				$help_url = 'https://www.1ka.si/d/en/help/manuals/settings-for-exporting-pdfrtf-files-with-responses';
			}
			
			//GDPR
			elseif($_GET['a']== 'gdpr_settings'){
				$help_url = 'https://www.1ka.si/d/en/help/manuals/gdpr-survey-settings';
			}
			
			//Samoevalvacija v solah
			elseif($_GET['a']== 'hierarhija_superadmin'){
				//if($_GET['m']== 'uredi-sifrante'){
				if($_GET['m']== 'uredi-sifrante' || $_GET['m']== 'status'){
					$help_url = 'https://www.1ka.si/d/en/help/user-guide/advanced-modules/self-evaluation-schools';
				}
			}
			
			//Klepet
			elseif($_GET['a']== 'chat'){
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/advanced-modules/chat';
			}
			
			//Panel
			elseif($_GET['a']== 'panel'){
				$help_url = ' https://www.1ka.si/d/en/help/user-guide/advanced-modules/panel';
			}
			
			//Napredni parapodatki
			elseif($_GET['a']== 'advanced_paradata'){
				$help_url = 'https://www.1ka.si/d/en/about/general-description/user-levels/administrator';
			}
			
			//JSON izvoz ankete
			elseif($_GET['a']== 'json_survey_export'){
				$help_url = 'https://www.1ka.si/d/en/help';
			}
			
			//TABLICE, PRENOSNIKI (BETA)
			elseif($_GET['a']== 'fieldwork'){
				//$help_url = 'https://www.1ka.si/d/en/about/uses-of-1ka-services/1ka-offline';
				$help_url = 'https://www.1ka.si/d/en/help';
			}

			//360 stopinj
			elseif($_GET['a']== '360_stopinj'){
				$help_url = 'https://www.1ka.si/d/en/help';
			}

			//borza
			elseif($_GET['a']== 'borza'){
				$help_url = 'https://www.1ka.si/d/en/help';
			}

			//mju
			elseif($_GET['a']== 'mju'){
				$help_url = 'https://www.1ka.si/d/en/help';
			}

			//excelleration matrix
			elseif($_GET['a']== 'excell_matrix'){
				$help_url = 'https://www.1ka.si/d/en/help';
			}
			
			//Povezave - jezik
			elseif($_GET['a']== 'prevajanje'){
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/edit/settings/language';
			}

			// Napredni moduli
			else if ($_GET['a'] == A_TELEPHONE) {
				$help_url = 'http://www.1ka.si/d/en/help/user-guide/advanced-modules/telephone-survey';
			}
			else if ($_GET['a'] == 'uporabnost') {
				$help_url = 'http://www.1ka.si/d/en/help/user-guide/advanced-modules/website-evaluation-split-screen';
			}
			else if ($_GET['a'] == 'vnos') {
				$help_url = 'http://www.1ka.si/d/en/help/user-guide/advanced-modules/administrative-data-input';
			}
			else if ($_GET['a'] == 'kviz') {
				$help_url = 'https://www.1ka.si/d/en/help/manuals/quiz';
			}
			else if ($_GET['a'] == 'slideshow') {
				$help_url = 'http://www.1ka.si/d/en/help/user-guide/advanced-modules/slideshow';
			}
			else if ($_GET['a'] == 'social_network') {
				$help_url = 'https://www.1ka.si/d/en/help/manuals/social-networks';
			}

			// Oblika
			else if ($_GET['a'] == 'tema') {
				$help_url = 'http://www.1ka.si/d/en/help/user-guide/edit/design';
			}
			else if ($_GET['a'] == 'theme-editor') {
				if($_GET['t'] == 'css'){
					$help_url = 'https://www.1ka.si/d/en/help/faq/can-i-alter-the-design-of-an-individual-survey';
				}else{
					$help_url = 'https://www.1ka.si/d/en/help/manuals/design';
				}
			}

			// Arhivi
			else if ($_GET['a'] == 'arhivi') {
				if($_GET['m']== 'data'){
					$help_url = 'https://www.1ka.si/d/en/help/manuals/data-archive';
				}elseif($_GET['m']== 'survey'){
					$help_url = 'https://www.1ka.si/d/en/help/manuals/export-survey-archive';
				}elseif($_GET['m']== 'survey_data'){
					$help_url = 'https://www.1ka.si/d/en/help/manuals/export-survey-archive';
				}elseif($_GET['m']== 'testdata'){
					$help_url = 'https://www.1ka.si/d/en/help/manuals/test-entries';
				}
				else{
					$help_url = 'https://www.1ka.si/d/en/help/manuals/questionnaire-archives';
				}
			}
			
			//Spremembe
			elseif($_GET['a']== 'tracking'){
					$help_url = 'https://www.1ka.si/d/en/help/manuals/archives-survey-changes';				
			}
			
			// STATUS in podstrani
			elseif($_GET['a']== 'reporti'){
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/dashboard/summary';
			}elseif($_GET['a']== 'para_graph'){
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/dashboard/paradata';
			}elseif($_GET['a']== 'nonresponse_graph'){
				if($_GET['m']== 'breaks'){
					$help_url = 'https://www.1ka.si/d/en/help/manuals/interruptions-beta';
				}elseif($_GET['m']== 'advanced'){
					$help_url = 'https://www.1ka.si/d/en/help/user-guide/dashboard/item-nonresponse';
				}else{
					$help_url = 'https://www.1ka.si/d/en/help/user-guide/dashboard/item-nonresponse';
				}				
			}elseif($_GET['a']== 'usable_resp'){
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/dashboard/usable-respondents';
			}elseif($_GET['a']== 'speeder_index'){
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/dashboard/speed-index';
			}elseif($_GET['a']== 'text_analysis'){
				$help_url = '';
			}elseif($_GET['a']== 'geoip_location'){
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/dashboard/ip-location';
			}

			// Komentarji
			else if ($_GET['a'] == 'komentarji' || $_GET['a'] == 'komentarji_anketa') {
				$help_url = 'http://www.1ka.si/d/en/help/user-guide/testing/comments';
			}

			// Testiranje
			else if ($_GET['a'] == 'testiranje') {
				if ($_GET['m'] == 'diagnostika') {
					$help_url = 'http://www.1ka.si/d/en/help/user-guide/testing/diagnostics';
				}
				else if ($_GET['m'] == 'testnipodatki') {
					$help_url = 'http://www.1ka.si/d/en/help/user-guide/testing/automatic-entries';
				}
				else if ($_GET['m'] == 'predvidenicas') {
					$help_url = 'http://www.1ka.si/d/en/help/user-guide/testing/diagnostics';
				}
				else if ($_GET['m'] == 'cas') {
					$help_url = 'http://www.1ka.si/d/en/help/user-guide/testing/diagnostics';
				}
				else {
					$help_url = 'http://www.1ka.si/d/en/help/user-guide/testing/diagnostics';
				}
			}

			// Vabila
			else if ($_GET['a'] == 'vabila') {
				if($_GET['m'] == 'url') {
					$help_url = 'https://www.1ka.si/d/en/help/user-guide/publish/link-url';
				}else{
					$help_url = 'https://www.1ka.si/d/en/help/user-guide/publish/settings';
				}
			}
			
			else if ($_GET['a'] == 'invitations') {
				if ($_GET['m'] == 'add_recipients_view') {
					$help_url = 'http://www.1ka.si/d/en/help/user-guide/publish/1ka-invitations';
				} else if ($_GET['m'] == 'view_recipients') {
					$help_url = 'http://www.1ka.si/d/en/help/user-guide/publish/1ka-invitations';
				} else if ($_GET['m'] == 'view_message') {
					$help_url = 'http://www.1ka.si/d/en/help/user-guide/publish/1ka-invitations';
				} else if ($_GET['m'] == 'send_message') {
					$help_url = 'http://www.1ka.si/d/en/help/user-guide/publish/1ka-invitations';
				} else if ($_GET['m'] == 'view_archive') {
					$help_url = 'https://www.1ka.si/d/en/help/manuals/invitation-archives';
				} else {
					$help_url = 'http://www.1ka.si/d/en/help/user-guide/publish/1ka-invitations';
				}
			}

			// Podatki
			else if ($_GET['a'] == 'data') {
				if ($_GET['m'] == 'quick_edit') {
					$help_url = 'http://www.1ka.si/d/en/help/user-guide/data/browse';
				} else if ($_GET['m'] == 'calculation') {
					$help_url = 'https://www.1ka.si/d/en/help/manuals/computed-values';
				} else if ($_GET['m'] == 'coding') {
					$help_url = 'https://www.1ka.si/d/en/help/manuals/manual-coding';
				} else if ($_GET['m'] == 'coding_auto') {
					$help_url = 'https://www.1ka.si/d/en/help/manuals/automatic-coding';
				} else if ($_GET['m'] == 'recoding') {
					$help_url = 'https://www.1ka.si/d/en/help/manuals/recoding';
				} else if ($_GET['m'] == 'append') {
					$help_url = 'https://www.1ka.si/d/en/help/manuals/adding-data-append';
				} else if ($_GET['m'] == 'merge') {
					$help_url = 'https://www.1ka.si/d/en/help/manuals/merging-data';
				} else {
					$help_url = 'http://www.1ka.si/d/en/help/user-guide/data/browse';
				}
			}
			// Izvoz podatkov
			else if ($_GET['a'] == 'export') {
				if ($_GET['m'] == 'spss' || $_GET['m'] == 'sav') {
					$help_url = 'https://www.1ka.si/d/en/help/manuals/export-to-spss';
				}else if ($_GET['m'] == 'excel_xls') {
					$help_url = 'https://www.1ka.si/d/en/help/manuals/export-to-excel';
				}else if ($_GET['m'] == 'excel') {
					$help_url = 'https://www.1ka.si/d/en/help/faq/export-to-excel-why-are-answers-incomplete-when-i-export-the-data-to-excel';
				} 
				else if ($_GET['m'] == 'txt') {
					$help_url = 'https://www.1ka.si/d/en/help/manuals/export-to-text-file';
				} else {
					$help_url = 'https://www.1ka.si/d/en/help/manuals/export-to-spss';
				}
			}

			// Analiza
			else if ($_GET['a'] == 'analysis' && (in_array($_GET['m'], array('', 'sumarnik', 'ttest', 'para')))) {
				$help_url = 'http://www.1ka.si/d/en/help/user-guide/analysis/statistics';
			}
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'charts') {
				$help_url = 'http://www.1ka.si/d/en/help/user-guide/analysis/charts';
			}
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'anal_arch') {
				$help_url = 'https://www.1ka.si/d/en/help/manuals/analysis-archives';
			}
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'analysis_links') {
				$help_url = 'http://www.1ka.si/d/en/help/user-guide/analysis/reports';
			}
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'analysis_creport') {
				$help_url = 'https://www.1ka.si/d/en/help/manuals/custom-reports';
			}			
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'descriptor') {
				$help_url = 'https://www.1ka.si/d/en/help/manuals/descriptive-statistics';
			}
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'frequency') {
				$help_url = 'https://www.1ka.si/d/en/help/manuals/frequencies';
			}
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'crosstabs') {
				$help_url = 'https://www.1ka.si/d/en/help/manuals/crosstabs';
			}
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'multicrosstabs') {
				$help_url = 'https://www.1ka.si/d/en/help/manuals/multitables';
			}
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'means') {
				$help_url = 'https://www.1ka.si/d/en/help/manuals/means';
			}else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'break') {
				$help_url = 'https://www.1ka.si/d/en/help/manuals/break';
			}
			
			//My surveys
			else if ($_GET['a'] == 'ustvari_anketo') {
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/creating-new-survey';
			}
			
			//Activities
			else if ($_GET['a'] == 'diagnostics' && $_GET['t'] != 'uporabniki' && $_GET['m'] != 'my') {
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/my-surveys/activity';
			}
			
			//Users
			else if ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'uporabniki') {
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/my-surveys/users';
			}
			
			//Library
			else if ($_GET['a'] == 'knjiznica') {
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/my-surveys/library';
			}
			
			//Sporocila
			else if ($_GET['a'] == 'obvestila') {
				$help_url = 'https://www.1ka.si/d/sl/o-1ka/splosen-opis/nivoji-uporabnikov/administrator';
			}
			
			//GDPR
			else if ($_GET['a'] == 'gdpr') {				
				switch($_GET['m']){
					case 'gdpr_survey_list':
						$help_url = 'https://www.1ka.si/d/en/help/user-guide/list-of-surveys';
					break;
					case 'gdpr_requests':
						$help_url = 'https://www.1ka.si/d/en/help/user-guide/my-surveys/gdpr-profile/processing-requests';
					break;					
					case 'gdpr_requests_all':
						$help_url = 'https://www.1ka.si/d/en/help/user-guide/my-surveys/gdpr-profile/processing-requests';
					break;
					case 'gdpr_dpa':
						$help_url = 'https://www.1ka.si/d/en/help/user-guide/my-surveys/gdpr-profile/dpa';
					break;
					default:
						$help_url = 'https://www.1ka.si/d/en/my-surveys/gdpr-profile/general-settings';
					break;					
				}				
			}
			
			//Analize urejanja
			else if ($_GET['a'] == 'edits_analysis') {
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/dashboard/edits-analyses';
			}
			
			//Urejanje - Vprasalnik
			else if ($_GET['a'] == 'branching') {
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/edit/questionnaire';				
			}
			
			//Urejanje - Nastavitve
			else if ($_GET['anketa'] != '' && $_GET['a'] == 'nastavitve') {
				$help_url = 'https://www.1ka.si/d/en/help/user-guide/edit/settings';
			}

            // Moje ankete - narocila
			else if ($_GET['a'] == 'narocila') {
				$help_url = 'https://www.1ka.si/d/en/services';
			}

			// Ostale default podstrani
			else {
				switch ($podstran) {
					case NAVI_UREJANJE:
						$help_url = 'http://www.1ka.si/d/en/help/user-guide/edit';
						break;
					case NAVI_OBJAVA:
						$help_url = 'http://www.1ka.si/d/en/help/user-guide/publish';
						break;
					case NAVI_RESULTS:
						$help_url = 'http://www.1ka.si/d/en/help/user-guide/data';
						break;
					case NAVI_ANALYSIS:
						$help_url = 'http://www.1ka.si/d/en/help/user-guide/analysis';
						break;
					case NAVI_TESTIRANJE:
						$help_url = 'http://www.1ka.si/d/en/help/user-guide/testing';
						break;
					case NAVI_STATUS:
						$help_url = 'http://www.1ka.si/d/en/help/user-guide/dashboard';
						break;
					case NAVI_ADVANCED:
						$help_url = 'http://www.1ka.si/d/en/help/user-guide/advanced-modules';
						break;
					default:
						//$help_url = 'https://www.1ka.si/d/en/help/my-surveys';
						$help_url = 'https://www.1ka.si/d/en/help/user-guide/edit/questionnaire';
						break;
				}
			}
		}
		// Slovenski vmesnik - usmerimo na www.1ka.si help
		else{

			// Nastavitve in podzavihki
			if ($_GET['a'] == 'nastavitve' # in podzavihki
				|| $_GET['a'] == 'osn_pod') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/moje-ankete/nastavitve';
			}
			
			
			//Mobilne nastavitve
			elseif($_GET['a']== 'mobile_settings'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/prilagoditve-za-mobilno-anketo';
			}
			
			//Standardne besede
			elseif($_GET['a']== 'jezik'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc/pogosta-vprasanja/ali-mozno-spremeniti-standardne-besede';
			}			
			
			//Dostop uredniki
			elseif($_GET['a']== 'dostop'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc/pogosta-vprasanja/kako-dodam-se-eno-osebo-za-urejanje-vprasalnika';
			}
			
			//Dostop respondenti
			elseif($_GET['a']== 'piskot'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-za-dostop-respondentov-piskotki-ip-naslovi-gesla-sistemsko-prepoznavanja';
			}
			
			//Aktivnost/kvote
			elseif($_GET['a']== 'trajanje'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/trajanje-ankete-glede-na-datum-ali-stevilo-odgovorov-kvote';
			}			
			
			//Skupine
			elseif($_GET['a']== 'skupine'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/ustvarjanje-skupin-respondentov';
			}			
			
			//Komentarji
			elseif($_GET['a']== 'urejanje'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/komentarji';
			}			
			
			//Prikaz podatkov
			elseif($_GET['a']== A_PRIKAZ){	//|| $_GET['a'] == A_PRIKAZ
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/prikaz-podatkov-nastavitve';
			}			
			
			//Parapodatki
			elseif($_GET['a']== 'metadata'){	//|| $_GET['a'] == 'metadata'
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/anketni-podatki-parapodatki-identifikatorji-sistemske-spremenljivke';
			}			
			
			//Manjkajoce vrednosti
			elseif($_GET['a']== 'missing'){	//|| $_GET['a'] == 'missing'
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/statusi-enot-ustreznost-veljavnost-manjkajoce-vrednosti';
			}
			
			//PDF/RFT Izvozi
			elseif($_GET['a']== 'export_settings'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/nastavitve-izvoza-pdfrtf-datotek-z-odgovori';
			}			
			
			//GDPR
			elseif($_GET['a']== 'gdpr_settings'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/gdpr-nastavitve-ankete';
			}
			
			//Samoevalvacija v solah
			elseif($_GET['a']== 'hierarhija_superadmin'){
				//if($_GET['m']== 'uredi-sifrante'){
				if($_GET['m']== 'uredi-sifrante' || $_GET['m']== 'status'){
					$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/napredni-moduli/samoevalvacija-solah';
				}
			}
			
			//Klepet
			elseif($_GET['a']== 'chat'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/napredni-moduli/klepet';
			}			
			
			//Panel
			elseif($_GET['a']== 'panel'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/napredni-moduli/panel';
			}			
			
			//Napredni parapodatki
			elseif($_GET['a']== 'advanced_paradata'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/indeks-kakovosti-sledenje-opozorilom';
			}
			
			//JSON izvoz ankete
			elseif($_GET['a']== 'json_survey_export'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc';
			}
			
			//
			
			//TABLICE, PRENOSNIKI (BETA)
			elseif($_GET['a']== 'fieldwork'){
				//$help_url = 'https://www.1ka.si/d/sl/o-1ka/nacini-uporabe-1ka/1ka-offline';
				$help_url = 'https://www.1ka.si/d/sl/pomoc';
			}

			//360 stopinj
			elseif($_GET['a']== '360_stopinj'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc';
			}

			//borza
			elseif($_GET['a']== 'borza'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc';
			}

			//mju
			elseif($_GET['a']== 'mju'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc';
			}

			//excelleration matrix
			elseif($_GET['a']== 'excell_matrix'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc';
			}
			
			//Povezave - jezik
			elseif($_GET['a']== 'prevajanje'){
				$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/urejanje/nastavitve/jezik';
			}
			
			//Arhivi
			elseif($_GET['a']== 'arhivi'){
				if($_GET['m']== 'data'){
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/arhivi-podatkov';
				}elseif($_GET['m']== 'survey'){
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/izvoz-arhivi-ankete';
				}elseif($_GET['m']== 'survey_data'){
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/izvoz-arhivi-ankete';
				}elseif($_GET['m']== 'testdata'){
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/testni-vnosi';
				}
				else{
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/arhivi-vprasalnika';
				}				
			}
						
			//Spremembe
			elseif($_GET['a']== 'tracking'){
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/arhivi-spremembe-ankete';				
			}

			// STATUS in podstrani
			elseif($_GET['a']== 'reporti'){
				$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/status/povzetek/?from1ka=1';
			}elseif($_GET['a']== 'para_graph'){
				$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/status/parapodatki/?from1ka=1';
			}elseif($_GET['a']== 'nonresponse_graph'){
				$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/status/neodgovor-spremenljivke/?from1ka=1';
			}elseif($_GET['a']== 'usable_resp'){
				$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/status/uporabni-respondenti/?from1ka=1';
			}elseif($_GET['a']== 'speeder_index'){
				$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/status/indeks-hitrosti/?from1ka=1';
			}elseif($_GET['a']== 'text_analysis'){
				$help_url = '';
			}elseif($_GET['a']== 'geoip_location'){
				$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/status/ip-lokacija/?from1ka=1';
			}

			// Obvescanje
			else if ($_GET['a'] == 'alert') {
				if($_GET['m']=='email_server'){
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/posiljanje-emailov-preko-smtp-email-sistema-1ka-na-strezniku-uporabnika-oziroma';
				}
				else{
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/obvescanje';
				}
			}

			// Napredni moduli
			else if ($_GET['a'] == A_TELEPHONE) {
				$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/napredni-moduli/telefonska-anketa/?from1ka=1';
			}
			else if ($_GET['a'] == 'uporabnost') {
				$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/napredni-moduli/evalvacija-strani/?from1ka=1';
			}
			else if ($_GET['a'] == 'vnos') {
				$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/napredni-moduli/vnos-vprasalnikov/?from1ka=1';
			}
			else if ($_GET['a'] == 'kviz') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/kviz';
			}
			else if ($_GET['a'] == 'slideshow') {
				$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/napredni-moduli/prezentacija/?from1ka=1';
			}
			else if ($_GET['a'] == 'social_network') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/socialna-omrezja';
			}

			// Oblika
			else if ($_GET['a'] == 'tema') {
				$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/urejanje/oblika/?from1ka=1';
			}
			else if ($_GET['a'] == 'theme-editor') {
				if($_GET['t'] == 'css'){
					$help_url = 'https://www.1ka.si/d/sl/pomoc/pogosta-vprasanja/ali-lahko-spremenim-izgled-posamezne-ankete';
				}else{
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/oblika';
				}
				
			}

			// Komentarji
			else if ($_GET['a'] == 'komentarji' || $_GET['a'] == 'komentarji_anketa') {
				$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/testiranje/komentarji/?from1ka=1';
			}

			// Testiranje
			else if ($_GET['a'] == 'testiranje') {
				if ($_GET['m'] == 'diagnostika') {
					$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/testiranje/diagnostika/?from1ka=1';
				}
				else if ($_GET['m'] == 'testnipodatki') {
					$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/testiranje/avtomatski-vnosi/?from1ka=1';
				}
				else if ($_GET['m'] == 'predvidenicas') {
					$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/testiranje/diagnostika/?from1ka=1';
				}
				else if ($_GET['m'] == 'cas') {
					$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/testiranje/diagnostika/?from1ka=1';
				}
				else {
					$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/testiranje/diagnostika/?from1ka=1';
				}
			}

			// Vabila
			else if ($_GET['a'] == 'vabila') {
				if($_GET['m'] == 'url') {
					$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/objava/povezava-url/?from1ka=1';
				}else{
					$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/objava/nastavitve-objave/?from1ka=1';
				}
			}
			else if ($_GET['a'] == 'invitations') {
				if ($_GET['m'] == 'add_recipients_view') {
					$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/objava/1ka-vabila/?from1ka=1';
				} else if ($_GET['m'] == 'view_recipients') {
					$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/objava/1ka-vabila/?from1ka=1';
				} else if ($_GET['m'] == 'view_message') {
					$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/objava/1ka-vabila/?from1ka=1';
				} else if ($_GET['m'] == 'send_message') {
					$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/objava/1ka-vabila/?from1ka=1';
				} else if ($_GET['m'] == 'view_archive') {
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/arhivi-vabil';
				} else {
					$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/objava/email-vabila';
				}
			}

			// Podatki
			else if ($_GET['a'] == 'data') {
				if ($_GET['m'] == 'quick_edit') {
					$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/podatki/pregledovanje/?from1ka=1';
				} else if ($_GET['m'] == 'calculation') {
					$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/podatki/izracuni/?from1ka=1';
				} else if ($_GET['m'] == 'coding') {
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/rocno-kodiranje';
				} else if ($_GET['m'] == 'coding_auto') {
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/avtomatsko-kodiranje';
				} else if ($_GET['m'] == 'recoding') {
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/rekodiranje';
				} else if ($_GET['m'] == 'append') {
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/dodajanje-podatkov-append';
				} else if ($_GET['m'] == 'merge') {
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/zdruzevanje-podatkov';
				} else {
					$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/podatki/pregledovanje/?from1ka=1';
				}
			}
			// Izvoz podatkov
			else if ($_GET['a'] == 'export') {
				if ($_GET['m'] == 'spss' || $_GET['m'] == 'sav') {
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/izvoz-spss';					
				}else if ($_GET['m'] == 'excel_xls') {
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/izvoz-excel';
				}else if ($_GET['m'] == 'excel') {
					$help_url = 'https://www.1ka.si/d/sl/pomoc/pogosta-vprasanja/izvoz-excel-zakaj-se-pri-izvozu-excel-odprti-odgovori-ne-izpisejo-celoti';
				} 
				else if ($_GET['m'] == 'txt') {
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/izvoz-tekstovno-datoteko';
				} else {
					$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/izvoz-spss';
				}
			}

			// Analiza
			else if ($_GET['a'] == 'analysis' && (in_array($_GET['m'], array('', 'sumarnik', 'ttest', 'para')))) {
				$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/analize/statistike/?from1ka=1';
			}
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'charts') {
				$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/analize/grafi/?from1ka=1';
			}
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'anal_arch') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/arhivi-analiz';
			}
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'analysis_links') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/analize/porocila';
			}
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'analysis_creport') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/porocila-meri';
			}			
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'descriptor') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/opisne-statistike';
			}			
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'frequency') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/frekvence';
			}
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'crosstabs') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/tabele';
			}
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'multicrosstabs') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/multitabele';
			}
			else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'means') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/povprecja';
			}else if ($_GET['a'] == 'analysis' && $_GET['m'] == 'break') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/prirocniki/razbitje';
			}
			
			//Moje ankete
			else if ($_GET['a'] == 'ustvari_anketo') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/kreiranje-nove-ankete';
			}
			
			//Aktivnost
			else if ($_GET['a'] == 'diagnostics' && $_GET['t'] != 'uporabniki' && $_GET['m'] != 'my') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/moje-ankete/aktivnost';
			}
			
			//Uporabniki
			else if ($_GET['a'] == 'diagnostics' && $_GET['t'] == 'uporabniki') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/moje-ankete/uporabniki';
			}
			
			//Knjiznica
			else if ($_GET['a'] == 'knjiznica') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/moje-ankete/knjiznica';
			}
			
			//Sporocila
			else if ($_GET['a'] == 'obvestila') {
				$help_url = 'https://www.1ka.si/d/sl/o-1ka/splosen-opis/nivoji-uporabnikov/administrator';
			}

			//GDPR
			else if ($_GET['a'] == 'gdpr') {				
				switch($_GET['m']){
					case 'gdpr_survey_list':
						$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/moje-ankete/gdpr-profil/seznam-anket';
					break;
					case 'gdpr_requests':
						$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/moje-ankete/gdpr-profil/procesiranje-zahtevkov';
					break;					
					case 'gdpr_requests_all':
						$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/moje-ankete/gdpr-profil/procesiranje-zahtevkov';
					break;
					case 'gdpr_dpa':
						$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/moje-ankete/gdpr-profil/dpa';
					break;
					default:
						$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/moje-ankete/gdpr-profil/splosne-nastavitve';
					break;					
				}				
			}
			
			//Analize urejanja
			else if ($_GET['a'] == 'edits_analysis') {
				$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/status/analize-urejanja';
			}
			
			//Urejanje - Vprasalnik
			else if ($_GET['a'] == 'branching') {				
				$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/urejanje/vprasalnik';
			}

            // Moje ankete - narocila
			else if ($_GET['a'] == 'narocila') {
				$help_url = 'https://www.1ka.si/d/en/services';
				$help_url = 'https://www.1ka.si/d/sl/cenik';
			}
			
			// Ostale default podstrani
			else {
				switch ($podstran) {
					case NAVI_STATUS;
						$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/status/povzetek/?from1ka=1';
						break;
					case NAVI_UREJANJE:
						$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/urejanje/?from1ka=1';
						break;
					case NAVI_OBJAVA:
						$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/objava/?from1ka=1';
						break;
					case NAVI_RESULTS:
						$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/podatki/?from1ka=1';
						break;
					case NAVI_ANALYSIS:
						$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/analize/?from1ka=1';
						break;
					case NAVI_TESTIRANJE:
						$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/testiranje/?from1ka=1';
						break;
					case NAVI_STATUS:
						$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/status/?from1ka=1';
						break;
					case NAVI_ADVANCED:
						$help_url = 'http://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/napredni-moduli/?from1ka=1';
						break;
					default:
						//$help_url = 'https://www.1ka.si/d/sl/pomoc/moje-ankete';
						$help_url = 'https://www.1ka.si/d/sl/pomoc/vodic-za-uporabnike/urejanje/vprasalnik';
						break;
				}
			}
		}

		return $help_url;
	}

	// Nov algoritem za id ankete v url-ju (ni vec stevilka ampak string - vsako liho stevilko zamenjamo z ustrezno crko npr. 1234 -> A2C4)
	public static function encryptAnketaID($anketa_id){

		// Ali imamo vklopljeno sifriranje id-ja anket v url-ju - ZENKRAT UGASNJENO
		//if(true){
		if(false){
			$anketa_arr = str_split($anketa_id);
			$anketa_string = '';

			foreach($anketa_arr as $pos => $num){

				// Na lihih mestih pretvorimo stevilko v crko
				if($pos % 2 == 0)
					$anketa_string .= chr(97 + $num);
				else
					$anketa_string .= $num;
			}
			//$anketa_string = strtolower($anketa_string);
		}
		else
			$anketa_string = $anketa_id;

		return $anketa_string;
	}

	// Algoritem, ki iz texta ustvari strukturo vprasanj in variabel (za uvoz anekte iz texta)
	public static function anketaArrayFromText($raw_text){
		
		if($raw_text != ''){
			
			// Trenutna vrstica je vprasanje (0) ali variabla (1)
			$prev_blank = true;
			$questin_cnt = -1;
			$variable_cnt = 0;
			
			$text_array = array();	
			
			// Gremo cez vse vrstice
			$lines = explode('\n', str_replace('\r', '', $raw_text));
			foreach($lines as $line){
				
				$line = trim($line);
				
				// Ce imamo prazno vrstico
				if($line == ''){
					$prev_blank = true;

					$variable_cnt = 0;
				}
				else{
					// Ce je bila prejsnja prazna gre za novo vprasanje
					if($prev_blank){
						$questin_cnt++;
						$text_array[$questin_cnt]['title'] = $line;
					}
					// Drugace gre za variablo v vprasanju
					else{
						$text_array[$questin_cnt][$variable_cnt] = $line;
						$variable_cnt++;
					}

					$prev_blank = false;
				}
			}
			
			return $text_array;
		}
		else
			return false;
    }
    
    // Izpise obvestilo, da ni podatkov
    static function noDataAlert() {
        global $lang;
        
        echo '<div class="no_data_alert">';
        echo $lang['srv_data_no_data'];
        echo '</div>';
    }

    // Vrne string s signaturjem za email v ustreznem jeziku (default slovenski)
    public static function getEmailSignature($lang_id = 0){
        global $lang;
        global $app_settings;

        if(isset($app_settings['email_signature_custom']) && $app_settings['email_signature_custom'] == 1){
            $signature = '<br /><br /><br />'.$app_settings['email_signature_text'];
        }
        else{

            $signature = '<br /><br /><br />';

            $signature .= $lang['srv_1ka_mail_signature_bye'];

            $signature .= '<br /><br />';

            // Logo
            if($lang['id'] == '1')
                $signature .= '<img src="https://www.1ka.si/public/img/logo/1ka_slo.png" width=90 style="width:90px; height:auto; border:0;" />';
            else
                $signature .= '<img src="https://www.1ka.si/public/img/logo/1ka_eng.png" width=90 style="width:90px; height:auto; border:0;" />';

            $signature .= '<br/>-------------------------------------------------------------------<br/>';

            // Footer
            $signature .= '<span style="font-size:13px; line-height:30px;">';
            
            $signature .= ' <strong><span style="color:#1e88e5;">'.$lang['srv_1ka_mail_signature_help'].'</span></strong>';
            $signature .= ' <br/><strong><span style="color:#777777;">'.$lang['srv_1ka_mail_signature_click'].':</span></strong> <a href="'.($lang['id'] == '1' ? 'https://www.1ka.si/d/sl/storitve' : 'https://www.1ka.si/d/en/services').'" target="_blank" style="color:#1e88e5;">'.$lang['srv_1ka_mail_signature_link'].'</a>';
            $signature .= ' <br/><span style="color:#777777;">'.$lang['srv_1ka_mail_signature_social'].':</span> <a href="https://www.facebook.com/1KA-123545614388521/" target="_blank" style="color:#1e88e5;">Facebook</a> <span style="color:#777777;">'.$lang['srv_1ka_mail_signature_and'].'</span> <a href="https://twitter.com/enklikanketa" target="_blank" style="color:#1e88e5;">Twitter</a></span>';

            $signature .= '</span>';
        }

        return $signature;
    }
}?>