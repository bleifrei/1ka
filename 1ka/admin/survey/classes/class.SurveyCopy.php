<?php

/* Za potrebe kopiranja ankete
 * 
 * lahko v okviru istega strežnika ali pa iz strežnika na strežnik oz v datoteko
 *
 * Tabele ki se avtomatsko prilagajajo in kopirajo:
 * - fixed, sedaj se vse prilagajajo
 * 
 *  
 * Created on 23.02.2010
 * 
 * UPDATES:
 * 	24.02.2001 - nov način kopiranja, večina tabel se avtomatsko prilagodi (razen pogojev) 
 *  23.11.2010 - spremenjeno, da se vse tabele klicejo preko preformCopyTable() - funkcijo sem tudi malo spremenil, je komentar -mitja
 *  24.11.2010 - dela z bazo ver. 10.11.24 za naprej je treba dodajati samo nove tabele. obstojece tabele se vse cele kopirajo 
 *  04.04.2011 - spremenljen način kopiranja, da gre najprej vse v 1 array, potem pa se bere iz arraya (da omogocimo export - import v datoteko) 
 *
 *
 * TODO:
 * 
 *	preimenovat funckijo preform.. v perform... :)
 * 
 */

class SurveyCopy {
	
	static private $inited = null;				#je inicializiran, ali imamo vse podatke 

	static private $src_survey = null;			#izvorna anketa 
	static private $src_connect_db = null;		#db povezava na izvorna streznik 
	static private $dest_connect_db = null;		#db povezava na streznik kamor kopiramo
	static private $destSite = 0;
	static private $errors = null;				#array z napakami 

	static private $source_array = null;
	
	public static function setSrcSurvey($srcSurvey){
		self :: $src_survey = $srcSurvey;
	}
	
	public static function setSrcConectDb($src_connect_db){
		self :: $src_connect_db = $src_connect_db;
	}

	public static function setDestSite($destSite = 0) {
		$connect_db2 = null;
		
		// mitja - kopiranje na localhost
		if ($destSite == -1 && isset ($_GET['ip'])) {
			self::$destSite = -1;
			
			$ip = $_GET['ip'];
			$connect_db2 = $GLOBALS['connect_db'] = mysqli_connect($ip, 'mitja', 'geslo');
			$sql = mysqli_select_db($GLOBALS['connect_db'], "sisplet", $connect_db2);
			if (!$sql) {
				self :: $errors[] = mysqli_error($GLOBALS['connect_db']); 
			}
		} elseif ($destSite == 0) { 	// backup
			self::$destSite = 0;
			
			if ( self :: $src_connect_db != null )
				$connect_db2 = self :: $src_connect_db;
			else {
				self :: $errors[] = "Src connection is missing! ".mysqli_error($GLOBALS['connect_db']);
			}

		} elseif ($destSite > 0) { // kopiranje na drug server
			self::$destSite = $destSite;
			
			// Predlagam da se tole da v locen config fajl!
			// v /etc/hosts (windows: %SystemRoot%\System32\drivers\etc\hosts ) vpisi serverje (server1, server2,...)
			// da se ne bo v kodi vleklo ime pravega serverja!!!
			global $site_path;			
            
            $servers_array = array (
                '1'     =>   array('mirisorg', 'server1'),
                '2'     =>   array('www1kasi', 'server4'),
                '3'     =>   array('wwwrisorg', 'server1'),
                '4'     =>   array('wwwfdvinfonet', 'server1'),
                '5'     =>   array('wwwsafesi', 'server1'),
                '6'     =>   array('praksafdvinfonet', 'server1'),
                '7'     =>   array('skin1kasi', 'server2'),
                '8'     =>   array('back1kasi', 'server2'),
                '9'     =>   array('back14031kasi', 'server2'),
                '10'    =>   array('back30031kasi', 'server2'),
                '11'    =>   array('pizza1kasi', 'server3'),
                '12'    =>   array('fdv1kasi', 'server2'),
                '13'    =>   array('ef1kasi', 'server2'),
                '14'    =>   array('20101kasi', 'server2'),
                '15'    =>   array('new1kasi', 'server2'),
                '16'    =>   array('beta1kasi', 'server4'),
                '17'    =>   array('www1cssi', 'server4')
            );

			$server = $servers_array[$destSite][1];
			$db = $servers_array[$destSite][0];

			$connect_db2 = $GLOBALS['connect_db'] = mysqli_connect($server, 'survey_push', 'survey_push');
			$sql = mysqli_select_db($GLOBALS['connect_db'],$db, $connect_db2);
			if (!$sql) {
				self :: $errors[] = '0'.mysqli_error($GLOBALS['connect_db']);
			}
		} else {
			self :: $errors[] = "Invalid site ID";
		}
		
		if ($connect_db2 != null) {
			# povezava do drugega streznikla dela
			self :: $dest_connect_db = $connect_db2;

		} else {
			self :: $errors[] = "Could not connect to dest server";
		}
		
	}

	static function getErrors() {
		return self :: $errors;
	}
	
	/**
	* shrani array ankete kot datoteko
	* 
	*/
	static function saveArrayFile ( $data = false ) {
		
		// pocistimo, ce se ze prej kaj izpisuje
		ob_end_clean();
		
		// preberemo array
		$array = self::getArraySource( $data );
		
		$array['srv_anketa'][0]['naslov'] = $array['srv_anketa'][0]['naslov'].' '.date("j.n.Y");
		
		// zapisemo file na disk
		$fp = fopen( dirname(__FILE__) . '/../SurveyBackup/'.$array['srv_anketa'][0]['id'].'-'.date("d.m.Y-H.i.s").'.1ka', 'w');
		fwrite($fp, serialize($array));
		fclose($fp);
		
	}
	
	
	/**
	* restavrira anketo iz datoteke na strezniku
	* 
	* @param mixed $filename
	*/
	static function restoreArrayFile ( $filename ) {
		
		$handle = fopen(dirname(__FILE__) . '/../SurveyBackup/'.$filename, "rb");
		$contents = fread($handle, filesize(dirname(__FILE__) . '/../SurveyBackup/'.$filename));
		fclose($handle);
		
		$array = unserialize($contents);
		
		self::setSourceArray($array);
		
		return self::doCopy();
	}
	
	/**
	* vrne array ankete kot datoteko
	* 
	*/
	static function downloadArrayFile ( $data = false ) {
		
		// Samo za ekstremne primere (zelo velike ankete) :)
		//ini_set('memory_limit', '1000M');
		
		// pocistimo, ce se ze prej kaj izpisuje
		ob_end_clean();
		
		// preberemo array
		$array = self::getArraySource( $data );
		
		// nastavimo attachment header
		header('Content-Disposition: attachment; filename="'.$array['srv_anketa'][0]['naslov'].' '.date("j.n.Y").'.1ka"'); 
		
		$array['srv_anketa'][0]['naslov'] = $array['srv_anketa'][0]['naslov'].' '.date("j.n.Y");
		
		// izpisemo serializiran array
		//echo serialize($array);
		echo base64_encode(serialize($array));
	}

	/**
	* vrne array ankete kot datoteko
	* 
	*/
	static function downloadArrayVar ( $data = false ) {
		
		// pocistimo, ce se ze prej kaj izpisuje
		ob_end_clean();
		
		// preberemo array
		$array = self::getArraySource( $data );
		
		$array['srv_anketa'][0]['naslov'] = $array['srv_anketa'][0]['naslov'].' '.date("j.n.Y");
		
		// izpisemo serializiran array
		return $array;
		
	}

	static function setSourceArray ($array) {
		self::$source_array = $array;
	}
	
	/**
	* vrne array z vsebino celotne ankete
	* 
	*/
	public static function getArraySource ( $data = false ) {

		$arr_src = array();

		// subqueryji mysql cist ubijejo zato sem razbil v 2 locena queryja... v bistvu si zakesiramo seznam spremenljivk in ifov
		// TODO ko se MySQL upgrada na 5.6 mogoce prestavit nazaj na navaden subquery, ali pa tut ne - bo za sprobat


		$qry_src_survey = sisplet_query("SELECT * FROM srv_anketa WHERE id = '".self :: $src_survey."'", self :: $src_connect_db);
		$anketa_array = self::sql2array($qry_src_survey);

        //Če imamo vklopljeno hierarhijo, potem vrednosti spremenljivk od hierarhije ne kopiramo
        // V kolikor imamo vklopljeno hierarhijo potem ne kopiramo spremenljivke od hierarhije
        $hierarhija_sql = null;
        $hierarhija_not_in = null;

		SurveyInfo::getInstance()->SurveyInit(self::$src_survey);
		if(SurveyInfo::getInstance()->checkSurveyModule('hierarhija')) {
			$hierarhija_sql = "AND s.variable!='vloga' AND s.variable NOT LIKE 'nivo%'";
			$hierarhija_not_in = "AND element_spr NOT IN (SELECT s.id AS spr_id
																FROM srv_spremenljivka s, srv_grupa g
																WHERE
																	g.ank_id='" . self::$src_survey . "'
																	AND g.id=s.gru_id
																	AND (s.variable='vloga' OR s.variable LIKE 'nivo%'))";
		}

		// pripravimo si seznam spremenljivk in IF stavkov
		$qry_cache_spr_id = self::prepareSubquery(sisplet_query("SELECT element_spr AS spr_id FROM srv_branching
																	WHERE ank_id='" . self::$src_survey . "' AND element_spr>0 ".$hierarhija_not_in."
																UNION SELECT s.id AS spr_id FROM srv_spremenljivka s, srv_grupa g
																	WHERE g.ank_id='" . self::$src_survey . "' AND g.id=s.gru_id AND s.skupine='1' ".$hierarhija_sql."
																UNION SELECT s.id AS spr_id FROM srv_spremenljivka s, srv_grupa g
																	WHERE g.ank_id='" . self::$src_survey . "' AND g.id=s.gru_id AND s.skupine='3'
																UNION SELECT spr_id FROM srv_grid_multiple
																	WHERE ank_id='" . self::$src_survey . "'", self::$src_connect_db));

		// pripravimo si seznam IFov, ker se pogosto rabijo
		$qry_cache_if_id = self::prepareSubquery( sisplet_query("SELECT element_if FROM srv_branching
																	WHERE element_if>0 AND ank_id = '".self::$src_survey."'
																UNION SELECT v.if_id AS element_if FROM srv_vrednost v, srv_spremenljivka s, srv_grupa g
																	WHERE v.if_id>0 AND v.spr_id=s.id AND s.gru_id=g.id AND g.ank_id='".self::$src_survey."'", self::$src_connect_db) );


		$qry_src_version = sisplet_query("SELECT * FROM misc WHERE what = 'version'");
		$row_src_version = mysqli_fetch_assoc($qry_src_version);
		$arr_src['version'] = $row_src_version['value'];

		$qry_src_survey = sisplet_query("SELECT * FROM srv_anketa WHERE id = '".self :: $src_survey."'", self :: $src_connect_db);
		$arr_src['srv_anketa'] = self::sql2array($qry_src_survey);

		$qry_src_alert = sisplet_query("SELECT * FROM srv_alert WHERE ank_id = '".self :: $src_survey."'", self :: $src_connect_db);
		$arr_src['srv_alert'] = self::sql2array($qry_src_alert);

		$qry_src_call_setting = sisplet_query("SELECT * FROM srv_call_setting WHERE survey_id = '".self::$src_survey."'", self::$src_connect_db);
		$arr_src['srv_call_setting'] = self::sql2array($qry_src_call_setting);

		$qry_src_dostop = sisplet_query("SELECT * FROM srv_dostop WHERE ank_id = '".self :: $src_survey."'", self :: $src_connect_db);
		$arr_src['srv_dostop'] = self::sql2array($qry_src_dostop);

		$qry_src_dostop_language = sisplet_query("SELECT * FROM srv_dostop_language WHERE ank_id = '".self :: $src_survey."'", self :: $src_connect_db);
		$arr_src['srv_dostop_language'] = self::sql2array($qry_src_dostop_language);

		$qry_language = sisplet_query("SELECT * FROM srv_language WHERE ank_id = '".self::$src_survey."'", self::$src_connect_db);
		$arr_src['srv_language'] = self::sql2array($qry_language);

		$qry_src_grupa = sisplet_query("SELECT * FROM srv_grupa WHERE ank_id = '".self :: $src_survey."'", self :: $src_connect_db);
		$arr_src['srv_grupa'] = self::sql2array($qry_src_grupa);

		$qry_src_spremenljivke = sisplet_query("SELECT s.* FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id = g.id AND g.ank_id = '".self :: $src_survey."' ".$hierarhija_sql." UNION SELECT s.* FROM srv_spremenljivka s, srv_grid_multiple gm WHERE s.id=gm.spr_id AND gm.ank_id = '".self::$src_survey."'", self :: $src_connect_db);
		$arr_src['srv_spremenljivka'] = self::sql2array($qry_src_spremenljivke);

		//$qry_src_vrednosti = sisplet_query("SELECT v.* FROM srv_vrednost v WHERE v.spr_id IN (SELECT element_spr FROM srv_branching WHERE ank_id='".self::$src_survey."' AND element_spr>0 UNION SELECT spr_id FROM srv_grid_multiple WHERE ank_id='".self::$src_survey."')", self :: $src_connect_db);
		$qry_src_vrednosti = sisplet_query("SELECT v.* FROM srv_vrednost v WHERE v.spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
		$arr_src['srv_vrednost'] = self::sql2array($qry_src_vrednosti);
		
		$qry_src_hotspot_regions = sisplet_query("SELECT hr.* FROM srv_hotspot_regions hr WHERE hr.spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
		$arr_src['srv_hotspot_regions'] = self::sql2array($qry_src_hotspot_regions);	
                
                $qry_src_vrednosti_map = sisplet_query("SELECT vm.* FROM srv_vrednost_map vm WHERE vm.spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
		$arr_src['srv_vrednost_map'] = self::sql2array($qry_src_vrednosti_map);

		//$qry_src_grid = sisplet_query("SELECT gr.* FROM srv_grid gr WHERE gr.spr_id IN (SELECT element_spr FROM srv_branching WHERE ank_id='".self::$src_survey."' AND element_spr>0 UNION SELECT spr_id FROM srv_grid_multiple WHERE ank_id='".self::$src_survey."')", self :: $src_connect_db);
		$qry_src_grid = sisplet_query("SELECT gr.* FROM srv_grid gr WHERE gr.spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
		$arr_src['srv_grid'] = self::sql2array($qry_src_grid);

		$qry_src_grid_multiple = sisplet_query("SELECT * FROM srv_grid_multiple WHERE ank_id = '".self::$src_survey."'", self::$src_connect_db);
		$arr_src['srv_grid_multiple'] = self::sql2array($qry_src_grid_multiple);

		$qry_src_language_spremenljivka = sisplet_query("SELECT * FROM srv_language_spremenljivka WHERE ank_id = '".self::$src_survey."'", self::$src_connect_db);
		$arr_src['srv_language_spremenljivka'] = self::sql2array($qry_src_language_spremenljivka);

		$qry_src_language_vrednost = sisplet_query("SELECT * FROM srv_language_vrednost WHERE ank_id = '".self::$src_survey."'", self::$src_connect_db);
		$arr_src['srv_language_vrednost'] = self::sql2array($qry_src_language_vrednost);

		$qry_src_language_grid = sisplet_query("SELECT * FROM srv_language_grid WHERE ank_id = '".self::$src_survey."'", self::$src_connect_db);
		$arr_src['srv_language_grid'] = self::sql2array($qry_src_language_grid);

		$qry_missing_values = sisplet_query("SELECT * FROM srv_missing_values WHERE sid = '".self::$src_survey."'", self::$src_connect_db);
		$arr_src['srv_missing_values'] = self::sql2array($qry_missing_values);

		// v cnd_id je -spr_id OR cnd_id = srv_condition.id
		$qry_calculation = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id IN ( SELECT (0 - s.id) AS id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id = g.id AND g.ank_id = '".self :: $src_survey."' ) OR cnd_id IN ( SELECT id FROM srv_condition WHERE if_id IN (".$qry_cache_if_id.") ) ", self::$src_connect_db);
		$arr_src['srv_calculation'] = self::sql2array($qry_calculation);
		
		$qry_src_misc = sisplet_query("SELECT * FROM srv_survey_misc WHERE sid = '".self :: $src_survey."'", self :: $src_connect_db);
		$arr_src['srv_survey_misc'] = self::sql2array($qry_src_misc);

		$qry_src_glasovanje = sisplet_query("SELECT * FROM srv_glasovanje WHERE ank_id = '".self :: $src_survey."'", self :: $src_connect_db);
		$arr_src['srv_glasovanje'] = self::sql2array($qry_src_glasovanje);

		$qry_src_if = sisplet_query("SELECT i.* FROM srv_if i, srv_branching b WHERE b.element_if=i.id AND b.element_if>0 AND b.ank_id='".self :: $src_survey."' UNION SELECT i.* FROM srv_if i, srv_vrednost v, srv_spremenljivka s, srv_grupa g WHERE i.id=v.if_id AND v.if_id>0 AND v.spr_id=s.id AND s.gru_id=g.id AND g.ank_id='".self::$src_survey."'", self :: $src_connect_db);
		$arr_src['srv_if'] = self::sql2array($qry_src_if);

		//$qry_condition = sisplet_query("SELECT * FROM srv_condition WHERE if_id IN (SELECT element_if FROM srv_branching WHERE element_if>0 AND ank_id = '".self::$src_survey."' UNION SELECT v.if_id AS element_if FROM srv_vrednost v, srv_spremenljivka s, srv_grupa g WHERE v.if_id>0 AND v.spr_id=s.id AND s.gru_id=g.id AND g.ank_id='".self::$src_survey."')", self :: $src_connect_db);
		$qry_condition = sisplet_query("SELECT * FROM srv_condition WHERE if_id IN (".$qry_cache_if_id.")", self :: $src_connect_db);
		$arr_src['srv_condition'] = self::sql2array($qry_condition);

		//$qry_src_condition_grid = sisplet_query("SELECT cg.* FROM srv_condition_grid cg, srv_condition c WHERE cg.cond_id = c.id AND c.if_id IN (SELECT element_if FROM srv_branching WHERE element_if>0 AND ank_id = '".self::$src_survey."' UNION SELECT v.if_id AS element_if FROM srv_vrednost v, srv_spremenljivka s, srv_grupa g WHERE v.if_id>0 AND v.spr_id=s.id AND s.gru_id=g.id AND g.ank_id='".self::$src_survey."')", self :: $src_connect_db);
		$qry_src_condition_grid = sisplet_query("SELECT cg.* FROM srv_condition_grid cg, srv_condition c WHERE cg.cond_id = c.id AND c.if_id IN (".$qry_cache_if_id.")", self :: $src_connect_db);
		$arr_src['srv_condition_grid'] = self::sql2array($qry_src_condition_grid);

		//$qry_src_condition_vre = sisplet_query("SELECT cv.* FROM srv_condition_vre cv, srv_condition c WHERE cv.cond_id = c.id AND c.if_id IN (SELECT element_if FROM srv_branching WHERE element_if>0 AND ank_id = '".self::$src_survey."' UNION SELECT v.if_id AS element_if FROM srv_vrednost v, srv_spremenljivka s, srv_grupa g WHERE v.if_id>0 AND v.spr_id=s.id AND s.gru_id=g.id AND g.ank_id='".self::$src_survey."')",  self :: $src_connect_db);
		$qry_src_condition_vre = sisplet_query("SELECT cv.* FROM srv_condition_vre cv, srv_condition c WHERE cv.cond_id = c.id AND c.if_id IN (".$qry_cache_if_id.")",  self :: $src_connect_db);
		$arr_src['srv_condition_vre'] = self::sql2array($qry_src_condition_vre);

		$qry_src_loop = sisplet_query("SELECT * FROM srv_loop WHERE if_id IN (".$qry_cache_if_id.")", self::$src_connect_db);
		$arr_src['srv_loop'] = self::sql2array($qry_src_loop);

		$qry_src_loop_vre = sisplet_query("SELECT * FROM srv_loop_vre WHERE if_id IN (".$qry_cache_if_id.")", self::$src_connect_db);
		$arr_src['srv_loop_vre'] = self::sql2array($qry_src_loop_vre);

		$qry_src_loop_data = sisplet_query("SELECT * FROM srv_loop_data WHERE if_id IN (".$qry_cache_if_id.")", self::$src_connect_db);
		$arr_src['srv_loop_data'] = self::sql2array($qry_src_loop_data);

		$qry_src_branching = sisplet_query("SELECT * FROM srv_branching WHERE ank_id = '".self :: $src_survey."' ".$hierarhija_not_in, self :: $src_connect_db);
		$arr_src['srv_branching'] = self::sql2array($qry_src_branching);
		
		
		// Vklopljeni moduli in njihove nastavitve
		$qry_src_anketa_module = sisplet_query("SELECT * FROM srv_anketa_module WHERE ank_id = '".self :: $src_survey."'", self :: $src_connect_db);
		$arr_src['srv_anketa_module'] = self::sql2array($qry_src_anketa_module);
		
		$qry_src_chat_settings = sisplet_query("SELECT * FROM srv_chat_settings WHERE ank_id = '".self :: $src_survey."'", self :: $src_connect_db);
		$arr_src['srv_chat_settings'] = self::sql2array($qry_src_chat_settings);
		
		$qry_src_panel_settings = sisplet_query("SELECT * FROM srv_panel_settings WHERE ank_id = '".self :: $src_survey."'", self :: $src_connect_db);
		$arr_src['srv_panel_settings'] = self::sql2array($qry_src_panel_settings);
		
		$qry_src_quiz_settings = sisplet_query("SELECT * FROM srv_quiz_settings WHERE ank_id = '".self :: $src_survey."'", self :: $src_connect_db);
		$arr_src['srv_quiz_settings'] = self::sql2array($qry_src_quiz_settings);
		
		$qry_src_quiz_vrednost = sisplet_query("SELECT v.* FROM srv_quiz_vrednost v WHERE v.spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
		$arr_src['srv_quiz_vrednost'] = self::sql2array($qry_src_quiz_vrednost);
		
		$qry_src_slideshow_settings = sisplet_query("SELECT * FROM srv_slideshow_settings WHERE ank_id = '".self :: $src_survey."'", self :: $src_connect_db);
		$arr_src['srv_slideshow_settings'] = self::sql2array($qry_src_slideshow_settings);

		
		if ( $data ) {

			// pripravimo si se seznam respondentov
			$qry_cache_usr_id = self::prepareSubquery( sisplet_query("SELECT id FROM srv_user WHERE ank_id = '".self::$src_survey."'", self::$src_connect_db) );

			$arr_src['data'] = true;

			$qry_src_user = sisplet_query("SELECT * FROM srv_user WHERE ank_id  = '".self::$src_survey."'", self :: $src_connect_db);
			$arr_src['srv_user'] = self::sql2array($qry_src_user);

			$qry_src_data_checkgrid = sisplet_query("SELECT * FROM srv_data_checkgrid WHERE spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
			$arr_src['srv_data_checkgrid'] = self::sql2array($qry_src_data_checkgrid);
			
			$qry_src_data_checkgrid_active = sisplet_query("SELECT * FROM srv_data_checkgrid_active WHERE spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
			$arr_src['srv_data_checkgrid_active'] = self::sql2array($qry_src_data_checkgrid_active);

			$qry_src_data_glasovanje = sisplet_query("SELECT * FROM srv_data_glasovanje WHERE spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
			$arr_src['srv_data_glasovanje'] = self::sql2array($qry_src_data_glasovanje);

			$qry_src_data_grid = sisplet_query("SELECT * FROM srv_data_grid WHERE spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
			$arr_src['srv_data_grid'] = self::sql2array($qry_src_data_grid);

			$qry_src_data_grid_active = sisplet_query("SELECT * FROM srv_data_grid_active WHERE spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
			$arr_src['srv_data_grid_active'] = self::sql2array($qry_src_data_grid_active);

			$qry_src_data_rating = sisplet_query("SELECT * FROM srv_data_rating WHERE spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
			$arr_src['srv_data_rating'] = self::sql2array($qry_src_data_rating);

			$qry_src_data_text = sisplet_query("SELECT * FROM srv_data_text WHERE spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
			$arr_src['srv_data_text'] = self::sql2array($qry_src_data_text);
			
			$qry_src_data_text_active = sisplet_query("SELECT * FROM srv_data_text_active WHERE spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
			$arr_src['srv_data_text_active'] = self::sql2array($qry_src_data_text_active);

			$qry_src_data_textgrid = sisplet_query("SELECT * FROM srv_data_textgrid WHERE spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
			$arr_src['srv_data_textgrid'] = self::sql2array($qry_src_data_textgrid);
			
			$qry_src_data_textgrid_active = sisplet_query("SELECT * FROM srv_data_textgrid_active WHERE spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
			$arr_src['srv_data_textgrid_active'] = self::sql2array($qry_src_data_textgrid_active);

			$qry_src_data_upload = sisplet_query("SELECT * FROM srv_data_upload WHERE ank_id='".self::$src_survey."'", self :: $src_connect_db);
			$arr_src['srv_data_upload'] = self::sql2array($qry_src_data_upload);

			$qry_src_data_vrednost = sisplet_query("SELECT * FROM srv_data_vrednost WHERE spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
			$arr_src['srv_data_vrednost'] = self::sql2array($qry_src_data_vrednost);

			$qry_src_data_vrednost_active = sisplet_query("SELECT * FROM srv_data_vrednost_active WHERE spr_id IN (".$qry_cache_spr_id.")", self :: $src_connect_db);
			$arr_src['srv_data_vrednost_active'] = self::sql2array($qry_src_data_vrednost_active);

			$qry_src_user_grupa = sisplet_query("SELECT * FROM srv_user_grupa WHERE usr_id IN (".$qry_cache_usr_id.")", self::$src_connect_db);
			$arr_src['srv_user_grupa'] = self::sql2array($qry_src_user_grupa);

			$qry_src_user_grupa_active = sisplet_query("SELECT * FROM srv_user_grupa_active WHERE usr_id IN (".$qry_cache_usr_id.")", self::$src_connect_db);
			$arr_src['srv_user_grupa_active'] = self::sql2array($qry_src_user_grupa_active);
			
			$qry_src_data_map = sisplet_query("SELECT * FROM srv_data_map WHERE spr_id IN (".$qry_cache_spr_id.") AND ank_id='".self::$src_survey."' AND usr_id IN (".$qry_cache_usr_id.")", self :: $src_connect_db);
			$arr_src['srv_data_map'] = self::sql2array($qry_src_data_map);
			
			$qry_src_data_heatmap = sisplet_query("SELECT * FROM srv_data_heatmap WHERE spr_id IN (".$qry_cache_spr_id.") AND ank_id='".self::$src_survey."' AND usr_id IN (".$qry_cache_usr_id.")", self :: $src_connect_db);
			$arr_src['srv_data_heatmap'] = self::sql2array($qry_src_data_heatmap);
			
 			/*$qry_src_data_hotspot_regions = sisplet_query("SELECT * FROM srv_hotspot_regions WHERE spr_id IN (".$qry_cache_spr_id.") AND ank_id='".self::$src_survey."' AND usr_id IN (".$qry_cache_usr_id.")", self :: $src_connect_db);
			$arr_src['srv_data_hotspot_regions'] = self::sql2array($qry_src_data_hotspot_regions); */

		}

		return $arr_src;
	}

	// Vrne implodan rezultat querija primeren za vstavit v subquery - to delamo ponavadi zarad performanca
	static function prepareSubquery ($sql_resorce) {
		
		if (!$sql_resorce) echo 'sub-q-error: '.mysqli_error($GLOBALS['connect_db']);
		
		$src_prepare = array();
		while ($row_src = mysqli_fetch_row($sql_resorce)) {
			$src_prepare[] = $row_src[0];
		}
		
		if (count($src_prepare) == 0)
			return 'null';
			
		return implode(',', $src_prepare);
	}
	
	static function doCopy( $data = false ){
		global $global_user_id;
		
		if ( self::$source_array == null ) {
			$arr_src = self::getArraySource( $data );
        } 
        else {
			$arr_src = self::$source_array;
		}

		if (!(self::$dest_connect_db != null)) {
			self :: $errors[] = "Mandatory data missing!";
        } 
        else {
						
			// iz izvorne ankete preberemo vsa polja
			if ( count($arr_src['srv_anketa']) > 0) {
                
                // predefinirana polja (vsilimo vrednosti)
				$pre_set = array('id' => "NULL",
								'backup' => "'0'",
								'active' => "'0'",
								'locked' => "'0'",
								'db_table' => "'1'",
								'insert_uid' => "'".$global_user_id."'",
								'insert_time' => "NOW()",
								'edit_uid' => "'".$global_user_id."'",
								'edit_time' => "NOW()",
								'folder' => "'1'",
								'forum' => "'0'",
								'thread' => "'0'",
								'old_email_style' => "'0'");

				# user_id ni enak če je anketa na drugem strežniku zato v tem primeru damo -1 da na drugem strežniku vemo da je to kopija od drugod
				if (self::$destSite != 0) {
					$pre_set['insert_uid'] = "'-1'"; 
					$pre_set['edit_uid'] = "'-1'";
				}
				
				$new_survey_ids = self :: preformCopyTable('srv_anketa', 'id', $arr_src['srv_anketa'], $pre_set);
				
				if ( isset($new_survey_ids[$arr_src['srv_anketa'][0]['id']]) && $new_survey_ids[$arr_src['srv_anketa'][0]['id']] != $arr_src['srv_anketa'][0]['id'] ) {
					// novo anketo smo uspesno skreirali
					$new_survey_id = $new_survey_ids[$arr_src['srv_anketa'][0]['id']];
					
					// tabela srv_alert
					$pre_set = array('ank_id' => "'".$new_survey_id."'",
									'finish_respondent_if'	=> "NULL",
									'finish_respondent_cms_if'	=> "NULL",
									'finish_other_if'	=> "NULL");
					$new_alert_ids = self :: preformCopyTable('srv_alert', null, $arr_src['srv_alert'], $pre_set);
					
					// tabela srv_call_setting
					$pre_set = array('survey_id' => "'".$new_survey_id."'");
					$new_call_setting_ids = self::preformCopyTable('srv_call_setting', null, $arr_src['srv_call_setting'], $pre_set);

					// tabela srv_language
					$pre_set = array('ank_id' => "'".$new_survey_id."'");
					$new_language_ids = self::preformCopyTable('srv_language', null, $arr_src['srv_language'], $pre_set);
					
					
					// tale zgornji if pogoj ne vem ce takole dela
					if (self::$destSite == 0 && self::$src_survey > 0) { // to je smiselno samo na istem strezniku, kjer so user_id-ji enaki 
						// dostop userjev, tabela srv_dostop
						$pre_set = array('ank_id' => "'".$new_survey_id."'");
						$new_dostop_ids = self :: preformCopyTable('srv_dostop', null, $arr_src['srv_dostop'], $pre_set);
						
						// dostop userjev do prevodov, tabela srv_dostop_language
						$pre_set = array('ank_id' => "'".$new_survey_id."'");
						$new_dostop_language_ids = self :: preformCopyTable('srv_dostop_language', null, $arr_src['srv_dostop_language'], $pre_set);
					
					// kopiranje iz datoteke (vse user IDje nastavimo na ID userja, ki uploada)
					} elseif ( self::$src_survey == -1 ) {
						
						$new_users_ids = array();
                        
                        foreach ($arr_src['srv_dostop'] AS $dostop) {
							$new_users_ids[$dostop['uid']] = $global_user_id;
						}
						foreach ($arr_src['srv_dostop_language'] AS $dostop) {
							$new_users_ids[$dostop['uid']] = $global_user_id;
						}
						
						// da damo dostop samo 1 userju
						$tmp = $arr_src['srv_dostop'][0];
						$arr_src['srv_dostop'] = null;
						$arr_src['srv_dostop'][0] = $tmp;
						
						if ( count($arr_src['srv_dostop_language']) > 0 ) {							
							$tmp = $arr_src['srv_dostop_language'][0];
							$arr_src['srv_dostop_language'] = null;
							$arr_src['srv_dostop_language'][0] = $tmp;
						}
						
						// dostop userjev, tabela srv_dostop
						$pre_set = array('ank_id' => "'".$new_survey_id."'",
										'uid' => array('field'=>'uid', 'from'=>$new_users_ids));
						$new_dostop_ids = self :: preformCopyTable('srv_dostop', null, $arr_src['srv_dostop'], $pre_set);
						
						// dostop userjev do prevodov, tabela srv_dostop_language
						$pre_set = array('ank_id' => "'".$new_survey_id."'",
										'uid' => array('field'=>'uid', 'from'=>$new_users_ids));
						$new_dostop_language_ids = self :: preformCopyTable('srv_dostop_language', null, $arr_src['srv_dostop_language'], $pre_set);
						
					// ce kopiramo na drug streznik, probamo najti iste uporabnike na drugem strezniku 
					} else {
						$sql_dostop = sisplet_query("SELECT u.id, u.email FROM srv_dostop d, users u WHERE u.id=d.uid AND d.ank_id = '".self::$src_survey."'", self::$src_connect_db);
						$new_users_ids = array();
                        while ($row_dostop = mysqli_fetch_array($sql_dostop)) {
							$sql_dostop_new = sisplet_query("SELECT id FROM users WHERE email='$row_dostop[email]'");
                            
                            if (mysqli_num_rows($sql_dostop_new) > 0) {
								$row_dostop_new = mysqli_fetch_array($sql_dostop_new);
								$new_users_ids[$row_dostop['id']] = $row_dostop_new['id'];
							}
						}
                        
                        $sql_dostop = sisplet_query("SELECT u.id, u.email FROM srv_dostop_language d, users u WHERE u.id=d.uid AND d.ank_id = '".self::$src_survey."'", self::$src_connect_db);
						while ($row_dostop = mysqli_fetch_array($sql_dostop)) {
							$sql_dostop_new = sisplet_query("SELECT id FROM users WHERE email='$row_dostop[email]'");
                            
                            if (mysqli_num_rows($sql_dostop_new) > 0) {
								$row_dostop_new = mysqli_fetch_array($sql_dostop_new);
								$new_users_ids[$row_dostop['id']] = $row_dostop_new['id'];
							}
						}
						
						// dostop userjev, tabela srv_dostop
						$pre_set = array('ank_id' => "'".$new_survey_id."'",
										'uid' => array('field'=>'uid', 'from'=>$new_users_ids));
						$new_dostop_ids = self :: preformCopyTable('srv_dostop', null, $arr_src['srv_dostop'], $pre_set);
						
						// dostop userjev do prevodov, tabela srv_dostop_language
						$pre_set = array('ank_id' => "'".$new_survey_id."'",
										'uid' => array('field'=>'uid', 'from'=>$new_users_ids));
						$new_dostop_language_ids = self :: preformCopyTable('srv_dostop_language', null, $arr_src['srv_dostop_language'], $pre_set);
					}
	
					
					// grupe - strani, tabela srv_grupa
					$pre_set = array('id' => "NULL",
									'ank_id' => "'".$new_survey_id."'");
					$new_grupa_ids = self :: preformCopyTable('srv_grupa', 'id', $arr_src['srv_grupa'], $pre_set);
	
					$new_grupa_ids[-1] = -1;	// spremenljivke v knjiznici imajo -1 (ampak tega tukaj niti nebi smel bit...)
                    $new_grupa_ids[-2] = -2;	// spremenljivke v multiple gridu imajo gru_id -2
					$new_grupa_ids[0] = 0;		// pri srv_user_grupa je 0 za prvo stran
					
					$new_spremenljivke_ids = array();
					if (count($new_grupa_ids) > 0) {
						foreach ($new_grupa_ids as $old_grupa_id => $new_grupa_id) {

							// spremenljivke, tabela srv_spremenljivka
							$src_srv_spremenljivka = self::arrayfilter($arr_src['srv_spremenljivka'], 'gru_id', $old_grupa_id);

							$pre_set = array('id' => "NULL",
											'gru_id' => "'".$new_grupa_id."'",
											'thread' => "'0'");
							$tmp_spremenljivke_ids = self :: preformCopyTable('srv_spremenljivka', 'id', $src_srv_spremenljivka, $pre_set);
                            
                            // shranimo stare in nove id-je spremenljivk
							if ( is_countable($tmp_spremenljivke_ids) && count($tmp_spremenljivke_ids) > 0 )
                            
                            foreach ($tmp_spremenljivke_ids as $key => $value)
								$new_spremenljivke_ids[$key] = $value;
						}
					}

					
					// gridi - srv_grid
					$new_grid_ids = array();
					if (count($new_spremenljivke_ids) > 0) {
						foreach ($new_spremenljivke_ids AS $old_spremenljivka_id => $new_spremenljivka_id) {
                            
                            // gridi, tabela srv_grid
							$src_srv_grid = self::arrayfilter($arr_src['srv_grid'], 'spr_id', $old_spremenljivka_id);
							$pre_set = array('spr_id' => "'".$new_spremenljivka_id."'");
							$tmp_grid_ids = self :: preformCopyTable('srv_grid', 'variable', $src_srv_grid, $pre_set);
							
							// shranimo stare in nove id-je spremenljivk
							if ( count($tmp_grid_ids) > 0 )
                            
                            foreach ($tmp_grid_ids as $key => $value)
								$new_grid_ids[$new_spremenljivka_id][$key] = $value;
							
						}
					}
                    
					// srv_grid_multiple
					$pre_set = array('ank_id' => "'".$new_survey_id."'",
									'parent' => array('field'=>'parent','from'=>$new_spremenljivke_ids), # uporabimo dinamično preslikavo iz stare spr_id na novo spr_id za vsako vrstico
									'spr_id' => array('field'=>'spr_id','from'=>$new_spremenljivke_ids)); # uporabimo dinamično preslikavo iz stare spr_id na novo spr_id za vsako vrstico
					$new_grid_multiple_ids = self::preformCopyTable('srv_grid_multiple', null, $arr_src['srv_grid_multiple'], $pre_set);
                    
                    
					// srv_language_spremenljivka
					$pre_set = array('ank_id' => "'".$new_survey_id."'",
									'spr_id' => array('field'=>'spr_id','from'=>$new_spremenljivke_ids)); # uporabimo dinamično preslikavo iz stare spr_id na novo spr_id za vsako vrstico
					$new_language_spremenljivka_ids = self::preformCopyTable('srv_language_spremenljivka', null, $arr_src['srv_language_spremenljivka'], $pre_set);
					
					// srv_language_grid
					$pre_set = array('ank_id' => "'".$new_survey_id."'",
									'spr_id' => array('field'=>'spr_id','from'=>$new_spremenljivke_ids)); # uporabimo dinamično preslikavo iz stare spr_id na novo spr_id za vsako vrstico
					$new_language_grid_ids = self::preformCopyTable('srv_language_grid', null, $arr_src['srv_language_grid'], $pre_set);
                    
                    
					// srv_missing_values
					$pre_set = array('sid' => "'".$new_survey_id."'");
					$new_missing_values_ids = self::preformCopyTable('srv_missing_values', null, $arr_src['srv_missing_values'], $pre_set);			
                    
                    
                    // vrednosti - prestavljeno pod srv_if da se ne porusijo notranji pogoji! Patrik - zakaj je bilo to prestavljeno višje??
					/*$new_vrednosti_ids = array();
					if ( count($new_spremenljivke_ids) > 0) {              
                        foreach( $new_spremenljivke_ids AS $old_spremenljivka_id => $new_spremenljivka_id) {
                            
                            // vrednosti, tabela srv_vrednost
							$src_srv_vrednost = self::arrayfilter($arr_src['srv_vrednost'], 'spr_id', $old_spremenljivka_id);
							$pre_set = array('id' => "NULL",
											'spr_id' => array('field'=>'spr_id', 'from'=>$new_spremenljivke_ids),
											'vre_id' => array('field'=>'vre_id', 'from'=>$new_vrednosti_ids));
							$tmp_vrednosti_ids = self :: preformCopyTable('srv_vrednost', 'id', $src_srv_vrednost, $pre_set);
                            
                            // shranimo stare in nove id-je spremenljivk
							if ( count($tmp_vrednosti_ids) > 0 )
                            
                            foreach ($tmp_vrednosti_ids as $key => $value){
								$new_vrednosti_ids[$key] = $value;
							}							
						}
					}*/

	
					// splosne nastavitve ankete, tabela srv_survey_misc
					$pre_set = array('sid' => "'".$new_survey_id."'");
					$new_misc_ids = self :: preformCopyTable('srv_survey_misc', null, $arr_src['srv_survey_misc'], $pre_set);
                    
                    
					// glasovanje, tabela srv_glasovanje
					$pre_set = array('ank_id' => "'".$new_survey_id."'",
									'spr_id' => array('field'=>'spr_id','from'=>$new_spremenljivke_ids)); # uporabimo dinamično preslikavo iz stare spr_id na novo spr_id za vsako vrstico
					$new_glasovanje_ids = self :: preformCopyTable('srv_glasovanje', null, $arr_src['srv_glasovanje'], $pre_set);
                    
                    
                    // IF-im tabela srv_if
                    // Mora biti pred kopiranjem srv_vrednost, ker drugace wswe porusijo notranji pogoji!
					$new_if_ids = array ();
					$srv_if = $arr_src['srv_if'];

					foreach ($srv_if AS $row) {
						// IF-i, tabela srv_if
						//$qry_src_if = sisplet_query("SELECT * FROM srv_if WHERE id = '".$row[id]."'", self :: $src_connect_db);
						$qry_src_if = self::arrayfilter($arr_src['srv_if'], 'id', $row['id']);
						$pre_set = array('id' => "NULL");
						$tmp_if_ids = self :: preformCopyTable('srv_if', 'id', $qry_src_if, $pre_set);
						
						$new_if_ids += $tmp_if_ids; 
					}
                    
                    // vrednosti
					$new_vrednosti_ids = array();
					if ( count($new_spremenljivke_ids) > 0) {
						foreach( $new_spremenljivke_ids AS $old_spremenljivka_id => $new_spremenljivka_id) {
                            
                            // vrednosti, tabela srv_vrednost
							$src_srv_vrednost = self::arrayfilter($arr_src['srv_vrednost'], 'spr_id', $old_spremenljivka_id);
							$pre_set = array('id' => "NULL",
											'spr_id' => "'".$new_spremenljivka_id."'",
											'if_id' => array('field'=>'if_id', 'from'=>$new_if_ids));
							$tmp_vrednosti_ids = self :: preformCopyTable('srv_vrednost', 'id', $src_srv_vrednost, $pre_set);
                            
                            // shranimo stare in nove id-je spremenljivk
							if ( count($tmp_vrednosti_ids) > 0 )
                            
                            foreach ($tmp_vrednosti_ids as $key => $value)
								$new_vrednosti_ids[$key] = $value;
							
						}
                    } 
                    
                    // srv_calculation
					// za vsak condition , če je spr_id > 0 priredimo novo, če ne damo enako (-1,-2)
					if (count($new_spremenljivke_ids) > 0) {
                        
                        foreach ($new_spremenljivke_ids AS $old_spremenljivka_id => $new_spremenljivka_id) {
                            $srv_calculation = self::arrayfilter($arr_src['srv_calculation'], 'cnd_id', -$old_spremenljivka_id);

							foreach ($srv_calculation AS $row) {
							
								$new_spr = $row['spr_id'] > 0
									? $new_spremenljivke_ids[$row['spr_id']] #naredimo preslikavo
									: $row['spr_id']; # uporabimo staro vrednost
								$new_vre = $row['vre_id'] > 0
									? $new_vrednosti_ids[$row['vre_id']]
									: $row['vre_id'];
								
								$src_srv_calculation = self::arrayfilter($arr_src['srv_calculation'], 'id', $row['id']);
								$pre_set = array('id' => "NULL",
											'cnd_id' => -$new_spremenljivka_id,
											'spr_id' => $new_spr,
											'vre_id' => $new_vre);
								$new_calculation_ids = self::preformCopyTable('srv_calculation', 'id', $src_srv_calculation, $pre_set);
							}
						}
                    }
                    
                    
					// srv_hotspot_regions
					$pre_set = array('id' => "NULL",
									'spr_id' => array('field'=>'spr_id', 'from'=>$new_spremenljivke_ids),
									'vre_id' => array('field'=>'vre_id', 'from'=>$new_vrednosti_ids));
					$hotspot_regions = self::preformCopyTable('srv_hotspot_regions', 'id', $arr_src['srv_hotspot_regions'], $pre_set);
                                       
                    
                    // srv_vrednost_map
					$pre_set = array('id' => "NULL",
									'spr_id' => array('field'=>'spr_id', 'from'=>$new_spremenljivke_ids),
									'vre_id' => array('field'=>'vre_id', 'from'=>$new_vrednosti_ids));
					self::preformCopyTable('srv_vrednost_map', 'id', $arr_src['srv_vrednost_map'], $pre_set);
                    
                    
					// srv_language_vrednost
					$pre_set = array('ank_id' => "'".$new_survey_id."'",
									'vre_id' => array('field'=>'vre_id','from'=>$new_vrednosti_ids)); # uporabimo dinamično preslikavo iz stare vre_id na novo vre_id za vsako vrstico
					$new_language_vrednost_ids = self::preformCopyTable('srv_language_vrednost', null, $arr_src['srv_language_vrednost'], $pre_set);
					
					
					// srv_condition
					$pre_set = array('id' => "NULL",
									'if_id' => array('field'=>'if_id', 'from'=>$new_if_ids),
									'spr_id' => array('field'=>'spr_id', 'from'=>$new_spremenljivke_ids),
									'vre_id' => array('field'=>'vre_id', 'from'=>$new_vrednosti_ids));
					$condition = self::preformCopyTable('srv_condition', 'id', $arr_src['srv_condition'], $pre_set);
					
	
					// condtition grid, tabela srv_condition_grid
					if (count($condition) > 0) {
						foreach ($condition AS $orig => $bckp) {
							// condtition grid, tabela srv_condition_grid
							//$qry_src_condition_grid = sisplet_query("SELECT * FROM srv_condition_grid WHERE cond_id = '".$orig."'", self :: $src_connect_db);
							$src_srv_condition_grid = self::arrayfilter($arr_src['srv_condition_grid'], 'cond_id', $orig);
							$pre_set = array('id'=>"NULL",
											'cond_id' => "'".$bckp."'");
							$new_condition_grid_ids = self :: preformCopyTable('srv_condition_grid', 'id', $src_srv_condition_grid, $pre_set);
						
						}
					}
	
					// condtition vrednost, tabela srv_condition_vre
					if (count($condition) > 0) {
						foreach ($condition AS $orig => $bckp) {
							//$qry_src_condition_vre = sisplet_query("SELECT * FROM srv_condition_vre WHERE cond_id = '$orig'",  self :: $src_connect_db);
							$src_srv_condition_vre = self::arrayfilter($arr_src['srv_condition_vre'], 'cond_id', $orig);
							$pre_set = array('cond_id' => $bckp,
											'vre_id' => array('field'=>'vre_id', 'from'=>$new_vrednosti_ids));
							self::preformCopyTable('srv_condition_vre', null, $src_srv_condition_vre, $pre_set);
						}
					}
	
					// srv_loop
					$pre_set = array('ank_id' => "'".$new_survey_id."'",
									'if_id' => array('field'=>'if_id','from'=>$new_if_ids), # uporabimo dinamično preslikavo iz stare if_id na novo if_id za vsako vrstico
									'spr_id' => array('field'=>'spr_id','from'=>$new_spremenljivke_ids)); # uporabimo dinamično preslikavo iz stare spr_id na novo spr_id za vsako vrstico
					$new_loop_ids = self::preformCopyTable('srv_loop', null, $arr_src['srv_loop'], $pre_set);
					
					// srv_loop_vre
					$pre_set = array('ank_id' => "'".$new_survey_id."'",
									'if_id' => array('field'=>'if_id','from'=>$new_if_ids), # uporabimo dinamično preslikavo iz stare if_id na novo if_id za vsako vrstico
									'vre_id' => array('field'=>'vre_id','from'=>$new_vrednosti_ids)); # uporabimo dinamično preslikavo iz stare spr_id na novo spr_id za vsako vrstico
					$new_loop_vre_ids = self::preformCopyTable('srv_loop_vre', null, $arr_src['srv_loop_vre'], $pre_set);
					
					// srv_loop_data
					$pre_set = array('ank_id' => "'".$new_survey_id."'",
									'id' => "NULL",
									'if_id' => array('field'=>'if_id','from'=>$new_if_ids), # uporabimo dinamično preslikavo iz stare if_id na novo if_id za vsako vrstico
									'vre_id' => array('field'=>'vre_id','from'=>$new_vrednosti_ids)); # uporabimo dinamično preslikavo iz stare spr_id na novo spr_id za vsako vrstico
					$new_loop_data_ids = self::preformCopyTable('srv_loop_data', 'id', $arr_src['srv_loop_data'], $pre_set);
                    
                    
					// srv_branching
					$pre_set = array('ank_id' => "'".$new_survey_id."'",
									'parent' => array('field'=>'parent', 'from' => $new_if_ids),
									'element_spr' => array('field'=>'element_spr', 'from' => $new_spremenljivke_ids),
									'element_if' => array('field' => 'element_if', 'from' => $new_if_ids));
					self::preformCopyTable('srv_branching', null, $arr_src['srv_branching'], $pre_set);
    
                    
					// srv_calculation
					// za vsak condition , če je spr_id > 0 priredimo novo, če ne damo enako (-1,-2)
					if (count($condition) > 0) {
						foreach ($condition AS $orig => $bckp) {
							//$qry_src_calculation = sisplet_query("SELECT * FROM srv_calculation WHERE cnd_id = '$orig'",  self :: $src_connect_db);
							$src_srv_calculation = self::arrayfilter($arr_src['srv_calculation'], 'cnd_id', $orig);
							$pre_set = array('id' => "NULL",
											'cnd_id' => $bckp,
											'spr_id' => array('field'=>'spr_id', 'from'=>$new_spremenljivke_ids),
											'vre_id' => array('field'=>'vre_id', 'from'=>$new_vrednosti_ids));
							self::preformCopyTable('srv_calculation', 'id', $src_srv_calculation, $pre_set);							
						}
					}
					
					
					// srv_anketa_module - skopiramo samo nekatere module
					$copy_modules = array('social_network', 'quiz', 'uporabnost', 'slideshow', 'chat', 'panel');
					foreach($copy_modules as $copy_module){					
						$src_srv_modules = self::arrayfilter($arr_src['srv_anketa_module'], 'modul', $copy_module);
						$pre_set = array('ank_id' => "'".$new_survey_id."'");
						self::preformCopyTable('srv_anketa_module', null, $src_srv_modules, $pre_set);
					}
					
					// srv_chat_settings
					$pre_set = array('ank_id' => "'".$new_survey_id."'");
					self::preformCopyTable('srv_chat_settings', null, $arr_src['srv_chat_settings'], $pre_set);
					
					// srv_panel_settings
					$pre_set = array('ank_id' => "'".$new_survey_id."'");
					self::preformCopyTable('srv_panel_settings', null, $arr_src['srv_panel_settings'], $pre_set);
					
					// srv_slideshow_settings
					$pre_set = array('ank_id' => "'".$new_survey_id."'");
					self::preformCopyTable('srv_slideshow_settings', null, $arr_src['srv_slideshow_settings'], $pre_set);
					
					// srv_quiz_settings
					$pre_set = array('ank_id' => "'".$new_survey_id."'");
					self::preformCopyTable('srv_quiz_settings', null, $arr_src['srv_quiz_settings'], $pre_set);
					
					// srv_quiz_vrednost
					$pre_set = array('spr_id' => array('field'=>'spr_id', 'from'=>$new_spremenljivke_ids),
									'vre_id' => array('field'=>'vre_id', 'from'=>$new_vrednosti_ids));
					self::preformCopyTable('srv_quiz_vrednost', null, $arr_src['srv_quiz_vrednost'], $pre_set);
						
					
					/**
					* PODATKI
					*/
					if ( $arr_src['data'] ) {
					    $ip = GetIP();
						
						// srv_user
						$new_user_ids = array();
						foreach ( $arr_src['srv_user'] AS $row_array ) {
							
							do {
								$rand = md5(mt_rand(1, mt_getrandmax()).'@'.$ip);
								$sql = sisplet_query("SELECT id FROM srv_user WHERE cookie = '$rand'");
							} while (mysqli_num_rows($sql) > 0);
						
							$pre_set = array('id'	=> "NULL", 
											'cookie' => "'$rand'",
											'pass' => "'".substr($rand, 0, 6)."'",
											'ank_id' => "'".$new_survey_id."'");
							$src_srv_user = self::arrayfilter($arr_src['srv_user'], 'id', $row_array['id']);
							$tmp_user_ids = self::preformCopyTable('srv_user', 'id', $src_srv_user, $pre_set);
							if ( count($tmp_user_ids) > 0 )
								foreach ($tmp_user_ids as $key => $value)
									$new_user_ids[$key] = $value;
						}
						
						// srv_data_checkgrid - VSE VEDNO KOPIRAMO V ACTIVE TABELE
						$pre_set = array('spr_id' => array('field'=>'spr_id', 'from' => $new_spremenljivke_ids),
										'vre_id' => array('field'=>'vre_id', 'from' => $new_vrednosti_ids),
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids),
										'grd_id' => array('field'=>'grd_id', 'from' => $new_grid_ids),
										'loop_id' => array('field'=>'loop_id', 'from' => $new_loop_data_ids));
						self::preformCopyTable('srv_data_checkgrid_active', null, $arr_src['srv_data_checkgrid'], $pre_set);
						
						// srv_data_checkgrid_active
						$pre_set = array('spr_id' => array('field'=>'spr_id', 'from' => $new_spremenljivke_ids),
										'vre_id' => array('field'=>'vre_id', 'from' => $new_vrednosti_ids),
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids),
										'grd_id' => array('field'=>'grd_id', 'from' => $new_grid_ids),
										'loop_id' => array('field'=>'loop_id', 'from' => $new_loop_data_ids));
						self::preformCopyTable('srv_data_checkgrid_active', null, $arr_src['srv_data_checkgrid_active'], $pre_set);
						
						// srv_data_glasovanje
						$pre_set = array('spr_id' => array('field'=>'spr_id', 'from' => $new_spremenljivke_ids),
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids));
						self::preformCopyTable('srv_data_glasovanje', null, $arr_src['srv_data_glasovanje'], $pre_set);
						
						// srv_data_grid - VSE VEDNO KOPIRAMO V ACTIVE TABELE
						$pre_set = array('spr_id' => array('field'=>'spr_id', 'from' => $new_spremenljivke_ids),
										'vre_id' => array('field'=>'vre_id', 'from' => $new_vrednosti_ids),
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids),
										'grd_id' => array('field'=>'grd_id', 'from' => $new_grid_ids),
										'loop_id' => array('field'=>'loop_id', 'from' => $new_loop_data_ids));
						self::preformCopyTable('srv_data_grid_active', null, $arr_src['srv_data_grid'], $pre_set);
						
						// srv_data_grid_active
						$pre_set = array('spr_id' => array('field'=>'spr_id', 'from' => $new_spremenljivke_ids),
										'vre_id' => array('field'=>'vre_id', 'from' => $new_vrednosti_ids),
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids),
										'grd_id' => array('field'=>'grd_id', 'from' => $new_grid_ids),
										'loop_id' => array('field'=>'loop_id', 'from' => $new_loop_data_ids));
						self::preformCopyTable('srv_data_grid_active', null, $arr_src['srv_data_grid_active'], $pre_set);
						
						// srv_data_rating
						$pre_set = array('spr_id' => array('field'=>'spr_id', 'from' => $new_spremenljivke_ids),
										'vre_id' => array('field'=>'vre_id', 'from' => $new_vrednosti_ids),
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids),
										'loop_id' => array('field'=>'loop_id', 'from' => $new_loop_data_ids));
						self::preformCopyTable('srv_data_rating', null, $arr_src['srv_data_rating'], $pre_set);
						
						// srv_data_text - VSE VEDNO KOPIRAMO V ACTIVE TABELE
						$pre_set = array('id' => "NULL",
										'spr_id' => array('field'=>'spr_id', 'from' => $new_spremenljivke_ids),
										'vre_id' => array('field'=>'vre_id', 'from' => $new_vrednosti_ids),
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids),
										'loop_id' => array('field'=>'loop_id', 'from' => $new_loop_data_ids));
						self::preformCopyTable('srv_data_text_active', 'id', $arr_src['srv_data_text'], $pre_set);

						// srv_data_text_active
						$pre_set = array('id' => "NULL",
										'spr_id' => array('field'=>'spr_id', 'from' => $new_spremenljivke_ids),
										'vre_id' => array('field'=>'vre_id', 'from' => $new_vrednosti_ids),
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids),
										'loop_id' => array('field'=>'loop_id', 'from' => $new_loop_data_ids));
						self::preformCopyTable('srv_data_text_active', 'id', $arr_src['srv_data_text_active'], $pre_set);
						
						// srv_data_textgrid - VSE VEDNO KOPIRAMO V ACTIVE TABELE
						$pre_set = array('spr_id' => array('field'=>'spr_id', 'from' => $new_spremenljivke_ids),
										'vre_id' => array('field'=>'vre_id', 'from' => $new_vrednosti_ids),
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids),
										'grd_id' => array('field'=>'grd_id', 'from' => $new_grid_ids),
										'loop_id' => array('field'=>'loop_id', 'from' => $new_loop_data_ids));
						self::preformCopyTable('srv_data_textgrid_active', null, $arr_src['srv_data_textgrid'], $pre_set);

						// srv_data_textgrid_active
						$pre_set = array('spr_id' => array('field'=>'spr_id', 'from' => $new_spremenljivke_ids),
										'vre_id' => array('field'=>'vre_id', 'from' => $new_vrednosti_ids),
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids),
										'grd_id' => array('field'=>'grd_id', 'from' => $new_grid_ids),
										'loop_id' => array('field'=>'loop_id', 'from' => $new_loop_data_ids));
						self::preformCopyTable('srv_data_textgrid_active', null, $arr_src['srv_data_textgrid_active'], $pre_set);
						
						// srv_data_upload
						$pre_set = array('ank_id' => "'".$new_survey_id."'",
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids));
						self::preformCopyTable('srv_data_upload', null, $arr_src['srv_data_upload'], $pre_set);
						
						// srv_data_vrednost - VSE VEDNO KOPIRAMO V ACTIVE TABELE
						$pre_set = array('spr_id' => array('field'=>'spr_id', 'from' => $new_spremenljivke_ids),
										'vre_id' => array('field'=>'vre_id', 'from' => $new_vrednosti_ids),
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids),
										'loop_id' => array('field'=>'loop_id', 'from' => $new_loop_data_ids));
						self::preformCopyTable('srv_data_vrednost_active', null, $arr_src['srv_data_vrednost'], $pre_set);
						
						// srv_data_vrednost_active
						$pre_set = array('spr_id' => array('field'=>'spr_id', 'from' => $new_spremenljivke_ids),
										'vre_id' => array('field'=>'vre_id', 'from' => $new_vrednosti_ids),
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids),
										'loop_id' => array('field'=>'loop_id', 'from' => $new_loop_data_ids));
						self::preformCopyTable('srv_data_vrednost_active', null, $arr_src['srv_data_vrednost_active'], $pre_set);
						
						// srv_user_grupa - VSE VEDNO KOPIRAMO V ACTIVE TABELE
						$pre_set = array('gru_id' => array('field'=>'gru_id', 'from' => $new_grupa_ids),
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids));
						self::preformCopyTable('srv_user_grupa_active', null, $arr_src['srv_user_grupa'], $pre_set);
						
						// srv_user_grupa_active
						$pre_set = array('gru_id' => array('field'=>'gru_id', 'from' => $new_grupa_ids),
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids));
						self::preformCopyTable('srv_user_grupa_active', null, $arr_src['srv_user_grupa_active'], $pre_set);
						
						// srv_data_map
						$pre_set = array('id' => "NULL",
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids),
										'spr_id' => array('field'=>'spr_id', 'from' => $new_spremenljivke_ids),
										'ank_id' => "'".$new_survey_id."'",										
										'loop_id' => array('field'=>'loop_id', 'from' => $new_loop_data_ids));
						self::preformCopyTable('srv_data_map', 'id', $arr_src['srv_data_map'], $pre_set);	

						// srv_data_heatmap
						$pre_set = array('id' => "NULL",
										'usr_id' => array('field'=>'usr_id', 'from' => $new_user_ids),
										'spr_id' => array('field'=>'spr_id', 'from' => $new_spremenljivke_ids),
										'ank_id' => "'".$new_survey_id."'",										
										'loop_id' => array('field'=>'loop_id', 'from' => $new_loop_data_ids));
						self::preformCopyTable('srv_data_heatmap', 'id', $arr_src['srv_data_heatmap'], $pre_set);							
						
                    }
                } 
                else {
					self :: $errors[] = "Survey could not be copied! Please contact web admin. (Error code: 2)";
				}
            } 
            else {
				self :: $errors[] = "Survey could not be copied! Source survey does not exist. Please contact web admin. (Error code: 1)";
			}
		}
       
		
		return $new_survey_id;
	}

	// query SHOW COLUMS si kesiramo, ker se lahko izvaja zelo pogosto na istih tabelah (in se zlo pozna)
	static $show_columns = array();
	
	/** Prekopira samo polja ki so v dest tabeli pred tem popravi $pre_set polja 
	 * 
	 * @param $table_name
	 * @param $id_field	(insert_id polje)
	 * @param $array_source - tole smo spremenili, da ne podamo sql querija, ampak array s podatki
	 * @param $pre_set

	 * @return array(oldId=>newId) Id-ji vpisanega zapisa
	 */
	static function preformCopyTable($table_name, $id_field, $array_source, $pre_set) {
		
		//if (!$array_source)
		//	self :: $errors[] = 'Qry src string error: ' . mysqli_error($GLOBALS['connect_db']);
		
		$result = null;
		if (isset(self::$show_columns[$table_name])) {
			$dest_table_fields = self::$show_columns[$table_name];
			mysqli_data_seek($dest_table_fields, 0);
		} else {
			self::$show_columns[$table_name] = sisplet_query("SHOW COLUMNS FROM ".$table_name, self::$dest_connect_db);
			$dest_table_fields = self::$show_columns[$table_name];
		}
		if (!$dest_table_fields) {
		    self :: $errors[] = 'Could not run query: ' . mysqli_error($GLOBALS['connect_db']);
		}

		if ( count($array_source) > 0 ) {
			// zloopamo skozi polja druge tabele in predpripravimo insert string polja
			if (mysqli_num_rows($dest_table_fields) > 0) {

				$insert_fields = '';
				$insert_fields_prefix = '';
				// vsebuje polja
			    while ($row_dest_table_fields = mysqli_fetch_assoc($dest_table_fields)) {
			    	// polja dodamo v insert string
			    	$dest_field = $row_dest_table_fields['Field'];
			    	$insert_fields .= $insert_fields_prefix . $dest_field;
			    	$insert_fields_prefix = ',';
			    }

				// zloopamo skozi zapise izvorne tabele
				// lahko updejtamo več zapisov, nove id-je damo v array, star_id=>nov_id
			    //while ( $row_src = mysqli_fetch_assoc($qry_src_string)) {
				foreach ( $array_source AS $row_src ) {
				    // pripravimo prvi del insert stringa
				    $insert_string = 'INSERT INTO '.$table_name.' ('.$insert_fields.') VALUES (';
					$insert_values = '';
					$insert_values_prefix = '';
				    // zloopamo skozi polja druge tabele in dodamo vrednosti
			    	mysqli_data_seek($dest_table_fields, 0);
			    	while ($row_dest_table_fields = mysqli_fetch_assoc($dest_table_fields)) {
			    		// ime polja v drugi tabeli
			    		$dest_field = $row_dest_table_fields['Field'];
			    		
			    		/* vrednosti se lahko dodeljujejo dinamično preko pre_set
			    		 *  se pravi za preslikavo starege vrednosti "spr_id" na novo vrednost "spr_id" 
			    		 *  je kot pre_set polje potrebno navesti ime polja ('field') in array z preslikavami (stara_vrednost=>nova_vrednost)
			    		 *
			    		 *    primer:
			    		 *    $pre_set = array('ank_id' => "'".$new_survey_id."'",
						 *		'spr_id' => array('field'=>'spr_id','from'=>$new_spremenljivke_ids)); # uporabimo dinamično preslikavo iz stare spr_id na novo spr_id za vsako vrstico
			    		 */
			    		
			    		// ce polje v src datoteki ni nastavljeno ga damo na praznega
			    		if ( ! isset($row_src[$dest_field]) && ! isset($pre_set[$dest_field]) ) {
							$dest_value = "''";
							
						// ce je polje NULL, ga moramo rocno nastavit na NULL
			    		} elseif ($row_src[$dest_field] === NULL) {
							$dest_value = "NULL";
							
			    		} else {
			    		
			    			$dest_value = isset($pre_set[$dest_field]) 
			    				? ( !(is_array($pre_set[$dest_field]) && isset($pre_set[$dest_field]['field']) && isset($pre_set[$dest_field]['from']))
			    					? $pre_set[$dest_field]
			    					: ( isset($pre_set[$dest_field]['from'][$row_src[$pre_set[$dest_field]['field']]]) 	# dodal sem se preverjenje, ce ID v tabeli preslikav obstaja - cene se uporabi original (kje se uporablja -1, -2...) -mitja
			    						? $pre_set[$dest_field]['from'][$row_src[$pre_set[$dest_field]['field']]]		# je array in sta potrebni polji preberemo iz arraya "from" za source polja "field"
			    						: "'".mysqli_real_escape_string($GLOBALS['connect_db'], $row_src[$dest_field])."'"
			    					)
			    				) 
			    				: "'".mysqli_real_escape_string($GLOBALS['connect_db'], $row_src[$dest_field])."'";
						
						}
			    		
				    	$insert_values .= $insert_values_prefix . $dest_value;
				    	$insert_values_prefix = ',';

			    	}
			    	// dodamo zaklepaj
			    	$insert_values .= ')';
			    	
					$insert_string .= $insert_values;	

					$updated = sisplet_query($insert_string, self::$dest_connect_db);
					if (!$updated) self::$errors[] = "Insert failed (".$table_name."): ".mysqli_error(self::$dest_connect_db).'('.$insert_string.')';
					$insert_id = mysqli_insert_id(self::$dest_connect_db);

					if ($insert_id > 0) {

						if (isset($row_src[$id_field])) {
							// poiscemo source id
							$result[$row_src[$id_field]] = $insert_id;
						} else {
							$result[] = $insert_id;
						}		
					} else {
						// lahko da je sql ne vrne id-ja (kadar ni primary key-a)
						$result[$row_src[$id_field]] = $row_src[$id_field];  
					}
				}
		    
			} else {
				self :: $errors[] = "Dest fields missing for table:".$table_name;
			}
		} else {
			// nothing to copy
		}
		return $result;
	}
	
	/**
	 * @desc Naredi kopijo respondenta v podatkih
	 */
	public static function copyRespondent($usr_id){
		global $global_user_id;
		
		// Dobimo podatke o respondentu ki ga kopiramo
		$sqlU = sisplet_query("SELECT preview, testdata, last_status, lurker, unsubscribed, language FROM srv_user WHERE ank_id='".self::$src_survey."' AND id='".$usr_id."'");
		$rowU = mysqli_fetch_array($sqlU);
		
		// Nastavimo nov cookie - izberemo random hash, ki se ni v bazi
		do {
			$rand = md5(mt_rand(1, mt_getrandmax()) . '@' . $global_user_id);
			
			$sql = sisplet_query("SELECT id FROM srv_user WHERE cookie = '$rand'");
			
		} while (mysqli_num_rows($sql) > 0);

		// Kopiramo respondenta
		$sql = sisplet_query("INSERT INTO srv_user
								(
									ank_id, 
									preview, 
									testdata, 
									cookie, 
									time_insert,
									time_edit,
									recnum, 
									last_status, 
									lurker, 
									unsubscribed, 
									language
								)
								VALUES (
									'".self::$src_survey."', 
									'".$rowU['preview']."', 
									'".$rowU['testdata']."', 
									'".$rand."', 
									NOW(),
									NOW(),
									MAX_RECNUM('".self::$src_survey."'), 
									'".$rowU['last_status']."', 
									'".$rowU['lurker']."', 
									'".$rowU['unsubscribed']."', 
									'".$rowU['language']."'
								)
							");
		$new_usr_id = mysqli_insert_id($GLOBALS['connect_db']);


		// Kopiramo odgovore	
		// srv_data_vrednost
		$sqlD = sisplet_query("SELECT * FROM srv_data_vrednost WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'usr_id' => $new_usr_id,
							'loop_id' => "NULL");
		self::preformCopyTable('srv_data_vrednost', null, self::sql2array($sqlD), $pre_set);
		
		// srv_data_vrednost_active
		$sqlD = sisplet_query("SELECT * FROM srv_data_vrednost_active WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'usr_id' => $new_usr_id,
							'loop_id' => "NULL");
		self::preformCopyTable('srv_data_vrednost_active', null, self::sql2array($sqlD), $pre_set);
		
		// srv_data_text
		$sqlD = sisplet_query("SELECT * FROM srv_data_text WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'id' => "NULL",
							'usr_id' => $new_usr_id,
							'loop_id' => "NULL");
		self::preformCopyTable('srv_data_text', 'id', self::sql2array($sqlD), $pre_set);
		
		// srv_data_text_active
		$sqlD = sisplet_query("SELECT * FROM srv_data_text_active WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'id' => "NULL",
							'usr_id' => $new_usr_id,
							'loop_id' => "NULL");
		self::preformCopyTable('srv_data_text_active', 'id', self::sql2array($sqlD), $pre_set);
		
		// srv_data_checkgrid
		$sqlD = sisplet_query("SELECT * FROM srv_data_checkgrid WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'usr_id' => $new_usr_id,
							'loop_id' => "NULL");
		self::preformCopyTable('srv_data_checkgrid', null, self::sql2array($sqlD), $pre_set);

		// srv_data_checkgrid_active
		$sqlD = sisplet_query("SELECT * FROM srv_data_checkgrid_active WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'usr_id' => $new_usr_id,
							'loop_id' => "NULL");
		self::preformCopyTable('srv_data_checkgrid_active', null, self::sql2array($sqlD), $pre_set);

		// srv_data_grid
		$sqlD = sisplet_query("SELECT * FROM srv_data_grid WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'usr_id' => $new_usr_id,
							'loop_id' => "NULL");		
		self::preformCopyTable('srv_data_grid', null, self::sql2array($sqlD), $pre_set);
		
		// srv_data_grid_active
		$sqlD = sisplet_query("SELECT * FROM srv_data_grid_active WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'usr_id' => $new_usr_id,
							'loop_id' => "NULL");
		self::preformCopyTable('srv_data_grid_active', null, self::sql2array($sqlD), $pre_set);
		
		// srv_data_textgrid
		$sqlD = sisplet_query("SELECT * FROM srv_data_textgrid WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'usr_id' => $new_usr_id,
							'loop_id' => "NULL");
		self::preformCopyTable('srv_data_textgrid', null, self::sql2array($sqlD), $pre_set);

		// srv_data_textgrid_active
		$sqlD = sisplet_query("SELECT * FROM srv_data_textgrid_active WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'usr_id' => $new_usr_id,
							'loop_id' => "NULL");
		self::preformCopyTable('srv_data_textgrid_active', null, self::sql2array($sqlD), $pre_set);
		
		// srv_data_rating
		$sqlD = sisplet_query("SELECT * FROM srv_data_rating WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'usr_id' => $new_usr_id,
							'loop_id' => "NULL");
		self::preformCopyTable('srv_data_rating', null, self::sql2array($sqlD), $pre_set);
		
		// srv_data_upload
		$sqlD = sisplet_query("SELECT * FROM srv_data_upload WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'usr_id' => $new_usr_id);
		self::preformCopyTable('srv_data_upload', null, self::sql2array($sqlD), $pre_set);		
		
		// srv_data_glasovanje
		$sqlD = sisplet_query("SELECT * FROM srv_data_glasovanje WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'usr_id' => $new_usr_id);		
		self::preformCopyTable('srv_data_glasovanje', null, self::sql2array($sqlD), $pre_set);
		
		// srv_user_grupa
		$sqlD = sisplet_query("SELECT * FROM srv_user_grupa WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'usr_id' => $new_usr_id,
							'time_edit' => "NOW()");
		self::preformCopyTable('srv_user_grupa', null, self::sql2array($sqlD), $pre_set);
		
		// srv_user_grupa_active
		$sqlD = sisplet_query("SELECT * FROM srv_user_grupa_active WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'usr_id' => $new_usr_id,
							'time_edit' => "NOW()");
		self::preformCopyTable('srv_user_grupa_active', null, self::sql2array($sqlD), $pre_set);
                
        // srv_data_map              
        $sqlD = sisplet_query("SELECT * FROM srv_data_map WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'usr_id' => $new_usr_id,
							'loop_id' => "NULL");
		self::preformCopyTable('srv_data_map', null, self::sql2array($sqlD), $pre_set);
		
        // srv_data_heatmap              
        $sqlD = sisplet_query("SELECT * FROM srv_data_heatmap WHERE usr_id='".$usr_id."'");
		$pre_set = array(	'usr_id' => $new_usr_id,
							'loop_id' => "NULL");
		self::preformCopyTable('srv_data_heatmap', null, self::sql2array($sqlD), $pre_set);
		
		return $new_usr_id;
	}
    
	
	/**
	* sql result vrne kot multi array
	* 
	* @param mixed $sql
	*/
	static function sql2array($sql) {

		$array = array();
		
		while ($row = mysqli_fetch_assoc($sql)) {
			
			$array[] = $row;
			
		}
		
		return $array;	
	}
	
	/**
	* pofilitira array in vrne samo vrstice, ki ustrezajo field == value
	* 
	* @param mixed $array
	* @param mixed $field
	* @param mixed $value
	*/
	static function arrayfilter ($array, $field, $value) {
		
		$arr = array();

		foreach ($array AS $row) {
			
			if ($row[$field] == $value){
				$arr[] = $row;
			}
		}
		
		return $arr;	
	}
	
}

?>