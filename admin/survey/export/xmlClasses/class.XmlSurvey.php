<?php

/**
 *
 *	Class ki skrbi za izris vprasalnika v xml
 *
 *
 */

include('../../vendor/autoload.php');
 
class XmlSurvey{
	
	var $anketa; // ID ankete
	var $tex;	//shrani tex kodo
	var $texNewLine = '\\\\ ';
	var $texPageBreak = "\\pagebreak";
	//var $texPageBreak = "\\newpage";
	var $export_format;
	var $export_data_show_recnum;
	var $exportDataPageBreak=0; //vsak respondent na svoji strani
	
	var $commentType = 1;	// tip izpisa komentarjev
	
	var $loop_id = null;	// id trenutnega loopa ce jih imamo	
	
	var $db_table = '';
	
	protected $showIntro = 0;
	protected $type = 0;			// tip izpisa - 0->navaden, 1->iz prve strani, 2->s komentarji
	
	protected $showIf = 0;		// izpis if-ov
	
	var $skipEmpty = 0; 	// izpusti vprasanja brez odgovora
	var $skipEmptySub = 0; 	// izpusti podvprasanja brez odgovora
	
	protected $recnum = 0;
	protected $usr_id = 0;
	protected $texBigSkip = '\bigskip';
	
	protected $xml;
	
	function __construct($anketa=null, $export_format='', $xml=null){
		global $site_path, $global_user_id, $admin_type, $lang;
	
		$this->anketa = $anketa;
		$this->export_format = $export_format;
		$this->xml = $xml;
		
		$this->usr_id = $_GET['usr_id'];
		
		if ($this->usr_id  != '') {
			$sqlR = sisplet_query("SELECT recnum FROM srv_user WHERE id = '$this->usr_id '");
			$rowR = mysqli_fetch_array($sqlR);
			$this->recnum = $rowR['recnum'];
		}
		
		//pridobitev nastavitev izvoza
		SurveySetting::getInstance()->Init($this->anketa);
		$this->export_data_show_recnum = SurveySetting::getInstance()->getSurveyMiscSetting('export_data_show_recnum');	//ali je potrebno pokazati recnum ob vsakem respondentu
		$this->exportDataPageBreak = (int)SurveySetting::getInstance()->getSurveyMiscSetting('export_data_PB');	//ali mora vsak izpis odgovorov respondenta zaceti na svoji strani
		
		//if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init())
		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa) )
		{		
			if (SurveyInfo::getInstance()->getSurveyColumn('db_table') == 1){
				$this->db_table = '_active';
			}
		}
		else{
			return false;
		}		
	}
	
	#funkcija, ki skrbi za izpis praznega vprasalnika v xml
	public function displaySurvey($export_subtype='', $export_data_type=''){
		global $lang;

		xmlwriter_start_element($this->xml, 'dataDscr'); //Zacetek elementa dataDscr
		
		//echo "in function: ".$export_data_type."</br>";
		$rowA = SurveyInfo::getInstance()->getSurveyRow();
		
		// filtriramo spremenljivke glede na profil					
		SurveyVariablesProfiles :: Init($this->anketa);
		
		$dvp = SurveyUserSetting :: getInstance()->getSettings('default_variable_profile');
		$_currentVariableProfile = SurveyVariablesProfiles :: checkDefaultProfile($dvp);
		
		$tmp_svp_pv = SurveyVariablesProfiles :: getProfileVariables($_currentVariableProfile);
		
		foreach ( $tmp_svp_pv as $vid => $variable) {
			$tmp_svp_pv[$vid] = substr($vid, 0, strpos($vid, '_'));			
		}
		
		$sqlGrupeString = "SELECT id FROM srv_grupa WHERE ank_id='".$this->anketa."' ORDER BY vrstni_red";		
		$sqlGrupe = sisplet_query($sqlGrupeString);
		
		//echo "__________________________________</br>";
		//echo "Funkcija displaySurvey user: $this->usr_id</br>";
		
		$question = new XmlSurveyElement($this->anketa, $this->export_format, $this->usr_id, $export_subtype, $this->xml);
		
		while ( $rowGrupe = mysqli_fetch_assoc( $sqlGrupe ) ){ // sprehodmo se skozi grupe ankete
			$this->grupa = $rowGrupe['id'];
			
			// Pogledamo prvo spremenljivko v grupi ce je v loopu
			$sql = sisplet_query("SELECT * FROM srv_spremenljivka WHERE gru_id='".$this->grupa."' AND visible='1' ORDER BY vrstni_red ASC");
			$row = mysqli_fetch_array($sql);

			// ce je ima loop za parenta
			$if_id = $this->find_parent_loop($row['id']);

			// Navadne spremenljivke ki niso v loopu
				
				$loop_id = 'IS NULL';
				//$zaporedna = 0;
				$sqlSpremenljivke = sisplet_query("SELECT * FROM srv_spremenljivka WHERE gru_id='".$this->grupa."' AND visible='1' ORDER BY vrstni_red ASC");
				while ($rowSpremenljivke = mysqli_fetch_assoc($sqlSpremenljivke)){ // sprehodimo se skozi spremenljivke grupe
					$spremenljivka = $rowSpremenljivke['id'];
					//echo "Ni v loop-u:".$rowSpremenljivke['tip']."  </br>";
					
					$preveriSpremenljivko = $this->checkSpremenljivka($spremenljivka);	//preveri ali je spremenljivka vidna (zaradi branchinga)
					
 					if ($preveriSpremenljivko){ // lahko izrišemo spremenljivke
						// če imamo številčenje Type = 1 potem številčimo V1
/* 						if (SurveyInfo::getInstance()->getSurveyCountType()){
							$zaporedna++;
						} */

						//$stevilcenje = ( SurveyInfo::getInstance()->getSurveyCountType() ) ? ( ( SurveyInfo::getInstance()->getSurveyCountType() == 2 ) ? $rowSpremenljivke['variable'].") " : $zaporedna.") " ) : null;

							
						// izpis navadnega vprasalnika #####################						
						//izpisi posamezen element praznega vprasalnika
						$question->displaySurveyElement($rowSpremenljivke, $export_subtype, $preveriSpremenljivko, $this->loop_id);						
						// izpis navadnega vprasalnika - konec #############
						
						//$this->pdf->Ln(LINE_BREAK);
					}
				}
		}
		xmlwriter_end_element($this->xml); //Zakljucek elementa dataDscr
	}
	#funkcija, ki skrbi za izpis praznega vprasalnika v xml - konec
	
	function getGrupa() {return $this->grupa;}
	
	
	/**
    * @desc preveri ali je spremenljivka vidna (zaradi branchinga)
    */
    function checkSpremenljivka ($spremenljivka=null) {

        $sql = sisplet_query("SELECT * FROM srv_spremenljivka WHERE id = '".$spremenljivka."'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

        if ($row['visible'] == 0) return false;

        $sql1 = sisplet_query("SELECT * FROM srv_branching WHERE element_spr = '".$spremenljivka."'");
        if (!$sql1) echo mysqli_error($GLOBALS['connect_db']);
        $row1 = mysqli_fetch_array($sql1);

        /*if (!$this->checkIf($row1['parent']))
            return false;*/

        return true;
    }
	
    /**
    * @desc preveri ali je spremenljivka vidna (zaradi branchinga), ko je q_data ali q_data_all
    */
    //function checkSpremenljivkaData ($spremenljivka, $gridMultiple=false) {
    function checkSpremenljivkaData ($spremenljivka=null, $loop_id_raw=null, $gridMultiple=false) {
		
		$loop_id = $loop_id_raw == 'IS NULL' ? " IS NULL" : " = '".$loop_id_raw."'";
		
		$sql = sisplet_query("SELECT * FROM srv_spremenljivka WHERE id = '".$spremenljivka."'");
        if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
        $row = mysqli_fetch_array($sql);

		// Ce vprasanje ni vidno ali ce uporabnik nima dostopa do vprasanja
        if ($row['visible'] == 0 || !( ($this->admin_type <= $row['dostop'] && $this->admin_type>=0) || ($this->admin_type==-1 && $row['dostop']==4) ) )
			return false;			
		//echo "skipEmpty: $this->skipEmpty </br>";
		// Preverjamo ce je vprasanje prazno in ce preskakujemo prazne
		if($this->skipEmpty==1 && !$gridMultiple){
			
			$isEmpty = true;
			//echo "isEmpty: ".$isEmpty."</br>";
			switch ( $row['tip'] ){
				case 1: //radio
				case 2: //check
				case 3: //select -> radio
					//$sqlUserAnswer = sisplet_query("SELECT * FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$row[id]' AND usr_id='".$this->usr_id."' AND vre_id!='-2'");
					$sqlUserAnswerString = "SELECT * FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$row[id]' AND usr_id='".$this->usr_id."' AND vre_id>'0' AND loop_id $loop_id ";
					$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
					if(mysqli_num_rows($sqlUserAnswer) > 0){
						$isEmpty = false;
					}						
				break;

				case 6: //multigrid
				case 16:// multicheckbox
				case 19:// multitext
				case 20:// multinumber											
					if($row['tip'] == 6 && $row['enota'] != 3){
						$sqlUserAnswerString = "SELECT * FROM srv_data_grid".$this->db_table." WHERE spr_id = '".$row['id']."' AND usr_id = '".$this->usr_id."' AND loop_id $loop_id";
					}
					elseif($row['tip'] == 16 || ($row['tip'] == 6 && $row['enota'] == 3)){
						//$sqlUserAnswer = sisplet_query("SELECT * FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$row['id']."' AND usr_id = '".$this->usr_id."'");
						$sqlUserAnswerString = "SELECT * FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$row['id']."' AND usr_id = '".$this->usr_id."' AND loop_id $loop_id";
					}
					else{
						//$sqlUserAnswer = sisplet_query("SELECT * FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$row['id']."' AND usr_id = '".$this->usr_id."'");
						$sqlUserAnswerString ="SELECT * FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$row['id']."' AND usr_id = '".$this->usr_id."' AND loop_id $loop_id";
					}
					$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
					//echo "stevilo podatkov: ".mysqli_num_rows($sqlUserAnswer)."</br>";
					if(mysqli_num_rows($sqlUserAnswer) > 0){
						$isEmpty = false;	
					}
				break;
				
				case 7: //number
				case 8: //datum	
				case 18: //vsota
				case 21: //besedilo*
					$sqlUserAnswerString = "SELECT * FROM srv_data_text".$this->db_table." WHERE spr_id='".$row['id']."' AND usr_id='".$this->usr_id."' AND loop_id $loop_id";
					$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
					if(mysqli_num_rows($sqlUserAnswer) > 0)
						$isEmpty = false;				
				break;
				
				case 17: //ranking					
					$sqlUserAnswerString = "SELECT * FROM srv_data_rating WHERE spr_id=".$row['id']." AND usr_id='".$this->usr_id."' AND loop_id $loop_id";		
					$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
					if(mysqli_num_rows($sqlUserAnswer) > 0)
						$isEmpty = false;
				break;
				
				case 24: //mesan multigrid	
					// loop po podskupinah gridov
					$sqlSubGrid = sisplet_query("SELECT m.spr_id, s.tip, s.enota FROM srv_grid_multiple m, srv_spremenljivka s WHERE m.parent='".$spremenljivka."' AND m.spr_id=s.id");
					while($rowSubGrid = mysqli_fetch_array($sqlSubGrid)){
						if($rowSubGrid['tip'] == 6){
							//$sqlUserAnswerString = "SELECT grd_id FROM srv_data_grid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->usr_id."'";
							$sqlUserAnswerString = "SELECT grd_id FROM srv_data_grid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->usr_id."' AND loop_id $loop_id";
						}
						elseif($rowSubGrid['tip'] == 16){
							//$sqlUserAnswerString = "SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->usr_id."'";
							$sqlUserAnswerString ="SELECT grd_id FROM srv_data_checkgrid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->usr_id."' AND loop_id $loop_id";
						}
						else{
							//$sqlUserAnswerString = "SELECT grd_id, text FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->usr_id."'";
							$sqlUserAnswerString = "SELECT grd_id, text FROM srv_data_textgrid".$this->db_table." WHERE spr_id = '".$rowSubGrid['spr_id']."' AND usr_id = '".$this->usr_id."' AND loop_id $loop_id";
						}
						$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
						if(mysqli_num_rows($sqlUserAnswer) > 0){
							$isEmpty = false;	
							break;
						}
					}	
				break;
				
				case 5: //nagovor	
					// Ce je nagovor v loopu, ga prikazemo
					if($this->loop_id != null)
						$isEmpty = false;
				break;
				
				case 26: //lokacija
					//$sqlUserAnswerString ="SELECT lat, lng, address, text FROM srv_data_map WHERE spr_id='".$row['id']."' AND usr_id='".$this->usr_id."' ";
					$sqlUserAnswerString ="SELECT lat, lng, address, text FROM srv_data_map WHERE spr_id='".$row['id']."' AND usr_id='".$this->usr_id."' AND loop_id $loop_id";
					$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
					if(mysqli_num_rows($sqlUserAnswer) > 0)
						$isEmpty = false;
				break;
				
				case 27: //heatmap
					//$sqlUserAnswerString ="SELECT lat, lng, address, text FROM srv_data_heatmap WHERE spr_id='".$row['id']."' AND usr_id='".$this->usr_id."' ";
					$sqlUserAnswerString ="SELECT lat, lng, address, text FROM srv_data_heatmap WHERE spr_id='".$row['id']."' AND usr_id='".$this->usr_id."' AND loop_id $loop_id";
					$sqlUserAnswer = sisplet_query($sqlUserAnswerString);
					if(mysqli_num_rows($sqlUserAnswer) > 0)
						$isEmpty = false;
				break;
				
				default:
					$isEmpty = false;
					//$isEmpty = true;
				break;
			}
			//echo "isEmpty na koncu: ".$isEmpty."</br>";
			if($isEmpty == true){
				return false;
			}
		}
        return true;
    }
	
	
	function displayIf($if=null){
		global $lang;
		//echo "</br> displayIf funkcija </br> ";
		$sql_if_string = "SELECT * FROM srv_if WHERE id = '$if'";
		//echo "sql_if_string: ".$sql_if_string." </br>";
    	//$sql_if = sisplet_query("SELECT * FROM srv_if WHERE id = '$if'");
    	$sql_if = sisplet_query($sql_if_string);
    	$row_if = mysqli_fetch_array($sql_if);
		//echo "tip: ".$row_if['tip']." </br>";
        // Blok
		if($row_if['tip'] == 1)
			$output = strtoupper($lang['srv_block']).' ';
		// Loop
		elseif($row_if['tip'] == 2)
			$output = strtoupper($lang['srv_loop']).' ';
		// IF
		else
			$output = 'IF ';
      
		$sql_if = sisplet_query("SELECT * FROM srv_if WHERE id = '$if'");
		$row_if = mysqli_fetch_array($sql_if);
		$output .= '('.$row_if['number'].') ';

        $sql = Cache::srv_condition($if);
        
        $bracket = 0;
        $i = 0;
         while ($row = mysqli_fetch_array($sql)) {

            if ($i++ != 0)
                if ($row['conjunction'] == 0)
                    $output .= ' and ';
                else
                    $output .= ' or ';

            if ($row['negation'] == 1)
                $output .= ' NOT ';

            for ($i=1; $i<=$row['left_bracket']; $i++)
				$output .=  ' ( ';

            // obicajne spremenljivke
            if ($row['spr_id'] > 0) {

				$row2 = Cache::srv_spremenljivka($row['spr_id']);
				
                // obicne spremenljivke
                if ($row['vre_id'] == 0) {
                    $row1 = Cache::srv_spremenljivka($row['spr_id']);
                // multigrid
                } elseif ($row['vre_id'] > 0) {
                    $sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE id = '$row[vre_id]'");
                    $row1 = mysqli_fetch_array($sql1);
                } else
                    $row1 = null;

                $output .= $row1['variable'];

                // radio, checkbox, dropdown in multigrid
                if (($row2['tip'] <= 3 || $row2['tip'] == 6) && ($row['spr_id'] || $row['vre_id'])) {

                    if ($row['operator'] == 0)
                        $output .= ' = ';
                    else
                        $output .= ' != ';

                    $output .= '[';

                    // obicne spremenljivke
                    if ($row['vre_id'] == 0) {
                        $sql2 = sisplet_query("SELECT * FROM srv_condition_vre c, srv_vrednost v WHERE cond_id='$row[id]' AND c.vre_id=v.id");

                        $j = 0;
                        while ($row2 = mysqli_fetch_array($sql2)) {
                            if ($j++ != 0) $output .= ', ';
                            $output .= $row2['variable'];
                        }
                    // multigrid
                    } elseif ($row['vre_id'] > 0) {
                        $sql2 = sisplet_query("SELECT g.* FROM srv_condition_grid c, srv_grid g WHERE c.cond_id='$row[id]' AND c.grd_id=g.id AND g.spr_id='$row[spr_id]'");

                        $j = 0;
                        while ($row2 = mysqli_fetch_array($sql2)) {
                            if ($j++ != 0) $output .= ', ';
                            $output .= $row2['variable'];
                        }
                    }

                    $output .= ']';

                // textbox in nubmer mata drugacne pogoje in opcije
                } elseif ($row2['tip'] == 4 || $row2['tip'] == 21 || $row2['tip'] == 7 || $row2['tip'] == 22) {

                    if ($row['operator'] == 0)
                        $output .= ' = ';
                    elseif ($row['operator'] == 1)
                        $output .= ' <> ';
                    elseif ($row['operator'] == 2)
                        $output .= ' < ';
                    elseif ($row['operator'] == 3)
                        $output .= ' <= ';
                    elseif ($row['operator'] == 4)
                        $output .= ' > ';
                    elseif ($row['operator'] == 5)
                        $output .= ' >= ';

                    $output .= '\''.$row['text'].'\'';

                }

            // recnum
            } elseif ($row['spr_id'] == -1) {

                $output .= 'mod(recnum, '.$row['modul'].') = '.$row['ostanek'];

			} 

            for ($i=1; $i<=$row['right_bracket']; $i++)
				$output .= ' ) ';
        }
        
        if ($row_if['label'] != '') {
	        $output .= ' (';
	        $output .= ' '.$row_if['label'].' ';
	        $output .= ') ';      		
        }
		echo $output."</br>";
/* 		$this->pdf->SetTextColor(0,0,150);
		$this->pdf->setFont('','B',$this->font);
		$this->pdf->MultiCell(90, 1, $this->encodeText($output),0,'L',0,1,0,0);
		$this->pdf->SetTextColor(0,0,0);
		$this->pdf->setFont('','',$this->font); */

		return $output;
	}

	/* poisce, ce ima podani element parenta, ki je loop
	* 
	*/
	function find_parent_loop ($element_spr=null, $element_if=0) {
		
		//$sql = sisplet_query("SELECT parent FROM srv_branching WHERE element_spr = '$element_spr' AND element_if = '$element_if' AND ank_id='".$this->anketa['id']."'");
		$sql = sisplet_query("SELECT parent FROM srv_branching WHERE element_spr = '$element_spr' AND element_if = '$element_if' AND ank_id='".$this->anketa."'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		$row = mysqli_fetch_array($sql);
		
		if ($row['parent'] == 0) return 0;
		
		$sql = sisplet_query("SELECT id FROM srv_if WHERE id = '$row[parent]' AND tip = '2'");
		if (!$sql) echo mysqli_error($GLOBALS['connect_db']);
		if (mysqli_num_rows($sql) > 0)
			return $row['parent'];
		else
			return $this->find_parent_loop(0, $row['parent']);
			
	}	
	
		 /**
    * poisce naslednjo vre_id v loopu
    * 
    */
    function findNextLoopId ($if_id=0) {
		if ($if_id == 0) {
			$sql = sisplet_query("SELECT * FROM srv_loop_data WHERE id='$this->loop_id'");
			$row = mysqli_fetch_array($sql);
			$if_id = $row['if_id'];
			$loop_id = $this->loop_id;
		} else{
			$loop_id = 0;
		}

		$sql = sisplet_query("SELECT * FROM srv_loop WHERE if_id = '$if_id'");
		$row = mysqli_fetch_array($sql);
		$spr_id = $row['spr_id'];
		$max = $row['max'];
		
		$spr = Cache::srv_spremenljivka($spr_id);
		//echo "spr tip: ".$spr['tip']."</br>";
		if ($spr['tip'] == 2 || $spr['tip'] == 3 || $spr['tip'] == 9) {
			$data_vrednost = array();
			if($spr['tip'] == 9){
				if($this->usr_id){
					$sql1String = "SELECT vre_id FROM srv_data_text".$this->db_table." WHERE spr_id='$spr_id' AND usr_id='".$this->usr_id."' ";
				}else{
					$sql1String = "SELECT vre_id FROM srv_data_text".$this->db_table." WHERE spr_id='$spr_id' ";
				}
			}
			else{
 				if($this->usr_id){
					$sql1String = "SELECT vre_id FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$spr_id' AND usr_id='".$this->usr_id."'";
				}else{
					$sql1String = "SELECT vre_id FROM srv_data_vrednost".$this->db_table." WHERE spr_id='$spr_id' ";
				}				
			}
			//echo $sql1String;
			$sql1 = sisplet_query($sql1String);
			while ($row1 = mysqli_fetch_array($sql1)) {
				$data_vrednost[$row1['vre_id']] = 1;
			}
			
			$vre_id = '';
			$i = 1;
			//$sql = sisplet_query("SELECT * FROM srv_loop_vre WHERE if_id='$if_id'");
			
			$sql = sisplet_query("SELECT * FROM srv_loop_vre lv, srv_vrednost v WHERE lv.if_id='$if_id' AND lv.vre_id=v.id ORDER BY v.vrstni_red ASC");
			while ($row = mysqli_fetch_array($sql)) {
				
				if ($row['tip'] == 0) {			// izbran
					if ( isset($data_vrednost[$row['vre_id']]) ) {
						$vre_id .= ', '.$row['vre_id'];
						$i++;
					}
				} elseif ($row['tip'] == 1) {	// ni izbran
					if ( !isset($data_vrednost[$row['vre_id']]) ) {
						$vre_id .= ', '.$row['vre_id'];
						$i++;
					}
				} elseif ($row['tip'] == 2) {	// vedno
					$vre_id .= ', '.$row['vre_id'];
					$i++;
				}								// nikoli nimamo sploh v bazi, zato ni potrebno nic, ker se nikoli ne prikaze
				
				if ($i > $max && $max>0) break;
			}
			
			$vre_id = substr($vre_id, 2);
			
			if ($vre_id == '') return null;
			
			$sql = sisplet_query("SELECT l.* FROM srv_loop_data l, srv_vrednost v WHERE l.if_id='$if_id' AND l.id > '$loop_id' AND l.vre_id IN ($vre_id) AND l.vre_id=v.id ORDER BY l.id ASC");
			if (!$sql) { echo 'err56545'.mysqli_error($GLOBALS['connect_db']); die();}
			$row = mysqli_fetch_array($sql);
			
			if (mysqli_num_rows($sql) > 0)
				return $row['id'];
			else
				return null;
				
		// number
		} elseif ($spr['tip'] == 7) {
			
			//$sql1 = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='$spr_id' AND usr_id='".$this->getUserId()."'");
			$sql1 = sisplet_query("SELECT text FROM srv_data_text".$this->db_table." WHERE spr_id='$spr_id' AND usr_id='".$this->user_id."'");
			$row1 = mysqli_fetch_array($sql1);
			
			$num = (int)$row1['text'];
			$sql2 = sisplet_query("SELECT * FROM srv_loop_data WHERE if_id='$if_id' AND id <= '$loop_id'");
			if (mysqli_num_rows($sql2) >= $num || (mysqli_num_rows($sql2) >= $max && $max>0))
				return null;
			
			$sql = sisplet_query("SELECT * FROM srv_loop_data WHERE if_id='$if_id' AND id > '$loop_id'");
			$row = mysqli_fetch_array($sql);
			
			if (mysqli_num_rows($sql) > 0)
				return $row['id'];
			else
				return null;
			
		}
    }
	
		/**
    * @desc V podanem stringu poisce spremenljivke in jih spajpa z vrednostmi
    */
    function dataPiping ($text='') {
    	Common::getInstance()->Init($this->anketa);
		echo Common::getInstance()->dataPiping($text, $this->usr_id, $this->loop_id)."</br>";
        return Common::getInstance()->dataPiping($text, $this->usr_id, $this->loop_id);
    }

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
}