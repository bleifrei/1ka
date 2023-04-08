<?php 
/*
 * 
 * skala - 0 Ordinalna
 * skala - 1 Nominalna
 * skala - 2 Razmernostna
 * 
 * Radio grupe so privzeto ordinalne, vendar jih v primeru kadar imamo samo dve kategoriji 
 * in ni drugače uporabniško določeno označimo kot nominalne
 * 
 * 
 * Spremenljivka je nominalna: Kategorij odgovorov ni mogoče primerjati niti ni mogoče računati povprečij. Npr. spol, barva, regija, država.
 * Spremenlijvka je ordinialna:  Kategorije odgovorov je mogoče primerjati; računamo lahko tudi povprečje. Npr. lestvice na skalah (strinjanje, zadovoljstvo,…)
 *
 */
class SpremenljivkaSkala {

	// set a constant
	const ORD = 0;
	const NOM = 1;
	const RAZ = 2;
	
	private $spr_id;		#id spremenljivke
	private $spr_data;		#podatkispremenljivke (cache)
	private $skala = -1;	#skala spremenljivke
	
	/** SpremenljivkaSkala
	 * 
	 * @param Intiger $spr_id
	 */
	function __construct($spr_id) {
		$this->spr_id = (int)$spr_id;
		$this->spr_data = Cache::srv_spremenljivka($this->spr_id);
		
		#polovimo nastavitev iz baze
		$tmpSkala = (int)$this->spr_data['skala'];
		$this->skala = $this->getSpremenljivkaRealSkala($tmpSkala);
	} 
	
	
	/** Vrene pravo skalo spremenljivke, glede na št. kategorij, ipd..., če ni uporabniško določena
	 *  Lahko vrne tudi NULL za nagovor ali za tipe nove tipe kateri niso dodani
	 *
	 * @param (int) $skala
	 * 
	 * @return SpremenljivkaSkala::ORD = 0
	 * @return SpremenljivkaSkala::NOM = 1
	 * @return SpremenljivkaSkala::RAZ = 2
	 * @return NULL
	 */
	function getSpremenljivkaRealSkala($skala) {
		# če je skala večja ali enaka 0 je uporabniško določena  
		if ((int)$skala >= 0) {
			return $skala;
		} else {
			# če ne pa je vse odvisno od vrste spremenljivke in drugih zadev (in od vasjinega razpoloženja)
			switch ((int)$this->spr_data['tip']) {
				#radio
				case 1:
				#dropdown
				case 3:
					$sql = sisplet_query("SELECT count(*) FROM srv_vrednost WHERE spr_id = '$this->spr_id'");
					list($cnt) = mysqli_fetch_row($sql);
					# če imamo samo dve kategoriji jo razglasimo za nominalno
					if ((int)$cnt == 2) {
						return SpremenljivkaSkala::NOM;
					} else {
						return SpremenljivkaSkala::ORD;
					}
				break;
				#checkbox
				case 2:
					return SpremenljivkaSkala::NOM;
				break;
				#tekst old
				case 4:
					return SpremenljivkaSkala::NOM;
				break;
				#6 multigrid
				case 6:
					$sql = sisplet_query("SELECT count(*) FROM srv_grid WHERE spr_id = '$this->spr_id'");
					list($cnt) = mysqli_fetch_row($sql);
					# če imamo samo dve kategoriji jo razglasimo za nominalno
					if ((int)$cnt == 2) {
						return SpremenljivkaSkala::NOM;
					} else {
						return SpremenljivkaSkala::ORD;
					}
				break;
				#number
				case 7:
					return SpremenljivkaSkala::RAZ;
				break;
				#datum
				case 8:
					return SpremenljivkaSkala::ORD;
				break;
				#multi checkbox
				case 16 : // mcheckbox
					return SpremenljivkaSkala::NOM;
				break;
				#razvrščanje
				case 17:
					return SpremenljivkaSkala::ORD;
				break;
				#vsota
				case 18:
					return SpremenljivkaSkala::RAZ;
				break;
				#multi tekst
				case 19:
					return SpremenljivkaSkala::NOM;
				break;
				#multi num
				case 20:
					return SpremenljivkaSkala::RAZ;
				break;
				#tekst *
				case 21:
					return SpremenljivkaSkala::NOM;
				break;
				#kalkulacija
				case 22:
					return SpremenljivkaSkala::NOM;
				break;
			}
		}
		return NULL;
	}
	
	/** vrne skalo spremenljivke kot numerično vrednost
	 * 
	 * @return (Intiger) $this->skala
	 */
	function getSkala() {
		if ((int)$skala >= 0) {
			return $this->skala;
		}
		return NULL;
	}
	
	/** vrne skalo spremenljivke kot tekstovno vrednost
	 * 
	 * @return (text) $this->skala
	 */
	function getSkalaAsText() {
		global $lang;
		switch ($this->skala) {
			case SpremenljivkaSkala::ORD:
				return $lang['srv_analiza_oblika_ordi'];
				break;
			case SpremenljivkaSkala::NOM:
				return $lang['srv_analiza_oblika_nomi'];
				break;
			case SpremenljivkaSkala::RAZ:
				return $lang['srv_analiza_oblika_razm'];
				break;
		}
		return NULL;
	}
	
	/** Ali lahko za tip spremenljivke spremenimo skalo
	 *
	 * skalo lahko spremninjamo pri 
	 *  -radio
	 *  -dropdown
	 *  -multi radio
	 *  
	 * @return boolean
	 */
	function canChangeSkala() {
		switch ((int)$this->spr_data['tip']) {
			case 1:
			case 3:
			case 6:
				return true;
			break;
		}
		return false;
	}

	/** Ali je trenutna skala enaka pogoju 
	 * 
	 * @param const(ORD|NOM|RAZ) $what 
	 * @return boolean
	 */
	function is($what) {
		return ($this->getSkala() == $what) ? TRUE : FALSE;
	}
	
	function __toString() {
		return (String)$this->getSkala();
	}
}

