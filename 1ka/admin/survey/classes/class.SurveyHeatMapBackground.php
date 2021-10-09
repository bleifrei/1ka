<?php
/** 
 *  November 2016
 * 
 * Pridobi podatke o klikanih tockah na slikah, za njihov prikaz v heatmap
 * 
 * @author Patrik Pucer
 */
include_once('../survey/definition.php');

class SurveyHeatMapBackground
{	
    function __construct() {		
    }

    function ajax() {
        if(isset($_POST['heatmapBackground_data'])) {
			$spr_id = $_POST['heatmapBackground_data'];
			//echo $spr_id;
			$data = sisplet_query("SELECT params from srv_spremenljivka WHERE id = $spr_id");
			$rowImageHtml = mysqli_fetch_assoc($data);
			$spremenljivkaParams = new enkaParameters($rowImageHtml['params']);
			
			//kopiranje slike iz spleta, ce ta ni na lokalnem strezniku
			//$this->getImagename('hotspot', $spr_id, 'hotspot_image=');
			$text=$spremenljivkaParams->get('hotspot_image');
			$this->changeHeatmapImage($text, $spr_id);
			//kopiranje slike iz spleta, ce ta ni na lokalnem strezniku - konec			
			
			
			echo $spremenljivkaParams->get('hotspot_image');
        }
		exit();
    }
	
		
	//function getImagename($text, $sprId, $findme){
	function changeHeatmapImage($text, $sprId){
		global $site_path;
		$imageName = $text;		
		//echo "imageName ".$imageName."</br>";
		$findme = 'editor/';
		
		$pos = strpos($imageName, $findme);	//najdi pozicijo teksta 'editor/'
		//echo "editor je tu ".$pos."</br>";

		if($pos){	//ce je slika na strezniku
			$slikaNaStrezniku = 1;
		}else{//ce slike ni na strezniku
			$slikaNaStrezniku = 0;
		}
		
		if($slikaNaStrezniku==0){	//ce slika ni na strezniku
			$this->getOnlineImageName($imageName, $slikaNaStrezniku, $sprId);	//pridobi njen URL				
		}
		//$imageName = substr($imageName, 0, $pos-4);	//pokazi le del params od zacetka besedila do '"'-4character manj ".png"/".jpg"*/
		
		//echo "imagename pred return: ".$imageName."</br>";
		//return $imageName;		
	}
	
	function getEndPosition($imageName){
		$findme = '"';
		$pos = strpos($imageName, $findme);	//najdi pozicijo teksta '"'
		return $pos;
	}
	
	function getOnlineImageName($imageName, $slikaNaStrezniku, $sprId){
		global $site_path;
		global $site_url;
		//$imageName = "jo je potrebno pobrati online";
		//$row = Cache::srv_spremenljivka(self::$spremenljivka);
		$row = Cache::srv_spremenljivka($sprId);
		//echo "sprem: ".self::$spremenljivka."</br>";
		$spremenljivkaParams = new enkaParameters($row['params']);
		//echo "params: ".$spremenljivkaParams->get('hotspot_image');
		$imageName = $spremenljivkaParams->get('hotspot_image');
 		
		$findHttp = 'http';
		$posHttp = strpos($imageName, $findHttp);		
		$imageName = substr($imageName,$posHttp);	//besedilo do zacetka http		

		$pos = $this->getEndPosition($imageName);	//najdi pozicijo konca URL slike
		$imageName = substr($imageName, 0, $pos);	//pokazi le del params od zacetka besedila do '"' oz. konca URL slike
		$imageExtension = substr($imageName, $pos-3, 3);	//pridobi koncnico slike
		
		if($imageExtension!='jpg'&&$imageExtension!='png'&&$imageExtension!='gif'){	//ce ni veljavnen extension, spremeni ga v png
			$imageExtension='png';
		}
	
		$imgFilename = $sprId.'tmpImage.'.$imageExtension;	//tmp ime slike, ki je sestavljeno iz id spremenljivke+tmpImage+extension
		$pathDir = $site_path.'uploadi/editor/';	//pot za novo mapo, kjer se bodo shranjevale slike za trenutno anketo	
		$path = $pathDir.$imgFilename;	//pot do datoteke z imenom datoteke

		# ukaz za pretakanje slike
		if(IS_WINDOWS){
			//za windows sisteme	//powershell -command "& { iwr URL -OutFile 'PATH' }"			
			$command = 'powershell -command "& { iwr \''.$imageName.'\' -OutFile \''.$path.'\' }"';
		}elseif(IS_LINUX){
			//za linux sisteme //exec('wget URL -P PATH ');
			//$command = 'wget \''.$imageName.'\' -P '.$path.' ';
			$command = 'wget -O '.$path.' \''.$imageName.'\' ';
		}		
		//echo $command;
		exec($command); //pretoci sliko
		
		//$path = $pathDir.$imgFilename;	//pot do datoteke z imenom datoteke
		if($imageExtension == 'gif' || $imageExtension == 'jpg'){	//ce je slika gif, jo je potrebno pretvoriti v png,  saj latex ne podpira gif
			//$this->convertGifToPng($path, $slikaNaStrezniku);
			$this->convertGifToPng($path, $slikaNaStrezniku, $imageExtension);
		}

	}
	
	function convertGifToPng($path, $slikaNaStrezniku, $imageExtension){
		//echo "path: ".$path."</br>";
		if($imageExtension == 'gif'){
			$image = imagecreatefromgif($path);	//pripravi sliko iz gif za pretvorbo
		}elseif($imageExtension == 'jpg'){
			$image = imagecreatefromjpeg($path);	//pripravi sliko iz jpeg za pretvorbo
		}
		$imageName = substr($path, 0, -3);	//ime slike brez extension-a
		//echo $imageName."</br>";
		$imageNamePNG = $imageName.'png';	//ime slike z ustreznim extension		
		imagepng($image, $imageNamePNG);	//pretvori pripravljeno gif sliko v png
		
		if($slikaNaStrezniku==0){	//ce slika je iz URL in ni na strezniku,
			if($imageExtension == 'gif'){
				unlink($imageName.'gif');	//izbrisi gif sliko
			}elseif($imageExtension == 'jpg'){
				unlink($imageName.'jpg');	//izbrisi jpg sliko
			}
		}		
	}
	
	
}