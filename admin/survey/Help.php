<?php

class Help {

    /**
    * @desc izpise polje s helpom. 
    * ce smo v editmodu se bo prikazal textbox za urejanje helpa
    * ce smo v navadnem modu se bo prikazal help box
    */
    public static function display ($what) {
        global $admin_type, $lang;
        
        $sql = sisplet_query("SELECT help FROM srv_help WHERE what='$what' AND lang='$lang[id]'");
        $row = mysqli_fetch_array($sql);
        $help = $row['help'];
        
        if ($admin_type == 0 && isset($_COOKIE['edithelp'])) {
            return ' <a href="/" id="help_'.$what.'" lang="'.$lang['id'].'" class="edithelp" onclick="return false;" title_txt="">(?)</a>';  
        } 
        elseif ($help != '') {
            return ' <a href="/" id="help_'.$what.'" lang="'.$lang['id'].'" class="help" onclick="return false;" title_txt="">(?)</a>';
        }
        
    }
    
    /**
    * @desc vkljuci izkljuci editiranje helpa
    */
    static function edit_toggle () {
        global $lang;
        
        if (isset($_COOKIE['edithelp']))
            echo '<label><a href="ajax.php?t=help&a=edit_off" title="'.$lang['help'].'">'.$lang['srv_insend'].'</a></label>';
        else
            echo '<label><a href="ajax.php?t=help&a=edit_on" title="'.$lang['help'].'">'.$lang['start'].'</a></label>';
    }
    
    function ajax () {
        
        if ($_GET['a'] == 'edit_on') {
            $this->ajax_edit_on();
        } elseif ($_GET['a'] == 'edit_off') {
            $this->ajax_edit_off();
        } elseif ($_GET['a'] == 'display_edit_help') {
            $this->ajax_display_edit_help();
        } elseif ($_GET['a'] == 'save_help') {
            $this->ajax_save_help();
        } elseif ($_GET['a'] == 'display_help') {
            $this->ajax_display_help();
        }
    }
    
    /**
    * @desc vklopi editiranje helpa (nastavi cooike)
    */
    function ajax_edit_on () {
//        $anketa = $_GET['anketa'];
        
        setcookie('edithelp', 'on');
//        header("Location: index.php?anketa=$anketa");
        header("Location: index.php?a=nastavitve");
    }
    
    /**
    * @desc izklopi editiranje helpa (nastavi cooike)
    */
    function ajax_edit_off () {
//        $anketa = $_GET['anketa'];
        
        setcookie('edithelp', '', time()-3600);
//        header("Location: index.php?anketa=$anketa");
        header("Location: index.php?a=nastavitve");
    }
    
    /**
    * @desc prikaze formo za urejanje helpa
    */
    function ajax_display_edit_help () {
    	global $lang;
    	
        $l = (int)$_GET['lang'];
        
        $what = substr($_REQUEST['what'], 5);
        
        $sql = sisplet_query("SELECT help FROM srv_help WHERE what = '$what' AND lang='$l'");
        $row = mysqli_fetch_array($sql);

        echo '<textarea id="edithelp_'.$what.'" name="help" style="width:100%; height: 100px">'.$row['help'].'</textarea>';
        echo '<input type="button" value="'.$lang['save'].'" onclick="save_help(\''.$what.'\', \''.$l.'\')" />';
        
    }
    
    /** 
    * @desc shrani help
    */
    function ajax_save_help () {
    	$l = (int)$_GET['lang'];
    	
        $what = $_REQUEST['what'];
        $help = $_POST['help'];
        
        sisplet_query("REPLACE INTO srv_help (what, lang, help) VALUES ('$what', '$l', '$help')");
        
    }
    
    function ajax_display_help() {
        	
    	$l = (int)$_GET['lang'];
    	
		/*
		$regex = "#(help_)(.*)#e";
		$what = preg_replace($regex,"('$2')",$_GET['what']);
		print_r($output);
		*/

        $what = substr($_REQUEST['what'], 5);
        
        $sql = sisplet_query("SELECT help FROM srv_help WHERE what = '$what' AND lang='$l'");
        $row = mysqli_fetch_array($sql);
		
		echo '<div class="qtip-help">'.nl2br($row['help']).'</div>';
    }
}

?>