<?php
class Library {

    var $SurveyAdmin;
    var $Branching;
	
    var $tab = 2;           // tab pove kater tab je odprt (od 4ih)
    var $tip = 1;          	// tip pove v bazi srv_library_folder za kater tip gre (0-vpra�anja, 1-ankete)
    var $prva = 0;      	// prva pove, ce je to library na prvi strani (1) ali v urejanju ankete (0)
	
    // v odvisnosti od mode prilagajamu UI. (Mode je odvisen od tipa ankete - survey_type)
    var $mode = -1; 		// mode: -1 -> library pri datotekah, 0 -> glasovanje, 1 -> forma, 2 -> ankata na več straneh, 3 -> ifi
    var $skin = 0;
	
	private $isSearch = 0;					// ali izvajamo search po anektah
	private $searchString = '';				// geslo po katerem iscemo po anketah
	private $searchStringProcessed = array();	// geslo po katerem iscemo po anketah, obdelano (skrajsano da isce tudi po drugih sklanjatvah)
	private $searchSettings = array();		// nastavitve searcha
	

    /**
    * @desc konstruktor
    */
    function __construct ($_options = array()) {
    	global $surveySkin;

        if (isset($surveySkin))
            $this->skin = $surveySkin;

        $this->SurveyAdmin = new SurveyAdmin(1, -1);
        $this->Branching = new Branching($this->SurveyAdmin->anketa);

        if (isset($_options['tab'])) {
			$this->tab = $_options['tab'];
            if ($this->tab <= 1)
                $this->tip = 0;
            else
                $this->tip = 1;
        } 
        else if (isset($_POST['tab'])) {
            $this->tab = ($_POST['tab'] == 0 || $_POST['tab'] == 'undefined') ? 0 : $_POST['tab'];
            if ($this->tab <= 1)
                $this->tip = 0;
            else
                $this->tip = 1;
        } 
        else {
            $this->tab = 0;
            $this->tip = 0;
        }

        if (isset($_options['prva'])) {
        	$this->prva = $_options['prva'];
        } 
        else {
        	$this->prva = 0;
        }

        if (isset($_GET['tab'])) $this->tab = (int)$_GET['tab'];

        // nastavimo mode v odvisnosti od survey_type ( če nismo v anketi je -1)
        if (isset($this->SurveyAdmin->anketa) && $this->SurveyAdmin->anketa > 0) {
        	$_st = $this->SurveyAdmin->getSurvey_type($this->SurveyAdmin->anketa);
			
            if ($_st > -1)
				$this->mode = $_st;
        }
        
		$this->repareTabs();
		
		// Preverimo ce gre za search po anketah
		if(isset($_GET['search']) && $_GET['search'] != ''){
			$this->isSearch = 1;
			$this->searchString = str_replace("\\", "", trim($_GET['search']));
			
			// Iscemo po naslovu ali vsebini
			$this->searchSettings['stype'] = (isset($_GET['stype'])) ? $_GET['stype'] : '0';
		}
	}
	

    /**
    * @desc prikaze knjiznico znotraj ankete na desni
    */
    function display () {
        global $admin_type;
		global $global_user_id;
		global $lang;

		echo '<div id="library_title">';
		echo '<span class="faicon library"></span> '.$lang['srv_library'];
		echo '<a href="#" title="'.$lang['srv_zapri'].'" onclick="change_mode(\'toolboxback\', \'1\'); return false;"><span class="faicon close" style="float:right;"></span></a>';
		echo '</div>';

        $this->display_tabs();

        echo '<div id="library">';

        echo '<div id="libraryInner">';
        $this->display_folders();
        echo '</div><!-- id="libraryInner" -->';

        echo '</div><!-- id="library" -->';
    }

    /**
    * @desc prikaze tabe za izbiro
    */
    function display_tabs () {
        global $lang;

		echo '<p class="display_tabs">';
	    echo '<span' . ($this->tab==0 || $this->tab==1 ? ' class="highlightTabBlackLeft"' : ' class="nohighlight"') . ' >';
		echo '<a href="/" onclick="display_knjiznica(\'0\'); return false;" title="'.$lang['srv_vprasanja'].'"><span>' . $lang['srv_vprasanja'] . '</span></a></span>';
		echo '<span' . ($this->tab==2 || $this->tab==3 ? ' class="highlightTabBlackRight"' : ' class="nohighlight"') . ' >';
		echo '<a href="/" onclick="display_knjiznica(\'2\'); return false;" title="'.$lang['srv_ankete'].'"><span>' . $lang['srv_ankete'] . '</span></a></span>';
		echo '</p>';
    }

    function display_folders () {
		global $global_user_id;
		global $lang;

		// Knjiznica znotraj posamezne ankete
		if ($this->prva == 0) {
			$this->display_contentfolders(0, 0);
	        $this->display_contentfolders(0, $global_user_id);

	        if ($this->tip == 1) {
	        	echo '<p class="bold"><a href="index.php?a=knjiznica">'.$lang['srv_library_edit'].'</a><br>';

	        	$sql = sisplet_query("SELECT * FROM srv_library_anketa WHERE uid='".$global_user_id."' AND ank_id='".$this->SurveyAdmin->anketa."'");
	        	if (mysqli_num_rows($sql) == 0) {
		        	echo '<div class="buttonwrapper" style="float:left;">
					<a class="ovalbutton ovalbutton_orange btn_savesettings" onclick="add_to_my_library(); return false;" href="#"><span>'.$lang['srv_library_edit_add'].'</span></a>
					</div></p>';
	    		}

	    	} else {
				echo '<p>'.$lang['srv_library_q_txt'].'</p>';
			}

			//echo '<a style="padding:5px; background-color: white; bottom: 1px; position: absolute; right: 18px;" onclick="change_mode(\'toolboxback\', \'1\'); return false;" href="#">'.$lang['srv_zapri'].'</a>';
		} 
		// Knjiznica na prvi strani zraven mojih anket
		else {
			// Na prvi strani imamo search	
			if($this->isSearch == 1){
				echo '<div id="searchLibrarySettings">';
				$this->displaySearchSettings();
				echo '</div>';
				
				echo '<div class="clr"></div>';
				
				$this->display_contentfolders_searchList();
			}				
			else{
				echo '<div id="searchLibrarySurveys">';
				$this->displaySearch();
				echo '</div>';
				
				echo '<div class="clr"></div>';
				
				$this->display_contentfolders();
			}
		}
    }

    /**
    * @desc prikaze folderje v knjiznici
    */
    function display_contentfolders ($parent = 0, $uid = -1) {
        global $lang;
        global $admin_type;
        global $global_user_id;
        global $site_url;

		$language = "";

        if ($uid == -1) {
	        if ($this->tab == 0 || $this->tab == 2) {
	            $uid = 0;
			} else {
	            $uid = $global_user_id;
			}
		}

		if ($parent == 0 && $uid == 0) {
			$language = " AND lang='$lang[id]' ";
		}

        $cookie = $_COOKIE['library_folders'];
        // da se v url lahko doda odprte folderje: &libfolder=131-147 (more vkljucevat tudi parente)
		if (isset($_GET['libfolder'])) $cookie .= '-'.$_GET['libfolder'].'-';

        if ($parent == 0) {
	        // v skrite html elemente shranimo tab, in prva
			echo '<input type="hidden" name="lib_tab" id="lib_tab" value="'.($this->tab == 0 || $this->tab == "" ? "0" : "$this->tab").'">';
			echo '<input type="hidden" name="lib_tip" id="lib_tip" value="'.($this->tip == 0 || $this->tip== "" ? "0" : "$this->tip").'">';
			echo '<input type="hidden" name="lib_prva" id="lib_prva" value="'.($this->prva == 0 || $this->prva== "" ? "0" : "$this->prva").'">';

			echo '<ul title="'.($uid==0?$lang['srv_library_left']:$lang['srv_library_left_right']).'" style="padding-left:0;" class="'.($admin_type==0 || $uid == $global_user_id?'can_edit':'').'">'."\n";
        }

        $sql = sisplet_query("SELECT id, naslov FROM srv_library_folder WHERE uid='$uid' AND parent = '$parent' AND tip='$this->tip' $language ORDER BY naslov");
        if (!$sql) 
            echo mysqli_error($GLOBALS['connect_db']);

        if (mysqli_num_rows($sql) == 0 && $uid > 0 && $parent == 0) {
            
            // za prvic ko pride user, da mu dodamo folder
            if ($this->tip == 0)
            	$naslov = $lang['srv_moja_vprasanja'];
            else
            	$naslov = $lang['srv_moje_ankete'];
            
            sisplet_query("INSERT INTO srv_library_folder (uid, tip, naslov, parent, lang) VALUES ('$uid', '$this->tip', '$naslov', '0', '$lang[id]')");
            
            $sql = sisplet_query("SELECT id, naslov FROM srv_library_folder WHERE uid='$uid' AND parent = '$parent' AND tip='$this->tip' ORDER BY naslov");
            if (!$sql) 
                echo mysqli_error($GLOBALS['connect_db']);
        }

        while ($row = mysqli_fetch_array($sql)) {
            if (strpos($cookie, '-'.$row['id'].'-')=== false && $parent!=0)
                $hidden = true;
            else
                $hidden = false;


            echo '  <li id="li'.$row['id'].'" eid="'.$row['id'].'" class="folder" name="folder">';

            if ($parent != 0)
                echo '  <a href="/" onclick="javascript:library_folders_plusminus(\''.$row['id'].'\',\''.$this->tab.'\',\''.$this->prva.'\'); return false;" id="f_pm_'.$row['id'].'"><span class="faicon icon-blue '.($hidden?'plus':'minus').'"></span></a>';
            else
                echo '  <span class="sprites spacer12"></span>';

            echo '  <span class="'.($parent!=0 || $uid>0 || $admin_type==0 ? ' folderdrop' : '').'" id="sp'.$row['id'].'" eid="'.$row['id'].'"><span class="faicon folder icon-blue'.($parent!=0?' movable':'').'"></span>'.
                 '  <span '.($admin_type==0||$uid>0? 'title="'.$lang['srv_rename_profile'].'" onclick="folder_rename(\''.$row['id'].'\'); return false;"':'').'>'.$row['naslov'].'</span>';
            if ($admin_type==0 || $uid>0)
            	echo '  <a href="/" onclick="javascript:library_new_folder(\''.$row['id'].'\',\''.$uid.'\'); return false;"><span class="faicon add icon-blue-hover-orange small new_folder" id="new_folder_'.$row['id'].'" title="'.$lang['srv_newfolder'].'"></span></a>';

            if ($parent != 0 && ($admin_type==0||$uid>0) )
                echo '  <a href="/" onclick="javascript:library_delete_folder(\''.$row['id'].'\',\''.$this->tab.'\',\''.$this->prva.'\'); return false;"><span class="faicon remove icon-orange small delete_folder" id="delete_folder_'.$row['id'].'" title="'.$lang['srv_deletefolder'].'"></span></a>';

            echo '  </span>'."\n";

            echo '<ul id="folder_'.$row['id'].'"'.($hidden?' style="display:none"':'').'>'."\n";

            $this->display_contentfolders($row['id'], $uid);

            $this->display_ifs($row['id']);

			// Izpis spremenljivk v folderju - v root folderju ne izpisujemo vprasanj (ker jih itak ne sme bit)
			if(!($this->tip == 0 && $parent == 0 && $uid == 0)){

				if ($this->tip == 0)
					$sql1 = sisplet_query("SELECT * FROM srv_spremenljivka WHERE folder = '$row[id]' AND gru_id='-1' ORDER BY naslov ASC");
				else
					$sql1 = sisplet_query("SELECT * FROM srv_anketa a, srv_library_anketa l WHERE a.id=l.ank_id AND l.folder='$row[id]' AND l.uid='$uid' ORDER BY naslov ASC");
				if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);

				if (mysqli_num_rows($sql1) > 0) {

					while ($row1 = mysqli_fetch_array($sql1)) {

                        echo '<li class="anketa tip'.$this->tip.'">';
                        echo '        <div copy="'.$row1['id'].'" eid="'.$row1['id'].'" class="folder_container '.($this->tip==0?'new_spr':'').'" name="library">';

                        echo '          <div class="folder_right">';

                        if ($this->tip == 0) {
                            //echo '<a href="/" onclick="javascript:copy_spremenljivka(\''.$row1['id'].'\'); return false;"><img src="img_'.$this->skin.'/copy.png" title="'.$lang['srv_copy_spr'].'" /></a>';
                            if ($admin_type==0 or $uid==$global_user_id)
                                echo ' <a href="/" onclick="library_brisi_spremenljivko(\''.$row1['id'].'\', \''.$lang['srv_brisispremenljivkoconfirm'].'\',\''.$this->tab.'\',\''.$this->prva.'\'); return false;"><span class="faicon delete_circle icon-orange" title="'.$lang['srv_brisispremenljivko'].'"></span></a>';
                        }
                        else {
                            SurveyInfo::getInstance()->SurveyInit($row1['id']);

                            if ($this->prva == "1") {
                                
                                // Dodaj anketo v javno knjiznico
                                if ($admin_type == 0 && $this->tab == 3) {

                                    $sqlPublic = sisplet_query("SELECT * FROM srv_library_anketa WHERE ank_id='".$row1['id']."' AND uid='0'");
                                    if (!$sqlPublic) echo mysqli_error($GLOBALS['connect_db']);

                                    // Anketa ze obstaja v javni knjiznici - jo pobrisemo
                                    if (mysqli_num_rows($sqlPublic) > 0) {
                                        echo '  <a href="/" onclick="surveyList_knjiznica_new(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_ank_lib_off'].'">';
                                        echo '      <span class="sprites faicon remove icon-orange small"></span> <span class="library_item_setting_text">'.$lang['srv_ank_lib_off'].'</span>';
                                        echo '  </a>';
                                    }
                                    // Anketo dodamo v javno knjiznico
                                    else{
                                        echo '  <a href="/" onclick="surveyList_knjiznica_new(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_ank_lib_on'].'">';
                                        echo '      <span class="sprites faicon library"></span> <span class="library_item_setting_text">'.$lang['srv_ank_lib_on'].'</span>';
                                        echo '  </a>';
                                    }
                                }

                                // nova anketa kot template iz knjiznice
                                echo '<a href="/" onclick="anketa_copy(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_library_use_survey'].'"><span class="faicon copy"></span> <span class="library_item_setting_text">'.$lang['srv_anketacopy'].'</span></a> ';
                                if(SurveyInfo::getInstance()->checkSurveyModule('uporabnost'))                                
                                    echo '<a href="'.$site_url.'main/survey/uporabnost.php?anketa='.SurveyInfo::getInstance()->getSurveyHash().'&preview=on" target="_blank" title="'.$lang['srv_poglejanketo'].'"><span class="faicon preview"></span> <span class="library_item_setting_text">'.$lang['srv_poglejanketo2'].'</span></a> ';
                                else
                                    echo '<a href="'.$site_url.'main/survey/index.php?anketa='.SurveyInfo::getInstance()->getSurveyHash().'&preview=on" target="_blank" title="'.$lang['srv_poglejanketo'].'"><span class="faicon preview"></span> <span class="library_item_setting_text">'.$lang['srv_poglejanketo2'].'</span></a> ';
                                
                                // brisi iz knjiznice
                                if ($admin_type == 0) {
                                    echo ' <a href="index.php?anketa='.$row1['id'].'" title="'.$lang['srv_editirajanketo'].'"><span class="faicon edit"></span> <span class="library_item_setting_text">'.$lang['edit3'].'</span></a>';
                                }
                                
                                if ($admin_type==0 && $this->tab==2) {// sistemska
                                    echo ' <a href="/" onclick="library_del_anketa(\''.$row1['id'].'\', \''.$lang['srv_anketadeletelibrary_4'].'\',\''.$this->tab.'\',\''.$this->prva.'\'); return false;" title="'.$lang['srv_ank_lib_off'].'"><span class="sprites faicon remove icon-orange small"></span> <span class="library_item_setting_text">'.$lang['hour_remove'].'</span></a>';
                                }
                                
                                if($this->tab==3){// moja knjiznica
                                    echo ' <a href="/" onclick="library_del_myanketa(\''.$row1['id'].'\', \''.$lang['srv_anketadeletelibrary_3'].'\',\''.$this->tab.'\',\''.$this->prva.'\'); return false;" title="'.$lang['srv_ank_mylib_off'].'"><span class="faicon remove icon-orange small"></span> <span class="library_item_setting_text">'.$lang['hour_remove'].'</span></a>';
                                }
                            }
                            else {
                                if(SurveyInfo::getInstance()->checkSurveyModule('uporabnost'))                                
                                    echo '<a href="'.$site_url.'main/survey/uporabnost.php?anketa='.SurveyInfo::getInstance()->getSurveyHash().'&preview=on" target="_blank" title="'.$lang['srv_poglejanketo'].'"><span class="faicon preview"></span></a>';
                                else
                                    echo '<a href="'.$site_url.'main/survey/index.php?anketa='.SurveyInfo::getInstance()->getSurveyHash().'&preview=on" target="_blank" title="'.$lang['srv_poglejanketo'].'"><span class="faicon preview"></span></a>';
                                
                                // moznost da povozi anketo z anketo iz knjiznice
                                //TEGA NE DOVOLIMO KER NI OK DA SE KAR PREPISE OBSTOJECO ANKETO - anketo iz knjiznice se lahko po novem dodaja samo iz mojih anket oz. pri ustvarjanju
                                echo ' <a href="/" onclick="alert_copy_anketa(\''.$row1['id'].'\'); return false;"><span class="sprites copy_small" title="'.$lang['srv_copy_srv'].'"></span></a>';
                            }
                        }
                        echo '</div>';

                        echo '          <div class="folder_left'.($this->tip==1?' indent"':'" onclick="library_spremenljivka_new(\''.$row1['id'].'\'); return false;"').'>';
                        if ($this->tip == 1 && $this->prva == "0") {
                            echo '<a href="/" onclick="javascript:library_anketa_plusminus(\''.$row1['id'].'\', this); return false;"><span class="faicon icon-blue plus" style="opacity: 0.3"></span></a> ';
                        } else {
                            if ($this->tip != 0)
                                echo '  <span class="sprites spacer12"></span>';
                        }


                        if ($this->tip == 0) {
                            if ($row1['tip']==1 || $row1['tip']==2 || $row1['tip']==3 || $row1['tip']==21 || $row1['tip']==7)
                                $ikonca = 'osnovna_vprasanja';
                            elseif ($row1['tip']==6 || $row1['tip']==16 || $row1['tip']==19 || $row1['tip']==20)
                                $ikonca = 'table';
                            else
                                $ikonca = 'other_vprasanja';
                        } else {
                            $ikonca = 'anketa';
                        }

                        echo '<span class="faicon '.$ikonca.' mapca icon-blue" style="display:inline-block"></span> ';
                        echo skrajsaj(strip_tags($row1['naslov']), 40).'</a>'."\n";

                        echo '          </div>';


                        if ($this->prva == "0") {

                            echo '<div id="anketa_vprasanja_'.$row1['id'].'" class="anketa_vprasanja">';
                            if ($this->tip == 1) {
                                $sql2 = sisplet_query("SELECT s.id, s.naslov, s.tip FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$row1[id]'");
                                while ($row2 = mysqli_fetch_array($sql2)) {

                                    if ($row2['tip']==1 || $row2['tip']==2 || $row2['tip']==3 || $row2['tip']==21 || $row2['tip']==7)
                                        $ikonca = 'osnovna_vprasanja';
                                    elseif ($row2['tip']==6 || $row2['tip']==16 || $row2['tip']==19 || $row2['tip']==20)
                                        $ikonca = 'table';
                                    else
                                        $ikonca = 'other_vprasanja';

                                    echo '<span class="new_spr" copy="'.$row2['id'].'" onclick="library_spremenljivka_new(\''.$row2['id'].'\'); return false;"><span class="faicon '.$ikonca.' icon-blue" title="'.$lang['srv_copy_spr'].'" style="display:inline-block"></span>';
                                    echo ' '.skrajsaj(strip_tags($row2['naslov']), 40).'</span>';

                                }
                            }

                            echo '</div>';
                        }

                        echo '</div></li>';
					}

				}
			}

            echo '    </ul>'."\n";

            echo '  </li>'."\n";
        }

        if ($parent == 0) {
            echo '</ul>'."\n";
        }

        if ( $parent == 0 ) {
            ?>
            <script type="text/javascript">
            	$(function() {
            		library();
				});
            </script>
            <?php
        }
    }

    /**
    * @desc prikaze ife / bloke v knjiznici
    */
    function display_ifs ($folder) {
        global $lang, $admin_type;

        $sql = sisplet_query("SELECT * FROM srv_if WHERE folder = '$folder' ORDER BY label ASC, id ASC");
        while ($row = mysqli_fetch_array($sql)) {

            echo '<li class="anketa tip0">';
            echo '        <div eid="'.$row['id'].'" copy="'.$row['id'].'" class="folder_container new_if" name="library_if">';

            echo '          <div class="folder_right">';
            if ($admin_type==0 or $this->tab==1)
        		echo ' <a href="/" onclick="library_if_remove(\''.$row['id'].'\', \''.$lang['srv_brisispremenljivkoconfirm'].'\'); return false;"><span class="faicon delete_circle icon-orange" title="'.($row['tip']==0?$lang['srv_if_rem']:$lang['srv_block_rem']).'"></span></a>';
            echo '          </div>';

            echo '          <div class="folder_left'.($this->tip==1?' indent':'').'" onclick="library_if_new(\''.$row['id'].'\'); return false;">';

            echo '<span class="faicon '.($row['tip']==0?'if':'b').' mapca icon-blue"></span> ';

            echo skrajsaj(strip_tags(($row['label']!=''?$row['label']:($row['tip']==0?$lang['srv_pogoj']:$lang['srv_blok']))), 40).'</a>'."\n";

            echo '          </div>';

            echo '</div></li>';
        }

    }
  
  
	/**
  	* @desc prikaze seznam iskanih anket v knjiznici
  	*/
  	function display_contentfolders_searchList () {
  		global $lang;
  		global $admin_type;
  		global $global_user_id;
  		global $site_url;

  		$language = "";

		if ($this->tab == 2)
			$uid = 0;
		else
			$uid = $global_user_id;

  		if ($uid == 0)
  			$language = " AND lang='$lang[id]' ";

		// v skrite html elemente shranimo tab, in prva
		echo '<input type="hidden" name="lib_tab" id="lib_tab" value="'.$this->tab.'">';
		echo '<input type="hidden" name="lib_tip" id="lib_tip" value="1">';
		echo '<input type="hidden" name="lib_prva" id="lib_prva" value="1">';

		echo '<ul title="'.($uid == 0 ? $lang['srv_library_left'] : $lang['srv_library_left_right']).'" style="padding-left:0; margin-left:-12px; margin-top:10px;" class="'.($admin_type == 0 || $uid == $global_user_id ? 'can_edit' : '').'">'."\n";


		// Sestavimo query za search po knjiznici
		$search_query = $this->getSearchString();
		
		$sql1 = sisplet_query("SELECT sa.* 
								FROM srv_anketa sa, srv_library_anketa l, srv_grupa sg, srv_spremenljivka ss, srv_vrednost sv
								WHERE sa.id=l.ank_id AND sg.ank_id=sa.id AND ss.gru_id=sg.id AND sv.spr_id=ss.id
								AND l.uid='$uid' ".$search_query."
								GROUP BY sa.id
								ORDER BY sa.naslov ASC");
		if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
		
		// Loop po najdenih anketah
		if (mysqli_num_rows($sql1) > 0) {
			while ($row1 = mysqli_fetch_array($sql1)) {
				
				echo '<li class="anketa tip1">';
				echo '        <div copy="'.$row1['id'].'" eid="'.$row1['id'].'" class="folder_container" name="library">';

				echo '          <div class="folder_right">';
				
				SurveyInfo::getInstance()->SurveyInit($row1['id']);
				
				// nova anketa kot template iz knjiznice
				echo '<a href="/" onclick="anketa_copy(\''.$row1['id'].'\'); return false;" title="'.$lang['srv_library_use_survey'].'"><span class="faicon copy"></span> '.$lang['srv_anketacopy'].'</a> ';
				
				// Preview
                
                if(SurveyInfo::getInstance()->checkSurveyModule('uporabnost'))
				    echo '<a href="'.$site_url.'main/survey/uporabnost.php?anketa='.SurveyInfo::getInstance()->getSurveyHash().'&preview=on" target="_blank" title="'.$lang['srv_poglejanketo'].'"><span class="faicon preview"></span> '.$lang['srv_poglejanketo2'].'</a> ';
                else
				    echo '<a href="'.$site_url.'main/survey/index.php?anketa='.SurveyInfo::getInstance()->getSurveyHash().'&preview=on" target="_blank" title="'.$lang['srv_poglejanketo'].'"><span class="faicon preview"></span> '.$lang['srv_poglejanketo2'].'</a> ';
				
				// Urejanje - admin
				if ($admin_type == 0)
					echo ' <a href="index.php?anketa='.$row1['id'].'" title="'.$lang['srv_editirajanketo'].'"><span class="faicon edit"></span> '.$lang['edit3'].'</a>';
				
				// Brisanje - admin iz javne knjiznice
				if ($admin_type == 0 && $this->tab == 2)
					echo ' <a href="/" onclick="library_del_anketa(\''.$row1['id'].'\', \''.$lang['srv_anketadeletelibrary_4'].'\',\''.$this->tab.'\',\''.$this->prva.'\'); return false;" title="'.$lang['srv_ank_lib_off'].'"><span class="faicon remove icon-orange small"></span> '.$lang['hour_remove'].'</a>';
				
				// Brisanje - uporabnik iz moje knjiznice
				if($this->tab == 3)
					echo ' <a href="/" onclick="library_del_myanketa(\''.$row1['id'].'\', \''.$lang['srv_anketadeletelibrary_3'].'\',\''.$this->tab.'\',\''.$this->prva.'\'); return false;" title="'.$lang['srv_ank_mylib_off'].'"><span class="faicon remove icon-orange small"></span> '.$lang['hour_remove'].'</a>';
				
				echo '</div>';


				echo '          <div class="folder_left'.($this->tip==1?' indent"':'" onclick="library_spremenljivka_new(\''.$row1['id'].'\'); return false;"').'>';	
				echo '  <span class="sprites spacer12"></span>';

				$ikonca = 'anketa';

				echo '<span class="faicon '.$ikonca.' mapca icon-blue" style="display:inline-block"></span> ';
				
				// Ce gre za search moramo ustrezno pobarvati najden del besede
				$text_searched = $row1['naslov'];
				if($this->isSearch == 1 && $this->searchSettings['stype'] == '0'){
					foreach($this->searchStringProcessed as $search_word){

						// Pobarvamo najden niz v naslovu ankete
						preg_match_all("/$search_word+/i", $text_searched, $matches);
						if (is_array($matches[0]) && count($matches[0]) >= 1) {
							foreach ($matches[0] as $match) {
								$text_searched = str_replace($match, '<span class="red">'.$match.'</span>', $text_searched);
							}
						}
					}					
				}
				
				echo skrajsaj($text_searched, 40).'</a>'."\n";

				echo '          </div>';
				echo '</div></li>';
			}
		}

		echo '    </ul>'."\n";
		echo '  </li>'."\n";


  		if ($parent == 0) {
  			echo '</ul>'."\n";
  		}

  		if ( $parent == 0 ) {
  			?>
  			<script type="text/javascript">
  				$(function() {
  					library();
  				});
  			</script>
  			<?php
  		}
  	}
  
	// Prikazemo nastavitve za napredno iskanje ce iscemo po anketah znotraj knjiznice
	private function displaySearchSettings(){
		global $lang;
		global $site_url;

		echo '<span class="title">'.$lang['s_search_settings_lib'].'</span>';

		echo '<form method="GET" id="1kasf2" action="'.$site_url.'admin/survey/index.php?a=knjiznica">';

		// Hidden polja za knjiznico
		echo '<input type="hidden" name="a" value="knjiznica">';
		if($this->tab == '3')
			echo '<input type="hidden" name="t" value="moje_ankete">';

		// Iskano geslo
		echo '<p>';
        echo '	<span class="bold">'.$lang['s_search2'].':</span> <input type="text" name="search" id="searchMySurveyText" value="'.htmlentities($this->searchString).'" placeholder="' . $lang['s_search'] . '" />';
		echo '</p>';
		
		// Iskanje po naslovu ali avtorju ali besedilu
		echo '<p>';
		echo '	<span>'.$lang['s_thru'].': </span>';
		echo '	<label for="stype_0"><input type="radio" name="stype" id="stype_0" value="0" '.($this->searchSettings['stype'] == '0' ? ' checked="checked"' : '').' />'.$lang['s_title'].'</label>';
		echo '	<label for="stype_1"><input type="radio" name="stype" id="stype_1" value="1" '.($this->searchSettings['stype'] == '1' ? ' checked="checked"' : '').' />'.$lang['s_text'].'</label>';
		echo '</p>';
				
		// Gumba isci in zapri
		echo '<span style="margin-top: 10px;" class="floatRight spaceRight">';
		echo '	<div class="buttonwrapper floatLeft spaceRight">';
		echo '		<a class="ovalbutton ovalbutton_gray" href="'.$site_url.'admin/survey/index.php?a=knjiznica'.($this->tab == '3' ? '&t=moje_ankete' : '').'"><span>'.$lang['srv_zapri'].'</span></a>';
		echo '	</div>';
		echo '	<div class="buttonwrapper floatRight">';
		echo '		<a class="ovalbutton ovalbutton_orange" href="#" onclick="$(\'#1kasf2\').submit(); return false;"><span>'.$lang['s_search'].'</span></a>';
		echo '	</div>';
		echo '</span>';
		
		// Link na isci po mojih anketah
		echo '<span class="link"><a href="'.$site_url.'admin/survey/index.php?search='.$this->searchString.'">'.$lang['s_search_mySurvey'].'</a></span>';
		
		echo '<input style="display: none;" value="Išči" type="submit">';
		
        echo '</form>';		
	}
	
	// Prikazemo search okno za iskanje po anketah znotraj knjiznice
	private function displaySearch(){
		global $lang;
		global $site_url;
		
		echo '<form method="GET" id="1kasmysurvey" action="'.$site_url.'admin/survey/index.php">';      
	    
		// Hidden polja za knjiznico
		echo '<input type="hidden" name="a" value="knjiznica">';
		if($this->tab == '3')
			echo '<input type="hidden" name="t" value="moje_ankete">';
		
		//echo '<span class="sprites search"></span> ';
        echo '<input id="searchMySurvey" class="floatLeft" type="text" value="" placeholder="' . $lang['s_search_Library'] . '" name="search" />';
		
		//echo '<input type="submit" value="' . $lang['s_search'] . '" />'; 
		echo '	<div class="buttonwrapper floatLeft">';
		echo '		<a class="ovalbutton ovalbutton_orange" href="#" onclick="$(\'#1kasmysurvey\').submit(); return false;"><span>'.$lang['s_search2'].'</span></a>';
		echo '	</div>';
          		
		echo '</form>';
	}
	
	// vrne sql string za search po anketah glede na nastavitve searcha
	private function getSearchString(){
		
        $search_text = mysqli_real_escape_string($GLOBALS['connect_db'], $this->searchString);
        
        // Vse gre v lowerstring
        $search_text = strtolower($search_text);
		
		// Sklanjamo po search besedi    
        $search_text = explode (" ", $search_text);
        
		for ($a=0; $a<sizeof($search_text); $a++) {
            if (strlen ($search_text[$a]) > 5) 
				$search_text[$a] = substr ($search_text[$a], 0, -2);
            elseif (strlen ($search_text[$a]) > 2) 
				$search_text[$a] = substr ($search_text[$a], 0, -1);
            else 
				$search_text[$a] = $search_text[$a];
			
			$this->searchStringProcessed[$a] = $search_text[$a];	
			$search_text[$a] = '%'.$search_text[$a].'%';	
        }
        
		$search_text = implode (" ", $search_text);
		
		// Search po kljucnih besedah znotraj vprasanj (naslovi vprasanj in vrednosti)
		if($this->searchSettings['stype'] == '1')
			$result = " AND (LOWER(sa.introduction) LIKE LOWER('".$search_text."')
								OR LOWER(sa.conclusion) LIKE LOWER('".$search_text."') 
								OR LOWER(ss.naslov) LIKE LOWER('".$search_text."') 
								OR LOWER(sv.naslov) LIKE LOWER('".$search_text."'))";
		// Search po naslovu
		else
			$result = " AND (LOWER(sa.naslov) LIKE LOWER('".$search_text."') OR LOWER(sa.akronim) LIKE LOWER('".$search_text."'))";
		
		return $result;
	}

  
    /**
    * @desc pohendla ajax klice
    */
    function ajax () {

    	if (isset($_POST['tab']))
    		$this->tab = (int)$_POST['tab'];
    	else if (isset($_GET['tab']))
    		$this->tab = (int)$_GET['tab'];
    	if ($this->tab == 'undefined')
    		$this->tab = 0;
    	if (isset($_POST['prva']))
    		$this->prva = $_POST['prva'];
    	else if (isset($_GET['prva']))
    		$this->prva = $_GET['prva'];
    	if ($this->prva == 'undefined')
    		$this->prva = 0;

    	if ($this->tab <= 1)
            $this->tip = 0;
        else
            $this->tip = 1;

    	if ($_GET['a'] == 'display_knjiznica') {
            $this->ajax_display_knjiznica();

        } elseif ($_GET['a'] == 'library_add') {
            $this->ajax_library_add();

        } elseif ($_GET['a'] == 'spr_dropped') {
            $this->ajax_spr_dropped();

        } elseif ($_GET['a'] == 'if_dropped') {
            $this->ajax_if_dropped();

        } elseif ($_GET['a'] == 'folder_dropped') {
            $this->ajax_folder_dropped();

        } elseif ($_GET['a'] == 'folder_rename') {
            $this->ajax_folder_rename();

        } elseif ($_GET['a'] == 'folder_newname') {
            $this->ajax_folder_newname();

        } elseif ($_GET['a'] == 'new_folder') {
            $this->ajax_new_folder();

        } elseif ($_GET['a'] == 'delete_folder') {
            $this->ajax_delete_folder();

        } elseif ($_GET['a'] == 'folder_collapsed') {
            $this->ajax_folder_collapsed();

        } elseif ($_GET['a'] == 'library_del_anketa') {
            $this->ajax_library_del_anketa();

        } elseif ($_GET['a'] == 'library_del_myanketa') {
            $this->ajax_library_del_myanketa();

        } elseif ($_GET['a'] == 'library_add_myanketa') {
        	$this->ajax_library_add_myanketa();

        } elseif ($_GET['a'] == 'anketa_copy') {
            $this->ajax_anketa_copy();
        } elseif ($_GET['a'] == 'anketa_copy_new') {
            $this->ajax_anketa_copy_new();

        } elseif ($_GET['a'] == 'if_remove') {
            $this->ajax_if_remove();

        } elseif ($_GET['a'] == 'brisi_spremenljivko') {
            $this->ajax_brisi_spremenljivko();

        } elseif ($_GET['a'] == 'alert_copy_anketa') {
            $this->ajax_alert_copy_anketa();

        } elseif ($_GET['a'] == 'anketa_archive_and_copy') {
            $this->ajax_anketa_archive_and_copy();

        }
    }

    function ajax_display_knjiznica () {
        $this->display();
    }

    function ajax_library_add () {
        global $lang;

        $data = array();

        if ($this->tip == 0) {
            $spremenljivka = substr($_POST['spremenljivka'], 10);	// odrezemo branching_
            $folder = $_POST['folder'];

            // v knjiznico dodamo spremenljivko
            if ($spremenljivka > 0) {
                $id = $this->Branching->nova_spremenljivka(-1, 0, 0, $spremenljivka);
                sisplet_query("UPDATE srv_spremenljivka SET folder = '$folder' WHERE id = '$id'");

            	$data['response'] = $lang['srv_library_q_added'];

            // v knjiznico dodamo if/blok
            } else {
                $if = substr($_POST['spremenljivka'], 12);			// odrezemo branching_if

                if ($if > 0) {
	                $id = $this->Branching->if_copy(0, $if, true);
	                sisplet_query("UPDATE srv_if SET folder = '$folder' WHERE id = '$id'");

	                $data['response'] = $lang['srv_library_b_added'];
				}
            }
        }

        ob_start();
		$this->display_folders();
		$data['folders'] = ob_get_clean();

		echo json_encode($data);

    }


    function ajax_spr_dropped() {
        global $global_user_id;

        $spremenljivka = $_POST['spremenljivka'];
        $folder = $_POST['folder'];

        if ($this->tab == 0 or $this->tab == 2)
            $uid = 0;
        elseif ($this->tab == 1 or $this->tab == 3)
            $uid = $global_user_id;

        if ($this->tip == 0) {
            sisplet_query("UPDATE srv_spremenljivka SET folder = '$folder' WHERE id = '$spremenljivka'");
        } else {
            sisplet_query("UPDATE srv_library_anketa SET folder = '$folder' WHERE ank_id = '$spremenljivka' AND uid='$uid'");
        }
        //$this->display();
        $this->display_folders();
    }

    function ajax_if_dropped() {
        global $global_user_id;

        $if = $_POST['if'];
        $folder = $_POST['folder'];

        sisplet_query("UPDATE srv_if SET folder = '$folder' WHERE id = '$if'");

        //$this->display();
        $this->display_folders();
    }

    function ajax_folder_dropped() {

    	$drop = $_POST['drop'];
        $folder = $_POST['folder'];

        if ($drop != $folder)
            sisplet_query("UPDATE srv_library_folder SET parent = '$folder' WHERE id = '$drop' AND tip='$this->tip'");

        //$this->display();
        $this->display_folders();
    }

    function ajax_folder_rename () {

       $folder = $_POST['folder'];

       $sql = sisplet_query("SELECT naslov FROM srv_library_folder WHERE id = '$folder'");
       $row = mysqli_fetch_array($sql);

       echo '<form method="post" onsubmit="javascript:library_folder_newname(\''.$folder.'\',\''.$this->tab.'\',\''.$this->prva.'\'); return false;" style="display:inline">';
       echo '<span class="faicon folder icon-blue"></span> '.
            '<input type="text" name="naslov" id="naslov_'.$folder.'" value="'.$row['naslov'].'" onblur="javascript:library_folder_newname(\''.$folder.'\',\''.$this->tab.'\',\''.$this->prva.'\'); return false;" />';
       echo '</form>';

    }

    function ajax_folder_newname () {
        $folder = $_POST['folder'];
        $naslov = $_POST['naslov'];

        sisplet_query("UPDATE srv_library_folder SET naslov='$naslov' WHERE id ='$folder'");

        //$this->display();
        $this->display_folders();
    }

    function ajax_new_folder () {
        global $lang;
        global $global_user_id;
        $folder = $_POST['folder'];

        if ($this->tab == 0 or $this->tab == 2)
            $uid = 0;
        elseif ($this->tab == 1 or $this->tab == 3)
            $uid = $global_user_id;

        $uid = $_POST['uid'];

        $s = sisplet_query("INSERT INTO srv_library_folder (uid, tip, naslov, parent) VALUES ('$uid', '$this->tip', '$lang[srv_newfolder]', '$folder')");
        if (!$s) echo mysqli_error($GLOBALS['connect_db']);
        $insert_id = mysqli_insert_id($GLOBALS['connect_db']);

        $_COOKIE['library_folders'] .= '-'.$insert_id.'-';  // $_COOKIE popravimo, da bo sprememba vidna tudi v display_folders()
        setcookie('library_folders', $_COOKIE['library_folders'], time()+2500000);

		//        $this->display();
		$this->display_folders();

    }

    function ajax_delete_folder () {
        global $lang;

        $folder = $_POST['folder'];

        $sql = sisplet_query("SELECT parent FROM srv_library_folder WHERE id = '$folder'");
        $row = mysqli_fetch_array($sql);

        if ($this->tip == 0) {
            sisplet_query("UPDATE srv_spremenljivka SET folder = '$row[parent]' WHERE folder = '$folder'");
            sisplet_query("UPDATE srv_if SET folder = '$row[parent]' WHERE folder = '$folder'");
        } else {
            sisplet_query("UPDATE srv_library_anketa SET folder = '$row[parent]' WHERE folder = '$folder'");
        }

        sisplet_query("UPDATE srv_library_folder SET parent = '$row[parent]' WHERE parent = '$folder'");

        sisplet_query("DELETE FROM srv_library_folder WHERE id = '$folder'");

        //$this->display();
        $this->display_folders();
    }

    function ajax_folder_collapsed () {
        $folder = $_POST['folder'];
        $collapsed = $_POST['collapsed'];

        $cookie = $_COOKIE['library_folders'];

        if ($collapsed == 0) {
            $cookie .= '-'.$folder.'-';
        } else {
            $cookie = str_replace('-'.$folder.'-', '', $cookie);
        }

        setcookie('library_folders', $cookie, time()+2500000);

        echo '<span class="faicon icon-blue '.($collapsed==1?'plus':'minus').'"></span>';
    }

    function ajax_library_del_anketa () {
        $anketa = $_POST['anketa'];

        sisplet_query("DELETE FROM srv_library_anketa WHERE ank_id='$anketa' AND uid='0'");

        $this->display_folders();
    }

    function ajax_library_del_myanketa () {
        global $global_user_id;

        $anketa = $_POST['anketa'];

        sisplet_query("DELETE FROM srv_library_anketa WHERE ank_id='$anketa' AND uid='$global_user_id'");

        $this->display_folders();
    }

    function ajax_library_add_myanketa () {
    	global $global_user_id;

    	$anketa = $_POST['anketa'];

    	$sql1 = sisplet_query("SELECT id FROM srv_library_folder WHERE uid='$global_user_id' AND tip='1' AND parent='0'");
    	$row1 = mysqli_fetch_array($sql1);

    	sisplet_query("INSERT INTO srv_library_anketa (ank_id, uid, folder) VALUES ('$anketa', '$global_user_id', '$row1[id]')");
    }

    /**
    * skopira anketo cez neko ze obstojeco anketo
    *
    */
    function ajax_anketa_copy () {
    	global $global_user_id;
    	global $lang;
    	global $site_url;
        $anketa = $_POST['anketa'];     // nasa anketa (jo povozimo)
        $ank_id = $_POST['ank_id'];     // anketa, ki jo uporabimo za predlogo

        $hierarhija = (empty($_POST['hierarhija']) ? false : true);

        if($hierarhija && $ank_id == 'privzeta'){

            $ank_id = AppSettings::getInstance()->getSetting('hierarhija-default_id');
        }

        if ($anketa > 0) {

			// preberemo osnovne podatke obstojece ankete (naslov.....)
			$sql = sisplet_query("SELECT naslov, dostop FROM srv_anketa WHERE id = '$anketa'");
	        $row = mysqli_fetch_array($sql);

	        $sql2 = sisplet_query("SELECT naslov FROM srv_anketa WHERE id = '$ank_id'");
	        $row2 = mysqli_fetch_array($sql2);

             $sqls = sisplet_query("SELECT ank_id, uid FROM srv_dostop WHERE ank_id='$anketa'");

            $this->SurveyAdmin->anketa_delete($anketa);

        } else {

            $sql = sisplet_query("SELECT naslov, dostop FROM srv_anketa WHERE id = '$ank_id'");
            $row = mysqli_fetch_array($sql);
			
            $sqls = sisplet_query("SELECT ank_id, uid FROM srv_dostop WHERE ank_id='$ank_id'");
        }

        $naslov = " naslov='".(isset($_POST['naslov']) ? $_POST['naslov'] : $row['naslov'])."',";
        $intro_opomba = " intro_opomba='".(addslashes($lang['srv_library_copy_of_note'].'<a href="'.$site_url.'admin/survey/index.php?anketa='.$ank_id.'">'.$row2['naslov'].'</a>'))."',";


        //$new_id = $this->SurveyAdmin->anketa_copy($ank_id);
        $sas = new SurveyAdminSettings();
		$new_id = $sas->anketa_copy($ank_id);

        // popravimo naslov, opombo, dostop, in novega avtorja
        sisplet_query("UPDATE srv_anketa SET $naslov $intro_opomba dostop='$row[dostop]', insert_uid='$global_user_id', edit_uid='$global_user_id' WHERE id='$new_id'");
		// vsilimo refresh podatkov
		SurveyInfo :: getInstance()->resetSurveyData();

        // dostop uporabimo od stare ankete in ne od skopirane (trnutno ne kopira pravic od prej, če ustvarjamo novo anketo)
        // TODO: po kakšni logiki ohranimo dostop od stare ankete?? Če jo jaz ustvarim je prav, v kolikor kopirma iz knjižnice pa tole ni ok!
        if(!empty($anketa)) {
            sisplet_query("DELETE FROM srv_dostop WHERE ank_id = '$new_id'");
            while ($rows = mysqli_fetch_array($sqls)) {
                sisplet_query("INSERT INTO srv_dostop (ank_id, uid) VALUES ('$new_id', '$rows[uid]')");
            }
        }

        // Vrnemo samo ID ankete
        if($hierarhija) {
            sisplet_query("INSERT INTO srv_anketa_module (ank_id, modul) VALUES ('".$new_id."', 'hierarhija')");
            sisplet_query("INSERT INTO srv_hierarhija_users (user_id, anketa_id, type) VALUES ('".$global_user_id."', '".$new_id."', 1)");

            // Določimo vlogo
            (new \Hierarhija\Hierarhija($new_id))->izrisisSistemskoVprsanjeVloga();

            echo $new_id;
        }else{
            echo 'index.php?anketa='.$new_id.'&a=branching';
        }
    }

    /**
    * ustvari novo kopijo ankete
    * @param ank_id samo za API - prekrije tudi vse echo
    */
    function ajax_anketa_copy_new ($ank_id = null) {
    	global $global_user_id, $lang, $site_url;

        $API_call = false;

        if($ank_id == null) {
            $ank_id = $_POST['ank_id'];     // anketa, ki jo uporabimo za predlogo

            // Če imamo hierarhijo in je privzeta anketa potem preverimo v settings_optional.php
            if(!empty($_POST['hierarhija']) && $ank_id == 'privzeta'){
                $ank_id = AppSettings::getInstance()->getSetting('hierarhija-default_id');
            }
        }
        else {
            $API_call = true;
        }

        $sql = sisplet_query("SELECT naslov, dostop FROM srv_anketa WHERE id = '$ank_id'");
        $row = mysqli_fetch_array($sql);

		// Nastavimo naslov
		if(isset($_POST['naslov']) && $_POST['naslov'] != '' && $_POST['naslov'] != $lang['srv_naslov'] && $_POST['naslov'] != $lang['srv_novaanketa_polnoime'] && trim($_POST['naslov']) != "")
			$naslov = " naslov='".$_POST['naslov']."',";
		else
			$naslov = " naslov='".addslashes($lang['srv_library_copy_of'].$row['naslov'])."',";
        
		// Nastavimo akronim, ce ga imamo
		$akronim = "";
		if(isset($_POST['akronim']) && $_POST['akronim'] != '' && $_POST['akronim'] != $lang['srv_naslov'] && $_POST['akronim'] != $lang['srv_novaanketa_ime_respondenti'] && trim($_POST['akronim']) != "")
			$akronim = " akronim='".$_POST['akronim']."',";
		
		$intro_opomba = " intro_opomba='".(addslashes($lang['srv_library_copy_of_note'].'<a href="'.$site_url.'admin/survey/index.php?anketa='.$ank_id.'">'.$row['naslov'].'</a>'))."',";

        //$new_id = $this->SurveyAdmin->anketa_copy($ank_id);
        $sas = new SurveyAdminSettings();
		$new_id = $sas->anketa_copy($ank_id);

        // popravimo naslov, opombo, dostop, in novega avtorja in ugasnemo email vabila
        sisplet_query("UPDATE srv_anketa 
						SET $naslov $akronim $intro_opomba dostop='$row[dostop]', insert_uid='$global_user_id', edit_uid='$global_user_id', user_base='0'
						WHERE id='$new_id'");

		// vsilimo refresh podatkov
		SurveyInfo :: getInstance()->resetSurveyData();

        // dostop od stare ankete odstranimo
        sisplet_query("DELETE FROM srv_dostop WHERE ank_id = '$new_id'");

        //dostop dodamo uporabniku, ki si kopira anketo
        sisplet_query("INSERT INTO srv_dostop (ank_id, uid) VALUES ('$new_id', '$global_user_id')");

		// Ce imamo pri ustvarjanju doloceno tudi mapo, anketo vstavimo v njo
		if(isset($_POST['folder']) && $_POST['folder'] > 0){
			
			// Razpremo folder v akterega uvrscamo anketo
			$sql = sisplet_query("UPDATE srv_mysurvey_folder SET open='1' WHERE id='".$_POST['folder']."' AND usr_id='".$global_user_id."'");

			// Vstavimo anketo
			$sql = sisplet_query("INSERT INTO srv_mysurvey_anketa (ank_id, usr_id, folder) VALUES ('".$new_id."', '".$global_user_id."', '".$_POST['folder']."')");
		}

    	// popravimo branching, ce kopiramo staro anketo, ki ima lahko pokvarjenega
    	$b = new Branching($new_id);
    	$b->repare_branching();

        // v kolikor je vkloplje modul evalvacija v šolah - hierarhija potem modul vključimo tudi v izbrani anketi
        if(SurveyInfo::checkSurveyModule('hierarhija', $ank_id) || !empty($_POST['novaHierarhjia'])){
            sisplet_query("INSERT INTO srv_anketa_module (ank_id, modul) VALUES ('".$new_id."', 'hierarhija')");
            sisplet_query("INSERT INTO srv_hierarhija_users (user_id, anketa_id, type) VALUES ('".$global_user_id."', '".$new_id."', 1)");


           // Določimo vlogo
            (new \Hierarhija\Hierarhija($new_id))->izrisisSistemskoVprsanjeVloga();

            // Omenjeno funkcijo kopije strukture in preusmeritev uporabimo, kadar kopiramo obstoječe anketo skupaj s strukturo
            if($_POST['hierarhija'] == 1 && empty($_POST['novaHierarhjia'])){
                // $new_id je ID nove ankete, ki je bila skopirana
                // $ank_id pa je ID naše trenutne ankete
                \Hierarhija\HierarhijaKopiranjeClass::getInstance($new_id)->kopirajCelotroStrukturoKNoviAnketi($ank_id);

            }

            if(empty($_POST['novaHierarhjia'])){
                echo 'index.php?anketa=' . $new_id .'&a='.A_HIERARHIJA_SUPERADMIN.'&m='.M_ADMIN_UREDI_SIFRANTE;
            }else{
                echo $new_id;
            }

        }else {
            if(!$API_call)
                echo 'index.php?anketa=' . $new_id;
        }

        //vrrni id nove ankete za API
        return $new_id;
    }

    function ajax_if_remove () {

        $if = $_POST['if'];
        $this->anketa = $_POST['anketa'];

		$BranchingAjax = new BranchingAjax($this->anketa);
		// ne bomo izpisal kar izpisuje ta funkcija
		ob_start();
		$BranchingAjax->ajax_if_remove($if);
        ob_end_clean();

        //$this->display();
        $this->display_folders();
    }

    function ajax_brisi_spremenljivko () {
        $spremenljivka = $_POST['spremenljivka'];

        $this->SurveyAdmin->brisi_spremenljivko($spremenljivka);

        //$this->display();
        $this->display_folders();
    }

    function ajax_alert_copy_anketa () {
		global $lang;
		
		// preverimo stevilo trenutno dodanih vprasanj ce jih ni, ni potrebno arhivirat
		$sql = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='".$_POST['anketa']."'");
    	if (mysqli_num_rows($sql) > 0)
		{
			?>
			<div id="copy_library_alert"><?=$lang['srv_alert_copy_anketa'];?>
			<br/>
			<div class="floatRight spaceRight" title="<?=$lang['srv_alert_copy_anketa_archive'];?>"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="anketa_archive_and_copy('<?=$_POST['anketa'];?>','<?=$_POST['ank_id'];?>'); return false;"><span><?=$lang['srv_alert_copy_anketa_archive'];?></span></a></div></div>
			<div class="floatRight spaceRight" title="<?=$lang['srv_alert_copy_anketa_copy'];?>"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="anketa_copy_no_archive('<?=$_POST['anketa'];?>','<?=$_POST['ank_id'];?>'); return false;"><span><?=$lang['srv_alert_copy_anketa_copy'];?></span></a></div></div>
			<div class="floatRight spaceRight" title="<?=$lang['srv_close_profile'];?>"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="alert_copy_anketa_cancle(); return false;"><span><?=$lang['srv_close_profile'];?></span></a></div></div>
			<div class="clr"></div>
			</div>
			<?php
		} else {
			?>
			<div id="copy_library_alert"><?=$lang['srv_alert_copy_anketa1'];?>
			<br/>
			<div class="floatRight spaceRight" title="<?=$lang['srv_alert_copy_anketa_copy1'];?>"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="anketa_copy_no_archive('<?=$_POST['anketa'];?>','<?=$_POST['ank_id'];?>'); return false;"><span><?=$lang['srv_alert_copy_anketa_copy1'];?></span></a></div></div>
			<div class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray" href="#" onclick="alert_copy_anketa_cancle(); return false;"><span><?=$lang['srv_close_profile'];?></span></a></div></div>
			<div class="clr"></div>
			</div>
			<?php

		}
    }

    function ajax_anketa_archive_and_copy () {
    	global $global_user_id;
    	global $lang;
    	global $site_url;

        $anketa = $_POST['anketa']; // obstoječa anketa
        $ank_id = $_POST['ank_id']; // id ankete iz katere kopiramo

		// preberemo osnovne podatke obstojece ankete (naslov.....)
		$sql = sisplet_query("SELECT naslov, dostop FROM srv_anketa WHERE id = '$anketa'");
        $row = mysqli_fetch_array($sql);

        $sql2 = sisplet_query("SELECT naslov FROM srv_anketa WHERE id = '$ank_id'");
        $row2 = mysqli_fetch_array($sql2);

		$sqls = sisplet_query("SELECT uid FROM srv_dostop WHERE ank_id='$anketa'");

    	// kreiramo novo anketo tako da jo skopiramo iz knjižnice
        //$new_id = $this->SurveyAdmin->anketa_copy($ank_id);
        $sas = new SurveyAdminSettings();
		$new_id = $sas->anketa_copy($ank_id);

        $intro_opomba = addslashes( $lang['srv_library_copy_of_note'].'<a href="'.$site_url.'admin/survey/index.php?anketa='.$ank_id.'">'.$row2['naslov'].'</a>' );

		// popravimo polja
        sisplet_query("UPDATE srv_anketa SET naslov='$row[naslov]', intro_opomba='$intro_opomba', dostop='$row[dostop]', insert_uid='$global_user_id', insert_time=NOW(), edit_uid='$global_user_id', edit_time=NOW() WHERE id='$new_id'");

        // dostop uporabimo od stare ankete in ne od skopirane
        sisplet_query("DELETE FROM srv_dostop WHERE ank_id = '$new_id'");
        while ($rows = mysqli_fetch_array($sqls)) {
            sisplet_query("INSERT INTO srv_dostop (ank_id, uid) VALUES ('$new_id', '$rows[uid]')");
        }

    	// staro anketo razglasimo kot backup(arhiv) nove
		sisplet_query("UPDATE srv_anketa SET backup='$new_id', active=0, edit_uid='$global_user_id', edit_time=NOW(), naslov = CONCAT( naslov, ' ', DAY(NOW()), '.', MONTH(NOW()), '.', YEAR(NOW()) ) WHERE id='$anketa'");
		// vsilimo refresh podatkov
		SurveyInfo :: getInstance()->resetSurveyData();

		// redirektamo na novo anketo
		echo 'index.php?anketa='.$new_id;
    }

	function repareTabs() {
        if ($this->tab >= 2)
            $this->tip = 1;
        else
            $this->tip = 0;

        // popravimo tabe če smo v glasovanju ali formi
        if ($this->mode < 2) {
           	// uredimo tabe
        	if ($this->tab==0 || $this->tab==1) {
        		$this->tip = 1;
        		$this->tab=2;
        	}
        }
	}

}

?>
