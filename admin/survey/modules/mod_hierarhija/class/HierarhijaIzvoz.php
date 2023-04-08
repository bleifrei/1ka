<?php
/***************************************
 * Description:
 * Autor: Robert Šmalc
 * Created date: 10.08.2017
 *****************************************/

namespace Hierarhija;

use Dostop;
use Export;
use Hierarhija\Model\HierarhijaOnlyQuery;
use Hierarhija\Model\HierarhijaQuery;
use SurveyInfo;
use TrackingClass;

class HierarhijaIzvoz {

	private static $_instance;

	protected $anketa;

	protected $polje_strukture = [];

	protected $user_id;

	public function __construct($anketa)
	{
		$this->anketa = $anketa;

		global $global_user_id;
		$this->user_id = $global_user_id;

		TrackingClass::update($anketa, '21');

		if (!(new Dostop())->checkDostop($this->anketa)) {
			return FALSE;
		}
	}

	public static function getInstance($anketa)
	{
		if (self::$_instance) {
			return self::$_instance;
		}

		return new HierarhijaIzvoz($anketa);
	}

	/**
	 * Izvozimo vse uporabnike, do katerih imamo dostop
	 *
	 * @param bool $porocilo - v kolikor gre za poročilo vrnemo polje;
	 * @return response downlod CSV
	 */

	public function csvIzvozVsehUporabnikov()
	{
		$hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : NULL);

		if ($hierarhija_type < 5) {
			$podatki = (new HierarhijaQuery())->hierarhijaArrayDataTables($this->anketa, NULL, TRUE);
		} else {
			$hierarhija = (new HierarhijaQuery());
			$uporabnik = $hierarhija->preveriPravicoUporabnika($this->anketa);
			$struktura = $hierarhija->poisciHierarhijoNavzgor($uporabnik->struktura_id);

			$podatki = (new HierarhijaQuery())->hierarhijaArrayDataTables($this->anketa, $struktura, TRUE);
		}

		// pridobimo prvo vrstico za izvoz
		$ravni = sisplet_query("SELECT level, ime FROM srv_hierarhija_ravni WHERE anketa_id='" . $this->anketa . "' ORDER BY level", "obj");
		foreach ($ravni as $raven) {
			$header[] = $raven->level . '. ' . $raven->ime;
		}
		$izvoz[] = $header;

		foreach ($podatki as $key => $row) {
			foreach ($row as $podatek) {
				$izvoz[$key + 1][] = str_replace('<br />', '', $podatek['label']);
			}
		}

		return Export::init($this->anketa)->csv('Izvoz uporabnikov', $izvoz);
	}

    /**
     * Pripravimo izvoz strukture za R poročilo ali CSV izvoz
     *
     * @param bool $porocilo
     * @param bool $stevilke
     *
     * @return array|bool|void
     */
	public function csvIzvozStruktureZaObdelavo($porocilo = false, $stevilke = false)
	{
		$hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : NULL);

		if ($hierarhija_type > 4) {
			return FALSE;
		}

		//Pridobimo vso strukturo iz šifer
		$strukture = sisplet_query("SELECT k.url AS url, u.name AS name, u.surname AS surname, u.email AS email FROM srv_hierarhija_koda AS k LEFT JOIN users AS u ON k.user_id=u.id WHERE anketa_id='" . $this->anketa . "'", "obj");

		// pridobimo prvo vrstico za izvoz
		$ravni = sisplet_query("SELECT level, ime FROM srv_hierarhija_ravni WHERE anketa_id='" . $this->anketa . "' ORDER BY level", "obj");
		$header[] = 'vloga';
		foreach ($ravni as $raven) {
			$header[] = $raven->level . '. ' . $raven->ime;
		}
		//podatki o učitelju
		$header[] = 'Ime in priimek';
		$header[] = 'Email';

		$izvoz[] = $header;

		foreach ($strukture as $key => $struktura) {
			parse_str($struktura->url, $url);
			asort($url);

			// izvozimo strukturo za vsakega učitelja posebej
			foreach ($url as $keySifrant => $row) {
				if ($key == 0) {
					$izvoz[$key + 1][] = $keySifrant;
				}

				$podatek = sisplet_query("SELECT naslov, variable FROM srv_vrednost WHERE id='".$row."'", "obj");
				if($stevilke){
            $izvoz[$key + 2][] = $podatek->variable;
        }else {
//            $podatek = sisplet_query("SELECT naslov, variable FROM srv_vrednost WHERE id='".$row."'", "obj");
            $izvoz[$key + 2][] = $podatek->naslov;
        }
			}


			// Dodamo še drugo vrstico ime in email
			$izvoz[1][sizeof($url)] = 'ime';
			$izvoz[1][sizeof($url) + 1] = 'email';

			//Podatki o učitelju
			$izvoz[$key + 2][] = $struktura->name . ' ' . $struktura->surname;
			$izvoz[$key + 2][] = $struktura->email;

		}

		if($porocilo)
			return $izvoz;

		return Export::init($this->anketa)
		             ->csv('Izvoz strukture hierarhije', $izvoz);
	}

	/**
	 * Izvoz tabele status z vsemi šiframi in časi reševanja
	 *
	 * @return CSV download response
	 */
	public function csvIzvozStatusa()
	{

		$hierarhija_type = (!empty($_SESSION['hierarhija'][$this->anketa]['type']) ? $_SESSION['hierarhija'][$this->anketa]['type'] : NULL);

		if ($hierarhija_type > 4) {
			return FALSE;
		}

		$header[] = [
			'Hierarhija',
			'Email učitelja',
			'Koda za učence',
			'Status učencev - začel izpolnjevati',
			'Status učencev - delno izpolnjene',
			'Status učencev - končal anketo',
			'Časovni potek - prvi vnos',
			'Časovni potek - zadnji vnos',
			'Koda za učitelja',
			'Vnos učitelja',
		];

		$body = $this->getStatus();

		if (!is_null($body)) {
			$izvoz = array_merge($header, $body);
		} else {
			$izvoz = $header;
		}

		return Export::init($this->anketa)->csv('Status_izvoz', $izvoz);
	}

	/**
	 * Pridobimo večdimenzionalno polje, kjer posamezna vrstica ima podatke o
	 * enem predmetu njegovih respondentih
	 *
	 * @param (boolean) $array - v kolikor vrnemo polje
	 *
	 * @return array|null
	 */
	public function getStatus($array = FALSE)
	{
		$izvoz = [];

		//v kolikor imamo več URL-jev se pravi za več predmetov potem moramo izpisati sklope za vse predmete
		$max_st_nivojev = sisplet_query("SELECT MAX(level) FROM srv_hierarhija_struktura WHERE anketa_id='" . $this->anketa . "'");

		// V kolikor še ni nič vnosov
		if ($max_st_nivojev->num_rows == 0) {
			return NULL;
		}

		$max_st_nivojev = $max_st_nivojev->fetch_row()[0];

		// Pridobimo vse ID-je na zadnjem nivoju
		$strukture = (new HierarhijaOnlyQuery())->queryStrukturaUsers($this->anketa);

		// Zanka po celotni strukturi za vsakega učitelja pridobimo vse response
		foreach ($strukture as $struktura) {

			// Prikažemo samo vpise, ki imajo vpisanega učitelja na zadnjem nivoju
			if ($struktura['level'] == $max_st_nivojev) {

				// pridobimo podatke o rešenih anketah samo za to strukturo
				$cas = $this->pridobiStatisticnePodatke($struktura['id'], 'ucenec');
				$cas_ucitelj = $this->pridobiStatisticnePodatke($struktura['id'], 'ucitelj');

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

				$vrstica = [
					HierarhijaHelper::hierarhijaPrikazNaslovovpriUrlju($this->anketa, $struktura['id'], $struktura['email']),
					$struktura['email'],
					strtoupper(HierarhijaOnlyQuery::getKodaRow($this->anketa, $struktura['id'])->koda),
					(!empty($zacel_izpolnjevati) ? $zacel_izpolnjevati : '/'),
					(!empty($delno_izpolnjena) ? $delno_izpolnjena : '/'),
					(!empty($koncal_anketo) ? $koncal_anketo : '/'),
					(!is_null($first_insert) ? date('d.m.Y, H:i', $first_insert) : '/'),
					(!is_null($last_insert) ? date('d.m.Y, H:i', $last_insert) : '/'),
					strtoupper(HierarhijaOnlyQuery::getKodaRow($this->anketa, $struktura['id'], 'ucitelj')->koda),
					((!is_null($cas_ucitelj['cas']) && $cas_ucitelj['status'] == 6 && $cas_ucitelj['lurker'] == 0) ? date('d.m.Y, H:i', $cas_ucitelj['cas']) : '/'),
				];

				$id_kode = HierarhijaOnlyQuery::getKodaRow($this->anketa, $struktura['id'])->koda;

				if ($array && !is_null($id_kode)) {
					$izvoz[$id_kode] = $vrstica;
				} else {
					$izvoz[] = $vrstica;
				}
			}

		}

		return $izvoz;
	}

	/**
	 * Pridobimo podatke o izpolnjevanju posameznega respondenta
	 *
	 * @param $struktura_id
	 * @param $vloga
	 *
	 * @return array|mixed|null
	 */
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
}