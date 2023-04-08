<?php
/**
* @author 	Peter Hrvatin
* @date		November 2013
*
*/

	
class SurveySkupine {
	
	public $anketa;									# id ankete
	public $folder = '';							# pot do folderja
	
	public $uid;									# id userja

	
	/**
	* Konstruktor
	* 
	* @param int $anketa
	*/
	function __construct( $anketa = null ) {
		global $global_user_id, $site_path;
				
		$this->folder = $site_path . EXPORT_FOLDER.'/';
	
		// če je podan anketa ID		
		if ((int)$anketa > 0) { 		

			$this->anketa = $anketa;
		}
		else {
			die("Napaka!");
		}
		
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa)) {
			$this->uid = $global_user_id;
			SurveyUserSetting::getInstance()->Init($this->anketa, $this->uid);
		}
	}
	
	function displayEdit(){
		global $global_user_id;
		global $lang;
		global $site_url;
		
		// Ce je vklopljen evoli team meter se ne sme tukaj urejat skupin (ker se belezijo v dodatno bazo srv_evoli_teammeter z dodatnimi parametri)
        if(SurveyInfo::getInstance()->checkSurveyModule('evoli_teammeter') 
            || SurveyInfo::getInstance()->checkSurveyModule('evoli_quality_climate') 
            || SurveyInfo::getInstance()->checkSurveyModule('evoli_teamship_meter') 
            || SurveyInfo::getInstance()->checkSurveyModule('evoli_organizational_employeeship_meter')
        ){

			echo '<fieldset><legend>'.$lang['srv_skupine'].'</legend>';
			echo '<p class="bold red">Urejanje skupin ni mogoče, ker je vklopljen modul za Evoli!</p>';
			echo '</fieldset>';
			
			return;
		}
        
        $userAccess = UserAccess::getInstance($global_user_id);

		$spr_id = $this->hasSkupine();
		echo '<input type="hidden" id="skupine_spr_id" value="'.$spr_id.'"></input>';
		
		echo '<fieldset><legend>'.$lang['srv_skupine'].'</legend>';
		echo '<div id="skupine">';
		
		echo '<br />'.$lang['srv_skupine_insert'].' '.Help::display('srv_skupine');

		if($spr_id != 0){

			// dodajanje skupin za anketo
			$vrednosti = $this->getVrednosti($spr_id);
			foreach($vrednosti as $vrednost){
				echo '<p>';
				
				echo '<strong>'.$vrednost['naslov'].'</strong>';
				
				$link = $vrednost['url'];
				if(isset($vrednost['nice_url']))
					$link = $vrednost['nice_url'];
				echo ' (<a href="'.$link.'" target="_blank" title="URL skupine '.$vrednost['naslov'].'">'.$link.'</a>)';
				
				echo '<span class="faicon delete_circle icon-orange_link spaceLeft" style="margin-bottom:1px;" onclick="delete_skupina(\'1\', \''.$vrednost['id'].'\');"></span>';
				
				echo '</p>';
			}
        }
        
        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik - ce ni ni gumba za dodajanje skupin
        if(!$userAccess->checkUserAccess($what='skupine')){
            echo '<br />';
            $userAccess->displayNoAccess($what='skupine');
        }
        else{
            echo '<p class="add_skupina_button"><input type="text" name="skupina" autocomplete="off" onKeyUp="add_skupina_enter(\'1\', event);" /> <input type="button" value="'.$lang['add'].'" onclick="add_skupina(\'1\');" /></p>';
        }
		
		echo '</div>';
		echo '</fieldset>';
	}
	
	/* 
	 * Vrne id spremenljivke ce obstaja skupina 
	 * param $skupine -> 1 navadne skupine, 2 -> password skupine
	*/
	function hasSkupine($skupine=1){
		global $global_user_id;
		
		$sql = sisplet_query("SELECT s.id AS id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' AND s.skupine='$skupine'");
		
		if(mysqli_num_rows($sql) > 0){		
			$row = mysqli_fetch_array($sql);
			return $row['id'];
		}
		else		
			return 0;
	}
	
	function getVrednosti($spr_id){
		global $global_user_id;
		global $site_url;
		
		$link = SurveyInfo::getSurveyLink();
		$vrednosti = array();
		
		$sqlS = sisplet_query("SELECT variable FROM srv_spremenljivka WHERE id='$spr_id'");
		$rowS = mysqli_fetch_array($sqlS);
		$variable = $rowS['variable'];
		
		// Preverimo ce imamo lep url
		$sql2 = sisplet_query("SELECT id FROM srv_nice_links WHERE ank_id='$this->anketa'");
		
		$sql = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$spr_id'");
		if(mysqli_num_rows($sql) > 0){

			$cnt = 0;
			while($row = mysqli_fetch_array($sql)){
				$vrednosti[$cnt] = $row;
				$vrednosti[$cnt]['url'] = $link.'?'.$variable.'='.$row['id'];
				
				// Ce imamo nice url za skupine ga tudi shranimo
				if(mysqli_num_rows($sql2) > 0){
					
					$sql3 = sisplet_query("SELECT link fROM srv_nice_links_skupine WHERE ank_id='$this->anketa' AND vre_id='$row[id]'");
					if(mysqli_num_rows($sql3) > 0){
						$row3 = mysqli_fetch_array($sql3);
						$vrednosti[$cnt]['nice_url'] = $site_url.$row3['link'];
					}
				}
				
				$cnt++;
			}
			
			return $vrednosti;
		}
		else		
			return 0;
	}

	// Vrnemo url za doloceno skupino
	function getUrl($spr_id, $vre_id){
		global $global_user_id;
		global $site_url;
		
		$link = SurveyInfo::getSurveyLink();
		
		$sqlS = sisplet_query("SELECT variable FROM srv_spremenljivka WHERE id='$spr_id'");
		$rowS = mysqli_fetch_array($sqlS);
		$variable = $rowS['variable'];
				
		$sql = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$spr_id' AND id='$vre_id'");
		if(mysqli_num_rows($sql) > 0){

			$row = mysqli_fetch_array($sql);
			$url = $link.'?'.$variable.'='.$row['id'];
			
			// Ce imamo nice url za skupine ga tudi shranimo
			$sql2 = sisplet_query("SELECT id FROM srv_nice_links WHERE ank_id='$this->anketa'");
			if(mysqli_num_rows($sql2) > 0){
				
				$sql3 = sisplet_query("SELECT link fROM srv_nice_links_skupine WHERE ank_id='$this->anketa' AND vre_id='$row[id]'");
				if(mysqli_num_rows($sql3) > 0){
					$row3 = mysqli_fetch_array($sql3);
					$url = $site_url.$row3['link'];
				}
			}
			
			return $url;
		}
		else		
			return '';
	}
	
	
	/** Funkcije ki skrbijo za ajax del
	 * 
	 */
	public function ajax() {
		global $global_user_id;
		global $lang;
		global $site_path;
			
		if (isset ($_POST['anketa'])) {
			$anketa = $_POST['anketa'];
			$this->anketa = $_POST['anketa'];
		}
		
		$spr_id = (isset($_POST['spr_id'])) ? $_POST['spr_id'] : 0;	
		
		
		if ($_GET['a'] == 'add_skupina') {
			
			$skupine = (isset($_POST['skupine'])) ? $_POST['skupine'] : 1;
			$variable = ($skupine == 2) ? 'password' : strtolower($lang['srv_skupina']);
            $naslov = ($skupine == 2) ? 'Password' : $lang['srv_skupina'];
            $naslov_vrednost = (isset($_POST['text'])) ? $_POST['text'] : '';

            if($naslov_vrednost != ''){

                // Dodatno preverimo ce sigurno nimamo skupine
                if($spr_id == 0){
                    $spr_id = $this->hasSkupine($skupine);
                }

                // Na zacetku moramo ustvarit najprej vprasanje
                if($spr_id == 0){
                    
                    $sqlG = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$this->anketa' AND vrstni_red='1'");
                    $rowG = mysqli_fetch_array($sqlG);
                    $gru_id = $rowG['id'];
                
                    $b = new Branching($this->anketa);
                    $spr_id = $b->nova_spremenljivka($grupa=$gru_id, $grupa_vrstni_red=1, $vrstni_red=0);
                    
                    $sql = sisplet_query("UPDATE srv_spremenljivka SET naslov='$naslov', variable='$variable', variable_custom='1', skupine='$skupine', sistem='1', visible='0', size='0' WHERE id='$spr_id'");
                    
                    Vprasanje::change_tip($spr_id, 1);
                }
                
                
                $v = new Vprasanje($this->anketa);
                $v->spremenljivka = $spr_id;
                $vre_id = $v->vrednost_new($naslov_vrednost);
                
                
                // Ce gre za password ga dodamo
                if($skupine == 2){
                    $s = sisplet_query("REPLACE INTO srv_password (ank_id, password) VALUES ('$this->anketa', '$naslov_vrednost')");
                    if (!$s) echo mysqli_error($GLOBALS['connect_db']);
                }
                
                
                // Preverimo ce imamo nice URL -> dodamo dodatnega za skupine
                $sql = sisplet_query("SELECT id, link FROM srv_nice_links WHERE ank_id='$this->anketa'");
                if($skupine == 1 && mysqli_num_rows($sql) > 0){
                
                    Common::updateEditStamp();
                
                    $row = mysqli_fetch_array($sql);
                                    
                    $add = false;
                    
                    $anketa = $this->anketa;
                    $nice_url = $row['link'];			
                    
                    $sql2 = sisplet_query("SELECT vrstni_red FROM srv_vrednost WHERE id='$vre_id'");
                    $row2 = mysqli_fetch_array($sql2);
                    $nice_url .= '_'.$row2['vrstni_red'];
                    
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
                    
                    if (strlen($nice_url) < 3) $add = false;
                                    
                    if (SurveyInfo::getInstance()->checkSurveyModule('uporabnost'))
                        $link = 'main/survey/uporabnost.php?anketa='.SurveyInfo::getInstance()->getSurveyHash().'&skupina='.$vre_id;
                    else
                        $link = 'main/survey/index.php?anketa='.SurveyInfo::getInstance()->getSurveyHash().'&skupina='.$vre_id;
                    
                    if ($add) {		
                        $f = @fopen($site_path.'.htaccess', 'a');
                        if ($f !== false) {
                            fwrite($f, "\nRewriteRule ^".$nice_url.'\b(.*)			'.$link."&foo=\$1&%{QUERY_STRING}");
                            fclose($f);
                            
                            $sqlI = sisplet_query("INSERT INTO srv_nice_links_skupine (id,ank_id,nice_link_id,vre_id,link) VALUES ('','$this->anketa','$row[id]','$vre_id','$nice_url')");
                        }
                    }
                }


                // Vrnemo novo geslo, ki ga vstavimo v html
                echo '<p>';

                echo '<strong>'.stripslashes($naslov_vrednost).'</strong>';
                if($skupine == 1){
                    $link = $this->getUrl($spr_id, $vre_id);
                    echo ' (<a href="'.$link.'" target="_blank" title="URL skupine '.stripslashes($naslov_vrednost).'">'.$link.'</a>)';
                }

                echo '<span class="faicon delete_circle icon-orange_link spaceLeft" style="margin-bottom:1px;" onclick="delete_skupina(\''.$skupine.'\', \''.$vre_id.'\');"></span>';		
                
                echo '</p>';
            }
		}
		
		if ($_GET['a'] == 'delete_skupina') {
			
			$skupine = (isset($_POST['skupine'])) ? $_POST['skupine'] : 1;
			$vre_id = (isset($_POST['vre_id'])) ? $_POST['vre_id'] : 0;
			
			$sql2 = sisplet_query("SELECT vrstni_red, naslov FROM srv_vrednost WHERE id='$vre_id'");
			$row2 = mysqli_fetch_array($sql2);
			$index = $row2['vrstni_red'];
			
			if($spr_id > 0){		
				$sql = sisplet_query("DELETE FROM srv_vrednost WHERE id='$vre_id' AND spr_id='$spr_id'");
				
				// Ce smo pobrisali zadnjo vrednost pobrisemo tudi spremenljivko
				$sql2 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$spr_id'");
				if(mysqli_num_rows($sql2) == 0){
					$sql3 = sisplet_query("DELETE FROM srv_spremenljivka WHERE id='$spr_id'");
				}
			}
			
			// Ce gre za password ga zbrisemo
			if($skupine == 2){
				$password = $row2['naslov'];
				if ($password != '') {
					$s = sisplet_query("DELETE FROM srv_password WHERE ank_id='$this->anketa' AND password = '$password'");
					if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				}
			}
			
			// Preverimo ce imamo nice URL -> pobrisemo dodatnega za skupine
			$sql = sisplet_query("SELECT id, link FROM srv_nice_links WHERE ank_id='$this->anketa'");
			if($skupine == 1 && mysqli_num_rows($sql) > 0){
				
				Common::updateEditStamp();
		
				$row = mysqli_fetch_array($sql);
				
				$anketa = $this->anketa;
				$nice_url = $row['link'].'_'.$index;

				$f = fopen($site_path.'.htaccess', 'rb');
				if ($f !== false) {
					$output = array();
					while (!feof($f)) {
						$r = fgets($f);
						if (strpos($r, "^".$nice_url.'\b(.*)	') !== false && strpos($r, "?anketa=".$anketa."&skupina=".$vre_id."") !== false) {
							// kao pobrisemo vrstico in vnos v bazi
							$sqlD = sisplet_query("DELETE FROM srv_nice_links_skupine WHERE ank_id='$anketa' AND nice_link_id='$row[id]' AND vre_id='$vre_id'");
						} 
						else {
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
					}
				}
			}
        }
        

        // Popup za masovno dodajanje gesel
        if ($_GET['a'] == 'show_add_password_mass') {

            // Naslov
            echo '<h2>' . $lang['srv_password_add_mass'] . '</h2>';

            echo '<div class="popup_close"><a href="#" onClick="popupImportAnketaFromText_close();">✕</a></div>';

            echo '<p  class="bold">' . $lang['srv_password_add_mass_instructions'] . '</p>';
            //echo '<span class="italic">' . $lang['srv_password_add_mass_sample'] . '</span></p>';

            echo '<textarea id="add_passwords_mass" name="add_passwords_mass" style="width:99%; height:300px; box-sizing:border-box; padding:5px;"></textarea>';

            echo '<br /><br />';

            echo '<span class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_orange" href="#" onClick="execute_add_passwords_mass();">'.$lang['srv_password_add_mass_execute'].'</a></span>';
            echo '<span class="buttonwrapper floatRight spaceRight"><a class="ovalbutton ovalbutton_gray" href="#" onClick="popupImportAnketaFromText_close();">'.$lang['srv_zapri'].'</a></span>';
        }

        // Masovno dodajanje gesel
        if ($_GET['a'] == 'add_password_mass') {

            $skupine = 2;
			$variable = 'password';
            $naslov = 'Password';
            
            $passwords = (isset($_POST['passwords'])) ? $_POST['passwords'] : '';

            if($passwords != ''){

                // Dodatno preverimo ce sigurno nimamo skupine
                if($spr_id == 0){
                    $spr_id = $this->hasSkupine($skupine);
                }

                // Na zacetku moramo ustvarit najprej vprasanje
                if($spr_id == 0){
                    
                    $sqlG = sisplet_query("SELECT id FROM srv_grupa WHERE ank_id='$this->anketa' AND vrstni_red='1'");
                    $rowG = mysqli_fetch_array($sqlG);
                    $gru_id = $rowG['id'];
                
                    $b = new Branching($this->anketa);
                    $spr_id = $b->nova_spremenljivka($grupa=$gru_id, $grupa_vrstni_red=1, $vrstni_red=0);
                    
                    $sql = sisplet_query("UPDATE srv_spremenljivka SET naslov='$naslov', variable='$variable', variable_custom='1', skupine='$skupine', sistem='1', visible='0', size='0' WHERE id='$spr_id'");
                    
                    Vprasanje::change_tip($spr_id, 1);
                }
                
                
                $v = new Vprasanje($this->anketa);
                $v->spremenljivka = $spr_id;
            

                // Loop cez vsa gesla po vrsticah in jih dodamo
                $passwords_array = explode("\\n", $passwords);
                foreach($passwords_array as $password){

                    // Pocistimo vec presledkov in line breakov
                    $password = trim($password);
                    $password = preg_replace('/\s+/', ' ', $password);

                    if($password != ''){
                        $vre_id = $v->vrednost_new($password);

                        $s = sisplet_query("REPLACE INTO srv_password (ank_id, password) VALUES ('$this->anketa', '$password')");
                        if (!$s) echo mysqli_error($GLOBALS['connect_db']);
                    }
                }
            }
        }
	}
}
?>