<?php

	global $site_path;
	global $site_url;
	
	include_once('../../function.php');
	include_once('../survey/definition.php');


/** 
 * @desc Class za generacijo slike za heatmap
 */
class imageIzvozHeatmap {

	var $anketa;// = array();			// trenutna anketa

	var $pi=array('canCreate'=>false); // za shrambo parametrov in sporocil
	var $pdf;
	
	
	var $image;
	
	
	var $currentStyle;
	
	private $headFileName = null;					# pot do header fajla
	private $dataFileName = null;					# pot do data fajla
	private $dataFileStatus = null;					# status data datoteke
	private $CID = null;							# class za inkrementalno dodajanje fajlov
	
	var $current_loop = 'undefined';
	
	
	/**
	* @desc konstruktor
	*/
	function __construct ($anketa = null, $sprID = null, $loop = null)
	{	
		global $site_path;
		global $global_user_id;
		
		// preverimo ali imamo stevilko ankete
		if ( is_numeric($anketa) )
		{
			$this->anketa['id'] = $anketa;
			$this->spremenljivka = $sprID;			
			SurveyAnalysis::Init($anketa);
			SurveyAnalysis::$setUpJSAnaliza = false;
		}
		else
		{
			$this->pi['msg'] = "Anketa ni izbrana!";
			$this->pi['canCreate'] = false;
			return false;
		}

		if ( SurveyInfo::getInstance()->SurveyInit($this->anketa['id']) && $this->init())
		{
			$this->anketa['uid'] = $global_user_id;
			SurveyUserSetting::getInstance()->Init($this->anketa['id'], $this->anketa['uid']);
		}
		else
			return false;
		// ce smo prisli do tu je vse ok
		$this->pi['canCreate'] = true;

		return true;
	}

	// SETTERS && GETTERS

	function getFile($fileName)
	{	
		global $site_path;
		global $site_url;
		//echo '<img alt="" src="'.$site_url.'main/survey/uploads/'.$fileName.'"></br>';
		$src = $site_url.'main/survey/uploads/'.$fileName;
		$image = imagecreatefrompng($src);
		
		imagealphablending($image, false);
		imagesavealpha($image, true);
		
		//header('Content-Disposition: Attachment;filename='.$fileName);
		header('Content-Disposition: Attachment;filename='.$fileName.';filename*=utf8'.$fileName);
		header('Content-Type: image/png');
		//header('Content-Type: image/png; charset=utf-8');
		//header('Content-Type: application/force-download');

		imagepng($image);
		imagedestroy($image);		
	}


	function init()
	{
		global $lang;		

		return true;
	}
	

}
?>