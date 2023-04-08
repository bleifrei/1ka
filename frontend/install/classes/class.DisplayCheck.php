<?php

	
class DisplayCheck{


	function __construct(){

	}
	
        
	// Izris strani za preverjanje konfiguracije streznika, baze
	public function displayCheckPage(){
		global $lang;
        
        echo '<h2>'.$lang['install_check_title'].'</h2>';

        echo '<p>'.$lang['install_check_text'].'</p><br/>';

        
        $red_error = false;

        // APACHE
        echo '<div class="check_segment apache">';
        echo '<h3>'.$lang['install_check_apache'].'</h3>';
        
        $apache_check = $this->apacheCheck();
        foreach($apache_check as $apache_item){

            if($apache_item['error'] == 1){
                $color_class = 'orange';
            }
            elseif($apache_item['error'] == 2){
                $color_class = 'red';
                $red_error = true;
            }
            else{
                $color_class = 'green';
            }

            echo '<div class="check_item">';
            echo '  <div class="what">'.$lang['install_check_'.$apache_item['what']].':</div>';
            echo '  <div class="value '.$color_class.'">'.$apache_item['value'].'</div>';
            echo '</div>';
        }
        echo '</div>';


        // PHP
        echo '<div class="check_segment php">';
        echo '<h3>'.$lang['install_check_php'].'</h3>';
        
        $php_check = $this->phpCheck();
        foreach($php_check as $php_item){

            if($php_item['error'] == 1){
                $color_class = 'orange';
            }
            elseif($php_item['error'] == 2){
                $color_class = 'red';
                $red_error = true;
            }
            else{
                $color_class = 'green';
            }

            echo '<div class="check_item">';
            echo '  <div class="what">'.$lang['install_check_'.$php_item['what']].':</div>';
            echo '  <div class="value '.$color_class.'">'.$php_item['value'].'</div>';
            echo '</div>';
        }
        echo '</div>';


        // SQL
        echo '<div class="check_segment sql">';
        echo '<h3>'.$lang['install_check_sql'].'</h3>';
        
        $sql_check = $this->sqlCheck();
        foreach($sql_check as $sql_item){

            if($sql_item['error'] == 1){
                $color_class = 'orange';
            }
            elseif($sql_item['error'] == 2){
                $color_class = 'red';
                $red_error = true;
            }
            else{
                $color_class = 'green';
            }

            echo '<div class="check_item">';
            echo '  <div class="what">'.$lang['install_check_'.$sql_item['what']].':</div>';
            echo '  <div class="value '.$color_class.'">'.$sql_item['value'].'</div>';
            echo '</div>';
        }
        echo '</div>';


        // OTHER
        echo '<div class="check_segment other">';
        echo '<h3>'.$lang['install_check_other'].'</h3>';
        
        $other_check = $this->otherCheck();
        foreach($other_check as $other_item){

            if($other_item['error'] == 1){
                $color_class = 'orange';
            }
            elseif($other_item['error'] == 2){
                $color_class = 'red';
                $red_error = true;
            }
            else{
                $color_class = 'green';
            }

            echo '<div class="check_item">';
            echo '  <div class="what">'.$lang['install_check_'.$other_item['what']].':</div>';
            echo '  <div class="value '.$color_class.'">'.$other_item['value'].'</div>';
            echo '</div>';
        }
        echo '</div>';        
        

        // Next button - if no red errors
        echo '<div class="bottom_buttons">';
        echo '  <a href="index.php?step=welcome"><input name="back" value="'.$lang['back'].'" type="button"></a>';
        if(!$red_error)
            echo '  <a href="index.php?step=database"><input type="button" value="'.$lang['next1'].'"></a>';
        else
            echo '  <a href="index.php?step=check"><input type="button" value="Ponovno preveri"></a>';
        echo '</div>';
    }


    // Preverimo apache
	private function apacheCheck(){
        global $lang;

        $result = array();

        $apache_modules = apache_get_modules();

        // Mod rewrite
        $result['mod_rewrite']['what'] = 'mod_rewrite';
        
        if(in_array('mod_rewrite', $apache_modules)){
            $result['mod_rewrite']['value'] = $lang['install_check_ok'];
        }
        else{
            $result['mod_rewrite']['value'] = $lang['install_check_not_ok'];
            $result['mod_rewrite']['error'] = 2;
        }

        return $result;
    }

    // Preverimo php verzijo
	private function phpCheck(){
        global $lang;

        $result = array();


        // Php verzija
        $php_version = phpversion();
        
        $result['version']['what'] = 'php_version';
        $result['version']['value'] = $php_version;

        // Zahtevan je php 7 ali 8.0
        if(substr($php_version, 0, 1) != '7' && substr($php_version, 0, 3) != '8.0'){
            $result['version']['error'] = 2;
        }


        // Php nastavitve
        // Open tag
        $result['opentag']['what'] = 'short_open_tag';
        if(ini_get('short_open_tag') == '1'){
            $result['opentag']['value'] = $lang['install_check_ok'];
        }
        else{
            $result['opentag']['value'] = 'Not enabled';
            $result['opentag']['error'] = 2;
        }

        // upload_max_filesize - 500M
        $result['upload_max_filesize']['what'] = 'upload_max_filesize';
        if((int)str_replace('M', '', ini_get('upload_max_filesize')) >= '500'){
            $result['upload_max_filesize']['value'] = ini_get('upload_max_filesize');
        }
        else{
            $result['upload_max_filesize']['value'] = ini_get('upload_max_filesize').' - '.$lang['install_check_upload_max_filesize_error'];
            $result['upload_max_filesize']['error'] = 1;
        }

        // max_execution_time - 120
        $result['max_execution_time']['what'] = 'max_execution_time';
        if((int)ini_get('max_execution_time') >= 120){
            $result['max_execution_time']['value'] = ini_get('max_execution_time');
        }
        else{
            $result['max_execution_time']['value'] = ini_get('max_execution_time').' - '.$lang['install_check_max_execution_time_error'];
            $result['max_execution_time']['error'] = 1;
        }

        // max_input_time - 120
        $result['max_input_time']['what'] = 'max_input_time';
        if((int)ini_get('max_input_time') >= 120){
            $result['max_input_time']['value'] = ini_get('max_input_time');
        }
        else{
            $result['max_input_time']['value'] = ini_get('max_input_time').' - '.$lang['install_check_max_input_time_error'];
            $result['max_input_time']['error'] = 1;
        }

        // max_input_vars - 8000
        /*$result['max_input_vars']['what'] = 'max_input_vars';
        if((int)ini_get('max_input_vars') >= 8000){
            $result['max_input_vars']['value'] = ini_get('max_input_vars');
        }
        else{
            $result['max_input_vars']['value'] = 'Recommended value is 8000';
            $result['max_input_vars']['error'] = true;
        }*/

        // memory_limit - 512M
        $result['memory_limit']['what'] = 'memory_limit';
        if((int)str_replace('M', '', ini_get('memory_limit')) >= 512){
            $result['memory_limit']['value'] = ini_get('memory_limit');
        }
        else{
            $result['memory_limit']['value'] = ini_get('memory_limit').' - '.$lang['install_check_memory_limit_error'];
            $result['memory_limit']['error'] = 1;
        }

        // post_max_size - 500M
        $result['post_max_size']['what'] = 'post_max_size';
        if((int)str_replace('M', '', ini_get('post_max_size')) >= 500){
            $result['post_max_size']['value'] = ini_get('post_max_size');
        }
        else{
            $result['post_max_size']['value'] = ini_get('post_max_size').' - '.$lang['install_check_post_max_size_error'];
            $result['post_max_size']['error'] = 1;
        }


        // Php moduli
        // Mbstring
        $result['mbstring']['what'] = 'mbstring';
        if(extension_loaded('mbstring')){
            $result['mbstring']['value'] = $lang['install_check_ok'];
        }
        else{
            $result['mbstring']['value'] = $lang['install_check_not_ok'];
            $result['mbstring']['error'] = 2;
        }

        // Openssl
        $result['openssl']['what'] = 'openssl';
        if(extension_loaded('openssl')){
            $result['openssl']['value'] = $lang['install_check_ok'];
        }
        else{
            $result['openssl']['value'] = $lang['install_check_not_ok'];
            $result['openssl']['error'] = 2;
        }

        // GD
        $result['gd']['what'] = 'gd';
        if(extension_loaded('gd')){
            $result['gd']['value'] = $lang['install_check_ok'];
        }
        else{
            $result['gd']['value'] = $lang['install_check_not_ok'];
            $result['gd']['error'] = 2;
        }

        // bcmath
        $result['bcmath']['what'] = 'bcmath';
        if(extension_loaded('bcmath')){
            $result['bcmath']['value'] = $lang['install_check_ok'];
        }
        else{
            $result['bcmath']['value'] = $lang['install_check_not_ok'];
            $result['bcmath']['error'] = 2;
        }

        // zip
        $result['zip']['what'] = 'zip';
        if(extension_loaded('zip')){
            $result['zip']['value'] = $lang['install_check_ok'];
        }
        else{
            $result['zip']['value'] = $lang['install_check_not_ok'];
            $result['zip']['error'] = 2;
        }


        return $result;
    }

    // Preverimo sql
	private function sqlCheck(){
        global $lang;

        $result = array();

        // Sql version
        $sql_version = mysqli_get_server_info($GLOBALS['connect_db']);

        $result['version']['what'] = 'sql_version';
        $result['version']['value'] = $sql_version;

        if(false){
            $result['version']['error'] = 2;
        }


        // Strict
        $sql_mode = sisplet_query("SHOW VARIABLES LIKE 'sql_mode'");
        $row_mode = mysqli_fetch_array($sql_mode);
            
        $result['strict']['what'] = 'sql_strict';

        if (strpos($row_mode[0], 'STRICT_TRANS_TABLES') === false) {
            $result['strict']['value'] = $lang['install_check_ok'];
        }
        else{
            $result['strict']['value'] = $lang['install_check_sql_strict_error'];
            $result['strict']['error'] = 2;
        }


        return $result;
    }

    // Preverimo ostalo
	private function otherCheck(){
        global $lang;

        $result = array();


        // SED
        $output = array();
        exec("sed 2>&1", $output1);
        $result['sed']['what'] = 'sed';

        if(strpos($output1[0], 'not found') === false){
            $result['sed']['value'] = $lang['install_check_ok'];
        }
        else{
            $result['sed']['value'] = $lang['install_check_sed_error'];
            $result['sed']['error'] = 2;
        }


        // AWK
        $output = array();
        exec("awk 2>&1", $output);
        $result['awk']['what'] = 'awk';

        if(strpos($output[0], 'not found') === false){
            $result['awk']['value'] = $lang['install_check_ok'];
        }
        else{
            $result['awk']['value'] = $lang['install_check_awk_error'];
            $result['awk']['error'] = 2;
        }


        // Rscript
        $output = array();
        exec("Rscript 2>&1", $output);
        $result['rscript']['what'] = 'r';

        if(strpos($output[0], 'not found') === false){
            $result['rscript']['value'] = $lang['install_check_ok'];
        }
        else{
            $result['rscript']['value'] = $lang['install_check_r_error'];
            $result['rscript']['error'] = 1;
        }


        return $result;
    }
}