<?php 
	
	session_start();
	
	//phpinfo();
	error_reporting(0);
	//ini_set('display_errors', 'On');
    

    include_once('classes/DisplayController.php');
    

    // Preverimo ce imamo uvozeno bazo in ce ne ponudbimo uvoz
	$import_db = new ImportDB();
	if($import_db->checkDBEmpty()){
		global $site_url;
		header('Location: '.$site_url.'frontend/install');
    }
    
	
	// Inicializiramo razred za prikaz
	$dc = new DisplayController();
	
    
    echo '<!doctype html>';
    echo '<html lang="en">';
    

    /********** HEAD **********/
    echo '<head>';
    $dc->displayHead();
    echo '</head>';
    /********** HEAD - END **********/
	
	
    /********** BODY **********/
    echo '<body class="'.($_GET['a'] == '' ? 'landing_page' : $_GET['a']).'">';
    
    echo '<div id="content" '.($aai_instalacija ? 'class="aai"' : '').'>';


	// Glava
	echo '<header>';
	$dc->displayHeader();	
	echo '</header>';
    

    // Vsebina strani
    global $aai_instalacija;
    echo '<div id="main">';

    echo '<div class="main_content">';
	$dc->displayMain();	
    echo '</div>';
    
	echo '</div>';
	
	
    // Footer
    echo '<footer>';

    echo '<div class="footer_content">';
    $dc->displayFooter();	
    echo '</div>';

    echo '</footer>';
    
    
    echo '</div>';

    echo '</body>';
    /********** BODY - END **********/
    
    
	echo '</html>';
?>