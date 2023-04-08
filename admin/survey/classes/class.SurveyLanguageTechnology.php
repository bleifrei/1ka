<?php 
ini_set('max_execution_time', 300); //300 seconds = 5 minutes
#set_time_limit(20);
ini_set('default_socket_timeout', 60); // 20 Seconds
/**
 * @author 	Gorazd Veselič
 * @date		July 2014
 *
 */
class SurveyLanguageTechnology {

	protected $anketa;

	protected $response;
	
	protected $settings = array(
				'lt_language' => 'Slo',
				'lt_min_FWD' => 5000,
				'lt_min_nNoM' => 9999,
                'lt_min_vNoM'=> 9999,
				'lt_special_setting'=> false
			);

	public function __construct($anketa) {
		global $lang;
		$this->anketa = $anketa;
		$this->lang = $lang;
	}
	
	public function setup( array $settings) {
		if (isset($settings['lt_language']) && is_string($settings['lt_language']) && in_array(strtolower($settings['lt_language']), array('slo','eng'))) {
			$this->settings['lt_language'] = $settings['lt_language'];
		}
		if (isset($settings['lt_min_FWD']) && is_numeric($settings['lt_min_FWD'])) {
			$this->settings['lt_min_FWD'] = $settings['lt_min_FWD'];
		}
		if (isset($settings['lt_min_nNoM']) && is_numeric($settings['lt_min_nNoM'])) {
			$this->settings['lt_min_nNoM'] = $settings['lt_min_nNoM'];
		}
		if (isset($settings['lt_min_vNoM']) && is_numeric($settings['lt_min_vNoM'])) {
			$this->settings['lt_min_vNoM'] = $settings['lt_min_vNoM'];
		}
		return $this->settings;
	}

	public function display() {
		global $lang;
		global $site_url;
		global $admin_type;
		global $global_user_id;
		
		echo '<div id="placeholder" class="language_technology">';
		$this->dispalySettings();
#$r = $this->sendJson('Na kolokvij se prijavljam kot');
#print_r("<pre>");
#print_r($r);
#print_r("</pre>");

		echo '<div id="language_technology" class="branching_new expanded language_technology">';
		?><script> locked = true; </script><?php
		$lang_admin = $lang;

		$lang = $lang_admin;

		$b = new Branching($this->anketa);
		$b->displayKomentarji(false);
        $b->branching = '';
        $b->locked = true;

		$sql = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='$this->anketa' ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");
		if (mysqli_num_rows($sql) > 0 ) {
			while ($row = mysqli_fetch_array($sql)) {

					echo '<li id="branching_'.$row['id'].'" class="spr">';
					ob_start();
					$b->vprasanje($row['id']);
					$out1 = ob_get_contents();
					ob_end_clean();
					echo $out1;
					echo '</li>';
					echo '<div class="clr"></div>';
#					print_r("<pre>");
#					print_r(strip_tags($out1));
#					print_r("<pre>");
			}
		} 
		echo '</div>';	// branching
		echo '<div id="vprasanje_float_editing" class="language_technology">';
        $this->displayVprasanjeFloatEditing();
        echo '</div>';
		echo '</div>';	// placeholder
		
	}
	private function dispalySettings() {
        
        
        #echo '<div id="language_technology_setting">';
		echo '<div id="topSettingsHolder" class="language_technology" style="margin-bottom:10px;">';
        echo '<div id="additional_navigation">';
		echo '<table class="setting">';
		echo '<tr><td class="lt_table_lang">' . $this->lang['srv_language_technology_language'] . '</td>';
		echo '<td>' . $this->lang['srv_language_technology_flag_words'] . '</td>';
        echo '<td rowspan="2"><button type="button" onclick="cleanLanguageTechnology();">' . $this->lang['srv_language_technology_clean'] . '</button></td>';
		//echo '<td rowspan="2"><label id="lt_export_excel" onclick="lt_export_excel();"><span class="sprites xls_grey_16"></span>' . $this->lang['srv_language_technology_export_excel'] . '</label></td>';
		echo '</tr>';
		echo '<tr>';
        echo '<td><select id="lt_language">';
        if ($this->lang['id'] === '1') {
            echo '<option value="Slo" selected>Slovenščina</option>';
            echo '<option value="Eng">English</option>';
        } else {
            echo '<option value="Slo">Slovenščina</option>';
            echo '<option value="Eng" selected>English</option>';
        }
        echo '</select></td>';

        echo '<td>' . $this->lang['srv_language_technology_wf_under'] . '&nbsp;&nbsp;<input id="lt_min_FWD" type="text" value="5000"></td></tr>';
		#echo '<tr>';
        #echo '<td>' . $this->lang['srv_language_technology_verbs_above'] . 'Verbs </td><td><input id="lt_min_vNoM" type="text" value="3"></td></tr>';
		echo '</table>';
        echo '</div>';
		echo '</div>';
	}
	
	private function getMustangResult($sentence, $wordType = null) {
		if (empty($sentence) || trim($sentence) == "") {
			return null;
		}
		
		//$d = '[{"language": "Eng","sentence": "Sometimes it work.","min_FWD": 5000,"min_nNoM": 1,"min_vNoM": 3}]';
		//$d = '[{"language": "Slo","sentence": "S šumniki so težave","min_FWD": 5000,"min_nNoM": 1,"min_vNoM": 3}]';
		#$d = '[{"language": "Slo","sentence": "Na kolokvij se prijavljam kot","min_FWD": 5000,"min_nNoM": 1,"min_vNoM": 3}]';
		//$d = '[{"language": "Slo","sentence": "'.$sentence.'","min_FWD": 5000,"min_nNoM": 0,"min_vNoM": 3}]';
		$d .= '"language": "' . $this->settings['lt_language'] . '",';
		if ($wordType == null) {
			$mustangWordSentance = 'sentence?json=';
			$d .= '"sentence": "' . $sentence . '",';
		} else {
			$mustangWordSentance = 'word?json=';
			$d .= '"word": "' . $sentence . '",';
			$d .= '"Tag": "' . $wordType . '",';
		}
		$d .= '"min_FWD": ' . $this->settings['lt_min_FWD'] . ',';
		$d .= '"min_nNoM": ' . $this->settings['lt_min_nNoM'] . ',';
		$d .= '"min_vNoM": ' . $this->settings['lt_min_vNoM'] ;
		$d = trim($d);

		if (false) {
		    $d = '[{' . urlencode($d) . '}]';
		} else {
            $d = urlencode('[{' . $d . '}]');
        }

        $result = $this->makeMustangRequest($this->settings['lt_language'], $mustangWordSentance . $d);
                
		return $result;
	}

    private function makeMustangRequest($language, $mustangString) {
        
        if ( strtolower($language) == 'eng') {
            $url = 'http://mustang.ijs.si:5111/synonym/' . $mustangString ;
        } else {
            $url = 'http://mustang.ijs.si:5112/synonym/' . $mustangString;
        }

        try {
            //$r = $this->get_url_contents($url); // na testu ne deluje curl ???
                
            $ctx = stream_context_create(array('http'=>
                    array(
                            'timeout' => 20, // 20 Seconds 
                    )
            ));
            
            $r = (file_get_contents($url, false, $ctx));
            $r = str_replace(array("\r\n", "\n"), "", $r);
            $r = json_decode($r, true);


            if (is_array($r) && count($r) == 1) {
                $r = $r[0];
            }

        } catch(Exception $e) {

            $r = null;
        }
        return $r;
    }
    
	private function parseMustangResult($spremenljivka_mustang, $onlyProblematic = true) {
		$result = array();
		
		//$spremenljivka_mustang =  $spremenljivka_mustang['sentence'];
		if (is_array($spremenljivka_mustang) && count($spremenljivka_mustang) > 0) {
			
			foreach ($spremenljivka_mustang['sentence'] AS $mustangWordArray) {
				$mw = new MustangWord($mustangWordArray);
				if (($onlyProblematic== true && $mw->isProblematic()) || $onlyProblematic == false) 
				{
					$result[] = $mw->getJson();
				}
			}
		}
		return $result;
	}
	
	public function parseSpremenljivka($spremenljivka) {
		// polovimo tekste vprašanja

		$spremenljivka_naslov = $this->sanityze($this->getNaslovSpremenljivka($spremenljivka));
		$spremenljivka_mustang = $this->getMustangResult($spremenljivka_naslov);
		$spremenljivkaResult = $this->parseMustangResult($spremenljivka_mustang);
		return array('data' => $spremenljivkaResult );
	/*	
		$this->response['mustang']['naslov'] = $spremenljivka_mustang; 
		 
		//$this->addWords($spremenljivka_naslov);
		$vrednosti = ($this->getVrednostiSpremenljivka($spremenljivka));
		foreach ($vrednosti AS $vrednost) {
			$this->response['mustang']['vrednosti'][] = $this->sendJson($spremenljivka_naslov);
			//$this->response['vrednosti'][] = $this->sanityze($vrednost);
			//$this->addWords($this->sanityze($vrednost));
		}
		
		//echo strip_tags($spremenljivka_naslov);
		#echo 'ank:'.$this->anketa;
		#echo 'spr:'.$spremenljivka;
				
		return $this->response['mustang']['naslov'][0];
        */
	}
	
	/** funkcija počisti string neveljavnih zankov
	 * 
	 * @param unknown_type $string
	 */
	private function sanityze($string) {
		// odstranimo html tage
		$string = strip_tags($string);
		
		// odstanimo dvojne narekovaje
		$string = str_replace(array('"', "\n\r", "\n", "/", "-", "\\"), " ", $string);
		$string = preg_replace('!\s+!', ' ', $string);
		
		// odstranimo nepotrebne spejse
		$string = trim($string);
		return $string;
	}
	
	private function addWords($sentance) {
		$words = explode(" ", $sentance);
		if (!empty($words)) {
			foreach ($words AS $word) {
				$word = str_replace(array('"', "\n\r", "\n", "."), "", $word);
				$this->response['words'][$word] = $word;
			}
		}
	}
	
	protected function getNaslovSpremenljivka($spremenljivka) {
		$result = Cache::srv_spremenljivka($spremenljivka,'naslov');
		return $result;
	}

	protected function getVrednostiSpremenljivka($spremenljivka) {
		$result = array();
		$sql1 = sisplet_query("SELECT naslov FROM srv_vrednost WHERE spr_id='$spremenljivka'");
		while ($row1 = mysqli_fetch_assoc($sql1)) {
			$result[] = $row1['naslov']; 
		}
		return $result;
	}

	private function get_url_contents($url) {
		$curl = curl_init();
		$userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';
		
		//The contents of the "User-Agent: " header to be used in a HTTP request.
		curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
		curl_setopt($curl, CURLOPT_URL, $url);
		//To fail silently if the HTTP code returned is greater than or equal to 400.
		curl_setopt($curl, CURLOPT_FAILONERROR, TRUE); 
		// Removes the headers from the output
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		// Return the output instead of displaying it directly
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
		//To follow any "Location: " header that the server sends as part of the HTTP header.
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		//To automatically set the Referer: field in requests where it follows a Location: redirect.
		curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);
		//The maximum number of seconds to allow cURL functions to execute.
		curl_setopt($curl, CURLOPT_TIMEOUT, 10); 
		
		$ret = curl_exec($curl);
		curl_close($curl);
		return $ret;
	}
	
	public function exportLanguageTechnology($lt_data, $language) {
		require_once("./excel/PHPExcel.php");
		require_once("./excel/PHPExcel/IOFactory.php");

        $data = $this->prepareExcelData($lt_data, $language);

		$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_in_memory_serialized;
		PHPExcel_Settings::setCacheStorageMethod($cacheMethod);
		
		// Create new PHPExcel object
		$objPHPExcel = new PHPExcel();
		// Create a first sheet, representing sales data
        $objPHPExcel->setActiveSheetIndex(0);
        $aSheet = $objPHPExcel->getActiveSheet();
		// Rename sheet
		$aSheet->setTitle('Relevant meaning');
		$aSheet->getColumnDimension('A')->setWidth(8);
        $aSheet->getColumnDimension('B')->setWidth(20);
		$aSheet->getColumnDimension('C')->setWidth(12);
		$aSheet->getColumnDimension('D')->setWidth(12);
		// first lines
		$aSheet->setCellValue('A1', 'Question');
        $aSheet->setCellValue('B1', 'Original word');
		$aSheet->setCellValue('C1', 'WF');
		$aSheet->setCellValue('D1', 'NoM');

		$line = 2;
        //$response = unserialize(file_get_contents('mustangData.dat'));
		// loop po spremenljivkah

        foreach ($data['words'] as $spr_id => $words) {
            $spremenljivkaId = $spr_id;
            $spremenljivkaVariable = Cache::srv_spremenljivka($spremenljivkaId, 'variable');
            $naslov = Cache::srv_spremenljivka($spremenljivkaId, 'naslov');

            
            $sc = 0;
            foreach ($words as $w_idx => $bData) {
                $word = $data['spremenljivkeData'][$spr_id][$w_idx]['word'];
                $tag = $data['spremenljivkeData'][$spr_id][$w_idx]['Tag'];
                $fwd = $data['spremenljivkeData'][$spr_id][$w_idx]['FWD'];
                $nom = $data['spremenljivkeData'][$spr_id][$w_idx]['NoM'];
                if ($sc == 0) {
                    $aSheet->setCellValue('A'.$line, $spremenljivkaVariable . ' - ' . strip_tags ($naslov));    
                }
                $sc ++;
                $aSheet->setCellValue('B'.$line, $word);
                $aSheet->setCellValue('C'.$line, $fwd);
                $aSheet->setCellValue('D'.$line, $nom);
                $line++;
            }
        }

        #SYNONYMS        
        $objPHPExcel->createSheet();
        $objPHPExcel->setActiveSheetIndex(1);
        $aSheet = $objPHPExcel->getActiveSheet();
        $aSheet->setTitle('Synonyms');
        
        $aSheet->getColumnDimension('A')->setWidth(8);
        $aSheet->getColumnDimension('B')->setWidth(20);
        $aSheet->getColumnDimension('C')->setWidth(20);
        $aSheet->getColumnDimension('D')->setWidth(12);
        $aSheet->getColumnDimension('E')->setWidth(12);
        
        $aSheet->setCellValue('A1', 'Question');
        $aSheet->setCellValue('B1', 'Original wording');
        $aSheet->setCellValue('C1', 'Alternative wording');
        $aSheet->setCellValue('D1', 'WF');
        $aSheet->setCellValue('E1', 'NoM');

        $line = 2;        
        if (count($data['synsets'])) {
            foreach ($data['synsets'] as $spr_id => $wordData) {
                $spremenljivkaId = $spr_id;
                $spremenljivkaVariable = Cache::srv_spremenljivka($spremenljivkaId, 'variable');
                $naslov = Cache::srv_spremenljivka($spremenljivkaId, 'naslov');

                $sc = 0;
                foreach ($wordData AS $w_idx => $synsetsData) {
                    $wc = 0;
                    $word = $data['spremenljivkeData'][$spr_id][$w_idx]['word'];
                      foreach ($synsetsData AS $syns_word => $syns_data) {
                        if ($sc == 0) {
                            $aSheet->setCellValue('A'.$line, $spr_id);
                            $aSheet->setCellValue('A'.$line, $spremenljivkaVariable . ' - ' . strip_tags ($naslov));
                        }
                        $sc++;
                        if ($wc == 0) {
                            $aSheet->setCellValue('B'.$line, $word);
                        }
                        $wc++;
                        $aSheet->setCellValue('C'.$line, $syns_word);
                        $aSheet->setCellValue('D'.$line, $syns_data['freq']);
                        $aSheet->setCellValue('E'.$line, $syns_data['nom']);
                        $line++;
                    }
                }
            }
		}
        
        # HYPERNYMS       
        $objPHPExcel->createSheet();
        $objPHPExcel->setActiveSheetIndex(2);
        $aSheet = $objPHPExcel->getActiveSheet();
        // Rename 2nd sheet
        $aSheet->setTitle('Hypernyms');
        
        $aSheet->getColumnDimension('A')->setWidth(8);
        $aSheet->getColumnDimension('B')->setWidth(20);
        $aSheet->getColumnDimension('C')->setWidth(20);
        
        $aSheet->setCellValue('A1', 'Question');
        $aSheet->setCellValue('B1', 'Original wording');
        $aSheet->setCellValue('C1', 'Hypernym');

        $line = 2;        
        if (count($data['hypernyms'])) {
            foreach ($data['hypernyms'] as $spr_id => $wordData) {
                $spremenljivkaId = $spr_id;
                $spremenljivkaVariable = Cache::srv_spremenljivka($spremenljivkaId, 'variable');
                $naslov = Cache::srv_spremenljivka($spremenljivkaId, 'naslov');

                $sc = 0;
                foreach ($wordData AS $w_idx => $hypersData) {
                    $wc = 0;
                    $word = $data['spremenljivkeData'][$spr_id][$w_idx]['word'];
                      foreach ($hypersData AS $syns_word => $hyper_data) {
                        if ($sc == 0) {
                            $aSheet->setCellValue('A'.$line, $spr_id); 
                            $aSheet->setCellValue('A'.$line, $spremenljivkaVariable . ' - ' . strip_tags ($naslov));   
                        }
                        $sc++;
                        if ($wc == 0) {
                            $aSheet->setCellValue('B'.$line, $word);
                        }
                        $wc++;
                        $aSheet->setCellValue('C'.$line, $hyper_data);
                        $line++;
                    }
                }
            }
        }
        # HYPONYMS       
        $objPHPExcel->createSheet();
        $objPHPExcel->setActiveSheetIndex(3);
        $aSheet = $objPHPExcel->getActiveSheet();
        // Rename 2nd sheet
        $aSheet->setTitle('Hyponyms');
        
        $aSheet->getColumnDimension('A')->setWidth(8);
        $aSheet->getColumnDimension('B')->setWidth(20);
        $aSheet->getColumnDimension('C')->setWidth(20);
        
        $aSheet->setCellValue('A1', 'Question');
        $aSheet->setCellValue('B1', 'Original wording');
        $aSheet->setCellValue('C1', 'Hyponym');

        $line = 2;        
        if (count($data['hyponyms'])) {
            foreach ($data['hyponyms'] as $spr_id => $wordData) {
                $sc = 0;
                foreach ($wordData AS $w_idx => $hypersData) {
                    $wc = 0;
                    $word = $data['spremenljivkeData'][$spr_id][$w_idx]['word'];
                      foreach ($hypersData AS $syns_word => $hyper_data) {
                        if ($sc == 0) {
                            $aSheet->setCellValue('A'.$line, $spr_id);    
                        }
                        $sc++;
                        if ($wc == 0) {
                            $aSheet->setCellValue('B'.$line, $word);
                        }
                        $wc++;
                        $aSheet->setCellValue('C'.$line, $hyper_data);
                        $line++;
                    }
                }
            }
        }
        
        global $site_path;
		$folder = $site_path . EXPORT_FOLDER.'/';
		
		// izberemo random hash, ki se ni v bazi (to more bit, ker je index na fieldu cookie)
		$filename = null;
		$rand = null;
		$x = 0;
		do {
			$rand = md5(mt_rand(1, mt_getrandmax()).'@'.$_SERVER['REMOTE_ADDR']);
			$filename = $folder . "lt_" . $rand . '.xlsx';
			$x++;
		} while (file_exists($filename) === true && $x < 99999); // backup loop

		if ($filename != null) {
			$objPHPExcel->setActiveSheetIndex(0);
			$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
			$objWriter->save($filename);
			return $rand;
		} else {
			throw new Exception("Can't create excel report!");
		}
	}	
	
	
	public function parseWord($word, $wordType) {
		$spremenljivkaResult = array();

		if (in_array($wordType, array('a', 'n', 'v', 'adv', 'e'))) {
			$spremenljivka_mustang = $this->getMustangResult($word, $wordType);


			$data = array('language'=>$spremenljivka_mustang['language']);
			unset($spremenljivka_mustang['language']);
			$data['sentence'][] = $spremenljivka_mustang;
            // ker delamo posamezne besede ni pomembno da je onlyProblematic
			$spremenljivkaResult = $this->parseMustangResult($data, false);
		}
		return $spremenljivkaResult ;
		
	}
    
    public function getHypoHypernym($synsets) {
        if (!is_array($synsets) || empty($synsets)) {
            return null;
        }
        $hypernyms = array();
        $hyponyms = array();

        $_synsets = array();        

        foreach ($synsets AS $synset) {
            $synset = $synset['synonyms'];

            // http://mustang.ijs.si:5111/synonym/hypohypernym?json=
            // [{ "language": "Eng", "": "1. spouse,partner,married person,mate,better half|n|a person's partner in marriage|"}]
            
            $mustangWordSentance = 'hypohypernym?json=';
            $d = '';
            $d .= '"language": "' . $this->settings['lt_language'] . '", ';
            $d .= '"synset": "' . ( $synset) . '"';
            $d = trim($d);

            if (false) {
                $d = '[{' . urlencode($d) . '}]';
            } else {
                $d = urlencode('[{' . $d . '}]');
            }

            $result = $this->makeMustangRequest($this->settings['lt_language'], $mustangWordSentance . $d);
            
            // nima vsaka beseda hypernymov in hyponimpov takrat mustang vrne specifičen string:
            if (isset($result['hypernym'][0]['hypernym']) 
                && ($result['hypernym'][0]['hypernym']== 'blamaÅ¾a, kiks, zagata'
                    || $result['hypernym'][0]['hypernym']== 'blamaža, kiks, zagata'
                    || $result['hypernym'][0]['hypernym']== 'happening, occurrence, occurrent, natural event'))
                    {
                        // beseda nima hypernymov in hyponymov
                        $result['hypernym'] = array();
                        $result['hyponym'] = array();
            }

            if (isset($result['hypernym']) && is_array($result['hypernym']) && count($result['hypernym']) > 0) {
                foreach ($result['hypernym'] AS $_hypernyms) {
                    if (isset($_hypernyms['hypernym'])) {
                        $__hypernyms = array_map('trim',explode(',', $_hypernyms['hypernym']));
                        $hypernyms = array_merge($hypernyms, $__hypernyms);
                        
                    }
                }
            }
            if (isset($result['hyponym']) && is_array($result['hyponym']) && count($result['hyponym']) > 0) {
                foreach ($result['hyponym'] AS $_hyponyms) {
                    $_hyponyms = array_map('trim',explode(',', $_hyponyms));
                    $hyponyms = array_merge($hyponyms, $_hyponyms);
                }
            }
        }
        $hypernyms = array_unique($hypernyms);
        $hyponyms = array_unique($hyponyms);
        natsort($hypernyms);
        natsort($hyponyms);
        if(($key = array_search('NO HYPERNYMS', $hypernyms)) !== false) {
            unset($hypernyms[$key]);
        }
        if(($key = array_search('NO HYPONYMS', $hyponyms)) !== false) {
            unset($hyponyms[$key]);
        }
        return array('data' => array('hypernyms' => $hypernyms, 'hyponyms' => $hyponyms), 'synsets' => $_synsets);
    }
    
    private function displayVprasanjeFloatEditing() {
        echo '<p><label>Frekvenca besede je pod: <input type="text" id="lt_min_FWD_spr" value=""></label>';
        echo '</p><p>';
        echo '<label>Nastavi le tej spremenljivki <input type="checkbox" id="lt_special_setting" value=""></label>';
        echo '</p><p>';
        echo '<label><button type="button" onclick="saveLanguageTechnologySetting();">' . $this->lang[''] . 'Nastavi</button>';
        echo '</p><p>';
        echo '<button type="button" onclick="runLanguageTechnology();">' . $this->lang['srv_language_technology_run'] . '</button>';
        echo '</p>';
        echo '</p><p>';
        echo '<label id="lt_export_excel" onclick="lt_export_excel();"><span class="sprites xls_grey_16"></span>' . $this->lang['srv_language_technology_export_excel'] . '</label>';
        echo '</p>';
    
        
    }

    private function prepareExcelData($lt_data, $language) {

        $result = array();

        if (!isset($lt_data['response'])) {
            return $result;
        }
        $spremenljivkeData = $lt_data['response'];
        $result['spremenljivkeData'] = $spremenljivkeData;
        unset($lt_data['response']);
        
        $spremenljivkaWords = array();
        if (isset($lt_data['wordsSynonyms'])) {
            foreach ($lt_data['wordsSynonyms'] as $spr_id => $wordSynData) {
                foreach ($wordSynData as $wordIdx => $synData) {
                    foreach ($synData AS $synIdx => $star) {
                        if (isset($spremenljivkeData[$spr_id][$wordIdx]['Synset'][$synIdx])) {
                            $wordSynsets = $this->parseExcelSynsets($spremenljivkeData[$spr_id][$wordIdx]['Synset'][$synIdx], $language);
                            foreach($wordSynsets AS $word => $wordData) {
                                if (!isset($result['words'][$spr_id][$wordIdx])) {
                                    $result['words'][$spr_id][$wordIdx] = true;
                                }
                                if (!isset($result['synsets'][$spr_id][$wordIdx][$word])) {
                                    $result['synsets'][$spr_id][$wordIdx][$word] = $wordData;
                                }        
                            }
                        }
                        
                    }
                }
                
            }
        }   

        if (isset($lt_data['wordsHypernyms'])) {
            foreach ($lt_data['wordsHypernyms'] as $spr_id => $wordHyperData) {
                foreach ($wordHyperData as $wordIdx => $synData) {
                    foreach ($synData AS $synIdx => $star) {
                        if (isset($spremenljivkeData[$spr_id][$wordIdx]['cleanhypernyms'][$synIdx])) {
                            if (!isset($result['words'][$spr_id][$wordIdx])) {
                                $result['words'][$spr_id][$wordIdx] = true;
                            }

                            if (!isset($result['hypernyms'][$spr_id][$wordIdx][$synIdx])) {
                                $result['hypernyms'][$spr_id][$wordIdx][$synIdx] = $spremenljivkeData[$spr_id][$wordIdx]['cleanhypernyms'][$synIdx];
                            }        
                        }
                        
                    }
                }
                
            }
        }   
        
        if (isset($lt_data['wordsHyponyms'])) {
            foreach ($lt_data['wordsHyponyms'] as $spr_id => $wordHypoData) {
                foreach ($wordHypoData as $wordIdx => $synData) {
                    foreach ($synData AS $synIdx => $star) {
                        if (isset($spremenljivkeData[$spr_id][$wordIdx]['cleanhyponyms'][$synIdx])) {
                            if (!isset($result['words'][$spr_id][$wordIdx])) {
                                $result['words'][$spr_id][$wordIdx] = true;
                            }

                            if (!isset($result['hyponyms'][$spr_id][$wordIdx][$synIdx])) {
                                $result['hyponyms'][$spr_id][$wordIdx][$synIdx] = $spremenljivkeData[$spr_id][$wordIdx]['cleanhyponyms'][$synIdx];
                            }        
                        }
                        
                    }
                }
                
            }
        }   

        return $result;
    }   

    private function parseExcelSynsets($stringWordSynsets, $language) {
        $result = array();
        $synonyms = $stringWordSynsets['FWDNoM'];
        
        if (strtolower($language) == 'eng') {
            // besede so ločene z vejico
            $words = explode(';', $synonyms);
            foreach ($words AS $word) {
                if ($word == null || trim($word) == "") {
                    continue;
                }
                $tmp = explode(':', $word);
                $tmpWord = trim($tmp[0]);
                $tmp = explode(',', $tmp[1]);
                $freq = trim(str_replace(array("FW","=", " "), "", trim($tmp[0])));
                $nom = trim(str_replace(array("NoM","=", " "), "", trim($tmp[1])));
                if (!isset($result[$tmpWord])) {
                    $result[$tmpWord] = array('freq'=>$freq, 'nom'=> $nom);
                }
            }

        } else {
            // besede so ločene z vejico
            $words = explode(';', $synonyms);
            foreach ($words AS $word) {
                if ($word == null || trim($word) == "") {
                    continue;
                }
                $tmp = explode(':', $word);
                $tmpWord = trim($tmp[0]);
                $tmp = explode(',', $tmp[1]);
                $freq = trim(str_replace(array("FW","=", " "), "", trim($tmp[0])));
                $nom = trim(str_replace(array("NoM","=", " "), "", trim($tmp[1])));
                if (!isset($result[$tmpWord])) {
                    $result[$tmpWord] = array('freq'=>$freq, 'nom'=> $nom);
                }
            }
        }
        return $result;
    }
}

class MustangWord {

	
	public $word = "";
	private $Tag = ""; // {Do, n, v, adv, 
	private $FWD = 0;
	private $NoM = 0;
	private $minFWD = false;
	private $min_nNoM = false;
	private $min_vNoM = false;
	private $Synset = array();
	
	public $problematic;
	private $json;
	
	/**
	 * 
{
	"word": "kot",
	"Tag": "Vd",
	"FWD": 467459,
	"NoM": 0,
	"Flag": {
		"minFWD": false,
		"min_nNoM": false,
		"min_vNoM": false
	},
	"Synset": [{
		"synonyms": "enako, kakor, ko, kot, medtem ko, prav tako, tako kot",
		"FWDNoM": "enako NoM: 2 Frek: 14288, kakor NoM: 1 Frek: 37364, ko NoM: 1 Frek: 297690, kot NoM: 6 Frek: 467459, medtem ko NoM: 1 Frek: neznana, prav tako NoM: 1 Frek: neznana, tako kot NoM: 1 Frek: neznana"
	},
	{
		"synonyms": "kot, vogal",
		"FWDNoM": "kot NoM: 6 Frek: 467459, vogal NoM: 5 Frek: 2507"
	},
	{
		"synonyms": "kot, vogal",
		"FWDNoM": "kot NoM: 6 Frek: 467459, vogal NoM: 5 Frek: 2507"
	},
	{
		"synonyms": "kot",
		"FWDNoM": "kot NoM: 6 Frek: 467459"
	},
	{
		"synonyms": "kot, vogal, vogel",
		"FWDNoM": "kot NoM: 6 Frek: 467459, vogal NoM: 5 Frek: 2507, vogel NoM: 2 Frek: 269"
	},
	{
		"synonyms": "kot",
		"FWDNoM": "kot NoM: 6 Frek: 467459"
	}]
}
	 */
	public function __construct($mustangWordArray) {
		$this->json = $mustangWordArray;
		
		$this->word = $mustangWordArray['word'];
		$this->Tag = $mustangWordArray['Tag'];
		$this->FWD = $mustangWordArray['FWD'];
		$this->NoM = $mustangWordArray['NoM'];

		$this->minFWD = $mustangWordArray['Flag']['minFWD'];
		$this->min_nNoM = $mustangWordArray['Flag']['min_nNoM'];
		$this->min_vNoM = $mustangWordArray['Flag']['min_vNoM'];
		
		$this->problematic = $this->minFWD || $this->min_nNoM || $this->min_vNoM; 
		

	}
	
	public function isProblematic() {
		
		return $this->problematic;
	}
	
	public function getJson() {
		return $this->json;
	}
 
 
}