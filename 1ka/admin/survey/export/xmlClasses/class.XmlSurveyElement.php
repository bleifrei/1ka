<?php

/**
 *
 *	Class ki skrbi za izris posameznega vprasanja za vprašalnik
 *
 *
 */

include('../../vendor/autoload.php');

define("MAX_STRING_LENGTH", 60);
define("LINE_BREAK_AT", '7 cm');
define("RADIO_BTN_SIZE", 0.13);
define("CHCK_BTN_SIZE", 0.13);
define("PIC_SIZE_ANS", "\includegraphics[width=3cm]"); 	//slika dolocene sirine
define("DROPDOWN_SIZE", 0.8);

 
class XmlSurveyElement{
	
	public $anketa;				// ID ankete
	public static $spremenljivka;
	public $spremenljivkaParams;
	public $stevilcenje;
	public $showIf = 0;			// izpis if-ov
	public $numbering = 0; 		// ostevillcevanje vprasanj
	public $export_format;
	public $questionText;
	protected $usr_id = null;			// id userja ki je odgovarjal (na katerega so vezani podatki)
	protected $db_table = '';
	protected $loop_id = null;	// id trenutnega loopa ce jih imamo
	protected $userAnswer = array();
	//protected $userDataPresent = array();
	//protected $userDataPresent = 0;
	protected $xml;
	
	
	function __construct($anketa=null, $export_format='', $usr_id=null, $export_subtype='', $xml=null){
		global $site_path, $global_user_id, $admin_type, $lang;
		
		$this->anketa = $anketa;
		
		$this->xml = $xml;
/* 		$this->spremenljivka = $spremenljivka;
		$this->stevilcenje = $stevilcenje; */

		$this->numbering = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_numbering');

		$this->export_format = $export_format;
		
		//$this->usr_id = $_GET['usr_id'];
		$this->usr_id = $usr_id;
			
		
		if ( SurveyInfo::getInstance()->SurveyInit($anketa))
		{
			SurveyUserSetting::getInstance()->Init($anketa, $global_user_id);
			
			if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1)
				$this->db_table = '_active';
		}		
		else{
			return false;			
		}
		
	}
	
	#funkcija, ki pripravi xml za posamezene element vprasalnika glede na tip vprasanja ################################################################
	public function displaySurveyElement($spremenljivke=null, $export_subtype='', $preveriSpremenljivko=null, $loop_id=null){	
		switch ($spremenljivke['tip']){
			case 1: //radio
				return RadioXml::getInstance()->export($spremenljivke, $this->db_table, $preveriSpremenljivko, $export_subtype, $loop_id, $this->xml);
			break;
			case 2: //check
				return CheckboxXml::getInstance()->export($spremenljivke, $this->db_table, $preveriSpremenljivko, $export_subtype, $loop_id, $this->xml);
			break;
			case 3: //select -> radio
				return RadioXml::getInstance()->export($spremenljivke, $this->db_table, $preveriSpremenljivko, $export_subtype, $loop_id, $this->xml);
			break;
			case 6: //multigrid
/* 			case 16:// multicheckbox
			case 19:// multitext
			case 20:// multinumber */
				return MultiGridXml::getInstance()->export($spremenljivke, $this->db_table, $preveriSpremenljivko, $export_subtype, $loop_id, $this->xml);
			break;
			case 21: //besedilo
				return BesediloXml::getInstance()->export($spremenljivke, $this->db_table, $preveriSpremenljivko, $export_subtype, $loop_id, $this->xml);
			break;
			case 7: //stevilo
				return SteviloXml::getInstance()->export($spremenljivke, $this->db_table, $preveriSpremenljivko, $export_subtype, $loop_id, $this->xml);
			break;
			case 8:	//datum
				//return DatumLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $export_subtype, $preveriSpremenljivko, $loop_id);
			break;
			case 17: //ranking
				//return RazvrscanjeLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $export_subtype, $preveriSpremenljivko, $export_data_type, $loop_id);
			break;
			case 18: //vsota
				//return VsotaLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $export_subtype, $preveriSpremenljivko, $loop_id);
			break;
			case 24: // kombinirana tabela
				//return GridMultipleLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $export_subtype, $preveriSpremenljivko, $export_data_type, $loop_id);
			break;			
			case 26: //lokacija
				//return LokacijaLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $export_subtype, $preveriSpremenljivko, $loop_id);
			break;
			case 27: //heatmap
				return HeatmapLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $this->getUserId(), $this->db_table, $export_subtype, $preveriSpremenljivko, $loop_id);
			break;
			case 5:	//nagovor
				//return NagovorLatex::getInstance()->export($spremenljivke, $this->export_format, $this->questionText, $this->fillablePdf, $this->texNewLine, $export_subtype, $preveriSpremenljivko, $loop_id);
			break;
			case 22: //kalkulacija
				//return KalkulacijaLatex::getInstance()->export($spremenljivke, $this->export_format, $this->fillablePdf, $this->texNewLine, $export_subtype, $this->db_table, $this->getUserId(), $loop_id);
			break;
			case 25: //kvota
				//return KvotaLatex::getInstance()->export($spremenljivke, $this->export_format, $this->fillablePdf, $this->texNewLine, $export_subtype, $this->db_table, $this->anketa, $this->getUserId(), $loop_id);
			break;
			case 9: //SN-imena
				//return SNImenaLatex::getInstance()->export($spremenljivke, $this->export_format, $this->fillablePdf, $this->texNewLine, $export_subtype, $this->db_table, $this->anketa, $this->getUserId(), $loop_id);
			break;
		}
	}
	#funkcija, ki pripravi xml za posamezene element vprasalnika glede na tip vprasanja  - konec #######################################################

	
	function writeXmlAttr4Element($xml=null, $attribute=null, $element=null, $writeAttribute=0){
		$this->xml = $xml;		
		if($writeAttribute){
			xmlwriter_write_attribute($this->xml, $attribute, $element);
		}else{
			xmlwriter_start_attribute($this->xml,  $attribute);
		}
		xmlwriter_text($this->xml, $element);
		xmlwriter_end_attribute($this->xml);
	}
	
	function writeXmlElement($xml=null, $text=null, $element=null){
		$this->xml = $xml;
		xmlwriter_start_element($this->xml,  $element);
		xmlwriter_text($this->xml, $text);
		xmlwriter_end_element($this->xml);
	}
	
	/**
	 * prevod za srv_spremenljivka
	 */
	 function srv_language_spremenljivka ($spremenljivka=null) {
		 
		 if ($this->language != -1) {
			$sqll = sisplet_query("SELECT * FROM srv_language_spremenljivka WHERE ank_id='".$this->anketa."' AND spr_id='".$spremenljivka['id']."' AND lang_id='".$this->language."'");
			$rowl = mysqli_fetch_array($sqll);
			
			return $rowl;
		 }
		
		return false;
	 }
	 	
	 /**
	 * vrne prevod za srv_vrednost
	 * 
	 * @param mixed $vrednost
	 */
	 function srv_language_vrednost ($vrednost=null) {

		 if ($this->language != -1) {	
			$sqll = sisplet_query("SELECT * FROM srv_language_vrednost WHERE ank_id='".$this->anketa['id']."' AND vre_id='".$vrednost."' AND lang_id='".$this->language."'");
			$rowl = mysqli_fetch_array($sqll);
			
			if ($rowl['naslov'] != '') return $rowl['naslov'];
		 }
		 
		 return false;	 
	 }
	 
	 /**
	 * vrne prevod za srv_grid
	 * 
	 * @param mixed $vrednost
	 */
	 function srv_language_grid ($spremenljivka=null, $grid=null) {
	 	 
		 if ($this->language != -1) {
			$sqll = sisplet_query("SELECT * FROM srv_language_grid WHERE ank_id='".$this->anketa['id']."' AND spr_id='".$spremenljivka."' AND grd_id='".$grid."' AND lang_id='".$this->language."'");
			$rowl = mysqli_fetch_array($sqll);
			
			if ($rowl['naslov'] != '') return $rowl['naslov'];	
		 }
		 
		 return false;		 
	 }
	 
	#funkcija, ki skrbi za filanje obstojecega polja z odgovori z missing odgovori #############################################################
	function AddMissingsToAnswers($vodoravniOdgovori=null, $missingOdgovori=null){		
		for($m=0;$m<count($missingOdgovori);$m++){
			array_push($vodoravniOdgovori,$missingOdgovori[$m]);
		}
		return $vodoravniOdgovori;
	}	
	#funkcija, ki skrbi za filanje obstojecega polja z odgovori z missing odgovori - konec #####################################################

		
	function getUserId() {return ($this->usr_id)?$this->usr_id:false;}
	
	#funkcija, ki skrbi za preverjanje obstoja podatkov za vprasanja, ki niso grid ali kombinirana tabela
	function GetUsersData($db_table=null, $spremenljivkeId=null, $spremenljivkeTip=null, $usr_id=null, $loop_id_raw=null){
		$userDataPresent = 0;	//belezi, ali je odgovor respondenta prisoten in je indeks za določena polja, ki shranjujejo podatke o odgovorih respondenta
		$loop_id = $loop_id_raw == null ? " IS NULL" : " = '".$loop_id_raw."'";
		//echo "loop_id v GetUsersData: ".$loop_id."</br>";
		
		// če imamo vnose, pogledamo kaj je odgovoril uporabnik
		if( in_array($spremenljivkeTip, array(21, 7, 8, 18)) ){	//ce je tip besedilo ali stevilo ali datum ali vsota
			//$sqlUserAnswerString ="SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivkeId."' AND usr_id='".$usr_id."' ";		
			$sqlUserAnswerString ="SELECT text FROM srv_data_text".$db_table." WHERE spr_id='".$spremenljivkeId."' AND usr_id='".$usr_id."' AND loop_id $loop_id ";
		}elseif($spremenljivkeTip==17){		//ce je razvrscanje
			//$sqlUserAnswer = sisplet_query("SELECT vrstni_red FROM srv_data_rating WHERE spr_id=".$spremenljivke['id']." AND usr_id='".$this->getUserId()."' AND vre_id='".$rowVrednost['id']."' AND loop_id $loop_id");
			//$sqlUserAnswerString = "SELECT vrstni_red FROM srv_data_rating WHERE spr_id='".$spremenljivkeId."' AND usr_id='".$usr_id."' ";
			$sqlUserAnswerString = "SELECT vrstni_red FROM srv_data_rating WHERE spr_id='".$spremenljivkeId."' AND usr_id='$usr_id' AND loop_id $loop_id ";
			//echo $sqlUserAnswerString."</br>";
		}elseif($spremenljivkeTip==26){		//ce je lokacija
			//$sqlUserAnswerString ="SELECT lat, lng, address, text FROM srv_data_map WHERE spr_id='".$spremenljivkeId."' AND usr_id='".$usr_id."' ";
			$sqlUserAnswerString ="SELECT lat, lng, address, text FROM srv_data_map WHERE spr_id='".$spremenljivkeId."' AND usr_id='$usr_id' AND loop_id $loop_id ";
			//echo $sqlUserAnswerString."</br>";
		}elseif($spremenljivkeTip==27){		//ce je heatmap
			//$sqlUserAnswerString ="SELECT lat, lng, address, text FROM srv_data_heatmap WHERE spr_id='".$spremenljivkeId."' AND usr_id='".$usr_id."' ";
			$sqlUserAnswerString ="SELECT lat, lng, address, text FROM srv_data_heatmap WHERE spr_id='".$spremenljivkeId."' AND usr_id='$usr_id' AND loop_id $loop_id ";
			//echo $sqlUserAnswerString."</br>";
		}else{	
			//$sqlUserAnswerString =  "SELECT vre_id FROM srv_data_vrednost".$db_table." WHERE spr_id='$spremenljivkeId' AND usr_id=$usr_id";
			$sqlUserAnswerString =  "SELECT vre_id FROM srv_data_vrednost".$db_table." WHERE spr_id='$spremenljivkeId' AND usr_id='$usr_id' AND loop_id $loop_id";
			//echo $sqlUserAnswerString."</br>";
		}
		
		$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
		
		if( in_array($spremenljivkeTip, array(21, 7, 8, 18, 17)) ){//ce je tip besedilo ali stevilo ali datum ali vsota ali razvrscanje
			$rowAnswers = mysqli_fetch_assoc($sqlUserAnswer);
			if($rowAnswers){	//ce je kaj v bazi
				//echo "Nekaj je v bazi za spremenljivko".$spremenljivkeId." in usr".$usr_id."</br>";
				$userDataPresent++;
			}
		}else{
			if($sqlUserAnswer){	//ce je kaj v bazi
				while ($rowAnswers = mysqli_fetch_assoc($sqlUserAnswer)){
					if($spremenljivkeTip==26||$spremenljivkeTip==27){
						//$this->userAnswer = $rowAnswers;
						$this->userAnswer[$userDataPresent] = $rowAnswers;
						//echo "rowAnswers: ".$this->userAnswer['address'].' za odgovore tip '.$spremenljivkeTip.' id '.$spremenljivkeId.' usr '.$usr_id.'</br>';	
						$userDataPresent++;
					}else{
						$this->userAnswer[$rowAnswers['vre_id']] = $rowAnswers['vre_id'];
						//echo "rowAnswers: ".$rowAnswers['vre_id'].' za odgovore tip '.$spremenljivkeTip.' id '.$spremenljivkeId.' usr '.$usr_id.'</br>';
						if($rowAnswers['vre_id']>0){
							$userDataPresent++;
						}
					}				
				}		
			}
		}
		//echo "userDataPresent za tip ".$spremenljivkeTip." id".$spremenljivkeId." usr ".$usr_id." je:".$userDataPresent."</br>";
		return $userDataPresent;
	}
	#funkcija, ki skrbi za preverjanje obstoja podatkov za vprasanja, ki niso grid ali kombinirana tabela - konec
	
	#funkcija, ki skrbi za preverjanje obstoja podatkov za vprasanja z grid	
	function GetUsersDataGrid($spremenljivke=null, $db_table=null, $rowVrednost=null, $rowVsehVrednosti=null, $usr_id=null, $subtip=null, $loop_id_raw=null){
		$loop_id = $loop_id_raw == null ? " IS NULL" : " = '".$loop_id_raw."'";
		
		// poiščemo kaj je odgovoril uporabnik: PREVERITI, CE JE POTREBEN STAVEK Z LOOP IN KDAJ JE TO AKTUALNO
		if(($spremenljivke['tip']==16)||($spremenljivke['tip']==6&&$spremenljivke['enota']==3)){	//ce je grid checkbox ali dvojna tabela
			//$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' AND loop_id $loop_id");
			//$sqlString = "SELECT grd_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];
			
			//$sqlString = "SELECT grd_id, vre_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];
			$sqlString = "SELECT grd_id, vre_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id']." AND loop_id $loop_id";
			
			//$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id']);
			$sqlUserAnswer = sisplet_query($sqlString);
		//}elseif($spremenljivke['tip']==6){	//ce je grid radio
		}elseif($spremenljivke['tip']==6){	//ce je grid radio
			//$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_grid".$db_table." where spr_id = '".$rowVrednost['spr_id']."' and usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND loop_id $loop_id");

			//$sqlString ="SELECT grd_id FROM srv_data_grid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];
			
			//$sqlString ="SELECT grd_id, vre_id FROM srv_data_grid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];
			$sqlString ="SELECT grd_id, vre_id FROM srv_data_grid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id']." AND loop_id $loop_id";
			//echo $sqlString."</br>";
			$sqlUserAnswer = sisplet_query($sqlString);
			
			//echo $sqlString."</br>";
		}elseif($spremenljivke['tip']==19||$spremenljivke['tip']==20){	//ce je grid besedila ali stevil
			$sqlString = "SELECT grd_id, text FROM srv_data_textgrid".$db_table." where spr_id = '".$rowVrednost['spr_id']."' and usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];
			//$sqlUserAnswer = sisplet_query("SELECT grd_id, text FROM srv_data_textgrid".$db_table." where spr_id = '".$rowVrednost['spr_id']."' and usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id']);
			$sqlUserAnswer = sisplet_query($sqlString);
		}elseif($spremenljivke['tip']==24){	//ce je kombo
			//echo "Subtip kombo vprasanja: ".$subtip."</br>";
			if($subtip==6){	//ce je grid radio
				//$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_grid".$db_table." where spr_id = '".$rowVrednost['spr_id']."' and usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND loop_id $loop_id");
				//$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_grid".$db_table." where spr_id = '".$rowVrednost['spr_id']."' and usr_id = '".$usr_id."' AND vre_id = ".$rowVrednost['id']);
				//$sqlString ="SELECT grd_id FROM srv_data_grid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = ".$rowVrednost['id'];
				//$sqlString ="SELECT grd_id FROM srv_data_grid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];
				$sqlString ="SELECT grd_id FROM srv_data_grid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id']." AND loop_id $loop_id";
				//echo $sqlString."</br>";
				$sqlUserAnswer = sisplet_query($sqlString);
				
				//echo $sqlString."</br>";
			}elseif($subtip==16){	//ce je grid checkbox ali dvojna tabela
				//$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = '".$rowVsehVrednosti['id']."' AND loop_id $loop_id");
				
				//$sqlString = "SELECT grd_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];
				$sqlString = "SELECT grd_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id']." AND loop_id $loop_id";
				//$sqlUserAnswer = sisplet_query("SELECT grd_id FROM srv_data_checkgrid".$db_table." WHERE spr_id = '".$rowVrednost['spr_id']."' AND usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id']);
				$sqlUserAnswer = sisplet_query($sqlString);				
			}elseif($subtip==19||$subtip==20){	//ce je grid besedila ali stevil
				$sqlString = "SELECT grd_id, text FROM srv_data_textgrid".$db_table." where spr_id = '".$rowVrednost['spr_id']."' and usr_id = '".$usr_id."' AND vre_id = '".$rowVrednost['id']."' AND grd_id = ".$rowVsehVrednosti['id'];

				$sqlUserAnswer = sisplet_query($sqlString);
				
				//$this->userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
				//echo "userAnswer v funkciji: ".$this->userAnswer['text'].'</br>';
			}
		}
		
		//echo $sqlString."</br>";
		//$this->userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
		//$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
		//echo "userAnswer v funkciji: ".$this->userAnswer['grd_id'].'</br>';
		//echo "userAnswer v funkciji: ".$userAnswer['text'].'</br>';
		//echo "userAnswer v funkciji: ".$userAnswer['grd_id'].'</br>';
		
		return $sqlUserAnswer;

	}
	#funkcija, ki skrbi za preverjanje obstoja podatkov za vprasanja z grid - konec
	
	#funkcija, ki skrbi za preverjanje obstoja podatkov za vprasanja s kombinirano tabelo
	function GetUsersDataKombinirana($spremenljivke=null, $db_table=null, $usr_id=null, $questionText=null, $loop_id_raw=null){
		$userDataPresent = 0;	//belezi, ali je odgovor respondenta prisoten in je indeks za določena polja, ki shranjujejo podatke o odgovorih respondenta
		$userAnswerSprIds = array();
		$userAnswerSprTip = array();
		$userAnswerSprIdsIndex = 0;
		$orStavek = '';
		//$loop_id = $loop_id_raw == null ? " IS NULL" : " = '".$loop_id_raw."'";
		$loop_id = $loop_id_raw;
		
		#za pridobitev stevila vrstic
		$sqlVrednostiKombo = sisplet_query("SELECT id, naslov, naslov2, variable, other FROM srv_vrednost WHERE spr_id='".$spremenljivke['id']."' ORDER BY vrstni_red");
		$numRowsSql = mysqli_num_rows($sqlVrednostiKombo);
		//echo $numRowsSql."</br>";
		#za pridobitev stevila vrstic - konec
		
		#za pridobitev stevila stolpcev
		$sqlStVrednostiKombo = sisplet_query("SELECT count(*) FROM srv_grid g, srv_grid_multiple m WHERE m.spr_id=g.spr_id AND m.parent='".$spremenljivke['id']."'");
		$rowStVrednost = mysqli_fetch_array($sqlStVrednostiKombo);	//stevilo stolpcev		
		$numColSql = $rowStVrednost['count(*)'];	//stevilo vseh stolpcev
		//echo "stevilo stolpcev: ".$numColSql."</br>";
		#za pridobitev stevila stolpcev - konec
		
		$sqlSubGrid = sisplet_query("SELECT m.spr_id, s.tip FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivke['id']."' AND m.spr_id=s.id ORDER BY m.vrstni_red");	//pridobimo spr_id in tip podvprasanj, ki sestavljajo kombinirano tabelo
		//echo "SELECT m.spr_id, s.tip FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivke['id']."' AND m.spr_id=s.id ORDER BY m.vrstni_red"."</br>";
		
		while($rowSubGrid = mysqli_fetch_array($sqlSubGrid)){
			array_push($userAnswerSprIds, $rowSubGrid['spr_id'] );	//filanje polja s spr_id podvprasanj
			array_push($userAnswerSprTip, $rowSubGrid['tip'] );	//filanje polja s tip podvprasanj
			if($userAnswerSprIdsIndex){
				$orStavek .= ' OR ';
			}
			//$orStavek .= "spr_id='".$rowSubGrid['spr_id']."' ";
			$orStavek .= "v.spr_id='".$rowSubGrid['spr_id']."' ";
			$userAnswerSprIdsIndex++;
		}
		
		//echo $orStavek."</br>";
		//echo count($userAnswerSprTip)."</br>";
		
		for($i=1;$i<=$numRowsSql;$i++){
			//$sqlVrednostiString = "SELECT id, naslov, spr_id FROM srv_vrednost WHERE (".$orStavek.") AND vrstni_red=".($i).";";
			$sqlVrednostiString = "SELECT v.spr_id, v.naslov, s.tip, v.id FROM srv_vrednost v, srv_spremenljivka s WHERE v.spr_id=s.id AND (".$orStavek.") AND v.vrstni_red=".($i).";";
			//echo $sqlVrednostiString."</br>";
			$sqlVrednosti = sisplet_query($sqlVrednostiString);
			while($rowVrednosti = mysqli_fetch_assoc($sqlVrednosti)){
				$sqlVsehVrednostiString = "SELECT id, naslov FROM srv_grid WHERE spr_id='".$rowVrednosti['spr_id']."' ORDER BY 'vrstni_red'";
				//echo $sqlVsehVrednostiString."</br>";
				//echo $rowVrednosti['tip']."</br>";
				//echo "Vrednost: ".$rowVrednosti['spr_id']."</br>";
				$sqlVsehVrednosti = sisplet_query($sqlVsehVrednostiString);
				while ($rowVsehVrednosti = mysqli_fetch_assoc($sqlVsehVrednosti)){
					//$sqlUserAnswer = $this->GetUsersDataGrid($spremenljivke, $db_table, $rowVrednosti, $rowVsehVrednosti, $usr_id, $rowVrednosti['tip']);
					$sqlUserAnswer = $this->GetUsersDataGrid($spremenljivke, $db_table, $rowVrednosti, $rowVsehVrednosti, $usr_id, $rowVrednosti['tip'], $loop_id);
					$userAnswer = mysqli_fetch_assoc($sqlUserAnswer);
					//if($userAnswer){	//ce je kaj v bazi
						if($rowVrednosti['tip']==19||$rowVrednosti['tip']==20){
							//$this->userAnswer[$userDataPresent] = $userAnswer['text'];	
							$userAnswers[$userDataPresent] = $userAnswer['text'];	
						}else{
							//$this->userAnswer[$userDataPresent] = $userAnswer['grd_id'];								
							$userAnswers[$userDataPresent] = $userAnswer['grd_id'];								
						}
						//echo $this->userAnswer[$userDataPresent]."</br>";
						//echo $userAnswer[$userDataPresent]."</br>";
						$userDataPresent++;
					//}
				}
			}
			
		}
		//if($questionText){
			//return $userDataPresent;			
		//}else{
			//return $this->userAnswer;
			return $userAnswers;
		//}		
	}
	#funkcija, ki skrbi za preverjanje obstoja podatkov za vprasanja s kombinirano tabelo - konec
	
	#funkcija, ki skrbi za pridobitev operatorja iz stevilskega podatka ###########################################################
	function GetOperator($operatorNum=null){
		if ($operatorNum == 0){
			$operator = $this->encodeText('+');
		}elseif ($operatorNum == 1){
			$operator = $this->encodeText('-');
		}elseif ($operatorNum == 2){
			$operator = $this->encodeText('*');
		}elseif ($operatorNum == 3){
			$operator = $this->encodeText('/');
		}
		return $operator;
	}
	#funkcija, ki skrbi za pridobitev operatorja iz stevilskega podatka - konec ###################################################
	
}