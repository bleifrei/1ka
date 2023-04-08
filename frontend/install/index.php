<?php 
	
	session_start();
    
    
	//phpinfo();
	/*error_reporting(1);
	ini_set('display_errors', 'On');*/
	

	include_once('classes/class.Display.php');
	
	// Inicializiramo razred za prikaz
	$display = new Display();
	
    
    echo '<!doctype html>';
    echo '<html lang="en">';
    

    /********** HEAD **********/
    echo '<head>';
    $display->displayHead();
    echo '</head>';
    /********** HEAD - END **********/
	
	
    /********** BODY **********/
    echo '<body>';
    
    echo '<div id="content">';


	// Glava
	echo '<header>';
	$display->displayHeader();	
	echo '</header>';
    

    // Vsebina strani
    echo '<div id="main">';
	$display->displayMain();	
	echo '</div>';
	
	
    // Footer
    echo '<footer>';

    echo '<div class="footer_content">';
    $display->displayFooter();	
    echo '</div>';

    echo '</footer>';
    
    
    echo '</div>';

    echo '</body>';
    /********** BODY - END **********/
    
    
	echo '</html>';
?>