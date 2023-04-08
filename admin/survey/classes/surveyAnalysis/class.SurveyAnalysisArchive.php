<?php
/**
* @author 	Gorazd Veselič
* @date		May 2010
* 
* Funkcije za arhive analiz
* 
*  Polja v tabeli - srv_analysis_archive:
*	- id int(11) NOT NULL auto_increment	# id arhiva
*	- sid int(11) NOT NULL default 0,		# id ankete
*	- uid int(11) NOT NULL default 0,		# id uporabnika kateri je skreiral arhiv
*	- name varchar(200) NOT NULL,			# dodaljeno ime arhiva
*	- filename varchar(50) NOT NULL,		# dodeljeno ime datoteke na FS
*	- date datetime NOT NULL,				# datum kreacije
*	- note varchar(200) NOT NULL,			# opomba
*	- access TINYINT NOT NULL DEFAULT 0; 	# 0 - vidijo vsi, 1 - vidijo samo uporabniki z dostopom
*	- type TINYINT NOT NULL DEFAULT 0; 		# 0 - sumarnik, 1 - opisne, 2 - frekvence, 3 - crostabi, 4 - means, 5 - ttest, 6 - break, 7 - charts, 8 - creport
*   - duration  date NOT NULL;				# datum do kdaj je arhiv aktiven, potem se briše iz baze in FS
*   - editid int(11) NOT NULL default 0;	# id avtorja ki je zadnji spremnijal
*
*/


define("SAA_FOLDER", "AnalysisArchive");
define("DEFAULT_DURATION", " +3 month"); // privzet čas trajanja athiva

class SurveyAnalysisArchive {
	
	
	static private $sid;
	
	// konstrutor
	protected function __construct() {}
	// kloniranje
	final private function __clone() {}
	
	/**
	* Inicializacija
	* 
	* @param int $anketa
	*/
	static function Init( $anketa = null )
	{
		if ($anketa)
			self::$sid = $anketa;
			
		# pobrišemo linke, ki so pretekli 
		$s = sisplet_query("SELECT id FROM srv_analysis_archive WHERE date_add(duration, INTERVAL 1 DAY) < NOW()");
		while ($row = mysqli_fetch_assoc($s)) {
			$successDelete = self::DoDeleteArchive($row['id']); 
		}
	}
		
	/**
	* @desc Vrne ID trenutnega uporabnika (ce ni prijavljen vrne 0)
	*/
	static function uid() {
		global $global_user_id;

		return $global_user_id;
	}
	
	static function ListArchive($fields=array()) {
		global $lang, $site_url;
		$defaultFields = array(
                        'create_new'=>true, 	#fieldset z linkom za generiranje novega arhiva
			'delete'=>true, 	#stolpec delete
			'edit'=>true, 		#stolpec editiraj
			'email'=>true, 		#stolpec pošlji po mailu
			'name'=>true,		#stolpec ime
			'note'=>true,		#stolpec opomba
			'date'=>true,		#stolpec datum
			'access'=>true,		#stolpec access (dostop)		# 0 - vsi, 1 - uporabniki iz srv_dostop
			'type'=>true,		#stolpec type (vrsta analize)	# 0 - sumarnik, 1 - opisne, 2 - frekvence, 3 - crostabi, 4 - means, 5 - ttest, 6 - break, 7 - charts, 8 - creport
			'name_link' => true,#ali se ime pokaže kot link
			'duration' => true,	#stolpec trajanje (duration)
			'insert' => true,	#stolpec autor
			'edit' => true		#stolpec spreminajl
		
		);
		$ArchiveTypes = array(M_ANALIZA_SUMS => 0, M_ANALIZA_DESCRIPTOR=>1, M_ANALIZA_FREQUENCY=>2, M_ANALIZA_CROSSTAB=>3, M_ANALYSIS_MEANS=>4, M_ANALYSIS_TTEST=>5, M_ANALYSIS_BREAK=>6, M_ANALYSIS_CHARTS=>7, M_ANALYSIS_CREPORT=>8);
		#ponastavimo želene vrednosti				
		foreach ($fields AS $key => $value) {
			$defaultFields[$key] = $value;
		}		
		
		$users = array();		
		$qry = "SELECT saa.*, UNIX_TIMESTAMP(saa.date) as insert_date, UNIX_TIMESTAMP(saa.duration) as duration_d, DATEDIFF(saa.duration, CURDATE()) as days_left"
			# da ne delamo vlkege poizvedbe kadar ni potrebno 
			. ($defaultFields['insert'] ? " , us1.name as iname, us1.surname as isurname, us1.email as iemail " : "" )
			. ($defaultFields['edit'] ? " , us2.name as ename, us2.surname as esurname, us2.email as eemail " : "" )
			. " FROM srv_analysis_archive as saa "
			. ($defaultFields['insert'] ? " LEFT OUTER JOIN ( SELECT us1.name, us1.surname, us1.id, us1.email FROM users as us1 ) AS us1 ON us1.id = saa.uid " : "" )
			. ($defaultFields['edit'] ? " LEFT OUTER JOIN ( SELECT us2.name, us2.surname, us2.id, us2.email FROM users as us2 ) AS us2 ON us2.id = saa.editid " : "" )
			. " WHERE sid='".self::$sid."' ORDER BY date DESC";
		
		$s = sisplet_query($qry);
		if (mysqli_num_rows($s) > 0 ) {
		
                    if($defaultFields['create_new']){
			echo '<fieldset>';
			echo '<legend>'.$lang['srv_archive_analysis'].'</legend>';
			
			echo $lang['srv_analiza_archive_generate_quick'];
			
			echo '</fieldset>';
			
			echo '<br />';
                    }
			
			echo '<table class="arch_tbl anl_bt anl_bl" style="width:100%">';
			echo '<tr>';
			if ($defaultFields['delete'])
				echo '<td class="anl_bck gray anl_bb anl_br anl_ac">'.$lang['srv_analiza_archive_lbl_delete'].'</td>';
			if ($defaultFields['edit'])
				echo '<td class="anl_bck gray anl_bb anl_br anl_ac">'.$lang['srv_analiza_archive_lbl_edit'].'</td>';
			if ($defaultFields['email'])
				echo '<td class="anl_bck gray anl_bb anl_br anl_ac">'.$lang['srv_analiza_archive_lbl_send'].'</td>';
			if ($defaultFields['name'])
				echo '<td class="anl_bck gray anl_bb anl_br anl_ac">'.$lang['srv_analiza_archive_lbl_name'].'</td>';
			if ($defaultFields['note'])
				echo '<td class="anl_bck gray anl_bb anl_br anl_ac">'.$lang['srv_analiza_archive_lbl_note'].'</td>';
			if ($defaultFields['access'])
				echo '<td class="anl_bck gray anl_bb anl_br anl_ac">'.$lang['srv_analiza_archive_lbl_access'].'</td>';
			if ($defaultFields['type'])
				echo '<td class="anl_bck gray anl_bb anl_br anl_ac">'.$lang['srv_analiza_archive_lbl_type'].'</td>';
			if ($defaultFields['date'])			
				echo '<td class="anl_bck gray anl_bb anl_br anl_ac">'.$lang['srv_analiza_archive_lbl_date'].'</td>';
			if ($defaultFields['duration'])			
				echo '<td class="anl_bck gray anl_bb anl_br anl_ac">'.$lang['srv_analiza_archive_lbl_duration'].'</td>';
			if ($defaultFields['insert'])			
				echo '<td class="anl_bck gray anl_bb anl_br anl_ac">'.$lang['srv_analiza_archive_lbl_author'].'</td>';
			if ($defaultFields['edit'])			
				echo '<td class="anl_bck gray anl_bb anl_br anl_ac">'.$lang['srv_analiza_archive_lbl_editor'].'</td>';
			echo '</tr>';
			
			while ($row = mysqli_fetch_assoc($s)) {
				echo '<tr id="AnalysisArchiveRow_'.$row['id'].'">';
				if ($defaultFields['delete']) {
					echo '<td class="anl_bb anl_br anl_ac">';
					echo '<span>';
					echo '<a href="/" onclick="AnalysisArchiveDelete(\'' . $row['id'] . '\'); return false;" title="">';
					echo '<img src="img_0/delete_red.png" alt="" />';
					echo '</a>';
					echo '</span>';
					echo '</td>';
				}
				if ($defaultFields['edit']) {
					echo '<td class="anl_bb anl_br anl_ac">';
					echo '<span>';
					echo '<a href="/" onclick="AnalysisArchiveEdit(\'' . $row['id'] . '\'); return false;" title="">';
					echo '<img src="img_0/edit.png" alt="" />';
					echo '</a>';
					echo '</span>';
					echo '</td>';
				}
				if ($defaultFields['email']) {
					echo '<td class="anl_bb anl_br anl_ac">';
					echo '<span>';
					echo '<a href="/" onclick="emailArchiveAnaliza(\'' . $row['id'] . '\'); return false;" title="">';
					echo '<img src="icons/icons/email_link.png" alt="" />';
					echo '</a>';
					echo '</span>';
					echo '</td>';
				}
				if ($defaultFields['name']) {
					echo '<td class="anl_bb anl_br">';
					echo '<span>';
					if ($defaultFields['name_link']) {
						echo '<a href="'.$site_url.'admin/survey/AnalysisArchive.php?anketa='.self::$sid.'&aid='. $row['id'] . '" target="_blank" title="' . $row['name'] . '">';
						echo $row['name'];
						echo '</a>';
					} else {
						echo $row['name'];
					}
					echo '</span>';
					
					echo '</td>';
				}
				if ($defaultFields['note']) {
					echo '<td class="anl_bb anl_br">'.$row['note'].'</td>';
				}
				if ($defaultFields['access']) {
					echo '<td class="anl_bb anl_br">'.$lang['srv_analiza_arhiviraj_access_'.$row['access']].'</td>';
				}
				if ($defaultFields['type']) {
					$key = $lang['srv_analiza_arhiviraj_type_'.$row['type']]; 
					echo '<td class="anl_bb anl_br anl_ac">'.$key.'</td>';
				}
				if ($defaultFields['date']) {
					echo '<td class="anl_bb anl_br anl_ac" title="'.date('d.m.Y H:m:s',$row['insert_date']).'">';
					echo date('d.m.Y',$row['insert_date']);
					echo '</td>';
				}
				if ($defaultFields['duration']) {
					# koliko dni damo v title
					$days = ($row['days_left'] == 1) 
							? $lang['1day'] 
							: $row['days_left'].' '.$lang['hour_days'];
					echo '<td class="anl_bb anl_br anl_ac" title="'.$days.'">';
					echo date('d.m.Y',$row['duration_d']);
					echo '</td>';
				}
				if ($defaultFields['insert']) {
					$users = array();
					echo '<td class="anl_bb anl_br" title="'.$row['iname'].' '.$row['isurname'].'">';
					echo $row['iemail'];
					echo '</td>';
				}
				if ($defaultFields['edit']) {			
					echo '<td class="anl_bb anl_br" title="'.$row['ename'].' '.$row['esurname'].'">';
					echo $row['eemail'];
					echo '</td>';
				}
				echo '</tr>';
			}
			echo '</table>';
		} 
		else {
			echo '<fieldset>';
			echo '<legend>'.$lang['srv_archive_analysis'].'</legend>';
			
			echo $lang['srv_analiza_archive_note_no_archive'];
			echo '<br/><br/>';
			echo $lang['srv_analiza_archive_generate_quick'];
			
			echo '</fieldset>';
		}	
	}
	
	/** Skreira tekstovni fajl in shrani zapis o fajlu v bazo.
	 * 
	 * @param unknown_type $content
	 * @param unknown_type $name
	 */	
	static function CreateArchive($content,$name=null, $note=null, $access='0',$type=null,$duration=null,$durationType='0',$settings=array(), $access_password=null) {
		global $site_path, $site_url, $global_user_id, $lang;
		
		#če ni imena ga zgeneriramo
		if ($name==null) {
			
			$name = 'Arhiv: '.date("d.m.Y H:i:s");
		}
		$folder = $site_path . 'admin/survey/'.SAA_FOLDER.'/';
		$filename = 'saa_'.self::$sid.'_'.time().'.txt';
		
		# če imamo durationType = 2, imamo trajen arhiv (do leta 2038 - max za 32bit server)
		if ($durationType == 2) {
			$duration  = strtotime(date("d.m.Y", strtotime('1.1.2038')));
		}
		else{
			# če imamo durationType = 0, imamo privzet interval 3 mesece
			if ($durationType == 0) {
				$duration = null;
			}		
			if ( $duration == null ) { #če ni časa trajanja ga zgeneriramo
				$duration = date("Y-m-d");// current date
				$duration  = strtotime(date("Y-m-d", strtotime($duration)) . DEFAULT_DURATION);
			} else { # če je ga pretvorimo v datum
				$duration  = strtotime(date("d.m.Y", strtotime($duration)));
			}
		}
		$duration = date("Y-m-d",$duration);
		
		$settings = serialize($settings);
		#dodamo zapis o arhivu v bazo
		$s = sisplet_query("INSERT INTO srv_analysis_archive (sid, uid, name, filename, date, note, access, type, duration, editid, settings, access_password) "
			."VALUES ('".self::$sid."', '$global_user_id', '$name', '$filename', NOW(), '$note', '$access', '$type', '$duration', '$global_user_id', '$settings', '$access_password')");
		$id = mysqli_insert_id($GLOBALS['connect_db']);
		
			
		// Na zacetek dodamo glavo -> po novem se shrani v file
		SurveyInfo::getInstance()->SurveyInit(self::$sid);
		$naslov = SurveyInfo::getInstance()->getSurveyColumn('akronim');
		if ($naslov == null || trim($naslov) == '') {
			$naslov = SurveyInfo::getInstance()->getSurveyColumn('naslov');
		}
		
		$text = '<div id="arch_body_div">';
		
		SurveySetting::getInstance()->Init(self::$sid);
		$survey_hide_title = SurveySetting::getInstance()->getSurveyMiscSetting('survey_hide_title');	
		
		// Ce ne prikazujemo naslova ankete to skrijemo
		if($survey_hide_title == 0){
			$text .= '<div class="arch_head_date">'	
					.$lang['srv_analiza_archive_date_created']
					.date("d.m.Y")
					.'</div><h2>'.$naslov.'</h2>';
		}
		
		// Porocilo po meri ima custom naslov
		$text .= ($survey_hide_title == 1) ? '<h2>' : '<h3>';
		if($type == 8){		
			$SCR = new SurveyCustomReport(self::$sid);
			$creport_title = $SCR->getTitle();
			$text .= $creport_title;
		}
		else{
			$text .= $lang['srv_analiza_archive_title'].$lang['srv_analiza_arhiviraj_type_'.$type];
		}
		$text .= ($survey_hide_title == 1) ? '</h2>' : '</h3>';
		
		$content = $text.$content;		
		
		
		if ($id) { # če smo dodali zapis v bazo shranimo še datoteko
			# zapišemo fajl na disk
			$fh = fopen($folder.$filename, 'w') or die("can't open file");
			fwrite($fh, $content);
			fclose($fh);
			
		}
		self :: DisplayCreatedArchive($id,$name);
	}

	static function DisplayDoArchive() {
		global $lang, $site_url;
		
        echo '<div id="div_analiza_archive_name" class="divPopUp">'."\n";
        
        echo '<div class="popup_close"><a href="#" onClick="cancleArchiveAnaliza(); return false;">✕</a></div>';

		echo '<h2>'.$lang['srv_analiza_arhiv'].'</h2>';
		
		echo $lang['srv_analiza_arhiviraj_ime'];
        echo ':&nbsp;<input id="newAnalysisArchiveName" name="newAnalysisArchiveName" type="text" size="60"  />'."\n";

        echo '<br class="clr" />';

		echo '<div>';
		echo $lang['srv_analiza_archive_note'].':';
		echo '<textarea name="newAnalysisArchiveNote" id="newAnalysisArchiveNote" style="height:50px; width:100%"></textarea>';
		echo '</div>';
		echo '<br class="clr" />';
		echo '<div>';
		// dostop
		echo '<div class="floatLeft">';
		echo $lang['srv_analiza_archive_access'].':';
		echo '<br/><input type="radio" name="newAnalysisArchiveAccess" value="0" checked="true" onchange="toggleAnalysisArchiveAccessPassword();"/>&nbsp;'.$lang['srv_analiza_archive_access_all'];
		echo '<br/><input type="radio" name="newAnalysisArchiveAccess" value="1" onchange="toggleAnalysisArchiveAccessPassword();"/>&nbsp;'.$lang['srv_analiza_archive_access_admins'];
                echo '<br/><input type="radio" name="newAnalysisArchiveAccess" value="2" onchange="toggleAnalysisArchiveAccessPassword();"/>&nbsp;'.$lang['srv_analiza_archive_access_password'];
                echo '<br/><div id="newAnalysisArchiveAccessPasswordDiv" style="visibility: hidden;">'.$lang['srv_analiza_archive_access_password_label'].'<input type="text" name="newAnalysisArchiveAccessPassword" id="newAnalysisArchiveAccessPassword" maxlength="25" />';
		echo '</div></div>';
		echo '<div class="floatLeft anl_w110"  >&nbsp;</div>';
		
		// trajanje
		echo '<div class="floatLeft">';
		echo $lang['srv_analiza_archive_duration'].':';
		echo '<br/>';
		$date = date("Y-m-d");// current date
		$duration  = strtotime(date("Y-m-d", strtotime($date)) . " +3 month");
		$duration = date("d.m.Y",$duration);
		echo '<input type="radio" name="newAADurationType" id="newAADurationFixed" value="0" checked="true" />';
		printf ($lang['srv_analiza_archive_duration_default'], $duration);
		echo '<br/>';
		echo '<input type="radio" name="newAADurationType" id="newAADurationUser" value="1" />'.$lang['srv_analiza_archive_duration_custom'].'&nbsp;<input id="newAnalysisArchiveDuration" type="text" name="newAnalysisArchiveDuration" value="' . $duration . '" disabled/>
			<span class="faicon calendar_icon icon-as_link" id="duration_img"></span>
			<script type="text/javascript">
				Calendar.setup({
					inputField  : "newAnalysisArchiveDuration",
					ifFormat    : "%d.%m.%Y",
					button      : "duration_img",
					singleClick : true
				});
			</script>
			';
		echo '<br/>';
		echo '<input type="radio" name="newAADurationType" id="newAADurationPermanent" value="2" />'.$lang['srv_permanent_archive'];
		echo '</div>';
		
		echo '<div class="clr"></div>';
		echo '</div>';
		
		echo '<div class="div_curent_archives">'."\n";
		echo $lang['srv_analiza_current_archives'];
		if (true) {
				self :: ListArchive(array('create_new'=>false, 'delete'=>false, 'edit'=>false, 'email'=>false, 'access'=>false, 'note'=>false, 'type'=>true, 'access'=>false, 'name_link'=>true,'duration'=>false, 'insert'=>true, 'edit'=>false));
				#self :: ListArchive(array('delete'=>false, 'edit'=>false, 'email'=>false, 'access'=>false, 'note'=>false, 'type'=>false, 'access'=>false, 'name_link'=>true, 'duration'=>false, 'insert'=>false, 'edit'=>false));
		} else {
			echo $lang['srv_analiza_no_current_archives'];
        }
        
        echo '</div>'."\n"; // end: div_curent_archives


        if ($_GET['podstran'] == M_ANALYSIS_CROSSTAB || $_POST['podstran'] == M_ANALYSIS_CROSSTAB) {
        	echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_create'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="submitArchiveCrosstabs(); return false;"><span>'.$lang['srv_analiza_arhiviraj_create'].'</span></a></span></span>'."\n";
        } else  if ($_GET['podstran'] == M_ANALYSIS_MEANS || $_POST['podstran'] == M_ANALYSIS_MEANS) {
        	echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_create'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="submitArchiveMeans(); return false;"><span>'.$lang['srv_analiza_arhiviraj_create'].'</span></a></span></span>'."\n";
        } else  if ($_GET['podstran'] == M_ANALYSIS_TTEST || $_POST['podstran'] == M_ANALYSIS_TTEST) {
        	echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_create'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="submitArchiveTTest(); return false;"><span>'.$lang['srv_analiza_arhiviraj_create'].'</span></a></span></span>'."\n";
        } else  if ($_GET['podstran'] == M_ANALYSIS_BREAK || $_POST['podstran'] == M_ANALYSIS_BREAK) {
        	echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_create'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="submitArchiveBreak(); return false;"><span>'.$lang['srv_analiza_arhiviraj_create'].'</span></a></span></span>'."\n";
        } else  if ($_GET['podstran'] == M_ANALYSIS_CHARTS || $_POST['podstran'] == M_ANALYSIS_CHARTS) {
        	echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_create'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="submitArchiveChart(); return false;"><span>'.$lang['srv_analiza_arhiviraj_create'].'</span></a></span></span>'."\n";
        } else  if ($_GET['podstran'] == M_ANALYSIS_CREPORT || $_POST['podstran'] == M_ANALYSIS_CREPORT) {
        	echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_create'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="submitArchiveCReport(); return false;"><span>'.$lang['srv_analiza_arhiviraj_create'].'</span></a></span></span>'."\n";
        } else {
        	echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_create'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="submitArchiveAnaliza(); return false;"><span>'.$lang['srv_analiza_arhiviraj_create'].'</span></a></span></span>'."\n";
        }
        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_cancle'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="cancleArchiveAnaliza(); return false;"><span>'.$lang['srv_analiza_arhiviraj_cancle'].'</span></a></span></span>'."\n";

		
		echo '</div>'."\n"; // end div_analiza_archive_name
	}
	
	static function DisplayCreatedArchive($aid=null, $name) {
		global $lang, $site_url;
		
        echo '<div id="div_analiza_archive_name" class="divPopUp">'."\n";
        
        echo '<div class="popup_close"><a href="#" onClick="closeArchiveAnaliza(); return false;">✕</a></div>';
		
		echo '<h2>'.$lang['srv_analiza_arhiv'].'</h2>';
		
		$CAE = self::CheckArchiveExistance($aid); 
		if ( $CAE > 0) {
			echo '<div>';
			printf( $lang['srv_analiza_arhiviraj_success'],$name);
			echo '</div>';
			echo '<br/>';

			echo '<div>';
			echo $lang['srv_analiza_arhiviraj_success_note'];
			echo '<br/>';
			echo '<span>';
			echo '<a href="'.$site_url.'admin/survey/AnalysisArchive.php?anketa='.self::$sid.'&aid='. $aid . '" target="_blank" title="">';
			echo $site_url.'admin/survey/AnalysisArchive.php?anketa='.self::$sid.'&aid='. $aid;
			echo '</a>';
			echo '</span>';
			echo '</div>';
	        
			echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_close'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="closeArchiveAnaliza(); return false;"><span>'.$lang['srv_analiza_arhiviraj_close'].'</span></a></span></span>'."\n";
	        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_send_mail'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="emailArchiveAnaliza(\''.$aid.'\'); return false;" ><span><img src="icons/icons/email_link.png" alt="" /> '.$lang['srv_analiza_arhiviraj_send_mail'].'</span></a></span></span>'."\n";
		} else {
			self::DisplayError($CAE);
		}

		# seznam arhivov
		echo '<br class="clr" />';
		echo '<div class="div_curent_archives">'."\n";
		echo $lang['srv_analiza_current_archives'];
		if (true) {
			self :: ListArchive(array('delete'=>false, 'edit'=>false, 'email'=>false, 'access'=>false, 'note'=>false, 'type'=>true, 'access'=>false, 'name_link'=>true,'duration'=>false, 'insert'=>true, 'edit'=>false));
		} else {
			echo $lang['srv_analiza_no_current_archives'];
		}
		echo '</div>'."\n"; // end: div_curent_archives
	
		echo '</div>'."\n"; // end div_analiza_archive_name
	}
	
	static function EmailArchive($aid) {
		global $lang;
		
        echo '<div id="div_analiza_archive_name" class="divPopUp">'."\n";
        
        echo '<div class="popup_close"><a href="#" onClick="cancleArchiveAnaliza(); return false;">✕</a></div>';
		
		echo '<h2>'.$lang['srv_analiza_arhiv'].'</h2>';
		
		# preverimo obstoj datoteke, in dostop
		$CAE = self::CheckArchiveExistance($aid);
		 
		if ( $CAE > 0) {
			# vsebina emaila in naslovi
			echo '<div id="div_archives_email_left">'."\n";
	
            echo '<p>'.$lang['srv_analiza_archive_message_note'].'</p>';
            
			echo '<div ><label for="email_archive_list">'.$lang['srv_analiza_archive_message_emails'].':</label>'."\n";
			echo '<textarea name="email_archive_list" rows="4" id="email_archive_list" ></textarea>'."\n";
            echo '</div>';
            
            echo '<br>';
            
			echo '<div class="anl_dash_bt">';
			echo '<br/><label for="subject">' . $lang['subject'] . ': </label>';
			echo '<input type="text" name="email_archive_subject" id="email_archive_subject" value="'.$lang['srv_analiza_arhiviraj_mail_subject'].'" size="90"/></p>';
            echo '<p><label for="email_archive_text">' . $lang['text'] . ':</label>'."\n";
            
            $signature = Common::getEmailSignature();
			echo '<textarea name="email_archive_text" id="email_archive_text" rows="2" >' . nl2br($lang['srv_analiza_arhiviraj_mail_text'].$signature). '</textarea>'."\n";
            echo '</div>';
            
			echo '<script type="text/javascript">'."\n";
			echo 'if ($("#email_archive_text")) {'."\n";
			echo ' 	create_editor(\'email_archive_text\', false);'."\n";
			echo '}'."\n";
            echo '</script>'."\n";
            
			echo '</div>'."\n";
			#gumbi
			echo '<div id="div_archives_email_right" >'."\n";
			
			echo '<div id="div_archives_email_buttons">';
	        echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_do_send_mail'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="sendEmailArchiveAnaliza(\''.$aid.'\'); return false;"><span>'.$lang['srv_analiza_arhiviraj_do_send_mail'].'</span></a></span></span>'."\n";
			echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_close'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="cancleArchiveAnaliza(); return false;"><span>'.$lang['srv_analiza_arhiviraj_close'].'</span></a></span></span>'."\n";
			echo '<br class="clr" />';
			echo '</div>';
			echo '<div id="div_error">';
			# navodila
			echo $lang['srv_analiza_arhiviraj_mail_note_0'];
			echo '</div>';
			echo '<br/>';
			echo '<br/>';
			echo '<div id="div_error">';
			# navodila
			echo $lang['srv_analiza_arhiviraj_mail_note_1'];
			echo '</div>';
			echo '</div>';
	        echo '<br class="clr" />';
		} else {
			self::DisplayError($CAE);
		}
        echo '</div>'."\n"; // end div_analiza_archive_name
	}
	
	static function SendEmailArchive($aid = null, $subject, $text, $emails) {
		global $lang, $site_url, $site_path, $global_user_id;
		
        echo '<div id="div_analiza_archive_name" class="divPopUp">'."\n";
        
        echo '<div class="popup_close"><a href="#" onClick="cancleArchiveAnaliza(); return false;">✕</a></div>';
		
		echo '<h2>'.$lang['srv_analiza_arhiv'].'</h2>';
		
		$CAE = self::CheckArchiveExistance($aid); 
		if ( $CAE > 0) {
			if (isset($emails) && trim($emails) != "") {
				$_subject = ( isset($subject) && trim($subject) != "" ) 
					? stripcslashes ($subject)
					: stripcslashes ($lang['srv_analiza_arhiviraj_mail_subject']);
                
                // Podpis
                $signature = Common::getEmailSignature();

				$_text = ( isset($text) && trim($text) != "" ) 
					? stripcslashes ($text)
					: stripcslashes (nl2br($lang['srv_analiza_arhiviraj_mail_text'].$signature));
	
				# polovimo podatke ankete
				SurveyInfo::getInstance()->SurveyInit(self::$sid);
				$row = SurveyInfo::getInstance()->getSurveyRow();
				
				#polovimo podatke arhiva 
				$archQry = sisplet_query("SELECT date FROM srv_analysis_archive WHERE sid='".self::$sid."' AND id='".$aid."'");
				$archRow = mysqli_fetch_assoc($archQry);
	
				$userQry = sisplet_query("SELECT name, surname, id, email FROM users WHERE id='$global_user_id'");
				$userRow = mysqli_fetch_assoc($userQry);
						
				/* zamenjave 
					[LINK]</dt><dd>HTML povezava do arhiva
					[URL]</dt><dd>URL povezave do arhiva
					[NAME]</dt><dd>ime uporabnika (iz baze (CMS))
					[SURVEY]</dt><dd>ime ankete
					[DATE]</dt><dd>datum
					[SITE]</dt><dd>URL do ankete</dd></dl>",
				*/
				$in = array('[LINK]','[URL]','[NAME]','[SURVEY]','[DATE]','[SITE]');
							
				$repl_link = '<a href="'.$site_url.'admin/survey/AnalysisArchive.php?anketa='.self::$sid.'&aid='.$aid.'">'.$site_url.'admin/survey/AnalysisArchive.php?anketa='.self::$sid.'&aid='.$aid.'</a>';
				$repl_url  = $site_url.'admin/survey/AnalysisArchive.php?anketa='.self::$sid.'&aid='.$aid;
				$repl_name = $userRow['name'].' '.$userRow['surname'].' ('.$userRow['email'].')';
				$repl_survey = $row['naslov'];
				$repl_date = $archRow['date'];
				$repl_site  = $site_url.'admin/survey/AnalysisArchive.php?anketa='.self::$sid;
	
				$out = array($repl_link,$repl_url,$repl_name,$repl_survey,$repl_date,$repl_site);
	
				$_subject = str_replace($in,$out,$_subject);
				$_text= str_replace($in,$out,$_text);
				
				# v loopu pošljemo maile
				$email_addresses = explode("\n", $emails);
				if (count($email_addresses)) {
	
					$status_success = array();
					$status_error = array();
					foreach ($email_addresses AS $email) {
						$email = trim($email);
						if (strlen ($email) > 1) {
							// Posljemo mail vsakemu uporabniku posebej
							try
							{
								$MA = new MailAdapter(self::$sid, $type='alert');
								$MA->addRecipients($email);
								$resultX = $MA->sendMail($_text, $_subject);
							}
							catch (Exception $e)
							{
							}
							
							if ($resultX) {
								$status_success[] = $email; // poslalo ok
							} else {
								$status_error[] = $email; // ni poslalo
							}
						}
					} // end foreach
					
					// zlistamo uspešne in neuspešne naslove
					echo '<b>Sporočilo:</b><br/><br/>' . $_subject . ',<br/> ' . $_text . '<br/>';
					if (count($status_success) > 0) {
						echo '<b>je bilo uspešno poslano na naslednje naslove:<br/></b>';
						foreach ($status_success as $email) {
							echo $email . ",<br/>";
						}
						echo "<br/>";
					}
					if (count($status_error) > 0) {
						echo '<br/><b>ni bilo uspešno poslano na naslednje naslove:<br/></b>';
						foreach ($status_error as $email) {
							echo $email . ",<br/>";
						}
					}
					
				} else {
					echo 'No email adress!';			
				}
	
			} else {
				echo 'Pri pošiljanju e-mailov je prišlo do napake!';
			}
			
			echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_close'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="cancleArchiveAnaliza(); return false;"><span>'.$lang['srv_analiza_arhiviraj_close'].'</span></a></span></span>'."\n";
			echo '<br class="clr" />';
		} else {
			self::DisplayError($CAE);
		}
		
		echo '</div>'."\n"; // end div_analiza_archive_name
	}
	
	static function EditArchive($aid) {
		global $lang, $site_url, $site_path, $global_user_id;
		
        echo '<div id="div_analiza_archive_name" class="divPopUp">'."\n";
        
        echo '<div class="popup_close"><a href="#" onClick="cancleArchiveAnaliza(); return false;">✕</a></div>';
		
		echo '<h2>'.$lang['srv_analiza_arhiv'].'</h2>';
		
		$CAE = self::CheckArchiveExistance($aid); 
		if ( $CAE > 0) {
			# polovimo podatke o arhivu
			$s = sisplet_query("SELECT *, UNIX_TIMESTAMP(duration) as duration_d FROM srv_analysis_archive WHERE id='".$aid."' AND sid='".self::$sid."'");			
			$row = mysqli_fetch_assoc($s);

			echo $lang['srv_analiza_arhiviraj_ime'];
	        echo ':&nbsp;<input id="newAnalysisArchiveName" name="newAnalysisArchiveName" type="text" size="60"  value="'.$row['name'].'"/>'."\n";

	        echo '<br class="clr" />';
			echo '<div>';
			echo $lang['srv_analiza_archive_note'].':';
			echo '<textarea name="newAnalysisArchiveNote" id="newAnalysisArchiveNote" style="height:50px; width:100%">'.$row['note'].'</textarea>';
			echo '</div>';
			echo '<div>';
			// dostop
			echo '<div class="floatLeft">';
			echo $lang['srv_analiza_archive_access'].':';
			echo '<br/><input type="radio" name="newAnalysisArchiveAccess" value="0"'.((int)$row['access'] == 0 ? ' checked="true"' : '').' onchange="toggleAnalysisArchiveAccessPassword();"/>&nbsp;'.$lang['srv_analiza_archive_access_all'];
			echo '<br/><input type="radio" name="newAnalysisArchiveAccess" value="1"'.((int)$row['access'] == 1 ? ' checked="true"' : '').' onchange="toggleAnalysisArchiveAccessPassword();"/>&nbsp;'.$lang['srv_analiza_archive_access_admins'];
                        echo '<br/><input type="radio" name="newAnalysisArchiveAccess" value="2"'.((int)$row['access'] == 2 ? ' checked="true"' : '').' onchange="toggleAnalysisArchiveAccessPassword();"/>&nbsp;'.$lang['srv_analiza_archive_access_password'];
                        echo '<br/><div id="newAnalysisArchiveAccessPasswordDiv" style="visibility: '.((int)$row['access'] == 2 ? 'visible' : 'hidden').';">'.$lang['srv_analiza_archive_access_password_label'].'<input type="text" name="newAnalysisArchiveAccessPassword" id="newAnalysisArchiveAccessPassword" maxlength="25"  value="'.$row['access_password'].'"/>';
                        echo '</div></div>';
                        
			echo '<div class="floatLeft anl_w110"  >&nbsp;</div>';
			// trajanje
			echo '<div class="floatLeft">';
			echo $lang['srv_analiza_archive_duration'].':';
			echo '<br/>';
			echo '<input id="newAnalysisArchiveDuration" type="text" name="newAnalysisArchiveDuration" value="' . date('d.m.Y',$row['duration_d']) . '" disabled/>
				<span class="faicon calendar_icon icon-as_link" id="duration_img"></span>
				<script type="text/javascript">
					Calendar.setup({
						inputField  : "newAnalysisArchiveDuration",
						ifFormat    : "%d.%m.%Y",
						button      : "duration_img",
						singleClick : true
					});
				</script>
				';
			
			echo '</div>';
			echo '<div class="clr"></div>';
			echo '</div>';

			echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_save'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="saveArchiveAnaliza(\''.$aid.'\'); return false;"><span>'.$lang['srv_analiza_arhiviraj_save'].'</span></a></span></span>'."\n";			
			echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_close'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="cancleArchiveAnaliza(); return false;"><span>'.$lang['srv_analiza_arhiviraj_close'].'</span></a></span></span>'."\n";
			echo '<br class="clr" />';
		} else {
			self::DisplayError($CAE);
		}
		echo '</div>'."\n"; // end div_analiza_archive_name
	}

	static function SaveArchive($aid,$name,$note,$access,$duration,$access_password) {
		global $lang, $site_url, $site_path, $global_user_id;
		
		$CAE = self::CheckArchiveExistance($aid); 
		if ( $CAE > 0) {
			if ($name==null || trim($name) == "") {
				$name = 'Arhiv: '.date("d.m.Y H:i:s");
			}
			
			if ( $duration == null ) { #če ni časa trajanja ga zgeneriramo
				$duration = date("Y-m-d");// current date
				$duration  = strtotime(date("Y-m-d", strtotime($duration)) . DEFAULT_DURATION);
			} else { # če je ga pretvorimo v datum
				$duration  = strtotime(date("d.m.Y", strtotime($duration)));
			}
			# pripravimo pravilno obliko datuma za insert v bazo
			$duration = date("Y-m-d",$duration);
						
			$updated = sisplet_query("UPDATE srv_analysis_archive SET name= '$name', note='$note', access='$access', duration='$duration', editid='$global_user_id', access_password='$access_password' WHERE id = '$aid'");

			echo $updated;					
		} else {
			echo $CAE;
		}

	}
	
	/**
	 * 
	 * @param $aid
	 * 
	 * @return 	-1 = invalid $aid
	 * @return 	-2  = file not exist
	 * @return 	-3  = no access
	 * @return 	-4  = invalid profile id
	 * @return 	-5  = no access, pass needed
	 */
	static function CheckArchiveExistance($aid) {
		global $site_path;

		if ($aid < 1 || $aid == null || trim($aid) == "") {
			# invalid $aid
			return -1;
		}

		#podtki profila
		$s = sisplet_query("SELECT filename, access FROM srv_analysis_archive WHERE id='".$aid."' AND sid='".self::$sid."'");
		if ($_GET['debug'] == 1) {
			print_r("SELECT * FROM srv_analysis_archive WHERE id='".$aid."' AND sid='".self::$sid."'");
		}
		if (mysqli_num_rows($s)) {
			
			$row = mysqli_fetch_assoc($s);
			
			# najprej preverimo obstoj datoteke
			$filename = $site_path . 'admin/survey/'.SAA_FOLDER.'/'.$row['filename'];
			if (file_exists($filename)) {
				#preverimo dostop
				if ($row['access'] == '0') {
					return true;
				} else {
					# preverimo ali ima trenuten uid dostop do ankete
					if (self::CheckArchiveAccess()) {
						return true;
					} 
                                        #dostop z geslom
                                        elseif($row['access'] == '2'){
                                                if(isset($_SESSION['archive_access'][$aid]) && $_SESSION['archive_access'][$aid] == '1')
                                                    #uporabnik je vpisal pravileno geslo
                                                    return true;
                                                else
                                                    #uporabnik nima dostopa, za dostop vpogleda je potrebno geslo
                                                    return -5;
                                        }
                                        else  {
						# uporabnik nima dostopa
						return -3;
					}
				}
			
			} else { # return -2 => file not exist
				# pobrišemo morebiten zapis iz baze
				$sqlDelete = sisplet_query("DELETE FROM srv_analysis_archive WHERE id='".$aid."' AND sid='".self::$sid."'");
				
				return -2;
			}
				
		} else {
			#invalid profile ID;
			return -4;
		}
	}
	
	static function CheckArchiveAccess($uid=null) {
		global $global_user_id, $admin_type;
		
		if ($uid == null) {
			$uid = $global_user_id;
		}

		#podtki dostopa
		$a = sisplet_query("SELECT ank_id, uid FROM srv_dostop WHERE ank_id = '".self::$sid."' AND uid='".$uid."'");

		if (mysqli_num_rows($a) || $admin_type === '0') {
			return true;
		} else {
			return false;
		}
	}
				
	
	static function ViewArchive($aid) {
		global $site_path, $global_user_id, $lang, $site_url;

		#izpišemo osnovni html
		$sql = sisplet_query("SELECT * FROM misc WHERE what='name'");
		$row = mysqli_fetch_array($sql);

		// nastavimo jezik
		if (self::$sid > 0) {
			$sql = sisplet_query("SELECT lang_admin FROM srv_anketa WHERE id = '".self::$sid."'");
			$row = mysqli_fetch_array($sql);
			$lang_admin = $row['lang_admin'];
		} else {
			$sql = sisplet_query("SELECT value FROM misc WHERE what = 'SurveyLang_admin'");
			$row = mysqli_fetch_array($sql);
			$lang_admin = $row['value'];
		}
		
		// Naložimo jezikovno datoteko (da se datumin naslov izpiseta v pravem jeziku)
		$file = '../../lang/'.$lang_admin.'.php';
		include($file);
		$_SESSION['langX'] = $site_url .'lang/'.$lang_admin.'.php';
		
		header('Cache-Control: no-cache');
		header('Pragma: no-cache');
		echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
		echo '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n";
		echo '<head>'."\n";
		echo '<title>'.$row['value'].'</title>'."\n";
		echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n";
		echo '<script type="text/javascript" src="script/js-lang.php?lang='.($lang_admin==1?'si':'en').'"></script>';
		if ($_GET['mode'] != 'old') {
			echo '<script type="text/javascript" src="minify/g=jsnew"></script>'."\n";
		} else {
			echo '<script type="text/javascript" src="minify/g=js"></script>'."\n";
		}
		echo '<link type="text/css" href="minify/g=css" media="screen" rel="stylesheet" />'."\n";
		echo '<link type="text/css" href="minify/g=cssPrint" media="print" rel="stylesheet" />'."\n";
		echo '<style type="text/css">.iconHide{display:none;}</style>'."\n";

		echo '</head>'."\n";
		echo '<body id="arch_body" >'."\n";
		
		#polovimo podatke ankete
		SurveyInfo::getInstance()->SurveyInit(self::$sid);
		$naslov = SurveyInfo::getInstance()->getSurveyColumn('akronim');
		if ($naslov == null || trim($naslov) == '') {
			$naslov = SurveyInfo::getInstance()->getSurveyColumn('naslov');
		}

		# podatki arhiva
		$s = sisplet_query("SELECT filename, date, type FROM srv_analysis_archive WHERE id='".$aid."'");
		$row = mysqli_fetch_assoc($s);		
		
		$CAE = self::CheckArchiveExistance($aid);
  
		if ( $CAE > 0) {
			$folder = $site_path . 'admin/survey/'.SAA_FOLDER.'/';
	
			
			$fh = fopen($folder.$row['filename'], 'r');
			$theData = fread($fh, filesize($folder.$row['filename']));
			fclose($fh);
	
			$in  = array('\"', "\'");
			$out = array('"', "'",);
	
			$theData = str_replace($in, $out, $theData);

			// Zaradi kompatibilnosti za nazaj -> ko se se ni naslov in datum shranjeval v file
			if(substr($theData, 0, 24) != '<div id="arch_body_div">'){
				echo '<div id="arch_body_div">'."\n";
				
				echo '<div class="arch_head_date">';
				echo $lang['srv_analiza_archive_date_created'];
				$datetime = strtotime($row['date']);
				echo date("d.m.Y",$datetime);
				echo '</div>';
				
				echo '<h2>'.$naslov.'</h2>';

				echo '<h3>'.$lang['srv_analiza_archive_title'].$lang['srv_analiza_arhiviraj_type_'.$row['type']].'</h3>';
			}
			
			echo $theData;
				
		} 
                else {
			// Zaradi kompatibilnosti za nazaj -> ko se se ni naslov in datum shranjeval v file
			if(substr(0, 25, $theData) != '<div id="arch_body_div">'){
				echo '<div id="arch_body_div">'."\n";
				
				echo '<div class="arch_head_date">';
				echo $lang['srv_analiza_archive_date_created'];
				$datetime = strtotime($row['date']);
				echo date("d.m.Y",$datetime);
				echo '</div>';
			}
                        
                        //to access, password is needed
                        if($CAE == -5){
                            // form for access with password
                            self::DisplayAccessPassword($aid);
                        }
                        //no access/other error
			else{
                            // Izpišemo error
                            self::DisplayError($CAE,false);
                        }
		}
		
		#izpišemo še zaključek html
		echo '</div>'."\n";
		echo '</body>'."\n";
		echo '</html>';
	}
	
	static function DisplayError($CAE, $showButton=true) {
		global $lang; 
		
		echo '<div>';
		echo $lang['srv_analiza_arhiviraj_error_'.$CAE];
		echo '</div>';
		
		if ($showButton) {
        	echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_close'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="closeArchiveAnaliza(); return false;"><span>'.$lang['srv_analiza_arhiviraj_close'].'</span></a></span></span>'."\n";
			echo '<br class="clr" />';
		}
	}
        
        /**
         * Display from for password to access archive
         * @global type $lang
         * @param type $aid - archive id
         */
        static function DisplayAccessPassword($aid) {
		global $lang, $site_url; 

                echo '<br><div style="float:left"><fieldset>';
                echo '<legend>' . $lang['srv_analiza_archive_access'] . '</legend>';

                echo '<form name="archive_access_pass_form" id="archive_access_pass_form" method="post" action="'.$site_url.'admin/survey/AnalysisArchive.php?anketa='.self::$sid.'&aid='. $aid . '">';
                //echo '<input type="hidden" name="archive_id" value="' . $aid . '">';
                
                //user insertet wrong password
                if(isset($_SESSION['archive_access'][$aid]) && $_SESSION['archive_access'][$aid] == '0')
                    echo '<i class="red" id="archive_access_wrong_pass_warning">' . $lang['srv_analiza_archive_access_wrong_pass'] . '</i><br>';
                
                echo '<br>'.$lang['srv_analiza_archive_access_password_label'].': ';
                echo '<input type="password" name="archive_access_pass" id="archive_access_pass" maxlength="25" value="" /><br><br>';

                echo '<span class="spaceRight floatLeft"><div class="buttonwrapper">'
                . '<a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$(\'#archive_access_pass_form\').submit();">';
                echo $lang['srv_analiza_archive_access_button'];
                echo '</a></div></span><br><br>';
                echo '</form></fieldset></div>';
	}
        
        /**
         * Check if archive access password matches
         * @return boolean
         */
        static function CheckArchiveAccessPass() {
		$sql = sisplet_query("SELECT access_password AS pass FROM srv_analysis_archive WHERE id = '".$_POST['archive_id']."'");
                if($sql){
                    $row = mysqli_fetch_array($sql);
                    if($row['pass'] == $_POST['archive_access_pass'])
                        return true;
                    else
                        return false;
                }
                return false;   
	}
	
	static function AskDeleteArchive($aid) {
		global $lang;
		
        echo '<div id="div_analiza_archive_name" class="divPopUp">'."\n";
        
		$CAE = self::CheckArchiveExistance($aid);
  
		if ( $CAE > 0) {
			echo '<h2>Ali ste prepričani?</h2>';
			echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_delete'].'"><a class="ovalbutton ovalbutton_orange" href="#" onclick="doDeleteArchiveAnaliza(\''.$aid.'\'); return false;"><span>'.$lang['srv_analiza_arhiviraj_delete'].'</span></a></span></span>'."\n";
        	echo '<span class="floatRight spaceLeft" ><span class="buttonwrapper" title="'.$lang['srv_analiza_arhiviraj_cancle'].'"><a class="ovalbutton ovalbutton_gray" href="#" onclick="cancleArchiveAnaliza(); return false;"><span>'.$lang['srv_analiza_arhiviraj_cancle'].'</span></a></span></span>'."\n";
            
            echo '<br class="clr" />';	
        } 
        else {
			#izpišemo error
			self::DisplayError($CAE,true);
        }
        
		echo '</div>'."\n"; // end: div_analiza_archive_name
	}

	static function DoDeleteArchive($aid) {
		global $site_path;
		
		$CAE = self::CheckArchiveExistance($aid);
		
		if ( $CAE > 0) {
			$sqlSelect = sisplet_query("SELECT filename FROM srv_analysis_archive WHERE id='".$aid."' AND sid='".self::$sid."'");
			$rowSelect = mysqli_fetch_assoc($sqlSelect);

			#izbrišemo datoteko
			$filename = $site_path . 'admin/survey/'.SAA_FOLDER.'/'.$rowSelect['filename'];
			unlink($filename);
				
			#izbrišemo zapis iz baze
			$sqlDelete = sisplet_query("DELETE FROM srv_analysis_archive WHERE id='".$aid."' AND sid='".self::$sid."'");
			return 1;
		} else {
			#vrnemo  error
			return $CAE;
		}
	}
	
	static function archiveCrosstabBeforeEmail() {
		global $site_path, $site_url, $global_user_id, $lang;
			
		$ArchiveTypes = array(M_ANALIZA_SUMS => 0, M_ANALIZA_DESCRIPTOR=>1, M_ANALIZA_FREQUENCY=>2, M_ANALIZA_CROSSTAB=>3, M_ANALYSIS_MEANS=>4, M_ANALYSIS_TTEST=>5, M_ANALYSIS_BREAK=>6, M_ANALYSIS_CHARTS=>7, M_ANALYSIS_CREPORT=>8);
		if (isset($_POST['podstran'])) {
			$type = $ArchiveTypes[$_POST['podstran']];
		}
		
		$content = $_POST['content'];
		
		if (isset($content) && trim($content) != null) {
			
			#ime zgeneriramo
			$name = 'Arhiv: '.date("d.m.Y H:i:s");
			$access = 0;
			
			$folder = $site_path . 'admin/survey/'.SAA_FOLDER.'/';
			$filename = 'saa_'.self::$sid.'_'.time().'.txt';
	
			#če ni časa trajanja ga zgeneriramo
			$duration = date("Y-m-d");// current date
			$duration  = strtotime(date("Y-m-d", strtotime($duration)) . DEFAULT_DURATION);
			# pripravimo pravilno obliko datuma za insert v bazo
			$duration = date("Y-m-d",$duration);
			
			#dodamo zapis o arhivu v bazo
			$s = sisplet_query("INSERT INTO srv_analysis_archive (sid, uid, name, filename, date, note, access, type, duration, editid) "
				."VALUES ('".self::$sid."', '$global_user_id', '$name', '$filename', NOW(), '', '$access', '$type', '$duration', '$global_user_id')");
			$id = mysqli_insert_id($GLOBALS['connect_db']);
			
						
			// Na zacetek dodamo glavo -> po novem se shrani v file
			SurveyInfo::getInstance()->SurveyInit(self::$sid);
			$naslov = SurveyInfo::getInstance()->getSurveyColumn('akronim');
			if ($naslov == null || trim($naslov) == '') {
				$naslov = SurveyInfo::getInstance()->getSurveyColumn('naslov');
			}
			
			$text = '<div id="arch_body_div"><div class="arch_head_date">'	
					.$lang['srv_analiza_archive_date_created']
					.date("d.m.Y")
					.'</div><h2>'.$naslov.'</h2>'; 
			
			$text .= '<h3>'.$lang['srv_analiza_archive_title'].$lang['srv_analiza_arhiviraj_type_'.$type].'</h3>';
			
			$content = $text.$content;
			
			
			if ($id > 0) { # če smo dodali zapis v bazo shranimo še datoteko
				# zapišemo fajl na disk
				$fh = fopen($folder.$filename, 'w') or die("can't open file");
				fwrite($fh, $content);
				fclose($fh);

				echo $id;
				return $id;
				
			} else {
				echo 0;
				return 0;
			}
		} else {
			echo '-1';
			return '-1';
		}			
	}
	
	static function createArchiveBeforeEmail() {
		global $site_path, $site_url, $global_user_id, $lang;
		
		$ArchiveTypes = array(M_ANALIZA_SUMS => 0, M_ANALIZA_DESCRIPTOR=>1, M_ANALIZA_FREQUENCY=>2, M_ANALIZA_CROSSTAB=>3, M_ANALYSIS_MEANS=>4, M_ANALYSIS_TTEST=>5, M_ANALYSIS_BREAK=>6, M_ANALYSIS_CHARTS=>7, M_ANALYSIS_CREPORT=>8);
		if (isset($_POST['podstran'])) {
			$type = $ArchiveTypes[$_POST['podstran']];
		}
		
		SurveyAnalysis::Init(self::$sid);
		SurveyAnalysis::setUpReturnAsHtml(true);
		
		if($_POST['podstran'] == 'charts'){
			# kreiramo arhiv za grafe
			$SC = new SurveyChart();
			$SC->Init(self::$sid);
			$SC->setUpReturnAsHtml(true);
			$chartTime = $SC->setUpIsForArchive(true);
			$content = $SC->display();
			$settings = array( 'chartTime' => $chartTime);
		}
		elseif($_POST['podstran'] == 'analysis_creport'){
			#kreiramo arhiv za creport
			$SCR = new SurveyCustomReport(self::$sid);
			$SCR->setUpReturnAsHtml(true);
			$SCR->setUpIsForArchive(true);
			$content = $SCR->displayReport();
		}
		else{
			if (isset($_POST['content']) && trim($_POST['content']) != '') {
				$content = $_POST['content'];
			} else {
				$content = SurveyAnalysis::Display();
			}
		}
			
		if (isset($content) && trim($content) != null) {
			
			#ime zgeneriramo
			$name = 'Arhiv: '.date("d.m.Y H:i:s");
			$access = 0;
			
			$folder = $site_path . 'admin/survey/'.SAA_FOLDER.'/';
			$filename = 'saa_'.self::$sid.'_'.time().'.txt';
	
			#če ni časa trajanja ga zgeneriramo
			$duration = date("Y-m-d");// current date
			$duration  = strtotime(date("Y-m-d", strtotime($duration)) . DEFAULT_DURATION);
			# pripravimo pravilno obliko datuma za insert v bazo
			$duration = date("Y-m-d",$duration);
			
			#dodamo zapis o arhivu v bazo
			$s = sisplet_query("INSERT INTO srv_analysis_archive (sid, uid, name, filename, date, note, access, type, duration, editid) "
				."VALUES ('".self::$sid."', '$global_user_id', '$name', '$filename', NOW(), '', '$access', '$type', '$duration', '$global_user_id')");
			$id = mysqli_insert_id($GLOBALS['connect_db']);
						
			
			// Na zacetek dodamo glavo -> po novem se shrani v file
			SurveyInfo::getInstance()->SurveyInit(self::$sid);
			$naslov = SurveyInfo::getInstance()->getSurveyColumn('akronim');
			if ($naslov == null || trim($naslov) == '') {
				$naslov = SurveyInfo::getInstance()->getSurveyColumn('naslov');
			}
						
			$text = '<div id="arch_body_div">';
		
			SurveySetting::getInstance()->Init(self::$sid);
			$survey_hide_title = SurveySetting::getInstance()->getSurveyMiscSetting('survey_hide_title');	
			
			// Ce ne prikazujemo naslova ankete to skrijemo
			if($survey_hide_title == 0){
				$text .= '<div class="arch_head_date">'	
						.$lang['srv_analiza_archive_date_created']
						.date("d.m.Y")
						.'</div><h2>'.$naslov.'</h2>';
			}
			
			// Porocilo po meri ima custom naslov
			$text .= ($survey_hide_title == 1) ? '<h2>' : '<h3>';
			if($type == 8){		
				$SCR = new SurveyCustomReport(self::$sid);
				$creport_title = $SCR->getTitle();
				$text .= $creport_title;
			}
			else{
				$text .= $lang['srv_analiza_archive_title'].$lang['srv_analiza_arhiviraj_type_'.$type];
			}
			$text .= ($survey_hide_title == 1) ? '</h2>' : '</h3>';
			
			$content = $text.$content;
			
			
			if ($id > 0) { # če smo dodali zapis v bazo shranimo še datoteko
				# zapišemo fajl na disk
				$fh = fopen($folder.$filename, 'w') or die("can't open file");
				fwrite($fh, $content);
				fclose($fh);

				echo $id;
				return $id;
				
			} else {
				echo 0;
				return 0;
			}
		} else {
			echo '-1';
			return '-1';
		}			
			
	}
	
	/** zgenerira html iz analize in ga shrani kot arhiv.
	 * 
	 */
	static function createArchiveFromAnaliza() {
		$content = null;
		if($_POST['podstran'] == 'charts'){
			# kreiramo arhiv za grafe
			$SC = new SurveyChart();
			$SC->Init(self::$sid);
			$SC->setUpReturnAsHtml(true);
			$chartTime = $SC->setUpIsForArchive(true);
			$content = $SC->display();
			$settings = array( 'chartTime' => $chartTime);
		}
		elseif($_POST['podstran'] == 'analysis_creport'){
			#kreiramo arhiv za creport
			$SCR = new SurveyCustomReport(self::$sid);
			$SCR->setUpReturnAsHtml(true);
			$SCR->setUpIsForArchive(true);
			$content = $SCR->displayReport();
		}
		else{
			if ($_POST['podstran'] == 'anal_arch') {
				$_POST['podstran'] = 'sumarnik';
			}
			if (!isset($_POST['content'])) {
				SurveyAnalysis::Init(self::$sid);
				SurveyAnalysis::setUpIsForArchive(true);
				SurveyAnalysis::setUpReturnAsHtml(true);
				
				$content = SurveyAnalysis::Display();
			} else {
				$content = $_POST['content'];
			}
		}	

		$name = (isset($_POST['name']) && trim($_POST['name'])) ? trim($_POST['name']) : null;
		$note = (isset($_POST['note']) && trim($_POST['note'])) ? trim($_POST['note']) : null;
		$access = (isset($_POST['access']) && trim($_POST['access'])) ? trim($_POST['access']) : 0;
                $access_password = (isset($_POST['access_password']) && trim($_POST['access_password'])) ? trim($_POST['access_password']) : null;
		$duration = (isset($_POST['duration']) && trim($_POST['duration'])) ? trim($_POST['duration']) : null;
		$durationType = (isset($_POST['durationType']) && trim($_POST['durationType'])) ? trim($_POST['durationType']) : 0;
		
		$ArchiveTypes = array(M_ANALIZA_SUMS => 0, M_ANALIZA_DESCRIPTOR=>1, M_ANALIZA_FREQUENCY=>2, M_ANALIZA_CROSSTAB=>3, M_ANALYSIS_MEANS=>4, M_ANALYSIS_TTEST=>5, M_ANALYSIS_BREAK=>6, M_ANALYSIS_CHARTS=>7, M_ANALYSIS_CREPORT=>8);
		if (isset($_POST['podstran']))
			$type = $ArchiveTypes[$_POST['podstran']];
		if (isset($content) && trim($content) != null && self::$sid > 0) {

			SurveyAnalysisArchive :: Init(self::$sid);
			SurveyAnalysisArchive :: CreateArchive($content,$name,$note,$access,$type,$duration,$durationType,$settings,$access_password);
						
		} else {
			echo 'Error! (SurveyAnalysisArchive :: CreateArchive)';
		}
		
	}
}
?>