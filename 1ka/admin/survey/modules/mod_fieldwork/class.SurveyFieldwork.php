<?php
/** class ki skrbi za delo s tablicami
 * in laptopi (fieldwork naprave)
 *
 */

			// takole gre:
                        
                        // tablicam daš unique_id (naredi skripto za generiranje)
                        // tu si zabeležiš, katere tablice lahko pišejo v to anketo, v tabelo prevodov:
                        // TABLET_ID - TABLET_ANK - TALE_ANK
                        // vzameš "tisti" merge, ampak dodaš da popravi ID (da vzame prave)
                        
                        // deployment (kasneje)

class SurveyFieldwork {
	private $sid;					# id ankete
	private $surveySettings;			# zakeširamo nastavitve ankete

	var $isAnketar = false;
		
	function __construct($sid) {
		
		$this->sid = $sid;
		
		SurveyInfo::SurveyInit($this->sid);
		$this->surveySettings = SurveyInfo::getInstance()->getSurveyRow();
		SurveyDataSettingProfiles :: Init($this->sid);
		
		$d = new Dostop();
		$this->isAnketar = $d->isAnketar();

	}
        
        public function action ($action) {
            switch ($action) {
                case "neki":
                    die();
                    break;
                default:
                    $this->nastavitve();
                    break;
            }
        }
        
        private function nastavitve () {
            global $lang;

?>
            <fieldset>
                <legend><?=$lang['srv_fieldwork_devices']?> <?=Help::display('fieldwork_devices')?></legend>
                
                
                
<?php
                $result = sisplet_query ("SELECT id, terminal_id, sid_terminal, secret, lastnum FROM srv_fieldwork WHERE sid_server='" .$this->sid ."'");
                
                if (mysqli_num_rows ($result) == 0) {
                    echo $lang['srv_fieldwork_no_devices'];
                }
                else {
                    
                    echo '<table class="dataTable"><thead><tr><td>Naziv naprave</td><td>Geslo</td><td>ID ankete na napravi</td><td>Numerus</td><td>Briši</td></tr></thead>';
                    
                    while ($r = mysqli_fetch_assoc ($result)) {
?>
                <tr>
                    <td><?=$r['terminal_id']?></td>
                    <td><?=$r['secret']?></td>
                    <td><?=$r['sid_terminal']?></td>
                    <td><?=$r['lastnum']?></td>
                    <td><a href="ajax.php?a=anketadeldevice&dev=<?=$r['id']?>&srv=<?=$this->sid?>">x</a></td>
                </tr>

                
<?php
                }
                echo '</table>';
                }
?>
            </fieldset>
            <br><br>
            <fieldset>
                <legend><?=$lang['srv_fieldwork_add_device']?></legend>
                <form name="addtablet" id="addtablet" method="post" action="ajax.php?a=anketaadddevice">
                    <input type="hidden" name="sid" value="<?=$this->sid?>">
                        <span class="nastavitveSpan3 bold"><?=$lang['srv_fieldwork_device_name']?>:&nbsp;</span>                      
                        <label> <input type="text" name="tablet_name"> <i>(npr. T01)</i></label>
                        <br>    
                        <span class="nastavitveSpan3 bold"><?=$lang['srv_fieldwork_device_pass']?>:&nbsp;</span>                      
                        <label><input type="text" name="tablet_secret"> <i>(npr. zrbrtbdtzsythw6r)</i></label>
                        <br>    
                        <span class="nastavitveSpan3 bold"><?=$lang['srv_fieldwork_device_surveyID']?>:&nbsp;</span>                      
                        <label><input type="number" name="terminal_srv_id" id="term_srv_id"> <i>(npr. 12)</i></label>
                        <br>    
                        
                        <span class="floatLeft spaceRight">
                            <div class="buttonwrapper">
                                <a class="ovalbutton ovalbutton_orange btn_savesettings" href="#" onclick="$('#addtablet').submit();"><?=$lang['srv_fieldwork_add_device']?></a></div>
                        </span>  
                </form>
            </fieldset>
            <br>
            
            <span class="floatLeft spaceRight">
                <div class="buttonwrapper">
                    <a class="ovalbutton ovalbutton_orange btn_savesettings" href="/utils/SurveySyncMergeImport.php?srv_id=<?=$this->sid?>" ><?=$lang['srv_fieldwork_sync_data']?></a></div>
            </span>
            
                <?php
                
            if (isset ($_GET['n'])) {
                if (is_numeric ($_GET['n']) && $_GET['n'] > 0) {
                    echo '<strong>Podatki so uspešno uvoženi v novo anketo, <a href="index.php?anketa=' .$_GET['n'] .'" target="_blank">odpri jo</a></strong>';
                }
                else {
                    echo '<strong>Pri uvažanju je prišlo do težave.</strong>';
                }
            }
                
        }
        
        private function doImport () {
            // ko bom prenesel sem iz /utils/
        }
}
