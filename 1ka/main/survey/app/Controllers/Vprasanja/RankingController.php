<?php
/***************************************
 * Description: Ranking
 *
 * Vprašanje je prisotno:
 *  tip 17
 *
 * Autor: Robert Šmalc
 * Created date: 09.03.2016
 *****************************************/

namespace App\Controllers\Vprasanja;

// Osnovni razredi
use App\Controllers\Controller;
use App\Controllers\HelperController as Helper;
use App\Controllers\LanguageController as Language;
use App\Models\Model;

// Iz admin/survey
use enkaParameters;
use SurveySetting;
use Common;
use SurveyInfo;

class RankingController extends Controller
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

        return new RankingController();
    }

    public function display($spremenljivka, $oblika)
    {	
		$row = Model::select_from_srv_spremenljivka($spremenljivka);

		// Pri WebSM anketi nimamo userja, zato ne izvajamo ajaxa
        $ajax = 'true';
        $usr_id = get('usr_id');
        if (get('anketa') == get('webSMSurvey') && Common::checkModule('websmsurvey') == '1') {
            $ajax = 'false';
            $usr_id = 0;
        }
		
		//********* potrebno za pravilno prikazovanje predogleda in vpogled v posamezen podatek
		$quick_view = json_encode(get('quick_view'));
		
		if(isset($_GET['a'])){				
			if($_GET['a'] == 'preview_spremenljivka'){
				$preview_spremenljivka = 1;
				echo '
					<script>
						var usr_id = 0;
					</script>					
				';
			}
			else{
				$preview_spremenljivka = 0;
				echo '
					<script>
						var usr_id = '.$usr_id.';
					</script>					
				';	
			}
		}
		else{
			$preview_spremenljivka = 0;		
			echo '
				<script>
					var usr_id = '.$usr_id.';
				</script>					
			';				
		}
		//********* potrebno za pravilno prikazovanje predogleda in vpogled v posamezen podatek - konec


        // premikanje - n==k
        if ($row['design'] == 2 && get('mobile') == 0) 
			$this->displayPremikanje($spremenljivka, $preview_spremenljivka, $quick_view, $ajax);

		// prestavljanje za SAZU - n>k 
        else if ($row['design'] == 0 && get('mobile') == 0 
                    && SurveyInfo::getInstance()->checkSurveyModule('sazu') 
                    && in_array($spremenljivka, array('11092569','11092563'))
                )
			$this->displayPrestavljanjeSAZU($spremenljivka, $preview_spremenljivka, $quick_view, $ajax);

		// prestavljanje - n>k
        else if ($row['design'] == 0 && get('mobile') == 0)
			$this->displayPrestavljanje($spremenljivka, $preview_spremenljivka, $quick_view, $ajax);

		// ostevilcevanje - mobile
        else if ($row['design'] == 1 || $row['design'] == 3 || get('mobile') > 0)
			$this->displayOstevilcevanje($spremenljivka, $preview_spremenljivka, $quick_view, $ajax, $oblika);
    }


	// Izrisemo podtip premikanje (n == k)
	private function displayPremikanje($spremenljivka, $preview_spremenljivka, $quick_view, $ajax){
		
		$row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $spremenljivkaParams = new enkaParameters($row['params']);
		$selected = Model::getOtherValue($spremenljivka);
		

		# če smo v quick_view mode ne omogočamo
		if ( (get('quick_view') || $preview_spremenljivka == 1) == false) {
			//javascript za sortable (drag in drop)
			echo "<script>
						$(document).ready(
						function(){
							$('#sortzone_$spremenljivka').sortable({

								items: '.handle, .handle_long',

								opacity: '0.7',
								//stop: function () {
								stop: function (event, ui) {
									if(typeof checkBranching == 'function'){
										checkBranching();
									}										
									frame_height_ranking_premikanje_dyn (ui, $spremenljivka);
									if(" . $ajax . "){

										var sortable_filtered_items = $('#sortzone_$spremenljivka').sortable({
											items: 'div:visible'
										});

										$.post('" . self::$site_url . "main/survey/ajax.php?a=accept_ranking', {
											/*order: $('#sortzone_$spremenljivka:visible').sortable('serialize'), */
											order: sortable_filtered_items.sortable('serialize'), 
											spremenljivka: $spremenljivka, 
											usr_id: usr_id, 
											anketa: srv_meta_anketa_id
										});
									}
								},
								over: function( event, ui ) {
									//frame_height_ranking_premikanje_dyn (ui, $spremenljivka);
								}
							});
							//checkBranching();		// dokler ne premakne elementa je -1, zato naj bo tudi v JS tako
							// checkBranching() sprozimo samo, ce je spremenljivka skrita, ker je vkljucena v enem od pogojev na prejsnji strani
							if ( $('div#spremenljivke_hidden div#spremenljivka_{$spremenljivka}').length > 0){
								if(typeof checkBranching == 'function'){
									checkBranching();
								}
							}
						}
						);
					</script>";
		}


		//ce je bil vrstni red popravljen ze prej (so ze vnosi v bazi)
		$sql1 = sisplet_query("SELECT * FROM srv_data_rating WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id ORDER BY vrstni_red");
		
		$vre_idCache1 = array();
		$otherCache1 = array();
		$vre_idCache2 = array();
		$otherCache2 = array();
		$vre_idCacheN = array();
		$vrstni_redCacheN = array();
		
		if (($num = mysqli_num_rows($sql1)) != 0) {
			//izracun visine
			$size = $num * 50;

			echo '<div class="sortholder">';

			echo '<div id="sortzone_' . $spremenljivka . '" class="sortzone">';

			while ($row1 = mysqli_fetch_array($sql1)) {
				$sql2 = sisplet_query("SELECT id, naslov, other FROM srv_vrednost WHERE id='$row1[vre_id]' ");
				$row2 = mysqli_fetch_array($sql2);

				$naslov = Language::getInstance()->srv_language_vrednost($row2['id']);
				if ($naslov != '') $row2['naslov'] = $naslov;

				// Datapiping
				$row2['naslov'] = Helper::dataPiping($row2['naslov']);
				
				$value = $row2['naslov'];
				$vre_id = $row2['id'];
				array_push($vre_idCache1, $vre_id);
				$length = strlen($value);    //dolzina teksta kategorije odgovora
				$style = '';
				if (get('mobile') == 0 || get('mobile') == 2) {// ce respondent uporablja PC ali tablico
					$class = 'handle moving';
				} else if (get('mobile') == 1) {
					$class = 'ranking_mobile';
				}


				$c = '';
				$other = $row2['other'];    //drugo, po navadi missing
				array_push($otherCache1, $other);
				echo '<div id="spremenljivka_' . $spremenljivka . '_vrednost_' . $vre_id . '" class="' . $class . ' ' . $c . '">' . $value . '</div>' . "\n"; //'#spremenljivka_'+spremenljivka+'_vrednost_'+id					

			}
			echo '</div>';

			//$sqlN = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id = '$spremenljivka' AND vrstni_red>0");
			$sqlN = sisplet_query("SELECT * FROM srv_data_rating WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id ORDER BY vrstni_red");
			//izris stevilk pred vrednostmi
			echo '<ul class="sorting">';
			$stevec = 0;
			//for ($i = 0; $i < mysqli_num_rows($sqlN); $i++) {
			while ($rowN = mysqli_fetch_array($sqlN)) {
				$spr_id = $rowN["spr_id"];
				$vre_id = $rowN["vre_id"];
				array_push($vre_idCacheN, $vre_id);
				$vrstni_red = $rowN["vrstni_red"];
				array_push($vrstni_redCacheN, $vrstni_red);
				//$stevec = $i + 1;
				//$stevec = $stevec + 1;
				echo '<li id="frame_spremenljivka_'.$spremenljivka.'_vrednost_'.$vre_id.'" class="frame_spremenljivka_'.$spremenljivka.'">
						<div class="frame_moving" data="'.$vrstni_red.'">' . $vrstni_red . '.</div>
						</li>' . "\n";								
			}
			echo '</ul>';

			echo '</div>';
			
			//ureditev gladkega delovanja prikazovanja visjih blokov odgovorov (vecvrsticni, s slikami, ipd.)		
			$indeksVre_idCache1 = 0;
			foreach($vre_idCache1 as $vre_id){
				?>
				<script>
					$(document).ready(function () {
						customizeImageView4Respondent(<?=$row['tip']?>, <?=$spremenljivka?>, <?=$vre_id?>, <?=$ajax?>, srv_meta_anketa_id, '<?=self::$site_url?>', usr_id, <?=$otherCache1[$indeksVre_idCache1]?>, <?=get('mobile')?>, <?=$quick_view?>, <?=$preview_spremenljivka?>); //poklici funkcijo za ureditev draggable in droppable
					});
				</script>
				<?
				$indeksVre_idCache1++;
			}
			$indeksvre_idCacheN = 0;
			foreach($vre_idCacheN as $vre_id){
				//**************
					?>
					<script>
						$(document).ready(function () {
							frame_height_ranking_premikanje(<?=$spremenljivka?>, <?=$vre_id?>, <?=$vrstni_redCacheN[$indeksvre_idCacheN]?>);
						});
					</script>
					<?
				//********	
				$indeksvre_idCacheN++;
			}
			//ureditev gladkega delovanja prikazovanja visjih blokov odgovorov (vecvrsticni, s slikami, ipd.) - konec
			
		} //ce gre za prvo popravljanje vrstnega reda (se ni vnosov v srv_data_rating)
		else {
			$order = Model::generate_order_by_field($spremenljivka, get('usr_id'));
			$sql2 = sisplet_query("SELECT id, naslov, other FROM srv_vrednost WHERE spr_id = '$spremenljivka' AND vrstni_red>0 ORDER BY FIELD(vrstni_red, $order)");

			//izracun visine
			$num = mysqli_num_rows($sql2);
			$size = $num * 50;

			echo '<div class="sortholder">';

			echo '<div id="sortzone_' . $spremenljivka . '" class="sortzone">';

			while ($row2 = mysqli_fetch_array($sql2)) {

				$naslov = Language::getInstance()->srv_language_vrednost($row2['id']);
				if ($naslov != '') $row2['naslov'] = $naslov;

				// Datapiping
				$row2['naslov'] = Helper::dataPiping($row2['naslov']);

				$value = $row2['naslov'];
				$vre_id = $row2['id'];
				array_push($vre_idCache2, $vre_id);
				$length = strlen($value);    //dolzina teksta kategorije odgovora
				$style = '';
				if (get('mobile') == 0 || get('mobile') == 2) {// ce respondent uporablja PC ali tablico
					$class = 'handle moving';
				} else if (get('mobile') == 1) {
					$class = 'ranking_mobile';
				}


				$c = '';
				$other = $row2['other'];    //drugo, po navadi missing
				array_push($otherCache2, $other);

				echo '<div id="spremenljivka_' . $spremenljivka . '_vrednost_' . $vre_id . '" class="' . $class . ' ' . $c . '">' . $value . '</div>' . "\n"; //'#spremenljivka_'+spremenljivka+'_vrednost_'+id
				
			}
			echo '</div>';	
			
			$sqlN = sisplet_query("SELECT id, spr_id, vrstni_red FROM srv_vrednost WHERE spr_id = '$spremenljivka' AND vrstni_red>0");
			//izris stevilk pred vrednostmi
			$stevec = 0;
			echo '<ul class="sorting">';
			//for ($i = 0; $i < mysqli_num_rows($sqlN); $i++) {
			while ($rowN = mysqli_fetch_array($sqlN)) {
				$spr_id = $rowN["spr_id"];
				$vre_id = $rowN["id"];					
				$vrstni_red = $rowN["vrstni_red"];
				array_push($vre_idCacheN, $vre_id);					
				array_push($vrstni_redCacheN, $vrstni_red);
				//$stevec = $i + 1;
				$stevec = $stevec + 1;
				echo '<li id="frame_spremenljivka_'.$spremenljivka.'_vrednost_'.$vre_id.'" class="frame_spremenljivka_'.$spremenljivka.'">
							<div class="frame_moving" data="'.$stevec.'">' . $stevec . '.</div>
							</li>' . "\n";
			}
			echo '</ul>';

			echo '</div>';
			//ureditev gladkega delovanja prikazovanja visjih blokov odgovorov (vecvrsticni, s slikami, ipd.)
			$indeksVre_idCache2 = 0;
			foreach($vre_idCache2 as $vre_id){
				?>
				<script>
					$(document).ready(function () {
						customizeImageView4Respondent(<?=$row['tip']?>, <?=$spremenljivka?>, <?=$vre_id?>, <?=$ajax?>, srv_meta_anketa_id, '<?=self::$site_url?>', usr_id, <?=$otherCache2[$indeksVre_idCache2]?>, <?=get('mobile')?>, <?=$quick_view?>, <?=$preview_spremenljivka?>); //poklici funkcijo za ureditev draggable in droppable
					});
				</script>
				<?
				$indeksVre_idCache2++;
			}
			$indeksvre_idCacheN = 0;
			foreach($vre_idCacheN as $vre_id){
				//**************
					?>
					<script>
						$(document).ready(function () {
							frame_height_ranking_premikanje(<?=$spremenljivka?>, <?=$vre_id?>, <?=$vrstni_redCacheN[$indeksvre_idCacheN]?>);
						});
					</script>
					<?
				//********	
				$indeksvre_idCacheN++;
			}
			//ureditev gladkega delovanja prikazovanja visjih blokov odgovorov (vecvrsticni, s slikami, ipd.) - konec
		}
	}

	// Izrisemo podtip prestavljanje (n >= k)
	private function displayPrestavljanje($spremenljivka, $preview_spremenljivka, $quick_view, $ajax){

		$row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $spremenljivkaParams = new enkaParameters($row['params']);
		$selected = Model::getOtherValue($spremenljivka);
		

		$order = Model::generate_order_by_field($spremenljivka, get('usr_id'));
		$sql1 = sisplet_query("SELECT id FROM srv_vrednost WHERE spr_id = '$spremenljivka' AND vrstni_red>0 ORDER BY FIELD(vrstni_red, $order)");

		//izracun visine
		$num = mysqli_num_rows($sql1);
		$size = $num * 37;

		//nastavimo $max (maximum prenesenih vrednosti) -> ce lahko nosimo vse vrednosti ($row['ranking_k'] == 0) je $max stevilo vseh vrednotsti
		if ($row['ranking_k'] == 0)
			$max = mysqli_num_rows($sql1);
		else
			$max = $row['ranking_k'];

		$sqlc = sisplet_query("SELECT * FROM srv_data_rating WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
		$count = mysqli_num_rows($sqlc);

		save('lang_id', (get('lang_id') == null) ? self::$lang['id'] : get('lang_id'));

		//nismo presegli limita ranking_k
		if ($count < $max) {
			# če smo v quick_view mode ne omogočamo
			//if (get('quick_view') == false) {
			if ( (get('quick_view') || $preview_spremenljivka == 1) == false) {
				//javascript za drag in drop
				echo "<script>
							$(document).ready(
							function(){

								$('#half_$spremenljivka div').dblclick(function() {
									var litem = $(this).clone();
									litem.appendTo($('#half2_$spremenljivka'));
									$(this).remove();
									if(typeof checkBranching == 'function'){
										checkBranching();
									}
									if(" . $ajax . ")
										$('#prestavljanje_$spremenljivka').load('" . self::$site_url . "main/survey/ajax.php?a=accept_ranking', {order: $('#half2_$spremenljivka').sortable('serialize'), spremenljivka: $spremenljivka, usr_id: usr_id, lang_id: " . get('lang_id') . ", anketa: srv_meta_anketa_id});
								});
								$('#half2_$spremenljivka div').dblclick(function() {
									var litem = $(this).clone();
									litem.appendTo($('#half_$spremenljivka'));
									$(this).remove();
									if(typeof checkBranching == 'function'){
										checkBranching();
									}
									if(" . $ajax . ")
										$('#prestavljanje_$spremenljivka').load('" . self::$site_url . "main/survey/ajax.php?a=accept_ranking', {order: $('#half2_$spremenljivka').sortable('serialize'), spremenljivka: $spremenljivka, usr_id: usr_id, lang_id: " . get('lang_id') . ", anketa: srv_meta_anketa_id});
								});

								$('#half_$spremenljivka, #half2_$spremenljivka').sortable({
									opacity: '0.7',
									connectWith: ['#half_$spremenljivka, #half2_$spremenljivka'],

									placeholder: 'frame_ranking_hover',

									//stop: function (){
									stop: function (event, ui){
										if(typeof checkBranching == 'function'){
											checkBranching();
										}
										
										if(" . $ajax . "){
											$('#prestavljanje_$spremenljivka').load('" . self::$site_url . "main/survey/ajax.php?a=accept_ranking', {order: $('#half2_$spremenljivka').sortable('serialize'), spremenljivka: $spremenljivka, usr_id: usr_id, lang_id: " . get('lang_id') . ", anketa: srv_meta_anketa_id});
										}
										frame_height_ranking (ui, $spremenljivka);
									},
									over: function( event, ui ) {
										frame_height_ranking (ui, $spremenljivka);
									}
								});
								if(typeof checkBranching == 'function'){
									checkBranching();
								}
							}
						);
					
					</script>";
			}
		} //preneseno je bilo max stevilo vrednosti
		else {
			# če smo v quick_view mode ne omogočamo
			//if (get('quick_view') == false) {
			if ( (get('quick_view') || $preview_spremenljivka == 1) == false) {
				echo "<script>
							$(document).ready(
							function(){

								$('#half2_$spremenljivka div').dblclick(function() {
									var litem = $(this).clone();
									litem.appendTo($('#half_$spremenljivka'));
									$(this).remove();
									if(typeof checkBranching == 'function'){
										checkBranching();
									}
									if(" . $ajax . ")
										$('#prestavljanje_$spremenljivka').load('" . self::$site_url . "main/survey/ajax.php?a=accept_ranking', {order: $('#half2_$spremenljivka').sortable('serialize'), spremenljivka: $spremenljivka, usr_id: usr_id, lang_id: " . get('lang_id') . ", anketa: srv_meta_anketa_id});
								});

								$('#half_$spremenljivka, #half2_$spremenljivka').sortable({
									opacity: '0.7',
									connectWith: ['#half_$spremenljivka'],

									placeholder: 'frame_ranking_hover',

									stop: function (){
										if(typeof checkBranching == 'function'){
											checkBranching();
										}
										if(" . $ajax . ")
											$('#prestavljanje_$spremenljivka').load('" . self::$site_url . "main/survey/ajax.php?a=accept_ranking', {order: $('#half2_$spremenljivka').sortable('serialize'), spremenljivka: $spremenljivka, usr_id: usr_id, lang_id: " . get('lang_id') . ", anketa: srv_meta_anketa_id});
									}
								});
								if(typeof checkBranching == 'function'){
									checkBranching();
								}
							}
						);

					</script>";
			}
		}

		echo '<div id="prestavljanje_' . $spremenljivka . '">';
		echo '<table class="ranking_table">';
		echo '<tr>';
		//zaslon razdelimo na dva dela - izris leve strani
		echo '<td>';

		if (get('lang_id') != null) $_lang = '_' . get('lang_id'); else $_lang = '';
		$srv_ranking_avaliable_categories = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_ranking_avaliable_categories' . $_lang);
		if ($srv_ranking_avaliable_categories == '') $srv_ranking_avaliable_categories = self::$lang['srv_ranking_avaliable_categories'];

		echo '<b>' . $srv_ranking_avaliable_categories . ':</b>';

		echo '<div id="half_' . $spremenljivka . '" class="dropzone" style="height:' . $size . 'px">';
		$sql1 = sisplet_query("SELECT id, naslov, other FROM srv_vrednost WHERE spr_id='$spremenljivka' AND id NOT IN(SELECT vre_id FROM srv_data_rating WHERE spr_id = '$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id) ORDER BY FIELD(vrstni_red, $order)");
		
		$vre_idCacheL = array();
		$otherCacheL = array();

		while ($row1 = mysqli_fetch_array($sql1)) {

			$naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
			if ($naslov != '') $row1['naslov'] = $naslov;

			// Datapiping
			$row1['naslov'] = Helper::dataPiping($row1['naslov']);

			$value = $row1['naslov'];
			$vre_id = $row1['id'];
			array_push($vre_idCacheL, $vre_id);
			$other = $row1['other'];    //drugo, po navadi missing
			array_push($otherCacheL, $other);
			
			$length = strlen($value);    //dolzina teksta kategorije odgovora
			$style = '';
			if (get('mobile') == 0 || get('mobile') == 2) {// ce respondent uporablja PC ali tablico
				$class = 'ranking';
			} else if (get('mobile') == 1) {
				$class = 'ranking_mobile';
			}
			
			$c = '';				
			
			echo '<div id="spremenljivka_' . $spremenljivka . '_vrednost_' . $vre_id . '" class="' . $class . ' ' . $c . '">' . $value . '</div>' . "\n"; //'#spremenljivka_'+spremenljivka+'_vrednost_'+id
		}
		
		echo '</div>';
		echo '</td>';


		//ureditev gladkega delovanja prikazovanja visjih blokov odgovorov (vecvrsticni, s slikami, ipd.)
		$indeksVre_idCacheL = 0;
		foreach($vre_idCacheL as $vre_id){
			?>
			<script>
				$(document).ready(function () {
					customizeImageView4Respondent(<?=$row['tip']?>, <?=$spremenljivka?>, <?=$vre_id?>, <?=$ajax?>, srv_meta_anketa_id, '<?=self::$site_url?>', usr_id, <?=$otherCacheL[$indeksVre_idCacheL]?>, <?=get('mobile')?>, <?=$quick_view?>, <?=$preview_spremenljivka?>); //poklici funkcijo za ureditev draggable in droppable
				});
			</script>
			<?
			$indeksVre_idCacheL++;
		}
		//ureditev gladkega delovanja prikazovanja visjih blokov odgovorov (vecvrsticni, s slikami, ipd.) - konec
		
		//srednja celica (stevilo prenesenih in spodaj puscica)
		echo '<td class="middle">';
		echo '<b>' . $count . '/' . $max . '</b>';
		echo '<img src="' . self::$site_url . 'main/survey/skins/Modern/arrow.png" alt="arrow">';
		echo '</td>';

		//izris desne strani
		echo '<td>';


		$srv_ranking_ranked_categories = SurveySetting::getInstance()->getSurveyMiscSetting('srvlang_srv_ranking_ranked_categories' . $_lang);
		if ($srv_ranking_ranked_categories == '') $srv_ranking_ranked_categories = self::$lang['srv_ranking_ranked_categories'];

		echo '<b>' . $srv_ranking_ranked_categories . ':</b>';

		echo '<div class="dropholder">'; // ker na td ne primer position relative za nastavit position absolute na dropzone


		echo '<div id="half2_' . $spremenljivka . '" class="dropzone">';
		$sql2 = sisplet_query("SELECT vre_id FROM srv_data_rating WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id ORDER BY vrstni_red ASC");
		
		$vre_idCacheD = array();
		$otherCacheD = array();

		while ($row2 = mysqli_fetch_array($sql2)) {
			$sql1 = sisplet_query("SELECT id, naslov, other FROM srv_vrednost WHERE id='$row2[vre_id]' ");
			$row1 = mysqli_fetch_array($sql1);

			$naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
			if ($naslov != '') $row1['naslov'] = $naslov;

			// Datapiping
			$row1['naslov'] = Helper::dataPiping($row1['naslov']);
			

			$value = $row1['naslov'];
			$vre_id = $row1['id'];
			array_push($vre_idCacheD, $vre_id);
			$length = strlen($value);    //dolzina teksta kategorije odgovora
			$style = '';
			if (get('mobile') == 0 || get('mobile') == 2) {// ce respondent uporablja PC ali tablico
				$class = 'ranking';
			} else if (get('mobile') == 1) {
				$class = 'ranking_mobile';
			}


			$c = '';
			$other = $row1['other'];    //drugo, po navadi missing
			array_push($otherCacheD, $other);
			
			echo '<div id="spremenljivka_' . $spremenljivka . '_vrednost_' . $vre_id . '" class="' . $class . ' ' . $c . '">' . $value . '</div>' . "\n"; //'#spremenljivka_'+spremenljivka+'_vrednost_'+id
		}
		echo '</div>';
		
		//ureditev gladkega delovanja prikazovanja visjih blokov odgovorov (vecvrsticni, s slikami, ipd.)
		$indeksVre_idCacheD = 0;
		foreach($vre_idCacheD as $vre_id){
			?>
			<script>
				$(document).ready(function () {
					customizeImageView4Respondent(<?=$row['tip']?>, <?=$spremenljivka?>, <?=$vre_id?>, <?=$ajax?>, srv_meta_anketa_id, '<?=self::$site_url?>', usr_id, <?=$otherCacheD[$indeksVre_idCacheD]?>, <?=get('mobile')?>, <?=$quick_view?>, <?=$preview_spremenljivka?>); //poklici funkcijo za ureditev draggable in droppable
				});
			</script>
			<?
			$indeksVre_idCacheD++;
		}
		//ureditev gladkega delovanja prikazovanja visjih blokov odgovorov (vecvrsticni, s slikami, ipd.) - konec
		
		
		?>
		<script>
			$(document).ready(function () {
				question_height_ranking(<?=$spremenljivka?>);
			});
		</script>
		<?


		//izris osencenih polj (ranking_k)

		echo '<ul>';
		for ($i = 0; $i < $max; $i++) {
			$stevec = $i + 1;
			echo '<li>
							<div class="frame_ranking" onHover="">' . $stevec . '.</div>
						</li>' . "\n";
		}
		echo '</ul>';


		echo '</div>'; //dropholder

		echo '</td>';


		echo '</tr>';
		echo '</table>';
		echo '</div>';

		$sql2_Refresh = sisplet_query("SELECT * FROM srv_data_rating WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' ");
		while ($row2_Refresh = mysqli_fetch_array($sql2_Refresh)) {
			$spr_id = $row2_Refresh["spr_id"];
			$vre_id = $row2_Refresh["vre_id"];
			$vrstni_red = $row2_Refresh["vrstni_red"];
			if (!empty($row2_Refresh)) {    //ce je ze kaj v bazi
				?>
				<script>
					$(document).ready(function () {
						var trenutna_visina_prenesenega = $('#spremenljivka_<?=$spr_id?>_vrednost_<?=$vre_id?>').height()
						//console.log(trenutna_visina_prenesenega);
						var i = 0;
						$('#half2_<?=$spr_id?>').siblings().children('li').children('div').each(function(){	//preleti prazne okvirje
							i = i + 1;
							if(<?=$vrstni_red?> == i){
								$(this).height(trenutna_visina_prenesenega);
								//console.log(i);
							}
						});
						if(typeof checkBranching == 'function'){
							checkBranching();
						}
					});
				</script>
				<?
			}
		}	
	}

	// Izrisemo podtip prestavljanje za sazu (vodoravno postavljene slike)
	private function displayPrestavljanjeSAZU($spremenljivka, $preview_spremenljivka, $quick_view, $ajax){

		$row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $spremenljivkaParams = new enkaParameters($row['params']);
		$selected = Model::getOtherValue($spremenljivka);
		

		$order = Model::generate_order_by_field($spremenljivka, get('usr_id'));

		//izracun visine
		$size = 3 * 37;
		$max = 3;

		$sqlc = sisplet_query("SELECT * FROM srv_data_rating WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
		$count = mysqli_num_rows($sqlc);

		save('lang_id', (get('lang_id') == null) ? self::$lang['id'] : get('lang_id'));

		// nismo presegli limita ranking_k
		if ($count < $max) {

			# če smo v quick_view mode ne omogočamo
			if ( (get('quick_view') || $preview_spremenljivka == 1) == false) {
				
				//javascript za drag in drop
				echo "<script>
							$(document).ready(
							function(){

								$('#half_$spremenljivka div').dblclick(function() {
									var litem = $(this).clone();
									litem.appendTo($('#half2_$spremenljivka'));
									$(this).remove();
									if(typeof checkBranching == 'function'){
										checkBranching();
									}
									if(" . $ajax . ")
										$('#prestavljanje_$spremenljivka').load('" . self::$site_url . "main/survey/ajax.php?a=accept_ranking', {order: $('#half2_$spremenljivka').sortable('serialize'), spremenljivka: $spremenljivka, usr_id: usr_id, lang_id: " . get('lang_id') . ", anketa: srv_meta_anketa_id});
								});
								$('#half2_$spremenljivka div').dblclick(function() {
									var litem = $(this).clone();
									litem.appendTo($('#half_$spremenljivka'));
									$(this).remove();
									if(typeof checkBranching == 'function'){
										checkBranching();
									}
									if(" . $ajax . ")
										$('#prestavljanje_$spremenljivka').load('" . self::$site_url . "main/survey/ajax.php?a=accept_ranking', {order: $('#half2_$spremenljivka').sortable('serialize'), spremenljivka: $spremenljivka, usr_id: usr_id, lang_id: " . get('lang_id') . ", anketa: srv_meta_anketa_id});
								});

								$('#half_$spremenljivka, #half2_$spremenljivka').sortable({
									opacity: '0.7',
									connectWith: ['#half_$spremenljivka, #half2_$spremenljivka'],

									placeholder: 'frame_ranking_hover',

									//stop: function (){
									stop: function (event, ui){
										if(typeof checkBranching == 'function'){
											checkBranching();
										}
										
										if(" . $ajax . "){
											$('#prestavljanje_$spremenljivka').load('" . self::$site_url . "main/survey/ajax.php?a=accept_ranking', {order: $('#half2_$spremenljivka').sortable('serialize'), spremenljivka: $spremenljivka, usr_id: usr_id, lang_id: " . get('lang_id') . ", anketa: srv_meta_anketa_id});
										}
									}
								});
								if(typeof checkBranching == 'function'){
									checkBranching();
								}
							}
						);
					
					</script>";
			}
		} //preneseno je bilo max stevilo vrednosti
		else {
			# če smo v quick_view mode ne omogočamo
			//if (get('quick_view') == false) {
			if ( (get('quick_view') || $preview_spremenljivka == 1) == false) {
				echo "<script>
							$(document).ready(
							function(){

								$('#half2_$spremenljivka div').dblclick(function() {
									var litem = $(this).clone();
									litem.appendTo($('#half_$spremenljivka'));
									$(this).remove();
									if(typeof checkBranching == 'function'){
										checkBranching();
									}
									if(" . $ajax . ")
										$('#prestavljanje_$spremenljivka').load('" . self::$site_url . "main/survey/ajax.php?a=accept_ranking', {order: $('#half2_$spremenljivka').sortable('serialize'), spremenljivka: $spremenljivka, usr_id: usr_id, lang_id: " . get('lang_id') . ", anketa: srv_meta_anketa_id});
								});

								$('#half_$spremenljivka, #half2_$spremenljivka').sortable({
									opacity: '0.7',
									connectWith: ['#half_$spremenljivka'],

									placeholder: 'frame_ranking_hover',

									stop: function (){
										if(typeof checkBranching == 'function'){
											checkBranching();
										}
										if(" . $ajax . ")
											$('#prestavljanje_$spremenljivka').load('" . self::$site_url . "main/survey/ajax.php?a=accept_ranking', {order: $('#half2_$spremenljivka').sortable('serialize'), spremenljivka: $spremenljivka, usr_id: usr_id, lang_id: " . get('lang_id') . ", anketa: srv_meta_anketa_id});
									}
								});
								if(typeof checkBranching == 'function'){
									checkBranching();
								}
							}
						);

					</script>";
			}
		}


		// Izris drag in drop slik
		echo '<div id="prestavljanje_' . $spremenljivka . '">';
		echo '<div class="ranking_table">';
	

		// izris zgornje vrstice iz kjer jemljemo slike
		echo '<div class="grab_row">';

		echo '<div id="half_' . $spremenljivka . '" class="dropzone">';

		$sql1 = sisplet_query("SELECT id, naslov, other, if_id FROM srv_vrednost WHERE spr_id='$spremenljivka' ORDER BY RAND()");
		//$sql1 = sisplet_query("SELECT id, naslov, other FROM srv_vrednost WHERE spr_id='$spremenljivka' AND id NOT IN(SELECT vre_id FROM srv_data_rating WHERE spr_id = '$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id) ORDER BY FIELD(vrstni_red, $order)");
		
		while ($row1 = mysqli_fetch_array($sql1)) {
	
			$naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
			if ($naslov != '') $row1['naslov'] = $naslov;

			// Datapiping
			$row1['naslov'] = Helper::dataPiping($row1['naslov']);

			$value = $row1['naslov'];
			$vre_id = $row1['id'];

			if (get('mobile') == 0 || get('mobile') == 2) {// ce respondent uporablja PC ali tablico
				$class = 'ranking';
			} 
			else if (get('mobile') == 1) {
				$class = 'ranking_mobile';
			}		
			
			$sql1V = sisplet_query("SELECT * FROM srv_data_rating WHERE vre_id = '$row1[id]' AND spr_id = '$spremenljivka' AND usr_id='" . get('usr_id') . "'");
			if(mysqli_num_rows($sql1V) == 0){
				// '#spremenljivka_'+spremenljivka+'_vrednost_'+id
				echo '<div id="spremenljivka_' . $spremenljivka . '_vrednost_' . $vre_id . '" class="' . $class . '">' . $value . '</div>' . "\n"; 
				//echo '<div id="spremenljivka_' . $spremenljivka . '_vrednost_' . $vre_id . '" class="' . $class . '"  '.($row1['if_id'] > 0 ? ' style="display:none"' : '').'>' . $value . '</div>' . "\n"; 
			}
		}
		
		echo '</div>';

		// End grab_row
		echo '</div>';


		// izris spodnje vrstice kamor spustimo slike
		echo '<div class="drop_row">';

		// izris osencenih polj (ranking_k)
		echo '<div class="drop_frames">';
		for ($i = 1; $i <= 3; $i++) {
			echo '<div class="frame_ranking">' . $i . '</div>' . "\n";
		}
		echo '</div>';

		echo '<div id="half2_' . $spremenljivka . '" class="dropzone">';
		$sql2 = sisplet_query("SELECT vre_id FROM srv_data_rating WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id ORDER BY vrstni_red ASC");

		while ($row2 = mysqli_fetch_array($sql2)) {
			$sql1 = sisplet_query("SELECT id, naslov, other FROM srv_vrednost WHERE id='$row2[vre_id]' ");
			$row1 = mysqli_fetch_array($sql1);
		
			$naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
			if ($naslov != '') $row1['naslov'] = $naslov;
			
			// Datapiping
			$row1['naslov'] = Helper::dataPiping($row1['naslov']);
			
			$value = $row1['naslov'];
			$vre_id = $row1['id'];

			if (get('mobile') == 0 || get('mobile') == 2) {// ce respondent uporablja PC ali tablico
				$class = 'ranking';
			} 
			else if (get('mobile') == 1) {
				$class = 'ranking_mobile';
			}
			
			//'#spremenljivka_'+spremenljivka+'_vrednost_'+id
			echo '<div id="spremenljivka_' . $spremenljivka . '_vrednost_' . $vre_id . '" class="' . $class . '">' . $value . '</div>' . "\n"; 
		}
		echo '</div>';

		
		// END drop_row		
		echo '</div>';	

		
		// END ranking_table
		echo '</div>';
		echo '</div>';


		// Large image on hover
		echo '<script>
			$(".ranking").hover(
				function enter(el){				
					var content = $(this).html();

					$(this).append( $("<div class=\"sazu_holder2\">" + content + "</div>") );
					$(this).css("z-index", 3000);
				},
				function exit(){
					$("div.sazu_holder2").remove();
					$(".ranking").css("z-index", 20);
				}
			);
			$(".ranking img").mousedown(
				function(){
					$("div.sazu_holder2").remove();
					$(".ranking").css("z-index", 20);
				}
			);
		</script>';
	}

	// Izrisemo podtip ostevilcevanje (mobile)
	private function displayOstevilcevanje($spremenljivka, $preview_spremenljivka, $quick_view, $ajax, $oblika){

		$row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $spremenljivkaParams = new enkaParameters($row['params']);
		$selected = Model::getOtherValue($spremenljivka);
		

		$order = Model::generate_order_by_field($spremenljivka, get('usr_id'));
		$sql1 = sisplet_query("SELECT id, naslov, if_id FROM srv_vrednost WHERE spr_id = '$spremenljivka' AND vrstni_red>0 ORDER BY FIELD(vrstni_red, $order)");

		//nastavimo $max (maximum prenesenih vrednosti) -> ce lahko nosimo vse vrednosti ($row['ranking_k'] == 0) je $max stevilo vseh vrednotsti
		if ($row['ranking_k'] == 0)
			$max = mysqli_num_rows($sql1);
		else
			$max = $row['ranking_k'];

		$count = mysqli_num_rows($sql1);

		$counter = 0;

		while ($row1 = mysqli_fetch_array($sql1)) {

			$naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
			if ($naslov != '') $row1['naslov'] = $naslov;

			// Datapiping
			$row1['naslov'] = Helper::dataPiping($row1['naslov']);

			$sql2 = sisplet_query("SELECT * FROM srv_data_rating WHERE vre_id='$row1[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
			$row2 = mysqli_fetch_array($sql2);

			echo '<div class="variabla' . $oblika['cssFloat'] . '" id="vrednost_if_' . $row1['id'] . '"' . ($row1['if_id'] > 0 ? ' style="display:none"' : '') . '>';
			
			// Poseben input za SAZU
			if(SurveyInfo::getInstance()->checkSurveyModule('sazu')){
				echo '     <input 
								vred_id='.$row1['id'].' 
								type="hidden" 
								name="spremenljivka_' . $spremenljivka . '_vrednost_' . $row1['id'] . '" 
								id="spremenljivka_' . $spremenljivka . '_ranking_cifre_' . $counter . '" 
								value="' . $row2['vrstni_red'] . '" 
							> ';

				echo '<br />';

				echo '<select class="sazu_select" id="sazu_select_'.$counter.'" onChange="sazuSelect(this.value, \''.$spremenljivka.'\', \''.$counter.'\');">
					<option></option>
					<option value="1" '.($row2['vrstni_red'] == "1" ? ' selected="selected"' : '').'>1</option>
					<option value="2" '.($row2['vrstni_red'] == "2" ? ' selected="selected"' : '').'>2</option>
					<option value="3" '.($row2['vrstni_red'] == "3" ? ' selected="selected"' : '').'>3</option>
				</select>';

				echo $row1['naslov'];
			}
			else{
                echo '     <input 
								vred_id='.$row1['id'].' 
								type="hidden" 
								name="spremenljivka_' . $spremenljivka . '_vrednost_' . $row1['id'] . '" 
								id="spremenljivka_' . $spremenljivka . '_ranking_cifre_' . $counter . '" 
								value="' . $row2['vrstni_red'] . '" 
							> ';

				echo '<br />';

                echo '<select class="ranking_select" id="ranking_select_'.$counter.'" onChange="rankingSelect(this.value, \''.$spremenljivka.'\', \''.$counter.'\');">';
                echo '  <option></option>';
                
                // Ce imamo omejitev stevila izbir
                for($i=1; $i<=$max; $i++){
                    echo '  <option value="'.$i.'" '.($row2['vrstni_red'] == $i ? ' selected="selected"' : '').'>'.$i.'</option>';
                }
                
                echo '</select>';

				echo $row1['naslov'];
			}			

			echo '</div>' . "\n";
			$counter++;
		}

        // Preverimo in omogocimo/onemogocimo vrednosti pri loadu, ce imamo slucajno ze izpolnjene
        echo '<script> $(document).ready(rankingSelectCheckAll()); </script>';

		// Script za SAZU
		if(SurveyInfo::getInstance()->checkSurveyModule('sazu')){
			echo '<script> $(document).ready(sazuSelectCheck()); </script>';
		}

		if ($row['ranking_k'] != 0)
			echo '<span class="limit">(' . self::$lang['srv_max_answers'] . ': ' . $max . ')</span>';
	}
}