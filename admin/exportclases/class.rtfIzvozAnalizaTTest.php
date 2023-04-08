<?php

	global $site_path;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');
	include_once('../exportclases/class.rtfIzvozAnalizaFunctions.php');
	require_once("class.enka.rtf.php");

	define("FNT_TIMES", "Times New Roman", true);
	define("FNT_ARIAL", "Arial", true);

	define("FNT_MAIN_TEXT", FNT_TIMES, true);
	define("FNT_QUESTION_TEXT", FNT_TIMES, true);
	define("FNT_HEADER_TEXT", FNT_TIMES, true);

	define("FNT_MAIN_SIZE", 12, true);
	define("FNT_QUESTION_SIZE", 10, true);
	define("FNT_HEADER_SIZE", 10, true);
	
	define("M_ANALIZA_DESCRIPTOR", "descriptor", true);
	define("M_ANALIZA_FREQUENCY", "frequency", true);
	define("ALLOW_HIDE_ZERRO_REGULAR", false); // omogo�imo delovanje prikazovanja/skrivanja ni�elnih vnosti za navadne odgovore
	define("ALLOW_HIDE_ZERRO_MISSING", true); // omogo�imo delovanje prikazovanja/skrivanja ni�elnih vnosti za missinge


/** Class za generacijo rtf-a
 */
class RtfIzvozAnalizaTTest {

	var $anketa;// = array();			// trenutna anketa
	var $grupa = null;				// trenutna grupa
	var $usrId = null;			// trenutni user
	var $spremenljivka;		// trenutna spremenljivka
	var $usr_id;			// ID trenutnega uporabnika
	var $printPreview = false;	// ali kli?e konstruktor
	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $rtf;

	public static $ttestClass = null;		//ttest class
	
	var $ttestData1;
	var $ttestData2;
	
	var $ttestVars;

	var $sessionData;			// podatki ki so bili prej v sessionu - za nastavitve, ki se prenasajo v izvoze...
	
	
	/**
    * @desc konstruktor
    */
	function __construct ($anketa = null)
	{
		global $site_path;
		global $global_user_id;
		
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) )
		{
			$this->anketa['id'] = $anketa;

			// create new RTF document
			$this->rtf = new enka_RTF(true);
		}
		else
		{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}
		
		
		$_GET['a'] = A_ANALYSIS;
				
		// preberemo nastavitve iz baze (prej v sessionu) 
		SurveyUserSession::Init($this->anketa['id']);
		$this->sessionData = SurveyUserSession::getData();
		
		// ustvarimo ttest objekt
		$this->ttestClass = new SurveyTTest($anketa);
		
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init())
		{
			$this->anketa['uid'] = $global_user_id;
			SurveyUserSetting::getInstance()->Init($this->anketa['id'], $this->anketa['uid']);
		}
		else
			return false;
		// ce smo prisli do tu je vse ok
		$this->pi['canCreate'] = true;
		return true;
	}

	function getAnketa()
	{ return $this->anketa['id']; }

	function checkCreate()
	{
		return $this->pi['canCreate'];
	}

	function getFile($fileName)
	{
		$this->rtf->display($fileName = "analiza.rtf",true);
	}

	function init()
	{
		global $lang;
		
		// dodamo avtorja in naslov
		$this->rtf->WriteTitle();
		$this->rtf->WriteHeader($this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()), 'left');
		$this->rtf->WriteHeader($this->encodeText(SurveyInfo::getInstance()->getSurveyAkronim()), 'right');
		$this->rtf->WriteFooter($lang['page']." {PAGE} / {NUMPAGES}", 'right');
		$this->rtf->set_default_font(FNT_TIMES, FNT_MAIN_SIZE);
		return true;
	}

	function createRtf()
	{
		global $site_path;
		global $lang;

		// izpisemo prvo stran
		//$this->createFrontPage();
		
		$this->rtf->draw_title($lang['export_analisys_ttest']);
		
			
		if (count($this->sessionData['ttest']['sub_conditions']) > 1 ) {
			$variables1 = $this->ttestClass->getSelectedVariables();
			if (count($variables1) > 0) {
				foreach ($variables1 AS $v_first) {
					$ttest = null;
					$ttest = $this->ttestClass->createTTest($v_first, $this->sessionData['ttest']['sub_conditions']);

					$this->rtf->new_line(2);
					
					$this->displayTTestTable($ttest);
					
					if($this->sessionData['ttest_charts']['showChart'] == '1'){
						$this->displayChart();
					}
				}
			}
		}
	}

	public function displayTTestTable($ttest) {
		global $lang;
		
		# preverimo ali imamo izbrano odvisno spremenljivko
		$spid1 = $this->sessionData['ttest']['variabla'][0]['spr'];
		$seq1 = $this->sessionData['ttest']['variabla'][0]['seq'];
		$grid1 = $this->sessionData['ttest']['variabla'][0]['grd'];
		
		
		if (is_array($ttest) && count($ttest) > 0 && (int)$seq1 > 0) {
			
			$spr_data_1 = $this->ttestClass->_HEADERS[$spid1];
			if ($grid1 == 'undefined') {

				# imamp lahko več variabel
				$seq = $seq1;
				foreach ($spr_data_1['grids'] as $gkey => $grid ) {
						
					foreach ($grid['variables'] as $vkey => $variable) {
						$sequence = $variable['sequence'];
						if ($sequence == $seq) {
							$sprLabel1 = '('.$variable['variable'].') '. $variable['naslov'];
						}
					}
				}
			} else {
				# imamo subgrid
				$sprLabel1 = '('.$spr_data_1['grids'][$grid1]['variable'].') '. $spr_data_1['grids'][$grid1]['naslov'];
			}
				
			# polovio labele
			$spid2 = $this->sessionData['ttest']['spr2'];
			$sprLabel2 =  trim(str_replace('&nbsp;','',$this->sessionData['ttest']['label2']));
			$label1 = $this->ttestClass->getVariableLabels($this->sessionData['ttest']['sub_conditions'][0]);
			$label2 = $this->ttestClass->getVariableLabels($this->sessionData['ttest']['sub_conditions'][1]);
			
			$this->ttestVars = array($sprLabel1, $sprLabel2);
			
			$borderB = '\clbrdrb\brdrs\brdrw10';
			$borderT = '\clbrdrt\brdrs\brdrw10';
			$borderLR = '\clbrdrl\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
			$border = '\clbrdrb\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrt\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
			//$align = ($arrayParams['align']=='center' ? '\qc' : '\ql');
			$bold = '\b';
			
			//nastavitve tabele - (sirine celic, border...)
			$defw_full = 13500;
			$defw_part = 5000;
			$defw_part2 = 9000;
			$defw_part3 = 1000;

			
			// zacetek tabele
			$this->rtf->MyRTF .= $this->rtf->_font_size(16);
			$this->rtf->MyRTF .= "{\par";		
			
			
			// prva vrstica
			$tableHeader = '\trowd\trql\trrh400';
						
			$table = '\clvertalc'.$borderLR.$borderT.'\clvmgf\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText($sprLabel2). '\qc\cell';			
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part2 );	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($sprLabel1). '\qc\cell';		
			$tableEnd .= '\pard\intbl\row';
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
			
				
			// druga vrstica
			$tableHeader = '\trowd\trql\trrh400';
						
			$table = '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';			
				
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part3 );	
			$tableEnd .= '\pard\intbl\b0 '.$this->encodeText('n'). '\qc\cell';	
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 2*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->encodeText('x'). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 3*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->encodeText('s^2'). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 4*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->encodeText('se(x)'). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 5*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->encodeText('±1,96×se(x)'). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 6*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->encodeText('d'). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 7*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->encodeText('se(d)'). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 8*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->encodeText('Sig.'). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 9*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->encodeText('t'). '\qc\cell';
			
			$tableEnd .= '\pard\intbl\row';
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			

			// vrstici s podatki
			$tableHeader = '\trowd\trql\trrh400';
						
			$table = '\clvertalc'.$borderLR.$borderB.'\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText($label1). '\qc\cell';			
				
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part3 );	
			$tableEnd .= '\pard\intbl\b0 '.$this->formatNumber($ttest[1]['n'], 0). '\qc\cell';	
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 2*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->formatNumber($ttest[1]['x'], 3). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 3*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->formatNumber($ttest[1]['s2'], 3). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 4*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->formatNumber($ttest[1]['se'], 3). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 5*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->formatNumber($ttest[1]['margin'], 3). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.'\clvmgf\cellx'.( $defw_part + 6*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->formatNumber($ttest['d'], 3). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.'\clvmgf\cellx'.( $defw_part + 7*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->formatNumber($ttest['sed'], 3). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.'\clvmgf\cellx'.( $defw_part + 8*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->formatNumber($ttest['sig'], 3). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.'\clvmgf\cellx'.( $defw_part + 9*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->formatNumber($ttest['t'], 3). '\qc\cell';
			
			$tableEnd .= '\pard\intbl\row';
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			
			
			$tableHeader = '\trowd\trql\trrh400';
						
			$table = '\clvertalc'.$borderLR.$borderB.'\cellx'.( $defw_part );	
			$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText($label2). '\qc\cell';			
				
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + $defw_part3 );	
			$tableEnd .= '\pard\intbl\b0 '.$this->formatNumber($ttest[2]['n'], 0). '\qc\cell';	
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 2*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->formatNumber($ttest[2]['x'], 3). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 3*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->formatNumber($ttest[2]['s2'], 3). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 4*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->formatNumber($ttest[2]['se'], 3). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + 5*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->formatNumber($ttest[2]['margin'], 3). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part + 6*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->encodeText('&nbsp; '). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part + 7*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->encodeText('&nbsp; '). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part + 8*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->encodeText('&nbsp; '). '\qc\cell';
			$table .= '\clvertalc'.$borderLR.$borderB.'\clvmrg\cellx'.( $defw_part + 9*$defw_part3 );	
			$tableEnd .= '\pard\intbl '.$this->encodeText('&nbsp; '). '\qc\cell';
			
			$tableEnd .= '\pard\intbl\row';
			$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			
			
			// konec tabele
			$this->rtf->MyRTF .= "}";
			$this->rtf->new_line(1);
		}
	}
	
	function displayChart($counter){
		global $lang;

		$tableChart = new SurveyTableChart($this->anketa['id'], $this->ttestClass, 'ttest');
		$tableChart->setTTestChartSession();
		
		// updatamo session iz baze
		$this->sessionData = SurveyUserSession::getData();
		
		$spid1 = $this->sessionData['ttest']['variabla'][0]['spr'];
		$seq1 = $this->sessionData['ttest']['variabla'][0]['seq'];
		$grid1 = $this->sessionData['ttest']['variabla'][0]['grd'];
		$sub1 = $this->sessionData['ttest']['sub_conditions'][0];
		$sub2 = $this->sessionData['ttest']['sub_conditions'][1];
		$chartID = $sub1.'_'.$sub2.'_'.$spid1.'_'.$seq1.'_'.$grid1;
	
		$settings = $this->sessionData['ttest_charts'][$chartID];
		$imgName = $settings['name'];
		
		// IZRIS GRAFA
		$this->rtf->new_line(3);
		
		// Naslov posameznega grafa
		$this->rtf->set_font("Arial Black", 8);		
		$this->rtf->add_text($this->encodeText($lang['srv_chart_ttest_title'].':'), 'center');	
		$this->rtf->new_line();	
		
		$this->rtf->set_font("Arial Black", 8);	
		$title = $this->rtf->bold(1) .$this->ttestVars[0].' / '.$this->ttestVars[1] . $this->rtf->bold(0);
		$this->rtf->add_text($this->encodeText($title), 'center');		
		$this->rtf->new_line();	
		
		$this->rtf->set_font("Times New Roman", 10);
		
		
		$scale = 100;
		
		$this->rtf->add_image('pChart/Cache/'.$imgName, $scale, 'center');
	}	
	 
	 
	/*Skrajsa tekst in doda '...' na koncu*/
	function snippet($text,$length=64,$tail="...")
	{
		$text = trim($text);
		$txtl = strlen($text);
		if($txtl > $length)
		{
			for($i=1;$text[$length-$i]!=" ";$i++)
			{
				if($i == $length)
				{
					return substr($text,0,$length) . $tail;
				}
			}
		$text = substr($text,0,$length-$i+1) . $tail;
		}
		return $text;
	}

	function setUserId($usrId) {$this->anketa['uid'] = $usrId;}
	function getUserId() {return ($this->anketa['uid'])?$this->anketa['uid']:false;}

	function formatNumber($value,$digit=0,$sufix="")
	{
		if ( $value <> 0 && $value != null )
			$result = round($value,$digit);
		else
			$result = "0";
		$result = number_format($result, $digit, ',', '.').$sufix;
	
		return $result;
	}
	
	function encodeText($text)
	{ // popravimo sumnike ce je potrebno
		$text = html_entity_decode($text, ENT_NOQUOTES, 'UTF-8');
		$text = str_replace(array("&scaron;","&#353;","&#269;"),array("�","�","�"),$text);
		return strip_tags($text);
	}
}

?>
