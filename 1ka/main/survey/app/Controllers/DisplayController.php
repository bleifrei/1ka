<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 05.02.2016
 *****************************************/

namespace App\Controllers;

// Osnovni razredi
use App\Controllers\AjaxController as Ajax;
use App\Controllers\BodyController as Body;
use App\Controllers\FindController as Find;
use App\Controllers\HeaderController as Header;
use App\Controllers\HelperController as Helper;
use App\Controllers\Vprasanja\VprasanjaController as Vprasanja;
use SurveyInfo;
use SurveySetting;
use Common;
use SurveyGorenje;

class DisplayController extends Controller
{
    public function __construct()
    {
        parent::getGlobalVariables();
    }

    /************************************************
     * Get instance
     ************************************************/
    private static $_instance;

    public static function getInstance()
    {
        if (self::$_instance)
            return self::$_instance;

        return new DisplayController();
    }

    public function logo()
    {
		// Za gorenje posebej prikazemo logo
		if(Common::checkModule('gorenje')){
			SurveyGorenje::logoGorenje(get('anketa'), get('usr_id'));			
			return;
		}
		
        $class = '';
        $url = self::$site_url;

        // Logo prikazemo angleski v vseh primerih kjer respondentov jezik ni slovenscina in ce ni custom - popravimo tudi link na angleski frontend
        if (self::$lang['language'] != 'Slovenščina') {
            $class = ' class="english"';
            if (strpos(self::$site_url, 'www.1ka.si') !== false)
                $url = self::$site_url.'d/en/';
        }

        echo '<div id="logo" ' . $class . '><a href="' . $url . '" title="' . self::$lang['srv_1cs'] . ' ' . self::$lang['srv_footer_1ka'] . '" target="_blank">' . self::$lang['srv_1cs'] . '</a><div id="logo_right"></div></div>';
    }

    /**
     * @desc prikaze progress bar
     */
    public function progress_bar()
    {
        $row = SurveyInfo::getInstance()->getSurveyRow();

        $sql_count_pages = sisplet_query("SELECT COUNT( g.id ) AS count FROM srv_grupa g WHERE g.ank_id = '" . get('anketa') . "'");
        $row_count_pages = mysqli_fetch_assoc($sql_count_pages);

		// Ce prikazemo gumb za tawk chat
		$tawk_chat = false;
		if(SurveyInfo::getInstance()->checkSurveyModule('chat') == '1'){
			$sql_chat = sisplet_query("SELECT chat_type FROM srv_chat_settings WHERE ank_id='".get('anketa')."'");
			$row_chat = mysqli_fetch_assoc($sql_chat);
			
			if($row_chat['chat_type'] == '2')
				$tawk_chat = true;
		}
		
        if (($row['progressbar'] == 1 && $row_count_pages['count'] > 1) || ($row['continue_later'] == 1) || $tawk_chat) {
            echo '<div class="header_settings_holder">';

            if ($row['progressbar'] == 1 && $row_count_pages['count'] > 1) {

                echo '<div class="progress_bar">';

                $sql1 = sisplet_query("SELECT COUNT(s.id) AS count
                                FROM srv_grupa g, srv_spremenljivka s
                                WHERE s.gru_id=g.id AND g.ank_id = '" . get('anketa') . "' AND s.visible='1'");
                $row1 = mysqli_fetch_array($sql1);
                $all = $row1['count'];

                $sql2 = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id = '" . get('grupa') . "'");
                $row2 = mysqli_fetch_array($sql2);

                $sql3 = sisplet_query("SELECT COUNT(s.id) AS count
                                FROM srv_grupa g, srv_spremenljivka s
                                WHERE s.gru_id=g.id AND g.ank_id = '" . get('anketa') . "' AND s.visible='1' AND g.vrstni_red<='$row2[vrstni_red]'");
                $row3 = mysqli_fetch_array($sql3);
                $ans = $row3['count'];

                if ($all > 0) {

                    $p = round($ans / $all * 100, 0);

                    echo '<span>0%</span>';
                    echo '<div class="progress_bar_line"><span style="width:' . $p . '%"></span></div>';
                    echo '<span>100%</span>';
                }

                echo '</div>';
            }

			// Prikaz opcije "nadaljuj kasneje"
            if ($row['continue_later'] == 1) {
                SurveySetting::getInstance()->Init(get('anketa'));
                if (get('lang_id') != null) $_lang = '_' . get('lang_id'); else $_lang = '';
                $srv_continue_later = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_continue_later' . $_lang);
                if ($srv_continue_later == '') $srv_continue_later = self::$lang['srv_continue_later'];

                echo '<div class="continue_later_setting">';
                echo '  <a href="#" onclick="continue_later(\'' . self::$site_url . '\', \'' . get('lang_id') . '\'); return false;">' . $srv_continue_later . '</a>';
                echo '</div>';
            }
			
			// Priakz gumba za vklop tawk chata
			if ($tawk_chat){
                echo '<div class="tawk_chat">';
				echo '  <span class="tawk-chat-activation">'.self::$lang['srv_chat_turn_on'].'</span>';
                echo '</div>';
            }

            echo '</div>';
        }
    }

	// Prikaze ikono za izvoz pdf rezultatov v zakljucku
    public function displayRespondetnPDF()
    {
        $row = SurveyInfo::getInstance()->getSurveyRow();

        if ((int)$row['concl_PDF_link'] == 1) {

			// Ce je vklopljen evoli ali evoli employeeship meter, prikazemo link do posebnega porocila
			if(SurveyInfo::getInstance()->checkSurveyModule('evoli') || SurveyInfo::getInstance()->checkSurveyModule('evoli_employmeter')){
				
				// Nastavimo ustrezen jezik za report
				if(self::$lang['id'] == '1')
					$report_lang = 'slo';
				elseif(self::$lang['id'] == '29')
					$report_lang = 'dan';
				else
					$report_lang = '';
                
                if(SurveyInfo::getInstance()->checkSurveyModule('evoli_employmeter'))
                    $evoli_module = 'pdf_employmeter';
                else
                    $evoli_module = 'pdf_evoli';

				$pdf_url = self::$site_url . 'admin/survey/izvoz.php?dc=' . base64_encode(serialize(array('m' => $evoli_module, 'anketa' => get('anketa'), 'usr_id' => get('usr_id'), 'lang' => $report_lang)));

				echo '<br class="clr"/>';
				
				echo '<div class="concl_evoli_report naslov"><p>';
                
                if(SurveyInfo::getInstance()->checkSurveyModule('evoli')){
                    echo self::$lang['srv_report_pdf_evoli'].': ';
				
                    echo '<a href="' . $pdf_url . '" class="pdfExport" target="_blank">';
                    echo '<span class="evoli_button">'.self::$lang['srv_report_pdf_evoli_button'].'</span>';
                    echo '</a>';
                }
                else{
                    echo self::$lang['srv_report_pdf_evoli_em'].': ';
				
                    echo '<a href="' . $pdf_url . '" class="pdfExport" target="_blank">';
                    echo '<span class="evoli_button">'.self::$lang['srv_report_pdf_evoli_em_button'].'</span>';
                    echo '</a>';
                }
				
				echo '</p></div>';
			}
			else{
				# parametre zapakiramo v array injih serializiramo in zakodiramo z base64
				$pdf_url = self::$site_url . 'admin/survey/izvoz.php?dc=' . base64_encode(serialize(array('a' => 'pdf_results', 'anketa' => get('anketa'), 'usr_id' => get('usr_id'), 'type' => '0')));
				
				#echo '<div id="icon_bar">';
				echo '<br class="clr"/><div><p>';
				echo '<a href="' . $pdf_url . '" class="pdfExport" target="_blank"><span class="sprites pdf_white"></span> '.self::$lang['srv_report_pdf'].'</a>';
				echo '</p></div>';
			}
        }
    }
	
	// Prikaze url za naknadno popravljanje odgovorov (od zacetka ankete) v zakljucku
    public function displayReturnEditURL()
    {
        $row = SurveyInfo::getInstance()->getSurveyRow();

        if ((int)$row['concl_return_edit'] == 1) {

			$return_url = $_POST['url'] . '&return=1';
            $return_url = SurveyInfo::getSurveyLink() . get('cookie_url') . '&return=1';

            echo '<br class="clr"/><div class="return_edit_url"><p>';
            echo self::$lang['srv_concl_return_edit_URL'].':<br />';
			echo '<a href="'.$return_url.'" title="'.self::$lang['srv_concl_return_edit_URL'].'"><span>'.$return_url.'</span></a>';
            echo '</p></div>';
        }
    }
	
	// Prikaze pravilne rezultate v primeru modula KVIZ
    public function displayQuizAnswers()
    {
       // echo '<h2>'.self::$lang['results'].'</h2>';
		
		// Loop cez vsa ustrezna vprasanja (ki imajo oznacen vsaj en pravilen odgovor)
		$sqlS = sisplet_query("SELECT s.id, s.naslov, s.info FROM srv_spremenljivka s, srv_grupa g
											WHERE g.ank_id='".get('anketa')."' AND s.gru_id=g.id AND s.tip IN ('1', '2', '3') AND s.visible='1' 
											AND EXISTS (SELECT q.* FROM srv_quiz_vrednost q WHERE q.spr_id=s.id)
											AND NOT EXISTS (SELECT d.* FROM srv_data_vrednost_active d WHERE d.spr_id=s.id AND usr_id='".get('usr_id')."' AND d.vre_id='-2')");
		if (!$sqlS) echo mysqli_error($GLOBALS['connect_db']);
		while($rowS = mysqli_fetch_array($sqlS)){
			
			echo '<div class="spremenljivka">';
			
			//Vprasanja::getInstance()->displaySpremenljivka($rowS['id']);
			
			echo '<div class="naslov">'.$rowS['naslov'];
			if ($rowS['info'] != '')
				echo '<p class="spremenljivka_info">' . $rowS['info'] . '</p>';
			echo '</div>';
			
			echo '<div class="variable_holder">';
			
			// Loop cez vse vrednosti v vprasanju
			$sqlV = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='".$rowS['id']."' ORDER BY vrstni_red ASC");
			while($rowV = mysqli_fetch_array($sqlV)){

				// Za vsako pogledamo ce je pravilna oz napacna
				$answer = false;
				$correct = false;
				
				$sqlAnswer = sisplet_query("SELECT * FROM srv_data_vrednost_active WHERE spr_id='".$rowS['id']."' AND vre_id='".$rowV['id']."' AND usr_id='".get('usr_id')."'");
				if(mysqli_num_rows($sqlAnswer) == 1)
					$answer = true;
					
				$sqlQuiz = sisplet_query("SELECT * FROM srv_quiz_vrednost WHERE spr_id='".$rowS['id']."' AND vre_id='".$rowV['id']."'");
				if(mysqli_num_rows($sqlQuiz) == 1)
					$correct = true;
								
				if($correct && $answer){
					echo '<div class="variabla green">';
					echo $rowV['naslov'] . '<span class="true"></span>';				
					echo '</div>';
				}
				elseif($correct){
					echo '<div class="variabla bold">';
					//echo $rowV['naslov'] . '<span class="true2"></span>';				
					echo $rowV['naslov'];				
					echo '</div>';
				}
				elseif($answer){
					echo '<div class="variabla red">';
					echo $rowV['naslov'] . '<span class="false"></span>';				
					echo '</div>';
				}
				else{
					echo '<div class="variabla">';
					echo $rowV['naslov'];				
					echo '</div>';
				}
			}
			
			echo '</div>';
			
			echo '</div>';
		}
    }
	
	// Prikaze graf rezultatov v primeru modula KVIZ
    public function displayQuizChart()
    {
        //echo '<h2>'.self::$lang['results'].'</h2>';
		
		$cnt_all = 0;
		$cnt_answered = 0;
		$cnt_unanswered = 0;
		$cnt_correct = 0;
		$cnt_incorrect = 0;
		
		// Loop cez vsa ustrezna vprasanja (ki imajo oznacen vsaj en pravilen odgovor)
		$sqlS = sisplet_query("SELECT s.id, s.naslov, s.info FROM srv_spremenljivka s, srv_grupa g
											WHERE g.ank_id='".get('anketa')."' AND s.gru_id=g.id AND s.tip IN ('1', '2', '3') AND s.visible='1' 
											AND EXISTS (SELECT q.* FROM srv_quiz_vrednost q WHERE q.spr_id=s.id)
											AND NOT EXISTS (SELECT d.* FROM srv_data_vrednost_active d WHERE d.spr_id=s.id AND usr_id='".get('usr_id')."' AND d.vre_id='-2')");
		if (!$sqlS) echo mysqli_error($GLOBALS['connect_db']);
		while($rowS = mysqli_fetch_array($sqlS)){
			
			$cnt_all++;
			
			$answer = false;
			$correct = false;
			
			// Loop cez vse vrednosti v vprasanju
			$sqlV = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='".$rowS['id']."' ORDER BY vrstni_red ASC");
			while($rowV = mysqli_fetch_array($sqlV)){
		
				// Za vsako pogledamo ce je odgovorjena
				$sqlAnswer = sisplet_query("SELECT * FROM srv_data_vrednost_active WHERE spr_id='".$rowS['id']."' AND vre_id='".$rowV['id']."' AND usr_id='".get('usr_id')."'");
				if(mysqli_num_rows($sqlAnswer) == 1){
					$answer = true;
				
					// Za vsako pogledamo ce je pravilna oz napacna
					$sqlQuiz = sisplet_query("SELECT * FROM srv_quiz_vrednost WHERE spr_id='".$rowS['id']."' AND vre_id='".$rowV['id']."'");
					
					if(mysqli_num_rows($sqlQuiz) == 1){
						$correct = true;
					}
					// Ce je oznacil napacno breakamo
					else{
						$correct = false;
						break;
					}
				}
			}
			
			if($answer)
				$cnt_answered++;
			else
				$cnt_unanswered++;
				
			if($correct)
				$cnt_correct++;
			else
				$cnt_incorrect++;
		}
		
		
		// Izrisemo graf
		/*echo 'Vseh: '.$cnt_all;
		echo '<br>odgovorjen: '.$cnt_answered;
		echo '<br>neodg: '.$cnt_unanswered;
		echo '<br>pravilen: '.$cnt_correct;
		echo '<br>nepravilen: '.$cnt_incorrect;*/
		
		// Include knjiznice Chart.JS
		echo '<script src="'.self::$site_url.'/main/survey/js/ChartJS/Chart.min.js"></script>';
		echo '<script src="'.self::$site_url.'/main/survey/js/ChartJS/chart_init.js?v=3"></script>';
		
		// Izris grafa
		echo '<div class="spremenljivka" id="quiz_results_chart_holder">';
		echo '<canvas id="quiz_results_chart" class="chart"></canvas>';		
		echo '<script>$(document).ready(function(){ init_quiz_results_chart('.$cnt_all.', '.$cnt_correct.', '.$cnt_incorrect.', \''.self::$lang['srv_quiz_results_chart_correct'].'\', \''.self::$lang['srv_quiz_results_chart_incorrect'].'\'); })</script>';
		echo '</div>';
    }
	
	
	// Prikaze graf za matriko odlicnosti (excell_matrix) v primeru modula Excelleration matrix
    public function displayExcellChart()
    {
		echo '<h3 style="padding-left: 20px;">'.self::$lang['srv_excell_matrix_title'].'</h3>';
		
		// Loop cez vsa ustrezna vprasanja in njihove vrednosti (jih zakesiramo)
		$variables = array();
		$variable_marza = array();
		$variable_lp = array();
		$spr_ids = '';
		$sqlS = sisplet_query("SELECT s.id AS spr_id, s.naslov AS spr_naslov, s.variable AS spr_variable, v.id AS vre_id, v.variable AS vre_variable, v.naslov AS vre_naslov
								FROM srv_spremenljivka s, srv_grupa g, srv_vrednost v
								WHERE g.ank_id='".get('anketa')."' AND s.gru_id=g.id 
									AND s.variable LIKE 'em%'
									AND v.spr_id=s.id");
		if (!$sqlS) echo mysqli_error($GLOBALS['connect_db']);
		while($rowS = mysqli_fetch_array($sqlS)){

			// Marza
			if($rowS['spr_variable'] == 'emM'){
				
				// ID-ji vprasanj za query
				if(!isset($variable_marza['id']))
					$spr_ids .= $rowS['spr_id'].',';
				
				$variable_marza['naslov'] = strip_tags($rowS['spr_naslov']);
				$variable_marza['id'] = $rowS['spr_id'];
				
				$variable_marza['vrednosti'][$rowS['vre_id']]['variable'] = $rowS['vre_variable'];
				$variable_marza['vrednosti'][$rowS['vre_id']]['naslov'] = $rowS['vre_naslov'];
			}			
			// Letni promet
			elseif($rowS['spr_variable'] == 'emLP'){
				
				$variable_lp['naslov'] = strip_tags($rowS['spr_naslov']);
				$variable_lp['id'] = $rowS['spr_id'];
			}
			// Ostali
			else{
				
				// ID-ji vprasanj za query
				if(!isset($variables[$rowS['spr_id']]['variable']))
					$spr_ids .= $rowS['spr_id'].',';
				
				$variables[$rowS['spr_id']]['naslov'] = strip_tags($rowS['spr_naslov']);
				$variables[$rowS['spr_id']]['variable'] = $rowS['spr_variable'];
				
				$variables[$rowS['spr_id']]['vrednosti'][$rowS['vre_id']]['variable'] = $rowS['vre_variable'];
				$variables[$rowS['spr_id']]['vrednosti'][$rowS['vre_id']]['naslov'] = $rowS['vre_naslov'];
			}
		}
		$spr_ids = substr($spr_ids, 0, -1);		

		// Loop cez vse respondente s statusom 6 - vsak je svoj bubble
		$data = array();
		$sum = 0;
		$cnt = 0;
		$sqlAnswers = sisplet_query("SELECT * FROM srv_data_vrednost_active WHERE usr_id='".get('usr_id')."' AND spr_id IN (".$spr_ids.")");
		while($rowAnswers = mysqli_fetch_array($sqlAnswers)){

			// Marzo normalno preberemo
			if($rowAnswers['spr_id'] == $variable_marza['id']){
				$value = $variable_marza['vrednosti'][$rowAnswers['vre_id']]['variable'];
				$data['marza'] = $value;
				
				$variable_marza['value'] = $rowAnswers['vre_id'];
			}
			// Pri ostalih racunamo povprecje
			else{
				$value = $variables[$rowAnswers['spr_id']]['vrednosti'][$rowAnswers['vre_id']]['variable'];
				$sum += (int)$value;
				$cnt++;
				
				$variables[$rowAnswers['spr_id']]['value'] = $rowAnswers['vre_id'];
			}
		}
		$data['excell'] = round($sum/$cnt, 1);	
		
		// Posebej pridobimo tudi letni promet
		$sqlAnswers = sisplet_query("SELECT text FROM srv_data_text_active WHERE usr_id='".get('usr_id')."' AND spr_id='".$variable_lp['id']."'");
		$rowAnswers = mysqli_fetch_array($sqlAnswers);
		$data['letni_promet'] = $rowAnswers['text'];
		$variable_lp['value'] = $rowAnswers['text'];
		
		// Max radius=80 (vrednost 1000), min radius=8 (vrednost 100)
		$radius = $data['letni_promet'];
		
		// Include knjiznice Chart.JS
		echo '<script src="'.self::$site_url.'/main/survey/js/ChartJS/Chart.min.js"></script>';
		echo '<script src="'.self::$site_url.'/main/survey/js/ChartJS/chart_init.js?v=2"></script>';
		
		// Izris grafa
		echo '<div class="spremenljivka">';
		echo '<canvas id="excell_matrix_chart" class="chart"></canvas>';		
		echo '<script>$(document).ready(function(){ init_excell_matrix('.$data['excell'].', '.$data['marza'].', '.$radius.'); })</script>';
		echo '</div>';

		
		// Izrisemo se seznam vprasanj		
		foreach($variables as $spr_id => $spremenljivka){
			
			echo '<div class="spremenljivka">';			
			echo '	<div class="naslov">'.$spremenljivka['naslov'].'</div>';	
			echo '	<div class="variable_holder">';	
			foreach($spremenljivka['vrednosti'] as $vre_id => $vrednost){
				echo '		<div class="variabla" '.($variables[$spr_id]['value'] == $vre_id ? ' style="font-weight:bold; color:red;"' : '').'>'.$vrednost['naslov'].'</div>';
			}	
			echo '	</div>';	
			echo '</div>';
		}
		
		// Marza
		echo '<div class="spremenljivka">';				
		echo '	<div class="naslov">'.$variable_marza['naslov'].'</div>';		
		echo '	<div class="variable_holder">';	
		foreach($variable_marza['vrednosti'] as $vre_id => $vrednost){
			echo '		<div class="variabla" '.($variable_marza['value'] == $vre_id ? ' style="font-weight:bold; color:red;"' : '').'>'.$vrednost['naslov'].'</div>';
		}	
		echo '	</div>';		
		echo '</div>';
		
		// Letni promet
		echo '<div class="spremenljivka">';				
		echo '	<div class="naslov">'.$variable_lp['naslov'].'</div>';		
		echo '	<div class="variable_holder">';	
		echo '		<div class="variabla" style="font-weight:bold; color:red;">'.$variable_lp['value'].'</div>';
		echo '	</div>';		
		echo '</div>';
    }


    // SKAVTI - prikaze povzetek in graf za njihovo anketo
    public function displaySkavtiAnswers(){
        
        // Stevilo top pohval in izziv, ki jih izpisemo v zakljucku
        $max_odgovorov = 3;

        $vprasanja = array();
        $spr_ids = '';

        // Loop cez vsa ustrezna vprasanja in njihove vrednosti (jih zakesiramo)
		$sqlS = sisplet_query("SELECT s.id AS spr_id, s.naslov AS spr_naslov, s.variable AS spr_variable
								FROM srv_spremenljivka s, srv_grupa g
								WHERE g.ank_id='".get('anketa')."' AND s.gru_id=g.id 
									AND s.variable LIKE 'R%'
                            ");
		if (!$sqlS) echo mysqli_error($GLOBALS['connect_db']);
		while($rowS = mysqli_fetch_array($sqlS)){

            $vprasanje_number = substr($rowS['spr_variable'], 1);
            $vprasanja[$rowS['spr_id']] = $vprasanje_number;

            // ID-ji vprasanj za query
            $spr_ids .= $rowS['spr_id'].',';
        }
        
		$spr_ids = substr($spr_ids, 0, -1);		


		// Loop cez vse response za vprasanja
		$pohvale = array();
		$izzivi = array();
		$sqlAnswers = sisplet_query("SELECT vd.*, v.naslov, v.variable
                                        FROM srv_data_vrednost_active vd, srv_vrednost v
                                        WHERE vd.usr_id='".get('usr_id')."' AND vd.spr_id IN (".$spr_ids.")
                                            AND v.id=vd.vre_id
                                    ");
		while($rowAnswers = mysqli_fetch_array($sqlAnswers)){

            // Pohvala
            if((int)$rowAnswers['variable'] >= 1 && (int)$rowAnswers['variable'] <= 199){
                $pohvale[(int)$rowAnswers['variable']] = $rowAnswers;
            }
            // Izziv
            elseif((int)$rowAnswers['variable'] >= 201 && (int)$rowAnswers['variable'] <= 399){
                $izzivi[(int)$rowAnswers['variable']] = $rowAnswers;
            }
		}

        // Sortiramo po velikosti
        ksort($pohvale, SORT_NUMERIC);
        ksort($izzivi, SORT_NUMERIC);


        // Loop cez komentarje v nagovorih
        $pohvale_besedilo = array();
        $izzivi_besedilo = array();
		$sqlS = sisplet_query("SELECT s.id AS spr_id, s.naslov AS spr_naslov, s.variable AS spr_variable
								FROM srv_spremenljivka s, srv_grupa g
								WHERE g.ank_id='".get('anketa')."' AND s.gru_id=g.id 
									AND (s.variable LIKE 'P%' OR s.variable LIKE 'G%')
                            ");
		if (!$sqlS) echo mysqli_error($GLOBALS['connect_db']);
		while($rowS = mysqli_fetch_array($sqlS)){

            $tip = substr($rowS['spr_variable'], 0, 1);
            $vprasanje_number = substr($rowS['spr_variable'], 1);
            $naslov = $rowS['spr_naslov'];

            // Pohvala
            if($tip == 'P'){
                $pohvale_besedilo[$vprasanje_number] = $naslov;
            }
            // Izziv
            elseif($tip == 'G'){
                $izzivi_besedilo[$vprasanje_number] = $naslov;
            }
        }
        

        // Izrisemo seznam vprasanj s pohvalami
        echo '<h2 style="padding-left: 20px;">POHVALE</h2>';

        // Nagovor za pohvale
        $sqlNagovor = sisplet_query("SELECT s.naslov, s.variable 
                                        FROM srv_spremenljivka s, srv_grupa g
                                        WHERE g.ank_id='".get('anketa')."' AND s.gru_id=g.id AND s.variable='pohvale'
                                  ");
		$rowNagovor = mysqli_fetch_array($sqlNagovor);
        echo '<div class="spremenljivka" style="border-bottom:0;"><div class="naslov">'.$rowNagovor['naslov'].'</div></div>';
        
        $i = 1;
		foreach($pohvale as $pohvala_vrednost => $vrednost){
            
            if($i > $max_odgovorov)
                break;

            $spr_id = $vrednost['spr_id'];
            $vprasanje_number = $vprasanja[$spr_id];

            echo '<div class="spremenljivka">';			        
            echo '	<div class="naslov">'.$pohvale_besedilo[$vprasanje_number].'</div>';	            
            echo '</div>';
            
            $i++;
        }
        
        // Izrisemo seznam vprasanj z izzivi
        echo '<br><h2 style="padding-left: 20px;">IZZIVI</h2>';

        // Nagovor za izzive
        $sqlNagovor = sisplet_query("SELECT s.naslov, s.variable 
                                        FROM srv_spremenljivka s, srv_grupa g
                                        WHERE g.ank_id='".get('anketa')."' AND s.gru_id=g.id AND s.variable='izzivi'
                                    ");     
        $rowNagovor = mysqli_fetch_array($sqlNagovor);
        echo '<div class="spremenljivka" style="border-bottom:0;"><div class="naslov">'.$rowNagovor['naslov'].'</div></div>';

        $i = 1;
		foreach($izzivi as $izziv_vrednost => $vrednost){
            
            if($i > $max_odgovorov)
                break;

            $spr_id = $vrednost['spr_id'];
            $vprasanje_number = $vprasanja[$spr_id];

            echo '<div class="spremenljivka">';			   
            echo '	<div class="naslov">'.$izzivi_besedilo[$vprasanje_number].'</div>';	        
            echo '</div>';
            
            $i++;
        }
        
        $this->displaySkavtiRadar();
    }

    // SKAVTI - prikaze graf pajkovo mrezo
    private function displaySkavtiRadar(){

        // Include knjiznice Chart.JS
		echo '<script src="'.self::$site_url.'/main/survey/js/ChartJS/Chart.min.js"></script>';
        echo '<script src="'.self::$site_url.'/main/survey/js/ChartJS/chart_init.js?v=3"></script>';
        

        // Priprava podatkov za radar 
        $radar_data = array();
        $sqlB = sisplet_query("SELECT i.label, i.id
                                FROM srv_if i, srv_branching b
                                WHERE b.ank_id='".get('anketa')."' AND i.id=b.element_if
                                    AND i.tip='1'
                            ");
        if (!$sqlB) echo mysqli_error($GLOBALS['connect_db']);

        // Za graf rabimo vsaj 3 ogljisca
        if(mysqli_num_rows($sqlB) < 3)
            return;

        // Loop cez bloke (stranica radarja)
        while($rowB = mysqli_fetch_array($sqlB)){

            // Dobimo vsa ustrezna vprasanja z odgovori v tem bloku
            $sqlQ = sisplet_query("SELECT s.id AS spr_id, s.variable AS spr_variable, v.naslov, v.variable, vd.*
                                    FROM srv_branching b, srv_spremenljivka s, srv_vrednost v, srv_data_vrednost_active vd
                                    WHERE b.ank_id='".get('anketa')."' AND b.parent='".$rowB['id']."' AND b.element_spr=s.id
                                        AND s.variable LIKE 'R%'
                                        AND vd.usr_id='".get('usr_id')."' AND vd.spr_id=s.id
                                        AND v.id=vd.vre_id
                                ");
            if (!$sqlQ) echo mysqli_error($GLOBALS['connect_db']);

            // Loop cez vprasanja in odgovore v bloku
            $count_answers_pohvale = 0;
            $count_answers_izzivi = 0;
            while($rowQ = mysqli_fetch_array($sqlQ)){

                // Pohvala
                if((int)$rowQ['variable'] >= 1 && (int)$rowQ['variable'] <= 200){
                    $count_answers_pohvale++;
                }
                // Izziv
                elseif((int)$rowQ['variable'] >= 201 && (int)$rowQ['variable'] <= 400){
                    $count_answers_izzivi++;
                }
            }

            $count_answers_all = (int)$count_answers_pohvale + (int)$count_answers_izzivi;
            if($count_answers_all > 0){
                $radar_data[$rowB['label']]['all'] = $count_answers_all;
                $radar_data[$rowB['label']]['pohvale'] = $count_answers_pohvale;
                $radar_data[$rowB['label']]['izzivi'] = $count_answers_izzivi;
            }
        }
        //echo '<pre>' . var_export($radar_data, true) . '</pre>';

        $labels = array();
        $values = array();
        $i = 0;
        foreach($radar_data as $radar_label => $radar_values){

            $labels[$i] = $radar_label;
            $pohvale[$i] = round($radar_values['pohvale'] / $radar_values['all'] * 10, 2);
            $izzivi[$i] =  round($radar_values['izzivi'] / $radar_values['all'] * 10, 2);

            $i++;
        }

        $json_labels = json_encode($labels);
        $json_pohvale = json_encode($pohvale);
        $json_izzivi = json_encode($izzivi);

        echo '<br><h2 style="padding-left: 20px;">Pajkova mreža</h2>';

        // Nagovor za mrezo
        $sqlNagovor = sisplet_query("SELECT s.naslov, s.variable 
                                        FROM srv_spremenljivka s, srv_grupa g
                                        WHERE g.ank_id='".get('anketa')."' AND s.gru_id=g.id AND s.variable='mreza'
                                    ");     
        $rowNagovor = mysqli_fetch_array($sqlNagovor);
        echo '<div class="spremenljivka" style="border-bottom:0;"><div class="naslov">'.$rowNagovor['naslov'].'</div></div>';

        // Izris grafa
        echo '<div class="spremenljivka radar_chart" id="skavti_radar_chart_holder">';			
        
        echo '<canvas id="skavti_radar_chart" class="chart"></canvas>';		
		echo '<script>$(document).ready(function(){ init_skavti_radar('.$json_labels.', '.$json_pohvale.', '.$json_izzivi.'); })</script>';
        
        echo '</div>';
    }


    /**
     * prikaze lepo obvestilo o napaki (anketa je zaključena itd...)
     *
     * @param mixed $text
     */
    public function displayNapaka($text)
    {
        Header::getInstance()->header();
        
		$anketa = get('anketa');

        echo '<div class="outercontainer_holder"><div class="outercontainer_holder_top"></div>';
        echo '<div id="outercontainer">';

        echo '  <div class="outercontainer_header"></div>';
        
        
        echo '  <div id="container">';

        $this->logo();

        echo '      <h1>' . Helper::getInstance()->displayAkronim() . '</h1>';

        echo '      <div class="grupa">';
        echo '          <div class="spremenljivka">';
        echo '              <p>' . $text . '</p>';
        echo '          </div>';
        echo '      </div>'; // -grupa

        echo '  </div>'; // -container

        Body::getInstance()->displayFooterNote();

        echo '</div>';  // -outercontainer
        echo '<div class="outercontainer_holder_bottom"></div></div>';  // -outercontainer_holder
    }

    /**
     * Prikaze zavihke za bloke, ce obstajajo
     *
     */
    public function display_tabs()
    {

        $sql = sisplet_query("SELECT * FROM srv_if i, srv_branching b WHERE i.tab='1' AND i.tip='1' AND i.id=b.element_if AND b.ank_id='" . get('anketa') . "' ORDER BY b.parent, b.vrstni_red");
        if (mysqli_num_rows($sql) > 0) {

            echo '<div class="tabs">';

            $i = 0;
            while ($row = mysqli_fetch_array($sql)) {
                if ($i++ != 0) echo ' | ';
                $label = ($row['label'] == '' ? self::$lang['srv_blok'] . ' (' . $row['number'] . ')' : $row['label']);
                echo '<a href="#" onclick="submitForm(\'' . $row['id'] . '\'); return false;" ' . (Ajax::getInstance()->ajax_grupa_for_if($row['id']) == get('grupa') ? ' class="active"' : '') . '>' . $label . '</a> ';
            }

            echo '</div>';

        }
    }

    /**
     * @desc konstruktor
     */
    public function PrintSurvey()
    {

        if (isset($_GET['anketa'])) {
            save('anketa', $_GET['anketa']);

            $rowa = SurveyInfo::getInstance()->getSurveyRow();

            // uvodni nagovor
            if ($rowa['show_intro'] != 0) {
                Body::getInstance()->displayIntroduction();
            }

            // prikažemo ankete
            do {
                save('grupa', Find::getInstance()->findNextGrupa());

                Body::getInstance()->displayAnketa();
            } while (get('grupa') != Find::getInstance()->findNextGrupa() && Find::getInstance()->findNextGrupa() > 0);

            // prikažemo konec
            Body::getInstance()->displayKonec();
        } else
            echo 'Ni podatkov o anketi!';
    }


	/**
     * @desc prikaze chat okno za tawk chat, ce je modul vklopljen (js koda)
     */
	public function displayChatTAWK(){
				
		echo '<div style="display:none;">';
		
		$sql = sisplet_query("SELECT code, chat_type FROM srv_chat_settings WHERE ank_id='".get('anketa')."'");
		if(mysqli_num_rows($sql) > 0){
			
			$row = mysqli_fetch_array($sql);

			if($row['code'] != ''){		
			
				// JS koda za widget
				echo $row['code'];
				
				echo "<script type='text/javascript'>";				
				
				// Dodatno se poslje tudi recnum in usr_id
				$sqlu = sisplet_query("SELECT id, recnum FROM srv_user WHERE id = '".get('usr_id')."'");
				$rowu = mysqli_fetch_array($sqlu);
				echo "Tawk_API = Tawk_API || {}; Tawk_API.visitor = {name : 'Recnum ".$rowu['recnum']." (".get('usr_id').")', email : ''}; \n";
					
				// Nastavimo za span class="tawk-chat-activation" tage
				if($row['chat_type'] == '1' || $row['chat_type'] == '2'){
					
					// Na klik prikazemo chat
					echo "$('.tawk-chat-activation').click(function() { Tawk_API.showWidget(); Tawk_API.maximize(); }); \n";
						
					// Dodatna nastavitev, da je chat po defaultu skrit
					echo "Tawk_API.onLoad = function(){ Tawk_API.hideWidget();
														Tawk_API.setAttributes({
															'recnum'  : '".$rowu['recnum']."',
															'user-id' : '".get('usr_id')."'}, function(error){}); 
					}; \n";
				}				
				else{
					// Dodatno še shranimo recnum in id, ce user slucajno spremeni ime
					echo "Tawk_API.onLoad = function(){ Tawk_API.setAttributes({
															'recnum'  : '".$rowu['recnum']."',
															'user-id' : '".get('usr_id')."'}, function(error){}); 
													};";
				}
				echo "</script>";
			}
		}
		
		echo '</div>';
	}
}