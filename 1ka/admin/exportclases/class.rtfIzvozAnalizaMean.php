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
class RtfIzvozAnalizaMean {

	var $anketa;// = array();			// trenutna anketa
	var $grupa = null;				// trenutna grupa
	var $usrId = null;			// trenutni user
	var $spremenljivka;		// trenutna spremenljivka
	var $usr_id;			// ID trenutnega uporabnika
	var $printPreview = false;	// ali kli?e konstruktor
	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $rtf;

	public static $meansClass = null;		//means class
	
	var $meanData1;
	var $meanData2;

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
		
		//ustvarimo mean objekt in mu napolnimo variable (var1, var2, checkboxi)
		$this->meansClass = new SurveyMeans($anketa);	
		
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
		
		$this->rtf->draw_title($lang['export_analisys_means']);
		
		# polovimo nastavtve missing profila
		//SurveyConditionProfiles:: getConditionString();
		
		
		$this->meanData1 = $this->sessionData['means']['means_variables']['variabla1'];
		$this->meanData2 = $this->sessionData['means']['means_variables']['variabla2'];
		
		$means = array();
		# če ne uporabljamo privzetega časovnega profila izpišemo opozorilo
		//$doNewLine = SurveyTimeProfiles :: printIsDefaultProfile(false);

		# če imamo filter ifov ga izpišemo
		//$doNewLine = SurveyConditionProfiles:: getConditionString($doNewLine );

		# če imamo filter spremenljivk ga izpišemo
		//$doNewLine = SurveyVariablesProfiles:: getProfileString($doNewLine , true) || $doNewLine;
		
		if ($this->meanData1 !== null && $this->meanData2 !== null) {
			$variables1 = $this->meanData2;
			$variables2 = $this->meanData1;
			$c1=0;
			$c2=0;
			
			if(is_array($variables2) && count($variables2) > 0){
				#prikazujemo ločeno
				if ($this->sessionData['means']['meansSeperateTables'] == true || $this->sessionData['mean_charts']['showChart'] == '1') {
					foreach ($variables2 AS $v_second) {
						if (is_array($variables1) && count($variables1) > 0) {
							foreach ($variables1 AS $v_first) {
								$_means = $this->meansClass->createMeans($v_first, $v_second);
								if ($_means != null) {
									$means[$c1][0] = $_means;
								}
								$c1++;
							}
						}
					}
				}
				#prikazujemo skupaj
				else {
					foreach ($variables2 AS $v_second) {
						if (is_array($variables1) && count($variables1) > 0) {
							foreach ($variables1 AS $v_first) {
								$_means = $this->meansClass->createMeans($v_first, $v_second);
								if ($_means != null) {
									$means[$c1][$c2] = $_means;
								}
								$c2++;
							}
						}
						$c1++;
						$c2=0;
					}
				}
			}
			
			
			if (is_array($means) && count($means) > 0) {
			
				$count = 0;
				foreach ($means AS $mean_sub_grup) {

					if($this->sessionData['mean_charts']['showChart'] == '1'){
						if($count > 0){
							$this->rtf->new_page();
							$this->rtf->new_line(2);
						}
						
						$this->displayMeansTable($mean_sub_grup);
						$this->displayChart($count);
					}
					else{
						if($count != 0)
							$this->rtf->new_line(3);
						
						$this->displayMeansTable($mean_sub_grup);
					}
					
					$count++;
				}
			}
		}	
	}

	public function displayMeansTable($_means) {
		global $lang;
		
		#število vratic in število kolon
		$cols = count($_means);
		# preberemo kr iz prvega loopa
		$rows = count($_means[0]['options']);

		// sirina ene celice
		$singleWidth = round( 180 / $cols / 2 );

		//nastavitve tabele - (sirine celic, border...)
		$defw_full = 10500;
		$defw_part = 5700;
		$defw_part2 = 8500;
		//$defw_part3 = 8500;
		//izracun sirine ene celice
		$singleWidth = floor( $defw_part2 / ($cols*2) );	

		$borderB = '\clbrdrb\brdrs\brdrw10';
		$borderT = '\clbrdrt\brdrs\brdrw10';
		$borderLR = '\clbrdrl\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
		$border = '\clbrdrb\brdrs\brdrw10\clbrdrl\brdrs\brdrw10\clbrdrt\brdrs\brdrw10\clbrdrr\brdrs\brdrw10';
		//$align = ($arrayParams['align']=='center' ? '\qc' : '\ql');
		$bold = '\b';
		
		
		// zacetek tabele
		$this->rtf->MyRTF .= $this->rtf->_font_size(16);
		$this->rtf->MyRTF .= "{\par";
		
		
		// prva vrstica
		$tableHeader = '\trowd\trql\trrh400';
				
		$label2 = $this->meansClass->getSpremenljivkaTitle($_means[0]['v2']);
		$table = '\clvertalc'.$borderLR.$borderT.'\clvmgf\cellx'.( $defw_part );	
		$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText($label2). '\qc\cell';					
				
		for ($i = 0; $i < $cols; $i++) {

			$label1 = $this->meansClass->getSpremenljivkaTitle($_means[$i]['v1']);

			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + (($i+1) * 2 * $singleWidth) );	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($label1). '\qc\cell';
		}	
		
		$tableEnd .= '\pard\intbl\row';
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
		
		// druga vrstica
		$tableHeader = '\trowd\trql\trrh400';
					
		$table = '\clvertalc'.$borderLR.'\clvmrg\cellx'.( $defw_part );	
		$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText('&nbsp; '). '\qc\cell';			

		for ($i = 0; $i < $cols; $i++) {

			$label1 = $this->meansClass->getSpremenljivkaTitle($_means[$i]['v1']);

			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + (((($i+1) * 2) - 1) * $singleWidth) );	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($lang['srv_means_label']). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + (($i+1) * 2 * $singleWidth) );	
			$tableEnd .= '\pard\intbl'.$bold.' '.$this->encodeText($lang['srv_means_label4']). '\qc\cell';
		}	
		
		$tableEnd .= '\pard\intbl\row';
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);


		// vrstice s podatki
		if (count($_means[0]['options']) > 0) {
			foreach ($_means[0]['options'] as $ckey2 =>$crossVariabla2) {
				
				$tableHeader = '\trowd\trql\trrh400';
						
				$variabla = $crossVariabla2['naslov'];
				# če ni tekstovni odgovor dodamo key
				if ($crossVariabla2['type'] !== 't' ) {
					if ($crossVariabla2['vr_id'] == null) {
						$variabla .= ' ( '.$ckey2.' )';
					} else {
						$variabla .= ' ( '.$crossVariabla2['vr_id'].' )';
					}
				}
				$table = '\clvertalc'.$border.'\cellx'.( $defw_part );	
				$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText($variabla). '\b0\qc\cell';

				# celice z vsebino
				for ($i = 0; $i < $cols; $i++) {

					$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + (((($i+1) * 2) - 1) * $singleWidth) );	
					$tableEnd .= '\pard\intbl '.$this->encodeText($this->meansClass->formatNumber($_means[$i]['result'][$ckey2], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))). '\qc\cell';
					$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + (($i+1) * 2 * $singleWidth) );	
					$tableEnd .= '\pard\intbl '.$this->encodeText((int)$_means[$i]['sumaVrstica'][$ckey2]). '\qc\cell';
				}
				
				$tableEnd .= '\pard\intbl\row';
				$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
			}
		}
		
		// SKUPAJ
		$tableHeader = '\trowd\trql\trrh400';
			
		$table = '\clvertalc'.$border.'\cellx'.( $defw_part );	
		$tableEnd = '\pard\intbl'.$bold.' '.$this->encodeText($lang['srv_means_label3']). '\b0\qc\cell';

		for ($i = 0; $i < $cols; $i++) {

			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + (((($i+1) * 2) - 1) * $singleWidth) );	
			$tableEnd .= '\pard\intbl '.$this->encodeText($this->meansClass->formatNumber($_means[$i]['sumaMeans'], SurveyDataSettingProfiles :: getSetting('NUM_DIGIT_RESIDUAL'))). '\qc\cell';
			$table .= '\clvertalc'.$border.'\cellx'.( $defw_part + (($i+1) * 2 * $singleWidth) );	
			$tableEnd .= '\pard\intbl '.$this->encodeText((int)$_means[$i]['sumaSkupna']). '\qc\cell';
		}

		$tableEnd .= '\pard\intbl\row';
		$this->rtf->MyRTF .= $this->rtf->enkaEncode($tableHeader.$table.$tableEnd);
		
		
		// konec tabele
		$this->rtf->MyRTF .= "}";
		$this->rtf->new_line(1);
	}
		
	function displayChart($counter){
		global $lang;

		$variables1 = $this->meanData1;
		$variables2 = $this->meanData2;
		
		$pos1 = floor($counter / count($variables2));
		$pos2 = $counter % count($variables2);
		
		$chartID = implode('_', $variables1[$pos1]).'_'.implode('_', $variables2[$pos2]);
		$chartID .= '_counter_'.$counter;


		$settings = $this->sessionData['mean_charts'][$chartID];
		$imgName = $settings['name'];

		// IZRIS GRAFA
		$this->rtf->new_line();
		$this->rtf->new_line();
		$this->rtf->new_line();
		
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
