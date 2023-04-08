<?php
/***************************************
 * Description: Prikaže vprašanje Heatmap
 *
 * Vprašanje je prisotno:
 * tip 27
 *
 * Autor: Patrik Pucer
 * Created date: 06.10.2016
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

// Iz admin/survey


// Vprašanja

class HeatMapController extends Controller
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

        return new HeatMapController();
    }
	
	 /**
     * @desc prikaze vnos za image hotspot za radio
     */
	public function display($spremenljivka){

		global $lang;
		$row = Model::select_from_srv_spremenljivka($spremenljivka);

        $loop_id = get('loop_id') == null ? " IS NULL" : " = '" . get('loop_id') . "'";

        $spremenljivkaParams = new enkaParameters($row['params']);
        $selected = Model::getOtherValue($spremenljivka);

        $order = Model::generate_order_by_field($spremenljivka, get('usr_id'));

		
		
		$spremenljivkaParams = new enkaParameters($row['params']);
		$hotspot_image = ($spremenljivkaParams->get('hotspot_image') ? $spremenljivkaParams->get('hotspot_image') : "");
		$hotspot_region_visibility_option = ($spremenljivkaParams->get('hotspot_region_visibility_option') ? $spremenljivkaParams->get('hotspot_region_visibility_option') : 0);
		
		//$hotspot_tooltips_option = ($spremenljivkaParams->get('hotspot_tooltips_option') ? $spremenljivkaParams->get('hotspot_tooltips_option') : 1);	//po default-u skrij namig
		$hotspot_tooltips_option = 1;	//naj bo 1 tako, da je skrito, saj trenutno tega v heatmap ne rabimo
		
		$hotspot_region_color = ($spremenljivkaParams->get('hotspot_region_color') ? $spremenljivkaParams->get('hotspot_region_color') : "");
		
		$heatmap_num_clicks = ($spremenljivkaParams->get('heatmap_num_clicks') ? $spremenljivkaParams->get('heatmap_num_clicks') : 1);
		$heatmap_show_clicks = ($spremenljivkaParams->get('heatmap_show_clicks') ? $spremenljivkaParams->get('heatmap_show_clicks') : 0); //za checkbox
		$disable_heatmap_show_clicks_hidden = ($heatmap_show_clicks == 1) ? 'disabled' : '';
		
		$heatmap_click_color = ($spremenljivkaParams->get('heatmap_click_color') ? $spremenljivkaParams->get('heatmap_click_color') : "");
		$heatmap_click_size = ($spremenljivkaParams->get('heatmap_click_size') ? $spremenljivkaParams->get('heatmap_click_size') : 5);
		$heatmap_click_shape = ($spremenljivkaParams->get('heatmap_click_shape') ? $spremenljivkaParams->get('heatmap_click_shape') : 1);
		
		$heatmap_show_counter_clicks = ($spremenljivkaParams->get('heatmap_show_counter_clicks') ? $spremenljivkaParams->get('heatmap_show_counter_clicks') : 0); //za prikazovanje/skrivanje stevca klikov
		
		// Pri vpogledu moramo deaktivirati canvas in tipke (quick_edit & quick_view = 0)
		$quick_view = json_encode(get('quick_view'));

		

		
		echo '<div id="heatmap_'.$spremenljivka.'" class="hotspot" style="width: 40%; float: left;">';
			//imena obmocij iz srv_vrednost
			$sql1 = sisplet_query("SELECT id, naslov FROM srv_vrednost WHERE spr_id='$row[id]'  ORDER BY vrstni_red");
			$sqlR = sisplet_query("SELECT region_coords, vre_id FROM srv_hotspot_regions WHERE spr_id='$row[id]' ");
			
			//ce so podatki ze v bazi (rec. uporabnik klikne 'Prejsnja stran')
			//$sql1a = sisplet_query("SELECT lat, lng, address, text FROM srv_data_map WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
			$sql1a = sisplet_query("SELECT lat, lng, address, text FROM srv_data_heatmap WHERE spr_id='$spremenljivka' AND usr_id='" . get('usr_id') . "' AND loop_id $loop_id");
			$heatmap_data = array();
			while ($row1a = mysqli_fetch_array($sql1a)) {
				$heatmap_data[] = $row1a;
			}
			
			echo '<div id="hotspot_regions_hidden_menu_'.$row[id].'" style="display:none; ">';
			//echo '<div id="hotspot_regions_hidden_menu_'.$row[id].'">';
				//echo '        <div class="variabla" id="variabla_'.$row1['id'].'">';
				if (mysqli_num_rows($sql1) == 0){
					echo '        <div class="variabla">';
					echo '</div>';
				}else{
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
			
			//canvas
			echo '<canvas id="heatmapCanvas_'.$row['id'].'">';
			
			echo '</canvas>';
			
			//Za obmocja
 			echo '<map id="hotspot_'.$row['id'].'_map" name="hotspot_'.$row['id'].'_usemap">';
				while ($rowR = mysqli_fetch_array($sqlR)) {
					echo '<area coords="'.$rowR['region_coords'].'" name="'.$rowR['vre_id'].'" shape="poly" href="#">';
				}
			echo '</map>';
			
			//************************************************************************************************	
			//izris radio button in checkbox, ki bo skrita in beležila odgovore na sliki
			echo '<div id="heatmapCheckbox_'.$row['id'].'">';
			RadioCheckboxSelect::getInstance()->display($spremenljivka);	//prikaze radio button in checkbox z odgovori									
			echo '</div>';
			//izris radio button in checkbox, ki bo skrita in beležila odgovore na sliki - konec
			//***********************************************************************************			
			//Za obmocja - konec
			
			//GUMBA
			echo '<div class="buttonsHeatmap">';
			
			//gumb za resetiranje vseh tock
			echo '<input id="resetHeatMapCanvas_'.$row['id'].'" type="button"  value="'.$lang['srv_vprasanje_heatmap_reset_canvas'].'">';
			
			echo '&emsp;';
			
			//gumb za resetiranje zadnje tocke
			echo '<input id="resetHeatMapLastPoint_'.$row['id'].'" type="button" value="'.$lang['srv_vprasanje_heatmap_reset_last_point'].'">';
			
			echo '</div>';
			//GUMBA - konec
			
			//canvas - konec
			
			//div za stevec klikov
			if($heatmap_show_counter_clicks){
				echo '</br><div id="heatmapClickCounter_'.$spremenljivka.'">';
					echo '<div>'.$lang['srv_vprasanje_heatmap_num_clicks'].':</div>';
					echo '<div id="heatmapClickNumber_'.$spremenljivka.'" style="float:left">0</div>/'.$heatmap_num_clicks;
				echo '</div>';
			}
			//div za stevec klikov - konec
			
			//div za inpute tock
			echo '<div id="heatmapInputs_'.$spremenljivka.'" style="display:none">';		
			echo '</div>';
			//div za inpute tock - konec
			
			?>
			<script>

				mousePos[<?=$spremenljivka?>] = new Array(2);
				indeksMousePos[<?=$spremenljivka?>] = 0;
				heatmap_num_clicksGlobal[<?=$spremenljivka?>] = <?=$heatmap_num_clicks?>;
				var heatmapDataLength = [];
				var refreshed = [];
				for(i = 1; i <= <?=$heatmap_num_clicks?>; i++){
					mousePos[<?=$spremenljivka?>][i] = 0;
					//console.log("mousePos[<?=$spremenljivka?>]["+i+"]: "+mousePos[<?=$spremenljivka?>][i]);
				}
				
				$(document).ready(function () {
					
					InitHeatMapCanvas(<?=$spremenljivka?>, <?=$quick_view?>);
					//Ta spremenljivka ze vsebuje podatke? primer, ce gre uporabnik na prejsnjo stran ali refresh-a stran
					var heatmap_data = JSON.parse('<?php echo json_encode($heatmap_data); ?>');
					heatmapDataLength[<?=$spremenljivka?>] = heatmap_data.length;	//belezi zacetno dolzino polja
					//ce ze obstajajo tocke, jih dodaj na canvas
					if (heatmap_data.length > 0){
						heatmap_data_add(<?=$spremenljivka?>, heatmap_data, '<?=$heatmap_click_color?>', <?=$heatmap_click_size?>, <?=$heatmap_click_shape?>, <?=$heatmap_show_clicks?>, <?=$heatmap_num_clicks?>);
						refreshed[<?=$spremenljivka?>] = 1;
					}	
				});
				$('#heatmapCanvas_<?=$spremenljivka?>').click(function (e) {
					HeatMapCanvasDelovanje(e, <?=$spremenljivka?>, <?=$heatmap_show_clicks?>, <?=$heatmap_num_clicks?>, '<?=$heatmap_click_color?>', <?=$heatmap_click_size?>, <?=$heatmap_click_shape?>, <?=$quick_view?>);
				});
				$('#heatmapCanvas_<?=$spremenljivka?>').mouseover(function (e) {
					if (heatmap_num_clicksGlobal[<?=$spremenljivka?>] != 0){	//ce je se mozno klikati
						$(this).css( 'cursor', 'pointer' );	//ko je se miška nahaja na canvas-u, naj se spremeni v rokico
					}
				});
				//klicanje funkcije za brisanje vseh tock na canvasu
				$('#resetHeatMapCanvas_<?=$spremenljivka?>').click(function (e) {
					resetHeatMapCanvas(<?=$spremenljivka?>, <?=$heatmap_num_clicks?>, <?=$quick_view?>);
				});
				
				//klicanje funkcije za brisanje zadnje izbrane tocke na canvasu
				$('#resetHeatMapLastPoint_<?=$spremenljivka?>').click(function (e) {
					var heatmap_data = JSON.parse('<?php echo json_encode($heatmap_data); ?>');
					resetHeatMapLastPoint(<?=$spremenljivka?>, <?=$heatmap_num_clicks?>, <?=$heatmap_show_clicks?>, '<?=$heatmap_click_color?>', <?=$heatmap_click_size?>, <?=$heatmap_click_shape?>, heatmap_data, <?=$quick_view?>);
				});
				
				$('#heatmapCheckbox_<?=$spremenljivka?> .variabla').css("display", "none");	//skrij radio/checkbox button odgovore, kjer se bodo beležili odgovori
				
			</script>	
			<?
			
			echo '</div>';

		echo '      </div>';
	}
}