<?php

/**
* 
* Nov, fullscreen nacin urejanja vprasanj
* 
*/

class Vprasanje {
	
	var $anketa;                // trenutna anketa
	var $spremenljivka;			// spremenljivka ki jo urejamo
	
	var $db_table = '';
	var $expanded = 0;
		
	/**
	* konstruktor
	* 
	* @param mixed $anketa
	* @return Vprasanje
	*/
	function __construct ($anketa = 0) {
		
		if (isset ($_GET['anketa']))
			$this->anketa = $_GET['anketa'];
		elseif (isset ($_POST['anketa'])) 
			$this->anketa = $_POST['anketa'];
		elseif ($anketa != 0) 
			$this->anketa = $anketa;
		
		SurveyInfo::getInstance()->SurveyInit($this->anketa);

		if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
			$this->db_table = '_active';
		
		if (SurveyInfo::getInstance()->getSurveyColumn('expanded') == 1)
			$this->expanded = 1;
	}
	
	/**
	* izrise ogrodje urejanja vprasanja
	* 
	*/
	function display () {
				
		$this->tabs();
		
		echo '<div id="vprasanje_edit">';
		$this->vprasanje_edit();
		echo '</div>';
		
		$this->edit_buttons();		
	}
	
	/**
	* izrise tabe pri urejanju vprasanja
	* 
	*/
	function tabs () {
		global $lang;
		global $admin_type;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$rows = SurveyInfo::getInstance()->getSurveyRow();
		
		echo '<div id="vprasanje_tabs">';
		
		echo '<a href="#" class="tab_link active" id="tab_link_0" onclick="vprasanje_tab(\''.$this->spremenljivka.'\', \'0\'); return false;">'.$lang['srv_osnovno'].'</a>';
				
		if ($this->spremenljivka > 0 && !Demografija::getInstance()->isDemografija($row['variable']) ) {
			
			// Tab napredno
			echo '<a href="#" class="tab_link" id="tab_link_1" onclick="vprasanje_tab(\''.$this->spremenljivka.'\', \'1\'); return false;">'.$lang['srv_napredno'].'</a>';
			
			// Tab pogoji
			echo '<a href="#" class="tab_link" id="tab_link_2" onclick="vprasanje_tab(\''.$this->spremenljivka.'\', \'2\'); return false;">'.$lang['srv_condition'].'</a>';
			
			// Tab validacija
			echo '<a href="#" class="tab_link" id="tab_link_7" onclick="vprasanje_tab(\''.$this->spremenljivka.'\', \'7\'); return false;">'.$lang['srv_validation'].'</a>';
		
			// Tab opomba
			echo '<a href="#" class="tab_link" id="tab_link_3" onclick="vprasanje_tab(\''.$this->spremenljivka.'\', \'3\'); return false;">'.$lang['srv_note'].($row['note']!=''?'*':'').'</a>';
					
			// Tab tracking sprememb na vprašanju
			if (($admin_type == 0 || $admin_type == 1) && $rows['vprasanje_tracking'] > 0){
				echo '<div class="tab_link_tracking">';
				echo '<a href="#" class="tab_link" id="tab_link_6" onclick="vprasanje_tab(\''.$this->spremenljivka.'\', \'6\'); return false;" title="'.$lang['hour_archive'].'"><span>'.$lang['hour_archive'].'</span></a>';
				echo '</div>';
			}
		} 
		else {
			
			$star = '';
			if ($this->spremenljivka == -1) {
				if ($rows['thread_intro']!=0 || $rows['intro_note']!='') $star = '*';
			} else {
				if ($rows['thread_concl']!=0 || $rows['concl_note']!='') $star = '*';
			}
			
			echo '<a href="#" class="tab_link" id="tab_link_3" onclick="vprasanje_tab(\''.$this->spremenljivka.'\', \'3\'); return false;">'.$lang['srv_note'].$star.'</a>';			
		}			
		
		echo '</div>';
	}
	
	/** 
	* urejanje vprasanja
	* 
	*/
	function vprasanje_edit () {
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		echo '<form name="vprasanje_edit" onsubmit="vprasanje_save(); return false;">';
		
		echo '<input type="hidden" name="anketa" value="'.$this->anketa.'" />';
		echo '<input type="hidden" name="spremenljivka" value="'.$this->spremenljivka.'" />';
		
		echo '<div id="tab_0" class="tab" style="display:block">';
		$this->vprasanje_osnovno();
		echo '</div>';
		
		if ($this->spremenljivka > 0) {
		
			echo '<div id="tab_1" class="tab" style="display:none">';
			$this->vprasanje_napredno();
			echo '</div>';
			
			echo '<div id="tab_2" class="tab" style="display:none">';
			$this->vprasanje_pogoji();
			echo '</div>';
			
			echo '<div id="tab_7" class="tab" style="display:none">';
			$this->vprasanje_validation();
			echo '</div>';
			
			echo '<div id="tab_6" class="tab" style="display:none">';
			$this->vprasanje_display_tracking();
			echo '</div>';
		}
		
		echo '<div id="tab_3" class="tab" style="display:none">';
		$this->vprasanje_komentarji();
		echo '</div>';
			
		echo '</form>';
		
		echo '<span id="vprasanje_edit_bottom_placeholder"></span>';
	}
	
	/** 
	* osnovno urejanje vprasanja
	* 
	*/
	function vprasanje_osnovno () {
		global $lang;
		global $site_url;
		
		//uvod
		if ($this->spremenljivka == -1) {
			$this->edit_uvod();
		
		//zakljucek
		} elseif ($this->spremenljivka == -2) {
			$this->edit_zakljucek();
		
		//statistika
		} elseif($this->spremenljivka == -3) {
			$this->edit_statistika();
		
		} else {
		
			$row = Cache::srv_spremenljivka($this->spremenljivka);
			$spremenljivkaParams = new enkaParameters($row['params']);
			
			// demografija
			if (Demografija::getInstance()->isDemografija($row['variable'])) {
				
				// variabla na vrhu in izbira druge demografije
				$this->variable();
				
				// izbira tipa demografije
				echo '<fieldset><legend>'.$lang['srv_question_type'].'</legend>';
				echo '<p><span class="title">'.$lang['srv_select_type'].':</span><span class="content"><select style="width:150px" name="tip" id="spremenljivka_tip_' . $row['id'] . '" size="1" spr_id="' . $row['id'] . '" onChange="change_demografija(\'' . $row['id'] . '\', $(this).val());" data-ajax="true">';
				
				foreach (Demografija::getInstance()->getSeznam($row['variable']) AS $variabla) {
					echo '<option value="'.Demografija::getInstance()->getSpremenljivkaID($variabla).'" '.($row['variable'] == $variabla?'selected':'').'>'.$variabla.'</option>';
				}
			
				echo '</select></span></p>';
				
				echo '<script type="text/javascript">';
				echo '$(document).ready(function() { ';
				echo '$("#spremenljivka_tip_' . $row['id'] . '").selectbox();'; // kreira custom dropdown z možnostjo predogleda vprašanja
				echo '});';
				echo '</script>';
				
				echo '<p><strong>'.$lang[$row['variable']].'</strong></p>';
				
				echo '</fieldset>';
				
				// reminder
				$this->edit_reminder();
			
			
            } 
            // navadno vprasanje
            else {
				
				// variabla na vrhu
				$this->variable(1);
				
				// prikaz vprasanja - postavitev
				if ( in_array($row['tip'], array(1, 2, 3, 6, 7, 16, 17, 20, 9, 26, 27)) ) {				
					$this->edit_subtip();	
				}
				
				// Nastavitev za postavitev texbox za besedilo, number, datum - vodoravno ob ali pod vprasanjem
  				if ( in_array($row['tip'], array(21, 7, 8)) ) {

                    // Ce nimamo slider-ja
                    if($row['tip'] != 7 || $row['ranking_k'] != 1){ 
                        $this->edit_orientation();
                    }
				}
				
				// datum-min/max date
				if ( in_array($row['tip'], array(8)) ) {
					$this->edit_date_range();
					$this->edit_date_withTime();
				}
                                
                // Lokacija
				if ( in_array($row['tip'], array(26)) ) {
					$this->edit_input_type_map();
					$this->fokus_mape();
					$this->userLocation();
					$this->markerPodvprasanje();
					$this->naslov_podvprasanja_map();
					$this->st_markerjev();
					$this->dodaj_SearchBox();
				}
							
				// pri nagovoru ni smiselno dodajat opozoril
				if ( ! in_array($row['tip'], array(5)) ) {
					$this->edit_reminder();
				}

				// opomba na vprasanje
				$this->edit_opomba();
				
				if (($row['tip'] == 7 || $row['tip'] == 20) && $row['ranking_k'] == 1) {

					echo '<fieldset><legend>'.$lang['slider_properties_note'].'</legend>';
					$this->edit_sliders_settings();			
					echo '</fieldset>';
				}
                            
                // Hotspot
				if( in_array($row['tip'], array(1, 2, 6, 17)) ){	
					$this->edit_hot_spot_settings();
				}
				
				if ( in_array($row['tip'], array(6, 16, 19, 20)) ) {

					if ($row['ranking_k'] != 1){
						echo '<fieldset><legend>'.$lang['srv_kategorije_odgovorov'].'</legend>';
					}
					if ( in_array($row['tip'], array(6, 16)) ) {
						$this->edit_grid_subtitle();
					}
					
					$this->edit_grid();
                    
                    // Uporaba label
                    $this->edit_column_labels();
                    
                    // Ponovi glavo v gridu vsakih x vrstic
                    if ( in_array($row['tip'], array(6, 16)) )
					    $this->edit_grid_repeat_header();
					
					$this->edit_drag_and_drop_new_look();
					
					if ($row['ranking_k'] != 1){
						echo '</fieldset>';
					}	
					
					// Merska lestvica (ordinalna ali nominalna)
					if ($row['tip'] == 6){
						$this->edit_skala_new();
					}					
					
					echo '<fieldset><legend>'.$lang['srv_manjkajoce_vrednosti2'].' '.Help::display('srv_missing_values').'</legend>';
					$this->edit_grid_missing();
					echo '</fieldset>';
				}

				// kategorije vprasanj
				if ( in_array($row['tip'], array(1, 2, 3, 6, 16, 17, 18, 19, 20, 24)) )  {
					
					if ( in_array($row['tip'], array(1, 2, 3, 17, 18)) ){
						echo '<fieldset class="kategorije_odgovorov" id="kategorije_odgovorov_'.$row['id'].'"><legend>'.$lang['srv_kategorije_odgovorov'].'</legend>';
                    }
                    else{
						echo '<fieldset><legend>'.$lang['srv_podvprasanja'].'</legend>';
					}
						
					if ( in_array($row['tip'], array(24)) ) {
						$this->edit_multiple_subtitle();
					}
									
					if ( in_array($row['tip'], array(6, 16, 19, 18, 20, 24)) ) {
						$this->edit_grid_width();
						
						if ( in_array($row['tip'], array(6, 16, 19, 20, 24)) ) {
							$this->edit_grid_align();
						}
					}
					
					// kategorije vprasanja - hitro dodajanje
					$this->edit_vrednost();
					
					// razvrscanje
                    if(!in_array($row['orientation'], [9,11])) {
                        $this->edit_random();
                    }
					
					if ( in_array($row['tip'], array(1, 2, 3, 6, 16)) ) {
						$this->edit_selectbox_size();
					}

					// Slikice namesto radio gumbov (smiley, thumbs up...) - $row['orientation'] == 9
					if(in_array($row['tip'], array(1, 2))){
						$this->edit_custom_picture_radio();
					}

                    if(in_array($row['tip'], array(1, 6))){
					    $this->edit_visual_analog_scale();
                    }

					
					// razvrscanje - moznosti
					if ( in_array($row['tip'], array(17)) ) {
						$this->edit_ranking_moznosti();
					}
					
					echo '</fieldset>';
				}
				
				// Merska lestvica (ordinalna ali nominalna)
				if ($row['tip'] == 1 || $row['tip'] == 3){
					$this->edit_skala_new();
				}
				// manjkajoce vrednosti
				if ($row['tip'] <= 3) {
						echo '<fieldset><legend>'.$lang['srv_manjkajoce_vrednosti2'].' '.Help::display('srv_missing_values').'</legend>';
						$this->edit_missing();
						echo '</fieldset>';
				}
				
				// editiranje vrednosti pri datumu
				if ( in_array($row['tip'], array(8)) ) {

					echo '<fieldset><legend>'.$lang['srv_manjkajoce_vrednosti2'].' '.Help::display('srv_missing_values').'</legend>';					
                    
                    $this->edit_vrednost_datum();
					
					// naknaden prikaz missinga ne vem ob opozorilu (samo ce imamo vklopljeno opozorilo in missing ne vem)
					$this->edit_alert_show_missing();
					                    
					echo '</fieldset>';
				}
											
				
				
				if ( in_array($row['tip'], array(7, 19, 20, 21)) ) {

                    $captcha = ($spremenljivkaParams->get('captcha') ? $spremenljivkaParams->get('captcha') : 0);

                    //ce ni elektronski podpis, upload ali captcha
                    if($row['signature'] != 1 && $row['upload'] < 1 && $captcha != 1){
                        $displayFieldset = 'display: block';
                    }
                    else{
                        $displayFieldset = 'display: none';
                    }

                    echo '<fieldset style="'.$displayFieldset.'" class="kategorije_odgovorov" id="kategorijeOdgovorov_'.$this->spremenljivka.'"><legend>'.$lang['srv_kategorije_odgovorov'].'</legend>';
                    
                    if ( in_array($row['tip'], array(21)) ) {
                        $this->edit_textboxes();	
                    } 
                    
                    if ( in_array($row['tip'], array(7)) ) {
                        if ($row['ranking_k'] == 0){	//ce je izbrano stevilo in ne drsnik
                            $this->edit_num_size();
                            $this->edit_num_enota();
                        }
                    }
                    
                    if ($row['ranking_k'] == 0){	//ce je izbrano stevilo in ne drsnik
                        $this->edit_width();
                    }
                    
                    echo '</fieldset>';


                    // Poseben segment za upload datoteke
                    if($row['upload'] == 1){
                        $displayFieldset = 'display: block';
                    }
                    else{
                        $displayFieldset = 'display: none';
                    }

                    echo '<fieldset style="'.$displayFieldset.'" class="upload_info"><legend>'.$lang['srv_vprasanje_upload_limit_title'].' '.Help::display('srv_upload_limit').'</legend>';
                    echo '<p>'.$lang['srv_vprasanje_upload_limit'].'</p>';
                    echo '<p>'.$lang['srv_vprasanje_upload_limit_type'].'</p>';
                    echo '</fieldset>';
                }
    
				// manjkajoce vrednosti - besedilo
				if ( in_array($row['tip'], array(21)) ) {
					echo '<fieldset><legend>'.$lang['srv_manjkajoce_vrednosti2'].' '.Help::display('srv_missing_values').'</legend>';
					$this->edit_vrednost_besedilo();
					
					// naknaden prikaz missinga ne vem ob opozorilu (samo ce imamo vklopljeno opozorilo in missing ne vem)
					$this->edit_alert_show_missing();
					echo '</fieldset>';
				}
				
				// Sirina polja "drugo" (ce je v vprasanju)
				if (in_array($row['tip'], array(1,2,3,6,16,19,20,24)))
					$this->edit_other_field();
				
				if ( in_array($row['tip'], array(7, 18, 20)) ) {
				
					if ($row['ranking_k'] == 0){	//ce je izbrano stevilo in ne drsnik
						$this->edit_number();
					}
					
					if ($row['tip'] == 7) {
						echo '<fieldset><legend>'.$lang['srv_manjkajoce_vrednosti2'].' '.Help::display('srv_missing_values').'</legend>';
						$this->edit_vrednost_number();
						
						// naknaden prikaz missinga ne vem ob opozorilu (samo ce imamo vklopljeno opozorilo in missing ne vem)
                        $this->edit_alert_show_missing();

						echo '</fieldset>';
					}	
				}
					
				if ( in_array($row['tip'], array(22)) ) {
					$this->edit_compute();
				}
				
				if ( in_array($row['tip'], array(9)) ) {
					$this->edit_name_generator();
				}
				
				if ( in_array($row['tip'], array(7,18,20, 21)) ) {
					if ($row['ranking_k'] == 0){	//ce je izbrano stevilo in ne drsnik
						$this->edit_limit();
					}	
				}
				
				// Crta pod nagovorom
				if ($row['tip'] == 5) {
					$this->edit_nagovor_line();
				}
			}
		}	
	}
	
	function edit_sliders_settings(){
		global $lang;
		global $admin_type;
		global $default_grid_values; //privzete default vmesne opisne labele
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		
		$slider_handle = ($spremenljivkaParams->get('slider_handle') ? $spremenljivkaParams->get('slider_handle') : 0); //za checkbox
		$slider_window_number = ($spremenljivkaParams->get('slider_window_number') ? $spremenljivkaParams->get('slider_window_number') : 0); //za checkbox

		$slider_MinMaxNumLabelNew = ($spremenljivkaParams->get('slider_MinMaxNumLabelNew') ? $spremenljivkaParams->get('slider_MinMaxNumLabelNew') : 0);	
		$slider_MinMaxLabel = ($spremenljivkaParams->get('slider_MinMaxLabel') ? $spremenljivkaParams->get('slider_MinMaxLabel') : 0);		
		$slider_VmesneNumLabel = ($spremenljivkaParams->get('slider_VmesneNumLabel') ? $spremenljivkaParams->get('slider_VmesneNumLabel') : 0);
		$slider_VmesneDescrLabel = ($spremenljivkaParams->get('slider_VmesneDescrLabel') ? $spremenljivkaParams->get('slider_VmesneDescrLabel') : 0);
		$slider_VmesneCrtice = ($spremenljivkaParams->get('slider_VmesneCrtice') ? $spremenljivkaParams->get('slider_VmesneCrtice') : 0);
		$slider_handle_step = ($spremenljivkaParams->get('slider_handle_step') ? $spremenljivkaParams->get('slider_handle_step') : 1);
		$slider_MinLabel= ($spremenljivkaParams->get('slider_MinLabel') ? $spremenljivkaParams->get('slider_MinLabel') : "Minimum");
		$slider_MaxLabel= ($spremenljivkaParams->get('slider_MaxLabel') ? $spremenljivkaParams->get('slider_MaxLabel') : "Maximum");
		$slider_MinNumLabel = ($spremenljivkaParams->get('slider_MinNumLabel') ? $spremenljivkaParams->get('slider_MinNumLabel') : 0);
		$slider_MaxNumLabel = ($spremenljivkaParams->get('slider_MaxNumLabel') ? $spremenljivkaParams->get('slider_MaxNumLabel') : 100);
		$slider_MinNumLabelTemp = ($spremenljivkaParams->get('slider_MinNumLabelTemp') ? $spremenljivkaParams->get('slider_MinNumLabelTemp') : 0);
		$slider_MaxNumLabelTemp = ($spremenljivkaParams->get('slider_MaxNumLabelTemp') ? $spremenljivkaParams->get('slider_MaxNumLabelTemp') : 100);
		
		$slider_NumofDescrLabels = ($spremenljivkaParams->get('slider_NumofDescrLabels') ? $spremenljivkaParams->get('slider_NumofDescrLabels') : 5);
		$slider_DescriptiveLabel_defaults = ($spremenljivkaParams->get('slider_DescriptiveLabel_defaults') ? $spremenljivkaParams->get('slider_DescriptiveLabel_defaults') : 0);
		
		$slider_StevLabelPodrocij = ($spremenljivkaParams->get('slider_StevLabelPodrocij') ? $spremenljivkaParams->get('slider_StevLabelPodrocij') : 3);
		
		$slider_nakazi_odgovore = ($spremenljivkaParams->get('slider_nakazi_odgovore') ? $spremenljivkaParams->get('slider_nakazi_odgovore') : 0); //za checkbox
		$slider_labele_podrocij = ($spremenljivkaParams->get('slider_labele_podrocij') ? $spremenljivkaParams->get('slider_labele_podrocij') : 0); //za checkbox
		
		$displayDescriptiveLabels = ($slider_VmesneDescrLabel == 0) ? ' style="display:none;"' : '';
		$displayStevLabelPodrocij = ($slider_labele_podrocij == 0) ? ' style="display:none;"' : '';
		
		$disable_slider_handle_hidden = ($slider_handle == 0) ? 'disabled' : '';
		$disable_slider_MinMaxNumLabelNew_hidden = ($slider_MinMaxNumLabelNew == 0) ? 'disabled' : '';
		$disable_slider_VmesneCrtice_hidden = ($slider_VmesneCrtice == 1) ? 'disabled' : '';
		//$disable_slider_VmesneCrtice = ($slider_VmesneCrtice == 0) ? 'disabled' : '';
		
		$disable_slider_VmesneDescrLabel_hidden = ($slider_VmesneDescrLabel == 1) ? 'disabled' : '';
		$disable_slider_VmesneNumLabel_hidden = ($slider_VmesneNumLabel == 1) ? 'disabled' : '';
		$disable_slider_MinMaxLabel_hidden = ($slider_MinMaxLabel == 1) ? 'disabled' : '';
		$disable_slider_window_number_hidden = ($slider_window_number == 0) ? 'disabled' : '';
		$disable_slider_handle_step = ($slider_VmesneDescrLabel == 1) ? 'disabled' : '';
		
		$disable_slider_nakazi_odgovore_hidden = ($slider_nakazi_odgovore == 1) ? 'disabled' : '';		
		$disable_slider_labele_podrocij_hidden = ($slider_labele_podrocij == 1) ? 'disabled' : '';
		
		//za pravilno osivitev koraka sliderja
		if ($disable_slider_handle_step == 'disabled'){
			$slider_handle_step_opacity = 0.5;
		}else{
			$slider_handle_step_opacity = 1;
		}
		
		//za pravilno osivitev nastavitve za vmesne crtice
		if ($disable_slider_nakazi_odgovore_hidden == 'disabled'){
			$slider_VmesneCrtice_opacity = 0.5;
			$disable_slider_VmesneCrtice = 'disabled';
		}else{
			$slider_VmesneCrtice_opacity = 1;
			$disable_slider_VmesneCrtice = '';
		}
		
		//echo '<input type="hidden" name="MinMaxLabelsDefaultFlag" value="1" />';
				
		// Polje size moramo imeti - drugace se pobrise vse iz srv_vrednost in se ne shranjuje vec
		echo '<input type="hidden" value="1" name="size" id="num_size">';		
		
		//Drsna rocica
		echo '<label for="slider_handle_'.$this->spremenljivka.'"><div class="dropsliderhandle" >';	//drsna rocica
		echo '<p><span class="title" >'.$lang['slider_handle_note'].':</span>';
		//echo $slider_handle;
		echo '<span class="content">';
		echo '<input type="checkbox" value="0" name="slider_handle" '.( $slider_handle == 0 ? ' checked="checked"' : '') .' onChange="slider_checkbox_prop('.$this->spremenljivka.');" id="slider_handle_'.$this->spremenljivka.'">';

		//echo '<input type="hidden" value="1" name="slider_handle" id="slider_handle_hidden_'.$this->spremenljivka.'">';
		echo '<input '.$disable_slider_handle_hidden.' type="hidden" value="1" name="slider_handle" id="slider_handle_hidden_'.$this->spremenljivka.'">';

		//echo '<input type="checkbox" value="1" name="slider_handle" '.( $slider_handle == 1 ? ' checked="checked"' : '') .' onChange="slider_checkbox_prop('.$this->spremenljivka.');" id="slider_handle_'.$this->spremenljivka.'">';

		//echo '<input type="hidden" value="0" name="slider_handle" id="slider_handle_hidden_'.$this->spremenljivka.'">';

		echo '</span></p>';
		echo '</div></label>';
		
		//Stevilka nad izbrano tocko
		echo '<label for="slider_window_number_'.$this->spremenljivka.'"><div class="dropsliderwindownumber" >';
		echo '<p><span class="title" >'.$lang['slider_window_number_title'].':</span>';
		echo '<span class="content">';
		echo '<input type="checkbox" value="0" name="slider_window_number" '.( $slider_window_number == 0 ? ' checked="checked"' : '') .' onChange="slider_checkbox_prop('.$this->spremenljivka.');" id="slider_window_number_'.$this->spremenljivka.'">';
		echo '<input '.$disable_slider_window_number_hidden.' type="hidden" value="1" name="slider_window_number" id="slider_window_number_hidden_'.$this->spremenljivka.'">';
		//echo '<input '.$disable_slider_window_number_hidden.' value="1" name="slider_window_number" id="slider_window_number_hidden_'.$this->spremenljivka.'">';
		echo '</span></p>';
		echo '</div></label>';
		
		if ($admin_type == 0){
			//Nakazi mozne odgovore
			echo '<label for="slider_nakazi_odgovore_'.$this->spremenljivka.'"><div class="dropslidernakaziodgovore" >';
			echo '<p><span class="title" >'.$lang['srv_slider_nakazi_odgovore'].':</span>';
			echo '<span class="content">';
			echo '<input type="checkbox" value="1" name="slider_nakazi_odgovore" '.( $slider_nakazi_odgovore == 1 ? ' checked="checked"' : '') .' onChange="slider_checkbox_prop('.$this->spremenljivka.');" id="slider_nakazi_odgovore_'.$this->spremenljivka.'">';
			echo '<input '.$disable_slider_nakazi_odgovore_hidden.' type="hidden" value="0" name="slider_nakazi_odgovore" id="slider_nakazi_odgovore_hidden_'.$this->spremenljivka.'">';
			echo '</span></p>';
			echo '</div></label>';
		}
		
		
		
		
		//Korak drsnika
		$viewMinMaxNumLabels = ($slider_VmesneDescrLabel == 1) ? '; display:none;' : '';
		echo '<div class="dropsliderhandle_step_'.$this->spremenljivka.'" style="opacity: '.$slider_handle_step_opacity.''.$viewMinMaxNumLabels.'">';	//korak drsnika
		echo '<p><span class="title" >'.$lang['slider_handle_step_note'].':</span>';
		echo '<span class="content"><select name="slider_handle_step" id="slider_handle_step_'.$this->spremenljivka.'" '.$disable_slider_handle_step.'>';		
		for($i=1; $i<=10; $i++){
			echo '<option value="'.$i.'"'.($slider_handle_step == $i ? ' selected="true"' : '') . '>'.$i.'</option>';
		}
		echo '</select></span>';
		echo '</p>';
		echo '</div>';

		//Min in max vrednosti
		$viewMinMaxNumLabels = ($slider_VmesneDescrLabel == 1) ? ' style="display:none;"' : '';		
		echo '<div class="MinMaxNumLabels_'.$this->spremenljivka.'" '.$viewMinMaxNumLabels.'>';	//ureditev min in max stevilk
		echo '<p>';		
		echo $lang['srv_num_min'] . '<input type="text" name="slider_MinNumLabel" id="slider_MinNumLabel_'.$this->spremenljivka.'"  value="' . $slider_MinNumLabel . '" size="8" onkeyup="checkNumber(this, 6, 2); sliderCopytoMinNumLabelTemp('.$this->spremenljivka.');"></input> ';
		echo $lang['srv_num_limit'] . '<input type="text" name="slider_MaxNumLabel"  id="slider_MaxNumLabel_'.$this->spremenljivka.'" value="' . $slider_MaxNumLabel . '" size="8" onkeyup="checkNumber(this, 6, 2); sliderCopytoMaxNumLabelTemp('.$this->spremenljivka.');"></input> ';
		echo '</p>';
		echo '</div>';
		
		//temp Min in Max style="display: none" 
		echo '<div class="MinMaxNumLabelsTemp" style="display: none" >';	//ureditev temp min in max stevilk, za vrnitev stevilskih label, ki so bile prej izbrane
		echo '<p>';
		echo $lang['srv_num_min'] . '<input type="text" name="slider_MinNumLabelTemp" id="slider_MinNumLabelTemp_'.$this->spremenljivka.'"  value="' . $slider_MinNumLabelTemp . '" size="8" onkeyup="checkNumber(this, 6, 2);"></input> ';
		echo $lang['srv_num_limit'] . '<input type="text" name="slider_MaxNumLabelTemp"  id="slider_MaxNumLabelTemp_'.$this->spremenljivka.'" value="' . $slider_MaxNumLabelTemp . '" size="8" onkeyup="checkNumber(this, 6, 2);"></input> ';
		echo '</p>';
		echo '</div>';
		//temp Min in Max - konec
		
		//Vmesne crtice
		echo '<label class="slider_VmesneCrtice_'.$this->spremenljivka.'" for="slider_VmesneCrtice_'.$this->spremenljivka.'"><div class="dropVmesneCrtice" style="opacity: '.$slider_VmesneCrtice_opacity.'">';	//ureditev prikazovanja in skrivanja vmesnih crtic
		echo '<p><span class="title" >'.$lang['slider_VmesneCrtice_note'].':</span>';
		echo '<span class="content">';
		echo '<input '.$disable_slider_VmesneCrtice.' type="checkbox" value="1" name="slider_VmesneCrtice" '.( $slider_VmesneCrtice == 1 ? ' checked="checked"' : '') .' onChange="slider_checkbox_prop('.$this->spremenljivka.');" id="slider_VmesneCrtice_'.$this->spremenljivka.'">';
		echo '<input '.$disable_slider_VmesneCrtice_hidden.' type="hidden" value="0" name="slider_VmesneCrtice" id="slider_VmesneCrtice_hidden_'.$this->spremenljivka.'">';
		echo '</span></p>';
		echo '</div></label>';
		
		//Stevilske labele
		//echo '<p><span class="title" >'.$lang['slider_NumLabel_note'].':</span></p>';
		
		echo '<div class="dropNumLabelNew">';	

		echo '<label for="slider_VmesneNumLabel_'.$this->spremenljivka.'"><p><span class="title" >'.$lang['slider_NumLabel_note'].':</span>';	//ureditev prikazovanja in skrivanja stevilskih vmesnih label
		echo '<span class="content">';
		echo '<input type="checkbox" value="1" name="slider_VmesneNumLabel" '.( $slider_VmesneNumLabel == 1 ? ' checked="checked"' : '') .' onChange="slider_checkbox_prop('.$this->spremenljivka.');" id="slider_VmesneNumLabel_'.$this->spremenljivka.'">';
		echo '<input '.$disable_slider_VmesneNumLabel_hidden.' type="hidden" value="0" name="slider_VmesneNumLabel" id="slider_VmesneNumLabel_hidden_'.$this->spremenljivka.'">';
		echo '</span></p></label>';
		
		echo '<label for="slider_MinMaxNumLabelNew_'.$this->spremenljivka.'"><p><span class="title" >'.$lang['slider_MinMaxNumLabel_note'].':</span>';
		echo '<span class="content">';	//ureditev prikazovanja in skrivanja stevilskih label za min in max na sliderju s pips-i
		echo '<input type="checkbox" value="0" name="slider_MinMaxNumLabelNew" '.( $slider_MinMaxNumLabelNew == 0 ? ' checked="checked"' : '') .' onChange="slider_checkbox_prop('.$this->spremenljivka.');" id="slider_MinMaxNumLabelNew_'.$this->spremenljivka.'">';
		echo '<input '.$disable_slider_MinMaxNumLabelNew_hidden.' type="hidden" value="1" name="slider_MinMaxNumLabelNew" id="slider_MinMaxNumLabelNew_hidden_'.$this->spremenljivka.'">';
		echo '</span></p></label>';

		echo '</div>';
		
		//Opisne labele		
		echo '<div class="dropDescriptiveLabel" >';

		echo '<label for="slider_MinMaxLabel_'.$this->spremenljivka.'"><p><span  class="title">'.$lang['slider_MinMaxLabel_note'].'</span></label>';
		echo '<span class="content">'; //ureditev prikazovanja in skrivanja opisnih label za min in max
		echo '<input type="checkbox" value="1" name="slider_MinMaxLabel" '.( $slider_MinMaxLabel == 1 ? ' checked="checked"' : '') .' onChange="slider_checkbox_prop('.$this->spremenljivka.');" id="slider_MinMaxLabel_'.$this->spremenljivka.'">';
		echo '<input '.$disable_slider_MinMaxLabel_hidden.' type="hidden" value="0" name="slider_MinMaxLabel" id="slider_MinMaxLabel_hidden_'.$this->spremenljivka.'">';
		echo '</span></p>';
		
		echo '<label for="slider_VmesneDescrLabel_'.$this->spremenljivka.'"><p><span  class="title">'.$lang['slider_VmesneLabel_note'].'</span></label>';	//ureditev prikazovanja in skrivanja opisnih vmesnih label
		echo '<span class="content">';
		echo '<input type="checkbox" value="1" name="slider_VmesneDescrLabel" '.( $slider_VmesneDescrLabel == 1 ? ' checked="checked"' : '') .' onChange="slider_checkbox_prop('.$this->spremenljivka.');" id="slider_VmesneDescrLabel_'.$this->spremenljivka.'">';
		echo '<input '.$disable_slider_VmesneDescrLabel_hidden.' type="hidden" value="0" name="slider_VmesneDescrLabel" id="slider_VmesneDescrLabel_hidden_'.$this->spremenljivka.'">';
		echo '</span></p>';
		
		echo '</div>';	
		
		// prikaz dropdowna za default vrednosti opisnih vmesnih label		
		echo '<p class="slider_DescriptiveLabel_defaults" '.$displayDescriptiveLabels.'>'; //echo '<p class="grid_defaults" '.$display.'>';
		echo '<span class="title">'.$lang['srv_defaultDescrLabel'].':</span>';
		echo '<span class="content"><select name="slider_DescriptiveLabel_defaults" id="slider_DescriptiveLabel_defaults_'.$this->spremenljivka.'" style="width:100px" onChange="slider_defaultDescrLabels_value('.$this->spremenljivka.', this.value); switchSliderOpisneLabeleEditMode('.$this->spremenljivka.', \'\');">';
		//echo '<span class="content"><select name="slider_DescriptiveLabel_defaults" id="slider_DescriptiveLabel_defaults_'.$this->spremenljivka.'" style="width:100px" onChange="slider_defaultDescrLabels_value('.$this->spremenljivka.', this.value);">';
		echo '<option value="0">'.$lang['s_without'].'</option>';						
		foreach($default_grid_values AS $key => $value){
			//echo '<option value="'.$key.'">'.$value['name'].'</option>';
			echo '<option value="'.$key.'"'.($slider_DescriptiveLabel_defaults == $key ?' selected':'').'>'.$value['name'].'</option>';
		}
		echo '</select></span>';
		echo '</p>';
		
		echo '<div class="dropNumofDescrLabels" '.$displayDescriptiveLabels.'>';			
		echo '<p><span class="title" >'.$lang['srv_NumDescrLabels_note'].':</span>';
		echo '<span class="content"><select name="slider_NumofDescrLabels" id="slider_NumofDescrLabels_'.$this->spremenljivka.'" onChange="slider_checkbox_prop('.$this->spremenljivka.');">';
		echo '<option value="2"'.($slider_NumofDescrLabels =='2'?' selected':'').'>2</option>';
		echo '<option value="3"'.($slider_NumofDescrLabels =='3'?' selected':'').'>3</option>';
		echo '<option value="4"'.($slider_NumofDescrLabels =='4'?' selected':'').'>4</option>';
		echo '<option value="5"'.($slider_NumofDescrLabels =='5'?' selected':'').'>5</option>';
		echo '<option value="6"'.($slider_NumofDescrLabels =='6'?' selected':'').'>6</option>';
		echo '<option value="7"'.($slider_NumofDescrLabels =='7'?' selected':'').'>7</option>';
		echo '<option value="8"'.($slider_NumofDescrLabels =='8'?' selected':'').'>8</option>';
		echo '<option value="9"'.($slider_NumofDescrLabels =='9'?' selected':'').'>9</option>';
		echo '<option value="10"'.($slider_NumofDescrLabels =='10'?' selected':'').'>10</option>';
		echo '<option value="11"'.($slider_NumofDescrLabels =='11'?' selected':'').'>11</option>';
		echo '</select></span>';
		echo '</p>';
		echo '</div>';
		
		//Labele podrocij
		echo '<label for="slider_labele_podrocij_'.$this->spremenljivka.'"><div class="check_slider_labele_podrocij" >';
		echo '<p><span class="title" >'.$lang['srv_slider_labele_podrocij'].':</span>';
		echo '<span class="content">';
		echo '<input type="checkbox" value="1" name="slider_labele_podrocij" '.( $slider_labele_podrocij == 1 ? ' checked="checked"' : '') .' onChange="slider_checkbox_prop('.$this->spremenljivka.');" id="slider_labele_podrocij_'.$this->spremenljivka.'">';
		echo '<input '.$disable_slider_labele_podrocij_hidden.' type="hidden" value="0" name="slider_labele_podrocij" id="slider_labele_podrocij_hidden_'.$this->spremenljivka.'">';
		echo '</span></p>';
		echo '</div></label>';
		
		//Labele podrocij - prikaz dropdowna z moznimi stevilkami podrocij
		echo '<div class="drop_slider_stevilo_label_podrocij" '.$displayStevLabelPodrocij.'>';			
		echo '<p><span class="title" >'.$lang['srv_slider_stevilo_label_podrocij'].':</span>';
		echo '<span class="content"><select name="slider_StevLabelPodrocij" id="slider_StevLabelPodrocij_'.$this->spremenljivka.'" onChange="slider_checkbox_prop('.$this->spremenljivka.');">';
		echo '<option value="1"'.($slider_StevLabelPodrocij =='1'?' selected':'').'>1</option>';
		echo '<option value="2"'.($slider_StevLabelPodrocij =='2'?' selected':'').'>2</option>';
		echo '<option value="3"'.($slider_StevLabelPodrocij =='3'?' selected':'').'>3</option>';
		echo '<option value="4"'.($slider_StevLabelPodrocij =='4'?' selected':'').'>4</option>';
		echo '<option value="5"'.($slider_StevLabelPodrocij =='5'?' selected':'').'>5</option>';
		echo '<option value="6"'.($slider_StevLabelPodrocij =='6'?' selected':'').'>6</option>';
		echo '<option value="7"'.($slider_StevLabelPodrocij =='7'?' selected':'').'>7</option>';
		echo '<option value="8"'.($slider_StevLabelPodrocij =='8'?' selected':'').'>8</option>';
		echo '<option value="9"'.($slider_StevLabelPodrocij =='9'?' selected':'').'>9</option>';
		echo '<option value="10"'.($slider_StevLabelPodrocij =='10'?' selected':'').'>10</option>';
		echo '</select></span>';
		echo '</p>';
		echo '</div>';
		
	}
	
	function edit_hot_spot_settings(){
		global $lang;
		global $admin_type;
		global $default_grid_values; //privzete default vmesne opisne labele
		
		SurveySetting::getInstance()->Init($this->anketa);
		SurveyInfo::getInstance()->SurveyInit($this->anketa);
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		$hotspot_image = ($spremenljivkaParams->get('hotspot_image') ? $spremenljivkaParams->get('hotspot_image') : "");
		$hotspot_region_visibility_option = ($spremenljivkaParams->get('hotspot_region_visibility_option') ? $spremenljivkaParams->get('hotspot_region_visibility_option') : 0);	//za checkbox
		$hotspot_region_visibility = ($spremenljivkaParams->get('hotspot_region_visibility') ? $spremenljivkaParams->get('hotspot_region_visibility') : 1);	//za radio "vedno" in "ob mouseover"
		$hotspot_tooltips_option = ($spremenljivkaParams->get('hotspot_tooltips_option') ? $spremenljivkaParams->get('hotspot_tooltips_option') : 0);
		$hotspot_region_color = ($spremenljivkaParams->get('hotspot_region_color') ? $spremenljivkaParams->get('hotspot_region_color') : "");
		$hotspot_visibility_color = ($spremenljivkaParams->get('hotspot_visibility_color') ? $spremenljivkaParams->get('hotspot_visibility_color') : "");
		$disable_hotspot_region_visibility_option_hidden = ($hotspot_region_visibility_option == 1) ? 'disabled' : '';
		$spr_id = $row['id'];
		
		//prikazovanje/skrivanje nastavitev za osvetljevanje
		if($hotspot_region_visibility_option){
			$hotspot_visibility_display = 'style=""';
        }
        else{
			$hotspot_visibility_display = 'style="display:none"';
		}
		//prikazovanje/skrivanje nastavitev za osvetljevanje - konec
		
		//preveri, ce je kaksno obmocje shranjeno v bazi
		$sqlR = sisplet_query("SELECT region_index, vre_id, region_name FROM srv_hotspot_regions WHERE spr_id = $spr_id");
		
		if($row['tip'] == 1 || $row['tip'] == 2){
			$enota_orientation = $row['orientation'];			
        }
        else if($row['tip'] == 6){
			$enota_orientation = $row['enota'];
        }
        else if($row['tip'] == 17){
			$enota_orientation = $row['design'];
		}
 		?>
		<script>
 			$(document).ready(function(){							
				//show_hot_spot_settings (<?=$row['id']?>, <?=$row['enota']?>, <?=$row['tip']?>, '<?=$hotspot_image?>');
				show_hot_spot_settings (<?=$row['id']?>, <?=$enota_orientation?>, <?=$row['tip']?>, '<?=$hotspot_image?>');
				//init_colorPicker(<?=$row['id']?>, 'region');	//init za region
				//init_colorPicker(<?=$row['id']?>, 'visibility');	//init za visibility
				init_colorPicker(<?=$row['id']?>);
			});			
		</script>

		<?
		
		$display_regions_menu = ( ($row['tip'] == 6 && $row['enota'] == 10) ||  ($row['tip'] == 1 && $row['orientation'] == 10) || ($row['tip'] == 17 && $row['design'] == 3) ) ? '' : 'style="display:none;"';

		if($row['tip'] == 6 || $row['tip'] == 17){	//ce je radio grid ali razvrscanje, pokazi naslov kot "Obmocja"
			echo '<fieldset id="hot_spot_fieldset_'.$row['id'].'" '.$display_regions_menu.'><legend>'.$lang['srv_hot_spot_regions_menu'].'</legend>';
        }
        elseif($row['tip'] == 1 || $row['tip'] == 2){	//ce je radio ali checkbox, pokazi naslov kot "Obmocja - Kategorije odgovorov"
			echo '<fieldset id="hot_spot_fieldset_'.$row['id'].'" '.$display_regions_menu.'><legend>'.$lang['srv_hot_spot_regions_menu'].' - '.$lang['srv_kategorije_odgovorov'].'</legend>';
		}
			
        //Sporocilo ob odsotnosti slike
        echo '<p id="hotspot_message"><span class="title" >'.$lang['srv_hotspot_message'].'</span></p>';	

        if (mysqli_num_rows($sqlR) != 0){
            //pokazi shranjena obmocja
            while ($rowR = mysqli_fetch_array($sqlR)) {					
                echo '<div id="hotspot_region_'.$rowR['region_index'].'" class="hotspot_region"><div id="hotspot_region_name_'.$rowR['region_index'].'" vre_id="'.$rowR['vre_id'].'" region_index = "'.$rowR['region_index'].'" class="hotspot_vrednost_inline" contenteditable="true">'.$rowR['region_name'].'</div><span class="faicon edit2 inline_hotspot_edit_region"></span><span class="faicon delete_circle icon-orange_link inline_hotspot_delete_region"></span><br /></div>';
            }
        }

        
        //Dodajanje območja - gumb
        echo '<p><span class="title" ><button id="hot_spot_regions_add_button" type="button" onclick=" hotspot_edit_regions('.$row['id'].', 0)">'.$lang['srv_hot_spot_regions'].'</button></span></p>';
        
        //Izbira barve izbranega obmocja
        //if($row['tip'] != 6)	//ce ni grid, torej radio ali checkbox dodaj nastavitev za barvo izbranega obmocja
        if($row['tip'] != 6 && $row['tip'] != 17)	//ce ni grid in ni razvrscanje, torej radio ali checkbox dodaj nastavitev za barvo izbranega obmocja
        {
            if ($hotspot_region_color == '') {
                $value = '#000000';
                //echo '<span class="title">'.$lang['srv_hotspot_region_color_text'].': <a href="#" onclick="$(\'#color-region-'.$row['id'].'\').show(); $(this).parent().hide(); return false;" title="'.$lang['edit4'].'">'.$lang['srv_te_default'].' <span class="sprites edit"></span></a></span>';
                echo '<span class="title">'.$lang['srv_hotspot_region_color_text'].':<span id="help_hotspot_region_color" class="spaceLeft">'.Help::display('srv_hotspot_region_color').' </span> <a href="#" onclick="$(\'#color-region-'.$row['id'].'\').show(); $(this).parent().hide(); return false;" title="'.$lang['edit4'].'">'.$lang['srv_te_default'].' <span class="faicon edit"></span></a></span>';
            }else{
                $value = $hotspot_region_color;
            }
            
            echo '<span class="title" id="color-region-'.$row['id'].'" '.($hotspot_region_color==''?'style="display:none;"':'').'>'.$lang['srv_hotspot_region_color_text'].': <span id="help_hotspot_region_color" class="spaceLeft">'.Help::display('srv_hotspot_region_color').' </span>';
            echo '<input type="text" id="color-region'.$row['id'].'" class="colorwell auto-save" name="hotspot_region_color" value="'.$value.'" data-id="'.$row['id'].'" data-type="'.$type.'" >';
            echo '</span>';
            
            //echo '<div id="picker"></div>';
        }
        //Izbira barve izbranega obmocja - konec
        
        //Regions visibility options **********************************************
                        
        //checkbox za "Osvetljevanje"
        echo '<label for="hotspot_region_visibility_options_' . $row['id'] . '"><div class="hotspot_region_visibility_option_class">';
        //echo '<div class="hotspot_region_visibility_option_class">';
        echo '<p><span class="title" >'.$lang['srv_hotspot_visibility_options_title'].':<span id="help_hotspot_visibility" class="spaceLeft">'.Help::display('srv_hotspot_visibility').' </span></span>';	//vprasajcek za help ob osvetilitvi
        echo '<span class="content">';
        echo '<input type="checkbox" value="1" name="hotspot_region_visibility_option" '.( $hotspot_region_visibility_option == 1 ? ' checked="checked"' : '') .' onChange="hotspot_region_visibility_option_checkbox_prop('.$row['id'].');" id="hotspot_region_visibility_options_' . $row['id'] . '">';
        echo '<input '.$disable_hotspot_region_visibility_option_hidden.' type="hidden" value="0" name="hotspot_region_visibility_option" id="hotspot_region_visibility_option_'.$row['id'].'">';
        echo '</span></p>';
        echo '</div></label>';
        //echo '</div>';
        //checkbox za "Osvetljevanje" - konec
        
        //radio za nastavitve osvetljevanja (vedno, ob mouseover)
        echo '<p id="hotspot_region_visibility_'.$row['id'].'" '.$hotspot_visibility_display.'>';
        echo '<input type="radio" name="hotspot_region_visibility" id="hotspot_region_visibility_0" value="1" '.(($hotspot_region_visibility == 1 || $hotspot_region_visibility == -1) ? ' checked="checked" ' : '').' onClick="" /><label for="hotspot_region_visibility_0" class="spaceRight">'.$lang['srv_hotspot_visibility_options_4'].'</label>';		
        echo '<input type="radio" name="hotspot_region_visibility" id="hotspot_region_visibility_1" value="2" '.(($hotspot_region_visibility == 2) ? ' checked="checked" ' : '').' onClick="" /><label for="hotspot_region_visibility_1">'.$lang['srv_hotspot_visibility_options_5'].'</label>';
        //echo '</p>';
        //radio za nastavitve osvetljevanja (vedno, ob mouseover) - konec
        
        //Izbira barve osvetljevanja obmocja
        if ($hotspot_visibility_color == '') {
            $value = '#000000';
            echo '<br /><span class="title">'.$lang['srv_hotspot_visibility_color_text'].': <span id="help_hotspot_visibility_color" class="spaceLeft">'.Help::display('srv_hotspot_visibility_color').' </span> <a href="#" onclick="$(\'#color-visibility-'.$row['id'].'\').show(); $(this).parent().hide(); return false;" title="'.$lang['edit4'].'">'.$lang['srv_te_default'].' <span class="faicon edit"></span></a></span>';
        }else{
            $value = $hotspot_visibility_color;	
        }
        
        echo '<br /><span class="title" id="color-visibility-'.$row['id'].'" '.($hotspot_visibility_color==''?'style="display:none;"':'').'>'.$lang['srv_hotspot_visibility_color_text'].': <span id="help_hotspot_visibility_color" class="spaceLeft">'.Help::display('srv_hotspot_visibility_color').' </span>';
        echo '<input type="text" id="color-visibility'.$row['id'].'" class="colorwell auto-save" name="hotspot_visibility_color" value="'.$value.'" data-id="'.$row['id'].'" data-type="'.$type.'" >';
        echo '</span>';
        
        echo '<div id="picker"></div>';
        //Izbira barve osvetljevanja obmocja - konec
        
        echo '</p>';
            
        //Regions visibility options - konec	****************************************************************
        
        //Tooltips options
        if($row['tip'] == 1 || $row['tip'] == 2){	//ce je radio ali checkbox
            $srv_hotspot_tooltip = 'srv_hotspot_tooltip';
        }
        else if($row['tip'] == 6 ||$row['tip'] == 17){
            $srv_hotspot_tooltip = 'srv_hotspot_tooltip_grid';
        }

        echo '<p><span class="title">'.$lang['srv_hotspot_tooltips_options_title'].':<span id="help_hotspot_namig" class="spaceLeft">'.Help::display($srv_hotspot_tooltip).' </span></span>';
        echo '<span class="title"><select id="hotspot_tooltips_options_' . $row['id'] . '" spr_id="'.$row['id'].'" name="hotspot_tooltips_option" onChange="">';
            echo '<option value="0" '.(($hotspot_tooltips_option == 0) ? ' selected="true" ' : '').'>'.$lang['srv_hotspot_tooltips_options_0'].'</option>';
            if($row['tip'] == 1 || $row['tip'] == 2){	//ce je radio ali checkbox
                echo '<option value="1" '.(($hotspot_tooltips_option == 1) ? ' selected="true" ' : '').'>'.$lang['srv_hotspot_tooltips_options_1'].'</option>';
            }
            //if($row['tip'] == 6){	//ce je radio grid
            if($row['tip'] == 6 ||$row['tip'] == 17){	//ce je radio grid ali razvrscanje
                echo '<option value="2" '.(($hotspot_tooltips_option == 2) ? ' selected="true" ' : '').'>'.$lang['srv_hotspot_tooltips_options_2'].'</option>';
            }

        echo '</select>';
        echo '</span></p>';
				
		echo '</fieldset>';
	}
	
	function edit_orientation() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		
		if($row['signature'] != 1){	//ce ni elektronski podpis
			$displayOrientation = 'display: block';
		}else{
			$displayOrientation = 'display: none';
		}
			//echo '<p>';
			echo '<p id="orientation_'.$this->spremenljivka.'" style="'.$displayOrientation.' ">';
			echo '<span class="title" >'.$lang['srv_orientacija'].': </span>';
			
			//echo (int)$row['orientation'];
			echo '<span class="content"><select name="orientation" id="spremenljivka_orientation_' . $row['id'] . '" spr_id="'.$row['id'].'">';
			
			echo '<option value="1"' . (($row['orientation'] == 1 || $row['orientation'] == 2) ? ' selected="true"' : '') . '>'.$lang['srv_orientacija_horizontalna_2'].'</option>';
			//echo '<option value="1"' . ($row['orientation'] == 1 ? ' selected="true"' : '') . '>'.$lang['srv_orientacija_vertikalna'].'</option>';
			echo '<option value="0"' . ($row['orientation'] == 0 ? ' selected="true"' : '') . '>'.$lang['srv_orientacija_horizontalna'].'</option>';
			if($row['tip'] == 21)
				echo '<option value="3"' . (($row['orientation'] == 3 || $row['orientation'] == 3) ? ' selected="true"' : '') . '>'.$lang['srv_orientacija_vertikalna'].'</option>';
			
			echo '</select></span>';
			
			echo '</p>';
	}
	
	
	function edit_date_range() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		$newParams = new enkaParameters($row['params']);
		$min = $newParams->get('date_range_min');
		$max = $newParams->get('date_range_max');
		
		echo '<p>';
		echo '<span class="title" >'.$lang['srv_vprasanje_date_range_min'].': </span>'.Help::display('edit_date_range');
		echo '<span class="content">';
		echo '<input type="number" value="'.$min.'"  name="date_range_min" id="date_range_min_' . $row['id'] . '" spr_id="'.$row['id'].'" size="4" style="margin-top:-12px;" />';
		echo '</span>';
		echo '</p>';

		echo '<p>';
		echo '<span class="title" >'.$lang['srv_vprasanje_date_range_max'].': </span>'.Help::display('edit_date_range');
		echo '<span class="content">';
		echo '<input type="number" min="0" value="'.$max.'" name="date_range_max" id="date_range_max_' . $row['id'] . '" spr_id="'.$row['id'].'" size="4" style="margin-top:-12px;" />';
		echo '</span>';
		echo '</p>';	
	}
	
	function edit_date_withTime() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		$newParams = new enkaParameters($row['params']);
		$withTime = $newParams->get('date_withTime');
		
		echo '<p>';
		echo '<span class="title"><label for="date_withTime_' . $row['id'] . '">'.$lang['srv_vprasanje_date_withTime'].'</label>: </span>';
		echo '<span class="content"><input type="hidden" name="date_withTime" value="0">';
		echo '<input type="checkbox" value="1" '.(($withTime == 1) ? 'checked' : '').' name="date_withTime" id="date_withTime_' . $row['id'] . '" spr_id="'.$row['id'].'" />';
		echo '</span>';
		echo '</p>';
	}
        
		
	/**
     * Tip multilokacija
     * 
	 * Doloci najvisjo stevilo dovoljenih vnosov - markerjev
	 * @global type $lang
	 */
	function st_markerjev() {
        global $lang;
        
        $row = Cache::srv_spremenljivka($this->spremenljivka);
        $newParams = new enkaParameters($row['params']);

        //$min = $newParams->get('date_range_min');
        $max = $newParams->get('max_markers') ? $newParams->get('max_markers') : 1;
            $input = $newParams->get('multi_input_type') ? $newParams->get('multi_input_type') : 'marker';
            
        echo '<p id="max_markers_map" '. (($row['enota'] == 2 && $input =='marker') ? '' : 'style="display: none;"').'>';
        echo '<span class="title">'.$lang['srv_vprasanje_max_marker_map'].': '.Help::display('srv_vprasanje_max_marker_map').'</span>';
        echo '<span class="content">';
                                
        //roleta
        echo '<select name="max_markers" id="max_markers_' . $row['id'] . '" spr_id="'.$row['id'].'">';
        echo '<option value="1" '.(($max == 1) ? ' selected="true" ' : '').'>1</option>';		
        echo '<option value="2" '.(($max == 2) ? ' selected="true" ' : '').'>2</option>';
                    echo '<option value="3" '.(($max == 3) ? ' selected="true" ' : '').'>3</option>';		
        echo '<option value="4" '.(($max == 4) ? ' selected="true" ' : '').'>4</option>';
                    echo '<option value="5" '.(($max == 5) ? ' selected="true" ' : '').'>5</option>';		
        echo '<option value="6" '.(($max == 6) ? ' selected="true" ' : '').'>6</option>';
                    echo '<option value="7" '.(($max == 7) ? ' selected="true" ' : '').'>7</option>';		
        echo '<option value="8" '.(($max == 8) ? ' selected="true" ' : '').'>8</option>';
                    echo '<option value="9" '.(($max == 9) ? ' selected="true" ' : '').'>9</option>';		
        echo '<option value="10" '.(($max == 10) ? ' selected="true" ' : '').'>10</option>';
                        
        echo '</select></span>';
        echo '</p>';	
    }
	
	/**
	 * vrstica za fokusiranje mape (text kraja, lokacije)
	 */
	function fokus_mape() {
        global $lang;
        
        $row = Cache::srv_spremenljivka($this->spremenljivka);
        $newParams = new enkaParameters($row['params']);
        $fokus = $newParams->get('fokus_mape'); //dobi fokus mape

        echo '<p id="fokus_mape" '.($row['enota'] == 3 ? 'style="display: none;"' : '').'>';
        echo '<span class="title" >'.$lang['srv_vprasanje_fokus_map'].': </span>';
        echo '<span class="content">';
        echo '<input type="text" value="'.$fokus.'"  name="fokus_mape" id="fokus_mape_' . $row['id'] . '" spr_id="'.$row['id'].'" size="20" />';
        echo '</span>';
        echo '</p>';	
    }
	
	/**
	 * vrstica za naslov podvprasanja v oblacek markerja
	 */
	function naslov_podvprasanja_map() {
        global $lang;
        
        $row = Cache::srv_spremenljivka($this->spremenljivka);
        $newParams = new enkaParameters($row['params']);
        
        $naslov = $newParams->get('naslov_podvprasanja_map'); //dobi naslov podvprasanja mape
        $marpod = $newParams->get('marker_podvprasanje'); //ali dodam podvprasanje v infowindow

        echo '<p id="naslov_podvprasanja_map" '.(($marpod == 1 || $row['enota'] == 3) ? '' : 'style="display: none;"').'>';
        echo '<span class="title" >'.$lang['srv_vprasanje_naslov_podvprasanja_map'].': '.Help::display('naslov_podvprasanja_map').'</span>';
        echo '<span class="content">';
        echo '<input type="text" value="'.$naslov.'"  name="naslov_podvprasanja_map" id="naslov_podvprasanja_map_' . $row['id'] . '" spr_id="'.$row['id'].'" size="25" />';
        echo '</span>';
        echo '</p>';
	}
	
	/**
	* vrstica za poizvedovanje trenutne lokacije
	*/
	function userLocation() {
        global $lang;
        
        $row = Cache::srv_spremenljivka($this->spremenljivka);
        $newParams = new enkaParameters($row['params']);
        $usrloc = $newParams->get('user_location'); //ali se poizve trenutna lokacija
            $input = $newParams->get('multi_input_type') ? $newParams->get('multi_input_type') : 'marker';

        echo '<p id="user_location_map" '. ((($row['enota'] == 2 && $input !== 'marker') || $row['enota'] == 3) ? 'style="display: none;"' : '').'>';
            echo '<label for="user_location_' . $row['id'] . '" class="title">'.$lang['srv_vprasanje_user_location_map'].': '.Help::display('user_location_map').'</label>';
        echo '<span class="content"><input type="hidden" name="user_location" value="0">';
        echo '<input type="checkbox" value="1" '.(($usrloc == 1) ? 'checked' : '').' name="user_location" id="user_location_' . $row['id'] . '" spr_id="'.$row['id'].'" />';
        echo '</span>';
        echo '</p>';	
    }
	
	/**
	* vrstica za podvprasanje v markerju - infowindow
	*/
	function markerPodvprasanje() {
        global $lang;
        
        $row = Cache::srv_spremenljivka($this->spremenljivka);
        $newParams = new enkaParameters($row['params']);
        $marpod = $newParams->get('marker_podvprasanje'); //ali dodam podvprasanje v infowindow
        $input = $newParams->get('multi_input_type') ? $newParams->get('multi_input_type') : 'marker';

        echo '<p id="marker_podvprasanje" '. ((($row['enota'] == 3) || $input !== 'marker') ? 'style="display: none;"' : '').' >';
        echo '<label for="marker_podvprasanje_' . $row['id'] . '" class="title">'.$lang['srv_vprasanje_marker_podvpr_map'].': '.Help::display('marker_podvprasanje').'</label>';
        
        echo '<span class="content"><input type="hidden" name="marker_podvprasanje" value="0">';
        echo '<input type="checkbox" value="1" '.(($marpod == 1) ? 'checked' : '' ). ' onChange="show_infowindow_map();"'.
                        ' name="marker_podvprasanje" id="marker_podvprasanje_' . $row['id'] . '" spr_id="'.$row['id'].'" />';
        echo '</span>';
        echo '</p>';	
    }
	
	/**
	* vrstica za podvprasanje v markerju - infowindow
	*/
	function dodaj_SearchBox() {
        global $lang;
        
        $row = Cache::srv_spremenljivka($this->spremenljivka);
        $newParams = new enkaParameters($row['params']);
        $marpod = $newParams->get('dodaj_searchbox'); //ali dodam podvprasanje v infowindow
        $input = $newParams->get('multi_input_type') ? $newParams->get('multi_input_type') : 'marker';

        echo '<p id="dodaj_searchbox" '.($row['enota'] == 3 || $input != 'marker' ? 'style="display: none;"' : '').'>';
        echo '<label for="dodaj_searchbox_' . $row['id'] . '" class="title">'.$lang['srv_vprasanje_show_searchbox_map'].': '
                        .Help::display('dodaj_searchbox').'</label>';
        echo '<span class="content"><input type="hidden" name="dodaj_searchbox" value="0">';
        echo '<input type="checkbox" value="1" '.(($marpod == 1) ? 'checked' : '').
                        ' name="dodaj_searchbox" id="dodaj_searchbox_' . $row['id'] . '" spr_id="'.$row['id'].'" />';
        echo '</span>';
        echo '</p>';	
    }	
	
	
	/** 
	* napredno urejanje vprasanja
	* 
	*/
	function vprasanje_napredno () {
		global $lang;
		global $global_user_id;
		global $admin_type;

		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		//echo '<h2>'.$row['variable'].' - '.$lang['srv_napredno_urejanje'].'</h2>';
		$this->variable();
	
	
		// Spremenljivka
		echo '<fieldset><legend>'.$lang['srv_spremenljivka'].'</legend>';
		$this->edit_variable();
		$this->edit_label();
		echo '</fieldset>';
		
		
		// Lastnosti
		echo '<fieldset><legend>'.$lang['srv_lastnosti'].'</legend>';
		$this->edit_sistem();
		
		//echo '<p class="heading">'.$lang['srv_prikaz_vprasanja'].'</p>';
		$this->edit_visible();
		
		// Disabled vprasanje - vprasanje je onemogoceno za respondente (zaenkrat samo osnovni radio, dropdown in text)
		if(in_array($row['tip'], array(1,3,4,21)))
			$this->edit_disabled();

		// Vprasanje lahko zaklene samo admin, manager ali avtor (drugace ga lahko zaklene sam sebi in potem ne more vec urejat)
		$author = SurveyInfo::getInstance()->getSurveyColumn("insert_uid");
		if($admin_type == 0 || $admin_type == 1 || $global_user_id == $author)
			$this->edit_locked();
	
		$this->edit_timer();
		
		if ($row['tip'] <= 2 )
			$this->edit_stolpci();
		
		if($row['tip'] == 2) {
			$this->edit_checkbox_max_limit();
			$this->edit_checkbox_min_limit();
		}
		
		if($row['tip'] <= 3)
			$this->edit_stat();
				
		if ($row['tip'] == 6)
			$this->edit_grid_dynamic();
		
		if ($row['tip'] == 1 || $row['tip'] == 6) {
			$this->edit_onchange_submit();
		}
		
		if ($row['tip'] == 1 || $row['tip'] == 3)
			$this->edit_inline_edit();
				
		if ($row['tip'] != 5)
			$this->edit_showOnAllPages();
		
		if ($row['tip'] == 1 || $row['tip'] == 2 && (in_array($row['orientation'], array(0,1,2))))
			$this->edit_hideRadio();
		
		if($row['tip'] == 1 || $row['tip'] == 2 || $row['tip'] == 6)
			$this->edit_presetValue();
		
		// Nastavitev za prikaz prejsnjih odgovorov pod text vprasanjem
		if ($row['tip'] == 21)
			$this->edit_show_prevAnswers();
			
		echo '</fieldset>';
	
		// Posebni tipi text vprasanja (signature, captcha, upload, email)
		if ($row['tip'] == 21){		
			echo '<fieldset><legend>'.$lang['srv_advanced_subtype'].'</legend>';
			
            $this->edit_upload();
            $this->edit_signature();	
			$this->edit_captcha();
			$this->edit_email_verify();
			
			echo '</fieldset>';
		}
	}
	
	/**
	* poskrbi za prikaz pogojev v vprasanju
	* 
	*/
	function vprasanje_pogoji () {
		global $lang;
		global $global_user_id;
		
		$b = new Branching($this->anketa);
		
		$rows = Cache::srv_spremenljivka($this->spremenljivka);
		
		$this->variable();
        
        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
        $userAccess = UserAccess::getInstance($global_user_id);
        if(!$userAccess->checkUserAccess($what='if')){

            $userAccess->displayNoAccess($what='if');
            return;
        }
		
		$sql = sisplet_query("SELECT parent FROM srv_branching WHERE element_spr = '$this->spremenljivka'");
		$row = mysqli_fetch_array($sql);
		
		if ($row['parent'] > 0) {
			$sql1 = sisplet_query("SELECT COUNT(*) AS count FROM srv_branching WHERE parent = '$row[parent]'");
			$row1 = mysqli_fetch_array($sql1);
			$count = $row1['count'];
		} else $count = 0;
		
		// $count pove koliko elementov je v parent ifu
		if ($row['parent'] == 0 || $count > 1) {
			
			// naredimo isto kot s klikom na ikono IF na vprasanju
			$r['spr'] = $this->spremenljivka;
			$r['if'] = 0;
			$r['endif'] = 1;
			
			echo '<p id="if_preview">'.$lang['srv_question_no_if'].'</p>';
			
			echo '<p id="if_preview_link"><a href="#" onclick="if_new(\''.$r['spr'].'\', \''.$r['if'].'\', \''.$r['endif'].'\', \'0\'); return false;"><span class="bold">'.$lang['srv_add_condition_question'].'</span></a></p>';
			
			if ($count > 1) {
				$parents = $b->get_parents($this->spremenljivka);
				if ($parents != '') {
					echo '<p><b>'.$lang['srv_question_no_if_in_nested_if'].':</b> ';
					$parents = explode(' ', $parents);
					foreach ($parents AS $p) {
						$p = str_replace('p_', '', $p);
						echo '<br />';
						$b->conditions_display($p);
					}
					echo '</p>';
				}
			}
			
		} else {
			
			//echo '<h2>'.$rows['variable'].' - '.$lang['srv_edit_condition_question'].'</h2>';
				
			echo '<p id="if_preview">';
			
			$b->conditions_display($row['parent']);
			echo '</p>';
			
			echo '<p><a href="#" onclick="condition_editing(\''.$row['parent'].'\'); return false;">'.$lang['srv_if_edit'].'</a></p>';
			
			if ($row['parent'] != 0) {
				
				$parents = $b->get_parents(0, $row['parent']);
				if ($parents != '') {
					echo '<p><b>'.$lang['srv_question_in_if_in_nested_if'].':</b> ';
					$parents = explode(' ', $parents);
					foreach ($parents AS $p) {
						$p = str_replace('p_', '', $p);
						echo '<br />';
						$b->conditions_display($p);
					}
					echo '</p>';
				}
			}

		}
	}
	
	/**
	* prikaze opcije za validacijo
	* 
	*/
	function vprasanje_validation () {
		global $lang;
		global $global_user_id;
		
		$rows = Cache::srv_spremenljivka($this->spremenljivka);
		
		$this->variable();

		$sql = sisplet_query("SELECT if_id, reminder, reminder_text FROM srv_validation v WHERE v.spr_id='$this->spremenljivka'");
		if (mysqli_num_rows($sql) > 0) {
			
			echo '<input type="hidden" value="1" name="validationedit">';
			
			$b = new Branching($this->anketa);
			
			while ($row = mysqli_fetch_array($sql)) {
				
				echo '<fieldset>';
				echo '<p><a href="#" onclick="validation_edit(\''.$this->spremenljivka.'\', \''.$row['if_id'].'\'); return false;" title="'.$lang['srv_if_edit'].'">';
				$b->conditions_display($row['if_id']);
				echo '</a></p>';
				
				echo '<p>';
				echo '<span class="title">'.$lang['srv_alert_type'].':</span>';
				
				echo '<span class="content">';
				echo '<select name="validation-'.$row['if_id'].'-reminder">';
				echo '<option value="0" '.($row['reminder']==0?'selected':'').'>'.$lang['srv_reminder_off2'].'</option>';
				echo '<option value="1" '.($row['reminder']==1?'selected':'').'>'.$lang['srv_reminder_soft2'].'</option>';
				echo '<option value="2" '.($row['reminder']==2?'selected':'').'>'.$lang['srv_reminder_hard2'].'</option>';
				echo '</select>';
				echo '</span>';
				
				echo '</p>';
				echo '<p><span class="title">'.$lang['srv_alert_text'].':</span>';
				
				echo '<span class="content"><input type="text" name="validation-'.$row['if_id'].'-reminder_text" value="'.$row['reminder_text'].'"></span>';
				
				echo '</p>';
				
				echo '<p class="floatLeft spaceRight bold"><a href="#" onclick="validation_if_remove(\''.$this->spremenljivka.'\', \''.$row['if_id'].'\'); return false;">'.$lang['srv_validation_remove'].'</a></p>';
				
				echo '</fieldset>';
			}
			
		} else {
			echo '<p>'.$lang['srv_validation_no'].'</p>';
		}
        
        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik - drugace skrijemo gumb za dodajanje validacije
        $userAccess = UserAccess::getInstance($global_user_id);
        if(!$userAccess->checkUserAccess($what='validation')){
            $userAccess->displayNoAccess($what='validation');
        }
        else{
            echo '<p><a href="#" onclick="validation_new(\''.$this->spremenljivka.'\'); return false;"><span class="bold">'.$lang['srv_validation_new'].'</span></a></p>';
        }
	}
	
	/** 
	* prikaze komentarje na vprasanje
	* 
	*/
	function vprasanje_komentarji () {
		global $lang;
		global $admin_type;
		global $global_user_id;
		
		SurveySetting::getInstance()->Init($this->anketa);
		SurveyInfo::getInstance()->SurveyInit($this->anketa);
		
		if ($this->spremenljivka > 0) {
			$rows = Cache::srv_spremenljivka($this->spremenljivka);		
			$this->variable();
		} 
		else {
			$row = SurveyInfo::getInstance()->getSurveyRow();
			
			if ($this->spremenljivka == -1) {
				$rows['variable'] = $lang['srv_intro_label'];
				$rows['note'] = $row['intro_note'];
				$rows['thread'] = $row['thread_intro'];
				
				echo '<h2>'.$rows['variable'].'</h2>';
			} 
			elseif ($this->spremenljivka == -2) {
				$rows['variable'] = $lang['srv_end_label'];
				$rows['note'] = $row['concl_note'];
				$rows['thread'] = $row['thread_concl'];
				
				echo '<h2>'.$rows['variable'].'</h2>';
			}
		}
		
		
		$question_note_view = SurveySetting::getInstance()->getSurveyMiscSetting('question_note_view');
		$question_note_write = SurveySetting::getInstance()->getSurveyMiscSetting('question_note_write');
		
		if ($question_note_view == '' || $question_note_view >= $admin_type) {
			
			if ($question_note_write == '' || $question_note_write >= $admin_type) {
				echo '<p>'.$lang['srv_note'];
				//echo '<span class="red pointer" onclick="this.style.display=\'none\'; create_editor(\'note\');"> - '.$lang['srv_editor'].'</span>';
				echo '<a href="#" title="'.$lang['srv_editor_title'].'" onmouseover="this.style.display=\'none\'; create_editor(\'note\');"><span class="faicon edit spaceLeft"></span></a>';
				echo '<textarea name="note" id="note" style="width:99%; height:150px">'.$rows['note'].'</textarea></p>';
			} else {
				echo '<p>'.$lang['srv_note'].'';
				echo '<textarea name="note" id="note" style="width:99%; height:150px" disabled>'.$rows['note'].'</textarea></p>';
			}
		}
		
		// tukaj prikazujemo samo se opombo
		return;
		
		$question_comment = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment');
		//$question_comment = 4;	// vedno prikazemo
		$question_resp_comment = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment');
		$question_resp_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_viewadminonly');
		$question_comment_viewadminonly = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewadminonly');
		$question_comment_viewauthor = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_viewauthor');
		$sortpostorder = SurveySetting::getInstance()->getSurveyMiscSetting('sortpostorder');
		$addfieldposition = SurveySetting::getInstance()->getSurveyMiscSetting('addfieldposition');
		
		if (($admin_type <= $question_comment && $question_comment != '') || $question_resp_comment==1) {
			
			$f = new Forum;
			
			$spremenljivka = $this->spremenljivka;
			$type = 1;
			$view = 3;
			
			echo '<div id="survey_comment_'.$spremenljivka.'_'.$view.'">';
			echo '<div style="width:45%; float:left">';
			echo '<h3 class="red"><b>'.$lang['comments'].'</b>';
			echo '<span class="sprites '.($sortpostorder==1?'up':'down').'" style="float:right" title="'.($sortpostorder==1?$lang['forum_desc']:$lang['forum_asc']).'"></span>';
			echo '</h3>';
			
			if ($addfieldposition == 1) {
				$b = new BranchingAjax($this->anketa);
				$b->add_comment_field($spremenljivka, $type, $view, false);
				echo '<br /><br />';
			}
			
			// komentarji na vprašanje
			if ($rows['thread'] > 0) {
			
				$tid = $rows['thread'];
				
				$orderby = $sortpostorder == 1 ? 'DESC' : 'ASC' ;
				
				if ($admin_type <= $question_comment_viewadminonly) {	// vidi vse komentarje
					$sql = sisplet_query("SELECT * FROM post WHERE tid='$tid' ORDER BY time $orderby, id $orderby");
				} elseif ($question_comment_viewauthor == 1) {	// vidi samo svoje komentarje
					$sql = sisplet_query("SELECT * FROM post WHERE tid='$tid' AND uid='$global_user_id' ORDER BY time $orderby, id $orderby");
				} else {										// ne vidi nobenih komentarjev
					$sql = sisplet_query("SELECT * FROM post WHERE 1=0");
				}
				
				if (mysqli_num_rows($sql) > 0) {
					$i = 0;
					while ($row = mysqli_fetch_array($sql)) {
						if (($i != 0 && $sortpostorder==0) || ($i < $rowss-1 && $sortpostorder==1)) {
							if ($row['ocena'] == 0) echo '<span style="color:black">';
								elseif ($row['ocena'] == 1) echo '<span style="color:darkgreen">';
								elseif ($row['ocena'] == 2) echo '<span style="color:lightgray">';
								elseif ($row['ocena'] == 3) echo '<span style="color:lightgray">';
								else echo '<span>';
								
							echo '<b>'.$f->user($row['uid']).'</b> ('.$f->datetime1($row['time']).'):';
							echo '<br/>'.$row['vsebina'].'<hr>';
							
							echo '</span>';
						}
						$i++;
					}
				}
				
			}
						
			if ($addfieldposition == '' || $addfieldposition == 0) {
				$b = new BranchingAjax($this->anketa);
				$b->add_comment_field($spremenljivka, $type, $view, false);
			}	
			
			echo '</div>';
			echo '</div>';
			
			if ($admin_type <= $question_resp_comment_viewadminonly) {
				$sql = sisplet_query("SELECT d.*, u.time_edit FROM srv_data_text".$this->db_table." d, srv_user u WHERE d.spr_id='0' AND d.vre_id='$this->spremenljivka' AND u.id=d.usr_id ORDER BY d.id ASC");
			
				// komentarji respondentov
				$sql = sisplet_query("SELECT d.*, u.time_edit FROM srv_data_text".$this->db_table." d, srv_user u WHERE d.spr_id='0' AND d.vre_id='$this->spremenljivka' AND u.id=d.usr_id ORDER BY d.id ASC");
				if (mysqli_num_rows($sql) > 0) {
					
					echo '<div style="width:45%; float:right">';
					echo '<h3 class="red"><b>'.$lang['srv_repondent_comment'].'</b></h3>';
						
					while ($row = mysqli_fetch_array($sql)) {
						if ($row['text2'] == 0) echo '<span style="color:black">';
							elseif ($row['text2'] == 1) echo '<span style="color:darkgreen">';
							elseif ($row['text2'] == 2) echo '<span style="color:lightgray">';
							elseif ($row['text2'] == 3) echo '<span style="color:lightgray">';
							else echo '<span>';
							
						echo $f->datetime1($row['time_edit']).':<br />'.$row['text'].'<hr>';
					
						echo '</span>';
					}
					
					echo '</div>';
				
				}
			}
		}
	}
	
	/** 
	* urejanje label za manjkajoče vrednosti za vprašanje
	* 
	*/
	function vprasanje_manjkajoce () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);

		$vmv = new RecodeValues($this->anketa,$this->spremenljivka); 

		$this->variable();
		
		echo '<span id="vprasanje_edit_mv">';
		echo $vmv->DisplayMissingValuesForQuestion();
		echo '</span>';
	}
	
	
	
	/** 
	* urejanje label za grafe
	* 
	*/
	/*function vprasanje_grafi () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
			
		//echo '<h2>'.$row['variable'].' - '.$lang['srv_grafi_urejanje'].'</h2>';
		$this->variable();
		
		// Enable/disable editiranje label posebej za grafe
		$this->edit_graf();		
		
		// Gumb za kopiranje besedila iz osnovnega urejanja
		echo '<div class="chart_copy" '.($row['edit_graf'] == 0 ? ' style="display: none;"' : '').'><p>';
		echo $lang['srv_edit_chart_copy'].': ';
		echo '<input type="button" name="copy_graf" value="'.$lang['srv_edit_chart_copyB'].'" onClick="copy_chart();" />';
		
		//echo '<a class="ovalbutton ovalbutton_gray" onclick="copy_chart();" href="#">
		//	<span>'.$lang['srv_edit_chart_copyB'].'</span>
		//	</a>';
		
		echo '</p></div>';
		
		$show = ($row['edit_graf'] == 0) ? ' style="display:none;"' : '';
		
		echo '<div class="chart_editing" '.$show.'>';
		
		// Urejanje naslova spremenljivke
		$text = $row['naslov_graf'] == '<p></p>' ? $row['naslov'] : $row['naslov_graf'];
		if (strtolower(substr($text, 0, 3)) == '<p>' && strtolower(substr($text, -4)) == '</p>' && strrpos($text, '<p>') == 0) {
			$text = substr($text, 3);
			$text = substr($text, 0, -4);
		}		
		echo '<p>';
		echo '<textarea style="width:99%; height:60px;" name="naslov_graf" id="naslov_graf" class="chart_label">'.$text.'</textarea>';
		echo '</p>';		
				
		// Urejanje naslovov variabel
		$sql1 = sisplet_query("SELECT id, naslov, naslov_graf, variable, other FROM srv_vrednost WHERE spr_id = '$this->spremenljivka' ORDER BY vrstni_red ASC");
		if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
		
		echo '<input type="hidden" name="edit_vrednost_graf" value="1" />';
		
		echo '<div id="vrednosti_holder"><ul class="vrednost_sort">';
		while ($row1 = mysqli_fetch_array($sql1)) {
			
			$text = $row1['naslov_graf'] == '' ? $row1['naslov'] : $row1['naslov_graf'];
			
			echo '<li id="vrednost_'.$vrednost.'" '.($row1['other'] == 1 ? 'class="li_other"' : '').'>';
	
			$gridWidth = 42;
					
			echo '<textarea name="vrednost_graf_'.$row1['id'].'" id="'.$row1['variable'].'_graf" class="vrednost_textarea chart_label" style="width:'.$gridWidth.'%;">'.$text.'</textarea> ';
			echo '['.$row1['variable'].']</span>';
			if ($row1['other'] == 1) echo ' <input type="text" disabled style="width:40px" />';

			echo '</li>';			
		}
		
		echo '</ul>';
	
		echo '</div>';
				
		
		// Urejanje label za gride
		//if($row['tip'] == 6 || $row['tip'] == 16 || $row['tip'] == 19 || $row['tip'] == 20){
		if($row['tip'] == 6 || $row['tip'] == 16 || $row['tip'] == 19 || $row['tip'] == 20 || $row['tip'] == 2){
			
			echo '<div class="grid_settings">';
			echo '<input type="hidden" name="edit_grid_graf" value="1" />';

			echo '<table id="grids" style="width:100%">';
			
			echo '<tr>';
			for ($i=1; $i<=$row['grids']; $i++) {
				echo '<td>'.$i.'</td>';
			}
			
			//dodatne vrednosti (ne vem, zavrnil...)
			if (count($already_set_mv) > 0 ) {
				echo '<td></td>';
				if (count($missing_values) > 0) {
					foreach ($missing_values AS $mv_key => $mv_text) {
						if (isset($already_set_mv[$mv_key])) {
							echo '<td>'.$mv_key.'</td>';
						}
					}
				}
			}
			echo '</tr>';
			
			echo '<tr>';
			for ($i=1; $i<=$row['grids']; $i++) {
				$sql1 = sisplet_query("SELECT naslov, naslov_graf FROM srv_grid WHERE id='$i' AND spr_id='$this->spremenljivka'");
				$row1 = mysqli_fetch_array($sql1);
				$text = $row1['naslov_graf'] == '' ? $row1['naslov'] : $row1['naslov_graf'];
				echo '<td><input type="text" name="grid_graf_'.$i.'" id="grid_naslov_'.$i.'_graf" class="chart_label" value="'.$text.'" /></td>';
			}
			
			//dodatne vrednosti (ne vem, zavrnil...)
			if (count($already_set_mv) > 0 ) {
				echo '<td></td>';
				if (count($missing_values) > 0) {
					foreach ($missing_values AS $mv_key => $mv_text) {
						if (isset($already_set_mv[$mv_key])) {
							echo '<td><input type="text" name="grid_'.$mv_key.'_graf" class="chart_label" value="'.$already_set_mv[$mv_key].'" /></td>';
						}
					}
				}
			}
			echo '</tr>';
			
			echo '</table>';
			echo '</div>';
		}
		
		echo '</div>';
		
		
		// sirina labele grafa (navadna ali 50%)
		//$this->wide_graf();	
	}*/
	
	/** 
	* prikaz trackinga sprememb spremenljivke
	* 
	*/
	function vprasanje_display_tracking () {
		global $lang;
		global $admin_type;
		
		$rows = SurveyInfo::getInstance()->getSurveyRow();
		if ($rows['vprasanje_tracking'] == 0) return;
		
		$row1 = Cache::srv_spremenljivka($this->spremenljivka);
		
		$this->variable();
		
		$sql = sisplet_query("SELECT * FROM srv_spremenljivka_tracking s, users u WHERE s.spr_id='$this->spremenljivka' AND s.tracking_uid=u.id ORDER BY s.tracking_time DESC");
		while ($row = mysqli_fetch_array($sql)) {
			
			echo '<div class="pointer" onmouseover="show_tip_preview_toolbox(\'0\', \''.$row['tracking_id'].'\', \'1\');" onmouseout="$(\'#tip_preview\').hide();" copy="'.$row['tracking_id'].'"><p><span title="'.$row['email'].'">'.$row['name'].'</span> - '.datetime($row['tracking_time']).'</p></div>';			
		}	
	}
	
	// ali urejamo labele za graf ali uporabimo default labele
	function edit_graf() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		echo '<p>';
		echo $lang['srv_edit_chart'].':<br /><br />';
		echo '<input type="radio" name="edit_graf" value="0" '.(($row['edit_graf'] == 0) ? ' checked="checked" ' : '').' onClick="edit_chart(0);" />'.$lang['srv_edit_chart_0'].'<br />';		
		echo '<input type="radio" name="edit_graf" value="1" '.(($row['edit_graf'] == 1) ? ' checked="checked" ' : '').' onClick="edit_chart(1);" />'.$lang['srv_edit_chart_1'];
		echo '</p>';
	}
	
	// sirina grafa - (navadna ali sirse labele -> 50%)
	function wide_graf() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		echo '<p>';
		echo '<span class="title">'.$lang['srv_wide_chart'].':</span>';
		echo '<input type="radio" name="wide_graf" value="0" '.(($row['wide_graf'] == 0) ? ' checked="checked" ' : '').' />'.$lang['srv_wide_chart0'];		
		echo '<input type="radio" name="wide_graf" value="1" '.(($row['wide_graf'] == 1) ? ' checked="checked" ' : '').' />'.$lang['srv_wide_chart1'];
		echo '</p>';
	}
	
	
	function edit_tip () {
		global $lang;
		global $admin_type;
		global $global_user_id;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$sa = new SurveyAdmin();
		$this->survey_type = $sa->getSurvey_type($this->anketa);
        
        // Preverimo, ce je funkcionalnost v paketu, ki ga ima uporabnik
        $userAccess = UserAccess::getInstance($global_user_id);
    

		// demografija ima svojo roleto
		if (Demografija::getInstance()->isDemografija($row['variable'])) {

        } 
        // obicna roleta za tip
        else {
					
			echo '<span class="content"><select name="tip" id="spremenljivka_tip_' . $row['id'] . '" size="1" spr_id="' . $row['id'] . '" onChange="change_tip(\'' . $row['id'] . '\', $(this).val());">';
			echo '<option value="1"' . ($row['tip'] == 1 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_radio'] . '</option>';
			echo '<option value="2"' . ($row['tip'] == 2 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_checkbox'] . '</option>';
            
            if($this->survey_type != 0){

				if ($row['tip'] == 3)	// star tip, ostane samo za kompatibilnost, ce je kje ostal se star tip
					//echo '<option value="3"' . ($row['tip'] == 3 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_dropdown'] . '</option>';
					if ($row['info']){
						echo '<option value="3"' . ($row['tip'] == 3 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_checkbox'] .'</option>';
					}
					else{
						echo '<option value="3"' . ($row['tip'] == 3 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_radio'] .'</option>';
                    }
                    
				echo '<option value="6"' . ($row['tip'] == 6 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_multigrid'] . '</option>';
				echo '<option value="16"' . ($row['tip'] == 16 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_multicheckbox'] . '</option>';
				echo '<option value="19"' . ($row['tip'] == 19 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_multitext'] . '</option>';
				echo '<option value="20"' . ($row['tip'] == 20 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_multinumber'] . '</option>';
                
                if($userAccess->checkUserAccess($what='question_type_multitable'))
                    echo '<option value="24"' . ($row['tip'] == 24 ? ' selected="true"' : '') . '>' . $lang['srv_survey_table_multiple'] . '</option>';
                
                echo '<option value="21"' . ($row['tip'] == 21 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_text'] . '</option>';
                
                if ($row['tip'] == 4)	// star tip, ostane samo za kompatibilnost, ce je kje ostal se star tip
					echo '<option value="4"' . ($row['tip'] == 4 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_text'] . '</option>';
                
                echo '<option value="7"' . ($row['tip'] == 7 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_number'] . '</option>';
                echo '<option value="5"' . ($row['tip'] == 5 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_label'] . '</option>';
                
                if($userAccess->checkUserAccess($what='question_type_location'))
				    echo '<option value="26"' . ($row['tip'] == 26 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_location'] . '</option>';
                
                // heatmap - sedaj lahko vsi to uporabljajo
                if($userAccess->checkUserAccess($what='question_type_heatmap'))
                    echo '<option value="27"' . ($row['tip'] == 27 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_heatmap'] . '</option>';
                    
                echo '<option value="8"' . ($row['tip'] == 8 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_datum'] . '</option>';
                
                if($userAccess->checkUserAccess($what='question_type_ranking'))
                    echo '<option value="17"' . ($row['tip'] == 17 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_ranking'] . '</option>';
                
                if($userAccess->checkUserAccess($what='question_type_sum'))
				    echo '<option value="18"' . ($row['tip'] == 18 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_vsota'] . '</option>';
                
                if ($row['tip'] == 22)	// star tip, ostane samo za kompatibilnost, ce je kje ostal se star tip
					echo '<option value="22"' . ($row['tip'] == 22 ? ' selected="true"' : '') . '>' . $lang['srv_vprasanje_tip_22'] . '</option>';
			}
			
			if (SurveyInfo::getInstance()->checkSurveyModule('social_network')) {
				echo '<option disabled="disabled">-------------------------------</option>';
				echo '<option value="9"' . ($row['tip'] == 9 ? ' selected="true"' : '') . '>'.$lang['srv_vprasanje_tip_9'].'</option>';
			}
		
			echo '</select></span>';
			//echo '</p>';
		
		
			echo '<script type="text/javascript">';
			echo '$(document).ready(function() { ';
			echo '$("#spremenljivka_tip_' . $row['id'] . '").selectbox();'; // kreira custom dropdown z možnostjo predogleda vprašanja
			echo '});';
			echo '</script>';
		}	
	}
	
	/**
	* urejanje tipa vprašanja
	*/
	function edit_subtip () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$sa = new SurveyAdmin();
		$this->survey_type = $sa->getSurvey_type($this->anketa);
		$rowa = SurveyInfo::getInstance()->getSurveyRow();
		
		
		//editiranje podtipa pri radio (hor., ver., dropdown)
		if($row['tip'] == 1 || ($row['tip'] == 3 && !$row['info'])){
			$this->edit_radio_subtype();
		}

		//editiranje orientacije pri checkboxu (hor., ver.)
		if($row['tip'] == 2 || ($row['tip'] == 3 && $row['info'])){
			$this->edit_checkbox_subtype();
		}
		
		//editiranje podtipa pri razvrscanju (prestavljanje, ostevilcevanje...)
		if($row['tip'] == 17){
			$this->edit_ranking();
		}
		
		//editiranje podtipa pri multigridu (navadno, dropdown, sem.dif.)
		if($row['tip'] == 6 || $row['tip'] == 16){
			$this->edit_grid_subtype();
		}
		
		if ($row['tip'] == 7) {
			$this->edit_subtype_number();
			return;
		}
		
		if ($row['tip'] == 20) {
			$this->edit_subtype_multinumber();
			return;
		}
		
		if ($row['tip'] == 9) {
			$this->edit_name_generator_design();
		}
                
        //podtip lokacija - moja lokacija
        if ($row['tip'] == 26) {
            $this->edit_subtype_map();
		}
		
		//podtip heatmap
        if ($row['tip'] == 27) {
            $this->edit_heatmap_settings();
		}
		
		echo '<script type="text/javascript">';
		echo '  $(document).ready(function() {';
		echo '      $("#spremenljivka_podtip_' . $row['id'] . '").selectbox();'; // kreira custom dropdown z možnostjo predogleda vprašanja
		echo '  });';
		echo '</script>';		
	}
	
	/**
	* urejanje naslova
	* 
	*/
	function edit_naslov($editor = true) {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		// ce je ena vrstica pobrisemo <p> in </p>
		if (strtolower(substr($row['naslov'], 0, 3)) == '<p>' && strtolower(substr($row['naslov'], -4)) == '</p>' && strrpos($row['naslov'], '<p>') == 0) {
			$row['naslov'] = substr($row['naslov'], 3);
			$row['naslov'] = substr($row['naslov'], 0, -4);
		}
		
		echo '<p>';
		if ($editor) 
			echo '<span class="red pointer" onmouseover="this.style.display=\'none\'; create_editor(\'naslov\');">'.$lang['srv_editor'].'</span><br />';
		echo '<textarea name="naslov" id="naslov">'.$row['naslov'].'</textarea>';
		echo '</p>';
	}
	
	/**
	* urejanje variable
	* 
	*/
	function edit_variable () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		#'email','ime','priimek','telefon','naziv','drugo'
		if ( in_array($row['variable'], array('email','telefon','ime','priimek','naziv','drugo')) && $row['sistem']==1	 )
			$disabled = true; else $disabled = false;
		
		echo '<p><span class="title">'.$lang['srv_variable'].': '.Help::display('edit_variable').'</span><span class="content"><input type="text" name="variable" value="'.$row['variable'].'" onkeyup="vprasanje_check_variable(this);" '.($disabled?'disabled':'').' maxlength="10" /></span></p>';
		
		//echo '<p><span class="title">'.$lang['srv_datapiping'].': '.Help::display('DataPiping').'</span> '.$lang['srv_datapiping_txt'].'</p>';
		
	}
	
	/**
	* urejanje variable
	* 
	*/
	function edit_label () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		#'email','ime','priimek','telefon','naziv','drugo'
		
		echo '<p><span class="title">'.$lang['srv_label'].':</span><span class="content"><input type="text" name="label" value="'.$row['label'].'" maxlength="80" /></span></p>';		
	}
	
	/**
	* variablo urejamo inline v naslovu
	* 
	*/
	function variable ($edit_tip = 0) {
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		//echo '<h2><div contenteditable="true" onkeyup="vprasanje_check_variable(this); $(\'#spremenljivka_content_'.$row['id'].' .variable_name\').html( $(this).html() );" onblur="inline_variable(\''.$row['id'].'\', this);" class="editable" style="display:inline-block; width:115px; overflow:hidden; cursor:text">'.$row['variable'].'</div>';
		echo '<h2>'.$row['variable'].'';
		if ($edit_tip == 1) $this->edit_tip();
		echo '</h2>';
		
	}
	
	//edit opombe
	function edit_opomba() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		echo '<p style="margin-bottom: 32px"><label for="_info" class="title">'.$lang['srv_note2'].':</label>';
		//echo '<span class="content"><textarea name="info" style="width:200px">'.$row['info'].'</textarea></span>';
		//echo '<span class="content"><input type="radio" name="info" value="" '.($row['info']==''?' checked':'').'>'.$lang['no'].'';
		//echo '<input type="radio" name="info" value="'.($row['info']!=''?$row['info']:$lang['note']).'" id="radiothis" '.($row['info']!=''?' checked':'').'>'.$lang['yes'].'</span>';
		echo '<span class="content"><input type="hidden" name="info" value="">';
		echo '<input type="checkbox" id="_info" name="info" value="'.($row['info']!=''?$row['info']:$lang['note']).'" '.($row['info']!=''?' checked':'').'></span>';
		echo '</p>';
		
		// ob spremembi opombe, damo drug value tudi na radio Da, da se ob shranjevanju desnega menija shrani nova opomba, ce je bila spremenenjena
		?><script> $('div.spremenljivka_info.info_inline[spr_id=<?=$this->spremenljivka?>]').live('blur', function() { $('#_info').val( $(this).html() ); }) </script><?
	}	
	
	//editiranje naslova uvoda
	function edit_uvod() {
		global $lang;
		global $admin_type;
		
		$row = SurveyInfo::getInstance()->getSurveyRow();

		echo '<h2>'.$lang['srv_intro_label'].'</h2>';
		
		if ($row['introduction'] == '') {
			$lang_admin = SurveyInfo::getInstance()->getSurveyColumn('lang_admin');
			$lang_resp  = SurveyInfo::getInstance()->getSurveyColumn('lang_resp');
			
			// nastavimo na jezik za respondentov vmesnik
			$file = '../../lang/'.$lang_resp.'.php';
			include($file);
			
			$text = $lang['srv_intro'];
			$lang_srv_nextpage_uvod = $lang['srv_nextpage_uvod'];
			
			// nastavimo nazaj na admin jezik
			$file = '../../lang/'.$lang_admin.'.php';
			include($file);
		} 
		else {
			$text = $row['introduction'];
		}

		if (strtolower(substr($text, 0, 3)) == '<p>' && strtolower(substr($text, -4)) == '</p>' && strrpos($text, '<p>') == 0) {
			$text = substr($text, 3);
			$text = substr($text, 0, -4);
		}

		// Opomba
		$opomba = $row['intro_opomba'];
		echo '<p>'.$lang['note'].' ('.$lang['srv_internal'].'): ';
		echo '<textarea name="intro_opomba" class="texteditor info" >'.$opomba.'</textarea>';
		echo '</p>';
		
		/*if ($row['user_base'] == 1 && (int)$row['individual_invitation'] > 0) {
			$disabled = 'disabled';
		} else*/ $disabled = '';
		
		// Prikaz uvoda
		echo '<p>';
		echo '<span class="title">'.$lang['srv_show_intro'].': </span>';
		echo '<input type="radio" name="show_intro" value="0" id="show_intro_0" '.(($row['show_intro'] == 0) ? ' checked="checked" ' : '').' '.$disabled.' onClick="$(\'.intro_static_setting\').hide();" /><label for="show_intro_0">'.$lang['no1'].'</label>';
		echo '<input type="radio" name="show_intro" value="1" id="show_intro_1" '.(($row['show_intro'] == 1) ? ' checked="checked" ' : '').' '.$disabled.' onClick="$(\'.intro_static_setting\').show();" /><label for="show_intro_1">'.$lang['yes'].'</label>';		
		echo '</p>';
		
		// Staticen uvod, ki ne ustvari userja (user se ustvari sele na naslednji strani) - za recimo embeddane ankete...
		//if($admin_type == 0){
			echo '<p class="intro_static_setting" '.($row['show_intro'] == 0 ? ' style="display:none;"' : '').'>';
			echo '<span class="title">'.$lang['srv_show_intro_static'].': </span>';
			echo '<input type="radio" name="intro_static" id="intro_static_0" value="0" '.(($row['intro_static'] == 0) ? ' checked="checked" ' : '').' '.$disabled.' /><label for="intro_static_0">'.$lang['srv_show_intro_static_0'].'</label>';
			echo '<input type="radio" name="intro_static" id="intro_static_1" value="1" '.(($row['intro_static'] == 1) ? ' checked="checked" ' : '').' '.$disabled.' /><label for="intro_static_1">'.$lang['srv_show_intro_static_1'].'</label>';		
			echo '<input type="radio" name="intro_static" id="intro_static_2" value="2" '.(($row['intro_static'] == 2) ? ' checked="checked" ' : '').' '.$disabled.' /><label for="intro_static_2">'.$lang['srv_show_intro_static_2'].'</label>';		
			echo '</p>';		
		//}
		
		//dodaten naslov gumba za naprej
		SurveySetting::getInstance()->Init($this->anketa);
		$srv_nextpage_uvod = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_nextpage_uvod');
		if ($srv_nextpage_uvod != '')
			$text = $srv_nextpage_uvod;
		else
			$text = $lang_srv_nextpage_uvod;
		echo '<p><label for="srv_nextpage_uvod">'.$lang['srv_nextpage_uvod'].':</label> <input type="text" name="srv_nextpage_uvod" id="srv_nextpage_uvod" value="'.$text.'" style="width:200px"></span></p>';
	}
	
	//editiranje naslova zakljucka in osnovnih nastavitev (skok na url...)
	function edit_zakljucek() {
		global $lang;
		global $site_url;
		
		$row = SurveyInfo::getInstance()->getSurveyRow();
		SurveySetting::getInstance()->Init($this->anketa);

		echo '<h2>'.$lang['srv_end_label'].'</h2>';
		
		if ($row['conclusion'] == '') {
			$lang_admin = SurveyInfo::getInstance()->getSurveyColumn('lang_admin');
			$lang_resp  = SurveyInfo::getInstance()->getSurveyColumn('lang_resp');
			
			// nastavimo na jezik za respondentov vmesnik
			$file = '../../lang/'.$lang_resp.'.php';
			include($file);
			
			$text = $lang['srv_end'];
			$lang_srv_prevpage = $lang['srv_prevpage'];
			$lang_srv_konec = $lang['srv_konec'];
			
			// nastavimo nazaj na admin jezik
			$file = '../../lang/'.$lang_admin.'.php';
			include($file);
		} 
		else {
			$text = $row['conclusion'];
		}

		if (strtolower(substr($text, 0, 3)) == '<p>' && strtolower(substr($text, -4)) == '</p>' && strrpos($text, '<p>') == 0) {
			$text = substr($text, 3);
			$text = substr($text, 0, -4);
		}
			
		// opomba
		$opomba = $row['concl_opomba'];
		
		echo '<p>'.$lang['note'].' ('.$lang['srv_internal'].'): ';
		echo '<textarea name="concl_opomba" class="texteditor info" >'.$opomba.'</textarea>';
		echo '</p>';

		echo '<p>';
		echo '<span class="title">'.$lang['srv_show_concl'].': </span>';
		echo '<input type="radio" id="show_concl_0" name="show_concl" value="0" '.(($row['show_concl'] == 0) ? ' checked="checked" ' : '').' /><label for="show_concl_0">'.$lang['no1'].'</label>';
		echo '<input type="radio" id="show_concl_1" name="show_concl" value="1" '.(($row['show_concl'] == 1) ? ' checked="checked" ' : '').' /><label for="show_concl_1">'.$lang['yes'].'</label>';		
		echo '</p>';
		
		
		//dodatne nastaitve (skok na url ...)
		echo '<fieldset><legend>'.$lang['srv_concl_link'].'</legend>';
		
		if ($row['url'] != '')
			$url = $row['url'];
		else
			$url = $site_url;
			
		echo '<p>';
		echo '<input type="radio" id="concl_link_1" name="concl_link" value="1" '.($row['concl_link'] == 0 ? ' checked' : '').' onclick="$(\'#srv_concl_link_go\').hide()"><label for="concl_link_1">'.$lang['srv_concl_link_close'].'</label> ';
		// Rekurzivno - samo pri navadni anketi
		if($row['survey_type'] > 1)
			echo '<br /><input type="radio" id="concl_link_2" name="concl_link" value="2" '.($row['concl_link'] == 2 ? ' checked' : '').' onclick="$(\'#srv_concl_link_go\').hide()"><label for="concl_link_2">'.$lang['srv_concl_link_rec'].'</label> ';
		echo '<br /><input type="radio" id="concl_link_0" name="concl_link" value="0" '.($row['concl_link'] == 1 ? ' checked' : '').' onclick="$(\'#srv_concl_link_go\').show()"><label for="concl_link_0">'.$lang['srv_concl_link_go'].'</label> ';
		echo '</p>';

		// Ce skocimo na custom url prikazemo urejanje url-ja in dodatne nastavitve za parametre v url (usr_id, status...)
		echo '<div id="srv_concl_link_go" '.($row['concl_link'] == 0 || $row['concl_link'] == 2 ?' style="display:none"':'').'">';
		
		// URL
		echo '<p><label for="url">'.$lang['srv_url'].':</label> <input type="text" name="url" id="url_concl_sett" value="'.$url.'" style="width:200px"></p>';
		

		// Parametri
		echo '<p>';
		echo $lang['srv_concl_link_params'].':<br />';
		$concl_url_usr_id = SurveySetting::getInstance()->getSurveyMiscSetting('concl_url_usr_id');
		echo '<input type="hidden" name="concl_url_usr_id" value="0">';
		echo '<span class="spaceLeft spaceright"><input type="checkbox" name="concl_url_usr_id" id="concl_url_usr_id" value="1" '.($concl_url_usr_id==1 ? ' checked="checked"' : '').'> <label for="concl_url_usr_id">'.$lang['srv_concl_link_usr_id'].'</label></span>';
		echo '<br />';
		$concl_url_status = SurveySetting::getInstance()->getSurveyMiscSetting('concl_url_status');
		echo '<input type="hidden" name="concl_url_status" value="0">';
		echo '<span class="spaceLeft"><input type="checkbox" name="concl_url_status" id="concl_url_status" value="1" '.($concl_url_status==1 ? ' checked="checked"' : '').'> <label for="concl_url_status">'.$lang['srv_concl_link_status'].'</label></span>';
		echo '<br />';
		$concl_url_recnum = SurveySetting::getInstance()->getSurveyMiscSetting('concl_url_recnum');
		echo '<input type="hidden" name="concl_url_recnum" value="0">';
		echo '<span class="spaceLeft"><input type="checkbox" name="concl_url_recnum" id="concl_url_recnum" value="1" '.($concl_url_recnum==1 ? ' checked="checked"' : '').'> <label for="concl_url_recnum">'.$lang['srv_concl_link_recnum'].'</label></span>';	
		echo '</p>';

		
		// Text za datapiping v url-ju
		echo '<p>'.$lang['srv_concl_link_datapiping'].'</p>';
		
		echo '</div>';
		
		echo '</fieldset>';
		
		
		echo '<fieldset><legend>'.$lang['srv_extra_settings'].'</legend>';
		
		echo '<p><label for="concl_back_button">'.$lang['srv_concl_back_button_show'].'</label><span class="content"><input type="checkbox" id="concl_back_button" name="concl_back_button" value="1" '.($row['concl_back_button'] == 1 ? ' checked' : '').' onclick=" if (this.checked) { $(\'#srv_prevpage_span\').show(); } else { $(\'#srv_prevpage_span\').hide(); }" /></span>';
				
		// dodaten naslov gumba zakljucek
		$srv_prevpage = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_prevpage');
		if ($srv_prevpage != '')
			$text = $srv_prevpage;
		else
			$text = $lang_srv_prevpage;
		echo '<br /><span id="srv_prevpage_span"'.($row['concl_back_button']==0?' style="display:none"':'').'> <label for="srv_prevpage">'.$lang['srv_prevpage'].':</label> <span class="content"><input type="text" name="srv_prevpage" id="srv_prevpage" value="'.$text.'" style="width:190px"></span></span></p>';
		
		
		echo '<p><label for="concl_end_button">'.$lang['srv_concl_end_button_show'].'</label><span class="content"><input type="checkbox" id="concl_end_button" name="concl_end_button" value="1" '.($row['concl_end_button'] == 1 ? ' checked' : '').' onclick=" if (this.checked) { $(\'#srv_konec_span\').show(); } else { $(\'#srv_konec_span\').hide(); }" /></span>';
		
		// dodaten naslov gumba zakljucek
		$srv_konec = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_konec');
		if ($srv_konec != '')
			$text = $srv_konec;
		else
			$text = $lang_srv_konec;
		echo '<br /><span id="srv_konec_span"'.($row['concl_end_button']==0?' style="display:none"':'').'> <label for="srv_konec">'.$lang['srv_konec'].':</label> <span class="content"><input type="text" name="srv_konec" id="srv_konec" value="'.$text.'" style="width:190px"></span></span></p>';
		
		// Povezava za naknadno urejanje
		echo '<p><label for="concl_return_edit">'.$lang['srv_concl_return_edit'].'</label><span class="content"><input type="checkbox" id="concl_return_edit" name="concl_return_edit" value="1" '.($row['concl_return_edit'] == 1 ? ' checked' : '').'/></span></p>';
		
		// Povezava na pdf
		echo '<p><label for="concl_PDF_link">'.$lang['srv_concl_PDF_link'].'</label><span class="content"><input type="checkbox" id="concl_PDF_link" name="concl_PDF_link" value="1" '.($row['concl_PDF_link'] == 1 ? ' checked' : '').'/></span>'.Help :: display('srv_concl_PDF_link').'</p>';

		
		// link na urejanje texta ce je anketa ze zakljucena
		echo '<p>';
		echo $lang['srv_concl_deactivation_text'].' '.Help::display('srv_concl_deactivation_text').'<br />';
		
		$value = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_survey_non_active');
		if ($value == '') $value = $lang['srv_survey_non_active'];

		echo '<textarea name="srvlang_srv_survey_non_active" id="srvlang_srv_survey_non_active" style="width:260px; height:30px;">'.$value.'</textarea>';
		echo '<span class="faicon edit icon-as_link small" onclick="vprasanje_jezik_edit_zakljucek(\'srvlang_srv_survey_non_active\');" style="float: right; margin-top: 1px;"></span>';
		echo '</p>';	
			
		
		echo '</fieldset>';
	}
	
	//editiranje naslova statistike
	function edit_statistika() {	
		global $lang;
		
		$row = SurveyInfo::getInstance()->getSurveyRow();

		echo '<h2>'.$lang['srv_statistic_label'].'</h2>';
		
		$text = $row['statistics'];
		
		if (strtolower(substr($text, 0, 3)) == '<p>' && strtolower(substr($text, -4)) == '</p>' && strrpos($text, '<p>') == 0) {
			$text = substr($text, 3);
			$text = substr($text, 0, -4);
		}
		
		// text
		echo '<p><span class="title">Besedilo statistike:</span>';
		echo '<textarea name="statistics">'.$text.'</textarea>';
		echo '</p>';
	}
	
	// navadna/sistemska spr
	function edit_sistem() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		if ( in_array($row['variable'], array('email','telefon','ime','priimek','naziv','drugo')) && $row['sistem']==1	 )
			$disabled = true; else $disabled = false;
		
		echo '<p>';
		echo '<label for="sistem" class="title">'.$lang['srv_sistemska'].': '.Help::display('srv_sistemska_edit').'</label>';
		echo '<span class="content"><input type="hidden" name="sistem" value="0" />';
		echo '<input type="checkbox" id="sistem" name="sistem" value="1" '.(($row['sistem'] == 1) ? ' checked="checked" ' : '').' '.($disabled?'disabled':'').' />';		
		echo '</span></p>';
	}
	
	// skrito/vidno vprasanje
	function edit_visible() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		echo '<p>';
		echo '<label for="visible" class="title">'.$lang['srv_visible'].': </label>';
		//echo '<span class="content"><input type="radio" name="visible" value="0" '.(($row['visible'] == 0) ? ' checked="checked" ' : '').' />'.$lang['no1'];
		//echo '<input type="radio" name="visible" value="1" '.(($row['visible'] == 1) ? ' checked="checked" ' : '').' />'.$lang['yes'];		
		echo '<span class="content"><input type="hidden" name="visible" value="0" />';
		echo '<input type="checkbox" id="visible" name="visible" value="1" '.(($row['visible'] == 1) ? ' checked="checked" ' : '').' onClick="show_dostop(this.checked);" />';		
		echo '</span></p>';
		
		echo '<div id="dostop" '.($row['visible'] == 1 ? '' : ' style="display:none;"').'><p>';
		echo '<span class="title">'.$lang['srv_visible_dostop'].': </span>';
		echo '<span class="content"><select name="dostop">';
		echo '<option value="4"'.($row['dostop']==4?' selected':'').'>'.$lang['see_everybody'].'</option>';
		echo '<option value="3"'.($row['dostop']==3?' selected':'').'>'.$lang['see_registered'].'</option>';
		echo '<option value="2"'.($row['dostop']==2?' selected':'').'>'.$lang['see_member'].'</option>';
		echo '<option value="1"'.($row['dostop']==1?' selected':'').'>'.$lang['see_manager'].'</option>';
		echo '<option value="0"'.($row['dostop']==0?' selected':'').'>'.$lang['see_admin'].'</option>';
		echo '</select></span>';
		echo '</p></div>';
	}
	
	// odklenjeno/zaklenjeno vprasanje
	function edit_locked() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		echo '<p>';
		echo '<label for="locked" class="title">'.$lang['srv_locked'].': '.Help::display('srv_spremenljivka_lock').'</label>';
		echo '<span class="content"><input type="hidden" name="locked" value="0" />';
		echo '<input type="checkbox" id="locked" name="locked" value="1" '.(($row['locked'] == 1) ? ' checked="checked" ' : '').' />';		
		echo '</span></p>';		
	}

	// omogoceno/onemogoceno vprasanje pri resevanju
	function edit_disabled() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);

		$spremenljivkaParams = new enkaParameters($row['params']);
		$disabled_vprasanje = ($spremenljivkaParams->get('disabled_vprasanje') ? $spremenljivkaParams->get('disabled_vprasanje') : 0);

		echo '<p>';
		echo '<label for="disabled_vprasanje" class="title">'.$lang['srv_disabled'].': '.Help::display('srv_disabled_question').'</label>';
		echo '<span class="content"><input type="hidden" name="disabled_vprasanje" value="0" />';
		echo '<input type="checkbox" id="disabled_vprasanje" name="disabled_vprasanje" value="1" '.(($disabled_vprasanje == 1) ? ' checked="checked" ' : '').' />';		
		echo '</span></p>';		
	}
	
	/**
	* editiranje grida
	*/
	function edit_grid () {
		global $lang;
		global $default_grid_values;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$rowA = SurveyInfo::getInstance()->getSurveyRow();
		
		$spremenljivkaParams = new enkaParameters($row['params']);
		$diferencial_trak = ($spremenljivkaParams->get('diferencial_trak') ? $spremenljivkaParams->get('diferencial_trak') : 0); //za checkbox
		
		$display_1 = ( ($row['tip'] == 6 && ($row['enota'] == 4 || $row['enota'] == 5)) || ($row['tip'] == 6 && $row['enota'] == 8) ||  ($row['tip'] == 20 && $row['ranking_k'] == 1)) ? ' style="display:none;"' : '';
		$display = ( ($row['tip'] == 6 && ($row['enota'] == 4 || $row['enota'] == 5  || ($row['enota'] == 1 && $diferencial_trak == 1) || ($row['enota'] == 0 && $diferencial_trak == 1)) ) || ($row['tip'] == 6 && $row['enota'] == 8) ||  ($row['tip'] == 20 && $row['ranking_k'] == 1)) ? ' style="display:none;"' : '';
		

		echo '<div class="drop_grids_num" '.$display_1.'>';
		echo '<p><span class="title" >'.$lang['srv_odgovorov'].':</span>';
		echo '<span class="content"><select name="grids_count" id="grids_count" onChange="change_selectbox_size(\'' . $row['id'] . '\', $(this).val(), \'' . $lang['srv_select_box_vse'] . '\'); change_trak_num_of_titles(\'' . $row['id'] . '\', $(this).val());">';

		// Vedno imamo najmanj 2 grida (drugace so stvari cudne v analizah) - namesto 1 se uporabi navaden radio tip vprasanja
		// Pri number sliderju se rabi 1 (mogoče še kje - npr checkbox itd.... ) analize morajo delati tudi v tem primeru :P
		echo '<option value="1"'.($row['grids']=='1'?' selected':'').'>1</option>';
		echo '<option value="2"'.($row['grids']=='2'?' selected':'').'>2</option>';
		echo '<option value="3"'.($row['grids']=='3'?' selected':'').'>3</option>';
		echo '<option value="4"'.($row['grids']=='4'?' selected':'').'>4</option>';
		echo '<option value="5"'.($row['grids']=='5'?' selected':'').'>5</option>';
		echo '<option value="6"'.($row['grids']=='6'?' selected':'').'>6</option>';
		echo '<option value="7"'.($row['grids']=='7'?' selected':'').'>7</option>';

		if($row['enota'] != 11) {
            echo '<option value="8"' . ($row['grids'] == '8' ? ' selected' : '') . '>8</option>';
            echo '<option value="9"' . ($row['grids'] == '9' ? ' selected' : '') . '>9</option>';
            echo '<option value="10"' . ($row['grids'] == '10' ? ' selected' : '') . '>10</option>';
            echo '<option value="11"' . ($row['grids'] == '11' ? ' selected' : '') . '>11</option>';
            echo '<option value="12"' . ($row['grids'] == '12' ? ' selected' : '') . '>12</option>';
        }
		echo '</select></span>';
		echo '</p>';
		echo '</div>';

        // Slikovni tip
		if($row['tip'] == 6){
		    //if($row['enota'] == 12) {
                $this->edit_custom_picture_radio();
            //}

			$this->edit_trak_tabela();
		}		
		
		// prikaz dropdowna za default vrednosti gridov
		if ($row['tip'] == 6 /*&& $row['grids'] == 5*/){
			//echo '<p class="grid_defaults">';
			echo '<p class="grid_defaults_class" '.$display.'>';
			echo '<span class="title">'.$lang['srv_defaultGrid'].':</span>';
			echo '<span class="content"><select name="grid_defaults" id="grid_defaults" style="width:100px">';
			//echo '<span class="content"><select name="grid_defaults" id="grid_defaults" style="width:100px" onChange="vprasanje_save(true)">';
			echo '<option value="0">'.$lang['s_without'].'</option>';						
			foreach($default_grid_values AS $key => $value){
				echo '<option value="'.$key.'">'.$value['name'].'</option>';
			}
			echo '</select></span>';
			echo '</p>';
		}
		
		if ($row['ranking_k'] != 1){
			$spremenljivkaParams = new enkaParameters($row['params']);
			$grid_var = ($spremenljivkaParams->get('grid_var') ? $spremenljivkaParams->get('grid_var') : 0);
			
			echo '<p class="grid_var_class" '.$display.'><label for="grid_var" class="title">'.$lang['srv_edit_values'].':</label>';
			echo ' '.Help::display('srv_grid_var');
			echo '<span class="content">';
			echo '<input type="hidden" name="grid_var" value="0">';
			echo '<input type="checkbox" id="grid_var" name="grid_var" value="1" '.($grid_var == 1 ? ' checked="checked"' : '').'></span></p>';
		}
	}
	
	/**
	* editiranje podnaslova grida (pri double gridu)
	*/
	function edit_grid_missing () {
		global $lang;
		
		//dodatne missing vrednosti (ne vem, zavrnil...)
		# preberemo iz class.SurveyMissingValues
		$smv = new SurveyMissingValues($this->anketa);
		# katere missinge imamo na voljo
		$missing_values = $smv->GetUnsetValuesForSurvey();
		
		#kateri missingi so nastavljeni
		$already_set_mv = array();
		$sql_grid_mv = sisplet_query("SELECT naslov, other FROM srv_grid WHERE spr_id='".$this->spremenljivka."' AND other != 0");
		while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
			$already_set_mv[$row_grid_mv['other']] = $row_grid_mv['naslov'];
		}
		echo '<p>';
		if (count($missing_values) > 0) {
			foreach ($missing_values AS $mv_key => $mv_text) {
				echo '<input type="checkbox" '.(isset($already_set_mv[$mv_key]) ? ' checked' : '').' name="missing_value_checkbox[]" id="missing_value_'.$mv_key.'" value="'.$mv_key.'" title="'.$mv_text.'""></input> '; // ,\''.$mv_key.'\',\''.$mv_text.'\'
				echo '<label for="missing_value_'.$mv_key.'" class="pointer">'.$mv_text.'</label>';
			}
		}
		echo '</p>';
	}
	
	/**
	* editiranje podnaslova grida (pri double gridu)
	*/
	function edit_grid_subtitle () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$rowA = SurveyInfo::getInstance()->getSurveyRow();
		
		$display = ($row['enota'] == 3 ? '' : 'display: none;');		
		
		
		echo '<div class="grid_subtitle" style="'.$display.'">';
		
		echo '<p><span class="title">'.$lang['srv_grid_subtitle1'].':</span><span class="content"><input type="text" size="23" name="grid_subtitle1" value="'.$row['grid_subtitle1'].'" /></span></p>';
		echo '<p><span class="title">'.$lang['srv_grid_subtitle2'].':</span><span class="content"><input type="text" size="23" name="grid_subtitle2" value="'.$row['grid_subtitle2'].'" /></span></p>';
		
		echo '</div>';
	}	

		
	/**
	* navadno editiranje vrednosti 
	* 
	*/
	function edit_vrednost () {
		global $lang;
		global $admin_type;
		global $default_grid_values;

		$row = Cache::srv_spremenljivka($this->spremenljivka);

		//Če gre za vizualno skalo ali slikovni tip potem opcij ne prikazujemo. Še vedno pa izpišemo, da delujejo če nekdo zamenja tip vprašanaj
        $displayNone = '';
        if(in_array($row['orientation'], [9,11]))
            $displayNone = 'style="display:none";';

		// Prednastavljene vrednosti odgovorov (dropdown)
		if ($row['tip'] == 1) {

			echo '<p class="radio_defaults_class">';
			echo '<span class="title">'.$lang['srv_defaultGrid'].':</span>';
			echo '<span class="content"><select name="radio_defaults" id="radio_defaults" style="width:100px">';
			echo '<option value="0">'.$lang['s_without'].'</option>';
			foreach($default_grid_values AS $key => $value){
				echo '<option value="'.$key.'">'.$value['name'].'</option>';
			}
			echo '</select></span>';
			echo '</p>';
		}

		// inline urejanje variabel vrednosti
		if ( in_array($row['tip'], array(1,2,3))) {
			
			$spremenljivkaParams = new enkaParameters($row['params']);
			$grid_var = ($spremenljivkaParams->get('grid_var') ? $spremenljivkaParams->get('grid_var') : 0);
			
			echo '<p '.$displayNone.'><label for="grid_var" class="title">'.$lang['srv_edit_values'].':</label>';
			echo ' '.Help::display('srv_grid_var');
			echo '<span class="content">';
			echo '<span class="content"><input type="hidden" name="grid_var" value="0" />';
			echo '<input type="checkbox" id="grid_var" name="grid_var" value="1" '.($grid_var == 1 ? 'checked="checked"' : '').'></span></p>';
			
			// Obratni vrstni red vrednosti
			if($row['tip'] == 1 || $row['tip'] == 3){
				
				$reverse_var = ($spremenljivkaParams->get('reverse_var') ? $spremenljivkaParams->get('reverse_var') : 0);
								
				echo '<p '.$displayNone.'><label for="reverse_var" class="title">'.$lang['srv_reverse_values'].':</label>';
				echo '<span class="content">';
				echo '<span class="content"><input type="hidden" name="reverse_var" value="0" />';
				echo '<input type="checkbox" '.($reverse_var == '1' ? ' checked="checked" ': '').' value="1" id="reverse_var" name="reverse_var"></span></p>';			
			}
		}

		// besedilo za vsoto
		if ($row['tip'] == 18) {
			//echo '<div style="width:60%; height: 1px; border-top: 1px black solid;"></div>';

			if($row['vsota'] == '')
				$vsotaText = $lang['srv_vsota_text'];
			else
				$vsotaText = $row['vsota'];

			// echo '<p id="vrednost_'.$row['id'].'" class="vrednost_vsota">';
			// echo '<textarea name="vsota">'.$vsotaText.'</textarea>';
			// echo '</p>';
		}


            echo '<p '.$displayNone.'><span onclick="vrednost_fastadd(\''.$this->spremenljivka.'\'); return false;" class="pointer"><span class="sprites paste_word"></span> ';

            if ( in_array($row['tip'], array(6, 16, 19, 20)) )
                echo $lang['srv_question_fastadd'].' '.Help::display('srv_question_fastadd');
            else
                echo $lang['srv_vrednost_fastadd'].' '.Help::display('srv_vrednost_fastadd');

            echo '</span></p>';

		
		// inline hitro dodajanje slik - zaenkrat samo radio in checkbox - v testiranju, zato samo za admine
		if (in_array($row['tip'], array(1,2)) && $row['orientation'] == 1) {
			
			$spremenljivkaParams = new enkaParameters($row['params']);
			$quickImage = ($spremenljivkaParams->get('quickImage') ? $spremenljivkaParams->get('quickImage') : 0);
			
			echo '<p><label for="quickImage" class="title">'.$lang['srv_edit_quick_image'].':</label>';
			echo '<span class="content">';
			echo '<span class="content"><input type="hidden" value="0" name="quickImage" />';
			echo '<input type="checkbox" id="quickImage" name="quickImage" value="1" '.($quickImage == 1 ? ' checked="checked"' : '').'></span></p>';
		}
	}
	
	/**
	* Urejanje manjkajocih vrednosti
	*
	*/
	function edit_missing () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		if ($row['tip'] <= 3) {
			
			//echo '<p class="vrednost_new">';
			echo '<p>';
		
			//dodatne missing vrednosti (ne vem, zavrnil...)
			# preberemo iz class.SurveyMissingValues
			$smv = new SurveyMissingValues($this->anketa);
			# katere missinge imamo na voljo
			$missing_values = $smv->GetUnsetValuesForSurvey();
			
			#kateri missingi so nastavljeni
			$already_set_mv = array();
			$sql_grid_mv = sisplet_query("SELECT naslov, other FROM srv_vrednost WHERE spr_id='".$this->spremenljivka."' AND other != 0");
			while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
				$already_set_mv[$row_grid_mv['other']] = $row_grid_mv['naslov'];
			}
			//echo '<span class="floatRight">';
			if (count($missing_values) > 0) {
				
				foreach ($missing_values AS $mv_key => $mv_text) {
					echo '<span class="floatLeft">';
					echo '<input type="checkbox" '.(isset($already_set_mv[$mv_key]) ? ' checked="checked"' : '').' onChange="vrednost_new_dodatne(\'' . $row['id'] . '\', \''.$mv_key.'\', \''.$row['tip'].'\', this.checked); show_alert_missing();" id="missing_value_'.$mv_key.'">';
					echo '<label for="missing_value_'.$mv_key.'" class="pointer">'. $mv_text . '</label> ';
					//echo '<br/>';
					echo '</span>';
				}
			}
			//echo '</span>';
			
			echo '<span class="clr"></span>';
		
			//echo '</p></div>';
			echo '</p>';
		
			// naknaden prikaz missinga ne vem ob opozorilu (samo ce imamo vklopljeno opozorilo in missing ne vem)
			if ($row['tip'] <= 2)
				$this->edit_alert_show_missing();
		}
	}
	
	/**
	* vrstica vrednosti
	* 
	*/
	function edit_vrednost_li ($vrednost, $row=null) {
				
		if ($row == null) {
			$sql = sisplet_query("SELECT * FROM srv_vrednost WHERE id = '$vrednost'");
			$row = mysqli_fetch_array($sql);
		}
		
		echo '<li id="vrednost_'.$vrednost.'" '.($row['other'] == 1 ? 'class="li_other"' : '').'>';
		$this->edit_vrednost_li_content($vrednost, $row);
		echo '</li>';	
	}
	
	/**
	* ta je locena od zgornje funkcije, ker se z ajaxom refresha samo vsebina (pri urejanju vrstice)
	* 
	*/
	function edit_vrednost_li_content($vrednost, $row=null) {
		global $lang;
		
		$rowS = Cache::srv_spremenljivka($this->spremenljivka);
	
		if($rowS['tip'] == 6 || $rowS['tip'] == 16 || $rowS['tip'] == 19 || $rowS['tip'] == 20){
			$spremenljivkaParams = new enkaParameters($rowS['params']);	
			$gridWidth = ($spremenljivkaParams->get('gridWidth') ? $spremenljivkaParams->get('gridWidth') : 20);
			$gridWidth = ($gridWidth == -1 ? 20 : $gridWidth);
		}
		else
			$gridWidth = 42;
		
		if ($row == null) {
			$sql = sisplet_query("SELECT id, naslov, naslov2, variable, other, if_id, random FROM srv_vrednost WHERE id = '$vrednost'");
			$row = mysqli_fetch_array($sql);
		}
				
		echo ' <span class="faicon move_updown move"></span> <textarea name="vrednost_naslov_'.$row['id'].'" id="'.$row['variable'].'" class="vrednost_textarea" style="width:'.$gridWidth.'%;">'.$row['naslov'].'</textarea> ';
		echo '['.$row['variable'].'] <span class="faicon edit2 pointer" onclick="vrednost_edit(\''.$row['id'].'\'); return false;"></span> <span class="faicon delete_circle icon-orange_link" onclick="vrednost_delete(\''.$rowS['id'].'\', \''.$row['id'].'\', \''.$rowS['tip'].'\', \''.$row['variable'].'\');"></span>';
		if ($row['other'] == 1) echo ' <input type="text" disabled style="width:40px" />';
		
		if ($row['if_id'] > 0) {
			echo ' * ';
			$b = new Branching($this->anketa);
			if ($b->condition_check($row['if_id']) != 0)
				echo ' <span class="faicon warning icon-orange" title="'.$lang['srv_check_pogoji_spremenljivka'].'"></span>';
		}
		
		//polje pri diferencialu
		if($rowS['tip'] == 6 && $rowS['enota'] == 1){
			echo '<textarea name="vrednost_naslov2_'.$row['id'].'" id="'.$row['variable'].'_2" style="float: right; width:20%">'.$row['naslov2'].'</textarea>';
		}
		
		switch ($row['random']) {
			//case 0 : echo $lang['srv_random_off'];
			//break;
			case 1 : echo ' '.$lang['srv_random_on'];
			break;
			case 2 : echo ' '.$lang['srv_sort_asc'];
			break;
			case 3 : echo ' '.$lang['srv_sort_desc'];
			break;
			
		}
	}
	
	/**
	* urejanje vrednosti, ki se odpre v popupu
	* 
	*/
	function vrednost_edit ($vrednost) {
		global $lang;
		
		$lang_id = $_POST['lang_id'];
		
		$sql = sisplet_query("SELECT id, spr_id, variable, naslov, random, other, if_id FROM srv_vrednost WHERE id = '$vrednost'");
		$row = mysqli_fetch_array($sql);
		
		$rows = Cache::srv_spremenljivka($row['spr_id']);
		
		echo '<form name="vrednost_edit" onsubmit="vrednost_save(); return false;">';

		echo '<input type="hidden" name="anketa" value="'.$this->anketa.'">';
		echo '<input type="hidden" name="spremenljivka" value="'.$row['spr_id'].'">';
		echo '<input type="hidden" name="vrednost" value="'.$vrednost.'">';
		echo '<input type="hidden" name="lang_id" value="'.$lang_id.'">';
		
		
		if ($lang_id > 0) {
			include_once('../../main/survey/app/global_function.php');
			new \App\Controllers\SurveyController(true);
			save('lang_id', $lang_id);

			$naslov = \App\Controllers\LanguageController::getInstance()->srv_language_vrednost($vrednost);
			if ($naslov != '') $row['naslov'] = $naslov;
			echo '<p>'.$lang['srv_vprasanje_text'].': <textarea id="vrednost_naslov" name="vrednost_naslov" style="width:99%">'.$row['naslov'].'</textarea></p>';
			?><script>
				create_editor('vrednost_naslov', false); 
			</script><?
            
            echo '<span class="buttonwrapper spaceLeft floatRight">';
	        echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="vrednost_save_lang(\''.$vrednost.'\'); return false;"><span>'.$lang['srv_potrdi'].'</span></a>';
            echo '</span>';	
            
            echo '<span class="buttonwrapper spaceLeft floatRight">';
            echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_cancel(); return false;"><span>'.$lang['srv_close_profile'].'</span></a>'."\n\r";
            echo '</span>';		
        } 
        else {
            echo '<div class="popup_close"><a href="#" onClick="vrednost_cancel(); return false;">✕</a></div>';

            echo '<h2>'.$lang['srv_kategorije_odgovorov_title'].'</h2>';
			
			echo '<div class="left-float"><span class="title">'.(in_array($rows['tip'], array(2,6,16,19,20))?$lang['srv_spremenljivka']:$lang['srv_vrednost']).':</span> <input type="text" name="vrednost_variable" value="'.$row['variable'].'" '.(in_array($rows['tip'], array(2,6,16,19,20)) ? ' onkeyup="vprasanje_check_variable(this);"' : '').'></div>';
            
			// RAZVRSTI odgovore
            echo '<div class="left-float"><span class="title">'.$lang['sort'].':</span> ';
            echo '<select name="vrednost_random">';
            echo '<option value="0"'.($row['random']==0?' selected':'').'>'.$lang['srv_random_off2'].'</option>';
            echo '<option value="1"'.($row['random']==1?' selected':'').'>'.$lang['srv_random_on2'].'</option>';
            echo '<option value="2"'.($row['random']==2?' selected':'').'>'.$lang['srv_sort_asc2'].'</option>';
            echo '<option value="3"'.($row['random']==3?' selected':'').'>'.$lang['srv_sort_desc2'].'</option>';
            echo '</select>';
            echo '</div>';

			echo '<div style="clear:both;"></div>';
            
			echo '<div>'.$lang['srv_vprasanje_text'].' <span onmouseover="$(this).hide(); create_editor(\'vrednost_naslov\');" class="red pointer"> - '.$lang['srv_editor'].'</span>: <textarea id="vrednost_naslov" name="vrednost_naslov" style="width:99%">'.$row['naslov'].'</textarea></div>';
						
			echo '<p>';
			if ($row['if_id'] > 0) {
				echo $lang['srv_podif_edit'].': ';
                echo '<a href="#" onclick="vrednost_condition_editing(\''.$vrednost.'\'); return false;">';
				$b = new Branching($this->anketa);
				$b->conditions_display($row['if_id'], 0, 1);
                echo '</a>';
			} else {
				echo  $lang['srv_podif_new'].': ';
                echo '<span class="faicon odg_if_not inline inline_if_not" onclick="vrednost_condition_editing(\''.$vrednost.'\'); return false;" title="'.$lang['srv_podif_edit'].'" style="cursor: pointer;"></span>';
            }
			echo '</p>';
            
			echo '<span class="buttonwrapper spaceLeft floatRight">';
	        echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="vrednost_save(\''.$vrednost.'\'); return false;"><span>'.$lang['srv_potrdi'].'</span></a>';
            echo '</span>';
            
            echo '<span class="buttonwrapper spaceLeft floatRight">';
            echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_cancel(); return false;"><span>'.$lang['srv_close_profile'].'</span></a>'."\n\r";
            echo '</span>';
		}

		echo '</form>';		
	}
	
	/**
	* hiter upload slike, ki se odpre v popupu - V DELU
	* 
	*/
	function vrednost_insert_image ($vrednost) {
		global $lang;
		
		$lang_id = $_POST['lang_id'];
		
		$sql = sisplet_query("SELECT spr_id, naslov, variable FROM srv_vrednost WHERE id = '$vrednost'");
		$row = mysqli_fetch_array($sql);

		echo '<form name="vrednost_insert_image_form" onsubmit="vrednost_insert_image_save(); return false;">';

			echo '<input type="hidden" name="anketa" value="'.$this->anketa.'">';
			echo '<input type="hidden" name="spremenljivka" value="'.$row['spr_id'].'">';
			echo '<input type="hidden" name="vrednost" value="'.$vrednost.'">';
			echo '<input type="hidden" name="vrednost_variable" value="'.$row['variable'].'">';
			echo '<input type="hidden" name="lang_id" value="'.$lang_id.'">';

			// Textovno polje in naložena vsebina
			echo '<br />';
			echo '<textarea name="vrednost_naslov" id="hitro-nalaganje-slike">'.$row['naslov'].'</textarea>';

			echo '<br /><br />';

			echo '<span class="buttonwrapper spaceRight floatLeft">';
			echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_insert_image_save ('.$vrednost.'); return false;"><span>'.$lang['srv_potrdi'].'</span></a>'."\n\r";
			echo '</span>';
			echo '<span class="buttonwrapper spaceRight floatLeft">';
			echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_cancel(); return false;"><span>'.$lang['srv_close_profile'].'</span></a>'."\n\r";
			echo '</span>';

		echo '</form>';
	}
	
	/**
	* urejanje slike in obmocij za hotspot, ki se odpre v popupu
	* 
	*/

	function hotspot_edit ($spr_id) {
		global $lang;
		
		$lang_id = $_POST['lang_id'];
		$spr_id = $_POST['spr_id'];
		
		//$sql = sisplet_query("SELECT * FROM srv_vrednost WHERE id = '$vrednost'");
		$sql = sisplet_query("SELECT id, tip, enota, params, orientation, design FROM srv_spremenljivka WHERE id = '$spr_id'");
		$row = mysqli_fetch_array($sql);
		
		$spremenljivkaParams = new enkaParameters($row['params']);
		$hotspot_image = ($spremenljivkaParams->get('hotspot_image') ? $spremenljivkaParams->get('hotspot_image') : "");
		//$rows = Cache::srv_spremenljivka($row['spr_id']);
		
		if($row['tip'] == 1 || $row['tip'] == 2){
			$enota_orientation = $row['orientation'];			
		}else if($row['tip'] == 6){
			$enota_orientation = $row['enota'];
		}else if($row['tip'] == 17){
			$enota_orientation = $row['design'];
		}
		
		
		echo '<form name="hotspot_image_edit" onsubmit="hotspot_image_save(); return false;">';

			echo '<input type="hidden" name="spremenljivka" value="'.$spr_id.'">';
			
			echo '<div style="clear:both;"></div>';
			
			//izris editorja s sliko
			echo '<div><textarea id="hotspot_image" name="hotspot_image" style="width:99%">'.$hotspot_image.'</textarea></div>';
			//izris editorja s sliko - konec
			
			echo '<br />';
			
			//gumb Potrdi
			echo '<span class="buttonwrapper spaceRight floatLeft">';
			echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="hotspot_image_save(\''.$row['id'].'\'); hotspot_image_resize(\''.$row['id'].'\'); show_hot_spot_settings_from_editor (\''.$row['id'].'\', \''.$enota_orientation.'\', \''.$row['tip'].'\'); hotspot_image_button_update(\''.$row['id'].'\', \''.$lang['srv_hot_spot_load_image'].'\', \''.$lang['srv_hot_spot_edit_image'].'\'); return false; "><span>'.$lang['srv_potrdi'].'</span></a>';						
			echo '</span>';
			//gumb Potrdi - konec

			//gumb Zapri
			echo '<span class="buttonwrapper spaceRight floatLeft">';
			echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_cancel(); return false;"><span>'.$lang['srv_close_profile'].'</span></a>'."\n\r";
			echo '</span>';
			//gumb Zapri - konec
		
		echo '</form>';
		
	}
	
	function hotspot_edit_regions () {
		global $lang;
		
		$lang_id = $_POST['lang_id'];

		$vrednost = $_POST['vrednost'];
		$sql = sisplet_query("SELECT id, spr_id, naslov FROM srv_vrednost WHERE id = '$vrednost'");
		$row = mysqli_fetch_array($sql);
		
		$rows = Cache::srv_spremenljivka($row['spr_id']);
		$spremenljivkaParams = new enkaParameters($rows['params']);
		$hotspot_image = ($spremenljivkaParams->get('hotspot_image') ? $spremenljivkaParams->get('hotspot_image') : "");
		
		$src_image = $_POST['src_image'];
		$hotspot_image_height = $_POST['hotspot_image_height'];
		$hotspot_image_width = $_POST['hotspot_image_width'];
		$spr_id = $rows['id'];
		//$region_name = $_POST['region_name'];
		
		//echo $vrednost;
		//poberi iz baze, kateri je zadnji vneseni indeks obmocja $last_hotspot_region_index
		//$sqlR = sisplet_query("SELECT * FROM srv_hotspot_regions WHERE spr_id = $spr_id order by region_index DESC LIMIT 1");
		$sqlR = sisplet_query("SELECT region_index FROM srv_hotspot_regions WHERE spr_id = $spr_id order by region_index DESC LIMIT 1");
		//$sqlR2 = sisplet_query("SELECT * FROM srv_hotspot_regions WHERE spr_id = $spr_id AND vre_id = $vrednost");
		$sqlR2 = sisplet_query("SELECT region_coords, region_name, region_index FROM srv_hotspot_regions WHERE spr_id = $spr_id AND vre_id = $vrednost");
		//$sqlR3 = sisplet_query("SELECT * FROM srv_hotspot_regions WHERE spr_id= $spr_id ");
		$sqlR3 = sisplet_query("SELECT region_coords, vre_id FROM srv_hotspot_regions WHERE spr_id= $spr_id ");
		
		$rowR = mysqli_fetch_array($sqlR);
		$rowR2 = mysqli_fetch_array($sqlR2);

		if(mysqli_num_rows($sqlR) == 0){
			$last_hotspot_region_index = -1;
		}else{
			$last_hotspot_region_index = $rowR['region_index'];
		}
		
		if(mysqli_num_rows($sqlR2) != 0){
			$hotspot_image_coords = $rowR2['region_coords'];
			$region_name = $rowR2['region_name'];
			$hotspot_region_index = $rowR2['region_index'];
		}else{
			$region_name = "";
			$hotspot_region_index = -2;
		}


		echo '<form name="hotspot_region_edit" onsubmit="hotspot_save_regions(); return false;">';
			//echo '$last_hotspot_region_index: '.$last_hotspot_region_index;
			//echo '$hotspot_region_index: '.$hotspot_region_index;
			
			echo '<input type="hidden" name="anketa" value="'.$this->anketa.'">';
			echo '<input type="hidden" name="spremenljivka" value="'.$row['spr_id'].'">';
			echo '<input type="hidden" name="vrednost" value="'.$vrednost.'">';
			echo '<input type="hidden" name="lang_id" value="'.$lang_id.'">';

			echo '<div><p id="slika_'.$row['spr_id'].'" style="width:99%; display: none;">'.$row['naslov'].'</p></div>';
			
			echo '<div id="slika_'.$row['spr_id'].'_container" >';

				//********* za prikazovanje obstojecih obmocij @ urejanju/dodajanju novega obmocja ********

				$findme = 'img';
				$pos = strpos($hotspot_image, $findme);
				if($pos === false) {	//string NOT present
					
				}
				else {	//string present
					$usemap = 'id="hotspot_'.$row['id'].'_image" usemap="#hotspot_'.$row['id'].'_usemap" style="z-index: 1; height:'.$hotspot_image_height.'px; width: '.$hotspot_image_width.'px; position: relative; top: 15px;"';	//z-index: 1, da bo slika pod canvas in prave dimenzije in na pravi poziciji
					//v $hotspot_image je potrebno dodati usemap="#hotspot_image_'.$row['id'].'" za identificiranje mape
					$hotspot_image = substr_replace($hotspot_image, $usemap, 5, 0);	//dodaj zeleni string v $hotspot_image
				}
				
				//prikaz slike
				echo $hotspot_image;
				
				//ureditev map
				if(mysqli_num_rows($sqlR3) != 0){	//ce je kaksno obmocje v bazi
					echo '<map id="hotspot_'.$row['id'].'_map" name="hotspot_'.$row['id'].'_usemap">';
						while ($rowR3 = mysqli_fetch_array($sqlR3)) {
							echo '<area coords="'.$rowR3['region_coords'].'" name="'.$rowR3['vre_id'].'" shape="poly" href="#">';
						}
					echo '</map>';
				}
				
				?>					
				<script>						
					$(document).ready(function () {
						mapinit_editor(<?=$row['id']?>);	//uredi delovanje imagemapster in prikazovanja obmocij ter tooltip-ov							
					});
				</script>
				<?
				
				//********* za prikazovanje obstojecih obmocij @ urejanju/dodajanju novega obmocja - konec *********

				echo '<div style="clear:both;"></div>';
								
				echo '
					<script language="javascript" type="text/javascript" src="./script/jquery/jquery.canvasAreaDraw.js"></script>
					<textarea id="hotspot_region_coords_'.$row['spr_id'].'" style="display: none;" rows=3 name="hotspot_region_coords_'.$row['spr_id'].'" class="canvas-area" disabled 
						placeholder="Shape Coordinates" 
						data-image-url="'.$src_image.'" data-canvas-id="canvas_'.$row['spr_id'].'" data-spr-id="'.$row['spr_id'].'" image-height="'.$hotspot_image_height.'" image-width="'.$hotspot_image_width.'" image-coords="'.$hotspot_image_coords.'" clear_button="'.$lang['srv_hotspot_clear_region_points'].'"></textarea>			
				';
				
			echo '</div>';

			echo '<br />'; 
			

			//polje za vnos imena obmocja
			echo '<span class="buttonwrapper spaceRight">';
			echo $lang['srv_hot_spot_region_name'].': ';
			echo '<input name="hotspot_region_name" value="'.$region_name.'">';
			echo '</span>';
			//polje za vnos imena obmocja - konec
			
			echo '<br />';
			echo '<br />';
			

			//gumb Potrdi
			echo '<span class="buttonwrapper spaceRight floatLeft">';
			echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="hotspot_save_regions('.$row['spr_id'].', '.$last_hotspot_region_index.', '.$vrednost.', '.$hotspot_region_index.'); return false; "><span>'.$lang['srv_potrdi'].'</span></a>';	        
			echo '</span>';
			//gumb Potrdi - konec

			//gumb Zapri - konec
			echo '<span class="buttonwrapper spaceRight floatLeft">';
			echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="hotspot_region_cancel('.$row['spr_id'].', '.$vrednost.'); vrednost_cancel(); return false;"><span>'.$lang['srv_close_profile'].'</span></a>'."\n\r";
			echo '</span>';
			//gumb Zapri - konec

			//tekst z navodili
			echo '<br />';
			echo '<br />'.$lang['srv_hotspot_edit_region_msg'];
			echo '<div id="hotspot_tips_'.$row['spr_id'].'" style="display:none">'.$lang['srv_hotspot_edit_region_tip_delete'].' <br />
				'.$lang['srv_hotspot_edit_region_tip_move'].'</div>';				
			//tekst z navodili - konec
			
		
		echo '</form>';
	}	
	
	/**
	* hitro dodajanje vrednosti preko textarea
	* 
	*/
	function vrednost_fastadd() {
		global $lang;
		
        echo '<h2>'.$lang['srv_vrednost_fastadd'].'</h2>';
        
        echo '<div class="popup_close"><a href="#" onClick="vrednost_cancel(); return false;">✕</a></div>';
		
		echo '<form name="vrednost_fastadd_form" onsubmit="vrednost_fastadd_save(); return false;">';
		
		echo '<input type="hidden" name="anketa" value="'.$this->anketa.'" />';
		echo '<input type="hidden" name="spremenljivka" value="'.$this->spremenljivka.'" />';
		
		echo '<p><textarea name="fastadd" style="width:99%; height:100px"></textarea></p>';
		
        echo '<p>'.$lang['srv_vrednost_fastadd_txt'].'</p>';
        
        echo '<span class="buttonwrapper floatRight">';
        echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="vrednost_fastadd_save(\''.$this->spremenljivka.'\'); return false;"><span>'.$lang['srv_potrdi'].'</span></a>';
        echo '</span>';

		echo '<span class="buttonwrapper spaceRight floatRight">';
        echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vrednost_cancel(); return false;"><span>'.$lang['srv_close_profile'].'</span></a>'."\n\r";
        echo '</span>'; 
		
		echo '</form>';
		
		?><script> $('textarea[name=fastadd]').focus(); </script><?php
	}
	
	//editiranje vrednosti pri besedilu
	function edit_vrednost_besedilo(){
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		
		$taSize = ($spremenljivkaParams->get('taSize') ? $spremenljivkaParams->get('taSize') : 1);
		$taWidth = ($spremenljivkaParams->get('taWidth') ? $spremenljivkaParams->get('taWidth') : -1);
                
		//default sirina
		if($taWidth == -1)
			$taWidth = 30;
		
		echo '<input type="hidden" name="edit_vrednost_besedilo" value="1" />';
		
		# manjkajoče vrednosti 
		//dodatne missing vrednosti (ne vem, zavrnil...)
		# preberemo iz class.SurveyMissingValues
		$smv = new SurveyMissingValues($this->anketa);
		# katere missinge imamo na voljo
		$missing_values = $smv->GetUnsetValuesForSurvey();
		
		#kateri missingi so nastavljeni
		$already_set_mv = array();
		$sql_grid_mv = sisplet_query("SELECT naslov, other FROM srv_vrednost WHERE spr_id='".$this->spremenljivka."' AND other != 0");
		while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
			$already_set_mv[$row_grid_mv['other']] = $row_grid_mv['naslov'];
		}
		

		echo '<p>';

		if (count($missing_values) > 0) {
			foreach ($missing_values AS $mv_key => $mv_text) {
				echo '<input type="checkbox" '.(isset($already_set_mv[$mv_key]) ? ' checked="checked"' : '').' onChange="vrednost_new_dodatne(\'' . $row['id'] . '\', \''.$mv_key.'\', \''.$row['tip'].'\', this.checked); show_alert_missing();" id="missing_value_'.$mv_key.'">';
				echo '<label for="missing_value_'.$mv_key.'" class="pointer">'.$mv_text.'</label>';
			}
		}
		echo '</p>';
	}
	
	//editiranje vrednosti pri number
	function edit_vrednost_number(){
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);		
		$spremenljivkaParams = new enkaParameters($row['params']);	
		
		$taWidth = ($spremenljivkaParams->get('taWidth') ? $spremenljivkaParams->get('taWidth') : -1);
		//default sirina
		if($taWidth == -1)
			$taWidth = 10;
		
		echo '<input type="hidden" name="edit_vrednost_number" value="1" />';
		
			
		//dodatne missing vrednosti (ne vem, zavrnil...)
		# preberemo iz class.SurveyMissingValues
		$smv = new SurveyMissingValues($this->anketa);
		# katere missinge imamo na voljo
		$missing_values = $smv->GetUnsetValuesForSurvey();
		
		#kateri missingi so nastavljeni
		$already_set_mv = array();
		$sql_grid_mv = sisplet_query("SELECT naslov, other FROM srv_vrednost WHERE spr_id='".$this->spremenljivka."' AND other != 0");
		while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
			$already_set_mv[$row_grid_mv['other']] = $row_grid_mv['naslov'];
		}
		

		echo '<p>';

		if (count($missing_values) > 0) {
			foreach ($missing_values AS $mv_key => $mv_text) {
				echo '<input type="checkbox" '.(isset($already_set_mv[$mv_key]) ? ' checked="checked"' : '').' onChange="vrednost_new_dodatne(\'' . $row['id'] . '\', \''.$mv_key.'\', \''.$row['tip'].'\', this.checked); show_alert_missing();" id="missing_value_'.$mv_key.'">';
				echo '<label for="missing_value_'.$mv_key.'" class="pointer">'.$mv_text.'</label>';
			}
		}
		echo '</p>';
	}
	
	//editiranje vrednosti pri datumu
	function edit_vrednost_datum(){
		global $lang;
				
		$row = Cache::srv_spremenljivka($this->spremenljivka);		
		$spremenljivkaParams = new enkaParameters($row['params']);	
		
		# manjkajoče vrednosti 
		
		//dodatne missing vrednosti (ne vem, zavrnil...)
		# preberemo iz class.SurveyMissingValues
		$smv = new SurveyMissingValues($this->anketa);
		# katere missinge imamo na voljo
		$missing_values = $smv->GetUnsetValuesForSurvey();
		
		#kateri missingi so nastavljeni
		$already_set_mv = array();
		$sql_grid_mv = sisplet_query("SELECT naslov, other FROM srv_vrednost WHERE spr_id='".$this->spremenljivka."' AND other != 0");
		while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
			$already_set_mv[$row_grid_mv['other']] = $row_grid_mv['naslov'];
		}
		

		echo '<p>';

		if (count($missing_values) > 0) {
			foreach ($missing_values AS $mv_key => $mv_text) {
				echo '<input type="checkbox" '.(isset($already_set_mv[$mv_key]) ? ' checked="checked"' : '').' onChange="vrednost_new_dodatne(\'' . $row['id'] . '\', \''.$mv_key.'\', \''.$row['tip'].'\', this.checked); show_alert_missing();" id="missing_value_'.$mv_key.'">';
				echo '<label for="missing_value_'.$mv_key.'" class="pointer">'.$mv_text.'</label>';
			}
		}
		echo '</p>';

	}
	
	function edit_grid_subtype(){
		global $lang;
		global $admin_type;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);


		$prikazi_vas_ali_slikovni_tip = 'show_custom_picture_radio(\''.$row['id'].'\', this.value);';
				
		echo '<p><span class="title">'.$lang['srv_orientacija'].':</span>';
		//echo '<span class="content"><select id="spremenljivka_podtip_' . $row['id'] . '" spr_id="'.$row['id'].'" name="enota" onChange="change_diferencial(\'' . $row['id'] . '\', this.value); show_selectbox_size(\'' . $row['id'] . '\', this.value, \'' . $row['tip'] . '\'); show_nastavitve_tabela_da_ne(\'' . $row['id'] . '\', this.value); max_diff_labels(\'' . $row['id'] . '\', this.value, \'' . $lang['srv_maxdiff_label1'] . '\',\'' . $lang['srv_maxdiff_label2'] . '\',\'' . $lang['srv_new_grid'] . '\'); show_hot_spot_settings(\'' . $row['id'] . '\', this.value, \'' . $row['tip'] . '\'); show_preset_value(\'' . $row['id'] . '\', this.value, \'' . $row['tip'] . '\'); show_drag_and_drop_new_look_option(\'' . $row['id'] . '\', this.value);">';
		echo '<span class="content"><select id="spremenljivka_podtip_' . $row['id'] . '" spr_id="'.$row['id'].'" name="enota" onChange="change_diferencial(\'' . $row['id'] . '\', this.value); show_selectbox_size(\'' . $row['id'] . '\', this.value, \'' . $row['tip'] . '\'); show_nastavitve_tabela_da_ne(\'' . $row['id'] . '\', this.value); max_diff_labels(\'' . $row['id'] . '\', this.value, \'' . $lang['srv_maxdiff_label1'] . '\',\'' . $lang['srv_maxdiff_label2'] . '\',\'' . $lang['srv_new_grid'] . '\'); show_hot_spot_settings(\'' . $row['id'] . '\', this.value, \'' . $row['tip'] . '\'); show_preset_value(\'' . $row['id'] . '\', this.value, \'' . $row['tip'] . '\'); show_drag_and_drop_new_look_option(\'' . $row['id'] . '\', this.value); '.$prikazi_vas_ali_slikovni_tip.'">';
		
			echo '<option value="0" '.(($row['enota'] == 0) ? ' selected="true" ' : '').'>'.$lang['srv_classic'].'</option>';		
			//te izbire niso mozne pri multicheckboxu
			if($row['tip'] == 6){
				echo '<option value="1" '.(($row['enota'] == 1) ? ' selected="true" ' : '').'>'.$lang['srv_diferencial2'].'</option>';
				echo '<option value="2" '.(($row['enota'] == 2) ? ' selected="true" ' : '').'>'.$lang['srv_dropdown'].'</option>';
				echo '<option value="4" '.(($row['enota'] == 4) ? ' selected="true" ' : '').'>'.$lang['srv_one_against_another'].'</option>';
				echo '<option value="5" '.(($row['enota'] == 5) ? ' selected="true" ' : '').'>'.$lang['srv_max_diff'].'</option>';
				echo '<option value="8"' . ($row['enota'] == 8 ? ' selected="true"' : '') . '>'.$lang['srv_orientacija_tabela_da_ne'].'</option>';
				//echo '<option value="6" '.(($row['enota'] == 6) ? ' selected="true" ' : '').'>'.$lang['srv_select-box_radio'].'</option>';
				//echo '<option value="9"' . ($row['enota'] == 9 ? ' selected="true"' : '') . '>'.$lang['srv_drag_drop'].'</option>';
				echo '<option value="10"' . ($row['enota'] == 10 ? ' selected="true"' : '') . '>'.$lang['srv_hot_spot'].'</option>';
                echo '<option value="11"' . ($row['enota'] == 11 ? ' selected="true"' : '') . '>'.$lang['srv_visual_analog_scale'].'</option>';
                echo '<option value="12"' . ($row['enota'] == 12 ? ' selected="true"' : '') . '>'.$lang['srv_custom-picture_radio'].'</option>';
			}
			
			# dvonji grid je na voljo samo za mgrid, dokler se ne uredi še za checkbox - CHECKBOX DELA VREDU?
			//if($row['tip'] == 6){
			echo '<option value="3" '.(($row['enota'] == 3) ? ' selected="true" ' : '').'>'.$lang['srv_double_grid'].'</option>';
			echo '<option value="6" '.(($row['enota'] == 6) ? ' selected="true" ' : '').'>'.$lang['srv_select-box_radio'].'</option>';
			echo '<option value="9"' . ($row['enota'] == 9 ? ' selected="true"' : '') . '>'.$lang['srv_drag_drop'].'</option>';	
			//}
			if ($admin_type == 0){
				//echo '<option value="6" '.(($row['enota'] == 6) ? ' selected="true" ' : '').'>'.$lang['srv_select-box_radio'].'</option>';
				//echo '<option value="9"' . ($row['enota'] == 9 ? ' selected="true"' : '') . '>'.$lang['srv_drag_drop'].'</option>';
				if($row['tip'] == 6){
					//echo '<option value="10"' . ($row['enota'] == 10 ? ' selected="true"' : '') . '>'.$lang['srv_hot_spot'].'</option>';
				}
			}
			
		echo '</select>';
		echo '</span></p>';	
	}
	
	function edit_grid_dynamic () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		// dinamicen multigrid
		if($row['tip'] == 6){
			echo '<p><label class="title">'.$lang['srv_dynamic_multigrid'].':</label>';
			echo '<span class="content"><input type="hidden" name="dynamic_mg" value="0" />';

			echo '<select id="dynamic_mg" name="dynamic_mg" onChange="onchange_submit_show(this.value);">';	
				echo '<option value="0" '.(($row['dynamic_mg'] == 0) ? ' selected="true" ' : '').'>'.$lang['no'].'</option>';
				echo '<option value="1" '.(($row['dynamic_mg'] == 1) ? ' selected="true" ' : '').'>'.$lang['srv_orientacija_horizontalna_3'].'</option>';
				echo '<option value="3" '.(($row['dynamic_mg'] == 3) ? ' selected="true" ' : '').'>'.$lang['srv_orientacija_horizontalna_3'].'_2</option>';		
				echo '<option value="5" '.(($row['dynamic_mg'] == 5) ? ' selected="true" ' : '').'>'.$lang['srv_orientacija_horizontalna_3'].'_3</option>';
				echo '<option value="2" '.(($row['dynamic_mg'] == 2) ? ' selected="true" ' : '').'>'.$lang['srv_orientacija_vertikalna'].'</option>';
				echo '<option value="4" '.(($row['dynamic_mg'] == 4) ? ' selected="true" ' : '').'>'.$lang['srv_orientacija_vertikalna'].'_2</option>';
				echo '<option value="6" '.(($row['dynamic_mg'] == 6) ? ' selected="true" ' : '').'>'.$lang['srv_orientacija_vertikalna'].'_3</option>';
			echo '</select>';	
			
			//echo '<input type="checkbox" id="dynamic_mg" name="dynamic_mg" value="1" '.($row['dynamic_mg'] == '1' ?'  checked' : '').'></span>';	
			echo '</span></p>';
		}
	}
	
	// nastavitev reminderja
	function edit_reminder() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		echo '<p>';
		echo '<span class="title">'.$lang['srv_reminder'].':</span>';
		echo '<span class="content"><select name="reminder" id="reminder" onChange="show_alert_missing();">';
			echo '<option value="0" '.(($row['reminder'] == 0) ? ' selected="true" ' : '').'>'.$lang['srv_reminder_off'].'</option>';		
			echo '<option value="1" '.(($row['reminder'] == 1) ? ' selected="true" ' : '').'>'.$lang['srv_reminder_soft'].'</option>';
			echo '<option value="2" '.(($row['reminder'] == 2) ? ' selected="true" ' : '').'>'.$lang['srv_reminder_hard'].'</option>';
		echo '</select></span>';
		echo '</p>';
	}
	
	// Nastavitev za naknaden prikaz odgovora ne vem
	function edit_alert_show_missing(){
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		if($row['reminder'] == 0)
			$show = array('97'=>false, '98'=>false, '99'=>false);
		else
			$show = array('97'=>true, '98'=>true, '99'=>true);	

		// Imamo missing v gridu
		if(in_array($row['tip'], array(6,16,19,20,24))){
			
			$already_set_mv = array();
			$sql_grid_mv = sisplet_query("SELECT naslov, other FROM srv_grid WHERE spr_id='".$this->spremenljivka."' AND other != 0");
			while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
				$already_set_mv[$row_grid_mv['other']] = $row_grid_mv['naslov'];
			}
			
			if(!isset($already_set_mv['-97']))
				$show['97'] = false;
			
			if(!isset($already_set_mv['-98']))
				$show['98'] = false;
			
			if(!isset($already_set_mv['-99']))
				$show['99'] = false;
		}
		// Imamo missing variablo
		else{
		
			$already_set_mv = array();
			$sql_grid_mv = sisplet_query("SELECT naslov, other FROM srv_vrednost WHERE spr_id='".$this->spremenljivka."' AND other != 0");
			while ($row_grid_mv = mysqli_fetch_array($sql_grid_mv)) {
				$already_set_mv[$row_grid_mv['other']] = $row_grid_mv['naslov'];
			}

			if(!isset($already_set_mv['-97']))
				$show['97'] = false;
			
			if(!isset($already_set_mv['-98']))
				$show['98'] = false;
				
			if(!isset($already_set_mv['-99']))
				$show['99'] = false;
		}

		echo '<div id="alert_show_missing">';
		
		// Prikaz -97 (neustrezno) ob opozorilu
		echo '<p><label for="alert_show_97" class="title"><span id="alert_show_97_text" class="'.($show['97']?'':' gray').'">'.$lang['srv_alert_show_missing_97'].':</span></label> '.Help::display('srv_alert_show_97');
		echo '<span class="content"><input type="hidden" name="alert_show_97" value="0" />';
		echo '<input type="checkbox" id="alert_show_97" name="alert_show_97" value="1" '.($row['alert_show_97']=='1'?' checked':'').' '.($show['97'] ? '' : ' disabled="disabled"').'></span>';
		echo '</p>';
		// Prikaz -98 (Zavrnil) ob opozorilu
		echo '<p><label for="alert_show_98" class="title"><span id="alert_show_98_text" class="'.($show['98']?'':' gray').'">'.$lang['srv_alert_show_missing_98'].':</span></label> '.Help::display('srv_alert_show_98');
		echo '<span class="content"><input type="hidden" name="alert_show_98" value="0" />';
		echo '<input type="checkbox" id="alert_show_98" name="alert_show_98" value="1" '.($row['alert_show_98']=='1'?' checked':'').' '.($show['98'] ? '' : ' disabled="disabled"').'></span>';
		echo '</p>';
		// Prikaz -99 (ne vem) ob opozorilu		
		echo '<p><label for="alert_show_99" class="title"><span id="alert_show_99_text" class="'.($show['99']?'':' gray').'">'.$lang['srv_alert_show_missing'].':</span></label> '.Help::display('srv_alert_show_99');
		echo '<span class="content"><input type="hidden" name="alert_show_99" value="0" />';
		echo '<input type="checkbox" id="alert_show_99" name="alert_show_99" value="1" '.($row['alert_show_99']=='1'?' checked':'').' '.($show['99'] ? '' : ' disabled="disabled"').'></span>';
		echo '</p>';
	
		echo '</div>';
	}
	
	// nastavitev ravrscanja vrednosti spr
	/**
			* 	0 = sort po vrstnem redu
			*   1 = sort random
			* 	2 = sort po abecedi naraščajoče
			* 	3 = sort po abecedi padajoče
	*/
	function edit_random() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		echo '<p>';
		
		if ( in_array($row['tip'], array(6, 16, 19, 20)) )
			echo '<span class="title">'.$lang['srv_sort_grid'].': </span>';
		else
			echo '<span class="title">'.$lang['srv_sort'].': </span>';
		
		echo '<span class="content"><select name="random">';
			echo '<option value="0" '.(($row['random'] == 0) ? ' selected="true" ' : '').'>'.$lang['srv_random_off2'].'</option>';		
			echo '<option value="1" '.(($row['random'] == 1) ? ' selected="true" ' : '').'>'.$lang['srv_random_on2'].'</option>';
			echo '<option value="2" '.(($row['random'] == 2) ? ' selected="true" ' : '').'>'.$lang['srv_sort_asc2'].'</option>';
			echo '<option value="3" '.(($row['random'] == 3) ? ' selected="true" ' : '').'>'.$lang['srv_sort_desc2'].'</option>';
		echo '</select></span>';
		echo '</p>';
	}
	
	// nastavitev stevila stolpcev v prikazu
	function edit_stolpci () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		if ($row['orientation'] != 1)
			return;
		
		$spremenljivkaParams = new enkaParameters($row['params']);	
		$stolpci = ($spremenljivkaParams->get('stolpci') ? $spremenljivkaParams->get('stolpci') : 1);
			
		echo '<p><span class="title">'.$lang['srv_stolpci'].': </span>';
		echo '<span class="content"><select name="stolpci">';
			echo '<option value="1" '.(($stolpci == 1) ? ' selected="true" ' : '').'>'.$lang['no'].'</option>';		
			echo '<option value="2" '.(($stolpci == 2) ? ' selected="true" ' : '').'>2</option>';
			echo '<option value="3" '.(($stolpci == 3) ? ' selected="true" ' : '').'>3</option>';
			echo '<option value="4" '.(($stolpci == 4) ? ' selected="true" ' : '').'>4</option>';
			echo '<option value="5" '.(($stolpci == 5) ? ' selected="true" ' : '').'>5</option>';
		echo '</select></span>';
		echo '</p>';
		
	}

	// nastavitev skale
	function edit_skala_new() {
		global $lang;
		
		$value = Common::getSpremenljivkaSkala($this->spremenljivka);
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		# v bazi je privzeto -1
		# skala - 1 Nominalna
		# skala - 0 Ordinalna - računamo povprečja
		//$display = (($row['tip'] == 6 && $row['enota'] == 8)) ? ' style="display:none;"' : '';
		
		//echo '<fieldset '.$display.'><legend>'.$lang['srv_measurment_scale'].' '.Help::display('srv_skala_edit').'</legend>';
		echo '<fieldset><legend>'.$lang['srv_measurment_scale'].' '.Help::display('srv_skala_edit').'</legend>';
		
		echo '<p>';
		echo '<input type="radio" name="skala" id="skala_0" value="0" '.(($value == 0 || $value == -1) ? ' checked="checked" ' : '').' onClick="show_scale_text(0)" /><label for="skala_0" class="spaceRight">'.$lang['srv_skala_0'].'</label>';		
		echo '<input type="radio" name="skala" id="skala_1" value="1" '.(($value == 1) ? ' checked="checked" ' : '').' onClick="show_scale_text(1)" /><label for="skala_1">'.$lang['srv_skala_1'].'</label>';
		echo '</p>';

		echo '<span id="skala_text_ord" class="spaceLeft" '.($value==0 || $value == -1 ? '' : ' style="display:none;"').'>'.$lang['srv_measurment_scale_ord'].' '.Help::display('srv_skala_text_ord').'</span>';
		echo '<span id="skala_text_nom" class="spaceLeft" '.($value==1 ? '' : ' style="display:none;"').'>'.$lang['srv_measurment_scale_nom'].' '.Help::display('srv_skala_text_nom').'</span>';
		
		echo '</fieldset>';
	}
	
	// prikaz checkboxa
	function edit_checkboxhide() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		echo '<p>';
		echo '<label for="checkboxhide" class="title">'.$lang['srv_checkboxhide_enabled'].':</label>';
		//echo '<input type="radio" name="checkboxhide" value="1" '.(($row['checkboxhide'] == 1) ? ' checked="checked" ' : '').' />'.$lang['yes'];		
		//echo '<input type="radio" name="checkboxhide" value="0" '.(($row['checkboxhide'] == 0) ? ' checked="checked" ' : '').' />'.$lang['no1'];
		echo '<span class="content"><input type="hidden" name="checkboxhide" value="1" />';		
		echo '<input type="checkbox" id="checkboxhide" name="checkboxhide" value="0" '.(($row['checkboxhide'] == 0) ? ' checked="checked" ' : '').' />';
		echo '</span></p>';
	}
	
	//bivsa edit_checkbox_limit()
	function  edit_checkbox_max_limit() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		$spremenljivkaParams = new enkaParameters($row['params']);
		$checkbox_limit = ($spremenljivkaParams->get('checkbox_limit') ? $spremenljivkaParams->get('checkbox_limit') : 0);
	
		$sqlc = sisplet_query("SELECT COUNT(*) AS count FROM srv_vrednost WHERE spr_id='$this->spremenljivka'");
		$rowc = mysqli_fetch_array($sqlc);
		
		echo '<p>';
		echo '<span class="title">'.$lang['srv_checkbox_max_limit'].':</span>';
		echo '<span class="content"><select name="checkbox_limit" id="checkbox_limit_'.$this->spremenljivka.'" onChange="checkCheckboxLimits(\'' . $row['id'] . '\', $(this).val(), \'checkbox_limit\');">';
		echo '<option value="0" '.(($checkbox_limit == 0) ? ' selected="true" ' : '').'>'.$lang['no'].'</option>';
		for ($i=1; $i<=$rowc['count']; $i++) {
			echo '<option value="'.$i.'" '.(($checkbox_limit == $i) ? ' selected="true" ' : '').'>'.$i.'</option>';
		}
		echo '</select></span>';
		echo '</p>';
	}
	
	
	function  edit_checkbox_min_limit() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		$spremenljivkaParams = new enkaParameters($row['params']);
		$checkbox_min_limit = ($spremenljivkaParams->get('checkbox_min_limit') ? $spremenljivkaParams->get('checkbox_min_limit') : 0);
	
		$sqlc = sisplet_query("SELECT COUNT(*) AS count FROM srv_vrednost WHERE spr_id='$this->spremenljivka'");
		$rowc = mysqli_fetch_array($sqlc);
		//$maxNumberOfAnswers = $rowc['count'] - 1;
		$maxNumberOfAnswers = $rowc['count'];
		
		echo '<p>';
		echo '<span class="title">'.$lang['srv_checkbox_min_limit'].':</span>';
		echo '<span class="content"><select name="checkbox_min_limit" id="checkbox_min_limit_'.$this->spremenljivka.'" onChange="checkCheckboxLimits(\'' . $row['id'] . '\', $(this).val(), \'checkbox_min_limit\'); toggleCheckboxMinLimitReminder(\'' . $row['id'] . '\', $(this).val());">';
		echo '<option value="0" '.(($checkbox_min_limit == 0) ? ' selected="true" ' : '').'>'.$lang['no'].'</option>';
		for ($i=1; $i<=$maxNumberOfAnswers; $i++) {
			echo '<option value="'.$i.'" '.(($checkbox_min_limit == $i) ? ' selected="true" ' : '').'>'.$i.'</option>';
		}
		echo '</select></span>';
		echo '</p>';
		
		$this->edit_reminder_min_checkbox($checkbox_min_limit);		
	}
	
	// nastavitev reminderja za minimalno stevilo izbranih checkbox-ox
	function edit_reminder_min_checkbox($checkbox_min_limit) {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		$checkbox_min_limit_reminder = ($spremenljivkaParams->get('checkbox_min_limit_reminder') ? $spremenljivkaParams->get('checkbox_min_limit_reminder') : 0);
		
		if($checkbox_min_limit){
			$displayReminder = " ";
		}else{
			$displayReminder = "none";
		}
	
		echo '<p id="checkboxLimitReminder_'.$row['id'].'" style="display:'.$displayReminder.'">';
		echo '<span class="title">'.$lang['srv_checkbox_min_limit_reminder'].':</span>';
		//echo '<span class="content"><select name="reminder" id="reminder" onChange="show_alert_missing();">';
		echo '<span class="content"><select name="checkbox_min_limit_reminder" id="checkbox_min_limit_reminder">';
			echo '<option value="0" '.(($checkbox_min_limit_reminder == 0) ? ' selected="true" ' : '').'>'.$lang['srv_reminder_off2'].'</option>';		
			echo '<option value="1" '.(($checkbox_min_limit_reminder == 1) ? ' selected="true" ' : '').'>'.$lang['srv_reminder_soft2'].'</option>';
			echo '<option value="2" '.(($checkbox_min_limit_reminder == 2) ? ' selected="true" ' : '').'>'.$lang['srv_reminder_hard2'].'</option>';
		echo '</select></span>';
		echo '</p>';
	}
	
	// editiranje radio tipa (1) - navaden, horizontalen, dropdown, semanticni diferencial
	function edit_radio_subtype() {
		global $lang;
		global $admin_type;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		echo '<p>';
		echo '<span class="title" >'.$lang['srv_orientacija'].': </span>';
		echo '<span class="content"><select name="orientation" id="spremenljivka_podtip_' . $row['id'] . '" spr_id="'.$row['id'].'" onChange="getOrientation(this.value); show_selectbox_size(\'' . $row['id'] . '\', this.value, \'' . $row['tip'] . '\'); show_preset_value(\'' . $row['id'] . '\', this.value, \'' . $row['tip'] . '\'); show_custom_picture_radio(\''.$row['id'].'\', this.value); show_hot_spot_settings(\'' . $row['id'] . '\', this.value, \'' . $row['tip'] . '\');">';
			echo '<option value="1"' . ($row['orientation'] == 1 ? ' selected="true"' : '') . '>'.$lang['srv_orientacija_vertikalna'].'</option>';
			echo '<option value="7"' . ($row['orientation'] == 7 ? ' selected="true"' : '') . '>'.$lang['srv_orientacija_vertikalna_2'].'</option>';
			echo '<option value="0"' . ($row['orientation'] == 0 ? ' selected="true"' : '') . '>'.$lang['srv_orientacija_horizontalna'].'</option>';
			echo '<option value="2"' . ($row['orientation'] == 2 ? ' selected="true"' : '') . '>'.$lang['srv_orientacija_horizontalna_2'].'</option>';
			echo '<option value="4"' . ($row['tip'] == 3 ? ' selected="true"' : '') . '>'.$lang['srv_dropdown'].'</option>';
			echo '<option value="6"' . ($row['orientation'] == 6 ? ' selected="true"' : '') . '>'.$lang['srv_select-box_radio'].'</option>';
			echo '<option value="8"' . ($row['orientation'] == 8 ? ' selected="true"' : '') . '>'.$lang['srv_drag_drop'].'</option>';
            echo '<option value="9"' . ($row['orientation'] == 9 ? ' selected="true"' : '') . '>'.$lang['srv_custom-picture_radio'].'</option>';	// Custom picture za radio tip
			echo '<option value="10"' . ($row['orientation'] == 10 ? ' selected="true"' : '') . '>'.$lang['srv_hot_spot'].'</option>';	//image hotspot
            echo '<option value="11"' . ($row['orientation'] == 11 ? ' selected="true"' : '') . '>'.$lang['srv_visual_analog_scale'].'</option>';	//vizualna analaogna skala - smeški
            echo '<option value="5"' . ($row['hidden_default'] == 1 ? ' selected="true"' : '') . '>'.$lang['srv_potrditev'].'</option>';
		echo '</select></span>';
		echo '</p>';
	}
	
	// editiranje orientacije chackboxa 
	function edit_checkbox_subtype() {
		global $lang;
		global $admin_type;

		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		echo '<p>';
		echo '<span class="title">'.$lang['srv_orientacija'].':</span>';
		//echo '<span class="content"><select name="orientation" id="spremenljivka_podtip_' . $row['id'] . '" spr_id="'.$row['id'].'" onChange="getOrientation(this.value); show_selectbox_size(\'' . $row['id'] . '\', this.value);">';
		echo '<span class="content"><select name="orientation" id="spremenljivka_podtip_' . $row['id'] . '" spr_id="'.$row['id'].'" onChange="getOrientation(this.value); show_selectbox_size(\'' . $row['id'] . '\', this.value, \'' . $row['tip'] . '\'); show_preset_value(\'' . $row['id'] . '\', this.value, \'' . $row['tip'] . '\'); show_hot_spot_settings(\'' . $row['id'] . '\', this.value, \'' . $row['tip'] . '\');">';
			echo '<option value="1"' . ($row['orientation'] == 1 ? ' selected="true"' : '') . '>'.$lang['srv_orientacija_vertikalna'].'</option>';
			echo '<option value="7"' . ($row['orientation'] == 7 ? ' selected="true"' : '') . '>'.$lang['srv_orientacija_vertikalna_2'].'</option>';
			echo '<option value="0"' . ($row['orientation'] == 0 ? ' selected="true"' : '') . '>'.$lang['srv_orientacija_horizontalna'].'</option>';
			echo '<option value="2"' . ($row['orientation'] == 2 ? ' selected="true"' : '') . '>'.$lang['srv_orientacija_horizontalna_2'].'</option>';
			echo '<option value="6"' . ($row['orientation'] == 6 ? ' selected="true"' : '') . '>'.$lang['srv_select-box_check'].'</option>';
			echo '<option value="8"' . ($row['orientation'] == 8 ? ' selected="true"' : '') . '>'.$lang['srv_drag_drop'].'</option>';
			echo '<option value="10"' . ($row['orientation'] == 10 ? ' selected="true"' : '') . '>'.$lang['srv_hot_spot'].'</option>';	//image hotspot
			if ($admin_type == 0){
				//echo '<option value="6"' . ($row['orientation'] == 6 ? ' selected="true"' : '') . '>'.$lang['srv_select-box_check'].'</option>';

			}
			//echo '<option value="6"' . ($row['orientation'] == 6 ? ' selected="true"' : '') . '>'.$lang['srv_mutliselect-box'].'</option>';
		echo '</select>';
		echo '</span></p>';
	}
	
	// navaden number ali slider
	function edit_subtype_number () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		echo '<p>';
		echo '<span class="title">'.$lang['srv_number_insert'].':</span>';
		// //echo '<span class="content"><select name="ranking_k" id="spremenljivka_podtip_' . $row['id'] . '" spr_id="'.$row['id'].'" class="no_submit" onchange="change_subtype_number(\''.$row['id'].'\');">';
		// echo '<span class="content"><select name="ranking_k" id="spremenljivka_podtip_' . $row['id'] . '" spr_id="'.$row['id'].'" class="no_submit" onchange="change_subtype_number(\''.$row['id'].'\'); show_slider_prop(\''.$row['id'].'\',this.value)">';
			// echo '<option value="0"' . ($row['ranking_k'] == 0 ? ' selected="true"' : '') . '>'.$lang['srv_number_insert_0'].'</option>';
			// echo '<option value="1"' . ($row['ranking_k'] == 1 ? ' selected="true"' : '') . '>'.$lang['srv_number_insert_1'].'</option>';
		// echo '</select>';
		//echo '<p>';
		echo '<span class="content">';
		echo '<input type="radio" name="ranking_k" id="select_num_0" value="0" '.(($row['ranking_k'] == 0) ? ' checked="checked" ' : '').' onClick="change_subtype_number(\''.$row['id'].'\'); show_slider_prop(\''.$row['id'].'\',this.value)" /><label for="select_num_0">'.$lang['srv_number_insert_0_new'].'</label>';
		echo '<input type="radio" name="ranking_k" id="select_num_1" value="1" '.(($row['ranking_k'] == 1) ? ' checked="checked" ' : '').' onClick="change_subtype_number(\''.$row['id'].'\'); show_slider_prop(\''.$row['id'].'\',this.value)"/><label for="select_num_1">'.$lang['srv_number_insert_1'].'</label>';

		//echo '</p>';
		echo '</span></p>';
	}
	
	// navaden number ali slider
	function edit_subtype_multinumber () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		echo '<p>';
		echo '<span class="title">'.$lang['srv_number_insert'].':</span>';				
		// echo '<span class="content"><select name="ranking_k" id="spremenljivka_podtip_' . $row['id'] . '" spr_id="'.$row['id'].'" class="no_submit" onchange="change_subtype_number(\''.$row['id'].'\');">';
			// echo '<option value="0"' . ($row['ranking_k'] == 0 ? ' selected="true"' : '') . '>'.$lang['srv_number_insert_0'].'</option>';
			// echo '<option value="1"' . ($row['ranking_k'] == 1 ? ' selected="true"' : '') . '>'.$lang['srv_number_insert_1'].'</option>';
			// echo '<option value="2"' . ($row['ranking_k'] == 2 ? ' selected="true"' : '') . '>'.$lang['srv_number_insert_2'].'</option>';
			// echo '<option value="3"' . ($row['ranking_k'] == 3 ? ' selected="true"' : '') . '>'.$lang['srv_number_insert_3'].'</option>';
			// echo '<option value="4"' . ($row['ranking_k'] == 4 ? ' selected="true"' : '') . '>'.$lang['srv_number_insert_4'].'</option>';
			// echo '<option value="5"' . ($row['ranking_k'] == 5 ? ' selected="true"' : '') . '>'.$lang['srv_number_insert_5'].'</option>';
			// echo '<option value="6"' . ($row['ranking_k'] == 6 ? ' selected="true"' : '') . '>'.$lang['srv_number_insert_6'].'</option>';
			//echo '<option value="7"' . ($row['ranking_k'] == 7 ? ' selected="true"' : '') . '>'.$lang['srv_number_insert_7'].'</option>';
		//echo '</select>';
		//echo '</span></p>';
		echo '<input type="radio" name="ranking_k" value="0" '.(($row['ranking_k'] == 0) ? ' checked="checked" ' : '').' onChange="change_subtype_number(\''.$row['id'].'\'); show_slider_prop(\''.$row['id'].'\',this.value)" /><label for="skala_0" class="spaceRight">'.$lang['srv_number_insert_0_new'].'</label>';
		echo '<input type="radio" name="ranking_k" value="1" '.(($row['ranking_k'] == 1) ? ' checked="checked" ' : '').' onChange="change_subtype_number(\''.$row['id'].'\'); show_slider_prop(\''.$row['id'].'\',this.value)"/><label for="skala_1">'.$lang['srv_number_insert_1'].'</label>';
		echo '</p>';
		
	}
	
	// prikaz statistike
	function edit_stat() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		echo '<p>';
		echo '<label for="stat" class="title">'.$lang['srv_stat_on'].': '.Help::display('srv_statistika').'</label>';
		//echo '<span class="content"><input type="radio" name="stat" value="0" '.(($row['stat'] == 0) ? ' checked="checked" ' : '').' />'.$lang['no1'];		
		//echo '<input type="radio" name="stat" value="1" '.(($row['stat'] == 1) ? ' checked="checked" ' : '').' />'.$lang['yes'];
		echo '<span class="content"><input type="hidden" name="stat" value="0" />';		
		echo '<input type="checkbox" id="stat" name="stat" value="1" '.(($row['stat'] == 1) ? ' checked="checked" ' : '').' />';
		echo '</span></p>';
	}
	
	// upload pri tekstovnem polju
	function edit_upload() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		$spremenljivkaParams = new enkaParameters($row['params']);
		$captcha = ($spremenljivkaParams->get('captcha') ? $spremenljivkaParams->get('captcha') : 0);
		$emailVerify = ($spremenljivkaParams->get('emailVerify') ? $spremenljivkaParams->get('emailVerify') : 0);
		
		$disabled = ($row['signature']==1 || $captcha==1 || $emailVerify==1) ? ' disabled="disabled"' : '';
	
		echo '<p>';
		echo '<span class="title">'.$lang['srv_vprasanje_upload_type'].':</span>';
                                
		//novo, dropdown s fotografijami        onChange="change_upload(\'' . $row['id'] . '\', this.value);"
		echo '<span class="content"><select name="upload" id="spremenljivka_upload_' . $row['id'] . '" spr_id="'.$row['id'].'" '.$disabled.' onChange="textSubtypeToggle(\'upload\', this.value);">';
		echo '	<option value="0" '.(($row['upload'] == 0) ? ' selected="true" ' : '').'>'.$lang['srv_vprasanje_upload_no'].'</option>';
		echo '	<option value="1" '.(($row['upload'] == 1) ? ' selected="true" ' : '').'>'.$lang['srv_vprasanje_upload_yes'].'</option>';
		echo '	<option value="2" '.(($row['upload'] == 2) ? ' selected="true" ' : '').'>'.$lang['srv_vprasanje_upload_fotografija'].'</option></select>';
		echo '</p>';
	}
	
	// podpis pri tekstovnem polju
	function edit_signature() {
		global $lang;
		global $global_user_id;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
        
        // Podpis je na voljo samo v 3. paketu
        $userAccess = UserAccess::getInstance($global_user_id);
        if(!$userAccess->checkUserAccess($what='question_type_signature') && $row['signature'] != 1)
            return;

		$spremenljivkaParams = new enkaParameters($row['params']);
		$captcha = ($spremenljivkaParams->get('captcha') ? $spremenljivkaParams->get('captcha') : 0);
		$emailVerify = ($spremenljivkaParams->get('emailVerify') ? $spremenljivkaParams->get('emailVerify') : 0);
		
        $disabled = ($row['upload']>0 || $captcha==1 || $emailVerify==1) ? ' disabled="disabled"' : '';
        			
		echo '<p>';
		echo '<span class="title">'.$lang['srv_tip_standard_996'].':</span>';
		
		echo '<span class="content">';
		echo '<input type="radio" id="signature_'.$this->spremenljivka.'_0" name="signature" '.$disabled.' onclick="signatureProp('.$this->spremenljivka.'); textSubtypeToggle(\'signature\', this.value);" value="0" '.(($row['signature'] == 0) ? ' checked="checked" ' : '').' /><label for="signature_'.$this->spremenljivka.'_0">'.$lang['no1'].'</label>';		
		echo '<input type="radio" id="signature_'.$this->spremenljivka.'" name="signature" '.$disabled.' onclick="signatureProp('.$this->spremenljivka.'); textSubtypeToggle(\'signature\', this.value);" value="1" '.(($row['signature'] == 1) ? ' checked="checked" ' : '').' /><label for="signature_'.$this->spremenljivka.'">'.$lang['yes'].'</label>';
		echo '</span>';
		
		echo '</p>';
	}
	
	// nastavitev timerja
	function edit_timer() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		echo '<p>';
		echo '<label for="_timer" class="title">Timer:</label>';
		echo '<span class="content"><input type="hidden" name="timer" value="0" />';		
		echo '<input type="checkbox" id="_timer" name="timer" value="1" '.(($row['timer'] > 0) ? ' checked="checked" ' : '').' onchange="show_timer(this.value);" />';
		echo '</span></p>';	

		echo '<p id="timer" '.(($row['timer'] == 0) ? 'style="display: none;"' : '').'>';
		echo '<span class="title">&nbsp;</span><span class="content">';
		echo '<select name="timer2">';
		
		# od 1-15 mamo za vsako sekundo
		for ($t = 1; $t <= 15; $t += 1){
			echo '<option value="'.$t.'" '.(($t == $row['timer']) ? ' selected' : '').'>';
			echo $t . $lang['srv_seconds'];
			echo '</option>';
		}
		
		# dodatna opcija za 20s 30s in 45s
		echo '<option value="20"' . ((20 == $row['timer']) ? ' selected' : '') . '>';
		echo '20' . $lang['srv_seconds'];
        echo '</option>';
        
        echo '<option value="30"' . ((30 == $row['timer']) ? ' selected' : '') . '>';
		echo '30' . $lang['srv_seconds'];
        echo '</option>';
        
        echo '<option value="45"' . ((45 == $row['timer']) ? ' selected' : '') . '>';
		echo '45' . $lang['srv_seconds'];
		echo '</option>';
		
		#od 60 do 600 mamo na 15s
		for ($t = 60; $t <= 600; $t += 15){
			echo '<option value="' . $t . '"' . (($t == $row['timer']) ? ' selected' : '') . '>';
			echo floor(bcdiv($t, 60)) . $lang['srv_minutes'] . ' ';
			echo (bcmod($t, 60)) . $lang['srv_seconds'] . '';
			echo '</option>';
		}
		
		echo '</select></span>';
		echo '</p>';	
	}
	
	// nastavitev celih in decimalnih mest (number, multinumber, vsota)
	function edit_number() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		echo '<fieldset><legend>'.$lang['srv_num_limits'].'</legend>';
		echo '<p><span class="title">';
		
		echo $lang['srv_cela'].':</span><span class="content">';		
		echo '<select name="cela">';
		for ($i = 1; $i <= 10; $i++)
			echo '<option value="' . $i . '"' . ($row['cela'] == $i ? ' selected="true"' : '') . '>' . $i . '</option>';
		echo '</select></span>';
		echo '</p><p>';
		echo '<span class="title">'.$lang['srv_decimalna'].':</span><span class="content">';		
		echo '<select name="decimalna">';
		for ($i = 0; $i <= 10; $i++)
			echo '<option value="' . $i . '"' . ($row['decimalna'] == $i ? ' selected="true"' : '') . '>' . $i . '</option>';
		echo '</select></span>';
		
		echo '</p>';	
                echo '</fieldset>';
		
	}
	
	// nastavitev za obliko generatorja imen
	function edit_name_generator_design(){
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);		
		
		// Design generatorja
		echo '<p><label class="title">'.$lang['srv_design'].':</label>';
		echo '<span class="content"><input type="hidden" name="sn_design" value="0" />';
		echo '<select id="spremenljivka_podtip_' . $row['id'] . '" name="sn_design" spr_id="'.$row['id'].'" onChange="show_SN_count(this.value)">';	
			echo '<option value="0" '.(($row['design'] == 0) ? ' selected="true" ' : '').'>'.$lang['srv_sn_design_1'].'</option>';
			echo '<option value="1" '.(($row['design'] == 1) ? ' selected="true" ' : '').'>'.$lang['srv_sn_design_2'].'</option>';
			echo '<option value="2" '.(($row['design'] == 2) ? ' selected="true" ' : '').'>'.$lang['srv_sn_design_3'].'</option>';
			echo '<option value="3" '.(($row['design'] == 3) ? ' selected="true" ' : '').'>'.$lang['srv_sn_design_4'].'</option>';
		echo '</select>';	
		echo '</span></p>';
	}
	
	// nastavitve za generator imen
	function edit_name_generator(){
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);		
		$spremenljivkaParams = new enkaParameters($row['params']);	
			
		
		// Max odgovorov - samo pri 2. designu
		echo '<div id="SN_count" class="SN_hidable_settings" '.($row['design'] != 1 ? ' style="display:none;"' : '').'>';
		
		echo '<p><label class="title">'.$lang['srv_design_count'].':</label>';
		echo '<span class="content"><input type="hidden" name="size" value="0" />';
		echo '<select id="size" name="size">';	
			for($i=1; $i<=20; $i++){
				echo '<option value="'.$i.'" '.(($row['size'] == $i) ? ' selected="true" ' : '').'>'.$i.'</option>';
			}	
		echo '</select>';	
		echo '</span></p>';
		
		echo '</div>';

		
		// Antonuccijev krog
		echo '<p><label class="title">'.$lang['srv_antonucci'].':</label>';
		echo '<span class="content"><input type="hidden" name="antonucci" value="0" />';
		echo '<select id="antonucci" name="antonucci">';	
			echo '<option value="0" '.(($row['antonucci'] == 0) ? ' selected="true" ' : '').'>'.$lang['srv_none'].'</option>';
			echo '<option value="1" '.(($row['antonucci'] == 1) ? ' selected="true" ' : '').'>1.</option>';
			echo '<option value="2" '.(($row['antonucci'] == 2) ? ' selected="true" ' : '').'>2.</option>';
			echo '<option value="3" '.(($row['antonucci'] == 3) ? ' selected="true" ' : '').'>3.</option>';	
		echo '</select>';			
		echo '</span></p>';
		
		
		if($spremenljivkaParams->get('NG_cancelButton') == '1'){
			$cancelText = $spremenljivkaParams->get('NG_cancelText');
			$cancelButton = 1;
			$hidden = '';
		}
		else{
			$cancelText = $lang['srv_NG_cancelText'];
			$cancelButton = 0;
			$hidden = ' style="display:none;"';
		}
		
		// Text za dodajanje nove osebe
		$addText = ($spremenljivkaParams->get('NG_addText') ? $spremenljivkaParams->get('NG_addText') : $lang['srv_NG_addText']);		
		echo '<div id="SN_add_text" class="SN_hidable_settings" '.($row['design'] != 0 ? ' style="display:none;"' : '').'>';
		
		echo '<p>';	
		echo $lang['srv_NG_addText_setting'] . ': <input type="text" name="NG_addText" value="' . $addText . '" size="30" />';
		echo '</p>';
		
		echo '</div>';
		
		
		// Pri vnosu stevila polj imamo opcijo za urejanje texta "Število polj za vnos"
		$countText = ($spremenljivkaParams->get('NG_countText') ? $spremenljivkaParams->get('NG_countText') : $lang['srv_design_count']);
		echo '<div id="SN_count_text" class="SN_hidable_settings" '.($row['design'] != 3 ? ' style="display:none;"' : '').'>';
		
		echo '<p>';		
		echo $lang['srv_NG_countText_setting'] . ': <input type="text" name="NG_countText" value="' . $countText . '" size="30" />';
		echo '</p>';
		
		echo '</div>';
		
		
		// Gumb za preskok generatorja imen
		echo '<p>';	
		
		echo $lang['srv_NG_cancelText_setting'] . ': ';
		echo '<input type="radio" '.($cancelButton == 0 ? ' checked' : '').' name="NG_cancelButton" value="0" onClick="change_NG_cancelButton(this.value);">'.$lang['no'].'</input> ';
		echo '<input type="radio" '.($cancelButton == 1 ? ' checked' : '').' name="NG_cancelButton" value="1" onClick="change_NG_cancelButton(this.value);">'.$lang['yes'].'</input> ';
		
		echo '&nbsp;&nbsp;&nbsp;<input type="text" '.$hidden.' name="NG_cancelText" id="NG_cancelText" value="' . $cancelText . '" size="30" />';
		echo '</p>';
	}	
	
	// nastavitev omejitve vnessenega stevila (number, vsota)
	function edit_limit() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		
		//omejitev za number 
		if($row['tip'] == 7 || $row['tip'] == 21){
                    $onkeyup = $row['tip'] == 21 ? ' onkeyup="checkNumber(this, 4, 0, true);"' : '';
                    
			echo '<fieldset><legend>'.$lang['srv_num_reminder'].'</legend>';
			echo '<input type="hidden" name="edit_number_limit" value="1" />';
			
			echo '<p>';
			echo '<span class="clr" id="num_limit_label" '.($row['size']==2 ? '' : ' style="display:none;"').'>'.$lang['srv_num_field1'].':</span>';

			//spodnji limit
			echo '<input type="checkbox" value="1" id="num_useMin" name="num_useMin" '.($row['num_useMin']==1 ? ' checked="checked"' : '').' onClick="num_limit(\'vsota_min\',this.checked)">';
			echo $lang['srv_num_min'] . '<input type="text" name="vsota_min" id="vsota_min"'.$onkeyup.' value="' . $row['vsota_min'] . '" size="8" '.($row['num_useMin'] == 0 ? ' disabled' : '').'></input> ';
			//zgornji limit
			echo '&nbsp;&nbsp;<input type="checkbox" value="1" id="num_useMax" name="num_useMax" '.($row['num_useMax']==1 ? ' checked="checked"' : '').' onClick="num_limit(\'vsota_limit\',this.checked)">';
			echo $lang['srv_num_limit'] . '<input type="text" name="vsota_limit" id="vsota_limit"'.$onkeyup.' value="' . $row['vsota_limit'] . '" size="8" '.($row['num_useMax'] == 0 ? ' disabled' : '').'></input> ';
			echo '</p>';
			
			// Omejitve za 2. polje (ce ga imamo)
			echo '<div id="num_limit2" '.($row['size'] == 2 ? '' : ' style="display:none;"').'>';		
			echo '<p>';
			echo '<span class="clr">'.$lang['srv_num_field2'].':</span>';
			//spodnji limit
			echo '<input type="checkbox" value="1" id="num_useMin2" name="num_useMin2" '.($row['num_useMin2']==1 ? ' checked="checked"' : '').' onClick="num_limit(\'num_min2\',this.checked)">';
			echo $lang['srv_num_min'] . '<input type="text" name="num_min2" id="num_min2" value="' . $row['num_min2'] . '" size="8" '.($row['num_useMin2'] == 0 ? ' disabled' : '').'></input> ';
			//zgornji limit
			echo '&nbsp;&nbsp;<input type="checkbox" value="1" id="num_useMax2" name="num_useMax2" '.($row['num_useMax2']==1 ? ' checked="checked"' : '').' onClick="num_limit(\'num_max2\',this.checked)">';
			echo $lang['srv_num_limit'] . '<input type="text" name="num_max2" id="num_max2" value="' . $row['num_max2'] . '" size="8" '.($row['num_useMax2'] == 0 ? ' disabled' : '').'></input> ';
			echo '</p>';			
			echo '</div>';
			
			// prikaz omejitve
			echo '<p><span class="title"><label for="vsota_show">'.$lang['srv_num_limit_show'] . '</label></span><span class="content"><input type="checkbox" name="vsota_show" id="vsota_show" value="1" '.($row['vsota_show']==1?'checked':'').' /></span></p>';
			
			// opozorilo za preseg limita (mehko, trdo)
			echo '<p><span class="title">'.$lang['srv_num_limit_reminder'].': </span>';
			echo '<span class="content"><select name="vsota_reminder">';
			echo '<option value="0"' . ($row['vsota_reminder'] == 0 ? ' selected="true"' : '') . '>'.$lang['srv_reminder_off2'].'</option>';
			echo '<option value="1"' . ($row['vsota_reminder'] == 1 ? ' selected="true"' : '') . '>'.$lang['srv_reminder_soft2'].'</option>';
			echo '<option value="2"' . ($row['vsota_reminder'] == 2 ? ' selected="true"' : '') . '>'.$lang['srv_reminder_hard2'].'</option>';
			echo '</select></span></p>';
			echo '</fieldset>';
		}
		
		// grid number
		if ($row['tip'] == 20 && $row['ranking_k'] != 1) {
			
			// zaenkrat sam za slider
			//if ($row['ranking_k'] != 1) return;
			
			echo '<fieldset><legend>'.$lang['srv_num_reminder'].'</legend>';
			echo '<input type="hidden" name="edit_number_limit" value="1" />';
			
			echo '<p>';
			echo '<span class="clr" id="num_limit_label" '.($row['size']==2 ? '' : ' style="display:none;"').'>'.$lang['srv_num_field1'].':</span>';

			//spodnji limit
			echo '<input type="checkbox" value="1" id="num_useMin" name="num_useMin" '.($row['num_useMin']==1 ? ' checked="checked"' : '').' onClick="num_limit(\'vsota_min\',this.checked)">';
			echo $lang['srv_num_min'] . '<input type="text" name="vsota_min" id="vsota_min" value="' . $row['vsota_min'] . '" size="8" '.($row['num_useMin'] == 0 ? ' disabled' : '').'></input> ';
			//zgornji limit
			echo '&nbsp;&nbsp;<input type="checkbox" value="1" id="num_useMax" name="num_useMax" '.($row['num_useMax']==1 ? ' checked="checked"' : '').' onClick="num_limit(\'vsota_limit\',this.checked)">';
			echo $lang['srv_num_limit'] . '<input type="text" name="vsota_limit" id="vsota_limit" value="' . $row['vsota_limit'] . '" size="8" '.($row['num_useMax'] == 0 ? ' disabled' : '').'></input> ';
			echo '</p>';
			
			// Omejitve za 2. polje (ce ga imamo)
			echo '<div id="num_limit2" '.($row['size'] == 2 ? '' : ' style="display:none;"').'>';		
			echo '<p>';
			echo '<span class="clr">'.$lang['srv_num_field2'].':</span>';
			//spodnji limit
			echo '<input type="checkbox" value="1" id="num_useMin2" name="num_useMin2" '.($row['num_useMin2']==1 ? ' checked="checked"' : '').' onClick="num_limit(\'num_min2\',this.checked)">';
			echo $lang['srv_num_min'] . '<input type="text" name="num_min2" id="num_min2" value="' . $row['num_min2'] . '" size="8" '.($row['num_useMin2'] == 0 ? ' disabled' : '').'></input> ';
			//zgornji limit
			echo '&nbsp;&nbsp;<input type="checkbox" value="1" id="num_useMax2" name="num_useMax2" '.($row['num_useMax2']==1 ? ' checked="checked"' : '').' onClick="num_limit(\'num_max2\',this.checked)">';
			echo $lang['srv_num_limit'] . '<input type="text" name="num_max2" id="num_max2" value="' . $row['num_max2'] . '" size="8" '.($row['num_useMax2'] == 0 ? ' disabled' : '').'></input> ';
			echo '</p>';			
			echo '</div>';
			
			// prikaz omejitve
			echo '<p><span class="title"><label for="vsota_show">'.$lang['srv_num_limit_show'] . '</label></span><span class="content"><input type="checkbox" name="vsota_show" id="vsota_show" value="1" '.($row['vsota_show']==1?'checked':'').' /></span></p>';
			
			// opozorilo za preseg limita (mehko, trdo)
			echo '<p><span class="title">'.$lang['srv_num_limit_reminder'].': </span>';
			echo '<span class="content"><select name="vsota_reminder">';
			echo '<option value="0"' . ($row['vsota_reminder'] == 0 ? ' selected="true"' : '') . '>'.$lang['srv_reminder_off2'].'</option>';
			echo '<option value="1"' . ($row['vsota_reminder'] == 1 ? ' selected="true"' : '') . '>'.$lang['srv_reminder_soft2'].'</option>';
			echo '<option value="2"' . ($row['vsota_reminder'] == 2 ? ' selected="true"' : '') . '>'.$lang['srv_reminder_hard2'].'</option>';
			echo '</select></span></p>';
			echo '</fieldset>';
		}
		
		// grid slider
		if ($row['tip'] == 20 && $row['ranking_k'] == 1) {
			
			echo '<fieldset><legend>'.$lang['srv_num_reminder'].'</legend>';
			echo '<input type="hidden" name="edit_number_limit" value="1" />';
			
			echo '<p>';
			//spodnji limit
			echo $lang['srv_num_min'] . '<input type="text" name="vsota_min" id="vsota_min" value="' . $row['vsota_min'] . '" size="8"></input> ';
			//zgornji limit
			echo '&nbsp;&nbsp;';
			echo $lang['srv_num_limit'] . '<input type="text" name="vsota_limit" id="vsota_limit" value="' . $row['vsota_limit'] . '" size="8"></input> ';
			echo '</p>';
			
			
			echo '</fieldset>';
			
		}
		
		//omejitev za vsoto
		if($row['tip'] == 18){
			echo '<fieldset><legend>'.$lang['srv_vsota_reminder'].'</legend>';
			echo '<input type="hidden" name="edit_vsota_limit" value="1" />';
			echo '<p>';
			//nastavitev tocne vsote
			if($row['vsota_min'] == $row['vsota_limit'])
				$val = $row['vsota_min'];

			echo $lang['srv_vsota_exact'] . '<input type="text" '.($row['vsota_limittype'] == 1 ? ' disabled' : '').' name="vsota_exact" id="vsota_exact" value="' . $val . '"  size="8"></input> ';
			echo '</p>';
			
			echo '<p>';
			echo $lang['srv_vsota_both'] . '<input type="checkbox" '.($row['vsota_limittype'] == 1 ? ' checked' : '').' name="vsota_limittype" value="1" onClick="change_limittype(this.checked)"></input> ';
			echo '</p>';
			
			echo '<p>';
			//spodnji limit vsote
			echo $lang['srv_vsota_min'] . '<input type="text" '.($row['vsota_limittype'] == 0 ? ' disabled' : '').' name="vsota_min" id="vsota_min" value="' . $row['vsota_min'] . '" size="8"></input> ';
			//zgornji limit vsote
			echo $lang['srv_vsota_limit'] . '<input type="text" '.($row['vsota_limittype'] == 0 ? ' disabled' : '').' name="vsota_limit" id="vsota_limit" value="' . $row['vsota_limit'] . '" size="8"></input> ';

			echo '</p>';
			
			// prikaz omejitve
			echo '<p>'.$lang['srv_vsota_show'] . '<input type="checkbox" name="vsota_show" id="vsota_show" value="1" '.($row['vsota_show']==1?'checked':'').' /></p>';
			
			// opozorilo za preseg limita (mehko, trdo)
			echo '<p>';
			echo $lang['srv_vsota_reminder'].': ';
			echo '<select name="vsota_reminder">';
			echo '<option value="0"' . ($row['vsota_reminder'] == 0 ? ' selected="true"' : '') . '>'.$lang['srv_reminder_off'].'</option>';
			echo '<option value="1"' . ($row['vsota_reminder'] == 1 ? ' selected="true"' : '') . '>'.$lang['srv_reminder_soft'].'</option>';
			echo '<option value="2"' . ($row['vsota_reminder'] == 2 ? ' selected="true"' : '') . '>'.$lang['srv_reminder_hard'].'</option>';
			echo '</select>';
			
			echo '</p>';	
			echo '</fieldset>';
		}
		
		
	}
	
	// nastavitev poravnave celic v gridih
	function edit_grid_align() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		$display = ( ($row['tip'] == 2)) ? ' style="display:none;"' : '';
		echo '<p '.$display.'>';
		
		$gridAlign = ($spremenljivkaParams->get('gridAlign') ? $spremenljivkaParams->get('gridAlign') : -1);
		echo '<span class="title">'.$lang['srv_gridAlign'].'</span>';

		echo '<span class="content"><select name="gridAlign" id="gridAlign">';
		
		echo '<option value="0"' . ($gridAlign == 0 ? ' selected="true"' : '') . '>'.$lang['srv_gridAlign_center'].'</option>';
		echo '<option value="1"' . ($gridAlign == 1 ? ' selected="true"' : '') . '>'.$lang['srv_gridAlign_left'].'</option>';
		echo '<option value="2"' . ($gridAlign == 2 ? ' selected="true"' : '') . '>'.$lang['srv_gridAlign_right'].'</option>';

		echo '</select></span>';		
		
		echo '</p>';
	}
	
	// nastavitev sirine text polja (besedilo*, multitext, multinumber, number)
	function edit_width() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
	
		echo '<p>';
		
		$taWidth = ($spremenljivkaParams->get('taWidth') ? $spremenljivkaParams->get('taWidth') : -1);
		$taHeight = ($spremenljivkaParams->get('taHeight') ? $spremenljivkaParams->get('taHeight') : 1);
		echo $lang['srv_textAreaWidth'].': ';

		//sirina za multitext in multinumber
		if($row['tip'] == 19 || $row['tip'] == 20){
			$size = $row['grids'];
			$missing_count = 0;
			# če imamo missinge size povečamo za 1 + številomissingov
			$sql_grid_mv = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='".$this->spremenljivka."' AND other != 0");
			$missing_count  = mysqli_num_rows($sql_grid_mv);
			if ($missing_count > 0) {
				$size += $missing_count + 1;
			}

			echo '<select name="taWidth" id="width">';
			$maxWidth = round(50 / $size);
			
			echo '<option value="-1"' . ($taWidth == -1 ? ' selected="true"' : '') . '>'.$lang['default'].'</option>';
			for($i=1; $i<$maxWidth; $i++){
				echo '<option value="'.$i.'"' . ($taWidth == $i ? ' selected="true"' : '') . '>' . $i . '</option>';
			}
			echo '</select>';
			
			// multitext ima tudi nastavitev visine
			if ($row['tip'] == 19) {
				
				echo '<span class="content">'.$lang['srv_textAreaHeight'].': ';
				echo '<select name="taHeight" class="no-margin" id="taHeight">';
				$maxHeight = 10;
				
				for($i=1; $i<=$maxHeight; $i++){
					echo '<option value="'.$i.'"' . ($taHeight == $i ? ' selected="true"' : '') . '>' . $i . '</option>';
				}
				echo '</select></span>';
			}
		}
				
		//sirina za number
		elseif($row['tip'] == 7){
			$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$this->spremenljivka' ORDER BY vrstni_red ASC");
			$row1 = mysqli_fetch_array($sql1);
			$id1 = $row1['id'];
			$row1 = mysqli_fetch_array($sql1);
			$id2 = $row1['id'];
			
			echo '<select name="taWidth" id="width" onchange="change_number(\'1\', \'2\');">';
			
			echo '<option value="-1"' . ($taWidth == -1 ? ' selected="true"' : '') . '>'.$lang['default'].' (10)</option>';
			for($i=5; $i<50; $i+=5){
				echo '<option value="'.$i.'"' . ($taWidth == $i ? ' selected="true"' : '') . '>' . $i . '</option>';
			}
			for($i=50; $i<=100; $i+=10){
				echo '<option value="'.$i.'"' . ($taWidth == $i ? ' selected="true"' : '') . '>' . $i . '</option>';
			}
			echo '</select>';
		}
		
		//sirina za besedilo*
		elseif($row['tip'] == 21){	
			echo '<select name="taWidth" id="width">';
		
			echo '<option value="-1"' . ($taWidth == -1 ? ' selected="true"' : '') . '>'.$lang['default'].' (30)</option>';
			for($i=5; $i<50; $i+=5){
				echo '<option value="'.$i.'"' . ($taWidth == $i ? ' selected="true"' : '') . '>' . $i . '</option>';
			}
			for($i=50; $i<=100; $i+=10){
				echo '<option value="'.$i.'"' . ($taWidth == $i ? ' selected="true"' : '') . '>' . $i . '</option>';
			}
			echo '</select>';
			
			$this->edit_height();
		}
		
		echo '</p>';
	}
	
	// nastavitev visine text polja (besedilo*)
	function edit_height() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
	
		//echo '<p>';
		
		$taSize = ($spremenljivkaParams->get('taSize') ? $spremenljivkaParams->get('taSize') : 1);
		echo '<span class="content">'.$lang['srv_textAreaSize'].'';
		echo '<select name="taSize" class="no-margin" id="height">';
			echo '<option value="1"' . ($taSize == 1 ? ' selected="true"' : '') . '>' . $lang['srv_textArea1line'] . '</option>';
			echo '<option value="2"' . ($taSize == 2 ? ' selected="true"' : '') . '>' . $lang['srv_textArea2line'] . '</option>';
			echo '<option value="3"' . ($taSize == 3 ? ' selected="true"' : '') . '>' . $lang['srv_textArea3line'] . '</option>';
			echo '<option value="5"' . ($taSize == 5 ? ' selected="true"' : '') . '>' . $lang['srv_textArea5line'] . '</option>';
			echo '<option value="7"' . ($taSize == 7 ? ' selected="true"' : '') . '>' . $lang['srv_textArea7line'] . '</option>';
			echo '<option value="10"' . ($taSize == 10 ? ' selected="true"' : '') . '>' . $lang['srv_textArea10line'] . '</option>';
			echo '<option value="20"' . ($taSize == 20 ? ' selected="true"' : '') . '>' . $lang['srv_textArea20line'] . '</option>';
			echo '<option value="30"' . ($taSize == 30 ? ' selected="true"' : '') . '>' . $lang['srv_textArea30line'] . '</option>';
		echo '</select></span>';
		
		//echo '</p>';	
	}
	
	/**
	 * Prikazovanje podnaslovov na multiple tabelah
	 */
	function edit_multiple_subtitle () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		echo '<input type="hidden" value="" name="multiple_subtitle">';
		echo '<p><label class="title" for="multiple_subtitle">';
		echo $lang['srv_multiple_subtitle'].':</label><span class="content">';
		echo '<input id="multiple_subtitle" type="checkbox" name="multiple_subtitle" value="1" '.($row['grid_subtitle1']==1?'checked':'').'></span>';
		
		echo '</p>';
		
	}
	
	// nastavitev sirina levih polj pri gridih
	function edit_grid_width() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		$display = ( ($row['tip'] == 2)) ? ' style="display:none;"' : '';
		echo '<p '.$display.'><span class="title">';
		
		$gridWidth = ($spremenljivkaParams->get('gridWidth') ? $spremenljivkaParams->get('gridWidth') : 1);
		echo $lang['srv_gridAreaSize'].':</span><span class="content">';
		echo '<select name="gridWidth" id="gridWidth" onChange="change_grid_width(this.value);">';
			echo '<option value="-1"' . ($gridWidth == -1 ? ' selected="true"' : '') . '>'.$lang['default'].' (30%)</option>';
                        // 0 bo podrla zdruzljivost za nazaj (0 = -1, default!!!!!!)
			echo '<option value="-2"' . ($gridWidth == -2 ? ' selected="true"' : '') . '>'.$lang['srv_gridAreaHidden'].' (0%)</option>';
			for($i=1; $i<=16; $i++){
				echo '<option value="'.$i * 5 .'"' . ($gridWidth == $i * 5 ? ' selected="true"' : '') . '>' . $i * 5 . '%</option>';
			}
		echo '</select></span>';
		
		echo '</p>';	
	}
	
	// nastavitve za besedilo* (st. kosov in polozaj besedila)
	function edit_textboxes() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);

		echo '<p>';	
		//stevilo kosov
		echo '<span class="title">'.$lang['srv_kosov'].': ';
		echo '<select name="text_kosov" id="kosov">';
		echo '<option value="1"' . ($row['text_kosov'] == 1 ? ' selected="true"' : '') . '>1</option>';
		echo '<option value="2"' . ($row['text_kosov'] == 2 ? ' selected="true"' : '') . '>2</option>';
		echo '<option value="3"' . ($row['text_kosov'] == 3 ? ' selected="true"' : '') . '>3</option>';
		echo '<option value="4"' . ($row['text_kosov'] == 4 ? ' selected="true"' : '') . '>4</option>';
		echo '</select></span>';
		
		//polozaj besedila
		echo '<span class="content">'.$lang['srv_polozaj'].': ';
		echo '<select name="text_orientation" class="no-margin" id="position">';
		echo '<option value="0"' . ($row['text_orientation'] == 0 ? ' selected="true"' : '') . '>' . $lang['srv_polozaj_off'] . '</option>';
		echo '<option value="1"' . ($row['text_orientation'] == 1 ? ' selected="true"' : '') . '>' . $lang['srv_polozaj_side'] . '</option>';
		echo '<option value="3"' . ($row['text_orientation'] == 3 ? ' selected="true"' : '') . '>' . $lang['srv_polozaj_above'] . '</option>';
		echo '<option value="2"' . ($row['text_orientation'] == 2 ? ' selected="true"' : '') . '>' . $lang['srv_polozaj_bottom'] . '</option>';
		echo '</select></span>';
				
		echo '</p>';	
		
		
	}
	
	// nastavitve za number (st. polj)
	function edit_num_size() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$this->spremenljivka' ORDER BY vrstni_red ASC");
		$row1 = mysqli_fetch_array($sql1);
		$id1 = $row1['id'];
		$row1 = mysqli_fetch_array($sql1);
		$id2 = $row1['id'];
	
		echo '<p><span class="title">';	
		
		echo $lang['srv_kategorij'].': ';
		echo '<select id="num_size" name="size" onchange="change_number(\'1\', \'2\'); toggle_num_limits(this.value);">';
		echo '<option value="1"' . ($row['size'] == 1 ? ' selected="true"' : '') . '>1</option>';
		echo '<option value="2"' . ($row['size'] == 2 ? ' selected="true"' : '') . '>2</option>';
		echo '</select></span>';	
	}
	
	// nastavitve za number (enota/brez enote)
	function edit_num_enota() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$this->spremenljivka' ORDER BY vrstni_red ASC");
		$row1 = mysqli_fetch_array($sql1);
		$id1 = $row1['id'];
		$row1 = mysqli_fetch_array($sql1);
		$id2 = $row1['id'];
	
		
		
		//polje za enoto
		echo '<span class="content">'.$lang['srv_enota'].': ';
		echo '<select id="num_enota" name="enota" class="no-margin" onchange="change_number(\'1\', \'2\');">';
		echo '<option value="0"' . ($row['enota'] == 0 ? ' selected="true"' : '') . '>' . $lang['no1'] . '</option>';
		echo '<option value="1"' . ($row['enota'] == 1 ? ' selected="true"' : '') . '>' . $lang['left'] . '</option>';
		echo '<option value="2"' . ($row['enota'] == 2 ? ' selected="true"' : '') . '>' . $lang['right'] . '</option>';
		echo '</select></span>';
								
		echo '</p>';	
	}
	
	// nastavitev za ranking (moznosti)
	function edit_ranking() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		echo '<p>';
		echo '<span class="title">'.$lang['srv_ranking_type'].':</span>';
		echo '<span class="content"><select name="design" id="spremenljivka_podtip_' . $row['id'] . '" spr_id="'.$row['id'].'" class="ranking_k" onchange="show_ranking_k(this.value); show_hot_spot_settings(\'' . $row['id'] . '\', this.value, \'' . $row['tip'] . '\');">';
			echo '<option value="0" '.(($row['design'] == 0) ? ' selected="true" ' : '').'>'.$lang['srv_ranking_prestavljanje'].'</option>';		
			echo '<option value="1" '.(($row['design'] == 1) ? ' selected="true" ' : '').'>'.$lang['srv_ranking_ostevilcevanje'].'</option>';
			echo '<option value="2" '.(($row['design'] == 2) ? ' selected="true" ' : '').'>'.$lang['srv_ranking_premikanje'].'</option>';
			echo '<option value="3" '.(($row['design'] == 3) ? ' selected="true" ' : '').'>'.$lang['srv_ranking_hotspot'].'</option>';
		echo '</select></span>';
		echo '</p>';
	}
	
	// nastavitev za ranking (moznosti)
	function edit_ranking_moznosti() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		$sqls = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$this->spremenljivka'");
		$count = mysqli_num_rows($sqls);
				
		echo '<div id="ranking_k" '.(($row['design'] == 2) ? 'style="display: none;"' : '').'>';
		echo '<p><span class="title">'.$lang['srv_ranking_k'].':</span>';	
		echo '<span class="content"><select name="ranking_k" class="ranking_k">';
			echo '<option value="0"' . ($row['ranking_k'] == 0 ? ' selected="true"' : '') . '>'.$lang['srv_vsi'].'</option>';
			for ($i=1; $i<$count; $i++) {
				echo '<option value="' . $i . '"' . ($row['ranking_k'] == $i ? ' selected="true"' : '') . '>' . $i . '</option>';
			}
		echo '</select></span>';
		echo '</p></div>';	
	}
	
	// nastavitev za nagovor - crta za vprasanjem
	function edit_nagovor_line(){
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		
		$nagovorLine = ($spremenljivkaParams->get('nagovorLine') ? $spremenljivkaParams->get('nagovorLine') : 0);
		echo '<p><span class="title">'.$lang['srv_nagovorLine'].':</span>';
		echo '<span class="content"><select name="nagovorLine" id="nagovorLine">';
			echo '<option value="0"' . ($nagovorLine == 0 ? ' selected="true"' : '') . '>' . $lang['srv_default'] . '</option>';
			echo '<option value="1"' . ($nagovorLine == 1 ? ' selected="true"' : '') . '>' . $lang['no1'] . '</option>';
			echo '<option value="2"' . ($nagovorLine == 2 ? ' selected="true"' : '') . '>' . $lang['yes'] . '</option>';
		echo '</select></span></p>';
	}
	
	/**
	* prikaze gumbe
	*/
	function edit_buttons () {
		global $lang;
		
		echo '<div id="vprasanje_buttons">';
		
		echo '<span class="buttonwrapper spaceLeft floatLeft">';
        echo '<a class="ovalbutton ovalbutton_orange" href="#" onclick="vprasanje_save(); return false;"><span>'.$lang['srv_zapri'].'</span></a>';
        echo '</span>';
        
		/*echo '<span class="buttonwrapper spaceLeft floatLeft">';
        echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="vprasanje_cancel(); return false;"><span>'.$lang['srv_close_profile'].'</span></a>'."\n\r";
		echo '</span>';*/
		
		echo '<span class="buttonwrapper spaceLeft floatLeft">';
        echo '<a class="ovalbutton ovalbutton_gray" href="#" onclick="brisi_spremenljivko(\''.$this->spremenljivka.'\', undefined, \'0\'); return false;"><span>'.$lang['srv_brisispremenljivko'].'</span></a>'."\n\r";
		echo '</span>';
		
		//echo '<div id="arrows_more_vprasanje" onclick=" $(\'#vprasanje_edit\').animate({ scrollTop: $(\'#vprasanje_edit\').attr(\'scrollHeight\') }, 2000); "><img src="img_0/bullet_arrow_down.png" /> '.$lang['srv_more'].'</div>';
		
		echo '</div>';
	}
	
	/**
	* kalkulacija
	* 
	*/
	function edit_compute () {
		global $lang;
		
		$b = new Branching($this->anketa);

		echo '<p>'.$lang['srv_vprasanje_tip_22'].': ';

		echo '<a href="#" onclick="calculation_editing(\'-'.$this->spremenljivka.'\'); return false;">';
		$calc = $b->calculations_display( - $this->spremenljivka);	// za spremenljivke je v srv_calculation, v cnd_id zapisan id spremenljivke kot minus (plus je za kalkulacije v ifih)
		echo $calc != '' ? $calc : $lang['srv_editcalculation'];
		echo '</a></p>';
		
	}
	
	function edit_inline_edit() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		echo '<p><label for="inline_edit" class="title">'.$lang['srv_inline_edit'].':</label> '.Help::display('srv_dropdown_quickedit');
		echo '<span class="content"><input type="hidden" value="0" name="inline_edit" />';
		echo '<input type="checkbox" value="1" id="inline_edit" name="inline_edit" '.($row['inline_edit']==1?' checked="checked"':'').' /></span></p>';
		
	}
	
	function edit_onchange_submit() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		// pri multigridu ga prikazemo samo ce imamo vklopljeno postopno resevanje
		echo '<div id="onchange_submit_div" '.($row['dynamic_mg']==0 && $row['tip']!=1 && $row['orientation']>2 ? ' style="display:none;"':'').'>';
		
		echo '<p><label for="onchange_submit" class="title">'.$lang['srv_onchange_submit'].':</label>';
		//echo '<span class="content"><input type="radio" value="0" name="onchange_submit" '.($row['onchange_submit']==0?' checked="checked"':'').' />'.$lang['no'];
		//echo '<input type="radio" value="1" name="onchange_submit" '.($row['onchange_submit']==1?' checked="checked"':'').' />'.$lang['yes'].'</span></p>';
		echo '<span class="content"><input type="hidden" value="0" name="onchange_submit" />';
		echo '<input type="checkbox" value="1" id="onchange_submit" name="onchange_submit" '.($row['onchange_submit']==1?' checked="checked"':'').' /></span></p>';
		
		echo '</div>';
	}
	
	function edit_hidden_default() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		echo '<p><span class="title">'.$lang['srv_hidden_default'].':</span><input type="radio" value="0" name="hidden_default" '.($row['hidden_default']==0?' checked="checked"':'').' />'.$lang['no'].'<input type="radio" value="1" name="hidden_default" '.($row['hidden_default']==1?' checked="checked"':'').' />'.$lang['yes'].'</p>';
			
	}
	
	function edit_captcha () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		$spremenljivkaParams = new enkaParameters($row['params']);
		$captcha = ($spremenljivkaParams->get('captcha') ? $spremenljivkaParams->get('captcha') : 0);
		$emailVerify = ($spremenljivkaParams->get('emailVerify') ? $spremenljivkaParams->get('emailVerify') : 0);
		
		$disabled = ($row['upload']>0 || $row['signature']==1 || $emailVerify==1) ? ' disabled="disabled"' : '';
		
		echo '<p><span class="title">'.$lang['srv_captcha_edit'].':</span><span class="content">';
		echo '<input type="radio" value="0" name="captcha" id="captcha_0" '.($captcha==0?' checked="checked"':'').' '.$disabled.' onClick="textSubtypeToggle(\'captcha\', this.value);" /><label for="captcha_0">'.$lang['no'].'</label>';
		echo '<input type="radio" value="1" name="captcha" id="captcha_1" '.($captcha==1?' checked="checked"':'').' '.$disabled.' onClick="textSubtypeToggle(\'captcha\', this.value);" /><label for="captcha_1">'.$lang['yes'].'</label>';
		echo '</span><br>'.$lang['srv_captcha_edit_note'].'</p>';

	}
	
	function edit_email_verify () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		$spremenljivkaParams = new enkaParameters($row['params']);
		$emailVerify = ($spremenljivkaParams->get('emailVerify') ? $spremenljivkaParams->get('emailVerify') : 0);
		$captcha = ($spremenljivkaParams->get('captcha') ? $spremenljivkaParams->get('captcha') : 0);
		
		$disabled = ($row['upload']>0 || $row['signature']==1 || $captcha==1) ? ' disabled="disabled"' : '';
		
		echo '<p><span class="title">'.$lang['srv_email_edit'].':</span><span class="content">';
		echo '<input type="radio" value="0" name="emailVerify" id="emailVerify_0" '.($emailVerify==0?' checked="checked"':'').' '.$disabled.' onClick="textSubtypeToggle(\'emailVerify\', this.value);" /><label for="emailVerify_0">'.$lang['no'].'</label>';
		echo '<input type="radio" value="1" name="emailVerify" id="emailVerify_1" '.($emailVerify==1?' checked="checked"':'').' '.$disabled.' onClick="textSubtypeToggle(\'emailVerify\', this.value);" /><label for="emailVerify_1">'.$lang['yes'].'</label>';
		echo '</span><br>'.$lang['srv_email_edit_note'].'</p>';
	}
	
	function edit_showOnAllPages () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		echo '<p><label for="showOnAllPages" class="title">'.$lang['srv_showOnAllPages_edit'].':</label>';
		echo '<span class="content"><input type="hidden" value="0" name="showOnAllPages" />';
		echo '<input type="checkbox" value="1" id="showOnAllPages" name="showOnAllPages" '.($row['showOnAllPages']==1?' checked="checked"':'').' /></span></p>';
		
	}
	
	function edit_hideRadio () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		$spremenljivkaParams = new enkaParameters($row['params']);
		$hideRadio = ($spremenljivkaParams->get('hideRadio') ? $spremenljivkaParams->get('hideRadio') : 0);

		echo '<p><label for="hideRadio" class="title">'.$lang['srv_hideRadio_edit_'.$row['tip']].':</label>';
		echo '<span class="content"><input type="hidden" value="0" name="hideRadio" />';
		echo '<input type="checkbox" value="1" id="hideRadio" name="hideRadio" '.($hideRadio==1?' checked="checked"':'').' /></span></p>';
		
	}
	
	// Prednastavljena vrednost (pri radio ali tabela - radio)
	function edit_presetValue () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		// Pri tabelah je prednastavljena vrednost srv_grid
		if($row['tip'] == 6 || $row['tip'] == 16){
			$sqlV = sisplet_query("SELECT id, naslov, variable FROM srv_grid WHERE spr_id='$this->spremenljivka' ORDER BY vrstni_red ASC");
		}
		// Pri navadnih vprasanjih je prednastavljena vrednost srv_vrednost
		else{
			$sqlV = sisplet_query("SELECT id, naslov, variable FROM srv_vrednost WHERE spr_id='$this->spremenljivka' ORDER BY vrstni_red ASC");
		}
		
		$spremenljivkaParams = new enkaParameters($row['params']);
		$presetValue = ($spremenljivkaParams->get('presetValue') ? $spremenljivkaParams->get('presetValue') : 0);

		$show = ' style="display:none;"';
		if(($row['tip'] == 1 && in_array($row['orientation'], array(0,1,2,7)))
			|| ($row['tip'] == 2 && in_array($row['orientation'], array(0,1,2,7)))
			|| ($row['tip'] == 6 && in_array($row['enota'], array(0,1,8))))
			$show = '';
		echo '<p class="presetValue" '.$show.'><label class="title">'.$lang['srv_vrednost_default'].':</label>';
		
		echo '<span class="content"><select id="presetValue" name="presetValue" style="width:120px; text-overflow:ellipsis;">';
		echo '	<option value="0">'.$lang['no'].'</option>';
		while($rowV = mysqli_fetch_array($sqlV)){
			
            $naslov = (strlen($rowV['naslov']) > 20) ? substr($rowV['naslov'], 0, 20).'...' : $rowV['naslov'];
            $naslov = strip_tags($naslov);
            $naslov = ($naslov == '') ? '' : '('.$naslov.')';


			echo '	<option value="'.$rowV['id'].'" '.($presetValue == $rowV['id'] ? ' selected="selected"' : '').'>'.$rowV['variable'].' '.$naslov.'</option>';
		}
		echo '</select></span>';
		
		echo '</p>';
	}	
	
	// Urejanje velikosti polja drugo
	function edit_other_field () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		// Ce imamo kaken odgovor drugo
		$sql = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$row[id]' AND other='1'");
		//if(mysqli_num_rows($sql) > 0){
		
			$spremenljivkaParams = new enkaParameters($row['params']);
			
			$otherWidth = ($spremenljivkaParams->get('otherWidth') ? $spremenljivkaParams->get('otherWidth') : -1);
			$otherHeight = ($spremenljivkaParams->get('otherHeight') ? $spremenljivkaParams->get('otherHeight') : 1);
		
			//echo '<fieldset><legend>'.$lang['srv_other_size'].'</legend>';
			//echo '<fieldset id = '.$row['id'].'><legend>'.$lang['srv_other_size'].'</legend>';
			//echo '<fieldset id = fieldset'.$row['id'].' ><legend onclick="$(\'#fieldset'.$row['id'].'\').hide()">'.$lang['srv_other_size'].'</legend>';
			if(mysqli_num_rows($sql) > 0){	//ce je prisotna moznost Drugo,
				echo '<fieldset id = fieldset'.$row['id'].'><legend>'.$lang['srv_other_size'].'</legend>';	//pokazi fieldset
			}
			else{							//drugace
				echo '<fieldset id = fieldset'.$row['id'].' hidden><legend>'.$lang['srv_other_size'].'</legend>';	//skrij fieldset
			}
			//echo '<fieldset id = fieldset'.$row['id'].' ><legend onclick="console.log(\'Tralala\');">'.$lang['srv_other_size'].'</legend>';
			
			echo '<p>';
			
			echo $lang['srv_textAreaWidth'].': ';
			echo '<select name="otherWidth" id="width">';
			echo '<option value="-1"' . ($otherWidth == -1 ? ' selected="true"' : '') . '>'.$lang['default'].'</option>';
			for($i=5; $i<61; $i+=5){
				echo '<option value="'.$i.'"' . ($otherWidth == $i ? ' selected="true"' : '') . '>' . $i . '</option>';
			}
			echo '</select>';	
			
			echo '<span class="content">'.$lang['srv_textAreaSize'].'';
			echo '<select name="otherHeight" class="no-margin" id="height">';
				echo '<option value="1"' . ($otherHeight == 1 ? ' selected="true"' : '') . '>' . $lang['srv_textArea1line'] . '</option>';
				echo '<option value="3"' . ($otherHeight == 3 ? ' selected="true"' : '') . '>' . $lang['srv_textArea3line'] . '</option>';
				echo '<option value="5"' . ($otherHeight == 5 ? ' selected="true"' : '') . '>' . $lang['srv_textArea5line'] . '</option>';
				echo '<option value="7"' . ($otherHeight == 7 ? ' selected="true"' : '') . '>' . $lang['srv_textArea7line'] . '</option>';
				echo '<option value="10"' . ($otherHeight == 10 ? ' selected="true"' : '') . '>' . $lang['srv_textArea10line'] . '</option>';
				echo '<option value="20"' . ($otherHeight == 20 ? ' selected="true"' : '') . '>' . $lang['srv_textArea20line'] . '</option>';
				echo '<option value="30"' . ($otherHeight == 30 ? ' selected="true"' : '') . '>' . $lang['srv_textArea30line'] . '</option>';
			echo '</select></span>';
			
			echo '</p>';
			
			echo '</fieldset>';
		//}
	}
	
	/**
	* editiranje stevila vidnih moznosti selectbox
	*/
	function edit_selectbox_size () {
		global $lang;
		
		$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$this->spremenljivka'");
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);	

        $prvaVrstica = ($spremenljivkaParams->get('prvaVrstica') ? $spremenljivkaParams->get('prvaVrstica') : 1);
		$prvaVrstica_roleta = ($spremenljivkaParams->get('prvaVrstica_roleta') ? $spremenljivkaParams->get('prvaVrstica_roleta') : 1);
		$sbSizeVse = mysqli_num_rows($sql1); //stevilo vnesenih moznih odgovorov

        echo '<span class="content"><select name="sbSizeVse" id="selectboxSizeVse'.$this->spremenljivka.'" style="display:none;">';
		echo '<option>'.$sbSizeVse.'</option>';
		echo '</select>';
		
		// Nastavimo prikaz nastavitve za stevilo vidnih moznosti v select box - ce imamo selectbox je ta moznost vidna, drugace ni
		if (($row['tip'] == 6 || $row['tip'] == 16) && $row['enota'] != 2){
			$display = (($row['tip'] == 6 || $row['tip'] == 16) && $row['enota'] != 6 && $row['enota'] != 8) ? ' style="display:none;"' : '';
			$displayprvavrstica_roleta =' style="display:none;"';
			$sbSizeVse = $row['grids'];
		}
		elseif($row['tip'] == 1 || $row['tip'] == 2){
			$display = (($row['tip'] == 1 || $row['tip'] == 2) && $row['orientation'] != 6) ? ' style="display:none;"' : '';	
			$displayprvavrstica_roleta =' style="display:none;"';
		}
		elseif($row['tip'] == 3 || ($row['tip'] == 6 && $row['enota'] == 2)){
			$display = ' style="display:none;"';
			$displayprvavrstica_roleta = ' ';
		}
		
		$sbSize = ($spremenljivkaParams->get('sbSize') ? $spremenljivkaParams->get('sbSize') : $sbSizeVse);
		
		echo '<div class="dropselectboxsize" '.$display.'>';			
		echo '<p><span class="title" >'.$lang['srv_stevilo_odgovorov_selectbox'].':</span>';
		echo '<span class="content"><select name="sbSize" id="selectboxSize'.$this->spremenljivka.'">';
		
		for($i=2; $i<$sbSizeVse; $i++){
			echo '<option value="'.$i.'"'.($sbSize == $i ? ' selected="true"' : '') . '>'.$i.'</option>';
		}
		echo '<option value="'.$sbSizeVse.'"'.($sbSize == $sbSizeVse ? ' selected="true"' : '') . '>'.$lang['srv_select_box_vse'].'</option>';
		echo '</select></span>';
		echo '</p>';
		echo '</div>';

		
		echo '<div class="dropselectboxsizeprvavrstica" '.$display.'>';
		echo '<p><span class="title" >'.$lang['srv_select_box_prva_vrstica'].':</span>';
		echo '<span class="content"><select name="prvaVrstica" id="prvaVrstica'.$this->spremenljivka.'">'; //echo '<span class="content"><select name="prvaVrstica" id="prvaVrstica">';
		
		echo '<option value="1"'.($prvaVrstica == 1 ? ' selected="true"' : '') . '>'.$lang['srv_select_box_prva_vrstica_1'].'</option>';
		echo '<option value="2"'.($prvaVrstica == 2 ? ' selected="true"' : '') . '>'.$lang['srv_select_box_prva_vrstica_2'].'</option>';
		echo '<option value="3"'.($prvaVrstica == 3 ? ' selected="true"' : '') . '>'.$lang['srv_select_box_prva_vrstica_3'].'</option>';
		echo '</select></span>';
		echo '</p>';
		echo '</div>';
		
		
		echo '<div class="dropselectboxsizeprvavrstica_roleta" '.$displayprvavrstica_roleta.'>';			
		echo '<p><span class="title" >'.$lang['srv_select_box_prva_vrstica'].':</span>';
		echo '<span class="content"><select name="prvaVrstica_roleta" id="prvaVrstica_roleta'.$this->spremenljivka.'">'; //echo '<span class="content"><select name="prvaVrstica" id="prvaVrstica">';
		
		echo '<option value="1"'.($prvaVrstica_roleta == 1 ? ' selected="true"' : '') . '>'.$lang['srv_select_box_prva_vrstica_2'].'</option>';
		
		echo '<option value="3"'.($prvaVrstica_roleta == 3 ? ' selected="true"' : '') . '>'.$lang['srv_select_box_prva_vrstica_3'].'</option>';
		echo '</select></span>';
		echo '</p>';
		echo '</div>';
		
	}
	
	// Nastavitev text vprasanja, da se pod njim prikazejo odgovori prejsnjih respondentov
	private function edit_show_prevAnswers(){
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		$spremenljivkaParams = new enkaParameters($row['params']);	
        $prevAnswers = ($spremenljivkaParams->get('prevAnswers') ? $spremenljivkaParams->get('prevAnswers') : 0);
	
		echo '<p>';
		echo '<label for="prevAnswers" class="title">'.$lang['srv_setting_prevAnswers'].': </label>';
		echo '<span class="content"><input type="hidden" name="prevAnswers" value="0" />';
		echo '<input type="checkbox" id="prevAnswers" name="prevAnswers" value="1" '.(($prevAnswers == 1) ? ' checked="checked" ' : '').' />';		
		echo '</span></p>';		
	}
	
	/**
	* CUSTOM PICTURE RADIO
	* Nastavitve za slikovni tip radio gumbov (smiley, thumbs up...)
	*/
	function edit_custom_picture_radio(){
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);	
		
		$displayCustomRadio = ($row['orientation'] == 9 || $row['tip'] == 6 && $row['enota'] == 12) ? '' :' style="display:none;"';

        $customRadioNumber = ($spremenljivkaParams->get('customRadioNumber') ? $spremenljivkaParams->get('customRadioNumber') : '5');
        $customRadio = $spremenljivkaParams->get('customRadio');

        echo '<div class="custom-picture-radio" '.$displayCustomRadio.'>';
            echo '<p><span class="title" >'.$lang['srv_custom-picture_radio_label'].':</span>';
            echo '<span class="content"><select name="customRadio" id="customRadioSelect'.$this->spremenljivka.'">';
            echo '<option value="star" '.($customRadio == "star" ? 'selected="selected"' : '').'>'.$lang['srv_custom-picture_radio_star'].'</option>';
            echo '<option value="smiley" '.($customRadio == "smiley" ? 'selected="selected"' : '').'>'.$lang['srv_custom-picture_radio_smiley'].'</option>';
            echo '<option value="thumb" '.($customRadio == 'thumb' ? 'selected="selected"' : '').'>'.$lang['srv_custom-picture_radio_thumb'].'</option>';
            echo '<option value="heart" '.($customRadio == 'heart' ? 'selected="selected"' : '').'>'.$lang['srv_custom-picture_radio_heart'].'</option>';
            echo '<option value="flag" '.($customRadio == 'flag' ? 'selected="selected"' : '').'>'.$lang['srv_custom-picture_radio_flag'].'</option>';
            echo '<option value="user" '.($customRadio == 'user' ? 'selected="selected"' : '').'>'.$lang['srv_custom-picture_radio_user'].'</option>';
            echo '</select></span>';
            echo '</p>';

            $preveriOdgovore = sisplet_query("SELECT spr_id FROM srv_data_vrednost" . $this->db_table. " WHERE spr_id='".$this->spremenljivka."'");
            $stOdgovorov=1;
            if(mysqli_num_rows($preveriOdgovore)){
                $stOdgovorov = $customRadioNumber;
            }

            if($row['tip'] != 6) {
                echo '<p><span class="title" >' . $lang['srv_custom-picture_number_label'] . ':</span>';
                echo '<span class="content"><select name="customRadioNumber" id="customRadioNumberSelect' . $this->spremenljivka . '">';
                for ($n = $stOdgovorov ; $n < 13; $n++) {
                    echo '<option value="' . $n . '" ' . (($customRadioNumber == $n) ? 'selected="selected"' : '') . '>' . $n . '</option>';
                }
                echo '</select></span>';
                echo '</p>';
            }
        echo '</div>';
	}

    /**
     * Funkcija prikaže izbir število odgovorov
     */
	function edit_visual_analog_scale(){
        global $lang;

        $row = Cache::srv_spremenljivka($this->spremenljivka);
        $spremenljivkaParams = new enkaParameters($row['params']);

        $displayAnalognoSkalo = ($row['orientation'] == 11) ? '' :' style="display:none;"';

        $vizualnaSkalaNumber = ($spremenljivkaParams->get('vizualnaSkalaNumber') ? $spremenljivkaParams->get('vizualnaSkalaNumber') : '5');

        echo '<div class="vizualna-analogna-skala" '.$displayAnalognoSkalo.'><p><span class="title" >'.$lang['srv_custom-picture_number_label'].':</span>';
        echo '<span class="content"><select name="vizualnaSkalaNumber" id="vizualnaSkalaNumberSelect'.$this->spremenljivka.'">';
        for($n=2; $n<8; $n++){
            echo '<option value="'.$n.'" '.(($vizualnaSkalaNumber == $n) ? 'selected="selected"' : '').'>'.$n.'</option>';
        }
        echo '</select></span>';
        echo '</p>';
        echo '</div>';
    }
        
        // nastavitev za lokacijo podtip
	function edit_subtype_map() {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
	
		echo '<p>';
		echo '<span class="title">'.$lang['srv_vprasanje_podtip_map'].':</span>';
		echo '<span class="content">';
                echo '<select name="podtip_lokacija" id="spremenljivka_podtip_' . $row['id'] . '" spr_id="'.$row['id'].'" onChange="change_map(this.value, '.$this->spremenljivka.');">';
                    echo '<option value="1" '.(($row['enota'] == 1) ? ' selected="true" ' : '').'>'.$lang['srv_vprasanje_mylocation'].'</option>';
                    echo '<option value="2" '.(($row['enota'] == 2) ? ' selected="true" ' : '').'>'.$lang['srv_vprasanje_multilocation'].'</option>';	
                    echo '<option value="3" '.(($row['enota'] == 3) ? ' selected="true" ' : '').'>'.$lang['srv_vprasanje_chooselocation'].'</option>';
			
		echo '</select></span>';
		echo '</p>';
	}
        
	/**
	 * Tip multilokacija
	 * nastavitev za lokacijo input type
	 * @global type $lang
	 */
	function edit_input_type_map() {
		global $lang;

		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$newParams = new enkaParameters($row['params']);
		$input = $newParams->get('multi_input_type') ? $newParams->get('multi_input_type') : 'marker';
		
		echo '<p id="multi_input_type_map" '. ($row['enota'] == 2 ? '' : 'style="display: none;"').'>';
		echo '<span class="title" >'.$lang['srv_vprasanje_input_type_map'].': </span>';
		echo '<span class="content">';

		//roleta
		echo '<select name="multi_input_type" id="multi_input_type_' . $row['id'] . '" spr_id="'.$row['id'].'" onChange="change_input_map(this.value, '.$this->spremenljivka.');">';
				echo '<option value="marker" '.(($input == 'marker') ? ' selected="true" ' : '').'>'.$lang['srv_vprasanje_marker'].'</option>';		
				echo '<option value="polyline" '.(($input == 'polyline') ? ' selected="true" ' : '').'>'.$lang['srv_vprasanje_line'].'</option>';
				echo '<option value="polygon" '.(($input == 'polygon') ? ' selected="true" ' : '').'>'.$lang['srv_vprasanje_polygon'].'</option>';		

		echo '</select></span>';
		echo '</p>';	
    }
	
	/**
	* spremeni tip vprašanja
	* static je, da se lažje kliče še iz ostalih classov
	*/
	public static function change_tip ($spremenljivka, $tip, $podtip = null) {
		global $lang;
		
		if ($spremenljivka <= 0) return;
		
		sisplet_query("UPDATE srv_spremenljivka SET tip = '$tip' WHERE id='$spremenljivka'");
		
		Cache::clear_cache();
		$row = Cache::srv_spremenljivka($spremenljivka);
		
		if (isset ($_GET['anketa']))
			$anketa = $_GET['anketa'];
		elseif (isset ($_POST['anketa'])) 
			$anketa = $_POST['anketa'];
		elseif ($anketa != 0) 
			$anketa = $anketa;
			
		/* TODO
		 * tukaj se doda se dodatne stvari, ki jih je treba narediti za vsak tip posebej ob spremembi
		 */
		 
		 
		// checkbox ima zraven default opombo - nastavimo na ustreznen jezik - uposteva se jezik za respondente in ne admin!
		$lang_admin = SurveyInfo::getInstance()->getSurveyColumn('lang_admin');
		$lang_resp  = SurveyInfo::getInstance()->getSurveyColumn('lang_resp');
		
		// nastavimo na jezik za respondentov vmesnik
		$file = '../../lang/'.$lang_resp.'.php';
		include($file);		
			
		// checkbox ima zraven default opombo
		if ($tip == 2 && $row['orientation'] != 6) {
			$s = sisplet_query("UPDATE srv_spremenljivka SET info='$lang[srv_info_checkbox]' WHERE id = '$spremenljivka'");
		 }
		 // checkbox s selectbox ima zraven default opombo
		 elseif($tip == 2 && $row['orientation'] == 6){
			$s = sisplet_query("UPDATE srv_spremenljivka SET info='$lang[srv_info_selectbox]' WHERE id = '$spremenljivka'");
		}
		// grid s checkboxi ima zraven default opombo
		elseif ($tip == 16 && $row['enota'] != 6) {
			$s = sisplet_query("UPDATE srv_spremenljivka SET info='$lang[srv_info_checkbox]' WHERE id = '$spremenljivka'");
		 }
		 //grid s selectboxi ima zraven default opombo
		 elseif($tip == 16 && $row['enota'] == 6){
			$s = sisplet_query("UPDATE srv_spremenljivka SET info='$lang[srv_info_selectbox]' WHERE id = '$spremenljivka'");
		}
		else {
			$s = sisplet_query("SELECT info FROM srv_spremenljivka WHERE id = '$spremenljivka'");
			$r = mysqli_fetch_array($s);
			// if ($r['info'] == $lang['srv_info_checkbox'])
			$s = sisplet_query("UPDATE srv_spremenljivka SET info='' WHERE id = '$spremenljivka'");
		}
		
		// nastavimo nazaj na admin jezik
		$file = '../../lang/'.$lang_admin.'.php';
		include($file);
			
		
		//besedilo* - pobrisemo odvecne vrednosti
		if ($tip == 21){
                    sisplet_query("UPDATE srv_spremenljivka SET size=text_kosov WHERE id='$spremenljivka'");
                    $row['size'] = $row['text_kosov']; // ker se spodaj bere se size
                    sisplet_query("DELETE FROM srv_vrednost WHERE vrstni_red > '$row[text_kosov]' AND spr_id='$spremenljivka' AND other = 0");
                    sisplet_query("UPDATE srv_vrednost SET naslov='$lang[srv_new_text]', naslov_graf='$lang[srv_new_text]' WHERE spr_id='$spremenljivka' AND other = 0");

                    //fotografiranje ima svojo variablo
                    if($podtip != null && $podtip == 7){
                        $sql1 = sisplet_query("SELECT s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$anketa' AND s.tip='21' AND s.upload=2 AND s.id!='$spremenljivka'");
                        if (!$sql1) echo 'err453'.mysqli_error($GLOBALS['connect_db']);
                        $c = 0;
                        while ($row1 = mysqli_fetch_array($sql1)) {
                                $row1['variable'] = (int)str_replace('FOTO', '', $row1['variable']);
                                if ($row1['variable'] > $c)
                                        $c = $row1['variable'];
                        }
                        $c++;
                        $variable = 'FOTO'.$c;
                        sisplet_query("UPDATE srv_spremenljivka SET variable='$variable', variable_custom='1' WHERE id = '$spremenljivka'");
                    }
		}
		
		// number ima na zacetku size 1 in enoto 0
		if ($row['tip'] == 7) {
			sisplet_query("UPDATE srv_spremenljivka SET size='1', enota='0' WHERE id = '$spremenljivka'");
			$row['size'] = 1;
			sisplet_query("DELETE FROM srv_vrednost WHERE vrstni_red > '".$row['size']."' AND spr_id='$spremenljivka' AND other = 0");
			sisplet_query("UPDATE srv_vrednost SET naslov='$lang[srv_new_text]', naslov_graf='$lang[srv_new_text]' WHERE spr_id='$spremenljivka' AND other = 0");
		}
		
		//multigrid ima na zacetku enoto 0 (subtype)
		if ($row['tip'] == 6 || $row['tip'] == 16) {
			sisplet_query("UPDATE srv_spremenljivka SET enota='0' WHERE id = '$spremenljivka'");
		}
                //lokacija ima na zacetku enoto 1 (subtype) in parametre - moja lokacija
		if ($row['tip'] == 26) {
			sisplet_query("UPDATE srv_spremenljivka SET enota='1' WHERE id = '$spremenljivka'");
		}
		
		// kalkulacija ima svojo variablo in 2 decimalki
		if ($tip == 22)	{
		
			$sql1 = sisplet_query("SELECT s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$anketa' AND s.tip='22' AND s.id!='$spremenljivka'");
			if (!$sql1) echo 'err453'.mysqli_error($GLOBALS['connect_db']);
			$c = 0;
                        $row['size'] = 1;
			while ($row1 = mysqli_fetch_array($sql1)) {
				$row1['variable'] = (int)str_replace('C', '', $row1['variable']);
				if ($row1['variable'] > $c)
					$c = $row1['variable'];
			}
			$c++;
			$variable = 'C'.$c;
	
			sisplet_query("UPDATE srv_spremenljivka SET naslov='$lang[srv_vprasanje_tip_22]', variable='C$c', variable_custom='1', sistem='1', decimalna='2' WHERE id = '$spremenljivka'");
		}
		
		// Kvota ima svojo variablo
		if ($tip == 25)	{
			$sql1 = sisplet_query("SELECT s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$anketa' AND s.tip='25' AND s.id!='$spremenljivka'");
			if (!$sql1) echo 'err453'.mysqli_error($GLOBALS['connect_db']);
			$c = 0;
			while ($row1 = mysqli_fetch_array($sql1)) {
				$row1['variable'] = (int)str_replace('QU', '', $row1['variable']);
				if ($row1['variable'] > $c)
					$c = $row1['variable'];
			}
			$c++;
			$variable = 'QU'.$c;

			sisplet_query("UPDATE srv_spremenljivka SET naslov='$lang[srv_vprasanje_tip_25]', variable='QU$c', variable_custom='1', sistem='1' WHERE id = '$spremenljivka'");
		}

		// generator imen ima svojo variablo in velikost 20 (max imen je 20)
		if ($tip == 9)	{
		
			$sql1 = sisplet_query("SELECT s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$anketa' AND s.tip='9' AND s.id!='$spremenljivka'");
			if (!$sql1) echo 'err453'.mysqli_error($GLOBALS['connect_db']);
			$c = 0;
			while ($row1 = mysqli_fetch_array($sql1)) {
				$row1['variable'] = (int)str_replace('G', '', $row1['variable']);
				if ($row1['variable'] > $c)
					$c = $row1['variable'];
			}
			$c++;
			$variable = 'G'.$c;
	
			sisplet_query("UPDATE srv_spremenljivka SET naslov='$lang[srv_vprasanje_tip_long_9]', variable='$variable', size='20', label='$lang[srv_name_generator]' WHERE id = '$spremenljivka'");
			
			$row['size'] = 20;
		}
		
		// radio, checkbox, dropdown, vse tabele (tudi multiple), razvrscanje, vsota, besedilo, number, SN-imena
		if ($row['tip']<=3 || $row['tip']==6 || $row['tip']==16 || $row['tip']==19 || $row['tip']==20 || $row['tip'] == 24 || $row['tip']==17 || $row['tip']==18 || $row['tip'] == 7 || $row['tip'] == 9 || $row['tip'] == 21 || $row['tip'] == 22) {
			// pri spremembi tipa (npr. iz radio v checkbox) ne smemo se enkrat dodat v srv_vrednost
			$sqlc = sisplet_query("SELECT COUNT(*) AS count FROM srv_vrednost WHERE spr_id='$spremenljivka'");
			$rowc = mysqli_fetch_array($sqlc);
			$rowc['count']++;	// da pri novem ne zacnemo z 0, ker so potem napacne labele variabel
			$values = "";
			for ($i = $rowc['count']; $i <= $row['size']; $i++) {
				if ($values != "") $values .= ",";
				if ($row['tip']==21 || $row['tip']==7 || $row['tip']==22) $def_naslov = $lang['srv_new_text']; else $def_naslov = $lang['srv_new_vrednost'].' '.$i;
				$values .= " ('$spremenljivka', '$i', '$def_naslov', '$i', '$def_naslov') ";
			}
			$sql1 = sisplet_query("INSERT INTO srv_vrednost (spr_id, variable, naslov, vrstni_red, naslov2) VALUES $values");
		} else {
			sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$spremenljivka'");
		}
		
		// tabele imajo zapis v srv_grid - razen multiple tabela
		//if ($row['tip'] == 6 || $row['tip'] == 16 || $row['tip'] == 19 || $row['tip'] == 20) {
		if ($row['tip'] == 6 || $row['tip'] == 16 || $row['tip'] == 19 || $row['tip'] == 20 || ($row['tip'] == 2)) {
			// tukaj ni problema, ce ob spremembi tipa se enkrat naredimo INSERT, ker so nastavljeni kljuci in se ne da 2x vnest
			$values = "";
			for ($i=1; $i<=$row['grids']; $i++) {
				if ($values != "") $values .= ", ";
				$values .= " ('$i', '$spremenljivka', '$lang[srv_new_grid]', '$i', '$i') ";
			}
			$sql1 = sisplet_query("INSERT INTO srv_grid (id, spr_id, naslov, vrstni_red, variable) VALUES $values");
			
		} else {
			sisplet_query("DELETE FROM srv_grid WHERE spr_id = '$spremenljivka'");
		}
		
		// multiple tabela
		if ($row['tip'] == 24) {
			// zaenkrat nic
		} else {
			// pri brisanju multiple grid vprasanja, moramo pobrisate tudi vse child spremenljivke (ker kljuci niso nastavljeni)
			$sqld = sisplet_query("SELECT spr_id FROM srv_grid_multiple WHERE parent='$spremenljivka'");
			while ($rowd = mysqli_fetch_array($sqld)) {
				sisplet_query("DELETE FROM srv_spremenljivka WHERE id='$rowd[spr_id]'");
			}
			sisplet_query("DELETE FROM srv_grid_multiple WHERE parent = '$spremenljivka' AND ank_id = '$anketa'");
		}
		
		Cache::clear_cache();
		
	}
	
	/**
	* nastavi vprasanje na tip captcha
	* 
	*/
	function set_captcha () {
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		$newParams = new enkaParameters($row['params']);
			
		$newParams->set('captcha', 1);
		
		$params = $newParams->getString();
		$update .= ", params = '$params' ";
		
		sisplet_query("UPDATE srv_spremenljivka SET tip='21', reminder='2' $update WHERE id = '$this->spremenljivka'");
	}
	
	/**
	* nastavi multigrid na datum
	* 
	*/
	function set_datum () {
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		$newParams = new enkaParameters($row['params']);
			
		$newParams->set('multigrid-datum', 1);
		
		$params = $newParams->getString();
		$update .= ", params = '$params' ";
		
		sisplet_query("UPDATE srv_spremenljivka SET tip='19' $update WHERE id = '$this->spremenljivka'");
	}
	
	/**
	* nastavi vprasanje na tip email
	* 
	*/
	function set_email ($reminder=2) {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);	
		$newParams = new enkaParameters($row['params']);		
		$newParams->set('emailVerify', 1);
		
		$params = $newParams->getString();
		$update .= ", params = '$params' ";
		
		sisplet_query("UPDATE srv_spremenljivka SET variable='email', variable_custom='1', info='(".$lang['srv_email_example'].")', tip='21', reminder='".$reminder."' $update WHERE id = '$this->spremenljivka'");
		//sisplet_query("UPDATE srv_spremenljivka SET info = '(".$lang['srv_email_example'].")' WHERE id = '$this->spremenljivka'");
	}
	
	/**
	* nastavi vprasanje na tip url
	* 
	*/
	function set_url () {
		global $lang;
		
		sisplet_query("UPDATE srv_spremenljivka SET info = '(".$lang['srv_url_example'].")' WHERE id = '$this->spremenljivka'");
	}
	
	/**
	* nastavi vprasanje na tip upload
	* 
	*/
	function set_upload () {
		global $lang;
		
		sisplet_query("UPDATE srv_spremenljivka SET upload = '1' WHERE id = '$this->spremenljivka'");
	}
        
        /**
	* nastavi vprasanje na tip fotografija
	* 
	*/
	function set_fotografija () {
		global $lang;

		sisplet_query("UPDATE srv_spremenljivka SET upload = '2' WHERE id = '$this->spremenljivka'");
	}
	
	/**
	* nastavi vprasanje na tip signature
	* 
	*/
	function set_signature () {
		global $lang;
		sisplet_query("UPDATE srv_spremenljivka SET signature = '1' $update WHERE id = '$this->spremenljivka'");
	}
	
	/**
	* nastavi vprasanje na tip GDPR
	* 
	*/
	function set_GDPR () {
		global $lang;
		
        $lang_admin = SurveyInfo::getInstance()->getSurveyColumn('lang_admin');
        $lang_resp  = SurveyInfo::getInstance()->getSurveyColumn('lang_resp');
        
        // nastavimo na jezik za respondentov vmesnik
        $file = '../../lang/'.$lang_resp.'.php';
        include($file);
        

		$user_settings = GDPR::getSurveySettings($this->anketa);
		
		// GDPR je radio (da / ne) tip vprasanja z predefiniranim textom
		$naslov = GDPR::getSurveyIntro($this->anketa);
		
		// Poporavimo naslov vprasanja
		sisplet_query("UPDATE srv_spremenljivka SET variable='gdpr', variable_custom='1', naslov='".$naslov."', tip='1', reminder='2' WHERE id = '$this->spremenljivka'");
		
		// Pobrisemo odvecne variable
		sisplet_query("DELETE FROM srv_vrednost WHERE spr_id = '$this->spremenljivka' AND vrstni_red > '2'");
		
		// Popravimo text variabel
		sisplet_query("UPDATE srv_vrednost SET naslov='".$lang['srv_gdpr_intro_no']."' WHERE spr_id = '$this->spremenljivka' AND vrstni_red = '1'");
		sisplet_query("UPDATE srv_vrednost SET naslov='".$lang['srv_gdpr_intro_yes']."' WHERE spr_id = '$this->spremenljivka' AND vrstni_red = '2'");
		
		// Popravimo, da ima anketa vklopljen gdpr
		sisplet_query("INSERT INTO srv_gdpr_anketa (ank_id) VALUES ('".$this->anketa."')");


        // nastavimo nazaj na admin jezik
        $file = '../../lang/'.$lang_admin.'.php';
        include($file);
	}
	
	/**
	* nastavi na tip text box
	* 
	*/
	function set_box () {
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		$newParams = new enkaParameters($row['params']);
			
		$newParams->set('taSize', 5);
		$newParams->set('taWidth', 50);
		
		$params = $newParams->getString();
		$update .= ", params = '$params' ";
		
		sisplet_query("UPDATE srv_spremenljivka SET tip='21' $update WHERE id = '$this->spremenljivka'");
	}
        
    /**
	* nastavi vprasanje na tip mape
	* 
	*/
	function set_map ($podtip) {		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$newParams = new enkaParameters($row['params']);
		
		//multi lokacija
		if($podtip == 2){
			$newParams->set('user_location', 0);
			$newParams->set('fokus_mape', 'Slovenija');
			$newParams->set('marker_podvprasanje', 0);
			$newParams->set('max_markers', 3);
			$newParams->set('dodaj_searchbox', 1);
                        $newParams->set('multi_input_type', 'marker');
		}
		//moja lokacija
		elseif($podtip == 1){
			$newParams->set('user_location', 1);
			$newParams->set('fokus_mape', 'Slovenija');
			$newParams->set('marker_podvprasanje', 0);
			$newParams->set('max_markers', 3);
			$newParams->set('dodaj_searchbox', 1);
                        $newParams->set('multi_input_type', 'marker');
		}
		
		$params = $newParams->getString();
		$update .= ", params = '$params' ";
		
        sisplet_query("UPDATE srv_spremenljivka SET enota = '$podtip' $update WHERE id = '$this->spremenljivka'");
	}
	
	 /**
	* nastavi text nagovoru za aktivacijo chata
	* 
	*/
	function set_chat() {
		global $lang;

		$title = '<p>'.$lang['srv_chat_question_text'].'</p><div class="tawk-chat-activation button">'.$lang['srv_chat_turn_on'].'</div>';
		
        sisplet_query("UPDATE srv_spremenljivka SET naslov = '".$title."' WHERE id = '$this->spremenljivka'");
	}
	
	/**
	* nastavi vprasanje na tip slider
	* 
	*/
	function set_slider () {
		
        $s = sisplet_query("UPDATE srv_spremenljivka SET ranking_k='1', vsota_min='0', vsota_limit='100', num_useMin='1', num_useMax='1' WHERE id = '$this->spremenljivka'");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
	}
	
	
	/**
	* pohendla ajax klice za vprasanje
	* 
	*/
	function ajax () {
		
		if (isset($_POST['spremenljivka'])) $this->spremenljivka = $_POST['spremenljivka'];
				
		$ajax = 'ajax_' . $_GET['a'];
		
		if ( method_exists('Vprasanje', $ajax) )
			$this->$ajax();
		else
			echo 'method '.$ajax.' does not exist';		
	}
	
	function ajax_vprasanje_fullscreen () {	
		$this->display();
	}
	
	function ajax_vprasanje_tab () {
		$this->vprasanje_edit();
	}
	
	function ajax_vprasanje_save () {
		global $lang;
		global $default_grid_values;
		
		if ($this->spremenljivka < -3) return;		// -1, -2, -3 so uvod, zakljucek in statistika
		
		$lang_id = $_POST['lang_id'];
		
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
		$update = '';
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		$diferencial_trak = ($spremenljivkaParams->get('diferencial_trak') ? $spremenljivkaParams->get('diferencial_trak') : 0); //za checkbox

		// urejanje grida
		if (isset($_POST['edit_grid'])) {
					
			//na zacetku pobrisemo missing gride (ker jih na koncu dodamo)
			sisplet_query("DELETE FROM srv_grid WHERE spr_id='$this->spremenljivka' AND vrstni_red > '$i' AND other != '0'");
			
			$vrstni_red = 0;
			$i = 0;
			foreach ($_POST as $key => $v) {
                
				if (substr($key, 0, 12) == 'grid_naslov_') {
					$vrstni_red++;
					
					$grid = substr($key, 12);
					$naslov = $_POST['grid_naslov_'.$grid];
					$variable = $grid;
					$id= $vrstni_red;
					
                    $other = '0';
                    
					# manjkoajoče vrednosti (ne vem, zavrnil ...
					if (isset($_POST['missing_value_checkbox']) && is_array($_POST['missing_value_checkbox'])) {
                        
                        if (in_array($grid, $_POST['missing_value_checkbox'])) {
							# grid je manjkajoča vrednost
							$other = $grid.'';
							$id =  $grid;
                        } 
                        else {
							# grid je normalna vrednost
							$i++;
						}
                    } 
                    else {
						# grid je normalna vrednost
						$i++;
					}
						
					// ne sme bit replace into, ker najprej zbrise in nato inserta, in pobrise vse tabele, ki se navezujejo s foreign keyi
					// v update ni variable, ker se variable vpise samo v insertu, potem ga pa spreminjamo samo spodaj rocno - _variable_edit
					$s = sisplet_query("INSERT INTO srv_grid (id, spr_id, naslov, vrstni_red, variable, other, part) VALUES ('$id', '$this->spremenljivka', '$naslov', '$vrstni_red', '$variable', '$other', '1') ON DUPLICATE KEY UPDATE naslov=VALUES(naslov), vrstni_red=VALUES(vrstni_red), other=VALUES(other), part='1'");
					if (!$s) echo mysqli_error($GLOBALS['connect_db']);
					
					// pri double gridih podvojimo vnose -> part=2
					if($row['enota'] == 3){
						$vrstni_red2 = $vrstni_red + ((int) $_POST['grids_count']);
						$id = $vrstni_red2;

						$s = sisplet_query("INSERT INTO srv_grid (id, spr_id, naslov, vrstni_red, variable, other, part) VALUES ('$id', '$this->spremenljivka', '$naslov', '$vrstni_red2', '$variable', '$other', 2) ON DUPLICATE KEY UPDATE naslov=VALUES(naslov), vrstni_red=VALUES(vrstni_red), other=VALUES(other), part=2");
					}
				}
			}
			
			//popravimo st gridov ce imamo posebne vrednosti
			$update .= ", grids = '$i' ";
			sisplet_query("DELETE FROM srv_grid WHERE spr_id='$this->spremenljivka' AND vrstni_red > '$i' AND other = '0' AND part = '1'");
			
			if($row['enota'] == 3){
				$i *= 2;
				sisplet_query("DELETE FROM srv_grid WHERE spr_id='$this->spremenljivka' AND vrstni_red > '$i' AND other = '0' AND part = '2'");			
			}
			else{
				sisplet_query("DELETE FROM srv_grid WHERE spr_id='$this->spremenljivka' AND part = '2'");			
			}
		}
		
		if (isset($_POST['edit_grid_variable_edit']) && $_POST['edit_grid_variable_edit'] == 1) {
			
			$s = sisplet_query("SELECT id FROM srv_grid WHERE spr_id = '$this->spremenljivka'");
			while ($r = mysqli_fetch_array($s)) {
				
				if (isset($_POST['edit_grid_variable_'.$r['id']]))
					sisplet_query("UPDATE srv_grid SET variable='{$_POST['edit_grid_variable_'.$r['id']]}' WHERE spr_id='$this->spremenljivka' AND id='$r[id]'");
			
			}
		}
		
		// urejanje vrednosti
		if (isset($_POST['edit_vrednost'])) {

			$i = 1;
			
			foreach ($_POST as $key => $v) {
				//shranimo drugo polje ce imamo diferencial
				if (substr($key, 0, 17) == 'vrednost_naslov2_') {
					$vrednost = substr($key, 17);
					
					$s = sisplet_query("UPDATE srv_vrednost SET naslov2='".$_POST['vrednost_naslov2_'.$vrednost]."' WHERE id = '$vrednost'");

					if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				}
				
				if (substr($key, 0, 16) == 'vrednost_naslov_') {
					$vrednost = substr($key, 16);
					
					$s = sisplet_query("UPDATE srv_vrednost SET naslov='".$_POST['vrednost_naslov_'.$vrednost]."', vrstni_red='$i' WHERE id = '$vrednost'");
					if (!$s) echo mysqli_error($GLOBALS['connect_db']);
					
					$i++;
				}
			}
			
			//sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$this->spremenljivka' AND naslov=''");
			Common::prestevilci($this->spremenljivka);
		}
		
		// odstranimo default vrednosti - ( v primeru da vsaj 1 ni vec default )
		if (true) {
			
			$_default = true;
			$s = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='$this->spremenljivka'");
			while ($r = mysqli_fetch_array($s)) {
				if ( strpos($r['naslov'], $lang['srv_new_vrednost']) === false ) $_default = false;
			}
			
			if ($_default == false) {
				mysqli_data_seek($s, 0);
				while ($r = mysqli_fetch_array($s)) {
					if ( strpos($r['naslov'], $lang['srv_new_vrednost']) !== false ) {
						sisplet_query("DELETE FROM srv_vrednost WHERE id = '$r[id]'");
					}
				}
			}
			
			Common::prestevilci($this->spremenljivka);
		}
		
		// shrani stevilo gridov in jih doda/pobrise iz srv_grid
		if (isset($_POST['grids_count'])) {
		
			// Shranimo stare naslove missing gridov da jih ne povozimo kasneje
			$sqlM = sisplet_query("SELECT id, naslov, other FROM srv_grid WHERE spr_id='$this->spremenljivka' AND other!='0'");
			$missing_old_vals = array();
			while($rowM = mysqli_fetch_array($sqlM)){
                $missing_old_vals[$rowM['other']]['naslov'] = $rowM['naslov'];
                
                // Shranimo se preveden naslov ce ga imamo
                $sqlL = sisplet_query("SELECT lang_id, naslov, podnaslov FROM srv_language_grid WHERE spr_id='$this->spremenljivka' AND grd_id='".$rowM['id']."'");
                while($rowL = mysqli_fetch_array($sqlL)){
                    $missing_old_vals[$rowM['other']]['translations'][] = $rowL;
                }
            }
			
			//na zacetku pobrisemo missing gride in podvojene gride (dvojna tabela) - ker jih na koncu dodamo
			sisplet_query("DELETE FROM srv_grid WHERE spr_id='$this->spremenljivka' AND (other != '0' OR part != '1')");
			
			// Pri multigrid tipih max diff in one against another popravimo stevilo gridov na 2
			if($row['tip'] == 6 && ($_POST['enota'] == 4 || $_POST['enota'] == 5 || $_POST['enota'] == 8)){
				$count = 2;
			}
			elseif ($row['tip'] == 20 && $row['ranking_k'] == 1){
				$count = 1;
			}
			else{
				$count = $_POST['grids_count'];				
			}
			
			$countAll = (isset($_POST['missing_value_checkbox']) && is_array($_POST['missing_value_checkbox'])) ? $count + count($_POST['missing_value_checkbox']) : $count;

			//pobrisemo gride ki so prevec
			sisplet_query("DELETE FROM srv_grid WHERE spr_id='$this->spremenljivka' AND vrstni_red > '$count' AND other = '0' AND part = '1'");
			
			for($i=1; $i<=$count; $i++){
				
				$id = $i;
				$vrstni_red = $i;
				$variable  = $i;
				
				//dodamo grid
				if($row['grids'] < $i){								

					$s = sisplet_query("INSERT INTO srv_grid (id, spr_id, naslov, vrstni_red, variable, other, part) VALUES ('$id', '$this->spremenljivka', '$lang[srv_new_grid]', '$vrstni_red', '$variable', '$other', '1') ON DUPLICATE KEY UPDATE naslov=VALUES(naslov), vrstni_red=VALUES(vrstni_red), other=VALUES(other), part=2");
					
					if ($diferencial_trak == 1){	//ce je trak, se morajo vrednosti ustrezno posodobiti,saj so se pred tem v bazi spremenile
						$this->ajax_diferencial_trak_skrite_vrednosti($this->spremenljivka, $count, $_POST['diferencial_trak_starting_num']);	
					}
				}
				
				//dodamo podvojen grid pri dvojni tabeli
				if($_POST['enota'] == 3){
				
					$sqlN = sisplet_query("SELECT naslov FROM srv_grid WHERE spr_id='$this->spremenljivka' AND id = '$id'");
					$rowN = mysqli_fetch_array($sqlN);
					$naslov2 = $rowN['naslov'];
					
					$vrstni_red2 = $vrstni_red + $countAll;
					$id = $vrstni_red2;

					$s = sisplet_query("INSERT INTO srv_grid (id, spr_id, naslov, vrstni_red, variable, other, part) VALUES ('$id', '$this->spremenljivka', '$naslov2', '$vrstni_red2', '$variable', '$other', 2)");
				}
			}
			
			# napolnimo/pobrisemo manjkajoče vrednosti (ne vem, zavrnil ...
			if (isset($_POST['missing_value_checkbox']) && is_array($_POST['missing_value_checkbox'])) {

				$vrstni_red = $count;
				
				foreach($_POST['missing_value_checkbox'] as $key => $missing){
										
					# popravimi za missing vrednosi
					# katere missinge imamo na voljo
					$smv = new SurveyMissingValues($this->anketa);

					$missing_values = $smv->GetUnsetValuesForSurvey();
					$naslov = addslashes($missing_values[$missing]);
					$other = $missing;

					// Popravimo naslov missing gridov ce samo zapremo edit (da ga ne povozimo)
					if(isset($missing_old_vals[$other]))
						$naslov = $missing_old_vals[$other]['naslov'];
					
					$vrstni_red++;
					$id =  $vrstni_red;				
					$variable = $id;
	
					$s = sisplet_query("INSERT INTO srv_grid (id, spr_id, naslov, vrstni_red, variable, other, part) VALUES ('$id', '$this->spremenljivka', '$naslov', '$vrstni_red', '$variable', '$other', '1') ON DUPLICATE KEY UPDATE naslov=VALUES(naslov), vrstni_red=VALUES(vrstni_red), other=VALUES(other), part=2");			
                    
                    // Insertamo se prevedene naslove missing gridov ce obstajajo
                    if(isset($missing_old_vals[$other]['translations'])){

                        foreach($missing_old_vals[$other]['translations'] as $translation){

                            $sl = sisplet_query("INSERT INTO srv_language_grid 
                                                (ank_id, spr_id, grd_id, lang_id, naslov, podnaslov) 
                                                VALUES 
                                                ('".$this->anketa."', '".$this->spremenljivka."', '".$id."', '".$translation['lang_id']."', '".$translation['naslov']."', '".$translation['podnaslov']."')");
                        }
                    }


					//dodamo podvojen grid pri dvojni tabeli
					if($_POST['enota'] == 3){
						
						$vrstni_red2 = $vrstni_red + $countAll;
						$id = $vrstni_red2;

						$s = sisplet_query("INSERT INTO srv_grid (id, spr_id, naslov, vrstni_red, variable, other, part) VALUES ('$id', '$this->spremenljivka', '$naslov', '$vrstni_red2', '$variable', '$other', 2)");
					}
				}			
			}
			
			$update .= ", grids = '$count' ";
		}
		
		// napolni default vrednosti v gride
		if(isset($_POST['grid_defaults'])){
			
			$grid_defaults = $_POST['grid_defaults'];
			
			$grids_count = 5;
			
			if($row['tip'] == 6 && ($_POST['enota'] == 4 || $_POST['enota'] == 5 || $_POST['enota'] == 8)){//dodal pogoj, ker drugace ne kaze pravilno label
				$grids_count = 2;
			}
			else{
				$grids_count = $_POST['grids_count'];				
			}
			
			// Ce imamo nastavljene dolocene default vrednosti gridov jih napolnimo
			if($grid_defaults > 0){
				
				// Napolnimo prave vrednosti
				$values = $default_grid_values[$grid_defaults];
				
				$indexArray = array(
					2 => array(2, 6),
					3 => array(2, 4, 6),
					4 => array(1, 2, 6, 7),
					5 => array(1, 2, 4, 6, 7),
					6 => array(1, 2, 3, 5, 6, 7),
					7 => array(1, 2, 3, 4, 5, 6, 7)
				);
			
				for($i=1; $i<=$grids_count; $i++){		
					
					// Ce imamo samo en grid
					if($grids_count == 1 && $i == 1){
						$index = 2;
						$naslov = $values[2];
					}
					// Ce imamo vec kot 7 gridov - prvih 7 zapisemo normalno, ostali so prazni
					else if($grids_count > 7){
						if($i <= 7){
							$index = $indexArray[7][$i-1];
							$naslov = $values[$index];
						}
						else
							break;
					}					
					// Ce imamo 2 - 7 gridov jih izpisemo v skladu z $indexArray
					else{
						$index = $indexArray[$grids_count][$i-1];
						$naslov = $values[$index];
					}
				
					//$lang['srv_grid_defaults_'.$grid_defaults.'_'.$i];
					$s = sisplet_query("UPDATE srv_grid SET naslov='".$naslov."' WHERE spr_id='$this->spremenljivka' AND vrstni_red = '$i'");	
				}
			}
		}
		
		// napolni default vrednosti v radio tip
		if(isset($_POST['radio_defaults'])){
		
			$radio_defaults = $_POST['radio_defaults'];
			
			// Preberemo stevilo vrednosti
			$sqlVCnt = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$this->spremenljivka' AND other='0' ORDER BY vrstni_red ASC");
			$vrednost_count = (mysqli_num_rows($sqlVCnt) > 0) ? mysqli_num_rows($sqlVCnt) : 5;
			
			// Ce imamo nastavljene dolocene default vrednosti gridov jih napolnimo
			if($radio_defaults > 0){
				
				// Napolnimo prave vrednosti
				$values = $default_grid_values[$radio_defaults];
				
				$indexArray = array(
					2 => array(2, 6),
					3 => array(2, 4, 6),
					4 => array(1, 2, 6, 7),
					5 => array(1, 2, 4, 6, 7),
					6 => array(1, 2, 3, 5, 6, 7),
					7 => array(1, 2, 3, 4, 5, 6, 7)
				);
			
				$sqlV = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$this->spremenljivka' AND other='0' ORDER BY vrstni_red ASC");
				$i=1;
				if(mysqli_num_rows($sqlV) > 0){
					while($rowV = mysqli_fetch_array($sqlV)){		
						
						// Ce imamo samo eno vrednost
						if($vrednost_count == 1 && $i == 1){
							$index = 2;
							$naslov = $values[2];
						}
						// Ce imamo vec kot 7 vrednosti - prvih 7 zapisemo normalno, ostali so prazni
						else if($vrednost_count > 7){
							if($i <= 7){
								$index = $indexArray[7][$i-1];
								$naslov = $values[$index];
							}
							else
								break;
						}					
						// Ce imamo 2 - 7 gridov jih izpisemo v skladu z $indexArray
						else{
							$index = $indexArray[$vrednost_count][$i-1];
							$naslov = $values[$index];
						}
						
						$s = sisplet_query("UPDATE srv_vrednost SET naslov='".$naslov."' WHERE spr_id='$this->spremenljivka' AND id='".$rowV['id']."'");					
						$i++;
					}
				}
			}
		}
	
		
		// urejanje vrednosti pri besedilu*
		if (isset($_POST['edit_vrednost_besedilo'])) {

			# pogledamo koliko kosov rabimo
			$kosov = $_POST['text_kosov'];

			$j = 0;
			for ($j = 1; $j <= $kosov; $j++) {
				$vrstni_red = $j;
				//$naslov = $_POST['vrednost_naslov_'.$vrstni_red];
				$naslov = $lang['srv_new_text'];
				$size = $_POST['vrednost_size_'.$vrstni_red];
				
				//$s = sisplet_query("UPDATE srv_vrednost SET naslov='".$_POST['vrednost_naslov_'.$j]."', size='".$_POST['vrednost_size_'.$j]."'  WHERE vrstni_red='$j' AND other = '0' AND spr_id='$this->spremenljivka'");
				$s = sisplet_query("UPDATE srv_vrednost SET size='".$_POST['vrednost_size_'.$j]."'  WHERE vrstni_red='$j' AND other = '0' AND spr_id='$this->spremenljivka'");

				$s = sisplet_query("SELECT id FROM srv_vrednost WHERE vrstni_red='$vrstni_red' AND spr_id='$this->spremenljivka' AND other = 0");
				if(mysqli_num_rows($s) == 0){
					$i = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, size, vrstni_red) VALUES ('', '$this->spremenljivka', '$naslov', '$size', '$vrstni_red')");
				}				

				unset($_POST['vrednost_naslov_'.$j]);
				unset($_POST['vrednost_size_'.$j]);
			}
			
			# popravimo polja drugo, nevem...
			foreach ($_POST as $key => $v) {
				if (substr($key, 0, 16) == 'vrednost_naslov_') {
					$vrednost = substr($key, 16);
					//$naslov = $_POST['vrednost_naslov_'.$vrednost]; // naslov spreminjamo zdaj v inlineu
					//$s = sisplet_query("UPDATE srv_vrednost SET naslov='$naslov', vrstni_red='$j' WHERE id='$vrednost' AND spr_id='$this->spremenljivka'"); 
					$s = sisplet_query("UPDATE srv_vrednost SET vrstni_red='$j' WHERE id='$vrednost' AND spr_id='$this->spremenljivka'"); 

					$j++;
				}
			}
			Common::prestevilci($this->spremenljivka);
		}
		
		// urejanje vrednosti pri number
		if (isset($_POST['edit_vrednost_number'])) {
			
			$kosov = (int)$_POST['size'];
			$j = 0;
			
			for ($j = 1; $j <= $kosov; $j++) {
				$vrstni_red = $j;
				//$naslov = $_POST['vrednost_naslov_'.$vrstni_red];
				$naslov = $lang['srv_new_text'];
				$size = $_POST['vrednost_size_'.$vrstni_red];
				
				//$s = sisplet_query("UPDATE srv_vrednost SET naslov='".$_POST['vrednost_naslov_'.$j]."', size='".$_POST['vrednost_size_'.$j]."'  WHERE vrstni_red='$j' AND other = '0' AND spr_id='$this->spremenljivka'");
				$s = sisplet_query("UPDATE srv_vrednost SET size='".$_POST['vrednost_size_'.$j]."'  WHERE vrstni_red='$j' AND other = '0' AND spr_id='$this->spremenljivka'");

				$s = sisplet_query("SELECT id FROM srv_vrednost WHERE vrstni_red='$vrstni_red' AND spr_id='$this->spremenljivka' AND other = 0");
				if(mysqli_num_rows($s) == 0){
					$i = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, size, vrstni_red) VALUES ('', '$this->spremenljivka', '$naslov', '$size', '$vrstni_red')");
				}				

				unset($_POST['vrednost_naslov_'.$j]);
				unset($_POST['vrednost_size_'.$j]);
			}
			# pobrišemo morebitne odvečne variable
			$s1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id='$this->spremenljivka' AND other = '0'");
			if (mysqli_num_rows($s1) > $kosov) {
				sisplet_query("DELETE FROM srv_vrednost WHERE vrstni_red > '$kosov' AND spr_id='$this->spremenljivka' AND other = 0");
			}
			
			# popravimo polja drugo, nevem...
			foreach ($_POST as $key => $v) {
				if (substr($key, 0, 16) == 'vrednost_naslov_') {
					$vrednost = substr($key, 16);
					//$naslov = $_POST['vrednost_naslov_'.$vrednost];
					//$s = sisplet_query("UPDATE srv_vrednost SET naslov='$naslov', vrstni_red='$j' WHERE id='$vrednost' AND spr_id='$this->spremenljivka'"); 
					$s = sisplet_query("UPDATE srv_vrednost SET vrstni_red='$j' WHERE id='$vrednost' AND spr_id='$this->spremenljivka'"); 
					$j++;
				}
			}

			Common::prestevilci($this->spremenljivka);
		}
				
		// shrani naslov
		if (isset($_POST['naslov'])) {
			$naslov = $_POST['naslov'];
			
			// firefox na koncu vsakega contenteditable doda <br>, ki ga tukaj odstranimo
			if (substr($naslov, -4) == '<br>') {
				$naslov = substr($naslov, 0, -4);
			}
			if (substr($naslov, -8) == '<br></p>') {	// ce je na koncu <br></p>
				$naslov = substr($naslov, 0, -8).'</p>';
			}
			
			// ce nimamo paragrafov jih dodamo
			if (strtolower(substr($naslov, 0, 3)) != '<p>' && strtolower(substr($naslov, -4)) != '</p>' && strrpos($naslov, '<p>') === false) {
				$naslov = '<p>' . str_replace("\n", "</p>\n<p>", $naslov) . '</p>';
			}
			
			$purifier = New Purifier();
    		$naslov = $purifier->purify_DB($naslov);
			
			if ($lang_id == 0) {
				$update .= ", naslov = '$naslov' ";
            } 
            else {
				sisplet_query("INSERT INTO srv_language_spremenljivka (ank_id, spr_id, lang_id, naslov) VALUES ('$this->anketa', '$this->spremenljivka', '$lang_id', '$naslov') ON DUPLICATE KEY UPDATE naslov='$naslov'");
			}
		}
		
		if (isset($_POST['variable'])) {
			
			if ( in_array($row['variable'], array('email','telefon','ime','priimek','naziv','drugo')) && $row['sistem']==1 ) {
				
				// tukaj ne pustimo spremeniti
				
			} else {
				
				// preverimo, da ni se kje drugje v anekti tako ime spremenljivke
				$sqlv = sisplet_query("SELECT s.variable FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa'");
				$ok = true;
				while ($rowv = mysqli_fetch_array($sqlv)) {
					if ($rowv['variable'] == $_POST['variable']) $ok = false;
				}
				
				if ($_POST['variable'] != $row['variable'] && $_POST['variable'] != ''){
					// Ce imamo unikatno ime shranimo
					if($ok){
						$update .= ", variable='$_POST[variable]', variable_custom='1' ";
					}
					// Ce se ime ze pojavi v anketi mu dodamo stevilko
					else{
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
						
						$update .= ", variable='$variable', variable_custom='1' ";
					}
				}
					
			}
		}
		
		if (isset($_POST['label'])) {
			if ($_POST['label'] != $row['label'])
					$update .= ", label='$_POST[label]' ";
		}
		
		// // shrani opombo
		if (isset($_POST['info'])) {
			$update .= ", info = '$_POST[info]' ";
		}
		
		
		// shrani uvod
		if (isset($_POST['introduction'])) {
			
			$naslov = $_POST['introduction'];			
			if (strtolower(substr($naslov, 0, 3)) != '<p>' && strtolower(substr($naslov, -4)) != '</p>' && strrpos($naslov, '<p>') === false) {
				$naslov = '<p>' . str_replace("\n", "</p>\n<p>", $naslov) . '</p>';
			}
			
			$purifier = New Purifier();
    		$naslov = $purifier->purify_DB($naslov);
			
			if ($lang_id == 0) {
				$s = sisplet_query("UPDATE srv_anketa SET introduction='".$naslov."' WHERE id = '$this->anketa'");
            } 
            else {
				if ($naslov!='')
					sisplet_query("REPLACE INTO srv_language_spremenljivka (ank_id, spr_id, lang_id, naslov) VALUES ('$this->anketa', '-1', '$lang_id', '$naslov')");
				else
					sisplet_query("DELETE FROM srv_language_spremenljivka WHERE ank_id='$this->anketa' AND spr_id='-1' AND lang_id='$lang_id'");
			}
		}
		
		// nastavitve uvoda
		if ( isset($_POST['intro_opomba'])) {
			if (isset($_POST['intro_opomba'])) {
				$intro_opomba = $_POST['intro_opomba'];
				$intro_note = $_POST['note'];
				$intro = " intro_opomba='".$intro_opomba."', intro_note='$intro_note'";
			} else $intro = '';
			
			unset($_POST['note']); // da ne gre shranjevat v srv_spremenljivka
			
			$s = sisplet_query("UPDATE srv_anketa SET $intro WHERE id = '$this->anketa'");
			//$s = sisplet_query("UPDATE srv_anketa SET $intro WHERE id = '$this->anketa'");
		}
		
		// shrani dodatno ime za gumb za naprej
		if(isset($_POST['srv_nextpage_uvod'])) {
			SurveySetting::getInstance()->Init($this->anketa);
			SurveySetting::getInstance()->setSurveyMiscSetting('srvlang_srv_nextpage_uvod', $_POST['srv_nextpage_uvod']);
		}
		
		// shrani napredne nastavitve za uvod
		if (isset($_POST['show_intro'])) {
			$s = sisplet_query("UPDATE srv_anketa SET show_intro='".$_POST['show_intro']."' WHERE id = '$this->anketa'");
		}
		
		// shrani napredne nastavitve za uvod
		if (isset($_POST['show_intro'])) {
			$s = sisplet_query("UPDATE srv_anketa SET intro_static='".$_POST['intro_static']."' WHERE id = '$this->anketa'");
		}
		
		// shrani zakljucek
		if (isset($_POST['conclusion'])) {
			
			$naslov = $_POST['conclusion'];
			if (strtolower(substr($naslov, 0, 3)) != '<p>' && strtolower(substr($naslov, -4)) != '</p>' && strrpos($naslov, '<p>') === false) {
				//$naslov = '<p>'.nl2br($naslov).'</p>';
				$naslov = '<p>' . str_replace("\n", "</p>\n<p>", $naslov) . '</p>';
			}
			
			$purifier = New Purifier();
    		$naslov = $purifier->purify_DB($naslov);
    	
    		if ($lang_id == 0) {
				$s = sisplet_query("UPDATE srv_anketa SET conclusion='".$naslov."' WHERE id = '$this->anketa'");	
			} 
			else {
				if ($naslov!='')
					sisplet_query("REPLACE INTO srv_language_spremenljivka (ank_id, spr_id, lang_id, naslov) VALUES ('$this->anketa', '-2', '$lang_id', '$naslov')");
				else
					sisplet_query("DELETE FROM srv_language_spremenljivka WHERE ank_id='$this->anketa' AND spr_id='-2' AND lang_id='$lang_id'");
			}
		}
		
		// nastavitve zakljucka
		if (isset($_POST['concl_opomba'])) {
			if (isset($_POST['concl_opomba'])) {
				
				// Shranjevanje kaj se zgodi po koncu ankete (skok na url...)
				$concl_link = $_POST['concl_link'];
				if($concl_link == 1)
					$concl_link = 0;
				elseif($concl_link == 0)
					$concl_link =1;
				
				$url = $_POST['url'];
				
				SurveySetting::getInstance()->Init($this->anketa);
				SurveySetting::getInstance()->setSurveyMiscSetting('concl_url_usr_id', $_POST['concl_url_usr_id']);
				SurveySetting::getInstance()->setSurveyMiscSetting('concl_url_status', $_POST['concl_url_status']);			
				SurveySetting::getInstance()->setSurveyMiscSetting('concl_url_recnum', $_POST['concl_url_recnum']);			
				
				$concl_opomba = $_POST['concl_opomba'];	
				$concl_note = $_POST['note'];	
				
				$concl_back_button = $_POST['concl_back_button'];
				$concl_end_button = $_POST['concl_end_button'];
				
				// shrani prikaz povezave na zacetek ankete za naknadno urejanje
				$concl_return_edit = $_POST['concl_return_edit'];
				
				// shrani prikaz povezave na PDF link na koncu
				$concl_PDF_link = $_POST['concl_PDF_link'];
				
				$concl = "concl_opomba='".$concl_opomba."', 
					url='".$url."', 
					concl_back_button='".$concl_back_button."', 
					concl_link='".$concl_link."', 
					concl_end_button='".$concl_end_button."', 
					concl_note='$concl_note', 
					concl_PDF_link='$concl_PDF_link', 
					concl_return_edit='$concl_return_edit'";
			} 
			else 
				$concl = '';
		
			unset($_POST['note']); // da ne gre shranjevat v srv_spremenljivka
			
			$s = sisplet_query("UPDATE srv_anketa SET $concl WHERE id = '$this->anketa'");
			//$s = sisplet_query("UPDATE srv_anketa SET $concl WHERE id = '$this->anketa'");
		}
		
		// shrani napredne nastavitve za zakljucek
		if (isset($_POST['show_concl'])) {
			$s = sisplet_query("UPDATE srv_anketa SET show_concl='".$_POST['show_concl']."' WHERE id = '$this->anketa'");
		}
		
		// shrani dodatno ime za gumb zakljucek
		if(isset($_POST['srv_konec'])){
			SurveySetting::getInstance()->Init($this->anketa);
			SurveySetting::getInstance()->setSurveyMiscSetting('srvlang_srv_konec', $_POST['srv_konec']);
		}
		
		// shrani dodatno ime za gumb prejsnja stran
		if(isset($_POST['srv_prevpage'])){
			SurveySetting::getInstance()->Init($this->anketa);
			SurveySetting::getInstance()->setSurveyMiscSetting('srvlang_srv_prevpage', $_POST['srv_prevpage']);
		}
			
		// shrani text zakljucka po deaktivaciji
		if(isset($_POST['srvlang_srv_survey_non_active'])){
			SurveySetting::getInstance()->Init($this->anketa);
			SurveySetting::getInstance()->setSurveyMiscSetting('srvlang_srv_survey_non_active', $_POST['srvlang_srv_survey_non_active']);
		}
		
		// shrani statistiko
		if (isset($_POST['statistics'])) {
			$naslov = $_POST['statistics'];			
			if (strtolower(substr($naslov, 0, 3)) != '<p>' && strtolower(substr($naslov, -4)) != '</p>' && strrpos($naslov, '<p>') === false) {
				//$naslov = '<p>'.nl2br($naslov).'</p>';
				$naslov = '<p>' . str_replace("\n", "</p>\n<p>", $naslov) . '</p>';
			}			
			
			$purifier = New Purifier();
    		$naslov = $purifier->purify_DB($naslov);
    	
			$s = sisplet_query("UPDATE srv_anketa SET statistics='".$naslov."' WHERE id = '$this->anketa'");
		}
		
		// shrani dodaten naslov spremenljivke za graf
		if (isset($_POST['naslov_graf'])) {
			$naslov = $_POST['naslov_graf'];
			if (strtolower(substr($naslov, 0, 3)) != '<p>' && strtolower(substr($naslov, -4)) != '</p>' && strrpos($naslov, '<p>') === false) {
				//$naslov = '<p>'.nl2br($naslov).'</p>';
				$naslov = '<p>' . str_replace("\n", "</p>\n<p>", $naslov) . '</p>';
			}
			
			$purifier = New Purifier();
    		$naslov = $purifier->purify_DB($naslov);
    		
			$update .= ", naslov_graf = '$naslov' ";
		}
		
		// shrani dodatne naslove variabel za graf
		if (isset($_POST['edit_vrednost_graf'])) {
			$i = 1;
			
			foreach ($_POST as $key => $v) {
				
				if (substr($key, 0, 14) == 'vrednost_graf_') {
					$vrednost = substr($key, 14);
					
					$s = sisplet_query("UPDATE srv_vrednost SET naslov_graf='".$_POST['vrednost_graf_'.$vrednost]."' WHERE id = '$vrednost'");

					if (!$s) echo mysqli_error($GLOBALS['connect_db']);
					$i++;
				}
			}
		}
		
		// shrani dodatne naslove gridov za graf
		if (isset($_POST['edit_grid_graf'])) {
			
			$vrstni_red = 0;
			$i = 0;
			foreach ($_POST as $key => $v) {
				if (substr($key, 0, 10) == 'grid_graf_') {
					$vrstni_red++;
					
					$grid = substr($key, 10);
					$naslov = $_POST['grid_graf_'.$grid];
					$variable = $grid;
					$id= $vrstni_red;
					
					$other = '0';
					# manjkoajoče vrednosti (ne vem, zavrnil ...
					if (isset($_POST['missing_value_checkbox']) && is_array($_POST['missing_value_checkbox'])) {
						if (in_array($grid, $_POST['missing_value_checkbox'])) {
							# grid je manjkajoča vrednost
							$other = $grid.'';
							$id =  $grid;
						} else {
							# grid je normalna vrednost
							$i++;
						}
					} else {
						# grid je normalna vrednost
						$i++;
					}
					$s = sisplet_query("UPDATE srv_grid SET naslov_graf='$naslov' WHERE id='$id' AND spr_id='$this->spremenljivka'");
					if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				}
			}
		}
		
		// shrani nastavitev za sistemsko/navadno spr.
		if (isset($_POST['sistem'])) {
			
			if ( in_array($row['variable'], array('email','telefon','ime','priimek','naziv','drugo')) && $row['sistem']==1 ) {
				
				// tukaj ne pustimo spremeniti)		
			} else {
				$update .= ", sistem = '$_POST[sistem]' ";
			}
		}
		
		// shrani nastavitev za sistemsko/navadno spr.
		if (isset($_POST['reverse_var'])) {
	
			$newParams = new enkaParameters($row['params']);
	
			// Ce je bila vrednost spremenjena
			if($newParams->get('reverse_var') != $_POST['reverse_var']){
				
				// Popravimo nastavitev
				$newParams->set('reverse_var', $_POST['reverse_var']);
				$s = sisplet_query("UPDATE srv_spremenljivka SET params='".$newParams->getString()."' WHERE id='$this->spremenljivka'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				
				// Na novo prestevilcimo variable
				Cache::clear_cache();
				Common::prestevilci($this->spremenljivka);
			}
		}
		
		// shrani nastavitev za vidno/skrito spr.
		if (isset($_POST['visible'])) {
			$update .= ", visible = '$_POST[visible]' ";
		}
		
		// shrani nastavitev za odklenjeno/zaklenjeno spr.
		if (isset($_POST['locked'])) {
			$update .= ", locked = '$_POST[locked]' ";
		}
		
		// shrani nastavitev kdo vidi spr.
		if (isset($_POST['dostop'])) {
			$update .= ", dostop = '$_POST[dostop]' ";
		}
		
		// shrani nastavitev za reminder
		if (isset($_POST['reminder'])) {
			$update .= ", reminder = '$_POST[reminder]' ";
		}
		
		// shrani nastavitev za prikaz "neustrezno" ob opozorilu
		if (isset($_POST['alert_show_97'])) {
			$update .= ", alert_show_97 = '$_POST[alert_show_97]' ";
		}
		// shrani nastavitev za prikaz "zavrnil" ob opozorilu
		if (isset($_POST['alert_show_98'])) {
			$update .= ", alert_show_98 = '$_POST[alert_show_98]' ";
		}
		// shrani nastavitev za prikaz "ne vem" ob opozorilu
		if (isset($_POST['alert_show_99'])) {
			$update .= ", alert_show_99 = '$_POST[alert_show_99]' ";
		}
		
		// shrani nastavitev za razvrstitev vrednosti spr
		if (isset($_POST['random'])) {
			$random = $_POST['random'];		
			$other = ($random) ? " AND other='0'" : "";

			$sql = sisplet_query("UPDATE srv_spremenljivka SET random = '$random' WHERE id='$this->spremenljivka'");
			/* kadar spremenimo, povozimo vse vrednosti (ker sicer se nastavlja še vsako opcijo posebej) */
			if ($row['random'] != $random)
				$sql1 = sisplet_query("UPDATE srv_vrednost SET random = '$random' WHERE spr_id ='$this->spremenljivka' $other");
		}
		
		// shrani nastavitev za skalo
		if (isset($_POST['skala'])) {
			$update .= ", skala = '$_POST[skala]' ";
		}
		
		// shrani nastavitev za stevilo - st. celih mest
		if (isset($_POST['cela'])) {
			$update .= ", cela = '$_POST[cela]' ";
		}
		
		// shrani nastavitev za stevilo - st. decimalnih mest
		if (isset($_POST['decimalna'])) {
			$update .= ", decimalna = '$_POST[decimalna]' ";
		}
		
		// shrani dinamicen multigrid
		if (isset($_POST['dynamic_mg'])) {
			$update .= ", dynamic_mg = '$_POST[dynamic_mg]' ";
		}
		
		// shrani nastavitve za omejitve vsote
		if (isset($_POST['edit_vsota_limit'])) {
			
			if($_POST['vsota_limittype'] == 1){
				$vsota_limittype = 1;
				$vsota_min = $_POST['vsota_min'];
				$vsota_limit = $_POST['vsota_limit'];
			}
			else{
				$vsota_limittype = 0;
				$vsota_min = $_POST['vsota_exact'];
				$vsota_limit = $_POST['vsota_exact'];
			}
		
			$update .= ", vsota_reminder = '$_POST[vsota_reminder]', vsota_min = '$vsota_min', vsota_limit = '$vsota_limit', vsota_limittype = '$vsota_limittype', vsota_show='$_POST[vsota_show]' ";
		}
				
		// shrani nastavitve za omejitve stevila
		if (isset($_POST['edit_number_limit'])) {		
			$update .= ", vsota_reminder = '$_POST[vsota_reminder]', num_useMin = '$_POST[num_useMin]', num_useMax = '$_POST[num_useMax]', vsota_min = '$_POST[vsota_min]', vsota_limit = '$_POST[vsota_limit]', vsota_show='$_POST[vsota_show]', num_useMin2 = '$_POST[num_useMin2]', num_useMax2 = '$_POST[num_useMax2]', num_min2 = '$_POST[num_min2]', num_max2 = '$_POST[num_max2]'";
		}
		
		// shrani nastavitev za razvrscanje - tip
		if (isset($_POST['design'])) {
			$update .= ", design = '$_POST[design]' ";
		}
		
		// shrani nastavitev za sn generator - tip
		if (isset($_POST['sn_design'])) {
			$update .= ", design = '$_POST[sn_design]' ";
		}
		
		// shrani nastavitev za razvrscanje - st. moznosti
		if (isset($_POST['ranking_k'])) {
			$update .= ", ranking_k = '$_POST[ranking_k]' ";
		}
		
		// shrani nastavitev za besedilo* - st. kosov
		if (isset($_POST['text_kosov'])) {
			
			$update .= ", text_kosov = '$_POST[text_kosov]' ";
			
			$sqlc = sisplet_query("SELECT COUNT(*) AS count FROM srv_vrednost WHERE spr_id='$this->spremenljivka' AND other = '0'");
			$rowc = mysqli_fetch_array($sqlc);
			if($rowc['count'] < $_POST['text_kosov']){
				$rowc['count']++;	// da pri novem ne zacnemo z 0, ker so potem napacne labele variabel
				$values = "";
				for ($i = $rowc['count']; $i <= $_POST['text_kosov']; $i++) {
					if ($values != "") $values .= ",";
					$values .= " ('$this->spremenljivka', '$i', '$i') ";
				}
				$sql1 = sisplet_query("INSERT INTO srv_vrednost (spr_id, variable, vrstni_red) VALUES $values");
			}		
			//pobrisemo odvecne vrednosti
			else {
				sisplet_query("DELETE FROM srv_vrednost WHERE vrstni_red > '$_POST[text_kosov]' AND spr_id='$this->spremenljivka' AND other = 0");
			}
			Common::prestevilci($this->spremenljivka);
		}
		
		// shrani nastavitev za besedilo* - polozaj besedila
		if (isset($_POST['text_orientation'])) {
			$update .= ", text_orientation = '$_POST[text_orientation]' ";
		}
		
		// shrani nastavitev za number - polozaj enote
		if (isset($_POST['enota'])) {
			$update .= ", enota = '$_POST[enota]' ";
		}
		
		// shrani nastavitev za number - stevilo polj
		if (isset($_POST['size'])) {
			$update .= ", size = '$_POST[size]' ";
		}
		
		// shrani nastavitev za prikaz checkboxa
		if (isset($_POST['checkboxhide'])) {
			$update .= ", checkboxhide = '$_POST[checkboxhide]' ";
		}
		
		// shrani nastavitev za prikaz statistike
		if (isset($_POST['stat'])) {
			$update .= ", stat = '$_POST[stat]' ";
		}	
		
		// shrani nastavitev za timer
		if (isset($_POST['timer'])) {
			if($_POST['timer'] == 0)
				$update .= ", timer = '$_POST[timer]' ";
			elseif(isset($_POST['timer2']))
				$update .= ", timer = '$_POST[timer2]' ";
		}
		
		// shrani nastavitev za editiranje label grafov
		if (isset($_POST['edit_graf'])) {
			$update .= ", edit_graf = '$_POST[edit_graf]' ";
		}
		// shrani nastavitev za siroke labele pri grafih
		if (isset($_POST['wide_graf'])) {
			$update .= ", wide_graf = '$_POST[wide_graf]' ";
		}
		
		// shrani nastavitev za antonuccijev krog pri generatorju imen
		if (isset($_POST['antonucci'])) {
			$update .= ", antonucci = '$_POST[antonucci]' ";
		}

		if ( isset($_POST['taWidth']) 
			|| isset($_POST['gridWidth'])		
			|| isset($_POST['gridAlign']) 
			|| isset($_POST['taSize']) 
			|| isset($_POST['taHeight']) 
			|| isset($_POST['gridmultiple_width']) 
			|| isset($_POST['stolpci']) 
			|| isset($_POST['checkbox_limit'])
			|| isset($_POST['checkbox_min_limit']) 
			|| isset($_POST['checkbox_min_limit_reminder']) 
			|| isset($_POST['reverse_var']) 
			|| isset($_POST['grid_var']) 
			|| isset($_POST['revers_var']) 
			|| isset($_POST['captcha'])
			|| isset($_POST['emailVerify']) 
			|| isset($_POST['NG_addText']) 
			|| isset($_POST['NG_cancelButton']) 
			|| isset($_POST['NG_cancelText']) 
			|| isset($_POST['NG_countText'])
			|| isset($_POST['date_range_min']) 
			|| isset($_POST['date_range_max']) 
			|| isset($_POST['date_withTime']) 
			|| isset($_POST['max_markers'])
			|| isset($_POST['multi_input_type'])
			|| isset($_POST['naslov_podvprasanja_map'])
			|| isset($_POST['fokus_mape'])
			|| isset($_POST['user_location'])
			|| isset($_POST['dodaj_searchbox'])
			|| isset($_POST['marker_podvprasanje'])
			|| isset($_POST['customRadio'])			
			|| isset($_POST['otherWidth']) 
			|| isset($_POST['otherHeight']) 
			|| isset($_POST['nagovorLine']) 
			|| isset($_POST['sbSize']) 
			|| isset($_POST['prvaVrstica']) 
			|| isset($_POST['prvaVrstica_roleta'])
			|| isset($_POST['sbSizeVse']) 
			|| isset($_POST['prevAnswers']) 
			|| isset($_POST['disabled_vprasanje']) 
			|| isset($_POST['slider_handle']) 
			|| isset($_POST['slider_MinMaxNumLabel']) 
			|| isset($_POST['slider_MinMaxLabel']) 
			|| isset($_POST['slider_VmesneDescrLabel'])
			|| isset($_POST['slider_VmesneNumLabel']) 
			|| isset($_POST['slider_handle_step']) 
			|| isset($_POST['slider_MinMaxNumLabelNew']) 
			|| isset($_POST['slider_VmesneCrtice'])
			|| isset($_POST['slider_MinLabel']) 
			|| isset($_POST['slider_MaxLabel']) 
			|| isset($_POST['slider_MinNumLabel']) 
			|| isset($_POST['slider_MaxNumLabel']) 
			|| isset($_POST['slider_MinNumLabelTemp']) 
			|| isset($_POST['slider_MaxNumLabelTemp']) 
			|| isset($_POST['slider_window_number']) 
			|| isset($_POST['slider_NumofDescrLabels']) 
			|| isset($_POST['slider_DescriptiveLabel_defaults']) 
			|| isset($_POST['slider_DescriptiveLabel_defaults_naslov1']) 
			|| isset($_POST['slider_DescriptiveLabel_defaults_naslov2']) 
			|| isset($_POST['slider_DescriptiveLabel_defaults_naslov3']) 
			|| isset($_POST['slider_DescriptiveLabel_defaults_naslov4']) 
			|| isset($_POST['slider_DescriptiveLabel_defaults_naslov5']) 
			|| isset($_POST['slider_DescriptiveLabel_defaults_naslov6']) 
			|| isset($_POST['slider_DescriptiveLabel_defaults_naslov7']) 
			|| isset($_POST['slider_nakazi_odgovore']) 
			|| isset($_POST['slider_labele_podrocij']) 
			|| isset($_POST['slider_StevLabelPodrocij']) 
			|| isset($_POST['hotspot_image']) 
			|| isset($_POST['hotspot_region_visibility_option'])
			|| isset($_POST['hotspot_region_visibility'])
			|| isset($_POST['hotspot_tooltips_option']) 
			|| isset($_POST['diferencial_trak']) 
			|| isset($_POST['diferencial_trak_starting_num']) 
			|| isset($_POST['trak_num_of_titles'])
			|| isset($_POST['display_drag_and_drop_new_look'])
			|| isset($_POST['custom_column_label_option'])
			|| isset($_POST['grid_repeat_header'])
			|| isset($_POST['hotspot_region_color'])
			|| isset($_POST['hotspot_visibility_color'])
			|| isset($_POST['heatmap_click_color'])
			|| isset($_POST['heatmap_click_size'])
			|| isset($_POST['heatmap_click_shape'])
			|| isset($_POST['heatmap_num_clicks'])
			|| isset($_POST['heatmap_show_clicks']) 
			|| isset($_POST['heatmap_show_counter_clicks']) ){
			
			$newParams = new enkaParameters($row['params']);
		
			if (isset($_POST['taWidth']))
				$newParams->set('taWidth', $_POST['taWidth']);
			if (isset($_POST['taHeight']))
				$newParams->set('taHeight', $_POST['taHeight']);
			if (isset($_POST['gridmultiple_width']))
				$newParams->set('gridmultiple_width', $_POST['gridmultiple_width']);
			if (isset($_POST['taSize']))
				$newParams->set('taSize', $_POST['taSize']);
			if (isset($_POST['gridWidth']))
				$newParams->set('gridWidth', $_POST['gridWidth']);
            if (isset($_POST['gridAlign']))
				$newParams->set('gridAlign', $_POST['gridAlign']);
			if (isset($_POST['stolpci']))
				$newParams->set('stolpci', $_POST['stolpci']);
			if (isset($_POST['checkbox_limit']))
				$newParams->set('checkbox_limit', $_POST['checkbox_limit']);			
			if (isset($_POST['checkbox_min_limit']))
				$newParams->set('checkbox_min_limit', $_POST['checkbox_min_limit']);
			if (isset($_POST['checkbox_min_limit_reminder']))
				$newParams->set('checkbox_min_limit_reminder', $_POST['checkbox_min_limit_reminder']);
			if (isset($_POST['reverse_var']))
				$newParams->set('reverse_var', $_POST['reverse_var']);	
			if (isset($_POST['grid_var']))
				$newParams->set('grid_var', $_POST['grid_var']);	
			if (isset($_POST['captcha']))
				$newParams->set('captcha', $_POST['captcha']);
			if (isset($_POST['emailVerify']))
				$newParams->set('emailVerify', $_POST['emailVerify']);
			if (isset($_POST['NG_addText']))
				$newParams->set('NG_addText', $_POST['NG_addText']);
			if (isset($_POST['NG_cancelButton']))
				$newParams->set('NG_cancelButton', $_POST['NG_cancelButton']);
			if (isset($_POST['NG_cancelText']))
				$newParams->set('NG_cancelText', $_POST['NG_cancelText']);
			if (isset($_POST['NG_countText']))
				$newParams->set('NG_countText', $_POST['NG_countText']);
			if (isset($_POST['date_range_min']))
				$newParams->set('date_range_min', $_POST['date_range_min']);
			if (isset($_POST['date_range_max']))
				$newParams->set('date_range_max', $_POST['date_range_max']);
			if (isset($_POST['date_withTime']))
				$newParams->set('date_withTime', $_POST['date_withTime']);
			if (isset($_POST['otherWidth']))
				$newParams->set('otherWidth', $_POST['otherWidth']);
			if (isset($_POST['otherHeight']))
				$newParams->set('otherHeight', $_POST['otherHeight']);
			if (isset($_POST['nagovorLine']))
				$newParams->set('nagovorLine', $_POST['nagovorLine']);
			if (isset($_POST['hideRadio']))
				$newParams->set('hideRadio', $_POST['hideRadio']);
			if (isset($_POST['quickImage']))
				$newParams->set('quickImage', $_POST['quickImage']);
			if (isset($_POST['presetValue']))
				$newParams->set('presetValue', $_POST['presetValue']);
			if (isset($_POST['sbSize']))
				$newParams->set('sbSize', $_POST['sbSize']);
            if (isset($_POST['customRadio']))
                $newParams->set('customRadio', ((in_array($row['tip'], [1,6]) && ($row['enota'] == 12 || $row['orientation'] == 9)) ? $_POST['customRadio'] : ''));
            if (isset($_POST['customRadioNumber']) && $_POST['customRadioNumber'] > 0) {
                if ($_POST['tip'] == 6 && $row['enota'] == 12) {
                    $customRadioNumber = $_POST['grids_count'];
                }else{
                    $customRadioNumber  = $_POST['customRadioNumber'];
                }
                $newParams->set('customRadioNumber', $customRadioNumber);
            }
            if (isset($_POST['vizualnaSkalaNumber']) && $_POST['vizualnaSkalaNumber'] > 0) {
                if ($_POST['tip'] == 6 && $row['enota'] == 11) {
                    $vizualnaSkalaNumber = ($_POST['grids_count'] > 7 ? 7 : $_POST['grids_count']);
                }else{
                    $vizualnaSkalaNumber = $_POST['vizualnaSkalaNumber'];
                }
                $newParams->set('vizualnaSkalaNumber', $vizualnaSkalaNumber);
            }
			if (isset($_POST['prvaVrstica']))
				$newParams->set('prvaVrstica', $_POST['prvaVrstica']);
			if (isset($_POST['prvaVrstica_roleta']))
				$newParams->set('prvaVrstica_roleta', $_POST['prvaVrstica_roleta']);
			if (isset($_POST['sbSizeVse']))
				$newParams->set('sbSizeVse', $_POST['sbSizeVse']);
			if (isset($_POST['prevAnswers']))
				$newParams->set('prevAnswers', $_POST['prevAnswers']);
			if (isset($_POST['disabled_vprasanje']))
				$newParams->set('disabled_vprasanje', $_POST['disabled_vprasanje']);
			if ( isset($_POST['slider_handle']) )
				$newParams->set('slider_handle', $_POST['slider_handle']);
			if (isset($_POST['slider_MinMaxNumLabel']))
				$newParams->set('slider_MinMaxNumLabel', $_POST['slider_MinMaxNumLabel']);
			if ( isset($_POST['slider_MinMaxNumLabelNew']) )
				$newParams->set('slider_MinMaxNumLabelNew', $_POST['slider_MinMaxNumLabelNew']);
			if ( isset($_POST['slider_MinMaxLabel']) )
				$newParams->set('slider_MinMaxLabel', $_POST['slider_MinMaxLabel']);
			if ( isset($_POST['slider_VmesneNumLabel']) )
				$newParams->set('slider_VmesneNumLabel', $_POST['slider_VmesneNumLabel']);
			if ( isset($_POST['slider_VmesneDescrLabel']) )
				$newParams->set('slider_VmesneDescrLabel', $_POST['slider_VmesneDescrLabel']);
			if ( isset($_POST['slider_VmesneCrtice']) )
				$newParams->set('slider_VmesneCrtice', $_POST['slider_VmesneCrtice']);
			if (isset($_POST['slider_handle_step']))
				$newParams->set('slider_handle_step', $_POST['slider_handle_step']);
			if (isset($_POST['slider_MinLabel']))
				$newParams->set('slider_MinLabel', $_POST['slider_MinLabel']);
			if (isset($_POST['slider_MaxLabel']))
				$newParams->set('slider_MaxLabel', $_POST['slider_MaxLabel']);
			if (isset($_POST['slider_MinNumLabel']))
				$newParams->set('slider_MinNumLabel', $_POST['slider_MinNumLabel']);
			if (isset($_POST['slider_MaxNumLabel']))
				$newParams->set('slider_MaxNumLabel', $_POST['slider_MaxNumLabel']);
			if (isset($_POST['slider_MinNumLabelTemp']))
				$newParams->set('slider_MinNumLabelTemp', $_POST['slider_MinNumLabelTemp']);
			if (isset($_POST['slider_MaxNumLabelTemp']))
				$newParams->set('slider_MaxNumLabelTemp', $_POST['slider_MaxNumLabelTemp']);
			if ( isset($_POST['slider_window_number']) )
				$newParams->set('slider_window_number', $_POST['slider_window_number']);
			if ( isset($_POST['slider_NumofDescrLabels']) )
				$newParams->set('slider_NumofDescrLabels', $_POST['slider_NumofDescrLabels']);
			if ( isset($_POST['slider_DescriptiveLabel_defaults']) )
				$newParams->set('slider_DescriptiveLabel_defaults', $_POST['slider_DescriptiveLabel_defaults']);
			if ( isset($_POST['slider_DescriptiveLabel_defaults_naslov1']) )
				$newParams->set('slider_DescriptiveLabel_defaults_naslov1', $_POST['slider_DescriptiveLabel_defaults_naslov1']);
			if ( isset($_POST['slider_DescriptiveLabel_defaults_naslov2']) )
				$newParams->set('slider_DescriptiveLabel_defaults_naslov2', $_POST['slider_DescriptiveLabel_defaults_naslov2']);
			if ( isset($_POST['slider_DescriptiveLabel_defaults_naslov3']) )
				$newParams->set('slider_DescriptiveLabel_defaults_naslov3', $_POST['slider_DescriptiveLabel_defaults_naslov3']);
			if ( isset($_POST['slider_DescriptiveLabel_defaults_naslov4']) )
				$newParams->set('slider_DescriptiveLabel_defaults_naslov4', $_POST['slider_DescriptiveLabel_defaults_naslov4']);
			if ( isset($_POST['slider_DescriptiveLabel_defaults_naslov5']) )
				$newParams->set('slider_DescriptiveLabel_defaults_naslov5', $_POST['slider_DescriptiveLabel_defaults_naslov5']);
			if ( isset($_POST['slider_DescriptiveLabel_defaults_naslov6']) )
				$newParams->set('slider_DescriptiveLabel_defaults_naslov6', $_POST['slider_DescriptiveLabel_defaults_naslov6']);
			if ( isset($_POST['slider_DescriptiveLabel_defaults_naslov7']) )
				$newParams->set('slider_DescriptiveLabel_defaults_naslov7', $_POST['slider_DescriptiveLabel_defaults_naslov7']);
			if ( isset($_POST['slider_nakazi_odgovore']) )
				$newParams->set('slider_nakazi_odgovore', $_POST['slider_nakazi_odgovore']);				
			if ( isset($_POST['slider_StevLabelPodrocij']) )
				$newParams->set('slider_StevLabelPodrocij', $_POST['slider_StevLabelPodrocij']);
			if ( isset($_POST['hotspot_image']) ){
				$newParams->set('hotspot_image', $_POST['hotspot_image']);
				$newParams->set('multi_input_type', 'marker');	
                        }
			if ( isset($_POST['slider_labele_podrocij']) ){
				$newParams->set('slider_labele_podrocij', $_POST['slider_labele_podrocij']);				
			}
            if ( isset($_POST['fokus_mape']) )
				$newParams->set('fokus_mape', $_POST['fokus_mape']);	
            if ( isset($_POST['naslov_podvprasanja_map']) )
				$newParams->set('naslov_podvprasanja_map', $_POST['naslov_podvprasanja_map']);
            if ( isset($_POST['user_location']) )
				$newParams->set('user_location', $_POST['user_location']);
            if ( isset($_POST['dodaj_searchbox']) )
				$newParams->set('dodaj_searchbox', $_POST['dodaj_searchbox']);
            if ( isset($_POST['max_markers']) )
				$newParams->set('max_markers', $_POST['max_markers']);
            if ( isset($_POST['multi_input_type']) )
				$newParams->set('multi_input_type', $_POST['multi_input_type']);
            if ( isset($_POST['marker_podvprasanje']) )
				$newParams->set('marker_podvprasanje', $_POST['marker_podvprasanje']);
			if ( isset($_POST['hotspot_region_visibility_option']) ){
				$newParams->set('hotspot_region_visibility_option', $_POST['hotspot_region_visibility_option']);			
			}
			if ( isset($_POST['hotspot_region_visibility']) ){
				$newParams->set('hotspot_region_visibility', $_POST['hotspot_region_visibility']);			
			}
			if ( isset($_POST['hotspot_region_color']) ){
				$newParams->set('hotspot_region_color', $_POST['hotspot_region_color']);			
			}
			if ( isset($_POST['hotspot_visibility_color']) ){
				$newParams->set('hotspot_visibility_color', $_POST['hotspot_visibility_color']);			
			}						
			if ( isset($_POST['heatmap_click_shape']) ){
				$newParams->set('heatmap_click_shape', $_POST['heatmap_click_shape']);			
			}			
			if ( isset($_POST['heatmap_click_size']) ){
				$newParams->set('heatmap_click_size', $_POST['heatmap_click_size']);			
			}		
			if ( isset($_POST['heatmap_click_color']) ){
				$newParams->set('heatmap_click_color', $_POST['heatmap_click_color']);			
			}
			if ( isset($_POST['hotspot_tooltips_option']) ){
				$newParams->set('hotspot_tooltips_option', $_POST['hotspot_tooltips_option']);
			}
			if ( isset($_POST['diferencial_trak']) ){
				$newParams->set('diferencial_trak', $_POST['diferencial_trak']);		
			}
			if ( isset($_POST['diferencial_trak_starting_num']) ){
				$newParams->set('diferencial_trak_starting_num', $_POST['diferencial_trak_starting_num']);		
			}
			if ( isset($_POST['trak_num_of_titles']) ){
				$newParams->set('trak_num_of_titles', $_POST['trak_num_of_titles']);		
			}
			if ( isset($_POST['display_drag_and_drop_new_look']) ){
				$newParams->set('display_drag_and_drop_new_look', $_POST['display_drag_and_drop_new_look']);		
			}
			if ( isset($_POST['custom_column_label_option']) ){
				$newParams->set('custom_column_label_option', $_POST['custom_column_label_option']);		
			}
			if ( isset($_POST['grid_repeat_header']) ){
				$newParams->set('grid_repeat_header', $_POST['grid_repeat_header']);		
			}
			if ( isset($_POST['heatmap_num_clicks']) ){
				$newParams->set('heatmap_num_clicks', $_POST['heatmap_num_clicks']);		
			}
			if ( isset($_POST['heatmap_show_clicks']) ){
				$newParams->set('heatmap_show_clicks', $_POST['heatmap_show_clicks']);		
			}
			if ( isset($_POST['heatmap_show_counter_clicks']) ){
				$newParams->set('heatmap_show_counter_clicks', $_POST['heatmap_show_counter_clicks']);		
			}
			if(isset($_POST['slider_DescriptiveLabel_defaults'])){
			
				$slider_descriptiveLabels_defaults = $_POST['slider_DescriptiveLabel_defaults'];
				$slider_NumofDescrLabels = ($_POST['slider_NumofDescrLabels'] + 1);
				$slider_VmesneDescrLabel = $_POST['slider_VmesneDescrLabel'];
				
				if($slider_VmesneDescrLabel == 1){
					if($slider_descriptiveLabels_defaults > 0){	//ce so izbrane default opisne labele
						
						// Napolnimo prave vrednosti
						$values = $default_grid_values[$slider_descriptiveLabels_defaults];
						
						$indexArray = array(
							2 => array(2, 6),
							3 => array(2, 4, 6),
							4 => array(1, 2, 6, 7),
							5 => array(1, 2, 4, 6, 7),
							6 => array(1, 2, 3, 5, 6, 7),
							7 => array(1, 2, 3, 4, 5, 6, 7)
						);
						
						$naslov = array();	//definicija 
					
						for($i=1; $i<=$slider_NumofDescrLabels; $i++){	
							
							// Ce imamo samo en grid
							if($slider_NumofDescrLabels == 1 && $i == 1){
								$index = 2;
								//$naslov = $values[2]; //slider_DescriptiveLabel_defaults_naslov
								$slider_DescriptiveLabel_defaults_naslov = $values[2];
							}
							// Ce imamo vec kot 7 gridov - prvih 7 zapisemo normalno, ostali so prazni
							else if($slider_NumofDescrLabels > 7){
								if($i <= 7){
									$index = $indexArray[7][$i-1];
									//$naslov = $values[$index];
									$slider_DescriptiveLabel_defaults_naslov = $values[$index];
									//echo $slider_DescriptiveLabel_defaults_naslov;
								}
								else
									break;
							}					
							// Ce imamo 2 - 7 gridov jih izpisemo v skladu z $indexArray
							else{
								$index = $indexArray[$slider_NumofDescrLabels][$i-1];
								//$naslov = $values[$index];
								$slider_DescriptiveLabel_defaults_naslov = $values[$index];
								//echo $slider_DescriptiveLabel_defaults_naslov.'<br />';
							}
							//echo $slider_DescriptiveLabel_defaults_naslov;
							$naslov[$i] = $slider_DescriptiveLabel_defaults_naslov;
							//echo $naslov[$i].'<br />';
						}
						$implodednaslov = implode(";", $naslov); //zdruzi elemente array v string
						$newParams->set('slider_DescriptiveLabel_defaults_naslov1', $implodednaslov);
						

                    }
                    else{	//ce so custom opisne labele
				
					}
				}
				else if($slider_VmesneDescrLabel != 1){

				}
			}
			
			//*******************
			if ($_POST['enota'] == 9){	//ce je postavitev drag and drop
				$newParams->set('izris_droppable_grid', 0);
			}
			//**************************
			
				
			$params = $newParams->getString();
			$update .= ", params = '$params' ";
		}

		// shrani nastavitev za orientacijo
		if (isset($_POST['orientation'])) {

			# checkbox
			//if ($row['tip'] == 2 || $row['tip'] == 7 || $row['tip'] == 8 || $row['tip'] == 21) {
			if ($row['tip'] == 7 || $row['tip'] == 8 || $row['tip'] == 21) {
				# če je checkbox ne spreminjamo tipa, tudi če je datum, besedilo ali number
				$update .= ", orientation = '$_POST[orientation]' ";
			 
			} else {			
				# radio, dropdown				
				//dropdown
				if($_POST['orientation'] == 4)
					$update .= ", orientation = '1', tip = '3', hidden_default = '0' ";
				//elseif ($_POST['orientation'] == 6)
				//	$update .= ", orientation = '6', tip = '3', hidden_default = '0' ";
				//druge opcije orientacije
				elseif ($_POST['orientation'] == 5)
					$update .= ", orientation = '1', tip = '1', hidden_default = '1' ";
				else{
					if($_POST['tip'] == 2){
						$update .= ", orientation = '$_POST[orientation]', tip = '2', hidden_default = '0' ";	//ce smo preklopli, ko je bilo vprasanje checkbox, naj se vrne checkbox
					}
					elseif($_POST['tip'] == 1){
						$update .= ", orientation = '$_POST[orientation]', tip = '1', hidden_default = '0' "; //ce smo preklopli, ko je bilo vprasanje radio button, naj se vrne kot radio button

                        //v kolikor gre za slikovni tip radio buttons potem v tabelo srv_vrednost vstaviti ustrezno št. vrstic
                        if($_POST['orientation'] == 9)
                            $this->slikovni_tip($_POST['customRadioNumber']);

                        // vizualno analogno skalo buttons potem v tabelo srv_vrednost vstaviti ustrezno št. vrstic
                        if($_POST['orientation'] == 11)
                            $this->slikovni_tip($_POST['vizualnaSkalaNumber']);
                    }
				}
			}
        }

		if (isset($_POST['note'])) {
			$update .= ", note = '$_POST[note]' ";
		}
		
		// shrani nastavitev za file upload
		if (isset($_POST['upload'])) {
			$update .= ", upload = '$_POST[upload]' ";
		}

		//shrani nastavitev za elektronski podpis
		if (isset($_POST['signature'])){
			$update .= ", signature = '$_POST[signature]' ";		
		}                
        
		// shrani enoto - podtip lokacije
		if (isset($_POST['podtip_lokacija'])) {
			$update .= ", enota = '$_POST[podtip_lokacija]' ";
		}

		// shrani podnaslova za dvojni grid
		if (isset($_POST['grid_subtitle1'])) {
			$update .= ", grid_subtitle1 = '$_POST[grid_subtitle1]' ";	
		}
		
		if (isset($_POST['grid_subtitle2'])) {
			$update .= ", grid_subtitle2 = '$_POST[grid_subtitle2]' ";			
		}
		
		if (isset($_POST['inline_edit'])) {
			$update .= ", inline_edit = '$_POST[inline_edit]' ";			
		}
		
		if (isset($_POST['onchange_submit'])) {
			$update .= ", onchange_submit = '$_POST[onchange_submit]' ";			
		}
		
		if (isset($_POST['showOnAllPages'])) {
			$update .= ", showOnAllPages = '$_POST[showOnAllPages]' ";
		}
		
		if (isset($_POST['validationedit'])) {
			
			foreach ($_POST AS $key => $val) {
				
				if ( substr($key, 0, 11) == 'validation-' ) {
					
					$key = explode('-', $key);
					
					if ( $key[2] == 'reminder' ) {
						
						$if_id = $key[1];
						$reminder = $val;
						
						sisplet_query("UPDATE srv_validation SET reminder='$reminder' WHERE spr_id='$this->spremenljivka' AND if_id='$if_id'");
						
					} elseif ( $key[2] == 'reminder_text' ) {
						
						$if_id = $key[1];
						$reminder_text = $val;
						
						sisplet_query("UPDATE srv_validation SET reminder_text='$reminder_text' WHERE spr_id='$this->spremenljivka' AND if_id='$if_id'");
						
					}
				}
				
			}

		}
		
		// shrani nastavitev za multiple subtitle
		if (isset($_POST['multiple_subtitle'])) {
			$update .= ", grid_subtitle1 = '$_POST[multiple_subtitle]' ";
		}
        	
		
		$update = substr($update, 1);	// odrezemo prvo vejico
		
		
		if ($update != '') {
			$sql = sisplet_query("UPDATE srv_spremenljivka SET $update WHERE id = '$this->spremenljivka' ");
			if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		}
		
		$this->ajax_vprasanje_refresh();
    
    	self::vprasanje_tracking();
		
		//echo $update;
	}

    function slikovni_tip($number){
        
        //pobrišemo dosedanje vrednosti
        $dosedanjiVnosi = sisplet_query("SELECT id, naslov, variable, vrstni_red FROM srv_vrednost WHERE spr_id='".$this->spremenljivka."' ORDER BY vrstni_red");
        $st = mysqli_num_rows($dosedanjiVnosi);

        // V koliko povečamo število vnosov od dosedanjega vprašanja
        if($st < $number) {
            if($st > 0){
                $zaporedje = 1;
                while($row = mysqli_fetch_assoc($dosedanjiVnosi)){
                    sisplet_query("UPDATE srv_vrednost SET naslov=$zaporedje, variable=$zaporedje, vrstni_red=$zaporedje WHERE id=".$row['id']." AND spr_id=".$this->spremenljivka);
                  $zaporedje++;
                }
            }

            for ( $i = $st + 1; $i < $number + 1; $i++) {
                sisplet_query("INSERT INTO srv_vrednost (spr_id, naslov, variable, vrstni_red) VALUES ('$this->spremenljivka', '$i', '$i', '$i')");
            }
        } 
        elseif($st > $number) {
                $preveriOdgovore = sisplet_query("SELECT spr_id FROM srv_data_vrednost" . $this->db_table. " WHERE spr_id='".$this->spremenljivka."'");

                if(mysqli_num_rows($preveriOdgovore) == 0){
                    sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='".$this->spremenljivka."' AND vrstni_red>'".$number."'");
                }
        }


        // Preštevilčimo naslove v kolikor bi radio tip spremenili in ostanejo v labelah imena namesto številk
        $zaporedje = 1;
        while($row = mysqli_fetch_assoc($dosedanjiVnosi)){
            if(!is_numeric($row['naslov']) || !is_int($row['variable'])) {
                sisplet_query("UPDATE srv_vrednost SET naslov=$zaporedje WHERE id=".$row['id']." AND spr_id=".$this->spremenljivka);
            }

            $zaporedje++;
        }
    }

	
	/**
   	* prikaze spremenljivko pri skrcenem nacinu
   	* 
   	* @param mixed $spremenljivka
   	* @param mixed $naslov
   	* @param mixed $variable
   	* @param mixed $visible
   	* @param mixed $sistem
   	*/
    function spremenljivka_name ($spremenljivka, $naslov=null, $variable=null, $visible=1, $sistem=0) {
		global $lang;
		
		$row = Cache::srv_spremenljivka($spremenljivka);
		
		$tip = $row['tip'];
        $naslov = $row['naslov'];
        $variable = $row['variable'];
        $visible = $row['visible'];
        $sistem = $row['sistem'];
    
		// barva vprašanja je privzeto modra, če pa je sistemsko ali skrito pa je rdeča 
		$spanred = ($visible == 0 || $sistem == 1 ) ? ' <span class="red">' : ''; 
		
		if ($tip != 22) {	// navadne spremenljivke
			echo '<span class="colorvariable">('.$variable.')</span> '.$spanred.skrajsaj(strip_tags($naslov), 80).($spanred!=''?'</span>':'').' <span class="spr_comment">( '.$lang['srv_vprasanje_tip_long_'.$row['tip']].' )</span>';
		
		} else {			// kalkulacija
			$b = new Branching($this->anketa);
			echo '<span class="calculationvariable">('.$variable.')</span> '.$b->calculations_display(-$spremenljivka).' <span class="spr_comment">( '.$lang['srv_vprasanje_tip_long_'.$row['tip']].' )</span>';
			
		}
    }
	
	/**
	* pobrise vrednost
        * 
        * $API_call = true, ce se klice iz API
	* 
	*/
	function ajax_vrednost_delete ($vrednost = null, $API_call = false) {
		global $lang;
		
		Common::getInstance()->Init($this->anketa);
        Common::getInstance()->updateEditStamp();
    	
		$spremenljivka = $this->spremenljivka;
                
        if($vrednost == null)
            $vrednost = (int)$_POST['vrednost'];
        
        if(!$API_call){
            $confirmed = (int)$_POST['confirmed'];
            $can_delete_last = (isset($_POST['can_delete_last']) && $_POST['can_delete_last'] == 1) ?
                    true : false;
        }
        else
            $confirmed = '1';
                
		$return = array();
		
		// preverimo, ce obstajajo ze podatki za spremenljivko - v tem primeru damo dodaten error
		if ($confirmed != '1') {
			$sql = sisplet_query("SELECT count(*) AS count FROM srv_user WHERE ank_id='$this->anketa' AND deleted='0' AND preview='0'");
            $row = mysqli_fetch_array($sql);
            
			if ($row['count'] > 0) {

                $return['error'] = 2;
                
                $return['output'] = '<h2>'.$lang['srv_warning'].'</h2>';
                $return['output'] .= '<div class="popup_close"><a href="#" onClick="$(\'#dropped_alert\').hide(); $(\'#fade\').fadeOut(); return false;">✕</a></div>';

				$return['output'] .= '<p>'.$lang['spremenljivka_delete_data_vre'].'</p>';
                $return['output'] .= '<p>'.$lang['srv_brisivrednostconfirm_data'].'</p><br />';
                
                //ce se spremeni onclick, se prosim spremeni v datoteki vprasanjeInline.js v funkciji inline_vrednost_delete v else if (data.error == 2)
                $return['output'] .= '<span class="buttonwrapper floatRight"><a class="ovalbutton ovalbutton_orange" href="#" onclick="inline_vrednost_delete(\''.$spremenljivka.'\', \''.$vrednost.'\', \'1\'); $(\'#dropped_alert\').html(\'\').hide(); $(\'#fade\').fadeOut(); return false;"><span>'.$lang['srv_brisivrednost'].'</span></a></span>';
                $return['output'] .= '<span class="buttonwrapper floatRight spaceRight"><a class="ovalbutton ovalbutton_gray" href="#" onclick="$(\'#dropped_alert\').hide(); $(\'#fade\').fadeOut(); return false;"><span>'.$lang['srv_analiza_arhiviraj_cancle'].'</span></a></span>';
				//$return['output'] .= '<p><a href="#" id="brisivrednostchecked" onclick="inline_vrednost_delete(\''.$spremenljivka.'\', \''.$vrednost.'\', \'1\'); $(\'#dropped_alert\').html(\'\').hide(); $(\'#fade\').fadeOut(); return false;">'.$lang['srv_brisivrednost'].'</a> <a href="#" onclick="$(\'#dropped_alert\').html(\'\').hide(); $(\'#fade\').fadeOut(); return false;">'.$lang['srv_analiza_arhiviraj_cancle'].'</a></p>';
                
                echo json_encode($return);
                
                return;
			}
		}
		
        if(!$API_call && !$can_delete_last){
            $sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_vrednost WHERE spr_id='$spremenljivka' AND id != '$vrednost'");
            $row = mysqli_fetch_array($sql);
            if ($row['count'] == 0) return;
        }
		
		if ($vrednost <= 0) return;
		
		$sql = sisplet_query("SELECT spr_id, if_id FROM srv_vrednost WHERE id = '$vrednost'");
		$row = mysqli_fetch_array($sql);
		
		$sql = sisplet_query("DELETE FROM srv_vrednost WHERE id = '$vrednost'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);	
		
		$spremenljivka = $row['spr_id'];
		
		// Pobrisemo se pogoj ce je dodan na vrednost
		if($row['if_id'] > 0){

			$if = $row['if_id'];
				
			$sqlCV = sisplet_query("SELECT id FROM srv_condition WHERE if_id = '$if'");
			while ($rowCV = mysqli_fetch_array($sqlCV))
				sisplet_query("DELETE FROM srv_condition_vre WHERE cond_id='$rowCV[id]'");

			sisplet_query("DELETE FROM srv_condition WHERE if_id = '$if'");
			sisplet_query("DELETE FROM srv_if WHERE id = '$if'");
		}
		
		$rows = Cache::srv_spremenljivka($spremenljivka);
		if ($rows['tip'] == 24) {
			$this->repare_grid_multiple($spremenljivka);
		}
		
		Common::repareVrednost($spremenljivka);
		Common::prestevilci($spremenljivka);
		
		$return['error'] = 0;
		
        if(!$API_call)
            echo json_encode($return);
        else
            return $return;
	}
	
	/**
	* pobrise vrednost
	* 
	*/
	function ajax_vrednosti_other_delete () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
		$spremenljivka = $this->spremenljivka;
		
		$sql = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id = '$spremenljivka' AND other = '1'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
				
		$spremenljivka = $row['spr_id'];
		
		Common::repareVrednost($spremenljivka);
		Common::prestevilci($spremenljivka);
	}
	
	/**
	* funkcija, ki doda novo vrednost
	* 
	*/
	function vrednost_new ($naslov='', $other=0, $mv = null, $spr_id=null) {
		global $lang;

		Common::updateEditStamp();
		
		$anketa = $this->anketa;
		$spremenljivka = $this->spremenljivka;
		
		if ($spr_id != null) $spremenljivka = $spr_id;
		
		$purifier = New Purifier();

        $naslov = $purifier->purify_DB($naslov);
    	
		$sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_vrednost WHERE spr_id='$spremenljivka' AND vrstni_red>0");
		$row = mysqli_fetch_array($sql);
		$nums = $row['count'];
		$vrstni_red = $nums +1;
		
		$variable = -$other;		// tole se itak popravi v prestevilci()
		
        //pri API, se poslje tudi naslov za other
		if ($other == 1 && $naslov == '') {
			$naslov = $lang['srv_other'] . ':';
		}
		# popravimi za missing vrednosi
		if ($mv != null) {
			# katere missinge imamo na voljo
			$smv = new SurveyMissingValues($this->anketa);

			$missing_values = $smv->GetUnsetValuesForSurvey();
			$naslov = addslashes($missing_values[$mv]);

			$variable = $mv;
			$other = $mv;
		}
		
		$row1 = Cache::srv_spremenljivka($spremenljivka);
		$random = $row1['random'];
		
		if((int)$variable == 0) {
			$variable = $vrstni_red;
		}
		$sql = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red, random, other) VALUES ('', '$spremenljivka', '$naslov', '$variable', '$vrstni_red', '$random', '$other')");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		$vrednost = mysqli_insert_id($GLOBALS['connect_db']);
		
			
		// dodamo vrednosti -4 za novo variablo k že vpisanim odgovorom
		// multigridu dodamo vrednost -4
		if ($row1['tip'] == 6 || $row1['tip'] == 16 || $row1['tip'] == 19 || $row1['tip'] == 20) { // multigrid, multicheckbox, multitext, multinumber
			//$sql = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id = '$spremenljivka'");
			$sql1 = sisplet_query("SELECT id FROM srv_user WHERE ank_id='$anketa'");
			$values = '';
			while ($row1 = mysqli_fetch_assoc($sql1)) {
				
				//$s = sisplet_query("INSERT INTO srv_data_grid".$this->db_table." (spr_id, vre_id, usr_id, grd_id) VALUES ('$spremenljivka', '$vrednost', '$row1[id]', '-4')");
				if ($values != '') $values .= ', ';
				$values .= "('$spremenljivka', '$vrednost', '$row1[id]', '-4')";
			}
			$s = sisplet_query("INSERT INTO srv_data_grid".$this->db_table." (spr_id, vre_id, usr_id, grd_id) VALUES $values");
			
		}

		if ($row1['tip'] == 24) {
			$this->repare_grid_multiple($row1['id']);
		}
		
		if ($row1['tip'] == 17) { // ranking		
			$sql1 = sisplet_query("SELECT id FROM srv_user WHERE ank_id='$anketa'");
			while ($row1 = mysqli_fetch_assoc($sql1)) {
				$s = sisplet_query("INSERT INTO srv_data_rating (spr_id, vre_id, usr_id, vrstni_red) VALUES ('$spremenljivka', '$vrednost', '$row1[id]', '-4')");
			}
		}
                
		if($row1['tip'] == 26 && $row1['enota'] == 3){
			$sql1 = sisplet_query("SELECT id FROM srv_user WHERE ank_id='$anketa'");
			while ($row1 = mysqli_fetch_assoc($sql1)) {
					$s = sisplet_query("INSERT INTO srv_data_map (spr_id, vre_id, usr_id, text) VALUES ('$spremenljivka', '$vrednost', '$row1[id]', '-4')");
			}
		}
		
		return $vrednost;
	}
	
	/**
	* doda novo vrednost
	* 
	*/
	function ajax_vrednost_new () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
		global $lang;
		
		$naslov = '';
		$other = $_POST['other'];
		$mv = $_POST['mv'];

		$vrednost = $this->vrednost_new($naslov, $other, $mv);
		
		Common::prestevilci($this->spremenljivka);
		$this->edit_vrednost_li($vrednost);
	}
	
	function ajax_change_tip () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
		$tip = $_POST['tip'];
		
		self::change_tip($this->spremenljivka, $tip);
		
		$this->display();
	}
	
	function ajax_show_tip () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
		$tip = $_POST['tip'];
		echo $tip;
		self::show_tip($tip);
		
		//$this->display();
	}
	
	// spremeni tip vprasanja demografija -- najprej ustvari novo vprasanje za trenutnim, in ga nato se izbrise (trenutnega)
	function ajax_change_demografija () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
    	$podtip = $_POST['podtip'];
		
		ob_start();
		$ba = new BranchingAjax($this->anketa);
		$ba->ajax_spremenljivka_new($this->spremenljivka, 0, 0, 0, 23, $podtip);
		
		$sa = new SurveyAdmin();
		$sa->brisi_spremenljivko($this->spremenljivka);
		ob_clean();
		
		echo $ba->spremenljivka;
	}
	
	function ajax_change_diferencial ($e = null) {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
		
		$enota = $_POST['enota'];
		if ($e !== null) $enota = $e;

		$sql = sisplet_query("UPDATE srv_spremenljivka SET enota = '$enota' WHERE id = '$this->spremenljivka'");
		
		$this->edit_vrednost();		
	}
	
	function ajax_vrednost_edit() {
		
		$vrednost = $_POST['vrednost'];
		$this->vrednost_edit($vrednost);	
	}
	
	function ajax_vrednost_insert_image() {
		
		$vrednost = $_POST['vrednost'];
		$this->vrednost_insert_image($vrednost);	
	}

	function ajax_hotspot_image_save () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
		$spr_id = $_POST['spremenljivka'];
		$naslov = $_POST['hotspot_image'];
		
		$row = Cache::srv_spremenljivka($spr_id);
		$newParams = new enkaParameters($row['params']);
				
		if (strtolower(substr($naslov, 0, 3)) == '<p>' && strtolower(substr($naslov, -4)) == '</p>') {
            $tmp = substr(substr($naslov, 0, -4), 3);
            
			if (strpos($tmp, "<p>") === false)
				$naslov = $tmp;
		}
		
		$purifier = New Purifier();
    	$naslov = $purifier->purify_DB($naslov);
		
		if (isset($_POST['hotspot_image'])){
            
            if ($_POST['hotspot_image'] == ""){
				$hotspot_image = "";
            }
            else{
				$hotspot_image = $_POST['hotspot_image'];
				$dimensions_present = strpos($hotspot_image,'style=');
				
				//ce slika nima dimenzij
				if($dimensions_present == ""){		

					//pobrisi obstojeci parameter hotspot_image
					$newParams->set('hotspot_image', "");
					$params = $newParams->getString();
					$sql = sisplet_query("UPDATE srv_spremenljivka SET params='$params' WHERE id='$spr_id'");
					
					$length = strlen($hotspot_image);	//dobi dolzino celotne html kode za sliko
					$hotspot_image = substr($hotspot_image, 0, ($length-2));	//izlusci vse razen zadnjih dveh znakov, kjer se zakljuci html koda za sliko

                    $hotspot_image = $hotspot_image . 'style="height:'.$_POST['height'].'px; width:'.$_POST['width'].'px;" />';
				}
			}
						
			//vnesi parameter hotspot_image
			$newParams->set('hotspot_image', $hotspot_image);
			$params = $newParams->getString();
			$sql = sisplet_query("UPDATE srv_spremenljivka SET params='$params' WHERE id='$spr_id'");
		}
		
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		
		echo mysql_real_unescape_string($naslov);
	}
	
	
	function ajax_hotspot_edit() {
		
		$vrednost = $_POST['vrednost'];
		$this->hotspot_edit($vrednost);
		
	}
	
	function ajax_hotspot_edit_regions() {
		
		$vrednost = $_POST['vrednost'];
		//$src_image = $_POST['src_image'];
		//$hotspot_image_height = $_POST['hotspot_image_height'];
		//$hotspot_image_width = $_POST['hotspot_image_width'];
		//$spr_id = $_POST['spr_id'];

		$this->hotspot_edit_regions($vrednost);
		
	}
	
	function ajax_hotspot_save_regions () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
		$spr_id = $_POST['spr_id'];
		$vre_id = $_POST['vre_id'];
		$hotspot_region_name = $_POST['hotspot_region_name'];
		$hotspot_region_coords = $_POST['hotspot_region_coords'];
		$last_hotspot_region_index = $_POST['last_hotspot_region_index'];
		$hotspot_region_index = $_POST['hotspot_region_index'];
		//$hotspot_region_index = 0;
			
		
		$purifier = New Purifier();
    	$hotspot_region_name = $purifier->purify_DB($hotspot_region_name);

		$sqlVrednost = sisplet_query("SELECT variable, vrstni_red FROM srv_vrednost WHERE spr_id = $spr_id AND id = $vre_id");
		$rowVrednost = mysqli_fetch_array($sqlVrednost);
		$vrstni_red = $rowVrednost['vrstni_red'];
		$rowSpr = Cache::srv_spremenljivka($spr_id);
		$variableName = $rowSpr['variable'];
		
		if($rowSpr['tip'] == 27){ //ce je heatmap vprasanje
			$variable = $variableName.chr($vrstni_red+96);	//spremeni default "variable", da ne bo tezav pri izvozu podatkov v SPSS
		}else{ //drugace
			$variable = $rowVrednost['variable'];	//poberi "variable" iz tabele srv_vrednost
		}
		
		
		
		//preveri, ce je kaksno obmocje shranjeno v bazi
		$sqlR = sisplet_query("SELECT id FROM srv_hotspot_regions WHERE spr_id = $spr_id AND vre_id = $vre_id");
		$rowR = mysqli_fetch_array($sqlR);
		
		if (mysqli_num_rows($sqlR) == 0){	//se ni obmocja v bazi za trenutno spremenljivko in vrednost kategorije odgovora
			
			//vnesi podatke o obmocju v bazo za trenutno spremenljivko in vrednost kategorije odgovora
			$sql = sisplet_query("INSERT INTO srv_hotspot_regions (vre_id, spr_id, region_name, region_coords, region_index, variable, vrstni_red) VALUES ('$vre_id', '$spr_id', '$hotspot_region_name', '$hotspot_region_coords', '$last_hotspot_region_index', '$variable', '$vrstni_red')");
			
			//posodobi podatke o obmocju za njegovo vrednost kategorije
			$sql_vre = sisplet_query("UPDATE srv_vrednost SET naslov = '$hotspot_region_name', variable = '$variable' WHERE spr_id = '$spr_id' AND id = '$vre_id'");
			
			//naberi vse identifikatorje kategorij odgovorov drugih obmocij, ki niso trenutnega obmocja
			$sql_vre_select = sisplet_query("SELECT id, spr_id, variable, vrstni_red FROM srv_vrednost WHERE spr_id = $spr_id AND id != '$vre_id'");
			
			//za vsako kategorijo odgovora drugih obmocij, ki niso trenutno obmocje
 			while($row_vre_select = mysqli_fetch_array($sql_vre_select)){
				$variable_select = $row_vre_select['variable'];
				$vrstni_red_select = $row_vre_select['vrstni_red'];
				$spr_id_select = $row_vre_select['spr_id'];
				$vre_id_select = $row_vre_select['id'];
				
				//posodobi podatke drugih obmocij
				$sql = sisplet_query("UPDATE srv_hotspot_regions SET variable = '$variable_select', vrstni_red = '$vrstni_red_select' WHERE spr_id = '$spr_id_select' AND vre_id = $vre_id_select");
			}
			
		}else{	//obstaja obmocje v bazi za trenutno spremenljivko in vrednost kategorije odgovora

			//posodobi podatke o obmocju za trenutno spremenljivko in vrednost kategorije odgovora
			$sql = sisplet_query("UPDATE srv_hotspot_regions SET region_name = '$hotspot_region_name', region_coords = '$hotspot_region_coords', region_index = '$hotspot_region_index', variable = '$variable', vrstni_red = '$vrstni_red' WHERE spr_id = '$spr_id' AND vre_id = $vre_id");
			
			//posodobi podatke o obmocju za njegovo vrednost kategorije
			$sql_vre = sisplet_query("UPDATE srv_vrednost SET naslov = '$hotspot_region_name' WHERE spr_id = '$spr_id' AND id = '$vre_id'");

		}
		//poberi vre_id novega polja za shranjevanja imena naslednjega obmocja
		$sql_vre_id = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = $spr_id order by vrstni_red DESC LIMIT 1");
		$row_vre_id = mysqli_fetch_array($sql_vre_id);
		
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		
		echo mysql_real_unescape_string($hotspot_region_name);
		
	}
	
	function ajax_vrednost_save () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
		$vrednost = $_POST['vrednost'];
		
		$sql = sisplet_query("SELECT variable, variable_custom FROM srv_vrednost WHERE id = '$vrednost'");
		$row = mysqli_fetch_array($sql);
		
		$variable = $_POST['vrednost_variable'];
		$naslov = $_POST['vrednost_naslov'];
		$random = $_POST['vrednost_random'];
				
		if (strtolower(substr($naslov, 0, 3)) == '<p>' && strtolower(substr($naslov, -4)) == '</p>') {
			//$naslov = '<p>'.nl2br($naslov).'</p>';
			$tmp = substr(substr($naslov, 0, -4), 3);
			if (strpos($tmp, "<p>") === false)
				$naslov = $tmp;
		}
		
		$purifier = New Purifier();
    	$naslov = $purifier->purify_DB($naslov);
    	
		if ($variable != $row['variable'] || $row['variable_custom'] == 1)
			$variable_custom = 1;
		else
			$variable_custom = 0;
		
		$sql = sisplet_query("UPDATE srv_vrednost SET naslov='$naslov', variable='$variable', variable_custom='$variable_custom', random='$random' WHERE id = '$vrednost'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		
		echo mysql_real_unescape_string($naslov);
	}
	
	function ajax_vrednost_fastadd () {
				
		$this->vrednost_fastadd();
		
	}
	
	function ajax_vrednost_fastadd_save () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
		global $lang;
		
		$fastadd = mysql_real_unescape_string( $_POST['fastadd'] );
		if ($fastadd == '') return;
		
		$s = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$this->spremenljivka' AND ( naslov='' OR naslov LIKE '%$lang[srv_new_vrednost]%' )");
		echo ("DELETE FROM srv_vrednost WHERE spr_id='$this->spremenljivka' AND ( naslov='' OR naslov = '$lang[srv_new_vrednost]' )");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		$fastadd = explode("\n", $fastadd);
		
		foreach ($fastadd AS $naslov) {
			if ($naslov != '') {
				$this->vrednost_new(trim($naslov), $other);
			}
		}
		
		Common::prestevilci($this->spremenljivka);
	}
	
	function ajax_vprasanje_refresh () {
		global $lang;
				
		if ($_GET['silentsave'] == 'true')
			$silentsave = true;
		else
			$silentsave = false;

		
		if ($this->expanded || $silentsave) {
			
			Cache::clear_cache();

			if($this->spremenljivka > 0){
				$Branching = new Branching($this->anketa);
				$Branching->vprasanje($this->spremenljivka);
			}
			elseif($this->spremenljivka == -1){
				$Branching = new Branching($this->anketa);
				$Branching->introduction_conclusion(-1, 0);
			}
			elseif($this->spremenljivka == -2){
				$Branching = new Branching($this->anketa);
				$Branching->introduction_conclusion(-2, 0);
			}
			elseif($this->spremenljivka == -3){
				$Glasovanje = new Glasovanje($this->anketa);
				$Glasovanje->edit_statistika();
			}
		
		} else {

			/* tole je skopirano iz Branching->spremenljivka_name(), da ne loadamo celga classa za par vrstic */
			
			// tukaj je treba še enkrat prebrat iz baze, ker se vrednosti spremenijo
			Cache::clear_cache();
			
			if ($this->spremenljivka > 0) {
		    
		        $this->spremenljivka_name($this->spremenljivka);
			
			} elseif ($this->spremenljivka == -1) {
				echo ''.$lang['srv_intro_label'].'';
				
			} elseif ($this->spremenljivka == -2) {
				echo ''.$lang['srv_end_label'].'';
				
			}
			
		}
		
	}
	
	function ajax_vprasanje_tracking () {
		global $lang;
		
		self::vprasanje_tracking(1);
		
		echo $lang['srv_vprasanje_tracking_done'];
	}
	
	function ajax_validation_new () {
		
		$sql = sisplet_query("INSERT INTO srv_if (id) VALUES ('')");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $if_id = mysqli_insert_id($GLOBALS['connect_db']);
        
		$s = sisplet_query("INSERT INTO srv_condition (id, if_id, vrstni_red) VALUES ('', '$if_id', '1')");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		$s = sisplet_query("INSERT INTO srv_validation (spr_id, if_id, reminder) VALUES ('$this->spremenljivka', '$if_id', '1')");
		if (!$s) echo mysqli_error($GLOBALS['connect_db']);
		
		$b = new Branching($this->anketa);
		
		$b->spremenljivka = $_POST['spremenljivka'];
        $b->condition_editing($if_id, -4);
        
	}
	
	function ajax_validation_edit() {
		
		$if_id = (int)$_POST['if_id'];
		
		$b = new Branching($this->anketa);
		
		$b->spremenljivka = $_POST['spremenljivka'];
        $b->condition_editing($if_id, -4);
		
	}
	
	function ajax_validation_if_close () {
		
		$this->spremenljivka = $_POST['spremenljivka'];
		
		$this->vprasanje_validation();
		
	}
	
	function ajax_change_subtype_number () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		// slider pri numberju
		if ( isset($_POST['ranking_k']) && $row['tip']=='7' ) {
			
			if ($row['num_useMin'] == '0' && $row['num_useMax']=='0') {
				
				$s = sisplet_query("UPDATE srv_spremenljivka SET ranking_k='$_POST[ranking_k]', vsota_min='0', vsota_limit='100', num_useMin='1', num_useMax='1' WHERE id = '$this->spremenljivka'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				
				/*$s = sisplet_query("SELECT * From srv_spremenljivka WHERE id = '$this->spremenljivka'");
				$r = mysqli_fetch_assoc($s);
				print_r($r);*/
				
			} else {
				$s = sisplet_query("UPDATE srv_spremenljivka SET ranking_k='$_POST[ranking_k]' WHERE id = '$this->spremenljivka'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
			
		}
		
		// slider pri multinumberju
		if ( isset($_POST['ranking_k']) && $row['tip']=='20' ) {
			
			if ($row['num_useMin'] == '0' || $row['num_useMax']=='0') {
				
				$s = sisplet_query("UPDATE srv_spremenljivka SET ranking_k='$_POST[ranking_k]', vsota_min='0', vsota_limit='100', num_useMin='1', num_useMax='1', grids='1' WHERE id = '$this->spremenljivka'");
				//$s = sisplet_query("UPDATE srv_spremenljivka SET ranking_k='$_POST[ranking_k]', vsota_min='0', vsota_limit='0', num_useMin='1', num_useMax='1', grids='1' WHERE id = '$this->spremenljivka'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				$s = sisplet_query("DELETE FROM srv_grid WHERE spr_id='$this->spremenljivka' AND vrstni_red > '1'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			
			} else {
				$s = sisplet_query("UPDATE srv_spremenljivka SET ranking_k='$_POST[ranking_k]' WHERE id = '$this->spremenljivka'");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
			}
			
		}
			
	}
	
	/**
	* manual je, ce rocno pozenemo tracking, sicer se poganja avtomatsko
	* 
	* @param mixed $manual
	*/
    
	public static function vprasanje_tracking($manual = 0) {
		global $global_user_id;
		
		$anketa = (int)$_REQUEST['anketa'];
		$spremenljivka = (int)$_REQUEST['spremenljivka'];
		
		SurveyInfo::getInstance()->SurveyInit($anketa);
		$row = SurveyInfo::getInstance()->getSurveyRow();
		
		if ($row['vprasanje_tracking'] == 0) return;
		if ($row['vprasanje_tracking'] == 2 && $manual==0) return;
		if ($row['vprasanje_tracking'] == 3 && $_GET['silentsave']!='undefined') return;
		
		$branching = new Branching($anketa);
		$tracking_id = $branching->nova_spremenljivka(-1, 0, 0, $spremenljivka);
				
		sisplet_query("INSERT INTO srv_spremenljivka_tracking (ank_id, spr_id, tracking_id, tracking_uid, tracking_time) VALUES ('$anketa', '$spremenljivka', '$tracking_id', '$global_user_id', NOW())");	
	}
	
	function ajax_grid_multiple_add () {
		global $lang;
		
		echo '<p><b>'.$lang['srv_gridmultiple_choose'].'</b></p>';
		
		echo '<p><label onclick="grid_multiple_addnew(\''.$this->spremenljivka.'\', \'6\');"><span class="sprites radio3"></span> '.$lang['srv_vprasanje_tip_1'].'</label></p>';
		echo '<p><label onclick="grid_multiple_addnew(\''.$this->spremenljivka.'\', \'16\');"><span class="sprites checkbox3"></span> '.$lang['srv_vprasanje_tip_2'].'</label></p>';
		echo '<p><label onclick="grid_multiple_addnew(\''.$this->spremenljivka.'\', \'19\');"><span class="sprites text"></span> '.$lang['srv_vprasanje_tip_21'].'</label></p>';
		echo '<p><label onclick="grid_multiple_addnew(\''.$this->spremenljivka.'\', \'20\');"><span class="sprites text"></span> '.$lang['srv_vprasanje_tip_7'].'</label></p>';
		echo '<p><label onclick="grid_multiple_addnew(\''.$this->spremenljivka.'\', \'19\', \'1\');"><span class="sprites text"></span> '.$lang['srv_vprasanje_datum'].'</label></p>';
		
		echo '<a onclick="$(\'#vrednost_edit\').html(\'\').hide(); return false;" href="#" style="position:absolute; right:10px; bottom:10px">'.$lang['srv_zapri'].'</a>';
	}
	
	function ajax_grid_multiple_addnew () {
		global $lang;
		
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
		
		$tip = $_POST['tip'];
		$podtip = $_POST['podtip'];

		$b = new Branching($this->anketa);
		$spr_id = $b->nova_spremenljivka(-2, 0, 0);
		
		if ($tip == 19)
			$vr = 1;
		else
			$vr = 3;
		
		$s = sisplet_query("DELETE FROM srv_grid WHERE spr_id='$spr_id' AND vrstni_red > '$vr'");
		$s = sisplet_query("UPDATE srv_spremenljivka SET grids='$vr' WHERE id='$spr_id'");
		
		$sql = sisplet_query("SELECT MAX(vrstni_red) AS max FROM srv_grid_multiple WHERE ank_id='$this->anketa' AND parent='$this->spremenljivka'");
		$row = mysqli_fetch_array($sql);
		$vrstni_red = $row['max'] + 1;
		
		$sql = sisplet_query("INSERT INTO srv_grid_multiple (ank_id, parent, spr_id, vrstni_red) VALUES ('$this->anketa', '$this->spremenljivka', '$spr_id', '$vrstni_red')");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		
		Vprasanje::change_tip($spr_id, $tip);
		
		if ($podtip > 0) {
			$v = new Vprasanje($this->anketa);
			$v->spremenljivka = $spr_id;
			
			if ($tip == 19) {
				if ($podtip == 1) {
					$v->set_datum();		// multigrid datum
				}
			}
		}
		
		$this->repare_grid_multiple($this->spremenljivka);
	}
	
	/**
	* urejanje pod-spremenljivki v multiple gridu
	* 
	*/
	function ajax_grid_multiple_edit () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		
		echo '<p><b>'.$lang['srv_analiza_opisne_subquestion'].'</b></p>';
		
		echo '<p><span class="title">'.$lang['srv_odgovorov'].':</span> ';
		echo '<span class="content"><select name="multi_grids_count" id="multi_grids_count" onChange="change_selectbox_size(\'' . $row['id'] . '\', $(this).val(), \'' . $lang['srv_select_box_vse'] . '\');">';
		//echo '<span class="content"><select name="multi_grids_count" id="multi_grids_count" onchange="">';
		// Vedno imamo najmanj 2 grida (drugace so stvari cudne v analizah) - namesto 1 se uporabi navaden radio tip vprasanja
		// Pri number sliderju se rabi 1 (mogoče še kje - npr checkbox itd.... ) analize morajo delati tudi v tem primeru :P
		for ($i=1; $i<=20; $i++)
			echo '<option value="'.$i.'"'.($row['grids']==$i?' selected':'').'>'.$i.'</option>';
		
		echo '</select></span>';
		echo '</p>';
		
		if ($row['tip'] == 6) {
		
			echo '<p><span class="title">'.$lang['srv_orientacija'].': </span>';
			//echo '<span class="content"><select id="spremenljivka_podtip" name="enota" onChange="show_selectbox_size(\'' . $row['id'] . '\', this.value);">';
			echo '<span class="content"><select id="spremenljivka_podtip" name="enota" onChange="show_selectbox_size(\'' . $row['id'] . '\', this.value, \'' . $row['tip'] . '\');">';
			//echo '<span class="content"><select id="spremenljivka_podtip" name="enota">';
			echo '<option value="0" '.(($row['enota'] == 0) ? ' selected="true" ' : '').'>'.$lang['srv_classic'].'</option>';		
			echo '<option value="2" '.(($row['enota'] == 2) ? ' selected="true" ' : '').'>'.$lang['srv_dropdown'].'</option>';
			echo '<option value="6" '.(($row['enota'] == 6) ? ' selected="true" ' : '').'>'.$lang['srv_select-box_radio'].'</option>';
			echo '</select>';
			echo '</span></p>';
			
			$this->edit_selectbox_size ();
		}
		
		if ($row['tip'] == 16) {
		
			echo '<p><span class="title">'.$lang['srv_orientacija'].': </span>';
			//echo '<span class="content"><select id="spremenljivka_podtip" name="enota">';
			//echo '<span class="content"><select id="spremenljivka_podtip" name="enota" onChange="show_selectbox_size(\'' . $row['id'] . '\', this.value);">';
			echo '<span class="content"><select id="spremenljivka_podtip" name="enota" onChange="show_selectbox_size(\'' . $row['id'] . '\', this.value, \'' . $row['tip'] . '\');">';
			echo '<option value="0" '.(($row['enota'] == 0) ? ' selected="true" ' : '').'>'.$lang['srv_classic'].'</option>';		
			echo '<option value="6" '.(($row['enota'] == 6) ? ' selected="true" ' : '').'>'.$lang['srv_select-box_check'].'</option>';
			echo '</select>';
			echo '</span></p>';
			
			$this->edit_selectbox_size ();
		}
		
		if ($row['tip'] == 19) {
		
			echo '<p>';
			
			$taWidth = ($spremenljivkaParams->get('taWidth') ? $spremenljivkaParams->get('taWidth') : -1);
			$taHeight = ($spremenljivkaParams->get('taHeight') ? $spremenljivkaParams->get('taHeight') : 1);
            

            // Sirina polja
            echo $lang['srv_textAreaWidth'].': ';

			$size = $row['grids'];
			$missing_count = 0;
			# če imamo missinge size povečamo za 1 + številomissingov
			$sql_grid_mv = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='".$this->spremenljivka."' AND other != 0");
			$missing_count  = mysqli_num_rows($sql_grid_mv);
			if ($missing_count > 0) {
				$size += $missing_count + 1;
			}

			echo '<select name="taWidth" id="multi_taWidth">';
			$maxWidth = round(50 / $size);
			$maxWidth = 10;
			
			echo '<option value="-1"' . ($taWidth == -1 ? ' selected="true"' : '') . '>'.$lang['default'].'</option>';
			for($i=1; $i<$maxWidth; $i++){
				echo '<option value="'.$i.'"' . ($taWidth == $i ? ' selected="true"' : '') . '>' . $i . '</option>';
			}
			echo '</select>';
			
            
            // Visina polja
			echo '<span class="spaceLeft">'.$lang['srv_textAreaHeight'].': <select name="taHeight" id="multi_taHeight">';
			$maxHeight = 10;
			
			for($i=1; $i<=$maxHeight; $i++){
				echo '<option value="'.$i.'"' . ($taHeight == $i ? ' selected="true"' : '') . '>' . $i . '</option>';
            }
            echo '</select></span>';
            
			echo '</p>';	
		}

		if ($row['tip'] == 20) {

            $this->edit_number();
		}
        
        // Prikaz podtabele glede na tip respondenta (admin, manager...)
		echo '<p>';
		echo '<span class="title">'.$lang['srv_visible_dostop'].': </span>';
		echo '<span class="content"><select name="dostop" id="spremenljivka_dostop">';
		echo '<option value="4"'.($row['dostop']==4?' selected':'').'>'.$lang['see_everybody'].'</option>';
		echo '<option value="3"'.($row['dostop']==3?' selected':'').'>'.$lang['see_registered'].'</option>';
		echo '<option value="2"'.($row['dostop']==2?' selected':'').'>'.$lang['see_member'].'</option>';
		echo '<option value="1"'.($row['dostop']==1?' selected':'').'>'.$lang['see_manager'].'</option>';
		echo '<option value="0"'.($row['dostop']==0?' selected':'').'>'.$lang['see_admin'].'</option>';
		echo '</select></span>';
		echo '</p>';

        // Ce imamo datum
        if ($row['tip'] == 19){
		    $is_datum = $spremenljivkaParams->get('multigrid-datum');
		
		    if ($is_datum == 1)
			    $this->edit_date_range();
        }

        // Sirina stolpca
        $grid_width = $spremenljivkaParams->get('gridmultiple_width');
        echo '<p>';
		echo '<span class="title">'.$lang['srv_gridmultiple_width'].': </span>';
		echo '<span class="content"><select name="gridmultiple_width" id="gridmultiple_width">';
		echo '<option value="0"'.($grid_width==0?' selected':'').'>'.$lang['default'].'</option>';
		echo '<option value="10"'.($grid_width==10?' selected':'').'>10%</option>';
		echo '<option value="20"'.($grid_width==20?' selected':'').'>20%</option>';
		echo '<option value="30"'.($grid_width==30?' selected':'').'>30%</option>';
		echo '<option value="40"'.($grid_width==40?' selected':'').'>40%</option>';
		echo '<option value="50"'.($grid_width==50?' selected':'').'>50%</option>';
		echo '<option value="60"'.($grid_width==60?' selected':'').'>60%</option>';
		echo '</select></span>';
		echo '</p>';
        
        
		echo '<br />';


        echo '<span class="buttonwrapper spaceLeft floatRight">
		<a class="ovalbutton ovalbutton_orange" onclick="grid_multiple_save(\''.$this->spremenljivka.'\'); $(\'#vrednost_edit\').html(\'\').hide(); return false;" href="#">
		<span>'.$lang['srv_potrdi'].'</span>
		</a>
        </span>';
        
		echo '<span class="buttonwrapper spaceLeft floatRight">
		<a class="ovalbutton ovalbutton_gray" onclick="$(\'#fade\').fadeOut(\'slow\'); $(\'#vrednost_edit\').html(\'\').hide(); return false;" href="#">
		<span>'.$lang['srv_zapri'].'</span>
		</a>
		</span>';
		
		echo '<span class="buttonwrapper spaceLeft floatRight">
		<a class="ovalbutton ovalbutton_gray" onclick="brisi_spremenljivko(\''.$this->spremenljivka.'\', undefined, \'0\'); $(\'#fade\').fadeOut(\'slow\'); $(\'#vrednost_edit\').html(\'\').hide(); return false;" href="#">
		<span>'.$lang['srv_brisispremenljivko'].'</span>
		</a>
		</span>';
		
	}
	
	/**
	* popravi srv_vrednost tabele za childe multiple grida (v urejanju se vedno ureja parenta)
	* 
	* @param mixed $parent
	*/
	function repare_grid_multiple($parent) {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
		
		$sql_parent = sisplet_query("SELECT naslov, naslov2, variable, variable_custom, other FROM srv_vrednost WHERE spr_id='$parent' ORDER BY vrstni_red");
		
		$sql = sisplet_query("SELECT spr_id FROM srv_grid_multiple WHERE ank_id='$this->anketa' AND parent='$parent' ORDER BY vrstni_red");
		while ($row = mysqli_fetch_array($sql)) {
			
			$sql_grid = sisplet_query("SELECT id, naslov, naslov2, variable, variable_custom FROM srv_vrednost WHERE spr_id = '$row[spr_id]' ORDER BY vrstni_red");
			
			// dodamo manjkajoce vrstice
			if (mysqli_num_rows($sql_grid) < mysqli_num_rows($sql_parent)) {
				
				for ($i=mysqli_num_rows($sql_grid); $i<mysqli_num_rows($sql_parent); $i++) {
					$this->vrednost_new('', 0, null, $row['spr_id']);
				}
			
			// pobrisemo odvecne vrstice
			} elseif (mysqli_num_rows($sql_grid) > mysqli_num_rows($sql_parent)) {
				
				$limit = mysqli_num_rows($sql_grid) - mysqli_num_rows($sql_parent);
				$s = sisplet_query("DELETE FROM srv_vrednost WHERE spr_id='$row[spr_id]' ORDER BY vrstni_red DESC LIMIT $limit");
				if (!$s) echo mysqli_error($GLOBALS['connect_db']);
				
			}
			
			// popravimo napise
			mysqli_data_seek($sql_parent, 0);
			mysqli_data_seek($sql_grid, 0);
			
			while ($row_grid = mysqli_fetch_array($sql_grid) ) {
				
				$row_parent = mysqli_fetch_array($sql_parent);
				
				if ($row_grid['naslov'] != $row_parent['naslov']
					|| $row_grid['naslov2'] != $row_parent['naslov2']
					|| $row_grid['variable'] != $row_parent['variable']
					|| $row_grid['variable_custom'] != $row_parent['variable_custom']
				) {           
                    $purifier = New Purifier();
                    $naslov = $purifier->purify_DB($row_parent['naslov']);
                    $naslov2 = $purifier->purify_DB($row_parent['naslov2']);

					$s = sisplet_query("UPDATE srv_vrednost SET naslov='$naslov', naslov2='$naslov2', variable='$row_parent[variable]', variable_custom='$row_parent[variable_custom]', other='$row_parent[other]' WHERE id='$row_grid[id]'");
					if (!$s) echo mysqli_error($GLOBALS['connect_db']);				
				}
				
			}
			
			// Nastavimo ustrezne variable
			Common::prestevilci($row['spr_id']);
		}
	}
	
	function ajax_hotspot_vrednost_new () {
		Common::getInstance()->Init($this->anketa);
    	Common::getInstance()->updateEditStamp();
    	
		global $lang;
		
		$naslov = '';

		$v = new Vprasanje($this->anketa);
		$v->spremenljivka = $this->spremenljivka;
		$vrednost = $v->vrednost_new($naslov /*, $other, $mv*/);
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		
		if($row['tip'] != 27){	//ce ni heatmap, torej je image hotspot
			Common::prestevilci($this->spremenljivka);
		}
		
		echo $vrednost;
		
		Vprasanje::vprasanje_tracking();
	}

	function ajax_get_hotspot_image() {
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		$hotspot_image = ($spremenljivkaParams->get('hotspot_image') ? $spremenljivkaParams->get('hotspot_image') : "");
	
		echo $hotspot_image;
	}
	
	function ajax_get_next_hotspot_vrednost() {

        $spr_id = $_POST['spr_id'];

		// Poberi podatke o na zadnje vnesenih obmocjih
        $sqlR = sisplet_query("SELECT vre_id FROM srv_hotspot_regions WHERE spr_id = $spr_id ORDER BY region_index");
        
        // ce je kaj v bazi
        if(mysqli_num_rows($sqlR) != 0){	
			$sql_vre_id_middle = "";
            
            while($rowR = mysqli_fetch_array($sqlR)){
				$temp = $rowR['vre_id'];
				$sql_vre_id_middle = $sql_vre_id_middle . "AND id != $temp ";	//stavek z id-ji prisotnih obmocij
            }	
            		
			$sql_vre_id_begin = "SELECT id FROM srv_vrednost WHERE spr_id = $spr_id ";
			$sql_vre_id_end = " ORDER BY vrstni_red LIMIT 1";
			$sql_vre_id_whole = $sql_vre_id_begin.''.$sql_vre_id_middle.''.$sql_vre_id_end;
			$sql_vre_id = sisplet_query($sql_vre_id_whole);		
        }
        else{	//ce ni nicesar bazi
			$sql_vre_id = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = $spr_id ORDER BY vrstni_red LIMIT 1");
		}
		
		
		$row_vre_id = mysqli_fetch_assoc($sql_vre_id);
 		if(mysqli_num_rows($sql_vre_id) != 0){	//ce je kaj v bazi
			$next_vrednost = $row_vre_id['id'];
		}
		
		// prestej koliko je v bazi vrednosti in obmocij
		$sqlc = sisplet_query("SELECT COUNT(*) AS count FROM srv_vrednost WHERE spr_id=$spr_id");
		$rowc = mysqli_fetch_assoc($sqlc);
		$vre_num = $rowc['count'];	//stevilo vrednosti
		$sqlcr = sisplet_query("SELECT COUNT(*) AS count FROM srv_hotspot_regions WHERE spr_id=$spr_id");
		$rowcr = mysqli_fetch_assoc($sqlcr);
		$reg_num = $rowcr['count'];	//stevilo obmocij
		
		if ( mysqli_num_rows($sqlR) != 0 && ($reg_num == $vre_num) ){	//ce je stevilo vrednosti enako stevilu obmocij
			$next_vrednost = "";	//vrednost naj bo prazna, tako, da bomo kasneje dodali novo vrednost v bazo
		}
		
		echo $next_vrednost;	//vrni ustrezno vrednost
	}
	
	function ajax_hotspot_get_region_name() {
		$spr_id = $_POST['spr_id'];
		$vrednost = $_POST['vrednost'];
        
        //poberi podatke o na trenutnem obmocju
		$sqlR = sisplet_query("SELECT region_name FROM srv_hotspot_regions WHERE spr_id = $spr_id AND vre_id = $vrednost");
		$rowR = mysqli_fetch_assoc($sqlR);
        
        $region_name = $rowR['region_name'];
	
		echo $region_name;
	}
	
	
	//primerjaj stevilo vnosov v srv_vrednost in srv_hotspot_regions za trenutno spremenljivko in preuredi srv_vrednost, ce je to potrebno
	function ajax_get_hotspot_stevilo_vnosov(){
		$spr_id = $_POST['spremenljivka'];
		
		//preveri, ce je kaksno obmocje shranjeno v bazi
		$sqlR = sisplet_query("SELECT vre_id, spr_id, region_name, variable, vrstni_red  FROM srv_hotspot_regions WHERE spr_id = $spr_id");
		$enako_stevilo_vnosov_za_hotspot = 1;
		
		// ce se je uredilo obmocja, presaltalo na drugo postavitev in tam brisalo vrednosti (srv_vrednost), je potrebno restorat izbrisane odgovore iz srv_hotspot_regions v srv_vrednost
		// prestej koliko je v bazi vrednosti in obmocij
		$sqlc = sisplet_query("SELECT COUNT(*) AS count FROM srv_vrednost WHERE spr_id=$spr_id");
		$rowc = mysqli_fetch_assoc($sqlc);
		$vre_num = $rowc['count'];	//stevilo vrednosti
		$sqlcr = sisplet_query("SELECT COUNT(*) AS count FROM srv_hotspot_regions WHERE spr_id=$spr_id");
		$rowcr = mysqli_fetch_assoc($sqlcr);
		$reg_num = $rowcr['count'];	//stevilo obmocij
		
		if ( mysqli_num_rows($sqlR) != 0 && ($reg_num != $vre_num) ){	//ce imamo nekaj obmocij in ce je stevilo vrednosti razlicno od stevila obmocij
			$enako_stevilo_vnosov_za_hotspot = 0;
			
			//preglej obmocja in ustrezno uredi srv_vrednost
			while($rowR = mysqli_fetch_array($sqlR)){
				$vre_id = $rowR['vre_id'];
				$spr_id = $rowR['spr_id'];
				$naslov = $rowR['region_name'];
				$variable = $rowR['variable'];
				$vrstni_red = $rowR['vrstni_red'];
				$sqlVrednost = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = $spr_id AND id = $vre_id");
				
				//ce ni nicesar v srv_vrednost s tem id-jem, dodaj ustrezno vrednost
				if(mysqli_num_rows($sqlVrednost) == 0){
					$sql = sisplet_query("INSERT INTO srv_vrednost (id, spr_id, naslov, variable, vrstni_red) VALUES ($vre_id, '$spr_id', '$naslov', '$variable', '$vrstni_red')");					
				}else{	//drugace, posodobi informacije o ostalih obmocijh
					$sql = sisplet_query("UPDATE srv_vrednost SET variable = '$variable', vrstni_red = '$vrstni_red' WHERE spr_id='$spr_id' AND id = $vre_id");
				}				
			}
			
			//preglej srv_vrednost in ustrezno izbrisi vrednosti brez obmocja, ker se jih je dodalo na roke, ko je bila druga postavitev (ce niso missingi)
			$sqlV = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = $spr_id AND other = 0");
			while($rowV = mysqli_fetch_array($sqlV)){
				$vre_id_V = $rowV['id'];
				$sqlObmocje = sisplet_query("SELECT id FROM srv_hotspot_regions WHERE spr_id = $spr_id AND vre_id = $vre_id_V");
                
                //ce ni obmocja s tem id-jem, izbrisi iz srv_vrednost vrednost s tem id-jem
				if(mysqli_num_rows($sqlObmocje) == 0){
					$sql = sisplet_query("DELETE FROM srv_vrednost WHERE id = '$vre_id_V' AND spr_id='$spr_id'");
				}
			}		
		}
	
		echo $enako_stevilo_vnosov_za_hotspot;
	}
	
	function ajax_hotspot_region_cancel(){
		$spr_id = $_POST['spr_id'];
		$vre_id = $_POST['vre_id'];

        $sqlR = sisplet_query("SELECT id FROM srv_hotspot_regions WHERE spr_id = $spr_id AND vre_id = '$vre_id'");

        // Ce ni obmocja v bazi
        if( mysqli_num_rows($sqlR) == 0){	
			$sql = sisplet_query("DELETE FROM srv_vrednost WHERE id = '$vre_id' AND spr_id='$spr_id'");	//brisi vrednost, ki se je skenslalo
		}		
	}
	
	function edit_trak_tabela(){
		global $lang;
		global $admin_type;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		$diferencial_trak = ($spremenljivkaParams->get('diferencial_trak') ? $spremenljivkaParams->get('diferencial_trak') : 0); //za checkbox
		$disable_diferencial_trak_hidden = ($diferencial_trak == 1) ? 'disabled' : '';
		$diferencial_trak_starting_num = ($spremenljivkaParams->get('diferencial_trak_starting_num') ? $spremenljivkaParams->get('diferencial_trak_starting_num') : 0);
		$trak_num_of_titles = ($spremenljivkaParams->get('trak_num_of_titles') ? $spremenljivkaParams->get('trak_num_of_titles') : 0);
        
        //ce je diferencial ali klasicna tabela
		if($row['enota'] == 1 || $row['enota'] == 0){ 
			$display_trak = 'block';
			if($diferencial_trak == 1){
				$display_trak_starting_num = 'block';
            }
            else{
				$display_trak_starting_num = 'none';
			}		
        }
        else{
			$display_trak = 'none';
			$display_trak_starting_num = 'none';
		}

		//koda za izris moznosti za vklop/izklop traku
		echo '<div class="diferencial_trak_class" style="display: '.$display_trak.'">';
		echo '<p><span class="title" ><label for="diferencial_trak_'.$this->spremenljivka.'">'.$lang['srv_diferencial_trak'].':</label></span>';
		echo '<span class="content">';
		echo '<input type="checkbox" value="1" name="diferencial_trak" '.( $diferencial_trak == 1 ? ' checked="checked"' : '') .' onChange="diferencial_trak_checkbox_prop('.$this->spremenljivka.', '.$row['grids'].');" id="diferencial_trak_'.$this->spremenljivka.'">';
		echo '<input '.$disable_diferencial_trak_hidden.' type="hidden" value="0" name="diferencial_trak" id="diferencial_trak_hidden_'.$this->spremenljivka.'">';
		echo '</span></p>';
		echo '</div>';
		//koda za izris moznosti za vklop/izklop traku - konec
		
		//koda za izris polja za vnos zacetne stevilke traku
		echo '<div class="diferencial_trak_starting_num_class_'.$this->spremenljivka.'" style="display: '.$display_trak_starting_num.'"><p><span class="title">' . $lang['srv_diferencial_trak_starting_num'] . ':</span> <span class="content"><input type="text" name="diferencial_trak_starting_num" id="diferencial_trak_starting_num_'.$this->spremenljivka.'"  value="' . $diferencial_trak_starting_num . '" size="8" onChange="diferencial_trak_change_values('.$this->spremenljivka.', '.$row['grids'].');"></input></span></p></div>';
		
		echo '<div class="trak_num_of_titles_class" style="display: '.$display_trak_starting_num.'">';
		echo '<p><span class="title" >'.$lang['srv_trak_num_of_titles'].':</span>';
		echo '<span class="content"><select name="trak_num_of_titles" id="trak_num_of_titles">';
		
		$deljivaStevila = [];
		$indeksDeljivihStevil = 0;
		
		for($i = 2; $i<=$row['grids']; $i++){
			if(($row['grids']%$i == 0)&&$i!=2){
				$deljivaStevila[$indeksDeljivihStevil] = $i;
				$indeksDeljivihStevil++;
            }
            else if(($row['grids']%$i == 2)&&$i!=2){
				$deljivaStevila[$indeksDeljivihStevil] = $i;
				$indeksDeljivihStevil++;				
            }
            elseif($i == 2){
				$deljivaStevila[$indeksDeljivihStevil] = $i;
				$indeksDeljivihStevil++;
			}
		}
		
		for ($i=0; $i< sizeof($deljivaStevila); $i++){	//napolni dropdown z ustreznimi stevili vnosov
			echo '<option value="'.$deljivaStevila[$i].'"'.($trak_num_of_titles == $deljivaStevila[$i] ? ' selected="true"' : '') . '>'.$deljivaStevila[$i].'</option>';
		}
		echo '</select></span>';
		echo '</p>';
		echo '</div>';
	}
	
	//posodobi skrite vrednosti odgovorov za diferencial trak
	function ajax_diferencial_trak_skrite_vrednosti($spr_id, $num_grids, $diferencial_trak_starting_num){
		if(isset ($_POST['spr_id'])){
			$spr_id = $_POST['spr_id'];
		}
		if(isset ($_POST['num_grids'])){
			$num_grids = $_POST['num_grids'];
		}
		if(isset ($_POST['diferencial_trak_starting_num'])){
			$diferencial_trak_starting_num = $_POST['diferencial_trak_starting_num'];
		}

		$new_vrednosti_odgovorov = array();
		$new_vrednosti_odgovorov[0] = $diferencial_trak_starting_num;

		for($i = 0; $i < $num_grids; $i++){	//iz zacetne rocno vpisane vrednosti zgeneriraj se ostale glede na izbrano stevilo odgovorov
			$id = $i + 1;
			$sql = sisplet_query("UPDATE srv_grid SET variable = '$new_vrednosti_odgovorov[$i]' WHERE spr_id='$spr_id' AND id = $id");
			$new_vrednosti_odgovorov[$i + 1] = $new_vrednosti_odgovorov[$i] + 1;
		}
	}
        
    /**
     * What is input type of this multilocation - 26 - 2 (marker, polyline, polygon)
     * @return {string} type of input for multilocation
     */
    function ajax_get_input_type_map(){
        $spr_id = $_POST['spr_id'];
        
        $row = Cache::srv_spremenljivka($spr_id);
        $newParams = new enkaParameters($row['params']);
        $input = $newParams->get('multi_input_type') ? $newParams->get('multi_input_type') : 'marker';

        echo $input;
    }
	
	function edit_drag_and_drop_new_look(){
		global $lang;
		global $admin_type;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		$display_drag_and_drop_new_look = ($spremenljivkaParams->get('display_drag_and_drop_new_look') ? $spremenljivkaParams->get('display_drag_and_drop_new_look') : 0); //da bo po default-u izbrana moznost "okivirov"
		
		if($row['enota'] == 9){	//ce je drag and drop
			$display_new_look_option = 'block';		
        }
        else{
			$display_new_look_option = 'none';
		}

		// koda za dropdown za izbiro oblike okvirjev ali skatel
		echo '<div class="drag_and_drop_new_look_class" style="display: '.$display_new_look_option.'">';
		echo '<p><span class="title" >'.$lang['srv_drag_and_drop_new_look_option'].':</span>';
		echo '<span class="content"><select name="display_drag_and_drop_new_look" id="drag_and_drop_new_look_'.$this->spremenljivka.'" onChange="drag_and_drop_new_look_checkbox_prop('.$this->spremenljivka.');">';
		echo '<option value="0"'.($display_drag_and_drop_new_look=='0'?' selected':'').'>'.$lang['srv_drag_and_drop_new_look_option1'].'</option>';
		echo '<option value="1"'.($display_drag_and_drop_new_look=='1'?' selected':'').'>'.$lang['srv_drag_and_drop_new_look_option2'].'</option>';
		echo '</select></span></p>';
		echo '</div>';
	}
	
	/**
	* editiranje prilagajanja label stolpcev z radio buttoni
	*/
	function edit_column_labels () {
		global $lang;
		global $default_grid_values;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$rowA = SurveyInfo::getInstance()->getSurveyRow();
		
		$spremenljivkaParams = new enkaParameters($row['params']);
		$diferencial_trak = ($spremenljivkaParams->get('diferencial_trak') ? $spremenljivkaParams->get('diferencial_trak') : 0); //za checkbox
		$custom_column_label_option = ($spremenljivkaParams->get('custom_column_label_option') ? $spremenljivkaParams->get('custom_column_label_option') : 1);

		$display = ( ( $row['tip'] == 6 && ($row['enota'] == 0 || $row['enota'] == 1) ) || ( $row['tip'] == 16 && ($row['enota'] == 0 || $row['enota'] == 1) ) ) && ($diferencial_trak == 0) ? '' : 'style="display:none;"';
		
	
		echo '<div class="drop_custom_column_labels" '.$display.'>';
        
        echo '<p><span class="title" >'.$lang['srv_custom_column_labels_presentation'].':</span>';
        
		echo '<span class="content"><select name="custom_column_label_option" id="custom_column_label_option_'.$row['id'].'" >';
		echo '<option value="1"'.($custom_column_label_option=='1'?' selected':'').'>'.$lang['srv_custom_column_labels_o1'].'</option>';
		echo '<option value="2"'.($custom_column_label_option=='2'?' selected':'').'>'.$lang['srv_custom_column_labels_o2'].'</option>';
		echo '<option value="3"'.($custom_column_label_option=='3'?' selected':'').'>'.$lang['srv_custom_column_labels_o3'].'</option>';
		echo '</select></span>';
        
        echo '</p>';
        
        echo '</div>';
    }
    
    // Nastavitev za ponovitev header vrstice v gridu
    function edit_grid_repeat_header () {
		global $lang;
		
		$row = Cache::srv_spremenljivka($this->spremenljivka);
        $spremenljivkaParams = new enkaParameters($row['params']);
        
		$grid_repeat_header = ($spremenljivkaParams->get('grid_repeat_header') ? $spremenljivkaParams->get('grid_repeat_header') : 0);

		echo '<div class="grid_repeat_header">';
        
        echo '<p><span class="title" >'.$lang['srv_grid_repeat_header'].':</span>';
        
		echo '<span class="content"><select name="grid_repeat_header" id="grid_repeat_header_'.$row['id'].'" >';
		echo '<option value="0"'.($grid_repeat_header=='0'?' selected':'').'>'.$lang['srv_grid_repeat_header_0'].'</option>';
		echo '<option value="5"'.($grid_repeat_header=='5'?' selected':'').'>'.$lang['srv_grid_repeat_header_5'].'</option>';
		echo '<option value="10"'.($grid_repeat_header=='10'?' selected':'').'>'.$lang['srv_grid_repeat_header_10'].'</option>';
		echo '<option value="15"'.($grid_repeat_header=='15'?' selected':'').'>'.$lang['srv_grid_repeat_header_15'].'</option>';
		echo '<option value="20"'.($grid_repeat_header=='20'?' selected':'').'>'.$lang['srv_grid_repeat_header_20'].'</option>';
		echo '</select></span>';
        
        echo '</p>';
        
        echo '</div>';
    }
    
	
	// nastavitve za heatmap
	function edit_heatmap_settings(){
		global $lang;
		global $admin_type;
		global $default_grid_values; //privzete default vmesne opisne labele
		
		SurveySetting::getInstance()->Init($this->anketa);
		SurveyInfo::getInstance()->SurveyInit($this->anketa);
		$row = Cache::srv_spremenljivka($this->spremenljivka);
		$spremenljivkaParams = new enkaParameters($row['params']);
		$hotspot_image = ($spremenljivkaParams->get('hotspot_image') ? $spremenljivkaParams->get('hotspot_image') : "");
		$hotspot_region_visibility_option = ($spremenljivkaParams->get('hotspot_region_visibility_option') ? $spremenljivkaParams->get('hotspot_region_visibility_option') : 0);
		$hotspot_tooltips_option = ($spremenljivkaParams->get('hotspot_tooltips_option') ? $spremenljivkaParams->get('hotspot_tooltips_option') : 0);
		$hotspot_region_color = ($spremenljivkaParams->get('hotspot_region_color') ? $spremenljivkaParams->get('hotspot_region_color') : "");
		
		$heatmap_num_clicks = ($spremenljivkaParams->get('heatmap_num_clicks') ? $spremenljivkaParams->get('heatmap_num_clicks') : 1);
		$heatmap_show_clicks = ($spremenljivkaParams->get('heatmap_show_clicks') ? $spremenljivkaParams->get('heatmap_show_clicks') : 0); //za checkbox
		$disable_heatmap_show_clicks_hidden = ($heatmap_show_clicks == 1) ? 'disabled' : '';
		//$heatmap_region_settings = ($spremenljivkaParams->get('heatmap_region_settings') ? $spremenljivkaParams->get('heatmap_region_settings') : 0);
		$heatmap_click_color = ($spremenljivkaParams->get('heatmap_click_color') ? $spremenljivkaParams->get('heatmap_click_color') : "");
		$heatmap_click_size = ($spremenljivkaParams->get('heatmap_click_size') ? $spremenljivkaParams->get('heatmap_click_size') : 5);
		$heatmap_click_shape = ($spremenljivkaParams->get('heatmap_click_shape') ? $spremenljivkaParams->get('heatmap_click_shape') : 1);
		
		$heatmap_show_counter_clicks = ($spremenljivkaParams->get('heatmap_show_counter_clicks') ? $spremenljivkaParams->get('heatmap_show_counter_clicks') : 0); //za checkbox za stevec
		$disable_heatmap_show_counter_clicks_hidden = ($heatmap_show_counter_clicks == 1) ? 'disabled' : '';
		
		if($heatmap_num_clicks > 1){
			$enableHeatMapClickCounter = "display: block";
		}else{
			$enableHeatMapClickCounter = "display: none";
		}
		
		if($heatmap_show_clicks == 1)
		{
			$heatmap_clicks_settings_display = 'style=""';
		}else
		{
			$heatmap_clicks_settings_display = 'style="display:none"';
		}
		
		
		$spr_id = $row['id'];
		//preveri, ce je kaksno obmocje shranjeno v bazi
		//$sqlR = sisplet_query("SELECT * FROM srv_hotspot_regions WHERE spr_id = $spr_id");
		$sqlR = sisplet_query("SELECT region_index, vre_id, region_name FROM srv_hotspot_regions WHERE spr_id = $spr_id");
		//$rowR = mysqli_fetch_array($sql);

		if($row['tip'] == 1 || $row['tip'] == 2){
			$enota_orientation = $row['orientation'];			
		}else if($row['tip'] == 6){
			$enota_orientation = $row['enota'];
		}
 		?>
		<script>
			$(document).ready(function(){
				//show_hot_spot_settings_4Heatmap (<?=$row['id']?>, <?=$row['tip']?>, '<?=$hotspot_image?>', <?=$heatmap_region_settings?>);
				show_hot_spot_settings_4Heatmap (<?=$row['id']?>, <?=$row['tip']?>, '<?=$hotspot_image?>');
				init_colorPicker(<?=$row['id']?>);
			});					
		</script>

		<?
		
		//roleta za izbiro najvecjega stevila klikov na sliko
		echo '<p><span class="title">'.$lang['srv_vprasanje_heatmap_num_clicks'].':<span id="help_hotspot_visibility" class="spaceLeft">'.Help::display('srv_hotspot_visibility').' </span></span>';
		echo '<span class="content"><select id="heatmap_num_clicks_' . $row['id'] . '" spr_id="'.$row['id'].'" name="heatmap_num_clicks" onChange="showHeatMapClickCounter($(this).val(), '.$row['id'].')">';
			echo '<option value="1" '.(($heatmap_num_clicks == 1) ? ' selected="true" ' : '').'>1</option>';
			echo '<option value="2" '.(($heatmap_num_clicks == 2) ? ' selected="true" ' : '').'>2</option>';
			echo '<option value="3" '.(($heatmap_num_clicks == 3) ? ' selected="true" ' : '').'>3</option>';
			echo '<option value="4" '.(($heatmap_num_clicks == 4) ? ' selected="true" ' : '').'>4</option>';
			echo '<option value="5" '.(($heatmap_num_clicks == 5) ? ' selected="true" ' : '').'>5</option>';
			echo '<option value="6" '.(($heatmap_num_clicks == 6) ? ' selected="true" ' : '').'>6</option>';
			echo '<option value="7" '.(($heatmap_num_clicks == 7) ? ' selected="true" ' : '').'>7</option>';
			echo '<option value="8" '.(($heatmap_num_clicks == 8) ? ' selected="true" ' : '').'>8</option>';
			echo '<option value="9" '.(($heatmap_num_clicks == 9) ? ' selected="true" ' : '').'>9</option>';
			echo '<option value="10" '.(($heatmap_num_clicks == 10) ? ' selected="true" ' : '').'>10</option>';
		echo '</select>';
		echo '</span></p>';
		//roleta za izbiro najvecjega stevila klikov na sliko - konec
		
		//checkbox za "Pokazi stevec klikov"		
		echo '<label for="heatmap_show_counter_clicks_'.$this->spremenljivka.'"><div style="'.$enableHeatMapClickCounter.'" class="heatmap_show_counter_clicks_class">';
		echo '<p><span class="title" >'.$lang['srv_vprasanje_heatmap_show_counter_clicks'].':</span>';
		echo '<span class="content">';
		echo '<input type="checkbox" value="1" name="heatmap_show_counter_clicks" '.( $heatmap_show_counter_clicks == 1 ? ' checked="checked"' : '') .' onChange="heatmap_show_counter_clicks_checkbox_prop('.$this->spremenljivka.');" id="heatmap_show_counter_clicks_'.$this->spremenljivka.'">';
		echo '<input '.$disable_heatmap_show_counter_clicks_hidden.' type="hidden" value="0" name="heatmap_show_counter_clicks" id="heatmap_show_counter_clicks_hidden_'.$this->spremenljivka.'">';
		echo '</span></p>';
		echo '</div></label>';
		//checkbox za "Pokazi stevec klikov" - konec	
		
		//checkbox za "Pokazi klike"
		echo '<label for="heatmap_show_clicks_'.$this->spremenljivka.'"><div class="heatmap_show_clicks_class">';
		echo '<p><span class="title" >'.$lang['srv_vprasanje_heatmap_show_clicks'].':</span>';
		echo '<span class="content">';
		echo '<input type="checkbox" value="1" name="heatmap_show_clicks" '.( $heatmap_show_clicks == 1 ? ' checked="checked"' : '') .' onChange="heatmap_show_clicks_checkbox_prop('.$this->spremenljivka.');" id="heatmap_show_clicks_'.$this->spremenljivka.'">';
		echo '<input '.$disable_heatmap_show_clicks_hidden.' type="hidden" value="0" name="heatmap_show_clicks" id="heatmap_show_clicks_hidden_'.$this->spremenljivka.'">';
		echo '</span></p>';
		echo '</div></label>';			
		//checkbox za "Pokazi klike" - konec
		
		//dodatne nastavitve, ce morajo biti kliki vidni
		
		echo '<div id="heatmap_clicks_settings_'.$row['id'].'" '.$heatmap_clicks_settings_display.'>';
			
			//Izbira barve klika
 				if ($heatmap_click_color == '') {
					$value = '#000000';
					echo '<div><p><span class="title">'.$lang['srv_vprasanje_heatmap_clicks_color'].': <a href="#" onclick="$(\'#color-click-'.$row['id'].'\').show(); $(this).parent().hide(); return false;" title="'.$lang['edit4'].'">'.$lang['srv_te_default'].' <span class="faicon edit"></span></a></span></p></div>';
				}else{
					$value = $heatmap_click_color;	
				}
				echo '<div><p><span class="title" id="color-click-'.$row['id'].'" '.($heatmap_click_color==''?'style="display:none;"':'').'>'.$lang['srv_vprasanje_heatmap_clicks_color'].': ';
				echo '<input type="text" id="color-click'.$row['id'].'" class="colorwell auto-save" name="heatmap_click_color" value="'.$value.'" data-id="'.$row['id'].'" data-type="'.$type.'" >';
				echo '</span></p></div>';
				
				echo '<div id="picker"></div>';
			//Izbira barve klika - konec
			
			//Izbira radija/velikosti klika
				echo '<div><p><span>'.$lang['srv_vprasanje_heatmap_clicks_size'].': <input id="heatmapClickSize_'.$row['id'].'" name="heatmap_click_size" type="range" min="2" max="50" step="1" value="'.$heatmap_click_size.'" oninput="UpdateClickSizeSlider(value, '.$row['id'].')"/><output for="heatmapClickSize_'.$row['id'].'" id="heatmapClickSizeValue_'.$row['id'].'">'.$heatmap_click_size.'</output></span></p></div>';
			//Izbira radija/velikosti klika - konec

			//Izbira oblike klika $heatmap_click_shape
				echo '<div><p><span class="title">'.$lang['srv_vprasanje_heatmap_clicks_shape'].': </span>';
				echo '<span class="content"><select id="heatmapClickShape_' . $row['id'] . '" spr_id="'.$row['id'].'" name="heatmap_click_shape" onChange="">';
					echo '<option value="1" '.(($heatmap_click_shape == 1) ? ' selected="true" ' : '').'>'.$lang['srv_vprasanje_heatmap_clicks_shape_1'].'</option>';
					echo '<option value="2" '.(($heatmap_click_shape == 2) ? ' selected="true" ' : '').'>'.$lang['srv_vprasanje_heatmap_clicks_shape_2'].'</option>';
				echo '</select>';
				echo '</span></p></div>';
			//Izbira oblike klika - konec		
		
		
		echo '</div>';
		//dodatne nastavitve, ce morajo biti kliki vidni - konec	
		
 		//fieldset Obmocja - zacasno skrivanje
  		echo '<fieldset id="hot_spot_fieldset_'.$row['id'].'"><legend>'.$lang['srv_hot_spot_regions_menu'].'</legend>';
			if (mysqli_num_rows($sqlR) != 0){
			//pokazi shranjena obmocja
				while ($rowR = mysqli_fetch_array($sqlR)) {					
					echo '<div id="hotspot_region_'.$rowR['region_index'].'" class="hotspot_region"><div id="hotspot_region_name_'.$rowR['region_index'].'" vre_id="'.$rowR['vre_id'].'" region_index = "'.$rowR['region_index'].'" class="hotspot_vrednost_inline" contenteditable="true">'.$rowR['region_name'].'</div><span class="faicon edit2 inline_hotspot_edit_region"></span><span class="faicon delete_circle icon-orange_link inline_hotspot_delete_region"></span><br /></div>';
				}
			}
			//Sporocilo ob odsotnosti slike
			echo '<p id="hotspot_message"><span class="title" >'.$lang['srv_hotspot_message'].'</span></p>';
			//Sporocilo ob odsotnosti slike - konec
			
			//Dodajanje območja - gumb
			echo '<p><span class="title" ><button id="hot_spot_regions_add_button" type="button" onclick=" hotspot_edit_regions('.$row['id'].', 0)">'.$lang['srv_hot_spot_regions'].'</button></span></p>';
				
			//*************************** SKRIVANJE NASTAVITEV OBMOCJA
			$display_regions_menu = 'style="display:none;"';	//skrivanje nastavitev obmocja + v js datotekah
			//***************************
			//div za nastavitve obmocja
			echo '<div id="heatmap_region_settings_'.$row['id'].'" '.$display_regions_menu.'>';				
				//Izbira barve obmocja			
				if ($hotspot_region_color == '') {
					$value = '#000000';
					echo '<span class="title">'.$lang['srv_hotspot_region_color_text'].': <a href="#" onclick="$(\'#color-'.$row['id'].'\').show(); $(this).parent().hide(); return false;" title="'.$lang['edit4'].'">'.$lang['srv_te_default'].' <span class="faicon edit"></span></a></span>';
				}else{
					$value = $hotspot_region_color;	
				}
				
				echo '<span class="title" id="color-'.$row['id'].'" '.($hotspot_region_color==''?'style="display:none;"':'').'>'.$lang['srv_hotspot_region_color_text'].': ';
				echo '<input type="text" id="color'.$row['id'].'" class="colorwell auto-save" name="hotspot_region_color" value="'.$value.'" data-id="'.$row['id'].'" data-type="'.$type.'" >';
				echo '</span>';
				
				echo '<div id="picker"></div>';
				//Izbira barve obmocja - konec
				
				//Regions visibility options
					echo '<p><span class="title">'.$lang['srv_hotspot_visibility_options_title'].':<span id="help_hotspot_visibility" class="spaceLeft">'.Help::display('srv_hotspot_visibility').' </span></span>';
					echo '<span class="title"><select id="hotspot_region_visibility_options_' . $row['id'] . '" spr_id="'.$row['id'].'" name="hotspot_region_visibility_option" onChange="">';
						echo '<option value="0" '.(($hotspot_region_visibility_option == 0) ? ' selected="true" ' : '').'>'.$lang['srv_hotspot_visibility_options_0'].'</option>';
						echo '<option value="1" '.(($hotspot_region_visibility_option == 1) ? ' selected="true" ' : '').'>'.$lang['srv_hotspot_visibility_options_1'].'</option>';
						echo '<option value="2" '.(($hotspot_region_visibility_option == 2) ? ' selected="true" ' : '').'>'.$lang['srv_hotspot_visibility_options_2'].'</option>';
						//echo '<option value="3" '.(($hotspot_region_visibility_option == 3) ? ' selected="true" ' : '').'>'.$lang['srv_hotspot_visibility_options_3'].'</option>';
					echo '</select>';
					echo '</span></p>';	
				//Regions visibility options - konec
				
				//Tooltips options
				if($row['tip'] == 1 || $row['tip'] == 2){	//ce je radio ali checkbox
					$srv_hotspot_tooltip = 'srv_hotspot_tooltip';
				}else if($row['tip'] == 6){
					$srv_hotspot_tooltip = 'srv_hotspot_tooltip_grid';
				}

					echo '<p><span class="title">'.$lang['srv_hotspot_tooltips_options_title'].':<span id="help_hotspot_namig" class="spaceLeft">'.Help::display($srv_hotspot_tooltip).' </span></span>';
					echo '<span class="title"><select id="hotspot_tooltips_options_' . $row['id'] . '" spr_id="'.$row['id'].'" name="hotspot_tooltips_option" onChange="">';
						echo '<option value="0" '.(($hotspot_tooltips_option == 0) ? ' selected="true" ' : '').'>'.$lang['srv_hotspot_tooltips_options_0'].'</option>';
						if($row['tip'] == 1 || $row['tip'] == 2){	//ce je radio ali checkbox
							echo '<option value="1" '.(($hotspot_tooltips_option == 1) ? ' selected="true" ' : '').'>'.$lang['srv_hotspot_tooltips_options_1'].'</option>';
						}
						if($row['tip'] == 6){	//ce je radio grid
							echo '<option value="2" '.(($hotspot_tooltips_option == 2) ? ' selected="true" ' : '').'>'.$lang['srv_hotspot_tooltips_options_2'].'</option>';
						}

					echo '</select>';
					echo '</span></p>';	
				//Tooltips options - konec			
			echo '</div>';
			//div za nastavitve obmocja - konec
			
		echo '</fieldset>';
		//fieldset Obmocja - konec
		
	}
	
}

?>