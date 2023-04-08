<?php

/** *
 *  Ime: Samoocena hierarhija
 *  Opis: Class skrbi za izdelavo hierarhije za administratorja/osebo s
 * pravicami za gradnjo hierarhije na nivoju posamezne ankete Avtor: Robert
 * Šmalc
 */

namespace Hierarhija;

use Cache;
use Common;
use Export;
use finfo;
use Help;
use Hierarhija\Model\HierarhijaOnlyQuery;
use Hierarhija\Model\HierarhijaQuery;
use MailAdapter;
use SurveyInfo;
use SurveySetting;
use TrackingClass;
use function is_null;


class Hierarhija {

	#v konstruktor poberemo vse globalne spremenljivke, ki jih omenjen razrred uporablja
	protected $anketa;

	/**
	 * Funkcija poišče ustrezne srv_vrednost-i za določeno spremenljivko, kjer
	 * moramo izbrati "variablo" te spremenljivke
	 *
	 * @param (string) $var
	 *
	 * @return (array) or null
	 */

	protected $var;

	public function __construct($anketa)
	{
		global $lang, $global_user_id, $site_url, $admin_type;

		$this->anketa = $anketa;
		$this->lang = $lang;
		$this->hierarhija_type = HierarhijaHelper::preveriTipHierarhije($this->anketa);
		$this->user = $global_user_id;
		$this->admin_type = $admin_type;
		$this->url = $site_url;
		$this->modul = \SurveyInfo::getSurveyModules();
	}

	/**
	 * Inicializacija hierarhije
	 *
	 * @param
	 *
	 * @return
	 */
	public static function hierarhijaInit($anketa)
	{
		$new = new Hierarhija($anketa);
		$new->DolociPraviceUporabniku();
		$new->izrisisSistemskoVprsanjeVloga();
		$new->hierarhijaSuperadminSifranti();
	}

	/**
	 * Uporabniku določimo pravice, če vključi anketo dobi type 1 - admin
	 * hierarhije
	 */
	public function dolociPraviceUporabniku()
	{
		if (is_null($this->hierarhija_type)) {

				// Preverimo, kdo je anketo ustvaril
				$sql_dostop = sisplet_query("SELECT type FROM srv_hierarhija_users WHERE anketa_d='".$this->anketa."' AND user_id='".$this->user."'", "obj");

				if(empty($sql_dostop))
					$user_query = sisplet_query("INSERT INTO srv_hierarhija_users (user_id, anketa_id, type) VALUES ('".$this->user."', '$this->anketa', 1)");


				if (!$user_query && $this->admin_type == 0) {
					echo mysqli_error($GLOBALS['connect_db']);
				}
		}
	}

	/**
	 * Ko se kativira anketa se določi prvo sistemsko vprašanje VLOGA (učenece,
	 * učitelj) to vprašanje samo 1 izrišemo in potem nikoli več
	 */
	public function izrisisSistemskoVprsanjeVloga()
	{
		//Preverimo, če je sistemsko vprašanje vloga že ustvarjeno
		$grup_id = sisplet_query("SELECT id, vrstni_red FROM srv_grupa WHERE ank_id='" . $this->anketa . "' ORDER BY vrstni_red LIMIT 0,1", "obj");
		$sql_vpisane_spr = sisplet_query("SELECT id, gru_id, variable, vrstni_red FROM srv_spremenljivka WHERE gru_id='" . $grup_id->id . "' AND variable='vloga'");

		//V kolikor je vloga že vnešena in ni postavljena na prvo mesto, potem jo moramo premakniti na prvo mesto
		if (mysqli_num_rows($sql_vpisane_spr) == 0) {

			//preštevilčimo ostala vprašanja za 1
			(new HierarhijaAjax($this->anketa))->prestevilciBranching(0, TRUE);

			//vedno ustavimo vlogo (učenec - učitelj)
			$vloga = [$grup_id->id, 'vloga', 'vloga', '2', '1'];
			(new HierarhijaQuery())->insertSpremenljivkaBranching($vloga, NULL, $this->anketa, 1);

		}
	}

	/**
	 *  Prikaže nastavitve za dodajanje nivojev in šifrantov - SUPERADMIN
	 * HIERARHIJA
	 *
	 * @return html page
	 */
	public function hierarhijaSuperadminSifranti()
	{
		$aktivna = $this->preveriCeJeAktivirana();
		$this->preverimoCeJeVnesenaStruktura();

		if ($_GET['e'] == 'null') {
			echo '<div style="color: #ffa608; font-style: italic;">' . $this->lang['srv_hierarchy_element_missing'] . '</div>';
		}

		if ($aktivna && (is_null($this->hierarhija_type) || $this->hierarhija_type < 4)) {
			echo '<div id="hierarhija-app">';

			// meni na levi strani
			echo '<div class="hierarhija-levi-meni">';
			echo '<div>' . $this->lang['srv_hierarchy_save_list'] . '</div>';
			echo '<div class="h-tabela">';
			echo '<table><tbody>';
			echo '<tr v-for="shranjena in shranjenaHierarhija">';
			echo '<td class="h-ime-shranjeno"
                        v-show="!imeHierarhije.urejanje"
                        v-on:click="pregledShranjeneHierarhije($index, shranjena.id, shranjena.struktura)"';
			//                        v-on:click="uporabiShranjenoHierarhijo($index, shranjena.id, shranjena.struktura)"
			echo 'v-bind:class="[{ \'active\': $index == imeHierarhije.index  }]"                     
                        >                        
                        <div class="h-ime-prikaz">
                            {{ shranjena.ime }} 
                            <span class="stevilo-evalvirancev" title="Število uporabnikov, ki so že dodani k strukturi" v-if="shranjena.stUporabnikov > 0">({{ shranjena.stEvalvirancev }})</span>
                        </div>
                    <td>';
			echo '<td class="h-brisi-shranjeno" v-show="imeHierarhije.urejanje" v-on:click="izbrisiShranjenoHierarhijo($index, shranjena.id)"><span class="faicon delete_circle icon-orange_link"  title="' . $this->lang['srv_hierarchy_help_1'] . '"></span></td>';
			echo '<td class="h-ime-shranjeno"
                        v-show="imeHierarhije.urejanje"      
                        v-on:click="imeHierarhije.index = $index"
                        v-bind:class="[{ \'editable-hierarhija\': $index == imeHierarhije.index && imeHierarhije.urejanje}]"
                        v-bind="{ contenteditable: $index == imeHierarhije.index && imeHierarhije.urejanje }" 
                        v-on:blur="preimenujHierarhijo($index, shranjena.id)">
                        <div class="h-ime-prikaz h-urejanje">
                            {{ shranjena.ime }}
                            <span class="stevilo-evalvirancev" title="Število uporabnikov, ki so že dodani k strukturi" v-if="shranjena.stUporabnikov > 0 && imeHierarhije.index != $index"">({{ shranjena.stEvalvirancev }})</span>
                        </div>
                    <td>';
			echo '</tr>';
			echo '</tbody></table>';
			echo '</div>';
			echo '<div>
                        <span style="float: left; padding: 6px 15px 5px 0;">Urejanje</span>
                        <div class="onoffswitch" style="float: left; margin: 6px;">
                            <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" v-model="imeHierarhije.urejanje" id="urejanje-imen" v-on:click="posodobiOpcijeHierarhije()">
                            <label class="onoffswitch-label" for="urejanje-imen">
                                <span class="onoffswitch-inner"></span>
                                <span class="onoffswitch-switch"></span>
                            </label>
                        </div>
                    </div>';
			echo '<div style="float: left;">
                        <span style="margin: 10px 15px 0 0;display: inline-block;float: left;">Uvoz/Izvoz</span>
                        <span class="faicon import hierarhija-ikona" 
                              title="Uvoz hierarhije" 
                              style="margin: 8px 15px 0 0"
                              @click="uvozHierarhije"
                        ></span>
                        <a href="index.php?anketa=' . $this->anketa . '&a=hierarhija_superadmin&m=' . M_ADMIN_IZVOZ_SIFRANTOV . '">
                        <span class="faicon export hierarhija-ikona" 
                              title="Izvoz hierarhije" 
                              style="margin: 8px 10px 0 0"                        
                        ></span>
                        </a>
                    </div>';
			echo '</div>';

			// Uvodno besedilo, ko se aktivira modul hierarhija$anketa
			echo '<div v-if="imeHierarhije.aktivna.length == 0 && 
                             imeHierarhije.shrani.length == 0 &&                           
                             previewHierarhije.ime.length == 0"
                        style="width: 650px;"
                  >';
			echo '<h1>' . $this->lang['srv_hierarchy_wellcome_title'] . '</h1>';
			echo $this->lang['srv_hierarchy_wellcome_text'] . '<br /><br />';
			echo '<button class="btn btn-moder" 
                        v-on:click="izbrisiCelotnoHierarhijo()"   
                        style="float:right; margin: 20px 60px;"
                        >
                        Ustvari novo hierarhijo
                        </button>';
			echo '</div>';


			// Omogočimo predogled hierarhije
			echo '<div class="hierarhija_fieldset" v-show="previewHierarhije.vklop && previewHierarhije.ime.length > 0" style="display:none;">';
			echo '<h1>Predogled hierarhije: <span class="oranzna">{{ previewHierarhije.ime }}</span></h1>';

			echo '<div v-if="shranjenaHierarhija[previewHierarhije.index] && shranjenaHierarhija[previewHierarhije.index].stUporabnikov > 0">Hierarhija ima {{ shranjenaHierarhija[previewHierarhije.index].stEvalvirancev }} evalvacij in {{ shranjenaHierarhija[previewHierarhije.index].stUporabnikov }} uporabnikov.</div>';

			// Tabela nivojev in šifrantov
			echo '<div id="primer-sifrantov" v-show="previewHierarhije.input.length > 0">';
			echo '<table>';
			echo '<thead style="text-align: left;">';
			echo '<tr>';
			echo '<th>' . $this->lang['srv_hierarchy_table_header_1'] . '</th>';
			echo '<th>' . $this->lang['srv_hierarchy_table_header_2'] . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			// Vuejs dinamično kreiranje novih nivojev
			echo '<tr id="nivo-{{ nivo.id }}" v-for="nivo in previewHierarhije.input" track-by="$index">';
			echo '<td><label> {{ nivo.st}}. {{ nivo.ime }}</label></td>';
			echo '<td><select name="nivo" data-opcije="{{ nivo.id }}">';
			echo '<option value = "#" v-for="sifrant in nivo.sifranti">{{ sifrant.ime }}</option >';
			echo '</select></td>';
			echo '</tr>';

			echo '</tbody></table></div>';
			echo '<div v-else>Hierarhija je prazna in ima določen samo naslov.</div>';

			// Gumbi
			echo '<div style="padding:20px 10px; float: right;">';
			echo '<button class="btn btn-moder" @click="izklopiPredogled()" style="margin: 0 10px;">' . $this->lang['back'] . '</button>';
			echo '<button class="btn btn-moder" 
                                    @click="aktivirajIzbranoHierarhijo()"
                                    v-if="previewHierarhije.input.length > 0"                                    
                           >
                                    Uporabi omenjeno hierarhijo
                           </button>';
			echo '</div>';
			echo '</div>';


			// naslov hierarhije
			echo '<div class="hierarhija_fieldset" v-if="(imeHierarhije.aktivna > 0 || imeHierarhije.shrani.length > 0) && !previewHierarhije.vklop">';
			echo '<div class="left-float">';
			echo '<h1>Hierarhija
                        <span class="oranzna">
                            <span v-if="imeHierarhije.shrani != imeHierarhije.aktivna">{{ (imeHierarhije.shrani.length > 30 ? (imeHierarhije.shrani.substring(0,30)+\' ...\') }}</span>
                            <span v-else>{{ (imeHierarhije.aktivna.length > 30 ? (imeHierarhije.aktivna.substring(0,30)+\' ...\') : imeHierarhije.aktivna) }}</span>
                        </span>                                       
                     </h1>';
			echo '</div>';
			echo '<div class="left-float" style="padding: 20px 10px;">
                        <a href="#" 
                           class="surveycomment"           
                           v-on:click="dodajKomentar()"               
                           title="Dodaj komentar k hierarhiji"
                        > 
                            <span class="faicon inline_comment"></span> Dodaj komentar
                        </a>
                      </div>';
			echo '<div class="left-float" style="padding: 20px 0px;">
                        <a href="#" class="logo-upload" v-on:click="logoUpload()" title="Naloži logotip za izpis pri poročilih"> 
                            <i class="fa fa-lg fa-file-image-o" aria-hidden="true"></i> Naloži logo
                        </a>
                      </div>';

			// V kolikor imamo že strukturo prikažemo tudi številke
			echo '<div style="clear:both;" v-if="shranjenaHierarhija[imeHierarhije.index] && shranjenaHierarhija[imeHierarhije.index].stUporabnikov > 0">Hierarhija ima {{ shranjenaHierarhija[imeHierarhije.index].stEvalvirancev }} evalvacij in {{ shranjenaHierarhija[imeHierarhije.index].stUporabnikov }} uporabnikov.</div>';

			// Dodajanje nivojev in njihovih nazivov
			echo '<div v-if="!vpisanaStruktura" style="clear: both;">';
			echo '<h2 v-if="!vpisanaStruktura">' . $this->lang['srv_hierarchy_create_code'] . '</h2>';
			echo '<div class="hierarhija-nov-nivo"
                            v-on:click="izbrisiCelotnoHierarhijo()" 
                            v-if="!vpisanaStruktura"
                            style="float: right;display: block;margin:-30px -35px 0 0;">
                            Ustvari novo hierarhijo<span class="faicon edit small icon-as_link pointer h-edit-nivo hierarhija-inline"></span>
                            </div>';
			echo '<div>';
			echo '<table v-if="!vpisanaStruktura">';
			echo '<thead style="text-align: left;">';
			echo '<tr>';
			echo '<th>' . $this->lang['srv_hierarchy_table_header_nivo_1'] . '</th>';
			echo '<th style="padding-left: 15px;">' . $this->lang['srv_hierarchy_table_header_nivo_2'] . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			// Vuejs dinamično kreiranje novih nivojev
			echo '<tr>';
			echo '<td>';
			echo '<span class="hierarhija-elementi">{{ novaHierarhijaSt }}.</span>';
			echo '</td>';
			echo '<td>';
			echo '<input type="text" size="50" class="hierarhija-elementi"  v-model="imeNivoja" v-on:keyup.enter="dodajNivoHierarhije()">';
			echo '<div class="hierarhija-nov-nivo"  v-on:click="dodajNivoHierarhije()">' . $this->lang['srv_hierarchy_input_name_nivo'] . '<span class="hierarhija-plus"></span></div>';
			echo '</td>';
			echo '</tr>';

			echo '</tbody></table>';
			echo '</div>';
			echo '</div>';
			echo '<div class="clear"></div>';


			// Selectbox s šifranti za posamezen nivo, vpis šifrantov dovolimo šele ko imamo vpisano prvo raven
			echo '<div v-show="inputNivo[0]">';
			echo '<div class="h-sa-list">';
			echo '<div style="margin-top: 16px;">';
			echo '<h2 style="float: left; display: inline-block; margin-top: 0;">' . $this->lang['srv_hierarchy_code_lists'] . '</h2>';

			echo '<div class="hierarhija-urejanje">
                                  <div class="onoffswitch">
                                        <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" v-model="vklopiUrejanje" id="vklopi-urejanje-hierarhije" checked v-on:click="getSaveOptions(\'admin_skrij_urejanje_nivojev\', !vklopiUrejanje)">
                                        <label class="onoffswitch-label" for="vklopi-urejanje-hierarhije">
                                            <span class="onoffswitch-inner"></span>
                                            <span class="onoffswitch-switch"></span>
                                        </label>
                                    </div>
                            </div>';
			echo '<span class="toolbox_add_title"> ' . Help::display('srv_hierarchy_edit_elements') . '</span>';

			echo '</div>';

			echo '<div class="clear"></div>';

			// izris primera šifrantov
			echo '<div id="primer-sifrantov">';
			echo '<table>';
			echo '<thead style="text-align: left;">';
			echo '<tr>';
			echo '<th>' . $this->lang['srv_hierarchy_table_header_1'] . '</th>';
			echo '<th>' . $this->lang['srv_hierarchy_table_header_2'] . '</th>';
			echo '<th>' . $this->lang['srv_hierarchy_table_header_3'] . '</th>';
			echo '<th v-show="vklopiUrejanje">' . $this->lang['srv_hierarchy_table_header_4'] . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			// Vuejs dinamično kreiranje novih nivojev
			echo '<tr id="nivo-{{ nivo.id }}" v-for="nivo in inputNivo">';
			echo '<td><label> {{ nivo.st}}. <span contenteditable="true" class="h-edit-nivo editable" data-labela="{{ nivo.id }}" v-on:blur="preimenujLabeloNivoja(nivo.id)">{{ nivo.ime }}</span></label></td>';
			echo '<td><select name="nivo" data-opcije="{{ nivo.id }}">';
			echo '<option value = "#" v-for="sifrant in nivo.sifranti">{{ sifrant.ime }}</option >';
			echo '</select></td>';

			echo '<td>';
			echo '<span class="faicon delete_circle icon-orange_link spaceLeft hierarhija-inline"  v-on:click="odstraniNivoHierarhije($index, nivo.id)"  v-show="vklopiUrejanje && vpisanaStruktura == 0" title="' . $this->lang['srv_hierarchy_help_1'] . '"></span>';
			echo '<span class="faicon edit small icon-as_link pointer h-edit-nivo hierarhija-inline" v-on:click="brisiSifrant(nivo.id)" v-show="vklopiUrejanje" title="' . $this->lang['srv_hierarchy_help_2'] . '"></span>';

			## Vstavimo checkboc od kje naprej se lahko šifranti ponavljajo
			echo '<input type="checkbox" name="unikatni-sifranti" class="hierarhija-inline" style="cursor: pointer;" value="1" :checked="nivo.unikaten == 1" v-on:click="posodobiUnikatnega(nivo.id, nivo)" title="' . $this->lang['srv_hierarchy_help_3'] . '">';
			echo '</td>';
			echo '<td>';
			echo '<input type="text" class="hierarhija-inline" size="40" data-nivo="{{ nivo.id }}" v-show="vklopiUrejanje"  v-on:keyup.enter="dodajSifrant( $index, nivo.id )" placeholder="' . $this->lang['srv_hierarchy_input_name_sifrant'] . '"/>';
			echo '<div class="hierarhija-nov-nivo"    v-show="vklopiUrejanje" v-on:click="dodajSifrant( $index, nivo.id )">' . $this->lang['srv_hierarchy_input_name_sifrant'] . '<span class="hierarhija-plus"></span></div>';
			echo '</td>';
			echo '</tr>';

			echo '</tbody></table>';
			echo '</div>';

			// Naprej na naslednji korak
			echo '<div>';
			// Shranjevanje hierarhije
			echo '<div style="padding: 20px 0 10px;" v-show="prikaziImeZaShranjevanje">';
			echo '<b>Shrani trenutno hierarhijo pod imenom: </b>';
			echo '<input size="50" type="text" v-model="imeHierarhije.shrani">';
			echo '</div>';

			echo '<div style="float: right; width: 100%; text-align: right; padding: 7px 0;">
                    <input type="checkbox" v-model="prikaziImeZaShranjevanje" value="1"> Hierarhijo želim shraniti pod novim imenom.
                 </div>';

			echo '<div style="padding:20px;float:right;">';
			echo '<button v-on:click="shraniTrenutnoHierarhijo()" v-show="prikaziImeZaShranjevanje" class="btn btn-moder" style="margin-right: 15px;">' . $this->lang['save'] . '</button>';
			echo '<button v-on:click="premikNaprej(\'' . M_UREDI_UPORABNIKE . '\')" class="btn btn-moder" style="margin-right: 15px;">' . $this->lang['next1'] . '</button>';
			echo '</div>';
			echo '</div>';

			echo '</div>';


			echo '</fieldset>';
			echo '</div>';
			echo '</div>';

			// popup za urejanje vrednosti
			echo '<div id="vrednost_edit" class="divPopUp">';
			echo '</div>';

			// fade pri fullscreen urejanje spremenljivke
			echo '<div id="fade">';
			echo '</div>';

		} else {
			echo '<div id="hierarhija-app">';
			// Naslov
			echo '<div class="hierarhija_fieldset">';
			echo '<div class="left-float"><h1 v-show="imeHierarhije.aktivna">Hierarhija: <span style="color: #fa4913">{{ imeHierarhije.aktivna }}</span></h1></div>';
			echo '<div class="left-float" style="padding: 20px;"><a href="#" class="surveycomment" title="Dodaj komentar o vprašanju"> <span class="faicon inline_comment"></span> Dodaj komentar</a></div>';
			echo '<div class="left-float" style="padding: 20px 0px;">
                        <a href="#" class="logo-upload" v-on:click="logoUpload()" title="Naloži logotip za izpis pri poročilih"> 
                            <i class="fa fa-lg fa-file-image-o" aria-hidden="true"></i> Naloži logo
                        </a>
                      </div>';
			echo '</div>';

			echo '<div class="clear"></div>';

			// Podatki o aktivaciji hierarhije
			$hierarhija_options = new HierarhijaQuery();
			$cas_aktivacije = $hierarhija_options->getDeleteHierarhijaOptions($this->anketa, 'cas_aktivacije_hierarhije', NULL, NULL, FALSE);
			$uporabnik_aktivacije = $hierarhija_options->getDeleteHierarhijaOptions($this->anketa, 'uporabnik_aktiviral_hierarhijo', NULL, NULL, FALSE);
			$uporabnik = HierarhijaQuery::getUserSurvey($uporabnik_aktivacije);

			echo '<div class="okvircek" style="padding-bottom: 3px;">';
			echo '<h3>Čas aktivacije: <b>' . $cas_aktivacije . '</b></h3>';
			echo '<h3>Hierarhijo je aktiviral uporabnik: <b>' . $uporabnik->name . ' ' . $uporabnik->surname . '</b> (' . $uporabnik->email . ') <a class="btn" href="index.php?anketa=' . $this->anketa . '&a=hierarhija_superadmin&m=aktivacija-strukture-ankete">Podrobnosti</a></h3>';
			echo '</div>';

			echo '<div class="clear"></div>';

			// Tabela nivojev in šifrantov
			echo '<div id="primer-sifrantov" class="pregled">';
			echo '<table>';
			echo '<thead style="text-align: left;">';
			echo '<tr>';
			echo '<th>' . $this->lang['srv_hierarchy_table_header_1'] . '</th>';
			echo '<th>' . $this->lang['srv_hierarchy_table_header_2'] . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			// Vuejs dinamično kreiranje novih nivojev
			echo '<tr id="nivo-{{ nivo.id }}" v-for="nivo in inputNivo">';
			echo '<td><label> {{ nivo.st}}. {{ nivo.ime }}</label></td>';
			echo '<td><select name="nivo" data-opcije="{{ nivo.id }}">';
			echo '<option value = "#" v-for="sifrant in nivo.sifranti">{{ sifrant.ime }}</option >';
			echo '</select></td>';
			echo '</tr>';

			echo '</tbody></table>';
			echo '</div>';

			echo '<div style="width:35%;text-align: right;">';
			echo '<a class="btn btn-moder" href="index.php?anketa=' . $this->anketa . '&a=hierarhija_superadmin&m=' . M_ADMIN_IZVOZ_SIFRANTOV . '">Izvoz šifrantov</a>';
			echo '</div>';

			echo '</div>';
		}
	}

	/**
	 * V kolikor je hierarhija aktivirana potem jo ni mogoče več urejati
	 *
	 * @return boolean
	 */

	private function preveriCeJeAktivirana()
	{

		if ($this->modul['hierarhija'] == 2 && $this->hierarhija_type < 4) {
			echo $this->lang['srv_hierarchy_active_text'];
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Preverimo, če je bila struktura vnešena
	 *
	 * @return  boolean
	 */

	private function preverimoCeJeVnesenaStruktura()
	{

		$hierarhija_opcije = (new HierarhijaQuery())->getDeleteHierarhijaOptions($this->anketa, 'vpisana_struktura', NULL, NULL, FALSE);

		if (sizeof($hierarhija_opcije) > 0 && isset($hierarhija_opcije['vpisana_struktura']) && $hierarhija_opcije['vpisana_struktura'] == 1) {
			$sql_str = sisplet_query("SELECT id FROM  srv_hierarhija_struktura WHERE anketa_id='" . $this->anketa . "'");

			if ($sql_str->num_rows == 0) {
				(new HierarhijaQuery())->getDeleteHierarhijaOptions($this->anketa, 'vpisana_struktura', $id = NULL, 1);
			}
		}

	}

	/**
	 * Ko se aktivira anketa se posreduje email učiteljem za reševanje ankete
	 */

	public static function aktivacijaAnketePosljiEmail($anketa)
	{
		# Dobimo samo uporabnike na zadnjem nivoju
		$users_upravicen_do_evalvacije = (new HierarhijaOnlyQuery())->queryStrukturaUsers($anketa, ' AND hs.level=(SELECT MAX(level) FROM srv_hierarhija_struktura WHERE anketa_id=' . $anketa . ') GROUP BY users.id');

		if ($users_upravicen_do_evalvacije->num_rows == 0) {
			return FALSE;
		}

		# Če imamo uporabnike potem gremo za vsak id uporabnika preverit kakšno ima strukturo
		while ($uporabnik = $users_upravicen_do_evalvacije->fetch_object()) {
			$vloga_poizvedba = self::spremenljivkaVloga('vloga', $anketa);
			$url_hierarhija = self::hierarhijaUrl($anketa, $uporabnik->user_id);

			// generiramo kode za vse
			foreach ($url_hierarhija as $struktura_id => $url) {
				// generiranje kode
				foreach ($vloga_poizvedba as $v) {
					if ($v->variable == 1) {
						$vloga = 'ucenec';
					}

					if ($v->variable == 2) {
						$vloga = 'ucitelj';
					}

					// Url parametri hierarhije (nivoji in vloga)
					$url_baza = 'vloga=' . $v->id . $url;

					// Url parametre vstavimo v tabelo in generiramo kodo, Pri kodi trenutno uporabimo brez šumnikov, kar zadošča za 60.466.176 različnih kod (36 unikatnih znakov), če bi primanjkovalo se doda šumnik in je nato 90.224.199 kod.

					$vpis_kode_loop = FALSE;
					while (!$vpis_kode_loop) {
						$vpis_kode_loop = sisplet_query("INSERT INTO 
                                                              srv_hierarhija_koda 
                                                              (koda, anketa_id, url, vloga, user_id, hierarhija_struktura_id, datetime) 
                                                        VALUES 
                                                              (CONCAT(SUBSTRING('abcdefghijklmnoprstuvzwxq0123456789', RAND()*34+1, 1),
                                                                      SUBSTRING('abcdefghijklmnoprstuvzwxq0123456789', RAND()*34+1, 1),
                                                                      SUBSTRING('abcdefghijklmnoprstuvzwxq0123456789', RAND()*34+1, 1),
                                                                      SUBSTRING('abcdefghijklmnoprstuvzwxq0123456789', RAND()*34+1, 1),
                                                                      SUBSTRING('abcdefghijklmnoprstuvzwxq0123456789', RAND()*34+1, 1)
                                                                     ), '" . $anketa . "', '" . $url_baza . "', '" . $vloga . "',  '" . $uporabnik->user_id . "', '" . $struktura_id . "', NOW())");


					}
				}
			}
		}

		if (HierarhijaQuery::getOptionsPosljiKode($anketa) == 'nikomur') {
			HierarhijaQuery::saveOptions($anketa, 'obvesti_samo_ucitelje', 0);
		} else {
			self::posljiEmailSkodamiUcencemAliSamoUciteljem($anketa);
		}

		return TRUE;
	}

	private static function spremenljivkaVloga($var, $anketa)
	{
		$spremenljivke = Cache::cache_all_srv_spremenljivka($anketa, TRUE);

		$spremenljivka_id = NULL;
		foreach ($spremenljivke as $spr) {
			if ($spr['variable'] == $var) {
				$spremenljivka_id = $spr['id'];
			}
		}

		if (!is_null($spremenljivka_id)) {
			return Cache::cache_all_srv_vrednost($spremenljivka_id);
		}

		return NULL;
	}

	private static function hierarhijaUrl($anketa, $user = NULL)
	{
		$hierarhija = (new HierarhijaQuery())->pridobiHierarhijoNavzgor($anketa, NULL, $user);

		// če hierarhija še ni narejena
		if (is_null($hierarhija)) {
			return [];
		}

		$max_level = sisplet_query("SELECT MAX(level) AS level FROM srv_hierarhija_ravni WHERE anketa_id='" . $anketa . "'", "obj");

		//najprej moramo priti do polja z ustreznimi nivjo
		foreach ($hierarhija as $key => $array) {

			// če smo res na zadnjem nivoju
			if ($max_level->level == sizeof($array)) {
				//gremo po nivojih ter sestavimo URL naslov
				$url_zacasni = NULL;
				foreach ($array as $nivoji) {
					$id = (new HierarhijaQuery())->getVrednostIdFromPivot($nivoji['id']);
					$url_zacasni .= '&' . $nivoji['nivo'] . '=' . $id;
				}

				//sestavljen url dodamo v polje, kot ključ uporabimo ID strukture hierarhije
				$url[$key] = $url_zacasni;
			}
		}

		return $url;
	}

	/**
	 * Funkcija za pošiljanja kode učiteljem pri hierarhiji
	 *
	 * @param (int) $anketa
	 *
	 * @return send email | error
	 */

	public static function posljiEmailSkodamiUcencemAliSamoUciteljem($anketa)
	{
		global $site_url;
		global $lang;

		$ucitelji = sisplet_query("SELECT user_id FROM srv_hierarhija_koda WHERE anketa_id='" . $anketa . "' AND vloga='ucitelj' GROUP BY user_id");
		$koda_za_resevanje_ankete = HierarhijaQuery::getOptionsPosljiKode($anketa);

		if (mysqli_num_rows($ucitelji) == 0) {
			return 'Ni podatka o učiteljih';
		}


		while ($ucitelj = $ucitelji->fetch_object()) {
			$kode = sisplet_query("SELECT koda, hierarhija_struktura_id FROM srv_hierarhija_koda WHERE anketa_id='" . $anketa . "' AND vloga='ucitelj' AND user_id='" . $ucitelj->user_id . "'");

			// Email naslov
			$subject = 'Povezava do samooevalvacije za anketo: ' . SurveyInfo::getSurveyTitle();

			// Email besedilo
			$email = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
                        <html xmlns="http://www.w3.org/1999/xhtml">
                        <head>               
                          <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />                   
                          <style>
                            body {
                              width: 100% !important;
                              min-width: 100%;
                              -webkit-text-size-adjust: 100%;
                              -ms-text-size-adjust: 100%;
                              margin: 0;
                              Margin: 0;
                              padding: 0;
                              -moz-box-sizing: border-box;
                              -webkit-box-sizing: border-box;
                              box-sizing: border-box; 
                            }
                            
                            table {
                              border-spacing: 0;
                              border-collapse: collapse; 
                            }
                            
                            td {
                              word-wrap: break-word;
                              -webkit-hyphens: auto;
                              -moz-hyphens: auto;
                              hyphens: auto;
                              border-collapse: collapse !important; 
                            }                            
                       
                            
                            tr, td, th {
                              text-align: left; 
                              padding: 8px 10px;
                              border-color: #dddddd;
                              border-width: 1px;
                              border-style: solid;
                            }
                            
                            
                            th {
                              background-color: #EFF2F7;                              
                            }
                            
                            .center{
                              text-align: center !important;
                            }
                          </style>
                        </head><body>';

			$email .= $lang['srv_hierarchy_teacher_email_1'];
			$email .= '<p>' . $lang['srv_hierarchy_teacher_email_2'] . '»<b>' . SurveyInfo::getSurveyTitle() . '</b>«' . $lang['srv_hierarchy_teacher_email_3'] . '<a href="' . $site_url . 'sa" target="_blank">' . $site_url . 'sa</a></p>';

			$email .= '<br /><table cellspacing="0" style="border-collapse: collapse;">';
			$email .= '<thead>';
			$email .= '<tr>
                                <th border="1" cellpadding="0">Hierarhija</th>';

			if (SurveyInfo::getSurveyModules('hierarhija') == 2 || in_array($koda_za_resevanje_ankete, ['vsem', 'ucitelju'])) {
				$email .= '<th  border="1" cellspacing="0" class="center">Koda za učitelja</th>';
			}

			// V kolikor nimamo nikakršne izbere potem posredujemo kodo tudi za učence
			if (in_array($koda_za_resevanje_ankete, ['vsem', 'ucencem'])) {
				$email .= '<th border="1" cellspacing="0" class="center">Koda za učence</th>';
			}

			$email .= '<tr>';
			$email .= '</thead>';
			$email .= '<tbody>';

			// generiranje kode
			while ($koda = mysqli_fetch_object($kode)) {
				$email .= '<tr>';
				$email .= '<td border="1" cellspacing="0">' . HierarhijaHelper::hierarhijaPrikazNaslovovpriUrlju($anketa, $koda->hierarhija_struktura_id, TRUE) . '</td>';

				if (SurveyInfo::getSurveyModules('hierarhija') == 2 || in_array($koda_za_resevanje_ankete, ['vsem', 'ucitelju'])) {
					$email .= '<td border="1" cellspacing="0" class="center"><span style="letter-spacing: 1px; font-size:16px; font-weight: bold;">' . strtoupper($koda->koda) . '</span></td>';
				}

				// V kolikor prejme učitelj email tudi s kodami za učence
				if (in_array($koda_za_resevanje_ankete, ['vsem', 'ucencem'])) {
					$koda_ucenci = sisplet_query("SELECT koda FROM srv_hierarhija_koda WHERE anketa_id='" . $anketa . "' AND vloga='ucenec' AND user_id='" . $ucitelj->user_id . "' AND hierarhija_struktura_id='" . $koda->hierarhija_struktura_id . "'", "obj");
					$email .= '<td border="1" cellspacing="0" class="center"><span style="letter-spacing: 1px; font-size:16px; font-weight: bold; color:#ffa608;">' . strtoupper($koda_ucenci->koda) . '</span></td>';
				}

				$email .= '</tr>';
			}

			$email .= '</tbody>';
			$email .= '</table>';

			$user = sisplet_query("SELECT email FROM users WHERE id='" . $ucitelj->user_id . "'", "obj");

			//Zaključek emaila
			// V kolikor se emailpošlje samo učiteljem potem se skrije možnost za dostop učiteljem
        $onemogocenDostopUcitelju =  (new HierarhijaQuery())->getDeleteHierarhijaOptions($anketa, 'onemogoci_dostop_uciteljem', NULL, NULL, FALSE);


        if (is_null($onemogocenDostopUcitelju) && is_null($koda_za_resevanje_ankete)) {
				$email .= '<p>' . $lang['srv_hierarchy_teacher_email_4'] . '<a href="' . $site_url . '" target="_blank">' . $site_url . '</a>' . $lang['srv_hierarchy_teacher_email_5'];
				$email .= '»' . $user->email . '«' . $lang['srv_hierarchy_teacher_email_6'] . '</p>';
			}

            // Podpis
            $signature = Common::getEmailSignature();
			$email .= $signature;

			// Zaključek emaila
			$email .= '</body></html>';


			// Pošljemo email
			try {
				$MA = new MailAdapter($anketa, $type='invitation');
				$MA->addRecipients($user->email);
				$MA->sendMail(stripslashes($email), $subject);
			} catch (Exception $e) {
				echo "Email za hierarhijo ni bil poslan: " . $e;
				error_log("Email za hierarhijo ni bil poslan: " . $e);
			}

		}
	}

	/**
	 * Prikažemo podatke o hierarhiji pri izpolnjevanju
	 *
	 * @param array $get - pridobimo vse get parametre od respondenta
	 *
	 * @return eho html
	 */
	public static function displayPodatkeOhierarhijiZaRespondente($get = [], $only_hierarhija = FALSE)
	{
		global $lang;
		$izpis = '';

		if (!$only_hierarhija) {
			$izpis .= '<div class="hierarhija-naslov-uvod">';
			$izpis .= $lang['srv_hierarchy_main'];
		}

		if (empty($get) || sizeof($get) == 0) {
			return NULL;
		}

		// Pridobimo ime glede na izbiro
		$sifrant = [];
		foreach ($get as $key => $param) {
			if (preg_match('/nivo(\d+)/', $key, $match)) {
				$sql = sisplet_query("SELECT naslov FROM srv_vrednost WHERE id='" . $param . "'", 'obj');
				$sifrant[$match[1]] = $sql->naslov;
			}
		}

		// Sortiramo po nivojih, da je vedno prvi najprej
		ksort($sifrant);
		$izpis .= '<b>';
		foreach ($sifrant as $key => $sifra) {
			$izpis .= ($key > 1 ? ' - ' : NULL) . $sifra;
		}
		$izpis .= '</b>';

		if (!$only_hierarhija) {
			$izpis .= '</div>';
		}

		return $izpis;
	}

	/**
	 * Iščemo v vrednost v 2 dimenzionalnem polju
	 * return $row/null
	 */
	public static function iskanjeArray($id, $array, $keyValue = 'id')
	{
		foreach ($array as $key => $value) {
			if ($value[$keyValue] == $id) {
				return $value;
			}
		}

		return NULL;
	}


	/**********   SUPERADMIN HIERARHIJA END ***********/

	/**
	 * Izvoz šifrantov iz trenutno aktivne hierahije
	 */
	public function izvozSifrantov()
	{
		$ravni = (new HierarhijaOnlyQuery())->getSifrantiRavni($this->anketa);

		// V kolikor nimamo šifrantov potem ne moremo nič izvažati
		if (is_null($ravni)) {
			return redirect('index.php?anketa=' . $this->anketa . '&a=hierarhija_superadmin&m=uredi-sifrante');
		}


		$csv_polje = NULL;
		while ($row = $ravni->fetch_object()) {
			$csv_polje[] = [$row->level, $row->raven, $row->sifranti];
		}

		return Export::init()->csv('Hierarhija_izvoz', $csv_polje);
	}

	/**
	 *  Možnost uvoza hierarhije šifrantov
	 *
	 * @return html page
	 */
	public function hierarhijaSuperadminUvoz()
	{
		$aktivna = $this->preveriCeJeAktivirana();

		#Shranimo CSV datoteko in naredimo strukturo hierarhije
		if ($_GET['t'] == 'hierarhija-uvoz' && $aktivna) {
			//preverimo, če je CSV format
			if (FALSE === array_search($_FILES['uvozi-hierarhijo']['type'], [
					'csv' => 'text/csv',
				], TRUE)) {
				// V kolikor datoteka za uvoz ni v pravem formatu samo vrnemo na prvotno stran
				return redirect('index.php?anketa=' . $this->anketa . '&a=hierarhija_superadmin&m=uredi-sifrante');
			}

			if (($datoteka = fopen($_FILES['uvozi-hierarhijo']['tmp_name'], "r")) !== FALSE) {

				//CSV preberemo in zapišemo v polje
				while (($data = fgetcsv($datoteka, 10000, ",")) !== FALSE) {
					$uvozi_hierarhijo[] = $data;
				}

				$ravni = [];
				$sifrant = [];
				foreach ($uvozi_hierarhijo as $uvoz) {
					//pridobimo samo unikatne nivoje in imena nivojev
					if (!$this->in_mul_array($uvoz[1], $ravni)) {
						$ravni[] = $uvoz;
					}

					//Pridobimo vse šifrante samo vranostno preverimo, če bi se slučajno kak šifrant dvakrat ponovil
					if (!$this->in_mul_array($uvoz[2], $sifrant)) {
						$sifrant[] = $uvoz;
					}
				}

				// preden vnesemo novo hierarhijo izbrišemo že obstoječo
				sisplet_query("DELETE FROM srv_hierarhija_ravni WHERE anketa_id='" . $this->anketa . "'");

				//vpisemo vse ravni
				foreach ($ravni as $raven) {
					// Vpišem, samo če je prvi element polja številka, ker gre za številko ravni
					if (!empty($raven[0]) && is_numeric($raven[0])) {
						sisplet_query("INSERT INTO srv_hierarhija_ravni (anketa_id, user_id, level, ime) VALUES ('$this->anketa', '$this->user', '$raven[0]', '$raven[1]')");
						$raven_id = mysqli_insert_id($GLOBALS['connect_db']);

						//vpišemo vse šifre za sledečo raven
						foreach ($sifrant as $sifra) {
							if ($raven[0] == $sifra[0]) {
								sisplet_query("INSERT INTO srv_hierarhija_sifranti (hierarhija_ravni_id, ime) VALUES ('" . $raven_id . "', '" . $sifra[2] . "')");
							}
						}
					}
				}

				fclose($datoteka);
			}

			return redirect('index.php?anketa=' . $this->anketa . '&a=hierarhija_superadmin&m=uredi-sifrante');
		}
	}

	/**
	 * Preveri, če se spremenljivka nahaja v večdimenzionalnem polju
	 *
	 * @param string $value
	 * @param array $array
	 *
	 * @return boolean
	 */

	public function in_mul_array($value, $array)
	{
		foreach ($array as $row) {
			if (in_array($value, $row)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 *  Možnost nalaganja datoteke, tudi ko je hierarhija aktivna
	 */
	public function hierarhijaSuperadminUploadLogo()
	{
		global $site_path;

		// tracking - beleženje sprememb
		TrackingClass::update($this->anketa, '20');

		$finfo = new finfo(FILEINFO_MIME_TYPE);
		if (FALSE === $ext = array_search($finfo->file($_FILES['logo']['tmp_name']), [
				'jpg' => 'image/jpeg',
				'png' => 'image/png',
				'gif' => 'image/gif',
			], TRUE)) {
			throw new RuntimeException('Datoteka ni v pravem formatu.');
		}

		$shrani_id = (!empty($_POST['id']) ? $_POST['id'] : NULL);
		$path = $site_path . 'admin/survey/modules/mod_hierarhija/porocila/logo/';

		// Predhodno datoteko pobrišemo
		self::brisiLogo($this->anketa, $shrani_id);


		$logo_ime = time() . '_' . slug($_FILES['logo']['name'], '_');
		if (!move_uploaded_file($_FILES['logo']['tmp_name'], sprintf($path . $logo_ime, sha1_file($_FILES['logo']['tmp_name']), $ext))) {
			throw new RuntimeException('Ne morem premakniti datoteke.');
		}


		$shrani_id = (!empty($_POST['id']) ? $_POST['id'] : NULL);

		sisplet_query("UPDATE srv_hierarhija_shrani SET logo='" . $logo_ime . "' WHERE id='" . $shrani_id . "'  AND anketa_id='" . $this->anketa . "'");

		return redirect('index.php?anketa=' . $this->anketa . '&a=hierarhija_superadmin&m=uredi-sifrante');
	}

	public static function brisiLogo($anketa, $id = NULL)
	{
		global $site_path;

		if (is_null($id)) {
			$id = (!empty($_POST['id']) ? $_POST['id'] : NULL);
		}

		$old_logo_name = sisplet_query("SELECT logo FROM srv_hierarhija_shrani WHERE id='" . $id . "'  AND anketa_id='" . $anketa . "'", "obj")->logo;

		$datoteka_za_izbris = NULL;

		if (!empty($old_logo_name)) {
			$datoteka_za_izbris = $site_path . 'admin/survey/modules/mod_hierarhija/porocila/logo/' . $old_logo_name;
		}

		if (file_exists($datoteka_za_izbris)) {
			unlink($datoteka_za_izbris);
		}

		sisplet_query("UPDATE srv_hierarhija_shrani SET logo='' WHERE id='" . $id . "'  AND anketa_id='" . $anketa . "'");
	}

	/**
	 * Uporabniko prikažemo opcijo za aktiviranje ankete in hierarhije
	 *
	 * V kolikor anketa še ni bila aktivirana potem ima uporabnik tudi možnost
	 * izklopiti hierarhijo
	 *
	 * @return
	 */
	public function aktivacijaHierarhijeInAnkete()
	{
		// Preveri če je kak uporabnik upravičen do evalvacije
		$st_uporabnikov_upravicenih_do_evalvacije = (new HierarhijaOnlyQuery())->queryStrukturaUsers($this->anketa, ' AND hs.level=(SELECT MAX(level) FROM srv_hierarhija_struktura WHERE anketa_id=' . $this->anketa . ') GROUP BY users.id');
		$st_uporabnikov_upravicenih_do_evalvacije = mysqli_num_rows($st_uporabnikov_upravicenih_do_evalvacije);

		if (SurveyInfo::getSurveyModules('hierarhija') == 1) {
			// Aktivacija ankete, ki tudi aktivira hierarhij
			echo '<div class="okvircek">';
			echo '<h2>' . $this->lang['srv_hierarchy_activation_link'];
			echo ' <a href="#" class="surveycomment" title="Dodaj komentar k hierarhiji" style="float:right; margin-top: -5px; margin-left: 10px;"> 
                            <span class="faicon inline_comment"></span>
                   </a>';
			echo '<span onclick="previewMail(\'1\')"><i class="fa fa-envelope-o block right link" aria-hidden="true"></i></span></h2>';
			echo $this->lang['srv_hierarhy_activation_text'];

			if ($st_uporabnikov_upravicenih_do_evalvacije) {
				echo '<div style="padding: 15px 0 20px;">';
				echo '<b>Ob aktiviciji ankete bodo upoštevane naslednje nastavitve:</b><br />';
				echo '<table>';
				echo '<tbody>';
				$nastavitve = [
					'srv_hierarchy_code_for_teacher' => 'ne_poslji_kodo_ucitelju',
					'srv_hierarchy_code_for_students' => 'ne_poslji_kode_ucencem',
					'srv_hierarchy_code_teacher_has_access' => 'onemogoci_dostop_uciteljem',
				];
				foreach ($nastavitve as $prevod => $nastavitev) {
					echo '<tr>';
					echo '<td style="width: 180px;">' . $this->lang[$prevod] . '</td>';
					echo '<td><input type="radio" name="' . $nastavitev . '" id="' . $nastavitev . '-0" value="0" onclick="posodobiPosiljanjeKod(\'' . $nastavitev . '\', 0);" ' . (is_null(HierarhijaQuery::getOptions($this->anketa, $nastavitev)) ? 'checked="checked"' : NULL) . '> 
                                  <label for="' . $nastavitev . '-0">' . $this->lang['srv_hierarchy_yes'] . '</label>';
					echo '<input type="radio" name="' . $nastavitev . '" id="' . $nastavitev . '-1" value="1" onclick="posodobiPosiljanjeKod(\'' . $nastavitev . '\', 1);" ' . (HierarhijaQuery::getOptions($this->anketa, $nastavitev) == 1 ? 'checked="checked"' : NULL) . '> 
                                  <label for="' . $nastavitev . '-1">' . $this->lang['srv_hierarchy_no'] . '</label></td>';
					echo '</tr>';
				}
				echo '</tbody>';
				echo '</table>';

				echo '<br />';
				echo $this->lang['srv_hierarchy_code_text_bottom'];
				echo '</div>';
				echo '<button type="button" class="btn btn-moder" onclick="anketa_active(\'' . $this->anketa . '\',\'0\', \'\', \'1\');  return false;">Aktiviraj hierarhijo in anketo</button>';
			} else {
				echo '<div class="error-email"><span class="faicon warning icon-orange"></span> V bazi ni dodanega nobenega učitelja, zato aktivacija ni mogoča!</div>';
			}

			echo '</div>';

			// izklop ankete
			echo '<div class="okvircek" style="margin-top: 30px;">';
			echo '<h2>' . $this->lang['srv_hierarchy_turnoff'] . '</h2>';
			echo $this->lang['srv_hierarhy_turnoff_text'];
			echo '<div>';
			echo '<a id="h-navbar-link" 
                                class="no-img side-right btn btn-moder  
                                 ref="#" 
                                 title="' . $this->lang['srv_hierarchy_turnoff'] . '" 
                                 onclick="toggleAdvancedModule(\'hierarhija\', 1);">';
			echo $this->lang['srv_hierarchy_turnoff'] . '</a>';
			echo '</div>';
			echo '</div>';

		} else {
			// Aktivirana anketa in hierarhija
			echo '<div class="okvircek">';
			echo '<h2>' . $this->lang['srv_hierarchy_active_hierarchy_and_survey'] . '</h2>';
			echo $this->lang['srv_hierarchy_active_hierarchy_and_survey_text'];

			$row = SurveyInfo::getInstance()->getSurveyRow();
			echo '<button type="button" class="btn btn-moder" onclick="anketa_active(\'' . $this->anketa . '\',\'' . $row['active'] . '\'); return false;">' . ($row['active'] ? $this->lang['srv_hierarchy_deactivate_survey'] : $this->lang['srv_hierarchy_activate_survey']) . '</button>';
			echo '</div>';

			// Podatki o aktivaciji hierarhije
			$hierarhija_options = new HierarhijaQuery();
			$cas_aktivacije = $hierarhija_options->getDeleteHierarhijaOptions($this->anketa, 'cas_aktivacije_hierarhije', NULL, NULL, FALSE);
			$uporabnik_aktivacije = $hierarhija_options->getDeleteHierarhijaOptions($this->anketa, 'uporabnik_aktiviral_hierarhijo', NULL, NULL, FALSE);
			$uporabnik = HierarhijaQuery::getUserSurvey($uporabnik_aktivacije);

			echo '<div class="okvircek" style="margin-top: 30px;">';
			echo '<h2>' . $this->lang['srv_hierarchy_active_information_user'] . ' <span onclick="previewMail(\'1\')"><i class="fa fa-envelope-o block right link" aria-hidden="true"></i></span></h2>';
			echo '<h3>Čas aktivacije: <b>' . $cas_aktivacije . '</b></h3>';
			echo '<h3>Aktivnost evalvacije: <b> od ' . date('d.m.Y', strtotime($row['starts'])) . ' do ' . date('d.m.Y', strtotime($row['expire'])) . '</b><a href="index.php?anketa=' . $row['id'] . '&a=trajanje"><span class="faicon edit"></span></a></h3>';
			echo '<h3>Hierarhijo je aktiviral uporabnik: <b>' . $uporabnik->name . ' ' . $uporabnik->surname . '</b> (' . $uporabnik->email . ')</h3>';

			if (HierarhijaQuery::getOptions($this->anketa, 'onemogoci_dostop_uciteljem') == 1) {
				echo '<h3>' . $this->lang['srv_hierarchy_teacher_can_not_access'] . '</h3>';
			}

			echo '<h3>' . HierarhijaHelper::textGledeNaOpcije($this->anketa, 'srv_hierarchy_email_code') . '</h3>';

			$users_upravicen_do_evalvacije = (new HierarhijaOnlyQuery())->queryStrukturaUsers($this->anketa, ' AND hs.level=(SELECT MAX(level) FROM srv_hierarhija_struktura WHERE anketa_id=' . $this->anketa . ') GROUP BY users.id');
			echo '<ul style="list-style: initial;max-height: 500px; overflow: auto;">';
			while ($uporabnik = $users_upravicen_do_evalvacije->fetch_object()) {
				echo '<li>' . $uporabnik->email . '</li>';
			}
			echo '</ul>';

			// Obvesti učitelje, če niso bili obveščeni
			$obvesti_Samo_ucitelje = HierarhijaQuery::getOptions($this->anketa, 'obvesti_samo_ucitelje');
			if (!is_null($obvesti_Samo_ucitelje) && $obvesti_Samo_ucitelje == 0) {
				echo '<div id="obvesti-samo-ucitelje" style="padding-top: 15px;"><button class="btn btn-moder" onclick="obvestiUciteljeZaResevanjeAnkete()">Pošlji obvestilo učiteljem s kodo za reševanje</button></div>';
			}

			echo '</div>';

			// Obveščanje managerjev
			$managerji_ankete = (new HierarhijaOnlyQuery())->queryStrukturaUsers($this->anketa, ' AND hs.level<(SELECT MAX(level) FROM srv_hierarhija_struktura WHERE anketa_id=' . $this->anketa . ')');
			if (mysqli_num_rows($managerji_ankete) > 0) {
				echo '<div class="okvircek"  style="margin-top: 30px;" id="vue-custom">';
				echo '<h2>' . $this->lang['srv_hierarchy_active_information_about_manager'] . ' <span onclick="previewMail(\'2\')"><i class="fa fa-envelope-o block right link" aria-hidden="true"></i></span></h2>';
				echo '<p>' . $this->lang['srv_hierarchy_active_information_about_manager_text'] . '</p>';

				echo '<form action="#" method="post">';
				echo '<div v-if="managerOznaciVse" v-on:click="managerZamenjajOznaci" class="link oznaci"><i class="fa fa-check-square-o" aria-hidden="true"></i> - označi vse</div>';
				echo '<div v-else v-on:click="managerZamenjajOznaci" class="link oznaci"><i class="fa fa-square-o" aria-hidden="true"></i> - označi nobenega</div>';

				echo '<ul style="padding: 4px;">';
				while ($manager = $managerji_ankete->fetch_object()) {
					echo '<li style="padding: 2px 0;"><input type="checkbox" v-model="!managerOznaciVse" name="manager" value="' . $manager->user_id . '" id="manager-' . $manager->id . $manager->user_id . '"/><label  for="manager-' . $manager->id . $manager->user_id . '">' . $manager->level . '.nivo: ' . $manager->email . '</label></li>';
				}
				echo '</ul>';

				echo '<button type="submit" class="btn btn-moder" v-on:click="emailObvestiloZaManagerje()"; return false;">' . $this->lang['srv_hierarchy_submit'] . '</button>';
				echo '</form>';
				echo '</div>';
			}
		}

	}

	/**
	 * Uporabniko prikažemo opcijo za kopiranje ankete s šifranti in strukturo
	 */
	public function kopiranjeHierarhijeInAnkete()
	{

		echo '<div class="okvircek">';
		echo '<h2>' . $this->lang['srv_hierarchy_copy_link'] . '</h2>';
		echo $this->lang['srv_hierarhy_copy_text'];
		echo '<button type="button" class="btn btn-moder" onclick="anketa_copy_top(\'' . $this->anketa . '\', \'1\');  return false;">Kopiraj anketo skupaj s strukturo uporabnikov</button>';
		echo '<br /><br />';
		echo $this->lang['srv_hierarhy_copy_text_2'];
		echo '<button type="button" class="btn btn-moder" onclick="anketa_copy_top(\'' . $this->anketa . '\');  return false;">Kopiraj anketo</button>';
		echo '</div>';

	}

	/**
	 * Prikaz in urejanje hierarhije
	 *
	 * @return html page
	 */
	public function displayHierarhijaUporabniki()
	{
		SurveySetting::getInstance()->Init($this->anketa);
		$row = SurveyInfo::getInstance()
		                 ->getSurveyRow(); //("SELECT * FROM srv_anketa WHERE id='$this->anketa'")

		$max_st_nivojev = (new HierarhijaOnlyQuery())->getSifrantiRavni($this->anketa, ', MAX(level) AS max', NULL);

		//preverimo število nivojev v kolikor jih ni potem nimamo še podatka o vnesenih šifrantih
		if (!empty($max_st_nivojev) && !is_null($max_st_nivojev = $max_st_nivojev->fetch_object()->max) && SurveyInfo::getSurveyModules('hierarhija') == 1) {
			// Pridobimo ime hierarhije
			$aktivna_hierarhija_ime = (new HierarhijaQuery())->getDeleteHierarhijaOptions($this->anketa, 'aktivna_hierarhija_ime', NULL, NULL, FALSE);

			echo '<h2>Izgradnja hierarhije <span class="oranzna">' . (!empty($aktivna_hierarhija_ime) ? $aktivna_hierarhija_ime : '') . '</span> za anketo: ' . $row['naslov'] . '</h2>';
			echo '<span>Ob aktiviranju bodo uporabniki na najnižjem nivoju prejeli kodo/šifro</span>';

			//vnosni obrazec za izgradnjo hierarhije
			echo '<div class="izgradnja_hierarhije">';

			//pravice za gradnjo hierarhije v kolikor uporabnik ni super admin (type 5 ali več) ima tyle manjši kot 5
			if ($this->hierarhija_type > 4) {

				$sql = HierarhijaOnlyQuery::queryStrukturaUsersLevel($this->anketa, $this->user, 'ASC');
				//pridobimo največji nivo uporabnika ter id-je strukture
				while ($struktura = $sql->fetch_object()) {
					## pridobimo največji nivo uporabnika ter id-je strukture za posamezen  vpis
					if (!isset($level) || $struktura->level < $level) {
						$level = $struktura->level;
					}

					$struktura_nivo[] = $struktura->parent_id;
					$struktura_nivo[] = $struktura->struktura_id;
				}

				$struktura_parent = (new HierarhijaOnlyQuery())->queryStruktura($this->anketa, NULL, NULL, 'id DESC');
				while ($obj = $struktura_parent->fetch_object()) {
					//v polje vnesemo samo id strukture, ki je višja od trenutnega nivoja uporabnika
					if (isset($obj) && in_array($obj->id, $struktura_nivo)) {
						$struktura_nivo[] = $obj->parent_id; //tu povnimo parent_id, da lahko potem poiščemo celotno strukturo
						$struktura_sifrant_id[] = $obj->sifrant_id; // narredimo polje z vsemi ID, sifrantov, ki so že vpisani za hierarhijo
					}
				}

			}
			$results = (new HierarhijaQuery())->getSifrantAdmin($this->anketa);

			if (!is_null($results)) {
				if (isset($level)) {
					$this->vpisHierarhijeAdmin($results, $level, $struktura_sifrant_id);
				} else {
					$this->vpisHierarhijeAdmin($results);
				}
			}

			echo '</div>';

			//prikaži JS Tree s trenutno hierarhijo
			$this->jsTreePrikazHierarhije();
		} elseif (!empty($max_st_nivojev) && SurveyInfo::getSurveyModules('hierarhija') == 2) {
			echo '<h3>' . $this->lang['srv_hierarchy_active_text'] . '</h3>';
			$this->jsTreePrikazHierarhije();
		} else {
			echo '<h3>' . $this->lang['srv_hierarchy_nothing'] . '</h3>';
		}
	}

	/**
	 * Nariše drevesno strukturo hierarhi
	 *
	 * @return HTML view
	 */
	public function jsTreePrikazHierarhije()
	{

		$hierarhija = (new HierarhijaOnlyQuery())->queryStruktura($this->anketa, NULL, ' AND parent_id IS NULL')
		                                         ->fetch_object();

		echo '<div id="hierarhija-jstree-ime">';
		if (!is_null($hierarhija->ravni_ime)) {
			echo '<h2>Hierarhija</h2>';
			echo '<b>' . $hierarhija->ravni_ime;
			if ($this->hierarhija_type > 4) {
				echo ' - ' . $hierarhija->sifrant_ime;
			}
			echo ': </b>';
		}
		echo '</div>';

		//        LOAD jsTree na ta element
		echo '<div id="admin_hierarhija_jstree"></div>';

		echo '<script type="text/javascript" src="modules/mod_hierarhija/js/vendor/jstree.min.js"></script>';
		echo '<script>jstree_json_data(' . $this->anketa . ');</script>';
	}

	/**
	 * Gradnja uporabnikov/hierarhije, kjer lahko uporabnik izbira kako želi
	 * imeti prikazana podatke
	 */
	public function izberiDodajanjeUporabnikovNaHierarhijo()
	{
		global $site_url;

		// za vse ostalo je ure uredi uporabnike - M_UREDI_UPORABNIKE
		SurveySetting::getInstance()->Init($this->anketa);
		$row = SurveyInfo::getInstance()->getSurveyRow();
		$hierarchy_status = SurveyInfo::getSurveyModules('hierarhija');

		$max_st_nivojev = (new HierarhijaOnlyQuery())->getSifrantiRavni($this->anketa, ', MAX(level) AS max', NULL);

		//preverimo število nivojev v kolikor jih ni potem nimamo še podatka o vnesenih šifrantih
		if (!empty($max_st_nivojev) && !is_null($max_st_nivojev = $max_st_nivojev->fetch_object()->max) && $hierarchy_status == 1) {

			// Preverimo, če so vpisani šifranti, drugače preusmerimo na vpis šifrantov
			$sql = (new HierarhijaOnlyQuery())->getSifrantiRavni($this->anketa); // pridobimo vse nivoje in šifre za vpis uporabnikov
			if ($sql->num_rows > 0) {
				while ($obj = $sql->fetch_object()) {
					if (empty($obj->sifranti)) {
						return redirect($site_url . 'admin/survey/index.php?anketa=' . $this->anketa . '&a=' . A_HIERARHIJA_SUPERADMIN . '&m=' . M_ADMIN_UREDI_SIFRANTE . '&e=null');
					}
				}
			}

			// Preverimo na katerem nivoju se nahaja uporabnik
			$uporabnik_level = HierarhijaOnlyQuery::queryStrukturaUsersLevel($this->anketa, $this->user, 'ASC')
			                                      ->fetch_object()->level;

			if ($this->hierarhija_type < 4 || $uporabnik_level != $max_st_nivojev) {
				// Pridobimo ime hierarhije
				$aktivna_hierarhija_ime = (new HierarhijaQuery())->getDeleteHierarhijaOptions($this->anketa, 'aktivna_hierarhija_ime', NULL, NULL, FALSE);

				echo '<h2>Izgradnja hierarhije <span class="oranzna">' . (!empty($aktivna_hierarhija_ime) ? $aktivna_hierarhija_ime : '') . '</span> za anketo: ' . $row['naslov'] . '</h2>';
				echo '<div class="help-text">';
				echo '<div class="srv_hierarchy_user_help">';
				echo $this->lang['srv_hierarchy_user_help_top_1'];
				echo ' Vse uporabnike lahko uvozite tukaj <i class="fa fa-lg modra click fa-user-plus" onclick="uvoziUporabnike()" aria-hidden="true"></i>';
				echo '<br/><br/>' . $this->lang['srv_hierarchy_user_help_top_2'];
				echo '</div>';
				echo '<div class="srv_hierarchy_user_help_sifrant_vnesen" style="display: none;">' . $this->lang['srv_hierarchy_user_help_sifrant_vnesen_1'] . ' Vse uporabnike lahko uvozite tukaj <i class="fa fa-lg modra click fa-user-plus" onclick="uvoziUporabnike()" aria-hidden="true"></i><br /><br />' . $this->lang['srv_hierarchy_user_help_sifrant_vnesen_2'] . '</div>';
				echo '</div>';

				//vnosni obrazec za izgradnjo hierarhije
				echo '<div class="izgradnja_hierarhije">';

				$results = (new HierarhijaQuery())->getSifrantAdmin($this->anketa);

				if (!is_null($results)) {

					// V kolikor je postavvljena spremenljivka $level, potem ni superadmin, ampak uporabnik na določenem nivoju
					if ($this->hierarhija_type > 4) {
						$this->vpisHierarhijeUporabnikTabela($results);
					} else {
						$this->vpisHierarhijeAdminTabela($results);
					}

				}

				echo '</div>';
			}

			//prikaži JS Tree s trenutno hierarhijo
			$this->jsTreePrikazHierarhije();
		} elseif (!empty($max_st_nivojev) && $hierarchy_status == 2) {
			echo '<h3>' . $this->lang['srv_hierarchy_active_text'] . '</h3>';

			if ($this->hierarhija_type < 5) {
				$results = (new HierarhijaQuery())->getSifrantAdmin($this->anketa);

				// Prikažemo samo datatables
				echo '<div id="vue-gradnja-hierarhije">';
				echo '<div style="padding-top:26px;clear: both;display: block;">';
				echo '<h2>Prikaz zgrajene hierarhije:</h2>';
				echo '<div id="secondNavigation_links">
                <a href="#" class="srv_ico" id="hover_export_icon" title="Izvoz v"><span class="faicon export" deluminate_imagetype="png"></span> Izvozi strukturo uporabnikov</a>
                <div id="hover_export" style="display: none;">
                    <a href="index.php?anketa=' . $this->anketa . '&a=' . ($this->hierarhija_type < 5 ? A_HIERARHIJA_SUPERADMIN : A_HIERARHIJA) . '&m=uredi-uporabnike&izvoz=1" class="srv_ico" title="CSV izvoz uporabnikov za analizo">
                         Iz tabele
                    </a>                
                    <a href="index.php?anketa=' . $this->anketa . '&a=' . ($this->hierarhija_type < 5 ? A_HIERARHIJA_SUPERADMIN : A_HIERARHIJA) . '&m=uredi-uporabnike&izvoz=struktura-analiz&n=1" class="srv_ico" title="Izvozi v excel">
                        Za združevanje s podatki ankete
                    </a>
                      <a href="index.php?anketa=' . $this->anketa . '&a=' . ($this->hierarhija_type < 5 ? A_HIERARHIJA_SUPERADMIN : A_HIERARHIJA) . '&m=uredi-uporabnike&izvoz=struktura-analiz" class="srv_ico" title="Izvozi v excel">
                        Za združevanje z imenskimi vnosi šifrantov
                    </a>
                </div>
               </div>';

//				echo '<div class="uporabniki-ikona-tabela"
//                        title="CSV izvoz uporabnikov"
//                        >
//                        <a href="index.php?anketa=' . $this->anketa . '&a=' . ($this->hierarhija_type < 5 ? A_HIERARHIJA_SUPERADMIN : A_HIERARHIJA) . '&m=uredi-uporabnike&izvoz=1" class="btn btn-moder">Izvoz uporabnikov</a>
//                  </div>';
//				echo '<div class="uporabniki-ikona-tabela"
//                        title="CSV izvoz uporabnikov za analizo"
//                        style="padding-right: 20px;"
//                        >
//                        <a href="index.php?anketa=' . $this->anketa . '&a=' . ($this->hierarhija_type < 5 ? A_HIERARHIJA_SUPERADMIN : A_HIERARHIJA) . '&m=uredi-uporabnike&izvoz=struktura-analiz" class="btn btn-moder">Struktura uporabnikov za analizo</a>
//                  </div>';
				echo '<table id="vpis-sifrantov-admin-tabela" class="tabela-obroba custom-datatables">';
				echo '<thead>';
				echo ' <tr>';
				foreach ($results['nivoji'] as $key => $nivo) {
					echo '<th style="text-align: left;">' . $nivo['level'] . '.nivo: ' . $nivo['ime'] . '</th>';
				}
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				echo '<tbody>';
				echo '</table>';
				echo '</div>';
				echo '</div>';
			}

			$this->jsTreePrikazHierarhije();
		} else {
			echo '<h3>' . $this->lang['srv_hierarchy_nothing'] . '</h3>';
		}
	}

	/**
	 * Izris forme za gradnjo hierarhije uporabnik na določenem nivoju
	 *
	 * @param array $results
	 *
	 * @return echo html
	 */
	private function vpisHierarhijeUporabnikTabela($results)
	{
		echo '<div id="vue-gradnja-hierarhije">';

		// Kadar nimamo vpisanih šifrantov
		echo '<div v-if="podatki[0].sifranti[0].sifrant == null" style="padding:10px 0">';
		echo $this->lang['srv_hierarchy_empty_drop_downs'];
		echo '</div>';

		echo '<div class="vpis-sifrantov" v-else>';
		echo '<table class="tabela-obroba tabela-vpis-sifrantov">';

		echo '<thead>';
		echo '<tr>';
		foreach ($results['nivoji'] as $key => $nivo) {
			$array_key = array_keys($results['nivoji']);
			if ($key == end($array_key)) {
				echo '<th style="border-right: none;">' . $nivo['level'] . '. nivo - ' . $nivo['ime'] . '</th>';
			} else {
				echo '<th>' . $nivo['level'] . '. nivo - ' . $nivo['ime'] . '</th>';
			}
		}
		echo '<th style="border-left: none;">Evalviranec</th>';
		echo '</tr>';
		echo '</thead>';

		echo '<tbody>';
		echo '<tr>';

		// Če je uporabnik izbran na določen nivo, potem pred tem naredimo fiksna polja z input disabled
		echo '<td style="width: auto;vertical-align: top;" v-for="struktura in user.struktura">';
		echo '<div style="font-weight: bold;margin: 5px 0;text-align: center;">{{ struktura.ime }}</div>';
		echo '</td>';

		echo '<td v-for="nivo in podatki" v-if="nivo.level > user.uporabnik.level" style="width: auto;vertical-align: top;">';
		//      Prikažemo Select2, samo če je 1 nivo in celotne js oz. spletna stran naložena - v kolikor je počasna povezava potem nekaj časa potrebuje, da naloži tudi select2
		echo '<div v-show="pageLoadComplete && (izbran.strukturaId[nivo.level -1] > 0 || izbran.sifrant[nivo.level-1] > 0)">';
		echo '<div class="h-select2">   
                    <select class="select2" v-select="izbran.sifrant[nivo.level]" data-level="{{ nivo.level }}">
                        <option value="0"> --- </option>
                        <option v-for="s in nivo.sifranti" :value="s.id" >{{ s.sifrant }}</option>
                    </select>                                                                    
                 </div>';
		echo '<div class="h-uporabnik" v-show="izbran.sifrant[nivo.level] > 0" v-on:click="prikaziVnosOseb(nivo.level)">
                        <span v-if="nivo.level == podatki.maxLevel" class="icon user-red"></span>
                       <span v-else class="faicon users icon-as_link"></span> 
                  </div>';
		echo '</div>';

		// Prikažemo že dodane uporabnike in tudi uporabnike samo dodane v virtual dom
		echo '<div class="h-uporabnik-prikazi" v-if="osebe.show[nivo.level] && (nivo.level < podatki.maxLevel)">';
		echo 'Uporabnik/i:';
		echo '<ul>';
		// Seznam uporabnikov, ki so že v bazi in jih samo prikličemo
		echo '<li v-for="uporabnik in izbran.sifrantPodatki[nivo.level].uporabniki">{{ uporabnik.email }} 
                    <span v-if="uporabnik.ime != uporabnik.email">({{ uporabnik.ime }} {{ uporabnik.priimek }})</span>
                    <span class="icon brisi-x" 
                            v-on:click="izbrisiUporabnikaIzBaze(uporabnik.id, $index, nivo.level)"
                            v-if="osebe.nivo < podatki.maxLevel"
                            ></span>
                  </li>';
		// Seznam uporabnikov, ki jih še ni v bazi in so bili na novo dodani
		echo '<li v-for="oseba in osebe.nove[nivo.level]">{{ oseba[0] }} <span v-if="oseba[1]">({{ oseba[1] }} {{ oseba[2] }})</span> <span class="icon brisi-x" v-on:click="izbrisiUporabnika(nivo.level)" ></span></li>';
		echo '</ul>';
		echo '</div>';
		echo '</td>';
		echo '<td style="vertical-align: top;padding: 13px;color:#ffa608;border-left: none;">';
		// Seznam uporabnikov, ki so že v bazi in jih samo prikličemo
		echo '<div v-for="uporabnik in izbran.sifrantPodatki[podatki.maxLevel].uporabniki">{{ uporabnik.email }} 
                        <span v-if="uporabnik.ime != uporabnik.email">({{ uporabnik.ime }} {{ uporabnik.priimek }})</span>  
                       </div>';
		// Seznam uporabnikov, ki jih še ni v bazi in so bili na novo dodani
		echo '<div v-for="oseba in osebe.nove[podatki.maxLevel]">{{ oseba[0] }} <span v-if="oseba[1]">({{ oseba[1] }} {{ oseba[2] }})</span> <span class="icon brisi-x" v-on:click="izbrisiUporabnika(podatki.maxLevel)" style="margin-left: 10px;"></span></div>';
		echo '</td>';
		echo '</tr>';
		echo '<tr id="gumb">
                    <td colspan="' . (sizeof($results['nivoji']) + 1) . '">
                        <button type="button" class="btn btn-moder" v-on:click="submitSifrante()">Potrdi in prenesi</button>  
                     </td>
                  </tr>';
		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		// možnost vpisa osebe za določen nivo
		echo '<div style="padding-top:26px;clear: both;display: block;" v-if="osebe.prikazi">';
		echo '<div class="okvircek">';
		echo '<h2>Vnos oseb za {{ osebe.nivo }}. nivo:</h2>';
		echo '<div>';
		echo $this->lang['srv_hierarchy_add_users'] . '
                                                   <div style="padding:15px 0;">
                                                        <textarea name="emails" style="height:100px; width:100%;" 
                                                                    v-if="osebe.nivo < podatki.maxLevel"
                                                                    v-model="osebe.textarea" 
                                                                    v-on:keyup.enter="preveriPravilnostEmaila()"
                                                        ></textarea>
                                                        <input type="text" 
                                                                 name="emails" 
                                                                 style="height: 16px; width:100%;" 
                                                                 v-else
                                                                v-model="osebe.textarea" 
                                                                v-on:keyup.enter="preveriPravilnostEmaila()"
                                                        />
                                                   </div>
                                                 <div class="h-opozorilo">*Polje email je obvezno polje za zadnji nivo.</div>
                                                 <div v-if="email.opozorilo" style="color:red;font-style:italic;padding: 0 0 10px;"><ul><li v-for="email in email.napake">Elektronski naslov <b>{{ email.naslov }}</b> v vrstici <b>{{ email.vrstica }}</b> ni pravilen.</li></ul></div>
                                              ';
		echo '</div>';
		echo '<button type="button" class="btn btn-moder" v-on:click="vpisOsebNaNivoTextarea()">Vnesi osebe</button>';
		echo '</div>';
		echo '</div>';


		// Prikažemo Datatables rezultate samo za zdanji nivo;
		echo '<div style="padding-top:26px;clear: both;display: block;">';
		echo '<h2>Prikaz zgrajene hierarhije:</h2>';
		echo '<table id="vpis-sifrantov-admin-tabela" class="tabela-obroba">';
		echo '<thead>';
		echo ' <tr>';
		foreach ($results['nivoji'] as $key => $nivo) {
			echo '<th style="text-align: left;">' . $nivo['level'] . '.nivo: ' . $nivo['ime'] . '</th>';
		}
		echo '<th style="width: 120px;"> </th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		echo '<tbody>';
		echo '</table>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Izris forme za gradnjo hierarhije Superadmin
	 *
	 * @param array $results
	 *
	 * @return echo html
	 */
	private function vpisHierarhijeAdminTabela($results)
	{
		global $site_url;

		echo '<div id="vue-gradnja-hierarhije">';
		// Kadar nimamo vpisanih šifrantov
		echo '<div v-if="podatki[0].sifranti[0].sifrant == null" style="padding:10px 0">';
		echo $this->lang['srv_hierarchy_empty_drop_downs'];
		echo '</div>';

		echo '<div class="vpis-sifrantov" v-else><table class="tabela-obroba tabela-vpis-sifrantov">';
		echo '<thead>';
		echo '<tr>';
		foreach ($results['nivoji'] as $key => $nivo) {
			$polje_kljuci = array_keys($results['nivoji']);
			if ($key == end($polje_kljuci)) {
				echo '<th style="border-right: none;">' . $nivo['level'] . '. nivo - ' . $nivo['ime'] . '</th>';
			} else {
				echo '<th>' . $nivo['level'] . '. nivo - ' . $nivo['ime'] . '</th>';
			}
		}
		echo '<th style="border-left: none;">Evalviranec</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		echo '<tr>';
		echo '<td v-for="nivo in podatki" style="width: auto;vertical-align: top; border-right:none;">';
		//      Prikažemo Select2, samo če je 1 nivo in celotne js oz. spletna stran naložena - v kolikor je počasna povezava potem nekaj časa potrebuje, da naloži tudi select2
		echo '<div v-show="prikaziJsKoSeJeCelaSpletnaStranZeNalozila(nivo.level)">';

		echo '<div class="h-select2">               
                         <select class="select2" v-select="izbran.sifrant[nivo.level]" data-level="{{ nivo.level }}">
                            <option value="0"> --- </option>
                            <option v-for="s in nivo.sifranti" :value="s.id">{{ s.sifrant }}</option>
                         </select>  
                    </div>';

		echo '<div class="h-uporabnik" v-show="aliPrikazemIkonoZaDodajanjeUporabnikov(nivo.level)" v-on:click="prikaziVnosOseb(nivo.level)">
                                <span v-if="nivo.level == podatki.maxLevel" class="icon user-red"></span>
                                <span v-else class="faicon users icon-as_link"></span>
                     </div>';

		echo '<div class="h-select2 izberi-uporabnika" v-if="prikaziSelectZaZadnjiNivo(nivo.level)">   
                                           <select class="select2"  id="izbira-uciteljev" v-select="user.selected" v-on:change="vpisemoUporabnikaIzDropDownMenija()">
                                               <option value="0"> --- </option>
                                              <option v-for="user in user.dropdown" :value="user.id">{{ user.label }}</option>
                                           </select>    
                    </div>';

		echo '</div>';


		// Prikažemo že dodane uporabnike in tudi uporabnike samo dodane v virtual dom
		echo '<div class="h-uporabnik-prikazi" v-if="osebe.show[nivo.level] && (nivo.level < podatki.maxLevel)">';
		echo 'Uporabnik/i:';
		echo '<ul>';
		// Seznam uporabnikov, ki so že v bazi in jih samo prikličemo
		echo '<li v-for="uporabnik in izbran.sifrantPodatki[nivo.level].uporabniki">{{ uporabnik.email }} 
                            <span v-if="uporabnik.ime != uporabnik.email">({{ uporabnik.ime }} {{ uporabnik.priimek }})</span>   
                            <span class="icon brisi-x" 
                                  v-on:click="izbrisiUporabnikaIzBaze(uporabnik.id, $index, nivo.level)"
                                  v-if="nivo.level < podatki.maxLevel"
                            ></span>
                           </li>';
		// Seznam uporabnikov, ki jih še ni v bazi in so bili na novo dodani
		echo '<li v-for="oseba in osebe.nove[nivo.level]">{{ oseba[0] }} <span v-if="oseba[1]">({{ oseba[1] }} {{ oseba[2] }})</span> <span class="icon brisi-x" v-on:click="izbrisiUporabnika(nivo.level)" ></span></li>';
		echo '</ul>';
		echo '</div>';
		echo '</td>';
		echo '<td style="vertical-align: top;padding: 13px;color:#ffa608; border-left: none;">';
		echo '<div v-if="izbran.sifrantPodatki[podatki.maxLevel]">';
		// Seznam uporabnikov, ki so že v bazi in jih samo prikličemo
		echo '<div v-for="uporabnik in izbran.sifrantPodatki[podatki.maxLevel].uporabniki">{{ uporabnik.email }} 
                    <span v-if="uporabnik.ime != uporabnik.email">({{ uporabnik.ime }} {{ uporabnik.priimek }})</span>  
                   </div>';
		// Seznam uporabnikov, ki jih še ni v bazi in so bili na novo dodani
		echo '<div v-for="oseba in osebe.nove[podatki.maxLevel]">{{ oseba[0] }} <span v-if="oseba[1]">({{ oseba[1] }} {{ oseba[2] }})</span> <span class="icon brisi-x" v-on:click="izbrisiUporabnika(podatki.maxLevel)" style="margin-left: 10px;"></span></div>';
		echo '</div>';
		echo '</td>';
		echo '</tr>';
		echo '<tr id="gumb">
                <td colspan="' . (sizeof($results['nivoji']) + 1) . '">
                    <button type="button" class="btn btn-moder" v-on:click="submitSifrante()">Potrdi in prenesi</button>  
                 </td>
              </tr>';
		echo '</tbody>';
		echo '</table>';
		echo '<div class="error-email" style="display: none;" v-if="preveriCejeEmailZeVnesenVbazoZaUcitelja(podatki.maxLevel)"><span class="faicon warning icon-orange"></span> Elektronski naslov za zadnji nivo je že vnešen v bazo.</div>';
		echo '</div>';

		// možnost vpisa osebe za določen nivo
		echo '<div style="padding-top:26px;clear: both;display: block;" v-if="osebe.prikazi">';
		echo '<div class="okvircek">';
		echo '<h2 v-if="osebe.nivo < podatki.maxLevel">Vnos oseb za {{ osebe.nivo }}. nivo ​– managerji z vpogledom  v rezultate (?)</h2>';
		echo '<h2 v-else class="oranzna">​Vnos osebe na zadnjem nivoju  - učitelj, ki bo evalviran (?)</h2>';
		echo '<div>';
		echo '<p v-if="osebe.nivo < podatki.maxLevel">' . $this->lang['srv_hierarchy_add_users'] . '</p>';
		echo '<p v-else>' . $this->lang['srv_hierarchy_add_users_last'] . '</p>';
		echo '<div style="padding:15px 0;">';
		echo '<textarea name="emails" style="height:100px; width:100%;" 
                             v-if="osebe.nivo < podatki.maxLevel"
                             v-model="osebe.textarea" 
                             v-on:keyup.enter="preveriPravilnostEmaila()"
                   ></textarea>
                   <input type="text" 
                          name="emails" 
                          style="height: 16px; width:100%;" 
                          v-if="osebe.nivo == podatki.maxLevel"
                          v-model="osebe.textarea" 
                          v-on:keyup.enter="preveriPravilnostEmaila()"
                   />
               </div>
               <div class="h-opozorilo">*Polje email je obvezno polje za zadnji nivo.</div>
               <div v-if="email.opozorilo" style="color:red;font-style:italic;padding: 0 0 10px;"><ul><li v-for="email in email.napake">Elektronski naslov <b>{{ email.naslov }}</b> v vrstici <b>{{ email.vrstica }}</b> ni pravilen.</li></ul></div>';
		echo '</div>';
		echo '<button type="button" class="btn btn-moder" v-on:click="vpisOsebNaNivoTextarea()">
                    <span v-if="osebe.nivo < podatki.maxLevel">Vnos oseb</span>
                    <span v-else>Vnos osebe</span>
                </button>';
		echo '</div>';
		echo '</div>';


		// Prikažemo Datatables rezultate samo za zdanji nivo;
		echo '<div id="div-datatables" style="padding-top:26px;clear: both;display: block;">';
		echo '<h2>Prikaz zgrajene hierarhije:</h2>';
		// Vklopimo prikaz pomoči in števila vnoso uporabnikov
		echo '<div class="help-text" style="width: 100%">';
		$this->prikaziStUporabnikovNaZadnjemNivojuHelp();
		echo '</div>';

		echo '<div class="uporabniki-ikona-tabela" 
                        title="Zamenjaj uporabnika na zadnjem nivoju" 
                        onclick="zamenjajUporabnikaZNovim()"
                        >
                        <div class="btn btn-moder">' . $this->lang['srv_hierarchy_btn_find_and_replace'] . '</div>
                  </div>';
		echo '<div class="uporabniki-ikona-tabela" 
                        title="CSV izvoz uporabnikov"       
                        style="padding: 0 10px;"              
                        >
                        <a href="index.php?anketa=' . $this->anketa . '&a=' . ($this->hierarhija_type < 5 ? A_HIERARHIJA_SUPERADMIN : A_HIERARHIJA) . '&m=uredi-uporabnike&izvoz=1" class="btn btn-moder">Izvoz uporabnikov</a>
                  </div>';
		echo '<table id="vpis-sifrantov-admin-tabela" class="tabela-obroba">';
		echo '<thead>';
		echo ' <tr>';
		foreach ($results['nivoji'] as $key => $nivo) {
			echo '<th style="text-align: left;">' . $nivo['level'] . '.nivo: ' . $nivo['ime'] . '</th>';
		}
		echo '<th style="width: 70px;"> </th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		echo '<tbody>';
		echo '</table>';
		echo '<div style="display:block; margin:25px -20px; padding-bottom:25px; width: 100%;">';
		echo '<button class="btn btn-moder"
                          style="float: right;"                    
                          onclick="opozoriUporabnikaKerNiPotrdilPodatkov(\'index.php?anketa=' . $this->anketa . '&amp;a=' . A_HIERARHIJA_SUPERADMIN . '&amp;m=' . M_ADMIN_AKTIVACIJA . '\')" 
                          title="' . $this->lang['srv_hierarchy_status'] . '">';
		echo $this->lang['next1'] . '</button>';
		echo '</div>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Prikažemo število vseh uporabnikov na zadnjem nivoju, število unikatnih
	 * učiteljev in število predmetov
	 *
	 * @return html
	 */
	private function prikaziStUporabnikovNaZadnjemNivojuHelp()
	{

		$id = (new HierarhijaQuery())->getDeleteHierarhijaOptions($this->anketa, 'srv_hierarhija_shrani_id', NULL, NULL, FALSE);
		// Če ne dobimo ID-ja
		if (empty($id)) {
			return NULL;
		}

		$sql_st_uciteljev = sisplet_query("SELECT st_uciteljev FROM srv_hierarhija_shrani WHERE id='" . $id . "' AND anketa_id='" . $this->anketa . "'", "obj");

		// Prešteje število unikatnih učiteljev
		$unikatni_uporabniki = (new HierarhijaOnlyQuery())->queryStrukturaUsers($this->anketa, ' AND hs.level=(SELECT MAX(level) FROM srv_hierarhija_struktura WHERE anketa_id=' . $this->anketa . ') GROUP BY users.id');
		$st_unikatnih_uporabnikov = mysqli_num_rows($unikatni_uporabniki);

		// Prešteje število unikatnih predmetov
		$unikatni_predmeti = (new HierarhijaOnlyQuery())->queryStrukturaUsers($this->anketa, ' AND hs.level=(SELECT MAX(level) FROM srv_hierarhija_struktura WHERE anketa_id=' . $this->anketa . ') GROUP BY hs.hierarhija_sifranti_id');
		$st_unikatnih_predmetov = mysqli_num_rows($unikatni_predmeti);

		echo $this->lang['srv_hierarchy_user_help_1_1'];
		echo ' ' . $sql_st_uciteljev->st_uciteljev . ' ';
		echo $this->lang['srv_hierarchy_user_help_1_2'];
		echo ' ' . $st_unikatnih_uporabnikov . ' ' . $this->lang['srv_hierarchy_user_help_1_3'] . ' ' . $st_unikatnih_predmetov . ' ' . $this->lang['srv_hierarchy_user_help_1_4'];

	}

	/**
	 * Prikaže tabelo za gradnjo hierarhije uporabnikov
	 */
	public function displayHierarhijaUporabnikiTabela()
	{
		SurveySetting::getInstance()->Init($this->anketa);
		$row = SurveyInfo::getInstance()
		                 ->getSurveyRow(); //("SELECT * FROM srv_anketa WHERE id='$this->anketa'")

		$max_st_nivojev = (new HierarhijaOnlyQuery())->getSifrantiRavni($this->anketa, ', MAX(level) AS max', NULL);


		//preverimo število nivojev v kolikor jih ni potem nimamo še podatka o vnesenih šifrantih
		if (!empty($max_st_nivojev) && !is_null($max_st_nivojev = $max_st_nivojev->fetch_object()->max) && SurveyInfo::getSurveyModules('hierarhija') == 1) {
			// Pridobimo ime hierarhije
			$aktivna_hierarhija_ime = (new HierarhijaQuery())->getDeleteHierarhijaOptions($this->anketa, 'aktivna_hierarhija_ime', NULL, NULL, FALSE);

			echo '<h2>Izgradnja hierarhije <span class="oranzan">' . (!empty($aktivna_hierarhija_ime) ? $aktivna_hierarhija_ime : '') . '</span> za anketo: ' . $row['naslov'] . '</h2>';

			//vnosni obrazec za izgradnjo hierarhije
			echo '<div class="izgradnja_hierarhije">';

			//pravice za gradnjo hierarhije v kolikor uporabnik ni super admin (type večji kot 5)
			if ($this->hierarhija_type > 4) {

				$sql = HierarhijaOnlyQuery::queryStrukturaUsersLevel($this->anketa, $this->user, 'ASC');
				//pridobimo največji nivo uporabnika ter id-je strukture
				while ($struktura = $sql->fetch_object()) {
					## pridobimo največji nivo uporabnika ter id-je strukture za posamezen  vpis
					if (!isset($level) || $struktura->level < $level) {
						$level = $struktura->level;
					}

					$struktura_nivo[] = $struktura->parent_id;
					$struktura_nivo[] = $struktura->struktura_id;
				}

				$struktura_parent = (new HierarhijaOnlyQuery())->queryStruktura($this->anketa, NULL, NULL, 'id DESC');
				while ($obj = $struktura_parent->fetch_object()) {
					//v polje vnesemo samo id strukture, ki je višja od trenutnega nivoja uporabnika
					if (isset($obj) && in_array($obj->id, $struktura_nivo)) {
						$struktura_nivo[] = $obj->parent_id; //tu povnimo parent_id, da lahko potem poiščemo celotno strukturo
						$struktura_sifrant_id[] = $obj->sifrant_id; // narredimo polje z vsemi ID, sifrantov, ki so že vpisani za hierarhijo
					}
				}

			}
			$results = (new HierarhijaQuery())->getSifrantAdmin($this->anketa);

			if (!is_null($results)) {
				//                echo '<form id="h-submit">';
				//                echo '<input type="hidden" value="' . $this->anketa . '" id="anketa_id" name="anketa_id">';
				//
				//                if (isset($level)) {
				//                    $this->vpisHierarhijeTabela($results, $level, $struktura_sifrant_id);
				//                } else {
				//                    $this->vpisHierarhijeTabela($results, null, null, $max_st_nivojev);
				//                }

				//                echo '<form id="h-submit">';
				//                echo '<input type="hidden" value="' . $this->anketa . '" id="anketa_id" name="anketa_id">';
				//
				//                if (isset($level)) {
				//                    $this->vpisHierarhijeAdmin($results, $level, $struktura_sifrant_id);
				//                } else {
				//                    $this->vpisHierarhijeAdmin($results);
				//                }
				//
				//
				//                echo '<div class="h-form-field">
				//                            <label id="check-uporabnik">Dodaj uporabnika na izbran nivo:</label>
				//                             <div class="h-form-options">
				//                                 <input type="checkbox" name="email-check" value="1" id="dovoli-vpis-emaila"/>
				//                            </div>
				//                      </div>';
				//
				//                echo '<div class="h-form-field" id="vpis-emaila">
				//                                  <label>' . $this->lang['srv_hierarchy_add_users'] . '</label>
				//                                  <div class="h-form-options h-email-user">
				//                                     <textarea name="emails"></textarea>
				//                                  </div>
				//                                  <div class="h-opozorilo">*Polje email je obvezno polje za zadnji nivo.</div>
				//                             </div>';
				//
				//                echo '<div class="h-form-field" style="padding-left: 35em; clear: both;">
				//                                  <input type="submit" value="Vnesi">
				//                             </div>';
				//                echo '</form>';

			}


			echo '</div>';

			//prikaži JS Tree s trenutno hierarhijo
			$this->jsTreePrikazHierarhije();
		} elseif (!empty($max_st_nivojev) && SurveyInfo::getSurveyModules('hierarhija') == 2) {
			echo '<h3>' . $this->lang['srv_hierarchy_active_text'] . '</h3>';
			$this->jsTreePrikazHierarhije();
		} else {
			echo '<h3>' . $this->lang['srv_hierarchy_nothing'] . '</h3>';
		}

	}

	public function statistikaHierjearhije()
	{
		if ($this->hierarhija_type < 5) {
			return $this->statistikaAdminHierarhije();
		}

		if ($this->hierarhija_type == 10) {
			return $this->statistikaUcitelj();
		}

		echo $this->lang['srv_hierarchy_only_teachers'];
	}

	/**
	 * Prikaz statisti za vse ankete brez kod, ker administrator nima pravice do
	 * vpolgeda teh kod
	 */

	public function statistikaAdminHierarhije()
	{
		echo '<div id="hierarhija-status">';

		if (SurveyInfo::getSurveyModules('hierarhija') == 2) {
			echo '<div class="status-gumbi">';
			echo '<button class="btn btn-moder" onclick="prikaziUrejanjeSuperkode()">Vključi izdelavo superšifre</button>';
			echo '</div>';
		}

		echo '<div class="tabela-status">';
		echo '<table class="hierarhija-status-admin custom-datatables printElement printTable" id="hierarhija-status-admin">';
		echo '<thead>';
		echo '<tr>
                    <th rowspan="2" class="anl_br anl_ac anl_bck anl_variabla_line anl_bb auto-width" style="width:90px;">Izberi učitelja za generiranje super šifre</th>
                    <th rowspan="2" class="anl_br anl_ac anl_bck anl_variabla_line anl_bb auto-width">Hierarhija</th>
                    <th rowspan="2" class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">Email učitelja</th>
                    <th rowspan="2" class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">Koda za učence</th>
                    <th colspan="3" style="text-align: center;" class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">Status učencev</th>
                    <th colspan="2" style="text-align: center;" class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">Časovni potek učencev</th>';
		echo '<th rowspan="2" class="anl_br anl_ac anl_bck anl_variabla_line anl_bb" style="border-left:2px solid #fa4913 !important;">Koda za učitelja</th>';
		echo '<th rowspan="2" class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">Vnos učitelja (datum)</th>
                   </tr>';
		echo '<tr>
                    <th class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">' . $this->lang['srv_userstatus_5ll'] . '</th>
                    <th class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">' . $this->lang['srv_userstatus_5'] . '</th>
                    <th class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">' . $this->lang['srv_userstatus_6'] . '</th>
                    <th class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">Prvi vnos</th>
                    <th class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">Zadnji vnos</th>
                   </tr>';
		echo '</thead>';
		echo '<tbody>';

		$body = HierarhijaIzvoz::getInstance($this->anketa)->getStatus(TRUE);

		if (is_null($body)) {
			return '';
		}

		foreach ($body as $id_koda => $row) {
			echo '<tr>';
			echo '<td style="text-align: center;">
                    <input type="checkbox" value="' . $id_koda . '" class="koda-za-kosarico" onclick="dodajKodoVKosarico(\'' . $id_koda . '\')"/>
                </td>';

			// Izrišemo vse stolpce v omenjeni vrstici
			foreach ($row as $key => $podatek) {
				if ($key == 0 || $key == 1) {

					echo '<td ' . ($key == 0 ? 'data-hierarhija' : 'data-email') . '="' . $id_koda . '">' . $podatek . '</td>';

				} elseif ($key == 2 || $key == 8) {

					echo '<td style="text-align:center; letter-spacing:1px; ' . ($key == 2 ? 'color:#1e88e5;' : NULL) . ($key == 8 ? 'border-left:2px solid #fa4913 !important;' : NULL) . '">' . $podatek . '</td>';

				} else {

					echo '<td style="text-align:center;">' . $podatek . '</td>';

				}

			}

			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		echo '<div class="analysis_bottom_settings">      
     
                <a href="#" onclick="printElement(\'Status\'); return false;" title="Natisni" class="srv_ico">
                    <span class="faicon print icon-grey_dark_link"></span>
                </a>
                
                 <a href="index.php/?anketa=' . $this->anketa . '&a=' . A_HIERARHIJA_SUPERADMIN . '&m=' . M_HIERARHIJA_STATUS . '&izvoz=status" title="CSV izvoz" class="srv_ico">
                    <span class="sprites xls_large"></span>
                </a>
                
              </div>';

		echo '</div>';

		// košarica s kodami
		echo '<div class="superkode">';
		echo '<div class="kosarica" style="display: none;">';
		echo '<h2>Tranutno izbrane kode:</h2>';
		echo '<ul id="seznamKod"></ul>';
		echo '<button class="btn btn-moder" id="ustvari-superkodo">Ustvari novo super kodo</button>';
		echo '</div>';

		echo '<div class="prikaz-superkod" style="display: none;">
                    <h2>Superkode</h2>
                    <table class="hierarhija-tabela">
                        <thead>
                        <tr>
                         <th class="anl_br anl_ac anl_bck anl_variabla_line anl_bb auto-width">Superkoda</th>
                          <th class="anl_br anl_ac anl_bck anl_variabla_line anl_bb auto-width">Seznam hierarhije, ki jih pokriva</th>                        
                        </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                 </div>';
		echo '</div>';
	}

	/**
	 * Prikaže tabelo s kodami za učitelja in učence skupaj s  s statističnimi
	 * podatki
	 */

	protected function statistikaUcitelj()
	{
		if (!$this->upravicenDoSamoevalvacije()) {
			die();
		}

		echo '<table class="hierarhija-status-ucitelji printElement">';
		echo '<thead>';
		echo '<tr>
                <th rowspan="2" class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">Hierarhija</th>';
		echo '<th rowspan="2" class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">Koda za učence</th>';

		echo '<th rowspan="2" class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">Koda za učitelja</th>
                <th colspan="3" style="text-align: center;" class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">Status učencev</th>
                <th colspan="2" style="text-align: center;" class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">Časovni potek učencev</th>
                <th rowspan="2" class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">Vnos učitelja (datum)</th>
               </tr>';
		echo '<tr>
                <th class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">' . $this->lang['srv_userstatus_5ll'] . '</th>
                <th class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">' . $this->lang['srv_userstatus_5'] . '</th>
                <th class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">' . $this->lang['srv_userstatus_6'] . '</th>
                <th class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">Prvi vnos</th>
                <th class="anl_br anl_ac anl_bck anl_variabla_line anl_bb">Zadnji vnos</th>

               </tr>';
		echo '</thead>';
		echo '<tbody>';

		//v kolikor imamo več URL-jev se pravi za več predmetov potem moramo izpisati sklope za vse predmete
		$url_hierarhija = self::hierarhijaUrl($this->anketa);

		// pridobimo podatke

		foreach ($url_hierarhija as $struktura_id => $url) {
			// pridobimo podatke o rešenih anketah samo za to strukturo
			$cas = $this->pridobiStatisticnePodatke($struktura_id, 'ucenec');
			$cas_ucitelj = $this->pridobiStatisticnePodatke($struktura_id, 'ucitelj');

			$first_insert = NULL;
			$last_insert = NULL;
			$zacel_izpolnjevati = 0;
			$delno_izpolnjena = 0;
			$koncal_anketo = 0;

			if (!is_null($cas)) {
				foreach ($cas as $key => $row) {
					if ($row['cas'] < $first_insert || $key == 0) {
						$first_insert = $row['cas'];
					}

					if ($row['cas'] > $last_insert) {
						$last_insert = $row['cas'];
					}

					// Končal anketo
					if ($row['status'] == 6 && $row['lurker'] == 0) {
						$koncal_anketo++;
					}

					// Delno izpolnjena
					if ($row['status'] == 5 && $row['lurker'] == 0) {
						$delno_izpolnjena++;
					}

					if ($row['lurker'] == 1) {
						$zacel_izpolnjevati++;
					}
				}
			}

			echo '<tr>
                    <td>' . HierarhijaHelper::hierarhijaPrikazNaslovovpriUrlju($this->anketa, $struktura_id) . '</td>';

			echo '<td style="text-align:center;color:#ffa608;">' . strtoupper(HierarhijaOnlyQuery::getKodaRow($this->anketa, $struktura_id)->koda) . '</td>';

			echo '<td style="text-align:center;">' . strtoupper(HierarhijaOnlyQuery::getKodaRow($this->anketa, $struktura_id, 'ucitelj')->koda) . '</td>
                    <td style="text-align:center;">' . (!empty($zacel_izpolnjevati) ? $zacel_izpolnjevati : '/') . '</td>
                    <td style="text-align:center;">' . (!empty($delno_izpolnjena) ? $delno_izpolnjena : '/') . '</td>
                    <td style="text-align:center;">' . (!empty($koncal_anketo) ? $koncal_anketo : '/') . '</td>
                    <td style="text-align:center;">' . (!is_null($first_insert) ? date('d.m.Y, H:i', $first_insert) : '/') . '</td>
                    <td style="text-align:center;">' . (!is_null($last_insert) ? date('d.m.Y, H:i', $last_insert) : '/') . '</td>
                    <td style="text-align:center;">' . ((!is_null($cas_ucitelj['cas']) && $cas_ucitelj['status'] == 6 && $cas_ucitelj['lurker'] == 0) ? date('d.m.Y, H:i', $cas_ucitelj['cas']) : '/') . '</td>';

			// V kolikor je hierarhija aktivna potem prikažemo možnost pregleda analiz
			if ($this->modul['hierarhija'] > 1) {
				echo '<td><a href="index.php?anketa=' . $this->anketa . '&a=' . A_HIERARHIJA . '&m=' . M_ANALIZE . '&s=' . $struktura_id . '" class="btn btn-moder">Poglej analizo</button></td>';
			}
			echo '</tr>';
		}

		echo '</tbody>';
		echo '</table>';
	}

	/**
	 * Preverimo, če je uporabnik upravičen do samoevalvacije - je na zadnjem
	 * nivoju hierarhije
	 *
	 * @return boolean
	 */

	public function upravicenDoSamoevalvacije()
	{

		$max_level = (new HierarhijaOnlyQuery())->getSifrantiRavni($this->anketa, ', MAX(level) AS max', NULL);
		$user_level = HierarhijaOnlyQuery::queryStrukturaUsersLevel($this->anketa, $this->user, 'DESC');

		if (!empty($user_level) && !empty($user_level) && $user_level->fetch_object()->level == $max_level->fetch_object()->max) {
			return TRUE;
		}

		return FALSE;
	}

	/**********   Prikaz in urejanje hierarhije END   **********/

	private function pridobiStatisticnePodatke($struktura_id, $vloga)
	{

		$url = sisplet_query("SELECT url FROM srv_hierarhija_koda WHERE anketa_id='" . $this->anketa . "'  AND hierarhija_struktura_id='" . $struktura_id . "' AND vloga='" . $vloga . "'", "obj")->url;

		// V kolikor anketa še ni bila aktivirana potem vrnemo null, ker nimamo še podatkov o izpolnjevanju
		if (is_null($url)) {
			return NULL;
		}

		parse_str($url, $nivoji);

		$polje_iskanja = NULL;
		foreach ($nivoji as $key => $nivo) {
			if ($key == 'vloga') {
				$polje_iskanja = $nivo;
			} else {
				$polje_iskanja .= ', ' . $nivo;
			}
		}

		$db_table = SurveyInfo::getInstance()->getSurveyArchiveDBString();

		// tukaj pridobimo podatke o anketi za določeno strukturo
		// preverimov prvi in zadnji nivo
		$sql_user = sisplet_query("SELECT time_insert, last_status, lurker FROM srv_data_vrednost" . $db_table . " as sa LEFT JOIN srv_user as us ON (sa.usr_id=us.id) WHERE vre_id IN (" . $polje_iskanja . ") GROUP BY usr_id  HAVING COUNT(usr_id)=" . sizeof($nivoji));
		$cas = [];

		// V kolikor ni zapisov vrnemo prazno
		if ($sql_user->num_rows == 0) {
			return NULL;
		}

		while ($row = mysqli_fetch_object($sql_user)) {
			$cas[] = [
				'cas' => strtotime($row->time_insert),
				'status' => $row->last_status,
				'lurker' => $row->lurker,
			];
		}

		if ($vloga == 'ucitelj') {
			return $cas[0];
		}


		return (sizeof($cas) > 0 ? $cas : NULL);
	}
	/**********   Prikaz in urejanje hierarhije END   **********/

	/**
	 *  Prikaže glavni meni za super admina  - userja, ki je aktiviral hierarhijo
	 *
	 * @return html
	 */
	public function displayHierarhijaNavigationSuperAdmin()
	{
		if (is_null($this->hierarhija_type)) {
			$this->hierarhija_type = HierarhijaHelper::preveriTipHierarhije($this->anketa);
		}

		// V kolikor nima pravic običjanega uporabnika potem ne prikažemo nič
		if ($this->admin_type > 0 && (is_null($this->hierarhija_type) || $this->hierarhija_type > 4)) {
			die();
		}

		$url = NULL;
		if (!empty($_GET['m'])) {
			$url = $_GET['m'];
		}

		// preverimo status hierarhije
		$hierarchy_status = SurveyInfo::getSurveyModules('hierarhija');

		echo '<div class="hierarhija-navigacija">';
		echo '<ul>';

		# zavihek urejanje hierarhije
		echo '<li>';

		echo '<a id="h-navbar-link" class="no-img side-right' . ($url == M_ADMIN_UREDI_SIFRANTE ? ' active' : '') . '"' . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_HIERARHIJA_SUPERADMIN . '&amp;m=' . M_ADMIN_UREDI_SIFRANTE . '" 
                title="' . ($hierarchy_status == 2 ? $this->lang['srv_hierarchy_code_active'] : $this->lang['srv_hierarchy_code']) . '">';
		echo ($hierarchy_status == 2 ? $this->lang['srv_hierarchy_code_active'] : $this->lang['srv_hierarchy_code']) . '</a>';
		echo '</li>';
		echo '<li class="space"></li>';

		# zavihek uredi uporabnike
		echo '<li>';
		echo '<a id="h-navbar-link" 
                class="no-img side-right' . ($url == M_UREDI_UPORABNIKE ? ' active' : '') . '"' . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_HIERARHIJA_SUPERADMIN . '&amp;m=' . M_UREDI_UPORABNIKE . '" 
                title="' . ($hierarchy_status == 2 ? $this->lang['srv_hierarchy_add_users_link_active'] : $this->lang['srv_hierarchy_add_users_link']) . '">';
		echo ($hierarchy_status == 2 ? $this->lang['srv_hierarchy_add_users_link_active'] : $this->lang['srv_hierarchy_add_users_link']) . '</a>';
		echo '</li>';
		echo '<li class="space"></li>';

		# zavihek AKTIVIRANJE ANEKTE v kolikor je bila že aktivirana potem izklop ni več mogoč
		echo '<li>';
		echo '<a id="h-navbar-link" class="no-img side-right' . ($url == M_ADMIN_AKTIVACIJA ? ' active' : '') . '"' . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_HIERARHIJA_SUPERADMIN . '&amp;m=' . M_ADMIN_AKTIVACIJA . '" title="' . $this->lang['srv_hierarchy_activation_link'] . '">';
		echo $this->lang['srv_hierarchy_activation_link'] . '</a>';
		echo '</li>';
		echo '<li class="space"></li>';

		# zavihek status
		echo '<li>';
		echo '<a id="h-navbar-link" class="no-img side-right' . ($url == M_HIERARHIJA_STATUS ? ' active' : '') . '"' . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_HIERARHIJA_SUPERADMIN . '&amp;m=' . M_HIERARHIJA_STATUS . '" title="' . $this->lang['srv_hierarchy_status'] . '">';
		echo $this->lang['srv_hierarchy_status'] . '</a>';
		echo '</li>';
		echo '<li class="space"></li>';

		# zavihek analize
		if (HierarhijaHelper::preveriDostop($this->anketa) ) {
			echo '<li>';
			echo '<a id="h-navbar-link" class="no-img side-right' . ($url == M_ANALIZE ? ' active' : '') . '"' . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_HIERARHIJA_SUPERADMIN . '&amp;m=' . M_ANALIZE . '" title="' . $this->lang['srv_hierarchy_analysis_link'] . '">';
			echo $this->lang['srv_hierarchy_analysis_link'] . '</a>';
			echo '</li>';
			echo '<li class="space"></li>';
		}

		# zavihek KOPIRANJE ankete
		echo '<li>';
		echo '<a id="h-navbar-link" class="no-img side-right' . ($url == M_ADMIN_KOPIRANJE ? ' active' : '') . '"' . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_HIERARHIJA_SUPERADMIN . '&amp;m=' . M_ADMIN_KOPIRANJE . '" title="' . $this->lang['srv_hierarchy_copy_link'] . '">';
		echo $this->lang['srv_hierarchy_copy_link'] . '</a>';
		echo '</li>';
		echo '<li class="space"></li>';


		echo '</ul>';
		echo '</div>';

	}

	/**
	 *  Prikaže glavni meni vse uporabnike, ki imajo pravico za dostop do
	 * hierarhije
	 *
	 * @return html
	 */
	public function displayHierarhijaNavigation()
	{
		global $site_url;
		// V kolikor nima pravic običjanega uporabnika potem ne prikažemo nič
		if (is_null($this->hierarhija_type) || $this->hierarhija_type < 5) {
			die();
		}

		$url = NULL;
		if (!empty($_GET['m'])) {
			$url = $_GET['m'];
		}


		$hierarchy_status = SurveyInfo::getSurveyModules('hierarhija');

		echo '<div class="hierarhija-navigacija ucitelji">';
		echo '<ul>';

		# zavihek uredi uporabnike
		echo '<li>';
		echo '<a id="h-navbar-link" class="no-img side-right' . ($url == M_UREDI_UPORABNIKE ? ' active' : '') . '"' . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_HIERARHIJA . '&amp;m=' . M_UREDI_UPORABNIKE . '" 
                title="' . ($hierarchy_status == 2 ? $this->lang['srv_hierarchy_add_users_link_active'] : $this->lang['srv_hierarchy_add_users_link']) . '">';
		echo ($hierarchy_status == 2 ? $this->lang['srv_hierarchy_add_users_link_active'] : $this->lang['srv_hierarchy_add_users_link']) . '</a>';
		echo '</li>';
		echo '<li class="space"></li>';

		# zavihek status
		echo '<li>';
		echo '<a id="h-navbar-link" class="no-img side-right' . ($url == M_HIERARHIJA_STATUS ? ' active' : '') . '"' . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_HIERARHIJA . '&amp;m=' . M_HIERARHIJA_STATUS . '" title="' . $this->lang['srv_hierarchy_status'] . '">';
		echo $this->lang['srv_hierarchy_status'] . '</a>';
		echo '</li>';
		echo '<li class="space"></li>';

		# zavihek analize
		echo '<li>';
		echo '<a id="h-navbar-link" class="no-img side-right' . ($url == M_ANALIZE ? ' active' : '') . '"' . ' href="index.php?anketa=' . $this->anketa . '&amp;a=' . A_HIERARHIJA . '&amp;m=' . M_ANALIZE . '" title="' . $this->lang['srv_hierarchy_analysis_link'] . '">';
		echo $this->lang['srv_hierarchy_analysis_link'] . '</a>';
		echo '</li>';
		echo '<li class="space"></li>';


		# url naslov
		echo '<li class="hierarhija-desni-link">';
		echo '<span>' . $this->lang['srv_hierarchy_link_name'] . '</span>';
		echo '<a id="h-navbar-link" class="no-img side-right" href="' . $site_url . 'sa" target="_blank">';
		echo $site_url . 'sa</a>';
		echo '</li>';

		echo '</ul>';
		echo '</div>';

	}

}