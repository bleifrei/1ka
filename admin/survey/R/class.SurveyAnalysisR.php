<?php

/**
 * @date 11.6.2013
 *
 * @author: Peter Hrvatin
 *
 * @desc: racunanje analiz z R-jem (klicanje skript, obdelava rezultatov)
 *
 */

define("DATA_FOLDER", "admin/survey/R/TempData");
define("SCRIPT_FOLDER", "admin/survey/R/script");


class SurveyAnalysisR {

	public $ank_id;			# id ankete
	

	// Konstruktor
	public function __construct($anketa = null) {
		global $global_user_id, $site_path, $lang;

		if((int)$anketa > 0){
			
			$this->ank_id = $anketa;
		}
		else {
			die("Napaka!");
		}
	}
	
	
	// Pozenemo skripto za multicrosstabulacije
	public function createMultiCrosstabulation($crosstabVars=array(), $avgVar=0, $delezVars=array()){
		global $site_path;
		global $lang;
		
		// Skripta ki jo klicemo (glede na stevilo spremenljivk)		
		$script = $site_path . SCRIPT_FOLDER . '/';
		$script .= 'createCrosstabulation'.count($crosstabVars).'.R';

		
		// Prvi parameter je pot do r folderja
		$params = $site_path;		
		
		
		// Ce racunamo povprecje (avgVar==1)
		$params .= ' '.$avgVar;
			
		
		// variable pri katerih upostevamo delez
		$delezVar = 0;
		if(count($delezVars) > 0){
			
			// Gre za checkbox
			if($delezVars == -1){
				$params .= ' -1';
			}
			else{
				$params .= ' ';
				$cnt = 1;
				foreach($delezVars as $var){
					if($var == '1')
						$params .= $cnt.',';
					
					$cnt++;
				}
				$params = substr($params, 0, -1);
			}
			
			$delezVar = 1;
		}

		
		// pozenemo skripto
		$out = shell_exec('Rscript '.$script.' '.$params);


		//Rscript c:/Programs/WAMP/www/FDV/admin/survey/R/script/createCrosstabulation2.R c:/Programs/WAMP/www/FDV/ 0 -1
		

		// Napolnimo rezultate v array
		$results = $this->parseMultiCrosstabResults($out, $avgVar, $delezVar);
		
		return $results;
	}
	
	// Napolnimo podatke iz vrnjenega rezultata
	function parseMultiCrosstabResults($out, $avgVar=0, $delezVar=0){
		
		$results = array();
		
		// Razbijemo na variable, podatke in vsote
		$strings = explode("--", $out);
		
		
		// VARIABLE shranimo v array
		$varsString = explode("_", $strings[0]);
		$vars = array();
		foreach($varsString as $varString){
			$vars[] = explode(",", $varString);
		}
		//$results['vars'] = $vars;
	
		
		// PODATKE shranimo v array
		$tempData = explode("_", $strings[1]);
		$tempAvgData = explode("_", $strings[6]);
		$tempDelezData = ($avgVar == 1) ? explode("_", $strings[7]) : explode("_", $strings[6]);
		$data = array();
		$avgData = array();
		$delezData = array();
		$varCount = count($vars);
		
		// crosstab na 2 variablah
		if($varCount == 2){
		
			$dataKey = 0;
			foreach($vars[1] as $key2 => $var2){		
				foreach($vars[0] as $key1 => $var1){
					
					if((int)$var1 != -1 && (int)$var2 != -1 && $tempData[$dataKey] != null && $tempData[$dataKey] != 0)
						$data[(int)$var1][(int)$var2] = (int)$tempData[$dataKey];
					
					// Povprecje ce ga imamo
					if($avgVar == 1 && (int)$var1 != -1 && (int)$var2 != -1 && $tempAvgData[$dataKey] != null && $tempAvgData[$dataKey] != 0)
						$avgData[(int)$var1][(int)$var2] = (float)$tempAvgData[$dataKey];
						
					// Delez ce ga imamo
					if($delezVar == 1 && (int)$var1 != -1 && (int)$var2 != -1 && $tempDelezData[$dataKey] != null && $tempDelezData[$dataKey] != 0)
						$delezData[(int)$var1][(int)$var2] = (float)$tempDelezData[$dataKey];

					$dataKey++;
				}
			}
			$results['crosstab'] = $data;
			$results['avg'] = $avgData;
			$results['delez'] = $delezData;

			// VSOTE shranimo v array
			$sumsTemp = array_map('intval', explode("_", $strings[2]));
			$sums = array();
			foreach($sumsTemp as $sumKey => $sum){
				$key = (int)$vars[0][$sumKey];
				$sums[$key] = $sum;
			}
			$results['sumaVrstica'] = $sums;
			
			$sumsTemp = array_map('intval', explode("_", $strings[3]));
			$sums = array();
			foreach($sumsTemp as $sumKey => $sum){
				$key = (int)$vars[1][$sumKey];
				$sums[$key] = $sum;
			}
			$results['sumaStolpec'] = $sums;
			
			$sums = $strings[4];
			$results['sumaSkupna'] = (int)$sums;
		}		
		// crosstab na 3 variablah
		elseif($varCount == 3){
		
			$dataKey = 0;
			foreach($vars[2] as $key3 => $var3){		
				foreach($vars[1] as $key2 => $var2){
					foreach($vars[0] as $key1 => $var1){

						if((int)$var1 != -1 && (int)$var2 != -1 && (int)$var3 != -1 && $tempData[$dataKey] != null && $tempData[$dataKey] != 0)
							$data[(int)$var1][(int)$var2][(int)$var3] = (int)$tempData[$dataKey];
							
						// Povprecje ce ga imamo
						if($avgVar == 1 && (int)$var1 != -1 && (int)$var2 != -1 && (int)$var3 != -1 && $tempAvgData[$dataKey] != null && $tempAvgData[$dataKey] != 0)
							$avgData[(int)$var1][(int)$var2][(int)$var3] = (float)$tempAvgData[$dataKey];
							
						// Delez ce ga imamo
						if($delezVar == 1 && (int)$var1 != -1 && (int)$var2 != -1 && (int)$var3 != -1 && $tempDelezData[$dataKey] != null && $tempDelezData[$dataKey] != 0)
							$delezData[(int)$var1][(int)$var2][(int)$var3] = (float)$tempDelezData[$dataKey];
							
						$dataKey++;
					}
				}
			}
			$results['crosstab'] = $data;
			$results['avg'] = $avgData;
			$results['delez'] = $delezData;

			// VSOTE shranimo v array
			$sumsTemp = array_map('intval', explode("_", $strings[2]));
			$sums = array();
			foreach($sumsTemp as $sumKey => $sum){
				$key = (int)$vars[0][$sumKey];
				$sums[$key] = $sum;
			}
			$results['sumaVrstica'] = $sums;
			
			$sumsTemp = array_map('intval', explode("_", $strings[3]));
			$sums = array();
			foreach($sumsTemp as $sumKey => $sum){
				$key = (int)$vars[1][$sumKey];
				$sums[$key] = $sum;
			}
			$results['sumaStolpec'] = $sums;
			
			$sums = $strings[4];
			$results['sumaSkupna'] = (int)$sums;
		}
		// crosstab na 4 variablah
		elseif($varCount == 4){
			
			$dataKey = 0;
			foreach($vars[3] as $key4 => $var4){		
				foreach($vars[2] as $key3 => $var3){
					foreach($vars[1] as $key2 => $var2){
						foreach($vars[0] as $key1 => $var1){

							if((int)$var1 != -1 && (int)$var2 != -1 && (int)$var3 != -1 && (int)$var4 != -1 && $tempData[$dataKey] != null && $tempData[$dataKey] != 0)
								$data[(int)$var1][(int)$var2][(int)$var3][(int)$var4] = (int)$tempData[$dataKey];
							
							// Povprecje ce ga imamo
							if($avgVars == 1 && (int)$var1 != -1 && (int)$var2 != -1 && (int)$var3 != -1 && (int)$var4 != -1 && $tempAvgData[$dataKey] != null && $tempAvgData[$dataKey] != 0)
								$avgData[(int)$var1][(int)$var2][(int)$var3][(int)$var4] = (float)$tempAvgData[$dataKey];
								
							// Delez ce ga imamo
							if($delezVars == 1 && (int)$var1 != -1 && (int)$var2 != -1 && (int)$var3 != -1 && (int)$var4 != -1 && $tempDelezData[$dataKey] != null && $tempDelezData[$dataKey] != 0)
								$delezData[(int)$var1][(int)$var2][(int)$var3][(int)$var4] = (float)$tempDelezData[$dataKey];
								
							$dataKey++;
						}
					}
				}
			}
			$results['crosstab'] = $data;
			$results['avg'] = $avgData;
			$results['delez'] = $delezData;
			
			// VSOTE shranimo v array
			$sumsTemp = array_map('intval', explode("_", $strings[2]));
			$sums = array();
			foreach($sumsTemp as $sumKey => $sum){
				$key = (int)$vars[0][$sumKey];
				$sums[$key] = $sum;
			}
			$results['sumaVrstica'] = $sums;
			
			$sumsTemp = array_map('intval', explode("_", $strings[3]));
			$sums = array();
			foreach($sumsTemp as $sumKey => $sum){
				$key = (int)$vars[1][$sumKey];
				$sums[$key] = $sum;
			}
			$results['sumaStolpec'] = $sums;
			
			$sums = $strings[4];
			$results['sumaSkupna'] = (int)$sums;
		}	
		else{
			$data = null;
		}
		
		
		// X^2
		$x2 = $strings[5];
		$results['hi2'] = (float)$x2;		
		
		
		return $results;
	}
	
	
	// Pozenemo skripto za ttest
	public function createTTest($vals){
		global $site_path;
		global $lang;
		
		// Skripta ki jo klicemo (glede na stevilo spremenljivk)		
		$script = $site_path . SCRIPT_FOLDER . '/';
		$script .= 'createTTest.R';

		
		// Prvi parameter je pot do r folderja
		$params = $site_path;
		
		
		// Drugi in tretji parameter - vrednost ki jo upostevamo (ce je checkbox sta oba 1 in jo kasneje ignoriramo)
		$params .= ' '.$vals[0].' '.$vals[1];
		

		// pozenemo skripto
		$out = shell_exec('Rscript '.$script.' '.$params);
		//echo $out;

		// Napolnimo rezultate v array
		$results = $this->parseTTestResults($out);
		
		//Rscript c:/Programs/WAMP/www/FDV/admin/survey/R/script/createTTest.R c:/Programs/WAMP/www/FDV/
		
		return $results;
	}
	
	// Napolnimo podatke iz vrnjenega rezultata
	function parseTTestResults($out){
		
		$results = array();			

		
		// Razbijemo na variable, podatke in vsote
		$strings = explode("--", $out);

		// Vrednosti prve variable
		$vals1 = explode("_", $strings[0]);
		$results['1'] = array(	
			'n' => (int)$vals1[0], 
			'x' => (float)$vals1[1], 
			's2' => (float)$vals1[2], 
			'se' => (float)$vals1[3], 
			'se2' => (float)$vals1[4], 
			'margin' => (float)$vals1[5]
		);
	
		// Vrednosti druge variable
		$vals2 = explode("_", $strings[1]);
		$results['2'] = array(
			'n' => (int)$vals2[0], 
			'x' => (float)$vals2[1], 
			's2' => (float)$vals2[2], 
			'se' => (float)$vals2[3], 
			'se2' => (float)$vals2[4], 
			'margin' => (float)$vals2[5]
		);

		// Skupne vrednosti
		$vals = explode("_", $strings[2]);
		$results['d'] = (float)$vals[0];
		$results['sed'] = (float)$vals[1];
		$results['t'] = (float)$vals[2];
		$results['sig'] = (float)$vals[3];

		
		return $results;
	}
	
}

?>