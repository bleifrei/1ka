<?php

/*
 *  Modul za pripravo podatkov, generiranje/brisanje json datoteke in prikazovanje JSON za anketo
 *
 */


class SurveyJsonSurveyData {

	var $anketa;				# id ankete
	protected $json;	//hrani json
	protected $grupaId; //hrani id grupe oz. strani, kjer se nahajajo vprasanja
	
	function __construct($anketa){		
		// Ce imamo anketo
		if ((int)$anketa > 0){
			$this->anketa = $anketa;
		}
	}
		
	public function displaySettings(){
		global $lang;
/* 		echo '<fieldset><legend>'.$lang['settings'].'</legend>';

		echo '</fieldset>'; */
		
		echo '<br />';
		
		//Prikazemo JSON kodo za izvoz in povezavo za prenos json datoteke
		$this->displayJsonData();
	}
	
	##########################################################################################
	//Funkcija za prikazovanje json kode in sprozenje generacije ustrezne json datoteke
	private function displayJsonData(){
		global $lang;
		global $site_url;
		
		//generiranje polja s podatki za JSON
		$jsonArray= $this->generateJsonArray();		
		
		//pretvorba polja v JSON, kjer je json datoteka strukturirana in UTF-8
		$this->json = json_encode($jsonArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

		//ustvari json datoteko
		$this->GenerateJsonFile();

		//povezava do JSON datoteke za prenos
		echo '<fieldset><legend>JSON file</legend>';
		$href_json = 'izvoz.php?m=json_survey&anketa=' . $this->anketa;
		echo ' <span class="spaceLeft"><a href="'.$href_json.'">JSON</a></span>';	
		echo '</fieldset>';
		//povezava do JSON datoteke za prenos - konec		
		
		//prikazovanje JSON kode
/* 		echo '<fieldset><legend>JSON Data</legend>';		
		echo "<pre><code>";
		echo ($this->json)."</br>";
		echo "</pre></code>";
		echo '</fieldset>'; */
		//prikazovanje JSON kode - konec
	}
	######################################################################################################
	
	###################################################
	//Funkcija za ustvarjanje php polja za pretvorbo v JSON
	private function generateJsonArray(){

		#spremenljivke#################################################################
		$dolgoImeAnkete = SurveyInfo::getSurveyColumn('naslov');
		$kratkoImeAnkete = SurveyInfo::getSurveyColumn('akronim');
		$avtorAnkete = SurveyInfo::getSurveyEditEmail();
		//$avtorAnkete = SurveyInfo::getUserInsertInfo('email');
		
		
		
		#spremenljivke - konec ########################################################

		//polje za pretvorbo
 		$tmpJsonArray = array(
							"survey"=> array(
								"id"=> (float)$this->anketa ,
								"name"=> $dolgoImeAnkete,
								"author_username"=> $avtorAnkete,
								"questionnaire"=> array(
									"pages"=> $this->pagesArray()
								)
							)
						);
		return $tmpJsonArray;
	}
	###################################################
	
	
	###################################################
	//Funkcija za generiranje polja za strani ankete
	private function pagesArray(){
		global $lang;
		
		//pobiranje podatkov o anketi		
		$introShow = SurveyInfo::getSurveyColumn('show_intro');
		$conclShow = SurveyInfo::getSurveyColumn('show_concl');		
		$introductionText = (SurveyInfo::getSurveyColumn('introduction'));
		$conclusionText = (SurveyInfo::getSurveyColumn('conclusion'));
		
		//ureditev zacetne strani #######################
		if($introductionText == ''){
			$introductionText = $lang['srv_intro'];
		}
		if($introShow){	//ce je zacetna stran prisotna
			$introShow = true;
		}else{
			$introShow = false;
		}
		
		//dodaj v polje informacije o zacetni strani
		$pagesArray[] = array(
			"id"=> -1,
			"type"=> "intro",
			"visible"=> $introShow,
			"text"=> $introductionText		
		);
		//ureditev zacetne strani - konec #######################	
		
		
		//pobiranje podatkov o grupah/straneh ankete
		$sqlGrupeString = "SELECT id FROM srv_grupa WHERE ank_id='".$this->anketa."' ORDER BY vrstni_red";		
		$sqlGrupe = sisplet_query($sqlGrupeString);
		
		//ureditev ostalih strani
		while ($rowGrupe = mysqli_fetch_assoc( $sqlGrupe )){ // sprehodimo se skozi grupe oz. straneh ankete brez intro in end straneh
			$this->grupaId = $rowGrupe['id'];			
			
			$pagesArray[] = array(
				"id"=> (float)$this->grupaId,
				"type"=> "normal",
				"questions"=> $this->questionsArray()
			);
		}
		//ureditev ostalih strani - konec
		
		//ureditev zakljucne strani #######################
		if($conclusionText == ''){
			$conclusionText = $lang['srv_end'];
		}
		
 		//pobiranje podatkov o end_action ankete
		$sqlEndActionString = "SELECT concl_link FROM srv_anketa WHERE id='".$this->anketa."' ";		
		$sqlEndAction = sisplet_query($sqlEndActionString);
		$rowEndAction = mysqli_fetch_assoc($sqlEndAction);
		$endActionNum = $rowEndAction['concl_link'];
		//pobiranje podatkov o end_action ankete - konec
		
		//ureditev end_action parametra
		switch($endActionNum){
			case 0:
				$endAction = "close";
			break;
			case 1:
				$endAction = "open_url";
			break;
			case 2:
				$endAction = "restart";
			break;
		}		
		//ureditev end_action parametra - konec
		
		if($conclShow){	//ce je zacetna stran prisotna
			$conclShow = true;
		}else{
			$conclShow = false;
		}	
		
		//dodaj v polje informacije o zakljucni strani
		$pagesArray[] = array(
			"id"=> -2,
			"type"=> "end",
			"visible"=> $conclShow,
			"text"=> $conclusionText,
			"end_action"=> $endAction				
		);
		//ureditev zakljucne strani - konec #######################
		
		return $pagesArray;
	}
	###################################################
	
	
	###################################################
	//Funkcija za generiranje polja za vprasanja ankete
	private function questionsArray(){
		
		$databaseRows = " id, tip, orientation, variable, naslov, info, reminder, enota, text_orientation ";
		
		$sqlSpremenljivkeString = "SELECT ".$databaseRows." FROM srv_spremenljivka WHERE gru_id='".$this->grupaId."' AND visible='1' ORDER BY vrstni_red ASC";
		$sqlSpremenljivke = sisplet_query($sqlSpremenljivkeString);
		$indeksQuestions = 0;
		while ($rowSpremenljivke = mysqli_fetch_assoc($sqlSpremenljivke)){ // sprehodimo se skozi vprasanja

			$questionsArray[] = array(
				"id"=> (float)$rowSpremenljivke['id'],
				"type"=> $this->getQuestionType($rowSpremenljivke['tip']),				
			);

			if($rowSpremenljivke['tip']!=5){
				$questionsArray[$indeksQuestions]["input"] = $this->getQuestionInput($rowSpremenljivke['tip']);
			}
			
			if($this->getQuestionType($rowSpremenljivke['tip'])=='grid'){	//ce je "grid" dodaj "layout"
				$questionsArray[$indeksQuestions]["layout"]=$this->getQuestionLayout($rowSpremenljivke['orientation']);
			}
			
			$questionsArray[$indeksQuestions]["name"] = $rowSpremenljivke['variable'];			
			$questionsArray[$indeksQuestions]["text"] = ($rowSpremenljivke['naslov']);
			
			if($rowSpremenljivke['info']!=''){
				$questionsArray[$indeksQuestions]["note"] = $rowSpremenljivke['info'];
			}	
			
			if($rowSpremenljivke['tip']!=5){
				$questionsArray[$indeksQuestions]["nr_check"] = $this->getQuestionNrCheck($rowSpremenljivke['reminder']);
			}
			
	
			if($this->getQuestionType($rowSpremenljivke['tip'])=='open'){
				if($rowSpremenljivke['tip']==7){	//ce je stevilo
					$questionsArray[$indeksQuestions]["item_text_layout"] = $this->getQuestionItemTextLayout($rowSpremenljivke['tip'], $rowSpremenljivke['enota']);
				}elseif($rowSpremenljivke['tip']==21){ //ce je besedilo
					$questionsArray[$indeksQuestions]["item_text_layout"] = $this->getQuestionItemTextLayout($rowSpremenljivke['tip'], $rowSpremenljivke['text_orientation']);
				}
			}
			
			$items = $this->itemsArray($rowSpremenljivke['id'], $rowSpremenljivke['tip'], $rowSpremenljivke['variable']);
			if(!empty($items)){	//ce so itemi v polju
				$questionsArray[$indeksQuestions]["items"] = $items;
			}
 			
			if ( in_array($rowSpremenljivke['tip'], array(16)) ) {				
				$questionsArray[$indeksQuestions]["col_items"] = $this->colItemsArray($rowSpremenljivke['id']);
			}
			
			$answers = $this->answersArray($rowSpremenljivke['id'], $rowSpremenljivke['tip'], $rowSpremenljivke['variable']);
			if(!empty($answers)){	//ce so odgovori v polju
				$questionsArray[$indeksQuestions]["answers"] = $answers;
			}			
			
			$indeksQuestions++;
		}		
		
		return $questionsArray;
	}
	###################################################

	
	###################################################
	//Funkcija za generiranje polja za items, ki se nahajajo pod "questions"
	private function itemsArray($spr_id, $tip, $name){
		if ( in_array($tip, array(1)) ) {	//radio
			$itemsArray[]=array(
				"id" => (float)$spr_id,
				"name" => $name
			);
		}elseif ( in_array($tip, array(2)) ) { //checkbox
			$databaseRows = " id, variable, naslov, random, other, vrstni_red ";		
 			$sqlVrednostiString = "SELECT ".$databaseRows." FROM srv_vrednost WHERE spr_id='".$spr_id."' AND other>=0 ORDER BY vrstni_red ASC";
			$sqlVrednosti = sisplet_query($sqlVrednostiString);			
			
			$indeksItems = 0;

			while ($rowVrednosti = mysqli_fetch_assoc($sqlVrednosti)){ // sprehodimo se skozi item			
				$itemsArray[$indeksItems]["id"] = (float)$rowVrednosti["id"];
				$itemsArray[$indeksItems]["name"] = $rowVrednosti["variable"];
				$itemsArray[$indeksItems]["text"] = ($rowVrednosti["naslov"]);
				$itemsArray[$indeksItems]["position"] = (float)$rowVrednosti["vrstni_red"];
				$itemsArray[$indeksItems]["sort"] = $this->getAnswerSort($rowVrednosti["random"]);
				$itemsArray[$indeksItems]["text_field"] = $this->getAnswerTextField($rowVrednosti["other"]);
				
				$indeksItems++;
			}
		}elseif ( in_array($tip, array(6)) ) {	//radio grid
			$databaseRows = " id, variable, naslov, random, other ";		
 			$sqlVrednostiString = "SELECT ".$databaseRows." FROM srv_vrednost WHERE spr_id='".$spr_id."' ORDER BY vrstni_red ASC";
			$sqlVrednosti = sisplet_query($sqlVrednostiString);			
			
			$indeksItems = 0;
			while ($rowVrednosti = mysqli_fetch_assoc($sqlVrednosti)){ // sprehodimo se skozi item			
				$itemsArray[$indeksItems]["id"] = (float)$rowVrednosti["id"];
				$itemsArray[$indeksItems]["name"] = $rowVrednosti["variable"];
				$itemsArray[$indeksItems]["text"] = ($rowVrednosti["naslov"]);
				$itemsArray[$indeksItems]["sort"] = $this->getAnswerSort($rowVrednosti["random"]);
				$itemsArray[$indeksItems]["text_field"] = $this->getAnswerTextField($rowVrednosti["other"]);
				
				$indeksItems++;
			}
		}elseif ( in_array($tip, array(5, 7, 21)) ) {	//odprta vprasanja besedilo/stevilo
			$databaseRows = " id, variable, naslov, random, other, vrstni_red ";		
 			$sqlVrednostiString = "SELECT ".$databaseRows." FROM srv_vrednost WHERE spr_id='".$spr_id."' AND other>=0 ORDER BY vrstni_red ASC";
			$sqlVrednosti = sisplet_query($sqlVrednostiString);			
			
			$indeksItems = 0;
			while ($rowVrednosti = mysqli_fetch_assoc($sqlVrednosti)){ // sprehodimo se skozi item			
				$itemsArray[$indeksItems]["id"] = (float)$rowVrednosti["id"];
				$itemsArray[$indeksItems]["name"] = $rowVrednosti["variable"];
				$itemsArray[$indeksItems]["text"] = ($rowVrednosti["naslov"]);				
				$indeksItems++;
			}
		}elseif ( in_array($tip, array(16)) ) {		//checkbox grid		
			$databaseRows = " id, variable, naslov, random, other ";		
 			$sqlVrednostiString = "SELECT ".$databaseRows." FROM srv_vrednost WHERE spr_id='".$spr_id."' ORDER BY vrstni_red ASC";
			$sqlVrednosti = sisplet_query($sqlVrednostiString);			
			
			$indeksItems = 0;
			while ($rowVrednosti = mysqli_fetch_assoc($sqlVrednosti)){ // sprehodimo se skozi item			
				$itemsArray[$indeksItems]["id"] = (float)$rowVrednosti["id"];
				$itemsArray[$indeksItems]["name"] = $rowVrednosti["variable"];
				$itemsArray[$indeksItems]["text"] = ($rowVrednosti["naslov"]);
				$itemsArray[$indeksItems]["sort"] = $this->getAnswerSort($rowVrednosti["random"]);
				$itemsArray[$indeksItems]["text_field"] = $this->getAnswerTextField($rowVrednosti["other"]);
				
				$indeksItems++;
			}			
		}
		
		return $itemsArray;
	}
	###################################################
	
	
	###################################################
	//Funkcija za generiranje polja za col_items, ki se nahajajo pod "questions"
	private function colItemsArray($spr_id){
		$databaseRows = " id, variable, naslov, other ";
		$sqlVrednostiString = "SELECT ".$databaseRows." FROM srv_grid WHERE spr_id='".$spr_id."' AND other>=0 ORDER BY vrstni_red ASC";
		$sqlVrednosti = sisplet_query($sqlVrednostiString);

		$indekscolItems = 0;
		while ($rowVrednosti = mysqli_fetch_assoc($sqlVrednosti)){ // sprehodimo se skozi odgovore		
			$colItemsArray[$indekscolItems]["id"] = (float)$rowVrednosti["id"];
			$colItemsArray[$indekscolItems]["name"] = $rowVrednosti["variable"];
			$colItemsArray[$indekscolItems]["text"] = ($rowVrednosti["naslov"]);
			
			$indekscolItems++;
		}
		
		return $colItemsArray;
	}
	###################################################
	
	
	###################################################
	//Funkcija za generiranje polja za answers, ki se nahajajo pod "questions"
	private function answersArray($spr_id, $tip, $name){
 		if ( in_array($tip, array(1)) ) {	//radio		
			$databaseRows = " id, variable, naslov, random, other ";
			$sqlVrednostiString = "SELECT ".$databaseRows." FROM srv_vrednost WHERE spr_id='".$spr_id."' ORDER BY vrstni_red ASC";
			$sqlVrednosti = sisplet_query($sqlVrednostiString);
			
			$indeksAnswers = 0;
			while ($rowVrednosti = mysqli_fetch_assoc($sqlVrednosti)){ // sprehodimo se skozi odgovore		
				$answersArray[$indeksAnswers]["id"] = (float)$rowVrednosti["id"];
				
				$answersArray[$indeksAnswers]["value"] = $this->getNumOrString($rowVrednosti["variable"]);
				$answersArray[$indeksAnswers]["text"] = $this->getNumOrString($rowVrednosti["naslov"]);
				
				$answersArray[$indeksAnswers]["sort"] = $this->getAnswerSort($rowVrednosti["random"]);
				$answersArray[$indeksAnswers]["missing_value"] = $this->getAnswerMv($rowVrednosti["other"]);
				$answersArray[$indeksAnswers]["text_field"] = $this->getAnswerTextField($rowVrednosti["other"]);
				$indeksAnswers++;
			}
		}elseif ( in_array($tip, array(2)) ) {	//checkbox
			$databaseRows = " id, variable, naslov, random, other, vrstni_red ";		
 			$sqlVrednostiString = "SELECT ".$databaseRows." FROM srv_vrednost WHERE spr_id='".$spr_id."' AND other<0 ORDER BY vrstni_red ASC";
			$sqlVrednosti = sisplet_query($sqlVrednostiString);
			
			$indeksAnswers = 0;
			
			while ($rowVrednosti = mysqli_fetch_assoc($sqlVrednosti)){ // sprehodimo se skozi odgovore		
				$answersArray[$indeksAnswers]["id"] = (float)$rowVrednosti["id"];				
				$answersArray[$indeksAnswers]["value"] = $this->getNumOrString($rowVrednosti["variable"]);
				$answersArray[$indeksAnswers]["text"] = $this->getNumOrString($rowVrednosti["naslov"]);
				$answersArray[$indeksAnswers]["position"] = (float)$rowVrednosti["vrstni_red"];
				$answersArray[$indeksAnswers]["sort"] = $this->getAnswerSort($rowVrednosti["random"]);				
				$answersArray[$indeksAnswers]["missing_value"] = $this->getAnswerMv($rowVrednosti["other"]);
				$answersArray[$indeksAnswers]["text_field"] = $this->getAnswerTextField($rowVrednosti["other"]);				
				$indeksAnswers++;
			}
		}elseif ( in_array($tip, array(6)) ) {	//radio grid
			$databaseRows = " id, variable, naslov, other ";
			$sqlVrednostiString = "SELECT ".$databaseRows." FROM srv_grid WHERE spr_id='".$spr_id."' ORDER BY vrstni_red ASC";
			$sqlVrednosti = sisplet_query($sqlVrednostiString);
			
			$indeksAnswers = 0;
			while ($rowVrednosti = mysqli_fetch_assoc($sqlVrednosti)){ // sprehodimo se skozi odgovore		
				$answersArray[$indeksAnswers]["id"] = (float)$rowVrednosti["id"];
				if($rowVrednosti["other"]>=0){
					$answersArray[$indeksAnswers]["value"] = $this->getNumOrString($rowVrednosti["variable"]);
				}else{
					$answersArray[$indeksAnswers]["value"] = $this->getNumOrString($rowVrednosti["other"]);
				}				
				$answersArray[$indeksAnswers]["text"] = $this->getNumOrString($rowVrednosti["naslov"]);
				$answersArray[$indeksAnswers]["missing_value"] = $this->getAnswerMv($rowVrednosti["other"]);				
				$indeksAnswers++;
			}
		}elseif ( in_array($tip, array(5, 7, 21)) ) {		//odprta vprasanja besedilo/stevilo		
			$databaseRows = " id, variable, naslov, random, other, vrstni_red ";		
 			$sqlVrednostiString = "SELECT ".$databaseRows." FROM srv_vrednost WHERE spr_id='".$spr_id."' AND other<0 ORDER BY vrstni_red ASC";
			$sqlVrednosti = sisplet_query($sqlVrednostiString);
			
			$indeksAnswers = 0;
		
			while ($rowVrednosti = mysqli_fetch_assoc($sqlVrednosti)){ // sprehodimo se skozi odgovore		
				$answersArray[$indeksAnswers]["id"] = (float)$rowVrednosti["id"];				
				$answersArray[$indeksAnswers]["value"] = $this->getNumOrString($rowVrednosti["variable"]);												
				$answersArray[$indeksAnswers]["text"] = $this->getNumOrString($rowVrednosti["naslov"]);		
				$answersArray[$indeksAnswers]["missing_value"] = $this->getAnswerMv($rowVrednosti["other"]);								
				$indeksAnswers++;
			}
		}elseif ( in_array($tip, array(16)) ) {		//checkbox grid
			$databaseRows = " id, variable, naslov, other ";
			$sqlVrednostiString = "SELECT ".$databaseRows." FROM srv_grid WHERE spr_id='".$spr_id."' AND other<0 ORDER BY vrstni_red ASC";
			$sqlVrednosti = sisplet_query($sqlVrednostiString);

			$indeksAnswers = 0;
			while ($rowVrednosti = mysqli_fetch_assoc($sqlVrednosti)){ // sprehodimo se skozi odgovore		
				$answersArray[$indeksAnswers]["id"] = (float)$rowVrednosti["id"];
				$answersArray[$indeksAnswers]["value"] = $this->getNumOrString($rowVrednosti["other"]);
				$answersArray[$indeksAnswers]["text"] = $this->getNumOrString($rowVrednosti["naslov"]);				
				$answersArray[$indeksAnswers]["missing_value"] = $this->getAnswerMv($rowVrednosti["other"]);				
				$indeksAnswers++;
			}
		}
		
		return $answersArray;
	}
	###################################################
	
	
	###################################################
	//Funkcija ki skrbi za izbiro ustrezne vrednosti parametra type
	private function getQuestionType($tip){
		if ( in_array($tip, array(1)) ) {				
			$questionType = "single_answer";		
		}elseif ( in_array($tip, array(2)) ) {				
			$questionType = "multi_answer";
		}elseif ( in_array($tip, array(7, 21)) ) {				
			$questionType = "open";
		}elseif ( in_array($tip, array(6, 16)) ) {				
			$questionType = "grid";
		}elseif ( in_array($tip, array(5)) ) {				
			$questionType = "caption";
		}
		return $questionType;
	}
	###################################################
	
	
	###################################################
	//Funkcija ki skrbi za izbiro ustrezne vrednosti parametra input
	private function getQuestionInput($tip){
		if ( in_array($tip, array(1, 6)) ) {				
			$questionInput = "radio";		
		}elseif ( in_array($tip, array(2, 16)) ) {				
			$questionInput = "checkbox";
		}elseif ( in_array($tip, array(7)) ) {				
			$questionInput = "numeric";
		}elseif ( in_array($tip, array(21, 5)) ) {				
			$questionInput = "text";
		}		
		return $questionInput;
	}
	###################################################
	
	
	###################################################
	//Funkcija ki skrbi za izbiro ustrezne vrednosti parametra layout
	private function getQuestionLayout($orientation){
		//trenutno dodamo samo za grid in rabimo samo "standard_table"
		$questionLayout = "standard_table";
		return $questionLayout;
	}
	###################################################
	
	
	###################################################
	//Funkcija ki skrbi za izbiro ustrezne vrednosti parametra nr_check
	private function getQuestionNrCheck($reminder){
		switch ($reminder){
			case 0:
				$questionNrCheck = "none";
			break;
			case 1:
				$questionNrCheck = "ask";
			break;
			case 2:
				$questionNrCheck = "require";
			break;
		}
		return $questionNrCheck;
	}
	###################################################
	
	
	###################################################
	//Funkcija ki skrbi za izbiro ustrezne vrednosti parametra item_text_layout
	private function getQuestionItemTextLayout($tip, $textLayout){
		if($tip == 7){	//ce je stevilo
			switch ($textLayout){
				case 0:
					$questionItemTextLayout = "none";
				break;
				case 1:
					$questionItemTextLayout = "left";
				break;
				case 2:
					$questionItemTextLayout = "right";
				break;
			}
		}elseif($tip == 21){	//ce je besedilo
			switch ($textLayout){
				case 0:
					$questionItemTextLayout = "none";
				break;
				case 1:
					$questionItemTextLayout = "left";
				break;
				case 2:
					$questionItemTextLayout = "bottom";
				break;
				case 3:
					$questionItemTextLayout = "top";
				break;
			}
		}

		return $questionItemTextLayout;
	}
	###################################################
	
	
	###################################################
	//Funkcija ki skrbi za izbiro ustrezne vrednosti parametra sort
	private function getAnswerSort($sort){
		switch ($sort){
			case 0:
				$answerSort = "list";
			break;
			case 1:
				$answerSort = "random";
			break;
			case 2:
				$answerSort = "ascending";
			break;
			case 3:
				$answerSort = "descending";
			break;
		}
		return $answerSort;
	}
	###################################################
	
	###################################################
	//Funkcija ki skrbi za izbiro ustrezne vrednosti parametra missing_value
	private function getAnswerMv($mv){
		if($mv<0){
			$answerMV = true;
		}else{
			$answerMV = false;
		}
		return $answerMV;
	}
	###################################################
	
	
	###################################################
	//Funkcija ki skrbi za izbiro ustrezne vrednosti parametra text_field
	private function getAnswerTextField($tf){
		if($tf==1){
			$answerTextField = true;
		}else{			
			$answerTextField = false;
		}
		return $answerTextField;
	}
	###################################################
	
	
	###################################################
	//Funkcija ki skrbi za izbiro stevila ali besedila
	private function getNumOrString($check){
		if(is_numeric($check)){
			return (float)$check;
		}else{
			return ($check);
		}
	}	
	###################################################
	
	
	###################################################
	//Funkcija za generiranje json datoteke
	function GenerateJsonFile(){
		global $site_path;
		
		# generating json file
		$filename = $site_path.'admin/survey/modules/mod_json_survey_export/temp/exported_'.$this->anketa.'.json';
		
		$fp = fopen($filename, "w") or
				die ("cannot generate file $filename<br>\n");
		fwrite($fp, $this->json) or
				die ("cannot send data to file<br>\n");
		fclose($fp);
		# generating json file - konec
	}
	##################################################################################################
	
	
	###################################################
	//Funkcija za output in brisanje json datotek
	public function OutputJsonFile(){
		global $site_path;

		//datoteka
		$file = $site_path.'admin/survey/modules/mod_json_survey_export/temp/exported_'.$this->anketa.'.json';
		
		//ime datoteke
		$filename = 'exported_'.$this->anketa.'.json';

		//priprava header za json in forced download
		header('Content-type: application/json; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		//priprava header za json in forced download - konec
		
		readfile($file);
		
		//brisanje temp json datoteke
		unlink($file);
		//brisanje temp json datoteke - konec
	}	
	##################################################################################################
	
}