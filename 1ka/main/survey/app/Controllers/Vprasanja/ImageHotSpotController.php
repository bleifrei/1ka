<?php
/***************************************
 * Description: Prikaže vprašanje Image HotSpot
 *
 * Vprašanje je prisotno:
 * tip 6 - enota 10
 *
 * Autor: Patrik Pucer
 * Created date: 26.04.2016
 *****************************************/

namespace App\Controllers\Vprasanja;


// Osnovni razredi
use App\Controllers\Controller;
use App\Controllers\HelperController as Helper;
use App\Controllers\LanguageController as Language;
use App\Models\Model;
use enkaParameters;
use SurveySetting;
use App\Controllers\Vprasanja\RadioCheckboxSelectController as RadioCheckboxSelect;
use App\Controllers\Vprasanja\MultigridController as Multigrid;
use App\Controllers\Vprasanja\RankingController as Ranking;

// Iz admin/survey
use SurveyInfo;


class ImageHotSpotController extends Controller
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

        return new ImageHotSpotController();
    }
	
	/**
	 * @desc prikaze vnos za image hotspot za radio
	 */
	public function display($spremenljivka){
		
		$row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $spremenljivkaParams = new enkaParameters($row['params']);
        $selected = Model::getOtherValue($spremenljivka);

        $order = Model::generate_order_by_field($spremenljivka, get('usr_id'));

		$spremenljivkaParams = new enkaParameters($row['params']);
		$hotspot_image = ($spremenljivkaParams->get('hotspot_image') ? $spremenljivkaParams->get('hotspot_image') : "");
		$hotspot_region_visibility_option = ($spremenljivkaParams->get('hotspot_region_visibility_option') ? $spremenljivkaParams->get('hotspot_region_visibility_option') : 0);
		$hotspot_region_visibility = ($spremenljivkaParams->get('hotspot_region_visibility') ? $spremenljivkaParams->get('hotspot_region_visibility') : 1);	//za radio "vedno" in "ob mouseover"
		$hotspot_tooltips_option = ($spremenljivkaParams->get('hotspot_tooltips_option') ? $spremenljivkaParams->get('hotspot_tooltips_option') : 0);
		
		$hotspot_region_color = ($spremenljivkaParams->get('hotspot_region_color') ? $spremenljivkaParams->get('hotspot_region_color') : "");
		$hotspot_visibility_color = ($spremenljivkaParams->get('hotspot_visibility_color') ? $spremenljivkaParams->get('hotspot_visibility_color') : "");
		
		// Pri vpogledu moramo deaktivirati canvas in tipke (quick_edit & quick_view = 0)
		$quick_view = json_encode(get('quick_view'));
		
		if($hotspot_region_visibility_option){
			$hotspot_region_visibility_option = $hotspot_region_visibility;
		}
		
		//zaslon razdelimo na dva dela - izris leve strani***************************************
		echo '<div id="half_hot_spot_1" class="hotspot" style="width: 40%; float: left;">';

		
		//imena obmocij iz srv_vrednost
		$sql1 = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='$row[id]'  ORDER BY vrstni_red");
		//$sqlR = sisplet_query("SELECT * FROM srv_hotspot_regions WHERE spr_id='$row[id]' ");
		$sqlR = sisplet_query("SELECT region_coords, vre_id FROM srv_hotspot_regions WHERE spr_id='$row[id]' ");
		
		echo '<div id="hotspot_regions_hidden_menu_'.$row[id].'" style="display:none; ">';
		if (mysqli_num_rows($sql1) == 0){
			echo '        <div class="variabla">';
			echo '</div>';
		}
		else{
			while ($row1 = mysqli_fetch_array($sql1)) {
				
				echo '        <div class="variabla" id="variabla_'.$row1['id'].'">';
				echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'].'</div>';
				echo '</div>';
			}
		}			

		echo '</div>';

		//prikaz slike
		$findme = 'img';
		$pos = strpos($hotspot_image, $findme);
		if($pos === false) {	//string NOT present
			
		}
		else {	//string present
			$usemap = 'id="hotspot_'.$row['id'].'_image" usemap="#hotspot_'.$row['id'].'_usemap"';
			//v $hotspot_image je potrebno dodati usemap="#hotspot_image_'.$row['id'].'" za identificiranje mape
			$hotspot_image = substr_replace($hotspot_image, $usemap, 5, 0);	//dodaj zeleni string v $hotspot_image
		}
		
		echo '<div id="hotspot_image_'.$row['id'].'" class="vrednost_inline_hotspot"  contenteditable="false" spr_id="'.$row['id'].'">'.$hotspot_image.'';
		//prikaz slike - konec
		
		//prikaz obmocij na sliki*********************************************************************************
		$options[$row['id']] = '{areas: [';	//belezi kodo za settings-e za prikazovanje tooltip v imagemapster
		
		//HTML ZA TOOLTIP
		//$htmltootip[$row['id']] = '';
		// izracuni za sirino celic
		$size = $row['grids'];

		# polovimo vrednosti gridov, prevedemo naslove in hkrati preverimo ali imamo missinge
		$srv_grids = array();
		$mv_count = 0; # koliko je stolpcev z manjkajočimi vrednostmi
		# če polje other != 0 je grid kot missing
		//$sql_grid = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row[id]' ORDER BY vrstni_red");
		$sql_grid = sisplet_query("SELECT id, naslov, other FROM srv_grid WHERE spr_id='$row[id]' ORDER BY vrstni_red");

		while ($row_grid = mysqli_fetch_assoc($sql_grid)) {
			# priredimo naslov če prevajamo anketo
			$naslov = Language::srv_language_grid($row['id'], $row_grid['id']);
			if ($naslov != '') {
				$row_grid['naslov'] = $naslov;
			}
			$srv_grids[$row_grid['id']] = $row_grid;
			# če je označena kot manjkajoča jo prištejemo k manjkajočim
			if ($row_grid['other'] != 0) {
				$mv_count++;
			}

		}
		# Izpišemo TOP vrstico z labelami
		if (count($srv_grids) > 0) {
			$first_missing_value = true;
			foreach ($srv_grids AS $i => $srv_grid) {
				if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
					# dodamo spejs pred manjkajočimi vrednostmi
					echo '		<td></td>' . "\n";
					$first_missing_value = false;
				}

				// Datapiping
				$srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
			}
		}
		
		$orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

		# cache tabele srv_data_grid, da se ne dela vsakic posebej nov query (preberemo enkrat vse odgovore userja)
		$srv_data_grid = array();
		//$sql_grid = sisplet_query("SELECT * FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
		$sql_grid = sisplet_query("SELECT vre_id, grd_id FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
		while ($row_grid = mysqli_fetch_array($sql_grid)) {
			$srv_data_grid[$row_grid['vre_id']] = $row_grid;
		}

		# loop skozi srv_vrednost, da izpišemo vrstice z vrednostmi
		//$sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
		$sql1 = sisplet_query("SELECT id, naslov, hidden FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
		while ($row1 = mysqli_fetch_array($sql1)) {
			$htmltootip[$row1['id']] = '';
			$htmltootip1[$row1['id']] = '';
			$htmltootip1[$row1['id']] = $htmltootip1[$row1['id']].''.$row1['id'].'<br />';
			//echo $htmltootip1[$row1['id']];
			//$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'<div class=\"grid_table multigrid\">';
			# po potrebi prevedemo naslov
			$naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
			if ($naslov != '') {
				$row1['naslov'] = $naslov;
			}
			# preverimo izbrano vrednost
			$grid_id = $srv_data_grid[$row1['id']]['grd_id'];

			# ugotovimo ali je na katerem gridu predhodno izbran missing
			$is_missing = false;
			if (count($srv_grids) > 0) {
				foreach ($srv_grids AS $i => $srv_grid) {
					if ($srv_grid['other'] != 0 && $srv_grids[$i]['id'] == $grid_id) {
						$is_missing = true;
					}
				}
			}

			// Datapiping
			$row1['naslov'] = Helper::dataPiping($row1['naslov']);

			//echo '	<tr id="vrednost_if_' . $row1['id'] . '"' . (($row1['hidden'] == 1) ? 'style="display:none"' : '') . (($row1['hidden'] == 2) ? 'class="answer-disabled"' : '') . '">' . "\n";
			//$htmltootip[$row['id']] = $htmltootip[$row['id']].'	<tr id="vrednost_if_' . $row1['id'] . '"' . (($row1['hidden'] == 1) ? 'style="display:none"' : '') . (($row1['hidden'] == 2) ? 'class="answer-disabled"' : '') . '">' . "\n";
			$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'	<div id=\"im_vrednost_if_' . $row1['id'] . '\"' . (($row1['hidden'] == 1) ? 'style=\"display:none\"' : '') . (($row1['hidden'] == 2) ? 'class=\"answer-disabled\"' : '') . '\">';
			
			
			//echo '		<td class="question">';
			//$htmltootip[$row['id']] = $htmltootip[$row['id']].'		<td class="question">';
			$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'		<div class=\"question\">';
			
			//echo $row1['naslov'];
			$htmltootip[$row1['id']] = $htmltootip[$row1['id']].$row1['naslov'];
			
			//echo '</td>' . "\n";
			//$htmltootip[$row['id']] = $htmltootip[$row['id']].'</td>' . "\n";
			$htmltootip[$row['id']] = $htmltootip[$row['id']].'</div>';
			
			//echo '		<td></td>' . "\n";
			//$htmltootip[$row['id']] = $htmltootip[$row['id']].'		<td></td>' . "\n";
			$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'		<div></div>';

			$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'	</div>';
			
			//uredi options za imagemapster in prikazovanja obmocij
			//$options[$row['id']] = $options[$row['id']] .'{key: "'.$row1['naslov'].'", toolTip: "'.$htmltootip[$row['id']].' "} ,';
			
			//$bg++;
			$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'</div>';
			//$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'</table>' . "\n";
		}
		//echo '</table>' . "\n";
		//$htmltootip[$row['id']] = $htmltootip[$row['id']].'</table>' . "\n";
		
		//echo $htmltootip[$row1['id']];
		//HTML ZA TOOLTIP - KONEC
		echo '<map id="hotspot_'.$row['id'].'_map" name="hotspot_'.$row['id'].'_usemap">';
			while ($rowR = mysqli_fetch_array($sqlR)) {
				echo '<area coords="'.$rowR['region_coords'].'" name="'.$rowR['vre_id'].'" shape="poly" href="#">';
				//uredi options za imagemapster in prikazovanja obmocij
				$options[$row['id']] = $options[$row['id']] .'{key: "'.$rowR['vre_id'].'", toolTip: "'.$htmltootip[$rowR['vre_id']].' "} ,';
			}
		echo '</map>';

		$options[$row['id']] = rtrim($options[$row['id']], ",");	//odstrani zadnjo vejico pri options
		$options[$row['id']] = $options[$row['id']].']}';	//zapri areas z ]}
		//echo $options[$row['id']];
		
		//************************************************************************************************	
		//izris radio button in checkbox, ki bo skrita in beležila odgovore na sliki
		RadioCheckboxSelect::getInstance()->display($spremenljivka);	//prikaze radio button in checkbox z odgovori									
		//izris radio button in checkbox, ki bo skrita in beležila odgovore na sliki - konec
		//***********************************************************************************


		?>
		
		<script>						
			$(document).ready(function () {
				mapinitRadio(<?=$row['id']?>, <?=$options[$row['id']]?>, <?=$row['tip']?>, <?=$hotspot_region_visibility_option?>, <?=$hotspot_tooltips_option?>, '<?=$hotspot_region_color?>', '<?=$hotspot_visibility_color?>', <?=$quick_view?>);	//uredi delovanje imagemapster in prikazovanja/skrivanje obmocij ter tooltip-ov							
			});
		</script>
		<?
		echo '</div>';
		//prikaz obmocij na sliki - konec**************************************************************************


		echo '      </div>';
		//************************* Izris leve strani - konec		
	}

	/**
	* @desc prikaze vnos za image hotspot za radio grid
	*/
	public function grid($spremenljivka){
		
		$row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";
		$hideName = ((get('loop_id') != null) && ($_GET['m'] == 'quick_edit')) ? true : false;
		
        $spremenljivkaParams = new enkaParameters($row['params']);
        $selected = Model::getOtherValue($spremenljivka);

        $order = Model::generate_order_by_field($spremenljivka, get('usr_id'));

		$spremenljivkaParams = new enkaParameters($row['params']);
		$hotspot_image = ($spremenljivkaParams->get('hotspot_image') ? $spremenljivkaParams->get('hotspot_image') : "");
		$hotspot_region_visibility_option = ($spremenljivkaParams->get('hotspot_region_visibility_option') ? $spremenljivkaParams->get('hotspot_region_visibility_option') : 0);	//za checkbox
		$hotspot_region_visibility = ($spremenljivkaParams->get('hotspot_region_visibility') ? $spremenljivkaParams->get('hotspot_region_visibility') : 1);	//za radio "vedno" in "ob mouseover"
		$hotspot_tooltips_option = ($spremenljivkaParams->get('hotspot_tooltips_option') ? $spremenljivkaParams->get('hotspot_tooltips_option') : 0);
		$hotspot_region_color = ($spremenljivkaParams->get('hotspot_region_color') ? $spremenljivkaParams->get('hotspot_region_color') : "");
		$hotspot_visibility_color = ($spremenljivkaParams->get('hotspot_visibility_color') ? $spremenljivkaParams->get('hotspot_visibility_color') : "");
		
		if($hotspot_region_visibility_option){
			$hotspot_region_visibility_option = $hotspot_region_visibility;
		}
		
		// Pri vpogledu moramo deaktivirati radio button-e (quick_edit & quick_view = 0)
		$quick_view = json_encode(get('quick_view'));
		
		
		
		//zaslon razdelimo na dva dela - izris leve strani***************************************
		echo '<div id="half_hot_spot_1" class="hotspot" style="width: 40%; float: left;">';
						
		//imena obmocij iz srv_vrednost
		$sql1 = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='$row[id]'  ORDER BY vrstni_red");
		$sqlR = sisplet_query("SELECT region_coords, vre_id FROM srv_hotspot_regions WHERE spr_id='$row[id]' ");				
		
		echo '<div id="hotspot_regions_hidden_menu_'.$row['id'].'" style="display:none; ">';
		
		if (mysqli_num_rows($sql1) == 0){
			echo '        <div class="variabla">';
			echo '</div>';
		}
		else{
			while ($row1 = mysqli_fetch_array($sql1)) {
				
				echo '        <div class="variabla" id="variabla_'.$row1['id'].'">';
				echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'].'</div>';
				echo '</div>';
			}
		}			

		echo '</div>';

		//prikaz slike
		$findme = 'img';
		$pos = strpos($hotspot_image, $findme);
		if($pos === false) {	//string NOT present
			
		}
		else {	//string present
			$usemap = 'id="hotspot_'.$row['id'].'_image" usemap="#hotspot_'.$row['id'].'_usemap"';
			//v $hotspot_image je potrebno dodati usemap="#hotspot_image_'.$row['id'].'" za identificiranje mape
			$hotspot_image = substr_replace($hotspot_image, $usemap, 5, 0);	//dodaj zeleni string v $hotspot_image
		}
		
		echo '<div id="hotspot_image_'.$row['id'].'" class="vrednost_inline_hotspot"  contenteditable="false" spr_id="'.$row['id'].'">'.$hotspot_image.'';
		//prikaz slike - konec
		
		//prikaz obmocij na sliki*********************************************************************************
		$options[$row['id']] = '{areas: [';	//belezi kodo za settings-e za prikazovanje tooltip v imagemapster
		
		//HTML ZA TOOLTIP
		//$htmltootip[$row['id']] = '';
		// izracuni za sirino celic
		$size = $row['grids'];

		# polovimo vrednosti gridov, prevedemo naslove in hkrati preverimo ali imamo missinge
		$srv_grids = array();
		$mv_count = 0; # koliko je stolpcev z manjkajočimi vrednostmi
		# če polje other != 0 je grid kot missing
		//$sql_grid = sisplet_query("SELECT * FROM srv_grid WHERE spr_id='$row[id]' ORDER BY vrstni_red");
		$sql_grid = sisplet_query("SELECT id, naslov, other FROM srv_grid WHERE spr_id='$row[id]' ORDER BY vrstni_red");

		while ($row_grid = mysqli_fetch_assoc($sql_grid)) {
			# priredimo naslov če prevajamo anketo
			$naslov = Language::srv_language_grid($row['id'], $row_grid['id']);
			if ($naslov != '') {
				$row_grid['naslov'] = $naslov;
			}
			$srv_grids[$row_grid['id']] = $row_grid;
			# če je označena kot manjkajoča jo prištejemo k manjkajočim
			if ($row_grid['other'] != 0) {
				$mv_count++;
			}

		}
		# Izpišemo TOP vrstico z labelami
		if (count($srv_grids) > 0) {
			$first_missing_value = true;
			foreach ($srv_grids AS $i => $srv_grid) {
				if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
					# dodamo spejs pred manjkajočimi vrednostmi
					echo '		<td></td>' . "\n";
					$first_missing_value = false;
				}

				// Datapiping
				$srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
			}
		}
		
		//echo '<table class="grid_table multigrid">' . "\n";
		//$htmltootip[$row['id']] = $htmltootip[$row['id']].'<table class=\"grid_table multigrid\">';
		
		$orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

		# cache tabele srv_data_grid, da se ne dela vsakic posebej nov query (preberemo enkrat vse odgovore userja)
		$srv_data_grid = array();
		//$sql_grid = sisplet_query("SELECT * FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
		$sql_grid = sisplet_query("SELECT vre_id FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
		while ($row_grid = mysqli_fetch_array($sql_grid)) {
			$srv_data_grid[$row_grid['vre_id']] = $row_grid;
		}

		# loop skozi srv_vrednost, da izpišemo vrstice z vrednostmi
		//$sql1 = sisplet_query("SELECT * FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
		$sql1 = sisplet_query("SELECT id, naslov, hidden FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
		while ($row1 = mysqli_fetch_array($sql1)) {
			$htmltootip[$row1['id']] = '';
			$htmltootip1[$row1['id']] = '';
			$htmltootip1[$row1['id']] = $htmltootip1[$row1['id']].''.$row1['id'].'<br />';

			// po potrebi prevedemo naslov
			$naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
			if ($naslov != '') {
				$row1['naslov'] = $naslov;
			}
			// preverimo izbrano vrednost
			$grid_id = $srv_data_grid[$row1['id']]['grd_id'];

			// ugotovimo ali je na katerem gridu predhodno izbran missing
			$is_missing = false;
			if (count($srv_grids) > 0) {
				foreach ($srv_grids AS $i => $srv_grid) {
					if ($srv_grid['other'] != 0 && $srv_grids[$i]['id'] == $grid_id) {
						$is_missing = true;
					}
				}
			}

			// Datapiping
			$row1['naslov'] = Helper::dataPiping($row1['naslov']);

			//echo '	<tr id="vrednost_if_' . $row1['id'] . '"' . (($row1['hidden'] == 1) ? 'style="display:none"' : '') . (($row1['hidden'] == 2) ? 'class="answer-disabled"' : '') . '">' . "\n";
			//$htmltootip[$row['id']] = $htmltootip[$row['id']].'	<tr id="vrednost_if_' . $row1['id'] . '"' . (($row1['hidden'] == 1) ? 'style="display:none"' : '') . (($row1['hidden'] == 2) ? 'class="answer-disabled"' : '') . '">' . "\n";
			$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'	<div id=\"im_vrednost_if_' . $row1['id'] . '\"' . (($row1['hidden'] == 1) ? 'style=\"display:none\"' : '') . (($row1['hidden'] == 2) ? 'class=\"answer-disabled\"' : '') . '\">';
			
			
			//echo '		<td class="question">';
			//$htmltootip[$row['id']] = $htmltootip[$row['id']].'		<td class="question">';
			$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'		<div class=\"question\">';
			
			//echo $row1['naslov'];
			$htmltootip[$row1['id']] = $htmltootip[$row1['id']].$row1['naslov'];

			
			//echo '</td>' . "\n";
			//$htmltootip[$row['id']] = $htmltootip[$row['id']].'</td>' . "\n";
			$htmltootip[$row['id']] = $htmltootip[$row['id']].'</div>';
			
			//echo '		<td></td>' . "\n";
			//$htmltootip[$row['id']] = $htmltootip[$row['id']].'		<td></td>' . "\n";
			$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'		<div></div>';


			if (count($srv_grids) > 0) {
				$first_missing_value = true;
				foreach ($srv_grids AS $i => $srv_grid) {
					if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
						# dodamo spejs pred manjkajočimi vrednostmi
						
						//echo '		<td></td>' . "\n";
						//$htmltootip[$row['id']] = $htmltootip[$row['id']].'		<td></td>' . "\n";
						$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'		<div></div>';
						
						$first_missing_value = false;
					}

					$value = $srv_grids[$i]['id'];

					# izpišemo radio grida
					if ($srv_grid['other'] != 0) {
						$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'<div class=\"missing ' . $cssAlign . '\">';
						# imamo missing vrednost
						$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'<label for=\"im_grid_missing_value_' . $row1['id'] . '_grid_' . $value . '\">';
						
						//$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'<input type=\"radio\" ' . (!$hideName ? ' name=\"vrednost_' . $row1['id'] . '\"' : '') . ' id=\"im_grid_missing_value_' . $row1['id'] . '_grid_' . $value . '\" value=\"' . $value . '\"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation=\"0\" onclick=\"checkChecked(this); checkTableMissing(this); checkBranching(); setCheckedClass(this, null, ' . $row1['id'] . '); mapdelovanjeRadioGrid(this, ' . $row1['id'] . ');\">';							
						$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'<input type=\"radio\" ' . (!$hideName ? ' name=\"vrednost_' . $row1['id'] . '\"' : '') . ' id=\"im_grid_missing_value_' . $row1['id'] . '_grid_' . $value . '\" value=\"' . $value . '\"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation=\"0\" onclick=\"checkChecked(this); checkTableMissing(this); if (typeof checkBranching == \'function\'){checkBranching();} setCheckedClass(this, null, ' . $row1['id'] . '); mapdelovanjeRadioGrid(this, ' . $row1['id'] . ');\">';							
						
						//$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') .'"'.
						$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'<span class=\"enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') .'\"'.
																			((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
																			'></span>';
						$htmltootip[$row1['id']] = $htmltootip[$row1['id']].$srv_grid['naslov'];
						$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'</label>';
						$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'</div>';
					} else {
						$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'<div class=\"category ' . $cssAlign . '\">';
						# ni missing vrednost
						$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'<label for=\"im_vrednost_' . $row1['id'] . '_grid_' . $value . '\">';
						$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'<input type=\"radio\" ' . (!$hideName ? ' name=\"vrednost_' . $row1['id'] . '\"' : '') . ' id=\"im_vrednost_' . $row1['id'] . '_grid_' . $value . '\" value=\"' . $value . '\"' . (($grid_id == $value && $grid_id != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation=\"' . $srv_grids[$i]['variable'] . '\" onclick=\"checkChecked(this); if (typeof checkBranching == \'function\'){checkBranching();} setCheckedClass(this, null, ' . $row1['id'] . '); mapdelovanjeRadioGrid(this, ' . $row1['id'] . ');\">';
						//$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'<span class="enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') .'"'.
						$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'<span class=\"enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') .'\"'.
																			((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
																			'></span>';
						$htmltootip[$row1['id']] = $htmltootip[$row1['id']].$srv_grid['naslov'];
						$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'</label>';
						$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'</div>';									
					}
				}
			}
			$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'	</div>';
			
			
			//uredi options za imagemapster in prikazovanja obmocij
			//$options[$row['id']] = $options[$row['id']] .'{key: "'.$row1['naslov'].'", toolTip: "'.$htmltootip[$row['id']].' "} ,';
			
			//$bg++;
			$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'</div>';
			//$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'</table>' . "\n";
		}
		//HTML ZA TOOLTIP - KONEC
		
		echo '<map id="hotspot_'.$row['id'].'_map" name="hotspot_'.$row['id'].'_usemap">';
			while ($rowR = mysqli_fetch_array($sqlR)) {
				echo '<area coords="'.$rowR['region_coords'].'" name="'.$rowR['vre_id'].'" shape="poly" href="#">';
				//uredi options za imagemapster in prikazovanja obmocij
				$options[$row['id']] = $options[$row['id']] .'{key: "'.$rowR['vre_id'].'", toolTip: "'.$htmltootip[$rowR['vre_id']].' "} ,';
			}
		echo '</map>';

		$options[$row['id']] = rtrim($options[$row['id']], ",");	//odstrani zadnjo vejico pri options
		$options[$row['id']] = $options[$row['id']].']}';	//zapri areas z ]}
		//echo $options[$row['id']];
		
		//************************************************************************************************	
		//izris tabele z radio button, ki bo skrita in beležila odgovore na sliki
		Multigrid::getInstance()->display($spremenljivka);	//prikaze tabelo z radio button odgovori
	
		//izris tabele z radio button, ki bo skrita in beležila odgovore na sliki - konec
		//***********************************************************************************
		
		?>

		<script>						
			$(document).ready(function () {
				mapinitRadioGrid(<?=$row['id']?>, <?=$options[$row['id']]?>, <?=$hotspot_region_visibility_option?>, <?=$hotspot_tooltips_option?>, '<?=$hotspot_region_color?>', '<?=$hotspot_visibility_color?>', <?=$quick_view?>);	//uredi delovanje imagemapster in prikazovanja obmocij ter tooltip-ov							
			});
		</script>
		<?
		echo '</div>';
		//prikaz obmocij na sliki - konec**************************************************************************


		echo '      </div>';
		
		//************************* Izris leve strani - konec		
	}
	
	public function ranking($spremenljivka, $oblika){
		
		$row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";
		$hideName = ((get('loop_id') != null) && ($_GET['m'] == 'quick_edit')) ? true : false;
		
        $spremenljivkaParams = new enkaParameters($row['params']);
        $selected = Model::getOtherValue($spremenljivka);

        $order = Model::generate_order_by_field($spremenljivka, get('usr_id'));
		
		$spremenljivkaParams = new enkaParameters($row['params']);
		$hotspot_image = ($spremenljivkaParams->get('hotspot_image') ? $spremenljivkaParams->get('hotspot_image') : "");
		$hotspot_region_visibility_option = ($spremenljivkaParams->get('hotspot_region_visibility_option') ? $spremenljivkaParams->get('hotspot_region_visibility_option') : 0);	//za checkbox
		$hotspot_region_visibility = ($spremenljivkaParams->get('hotspot_region_visibility') ? $spremenljivkaParams->get('hotspot_region_visibility') : 1);	//za radio "vedno" in "ob mouseover"
		$hotspot_tooltips_option = ($spremenljivkaParams->get('hotspot_tooltips_option') ? $spremenljivkaParams->get('hotspot_tooltips_option') : 0);
		$hotspot_region_color = ($spremenljivkaParams->get('hotspot_region_color') ? $spremenljivkaParams->get('hotspot_region_color') : "");
		$hotspot_visibility_color = ($spremenljivkaParams->get('hotspot_visibility_color') ? $spremenljivkaParams->get('hotspot_visibility_color') : "");
		
		if($hotspot_region_visibility_option){
			$hotspot_region_visibility_option = $hotspot_region_visibility;
		}
		
		// Pri vpogledu moramo deaktivirati radio button-e (quick_edit & quick_view = 0)
		$quick_view = json_encode(get('quick_view'));
		
		
		//zaslon razdelimo na dva dela - izris leve strani***************************************
		echo '<div id="half_hot_spot_1" class="hotspot" style="width: 40%; float: left;">';
	
		//imena obmocij iz srv_vrednost
		$sql1 = sisplet_query("SELECT id, naslov, vrstni_red FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");
		$sqlR = sisplet_query("SELECT region_coords, vre_id FROM srv_hotspot_regions WHERE spr_id='$row[id]' ");				
		
		echo '<div id="hotspot_regions_hidden_menu_'.$row[id].'" style="display:none; ">';

		// Ali gre za sazu anketo
		if(SurveyInfo::getInstance()->checkSurveyModule('sazu'))
			echo '<input type="hidden" id="hotspot_image_'.$row[id].'_sazu" value="1">';
		
		if (mysqli_num_rows($sql1) == 0){
			echo '        <div class="variabla">';
			echo '</div>';
		}
		else{
			while ($row1 = mysqli_fetch_array($sql1)) {		
				echo '        <div class="variabla" id="variabla_'.$row1['id'].'">';
				echo '<div id="vre_id_'.$row1['id'].'" class="vrednost_inline" contenteditable="'.(!$locked?'true':'false').'" tabindex="1" vre_id="'.$row1['id'].'" '.(strpos($row1['naslov'], $lang['srv_new_vrednost'])!==false || strpos($row1['naslov'], $lang1['srv_new_vrednost'])!==false || $this->lang_id!=null ? ' default="1"':'').'>' . $row1['naslov'].'</div>';
				echo '</div>';
			}
		}			

		echo '</div>';

		//prikaz slike
		$findme = 'img';
		$pos = strpos($hotspot_image, $findme);
		if($pos === false) {	//string NOT present
			
		}
		else {	//string present
			$usemap = 'id="hotspot_'.$row['id'].'_image" usemap="#hotspot_'.$row['id'].'_usemap"';
			//v $hotspot_image je potrebno dodati usemap="#hotspot_image_'.$row['id'].'" za identificiranje mape
			$hotspot_image = substr_replace($hotspot_image, $usemap, 5, 0);	//dodaj zeleni string v $hotspot_image
		}
		
		echo '<div id="hotspot_image_'.$row['id'].'" class="vrednost_inline_hotspot"  contenteditable="false" spr_id="'.$row['id'].'">'.$hotspot_image.'';
		//prikaz slike - konec
		
		//prikaz obmocij na sliki*********************************************************************************
		$options[$row['id']] = '{areas: [';	//belezi kodo za settings-e za prikazovanje tooltip v imagemapster
		
		//HTML ZA TOOLTIP
		// izracuni za sirino celic
		$size = $row['grids'];

		# polovimo vrednosti gridov, prevedemo naslove in hkrati preverimo ali imamo missinge
		$srv_grids = array();
		$mv_count = 0; # koliko je stolpcev z manjkajočimi vrednostmi
		# če polje other != 0 je grid kot missing
		$sql_grid = sisplet_query("SELECT * FROM srv_hotspot_regions WHERE spr_id='$row[id]' ORDER BY vrstni_red");

		while ($row_grid = mysqli_fetch_assoc($sql_grid)) {
			# priredimo naslov če prevajamo anketo
			$naslov = Language::srv_language_grid($row['id'], $row_grid['id']);
			if ($naslov != '') {
				//$row_grid['naslov'] = $naslov;
				$row_grid['vrstni_red'] = $naslov;
			}
			$srv_grids[$row_grid['id']] = $row_grid;
			# če je označena kot manjkajoča jo prištejemo k manjkajočim
			if ($row_grid['other'] != 0) {
				$mv_count++;
			}

		}
		# Izpišemo TOP vrstico z labelami
		if (count($srv_grids) > 0) {
			$first_missing_value = true;
			foreach ($srv_grids AS $i => $srv_grid) {
				if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {
					# dodamo spejs pred manjkajočimi vrednostmi
					echo '		<td></td>' . "\n";
					$first_missing_value = false;
				}

				// Datapiping
				$srv_grid['naslov'] = Helper::dataPiping($srv_grid['naslov']);
			}
		}
					
		$orderby = Model::generate_order_by_field($spremenljivka, get('usr_id'));

		# cache tabele srv_data_grid, da se ne dela vsakic posebej nov query (preberemo enkrat vse odgovore userja)
		$srv_data_grid = array();
		$sql_grid = sisplet_query("SELECT vre_id FROM srv_data_grid" . get('db_table') . " WHERE spr_id='$row[id]' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
		while ($row_grid = mysqli_fetch_array($sql_grid)) {
			$srv_data_grid[$row_grid['vre_id']] = $row_grid;
		}

		# loop skozi srv_vrednost, da izpišemo vrstice z vrednostmi
		$sql1 = sisplet_query("SELECT id, naslov, hidden, vrstni_red FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY FIELD(vrstni_red, $orderby)");
		while ($row1 = mysqli_fetch_array($sql1)) {
			$htmltootip[$row1['id']] = '';
			$htmltootip1[$row1['id']] = '';
			$htmltootip1[$row1['id']] = $htmltootip1[$row1['id']].''.$row1['id'].'<br />';

			# po potrebi prevedemo naslov
			$naslov = Language::getInstance()->srv_language_vrednost($row1['id']);
			if ($naslov != '') {
				$row1['naslov'] = $naslov;
			}
			# preverimo izbrano vrednost
			$grid_id = $srv_data_grid[$row1['id']]['grd_id'];

			# ugotovimo ali je na katerem gridu predhodno izbran missing
			$is_missing = false;
			if (count($srv_grids) > 0) {
				foreach ($srv_grids AS $i => $srv_grid) {
					if ($srv_grid['other'] != 0 && $srv_grids[$i]['id'] == $grid_id) {
						$is_missing = true;
					}
				}
			}

			// Datapiping
			$row1['naslov'] = Helper::dataPiping($row1['naslov']);

			$htmltootip[$row1['id']] .= '<div id=\"im_vrednost_if_' . $row1['id'] . '\"' . (($row1['hidden'] == 1) ? 'style=\"display:none\"' : '') . (($row1['hidden'] == 2) ? 'class=\"answer-disabled\"' : '') . '\">';						
			
			$htmltootip[$row1['id']] .= '<div class=\"question\">';

			if(SurveyInfo::getInstance()->checkSurveyModule('sazu'))
				$htmltootip[$row1['id']] .= '<div class=\"title\">Razvrsti območje od 1 do 6 glede na privlačnost:</div>';
			else
				$htmltootip[$row1['id']] .= '<div class=\"title\">'.$row1['naslov'].'</div>';
			

			if (count($srv_grids) > 0) {
				$first_missing_value = true;

				foreach ($srv_grids AS $i => $srv_grid) {
					
					if ((string)$srv_grid['other'] != '0' && $first_missing_value == true) {					
						$first_missing_value = false;
					}

					$value = $srv_grids[$i]['id'];

					# izpišemo radio grida
					if ($srv_grid['other'] != 0) {
						$htmltootip[$row1['id']] .= '<div class=\"missing ' . $cssAlign . '\">';
						# imamo missing vrednost
						$htmltootip[$row1['id']] .= '<label for=\"im_grid_missing_value_' . $row1['id'] . '_grid_' . $value . '\">';
						
						$htmltootip[$row1['id']] .= '<input type=\"radio\" ' . (!$hideName ? ' name=\"vrednost_' . $row1['id'] . '\"' : '') . ' id=\"im_grid_missing_value_' . $row1['id'] . '_grid_' . $value . '\" value=\"' . $value . '\"' . (($grid_id == $value && $grid_id != '') ? ' checked' : '') . ' data-calculation=\"0\" onclick=\"checkChecked(this); checkTableMissing(this); if (typeof checkBranching == \'function\'){checkBranching();} setCheckedClass(this, null, ' . $row1['id'] . '); mapdelovanjeRadioGrid(this, ' . $row1['id'] . ');\">';							
						
						$htmltootip[$row1['id']] .= '<span class=\"enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') .'\"'.
																			((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
																			'></span>';
						$htmltootip[$row1['id']] .= $srv_grid['naslov'];
						$htmltootip[$row1['id']] .= '</label>';
						$htmltootip[$row1['id']] .= '</div>';
					} 
					else {
						$htmltootip[$row1['id']] .= '<div class=\"category ' . $cssAlign . '\">';
						
						
						// Za SAZU moramo posebej obarvati
						if(SurveyInfo::getInstance()->checkSurveyModule('sazu')){
							# ni missing vrednost
							$htmltootip[$row1['id']] .= '<label for=\"im_vrednost_' . $row1['id'] . '_grid_' . $value . '\">';
							
							$htmltootip[$row1['id']] .= '<input vrstni_red='.$srv_grid['vrstni_red'].' type=\"radio\" ' . (!$hideName ? ' name=\"vrednost_' . $row1['id'] . '\"' : '') . ' id=\"im_vrednost_' . $row1['id'] . '_grid_' . $value . '\" value=\"' . $value . '\"' . (($grid_id == $value && $grid_id != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation=\"' . $srv_grids[$i]['variable'] . '\" onclick=\"checkChecked(this); if (typeof checkBranching == \'function\'){checkBranching();} setCheckedClass(this, null, ' . $row1['id'] . '); mapdelovanjeRankingSazu(this, ' . $row1['id'] . ');\">';
							$htmltootip[$row1['id']] .= '<span class=\"enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') .'\"'.
																				((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
																				'></span>';
							//besedilo ob radio buttonu
							$htmltootip[$row1['id']] .= $srv_grid['vrstni_red'];

							//besedilo ob radio buttonu - konec
							$htmltootip[$row1['id']] .= '</label>';
						}
						else{
							# ni missing vrednost
							$htmltootip[$row1['id']] .= '<label for=\"im_vrednost_' . $row1['id'] . '_grid_' . $value . '\">';

							$htmltootip[$row1['id']] .= '<input vrstni_red='.$srv_grid['vrstni_red'].' type=\"radio\" ' . (!$hideName ? ' name=\"vrednost_' . $row1['id'] . '\"' : '') . ' id=\"im_vrednost_' . $row1['id'] . '_grid_' . $value . '\" value=\"' . $value . '\"' . (($grid_id == $value && $grid_id != '' && !$is_missing) ? ' checked' : '') . ($is_missing ? ' disabled' : '') . ' data-calculation=\"' . $srv_grids[$i]['variable'] . '\" onclick=\"checkChecked(this); if (typeof checkBranching == \'function\'){checkBranching();} setCheckedClass(this, null, ' . $row1['id'] . '); mapdelovanjeRanking(this, ' . $row1['id'] . ');\">';
							$htmltootip[$row1['id']] .= '<span class=\"enka-checkbox-radio ' . (($row1['hidden'] == 2) ? ' answer-disabled' : '') .'\"'.
																				((Helper::getCustomCheckbox() != 0) ? (' style="font-size:' . Helper::getCustomCheckbox().'px;"') : '').
																				'></span>';
							//besedilo ob radio buttonu
							$htmltootip[$row1['id']] .= $srv_grid['vrstni_red'];
							//besedilo ob radio buttonu - konec
							$htmltootip[$row1['id']] .= '</label>';
						}
						$htmltootip[$row1['id']] .= '</div>';									
					}
				}
			}
			
			$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'	</div>';	//konec elementa question
			

			//KOMENTAR v popupu******************************************************************************************************
			if(SurveyInfo::getInstance()->checkSurveyModule('sazu')){

				$sqlComment = sisplet_query("SELECT id, naslov, vrstni_red FROM srv_vrednost WHERE spr_id='$row[id]' ORDER BY vrstni_red");

				// Najdemo naslednje textgrid vprasanje na isti strani, kamor zapisemo komentarje
				$sqlComment = sisplet_query("SELECT v1.id, v1.naslov, v1.vrstni_red, s1.id as spr_id
												FROM srv_vrednost v1, srv_spremenljivka s1 
												WHERE s1.gru_id='".$row[gru_id]."' AND s1.variable='regcom' 
													AND v1.spr_id=s1.id AND v1.vrstni_red='".$row1[vrstni_red]."'
											");
				$rowComment = mysqli_fetch_array($sqlComment);

				// Pridobimo ze mogoce vnesen text
				$sqlCommentData = sisplet_query("SELECT text FROM srv_data_textgrid".get('db_table')." 
													WHERE spr_id='".$rowComment['spr_id']."' AND usr_id='".get('usr_id')."' AND vre_id='".$rowComment['id']."'
												");
				$rowCommentData = mysqli_fetch_array($sqlCommentData);

				// Izrisemo input box, ki je sinhroniziran z inputi naslednjega skritega textgrid vprasanja
				$textarea_id = 'vrednost_'.$rowComment['id'].'_grid_1';
				
				$komentar = '<textarea onkeyup=\"mapdelovanjeRankingKomentar(\''.$textarea_id.'\', this.value);\" id=\"im_vrednost_komentar_' . $row1['id'] . '\"  name=\"vrednost_komentar_' . $row1['id'] . '\" >';
				$komentar .= $rowCommentData['text'];
				$komentar .= '</textarea>';
				
				$htmltootip[$row1['id']] .= ' <div class=\"komentar\">Opišite s ključno besedo:'.$komentar.'</div>';
			}
			//KOMENTAR - KONEC **********************************************************************************************
	

			$htmltootip[$row1['id']] .= '<span class=\"close_button\" onClick=\"removeMapsterTooltip();\" style=\"margin:\">Zapri</span>';
			
			//uredi options za imagemapster in prikazovanja obmocij
			$htmltootip[$row1['id']] = $htmltootip[$row1['id']].'</div>';	//konec elementa z radio buttoni
		}
		
		//echo $htmltootip[$row1['id']];
		//HTML ZA TOOLTIP - KONEC
		
		echo '<map id="hotspot_'.$row['id'].'_map" name="hotspot_'.$row['id'].'_usemap">';
			while ($rowR = mysqli_fetch_array($sqlR)) {
				echo '<area coords="'.$rowR['region_coords'].'" name="'.$rowR['vre_id'].'" shape="poly" href="#">';
				//uredi options za imagemapster in prikazovanja obmocij
				$options[$row['id']] = $options[$row['id']] .'{key: "'.$rowR['vre_id'].'", toolTip: "'.$htmltootip[$rowR['vre_id']].' "} ,';
			}
		echo '</map>';

		$options[$row['id']] = rtrim($options[$row['id']], ",");	//odstrani zadnjo vejico pri options
		$options[$row['id']] = $options[$row['id']].']}';	//zapri areas z ]}
		//echo $options[$row['id']];

		//************************************************************************************************	
		//izris ranking z oštevilčevanjem, ki bo skrita in beležila odgovore na sliki
		Ranking::getInstance()->display($spremenljivka, $oblika);	//prikaze tabelo z radio button odgovori				
		//izris ranking z oštevilčevanjem, ki bo skrita in beležila odgovore na sliki - konec
		//***********************************************************************************
		

		?>
		<script>						
			$(document).ready(function () {

				//uredi delovanje imagemapster in prikazovanja obmocij ter tooltip-ov
				mapinitRanking(<?=$row['id']?>, <?=$options[$row['id']]?>, <?=$hotspot_region_visibility_option?>, <?=$hotspot_tooltips_option?>, '<?=$hotspot_region_color?>', '<?=$hotspot_visibility_color?>', <?=$quick_view?>);								

				// Remove tooltip if clicked outside
				$(window).click(function(e) {
					if(e.target.class != "hotspot" 
						&& e.target.class != "mapster_tooltip" 
						&& !$(".hotspot").has(e.target).length
						&& !$(".mapster_tooltip").has(e.target).length){
						removeMapsterTooltip();
					}
				});
			});
		</script>
		<?
		echo '</div>';
		//prikaz obmocij na sliki - konec**************************************************************************


		echo '      </div>';
		
		//************************* Izris leve strani - konec		
	}

}