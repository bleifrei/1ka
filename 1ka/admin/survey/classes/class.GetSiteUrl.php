<?php
/** 
 *  Julij 2018
 * 
 * Pridobi site url
 * 
 * @author Patrik Pucer
 */
include_once('../survey/definition.php');

class GetSiteUrl
{	
    function __construct() {		
    }

    function ajax() {
		global $site_url;
		echo $site_url;
    }
	
}