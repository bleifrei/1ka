<?php
/***************************************
 * Description: Služi kot seznam vseh globalnih spremenljivk, ki se uporabljajo v Main Survey in jih potem tudi definiramo v Controllerju
 * Autor: Robert Šmalc
 * Created date: 29.01.2016
 *****************************************/


namespace App\Controllers;


class VariableClass
{
    protected static $_configuration = array(
        'anketa' 		=> null,    // trenutna anketa
        'get' 			=> null,    // tukaj poberemo vse get parametre, ki se pošiljajo preko GET ali POST in jih dodamo v spremenljivko
        'grupa' 		=> null,    // trenutna grupa
        'spremenljivka' => null,    // trenutna spremenljivka
        'usr_id' 		=> null,    // ID trenutnega uporabnika
        'cookie_expire' => null, 	// nastavitev kdaj expira cookie, da vemo za primer brez cookie-ja, da prenasamo preko urlja
        'cookie_url' 	=> null,    // kadar cookie prenasamo po URLju, se v to spremenljivko zapise koda, ki jo dodamo vsakemu linku ($_GET)
        'printPreview' 	=> false,   // ali kliče konstruktor
        'hierarhija'    => null,    // če gre za hierarhijo potem poberemo get parametre

        /** prisilimo prikaz spremenljivke, za predogled vanalizah in mogoče še kje
         *
         * @public boolean
         */
        'forceShowSpremenljivka' => false,
        'db_table' 	=> '',
        'ime_AW' 	=> null,    // trenutno ime, ki se reseuje (socialna omrezja - alterwise)
        'loop_AW' 	=> null,
        'loop_id' 	=> null,    // vrednost po kateri loopamo, ce smo v loopu
        'lang_id' 	=> null,
        'language' 	=> '1',
        'smv' 		=> null,    // misnig vrednosti od ankete
        'mobile' 	=> 0,       // 0 - klasicna, 1 - mobilna, 2 - dlancniki
        'ismobile' 	=> 0,       // detekcija, ce je mobilna naprava

        'quick_view' 			=> false,   	// ali smo samo v predogledu ankete od uporabnika
        'user_inv_archive' 		=> 0,    		// id_arhiva vabil (če smo preko vabil)
        'displayAllPages' 		=> false,   	// ali smo v predogledu kjer izpisujemo vse strani
        'webSMSurvey'			=> '34862', 	// WebSM anketa, ki ne shranjuje nicesar in skoci na pravo stran (gru_id) glede na url
        'generateComputeJS' 	=> '',

        /**
         * @desc vrne array vseh spremenljivk vgnezdenih v podanem ifu
         */
        'getElements' => array(),
        'checkSpremenljivka' => array(),
        'checkIf' => array(),
        'getGrupa' => array(),
        'preskocena_first' => 1,        // spremenljivka, da pri preskocenih straneh ne delamo vsakic vseh querijev (ampak samo prvic)
        'cache_srv_data_grid' => '',            // ostale spremenljivke so cache, ki se v posted() polni in zapise v bazo v posted_commit()
        'cache_srv_data_vrednost' => '',
        'cache_srv_data_text' => '',
        'cache_srv_data_checkgrid' => '',
        'cache_srv_data_textgrid' => '',
        'cache_srv_data_rating' => '',
        'cache_srv_data_vrednost_cond' => '',
        'cache_srv_data_map' => '',
		'cache_srv_data_heatmap' => '',
        'cache_delete' => '',
        'lurker' => -1,
        'getOtherValue' => array(),
        'select_from_srv_spremenljivka' => array(),

        // Naknadno dodane globalne spremenljivke
        'userAutor' => false
    );

    protected $key, $value, $return;

    // Shrani novo vrednost na obstoječo spremenljivko ali doda novo spremenljivko
    public static function save($key, $value)
    {
		// Ce shranjujemo anketa_id ('anketa') jo najprej dekodiramo ce je potrebno
		if($key == 'anketa'){
			$value = self::decryptAnketaID($value);
		}

        $polje = self::izStringaVpolje($key);
        if ($polje){
            self::$_configuration[ $polje['spremenljivka'][0] ][ $polje['key'][0] ] = $value;
        } else {
            self::$_configuration[$key] = $value;
        };
    }
	
	// Popravimo id ankete ce gre za kodiranega (pri novih anketah je v url-ju kodiran id ankete da respondenti ne morejo dostopati do drugih anket)
	private static function decryptAnketaID($anketa){
		
		// Ce anketa ni numeric jo pretvorimo v originalen id
		if(!is_numeric($anketa)){
			
			$anketa_arr = str_split($anketa);
			$anketa_id = '';
			
			foreach($anketa_arr as $pos => $char){
				// Na lihih mestih pretvorimo crko nazaj v stevilko
				if($pos % 2 == 0)
					$anketa_id .= ord($char) - 97;
				else
					$anketa_id .= $char;
			}
		}
		else 
			$anketa_id = $anketa;
			
		return $anketa_id;
	}

    /************************************************
     *  Funkcija iz stringa naredi polje z id vrednostjo
     *  primer (string) 'polje[id]' uredi v (arrey) polje[id]
     * @return array()
     ************************************************/
    private static function izStringaVpolje($string)
    {
        // spremenljivka je mišljen tekst, ki je pred oklepaji
        $spremenljivka = array();
        // key je vrednost oz. id te spremenljivke, ki je med []
        $key = array();

        $t = "";
        for ($i = 0; $i < strlen($string); $i++) {
            if ($string[$i] == '[') {
                $spremenljivka[] = $t;
                $t = "";
                $t1 = "";
                $i++;
                while ($string[$i] != ']') {
                    $t1 .= $string[$i];
                    $i++;
                }
                $key[] = $t1;

            } else {
                if ($string[$i] != ']')
                    $t .= $string[$i];
                else {
                    continue;
                }

            }
        }

        // V kolikor je samo string
        if ($t != "")
            return false;

        return [
            'spremenljivka' => $spremenljivka,
            'key' => $key
        ];
    }


    // Dodamo vrednost k že obstoječi vrednosti
    public static function add($key, $value, $return = null)
    {
        self::$_configuration[$key] .= $value;

        if (!is_null($return))
            return $value;
    }

    public static function get($key)
    {
        return self::$_configuration[$key];
    }

    public static function getAll()
    {
        return self::$_configuration;
    }

    /************************************************
     * Pridobimo vse variable, ki se uporabljajo za main/survey in jih dodamo na Controller -> $this variable
     *
     * @return $this
     ************************************************/
    public function refresh()
    {
        foreach (self::$_configuration as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }


}