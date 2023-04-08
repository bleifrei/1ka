<?php

class BranchingAjax {

    var $anketa;                // trenutna anketa
    var $grupa;                 // trenutna grupa
    var $spremenljivka;         // trenutna spremenljivka
    //var $SurveyAdmin;           // globalna spremenljivka za SurveyAdmin // SurveyAdmin se nikjer vec ne klice iz Branchinga

    //var $sidebar;               // ali prikazemo sidebar: 0-ne, 1-vprasanja, 2-library
    //var $collapsed_content;     // ali prikazujemo vseibno IFa (ce ne se poklice z ajaxom)

    var $skin = 0;
    
    // tele nastavitve so tudi v BranchingAjax in jih je treba tudi tam popravit!
    
    //var $maxIfCount = 0;		// koliko ifov je meja za prikaz. Če je 0 prikeže vse
   	//var $autoRecount = 0;		// ce je vec kot 50 spremenljivk nimamo avtomatskega prestevilcevanja

    /**
    * @desc konstruktor
    */
    function __construct ($anketa=0) {
        global $surveySkin;
		global $site_path;
		
        $this->anketa = $anketa;
        
        if (isset($surveySkin))
            $this->skin = $surveySkin;

        SurveyInfo::getInstance()->SurveyInit($this->anketa);
    }

    /**
    * @desc pohendla ajax zahteve
    */
    function ajax () {

        if (isset($_POST['anketa'])) $this->anketa = $_POST['anketa'];
        if (isset($_POST['spremenljivka'])) $this->spremenljivka = $_POST['spremenljivka'];
		
		if (strpos($_SERVER['HTTP_REFERER'], 'parent_if') !== false) {
			$_GET['parent_if'] = substr( $_SERVER['HTTP_REFERER'], strpos($_SERVER['HTTP_REFERER'], 'parent_if')+10 );
		}
	
		// genericna resitev
		$ajax = 'ajax_' . $_GET['a'];
		
		if ( method_exists('BranchingAjax', $ajax) )
			$this->$ajax();
		else
			echo 'method '.$ajax.' does not exist';
    }

    function ajax_follow_up_condition($ank_id = null, $if_id = null, $odg_id = null, $spr_id = null)
    {
        if ($ank_id == null) $ank_id = $_POST['ank_id'];
        if ($if_id == null) $if_id = $_POST['if_id'];
        if ($odg_id == null) $odg_id = $_POST['odg_id'];
        if ($spr_id == null) $spr_id = $_POST['spr_id'];

        $sql = sisplet_query("SELECT naslov FROM srv_vrednost WHERE id = '$odg_id'");
        $naslov = mysqli_fetch_array($sql);
        sisplet_query("UPDATE srv_if SET label='$naslov[0]' WHERE id='$if_id'");

        $sql_id = sisplet_query("SELECT id FROM srv_condition WHERE if_id='$if_id'");
        $id_condition = mysqli_fetch_array($sql_id);

        sisplet_query("UPDATE srv_condition SET spr_id='$spr_id' WHERE id='$id_condition[0]'");
        sisplet_query("INSERT INTO srv_condition_vre (cond_id, vre_id) VALUES ('$id_condition[0]', '$odg_id')");

    }


    function ajax_if_new ($spremenljivka = null, $if = null, $endif = null, $tip = null) {
    	Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	global $lang;

        if ($spremenljivka == null) $spremenljivka = $_POST['spremenljivka'];
        if ($if == null)			$if = $_POST['if'];
        if ($endif == null)			$endif = $_POST['endif'];
        if ($tip == null)			$tip = $_POST['tip'];
		$copy = $_POST['copy'];
        $no_content = $_POST['no_content'];
        
        $include_element = false;
        
        $b = new Branching($this->anketa);
        
        if ($spremenljivka >= 0 || $if >= 0) {

            $sqln = sisplet_query("SELECT MAX(i.number) AS number FROM srv_if i, srv_branching b WHERE b.ank_id='$this->anketa' AND b.element_if=i.id");
            if (!$sqln) echo mysqli_error($GLOBALS['connect_db']);
            
            $rown = mysqli_fetch_array($sqln);
           
            $number = $rown['number'] + 1;
            
            $sql = sisplet_query("INSERT INTO srv_if (id, number, tip) VALUES ('', '$number', '$tip')");
            if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
            
            $if_id = mysqli_insert_id($GLOBALS['connect_db']);

            if ($spremenljivka > 0 || ($if > 0 && $endif == 1)) {
                $sql = sisplet_query("SELECT parent, vrstni_red, element_spr, element_if FROM srv_branching WHERE element_spr = '$spremenljivka' AND element_if='$if'");
                $row = mysqli_fetch_array($sql);
            }
            
            if ($spremenljivka == 0 && $if == 0) {
            	if ($endif == 0) {	// dodajanje na zacetek
	                $row['parent'] = 0;
	                $row['vrstni_red'] = 0;
				} else {	// dodajanje na konec
					$row['parent'] = 0;
					$row['vrstni_red'] = 99999999;
					
					// ce je blok Demografija na zadnjem mestu v anketi, potem pri "dodajanju na konec" dodamo pred demografijo --da je demografija vedno na koncu
					$sqld = sisplet_query("SELECT b.element_if, b.vrstni_red FROM srv_if i, srv_branching b 
													WHERE i.label = '$lang[srv_demografija]' AND i.tip='1' AND b.ank_id='$this->anketa' AND b.element_if=i.id AND parent='0' AND b.vrstni_red = (
															SELECT MAX(vrstni_red) FROM srv_branching WHERE ank_id='$this->anketa' AND parent = '0'
														)");
					if (mysqli_num_rows($sqld) > 0) {
						$rowd = mysqli_fetch_array($sqld);
						
						sisplet_query("UPDATE srv_branching SET vrstni_red = vrstni_red+1 WHERE element_if = $rowd[element_if] AND element_spr = '0' AND ank_id='$this->anketa'");
						$row['vrstni_red'] = $rowd['vrstni_red'];	
					}
				}
            }

            if ($if > 0 && $endif != 1) {
                $row['parent'] = $if;
                $row['vrstni_red'] = 0;
            }

            // dodajanje ifa na trenutno spremenljivko
            if ($spremenljivka > 0 && $endif == 1) {
				$next_element = $row;
				$include_element = true;		// v if vkljucimo tudi trenutno spremenljivko
				
			// dodajanje ifa na naslednji element
            } else {
            	$next_element = $b->find_next_element($row['parent'], $row['vrstni_red']);
			}
			
            if ($next_element == null) {	// next_element je prazen na koncu ifa, takrat je tudi nov if prazen
				$next_element['parent'] = $row['parent'];
				$next_element['vrstni_red'] = $row['vrstni_red'] + 1;
				$next_element['element_spr'] = 0;
				$next_element['element_if'] = 0;
            }
            
            $add = true;
            
            
            // dodajamo loop - preverimo da ga ne zelimo vgnezditi v drug loop
	        if ($tip == 2) {
	        	// preverimo, da ga ne dodamo v ze obstojec loop
				if ($b->find_loop_parent($next_element['parent']) > 0)
					$add = false;
				
				// preverimo, da ge ne dodamo direktno pred obstojec loop (ker potem objame obstojec loop in dobimo vgnezdenje)
				if ($next_element['element_if'] > 0)
					if ($b->find_loop_child($next_element['element_if']) > 0)
						$add = false;
	        }
            
            if ($add) {
	            $b->if_new($endif, $next_element['parent'], $if_id, $next_element['vrstni_red'], $next_element['element_spr'], $next_element['element_if'], $copy, $no_content, $include_element);

	            sisplet_query("UPDATE srv_anketa SET branching='1' WHERE id = '$this->anketa'");
			
			} else {
				$b->dropped_alert($lang['srv_loop_no_nesting']);
			}
			
        }

		$this->check_loop();
			
        $b->repare_vrstni_red();
        
		Common::getInstance()->prestevilci($spremenljivka, $all=true);
		
		// Zacasno shranimo zadnji ustvarjen if, da vemo katerega odpreti
		echo '<input type="hidden" id="temp_new_if_id" name="temp_new_if_id" value="'.$if_id.'" />';

        $b->branching_struktura();
        		
        return $if_id;
    }

    function ajax_spremenljivka_new ($spremenljivka = null, $if = null, $endif = null, $copy=null, $tip=null, $podtip=null, $drop=null) {
    	Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();

        if ($spremenljivka == null)	$spremenljivka = $_POST['spremenljivka'];
        if ($if == null) 			$if = $_POST['if'];
        if ($endif == null)			$endif = $_POST['endif'];
		if ($copy == null)			$copy = $_POST['copy'];
		if ($drop == null)			$drop = $_POST['drop'];
		if ($tip == null)			$tip = $_POST['tip'];
		if ($podtip == null)		$podtip = $_POST['podtip'];
		
		if ($tip == 23) {
			$copy = $podtip;
			$tip = 0;
			$podtip = 0;
		}
        elseif ($tip == 26) {
			$podtip = 1;
		}
		
		$b = new Branching($this->anketa);
        $this->spremenljivka_new($spremenljivka, $if, $endif, $copy, $drop);
		
		if ($tip > 0) {
			Vprasanje::change_tip($this->spremenljivka, $tip, $podtip);
		}
		
		if ($podtip > 0) {
			$v = new Vprasanje($this->anketa);
			$v->spremenljivka = $this->spremenljivka;
			
			ob_start();
			
			if ($tip == 6) {	// tabela en odgovor
				$v->ajax_change_diferencial($podtip);
			} 
			elseif ($tip == 21) {	// besedilo*
				if ($podtip == 1) {	// captcha
					$v->set_captcha();
				} elseif ($podtip == 2) {	// email
					$v->set_email();
				} elseif ($podtip == 3) {	// url
					$v->set_url();
				} elseif ($podtip == 4) {	// upload
					$v->set_upload();
				} elseif ($podtip == 5) {	// text box
					$v->set_box();
				} elseif ($podtip == 6) {	// podpis
					$v->set_signature();
				} elseif ($podtip == 7) {	// fotografija
                    $v->set_fotografija();
				}
			} 
			elseif ($tip == 19) {
				if ($podtip == 1) {
					$v->set_datum();		// multigrid datum
				}
			}
			elseif($tip == 26){
				$v->set_map ($podtip);
			}
			elseif($tip == 5 && $podtip == 2){
				$v->set_chat();
			}
			elseif($tip == 7 && $podtip == 2){
				$v->set_slider();
			}
			elseif($tip == 1 && $podtip == 10) {	// GDPR
				$v->set_GDPR();
			}
			
			ob_clean();
		}
		
        $b->repare_vrstni_red();
        Common::getInstance()->prestevilci();
        //$b->branching_struktura();
        Cache::clear_cache();
		
		$data = array();
		
		$data['nova_spremenljivka_id'] = $this->spremenljivka;
				
		ob_start();
		$b = new Branching($this->anketa);
        $b->spremenljivka = $this->spremenljivka;
        $b->branching_struktura();
		
		$branching_struktura_text = ob_get_clean();
		if(!mb_detect_encoding($branching_struktura_text, 'UTF-8', true))
        	$data['branching_struktura'] = utf8_encode($branching_struktura_text);
		else
			$data['branching_struktura'] = $branching_struktura_text;
		
		$this->check_loop();
		
		ob_start();
		$v = new Vprasanje($this->anketa);
		$v->spremenljivka = $this->spremenljivka;
		$v->ajax_vprasanje_fullscreen();
	
		$vprasanje_fullscreen_text = ob_get_clean();
		if(!mb_detect_encoding($vprasanje_fullscreen_text, 'UTF-8', true))
        	$data['vprasanje_fullscreen'] = utf8_encode($vprasanje_fullscreen_text);
		else
			$data['vprasanje_fullscreen'] = $vprasanje_fullscreen_text;
		
		
		echo json_encode($data);
    }
    
    
    /**
    * @desc kreira novo spremeniljvko v branchingu -- doda zapis tudi v srv_branching
    */
    function spremenljivka_new ($spremenljivka, $if=0, $endif=0, $copy=0, $drop=0, $toStart=false) {
        Common::updateEditStamp();
        global $lang;
        
        $b = new Branching($this->anketa);
        
        if ($spremenljivka >= 0 || $if > 0) {

            if ($if > 0) {
                if ($endif != 1)
                    $spr_id = $b->find_first_in_if($if);
                else
                    $spr_id = $b->find_next_spr($b->find_last_in_if($if));
            } elseif ($spremenljivka > 0) {
                $spr_id = $spremenljivka;
            } elseif ($spremenljivka == 0 && $toStart==false) {
                $spr_id = $b->find_first_spr();
            } elseif ($spremenljivka == 0 && $toStart==true) {
                $spr_id = $b->find_last_spr();
            }

            $sqlS = sisplet_query("SELECT gru_id, vrstni_red FROM srv_spremenljivka WHERE id='$spr_id'");
            $rowS = mysqli_fetch_array($sqlS);
			
			// Mogoce ni se nobenega vprasanja v anketi
			if ($rowS['gru_id'] == '0'){
				
				$sqlG = sisplet_query("SELECT id, vrstni_red FROM srv_grupa WHERE ank_id='$this->anketa'");
				$rowG = mysqli_fetch_array($sqlG);
				
				// Dodaten pogoj da nikoli tega ne pustimo, ce je gru_id==0
				if($rowG['id'] == '0')
					die('group id error');
				
				$spr_id = $b->nova_spremenljivka($rowG['id'], $rowG['vrstni_red'], 1, $copy);
			}
			elseif($rowS['gru_id'] < 0){
				die('group id < 0 error');
			}
			else{
				// Povecamo vrstni red vsem kasnejsim vprasanjem v isti grupi
				sisplet_query("UPDATE srv_spremenljivka SET vrstni_red=vrstni_red+1 WHERE gru_id='$rowS[gru_id]' AND vrstni_red>'$rowS[vrstni_red]'");

				$sqlG = sisplet_query("SELECT id, vrstni_red FROM srv_grupa WHERE ank_id='$this->anketa' AND id='$rowS[gru_id]'");
				$rowG = mysqli_fetch_array($sqlG);
				
				$spr_id = $b->nova_spremenljivka($rowS['gru_id'], $rowG['vrstni_red'], $rowS['vrstni_red']+1, $copy);
			}

            
            $this->spremenljivka = $spr_id;
			
			if ( $this->spremenljivka == $spremenljivka ) die('copy error');
			
			// Dodaten pogoj da nikoli ne vstavimo v srv_branching elementa ki ima element_spr=0 in element_if=0 (potem lahko pride do neskoncnega loopa kjer se dodajajo grupe v anketo)
			if ($this->spremenljivka == 0 && $if == 0) die('copy error2');
			
            if ($if > 0) {
				
				if ($endif != 1) {
					
					$sql = sisplet_query("INSERT INTO srv_branching (ank_id, parent, element_spr, element_if, vrstni_red) VALUES ('$this->anketa', '$if', '$spr_id', '0', '0')");

                	$b->repare_branching($if);
					
				} else {
					
					$sqlb = sisplet_query("SELECT parent, vrstni_red FROM srv_branching WHERE element_spr='$spremenljivka' AND element_if='$if'");
                    $rowb = mysqli_fetch_array($sqlb);
                    
                    sisplet_query("UPDATE srv_branching SET vrstni_red=vrstni_red+1 WHERE parent='$rowb[parent]' AND vrstni_red>'$rowb[vrstni_red]' AND ank_id='$this->anketa'");
				
					$rowb['vrstni_red']++;

					$sql = sisplet_query("INSERT INTO srv_branching (ank_id, parent, element_spr, element_if, vrstni_red) VALUES ('$this->anketa', '$rowb[parent]', '$spr_id', '0', '$rowb[vrstni_red]')");

 					$b->repare_branching($rowb['parent']);
 					
				}
            } else {
			
				if ($spremenljivka > 0) {
			
					$sqlb = sisplet_query("SELECT parent, vrstni_red FROM srv_branching WHERE element_spr='$spremenljivka' AND element_if='$if'");
                    $rowb = mysqli_fetch_array($sqlb);

                    sisplet_query("UPDATE srv_branching SET vrstni_red=vrstni_red+1 WHERE parent='$rowb[parent]' AND vrstni_red>'$rowb[vrstni_red]' AND ank_id='$this->anketa'");
				
					$rowb['vrstni_red']++;
				
	            } elseif ($spremenljivka == 0) {
	            	if ($endif == 0) {	// dodajanje na zacetek
						$rowb['parent'] = 0;
	                    $rowb['vrstni_red'] = 0;
					} else {	// dodajanje na konec
						$rowb['parent'] = 0;
						$rowb['vrstni_red'] = 99999999;
						
						// ce je blok Demografija na zadnjem mestu v anketi, potem pri "dodajanju na konec" dodamo pred demografijo --da je demografija vedno na koncu
						$sqld = sisplet_query("SELECT b.element_if, b.vrstni_red FROM srv_if i, srv_branching b 
														WHERE i.label = '$lang[srv_demografija]' AND i.tip='1' AND b.ank_id='$this->anketa' AND b.element_if=i.id AND parent='0' AND b.vrstni_red = (
																SELECT MAX(vrstni_red) FROM srv_branching WHERE ank_id='$this->anketa' AND parent = '0'
															)");
						if (mysqli_num_rows($sqld) > 0) {
							$rowd = mysqli_fetch_array($sqld);
							
							sisplet_query("UPDATE srv_branching SET vrstni_red = vrstni_red+1 WHERE element_if = $rowd[element_if] AND element_spr = '0' AND ank_id='$this->anketa'");
							$rowb['vrstni_red'] = $rowd['vrstni_red'];
							
						}
					}
	            }	
				
				$sql = sisplet_query("INSERT INTO srv_branching (ank_id, parent, element_spr, element_if, vrstni_red) VALUES ('$this->anketa', '$rowb[parent]', '$spr_id', '0', '$rowb[vrstni_red]')");

                $b->repare_branching($rowb['parent']);
                
                // ++ nastavljanje pagebreakov //
                if ($drop == 2) {
                	$s = sisplet_query("SELECT pagebreak FROM srv_branching WHERE element_spr='$spremenljivka' AND ank_id='$this->anketa'");
                	$r = mysqli_fetch_array($s);
                	// preverimo se, ce imamo res pagebreak
                	if ($r['pagebreak'] == 1) {
                		$s = sisplet_query("UPDATE srv_branching SET pagebreak='1' WHERE element_spr='$this->spremenljivka' AND ank_id='$this->anketa'");
						if (!$s) echo mysqli_error($GLOBALS['connect_db']);
						$s = sisplet_query("UPDATE srv_branching SET pagebreak='0' WHERE element_spr='$spremenljivka' AND ank_id='$this->anketa'");
						if (!$s) echo mysqli_error($GLOBALS['connect_db']);
						
						Cache::clear_branching_cache();	// drugace se polje pagebreak zakesira pri prikazu
					}
				}
				// -- nastavljanje pagebreakov //
                
            }
            
        }
        //return id nove spremenljivke za API
        return $spr_id;
    }

    /**
     * Uporablja se tudi v API, ampak zaenkrat samo za mobile app, zato sem ignoriral vse alerte in droppanje ifa
     * V kolikor se bo to uporabljalo tudi v API za sirso uporabo, je funkcijo potrebno ustrezno prilagoditi
     */
    function ajax_accept_droppable ($child = 0, $vrstni_red = 0, $page_break = 0, $API_call = false, $parent = 0) {
    	global $lang;
		
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();	
		
        if(!$API_call){
            $child = $_POST['child'];
            $parent = $_POST['parent'];

            $tmp_p = str_replace('droppable_', '', $parent);
            $tmp_p = explode('-', $tmp_p);
            $parent = $tmp_p[0];
            $vrstni_red = (int)$tmp_p[1];
            $page_break = (int)$tmp_p[2];
            $child = str_replace('branching_', '', $child);
        }

        $b = new Branching($this->anketa);
        
        // droppal smo spremenljivko
        if (is_numeric($parent) && $child > 0) {
            if ($b->check_dropped_spremenljivka($child, $parent, $vrstni_red)) {

                $sql = sisplet_query("SELECT pagebreak FROM srv_branching WHERE element_spr = '$child'");
                $row = mysqli_fetch_array($sql);	

				$prev = $b->find_prev_spr($child);	// preden zacnemo premikati, poiscemo predhodno spremenljivko, ki jo rabimo pri PB
				
				$s = sisplet_query("UPDATE srv_branching SET vrstni_red = (vrstni_red+1) WHERE parent='$parent' AND vrstni_red > '$vrstni_red' AND ank_id='$this->anketa'");
				if (!$s && !$API_call) echo mysqli_error($GLOBALS['connect_db']);
                                else if (!$s && $API_call) return mysqli_error($GLOBALS['connect_db']);
				
                $sql = sisplet_query("UPDATE srv_branching SET parent='$parent', vrstni_red=('$vrstni_red'+1) WHERE element_spr='$child' AND ank_id='$this->anketa'");
                if (!$sql && !$API_call) echo mysqli_error($GLOBALS['connect_db']);
                else if (!$sql && $API_call) return mysqli_error($GLOBALS['connect_db']);
                
                
                // ++ nastavljanje pagebreakov //
                
                // spremenljivko smo spustili pred page break, zato zamenjamo pagebreak polja (to oznacuje $page_break == 2)
                if ($page_break == 2) { /*echo '$page_break == 2';*/
                	$s = sisplet_query("SELECT pagebreak FROM srv_branching WHERE parent='$parent' AND vrstni_red='$vrstni_red' AND ank_id='$this->anketa'");
                	$r = mysqli_fetch_array($s);

                	// preverimo se, ce imamo res pagebreak - npr. ce spustimo cisto na konec, ga ni
                	if ($r['pagebreak'] == 1) { /*echo '$r[pb] == 1';*/
                		$s = sisplet_query("UPDATE srv_branching SET pagebreak='1' WHERE element_spr='$child' AND ank_id='$this->anketa'");
						if (!$s && !$API_call) echo mysqli_error($GLOBALS['connect_db']);
                                                else if (!$s && $API_call) return mysqli_error($GLOBALS['connect_db']);
						$s = sisplet_query("UPDATE srv_branching SET pagebreak='0' WHERE parent='$parent' AND vrstni_red='$vrstni_red' AND ank_id='$this->anketa'");
						if (!$s && !$API_call) echo mysqli_error($GLOBALS['connect_db']);
                                                else if (!$s && $API_call) return mysqli_error($GLOBALS['connect_db']);
						
						Cache::clear_branching_cache();	// drugace se polje pagebreak zakesira pri prikazu
					}
				
				// ce je za spremenljivko page break, ga moramo ohraniti tam, kjer je
				} elseif ($row['pagebreak'] == 1) { /*echo '$row[pb] == 1';*/
					
					$s = sisplet_query("SELECT element_spr FROM srv_branching WHERE parent='$parent' AND vrstni_red>='$vrstni_red' AND ank_id='$this->anketa' ORDER BY vrstni_red ASC LIMIT 1");
                	$r = mysqli_fetch_array($s);
                	/*echo ' r: '.$r['element_spr'].' c:'. $child. ' prev:'.$prev.' previous:'. $b->find_prev_spr_branching($child);*/
                	// ce spremenljivko s PB spustimo na isto mesto, ne smemo popravljati PB
                	if ($b->find_prev_spr_branching($child) != $prev /*|| $r['element_spr']==$child*/) {	/*echo ' yes ';*/
						if ($prev == 0 && $page_break != 2) { /*echo '$prev==0';*/	// ce premikamo prvo spremenljivko spustimo nekam za PB, ga zbrisemo (ker na prvem mestu ostane prazna stran)
							$s = sisplet_query("UPDATE srv_branching SET pagebreak='0' WHERE element_spr='$child' AND ank_id='$this->anketa'");
							if (!$s && !$API_call) echo mysqli_error($GLOBALS['connect_db']);
                                                        else if (!$s && $API_call) return mysqli_error($GLOBALS['connect_db']);
						} elseif ($prev > 0) {			/*echo '$prev>0';*/		// normalno - zamenjamo pagebreak-a
							$s = sisplet_query("UPDATE srv_branching SET pagebreak='0' WHERE element_spr='$child' AND ank_id='$this->anketa'");
							if (!$s && !$API_call) echo mysqli_error($GLOBALS['connect_db']);
                                                        else if (!$s && $API_call) return mysqli_error($GLOBALS['connect_db']);
							$s = sisplet_query("UPDATE srv_branching SET pagebreak='1' WHERE element_spr='$prev' AND ank_id='$this->anketa'");
							if (!$s && !$API_call) echo mysqli_error($GLOBALS['connect_db']);
                                                        else if (!$s && $API_call) return mysqli_error($GLOBALS['connect_db']);
						}
					} /*else echo ' no';*/
					
					Cache::clear_branching_cache();	// drugace se polje pagebreak zakesira pri prikazu
					
				}
				
				// na zadnjem mestu vedno popravimo, da ni pagebreaka (ker je nepotreben in lahko kasneje ko se premika kaj pokvari)
				$last = $b->find_last_spr_branching();
				sisplet_query("UPDATE srv_branching SET pagebreak='0' WHERE element_spr='$last' AND ank_id='$this->anketa'");
				
				// -- nastavljanje pagebreakov //
					
				
                //$b->repare_branching($row['parent']);
                //$b->repare_branching($parent);
                $b->repare_branching();

            } else $b->dropped_alert();

        // droppal smo if ali endif
        } else {
			
            $child = str_replace('if', '', $child);

            // droppal smo if
            if (is_numeric($child)) {
                if (is_numeric($parent) && $child > 0 && $child != $parent) {
					
					$loop = false;
					// premikamo loop ali blok/if ki vsebuje loop
					if ($b->find_loop_child($child) > 0) {
						if ($parent > 0) {
							if ($b->find_loop_parent($parent) > 0)
								$loop = true;
						}
					}
					
                    if ($b->check_dropped_if($child, $parent, $vrstni_red) && !$loop) {

                        sisplet_query("UPDATE srv_branching SET vrstni_red = (vrstni_red+1) WHERE parent='$parent' AND vrstni_red > '$vrstni_red' AND ank_id='$this->anketa'");

                        $sql = sisplet_query("UPDATE srv_branching SET parent='$parent', vrstni_red=('$vrstni_red'+1) WHERE element_if='$child' AND ank_id='$this->anketa'");
                        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
						
						// ++ nastavljanje pagebreakov //
						
						// ce if premaknemo na zgornjo stran pagebreaka, potem PB premaknemo na zadnjo spremenljivko v IFu
						if ($page_break == 2) {
							$s = sisplet_query("SELECT pagebreak FROM srv_branching WHERE parent='$parent' AND vrstni_red='$vrstni_red' AND ank_id='$this->anketa'");
                        	$r = mysqli_fetch_array($s);
                        	if ($r['pagebreak'] == 1) {
								$s = sisplet_query("UPDATE srv_branching SET pagebreak='0' WHERE parent='$parent' AND vrstni_red='$vrstni_red' AND ank_id='$this->anketa'");
								$s = sisplet_query("UPDATE srv_branching SET pagebreak='1' WHERE element_spr='{$b->find_last_spr_branching($child)}' AND ank_id='$this->anketa'");
                        		Cache::clear_branching_cache();	// drugace se polje pagebreak zakesira pri prikazu
                        	}
						}
						
						// -- nastavljanje pagebreakov //
						
                        $b->repare_branching();

                    } else {
						if ($loop)
							$b->dropped_alert($lang['srv_loop_no_nesting']);
						else
							$b->dropped_alert();	
                    }
                }

            // droppal smo endif
            } else {

                $child = str_replace('end', '', $child);

                if (is_numeric($parent) && is_numeric($child)) {

                    $sql = sisplet_query("SELECT parent, vrstni_red FROM srv_branching WHERE element_if = '$child'");
                    $row = mysqli_fetch_array($sql);

                    $sql1 = sisplet_query("SELECT * FROM srv_branching WHERE parent='$child'");
                    $elements = mysqli_num_rows($sql1);

                    // podaljsamo IF
                    if ($row['parent'] == $parent && $row['vrstni_red'] <= $vrstni_red) {

                    	$loop = false;
                    	// preverjanje, ce premikamo ENDLOOP, da ne potegnemo cez kaksen drug loop
                    	$sqli = sisplet_query("SELECT tip FROM srv_if WHERE id = '$child'");
                    	$rowi = mysqli_fetch_array($sqli);
                    	if ($rowi['tip'] == '2') {
                    		for ($i=$row['vrstni_red']+1; $i<=$vrstni_red; $i++) {
								$sqli = sisplet_query("SELECT element_if FROM srv_branching WHERE parent='$parent' AND vrstni_red='$i' AND ank_id='$this->anketa'");
								$rowi = mysqli_fetch_array($sqli);
								if ($rowi['element_if'] > 0)
									if ($b->find_loop_child($rowi['element_if']) > 0)
										$loop = true;
							}
                    	}
                    	
                    	if (!$loop) {
	                        $vr = $elements+1;

	                        for ($i=$row['vrstni_red']+1; $i<=$vrstni_red; $i++) {
	                            sisplet_query("UPDATE srv_branching SET parent='$child', vrstni_red='$vr' WHERE parent='$parent' AND vrstni_red='$i' AND ank_id='$this->anketa'");
	                            $vr++;
	                        }
						} else $b->dropped_alert($lang['srv_loop_no_nesting']);

                    // krajsamo IF
                    } elseif ($parent == $child) {

                        $vr = $row['vrstni_red']+1;

                        sisplet_query("UPDATE srv_branching SET vrstni_red=(vrstni_red+'$elements'-'$vrstni_red') WHERE
                                    parent='$row[parent]' AND vrstni_red>'$row[vrstni_red]' AND ank_id='$this->anketa'");

                        for ($i=$vrstni_red+1; $i<=$elements; $i++) {
                            sisplet_query("UPDATE srv_branching SET parent='$row[parent]', vrstni_red='$vr' WHERE parent='$child' AND vrstni_red='$i' AND ank_id='$this->anketa'");
                            $vr++;
                        }
                    	
                    } else $b->dropped_alert();

                    $b->repare_branching();
                }
            }
        }

        $this->check_loop();
        $b->repare_vrstni_red();
		
        Common::getInstance()->prestevilci();
		
        if(!$API_call)
            $b->branching_struktura();
    }
	
    function ajax_if_remove ($if=0, $first=1) {
		global $lang;
		
        Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();

        if ($if == 0)
            $if = $_POST['if'];
		
		if ($_POST['all'] == 1) $all = 1; else $all = 0;	// ali brisemo tudi celotno vsebino pogoja/bloka
			
		$b = new Branching($this->anketa);
		
        if ($if > 0) {
			
			// preverimo, ce obstajajo ze podatki za spremenljivko - v tem primeru damo dodaten error
			$confirmed = $_POST['confirmed'];
			if ($all == 1 && $confirmed != '1') {

				$sql = sisplet_query("SELECT count(*) AS count FROM srv_user WHERE ank_id='$this->anketa' AND deleted='0' AND preview='0'");
                $row = mysqli_fetch_array($sql);
                
				if ($row['count'] > 0) {

                    echo '<h2>'.$lang['srv_warning'].'</h2>';
                    echo '<div class="popup_close"><a href="#" onClick="$(\'#dropped_alert\').hide(); $(\'#fade\').fadeOut(); return false;">✕</a></div>';

					echo '<p>'.$lang['if_delete_data'].'</p>';
                    echo '<p>'.$lang['srv_brisiifconfirm_all'].'</p><br />';
                    
                    echo '<span class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_orange" href="#" onclick="if_remove(\''.$if.'\', \'1\'); return false;"><span>'.$lang['srv_if_rem_all'].'</span></a></span>';
                    echo '<span class="buttonwrapper floatRight spaceRight"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#dropped_alert\').hide(); $(\'#fade\').fadeOut(); return false;"><span>'.$lang['srv_analiza_arhiviraj_cancle'].'</span></a></span>';    
					//echo '<p><a href="#" onclick="if_remove(\''.$if.'\', \'1\'); return false;">'.$lang['srv_if_rem_all'].'</a> <a href="#" onclick="$(\'#dropped_alert\').hide(); $(\'#fade\').fadeOut(); return false;">'.$lang['srv_analiza_arhiviraj_cancle'].'</a></p>';
	
					return;
				}
			}		

            $sql = sisplet_query("SELECT * FROM srv_condition WHERE if_id = '$if'");
            while ($row = mysqli_fetch_array($sql))
                sisplet_query("DELETE FROM srv_condition_vre WHERE cond_id='$row[id]'");

            sisplet_query("DELETE FROM srv_condition WHERE if_id = '$if'");
            sisplet_query("DELETE FROM srv_if WHERE id = '$if'");
			
			sisplet_query("DELETE FROM srv_validation WHERE if_id='$if'");

            $sql = sisplet_query("SELECT parent, vrstni_red FROM srv_branching WHERE element_if = '$if'");
            if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
            $row = mysqli_fetch_array($sql);

            if ($all == 0) {
	            $sql1 = sisplet_query("SELECT * FROM srv_branching WHERE parent = '$if'");
	            $childs = mysqli_num_rows($sql1);

	            sisplet_query("UPDATE srv_branching SET vrstni_red=(vrstni_red+'$childs'-1) WHERE parent='$row[parent]' AND vrstni_red > '$row[vrstni_red]' AND ank_id='$this->anketa'");

	            sisplet_query("UPDATE srv_branching SET parent='$row[parent]', vrstni_red=(vrstni_red+'$row[vrstni_red]'-1) WHERE parent='$if' AND ank_id='$this->anketa'");
			
			} else {
				$sa = null;
				$sql1 = sisplet_query("SELECT element_spr, element_if FROM srv_branching WHERE parent = '$if'");
				while ($row1 = mysqli_fetch_array($sql1)) {
					if ($row1['element_spr'] > 0) {
						if ($sa == null) $sa = new SurveyAdmin(-1, $this->anketa);
						$sa->brisi_spremenljivko($row1['element_spr']);
					} else {
						$this->ajax_if_remove($row1['element_if'], 0);
					}
				}
			}
			
			sisplet_query("DELETE FROM srv_branching WHERE element_if = '$if'");

	        $b->repare_branching($row['parent']);
	        $b->repare_vrstni_red();
			
            $sql = sisplet_query("SELECT * FROM srv_branching WHERE ank_id='$this->anketa' AND element_if > 0");
            if (mysqli_num_rows($sql) == 0) {
                sisplet_query("UPDATE srv_anketa SET branching='0' WHERE id='$this->anketa'");
            }
        }
        
        // izpisemo samo pri prvem klicu, pri rekurzivnih pa ne
        if ($first == 1)
        	$b->branching_struktura();
    }
    
    
    function ajax_vrednost_if_remove ($if=0) {
        Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
        if ($if == 0)
            $if = $_POST['if'];
		
        $vrednost = $_POST['vrednost'];
        
        if ($if > 0) {
            
            $sql = sisplet_query("SELECT * FROM srv_condition WHERE if_id = '$if'");
            while ($row = mysqli_fetch_array($sql))
                sisplet_query("DELETE FROM srv_condition_vre WHERE cond_id='$row[id]'");

            sisplet_query("DELETE FROM srv_condition WHERE if_id = '$if'");
            sisplet_query("DELETE FROM srv_if WHERE id = '$if'");
                
            sisplet_query("UPDATE srv_vrednost SET if_id='0' WHERE if_id='$if'");   
        }
    }

    function ajax_if_tip() {
    	Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();

        $if = $_POST['if'];
        $tip = $_POST['tip'];

        sisplet_query("UPDATE srv_if SET tip = '$tip' WHERE id = '$if'");

        $b = new Branching($this->anketa);
        $b->condition_editing($if);
    }

    function ajax_vrednost_condition_editing () {
		Common::updateEditStamp();
		
        $vrednost = $_POST['vrednost'];

        $sql = sisplet_query("SELECT if_id FROM srv_vrednost WHERE id = '$vrednost'");
        $row = mysqli_fetch_array($sql);
        
        if ($row['if_id'] > 0) {
            $if = $row['if_id'];
        } else {
            sisplet_query("INSERT INTO srv_if (id) VALUES ('')");
            $if = mysqli_insert_id($GLOBALS['connect_db']);
            sisplet_query("INSERT INTO srv_condition (id, if_id, vrstni_red) VALUES ('', '$if', '1')");
            sisplet_query("UPDATE srv_vrednost SET if_id='$if' WHERE id = '$vrednost'");
        }

        $b = new Branching($this->anketa);
        $b->condition_editing($if, $vrednost);
    }
    
    function ajax_condition_editing () {

        $if = $_POST['if'];

        if ($if == 0) {
            $sql = sisplet_query("SELECT id FROM srv_if ORDER BY id DESC LIMIT 1");
            $row = mysqli_fetch_array($sql);
            $if = $row['id'];
        }

        $b = new Branching($this->anketa);
        $b->condition_editing($if);
    }
    
    function ajax_data_condition_editing () {

        $if = $_POST['if'];

        if ($if == 0) {
            $sql = sisplet_query("SELECT id FROM srv_if ORDER BY id DESC LIMIT 1");
            $row = mysqli_fetch_array($sql);
            $if = $row['id'];
        }

        $b = new Branching($this->anketa);
        $b->condition_editing($if,-1);
    }

    function ajax_condition_add () {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $if = $_POST['if'];
        $conjunction = $_POST['conjunction'];
        $negation = $_POST['negation'];

        $sql = sisplet_query("SELECT MAX(vrstni_red) AS max FROM srv_condition WHERE if_id = '$if'");
        $row = mysqli_fetch_array($sql);
        $vrstni_red = $row['max'] + 1;

        sisplet_query("INSERT INTO srv_condition (if_id, conjunction, negation, vrstni_red) VALUES ('$if', '$conjunction', '$negation', '$vrstni_red')");

        $b = new Branching($this->anketa);
        $b->condition_editing_inner($if, $_POST['vrednost']);
    }

    function ajax_condition_edit () {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $text = strip_tags($_POST['text']);
        $operator = $_POST['operator'];
        $negation = $_POST['negation'];
        $conjunction = $_POST['conjunction'];
        $vrednost = $_POST['vrednost'];
        $condition = $_POST['condition'];
        $spremenljivka = $_POST['spremenljivka'];
        $ostanek = $_POST['ostanek'];

        //$vrednost = explode(',', $vrednost);

        sisplet_query("DELETE FROM srv_condition_vre WHERE cond_id='$condition'");
        sisplet_query("DELETE FROM srv_condition_grid WHERE cond_id='$condition'");

        // obicna spremenljivka
        if (is_numeric($spremenljivka)) {
            $spremenljivka = $spremenljivka;
            $vre_id = 0;

            if ($vrednost != null)
                foreach ($vrednost AS $val)
                    if ($val > 0 || $val == -1)
                        sisplet_query("INSERT INTO srv_condition_vre (cond_id, vre_id) VALUES ('$condition', '$val')");


        // multigrid
        } elseif ( is_numeric(str_replace('vre_', '', $spremenljivka)) ) {
            $vre_id = str_replace('vre_', '', $spremenljivka);
            $sql2 = sisplet_query("SELECT spr_id FROM srv_vrednost WHERE id = '$vre_id'");
            $row2 = mysqli_fetch_array($sql2);
            $spremenljivka = $row2['spr_id'];

            if ($vrednost != null)
                foreach ($vrednost AS $val) {
                    if ($val > 0 || $val < 0)	// neustrezni so minus
                        sisplet_query("INSERT INTO srv_condition_grid (cond_id, grd_id) VALUES ('$condition', '$val')");
            }
        // tabela besedilo, tabela stevilo
        } elseif (substr($spremenljivka,0,4) == 'grd_') {
			$e = explode('_', $spremenljivka);
			$vre_id = $e[1];
			$grid = $e[2];
            $sql2 = sisplet_query("SELECT spr_id FROM srv_vrednost WHERE id = '$vre_id'");
            $row2 = mysqli_fetch_array($sql2);
            $spremenljivka = $row2['spr_id'];
		// number
		} elseif (substr($spremenljivka,0,4) == 'num_') {
			$e = explode('_', $spremenljivka);
			$spremenljivka = $e[1];
			$grid = $e[2];
            $vre_id = 0;
		}
           
        // calculation
        if ($spremenljivka == -2) {
            $sqlc = sisplet_query("SELECT vre_id FROM srv_condition WHERE id = '$condition'");
            $rowc = mysqli_fetch_array($sqlc);
            $vre_id = $rowc['vre_id'];
        }
        
        sisplet_query("UPDATE srv_condition SET spr_id='$spremenljivka', vre_id='$vre_id', text='$text', conjunction='$conjunction', negation='$negation', operator='$operator', ostanek='$ostanek' WHERE id = '$condition'");

        $sql = sisplet_query("SELECT * FROM srv_condition WHERE id = '$condition'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        $b = new Branching($this->anketa);
        $b->conditions_display($row['if_id'], 1, 1);
    }

    function ajax_bracket_edit () {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $right_bracket = $_POST['right_bracket'];
        $left_bracket = $_POST['left_bracket'];
        $condition = $_POST['condition'];

        sisplet_query("UPDATE srv_condition SET left_bracket='$left_bracket', right_bracket='$right_bracket' WHERE id = '$condition'");

        $sql = sisplet_query("SELECT * FROM srv_condition WHERE id = '$condition'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        $b = new Branching($this->anketa);
        $b->condition_editing($row['if_id'], $_POST['vrednost']);
    }
    
    function ajax_bracket_edit_new () {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $who = $_POST['who'];
        $what = $_POST['what'];
        $left_bracket = $_POST['left_bracket'];
        $condition = $_POST['condition'];

        if ($who == 'left')
        	if ($what == 'plus')
        		$bracket = 'left_bracket=left_bracket+1';
        	else
        		$bracket = 'left_bracket=left_bracket-1';
        else
        	if ($what == 'plus')
        		$bracket = 'right_bracket=right_bracket+1';
        	else
        		$bracket = 'right_bracket=right_bracket-1';
        		
        
        sisplet_query("UPDATE srv_condition SET $bracket WHERE id = '$condition'");

        $sql = sisplet_query("SELECT * FROM srv_condition WHERE id = '$condition'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        $b = new Branching($this->anketa);
        $b->condition_editing_inner($row['if_id'], $_POST['vrednost']);
    }
    
    function ajax_conjunction_edit () {
		if ($_POST['noupdate'] != 1) {
			Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
		$conjunction = $_POST['conjunction'];
		$negation = $_POST['negation'];
		$condition = $_POST['condition'];
		
        sisplet_query("UPDATE srv_condition SET conjunction='$conjunction', negation='$negation' WHERE id = '$condition'");

        $sql = sisplet_query("SELECT * FROM srv_condition WHERE id = '$condition'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        $b = new Branching($this->anketa);
        $b->condition_editing_inner($row['if_id'], $_POST['vrednost']);
    }

    function ajax_fill_value () {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $condition = $_POST['condition'];
        $spremenljivka = $_POST['spremenljivka'];

        // obicna spremenljivka
        if (is_numeric($spremenljivka)) {
            $spremenljivka = $spremenljivka;
            $vrednost = 0;
            $grid = 0;
        // multigrid
        } elseif ( is_numeric( str_replace('vre_', '', $spremenljivka) ) ) {
            $vrednost = str_replace('vre_', '', $spremenljivka);
            $sql2 = sisplet_query("SELECT spr_id FROM srv_vrednost WHERE id = '$vrednost'");
            $row2 = mysqli_fetch_array($sql2);
            $spremenljivka = $row2['spr_id'];
            $grid = 0;
		// tabela besedilo, tabela stevilo
		} elseif (substr($spremenljivka,0,4) == 'grd_') {
			$e = explode('_', $spremenljivka);
			$vrednost = $e[1];
			$grid = $e[2];
            $sql2 = sisplet_query("SELECT spr_id FROM srv_vrednost WHERE id = '$vrednost'");
            $row2 = mysqli_fetch_array($sql2);
            $spremenljivka = $row2['spr_id'];
		// number
		} elseif (substr($spremenljivka,0,4) == 'num_') {
			$e = explode('_', $spremenljivka);
			$spremenljivka = $e[1];
			$grid = $e[2];
            $vrednost = 0;
		}

        sisplet_query("DELETE FROM srv_condition_vre WHERE cond_id='$condition'");
        sisplet_query("DELETE FROM srv_condition_grid WHERE cond_id='$condition'");

        sisplet_query("UPDATE srv_condition SET spr_id='$spremenljivka', vre_id='$vrednost', grd_id='$grid' WHERE id = '$condition'");


        $sql = sisplet_query("SELECT * FROM srv_condition WHERE id = '$condition'");
        $row = mysqli_fetch_array($sql);

        $b = new Branching($this->anketa);
        $b->condition_editing_inner($row['if_id'], $_POST['vrednost'], $_POST['condition']);
    }

    function ajax_fill_ostanek () {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $condition = $_POST['condition'];
        $modul = $_POST['modul'];

        sisplet_query("UPDATE srv_condition SET modul='$modul' WHERE id = '$condition'");

        $b = new Branching($this->anketa);
        $b->fill_ostanek($condition);
    }

    function ajax_edit_label() {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $if = $_POST['if'];
        $label = strip_tags($_POST['label']);

        sisplet_query("UPDATE srv_if SET label='$label' WHERE id = '$if'");
    }
	
	function ajax_edit_panel_status() {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $if = $_POST['if'];
        $panel_status = $_POST['panel_status'];

		// Ce je prazen ga pobrisemo iz baze
		if($panel_status == ''){
			$sqlP = sisplet_query("DELETE FROM srv_panel_if WHERE ank_id='".$this->anketa."' AND if_id='".$if."'");
		}
		else{
			$sqlP = sisplet_query("INSERT INTO srv_panel_if (ank_id, if_id, value) VALUES ('".$this->anketa."', '".$if."', '".$panel_status."') 
									ON DUPLICATE KEY UPDATE value='".$panel_status."'");
		}
    }

    function ajax_condition_remove () {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $condition = $_POST['condition'];

        sisplet_query("DELETE FROM srv_condition WHERE id='$condition'");

        $b = new Branching($this->anketa);
        $b->repare_condition($_POST['if']);
        $b->condition_editing_inner($_POST['if'], $_POST['vrednost']);
    }

    function ajax_calculation_editing () {
    	if ($_POST['noupdate'] != 1) {
        	Common::updateEditStamp();
		}
		
        $condition = $_POST['condition'];
        $vrednost = $_POST['vrednost'];
        
        $sql = sisplet_query("SELECT * FROM srv_condition WHERE id = '$condition'");
        $row = mysqli_fetch_array($sql);
        
        $b = new Branching($this->anketa);
        $calculation = $b->calculation_editing($condition, $vrednost);
        
        if ($row['vre_id'] == 0) {
            $s = sisplet_query("UPDATE srv_condition SET vre_id='$calculation' WHERE id='$condition'");
            if (!$s) echo mysqli_error($GLOBALS['connect_db']);
        }
    }
    
    function ajax_calculation_editing_close () {
        
        $condition = $_POST['condition'];
        $vrednost = $_POST['vrednost'];
        
        $sql = sisplet_query("SELECT if_id FROM srv_condition WHERE id = '$condition'");
        $row = mysqli_fetch_array($sql);
        
        $b = new Branching($this->anketa);
        if ($condition >= 0)
        	$b->condition_editing($row['if_id'], $vrednost);
        else {
        	$row = SurveyInfo::getInstance()->getSurveyRow();
        	if ($row['expanded'] == 1) {
				$b->vprasanje(-$condition);
			} else {
        		$b->spremenljivka_name(-$condition);
			}
		}
    }
    
    function ajax_calculation_save () {
    	if ($_POST['noupdate'] != 1) {
    		Common::updateEditStamp();
		}
		
        $calculation = $_POST['calculation'];
        $expression = $_POST['expression'];
        
        sisplet_query("UPDATE srv_calculation SET expression='$expression' WHERE id = '$calculation'");
    }
    
    function ajax_calculation_add () {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $condition = $_POST['condition'];
        $operator = $_POST['operator'];
        $vrednost = $_POST['vrednost'];
        
        $sql = sisplet_query("SELECT MAX(vrstni_red) AS max FROM srv_calculation WHERE cnd_id = '$condition'");
        $row = mysqli_fetch_array($sql);
        $vrstni_red = $row['max'] + 1;

        $s = sisplet_query("INSERT INTO srv_calculation (id, cnd_id, operator, vrstni_red) VALUES ('', '$condition', '$operator', '$vrstni_red')");
        if (!$s) echo mysqli_error($GLOBALS['connect_db']);
        
        $b = new Branching($this->anketa);
        $b->calculation_editing_inner($condition, $vrednost);
    }
    
    function ajax_calculation_operator_edit () {
		if ($_POST['noupdate'] != 1) {
			Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
		$calculation = $_POST['calculation'];
		$operator = $_POST['operator'];
		
        sisplet_query("UPDATE srv_calculation SET operator='$operator' WHERE id = '$calculation'");

        $sql = sisplet_query("SELECT * FROM srv_calculation WHERE id = '$calculation'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        $b = new Branching($this->anketa);
        $b->calculation_editing_inner($row['cnd_id'], $_POST['vrednost']);
    
    }
    
    function ajax_calculation_edit () {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $spremenljivka = $_POST['spremenljivka'];
        $calculation = $_POST['calculation'];
        $number = $_POST['number'];
        $vrednost = $_POST['vrednost'];
        $grd_id = 0;
        
        // obicna spremenljivka
        if (is_numeric($spremenljivka)) {
            $spr_id = $spremenljivka;
            $vre_id = 0;
            
        // checkbox, multigrid
        } elseif ( strpos($spremenljivka, 'vre_') !== false ) {
            $e = explode('_', $spremenljivka);
			list( , $vre_id, $grd_id) = $e;
            $sql2 = sisplet_query("SELECT spr_id FROM srv_vrednost WHERE id = '$vre_id'");
            $row2 = mysqli_fetch_array($sql2);
            $spr_id = $row2['spr_id'];
        
        // number
		} elseif ( strpos($spremenljivka, 'num_') !== false ) {
        	
        	$e = explode('_', $spremenljivka);
			list( , $spr_id, $grd_id) = $e;
        	$vre_id = 0;
        	
        // multicheckbox, multinumber
        } else {
			$e = explode('_', $spremenljivka);
			list( , $spr_id, $vre_id, $grd_id) = $e;
        }
        
        if (!is_numeric($number)) $number = 0;
        
        $s = sisplet_query("UPDATE srv_calculation SET spr_id='$spr_id', vre_id='$vre_id', grd_id='$grd_id', number='$number' WHERE id = '$calculation'");
        if (!$s) echo mysqli_error($GLOBALS['connect_db']);
        
        $sql = sisplet_query("SELECT * FROM srv_calculation WHERE id = '$calculation'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        $b = new Branching($this->anketa);
        $b->calculation_editing_inner($row['cnd_id'], $vrednost);
    }
    
    function ajax_calculation_remove () {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $calculation = $_POST['calculation'];
        $vrednost = $_POST['vrednost'];

        sisplet_query("DELETE FROM srv_calculation WHERE id='$calculation'");

        $b = new Branching($this->anketa);
        $b->repare_calculation($_POST['condition']);

        $b->calculation_editing_inner($_POST['condition'], $vrednost);
    }
    
    function ajax_calculation_bracket_edit_new () {
    	if ($_POST['noupdate'] != 1) {
    		Common::getInstance()->Init($this->anketa);
    		Common::getInstance()->updateEditStamp();
		}
		
        $who = $_POST['who'];
        $what = $_POST['what'];
        $left_bracket = $_POST['left_bracket'];
        $calculation = $_POST['calculation'];

        if ($who == 'left')
        	if ($what == 'plus')
        		$bracket = 'left_bracket=left_bracket+1';
        	else
        		$bracket = 'left_bracket=left_bracket-1';
        else
        	if ($what == 'plus')
        		$bracket = 'right_bracket=right_bracket+1';
        	else
        		$bracket = 'right_bracket=right_bracket-1';
        		
        
        sisplet_query("UPDATE srv_calculation SET $bracket WHERE id = '$calculation'");

        $sql = sisplet_query("SELECT * FROM srv_calculation WHERE id = '$calculation'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        $b = new Branching($this->anketa);
        $b->calculation_editing_inner($row['cnd_id'], $_POST['vrednost']);
    }
    
    function ajax_condition_editing_close () {

        $if_nova = $_POST['if_nova'];
        $if = $_POST['if'];

        // tega ni vec
        /*if ($if_nova > 0) {
            echo '<script type="text/javascript">
                    spremenljivka_new(\'0\', \''.$if_nova.'\', \'1\');
                  </script>';
        }*/

        $b = new Branching($this->anketa);
        $b->display_if_label($if);
    }
    
    function ajax_vrednost_condition_editing_close () {
        Common::getInstance()->Init($this->anketa);
        Common::getInstance()->updateEditStamp();

        $vrednost = $_POST['vrednost'];
        $if = $_POST['if'];
        $grid = $_POST['grid'];

        $b = new Branching($this->anketa);
        if($grid == 1) {
            echo '<span class="red" style="cursor:pointer" onclick="vrednost_condition_editing(\''.$vrednost.'\'); return false;" title="'.$lang['srv_podif_edit'].'">*</span>';
        }else{
            $b->conditions_display($if, 0, 1);
        }

        //dodamo trikotnik error na koncu
        if ($b->condition_check($if) != 0)
            echo '<span class="faicon warning icon-orange"></span>';
    }

    function ajax_pagebreak ($spr = 0, $force_on = 0) {
    	Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();

        $spremenljivka = $_POST['spremenljivka'];
		if ($spr > 0) $spremenljivka = $spr;
        
        $sql = sisplet_query("SELECT pagebreak FROM srv_branching WHERE element_spr = '$spremenljivka'");
        $row = mysqli_fetch_array($sql);

        if ($row['pagebreak'] == 0 || $force_on == 1)
            $pagebreak = 1;
        else
            $pagebreak = 0;

        sisplet_query("UPDATE srv_branching SET pagebreak = '$pagebreak' WHERE element_spr = '".$spremenljivka."' AND ank_id='".$this->anketa."'");

        $this->check_loop();
        
        $b = new Branching($this->anketa);
        $b->repare_vrstni_red();
        $b->trim_grupe();

        //$this->pagebreak_display($spremenljivka);
        $this->spremenljivka = 0;
        $b->branching_struktura();
    }
    
	function ajax_pagebreak_all () {
    	Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();

        sisplet_query("UPDATE srv_branching SET pagebreak = '1' WHERE element_spr > '0' AND ank_id='".$this->anketa."'");

        $b = new Branching($this->anketa);
        $b->repare_vrstni_red();
        $b->trim_grupe();

        //$this->pagebreak_display($spremenljivka);
        $this->spremenljivka = 0;
        $b->branching_struktura();
    }

    function ajax_vprasanje_edit () {
        $spremenljivka = $_POST['spremenljivka'];

        $this->spremenljivka = $spremenljivka;
        $b = new Branching($this->anketa);
        $b->display_vprasanja();
    }

    function ajax_refresh_left () {
        $b = new Branching($this->anketa);
        $b->spremenljivka = $this->spremenljivka;
        $b->branching_struktura();
    }

    function ajax_refresh_right () {
        $b = new Branching($this->anketa);
        $b->display_vprasanja();
        $b->showVprasalnikBottom();
    }

    function ajax_get_new_spr () {
        $sql = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' ORDER BY s.id DESC LIMIT 1");
        $row = mysqli_fetch_array($sql);
        echo $row['id'];
    }

    function ajax_if_collapsed () {
    	Common::updateEditStamp();
    	
        $if = $_POST['if'];
        $collapsed = $_POST['collapsed'];

        sisplet_query("UPDATE srv_if SET collapsed='$collapsed' WHERE id = '$if'");
    }

    function ajax_if_display_collapsed () {
        $if = $_POST['if'];

        $b = new Branching($this->anketa);
        $b->display_if_content($if);
    }

    function ajax_editmode_introconcl () {
        $id = $_POST['id'];

        $b = new Branching($this->anketa);
        $b->introduction_conclusion($id, 1);
    }

    function ajax_normalmode_introconcl () {
        $id = $_POST['id'];

        $b = new Branching($this->anketa);
        $b->introduction_conclusion($id);
    }

    function ajax_edit_introconcl () {
    	Common::updateEditStamp();
    	
        $id = $_POST['id'];
        $text = $_POST['text'];
        $opomba = strip_tags($_POST['opomba']);

        if ($id == -1) {
            sisplet_query("UPDATE srv_anketa SET introduction='$text', intro_opomba='$opomba' WHERE id='$this->anketa'");
        } 
		elseif ($id == -2) {
            sisplet_query("UPDATE srv_anketa SET conclusion='$text', concl_opomba='$opomba' WHERE id='$this->anketa'");
        }
		else{
			sisplet_query("UPDATE srv_anketa SET statistics='$text' WHERE id='$this->anketa'");
		}
    }

    function ajax_introconcl_visible () {
    	Common::updateEditStamp();
    	
        $id = $_POST['id'];

		$row = SurveyInfo::getInstance()->getSurveyRow();
		
        if ($id == -1) {
            $name = 'show_intro';
        } else {
            $name = 'show_concl';
        }

        $show = $row[$name];

        if ($show == 1)
            $newshow = 0;
        else
            $newshow = 1;

        sisplet_query("UPDATE srv_anketa SET $name = '$newshow' WHERE id ='$this->anketa'");
		SurveyInfo :: getInstance()->resetSurveyData();		

        $b = new Branching($this->anketa);
        $b->introduction_conclusion($id);
    }

    function ajax_concl_settings () {
    	Common::updateEditStamp();
    	
        $text = $_POST['text'];
        $url = $_POST['url'];
        if ($_POST['concl_link'] == 'true')
            $concl_link = 0;
        else
            $concl_link = 1;

        if ($_POST['concl_back_button'] == 'true')
            $concl_back_button = 1;
        else
            $concl_back_button = 0;

        sisplet_query("UPDATE srv_anketa SET text='$text', url='$url', concl_link='$concl_link', concl_back_button='$concl_back_button' WHERE id = '$this->anketa'");

    }
	
	function ajax_scale_ordnom () {
    	Common::updateEditStamp();
    	
        $spremenljivka = $_POST['spremenljivka'];
		$value = $_POST['value'];

        sisplet_query("UPDATE srv_spremenljivka SET skala='$value' WHERE id='$spremenljivka'");
	
        $b = new Branching($this->anketa);
        $b->vprasanje($spremenljivka);
	}

    function ajax_expand () {
        $mode = $_POST['mode'];

        $b = new Branching($this->anketa);
        $b->display_vprasanja($mode);
    }

    function ajax_refresh_spremenljivka_name () {
        $spremenljivka = $_POST['spremenljivka'];

        $b = new Branching($this->anketa);
        $b->spremenljivka_name($spremenljivka);
    }

    function ajax_prestevilci () {
        global $site_url;
        
        Common::updateEditStamp();
        
        Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->prestevilci(0, true);
        
        echo $site_url.'admin/survey/index.php?anketa='.$this->anketa.'&a=branching';

    }

    function ajax_dodaj_blok_interpretacije () {
		global $site_url;
		global $lang;
		
		Common::updateEditStamp();
		
		// blok
		$this->ajax_if_new(0, 0, 0, 1);
		
		$sql = sisplet_query("SELECT element_if FROM srv_branching WHERE ank_id='$this->anketa' AND parent='0' AND element_if>0 ORDER BY vrstni_red DESC LIMIT 1");
		$row = mysqli_fetch_array($sql);
		$if_id = $row['element_if'];
		
		// bloku nastavimo labelo
		sisplet_query("UPDATE srv_if SET label = '".$lang['srv_zakljucek_vprasalnika']."' WHERE id = '$if_id'");
		
		// spremenljivka compute
		$this->ajax_spremenljivka_new(0, $if_id, 1);
		
		// spremenljivko nastavimo tip na compute
		sisplet_query("UPDATE srv_spremenljivka SET tip='22', size='1', naslov='$lang[srv_vprasanje_tip_22]', variable='sum', variable_custom='1' WHERE id = '$this->spremenljivka'");
		
		// nastavimo page break
		sisplet_query("UPDATE srv_branching SET pagebreak = '1' WHERE element_spr = '".$this->spremenljivka."' AND ank_id='".$this->anketa."'");

        $b = new Branching($this->anketa);
        $b->repare_vrstni_red();
        $b->trim_grupe();
		
		// spremenljivka label
		$this->ajax_spremenljivka_new(0, $if_id, 1);
		
		// spremenljivko nastavimo tip label
		sisplet_query("UPDATE srv_spremenljivka SET tip='5', naslov='Rezultat: #sum#' WHERE id = '$this->spremenljivka'");
		
    }
    
    function ajax_check_pogoji() {
		global $lang;
		
		$b = new Branching($this->anketa);
		$code = $b->check_pogoji();
		
		// ce je vse ok, preverimo se loope
		if ($code === true)
			$code = $b->check_loops();
		
		// ce je vse ok, preverimo se validacije
		if ($code === true)
			$code = $b->check_validation();
		
		// ce je vse ok, preverimo se imena variabel (vprasanj in variabel znotraj vprasanj)
		if ($code === true)
			$code = $b->check_variable();
			
		// pogoji so ok
		if ($code === true) {
			if ($_GET['izpis'] == 'long') {
				echo '<p style="text-align: center"><b>'.$lang['srv_check_pogoji_ok'].'!</b></p>';
			
				?>
				<script>
				$(function () {
                    $('#check_pogoji').animate({opacity: 1.0}, 3000).fadeOut('slow');
                    $('#fade').fadeOut("slow");
				})
				</script>
				<?php
			} else {
				echo '1';
			}
		// pogoji niso ok
		} else {
			
			if ($_GET['izpis'] == 'long') {
				
				if ($code['type'] == 'if' || $code['type'] == 'podif')
					echo '<h2>'.$lang['srv_check_pogoji_not_ok'].'</h2>';
				elseif ($code['type'] == 'loop')
					echo '<h2>'.$lang['srv_loop_no_nesting'].'</h2>';
				elseif ($code['type'] == 'question_variable')
					echo '<h2>'.$lang['srv_duplicate_question_variable'].'</h2>';
				elseif ($code['type'] == 'variable')
					echo '<h2>'.$lang['srv_duplicate_variables'].'</h2>';
                
                echo '<div class="popup_close"><a href="#" onClick="$(\'#check_pogoji\').fadeOut(\'slow\'); $(\'#fade\').fadeOut(\'slow\');">✕</a></div>';        
				
				// napaka v ifih
				if ($code['type'] == 'if') {
					$sql = sisplet_query("SELECT id, number FROM srv_if WHERE id = '$code[id]'");
					$row = mysqli_fetch_array($sql);
					echo '<p>'.$lang['srv_check_pogoji_if'].' <a href="javascript:condition_editing(\''.$row['id'].'\');"><b>'.$row['number'].'</b></a></p>';
				
				// napaka v podifih, ki so nastavljeni na vrednosti spremenljivk
				} elseif ($code['type'] == 'podif') {
					$sql = sisplet_query("SELECT id, naslov, variable FROM srv_spremenljivka WHERE id = '$code[id]'");
					$row = mysqli_fetch_array($sql);
					echo '<p>'.$lang['srv_check_pogoji_spremenljivka'].': <b><a href="javascript:vprasanje_fullscreen(\''.$row['id'].'\');">'.$row['variable'].' - '.strip_tags($row['naslov']).'</a></b></p>';
				
				// napaka z gnezdenjem loopov
				} elseif ($code['type'] == 'loop') {
					$sql = sisplet_query("SELECT id, number FROM srv_if WHERE id = '$code[id]'");
					$row = mysqli_fetch_array($sql);
					echo '<p>'.$lang['srv_check_pogoji_loop'].' <a href="javascript:condition_editing(\''.$row['id'].'\');"><b>'.$row['number'].'</b></a></p>';
				
				} elseif ($code['type'] == 'validation') {
					$sql = sisplet_query("SELECT id, naslov, variable FROM srv_spremenljivka WHERE id = '$code[id]'");
					$row = mysqli_fetch_array($sql);
					echo '<p>'.$lang['srv_check_validacija'].': <b><a href="javascript:vprasanje_fullscreen(\''.$row['id'].'\');">'.$row['variable'].' - '.strip_tags($row['naslov']).'</a></b></p>';
				
				// napaka z imeni variabel
				} elseif ($code['type'] == 'variable') {
					foreach ($code['vars'] AS $var) {
						echo '<strong>'.$var.'</strong><br />';
					};
				} 
				
				// error code
				echo '<p>';
				if ($code['code'] == 1) {
					echo '<img src="img_'.$this->skin.'/error.png" alt="" /> '.$lang['srv_error_oklepaji'].'';
				} elseif ($code['code'] == 2) {
					echo '<img src="img_'.$this->skin.'/error.png" alt="" /> '.$lang['srv_error_spremenljivka'].'';
				} elseif ($code['code'] == 3) {
					echo '<img src="img_'.$this->skin.'/error.png" alt="" /> '.$lang['srv_error_vrednost'].'';
				} elseif ($code['code'] == 4) {
					echo '<img src="img_'.$this->skin.'/error.png" alt="" /> '.$lang['srv_error_numericno'].'';
				} elseif ($code['code'] == 5) {
					echo '<img src="img_'.$this->skin.'/error.png" alt="" /> '.$lang['srv_error_calculation'].'';
				} elseif ($code['code'] == 6) {
					echo '<img src="img_'.$this->skin.'/error.png" alt="" /> '.$lang['srv_error_loop'].'';
				}
				
				echo '</p>';
			
				//echo '<p class="as_link" onclick="$(\'#check_pogoji\').fadeOut(\'slow\'); $(\'#fade\').fadeOut(\'slow\');">'.$lang['srv_zapri'].'</p>';
                echo '<span class="floatRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="$(\'#check_pogoji\').fadeOut(\'slow\'); $(\'#fade\').fadeOut(\'slow\');">'.$lang['srv_zapri'].'</a></div></span>';
			} 
			else {
                echo '<h2>'.$lang['srv_check_pogoji_not_ok'].'</h2>';
                echo '<div class="popup_close"><a href="#" onClick="$(\'#surveyTrajanje\').fadeOut(\'slow\'); $(\'#fade\').fadeOut(\'slow\');">✕</a></div>';        

				echo '<p>'.$lang['srv_check_pogoji_not_ok_txt'].'</p>';
				
				//echo '<p class="as_link" onclick="$(\'#surveyTrajanje\').fadeOut(\'slow\'); $(\'#fade\').fadeOut(\'slow\');">'.$lang['srv_zapri'].'</p>';
                echo '<span class="floatRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange" href="#" onclick="$(\'#surveyTrajanje\').fadeOut(\'slow\'); $(\'#fade\').fadeOut(\'slow\');">'.$lang['srv_zapri'].'</a></div></span>';
            }		
		}
    }

    /**
    * alert, da se naj zapira bloke (shranimo da se ne prikazuje vec)
    * 
    */
    function ajax_alert_close_block () {
		global $lang;
		
		SurveySetting::getInstance()->Init($this->anketa);

		$show_alert = SurveySetting::getInstance()->getSurveyMiscSetting('alert_close_block');	
		if($show_alert != '2'){
		
			echo $lang['alert_close_block'];
				
			echo '<span class="buttonwrapper floatRight">';
            echo '<a class="ovalbutton ovalbutton_grey" href="#" onclick="alert_close_block(); return false;"><span>'.$lang['srv_zapri'].'</span></a>';
            echo '</span>';
		
			SurveySetting::getInstance()->setSurveyMiscSetting('alert_close_block', '2');
		}		
		else{
			return false;
		}
    }
    
    /**
    * spremeni nastavitve toolboxa ali nacina prikaza ankete
    * 
    */
    function ajax_change_mode () {
		Common::updateEditStamp();
		
		$what = $_REQUEST['what'];
		$value = $_REQUEST['value'];
		
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		if ($what == 'expanded') {
			$s = sisplet_query("UPDATE srv_anketa SET expanded = '$value' WHERE id = '$this->anketa'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		} elseif ($what == 'flat') {
			$s = sisplet_query("UPDATE srv_anketa SET flat = '$value' WHERE id = '$this->anketa'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		} elseif ($what == 'toolbox') {
			
			if ($value == 1) {	// basic nacin
				$s = sisplet_query("UPDATE srv_anketa SET toolbox = '$value', expanded = '1', flat = '1' WHERE id = '$this->anketa'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			} 
			elseif ($value == 2) {	// advanced nacin
				$s = sisplet_query("UPDATE srv_anketa SET toolbox = '$value', expanded = '0', flat = '0' WHERE id = '$this->anketa'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
			elseif ($value == 3 || $value == 4) {	// knjiznica (3 in 4 da se ve za nazaj na 1 ali 2)
				$s = sisplet_query("UPDATE srv_anketa SET toolbox = '$value' WHERE id = '$this->anketa'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
			//else die();
			
		} elseif ($what == 'toolboxback') {		// iz knjiznice nazaj v obicno urejanje
			$s = sisplet_query("UPDATE srv_anketa SET toolbox = '$value' WHERE id = '$this->anketa'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			
		} elseif ($what == 'popup') {
			$s = sisplet_query("UPDATE srv_anketa SET popup = '$value' WHERE id = '$this->anketa'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		} elseif ($what == 'form_settings_obvescanje'){
			SurveySetting::getInstance()->setSurveyMiscSetting('srvtoolbox_form_alert', $value);
			
		} elseif ($what == 'form_settings_vabila'){			
			SurveySetting::getInstance()->setSurveyMiscSetting('srvtoolbox_form_email', $value);
			
		}
		
		SurveyInfo::getInstance()->resetSurveyData();	
    }
	
	/**
    * spreminjanje hitrih nastavitev
    * 
    */
    function ajax_edit_quick_settings () {		
		Common::updateEditStamp();
		
		$what = $_POST['what'];
		$results = $_POST['results'];
		
		$status1 = $_POST['status1'];
		$status2 = $_POST['status2'];
		
		if($what == 'finish_author' || $what == 'finish_respondent_cms' || $what == 'finish_respondent' || $what == 'finish_other' | $what == 'finish_other_emails') {
			sisplet_query("INSERT INTO srv_alert (ank_id, $what) VALUES ('$this->anketa', '$results')
			ON DUPLICATE KEY UPDATE $what = '$results' ");
		}
		else{
			sisplet_query("UPDATE srv_anketa SET $what = '$results' WHERE id = '$this->anketa'");
		}
		
		$b = new Branching($this->anketa);
		$b->toolbox_settings($status1, $status2);
    }
    
    
    /**
	* @desc funkcije, ki pohendla komentarje ankete in vprasanj (kreira novo temo, ce se ni itd...)
	* 
	*/
	function ajax_comment_manage ($t = false, $s = false, $v = false, $o = false) {
		global $site_path;
		global $site_url;
		global $lang;
		global $global_user_id;
		global $admin_type;
		
		/**
		* $type :	0 - komentar na anketo
		* 			1 - komentar na vprasanje
		* 			2 - komentar respondentov na vprasanje
		*			4 - komentar respondentov na anketo
		*			5 - komentar na if
		*			6 - komentar na blok
		*
		* $view :	0 - izpise se samo field za dodajanje komentarja (komentarjev se ne da brati)
		* 			1 - izpisejo se tudi ze vneseni komentarji
		* 			3 - izpis komentarjev v urejanju vprasanja tabu 'Komentarji'
		* 			4 - izpis komentarjev v zgornjem zavihku Komentarji
		* 			5 - izpis komentarjev v zgornjem zavihku Komentarji - za respondente
		*/

		$spremenljivka = !$s ? $_REQUEST['spremenljivka'] : $s;
		$type = !$t ? $_REQUEST['type'] : $t;
		$view = $_REQUEST['view'];

		SurveySetting::getInstance()->Init($this->anketa);
		$sortpostorder = SurveySetting::getInstance()->getSurveyMiscSetting('sortpostorder');
		$addfieldposition = SurveySetting::getInstance()->getSurveyMiscSetting('addfieldposition');
		$survey_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_viewadminonly');
		$survey_comment_viewauthor = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_viewauthor');
		$question_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewadminonly');
		$question_comment_viewauthor = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewauthor');
		$commentmarks = SurveySetting::getInstance()->getSurveyMiscSetting('commentmarks');
		$commentmarks_who = SurveySetting::getInstance()->getSurveyMiscSetting('commentmarks_who');
		$comment_history = SurveySetting::getInstance()->getSurveyMiscSetting('comment_history');
		$survey_comment_viewadminonly_resp = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_viewadminonly_resp');
		$survey_comment_viewauthor_resp = SurveySetting::getInstance()->getSurveyMiscSetting('survey_comment_viewauthor_resp');
		//$question_resp_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_viewadminonly');


		$f = new Forum;

		$rowi = SurveyInfo::getInstance()->getSurveyRow();
		
		// vprasanje (1) in en oblacek za celo anketo (0)
		if ($type == 1 or $type == 0) {            

			// okvir, da se lahko refresha pri oddaji novega komentarja
			if ($_REQUEST['refresh'] != '1')
				echo '<div id="survey_comment_'.$spremenljivka.'_'.$view.'">';

			$vsebina = !$v ? $_REQUEST['vsebina'] : $v;

			if ($spremenljivka > 0) {   // komentar na spremenljivko
				$rows = Cache::srv_spremenljivka($spremenljivka);
				if ($rows['thread'] > 0)
					$tid = $rows['thread'];
			} elseif ($spremenljivka == -1) { // komentar na uvod
				$tid = $rowi['thread_intro'];
				$rows['naslov'] = $lang['srv_intro_label'];
			} elseif ($spremenljivka == -2) { // komentar na zakljucek
				$tid = $rowi['thread_concl'];
				$rows['naslov'] = $lang['srv_end_label'];
			} else {                    // komeentar na anketo
				$tid = $rowi['thread'];
				$rows['naslov'] = $rowi['naslov'];
			}

			// poslali smo vsebino, ki jo shranimo v forum
			if ($vsebina != '') {

				Common::updateEditStamp();
				
				if ($rowi['forum'] == 0) {
					$rowi['forum'] = $this->comment_create_forum();
				}

				$f->currentForum = $rowi['forum'];

				if ($tid == 0) {
					if ($type == 1)
						$vsebina_post = $lang['srv_forum_intro'].strip_tags($rows['naslov']).'<br /><br /><a href="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'">'.$lang['srv_forum_back'].'</a>';
					else
						$vsebina_post = $lang['srv_forum_srv_intro'].strip_tags($rows['naslov']).'<br /><br /><a href="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'">'.$lang['srv_forum_back'].'</a>';
					$tid = $f->addPost($global_user_id, strip_tags($rows['naslov']), $vsebina_post, 0, 0, 0, false);
					if ($spremenljivka > 0)
						$sql = sisplet_query("UPDATE srv_spremenljivka SET thread = '$tid' WHERE id = '$spremenljivka'");
					elseif ($spremenljivka == -1)
						$sql = sisplet_query("UPDATE srv_anketa SET thread_intro = '$tid' WHERE id = '$this->anketa'");
					elseif ($spremenljivka == -2)
						$sql = sisplet_query("UPDATE srv_anketa SET thread_concl = '$tid' WHERE id = '$this->anketa'");
					else
						$sql = sisplet_query("UPDATE srv_anketa SET thread = '$tid' WHERE id = '$this->anketa'");
					
					// vsilimo refresh podatkov
					SurveyInfo :: getInstance()->resetSurveyData();
				}

				$f->currentThread = $tid;
				$_id = $f->addPost($global_user_id, strip_tags($rows['naslov']), nl2br($vsebina), 0, 0, 0, false);

				if ($o !== false) sisplet_query("UPDATE post SET ocena = '$o' WHERE id = '$_id'");		
			}
			
			// prikazemo komentarje, ce le-ti obstajajo
			if ($tid != 0 && ($view == 1 || $view == 3)) {
				
				$orderby = $sortpostorder == 1 ? 'DESC' : 'ASC' ;
				
				$tema_vsebuje = substr($lang['srv_forum_intro'],0,10);		// da ne prikazujemo 1. default sporocila
				
				if ($admin_type <= $question_comment_viewadminonly) {	// vidi vse komentarje
					$sql = sisplet_query("SELECT * FROM post WHERE vsebina NOT LIKE '%{$tema_vsebuje}%' AND tid='$tid' ORDER BY time $orderby, id $orderby");
				} elseif (($type==0 && $survey_comment_viewauthor==1) || ($type==1 && $question_comment_viewauthor==1)) {	// vidi samo svoje komentarje
					$sql = sisplet_query("SELECT * FROM post WHERE vsebina NOT LIKE '%{$tema_vsebuje}%' AND tid='$tid' AND uid='$global_user_id' ORDER BY time $orderby, id $orderby");
				} else {												// ne vidi nobenih komentarjev
					$sql = sisplet_query("SELECT * FROM post WHERE 1=0");
				}
				
				if (mysqli_num_rows($sql) > 0) {
					if ($view == 1) {
						//echo '<b><a href="'.$site_url.'index.php?fl=4&fid='.$row['forum'].'&tid='.$tid.'&sortpostorder='.$sortpostorder.'" target="_blank">'.$lang['srv_forum_go'].'</a></b>';
						$rows = mysqli_num_rows($sql);
						if ($rows > 0) echo '<img src="'.$site_url.'/admin/survey/img_0/'.($sortpostorder==1?'up':'down').'.gif" style="float:right" title="'.($sortpostorder==1?$lang['forum_desc']:$lang['forum_asc']).'" />';
						echo '<br /><br />';
					} elseif ($view == 3) {
						echo '<div style="width:45%; float:left">';
						echo '<h3 class="red"><b>'.$lang['comments'].'</b>';
						$rows = mysqli_num_rows($sql);
						if ($rows > 0) echo '<img src="img_0/'.($sortpostorder==1?'up':'down').'.gif" style="float:right" title="'.($sortpostorder==1?$lang['forum_desc']:$lang['forum_asc']).'" />';
						echo '</h3>';
					}
				}
				
				// textarea za oddat komentar - zgoraj
				if ($addfieldposition == 1) {
					$this->add_comment_field($spremenljivka, $type, $view);
					echo '<br /><br />';
				}
				
				if (mysqli_num_rows($sql) > 0) {
					
					$i = 0;
					$rows = mysqli_num_rows($sql);
					while ($row = mysqli_fetch_array($sql)) {

						// Prikazemo zgodovino glede na nastavitev
						if($comment_history == '2' || (($comment_history == '0' || $comment_history == '') && $row['uid'] == $global_user_id)){
							if ($row['ocena'] == 0) echo '<span style="color:black">';
								elseif ($row['ocena'] == 1) echo '<span style="color:darkgreen">';
								elseif ($row['ocena'] == 2) echo '<span style="color:#999999">';
								elseif ($row['ocena'] == 3) echo '<span style="color:#999999">';
								else echo '<span>';
							
							echo '<b>'.$f->user($row['uid']).'</b> ('.$f->datetime1($row['time']).'):';
							
							if ($admin_type <= 1 || $rowi['insert_uid']==$global_user_id || $commentmarks_who==0) {
									
								echo '<div style="float:right">';
								
								if ($commentmarks == 1) {
									echo '	<select name="ocena" onchange="$.post(siteurl+\'ajax.php?a=comment_ocena\', {type: \'question_comment\', ocena: this.value, id: \''.$row['id'].'\', anketa: \''.$rowi['id'].'\'}, function () { add_comment(\''.$spremenljivka.'\', \''.$type.'\', \''.$view.'\', \'\' ); });">
												<option value="0"'.($row['ocena']==0?' selected':'').'>'.$lang['srv_undecided'].'</option>
												<option value="1"'.($row['ocena']==1?' selected':'').'>'.$lang['srv_todo'].'</option>
												<option value="2"'.($row['ocena']==2?' selected':'').'>'.$lang['srv_done'].'</option>
												<option value="3"'.($row['ocena']==3?' selected':'').'>'.$lang['srv_not_relevant'].'</option>
											</select>';
								} else {
									echo '<input type="checkbox" name="ocena_'.$row['id'].'" id="ocena_'.$row['id'].'" style="margin-right:3px;" onchange="$.post(siteurl+\'ajax.php?a=comment_ocena\', {type: \'question_comment\', ocena: (this.checked?\'2\':\'0\'), id: \''.$row['id'].'\', anketa: \''.$rowi['id'].'\'}, function () { add_comment(\''.$spremenljivka.'\', \''.$type.'\', \''.$view.'\', \'\' ); });" value="2" '.($row['ocena'] >= 2?' checked':'').' /><label for="ocena_'.$row['id'].'">'.$lang['srv_done'].'</label>';
								}
								//echo '	<br /><a href="javascript:comment_on_comment(\''.$rowt['id'].'\');">'.$lang['srv_comment_comment'].'</a>';
								echo '</div>';
							}
							
							echo '<br/>'.$row['vsebina'].'<hr>';
							
							echo '</span>';
						}
						
						//}
						$i++;
						
						// Nastavimo oglede foruma in teme
						if ($global_user_id > 0) {
							$sqla2 = sisplet_query("SELECT time FROM views WHERE pid='" .$row['id'] ."' AND uid='$global_user_id'");
							if (mysqli_num_rows($sqla2) > 0) {
								$sqla3 = sisplet_query("UPDATE views SET time=NOW() WHERE pid='" .$row['id'] ."' AND uid='$global_user_id'");
							} else {
								$sqla3 = sisplet_query("INSERT INTO views (pid, uid, time) VALUES ('" .$row['id'] ."', '$global_user_id', NOW())");
							}
						}
					}
				}
				
				// textarea za oddat komentar - spodaj
				if ($addfieldposition == 0 || $addfieldposition == '') {
					echo '<br />';
					$this->add_comment_field($spremenljivka, $type, $view);
				}
			
			} else {
				$this->add_comment_field($spremenljivka, $type, $view);
			}
					
			if ($_REQUEST['refresh'] != '1' || $view==3)
				echo '</div>';
		} 
		// komentarji na if ali blok
		elseif ($type == 5 || $type == 6){
		
			// okvir, da se lahko refresha pri oddaji novega komentarja
			if ($_REQUEST['refresh'] != '1')
				echo '<div id="survey_comment_'.$spremenljivka.'_'.$view.'">';

			$vsebina = !$v ? $_REQUEST['vsebina'] : $v;

			$rows = Cache::srv_if($spremenljivka);
			if ($rows['thread'] > 0)
				$tid = $rows['thread'];

			// poslali smo vsebino, ki jo shranimo v forum
			if ($vsebina != '') {

				Common::updateEditStamp();
				
				if ($row['forum'] == 0) {
					$row['forum'] = $this->comment_create_forum();
				}

				$f->currentForum = $row['forum'];

				if ($tid == 0) {
					if ($type == 5)
						$vsebina_post = $lang['srv_forum_intro_if'].strip_tags($rows['naslov']).'<br /><br /><a href="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'">'.$lang['srv_forum_back'].'</a>';
					else
						$vsebina_post = $lang['srv_forum_intro_blok'].strip_tags($rows['naslov']).'<br /><br /><a href="'.$site_url.'admin/survey/index.php?anketa='.$this->anketa.'">'.$lang['srv_forum_back'].'</a>';
					
					$tid = $f->addPost($global_user_id, strip_tags($rows['naslov']), $vsebina_post, 0, 0, 0, false);
					
					$sql = sisplet_query("UPDATE srv_if SET thread = '$tid' WHERE id = '$spremenljivka'");
					
					// vsilimo refresh podatkov
					SurveyInfo :: getInstance()->resetSurveyData();
				}

				$f->currentThread = $tid;
				$_id = $f->addPost($global_user_id, strip_tags($rows['naslov']), nl2br($vsebina), 0, 0, 0, false);

				if ($o !== false) sisplet_query("UPDATE post SET ocena = '$o' WHERE id = '$_id'");		
			}
			
			// prikazemo komentarje, ce le-ti obstajajo
			if ($tid != 0 && ($view == 1 || $view == 3)) {
				
				$orderby = $sortpostorder == 1 ? 'DESC' : 'ASC' ;
				
				$tema_vsebuje = substr($lang['srv_forum_intro'],0,10);		// da ne prikazujemo 1. default sporocila
				
				if ($admin_type <= $question_comment_viewadminonly) {	// vidi vse komentarje
					$sql = sisplet_query("SELECT * FROM post WHERE vsebina NOT LIKE '%{$tema_vsebuje}%' AND tid='$tid' ORDER BY time $orderby, id $orderby");
				} elseif ($survey_comment_viewauthor==1 || $question_comment_viewauthor==1) {	// vidi samo svoje komentarje
					$sql = sisplet_query("SELECT * FROM post WHERE vsebina NOT LIKE '%{$tema_vsebuje}%' AND tid='$tid' AND uid='$global_user_id' ORDER BY time $orderby, id $orderby");
				} else {												// ne vidi nobenih komentarjev
					$sql = sisplet_query("SELECT * FROM post WHERE 1=0");
				}
				
				if (mysqli_num_rows($sql) > 0) {
					if ($view == 1) {
						//echo '<b><a href="'.$site_url.'index.php?fl=4&fid='.$row['forum'].'&tid='.$tid.'&sortpostorder='.$sortpostorder.'" target="_blank">'.$lang['srv_forum_go'].'</a></b>';
						$rows = mysqli_num_rows($sql);
						if ($rows > 0) echo '<img src="'.$site_url.'/admin/survey/img_0/'.($sortpostorder==1?'up':'down').'.gif" style="float:right" title="'.($sortpostorder==1?$lang['forum_desc']:$lang['forum_asc']).'" />';
						echo '<br /><br />';
					} elseif ($view == 3) {
						echo '<div style="width:45%; float:left">';
						echo '<h3 class="red"><b>'.$lang['comments'].'</b>';
						$rows = mysqli_num_rows($sql);
						if ($rows > 0) echo '<img src="img_0/'.($sortpostorder==1?'up':'down').'.gif" style="float:right" title="'.($sortpostorder==1?$lang['forum_desc']:$lang['forum_asc']).'" />';
						echo '</h3>';
					}
				}
				
				// textarea za oddat komentar - zgoraj
				if ($addfieldposition == 1) {
					$this->add_comment_field($spremenljivka, $type, $view);
					echo '<br /><br />';
				}
				
				if (mysqli_num_rows($sql) > 0) {
					
					$i = 0;
					$rows = mysqli_num_rows($sql);
					while ($row = mysqli_fetch_array($sql)) {

						// Prikazemo zgodovino glede na nastavitev
						if($comment_history == '2' || (($comment_history == '0' || $comment_history == '') && $row['uid'] == $global_user_id)){
							if ($row['ocena'] == 0) echo '<span style="color:black">';
								elseif ($row['ocena'] == 1) echo '<span style="color:darkgreen">';
								elseif ($row['ocena'] == 2) echo '<span style="color:#999999">';
								elseif ($row['ocena'] == 3) echo '<span style="color:#999999">';
								else echo '<span>';
							
							echo '<b>'.$f->user($row['uid']).'</b> ('.$f->datetime1($row['time']).'):';
							
							if ($admin_type <= 1 || $rowi['insert_uid']==$global_user_id || $commentmarks_who==0) {
									
								echo '<div style="float:right">';
								
								if ($commentmarks == 1) {
									echo '	<select name="ocena" onchange="$.post(siteurl+\'ajax.php?a=comment_ocena\', {type: \'question_comment\', ocena: this.value, id: \''.$row['id'].'\', anketa: \''.$rowi['id'].'\'}, function () { add_comment(\''.$spremenljivka.'\', \''.$type.'\', \''.$view.'\', \'\' ); });">
												<option value="0"'.($row['ocena']==0?' selected':'').'>'.$lang['srv_undecided'].'</option>
												<option value="1"'.($row['ocena']==1?' selected':'').'>'.$lang['srv_todo'].'</option>
												<option value="2"'.($row['ocena']==2?' selected':'').'>'.$lang['srv_done'].'</option>
												<option value="3"'.($row['ocena']==3?' selected':'').'>'.$lang['srv_not_relevant'].'</option>
											</select>';
								} else {
									echo '<input type="checkbox" name="ocena_'.$row['id'].'" id="ocena_'.$row['id'].'" style="margin-right:3px;" onchange="$.post(siteurl+\'ajax.php?a=comment_ocena\', {type: \'question_comment\', ocena: (this.checked?\'2\':\'0\'), id: \''.$row['id'].'\', anketa: \''.$rowi['id'].'\'}, function () { add_comment(\''.$spremenljivka.'\', \''.$type.'\', \''.$view.'\', \'\' ); });" value="2" '.($row['ocena'] >= 2?' checked':'').' /><label for="ocena_'.$row['id'].'">'.$lang['srv_done'].'</label>';
								}
								//echo '	<br /><a href="javascript:comment_on_comment(\''.$rowt['id'].'\');">'.$lang['srv_comment_comment'].'</a>';
								echo '</div>';
							}
							
							echo '<br/>'.$row['vsebina'].'<hr>';
							
							echo '</span>';
						}
						
						//}
						$i++;
						
						// Nastavimo oglede foruma in teme
						if ($global_user_id > 0) {
							$sqla2 = sisplet_query("SELECT time FROM views WHERE pid='" .$row['id'] ."' AND uid='$global_user_id'");
							if (mysqli_num_rows($sqla2) > 0) {
								$sqla3 = sisplet_query("UPDATE views SET time=NOW() WHERE pid='" .$row['id'] ."' AND uid='$global_user_id'");
							} else {
								$sqla3 = sisplet_query("INSERT INTO views (pid, uid, time) VALUES ('" .$row['id'] ."', '$global_user_id', NOW())");
							}
						}
					}
				}
				
				// textarea za oddat komentar - spodaj
				if ($addfieldposition == 0 || $addfieldposition == '') {
					echo '<br />';
					$this->add_comment_field($spremenljivka, $type, $view);
				}
			
			} else {
				$this->add_comment_field($spremenljivka, $type, $view);
			}
					
			if ($_REQUEST['refresh'] != '1' || $view==3)
				echo '</div>';		
		}
		// komentarji respondentov
		elseif ($type == 2) {     
		
			$db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();
		
			$orderby = $sortpostorder == 1 ? 'DESC' : 'ASC' ;
			$sql = sisplet_query("SELECT d.*, u.time_edit FROM srv_data_text".$db_table." d, srv_user u WHERE d.spr_id='0' AND d.vre_id='$spremenljivka' AND u.id=d.usr_id ORDER BY d.id $orderby");
			if (mysqli_num_rows($sql) > 0) echo '<img src="img_0/'.($sortpostorder==1?'up':'down').'.gif" style="float:right" title="'.($sortpostorder==1?$lang['forum_desc']:$lang['forum_asc']).'" />';
			while ($row = mysqli_fetch_array($sql)) {
				if ($row['text2'] == 0) echo '<span style="color:black">';
				elseif ($row['text2'] == 1) echo '<span style="color:darkgreen">';
				elseif ($row['text2'] == 2) echo '<span style="color:#999999">';
				elseif ($row['text2'] == 3) echo '<span style="color:#999999">';
				else echo '<span>';
							
				echo $f->datetime1($row['time_edit']).':';
				
				if ($admin_type <= 1 || $rowi['insert_uid']==$global_user_id || $commentmarks_who==0) {
				echo '<div style="float:right">';
				if ($commentmarks == 1) {
					echo '	<select name="ocena'.$row['id'].'" onchange="$.post(siteurl+\'ajax.php?a=comment_ocena\', {type: \'respondent_comment\', text2: this.value, id: \''.$row['id'].'\', anketa: \''.$rowi['id'].'\'}, function () {window.location.reload();});">
								<option value="0"'.($row['text2']==0?' selected':'').'>'.$lang['srv_undecided'].'</option>
								<option value="1"'.($row['text2']==1?' selected':'').'>'.$lang['srv_todo'].'</option>
								<option value="2"'.($row['text2']==2?' selected':'').'>'.$lang['srv_done'].'</option>
								<option value="3"'.($row['text2']==3?' selected':'').'>'.$lang['srv_not_relevant'].'</option>
							</select>';
				} else {
					echo '<input type="checkbox" name="ocena_'.$row['id'].'" id="ocena_'.$row['id'].'" onchange="$.post(siteurl+\'ajax.php?a=comment_ocena\', {type: \'respondent_comment\', text2: (this.checked?\'2\':\'0\'), id: \''.$row['id'].'\', anketa: \''.$rowi['id'].'\'}, function () {window.location.reload();});" value="2" '.($row['text2'] >= 2?' checked':'').' /><label for="ocena_'.$row['id'].'">'.$lang['srv_done'].'</label>';
				}
				echo '  </div>';
			}
				
				echo '<br />'.nl2br($row['text']).'<hr>';
				
				echo '</span>';
			}
		}
		// komentarji respondentov za celo anketo
		elseif($type == 4){
					
			// okvir, da se lahko refresha pri oddaji novega komentarja
			if ($_REQUEST['refresh'] != '1')
				echo '<div id="survey_comment_'.$spremenljivka.'_'.$view.'">';

			$vsebina = !$v ? $_REQUEST['vsebina'] : $v;
                
			// poslali smo vsebino, ki jo shranimo
			if ($vsebina != '') {

				$ocena = ($o !== false) ? $o : 0;
				$sql = sisplet_query("INSERT INTO srv_comment_resp (ank_id, usr_id, comment, comment_time, ocena) VALUES ('$this->anketa', '$global_user_id', '$vsebina', NOW(), '$ocena')");
			}
			
			// prikazemo komentarje, ce le-ti obstajajo
			$sql = sisplet_query("SELECT id FROM srv_comment_resp WHERE ank_id='".$this->anketa."' ORDER BY comment_time $orderby");
			if (mysqli_num_rows($sql) > 0 && ($view == 1 || $view == 3)) {
				
				$orderby = $sortpostorder == 1 ? 'DESC' : 'ASC' ;
				
				if ($admin_type <= $survey_comment_viewadminonly_resp) {	// vidi vse komentarje
					$sql = sisplet_query("SELECT * FROM srv_comment_resp WHERE ank_id='".$this->anketa."' ORDER BY comment_time $orderby, id $orderby");
				} elseif ($survey_comment_viewauthor_resp == 1) {	// vidi samo svoje komentarje
					$sql = sisplet_query("SELECT * FROM srv_comment_resp WHERE ank_id='".$this->anketa."' AND usr_id='$global_user_id' ORDER BY comment_time $orderby, id $orderby");
				} else {												// ne vidi nobenih komentarjev
					$sql = sisplet_query("SELECT * FROM srv_comment_resp WHERE 1=0");
				}
				
				if (mysqli_num_rows($sql) > 0) {
					if ($view == 1) {
						$rows = mysqli_num_rows($sql);
						if ($rows > 0) echo '<img src="'.$site_url.'/admin/survey/img_0/'.($sortpostorder==1?'up':'down').'.gif" style="float:right" title="'.($sortpostorder==1?$lang['forum_desc']:$lang['forum_asc']).'" />';
						echo '<br /><br />';
					} elseif ($view == 3) {
						echo '<div style="width:45%; float:left">';
						echo '<h3 class="red"><b>'.$lang['comments'].'</b>';
						$rows = mysqli_num_rows($sql);
						if ($rows > 0) echo '<img src="img_0/'.($sortpostorder==1?'up':'down').'.gif" style="float:right" title="'.($sortpostorder==1?$lang['forum_desc']:$lang['forum_asc']).'" />';
						echo '</h3>';
					}
				}
				
				// textarea za oddat komentar - zgoraj
				if ($addfieldposition == 1) {
					$this->add_comment_field($spremenljivka, $type, $view);
					echo '<br /><br />';
				}
				
				if (mysqli_num_rows($sql) > 0) {
					$rows = mysqli_num_rows($sql);
					while ($row = mysqli_fetch_array($sql)) {
						
						// Prikazemo zgodovino glede na nastavitev
						if($comment_history == '2' || (($comment_history == '0' || $comment_history == '') && $row['usr_id'] == $global_user_id && $global_user_id != 0)){
							if ($row['ocena'] == 0) echo '<span style="color:black">';
							elseif ($row['ocena'] == 1) echo '<span style="color:darkgreen">';
							elseif ($row['ocena'] == 2) echo '<span style="color:#999999">';
							elseif ($row['ocena'] == 3) echo '<span style="color:#999999">';
							else echo '<span>';
							
							$datetime = strtotime($row['comment_time']);
							$datetime = date("d.m G:i", $datetime);
							
							if($row['usr_id'] == 0){
								$user = $lang['guest'];
							}
							else{
								$sqlU = sisplet_query("SELECT name FROM users WHERE id='$row[usr_id]'");
								$rowU = mysqli_fetch_array($sqlU);
								
								$user = $rowU['name'];
							}
							
							echo '<b>'.$user.'</b> ('.$datetime.'):';
							
							if ($admin_type <= 1 || $rowi['insert_uid']==$global_user_id || $commentmarks_who==0) {
								// Zaenkrat ni ocen ce respondent komentira pri resevanju
								/*echo '<div style="float:right">';
								if ($commentmarks == 1) {
									echo '	<select name="ocena" onchange="$.post(siteurl+\'ajax.php?a=comment_ocena\', {type: \'respondent_survey_comment\', ocena: this.value, id: \''.$row['id'].'\'}, function () { add_comment(\''.$spremenljivka.'\', \''.$type.'\', \''.$view.'\', \'\' ); });">
												<option value="0"'.($row['ocena']==0?' selected':'').'>'.$lang['srv_undecided'].'</option>
												<option value="1"'.($row['ocena']==1?' selected':'').'>'.$lang['srv_todo'].'</option>
												<option value="2"'.($row['ocena']==2?' selected':'').'>'.$lang['srv_done'].'</option>
												<option value="3"'.($row['ocena']==3?' selected':'').'>'.$lang['srv_not_relevant'].'</option>
											</select>';
								} else {							
									echo '<input type="checkbox" name="ocena_'.$row['id'].'" id="ocena_'.$row['id'].'" onchange="$.post(siteurl+\'ajax.php?a=comment_ocena\', {type: \'question_comment\', ocena: (this.checked?\'2\':\'0\'), id: \''.$row['id'].'\'}, function () { add_comment(\''.$spremenljivka.'\', \''.$type.'\', \''.$view.'\', \'\' ); });" value="2" '.($row['ocena'] >= 2?' checked':'').' /><label for="ocena_'.$row['id'].'">'.$lang['srv_done'].'</label>';
								}
								echo '</div>';*/
							}
							
							echo '<br/>'.$row['comment'].'<hr>';
							
							echo '</span>';
						}
					}
				}
				
				// textarea za oddat komentar - spodaj
				if ($addfieldposition == 0 || $addfieldposition == '') {
					echo '<br />';
					$this->add_comment_field($spremenljivka, $type, $view);
				}
			
			} else {
				$this->add_comment_field($spremenljivka, $type, $view);
			}
			
			
			if ($_REQUEST['refresh'] != '1' || $view==3)
				echo '</div>';
		}
	}
	
	function add_comment_field ($spremenljivka, $type, $view, $form=true) {
		global $admin_type;
		global $global_user_id;
		global $lang;
		global $site_url;
		
		$rowanketa = SurveyInfo::getInstance()->getSurveyRow();
		
		echo '<textarea name="vsebina" id="vsebina_'.$spremenljivka.'_'.$view.'" style="width:100%; height:50px; margin-bottom:10px; border:1px red solid;"></textarea><br />';
		echo '<input type="submit" value="'.$lang['send'].'" onclick="add_comment(\''.$spremenljivka.'\', \''.$type.'\', \''.$view.'\', $(\'#vsebina_'.$spremenljivka.'_'.$view.'\').val()); return false;" />';
		
		if (($type == 0 || $view==3) && ($admin_type == 0 || $global_user_id==$rowanketa['insert_uid'])) {
		
			echo '<div style="float:right">';
			
			// Link na pregled splosnih komentarjev
			echo '<span style="margin-right: 20px;"><a href="'.$site_url.'/admin/survey/index.php?anketa='.$this->anketa.'&a=komentarji_anketa">'.$lang['srv_comment_overview'].'</a></span>';	
			
			// Link na nastavitve komentarjev
			echo '<a href="'.$site_url.'/admin/survey/index.php?anketa='.$this->anketa.'&a=urejanje">'.$lang['settings'].'</a>';
			
			echo '</div>';
		}	
	}
	
	/**
	* @desc kreira nov forum za komentiranje ankete
	*/
	function comment_create_forum() {
		global $site_path;
		global $site_url;
		global $lang;
		global $global_user_id;
		
		Common::updateEditStamp();
		
		$sql = sisplet_query("SELECT * FROM misc WHERE what = 'SurveyForum'");
		$row = mysqli_fetch_array($sql);
		$parent = $row['value'];

		$sqlp = sisplet_query("SELECT * FROM forum WHERE id='$parent'");
		$rowp = mysqli_fetch_array($sqlp);

		$row = SurveyInfo::getInstance()->getSurveyRow();

		$sql = sisplet_query("INSERT INTO forum (parent, ord, naslov, opis, user, clan, admin) VALUES ('$parent', '2132154', '$row[naslov]', '', '$rowp[user]', '$rowp[clan]', '$rowp[admin]')");
		$id = mysqli_insert_id($GLOBALS['connect_db']);

		$sql = sisplet_query("UPDATE srv_anketa SET forum='$id' WHERE id = '$this->anketa'");
        
        // vsilimo refresh podatkov
		SurveyInfo :: getInstance()->resetSurveyData();

		return $id;
	}
	
	function ajax_preview_spremenljivka () {
        include_once('../../main/survey/app/global_function.php');
        new \App\Controllers\SurveyController(true);
		
		$spremenljivka = $_POST['spremenljivka'];
		
		save('forceShowSpremenljivka', true);
        \App\Controllers\Vprasanja\VprasanjaController::getInstance()->displaySpremenljivka($spremenljivka);
	}
	
	function ajax_calculation_edit_variable () {
		Common::updateEditStamp();
		
		$variable = $_POST['variable'];

		// preverimo, da ni se kje drugje v anekti tako ime spremenljivke
		$sqlv = sisplet_query("SELECT s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa'");
		$ok = true;
		while ($rowv = mysqli_fetch_array($sqlv)) {
			if ($rowv['variable'] == $variable) 
				$ok = false;
		}

		// Ce se ime ze pojavi v anketi mu dodamo stevilko
		if(!$ok){
			$ok = false;
			$i = 2;
			while(!$ok){				
				$ok = true;
				$variable = $_POST['variable'].'_'.$i;
				
				$sqlv = sisplet_query("SELECT s.variable, s.id as id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa'");
				while ($rowv = mysqli_fetch_array($sqlv)) {
					if ($rowv['variable'] == $variable && $this->spremenljivka != $rowv['id']){
						$ok = false;
						$i++;
					}
				}
			}
		}
		
		sisplet_query("UPDATE srv_spremenljivka SET variable='$variable', variable_custom='1' WHERE id='$this->spremenljivka'");
	}
	
	function ajax_calculation_edit_decimalna () {
		Common::updateEditStamp();
		
		$decimalna = $_POST['decimalna'];
		
		sisplet_query("UPDATE srv_spremenljivka SET decimalna='$decimalna' WHERE id='$this->spremenljivka'");
	}
	
	function ajax_calculation_edit_missing () {
		Common::updateEditStamp();
			
		if(isset($_POST['spremenljivka']) && isset($_POST['missing'])){

            $spremenljivka = $_POST['spremenljivka'];
            $missing = $_POST['missing'];

            $row = Cache::srv_spremenljivka($spremenljivka);
            $newParams = new enkaParameters($row['params']);

            $newParams->set('calcMissing', $missing);
            $params = $newParams->getString();

            $sql = sisplet_query("UPDATE srv_spremenljivka SET params='$params' WHERE id='$spremenljivka'");
        }
	}
	
	function ajax_fill_value_loop () {
    	Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();

        $if = $_POST['if'];
        $spremenljivka = $_POST['spremenljivka'];

        $row = Cache::srv_spremenljivka($spremenljivka);
        
        // obicna spremenljivka
        if (is_numeric($spremenljivka)) {
            $spremenljivka = $spremenljivka;
            $vrednost = 0;
        // multigrid
        } /*else {
            $vrednost = str_replace('vre_', '', $spremenljivka);
            $sql2 = sisplet_query("SELECT * FROM srv_vrednost WHERE id = '$vrednost'");
            $row2 = mysqli_fetch_array($sql2);
            $spremenljivka = $row2['spr_id'];
        }*/

        sisplet_query("REPLACE INTO srv_loop (if_id, spr_id) VALUES ('$if', '$spremenljivka')");

        // na zacetku damo po defaultu na 'izbran'
        $s = sisplet_query("DELETE FROM srv_loop_vre WHERE if_id = '$if'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		$s = sisplet_query("DELETE FROM srv_loop_data WHERE if_id = '$if'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		// pri numberju nafilamo 20 opcij - omogocali bomo max 20 zank po numberju
		if ($row['tip'] == 7) {
			
			$s = sisplet_query("INSERT INTO srv_loop_vre (if_id, vre_id) VALUES ('$if', NULL)");
			
			for ($i=0; $i<20; $i++) {
				$s = sisplet_query("INSERT INTO srv_loop_data (id, if_id, vre_id) VALUES ('', '$if', NULL)");
			}
				
			
		// pri ostalih se nafila vse ki so v srv_vrednost	
		} else {
			$sql2 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$spremenljivka' ORDER BY vrstni_red ASC");			
			while ($row2 = mysqli_fetch_array($sql2)) {
				$vrednost = $row2['id'];
			
				if ($vrednost > 0) {
					$s = sisplet_query("INSERT INTO srv_loop_vre (if_id, vre_id) VALUES ('$if', '$vrednost')");
					if (!$s) echo mysqli_error($GLOBALS['connect_db']);
					$s = sisplet_query("INSERT INTO srv_loop_data (id, if_id, vre_id) VALUES ('', '$if', '$vrednost')");
					if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				}				
			}
		}
		
        $b = new Branching($this->anketa);
        $b->condition_editing($if);
	}
	
	function ajax_loop_edit () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
		
		$if = $_POST['if'];
		$vrednost = $_POST['vrednost'];
		
		$s = sisplet_query("DELETE FROM srv_loop_vre WHERE if_id = '$if' ".(count($vrednost)>0?"AND vre_id NOT IN (".implode(',', $vrednost).")":"")."");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		$s = sisplet_query("DELETE FROM srv_loop_data WHERE if_id = '$if' ".(count($vrednost)>0?"AND vre_id NOT IN (".implode(',', $vrednost).")":"")."");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		if ($vrednost != null)
            foreach ($vrednost AS $val)
                if ($val > 0) {
                	$sql = sisplet_query("SELECT * FROM srv_loop_vre WHERE if_id='$if' AND vre_id='$val'");
                	if (mysqli_num_rows($sql) == 0) {
                    	$s = sisplet_query("INSERT INTO srv_loop_vre (if_id, vre_id) VALUES ('$if', '$val')");
                    	if (!$s) echo mysqli_error($GLOBALS['connect_db']);
                    }
                    $sql = sisplet_query("SELECT * FROM srv_loop_data WHERE if_id='$if' AND vre_id='$val'");
                	if (mysqli_num_rows($sql) == 0) {
						$s = sisplet_query("INSERT INTO srv_loop_data (id, if_id, vre_id) VALUES ('', '$if', '$val')");
						if (!$s) echo mysqli_error($GLOBALS['connect_db']);
					}
				}
				
		$b = new Branching($this->anketa);
        $b->condition_editing($if);
	}
	
	function ajax_loop_edit_advanced () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
		
		$if = $_POST['if'];
		$vrednost = $_POST['vrednost'];
		
		$sql = sisplet_query("SELECT spr_id FROM srv_loop WHERE if_id='$if'");
		$row = mysqli_fetch_array($sql);
		
		// ko postnemo izbrane vrednosti, so podani vrstni_red in ne ID, ker JS zapolni cel array s praznimi vrednostmi do izbranega IDja (ki pa so lahko zelo veliki in je zato zelooo velik array)
		$vre = array();
		$sql = sisplet_query("SELECT id, vrstni_red FROM srv_vrednost WHERE spr_id='$row[spr_id]' ORDER BY vrstni_red ASC");
		while ($row = mysqli_fetch_array($sql)) {
			$vre[$row['vrstni_red']] = $row['id'];
		}
		
		$delete = array();
		foreach ($vrednost AS $key => $val) {
			if ($val != 'undefined' && $val < 3) {	// 3 pomeni nikoli in tega ne shranimo v bazo
				$delete[] = $vre[$key];
			}
		}
		
		$s = sisplet_query("DELETE FROM srv_loop_vre WHERE if_id = '$if' ".(count($delete)>0?"AND vre_id NOT IN (".implode(',', $delete).")":"")."");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		$s = sisplet_query("DELETE FROM srv_loop_data WHERE if_id = '$if' ".(count($delete)>0?"AND vre_id NOT IN (".implode(',', $delete).")":"")."");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		foreach ($vrednost AS $key => $val) {
			if ($val != 'undefined' && $val < 3) {	// 3 pomeni nikoli in tega ne shranimo v bazo
				
				//echo $vre[$key].': '.$val."\n";
				$sql = sisplet_query("SELECT * FROM srv_loop_vre WHERE if_id='$if' AND vre_id='$vre[$key]'");
   				if (mysqli_num_rows($sql) == 0) {
					$s = sisplet_query("INSERT INTO srv_loop_vre (if_id, vre_id, tip) VALUES ('$if', '$vre[$key]', '$val')");
	                if (!$s) echo mysqli_error($GLOBALS['connect_db']);
                } else {
                	$s = sisplet_query("UPDATE srv_loop_vre SET tip='$val' WHERE if_id='$if' AND vre_id='$vre[$key]'");
	                if (!$s) echo mysqli_error($GLOBALS['connect_db']);
                }
                $sql = sisplet_query("SELECT * FROM srv_loop_data WHERE if_id='$if' AND vre_id='$vre[$key]'");
               	if (mysqli_num_rows($sql) == 0) {
					$s = sisplet_query("INSERT INTO srv_loop_data (id, if_id, vre_id) VALUES ('', '$if', '$vre[$key]')");
					if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				}
			}
			
		}
		
		$b = new Branching($this->anketa);
        $b->condition_editing($if);
	}
	
	function ajax_loop_edit_max () {
		Common::updateEditStamp();
		
		$max = $_POST['max'];
		$if = $_POST['if'];
		
		$s = sisplet_query("UPDATE srv_loop SET max='$max' WHERE if_id = '$if'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
	}
	
	/**
	* preveri, ce so loopi v redu postavljeni
	* 
	*/
	function check_loop () {
		
		// preverimo za loope, ce so in ce so ok postavljeni
		$b = new Branching($this->anketa);
		$b->check_loop();
	}
	
	/**
	* zakleni / odkleni anketo
	* 
	*/
	function ajax_anketa_lock () {
		Common::updateEditStamp();
		
		$locked = $_POST['locked'];
		
                //mobile created nastavi na 0, ce je slucajno prej 1 (zaradi mobilne aplikacije)
		$s = sisplet_query("UPDATE srv_anketa SET locked = '$locked', mobile_created = '0' WHERE id = '$this->anketa'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		 
		echo 'index.php?anketa='.$this->anketa.'&a=branching';
	}
	
	function ajax_condition_sort() {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
    	$if = $_POST['if'];
    	$sortable = $_POST['sortable'];
    	$sortable = explode('&', $sortable);
    	
    	$i=1;
    	foreach ($sortable AS $cond) {
			$condition = explode('=', $cond);
			$condition = $condition[1];
			
			$s = sisplet_query("UPDATE srv_condition SET vrstni_red='{$i}' WHERE id='{$condition}'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);

			$i++;
    	}
    	
		$b = new Branching($this->anketa);
		$b->repare_condition($if);
		$b->condition_editing_inner($if);
	}
	
	function ajax_calculation_sort() {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
    	$condition = $_POST['condition'];
    	$sortable = $_POST['sortable'];
    	$sortable = explode('&', $sortable);
    	
    	$i=1;
    	foreach ($sortable AS $calc) {
			$calculation = explode('=', $calc);
			$calculation = $calculation[1];
			
			$s = sisplet_query("UPDATE srv_calculation SET vrstni_red='{$i}' WHERE id='{$calculation}'");
			if (!$s) echo mysqli_error($GLOBALS['connect_db']);

			$i++;
    	}
    	
		$b = new Branching($this->anketa);
		$b->repare_condition($if);
		$b->calculation_editing_inner($condition);
	}
	
	function ajax_spremenljivka_preview_print() {
		global $lang;
		
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
		<head>
		<title>CMS</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link type="text/css" href="minify/g=css" media="screen" rel="stylesheet" />
		<link type="text/css" href="minify/g=cssPrint" media="print" rel="stylesheet" />
		<!--[if lt IE 7]>
		<link rel="stylesheet" href="http://localhost/fdv/cms2/admin/survey/css/ie6hacks.css" type="text/css" />
		<![endif]-->
		<!--[if IE 7]>
		<link rel="stylesheet" href="http://localhost/fdv/cms2/admin/survey/css/ie7hacks.css" type="text/css" />
		<![endif]-->
		<!--[if IE 8]>
		<link rel="stylesheet" href="http://localhost/fdv/cms2/admin/survey/css/ie8hacks.css" type="text/css" />
		<![endif]-->
		</head>
		<body>
		<?
	
		echo '<div id="printIcon">';
		echo '<a href="#" onclick="window.print(); return false;"><span class="faicon print_small icon-grey_dark_link"></span> '.$lang['hour_print2'].'</a>';
		echo '</div>';

        include_once('../../main/survey/app/global_function.php');
        new \App\Controllers\SurveyController(true);

		echo '  <div  id="spremenljivka_preview">';
		if ( $_GET['spremenljivka'] == -1 ) {
            \App\Controllers\BodyController::getInstance()->displayIntroduction();
		}
		elseif ( $_GET['spremenljivka'] == -2 ) {
            \App\Controllers\BodyController::getInstance()->displayKonec();
		}
		elseif ( $_GET['spremenljivka'] == -3 ) {
            \App\Controllers\StatisticController::displayStatistika();
		}
		else {
            save('forceShowSpremenljivka', true);
            \App\Controllers\Vprasanja\VprasanjaController::getInstance()->displaySpremenljivka($_GET['spremenljivka']);
		}
		
		?>
		</body>
		</html>
		<?
	}
	
	function ajax_toolbox_add_advanced () {
		
		$b = new Branching($this->anketa);
		$b->toolbox_add_advanced();
	}
	function ajax_toggle_toolbox () {
		
		$b = new Branching($this->anketa);
		$b->toogle_toolbox_nastavitve();
	}
	
	function ajax_if_edit_enabled () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
		
		$if = $_POST['if'];
		$enabled = $_POST['enabled'];
		
		sisplet_query("UPDATE srv_if SET enabled = '$enabled' WHERE id = '$if'");
	}
	
	function ajax_vprasanje_full () {
		
		$b = new Branching($this->anketa);
		
		if ($this->spremenljivka > 0) {
			$b->vprasanje($this->spremenljivka);
		} else {
			$b->introduction_conclusion($this->spremenljivka);
		}
	}
	
	function ajax_find_replace() {
		global $lang;
        
        echo '<div class="popup_close"><a href="#" onClick="$(\'#vrednost_edit\').hide().html(\'\'); $(\'#fade\').fadeOut(); return false;">✕</a></div>';

		echo '<h2>'.$lang['srv_find_replace'].'</h2>';
		
		echo '<p class="gray">'.$lang['srv_find_text'].'</p>';
		
		echo '<p><label style="display:inline-block; width:100px">'.$lang['srv_find'].': </label><input type="text" name="find" style="width:200px"><span id="find_count" style="color:red; margin:0 20px"></span></p>';
		echo '<p><label style="display:inline-block; width:100px">'.$lang['srv_replace_with'].': </label><input type="text" name="replace" style="width:200px"></p>';
		
		echo '<span class="floatRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_orange btn_savesettings" onclick="find_replace_do(); return false;" href="#"><span>'.$lang['srv_replace'].'</span></a></div></span>';
		echo '<span class="floatRight spaceRight"><div class="buttonwrapper"><a class="ovalbutton ovalbutton_gray btn_savesettings" onclick="$(\'#vrednost_edit\').hide().html(\'\'); $(\'#fade\').fadeOut(); return false;" href="#"><span>'.$lang['srv_cancel'].'</span></a></div></span>';	
	}
	
	function ajax_find_replace_count() {
		global $lang;
		
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
		
		$find = $_POST['find'];
		$replace = $find.'foo';	// more bit drugacen od $find da mysql_affected_rows prime
		
		if ($find == '') return;
		
		$count = 0;
		
		sisplet_query("BEGIN");
		
		$s = sisplet_query("UPDATE srv_anketa SET naslov = REPLACE(naslov, '$find', '$replace'), 
												akronim = REPLACE(akronim, '$find', '$replace'),
												introduction = REPLACE(introduction, '$find', '$replace'),
												conclusion = REPLACE(conclusion, '$find', '$replace'),
												intro_opomba = REPLACE(intro_opomba, '$find', '$replace'),
												intro_note = REPLACE(intro_note, '$find', '$replace'),
												concl_note = REPLACE(concl_note, '$find', '$replace')
											WHERE id = '$this->anketa'
		");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		$count += mysqli_affected_rows($GLOBALS['connect_db']);
		
		$s = sisplet_query("UPDATE srv_spremenljivka s, srv_grupa g SET 
												s.naslov = REPLACE(s.naslov, '$find', '$replace'), 
												s.info = REPLACE(s.info, '$find', '$replace'),
												s.naslov_graf = REPLACE(s.naslov_graf, '$find', '$replace')
											WHERE s.gru_id=g.id AND g.ank_id='$this->anketa'
		");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		$count += mysqli_affected_rows($GLOBALS['connect_db']);
		
		$s = sisplet_query("UPDATE srv_vrednost v, srv_spremenljivka s, srv_grupa g SET 
												v.naslov = REPLACE(v.naslov, '$find', '$replace'), 
												v.naslov2 = REPLACE(v.naslov2, '$find', '$replace'),
												v.naslov_graf = REPLACE(v.naslov_graf, '$find', '$replace')
											WHERE v.spr_id=s.id AND s.gru_id=g.id AND g.ank_id='$this->anketa'
		");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		$count += mysqli_affected_rows($GLOBALS['connect_db']);
		
		$s = sisplet_query("UPDATE srv_grid gr, srv_spremenljivka s, srv_grupa g SET 
												gr.naslov = REPLACE(gr.naslov, '$find', '$replace'), 
												gr.naslov_graf = REPLACE(gr.naslov_graf, '$find', '$replace')
											WHERE gr.spr_id=s.id AND s.gru_id=g.id AND g.ank_id='$this->anketa'
		");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		$count += mysqli_affected_rows($GLOBALS['connect_db']);
		
		sisplet_query("ROLLBACK");
		
		echo $lang['srv_find_replace_count'].': '.$count;
	}
	
	function ajax_find_replace_do () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
		$find = $_POST['find'];
		$replace = $_POST['replace'];
				
		if ($find == '' || $replace == '') return;
		
		$s = sisplet_query("UPDATE srv_anketa SET naslov = REPLACE(naslov, '$find', '$replace'), 
												akronim = REPLACE(akronim, '$find', '$replace'),
												introduction = REPLACE(introduction, '$find', '$replace'),
												conclusion = REPLACE(conclusion, '$find', '$replace'),
												intro_opomba = REPLACE(intro_opomba, '$find', '$replace'),
												intro_note = REPLACE(intro_note, '$find', '$replace'),
												concl_note = REPLACE(concl_note, '$find', '$replace')
											WHERE id = '$this->anketa'
		");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		$s = sisplet_query("UPDATE srv_spremenljivka s, srv_grupa g SET 
												s.naslov = REPLACE(s.naslov, '$find', '$replace'), 
												s.info = REPLACE(s.info, '$find', '$replace'),
												s.naslov_graf = REPLACE(s.naslov_graf, '$find', '$replace')
											WHERE s.gru_id=g.id AND g.ank_id='$this->anketa'
		");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		$s = sisplet_query("UPDATE srv_vrednost v, srv_spremenljivka s, srv_grupa g SET 
												v.naslov = REPLACE(v.naslov, '$find', '$replace'), 
												v.naslov2 = REPLACE(v.naslov2, '$find', '$replace'),
												v.naslov_graf = REPLACE(v.naslov_graf, '$find', '$replace')
											WHERE v.spr_id=s.id AND s.gru_id=g.id AND g.ank_id='$this->anketa'
		");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		$s = sisplet_query("UPDATE srv_grid gr, srv_spremenljivka s, srv_grupa g SET 
												gr.naslov = REPLACE(gr.naslov, '$find', '$replace'), 
												gr.naslov_graf = REPLACE(gr.naslov_graf, '$find', '$replace')
											WHERE gr.spr_id=s.id AND s.gru_id=g.id AND g.ank_id='$this->anketa'
		");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
	}
	
	function ajax_SN_generator_new ($spremenljivka = null, $endif = null) {
		global $lang;
		
    	Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();

        if ($spremenljivka == null)	$spremenljivka = $_POST['spremenljivka'];
		if ($endif == null)			$endif = $_POST['endif'];
		$tip = 9;
		
		$b = new Branching($this->anketa);
        $this->spremenljivka_new($spremenljivka, 0, $endif, 0, 2);

		Vprasanje::change_tip($this->spremenljivka, $tip);
	
		$gen_id = $this->spremenljivka;		

		
		// USTVARIMO PAGEBREAK
		sisplet_query("UPDATE srv_branching SET pagebreak = '1' WHERE element_spr = '$this->spremenljivka' AND ank_id='".$this->anketa."'");
		
		
		// USTVARIMO NAGOVOR
		$this->spremenljivka_new($gen_id, 0, 0, 0, 0);
		Vprasanje::change_tip($this->spremenljivka, 5);
		
		// nastavimo text nagovora
		$row = Cache::srv_spremenljivka($gen_id);
		
		$naslov = $lang['srv_SN_nagovor_title'].'#'.$row['variable'].'#';
		$purifier = New Purifier();
    	$naslov = $purifier->purify_DB($naslov);
		
		sisplet_query("UPDATE srv_spremenljivka SET naslov = '$naslov' WHERE id = '$this->spremenljivka'");
		
		
		// USTVARIMO LOOP NA NAGOVORU
		$sqln = sisplet_query("SELECT MAX(i.number) AS number FROM srv_if i, srv_branching b WHERE b.ank_id='$this->anketa' AND b.element_if=i.id");
		if (!$sqln) echo mysqli_error($GLOBALS['connect_db']);
		$rown = mysqli_fetch_array($sqln);
		$number = $rown['number'] + 1;
		$sql = sisplet_query("INSERT INTO srv_if (id, number, tip) VALUES ('', '$number', '2')");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		$if_id = mysqli_insert_id($GLOBALS['connect_db']);


		if ($this->spremenljivka > 0 || ($if > 0 && $endif == 1)) {
			$sql = sisplet_query("SELECT * FROM srv_branching WHERE element_spr = '$this->spremenljivka' AND element_if='$if'");
			$row = mysqli_fetch_array($sql);
		}

		if ($if > 0 && $endif != 1) {
			$row['parent'] = $if;
			$row['vrstni_red'] = 0;
		}

		// dodajanje ifa na trenutno spremenljivko
		if ($this->spremenljivka > 0) {
			$next_element = $row;
			$include_element = true;		// v if vkljucimo tudi trenutno spremenljivko
			
		// dodajanje ifa na naslednji element
		} else {
			$next_element = $b->find_next_element($row['parent'], $row['vrstni_red']);
		}
		
		if ($next_element == null) {	// next_element je prazen na koncu ifa, takrat je tudi nov if prazen
			$next_element['parent'] = $row['parent'];
			$next_element['vrstni_red'] = $row['vrstni_red'] + 1;
			$next_element['element_spr'] = 0;
			$next_element['element_if'] = 0;
		}
		
		$add = true;

		// preverimo, da ga ne dodamo v ze obstojec loop
		if ($b->find_loop_parent($next_element['parent']) > 0)
			$add = false;
		
		// preverimo, da ge ne dodamo direktno pred obstojec loop (ker potem objame obstojec loop in dobimo vgnezdenje)
		if ($next_element['element_if'] > 0)
			if ($b->find_loop_child($next_element['element_if']) > 0)
				$add = false;
	
		if ($add) {
			$b->if_new($endif, $next_element['parent'], $if_id, $next_element['vrstni_red'], $next_element['element_spr'], $next_element['element_if'], $copy, $no_content, $include_element);

			sisplet_query("UPDATE srv_anketa SET branching='1' WHERE id = '$this->anketa'");
		
		} else {
			$b->dropped_alert($lang['srv_loop_no_nesting']);
		}
		
		
		// NASTAVIMO LOOP NA GENERATOR
        if (is_numeric($gen_id )) {
            $vrednost = 0;
        } 
		
        sisplet_query("REPLACE INTO srv_loop (if_id, spr_id) VALUES ('$if_id', '$gen_id ')");

        // na zacetku damo po defaultu na 'izbran'
        $s = sisplet_query("DELETE FROM srv_loop_vre WHERE if_id = '$if_id'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		$s = sisplet_query("DELETE FROM srv_loop_data WHERE if_id = '$if_id'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		$sql2 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$gen_id ' ORDER BY vrstni_red ASC");			
		while ($row2 = mysqli_fetch_array($sql2)) {
			$vrednost = $row2['id'];
		
			if ($vrednost > 0) {
				$s = sisplet_query("INSERT INTO srv_loop_vre (if_id, vre_id) VALUES ('$if_id', '$vrednost')");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				$s = sisplet_query("INSERT INTO srv_loop_data (id, if_id, vre_id) VALUES ('', '$if_id', '$vrednost')");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}				
		}
			

	    $b->repare_vrstni_red();
        Common::getInstance()->prestevilci();
        Cache::clear_cache();
		
		$data = array();
		
		$data['nova_spremenljivka_id'] = $gen_id;
		
		ob_start();
		$b = new Branching($this->anketa);
        $b->spremenljivka = $gen_id;
        $b->branching_struktura();
        $data['branching_struktura'] = ob_get_clean();
		
		$this->check_loop();
		
		ob_start();
		$v = new Vprasanje($this->anketa);
		$v->spremenljivka = $gen_id;
		$v->ajax_vprasanje_fullscreen();
		$data['vprasanje_fullscreen'] = ob_get_clean();
		
		echo json_encode($data);
    }
	
	/**
	* dodajanje demografije preko obrazca na prvi strani nove ankete
	* 
	*/
	function ajax_demografija_new () {
		global $lang;
		
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
		
		if ( count($_POST['variable']) > 0 ) {
			$type = $_POST['type'];
			
			$b = new Branching($this->anketa);

			ob_start();
			$if_id = 0;
			
			// ce ze obstaja blok demografija
			$if_id = $b->get_demografija_id();
			
			// dodajanje nove spremenljivke
			if ($type == 'add') {
				
				// blok obstaja, novo bomo dodali na dno
				if ($if_id > 0) {
					$sql = sisplet_query("SELECT element_spr FROM srv_branching WHERE parent='$if_id' AND ank_id='$this->anketa' ORDER BY vrstni_red DESC LIMIT 1");
					$row = mysqli_fetch_array($sql);
					
					$spr = $row['element_spr'];
					$if = 0;
				}
				
				// sicer naredimo nov blok
				if ($if_id == 0) {
					$if_id = $this->ajax_if_new(0, 0, 1, 1);
				
					sisplet_query("UPDATE srv_if SET label = '$lang[srv_demografija]' WHERE id = '$if_id'");
					
					// novo spremenljivko bomo dodali v blok
					$spr = 0;
					$if = $if_id;
				}
				
				// id spremenljivke v knjiznici, ki jo bomo dodali
				$d_id = Demografija::getInstance()->getSpremenljivkaID($_POST['variable']);
				
				$this->ajax_spremenljivka_new($spr, $if, 0, 0, 23, $d_id);
			
			// brisanje demografske spremenljivke
			} elseif ($type == 'remove') {
				
				// poiscemo ID spremenljivke v bloku demografija glede na variablo
				$sql = sisplet_query("SELECT s.id FROM srv_branching b, srv_spremenljivka s WHERE b.element_spr=s.id AND b.parent='$if_id' AND s.variable='$_POST[variable]' LIMIT 1");
				$row = mysqli_fetch_array($sql);
				
				if ($row['id'] > 0) {
					$sa = new SurveyAdmin(1, $this->anketa);
					$sa->brisi_spremenljivko($row['id']);
				}
				
				// preverimo ce je blok demografija prazen in ga odstranimo
				$sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_branching WHERE parent='$if_id'");
				$row = mysqli_fetch_array($sql);
				if ($row['count'] == 0)
					$this->ajax_if_remove($if_id);
				
			}
			
			ob_clean();
			Cache::clear_cache_all();
			
			$b->branching_struktura();
			
	        $output['branching'] = ob_get_clean();
	        $output['spremenljivka'] = $this->spremenljivka;
	        
	        echo json_encode($output);
		}	
	}
	
	function ajax_if_blok_tab() {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
		
		$if = $_POST['if'];
		$tab = $_POST['tab'];
		
		if ($if > 0) {
			sisplet_query("UPDATE srv_if SET tab='$tab' WHERE id='$if'");
			
			if ($tab == 1) {
				$b = new Branching($this->anketa);
				$spr = $b->find_last_in_if($if);
				
				if ($spr > 0)
					$this->ajax_pagebreak($spr, 1);
			
				$spr = $b->find_before_if($if);
				
				if ($spr > 0)
					$this->ajax_pagebreak($spr, 1);
			}
		}
	}
	
	function ajax_if_blok_random() {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
		
		$if = $_POST['if'];
		$random = $_POST['random'];
		
		if ($if > 0)
			sisplet_query("UPDATE srv_if SET random='$random' WHERE id='$if'");
	}
	
	function ajax_if_blok_horizontal() {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
		
		$if = $_POST['if'];
		$horizontal = $_POST['horizontal'];
		
		if ($if > 0)
			sisplet_query("UPDATE srv_if SET horizontal='$horizontal' WHERE id='$if'");
	}

    /*
     * spremenimo odgovor VISIBLE/DISABLE/HIDDEN
     *
     * @odg = 0 -> imajo vsi odgovori - visible
     * @odg = 1 -> hidden
     * @odg = 2 -> disable (viden, vendar ni mogoča izbira)
     * @return $odg
     *
     */
    function ajax_hidden_answer(){
        Common::getInstance()->Init($this->anketa);
        Common::getInstance()->updateEditStamp();

        $odg = $_POST['odgovor'];
        $id = $_POST['id'];

        switch ($odg){
            case 2:
                $odg = 0;
                break;
            default:
                $odg++;
        }

        sisplet_query("UPDATE srv_vrednost SET hidden='$odg' WHERE id='$id'");

        echo $odg;
    }

	 /*
     * spremenimo odgovor CORRECT za modul KVIZ
     *
     *
     */
    function ajax_correct_answer(){
        Common::getInstance()->Init($this->anketa);
        Common::getInstance()->updateEditStamp();

		if(isset($_POST['vre_id']) && isset($_POST['spr_id']) && isset($_POST['action'])){
			
			$spr_id = $_POST['spr_id'];
			$vre_id = $_POST['vre_id'];
			$action = $_POST['action'];

			if($action == 'add'){
				$sql = sisplet_query("INSERT INTO srv_quiz_vrednost (spr_id, vre_id) VALUES ('".$spr_id."', '".$vre_id."')");
				if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
			}
			else{
				sisplet_query("DELETE FROM srv_quiz_vrednost WHERE spr_id='".$spr_id."' AND vre_id='".$vre_id."'");
				if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
			}
		}
    }
    
    /**
     * Lokacija - 26
     * shrane v bazo koordinate za fokus, da se ne vedno porabljajo kvote geocodinga
     */
    function ajax_fokus_koordiante_map(){
        if(isset($_POST['anketa']))
            $this->anketa = $_POST['anketa'];
        Common::getInstance()->Init($this->anketa);
        Common::getInstance()->updateEditStamp();

        if(isset($_POST['spr_id'])){

            $spr_id = $_POST['spr_id'];
            $koordinate = $_POST['koordinate'];

            $row = Cache::srv_spremenljivka($spr_id);
            $newParams = new enkaParameters($row['params']);

            $newParams->set('fokus_koordinate', json_encode($koordinate));
            $params = $newParams->getString();
            sisplet_query("UPDATE srv_spremenljivka SET params='$params' WHERE id='$spr_id'");
        }
    }
    
     /**
     * Lokacija - 26
     * shrane v bazo string za fokus
     */
    function ajax_fokus_string_map(){
        if(isset($_POST['anketa']))
            $this->anketa = $_POST['anketa'];
        Common::getInstance()->Init($this->anketa);
        Common::getInstance()->updateEditStamp();
        
        if(isset($_POST['spr_id'])){
        
            $spr_id = $_POST['spr_id'];
            $string = $_POST['fokus'];

            $row = Cache::srv_spremenljivka($spr_id);
            $newParams = new enkaParameters($row['params']);

            $newParams->set('fokus_mape', json_encode($string));
            $params = $newParams->getString();
            sisplet_query("UPDATE srv_spremenljivka SET params='$params' WHERE id='$spr_id'");
        }
    }
    
     /**
     * Lokacija - 26 - podtip 3 choose location
     * shrane v nov marker - vrednost
     */
    function ajax_save_marker(){
        if(isset($_POST['anketa']))
            $this->anketa = $_POST['anketa'];
        Common::getInstance()->Init($this->anketa);
        Common::getInstance()->updateEditStamp();
        
        if(isset($_POST['spr_id'])){
        
            $spr_id = $_POST['spr_id'];
            $add = $_POST['address'];
            $lat = $_POST['lat'];
            $lng = $_POST['lng'];

            $v = new Vprasanje();
            $v->spremenljivka = $spr_id;
            $vrednost = $v->vrednost_new('');
			
            Common::prestevilci($spr_id);

            //last decimals of coordiates are not exact same in database, because float in mySql is not precise - practical variations are minimal
            $sql = sisplet_query("INSERT INTO srv_vrednost_map (spr_id, vre_id, address, lat, lng, overlay_type) "
                    . "VALUES ('$spr_id', '$vrednost', '$add', '$lat', '$lng', 'marker')", "id");

            echo $vrednost;
        }
    }
    
    /**
     * Lokacija - 26 - podtip 3 choose location
     * shrane v nov shape - ni vrednost, samo info
     */
    function ajax_save_polyline(){
        if(isset($_POST['anketa']))
            $this->anketa = $_POST['anketa'];
        Common::getInstance()->Init($this->anketa);
        Common::getInstance()->updateEditStamp();
        
        if(isset($_POST['spr_id'])){
        
            $spr_id = $_POST['spr_id'];
            $overlay_id = $_POST['overlay_id'];
            $path = $_POST['path'];
            
            //create row for each point of path of line
            foreach ($path as $point) {
                $sql = sisplet_query("INSERT INTO srv_vrednost_map (spr_id, vre_id, lat, lng, vrstni_red, overlay_id, overlay_type) "
                    . "VALUES ('$spr_id', '-1', '".$point['lat']."', '".$point['lng']."', '".$point['vrstni_red']."', '$overlay_id', 'polyline')");
            }           
        }
    }
    
    /**
     * Lokacija - 26 - podtip 3 choose location
     * izvede query za brisanje shape iz baze - tabela srv_vrednost_map
     * @param type $spr_id
     * @param type $overlay_id
     */
    function deleteShapeQuery($spr_id, $overlay_id){
        //first dele old line
        $sql = sisplet_query("DELETE FROM srv_vrednost_map WHERE spr_id='$spr_id' AND overlay_id='$overlay_id'");  
    }
    
    /**
     * Lokacija - 26 - podtip 3 choose location
     * shrane spremenjen shape - ni vrednost, samo info
     */
    function ajax_edit_polyline(){
        if(isset($_POST['anketa']))
            $this->anketa = $_POST['anketa'];
        Common::getInstance()->Init($this->anketa);
        Common::getInstance()->updateEditStamp();
        
        if(isset($_POST['spr_id'])){
        
            $spr_id = $_POST['spr_id'];
            $overlay_id = $_POST['overlay_id'];
            $path = $_POST['path'];
            
            //first dele old line
            $this -> deleteShapeQuery($spr_id, $overlay_id);
            
            //create new line
            foreach ($path as $point) {
                $sql = sisplet_query("INSERT INTO srv_vrednost_map (spr_id, vre_id, lat, lng, vrstni_red, overlay_id, overlay_type) "
                    . "VALUES ('$spr_id', '-1', '".$point['lat']."', '".$point['lng']."', '".$point['vrstni_red']."', '$overlay_id', 'polyline')");
            }           
        }
    }
    
    /**
     * Lokacija - 26 - podtip 3 choose location
     * izbrise shape - ni vrednost, samo info
     */
    function ajax_delete_polyline(){
        if(isset($_POST['anketa']))
            $this->anketa = $_POST['anketa'];
        Common::getInstance()->Init($this->anketa);
        Common::getInstance()->updateEditStamp();
        
        if(isset($_POST['spr_id'])){
        
            $spr_id = $_POST['spr_id'];
            $overlay_id = $_POST['overlay_id'];
            
            $this -> deleteShapeQuery($spr_id, $overlay_id);
        }
    }
    
    /**
     * Lokacija - 26 - podtip 3 choose location
     * shrane naslov shapea - ni vrednost, samo info
     */
    function ajax_edit_naslov_polyline(){
        if(isset($_POST['anketa']))
            $this->anketa = $_POST['anketa'];
        Common::getInstance()->Init($this->anketa);
        Common::getInstance()->updateEditStamp();
        
        if(isset($_POST['spr_id'])){
        
            $spr_id = $_POST['spr_id'];
            $overlay_id = $_POST['overlay_id'];
            $address = $_POST['address'];
            
            $sql = sisplet_query("UPDATE srv_vrednost_map SET address='$address' WHERE spr_id='$spr_id' AND overlay_id='$overlay_id'");        
        }
    }
}

?>