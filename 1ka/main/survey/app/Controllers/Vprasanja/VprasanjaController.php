<?php
/***************************************
 * Description: Tukaj kličemo vse tipe vprašanj, ki so nato v posameznih razredih
 * Autor: Robert Šmalc
 * Created date: 29.02.2016
 **************************************
 * TIPI VPRAŠANJ:
 *  radio            -> tip = 1
 *    checkbox        -> tip = 2
 *    select            -> tip = 3
 *    text            -> tip = 4    // ni vec v uporabi
 *    besedilo*        -> tip = 21
 *    label            -> tip = 5
 *    multigrid        -> tip = 6
 *    multicheckbox   -> tip = 16
 *    multitext        -> tip = 19
 *    multinumber        -> tip = 20
 *    number            -> tip = 7
 *    compute            -> tip = 22    // samo v naprednejših anketah (ifi ali test anketa)
 *    quota            -> tip = 25    // samo v naprednejših anketah (ifi ali test anketa)
 *    datum            -> tip = 8
 *    ranking            -> tip = 17
 *    vsota            -> tip = 18
 *    grid - multiple    -> tip = 24
 *   iz knjiznice    -> tip = 23 // podtip nam pove za tip vprasanja, ki ga poiscemo glede na variablo
 *    SN-imena        -> tip = 9
 *    Map-lokacija        -> tip = 26
 *    HeatMap        -> tip = 27
 *****************************************/

namespace App\Controllers\Vprasanja;


use App\Controllers\Controller;
use App\Controllers\FindController as Find;
use App\Controllers\HeaderController as Header;
use App\Controllers\HelperController as Helper;
use App\Controllers\LanguageController as Language;
use App\Controllers\StatisticController as Statistic;
use App\Controllers\Vprasanja\ComputeController as Compute;
use App\Controllers\Vprasanja\DatumController as Datum;
use App\Controllers\Vprasanja\DoubleController as Double;
use App\Controllers\Vprasanja\DragDropController as DragDrop;
use App\Controllers\Vprasanja\DynamicController as Dynamic;
use App\Controllers\Vprasanja\ImenaController as Imena;
use App\Controllers\Vprasanja\MaxDiffController as MaxDiff;
use App\Controllers\Vprasanja\MultigridController as Multigrid;
use App\Controllers\Vprasanja\MultigridMobileController as MultigridMobile;
use App\Controllers\Vprasanja\NumberController as Number;
use App\Controllers\Vprasanja\OneAgainstAnotherController as OneAgainstAnother;
use App\Controllers\Vprasanja\QuotaController as Quota;
use App\Controllers\Vprasanja\RadioCheckboxSelectController as RadioCheckboxSelect;
use App\Controllers\Vprasanja\RankingController as Ranking;
use App\Controllers\Vprasanja\SystemVariableController as SystemVariable;
use App\Controllers\Vprasanja\TextController as Text;
use App\Controllers\Vprasanja\VprasanjaController as Vprasanja;
use App\Controllers\Vprasanja\VsotaController as Vsota;
use App\Controllers\Vprasanja\MapsController as Maps;
use App\Controllers\Vprasanja\ImageHotSpotController as HotSpot;
use App\Controllers\Vprasanja\HeatMapController as HeatMap;
use App\Models\Model;
use Branching;
use enkaParameters;
use SurveyInfo;
use SurveySetting;
use SurveySlideshow;
use UserAccess;


class VprasanjaController extends Controller
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
        if (self::$_instance) {
            self::refreshVariables();
            return self::$_instance;
        }

        return new VprasanjaController();
    }

    private function refreshVariables()
    {
        return parent::getAllVariables();
    }

    /**
     * @desc prikaze spremenljivke v trenutni grupi
     */
    public function displaySpremenljivke(){

        if (!get('printPreview')) {
            // poiscemo vprasanja s prejsnje strani, ki imajo vklopljeno statistiko
            Statistic::displayStatistika();

            // zgeneriramo sistemske spremenljivke
            Header::getInstance()->displaySistemske();

            // prikazemo skrita ze odgovorjena vprasanja
            Vprasanja::getInstance()->displaySpremenljivkeHidden();
        }

        $offset = 0;
        $zaporedna = 1;

        if (SurveyInfo::getInstance()->getSurveyCountType() > 0) {
            // Preštejemo koliko vprašanj je bilo do sedaj
            $sqlg = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id='" . get('grupa') . "'");
            $rowg = mysqli_fetch_assoc($sqlg);
            $vrstni_red = $rowg['vrstni_red'];

            $sqlCountPast = sisplet_query("SELECT count(*) as cnt FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='" . get('anketa') . "' AND s.gru_id=g.id AND g.vrstni_red < '$vrstni_red' ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");
            $rowCount = mysqli_fetch_assoc($sqlCountPast);
            $offset = $rowCount['cnt'];
        }

        // poiscemo vprasanja / spremenljivke
        // če imamo pri posamezni spremenljivki nastavljeno da jo prikazujemo na začetku vsake strani
        if (get('displayAllPages')) {
            $sql = sisplet_query("SELECT s.id FROM srv_spremenljivka AS s, srv_grupa g WHERE s.gru_id=g.id AND g.ank_id='" . get('anketa') . "' ORDER BY g.vrstni_red, s.vrstni_red ASC");
        } 
        else {
            // Optimizirano
			$sql = sisplet_query("SELECT s.id FROM srv_spremenljivka AS s, srv_grupa g WHERE ((s.gru_id='" . get('grupa') . "' AND s.visible='1' AND g.ank_id='" . get('anketa') . "') OR (s.showOnAllPages = '1' AND s.visible='1' AND g.ank_id='" . get('anketa') . "')) AND s.gru_id=g.id ORDER BY g.vrstni_red, s.vrstni_red ASC");
        }


        if(SurveyInfo::getInstance()->checkSurveyModule('evoli_teammeter') 
            || SurveyInfo::getInstance()->checkSurveyModule('evoli_quality_climate')
            || SurveyInfo::getInstance()->checkSurveyModule('evoli_teamship_meter')
            || SurveyInfo::getInstance()->checkSurveyModule('evoli_organizational_employeeship_meter')
        )
            $evoli_teammeter = true;
        else
            $evoli_teammeter = false;
		
        while ($row = mysqli_fetch_array($sql)) {

			// Izbira departmenta za modul EVOLI TEAMMETER / evoli_quality_climate / evoli_teamship_meter / evoli_organizational_employeeship_meter na prvi strani
			if($evoli_teammeter){
				
				// Ce smo na prvi strani in izpisujemo drugo vprasanje
				$sqlg = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id='" . get('grupa') . "'");
				$rowg = mysqli_fetch_assoc($sqlg);
				$vrstni_red = $rowg['vrstni_red'];
				if($zaporedna == 2 && $rowg['vrstni_red'] == 1){			
					// Dobimo id skupine (podjetja) za respondenta
					$sqlGroupTM = sisplet_query("SELECT d.* FROM srv_data_vrednost_active d, srv_spremenljivka s, srv_grupa g
													WHERE g.ank_id='" . get('anketa') . "' AND d.usr_id='" . get('usr_id') . "' AND s.skupine='1'
														AND s.id=d.spr_id AND g.id=s.gru_id");
					$rowGroupTM = mysqli_fetch_array($sqlGroupTM);
					
					// Loop cez vse oddelke (departments) za izbrano skupino (podjetje)
					$sqlTM = sisplet_query("SELECT d.* FROM srv_evoli_teammeter_department d, srv_evoli_teammeter tm WHERE d.tm_id=tm.id AND tm.skupina_id='".$rowGroupTM['vre_id']."'");
					if(mysqli_num_rows($sqlTM) > 0){
						
						echo '  <div id="spremenljivka_evoli_tm_department" class="spremenljivka lang_pick">' . "\n";

						// Izbira oddelka z dropdown menijem
						if(isset($_GET['language']) && $_GET['language'] == '1'){
							echo '    <p><div class="naslov"><span class="reminder">*</span>Prosimo izberite vašo ekipo, oddelek ali delovno skupino v vaši organizaciji:</div>';
                            echo '    <div class="variable_holder clr"><div class="variabla"><select name="evoli_tm_department" id="evoli_tm_department">';
                            
                            echo '<option value="0">Izberite s seznama</option>';
                            while($rowTM = mysqli_fetch_array($sqlTM)){
                                echo '<option value="' . $rowTM['id'] . '">' . $rowTM['department'] . '</option>';
                            }

                            echo '    </select></div></div>';
                            echo '</p>' . "\n";
                        }
						else{
							echo '    <p><div class="naslov"><span class="reminder">*</span>Please indicate your team, department or working group in your organisation:</div>';
                            echo '    <div class="variable_holder clr"><div class="variabla"><select name="evoli_tm_department" id="evoli_tm_department">';
                            
                            echo '<option value="0">Select from dropdown</option>';
                            while($rowTM = mysqli_fetch_array($sqlTM)){
                                echo '<option value="' . $rowTM['id'] . '">' . $rowTM['department'] . '</option>';
                            }

                            echo '    </select></div></div>';
                            echo '</p>' . "\n";
                        }

						echo '  </div>' . "\n";
					}
				}
			}
		
            if ($zaporedna == 1 && get('loop_id') == null) {    // preverimo, ce je na tej strani LOOP in redirectamo na prvo vrednost
                
                $if_id = Find::find_parent_loop($row['id']);
                if ($if_id > 0) {
                    $sql1 = sisplet_query("SELECT if_id FROM srv_loop WHERE if_id = '$if_id'");
                    $row1 = mysqli_fetch_array($sql1);
                    
                    save('loop_id', Find::getInstance()->findNextLoopId($row1['if_id']));
                    
                    if (get('loop_id') != null) {
                        $loop_id = '&loop_id=' . get('loop_id');
                    } 
                    else {
                        $loop_id = '';
                        save('grupa', Find::getInstance()->findNextGrupa());
                        if (get('grupa') == 0) save('grupa', 'end');
                    }
                   
                    header('Location: ' . SurveyInfo::getSurveyLink() . '&grupa=' . get('grupa') . $loop_id . Header::getSurveyParams() . get('cookie_url') . '');
                    return;
                }
            }

            //ce gre za glasovanje in smo eno vprasanje ze prikazali, ostalih ne prikazemo
            if ((SurveyInfo::getInstance()->getSurveyType() != 0) || ($zaporedna == 1))
                Vprasanja::getInstance()->displaySpremenljivka($row['id'], $offset, $zaporedna);
            
                $zaporedna++;
        }

		
		// JS za mobilno razpiranje tabel
		SurveySetting::getInstance()->Init(get('anketa'));
		$mobile_tables = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_tables');
		if($mobile_tables == 2){
			
			echo '<script>
                    $(document).ready(
                        function(){                  
							mobileMultigridExpandable();
                        }
                    );
				</script>';
        }
        
        // JS za razpiranje tabel znotraj bloka s to nastavitvijo
        echo '<script>
                $(document).ready(
                    function(){                  
                        questionsExpandable();
                    }
                );
            </script>';
		
		
        echo '<script>
            var comments = init_comments_save();

            // GDPR popup
            $(".gdpr_popup_trigger").click(function(){ show_gdpr_about(\''.get('lang_id').'\'); });
        </script>';
    }

    /**
     * @desc prikazemo skrita ze odgovorjena vprasanja
     */
    public function displaySpremenljivkeHidden()
    {

        $sqlg = sisplet_query("SELECT vrstni_red FROM srv_grupa WHERE id='" . get('grupa') . "'");
        $rowg = mysqli_fetch_array($sqlg);
        $vrstni_red = $rowg['vrstni_red'];

        echo "\n";
        echo '<!-- hidden -->' . "\n";
        echo '<div id="spremenljivke_hidden" style="display:none">' . "\n";

        // pri skritih gledamo za nazaj, in ce smo v loopu ne smemo upostevat trenutnega loopa
        $loop_id = get('loop_id');
        save('loop_id', null, 1);

        // Izpisemo vprasanja ki so uporabljena v pogoju in so bila resena na prejsnjih straneh oz. tudi na isti strani ce gre za skupine ali jezike
        $sql = sisplet_query("SELECT s.id FROM srv_spremenljivka s, srv_grupa g WHERE g.ank_id='" . get('anketa') . "' AND s.gru_id=g.id AND (g.vrstni_red<'$vrstni_red' OR (g.vrstni_red='$vrstni_red' AND s.skupine>'0')) ORDER BY g.vrstni_red ASC, s.vrstni_red ASC");
        while ($row = mysqli_fetch_array($sql)) {
            if ($this->inCondition($row['id'])) {
                $this->displaySpremenljivka($row['id']);
            }
        }

        save('loop_id', $loop_id, 1);

        echo '</div>' . "\n";
        echo '<!-- /hidden -->' . "\n\r\n\r";

    }

    /**
     * @desc prikaze komentar spremenljivke
     * shrani se v srv_Data_text, kjer je spr_id=0 in vre_id=$spremenljivka
     */
    public function displaySpremenljivkaComment($spremenljivka){
        
        // Preverimo, ce so komentarji v placljivem paketu
        $userAccess = UserAccess::getInstance(self::$global_user_id);
        if(!$userAccess->checkUserAccess($what='komentarji'))
            return;

        SurveySetting::getInstance()->Init(get('anketa'));
        $srv_qct = SurveySetting::getInstance()->getSurveyMiscSetting('question_comment_text');
        $question_resp_comment_show_open = SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment_show_open');

        SurveySetting::getInstance()->Init(get('anketa'));
        if (get('lang_id') != null) $_lang = '_' . get('lang_id'); else $_lang = '';
        $srv_comment = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_question_respondent_comment' . $_lang);
        $comment = $srv_comment != '' ? $srv_comment : self::$lang['srv_question_respondent_comment'];

        if (get('lang_id') != null) {
            $qct = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_question_comment_text_' . get('lang_id'));
            if ($qct != '') $srv_qct = $qct;
        }

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $sql = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='0' AND vre_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
        $row = mysqli_fetch_array($sql);

        echo '<div class="comment red" onclick="$(\'#question_resp_comment_' . $spremenljivka . '\').toggle();" >' . $comment . '</div>
            <div class="variable_holder ' . ($question_resp_comment_show_open == 1 ? 'display_none' : '') . '" id="question_resp_comment_' . $spremenljivka . '">
            <div class="variabla question_comment">
                <textarea name="question_comment_' . $spremenljivka . '" id="question_comment_' . $spremenljivka . '">' . $row['text'] . '</textarea>
            </div>
            </div>';
    }

    /**
     * @desc prikaze spremenljivko
     */
    public function displaySpremenljivka($spremenljivka, $offset = 0, $zaporedna = null)
    {

        $rowa = SurveyInfo::getInstance()->getSurveyRow();

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        $rowl = Language::srv_language_spremenljivka($spremenljivka);
        if (strip_tags($rowl['naslov']) != '') $row['naslov'] = $rowl['naslov'];
        if (strip_tags($rowl['info']) != '') $row['info'] = $rowl['info'];
        if ($rowl['vsota'] != '') $row['vsota'] = $rowl['vsota'];


        // da dela tudi userjem brez JS, po defaultu vedno prikazemo vsa vprasanja, ki jih potem skrivamo z JS
        $display = '';

        // smo v vpogledu in izpisujemo spremenljivko iz loopa
        $loop_style = (get('loop_id') != null && $_GET['m'] == 'quick_edit') ? ' quick_edit_loop' : '';

        // hidden_default je pri radio buttnu opcija, da je vprasanje skrito, shrani pa se po defaultu prvi odgovor

        if ($row['tip'] == 22 || $row['tip'] == 25 /*|| ($row['tip']==1 && $row['hidden_default']==1)*/) {
            $display = 'display_none';        // compute ali kvota vprašanje je vedno skrito
        }

        // Class za tipe ki jih v hitrem urejanju izpisemo v eni vrstici
        $simple = (!in_array($row['tip'], array(5, 6, 16, 19, 20, 17))) ? ' simple' : '';

        // Class za nagovor - ce ima crto ali ne
        $nagovor_line = '';
        if ($row['tip'] == 5) {
            $spremenljivkaParams = new enkaParameters($row['params']);
            $nagovorLine = ($spremenljivkaParams->get('nagovorLine') ? $spremenljivkaParams->get('nagovorLine') : 0);

            // Brez crte
            if ($nagovorLine == 1)
                $nagovor_line = 'hide_border';
            // S crto
            elseif ($nagovorLine == 2)
                $nagovor_line = 'show_border';
        }

        /*
         * V kolikor sistemsk spremenljivka je uporabljena v pogoju, potem preverimo, vrednost odgovora sistemske spremenljivke in omenjen odgovor v našem primeru samo nagovor prikažemo
         */
        $display_sistemske = '';
        $sql_if = sisplet_query("SELECT * FROM srv_branching WHERE element_spr='$spremenljivka'");
        $row_if = mysqli_fetch_object($sql_if);
        if (!empty($row_if->parent)) {
            $sql_condition = sisplet_query("SELECT * FROM srv_condition WHERE if_id='$row_if->parent'");
            $row_condition = mysqli_fetch_object($sql_condition);

            # V kolikor imamo pogoj, potem preverimo, če je pogoj vezan na sistemske in skrite spremenljivke
            if (!empty($row_condition)) {
                $sql_spr_sistem = sisplet_query("SELECT id FROM srv_spremenljivka WHERE id='$row_condition->spr_id' AND sistem=1 AND visible=0");
                if ($sql_spr_sistem->num_rows > 0)
                    $display_sistemske = 'display_none';
            }

            $sql_condition_vred = sisplet_query("SELECT * FROM srv_condition_vre WHERE cond_id='$row_condition->id'");
            $row_condition_vred = mysqli_fetch_object($sql_condition_vred);

            //preverimo, še če je bilo to vprašanje odgovorjeno in izbrana vrednost odgovora enaka našemu pogoji pri IF stavku
            $sql_data_cond = sisplet_query("SELECT * FROM srv_data_vrednost_active WHERE usr_id='" . get('usr_id') . "' AND spr_id='$row_condition->spr_id'");
            $row_data_cond = mysqli_fetch_object($sql_data_cond);

            if (!empty($row_data_cond->vre_id) && !empty($row_condition_vred->vre_id) && ($row_data_cond->vre_id == $row_condition_vred->vre_id)) {
                $display_sistemske = '';
            }
        }


        if ((self::$admin_type <= $row['dostop'] && self::$admin_type >= 0) || (self::$admin_type == -1 && $row['dostop'] == 4)) {
            // ok
        } else {
            $display = 'display_none';        // user iz sispleta nima dostopa do vprasanja
            return;        // ce damo samo display na none, se npr. v IFih spet pokaze vprasanje...
        }


        if (get('forceShowSpremenljivka')) {  // za prikaz spremenljivke v analizi in mogoče še kje
            $display = '';
        }
        echo "\n\r\n\r";
        echo '  <!-- spremenljivka -->' . "\n";
        echo '  <div id="spremenljivka_' . $spremenljivka . '" class="spremenljivka ' . $simple . ' ' . $nagovor_line . ' tip_' . $row['tip'] . ' ' . $loop_style . ' ' . $display . $display_sistemske . ($row['dostop'] < 4 ? ' limited' : '') . '" data-vrstni_red="' . $row['vrstni_red'] . '">' . "\n";

        // izpis tekstovnega pogoja
        if (isset($_GET['displayifs']) && $_GET['displayifs'] == '1') {
            $b = new Branching(get('anketa'));
            $parents = $b->get_parents($row['id']);

            $parents = explode('p_', $parents);
            foreach ($parents AS $key => $val) {
                if (is_numeric(trim($val))) {
                    $parents[$key] = (int)$val;
                } else {
                    unset($parents[$key]);
                }
            }

            echo '<div>';
            foreach ($parents AS $if) {
                echo '<p>';
                $b->conditions_display($if);
                $b->blocks_display($if);
                echo '</p>';
            }
            echo '</div>';
        }

        echo '    <input type="hidden" name="visible_' . $spremenljivka . '" id="visible_' . $spremenljivka . '" value="' . ($display == '' || $row['hidden_default'] == 1 ? '1' : '0') . '">' . "\n";

        $oblika = array();

        $oblika['orientation'] = $row['orientation'];

        // pri multigridu ne pustimo spremembe orientacije - spremembe ne pustimo tudi ce je resevalec mobitl
        if (($row['orientation'] == 0 || $row['orientation'] == 2)
            && $row['tip'] != 6 && $row['tip'] != 16 && $row['tip'] != 19 && $row['tip'] != 20
            && get('mobile') == 0
        ) {

            // ce ni besedilo
			if($row['tip'] != 21){	
				$oblika['cssFloat'] = ' floatLeft';
            }
            // ce je besedilo in postavitev vodoravno ob vprasanju
            else{	
				$oblika['cssFloat'] = ' besediloObVprasanju';
			}       

            // ce je vodoravno ob vprasanju
            if ($row['orientation'] == 0) {
                $oblika['divClear'] = '';
            } 
            // ce je vodoravno pod vprasanjem
            else {	
                $oblika['divClear'] = 'clr';
				$oblika['cssLineBreak'] = '<br>';
            }
        } 
        else {
            $oblika['cssFloat'] = '';
            $oblika['divClear'] = 'clr';
        }

        // datapiping
        $row['naslov'] = Helper::dataPiping($row['naslov']);

        // izpisemo statistiko za radio, checkbox, dropdown in multigrid
        if ($row['stat'] == 1 && ($row['tip'] <= 3 || $row['tip'] == 6)) {
            echo '    <div id="stat_' . $spremenljivka . '" class="stat">' . "\n";
            echo '    </div>' . "\n";
        }

        // stevilcenje
        if ((SurveyInfo::getInstance()->getSurveyCountType() > 0 || isset($_GET['displayvariables']) && $_GET['displayvariables'] > 0) && get('forceShowSpremenljivka') == false) {
            echo '<div class="counter">';
            if (SurveyInfo::getInstance()->getSurveyCountType() == 1) {
                echo $zaporedna + $offset . ")&nbsp;";
            }
            if (SurveyInfo::getInstance()->getSurveyCountType() == 2 || isset($_GET['displayvariables']) && $_GET['displayvariables'] == 1) {
                echo strip_tags($row['variable']) . '&nbsp;-&nbsp;';
            }
            if (SurveyInfo::getInstance()->getSurveyCountType() == 3) {
                echo $zaporedna + $offset . ")&nbsp;" . strip_tags($row['variable']) . '&nbsp;-&nbsp;';
            }
            echo '</div>';
        }


        echo '<div class="naslov ' . $oblika['cssFloat'] . '">';

        if ($row['reminder'] > 0) // statusi: reminder, in še kaj
            echo '<span class="reminder">*</span>';

        echo $row['naslov'];
        if ($row['info'] != '')
            echo '<p class="spremenljivka_info">' . $row['info'] . '</p>';

        echo '</div>';

        // nagovor nima nicesar
        if ($row['tip'] != 5) {    
            
            // ce je besedilo in postavitev vodoravno ob vprasanju
			if($row['tip'] == 21 && $row['orientation'] == 0){ 
				echo '<div class="variable_holder besediloObVprasanju ' . $oblika['divClear'] . '" ' . ($row['hidden_default'] == 1 ? 'style="display:none"' : '') . '>';
            }
            else{
                // ce je postavitev vodoravno pod vprasanjem
				if($row['orientation'] == 2){	
                    echo '<div class="variable_holder vodoravno_pod_vprasanjem '.$oblika['divClear'].'" '.($row['hidden_default'] == 1 ? 'style="display:none"' : '').'>';
                }
                // ce je postavitev vodoravno ob vprasanju
				elseif($row['orientation'] == 0){	
                    echo '<div class="variable_holder vodoravno_ob_vprasanju '.$oblika['divClear'].'" '.($row['hidden_default'] == 1 ? 'style="display:none"' : '').'>';
                }
                else{
                    //echo '<div class="variable_holder  '.$oblika['divClear'].'" ' . ($row['hidden_default'] == 1 ? 'style="display:none"' : 'style="display:inline"').'>';
                    echo '<div class="variable_holder  '.$oblika['divClear'].'" ' . ($row['hidden_default'] == 1 ? 'style="display:none"' : '').'>';
				}
			}

            if ($rowa['mass_insert'] == '1' && $_GET['m'] != 'quick_edit')
                $this->displayMassVnos($spremenljivka, $oblika);
            else
                $this->displayVnos($spremenljivka, $oblika);

            echo '</div>';
        }


        SurveySetting::getInstance()->Init(get('anketa'));
        if (SurveySetting::getInstance()->getSurveyMiscSetting('question_resp_comment') == 1 &&
            get('forceShowSpremenljivka') == false &&
            !$_GET['hidecomment'] == 1 &&
            ($_GET['preview'] == 'on' && $_GET['testdata'] == 'on')
        ) {
            $this->displaySpremenljivkaComment($spremenljivka);
        }

        $ss = new SurveySlideshow(get('anketa'));
        $ss_setings = $ss->getSettings();

        // timer na spremenljivki
        if ($row['timer'] > 0 && $row['gru_id'] == $_GET['grupa']) {
            echo '<script>' . "\n";
            echo 'setTimeout(\'submitForm()\', ' . ($row['timer'] * 1000) . ');' . "\n";
            echo '</script>' . "\n";
        } 
        else if ((int)$row['timer'] > 0 && SurveyInfo::getInstance()->checkSurveyModule('slideshow')) {
            echo '<script>' . "\n";
            # če smo v prezentaciji in imamo nastavljen autostart

            # če smo na prvi strani in mamo nastavljen autostart z timerjem
            if (!isset($_GET['grupa']) && ($ss_setings['autostart'] == 1 || $ss_setings['autostart'] == 3)) {
                echo 'setTimeout(\'submitForm()\', ' . ($row['timer'] * 1000) . ');' . "\n";
            }
            if (!isset($_GET['grupa']) && ((int)$ss_setings['autostart'] == 2 || (int)$ss_setings['autostart'] == 3)) {
                echo '$(".spremenljivka").addClass("pointer");' . "\n";
                # autostart z klikom
                echo '$(".spremenljivka").live("click", function(event) {submitForm()});' . "\n";
            }
            echo '        </script>' . "\n";
        }

        echo '</div>'; // spremenljivka
    }

    /**
     * preveri, ce se spremenljivka pojavi v katerem izmed pogojev, da jo je potrebno izpisati
     *
     * @param mixed $spremenljivka
     */
    private function inCondition($spremenljivka)
    {

        $sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_condition WHERE spr_id='$spremenljivka'");
        $row = mysqli_fetch_array($sql);
        if ($row['count'] > 0)
            return true;

        $sql = sisplet_query("SELECT COUNT(*) AS count FROM srv_calculation WHERE spr_id='$spremenljivka'");
        $row = mysqli_fetch_array($sql);
        if ($row['count'] > 0)
            return true;

        return false;
    }

    /**
     * prikaze vnos spremenljivke za masovno vnasanje vprasalnika
     *
     * @param mixed $spremenljivka
     * @param mixed $oblika
     */
    function displayMassVnos($spremenljivka, $oblika)
    {

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        switch ($row['tip']) {
            case 1:
            case 3:
                $sql = sisplet_query("SELECT id, vrstni_red FROM srv_vrednost WHERE spr_id='$spremenljivka' AND other='1' ORDER BY vrstni_red ASC");
                $input = '';
                $js = '';
                while ($row = mysqli_fetch_array($sql)) {
                    $input .= ' <input type="text" name="textfield_' . $row['id'] . '" style="display:none" id="textfield_' . $row['id'] . '">';
                    $js .= ' if (this.value == \'' . $row['vrstni_red'] . '\') document.getElementById(\'textfield_' . $row['id'] . '\').style.display = \'inline\'; else document.getElementById(\'textfield_' . $row['id'] . '\').style.display = \'none\'; ';
                }
                echo '<input type="text" name="vrednost_' . $spremenljivka . '" onkeyup="checkBranching(); ' . $js . '" id="vrednost_' . $spremenljivka . '">';
                echo $input;
                break;

            case 2:
                $sql = sisplet_query("SELECT id, naslov, other FROM srv_vrednost WHERE spr_id='$spremenljivka' ORDER BY vrstni_red ASC");
                echo '<table>';
                while ($row = mysqli_fetch_array($sql)) {
                    echo '<tr><td>' . $row['naslov'] . '</td><td><input type="text" name="vrednost_' . $spremenljivka . '[]" onkeyup="checkBranching();" id="spremenljivka_' . $spremenljivka . '_vrednost_' . $row['id'] . '"></td>';
                    if ($row['other'] == 1) echo '<td><input type="text" name="textfield_' . $row['id'] . '"></td>';
                    echo '</tr>';
                }
                echo '</table>';
                break;

            case 6:
                $sql = sisplet_query("SELECT id, naslov, other FROM srv_vrednost WHERE spr_id='$spremenljivka' ORDER BY vrstni_red ASC");
                echo '<table>';
                while ($row = mysqli_fetch_array($sql)) {
                    echo '<tr><td>' . $row['naslov'] . '</td><td><input type="text" name="vrednost_' . $row['id'] . '" onkeyup="checkBranching();" id="spremenljivka_' . $spremenljivka . '_vrednost_' . $row['id'] . '"></td>';
                    if ($row['other'] == 1) echo '<td><input type="text" name="textfield_' . $row['id'] . '"></td>';
                    echo '</tr>';
                }
                echo '</table>';
                break;

            default:
                $this->displayVnos($spremenljivka, $oblika);
                break;
        }
    }

    /**
     * @desc prikaze polja za vnos
     */
    function displayVnos($spremenljivka, $oblika = null)
    {

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $row = Model::select_from_srv_spremenljivka($spremenljivka);

        // Pridobimo parametre
        // nalozimo parametre spremenljivke
        $spremenljivkaParams = new enkaParameters($row['params']);
        $selected = Model::getOtherValue($spremenljivka);


        // Izrisemo ustrezno vsebino vprasanja glede na tip
        switch($row['tip']){

            // Radio, checkbox, dropdown
            case 1:
            case 2:
            case 3:

                if ($row['orientation'] != 8 && $row['orientation'] != 10) {
                    RadioCheckboxSelect::getInstance()->display($spremenljivka, $oblika);
                } 
                elseif ($row['orientation'] == 8) {
                    if(get('mobile') == 1)
                        RadioCheckboxSelect::getInstance()->display($spremenljivka, $oblika);
                    else
                        DragDrop::getInstance()->display($spremenljivka);
                } 
                elseif($row['orientation'] == 10){
                    HotSpot::getInstance()->display($spremenljivka);
                }

                break;


            // Tabela - radio
            case 6:
                
                SurveySetting::getInstance()->Init(get('anketa'));
                $mobile_tables = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_tables');

                // Izris multigrida s postopnim resevanjem
                if($row['dynamic_mg'] > 0 && !get('printPreview')){
                    
                    if ($row['dynamic_mg'] == 1 || $row['dynamic_mg'] == 3 || $row['dynamic_mg'] == 5)
                        Dynamic::getInstance()->multigrid($spremenljivka);
                    else
                        Dynamic::getInstance()->verticalMultigrid($spremenljivka);
                }
                // Izris radio multigrida na mobitelu
                elseif (get('mobile') == 1 && $mobile_tables > 0) {
                    
					// Dvojni multigrid
					if($row['enota'] == 3)
						MultigridMobile::getInstance()->radioDoubleMultigrid($spremenljivka);
					else
						MultigridMobile::getInstance()->radioMultigrid($spremenljivka);
                }
                // Ostali podtipi tabel
                else{

                    // Izris glede na podtip
                    switch($row['enota']){
                        
                        // Multigrid z dropdownom
                        case 2: 
                            Multigrid::getInstance()->dropdown($spremenljivka);
                            break;

                        // Dvojni multigrid
                        case 3: 
                            Double::getInstance()->grid($spremenljivka);
                            break;

                        // OneAgainstAnother
                        case 4: 
                            OneAgainstAnother::getInstance()->display($spremenljivka);
                            break;

                        // MaxDiff
                        case 5: 
                            MaxDiff::getInstance()->display($spremenljivka);
                            break;

                        // SelectBox
                        case 6: 
                            Multigrid::getInstance()->selectBox($spremenljivka);
                            break;
                    
                        // Grid drag and drop
                        case 9: 
                            DragDrop::getInstance()->grid($spremenljivka);
                            break;
                            
                        // Image hotspot za radio grid
                        case 10: 
                            HotSpot::getInstance()->grid($spremenljivka);
                            break;

                        // Navaden multigrid
                        default:
                            Multigrid::getInstance()->display($spremenljivka);
                            break;
                    }
                }

                break;
               
                
            // Tabela - checkbox
            case 16:

                SurveySetting::getInstance()->Init(get('anketa'));
                $mobile_tables = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_tables');

                // Izris checkbox multigrida na mobitelu
                if (get('mobile') == 1 && $mobile_tables > 0) {
                    
					// Dvojni multigrid
					if($row['enota'] == 3)
						MultigridMobile::getInstance()->checkboxDoubleMultigrid($spremenljivka);
					else
						MultigridMobile::getInstance()->checkboxMultigrid($spremenljivka);
                }
                // Izris glede na podtip
                else{
                    switch($row['enota']){

                        // Dvojni multicheckboxa
                        case 3: 
                            Double::getInstance()->checkGrid($spremenljivka);
                            break;

                        // SelectBox
                        case 6: 
                            Multigrid::getInstance()->selectBox($spremenljivka);
                            break;

                        // Navaden checkbox
                        case 0: 
                            Multigrid::getInstance()->checkbox($spremenljivka);
                            break;

                        // Grid drag and drop
                        case 9: 
                            DragDrop::getInstance()->grid($spremenljivka);
                            break;
                    }
                }

                break;
                
            
            // Tabela - multitext in multinumber
            case 19:
            case 20:

                SurveySetting::getInstance()->Init(get('anketa'));
                $mobile_tables = SurveySetting::getInstance()->getSurveyMiscSetting('mobile_tables');

                // Izris text in number multigrida na mobitelu
                if (get('mobile') == 1 && $mobile_tables > 0) {
                    MultigridMobile::getInstance()->textMultigrid($spremenljivka);
                }
                // Izris navadnega text in number multigrida
                else{
                    Text::getInstance()->multitext($spremenljivka);
                }
                
                break;

            
            // Multi Tabela
            case 24:
                Multigrid::getInstance()->multiple($spremenljivka);
                break;


            // Textbox - tega ni vec... naj zaenkrat se ostane za kaksno staro anketo...
            case 4:

                echo '<div class="variabla ' . $oblika['cssFloat'] . '">';
                $sql1 = sisplet_query("SELECT text FROM srv_data_text" . get('db_table') . " WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
                $row1 = mysqli_fetch_array($sql1);

                $taSize = ($spremenljivkaParams->get('taSize') ? $spremenljivkaParams->get('taSize') : 1);
                $taWidth = ($spremenljivkaParams->get('taWidth') ? $spremenljivkaParams->get('taWidth') : -1);
                //default sirina
                if ($taWidth == -1)
                    $taWidth = 30;

                if ($taSize > 1)
                    echo '      <textarea name="vrednost_' . $spremenljivka . '" id="vrednost_' . $spremenljivka . '" rows="' . $taSize . '" style="width: ' . $taWidth . 'em;" onkeyup="checkBranching();"' . ($selected ? ' disabled' : '') . '>' . $row1['text'] . '</textarea>';
                else
                    echo '      <input type="text" name="vrednost_' . $spremenljivka . '" id="vrednost_' . $spremenljivka . '" style="width: ' . $taWidth . 'em;" onkeyup="checkBranching();" value="' . $row1['text'] . '" ' . ($selected ? ' disabled' : '') . '>';
                echo '</div>';

                SystemVariable::getInstance()->display($spremenljivka, $oblika);

                break;


            // Textbox
            case 21:
                Text::getInstance()->textbox($spremenljivka, $oblika);
                break;


            // Number
            case 7:
                Number::getInstance()->display($spremenljivka, $oblika);
                break;


            // Datum
            case 8:
                Datum::getInstance()->display($spremenljivka, $oblika);
                break;


            // Generator imen (SN)
            case 9:
                Imena::getInstance()->display($spremenljivka, $oblika);
                break;


            // Ranking
            case 17:

                // Image hotspot ranking
                if($row['design'] == 3){	
                    HotSpot::getInstance()->ranking($spremenljivka, $oblika);
                }
                else{
                    Ranking::getInstance()->display($spremenljivka, $oblika);
                }
                
                break;


            // Vsota
            case 18:
                Vsota::getInstance()->display($spremenljivka, $oblika);
                break;


            // Compute
            case 22:
                Compute::getInstance()->display($spremenljivka);
                break;


            // Kvota
            case 25:
                Quota::getInstance()->display($spremenljivka);
                break;

            
            // Maps / lokacija
            case 26:
                Maps::getInstance()->display($spremenljivka, $oblika);
                break;

            
            // HeatMap
            case 27:
                HeatMap::getInstance()->display($spremenljivka, $oblika);
                break;


            // Nagovor - prazno
            case 5:
            default:
                break;
        }
    }

}
