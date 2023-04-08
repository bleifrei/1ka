<?php

/*
 *  Modul za volitve
 */


class SurveyVoting{

	var $anketa;				# id ankete

	
	function __construct($anketa){
		global $site_url;

		// Ce imamo anketo
		if ((int)$anketa > 0){
			$this->anketa = $anketa;
		}
	}
	
	
    // Izvedemo vse potrebno pri vklopu (vklopimo obvescanje, ugasnemo belezenje parapodatkov...)
	public function turnOnVoting(){
		global $lang;

        SurveySetting::getInstance()->Init($this->anketa);

        // Ugasnimo belezenje vseh parapodatkov
        SurveySetting::getInstance()->setSurveyMiscSetting('survey_ip', '1');
        SurveySetting::getInstance()->setSurveyMiscSetting('survey_show_ip', '0');
        SurveySetting::getInstance()->setSurveyMiscSetting('survey_browser', '1');
        SurveySetting::getInstance()->setSurveyMiscSetting('survey_referal', '1');
        SurveySetting::getInstance()->setSurveyMiscSetting('survey_date', '1');

        // Vklopimo email vabila
        sisplet_query("UPDATE srv_anketa SET user_base='1', show_email='0' WHERE id='".$this->anketa."'");
        sisplet_query("INSERT INTO srv_anketa_module (ank_id, modul) VALUES ('".$this->anketa."', 'email')");
        
        // Ugasnemo obvescanje respondenta
        sisplet_query("UPDATE srv_alert SET finish_respondent='0', finish_respondent_cms='0' WHERE ank_id='".$this->anketa."'");
	}
    
	// Nastavitve volitev
	public function displaySettings(){
		global $lang;
    			
        echo '<fieldset><legend>'.$lang['settings'].'</legend>';

        echo '<br>';

        echo $lang['srv_voting_edit1'].' <a href="index.php?anketa='.$this->anketa.'&amp;a='.A_BRANCHING.'"><span class="bold">'.$lang['srv_voting_edit2'].'</span></a>.';
        
        echo '<br><br>';
        
        echo $lang['srv_voting_invitations1'].' <a href="index.php?anketa='.$this->anketa.'&amp;a='.A_INVITATIONS.'"><span class="bold">'.$lang['srv_voting_invitations2'].'</span></a>.';

        echo '<br><br>';

        echo '</fieldset>';
	}
	
    // Pridobimo trenutne nastavitve volitev za anketo
    private function getSettings(){
            
        $settings = array();
                
        return $settings;
    }
    
}